<?php
declare(strict_types=1);
/**
 * Admin-Sicherungen: Ablage im Dateisystem, Übersicht, Rückspielung.
 *
 * Zweck (Block A8): Administration soll Konten sichern und wiederherstellen
 * können, OHNE Einblick in die Daten zu erhalten. Das Rohpaket entsteht
 * serverseitig über edbak_build() und behält `pat_blob` als Chiffretext; die
 * Entschlüsselung passiert wie überall sonst erst im Browser.
 *
 * WARUM DAS DATEISYSTEM UND NICHT DIE DATENBANK (E16)
 * Ein Paket liegt bei größeren Beständen im zweistelligen MB-Bereich, und
 * `max_allowed_packet` liegt auf geteiltem Webspace oft unveränderlich bei
 * 16 MB. Der zweite Grund wiegt schwerer: Eine Sicherung, die im selben
 * Behälter liegt wie das Gesicherte, ist keine Rückfallebene.
 *
 * ZWEI SCHRANKEN GEGEN DEN ABRUF ÜBER DEN BROWSER
 *   1. `sicherungen/.htaccess` mit `Require all denied`.
 *   2. Der Ordnername ist die zufällige Kontokennung (E17) und damit auch
 *      dann nicht zu erraten, wenn (1) nicht greift — anderer Webserver,
 *      .htaccess abgeschaltet. Dasselbe Muster wie bei der Nachweisdatei der
 *      Ersteinrichtung (`server/.htaccess`, M1-11).
 *
 * ZWINGEND: `sicherungen/` steht in der `exclude`-Liste von
 * `.github/workflows/deploy.yml`. Der FTP-Deploy synchronisiert `server/` und
 * löscht alles, was nicht ausgenommen ist — ohne den Eintrag wäre die erste
 * Auslieferung nach dieser Fassung zugleich die letzte aller Sicherungen.
 *
 * Aufbau der Ablage:
 *
 *   server/sicherungen/
 *     .htaccess
 *     <kontokennung>/
 *       konto.json                       Begleitdatei UND Verzeichnis
 *       2026-08-16T18-22-31Z_a1b2c3d4.json   ein Paket
 *
 * Die Begleitdatei hält Anzeigename und E-Mail-Adresse fest, damit die
 * Zuordnung eine Kontolöschung überlebt (A8.2) — genau dafür gibt es den
 * Abschnitt „verwaiste Sicherungen" in der Übersicht.
 */

require_once __DIR__ . '/backup_lib.php';

/**
 * Vorbelegung der Aufbewahrung je Konto (E18, seit Web 9.8.0 einstellbar).
 *
 * Bis Web 9.7.2 war die Zahl fest verdrahtet. Sie ist jetzt eine Regel der
 * Installation (`app_state.adminbackup_aufbewahrung`, Seite „Sicherungen").
 *
 * ZWEI STATT DREI (S2/AP6, Entscheidung vom 31.08.2026). Das Konzept nennt
 * seit E-S2-14 die Zwei; Code und drei Dokumente standen auf drei. Der
 * Widerspruch ist zugunsten des Konzepts aufgelöst.
 *
 * WAS DAS KOSTET, UND WER ES ZU SEHEN BEKOMMT: Eine Installation, die die
 * Einstellung nie angefasst hat, verliert beim nächsten Sichern je Konto den
 * ältesten von drei Ständen. Das geschieht nicht still — die Rückmeldung des
 * Laufs nennt jede verdrängte Datei, und das Handbuch sagt es an der Stelle,
 * an der die Zahl eingestellt wird. Wer drei behalten will, trägt drei ein;
 * die Einstellung gibt es seit Web 9.8.0.
 */
const EDBAK_AUFBEWAHRUNG_VORGABE = 2;

/** Vorbelegung der Erinnerung in Tagen, änderbar im Admin-Bereich (A8.4). */
const EDBAK_INTERVALL_VORGABE = 30;

/**
 * Wie viele Einträge in ein Fenster des Adminpakets gehen (S2/AP6).
 *
 * DIESELBE ZAHL WIE IM BROWSER, aus einem anderen Grund. Beim Nutzerformat
 * kommt die 250 von `client_max_body_size` — die Fenster gehen dort als POST
 * zurück. Hier geht nichts über die Leitung; hier zählt allein der Speicher.
 * Gemessen am 5000er-Bestand: ein Fenster von 250 Einträgen ist 0,44 MB, und
 * die Serverspitze bleibt damit weit unter dem Budget von 64 MB (Z3).
 *
 * Sie ist trotzdem dieselbe Zahl, und das ist Absicht: Zwei Fenstergrößen für
 * dasselbe Format wären zwei Zahlen, die auseinanderlaufen können, ohne dass
 * es jemandem auffällt.
 */
const EDBAK_ADMIN_FENSTER = 250;

/** Punkte je Spurteil — wie beim Nutzerformat (docs/Backup-Format.md 1.2). */
const EDBAK_ADMIN_TEIL_PUNKTE = 250000;

/** Kennungen je Anfrage an spur_fuer_sicherung_viele() — wie im Browser. */
const EDBAK_ADMIN_SPUR_BLOCK = 25;

/**
 * Präfix des Bauordners, in dem die Teile einer Sicherung entstehen (S2/AP6).
 *
 * Er ist ERKENNBAR, und das ist der Punkt: Bricht ein Lauf mitten im Bau ab,
 * bleibt ein solcher Ordner liegen. Vorher war das eine `.tmp`-Datei, die
 * durch jedes Sieb der Bibliothek fiel — unsichtbar in der Liste, nicht
 * löschbar, und `edbak_ordner_loeschen()` scheiterte dauerhaft an ihr, weil
 * sie nicht auf der Weißliste stand. Ein Konto liess sich danach nicht mehr
 * vollständig löschen, und die Meldung sagte nicht, woran es lag.
 */
const EDBAK_BAU_PRAEFIX = '.bau-';

/**
 * Vorgabe der Speichergrenze für `server/sicherungen/` in GB (E-S2-15).
 *
 * Zwei GB, weil das die Größenordnung ist, die ein einfacher Webspace
 * mitbringt — und weil eine Grenze, die nie greift, keine ist. Einstellbar im
 * Adminbereich; 0 heißt „nie gesetzt", dann gilt diese Vorgabe.
 */
const EDBAK_GRENZE_GB_VORGABE = 2;

/**
 * Vorgabe der Warnschwellen in Prozent der Grenze (E-S2-15).
 *
 * Zwei Schwellen und nicht eine: Die erste ist die Vorwarnung, bei der noch
 * Zeit zum Handeln bleibt; die zweite sagt, dass es jetzt eng wird. Je
 * überschrittener Schwelle geht **einmal** eine Meldung heraus, nicht bei
 * jedem Lauf — sonst wäre sie nach dem dritten Mal eine Mail, die niemand
 * mehr liest.
 */
const EDBAK_SCHWELLEN_VORGABE = '70,90';

/**
 * Wurzel der Ablage.
 *
 * KEINE Konfigurationsoption: Der Pfad muss mit dem `exclude`-Eintrag im
 * Deploy übereinstimmen, und eine Einstellung, die man ändern kann, ohne dass
 * der Deploy davon erfährt, ist genau die Art Falle, die erst beim nächsten
 * Ausliefern zuschnappt.
 */
function edbak_wurzel(): string
{
    return __DIR__ . '/sicherungen';
}

/**
 * Legt Wurzel und `.htaccess` an, falls sie fehlen.
 *
 * Die `.htaccess` wird bei JEDEM Schreibzugriff geprüft, nicht nur einmal:
 * Sie ist die erste Schranke, und eine erste Schranke, die stillschweigend
 * fehlen kann, ist keine.
 */
function edbak_ablage_bereit(): array
{
    /* OHNE ext/zip GIBT ES KEINE SICHERUNG (S2/AP6).
     *
     * Seit dem Umbau auf das mehrteilige Rohpaket ist ein Adminpaket ein ZIP.
     * Fehlt die Erweiterung, soll das HIER auffallen — an der Stelle, die
     * ohnehin sagt, ob gesichert werden kann —, und nicht mitten im ersten
     * Lauf als „liess sich nicht schreiben". `install.php` prüft sie seit
     * demselben Paket schon vor der Einrichtung. */
    if (!class_exists('ZipArchive')) {
        return [false, 'Der PHP-Erweiterung „zip" fehlt (ext/zip, Klasse '
                     . 'ZipArchive). Sicherungen sind ZIP-Dateien; ohne sie '
                     . 'lässt sich keine erzeugen. Bitte beim Hoster '
                     . 'freischalten lassen.'];
    }
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel) && !@mkdir($wurzel, 0770, true) && !is_dir($wurzel)) {
        return [false, 'Das Verzeichnis für Sicherungen lässt sich nicht anlegen ('
                     . $wurzel . '). Bitte Schreibrechte prüfen.'];
    }
    $ht = $wurzel . '/.htaccess';
    if (!is_file($ht)) {
        $inhalt = "# Sicherungen sind NIE über den Browser abrufbar.\n"
                . "# Zweite Schranke ist der Ordnername: Er ist die zufällige\n"
                . "# Kontokennung und nicht zu erraten (E17).\n"
                . "Require all denied\n"
                . "<IfModule !mod_authz_core.c>\n"
                . "  Order allow,deny\n"
                . "  Deny from all\n"
                . "</IfModule>\n";
        if (@file_put_contents($ht, $inhalt) === false) {
            return [false, 'Die Schutzdatei .htaccess lässt sich nicht schreiben. '
                         . 'Ohne sie wird nicht gesichert.'];
        }
    }
    if (!is_writable($wurzel)) {
        return [false, 'Das Verzeichnis für Sicherungen ist nicht beschreibbar ('
                     . $wurzel . ').'];
    }
    return [true, null];
}

/** Kennungen bestehen ausschliesslich aus 16 Hexziffern — alles andere ist kein Ordner von uns. */
function edbak_kennung_gueltig(?string $k): bool
{
    return is_string($k) && preg_match('/^[a-f0-9]{16}$/', $k) === 1;
}

function edbak_ordner(string $kennung): string
{
    return edbak_wurzel() . '/' . $kennung;
}

/**
 * Handgriff für die Oberfläche — steht dort ANSTELLE der Kontokennung.
 *
 * Akzeptanzkriterium 49 verlangt, dass die Kennung an keiner Stelle der
 * Oberfläche erscheint. Ein verborgenes Formularfeld ist zwar für Menschen
 * unsichtbar, steht aber im ausgelieferten Quelltext — und die Kennung ist die
 * ZWEITE SCHRANKE gegen den Abruf über den Browser (E17). Eine Schranke, die
 * man auf jeder Verwaltungsseite mitliest, ist keine.
 *
 * Der Handgriff ist die gekürzte Prüfsumme der Kennung. Er ist stabil (kein
 * Zustand in der Sitzung, mehrere Tabs stören sich nicht), sagt nichts über die
 * Kennung aus und braucht kein Geheimnis: Aus 16 Hexziffern zurückzurechnen
 * hiesse, 2^64 Möglichkeiten durchzuprobieren.
 *
 * Der DATEINAME wandert unverändert durch die Formulare — er ist Zeitstempel
 * plus Zufallsanteil und ohne den Ordner wertlos.
 */
function edbak_handgriff(string $kennung): string
{
    return substr(hash('sha256', 'edbak-handgriff|' . $kennung), 0, 16);
}

/** Rückweg: Handgriff -> Kennung, durch Vergleich über die vorhandenen Ordner. */
function edbak_kennung_aus_handgriff(string $handgriff): ?string
{
    if (!preg_match('/^[a-f0-9]{16}$/', $handgriff)) { return null; }
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return null; }
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        if (hash_equals(edbak_handgriff($n), $handgriff)) { return $n; }
    }
    /* Auch Konten OHNE Ordner müssen auflösbar sein: „Jetzt sichern" erzeugt
     * den Ordner ja erst. Die Konten sind wenige — ein Durchgang kostet
     * nichts. */
    foreach (db()->query('SELECT account_key FROM users')->fetchAll(PDO::FETCH_COLUMN) as $k) {
        if (edbak_kennung_gueltig($k) && hash_equals(edbak_handgriff((string)$k), $handgriff)) {
            return (string)$k;
        }
    }
    return null;
}

/**
 * Dateiname eines Pakets: Zeitpunkt (sortierbar) plus Zufallsanteil (E16).
 *
 * Seit S2/AP6 endet er auf `.zip` — ein Adminpaket ist ein Archiv aus Kopf,
 * Eintragsfenstern und Spurteilen. Die Endung ist zugleich die
 * Fassungserkennung: `.json` = die einteilige Fassung 1, `.zip` = Fassung 2.
 * Das kostet nichts und spart das Öffnen einer 94-MB-Datei, nur um zu
 * erfahren, was für eine sie ist.
 */
function edbak_paketname(): string
{
    return gmdate('Y-m-d\TH-i-s\Z') . '_' . bin2hex(random_bytes(4)) . '.zip';
}

/** Beide Fassungen — Fassung 1 wird noch gelesen, aber nicht mehr geschrieben. */
function edbak_paketname_gueltig(string $name): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z_[a-f0-9]{8}\.(json|zip)$/', $name) === 1;
}

/** Paketfassung am Namen: 2 = mehrteiliges ZIP, 1 = einteiliges JSON, 0 = keins. */
function edbak_paket_fassung(string $name): int
{
    if (!edbak_paketname_gueltig($name)) { return 0; }
    return str_ends_with($name, '.zip') ? 2 : 1;
}

/**
 * Begleitdatei lesen.
 *
 * Liefert IMMER ein Feld — auch wenn die Datei fehlt oder unlesbar ist. Der
 * Schlüssel 'lesbar' sagt, welcher der beiden Fälle vorliegt: Ein Ordner ohne
 * lesbare Begleitdatei wird in der Übersicht mit Hinweis aufgeführt und nicht
 * stillschweigend übergangen (Akzeptanzkriterium 48).
 */
function edbak_begleit_lesen(string $kennung): array
{
    $pfad = edbak_ordner($kennung) . '/konto.json';
    $vorgabe = ['lesbar' => false, 'email' => null, 'name' => null,
                'account_key' => $kennung, 'letzte_sicherung' => null,
                'sicherungen' => [], 'freigabe' => null];
    if (!is_file($pfad)) { return $vorgabe; }
    $roh = @file_get_contents($pfad);
    if ($roh === false) { return $vorgabe; }
    $d = json_decode($roh, true);
    if (!is_array($d)) { return $vorgabe; }
    return array_merge($vorgabe, $d, ['lesbar' => true, 'account_key' => $kennung]);
}

function edbak_begleit_schreiben(string $kennung, array $daten): bool
{
    $daten['account_key'] = $kennung;
    unset($daten['lesbar']);
    $pfad = edbak_ordner($kennung) . '/konto.json';
    /* Erst schreiben, dann umbenennen: Ein Abbruch mitten im Schreiben würde
     * sonst genau die Datei zerstören, die nach einer Kontolöschung die
     * einzige Zuordnung ist. */
    $tmp = $pfad . '.' . bin2hex(random_bytes(4)) . '.tmp';
    $ok = @file_put_contents($tmp, json_encode($daten,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    if ($ok === false) { @unlink($tmp); return false; }
    return @rename($tmp, $pfad);
}

/**
 * Pakete eines Ordners, neueste zuerst.
 *
 * Grundlage ist das VERZEICHNIS, nicht die Begleitdatei: Eine Begleitdatei,
 * die eine Datei nennt, die es nicht mehr gibt, darf keine Sicherung
 * vortäuschen — und umgekehrt darf eine vorhandene Datei nicht deshalb
 * unsichtbar bleiben, weil sie im Verzeichnis fehlt.
 */
function edbak_pakete(string $kennung): array
{
    $ordner = edbak_ordner($kennung);
    if (!is_dir($ordner)) { return []; }
    $begleit = edbak_begleit_lesen($kennung);
    $bekannt = [];
    foreach ($begleit['sicherungen'] ?? [] as $e) {
        if (isset($e['datei'])) { $bekannt[(string)$e['datei']] = $e; }
    }
    $liste = [];
    foreach (scandir($ordner) ?: [] as $name) {
        if (!edbak_paketname_gueltig($name)) { continue; }
        $pfad = $ordner . '/' . $name;
        $e = $bekannt[$name] ?? [];
        $liste[] = [
            'datei'   => $name,
            'fassung' => edbak_paket_fassung($name),
            'erzeugt' => $e['erzeugt'] ?? edbak_zeit_aus_name($name),
            'umfang'  => $e['umfang'] ?? null,
            'groesse' => (int)@filesize($pfad),
            'im_verzeichnis' => isset($bekannt[$name]),
        ];
    }
    usort($liste, static fn($a, $b) => strcmp($b['datei'], $a['datei']));
    return $liste;
}

/** Zeitpunkt aus dem Dateinamen — der Rückfall, wenn die Begleitdatei fehlt. */
function edbak_zeit_aus_name(string $name): ?string
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2})-(\d{2})-(\d{2})Z_/', $name, $m)) {
        return null;
    }
    return $m[1] . 'T' . $m[2] . ':' . $m[3] . ':' . $m[4] . 'Z';
}

/**
 * Sicherung erzeugen.
 *
 * Zusätzlich zum Ergebnis von edbak_build() werden `pat_wrap_rc` und
 * `pat_key_check` mitgesichert (E5). Ohne die Hülle liesse sich das Paket
 * NUR in dasselbe Konto zurückspielen — der Hauptanwendungsfall ist aber das
 * neu aufgesetzte Konto, und dort ist die Hülle der einzige Weg vom
 * Wiederherstellungsschlüssel zum alten Inhaltsschlüssel.
 *
 * ADMINISTRATION SIEHT DABEI KEINEN KLARTEXT (E6): Weder `pat_blob` noch
 * `pat_wrap_rc` sind ohne Kontopasswort bzw. Wiederherstellungsschlüssel
 * lesbar. `pat_wrap_rc` steht ausserdem schon heute in der Tabelle `users`,
 * auf die Administration ohnehin Zugriff hat — es entsteht kein neues Risiko.
 */
function edbak_sicherung_erzeugen(int $userId): array
{
    [$bereit, $grund] = edbak_ablage_bereit();
    if (!$bereit) { return [false, $grund, null]; }

    /* DIE GRENZE VOR DEM BAU (E-S2-14/15): abgelehnt mit Meldung, nie still
     * verdraengt. Beim 5000er-Konto kostet der Bau 14 Sekunden — die erst
     * auszugeben und das Ergebnis dann wegzuwerfen waere bei „Alle sichern"
     * derselbe Preis je Konto. */
    [$platz, $grundPlatz] = edbak_grenze_pruefen();
    if (!$platz) { return [false, $grundPlatz, null]; }

    $st = db()->prepare('SELECT id, email, name, account_key, pat_wrap_rc, pat_key_check
                         FROM users WHERE id = ?');
    $st->execute([$userId]);
    $u = $st->fetch();
    if (!$u) { return [false, 'Konto nicht gefunden.', null]; }
    if (!edbak_kennung_gueltig($u['account_key'])) {
        return [false, 'Diesem Konto fehlt die Kontokennung. Bitte zuerst die '
                     . 'Wartung aufrufen und die Migration ausführen.', null];
    }
    $kennung = (string)$u['account_key'];
    $ordner  = edbak_ordner($kennung);

    /* DER ORDNER ENTSTEHT ERST, WENN ES ETWAS HINEINZULEGEN GIBT.
     *
     * Bis Web 9.9.0 stand das mkdir hier oben, vor edbak_build(). Scheiterte
     * danach irgendetwas — kein Datenpaket, die Datei nicht schreibbar, eine
     * Speichergrenze mitten im Aufbau —, blieb ein LEERER Ordner ohne
     * Begleitdatei zurueck. Der ist kein Schoenheitsfehler: Die
     * NutzerInnen-Liste liest den Stand aus der Begleitdatei und meldete fuer
     * dieses Konto „Stand unbekannt", waehrend die Kontoseite (die Dateien
     * zaehlt) „nie gesichert" sagte. Zwei Seiten, zwei Antworten, beide aus
     * demselben Fehlschlag.
     *
     * edbak_build() braucht den Ordner nicht — es liefert eine Zeichenkette. */
    /* ---- DER BAU LÄUFT IN FENSTERN, NICHT AM STÜCK (S2/AP6) -----------
     *
     * Hier stand `json_decode(edbak_build($userId), true)`: der ganze Bestand
     * als Zeichenkette, derselbe Bestand noch einmal als Feld, und beim
     * Schreiben ein drittes Mal als Zeichenkette. Gemessen am 5000er-Konto:
     * 19,81 s, 94,28 MB Paket, **1077,6 MB** Spitze — und mit
     * `memory_limit=64M` (Z3) brach der Lauf in `spur_lib.php` ab. Auf genau
     * der Sorte Webspace, für die diese Anwendung gebaut ist, war die
     * Admin-Sicherung eines großen Kontos schlicht unmöglich.
     *
     * Jetzt derselbe Weg wie beim Nutzerformat seit Web 11.1.0: Kopf,
     * Eintragsfenster, Spurteile — nur unversiegelt, denn ein Adminpaket
     * liegt ohnehin serverseitig und trägt `pat_blob` als Chiffretext.
     *
     * WARUM ÜBER DATEIEN UND NICHT ÜBER addFromString(). `ZipArchive` hält
     * eine per `addFromString()` übergebene Zeichenkette bis zum `close()` im
     * Speicher — damit läge am Ende doch wieder alles gleichzeitig da.
     * Gemessen an 34,6 MB Inhalt, je eigener Prozess: `addFromString` 42,0 MB
     * Spitze, `addFile` **2,0 MB**. libzip liest die Datei erst beim
     * Schliessen und streamt sie. Die Teile gehen deshalb einzeln in einen
     * Bauordner und von dort ins Archiv.
     */
    $pdo = db();

    $kopfJson = edbak_build($userId, true, ['kopf' => true]);
    $kopf = json_decode($kopfJson, true);
    if (!is_array($kopf) || !isset($kopf['eintraege_gesamt'])) {
        return [false, 'Das Datenpaket liess sich nicht erzeugen.', null];
    }
    $gesamtEintraege = (int)$kopf['eintraege_gesamt'];

    $ordnerNeu = !is_dir($ordner);
    if ($ordnerNeu && !@mkdir($ordner, 0770, true) && !is_dir($ordner)) {
        return [false, 'Der Ordner für dieses Konto lässt sich nicht anlegen.', null];
    }

    /* Der Bauordner trägt ein erkennbares Präfix: `edbak_ordner_loeschen()`
     * und die Aufräumung räumen ihn mit, auch wenn ein Lauf mitten darin
     * abbricht (siehe edbak_baureste_aufraeumen()). */
    $bau = $ordner . '/' . EDBAK_BAU_PRAEFIX . bin2hex(random_bytes(4));
    if (!@mkdir($bau, 0770, true)) {
        if ($ordnerNeu) { @rmdir($ordner); }
        return [false, 'Der Bauordner lässt sich nicht anlegen.', null];
    }

    $teile = [];
    $bauNr = 0;
    /** Ein Teil: erst in den Bauordner, dann in die Teileliste. */
    $teilSchreiben = function (string $name, string $inhalt) use ($bau, &$teile, &$bauNr): bool {
        $datei = $bau . '/' . sprintf('%04d', ++$bauNr) . '.part';
        if (@file_put_contents($datei, $inhalt) === false) { return false; }
        $teile[] = ['name' => $name, 'datei' => $datei, 'bytes' => strlen($inhalt)];
        return true;
    };
    $abbruch = function (string $meldung) use ($bau, $ordner, $ordnerNeu): array {
        edbak_ordner_leeren($bau);
        @rmdir($bau);
        if ($ordnerNeu) { @rmdir($ordner); }
        return [false, $meldung, null];
    };

    if (!$teilSchreiben('kopf.json', $kopfJson)) {
        return $abbruch('Der Kopf der Sicherung liess sich nicht schreiben.');
    }
    unset($kopfJson);

    /* 'papierkorb' als Unterblock (E-S1-02). Seit Nutzlast 7 enthaelt jede
     * Sicherung den Papierkorb; die Zahl daneben ist die einzige Stelle, an
     * der das SICHTBAR wird, ohne die Datei zu oeffnen. */
    $imPapierkorb = static function (array $zeilen): int {
        $n = 0;
        foreach ($zeilen as $z) {
            if (is_array($z) && ($z['deleted_at'] ?? null) !== null) { $n++; }
        }
        return $n;
    };

    $umfang = [
        'einsaetze'  => 0,
        'diensttage' => count($kopf['days'] ?? []),
        'ruhezeiten' => 0,
        'papierkorb' => [
            'einsaetze'  => 0,
            'diensttage' => $imPapierkorb($kopf['days'] ?? []),
            'ruhezeiten' => 0,
        ],
    ];
    /* WIE VIELE EINSAETZE GESCHUETZTE ANGABEN TRAGEN (S2/AP6).
     *
     * Bis hierher hat `edbak_paket_hat_geschuetzte()` die Einsatzliste des
     * Pakets durchgesehen. Mit gefenstertem Kern steht sie dort nicht mehr —
     * die Funktion haette still `false` geliefert und damit die Sperre aus
     * E20 ausgehebelt: Ein Paket mit unlesbaren Angaben waere als „direkt
     * einspielbar" durchgegangen. Der Erzeuger zaehlt es deshalb hier und
     * schreibt es ins Manifest. */
    $geschuetzte = 0;
    $index = [];
    $eintragsteile = 0;

    for ($ab = 0; $ab < $gesamtEintraege; $ab += EDBAK_ADMIN_FENSTER) {
        $roh = edbak_build($userId, true, ['ab' => $ab, 'anzahl' => EDBAK_ADMIN_FENSTER]);
        $f = json_decode($roh, true);
        unset($roh);
        if (!is_array($f) || !isset($f['missions'], $f['rest_segments'])
            || !is_array($f['missions']) || !is_array($f['rest_segments'])) {
            return $abbruch('Ein Eintragsfenster der Sicherung ist unvollständig.');
        }
        /* NACHZAEHLEN, WAS ANGEKOMMEN IST — dieselbe Schranke wie im Browser
         * (S2/AP5b). Die Schleife rueckt um das Fenster weiter, gleichgueltig
         * wie viel zurueckkam; ein zu kurzes Fenster liesse Eintraege aus der
         * Sicherung fallen, und die Meldung lautete trotzdem „fertig". */
        $bekommen = count($f['missions']) + count($f['rest_segments']);
        $soll = min(EDBAK_ADMIN_FENSTER, $gesamtEintraege - $ab);
        if ($bekommen !== $soll) {
            return $abbruch('Das Eintragsfenster ab ' . $ab . ' lieferte ' . $bekommen
                          . ' statt ' . $soll . ' Einträgen. Es wurde nichts geschrieben.');
        }

        foreach ($f['_spur_index'] ?? [] as $e) { $index[] = $e; }
        unset($f['_spur_index']);          // Arbeitsfeld, gehört nicht in die Datei

        $umfang['einsaetze']  += count($f['missions']);
        $umfang['ruhezeiten'] += count($f['rest_segments']);
        $umfang['papierkorb']['einsaetze']  += $imPapierkorb($f['missions']);
        $umfang['papierkorb']['ruhezeiten'] += $imPapierkorb($f['rest_segments']);
        foreach ($f['missions'] as $m) {
            if (is_array($m) && ($m['pat_blob'] ?? null) !== null && $m['pat_blob'] !== '') {
                $geschuetzte++;
            }
        }

        $eintragsteile++;
        $name = 'eintraege/' . sprintf('%04d', $eintragsteile) . '.json';
        if (!$teilSchreiben($name, (string)json_encode($f,
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
            return $abbruch('Ein Eintragsfenster liess sich nicht schreiben.');
        }
        unset($f);
    }

    /* ---- Die Spuren, an Spurgrenzen geschnitten ------------------------ */
    $spurteile = 0; $spurenGesamt = 0; $punkteGesamt = 0;
    $abgelehnt = [];
    $laufend = []; $laufendePunkte = 0;
    $spurteilSchreiben = function (array $eintraege) use (&$spurteile, $teilSchreiben): bool {
        if (!$eintraege) { return true; }
        $spurteile++;
        return $teilSchreiben('spuren/' . sprintf('%04d', $spurteile) . '.json',
            (string)json_encode(['spuren' => $eintraege],
                JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    };

    foreach (['mission', 'rest'] as $art) {
        $dieser = array_values(array_filter($index, static fn($e) => ($e['art'] ?? '') === $art));
        for ($k = 0; $k < count($dieser); $k += EDBAK_ADMIN_SPUR_BLOCK) {
            $block = array_slice($dieser, $k, EDBAK_ADMIN_SPUR_BLOCK);
            $refNach = [];
            foreach ($block as $e) { $refNach[(int)$e['id']] = $e['spur_ref']; }
            foreach (spur_fuer_sicherung_viele($pdo, $art, array_keys($refNach)) as $id => $sp) {
                if (!empty($sp['leer'])) { continue; }
                if (!empty($sp['fehler'])) {
                    $abgelehnt[] = $art . ' ' . $id . ': ' . ($sp['grund'] ?? 'unbekannt');
                    continue;
                }
                if ($laufend && $laufendePunkte + (int)$sp['n'] > EDBAK_ADMIN_TEIL_PUNKTE) {
                    if (!$spurteilSchreiben($laufend)) {
                        return $abbruch('Ein Spurteil liess sich nicht schreiben.');
                    }
                    $laufend = []; $laufendePunkte = 0;
                }
                $laufend[] = [
                    'spur_ref'   => $refNach[$id],
                    'stufe'      => (int)$sp['stufe'],
                    'n_original' => (int)$sp['n_original'],
                    'n'          => (int)$sp['n'],
                    'blob'       => base64_encode($sp['blob']),
                ];
                $laufendePunkte += (int)$sp['n'];
                $spurenGesamt++; $punkteGesamt += (int)$sp['n'];
            }
        }
    }
    if (!$spurteilSchreiben($laufend)) {
        return $abbruch('Ein Spurteil liess sich nicht schreiben.');
    }
    unset($laufend, $index);

    /* ---- Das Manifest zuletzt: es kennt dann alle Zahlen --------------- */
    $erzeugt = gmdate('Y-m-d\TH:i:s\Z');
    $manifest = [
        'format'      => 'einsatzdoku-adminsicherung',
        'version'     => 2,
        'erzeugt'     => $erzeugt,
        'web_version' => WEB_VERSION,
        'konto'       => [
            'account_key' => $kennung,
            'email'       => (string)$u['email'],
            'name'        => $u['name'],
        ],
        /* Die beiden Hüllen liegen BEWUSST neben den Daten und nicht in
         * ihnen: der Kern beschreibt den Bestand, diese beiden Werte
         * beschreiben den Schlüssel dazu. */
        'schluessel'  => [
            'pat_wrap_rc'   => $u['pat_wrap_rc'],
            'pat_key_check' => $u['pat_key_check'],
        ],
        'umfang'        => $umfang,
        'nutzlast'      => 8,
        'eintraege'     => $gesamtEintraege,
        'eintragsteile' => $eintragsteile,
        'spurteile'     => $spurteile,
        'spuren'        => $spurenGesamt,
        'punkte'        => $punkteGesamt,
        'geschuetzte'   => $geschuetzte,
        'teile'         => array_map(static fn($t) => $t['name'], $teile),
        'abgelehnt'     => $abgelehnt,
    ];
    if (!$teilSchreiben('manifest.json', (string)json_encode($manifest,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES))) {
        return $abbruch('Das Manifest liess sich nicht schreiben.');
    }

    /* ---- Und alles in ein Archiv --------------------------------------- */
    $name = edbak_paketname();
    $tmp  = $ordner . '/' . $name . '.tmp';
    $zip  = new ZipArchive();
    if ($zip->open($tmp, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return $abbruch('Die Sicherungsdatei lässt sich nicht anlegen.');
    }
    foreach ($teile as $t) {
        /* GEPACKT, anders als beim Nutzerformat: Dort sind die Teile bereits
         * gzip UND verschlüsselt, hier ist es blankes JSON. Der Packlauf
         * lohnt sich also — er ist der Grund, aus dem ein Adminpaket trotz
         * derselben Daten kleiner ausfällt als früher. */
        if (!$zip->addFile($t['datei'], $t['name'])) {
            @$zip->close();
            @unlink($tmp);
            return $abbruch('Ein Teil liess sich nicht in die Sicherung legen.');
        }
    }
    if (!$zip->close()) {
        @unlink($tmp);
        return $abbruch('Die Sicherung liess sich nicht abschliessen.');
    }
    edbak_ordner_leeren($bau);
    @rmdir($bau);

    if (!@rename($tmp, $ordner . '/' . $name)) {
        @unlink($tmp);
        if ($ordnerNeu) { @rmdir($ordner); }
        return [false, 'Die Sicherung liess sich nicht ablegen.', null];
    }

    /* ---- ERST GEGENLESEN, DANN AUFRAEUMEN (S2/AP6) ---------------------
     *
     * Die Verdraengung entfernt aeltere Staende, und mit der Umstellung
     * entfernt sie zusaetzlich die einteiligen Pakete dieses Kontos
     * (Entscheidung vom 31.08.2026). Beides sind Loeschungen, die auf das
     * Gelingen DIESES Laufs vertrauen — und ein ZIP, das sich nicht oeffnen
     * laesst, ist genau der Fall, in dem man den alten Stand noch braucht.
     *
     * Das frische Paket wird deshalb gelesen, bevor irgendetwas verschwindet.
     * Schlaegt das fehl, bleibt die Datei liegen (sie ist nicht kaputt, nur
     * unbrauchbar — wer sie ansieht, soll sie finden), und der Lauf meldet
     * einen Fehler, ohne den Bestand angetastet zu haben. */
    $gegenprobe = edbak_paket_kopf_lesen($kennung, $name);
    if ($gegenprobe === null || ($gegenprobe['version'] ?? 0) !== 2) {
        return [false, 'Die Sicherung wurde geschrieben, liess sich danach aber '
                     . 'nicht lesen. Der bisherige Bestand ist unangetastet '
                     . 'geblieben; die fragliche Datei heisst ' . $name . '.', null];
    }

    $begleit = edbak_begleit_lesen($kennung);
    $begleit['email'] = (string)$u['email'];
    $begleit['name']  = $u['name'];
    $begleit['letzte_sicherung'] = $erzeugt;
    $begleit['sicherungen'][] = [
        'datei'   => $name,
        'erzeugt' => $erzeugt,
        'umfang'  => $umfang,
    ];
    if (!edbak_begleit_schreiben($kennung, $begleit)) {
        /* KEIN STILLES WEITERGEHEN (S2/AP6). Der Rueckgabewert wurde hier
         * verworfen. Die Sicherung liegt dann zwar, aber das Verzeichnis
         * kennt sie nicht — und `edbak_pakete()` faellt fuer sie auf den
         * Zeitstempel im Namen zurueck, ohne Umfang. Das ist kein
         * Datenverlust, aber eine Auskunft, die fehlt. */
        return [true, 'Die Sicherung liegt, aber das Verzeichnis des Kontos '
                    . 'liess sich nicht schreiben. Umfang und Zeitpunkt fehlen '
                    . 'deshalb in der Liste.',
                ['datei' => $name, 'umfang' => $umfang, 'verdraengt' => [],
                 'abgelehnt' => $abgelehnt]];
    }

    $verdraengt = edbak_verdraengen($kennung);
    edbak_marke_setzen('adminbackup_last', gmdate('Y-m-d'));
    edbak_ablage_zahlen(true);      // der Stand hat sich gerade geaendert

    return [true, null, ['datei' => $name, 'umfang' => $umfang,
                         'verdraengt' => $verdraengt, 'abgelehnt' => $abgelehnt,
                         'spuren' => $spurenGesamt, 'punkte' => $punkteGesamt,
                         'teile' => 1 + $eintragsteile + $spurteile]];
}

/**
 * Älteste Sicherungen entfernen, bis höchstens n übrig sind (E18).
 *
 * KEINE ALTERSGRENZE. Bei rein manueller Auslösung würde eine Altersgrenze
 * genau die letzte vorhandene Sicherung entfernen, wenn lange keine neue
 * erzeugt wurde — also in der Lage, in der man sie braucht. Die Anzahlgrenze
 * greift nur, wenn tatsächlich eine neuere existiert.
 *
 * Das ist die zugesagte Verdrängung und KEINE Löschhandlung im Sinne von
 * A8.8: Sie braucht deshalb keine Bestätigung (Akzeptanzkriterium 60).
 *
 * ZWEI PAKETE SIND AUSGENOMMEN (E-P3-41, Web 9.8.0). Die Zahl ist seit der
 * einstellbaren Aufbewahrung nicht mehr fest, und wer sie auf 1 stellt,
 * hätte sonst zwei Zusagen gebrochen:
 *
 *   das JÜNGSTE Paket, weil `array_slice` bei n = 0 den ganzen Bestand
 *   entfernen würde — eine Sicherung, die beim Sichern alles wegräumt, ist
 *   das Gegenteil der Funktion;
 *
 *   ein FREIGEGEBENES Paket, weil die NutzerIn es im eigenen Backup-Bereich
 *   angeboten bekommt. Es unter ihr wegzuräumen hiesse, ihr einen Weg
 *   anzubieten, der beim Klick ins Leere läuft — derselbe Grund, aus dem
 *   edbak_verzeichnis_abgleichen() eine gegenstandslose Freigabe löscht.
 */
function edbak_verdraengen(string $kennung): array
{
    $pakete = edbak_pakete($kennung);          // neueste zuerst
    $grenze = max(1, edbak_aufbewahrung());
    $weg = array_slice($pakete, $grenze);

    /* DIE EINTEILIGEN PAKETE GEHEN MIT (S2/AP6, Entscheidung vom 31.08.2026).
     *
     * Fassung 1 ist eine einzige JSON-Datei mit dem ganzen Bestand darin —
     * beim 5000er-Konto 94,28 MB gegen 11,42 MB derselben Daten als
     * Fassung 2. Sie liegen zu lassen hiesse, den Platz doppelt zu belegen,
     * und zwar dauerhaft: Bei einer Aufbewahrung von 2 raeumt die Grenze oben
     * sie zwar irgendwann weg, aber erst nach zwei weiteren Laeufen.
     *
     * ES IST EINE LOESCHUNG UND WIRD ALS SOLCHE GEMELDET. Der Aufrufer
     * bekommt die Namen zurueck und nennt sie; still verschwindet hier
     * nichts. Und sie geschieht ERST, nachdem das neue Paket geschrieben UND
     * wieder gelesen wurde — die Gegenprobe steht in
     * edbak_sicherung_erzeugen(), unmittelbar vor dem Aufruf dieser Funktion.
     *
     * WARUM UEBERHAUPT UND NICHT „liegen lassen": Ein Format, das niemand
     * mehr schreibt, wird auch von niemandem mehr geprueft. Ein alter Stand,
     * den man im Ernstfall braucht und der sich dann nicht oeffnen laesst,
     * ist schlimmer als keiner. */
    /* DAS JUENGSTE PAKET BLEIBT — auch wenn es Fassung 1 ist.
     *
     * Die Regel gab es schon (array_slice ab $grenze >= 1 laesst es stehen);
     * die Zeile hier haette sie ausgehebelt. Wird diese Funktion einmal aus
     * einem anderen Zusammenhang gerufen als unmittelbar nach einem
     * erfolgreichen Lauf, waere der einzige vorhandene Stand eines Kontos
     * weg — eine Aufraeumung, die aufraeumt, bis nichts mehr da ist. */
    foreach (array_slice($pakete, 1, max(0, $grenze - 1)) as $p) {
        if ((int)($p['fassung'] ?? 1) === 1) { $weg[] = $p; }
    }

    if (!$weg) { return []; }
    $begleit  = edbak_begleit_lesen($kennung);
    /* NUR EINE OFFENE FREIGABE SCHONT. Der Grund der Ausnahme ist, dass die
     * NutzerIn das Paket im eigenen Backup-Bereich angeboten bekommt — und
     * genau das hoert mit dem Einloesen auf: edbak_freigabe_fuer() ueberspringt
     * eine eingeloeste Freigabe. Ohne diese Pruefung waere ein einmal
     * freigegebenes Paket dauerhaft von der Verdraengung ausgenommen, und die
     * eingestellte Aufbewahrung wuerde still ueberschritten. */
    $f = $begleit['freigabe'] ?? null;
    $freigabe = (is_array($f) && empty($f['eingeloest']))
        ? (string)($f['datei'] ?? '') : '';
    $namen = [];
    foreach ($weg as $p) {
        if ($p['datei'] === $freigabe) { continue; }
        if (@unlink(edbak_ordner($kennung) . '/' . $p['datei'])) { $namen[] = $p['datei']; }
    }
    edbak_verzeichnis_abgleichen($kennung);
    return $namen;
}

/** Verzeichnis in der Begleitdatei auf die tatsächlich vorhandenen Dateien zurückführen. */
function edbak_verzeichnis_abgleichen(string $kennung): void
{
    $begleit = edbak_begleit_lesen($kennung);
    $da = [];
    foreach (scandir(edbak_ordner($kennung)) ?: [] as $n) {
        if (edbak_paketname_gueltig($n)) { $da[$n] = true; }
    }
    $neu = [];
    foreach ($begleit['sicherungen'] ?? [] as $e) {
        if (isset($e['datei'], $da[$e['datei']])) { $neu[] = $e; }
    }
    $begleit['sicherungen'] = $neu;
    $begleit['letzte_sicherung'] = $neu ? end($neu)['erzeugt'] : null;
    /* Eine Freigabe auf eine nicht mehr vorhandene Datei ist gegenstandslos —
     * sie stehen zu lassen hiesse, der NutzerIn etwas anzubieten, das beim
     * Klick ins Leere läuft. */
    if (isset($begleit['freigabe']['datei']) && !isset($da[$begleit['freigabe']['datei']])) {
        $begleit['freigabe'] = null;
    }
    edbak_begleit_schreiben($kennung, $begleit);
}

/** Ein Paket lesen und formal prüfen. */
/** Alle Dateien eines Verzeichnisses entfernen (eine Ebene, ohne Rekursion). */
function edbak_ordner_leeren(string $pfad): bool
{
    if (!is_dir($pfad)) { return true; }
    $ok = true;
    foreach (scandir($pfad) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        $voll = $pfad . '/' . $n;
        if (is_dir($voll)) { $ok = edbak_ordner_leeren($voll) && @rmdir($voll) && $ok; }
        else { $ok = @unlink($voll) && $ok; }
    }
    return $ok;
}

/**
 * Liegengebliebene Bauordner und `.tmp`-Dateien eines Kontos entfernen (S2/AP6).
 *
 * WOFUER. Ein Lauf, der mitten im Bau abbricht — Zeitüberschreitung, Absturz,
 * volle Platte —, lässt Reste liegen. Sie zählen auf der Platte, tauchen in
 * keiner Liste auf und blockierten bis Web 11.2.0 dauerhaft das Löschen des
 * Ordners und damit einen Teil der Kontolöschung.
 *
 * SIE WERDEN NICHT NACH ALTER GEFILTERT, sondern nach Zugehörigkeit: Was hier
 * entfernt wird, trägt das Präfix bzw. die Endung, die nur diese Bibliothek
 * vergibt. Ein paralleler Lauf im selben Konto ist ausgeschlossen — die
 * Sicherung eines Kontos läuft in einer Anfrage, und der Joblauf sperrt.
 */
function edbak_baureste_aufraeumen(string $kennung): int
{
    if (!edbak_kennung_gueltig($kennung)) { return 0; }
    $ordner = edbak_ordner($kennung);
    if (!is_dir($ordner)) { return 0; }
    $weg = 0;
    foreach (scandir($ordner) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        $voll = $ordner . '/' . $n;
        if (is_dir($voll) && str_starts_with($n, EDBAK_BAU_PRAEFIX)) {
            edbak_ordner_leeren($voll);
            if (@rmdir($voll)) { $weg++; }
            continue;
        }
        if (is_file($voll) && str_ends_with($n, '.tmp')) {
            if (@unlink($voll)) { $weg++; }
        }
    }
    return $weg;
}

/**
 * Der KOPF eines Pakets — ohne den Bestand zu laden.
 *
 * Fassung 2 (ZIP): das Manifest, ein paar Kilobyte.
 * Fassung 1 (JSON): die Datei muss geöffnet werden, weil der Umschlag und der
 * Bestand in derselben Struktur liegen; `daten` wird danach sofort entfernt.
 * Das ist teuer und einer der Gründe, aus denen Fassung 1 nicht bleibt.
 */
function edbak_paket_kopf_lesen(string $kennung, string $datei): ?array
{
    if (!edbak_kennung_gueltig($kennung) || !edbak_paketname_gueltig($datei)) { return null; }
    $pfad = edbak_ordner($kennung) . '/' . $datei;
    if (!is_file($pfad)) { return null; }

    if (edbak_paket_fassung($datei) === 2) {
        if (!class_exists('ZipArchive')) { return null; }
        $zip = new ZipArchive();
        if ($zip->open($pfad) !== true) { return null; }
        $roh = $zip->getFromName('manifest.json');
        $zip->close();
        if ($roh === false) { return null; }
        $d = json_decode($roh, true);
        if (!is_array($d) || ($d['format'] ?? '') !== 'einsatzdoku-adminsicherung') { return null; }
        return $d;
    }

    $roh = @file_get_contents($pfad);
    if ($roh === false) { return null; }
    $d = json_decode($roh, true);
    if (!is_array($d) || ($d['format'] ?? '') !== 'einsatzdoku-adminsicherung') { return null; }
    /* Fassung 1 trägt den Bestand im selben Feld. Er wird hier entfernt: Wer
     * den Kopf liest, will den Bestand nicht — und ihn zurückzugeben hiesse,
     * 94 MB durch jede aufrufende Stelle zu tragen. */
    unset($d['daten']);
    $d['version'] = (int)($d['version'] ?? 1);
    return $d;
}

/**
 * Ein Paket vollständig lesen — NUR für Fassung 1.
 *
 * Für Fassung 2 gibt es diesen Weg bewusst nicht: Ein mehrteiliges Paket am
 * Stück in den Speicher zu holen wäre genau die Spitze, die der Umbau
 * beseitigt hat, nur auf der Leseseite. Wer ein Fassung-2-Paket einspielen
 * will, nimmt `edbak_paket_einspielen()`; wer nur die Zahlen braucht,
 * `edbak_paket_kopf_lesen()`.
 */
function edbak_paket_lesen(string $kennung, string $datei): ?array
{
    if (!edbak_kennung_gueltig($kennung) || !edbak_paketname_gueltig($datei)) { return null; }
    if (edbak_paket_fassung($datei) !== 1) { return null; }
    $pfad = edbak_ordner($kennung) . '/' . $datei;
    if (!is_file($pfad)) { return null; }
    $roh = @file_get_contents($pfad);
    if ($roh === false) { return null; }
    $d = json_decode($roh, true);
    if (!is_array($d) || ($d['format'] ?? '') !== 'einsatzdoku-adminsicherung') { return null; }
    return $d;
}

/** Enthält das Paket überhaupt geschützte Angaben? Entscheidet den Sonderfall in A8.6. */
function edbak_paket_hat_geschuetzte(array $paket): bool
{
    /* FASSUNG 2 SAGT ES SELBST (S2/AP6).
     *
     * Bis hierher wurde die Einsatzliste des Pakets durchgesehen. Bei einem
     * gefensterten Kern steht sie dort nicht mehr — die Funktion haette still
     * `false` geliefert, und damit waere die Sperre aus E20 weggefallen: Ein
     * Paket mit unlesbaren Angaben ginge als „direkt einspielbar" durch, und
     * die geschuetzten Angaben kaemen im Zielkonto als Chiffretext an, den
     * dort niemand oeffnen kann.
     *
     * Der Erzeuger zaehlt es beim Bau und schreibt `geschuetzte` ins
     * Manifest. Fehlt der Schluessel bei einem Fassung-2-Paket, wird
     * VORSICHTIG entschieden — „nicht erhoben" ist etwas anderes als „keine",
     * und der teurere der beiden Wege ist hier der sichere. */
    if ((int)($paket['version'] ?? 1) >= 2) {
        return !array_key_exists('geschuetzte', $paket)
            || (int)$paket['geschuetzte'] > 0;
    }
    foreach ($paket['daten']['missions'] ?? [] as $m) {
        if (!empty($m['pat_blob'])) { return true; }
    }
    return false;
}

/**
 * Welcher Weg ist für dieses Paket und dieses Zielkonto zulässig? (E20)
 *
 * Liefert 'direkt', 'freigabe' oder 'gesperrt', dazu die Begründung im
 * Klartext. Die Regel ist maschinell prüfbar und braucht keine Einschätzung
 * im Einzelfall — genau das war der Grund, sie an den Kennungen festzumachen
 * und nicht am Gefühl der handelnden Person.
 */
function edbak_weg(array $paket, array $ziel): array
{
    $herkunft = (string)($paket['konto']['account_key'] ?? '');
    $zielKey  = (string)($ziel['account_key'] ?? '');

    if ($herkunft !== '' && $zielKey !== '' && $herkunft === $zielKey) {
        return ['direkt', 'Dasselbe Konto besteht weiter — die geschützten Angaben '
                        . 'sind mit dem unveränderten Inhaltsschlüssel verschlüsselt '
                        . 'und bleiben lesbar.'];
    }

    /* SONDERFALL, ausdrücklich benannt statt stillschweigend behandelt (P6).
     *
     * Die Sperre aus E20 besteht, WEIL die geschützten Angaben mit einem
     * Inhaltsschlüssel verschlüsselt sind, den nur der
     * Wiederherstellungsschlüssel öffnet. Enthält das Paket überhaupt keine
     * geschützten Angaben, gibt es nichts umzuschlüsseln — die Begründung
     * trifft dann nicht zu, und ein Umweg über die NutzerIn wäre eine Hürde
     * ohne Zweck. Das betrifft insbesondere Konten mit `pat_wrap_rc IS NULL`:
     * ein Konto vor der Erstvergabe des Passworts hat gar keinen
     * Inhaltsschlüssel.
     */
    if (!edbak_paket_hat_geschuetzte($paket)) {
        return ['direkt', 'Das Paket enthält keine geschützten Angaben — es gibt '
                        . 'nichts umzuschlüsseln, ein Umweg über die NutzerIn '
                        . 'hätte keinen Zweck.'];
    }

    if (empty($paket['schluessel']['pat_wrap_rc'])) {
        return ['gesperrt', 'Das Paket enthält geschützte Angaben, aber keine '
                          . 'Wiederherstellungs-Hülle. Der zugehörige Inhaltsschlüssel '
                          . 'lässt sich damit von niemandem mehr öffnen — auch nicht '
                          . 'von der NutzerIn. Ein Einspielen erzeugte unlesbare Einträge.'];
    }

    return ['freigabe', 'Das Konto wurde neu aufgesetzt. Die geschützten Angaben sind '
                      . 'mit einem Inhaltsschlüssel verschlüsselt, den nur der '
                      . 'Wiederherstellungsschlüssel öffnet — und der liegt '
                      . 'ausschliesslich bei der NutzerIn.'];
}

/**
 * Übersicht: bestehende Konten und verwaiste Ordner (E19).
 *
 * Die Liste der Ordner entsteht aus dem VERZEICHNIS, nicht aus der Datenbank.
 * Eine Liste allein aus `users` würde genau die Sicherungen verschweigen, um
 * derentwillen die Funktion gebaut wird: Das neu aufgesetzte Konto trägt eine
 * neue Kennung, zum alten Ordner existiert keine Datenbankzeile mehr.
 */
function edbak_uebersicht(): array
{
    $konten = db()->query('SELECT id, email, name, account_key, pat_wrap_rc
                           FROM users ORDER BY email')->fetchAll();
    $nachKennung = [];
    foreach ($konten as $k) {
        if (edbak_kennung_gueltig($k['account_key'])) {
            $nachKennung[(string)$k['account_key']] = $k;
        }
    }

    $wurzel = edbak_wurzel();
    $ordner = [];
    if (is_dir($wurzel)) {
        foreach (scandir($wurzel) ?: [] as $n) {
            if (edbak_kennung_gueltig($n) && is_dir($wurzel . '/' . $n)) { $ordner[$n] = true; }
        }
    }

    $mitKonto = [];
    foreach ($konten as $k) {
        $kennung = (string)($k['account_key'] ?? '');
        $hat = edbak_kennung_gueltig($kennung) && isset($ordner[$kennung]);
        $begleit = $hat ? edbak_begleit_lesen($kennung) : null;
        $mitKonto[] = [
            'user_id'     => (int)$k['id'],
            'email'       => (string)$k['email'],
            'name'        => $k['name'],
            'account_key' => $kennung,
            'kennung_ok'  => edbak_kennung_gueltig($kennung),
            'pakete'      => $hat ? edbak_pakete($kennung) : [],
            'freigabe'    => $begleit['freigabe'] ?? null,
        ];
    }

    $verwaist = [];
    foreach (array_keys($ordner) as $kennung) {
        if (isset($nachKennung[$kennung])) { continue; }
        $begleit = edbak_begleit_lesen($kennung);
        $verwaist[] = [
            'account_key' => $kennung,
            'lesbar'      => (bool)$begleit['lesbar'],
            'email'       => $begleit['email'],
            'name'        => $begleit['name'],
            'pakete'      => edbak_pakete($kennung),
            'freigabe'    => $begleit['freigabe'] ?? null,
        ];
    }

    return ['konten' => $mitKonto, 'verwaist' => $verwaist];
}

/** Freigabe setzen (A8.6): die Sicherung wird für ein Zielkonto sichtbar. */
function edbak_freigeben(string $kennung, string $datei, int $zielUserId): bool
{
    if (!edbak_paketname_gueltig($datei)) { return false; }
    $begleit = edbak_begleit_lesen($kennung);
    $begleit['freigabe'] = [
        'datei'       => $datei,
        'ziel_user'   => $zielUserId,
        'erstellt'    => gmdate('Y-m-d\TH:i:s\Z'),
        'eingeloest'  => null,
    ];
    return edbak_begleit_schreiben($kennung, $begleit);
}

/** Freigabe widerrufen, solange sie nicht eingelöst wurde (Akzeptanzkriterium 53). */
function edbak_freigabe_widerrufen(string $kennung): bool
{
    $begleit = edbak_begleit_lesen($kennung);
    $begleit['freigabe'] = null;
    return edbak_begleit_schreiben($kennung, $begleit);
}

/**
 * Die für ein Konto freigegebene Sicherung — oder null.
 *
 * Durchsucht alle Ordner, nicht nur den eigenen: Der Anwendungsfall ist gerade
 * der, dass die Sicherung in einem FREMDEN (verwaisten) Ordner liegt, weil das
 * Konto neu aufgesetzt wurde.
 */
function edbak_freigabe_fuer(int $userId): ?array
{
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return null; }
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        $begleit = edbak_begleit_lesen($n);
        $f = $begleit['freigabe'] ?? null;
        if (!is_array($f) || (int)($f['ziel_user'] ?? 0) !== $userId) { continue; }
        if (!empty($f['eingeloest'])) { continue; }
        if (!isset($f['datei']) || !is_file(edbak_ordner($n) . '/' . $f['datei'])) { continue; }
        return ['account_key' => $n, 'datei' => (string)$f['datei'],
                'erstellt' => $f['erstellt'] ?? null,
                'herkunft_email' => $begleit['email'], 'herkunft_name' => $begleit['name']];
    }
    return null;
}

/** Freigabe als eingelöst vermerken. */
function edbak_freigabe_eingeloest(string $kennung): void
{
    $begleit = edbak_begleit_lesen($kennung);
    if (isset($begleit['freigabe']) && is_array($begleit['freigabe'])) {
        $begleit['freigabe']['eingeloest'] = gmdate('Y-m-d\TH:i:s\Z');
        edbak_begleit_schreiben($kennung, $begleit);
    }
}

/** Eine einzelne Sicherung löschen (A8.8). Wirkt sofort und endgültig (E23). */
function edbak_paket_loeschen(string $kennung, string $datei): bool
{
    if (!edbak_kennung_gueltig($kennung) || !edbak_paketname_gueltig($datei)) { return false; }
    $ok = @unlink(edbak_ordner($kennung) . '/' . $datei);
    if ($ok) { edbak_verzeichnis_abgleichen($kennung); }
    return $ok;
}

/** Einen ganzen Kontoordner löschen — der einzige Weg, einen verwaisten loszuwerden. */
function edbak_ordner_loeschen(string $kennung): bool
{
    if (!edbak_kennung_gueltig($kennung)) { return false; }
    $ordner = edbak_ordner($kennung);
    if (!is_dir($ordner)) { return true; }

    /* ERST DIE EIGENEN RESTE (S2/AP6). Ein abgebrochener Lauf hinterlaesst
     * einen Bauordner oder eine `.tmp`-Datei. Beide standen nicht auf der
     * Weissliste unten — und damit scheiterte das Loeschen des Kontoordners
     * dauerhaft an einem Rest, den die Anwendung selbst erzeugt hatte. */
    edbak_baureste_aufraeumen($kennung);

    /* ERST PRUEFEN, DANN LOESCHEN (S2/AP6).
     *
     * Die Pruefung stand IN der Schleife: Beim fuenften Eintrag abzubrechen
     * hiess, die ersten vier bereits geloescht zu haben — und dann `false` zu
     * melden. Der Aufrufer sah „nicht geloescht" und hatte trotzdem einen
     * halb ausgeraeumten Ordner vor sich. Jetzt entscheidet ein erster
     * Durchgang, ob ueberhaupt etwas geloescht wird. */
    $eigene = [];
    foreach (scandir($ordner) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        /* Nur, was wir selbst anlegen. Ein fremder Eintrag im Ordner waere ein
         * Befund und kein Grund, blind zu loeschen. */
        if ($n !== 'konto.json' && !edbak_paketname_gueltig($n)) { return false; }
        $eigene[] = $ordner . '/' . $n;
    }
    foreach ($eigene as $pfad) { @unlink($pfad); }
    return @rmdir($ordner);
}

/** Alle Sicherungen eines Kontos entfernen (E25, Kontolöschung). */
function edbak_konto_ordner_loeschen(?string $kennung): bool
{
    return edbak_kennung_gueltig($kennung) ? edbak_ordner_loeschen((string)$kennung) : true;
}

/* ---- Erinnerung (A8.4) ---------------------------------------------------
 *
 * Intervall und Zeitpunkt der letzten Sicherung passen in die vorhandene
 * Tabelle app_state; dafür braucht es keine neue Struktur. Die Anzeige folgt
 * dem Muster der Wartungswarnung in update.php: erst sagen, was ist, dann was
 * daraus folgt.
 */
/* JE ANFRAGE EINMAL LESEN (O9b). Die Marken sind Einstellungen, keine
 * Messwerte: Innerhalb eines Seitenaufrufs ändern sie sich nur, wenn diese
 * Anfrage sie selbst ändert — und dann schreibt edbak_marke_setzen() den neuen
 * Wert gleich mit in den Zwischenspeicher.
 *
 * DER GRUND IST GEMESSEN. Die NutzerInnen-Liste wertet je Zeile einen
 * Sicherungsstand und braucht dafür das Erinnerungsintervall. Ohne
 * Zwischenspeicher waren das bei 304 Konten 304 Abfragen und 27,7 ms für eine
 * Rechnung, die aus einer Subtraktion besteht.
 *
 * Der Speicher steht in einer eigenen Funktion mit Rückgabe per Referenz, weil
 * eine `static` innerhalb von edbak_marke_lesen() von edbak_marke_setzen() aus
 * nicht erreichbar wäre — und ein Wert, den man schreiben, aber nicht
 * nachziehen kann, ist genau die Art Zwischenspeicher, die später lügt. */
function &edbak_marken_speicher(): array
{
    static $speicher = [];
    return $speicher;
}

function edbak_marke_lesen(string $k): ?string
{
    $c = &edbak_marken_speicher();
    if (array_key_exists($k, $c)) { return $c[$k]; }
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return $c[$k] = ($v === false ? null : (string)$v);
    } catch (Throwable) {
        return $c[$k] = null;   // app_state fehlt (Migration noch nicht gelaufen)
    }
}

/**
 * Eine Marke schreiben. Liefert, OB es geklappt hat (S2/AP6).
 *
 * VORHER WAR DER RUECKGABETYP `void` UND DER `catch` LEER. Der Gedanke war
 * richtig: Eine nicht schreibbare Marke darf die Sicherung selbst nicht
 * scheitern lassen. Nur hat der Block danach jeden Fehler geschluckt — auch
 * den, bei dem ein Wert schlicht nicht in die Spalte passt.
 *
 * Genau das ist passiert: `app_state.v` ist `varchar(190)`. Die Warteschlange
 * von „Alle sichern" war laenger, das INSERT scheiterte, niemand erfuhr davon,
 * und die Schaltflaeche meldete „0 von 0 Konten gesichert" — eine Zahl, die
 * nichts mit der Wirklichkeit zu tun hatte. Die Suche danach hat gekostet,
 * was ein `error_log()` gespart haette.
 *
 * DIE LAENGENGRENZE STEHT JETZT AUCH HIER, nicht nur im Schema: Ein zu langer
 * Wert wird abgewiesen und benannt, statt in einer Datenbankmeldung zu enden,
 * die je nach Serverbetriebsart mal ein Fehler und mal eine stille Kuerzung
 * ist. Eine stille Kuerzung waere hier das Schlimmste von allem — ein halbes
 * JSON, das beim naechsten Lesen als „kein Auftrag" durchgeht.
 */
const EDBAK_MARKE_MAX = 190;

function edbak_marke_setzen(string $k, string $v): bool
{
    if (strlen($v) > EDBAK_MARKE_MAX) {
        error_log('adminbackup: Marke "' . $k . '" ist ' . strlen($v)
                . ' Zeichen lang, erlaubt sind ' . EDBAK_MARKE_MAX . '.');
        return false;
    }
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
        $c = &edbak_marken_speicher();
        $c[$k] = $v;
        return true;
    } catch (Throwable $ex) {
        /* Still gegenueber der Anfrage — die Sicherung selbst soll daran nicht
         * scheitern —, aber nachlesbar. */
        error_log('adminbackup: Marke "' . $k . '" liess sich nicht schreiben: '
                . $ex->getMessage());
        return false;
    }
}

function edbak_intervall(): int
{
    $v = (int)(edbak_marke_lesen('adminbackup_intervall') ?? 0);
    return $v > 0 ? $v : EDBAK_INTERVALL_VORGABE;
}

/**
 * Aufbewahrung je Konto in Paketen (E-P3-41, Web 9.8.0).
 *
 * Dieselbe Ablage wie das Erinnerungsintervall — app_state, kein neues
 * Schema. Der Wert 0 heisst „nie gesetzt"; dann gilt die Vorbelegung.
 */
function edbak_aufbewahrung(): int
{
    $v = (int)(edbak_marke_lesen('adminbackup_aufbewahrung') ?? 0);
    return $v > 0 ? $v : EDBAK_AUFBEWAHRUNG_VORGABE;
}

/** Wöchentliche Erinnerungsmail an Admins — aus, solange nichts gesetzt ist. */
function edbak_admin_mail_an(): bool
{
    return edbak_marke_lesen('adminbackup_mail') === '1';
}

/* ---- Stand EINES Kontos (E-P3-41) ----------------------------------------
 *
 * edbak_uebersicht() liest ALLE Konten und dazu je Konto eine Begleitdatei
 * und ein Verzeichnis. Für die Kontoseite ist das die falsche Frage: Sie
 * zeigt genau ein Konto, und bei mehreren hundert Konten wäre die Übersicht
 * dafür ein Verzeichnisdurchlauf über den ganzen Bestand.
 *
 * Zurück kommt, was die Karte „Sicherungen" braucht: die Pakete, die
 * Freigabe, und der Stand als eines von fünf Worten. „nie" ist dabei nicht
 * „überfällig": Ein Konto ohne jede Sicherung ist ein anderer Befund als
 * eines, dessen letzte zu alt ist — die Liste zählt beide getrennt.
 */
function edbak_konto_stand(array $konto): array
{
    $kennung = (string)($konto['account_key'] ?? '');
    if (!edbak_kennung_gueltig($kennung)) {
        return ['stand' => 'ohne_kennung', 'pakete' => [], 'freigabe' => null,
                'letzte' => null, 'tage' => null];
    }
    $pakete  = edbak_pakete($kennung);
    $begleit = edbak_begleit_lesen($kennung);
    if (!$pakete) {
        return ['stand' => 'nie', 'pakete' => [], 'freigabe' => $begleit['freigabe'] ?? null,
                'letzte' => null, 'tage' => null];
    }
    /* Der Zeitpunkt kommt aus dem jüngsten VORHANDENEN Paket, nicht aus
     * 'letzte_sicherung' der Begleitdatei: Wird ein Paket von Hand aus dem
     * Ordner entfernt, bliebe die Marke stehen und meldete einen Stand, den
     * es nicht mehr gibt. edbak_pakete() zählt Dateien. */
    $letzte = (string)($pakete[0]['erzeugt'] ?? '');
    return edbak_stand_werten($letzte !== '' ? $letzte : null)
         + ['pakete' => $pakete, 'freigabe' => $begleit['freigabe'] ?? null];
}

/**
 * Ein Zeitpunkt wird zu einem Stand — die EINE Regel für beide Seiten (O9b).
 *
 * Kontoseite und NutzerInnen-Liste beantworten dieselbe Frage aus zwei
 * verschiedenen Quellen: die eine aus den Paketdateien, die andere aus den
 * Begleitdateien. Die REGEL, ab wann etwas überfällig ist, darf deshalb nur
 * an einer Stelle stehen — sonst driften Kachel und Kontoseite auseinander,
 * und niemandem fällt auf, welche von beiden recht hat.
 */
function edbak_stand_werten(?string $letzte, ?int $intervall = null): array
{
    if ($letzte === null || $letzte === '') {
        return ['stand' => 'nie', 'letzte' => null, 'tage' => null];
    }
    /* strtotime() liefert false, wenn die Zeichenkette kein Zeitpunkt ist —
     * und (int)false ist 0, also „1970": Das Konto stuende mit rund
     * zwanzigtausend Tagen als am dringendsten ueberfaellig ganz oben. Ein
     * unlesbarer Wert ist kein alter Wert. */
    $stempel = strtotime($letzte);
    if ($stempel === false) {
        return ['stand' => 'unbekannt', 'letzte' => null, 'tage' => null];
    }
    $tage = (int)floor((time() - $stempel) / 86400);
    $grenze = $intervall ?? edbak_intervall();
    return ['stand' => $tage >= $grenze ? 'ueberfaellig' : 'aktuell',
            'letzte' => $letzte, 'tage' => $tage];
}

/* ---- Stand ALLER Konten mit EINEM Verzeichnisdurchlauf (O9b) --------------
 *
 * Die NutzerInnen-Liste braucht zu jedem Konto ein Wort („aktuell",
 * „überfällig · 23 Tage", „nie gesichert") und zu allen Konten vier Zahlen.
 * edbak_konto_stand() je Zeile aufzurufen hiesse: je Konto ein scandir des
 * Kontoordners PLUS eine Begleitdatei — bei 300 Konten 300 Verzeichnisse.
 *
 * Hier ist es EIN scandir der Wurzel plus je Ordner eine kleine JSON-Datei.
 * Konten, die nie gesichert wurden, haben gar keinen Ordner und kosten
 * deshalb NICHTS — sie fehlen einfach in der Karte.
 *
 * DER PREIS: Die Angabe stammt aus `konto.json`, nicht aus den Paketdateien
 * selbst. Wer ein Paket von Hand aus einem Ordner entfernt, ohne die Anwendung
 * zu benutzen, sieht in der LISTE einen Stand, den es nicht mehr gibt — die
 * KONTOSEITE zeigt dann das Richtige, weil sie die Dateien zählt. Das ist die
 * bewusste Wahl: Eine Liste, die bei jedem Aufruf hunderte Verzeichnisse
 * durchgeht, um einen Fall abzudecken, den die Anwendung selbst nie herstellt,
 * wäre der schlechtere Tausch. `edbak_verzeichnis_abgleichen()` hält die Marke
 * bei jeder Änderung DURCH die Anwendung nach.
 *
 * Liefert kennung => ['lesbar'=>bool, 'letzte'=>?string, 'pakete'=>int,
 *                     'freigabe'=>bool].
 */
/** Liegt in diesem Ordner wenigstens eine Paketdatei? */
function edbak_ordner_hat_paket(string $kennung): bool
{
    $ordner = edbak_ordner($kennung);
    if (!is_dir($ordner)) { return false; }
    foreach (scandir($ordner) ?: [] as $n) {
        if (edbak_paketname_gueltig($n)) { return true; }
    }
    return false;
}

function edbak_staende(): array
{
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return []; }
    $karte = [];
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        $b = edbak_begleit_lesen($n);
        /* WAS AUS EINER DATEI KOMMT, IST NICHT VOM ERWARTETEN TYP.
         * `letzte_sicherung` ist eine Zeichenkette — aber konto.json ist eine
         * Datei auf der Platte, und eine von Hand nachgezogene oder halb
         * geschriebene kann dort eine Zahl tragen. Unter `strict_types=1`
         * waere die Weitergabe an edbak_stand_werten(?string) ein TypeError,
         * und EIN kaputtes konto.json legte die ganze NutzerInnen-Liste lahm.
         * Was kein String ist, gilt als „nicht bekannt". */
        $letzte = $b['letzte_sicherung'] ?? null;
        $karte[$n] = [
            'lesbar'   => (bool)$b['lesbar'],
            'letzte'   => is_string($letzte) && $letzte !== '' ? $letzte : null,
            'pakete'   => count((array)($b['sicherungen'] ?? [])),
            'freigabe' => ($b['freigabe']['datei'] ?? null) !== null,
        ];
        if (!$b['lesbar']) {
            /* Ordner ohne lesbare Begleitdatei: Steht wenigstens ein Paket
             * darin? Nur DANN ist der Stand wirklich unbekannt; ein leerer
             * Ordner ist schlicht „nie gesichert" und soll nicht anders
             * heissen als auf der Kontoseite. Das kostet ein scandir — aber
             * nur fuer die Ordner, die kaputt sind, und das sind im Regelfall
             * keine. */
            $karte[$n]['leer'] = !edbak_ordner_hat_paket($n);
        }
    }
    return $karte;
}

/**
 * Stand eines Kontos aus der Karte von edbak_staende().
 *
 * Die Karte wird EINMAL je Seitenaufruf gebaut und hier je Zeile gelesen —
 * ab hier kein Dateizugriff mehr.
 */
function edbak_stand_aus_karte(?string $kennung, array $karte, ?int $intervall = null): array
{
    if (!edbak_kennung_gueltig($kennung)) {
        return ['stand' => 'ohne_kennung', 'letzte' => null, 'tage' => null];
    }
    $e = $karte[(string)$kennung] ?? null;
    if ($e === null) {
        // Kein Ordner: nie gesichert. Der Normalfall eines neuen Kontos.
        return ['stand' => 'nie', 'letzte' => null, 'tage' => null];
    }
    if (!$e['lesbar']) {
        /* Der Ordner ist da, die Begleitdatei nicht lesbar. Steht ein Paket
         * darin, wäre „nie gesichert" gelogen — es ist etwas da, nur nicht
         * lesbar verzeichnet; was genau, sagt die Kontoseite. Ist der Ordner
         * dagegen LEER, ist „nie gesichert" die richtige und dieselbe Antwort
         * wie dort (ein leerer Ordner bleibt etwa nach einem abgebrochenen
         * Sicherungslauf zurück). */
        return !empty($e['leer'])
            ? ['stand' => 'nie', 'letzte' => null, 'tage' => null]
            : ['stand' => 'unbekannt', 'letzte' => null, 'tage' => null];
    }
    return edbak_stand_werten($e['letzte'], $intervall);
}

/* ---- Zwei Zeilen für die Anzeige (O9) ------------------------------------
 *
 * Beide standen bis Web 9.7.2 in admin_sicherungen.php. Seit die Sicherungen
 * eines Kontos auf dessen Kontoseite liegen (E-P3-41), brauchen sie zwei
 * Seiten — und eine Formatierung, die an zwei Stellen doppelt steht, läuft
 * auseinander.
 */

/**
 * Umfang einer Sicherung als eine Zeile — nur Zahlen, nie Inhalte (A8.7).
 *
 * REIHENFOLGE UND TRENNER FOLGEN MOCKUP 40 („41 Diensttage · 138 Einsätze ·
 * 2,1 MB"): Diensttage zuerst, Mittelpunkt statt Komma. Ruhezeiten und
 * Papierkorb bleiben trotzdem stehen — das Mockup zeigt eine kurze Zeile,
 * aber die Zahl der gelöschten Datensätze ist die einzige Stelle, an der
 * sichtbar wird, was in einem Paket steckt, ohne es zu öffnen (E-S1-02).
 * Kürzen hiesse hier, eine Auskunft zu streichen, nicht ein Wort.
 */
function edbak_umfang_text(array $p): string
{
    $z = $p['umfang'] ?? null;
    $teile = [];
    if (is_array($z)) {
        $teile[] = (int)($z['diensttage'] ?? $z['flugtage'] ?? 0) . ' Diensttage';
        $teile[] = (int)($z['einsaetze'] ?? 0) . ' Einsätze';
        $teile[] = (int)($z['ruhezeiten'] ?? 0) . ' Ruhezeiten';
        /* „davon im Papierkorb" (E-S1-02). Seit Nutzlast 7 steht der
         * Papierkorb in jeder Sicherung und zaehlt in den drei Zahlen oben
         * MIT. Ohne diesen Zusatz waere aus „87 Einsätze" nicht zu erkennen,
         * dass fünf davon geloescht sind.
         *
         * EINE ZAHL STATT DREI (O9). Bis Web 9.7.2 stand hier die Aufteilung
         * nach Art („davon im Papierkorb: 5 Einsätze, 1 Diensttag, 5
         * Ruhezeiten"). In der Zeile einer Karte, die halb so breit ist wie
         * die alte Tabelle, waren das drei Zeilen Umbruch für eine Angabe,
         * die man liest, um EINE Frage zu beantworten: Wie viel von dem
         * Paket ist gelöschter Bestand? Die Summe beantwortet sie; die
         * Aufteilung stand im Weg. Bewusste Kürzung, keine Auslassung —
         * das Paket selbst führt die Zahlen weiter je Art.
         *
         * Fehlt der Block (Sicherungen vor S1), wird NICHTS angezeigt statt
         * einer Null: Eine Null behauptete „nichts im Papierkorb", richtig ist
         * „nicht erhoben". */
        $pk = $z['papierkorb'] ?? null;
        if (is_array($pk)) {
            $summe = (int)($pk['einsaetze'] ?? 0) + (int)($pk['diensttage'] ?? 0)
                   + (int)($pk['ruhezeiten'] ?? 0);
            $teile[] = $summe === 0
                ? 'nichts im Papierkorb'
                : 'davon ' . $summe . ' im Papierkorb';
        }
    }
    $teile[] = edbak_groesse_text((int)($p['groesse'] ?? 0));
    return implode(' · ', $teile);
}

/**
 * Dateigrösse: KB unter einem Megabyte, MB darüber — und GB ab einem Gigabyte.
 *
 * DIE DRITTE STUFE KAM MIT DER SPEICHERGRENZE (S2/AP6). Sie wird in GB
 * angegeben (Vorgabe 2 GB); ohne diese Stufe hätte die Meldung „Die
 * Speichergrenze ist erreicht (2.048,0 MB von 2.048,0 MB)" gelautet — dieselbe
 * Zahl, die daneben als „2 GB" eingestellt wird, in einer anderen Einheit.
 */
function edbak_groesse_text(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return number_format($bytes / (1024 * 1024 * 1024), 2, ',', '.') . ' GB';
    }
    return $bytes < 1024 * 1024
        ? number_format($bytes / 1024, 0, ',', '.') . ' KB'
        : number_format($bytes / (1024 * 1024), 1, ',', '.') . ' MB';
}

/** Zeitpunkt aus dem Paket (UTC in ISO) in Ortszeit. */
function edbak_zeitpunkt_text(?string $iso): string
{
    if (!$iso) { return 'unbekannt'; }
    /* Mittelpunkt zwischen Datum und Uhrzeit (Mockup 40: „03.08.2026 ·
     * 22:10") — derselbe Trenner wie in der Umfangszeile darunter. */
    try { return fmt_local(str_replace(['T', 'Z'], [' ', ''], (string)$iso), 'd.m.Y · H:i'); }
    catch (Throwable) { return (string)$iso; }
}

/* ---- Zwei Helfer, die zwei Seiten brauchen (O9) ---------------------------
 *
 * Beide standen bis Web 9.7.2 in admin_sicherungen.php. Seit die Kontoseite
 * dieselben Handlungen anbietet, gaebe es sie zweimal — und zwei Fassungen
 * einer Sicherheitspruefung sind eine Gelegenheit, dass eine davon nachlaesst.
 * (Die alte Fassung von bestaetigung_passt() liess ausserdem den leeren
 * Sollwert durchgehen: Ein Konto ohne Adresse haette sich mit einer leeren
 * Eingabe bestaetigen lassen.)
 */

/** Zielkonto samt Kennung und Huelle lesen — für Rückspielung und Freigabe. */
function edbak_ziel_konto(int $id): ?array
{
    $st = db()->prepare('SELECT id, email, name, account_key, pat_wrap_rc, pat_key_check
                         FROM users WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Harte Bestätigung: die E-Mail-Adresse des ZIELKONTOS muss abgetippt sein (E21).
 *
 * Abgetippt wird die Adresse des Ziels, nicht die der Herkunft. Das Risiko ist
 * nicht Datenverlust — edbak_restore() ergänzt und ersetzt nicht —, sondern das
 * Einspielen FREMDER Daten in ein falsches Konto. Abgesichert werden muss
 * deshalb das Ziel. Geprüft wird SERVERSEITIG: Ein Browser-Dialog liesse sich
 * umgehen.
 *
 * Ein leerer Sollwert passt zu NICHTS — sonst genügte bei einem Konto ohne
 * Adresse die leere Eingabe.
 */
function edbak_bestaetigung_passt(string $eingabe, string $soll): bool
{
    $soll = trim($soll);
    return $soll !== '' && strcasecmp(trim($eingabe), $soll) === 0;
}

/* ---- Zahlen der ganzen Ablage (O9c) --------------------------------------
 *
 * Die Regelseite nennt oben, wie viel in der Ablage liegt: Ordner, Pakete,
 * Bytes. Anders als die NutzerInnen-Liste kommt das NICHT ohne Verzeichnisse
 * aus — eine Groesse in Bytes steht in keiner Begleitdatei, sie steht nur an
 * den Dateien.
 *
 * Das ist hier vertretbar und in der Liste nicht: Diese Seite EXISTIERT, um
 * genau das zu beantworten, und sie wird selten geoeffnet. Die Liste dagegen
 * ist der Weg zu einem Konto und wird staendig aufgerufen. Gemessen an 209
 * Ordnern mit 421 Paketen: siehe Pruefprotokoll O9c.
 */
function edbak_ablage_zahlen(bool $frisch = false): array
{
    /* EINMAL JE ANFRAGE, NICHT EINMAL JE KONTO (S2/AP6).
     *
     * Seit die Zaehlung das ganze Verzeichnis wiegt, ist sie ein `du` ueber
     * alle Konten. Die Speichergrenze wird vor JEDEM Sichern geprueft — bei
     * „Alle sichern" ueber zwanzig Konten waeren das zwanzig Durchgaenge ueber
     * denselben Baum, auf einem geteilten Webspace der teuerste Teil des
     * Laufs. Der Zwischenspeicher haelt eine Anfrage lang; wer gerade
     * geschrieben hat, verlangt mit `$frisch` eine neue Zaehlung. */
    static $letzte = null;
    if (!$frisch && $letzte !== null) { return $letzte; }

    $wurzel = edbak_wurzel();
    $z = ['ordner' => 0, 'pakete' => 0, 'bytes' => 0,
          'sonstige_bytes' => 0, 'reste' => 0,
          'komplett' => 0, 'komplett_bytes' => 0];
    if (!is_dir($wurzel)) { return $letzte = $z; }

    /* DAS GANZE VERZEICHNIS, NICHT NUR DIE PAKETE (S2/AP6).
     *
     * Bis Web 11.2.0 zaehlte diese Funktion ausschliesslich Dateien, die
     * `edbak_paketname_gueltig()` bestanden. Alles andere im Ordner —
     * Begleitdateien, `.htaccess`, liegengebliebene `.tmp`-Dateien und
     * Bauordner eines abgebrochenen Laufs — war unsichtbar und zaehlte
     * trotzdem auf der Platte.
     *
     * Fuer eine Anzeige mag das angehen. Fuer eine SPEICHERGRENZE ist es
     * falsch: Sie soll sagen, wann die Platte voll ist, und die fuellt sich
     * auch mit dem, was hier nicht auf der Liste steht. `bytes` ist deshalb
     * jetzt der ganze Verbrauch; `sonstige_bytes` sagt, wie viel davon nicht
     * in Paketen steckt, damit ein auffaelliger Rest AUFFAELLT statt in einer
     * Summe unterzugehen. */
    $wiegen = static function (string $pfad) use (&$wiegen): int {
        $summe = 0;
        foreach (scandir($pfad) ?: [] as $n) {
            if ($n === '.' || $n === '..') { continue; }
            $voll = $pfad . '/' . $n;
            if (is_dir($voll)) { $summe += $wiegen($voll); }
            else { $summe += (int)@filesize($voll); }
        }
        return $summe;
    };

    $z['bytes'] = $wiegen($wurzel);
    $inPaketen = 0;
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        $z['ordner']++;
        foreach (scandir($wurzel . '/' . $n) ?: [] as $d) {
            $voll = $wurzel . '/' . $n . '/' . $d;
            if (edbak_paketname_gueltig($d)) {
                $z['pakete']++;
                $inPaketen += (int)@filesize($voll);
            } elseif ((is_dir($voll) && str_starts_with($d, EDBAK_BAU_PRAEFIX))
                      || (is_file($voll) && str_ends_with($d, '.tmp'))) {
                $z['reste']++;
            }
        }
    }
    /* DIE KOMPLETTSICHERUNGEN BEKOMMEN EINE EIGENE ZAHL (S2/AP8).
     *
     * Gewogen waren sie schon vorher — `$wiegen` geht ueber den ganzen Baum.
     * Sie landeten damit aber unter `sonstige_bytes`, und das ist die Zahl,
     * die auf der Speicherseite „auffaelliger Rest" heisst. Eine
     * Komplettsicherung ist mit Abstand die groesste Datei der Ablage; sie als
     * Rest auszuweisen hiesse, die Speicherseite zur Meldung eines Fehlers zu
     * bringen, den es nicht gibt — und beim naechsten Mal glaubt ihr niemand
     * mehr. */
    /* Name und Muster kommen aus `komplett_lib.php` und stehen nicht hier
     * noch einmal. Das Nachladen mitten in der Funktion ist Absicht: Jene
     * Datei laedt diese hier, also darf es nicht am Kopf stehen. Zum
     * Zeitpunkt des AUFRUFS ist beides fertig geladen. */
    require_once __DIR__ . '/komplett_lib.php';
    $kompPfad = $wurzel . '/' . KOMP_ORDNER;
    if (is_dir($kompPfad)) {
        foreach (scandir($kompPfad) ?: [] as $d) {
            $voll = $kompPfad . '/' . $d;
            if (is_file($voll) && komp_name_gueltig($d)) {
                $z['komplett']++;
                $z['komplett_bytes'] += (int)@filesize($voll);
            } elseif (is_dir($voll) && str_starts_with($d, KOMP_BAU_PRAEFIX)) {
                $z['reste']++;
            }
        }
    }
    $z['sonstige_bytes'] = max(0, $z['bytes'] - $inPaketen - $z['komplett_bytes']);
    return $letzte = $z;
}

/** Speichergrenze in Byte — 0 heisst „nie gesetzt", dann gilt die Vorgabe. */
function edbak_grenze_bytes(): int
{
    $gb = (float)(edbak_marke_lesen('adminbackup_grenze_gb') ?? 0);
    if ($gb <= 0) { $gb = (float)EDBAK_GRENZE_GB_VORGABE; }
    return (int)round($gb * 1024 * 1024 * 1024);
}

/** Warnschwellen in Prozent, aufsteigend und entdoppelt. */
function edbak_schwellen(): array
{
    $roh = (string)(edbak_marke_lesen('adminbackup_schwellen') ?? '');
    if (trim($roh) === '') { $roh = EDBAK_SCHWELLEN_VORGABE; }
    $aus = [];
    foreach (explode(',', $roh) as $t) {
        $v = (int)trim($t);
        if ($v > 0 && $v <= 100) { $aus[$v] = true; }
    }
    $aus = array_keys($aus);
    sort($aus);
    return $aus;
}

/** Wo steht die Ablage gegen ihre Grenze? */
function edbak_speicherstand(bool $frisch = false): array
{
    $z = edbak_ablage_zahlen($frisch);
    $grenze = edbak_grenze_bytes();
    return [
        'bytes'          => $z['bytes'],
        'sonstige_bytes' => $z['sonstige_bytes'],
        'reste'          => $z['reste'],
        'pakete'         => $z['pakete'],
        'ordner'         => $z['ordner'],
        'grenze'         => $grenze,
        'prozent'        => $grenze > 0 ? (int)floor($z['bytes'] * 100 / $grenze) : 0,
        'voll'           => $grenze > 0 && $z['bytes'] >= $grenze,
    ];
}

/**
 * Darf noch gesichert werden? (E-S2-14: ablehnen mit Meldung, nie still
 * verdrängen.)
 *
 * DIE PRUEFUNG STEHT VOR DEM BAU, nicht dahinter. Ein Paket erst zu bauen und
 * dann wegen der Grenze zu verwerfen kostet beim 5000er-Konto 14 Sekunden für
 * nichts — und bei „Alle sichern" diese 14 Sekunden je Konto.
 */
function edbak_grenze_pruefen(bool $frisch = false): array
{
    $st = edbak_speicherstand($frisch);
    if (!$st['voll']) { return [true, null]; }
    return [false, 'Die Speichergrenze für Sicherungen ist erreicht ('
                 . edbak_groesse_text($st['bytes']) . ' von '
                 . edbak_groesse_text($st['grenze']) . '). Es wurde NICHTS '
                 . 'gelöscht und nichts überschrieben. Bitte alte Sicherungen '
                 . 'entfernen, die Aufbewahrung senken oder die Grenze erhöhen.'];
}

/**
 * Je überschrittener Schwelle einmal melden (E-S2-15).
 *
 * DIE MARKE WIRD NACH DEM VERSAND GESETZT, nicht davor.
 *
 * Das einzige Vorbild im Haus — die wöchentliche Erinnerungsmail — macht es
 * umgekehrt: erst die Marke, dann `@smtp_send()`, dessen Rückgabewert
 * verworfen wird. Für eine Erinnerung ist das vertretbar (die nächste kommt
 * in einer Woche). Für eine Warnung, dass der Speicher zuläuft, ist es
 * falsch: Scheitert der Versand, steht die Marke trotzdem, und die Warnung
 * kommt NIE — genau in dem Fall, in dem sie gebraucht wird.
 *
 * OHNE EINGERICHTETES SMTP wird gar nicht erst versucht. Die Schwelle wird
 * dann vermerkt, damit der Adminbereich den dauerhaften Hinweis zeigen kann,
 * aber als `hinweis` und nicht als `gemeldet` — sonst verschwände der Hinweis
 * beim nächsten Lauf, ohne dass ihn jemand gesehen hätte.
 */
function edbak_schwellen_melden(): array
{
    $st = edbak_speicherstand(true);
    $erledigt = array_filter(array_map('intval',
        explode(',', (string)(edbak_marke_lesen('adminbackup_schwellen_gemeldet') ?? ''))));
    $offen = [];
    foreach (edbak_schwellen() as $s) {
        if ($st['prozent'] >= $s && !in_array($s, $erledigt, true)) { $offen[] = $s; }
    }

    /* UNTERSCHRITTENE SCHWELLEN VERGESSEN. Wer aufräumt und wieder unter die
     * Schwelle fällt, soll beim nächsten Überschreiten erneut gewarnt werden.
     * Ohne das wäre die Warnung ein einmaliges Ereignis im Leben einer
     * Installation. */
    $bleibt = array_values(array_filter($erledigt, static fn($s) => $st['prozent'] >= $s));

    $aus = ['stand' => $st, 'gemeldet' => [], 'hinweis' => [], 'fehler' => []];
    if (!$offen) {
        if ($bleibt !== $erledigt) {
            edbak_marke_setzen('adminbackup_schwellen_gemeldet', implode(',', $bleibt));
        }
        return $aus;
    }

    require_once __DIR__ . '/smtp.php';
    if (!smtp_eingerichtet()) {
        $aus['hinweis'] = $offen;
        edbak_marke_setzen('adminbackup_schwellen_offen', implode(',', $offen));
        return $aus;
    }

    $ziele = [];
    foreach (db()->query("SELECT email FROM users WHERE role = 'admin'
                          ORDER BY id")->fetchAll(PDO::FETCH_COLUMN) as $m) {
        if (is_string($m) && $m !== '') { $ziele[] = $m; }
    }
    foreach ($offen as $s) {
        $text = "Die Ablage der Sicherungen hat " . $s . " % ihrer Grenze erreicht.\n\n"
              . "Belegt:  " . edbak_groesse_text($st['bytes']) . "\n"
              . "Grenze:  " . edbak_groesse_text($st['grenze']) . "\n"
              . "Pakete:  " . $st['pakete'] . " in " . $st['ordner'] . " Konten\n\n"
              . "Ist die Grenze erreicht, wird nicht mehr gesichert — es wird "
              . "nichts still verdraengt. Bitte alte Sicherungen entfernen, die "
              . "Aufbewahrung senken oder die Grenze erhoehen.\n";
        $ok = false;
        foreach ($ziele as $m) {
            if (smtp_send($m, 'Sicherungen: ' . $s . ' % der Speichergrenze erreicht', $text)) {
                $ok = true;
            }
        }
        if ($ok) { $aus['gemeldet'][] = $s; } else { $aus['fehler'][] = $s; }
    }

    if ($aus['gemeldet']) {
        edbak_marke_setzen('adminbackup_schwellen_gemeldet',
                           implode(',', array_merge($bleibt, $aus['gemeldet'])));
    }
    if ($aus['fehler']) {
        edbak_marke_setzen('adminbackup_schwellen_offen', implode(',', $aus['fehler']));
    }
    return $aus;
}

/* ---- Die Zaehler ueber alle Konten (O9c) ----------------------------------
 *
 * Dieselbe Frage wie die Statuskacheln der NutzerInnen-Liste, aber ohne deren
 * Zeilendaten: eine schmale Abfrage (Kennung und Rolle) plus die Karte aus
 * edbak_staende(). Die Liste rechnet die Zahlen aus ihren eigenen Zeilen —
 * beide benutzen dieselbe Regel (edbak_stand_aus_karte), damit sie nicht
 * auseinanderlaufen koennen.
 */
function edbak_stand_zaehlen(): array
{
    $konten = db()->query('SELECT id, email, name, role, account_key FROM users')->fetchAll();
    $karte  = edbak_staende();
    $z = ['konten' => count($konten), 'admins' => 0, 'aktuell' => 0,
          'ueberfaellig' => 0, 'nie' => 0, 'ohne_kennung' => 0, 'unbekannt' => 0];
    foreach ($konten as $k) {
        if ($k['role'] === 'admin') { $z['admins']++; }
        $stand = edbak_stand_aus_karte($k['account_key'], $karte)['stand'];
        if (isset($z[$stand])) { $z[$stand]++; }
    }
    return $z;
}

/**
 * Die überfälligen und nie gesicherten Konten — für die Erinnerungsmail.
 *
 * Liefert je Konto Adresse, Name, Stand und Alter. Sortiert: was nie gesichert
 * wurde zuerst, danach das Älteste — die Reihenfolge, in der man die Liste
 * abarbeitet.
 */
function edbak_faellige_konten(): array
{
    $konten = db()->query('SELECT id, email, name, account_key FROM users ORDER BY email')->fetchAll();
    $karte  = edbak_staende();
    $liste = [];
    foreach ($konten as $k) {
        $stand = edbak_stand_aus_karte($k['account_key'], $karte);
        if (!in_array($stand['stand'], ['ueberfaellig', 'nie'], true)) { continue; }
        $liste[] = ['id' => (int)$k['id'], 'email' => (string)$k['email'],
                    'name' => $k['name'], 'stand' => $stand['stand'],
                    'tage' => $stand['tage']];
    }
    usort($liste, static function (array $a, array $b): int {
        if ($a['stand'] !== $b['stand']) { return $a['stand'] === 'nie' ? -1 : 1; }
        return (int)$b['tage'] <=> (int)$a['tage'];
    });
    return $liste;
}

/* ---- Sicherungen ohne Konto (O9c) -----------------------------------------
 *
 * Ordner, zu deren Kennung es keine Zeile in `users` (mehr) gibt — der Fall
 * „Konto geloescht und neu aufgesetzt" (A8.2). Sie sind der Grund, aus dem die
 * Uebersicht ueberhaupt aus dem VERZEICHNIS entsteht und nicht aus der
 * Datenbank: Eine Liste aus `users` verschwiege genau die Sicherungen, um
 * derentwillen es sie gibt.
 *
 * Das ist die schmale Fassung von edbak_uebersicht(): NUR die verwaisten
 * Ordner. Die Konten selbst brauchen hier nichts mehr — ihre Sicherungen
 * stehen seit Web 9.8.0 auf der Kontoseite.
 */
function edbak_verwaiste(): array
{
    $bekannt = [];
    foreach (db()->query('SELECT account_key FROM users')->fetchAll(PDO::FETCH_COLUMN) as $k) {
        if (edbak_kennung_gueltig($k)) { $bekannt[(string)$k] = true; }
    }
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return []; }
    $liste = [];
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        if (isset($bekannt[$n])) { continue; }
        $begleit = edbak_begleit_lesen($n);
        $liste[] = [
            'account_key' => $n,
            'lesbar'      => (bool)$begleit['lesbar'],
            'email'       => $begleit['email'],
            'name'        => $begleit['name'],
            'pakete'      => edbak_pakete($n),
            'freigabe'    => $begleit['freigabe'] ?? null,
        ];
    }
    return $liste;
}

/* ---- Die wöchentliche Erinnerung an die Administration (E-P3-41, O9c) -----
 *
 * ES GIBT KEINEN CRON. Auf diesem Webspace laeuft kein Zeitplan; was
 * regelmaessig geschieht, haengt am Aufraeumjob (run_cleanup_if_due, db.php),
 * und der laeuft huckepack auf der ersten Anfrage des Tages. Die Erinnerung
 * ist deshalb genau genommen keine Wochenmail, sondern: hoechstens einmal je
 * Woche, und zwar dann, wenn die Anwendung an diesem Tag ueberhaupt benutzt
 * wurde. Wird sie zwei Wochen lang nicht angefasst, kommt die Mail zwei
 * Wochen spaeter. Das steht so auf der Seite — eine Zusage, die nur bei
 * Benutzung gilt, muss man als solche kennzeichnen.
 *
 * SIE KOMMT NUR, WENN ES ETWAS ZU MELDEN GIBT. Eine Wochenmail „0 Konten
 * ueberfaellig" ist nach dem dritten Mal keine Meldung mehr, sondern etwas,
 * das man wegklickt — und dann geht auch die vierte unter, in der etwas steht.
 *
 * VERSCHICKT WIRD NACH DER ANTWORT (register_shutdown_function). Der
 * Aufraeumjob laeuft VOR der Seitenausgabe; ein SMTP-Gespraech an dieser
 * Stelle waere eine messbare Verzoegerung auf der Seite irgendeiner NutzerIn,
 * die damit nichts zu tun hat.
 */
function edbak_erinnerung_faellig(): bool
{
    if (!edbak_admin_mail_an()) { return false; }
    $letzte = edbak_marke_lesen('adminbackup_mail_last');
    if ($letzte !== null && $letzte !== '') {
        $stempel = strtotime($letzte);
        if ($stempel !== false && (time() - $stempel) < 7 * 86400) { return false; }
    }
    return true;
}

/**
 * Prüfen und, wenn nötig, den Versand für nach der Antwort vormerken.
 *
 * Aufgerufen aus dem Aufräumjob (db.php). Gibt zurück, wie viele Konten
 * gemeldet werden — 0 heisst „nichts zu tun".
 */
function edbak_erinnerung_planen(): int
{
    if (!edbak_erinnerung_faellig()) { return 0; }
    $faellig = edbak_faellige_konten();
    if (!$faellig) { return 0; }

    $admins = db()->query("SELECT email, name FROM users
                            WHERE role = 'admin' AND password_hash IS NOT NULL
                            ORDER BY email")->fetchAll();
    if (!$admins) { return 0; }

    /* Die Marke steht VOR dem Versand, wie beim Aufräumjob selbst: Zwei
     * gleichzeitige Anfragen sollen nicht beide schicken. Der teurere Fehler
     * ist die doppelte Mail, nicht die ausgefallene — die nächste kommt in
     * sieben Tagen. */
    edbak_marke_setzen('adminbackup_mail_last', gmdate('Y-m-d'));

    $text = edbak_erinnerung_text($faellig);
    register_shutdown_function(static function () use ($admins, $text): void {
        require_once __DIR__ . '/smtp.php';
        foreach ($admins as $a) {
            @smtp_send((string)$a['email'],
                'Sicherungen fällig — Gen-EM Einsatzdokumentation Notarzt', $text);
        }
    });
    return count($faellig);
}

/**
 * Der Text der Erinnerungsmail.
 *
 * KEINE NAMEN, KEINE ZAHLEN AUS DEN KONTEN — nur Adressen und das Alter der
 * letzten Sicherung. Eine Mail liegt unverschlüsselt im Postfach; was darin
 * steht, steht damit auch auf jedem Mailserver dazwischen. Die Adresse muss
 * hinein, sonst weiss niemand, welches Konto gemeint ist; alles Weitere steht
 * in der Anwendung.
 *
 * Aufbau wie die übrigen Vorlagen (admin_users.php, reset_request.php): Anrede,
 * Sache, was zu tun ist, Support-Adresse, Gruss. Reiner Text, kein HTML.
 */
function edbak_erinnerung_text(array $faellig): string
{
    global $CFG;
    $nie   = array_filter($faellig, static fn($k) => $k['stand'] === 'nie');
    $alt   = array_filter($faellig, static fn($k) => $k['stand'] === 'ueberfaellig');
    $zeilen = [];
    foreach ($nie as $k) { $zeilen[] = '  ' . $k['email'] . ' — nie gesichert'; }
    foreach ($alt as $k) {
        $zeilen[] = '  ' . $k['email'] . ' — letzte Sicherung vor ' . (int)$k['tage'] . ' Tagen';
    }
    $basis = (string)($CFG['app']['base_url'] ?? '');

    return "Hallo,\n\n"
         . "in der Gen-EM Einsatzdokumentation Notarzt sind " . count($faellig)
         . " Konten ohne aktuelle Sicherung:\n\n"
         . implode("\n", $zeilen) . "\n\n"
         . "Als überfällig gilt ein Konto, dessen letzte Sicherung älter ist als "
         . edbak_intervall() . " Tage.\n\n"
         . "Sichern lässt sich jedes Konto auf seiner Kontoseite, mehrere auf einmal\n"
         . "über die Auswahl in der NutzerInnen-Liste:\n\n"
         . ($basis !== '' ? $basis . "/admin_users.php?f=nie\n" . $basis . "/admin_users.php?f=ueberfaellig\n\n" : '')
         . "Diese Erinnerung kommt höchstens einmal je Woche und nur, wenn es etwas zu\n"
         . "melden gibt. Abschalten lässt sie sich unter Einstellungen → Sicherungen.\n\n"
         . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
         . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n";
}

/** Plakettentext und -ton zu einem Stand aus edbak_konto_stand(). */
function edbak_stand_plakette(array $stand): array
{
    /* Die Toene der PLAKETTE heissen neutral/orange/blau/rot (Stylesheet,
     * Abschnitt 8) — nicht info/warn/ok/gefahr; das sind die Toene der
     * MELDUNG. Zwei Vorraete, zwei Bausteine. */
    return match ((string)$stand['stand']) {
        'aktuell'      => ['aktuell', 'blau'],
        'ueberfaellig' => ['überfällig · ' . (int)$stand['tage'] . ' Tage', 'orange'],
        'nie'          => ['nie gesichert', 'rot'],
        'unbekannt'    => ['Stand unbekannt', 'orange'],
        default        => ['ohne Kennung', 'orange'],
    };
}

/**
 * Zustand der Erinnerung.
 *
 * Liefert ['faellig'=>bool, 'letzte'=>?string, 'tage'=>?int, 'intervall'=>int].
 * 'letzte' ist NULL, solange nie gesichert wurde — dann ist die Erinnerung
 * fällig, sobald überhaupt ein Konto existiert.
 */
function edbak_erinnerung(): array
{
    $intervall = edbak_intervall();
    $letzte = edbak_marke_lesen('adminbackup_last');
    if ($letzte === null || $letzte === '') {
        return ['faellig' => true, 'letzte' => null, 'tage' => null, 'intervall' => $intervall];
    }
    $tage = (int)floor((time() - strtotime($letzte . ' UTC')) / 86400);
    return ['faellig' => $tage >= $intervall, 'letzte' => $letzte,
            'tage' => $tage, 'intervall' => $intervall];
}

/**
 * Ein Adminpaket der Fassung 2 in ein Konto einspielen (S2/AP6).
 *
 * WOFUER. Fassung 2 liegt mehrteilig vor; sie am Stueck zu lesen und
 * `edbak_restore()` in einem Zug zu uebergeben waere genau die Spitze, die
 * der Umbau auf der Schreibseite beseitigt hat — nur eben beim Lesen. Diese
 * Funktion geht denselben Weg wie der Browser bei einer Nutzersicherung:
 * Kopf, Eintragsfenster, Spurteile.
 *
 * WARUM DAS NICHT „nur" eine Bequemlichkeit ist. Ein Fassung-2-Kern durch
 * `edbak_restore()` zu schicken, wie es der einteilige Weg tut, ginge nicht
 * schief — es ginge STILL schief: Nutzlast 8 traegt keine Punktlisten, der
 * Verweisweg faende keine `spur_ref`, und das Konto haette am Ende jeden
 * Einsatz, aber keine einzige Spur. Genau dieser Fall ist in F-S2-E einmal
 * eingetreten und hat 91 208 Punkte gekostet.
 *
 * KEIN KLARTEXT UNTERWEGS. `pat_blob` wandert als Chiffretext durch; der
 * Server sieht ihn nicht offen, und er wird auch nicht umgeschluesselt. Wer
 * ein Paket in ein ANDERES Konto spielt, bekommt die geschuetzten Angaben
 * deshalb unlesbar — dafuer gibt es den Freigabeweg, auf dem der Browser der
 * Nutzerin sie mit ihrem eigenen Schluessel neu verschluesselt (E20).
 *
 * Rueckgabe: [bool $ok, ?string $meldung, ?array $stats].
 */
function edbak_paket_einspielen(string $kennung, string $datei, int $zielUserId): array
{
    if (!edbak_kennung_gueltig($kennung) || edbak_paket_fassung($datei) !== 2) {
        return [false, 'Das ist kein mehrteiliges Sicherungspaket.', null];
    }
    if (!class_exists('ZipArchive')) {
        return [false, 'Der PHP-Erweiterung „zip" fehlt (ext/zip).', null];
    }
    $pfad = edbak_ordner($kennung) . '/' . $datei;
    if (!is_file($pfad)) { return [false, 'Die Sicherung ist nicht auffindbar.', null]; }

    $zip = new ZipArchive();
    if ($zip->open($pfad) !== true) {
        return [false, 'Die Sicherung liess sich nicht öffnen.', null];
    }
    $lies = static function (string $name) use ($zip): ?array {
        $roh = $zip->getFromName($name);
        if ($roh === false) { return null; }
        $d = json_decode($roh, true);
        return is_array($d) ? $d : null;
    };

    $manifest = $lies('manifest.json');
    if ($manifest === null || ($manifest['format'] ?? '') !== 'einsatzdoku-adminsicherung') {
        $zip->close();
        return [false, 'Der Sicherung fehlt ein lesbares Manifest.', null];
    }
    /* VOLLSTAENDIGKEIT VOR DEM ERSTEN SCHREIBEN. Ein fehlendes Teil soll
     * auffallen, solange das Zielkonto noch unberuehrt ist — nicht auf halber
     * Strecke, wenn schon die Haelfte drinsteht. */
    $fehlend = [];
    foreach ((array)($manifest['teile'] ?? []) as $t) {
        if ($zip->locateName((string)$t) === false) { $fehlend[] = (string)$t; }
    }
    if ($fehlend) {
        $zip->close();
        return [false, 'Der Sicherung fehlen ' . count($fehlend) . ' von '
                     . count((array)$manifest['teile']) . ' Teilen ('
                     . implode(', ', array_slice($fehlend, 0, 3))
                     . (count($fehlend) > 3 ? ' …' : '')
                     . '). Es wurde nichts geändert.', null];
    }

    $kopf = $lies('kopf.json');
    if ($kopf === null) {
        $zip->close();
        return [false, 'Der Sicherung fehlt der Kopf.', null];
    }

    $summe = [];
    $dazu = static function (array &$summe, array $stats): void {
        foreach ($stats as $k => $v) {
            if (is_int($v)) { $summe[$k] = (int)($summe[$k] ?? 0) + $v; }
            elseif (is_array($v)) {
                foreach ($v as $k2 => $v2) {
                    if (is_int($v2)) { $summe[$k][$k2] = (int)($summe[$k][$k2] ?? 0) + $v2; }
                }
            }
        }
    };

    try {
        $stats = edbak_restore($zielUserId, $kopf);
        $dayMap = $stats['day_map'] ?? [];
        $karte  = $stats['spur_karte'] ?? [];
        unset($stats['day_map'], $stats['spur_karte']);
        $dazu($summe, $stats);
        unset($kopf);

        for ($i = 1; $i <= (int)($manifest['eintragsteile'] ?? 0); $i++) {
            $name = 'eintraege/' . sprintf('%04d', $i) . '.json';
            $f = $lies($name);
            if ($f === null) {
                $zip->close();
                return [false, 'Das Teil ' . $name . ' liess sich nicht lesen. '
                             . 'Der bis dahin eingespielte Bestand bleibt stehen.', null];
            }
            /* Die Fassung steht im Kopf der Datei und wird hier mitgegeben —
             * derselbe Grund wie in api/backup_eintraege_restore.php: Der
             * Eintragsweg gibt es nur fuer Nutzlast 8, und das soll dastehen
             * statt sich daraus zu ergeben, dass niemand anders ihn ruft. */
            $f['version'] = 8;
            $s2 = edbak_restore($zielUserId, $f, $dayMap);
            foreach ((array)($s2['spur_karte'] ?? []) as $ref => $ziel) {
                $karte[(int)$ref] = $ziel;
            }
            unset($s2['spur_karte'], $s2['day_map']);
            $dazu($summe, $s2);
            unset($f);
        }

        $spuren = ['geschrieben' => 0, 'uebersprungen' => 0, 'abgelehnt' => [],
                   'ohne_ziel' => 0];
        for ($i = 1; $i <= (int)($manifest['spurteile'] ?? 0); $i++) {
            $name = 'spuren/' . sprintf('%04d', $i) . '.json';
            $t = $lies($name);
            if ($t === null) {
                $zip->close();
                return [false, 'Das Spurteil ' . $name . ' liess sich nicht lesen. '
                             . 'Der Bestand ist eingespielt, es fehlen aber Spuren.', null];
            }
            $liste = [];
            foreach ((array)($t['spuren'] ?? []) as $e) {
                $ziel = $karte[(int)($e['spur_ref'] ?? -1)] ?? null;
                if ($ziel === null) { $spuren['ohne_ziel']++; continue; }
                $liste[] = ['owner_type' => $ziel['art'], 'owner_id' => $ziel['id'],
                            'blob' => (string)($e['blob'] ?? ''),
                            'n' => isset($e['n']) ? (int)$e['n'] : null];
            }
            unset($t);
            if ($liste) {
                $e = edbak_spuren_schreiben(db(), $zielUserId, $liste);
                $spuren['geschrieben']   += $e['geschrieben'];
                $spuren['uebersprungen'] += $e['uebersprungen'];
                foreach ($e['abgelehnt'] as $a) { $spuren['abgelehnt'][] = $a; }
            }
            unset($liste);
        }
        $summe['spuren'] = $spuren;
    } catch (Throwable $ex) {
        $zip->close();
        throw $ex;
    }
    $zip->close();
    return [true, null, $summe];
}

/**
 * Ein Paket zurueckspielen — beide Fassungen, eine Tuer (S2/AP6).
 *
 * WOFUER. Die Aufrufer im Adminbereich sollen nicht wissen muessen, welche
 * Fassung vor ihnen liegt. Vor allem sollen sie nicht in Versuchung geraten,
 * ein Fassung-2-Paket durch den einteiligen Weg zu schicken: Das ginge nicht
 * schief, sondern STILL schief — jeder Einsatz kaeme an, keine einzige Spur
 * (F-S2-E).
 *
 * Rueckgabe: [bool $ok, ?string $meldung, ?array $bericht].
 */
function edbak_paket_zurueckspielen(string $kennung, string $datei, int $zielUserId): array
{
    if (edbak_paket_fassung($datei) === 2) {
        return edbak_paket_einspielen($kennung, $datei, $zielUserId);
    }
    $paket = edbak_paket_lesen($kennung, $datei);
    if ($paket === null) { return [false, 'Die Sicherung liess sich nicht lesen.', null]; }
    return [true, null, edbak_restore($zielUserId, $paket['daten'] ?? [])];
}

/**
 * EIN Teil eines Fassung-2-Pakets, roh (S2/AP6).
 *
 * WOFUER. Der Freigabeweg reicht das Paket an den Browser der NutzerIn — sie
 * ist die Einzige, die die geschuetzten Angaben umschluesseln kann. Bis
 * Web 12.0.0 ging das in EINER Antwort; beim 5000er-Konto waeren das 94 MB
 * gewesen, und auf dem Rueckweg ein POST derselben Groesse.
 *
 * Der Name wird gegen die Teileliste des Manifests geprueft und nicht gegen
 * ein Muster: Was nicht im Manifest steht, gibt es fuer diesen Weg nicht.
 * Damit ist auch ein `../` im Namen erledigt, ohne dass es dafuer eine eigene
 * Pruefung braeuchte.
 */
function edbak_paket_teil_lesen(string $kennung, string $datei, string $teil): ?string
{
    if (!edbak_kennung_gueltig($kennung) || edbak_paket_fassung($datei) !== 2) { return null; }
    if (!class_exists('ZipArchive')) { return null; }
    $pfad = edbak_ordner($kennung) . '/' . $datei;
    if (!is_file($pfad)) { return null; }
    $zip = new ZipArchive();
    if ($zip->open($pfad) !== true) { return null; }
    $manifest = json_decode((string)$zip->getFromName('manifest.json'), true);
    $erlaubt = is_array($manifest) ? array_map('strval', (array)($manifest['teile'] ?? [])) : [];
    if (!in_array($teil, $erlaubt, true)) { $zip->close(); return null; }
    $roh = $zip->getFromName($teil);
    $zip->close();
    return $roh === false ? null : $roh;
}

/* ---- Der Auftrag „Alle sichern" (S2/AP6, E-S2-14) ------------------------
 *
 * WARUM EINE WARTESCHLANGE UND KEINE HEURISTIK.
 *
 * Bis Web 12.0.0 gab es keinen Merkzettel: „Alle sichern" sortierte die
 * Konten nach dem Alter ihrer letzten Sicherung, arbeitete ab, bis die Zeit
 * knapp wurde, und verliess sich darauf, dass ein zweiter Klick dort
 * weitermacht, wo der erste aufgehoert hat — wer eben gesichert wurde, steht
 * ja hinten.
 *
 * Das traegt nur, wenn sich die Konten um mindestens einen ganzen Tag
 * unterscheiden: `edbak_stand_aus_karte()` rechnet in TAGEN. Wer heute alle
 * Konten sichert, hat danach lauter Nullen — und die Sortierung ist bei
 * Gleichstand beliebig. Der zweite Klick nimmt dann womoeglich dieselben
 * Konten noch einmal, und die letzten werden nie erreicht.
 *
 * Die Warteschlange sagt statt dessen, wer noch dran ist. Sie liegt in
 * `app_state`, wird nach JEDEM Konto fortgeschrieben (ein abgebrochenes
 * Haeppchen verliert damit hoechstens das laufende Konto) und wird von zwei
 * Seiten geleert: von der Schaltflaeche, solange die Anfrage Zeit hat, und
 * vom Wartungsjob, in Schueben.
 *
 * KEINE AUTOMATISCHEN SICHERUNGEN. Der Job arbeitet nur, wenn ein Auftrag
 * vorliegt — E-S2-19 hat naechtliche Konto-Sicherungen ausdruecklich
 * abgelehnt. Ohne Auftrag kostet er eine Abfrage.
 */

const EDBAK_AUFTRAG_SCHLUESSEL = 'adminbackup_auftrag';

/**
 * EIN ZEIGER, KEINE LISTE — und das ist keine Sparsamkeit, sondern die
 * Konsequenz aus einem Fehlschlag (S2/AP6).
 *
 * Die erste Fassung legte die Kennungen aller offenen Konten als Feld in die
 * Marke. `app_state.v` ist `varchar(190)`; bei 31 Konten waren es schon 350
 * Zeichen. Das INSERT scheiterte, `edbak_marke_setzen()` schluckte es, und
 * die Schaltflaeche meldete „0 von 0 Konten gesichert".
 *
 * Ein Zeiger passt immer: Gearbeitet wird in der Reihenfolge der Kennung
 * (`users.id`), und die Marke merkt sich, wie weit es ist.
 *
 * WAS DAMIT WEGFAELLT: „aelteste Sicherung zuerst". Das war ohnehin nie eine
 * Reihenfolge, sondern ein Ersatz dafuer — gerechnet wurde in TAGEN, und bei
 * Gleichstand war sie beliebig. Was der Auftrag zusagt, ist etwas anderes und
 * Belastbareres: JEDES Konto genau einmal, und ein Abbruch verliert
 * hoechstens das laufende.
 */
function edbak_auftrag_lesen(): ?array
{
    $roh = edbak_marke_lesen(EDBAK_AUFTRAG_SCHLUESSEL);
    if ($roh === null || trim($roh) === '') { return null; }
    $a = json_decode($roh, true);
    if (!is_array($a) || !isset($a['cur'])) { return null; }
    return $a + ['cur' => 0, 'ges' => 0, 'gut' => 0, 'feh' => 0, 'seit' => null];
}

/** Auftrag fortschreiben; null loescht ihn. */
function edbak_auftrag_schreiben(?array $a): bool
{
    return edbak_marke_setzen(EDBAK_AUFTRAG_SCHLUESSEL,
        $a === null ? '' : (string)json_encode($a, JSON_UNESCAPED_UNICODE));
}

/** Wie viele Konten mit Kontokennung warten noch? */
function edbak_auftrag_offen(array $a): int
{
    $st = db()->prepare("SELECT COUNT(*) FROM users
                          WHERE id > ? AND account_key IS NOT NULL AND account_key <> ''");
    $st->execute([(int)$a['cur']]);
    return (int)$st->fetchColumn();
}

/**
 * Einen Auftrag anlegen.
 *
 * Er umfasst alle Konten mit Kontokennung. Die Reihenfolge ist die der
 * Kennung — stabil, lueckenlos und mit einem Zeiger fortsetzbar. Ein Konto,
 * das WAEHREND des Auftrags angelegt wird, bekommt eine hoehere Kennung und
 * faehrt deshalb mit; eines, das geloescht wird, faellt einfach heraus.
 */
function edbak_auftrag_starten(): array
{
    $ges = (int)db()->query("SELECT COUNT(*) FROM users
                              WHERE account_key IS NOT NULL AND account_key <> ''")
                    ->fetchColumn();
    $a = ['cur' => 0, 'ges' => $ges, 'gut' => 0, 'feh' => 0,
          'seit' => gmdate('Y-m-d\TH:i:s\Z')];
    edbak_auftrag_schreiben($a);
    return $a;
}

/**
 * Einen Schub abarbeiten — von der Schaltflaeche wie vom Job.
 *
 * @param callable $zeitLinks Sekunden, die noch bleiben.
 * @param float    $reserve   So viel Zeit muss fuer EIN Konto uebrig sein.
 * @return array{erledigt:int,offen:int,auftrag:?array}
 */
function edbak_auftrag_schub(callable $zeitLinks, float $reserve): array
{
    $a = edbak_auftrag_lesen();
    if ($a === null) { return ['erledigt' => 0, 'offen' => 0, 'auftrag' => null,
                               'meldungen' => []]; }

    $pdo = db();
    $naechstes = $pdo->prepare("SELECT id FROM users
                                 WHERE id > ? AND account_key IS NOT NULL
                                   AND account_key <> ''
                                 ORDER BY id LIMIT 1");
    $n = 0;
    /* DIE MELDUNGEN DIESES SCHUBS, entdoppelt: Bei erreichter Speichergrenze
     * scheitert JEDES Konto mit derselben Zeile. Dreihundertmal dieselbe ist
     * keine Auskunft, sondern eine Wand. Sie gehen an den Aufrufer und nicht
     * in die Marke — dort waere ihretwegen wieder kein Platz. */
    $meldungen = [];
    while (true) {
        if ($zeitLinks() < $reserve) { break; }
        /* DER SPEICHER IST HIER DIE ENGERE GRENZE, nicht die Zeit: Ein Konto
         * mit 5000 Einsaetzen kostet 24 MB. Was darueber liegt, reicht fuer
         * ein weiteres womoeglich nicht mehr — und ein Abbruch mitten im Bau
         * kostet mehr als ein Schub, der eines frueher aufhoert. */
        if (function_exists('jobs_speicher_knapp') && jobs_speicher_knapp()) { break; }

        $naechstes->execute([(int)$a['cur']]);
        $id = (int)($naechstes->fetchColumn() ?: 0);
        if ($id === 0) { break; }

        [$ok, $grund, ] = edbak_sicherung_erzeugen($id);
        if ($ok) { $a['gut']++; }
        else {
            $a['feh']++;
            if ($grund !== null && !in_array($grund, $meldungen, true)) {
                $meldungen[] = $grund;
            }
        }
        $a['cur'] = $id;
        $n++;
        /* NACH JEDEM KONTO FORTSCHREIBEN. Ein Haeppchen, das mittendrin
         * abbricht, verliert damit hoechstens das laufende Konto — und nicht
         * den ganzen Schub. */
        edbak_auftrag_schreiben($a);
    }

    $offen = edbak_auftrag_offen($a);
    if ($offen === 0) { edbak_auftrag_schreiben(null); }
    return ['erledigt' => $n, 'offen' => $offen, 'auftrag' => $a,
            'meldungen' => $meldungen];
}

