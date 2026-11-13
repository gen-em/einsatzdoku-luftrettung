<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/email_lib.php';   // ist_dublettenfehler()
require_once __DIR__ . '/geraete_lib.php';  // geraet_vorgabename(), geraet_bezeichnung()
require_once __DIR__ . '/einstieg_lib.php'; // erststart_erledigt()
require_once __DIR__ . '/mail_lib.php';     // mail_einreihen()
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text()

/**
 * KOPPLUNGSSITZUNGEN — die Schicht zwischen dem
 * Endpunkt (pair.php), der Geraeteseite (einstellungen.php) und der
 * Kopplungsprobe (tools/proben/kopplung/).
 *
 * WARUM EINE EIGENE DATEI. Drei Stellen greifen auf dieselbe Tabelle zu, und
 * zwei Regeln daran duerfen nirgends verschieden ausgelegt werden:
 *
 *   1. Die Frist. EINE Frist ab `erstellt_am` fuer alles: Anlegen,
 *      Nachfragen, Eingeben im Web und das Ja am Geraet rechnen alle gegen
 *      dieselbe Bedingung (pair_frist_sql()). Wer sie an einer Stelle anders
 *      schriebe — etwa `>=` statt `>` —, haette zwei Fristen.
 *   2. Der Schiedsrichter. Beanspruchen ist ein UPDATE mit `user_id IS NULL`
 *      in der Bedingung und gilt nur bei rowCount() = 1. Zwei
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

/** Zahl der unverfallenen Sitzungen — die Zaehlung hinter PAIR_SITZUNGEN_MAX. */
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
 * Der Code, wie ein Mensch ihn liest: zwei Dreiergruppen, „AB3 K7Q“.
 *
 * Dieselbe Form zeigt das Geraet. Der Server SPEICHERT ihn ohne
 * Trennzeichen; die Gruppierung ist reine Anzeige, und die Eingabe nimmt beide
 * Formen (pair_code_normalisieren()). Geteilt wird in der Mitte, damit die
 * Regel eine Aenderung von PAIR_LEN ueberlebt.
 */
function pair_code_anzeigen(string $code): string
{
    return implode(' ', str_split($code, (int)ceil(PAIR_LEN / 2)));
}

/**
 * Restzeit in Worten: volle Minuten, unter einer Minute
 * Sekunden. „Noch 9 Minuten“ ist genauer, als es aussieht — die Zahl kommt vom
 * Server, nicht von der Uhr des Geraets.
 *
 * OHNE NACHKOMMASTELLEN UND OHNE SEKUNDEN OBERHALB EINER MINUTE: Eine Anzeige,
 * die im Sekundentakt springt, zieht den Blick auf sich und sagt nichts, was
 * die Person tun koennte. Erst in der letzten Minute ist die Sekunde die
 * Auskunft, auf die es ankommt.
 */
function pair_restzeit_text(int $sekunden): string
{
    if ($sekunden <= 0)  { return 'abgelaufen'; }
    if ($sekunden < 60)  { return $sekunden . ' Sekunden'; }
    $min = intdiv($sekunden, 60);
    return $min === 1 ? 'eine Minute' : $min . ' Minuten';
}

/**
 * Eine Sitzung verwerfen — der Abbruch im Web.
 *
 * DAS KONTO STEHT IN DER BEDINGUNG, nicht in einer vorherigen Abfrage: Wer
 * eine fremde Kennung schickt, loescht nichts, und die Antwort ist dieselbe.
 * Wirkung am Geraet: Seine naechste Frage laeuft ins Leere (401), und es sagt
 * „Code abgelaufen — neuer Code“. Das ist richtig; die Sitzung ist weg.
 */
function pair_sitzung_verwerfen(PDO $pdo, string $deviceId, int $userId): bool
{
    $st = $pdo->prepare('DELETE FROM pair_sessions WHERE device_id = ? AND user_id = ?');
    $st->execute([$deviceId, $userId]);
    return $st->rowCount() === 1;
}

/**
 * Gibt es zu dieser Kennung schon ein Geraet in diesem Konto?
 *
 * Die eine Frage, die das Web nach dem Beanspruchen wiederholt stellt, bis das
 * Geraet Ja gesagt hat. Sie steht hier und nicht als SQL in der
 * Seite, damit der Endpunkt und die Seite dieselbe Antwort bekommen.
 */
function pair_geraet_da(PDO $pdo, string $deviceId, int $userId): bool
{
    $st = $pdo->prepare('SELECT 1 FROM devices WHERE device_id = ? AND user_id = ?');
    $st->execute([$deviceId, $userId]);
    return $st->fetchColumn() !== false;
}

/**
 * Beanspruchen: das Konto an die Sitzung binden.
 *
 * Gilt nur bei rowCount() = 1. `user_id IS NULL` steht in der BEDINGUNG, nicht
 * in einer vorherigen Abfrage — sonst faenden zwei gleichzeitige Klicks den
 * Code beide frei und beide bekaemen ihn. Die Frist steht ebenfalls in der
 * Bedingung: Ein Klick in Minute elf aendert nichts, und zwar ohne dass hier
 * jemand auf die Uhr sehen muesste.
 */
function pair_sitzung_beanspruchen(PDO $pdo, string $code, int $userId, ?string $nB = null): bool
{
    /* N_b GEHT MIT DEMSELBEN UPDATE — genau einmal, und nur zusammen mit
     * dem Konto, das die Sitzung nimmt (KA-06d). Ohne die Spalten (Migration
     * steht aus) bleibt es beim alten Weg. */
    if ($nB !== null && pair_herkunft_bereit($pdo)) {
        $st = $pdo->prepare(
            'UPDATE pair_sessions SET user_id = ?, n_b = ?
             WHERE code = ? AND user_id IS NULL AND ' . pair_frist_sql());
        $st->execute([$userId, $nB, $code]);
    } else {
        $st = $pdo->prepare(
            'UPDATE pair_sessions SET user_id = ?
             WHERE code = ? AND user_id IS NULL AND ' . pair_frist_sql());
        $st->execute([$userId, $code]);
    }
    return $st->rowCount() === 1;
}

/* =========================================================================
 * DIE HERKUNFT DER KOPPLUNG — Zusage und Vergleichswert (KA-06d, E-KA-61)
 * =========================================================================
 *
 * Die Rechnung steht in `tools/proben/huelle/referenz.py` (Kopf „DER
 * VERGLEICHSWERT DER KOPPLUNG") und im Browser in `assets/huelle2.js`. Der
 * Server rechnet NICHTS davon: Er trägt Geräteschlüssel (SPK), Zusage und
 * die beiden Zufallszahlen von einer Seite zur anderen und hält die
 * Reihenfolge — N_b genau einmal, beim Beanspruchen; N_g erst danach. Ob
 * die Zusage zu N_g passt, prüft nur der Browser; ein Server, der hier
 * lügt, fällt am Vergleichswert auf, nicht an einer Prüfung des Servers.
 *
 * ABGESCHLOSSEN wird eine Sitzung mit Geräteschlüssel erst, wenn BEIDE
 * zugestimmt haben: das Gerät (Ja) und die Webseite („Wert stimmt“). Wer
 * zuletzt kommt, legt das Gerät an (`pair_abschliessen()`). Eine Sitzung
 * ohne Geräteschlüssel (ältere App) schließt wie bisher mit dem Ja.
 *
 * BIS DIE MIGRATION `2026_11_13_kopplung_herkunft` GELAUFEN IST, gibt es
 * die Spalten nicht; `pair_herkunft_bereit()` sagt es, und dann koppelt
 * jedes Gerät auf dem alten Weg — der Block `schluessel` wird überlesen.
 */

/** Länge der Zufallszahlen N_g und N_b in Byte. */
const PAIR_NONCE_LAENGE = 16;

/** Gibt es die Spalten der Herkunft schon? Einmal je Anfrage gefragt. */
function pair_herkunft_bereit(PDO $pdo): bool
{
    static $bereit = null;
    if ($bereit === null) {
        $bereit = db_hat_spalte($pdo, 'pair_sessions', 'zusage') && db_hat_spalte($pdo, 'devices', 'spk');
    }
    return $bereit;
}

/**
 * Ein Base64-Wert fester Länge in kanonischer Form — oder null. Streng:
 * nur das Standardalphabet, mit Auffüllung, und genau so, wie PHP denselben
 * Inhalt schriebe. So steht in der Datenbank nie zweimal derselbe Wert in
 * zwei Schreibweisen.
 */
function pair_b64(mixed $wert, int $laenge): ?string
{
    if (!is_string($wert) || $wert === '' || strlen($wert) > 4 * intdiv($laenge + 2, 3)) { return null; }
    $roh = base64_decode($wert, true);
    if ($roh === false || strlen($roh) !== $laenge || base64_encode($roh) !== $wert) { return null; }
    return $wert;
}

/**
 * Der Block `schluessel` aus `start`: null, wenn er fehlt (ältere App);
 * false, wenn er in falscher Form kommt; sonst {spk, zusage}. SPK ist ein
 * öffentlicher P-256-Schlüssel in SEC1 (65 Byte, 0x04 vorn) — ob er auf der
 * Kurve liegt, prüft der Browser beim Rechnen, nicht der Server.
 */
function pair_schluessel_lesen(mixed $block): array|false|null
{
    if ($block === null) { return null; }
    if (!is_array($block)) { return false; }
    $spk = pair_b64($block['spk'] ?? null, 65);
    $zusage = pair_b64($block['zusage'] ?? null, 32);
    if ($spk === null || $zusage === null || base64_decode($spk, true)[0] !== "\x04") { return false; }
    return ['spk' => $spk, 'zusage' => $zusage];
}

/** SPK und Zusage an eine frisch angelegte Sitzung. */
function pair_schluessel_setzen(PDO $pdo, string $deviceId, array $schluessel): void
{
    $pdo->prepare('UPDATE pair_sessions SET spk = ?, zusage = ? WHERE device_id = ? AND user_id IS NULL')
        ->execute([$schluessel['spk'], $schluessel['zusage'], $deviceId]);
}

/**
 * N_g des Geräts annehmen — wiederholbar (die Antwort kann verloren gehen):
 * 'ok' beim ersten Mal und bei demselben Wert, 'anders' bei einem anderen,
 * 'zustand', wenn die Sitzung dafür nicht bereit ist (nicht beansprucht,
 * ohne Geräteschlüssel, ohne N_b oder abgelehnt).
 */
function pair_offenlegen(PDO $pdo, int $sitzungId, string $nG): string
{
    $st = $pdo->prepare('UPDATE pair_sessions SET n_g = ?
                          WHERE id = ? AND n_g IS NULL AND user_id IS NOT NULL
                            AND spk IS NOT NULL AND n_b IS NOT NULL AND abgelehnt IS NULL');
    $st->execute([$nG, $sitzungId]);
    if ($st->rowCount() === 1) { return 'ok'; }
    $z = $pdo->prepare('SELECT n_g, user_id, spk, n_b, abgelehnt FROM pair_sessions WHERE id = ?');
    $z->execute([$sitzungId]);
    $s = $z->fetch();
    if (!$s || $s['user_id'] === null || $s['spk'] === null || $s['n_b'] === null || $s['abgelehnt'] !== null) {
        return 'zustand';
    }
    return hash_equals((string)$s['n_g'], $nG) ? 'ok' : 'anders';
}

/** Ist diese Sitzung bereit zum Abschluss? Ohne Geräteschlüssel: das Ja
 *  genügt (älterer Weg). Mit: Ja UND „Wert stimmt“. */
function pair_abschlussreif(array $s): bool
{
    if (($s['spk'] ?? null) === null) { return true; }
    return (int)($s['geraet_ja'] ?? 0) === 1 && (int)($s['wert_ok'] ?? 0) === 1;
}

/**
 * DAS GERÄT ENTSTEHT — eine Transaktion, für beide Wege (Ja am Gerät in
 * `pair.php`, „Wert stimmt“ im Web). SELECT … FOR UPDATE sperrt die
 * Zeile; ein zweiter Aufruf wartet und findet sie danach nicht mehr.
 * Anlegen und Löschen stehen in EINEM Commit.
 *
 * DAS GERÄTELIMIT WIRD HIER NOCH EINMAL GEPRÜFT, obwohl das Web es beim
 * Eingeben schon getan hat: Zwischen Klick und Ja kann ein Gerät
 * dazugekommen sein. Die Sitzung ist danach weg.
 *
 * @return array{ergebnis: string, frisch: ?array}  ergebnis:
 *   'angelegt' | 'verschwunden' | 'nicht_beansprucht' | 'device_limit' | 'wartet'
 */
function pair_abschliessen(PDO $pdo, int $sitzungId): array
{
    /* `db_transaktion()` (CLAUDE.md 4): Der Rumpf gibt sein Ergebnis zurueck
     * statt mittendrin zurueckzurollen — wo er nichts geschrieben hat, ist
     * das Bestaetigen dasselbe wie ein Zurueckrollen. Bis Web 24.17.0 stand
     * der Rahmen von Hand in pair.php (Ausnahme im Register, Z16). */
    $erg = db_transaktion($pdo, function (PDO $pdo) use ($sitzungId): array {
        $st = $pdo->prepare('SELECT * FROM pair_sessions WHERE id = ? FOR UPDATE');
        $st->execute([$sitzungId]);
        $frisch = $st->fetch();
        if (!$frisch) { return ['ergebnis' => 'verschwunden', 'frisch' => null]; }
        if ($frisch['user_id'] === null) { return ['ergebnis' => 'nicht_beansprucht', 'frisch' => $frisch]; }
        if (!pair_abschlussreif($frisch)) { return ['ergebnis' => 'wartet', 'frisch' => $frisch]; }
        if (geraete_grenze_erreicht($pdo, (int)$frisch['user_id'])) {
            $pdo->prepare('DELETE FROM pair_sessions WHERE id = ?')->execute([$sitzungId]);
            return ['ergebnis' => 'device_limit', 'frisch' => $frisch];
        }
        $spalten = 'user_id, device_id, api_key_hash, label, geraet_art, geraet_modell, geraet_teil';
        $werte = [(int)$frisch['user_id'], (string)$frisch['device_id'], (string)$frisch['api_key_hash'],
                  /* Vorgabename nach der gemeldeten Art, OHNE Datum — das
                   * Datum gehoert der Zeile (devices.created_at). */
                  geraet_vorgabename($frisch['geraet_art']),
                  $frisch['geraet_art'], $frisch['geraet_modell'], $frisch['geraet_teil']];
        if (pair_herkunft_bereit($pdo)) {
            /* `herkunft` = im Gerätebuch des Browsers eingetragen. Nur dann
             * darf das Gerät Fassung 2 senden (`huelle: 2`): Eine Hülle eines
             * Absenders, den das Buch nicht kennt, ginge nicht auf. */
            $spalten .= ', spk, herkunft';
            $werte[] = $frisch['spk'] ?? null;
            $werte[] = (int)($frisch['im_buch'] ?? 0);
        }
        $pdo->prepare("INSERT INTO devices ($spalten) VALUES (" . implode(',', array_fill(0, count($werte), '?')) . ')')
            ->execute($werte);
        $pdo->prepare('DELETE FROM pair_sessions WHERE id = ?')->execute([$sitzungId]);
        return ['ergebnis' => 'angelegt', 'frisch' => $frisch];
    });
    if ($erg['ergebnis'] !== 'angelegt') { return $erg; }
    $ownerId = (int)$erg['frisch']['user_id'];
    /* DER DRITTE SCHRITT DES ERSTSTARTS IST ERLEDIGT. Nach dem Commit: Der
     * Vermerk ist eine Anzeige, er darf die Kopplung nicht zurueckrollen,
     * und `erststart_erledigt()` faengt seine Fehler selbst. */
    erststart_erledigt($ownerId, ERSTSTART_GERAET);
    return $erg;
}

/**
 * Den Kontoinhaber benachrichtigen: Dies ist die Stelle, an der ein Gerät
 * zum Konto DAZUKOMMT. Ein Fehlschlag des Versands berührt die Kopplung
 * nicht; er landet im Reiter System.
 */
function pair_gekoppelt_melden(PDO $pdo, array $frisch): void
{
    try {
        $ust = $pdo->prepare('SELECT email FROM users WHERE id = ?');
        $ust->execute([(int)$frisch['user_id']]);
        $mail = $ust->fetchColumn();
        if ($mail !== false && $mail !== null && $mail !== '') {
            mail_einreihen('geraet_gekoppelt', (string)$mail, [
                'geraet'    => geraet_bezeichnung($frisch['geraet_art'],
                                   $frisch['geraet_modell'], $frisch['geraet_teil']),
                'geraet_id' => (string)$frisch['device_id'],
                'zeitpunkt' => datum_zeit_text(gmdate('Y-m-d H:i:s')),
            ]);
        }
    } catch (Throwable $ex) {
        system_melden('pair', 'Hinweis auf neues Gerät konnte nicht verschickt werden', $ex);
    }
}

/**
 * Die Antwort der Webseite auf den Vergleichswert. `ja`: „Wert stimmt“,
 * `$imBuch` sagt, ob der Browser das Gerät ins Gerätebuch eingetragen hat
 * (mit ausgelieferter Weiche nicht). `nein`: Wert anders oder Zusage
 * unpassend — die Sitzung bleibt bis zum Ende der Frist als abgelehnt
 * stehen, damit das Gerät den Grund erfährt.
 *
 * @return string 'ok' | 'zustand' (nichts zu bestätigen: keine Sitzung
 *                dieses Kontos, kein Geräteschlüssel, kein N_g, abgelehnt)
 */
function pair_wert_melden(PDO $pdo, string $deviceId, int $userId, bool $ja, string $grund, bool $imBuch): string
{
    if ($ja) {
        $st = $pdo->prepare('UPDATE pair_sessions SET wert_ok = 1, im_buch = ?
                              WHERE device_id = ? AND user_id = ? AND spk IS NOT NULL
                                AND n_g IS NOT NULL AND abgelehnt IS NULL AND ' . pair_frist_sql());
        $st->execute([$imBuch ? 1 : 0, $deviceId, $userId]);
        if ($st->rowCount() === 0) {
            /* Ein zweites „Wert stimmt“ (Doppelklick, verlorene Antwort):
             * MySQL zählt eine Zeile ohne Änderung nicht mit. */
            $z = $pdo->prepare('SELECT wert_ok FROM pair_sessions WHERE device_id = ? AND user_id = ?
                                   AND abgelehnt IS NULL');
            $z->execute([$deviceId, $userId]);
            return (int)$z->fetchColumn() === 1 ? 'ok' : 'zustand';
        }
    } else {
        $st = $pdo->prepare('UPDATE pair_sessions SET abgelehnt = ?
                              WHERE device_id = ? AND user_id = ? AND abgelehnt IS NULL');
        $st->execute([$grund === 'zusage' ? 'zusage' : 'wert', $deviceId, $userId]);
    }
    return $st->rowCount() === 1 ? 'ok' : 'zustand';
}

/**
 * Das Feld `huelle` für die Antworten an ein gekoppeltes Gerät: `2`, wenn
 * es einen Geräteschlüssel hat UND der Browser ihn ins Gerätebuch
 * eingetragen hat — sonst fehlt es, und das Gerät sendet Fassung 1.
 */
function pair_huelle_feld(?string $spk, int $herkunft): array
{
    return ($spk !== null && $spk !== '' && $herkunft === 1) ? ['huelle' => 2] : [];
}
