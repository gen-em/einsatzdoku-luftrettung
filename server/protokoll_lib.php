<?php
declare(strict_types=1);
/**
 * DAS BETRIEBSPROTOKOLL — der Schreibweg (P5b/AP1, E-P5b-12, V1).
 *
 * ---------------------------------------------------------------------------
 * WAS HIER HINEINGESCHRIEBEN WIRD — UND WAS AUSDRUECKLICH NICHT
 * ---------------------------------------------------------------------------
 *
 * Betriebsereignisse. Konto angelegt, freigeschaltet, gesperrt, geloescht;
 * Rolle oder Adresse geaendert; Sicherung eingespielt; Wartung gefahren;
 * Schluesselblatt bestaetigt; Mail versandt; Job gelaufen.
 *
 * NICHT: wer wann welchen Einsatz geoeffnet, gelesen oder exportiert hat.
 * Das ist V1, entschieden am 16.09.2026, und es ist keine Nachlaessigkeit,
 * sondern die Zusage: Diese Anwendung fuehrt KEIN Zugriffsprotokoll. Wer hier
 * einen Eintrag „Einsatz 417 angesehen" ergaenzen will, aendert eine
 * Programmentscheidung und nicht eine Funktion.
 *
 * KEINE IP-ADRESSEN (V2, E-P5b-06). Sie stehen ausschliesslich im Reiter
 * *Sicherheit*, und der liegt in einer anderen Tabelle
 * (`sicherheit_ereignisse`, P5a/AP6) mit einer eigenen, festen Frist von 30
 * Tagen. Diese Trennung ist der Grund, warum P5b die bestehende Tabelle NICHT
 * anfasst: Zwei verschiedene Fristen und zwei verschiedene
 * Datenschutz-Begruendungen gehoeren nicht in eine Tabelle, die man
 * versehentlich gemeinsam aufbewahrt. Ob die beiden spaeter zusammenrueckten,
 * entscheidet 10c (V6).
 *
 * ---------------------------------------------------------------------------
 * WARUM DAS SCHEITERN DES SCHREIBENS DIE HANDLUNG NICHT SCHEITERN LAESST
 * ---------------------------------------------------------------------------
 *
 * V7, so entschieden: **Still scheitern ist schlechter als laut, laut
 * abbrechen ist schlechter als still.** Ein Protokoll, das eine
 * Kontoloeschung verhindert, weil seine Tabelle fehlt, ist ein Protokoll, das
 * den Betrieb anhaelt, um ueber den Betrieb zu berichten. Ein Protokoll, das
 * unbemerkt nichts schreibt, ist keines.
 *
 * Der Mittelweg hat drei Stufen, und alle drei muessen da sein:
 *   1. `error_log()` mit der Kennung `protokoll:` — fuer die Betreiberin, die
 *      ins Serverprotokoll sieht. Eine der zwei Stellen, die `error_log()`
 *      noch rufen (Register Z38): Wer hier `system_melden()` riefe, schriebe
 *      den Fehlschlag des Protokolls ins Protokoll.
 *   2. Der Zaehler `protokoll_fehler` in `app_state` — er ueberlebt die
 *      Anfrage und laesst sich zaehlen.
 *   3. Der Hinweis auf der Statusseite — er faellt jemandem auf, der nicht
 *      sucht.
 *
 * ---------------------------------------------------------------------------
 * DIE SECHS REITER
 * ---------------------------------------------------------------------------
 *
 * `verwaltung` ist das Audit und der einzige mit einer langen, EINSTELLBAREN
 * Frist (365 Tage, 90 bis 1095). Die uebrigen fuenf verfallen nach 30 Tagen,
 * fest — dieselbe Zahl wie bei den Sperrereignissen und aus demselben Grund
 * (E-P5a-09): Betriebsdaten sollen nicht versehentlich Jahre liegen.
 *
 * LESBAR IST DAS PROTOKOLL SEIT P5c/AP2 — Verwaltung -> Protokoll
 * (`admin_protokoll.php`), SIEBEN Reiter. Der siebte, *Sicherheit*, liest
 * eine andere Tabelle (E-P5c-11), und drei weitere — *E-Mail*, *Jobs*,
 * *Ziele* — lesen die Tabellen, die ihre Sache ohnehin fuehren
 * (E-P5c-38): Keine doppelte Ablage, und der ENUM bleibt bei sechs Werten.
 * Wie die Reiter gefuellt werden, steht unten bei DIE QUELLEN.
 *
 * ---------------------------------------------------------------------------
 * DER REITER SYSTEM (P5c/AP3)
 * ---------------------------------------------------------------------------
 *
 * Bis Web 20.39.0 stand hier „Die `error_log()`-Aufrufe bleiben, wo sie
 * sind" — bis der Reiter System steht und jemand die Eintraege lesen kann.
 * Er steht seit AP2. Seit AP3 gehen die 75 Aufrufe ueber `system_melden()`
 * (`systemmeldung_lib.php`) hierher, mit Kennung und bereinigt; nur wenn die
 * Datenbank nicht antwortet, bleiben sie im Fehlerprotokoll des Webspace.
 */

require_once __DIR__ . '/db.php';

/* ---- Die Reiter ---------------------------------------------------------- */

/**
 * Die sechs Reiter, in die `protokoll()` SCHREIBT — die Werte des ENUM.
 *
 * `sicherheit` steht bewusst NICHT dabei — siehe Kopf. Was die Seite ZEIGT,
 * sind sieben: `PROTOKOLL_SEITE_REITER`.
 */
const PROTOKOLL_REITER = [
    'verwaltung' => 'Verwaltung',
    'email'      => 'E-Mail',
    'jobs'       => 'Jobs',
    'sicherung'  => 'Sicherung',
    'ziele'      => 'Ziele',
    'system'     => 'System',
];

/**
 * Die sieben Reiter der Seite, in der Reihenfolge der Anzeige (E-P5c-02,
 * Bild M-P5c-01a). Jedes Ereignis hat genau einen, kein „Sonstiges".
 */
const PROTOKOLL_SEITE_REITER = [
    'verwaltung' => 'Verwaltung',
    'sicherheit' => 'Sicherheit',
    'email'      => 'E-Mail',
    'jobs'       => 'Jobs',
    'sicherung'  => 'Sicherung',
    'ziele'      => 'Ziele',
    'system'     => 'System',
];

/** Schluessel in `app_state`. */
const PROTOKOLL_K_FRIST_VERWALTUNG = 'protokoll_frist_verwaltung';
const PROTOKOLL_K_FEHLER           = 'protokoll_fehler';

/** Frist des Reiters *Verwaltung* in Tagen — Vorgabe und Grenzen (E-P5b-06). */
const PROTOKOLL_FRIST_VORGABE = 365;
const PROTOKOLL_FRIST_MIN     = 90;
const PROTOKOLL_FRIST_MAX     = 1095;

/** Frist aller uebrigen Reiter in Tagen. FEST, keine Einstellung. */
const PROTOKOLL_FRIST_UEBRIGE = 30;

/**
 * Wie lange die Verwaltungseintraege bleiben, in Tagen.
 *
 * Ein Wert ausserhalb der Grenzen faellt auf die Vorgabe zurueck, statt zu
 * gelten: Eine Frist von drei Tagen, die jemand verschrieben hat, raeumte das
 * Audit weg, bevor es jemand liest.
 */
function protokoll_frist_verwaltung(): int
{
    $v = app_state_lesen(PROTOKOLL_K_FRIST_VERWALTUNG);
    if ($v === null || $v === '' || !ctype_digit($v)) { return PROTOKOLL_FRIST_VORGABE; }
    $n = (int)$v;
    return ($n >= PROTOKOLL_FRIST_MIN && $n <= PROTOKOLL_FRIST_MAX)
        ? $n : PROTOKOLL_FRIST_VORGABE;
}

/** Die Frist setzen. `false`, wenn sie ausserhalb der Grenzen liegt. */
function protokoll_frist_verwaltung_setzen(int $tage): bool
{
    if ($tage < PROTOKOLL_FRIST_MIN || $tage > PROTOKOLL_FRIST_MAX) { return false; }
    return app_state_setzen(PROTOKOLL_K_FRIST_VERWALTUNG, (string)$tage);
}

/* ---- Der Schreibweg ------------------------------------------------------ */

/**
 * Ein Betriebsereignis vermerken.
 *
 * @param string   $reiter    einer aus `PROTOKOLL_REITER`
 * @param string   $art       kurze Kennung, z. B. `konto_angelegt` — sie ist
 *                            das, wonach 10c filtert, und deshalb maschinell
 *                            und nicht menschlich formuliert
 * @param string   $text      der Satz, den ein Mensch liest
 * @param array    $daten     zusaetzliche Angaben, als JSON abgelegt
 * @param int|null $betroffen Konto, um das es geht (nicht: wer gehandelt hat)
 *
 * @return bool `true`, wenn geschrieben wurde. **Der Rueckgabewert ist ein
 *         Hinweis und kein Grund abzubrechen** — siehe Kopf.
 *
 * DER URHEBER KOMMT AUS DER SITZUNG und wird nicht uebergeben: Ein Aufrufer,
 * der ihn selbst setzen darf, kann ihn auch falsch setzen, und ein Audit, in
 * dem der Urheber ein Parameter ist, ist kein Audit. Laeuft kein Mensch
 * (Kommandozeile, Huckepack-Job), steht `0` — und `urheber_art` sagt, welche
 * der beiden Arten es war. Ein `NULL` waere hier die schlechtere Wahl: Es
 * liesse offen, ob niemand gehandelt hat oder ob jemand vergessen wurde.
 */
function protokoll(string $reiter, string $art, string $text,
                   array $daten = [], ?int $betroffen = null): bool
{
    if (!isset(PROTOKOLL_REITER[$reiter])) {
        /* Ein unbekannter Reiter ist ein Programmierfehler und kein
         * Betriebszustand — er landet trotzdem nicht in einer Ausnahme,
         * sondern unter `system`, damit die Handlung weiterlaeuft und der
         * Eintrag nicht verlorengeht. Die Meldung sagt, wo zu suchen ist. */
        system_melden('protokoll', 'unbekannter Reiter „' . $reiter . '" bei „' . $art
                    . '" — der Eintrag steht unter „system".');
        $reiter = 'system';
    }

    /* URHEBER. `php_sapi_name() === 'cli'` unterscheidet den Notausgang auf
     * der Kommandozeile vom Huckepack-Lauf in einer Anfrage — beide haben
     * keine Sitzung, aber es ist nicht dasselbe: Der eine ist eine
     * Betreiberin an der Konsole, der andere ist niemand. */
    $urheber = (int)($_SESSION['user_id'] ?? 0);
    $urheberArt = $urheber > 0 ? 'mensch'
                : (PHP_SAPI === 'cli' ? 'cli' : 'job');

    try {
        $st = db()->prepare(
            'INSERT INTO protokoll_ereignisse
                 (reiter, art, urheber_user_id, urheber_art, betroffen_user_id, text, daten)
             VALUES (?, ?, ?, ?, ?, ?, ?)');
        $st->execute([
            $reiter, $art, $urheber, $urheberArt, $betroffen,
            /* Die Spalte ist TEXT, aber ein Satz, der eine Bildschirmseite
             * fuellt, ist kein Protokolleintrag. Gekuerzt wird hier und nicht
             * beim Lesen — sonst traegt die Datenbank, was niemand je sieht. */
            mb_substr($text, 0, 500),
            $daten === [] ? null : json_encode($daten, JSON_UNESCAPED_UNICODE),
        ]);
        return true;
    } catch (Throwable $ex) {
        protokoll_fehler_vermerken($ex->getMessage());
        return false;
    }
}

/**
 * Einen Fehlschlag des Schreibens festhalten — Protokoll, Zaehler, Statuszeile.
 *
 * WARUM DER ZAEHLER NICHT IN EINER TRANSAKTION MIT DEM EINTRAG STEHT: Er soll
 * gerade dann noch gehen, wenn der Eintrag nicht geht. Faellt auch er aus
 * (die Datenbank ist ganz weg), bleibt `error_log()` — und das ist dann die
 * richtige und einzige Antwort.
 */
function protokoll_fehler_vermerken(string $grund): void
{
    error_log('protokoll: Eintrag nicht geschrieben — ' . $grund);
    try {
        $alt = (int)(app_state_lesen(PROTOKOLL_K_FEHLER) ?? '0');
        app_state_setzen(PROTOKOLL_K_FEHLER, (string)($alt + 1));
    } catch (Throwable $ex) {
        /* Dann geht auch `app_state` nicht mehr. `error_log()` steht oben. */
    }
}

/** Wie oft ein Eintrag nicht geschrieben werden konnte. 0 = alles in Ordnung. */
function protokoll_fehler_zahl(): int
{
    return (int)(app_state_lesen(PROTOKOLL_K_FEHLER) ?? '0');
}

/** Den Zaehler zuruecksetzen (Betrieb -> Status, nachdem jemand hingesehen hat). */
function protokoll_fehler_quittieren(): bool
{
    return app_state_setzen(PROTOKOLL_K_FEHLER, '0');
}

/**
 * Ein Gerät umgeschaltet oder entkoppelt (P5c/AP2, E-P5c-38) — vier Stellen,
 * ein Satz: die Kontoseite der Verwaltung und die Einstellungen der NutzerIn,
 * je Umschalten und Entkoppeln.
 *
 * BEIM ENTKOPPELN VOR DEM LÖSCHEN RUFEN — danach gibt es die Zeile nicht
 * mehr, und der Eintrag wüsste nicht, welches Gerät es war.
 *
 * @param string $art `geraet_umgeschaltet` | `geraet_geloescht`
 * @param string $weg `verwaltung` | `selbst`
 */
function protokoll_geraet(string $art, int $geraetId, int $userId, string $weg): void
{
    try {
        $st = db()->prepare('SELECT device_id, label, active FROM devices WHERE id = ? AND user_id = ?');
        $st->execute([$geraetId, $userId]);
        $g = $st->fetch(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $g = false;
    }
    if (!$g) { return; }   // fremdes oder schon fort: keine Handlung, kein Eintrag
    $name = trim((string)($g['label'] ?? '')) !== ''
        ? '„' . $g['label'] . '"' : (string)$g['device_id'];
    $text = $art === 'geraet_geloescht'
        ? 'Gerät ' . $name . ' entkoppelt'
        : 'Gerät ' . $name . ((int)$g['active'] === 1 ? ' aktiviert' : ' deaktiviert');
    protokoll('verwaltung', $art, $text . ($weg === 'verwaltung' ? ' (durch die Verwaltung)' : ''),
              ['geraet' => (string)$g['device_id'], 'weg' => $weg], $userId);
}

/* ---- Wer welchen Reiter sieht (E-P5c-02) ----------------------------------- */

/** Die Reiter, die jede verwaltende Rolle sieht — der Admin genau diese. Als
 *  Konstante, weil die Seite sie auch fuer die Meldung an den Support braucht
 *  („der Verwaltung vorbehalten" statt „der BetreiberIn", F-P5c-100). */
const PROTOKOLL_REITER_VERWALTUNG = ['verwaltung', 'email', 'jobs', 'sicherung'];

/**
 * Die Reiter, die die angemeldete Rolle sehen darf — in der Reihenfolge der
 * Anzeige.
 *
 * WAS DIE ROLLE NICHT DARF, ZEIGT DIE SEITE NICHT (E-P5c-10): kein
 * ausgegrauter Reiter, kein „dafür fehlt dir das Recht". Die BetreiberIn
 * sieht alle sieben; der Admin die vier, die ohne IP-Adressen,
 * Zieldaten und Systemmeldungen auskommen. Der Support (seit P5c/AP4,
 * E-P5c-14) Verwaltung und E-Mail — lesend; eine Handlung gibt es auf der
 * Seite fuer ihn nicht, das Archiv bleibt der BetreiberIn.
 *
 * @return list<string>
 */
function protokoll_reiter_sichtbar(): array
{
    if (function_exists('ist_betreiberin') && ist_betreiberin()) {
        return array_keys(PROTOKOLL_SEITE_REITER);
    }
    if (function_exists('ist_admin') && ist_admin()) {
        return PROTOKOLL_REITER_VERWALTUNG;
    }
    if (function_exists('ist_support') && ist_support()) {
        return ['verwaltung', 'email'];
    }
    return [];
}

/* ---- Der Katalog: Art -> Beschriftung und Ton (E-P5c-26) ------------------- */

/**
 * Jede Art als Wort und mit einem Ton — die Ampel aus `Design.md` 9.23:
 * neutral (ein Vorgang), blau (erledigt, gut), orange (ein Eingriff, eine
 * Sperre), rot (ein Fehler).
 *
 * DIE ART BLEIBT MASCHINELL, DIE BESCHRIFTUNG IST FUER MENSCHEN. Gefiltert
 * und gesucht wird nach dem Schluessel (`konto_status`), gezeigt wird das
 * Wort („Kontostatus"). Eine Art, die hier fehlt, erscheint als ihr
 * Schluessel mit Leerzeichen und neutral — sichtbar falsch, aber nicht
 * verloren.
 *
 * Der Ton haengt bei drei Arten am Inhalt (`protokoll_art_ton()`): Ein
 * Konto, das freigeschaltet wird, ist blau, eines, das gesperrt wird,
 * orange.
 */
const PROTOKOLL_ARTEN = [
    /* Verwaltung — seit P5b */
    'konto_angelegt'            => ['Konto angelegt', 'blau'],
    'konto_status'              => ['Kontostatus', 'neutral'],
    'konto_geloescht'           => ['Konto gelöscht', 'neutral'],
    'loeschung_beantragt'       => ['Löschung beantragt', 'orange'],
    'adresse_geaendert'         => ['Adresse geändert', 'neutral'],
    'konto_grenzen'             => ['Grenzen', 'neutral'],
    'einwilligung'              => ['Einwilligung', 'neutral'],
    'rechtstext_geaendert'      => ['Rechtstext geändert', 'neutral'],
    'schluessel_erneuert'       => ['Schlüssel erneuert', 'neutral'],
    'schluesselblatt_bestaetigt'=> ['Schlüsselblatt', 'blau'],
    'einstellungen_konten'      => ['Einstellungen', 'neutral'],
    'rundmail'                  => ['Rundmail', 'neutral'],
    /* Verwaltung — neu mit P5c/AP2 (E-P5c-38) */
    'rolle_geaendert'           => ['Rolle geändert', 'orange'],
    'setzlink_gesendet'         => ['Setz-Link', 'neutral'],
    'verifikation_gesendet'     => ['Bestätigung erneut', 'neutral'],
    'totp_eingerichtet'         => ['Zweitfaktor eingeschaltet', 'blau'],
    'totp_codes_erneuert'       => ['Codes erneuert', 'neutral'],
    'totp_ausgeschaltet'        => ['Zweitfaktor ausgeschaltet', 'orange'],
    'totp_zurueckgesetzt'       => ['Zweitfaktor zurückgesetzt', 'orange'],
    'totp_code_benutzt'         => ['Wiederherstellungscode', 'orange'],
    /* Verwaltung — neu mit Konzept RW (RW-02, E-RW-14) */
    'rueckweg_angelegt'         => ['Rückweg eingerichtet', 'neutral'],
    'rueckweg_erneuert'         => ['Rückweg erneuert', 'neutral'],
    'geraet_umgeschaltet'       => ['Gerät umgeschaltet', 'neutral'],
    'geraet_geloescht'          => ['Gerät gelöscht', 'neutral'],
    'wartung_an'                => ['Wartung an', 'orange'],
    'wartung_aus'               => ['Wartung aus', 'blau'],
    'demo_zurueckgesetzt'       => ['Demo zurückgesetzt', 'neutral'],
    'migration_ausgefuehrt'     => ['Migration', 'blau'],
    'archiv_heruntergeladen'    => ['Archiv heruntergeladen', 'orange'],
    'frist_geaendert'           => ['Fristen', 'neutral'],
    /* Sicherung — neu mit P5c/AP2 (E-P5c-38) */
    'komplett_erzeugt'          => ['Komplett-Backup erzeugt', 'blau'],
    'komplett_heruntergeladen'  => ['Komplett-Backup geladen', 'orange'],
    'komplett_eingespielt'      => ['Komplett-Backup eingespielt', 'orange'],
    'komplett_geloescht'        => ['Komplett-Backup gelöscht', 'neutral'],
    'kontobackup_eingespielt'   => ['Konto-Backup eingespielt', 'orange'],
    /* Sicherheit — Sicht auf `sicherheit_ereignisse` und `csp_berichte` */
    'sperre'                    => ['Sperre', 'orange'],
    'verlangsamung'             => ['Verlangsamung', 'orange'],
    'aufgehoben'                => ['Aufgehoben', 'blau'],
    'csp_bericht'               => ['CSP-Bericht', 'orange'],
    /* E-Mail — Sicht auf `mail_warteschlange` */
    'mail_offen'                => ['wartet', 'neutral'],
    'mail_zugestellt'           => ['zugestellt', 'blau'],
    'mail_unzustellbar'         => ['unzustellbar', 'rot'],
    'mail_zu_spaet'             => ['verfallen', 'orange'],
    'mail_ueberholt'            => ['ersetzt', 'neutral'],
    /* Jobs — Sicht auf `job_laeufe` */
    'job_lauf'                  => ['Lauf', 'neutral'],
    'job_fehler'                => ['Fehler', 'rot'],
    /* Ziele — Sicht auf `sicherungsziel_dateien` */
    'ziel_gesendet'             => ['gesendet', 'blau'],
    'ziel_geloescht'            => ['dort gelöscht', 'neutral'],
    /* System — `system_melden()` und die Behandler (P5c/AP3, SYSTEM_ARTEN) */
    'stoerung'                  => ['Störung', 'orange'],
    'ausnahme'                  => ['Unerwarteter Fehler', 'rot'],
    'abbruch'                   => ['Abbruch', 'rot'],
    'php_warnung'               => ['PHP-Warnung', 'orange'],
    'php_hinweis'               => ['PHP-Hinweis', 'neutral'],
];

/** Die Beschriftung einer Art. */
function protokoll_art_text(string $art): string
{
    return PROTOKOLL_ARTEN[$art][0] ?? str_replace('_', ' ', $art);
}

/** Der Ton einer Art — bei drei Arten nach dem Inhalt. */
function protokoll_art_ton(string $art, array $daten = []): string
{
    if ($art === 'konto_status') {
        $nach = (string)($daten['nach'] ?? '');
        return match ($nach) {
            'aktiv'    => 'blau',
            'gesperrt' => 'orange',
            default    => 'neutral',
        };
    }
    return PROTOKOLL_ARTEN[$art][1] ?? 'neutral';
}

/* ---- Die Quellen (E-P5c-11, -38) ------------------------------------------- *
 *
 * JE REITER EINE BIS DREI QUELLEN, NIE EIN UNION. Die naheliegende Loesung —
 * ein `UNION ALL` ueber `protokoll_ereignisse`, `mail_warteschlange` und die
 * uebrigen — stoesst auf eine Eigenschaft des Hosters, nicht des Codes: Die
 * Tabellen sind zu verschiedenen Zeiten entstanden, auf Produktiv unter
 * verschiedenen Vorgabe-Kollationen, und ein UNION ueber zwei Textspalten
 * verschiedener Kollation ist auf MySQL und MariaDB ein Fehler („Illegal mix
 * of collations"). Auf der oertlichen Anlage sind alle gleich; der Fehler
 * zeigte sich erst dort, wo niemand mehr hinsieht (Muster Nr. 238).
 *
 * Deshalb fragt jede Quelle fuer sich, mit denselben Spaltennamen, und PHP
 * fuegt zusammen: Fuer Seite `s` holt jede Quelle ihre juengsten `s × 50`
 * Zeilen, die Liste wird nach Zeit sortiert und zugeschnitten. Die Zahl
 * ist die Summe der Zaehlungen. Das kostet auf Seite 7 dreihundertfuenfzig
 * Zeilen je Quelle — bei Fristen von 30 Tagen (365 in der Verwaltung) ist das
 * nichts, und es geht ueberall.
 *
 * JEDE QUELLE LIEFERT: `zeit` (UTC), `art`, `uid` (Urheber, 0 = kein
 * Mensch), `uart` (mensch/job/cli oder leer), `bid` (Betroffener), `text`,
 * `daten` (JSON), `id`. Wo eine Tabelle keinen Satz fuehrt, baut
 * `protokoll_zeile_text()` ihn aus `art` und `daten` — in PHP, weil die
 * Beschriftungen hier stehen und nicht in SQL.
 */

/**
 * Die Quellen eines Reiters als SQL-Stuecke.
 *
 * @return list<array{sql:string, args:list<mixed>, suche:list<string>,
 *                    zeit:string, art:string, konto:bool}>
 *   `sql` ist ein vollstaendiges SELECT ohne WHERE-Teil fuer die Filter;
 *   Filter werden als `AND …` an `wo` angehaengt. `suche` sind die Spalten,
 *   gegen die ein Suchtext laeuft, `zeit` und `art` die Ausdruecke fuer
 *   Zeitraum und Art.
 */
function protokoll_quellen(string $reiter): array
{
    $pe = [
        'sql'   => 'SELECT p.id, p.zeit, p.art, p.urheber_user_id AS uid,
                           p.urheber_art AS uart, p.betroffen_user_id AS bid,
                           p.text, p.daten
                      FROM protokoll_ereignisse p
                 LEFT JOIN users u1 ON u1.id = p.urheber_user_id
                 LEFT JOIN users u2 ON u2.id = p.betroffen_user_id
                     WHERE p.reiter = ?',
        'args'  => [$reiter],
        'suche' => ['p.text', 'u1.email', 'u1.name', 'u2.email', 'u2.name'],
        'zeit'  => 'p.zeit', 'art' => 'p.art', 'konto' => true, 'idx' => 'p.id',
        'kennung' => "JSON_UNQUOTE(JSON_EXTRACT(p.daten, '$.kennung'))",
    ];

    switch ($reiter) {
        case 'verwaltung':
        case 'sicherung':
        case 'system':
            return [$pe];

        case 'sicherheit':
            /* KEINE `protokoll_ereignisse`: Der Reiter hat dort keinen
             * ENUM-Wert (Kopf). */
            return [[
                'sql'   => "SELECT id, zeitpunkt AS zeit, art, 0 AS uid, '' AS uart,
                                   NULL AS bid, '' AS text,
                                   JSON_OBJECT('topf', topf, 'merkmal', merkmal,
                                               'stufe', stufe, 'versuche', versuche,
                                               'bis', bis, 'wer', wer) AS daten
                              FROM sicherheit_ereignisse WHERE 1 = 1",
                'args'  => [], 'suche' => ['topf', 'merkmal', 'wer'],
                'zeit'  => 'zeitpunkt', 'art' => 'art', 'konto' => false, 'idx' => 'id',
            ], [
                'sql'   => "SELECT id, zuletzt AS zeit, 'csp_bericht' AS art, 0 AS uid,
                                   '' AS uart, NULL AS bid, '' AS text,
                                   JSON_OBJECT('richtlinie', richtlinie, 'quelle', quelle,
                                               'seite', seite, 'anzahl', anzahl,
                                               'erstellt', erstellt) AS daten
                              FROM csp_berichte WHERE 1 = 1",
                'args'  => [], 'suche' => ['richtlinie', 'quelle', 'seite'],
                'zeit'  => 'zuletzt', 'art' => "'csp_bericht'", 'konto' => false, 'idx' => 'id',
            ]];

        case 'email':
            /* NUR ART, ZUSTAND, ZEIT — nie Empfaenger, Betreff, Rumpf oder
             * Fehlertext (E-P5c-38). Eine offene Zeile traegt einen Setz-Link
             * mit gueltigem Token, und ein SMTP-Fehler nennt die Adresse, an
             * der er scheiterte. Beides gehoert nicht in eine Liste, die der
             * Admin sieht und die ins Archiv geht. */
            return [[
                'sql'   => "SELECT id, COALESCE(beendet, erstellt) AS zeit,
                                   CONCAT('mail_', zustand) AS art, 0 AS uid, '' AS uart,
                                   NULL AS bid, '' AS text,
                                   JSON_OBJECT('vorlage', schluessel, 'art', art,
                                               'versuche', versuche,
                                               'erstellt', erstellt) AS daten
                              FROM mail_warteschlange WHERE 1 = 1",
                'args'  => [], 'suche' => ['schluessel'],
                'zeit'  => 'COALESCE(beendet, erstellt)',
                'art'   => "CONCAT('mail_', zustand)", 'konto' => false, 'idx' => 'id',
            ], $pe];

        case 'jobs':
            return [[
                'sql'   => "SELECT id, zeitpunkt AS zeit,
                                   IF(fehler IS NULL OR fehler = '', 'job_lauf', 'job_fehler') AS art,
                                   0 AS uid, 'job' AS uart, NULL AS bid, '' AS text,
                                   JSON_OBJECT('job', job, 'ausloeser', ausloeser,
                                               'erledigt', erledigt,
                                               'fehler', LEFT(fehler, 300)) AS daten
                              FROM job_laeufe WHERE 1 = 1",
                'args'  => [], 'suche' => ['job', 'fehler'],
                'zeit'  => 'zeitpunkt',
                'art'   => "IF(fehler IS NULL OR fehler = '', 'job_lauf', 'job_fehler')",
                'konto' => false, 'idx' => 'id',
            ], $pe];

        case 'ziele':
            $ziel = "SELECT d.id, %s AS zeit, '%s' AS art, 0 AS uid, 'job' AS uart,
                            NULL AS bid, '' AS text,
                            JSON_OBJECT('ziel', t.name, 'ordner', d.ordner, 'datei', d.datei,
                                        'bytes', d.bytes, 'grund', d.grund) AS daten
                       FROM sicherungsziel_dateien d
                  LEFT JOIN backup_targets t ON t.id = d.ziel_id
                      WHERE %s";
            return [[
                'sql'   => sprintf($ziel, 'd.gesendet_am', 'ziel_gesendet', '1 = 1'),
                'args'  => [], 'suche' => ['d.datei', 'd.ordner', 't.name'],
                'zeit'  => 'd.gesendet_am', 'art' => "'ziel_gesendet'", 'konto' => false, 'idx' => 'd.id',
            ], [
                'sql'   => sprintf($ziel, 'd.geloescht_am', 'ziel_geloescht',
                                   'd.geloescht_am IS NOT NULL'),
                'args'  => [], 'suche' => ['d.datei', 'd.ordner', 't.name', 'd.grund'],
                'zeit'  => 'd.geloescht_am', 'art' => "'ziel_geloescht'", 'konto' => false, 'idx' => 'd.id',
            ], $pe];
    }
    return [];
}

/**
 * Die Arten, die ein Reiter kennt — fuer das Auswahlfeld „Art".
 *
 * Aus dem Katalog, nicht aus dem Bestand: Eine Art, die gerade keine Zeile
 * hat, ist trotzdem eine, nach der man fragen kann. Die Zuordnung steht
 * einmal, hier.
 *
 * @return array<string,string> Art => Beschriftung
 */
function protokoll_arten_des_reiters(string $reiter): array
{
    $je = [
        'verwaltung' => ['konto_angelegt', 'konto_status', 'konto_geloescht',
                         'loeschung_beantragt', 'adresse_geaendert', 'rolle_geaendert',
                         'konto_grenzen', 'setzlink_gesendet', 'verifikation_gesendet', 'einwilligung',
                         'totp_eingerichtet', 'totp_codes_erneuert', 'totp_ausgeschaltet',
                         'totp_zurueckgesetzt', 'totp_code_benutzt',
                         'rueckweg_angelegt', 'rueckweg_erneuert',
                         'rechtstext_geaendert', 'schluessel_erneuert',
                         'schluesselblatt_bestaetigt', 'geraet_umgeschaltet',
                         'geraet_geloescht', 'wartung_an', 'wartung_aus',
                         'demo_zurueckgesetzt', 'migration_ausgefuehrt',
                         'einstellungen_konten', 'frist_geaendert', 'rundmail',
                         'archiv_heruntergeladen'],
        'sicherheit' => ['sperre', 'verlangsamung', 'aufgehoben', 'csp_bericht'],
        'email'      => ['mail_offen', 'mail_zugestellt', 'mail_unzustellbar',
                         'mail_zu_spaet', 'mail_ueberholt'],
        'jobs'       => ['job_lauf', 'job_fehler'],
        'sicherung'  => ['komplett_erzeugt', 'komplett_heruntergeladen',
                         'komplett_eingespielt', 'komplett_geloescht',
                         'kontobackup_eingespielt'],
        'ziele'      => ['ziel_gesendet', 'ziel_geloescht'],
        'system'     => SYSTEM_ARTEN,
    ];
    $aus = [];
    foreach ($je[$reiter] ?? [] as $a) { $aus[$a] = protokoll_art_text($a); }
    return $aus;
}

/** Eine achtstellige Fehlerkennung (`fehler_kennung()`, AP3)? */
function protokoll_ist_kennung(string $q): bool
{
    return (bool)preg_match('/^[0-9a-f]{8}$/i', trim($q));
}

/**
 * Die Zeilen eines Reiters — gefiltert, sortiert, eine Seite.
 *
 * @param array{q?:string, tage?:int, art?:string, konto?:int} $filter
 * @return array{zeilen: list<array>, gesamt: int, fehler: ?string}
 *   Je Zeile: zeit, art, beschriftung, ton, text, wer, betrifft, daten
 *   (Liste von [Schluessel, Wert]).
 *
 * FEHLT EINE TABELLE (Migration steht aus), liefert die Quelle nichts und
 * `fehler` sagt, welche — die Seite zeigt dann eine Meldung statt einer
 * leeren Liste, die wie ein ruhiger Monat aussaehe.
 */
function protokoll_liste(string $reiter, array $filter, int $seite = 1, int $je = 50): array
{
    $seite = max(1, $seite);
    $bis = $seite * $je;
    $roh = []; $gesamt = 0; $fehler = null;

    foreach (protokoll_quellen($reiter) as $q) {
        [$wo, $args] = protokoll_filter_sql($q, $filter);
        if ($wo === null) { continue; }   // der Filter kann diese Quelle nicht treffen
        try {
            $st = db()->prepare('SELECT COUNT(*) FROM (' . $q['sql'] . $wo . ') n');
            $st->execute(array_merge($q['args'], $args));
            $gesamt += (int)$st->fetchColumn();

            $st = db()->prepare($q['sql'] . $wo
                . ' ORDER BY ' . $q['zeit'] . ' DESC LIMIT ' . $bis);
            $st->execute(array_merge($q['args'], $args));
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) { $roh[] = $z; }
        } catch (Throwable $ex) {
            $fehler = $ex->getMessage();
        }
    }

    usort($roh, static fn(array $a, array $b): int
        => [(string)$b['zeit'], (int)$b['id']] <=> [(string)$a['zeit'], (int)$a['id']]);
    $roh = array_slice($roh, ($seite - 1) * $je, $je);

    return ['zeilen' => protokoll_zeilen_aufbereiten($reiter, $roh),
            'gesamt' => $gesamt, 'fehler' => $fehler];
}

/**
 * Nur die Zahl — fuer die Zaehlkarte, ohne eine einzige Zeile zu lesen.
 */
function protokoll_zahl(string $reiter, array $filter = []): int
{
    $n = 0;
    foreach (protokoll_quellen($reiter) as $q) {
        [$wo, $args] = protokoll_filter_sql($q, $filter);
        if ($wo === null) { continue; }
        try {
            $st = db()->prepare('SELECT COUNT(*) FROM (' . $q['sql'] . $wo . ') n');
            $st->execute(array_merge($q['args'], $args));
            $n += (int)$st->fetchColumn();
        } catch (Throwable) {
            /* Tabelle fehlt (Migration steht aus) — die Zaehlkarte zeigt dann
             * null, und die Statusseite sagt an anderer Stelle, dass ein
             * Update aussteht. */
        }
    }
    return $n;
}

/**
 * Den WHERE-Zusatz einer Quelle bauen. `null`, wenn der Filter diese Quelle
 * gar nicht treffen kann (Konto-Filter auf eine Tabelle ohne Konten, eine
 * Art aus einer anderen Quelle) — dann wird sie nicht gefragt.
 *
 * @return array{0:?string, 1:list<mixed>}
 */
function protokoll_filter_sql(array $q, array $filter): array
{
    $wo = ''; $args = [];

    $tage = (int)($filter['tage'] ?? 0);
    if ($tage > 0) {
        $wo .= ' AND ' . $q['zeit'] . ' >= DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)';
        $args[] = $tage;
    }
    /* Ein fester Zeitraum [von, bis) und eine Fortsetzungsmarke — fuer das
     * Archiv, das in Haeppchen liest (E-P5c-57). */
    if (!empty($filter['von'])) { $wo .= ' AND ' . $q['zeit'] . ' >= ?'; $args[] = $filter['von']; }
    if (!empty($filter['bis'])) { $wo .= ' AND ' . $q['zeit'] . ' < ?';  $args[] = $filter['bis']; }
    if (!empty($filter['nach']) && is_array($filter['nach'])) {
        [$zeit, $id] = $filter['nach'];
        $wo .= ' AND (' . $q['zeit'] . ' > ? OR (' . $q['zeit'] . ' = ? AND ' . $q['idx'] . ' > ?))';
        array_push($args, $zeit, $zeit, (int)$id);
    }

    $art = (string)($filter['art'] ?? '');
    if ($art !== '') {
        $wo .= ' AND ' . $q['art'] . ' = ?';
        $args[] = $art;
    }

    $konto = (int)($filter['konto'] ?? 0);
    if ($konto > 0) {
        if (empty($q['konto'])) { return [null, []]; }
        $wo .= ' AND (p.urheber_user_id = ? OR p.betroffen_user_id = ?)';
        $args[] = $konto; $args[] = $konto;
    }

    $such = trim((string)($filter['q'] ?? ''));
    if ($such !== '') {
        /* EIN SUCHFELD (E-P5c-26): Text, Konto (E-Mail oder Name von
         * Urheber und Betroffenem), die Beschriftung einer Art — und, wo die
         * Quelle eine hat, die Fehlerkennung. Jede Spalte fuer sich mit
         * `LIKE ?`, nicht ueber `CONCAT_WS`: Das Verketten zweier Spalten
         * aus zwei Tabellen scheitert an derselben Kollationsfrage wie ein
         * UNION (siehe DIE QUELLEN). */
        $muster = '%' . strtr($such, ['\\' => '\\\\', '%' => '\\%', '_' => '\\_']) . '%';
        $oder = [];
        foreach ($q['suche'] as $spalte) { $oder[] = $spalte . ' LIKE ?'; $args[] = $muster; }
        $arten = [];
        foreach (PROTOKOLL_ARTEN as $a => [$text, $ton]) {
            if (mb_stripos($text, $such) !== false) { $arten[] = $a; }
        }
        if ($arten !== []) {
            $oder[] = $q['art'] . ' IN (' . implode(',', array_fill(0, count($arten), '?')) . ')';
            foreach ($arten as $a) { $args[] = $a; }
        }
        if (!empty($q['kennung']) && protokoll_ist_kennung($such)) {
            /* GROSS, wie `system_kennung()` sie vergibt. Die Kollation von
             * `JSON_UNQUOTE()` haengt an der Engine: gemessen 24.09.2026
             * `utf8mb4_bin` auf MariaDB 10.11, MySQL 8.0 und 8.4 — dort
             * traefe „a1b2c3d4" nicht „A1B2C3D4" —, `utf8mb3_general_ci`
             * nur auf MariaDB 10.6. Bis AP3 stand hier `strtolower`, und die
             * Suche fand auf Produktiv und Staging nichts (F-P5c-89). */
            $oder[] = $q['kennung'] . ' = ?';
            $args[] = strtoupper(trim($such));
        }
        $wo .= ' AND (' . implode(' OR ', $oder) . ')';
    }
    return [$wo, $args];
}

/**
 * Rohzeilen in die Form bringen, die die Seite zeigt und das Archiv
 * schreibt: Wort statt Schluessel, Satz statt Spalten, Konten als Adressen.
 */
function protokoll_zeilen_aufbereiten(string $reiter, array $roh): array
{
    /* Die Konten EINMAL lesen, nicht je Zeile. */
    $ids = [];
    foreach ($roh as $z) {
        foreach (['uid', 'bid'] as $k) {
            if ((int)($z[$k] ?? 0) > 0) { $ids[(int)$z[$k]] = true; }
        }
    }
    $konten = [];
    if ($ids !== []) {
        try {
            $st = db()->prepare('SELECT id, email, name FROM users WHERE id IN ('
                . implode(',', array_fill(0, count($ids), '?')) . ')');
            $st->execute(array_keys($ids));
            foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $u) { $konten[(int)$u['id']] = $u; }
        } catch (Throwable) { /* dann eben mit Kennnummer */ }
    }
    $konto = static function (int $id) use ($konten): string {
        if (!isset($konten[$id])) { return 'Konto ' . $id . ' (gelöscht)'; }
        $u = $konten[$id];
        return trim((string)$u['name']) !== ''
            ? $u['name'] . ' (' . $u['email'] . ')' : (string)$u['email'];
    };

    $aus = [];
    foreach ($roh as $z) {
        $daten = [];
        if (($z['daten'] ?? null) !== null && $z['daten'] !== '') {
            $d = json_decode((string)$z['daten'], true);
            if (is_array($d)) { $daten = $d; }
        }
        $art = (string)$z['art'];
        $uid = (int)($z['uid'] ?? 0);
        $wer = match (true) {
            $uid > 0                    => 'von ' . $konto($uid),
            ($z['uart'] ?? '') === 'cli' => 'Kommandozeile',
            ($z['uart'] ?? '') === 'job' => 'job',
            default                     => '',
        };
        $bid = (int)($z['bid'] ?? 0);
        $aus[] = [
            'zeit'         => (string)$z['zeit'],
            'art'          => $art,
            'beschriftung' => protokoll_art_text($art),
            'ton'          => protokoll_art_ton($art, $daten),
            'text'         => (string)$z['text'] !== ''
                                ? (string)$z['text'] : protokoll_zeile_text($art, $daten),
            'wer'          => $wer,
            'betrifft'     => $bid > 0 && $bid !== $uid ? $konto($bid) : '',
            'daten'        => protokoll_daten_zeilen($daten),
        ];
    }
    return $aus;
}

/**
 * Der Satz einer Zeile aus einer Sicht — dort gibt es keine Spalte `text`.
 *
 * Kurz und ohne Wertung; die Plakette traegt die Art und ihren Ton, der Satz
 * sagt, worum es ging.
 */
function protokoll_zeile_text(string $art, array $d): string
{
    $v = static fn(string $k): string => trim((string)($d[$k] ?? ''));
    switch ($art) {
        case 'sperre':
        case 'verlangsamung':
            return ($art === 'sperre' ? 'Gesperrt' : 'Verlangsamt')
                . ': Topf „' . $v('topf') . '", Stufe ' . (int)($d['stufe'] ?? 0)
                . ($v('versuche') !== '' ? ', ' . $v('versuche') . ' Versuche' : '');
        case 'aufgehoben':
            return 'Sperre aufgehoben: Topf „' . $v('topf') . '"'
                . ($v('wer') !== '' ? ' — von ' . $v('wer') : '');
        case 'csp_bericht':
            return 'Inhaltsrichtlinie: ' . $v('richtlinie') . ' blockierte „' . $v('quelle')
                . '" auf ' . $v('seite') . ' (' . (int)($d['anzahl'] ?? 1) . '×)';
        case 'mail_offen':
        case 'mail_zugestellt':
        case 'mail_unzustellbar':
        case 'mail_zu_spaet':
        case 'mail_ueberholt':
            $n = (int)($d['versuche'] ?? 0);
            $versuche = $n . ($n === 1 ? ' Versuch' : ' Versuche');
            if ($art === 'mail_offen') {
                return 'Nachricht „' . $v('vorlage') . '" wartet'
                    . ($n > 0 ? ' — ' . $versuche . ' bisher' : '');
            }
            return 'Nachricht „' . $v('vorlage') . '" — ' . protokoll_art_text($art)
                . ($n > 0 ? ' nach ' . $n . ($n === 1 ? ' Versuch' : ' Versuchen') : '');
        case 'job_lauf':
            return protokoll_job_titel($v('job')) . ': ' . (int)($d['erledigt'] ?? 0) . ' erledigt';
        case 'job_fehler':
            return protokoll_job_titel($v('job')) . ': Fehler';
        case 'ziel_gesendet':
            return $v('ordner') . '/' . $v('datei') . ' an „' . $v('ziel') . '" gesendet';
        case 'ziel_geloescht':
            return $v('ordner') . '/' . $v('datei') . ' auf „' . $v('ziel') . '" gelöscht'
                . ($v('grund') !== '' ? ' — ' . $v('grund') : '');
    }
    return protokoll_art_text($art);
}

/** Der Titel eines Jobs aus dem Katalog — ohne den Katalog bei jeder Zeile zu bauen. */
function protokoll_job_titel(string $job): string
{
    static $titel = null;
    if ($titel === null) {
        $titel = [];
        try {
            require_once __DIR__ . '/jobs_lib.php';
            foreach (jobs_katalog() as $k => $j) { $titel[$k] = (string)$j['titel']; }
        } catch (Throwable) { /* dann der Schluessel */ }
    }
    return $titel[$job] ?? $job;
}

/**
 * `daten` als Liste zum Aufklappen: [Schluessel, Wert], Werte als Text.
 * `null` wird zu „—", Listen und Objekte zu JSON. Leere Liste = nichts
 * aufzuklappen, die Zeile ist dann kein `<details>`.
 *
 * @return list<array{0:string, 1:string}>
 */
function protokoll_daten_zeilen(array $daten): array
{
    $aus = [];
    foreach ($daten as $k => $w) {
        $aus[] = [(string)$k, match (true) {
            $w === null   => '—',
            is_bool($w)   => $w ? 'ja' : 'nein',
            is_scalar($w) => (string)$w,
            default       => (string)json_encode($w, JSON_UNESCAPED_UNICODE),
        }];
    }
    return $aus;
}

/**
 * Eintraege je Reiter der letzten 24 Stunden und gesamt — fuer die
 * Zaehlkarte auf Betrieb -> Status.
 *
 * SEIT P5c/AP2 UEBER DIE QUELLEN DER SEITE, nicht nur ueber
 * `protokoll_ereignisse`. Bis dahin standen E-Mail, Jobs und Ziele dort auf
 * null, obwohl ihre Tabellen voll waren — der Schreibweg kennt sie, geschrieben
 * wird aber woanders (E-P5c-38).
 *
 * @return array{tag: array<string,int>, gesamt: array<string,int>, alle: int}
 */
function protokoll_zaehlkarte(): array
{
    $tag = []; $gesamt = []; $alle = 0;
    foreach (array_keys(PROTOKOLL_SEITE_REITER) as $r) {
        $tag[$r]    = protokoll_zahl($r, ['tage' => 1]);
        $gesamt[$r] = protokoll_zahl($r);
        $alle      += $gesamt[$r];
    }
    return ['tag' => $tag, 'gesamt' => $gesamt, 'alle' => $alle];
}

/**
 * Die Bereinigung — zwei Fristen, ein Lauf (E-P5b-06).
 *
 * Wird aus `job_aufraeumen()` gerufen. Eigene Funktion, damit die Fristen an
 * EINER Stelle stehen und der Job sie nicht noch einmal auslegt.
 *
 * @return int Zahl der geloeschten Zeilen.
 */
function protokoll_bereinigen(PDO $pdo): int
{
    $weg = 0;

    /* Verwaltung: die lange, einstellbare Frist. */
    $st = $pdo->prepare(
        'DELETE FROM protokoll_ereignisse
          WHERE reiter = "verwaltung"
            AND zeit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)');
    $st->execute([protokoll_frist_verwaltung()]);
    $weg += $st->rowCount();

    /* Alle uebrigen: 30 Tage, fest. `!=` statt einer Aufzaehlung der fuenf —
     * so faellt ein Reiter, den 10c ergaenzt, von selbst unter die kurze
     * Frist, statt unbegrenzt zu liegen. Das ist die sichere Richtung. */
    $st = $pdo->prepare(
        'DELETE FROM protokoll_ereignisse
          WHERE reiter != "verwaltung"
            AND zeit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)');
    $st->execute([PROTOKOLL_FRIST_UEBRIGE]);
    $weg += $st->rowCount();

    return $weg;
}
