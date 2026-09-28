<?php
declare(strict_types=1);

/**
 * Passkeyprobe — liest `passkey_lib.php` richtig, und lehnt sie ab, was sie
 * ablehnen muss? (Schritt 18, SR-09)
 *
 * Anlass: Nr. 350 — Passkeys als zweiter Faktor, gebaut ohne
 * WebAuthn-Bibliothek: ein eigener CBOR-Leser und eine eigene Pruefung beider
 * Zeremonien (E-SR-30). Das ist der Code, bei dem ein falsch gelesenes
 * Laengenfeld einen fremden Schluessel annimmt (E-SR-36).
 *
 * WAS SIE MISST, ohne Browser und ohne HTTP, mit einem eigenen Konto:
 *   1. Der CBOR-Leser: Ganzzahlen, Ketten, Listen, Karten; abgelehnt werden
 *      Fliesszahl, Marke, unbestimmte Laenge, abgeschnittene Eingabe, Rest
 *      hinter dem Element, doppelter Schluessel, eine Liste, die mehr Elemente
 *      verspricht, als Bytes da sind, und neun Ebenen (acht gehen).
 *   2. Registrierung ES256 und RS256 durch; abgelehnt: fremder Ursprung,
 *      fremde rpId, fremde oder abgelaufene Herausforderung, vertauschte Art,
 *      UP nicht gesetzt, AT nicht gesetzt, rawId ungleich Kennung, `alg` -8,
 *      fremder `kty`, RSA unter 2048 Bit, eine Adresse, die keine Domain ist.
 *   3. Anmeldung ES256 und RS256 durch, der Zaehler steigt; abgelehnt:
 *      fremder Ursprung, fremde rpId, fremde Herausforderung, vertauschte Art,
 *      UP nicht gesetzt, falsche Signatur, unbekannte Kennung, die Kennung
 *      eines anderen Kontos, ein zurueckgelaufener Zaehler (mit Protokoll
 *      `passkey_zaehler`, der Passkey bleibt); beide Zaehler 0 geht (E-SR-33).
 *   4. Die Tabelle: der elfte Passkey wird abgelehnt, eine Kennung zweimal
 *      ebenso, `pk_entfernen()` nur am eigenen Konto, `totp_abschalten()`
 *      raeumt alle ab (mit Protokoll).
 *
 * WAS SIE NICHT MISST: einen echten Authenticator und einen echten Browser
 * (Bedienweg `einstellungen_profil_passkey.mjs`, Chromium mit virtuellem
 * Authenticator) und die Anbindung an Anmeldung und Endpunkt
 * (Zweitfaktorprobe, Teil 5d).
 *
 * `app.base_url` WIRD FUER DEN LAUF AUF `https://localhost:8443` GESTELLT:
 * Die Sandbox steht auf einer IP-Adresse, und fuer eine IP gibt es keine
 * Passkeys (E-SR-42). Zurueckgelegt wird die Datei byte-gleich, auch nach
 * einem Abbruch (`konfig_stellen()`).
 *
 * Aufruf:  php tools/proben/passkey/probe.php
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht, 2 = nicht gelaufen.
 */

$wurzel = dirname(__DIR__, 3);
$srv = $wurzel . '/server';
require_once $srv . '/db.php';
require_once $srv . '/totp_lib.php';
require_once $srv . '/passkey_lib.php';
require_once $wurzel . '/tools/sandbox/konfig_stellen.php';
require_once __DIR__ . '/bauen.php';

$pdo = db();
$gut = 0; $schlecht = 0;

function pruefe(bool $ok, string $was, string $sonst = ''): void
{
    global $gut, $schlecht;
    if ($ok) { $gut++; echo "  ok    $was\n"; return; }
    $schlecht++;
    echo "  FEHLT $was" . ($sonst !== '' ? " — $sonst" : '') . "\n";
}
/** Wirft `pk_cbor_lesen()`? Liefert die Meldung oder null. */
function cbor_fehler(string $b): ?string
{
    try { pk_cbor_lesen($b); return null; } catch (PkFehler $f) { return $f->getMessage(); }
}

if (!pk_tabelle_da($pdo)) {
    fwrite(STDERR, "Nicht gelaufen: Die Tabelle passkeys fehlt — update.php.\n");
    exit(2);
}

/* ---- Adresse stellen, Konto anlegen ---------------------------------------- */

$app = (array)konfig('app');
$app['base_url'] = 'https://localhost:8443';
$zurueck = konfig_stellen(['app' => $app]);
$u = pk_ursprung();

$mail = 'passkeyprobe@probe.invalid';
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$mail]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Passkeyprobe', 'user', '', '', 320000)")->execute([$mail]);
$uid = (int)$pdo->lastInsertId();
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'Passkeyprobe 2', 'user', '', '', 320000)")->execute(['passkeyprobe-2@probe.invalid']);
$uid2 = (int)$pdo->lastInsertId();
register_shutdown_function(static function () use ($pdo, $uid, $uid2, $zurueck): void {
    $pdo->prepare('DELETE FROM protokoll_ereignisse WHERE betroffen_user_id IN (?, ?)')->execute([$uid, $uid2]);
    $pdo->prepare('DELETE FROM users WHERE id IN (?, ?)')->execute([$uid, $uid2]);
    $zurueck();
});

echo "Passkeyprobe (SR-09) · Ursprung " . ($u['ursprung'] ?? '—') . "\n";

/* ---- 1. Der CBOR-Leser ----------------------------------------------------- */
echo "== 1. CBOR\n";
$w = pk_cbor_lesen(pb_cbor(new PbKarte([1 => 2, -1 => 'ab', 'x' => [0, -1, 23, 24, 255, 65536],
                                        'b' => new PkBytes("\x00\xff")])));
pruefe(is_array($w) && $w[1] === 2 && $w[-1] === 'ab' && $w['x'] === [0, -1, 23, 24, 255, 65536]
       && $w['b'] instanceof PkBytes && $w['b']->b === "\x00\xff",
       'Karte mit Zahlen, Text, Liste und Bytes wird gelesen');
$tief = static function (int $n): string { $s = str_repeat("\x81", $n - 1) . "\x80"; return $s; };
pruefe(cbor_fehler($tief(8)) === null, 'acht Ebenen gehen');
pruefe(cbor_fehler($tief(9)) !== null, 'neun Ebenen: Ablehnung', (string)cbor_fehler($tief(9)));
$faelle = [
    'Fliesszahl (half float)'      => "\xf9\x3c\x00",
    'Fliesszahl (double)'          => "\xfb" . pack('E', 1.5),
    'einfacher Wert (true)'        => "\xf5",
    'Marke (Datum)'                => "\xc1\x1a\x00\x00\x00\x00",
    'unbestimmte Laenge (Liste)'   => "\x9f\x01\xff",
    'unbestimmte Laenge (Bytes)'   => "\x5f\x41\x00\xff",
    'abgeschnitten (Kopf)'         => "\x19\x01",
    'abgeschnitten (Kette)'        => "\x45\x00\x01",
    'Rest hinter dem Element'      => "\x01\x02",
    'doppelter Schluessel'         => "\xa2\x01\x01\x01\x02",
    'Liste verspricht zu viel'     => "\x9a\x7f\xff\xff\xff\x01",
    'Textkette kein UTF-8'         => "\x62\xc3\x28",
    'Schluessel ist eine Liste'    => "\xa1\x80\x01",
    'reservierte Laenge'           => "\x1c",
];
foreach ($faelle as $was => $b) {
    $f = cbor_fehler($b);
    pruefe($f !== null, "Ablehnung: $was", $f ?? 'angenommen');
}

/* ---- 2. Registrierung ------------------------------------------------------ */
echo "== 2. Registrierung\n";
$ec  = pb_paar(-7);
$rsa = pb_paar(-257);
$h = static function (): array { $a = []; pk_herausforderung_stellen($a); return $a; };

$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$regEc = pk_registrierung_pruefen($ablage, $r['antwort']);
pruefe($regEc['ok'] && $regEc['alg'] === -7 && str_contains($regEc['spki'], 'BEGIN PUBLIC KEY'),
       'ES256: angenommen, SPKI gespeichert', $regEc['grund'] ?? '');
$kennEc = $r['kennung'];

$ablage = $h();
$r = pb_registrierung($rsa, $ablage['herausforderung'], $u);
$regRsa = pk_registrierung_pruefen($ablage, $r['antwort']);
pruefe($regRsa['ok'] && $regRsa['alg'] === -257, 'RS256: angenommen', $regRsa['grund'] ?? '');
$kennRsa = $r['kennung'];

$nein = static function (string $was, array $o, ?array $paar = null, ?array $ablage = null) use ($ec, $u, $h): void {
    $ablage ??= $h();
    $r = pb_registrierung($paar ?? $ec, $ablage['herausforderung'], $u, $o);
    $e = pk_registrierung_pruefen($ablage, $r['antwort']);
    pruefe(!$e['ok'], "Registrierung abgelehnt: $was", $e['ok'] ? 'ANGENOMMEN' : $e['grund']);
};
$nein('fremder Ursprung', ['ursprung' => 'https://boese.invalid']);
$nein('fremde rpId', ['rp_id' => 'boese.invalid']);
$nein('fremde Herausforderung', ['herausforderung' => pk_b64u(random_bytes(32))]);
$abgelaufen = $h(); $abgelaufen['bis'] = time() - 1;
$nein('abgelaufene Herausforderung', [], null, $abgelaufen);
$nein('Art webauthn.get', ['art' => 'webauthn.get']);
$nein('UP nicht gesetzt', ['flags' => 0x40]);
$nein('AT nicht gesetzt', ['flags' => 0x01]);
$nein('rawId ungleich Kennung', ['rawId' => random_bytes(32)]);
$nein('alg -8 (EdDSA)', ['cose' => new PbKarte([1 => 1, 3 => -8, -1 => 6, -2 => new PkBytes(random_bytes(32))])]);
$nein('kty fremd (OKP bei -7)', ['cose' => new PbKarte([1 => 1, 3 => -7, -1 => 1,
      -2 => new PkBytes(random_bytes(32)), -3 => new PkBytes(random_bytes(32))])]);
$nein('Punkt nicht auf der Kurve', ['cose' => new PbKarte([1 => 2, 3 => -7, -1 => 1,
      -2 => new PkBytes(str_repeat("\x01", 32)), -3 => new PkBytes(str_repeat("\x02", 32))])]);
$nein('COSE mit Fliesszahl', ['cose' => new PbRoh("\xa1\x01\xf9\x3c\x00")]);
$kurz = pb_paar(-257, 1024);
$nein('RSA mit 1024 Bit', [], $kurz);
/* KEINE DOMAIN: die Anlage auf einer IP-Adresse (und einmal ohne HTTPS). In
 * EINEM EIGENEN PROZESS — `app_url()` merkt sich die Adresse je Anfrage, und
 * in diesem Prozess steht sie schon fest (die erste Fassung fragte hier und
 * bekam die alte Antwort). */
$ursprungBei = static function (string $basis) use ($app, $srv): string {
    $a = $app; $a['base_url'] = $basis;
    $zurueck = konfig_stellen(['app' => $a]);
    $aus = shell_exec('php -r ' . escapeshellarg('require "' . $srv . '/db.php"; require "' . $srv
                      . '/passkey_lib.php"; echo json_encode(pk_ursprung());') . ' 2>&1');
    $zurueck();
    return trim((string)$aus);
};
pruefe($ursprungBei('https://127.0.0.1:8443') === 'null', 'eine IP-Adresse als base_url: keine Passkeys');
pruefe($ursprungBei('http://beispiel.invalid') === 'null', 'ohne HTTPS (ausser localhost): keine Passkeys');
pruefe($ursprungBei('https://beispiel.invalid:443/einsatz') === '{"ursprung":"https:\\/\\/beispiel.invalid","rp_id":"beispiel.invalid"}',
       'Standardport und Unterverzeichnis: Ursprung ohne beides', $ursprungBei('https://beispiel.invalid:443/einsatz'));

/* ---- 3. Anlegen und Anmeldung ---------------------------------------------- */
echo "== 3. Anlegen und Anmeldung\n";
$aEc  = pk_anlegen($uid, $regEc, 'Handy');
$aRsa = pk_anlegen($uid, $regRsa, '');
pruefe($aEc['ok'] && $aRsa['ok'] && pk_zahl($uid) === 2, 'zwei angelegt', json_encode([$aEc, $aRsa]));
pruefe(pk_anzeigename(pk_liste($uid)[1]) !== '' && str_starts_with(pk_anzeigename(pk_liste($uid)[1]), 'Passkey vom '),
       'ohne Bezeichnung: „Passkey vom <Datum>"', pk_anzeigename(pk_liste($uid)[1]));
$doppelt = pk_anlegen($uid2, $regEc, 'fremd');
pruefe(!$doppelt['ok'] && $doppelt['grund'] === 'vorhanden', 'dieselbe Kennung an einem zweiten Konto: abgelehnt');
pruefe(!pk_anlegen($uid, $regEc + [], str_repeat('x', 41))['ok'], 'Bezeichnung mit 41 Zeichen: abgelehnt');

$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 5]));
$z = (int)$pdo->query("SELECT zaehler FROM passkeys WHERE id = {$aEc['id']}")->fetchColumn();
pruefe($an['ok'] && $z === 5, 'ES256: Anmeldung durch, Zaehler 5 gespeichert', $an['grund'] ?? "Zaehler $z");
$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($rsa, $kennRsa, $ablage['herausforderung'], $u, ['zaehler' => 0]));
pruefe($an['ok'], 'RS256: Anmeldung durch (beide Zaehler 0 — synchronisierter Passkey)', $an['grund'] ?? '');
$ablage = $h();
$an = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($rsa, $kennRsa, $ablage['herausforderung'], $u, ['zaehler' => 0]));
pruefe($an['ok'], '… und noch einmal mit 0', $an['grund'] ?? '');

$nicht = static function (string $was, array $o, int $konto = 0, ?array $ablage = null) use ($ec, $kennEc, $u, $h, $uid): void {
    $ablage ??= $h();
    $e = pk_anmeldung_pruefen($konto ?: $uid, $ablage,
                              pb_anmeldung($ec, $o['kennung'] ?? $kennEc, $ablage['herausforderung'], $u, $o + ['zaehler' => 9]));
    pruefe(!$e['ok'], "Anmeldung abgelehnt: $was", $e['ok'] ? 'ANGENOMMEN' : $e['grund']);
};
$nicht('fremder Ursprung', ['ursprung' => 'https://boese.invalid']);
$nicht('fremde rpId', ['rp_id' => 'boese.invalid']);
$nicht('fremde Herausforderung', ['herausforderung' => pk_b64u(random_bytes(32))]);
$nicht('Art webauthn.create', ['art' => 'webauthn.create']);
$nicht('UP nicht gesetzt', ['flags' => 0x04]);
$nicht('falsche Signatur', ['falsch' => true]);
$nicht('unbekannte Kennung', ['kennung' => random_bytes(32)]);
$nicht('die Kennung am falschen Konto', [], $uid2);
$ablage = $h(); $ablage['bis'] = time() - 1;
$nicht('abgelaufene Herausforderung', [], 0, $ablage);

$vor = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_zaehler' AND betroffen_user_id = $uid")->fetchColumn();
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 3]));
$nach = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_zaehler' AND betroffen_user_id = $uid")->fetchColumn();
pruefe(!$e['ok'] && $nach === $vor + 1 && pk_zahl($uid) === 2,
       'Zaehler zurueck (5 → 3): abgelehnt, Protokoll passkey_zaehler, der Passkey bleibt',
       ($e['ok'] ? 'ANGENOMMEN' : $e['grund']) . ", Protokoll $vor → $nach");
$ablage = $h();
$e = pk_anmeldung_pruefen($uid, $ablage, pb_anmeldung($ec, $kennEc, $ablage['herausforderung'], $u, ['zaehler' => 5]));
pruefe(!$e['ok'], 'Zaehler gleich (5 → 5): abgelehnt');

/* ---- 4. Die Tabelle -------------------------------------------------------- */
echo "== 4. Tabelle\n";
for ($i = pk_zahl($uid); $i < PK_HOECHSTENS; $i++) {
    $ablage = $h();
    $r = pb_registrierung($ec, $ablage['herausforderung'], $u);
    pk_anlegen($uid, pk_registrierung_pruefen($ablage, $r['antwort']), 'Nr. ' . ($i + 1));
}
$ablage = $h();
$r = pb_registrierung($ec, $ablage['herausforderung'], $u);
$elfter = pk_anlegen($uid, pk_registrierung_pruefen($ablage, $r['antwort']), 'Nr. 11');
pruefe(pk_zahl($uid) === PK_HOECHSTENS && !$elfter['ok'] && $elfter['grund'] === 'voll',
       'der elfte Passkey: abgelehnt', pk_zahl($uid) . ' da, ' . json_encode($elfter));
pruefe(!pk_entfernen($uid2, $aEc['id']) && pk_zahl($uid) === PK_HOECHSTENS,
       'pk_entfernen() an einem fremden Konto: nichts');
pruefe(pk_entfernen($uid, $aEc['id']) && pk_zahl($uid) === PK_HOECHSTENS - 1, 'pk_entfernen() am eigenen Konto: weg');
$vorP = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_entfernt' AND betroffen_user_id = $uid")->fetchColumn();
totp_abschalten($uid, 'verwaltung');
$nachP = (int)$pdo->query("SELECT COUNT(*) FROM protokoll_ereignisse WHERE art = 'passkey_entfernt' AND betroffen_user_id = $uid")->fetchColumn();
pruefe(pk_zahl($uid) === 0 && $nachP === $vorP + 1,
       'totp_abschalten(): alle Passkeys weg, ein Protokolleintrag', pk_zahl($uid) . " da, Protokoll $vorP → $nachP");

echo "\n$gut ok, $schlecht fehlen\n";
exit($schlecht === 0 ? 0 : 1);
