<?php
declare(strict_types=1);

/**
 * DER ZWEITFAKTOR (P5c/AP5, R38, Nr. 141; E-P5c-15, -41, -42, -53, -54).
 *
 * WAS ES IST. Zusätzlich zum Passwort ein sechsstelliger Code aus einer App
 * auf dem Handy — TOTP nach RFC 6238: HMAC-SHA1 über den Zeitschritt, 30 s,
 * sechs Stellen, ein Schritt Toleranz nach beiden Seiten. Pflicht für
 * Support, Admin und BetreiberIn (`rolle_braucht_zweitfaktor()`), Angebot für
 * NutzerInnen, gesperrt im Demo-Konto.
 *
 * DAS GEHEIMNIS liegt in `users.totp_geheimnis`, versiegelt mit dem
 * Serverschlüssel (`sk_versiegeln()`), Zweck `totp|<user_id>` — der Zweck
 * steht in der Prüfsumme und verhindert das Umhängen eines Geheimnisses auf
 * ein anderes Konto (E-P5c-54). Eingeschaltet ist der Zweitfaktor erst, wenn
 * `totp_seit` gesetzt ist, und das geschieht erst nach einem bestätigten Code:
 * Ein Geheimnis ohne `totp_seit` ist eine angefangene Einrichtung und zählt
 * bei der Anmeldung nicht.
 *
 * KEIN CODE GILT ZWEIMAL. `totp_schritt` hält den letzten angenommenen
 * Zeitschritt; angenommen wird nur ein späterer. Ohne das wäre derselbe Code
 * wegen der Toleranz rund 90 Sekunden lang wiederholbar.
 *
 * DIE WIEDERHERSTELLUNGSCODES HÄNGEN NICHT AM SERVERSCHLÜSSEL (E-P5c-42).
 * Zehn Stück, je acht Zeichen, gespeichert mit `password_hash()` in
 * `totp_codes`. Sie sind der Rückweg für genau den Fall, dass das Geheimnis
 * nicht mehr zu öffnen ist — ein anderer Serverschlüssel, ein eingespieltes
 * Komplett-Backup einer anderen Anlage. Hingen sie am selben Schlüssel,
 * fielen sie mit ihm.
 *
 * WAS HIER NICHT STEHT: der Rückweg über den Wiederherstellungsschlüssel.
 * E-P5c-42 sah ihn vor, geprüft gegen `pat_key_check` — einen Wert, den jeder
 * Datenbankabzug enthält (F-P5c-106). Er steht fälschungssicher in
 * `rueckweg_lib.php` (Konzept RW, seit Web 20.45.0); hier bleibt davon nur
 * `totp_abschalten(…, 'schluessel')`.
 *
 * OHNE DIE SPALTEN IST ALLES STUMM (E-P5c-36, -53). Zwischen Deploy und
 * `update.php` gibt es `totp_*` nicht; dann fragt die Anmeldung keinen Code,
 * und das Tor der Pflichtrollen schweigt. `totp_spalten_da()` ist die eine
 * Frage danach.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/vendor/laden.php';

const TOTP_SCHRITT_S       = 30;
const TOTP_STELLEN         = 6;
const TOTP_FENSTER         = 1;     // Schritte Toleranz nach beiden Seiten
const TOTP_GEHEIMNIS_BYTES = 20;    // 160 Bit, wie RFC 4226 empfiehlt
const TOTP_CODES           = 10;    // Wiederherstellungscodes (E-P5c-41)
const TOTP_CODE_LAENGE     = 8;
/** Dasselbe Alphabet wie der Wiederherstellungsschlüssel (`RC_CHARS` in
 *  crypto.js): ohne 0, 1, I, L, O und U — die Verwechslungszeichen. */
const TOTP_CODE_ZEICHEN    = 'ABCDEFGHJKMNPQRSTVWXYZ23456789';
/** Wie lange eine halbe Anmeldung (Passwort ja, Code noch nicht) gilt. */
const TOTP_HALB_FRIST_S    = 300;

/* ---- RFC 4226 / 6238 ------------------------------------------------------ */

/** HOTP (RFC 4226): HMAC-SHA1 über den Zähler, dynamisch gekürzt. */
function totp_hotp(string $geheimnis, int $zaehler, int $stellen = TOTP_STELLEN): string
{
    $h = hash_hmac('sha1', pack('J', $zaehler), $geheimnis, true);
    $o = ord($h[19]) & 0x0f;
    $bin = ((ord($h[$o]) & 0x7f) << 24) | (ord($h[$o + 1]) << 16)
         | (ord($h[$o + 2]) << 8) | ord($h[$o + 3]);
    return str_pad((string)($bin % (10 ** $stellen)), $stellen, '0', STR_PAD_LEFT);
}

/** Der Zeitschritt zu einem Zeitpunkt (Unix-Sekunden). */
function totp_schritt(int $zeit): int
{
    return intdiv($zeit, TOTP_SCHRITT_S);
}

/** Der Code zu einem Zeitpunkt. */
function totp_code(string $geheimnis, int $zeit, int $stellen = TOTP_STELLEN): string
{
    return totp_hotp($geheimnis, totp_schritt($zeit), $stellen);
}

/**
 * Passt die Eingabe? Gibt den getroffenen Zeitschritt zurück — oder null.
 *
 * Nur ein Schritt NACH `$letzter` zählt; derselbe Code ein zweites Mal ist
 * also abgewiesen, auch innerhalb der Toleranz. Alle drei Fenster werden
 * gerechnet, auch nach einem Treffer — die Laufzeit sagt dann nicht, welches
 * getroffen hat.
 */
function totp_code_passt(string $geheimnis, string $eingabe, int $zeit, ?int $letzter): ?int
{
    $eingabe = preg_replace('/\s+/', '', $eingabe) ?? '';
    if (!preg_match('/^\d{' . TOTP_STELLEN . '}$/', $eingabe)) { return null; }
    $jetzt = totp_schritt($zeit);
    $treffer = null;
    for ($d = -TOTP_FENSTER; $d <= TOTP_FENSTER; $d++) {
        if (hash_equals(totp_hotp($geheimnis, $jetzt + $d), $eingabe)) { $treffer = $jetzt + $d; }
    }
    if ($treffer === null || ($letzter !== null && $treffer <= $letzter)) { return null; }
    return $treffer;
}

/* ---- Darstellung: Base32, otpauth-Adresse, Codes ---------------------------- */

/** Base32 ohne Auffüllung, groß — so, wie jede Authenticator-App es liest. */
function totp_base32(string $roh): string
{
    return \ParagonIE\ConstantTime\Base32::encodeUpperUnpadded($roh);
}

/** Base32 in Vierergruppen, zum Abtippen. */
function totp_base32_gruppen(string $roh): string
{
    return trim(chunk_split(totp_base32($roh), 4, ' '));
}

/**
 * Die otpauth-Adresse (Key Uri Format). Aussteller ist der Name der
 * Installation — in der App steht dann „Gen-EM NAdoku: name@beispiel.de",
 * und zwei Installationen lassen sich auseinanderhalten.
 */
function totp_otpauth(string $roh, string $konto, string $aussteller): string
{
    /* Der Doppelpunkt zwischen Aussteller und Konto bleibt stehen, wie im
     * Key Uri Format beschrieben und im Mockup gezeigt; kodiert wird je Teil. */
    return 'otpauth://totp/' . rawurlencode($aussteller) . ':' . rawurlencode($konto)
         . '?secret=' . totp_base32($roh)
         . '&issuer=' . rawurlencode($aussteller)
         . '&algorithm=SHA1&digits=' . TOTP_STELLEN . '&period=' . TOTP_SCHRITT_S;
}

/** Zehn neue Wiederherstellungscodes, im Klartext — sie werden genau einmal
 *  gezeigt und nur als Hash gespeichert. */
function totp_codes_erzeugen(): array
{
    $codes = [];
    $n = strlen(TOTP_CODE_ZEICHEN);
    for ($i = 0; $i < TOTP_CODES; $i++) {
        $c = '';
        for ($j = 0; $j < TOTP_CODE_LAENGE; $j++) { $c .= TOTP_CODE_ZEICHEN[random_int(0, $n - 1)]; }
        $codes[] = $c;
    }
    return $codes;
}

/** Ein Code zum Zeigen: zwei Vierergruppen („K7QF 2MXD"). */
function totp_code_anzeige(string $code): string
{
    return substr($code, 0, 4) . ' ' . substr($code, 4);
}

/** Eine Eingabe als Code: groß, ohne Leerzeichen und Bindestriche — oder
 *  null, wenn Länge oder Zeichen nicht passen. */
function totp_code_normieren(string $eingabe): ?string
{
    $c = strtoupper(preg_replace('/[\s\-]+/', '', $eingabe) ?? '');
    if (strlen($c) !== TOTP_CODE_LAENGE) { return null; }
    return strspn($c, TOTP_CODE_ZEICHEN) === TOTP_CODE_LAENGE ? $c : null;
}

/* ---- Datenbank --------------------------------------------------------------- */

/** Gibt es die Spalten und die Codetabelle? Ohne sie ist der Zweitfaktor
 *  stumm (E-P5c-36, -53). Einmal je Anfrage gefragt.
 *
 *  EIN FEHLER DER DATENBANK IST KEIN „NEIN" (F-P5c-166). Bis Web 21.1.0 fing
 *  hier ein `catch (Throwable)` jeden Fehler und gab `false` — und
 *  `login.php` meldete darauf ohne Code-Schritt an. Ein Tor, das bei einem
 *  Fehler aufgeht, ist keines. `db_hat_spalte()` und `db_hat_tabelle()` sagen
 *  „nein", wenn es die Spalte nicht gibt; werfen tun sie nur, wenn die
 *  Datenbank selbst nicht antwortet, und dann bricht die Anfrage ab. */
function totp_spalten_da(?PDO $pdo = null): bool
{
    static $da = null;
    if ($da !== null && $pdo === null) { return $da; }
    $p = $pdo ?? db();
    $ergebnis = db_hat_spalte($p, 'users', 'totp_seit') && db_hat_tabelle($p, 'totp_codes');
    if ($pdo === null) { $da = $ergebnis; }
    return $ergebnis;
}

/**
 * Der Zustand eines Kontos.
 *
 * @return array{an:bool, seit:?string, angefangen:bool, codes_offen:int,
 *               codes_alle:int, fehlt:bool}
 *         `fehlt` heisst: Die Spalten gibt es noch nicht (`update.php` steht aus).
 */
function totp_zustand(int $userId): array
{
    $leer = ['an' => false, 'seit' => null, 'angefangen' => false,
             'codes_offen' => 0, 'codes_alle' => 0, 'fehlt' => true];
    if (!totp_spalten_da()) { return $leer; }
    $st = db()->prepare('SELECT totp_seit, totp_geheimnis IS NOT NULL AS g FROM users WHERE id = ?');
    $st->execute([$userId]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    if (!$z) { return ['fehlt' => false] + $leer; }
    $c = db()->prepare('SELECT COUNT(*) AS alle, SUM(benutzt_am IS NULL) AS offen
                          FROM totp_codes WHERE user_id = ?');
    $c->execute([$userId]);
    $n = $c->fetch(PDO::FETCH_ASSOC) ?: ['alle' => 0, 'offen' => 0];
    return [
        'an'          => $z['totp_seit'] !== null,
        'seit'        => $z['totp_seit'],
        'angefangen'  => $z['totp_seit'] === null && (int)$z['g'] === 1,
        'codes_offen' => (int)($n['offen'] ?? 0),
        'codes_alle'  => (int)($n['alle'] ?? 0),
        'fehlt'       => false,
    ];
}

/** Ist der Zweitfaktor für dieses Konto eingeschaltet? Ohne Spalten: nein. */
function totp_an(int $userId): bool
{
    return totp_zustand($userId)['an'];
}

/**
 * Eine Einrichtung beginnen: ein neues Geheimnis, versiegelt gespeichert,
 * noch NICHT eingeschaltet.
 *
 * @return array{ok:bool, geheimnis?:string, grund?:string}
 *         `grund`: 'spalten' (update.php steht aus), 'demo', 'an' (schon
 *         eingeschaltet — erst zurücksetzen), 'serverschluessel'
 */
function totp_einrichtung_beginnen(int $userId): array
{
    if (!totp_spalten_da()) { return ['ok' => false, 'grund' => 'spalten']; }
    require_once __DIR__ . '/demo_lib.php';
    if (demo_ist_demo($userId)) { return ['ok' => false, 'grund' => 'demo']; }
    if (totp_an($userId)) { return ['ok' => false, 'grund' => 'an']; }
    if (serverschluessel() === null) { return ['ok' => false, 'grund' => 'serverschluessel']; }
    $roh = random_bytes(TOTP_GEHEIMNIS_BYTES);
    db()->prepare('UPDATE users SET totp_geheimnis = ?, totp_seit = NULL, totp_schritt = NULL
                    WHERE id = ?')
        ->execute([sk_versiegeln($roh, 'totp|' . $userId), $userId]);
    return ['ok' => true, 'geheimnis' => $roh];
}

/** Das gespeicherte Geheimnis, geöffnet — oder null (keins, oder mit einem
 *  anderen Serverschlüssel versiegelt). */
function totp_geheimnis(int $userId): ?string
{
    if (!totp_spalten_da()) { return null; }
    $st = db()->prepare('SELECT totp_geheimnis FROM users WHERE id = ?');
    $st->execute([$userId]);
    $paket = $st->fetchColumn();
    if (!is_string($paket) || $paket === '') { return null; }
    return sk_oeffnen($paket, 'totp|' . $userId);
}

/**
 * Die Einrichtung abschließen: Der Code muss zum angefangenen Geheimnis
 * passen. Erst dann `totp_seit`, und erst dann gibt es Codes.
 *
 * @return array{ok:bool, codes?:list<string>, grund?:string}
 *         `grund`: 'keine' (keine angefangene Einrichtung), 'code'
 */
function totp_einrichtung_abschliessen(int $userId, string $eingabe): array
{
    $z = totp_zustand($userId);
    if ($z['fehlt'] || $z['an'] || !$z['angefangen']) { return ['ok' => false, 'grund' => 'keine']; }
    $roh = totp_geheimnis($userId);
    if ($roh === null) { return ['ok' => false, 'grund' => 'keine']; }
    $schritt = totp_code_passt($roh, $eingabe, time(), null);
    if ($schritt === null) { return ['ok' => false, 'grund' => 'code']; }
    $codes = totp_codes_erzeugen();
    db_transaktion(db(), static function (PDO $pdo) use ($userId, $schritt, $codes): void {
        $pdo->prepare('UPDATE users SET totp_seit = UTC_TIMESTAMP(), totp_schritt = ?
                        WHERE id = ? AND totp_seit IS NULL')
            ->execute([$schritt, $userId]);
        totp_codes_schreiben($pdo, $userId, $codes);
    });
    require_once __DIR__ . '/protokoll_lib.php';
    protokoll('verwaltung', 'totp_eingerichtet', 'Zweitfaktor eingeschaltet', [], $userId);
    return ['ok' => true, 'codes' => $codes];
}

/** Die Codes eines Kontos ersetzen (innerhalb einer Transaktion rufen). */
function totp_codes_schreiben(PDO $pdo, int $userId, array $codes): void
{
    $pdo->prepare('DELETE FROM totp_codes WHERE user_id = ?')->execute([$userId]);
    $ins = $pdo->prepare('INSERT INTO totp_codes (user_id, hash) VALUES (?, ?)');
    foreach ($codes as $c) { $ins->execute([$userId, password_hash($c, PASSWORD_DEFAULT)]); }
}

/**
 * Neue Wiederherstellungscodes — die alten gelten danach nicht mehr.
 *
 * @return list<string>|null null, wenn der Zweitfaktor nicht eingeschaltet ist
 */
function totp_codes_erneuern(int $userId): ?array
{
    if (!totp_an($userId)) { return null; }
    $codes = totp_codes_erzeugen();
    db_transaktion(db(), static function (PDO $pdo) use ($userId, $codes): void {
        totp_codes_schreiben($pdo, $userId, $codes);
    });
    require_once __DIR__ . '/protokoll_lib.php';
    protokoll('verwaltung', 'totp_codes_erneuert', 'Wiederherstellungscodes erneuert', [], $userId);
    return $codes;
}

/**
 * Ein Code bei der Anmeldung: sechs Ziffern aus der App oder ein
 * Wiederherstellungscode.
 *
 * ANGENOMMEN WIRD ATOMAR. Ein Zeitschritt wird nur übernommen, wenn er größer
 * ist als der gespeicherte; ein Code nur, wenn er noch offen ist — beides in
 * der Bedingung des UPDATE. Zwei gleichzeitige Anmeldungen mit demselben Code
 * kommen so nicht beide durch.
 *
 * `$nur` beschränkt auf einen Weg: 'app' prüft nur den App-Code, 'code' nur
 * die Wiederherstellungscodes. Die Anmeldeseite hat für beides ein eigenes
 * Feld, und die Meldung soll zu dem Feld passen, in das getippt wurde.
 *
 * @return array{ok:bool, art:?string, codes_offen?:int, geheimnis_fehlt?:bool}
 *         `art`: 'app' oder 'code'. `geheimnis_fehlt`: Das Geheimnis lässt
 *         sich nicht öffnen (anderer Serverschlüssel) — dann helfen nur noch
 *         Wiederherstellungscodes und die Verwaltung.
 */
function totp_anmeldung_pruefen(int $userId, string $eingabe, ?string $nur = null): array
{
    $nein = ['ok' => false, 'art' => null];
    if (!totp_an($userId)) { return $nein; }
    $ziffern = preg_replace('/\s+/', '', $eingabe) ?? '';
    if ($nur === 'app' && !preg_match('/^\d{' . TOTP_STELLEN . '}$/', $ziffern)) { return $nein; }
    if ($nur !== 'code' && preg_match('/^\d{' . TOTP_STELLEN . '}$/', $ziffern)) {
        $roh = totp_geheimnis($userId);
        if ($roh === null) { return $nein + ['geheimnis_fehlt' => true]; }
        $st = db()->prepare('SELECT totp_schritt FROM users WHERE id = ?');
        $st->execute([$userId]);
        $letzter = $st->fetchColumn();
        $schritt = totp_code_passt($roh, $ziffern, time(),
                                   $letzter === null || $letzter === false ? null : (int)$letzter);
        if ($schritt === null) { return $nein; }
        $up = db()->prepare('UPDATE users SET totp_schritt = ?
                              WHERE id = ? AND (totp_schritt IS NULL OR totp_schritt < ?)');
        $up->execute([$schritt, $userId, $schritt]);
        return $up->rowCount() === 1 ? ['ok' => true, 'art' => 'app'] : $nein;
    }
    $code = totp_code_normieren($eingabe);
    if ($code === null) { return $nein; }
    $st = db()->prepare('SELECT id, hash FROM totp_codes WHERE user_id = ? AND benutzt_am IS NULL');
    $st->execute([$userId]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
        if (!password_verify($code, (string)$z['hash'])) { continue; }
        $up = db()->prepare('UPDATE totp_codes SET benutzt_am = UTC_TIMESTAMP()
                              WHERE id = ? AND benutzt_am IS NULL');
        $up->execute([(int)$z['id']]);
        if ($up->rowCount() !== 1) { return $nein; }
        return ['ok' => true, 'art' => 'code', 'codes_offen' => totp_zustand($userId)['codes_offen']];
    }
    return $nein;
}

/**
 * Den Zweitfaktor eines Kontos abschalten: Geheimnis, Zeitschritt, Codes weg.
 *
 * `$weg` sagt, wer es tat: 'selbst' (Profil, nur ohne Pflicht),
 * 'verwaltung' (Kontoseite, E-P5c-42) oder — seit Konzept RW, RW-03 —
 * 'schluessel' (der Rückweg am Code-Schritt, `login.php` hinter dem Tor).
 * Verwaltung und Schlüssel sind beide ein ZURÜCKSETZEN und schreiben
 * `totp_zurueckgesetzt`; `daten.weg` unterscheidet sie (E-RW-14). Die Mail
 * an die Kontoadresse verschickt der Aufrufer. EINE Funktion, drei
 * Aufrufer — RW baut das Zurücksetzen nicht ein zweites Mal (3.3).
 */
function totp_abschalten(int $userId, string $weg): bool
{
    if (!totp_spalten_da()) { return false; }
    $vorher = totp_zustand($userId);
    db_transaktion(db(), static function (PDO $pdo) use ($userId): void {
        $pdo->prepare('UPDATE users SET totp_geheimnis = NULL, totp_seit = NULL, totp_schritt = NULL
                        WHERE id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM totp_codes WHERE user_id = ?')->execute([$userId]);
    });
    if ($vorher['an']) {
        require_once __DIR__ . '/protokoll_lib.php';
        protokoll('verwaltung', $weg === 'selbst' ? 'totp_ausgeschaltet' : 'totp_zurueckgesetzt',
                  match ($weg) {
                      'verwaltung' => 'Zweitfaktor durch die Verwaltung zurückgesetzt',
                      'schluessel' => 'Zweitfaktor mit dem Wiederherstellungsschlüssel zurückgesetzt',
                      default      => 'Zweitfaktor ausgeschaltet',
                  },
                  ['weg' => $weg], $userId);
    }
    return true;
}
