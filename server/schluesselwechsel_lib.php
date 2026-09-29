<?php
declare(strict_types=1);

/**
 * DER WECHSEL DES SERVERSCHLÜSSELS — der Job, der umhüllt (Schritt 18, SR-03)
 * ===========================================================================
 *
 *     sw_beginnen($neu, $alt)          // aus serverschluessel_wechseln()
 *     sw_haeppchen($pdo, $z, $zeitLinks)  // Jobkatalog: ein Häppchen
 *     sw_bedingungen()                 // die drei Riegel vor dem Entfernen
 *     sw_abschliessen($neu, $alt)      // aus serverschluessel_alt_entfernen()
 *     sw_jetzt($budget)                // Knopf „Jetzt weiterarbeiten"
 *
 * Anlass: Nr. 247. Bis Web 21.12.0 gab es keinen Wechsel; wer `server_key`
 * von Hand änderte, machte alles Versiegelte stumm und merkte es erst, wenn er
 * es brauchte. Nr. 247 nennt den Schritt, dessen Fehlen den Vorgang gefährlich
 * macht: den Nachweis der Öffenbarkeit vor dem Verwerfen des alten Schlüssels.
 *
 * DAS INVENTAR KOMMT AUS DEN AUFRUFERN, NICHT AUS DER DOKU (F-SR-01). Gezählt
 * an `sk_versiegeln(` in `server/`: sieben Aufrufe, fünf Zweck-Familien —
 *
 *   ziele    `sicherungsziel:<id>:geheim|schluessel`   Zeile in backup_targets
 *   totp     `totp|<konto>`                            Zeile in users
 *   konten   `adminkonto|<kennung>` (konto.json) und
 *            `adminpaket|<kennung>|<paket>|<teil>`     Dateien unter sicherungen/<kennung>/
 *   archive  `protokollarchiv|<name>|<teil>`           sicherungen/protokoll/*.zip
 *
 * — und als sechster Zweck der Komplett-Stand, der mit dem ROHEN Schlüssel
 * versiegelt ist. Er wird NICHT umgehüllt (E-SR-21): Ein Stand ist eine
 * Momentaufnahme, ein frischer unter dem neuen ersetzt das Umhüllen alter, und
 * die Aufbewahrung räumt die alten wie bisher. Ein frischer Stand ist dafür
 * Abschlussbedingung (`sw_bedingungen()`). Wer einen Zweck dazunimmt, trägt
 * ihn hier ein; die Schlüsselwechselprobe zählt `sk_versiegeln(` gegen diese
 * Liste.
 *
 * JE STÜCK: ÖFFNEN, NEU VERSIEGELN, MIT DEM NEUEN WIEDER ÖFFNEN, VERGLEICHEN —
 * ERST DANN ERSETZEN (E-SR-09). Zeilen mit einem bedingten `UPDATE … WHERE
 * spalte = <gelesener Wert>`, Dateien über eine Nebendatei im Arbeitsordner
 * und `rename()`. Ein Abbruch mitten im Stück hinterlässt nur eine Nebendatei,
 * und die räumt das nächste Häppchen als Erstes weg — der Joblauf sperrt, ein
 * zweiter Umhüller kann nicht daneben arbeiten.
 *
 * DER ZUSTAND IST EIN ZEIGER, KEINE LISTE (E-SR-64). `jobs.zustand` ist TEXT,
 * 64 KiB; eine Liste aller Stücke einer Anlage mit ein paar hundert Konten und
 * drei Paketen je Konto läge darüber. Gemerkt wird deshalb der Zweck und das
 * zuletzt bearbeitete Stück; das nächste Häppchen sucht das folgende in einer
 * festen Sortierung.
 *
 * ZWEI DURCHGÄNGE. `umhuellen` hüllt um, was noch unter dem bisherigen liegt;
 * `nachweis` öffnet danach JEDES Stück noch einmal und zählt, was nicht mit
 * dem neuen aufgeht. Bleibt dabei eines unter dem bisherigen — etwa weil eine
 * Datei während des Umhüllens neu geschrieben wurde —, beginnt der erste
 * Durchgang von vorn. Erst ein Nachweis ohne Rest setzt `fertig`.
 *
 * WAS SICH MIT KEINEM DER BEIDEN ÖFFNEN LÄSST (E-SR-61), wird gezählt,
 * genannt und nicht angefasst: Es ist heute schon unlesbar, und der bisherige
 * Schlüssel hilft ihm nicht. Es blockiert das Entfernen deshalb nicht.
 *
 * WAS AUF EINEM BACKUP-ZIEL LIEGT, ERREICHT DIESER JOB NICHT (E-SR-10). Ein
 * umgehülltes Konto-Backup behält seinen Namen, und der Versand hält es für
 * übertragen — auf dem Ziel bleibt die Kopie unter dem bisherigen. Ein Archiv
 * des Protokolls bekommt dagegen einen neuen Namen (die Kennung steht darin),
 * gilt danach als „nur lokal" und geht noch einmal hinaus.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';        // edbak_wurzel(), Zwecke, Namen
require_once __DIR__ . '/protokoll_archiv_lib.php';   // Namen und Zwecke der Archive
require_once __DIR__ . '/zip_lib.php';

/** Der Name im Jobkatalog und in der Tabelle `jobs`. */
const SW_JOB = 'schluesselwechsel';

/** Die Zwecke in der Reihenfolge, in der sie abgearbeitet werden: erst die
 *  kleinen Zeilen, dann die Dateien. Eine Anmeldung mit Code braucht das
 *  Zweitfaktor-Geheimnis — es ist nach wenigen Häppchen umgehüllt, auch wenn
 *  hinter ihm hundert Konto-Backups warten. */
const SW_ZWECKE = ['ziele', 'totp', 'konten', 'archive'];

/** So viel Zeit muss übrig sein, damit ein Stück angefangen wird. Eine Zeile
 *  kostet Millisekunden; eine Datei kann ein Konto-Backup von 90 MB sein. Am
 *  Huckepack-Weg (3 s) werden deshalb nur Zeilen umgehüllt. */
const SW_RESERVE_ZEILE_S = 0.5;
const SW_RESERVE_DATEI_S = 6.0;

/** So viele unlesbare Stücke werden mit Namen gemerkt; gezählt werden alle. */
const SW_VERLOREN_MAX = 20;

/** Budget des Knopfs „Jetzt weiterarbeiten" — dieselbe Überlegung wie bei
 *  „Alle sichern" und dem Token (20 s unter der üblichen Laufzeitgrenze). */
const SW_BUDGET_SEITE = 20.0;

/* ---- Zustand ------------------------------------------------------------- */

/** Der Zustand des Jobs — ein leeres Feld, wenn kein Wechsel läuft. */
function sw_zustand(): array
{
    try {
        $st = db()->prepare('SELECT zustand FROM jobs WHERE job = ?');
        $st->execute([SW_JOB]);
        $z = json_decode((string)($st->fetchColumn() ?: '{}'), true);
        return is_array($z) ? $z : [];
    } catch (Throwable) {
        return [];
    }
}

/** Den Zustand schreiben — außerhalb eines Joblaufs (Beginn, Abschluss). */
function sw_zustand_setzen(array $z): void
{
    $pdo = db();
    $pdo->prepare('INSERT IGNORE INTO jobs (job) VALUES (?)')->execute([SW_JOB]);
    $pdo->prepare('UPDATE jobs SET zustand = ?, rueckstand = ? WHERE job = ?')
        ->execute([json_encode($z), sw_rueckstand($z), SW_JOB]);
}

/** Wie viele Stücke stehen noch aus? `null`, wenn kein Wechsel läuft. */
function sw_rueckstand(array $z): ?int
{
    if (($z['phase'] ?? '') === '' || $z['phase'] === 'fertig') { return null; }
    return max(0, (int)($z['gesamt'] ?? 0) - (int)($z['erledigt'] ?? 0));
}

/** Zeitpunkt als Sekunden — für die Vergleiche mit dem Beginn. Liest
 *  `Y-m-d H:i:s` (UTC) wie ISO — die eine Stelle ist `iso_utc_lesen()`
 *  (R83, Register Z25; eine eigene Rechnung hier riss die Decke). */
function sw_zeit(?string $t): ?int
{
    require_once __DIR__ . '/format_lib.php';
    return iso_utc_lesen($t);
}

/* ---- Inventar ------------------------------------------------------------ */

/** Der Arbeitsordner: unter `sicherungen/` (`.htaccess`, E16), aber kein
 *  Kontoordner — `edbak_kennung_gueltig()` nimmt ihn nicht, und die
 *  Bauresten-Räumung eines Konto-Backups (`edbak_baureste_aufraeumen()`)
 *  sieht ihn deshalb nicht. Läge er im Kontoordner, räumte ein gleichzeitiges
 *  Backup desselben Kontos die Nebendatei weg. */
function sw_arbeit(): string
{
    return edbak_wurzel() . '/.schluesselwechsel';
}

/** Den Arbeitsordner leeren — am Anfang jedes Häppchens: Was dort liegt, ist
 *  ein halbes Stück eines abgebrochenen Laufs (E-SR-09, Wiederanlauf). */
function sw_arbeit_leeren(): void
{
    $a = sw_arbeit();
    if (!is_dir($a)) { @mkdir($a, 0770, true); return; }
    foreach (scandir($a) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        $voll = $a . '/' . $n;
        if (is_dir($voll)) {
            foreach (scandir($voll) ?: [] as $m) {
                if ($m !== '.' && $m !== '..') { @unlink($voll . '/' . $m); }
            }
            @rmdir($voll);
        } else {
            @unlink($voll);
        }
    }
}

/**
 * Die Stücke eines Zwecks — als Liste von Schlüsseln, LEXIKALISCH sortiert.
 *
 * `ziele`, `totp`: Zeilennummern, auf zehn Stellen mit Nullen aufgefüllt;
 * `konten`: `<kennung>/<datei>`; `archive`: Dateinamen. Der Zeiger im
 * Zustand vergleicht mit `strcmp()`, und darauf muss die Reihenfolge passen:
 * Ungefüllt stünde „10" vor „9", und nach Stück 9 würde 10 übersprungen.
 */
function sw_stuecke(string $zweck): array
{
    $aus = [];
    if ($zweck === 'ziele') {
        foreach (db()->query('SELECT id FROM backup_targets
                              WHERE geheim IS NOT NULL OR schluessel IS NOT NULL
                              ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $id) {
            $aus[] = sprintf('%010d', (int)$id);
        }
    } elseif ($zweck === 'totp') {
        require_once __DIR__ . '/totp_lib.php';
        if (totp_spalten_da()) {
            foreach (db()->query('SELECT id FROM users WHERE totp_geheimnis IS NOT NULL
                                  ORDER BY id')->fetchAll(PDO::FETCH_COLUMN) as $id) {
                $aus[] = sprintf('%010d', (int)$id);
            }
        }
    } elseif ($zweck === 'konten') {
        $w = edbak_wurzel();
        $ordner = [];
        foreach (is_dir($w) ? (scandir($w) ?: []) : [] as $n) {
            if (edbak_kennung_gueltig($n) && is_dir($w . '/' . $n)) { $ordner[] = $n; }
        }
        foreach ($ordner as $k) {
            $o = $w . '/' . $k;
            if (is_file($o . '/konto.json')) { $aus[] = $k . '/konto.json'; }
            $pakete = [];
            foreach (scandir($o) ?: [] as $d) {
                if (edbak_paketname_gueltig($d) && is_file($o . '/' . $d)) { $pakete[] = $d; }
            }
            foreach ($pakete as $d) { $aus[] = $k . '/' . $d; }
        }
        sort($aus, SORT_STRING);
    } elseif ($zweck === 'archive') {
        $w = protokoll_archiv_wurzel();
        foreach (is_dir($w) ? (scandir($w) ?: []) : [] as $n) {
            if (protokoll_archiv_name_gueltig($n) && is_file($w . '/' . $n)) { $aus[] = $n; }
        }
        sort($aus, SORT_STRING);
    }
    return $aus;
}

/**
 * Das Inventar: Stücke je Zweck und zusammen, dazu die Komplett-Stände (die
 * nicht umgehüllt werden, E-SR-21 — sie stehen hier, damit die Karte sagt,
 * wie viele unter dem bisherigen bleiben).
 *
 * @return array{zwecke: array<string,int>, summe: int, komplett: int}
 */
function sw_inventar(): array
{
    $zwecke = [];
    foreach (SW_ZWECKE as $zw) { $zwecke[$zw] = count(sw_stuecke($zw)); }
    require_once __DIR__ . '/komplett_lib.php';
    return ['zwecke' => $zwecke, 'summe' => array_sum($zwecke),
            'komplett' => count(komp_staende())];
}

/* ---- Ein Stück ----------------------------------------------------------- */

/*
 * Jede der vier Funktionen darunter bearbeitet ein Stück und sagt, was sie
 * vorfand. Mit `$nurPruefen = true` (Nachweis) ändert sie nichts.
 *
 *   'neu'          öffnet mit dem neuen — nichts zu tun
 *   'umgehuellt'   lag unter dem bisherigen, ist jetzt unter dem neuen (nur
 *                  beim Umhüllen)
 *   'alt'          liegt noch unter dem bisherigen (nur beim Nachweis)
 *   'unversiegelt' trägt kein Siegel (Fassung 1/2 eines Konto-Backups, eine
 *                  Begleitdatei von vor S10) — hängt an keinem Schlüssel
 *   'verloren'     öffnet mit keinem der beiden (E-SR-61)
 *   'spaeter'      wurde während des Umhüllens verändert — der Nachweis
 *                  findet es wieder
 *   'weg'          gibt es nicht mehr
 */

/** Rangfolge beim Zusammenfassen mehrerer Felder eines Stücks. */
const SW_RANG = ['verloren' => 6, 'alt' => 5, 'spaeter' => 4, 'umgehuellt' => 3,
                 'neu' => 2, 'unversiegelt' => 1, 'weg' => 0];

/** Ein versiegelter Wert: neu versiegeln und NACHWEISEN, bevor er ersetzt wird. */
function sw_umsiegeln(string $klar, string $zweck): ?string
{
    $neu = sk_versiegeln($klar, $zweck);
    $probe = sk_oeffnen_mit_wem($neu, $zweck);
    return ($probe !== null && $probe['mit'] === 'neu' && hash_equals($klar, $probe['klar']))
        ? $neu : null;
}

/** Eine Zeile mit versiegelten Spalten (`backup_targets`, `users`). */
function sw_zeile(string $tabelle, array $felder, int $id, callable $zweck, bool $nurPruefen): string
{
    $st = db()->prepare('SELECT ' . implode(', ', $felder) . ' FROM ' . $tabelle . ' WHERE id = ?');
    $st->execute([$id]);
    $zeile = $st->fetch(PDO::FETCH_ASSOC);
    if (!is_array($zeile)) { return 'weg'; }
    $ergebnis = 'unversiegelt';
    foreach ($felder as $feld) {
        $wert = $zeile[$feld];
        if (!is_string($wert) || $wert === '' || !sk_versiegelt($wert)) { continue; }
        $e = sk_oeffnen_mit_wem($wert, $zweck($feld));
        if ($e === null) {
            $was = 'verloren';
        } elseif ($e['mit'] === 'neu') {
            $was = 'neu';
        } elseif ($nurPruefen) {
            $was = 'alt';
        } else {
            $neu = sw_umsiegeln($e['klar'], $zweck($feld));
            if ($neu === null) {
                $was = 'verloren';
            } else {
                /* BEDINGT: Nur wenn dort noch steht, was gelesen wurde. Hat
                 * jemand das Ziel inzwischen neu gespeichert, ist sein Wert
                 * schon unter dem neuen, und dieser hier wäre der ältere. */
                $up = db()->prepare('UPDATE ' . $tabelle . ' SET ' . $feld . ' = ?
                                      WHERE id = ? AND ' . $feld . ' = ?');
                $up->execute([$neu, $id, $wert]);
                $was = $up->rowCount() === 1 ? 'umgehuellt' : 'spaeter';
            }
        }
        if (SW_RANG[$was] > SW_RANG[$ergebnis]) { $ergebnis = $was; }
    }
    return $ergebnis;
}

/** Die Begleitdatei eines Kontoordners (`konto.json`, Zweck `adminkonto|…`). */
function sw_begleit(string $kennung, bool $nurPruefen): string
{
    $pfad = edbak_ordner($kennung) . '/konto.json';
    $roh = @file_get_contents($pfad);
    if ($roh === false) { return 'weg'; }
    if (!sk_versiegelt($roh)) { return 'unversiegelt'; }
    $zweck = edbak_begleit_zweck($kennung);
    $e = sk_oeffnen_mit_wem($roh, $zweck);
    if ($e === null) { return 'verloren'; }
    if ($e['mit'] === 'neu') { return 'neu'; }
    if ($nurPruefen) { return 'alt'; }
    $neu = sw_umsiegeln($e['klar'], $zweck);
    if ($neu === null) { return 'verloren'; }
    $tmp = sw_arbeit() . '/' . $kennung . '-konto.json';
    if (@file_put_contents($tmp, $neu) === false) {
        throw new RuntimeException('Die Nebendatei im Arbeitsordner ließ sich nicht schreiben.');
    }
    /* UNMITTELBAR VOR DEM ERSETZEN NOCH EINMAL LESEN. Ein Konto-Backup
     * desselben Kontos kann die Datei inzwischen geschrieben haben — dann
     * steht dort der neuere Stand, schon unter dem neuen Schlüssel, und die
     * Umhüllung des älteren darf ihn nicht überschreiben. Das Fenster
     * zwischen diesem Vergleich und dem `rename()` bleibt; es ist so kurz wie
     * ein Systemaufruf, und der Nachweis fände einen Verlust nicht — er fände
     * nur, dass alles unter dem neuen liegt. Deshalb steht es hier. */
    if (@file_get_contents($pfad) !== $roh) { @unlink($tmp); return 'spaeter'; }
    if (!@rename($tmp, $pfad)) {
        @unlink($tmp);
        throw new RuntimeException('Die Begleitdatei ließ sich nicht ersetzen.');
    }
    return 'umgehuellt';
}

/**
 * Ein Konto-Backup (ZIP, Fassung 3): jeden Teil umsiegeln, das Archiv neu
 * bauen, JEDEN Teil mit dem neuen öffnen und mit dem Klartext davor
 * vergleichen (Prüfsumme), dann ersetzen. Fassung 1 (JSON) und Fassung 2 (ZIP
 * ohne Siegel) hängen an keinem Schlüssel.
 */
function sw_paket(string $kennung, string $datei, bool $nurPruefen): string
{
    $pfad = edbak_ordner($kennung) . '/' . $datei;
    if (!is_file($pfad)) { return 'weg'; }
    if (edbak_paket_fassung($datei) !== 2) { return 'unversiegelt'; }
    $mRoh = zip_eintrag($pfad, 'manifest.json');
    if ($mRoh === null) { return 'verloren'; }
    if (!sk_versiegelt($mRoh)) { return 'unversiegelt'; }
    $m = sk_oeffnen_mit_wem($mRoh, edbak_teil_zweck($kennung, $datei, 'manifest.json'));
    if ($m === null) { return 'verloren'; }
    if ($m['mit'] === 'neu') { return 'neu'; }
    if ($nurPruefen) { return 'alt'; }

    $namen = zip_namen($pfad);
    $zip = zip_oeffnen($pfad);
    if ($namen === null || $zip === null) { return 'verloren'; }
    $bau = sw_arbeit() . '/' . $kennung . '-' . $datei . '.d';
    if (!is_dir($bau) && !@mkdir($bau, 0770, true)) {
        $zip->close();
        throw new RuntimeException('Der Arbeitsordner für ein Konto-Backup ließ sich nicht anlegen.');
    }
    $teile = []; $pruef = [];
    try {
        foreach ($namen as $nr => $name) {
            $roh = $zip->getFromName($name);
            if ($roh === false) { return 'verloren'; }
            if (sk_versiegelt($roh)) {
                $zw = edbak_teil_zweck($kennung, $datei, $name);
                $e = sk_oeffnen_mit_wem($roh, $zw);
                if ($e === null) { return 'verloren'; }
                $aus = $e['mit'] === 'neu' ? $roh : sw_umsiegeln($e['klar'], $zw);
                if ($aus === null) { return 'verloren'; }
                $pruef[$name] = hash('sha256', $e['klar']);
            } else {
                $aus = $roh;
                $pruef[$name] = null;
            }
            $f = $bau . '/' . $nr;
            if (@file_put_contents($f, $aus) === false) {
                throw new RuntimeException('Ein Teil ließ sich nicht in den Arbeitsordner schreiben.');
            }
            $teile[$name] = $f;
            unset($roh, $aus, $e);
        }
    } finally {
        $zip->close();
    }
    $tmp = sw_arbeit() . '/' . $kennung . '-' . $datei;
    $ok = zip_bauen($tmp, $teile);
    foreach ($teile as $f) { @unlink($f); }
    @rmdir($bau);
    if ($ok !== true) { throw new RuntimeException('Das umgehüllte Konto-Backup ließ sich nicht bauen: ' . $ok); }

    /* DER NACHWEIS AN DER NEUEN DATEI, BEVOR SIE DIE ALTE ERSETZT: jeder
     * versiegelte Teil mit dem NEUEN geöffnet und gegen die Prüfsumme des
     * Klartexts davor verglichen; ungesiegelte Teile nur auf ihr Dasein. */
    $gut = false;
    $offen = zip_lesen($tmp, static function (callable $eintrag) use ($pruef, $kennung, $datei, &$gut): void {
        foreach ($pruef as $name => $summe) {
            $roh = $eintrag((string)$name);
            if ($roh === null) { return; }
            if ($summe === null) { continue; }
            $e = sk_oeffnen_mit_wem($roh, edbak_teil_zweck($kennung, $datei, (string)$name));
            if ($e === null || $e['mit'] !== 'neu' || !hash_equals($summe, hash('sha256', $e['klar']))) {
                return;
            }
        }
        $gut = true;
    });
    if (!$offen || !$gut) {
        @unlink($tmp);
        throw new RuntimeException('Das umgehüllte Konto-Backup ' . $datei . ' hat den Nachweis '
            . 'nicht bestanden. Das bisherige bleibt, wie es ist.');
    }
    /* Ist das Paket inzwischen verdrängt oder gelöscht, holt es der Wechsel
     * nicht zurück. */
    if (!is_file($pfad)) { @unlink($tmp); return 'weg'; }
    if (!@rename($tmp, $pfad)) {
        @unlink($tmp);
        throw new RuntimeException('Das Konto-Backup ' . $datei . ' ließ sich nicht ersetzen.');
    }
    return 'umgehuellt';
}

/**
 * Ein Archiv des Protokolls. DIE KENNUNG STEHT IM NAMEN, und der Name im
 * Zweck jedes Teils — umgehüllt wird deshalb in eine Datei mit NEUEM Namen,
 * nachgewiesen, dann die alte gelöscht. Der Versand hält die neue für „nur
 * lokal" und schickt sie hinaus; auf dem Ziel liegt dann eine Kopie unter
 * jedem der beiden Schlüssel.
 */
function sw_archiv(string $name, string $neu, string $alt, bool $nurPruefen): string
{
    $w = protokoll_archiv_wurzel();
    $pfad = $w . '/' . $name;
    if (!is_file($pfad)) { return 'weg'; }
    $k = protokoll_archiv_kennung($name);
    $mRoh = zip_eintrag($pfad, 'manifest.json.sk');
    if ($mRoh === null) { return 'verloren'; }
    $m = sk_oeffnen_mit_wem($mRoh, protokoll_archiv_zweck($name, 'manifest.json'));
    if ($m === null) { return 'verloren'; }
    if ($m['mit'] === 'neu' && $k === $neu) { return 'neu'; }
    if ($k !== $alt) { return 'verloren'; }       // weder Name noch Schlüssel passen zusammen
    if ($nurPruefen) { return 'alt'; }

    $von = protokoll_archiv_von($name);
    $manifest = json_decode($m['klar'], true);
    if ($von === null || !is_array($manifest)) { return 'verloren'; }
    $neuName = protokoll_archiv_name($von, $neu);
    $ziel = $w . '/' . $neuName;
    /* Das Soll trägt die NEUE Kennung — vor dem Wiederanlauf gesetzt, sonst
     * verglich der Nachweis die neue Datei mit der alten Kennung und warf
     * ein fertiges Archiv weg, um es noch einmal zu bauen. */
    $manifest['kennung'] = $neu;

    /* WIEDERANLAUF: Liegt die neue Datei schon da und besteht den Nachweis,
     * brach der vorige Lauf zwischen Umbenennen und Löschen ab. */
    if (is_file($ziel)) {
        if (sw_archiv_nachweis($neuName, $manifest)) {
            @unlink($pfad);
            return 'umgehuellt';
        }
        @unlink($ziel);
    }

    $bau = sw_arbeit() . '/' . $neuName . '.d';
    if (!is_dir($bau) && !@mkdir($bau, 0770, true)) {
        throw new RuntimeException('Der Arbeitsordner für ein Archiv ließ sich nicht anlegen.');
    }
    $teile = [];
    $fehlt = false;
    zip_lesen($pfad, static function (callable $eintrag) use ($manifest, $name, $neuName, $bau, &$teile, &$fehlt): void {
        foreach ((array)($manifest['teile'] ?? []) as $nr => $teil) {
            $roh = $eintrag($teil . '.sk');
            $e = $roh === null ? null : sk_oeffnen_mit_wem($roh, protokoll_archiv_zweck($name, (string)$teil));
            $aus = $e === null ? null : sw_umsiegeln($e['klar'], protokoll_archiv_zweck($neuName, (string)$teil));
            if ($aus === null) { $fehlt = true; return; }
            $f = $bau . '/' . $nr;
            if (@file_put_contents($f, $aus) === false) { $fehlt = true; return; }
            $teile[$teil . '.sk'] = $f;
        }
    });
    $mNeu = $fehlt ? null : sw_umsiegeln(
        (string)json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        protokoll_archiv_zweck($neuName, 'manifest.json'));
    if ($mNeu === null) {
        foreach ($teile as $f) { @unlink($f); }
        @rmdir($bau);
        return 'verloren';
    }
    if (@file_put_contents($bau . '/manifest', $mNeu) === false) {
        throw new RuntimeException('Ein Teil ließ sich nicht in den Arbeitsordner schreiben.');
    }
    $teile = ['manifest.json.sk' => $bau . '/manifest'] + $teile;
    $tmp = sw_arbeit() . '/' . $neuName;
    $ok = zip_bauen($tmp, $teile);
    foreach ($teile as $f) { @unlink($f); }
    @rmdir($bau);
    if ($ok !== true) { throw new RuntimeException('Das umgehüllte Archiv ließ sich nicht bauen: ' . $ok); }
    if (!@rename($tmp, $ziel)) {
        @unlink($tmp);
        throw new RuntimeException('Das Archiv ' . $neuName . ' ließ sich nicht ablegen.');
    }
    if (!sw_archiv_nachweis($neuName, $manifest)) {
        @unlink($ziel);
        throw new RuntimeException('Das umgehüllte Archiv ' . $neuName . ' hat den Nachweis nicht '
            . 'bestanden. Das bisherige bleibt, wie es ist.');
    }
    @unlink($pfad);
    return 'umgehuellt';
}

/** Öffnet sich jeder Teil des Archivs mit dem NEUEN, und sagt das Manifest
 *  dasselbe wie das bisherige (bis auf die Kennung)? */
function sw_archiv_nachweis(string $neuName, array $manifestSoll): bool
{
    $pfad = protokoll_archiv_wurzel() . '/' . $neuName;
    $gut = false;
    zip_lesen($pfad, static function (callable $eintrag) use ($neuName, $manifestSoll, &$gut): void {
        $m = $eintrag('manifest.json.sk');
        $e = $m === null ? null : sk_oeffnen_mit_wem($m, protokoll_archiv_zweck($neuName, 'manifest.json'));
        $ist = $e === null ? null : json_decode($e['klar'], true);
        if ($e === null || $e['mit'] !== 'neu' || !is_array($ist)
            || ($ist['teile'] ?? null) !== ($manifestSoll['teile'] ?? null)
            || ($ist['kennung'] ?? null) !== ($manifestSoll['kennung'] ?? null)) { return; }
        foreach ((array)($ist['teile'] ?? []) as $teil) {
            $roh = $eintrag($teil . '.sk');
            $t = $roh === null ? null : sk_oeffnen_mit_wem($roh, protokoll_archiv_zweck($neuName, (string)$teil));
            if ($t === null || $t['mit'] !== 'neu') { return; }
        }
        $gut = true;
    });
    return $gut;
}

/** Ein Stück bearbeiten — der Verteiler über die vier Zwecke. */
function sw_stueck(string $zweck, string $schluessel, array $z, bool $nurPruefen): string
{
    if ($zweck === 'ziele') {
        require_once __DIR__ . '/sicherungsziel_lib.php';
        $id = (int)ltrim($schluessel, '0');
        return sw_zeile('backup_targets', ['geheim', 'schluessel'], $id,
                        static fn(string $feld): string => sz_zweck($id, $feld), $nurPruefen);
    }
    if ($zweck === 'totp') {
        $id = (int)ltrim($schluessel, '0');
        return sw_zeile('users', ['totp_geheimnis'], $id,
                        static fn(string $feld): string => 'totp|' . $id, $nurPruefen);
    }
    if ($zweck === 'konten') {
        [$kennung, $datei] = explode('/', $schluessel, 2);
        return $datei === 'konto.json' ? sw_begleit($kennung, $nurPruefen)
                                       : sw_paket($kennung, $datei, $nurPruefen);
    }
    return sw_archiv($schluessel, (string)$z['kennung_neu'], (string)$z['kennung_alt'], $nurPruefen);
}

/** Wie ein Stück in der Liste der Unlesbaren heißt. KEINE KONTOKENNUNG — sie
 *  erscheint an keiner Stelle der Oberfläche (Akzeptanzkriterium 49, E17). */
function sw_stueck_name(string $zweck, string $schluessel): string
{
    if ($zweck === 'ziele')  { return 'Zugang des Backup-Ziels Nr. ' . (int)ltrim($schluessel, '0'); }
    if ($zweck === 'totp')   { return 'Zweitfaktor-Geheimnis des Kontos Nr. ' . (int)ltrim($schluessel, '0'); }
    if ($zweck === 'konten') {
        [$kennung, $datei] = explode('/', $schluessel, 2);
        return $datei === 'konto.json'
            ? 'Begleitdatei eines Konto-Backup-Ordners (' . edbak_handgriff($kennung) . ')'
            : 'Konto-Backup ' . $datei;
    }
    return 'Archiv des Protokolls ' . $schluessel;
}

/* ---- Das Häppchen -------------------------------------------------------- */

/**
 * Ein Häppchen des Jobs — der Katalog ruft es über `job_schluesselwechsel()`.
 *
 * @return array{erledigt:int, fertig:bool}
 */
function sw_haeppchen(PDO $pdo, array &$z, callable $zeitLinks): array
{
    $sk = serverschluessel_zustand(true);
    if ($sk['stand'] !== 'rotation' || ($z['phase'] ?? '') === '') {
        /* Kein Wechsel, oder einer ohne Zustand (von Hand in config.php
         * eingetragen). Ohne Zustand fehlt der Beginn, gegen den der
         * frische Komplett-Stand und die Rückfrage gemessen werden — er wird
         * hier gesetzt, nicht erraten. */
        if ($sk['stand'] === 'rotation') {
            $z = sw_zustand_neu((string)$sk['kennung'], (string)$sk['kennung_alt']);
        } else {
            $z = [];
            return ['erledigt' => 0, 'fertig' => true];
        }
    }
    if ($z['phase'] === 'fertig') { return ['erledigt' => 0, 'fertig' => true]; }

    sw_arbeit_leeren();
    $erledigt = 0;
    $runden = 0;
    while (true) {
        $zweck = (string)$z['zweck'];
        $liste = sw_stuecke($zweck);
        $cursor = $z['cursor'] ?? null;
        $naechstes = null;
        foreach ($liste as $s) {
            if ($cursor === null || strcmp($s, (string)$cursor) > 0) { $naechstes = $s; break; }
        }
        if ($naechstes === null) {
            $i = array_search($zweck, SW_ZWECKE, true);
            if ($i !== false && $i + 1 < count(SW_ZWECKE)) {
                $z['zweck'] = SW_ZWECKE[$i + 1];
                $z['cursor'] = null;
                continue;
            }
            /* Ein Durchgang ist durch. */
            if ($z['phase'] === 'umhuellen') {
                $z['phase'] = 'nachweis';
                $z['zweck'] = SW_ZWECKE[0];
                $z['cursor'] = null;
                $z['erledigt'] = 0;
                $z['zahlen'] = [];
                $z['nachweis_alt'] = 0;
                $z['gesamt'] = sw_inventar()['summe'];
                continue;
            }
            if ((int)($z['nachweis_alt'] ?? 0) > 0) {
                /* Etwas liegt noch unter dem bisherigen — von vorn. Höchstens
                 * dreimal in einem Häppchen; danach wartet es aufs nächste. */
                if (++$runden > 3) { return ['erledigt' => $erledigt, 'fertig' => false]; }
                $z = array_merge($z, ['phase' => 'umhuellen', 'zweck' => SW_ZWECKE[0],
                                      'cursor' => null, 'erledigt' => 0, 'zahlen' => [],
                                      'nachweis_alt' => 0,
                                      'gesamt' => sw_inventar()['summe']]);
                continue;
            }
            $z['phase'] = 'fertig';
            $z['nachweis_am'] = gmdate('Y-m-d H:i:s');
            sw_nachweis_melden($z);
            $z['komplett_auftrag'] = sw_komplett_anstossen($z);
            return ['erledigt' => $erledigt, 'fertig' => true];
        }

        $reserve = in_array($zweck, ['konten', 'archive'], true)
            ? SW_RESERVE_DATEI_S : SW_RESERVE_ZEILE_S;
        if ($zeitLinks() < $reserve || sw_speicher_knapp()) {
            return ['erledigt' => $erledigt, 'fertig' => false];
        }
        $nurPruefen = $z['phase'] === 'nachweis';
        $was = sw_stueck($zweck, $naechstes, $z, $nurPruefen);
        $z['cursor'] = $naechstes;
        $z['erledigt'] = (int)($z['erledigt'] ?? 0) + 1;
        $erledigt++;
        /* GEZÄHLT WIRD JE DURCHGANG (`zahlen`, am Beginn eines Durchgangs
         * geleert) — nur `umgehuellt` läuft über alle Durchgänge weiter. Sonst
         * zählte ein zweiter Durchgang jedes Stück, das schon im ersten
         * umgehüllt wurde, noch einmal als „neu". */
        $zahlen = (array)($z['zahlen'] ?? []);
        $zahlen[$was] = (int)($zahlen[$was] ?? 0) + 1;
        $z['zahlen'] = $zahlen;
        if ($was === 'umgehuellt') { $z['umgehuellt'] = (int)($z['umgehuellt'] ?? 0) + 1; }
        if ($nurPruefen && ($was === 'alt' || $was === 'spaeter')) {
            $z['nachweis_alt'] = (int)($z['nachweis_alt'] ?? 0) + 1;
        }
        if ($was === 'verloren') {
            $liste = (array)($z['verloren'] ?? []);
            $name = sw_stueck_name($zweck, $naechstes);
            if (!in_array($name, $liste, true) && count($liste) < SW_VERLOREN_MAX) { $liste[] = $name; }
            $z['verloren'] = $liste;
        }
    }
}

/** Das Speicherbudget — `jobs_speicher_knapp()`, wenn der Rahmen geladen ist
 *  (am Knopf und im Job immer; in einer Probe ohne Jobrahmen nie knapp). */
function sw_speicher_knapp(): bool
{
    return function_exists('jobs_speicher_knapp') && jobs_speicher_knapp();
}

/** Der Anfangszustand eines Wechsels. */
function sw_zustand_neu(string $neu, string $alt): array
{
    return ['kennung_neu' => $neu, 'kennung_alt' => $alt,
            'begonnen' => gmdate('Y-m-d H:i:s'),
            'phase' => 'umhuellen', 'zweck' => SW_ZWECKE[0], 'cursor' => null,
            'erledigt' => 0, 'gesamt' => sw_inventar()['summe'],
            'zahlen' => [], 'umgehuellt' => 0, 'verloren' => [], 'nachweis_alt' => 0];
}

/** Der Nachweis ist durch — ein Protokolleintrag mit den Zahlen. */
function sw_nachweis_melden(array $z): void
{
    require_once __DIR__ . '/protokoll_lib.php';
    $n = (array)($z['zahlen'] ?? []);
    $umg = (int)($z['umgehuellt'] ?? 0);
    $verl = count((array)($z['verloren'] ?? []));
    protokoll('sicherung', 'serverschluessel_umgehuellt',
        'Serverschlüssel umgehüllt und nachgewiesen — ' . $umg . ' Stück(e) vom bisherigen ('
        . $z['kennung_alt'] . ') auf den neuen (' . $z['kennung_neu'] . ')'
        . ($verl > 0 ? ', ' . $verl . ' mit keinem der beiden zu öffnen' : ''),
        ['neu' => $z['kennung_neu'], 'alt' => $z['kennung_alt'], 'umgehuellt' => $umg,
         'nachweis' => $n,
         'verloren' => array_values((array)($z['verloren'] ?? []))]);
}

/* ---- Beginn, Riegel, Abschluss ------------------------------------------- */

/**
 * Was nach dem Schreiben von `config.php` zum Beginn gehört: Zustand, die
 * Blatt-Marke (Nr. 233, E-SR-11), Protokoll und die Mail an jede BetreiberIn
 * (E-SR-22, E-SR-62). Gerufen aus `serverschluessel_wechseln()`.
 */
function sw_beginnen(string $neu, string $alt): void
{
    $z = sw_zustand_neu($neu, $alt);
    sw_zustand_setzen($z);
    require_once __DIR__ . '/einstieg_lib.php';
    blatt_neu_faellig('serverschluessel');
    require_once __DIR__ . '/protokoll_lib.php';
    protokoll('sicherung', 'serverschluessel_gewechselt',
        'Serverschlüssel gewechselt — neu ' . $neu . ', bisher ' . $alt . '; '
        . (int)$z['gesamt'] . ' Stück(e) umzuhüllen. Was auf einem Backup-Ziel liegt, '
        . 'bleibt unter dem bisherigen.',
        ['neu' => $neu, 'alt' => $alt, 'stuecke' => (int)$z['gesamt']]);
    sw_mail('beginn', $neu, $alt);
}

/**
 * Die drei Bedingungen vor dem Entfernen des bisherigen (E-SR-09) — für die
 * Karte UND als Riegel in `serverschluessel_alt_entfernen()`.
 *
 * @return array{inventar:bool, komplett:bool, blatt:bool, alle:bool, fehlt:list<string>}
 */
function sw_bedingungen(): array
{
    $z = sw_zustand();
    $sk = serverschluessel_zustand();
    $beginn = sw_zeit((string)($z['begonnen'] ?? ''));
    $rotation = $sk['stand'] === 'rotation' && $beginn !== null;

    $inventar = $rotation && ($z['phase'] ?? '') === 'fertig'
             && sw_zeit((string)($z['nachweis_am'] ?? '')) !== null
             && sw_zeit((string)$z['nachweis_am']) >= $beginn;
    $komplett = $rotation && sw_komplett_unter_neuem($beginn);
    require_once __DIR__ . '/einstieg_lib.php';
    $bestaetigt = sw_zeit(app_state_lesen(BLATT_BESTAETIGT_K));
    $blatt = $rotation && $bestaetigt !== null && $bestaetigt >= $beginn;

    $fehlt = [];
    if (!$rotation) { $fehlt[] = 'Es läuft kein Wechsel des Serverschlüssels.'; }
    if ($rotation && !$inventar) {
        $fehlt[] = 'Noch ist nicht alles umgehüllt und mit dem neuen Schlüssel nachgewiesen'
                 . (($r = sw_rueckstand($z)) !== null ? ' (noch ' . $r . ' von '
                    . (int)($z['gesamt'] ?? 0) . ' Stücken)' : '') . '.';
    }
    if ($rotation && !$komplett) {
        $auftrag = (string)($z['komplett_auftrag'] ?? '');
        $fehlt[] = 'Es gibt noch keinen Komplett-Stand unter dem neuen Schlüssel, der jünger '
                 . 'ist als der Beginn des Wechsels'
                 . ($auftrag === 'vorgemerkt'
                    ? ' — einer ist vorgemerkt und läuft mit dem nächsten Joblauf an.'
                    : ($auftrag !== '' && $auftrag !== 'schon da'
                        ? ' — vormerken ging nicht: ' . rtrim($auftrag, '.') . '.' : '.'));
    }
    if ($rotation && !$blatt) {
        $fehlt[] = 'Die Rückfrage zum Schlüsselblatt ist seit dem Beginn des Wechsels nicht '
                 . 'beantwortet.';
    }
    return ['inventar' => $inventar, 'komplett' => $komplett, 'blatt' => $blatt,
            'alle' => $rotation && $inventar && $komplett && $blatt, 'fehlt' => $fehlt];
}

/**
 * Den frischen Komplett-Stand ANSTOSSEN, sobald alles nachgewiesen ist
 * (Q-SR-03: „der Vorgang sagt das und stößt ‚Jetzt sichern' an").
 *
 * ERST NACH DEM NACHWEIS, nicht beim Beginn: Ein Stand, der mitten im
 * Umhüllen entsteht, trüge Zeilen unter beiden Schlüsseln, und ihn
 * einzuspielen verlangte beide. Vorgemerkt wird ein gewöhnlicher Auftrag
 * (`komp_auftrag_starten()`); er läuft mit dem nächsten Joblauf an, wie
 * „Jetzt sichern" ohne offene Seite. Scheitert das Vormerken — Grenze
 * erreicht, es läuft schon einer —, steht der Grund im Zustand, und die
 * Karte sagt ihn; der Wechsel hängt nicht daran.
 *
 * @return string 'vorgemerkt', 'schon da' oder der Satz, warum nicht
 */
function sw_komplett_anstossen(array $z): string
{
    try {
        require_once __DIR__ . '/komplett_lib.php';
        $beginn = sw_zeit((string)($z['begonnen'] ?? ''));
        if ($beginn !== null && sw_komplett_unter_neuem($beginn)) { return 'schon da'; }
        $r = komp_auftrag_starten();
        return $r['ok'] ? 'vorgemerkt' : (string)$r['meldung'];
    } catch (Throwable $ex) {
        system_melden('schluesselwechsel', 'Komplett-Stand nicht vorgemerkt', $ex);
        return 'Der Auftrag ließ sich nicht vormerken.';
    }
}

/** Gibt es einen Komplett-Stand unter dem NEUEN Schlüssel, jünger als `$beginn`?
 *  Geprüft am Kopf (`kennung`, seit Web 21.12.0) UND am ersten Block — der
 *  Kopf allein ist eine Behauptung. */
function sw_komplett_unter_neuem(int $beginn): bool
{
    require_once __DIR__ . '/komplett_lib.php';
    $kennung = serverschluessel_kennung();
    $k = serverschluessel();
    if ($kennung === null || $k === null) { return false; }
    foreach (komp_staende() as $s) {
        $t = sw_zeit($s['zeit'] ?? null);
        if ($t === null || $t < $beginn) { continue; }
        $pfad = komp_wurzel() . '/' . $s['datei'];
        $kopf = komp_kopf_lesen($pfad);
        if ($kopf === null || ($kopf['kopf']['kdf'] ?? null) !== null
            || ($kopf['kopf']['kennung'] ?? null) !== $kennung) { continue; }
        if (komp_erster_block_oeffnet($pfad, $k)) { return true; }
    }
    return false;
}

/**
 * Was nach dem Entfernen des bisherigen zum Abschluss gehört: Zustand leeren,
 * Protokoll, Mail. Gerufen aus `serverschluessel_alt_entfernen()`.
 */
function sw_abschliessen(string $neu, string $alt): void
{
    $z = sw_zustand();
    sw_zustand_setzen([]);
    require_once __DIR__ . '/protokoll_lib.php';
    protokoll('sicherung', 'serverschluessel_alt_entfernt',
        'Bisheriger Serverschlüssel ' . $alt . ' aus config.php entfernt — der Wechsel auf '
        . $neu . ' ist abgeschlossen. Was auf einem Backup-Ziel liegt, öffnet weiter nur er; '
        . 'das bisherige Blatt bleibt in der Betriebsakte.',
        ['neu' => $neu, 'alt' => $alt, 'begonnen' => $z['begonnen'] ?? null,
         'zahlen' => $z['zahlen'] ?? []]);
    sw_mail('abschluss', $neu, $alt);
}

/** Die Mail an jede BetreiberIn — nur Kennungen, nie Werte (E-SR-22, E-SR-62). */
function sw_mail(string $phase, string $neu, string $alt): void
{
    try {
        require_once __DIR__ . '/mail_lib.php';
        foreach (mail_betreiberinnen() as $an) {
            mail_einreihen('serverschluessel_gewechselt', $an,
                ['phase' => $phase, 'neu' => $neu, 'alt' => $alt,
                 'link' => app_url('/betrieb_server.php#k-schluessel')]);
        }
    } catch (Throwable $ex) {
        /* Die Mail ist nicht der Vorgang — ein Wechsel, der an einer
         * Warteschlange scheitert, wäre schlechter als einer ohne Mail. */
        system_melden('schluesselwechsel', 'Mail nicht eingereiht', $ex);
    }
}

/**
 * „Jetzt weiterarbeiten": ein Häppchen mit dem Budget der Seite — ÜBER DEN
 * JOBRAHMEN, mit seiner Sperre (E-SR-65). Anders als „Jetzt sichern" bei den
 * Komplett-Ständen, das `komp_schub()` unmittelbar ruft: Zwei Umhüller
 * gleichzeitig räumten einander den Arbeitsordner leer.
 *
 * @return array der Bericht von `jobs_einen_lauf()`
 */
function sw_jetzt(float $budget = SW_BUDGET_SEITE): array
{
    require_once __DIR__ . '/jobs_lib.php';
    $start = microtime(true);
    $zeitLinks = static fn(): float => $budget - (microtime(true) - $start);
    return jobs_einen_lauf(SW_JOB, jobs_katalog()[SW_JOB], 'seite', $zeitLinks);
}
