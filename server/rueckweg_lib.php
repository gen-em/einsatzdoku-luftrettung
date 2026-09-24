<?php
declare(strict_types=1);

/**
 * DER RÜCKWEG BEIM ZWEITFAKTOR ÜBER DEN WIEDERHERSTELLUNGSSCHLÜSSEL
 * (Konzept RW, E-RW-01 bis -15; im P5c-Konzept AP5b).
 *
 * WAS ES IST. Wer Handy UND Wiederherstellungscodes verloren hat, schaltet
 * den Zweitfaktor am Code-Schritt der Anmeldung selbst ab — mit dem
 * Wiederherstellungsschlüssel vom Notfallblatt. Der Schlüssel verlässt den
 * Browser nie: Er öffnet dort die Wiederherstellungs-Hülle, damit den
 * privaten Teil eines Schlüsselpaars des Kontos, und signiert eine
 * Herausforderung des Servers. Der Server prüft die Signatur gegen den
 * öffentlichen Teil.
 *
 * WARUM SO UND NICHT GEGEN `pat_key_check` (F-P5c-106). Die erste Fassung
 * (E-P5c-42) prüfte gegen einen Wert, der in der Datenbank steht — wer einen
 * Abzug hatte, legte ihn vor. Hier liegt in der Datenbank nur der
 * ÖFFENTLICHE Teil (`rw_oeffentlich`) und der private als Chiffretext unter
 * dem Inhaltsschlüssel (`rw_privat`, `edk1:`), den der Server nie kennt. Ein
 * Abzug kann damit prüfen, aber nicht signieren.
 *
 * DAS VERFAHREN (E-RW-03): ECDSA über P-256 (`secp256r1`) mit SHA-256, die
 * Signatur im IEEE-Format — die 64 Byte `r‖s`, die WebCrypto liefert. Geprüft
 * über phpseclib (vendoriert für den SFTP-Adapter, `Lizenzen.md` 3a), das
 * `openssl` nimmt, wenn es SHA-256 kann, und sonst in reinem PHP rechnet
 * (gemessen rund 190 ms — für einen Weg, den ein Konto einmal im Jahr geht,
 * zumutbar). `openssl_verify()` und `sodium` werden NICHT unmittelbar
 * gerufen: zwei Prüfwege wären zwei Stellen.
 *
 * DIE NACHRICHT BAUT DER SERVER SELBST (E-RW-04), aus der Sitzung und dem
 * Konto, nie aus dem, was der Browser schickt:
 *     nadoku-rw-v1|totp-rueckweg|<Kontonummer>|<Herausforderung, 64 hex>
 * Präfix und Zweck trennen die Domäne, die Kontonummer bindet an das Konto,
 * die Herausforderung macht sie einmalig.
 *
 * OB DIE ANLAGE PRÜFEN KANN, sagt `rw_selbsttest()` mit einem festen Vektor
 * aus einem Browser (E-RW-11); `rw_verfuegbar()` merkt sich das Ergebnis je
 * Fassung und Plattform in `app_state` und schaltet den Weg. Ohne die
 * Spalten (vor `update.php`) ist der Weg stumm — `rw_spalten_da()`.
 *
 * DAS PAAR ENTSTEHT IM BROWSER (RW-02, E-RW-02): still nach der Anmeldung
 * (`assets/unlock.js`, wenn `rw_zustand()` „fehlt" sagt) oder über den Knopf
 * „Rückweg erneuern" (`assets/rueckweg.js`); abgelegt wird es über
 * `api/rueckweg_anlegen.php`, nur mit dem Nachweis des Passworts.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/vendor/laden.php';

use phpseclib3\Crypt\EC;
use phpseclib3\Crypt\PublicKeyLoader;

const RW_PRAEFIX = 'nadoku-rw-v1';
const RW_ZWECK   = 'totp-rueckweg';
const RW_KURVE   = 'secp256r1';
/** Die Frist der Herausforderung — dieselbe wie die des halben Standes. */
const RW_HERAUSFORDERUNG_S = 300;

/* DER PRÜFVEKTOR DES SELBSTTESTS (E-RW-11). Ein Paar, das Chromium 141 am
 * 24.09.2026 über WebCrypto erzeugt hat, und dessen Signatur über eine feste
 * Nachricht — also genau das Format, das später aus jedem Browser kommt. Der
 * Zweck `selbsttest` und die Kontonummer 0 trennen ihn von jeder echten
 * Nachricht: Mit diesem Schlüssel lässt sich kein Konto zurücksetzen, weil
 * keine Nachricht mit Zweck `totp-rueckweg` je gegen ihn geprüft wird. Der
 * private Teil ist nicht aufgehoben. */
const RW_VEKTOR_OEFFENTLICH = 'MFkwEwYHKoZIzj0CAQYIKoZIzj0DAQcDQgAE7fZBZ3IvmHCjm6DZ4KffNppezSsX54C2wOBhuKoQ4GbIxP54blmC+bgvEtRW0PsmOyKndbgeFyyPt8msx+H8ag==';
const RW_VEKTOR_NACHRICHT   = 'nadoku-rw-v1|selbsttest|0|0000000000000000000000000000000000000000000000000000000000000000';
const RW_VEKTOR_SIGNATUR    = 'mesKy6cNZ2lXuh+gMN6IL5f4Qi2qUn2u7z5yvNlq7HFJnLsIsvBlnn+bE9j+RNFXzRQnUMO9vjmI/ZqlkuczMA==';

/** Die Marke des Selbsttests in `app_state`. */
const RW_MARKE = 'rw_selbsttest';

/** Die Nachricht, die der Browser signiert und der Server prüft (E-RW-04). */
function rw_nachricht(int $kontoId, string $herausforderungHex): string
{
    return RW_PRAEFIX . '|' . RW_ZWECK . '|' . $kontoId . '|' . $herausforderungHex;
}

/** Eine neue Herausforderung: 32 Zufallsbytes, als 64 Hexzeichen. */
function rw_herausforderung(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Den öffentlichen Teil laden — oder null, wenn er keiner ist, den der
 * Rückweg annimmt: kein Base64, kein SPKI, kein EC-Schlüssel, eine andere
 * Kurve als P-256 (auch Ed25519, das phpseclib ebenfalls als EC lädt).
 */
function rw_oeffentlich_laden(string $spkiB64): ?EC\PublicKey
{
    $der = base64_decode($spkiB64, true);
    if ($der === false || $der === '') { return null; }
    try {
        $k = PublicKeyLoader::load($der);
    } catch (Throwable) {
        return null;
    }
    if (!$k instanceof EC\PublicKey || $k->getCurve() !== RW_KURVE) { return null; }
    return $k;
}

/**
 * Taugt dieser öffentliche Teil? Null heißt ja; sonst der Grund in einem
 * Satz. Geprüft werden Form (`RW_OEFFENTLICH_RE`) und Kurve — nicht, ob der
 * private Teil dazu passt: Das kann der Server nicht wissen, und deshalb
 * hängt das Schreiben am Passwortnachweis (E-RW-02, -06).
 */
function rw_oeffentlich_pruefen(string $spkiB64): ?string
{
    require_once __DIR__ . '/validate_lib.php';
    if (!preg_match(RW_OEFFENTLICH_RE, $spkiB64)) {
        return 'Der öffentliche Schlüssel hat ein unbrauchbares Format.';
    }
    if (rw_oeffentlich_laden($spkiB64) === null) {
        return 'Der öffentliche Schlüssel ist keiner auf der Kurve P-256.';
    }
    return null;
}

/**
 * Die Signatur prüfen: `$signatur` sind die 64 rohen Bytes `r‖s`. Jeder
 * Fehler — falsche Länge, anderer Schlüssel, anderes Verfahren — ist ein
 * Nein; die Funktion wirft nicht.
 */
function rw_pruefen(string $spkiB64, string $nachricht, string $signatur): bool
{
    if (strlen($signatur) !== 64) { return false; }
    $k = rw_oeffentlich_laden($spkiB64);
    if ($k === null) { return false; }
    try {
        return $k->withSignatureFormat('IEEE')->withHash('sha256')->verify($nachricht, $signatur) === true;
    } catch (Throwable) {
        return false;
    }
}

/** Wie oft der Selbsttest in diesem Aufruf lief — für die Probe. */
function rw_selbsttest_laeufe(bool $zaehlen = false): int
{
    static $n = 0;
    if ($zaehlen) { $n++; }
    return $n;
}

/**
 * Kann diese Anlage Signaturen des Rückwegs prüfen? (E-RW-11)
 *
 * Prüft den festen Vektor — richtig muss durchgehen, eine veränderte
 * Nachricht nicht — und sagt, auf welchem Weg und wie schnell.
 *
 * DER WEG IST DERSELBE, DEN `rw_pruefen()` NIMMT: ohne Zwang zuerst
 * `openssl`, und nur wenn das nicht geht, reines PHP. Um ihn zu benennen,
 * wird `openssl` einmal erzwungen; scheitert das, ist es reines PHP.
 * `$engine` erzwingt einen Weg (`'OpenSSL'` oder `'PHP'`), für die Probe.
 * Der Zwang ist in phpseclib eine Klassenvariable — er wird in jedem Fall
 * zurückgesetzt, sonst liefe jede spätere Prüfung dieses Aufrufs darunter.
 *
 * @return array{ok:bool, weg:string, ms:float}
 */
function rw_selbsttest(?string $engine = null): array
{
    rw_selbsttest_laeufe(true);
    $sig = (string)base64_decode(RW_VEKTOR_SIGNATUR, true);
    $versuch = static function (string $e) use ($sig): array {
        EC::forceEngine($e);
        try {
            /* EINMAL UNGEMESSEN VORAB: Der erste Aufruf eines Aufrufs lädt
               die Klassen von phpseclib (gemessen rund 15 ms), und das ist
               keine Eigenschaft der Prüfung. WAS DANACH GEMESSEN WIRD, ist
               das, was jede echte Prüfung kostet: den öffentlichen Teil laden
               — phpseclib zerlegt das SPKI in reinem PHP, rund 19 ms — und
               prüfen, über openssl rund 1 ms, in reinem PHP rund 170. */
            rw_pruefen(RW_VEKTOR_OEFFENTLICH, RW_VEKTOR_NACHRICHT, $sig);
            $t0 = microtime(true);
            $ja   = rw_pruefen(RW_VEKTOR_OEFFENTLICH, RW_VEKTOR_NACHRICHT, $sig);
            $nein = rw_pruefen(RW_VEKTOR_OEFFENTLICH, RW_VEKTOR_NACHRICHT . '.', $sig);
            $ms = (microtime(true) - $t0) * 1000 / 2;
            return ['ok' => $ja && !$nein, 'ms' => $ms];
        } finally {
            EC::forceEngine();
        }
    };
    if ($engine !== null) {
        $r = $versuch($engine);
        return ['ok' => $r['ok'], 'weg' => $engine === 'PHP' ? 'php' : 'openssl', 'ms' => $r['ms']];
    }
    $o = $versuch('OpenSSL');
    if ($o['ok']) { return ['ok' => true, 'weg' => 'openssl', 'ms' => $o['ms']]; }
    $p = $versuch('PHP');
    return ['ok' => $p['ok'], 'weg' => 'php', 'ms' => $p['ms']];
}

/**
 * Die Marke, unter der das Ergebnis gilt: Fassung der Anwendung und
 * Plattform. Ändert sich eins davon — ein Deploy, ein neues PHP oder
 * OpenSSL beim Hoster —, läuft der Selbsttest einmal neu.
 */
function rw_marke_schluessel(): string
{
    return hash('sha256', WEB_VERSION . '|' . PHP_VERSION . '|'
                        . (defined('OPENSSL_VERSION_TEXT') ? OPENSSL_VERSION_TEXT : '-'));
}

/**
 * Das zwischengespeicherte Ergebnis des Selbsttests (E-RW-11) — mit
 * Schlüssel `ok`, `weg`, `ms`. Läuft EINMAL je Marke, nicht je Seitenaufruf:
 * 190 ms in reinem PHP auf jeder Anmeldeseite wären zu viel.
 */
function rw_selbsttest_stand(): array
{
    $roh = app_state_lesen(RW_MARKE);
    $alt = $roh !== null ? json_decode($roh, true) : null;
    $marke = rw_marke_schluessel();
    if (is_array($alt) && ($alt['marke'] ?? '') === $marke) {
        return ['ok' => (bool)($alt['ok'] ?? false), 'weg' => (string)($alt['weg'] ?? ''),
                'ms' => (float)($alt['ms'] ?? 0)];
    }
    $t = rw_selbsttest();
    app_state_setzen(RW_MARKE, (string)json_encode($t + ['marke' => $marke]));
    if (!$t['ok']) {
        require_once __DIR__ . '/systemmeldung_lib.php';
        system_melden('rueckweg', 'Selbsttest der Rückweg-Prüfung fehlgeschlagen (Weg '
                    . $t['weg'] . ') — der Rückweg mit dem Wiederherstellungsschlüssel '
                    . 'ist abgeschaltet.');
    }
    return $t;
}

/** Schaltet den Weg: Kann die Anlage prüfen? */
function rw_verfuegbar(): bool
{
    return rw_selbsttest_stand()['ok'];
}

/**
 * Gibt es die Spalten des Paars? Vor `update.php` nicht — dann ist der
 * Rückweg stumm (E-RW-05). Einmal je Aufruf gefragt.
 */
function rw_spalten_da(?PDO $pdo = null): bool
{
    static $da = null;
    if ($da === null) {
        $da = db_hat_spalte($pdo ?? db(), 'users', 'rw_seit');
    }
    return $da;
}

/**
 * Der Stand des Paars eines Kontos (RW-02; E-RW-02, -05, -07, -15).
 *
 *   'da'       das Konto hat ein Paar; `seit` sagt, seit wann (UTC)
 *   'fehlt'    es hat keins — der Browser legt nach der Anmeldung eins an
 *   'spalten'  die Migration steht aus; der Browser tut nichts
 *   'demo'     das Demo-Konto; es bekommt keins (E-RW-15)
 *
 * EIN SELECT MIT RÜCKFALL, KEINE VORABFRAGE. `information_schema` bei jedem
 * Seitenaufbau zu fragen kostete einen Umlauf für einen Zustand, den es nach
 * dem ersten `update.php` nicht mehr gibt (Muster `auth_guard.php`). Der
 * Rückfall ist still, wie beim Tor des Zweitfaktors dort: Er meldete sonst
 * bei jeder Seite bis zur Migration dasselbe.
 *
 * `$pdo` nimmt die Probe, um den Rückfall ohne zurückgebaute Spalten zu
 * messen; die Anwendung ruft ohne.
 *
 * @return array{stand: string, seit: ?string}
 */
function rw_zustand(int $userId, ?PDO $pdo = null): array
{
    require_once __DIR__ . '/demo_lib.php';
    if (demo_ist_demo($userId)) { return ['stand' => 'demo', 'seit' => null]; }
    try {
        $st = ($pdo ?? db())->prepare('SELECT rw_oeffentlich IS NOT NULL, rw_seit
                                         FROM users WHERE id = ?');
        $st->execute([$userId]);
        $z = $st->fetch(PDO::FETCH_NUM);
    } catch (Throwable) {
        return ['stand' => 'spalten', 'seit' => null];
    }
    return $z !== false && (int)$z[0] === 1
        ? ['stand' => 'da', 'seit' => $z[1] !== null ? (string)$z[1] : null]
        : ['stand' => 'fehlt', 'seit' => null];
}
