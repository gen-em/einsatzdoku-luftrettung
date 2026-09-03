<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email_lib.php';   // ist_dublettenfehler()

/**
 * KOPPLUNGSSITZUNGEN (Web 13.0.0, S5 / R49) — die Schicht zwischen dem
 * Endpunkt (pair.php), der Geraeteseite (einstellungen.php) und der
 * Kopplungsprobe (tools/kopplungsprobe/).
 *
 * WARUM EINE EIGENE DATEI. Drei Stellen greifen auf dieselbe Tabelle zu, und
 * zwei Regeln daran duerfen nirgends verschieden ausgelegt werden:
 *
 *   1. Die Frist. EINE Frist ab `erstellt_am` fuer alles (E-S5-12): Anlegen,
 *      Nachfragen, Eingeben im Web und das Ja am Geraet rechnen alle gegen
 *      dieselbe Bedingung (pair_frist_sql()). Wer sie an einer Stelle anders
 *      schriebe — etwa `>=` statt `>` —, haette zwei Fristen.
 *   2. Der Schiedsrichter. Beanspruchen ist ein UPDATE mit `user_id IS NULL`
 *      in der Bedingung und gilt nur bei rowCount() = 1 (E-S5-13). Zwei
 *      Browser mit demselben Code: genau einer gewinnt, und zwar der, dessen
 *      Aenderung die Datenbank zuerst schreibt — nicht der, der zuerst
 *      gelesen hat.
 *
 * Dazu die Dublettenschleife beim Anlegen, damit die Probe sie mit einer
 * eingeschobenen Codequelle pruefen kann (Fall 25) — ueber HTTP liesse sich
 * der Zufall nicht patchen.
 *
 * Alles hier wirft PDOException durch; die Aufrufer entscheiden, was ein
 * Fehler fuer SIE bedeutet (pair.php: 500 und die Sitzung bleibt).
 */

/** SQL-Bedingung „Sitzung noch nicht verfallen“ — an jeder Stelle dieselbe. */
function pair_frist_sql(): string
{
    return 'erstellt_am > DATE_SUB(NOW(), INTERVAL ' . PAIR_TTL_MIN . ' MINUTE)';
}

/** Zahl der unverfallenen Sitzungen — die Zaehlung hinter PAIR_SITZUNGEN_MAX (E-S5-14). */
function pair_sitzungen_offen(PDO $pdo): int
{
    return (int)$pdo->query('SELECT COUNT(*) FROM pair_sessions WHERE ' . pair_frist_sql())
                    ->fetchColumn();
}

/** Ein Anzeigecode: PAIR_LEN Zeichen aus PAIR_CHARS, gleichverteilt. */
function pair_code_ziehen(): string
{
    $code = '';
    $n = strlen(PAIR_CHARS);
    for ($i = 0; $i < PAIR_LEN; $i++) { $code .= PAIR_CHARS[random_int(0, $n - 1)]; }
    return $code;
}

/**
 * Eine Eingabe aus dem Web in die Form bringen, in der der Code gespeichert
 * ist: ohne Leerzeichen und Bindestriche (das Geraet zeigt „AB3 K7Q“), in
 * Grossschreibung. Ob das Ergebnis dem Muster PAIR_RE genuegt, prueft der
 * Aufrufer — die Antwort darauf entscheidet, ob ein Fehlversuch zaehlt.
 */
function pair_code_normalisieren(string $eingabe): string
{
    return strtoupper((string)preg_replace('/[\s\-]+/u', '', trim($eingabe)));
}

/**
 * Sitzung anlegen; gibt den Code zurueck.
 *
 * FUENF VERSUCHE GEGEN DIE DUBLETTE am Code (Muster der alten Codeerzeugung in
 * einstellungen.php): Bei hoechstens 1000 offenen Sitzungen in 1,07 Milliarden
 * Moeglichkeiten trifft ein Versuch mit unter einem Millionstel; fuenf
 * Fehlschlaege hintereinander sind kein Zufall mehr, sondern ein Fehler.
 *
 * @param array{art: ?string, modell: ?string, teil: ?string} $geraet
 *        Ergebnis von geraet_block_lesen() — darf drei NULL-Werte tragen.
 * @param callable(): string|null $codeQuelle  NUR fuer die Probe: liefert die
 *        Codes der Reihe nach, damit sich eine Dublette erzwingen laesst.
 * @throws RuntimeException nach fuenf Dubletten; PDOException fuer alles andere
 */
function pair_sitzung_anlegen(PDO $pdo, string $deviceId, string $apiKeyHash,
                              array $geraet, ?callable $codeQuelle = null): string
{
    $quelle = $codeQuelle ?? 'pair_code_ziehen';
    $st = $pdo->prepare(
        'INSERT INTO pair_sessions (code, device_id, api_key_hash,
                                    geraet_art, geraet_modell, geraet_teil)
         VALUES (?,?,?,?,?,?)');
    for ($versuch = 1; $versuch <= 5; $versuch++) {
        $code = (string)$quelle();
        try {
            $st->execute([$code, $deviceId, $apiKeyHash,
                          $geraet['art'] ?? null, $geraet['modell'] ?? null,
                          $geraet['teil'] ?? null]);
            return $code;
        } catch (PDOException $ex) {
            if (!ist_dublettenfehler($ex)) { throw $ex; }
            // Dublette am Code — neuer Code, neuer Versuch. (Eine Dublette an
            // der Kennung aus 16 Zufallsbytes kaeme praktisch nie vor und
            // endete nach fuenf Versuchen unten — sichtbar, nicht still.)
        }
    }
    throw new RuntimeException('Kein freier Kopplungscode nach fuenf Versuchen.');
}

/**
 * Die Sitzung zu einer Geraetekennung — fuer die kopfzeilen-ausgewiesenen
 * Anliegen (status, bestaetigen). Liefert die Zeile MIT `rest_s` (Restzeit in
 * Sekunden, kann <= 0 sein) und `konto_email` (NULL, solange niemand
 * beansprucht hat), oder null, wenn es die Kennung nicht gibt.
 *
 * VERFALLENE SITZUNGEN KOMMEN MIT: Der Aufrufer muss 410 („abgelaufen“, die
 * Zugangsdaten stimmen) von 401 („unbekannt“) unterscheiden koennen. Die
 * Restzeit rechnet die Datenbank, nicht PHP — beide Seiten des Vergleichs
 * stehen dann in derselben Uhr (Verbindung auf UTC, db.php).
 */
function pair_sitzung_nach_kennung(PDO $pdo, string $deviceId): ?array
{
    $st = $pdo->prepare(
        'SELECT s.*, u.email AS konto_email,
                TIMESTAMPDIFF(SECOND, NOW(),
                              DATE_ADD(s.erstellt_am, INTERVAL ' . PAIR_TTL_MIN . ' MINUTE)) AS rest_s
         FROM pair_sessions s
         LEFT JOIN users u ON u.id = s.user_id
         WHERE s.device_id = ?');
    $st->execute([$deviceId]);
    $zeile = $st->fetch();
    return $zeile === false ? null : $zeile;
}

/**
 * Die Sitzung zu einem Code — fuer die Bestaetigungsseite im Web. Nur eine
 * OFFENE und UNVERFALLENE Sitzung wird gefunden; alles andere ist fuer die
 * Eingabe „diesen Code kennt der Server nicht“ (falsch, abgelaufen, schon
 * verwendet — der Unterschied ginge nur einen Ratenden etwas an).
 */
function pair_sitzung_nach_code(PDO $pdo, string $code): ?array
{
    $st = $pdo->prepare(
        'SELECT *, TIMESTAMPDIFF(SECOND, NOW(),
                                 DATE_ADD(erstellt_am, INTERVAL ' . PAIR_TTL_MIN . ' MINUTE)) AS rest_s
         FROM pair_sessions
         WHERE code = ? AND user_id IS NULL AND ' . pair_frist_sql());
    $st->execute([$code]);
    $zeile = $st->fetch();
    return $zeile === false ? null : $zeile;
}

/**
 * Beanspruchen: das Konto an die Sitzung binden (E-S5-13).
 *
 * Gilt nur bei rowCount() = 1. `user_id IS NULL` steht in der BEDINGUNG, nicht
 * in einer vorherigen Abfrage — sonst faenden zwei gleichzeitige Klicks den
 * Code beide frei und beide bekaemen ihn. Die Frist steht ebenfalls in der
 * Bedingung: Ein Klick in Minute elf aendert nichts, und zwar ohne dass hier
 * jemand auf die Uhr sehen muesste.
 */
function pair_sitzung_beanspruchen(PDO $pdo, string $code, int $userId): bool
{
    $st = $pdo->prepare(
        'UPDATE pair_sessions SET user_id = ?
         WHERE code = ? AND user_id IS NULL AND ' . pair_frist_sql());
    $st->execute([$userId, $code]);
    return $st->rowCount() === 1;
}
