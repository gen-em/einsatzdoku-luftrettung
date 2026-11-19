<?php
declare(strict_types=1);

/**
 * Demo-Fixture aus dem Referenzkonto erzeugen (Fixture-Format 4).
 *
 * Aufruf (auf der Maschine, auf der die Referenzinstallation laeuft):
 *   php tools/referenzdatensatz/fixture/erzeugen.php [email] [ziel.json.gz]
 *
 * Anlass: Nr. 388 — seit Web 23 brach dieses Skript mit Rueckgabe 2 ab,
 * und die eingecheckte Fixture liess sich nicht mehr neu erzeugen.
 *
 * ZWEI SCHRITTE, EIN AUFRUF (seit KA-13, Nr. 563):
 *
 *   1. DIESES SKRIPT liest das Konto, wie es liegt, und schreibt einen
 *      ROHBESTAND in eine Zwischendatei: Konto- und Schluesselmaterial,
 *      Geraete, den Bestand ueber `edbak_build()` (Kopf und Fenster —
 *      dieselbe Funktion, die die Sicherung fuer den Browser baut), die
 *      Spurstuecke ueber `spur2_lesen_viele()` und die Blaetter des
 *      Standbuchs. Es oeffnet NICHTS — der Server hat dafuer mit Absicht
 *      keine Funktion.
 *   2. `verschliessen.py` oeffnet den Rohbestand (Fassung 2, nach den
 *      Regeln der Referenz in `tools/proben/huelle/`) und verschliesst ihn
 *      neu: Geraetehuelle Fassung 2 von den Demo-Geraeten mit festem
 *      Schluessel, `edk2:`, Standbuch, Geraetebuch, Kontopaar. Was dabei wer
 *      verschliesst und warum, steht in seinem Kopf.
 *
 * BIS KA-13 oeffnete dieses Skript Stuecke und Einsatzhuellen selbst (in
 * PHP, nur Fassung 1) und schrieb Format 3 — Ortsdaten im Klartext, die der
 * Reset auf der Zielanlage in Fassung 1 verschloss. Mit umgelegter Weiche
 * liegt in einem Konto beides nebeneinander (Geraete, die neu gekoppelt
 * haben, senden Fassung 2; der Browser schreibt `edk2:`), und Fassung 1
 * truege keinen Herkunftsnachweis. Das Oeffnen steht deshalb an EINER
 * Stelle, in Python, bei der Referenz.
 *
 * WARUM NICHT AUS DER .edbak: Ihr Kern traegt `pat_blob` verschluesselt und
 * die Ortsdaten so, wie der Browser sie beim Sichern geoeffnet hat; den
 * Schluessel, unter dem die Datei versiegelt ist, kennt nur, wer sie
 * herunterlaedt, und das Schluesselmaterial des Kontos fehlt in ihr (F-RD-02).
 *
 * DIESES SKRIPT LIEST NUR. Es schreibt zwei Dateien, sonst nichts.
 *
 * VORHER DIE JOBS ANHALTEN — sonst stimmt die Punktzahl nicht.
 *
 *     php server/jobs.php --pause 1800      (0 hebt auf; es
 *                                            geht auch als Knopf unter
 *                                            Betrieb -> Hintergrundjobs)
 *
 * Was zwischen dem Herstellen des Referenzbestands und dem Erzeugen an
 * Hintergrundarbeit laeuft, landet still in der Fixture (gemessen am
 * 12.09.2026: 47 576 statt 55 861 Punkten). Die Zahl steht am Ende des
 * Laufs; wer sie nicht gegen die erwartete haelt, merkt es nicht.
 *
 * `NADOKU_SERVER` (Umgebung) nennt ein anderes `server/` als das des
 * Repositoriums — fuer eine Anlage, die aus einer Abschrift bedient wird.
 */

$email = $argv[1] ?? 'demo@gen-em.org';
$ziel  = $argv[2] ?? (__DIR__ . '/../../../server/demo/fixture.json.gz');

$wurzel = dirname(__DIR__, 3);
$server = realpath(getenv('NADOKU_SERVER') ?: $wurzel . '/server');
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/backup_lib.php';
require_once $server . '/version.php';
require_once $server . '/demo_lib.php';
require_once $server . '/kontopaar_lib.php';
require_once $server . '/spur_lib.php';
require_once $server . '/standbuch_lib.php';

/** Abbrechen: Meldung nach STDERR, Rueckgabe 2, keine Datei. */
function abbruch(string $text): never
{
    fwrite(STDERR, $text . (str_ends_with($text, "\n") ? '' : "\n"));
    exit(2);
}

$pdo = db();

/* ---- Konto- und Schluesselmaterial --------------------------------------- */
$st = $pdo->prepare('SELECT id, email, name, password_hash, kdf_salt, kdf_iter,
                            pat_wrap_pw, pat_wrap_rc, pat_key_check, role, account_key
                     FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch(PDO::FETCH_ASSOC);
if (!$u) { abbruch("Kein Konto $email"); }
if ($u['role'] !== 'user') {
    abbruch("Das Demo-Konto muss die Rolle 'user' haben, hat aber '{$u['role']}'.");
}
/* DIE RUNDENZAHL DER FIXTURE IST `KDF_ITER_ZIEL` — durch Bau, nicht durch
 * einen Riegel. Bis KA-13 schrieb dieses Skript `kdf_iter` und die Huelle
 * des Kontos unveraendert in die Fixture und brach ab, wenn das Konto unter
 * dem Ziel stand: `api/kdf_upgrade.php` ueberspringt das Demo-Konto, ein
 * Altwert in der Fixture kaeme also nie mehr aus `KDF_ITER_LISTE` heraus.
 * Jetzt rechnet `verschliessen.py` Huelle und Anmelde-Hash selbst aus dem
 * Demo-Passwort, mit der Zahl, die hier mitgeht — die Fixture traegt das
 * Ziel, auf welcher Zahl das Konto auch steht. */

/* DIE HUELLE DES KONTOS TRAEGT KEINEN ANTEIL (`edk2:`), SONST ABBRUCH.
 *
 * Der Datenschluessel haengt zusaetzlich am Server-Anteil aus
 * `config.php`. Eine Huelle, die damit gebildet wurde, traegt das Praefix
 * `edka2:<kennung>:` — und sie laesst sich nur auf DER Installation oeffnen,
 * deren Anteil diese Kennung hat.
 *
 * Die Fixture geht aber gerade NICHT dorthin zurueck, aus der sie stammt:
 * Sie liegt unter `server/demo/` und wird beim Deploy auf die
 * Produktivinstallation gelegt, die ihren eigenen Anteil fuehrt. Eine
 * `edka2:`-Huelle in der Fixture hiesse dort: Das Demo-Konto meldet sich an
 * (der bcrypt-Hash passt), und danach laesst sich nichts oeffnen. Bei jedem
 * Reset aufs Neue, ohne Meldung.
 *
 * Dass es nicht dazu kommt, ist heute an zwei Stellen abgesichert, und beide
 * sind fuer sich allein zu wenig:
 *
 *   - `api/kdf_upgrade.php` ueberspringt das Demo-Konto, die stille
 *     Umstellung fasst es also nicht an. Das ist eine Zusage ueber den
 *     REGULAEREN Weg — sie sagt nichts ueber eine Huelle, die jemand von Hand
 *     gesetzt hat oder die aus einem anderen Bestand stammt.
 *   - `huelle_pw_pruefen($wrap, istDemo: true)` in `serverkrypto_lib.php`
 *     weist eine `edka2:`-Huelle fuer das Demo-Konto ab. Das greift auf den
 *     SCHREIBWEGEN der Anwendung — der Demo-Reset in `demo_lib.php` schreibt
 *     mit eigenem SQL und kommt dort nicht vorbei.
 *
 * Deshalb steht der Riegel hier, an der Stelle, an der die Datei entsteht.
 * Dieselbe Bauart wie der Riegel auf `KDF_ITER_ZIEL` darueber: Wer
 * eine Fixture erzeugt, die spaeter niemanden mehr hereinlaesst, soll es
 * JETZT erfahren und nicht nach dem Deploy.
 *
 * `pat_wrap_rc` wird mitgeprueft, obwohl sie nie einen Anteil tragen darf
 * (`huelle_rc_pruefen()`): Sie ist der einzige Rueckweg, wenn der
 * Anteil verlorengeht, und eine Fixture, die ihn mit einem Anteil verbindet,
 * nimmt dem Demo-Konto genau den. Zwei Zeilen, die nie zuschlagen sollten —
 * das ist der Sinn eines Riegels.
 *
 * DER RIEGEL STEHT VOR JEDEM OEFFNEN. Eine `edka2:`-Huelle laesst sich mit
 * `DEMO_PASSWORT` allein nicht oeffnen; stuende das Oeffnen davor, kaeme die
 * Meldung „Passwort passt nicht" statt der, die den Grund nennt. */
foreach (['pat_wrap_pw' => 'Schluesselhuelle',
          'pat_wrap_rc' => 'Wiederherstellungs-Huelle'] as $spalte => $name) {
    $huelle = (string)($u[$spalte] ?? '');
    if ($huelle === '') { continue; }
    if (!str_starts_with($huelle, 'edk2:')) {
        abbruch(sprintf(
            "ABBRUCH: Die %s von %s traegt `%s`, erwartet ist `edk2:`.\n"
          . "Die Fixture wird auf einer Installation mit einem ANDEREN\n"
          . "Server-Anteil eingespielt; eine Huelle mit Anteil ist dort nicht zu\n"
          . "oeffnen. Das Demo-Konto kaeme herein und saehe nichts.\n"
          . "Weg zurueck: Passwort des Demo-Kontos im Browser neu setzen,\n"
          . "solange `config.php` KEINEN `kdf_anteil` fuehrt -- oder\n"
          . "`tools/proben/anteil/huelle_stellen.py %s <passwort> edk2`.\n",
            $name, $email, explode(':', $huelle)[0] . ':', $email));
    }
}

$id = (int)$u['id'];

/* ---- Das Kontopaar: beide Teile, wie sie liegen -------------------------
 *
 * Der oeffentliche als SPKI (aus dem Siegel des Servers), der private als
 * Chiffretext unter dem Inhaltsschluessel. Ohne Paar laesst sich nichts
 * oeffnen — es entsteht beim ersten Entsperren nach einer Anmeldung. */
$kp = kp_zustand($id, $pdo);
if ($kp['stand'] !== 'da' || $kp['privat'] === null) {
    abbruch("ABBRUCH: $email hat kein Schluesselpaar (Stand {$kp['stand']}). Es entsteht beim "
          . "ersten Entsperren nach einer Anmeldung.");
}

/* ---- Geraete -------------------------------------------------------------
 *
 * OHNE das virtuelle Geraet "Manuelle Einträge" (GERAETE_ECHT_SQL): Es traegt
 * die Kontonummer im Namen und gilt nur fuer das Konto, aus dem diese Fixture
 * stammt. ART UND MODELL WANDERN MIT — sonst zeigte das Demo-Konto auf der
 * Geraeteseite „Gerät unbekannt". Der Geraeteschluessel (`spk`) geht mit,
 * wo es ihn gibt: `verschliessen.py` braucht ihn nicht (die Demo-Geraete
 * bekommen ihren festen), aber das Geraetebuch des Kontos nennt ihn. */
$mitSpk = db_hat_spalte($pdo, 'devices', 'herkunft');
$st = $pdo->prepare('SELECT device_id, api_key_hash, label, geraet_art, geraet_modell'
                    . ($mitSpk ? ', spk, herkunft' : '') . '
                     FROM devices
                     WHERE user_id = ? AND ' . GERAETE_ECHT_SQL . ' ORDER BY id');
$st->execute([$id]);
$geraete = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---- Bestand, serverseitig: Kopf und Fenster ----------------------------
 *
 * MIT PAPIERKORB — `edbak_build()` nimmt ihn von sich aus mit, und
 * `edbak_restore()` bringt ihn als Papierkorb zurueck. */
$daten = json_decode(edbak_build($id, true, ['kopf' => true]), true);
if (!is_array($daten)) { abbruch('edbak_build (Kopf) lieferte kein JSON'); }
$daten['missions'] = [];
$daten['rest_segments'] = [];
$spurIndex = [];
for ($ab = 0, $gesamt = 1; $ab < $gesamt; $ab += EDBAK_FENSTER) {
    $f = json_decode(edbak_build($id, true, ['ab' => $ab, 'anzahl' => EDBAK_FENSTER]), true);
    if (!is_array($f)) { abbruch("edbak_build (Fenster ab $ab) lieferte kein JSON"); }
    $gesamt = (int)$f['gesamt'];
    array_push($daten['missions'], ...$f['missions']);
    array_push($daten['rest_segments'], ...$f['rest_segments']);
    array_push($spurIndex, ...$f['_spur_index']);
}
if (count($daten['missions']) + count($daten['rest_segments']) !== $gesamt) {
    abbruch('Die Fenster ergeben ' . (count($daten['missions']) + count($daten['rest_segments']))
          . " Eintraege, der Bestand nennt $gesamt.");
}

/* Gegenprobe: Der Kern darf keine Klartext-Ortsdaten tragen. Steht dort
 * welcher, stammt er nicht aus den regulaeren Wegen. */
$fund = edbak_klartext_fund($daten);
if ($fund !== null) { abbruch("ABBRUCH: Klartext-Ortsdaten im Kern: $fund"); }
foreach ($daten['missions'] as $m) {
    if (isset($m['pat'])) { abbruch("ABBRUCH: {$m['client_ref']} fuehrt Klartext (`pat`)."); }
}

/* ---- Spurstuecke, wie sie liegen ---------------------------------------- */
$idNach = ['mission' => [], 'rest' => []];
foreach ($spurIndex as $e) { $idNach[$e['art']][(int)$e['spur_ref']] = (int)$e['id']; }
$spuren = [];
foreach (['mission', 'rest'] as $art) {
    $stuecke = spur2_lesen_viele($pdo, $art, array_values($idNach[$art]));
    foreach ($idNach[$art] as $ref => $oid) {
        $liste = [];
        foreach ($stuecke[$oid] ?? [] as $s) {
            $liste[] = ['seq_from' => (int)$s['seq_from'], 'punkte' => (int)$s['punkte'],
                        'stufe' => (int)$s['stufe'], 'n_original' => (int)$s['n_original'],
                        'ts_von' => (int)$s['ts_von'], 'ts_bis' => (int)$s['ts_bis'],
                        'huelle' => (string)$s['huelle']];
        }
        if (!$liste) { abbruch("spur_ref $ref ($art) ohne Stuecke"); }
        $spuren[] = ['art' => $art, 'spur_ref' => (int)$ref, 'stuecke' => $liste];
    }
}

/* ---- Standbuch: die Blaetter und das Geraetebuch, wie sie liegen -------- */
$standbuch = [];
if (sb_bereit($pdo)) {
    $st = $pdo->prepare('SELECT wer, version, text FROM stand_blaetter WHERE user_id = ? ORDER BY wer');
    $st->execute([$id]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
        $standbuch[] = ['wer' => (int)$z['wer'], 'version' => (int)$z['version'], 'text' => (string)$z['text']];
    }
}

/* ---- Herkunft: der Commit des Referenzbestands ---------------------------
 *
 * Wer eine Fixture neben eine Referenz legt, will wissen, aus welchen
 * Quelldaten sie stammt. Sind die Quelldaten seit dem Commit veraendert,
 * steht das dabei — sonst nennte die Fixture einen Stand, den es nicht gibt. */
$commit = trim((string)shell_exec('git -C ' . escapeshellarg($wurzel) . ' rev-parse --short HEAD 2>/dev/null'));
$geaendert = trim((string)shell_exec('git -C ' . escapeshellarg($wurzel)
    . ' status --porcelain -- tools/referenzdatensatz/quelldaten tools/referenzdatensatz/generator 2>/dev/null'));
$quelle = 'tools/referenzdatensatz @ ' . ($commit !== '' ? $commit : 'unbekannt')
        . ($geaendert !== '' ? ' (Quelldaten veraendert)' : '');

/* ---- Der Rohbestand ------------------------------------------------------ */
$roh = [
    'format'      => 'einsatzdoku-demo-rohbestand',
    'version'     => 1,
    'erzeugt_am'  => gmdate('c'),
    'web_version' => WEB_VERSION,
    'quelle'      => $quelle,
    'konto' => [
        'email'          => $u['email'],
        'name'           => $u['name'],
        'kdf_salt'       => $u['kdf_salt'],
        'kdf_iter'       => (int)$u['kdf_iter'],
        'pat_wrap_pw'    => $u['pat_wrap_pw'],
        'account_key'    => $u['account_key'],
        'kp_oeffentlich' => $kp['oeffentlich'],
        'kp_privat'      => $kp['privat'],
    ],
    'geraete'   => $geraete,
    'daten'     => $daten,
    'spuren'    => $spuren,
    'standbuch' => $standbuch,
];
$json = json_encode($roh, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) { abbruch('json_encode: ' . json_last_error_msg()); }
/* Die Zwischendatei liegt im Systemtempordner und faellt am Ende weg: Sie
 * traegt nichts, was das Konto nicht auch traegt, aber zwei Dateien desselben
 * Bestands laufen auseinander, sobald jemand eine davon aufhebt. */
$zwischen = sys_get_temp_dir() . '/demo-rohbestand-' . bin2hex(random_bytes(4)) . '.json.gz';
if (file_put_contents($zwischen, gzencode($json, 1)) === false) { abbruch("Nicht schreibbar: $zwischen"); }

printf("Rohbestand gelesen: %s, Web %s, Quelle %s\n", $u['email'], WEB_VERSION, $quelle);
printf("  %d Geraete, %d Diensttage, %d Einsaetze, %d Ruhesegmente, %d Spuren, %d Blaetter\n",
       count($geraete), count($daten['days'] ?? []), count($daten['missions']),
       count($daten['rest_segments']), count($spuren), count($standbuch));

/* ---- Neu verschliessen --------------------------------------------------- */
@mkdir(dirname($ziel), 0775, true);
$p = proc_open(['python3', __DIR__ . '/verschliessen.py', $zwischen, $ziel,
                '--runden', (string)KDF_ITER_ZIEL], [], $rohre);
$rc = is_resource($p) ? proc_close($p) : 2;
@unlink($zwischen);
exit($rc);
