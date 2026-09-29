<?php
declare(strict_types=1);

/**
 * PASSKEYS ALS ZWEITER FAKTOR (seit Web 21.10.0, Schritt 18, SR-09; Nr. 350;
 * E-SR-28 bis -36, E-SR-42; gehaertet mit der Gegenlesung H-SR-08 in Web
 * 21.11.0, F-SR-33 bis -52, E-SR-44 bis -53).
 *
 * ===========================================================================
 * WAS EIN PASSKEY HIER IST — UND WAS NICHT
 * ===========================================================================
 *
 * Ein WEITERES VERFAHREN DESSELBEN FAKTORS (E-SR-29, -35): Voraussetzung ist
 * der eingeschaltete Zweitfaktor (TOTP); Codes und Rueckweg bleiben der
 * Notweg; `totp_abschalten()` nimmt die Passkeys mit, auf jedem Weg und in
 * derselben Transaktion (F-SR-38). Im Code-Schritt der Anmeldung ist er der
 * dritte Weg neben App-Code und Wiederherstellungscode und zaehlt wie ein
 * App-Code (E-SR-32) — fuer den Haken „Geraet merken" und fuer den frischen
 * Code. Er haengt NICHT am Serverschluessel (E-SR-49): Laesst sich das
 * TOTP-Geheimnis nach einem Wiederanlauf nicht oeffnen, meldet er weiter an.
 *
 * NICHT: ein Ersatz des Passworts, eine Quelle fuer den Datenschluessel (kein
 * PRF, E-SR-28), ein Faktor ohne TOTP (Nr. 351). Und der Code-Schritt wird
 * durch ihn nicht phishingfest: Die Passkey-ANTWORT ist an die Adresse
 * gebunden, der App-Code daneben bleibt abfischbar (F-SR-44).
 *
 * ===========================================================================
 * DIE EINE STELLE FUER WEBAUTHN (R83), OHNE FREMDBESTANDTEIL (E-SR-30)
 * ===========================================================================
 *
 * Drei Teile, alle hier:
 *
 *   1. EIN CBOR-LESER fuer genau die Teilmenge, die Registrierung und COSE
 *      brauchen: Ganzzahlen, Byte- und Textketten, Listen und Karten
 *      BESTIMMTER Laenge, hoechstens acht Behaelter tief (Listen und Karten
 *      zaehlen, Werte darin nicht — F-SR-34). Fliesszahlen, Marken, einfache
 *      Werte, unbestimmte Laengen, doppelte Schluessel, Textschluessel, die
 *      wie eine Zahl aussehen (PHP machte aus "1" den Schluessel 1 —
 *      F-SR-34), und ein Rest hinter dem Element sind eine Ablehnung — ein
 *      Leser, der mehr annimmt, als er braucht, ist die Stelle, an der ein
 *      falsch gelesenes Laengenfeld einen fremden Schluessel durchlaesst
 *      (E-SR-36). Wo eine Karte verlangt ist (attestationObject,
 *      COSE-Schluessel, Erweiterungen), prueft der Aufrufer die Art am
 *      Kopfbyte (`pk_cbor_art()`), denn gelesen sind Karte und Liste beide
 *      ein PHP-Feld.
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
 *      mit SHA-256). Andere Verfahren gibt es nicht. RSA-Schluessel haben
 *      2048 bis 4096 Bit und einen ungeraden Exponenten von 3 bis 64 Bit
 *      (F-SR-33): Ohne Obergrenze kostete eine einzige Pruefung mit einem
 *      selbst gebauten Schluessel Sekunden bis Minuten Rechenzeit, und mit
 *      e = 1 liesse sich die Signatur ohne Geheimnis rechnen.
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
 * WAS BEWUSST SO BLEIBT (H-SR-08, F-SR-44)
 * ===========================================================================
 *
 * - DER IST NICHT GANZ STRENG: Die Form der ES256-Signatur prueft
 *   `pk_es256_form()` selbst — eine Folge mit genau zwei INTEGER zu 1 bis 33
 *   Byte, hoechstens 73 Byte, sonst eine Ablehnung mit Grund, bevor phpseclib
 *   sie sieht (seit der Nachpruefung, A-1, A-2: phpseclib liest BER, und eine
 *   aufgeblaehte Eingabe kostete Hunderte Megabyte). Eine fuehrende Null in r
 *   oder s und die Laenge der Folge als `81 L` bleiben erlaubt; r und s werden
 *   im Bereich [1, n-1] geprueft, und eine andere Schreibweise derselben
 *   gueltigen Signatur macht keinen Angriff, weil jede Herausforderung einmal
 *   gilt.
 * - AT IN EINER ANMELDUNG ist eine Ablehnung (strenger als WebAuthn 7.2; kein
 *   Authenticator setzt es dort).
 * - ERWEITERUNGEN MIT EINFACHEM WERT (`hmac-secret`, `credBlob` liefern
 *   `true`) lehnt der Leser ab. `passkey.js` fordert deshalb keine an.
 * - BS OHNE BE ist eine Ablehnung (WebAuthn L3 7.1 Schritt 16); gelesen wird
 *   der Sicherungszustand sonst nicht.
 *
 * ===========================================================================
 * DER EIGENE URSPRUNG KOMMT AUS DER KONFIGURATION (E-SR-42)
 * ===========================================================================
 *
 * `rp.id` ist der Host aus `app.base_url` (`app_url()`), der Ursprung dessen
 * Schema, Host und Port. NICHT die `Host`-Kopfzeile der Anfrage. Bestaetigt
 * von der Betreiberin am 28.09.2026; die Begruendung ist dabei berichtigt
 * worden (H-SR-08, F-SR-44): Vor einer nachgemachten Seite schuetzt vor allem
 * der BROWSER — er gibt einen Passkey fuer diese Adresse unter einer anderen
 * gar nicht heraus. Den Ausschlag geben drei Dinge: WebAuthn verlangt den
 * Abgleich mit dem Ursprung, den die Anlage ERWARTET, nicht mit dem, den die
 * Anfrage ueber sich behauptet; die `rp.id` eines Passkeys muss ueber Jahre
 * dieselbe bleiben, und unter einem zufaellig benutzten zweiten Namen
 * entstuende einer, der unter dem Hauptnamen nie gilt; und es muss keine
 * Kopfzeile bewertet werden (`Host` oder hinter einem Vermittler
 * `X-Forwarded-Host`). Die Stelle in `kopfzeilen_lib.php`, die den Host
 * liest, ist fuer den Berichtsendpunkt der CSP richtig (dort MUSS es der Name
 * der laufenden Anfrage sein) und fuer diese Frage nicht.
 *
 * Folge: Passkeys gibt es nur unter der Adresse aus `app.base_url`; eine
 * IP-Adresse und eine Adresse ohne HTTPS taugen nicht (das verlangen die
 * Browser selbst). Ein Umlaut-Name wird in Punycode umgeschrieben, ein
 * abschliessender Punkt faellt weg (F-SR-40). JEDE ZEILE TRAEGT IHRE `rp_id`
 * (E-SR-48): Nach einem Umzug auf eine neue Adresse bietet die Anmeldung die
 * alten nicht mehr an, und die Karte zeigt sie als nicht nutzbar (F-SR-41).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/instanz_lib.php';     // app_url()
require_once __DIR__ . '/vendor/laden.php';    // phpseclib

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\EC\Formats\Signature\ASN1 as EcSignaturAsn1;
use phpseclib3\Crypt\PublicKeyLoader;
use phpseclib3\Crypt\RSA;
use phpseclib3\Math\BigInteger;

/** Hoechstens so viele Passkeys je Konto und Adresse (E-SR-31, E-SR-48). */
const PK_HOECHSTENS = 10;
/** So lange gilt die Herausforderung der Registrierung (E-SR-31). */
const PK_REG_S = 600;
/** Die Bezeichnung, hoechstens so viele Zeichen; leer heisst „Passkey vom …". */
const PK_BEZEICHNUNG_MAX = 40;
/** Die zwei Verfahren (COSE-Kennung → Name). Andere sind eine Ablehnung. */
const PK_ALGS = [-7 => 'ES256', -257 => 'RS256'];
/** Die Kennung eines Passkeys, hoechstens so viele Bytes (WebAuthn L2 5.1). */
const PK_KENNUNG_MAX = 1023;
/** Tiefer als acht Behaelter liest der CBOR-Leser nicht (E-SR-30, F-SR-34). */
const PK_CBOR_TIEFE = 8;
/** RSA-Schluessel: so viele Bit, nicht weniger und nicht mehr (F-SR-33). */
const PK_RSA_BITS_MIN = 2048;
const PK_RSA_BITS_MAX = 4096;
/** Der RSA-Exponent, hoechstens so viele Bytes (64 Bit). Die PKCS#1-Pruefung
 *  rechnet phpseclib immer selbst; die Potenz darin uebernimmt OpenSSL, und
 *  das tut es fuer JEDEN erlaubten Modul nur bis 64 Bit Exponent (ueber 3072
 *  Bit Modul lehnt es groessere ab) — darueber rechnete phpseclib die Potenz
 *  in reinem PHP (H-SR-08, Nachpruefung A-4). */
const PK_RSA_E_BYTES_MAX = 8;
/** Ein Feld der Antwort, dekodiert hoechstens so gross (F-SR-35) — auf das
 *  Byte: `pk_feld()` rechnet die Laenge in Base64url ohne Fuellzeichen. Echte
 *  Antworten liegen unter zwei Kilobyte; mit `attestation: 'none'` gibt es
 *  keine Zertifikatskette. */
const PK_FELD_MAX = 32768;
/** Die ganze Antwort als Text (JSON des Endpunkts oder Feld `passkey_antwort`),
 *  hoechstens so gross — vier Felder zu PK_FELD_MAX in Base64url und Rand. */
const PK_ANTWORT_MAX = 196608;
/** P-256: der Modul des Koerpers, 32 Byte gross-endian. Eine Koordinate darf
 *  nicht darueber liegen (F-SR-34, SEC 1 2.3.6). */
const PK_P256_P = "\xff\xff\xff\xff\x00\x00\x00\x01\x00\x00\x00\x00\x00\x00\x00\x00"
                . "\x00\x00\x00\x00\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff\xff";
/** Fehlerarten (Code von PkFehler): Was davon der Aufrufer NICHT als
 *  Fehlversuch zaehlt, steht in F-SR-37 — eine abgelaufene oder verdraengte
 *  Herausforderung und ein zurueckgelaufener Zaehler bei gueltiger Signatur. */
const PK_F_PRUEFUNG = 0;
const PK_F_HERAUSFORDERUNG = 1;
const PK_F_ZAEHLER = 2;

/** Eine Byte-Kette aus CBOR — getrennt von einer Textkette, die ein PHP-String bleibt. */
final class PkBytes
{
    public function __construct(public readonly string $b) {}
}

/** Jede Ablehnung beim Lesen oder Pruefen. Die Meldung ist fuer das Protokoll
 *  der Probe, nicht fuer die Oberflaeche; der Code sagt die Art (PK_F_*). */
final class PkFehler extends RuntimeException {}

/* ===========================================================================
 * Base64url — die eine Stelle im Server (der Browser hat seine in passkey.js)
 * ======================================================================== */

function pk_b64u(string $bytes): string
{
    return rtrim(strtr(base64_encode($bytes), '+/', '-_'), '=');
}

/**
 * Streng: nur das Alphabet von Base64url, ohne Fuellzeichen, ohne Zeilenende,
 * und nur in der kanonischen Schreibweise; sonst null (F-SR-34). `\z` statt
 * `$`, weil `$` auch vor einem abschliessenden Zeilenumbruch passt; die
 * Rueckprobe, weil `base64_decode()` die Fuellbits des letzten Zeichens nicht
 * prueft („QR" gaebe dasselbe wie „QQ").
 */
function pk_b64u_lesen(string $text): ?string
{
    if ($text === '' || !preg_match('/^[A-Za-z0-9_-]+\z/', $text) || strlen($text) % 4 === 1) {
        return null;
    }
    $roh = base64_decode(strtr($text, '-_', '+/'), true);
    if ($roh === false || pk_b64u($roh) !== $text) { return null; }
    return $roh;
}

/**
 * Ein Feld der Antwort lesen: Text, Base64url, nicht groesser als PK_FELD_MAX
 * (F-SR-35). Ein Feld vom falschen JSON-Typ ist eine Ablehnung mit Grund —
 * bis Web 21.10.0 machte ein `(string)` aus einer Liste eine PHP-Warnung im
 * Reiter System, ausloesbar von jedem, der das Passwort hat.
 *
 * @throws PkFehler
 */
function pk_feld(array $antwort, string $name): string
{
    $v = $antwort[$name] ?? null;
    if (!is_string($v)) { throw new PkFehler('Antwort: ' . $name . ' fehlt oder ist kein Text'); }
    /* ceil(PK_FELD_MAX * 4 / 3) Zeichen: genau PK_FELD_MAX Byte, keins mehr
       (bis zur Nachpruefung von H-SR-08, B-2, liess `+ 4` zwei Byte zu viel zu). */
    if (strlen($v) > intdiv(PK_FELD_MAX * 4 + 2, 3)) { throw new PkFehler('Antwort: ' . $name . ' zu gross'); }
    $roh = pk_b64u_lesen($v);
    if ($roh === null) { throw new PkFehler('Antwort: ' . $name . ' ist kein Base64url'); }
    return $roh;
}

/* ===========================================================================
 * 1. Der CBOR-Leser (RFC 8949, nur die Teilmenge von E-SR-30)
 * ======================================================================== */

/**
 * Ein ganzes CBOR-Element lesen — ein Rest dahinter ist eine Ablehnung.
 *
 * Rueckgabe: int, string (Textkette), PkBytes (Byte-Kette), list (Liste) oder
 * array mit int- oder string-Schluesseln (Karte). Karte und Liste sind danach
 * nicht zu unterscheiden (`{}` und `[]` sind beide `[]`) — wo es darauf
 * ankommt, fragt der Aufrufer vorher `pk_cbor_art()`.
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

/** Die Hauptart (0 bis 7) des Elements ab `$pos` — oder -1 am Ende. */
function pk_cbor_art(string $b, int $pos): int
{
    return $pos < strlen($b) ? ord($b[$pos]) >> 5 : -1;
}

/**
 * Ein Element ab `$pos` lesen und `$pos` dahinter setzen. Fuer `authData`, wo
 * hinter dem COSE-Schluessel noch Erweiterungen stehen koennen.
 *
 * `$tiefe` ist die Ebene dieses Elements (1 = oben). Gezaehlt werden nur
 * BEHAELTER: Eine Liste oder Karte auf Ebene neun ist eine Ablehnung, eine
 * Zahl darin auf Ebene neun nicht. Bis Web 21.10.0 stand die Pruefung am
 * Anfang jedes Elements, und acht Listen mit einer Zahl innen waren schon zu
 * tief — strenger, als Kopf, Doku und Probe sagten (F-SR-34).
 *
 * @throws PkFehler
 */
function pk_cbor_element(string $b, int &$pos, int $tiefe): mixed
{
    $laenge = strlen($b);
    if ($pos >= $laenge) { throw new PkFehler('CBOR: abgeschnitten'); }
    $erstes = ord($b[$pos++]);
    $typ  = $erstes >> 5;
    $info = $erstes & 0x1f;

    if ($typ === 6) { throw new PkFehler('CBOR: Marken werden nicht gelesen'); }
    if ($typ === 7) { throw new PkFehler('CBOR: Fliesszahlen und einfache Werte werden nicht gelesen'); }
    if ($info === 31) { throw new PkFehler('CBOR: unbestimmte Laenge'); }
    if ($info >= 28) { throw new PkFehler('CBOR: reservierte Laengenangabe'); }
    if (($typ === 4 || $typ === 5) && $tiefe > PK_CBOR_TIEFE) {
        throw new PkFehler('CBOR: tiefer als ' . PK_CBOR_TIEFE . ' Ebenen');
    }

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
                /* EIN TEXTSCHLUESSEL, DER WIE EINE ZAHL AUSSIEHT, IST EINE
                   ABLEHNUNG (F-SR-34). PHP macht aus dem Text "1" den
                   Arrayschluessel 1 — ein COSE-Schluessel mit lauter
                   Textketten als Labels ginge sonst als echter durch. Hier
                   braucht es das nie: COSE-Labels sind Zahlen, die Schluessel
                   des attestationObject und der Erweiterungen Woerter. */
                if (is_string($schluessel) && preg_match('/^-?(0|[1-9][0-9]*)\z/', $schluessel)) {
                    throw new PkFehler('CBOR: Textschluessel sieht aus wie eine Zahl');
                }
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
 * GENAU DIE LABELS, DIE ES BRAUCHT (F-SR-34, WebAuthn L2 5.8.5: „MUST NOT
 * contain any other OPTIONAL parameters"): EC2 1, 3, -1, -2, -3; RSA 1, 3,
 * -1, -2. Ein weiteres — etwa -4, der private Teil — ist eine Ablehnung, nicht
 * etwas, das stillschweigend liegen bleibt.
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
    $labels = array_keys($cose);
    sort($labels);
    $bytes = static fn(mixed $v): ?string => $v instanceof PkBytes ? $v->b : null;
    try {
        if ($alg === -7) {
            if ($labels !== [-3, -2, -1, 1, 3]) { throw new PkFehler('COSE: Labels passen nicht zu EC2'); }
            $x = $bytes($cose[-2]);
            $y = $bytes($cose[-3]);
            if ($kty !== 2 || $cose[-1] !== 1 || $x === null || $y === null
                || strlen($x) !== 32 || strlen($y) !== 32) {
                throw new PkFehler('COSE: kein EC2-Schluessel auf P-256');
            }
            /* Eine Koordinate ab p ist kein Koerperelement — phpseclib
               rechnete modulo p und nahme sie als denselben Punkt an
               („Reparatur statt Ablehnung", F-SR-34). Gleiche Laenge: Der
               Bytevergleich ist der Zahlenvergleich. */
            if (strcmp($x, PK_P256_P) >= 0 || strcmp($y, PK_P256_P) >= 0) {
                throw new PkFehler('COSE: Koordinate nicht kleiner als p');
            }
            $k = PublicKeyLoader::loadPublicKey((string)json_encode(
                ['kty' => 'EC', 'crv' => 'P-256', 'x' => pk_b64u($x), 'y' => pk_b64u($y)]));
            if (!$k instanceof EC\PublicKey) { throw new PkFehler('COSE: kein EC-Schluessel'); }
            return ['schluessel' => $k, 'alg' => $alg];
        }
        if ($labels !== [-2, -1, 1, 3]) { throw new PkFehler('COSE: Labels passen nicht zu RSA'); }
        $n = $bytes($cose[-1]);
        $e = $bytes($cose[-2]);
        if ($kty !== 3 || $n === null || $e === null || $n === '' || $e === '') {
            throw new PkFehler('COSE: kein RSA-Schluessel');
        }
        /* DER EXPONENT (F-SR-33): ungerade, mindestens 3, hoechstens 64 Bit.
           e = 1 machte die Signatur zum Klartext; ein grosses e machte jede
           Pruefung teuer (gemessen: 4096 Bit Modul und Exponent 6 s). Der
           Modul: ungerade, 2048 bis 4096 Bit. BEIDE IN DER KUERZESTEN FORM
           (RFC 8230 4, Nachpruefung A-5): ein fuehrendes Nullbyte ist eine
           Ablehnung, keine Reparatur — wie die Koordinaten oben. */
        if ($e[0] === "\0" || $n[0] === "\0") { throw new PkFehler('COSE: RSA-Parameter nicht minimal'); }
        if (strlen($e) > PK_RSA_E_BYTES_MAX || (ord($e[-1]) & 1) === 0
            || (strlen($e) === 1 && ord($e) < 3)) {
            throw new PkFehler('COSE: RSA-Exponent nicht erlaubt');
        }
        if ((ord($n[-1]) & 1) === 0) { throw new PkFehler('COSE: RSA-Modul gerade'); }
        $k = PublicKeyLoader::loadPublicKey((string)json_encode(
            ['kty' => 'RSA', 'n' => pk_b64u($n), 'e' => pk_b64u($e)]));
        if (!$k instanceof RSA\PublicKey) { throw new PkFehler('COSE: kein RSA-Schluessel'); }
        if ($k->getLength() < PK_RSA_BITS_MIN) { throw new PkFehler('COSE: RSA-Schluessel zu kurz'); }
        if ($k->getLength() > PK_RSA_BITS_MAX) { throw new PkFehler('COSE: RSA-Schluessel zu lang'); }
        return ['schluessel' => $k, 'alg' => $alg];
    } catch (PkFehler $f) {
        throw $f;
    } catch (Throwable $t) {
        throw new PkFehler('COSE: phpseclib lehnt den Schluessel ab');
    }
}

/**
 * Den gespeicherten Schluessel (SPKI als PEM) laden — oder null. DIE GRENZEN
 * VON F-SR-33 GELTEN AUCH HIER (Nachpruefung A-6): Die Kosten entstehen bei
 * der Anmeldung, nicht beim Anlegen; eine Zeile ausserhalb der Grenzen (aus
 * einem Bestand vor Web 21.11.0 oder von Hand geschrieben) laedt nicht.
 */
function pk_spki_laden(string $pem, int $alg): EC\PublicKey|RSA\PublicKey|null
{
    try {
        $k = PublicKeyLoader::loadPublicKey($pem);
        if ($alg === -7 && $k instanceof EC\PublicKey && $k->getCurve() === 'secp256r1') { return $k; }
        if ($alg === -257 && $k instanceof RSA\PublicKey
            && $k->getLength() >= PK_RSA_BITS_MIN && $k->getLength() <= PK_RSA_BITS_MAX
            && ($roh = $k->toString('Raw')) && ($roh['e'] ?? null) instanceof BigInteger
            && strlen($roh['e']->toBytes()) <= PK_RSA_E_BYTES_MAX) {
            return $k;
        }
    } catch (Throwable) {
        return null;
    }
    return null;
}

/* ===========================================================================
 * 3. Ursprung, Herausforderung, authData
 * ======================================================================== */

/**
 * Der eigene Ursprung und die `rp.id` — aus `app.base_url` (E-SR-42).
 *
 * DER HOST WIRD SO GESCHRIEBEN, WIE IHN DER BROWSER SCHICKT (F-SR-40): klein,
 * ohne abschliessenden Punkt, ein Umlaut-Name in Punycode (`idn_to_ascii`).
 * Fehlt `intl`, taugt ein Umlaut-Name nicht — lieber keine Passkeys als ein
 * Abschnitt, in dem jeder Versuch mit „nicht angenommen" endet.
 *
 * @return array{ursprung: string, rp_id: string}|null  null: keine taugliche
 *         Adresse (leer, kein HTTPS ausser `localhost`, eine IP-Adresse)
 */
function pk_ursprung(): ?array
{
    $basis = app_url();
    $teile = parse_url($basis);
    $schema = strtolower((string)($teile['scheme'] ?? ''));
    $host   = rtrim(strtolower((string)($teile['host'] ?? '')), '.');
    if ($host === '' || filter_var(trim($host, '[]'), FILTER_VALIDATE_IP) !== false) { return null; }
    if (preg_match('/[^\x21-\x7e]/', $host)) {
        if (!function_exists('idn_to_ascii')) { return null; }
        /* OHNE UEBERGANGSREGELN, wie der URL-Standard und damit der Browser
           (Nachpruefung D-1): Mit IDNA_DEFAULT wurde aus „straße" „strasse",
           der Browser schickt „xn--strae-oqa". */
        $puny = idn_to_ascii($host, IDNA_NONTRANSITIONAL_TO_ASCII | IDNA_CHECK_BIDI | IDNA_CHECK_CONTEXTJ,
                             INTL_IDNA_VARIANT_UTS46);
        if (!is_string($puny) || $puny === '') { return null; }
        $host = strtolower($puny);
    }
    if (!preg_match('/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\z/', $host)) { return null; }
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
 * im Code-Schritt, `$_SESSION['passkey_best']` auf der Bestaetigung). Mit
 * `$konto` traegt das Fach das Konto; `pk_ablage_passt()` prueft es bei der
 * Entnahme (F-SR-39: bis Web 21.10.0 trug es nur die Registrierung).
 *
 * @return string die Herausforderung, Base64url
 */
function pk_herausforderung_stellen(array &$ablage, int $gueltig_s = PK_REG_S, ?int $konto = null): string
{
    $h = pk_b64u(random_bytes(32));
    $ablage['herausforderung'] = $h;
    $ablage['bis'] = time() + $gueltig_s;
    if ($konto !== null) { $ablage['konto'] = $konto; }
    return $h;
}

/** Gehoert die Ablage diesem Konto? (Ein Fach ohne Konto passt nie.) */
function pk_ablage_passt(array $ablage, int $userId): bool
{
    return (int)($ablage['konto'] ?? 0) === $userId && $userId > 0;
}

/**
 * `authData` zerlegen (WebAuthn L2 6.1). Mit AT folgen AAGUID, Kennung und
 * COSE-Schluessel (eine Karte); mit ED eine Karte der Erweiterungen; danach
 * nichts mehr. BS ohne BE ist eine Ablehnung (WebAuthn L3 7.1 Schritt 16).
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
    if (($flags & 0x10) !== 0 && ($flags & 0x08) === 0) { throw new PkFehler('authData: BS ohne BE'); }
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
        if (pk_cbor_art($a, $pos) !== 5) { throw new PkFehler('authData: COSE-Schluessel ist keine Karte'); }
        $cose = pk_cbor_element($a, $pos, 1);
    }
    if (($flags & 0x80) !== 0) {
        /* Erweiterungen: gelesen, nicht verwendet — aber eine KARTE, wie
           WebAuthn 6.1 sie definiert (F-SR-34; bis Web 21.10.0 ging jedes
           einzelne Element durch). */
        if (pk_cbor_art($a, $pos) !== 5) { throw new PkFehler('authData: Erweiterungen sind keine Karte'); }
        pk_cbor_element($a, $pos, 1);
    }
    if ($pos !== strlen($a)) { throw new PkFehler('authData: Rest hinter den Daten'); }
    return ['rp_hash' => substr($a, 0, 32), 'flags' => $flags, 'zaehler' => $zaehler,
            'kennung' => $kennung, 'cose' => $cose];
}

/**
 * `clientDataJSON` pruefen: Art, Herausforderung, Ursprung, kein fremder
 * Rahmen.
 *
 * DIE HERAUSFORDERUNG DER ABLAGE MUSS EINE SEIN (F-SR-34): 43 Zeichen, wie
 * `pk_herausforderung_stellen()` sie stellt. Eine leere Ablage haette sonst
 * eine leere `challenge` angenommen — heute unerreichbar, aber eine Zeile.
 * `crossOrigin` zaehlt als fremder Rahmen, sobald es etwas anderes als
 * `false` ist; ein `topOrigin` gibt es nur in einem fremden Rahmen.
 *
 * @throws PkFehler
 */
function pk_client_pruefen(string $json, string $art, string $herausforderung, string $ursprung): void
{
    if (strlen($herausforderung) < 43) {
        throw new PkFehler('Herausforderung der Ablage ungueltig', PK_F_HERAUSFORDERUNG);
    }
    $c = json_decode($json, true);
    if (!is_array($c) || array_is_list($c)) { throw new PkFehler('clientData: kein JSON-Objekt'); }
    if (($c['type'] ?? null) !== $art) { throw new PkFehler('clientData: falsche Art'); }
    if (!is_string($c['challenge'] ?? null) || !hash_equals($herausforderung, $c['challenge'])) {
        throw new PkFehler('clientData: fremde Herausforderung', PK_F_HERAUSFORDERUNG);
    }
    if (($c['origin'] ?? null) !== $ursprung) { throw new PkFehler('clientData: fremder Ursprung'); }
    if ((array_key_exists('crossOrigin', $c) && $c['crossOrigin'] !== false) || isset($c['topOrigin'])) {
        throw new PkFehler('clientData: aus einem fremden Rahmen');
    }
}

/** Ablage vorhanden und nicht abgelaufen? Sonst eine Ablehnung, die der
 *  Aufrufer nicht als Fehlversuch zaehlt (F-SR-37). */
function pk_ablage_pruefen(array $ablage): string
{
    if (!is_string($ablage['herausforderung'] ?? null) || (int)($ablage['bis'] ?? 0) < time()) {
        throw new PkFehler('Herausforderung fehlt oder ist abgelaufen', PK_F_HERAUSFORDERUNG);
    }
    return $ablage['herausforderung'];
}

/** Aus einer Ausnahme das Ergebnis einer Pruefung machen. */
function pk_ablehnung(Throwable $t): array
{
    if ($t instanceof PkFehler) {
        return ['ok' => false, 'grund' => $t->getMessage(), 'art' => match ($t->getCode()) {
            PK_F_HERAUSFORDERUNG => 'herausforderung',
            PK_F_ZAEHLER         => 'zaehler',
            default              => 'pruefung',
        }];
    }
    return ['ok' => false, 'grund' => 'unerwartet: ' . get_class($t), 'art' => 'pruefung'];
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
 * @return array{ok:true, kennung:string, hash:string, rp_id:string, spki:string, alg:int, zaehler:int}
 *       | array{ok:false, grund:string, art:string}
 */
function pk_registrierung_pruefen(array $ablage, array $antwort): array
{
    try {
        $u = pk_ursprung();
        if ($u === null) { throw new PkFehler('Anlage: keine taugliche Adresse'); }
        $h = pk_ablage_pruefen($ablage);
        $client = pk_feld($antwort, 'clientDataJSON');
        $att    = pk_feld($antwort, 'attestationObject');
        $rawId  = pk_feld($antwort, 'rawId');
        pk_client_pruefen($client, 'webauthn.create', $h, $u['ursprung']);

        if (pk_cbor_art($att, 0) !== 5) { throw new PkFehler('attestationObject: keine Karte'); }
        $obj = pk_cbor_lesen($att);
        /* `attStmt` ist eine Karte (Nachpruefung B-3): Gelesen sind Karte und
           Liste dasselbe PHP-Feld; eine nicht leere Liste faellt, die leere
           Karte der Attestation `none` bleibt. Gelesen wird sie nicht (E-SR-30). */
        $stmt = is_array($obj) ? ($obj['attStmt'] ?? null) : null;
        if (!is_array($obj) || !is_string($obj['fmt'] ?? null) || !is_array($stmt)
            || ($stmt !== [] && array_is_list($stmt))
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
                'hash' => hash('sha256', (string)$a['kennung']), 'rp_id' => $u['rp_id'],
                'spki' => (string)$k['schluessel']->toString('PKCS8'), 'alg' => $k['alg'],
                'zaehler' => $a['zaehler']];
    } catch (Throwable $t) {
        return pk_ablehnung($t);
    }
}

/**
 * Die Form einer ES256-Signatur, ohne phpseclib (Nachpruefung A-1, A-2):
 * `30 L 02 lr r 02 ls s` — die Laenge der Folge kurz oder als `81 L`, und sie
 * geht genau auf; darin genau zwei INTEGER mit kurzer Laenge von 1 bis 33 Byte.
 * Eine fuehrende Null in r oder s bleibt erlaubt (phpseclib liest BER, und eine
 * zweite Schreibweise derselben gueltigen Signatur ist kein Angriff — jede
 * Herausforderung gilt einmal). Hoechstens 73 Byte gehen durch.
 */
function pk_es256_form(string $sig): bool
{
    $n = strlen($sig);
    if ($n < 8 || $n > 73 || $sig[0] !== "\x30") { return false; }
    $l = ord($sig[1]);
    $p = 2;
    if ($l === 0x81) { $l = ord($sig[2]); $p = 3; } elseif ($l >= 0x80) { return false; }
    if ($p + $l !== $n) { return false; }
    for ($i = 0; $i < 2; $i++) {
        if ($p + 2 > $n || $sig[$p] !== "\x02") { return false; }
        $li = ord($sig[$p + 1]);
        if ($li < 1 || $li > 33 || $p + 2 + $li > $n) { return false; }
        $p += 2 + $li;
    }
    return $p === $n;
}

/**
 * Eine Anmeldung (Assertion) pruefen — gegen die Passkeys DIESES Kontos an
 * DIESER Adresse (E-SR-48).
 *
 * `$antwort`: `rawId`, `clientDataJSON`, `authenticatorData`, `signature`
 * (Base64url). Der Zaehler folgt E-SR-33: neu > alt oder beide 0 → gut; sonst
 * Ablehnung, der Passkey bleibt, Protokoll `passkey_zaehler` mit seinem
 * Namen und hoechstens eine Mail je Tag an die Kontoadresse (E-SR-46,
 * F-SR-36). Das Fortschreiben ist atomar: Zwei gleichzeitige Anmeldungen mit
 * demselben Stand (Original und Kopie) kommen nicht beide durch.
 *
 * Bei Erfolg werden Zaehler und `zuletzt_am` fortgeschrieben — sonst nichts,
 * und nichts ins Protokoll (E-SR-33: je Anmeldung waere Rauschen).
 *
 * `art` einer Ablehnung: `herausforderung` (fehlt, abgelaufen, verdraengt)
 * und `zaehler` (Signatur gueltig, Stand zurueck) zaehlt der Aufrufer NICHT
 * als Fehlversuch (F-SR-37); `pruefung` schon.
 *
 * @return array{ok:true, id:int}|array{ok:false, grund:string, art:string}
 */
function pk_anmeldung_pruefen(int $userId, array $ablage, array $antwort): array
{
    try {
        $u = pk_ursprung();
        if ($u === null) { throw new PkFehler('Anlage: keine taugliche Adresse'); }
        $h = pk_ablage_pruefen($ablage);
        $rawId  = pk_feld($antwort, 'rawId');
        $client = pk_feld($antwort, 'clientDataJSON');
        $auth   = pk_feld($antwort, 'authenticatorData');
        $sig    = pk_feld($antwort, 'signature');
        pk_client_pruefen($client, 'webauthn.get', $h, $u['ursprung']);

        $st = db()->prepare('SELECT id, credential_id, oeffentlich, alg, zaehler, bezeichnung, angelegt_am
                               FROM passkeys WHERE user_id = ? AND rp_id = ? AND credential_hash = ?');
        $st->execute([$userId, $u['rp_id'], hash('sha256', $rawId)]);
        $pk = $st->fetch(PDO::FETCH_ASSOC);
        if (!$pk || !hash_equals((string)$pk['credential_id'], pk_b64u($rawId))) {
            throw new PkFehler('unbekannte Kennung');
        }

        $a = pk_authdata_lesen($auth, false);
        if (!hash_equals(hash('sha256', $u['rp_id'], true), $a['rp_hash'])) {
            throw new PkFehler('authData: fremde rpId');
        }
        if (($a['flags'] & 0x01) === 0) { throw new PkFehler('authData: UP fehlt'); }

        $k = pk_spki_laden((string)$pk['oeffentlich'], (int)$pk['alg']);
        if ($k === null) { throw new PkFehler('gespeicherter Schluessel laesst sich nicht laden'); }
        if ((int)$pk['alg'] === -7) {
            /* Eine ES256-Signatur ist eine ASN.1-Folge mit r und s. DIE FORM
               PRUEFT `pk_es256_form()` SELBST, bevor phpseclib sie sieht
               (Nachpruefung A-1, A-2): phpseclib liest BER, und eine Folge aus
               32 KiB geschachtelter unbestimmter Laengen kostete dort 356 MiB
               und zwei Sekunden; eine Zeitangabe, ein fremdes Etikett oder
               eine konstruierte Bitkette endeten in ValueError, TypeError oder
               einer PHP-Warnung. Dahinter bleibt die Pruefung, dass phpseclib
               zwei Zahlen gelesen hat (F-SR-34: die leere Folge). */
            if (!pk_es256_form($sig)) { throw new PkFehler('Signatur: keine DER-Folge mit r und s'); }
            $teile = EcSignaturAsn1::load($sig);
            if (!is_array($teile) || !($teile['r'] ?? null) instanceof BigInteger
                || !($teile['s'] ?? null) instanceof BigInteger) {
                throw new PkFehler('Signatur: keine ASN.1-Folge mit r und s');
            }
        } elseif ($k instanceof RSA\PublicKey && strlen($sig) !== intdiv($k->getLength() + 7, 8)) {
            /* RS256: Die Signatur ist so lang wie der Modul. phpseclib prueft
               das auch, aber ohne Grund (Nachpruefung A-1). */
            throw new PkFehler('Signatur: Laenge passt nicht zum Schluessel');
        }
        $daten = $auth . hash('sha256', $client, true);
        $k = (int)$pk['alg'] === -7
            ? $k->withHash('sha256')->withSignatureFormat('ASN1')
            : $k->withHash('sha256')->withPadding(RSA::SIGNATURE_PKCS1);
        if ($k->verify($daten, $sig) !== true) { throw new PkFehler('Signatur passt nicht'); }

        $alt = (int)$pk['zaehler'];
        $neu = $a['zaehler'];
        $gut = false;
        if ($neu === 0 && $alt === 0) {
            db()->prepare('UPDATE passkeys SET zuletzt_am = UTC_TIMESTAMP() WHERE id = ?')
                ->execute([(int)$pk['id']]);
            $gut = true;
        } elseif ($neu > $alt) {
            /* ATOMAR (F-SR-36): Nur, wenn der Stand noch der gelesene ist. Eine
               zweite Anmeldung, die im selben Augenblick denselben Stand
               gelesen hat, findet ihn nicht mehr und gilt als Kopie. Der neue
               Wert ist groesser als der alte — die Zeile aendert sich also
               sicher, und `rowCount()` sagt die Wahrheit. */
            $st = db()->prepare('UPDATE passkeys SET zaehler = ?, zuletzt_am = UTC_TIMESTAMP()
                                  WHERE id = ? AND zaehler = ?');
            $st->execute([$neu, (int)$pk['id'], $alt]);
            $gut = $st->rowCount() === 1;
        }
        if (!$gut) {
            pk_zaehler_warnen($userId, $pk, $alt, $neu);
            throw new PkFehler('Zaehler zurueckgelaufen', PK_F_ZAEHLER);
        }
        return ['ok' => true, 'id' => (int)$pk['id']];
    } catch (Throwable $t) {
        return pk_ablehnung($t);
    }
}

/**
 * Ein zurueckgelaufener Zaehler: Protokoll mit dem Namen des Passkeys (orange,
 * jedes Mal) und eine Mail an die Kontoadresse, hoechstens eine je Passkey und
 * Tag (E-SR-46, F-SR-36).
 *
 * WARUM DIE MAIL. E-SR-33 liess den Passkey stehen, „sie sieht den Eintrag
 * und entscheidet" — den Reiter Verwaltung sieht die Rolle `user` aber nicht.
 * Ausloesen kann das nur, wer einen GUELTIGEN Schluessel hat und im
 * Code-Schritt vorher das Passwort: Es ist das staerkste Zeichen, das die
 * Anlage fuer ein erbeutetes Konto kennt, und bis Web 21.10.0 erfuhr die
 * Betroffene davon nichts. Der Deckel steht in der Zeile (`gewarnt_am`) und
 * wird mit derselben Anweisung gesetzt, die ihn prueft.
 */
function pk_zaehler_warnen(int $userId, array $pk, int $alt, int $neu): void
{
    $name = pk_anzeigename(['bezeichnung' => (string)$pk['bezeichnung'], 'angelegt_am' => (string)$pk['angelegt_am']]);
    require_once __DIR__ . '/protokoll_lib.php';
    /* `weg` (Nachpruefung zu N1-2): Im Code-Schritt ist noch niemand angemeldet,
       und `protokoll()` schreibt dann den Urheber `job` — der Weg sagt der
       Verwaltung, woher der Versuch kam. */
    protokoll('verwaltung', 'passkey_zaehler',
              'Passkey „' . $name . '" mit zurückgelaufenem Zähler abgewiesen — vielleicht eine Kopie',
              ['passkey' => (int)$pk['id'], 'alt' => $alt, 'neu' => $neu,
               'weg' => isset($_SESSION['user_id']) ? 'bestaetigung' : 'anmeldung'], $userId);
    $st = db()->prepare('UPDATE passkeys SET gewarnt_am = UTC_TIMESTAMP()
                          WHERE id = ? AND (gewarnt_am IS NULL OR gewarnt_am < UTC_TIMESTAMP() - INTERVAL 1 DAY)');
    $st->execute([(int)$pk['id']]);
    if ($st->rowCount() !== 1) { return; }
    $st = db()->prepare('SELECT email FROM users WHERE id = ?');
    $st->execute([$userId]);
    $mail = (string)$st->fetchColumn();
    if ($mail === '') { return; }
    require_once __DIR__ . '/mail_lib.php';
    mail_einreihen('passkey_zaehler', $mail,
                   ['link' => app_url('/einstellungen.php?t=profil'), 'bezeichnung' => $name]);
}

/* ===========================================================================
 * 5. Die Tabelle
 * ======================================================================== */

/**
 * Die Passkeys eines Kontos, aelteste zuerst — ALLE Adressen. `hier` sagt, ob
 * einer zur heutigen passt (E-SR-48); nur solche bietet die Anmeldung an.
 *
 * @return list<array{id:int, kennung:string, rp_id:string, hier:bool, bezeichnung:string, angelegt_am:string, zuletzt_am:?string}>
 */
function pk_liste(int $userId): array
{
    if (!pk_tabelle_da()) { return []; }
    $u = pk_ursprung();
    $st = db()->prepare('SELECT id, credential_id, rp_id, bezeichnung, angelegt_am, zuletzt_am
                           FROM passkeys WHERE user_id = ? ORDER BY angelegt_am, id');
    $st->execute([$userId]);
    return array_map(static fn(array $z): array => [
        'id' => (int)$z['id'], 'kennung' => (string)$z['credential_id'],
        'rp_id' => (string)$z['rp_id'], 'hier' => $u !== null && (string)$z['rp_id'] === $u['rp_id'],
        'bezeichnung' => (string)$z['bezeichnung'],
        'angelegt_am' => (string)$z['angelegt_am'], 'zuletzt_am' => $z['zuletzt_am'],
    ], $st->fetchAll(PDO::FETCH_ASSOC));
}

/**
 * Wie viele Passkeys hat das Konto? Ohne `$alle` nur die der heutigen Adresse
 * — die, die der Code-Schritt anbieten kann (E-SR-48). Die Kontoseite der
 * Verwaltung zaehlt alle.
 */
function pk_zahl(int $userId, bool $alle = false): int
{
    if (!pk_tabelle_da()) { return 0; }
    if ($alle) {
        $st = db()->prepare('SELECT COUNT(*) FROM passkeys WHERE user_id = ?');
        $st->execute([$userId]);
        return (int)$st->fetchColumn();
    }
    $u = pk_ursprung();
    if ($u === null) { return 0; }
    $st = db()->prepare('SELECT COUNT(*) FROM passkeys WHERE user_id = ? AND rp_id = ?');
    $st->execute([$userId, $u['rp_id']]);
    return (int)$st->fetchColumn();
}

/**
 * Die Bezeichnung saeubern (F-SR-39): Steuer- und Formatzeichen und jeder
 * Weissraum werden zu einem Leerzeichen, dann wird getrimmt — nach dem Muster
 * der Geraetetexte. Eine Bezeichnung aus lauter NBSP ist danach leer und
 * heisst „Passkey vom <Datum>"; ein Zeilenumbruch bricht keine Mail mehr um.
 * null: kein gueltiges UTF-8.
 */
function pk_bezeichnung_saeubern(string $roh): ?string
{
    $s = preg_replace('/[\p{Cc}\p{Cf}\p{Z}\s]+/u', ' ', $roh);
    return $s === null ? null : trim($s);
}

/**
 * Eine gepruefte Registrierung ablegen. Abgelehnt: der elfte an dieser
 * Adresse (`voll`), eine Kennung, die es schon gibt — auch bei einem anderen
 * Konto (`vorhanden`) —, eine zu lange Bezeichnung, und ein Konto, dessen
 * Zweitfaktor inzwischen aus ist (`zweitfaktor`, F-SR-38: Ein Zuruecksetzen
 * zwischen der Pruefung im Endpunkt und diesem Schreiben liess sonst eine
 * Zeile ohne Faktor zurueck, die beim naechsten Einschalten wieder galt).
 *
 * @return array{ok:true, id:int}|array{ok:false, grund:string}
 */
function pk_anlegen(int $userId, array $reg, string $bezeichnung): array
{
    $bezeichnung = pk_bezeichnung_saeubern($bezeichnung);
    if ($bezeichnung === null || mb_strlen($bezeichnung) > PK_BEZEICHNUNG_MAX) {
        return ['ok' => false, 'grund' => 'bezeichnung'];
    }
    try {
        return db_transaktion(db(), static function (PDO $pdo) use ($userId, $reg, $bezeichnung): array {
            /* FOR UPDATE auf der Kontozeile: Zwei Fenster, die gleichzeitig
             * den zehnten anlegen, legen genau einen an; und ein
             * Zuruecksetzen, das dazwischenkommt, wartet oder ist schon da. */
            $st = $pdo->prepare('SELECT totp_seit FROM users WHERE id = ? FOR UPDATE');
            $st->execute([$userId]);
            $seit = $st->fetchColumn();
            if ($seit === null || $seit === false) { return ['ok' => false, 'grund' => 'zweitfaktor']; }
            $st = $pdo->prepare('SELECT COUNT(*) FROM passkeys WHERE user_id = ? AND rp_id = ?');
            $st->execute([$userId, $reg['rp_id']]);
            if ((int)$st->fetchColumn() >= PK_HOECHSTENS) { return ['ok' => false, 'grund' => 'voll']; }
            $st = $pdo->prepare('SELECT COUNT(*) FROM passkeys WHERE credential_hash = ?');
            $st->execute([$reg['hash']]);
            if ((int)$st->fetchColumn() > 0) { return ['ok' => false, 'grund' => 'vorhanden']; }
            $pdo->prepare('INSERT INTO passkeys (user_id, credential_id, credential_hash, rp_id, oeffentlich,
                                                 alg, zaehler, bezeichnung, angelegt_am)
                           VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())')
                ->execute([$userId, $reg['kennung'], $reg['hash'], $reg['rp_id'], $reg['spki'],
                           $reg['alg'], $reg['zaehler'], $bezeichnung]);
            return ['ok' => true, 'id' => (int)$pdo->lastInsertId()];
        });
    } catch (PDOException $e) {
        /* Zwei Konten, die im selben Augenblick dieselbe Kennung ablegen: Die
           Sperre liegt auf der Kontozeile, nicht auf der Kennung — der
           eindeutige Schluessel faengt den zweiten, und das ist `vorhanden`,
           kein 500 (F-SR-39). */
        if ((string)$e->getCode() === '23000') { return ['ok' => false, 'grund' => 'vorhanden']; }
        throw $e;
    }
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
 * (jeder Weg, in dessen Transaktion) und aus `totp_einrichtung_beginnen()`
 * (verwaiste, F-SR-38). Ein Protokolleintrag nur, wenn etwas geloescht wurde.
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
                  'Passkeys entfernt (' . $n . ') — ' . ($weg === 'einrichtung'
                      ? 'verwaist, beim Einrichten des Zweitfaktors' : 'mit dem Zweitfaktor'),
                  ['weg' => $weg, 'anzahl' => $n], $userId);
    }
    return $n;
}

/** Die Kennungen fuer `allowCredentials` / `excludeCredentials` (Base64url) —
 *  nur die der heutigen Adresse (E-SR-48). */
function pk_kennungen(int $userId): array
{
    return array_column(array_filter(pk_liste($userId), static fn(array $p): bool => $p['hier']), 'kennung');
}

/** Die Bezeichnung zur Anzeige — leer heisst „Passkey vom <Datum>". */
function pk_anzeigename(array $pk): string
{
    if ($pk['bezeichnung'] !== '') { return $pk['bezeichnung']; }
    require_once __DIR__ . '/format_lib.php';
    return 'Passkey vom ' . datum_text((string)$pk['angelegt_am']);
}
