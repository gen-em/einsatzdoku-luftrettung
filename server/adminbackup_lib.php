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
 * Installation (`app_state.adminbackup_aufbewahrung`, Seite „Sicherungen") —
 * die drei bleiben als Vorbelegung stehen, damit ein Bestand, der die
 * Einstellung nie angefasst hat, sich weiter genau so verhält wie vorher.
 */
const EDBAK_AUFBEWAHRUNG_VORGABE = 3;

/** Vorbelegung der Erinnerung in Tagen, änderbar im Admin-Bereich (A8.4). */
const EDBAK_INTERVALL_VORGABE = 30;

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

/** Dateiname eines Pakets: Zeitpunkt (sortierbar) plus Zufallsanteil (E16). */
function edbak_paketname(): string
{
    return gmdate('Y-m-d\TH-i-s\Z') . '_' . bin2hex(random_bytes(4)) . '.json';
}

function edbak_paketname_gueltig(string $name): bool
{
    return preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}-\d{2}-\d{2}Z_[a-f0-9]{8}\.json$/', $name) === 1;
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
    $daten = json_decode(edbak_build($userId), true);
    if (!is_array($daten)) { return [false, 'Das Datenpaket liess sich nicht erzeugen.', null]; }

    $ordnerNeu = !is_dir($ordner);
    if ($ordnerNeu && !@mkdir($ordner, 0770, true) && !is_dir($ordner)) {
        return [false, 'Der Ordner für dieses Konto lässt sich nicht anlegen.', null];
    }

    /* 'diensttage' statt 'flugtage' (Abschnitt 3.9). Der alte Schluessel bleibt
     * ausdruecklich NICHT stehen: Diese Zahlen werden je Sicherung neu
     * geschrieben und nur zur Anzeige gelesen — admin_sicherungen.php faellt
     * fuer aeltere Eintraege auf 0 zurueck, und eine Zahl, die dort fehlt, ist
     * kein Datenverlust.
     *
     * 'rests' war schon vorher der falsche Schluessel: edbak_build() liefert die
     * Ruhesegmente unter 'rest_segments'. Die Zahl stand deshalb immer auf 0.
     * Hier berichtigt. */
    /* 'papierkorb' als Unterblock (E-S1-02). Seit Nutzlast 7 enthaelt jede
     * Sicherung den Papierkorb; die Zahl daneben ist die einzige Stelle, an
     * der das SICHTBAR wird, ohne die Datei zu oeffnen. Additiv — die
     * Paketversion der Admin-Sicherung bleibt deshalb 1, und
     * admin_sicherungen.php faellt fuer aeltere Eintraege auf null zurueck
     * (kein Papierkorb-Zusatz statt einer erfundenen Null). */
    $imPapierkorb = static function (array $zeilen): int {
        $n = 0;
        foreach ($zeilen as $z) {
            if (is_array($z) && ($z['deleted_at'] ?? null) !== null) { $n++; }
        }
        return $n;
    };
    $umfang = [
        'einsaetze'  => count($daten['missions'] ?? []),
        'diensttage' => count($daten['days'] ?? []),
        'ruhezeiten' => count($daten['rest_segments'] ?? []),
        'papierkorb' => [
            'einsaetze'  => $imPapierkorb($daten['missions'] ?? []),
            'diensttage' => $imPapierkorb($daten['days'] ?? []),
            'ruhezeiten' => $imPapierkorb($daten['rest_segments'] ?? []),
        ],
    ];

    $paket = [
        'format'      => 'einsatzdoku-adminsicherung',
        'version'     => 1,
        'erzeugt'     => gmdate('Y-m-d\TH:i:s\Z'),
        'web_version' => WEB_VERSION,
        'konto'       => [
            'account_key' => $kennung,
            'email'       => (string)$u['email'],
            'name'        => $u['name'],
        ],
        /* Die beiden Hüllen liegen BEWUSST neben den Daten und nicht in
         * ihnen: edbak_build() beschreibt den Bestand, diese beiden Werte
         * beschreiben den Schlüssel dazu. Wer das Format später erweitert,
         * soll den Unterschied sehen. */
        'schluessel'  => [
            'pat_wrap_rc'   => $u['pat_wrap_rc'],
            'pat_key_check' => $u['pat_key_check'],
        ],
        'umfang'      => $umfang,
        'daten'       => $daten,
    ];

    $name = edbak_paketname();
    $tmp  = $ordner . '/' . $name . '.tmp';
    if (@file_put_contents($tmp, json_encode($paket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) === false
        || !@rename($tmp, $ordner . '/' . $name)) {
        @unlink($tmp);
        /* Nur einen Ordner aufraeumen, den DIESER Aufruf angelegt hat, und
         * nur, wenn nichts darin steht. @rmdir scheitert an einem nicht leeren
         * Verzeichnis von selbst — aber die Absicht soll dastehen. */
        if ($ordnerNeu) { @rmdir($ordner); }
        return [false, 'Die Sicherung liess sich nicht schreiben.', null];
    }

    $begleit = edbak_begleit_lesen($kennung);
    $begleit['email'] = (string)$u['email'];
    $begleit['name']  = $u['name'];
    $begleit['letzte_sicherung'] = $paket['erzeugt'];
    $begleit['sicherungen'][] = [
        'datei'   => $name,
        'erzeugt' => $paket['erzeugt'],
        'umfang'  => $umfang,
    ];
    edbak_begleit_schreiben($kennung, $begleit);

    $verdraengt = edbak_verdraengen($kennung);
    edbak_marke_setzen('adminbackup_last', gmdate('Y-m-d'));

    return [true, null, ['datei' => $name, 'umfang' => $umfang, 'verdraengt' => $verdraengt]];
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
function edbak_paket_lesen(string $kennung, string $datei): ?array
{
    if (!edbak_kennung_gueltig($kennung) || !edbak_paketname_gueltig($datei)) { return null; }
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
    foreach (scandir($ordner) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        /* Nur, was wir selbst anlegen. Ein fremder Eintrag im Ordner wäre ein
         * Befund und kein Grund, blind zu löschen. */
        if ($n !== 'konto.json' && !edbak_paketname_gueltig($n)) { return false; }
        @unlink($ordner . '/' . $n);
    }
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

function edbak_marke_setzen(string $k, string $v): void
{
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
        $c = &edbak_marken_speicher();
        $c[$k] = $v;
    } catch (Throwable) {
        // Eine nicht schreibbare Marke darf die Sicherung selbst nicht scheitern lassen.
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

/** Dateigrösse: KB unter einem Megabyte, sonst MB mit einer Nachkommastelle. */
function edbak_groesse_text(int $bytes): string
{
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
function edbak_ablage_zahlen(): array
{
    $wurzel = edbak_wurzel();
    $z = ['ordner' => 0, 'pakete' => 0, 'bytes' => 0];
    if (!is_dir($wurzel)) { return $z; }
    foreach (scandir($wurzel) ?: [] as $n) {
        if (!edbak_kennung_gueltig($n) || !is_dir($wurzel . '/' . $n)) { continue; }
        $z['ordner']++;
        foreach (scandir($wurzel . '/' . $n) ?: [] as $d) {
            if (!edbak_paketname_gueltig($d)) { continue; }
            $z['pakete']++;
            $z['bytes'] += (int)@filesize($wurzel . '/' . $n . '/' . $d);
        }
    }
    return $z;
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
