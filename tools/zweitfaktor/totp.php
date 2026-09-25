<?php
declare(strict_types=1);

/**
 * DER CODE-RECHNER FÜR PHP (P5c/AP5, E-P5c-43). Einer je Sprache, nicht
 * einer je Werkzeug — die beiden anderen sind `totp.mjs` und `totp.py`
 * daneben; alle drei rechnen dasselbe und teilen denselben Zähler.
 *
 * Anlass: F-P5c-33 — mit dem Zweitfaktor für Pflichtrollen käme kein
 * Werkzeug mehr über die Anmeldung des Prüfkontos (einer BetreiberIn).
 *
 * DAS GEHEIMNIS: `NADOKU_TOTP` (Base32), sonst das der Sandbox
 * (`PRUEF_TOTP_SANDBOX`, eingerichtet von `pruefkonto.php`). Auf Staging
 * reicht die Kette das Secret `STAGING_TOTP` durch.
 *
 * KEIN CODE GILT ZWEIMAL (E-P5c-54). Der Server nimmt nur einen Zeitschritt,
 * der größer ist als der zuletzt angenommene. Zwei Anmeldungen im selben
 * 30-Sekunden-Fenster mit demselben Code — die zweite scheitert. Der Rechner
 * führt deshalb einen Zähler in einer Datei (`pruef_totp_zaehlerdatei()`),
 * nimmt den kleinsten Schritt über dem letzten und wartet, wenn er aus dem
 * Fenster des Servers (±1) liefe. Die Datei ist je Geheimnis eine; gesperrt
 * wird mit einer Sperrdatei (`O_EXCL`), weil Node kein `flock` kennt und alle
 * drei Rechner denselben Riegel sehen müssen.
 *
 * Aufruf von der Kommandozeile: `php tools/zweitfaktor/totp.php` gibt den
 * nächsten Code aus.
 */

const PRUEF_TOTP_SANDBOX = 'PRUEFSTANDZWEITFAKTORNADOKU23456';

/** Das Geheimnis in Base32 — aus der Umgebung oder das der Sandbox. */
function pruef_totp_geheimnis(): string
{
    $e = trim((string)getenv('NADOKU_TOTP'));
    return $e !== '' ? $e : PRUEF_TOTP_SANDBOX;
}

/** Base32 (RFC 4648, ohne Auffüllung, Leerzeichen erlaubt) in Rohbytes. */
function pruef_totp_base32(string $b32): string
{
    $a = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ234567';
    $bits = '';
    foreach (str_split(strtoupper(preg_replace('/[\s=]+/', '', $b32) ?? '')) as $z) {
        $i = strpos($a, $z);
        if ($i === false) { throw new InvalidArgumentException('Kein Base32: ' . $z); }
        $bits .= str_pad(decbin($i), 5, '0', STR_PAD_LEFT);
    }
    $roh = '';
    for ($i = 0; $i + 8 <= strlen($bits); $i += 8) { $roh .= chr((int)bindec(substr($bits, $i, 8))); }
    return $roh;
}

/** Der sechsstellige Code für einen Zeitschritt (RFC 4226/6238, SHA-1). */
function pruef_totp_code(string $roh, int $schritt): string
{
    $h = hash_hmac('sha1', pack('J', $schritt), $roh, true);
    $o = ord($h[19]) & 0x0f;
    $n = ((ord($h[$o]) & 0x7f) << 24 | ord($h[$o + 1]) << 16 | ord($h[$o + 2]) << 8 | ord($h[$o + 3]))
       % 1000000;
    return str_pad((string)$n, 6, '0', STR_PAD_LEFT);
}

/** Die Zählerdatei eines Geheimnisses — dieselbe für alle drei Rechner. */
function pruef_totp_zaehlerdatei(string $b32): string
{
    $tmp = rtrim((string)(getenv('TMPDIR') ?: '/tmp'), '/');
    $kennung = substr(hash('sha256', strtoupper(preg_replace('/[\s=]+/', '', $b32) ?? '')), 0, 12);
    return $tmp . '/nadoku-totp-' . $kennung . '.schritt';
}

/**
 * Der nächste Code, den der Server annimmt: der kleinste Zeitschritt über dem
 * zuletzt benutzten, frühestens der jetzige, höchstens der nächste.
 */
function pruef_totp_naechster(?string $b32 = null): string
{
    $b32 = $b32 ?? pruef_totp_geheimnis();
    $datei = pruef_totp_zaehlerdatei($b32);
    $sperre = $datei . '.sperre';
    $bis = microtime(true) + 60;
    while (($h = @fopen($sperre, 'x')) === false) {
        /* Eine Sperre, die älter ist als zehn Sekunden, hat ihr Prozess
         * hinterlassen, als er starb. */
        if (is_file($sperre) && time() - (int)@filemtime($sperre) > 10) { @unlink($sperre); continue; }
        if (microtime(true) > $bis) { throw new RuntimeException('Zählersperre hängt: ' . $sperre); }
        usleep(50000);
    }
    fclose($h);
    try {
        $letzter = is_file($datei) ? (int)trim((string)file_get_contents($datei)) : 0;
        while (true) {
            $jetzt = intdiv(time(), 30);
            $kandidat = max($letzter + 1, $jetzt);
            if ($kandidat <= $jetzt + 1) { break; }
            usleep((int)((($kandidat - 1) * 30 - microtime(true)) * 1e6) + 200000);
        }
        file_put_contents($datei, (string)$kandidat);
        return pruef_totp_code(pruef_totp_base32($b32), $kandidat);
    } finally {
        @unlink($sperre);
    }
}

if (PHP_SAPI === 'cli' && isset($argv[0]) && realpath($argv[0]) === __FILE__) {
    echo pruef_totp_naechster(), "\n";
}
