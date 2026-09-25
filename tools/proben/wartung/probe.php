<?php
declare(strict_types=1);

/**
 * Wartungsprobe — schliesst der Wartungsmodus, was er schliessen soll, und
 * laesst er offen, was offen bleiben muss? (S5 Paket W, Konzept 6.1)
 *
 * Anlass: Nr. 171 — Anmeldung im Wartungsmodus scheiterte: auth_salt.php bekam 503
 *
 * WOFUER. Der Wartungsmodus ist eine Sperre, und eine Sperre hat zwei Arten
 * zu scheitern. Sie kann zu WENIG sperren — dann laeuft eine Uhr waehrend
 * einer Migration in eine halb umgebaute Datenbank und bekommt 500 statt
 * 503, und ein 500 ist fuer sie ein Grund, den Puffer NICHT zu behalten.
 * Und sie kann zu VIEL sperren — dann kommt die Betreiberin nicht mehr an
 * `betrieb_updates.php`, und die Installation bleibt geschlossen, bis jemand
 * per SSH eine Datei loescht. Beide Richtungen misst diese Probe.
 *
 * SEIT S8/AP2 liegt der Schalter auf `betrieb_updates.php` statt auf
 * `update.php`, und die drei Betriebsseiten stehen in der Ausnahmeliste. Sie
 * MUESSEN dort stehen: Ohne den Eintrag antwortete ausgerechnet die Seite mit
 * dem Ausschalter mit 503 (F-S8-P-04). Genau das misst Erwartung 6.
 *
 * UEBER ECHTES HTTP (Muster `tools/proben/kopplung/`): Geprueft wird ein
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
 * (dasselbe Verzeichnis, dieselbe Maschine) und schickt deren Kennung als
 * Cookie. Das ist genau das, was der Server nach einer gelungenen Anmeldung
 * vorfindet.
 *
 * SEIT WEB 20.26.0 REICHT DAFUER `session_save_path()` NICHT MEHR
 * (Schritt 16, E-SA-02). Die Anwendung legt ihre Sitzungen selbst in
 * `server/.sitzungen/` ab — aber nur im Web-Lauf; auf der Kommandozeile
 * richtet `sitzung_ablage()` bewusst nichts ein, damit ein Cron-Nutzer das
 * Verzeichnis nicht mit fremdem Eigentuemer anlegt. Diese Probe LAEUFT auf
 * der Kommandozeile und spricht einen Server ueber HTTP an: Ohne den Griff
 * in `sitzung_ort()` schriebe sie in den Hosterpfad, waehrend der Server in
 * `.sitzungen/` sucht. Alle Sitzungsfaelle fielen dann um — mit „nicht
 * angemeldet", also aussehend wie ein Fehler der ANWENDUNG statt wie einer
 * der Probe.
 *
 * SEIT P5c/AP5 TRAEGT DIE BETREIBERIN `totp_seit` (E-P5c-43, F-P5c-33).
 * Ohne Zweitfaktor fuehrte `auth_guard.php` sie auf `zweitfaktor.php` —
 * ausserhalb der Wartung, denn in ihr schweigt das Tor. Das trifft genau die
 * Faelle, in denen die Wartung aus ist: das Einschalten (13) und die offene
 * Installation danach (31). Und eine dritte Sitzung kommt dazu, eine
 * abgelaufene HALBE Anmeldung, fuer Erwartung 12a (Begruendung dort). Die
 * Anmeldung mit Code bleibt draussen wie die mit Passwort: Die Sitzungen
 * entstehen hinter ihr, und den Code-Schritt misst die Zweitfaktorprobe.
 *
 * WAS SIE NICHT PRUEFT, und warum:
 *   - Das VERHALTEN DES DEPLOYS gegenueber `wartung.lock` (Konzept 6.3).
 *     Die Ausnahme in `auslieferung.yml` ist eine Zusage; bewiesen wird sie beim
 *     ersten Deploy im Wartungsmodus. Steht im Pruefdokument.
 *   - Wie die WARTUNGSSEITE AUSSIEHT. Sie misst, dass sie kommt und was
 *     drinsteht; ob sie bei 360 px ueberlaeuft, misst der Bilderlauf.
 *   - Die ANMELDUNG selbst (Fall 10 sieht nur, dass die Seite kommt und den
 *     Balken traegt). Was nach einer gelungenen Anmeldung im Wartungsmodus
 *     geschieht — Admin weiter, alles andere sofort wieder abgemeldet
 *     (E-S5W-09) —, steht in Fall 18, und zwar am Code gelesen: `login.php` ist
 *     ueber HTTP nur mit abgeleitetem Token zu erreichen. Drei Erwartungen,
 *     jede mit genannter Fundstelle. Seit P5c/AP5 steht das Tor in
 *     `login_zugang()` und wird zweimal gefragt — nach dem Passwort und nach
 *     dem Code; Fall 18 liest beide Aufrufe.
 *
 * Aufruf:
 *   php tools/proben/wartung/probe.php [basisadresse]
 *   (Vorgabe: http://127.0.0.1:8080)
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3) . '/server';
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
 * Wohin der SERVER seine Sitzungen legt — nicht, wohin dieser CLI-Lauf sie
 * legen wuerde.
 *
 * Gibt es `server/.sitzungen/`, ist das der Ort: Das Verzeichnis entsteht
 * beim ersten Web-Aufruf und wird von `sitzung_ablage()` gesetzt. Gibt es
 * es nicht, laeuft die Anwendung im Rueckfall auf dem Hosterpfad (E-SA-03),
 * und dann gilt wieder, was `session_save_path()` sagt.
 */
function sitzung_ort(): string {
    $eigen = sitzung_ablage_pfad();
    if (is_dir($eigen)) { return $eigen; }
    return (string)(session_save_path() ?: sys_get_temp_dir());
}

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
    $csrf = bin2hex(random_bytes(16));
    $sid  = sitzung_schreiben(['user_id' => $uid, 'epoch' => $epoch,
                               'last_seen' => time(), 'csrf' => $csrf]);
    return ['sid' => $sid, 'csrf' => $csrf];
}

/**
 * Eine HALBE Anmeldung, und zwar eine abgelaufene (P5c/AP5, E-P5c-53):
 * `totp_halb` mit einer Frist in der Vergangenheit und KEIN `user_id`. So
 * steht die Sitzung da, wenn nach dem Passwort binnen fuenf Minuten kein
 * Code kam. `login.php` zeigt dann wieder das Passwortformular und schaltet
 * den Block ein, der das Vormerkfach des Browsers raeumt
 * (`data-vergessen="1"`). Gebraucht fuer Erwartung 12a.
 */
function sitzung_halb_anlegen(int $uid, string $email): string {
    return sitzung_schreiben(['totp_halb' => ['konto' => $uid, 'email' => $email,
                                              'bis' => time() - 60, 'demo' => false]]);
}

/** Eine Sitzungsdatei mit genau diesem Inhalt schreiben; liefert die Kennung. */
function sitzung_schreiben(array $inhalt): string {
    $sid = 'wartungsprobe' . bin2hex(random_bytes(10));
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    /* VOR `session_start()`, sonst schreibt die Probe woanders hin als der
     * Server liest. Siehe den Kopf der Datei. */
    session_save_path(sitzung_ort());
    session_id($sid);
    session_start();
    $_SESSION = $inhalt;
    session_write_close();
    return $sid;
}

/**
 * Der PHP-Code einer Quelle — ohne Kommentare und ohne HTML (P5c/AP5).
 *
 * Beides wird durch Leerzeichen ERSETZT, nicht entfernt: Versatz und
 * Zeilennummer bleiben die der Quelle, und die Probe kann sagen, wo sie
 * etwas gefunden hat. Ein Zeilenumbruch bleibt ein Zeilenumbruch; jedes
 * andere Byte wird ein Leerzeichen (ohne `/u`, also byteweise — ein Umlaut
 * im Kommentar verschiebt nichts).
 *
 * WARUM FALL 18 DAS BRAUCHT: `login.php` nennt `login_zugang()` und
 * `rate_erfolg('salt', …)` auch in Kommentaren. Ein Muster ueber den
 * Rohtext traefe dort und maesse den Kommentar statt des Ablaufs.
 */
function nur_php_code(string $quelle): string {
    $aus = '';
    foreach (token_get_all($quelle) as $t) {
        if (is_array($t) && in_array($t[0], [T_COMMENT, T_DOC_COMMENT, T_INLINE_HTML], true)) {
            $aus .= (string)preg_replace('/[^\n]/', ' ', $t[1]);
        } else {
            $aus .= is_array($t) ? $t[1] : $t;
        }
    }
    return $aus;
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
    $pfad = sitzung_ort() . '/sess_' . $sid;
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
/* Rolle `betreiberin` seit S8/AP1: `betrieb_updates.php` — die Seite mit dem
 * Schalter — beginnt mit `require_betreiberin()`. Ein blosser `admin` kaeme
 * dort nicht hinein, und die Probe maesse dann den Waechter statt des Tors. */
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Wartungsprobe BetreiberIn', 'betreiberin', '', '', 320000)")->execute([$emailAdmin]);
$uidAdmin = (int)$pdo->lastInsertId();
/* Der Zweitfaktor der BetreiberIn gilt als eingerichtet (P5c/AP5, Kopf der
 * Datei). Das Tor fragt allein `totp_seit`; ein Geheimnis braucht keine Seite
 * dieser Probe. Ohne die Spalte (vor `update.php`) schweigt das Tor ohnehin. */
if (db_hat_spalte($pdo, 'users', 'totp_seit')) {
    $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP() WHERE id = ?')->execute([$uidAdmin]);
}
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Wartungsprobe Nutzer', 'user', '', '', 320000)")->execute([$emailUser]);
$uidUser = (int)$pdo->lastInsertId();

$epochAdmin = (int)$pdo->query("SELECT session_epoch FROM users WHERE id = $uidAdmin")->fetchColumn();
$epochUser  = (int)$pdo->query("SELECT session_epoch FROM users WHERE id = $uidUser")->fetchColumn();
['sid' => $sidAdmin, 'csrf' => $csrfAdmin] = sitzung_anlegen($uidAdmin, $epochAdmin);
['sid' => $sidUser]                            = sitzung_anlegen($uidUser,  $epochUser);
$sidHalb = sitzung_halb_anlegen($uidUser, $emailUser);

echo "Wartungsprobe gegen $basis\n";
echo "  Konten $emailAdmin (uid $uidAdmin, admin), $emailUser (uid $uidUser)\n";
echo "  Schalter " . WARTUNG_DATEI . ", Retry-After " . WARTUNG_RETRY_S . " s\n";

$warVorher = file_exists(WARTUNG_DATEI);
if ($warVorher) {
    echo "  ACHTUNG: Es lag schon eine wartung.lock — sie wird am Ende WIEDERHERGESTELLT.\n";
    $inhaltVorher = (string)file_get_contents(WARTUNG_DATEI);
}
$geraet = null;
/* Teil 6 raeumt eine Registerzeile beiseite. Die drei Variablen stehen VOR
 * dem `try`, damit der `finally` sie auch dann kennt, wenn es vorher
 * abbricht — sonst bliebe das Migrationsregister der Installation stehen,
 * wie die Probe es hinterlassen hat. */
$kandidat = null; $migAlt = null; $registerWeg = null; $torWeg = null;

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

/* HEALTH ANTWORTET IN DER WARTUNG AUS DEM TOR (P5c/AP6, E-P5c-52): 503
 * `maintenance`, OHNE Token-Pruefung — mit falschem Token dieselbe Antwort
 * wie ohne. Das Tor steht in `db.php`; `api/health.php` fuehrt keine Zeile
 * aus, und deshalb zaehlt auch sein Topf nichts. */
$pdo->exec("DELETE FROM rate_limits WHERE topf = 'health'");
$a5h = hole('api/health.php');
$a5i = hole('api/health.php?token=falsch-und-zwar-eindeutig');
$healthZeilen = (int)$pdo->query("SELECT COUNT(*) FROM rate_limits WHERE topf = 'health'")->fetchColumn();
pruefe($a5h['code'] === 503 && ($a5h['daten']['error'] ?? '') === 'maintenance'
       && $a5i['code'] === 503 && ($a5i['daten']['error'] ?? '') === 'maintenance' && $healthZeilen === 0,
       '5a  api/health.php -> 503 maintenance aus dem Tor, ohne Token-Pruefung',
       'HTTP ' . $a5h['code'] . ' / ' . $a5i['code'] . ', Topf health ' . $healthZeilen);

/* ======================================================================
 * Teil 2 — mit Wartung: was offen bleibt (E-S5W-04)
 * ====================================================================== */
echo "\n  Teil 2 — mit Wartung: was offen bleibt\n";

$a6 = hole('betrieb_updates.php', $sidAdmin);
pruefe($a6['code'] === 200, '6   betrieb_updates.php mit BetreiberIn-Sitzung -> 200',
       'HTTP ' . $a6['code']);
pruefe(str_contains($a6['rumpf'], 'Wartungsmodus seit'),
       '6   ... traegt den Balken „Wartungsmodus seit"');
pruefe(str_contains($a6['rumpf'], 'Wartungsmodus ausschalten'),
       '6   ... und den Knopf „Wartungsmodus ausschalten"');

$a6b = hole('betrieb_jobs.php', $sidAdmin);
$a6c = hole('betrieb_server.php', $sidAdmin);
$a6d = hole('betrieb_status.php', $sidAdmin);
$a6e = hole('betrieb_statistik.php', $sidAdmin);
pruefe($a6b['code'] === 200 && $a6c['code'] === 200
       && $a6d['code'] === 200 && $a6e['code'] === 200,
       '6   ... und die vier anderen Betriebsseiten ebenso (F-S8-P-04)',
       'jobs ' . $a6b['code'] . ', server ' . $a6c['code']
       . ', status ' . $a6d['code'] . ', statistik ' . $a6e['code']);

/* DAS SCHLUESSELBLATT IST DIE SECHSTE AUSNAHMESEITE (S10/AP3).
 *
 * Es steht seit Web 20.1.0 in `WARTUNG_AUSNAHMEN`, und zwar aus einem Grund,
 * der genau hier zu messen ist: Die Lage, in der jemand den Server-Anteil vom
 * Blatt abliest, IST eine Wartungslage — `config.php` ist verlorengegangen
 * oder wird gerade wiederhergestellt. Waere die Seite gesperrt, stuende der
 * Wert hinter genau der Tuer, die man ohne ihn nicht mehr aufbekommt. */
$a6f = hole('betrieb_schluesselblatt.php', $sidAdmin);
pruefe($a6f['code'] === 200,
       '6a  betrieb_schluesselblatt.php mit BetreiberIn-Sitzung -> 200 (S10/AP3)',
       'HTTP ' . $a6f['code']);

/* WER IN DER AUSNAHMELISTE STEHT, ZEIGT DEN BALKEN (S8/AP8).
 *
 * Der Balken ist die einzige Stelle, an der ein stehengebliebener
 * Wartungsmodus auffaellt — es gibt kein automatisches Ausschalten
 * (E-S5W-05). `betrieb_statistik.php` stand bis Web 15.5.1 als einzige der
 * fuenf Ausnahmeseiten OHNE ihn da, und zwar unbemerkt: Erwartung 6 mass den
 * Statuscode, nicht den Balken. Ein Prueffall, der nur zaehlt, dass eine
 * Seite antwortet, sieht nicht, WAS sie antwortet. */
$ohneBalken = [];
foreach (['betrieb_updates.php' => $a6, 'betrieb_jobs.php' => $a6b,
          'betrieb_server.php'  => $a6c, 'betrieb_status.php' => $a6d,
          'betrieb_statistik.php' => $a6e] as $name => $antwort) {
    if (!str_contains($antwort['rumpf'], 'Wartungsmodus seit')) { $ohneBalken[] = $name; }
}
pruefe($ohneBalken === [],
       '6   ... und ALLE FUENF tragen den Balken „Wartungsmodus seit"',
       'ohne Balken: ' . implode(', ', $ohneBalken));

/* KOMPLETT-BACKUP UND BACKUP-ZIELE SEIT P5c/AP9 (E-P5c-134).
 *
 * Bis Web 21.0.0 antworteten beide im Wartungsmodus mit 503 — und die Seite
 * Updates bot, wenn der Torwaechter geschlossen hatte, einen Knopf zum
 * Komplett-Backup an, der genau dort hineinfuehrte. Gemessen wird hier, dass
 * beide antworten UND den Balken tragen: Ein Statuscode allein sagt nicht,
 * was die Seite zeigt (siehe oben, Web 15.5.1). */
$a6g = hole('admin_komplettsicherung.php', $sidAdmin);
$a6h = hole('admin_sicherungsziele.php', $sidAdmin);
pruefe($a6g['code'] === 200 && $a6h['code'] === 200
       && str_contains($a6g['rumpf'], 'Wartungsmodus seit')
       && str_contains($a6h['rumpf'], 'Wartungsmodus seit'),
       '6b  Komplett-Backup und Backup-Ziele offen, mit Balken (P5c/AP9)',
       'komplett ' . $a6g['code'] . ', ziele ' . $a6h['code']);

/* DAS SCHLUESSELBLATT TRAEGT IHN NICHT — UND ZWAR ABSICHTLICH.
 *
 * Es ist die sechste Ausnahmeseite, steht aber bewusst NICHT in der Schleife
 * darueber: Die Seite hat kein Geruest, kein Menue und keine Kopfleiste
 * (dieselbe Bauform wie `wartung_seite_html()`), weil sie gedruckt wird —
 * ein Balken auf dem Papier waere ein Fremdkoerper. Die Erwartung steht hier
 * als AUSDRUECKLICHE Gegenaussage, damit niemand die Seite spaeter „der
 * Vollstaendigkeit halber" in die Schleife oben nimmt und sich wundert. */
pruefe(!str_contains($a6f['rumpf'], 'Wartungsmodus seit')
       && !str_contains($a6f['rumpf'], '<nav'),
       '6a  ... und das Blatt traegt WEDER Balken NOCH Geruest (es wird gedruckt)',
       'Balken ' . (str_contains($a6f['rumpf'], 'Wartungsmodus seit') ? 'da' : 'weg')
       . ', <nav> ' . (str_contains($a6f['rumpf'], '<nav') ? 'da' : 'weg'));

$a7 = hole('betrieb_updates.php', $sidUser);
pruefe($a7['code'] !== 503,
       '7   betrieb_updates.php mit NUTZER-Sitzung: nicht 503 (Abweisung wie sonst)',
       'HTTP ' . $a7['code']);

/* `update.php` ist seit S8/AP3 eine 302 auf betrieb_updates.php — offen
 * heisst hier also 302 und nicht 503. Genau das ist die Aussage: Die alte
 * Adresse bleibt im Wartungsmodus erreichbar und leitet weiter. */
$a7b = hole('update.php', $sidAdmin);
pruefe($a7b['code'] === 302,
       '7   update.php (alte Adresse) leitet weiter statt 503', 'HTTP ' . $a7b['code']);

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

/* 10a  UND DAS FORMULAR LAESST SICH AUCH ABSCHICKEN (Backlog Nr. 171).
 *
 * Erwartung 10 war gruen, waehrend der Rueckweg in Wahrheit zu war: Die
 * Seite kam mit Balken und Formular, aber `login.php` holt vor dem Absenden
 * das Salt und die Rundenzahlen aus `auth_salt.php` — ohne sie leitet der
 * Browser kein Token ab und schickt nie ab. Der Endpunkt stand nicht in
 * WARTUNG_AUSNAHMEN, lud aber `db.php` und liegt nicht unter `/api/`; er
 * bekam also die HTML-Wartungsseite, und die Anmeldeseite schrieb
 * „Anmeldung derzeit nicht möglich".
 *
 * DAS IST DER FALL AUS CLAUDE.md 6: eine gruene Zahl, die nicht benennt,
 * was sie gemessen hat. Ein Formular, das nicht abgesendet werden kann,
 * erfuellt „mit Anmeldeformular" — und niemand sah es, weil die Probe den
 * Nebenaufruf nicht kannte. Deshalb misst sie ihn jetzt. */
/* JSON, nicht Formular: auth_salt.php liest den Rumpf als JSON. `hole()`
 * entscheidet das an der Kopfzeile. */
$a10b = hole('auth_salt.php', null, ['email' => 'admin@gen-em.org'],
             ['Content-Type: application/json']);
pruefe($a10b['code'] === 200,
       '10a auth_salt.php -> 200 (ohne ihn ist login.php nutzlos)',
       'HTTP ' . $a10b['code']);
pruefe(isset($a10b['daten']['salt']) && is_string($a10b['daten']['salt']),
       '10a ... und liefert JSON mit `salt`',
       substr((string)$a10b['rumpf'], 0, 60));

$a11 = hole('wiederherstellen.php', $sidAdmin);
pruefe($a11['code'] !== 503, '11  wiederherstellen.php mit Admin-Sitzung: nicht 503',
       'HTTP ' . $a11['code']);

$a12 = hole('assets/style.css');
pruefe($a12['code'] === 200, '12  assets/style.css -> 200 (statisch, ungetort)',
       'HTTP ' . $a12['code']);

/* 12a  DIE INTEGRITAETSWACHE LAEUFT AUCH IM WARTUNGSMODUS DURCH
 *      (Backlog Nr. 140, SP-6).
 *
 * WARUM DAS HIERHER GEHOERT. Die Wache laeuft taeglich und vergleicht die
 * ausgelieferten Dateien und den Inline-Block der Anmeldeseite mit dem
 * Repositorium. Wuerde der Wartungsmodus eines von beidem veraendern -- ein
 * getortes `assets/`, ein Balken IM Skriptblock statt darueber --, ginge der
 * Lauf jedes Mal rot, wenn jemand ein Update fuehrt. Eine Wache, die
 * regelmaessig aus einem harmlosen Grund rot wird, ist nach dem dritten Mal
 * abgeschaltet.
 *
 * Gemessen wird beides an derselben Stelle: Das Stylesheet kommt (12), und der
 * Inline-Block der Anmeldeseite ist derselbe wie in der Quelldatei -- der
 * Wartungsbalken steht im Markup, nicht im Skript. */
/* EIN `?>` IM TAG BEENDET DAS TAG NICHT -- fuer HTML schon, fuer uns nicht.
 * Seit Web 20.7.0 traegt jedes Inline-Skript seinen CSP-Nonce, und in der
 * QUELLE steht das als `<script<?= kopf_nonce_attr() ?>>`. Ein `[^>]*>` endet
 * am `>` des PHP-Schlusses, und der Block begann danach mit einem
 * ueberzaehligen `>` -- die Auslieferung hat es nicht, also stimmte keine
 * Pruefsumme mehr. Dieselbe Falle traf die Integritaetswache (Fund 23);
 * `$tagRest` ist ihre Antwort, hier in PCRE: erst ein PHP-Stueck am Stueck,
 * sonst ein einzelnes Zeichen, das kein `>` ist. */
/* SEIT P5c/AP5 KENNT DIE SEITE EINEN HALBEN STAND (Passwort ja, Code nein).
 * Endet er ohne Anmeldung, raeumt ein Block das Vormerkfach
 * (`EdCrypto.vergissAbleitungen()`). Dieser Block steht IMMER in der
 * Passwortseite und schaltet ueber `data-vergessen` — sonst saehe die
 * Integritaetswache, die ohne Sitzung fragt, ihn nie. Gemessen wird trotzdem
 * an zwei Abrufen, je einer Richtung, damit ein spaeterer bedingter Block
 * hier auffaellt statt still an der Wache vorbei:
 *
 *   - MIT ABGELAUFENER HALBER ANMELDUNG: Jeder PHP-freie Block der Quelle
 *     steht so da — dieselbe Aussage wie bis Web 20.41.
 *   - OHNE SITZUNG, also die Seite, die die Integritaetswache abruft, steht
 *     kein Block, den die Quelle nicht hat. Ein Balken IM Skript fiele hier
 *     auf. Nachsicht nur fuer so viele, wie die Quelle Bloecke MIT PHP darin
 *     hat — dieselbe Rechnung wie `zusatz` in `wache.py`.
 *
 * Welcher Zustand welchen Block zuschaltet, liest die Probe NICHT aus dem
 * Quelltext. Sie verlangt nur: Keiner fehlt, wenn alle geschaltet sind, und
 * keiner ist fremd, wenn nicht. */
$tagRest = '(?:<\?(?:php\b|=).*?\?>|[^>])*';
$blockRe = '/<script(?!' . $tagRest . '\bsrc=)' . $tagRest . '>(.*?)<\/script>/s';
preg_match_all($blockRe, (string)file_get_contents(dirname(__DIR__, 3) . '/server/login.php'), $mQ);
$a10h = hole('login.php', $sidHalb);
preg_match_all($blockRe, (string)$a10h['rumpf'], $mH);
preg_match_all($blockRe, (string)$a10['rumpf'], $mA);
$summe        = static fn(string $b): string => hash('sha256', $b);
$quellBloecke = array_values(array_filter($mQ[1] ?? [], static fn($b) => !str_contains($b, '<?')));
$quellSummen  = array_map($summe, $quellBloecke);
$mitPhp       = count($mQ[1] ?? []) - count($quellBloecke);
$halbSummen   = array_map($summe, $mH[1] ?? []);
$ohneSummen   = array_map($summe, $mA[1] ?? []);
$gefunden = count(array_filter($quellSummen, static fn($h) => in_array($h, $halbSummen, true)));
$fremd    = count(array_filter($ohneSummen, static fn($h) => !in_array($h, $quellSummen, true)));
pruefe($quellBloecke !== [] && $gefunden === count($quellBloecke)
       && $ohneSummen !== [] && $fremd <= $mitPhp,
       '12a Der Inline-Block der Anmeldeseite ist im Wartungsmodus unveraendert',
       $gefunden . ' von ' . count($quellBloecke) . ' PHP-freien Bloecken der Quelle '
       . 'stehen so in der Auslieferung (abgelaufene halbe Anmeldung, HTTP '
       . $a10h['code'] . '); ohne Sitzung ' . count($ohneSummen) . ' Bloecke, '
       . $fremd . ' davon fremd — sonst ginge die Integritaetswache '
       . 'bei jedem Update rot (Nr. 140)');

/* ======================================================================
 * Teil 3 — Schalten, kaputter Inhalt, Antwortzeit
 * ====================================================================== */
echo "\n  Teil 3 — Schalten, kaputter Inhalt, Antwortzeit\n";

$a13 = hole('betrieb_updates.php', $sidAdmin, ['action' => 'wartung_aus', 'csrf' => $csrfAdmin]);
pruefe($a13['code'] === 200 && !file_exists(WARTUNG_DATEI),
       '13  Ausschalten ueber betrieb_updates.php (POST, CSRF) -> Datei weg',
       'HTTP ' . $a13['code']);
$a13b = hole('index.php');
pruefe($a13b['code'] !== 503, '13  ... und index.php antwortet wieder',
       'HTTP ' . $a13b['code']);

$a13c = hole('betrieb_updates.php', $sidAdmin, ['action' => 'wartung_an', 'csrf' => $csrfAdmin]);
pruefe($a13c['code'] === 200 && file_exists(WARTUNG_DATEI),
       '13  Einschalten ueber betrieb_updates.php -> Datei da', 'HTTP ' . $a13c['code']);
$d = json_decode((string)file_get_contents(WARTUNG_DATEI), true);
pruefe(is_array($d) && ($d['von'] ?? '') === 'Wartungsprobe BetreiberIn' && !empty($d['seit']),
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
 * Teil 0 ohne Wartung.
 *
 * DER MEDIAN AUS FUENF, NICHT EIN ABRUF (F-P5c-165). Die 503 braucht hier
 * 1,4 bis 1,9 ms, der Vergleich 3,3 bis 5,8 ms — aber ein einzelner Abruf
 * sprang in einem von zwoelf Laeufen auf 4,4 ms, und ein Vergleich zweier
 * Einzelwerte im Millisekundenbereich faerbt den Pruefstand dann rot, ohne
 * dass das Tor sich geaendert haette. Die 503 hat keine Nebenwirkung (das Tor
 * greift vor jedem Zaehler), deshalb darf sie fuenfmal kommen; der Vergleich
 * aus Teil 0 bleibt EIN Abruf, weil jeder weitere den Adresstopf zaehlte. */
$dauern15 = [];
for ($i = 0; $i < 5; $i++) {
    $a15 = hole('ingest.php', null, ['mission' => ['x' => 1]],
                ['Content-Type: application/json', 'X-Device-Id: ' . $devId, 'X-Api-Key: ' . $apiKey]);
    $dauern15[] = $a15['code'] === 503 ? $a15['dauer'] : INF;
}
sort($dauern15);
pruefe($dauern15[2] < $dauerOhne,
       '15  503 kommt schneller als die Antwort ohne Wartung',
       sprintf('Median %.1f ms aus 5 statt %.1f ms', $dauern15[2] * 1000, $dauerOhne * 1000));

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
/* `auth_salt.php` ist mit Web 19.1.2 dazugekommen (Backlog Nr. 171):
 * `login.php` stand schon in der Liste, war ohne diesen Nebenaufruf aber
 * nicht benutzbar — der Browser holt dort Salt und Rundenzahlen und leitet
 * ohne sie kein Token ab. Erwartung 10 meldete trotzdem gruen, weil sie das
 * Formular sah und nicht seinen Weg; Erwartung 10a misst ihn seither. */
/* `betrieb_schluesselblatt.php` ist mit Web 20.1.0 dazugekommen (S10/AP3).
 * Die Begruendung steht bei Erwartung 6a: Wer den Server-Anteil vom Blatt
 * abliest, tut das in einer Wartungslage — die Seite hinter der Sperre waere
 * genau dann unerreichbar, wenn man sie braucht.
 * DIESE ZEILE WAR DER BEFUND VON AP5: Die Ausnahmeliste wuchs in AP3 auf 13
 * Eintraege, die Erwartung zaehlte weiter 12, und die Wartungsprobe stand
 * seither auf 1 nicht erfuellt — bemerkt hat es niemand, weil AP3 sie nicht
 * gefahren hat. Genau dafuer ist die Erwartung da. */
$sollAusnahmen = ['betrieb_status.php', 'betrieb_sicherheit.php',
                  'betrieb_statistik.php',
                  'betrieb_updates.php', 'betrieb_jobs.php', 'betrieb_server.php',
                  'betrieb_schluesselblatt.php',
                  'admin_komplettsicherung.php', 'admin_sicherungsziele.php',
                  'update.php', 'wiederherstellen.php', 'jobs.php',
                  'login.php', 'auth_salt.php', 'logout.php', 'install.php'];
sort($sollAusnahmen);
$istAusnahmen = WARTUNG_AUSNAHMEN;
sort($istAusnahmen);
pruefe($istAusnahmen === $sollAusnahmen,
       '17  Ausnahmeliste ist genau die aus E-S5W-04 + S8/AP2 + S8/AP4 + Nr. 171 + S10 + P5a/AP8 + P5c/AP9',
       implode(', ', $istAusnahmen));

/* E-S5W-09 am Code: login.php muss `role` lesen und im Wartungsmodus fuer
 * Nicht-Admins die Sitzung verwerfen. Ueber HTTP ist der Zweig nicht
 * erreichbar (das Token entsteht im Browser per PBKDF2), also wird die
 * Stelle gelesen — mit Nennung dessen, was gelesen wurde. */
$loginQuelle = (string)file_get_contents($wurzel . '/login.php');
pruefe(str_contains($loginQuelle, 'kdf_iter, logo_wahl, role'),
       '18  login.php liest `role` in seiner Nutzerabfrage (E-S5W-09 c)');
/* SEIT P5c/AP5 STEHT DAS TOR IN EINER FUNKTION, und die Reihenfolge im Text
 * sagt nichts mehr. `login_zugang()` steht am Kopf der Datei, also VOR jedem
 * `rate_erfolg()`: Die alte Pruefung „Tor nach rate_erfolg im Text" war rot,
 * obwohl der Ablauf stimmt — und waere gruen, wenn jemand den AUFRUF vor das
 * rate_erfolg zoege. Gelesen wird deshalb der Ablauf: wo das Tor steht, wo
 * es aufgerufen wird und was davor steht. Es gibt zwei Aufrufe, weil es zwei
 * Schritte gibt — nach dem Passwort und nach dem Code; in den fuenf Minuten
 * dazwischen kann die Wartung eingeschaltet worden sein (E-P5c-53).
 *
 * Am PHP-Code ohne Kommentare (`nur_php_code()`), mit den Zeilennummern der
 * Quelle in der Ausgabe. */
$loginCode = nur_php_code($loginQuelle);
$zeileVon  = static fn(int $pos): string => 'Z. ' . (substr_count($loginCode, "\n", 0, $pos) + 1);
/* Die Funktionen am Kopf: Name => [von, bis]. In `login.php` beginnen sie in
 * Spalte 0 und enden mit `}` in Spalte 0. */
$funktionen = [];
preg_match_all('/^function\s+(\w+)\s*\(/m', $loginCode, $mF, PREG_OFFSET_CAPTURE);
foreach ($mF[0] as $i => [, $von]) {
    $ende = strpos($loginCode, "\n}", $von);
    $funktionen[$mF[1][$i][0]] = [$von, $ende === false ? strlen($loginCode) : $ende + 2];
}
/** Treffer im HAUPTABLAUF, nicht in einer Funktion am Kopf: [[Versatz, Gruppe 1], …]. */
$imAblauf = static function (string $muster) use ($loginCode, $funktionen): array {
    preg_match_all($muster, $loginCode, $m, PREG_OFFSET_CAPTURE);
    $aus = [];
    foreach ($m[0] as $i => [, $pos]) {
        foreach ($funktionen as [$von, $bis]) {
            if ($pos >= $von && $pos < $bis) { continue 2; }
        }
        $aus[] = [$pos, $m[1][$i][0] ?? ''];
    }
    return $aus;
};
[$zVon, $zBis] = $funktionen['login_zugang'] ?? [0, 0];
$zugangKoerper = substr($loginCode, $zVon, $zBis - $zVon);
$zugang    = $imAblauf('/\blogin_zugang\s*\(/');
$vollenden = $imAblauf('/\banmeldung_vollenden\s*\(/');
$erfolge   = $imAblauf("/\\brate_erfolg\\s*\\(\\s*'(\\w+)'/");

/* Seit S8/AP1 fragt login.php nicht mehr `!== 'admin'`, sondern das Praedikat
 * `rolle_darf_verwalten()` — sonst haette die neue Rolle `betreiberin` als
 * Nicht-Admin gegolten und sich waehrend der Wartung selbst ausgesperrt.
 *
 * `(false)` seit Web 19.1.3 (Backlog Nr. 126): An DIESER Stelle ist die
 * Rolle bekannt und reicht nicht — ein Rueckweg-Knopf fuehrte garantiert
 * auf ein 403. Die Probe prueft den Parameter mit, sonst faellt er beim
 * naechsten Umbau still weg.
 *
 * SEIT P5c/AP5 IN EINEM MUSTER, im Rumpf von `login_zugang()`: Bedingung,
 * `session_verwerfen()` und die Seite ohne Rueckweg in DEMSELBEN Zweig. Bis
 * dahin durfte `session_verwerfen();` irgendwo stehen — es steht auch im
 * Zweig des Kontostatus darueber, und der haette die Erwartung allein
 * erfuellt. Am Code ohne Kommentare, weil ein `}` in einem Kommentar
 * zwischen den drei Stellen das Muster sonst still abbraeche.
 *
 * DAZU DER ABLAUF: Kein Aufruf von `anmeldung_vollenden()` — der Stelle, die
 * `user_id` setzt — ohne das Tor davor. Das ist E-S5W-09 im Ablauf mit
 * Code-Schritt: Wer die Sitzung bekommt, ist vorher gefragt worden. */
$ohneTor = [];
$vorher  = 0;
foreach ($vollenden as [$pos]) {
    $tor = array_filter($zugang, static fn(array $z): bool => $z[0] > $vorher && $z[0] < $pos);
    if ($tor === []) { $ohneTor[] = $zeileVon($pos); }
    $vorher = $pos;
}
pruefe(preg_match('/wartung_aktiv\(\)\s*&&\s*!rolle_darf_verwalten\([^{]*\{\s*session_verwerfen\(\);'
                  . '[^}]*wartung_antwort_seite\(false\);/', $zugangKoerper) === 1
       && $vollenden !== [] && $ohneTor === [],
       '18  ... und verwirft im Wartungsmodus die Sitzung ohne Verwaltungsrecht (ohne Rueckweg)',
       'Tor in login_zugang() ' . ($zugangKoerper !== '' ? $zeileVon($zVon) : 'nicht gefunden')
       . ', ' . count($vollenden) . ' Sitzungsanlage(n) '
       . ($ohneTor === [] ? 'je dahinter' : '— ohne Tor davor: ' . implode(', ', $ohneTor)));

/* Und jeder Aufruf des Tors steht HINTER dem rate_erfolg seines Schritts:
 * nach dem Passwort hinter `rate_erfolg('login')`, nach dem Code hinter
 * `rate_erfolg('totp')`. Wer waehrend der Wartung richtig tippt, darf sich
 * danach nicht ausgesperrt finden (E-S5W-09 b) — fuer beide Toepfe. */
$stellen = []; $ohneErfolg = 0; $toepfe = [];
$vorher  = 0;
foreach ($zugang as [$pos]) {
    $namen = array_column(array_filter($erfolge,
        static fn(array $e): bool => $e[0] > $vorher && $e[0] < $pos), 1);
    $toepfe    = array_merge($toepfe, $namen);
    $stellen[] = 'Aufruf ' . $zeileVon($pos) . ' hinter ' . ($namen === [] ? 'nichts' : implode('/', $namen));
    if ($namen === []) { $ohneErfolg++; }
    $vorher = $pos;
}
pruefe(count($zugang) >= 2 && $ohneErfolg === 0
       && in_array('login', $toepfe, true) && in_array('totp', $toepfe, true),
       '18  ... aber ERST nach rate_erfolg — richtiges Passwort sperrt nicht (E-S5W-09 b)',
       $stellen === [] ? 'kein Aufruf von login_zugang()' : implode('; ', $stellen));

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

/* 19a  DER RUECKWEG (Backlog Nr. 126, Web 19.1.3).
 *
 * Bis dahin trug die Seite NULL Verweise — gemessen am gerenderten Markup —,
 * und der einzige Weg zurueck war, `betrieb_updates.php` von Hand zu tippen.
 * Das Handbuch (12.3) beschrieb den Weg trotzdem. Faellig geworden ist der
 * Punkt erst mit Nr. 171: Vorher kam man gar nicht erst so weit, weil sich
 * das Anmeldeformular nicht abschicken liess. */
pruefe(str_contains($seite['rumpf'], 'href="betrieb_updates.php"')
       && str_contains($seite['rumpf'], 'Zur Verwaltung'),
       '19a ... und traegt den Rueckweg in die Verwaltung');
/* Die Gegenprobe am Code: An der einen Stelle, an der die Rolle bekannt und
 * unzureichend ist, steht der Knopf NICHT — sonst fuehrte er auf ein 403. */
pruefe(str_contains($loginQuelle, 'wartung_antwort_seite(false);')
       && preg_match('/function wartung_seite_html\(bool \$rueckweg = true\)/',
                     (string)file_get_contents($wurzel . '/wartung_lib.php')) === 1,
       '19a ... und login.php zeigt sie ohne ihn (Rolle bekannt, reicht nicht)');

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

/* ======================================================================
 * Teil 6 — Migrationen: EINE Zaehlweise (Backlog Nr. 149)
 * ======================================================================
 *
 * DER FALL, DEN NIEMAND GEMESSEN HAT. Eine Migration, deren Schema schon
 * aktuell ist, die aber im Register fehlt, zaehlt als offen — Status und
 * Menue melden „1 Migration steht aus". Bis Web 15.5.2 sortierte die Seite
 * Updates dieselbe Zeile mit Status `ok` unter „Ausgefuehrt", meldete
 * daneben „Alles aktuell" und zeigte den Knopf nicht. Zwei Zaehlweisen fuer
 * einen Sachverhalt, und der Registervermerk war im Web nicht nachzuholen.
 *
 * Genau dieser Zustand stand am 06.09.2026 auf dem Produktivserver: Die
 * Rollenmigration aus S8 war von Hand ueber phpMyAdmin gelaufen (der
 * Web-Weg verlangte die Rolle, die sie erst vergibt), der Registervermerk
 * fehlte. Hier wird er nachgebaut.
 *
 * WARUM DIESER TEIL GANZ AM ENDE STEHT. Erwartung 16 in Teil 4 ruft
 * `php update.php` auf — und das FUEHRT AUS. Stuende dieser Teil davor,
 * legte Erwartung 16 die geloeschte Registerzeile stillschweigend wieder an;
 * die Messung waere gruen und maesse nichts mehr.
 *
 * WARUM SIE IM WARTUNGSMODUS LAEUFT. Teil 5 hat die `wartung.lock` gesetzt,
 * und sie bleibt stehen. Das ist kein Versehen: Ein Update laeuft nun einmal
 * in der Wartung, und beide Seiten stehen in WARTUNG_AUSNAHMEN (Erwartung 6).
 * ====================================================================== */
echo "\n  Teil 6 — Migrationen: eine Zaehlweise (Nr. 149)\n";

require_once $wurzel . '/migration_lib.php';

/* Die Kennung wird GESUCHT, nicht hingeschrieben: Sie muss im Register
 * stehen UND eine `skip`-Pruefung haben, die wahr liefert — sonst fuehrte
 * der Knopfdruck weiter unten echtes SQL aus. Ein fest verdrahteter String
 * zeigte ausserdem auf die falsche Migration, sobald eine neue angehaengt
 * wird. */
$imRegister = $pdo->query('SELECT id FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$kandidat   = null;
foreach (array_reverse(migrationen_katalog()) as $m) {
    if (!in_array($m['id'], $imRegister, true) || !isset($m['skip'])) { continue; }
    try {
        if (($m['skip'])($pdo)) { $kandidat = (string)$m['id']; break; }
    } catch (Throwable $ex) { /* nicht feststellbar — naechste */ }
}

/* VORBEDINGUNG, und sie ist eine Sicherung, keine Formalie: Der Knopf fuehrt
 * ALLE ausstehenden Migrationen aus, nicht nur die eine. Steht auf dieser
 * Installation ohnehin etwas offen, darf die Probe ihn nicht druecken —
 * darunter waeren Migrationen, die Spalten loeschen. */
$vorLauf = migrationen_lauf($pdo, false);
$bereit  = $kandidat !== null && (int)$vorLauf['offen'] === 0;
if ($bereit) {
    $abfrage = $pdo->prepare('SELECT status, applied_at FROM schema_migrations WHERE id = ?');
    $abfrage->execute([$kandidat]);
    $migAlt  = $abfrage->fetch(PDO::FETCH_ASSOC) ?: null;
    $bereit  = $migAlt !== null;
}
pruefe($bereit,
       '21  Vorbedingung: nichts offen, eine verbuchte Migration mit skip=wahr',
       $kandidat === null ? 'keine Kennung mit skip=wahr gefunden'
                          : $kandidat . ', offen ' . (int)$vorLauf['offen']);

/* Der Menuezaehler liegt 60 s in `app_state` (STATUS_CACHE_S). Wer ihn nicht
 * leert, misst den Zwischenspeicher statt der Anwendung — und zwar in beide
 * Richtungen: vor dem Loeschen die alte Null, nach dem Klick die alte Eins. */
$frisch = static function () use ($pdo): void {
    $pdo->exec("DELETE FROM app_state
                 WHERE k IN ('menue_zaehler_betrieb', 'status_ampel')");
};
$nichtGemessen = 'Vorbedingung 21 nicht erfuellt — nicht gemessen';

if ($bereit) {
    $pdo->prepare('DELETE FROM schema_migrations WHERE id = ?')->execute([$kandidat]);
    $registerWeg = $kandidat;   // fuer den finally, falls es hier abbricht

    $frisch();
    $s21 = hole('betrieb_status.php', $sidAdmin);
    pruefe(str_contains($s21['rumpf'], '1 Migration steht aus'),
           '22  Status: „1 Migration steht aus"', 'HTTP ' . $s21['code']);

    /* Der Menuepunkt traegt den Zaehler als eigenes Element mit `aria-label`
     * — gegen das Markup gemessen und nicht gegen die blosse Ziffer, die auf
     * einer Seite mit siebzehn Menuepunkten auch anderswo steht. */
    pruefe((bool)preg_match('/Updates<\/span>\s*<span class="zaehler[^"]*"'
                            . '\s*aria-label="1 ausstehend">1<\/span>/', $s21['rumpf']),
           '23  Menuezaehler an „Updates" nennt dieselbe 1');

    $frisch();
    $u21 = hole('betrieb_updates.php', $sidAdmin);
    pruefe(str_contains($u21['rumpf'], '1 Update')
           && !str_contains($u21['rumpf'], 'Es steht nichts an.'),
           '24  Updates: Karte nennt „1 Update", nicht „Alles aktuell"',
           'HTTP ' . $u21['code']);
    pruefe(str_contains($u21['rumpf'], 'nicht nötig')
           && str_contains($u21['rumpf'], $kandidat),
           '25  ... die Zeile traegt die neutrale Plakette „nicht nötig"');
    /* GEGEN DAS FORMULAR GEMESSEN, NICHT GEGEN DEN WORTLAUT. Der Text
     * „Ausstehende ausführen" steht auf dieser Seite ZWEIMAL: als Knopf und
     * als Schritt 4 im fuenfstufigen Ablauf der Karte „Wartungsmodus". Der
     * erste Entwurf suchte nur den Wortlaut — und war damit auch dann gruen,
     * wenn der Knopf fehlte, also genau im Fehlerfall von Nr. 149. Gemessen
     * in der Gegenprobe gegen den Stand vor der Behebung: 26 gruen, obwohl
     * kein Knopf da war. `form="migform"` traegt nur der Knopf. */
    pruefe((bool)preg_match('/<button[^>]*form="migform"[^>]*>.*?Ausstehende ausführen/su',
                            $u21['rumpf']),
           '26  ... und der Knopf „Ausstehende ausführen" ist da (form="migform")');

    /* Druecken — derselbe POST, den der Knopf schickt. */
    $klick = hole('betrieb_updates.php', $sidAdmin,
                  ['action' => 'migrate', 'csrf' => $csrfAdmin]);
    $frisch();
    $nach    = migrationen_lauf($pdo, false);
    $eintrag = $pdo->prepare('SELECT status FROM schema_migrations WHERE id = ?');
    $eintrag->execute([$kandidat]);
    $status  = $eintrag->fetchColumn();
    if ($status !== false) { $registerWeg = null; }
    pruefe((int)$nach['offen'] === 0 && $status === 'skipped'
           && str_contains($klick['rumpf'], 'wurden angewendet'),
           '27  Nach dem Klick: 0 offen, Register „skipped", Meldung sagt es',
           'offen ' . (int)$nach['offen'] . ', Register '
           . var_export($status, true) . ', HTTP ' . $klick['code']);
} else {
    pruefe(false, '22  Status: „1 Migration steht aus"',                       $nichtGemessen);
    pruefe(false, '23  Menuezaehler an „Updates" nennt dieselbe 1',            $nichtGemessen);
    pruefe(false, '24  Updates: Karte nennt „1 Update", nicht „Alles aktuell"', $nichtGemessen);
    pruefe(false, '25  ... die Zeile traegt die neutrale Plakette „nicht nötig"', $nichtGemessen);
    pruefe(false, '26  ... und der Knopf „Ausstehende ausführen" ist da',      $nichtGemessen);
    pruefe(false, '27  Nach dem Klick: 0 offen, Register „skipped", Meldung sagt es', $nichtGemessen);
}

/* ======================================================================
 * Teil 7 — DER TORWAECHTER (P5a/AP3, E-P5a-20; R40 (4), Backlog Nr. 54)
 * ======================================================================
 *
 * DER FALL: Der Katalog kennt eine Migration, das Register nicht — und
 * NIEMAND hat den Wartungsmodus eingeschaltet. Bis Web 20.5.0 blieb die
 * Installation dann offen, und wer in dieses Fenster geriet, bekam einen
 * Fehler aus einer halb umgebauten Datenbank. Seit AP3 schliesst die
 * Anwendung sich selbst.
 *
 * ER STEHT HINTER TEIL 6, weil er dieselbe Kennung braucht und weil Teil 6
 * sie sauber hinterlaesst. Und er beginnt damit, den Wartungsmodus
 * AUSZUSCHALTEN — sonst liesse sich nicht sehen, dass er von selbst angeht.
 *
 * NR. 54 WIRD MITGEMESSEN, und zwar in der Richtung, die weh tut: Der
 * Zwischenspeicher haengt am Katalog-Hash, und der aendert sich beim
 * Einspielen eines fremden Dumps NICHT. Erwartung 32 zeigt, dass der
 * Torwaechter dann stumm bliebe; Erwartung 33 zeigt, dass
 * `migrationen_tor_zuruecksetzen()` — der Aufruf, den
 * `wiederherstellen.php` jetzt macht — ihn wieder sehend macht.
 * ====================================================================== */
echo "\n  Teil 7 — der Torwaechter (E-P5a-20, Nr. 54)\n";

$torBereit = $bereit && $kandidat !== null && $migAlt !== null;
$torWeg    = null;   // Kennung, die dieser Teil aus dem Register genommen hat

if ($torBereit) {
    wartung_weg();
    migrationen_tor_zuruecksetzen($pdo);
    $frisch();
    pruefe(!wartung_aktiv(), '28  Vorbedingung: Wartung aus, Zwischenspeicher leer');

    /* Die Registerzeile wieder herausnehmen — ohne den Schalter anzufassen. */
    $pdo->prepare('DELETE FROM schema_migrations WHERE id = ?')->execute([$kandidat]);
    $torWeg = $kandidat;
    migrationen_tor_zuruecksetzen($pdo);

    /* Eine ANGEMELDETE Seite, die keine Ausnahme ist. `auth_guard.php`
     * stellt die Frage; `index.php` beantwortet sie mit 503. */
    $t1 = hole('index.php', $sidAdmin);
    pruefe($t1['code'] === 503, '29  Angemeldete Seite antwortet 503',
           'HTTP ' . $t1['code']);
    pruefe(wartung_aktiv()
           && (string)(wartung_daten()['von'] ?? '') === 'torwaechter',
           '29  ... und der Schalter steht auf „torwaechter"',
           'von ' . var_export(wartung_daten()['von'] ?? null, true));
    pruefe(str_contains($t1['rumpf'], 'selbst geschlossen')
           && str_contains($t1['rumpf'], 'BetreiberIn ist informiert'),   // Hausform seit PK-04/5b (F-RP-03)
           '30  Die Wartungsseite nennt den Grund');

    /* Die Geraete bekommen ihr 503 als JSON — dieselbe Zusage wie in Teil 1,
     * nur ausgeloest vom Torwaechter statt von einem Menschen. */
    $t2 = hole('ingest.php', null, ['leer' => 1],
               ['X-Device-Id: torprobe', 'X-Api-Key: torprobe']);
    pruefe($t2['code'] === 503 && ($t2['daten']['error'] ?? '') === 'maintenance',
           '30  ... und ingest.php bekommt sein 503 als JSON',
           'HTTP ' . $t2['code'] . ', error '
           . var_export($t2['daten']['error'] ?? null, true));

    $t3 = hole('betrieb_updates.php', $sidAdmin);
    pruefe($t3['code'] === 200 && str_contains($t3['rumpf'], 'Vom Torwächter geschlossen'),
           '31  Betrieb → Updates bleibt offen und nennt den Torwaechter',
           'HTTP ' . $t3['code']);

    $t4 = hole('betrieb_updates.php', $sidAdmin,
               ['action' => 'migrate', 'csrf' => $csrfAdmin]);
    $st7 = $pdo->prepare('SELECT status FROM schema_migrations WHERE id = ?');
    $st7->execute([$kandidat]);
    if ($st7->fetchColumn() !== false) { $torWeg = null; }
    pruefe(str_contains($t4['rumpf'], 'Wartung beenden')
           && str_contains($t4['rumpf'], 'Migrationen erledigt'),
           '31  ... und bietet nach dem Lauf „Wartung beenden" an',
           'HTTP ' . $t4['code']);

    $t5 = hole('betrieb_updates.php', $sidAdmin,
               ['action' => 'wartung_aus', 'csrf' => $csrfAdmin]);
    $t6 = hole('index.php', $sidAdmin);
    pruefe(!wartung_aktiv() && $t6['code'] === 200,
           '31  ... und danach ist die Installation wieder offen',
           'HTTP ' . $t6['code']);

    /* ---- Nr. 54: der Zwischenspeicher ueberlebt eine Wiederherstellung --- */
    $pdo->prepare('DELETE FROM schema_migrations WHERE id = ?')->execute([$kandidat]);
    $torWeg = $kandidat;
    /* KEIN Zuruecksetzen — genau so kaeme die Lage nach einem eingespielten
     * Dump: fremdes Register, eigener Katalog, gleicher Hash. */
    pruefe(migrationen_ausstehend($pdo) === false,
           '32  Nach einer Wiederherstellung: der Zwischenspeicher luegt (erwartet)',
           'ausstehend = false, obwohl eine Zeile fehlt');
    migrationen_tor_zuruecksetzen($pdo);
    pruefe(migrationen_ausstehend($pdo) === true,
           '33  ... und `migrationen_tor_zuruecksetzen()` macht ihn wieder sehend',
           'ausstehend = true');

    /* Aufraeumen: Zeile zurueck, Schalter aus, Zwischenspeicher frisch. */
    $pdo->prepare('INSERT INTO schema_migrations (id, status, applied_at)
                   VALUES (?, ?, ?)
                   ON DUPLICATE KEY UPDATE status = VALUES(status),
                                           applied_at = VALUES(applied_at)')
        ->execute([$kandidat, $migAlt['status'], $migAlt['applied_at']]);
    $torWeg = null;
    migrationen_tor_zuruecksetzen($pdo);
    wartung_weg();
} else {
    foreach ([['28', 'Vorbedingung: Wartung aus, Zwischenspeicher leer'],
              ['29', 'Angemeldete Seite antwortet 503'],
              ['29', '... und der Schalter steht auf „torwaechter"'],
              ['30', 'Die Wartungsseite nennt den Grund'],
              ['30', '... und ingest.php bekommt sein 503 als JSON'],
              ['31', 'Betrieb → Updates bleibt offen und nennt den Torwaechter'],
              ['31', '... und bietet nach dem Lauf „Wartung beenden" an'],
              ['31', '... und danach ist die Installation wieder offen'],
              ['32', 'Nach einer Wiederherstellung: der Zwischenspeicher luegt (erwartet)'],
              ['33', '... und `migrationen_tor_zuruecksetzen()` macht ihn wieder sehend']]
             as [$nr, $was]) {
        pruefe(false, $nr . '  ' . $was, $nichtGemessen);
    }
}

} finally {
    if ($torWeg !== null && ($migAlt ?? null) !== null) {
        $pdo->prepare('INSERT INTO schema_migrations (id, status, applied_at)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE status = VALUES(status),
                                               applied_at = VALUES(applied_at)')
            ->execute([$torWeg, $migAlt['status'], $migAlt['applied_at']]);
    }
    /* Der Zwischenspeicher des Torwaechters wird IMMER verworfen — auch bei
     * einem Abbruch mitten in Teil 7. Ein stehengebliebener „steht aus"
     * schloesse die Installation bei der naechsten Anfrage. */
    if (function_exists('migrationen_tor_zuruecksetzen')) {
        migrationen_tor_zuruecksetzen($pdo);
    }
    if ($warVorher) { wartung_setzen($inhaltVorher ?? ''); } else { wartung_weg(); }
    sitzung_weg($sidAdmin);
    sitzung_weg($sidUser);
    sitzung_weg($sidHalb);
    if ($geraet !== null) {
        $pdo->prepare('DELETE FROM devices WHERE device_id = ?')->execute([$geraet]);
    }
    /* Die Registerzeile zurueck, MIT ihrem alten Zeitpunkt. Ein blosses
     * Neuanlegen setzte CURRENT_TIMESTAMP — und damit still um, was
     * `migrationen_stand()` auf zwei Karten als „zuletzt … am …" anzeigt und
     * was der Bilderlauf fotografiert. */
    if ($registerWeg !== null && $migAlt !== null) {
        $pdo->prepare('INSERT INTO schema_migrations (id, status, applied_at)
                       VALUES (?, ?, ?)
                       ON DUPLICATE KEY UPDATE status = VALUES(status),
                                               applied_at = VALUES(applied_at)')
            ->execute([$registerWeg, $migAlt['status'], $migAlt['applied_at']]);
    } elseif ($migAlt !== null && $kandidat !== null) {
        // Der Klick hat sie neu angelegt — alten Zeitpunkt zuruecklegen.
        $pdo->prepare('UPDATE schema_migrations SET status = ?, applied_at = ? WHERE id = ?')
            ->execute([$migAlt['status'], $migAlt['applied_at'], $kandidat]);
    }
    $pdo->exec("DELETE FROM app_state WHERE k IN ('menue_zaehler_betrieb', 'status_ampel')");
    foreach ([$uidAdmin, $uidUser] as $u) {
        if ($u > 0) { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$u]); }
    }
    echo "\n  Schalter, Sitzungen, Geraet, Konten und Migrationsregister der Probe"
       . " wieder hergestellt"
       . ($warVorher ? " (die vorgefundene wartung.lock steht wieder)" : "") . ".\n";
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
