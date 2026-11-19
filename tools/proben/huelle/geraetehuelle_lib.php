<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)
/**
 * DIE GERÄTEHÜLLE IN DEN PROBEN — Bausteine und die Gegenlage der Fassung 1
 * ===========================================================================
 *
 * Das Format steht im Kopf von `tools/proben/huelle/referenz.py`, und nur
 * dort. Bis Web 26.x lag diese Datei als `server/geraetehuelle_lib.php` auf
 * dem Server und verschloss Fassung 1 für den Demo-Reset; seit KA-24 liegt
 * sie hier, und seit KA-26 trägt sie nur noch, was die Proben brauchen:
 *
 *   Bausteine     Bindung, Schlüssel laden und bauen, die Innenform der
 *                 Punkte — `geraet.php` verschließt damit Fassung 2 wie ein
 *                 gekoppeltes Gerät (`gh2_probe_*`).
 *   Gegenlage     `gh_verschliessen()` baut eine Hülle der FASSUNG 1 — nur
 *                 damit Proben messen, dass der Eingang sie abweist
 *                 (`400 fassung`) und kein Leser sie öffnet
 *                 (`ohne-herkunft`). Gesendet wird sie von keinem Gerät mehr.
 *
 * **Eine Funktion zum Öffnen gibt es hier mit Absicht nicht.** Geöffnet
 * wird nur in `geraet.php` (`gh2_probe_oeffnen()`), im Prüfwerkzeug.
 * Die PRÜFUNG der Form (Zeichenvorrat, Länge, Kennbyte) steht in der
 * gemeinsamen Prüfschicht: `pruef_huelle()` in `server/validate_lib.php`.
 */

/** Fassung 1 (nur die Gegenlage), Info-Text der Ableitung — wie `referenz.py`. */
const GH_FASSUNG = 1;
const GH_INFO    = 'nadoku-huelle-1';
const GH_HOEHE_UNBEKANNT = -32768;

/** SPKI-Vorspann für einen unkomprimierten P-256-Punkt (65 Byte folgen). */
const GH_SPKI_P256 = '3059301306072a8648ce3d020106082a8648ce3d030107034200';

/**
 * Die Bindung: je Feld zwei Byte Länge (big-endian) und UTF-8 — Art,
 * `client_ref` des Eigentümers, Platz (`<seq_from>:<punkte>` oder leer).
 */
function gh_bindung(string $art, string $clientRef, string $platz = ''): string
{
    $aus = '';
    foreach ([$art, $clientRef, $platz] as $feld) {
        if (strlen($feld) > 0xFFFF) { throw new InvalidArgumentException('Bindung zu lang'); }
        $aus .= pack('n', strlen($feld)) . $feld;
    }
    return $aus;
}

/** Öffentlicher Schlüssel (SEC1, 65 Byte) als OpenSSL-Schlüssel. */
function gh_oeffentlich_laden(string $sec1)
{
    if (strlen($sec1) !== 65 || $sec1[0] !== "\x04") {
        throw new InvalidArgumentException('kein unkomprimierter P-256-Punkt');
    }
    $pem = "-----BEGIN PUBLIC KEY-----\n"
         . chunk_split(base64_encode(hex2bin(GH_SPKI_P256) . $sec1), 64, "\n")
         . "-----END PUBLIC KEY-----\n";
    $k = openssl_pkey_get_public($pem);
    if ($k === false) { throw new InvalidArgumentException('Punkt liegt nicht auf P-256'); }
    return $k;
}

/**
 * Privater P-256-Schlüssel aus der Zahl d (32 Byte) — NUR für die
 * Prüfvektoren, die einen festen flüchtigen Schlüssel vorgeben.
 * ECPrivateKey nach RFC 5915, ohne den optionalen öffentlichen Teil.
 */
function gh_privat_aus_d(string $d)
{
    if (strlen($d) !== 32) { throw new InvalidArgumentException('d muss 32 Byte haben'); }
    $der = hex2bin('30310201010420') . $d . hex2bin('a00a06082a8648ce3d030107');
    $pem = "-----BEGIN EC PRIVATE KEY-----\n" . chunk_split(base64_encode($der), 64, "\n")
         . "-----END EC PRIVATE KEY-----\n";
    $k = openssl_pkey_get_private($pem);
    if ($k === false) { throw new InvalidArgumentException('d ist kein Schlüssel auf P-256'); }
    return $k;
}

/** Der öffentliche Teil eines privaten Schlüssels als SEC1 (65 Byte). */
function gh_sec1_von($privat): string
{
    $ec = openssl_pkey_get_details($privat)['ec'] ?? null;
    if (!$ec) { throw new RuntimeException('kein EC-Schlüssel'); }
    return "\x04" . str_pad($ec['x'], 32, "\0", STR_PAD_LEFT) . str_pad($ec['y'], 32, "\0", STR_PAD_LEFT);
}

/**
 * Eine Hülle der FASSUNG 1 bauen (Binärwert) — nur als Gegenlage: Der
 * Eingang weist sie ab, kein Leser öffnet sie. `$fest` nur für feste
 * Eingaben: ['d' => 32 Byte, 'iv' => 16 Byte].
 */
function gh_verschliessen(string $klar, string $empfSec1, string $art, string $clientRef,
                              string $platz = '', ?array $fest = null): string
{
    $empf = gh_oeffentlich_laden($empfSec1);
    $fluechtig = $fest !== null
        ? gh_privat_aus_d($fest['d'])
        : openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($fluechtig === false) { throw new RuntimeException('kein flüchtiges Schlüsselpaar'); }
    $iv = $fest['iv'] ?? random_bytes(16);
    $epk = gh_sec1_von($fluechtig);
    $z = openssl_pkey_derive($empf, $fluechtig, 32);
    if ($z === false || strlen($z) !== 32) { throw new RuntimeException('ECDH gescheitert'); }
    $okm = hash_hkdf('sha256', $z, 64, GH_INFO . $empfSec1, $epk);
    $kEnc = substr($okm, 0, 32);
    $kMac = substr($okm, 32, 32);
    $ct = openssl_encrypt($klar, 'aes-256-cbc', $kEnc, OPENSSL_RAW_DATA, $iv);
    if ($ct === false) { throw new RuntimeException('AES gescheitert'); }
    $kopf = chr(GH_FASSUNG) . $epk . $iv;
    return $kopf . $ct . hash_hmac('sha256', $kopf . $ct . gh_bindung($art, $clientRef, $platz), $kMac, true);
}

/**
 * Spurpunkte in die binäre Innenform: je Punkt 14 Byte,
 * big-endian — Breite und Länge in 1e-7 Grad (int32), Höhe in ganzen Metern
 * (int16, geklemmt auf ±32 767), Zeit relativ zu `ts_von` in Sekunden
 * (uint32). Ohne Höhe: -32768 (`GH_HOEHE_UNBEKANNT`).
 *
 * @param list<array{0:float,1:float,2:?float,3:int}> $punkte [lat, lon, ele, ts]
 */
function gh_spur_innen(array $punkte, int $tsVon): string
{
    $aus = '';
    foreach ($punkte as $p) {
        $ele = $p[2] === null ? GH_HOEHE_UNBEKANNT
                              : max(-32767, min(32767, (int)round((float)$p[2])));
        $aus .= pack('NNnN',
            (int)round((float)$p[0] * 1e7) & 0xFFFFFFFF,
            (int)round((float)$p[1] * 1e7) & 0xFFFFFFFF,
            $ele & 0xFFFF,
            max(0, (int)$p[3] - $tsVon));
    }
    return $aus;
}

/**
 * Punkte zu Stücken gruppieren — die Regel von
 * `EdOrtsdaten.stueckeBauen()`: Ein Stück endet an jeder Lücke der
 * Punktnummern und nach höchstens 2000 Punkten (`LIMIT_TRACKPUNKTE_ANFRAGE`),
 * Punkt i eines Stücks ist `seq_from + i`. Verschlossen wird in `geraet.php`
 * (`gh2_probe_stuecke_bauen()`); bis KA-26 verschloss diese Funktion selbst,
 * in Fassung 1 (`gh_stuecke_bauen()`).
 *
 * @param list<array{0:int,1:float,2:float,3:?float,4:int}> $punkte
 *        [seq, lat, lon, ele, ts], nach `seq` sortiert, ohne Dubletten
 * @return list<array{seq_from:int, punkte:int, ts_von:int, ts_bis:int, gruppe:list<array>}>
 */
function gh_stuecke_gruppieren(array $punkte): array
{
    $gruppen = [];
    foreach ($punkte as $p) {
        $g = count($gruppen) - 1;
        if ($g >= 0 && (int)$p[0] === (int)end($gruppen[$g])[0] + 1 && count($gruppen[$g]) < 2000) {
            $gruppen[$g][] = $p;
        } else {
            $gruppen[] = [$p];
        }
    }
    $aus = [];
    foreach ($gruppen as $g) {
        $ts = array_map(static fn($p) => (int)$p[4], $g);
        $von = min($ts);
        $aus[] = ['seq_from' => (int)$g[0][0], 'punkte' => count($g), 'ts_von' => $von,
                  'ts_bis' => max($ts), 'gruppe' => $g];
    }
    return $aus;
}
