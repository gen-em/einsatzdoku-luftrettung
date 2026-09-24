<?php
declare(strict_types=1);

/**
 * Protokollprobe — hält das Archiv, was E-P5c-39 zusagt? (P5c/AP2) Und das
 * Fehlerprotokoll, was E-P5c-12 und -58 zusagen? (P5c/AP3)
 *
 * Anlass: F-P5c-18 und F-P5c-19; für Teil 7 F-P5c-21 bis -23 und Backlog
 * Nr. 248. `sicherheit_ereignisse` führt IP- und
 * E-Mail-Adressen und verfällt bewusst nach 30 Tagen (E-P5a-09); das Archiv
 * liegt 365 Tage und geht außer Haus. Stünden die Adressen darin, wäre die
 * 30-Tage-Zusage mit dem ersten Archiv gebrochen — und niemand sähe es, denn
 * das Archiv ist versiegelt.
 *
 * WAS SIE MISST.
 *   1. Der Job schreibt fällige Zeiträume in HÄPPCHEN: Mit einem Budget für
 *      einen Schritt je Aufruf braucht er mehrere Aufrufe, und der Zustand
 *      trägt ihn von einem zum nächsten.
 *   2. Im ENTSIEGELTEN Archiv: 0 Treffer für `"ip:`, für ein IPv4- und ein
 *      IPv6-Muster und für eine Adresse in den Reitern Sicherheit und E-Mail
 *      — gegen eine eigens angelegte Sperre MIT IP und Adresse und eine Mail
 *      MIT Empfänger und Fehlertext. Verwaltung darf Adressen tragen
 *      (E-P5c-75).
 *   3. Ein Archiv mit fremder Kennung im Namen wird erkannt und nicht
 *      geöffnet; ein umbenanntes lässt sich nicht öffnen (der Name steht im
 *      Siegel).
 *   4. Ein Archiv, dessen Zeitraum 366 Tage zurückliegt, löscht der Job; ein
 *      junges bleibt.
 *   5. Der Download über die Seite liefert ein gewöhnliches ZIP und schreibt
 *      genau einen Eintrag `archiv_heruntergeladen`.
 *   6. Der Versand erkennt den Namen als eigene Sicherung.
 *   7. Das Fehlerprotokoll (AP3), über einen eigenen `php -S` mit
 *      `fehlerrouter.php`: Eine ungefangene Ausnahme steht mit Kennung im
 *      Reiter System, und die Fehlerseite nennt dieselbe; als JSON unter
 *      `/api/`; ohne Wert, Adresse, Anfrage-, Kopf- und Sitzungsmarke; `@`
 *      schreibt nichts; eine Warnung schreibt einmal je Zeile und höchstens
 *      20 je Anfrage; ein Speicherende wird ein Abbruch mit Seite; die
 *      Kennungssuche findet den Eintrag, auch klein geschrieben; auf der
 *      Kommandozeile Rückgabewert 255 mit Kennung; ohne Datenbank der
 *      Rückfall, ohne Schleife; mit Ausgabepuffer verwirft die Fehlerseite
 *      eine halb geschriebene Seite.
 *
 * WAS SIE NICHT MISST: den Versand selbst auf ein Ziel (`versandprobe`), die
 * Seite als Bild (Bilderlauf), und ob der Job auf Produktiv ohne Cron in
 * einer Woche drankommt (Prüfdokument).
 *
 * SIE STELLT DEN ZUSTAND WIEDER HER: Einstellungen und Marke in `app_state`,
 * die angelegten Zeilen, die erzeugten Archive — im Schluss, auch nach einem
 * Abbruch. Archive, die VOR dem Lauf da waren, rührt sie nicht an.
 *
 * Aufruf:  php tools/proben/protokoll/probe.php [basisadresse]
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/sitzung_lib.php';
require_once $srv . '/protokoll_archiv_lib.php';
require_once $srv . '/sicherungsziel_lib.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo = db();

$n = 0; $offen = 0;
function kopf(string $t): void { echo "\n$t\n"; }
function pruef(bool $ok, string $was, string $dazu = ''): void
{
    global $n, $offen;
    $n++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-66s %s\n", $ok ? 'ok ' : 'FEHL', $was, $dazu);
}

/* ---- Sitzung der BetreiberIn — VOR jeder Ausgabe (Muster Wartungsprobe) --- */
$mail = 'protokollprobe-betreiberin@probe.invalid';
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Protokollprobe', 'betreiberin', '', '', 320000)")->execute([$mail]);
$uid = (int)$pdo->lastInsertId();
$epoch = (int)$pdo->query('SELECT session_epoch FROM users WHERE id = ' . $uid)->fetchColumn();
$sid = 'protokollprobe' . bin2hex(random_bytes(10));
$csrf = bin2hex(random_bytes(16));
$ort = is_dir(sitzung_ablage_pfad()) ? sitzung_ablage_pfad() : (string)(session_save_path() ?: sys_get_temp_dir());
session_save_path($ort);
session_id($sid);
session_start();
$_SESSION = ['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(), 'csrf' => $csrf];
session_write_close();

/* ---- Ausgangszustand merken, Gegenstände anlegen ------------------------- */
$schluessel = [PROTOKOLL_K_ARCHIV_TAGE, PROTOKOLL_K_ARCHIV_BEHALTEN,
               PROTOKOLL_K_ARCHIV_VERSAND, PROTOKOLL_K_ARCHIV_BIS];
$vorher = [];
foreach ($schluessel as $k) { $vorher[$k] = app_state_lesen($k); }
$archiveVorher = array_column(protokoll_archive(), 'datei');

$ip = '198.51.100.' . random_int(10, 250);
$adresse = 'protokollprobe-' . bin2hex(random_bytes(3)) . '@probe.invalid';
$zweiTage = gmdate('Y-m-d H:i:s', time() - 2 * 86400);
$pdo->prepare("INSERT INTO sicherheit_ereignisse (art, topf, merkmal, stufe, versuche, zeitpunkt, wer)
               VALUES ('sperre', 'login', ?, 2, 12, ?, ?)")->execute([$ip, $zweiTage, $adresse]);
$sichId = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO mail_warteschlange (schluessel, art, empfaenger, betreff, text, zustand,
                                               versuche, erstellt, beendet, fehler)
               VALUES ('passwort_neu', 'konto', ?, ?, ?, 'unzustellbar', 5, ?, ?, ?)")
    ->execute([$adresse, 'Neues Passwort für ' . $adresse, 'Link für ' . $adresse,
               $zweiTage, $zweiTage, '550 <' . $adresse . '>: user unknown from ' . $ip]);
$mailId = (int)$pdo->lastInsertId();

register_shutdown_function(static function () use ($pdo, $vorher, $archiveVorher, $sichId, $mailId, $uid, $ort, $sid): void {
    foreach ($vorher as $k => $v) {
        if ($v === null) { app_state_loeschen($k); } else { app_state_setzen($k, $v); }
    }
    foreach (protokoll_archive() as $a) {
        if (!in_array($a['datei'], $archiveVorher, true)) {
            @unlink(protokoll_archiv_wurzel() . '/' . $a['datei']);
        }
    }
    foreach (glob(protokoll_archiv_wurzel() . '/*.zip') ?: [] as $d) {
        if (!in_array(basename($d), $archiveVorher, true)) { @unlink($d); }
    }
    $pdo->prepare('DELETE FROM sicherheit_ereignisse WHERE id = ?')->execute([$sichId]);
    $pdo->prepare('DELETE FROM mail_warteschlange WHERE id = ?')->execute([$mailId]);
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE urheber_user_id = ?')->execute([$uid]);
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    @unlink($ort . '/sess_' . $sid);
});

echo "Protokollprobe gegen $basis — Kennung des Serverschlüssels "
   . (serverschluessel_kennung() ?? '(keiner)') . "\n";

/* ---- 1. Der Job, in Häppchen --------------------------------------------- */
kopf('1 — Der Job schreibt fällige Zeiträume in Häppchen');
app_state_setzen(PROTOKOLL_K_ARCHIV_TAGE, '1');
app_state_loeschen(PROTOKOLL_K_ARCHIV_BEHALTEN);
/* Ortsmitternacht vor drei Tagen, als UTC — die Grenzen des Archivs sind
 * Kalendertage der Anlage, nicht UTC-Tage. */
$beginn = protokoll_archiv_tag_beginn(gmdate('Y-m-d H:i:s', time() - 3 * 86400));
app_state_setzen(PROTOKOLL_K_ARCHIV_BIS, $beginn);
$rueck = protokoll_archiv_rueckstand($pdo, []);
pruef($rueck === 3, 'Drei Zeiträume stehen aus (Tag −3, −2, −1)', 'Rückstand ' . var_export($rueck, true));

$zustand = []; $aufrufe = 0; $fertig = false;
while (!$fertig && $aufrufe < 200) {
    $aufrufe++;
    /* EIN Schritt je Aufruf: Die Schleife im Job fragt die Zeit vor jedem
     * Schritt; die erste Antwort erlaubt einen, die zweite keinen mehr. */
    $antworten = [1.0, 0.0];
    $r = protokoll_archiv_job($pdo, $zustand,
        static function () use (&$antworten): float { return array_shift($antworten) ?? 0.0; });
    $zustand = $r['zustand'];
    $fertig = $r['fertig'];
}
$neu = array_values(array_filter(protokoll_archive(true),
    static fn(array $a): bool => !in_array($a['datei'], $archiveVorher, true)));
pruef($aufrufe > 3, 'Der Job brauchte mehrere Aufrufe, der Zustand trug ihn', $aufrufe . ' Aufrufe');
pruef(count($neu) === 3, 'Drei Archive liegen da', count($neu) . ' neu');
$heute = protokoll_archiv_tag_beginn(gmdate('Y-m-d H:i:s'));
pruef(app_state_lesen(PROTOKOLL_K_ARCHIV_BIS) === $heute,
      'Die Marke steht auf heute, Mitternacht Ortszeit', (string)app_state_lesen(PROTOKOLL_K_ARCHIV_BIS) . ' UTC');
pruef(glob(protokoll_archiv_wurzel() . '/.bau/*') === [], 'Der Bauordner ist leer (kein Klartext liegt herum)');
$kennung = serverschluessel_kennung();
pruef($neu !== [] && array_reduce($neu, static fn($c, $a) => $c && $a['kennung'] === $kennung, true),
      'Jeder Name trägt die Kennung des Serverschlüssels', (string)$kennung);

/* ---- 2. Was im Archiv steht ------------------------------------------------ */
kopf('2 — Was im entsiegelten Archiv steht (E-P5c-39)');
$ipv4 = '/\b(?:\d{1,3}\.){3}\d{1,3}\b/';
/* Nicht jede Folge mit Doppelpunkten ist eine IPv6 — „22:15:40" ist eine
 * Uhrzeit. Verlangt werden sieben Doppelpunkte oder ein „::". */
$ipv6 = '/(?<![0-9a-z:])(?:(?:[0-9a-f]{1,4}:){7}[0-9a-f]{1,4}|(?:[0-9a-f]{1,4}:){1,6}:(?:[0-9a-f]{1,4})?)(?![0-9a-z:])/i';
$adressmuster = '/[a-z0-9._%+-]+@[a-z0-9.-]+\.[a-z]{2,}/i';
$gefunden = ['sicherheit' => false, 'email' => false];
$treffer = ['sicherheit' => [0, 0, 0, 0], 'email' => [0, 0, 0, 0]];
$verwaltungAdressen = 0;
foreach ($neu as $a) {
    $arbeit = sys_get_temp_dir() . '/protokollprobe-' . bin2hex(random_bytes(4));
    [$fehler, $teile] = protokoll_archiv_entsiegeln($a['datei'], $arbeit);
    if ($fehler !== null) { pruef(false, 'Entsiegeln ' . $a['datei'], $fehler); continue; }
    foreach ($teile as $name => $pfad) {
        $inhalt = (string)file_get_contents($pfad);
        $reiter = basename($name, '.jsonl');
        if (isset($treffer[$reiter])) {
            if (str_contains($inhalt, '"sperre"') || str_contains($inhalt, 'passwort_neu')) {
                $gefunden[$reiter] = true;
            }
            $treffer[$reiter][0] += substr_count($inhalt, '"ip:');
            $treffer[$reiter][1] += preg_match_all($ipv4, $inhalt);
            $treffer[$reiter][2] += preg_match_all($ipv6, $inhalt);
            $treffer[$reiter][3] += preg_match_all($adressmuster, $inhalt);
        }
        if ($reiter === 'verwaltung') { $verwaltungAdressen += preg_match_all($adressmuster, $inhalt); }
    }
    protokoll_archiv_arbeit_weg($arbeit);
}
pruef($gefunden['sicherheit'], 'Die angelegte Sperre steht im Reiter Sicherheit', 'sonst misst Teil 2 nichts');
pruef($gefunden['email'], 'Die angelegte Mail steht im Reiter E-Mail', 'sonst misst Teil 2 nichts');
foreach ($treffer as $reiter => [$ipk, $v4, $v6, $at]) {
    pruef($ipk + $v4 + $v6 + $at === 0, "Reiter $reiter: keine IP, keine Adresse",
          "\"ip: $ipk · IPv4 $v4 · IPv6 $v6 · Adressen $at");
}
echo "  (Verwaltung trägt $verwaltungAdressen Adressen — erlaubt, E-P5c-75)\n";

/* ---- 3. Fremde Kennung, falscher Name -------------------------------------- */
kopf('3 — Fremde Kennung und umbenanntes Archiv');
/* Das Archiv MIT den angelegten Zeilen — das von vor zwei Tagen. */
$erstes = '';
foreach ($neu as $a) {
    if ((int)($a['manifest']['zeilen']['sicherheit'] ?? 0) > 0) { $erstes = $a['datei']; }
}
if ($erstes === '') { $erstes = $neu[0]['datei'] ?? ''; }
$w = protokoll_archiv_wurzel();
$fremd = preg_replace('/_[0-9a-f]{8}\.zip$/', '_00000000.zip', $erstes);
$umbenannt = '2001-01-01T00-00-00Z_' . $kennung . '.zip';
copy("$w/$erstes", "$w/$fremd");
copy("$w/$erstes", "$w/$umbenannt");
$liste = array_column(protokoll_archive(), 'passt', 'datei');
pruef(($liste[$fremd] ?? null) === false, 'Die Liste kennt das Archiv mit fremder Kennung als fremd', $fremd);
[$f1] = protokoll_archiv_entsiegeln($fremd, sys_get_temp_dir() . '/pp-f');
pruef($f1 !== null && str_contains($f1, 'anderen Serverschlüssel'), '…und öffnet es nicht', (string)$f1);
[$f2] = protokoll_archiv_entsiegeln($umbenannt, sys_get_temp_dir() . '/pp-u');
pruef($f2 !== null && str_contains($f2, 'umbenannt'), 'Ein umbenanntes Archiv lässt sich nicht öffnen', (string)$f2);
@unlink("$w/$fremd");

/* ---- 4. Aufbewahrung --------------------------------------------------------- */
kopf('4 — Aufbewahrung: 366 Tage alt geht, jung bleibt');
$alt = gmdate('Y-m-d\TH-i-s\Z', time() - 366 * 86400) . '_' . $kennung . '.zip';
copy("$w/$erstes", "$w/$alt");
$weg = protokoll_archiv_aufraeumen();
pruef(!is_file("$w/$alt"), 'Das 366 Tage alte Archiv ist gelöscht', $weg . ' gelöscht');
pruef(!is_file("$w/$umbenannt"), '…ebenso das aus dem Jahr 2001');
pruef(is_file("$w/$erstes"), 'Das junge Archiv bleibt', $erstes);

/* ---- 5. Download über die Seite -------------------------------------------- */
kopf('5 — Download über die Seite');
$zaehle = static function () use ($pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
                              WHERE art = 'archiv_heruntergeladen'")->fetchColumn();
};
$vorDl = $zaehle();
$ch = curl_init($basis . '/admin_protokoll.php?r=archiv');
curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_PROXY => '',
    CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=' . $sid], CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => http_build_query(['csrf' => $csrf, 'action' => 'archiv_laden',
                                            'datei' => $erstes])]);
$zip = (string)curl_exec($ch);
$typ = (string)curl_getinfo($ch, CURLINFO_CONTENT_TYPE);
$code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);
$tmp = sys_get_temp_dir() . '/protokollprobe-dl.zip';
file_put_contents($tmp, $zip);
$namen = zip_namen($tmp) ?? [];
@unlink($tmp);
pruef($code === 200 && str_contains($typ, 'zip'), 'Die Seite liefert ein ZIP', "HTTP $code, $typ, " . strlen($zip) . ' Byte');
pruef(in_array('manifest.json', $namen, true) && in_array('sicherheit.jsonl', $namen, true)
      && in_array('email.jsonl', $namen, true),
      '…mit Manifest und JSON-Zeilen im Klartext', implode(', ', $namen));
pruef($zaehle() === $vorDl + 1, 'Genau ein Eintrag „archiv_heruntergeladen"', $vorDl . ' → ' . $zaehle());

/* ---- 6. Versand ----------------------------------------------------------------- */
kopf('6 — Der Versand erkennt die dritte Dateiart');
pruef(sz_ist_sicherungsname(PROTOKOLL_ARCHIV_ORDNER, $erstes), 'Der Name gilt als eigene Sicherung', $erstes);
pruef(!sz_ist_sicherungsname(PROTOKOLL_ARCHIV_ORDNER, 'urlaub.zip'), 'Ein fremder Name nicht', 'urlaub.zip');

/* ---- 7. Das Fehlerprotokoll (P5c/AP3) ------------------------------------------ */
kopf('7 — Das Fehlerprotokoll');
$router = __DIR__ . '/fehlerrouter.php';
$sock = stream_socket_server('tcp://127.0.0.1:0');
$anschluss = (int)substr((string)stream_socket_get_name($sock, false), strrpos((string)stream_socket_get_name($sock, false), ':') + 1);
fclose($sock);
/* MIT AUSGABEPUFFER, wie ihn viele Hoster setzen (F-P5c-97) — `php -S`
 * puffert sonst nicht, und der Fall „halbe Seite" waere nicht zu sehen. */
$srvProz = proc_open(['php', '-d', 'output_buffering=4096', '-S', '127.0.0.1:' . $anschluss, '-t', $srv, $router],
                     [0 => ['file', '/dev/null', 'r'], 1 => ['file', '/dev/null', 'w'],
                      2 => ['file', '/dev/null', 'w']], $rohre);
$kennungenCli = [];
$kontaktVorher = app_state_lesen('instanz_kontakt');
register_shutdown_function(static function () use (&$srvProz, &$kennungenCli, $pdo, $kontaktVorher): void {
    if (is_resource($srvProz)) { proc_terminate($srvProz); proc_close($srvProz); }
    foreach ($kennungenCli as $k) {
        $pdo->prepare("DELETE FROM protokoll_ereignisse WHERE reiter = 'system'
                        AND JSON_UNQUOTE(JSON_EXTRACT(daten, '$.kennung')) = ?")->execute([$k]);
    }
    if ($kontaktVorher === null) { app_state_loeschen('instanz_kontakt'); }
    else { app_state_setzen('instanz_kontakt', $kontaktVorher); }
});
$fr = 'http://127.0.0.1:' . $anschluss;
for ($i = 0; $i < 50 && @fsockopen('127.0.0.1', $anschluss) === false; $i++) { usleep(100000); }

$abruf = static function (string $adresse) use ($sid): array {
    $ch = curl_init($adresse);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_PROXY => '',
        CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=' . $sid, 'X-Probe: KOPFMARKE',
                               'X-Forwarded-For: 203.0.113.77']]);
    $rumpf = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $ziel = (string)curl_getinfo($ch, CURLINFO_REDIRECT_URL);
    curl_close($ch);
    return [$code, $rumpf, $ziel];
};
$systemZahl = static function () use ($pdo): int {
    return (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE reiter = 'system'")->fetchColumn();
};
$eintrag = static function (string $k) use ($pdo): ?array {
    $st = $pdo->prepare("SELECT * FROM protokoll_ereignisse WHERE reiter = 'system'
                          AND JSON_UNQUOTE(JSON_EXTRACT(daten, '$.kennung')) = ?");
    $st->execute([$k]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    return $z === false ? null : $z;
};

app_state_loeschen('instanz_kontakt');
[$code, $seite] = $abruf($fr . '/probe-ausnahme?x=ANFRAGEMARKE');
preg_match('~Kennung des Fehlers ist <strong>([0-9A-F]{8})</strong>~', $seite, $m);
$k1 = $m[1] ?? '';
$z1 = $k1 !== '' ? $eintrag($k1) : null;
pruef($code === 500 && $k1 !== '', 'Ungefangene Ausnahme: 500 und eine Kennung auf der Seite', "HTTP $code, Kennung $k1");
pruef($z1 !== null && $z1['art'] === 'ausnahme' && (int)$z1['urheber_user_id'] === $uid,
      '…dieselbe Kennung im Reiter System, Art „ausnahme", Urheber aus der Sitzung',
      $z1 === null ? 'kein Eintrag' : $z1['art'] . ', Urheber ' . $z1['urheber_user_id']);
$roh = $z1 === null ? '' : $z1['text'] . ' ' . $z1['daten'];
$marken = ['protokollprobe-geheim', '@probe.invalid', '198.51.100.9', 'ANFRAGEMARKE',
           'KOPFMARKE', '203.0.113.77', '127.0.0.1', 'SITZUNGSMARKE', $sid];
$gefunden = array_values(array_filter($marken, static fn($mk) => str_contains($roh, $mk)));
pruef($z1 !== null && $gefunden === [] && str_contains($z1['text'], "Wert '…'"),
      '…ohne Wert, Adresse, Anfrage, Kopfzeilen, IP und Sitzung — der Wert ersetzt',
      $gefunden === [] ? count($marken) . ' Marken, 0 gefunden' : 'gefunden: ' . implode(', ', $gefunden));
pruef(str_contains($seite, 'Nenne diese Kennung'), 'Ohne Kontaktadresse: „Nenne diese Kennung"');

app_state_setzen('instanz_kontakt', 'betrieb-protokollprobe@probe.invalid');
[$code, $seite] = $abruf($fr . '/probe-ausnahme');
preg_match('~Kennung des Fehlers ist <strong>([0-9A-F]{8})</strong>~', $seite, $m);
pruef($code === 500 && str_contains($seite, 'Melde diese Kennung an <a href="mailto:betrieb-protokollprobe@probe.invalid">'),
      'Mit Kontaktadresse: „Melde diese Kennung an …" mit Verweis', 'Kennung ' . ($m[1] ?? '—'));

[$code, $rumpf] = $abruf($fr . '/api/probe-ausnahme');
$j = json_decode($rumpf, true);
$kj = is_array($j) ? (string)($j['kennung'] ?? '') : '';
pruef($code === 500 && is_array($j) && ($j['error'] ?? '') === 'server'
      && preg_match('/^[0-9A-F]{8}$/', $kj) === 1 && $eintrag($kj) !== null
      && str_contains((string)($j['meldung'] ?? ''), 'betrieb-protokollprobe@probe.invalid'),
      'Unter /api/: JSON mit error, kennung, meldung — und dem Meldeweg', "HTTP $code, " . substr($rumpf, 0, 60));
app_state_loeschen('instanz_kontakt');

[$code, $seite] = $abruf($fr . '/probe-halbseite');
pruef($code === 500 && str_starts_with(ltrim($seite), '<!doctype html>') && !str_contains($seite, 'HALBE SEITE')
      && str_contains($seite, 'Kennung des Fehlers ist'),
      'Mit Ausgabepuffer: die halbe Seite ist verworfen, nur die Fehlerseite steht da',
      "HTTP $code, " . (str_contains($seite, 'HALBE SEITE') ? 'halbe Seite davor' : 'sauber'));

$vor = $systemZahl();
[$code, $rumpf] = $abruf($fr . '/probe-at');
pruef($code === 200 && $rumpf === 'ok' && $systemZahl() === $vor,
      'Ein mit @ unterdrückter Fehler schreibt nichts', "HTTP $code, " . ($systemZahl() - $vor) . ' Einträge');

$vor = $systemZahl();
[$code, $rumpf] = $abruf($fr . '/probe-warnung');
$neu = $systemZahl() - $vor;
pruef($code === 200 && str_ends_with($rumpf, 'weiter') && $neu === SYSTEM_PHP_JE_ANFRAGE,
      'Warnungen: die Seite läuft weiter; einmal je Zeile, höchstens ' . SYSTEM_PHP_JE_ANFRAGE . ' je Anfrage',
      "HTTP $code, $neu Einträge aus 5 + 25 Warnungen");

[$code, $seite] = $abruf($fr . '/probe-abbruch');
preg_match('~Kennung des Fehlers ist <strong>([0-9A-F]{8})</strong>~', $seite, $m);
$za = isset($m[1]) ? $eintrag($m[1]) : null;
pruef($code === 500 && $za !== null && $za['art'] === 'abbruch',
      'Speicherende: 500, Fehlerseite, Eintrag „abbruch" mit derselben Kennung',
      "HTTP $code, " . ($za['art'] ?? 'kein Eintrag'));

[$code, $seite] = $abruf($basis . '/admin_protokoll.php?r=system&q=' . strtolower($k1));
pruef($code === 200 && $k1 !== '' && str_contains($seite, $k1) && str_contains($seite, 'unbehandelt: RuntimeException'),
      'Die Kennungssuche findet den Eintrag — auch klein geschrieben', "HTTP $code, q=" . strtolower($k1));
[$code, , $ziel] = $abruf($basis . '/admin_protokoll.php?r=verwaltung&q=' . $k1);
pruef($code === 303 && str_contains($ziel, 'r=system'), '…und leitet aus einem anderen Reiter nach System', "HTTP $code");

$lauf = static function (array $arg, ?string $log = null) use ($router): array {
    $befehl = array_merge(['php'], $log !== null ? ['-d', 'error_log=' . $log] : [], [$router], $arg);
    $p = proc_open($befehl, [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $r);
    $aus = stream_get_contents($r[1]); $err = stream_get_contents($r[2]);
    return [proc_close($p), (string)$aus, (string)$err];
};
[$rc, , $err] = $lauf(['--ausnahme']);
preg_match('/Kennung ([0-9A-F]{8})/', $err, $m);
$kc = $m[1] ?? '';
if ($kc !== '') { $kennungenCli[] = $kc; }
$zc = $kc !== '' ? $eintrag($kc) : null;
pruef($rc === 255 && str_contains($err, 'Uncaught RuntimeException') && $zc !== null && $zc['urheber_art'] === 'cli',
      'Kommandozeile: Rückgabewert 255, Text und Kennung auf stderr, Eintrag „cli"', "rc $rc, Kennung $kc");

$log = sys_get_temp_dir() . '/protokollprobe-ohne-db-' . getmypid() . '.log';
@unlink($log);
$t0 = microtime(true);
[$rc, $aus] = $lauf(['--ohne-db'], $log);
$dauer = microtime(true) - $t0;
$e = json_decode(trim($aus), true);
$kd = is_array($e) ? (string)($e['kennung'] ?? '') : '';
$zeilen = is_file($log) ? file($log, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [];
$logText = implode("\n", $zeilen);
@unlink($log);
pruef($rc === 0 && $kd !== '' && str_contains($logText, '[' . $kd . '] protokollprobe: Datenbank weg'),
      'Datenbank weg: der Satz steht mit seiner Kennung im Rückfall', "rc $rc, Kennung $kd");
pruef(count($zeilen) <= 6 && $dauer < 10 && !str_contains($logText, '@probe.invalid') && $eintrag($kd) === null,
      '…ohne Schleife: wenige Zeilen, kurze Dauer, bereinigt, kein Eintrag',
      count($zeilen) . ' Zeilen, ' . round($dauer, 1) . ' s');

echo "\n-> $n Erwartungen, $offen nicht erfüllt\n";
exit($offen === 0 ? 0 : 1);
