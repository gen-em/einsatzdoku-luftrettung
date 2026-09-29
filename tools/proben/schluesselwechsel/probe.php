<?php
declare(strict_types=1);

/**
 * Schlüsselwechselprobe — wechselt der Serverschlüssel als Vorgang, und geht
 * dabei nichts verloren? (Schritt 18, SR-03)
 *
 * Anlass: Nr. 247 — es gab keinen Wechsel; wer `server_key` von Hand änderte, machte alles Versiegelte stumm
 *
 * WAS SIE MISST, gegen die örtliche Anlage, mit eigenen Stücken:
 *   1. Das Inventar kommt aus den Aufrufern: jeder `sk_versiegeln(`-Aufruf in
 *      `server/` gehört zu einem Zweck, den der Job kennt (F-SR-01). Ein neuer
 *      Zweck ohne Eintrag ist rot.
 *   2. Je Zweck ein Stück unter dem Schlüssel A — ein Ziel mit Passwort, ein
 *      Konto mit Zweitfaktor, eine Begleitdatei und ein Konto-Backup
 *      (Fassung 3), dazu eines der Fassung 2 ohne Siegel, ein Archiv des
 *      Protokolls, ein Komplett-Stand — und ein Stück, das sich mit KEINEM
 *      Schlüssel öffnen lässt (E-SR-61).
 *   3. Die Riegel vor dem Wechsel: ohne Haken nichts (E-SR-63), nicht neben
 *      einer Anteil-Rotation (E-SR-60).
 *   4. Der Wechsel A → B: Lage `rotation`, Marke auf B, Blatt-Marke gelöscht
 *      (Nr. 233, E-SR-11), Protokoll, Mail an jede BetreiberIn (E-SR-62); die
 *      Anteil-Rotation ist jetzt gesperrt; das Entfernen verweigert mit drei
 *      Gründen; alles Alte öffnet noch (über den bisherigen); Blatt und Karte
 *      über HTTP.
 *   5. Die Häppchen: ein Wiederanlauf räumt eine halbe Nebendatei weg; mit
 *      wenig Zeit nur die Zeilen, nicht die Dateien; dann bis `fertig`. JEDES
 *      Stück öffnet danach mit B und liefert denselben Klartext; das Archiv
 *      hat den Namen gewechselt; das Stück ohne Siegel ist byte-gleich; das
 *      unlesbare ist gezählt und genannt.
 *   6. Der Abschluss: ohne frischen Stand und ohne Rückfrage verweigert; mit
 *      beiden entfernt; danach öffnet alles mit B, der alte Stand nur noch
 *      mit dem Wert A („vom Blatt").
 *   7. Nr. 344, über HTTP: „Freigabe widerrufen" mit einem Handgriff aus
 *      Nullen meldet einen Fehler und legt KEINE `konto.json` in die Wurzel
 *      der Ablage. (Das Konzept nannte die Freigabeprobe; die arbeitet als
 *      NutzerIn im Browser und hat kein Adminkonto — die Sitzung einer
 *      BetreiberIn hat diese Probe ohnehin, E-SR-66.)
 *   8. DER RÜCKWEG B → A — mit demselben Job. Er ist kein Aufräumen, sondern
 *      der zweite Wechsel: Der erste hat auch die Geheimnisse der Anlage selbst
 *      umgehüllt (die Zweitfaktoren der Prüfkonten), und ohne ihn wären sie
 *      nach dem Zurücklegen von `config.php` unlesbar. Gezählt wird, dass
 *      danach jedes Stück der Anlage mit A öffnet.
 *
 * DIE JOBS STEHEN WÄHREND DER PROBE STILL (`jobs_pause()`). Sonst trüge
 * jeder der zwei Seitenabrufe über den Huckepack-Weg ein Häppchen mit, und
 * der Fall „mit 1 s nur die Zeilen" wäre ein Wettlauf. `sw_jetzt()` fragt
 * die Pause nicht — es ist der Knopf, und die Probe braucht ihn.
 *
 * DAS AUSGANGSBILD: Vor dem ersten eigenen Stück liest sie für JEDES Stück
 * der Anlage, was der Nachweis dazu sagt (`neu`, `verloren`, …). Nach dem
 * Rückweg muss jedes wieder dasselbe sagen — eine Anlage mit einem Stück
 * unter einem fremden Schlüssel ist dann nicht rot, sondern gleich.
 *
 * SIE FASST DIE ANLAGE AN: `config.php` (am Ende byte-gleich zurück),
 * `app_state` (fünf Marken, zurück), `jobs` (Zeile des Jobs, zurück),
 * Protokoll, Warteschlange und Laufverlauf (die eigenen Zeilen gelöscht,
 * auch die „smtp:"-Zeilen der sofort versuchten Mails), und — über den
 * Wechsel — jedes versiegelte Stück der Anlage, zweimal umgehüllt. Bricht sie
 * ab, bevor der Rückweg lief, liegt der Rückweg in einer Datei im
 * Temp-Verzeichnis (`schluesselwechselprobe.json`, 0600, mit BEIDEN Werten)
 * und läuft mit `--zurueck`. **Nicht gegen eine Anlage mit Betrieb.**
 *
 * Aufruf:  php tools/proben/schluesselwechsel/probe.php [basis]
 *          php tools/proben/schluesselwechsel/probe.php --zurueck
 *
 * DREI HANDGRIFFE FÜR DEN BEDIENWEG `betrieb-server-schluesselwechsel`, der
 * den Wechsel im Browser fährt (P-SR-05) und dieselbe Buchführung braucht:
 *          --merken   den Stand sichern und die Jobs anhalten, sonst nichts
 *          --stand    einen kleinen Komplett-Stand unter dem heutigen
 *                     Schlüssel bauen (die zweite Bedingung, E-SR-09)
 *          --zurueck  der Rückweg wie oben — mit dem Schlüssel, der gerade
 *                     in config.php steht, als B
 * Rückgabewert: 0 = alles erfüllt, 1 = mindestens eine Erwartung nicht,
 *               2 = nicht gelaufen (Vorbedingung).
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/sitzung_lib.php';
require_once $srv . '/serverkrypto_lib.php';
require_once $srv . '/schluesselwechsel_lib.php';
require_once $srv . '/jobs_lib.php';
require_once $srv . '/komplett_lib.php';
require_once $srv . '/sicherungsziel_lib.php';
require_once $srv . '/totp_lib.php';
require_once $srv . '/einstieg_lib.php';
require_once $srv . '/mail_lib.php';
require_once $wurzel . '/tools/sandbox/konfig_stellen.php';

$basis = 'http://127.0.0.1:8080';
$modus = '';
foreach (array_slice($argv, 1) as $a) {
    if (in_array($a, ['--zurueck', '--merken', '--stand'], true)) { $modus = $a; }
    else { $basis = rtrim($a, '/'); }
}
$pdo = db();
$gut = 0; $schlecht = 0;
const SWP_DATEI = 'schluesselwechselprobe.json';
const SWP_MARKEN = ['server_key_kennung', 'kdf_anteil_kennung', 'schluesselblatt_bestaetigt_am',
                    'schluesselblatt_neu_weil', JOB_PAUSE_SCHLUESSEL];

function pruefe(bool $ok, string $was, string $dazu = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was" . ($dazu !== '' ? "  [$dazu]" : '') . "\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($dazu !== '' ? " — $dazu" : '') . "\n";
}
function teil(string $t): void { echo "\n== $t\n"; }

/** Eine Chiffre im Format `edsk1:` mit einem FREMDEN Schlüssel — das Stück,
 *  das sich mit keinem der beiden öffnen lässt. */
function fremd_versiegeln(string $klar, string $zweck): string
{
    $k = random_bytes(32); $n = random_bytes(SK_NONCE_LEN); $t = '';
    $c = openssl_encrypt($klar, 'aes-256-gcm', $k, OPENSSL_RAW_DATA, $n, $t, 'edsk1|' . $zweck, SK_TAG_LEN);
    return SK_PRAEFIX . base64_encode($n . $t . $c);
}

/** Ein kleiner Komplett-Stand unter dem AKTUELLEN Schlüssel — gebaut mit den
 *  Funktionen der Anwendung (Kopf, Versiegelung), nur ohne Datenbankabzug. */
function stand_bauen(): string
{
    $quelle = tempnam(sys_get_temp_dir(), 'swp');
    file_put_contents($quelle, gzencode(str_repeat("-- Schluesselwechselprobe\n", 4000)));
    $kopf = komp_kopf_bauen(['tabellen' => 1, 'zeilen' => 1, 'roh' => (int)filesize($quelle)], null) . "\n";
    if (!is_dir(komp_wurzel())) { @mkdir(komp_wurzel(), 0770, true); }
    $name = komp_dateiname();
    $z = [];
    komp_siegel_schub($quelle, komp_wurzel() . '/' . $name, (string)serverschluessel(), $kopf, $z,
                      static fn(): float => 100.0, 0.0);
    @unlink($quelle);
    return $name;
}

/** Die Seite über HTTP mit einer gefälschten, gebundenen, frischen Sitzung. */
function hole(string $pfad, array $sitzung, ?array $post = null): array
{
    global $basis;
    $ch = curl_init("$basis/$pfad");
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '',
        CURLOPT_HTTPHEADER => ['Cookie: PHPSESSID=' . $sitzung['sid'] . '; '
            . SITZUNG_COOKIES['bindung']['name'] . '=' . $sitzung['bind']]]);
    if ($post !== null) {
        curl_setopt_array($ch, [CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($post + ['csrf' => $sitzung['csrf']])]);
    }
    $rumpf = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'rumpf' => html_entity_decode($rumpf, ENT_QUOTES, 'UTF-8')];
}
function sitzung_ort(): string
{
    $eigen = sitzung_ablage_pfad();
    return is_dir($eigen) ? $eigen : (string)(session_save_path() ?: sys_get_temp_dir());
}
/** Eine gebundene, frische Sitzung — als DATEI geschrieben, im Format des
 *  Standard-Serialisierers (`schluessel|serialize(wert)`). Die Rollenprobe
 *  nimmt `session_start()` und legt ihre Sitzungen deshalb vor jeder Ausgabe
 *  an; diese hier braucht die ihre erst mitten im Lauf, und nach der ersten
 *  Zeile Ausgabe verweigert PHP das Starten. */
function sitzung_anlegen(int $uid): array
{
    $sid = 'swprobe' . bin2hex(random_bytes(10));
    $bind = bin2hex(random_bytes(32));
    $epoch = (int)db()->query('SELECT session_epoch FROM users WHERE id = ' . $uid)->fetchColumn();
    $csrf = bin2hex(random_bytes(16));
    $daten = ['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(),
              'csrf' => $csrf, 'bindung' => hash('sha256', $bind),
              'zf_frisch_bis' => time() + 3600];
    $roh = '';
    foreach ($daten as $k => $v) { $roh .= $k . '|' . serialize($v); }
    file_put_contents(sitzung_ort() . '/sess_' . $sid, $roh);
    return ['sid' => $sid, 'bind' => $bind, 'csrf' => $csrf];
}

/** Was der Nachweis zu jedem Stück der Anlage sagt — unter dem Schlüssel, der
 *  gerade `server_key` ist, ohne bisherigen. */
function ausgangsbild(): array
{
    $k = (string)serverschluessel_kennung();
    $bild = [];
    foreach (SW_ZWECKE as $zw) {
        foreach (sw_stuecke($zw) as $st) {
            $bild[$zw . '|' . $st] = sw_stueck($zw, $st, ['kennung_neu' => $k, 'kennung_alt' => ''], true);
        }
    }
    return $bild;
}

/* ---- Der Rückweg: B → A, mit demselben Job, dann alles zurück ------------- */

/**
 * @param array $s der gesicherte Stand: `a_hex`, `b_hex` (oder null), `marken`,
 *                 `job`, `max_prot`, `max_mail`, `konfig_bytes`, `eigenes`
 */
function rueckweg(array $s): array
{
    $bericht = ['umgehuellt' => 0, 'laeufe' => 0, 'abweichend' => [], 'fehler' => null];
    $a = (string)$s['a_hex'];
    $b = $s['b_hex'] ?? null;
    /* Nach dem Bedienweg steht B nicht im Stand — der Browser hat gewechselt.
     * Dann ist B, was jetzt in config.php steht, wenn es nicht A ist. */
    $jetzt = strtolower((string)konfig('server_key', ''));
    if ($b === null && preg_match('/^[0-9a-f]{64}$/', $jetzt) === 1 && $jetzt !== strtolower($a)) {
        $b = $jetzt;
    }

    /* Erst die eigenen Stücke weg — sie brauchen keinen Rückweg, und jedes
     * weniger ist ein Stück weniger, das der zweite Wechsel anfasst. */
    $e = (array)($s['eigenes'] ?? []);
    foreach ((array)($e['konten'] ?? []) as $uid) {
        db()->prepare('DELETE FROM users WHERE id = ?')->execute([(int)$uid]);
    }
    foreach ((array)($e['ziele'] ?? []) as $id) {
        db()->prepare('DELETE FROM backup_targets WHERE id = ?')->execute([(int)$id]);
    }
    foreach ((array)($e['ordner'] ?? []) as $k) { edbak_ordner_loeschen((string)$k); }
    foreach ((array)($e['archive_von'] ?? []) as $von) {
        $stamm = gmdate('Y-m-d\TH-i-s\Z', (int)strtotime($von . ' UTC'));
        foreach (glob(protokoll_archiv_wurzel() . '/' . $stamm . '_*.zip') ?: [] as $f) { @unlink($f); }
    }
    foreach ((array)($e['staende'] ?? []) as $n) { @unlink(komp_wurzel() . '/' . $n); }
    foreach ((array)($e['sitzungen'] ?? []) as $sid) { @unlink(sitzung_ort() . '/sess_' . $sid); }

    if ($b !== null && strtolower((string)konfig('server_key', '')) !== strtolower($a)) {
        /* Die Lage „Wechsel von B nach A": A ist der neue, B der bisherige,
         * und die Marke nennt A — der Job hüllt alles zurück. */
        konfig_stellen_schreiben(konfig_stellen_pfad(),
            array_merge(konfig_stellen_lesen(konfig_stellen_pfad()),
                        ['server_key' => $a, 'server_key_alt' => $b]));
        config_gemerktes_verwerfen();
        app_state_setzen('server_key_kennung', (string)schluessel_kennung($a));
        serverschluessel_zustand(true);
        sw_zustand_setzen(sw_zustand_neu((string)schluessel_kennung($a), (string)schluessel_kennung($b)));
        for ($i = 0; $i < 60; $i++) {
            $l = sw_jetzt(30.0);
            $bericht['laeufe']++;
            if (($l['fehler'] ?? null) !== null) { $bericht['fehler'] = $l['fehler']; break; }
            if ((sw_zustand()['phase'] ?? '') === 'fertig') { break; }
            if (isset($l['uebersprungen'])) { sleep(2); }
        }
        $bericht['umgehuellt'] = (int)(sw_zustand()['umgehuellt'] ?? 0);
    }

    /* config.php byte-gleich, Marken, Jobzeile, eigene Zeilen in Protokoll,
     * Post und Laufverlauf. */
    konfig_stellen_bytes(konfig_stellen_pfad(), (string)base64_decode((string)$s['konfig_bytes']));
    config_gemerktes_verwerfen();
    foreach ((array)$s['marken'] as $k => $v) {
        if ($v === null) { app_state_loeschen($k); } else { app_state_setzen($k, (string)$v); }
    }
    foreach ((array)($s['jobzeilen'] ?? []) as $job => $zeile) {
        if (!is_array($zeile)) {
            db()->prepare('DELETE FROM jobs WHERE job = ?')->execute([$job]);
            continue;
        }
        db()->prepare('INSERT IGNORE INTO jobs (job) VALUES (?)')->execute([$job]);
        db()->prepare('UPDATE jobs SET zustand = ?, rueckstand = ?, letzter_lauf = ?, letzter_erfolg = ?,
                              letzter_ausloeser = ?, letzter_fehler = ?, erledigt_zuletzt = ?,
                              laeuft_seit = ? WHERE job = ?')
            ->execute([$zeile['zustand'], $zeile['rueckstand'], $zeile['letzter_lauf'],
                       $zeile['letzter_erfolg'], $zeile['letzter_ausloeser'], $zeile['letzter_fehler'],
                       $zeile['erledigt_zuletzt'], $zeile['laeuft_seit'], $job]);
    }
    db()->prepare("DELETE FROM protokoll_ereignisse WHERE id > ? AND art LIKE 'serverschluessel_%'")
        ->execute([(int)$s['max_prot']]);
    db()->prepare("DELETE FROM mail_warteschlange WHERE id > ? AND schluessel = 'serverschluessel_gewechselt'")
        ->execute([(int)$s['max_mail']]);
    db()->prepare('DELETE FROM job_laeufe WHERE id > ? AND job = ?')
        ->execute([(int)$s['max_laeufe'], SW_JOB]);
    /* Die Mails gehen sofort hinaus (`mail_einreihen(…, sofort: true)`), und
     * die örtliche Anlage hat einen SMTP-Eintrag ohne Gegenstelle: Jede
     * schreibt eine Zeile „smtp: …" in den Reiter System. Sie gehören der
     * Probe — die Jobs stehen still, und Proben laufen nicht nebeneinander. */
    db()->prepare("DELETE FROM protokoll_ereignisse WHERE id > ? AND reiter = 'system'
                     AND art = 'stoerung' AND text LIKE 'smtp:%'")
        ->execute([(int)$s['max_prot']]);
    serverschluessel_zustand(true);

    /* Sagt der Nachweis zu jedem Stück der Anlage wieder dasselbe wie vorher? */
    $jetzt = ausgangsbild();
    foreach ((array)$s['bild'] as $k => $was) {
        if (($jetzt[$k] ?? 'weg') !== $was) { $bericht['abweichend'][] = $k . ': ' . $was . ' → ' . ($jetzt[$k] ?? 'weg'); }
    }
    @unlink(sys_get_temp_dir() . '/' . SWP_DATEI);
    return $bericht;
}

if ($modus === '--stand') {
    $pfad = sys_get_temp_dir() . '/' . SWP_DATEI;
    if (!is_file($pfad)) { fwrite(STDERR, "Kein gesicherter Stand — erst --merken.\n"); exit(2); }
    $s = (array)json_decode((string)file_get_contents($pfad), true);
    $name = stand_bauen();
    $s['eigenes']['staende'][] = $name;
    file_put_contents($pfad, json_encode($s));
    echo $name, "\n";
    exit(0);
}
if ($modus === '--zurueck') {
    $pfad = sys_get_temp_dir() . '/' . SWP_DATEI;
    if (!is_file($pfad)) { fwrite(STDERR, "Kein gesicherter Stand in $pfad — nichts zurückzulegen.\n"); exit(2); }
    $b = rueckweg((array)json_decode((string)file_get_contents($pfad), true));
    echo "Rückweg: {$b['laeufe']} Läufe, {$b['umgehuellt']} Stücke nach A, "
       . count($b['abweichend']) . " anders als vorher\n";
    foreach ($b['abweichend'] as $a) { echo "  $a\n"; }
    exit($b['abweichend'] === [] && $b['fehler'] === null ? 0 : 1);
}

/* ---- Vorbedingungen und Sicherung ----------------------------------------- */

$sk0 = serverschluessel_zustand(true);
if ($sk0['stand'] !== 'bereit') {
    fwrite(STDERR, "Nicht gelaufen: Der Serverschlüssel ist „{$sk0['stand']}\", nicht „bereit\".\n");
    exit(2);
}
if (anteil_zustand(true)['stand'] === 'rotation') {
    fwrite(STDERR, "Nicht gelaufen: Es läuft eine Rotation des Server-Anteils.\n");
    exit(2);
}
if (is_file(sys_get_temp_dir() . '/' . SWP_DATEI)) {
    fwrite(STDERR, "Nicht gelaufen: Ein früherer Lauf hat seinen Rückweg nicht beendet — erst --zurueck.\n");
    exit(2);
}
$pfadKonfig = konfig_stellen_pfad();
$stand = [
    'a_hex' => strtolower((string)konfig('server_key', '')), 'b_hex' => null,
    'konfig_bytes' => base64_encode((string)file_get_contents($pfadKonfig)),
    'marken' => array_combine(SWP_MARKEN, array_map('app_state_lesen', SWP_MARKEN)),
    /* Zwei Jobzeilen: die des Wechsels und die des Komplett-Backups — der
     * Wechsel merkt nach dem Nachweis einen Auftrag vor (Q-SR-03), und der
     * liefe nach der Probe sonst über die ganze örtliche Datenbank. */
    'jobzeilen' => (function () use ($pdo): array {
        $aus = [];
        foreach ([SW_JOB, KOMP_JOB] as $j) {
            $st = $pdo->prepare('SELECT * FROM jobs WHERE job = ?');
            $st->execute([$j]);
            $z = $st->fetch(PDO::FETCH_ASSOC);
            $aus[$j] = is_array($z) ? $z : null;
        }
        return $aus;
    })(),
    'max_laeufe' => (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM job_laeufe')->fetchColumn(),
    'bild' => ausgangsbild(),
    'max_prot' => (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM protokoll_ereignisse')->fetchColumn(),
    'max_mail' => (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn(),
    'eigenes' => ['konten' => [], 'ziele' => [], 'ordner' => [], 'archive_von' => [],
                  'staende' => [], 'sitzungen' => []],
];
$merken = static function () use (&$stand): void {
    $p = sys_get_temp_dir() . '/' . SWP_DATEI;
    file_put_contents($p, json_encode($stand));
    @chmod($p, 0600);
};
$merken();
jobs_pause(JOB_PAUSE_MAX_S);
$A = $stand['a_hex'];
$Akenn = (string)schluessel_kennung($A);
if ($modus === '--merken') {
    echo json_encode(['a' => $Akenn, 'stuecke' => count($stand['bild'])]), "\n";
    exit(0);
}
echo "Schlüsselwechselprobe gegen $basis — Schlüssel A $Akenn, "
   . count($stand['bild']) . " Stücke der Anlage im Ausgangsbild\n";

try {
    /* ---- 1. Inventar aus den Aufrufern -------------------------------------- */
    teil('1. Inventar aus den Aufrufern (F-SR-01)');
    $orte = [];
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($srv, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $f) {
        if (!str_ends_with((string)$f, '.php') || str_contains((string)$f, '/vendor/')) { continue; }
        $t = token_get_all((string)file_get_contents((string)$f));
        for ($i = 0, $n = count($t); $i < $n; $i++) {
            if (!is_array($t[$i]) || $t[$i][0] !== T_STRING || $t[$i][1] !== 'sk_versiegeln') { continue; }
            $j = $i + 1; while ($j < $n && is_array($t[$j]) && $t[$j][0] === T_WHITESPACE) { $j++; }
            $k = $i - 1; while ($k >= 0 && is_array($t[$k]) && $t[$k][0] === T_WHITESPACE) { $k--; }
            if (($t[$j] ?? null) !== '(' || (is_array($t[$k] ?? null) && $t[$k][0] === T_FUNCTION)) { continue; }
            $orte[] = basename((string)$f) . ':' . $t[$i][2];
        }
    }
    /* Welche Datei zu welchem Zweck des Jobs gehört. `schluesselwechsel_lib`
     * selbst versiegelt beim Umhüllen; `serverkrypto_lib` definiert. */
    $bekannt = ['sicherungsziel_lib.php' => 'ziele', 'totp_lib.php' => 'totp',
                'adminbackup_lib.php' => 'konten', 'protokoll_archiv_lib.php' => 'archive',
                'schluesselwechsel_lib.php' => '(umhüllen)'];
    $fremd = array_values(array_filter($orte, static fn(string $o): bool =>
        !isset($bekannt[strstr($o, ':', true)])));
    $zwecke = array_values(array_unique(array_filter(array_map(static fn(string $o): ?string =>
        $bekannt[strstr($o, ':', true)] ?? null, $orte), static fn($z) => $z !== '(umhüllen)')));
    sort($zwecke);
    pruefe($fremd === [] && $zwecke === ['archive', 'konten', 'totp', 'ziele'],
        'jeder Aufruf von sk_versiegeln( gehört zu einem Zweck des Jobs — ' . count($orte) . ' Aufrufe',
        implode(', ', $orte) . ($fremd !== [] ? ' · FREMD: ' . implode(', ', $fremd) : ''));
    $sollZwecke = SW_ZWECKE; sort($sollZwecke);
    pruefe($zwecke === $sollZwecke, 'die Zwecke der Aufrufer sind genau SW_ZWECKE', implode(', ', $zwecke));

    /* ---- 2. Stücke unter A --------------------------------------------------- */
    teil('2. Je Zweck ein Stück unter A');
    $konto = static function (string $mail, string $rolle) use ($pdo, &$stand, $merken): int {
        $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
        $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter, totp_seit)
                       VALUES (?, 'Schluesselwechselprobe', ?, 'x', '', 320000, UTC_TIMESTAMP())")
            ->execute([$mail, $rolle]);
        $id = (int)$pdo->lastInsertId();
        $stand['eigenes']['konten'][] = $id;
        $merken();
        return $id;
    };
    $uid = $konto('schluesselwechselprobe@probe.invalid', 'user');
    $pdo->prepare('UPDATE users SET totp_seit = NULL WHERE id = ?')->execute([$uid]);
    $tz = totp_einrichtung_beginnen($uid);
    $totpKlar = (string)($tz['geheimnis'] ?? '');
    $uidFremd = $konto('schluesselwechselprobe-fremd@probe.invalid', 'user');
    $pdo->prepare('UPDATE users SET totp_geheimnis = ? WHERE id = ?')
        ->execute([fremd_versiegeln('x', 'totp|' . $uidFremd), $uidFremd]);

    $pdo->prepare("DELETE FROM backup_targets WHERE name = 'Schluesselwechselprobe'")->execute();
    $pdo->prepare("INSERT INTO backup_targets (name, protokoll, host, port, nutzer, pfad, passiv, aktiv, erstellt_am)
                   VALUES ('Schluesselwechselprobe', 'sftp', 'probe.invalid', 22, 'probe', '/', 1, 0, UTC_TIMESTAMP())")
        ->execute();
    $zid = (int)$pdo->lastInsertId();
    $stand['eigenes']['ziele'][] = $zid; $merken();
    $zielKlar = 'Passwort-' . bin2hex(random_bytes(6));
    $pdo->prepare('UPDATE backup_targets SET geheim = ? WHERE id = ?')
        ->execute([sk_versiegeln($zielKlar, sz_zweck($zid, 'geheim')), $zid]);

    edbak_ablage_bereit();
    $kennung = bin2hex(random_bytes(8));
    $stand['eigenes']['ordner'][] = $kennung; $merken();
    @mkdir(edbak_ordner($kennung), 0770, true);
    $begleitKlar = ['email' => 'probe@probe.invalid', 'name' => 'Schluesselwechselprobe', 'sicherungen' => []];
    edbak_begleit_schreiben($kennung, $begleitKlar);
    $paket = edbak_paketname();
    $teilKlar = ['manifest.json' => (string)json_encode(['format' => 'einsatzdoku-adminsicherung',
                    'version' => 3, 'teile' => ['kern.json']]),
                 'kern.json' => str_repeat('{"probe":1}', 5000)];
    $bau = sys_get_temp_dir() . '/swp-' . bin2hex(random_bytes(4));
    @mkdir($bau);
    $teile = [];
    foreach ($teilKlar as $n => $k) {
        file_put_contents("$bau/$n", edbak_teil_siegeln($k, $kennung, $paket, $n));
        $teile[$n] = "$bau/$n";
    }
    zip_bauen(edbak_ordner($kennung) . '/' . $paket, $teile);
    clearstatcache();
    $paketGroesse = (int)filesize(edbak_ordner($kennung) . '/' . $paket);
    $paket2 = substr($paket, 0, 20) . '_' . bin2hex(random_bytes(4)) . '.zip';
    file_put_contents("$bau/m2", (string)json_encode(['format' => 'einsatzdoku-adminsicherung', 'version' => 2]));
    zip_bauen(edbak_ordner($kennung) . '/' . $paket2, ['manifest.json' => "$bau/m2"]);
    array_map('unlink', glob("$bau/*") ?: []); @rmdir($bau);
    $paket2Summe = hash_file('sha256', edbak_ordner($kennung) . '/' . $paket2);

    $archivVon = '2001-01-0' . random_int(1, 9) . ' 00:00:00';
    $stand['eigenes']['archive_von'][] = $archivVon; $merken();
    $archivAlt = protokoll_archiv_name($archivVon, $Akenn);
    @mkdir(protokoll_archiv_wurzel(), 0770, true);
    $archivTeil = str_repeat("{\"art\":\"probe\"}\n", 300);
    $manifestA = ['format' => 'einsatzdoku-protokollarchiv', 'fassung' => 1, 'von' => $archivVon,
                  'bis' => $archivVon, 'kennung' => $Akenn, 'teile' => ['probe.jsonl']];
    $bau = sys_get_temp_dir() . '/swp-' . bin2hex(random_bytes(4));
    @mkdir($bau);
    file_put_contents("$bau/m", sk_versiegeln((string)json_encode($manifestA), protokoll_archiv_zweck($archivAlt, 'manifest.json')));
    file_put_contents("$bau/t", sk_versiegeln($archivTeil, protokoll_archiv_zweck($archivAlt, 'probe.jsonl')));
    zip_bauen(protokoll_archiv_wurzel() . '/' . $archivAlt, ['manifest.json.sk' => "$bau/m", 'probe.jsonl.sk' => "$bau/t"]);
    array_map('unlink', glob("$bau/*") ?: []); @rmdir($bau);

    $standA = stand_bauen();
    $stand['eigenes']['staende'][] = $standA; $merken();
    /* Das Konto für die Seitenabrufe — VOR dem Zählen der Empfängerinnen,
     * denn es ist selbst eine BetreiberIn mit Passwort. */
    $bid = $konto('schluesselwechselprobe-betrieb@probe.invalid', 'betreiberin');

    pruefe($totpKlar !== '' && totp_geheimnis($uid) === $totpKlar
        && sz_geheim(['id' => $zid, 'geheim' => $pdo->query("SELECT geheim FROM backup_targets WHERE id = $zid")->fetchColumn()], 'geheim') === $zielKlar
        && edbak_paket_teil_lesen($kennung, $paket, 'kern.json') === $teilKlar['kern.json']
        && edbak_begleit_lesen($kennung)['lesbar'] === true
        && protokoll_archiv_manifest($archivAlt) !== null
        && komp_serverschluessel_fuer(komp_wurzel() . '/' . $standA)[1] === 'neu'
        && (komp_kopf_lesen(komp_wurzel() . '/' . $standA)['kopf']['kennung'] ?? null) === $Akenn,
        'sieben Stücke unter A angelegt und lesbar (Ziel, Zweitfaktor, Begleitdatei, Paket, Archiv, Stand mit Kennung A)');
    pruefe(totp_geheimnis($uidFremd) === null, 'das fremd versiegelte Stück öffnet mit A nicht');

    /* ---- 3. Riegel vor dem Wechsel ------------------------------------------ */
    teil('3. Riegel vor dem Wechsel');
    [$ok, $was] = serverschluessel_wechseln(false);
    pruefe(!$ok && str_contains($was, 'bestätigen') && konfig('server_key_alt', null) === null
        && strtolower((string)konfig('server_key', '')) === $A,
        'ohne Haken: abgewiesen, config.php unverändert (E-SR-63)', $was);
    if (anteil_zustand(true)['stand'] === 'bereit') {
        $weg = konfig_stellen(['kdf_anteil_alt' => bin2hex(random_bytes(32))]);
        anteil_zustand(true);
        [$ok, $was] = serverschluessel_wechseln(true);
        $weg();
        config_gemerktes_verwerfen();
        pruefe(!$ok && str_contains($was, 'Server-Anteils') && konfig('server_key_alt', null) === null,
            'neben einer Anteil-Rotation: abgewiesen (E-SR-60)', $was);
    } else {
        pruefe(false, 'neben einer Anteil-Rotation: abgewiesen (E-SR-60)',
            'nicht gemessen — die Anlage hat keinen Server-Anteil (Lage „' . anteil_zustand()['stand'] . '")');
    }

    /* ---- 4. Der Wechsel A → B ------------------------------------------------ */
    teil('4. Der Wechsel A → B');
    $betreiberinnen = count(mail_betreiberinnen());
    [$ok, $Bkenn] = serverschluessel_wechseln(true);
    $B = strtolower((string)konfig('server_key', ''));
    $stand['b_hex'] = $B; $merken();
    $z = serverschluessel_zustand(true);
    pruefe($ok && $z['stand'] === 'rotation' && $z['kennung'] === $Bkenn && $z['kennung_alt'] === $Akenn
        && app_state_lesen('server_key_kennung') === $Bkenn
        && strtolower((string)konfig('server_key_alt', '')) === $A,
        'Lage rotation, neu B, bisher A, Marke auf B, server_key_alt = A', "B $Bkenn");
    pruefe(app_state_lesen(BLATT_BESTAETIGT_K) === null && blatt_neu_weil() === 'serverschluessel',
        'Blatt-Marke gelöscht, Grund „serverschluessel" (Nr. 233, E-SR-11)');
    $prot = static fn(string $art): int => (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
        WHERE id > {$stand['max_prot']} AND art = '$art'")->fetchColumn();
    $post = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
        WHERE id > {$stand['max_mail']} AND schluessel = 'serverschluessel_gewechselt'")->fetchColumn();
    pruefe($prot('serverschluessel_gewechselt') === 1, 'Protokoll: serverschluessel_gewechselt, einmal');
    pruefe(!smtp_eingerichtet() || $post() === $betreiberinnen,
        'Mail an jede BetreiberIn mit Passwort (E-SR-62)',
        smtp_eingerichtet() ? $post() . " von $betreiberinnen" : 'SMTP nicht eingerichtet — nichts eingereiht');
    [$ok, $was] = anteil_wechseln();
    pruefe(!$ok && str_contains($was, 'Serverschlüssels') && anteil_zustand(true)['stand'] !== 'rotation',
        'Anteil wechseln während des Wechsels: abgewiesen (E-SR-60)', $was);
    [$ok, $was] = serverschluessel_alt_entfernen();
    pruefe(!$ok && substr_count($was, '.') >= 3 && str_contains($was, 'umgehüllt')
        && str_contains($was, 'Komplett-Stand') && str_contains($was, 'Rückfrage')
        && konfig('server_key_alt', null) !== null,
        'Entfernen jetzt: abgewiesen mit drei Gründen, server_key_alt steht', $was);
    pruefe(totp_geheimnis($uid) === $totpKlar
        && edbak_paket_teil_lesen($kennung, $paket, 'kern.json') === $teilKlar['kern.json']
        && protokoll_archiv_manifest($archivAlt) !== null
        && komp_serverschluessel_fuer(komp_wurzel() . '/' . $standA)[1] === 'alt',
        'alles unter A öffnet noch — über den bisherigen; der Stand A heißt „alt"');

    /* Blatt und Karte über HTTP — OPcache sieht die neue config.php erst nach
     * revalidate_freq (F-SR-31): drei Sekunden warten. */
    $sitz = sitzung_anlegen($bid);
    $stand['eigenes']['sitzungen'][] = $sitz['sid']; $merken();
    sleep(3);
    $blatt = hole('betrieb_schluesselblatt.php', $sitz);
    $kachel = preg_match_all('/class="blatt-kachel-name">([^<]+)</', $blatt['rumpf'], $m) ? $m[1] : [];
    $sollK = ['Serverschlüssel', 'Serverschlüssel (bisheriger)'];
    if (anteil_zustand()['kennung'] !== null) { $sollK[] = 'Server-Anteil'; }
    pruefe($blatt['code'] === 200 && $kachel === $sollK && str_contains($blatt['rumpf'], 'NICHT vernichten'),
        'Blatt während des Wechsels: ' . count($sollK) . ' Kacheln, der bisherige mit dem Satz aus E-SR-10',
        "HTTP {$blatt['code']}: " . implode(' | ', $kachel));
    $karte = hole('betrieb_server.php', $sitz);
    pruefe($karte['code'] === 200 && str_contains($karte['rumpf'], 'Wechsel läuft: neu ' . $Bkenn)
        && str_contains($karte['rumpf'], 'Umhüllung') && str_contains($karte['rumpf'], 'Jetzt weiterarbeiten')
        && !str_contains($karte['rumpf'], 'Alten Schlüssel entfernen')
        && !str_contains($karte['rumpf'], 'Server-Anteil wechseln'),
        'Karte: Wechsel läuft, Umhüllung, „Jetzt weiterarbeiten", kein Entfernen, kein Anteil-Wechsel',
        "HTTP {$karte['code']}");

    /* ---- 5. Häppchen -------------------------------------------------------- */
    teil('5. Häppchen, Wiederanlauf, Nachweis');
    @mkdir(sw_arbeit(), 0770, true);
    file_put_contents(sw_arbeit() . '/halb.zip', 'abgebrochen');
    @mkdir(sw_arbeit() . '/halb.d');
    file_put_contents(sw_arbeit() . '/halb.d/0', 'x');
    $e = jobs_einen_lauf(SW_JOB, jobs_katalog()[SW_JOB], 'probe', static fn(): float => 1.0);
    $zz = sw_zustand();
    $offenDateien = count(glob(sw_arbeit() . '/*') ?: []);
    $nurZeilen = ($zz['zweck'] ?? '') === 'konten' && ($zz['phase'] ?? '') === 'umhuellen'
              && (komp_zustand()['stand'] ?? null) !== 'dump';   // noch kein Auftrag (Q-SR-03)
    pruefe(($e['fehler'] ?? null) === null && $offenDateien === 0 && $nurZeilen
        && sk_oeffnen_mit_wem((string)$pdo->query("SELECT totp_geheimnis FROM users WHERE id = $uid")->fetchColumn(),
                              'totp|' . $uid)['mit'] === 'neu',
        'mit 1 s: die halbe Nebendatei geräumt, nur die Zeilen umgehüllt, vor den Dateien angehalten',
        'Zweck ' . ($zz['zweck'] ?? '?') . ', Arbeitsordner ' . $offenDateien . ', erledigt ' . (int)($e['erledigt'] ?? -1)
        . ($e['fehler'] ?? ''));
    /* WIEDERANLAUF BEIM ARCHIV: ein Lauf, der zwischen Ablegen und Löschen
     * abbrach — die neue Datei liegt schon da, die alte auch. Erwartet: Die
     * neue bleibt byte-gleich (nicht neu gebaut), die alte geht. */
    $archivKopie = (string)file_get_contents(protokoll_archiv_wurzel() . '/' . $archivAlt);
    $vorab = sw_archiv($archivAlt, $Bkenn, $Akenn, false);
    file_put_contents(protokoll_archiv_wurzel() . '/' . $archivAlt, $archivKopie);
    $archivNeuSumme = hash_file('sha256', protokoll_archiv_wurzel() . '/' . protokoll_archiv_name($archivVon, $Bkenn));
    for ($i = 0; $i < 40 && (sw_zustand()['phase'] ?? '') !== 'fertig'; $i++) {
        $e = sw_jetzt(30.0);
        if (($e['fehler'] ?? null) !== null) { break; }
        if (isset($e['uebersprungen'])) { sleep(2); }
    }
    $zz = sw_zustand();
    pruefe(($zz['phase'] ?? '') === 'fertig' && ($e['fehler'] ?? null) === null
        && count(glob(sw_arbeit() . '/*') ?: []) === 0,
        'bis fertig: umgehüllt und nachgewiesen, Arbeitsordner leer',
        'Phase ' . ($zz['phase'] ?? '?') . ', umgehüllt ' . (int)($zz['umgehuellt'] ?? 0)
        . ', Nachweis ' . json_encode($zz['zahlen'] ?? []) . ($e['fehler'] ?? ''));
    pruefe($prot('serverschluessel_umgehuellt') === 1, 'Protokoll: serverschluessel_umgehuellt, einmal');
    pruefe(($zz['komplett_auftrag'] ?? null) === 'vorgemerkt' && (komp_zustand()['stand'] ?? null) === 'dump',
        'nach dem Nachweis ist ein Komplett-Stand vorgemerkt (Q-SR-03), nicht vorher',
        (string)($zz['komplett_auftrag'] ?? '—') . ', Auftrag ' . (string)(komp_zustand()['stand'] ?? '—'));

    $mit = static function (?string $paket, string $zweck): ?string {
        $r = $paket === null ? null : sk_oeffnen_mit_wem($paket, $zweck);
        return $r['mit'] ?? null;
    };
    $archivNeu = protokoll_archiv_name($archivVon, $Bkenn);
    $zipTeil = static fn(string $pfad, string $n): ?string => zip_eintrag($pfad, $n);
    pruefe($mit((string)$pdo->query("SELECT totp_geheimnis FROM users WHERE id = $uid")->fetchColumn(), 'totp|' . $uid) === 'neu'
        && totp_geheimnis($uid) === $totpKlar, 'Zweitfaktor: öffnet mit B, derselbe Klartext');
    pruefe($mit((string)$pdo->query("SELECT geheim FROM backup_targets WHERE id = $zid")->fetchColumn(), sz_zweck($zid, 'geheim')) === 'neu'
        && sz_geheim(['id' => $zid, 'geheim' => $pdo->query("SELECT geheim FROM backup_targets WHERE id = $zid")->fetchColumn()], 'geheim') === $zielKlar,
        'Ziel: öffnet mit B, dasselbe Passwort');
    pruefe($mit((string)file_get_contents(edbak_ordner($kennung) . '/konto.json'), edbak_begleit_zweck($kennung)) === 'neu'
        && edbak_begleit_lesen($kennung)['name'] === 'Schluesselwechselprobe',
        'Begleitdatei: öffnet mit B, derselbe Inhalt');
    $pp = edbak_ordner($kennung) . '/' . $paket;
    pruefe($mit($zipTeil($pp, 'manifest.json'), edbak_teil_zweck($kennung, $paket, 'manifest.json')) === 'neu'
        && $mit($zipTeil($pp, 'kern.json'), edbak_teil_zweck($kennung, $paket, 'kern.json')) === 'neu'
        && edbak_paket_teil_lesen($kennung, $paket, 'kern.json') === $teilKlar['kern.json'],
        'Konto-Backup: jeder Teil mit B, derselbe Klartext, derselbe Name');
    clearstatcache();
    pruefe((int)filesize($pp) === $paketGroesse,
        'Konto-Backup: dieselbe Größe — der Versand (Name und Größe) hält es auf dem Ziel für vorhanden',
        $paketGroesse . ' → ' . (int)filesize($pp) . ' Byte');
    pruefe(hash_file('sha256', edbak_ordner($kennung) . '/' . $paket2) === $paket2Summe,
        'Konto-Backup der Fassung 2 (ohne Siegel): byte-gleich');
    $ma = protokoll_archiv_manifest($archivNeu);
    pruefe(!is_file(protokoll_archiv_wurzel() . '/' . $archivAlt) && $ma !== null
        && ($ma['kennung'] ?? null) === $Bkenn
        && sk_oeffnen((string)$zipTeil(protokoll_archiv_wurzel() . '/' . $archivNeu, 'probe.jsonl.sk'),
                      protokoll_archiv_zweck($archivNeu, 'probe.jsonl')) === $archivTeil,
        'Archiv: neuer Name mit Kennung B, der alte weg, Manifest und Teil unter B', $archivNeu);
    pruefe($vorab === 'umgehuellt'
        && hash_file('sha256', protokoll_archiv_wurzel() . '/' . $archivNeu) === $archivNeuSumme,
        'Archiv-Wiederanlauf: die schon abgelegte neue Datei blieb byte-gleich, die alte ging');
    $verl = (array)($zz['verloren'] ?? []);
    pruefe(in_array('Zweitfaktor-Geheimnis des Kontos Nr. ' . $uidFremd, $verl, true)
        && totp_geheimnis($uidFremd) === null,
        'das fremd versiegelte Stück: gezählt und genannt, nicht angefasst (E-SR-61)', implode(' · ', $verl));
    $bed = sw_bedingungen();
    pruefe($bed['inventar'] && !$bed['komplett'] && !$bed['blatt'] && !$bed['alle'],
        'Bedingungen: Inventar ja, frischer Stand nein, Rückfrage nein', json_encode($bed['fehlt']));

    /* ---- 6. Abschluss --------------------------------------------------------- */
    teil('6. Abschluss');
    [$ok, $was] = serverschluessel_alt_entfernen();
    pruefe(!$ok && str_contains($was, 'Komplett-Stand') && str_contains($was, 'Rückfrage')
        && !str_contains($was, 'umgehüllt'), 'Entfernen: abgewiesen — Stand und Rückfrage fehlen', $was);
    $standB = stand_bauen();
    $stand['eigenes']['staende'][] = $standB; $merken();
    pruefe(sw_bedingungen()['komplett'] && !sw_bedingungen()['alle'],
        'ein frischer Stand unter B: Bedingung 2 erfüllt, die Rückfrage fehlt noch');
    blatt_bestaetigt();
    pruefe(sw_bedingungen()['alle'], 'Rückfrage beantwortet: alle drei erfüllt');
    [$ok, $was] = serverschluessel_alt_entfernen();
    $z = serverschluessel_zustand(true);
    pruefe($ok && $was === $Akenn && konfig('server_key_alt', null) === null && $z['stand'] === 'bereit'
        && $z['kennung'] === $Bkenn && sw_zustand() === [],
        'entfernt: server_key_alt fort, Lage bereit mit B, Jobzustand leer', $was);
    pruefe($prot('serverschluessel_alt_entfernt') === 1, 'Protokoll: serverschluessel_alt_entfernt, einmal');
    pruefe(!smtp_eingerichtet() || $post() === 2 * $betreiberinnen, 'Mail zum Abschluss an jede BetreiberIn',
        smtp_eingerichtet() ? $post() . ' von ' . (2 * $betreiberinnen) : 'SMTP nicht eingerichtet');
    pruefe(totp_geheimnis($uid) === $totpKlar && edbak_paket_teil_lesen($kennung, $paket, 'kern.json') === $teilKlar['kern.json']
        && protokoll_archiv_manifest($archivNeu) !== null
        && komp_serverschluessel_fuer(komp_wurzel() . '/' . $standB)[1] === 'neu',
        'nach dem Entfernen öffnet alles mit B');
    pruefe(komp_serverschluessel_fuer(komp_wurzel() . '/' . $standA)[1] === 'keiner'
        && komp_erster_block_oeffnet(komp_wurzel() . '/' . $standA, (string)hex2bin($A)),
        'der alte Stand: mit keinem Schlüssel der Anlage — mit dem Wert A vom Blatt schon');

    /* ---- 7. Nr. 344 ----------------------------------------------------------- */
    teil('7. Freigabe widerrufen ohne gültige Kennung (Nr. 344)');
    $wurzelDatei = edbak_wurzel() . '/konto.json';
    $vorher = is_file($wurzelDatei);
    $w = hole('admin_sicherungen.php', $sitz, ['action' => 'widerrufen', 'handgriff' => str_repeat('0', 16)]);
    pruefe($w['code'] === 200 && str_contains($w['rumpf'], 'Die Freigabe liess sich nicht widerrufen.')
        && !$vorher && !is_file($wurzelDatei),
        'Handgriff aus Nullen: Fehlermeldung, keine konto.json in der Wurzel der Ablage',
        "HTTP {$w['code']}" . ($vorher ? ' — lag schon VOR dem Aufruf da' : '')
        . (is_file($wurzelDatei) ? ' — liegt da' : ''));
    if (!$vorher && is_file($wurzelDatei)) { @unlink($wurzelDatei); }   // hat dieser Aufruf gelegt
} catch (Throwable $ex) {
    pruefe(false, 'die Probe lief ohne Ausnahme durch', get_class($ex) . ': ' . $ex->getMessage()
        . ' @ ' . basename($ex->getFile()) . ':' . $ex->getLine());
} finally {
    /* ---- 8. Rückweg B → A ----------------------------------------------------- */
    teil('8. Rückweg B → A mit demselben Job, dann alles zurück');
    $r = rueckweg($stand);
    pruefe($r['abweichend'] === [] && $r['fehler'] === null,
        'jedes Stück der Anlage sagt wieder dasselbe wie vor der Probe (' . count($stand['bild']) . ')',
        "{$r['laeufe']} Läufe, {$r['umgehuellt']} Stücke zurück nach A"
        . ($r['fehler'] !== null ? ' · Fehler: ' . $r['fehler'] : '')
        . ($r['abweichend'] !== [] ? ' · ' . implode(' · ', array_slice($r['abweichend'], 0, 5)) : ''));
    pruefe(hash('sha256', (string)file_get_contents($pfadKonfig)) === hash('sha256', (string)base64_decode($stand['konfig_bytes']))
        && serverschluessel_zustand(true)['stand'] === 'bereit' && serverschluessel_kennung() === $Akenn,
        'config.php byte-gleich, Lage bereit mit A');
    sleep(3);   // OPcache der Anlage sieht die alte config.php wieder (F-SR-31)
}

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
