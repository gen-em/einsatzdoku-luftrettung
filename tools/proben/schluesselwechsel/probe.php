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
 *      einer Anteil-Rotation (E-SR-60); ein `server_key_alt` gleich dem
 *      heutigen ist kein Wechsel (F-SR-78) und steht nicht auf dem Blatt; eine
 *      Sperre, die steht, sagt seit wann und bis wann höchstens; die
 *      Rücknahme eines halben Griffs räumt keinen fremden Eintrag weg
 *      (Nachprüfung H-SR-06, F-SR-86).
 *   4. Der Wechsel A → B — ZWEIMAL GLEICHZEITIG, aus zwei Prozessen: genau
 *      einer kommt durch, A bleibt als bisheriger (H-SR-06, F-SR-78). Lage
 *      `rotation`, Marke auf B, Blatt-Marke gelöscht (Nr. 233, E-SR-11),
 *      Protokoll, Mail an jede BetreiberIn (E-SR-62); ein dritter Wechsel und
 *      die Anteil-Rotation sind jetzt gesperrt; das Entfernen verweigert mit
 *      drei Gründen; alles Alte öffnet noch (über den bisherigen); Blatt und
 *      Karte über HTTP.
 *   5. Die Häppchen: ein Wiederanlauf räumt eine halbe Nebendatei weg; mit
 *      wenig Zeit nur die Zeilen, nicht die Dateien. Ein Stück, das beim
 *      Umhüllen wirft, wird genannt und hält Bedingung 1 zu (F-SR-81); der
 *      Nachweis wartet die Frist ab (E-SR-73) und kreist nicht um ein Paket
 *      mit kaputtem Teil (F-SR-81). Dann bis `fertig`: JEDES Stück öffnet
 *      mit B und liefert denselben Klartext; die Archive haben den Namen
 *      gewechselt, auch eines mit fremder Kennung im Namen; das Stück ohne
 *      Siegel ist byte-gleich; das unlesbare ist gezählt und genannt; ein
 *      Komplett-Stand, dessen Versiegelung über den Wechsel lief, ist ganz
 *      unter B (E-SR-72); nach einem Einspielen beginnt der Nachweis neu.
 *      Ein Archiv, das sich nicht in den Arbeitsordner schreiben lässt,
 *      steht in der Fehlerliste, nicht unter „mit keinem der beiden"; ein
 *      Nachweis ohne Stück wird einmal fertig, nicht in jedem Häppchen; eine
 *      Lage „abweichend" lässt den Zustand stehen (Nachprüfung H-SR-06,
 *      F-SR-85). Ein Zustand eines anderen Schlüsselpaars in der Phase
 *      „fertig" heißt „nicht begonnen", und der Knopf steht da (F-SR-87).
 *      Die Karte zählt über der Decke auch mitten im Durchgang mit („und 3
 *      weitere") und sagt ungezählt „wird gezählt", nicht „noch 0 von 0"
 *      (F-SR-88, seit der Nachmessung).
 *   6. Der Abschluss: ohne frischen Stand und ohne Rückfrage verweigert; mit
 *      beiden entfernt; danach öffnet alles mit B, der alte Stand nur noch
 *      mit dem Wert A („vom Blatt"). Dieser Stand liegt jetzt unter einem
 *      DRITTEN Schlüssel: Die Liste sagt es in einem Glied der Kleinzeile und
 *      bietet weder Herunterladen noch Passphrase an, der Download weist vor
 *      Protokoll und Kopfzeilen ab (F-SR-82, F-SR-87; bis zur Nachmessung
 *      gelesen).
 *   7. Nr. 344, über HTTP: „Freigabe widerrufen" mit einem Handgriff aus
 *      Nullen meldet einen Fehler und legt KEINE `konto.json` in die Wurzel
 *      der Ablage. (Das Konzept nannte die Freigabeprobe; die arbeitet als
 *      NutzerIn im Browser und hat kein Adminkonto — die Sitzung einer
 *      BetreiberIn hat diese Probe ohnehin, E-SR-66.)
 *   8. DER RÜCKWEG B → A — mit demselben Job. Er ist kein Aufräumen, sondern
 *      der zweite Wechsel: Der erste hat auch die Geheimnisse der Anlage selbst
 *      umgehüllt (die Zweitfaktoren der Prüfkonten), und ohne ihn wären sie
 *      nach dem Zurücklegen von `config.php` unlesbar. Er steht von Hand in
 *      `config.php` (A neu, B bisher) und misst damit den Beginn, den der Job
 *      einem solchen Wechsel gibt (E-SR-74). Gezählt wird, dass danach jedes
 *      Stück der Anlage mit A öffnet. WIRD ER NICHT FERTIG, LEGT ER NICHTS
 *      ZURÜCK (H-SR-06, F-SR-83): A und B bleiben in `config.php`, die
 *      Stand-Datei bleibt, und ein zweites `--zurueck` macht weiter. Bis Web
 *      21.12.0 warf er B in diesem Fall weg. Als B gilt nur ein Wert, der
 *      nicht A ist (Nachprüfung H-SR-06, F-SR-84).
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
 * Temp-Verzeichnis (`schluesselwechselprobe.json`, 0600 vom ersten Byte an,
 * mit BEIDEN Werten) und läuft mit `--zurueck`. **Nicht gegen eine Anlage
 * mit Betrieb.** Die Frist vor dem Nachweis (zehn Minuten, E-SR-73) kürzt
 * sie im Zustand — die Jobs stehen still, und außer ihr versiegelt niemand.
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
/** Der Knopf „Jetzt weiterarbeiten", an seiner Aktion erkannt: Das Wort allein
 *  steht auch in der Kleinzeile der Zeile „Umhüllung" (Nachmessung H-SR-06). */
const SWP_KNOPF_WEITER = 'value="schluessel_sk_weiter"';
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

/** Den Stand in die Datei — nur für die eigene Kennung lesbar, und zwar vom
 *  ersten Byte an (H-SR-06, F-SR-83). Bis Web 21.12.0 lag sie zwischen dem
 *  Schreiben und dem `chmod` einen Augenblick mit 0644 da — mit dem Wert A
 *  und der ganzen `config.php`. */
function swp_stand_schreiben(array $s): void
{
    $p = sys_get_temp_dir() . '/' . SWP_DATEI;
    $maske = umask(0077);
    try {
        file_put_contents($p, json_encode($s));
    } finally {
        umask($maske);
    }
    @chmod($p, 0600);
}

/** Die Frist vor dem Nachweis (E-SR-73) im Zustand kürzen — die Probe hält
 *  die Jobs an, und außer ihr läuft kein Prozess, der mit dem bisherigen
 *  Schlüssel versiegeln könnte. */
function swp_frist_kuerzen(): void
{
    $z = sw_zustand();
    if ($z === [] || ($z['nachweis_begonnen'] ?? null) !== null) { return; }
    $z['nachweis_ab'] = gmdate('Y-m-d H:i:s', time() - 1);
    sw_zustand_setzen($z);
}

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
    $bericht = ['umgehuellt' => 0, 'laeufe' => 0, 'abweichend' => [], 'fehler' => null,
                'unter_b' => null, 'hand' => null];
    $a = strtolower((string)$s['a_hex']);
    $b = isset($s['b_hex']) ? strtolower((string)$s['b_hex']) : null;
    /* A IST NIE B (Nachprüfung H-SR-06, F-SR-84). Kam beim Wettlauf keines der
     * Kinder durch, stand bis dahin A als `b_hex` in der Datei — der Rückweg
     * schrieb A und A, die Lage hieß `bereit`, der Job tat nichts, und jedes
     * `--zurueck` endete „nicht fertig". Wechselte der Hauptlauf danach selbst,
     * stand sein B' nirgends sonst und wurde überschrieben. Jetzt nimmt der
     * Rückweg B dann aus config.php, wie nach dem Bedienweg. */
    if ($b === $a) { $b = null; }
    /* WOHER B, WENN DER STAND ES NICHT KENNT: Nach dem Bedienweg hat der
     * Browser gewechselt. Dann ist B, was in config.php neben A steht — als
     * `server_key`, oder als `server_key_alt` nach einem abgebrochenen
     * Rückweg. Und es wird SOFORT in die Datei geschrieben: Ab hier ist sie
     * die zweite Stelle, die B kennt. */
    $hex = static fn(string $h): bool => preg_match('/^[0-9a-f]{64}$/', $h) === 1;
    config_gemerktes_verwerfen();
    $cKey = strtolower((string)konfig('server_key', ''));
    $cAlt = strtolower((string)konfig('server_key_alt', ''));
    if ($b === null && $hex($cKey) && $cKey !== $a) { $b = $cKey; }
    if ($b === null && $hex($cAlt) && $cAlt !== $a) { $b = $cAlt; }
    if ($b !== null && ($s['b_hex'] ?? null) === null) {
        $s['b_hex'] = $b;
        swp_stand_schreiben($s);
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
    foreach ((array)($e['ordner_weg'] ?? []) as $d) {
        /* Wegwerfordner der Probe (Sperre im Arbeitsordner, Komplett-Bau):
         * höchstens zwei Ebenen. */
        foreach (glob($d . '/*') ?: [] as $f) {
            if (is_dir($f)) { array_map('unlink', glob($f . '/*') ?: []); @rmdir($f); }
            else { @unlink($f); }
        }
        @rmdir($d);
    }

    if ($b !== null) {
        /* DER RÜCKWEG IST EIN ZWEITER WECHSEL: A neu, B bisher — immer. Auch
         * wenn config.php schon so aussieht (ein abgebrochener Rückweg) oder
         * nur A trägt (eine Abbruchsicherung hat zurückgelegt): Solange B in
         * der Datei steht, ist nichts, was noch unter B liegt, verloren. Bis
         * Web 21.12.0 hing das an `server_key !== A`, und ein zweites
         * `--zurueck` übersprang den Job (H-SR-06, F-SR-83). */
        konfig_stellen_schreiben(konfig_stellen_pfad(),
            array_merge(konfig_stellen_lesen(konfig_stellen_pfad()),
                        ['server_key' => $a, 'server_key_alt' => $b]));
        config_gemerktes_verwerfen();
        $kA = (string)schluessel_kennung($a);
        $kB = (string)schluessel_kennung($b);
        /* Nennt die Marke B, wandert sie beim Lesen auf A („Wechsel soeben
         * eingeleitet"). Nennt sie etwas anderes, wird sie A — sonst stünde
         * die Lage auf „abweichend". */
        if (app_state_lesen('server_key_kennung') !== $kB) {
            app_state_setzen('server_key_kennung', $kA);
        }
        serverschluessel_zustand(true);
        /* EINE STEHENGEBLIEBENE SPERRE LÖSEN — aber nur eine alte: Brach die
         * Probe mitten in einem Häppchen ab, steht `laeuft_seit` bis zu einer
         * Stunde, und jeder Lauf hieße „läuft bereits". Eine junge kann einem
         * Häppchen gehören, das gerade arbeitet (Nachprüfung H-SR-06,
         * F-SR-84); dann wartet die Schleife unten. */
        $sperreLoesen = static function (): void {
            db()->prepare('UPDATE jobs SET laeuft_seit = NULL WHERE job = ?
                             AND laeuft_seit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL 120 SECOND)')
                ->execute([SW_JOB]);
        };
        $sperreLoesen();
        /* DER ZUSTAND BLEIBT LEER, und der Job beginnt den Wechsel selbst —
         * derselbe Weg wie ein Wechsel von Hand in config.php (E-SR-74), und
         * damit gemessen. Gehört der Zustand schon zu A/B (ein abgebrochener
         * Rückweg), arbeitet der Job dort weiter. */
        $z0 = sw_zustand();
        $frisch = ($z0['kennung_neu'] ?? null) !== $kA || ($z0['kennung_alt'] ?? null) !== $kB;
        if ($frisch) { sw_zustand_setzen([]); }
        $maxProt = (int)db()->query('SELECT COALESCE(MAX(id), 0) FROM protokoll_ereignisse')->fetchColumn();
        $maxMail = (int)db()->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();
        for ($i = 0; $i < 60; $i++) {
            $l = sw_jetzt(30.0);
            $bericht['laeufe']++;
            if (($l['fehler'] ?? null) !== null) { $bericht['fehler'] = (string)$l['fehler']; break; }
            if ($i === 0 && $frisch) {
                $bericht['hand'] = [
                    'protokoll' => (int)db()->query("SELECT COUNT(*) FROM protokoll_ereignisse
                        WHERE id > $maxProt AND art = 'serverschluessel_gewechselt'
                          AND text LIKE '%von Hand%'")->fetchColumn(),
                    'blatt' => app_state_lesen(BLATT_BESTAETIGT_K) === null
                               && blatt_neu_weil() === 'serverschluessel',
                    'mails' => (int)db()->query("SELECT COUNT(*) FROM mail_warteschlange
                        WHERE id > $maxMail AND schluessel = 'serverschluessel_gewechselt'")->fetchColumn(),
                    'betreiberinnen' => count(mail_betreiberinnen()),
                    'weg' => (string)(sw_zustand()['weg'] ?? ''),
                    /* Nach dem Beginn endet das Häppchen (F-SR-85), und die
                     * Mails trägt der Mailjob hinaus, nicht die Anfrage. */
                    'erledigt' => (int)($l['erledigt'] ?? -1),
                    'versucht' => (int)db()->query("SELECT COUNT(*) FROM mail_warteschlange
                        WHERE id > $maxMail AND schluessel = 'serverschluessel_gewechselt'
                          AND versuche > 0")->fetchColumn(),
                ];
            }
            swp_frist_kuerzen();
            if ((sw_zustand()['phase'] ?? '') === 'fertig') { break; }
            if (isset($l['uebersprungen'])) { $sperreLoesen(); sleep(2); }
        }
        $z = sw_zustand();
        $bericht['umgehuellt'] = (int)($z['umgehuellt'] ?? 0);
        if (($z['phase'] ?? '') !== 'fertig' || (array)($z['fehler'] ?? []) !== []
            || $bericht['fehler'] !== null) {
            /* NICHT FERTIG: NICHTS ZURÜCKLEGEN (H-SR-06, F-SR-83). Bis Web
             * 21.12.0 legte der Rückweg config.php hier trotzdem zurück und
             * löschte die Stand-Datei — B stand danach nirgends mehr, und was
             * noch unter B lag, war ohne Rückweg. Jetzt bleiben A und B in
             * config.php, die Datei bleibt, und ein zweites --zurueck macht
             * weiter. Gezählt wird, was noch unter B liegt. */
            $unterB = 0;
            foreach (SW_ZWECKE as $zw) {
                foreach (sw_stuecke($zw) as $st) {
                    try {
                        if (sw_stueck($zw, $st, $z, true) === 'alt') { $unterB++; }
                    } catch (Throwable) {
                        $unterB++;
                    }
                }
            }
            $bericht['unter_b'] = $unterB;
            $bericht['fehler'] ??= 'Der Job B → A ist nicht fertig geworden (Phase „'
                . (string)($z['phase'] ?? '') . '"'
                . ((array)($z['fehler'] ?? []) !== []
                    ? ', ' . count((array)$z['fehler']) . ' Stück(e) ließen sich nicht umhüllen' : '')
                . ')';
            return $bericht;
        }
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
    db()->prepare("DELETE FROM protokoll_ereignisse WHERE id > ?
                     AND (art LIKE 'serverschluessel_%' OR art = 'komplett_heruntergeladen')")
        ->execute([(int)$s['max_prot']]);
    db()->prepare("DELETE FROM mail_warteschlange WHERE id > ?
                     AND schluessel IN ('serverschluessel_gewechselt', 'serverschluessel_abgeschlossen')")
        ->execute([(int)$s['max_mail']]);
    db()->prepare('DELETE FROM job_laeufe WHERE id > ? AND job = ?')
        ->execute([(int)$s['max_laeufe'], SW_JOB]);
    /* Die Mails gehen sofort hinaus (der Vorgabewert von `mail_einreihen()`
     * ist `sofort`), und die örtliche Anlage hat einen SMTP-Eintrag ohne
     * Gegenstelle: Jede schreibt eine Zeile „smtp: …" in den Reiter System.
     * Dazu die Zeilen „schluesselwechsel: …" des Stücks, das die Probe
     * absichtlich scheitern lässt (F-SR-81). Sie gehören der Probe — die Jobs
     * stehen still, und Proben laufen nicht nebeneinander. */
    db()->prepare("DELETE FROM protokoll_ereignisse WHERE id > ? AND reiter = 'system'
                     AND art = 'stoerung' AND (text LIKE 'smtp:%' OR text LIKE 'schluesselwechsel:%')")
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
    swp_stand_schreiben($s);
    echo $name, "\n";
    exit(0);
}
if ($modus === '--zurueck') {
    $pfad = sys_get_temp_dir() . '/' . SWP_DATEI;
    if (!is_file($pfad)) { fwrite(STDERR, "Kein gesicherter Stand in $pfad — nichts zurückzulegen.\n"); exit(2); }
    /* Auch hier die Jobs anhalten (Nachprüfung H-SR-06, F-SR-84): Läuft
     * `--zurueck` erst nach der Pause des Hauptlaufs, arbeitete sonst ein
     * Häppchen des Auslösers neben dem Rückweg im selben Arbeitsordner. Der
     * fertige Rückweg legt die Marke der Pause mit den übrigen zurück. */
    jobs_pause(JOB_PAUSE_MAX_S);
    $b = rueckweg((array)json_decode((string)file_get_contents($pfad), true));
    echo "Rückweg: {$b['laeufe']} Läufe, {$b['umgehuellt']} Stücke nach A, "
       . count($b['abweichend']) . " anders als vorher\n";
    foreach ($b['abweichend'] as $a) { echo "  $a\n"; }
    if ($b['fehler'] !== null) {
        echo "  NICHT FERTIG: {$b['fehler']}\n";
        if ($b['unter_b'] !== null) {
            echo "  {$b['unter_b']} Stück(e) liegen noch unter B. config.php trägt A und B, die "
               . "Stand-Datei bleibt — erneut --zurueck.\n";
        }
    }
    exit($b['abweichend'] === [] && $b['fehler'] === null ? 0 : 1);
}

/* ---- Vorbedingungen und Sicherung ----------------------------------------- */

/* DIE STAND-DATEI ZUERST (Nachprüfung H-SR-06, F-SR-84): Nach einem nicht
 * fertigen Rückweg trägt config.php A und B, und die Lage hieße „rotation" —
 * die Meldung dazu schickte auf die Suche nach einem Wechsel der Anlage
 * statt zum liegengebliebenen Rückweg. */
if (is_file(sys_get_temp_dir() . '/' . SWP_DATEI)) {
    fwrite(STDERR, "Nicht gelaufen: Ein früherer Lauf hat seinen Rückweg nicht beendet — erst --zurueck.\n");
    exit(2);
}
$sk0 = serverschluessel_zustand(true);
if ($sk0['stand'] !== 'bereit') {
    fwrite(STDERR, "Nicht gelaufen: Der Serverschlüssel ist „{$sk0['stand']}\", nicht „bereit\".\n");
    exit(2);
}
if (anteil_zustand(true)['stand'] === 'rotation') {
    fwrite(STDERR, "Nicht gelaufen: Es läuft eine Rotation des Server-Anteils.\n");
    exit(2);
}
/* EINE STEHENDE SPERRE DES JOBS (Nachprüfung H-SR-06, F-SR-84): Mit ihr
 * hörten beide Kinder des Wettlaufs „gleich noch einmal", und kein Wechsel
 * käme durch. Sie gehört einem Griff, einem Häppchen — oder einem, der
 * abbrach; das entscheidet nicht die Probe. */
$sperre0 = $pdo->prepare('SELECT laeuft_seit FROM jobs WHERE job = ? AND laeuft_seit IS NOT NULL');
$sperre0->execute([SW_JOB]);
$sperre0Seit = $sperre0->fetchColumn();
if ($sperre0Seit !== false) {
    fwrite(STDERR, "Nicht gelaufen: Die Sperre des Jobs „Schlüsselwechsel\" steht seit $sperre0Seit UTC — "
        . "ein Griff an die Schlüssel oder ein Häppchen läuft, oder einer brach ab. Sie verfällt nach "
        . "einer Stunde; wer sicher weiß, dass nichts läuft: UPDATE jobs SET laeuft_seit = NULL "
        . "WHERE job = 'schluesselwechsel'.\n");
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
                  'staende' => [], 'sitzungen' => [], 'ordner_weg' => []],
];
$merken = static function () use (&$stand): void { swp_stand_schreiben($stand); };
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
            /* AUCH VOLL QUALIFIZIERT (`\sk_versiegeln(`, H-SR-06, F-SR-83): Das
             * ist in PHP 8 ein eigenes Zeichen, kein T_STRING. Durch
             * Zeichenketten (`call_user_func('sk_versiegeln')`) sieht die
             * Zählung nicht — heute gibt es keine (Prüfdokument). */
            $name = is_array($t[$i]) ? $t[$i] : null;
            if ($name === null
                || !(($name[0] === T_STRING && $name[1] === 'sk_versiegeln')
                     || ($name[0] === T_NAME_FULLY_QUALIFIED && $name[1] === '\\sk_versiegeln'))) {
                continue;
            }
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
    /* EIN PAKET MIT EINEM KAPUTTEN TEIL (F-SR-81): Das Manifest öffnet mit
     * A, `kern.json` mit keinem. Das Umhüllen nennt es `verloren` und lässt
     * es liegen; bis Web 21.12.0 sagte der Nachweis dazu `alt`, und der
     * Wechsel wurde nie fertig. */
    $paket3 = substr($paket, 0, 20) . '_' . bin2hex(random_bytes(4)) . '.zip';
    $bau = sys_get_temp_dir() . '/swp-' . bin2hex(random_bytes(4));
    @mkdir($bau);
    file_put_contents("$bau/m3", edbak_teil_siegeln($teilKlar['manifest.json'], $kennung, $paket3, 'manifest.json'));
    file_put_contents("$bau/k3", fremd_versiegeln('kaputt', edbak_teil_zweck($kennung, $paket3, 'kern.json')));
    zip_bauen(edbak_ordner($kennung) . '/' . $paket3, ['manifest.json' => "$bau/m3", 'kern.json' => "$bau/k3"]);
    array_map('unlink', glob("$bau/*") ?: []); @rmdir($bau);
    $paket3Summe = hash_file('sha256', edbak_ordner($kennung) . '/' . $paket3);

    $archivVon = '2001-01-0' . random_int(1, 9) . ' 00:00:00';
    $stand['eigenes']['archive_von'][] = $archivVon; $merken();
    $archivAlt = protokoll_archiv_name($archivVon, $Akenn);
    @mkdir(protokoll_archiv_wurzel(), 0770, true);
    $archivTeil = str_repeat("{\"art\":\"probe\"}\n", 300);
    /* `zeilen` und `gekuerzt` LEER UND ALS OBJEKT, wie der Archivjob sie
     * schreibt (Backup-Format 7.1) — das Umhüllen machte daraus bis Web
     * 21.12.0 `[]` (F-SR-81). */
    $manifestA = ['format' => 'einsatzdoku-protokollarchiv', 'fassung' => 1, 'von' => $archivVon,
                  'bis' => $archivVon, 'kennung' => $Akenn, 'teile' => ['probe.jsonl'],
                  'zeilen' => new stdClass(), 'gekuerzt' => new stdClass()];
    $bau = sys_get_temp_dir() . '/swp-' . bin2hex(random_bytes(4));
    @mkdir($bau);
    file_put_contents("$bau/m", sk_versiegeln((string)json_encode($manifestA), protokoll_archiv_zweck($archivAlt, 'manifest.json')));
    file_put_contents("$bau/t", sk_versiegeln($archivTeil, protokoll_archiv_zweck($archivAlt, 'probe.jsonl')));
    zip_bauen(protokoll_archiv_wurzel() . '/' . $archivAlt, ['manifest.json.sk' => "$bau/m", 'probe.jsonl.sk' => "$bau/t"]);
    array_map('unlink', glob("$bau/*") ?: []); @rmdir($bau);

    /* EIN ARCHIV MIT EINER DRITTEN KENNUNG IM NAMEN, versiegelt mit A
     * (F-SR-81). Bis Web 21.12.0 hieß es `verloren`, obwohl der bisherige es
     * öffnete. */
    $archivVon3 = '2001-02-0' . random_int(1, 9) . ' 00:00:00';
    $stand['eigenes']['archive_von'][] = $archivVon3; $merken();
    $archivDritt = protokoll_archiv_name($archivVon3, 'deadbeef');
    $bau = sys_get_temp_dir() . '/swp-' . bin2hex(random_bytes(4));
    @mkdir($bau);
    file_put_contents("$bau/m", sk_versiegeln((string)json_encode(['kennung' => 'deadbeef'] + $manifestA),
                                              protokoll_archiv_zweck($archivDritt, 'manifest.json')));
    file_put_contents("$bau/t", sk_versiegeln($archivTeil, protokoll_archiv_zweck($archivDritt, 'probe.jsonl')));
    zip_bauen(protokoll_archiv_wurzel() . '/' . $archivDritt, ['manifest.json.sk' => "$bau/m", 'probe.jsonl.sk' => "$bau/t"]);
    array_map('unlink', glob("$bau/*") ?: []); @rmdir($bau);

    $standA = stand_bauen();
    $stand['eigenes']['staende'][] = $standA; $merken();
    /* EIN KOMPLETT-STAND, DESSEN VERSIEGELUNG ÜBER DEN WECHSEL LÄUFT
     * (F-SR-79, E-SR-72): drei Blöcke Klartext, der Kopf mit Kennung A, ein
     * Block unter A versiegelt — dann hält die Probe an wie ein Häppchen, dem
     * die Zeit ausgeht. Nach dem Wechsel geht es mit dem weiter, was
     * `komp_schub_lauf()` tut: `komp_kopf_angleichen()`, dann siegeln. In
     * einem Wegwerfordner, nicht in der Ablage — dort räumte die Aufbewahrung. */
    $kompBau = sys_get_temp_dir() . '/swp-komp-' . bin2hex(random_bytes(4));
    @mkdir($kompBau, 0700);
    $stand['eigenes']['ordner_weg'][] = $kompBau; $merken();
    $kompRoh = random_bytes(3 * KOMP_BLOCK - 1000);
    file_put_contents("$kompBau/roh", $kompRoh);
    $kz = ['kopfzeile' => komp_kopf_bauen(['tabellen' => 1, 'zeilen' => 1, 'roh' => strlen($kompRoh)], null) . "\n",
           'siegel_i' => 0, 'siegel_bytes' => 0];
    $einmal = 0;
    komp_siegel_schub("$kompBau/roh", "$kompBau/ziel.edk", (string)serverschluessel(), $kz['kopfzeile'], $kz,
                      static function () use (&$einmal): float { return $einmal++ === 0 ? 100.0 : 0.0; }, 1.0);
    pruefe((int)$kz['siegel_i'] === 1
        && (komp_kopf_lesen("$kompBau/ziel.edk")['kopf']['kennung'] ?? null) === $Akenn,
        'Komplett-Stand halb versiegelt: Kopf mit A, ein Block von drei',
        'Block ' . (int)$kz['siegel_i']);
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
        'sechs Stücke unter A angelegt und lesbar (Ziel, Zweitfaktor, Begleitdatei, Paket, Archiv, Stand mit Kennung A)');
    pruefe(totp_geheimnis($uidFremd) === null, 'das fremd versiegelte Stück öffnet mit A nicht');

    /* ---- 3. Riegel vor dem Wechsel ------------------------------------------ */
    teil('3. Riegel vor dem Wechsel');
    [$ok, $was] = serverschluessel_wechseln(false);
    pruefe(!$ok && str_contains($was, 'bestätigen') && konfig('server_key_alt', null) === null
        && strtolower((string)konfig('server_key', '')) === $A,
        'ohne Haken: abgewiesen, config.php unverändert (E-SR-63)', $was);
    if (anteil_zustand(true)['stand'] === 'bereit') {
        /* OHNE `konfig_stellen()` (H-SR-06, F-SR-83): Dessen Abbruchsicherung
         * legt beim Ende des Prozesses den Text von HIER zurück — nach einem
         * Abbruch hinter dem Wechsel also eine config.php ohne B, während
         * Stücke schon unter B lägen. */
        $vorText = (string)file_get_contents($pfadKonfig);
        konfig_stellen_schreiben($pfadKonfig, array_merge(konfig_stellen_lesen($pfadKonfig),
            ['kdf_anteil_alt' => bin2hex(random_bytes(32))]));
        try {
            [$ok, $was] = serverschluessel_wechseln(true);
        } finally {
            konfig_stellen_bytes($pfadKonfig, $vorText);
        }
        config_gemerktes_verwerfen();
        pruefe(!$ok && str_contains($was, 'Server-Anteils') && konfig('server_key_alt', null) === null,
            'neben einer Anteil-Rotation: abgewiesen (E-SR-60)', $was);
    } else {
        pruefe(false, 'neben einer Anteil-Rotation: abgewiesen (E-SR-60)',
            'nicht gemessen — die Anlage hat keinen Server-Anteil (Lage „' . anteil_zustand()['stand'] . '")');
    }

    /* EINE STEHENDE SPERRE SAGT, SEIT WANN UND BIS WANN HÖCHSTENS
     * (Nachprüfung H-SR-06, F-SR-86). Stirbt ein Häppchen an der Zeitgrenze,
     * läuft kein `finally`; „gleich noch einmal" allein ließe eine Stunde
     * raten. Gemessen an einem Griff, der sonst nichts tut (Lage bereit). */
    $pdo->prepare('INSERT IGNORE INTO jobs (job) VALUES (?)')->execute([SW_JOB]);
    $pdo->prepare('UPDATE jobs SET laeuft_seit = DATE_SUB(UTC_TIMESTAMP(), INTERVAL 10 SECOND) WHERE job = ?')
        ->execute([SW_JOB]);
    $vorSperre = hash_file('sha256', $pfadKonfig);
    try {
        [$okS, $wasS] = serverschluessel_alt_entfernen();
    } finally {
        $pdo->prepare('UPDATE jobs SET laeuft_seit = NULL WHERE job = ?')->execute([SW_JOB]);
    }
    pruefe(!$okS && str_contains($wasS, 'seit ') && str_contains($wasS, 'spätestens')
        && hash_file('sha256', $pfadKonfig) === $vorSperre,
        'eine stehende Sperre: abgewiesen mit „seit" und „spätestens … frei", config.php unverändert (F-SR-86)',
        $wasS);

    /* DIE RÜCKNAHME EINES HALBEN GRIFFS (F-SR-86): Steht in `server_key`
     * nicht mehr der gesicherte Wert, hat jemand an der Sperre vorbei
     * geschrieben — dann bleibt `server_key_alt`. Steht er noch da, geht der
     * halbe Eintrag. Der fremde Wert ist ein Wegwerfwert; unter ihm wird in
     * diesem Augenblick nichts versiegelt (die Jobs stehen still). */
    konfig_stellen_schreiben($pfadKonfig, array_merge(konfig_stellen_lesen($pfadKonfig),
        ['server_key' => bin2hex(random_bytes(32)), 'server_key_alt' => $A]));
    try {
        $wasR1 = config_halbes_zuruecknehmen('server_key', 'server_key_alt', $A, 'eigen');
        $altR1 = strtolower((string)(konfig_stellen_lesen($pfadKonfig)['server_key_alt'] ?? ''));
    } finally {
        /* Die Marke zurück auf A: Beim Schreiben von „X neu, A bisher" ist
         * sie zu X gewandert — so liest die Lage einen soeben begonnenen
         * Wechsel, und das ist hier auch richtig so. */
        konfig_stellen_schreiben($pfadKonfig, array_merge(konfig_stellen_lesen($pfadKonfig),
            ['server_key' => $A, 'server_key_alt' => $A]));
        app_state_setzen('server_key_kennung', $Akenn);
        config_gemerktes_verwerfen();
    }
    $wasR2 = config_halbes_zuruecknehmen('server_key', 'server_key_alt', $A, 'eigen');
    config_gemerktes_verwerfen();
    pruefe(str_contains($wasR1, 'dazwischen') && $altR1 === $A
        && $wasR2 === 'eigen' && konfig('server_key_alt', null) === null
        && strtolower((string)konfig('server_key', '')) === $A,
        'Rücknahme eines halben Griffs: ein fremder server_key lässt server_key_alt stehen, der eigene halbe geht (F-SR-86)',
        mb_substr($wasR1, 0, 60));

    /* EIN `server_key_alt` GLEICH DEM HEUTIGEN — so bleibt ein Wechsel
     * stehen, der zwischen seinen beiden Schreibschritten abbrach (F-SR-78).
     * Kein Wechsel, sondern `bereit`; der nächste überschreibt ihn. */
    konfig_stellen_schreiben($pfadKonfig, array_merge(konfig_stellen_lesen($pfadKonfig), ['server_key_alt' => $A]));
    $zw = serverschluessel_zustand(true);
    pruefe($zw['stand'] === 'bereit' && $zw['kennung_alt'] === null && $zw['kennung'] === $Akenn,
        'server_key_alt = server_key: Lage bereit, kein Wechsel mit sich selbst (F-SR-78)', $zw['stand']);
    /* … und keine zweite Kachel mit derselben Kennung auf dem Blatt
     * (Nachprüfung H-SR-06, F-SR-86). Die Sitzung dient auch Teil 4. OPcache
     * sieht die neue config.php erst nach revalidate_freq (F-SR-31). */
    $sitz = sitzung_anlegen($bid);
    $stand['eigenes']['sitzungen'][] = $sitz['sid']; $merken();
    sleep(3);
    $blattW = hole('betrieb_schluesselblatt.php', $sitz);
    $kachelW = preg_match_all('/class="blatt-kachel-name">([^<]+)</', $blattW['rumpf'], $m) ? $m[1] : [];
    pruefe($blattW['code'] === 200 && in_array('Serverschlüssel', $kachelW, true)
        && !in_array('Serverschlüssel (bisheriger)', $kachelW, true),
        'Blatt bei server_key_alt = server_key: keine Kachel „bisheriger" (F-SR-86)',
        "HTTP {$blattW['code']}: " . implode(' | ', $kachelW));

    /* ---- 4. Der Wechsel A → B ------------------------------------------------ */
    teil('4. Der Wechsel A → B — zweimal gleichzeitig');
    $betreiberinnen = count(mail_betreiberinnen());
    /* ZWEI PROZESSE, EIN STARTSCHUSS (H-SR-06, F-SR-78). Bis Web 21.12.0 lief
     * der Wechsel ohne Sperre; je nach Verschränkung überschrieb der zweite
     * den frisch gewürfelten Schlüssel des ersten. Jetzt hält jeder die
     * Sperre der Jobzeile: genau einer kommt durch, der andere wartet und
     * hört „gleich noch einmal" oder „läuft bereits". JEDER HAT config.php
     * VOR DEM STARTSCHUSS GELESEN — wie eine Seite, die beim Aufruf schon
     * angemeldet prüft. Ohne das läse der zweite meist frisch, und der Fall
     * aus K-1 (ein Prozess mit gemerktem Stand) käme nicht vor; die
     * Gegenprobe ohne Sperre blieb so grün. Sicher für die Anlage:
     * Kein Häppchen läuft (die Jobs stehen still), unter B liegt also noch
     * nichts, und A steht in der Stand-Datei. */
    $kindSkript = sys_get_temp_dir() . '/swp-kind-' . bin2hex(random_bytes(4)) . '.php';
    file_put_contents($kindSkript, '<?php' . "\n" . 'declare(strict_types=1);
$w = $argv[1]; $start = (float)$argv[2];
require $w . "/server/db.php";
require $w . "/server/serverkrypto_lib.php";
serverschluessel_zustand(true);
while (microtime(true) < $start) { usleep(500); }
[$ok, $was] = serverschluessel_wechseln(true);
echo json_encode(["ok" => $ok, "was" => $was]);
');
    $start = microtime(true) + 2.0;
    $kinder = []; $rohre = [];
    for ($i = 0; $i < 2; $i++) {
        /* PHP_BINARY, nicht `php` aus dem Suchpfad (Nachprüfung H-SR-06,
         * F-SR-84): Ist das eine andere Hauptfassung, scheitern beide Kinder,
         * und der Hauptlauf wechselte nachher selbst. */
        $kinder[$i] = proc_open([PHP_BINARY, $kindSkript, $wurzel, sprintf('%.6F', $start)],
                                [1 => ['pipe', 'w'], 2 => ['pipe', 'w']], $rohre[$i]);
    }
    $erg = [];
    foreach ($kinder as $i => $kind) {
        $aus = (string)stream_get_contents($rohre[$i][1]);
        $err = (string)stream_get_contents($rohre[$i][2]);
        fclose($rohre[$i][1]); fclose($rohre[$i][2]);
        proc_close($kind);
        $j = json_decode($aus, true);
        $erg[] = is_array($j) ? $j : ['ok' => false, 'was' => 'Kind ohne Antwort: ' . trim($aus . ' ' . $err)];
    }
    @unlink($kindSkript);
    config_gemerktes_verwerfen();
    $B = strtolower((string)konfig('server_key', ''));
    /* NUR EIN WERT, DER NICHT A IST, IST B (Nachprüfung H-SR-06, F-SR-84). */
    if ($B !== $A && preg_match('/^[0-9a-f]{64}$/', $B) === 1) { $stand['b_hex'] = $B; $merken(); }
    $durch = array_values(array_filter($erg, static fn(array $e): bool => $e['ok'] === true));
    $Bkenn = (string)($durch[0]['was'] ?? '');
    $ok = count($durch) === 1;
    pruefe($ok && (string)schluessel_kennung($B) === $Bkenn
        && strtolower((string)konfig('server_key_alt', '')) === $A,
        'zwei Wechsel gleichzeitig: genau einer durch, A als bisheriger, B ist der des Siegers',
        implode(' | ', array_map(static fn(array $e): string => ($e['ok'] ? 'durch ' : 'abgewiesen: ')
                                 . mb_substr((string)$e['was'], 0, 70), $erg)));
    /* Kam KEINER durch, misst der Rest nichts — und ein dritter Wechsel
     * hier ginge durch und hätte keinen Platz in der Stand-Datei. */
    if ($durch === []) {
        throw new RuntimeException('Keiner der zwei Wechsel kam durch — die Probe hält an.');
    }
    $z = serverschluessel_zustand(true);
    pruefe($ok && $z['stand'] === 'rotation' && $z['kennung'] === $Bkenn && $z['kennung_alt'] === $Akenn
        && app_state_lesen('server_key_kennung') === $Bkenn
        && strtolower((string)konfig('server_key_alt', '')) === $A,
        'Lage rotation, neu B, bisher A, Marke auf B, server_key_alt = A', "B $Bkenn");
    $vorDritt = hash_file('sha256', $pfadKonfig);
    [$ok3, $was3] = serverschluessel_wechseln(true);
    pruefe(!$ok3 && str_contains($was3, 'bereits') && hash_file('sha256', $pfadKonfig) === $vorDritt,
        'ein weiterer Wechsel während des Wechsels: abgewiesen, config.php unverändert', $was3);
    pruefe(app_state_lesen(BLATT_BESTAETIGT_K) === null && blatt_neu_weil() === 'serverschluessel',
        'Blatt-Marke gelöscht, Grund „serverschluessel" (Nr. 233, E-SR-11)');
    $prot = static fn(string $art): int => (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
        WHERE id > {$stand['max_prot']} AND art = '$art'")->fetchColumn();
    $post = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
        WHERE id > {$stand['max_mail']} AND schluessel = 'serverschluessel_gewechselt'")->fetchColumn();
    pruefe($prot('serverschluessel_gewechselt') === 1, 'Protokoll: serverschluessel_gewechselt, einmal');
    /* OHNE SMTP NICHT GEMESSEN — und damit rot (H-SR-06, F-SR-83). Bis Web
     * 21.12.0 hieß es dann „ok": eine grüne Zahl ohne Gegenstand. */
    pruefe(smtp_eingerichtet() && $post() === $betreiberinnen,
        'Mail an jede BetreiberIn mit Passwort (E-SR-62)',
        smtp_eingerichtet() ? $post() . " von $betreiberinnen" : 'nicht gemessen — SMTP nicht eingerichtet');
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
     * revalidate_freq (F-SR-31): drei Sekunden warten. Die Sitzung stammt
     * aus Teil 3. */
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
        && str_contains($karte['rumpf'], 'Umhüllung') && str_contains($karte['rumpf'], SWP_KNOPF_WEITER)
        && !str_contains($karte['rumpf'], 'Alten Schlüssel entfernen')
        && !str_contains($karte['rumpf'], 'Server-Anteil wechseln'),
        'Karte: Wechsel läuft, Umhüllung, „Jetzt weiterarbeiten", kein Entfernen, kein Anteil-Wechsel',
        "HTTP {$karte['code']}");

    /* ---- 5. Häppchen -------------------------------------------------------- */
    teil('5. Häppchen, Wiederanlauf, Nachweis');
    $mit = static function (?string $paket, string $zweck): ?string {
        $r = $paket === null ? null : sk_oeffnen_mit_wem($paket, $zweck);
        return $r['mit'] ?? null;
    };
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
    $vorab = sw_archiv($archivAlt, $Bkenn, false);
    file_put_contents(protokoll_archiv_wurzel() . '/' . $archivAlt, $archivKopie);
    $archivNeuSumme = hash_file('sha256', protokoll_archiv_wurzel() . '/' . protokoll_archiv_name($archivVon, $Bkenn));

    /* EIN STÜCK, DAS BEIM UMHÜLLEN WIRFT (F-SR-81): An der Stelle der
     * Nebendatei der Begleitdatei liegt ein Ordner mit einem Unterordner —
     * `sw_arbeit_leeren()` räumt ihn nicht, und das Schreiben scheitert. Ein
     * Rechte-Trick ginge nicht: Die Probe läuft im Container als root. */
    $sperre = sw_arbeit() . '/' . $kennung . '-konto.json';
    @mkdir($sperre . '/x', 0770, true);
    $stand['eigenes']['ordner_weg'][] = $sperre; $merken();
    /* EIN ARCHIV, DESSEN TEIL SICH NICHT IN DEN ARBEITSORDNER SCHREIBEN LÄSST
     * (Nachprüfung H-SR-06, F-SR-85): An der Stelle des ersten Teils liegt ein
     * Ordner mit einer Datei. Bis zur Nachprüfung hieß das Archiv dann
     * „mit keinem der beiden zu öffnen", und der Nachweis kreiste. Das mit
     * der fremden Kennung im Namen, weil das andere schon umgehüllt liegt
     * (Archiv-Wiederanlauf oben). */
    $archivSperre = sw_arbeit() . '/' . protokoll_archiv_name($archivVon3, $Bkenn) . '.d';
    @mkdir($archivSperre . '/0', 0770, true);
    file_put_contents($archivSperre . '/0/x', 'x');
    $stand['eigenes']['ordner_weg'][] = $archivSperre; $merken();
    $archivDrittName = sw_stueck_name('archive', $archivDritt);
    for ($i = 0; $i < 40; $i++) {
        $e = sw_jetzt(30.0);
        if (($e['fehler'] ?? null) !== null) { break; }
        if (sw_nachweis_wartet(sw_zustand()) !== null || (sw_zustand()['phase'] ?? '') === 'fertig') { break; }
        if (isset($e['uebersprungen'])) { sleep(2); }
    }
    $zz = sw_zustand();
    $wartet = sw_nachweis_wartet($zz);
    $fehlerNamen = array_column(array_values((array)($zz['fehler'] ?? [])), 'name');
    $begleitName = sw_stueck_name('konten', $kennung . '/konto.json');
    $bed = sw_bedingungen();
    pruefe(($e['fehler'] ?? null) === null && $wartet !== null && $wartet > time() + 500
        && !$bed['inventar'] && str_contains(implode(' ', $bed['fehlt']), 'beginnt um'),
        'umgehüllt — der Nachweis wartet die Frist ab (E-SR-73), Bedingung 1 zu',
        'Phase ' . ($zz['phase'] ?? '?') . ', wartet bis ' . ($wartet !== null ? gmdate('H:i:s', $wartet) : '—')
        . ($e['fehler'] ?? ''));
    $sysZeilen = static fn(): int => (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse
        WHERE id > {$stand['max_prot']} AND reiter = 'system' AND text LIKE 'schluesselwechsel:%nicht umhüllen%'")->fetchColumn();
    pruefe(in_array($begleitName, $fehlerNamen, true)
        && $mit((string)file_get_contents(edbak_ordner($kennung) . '/konto.json'), edbak_begleit_zweck($kennung)) === 'alt'
        && str_contains(implode(' ', $bed['fehlt']), 'ließen sich nicht umhüllen') && $sysZeilen() === 2,
        'ein Stück, das wirft: genannt, liegt noch unter A, einmal im Reiter System (F-SR-81)',
        implode(' · ', $fehlerNamen) . ' · System ' . $sysZeilen());
    pruefe(in_array($archivDrittName, $fehlerNamen, true)
        && !in_array($archivDrittName, (array)($zz['verloren'] ?? []), true)
        && is_file(protokoll_archiv_wurzel() . '/' . $archivDritt),
        'ein Archiv, das sich nicht in den Arbeitsordner schreiben lässt: in der Fehlerliste, '
        . 'nicht „mit keinem der beiden" (F-SR-85)',
        'Fehler ' . count($fehlerNamen) . ', verloren: ' . implode(' · ', (array)($zz['verloren'] ?? [])));
    $karte = hole('betrieb_server.php', $sitz);
    pruefe($karte['code'] === 200 && str_contains($karte['rumpf'], 'Nachweis ab')
        && str_contains($karte['rumpf'], 'Ließen sich nicht umhüllen')
        && !str_contains($karte['rumpf'], SWP_KNOPF_WEITER),
        'Karte: „Nachweis ab …", die Zeile mit dem Stück, kein „Jetzt weiterarbeiten" während der Frist',
        "HTTP {$karte['code']}");
    swp_frist_kuerzen();
    for ($i = 0; $i < 3; $i++) { $e = sw_jetzt(30.0); }
    $zz = sw_zustand();
    pruefe(($zz['phase'] ?? '') !== 'fertig' && count((array)($zz['fehler'] ?? [])) === 2
        && !sw_bedingungen()['inventar'] && $sysZeilen() === 2,
        'nach der Frist: das Stück hält den Wechsel offen, ohne den Reiter System zu füllen',
        'Phase ' . ($zz['phase'] ?? '?') . ', System ' . $sysZeilen());
    @rmdir($sperre . '/x'); @rmdir($sperre);
    @unlink($archivSperre . '/0/x'); @rmdir($archivSperre . '/0'); @rmdir($archivSperre);
    for ($i = 0; $i < 40 && (sw_zustand()['phase'] ?? '') !== 'fertig'; $i++) {
        $e = sw_jetzt(30.0);
        if (($e['fehler'] ?? null) !== null) { break; }
        swp_frist_kuerzen();
        if (isset($e['uebersprungen'])) { sleep(2); }
    }
    $zz = sw_zustand();
    pruefe(($zz['phase'] ?? '') === 'fertig' && ($e['fehler'] ?? null) === null
        && count(glob(sw_arbeit() . '/*') ?: []) === 0 && (array)($zz['fehler'] ?? []) === [],
        'Sperre fort: bis fertig umgehüllt und nachgewiesen, Arbeitsordner leer, keine Fehler mehr',
        'Phase ' . ($zz['phase'] ?? '?') . ', Arbeitsordner ' . count(glob(sw_arbeit() . '/*') ?: [])
        . ', Fehler ' . count((array)($zz['fehler'] ?? [])) . ', umgehüllt ' . (int)($zz['umgehuellt'] ?? 0)
        . ', Nachweis ' . json_encode($zz['zahlen'] ?? []) . ($e['fehler'] ?? ''));
    pruefe($prot('serverschluessel_umgehuellt') === 1, 'Protokoll: serverschluessel_umgehuellt, einmal');
    pruefe(($zz['komplett_auftrag'] ?? null) === 'vorgemerkt' && (komp_zustand()['stand'] ?? null) === 'dump',
        'nach dem Nachweis ist ein Komplett-Stand vorgemerkt (Q-SR-03), nicht vorher',
        (string)($zz['komplett_auftrag'] ?? '—') . ', Auftrag ' . (string)(komp_zustand()['stand'] ?? '—'));

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
    pruefe(in_array('Konto-Backup ' . $paket3, $verl, true) && ($zz['phase'] ?? '') === 'fertig'
        && hash_file('sha256', edbak_ordner($kennung) . '/' . $paket3) === $paket3Summe,
        'Paket mit kaputtem Teil: verloren genannt, unangetastet — und der Wechsel wurde trotzdem fertig (F-SR-81)');
    $archivDrittNeu = protokoll_archiv_name($archivVon3, $Bkenn);
    $md = protokoll_archiv_manifest($archivDrittNeu);
    pruefe(!is_file(protokoll_archiv_wurzel() . '/' . $archivDritt) && $md !== null
        && ($md['kennung'] ?? null) === $Bkenn
        && sk_oeffnen((string)$zipTeil(protokoll_archiv_wurzel() . '/' . $archivDrittNeu, 'probe.jsonl.sk'),
                      protokoll_archiv_zweck($archivDrittNeu, 'probe.jsonl')) === $archivTeil,
        'Archiv mit fremder Kennung im Namen, unter A versiegelt: umgehüllt und umbenannt (F-SR-81)', $archivDrittNeu);
    $zeilenObjekt = (string)zip_eintrag(protokoll_archiv_wurzel() . '/' . $archivNeu, 'manifest.json.sk');
    $mj = (string)sk_oeffnen($zeilenObjekt, protokoll_archiv_zweck($archivNeu, 'manifest.json'));
    pruefe(str_contains($mj, '"gekuerzt": {}') && str_contains($mj, '"zeilen": {}'),
        'Archiv-Manifest: leere „zeilen"/„gekuerzt" bleiben Objekte (F-SR-81)');

    /* Der halb versiegelte Komplett-Stand — weiter mit dem, was ein Häppchen
     * von `komp_schub_lauf()` tut. */
    $neuBegonnen = komp_kopf_angleichen($kz, (string)schluessel_kennung(bin2hex((string)serverschluessel())));
    komp_siegel_schub("$kompBau/roh", "$kompBau/ziel.edk", (string)serverschluessel(), $kz['kopfzeile'], $kz,
                      static fn(): float => 100.0, 1.0);
    $klarZurueck = '';
    try {
        komp_oeffnen("$kompBau/ziel.edk", (string)serverschluessel(),
                     static function (string $k) use (&$klarZurueck): void { $klarZurueck .= $k; });
    } catch (Throwable $ex) {
        $klarZurueck = 'Fehler: ' . $ex->getMessage();
    }
    pruefe($neuBegonnen && (komp_kopf_lesen("$kompBau/ziel.edk")['kopf']['kennung'] ?? null) === $Bkenn
        && komp_serverschluessel_fuer("$kompBau/ziel.edk")[1] === 'neu' && hash_equals($kompRoh, $klarZurueck),
        'Komplett-Stand über den Wechsel: neu versiegelt, Kopf mit B, ganz unter B, derselbe Inhalt (E-SR-72)',
        $neuBegonnen ? 'neu begonnen' : 'NICHT neu begonnen');
    $bed = sw_bedingungen();
    pruefe($bed['inventar'] && !$bed['komplett'] && !$bed['blatt'] && !$bed['alle'],
        'Bedingungen: Inventar ja, frischer Stand nein, Rückfrage nein', json_encode($bed['fehlt']));
    /* NACH DEM EINSPIELEN EINES DUMPS gilt der mitgebrachte Zustand nicht als
     * Nachweis (F-SR-80): `wiederherstellen.php` ruft dieselbe Funktion. */
    $beginnVorher = (string)($zz['begonnen'] ?? '');
    sw_nach_einspielen($pdo);
    $ze = sw_zustand();
    /* Die Liste „mit keinem der beiden" des Dumps gilt hier nicht — der
     * Nachweis baut sie aus den hiesigen Stücken neu (Nachprüfung H-SR-06,
     * F-SR-85). */
    $nachEinspielen = ($ze['phase'] ?? '') === 'nachweis'
        && array_key_exists('cursor', $ze) && $ze['cursor'] === null
        && !isset($ze['nachweis_am']) && ($ze['begonnen'] ?? '') === $beginnVorher && !sw_bedingungen()['inventar']
        && (array)($ze['verloren'] ?? null) === [];
    for ($i = 0; $i < 20 && (sw_zustand()['phase'] ?? '') !== 'fertig'; $i++) { sw_jetzt(30.0); }
    $verlNeu = (array)(sw_zustand()['verloren'] ?? []);
    pruefe($nachEinspielen && (sw_zustand()['phase'] ?? '') === 'fertig' && sw_bedingungen()['inventar']
        && in_array('Zweitfaktor-Geheimnis des Kontos Nr. ' . $uidFremd, $verlNeu, true),
        'nach dem Einspielen: Nachweis von vorn, Beginn bleibt, „mit keinem der beiden" neu gezählt — '
        . 'dann wieder nachgewiesen (F-SR-80, F-SR-85)',
        'zurückgesetzt ' . ($nachEinspielen ? 'ja' : 'NEIN') . ', danach ' . (string)(sw_zustand()['phase'] ?? '?')
        . ', verloren ' . count($verlNeu));

    /* EIN NACHWEIS-DURCHGANG OHNE STÜCK (Nachprüfung H-SR-06, F-SR-85): Der
     * Zeiger steht hinter dem letzten Archiv — so sieht ein Durchgang auf
     * einer Anlage ohne Ziel, Zweitfaktor, Konto-Backup und Archiv aus. Bis
     * zur Nachprüfung blieb `nachweis_begonnen` dann leer: Jedes Häppchen hielt
     * den Zustand für einen aus 21.12.0, wiederholte ihn und schrieb erneut
     * „umgehüllt" ins Protokoll; Bedingung 1 ging nie auf. */
    $zl = sw_zustand();
    sw_zustand_setzen(array_merge($zl, ['phase' => 'nachweis', 'zweck' => SW_ZWECKE[count(SW_ZWECKE) - 1],
        'cursor' => "\u{10FFFF}", 'nachweis_begonnen' => null,
        'nachweis_ab' => gmdate('Y-m-d H:i:s', time() - 60), 'nachweis_alt' => 0, 'nachweis_fehler' => 0]));
    $protVor = $prot('serverschluessel_umgehuellt');
    sw_jetzt(30.0);
    $zl1 = sw_zustand();
    sw_jetzt(30.0);
    $zl2 = sw_zustand();
    pruefe(($zl1['phase'] ?? '') === 'fertig' && ($zl1['nachweis_begonnen'] ?? null) !== null
        && ($zl2['phase'] ?? '') === 'fertig' && $prot('serverschluessel_umgehuellt') === $protVor + 1
        && sw_bedingungen()['inventar'],
        'ein Nachweis ohne Stück: einmal fertig, Bedingung 1 offen, kein zweites „umgehüllt" (F-SR-85)',
        'Protokoll +' . ($prot('serverschluessel_umgehuellt') - $protVor) . ', Beginn '
        . (($zl1['nachweis_begonnen'] ?? null) ?? 'leer'));

    /* LAGE „ABWEICHEND" MITTEN IM WECHSEL (F-SR-85): eine Marke aus einem
     * älteren Dump. Das Häppchen lässt den Zustand stehen; nach der
     * Reparatur geht es weiter, ohne einen zweiten Beginn. */
    $gewVor = $prot('serverschluessel_gewechselt');
    $zAb = sw_zustand();
    app_state_setzen('server_key_kennung', 'deadbeef');
    serverschluessel_zustand(true);
    $lageAb = serverschluessel_zustand(true)['stand'];
    sw_jetzt(30.0);
    $zAb2 = sw_zustand();
    app_state_setzen('server_key_kennung', $Bkenn);
    serverschluessel_zustand(true);
    sw_jetzt(30.0);
    pruefe($lageAb === 'abweichend' && ($zAb2['begonnen'] ?? null) === ($zAb['begonnen'] ?? 'x')
        && (sw_zustand()['begonnen'] ?? null) === ($zAb['begonnen'] ?? 'x')
        && $prot('serverschluessel_gewechselt') === $gewVor,
        'Lage „abweichend" im Wechsel: Zustand bleibt, nach der Reparatur kein zweiter Beginn (F-SR-85)',
        "Lage $lageAb, Beginn " . (($zAb2['begonnen'] ?? null) === ($zAb['begonnen'] ?? 'x') ? 'gleich' : 'NEU'));

    /* EIN ZUSTAND EINES ANDEREN SCHLÜSSELPAARS IN DER PHASE „fertig"
     * (Nachprüfung H-SR-06, F-SR-87; gemessen seit der Nachmessung): Die Zeile
     * „Umhüllung" sagt „nicht begonnen … oder mit Jetzt weiterarbeiten" — dann
     * muss der Knopf auch dastehen. Bis zur Nachprüfung hing er am rohen
     * Zustand und fehlte. Die Jobs stehen still; kein Häppchen fasst den
     * Zustand während des Abrufs an. */
    $zK = sw_zustand();
    sw_zustand_setzen(array_merge($zK, ['kennung_neu' => 'deadbeef', 'phase' => 'fertig']));
    try {
        $karteK = hole('betrieb_server.php', $sitz);
    } finally {
        sw_zustand_setzen($zK);
    }
    /* An der Zeile „Umhüllung" selbst — ihr Kleintext und ihre Plakette.
     * „nicht begonnen" allein stünde auch in der Zeile „Bevor der bisherige
     * gehen darf" (aus `sw_bedingungen()`), und eine Zeile „Umhüllung" am
     * rohen Zustand bliebe grün (Gegenprüfung der Nachmessung). */
    $plaketteUmh = preg_match('/Umhüllung<\/span>.*?zeile-plaketten">(.*?)<\/div>/su', $karteK['rumpf'], $mU)
        ? trim(strip_tags($mU[1])) : '';
    pruefe($karteK['code'] === 200 && str_contains($karteK['rumpf'], 'die Umhüllung hat noch nicht begonnen')
        && $plaketteUmh === 'nicht begonnen'
        && str_contains($karteK['rumpf'], SWP_KNOPF_WEITER),
        'Karte: Zustand eines anderen Schlüsselpaars („fertig") — „nicht begonnen" und der Knopf dazu (F-SR-87)',
        "HTTP {$karteK['code']}, Plakette „{$plaketteUmh}\"");

    /* F-SR-88 FEST (Nachmessung zu H-SR-06): zwei Auskünfte, die bis dahin nur
     * eine einmalige Messung mit Verfälschung belegte — ein Rückbau bliebe
     * sonst in jedem Prüfmittel grün (Gegenprüfung der Nachmessung). Gesetzt
     * wird nur der Zustand, die Jobs stehen still. (1) Zwanzig Stücke in der
     * Liste, drei laufend darüber, der erste Durchgang noch nicht durch.
     * (2) Ungezählt — das Inventar warf beim Beginn. */
    $zF = sw_zustand();
    $liste20 = [];
    for ($fi = 0; $fi < SW_VERLOREN_MAX; $fi++) {
        $liste20['konten|probe/' . $fi] = ['name' => 'Probestück ' . $fi, 'grund' => 'Probe'];
    }
    sw_zustand_setzen(array_merge($zF, ['phase' => 'umhuellen', 'fehler' => $liste20, 'fehler_mehr' => 0,
                                        'fehler_mehr_lauf' => 3, 'nachweis_begonnen' => null]));
    try {
        $karteF1 = hole('betrieb_server.php', $sitz);
    } finally {
        sw_zustand_setzen($zF);
    }
    sw_zustand_setzen(array_merge($zF, ['phase' => 'umhuellen', 'gesamt' => null, 'fehler' => [],
                                        'nachweis_begonnen' => null]));
    try {
        $karteF2 = hole('betrieb_server.php', $sitz);
        $statusF2 = hole('betrieb_status.php', $sitz);
    } finally {
        sw_zustand_setzen($zF);
    }
    $plaketteF1 = preg_match('/Ließen sich nicht umhüllen<\/span>.*?zeile-plaketten">(.*?)<\/div>/su', $karteF1['rumpf'], $mF1)
        ? trim(strip_tags($mF1[1])) : '';
    $plaketteF2 = preg_match('/Umhüllung<\/span>.*?zeile-plaketten">(.*?)<\/div>/su', $karteF2['rumpf'], $mF2)
        ? trim(strip_tags($mF2[1])) : '';
    pruefe($karteF1['code'] === 200 && str_contains($karteF1['rumpf'], '· und 3 weitere') && $plaketteF1 === '23',
        'Karte über der Decke mitten im ersten Durchgang: „und 3 weitere", Plakette 23 (F-SR-88)',
        "HTTP {$karteF1['code']}, Plakette „{$plaketteF1}\"");
    pruefe($karteF2['code'] === 200 && str_contains($karteF2['rumpf'], 'die Zahl der Stücke zählt das erste Häppchen')
        && $plaketteF2 === 'wird gezählt' && !str_contains($karteF2['rumpf'], 'noch 0 von 0')
        && str_contains($statusF2['rumpf'], 'die Zahl der Stücke zählt das erste Häppchen'),
        'Karte und Statuszeile ungezählt: „wird gezählt", nicht „noch 0 von 0" (F-SR-88)',
        "HTTP {$karteF2['code']}/{$statusF2['code']}, Plakette „{$plaketteF2}\"");

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
    $postAb = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
        WHERE id > {$stand['max_mail']} AND schluessel = 'serverschluessel_abgeschlossen'")->fetchColumn();
    $offenBeginn = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
        WHERE id > {$stand['max_mail']} AND schluessel = 'serverschluessel_gewechselt'
          AND zustand = 'ueberholt'")->fetchColumn();
    pruefe(smtp_eingerichtet() && $postAb === $betreiberinnen && $post() === $betreiberinnen && $offenBeginn === 0,
        'Mail zum Abschluss an jede BetreiberIn, eigener Schlüssel — keine Beginn-Mail überholt (F-SR-82)',
        smtp_eingerichtet() ? "Abschluss $postAb, Beginn " . $post() . " von $betreiberinnen, überholt $offenBeginn"
                            : 'nicht gemessen — SMTP nicht eingerichtet');
    pruefe(totp_geheimnis($uid) === $totpKlar && edbak_paket_teil_lesen($kennung, $paket, 'kern.json') === $teilKlar['kern.json']
        && protokoll_archiv_manifest($archivNeu) !== null
        && komp_serverschluessel_fuer(komp_wurzel() . '/' . $standB)[1] === 'neu',
        'nach dem Entfernen öffnet alles mit B');
    pruefe(komp_serverschluessel_fuer(komp_wurzel() . '/' . $standA)[1] === 'keiner'
        && komp_erster_block_oeffnet(komp_wurzel() . '/' . $standA, (string)hex2bin($A)),
        'der alte Stand: mit keinem Schlüssel der Anlage — mit dem Wert A vom Blatt schon');
    /* … UND SO STEHT ER IN DER LISTE (F-SR-82, F-SR-87; bis zur Nachmessung
     * gelesen). Gelesen wird die Zeile des Stands bis zur nächsten Zeile oder
     * zum Ende der Karte — dort stünde ein Passphrase-Formular. OPcache sieht
     * die config.php ohne `server_key_alt` erst nach revalidate_freq
     * (F-SR-31): Ohne die drei Sekunden hieß der Stand dort noch „bisheriger
     * Schlüssel", und der Download ging durch (Nachmessung, erster Lauf). */
    sleep(3);
    $liste = hole('admin_komplettsicherung.php', $sitz);
    $zeileA = '';
    $posA = strpos($liste['rumpf'], $standA . ' · ');
    if ($posA !== false) {
        $enden = array_filter([strpos($liste['rumpf'], 'zeile-haupt', $posA),
                               strpos($liste['rumpf'], '</section>', $posA),
                               strpos($liste['rumpf'], '</details>', $posA)], static fn($x): bool => $x !== false);
        $zeileA = substr($liste['rumpf'], $posA, ($enden === [] ? strlen($liste['rumpf']) : min($enden)) - $posA);
    }
    pruefe($liste['code'] === 200
        && str_contains($zeileA, ' · öffnet nur mit dem Serverschlüssel vom Blatt, das damals galt')
        && str_contains($zeileA, 'anderer Schlüssel')
        && !str_contains($zeileA, 'Herunterladen') && !str_contains($zeileA, 'name="passphrase"')
        && !str_contains($zeileA, 'feld-hinweis'),
        'Liste der Komplett-Stände: der Stand unter drittem Schlüssel — ein Glied der Kleinzeile, '
        . 'kein Herunterladen, keine Passphrase, kein Absatz (F-SR-82, F-SR-87)',
        "HTTP {$liste['code']}" . ($zeileA === '' ? ' — Zeile nicht gefunden' : ', ' . strlen($zeileA) . ' Zeichen gelesen'));
    $dlVor = $prot('komplett_heruntergeladen');
    $dl = hole('admin_komplettsicherung.php', $sitz, ['action' => 'herunterladen', 'art' => 'klar', 'datei' => $standA]);
    pruefe($dl['code'] === 200 && str_contains($dl['rumpf'], 'Es wurde nichts heruntergeladen')
        && $prot('komplett_heruntergeladen') === $dlVor,
        'Download dieses Stands: abgewiesen, ohne Eintrag „heruntergeladen" (F-SR-82)',
        "HTTP {$dl['code']}, Einträge " . ($prot('komplett_heruntergeladen') - $dlVor));

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
    $h = $r['hand'];
    pruefe(is_array($h) && $h['protokoll'] === 1 && $h['blatt'] === true && $h['weg'] === 'hand'
        && smtp_eingerichtet() && $h['mails'] === $h['betreiberinnen']
        && $h['erledigt'] === 0 && $h['versucht'] === 0,
        'der Rückweg steht von Hand in config.php: der Job beginnt ihn wie einen Wechsel — Protokoll, '
        . 'Blatt fällig, Mail für den Mailjob; das Häppchen endet nach dem Beginn (E-SR-74, F-SR-85)',
        is_array($h) ? "Protokoll {$h['protokoll']}, Blatt " . ($h['blatt'] ? 'fällig' : 'NICHT fällig')
                       . ", Mails {$h['mails']} von {$h['betreiberinnen']}, versucht {$h['versucht']}"
                       . ", erledigt {$h['erledigt']}"
                       . (smtp_eingerichtet() ? '' : ' — nicht gemessen, SMTP nicht eingerichtet')
                     : 'nicht gemessen — der Rückweg setzte einen Zustand fort');
    if ($r['fehler'] !== null) {
        echo "  Rückweg NICHT FERTIG — config.php trägt A und B, die Stand-Datei bleibt; "
           . ($r['unter_b'] ?? '?') . " Stück(e) unter B. Erneut mit --zurueck.\n";
    }
    pruefe(hash('sha256', (string)file_get_contents($pfadKonfig)) === hash('sha256', (string)base64_decode($stand['konfig_bytes']))
        && serverschluessel_zustand(true)['stand'] === 'bereit' && serverschluessel_kennung() === $Akenn,
        'config.php byte-gleich, Lage bereit mit A');
    sleep(3);   // OPcache der Anlage sieht die alte config.php wieder (F-SR-31)
}

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
