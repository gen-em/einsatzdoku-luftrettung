<?php
declare(strict_types=1);

/**
 * Rollenprobe — erreicht jede Rolle genau das, was die Berechtigungsmatrix
 * sagt? (P5c/AP2, E-P5c-22)
 *
 * Anlass: Nr. 286 (ein Konto mit der Rolle admin erreichte Komplett-Backup
 * und Backup-Ziele — samt Klartext-Dump) und Nr. 149. Beide standen im Code,
 * und keine Prüfung fragte je eine Seite mit einer ANDEREN Rolle als der
 * BetreiberIn ab.
 *
 * DIE MATRIX STEHT NICHT HIER, SONDERN IN `docs/Technik.md` 4.99p, zwischen
 * `<!-- rollenprobe:anfang -->` und `<!-- rollenprobe:ende -->`. Die Probe
 * liest sie von dort. Zwei Listen derselben Sache — eine im Dokument, eine im
 * Werkzeug — laufen auseinander, sobald jemand eine Zeile an einer Stelle
 * ergänzt; hier gibt es nur die eine, und sie ist zugleich Dokumentation und
 * Vorgabe.
 *
 * WIE EINE ZELLE GEMESSEN WIRD.
 *   - GET: der Statuscode (200, 303, 404). 403 heißt zusätzlich: Der Text ist
 *     der des Rollentors („Kein Zugriff" oder „… vorbehalten") — ein 403 aus
 *     einem anderen Grund zählte sonst als Tor. Bis AP4 stand dieser Satz
 *     hier, ohne dass `messen()` den Text las (F-P5c-101).
 *   - POST: mit einem ABSICHTLICH FALSCHEN Formular-Token. `403` heißt: das
 *     Rollentor antwortet. `durch` heißt: Die Antwort ist die
 *     Token-Ablehnung — die Rolle hat die Handlung erreicht, ausgeführt wird
 *     sie nicht. So läuft die Probe gefahrlos über „Stand löschen",
 *     „jetzt versenden" und „jetzt sichern".
 *
 * DREI PLATZHALTER (AP4, der dritte seit R4-18): `{ziel}` ist ein Konto der
 * Rolle `user`, `{admin}` eines der Rolle `admin`, `{support}` eines der
 * Rolle `support`. Die Kontoseite fragt die Rolle zweimal — ob die
 * Angemeldete das Zielkonto betreuen darf, dann je Handlung —, und beide
 * Tore stehen in der Matrix. `{support}` misst, dass der Support andere
 * Support-Konten nicht betreut (E-P5c-99); bis R4-18 stand das nur im Code
 * (Nr. 327).
 *
 * UND WIRKUNGEN, denn `durch` sagt nur, dass eine Rolle die Handlung
 * erreicht, nicht, was sie dort tut:
 *   - Die Verwaltung loescht ein Konto mit Spur ueber `konto_loeschen()`
 *     (R4-10, Backlog Nr. 299): Das Konto ist fort, die Spur auch, der
 *     Protokolleintrag nennt den Weg `verwaltung`, `mengen:<id>` in
 *     `app_state` ist abgeraeumt — das vergass die Abschrift auf der
 *     Kontoseite bis Web 21.1.7 — und die Sperrliste seines Geraets auch
 *     (die raeumte bis dahin nur das Entfernen des Demo-Kontos).
 *   - Ein Rollenwechsel auf der Kontoseite schreibt genau einen Eintrag
 *     `rolle_geaendert`, ein Speichern ohne Wechsel keinen (E-P5c-38).
 *   - Nach einer Handlung leiten die Seiten unter Verwaltung und Betrieb um
 *     (R4-11, Backlog Nr. 250): je Seite ein harmloser Zweig, 302 auf das
 *     genannte Ziel, die Meldung beim ersten GET einmal mehr als beim
 *     zweiten. Dazu die zwei Fehler der Backup-Ziele, die dabei auffielen
 *     (F-R4-35, -36).
 *   - Der Support (AP4, E-P5c-14, -40), mit GUELTIGEM Token: Menü, Liste
 *     ohne Konten mit Rechten, die Sicht aus M-P5c-02c und die
 *     Protokollreiter; der Setz-Link steht
 *     nicht in seiner Antwort, auch im Fehlfall nicht — und die Gegenprobe
 *     mit einem Admin zeigt ihn im selben Fall, sonst belegte das Fehlen
 *     nichts; ein Gerät geht aus, aber nicht wieder an und nicht weg; ein
 *     Konto bleibt; die Bestätigung einer Registrierung geht erneut hinaus,
 *     ohne Link in der Antwort.
 *
 * DIE KONTEN UND SITZUNGEN LEGT SIE SELBST AN (Muster Wartungsprobe; die
 * Anmeldung leitet das Token im Browser per PBKDF2 ab und ist mit `curl`
 * nicht nachzubilden). Seit P5c/AP5 tragen die Konten der Pflichtrollen
 * `totp_seit`, sonst führte das Einrichtungstor des Zweitfaktors jede ihrer
 * Anfragen auf `zweitfaktor.php` (Begründung bei „Konten" unten). Die beiden
 * Zeilen `totp_zuruecksetzen` der Matrix versteht die Probe ohne Sonderfall:
 * Das Tor der Handlung steht in `admin_user.php` vor `csrf_check()`, ein
 * Admin am Konto eines Admins bekommt also 403, am Konto einer NutzerIn die
 * Token-Ablehnung. Ein Konto `rollenprobe-*@probe.invalid` je Rolle, dazu
 * drei Zielkonten (`ziel`, `zieladmin`, `neu` im Status „unbestätigt") und
 * ein Gerät — nicht die Sandbox, und nicht vorhandene Konten: Eine Probe, die
 * von einem vorher angelegten Konto abhängt, ist nach `hochfahren.sh --neu`
 * rot, ohne gemessen zu haben (E-P5c-78). Aufgeräumt wird im Schluss, auch
 * nach einem Abbruch: Konten (Geräte und Setz-Token fallen mit), Sitzungs-
 * dateien, ihre Protokolleinträge, die Mails der Probe in der Warteschlange.
 *
 * API-ENDPUNKTE SEIT KONZEPT RW (RW-02): Ihre Token-Ablehnung ist JSON
 * (`{"error":"csrf"}`, `csrf_check()` in `auth_guard.php`), nicht die Seite
 * mit dem Satz — `messen()` erkennt beide. Der erste ist
 * `api/rueckweg_anlegen.php`, den alle vier Rollen erreichen; dazu die
 * Wirkung: Jede Rolle legt mit gültigem Formular- UND Anmelde-Token ein
 * Paar ab.
 *
 * WAS SIE NICHT PRÜFT: ob eine Handlung mit gültigem Token gelingt, außer
 * den Wirkungen oben (das tun Bedienwege und die Proben der Sache), und die
 * übrigen API-Endpunkte (eigene Tore, eigene Proben).
 *
 * Aufruf:
 *   php tools/proben/rollen/probe.php [basisadresse]   (Vorgabe http://127.0.0.1:8080)
 *
 * Rückgabewert: 0 = jede Zelle wie verlangt · 1 = Abweichung · 2 = Matrix
 * nicht lesbar.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/sitzung_lib.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo = db();

/* ---- Die Matrix lesen ------------------------------------------------------ */

$technik = (string)@file_get_contents($wurzel . '/docs/Technik.md');
if (!preg_match('/<!-- rollenprobe:anfang -->(.*?)<!-- rollenprobe:ende -->/s', $technik, $t)) {
    fwrite(STDERR, "Die Matrix steht nicht in docs/Technik.md (Markierungen fehlen).\n");
    exit(2);
}
$zeilen = [];
$spalten = null;
foreach (explode("\n", trim($t[1])) as $z) {
    $z = trim($z);
    if (!str_starts_with($z, '|') || preg_match('/^\|[-| ]+\|$/', $z)) { continue; }
    $zellen = array_map('trim', explode('|', trim($z, '|')));
    if ($spalten === null) { $spalten = $zellen; continue; }
    if (count($zellen) !== count($spalten)) {
        fwrite(STDERR, "Matrixzeile mit falscher Spaltenzahl: $z\n");
        exit(2);
    }
    $zeilen[] = array_combine($spalten, $zellen);
}
/* ROLLEN SIND DIE SPALTEN, DIE ROLLEN HEISSEN (seit SR-07). Die letzte Spalte
 * „frischer Code" ist keine Rolle, sondern eine Markierung: `ja` heisst, die
 * Handlung steht in `ZF_FRISCH_HANDLUNGEN` — der Teil „Frischer Code" unten
 * misst an genau diesen Zeilen den Umweg. */
$rollen = array_values(array_intersect(array_slice($spalten ?? [], 2), array_keys(ROLLEN)));
if ($zeilen === [] || $rollen === []) { fwrite(STDERR, "Die Matrix ist leer.\n"); exit(2); }
if (!in_array('frischer Code', $spalten, true)) {
    fwrite(STDERR, "Die Matrix hat keine Spalte „frischer Code\" (SR-07).\n");
    exit(2);
}

/* ---- HTTP und Sitzungen (Muster: tools/proben/wartung/) -------------------- */

/** `$json`: der Rumpf als JSON mit dem Formular-Token in `X-CSRF` — so,
 *  wie `EdApi.postJson()` es schickt. */
function hole(string $pfad, ?string $sid, ?array $koerper = null, ?string $json = null): array
{
    global $basis;
    $ch = curl_init("$basis/$pfad");
    $kopf = $sid !== null ? ['Cookie: PHPSESSID=' . $sid . bindung_keks($sid)] : [];
    if ($json !== null) { $kopf[] = 'Content-Type: application/json'; $kopf[] = 'X-CSRF: ' . $json; }
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $kopf,
        CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '']);
    if ($koerper !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS,
                    $json !== null ? (string)json_encode($koerper) : http_build_query($koerper));
    }
    /* `ziel` ist die Kopfzeile `Location` — seit R4-11 (Nr. 250) misst die
     * Probe, WOHIN eine Handlung umleitet, nicht nur, dass sie es tut. */
    $ziel = null;
    curl_setopt($ch, CURLOPT_HEADERFUNCTION, static function ($c, string $z) use (&$ziel): int {
        if (stripos($z, 'Location:') === 0) { $ziel = trim(substr($z, 9)); }
        return strlen($z);
    });
    $rumpf = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'rumpf' => $rumpf, 'ziel' => $ziel];
}

function sitzung_ort(): string
{
    $eigen = sitzung_ablage_pfad();
    return is_dir($eigen) ? $eigen : (string)(session_save_path() ?: sys_get_temp_dir());
}

/** Die Bindung je angelegter Sitzung — Kennung → Cookiewert (seit Web 21.7.0,
 *  Schritt 18, SR-01). Ohne sie beendet `auth_guard.php` jede Sitzung dieser
 *  Probe mit dem Grund `bindung`, und jede Zelle der Matrix stuende auf 200
 *  der Abmeldeseite statt auf dem Rollentor. */
$BINDUNGEN = [];

/** Der Anhang an den `Cookie:`-Kopf: `; EDBIND=…`, wenn die Sitzung eine hat. */
function bindung_keks(string $sid): string
{
    $w = $GLOBALS['BINDUNGEN'][$sid] ?? null;
    return $w !== null ? '; ' . SITZUNG_COOKIES['bindung']['name'] . '=' . $w : '';
}

/** Eine Sitzung, wie `login.php` sie hinterlässt — mit Bindung (SR-01).
 *  VOR jeder Ausgabe anlegen.
 *
 *  FRISCH ALS VORGABE (seit SR-07): `login.php` setzt nach dem Code-Schritt
 *  `zf_frisch_bis`, und so misst die Matrix das Rollentor und das Token —
 *  den Umweg ohne frischen Code misst der eigene Teil darunter, mit
 *  `$frisch = false`. */
function sitzung_anlegen(int $uid, int $epoch, bool $frisch = true): array
{
    $sid  = 'rollenprobe' . bin2hex(random_bytes(10));
    $csrf = bin2hex(random_bytes(16));
    $bind = bin2hex(random_bytes(32));
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    session_save_path(sitzung_ort());
    session_id($sid);
    session_start();
    $_SESSION = ['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(), 'csrf' => $csrf,
                 'bindung' => hash('sha256', $bind)]
              + ($frisch ? ['zf_frisch_bis' => time() + 3600] : []);
    session_write_close();
    $GLOBALS['BINDUNGEN'][$sid] = $bind;
    return ['sid' => $sid, 'csrf' => $csrf];
}

/* ---- Konten ------------------------------------------------------------------ */

/* DIE PFLICHTROLLEN BEKOMMEN `totp_seit` (P5c/AP5, E-P5c-43, F-P5c-33).
 *
 * Seit AP5 führt `auth_guard.php` Support, Admin und BetreiberIn ohne
 * Zweitfaktor auf `zweitfaktor.php` — jede Seite mit 302, die API mit 403.
 * Ohne diese Zeile mäße die Probe für drei von vier Spalten das
 * Einrichtungstor statt des Rollentors: Jede Zelle stünde auf
 * „unerwartet 302", und keine sagte etwas über die Matrix.
 *
 * DAS SCHALTET DIE PFLICHT NICHT AB. Das Tor fragt allein `totp_seit`, und
 * genau diesen Zustand hinterlässt eine abgeschlossene Einrichtung. Die
 * Sitzungen entstehen hier ohnehin von Hand, also hinter dem Code-Schritt
 * von `login.php` — so wie sie hinter der Passwortableitung entstehen. Ob
 * die Anmeldung den Code verlangt, ist nicht Gegenstand dieser Probe,
 * sondern der Zweitfaktorprobe. Ein Geheimnis braucht es dafür nicht: Die
 * Seiten dieser Probe fragen es nie ab, und `einstellungen.php` zeigt ohne
 * es nur einen Hinweis.
 *
 * WELCHE ROLLEN, SAGT `rolle_braucht_zweitfaktor()` — nicht eine Liste hier.
 * Wer die Menge in `db.php` ändert, ändert sie für die Probe mit. Ohne die
 * Spalte (vor `update.php`) schweigt das Tor, und die Zeile entfällt. */
$mitZweitfaktor = db_hat_spalte($pdo, 'users', 'totp_seit');

$konten = [];
foreach ($rollen as $rolle) {
    $mail = 'rollenprobe-' . $rolle . '@probe.invalid';
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
    $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, ?, ?, '', '', 320000)")->execute([$mail, 'Rollenprobe ' . $rolle, $rolle]);
    $id = (int)$pdo->lastInsertId();
    if ($mitZweitfaktor && rolle_braucht_zweitfaktor($rolle)) {
        $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP() WHERE id = ?')->execute([$id]);
    }
    $epoch = (int)$pdo->query('SELECT session_epoch FROM users WHERE id = ' . $id)->fetchColumn();
    /* ZWEI SITZUNGEN JE ROLLE, beide jetzt — `session_start()` geht nur vor
     * der ersten Ausgabe: die frische fuer die Matrix, die unfrische fuer den
     * Teil „Frischer Code" (SR-07). Die erste Fassung legte die zweite erst
     * dort an, nach der Ausgabe der Matrix; jede Anfrage landete auf
     * `login.php`. */
    $konten[$rolle] = ['id' => $id, 'mail' => $mail] + sitzung_anlegen($id, $epoch)
                    + ['unfrisch' => sitzung_anlegen($id, $epoch, false)];
}
/* Die Zielkonten — eigene Zeilen, nicht die Konten der Rollen: `ziel` für
 * den Rollenwechsel und die Handlungen an einem Konto der Rolle `user`,
 * `zieladmin` für den Platzhalter `{admin}`, `zielsupport` für `{support}`
 * (R4-18), `neu` für die Bestätigung einer Registrierung. */
function zielkonto(string $name, string $rolle, string $status = 'aktiv'): int
{
    global $pdo;
    $mail = 'rollenprobe-' . $name . '@probe.invalid';
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
    $pdo->prepare("INSERT INTO users (email, name, role, status, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, ?, ?, ?, '', '', 320000)")
        ->execute([$mail, 'Rollenprobe ' . $name, $rolle, $status]);
    return (int)$pdo->lastInsertId();
}
$zielMail = 'rollenprobe-ziel@probe.invalid';
$zielId   = zielkonto('ziel', 'user');
$adminZiel = zielkonto('zieladmin', 'admin');
$supportZiel = zielkonto('zielsupport', 'support');
$neuId    = zielkonto('neu', 'user', 'unbestaetigt');
$pdo->prepare("INSERT INTO devices (user_id, device_id, api_key_hash, label, active)
               VALUES (?, ?, '', 'Rollenprobe', 1)")
    ->execute([$zielId, 'rollenprobe-' . bin2hex(random_bytes(6))]);
$geraetId = (int)$pdo->lastInsertId();
/* Die Mails der Probe erkennt der Schluss an der Nummer: Nach dem Versuch
 * leert die Warteschlange die Adresse, eine Suche nach ihr fände nichts. */
try {
    $mailVorher = (int)$pdo->query('SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange')->fetchColumn();
} catch (Throwable) { $mailVorher = null; }
$platzhalter = ['{ziel}' => (string)$zielId, '{admin}' => (string)$adminZiel,
                '{support}' => (string)$supportZiel];

register_shutdown_function(static function () use ($pdo, $konten, $zielId, $adminZiel, $supportZiel, $neuId, $mailVorher): void {
    $ids = array_merge(array_column($konten, 'id'), [$zielId, $adminZiel, $supportZiel, $neuId]);
    $in = implode(',', array_map('intval', $ids));
    foreach ($konten as $k) { @unlink(sitzung_ort() . '/sess_' . $k['sid']); }
    try {
        $pdo->exec("DELETE FROM protokoll_ereignisse
                     WHERE betroffen_user_id IN ($in) OR urheber_user_id IN ($in)");
    } catch (Throwable) {}
    if ($mailVorher !== null) {
        try {
            $pdo->exec("DELETE FROM mail_warteschlange WHERE id > $mailVorher
                           AND schluessel IN ('passwort_neu', 'registrierung_erneut')");
        } catch (Throwable) {}
    }
    /* Geräte und Setz-Token hängen per ON DELETE CASCADE an `users`. */
    $pdo->exec("DELETE FROM users WHERE id IN ($in)");
});

/* ---- Die Zellen ------------------------------------------------------------- */

$n = 0; $offen = 0;
function pruef(bool $ok, string $was, string $dazu = ''): void
{
    global $n, $offen;
    $n++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-64s %s\n", $ok ? 'ok ' : 'FEHL', $was, $dazu);
}

/** Was die Anlage auf eine Anfrage sagt, in der Sprache der Matrix. */
function messen(string $aufruf, array $konto): string
{
    /* Die Zelle steht in Backticks; `\S+` nähme den schließenden mit, und
     * aus `r=verwaltung` würde „verwaltung`" — ein unbekannter Reiter. */
    global $platzhalter;
    $aufruf = strtr(trim($aufruf, '` '), $platzhalter);
    if (!preg_match('/^(GET|POST)\s+(\S+)(?:\s+(\S+))?$/', $aufruf, $a)) { return 'unlesbar'; }
    [, $art, $pfad] = $a;
    if (str_contains($pfad, '{')) { return 'unbekannter Platzhalter'; }
    $felder = [];
    if (!empty($a[3])) { parse_str($a[3], $felder); }
    $antwort = $art === 'GET'
        ? hole($pfad, $konto['sid'])
        : hole($pfad, $konto['sid'], $felder + ['csrf' => 'absichtlich-falsch']);
    /* DIE TOKEN-ABLEHNUNG ZUERST. Jede 403-Seite trägt die Überschrift
     * „Kein Zugriff" (`ui_abbruch()`), auch die des Tokens — wer zuerst nach
     * dem Rollentor fragte, fände es in jeder Antwort (gemessen: 37 falsche
     * Befunde beim ersten Lauf). Der Satz der Token-Prüfung ist eindeutig. */
    $token = $antwort['code'] === 403 && (str_contains($antwort['rumpf'], 'Ungültiges Formular-Token')
             || (json_decode($antwort['rumpf'], true)['error'] ?? null) === 'csrf');
    if ($art === 'POST') {
        if ($token) { return 'durch'; }
        return $antwort['code'] === 403 ? '403' : 'unerwartet ' . $antwort['code'];
    }
    if (getenv('ROLLENPROBE_LAUT')) {
        preg_match('/<title>([^<]*)/', $antwort['rumpf'], $ti);
        fwrite(STDERR, "    $pfad → {$antwort['code']} " . ($ti[1] ?? '') . "\n");
    }
    if ($antwort['code'] === 403 && !str_contains($antwort['rumpf'], 'Kein Zugriff')
        && !str_contains($antwort['rumpf'], 'vorbehalten')) {
        return '403 ohne Rollentor';
    }
    return (string)$antwort['code'];
}

echo "Rollenprobe gegen $basis — " . count($zeilen) . ' Handlungen × ' . count($rollen)
   . ' Rollen aus docs/Technik.md 4.99p' . "\n";
foreach ($zeilen as $z) {
    foreach ($rollen as $rolle) {
        $soll = $z[$rolle];
        $ist = messen($z['Aufruf'], $konten[$rolle]);
        pruef($ist === $soll, $z['Handlung'] . ' · ' . $rolle, 'soll ' . $soll . ', ist ' . $ist);
    }
}

/* ---- Der frische Code: der Umweg ohne ihn (Schritt 18, SR-07, E-SR-20) ------
 *
 * JE ZEILE MIT `ja` EINE ANFRAGE, mit dem Konto der KLEINSTEN Rolle, die die
 * Handlung darf — und einer Sitzung ohne `zf_frisch_bis`. Erwartet: 303 auf
 * `zweitfaktor.php?bestaetigen=1&zurueck=<die Seite der Handlung>`, nach einem
 * POST mit `nochmal=1`. Mit frischem Code (die Matrix darueber) geht dieselbe
 * Anfrage durch.
 *
 * DAS TOKEN IST ABSICHTLICH FALSCH. Fehlt das Tor, endet die Anfrage an der
 * Token-Ablehnung — ein „Konto löschen" ohne Tor loescht dann nichts.
 *
 * Die Pflichtrollen haben `totp_seit`; die NutzerIn bekommt ihn fuer diesen
 * Teil, sonst waere die Frage bei ihr ein Durchlass (kein Zweitfaktor, nichts
 * zu bestaetigen), und das Ausschalten des eigenen Zweitfaktors liesse sich
 * nicht messen. Danach steht er wieder, wie er war. */
echo "\nFrischer Code: der Umweg ohne ihn (SR-07, E-SR-20)\n";
$frischZeilen = array_values(array_filter($zeilen, static fn(array $z): bool => $z['frischer Code'] === 'ja'));
/* ERST DER DURCHLASS: Ein Konto ohne Zweitfaktor hat nichts zu bestaetigen.
 * Die NutzerIn der Probe hat keinen — ihr Ausschalten geht ohne frische
 * Sitzung bis zur Token-Ablehnung, ohne Umweg. */
if (isset($konten['user'])) {
    $ohne = $mitZweitfaktor
        ? $pdo->query('SELECT totp_seit IS NULL FROM users WHERE id = ' . $konten['user']['id'])->fetchColumn() : '1';
    pruef((string)$ohne === '1'
          && messen('`POST einstellungen.php?t=profil action=zf_ausschalten`', $konten['user']['unfrisch']) === 'durch',
          'Konto ohne Zweitfaktor, Sitzung nicht frisch: kein Umweg (durch)');
}
$totpVorher = [];
foreach ($rollen as $rolle) {
    $totpVorher[$rolle] = $mitZweitfaktor
        ? $pdo->query('SELECT totp_seit FROM users WHERE id = ' . $konten[$rolle]['id'])->fetchColumn() : null;
    if ($mitZweitfaktor && $totpVorher[$rolle] === null) {
        $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP() WHERE id = ?')->execute([$konten[$rolle]['id']]);
    }
}
try {
    foreach ($frischZeilen as $z) {
        $wer = null;
        foreach (['user', 'support', 'admin', 'betreiberin'] as $r) {
            if (in_array($z[$r] ?? '', ['durch', '200'], true)) { $wer = $r; break; }
        }
        if ($wer === null) { pruef(false, $z['Handlung'], 'keine Rolle darf sie — Zeile falsch markiert'); continue; }
        $aufruf = strtr(trim($z['Aufruf'], '` '), $platzhalter);
        preg_match('/^(GET|POST)\s+(\S+)(?:\s+(\S+))?$/', $aufruf, $a);
        $felder = [];
        if (!empty($a[3])) { parse_str($a[3], $felder); }
        $sz = $konten[$wer]['unfrisch'];
        $r = $a[1] === 'GET' ? hole($a[2], $sz['sid'])
                             : hole($a[2], $sz['sid'], $felder + ['csrf' => 'absichtlich-falsch']);
        $ziel = (string)($r['ziel'] ?? '');
        parse_str((string)parse_url($ziel, PHP_URL_QUERY), $q);
        $seite = basename((string)parse_url($a[2], PHP_URL_PATH));
        $zurueckSeite = basename((string)parse_url((string)($q['zurueck'] ?? ''), PHP_URL_PATH));
        $ok = $r['code'] === 303 && str_starts_with($ziel, 'zweitfaktor.php?bestaetigen=1&')
           && $zurueckSeite === $seite && (isset($q['nochmal']) === ($a[1] === 'POST'));
        pruef($ok, $z['Handlung'] . ' · ' . $wer . ' ohne frischen Code: Umweg',
              'HTTP ' . $r['code'] . ' → ' . ($ziel !== '' ? $ziel : '—'));
    }
} finally {
    foreach ($rollen as $rolle) {
        if ($mitZweitfaktor && $totpVorher[$rolle] === null) {
            $pdo->prepare('UPDATE users SET totp_seit = NULL WHERE id = ?')->execute([$konten[$rolle]['id']]);
        }
    }
}
pruef(count($frischZeilen) >= count(ZF_FRISCH_HANDLUNGEN),
      'Jede Handlung der Liste hat mindestens eine Zeile mit „ja"',
      count($frischZeilen) . ' Zeilen, ' . count(ZF_FRISCH_HANDLUNGEN) . ' Handlungen');

/* ---- Die Wirkung: ein Rollenwechsel, genau ein Eintrag ---------------------- */

echo "\nRollenwechsel auf der Kontoseite (E-P5c-38)\n";
$eintraege = static function () use ($pdo, $zielId): int {
    $st = $pdo->prepare("SELECT COUNT(*) FROM protokoll_ereignisse
                          WHERE art = 'rolle_geaendert' AND betroffen_user_id = ?");
    $st->execute([$zielId]);
    return (int)$st->fetchColumn();
};
$b = $konten['betreiberin'] ?? null;
if ($b === null) {
    pruef(false, 'Die Matrix hat keine Spalte „betreiberin"');
} else {
    $vorher = $eintraege();
    $senden = static fn(string $rolle) => hole('admin_user.php?id=' . $zielId, $b['sid'], [
        'csrf' => $b['csrf'], 'action' => 'konto', 'name' => 'Rollenprobe Ziel',
        'email' => $zielMail, 'role' => $rolle]);
    $r1 = $senden('admin');
    $nach1 = $eintraege();
    pruef($r1['code'] === 200 && $nach1 === $vorher + 1, 'user → admin: genau ein Eintrag',
          'HTTP ' . $r1['code'] . ', ' . $vorher . ' → ' . $nach1);
    $r2 = $senden('admin');
    $nach2 = $eintraege();
    pruef($nach2 === $nach1, 'Speichern ohne Wechsel: kein Eintrag', $nach1 . ' → ' . $nach2);
    $r3 = $senden('user');
    $nach3 = $eintraege();
    $rolleJetzt = (string)$pdo->query('SELECT role FROM users WHERE id = ' . $zielId)->fetchColumn();
    pruef($nach3 === $nach2 + 1 && $rolleJetzt === 'user', 'admin → user: genau ein Eintrag',
          $nach2 . ' → ' . $nach3 . ', Rolle jetzt ' . $rolleJetzt);
}

/* ---- Die Wirkungen des Supports (AP4, E-P5c-14, -40) ---------------------- */

echo "\nDer Support mit gültigem Token (E-P5c-14, -40)\n";
$s = $konten['support'] ?? null;
$a = $konten['admin'] ?? null;
if ($s === null || $a === null) {
    pruef(false, 'Die Matrix hat keine Spalte „support" oder „admin"');
} else {
    $bei = static fn(int $id, array $k, array $felder)
        => hole('admin_user.php?id=' . $id, $k['sid'], $felder + ['csrf' => $k['csrf']]);
    $zahl = static function (string $sql, array $w) use ($pdo): int {
        $st = $pdo->prepare($sql);
        $st->execute($w);
        return (int)$st->fetchColumn();
    };
    $mitLink = static fn(array $r): bool => str_contains($r['rumpf'], 'pw_handling.php?token=');

    /* Menü und Reiter. Gezählt auf einer Seite ohne eigene Verweise in die
     * Verwaltung — auf der Liste der NutzerInnen stünde jede Kontoseite mit. */
    $r = hole('einstellungen.php', $s['sid']);
    preg_match_all('/href="((?:admin|betrieb)_[a-z_]+\.php)"/', $r['rumpf'], $m);
    $ziele = array_values(array_unique($m[1]));
    sort($ziele);
    pruef($ziele === ['admin_protokoll.php', 'admin_users.php'],
          'Menü: unter Verwaltung genau NutzerInnen und Protokoll', implode(', ', $ziele) ?: 'keine');
    /* Die Liste: Konten mit eigenen Rechten fehlen beim Support — und die
     * Gegenprobe beim Admin zeigt, dass die Suche sie überhaupt fände. */
    $liste = static fn(array $k): string => hole('admin_users.php?q=rollenprobe-ziel', $k['sid'])['rumpf'];
    $ls = $liste($s);
    $la = $liste($a);
    pruef(str_contains($ls, 'rollenprobe-ziel@') && !str_contains($ls, 'rollenprobe-zieladmin@')
          && str_contains($la, 'rollenprobe-zieladmin@'),
          'Liste: das Konto eines Admins fehlt beim Support, nicht beim Admin');
    /* Die Sicht aus dem freigegebenen Bild (M-P5c-02c, E-P5c-66): drei
     * Kacheln; die Kontoseite einspaltig, Mengen ohne Formular, keine
     * Konto-Backups, kein Löschen, kein Speichern. */
    pruef(str_contains($ls, 'kennzahl-raster-3') && str_contains($la, 'kennzahl-raster-4'),
          'Liste: drei Kacheln beim Support, vier beim Admin');
    $ks = hole('admin_user.php?id=' . $zielId, $s['sid'])['rumpf'];
    $fehlt = array_keys(array_filter([
        'einspaltig'       => !str_contains($ks, 'form-raster-einspaltig'),
        'Mengen'           => !str_contains($ks, 'id="karte-mengen"'),
        'Grenzen-Formular' => str_contains($ks, 'name="grenze_einsaetze"'),
        'Konto-Backups'    => str_contains($ks, 'id="k-konto-backups"'),
        'Konto löschen'    => str_contains($ks, 'id="karte-loeschen"'),
        'Speichern'        => str_contains($ks, 'value="konto"') && str_contains($ks, '>Speichern<'),
    ]));
    pruef($fehlt === [], 'Kontoseite: wie im Bild', $fehlt === [] ? '' : 'abweichend: ' . implode(', ', $fehlt));
    /* Die Gegenprobe: Beim Admin stehen die Marken da — sonst belegte ihr
     * Fehlen beim Support nichts. */
    $ka = hole('admin_user.php?id=' . $zielId, $a['sid'])['rumpf'];
    $gegen = array_keys(array_filter([
        'zweispaltig'      => str_contains($ka, 'form-raster-einspaltig'),
        'Grenzen-Formular' => !str_contains($ka, 'name="grenze_einsaetze"'),
        'Konto-Backups'    => !str_contains($ka, 'id="k-konto-backups"'),
        'Konto löschen'    => !str_contains($ka, 'id="karte-loeschen"'),
        'Speichern'        => !str_contains($ka, '>Speichern<'),
    ]));
    pruef($gegen === [], 'Gegenprobe: beim Admin stehen die Marken da',
          $gegen === [] ? '' : 'fehlt: ' . implode(', ', $gegen));
    $r = hole('admin_protokoll.php', $s['sid']);
    $reiter = substr_count($r['rumpf'], 'class="reiter-punkt');
    pruef($r['code'] === 200 && $reiter === 2, 'Protokoll: genau zwei Reiter',
          'HTTP ' . $r['code'] . ', ' . $reiter . ' Reiter');

    /* Der Setz-Link. Erst der Support, dann die Gegenprobe: Zeigt die Seite
     * dem Admin den Link im selben Fall, belegt sein Fehlen beim Support
     * etwas. Geht die Mail hinaus, zeigt sie ihn keinem — dann ist der
     * Fehlfall nicht belegt, und das steht so da. */
    $rs = $bei($zielId, $s, ['action' => 'pw_reset']);
    $ra = $bei($zielId, $a, ['action' => 'pw_reset']);
    $zugestellt = str_contains($ra['rumpf'], 'verschickt — eine Stunde gültig');
    pruef($rs['code'] === 200 && !$mitLink($rs), 'Setz-Link senden: kein Link in der Antwort',
          'HTTP ' . $rs['code'] . ($zugestellt ? ', Mail zugestellt' : ', Mail NICHT zugestellt'));
    pruef($zugestellt || str_contains($rs['rumpf'], 'bitte an einen Admin oder die BetreiberIn wenden'),
          'Setz-Link im Fehlfall: der Support wird an die Verwaltung verwiesen');
    pruef(!$zugestellt && $mitLink($ra), 'Gegenprobe: dem Admin zeigt die Seite den Link',
          $zugestellt ? 'Mail zugestellt — der Fehlfall ist nicht belegt' : 'HTTP ' . $ra['code']);

    /* Das Gerät: aus ja, an nein, weg nein. */
    $aktiv = static fn(): ?int => ($v = $pdo->query('SELECT active FROM devices WHERE id = ' . $geraetId)
                                             ->fetchColumn()) === false ? null : (int)$v;
    $umgeschaltet = static fn(): int => $zahl("SELECT COUNT(*) FROM protokoll_ereignisse
        WHERE art = 'geraet_umgeschaltet' AND betroffen_user_id = ?", [$zielId]);
    $r = $bei($zielId, $s, ['action' => 'device_aus', 'dev' => $geraetId]);
    pruef($r['code'] === 200 && $aktiv() === 0 && $umgeschaltet() === 1,
          'Gerät deaktivieren: 1 → 0, ein Eintrag', 'HTTP ' . $r['code'] . ', active ' . var_export($aktiv(), true)
          . ', ' . $umgeschaltet() . ' Eintrag');
    $r = $bei($zielId, $s, ['action' => 'device_aus', 'dev' => $geraetId]);
    pruef($r['code'] === 200 && $umgeschaltet() === 1, 'Gerät zweimal deaktivieren: kein zweiter Eintrag',
          $umgeschaltet() . ' Eintrag');
    $r = $bei($zielId, $s, ['action' => 'device_toggle', 'dev' => $geraetId]);
    pruef($r['code'] === 403 && $aktiv() === 0, 'Gerät wieder einschalten: 403, bleibt aus',
          'HTTP ' . $r['code'] . ', active ' . var_export($aktiv(), true));
    $r = $bei($zielId, $s, ['action' => 'device_delete', 'dev' => $geraetId]);
    pruef($r['code'] === 403 && $aktiv() !== null, 'Gerät entkoppeln: 403, das Gerät bleibt',
          'HTTP ' . $r['code']);
    $r = $bei($zielId, $s, ['action' => 'user_delete', 'confirm_email' => $zielMail]);
    $da = $zahl('SELECT COUNT(*) FROM users WHERE id = ?', [$zielId]);
    pruef($r['code'] === 403 && $da === 1, 'Konto löschen: 403, das Konto bleibt', 'HTTP ' . $r['code']);

    /* Die Bestätigung: nur für „unbestätigt", und ohne Link in der Antwort. */
    $bestaetigt = static fn(int $id): int => $zahl("SELECT COUNT(*) FROM protokoll_ereignisse
        WHERE art = 'verifikation_gesendet' AND betroffen_user_id = ?", [$id]);
    $r = $bei($neuId, $s, ['action' => 'verifikation']);
    pruef($r['code'] === 200 && $bestaetigt($neuId) === 1 && !$mitLink($r),
          'Bestätigung erneut senden: ein Eintrag, kein Link', 'HTTP ' . $r['code'] . ', '
          . $bestaetigt($neuId) . ' Eintrag');
    $r = $bei($zielId, $s, ['action' => 'verifikation']);
    pruef(str_contains($r['rumpf'], 'wartet auf keine Bestätigung') && $bestaetigt($zielId) === 0,
          'Bestätigung an ein aktives Konto: abgewiesen, kein Eintrag');
}

/* ---- Die Löschung durch die Verwaltung (R4-10, Backlog Nr. 299) ----------
 *
 * Ein eigenes Wegwerfkonto mit einem Einsatz, drei Spurpunkten, einem Geraet
 * mit einem Sperrvermerk und einem Mengenstand in `app_state` — genau das,
 * was an keinem Fremdschluessel haengt und eine Abschrift des Loeschens
 * deshalb vergessen kann. Geloescht wird es als Admin mit gueltigem Token, so wie die
 * Kontoseite es tut. Erwartet: 302 auf die Liste, und danach steht nichts
 * mehr davon da, dafuer ein Eintrag `konto_geloescht` mit `weg` =
 * `verwaltung`. */
echo "
Löschen über konto_loeschen() (Nr. 299)
";
if ($a === null) {
    pruef(false, 'Die Matrix hat keine Spalte „admin"');
} else {
    require_once $srv . '/spur_lib.php';
    $loeschMail = 'rollenprobe-loeschen@probe.invalid';
    $loeschId = zielkonto('loeschen', 'user');
    /* Scheitert die Loeschung, darf das Konto nicht liegen bleiben. */
    register_shutdown_function(static function () use ($pdo, $loeschId): void {
        try { $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$loeschId]); } catch (Throwable) {}
    });
    $pdo->prepare("INSERT INTO missions (user_id, client_ref, started_at) VALUES (?, ?, UTC_TIMESTAMP())")
        ->execute([$loeschId, 'rollenprobe-' . bin2hex(random_bytes(6))]);
    $loeschEinsatz = (int)$pdo->lastInsertId();
    $pp = $pdo->prepare('INSERT INTO track_points (owner_type, owner_id, seq, lat, lon, ele, ts)
                         VALUES (?, ?, ?, ?, ?, ?, ?)');
    for ($i = 0; $i < 3; $i++) {
        $pp->execute(['mission', $loeschEinsatz, $i, 47.5 + $i / 1e4, 11.5, 700.0, 1750000000 + $i]);
    }
    $pdo->prepare("INSERT INTO devices (user_id, device_id, api_key_hash) VALUES (?, ?, '-')")
        ->execute([$loeschId, 'rollenprobe-' . bin2hex(random_bytes(6))]);
    $loeschGeraet = (int)$pdo->lastInsertId();
    $pdo->prepare("INSERT INTO deleted_refs (device_id, owner_type, client_ref) VALUES (?, 'mission', 'rollenprobe')")
        ->execute([$loeschGeraet]);
    app_state_setzen('mengen:' . $loeschId, '1');
    $spurVorher = spur_zahlen($pdo, 'mission', [$loeschEinsatz])[$loeschEinsatz] ?? 0;
    $r = hole('admin_user.php?id=' . $loeschId, $a['sid'], ['csrf' => $a['csrf'],
              'action' => 'user_delete', 'confirm_email' => $loeschMail, 'sicherungen_mit' => '1']);
    $kontoDa = (int)$pdo->query('SELECT COUNT(*) FROM users WHERE id = ' . $loeschId)->fetchColumn();
    $spurNach = spur_zahlen($pdo, 'mission', [$loeschEinsatz])[$loeschEinsatz] ?? 0;
    $mengeDa = app_state_lesen('mengen:' . $loeschId);
    $sperreDa = (int)$pdo->query('SELECT COUNT(*) FROM deleted_refs WHERE device_id = ' . $loeschGeraet)->fetchColumn();
    $st = $pdo->prepare("SELECT daten FROM protokoll_ereignisse
                          WHERE art = 'konto_geloescht' AND betroffen_user_id = ? ORDER BY id DESC LIMIT 1");
    $st->execute([$loeschId]);
    $datenRoh = (string)$st->fetchColumn();
    $wegImProtokoll = json_decode($datenRoh, true)['weg'] ?? null;
    pruef($r['code'] === 302 && $kontoDa === 0, 'Admin löscht ein Konto: 302, das Konto ist fort',
          'HTTP ' . $r['code'] . ', Konto ' . $kontoDa);
    pruef($spurVorher === 3 && $spurNach === 0, 'Die Spur geht mit (spur_zahlen)',
          $spurVorher . ' → ' . $spurNach . ' Punkte');
    pruef($mengeDa === null, 'mengen:<id> in app_state ist abgeräumt',
          $mengeDa === null ? '' : 'steht noch: ' . $mengeDa);
    pruef($sperreDa === 0, 'Die Sperrliste des Geräts geht mit (deleted_refs)',
          $sperreDa === 0 ? '' : 'stehen noch: ' . $sperreDa);
    pruef($wegImProtokoll === 'verwaltung', 'Das Protokoll nennt den Weg', $datenRoh);
    /* Das Konto ist fort — der Schluss braucht es nicht mehr zu loeschen,
     * wohl aber seinen Protokolleintrag. */
    try {
        $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ?')->execute([$loeschId]);
        $pdo->prepare('DELETE FROM missions WHERE id = ?')->execute([$loeschEinsatz]);
        $pdo->prepare('DELETE FROM deleted_refs WHERE device_id = ?')->execute([$loeschGeraet]);
    } catch (Throwable) {}
}

/* ---- Umleiten nach POST (R4-11, Backlog Nr. 250) --------------------------
 *
 * Je Seite ein Zweig, der auf einer Prüfanlage HARMLOS gelingt — dieselben
 * Werte noch einmal speichern, eine Sperre aufheben, die es nicht gibt, ein
 * Konto sichern, das es nicht gibt —, als BetreiberIn mit gültigem Token.
 * Erwartet: 302 auf die genannte Adresse; das GET dorthin zeigt die Meldung
 * (sie kam über die Sitzung, `flash_setzen()`), ein zweites GET nicht mehr.
 * Das ist die Eigenschaft, um die es geht: Neuladen wiederholt nichts, und
 * die Meldung steht einmal da.
 *
 * NICHT GEFAHREN, mit Grund: das Demo-Konto (jeder Zweig setzt es zurück,
 * legt es an oder entfernt es — diese Probe ist nicht demo-empfindlich und
 * soll es nicht werden); Wartung an/aus (die Wartungsprobe, Erwartung 13);
 * ein neues Jobs-Token (es macht den Zeitplan-Eintrag ungültig); Backups,
 * Einspielen, Freigeben und Löschen (sie schreiben oder löschen Bestand);
 * Ziel speichern, prüfen, versenden (sie brauchen ein erreichbares Ziel). */
echo "\nUmleiten nach POST (R4-11, Nr. 250)\n";
$prg = static function (string $was, string $pfad, array $felder, string $ziel, string $meldung)
    use ($b): void {
    $r = hole($pfad, $b['sid'], ['csrf' => $b['csrf']] + $felder);
    $zielOhneAnker = preg_replace('/#.*$/', '', (string)$r['ziel']);
    $f1 = $r['code'] === 302 ? hole($zielOhneAnker, $b['sid']) : ['code' => 0, 'rumpf' => ''];
    $f2 = $r['code'] === 302 ? hole($zielOhneAnker, $b['sid']) : ['code' => 0, 'rumpf' => ''];
    /* GEZAEHLT, NICHT GESUCHT: Auf `betrieb_jobs.php` sagt ein Dauerhinweis
     * dasselbe wie die Meldung („… angehalten bis"), solange die Pause gilt.
     * Die Meldung ist, was das erste GET dem zweiten voraus hat — genau
     * einmal. */
    $n1 = substr_count(html_entity_decode($f1['rumpf']), $meldung);
    $n2 = substr_count(html_entity_decode($f2['rumpf']), $meldung);
    pruef($r['code'] === 302 && $r['ziel'] === $ziel && $n1 === $n2 + 1,
          $was . ': 302, Meldung einmal',
          'HTTP ' . $r['code'] . ' → ' . ($r['ziel'] ?? '—')
          . ($r['code'] === 302 ? ', Treffer ' . $n1 . ' dann ' . $n2 : ''));
};
if ($b === null) {
    pruef(false, 'Die Matrix hat keine Spalte „betreiberin"');
} else {
    require_once $srv . '/adminbackup_lib.php';
    require_once $srv . '/komplett_lib.php';
    require_once $srv . '/sicherungsziel_lib.php';
    require_once $srv . '/rechtstexte_lib.php';
    require_once $srv . '/jobs_lib.php';
    require_once $srv . '/migration_lib.php';
    require_once $srv . '/session_lib.php';

    /* Installation: der Logo-Standard, der schon gilt — Karte k-logo. */
    $prg('Installation · Logo-Standard', 'admin_installation.php',
         ['action' => 'logo_standard', 'logo' => logo_standard()],
         'admin_installation.php#k-logo', 'Standard der Installation:');

    /* Rechtstexte: der erste Text, dessen gespeicherter Stand die Prüfung
     * besteht, unverändert zurück — „Es gab nichts zu ändern". */
    $rtSchluessel = null;
    foreach (array_keys(RT_TEXTE) as $rk) {
        $rt = rt_lesen($rk);
        if (rt_pruefen($rt['inhalt'], (string)($rt['stand'] ?? '')) === null) { $rtSchluessel = $rk; break; }
    }
    if ($rtSchluessel === null) {
        pruef(false, 'Rechtstexte · unverändert speichern', 'kein gespeicherter Text besteht rt_pruefen() — nicht gemessen');
    } else {
        $rt = rt_lesen($rtSchluessel);
        $prg('Rechtstexte · unverändert speichern', 'admin_rechtstexte.php?t=' . $rtSchluessel,
             ['schluessel' => $rtSchluessel, 'text' => $rt['inhalt'], 'stand' => (string)($rt['stand'] ?? '')],
             'admin_rechtstexte.php?t=' . rawurlencode($rtSchluessel), 'Es gab nichts zu ändern.');
    }

    /* Konto-Backups: die Regeln, wie sie stehen. */
    $prg('Konto-Backups · Regeln unverändert', 'admin_sicherungen.php',
         ['action' => 'regeln', 'tage' => edbak_intervall(), 'pakete' => edbak_aufbewahrung(),
          'mail' => edbak_admin_mail_an() ? '1' : ''],
         'admin_sicherungen.php', 'Es gab nichts zu ändern.');

    /* Komplett-Backup: Plan und Aufbewahrung, wie sie stehen. */
    $prg('Komplett-Backup · Regeln unverändert', 'admin_komplettsicherung.php',
         ['action' => 'regeln', 'plan' => komp_plan(), 'aufbewahrung' => komp_aufbewahrung()],
         'admin_komplettsicherung.php', 'Die Regeln wurden gespeichert.');

    /* Backup-Ziele: der Versandschalter, wie er steht. */
    if (!sz_tabelle_da()) {
        pruef(false, 'Backup-Ziele · Versandschalter', 'Tabelle backup_targets fehlt — nicht gemessen');
    } else {
        $prg('Backup-Ziele · Versandschalter unverändert', 'admin_sicherungsziele.php',
             ['action' => 'versand_schalter', 'versand_auto' => sz_auto_an() ? '1' : ''],
             'admin_sicherungsziele.php', 'Der Versand ist');
    }

    /* NutzerInnen: ein Konto sichern, das es nicht gibt — die Zahl 0, der
     * Grund und die Restauswahl reisen über die Sitzung mit (E-R4-33). */
    $prg('NutzerInnen · Auswahl sichern', 'admin_users.php',
         ['action' => 'sichern_auswahl', 'auswahl' => '999999999'],
         'admin_users.php', '0 Konto-Backups erzeugt.');

    /* NutzerInnen: Konto anlegen. Ohne zugestellte Mail bleibt die Seite
     * stehen und zeigt den Setz-Link (E-R4-33 — ein Token gehört nicht in die
     * Sitzungsdatei); mit zugestellter Mail leitet sie um, und der Link steht
     * nirgends. Beides ist richtig; falsch wäre der Link nach einer
     * Umleitung oder eine 200 ohne Link. */
    $anlageMail = 'rollenprobe-anlage@probe.invalid';
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$anlageMail]);
    $r = hole('admin_users.php', $b['sid'], ['csrf' => $b['csrf'], 'action' => 'user_add',
              'email' => $anlageMail, 'role' => 'user', 'name' => '']);
    $mitLink = str_contains($r['rumpf'], 'pw_handling.php?token=');
    if ($r['code'] === 302) {
        $f = hole(preg_replace('/#.*$/', '', (string)$r['ziel']), $b['sid']);
        pruef(!str_contains($f['rumpf'], 'pw_handling.php?token=')
              && str_contains($f['rumpf'], 'Setz-Link per E-Mail verschickt'),
              'NutzerInnen · Konto anlegen (Mail zugestellt): 302, kein Link',
              'HTTP 302 → ' . $r['ziel']);
    } else {
        pruef($r['code'] === 200 && $mitLink && $r['ziel'] === null,
              'NutzerInnen · Konto anlegen (Mail nicht zugestellt): 200 mit Setz-Link',
              'HTTP ' . $r['code'] . ($mitLink ? ', Link da' : ', Link FEHLT'));
    }
    /* Das Konto sofort wieder fort; die Einladung in der Warteschlange
     * raeumt der Schluss mit den uebrigen Mails der Probe. */
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$anlageMail]);

    /* Jobs: anhalten und wieder freigeben — der Stand davor kommt zurück,
     * wörtlich (eine Pause, die ein anderer Lauf gesetzt hat, bleibt). */
    $pauseVorher = app_state_lesen(JOB_PAUSE_SCHLUESSEL);
    try {
        $prg('Jobs · anhalten', 'betrieb_jobs.php', ['action' => 'jobs_pause_an', 'dauer' => '900'],
             'betrieb_jobs.php#k-zustand', 'angehalten bis');
        $prg('Jobs · Pause aufheben', 'betrieb_jobs.php', ['action' => 'jobs_pause_aus'],
             'betrieb_jobs.php#k-zustand', 'Die Pause ist aufgehoben');
    } finally {
        if ($pauseVorher === null) { app_state_loeschen(JOB_PAUSE_SCHLUESSEL); }
        else                       { app_state_setzen(JOB_PAUSE_SCHLUESSEL, $pauseVorher); }
    }

    /* Servereinstellungen (Schritt 18, SR-02, Nr. 250, E-SR-37): bis Web
     * 21.7.0 die eine Betriebsseite ohne Umleitung. Zwei Karten, beide mit
     * dem Stand, der schon gilt — „Es gab nichts zu ändern", in der Karte.
     * Die Karte „Anmeldung" wird vorher einmal gespeichert: Beim ersten Mal
     * legt sie ihre zwei Werte in `app_state` an, und die Meldung waere eine
     * andere. */
    require_once $srv . '/totp_lib.php';
    require_once $srv . '/kopfzeilen_lib.php';
    $anmFelder = ['action' => 'anmeldung',
                  ZF_GERAET_K_USER => (string)zweitfaktor_geraet_tage('user'),
                  ZF_GERAET_K_VERWALTUNG => (string)zweitfaktor_geraet_tage('verwaltung')];
    $vor = hole('betrieb_server.php', $b['sid'], ['csrf' => $b['csrf']] + $anmFelder);
    if ($vor['code'] === 302) { hole('betrieb_server.php', $b['sid']); }   // Meldung abholen
    $prg('Servereinstellungen · Anmeldung unverändert', 'betrieb_server.php', $anmFelder,
         'betrieb_server.php#k-anmeldung', 'Es gab nichts zu ändern.');
    $prg('Servereinstellungen · Kopfzeilen unverändert', 'betrieb_server.php',
         ['action' => 'kopfzeilen', 'hsts_tage' => (string)kopf_hsts_tage()]
         + (kopf_csp_scharf() ? ['csp_scharf' => '1'] : []),
         'betrieb_server.php#k-kopfzeilen', 'Es gab nichts zu ändern.');
    /* Zwei Karten mehr (Nr. 250 sagt „jeder POST"): Protokoll und
     * Adresssuche, wieder mit dem Stand, der gilt. Beide werden vorher einmal
     * gespeichert, aus demselben Grund wie die Anmeldung — die Adresssuche
     * zählt die Vorgabe beim ersten Mal als Änderung. */
    require_once $srv . '/protokoll_archiv_lib.php';
    require_once $srv . '/geocoder_lib.php';
    $protFelder = ['action' => 'protokoll',
                   'protokoll_frist' => (string)protokoll_frist_verwaltung(),
                   'archiv_tage' => (string)protokoll_archiv_tage(),
                   'archiv_behalten' => (string)protokoll_archiv_behalten()]
                + (protokoll_archiv_versand() ? ['archiv_versand' => '1'] : []);
    $geoFelder = ['action' => 'geocoder', 'dienst' => geocoder_dienst()]
               + (geocoder_installation_an() ? ['adresssuche' => '1'] : []);
    foreach ([$protFelder, $geoFelder] as $f) {
        $vor = hole('betrieb_server.php', $b['sid'], ['csrf' => $b['csrf']] + $f);
        if ($vor['code'] === 302) { hole('betrieb_server.php', $b['sid']); }
    }
    $prg('Servereinstellungen · Protokoll unverändert', 'betrieb_server.php', $protFelder,
         'betrieb_server.php#k-protokoll', 'Es gab nichts zu ändern.');
    $prg('Servereinstellungen · Adresssuche unverändert', 'betrieb_server.php', $geoFelder,
         'betrieb_server.php#k-adresssuche', 'Es gab nichts zu ändern.');

    /* Sicherheit: eine Sperre aufheben, die es nicht gibt — Ton warn. */
    $prg('Sicherheit · Sperre aufheben (gibt es nicht)', 'betrieb_sicherheit.php',
         ['action' => 'aufheben', 'topf' => 'rollenprobe', 'merkmal' => 'rollenprobe'],
         'betrieb_sicherheit.php', 'Diese Sperre gibt es nicht mehr');

    /* Updates: „Ausstehende ausführen" — nur, wenn nichts aussteht; sonst
     * liefe hier eine Migration. */
    if ((int)migrationen_lauf($pdo, false)['offen'] !== 0) {
        pruef(false, 'Updates · Lauf ohne Ausstehendes', 'es steht eine Migration aus — nicht gemessen');
    } else {
        $prg('Updates · Lauf ohne Ausstehendes', 'betrieb_updates.php', ['action' => 'migrate'],
             'betrieb_updates.php#k-ausstehend', 'Es war nichts anzuwenden.');
    }

    /* Status: die Testmail. Ohne eingerichteten Versand ändert der Klick
     * nichts und bleibt stehen (E-R4-35); mit Versand leitet er um.
     *
     * DER ZÄHLER WIRD VORHER GELEERT. Der Ratenschutz erlaubt drei Testmails
     * je Stunde — je Konto UND je Adresse, und die Adresse ist hier immer
     * 127.0.0.1. Wer die Probe in einer Stunde öfter fuhr, bekam „Zu viele
     * Testmails" (200, ohne Umleitung) und ein Rot, das nichts über die
     * Seite sagte (erster Prüfstand von R4-11). */
    $pdo->exec("DELETE FROM rate_limits WHERE topf = 'testmail'");
    $r = hole('betrieb_status.php', $b['sid'], ['csrf' => $b['csrf'], 'action' => 'testmail']);
    if ($r['code'] === 302) {
        hole('betrieb_status.php', $b['sid']);   // die Meldung abholen, damit sie nicht liegen bleibt
        pruef($r['ziel'] === 'betrieb_status.php#k-mail',
              'Status · Testmail (Versand eingerichtet): 302 in die Karte E-Mail',
              'HTTP 302 → ' . $r['ziel']);
    } else {
        pruef($r['code'] === 200 && str_contains($r['rumpf'], 'kein SMTP eingerichtet'),
              'Status · Testmail (ohne Versand): bleibt stehen, sagt es',
              'HTTP ' . $r['code'] . (str_contains($r['rumpf'], 'Zu viele Testmails')
                                      ? ', „Zu viele Testmails"' : ''));
    }
}

/* ---- Backup-Ziele: Speichern und Nachsehen (R4-11, F-R4-35, F-R4-36) -----
 *
 * Zwei Fehler der Seite, die beim Umbau auf die Umleitung auffielen, beide
 * still und beide mit Folgen für die Daten am Ziel:
 *   - Ein Hostwechsel sollte den gespeicherten SFTP-Hostschlüssel vergessen;
 *     die Seite las den alten Stand erst NACH dem Speichern und vergaß nie
 *     (F-R4-36).
 *   - Scheiterte „Nachsehen, was dort liegt" bei offenem Formular, stand der
 *     Schalter „Auf dem Ziel aufräumen" danach auf aus — wer dann speicherte,
 *     schaltete die Aufbewahrung ab (F-R4-35).
 * Ein Wegwerfziel auf 127.0.0.1, Port 1 (niemand hört dort, „Nachsehen"
 * scheitert sofort); der Schluss löscht es. */
echo "\nBackup-Ziele: Speichern und Nachsehen (F-R4-35, -36)\n";
if ($b === null || !sz_tabelle_da()) {
    pruef(false, 'Backup-Ziele', 'keine BetreiberIn oder keine Tabelle backup_targets — nicht gemessen');
} else {
    $zielName = 'Rollenprobe-Ziel ' . bin2hex(random_bytes(3));
    $zFelder = ['action' => 'ziel_speichern', 'id' => '0', 'name' => $zielName,
                'protokoll' => 'sftp', 'host' => '127.0.0.1', 'port' => '1',
                'nutzer' => 'probe', 'pfad' => '/', 'geheim' => 'rollenprobe-geheim',
                'aufraeumen' => '1', 'behalten_konto' => '2', 'behalten_komplett' => '2'];
    $r = hole('admin_sicherungsziele.php?neu=1', $b['sid'], ['csrf' => $b['csrf']] + $zFelder);
    $st = $pdo->prepare('SELECT id FROM backup_targets WHERE name = ?');
    $st->execute([$zielName]);
    $zid = (int)$st->fetchColumn();
    register_shutdown_function(static function () use ($pdo, $zid): void {
        try { $pdo->prepare('DELETE FROM backup_targets WHERE id = ?')->execute([$zid]); } catch (Throwable) {}
    });
    pruef($r['code'] === 302 && $zid > 0, 'Ziel anlegen: 302, das Ziel steht',
          'HTTP ' . $r['code'] . ', Kennung ' . $zid);
    if ($zid > 0) {
        sz_fingerabdruck_merken($zid, 'SHA256:rollenprobe');
        $r = hole('admin_sicherungsziele.php?bearbeiten=' . $zid, $b['sid'], ['csrf' => $b['csrf']]
                  + array_merge($zFelder, ['id' => (string)$zid, 'host' => '127.0.0.2', 'geheim' => '']));
        $abdruck = $pdo->query('SELECT fingerabdruck FROM backup_targets WHERE id = ' . $zid)->fetchColumn();
        $f = $r['code'] === 302 ? hole(preg_replace('/#.*$/', '', (string)$r['ziel']), $b['sid'])
                                : ['rumpf' => ''];
        pruef($r['code'] === 302 && $abdruck === null
              && str_contains(html_entity_decode($f['rumpf']), 'Der Hostschlüssel wurde vergessen'),
              'Hostwechsel vergisst den Abdruck und sagt es (F-R4-36)',
              'HTTP ' . $r['code'] . ', Abdruck ' . var_export($abdruck, true));

        $r = hole('admin_sicherungsziele.php?bearbeiten=' . $zid, $b['sid'],
                  ['csrf' => $b['csrf'], 'action' => 'ziel_bestand', 'id' => (string)$zid]);
        $an = (bool)preg_match('/name="aufraeumen"[^>]*checked/', $r['rumpf']);
        pruef($r['code'] === 200 && $an,
              'Nachsehen scheitert: „Auf dem Ziel aufräumen" bleibt an (F-R4-35)',
              'HTTP ' . $r['code'] . ', Schalter ' . ($an ? 'an' : 'AUS'));
    }
}

/* ---- Die Wirkung des Rückwegs: jede Rolle legt ein Paar ab (Konzept RW) ---
 *
 * Die Matrixzeile sagt nur, dass jede Rolle den Endpunkt ERREICHT. Hier
 * gelingt es: gültiges Formular-Token, gültiges Anmelde-Token (die Konten
 * der Probe bekommen dafür einen bekannten Hash), ein frisch erzeugter
 * öffentlicher Teil auf P-256. Erwartet: 200 und ein Paar je Rolle. */
echo "\nRückweg: Paar ablegen je Rolle (Konzept RW, RW-02)\n";
if (!db_hat_spalte($pdo, 'users', 'rw_seit')) {
    pruef(false, 'Rückweg je Rolle', 'Spalten rw_* fehlen — nicht gemessen');
} else {
    require_once $srv . '/rueckweg_lib.php';
    $gelungen = 0;
    foreach ($konten as $rolle => $k) {
        $tok = bin2hex(random_bytes(32));
        $pdo->prepare('UPDATE users SET password_hash = ?, rw_oeffentlich = NULL, rw_privat = NULL,
                              rw_seit = NULL WHERE id = ?')
            ->execute([password_hash($tok, PASSWORD_DEFAULT), $k['id']]);
        $spki = (string)preg_replace('/-----[^-]+-----|\s/', '',
            \phpseclib3\Crypt\EC::createKey(RW_KURVE)->getPublicKey()->toString('PKCS8'));
        $r = hole('api/rueckweg_anlegen.php', $k['sid'], ['token' => $tok, 'oeffentlich' => $spki,
                  'privat' => 'edk1:' . base64_encode(random_bytes(160))], $k['csrf']);
        $da = (int)$pdo->query('SELECT rw_oeffentlich IS NOT NULL FROM users WHERE id = ' . (int)$k['id'])
                       ->fetchColumn();
        $ok = $r['code'] === 200 && $da === 1;
        $gelungen += $ok ? 1 : 0;
        pruef($ok, "Rückweg ablegen · $rolle", 'HTTP ' . $r['code'] . ', Paar ' . $da);
    }
    pruef($gelungen === count($konten), "$gelungen von " . count($konten) . ' Rollen');
}

echo "\n-> $n Erwartungen, $offen nicht erfüllt\n";
exit($offen === 0 ? 0 : 1);
