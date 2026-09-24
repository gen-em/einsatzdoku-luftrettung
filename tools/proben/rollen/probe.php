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
 * ZWEI PLATZHALTER (AP4): `{ziel}` ist ein Konto der Rolle `user`, `{admin}`
 * eines der Rolle `admin`. Die Kontoseite fragt die Rolle zweimal — ob die
 * Angemeldete das Zielkonto betreuen darf, dann je Handlung —, und beide
 * Tore stehen in der Matrix.
 *
 * UND WIRKUNGEN, denn `durch` sagt nur, dass eine Rolle die Handlung
 * erreicht, nicht, was sie dort tut:
 *   - Ein Rollenwechsel auf der Kontoseite schreibt genau einen Eintrag
 *     `rolle_geaendert`, ein Speichern ohne Wechsel keinen (E-P5c-38).
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
 * nicht nachzubilden). Ein Konto `rollenprobe-*@probe.invalid` je Rolle, dazu
 * drei Zielkonten (`ziel`, `zieladmin`, `neu` im Status „unbestätigt") und
 * ein Gerät — nicht die Sandbox, und nicht vorhandene Konten: Eine Probe, die
 * von einem vorher angelegten Konto abhängt, ist nach `hochfahren.sh --neu`
 * rot, ohne gemessen zu haben (E-P5c-78). Aufgeräumt wird im Schluss, auch
 * nach einem Abbruch: Konten (Geräte und Setz-Token fallen mit), Sitzungs-
 * dateien, ihre Protokolleinträge, die Mails der Probe in der Warteschlange.
 *
 * WAS SIE NICHT PRÜFT: ob eine Handlung mit gültigem Token gelingt, außer
 * den Wirkungen oben (das tun Bedienwege und die Proben der Sache), und die
 * API-Endpunkte (eigene Tore, eigene Proben).
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
/* Die Zielkonten — eigene Zeilen, nicht die Konten der Rollen: `ziel` für
 * den Rollenwechsel und die Handlungen an einem Konto der Rolle `user`,
 * `zieladmin` für den Platzhalter `{admin}`, `neu` für die Bestätigung einer
 * Registrierung. */
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
$platzhalter = ['{ziel}' => (string)$zielId, '{admin}' => (string)$adminZiel];

register_shutdown_function(static function () use ($pdo, $konten, $zielId, $adminZiel, $neuId, $mailVorher): void {
    $ids = array_merge(array_column($konten, 'id'), [$zielId, $adminZiel, $neuId]);
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
    $token = $antwort['code'] === 403 && str_contains($antwort['rumpf'], 'Ungültiges Formular-Token');
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

echo "\n-> $n Erwartungen, $offen nicht erfüllt\n";
exit($offen === 0 ? 0 : 1);
