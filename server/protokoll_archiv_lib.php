<?php
declare(strict_types=1);

/**
 * DAS ARCHIV DES PROTOKOLLS — versiegelt, wöchentlich, ein Jahr
 * ===========================================================================
 *
 *     protokoll_archiv_job($pdo, $zustand, $zeitLinks)   // Jobkatalog
 *     protokoll_archive()                                // was liegt da?
 *     protokoll_archiv_ausliefern($name)                 // Download, entsiegelt
 *
 * WOFUER (P5c/AP2, E-P5c-03, -39, -57). Die Datenbank hält die Einträge nur
 * so lange wie ihre Frist — 30 Tage, in der Verwaltung 365. Das Archiv hält
 * sie länger und außer Haus: Alle sieben Tage (einstellbar) schreibt der Job
 * die Einträge des Zeitraums als ZIP nach `sicherungen/protokoll/`, der
 * Versandjob schickt es auf das Sicherungsziel, und nach 365 Tagen
 * (einstellbar) löscht der Job es hier. Auf dem Ziel gibt es keine eigene
 * Löschregel (E-P5c-39) — rund 52 kleine Dateien im Jahr.
 *
 * WAS HINEINDARF (E-P5c-39). Das Archiv liegt ein Jahr und geht außer Haus,
 * `sicherheit_ereignisse` dagegen führt IP- und E-Mail-Adressen und wird
 * bewusst nach 30 Tagen gelöscht (E-P5a-09). Deshalb:
 *   - Sicherheit: nur Art, Topf, Stufe, Zeit — `merkmal` und `wer` NICHT;
 *     aus den CSP-Berichten Richtlinie, Quelle, Seite, Anzahl (keine
 *     Personenangaben).
 *   - E-Mail: nur Vorlage, Zustand, Zeit.
 *   - Alle übrigen Reiter: wie gespeichert — samt der Adressen im Text
 *     (E-P5c-75, Q-P5c-31: Das Audit soll nach einer Kontolöschung noch
 *     sagen, wer es war; die Datenbank hält dieselben Texte ohnehin 365 Tage).
 *
 * WIE ES GEBAUT IST (E-P5c-57, F-P5c-19).
 *   - `sk_versiegeln()` liefert eine Zeichenkette, und AES-GCM verlangt den
 *     Klartext am Stück. Ein Guss über ein Jahr Sicherheitsereignisse
 *     sprengte die 64 MB. Deshalb Teile zu höchstens 1 MB Klartext, jeder
 *     für sich versiegelt, im ZIP ungepackt nebeneinander.
 *   - Die KENNUNG DES SERVERSCHLÜSSELS steht im Dateinamen und im Zweck jedes
 *     Siegels, zusammen mit dem Namen selbst: Ein umbenanntes Archiv lässt
 *     sich nicht mehr öffnen, und eines von einem anderen Schlüssel erkennt
 *     die Seite am Namen, ohne es zu öffnen.
 *   - IN HÄPPCHEN mit Fortsetzungsmarke: Produktiv hat keinen Cron, huckepack
 *     gibt es 3 s. Der Job liest je Quelle 500 Zeilen, schreibt sie in einen
 *     Bauordner und merkt sich, wo er stand.
 *   - HÖCHSTGRÖSSE 32 MB Klartext je Archiv. Was darüber liegt — praktisch
 *     nur ein Angriff, der Zehntausende Sperren schreibt —, wird nicht
 *     archiviert, und das Manifest sagt es: `gekuerzt` je Reiter.
 *
 * DAS ERSTE ARCHIV BEGINNT BEIM ÄLTESTEN EINTRAG (E-P5c-76, Q-P5c-32), auf
 * den Tag abgerundet. Der Job holt dann Zeitraum für Zeitraum nach, bis er
 * die Gegenwart erreicht. Keine Lücke: Was die Datenbank später nach Frist
 * löscht, liegt dann im Archiv.
 *
 * DER ORT BLEIBT `sicherungen/protokoll/` (E-P5c-57): Schutzliste,
 * `.gitignore` und Integritätswache decken `sicherungen/` schon, und
 * `edbak_kennung_gueltig()` hält den Namen aus der Liste der verwaisten
 * Kontoordner heraus. Ein anderer Ort hieße drei Stellen und eine neue
 * Prüfzahl in `CLAUDE.md` 3.
 */

require_once __DIR__ . '/protokoll_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';    // edbak_wurzel()
require_once __DIR__ . '/serverkrypto_lib.php';   // sk_versiegeln(), serverschluessel_kennung()
require_once __DIR__ . '/zip_lib.php';
require_once __DIR__ . '/format_lib.php';         // iso_utc()

/** Der Ordner unter `sicherungen/` — auch der Ordnername auf dem Ziel. */
const PROTOKOLL_ARCHIV_ORDNER = 'protokoll';

/** Einstellungen in `app_state` (Karte „Protokoll" in den Servereinstellungen). */
const PROTOKOLL_K_ARCHIV_TAGE      = 'protokoll_archiv_tage';
const PROTOKOLL_K_ARCHIV_BEHALTEN  = 'protokoll_archiv_behalten';
const PROTOKOLL_K_ARCHIV_VERSAND   = 'protokoll_archiv_versand';
/** Das Ende des zuletzt archivierten Zeitraums (UTC) — die Marke des Jobs. */
const PROTOKOLL_K_ARCHIV_BIS       = 'protokoll_archiv_bis';

const PROTOKOLL_ARCHIV_TAGE_VORGABE = 7;
const PROTOKOLL_ARCHIV_TAGE_MIN     = 1;
const PROTOKOLL_ARCHIV_TAGE_MAX     = 31;
const PROTOKOLL_ARCHIV_BEHALTEN_VORGABE = 365;
const PROTOKOLL_ARCHIV_BEHALTEN_MIN     = 90;
const PROTOKOLL_ARCHIV_BEHALTEN_MAX     = 1095;

/** Klartext je versiegeltem Teil und je Archiv. */
const PROTOKOLL_ARCHIV_TEIL = 1048576;          // 1 MB
const PROTOKOLL_ARCHIV_MAX  = 33554432;         // 32 MB
/** Zeilen je Abfrage im Häppchen. */
const PROTOKOLL_ARCHIV_SCHUB = 500;

/* ---- Einstellungen --------------------------------------------------------- */

/** Eine Zahl aus `app_state` innerhalb ihrer Grenzen, sonst die Vorgabe. */
function protokoll_archiv_zahl(string $k, int $vorgabe, int $min, int $max): int
{
    $v = app_state_lesen($k);
    if ($v === null || !ctype_digit($v)) { return $vorgabe; }
    $n = (int)$v;
    return ($n >= $min && $n <= $max) ? $n : $vorgabe;
}

/** Alle wie viele Tage ein Archiv entsteht. */
function protokoll_archiv_tage(): int
{
    return protokoll_archiv_zahl(PROTOKOLL_K_ARCHIV_TAGE, PROTOKOLL_ARCHIV_TAGE_VORGABE,
        PROTOKOLL_ARCHIV_TAGE_MIN, PROTOKOLL_ARCHIV_TAGE_MAX);
}

/** Wie lange ein Archiv hier liegt, in Tagen. */
function protokoll_archiv_behalten(): int
{
    return protokoll_archiv_zahl(PROTOKOLL_K_ARCHIV_BEHALTEN, PROTOKOLL_ARCHIV_BEHALTEN_VORGABE,
        PROTOKOLL_ARCHIV_BEHALTEN_MIN, PROTOKOLL_ARCHIV_BEHALTEN_MAX);
}

/** Geht das Archiv mit dem Versandjob auf das Sicherungsziel? Vorgabe: ja. */
function protokoll_archiv_versand(): bool
{
    return app_state_lesen(PROTOKOLL_K_ARCHIV_VERSAND) !== '0';
}

/* ---- Zeiträume: Kalendertage der Anlage ---------------------------------- */

/**
 * DIE GRENZEN SIND MITTERNACHT IN DER ZEITZONE DER ANLAGE, nicht in UTC.
 *
 * Der erste Bau rechnete in UTC, und die Seite zeigte einen Tageszeitraum
 * als „23.09. – 24.09.2026": 00:00 UTC ist 02:00 Uhr in Berlin, und
 * 23:59:59 UTC gehört schon zum nächsten Tag. Wer ein Archiv sucht, sucht
 * „die Woche vom 13. bis zum 19." — und bekäme eines, das zwei Stunden in
 * den 20. reicht.
 *
 * ÜBER DEN KALENDER, NICHT ÜBER SEKUNDEN: `+7 days` in der Ortszeit bleibt
 * über eine Sommerzeitumstellung Mitternacht; `+ 7 × 86 400` s landete dort
 * um 23:00 oder 01:00.
 *
 * Gespeichert wird weiter in UTC — wie jede Zeit in dieser Datenbank.
 */
function protokoll_archiv_zone(): DateTimeZone
{
    return new DateTimeZone((string)konfig('app.timezone', 'Europe/Berlin'));
}

/** Ortsmitternacht des Tages, in den `$utc` fällt — als UTC. */
function protokoll_archiv_tag_beginn(string $utc): string
{
    $ort = (new DateTimeImmutable($utc, new DateTimeZone('UTC')))
        ->setTimezone(protokoll_archiv_zone())->setTime(0, 0);
    return $ort->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

/** Das Ende eines Zeitraums: `$tage` Kalendertage nach `$vonUtc`, als UTC. */
function protokoll_archiv_ende(string $vonUtc, int $tage): string
{
    return (new DateTimeImmutable($vonUtc, new DateTimeZone('UTC')))
        ->setTimezone(protokoll_archiv_zone())->modify('+' . $tage . ' days')
        ->setTimezone(new DateTimeZone('UTC'))->format('Y-m-d H:i:s');
}

/* ---- Namen und Ablage ------------------------------------------------------ */

function protokoll_archiv_wurzel(): string
{
    return edbak_wurzel() . '/' . PROTOKOLL_ARCHIV_ORDNER;
}

/**
 * `<Beginn als ISO>_<Kennung des Serverschlüssels>.zip` — dasselbe
 * Zeitstempelmuster wie Kontopakete und Komplett-Stände, damit
 * `sz_zeit_aus_dateiname()` es liest und der Name zeitlich sortiert.
 */
function protokoll_archiv_name(string $vonUtc, string $kennung): string
{
    return gmdate('Y-m-d\TH-i-s\Z', (int)strtotime($vonUtc . ' UTC')) . '_' . $kennung . '.zip';
}

function protokoll_archiv_name_gueltig(string $name): bool
{
    return (bool)preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z_[0-9a-f]{8}\.zip$/', $name);
}

/** Die Kennung aus dem Namen. */
function protokoll_archiv_kennung(string $name): ?string
{
    return preg_match('/_([0-9a-f]{8})\.zip$/', $name, $t) ? $t[1] : null;
}

/** Der Zweck eines Siegels: Name des Archivs und Name des Teils. */
function protokoll_archiv_zweck(string $name, string $teil): string
{
    return 'protokollarchiv|' . $name . '|' . $teil;
}

/**
 * Was hier liegt, neueste zuerst.
 *
 * @return list<array{datei:string, bytes:int, von:?string, kennung:?string,
 *                    passt:bool, manifest:?array}>
 *   `manifest` nur, wenn es sich mit dem heutigen Schlüssel öffnen lässt.
 */
function protokoll_archive(bool $mitManifest = false): array
{
    $pfad = protokoll_archiv_wurzel();
    if (!is_dir($pfad)) { return []; }
    $jetzt = serverschluessel_kennung();
    $aus = [];
    foreach (scandir($pfad) ?: [] as $n) {
        if (!protokoll_archiv_name_gueltig($n) || !is_file($pfad . '/' . $n)) { continue; }
        $k = protokoll_archiv_kennung($n);
        $e = ['datei' => $n, 'bytes' => (int)@filesize($pfad . '/' . $n),
              'von' => protokoll_archiv_von($n), 'kennung' => $k,
              'passt' => $jetzt !== null && $k === $jetzt, 'manifest' => null];
        if ($mitManifest && $e['passt']) { $e['manifest'] = protokoll_archiv_manifest($n); }
        $aus[] = $e;
    }
    usort($aus, static fn(array $a, array $b): int => strcmp($b['datei'], $a['datei']));
    return $aus;
}

/** Der Beginn des Zeitraums aus dem Namen, als UTC-Zeichenkette. */
function protokoll_archiv_von(string $name): ?string
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2})-(\d{2})-(\d{2})Z_/', $name, $t)) { return null; }
    return $t[1] . ' ' . $t[2] . ':' . $t[3] . ':' . $t[4];
}

/** Das Manifest eines Archivs, entsiegelt — oder null. */
function protokoll_archiv_manifest(string $name): ?array
{
    if (!protokoll_archiv_name_gueltig($name)) { return null; }
    $roh = zip_eintrag(protokoll_archiv_wurzel() . '/' . $name, 'manifest.json.sk');
    if ($roh === null) { return null; }
    $klar = sk_oeffnen($roh, protokoll_archiv_zweck($name, 'manifest.json'));
    $m = $klar === null ? null : json_decode($klar, true);
    return is_array($m) ? $m : null;
}

/* ---- Was eine Zeile im Archiv ist (E-P5c-39) ------------------------------- */

/**
 * Eine Rohzeile einer Quelle als Archivzeile. Hier — und nur hier — steht,
 * was aus welchem Reiter hinausgeht.
 */
function protokoll_archiv_zeile(string $reiter, array $z): array
{
    $daten = [];
    if (($z['daten'] ?? null) !== null && $z['daten'] !== '') {
        $d = json_decode((string)$z['daten'], true);
        if (is_array($d)) { $daten = $d; }
    }
    $art = (string)$z['art'];
    switch ($reiter) {
        case 'sicherheit':
            if ($art === 'csp_bericht') {
                return ['zeit' => (string)$z['zeit'], 'art' => $art,
                        'richtlinie' => $daten['richtlinie'] ?? null,
                        'quelle' => $daten['quelle'] ?? null,
                        'seite' => $daten['seite'] ?? null,
                        'anzahl' => $daten['anzahl'] ?? null];
            }
            /* `merkmal` (IP oder Adresse) und `wer` bleiben draußen — die
             * 30-Tage-Zusage aus E-P5a-09 hält auch im Archiv. */
            return ['zeit' => (string)$z['zeit'], 'art' => $art,
                    'topf' => $daten['topf'] ?? null, 'stufe' => $daten['stufe'] ?? null];
        case 'email':
            if (str_starts_with($art, 'mail_')) {
                return ['zeit' => (string)$z['zeit'], 'art' => $art,
                        'vorlage' => $daten['vorlage'] ?? null];
            }
            break;
    }
    return ['zeit' => (string)$z['zeit'], 'art' => $art,
            'urheber' => (int)($z['uid'] ?? 0), 'urheber_art' => (string)($z['uart'] ?? ''),
            'betroffen' => $z['bid'] !== null ? (int)$z['bid'] : null,
            'text' => (string)($z['text'] ?? ''), 'daten' => $daten ?: null];
}

/* ---- Der Job ----------------------------------------------------------------- */

/**
 * Der Beginn des nächsten Zeitraums: die Marke, oder — beim ersten Lauf —
 * der Tag des ältesten Eintrags (E-P5c-76). `null`: es gibt nichts.
 */
function protokoll_archiv_naechster_beginn(): ?string
{
    $marke = app_state_lesen(PROTOKOLL_K_ARCHIV_BIS);
    if ($marke !== null && $marke !== '') { return $marke; }
    $aeltester = null;
    foreach (array_keys(PROTOKOLL_SEITE_REITER) as $r) {
        foreach (protokoll_quellen($r) as $q) {
            try {
                /* Die Spalte heisst in der abgeleiteten Tabelle `zeit`. */
                $st = db()->prepare('SELECT MIN(t0.zeit) FROM (' . $q['sql'] . ') t0');
                $st->execute($q['args']);
                $m = $st->fetchColumn();
                if ($m !== null && $m !== false && ($aeltester === null || $m < $aeltester)) {
                    $aeltester = (string)$m;
                }
            } catch (Throwable) { /* Tabelle fehlt — die Quelle zählt nicht */ }
        }
    }
    return $aeltester === null ? null : protokoll_archiv_tag_beginn($aeltester);
}

/** Wie viele Zeiträume ausstehen — der Rückstand für die Jobseite. */
function protokoll_archiv_rueckstand(PDO $pdo, array $zustand): ?int
{
    if (!serverschluessel_da()) { return null; }
    $von = $zustand['von'] ?? protokoll_archiv_naechster_beginn();
    if ($von === null) { return 0; }
    $tage = protokoll_archiv_tage();
    $jetzt = gmdate('Y-m-d H:i:s');
    $n = 0;
    while (($bis = protokoll_archiv_ende($von, $tage)) <= $jetzt && $n < 1000) { $n++; $von = $bis; }
    return $n;
}

/**
 * Ein Häppchen Archivarbeit. Form wie jeder Job im Katalog.
 *
 * DER ZUSTAND: `von`, `bis` (der laufende Zeitraum), `r` (Reiter), `q`
 * (Quelle), `nach` ([zeit, id] — die Fortsetzungsmarke), `teile` (je Reiter
 * die Zahl der Teile), `zeilen` (je Reiter), `bytes`, `gekuerzt` (je Reiter).
 * Ohne Zustand beginnt ein neuer Zeitraum, wenn einer fällig ist.
 *
 * OHNE SERVERSCHLÜSSEL GIBT ES KEIN ARCHIV. Der Job ist dann fertig und
 * meldet nichts: Die Statusseite sagt schon rot, dass der Schlüssel fehlt,
 * und ein Archiv im Klartext wäre genau das, was das Siegel verhindern soll.
 */
function protokoll_archiv_job(PDO $pdo, array $zustand, callable $zeitLinks): array
{
    $erledigt = 0;
    $kennung = serverschluessel_kennung();
    if ($kennung === null) { return ['zustand' => [], 'erledigt' => 0, 'fertig' => true]; }

    $erledigt += protokoll_archiv_aufraeumen();

    while ($zeitLinks() > 0.5) {
        if (!isset($zustand['von'])) {
            $von = protokoll_archiv_naechster_beginn();
            if ($von === null) { return ['zustand' => [], 'erledigt' => $erledigt, 'fertig' => true]; }
            $bis = protokoll_archiv_ende($von, protokoll_archiv_tage());
            if ($bis > gmdate('Y-m-d H:i:s')) {
                return ['zustand' => [], 'erledigt' => $erledigt, 'fertig' => true];
            }
            $bau = protokoll_archiv_wurzel() . '/.bau';
            protokoll_archiv_bau_leeren($bau);
            if (!is_dir($bau) && !@mkdir($bau, 0770, true) && !is_dir($bau)) {
                throw new RuntimeException('Der Bauordner des Archivs lässt sich nicht anlegen: ' . $bau);
            }
            $zustand = ['von' => $von, 'bis' => $bis, 'kennung' => $kennung,
                        'r' => 0, 'q' => 0, 'nach' => null,
                        'teile' => [], 'zeilen' => [], 'bytes' => 0, 'gekuerzt' => []];
        }
        /* Ein Schlüsselwechsel mitten im Zeitraum: neu anfangen. Sonst trüge
         * das Archiv den neuen Namen und Teile unter dem alten Siegel. */
        if (($zustand['kennung'] ?? '') !== $kennung) {
            protokoll_archiv_bau_leeren(protokoll_archiv_wurzel() . '/.bau');
            $zustand = [];
            continue;
        }

        $reiter = array_keys(PROTOKOLL_SEITE_REITER);
        if ($zustand['r'] < count($reiter)) {
            $erledigt += protokoll_archiv_sammeln($zustand, $reiter[$zustand['r']], $zeitLinks);
            continue;
        }
        /* Alles gesammelt: versiegeln, packen, ablegen, Marke setzen. */
        protokoll_archiv_abschliessen($zustand);
        app_state_setzen(PROTOKOLL_K_ARCHIV_BIS, (string)$zustand['bis']);
        $erledigt++;
        $zustand = [];
    }
    /* FERTIG HEISST: KEIN ZEITRAUM OFFEN UND KEINER FÄLLIG. Nur „kein
     * Zustand" genügte nicht — nach einem abgeschlossenen Zeitraum ist der
     * Zustand leer, der nächste aber womöglich schon fällig, und der Job
     * meldete „fertig", während er beim Nachholen erst ein Drittel geschafft
     * hatte (gemessen: 1 von 3 Archiven, Protokollprobe). */
    return ['zustand' => $zustand, 'erledigt' => $erledigt,
            'fertig' => $zustand === [] && !protokoll_archiv_faellig()];
}

/** Ist ein Zeitraum abgelaufen, der noch nicht archiviert ist? */
function protokoll_archiv_faellig(): bool
{
    $von = protokoll_archiv_naechster_beginn();
    if ($von === null) { return false; }
    return protokoll_archiv_ende($von, protokoll_archiv_tage()) <= gmdate('Y-m-d H:i:s');
}

/** Einen Schub Zeilen einer Quelle in den Bauordner schreiben. */
function protokoll_archiv_sammeln(array &$z, string $reiter, callable $zeitLinks): int
{
    $quellen = protokoll_quellen($reiter);
    if ($z['q'] >= count($quellen)) {
        $z['r']++; $z['q'] = 0; $z['nach'] = null;
        return 0;
    }
    $q = $quellen[$z['q']];
    $filter = ['von' => $z['von'], 'bis' => $z['bis']];
    if ($z['nach'] !== null) { $filter['nach'] = $z['nach']; }
    [$wo, $args] = protokoll_filter_sql($q, $filter);
    try {
        $st = db()->prepare($q['sql'] . $wo . ' ORDER BY ' . $q['zeit'] . ', ' . $q['idx']
                            . ' LIMIT ' . PROTOKOLL_ARCHIV_SCHUB);
        $st->execute(array_merge($q['args'], $args));
        $zeilen = $st->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable) {
        $zeilen = [];   // Tabelle fehlt — diese Quelle trägt nichts bei
    }
    if ($zeilen === []) {
        $z['q']++; $z['nach'] = null;
        return 0;
    }

    $bau = protokoll_archiv_wurzel() . '/.bau';
    $n = 0;
    foreach ($zeilen as $roh) {
        $z['nach'] = [(string)$roh['zeit'], (int)$roh['id']];
        $zeile = json_encode(protokoll_archiv_zeile($reiter, $roh),
                             JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . "\n";
        if ($z['bytes'] + strlen($zeile) > PROTOKOLL_ARCHIV_MAX) {
            /* HÖCHSTGRÖSSE ERREICHT: nicht still abschneiden, sondern zählen. */
            $z['gekuerzt'][$reiter] = ($z['gekuerzt'][$reiter] ?? 0) + 1;
            continue;
        }
        $teil = (int)($z['teile'][$reiter] ?? 0);
        $datei = $bau . '/' . $reiter . '.' . sprintf('%04d', max(1, $teil)) . '.jsonl';
        if ($teil === 0 || (is_file($datei) && filesize($datei) + strlen($zeile) > PROTOKOLL_ARCHIV_TEIL)) {
            $teil++;
            $z['teile'][$reiter] = $teil;
            $datei = $bau . '/' . $reiter . '.' . sprintf('%04d', $teil) . '.jsonl';
        }
        clearstatcache(true, $datei);
        file_put_contents($datei, $zeile, FILE_APPEND | LOCK_EX);
        $z['bytes'] += strlen($zeile);
        $z['zeilen'][$reiter] = ($z['zeilen'][$reiter] ?? 0) + 1;
        $n++;
    }
    return $n;
}

/**
 * Den Bauordner zum Archiv machen: jeden Teil versiegeln, das Manifest
 * dazu, packen, an seinen Platz legen.
 *
 * DER KLARTEXT LIEGT NUR IM BAUORDNER, und der steht unter `sicherungen/`
 * (`.htaccess` mit `Require all denied`, E16). Er wird hier gelöscht, sobald
 * das Archiv steht, und bei jedem neuen Zeitraum vorher geleert.
 */
function protokoll_archiv_abschliessen(array $z): void
{
    $bau = protokoll_archiv_wurzel() . '/.bau';
    $name = protokoll_archiv_name((string)$z['von'], (string)$z['kennung']);
    $teile = [];
    foreach (glob($bau . '/*.jsonl') ?: [] as $datei) {
        $teil = basename($datei);
        $versiegelt = $bau . '/' . $teil . '.sk';
        file_put_contents($versiegelt,
            sk_versiegeln((string)file_get_contents($datei), protokoll_archiv_zweck($name, $teil)));
        @unlink($datei);
        $teile[$teil . '.sk'] = $versiegelt;
    }
    ksort($teile);
    $manifest = [
        'format'   => 'einsatzdoku-protokollarchiv',
        'fassung'  => 1,
        'von'      => $z['von'], 'bis' => $z['bis'],
        'kennung'  => $z['kennung'],
        'erzeugt'  => iso_utc(),
        'web'      => defined('WEB_VERSION') ? WEB_VERSION : null,
        'zeilen'   => (object)$z['zeilen'],
        'teile'    => array_map(static fn(string $t): string => substr($t, 0, -3), array_keys($teile)),
        'gekuerzt' => (object)$z['gekuerzt'],
        'hinweis'  => 'Sicherheit ohne merkmal und wer, E-Mail nur Vorlage, Zustand, Zeit (E-P5c-39).',
    ];
    $mDatei = $bau . '/manifest.json.sk';
    file_put_contents($mDatei, sk_versiegeln(
        (string)json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        protokoll_archiv_zweck($name, 'manifest.json')));
    $teile = ['manifest.json.sk' => $mDatei] + $teile;

    $tmp = protokoll_archiv_wurzel() . '/' . $name . '.tmp';
    $ok = zip_bauen($tmp, $teile);
    if ($ok !== true) {
        throw new RuntimeException('Das Archiv ließ sich nicht packen: ' . $ok);
    }
    if (!@rename($tmp, protokoll_archiv_wurzel() . '/' . $name)) {
        @unlink($tmp);
        throw new RuntimeException('Das Archiv ließ sich nicht ablegen.');
    }
    protokoll_archiv_bau_leeren($bau);
}

function protokoll_archiv_bau_leeren(string $bau): void
{
    if (!is_dir($bau)) { return; }
    foreach (scandir($bau) ?: [] as $n) {
        if ($n !== '.' && $n !== '..') { @unlink($bau . '/' . $n); }
    }
}

/**
 * Archive älter als die Aufbewahrung löschen. Gezählt ab dem Beginn des
 * Zeitraums im Namen — dieselbe Zahl, die die Seite zeigt.
 *
 * @return int gelöschte Archive
 */
function protokoll_archiv_aufraeumen(): int
{
    $grenze = gmdate('Y-m-d H:i:s', time() - protokoll_archiv_behalten() * 86400);
    $n = 0;
    foreach (protokoll_archive() as $a) {
        if ($a['von'] !== null && $a['von'] < $grenze) {
            if (@unlink(protokoll_archiv_wurzel() . '/' . $a['datei'])) { $n++; }
        }
    }
    return $n;
}

/* ---- Der Download (E-P5c-03) ------------------------------------------------ */

/**
 * Ein Archiv in einen Arbeitsordner entsiegeln: je Reiter eine
 * JSON-Zeilen-Datei und das Manifest, im Klartext.
 *
 * @return array{0:?string, 1:array<string,string>} [Fehlersatz oder null,
 *         Name im Ausgabe-ZIP => Pfad]
 */
function protokoll_archiv_entsiegeln(string $name, string $arbeit): array
{
    if (!protokoll_archiv_name_gueltig($name)) { return ['Kein Archiv dieses Namens.', []]; }
    $pfad = protokoll_archiv_wurzel() . '/' . $name;
    if (!is_file($pfad)) { return ['Das Archiv liegt nicht mehr hier.', []]; }
    if (protokoll_archiv_kennung($name) !== serverschluessel_kennung()) {
        return ['Das Archiv trägt einen anderen Serverschlüssel.', []];
    }
    $manifest = protokoll_archiv_manifest($name);
    if ($manifest === null) {
        return ['Das Archiv lässt sich nicht öffnen — beschädigt oder umbenannt.', []];
    }
    if (!is_dir($arbeit) && !@mkdir($arbeit, 0700, true)) {
        return ['Der Arbeitsordner lässt sich nicht anlegen.', []];
    }
    $fehler = null;
    $ausgabe = [];
    zip_lesen($pfad, static function (callable $eintrag) use ($manifest, $name, $arbeit, &$fehler, &$ausgabe): void {
        foreach ((array)$manifest['teile'] as $teil) {
            $roh = $eintrag($teil . '.sk');
            $klar = $roh === null ? null : sk_oeffnen($roh, protokoll_archiv_zweck($name, (string)$teil));
            if ($klar === null) { $fehler = 'Ein Teil des Archivs lässt sich nicht öffnen.'; return; }
            $reiter = strstr((string)$teil, '.', true);
            $ziel = $arbeit . '/' . $reiter . '.jsonl';
            file_put_contents($ziel, $klar, FILE_APPEND);
            $ausgabe[$reiter . '.jsonl'] = $ziel;
        }
    });
    if ($fehler !== null) { return [$fehler, []]; }
    file_put_contents($arbeit . '/manifest.json',
        json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return [null, ['manifest.json' => $arbeit . '/manifest.json'] + $ausgabe];
}

/**
 * Ein Archiv entsiegelt als gewöhnliches ZIP ausliefern und die Anfrage
 * beenden. Gibt einen Satz zurück, wenn es nicht geht (anderer Schlüssel,
 * beschädigt). Der Protokolleintrag steht beim Aufrufer — der weiß, wer.
 */
function protokoll_archiv_ausliefern(string $name): ?string
{
    $arbeit = sys_get_temp_dir() . '/protokollarchiv-' . bin2hex(random_bytes(6));
    [$fehler, $ausgabe] = protokoll_archiv_entsiegeln($name, $arbeit);
    if ($fehler !== null) { protokoll_archiv_arbeit_weg($arbeit); return $fehler; }
    $zip = $arbeit . '/aus.zip';
    $ok = zip_bauen($zip, $ausgabe, true);
    if ($ok !== true) { protokoll_archiv_arbeit_weg($arbeit); return (string)$ok; }

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="protokoll-' . substr($name, 0, 10) . '.zip"');
    header('Content-Length: ' . (string)filesize($zip));
    header('Cache-Control: no-store');
    readfile($zip);
    protokoll_archiv_arbeit_weg($arbeit);
    exit;
}

function protokoll_archiv_arbeit_weg(string $arbeit): void
{
    foreach (glob($arbeit . '/*') ?: [] as $d) { @unlink($d); }
    @rmdir($arbeit);
}

/**
 * Liegt ein Archiv auf einem Sicherungsziel? Für die Plakette im Reiter
 * Archiv: „auf dem Ziel" oder „nur lokal".
 *
 * @return array<string,bool> Dateiname => auf wenigstens einem Ziel
 */
function protokoll_archiv_auf_ziel(): array
{
    $aus = [];
    try {
        $st = db()->prepare('SELECT DISTINCT datei FROM sicherungsziel_dateien
                              WHERE ordner = ? AND geloescht_am IS NULL');
        $st->execute([PROTOKOLL_ARCHIV_ORDNER]);
        foreach ($st as $z) { $aus[(string)$z['datei']] = true; }
    } catch (Throwable) { /* Versandprotokoll fehlt — dann eben „nur lokal" */ }
    return $aus;
}
