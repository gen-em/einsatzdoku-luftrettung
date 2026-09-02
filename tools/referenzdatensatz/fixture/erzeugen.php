<?php
declare(strict_types=1);

/**
 * Demo-Fixture aus dem Referenzkonto erzeugen (Arbeitspaket B6, E-P1-08).
 *
 * Aufruf (auf der Maschine, auf der die Referenzinstallation laeuft):
 *   php tools/referenzdatensatz/fixture/erzeugen.php [email] [ziel.json]
 *
 * WAS HIER ENTSTEHT und warum es NICHT aus der .edbak kommen kann:
 *
 * Die Backup-Datei traegt die geschuetzten Angaben im KLARTEXT — der
 * Browser entschluesselt vor dem Versiegeln, damit sich ein Backup in
 * jedes Konto einspielen laesst. Fuer die Fixture waere das genau falsch:
 * Sie soll den CHIFFRETEXT unveraendert mitfuehren und daneben das
 * Schluesselmaterial, mit dem er lesbar ist. Erst dadurch kann der Server
 * das Konto ohne jede Entschluesselung zuruecksetzen — und erst dadurch ist
 * der Reset schnell genug, um bei jeder Anfrage laufen zu koennen.
 *
 * Die Quelle ist deshalb `edbak_build()`: dieselbe Funktion, die auch das
 * Backup aufbaut, aber SERVERSEITIG — dort steht `pat_blob` noch als
 * Chiffretext. Genau die Form, die `edbak_restore()` als Spalte wieder
 * annimmt.
 *
 * DIESES SKRIPT LIEST NUR. Es schreibt eine Datei, sonst nichts.
 */

$email = $argv[1] ?? 'demo@gen-em.org';
$ziel  = $argv[2] ?? (__DIR__ . '/../../../server/demo/fixture.json.gz');

$server = realpath(__DIR__ . '/../../../server');
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/backup_lib.php';
require_once $server . '/version.php';

$pdo = db();

/* ---- Konto- und Schluesselmaterial --------------------------------------- */
$st = $pdo->prepare('SELECT id, email, name, password_hash, kdf_salt, kdf_iter,
                            pat_wrap_pw, pat_wrap_rc, pat_key_check, role, account_key
                     FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { fwrite(STDERR, "Kein Konto $email\n"); exit(2); }
if ($u['role'] !== 'user') {
    fwrite(STDERR, "Das Demo-Konto muss die Rolle 'user' haben, hat aber '{$u['role']}'.\n");
    exit(2);
}
$id = (int)$u['id'];

/* ---- Geraete -------------------------------------------------------------
 *
 * OHNE das virtuelle Geraet "Manuelle Einträge" (GERAETE_ECHT_SQL). Es traegt
 * die Kontonummer im Namen und gilt nur fuer das Konto, aus dem diese Fixture
 * stammt; im Demo-Konto entsteht es bei Bedarf mit der richtigen Nummer von
 * selbst. Bis Web 8.0.0 stand es mit in der Datei und brach das Anlegen des
 * Demo-Kontos ab, sobald eine Installation beide Bestaende fuehrte
 * (device_id ist global eindeutig). Der Filter in demo_lib.php faengt das
 * auch fuer bereits ausgelieferte Fixtures ab; hier kommt es gar nicht erst
 * hinein. */
$st = $pdo->prepare('SELECT device_id, api_key_hash, label FROM devices
                     WHERE user_id = ? AND ' . GERAETE_ECHT_SQL . ' ORDER BY id');
$st->execute([$id]);
$geraete = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---- Bestand, serverseitig (Chiffretext bleibt Chiffretext) -------------- */
/* MIT PAPIERKORB — und dafuer ist seit S1 nichts mehr zu tun. `edbak_build()`
 * nimmt ihn von sich aus mit (E-S1-01), und `edbak_restore()` bringt ihn als
 * Papierkorb zurueck (E-S1-03/04). Bis Web 7.3.1 brauchte es hier ein Flag
 * und danach ein Nachlauf-Drehbuch, das die Eintraege ueber die regulaeren
 * Loeschwege wieder in den Papierkorb legte; beides ist entfallen. */
$daten = json_decode(edbak_build($id), true);
if (!is_array($daten)) { fwrite(STDERR, "edbak_build lieferte kein JSON\n"); exit(2); }

/* Gegenprobe: Die Fixture MUSS Chiffretext fuehren. Steht dort ein `pat`-
 * Objekt, stammt sie aus dem Browserweg und waere im Zielkonto unlesbar. */
$mitBlob = 0; $mitKlartext = 0;
foreach (($daten['missions'] ?? []) as $m) {
    if (isset($m['pat_blob']) && $m['pat_blob'] !== null) { $mitBlob++; }
    if (isset($m['pat'])) { $mitKlartext++; }
}
if ($mitKlartext > 0) {
    fwrite(STDERR, "ABBRUCH: $mitKlartext Einsaetze fuehren Klartext (`pat`).\n");
    exit(2);
}

/* ---- Papierkorb: nur noch ZAEHLEN, nicht mehr nachstellen ----------------
 *
 * Bis Web 7.3.1 stand hier ein Nachlauf-Drehbuch: eine Liste der Kennungen,
 * die der Demo-Reset nach dem Einspielen ueber die regulaeren Loeschwege
 * wieder in den Papierkorb legen sollte. Das war noetig, solange das
 * Backup-Format keine geloeschten Eintraege kannte (E-P1-21).
 *
 * Seit Nutzlast 7 traegt `daten` den Papierkorb selbst, und `edbak_restore()`
 * bringt ihn als Papierkorb zurueck. Das Drehbuch ist entfallen (E-S1-10);
 * die Zahlen werden nur noch fuer die Ausgabe unten ermittelt, damit der
 * Erzeuger sagen kann, was in der Fixture steckt. */
$papierkorb = ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0];
foreach (($daten['missions'] ?? []) as $m) {
    if (($m['deleted_at'] ?? null) !== null) { $papierkorb['einsaetze']++; }
}
foreach (($daten['days'] ?? []) as $d) {
    if (($d['deleted_at'] ?? null) !== null) { $papierkorb['diensttage']++; }
}
foreach (($daten['rest_segments'] ?? []) as $r) {
    if (($r['deleted_at'] ?? null) !== null) { $papierkorb['ruhezeiten']++; }
}

/* ---- Zusammensetzen ------------------------------------------------------ */
$fx = [
    'format'      => 'einsatzdoku-demo-fixture',
    /* FORMAT 2 (E-S1-10): ohne den Block `nachlauf`. Die Pflichtfelder sind
     * dieselben (`konto`, `daten`), und `demo_fixture_laden()` bleibt
     * tolerant — eine Fixture der Version 1 laesst sich weiterhin einspielen.
     * Die Nummer kennzeichnet, dass der Block weg ist, sie sperrt nichts. */
    'version'     => 2,
    'erzeugt_am'  => gmdate('c'),
    'web_version' => WEB_VERSION,
    'quelle'      => 'tools/referenzdatensatz — Phase P1',
    'konto' => [
        'email'         => $u['email'],
        'name'          => $u['name'],
        'password_hash' => $u['password_hash'],
        'kdf_salt'      => $u['kdf_salt'],
        'kdf_iter'      => (int)$u['kdf_iter'],
        'pat_wrap_pw'   => $u['pat_wrap_pw'],
        'pat_wrap_rc'   => $u['pat_wrap_rc'],
        'pat_key_check' => $u['pat_key_check'],
        'account_key'   => $u['account_key'],
    ],
    'geraete'  => $geraete,
    'daten'    => $daten,
];

/* GEPACKT ABLEGEN. Unkomprimiert sind es rund 2,4 MB — im Wesentlichen
 * 55 861 Spurpunkte als JSON-Zahlen (seit S1 mit denen des Papierkorbs).
 * Diese Datei liegt unter server/ und geht damit bei jedem Deploy ueber FTPS
 * mit; gepackt sind es rund 745 KB. Gelesen wird sie ohnehin nur beim
 * Anlegen und beim Reset, und `gzdecode()` darauf kostet Bruchteile einer
 * Sekunde.
 *
 * Die ungepackte Fassung wird NICHT zusaetzlich abgelegt: Zwei Dateien
 * desselben Inhalts laufen auseinander, sobald jemand eine davon anfasst. */
@mkdir(dirname($ziel), 0775, true);
$json = json_encode($fx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$gepackt = gzencode($json, 9);
file_put_contents($ziel, $gepackt);

printf("Fixture geschrieben: %s (%.1f KB gepackt, %.1f KB roh)\n",
       $ziel, strlen($gepackt) / 1024, strlen($json) / 1024);
printf("  Konto        %s (Rolle %s, kdf_iter %d)\n", $u['email'], $u['role'], (int)$u['kdf_iter']);
printf("  Geraete      %d\n", count($geraete));
printf("  Diensttage   %d\n", count($daten['days'] ?? []));
printf("  Einsaetze    %d  (davon %d mit Chiffretext)\n",
       count($daten['missions'] ?? []), $mitBlob);
printf("  Ruhesegmente %d\n", count($daten['rest_segments'] ?? []));
$punkte = 0;
foreach (($daten['missions'] ?? []) as $m) { $punkte += count($m['track'] ?? []); }
foreach (($daten['rest_segments'] ?? []) as $r) { $punkte += count($r['track'] ?? []); }
printf("  Spurpunkte   %d\n", $punkte);
printf("  Papierkorb   %d/%d/%d (Einsaetze/Diensttage/Ruhesegmente, in `daten` enthalten)\n",
       $papierkorb['einsaetze'], $papierkorb['diensttage'], $papierkorb['ruhezeiten']);
