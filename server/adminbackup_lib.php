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

/** Höchstens drei Sicherungen je Konto; die vierte verdrängt die älteste (E18). */
const EDBAK_MAX_JE_KONTO = 3;

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
    if (!is_dir($ordner) && !@mkdir($ordner, 0770, true) && !is_dir($ordner)) {
        return [false, 'Der Ordner für dieses Konto lässt sich nicht anlegen.', null];
    }

    $daten = json_decode(edbak_build($userId), true);
    if (!is_array($daten)) { return [false, 'Das Datenpaket liess sich nicht erzeugen.', null]; }

    /* 'diensttage' statt 'flugtage' (Abschnitt 3.9). Der alte Schluessel bleibt
     * ausdruecklich NICHT stehen: Diese Zahlen werden je Sicherung neu
     * geschrieben und nur zur Anzeige gelesen — admin_sicherungen.php faellt
     * fuer aeltere Eintraege auf 0 zurueck, und eine Zahl, die dort fehlt, ist
     * kein Datenverlust.
     *
     * 'rests' war schon vorher der falsche Schluessel: edbak_build() liefert die
     * Ruhesegmente unter 'rest_segments'. Die Zahl stand deshalb immer auf 0.
     * Hier berichtigt. */
    $umfang = [
        'einsaetze'  => count($daten['missions'] ?? []),
        'diensttage' => count($daten['days'] ?? []),
        'ruhezeiten' => count($daten['rest_segments'] ?? []),
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
 * Älteste Sicherungen entfernen, bis höchstens drei übrig sind (E18).
 *
 * KEINE ALTERSGRENZE. Bei rein manueller Auslösung würde eine Altersgrenze
 * genau die letzte vorhandene Sicherung entfernen, wenn lange keine neue
 * erzeugt wurde — also in der Lage, in der man sie braucht. Die Anzahlgrenze
 * greift nur, wenn tatsächlich eine neuere existiert.
 *
 * Das ist die zugesagte Verdrängung und KEINE Löschhandlung im Sinne von
 * A8.8: Sie braucht deshalb keine Bestätigung (Akzeptanzkriterium 60).
 */
function edbak_verdraengen(string $kennung): array
{
    $pakete = edbak_pakete($kennung);          // neueste zuerst
    $weg = array_slice($pakete, EDBAK_MAX_JE_KONTO);
    if (!$weg) { return []; }
    $namen = [];
    foreach ($weg as $p) {
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
function edbak_marke_lesen(string $k): ?string
{
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return $v === false ? null : (string)$v;
    } catch (Throwable) {
        return null;   // app_state fehlt (Migration noch nicht gelaufen)
    }
}

function edbak_marke_setzen(string $k, string $v): void
{
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')->execute([$k, $v]);
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
