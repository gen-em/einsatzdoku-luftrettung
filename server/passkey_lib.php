<?php
declare(strict_types=1);

/**
 * PASSKEYS ALS ZWEITER FAKTOR (seit Web 21.10.0, Schritt 18, SR-09; Nr. 350;
 * E-SR-28 bis -36, E-SR-42).
 *
 * ===========================================================================
 * WAS EIN PASSKEY HIER IST — UND WAS NICHT
 * ===========================================================================
 *
 * Ein WEITERES VERFAHREN DESSELBEN FAKTORS (E-SR-29, -35): Voraussetzung ist
 * der eingeschaltete Zweitfaktor (TOTP); Codes und Rueckweg bleiben der
 * Notweg; `totp_abschalten()` nimmt die Passkeys mit, auf jedem Weg. Im
 * Code-Schritt der Anmeldung ist er der dritte Weg neben App-Code und
 * Wiederherstellungscode und zaehlt wie ein App-Code (E-SR-32) — fuer den
 * Haken „Geraet merken" und fuer den frischen Code.
 *
 * NICHT: ein Ersatz des Passworts, eine Quelle fuer den Datenschluessel (kein
 * PRF, E-SR-28), ein Faktor ohne TOTP (Nr. 351).
 *
 * ===========================================================================
 * DIE EINE STELLE FUER WEBAUTHN (R83), OHNE FREMDBESTANDTEIL (E-SR-30)
 * ===========================================================================
 *
 * Drei Teile, alle hier:
 *
 *   1. EIN CBOR-LESER fuer genau die Teilmenge, die Registrierung und COSE
 *      brauchen: Ganzzahlen, Byte- und Textketten, Listen und Karten
 *      BESTIMMTER Laenge, hoechstens acht Ebenen tief. Fliesszahlen, Marken,
 *      einfache Werte, unbestimmte Laengen, doppelte Schluessel und ein Rest
 *      hinter dem Element sind eine Ablehnung — ein Leser, der mehr annimmt,
 *      als er braucht, ist die Stelle, an der ein falsch gelesenes Laengenfeld
 *      einen fremden Schluessel durchlaesst (E-SR-36).
 *   2. DIE ZWEI ZEREMONIEN. Registrierung: `clientDataJSON` (Art, gestellte
 *      Herausforderung, Ursprung) und `attestationObject` (`authData` mit
 *      `rpIdHash`, UP und AT, Zaehler, Kennung, COSE-Schluessel). Das Format
 *      der Attestation (`fmt`) wird gelesen und NICHT geprueft — verlangt
 *      wird `none`; wer den Hersteller des Authenticators wissen wollte,
 *      zahlte mit Datenschutz fuer nichts (E-SR-30, R36). Anmeldung: Art,
 *      Herausforderung, Ursprung, `rpIdHash`, UP, die Signatur ueber
 *      `authData ‖ sha256(clientDataJSON)`, der Zaehler (E-SR-33).
 *   3. DIE SIGNATUR UEBER PHPSECLIB, wie beim Rueckweg: `Crypt/EC` fuer ES256
 *      (-7, P-256, DER-Signatur), `Crypt/RSA` fuer RS256 (-257, PKCS#1 v1.5
 *      mit SHA-256, mindestens 2048 Bit). Andere Verfahren gibt es nicht.
 *
 * DER OEFFENTLICHE SCHLUESSEL WIRD BEI DER REGISTRIERUNG NACH SPKI UEBERFUEHRT
 * (PEM) und so gespeichert: Die Anmeldung laedt ihn mit `PublicKeyLoader`
 * und kommt ohne CBOR aus.
 *
 * UV IST `preferred` UND WIRD NICHT VERLANGT: Das Passwort ist das Wissen, der
 * Passkey der Besitz; wer UV verlangte, schloesse Hardware-Schluessel ohne PIN
 * aus. UP (die Nutzergeste) wird verlangt.
 *
 * ===========================================================================
 * DER EIGENE URSPRUNG KOMMT AUS DER KONFIGURATION (E-SR-42)
 * ===========================================================================
 *
 * `rp.id` ist der Host aus `app.base_url` (`app_url()`), der Ursprung dessen
 * Schema, Host und Port. NICHT die `Host`-Kopfzeile der Anfrage: Die setzt,
 * wer die Anfrage schickt. Antwortete die Anlage auch unter einem fremden
 * Namen, liesse ein Phishing-Proxy, der seine Anfragen mit seinem eigenen
 * Namen weiterreicht, seinen Ursprung als den eigenen gelten — genau das,
 * wogegen WebAuthn schuetzt. Das Konzept hatte hier die Stelle in
 * `kopfzeilen_lib.php` vorgesehen, die den Host liest; die ist fuer den
 * Berichtsendpunkt der CSP richtig (dort MUSS es der Name der laufenden
 * Anfrage sein) und fuer diese Frage falsch.
 *
 * Folge: Unter einer anderen Adresse als `app.base_url` gibt es keine
 * Passkeys (der Browser wuerde die Zeremonie fuer den falschen Namen
 * ablehnen), und eine IP-Adresse als `base_url` taugt nicht — Browser lassen
 * WebAuthn nur fuer Domainnamen zu. `pk_verfuegbar()` sagt beides.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/instanz_lib.php';     // app_url()
require_once __DIR__ . '/vendor/laden.php';    // phpseclib

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;

/** Hoechstens so viele Passkeys je Konto (E-SR-31). */
const PK_HOECHSTENS = 10;
/** So lange gilt die Herausforderung der Registrierung (E-SR-31). */
const PK_REG_S = 600;
/** Die Bezeichnung, hoechstens so viele Zeichen; leer heisst „Passkey vom …". */
const PK_BEZEICHNUNG_MAX = 40;
/** Die zwei Verfahren (COSE-Kennung → Name). Andere sind eine Ablehnung. */
const PK_ALGS = [-7 => 'ES256', -257 => 'RS256'];
/** Die Kennung eines Passkeys, hoechstens so viele Bytes (WebAuthn L2 5.1). */
const PK_KENNUNG_MAX = 1023;
/** Tiefer als acht Ebenen liest der CBOR-Leser nicht (E-SR-30). */
const PK_CBOR_TIEFE = 8;
/** RSA-Schluessel darunter werden abgelehnt. */
const PK_RSA_BITS_MIN = 2048;

/** Eine Byte-Kette aus CBOR — getrennt von einer Textkette, die ein PHP-String bleibt. */
final class PkBytes
{
    public function __construct(public readonly string $b) {}
}

/** Jede Ablehnung beim Lesen oder Pruefen. Die Meldung ist fuer das Protokoll
 *  der Probe, nicht fuer die Oberflaeche. */
final class PkFehler extends RuntimeException {}

/* ===========================================================================
 * Base64url — die eine Stelle im Server (der Browser hat seine in passkey.js)
 * ======================================================================== */

function pk_b64u(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/** Streng: nur das Alphabet von Base64url, ohne Fuellzeichen; sonst null. */
function pk_b64u_lesen(string $text): ?string
{
    if ($text === '' || !preg_match('/^[A-Za-z0-9_-]+$/', $text) || strlen($text) % 4 === 1) {
        return null;
    }
    $roh = base64_decode(strtr($text, '-_', '+/'), true);
    return $roh === false ? null : $roh;
}

/* ===========================================================================
 * 1. Der CBOR-Leser (RFC 8949, nur die Teilmenge von E-SR-30)
 * ======================================================================== */

/**
 * Ein ganzes CBOR-Element lesen — ein Rest dahinter ist eine Ablehnung.
 *
 * Rueckgabe: int, string (Textkette), PkBytes (Byte-Kette), list (Liste) oder
 * array mit int- oder string-Schluesseln (Karte).
 *
 * @throws PkFehler
 */
function pk_cbor_lesen(string $bytes): mixed
{
    $pos = 0;
    $wert = pk_cbor_element($bytes, $pos, 1);
    if ($pos !== strlen($bytes)) { throw new PkFehler('CBOR: Rest hinter dem Element'); }
    return $wert;
}

/**
 * Ein Element ab `$pos` lesen und `$pos` dahinter setzen. Fuer `authData`, wo
 * hinter dem COSE-Schluessel noch Erweiterungen stehen koennen.
 *
 * @throws PkFehler
 */
function pk_cbor_element(string $b, int &$pos, int $tiefe): mixed
{
    if ($tiefe > PK_CBOR_TIEFE) { throw new PkFehler('CBOR: tiefer als ' . PK_CBOR_TIEFE . ' Ebenen'); }
    $laenge = strlen($b);
    if ($pos >= $laenge) { throw new PkFehler('CBOR: abgeschnitten'); }
    $erstes = ord($b[$pos++]);
    $typ  = $erstes >> 5;
    $info = $erstes & 0x1f;

    if ($typ === 6) { throw new PkFehler('CBOR: Marken werden nicht gelesen'); }
    if ($typ === 7) { throw new PkFehler('CBOR: Fliesszahlen und einfache Werte werden nicht gelesen'); }
    if ($info === 31) { throw new PkFehler('CBOR: unbestimmte Laenge'); }
    if ($info >= 28) { throw new PkFehler('CBOR: reservierte Laengenangabe'); }

    if ($info < 24) {
        $n = $info;
    } else {
        $stellen = [24 => 1, 25 => 2, 26 => 4, 27 => 8][$info];
        if ($pos + $stellen > $laenge) { throw new PkFehler('CBOR: abgeschnitten'); }
        $n = 0;
        for ($i = 0; $i < $stellen; $i++) {
            /* Acht Bytes passen nur, solange das oberste Bit frei ist — PHP
               kennt keine vorzeichenlose 64-Bit-Zahl. Groesser braucht es
               hier nichts; groesser ist eine Ablehnung, kein Ueberlauf. */
            if ($n > (PHP_INT_MAX >> 8)) { throw new PkFehler('CBOR: Zahl zu gross'); }
            $n = ($n << 8) | ord($b[$pos++]);
        }
    }

    switch ($typ) {
        case 0: return $n;
        case 1: return -1 - $n;
        case 2:
        case 3:
            if ($n > $laenge - $pos) { throw new PkFehler('CBOR: Kette laenger als der Rest'); }
            $kette = substr($b, $pos, $n);
            $pos += $n;
            if ($typ === 2) { return new PkBytes($kette); }
            if (!mb_check_encoding($kette, 'UTF-8')) { throw new PkFehler('CBOR: Textkette ist kein UTF-8'); }
            return $kette;
        case 4:
            /* Jedes Element braucht mindestens ein Byte — eine Liste, die mehr
               verspricht, als Bytes uebrig sind, ist gelogen und wird nicht
               erst Element fuer Element abgearbeitet. */
            if ($n > $laenge - $pos) { throw new PkFehler('CBOR: Liste laenger als der Rest'); }
            $liste = [];
            for ($i = 0; $i < $n; $i++) { $liste[] = pk_cbor_element($b, $pos, $tiefe + 1); }
            return $liste;
        default: // 5: Karte
            if ($n * 2 > $laenge - $pos) { throw new PkFehler('CBOR: Karte laenger als der Rest'); }
            $karte = [];
            for ($i = 0; $i < $n; $i++) {
                $schluessel = pk_cbor_element($b, $pos, $tiefe + 1);
                if (!is_int($schluessel) && !is_string($schluessel)) {
                    throw new PkFehler('CBOR: Schluessel einer Karte ist weder Zahl noch Text');
                }
                /* PHP macht aus dem Text "1" den Schluessel 1 — dann waeren
                   zwei verschiedene CBOR-Schluessel einer. Als doppelt zaehlt
                   deshalb auch das. */
                if (array_key_exists($schluessel, $karte)) { throw new PkFehler('CBOR: doppelter Schluessel'); }
                $karte[$schluessel] = pk_cbor_element($b, $pos, $tiefe + 1);
            }
            return $karte;
    }
}

/* ===========================================================================
 * 2. COSE → phpseclib → SPKI
 * ======================================================================== */

/**
 * Einen COSE-Schluessel (RFC 9053) in einen oeffentlichen Schluessel von
 * phpseclib ueberfuehren. Nur EC2/P-256 mit -7 und RSA mit -257.
 *
 * @return array{schluessel: EC\PublicKey|RSA\PublicKey, alg: int}
 * @throws PkFehler
 */
function pk_cose_laden(mixed $cose): array
{
    if (!is_array($cose) || array_is_list($cose)) { throw new PkFehler('COSE: keine Karte'); }
    $kty = $cose[1] ?? null;
    $alg = $cose[3] ?? null;
    if (!is_int($alg) || !isset(PK_ALGS[$alg])) { throw new PkFehler('COSE: Verfahren nicht erlaubt'); }
    $bytes = static fn(mixed $v): ?string => $v instanceof PkBytes ? $v->b : null;
    try {
        if ($alg === -7) {
            $x = $bytes($cose[-2] ?? null);
            $y = $bytes($cose[-3] ?? null);
            if ($kty !== 2 || ($cose[-1] ?? null) !== 1 || $x === null || $y === null
                || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new PkFehler('COSE: kein EC2-Schluessel auf P-256');
            }
            $k = PublicKeyLoader::loadPublicKey((string)json_encode(
                ['kty' => 'EC', 'crv' => 'P-256', 'x' => pk_b64u($x), 'y' => pk_b64u($y)]));
            if (!$k instanceof EC\PublicKey) { throw new PkFehler('COSE: kein EC-Schluessel'); }
            return ['schluessel' => $k, 'alg' => $alg];
        }
        $n = $bytes($cose[-1] ?? null);
        $e = $bytes($cose[-2] ?? null);
        if ($kty !== 3 || $n === null || $e === null || $n === '' || $e === '') {
            throw new PkFehler('COSE: kein RSA-Schluessel');
        }
        $k = PublicKeyLoader::loadPublicKey((string)json_encode(
            ['kty' => 'RSA', 'n' => pk_b64u($n), 'e' => pk_b64u($e)]));
        if (!$k instanceof RSA\PublicKey) { throw new PkFehler('COSE: kein RSA-Schluessel'); }
        if ($k->getLength() < PK_RSA_BITS_MIN) { throw new PkFehler('COSE: RSA-Schluessel zu kurz'); }
        return ['schluessel' => $k, 'alg' => $alg];
    } catch (PkFehler $f) {
        throw $f;
    } catch (Throwable $t) {
        throw new PkFehler('COSE: phpseclib lehnt den Schluessel ab');
    }
}

/** Den gespeicherten Schluessel (SPKI als PEM) laden — oder null. */
function pk_spki_laden(string $pem, int $alg): EC\PublicKey|RSA\PublicKey|null
{
    try {
        $k = PublicKeyLoader::loadPublicKey($pem);
    } catch (Throwable) {
        return null;
    }
    if ($alg === -7 && $k instanceof EC\PublicKey && $k->getCurve() === 'secp256r1') { return $k; }
    if ($alg === -257 && $k instanceof RSA\PublicKey) { return $k; }
    return null;
}

/* ===========================================================================
 * 3. Ursprung, Herausforderung, authData
 * ======================================================================== */

/**
 * Der eigene Ursprung und die `rp.id` — aus `app.base_url` (E-SR-42).
 *
 * @return array{ursprung: string, rp_id: string}|null  null: keine taugliche
 *         Adresse (leer, kein HTTPS ausser `localhost`, eine IP-Adresse)
 */
function pk_ursprung(): ?array
{
    $basis = app_url();
    $teile = parse_url($basis);
    $schema = strtolower((string)($teile['scheme'] ?? ''));
    $host   = strtolower((string)($teile['host'] ?? ''));
    if ($host === '' || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) { return null; }
    if ($schema !== 'https' && !($schema === 'http' && $host === 'localhost')) { return null; }
    /* Der Standardport gehoert nicht in den Ursprung — der Browser laesst ihn
     * weg, und ein `https://host:443` in `base_url` braeche sonst jeden
     * Vergleich. */
    $p = isset($teile['port']) ? (int)$teile['port'] : null;
    $port = ($p === null || ($schema === 'https' && $p === 443) || ($schema === 'http' && $p === 80))
          ? '' : ':' . $p;
    return ['ursprung' => $schema . '://' . $host . $port, 'rp_id' => $host];
}

/** Kann diese Anlage Passkeys? (Tabelle da, Adresse tauglich) */
function pk_verfuegbar(): bool
{
    return pk_tabelle_da() && pk_ursprung() !== null;
}

/** Gibt es die Tabelle schon? Vor `update.php` ist alles stumm. */
function pk_tabelle_da(?PDO $pdo = null): bool
{
    static $da = null;
    if ($da !== null && $pdo === null) { return $da; }
    $ergebnis = db_hat_tabelle($pdo ?? db(), 'passkeys');
    if ($pdo === null) { $da = $ergebnis; }
    return $ergebnis;
}

/**
 * Eine Herausforderung stellen und in der Ablage vermerken — nach dem Muster
 * von `rw_herausforderung_stellen()`. `$ablage` ist ein Fach der Sitzung
 * (`$_SESSION['passkey_reg']` bei der Registrierung, `totp_halb['passkey']`
 * im Code-Schritt, `$_SESSION['passkey_best']` auf der Bestaetigung).
 *
 * @return string die Herausforderung, Base64url
 */
function pk_herausforderung_stellen(array &$ablage, int $gueltig_s = PK_REG_S): string
{
    $h = pk_b64u(random_bytes(32));
    $ablage['herausforderung'] = $h;
    $ablage['bis'] = time() + $gueltig_s;
    return $h;
}

/**
 * `authData` zerlegen (WebAuthn L2 6.1). Mit AT folgen AAGUID, Kennung und
 * COSE-Schluessel; mit ED eine Karte der Erweiterungen; danach nichts mehr.
 *
 * @return array{rp_hash:string, flags:int, zaehler:int, kennung:?string, cose:mixed}
 * @throws PkFehler
 */
function pk_authdata_lesen(string $a, bool $mitSchluessel): array
{
    if (strlen($a) < 37) { throw new PkFehler('authData: zu kurz'); }
    $flags = ord($a[32]);
    $zaehler = unpack('N', substr($a, 33, 4))[1];
    $pos = 37;
    $kennung = null; $cose = null;
    $at = ($flags & 0x40) !== 0;
    if ($mitSchluessel !== $at) {
        throw new PkFehler($mitSchluessel ? 'authData: AT fehlt' : 'authData: AT bei einer Anmeldung');
    }
    if ($at) {
        if (strlen($a) < $pos + 18) { throw new PkFehler('authData: Schluesselteil abgeschnitten'); }
        $pos += 16;                                         // AAGUID — gelesen, nicht verwendet (E-SR-31)
        $lk = unpack('n', substr($a, $pos, 2))[1];
        $pos += 2;
        if ($lk < 1 || $lk > PK_KENNUNG_MAX || strlen($a) < $pos + $lk) {
            throw new PkFehler('authData: Kennung ungueltig');
        }
        $kennung = substr($a, $pos, $lk);
        $pos += $lk;
        $cose = pk_cbor_element($a, $pos, 1);
    }
    if (($flags & 0x80) !== 0) { pk_cbor_element($a, $pos, 1); }   // Erweiterungen: gelesen, nicht verwendet
    if ($pos !== strlen($a)) { throw new PkFehler('authData: Rest hinter den Daten'); }
    return ['rp_hash' => substr($a, 0, 32), 'flags' => $flags, 'zaehler' => $zaehler,
            'kennung' => $kennung, 'cose' => $cose];
}

/**
 * `clientDataJSON` pruefen: Art, Herausforderung, Ursprung.
 *
 * @throws PkFehler
 */
function pk_client_pruefen(string $json, string $art, string $herausforderung, string $ursprung): void
{
    $c = json_decode($json, true);
    if (!is_array($c)) { throw new PkFehler('clientData: kein JSON-Objekt'); }
    if (($c['type'] ?? null) !== $art) { throw new PkFehler('clientData: falsche Art'); }
    if (!is_string($c['challenge'] ?? null) || !hash_equals($herausforderung, $c['challenge'])) {
        throw new PkFehler('clientData: fremde Herausforderung');
    }
    if (($c['origin'] ?? null) !== $ursprung) { throw new PkFehler('clientData: fremder Ursprung'); }
    if (($c['crossOrigin'] ?? false) === true) { throw new PkFehler('clientData: aus einem fremden Rahmen'); }
}

/* ===========================================================================
 * 4. Die zwei Zeremonien
 * ======================================================================== */

/**
 * Eine Registrierung pruefen.
 *
 * `$ablage` ist das Fach mit der gestellten Herausforderung; `$antwort` die
 * Felder `clientDataJSON`, `attestationObject`, `rawId` (Base64url). Die
 * Herausforderung verbraucht der AUFRUFER, gleich welcher Ausgang.
 *
 * @return array{ok:true, kennung:string, spki:string, alg:int, zaehler:int}
 *       | array{ok:false, grund:string}
 */
function pk_registrierung_pruefen(array $ablage, array $antwort): array
{
    try {
        $u = pk_ursprung();
        if ($u === null) { throw new PkFehler('Anlage: keine taugliche Adresse'); }
        if (!is_string($ablage['herausforderung'] ?? null) || (int)($ablage['bis'] ?? 0) < time()) {
            throw new PkFehler('Herausforderung fehlt oder ist abgelaufen');
        }
        $client = pk_b64u_lesen((string)($antwort['clientDataJSON'] ?? ''));
        $att    = pk_b64u_lesen((string)($antwort['attestationObject'] ?? ''));
        $rawId  = pk_b64u_lesen((string)($antwort['rawId'] ?? ''));
        if ($client === null || $att === null || $rawId === null) { throw new PkFehler('Antwort: Felder fehlen'); }
        pk_client_pruefen($client, 'webauthn.create', $ablage['herausforderung'], $u['ursprung']);

        $obj = pk_cbor_lesen($att);
        if (!is_array($obj) || !is_string($obj['fmt'] ?? null) || !is_array($obj['attStmt'] ?? null)
            || !($obj['authData'] ?? null) instanceof PkBytes) {
            throw new PkFehler('attestationObject: Aufbau');
        }
        $a = pk_authdata_lesen($obj['authData']->b, true);
        if (!hash_equals(hash('sha256', $u['rp_id'], true), $a['rp_hash'])) {
            throw new PkFehler('authData: fremde rpId');
        }
        if (($a['flags'] & 0x01) === 0) { throw new PkFehler('authData: UP fehlt'); }
        if (!hash_equals((string)$a['kennung'], $rawId)) { throw new PkFehler('Kennung passt nicht zu rawId'); }
        $k = pk_cose_laden($a['cose']);
        return ['ok' => true, 'kennung' => pk_b64u((string)$a['kennung']),
                'spki' => (string)$k['schluessel']->toString('PKCS8'), 'alg' => $k['alg'],
                'zaehler' => $a['zaehler']];
    } catch (PkFehler $f) {
        return ['ok' => false, 'grund' => $f->getMessage()];
    } catch (Throwable $t) {
        return ['ok' => false, 'grund' => 'unerwartet: ' . get_class($t)];
    }
}

/**
 * Eine Anmeldung (Assertion) pruefen — gegen die Passkeys DIESES Kontos.
 *
 * `$antwort`: `rawId`, `clientDataJSON`, `authenticatorData`, `signature`
 * (Base64url). Der Zaehler folgt E-SR-33: neu > alt oder beide 0 → gut; sonst
 * Ablehnung und Protokoll `passkey_zaehler`, der Passkey bleibt. Bei Erfolg
 * werden Zaehler und `zuletzt_am` fortgeschrieben — sonst nichts, und nichts
 * ins Protokoll (E-SR-33: je Anmeldung waere Rauschen).
 *
 * @return array{ok:true, id:int}|array{ok:false, grund:string}
 */
function pk_anmeldung_pruefen(int $userId, array $ablage, array $antwort): array
{
    try {
        $u = pk_ursprung();
        if ($u === null) { throw new PkFehler('Anlage: keine taugliche Adresse'); }
        if (!is_string($ablage['herausforderung'] ?? null) || (int)($ablage['bis'] ?? 0) < time()) {
            throw new PkFehler('Herausforderung fehlt oder ist abgelaufen');
        }
        $rawId  = pk_b64u_lesen((string)($antwort['rawId'] ?? ''));
        $client = pk_b64u_lesen((string)($antwort['clientDataJSON'] ?? ''));
        $auth   = pk_b64u_lesen((string)($antwort['authenticatorData'] ?? ''));
        $sig    = pk_b64u_lesen((string)($antwort['signature'] ?? ''));
        if ($rawId === null || $client === null || $auth === null || $sig === null) {
            throw new PkFehler('Antwort: Felder fehlen');
        }
        $st = db()->prepare('SELECT id, oeffentlich, alg, zaehler FROM passkeys
                              WHERE user_id = ? AND credential_id = ?');
        $st->execute([$userId, pk_b64u($rawId)]);
        $pk = $st->fetch(PDO::FETCH_ASSOC);
        if (!$pk) { throw new PkFehler('unbekannte Kennung'); }

        pk_client_pruefen($client, 'webauthn.get', $ablage['herausforderung'], $u['ursprung']);
        $a = pk_authdata_lesen($auth, false);
        if (!hash_equals(hash('sha256', $u['rp_id'], true), $a['rp_hash'])) {
            throw new PkFehler('authData: fremde rpId');
        }
        if (($a['flags'] & 0x01) === 0) { throw new PkFehler('authData: UP fehlt'); }

        $k = pk_spki_laden((string)$pk['oeffentlich'], (int)$pk['alg']);
        if ($k === null) { throw new PkFehler('gespeicherter Schluessel laesst sich nicht laden'); }
        $daten = $auth . hash('sha256', $client, true);
        $k = (int)$pk['alg'] === -7
            ? $k->withHash('sha256')->withSignatureFormat('ASN1')
            : $k->withHash('sha256')->withPadding(RSA::SIGNATURE_PKCS1);
        if ($k->verify($daten, $sig) !== true) { throw new PkFehler('Signatur passt nicht'); }

        $alt = (int)$pk['zaehler'];
        $neu = $a['zaehler'];
        if (!($neu > $alt || ($neu === 0 && $alt === 0))) {
            require_once __DIR__ . '/protokoll_lib.php';
            protokoll('verwaltung', 'passkey_zaehler',
                      'Passkey mit zurückgelaufenem Zähler abgewiesen — vielleicht eine Kopie',
                      ['passkey' => (int)$pk['id'], 'alt' => $alt, 'neu' => $neu], $userId);
            throw new PkFehler('Zaehler zurueckgelaufen');
        }
        db()->prepare('UPDATE passkeys SET zaehler = ?, zuletzt_am = UTC_TIMESTAMP() WHERE id = ?')
            ->execute([$neu, (int)$pk['id']]);
        return ['ok' => true, 'id' => (int)$pk['id']];
    } catch (PkFehler $f) {
        return ['ok' => false, 'grund' => $f->getMessage()];
    } catch (Throwable $t) {
        return ['ok' => false, 'grund' => 'unerwartet: ' . get_class($t)];
    }
}

/* ===========================================================================
 * 5. Die Tabelle
 * ======================================================================== */

/**
 * Die Passkeys eines Kontos, aelteste zuerst.
 *
 * @return list<array{id:int, kennung:string, bezeichnung:string, angelegt_am:string, zuletzt_am:?string}>
 */
function pk_liste(int $userId): array
{
    if (!pk_tabelle_da()) { return []; }
    $st = db()->prepare('SELECT id, credential_id, bezeichnung, angelegt_am, zuletzt_am
                           FROM passkeys WHERE user_id = ? ORDER BY angelegt_am, id');
    $st->execute([$userId]);
    return array_map(static fn(array $z): array => [
        'id' => (int)$z['id'], 'kennung' => (string)$z['credential_id'],
        'bezeichnung' => (string)$z['bezeichnung'],
        'angelegt_am' => (string)$z['angelegt_am'], 'zuletzt_am' => $z['zuletzt_am'],
    ], $st->fetchAll(PDO::FETCH_ASSOC));
}

/** Wie viele Passkeys hat das Konto? */
function pk_zahl(int $userId): int
{
    if (!pk_tabelle_da()) { return 0; }
    $st = db()->prepare('SELECT COUNT(*) FROM passkeys WHERE user_id = ?');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

/**
 * Eine gepruefte Registrierung ablegen. Der elfte wird abgelehnt; eine
 * Kennung, die es schon gibt (auch bei einem anderen Konto), ebenso.
 *
 * @return array{ok:true, id:int}|array{ok:false, grund:string}
 */
function pk_anlegen(int $userId, array $reg, string $bezeichnung): array
{
    $bezeichnung = trim($bezeichnung);
    if (mb_strlen($bezeichnung) > PK_BEZEICHNUNG_MAX) {
        return ['ok' => false, 'grund' => 'bezeichnung'];
    }
    return db_transaktion(db(), static function (PDO $pdo) use ($userId, $reg, $bezeichnung): array {
        /* FOR UPDATE auf der Kontozeile: Zwei Fenster, die gleichzeitig den
         * zehnten anlegen, legen genau einen an. */
        $pdo->prepare('SELECT id FROM users WHERE id = ? FOR UPDATE')->execute([$userId]);
        $st = $pdo->prepare('SELECT COUNT(*) FROM passkeys WHERE user_id = ?');
        $st->execute([$userId]);
        if ((int)$st->fetchColumn() >= PK_HOECHSTENS) { return ['ok' => false, 'grund' => 'voll']; }
        $st = $pdo->prepare('SELECT COUNT(*) FROM passkeys WHERE credential_id = ?');
        $st->execute([$reg['kennung']]);
        if ((int)$st->fetchColumn() > 0) { return ['ok' => false, 'grund' => 'vorhanden']; }
        $pdo->prepare('INSERT INTO passkeys (user_id, credential_id, oeffentlich, alg, zaehler, bezeichnung, angelegt_am)
                       VALUES (?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')
            ->execute([$userId, $reg['kennung'], $reg['spki'], $reg['alg'], $reg['zaehler'], $bezeichnung]);
        return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
    });
}

/** Einen Passkey DIESES Kontos entfernen; wahr, wenn es ihn gab. */
function pk_entfernen(int $userId, int $id): bool
{
    if (!pk_tabelle_da()) { return false; }
    $st = db()->prepare('DELETE FROM passkeys WHERE id = ? AND user_id = ?');
    $st->execute([$id, $userId]);
    return $st->rowCount() === 1;
}

/**
 * Alle Passkeys eines Kontos entfernen — gerufen aus `totp_abschalten()`
 * (jeder Weg). Ein Protokolleintrag nur, wenn etwas geloescht wurde.
 *
 * @return int Zahl der geloeschten
 */
function pk_alle_entfernen(int $userId, string $weg): int
{
    if (!pk_tabelle_da()) { return 0; }
    $st = db()->prepare('DELETE FROM passkeys WHERE user_id = ?');
    $st->execute([$userId]);
    $n = $st->rowCount();
    if ($n > 0) {
        require_once __DIR__ . '/protokoll_lib.php';
        protokoll('verwaltung', 'passkey_entfernt',
                  'Passkeys entfernt (' . $n . ') — mit dem Zweitfaktor',
                  ['weg' => $weg, 'anzahl' => $n], $userId);
    }
    return $n;
}

/** Die Kennungen fuer `allowCredentials` / `excludeCredentials` (Base64url). */
function pk_kennungen(int $userId): array
{
    return array_column(pk_liste($userId), 'kennung');
}

/** Die Bezeichnung zur Anzeige — leer heisst „Passkey vom <Datum>". */
function pk_anzeigename(array $pk): string
{
    if ($pk['bezeichnung'] !== '') { return $pk['bezeichnung']; }
    require_once __DIR__ . '/format_lib.php';
    return 'Passkey vom ' . datum_text((string)$pk['angelegt_am']);
}
