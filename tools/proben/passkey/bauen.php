<?php
declare(strict_types=1);

/**
 * Ein Authenticator zum Nachbauen — fuer die Passkeyprobe und die
 * Zweitfaktorprobe (Schritt 18, SR-09).
 *
 * Anlass: Nr. 350. Die Proben messen `passkey_lib.php` ohne Browser: Sie
 * erzeugen mit phpseclib Schluesselpaare, bauen daraus `attestationObject`,
 * `clientDataJSON` und Assertions SELBST und legen sie der Bibliothek vor.
 * Was ein echter Authenticator anders macht, misst der Bedienweg mit dem
 * virtuellen Authenticator von Chromium (`einstellungen_profil_passkey.mjs`).
 *
 * DER CBOR-SCHREIBER HIER KANN MEHR ALS DER LESER DER ANWENDUNG — mit
 * Absicht: Fliesszahl, Marke, unbestimmte Laenge und tiefe Verschachtelung
 * baut er, damit die Probe zeigen kann, dass der Leser sie ablehnt.
 */

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\RSA;

/** Eine CBOR-Karte, auch leer (ein leeres PHP-Feld waere sonst eine Liste). */
final class PbKarte { public function __construct(public readonly array $paare) {} }
/** Ein roher CBOR-Schnipsel, unveraendert eingefuegt. */
final class PbRoh { public function __construct(public readonly string $b) {} }

function pb_kopf(int $typ, int $n): string
{
    if ($n < 24)          { return chr(($typ << 5) | $n); }
    if ($n < 0x100)       { return chr(($typ << 5) | 24) . chr($n); }
    if ($n < 0x10000)     { return chr(($typ << 5) | 25) . pack('n', $n); }
    if ($n < 0x100000000) { return chr(($typ << 5) | 26) . pack('N', $n); }
    return chr(($typ << 5) | 27) . pack('J', $n);
}

/** CBOR schreiben: int, string (Text), PkBytes (Bytes), list, PbKarte, PbRoh. */
function pb_cbor(mixed $v): string
{
    if ($v instanceof PbRoh)   { return $v->b; }
    if ($v instanceof PkBytes) { return pb_kopf(2, strlen($v->b)) . $v->b; }
    if ($v instanceof PbKarte) {
        $s = pb_kopf(5, count($v->paare));
        foreach ($v->paare as $k => $w) { $s .= pb_cbor($k) . pb_cbor($w); }
        return $s;
    }
    if (is_int($v))    { return $v >= 0 ? pb_kopf(0, $v) : pb_kopf(1, -1 - $v); }
    if (is_string($v)) { return pb_kopf(3, strlen($v)) . $v; }
    if (is_array($v)) {
        $s = pb_kopf(4, count($v));
        foreach ($v as $w) { $s .= pb_cbor($w); }
        return $s;
    }
    throw new InvalidArgumentException('pb_cbor: unbekannter Typ');
}

/** Ein Schluesselpaar: `alg` -7 (P-256) oder -257 (RSA, `$bits`). */
function pb_paar(int $alg, int $bits = 2048): array
{
    if ($alg === -7) {
        $privat = EC::createKey('secp256r1');
        $jwk = json_decode($privat->getPublicKey()->toString('JWK'), true);
        $j = $jwk['keys'][0] ?? $jwk;
        $cose = new PbKarte([1 => 2, 3 => -7, -1 => 1,
                             -2 => new PkBytes(pk_b64u_lesen($j['x'])),
                             -3 => new PkBytes(pk_b64u_lesen($j['y']))]);
        return ['privat' => $privat, 'alg' => -7, 'cose' => $cose];
    }
    $privat = RSA::createKey($bits);
    $jwk = json_decode($privat->getPublicKey()->toString('JWK'), true);
    $j = $jwk['keys'][0] ?? $jwk;
    $cose = new PbKarte([1 => 3, 3 => $alg,
                         -1 => new PkBytes(pk_b64u_lesen($j['n'])),
                         -2 => new PkBytes(pk_b64u_lesen($j['e']))]);
    return ['privat' => $privat, 'alg' => $alg, 'cose' => $cose];
}

/** Signieren wie der Authenticator: ES256 als DER, RS256 PKCS#1 v1.5. */
function pb_signieren(array $paar, string $daten): string
{
    $k = $paar['alg'] === -7
        ? $paar['privat']->withHash('sha256')->withSignatureFormat('ASN1')
        : $paar['privat']->withHash('sha256')->withPadding(RSA::SIGNATURE_PKCS1);
    return $k->sign($daten);
}

/**
 * Eine Registrierung bauen. `$o` verstellt einzelne Teile fuer die
 * Ablehnungsfaelle: `art`, `herausforderung`, `ursprung`, `rp_id`, `flags`,
 * `cose` (roh, als PbKarte oder PbRoh), `rawId`, `zaehler`, `client` (weitere
 * Felder in `clientDataJSON`, etwa `crossOrigin` — seit H-SR-08).
 *
 * @return array{antwort: array, kennung: string}
 */
function pb_registrierung(array $paar, string $herausforderung, array $u, array $o = []): array
{
    $kennung = $o['kennung'] ?? random_bytes(32);
    $client = json_encode(($o['client'] ?? []) + ['type' => $o['art'] ?? 'webauthn.create',
                           'challenge' => $o['herausforderung'] ?? $herausforderung,
                           'origin' => $o['ursprung'] ?? $u['ursprung'], 'crossOrigin' => false],
                          JSON_UNESCAPED_SLASHES);
    $auth = hash('sha256', $o['rp_id'] ?? $u['rp_id'], true)
          . chr($o['flags'] ?? 0x41)
          . pack('N', $o['zaehler'] ?? 0)
          . str_repeat("\0", 16)
          . pack('n', strlen($kennung)) . $kennung
          . pb_cbor($o['cose'] ?? $paar['cose']);
    $att = pb_cbor(new PbKarte(['fmt' => 'none', 'attStmt' => new PbKarte([]),
                                'authData' => new PkBytes($auth)]));
    return ['antwort' => ['clientDataJSON' => pk_b64u((string)$client),
                          'attestationObject' => pk_b64u($att),
                          'rawId' => pk_b64u($o['rawId'] ?? $kennung)],
            'kennung' => $kennung];
}

/**
 * Eine Anmeldung (Assertion) bauen. `$o`: `art`, `herausforderung`,
 * `ursprung`, `rp_id`, `flags`, `zaehler`, `rawId`, `falsch` (Signatur ueber
 * andere Daten), `client` (weitere Felder in `clientDataJSON`).
 */
function pb_anmeldung(array $paar, string $kennung, string $herausforderung, array $u, array $o = []): array
{
    $client = (string)json_encode(($o['client'] ?? []) + ['type' => $o['art'] ?? 'webauthn.get',
                                   'challenge' => $o['herausforderung'] ?? $herausforderung,
                                   'origin' => $o['ursprung'] ?? $u['ursprung'], 'crossOrigin' => false],
                                  JSON_UNESCAPED_SLASHES);
    $auth = hash('sha256', $o['rp_id'] ?? $u['rp_id'], true)
          . chr($o['flags'] ?? 0x01)
          . pack('N', $o['zaehler'] ?? 1);
    $sig = pb_signieren($paar, ($o['falsch'] ?? false) ? $auth . 'x' : $auth . hash('sha256', $client, true));
    return ['rawId' => pk_b64u($o['rawId'] ?? $kennung), 'clientDataJSON' => pk_b64u($client),
            'authenticatorData' => pk_b64u($auth), 'signature' => pk_b64u($sig)];
}
