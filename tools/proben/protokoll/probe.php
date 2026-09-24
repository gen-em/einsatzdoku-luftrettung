<?php
declare(strict_types=1);

/**
 * Protokollprobe — hält das Archiv, was E-P5c-39 zusagt? (P5c/AP2)
 *
 * Anlass: F-P5c-18 und F-P5c-19. `sicherheit_ereignisse` führt IP- und
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

echo "\n-> $n Erwartungen, $offen nicht erfüllt\n";
exit($offen === 0 ? 0 : 1);
