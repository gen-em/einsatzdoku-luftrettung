<?php
declare(strict_types=1);

/**
 * Demo-Fixture aus dem Referenzkonto erzeugen (Fixture-Format 3).
 *
 * Aufruf (auf der Maschine, auf der die Referenzinstallation laeuft):
 *   php tools/referenzdatensatz/fixture/erzeugen.php [email] [ziel.json.gz]
 *
 * Anlass: Nr. 388 — seit Web 23 brach dieses Skript mit Rueckgabe 2 ab,
 * und die eingecheckte Fixture liess sich nicht mehr neu erzeugen.
 *
 * WAS HIER ENTSTEHT und warum es NICHT aus der .edbak kommen kann:
 *
 * Die Fixture traegt den Bestand fuer den Demo-Reset. Der Reset laeuft
 * huckepack auf einer Anfrage des Demo-Kontos und muss deshalb ohne Browser
 * auskommen: Er nimmt `pat_blob` als CHIFFRETEXT unveraendert, Orte,
 * Reanimation und Spur dagegen im Klartext und verschliesst sie selbst mit
 * dem oeffentlichen Schluessel des Demo-Kontos der Zielanlage
 * (`edbak_restore()`, Zweig 'server'). Eine `.edbak` passt dazu nicht: Ihr
 * Kern traegt `pat_blob` zwar ebenfalls verschluesselt, die Ortsdaten aber
 * hat der Browser beim Sichern geoeffnet, und den Schluessel, unter dem die
 * Datei versiegelt ist, kennt nur, wer sie herunterlaedt. Ausserdem fehlt in
 * ihr das Schluesselmaterial des Kontos (F-RD-02).
 *
 * DER WEG SEIT RD-01 (E-RD-09). Der Server kann die Spur nicht lesen — das
 * Werkzeug kann es, fuer genau dieses eine Konto:
 *
 *   1. Konto und Geraete aus der Datenbank, wie bisher.
 *   2. Der Bestand ueber `edbak_build()`, Kopf und Fenster — dieselbe
 *      Funktion, die die Sicherung fuer den Browser baut. `pat_blob` kommt
 *      dabei als Chiffretext, Spuren als Umriss (`spur_ref`, `n`).
 *   3. Stuecke ueber `spur2_lesen_viele()`, Einsatzhuellen aus dem Kern
 *      (`ort_huelle`). Beides wird mit dem privaten Teil des Kontopaars
 *      geoeffnet. Den liefert `kp_zustand()` unter dem Inhaltsschluessel,
 *      und den rechnet `demo_inhaltsschluessel()` aus `DEMO_PASSWORT` — das
 *      Demo-Konto ist das eine Konto, dessen Passwort der Server kennt. Das
 *      Oeffnen steht in `tools/proben/huelle/geraet.php` und NICHT im
 *      Server; der Server hat diese Funktion mit Absicht nicht.
 *   4. Je Spur eine Punktliste mit `stufe` und `n_original`, Phasenorte und
 *      Reanimation in die Felder, die Nutzlast 7 kennt; `ort_huelle` faellt
 *      weg (sie haengt am Schluesselpaar DIESER Anlage). Schnitte am Einsatz
 *      und Loeschvermerke am Segment kommen unveraendert aus dem Kern.
 *
 * DIESES SKRIPT LIEST NUR. Es schreibt eine Datei, sonst nichts.
 *
 * VORHER DIE JOBS ANHALTEN — sonst stimmt die Punktzahl nicht.
 *
 *     php server/jobs.php --pause 1800      (0 hebt auf; es
 *                                            geht auch als Knopf unter
 *                                            Betrieb -> Hintergrundjobs)
 *
 * Der Grund: Was zwischen dem Herstellen des Referenzbestands und dem
 * Erzeugen an Hintergrundarbeit laeuft, landet still in der Fixture. Gemessen
 * am 12.09.2026, damals noch mit der serverseitigen Ausduennung: 47 576 statt
 * 55 861 Punkten nach einer Sitzung mit einigen hundert Seitenaufrufen. Seit
 * Web 22 duennt der Browser aus, und das Demo-Konto bekommt vom Wartungslauf
 * leere Antworten (Nr. 459) — die Warnung bleibt trotzdem, denn nach RD-03
 * verdichtet das Einspielwerkzeug das Referenzkonto gezielt, und zwischen
 * diesem Schritt und dem Erzeugen darf nichts mehr tauschen. Die Zahl steht
 * am Ende des Laufs; wer sie nicht gegen die erwartete haelt, merkt es nicht.
 */

$email = $argv[1] ?? 'demo@gen-em.org';
$ziel  = $argv[2] ?? (__DIR__ . '/../../../server/demo/fixture.json.gz');

$wurzel = dirname(__DIR__, 3);
$server = realpath($wurzel . '/server');
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/backup_lib.php';
require_once $server . '/version.php';
require_once $server . '/demo_lib.php';
require_once $server . '/kontopaar_lib.php';
require_once $server . '/spur_lib.php';
require_once $wurzel . '/tools/proben/huelle/geraet.php';

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
/* ZIELRUNDENZAHL, SONST ABBRUCH.
 *
 * Die Fixture bringt die Rundenzahl des Kontos mit (`konto.kdf_iter` unten),
 * und der Demo-Reset schreibt sie unveraendert nach `users.kdf_iter`. Steht
 * sie unter KDF_ITER_ZIEL, kann der Altwert NIE aus KDF_ITER_LISTE
 * verschwinden: `api/kdf_upgrade.php` ueberspringt das Demo-Konto
 * ausdruecklich (weil ein Upgrade bis zum naechsten Reset nicht mehr
 * zu den oeffentlichen Zugangsdaten passte). Die Fixture wird bei jedem Reset
 * eingespielt und haelt den Altwert damit dauerhaft am Leben.
 *
 * Der Riegel steht hier und nicht nur in der Dokumentation, weil die Zusage
 * in `api/kdf_upgrade.php` sonst eine unbelegte bleibt. */
if ((int)$u['kdf_iter'] !== KDF_ITER_ZIEL) {
    abbruch(sprintf(
        "ABBRUCH: %s rechnet mit %d Runden, KDF_ITER_ZIEL ist %d.\n"
      . "Erst das Konto anheben (Passwort im Browser neu setzen, oder die\n"
      . "stille Anhebung laufen lassen -- beides nur an einem Konto ohne\n"
      . "app_state.demo_user_id), dann die Fixture erzeugen.\n",
        $email, (int)$u['kdf_iter'], KDF_ITER_ZIEL));
}

/* DIE DEMO-HUELLE BLEIBT `edk1:`, SONST ABBRUCH.
 *
 * Der Datenschluessel haengt zusaetzlich am Server-Anteil aus
 * `config.php`. Eine Huelle, die damit gebildet wurde, traegt das Praefix
 * `edka1:<kennung>:` — und sie laesst sich nur auf DER Installation oeffnen,
 * deren Anteil diese Kennung hat.
 *
 * Die Fixture geht aber gerade NICHT dorthin zurueck, aus der sie stammt:
 * Sie liegt unter `server/demo/` und wird beim Deploy auf die
 * Produktivinstallation gelegt, die ihren eigenen Anteil fuehrt. Eine
 * `edka1:`-Huelle in der Fixture hiesse dort: Das Demo-Konto meldet sich an
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
 *     weist eine `edka1:`-Huelle fuer das Demo-Konto ab. Das greift auf den
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
 * DER RIEGEL STEHT VOR JEDEM OEFFNEN. Eine `edka1:`-Huelle laesst sich mit
 * `DEMO_PASSWORT` allein nicht oeffnen; stuende das Oeffnen davor, kaeme die
 * Meldung „Passwort passt nicht" statt der, die den Grund nennt. */
foreach (['pat_wrap_pw' => 'Schluesselhuelle',
          'pat_wrap_rc' => 'Wiederherstellungs-Huelle'] as $spalte => $name) {
    $huelle = (string)($u[$spalte] ?? '');
    if ($huelle === '') { continue; }
    if (!str_starts_with($huelle, 'edk1:')) {
        abbruch(sprintf(
            "ABBRUCH: Die %s von %s traegt `%s`, erwartet ist `edk1:`.\n"
          . "Die Fixture wird auf einer Installation mit einem ANDEREN\n"
          . "Server-Anteil eingespielt; eine Huelle mit Anteil ist dort nicht zu\n"
          . "oeffnen. Das Demo-Konto kaeme herein und saehe nichts.\n"
          . "Weg zurueck: Passwort des Demo-Kontos im Browser neu setzen,\n"
          . "solange `config.php` KEINEN `kdf_anteil` fuehrt -- oder\n"
          . "`tools/proben/anteil/huelle_stellen.py %s <passwort> edk1`.\n",
            $name, $email, explode(':', $huelle)[0] . ':', $email));
    }
}

$id = (int)$u['id'];

/* ---- Der private Teil des Kontopaars ------------------------------------
 *
 * Inhaltsschluessel aus `DEMO_PASSWORT` (dieselbe Rechnung wie der Reset),
 * darunter `kp_privat` als `edk1:` mit dem PKCS8-Koerper. Scheitert einer der
 * Schritte, hat das Konto nicht das Demo-Passwort oder kein Paar — beides
 * heisst: Aus diesem Konto laesst sich keine Fixture machen. */
try {
    $ck = demo_inhaltsschluessel($u);
} catch (Throwable $ex) {
    abbruch('ABBRUCH: ' . $ex->getMessage());
}
$kp = kp_zustand($id, $pdo);
if ($kp['stand'] !== 'da' || $kp['privat'] === null) {
    abbruch("ABBRUCH: $email hat kein Schluesselpaar (Stand {$kp['stand']}). Es entsteht beim "
          . "ersten Entsperren nach einer Anmeldung.");
}
$pkcs8 = demo_edk1_oeffnen($ck, (string)$kp['privat']);
$privat = $pkcs8 === null ? false : openssl_pkey_get_private(
    "-----BEGIN PRIVATE KEY-----\n" . chunk_split($pkcs8, 64, "\n") . "-----END PRIVATE KEY-----\n");
if ($privat === false) {
    abbruch('ABBRUCH: Der private Teil des Kontopaars geht mit dem Inhaltsschluessel nicht auf.');
}

/* ---- Geraete -------------------------------------------------------------
 *
 * OHNE das virtuelle Geraet "Manuelle Einträge" (GERAETE_ECHT_SQL). Es traegt
 * die Kontonummer im Namen und gilt nur fuer das Konto, aus dem diese Fixture
 * stammt; im Demo-Konto entsteht es bei Bedarf mit der richtigen Nummer von
 * selbst. Stuende es mit in der Datei, braeche es das Anlegen des
 * Demo-Kontos ab, sobald eine Installation beide Bestaende fuehrte
 * (device_id ist global eindeutig). Der Filter in demo_lib.php faengt das
 * auch fuer bereits ausgelieferte Fixtures ab; hier kommt es gar nicht erst
 * hinein.
 *
 * ART UND MODELL WANDERN MIT. Sonst zeigte das Demo-Konto auf der
 * Geraeteseite „Gerät unbekannt", waehrend seine Einsaetze die
 * Momentaufnahme tragen. */
$st = $pdo->prepare('SELECT device_id, api_key_hash, label, geraet_art, geraet_modell
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

/* Gegenprobe VOR dem Oeffnen: Der Kern darf keine Klartext-Ortsdaten
 * tragen. Steht dort welcher, stammt er nicht aus den regulaeren Wegen —
 * und die Fixture zeigte ihn im Demo-Konto neben dem, was die Huellen sagen. */
$fund = edbak_klartext_fund($daten);
if ($fund !== null) { abbruch("ABBRUCH: Klartext-Ortsdaten im Kern: $fund"); }
$mitBlob = 0; $mitKlartext = 0;
foreach ($daten['missions'] as $m) {
    if (isset($m['pat_blob']) && $m['pat_blob'] !== null) { $mitBlob++; }
    if (isset($m['pat'])) { $mitKlartext++; }
}
if ($mitKlartext > 0) { abbruch("ABBRUCH: $mitKlartext Einsaetze fuehren Klartext (`pat`)."); }

/* ---- Spuren oeffnen ------------------------------------------------------ */

/** Vorzeichenbehaftete 32-Bit-Zahl aus `unpack('N')`. */
function i32(int $v): int { return $v >= 0x80000000 ? $v - 0x100000000 : $v; }

/** Breite und Laenge aus 1e-7 Grad — auf sieben Stellen, ohne Gleitkommarest. */
function grad(int $v): float { return round($v / 1e7, 7); }

/**
 * Die Stuecke EINER Spur oeffnen und als Punktliste [seq, lat, lon, ele, ts]
 * zurueckgeben, dazu Stufe und Originalzahl.
 *
 * STUFE 1 (Geraet) traegt je Punkt 14 Byte und die Nummer ergibt sich aus
 * `seq_from + i`. STUFE 2 UND 3 („B fest", `docs/Technik.md` 4.6) tragen die
 * Punkte gepackt und ohne eigene Nummer; das Stueck deckt `seq_from` bis
 * `seq_from + n_original - 1`. In die Punktliste schreibt das Werkzeug dort
 * `seq_from + i` — die Punkte bleiben in ihrer Reihenfolge, und der Reset
 * packt sie wieder zu einem Stueck mit `n_original` aus dem Eintrag (RD-04).
 *
 * @return array{punkte:list<array>, stufe:int, n_original:int}
 */
function spur_oeffnen(array $stuecke, $privat, string $art, string $ref): array
{
    $punkte = []; $stufen = []; $nOriginal = 0;
    foreach ($stuecke as $s) {
        $h = (string)base64_decode((string)$s['huelle'], true);
        $stufe = (int)$s['stufe'];
        $stufen[$stufe] = true;
        $nOriginal += (int)$s['n_original'];
        $n = (int)$s['punkte'];
        $seq = (int)$s['seq_from'];
        $tsVon = (int)$s['ts_von'];
        try {
            if ($stufe === SPUR2_STUFE_GERAET) {
                $innen = gh_probe_oeffnen($h, $privat, $art, $ref, $seq . ':' . $n);
                if (strlen($innen) !== 14 * $n) { throw new RuntimeException('laenge innen'); }
                for ($i = 0; $i < $n; $i++) {
                    $p = unpack('Nla/Nlo/nele/Nt', $innen, 14 * $i);
                    $ele = $p['ele'] >= 0x8000 ? $p['ele'] - 0x10000 : $p['ele'];
                    $punkte[] = [$seq + $i, grad(i32($p['la'])), grad(i32($p['lo'])),
                                 $ele === GH_HOEHE_UNBEKANNT ? null : $ele, $tsVon + $p['t']];
                }
            } else {
                $platz = $seq . ':' . $n . ':' . $stufe . ':' . (int)$s['n_original'];
                $roh = gzuncompress(gh_probe_oeffnen($h, $privat, $art, $ref, $platz));
                if ($roh === false || strlen($roh) !== 16 * $n) {
                    throw new RuntimeException('gepackte Innenform');
                }
                $w = array_map('i32', array_values(unpack('N' . (4 * $n), $roh)));
                $sp = [];
                for ($k = 0; $k < 4; $k++) {
                    $alt = 0;
                    for ($i = 0; $i < $n; $i++) { $alt += $w[$k * $n + $i]; $sp[$k][$i] = $alt; }
                }
                for ($i = 0; $i < $n; $i++) {
                    $punkte[] = [$seq + $i, grad($sp[0][$i]), grad($sp[1][$i]),
                                 $sp[2][$i] === GH_HOEHE_UNBEKANNT ? null : $sp[2][$i],
                                 $tsVon + $sp[3][$i]];
                }
            }
        } catch (Throwable $ex) {
            throw new RuntimeException("Stueck $seq:$n (Stufe $stufe) von $art $ref geht nicht auf: "
                                     . $ex->getMessage());
        }
    }
    /* EINE STUFE JE SPUR. Format 3 traegt `stufe` am Eintrag, nicht am
     * Stueck; eine Spur aus gepackten und Geraetestuecken liesse sich darin
     * nicht abbilden. Der Wartungslauf fuegt eine Spur immer ganz zusammen,
     * im Referenzbestand kommt die Mischung also nicht vor — und wenn doch,
     * soll das Werkzeug es sagen, statt eine Stufe zu erfinden. */
    if (count($stufen) > 1) {
        throw new RuntimeException("$art $ref hat Stuecke verschiedener Stufen ("
                                 . implode(', ', array_keys($stufen)) . ').');
    }
    return ['punkte' => $punkte, 'stufe' => (int)array_key_first($stufen), 'n_original' => $nOriginal];
}

$idNach = ['mission' => [], 'rest' => []];
foreach ($spurIndex as $e) { $idNach[$e['art']][(int)$e['spur_ref']] = (int)$e['id']; }
$stueckeNach = [
    'mission' => spur2_lesen_viele($pdo, 'mission', array_values($idNach['mission'])),
    'rest'    => spur2_lesen_viele($pdo, 'rest', array_values($idNach['rest'])),
];

$zahl = ['punkte' => 0, 'stufe' => [1 => 0, 2 => 0, 3 => 0], 'spuren' => 0,
         'phasenorte' => 0, 'rea' => 0, 'huellen' => 0, 'schnitte' => 0, 'loeschungen' => 0];

/** Spurangaben eines Eintrags ersetzen: Umriss raus, Punkte und Stufe rein. */
$spurEinsetzen = static function (array &$e, string $typ, string $huellenArt)
    use ($idNach, $stueckeNach, $privat, &$zahl): void {
    if (!isset($e['spur_ref'])) { return; }
    $oid = $idNach[$typ][(int)$e['spur_ref']] ?? null;
    if ($oid === null) { abbruch("spur_ref {$e['spur_ref']} ohne Eintrag im Verzeichnis"); }
    try {
        $s = spur_oeffnen($stueckeNach[$typ][$oid] ?? [], $privat, $huellenArt, (string)$e['client_ref']);
    } catch (Throwable $ex) {
        abbruch('ABBRUCH: ' . $ex->getMessage());
    }
    /* DIE PUNKTZAHL MUSS ZUM UMRISS PASSEN. `n` im Kern zaehlt die
     * gespeicherten Punkte; eine Abweichung hiesse, ein Stueck fehlt oder
     * ist doppelt — und die Fixture truege eine halbe Spur. */
    if (count($s['punkte']) !== (int)$e['n']) {
        abbruch(sprintf('ABBRUCH: %s %s hat %d Punkte, der Umriss nennt %d.',
                        $typ, $e['client_ref'], count($s['punkte']), (int)$e['n']));
    }
    unset($e['spur_ref'], $e['n']);
    $e['track'] = $s['punkte'];
    $e['stufe'] = $s['stufe'];
    $e['n_original'] = $s['n_original'];
    $zahl['punkte'] += count($s['punkte']);
    $zahl['stufe'][$s['stufe']] += count($s['punkte']);
    $zahl['spuren']++;
};

foreach ($daten['missions'] as &$m) {
    $spurEinsetzen($m, 'mission', 'spur');
    $zahl['schnitte'] += count($m['schnitte'] ?? []);
    /* DIE EINSATZHUELLE OEFFNEN UND WEGLASSEN. Sie ist mit dem Paar DIESER
     * Anlage verschlossen; der Reset baut auf der Zielanlage eine neue aus
     * dem Klartext. Bliebe sie stehen, naehme der Reset sie unveraendert —
     * und das Demo-Konto saehe dort weder Orte noch Reanimation. */
    $h = $m['ort_huelle'] ?? null;
    unset($m['ort_huelle']);
    /* JEDE PHASE TRAEGT `lat` UND `lon`, auch ohne Huelle (dann null) — eine
     * Form fuer alle Einsaetze, wie die Fixture der Nutzlast 7 sie kannte. */
    foreach ($m['phases'] as &$p) { $p['lat'] = null; $p['lon'] = null; }
    unset($p);
    $m['resus'] = [];
    if ($h === null) { continue; }
    try {
        $innen = json_decode(gh_probe_oeffnen((string)base64_decode((string)$h, true), $privat,
                                              'einsatz', (string)$m['client_ref']), true);
    } catch (Throwable $ex) {
        abbruch("ABBRUCH: Die Einsatzhuelle von {$m['client_ref']} geht nicht auf: " . $ex->getMessage());
    }
    if (!is_array($innen) || (int)($innen['fassung'] ?? 0) !== 1) {
        abbruch("ABBRUCH: Die Einsatzhuelle von {$m['client_ref']} hat keine bekannte Innenform.");
    }
    $zahl['huellen']++;
    $beginn = (int)strtotime($m['started_at'] . ' UTC');
    $zeit = static fn(int $t): string => gmdate('Y-m-d H:i:s', $beginn + $t);
    /* Phasenorte an die Phasen des Kerns, ueber Nummer UND Zeit. Die Reihenfolge
     * der Huelle ist die des Geraets, die des Kerns die nach Zeit; ein Abgleich
     * nur ueber die Position legte bei zwei Korrekturen derselben Nummer den Ort
     * an die falsche. */
    $orte = [];
    foreach ((array)($innen['phasen'] ?? []) as $p) {
        if (($p['lat'] ?? null) === null && ($p['lon'] ?? null) === null) { continue; }
        $orte[(int)$p['phase'] . '@' . $zeit((int)$p['t'])][] = [$p['lat'], $p['lon']];
    }
    foreach ($m['phases'] as &$p) {
        $k = (int)$p['phase'] . '@' . $p['occurred_at'];
        if (!isset($orte[$k])) { continue; }
        [$p['lat'], $p['lon']] = array_shift($orte[$k]);
        if (!$orte[$k]) { unset($orte[$k]); }
        $zahl['phasenorte']++;
    }
    unset($p);
    if ($orte) {
        abbruch("ABBRUCH: {$m['client_ref']}: Phasenorte ohne passende Phase im Kern ("
              . implode(', ', array_keys($orte)) . ').');
    }
    foreach ((array)($innen['rea'] ?? []) as $r) {
        $m['resus'][] = ['started_at' => $zeit((int)$r['t']),
                         'events' => array_map(static fn($e) => ['type' => (string)$e['art'],
                                                                 'occurred_at' => $zeit((int)$e['t'])],
                                               (array)($r['ereignisse'] ?? []))];
        $zahl['rea']++;
    }
}
unset($m);
foreach ($daten['rest_segments'] as &$r) {
    $spurEinsetzen($r, 'rest', 'ruhe');
    $zahl['loeschungen'] += count($r['loeschungen'] ?? []);
}
unset($r);

/* NUTZLAST 7: Punktlisten im Eintrag. Der Kern kommt als 13; `edbak_restore()`
 * entscheidet an dieser Zahl, ob es Punktlisten oder Verweise liest
 * (`$mitVerweisen = $nutzlast >= 8`). Eine 13 mit Punktlisten verloere
 * im Reset still jede Spur. */
$daten['version'] = 7;

/* ---- Papierkorb: nur zaehlen ------------------------------------------- */
$papierkorb = ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0];
foreach ($daten['missions'] as $m) {
    if (($m['deleted_at'] ?? null) !== null) { $papierkorb['einsaetze']++; }
}
foreach (($daten['days'] ?? []) as $d) {
    if (($d['deleted_at'] ?? null) !== null) { $papierkorb['diensttage']++; }
}
foreach ($daten['rest_segments'] as $r) {
    if (($r['deleted_at'] ?? null) !== null) { $papierkorb['ruhezeiten']++; }
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

/* ---- Zusammensetzen ------------------------------------------------------ */
$fx = [
    'format'      => 'einsatzdoku-demo-fixture',
    /* FORMAT 3 (E-RD-03): wie 2, dazu an jedem spurtragenden Eintrag `stufe`
     * und `n_original` und an Ruhesegmenten `loeschungen`. Das innere Backup
     * bleibt Nutzlast 7. `demo_fixture_laden()` prueft die Nummer nicht; was
     * der Reset aus `stufe` macht, steht in RD-04. */
    'version'     => 3,
    'erzeugt_am'  => gmdate('c'),
    'web_version' => WEB_VERSION,
    'quelle'      => $quelle,
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

/* GEPACKT ABLEGEN. Die Datei liegt unter server/ und geht bei jedem Deploy
 * ueber FTPS mit; gelesen wird sie nur beim Anlegen und beim Reset.
 * Die ungepackte Fassung wird NICHT zusaetzlich abgelegt: Zwei Dateien
 * desselben Inhalts laufen auseinander, sobald jemand eine davon anfasst. */
@mkdir(dirname($ziel), 0775, true);
$json = json_encode($fx, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
if ($json === false) { abbruch('json_encode: ' . json_last_error_msg()); }
$gepackt = gzencode($json, 9);
if (file_put_contents($ziel, $gepackt) === false) { abbruch("Nicht schreibbar: $ziel"); }

printf("Fixture geschrieben: %s (%.1f KB gepackt, %.1f KB roh)\n",
       $ziel, strlen($gepackt) / 1024, strlen($json) / 1024);
printf("  Format       3, Nutzlast 7, Web %s, Quelle %s\n", WEB_VERSION, $quelle);
printf("  Konto        %s (Rolle %s, kdf_iter %d)\n", $u['email'], $u['role'], (int)$u['kdf_iter']);
printf("  Geraete      %d\n", count($geraete));
printf("  Diensttage   %d\n", count($daten['days'] ?? []));
printf("  Einsaetze    %d  (davon %d mit Chiffretext, %d mit Einsatzhuelle)\n",
       count($daten['missions']), $mitBlob, $zahl['huellen']);
printf("  Ruhesegmente %d\n", count($daten['rest_segments']));
printf("  Spuren       %d\n", $zahl['spuren']);
printf("  Spurpunkte   %d  (Stufe 1: %d, Stufe 2: %d, Stufe 3: %d)\n", $zahl['punkte'],
       $zahl['stufe'][1], $zahl['stufe'][2], $zahl['stufe'][3]);
printf("  Phasenorte   %d, Reanimationen %d\n", $zahl['phasenorte'], $zahl['rea']);
printf("  Schnitte     %d, Loeschvermerke %d\n", $zahl['schnitte'], $zahl['loeschungen']);
printf("  Papierkorb   %d/%d/%d (Einsaetze/Diensttage/Ruhesegmente, in `daten` enthalten)\n",
       $papierkorb['einsaetze'], $papierkorb['diensttage'], $papierkorb['ruhezeiten']);
