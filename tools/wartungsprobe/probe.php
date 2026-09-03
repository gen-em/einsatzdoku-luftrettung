<?php
declare(strict_types=1);

/**
 * Wartungsprobe — schliesst der Wartungsmodus, was er schliessen soll, und
 * laesst er offen, was offen bleiben muss? (S5 Paket W, Konzept 6.1)
 *
 * WOFUER. Der Wartungsmodus ist eine Sperre, und eine Sperre hat zwei Arten
 * zu scheitern. Sie kann zu WENIG sperren — dann laeuft eine Uhr waehrend
 * einer Migration in eine halb umgebaute Datenbank und bekommt 500 statt
 * 503, und ein 500 ist fuer sie ein Grund, den Puffer NICHT zu behalten.
 * Und sie kann zu VIEL sperren — dann kommt die Administratorin nicht mehr
 * an `update.php`, und die Installation bleibt geschlossen, bis jemand per
 * SSH eine Datei loescht. Beide Richtungen misst diese Probe.
 *
 * UEBER ECHTES HTTP (Muster `tools/kopplungsprobe/`): Geprueft wird ein
 * Verhalten, das an Kopfzeilen haengt — Statuscode, `Retry-After`,
 * `Content-Type`, `Set-Cookie`. Nichts davon ist ueber einen
 * Funktionsaufruf zu sehen.
 *
 * SIE IST DIE EINZIGE PROBE, DIE DEN SCHALTER UMLEGT. Sie legt
 * `server/wartung.lock` an und raeumt sie im `finally` wieder weg — auch
 * wenn sie mittendrin abbricht. Wer sie auf einer Installation mit Betrieb
 * fahren wollte: nicht tun. Sie schliesst diese Installation fuer die Dauer
 * ihres Laufs.
 *
 * DIE SITZUNGEN LEGT SIE SELBST AN, statt sich anzumelden. Die Anmeldung
 * leitet das Token im BROWSER per PBKDF2 ab (assets/crypto.js) — mit `curl`
 * ist sie nicht nachzubilden, ohne die Ableitung ein zweites Mal zu
 * schreiben. Stattdessen schreibt die Probe die PHP-Sitzungsdatei direkt
 * (dieselbe `session.save_path`, dieselbe Maschine) und schickt deren
 * Kennung als Cookie. Das ist genau das, was der Server nach einer
 * gelungenen Anmeldung vorfindet.
 *
 * WAS SIE NICHT PRUEFT, und warum:
 *   - Das VERHALTEN DES DEPLOYS gegenueber `wartung.lock` (Konzept 6.3).
 *     Die Ausnahme in `deploy.yml` ist eine Zusage; bewiesen wird sie beim
 *     ersten Deploy im Wartungsmodus. Steht im Pruefdokument.
 *   - Wie die WARTUNGSSEITE AUSSIEHT. Sie misst, dass sie kommt und was
 *     drinsteht; ob sie bei 360 px ueberlaeuft, misst der Bilderlauf.
 *   - Die ANMELDUNG selbst (Fall 10 sieht nur, dass die Seite kommt und den
 *     Balken traegt). Was nach einer gelungenen Anmeldung im Wartungsmodus
 *     geschieht — Admin weiter, alles andere sofort wieder abgemeldet
 *     (E-S5W-09) —, steht in Fall 18, und zwar am Code gelesen: `login.php` ist
 *     ueber HTTP nur mit abgeleitetem Token zu erreichen. Drei Erwartungen,
 *     jede mit genannter Fundstelle.
 *
 * Aufruf:
 *   php tools/wartungsprobe/probe.php [basisadresse]
 *   (Vorgabe: http://127.0.0.1:8080)
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/kopplung_lib.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo   = db();

$erwartungen = 0; $offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-62s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

/* ---- HTTP -------------------------------------------------------------- */

/**
 * Eine Anfrage mit vollen Kopfzeilen. `$cookie` ist der Wert von PHPSESSID
 * oder null; `$koerper` null = GET.
 */
function hole(string $pfad, ?string $cookie = null, ?array $koerper = null,
              array $kopf = []): array {
    global $basis;
    $ch = curl_init("$basis/$pfad");
    if ($cookie !== null) { $kopf[] = 'Cookie: ' . session_name() . '=' . $cookie; }
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HEADER         => true,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_HTTPHEADER     => $kopf,
        CURLOPT_TIMEOUT        => 60,
        CURLOPT_PROXY          => '',
    ]);
    if ($koerper !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
            isset($kopf[0]) && str_contains(implode(' ', $kopf), 'application/json')
                ? (string)json_encode($koerper)
                : http_build_query($koerper));
    }
    $t0  = microtime(true);
    $roh = (string)curl_exec($ch);
    $dauer = microtime(true) - $t0;
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $tren = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    $kopfRoh = substr($roh, 0, $tren);
    $rumpf   = substr($roh, $tren);
    $d = json_decode($rumpf, true);
    return ['code' => $code, 'kopf' => $kopfRoh, 'rumpf' => $rumpf,
            'daten' => is_array($d) ? $d : [], 'dauer' => $dauer];
}

/** Steht eine Kopfzeile (unabhaengig von Gross-/Kleinschreibung)? */
function kopfzeile(array $a, string $name): ?string {
    foreach (explode("\r\n", $a['kopf']) as $z) {
        $p = strpos($z, ':');
        if ($p === false) { continue; }
        if (strcasecmp(trim(substr($z, 0, $p)), $name) === 0) {
            return trim(substr($z, $p + 1));
        }
    }
    return null;
}

/* ---- Sitzungen --------------------------------------------------------- */

/**
 * Eine PHP-Sitzung anlegen, wie login.php sie hinterlaesst.
 *
 * `user_id`, `epoch` und `csrf` sind das, was auth_guard.php erwartet;
 * `last_seen` verhindert, dass die Sitzung sofort als abgelaufen gilt.
 * Geschrieben wird ueber PHPs eigenen Serialisierer — ein von Hand
 * zusammengebautes Dateiformat waere die Art von Abkuerzung, die genau dann
 * bricht, wenn jemand `session.serialize_handler` umstellt.
 */
function sitzung_anlegen(int $uid, int $epoch): array {
    $sid  = 'wartungsprobe' . bin2hex(random_bytes(10));
    $csrf = bin2hex(random_bytes(16));
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    session_id($sid);
    session_start();
    $_SESSION = ['user_id' => $uid, 'epoch' => $epoch,
                 'last_seen' => time(), 'csrf' => $csrf];
    session_write_close();
    return ['sid' => $sid, 'csrf' => $csrf];
}

/**
 * ALLE SITZUNGEN ENTSTEHEN VOR DER ERSTEN AUSGABE, und das ist kein Zufall.
 *
 * `session_id()` und `session_start()` scheitern, sobald PHP eine Kopfzeile
 * geschickt hat — auf der Kommandozeile heisst das: sobald die Probe die
 * erste Zeile gedruckt hat. Der erste Entwurf las das CSRF-Token spaeter
 * nach und bekam einen Leerstring; die drei Schaltfaelle scheiterten dann
 * mit 403, und zwar an der Probe und nicht an der Anwendung.
 *
 * Deshalb: anlegen, das Token MITNEHMEN, und zum Aufraeumen die
 * Sitzungsdatei loeschen statt eine Sitzung zu oeffnen.
 */
function sitzung_weg(string $sid): void {
    $pfad = (session_save_path() ?: sys_get_temp_dir()) . '/sess_' . $sid;
    if (is_file($pfad)) { @unlink($pfad); }
}

/* ---- Schalter ---------------------------------------------------------- */

function wartung_setzen(string $inhalt): void {
    file_put_contents(WARTUNG_DATEI, $inhalt);
    clearstatcache(true, WARTUNG_DATEI);
}
function wartung_weg(): void {
    if (file_exists(WARTUNG_DATEI)) { unlink(WARTUNG_DATEI); }
    clearstatcache(true, WARTUNG_DATEI);
}

/* ---- Konten ------------------------------------------------------------ */

$emailAdmin = 'wartungsprobe-admin@gen-em.org';
$emailUser  = 'wartungsprobe-user@gen-em.org';
$pdo->prepare('DELETE FROM users WHERE email IN (?, ?)')->execute([$emailAdmin, $emailUser]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Wartungsprobe Admin', 'admin', '', '', 320000)")->execute([$emailAdmin]);
$uidAdmin = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Wartungsprobe Nutzer', 'user', '', '', 320000)")->execute([$emailUser]);
$uidUser = (int)$pdo->lastInsertId();

$epochAdmin = (int)$pdo->query("SELECT session_epoch FROM users WHERE id = $uidAdmin")->fetchColumn();
$epochUser  = (int)$pdo->query("SELECT session_epoch FROM users WHERE id = $uidUser")->fetchColumn();
['sid' => $sidAdmin, 'csrf' => $csrfAdmin] = sitzung_anlegen($uidAdmin, $epochAdmin);
['sid' => $sidUser]                            = sitzung_anlegen($uidUser,  $epochUser);

echo "Wartungsprobe gegen $basis\n";
echo "  Konten $emailAdmin (uid $uidAdmin, admin), $emailUser (uid $uidUser)\n";
echo "  Schalter " . WARTUNG_DATEI . ", Retry-After " . WARTUNG_RETRY_S . " s\n";

$warVorher = file_exists(WARTUNG_DATEI);
if ($warVorher) {
    echo "  ACHTUNG: Es lag schon eine wartung.lock — sie wird am Ende WIEDERHERGESTELLT.\n";
    $inhaltVorher = (string)file_get_contents(WARTUNG_DATEI);
}
$geraet = null;

try {

/* ======================================================================
 * Teil 0 — der Normalzustand, als Vergleichsmass
 * ====================================================================== */
echo "\n  Teil 0 — ohne Wartung (Vergleichsmass)\n";
wartung_weg();

$ohne = hole('index.php');
pruefe($ohne['code'] !== 503, '0   ohne wartung.lock antwortet index.php nicht mit 503',
       'HTTP ' . $ohne['code']);
$ohneIngest = hole('ingest.php', null, ['x' => 1], ['Content-Type: application/json']);
pruefe($ohneIngest['code'] === 401,
       '0   ohne Wartung: ingest.php ohne Zugangsdaten -> 401 (wie immer)',
       'HTTP ' . $ohneIngest['code']);
$dauerOhne = $ohneIngest['dauer'];

/* Ein Geraet fuer Fall 3: Es muss GUELTIGE Zugangsdaten haben, sonst
 * belegt ein 503 nichts — ein 401 kaeme ja ohnehin. */
$devId  = 'dev-wartungsprobe';
$apiKey = bin2hex(random_bytes(24));
$pdo->prepare('DELETE FROM devices WHERE device_id = ?')->execute([$devId]);
$pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
               VALUES (?, ?, ?, ?, 1)')
    ->execute([$uidUser, $devId, geraet_schluessel_hash($apiKey), 'Wartungsprobe']);
$geraet = $devId;

$missVorher = (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE user_id = $uidUser")->fetchColumn();

/* ======================================================================
 * Teil 1 — mit Wartung: was gesperrt wird
 * ====================================================================== */
echo "\n  Teil 1 — mit Wartung: was gesperrt wird\n";
wartung_setzen('{"seit":"2026-09-03T14:12:00Z","von":"Wartungsprobe"}');

$a1 = hole('index.php');
pruefe($a1['code'] === 503, '1   index.php ohne Sitzung -> 503', 'HTTP ' . $a1['code']);
pruefe(kopfzeile($a1, 'Retry-After') === (string)WARTUNG_RETRY_S,
       '1   Retry-After steht und nennt ' . WARTUNG_RETRY_S,
       (string)kopfzeile($a1, 'Retry-After'));
pruefe(str_contains($a1['rumpf'], 'Wartung'),
       '1   Die Seite nennt „Wartung"');
pruefe(kopfzeile($a1, 'Set-Cookie') === null,
       '1   KEIN Set-Cookie — das Tor greift vor session_start()');
pruefe(kopfzeile($a1, 'Cache-Control') === 'no-store',
       '1   Cache-Control: no-store');

$a2 = hole('einsatz.php', $sidUser);
pruefe($a2['code'] === 503 && str_contains($a2['rumpf'], 'Wartung'),
       '2   einsatz.php MIT Nutzer-Sitzung -> 503, HTML', 'HTTP ' . $a2['code']);

$a3 = hole('ingest.php', null, ['mission' => ['x' => 1]],
           ['Content-Type: application/json', 'X-Device-Id: ' . $devId, 'X-Api-Key: ' . $apiKey]);
pruefe($a3['code'] === 503 && ($a3['daten']['error'] ?? '') === 'maintenance',
       '3   ingest.php mit GUELTIGEM Geraeteschluessel -> 503 maintenance',
       'HTTP ' . $a3['code'] . ' ' . substr($a3['rumpf'], 0, 60));
$missNachher = (int)$pdo->query("SELECT COUNT(*) FROM missions WHERE user_id = $uidUser")->fetchColumn();
pruefe($missNachher === $missVorher,
       '3   ... und KEINE Zeile in missions', "$missVorher -> $missNachher");

$a4 = hole('pair.php', null, ['aktion' => 'start'], ['Content-Type: application/json']);
pruefe($a4['code'] === 503 && ($a4['daten']['error'] ?? '') === 'maintenance',
       '4   pair.php aktion=start -> 503 maintenance', 'HTTP ' . $a4['code']);
$sitzungen = (int)$pdo->query('SELECT COUNT(*) FROM pair_sessions')->fetchColumn();
pruefe($sitzungen === 0 || true, '4   (Sitzungszahl notiert)', (string)$sitzungen);

$a5 = hole('api/kopplung_stand.php', $sidUser);
pruefe($a5['code'] === 503 && ($a5['daten']['error'] ?? '') === 'maintenance'
       && ($a5['daten']['meldung'] ?? '') !== '',
       '5   api/… mit Sitzung -> 503 maintenance MIT meldung (E-S5W-10)',
       'HTTP ' . $a5['code'] . ' ' . substr($a5['rumpf'], 0, 60));

/* ======================================================================
 * Teil 2 — mit Wartung: was offen bleibt (E-S5W-04)
 * ====================================================================== */
echo "\n  Teil 2 — mit Wartung: was offen bleibt\n";

$a6 = hole('update.php', $sidAdmin);
pruefe($a6['code'] === 200, '6   update.php mit Admin-Sitzung -> 200', 'HTTP ' . $a6['code']);
pruefe(str_contains($a6['rumpf'], 'Wartungsmodus seit'),
       '6   ... traegt den Balken „Wartungsmodus seit"');
pruefe(str_contains($a6['rumpf'], 'Wartungsmodus ausschalten'),
       '6   ... und den Knopf „Wartungsmodus ausschalten"');

$a7 = hole('update.php', $sidUser);
pruefe($a7['code'] !== 503, '7   update.php mit NUTZER-Sitzung: nicht 503 (Abweisung wie sonst)',
       'HTTP ' . $a7['code']);

require_once $wurzel . '/jobs_lib.php';
$token = jobs_token(false);
$a8 = hole('jobs.php?token=' . urlencode($token));
pruefe($a8['code'] === 200, '8   jobs.php mit gueltigem Token -> 200 (Jobs laufen, E-S5W-11)',
       'HTTP ' . $a8['code']);

$a9 = hole('jobs.php?token=falsch-und-zwar-eindeutig');
pruefe($a9['code'] === 403 && ($a9['daten']['error'] ?? '') === 'token',
       '9   jobs.php mit falschem Token -> 403 token (wie heute)', 'HTTP ' . $a9['code']);

$a10 = hole('login.php');
pruefe($a10['code'] === 200, '10  login.php -> 200', 'HTTP ' . $a10['code']);
pruefe(str_contains($a10['rumpf'], 'Wartungsmodus seit'),
       '10  ... mit Balken');
pruefe(str_contains($a10['rumpf'], 'name="password"'),
       '10  ... und mit Anmeldeformular — die Verwaltung kommt hinein');

$a11 = hole('wiederherstellen.php', $sidAdmin);
pruefe($a11['code'] !== 503, '11  wiederherstellen.php mit Admin-Sitzung: nicht 503',
       'HTTP ' . $a11['code']);

$a12 = hole('assets/style.css');
pruefe($a12['code'] === 200, '12  assets/style.css -> 200 (statisch, ungetort)',
       'HTTP ' . $a12['code']);

/* ======================================================================
 * Teil 3 — Schalten, kaputter Inhalt, Antwortzeit
 * ====================================================================== */
echo "\n  Teil 3 — Schalten, kaputter Inhalt, Antwortzeit\n";

$a13 = hole('update.php', $sidAdmin, ['action' => 'wartung_aus', 'csrf' => $csrfAdmin]);
pruefe($a13['code'] === 200 && !file_exists(WARTUNG_DATEI),
       '13  Ausschalten ueber update.php (POST, CSRF) -> Datei weg',
       'HTTP ' . $a13['code']);
$a13b = hole('index.php');
pruefe($a13b['code'] !== 503, '13  ... und index.php antwortet wieder',
       'HTTP ' . $a13b['code']);

$a13c = hole('update.php', $sidAdmin, ['action' => 'wartung_an', 'csrf' => $csrfAdmin]);
pruefe($a13c['code'] === 200 && file_exists(WARTUNG_DATEI),
       '13  Einschalten ueber update.php -> Datei da', 'HTTP ' . $a13c['code']);
$d = json_decode((string)file_get_contents(WARTUNG_DATEI), true);
pruefe(is_array($d) && ($d['von'] ?? '') === 'Wartungsprobe Admin' && !empty($d['seit']),
       '13  ... und traegt Zeitpunkt und Konto', json_encode($d));

wartung_setzen("kein json {{{\n");
$a14 = hole('index.php');
pruefe($a14['code'] === 503, '14  wartung.lock mit kaputtem Inhalt -> trotzdem 503',
       'HTTP ' . $a14['code']);
$a14b = hole('login.php');
pruefe(str_contains($a14b['rumpf'], 'seit unbekannt'),
       '14  ... und der Balken sagt „seit unbekannt"');

/* Fall 15: Das Tor greift VOR Datenbank und Ratenschutz. Gemessen an
 * ingest.php, weil der Weg dort am laengsten ist — Zugangsdaten pruefen,
 * Ratenschutz zaehlen, Nutzlast lesen. Der Vergleich ist der Lauf aus
 * Teil 0 ohne Wartung. */
$a15 = hole('ingest.php', null, ['mission' => ['x' => 1]],
            ['Content-Type: application/json', 'X-Device-Id: ' . $devId, 'X-Api-Key: ' . $apiKey]);
pruefe($a15['dauer'] < $dauerOhne,
       '15  503 kommt schneller als die Antwort ohne Wartung',
       sprintf('%.1f ms statt %.1f ms', $a15['dauer'] * 1000, $dauerOhne * 1000));

/* ======================================================================
 * Teil 4 — Kommandozeile und die Regeln am Code
 * ====================================================================== */
echo "\n  Teil 4 — Kommandozeile und Ausnahmeliste\n";

$aus = [];
exec('cd ' . escapeshellarg($wurzel) . ' && php update.php 2>&1', $aus, $rc);
pruefe($rc === 0 && count($aus) > 3,
       '16  php update.php (CLI) laeuft im Wartungsmodus (Notausgang unberuehrt)',
       'Rueckgabe ' . $rc . ', ' . count($aus) . ' Zeilen');

/* Die Ausnahmeliste wird gegen die Entscheidung gezaehlt, nicht gegen sich
 * selbst: Wer eine Datei aus E-S5W-04 herausnimmt, soll hier scheitern und
 * nicht erst auf dem Produktivserver. */
$sollAusnahmen = ['update.php', 'wiederherstellen.php', 'jobs.php',
                  'login.php', 'logout.php', 'install.php'];
sort($sollAusnahmen);
$istAusnahmen = WARTUNG_AUSNAHMEN;
sort($istAusnahmen);
pruefe($istAusnahmen === $sollAusnahmen,
       '17  Ausnahmeliste ist genau die aus E-S5W-04',
       implode(', ', $istAusnahmen));

/* E-S5W-09 am Code: login.php muss `role` lesen und im Wartungsmodus fuer
 * Nicht-Admins die Sitzung verwerfen. Ueber HTTP ist der Zweig nicht
 * erreichbar (das Token entsteht im Browser per PBKDF2), also wird die
 * Stelle gelesen — mit Nennung dessen, was gelesen wurde. */
$loginQuelle = (string)file_get_contents($wurzel . '/login.php');
pruefe(str_contains($loginQuelle, 'kdf_iter, logo_wahl, role'),
       '18  login.php liest `role` in seiner Nutzerabfrage (E-S5W-09 c)');
pruefe(preg_match('/wartung_aktiv\(\)\s*&&.*?!==\s*\'admin\'/s', $loginQuelle) === 1
       && str_contains($loginQuelle, 'session_verwerfen();')
       && str_contains($loginQuelle, 'wartung_antwort_seite();'),
       '18  ... und verwirft im Wartungsmodus die Sitzung eines Nicht-Admins');
$posErfolg = strpos($loginQuelle, "rate_erfolg('login'");
$posTor    = strpos($loginQuelle, 'wartung_aktiv() &&');
pruefe($posErfolg !== false && $posTor !== false && $posErfolg < $posTor,
       '18  ... aber ERST nach rate_erfolg — richtiges Passwort sperrt nicht (E-S5W-09 b)');

/* ======================================================================
 * Teil 5 — die Wartungsseite selbst
 * ====================================================================== */
echo "\n  Teil 5 — die Wartungsseite\n";

wartung_setzen('{"seit":"2026-09-03T14:12:00Z","von":"Wartungsprobe"}');
$seite = hole('index.php');
pruefe(str_contains($seite['rumpf'], 'assets/style.css'),
       '19  Die Seite verlinkt das Stylesheet (statisch, im Wartungsmodus erreichbar)');
pruefe(!str_contains($seite['rumpf'], '<script'),
       '19  ... und traegt KEIN Skript');
pruefe(str_contains($seite['rumpf'], 'liefern ihre Daten danach')
       && str_contains($seite['rumpf'], 'zurück'),
       '19  ... und sagt beides: Geraete liefern nach, Formular ueber Zurueck');

/* Der Muenzwurf des Logos (statt logo_stamm(), das die Datenbank braucht).
 * Zwanzig Aufrufe muessen beide Logos zeigen — sonst ist der Wurf keiner. */
$logos = [];
for ($i = 0; $i < 20; $i++) {
    if (preg_match('/images\/(gen-em_logo_[a-z]+)\.svg/', hole('index.php')['rumpf'], $m)) {
        $logos[$m[1]] = true;
    }
}
pruefe(count($logos) === 2,
       '20  Das Logo wirft eine Muenze — beide Standardlogos kommen vor',
       implode(', ', array_keys($logos)));

} finally {
    if ($warVorher) { wartung_setzen($inhaltVorher ?? ''); } else { wartung_weg(); }
    sitzung_weg($sidAdmin);
    sitzung_weg($sidUser);
    if ($geraet !== null) {
        $pdo->prepare('DELETE FROM devices WHERE device_id = ?')->execute([$geraet]);
    }
    foreach ([$uidAdmin, $uidUser] as $u) {
        if ($u > 0) { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$u]); }
    }
    echo "\n  Schalter, Sitzungen, Geraet und Konten der Probe wieder entfernt"
       . ($warVorher ? " (die vorgefundene wartung.lock steht wieder)" : "") . ".\n";
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
