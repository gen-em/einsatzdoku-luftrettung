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
 * Die Sicherungsdatei traegt die geschuetzten Angaben im KLARTEXT — der
 * Browser entschluesselt vor dem Versiegeln, damit sich eine Sicherung in
 * jedes Konto einspielen laesst. Fuer die Fixture waere das genau falsch:
 * Sie soll den CHIFFRETEXT unveraendert mitfuehren und daneben das
 * Schluesselmaterial, mit dem er lesbar ist. Erst dadurch kann der Server
 * das Konto ohne jede Entschluesselung zuruecksetzen — und erst dadurch ist
 * der Reset schnell genug, um bei jeder Anfrage laufen zu koennen.
 *
 * Die Quelle ist deshalb `edbak_build()`: dieselbe Funktion, die auch die
 * Sicherung aufbaut, aber SERVERSEITIG — dort steht `pat_blob` noch als
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

/* ---- Geraete ------------------------------------------------------------- */
$st = $pdo->prepare('SELECT device_id, api_key_hash, label FROM devices
                     WHERE user_id = ? ORDER BY id');
$st->execute([$id]);
$geraete = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---- Bestand, serverseitig (Chiffretext bleibt Chiffretext) -------------- */
/* MIT PAPIERKORB. Die Fixture soll den Referenzzustand VOLLSTAENDIG
 * abbilden, und dazu gehoert ein gefuellter Papierkorb — die
 * Abdeckungsmatrix fuehrt ihn ausdruecklich. `edbak_build()` filtert ihn
 * fuer eine NutzerInnen-Sicherung heraus; hier nicht.
 *
 * Die Eintraege kommen beim Einspielen als AKTIVE zurueck (der Einspielweg
 * wertet `deleted_at` nicht aus). Das Nachlauf-Drehbuch legt sie danach
 * ueber die regulaeren Loeschwege wieder in den Papierkorb — so, wie eine
 * Nutzerin es taete (E-P1-21). */
$daten = json_decode(edbak_build($id, true), true);
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

/* ---- Nachlauf-Drehbuch: was liegt jetzt im Papierkorb? ------------------- */
$st = $pdo->prepare('SELECT client_ref FROM missions
                     WHERE user_id = ? AND deleted_at IS NOT NULL
                       AND (deleted_with_day = 0 OR deleted_with_day IS NULL)
                     ORDER BY started_at');
$st->execute([$id]);
$papierEinsaetze = $st->fetchAll(PDO::FETCH_COLUMN);

/* Diensttage ueber ihre Dienstkennung, nicht ueber das Datum: Seit E9 koennen
 * zwei Dienste auf einem Kalendertag liegen. */
$st = $pdo->prepare('SELECT r.day_ref FROM days d
                     JOIN day_refs r ON r.day_id = d.id
                     WHERE d.user_id = ? AND d.deleted_at IS NOT NULL
                     ORDER BY d.started_at');
$st->execute([$id]);
$papierTage = $st->fetchAll(PDO::FETCH_COLUMN);

/* ---- Zusammensetzen ------------------------------------------------------ */
$fx = [
    'format'      => 'einsatzdoku-demo-fixture',
    'version'     => 1,
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
    'nachlauf' => [
        'einsaetze'  => array_values($papierEinsaetze),
        'diensttage' => array_values($papierTage),
    ],
];

/* GEPACKT ABLEGEN. Unkomprimiert sind es rund 11 MB — im Wesentlichen
 * 52 484 Spurpunkte als JSON-Zahlen. Diese Datei liegt unter server/ und
 * geht damit bei jedem Deploy ueber FTPS mit; gepackt sind es weniger als
 * ein Zehntel. Gelesen wird sie ohnehin nur beim Anlegen und beim Reset,
 * und `gzdecode()` darauf kostet Bruchteile einer Sekunde.
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
printf("  Nachlauf     %d Einsaetze, %d Diensttage in den Papierkorb\n",
       count($papierEinsaetze), count($papierTage));
