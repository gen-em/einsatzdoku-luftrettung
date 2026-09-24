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
 *     einem anderen Grund zählte sonst als Tor.
 *   - POST: mit einem ABSICHTLICH FALSCHEN Formular-Token. `403` heißt: das
 *     Rollentor antwortet. `durch` heißt: Die Antwort ist die
 *     Token-Ablehnung — die Rolle hat die Handlung erreicht, ausgeführt wird
 *     sie nicht. So läuft die Probe gefahrlos über „Stand löschen",
 *     „jetzt versenden" und „jetzt sichern".
 *
 * UND EINE WIRKUNG: Ein Rollenwechsel auf der Kontoseite schreibt genau
 * einen Eintrag `rolle_geaendert`, ein Speichern ohne Wechsel keinen.
 *
 * DIE KONTEN UND SITZUNGEN LEGT SIE SELBST AN (Muster Wartungsprobe; die
 * Anmeldung leitet das Token im Browser per PBKDF2 ab und ist mit `curl`
 * nicht nachzubilden). Drei Konten `rollenprobe-*@probe.invalid`, eines je
 * Rolle — nicht die Sandbox, und nicht vorhandene Konten: Eine Probe, die
 * von einem vorher angelegten Konto abhängt, ist nach `hochfahren.sh --neu`
 * rot, ohne gemessen zu haben (E-P5c-78). Aufgeräumt wird im Schluss, auch
 * nach einem Abbruch: Konten, Sitzungsdateien, ihre Protokolleinträge.
 *
 * WAS SIE NICHT PRÜFT: ob eine Handlung mit gültigem Token gelingt (das tun
 * Bedienwege und die Proben der Sache), die Rolle `support` (kommt mit AP4),
 * und die API-Endpunkte (eigene Tore, eigene Proben).
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
$rollen = array_slice($spalten ?? [], 2);
if ($zeilen === [] || $rollen === []) { fwrite(STDERR, "Die Matrix ist leer.\n"); exit(2); }

/* ---- HTTP und Sitzungen (Muster: tools/proben/wartung/) -------------------- */

function hole(string $pfad, ?string $sid, ?array $koerper = null): array
{
    global $basis;
    $ch = curl_init("$basis/$pfad");
    $kopf = $sid !== null ? ['Cookie: PHPSESSID=' . $sid] : [];
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => false,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_HTTPHEADER => $kopf,
        CURLOPT_TIMEOUT => 60, CURLOPT_PROXY => '']);
    if ($koerper !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($koerper));
    }
    $rumpf = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'rumpf' => $rumpf];
}

function sitzung_ort(): string
{
    $eigen = sitzung_ablage_pfad();
    return is_dir($eigen) ? $eigen : (string)(session_save_path() ?: sys_get_temp_dir());
}

/** Eine Sitzung, wie `login.php` sie hinterlässt. VOR jeder Ausgabe anlegen. */
function sitzung_anlegen(int $uid, int $epoch): array
{
    $sid  = 'rollenprobe' . bin2hex(random_bytes(10));
    $csrf = bin2hex(random_bytes(16));
    if (session_status() === PHP_SESSION_ACTIVE) { session_write_close(); }
    session_save_path(sitzung_ort());
    session_id($sid);
    session_start();
    $_SESSION = ['user_id' => $uid, 'epoch' => $epoch, 'last_seen' => time(), 'csrf' => $csrf];
    session_write_close();
    return ['sid' => $sid, 'csrf' => $csrf];
}

/* ---- Konten ------------------------------------------------------------------ */

$konten = [];
foreach ($rollen as $rolle) {
    $mail = 'rollenprobe-' . $rolle . '@probe.invalid';
    $pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
    $pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, ?, ?, '', '', 320000)")->execute([$mail, 'Rollenprobe ' . $rolle, $rolle]);
    $id = (int)$pdo->lastInsertId();
    $epoch = (int)$pdo->query('SELECT session_epoch FROM users WHERE id = ' . $id)->fetchColumn();
    $konten[$rolle] = ['id' => $id, 'mail' => $mail] + sitzung_anlegen($id, $epoch);
}
/* Das Zielkonto des Rollenwechsels — eine vierte, eigene Zeile. */
$zielMail = 'rollenprobe-ziel@probe.invalid';
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$zielMail]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Rollenprobe Ziel', 'user', '', '', 320000)")->execute([$zielMail]);
$zielId = (int)$pdo->lastInsertId();

register_shutdown_function(static function () use ($pdo, $konten, $zielId): void {
    $ids = array_merge(array_column($konten, 'id'), [$zielId]);
    $in = implode(',', array_map('intval', $ids));
    foreach ($konten as $k) { @unlink(sitzung_ort() . '/sess_' . $k['sid']); }
    try {
        $pdo->exec("DELETE FROM protokoll_ereignisse
                     WHERE betroffen_user_id IN ($in) OR urheber_user_id IN ($in)");
    } catch (Throwable) {}
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
    if (!preg_match('/^(GET|POST)\s+(\S+)(?:\s+(\S+))?$/', trim($aufruf, '` '), $a)) { return 'unlesbar'; }
    [, $art, $pfad] = $a;
    $felder = [];
    if (!empty($a[3])) { parse_str($a[3], $felder); }
    $antwort = $art === 'GET'
        ? hole($pfad, $konto['sid'])
        : hole($pfad, $konto['sid'], $felder + ['csrf' => 'absichtlich-falsch']);
    /* DIE TOKEN-ABLEHNUNG ZUERST. Jede 403-Seite trägt die Überschrift
     * „Kein Zugriff" (`ui_abbruch()`), auch die des Tokens — wer zuerst nach
     * dem Rollentor fragte, fände es in jeder Antwort (gemessen: 37 falsche
     * Befunde beim ersten Lauf). Der Satz der Token-Prüfung ist eindeutig. */
    $token = $antwort['code'] === 403 && str_contains($antwort['rumpf'], 'Ungültiges Formular-Token');
    if ($art === 'POST') {
        if ($token) { return 'durch'; }
        return $antwort['code'] === 403 ? '403' : 'unerwartet ' . $antwort['code'];
    }
    if (getenv('ROLLENPROBE_LAUT')) {
        preg_match('/<title>([^<]*)/', $antwort['rumpf'], $ti);
        fwrite(STDERR, "    $pfad → {$antwort['code']} " . ($ti[1] ?? '') . "\n");
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

echo "\n-> $n Erwartungen, $offen nicht erfüllt\n";
exit($offen === 0 ? 0 : 1);
