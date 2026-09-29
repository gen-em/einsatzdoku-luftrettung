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
 * „GERAET MERKEN" (seit Web 21.8.0, Schritt 18, SR-02, E-SR-07, -17, -18,
 * -34). Nach einem Code aus der App kann der Browser fuer eine Dauer gemerkt
 * werden, die die BetreiberIn je Rollengruppe einstellt; dann fragt die
 * Anmeldung dort keinen Code. Die Helfer heissen `zweitfaktor_geraet_*`,
 * weil sie den FAKTOR meinen und nicht das Verfahren (E-SR-34) — ab SR-09
 * merkt auch ein Passkey. Das Datenmodell steht unten am Abschnitt.
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
             'codes_offen' => 0, 'codes_alle' => 0, 'geraete' => 0, 'fehlt' => true];
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
        'geraete'     => zweitfaktor_geraete_zahl($userId),
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
    /* VERWAISTE PASSKEYS RAEUMEN (H-SR-08, F-SR-38): Ein Faktor, der aus ist,
     * hat keine — steht trotzdem einer da (ein Weg an `totp_abschalten()`
     * vorbei, etwa das SQL des Notwegs), wuerde er mit dem neuen Faktor
     * wieder gelten. */
    require_once __DIR__ . '/passkey_lib.php';
    pk_alle_entfernen($userId, 'einrichtung');
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
 *         sich nicht öffnen (anderer Serverschlüssel) — dann helfen
 *         Wiederherstellungscodes, Passkeys (sie hängen nicht am Schlüssel,
 *         E-SR-49) und die Verwaltung. Es entsteht nur im Zweig des App-Codes.
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
 *
 * DIE GEMERKTEN GERAETE UND DIE PASSKEYS GEHEN MIT, auf jedem Weg (SR-02,
 * E-SR-07; SR-09, E-SR-29). Zu den Geraeten: Ein
 * Faktor, der aus ist, hat keine Geraete, die ihn ersetzen; und wer ihn
 * zuruecksetzen laesst, weil das Handy weg ist, will auch den Laptop nicht
 * mehr als bekannt gelten lassen, auf dem jemand anders sitzen koennte.
 *
 * IN DERSELBEN TRANSAKTION (seit Web 21.11.0, H-SR-08, F-SR-38): Bis dahin
 * liefen Geraete und Passkeys nach dem Commit. Brach das Loeschen ab, war der
 * Faktor aus und die Passkeys standen noch — und beim naechsten Einschalten
 * galten sie wieder. Jetzt gilt alles oder nichts, wie beim Passwortwechsel
 * (E-SR-40); die Protokolleintraege stehen mit in der Transaktion.
 */
function totp_abschalten(int $userId, string $weg): bool
{
    if (!totp_spalten_da()) { return false; }
    $vorher = totp_zustand($userId);
    require_once __DIR__ . '/passkey_lib.php';
    db_transaktion(db(), static function (PDO $pdo) use ($userId, $weg): void {
        $pdo->prepare('UPDATE users SET totp_geheimnis = NULL, totp_seit = NULL, totp_schritt = NULL
                        WHERE id = ?')->execute([$userId]);
        $pdo->prepare('DELETE FROM totp_codes WHERE user_id = ?')->execute([$userId]);
        zweitfaktor_geraete_vergessen($userId, 'zweitfaktor_' . $weg);
        /* DIE PASSKEYS GEHEN MIT, auf jedem Weg (SR-09, E-SR-29): Ein
         * Passkey ist ein Verfahren DIESES Faktors; ein Faktor, der aus ist,
         * hat keine. */
        pk_alle_entfernen($userId, 'zweitfaktor_' . $weg);
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


/* ===========================================================================
 * „GERAET MERKEN" (Schritt 18, SR-02; E-SR-07, -17, -18, -34)
 * ===========================================================================
 *
 * DAS COOKIE IST DER BESITZ, DIE TABELLE NUR DER HASH. Im Browser liegt
 * `EDGERAET` mit 32 Zufallsbyte (Parameter in `SITZUNG_COOKIES`,
 * `sitzung_lib.php`), in `vertraute_geraete` sein SHA-256 — dieselbe
 * Ueberlegung wie bei der Sitzungsbindung: Ein Datenbankabzug soll kein
 * Geraet zum bekannten machen. KEIN User-Agent, KEIN Geraetename (R36): Das
 * erste waere Telemetrie, das zweite eine Eingabe, die niemand pflegt.
 *
 * DIE DAUER WIRD BEIM PRUEFEN GERECHNET, NICHT BEIM MERKEN (E-SR-17). Die
 * Zeile traegt `angelegt_am`; gueltig ist sie, solange `angelegt_am` plus
 * die HEUTIGE Dauer ihrer Rollengruppe in der Zukunft liegt. So gilt eine
 * verkuerzte Einstellung sofort fuer alle, und „aus" (0) meldet alle Geraete
 * auf einmal ab — sonst hiesse „aus" erst in 30 Tagen aus. Das Cookie selbst
 * traegt die Dauer von damals als Ablauf; es darf laenger leben als die
 * Zeile, nicht umgekehrt.
 *
 * GEMERKT WIRD NUR NACH EINEM CODE AUS DER APP (E-SR-18) — nicht nach einem
 * Wiederherstellungscode und nicht nach dem Rueckweg: In beiden Lagen fehlte
 * gerade das Handy, und der Browser ist vor der Betroffenen nicht als ihrer
 * ausgewiesen. Die Entscheidung trifft `login.php`, das die Art kennt.
 *
 * VERGESSEN wird beim Passwortwechsel und -reset (wo `session_epoch`
 * steigt), bei `totp_abschalten()` auf jedem Weg, mit dem Konto (Kaskade),
 * im Demo-Reset und mit „Alle vergessen" im Profil. Protokolliert wird Merken
 * und Vergessen, nicht die Nutzung (E-SR-07).
 *
 * VOR `update.php` IST ALLES STUMM: ohne Tabelle kein Haken, kein Erkennen,
 * keine Zahl — dieselbe Regel wie fuer die Spalten des Zweitfaktors.
 */

/** Die waehlbaren Dauern in Tagen (E-SR-17); 0 heisst: kein Haken. */
const ZF_GERAET_TAGE_WAHL = [0, 1, 7, 14, 30, 90];
/** Die zwei Einstellungen in `app_state`, je Rollengruppe (Q-SR-10). */
const ZF_GERAET_K_USER       = 'zf_geraet_tage_user';
const ZF_GERAET_K_VERWALTUNG = 'zf_geraet_tage_verwaltung';
/** Die Vorgaben: NutzerInnen 30 Tage, die Verwaltung kuerzer (E-SR-17). */
const ZF_GERAET_VORGABE_USER       = 30;
const ZF_GERAET_VORGABE_VERWALTUNG = 7;

/** Gibt es die Tabelle schon? Ohne sie ist „Geraet merken" stumm. */
function zweitfaktor_geraete_da(?PDO $pdo = null): bool
{
    static $da = null;
    if ($da !== null && $pdo === null) { return $da; }
    $ergebnis = db_hat_tabelle($pdo ?? db(), 'vertraute_geraete');
    if ($pdo === null) { $da = $ergebnis; }
    return $ergebnis;
}

/** `user` oder `verwaltung` — die Verwaltung ist jede Rolle mit Pflicht zum
 *  Zweitfaktor (Support, Admin, BetreiberIn), dieselbe Menge wie das Tor. */
function zweitfaktor_geraet_gruppe(?string $rolle): string
{
    return rolle_braucht_zweitfaktor($rolle) ? 'verwaltung' : 'user';
}

/** Die eingestellte Dauer einer Rollengruppe in Tagen; ein Wert ausserhalb
 *  der Wahl gilt als nicht gesetzt, und dann gilt die Vorgabe. */
function zweitfaktor_geraet_tage(string $gruppe): int
{
    [$k, $vorgabe] = $gruppe === 'verwaltung'
        ? [ZF_GERAET_K_VERWALTUNG, ZF_GERAET_VORGABE_VERWALTUNG]
        : [ZF_GERAET_K_USER, ZF_GERAET_VORGABE_USER];
    $v = app_state_lesen($k);
    return ($v !== null && ctype_digit($v) && in_array((int)$v, ZF_GERAET_TAGE_WAHL, true))
        ? (int)$v : $vorgabe;
}

/** Die Dauer fuer eine Rolle (Tage); 0 heisst: kein Haken, nichts gilt. */
function zweitfaktor_geraet_dauer(?string $rolle): int
{
    return zweitfaktor_geraet_tage(zweitfaktor_geraet_gruppe($rolle));
}

/** Dieselbe Frage fuer ein Konto — die Rolle aus der Zeile, nicht aus der
 *  Sitzung (M1-05). Ein Konto, das es nicht gibt, hat 0. */
function zweitfaktor_geraet_dauer_konto(int $userId): int
{
    $st = db()->prepare('SELECT role FROM users WHERE id = ?');
    $st->execute([$userId]);
    $rolle = $st->fetchColumn();
    return $rolle === false ? 0 : zweitfaktor_geraet_dauer((string)$rolle);
}

/**
 * Diesen Browser fuer das Konto merken — nach einem Code aus der App.
 *
 * Wuerfelt 32 Byte, setzt `EDGERAET` mit der Dauer der Rollengruppe als
 * Ablauf und schreibt den Hash. Ein Cookie, das der Browser schon trug (ein
 * anderes Konto, eine abgelaufene Zeile), wird ersetzt; seine Zeile geht mit,
 * weil sie ohne Cookie niemand mehr vorzeigen kann.
 *
 * @return bool Gemerkt? Nein bei Dauer 0 und ohne Tabelle.
 */
function zweitfaktor_geraet_merken(int $userId): bool
{
    if (!zweitfaktor_geraete_da()) { return false; }
    $tage = zweitfaktor_geraet_dauer_konto($userId);
    if ($tage <= 0) { return false; }
    $alt = sitzung_cookie_lesen('geraet');
    if ($alt !== null) {
        db()->prepare('DELETE FROM vertraute_geraete WHERE token_hash = ?')
            ->execute([hash('sha256', $alt)]);
    }
    $wert = bin2hex(random_bytes(32));
    db()->prepare('INSERT INTO vertraute_geraete (user_id, token_hash, angelegt_am)
                   VALUES (?, ?, UTC_TIMESTAMP())')->execute([$userId, hash('sha256', $wert)]);
    sitzung_cookie_setzen('geraet', $wert, $tage * 86400);
    require_once __DIR__ . '/protokoll_lib.php';
    protokoll('verwaltung', 'zweitfaktor_geraet_gemerkt',
              'Gerät gemerkt — die Anmeldung fragt dort ' . $tage . ' Tage keinen Code',
              ['tage' => $tage], $userId);
    return true;
}

/**
 * Ist dieser Browser fuer das Konto gemerkt — und gilt das heute noch?
 *
 * Cookie → Hash → Zeile DIESES Kontos, deren `angelegt_am` plus die heutige
 * Dauer in der Zukunft liegt. Ein fremdes Cookie an einem anderen Konto
 * zaehlt nicht: Die Zeile muss zum Konto gehoeren. `zuletzt_am` wird
 * fortgeschrieben, ohne Protokoll (E-SR-07: je Nutzung waere Rauschen).
 *
 * ERST LESEN, DANN SCHREIBEN, und zwar ueber die Kennung: `rowCount()` eines
 * `UPDATE` zaehlt unter MySQL nur GEAENDERTE Zeilen — zwei Anmeldungen in
 * derselben Sekunde saehen sonst beim zweiten Mal kein Geraet.
 */
function zweitfaktor_geraet_erkannt(int $userId): bool
{
    if (!zweitfaktor_geraete_da()) { return false; }
    $wert = sitzung_cookie_lesen('geraet');
    if ($wert === null) { return false; }
    $tage = zweitfaktor_geraet_dauer_konto($userId);
    if ($tage <= 0) { return false; }
    $st = db()->prepare('SELECT id FROM vertraute_geraete
                          WHERE user_id = ? AND token_hash = ?
                            AND angelegt_am > UTC_TIMESTAMP() - INTERVAL ' . $tage . ' DAY');
    $st->execute([$userId, hash('sha256', $wert)]);
    $id = $st->fetchColumn();
    if ($id === false) { return false; }
    db()->prepare('UPDATE vertraute_geraete SET zuletzt_am = UTC_TIMESTAMP() WHERE id = ?')
        ->execute([(int)$id]);
    return true;
}

/** Wie viele gemerkte Geraete gelten fuer das Konto heute? */
function zweitfaktor_geraete_zahl(int $userId): int
{
    if (!zweitfaktor_geraete_da()) { return 0; }
    $tage = zweitfaktor_geraet_dauer_konto($userId);
    if ($tage <= 0) { return 0; }
    $st = db()->prepare('SELECT COUNT(*) FROM vertraute_geraete
                          WHERE user_id = ? AND angelegt_am > UTC_TIMESTAMP() - INTERVAL ' . $tage . ' DAY');
    $st->execute([$userId]);
    return (int)$st->fetchColumn();
}

/**
 * Alle gemerkten Geraete eines Kontos vergessen.
 *
 * `$weg` steht im Protokoll: `vergessen` (der Knopf im Profil), `passwort`,
 * `passwort_reset`, `zweitfaktor_<weg>` (aus `totp_abschalten()`). Ein
 * Eintrag nur, wenn etwas geloescht wurde — sonst stuende bei jedem
 * Passwortwechsel ein „0 vergessen" im Protokoll.
 *
 * @return int Zahl der geloeschten Zeilen, abgelaufene eingeschlossen.
 */
function zweitfaktor_geraete_vergessen(int $userId, string $weg): int
{
    if (!zweitfaktor_geraete_da()) { return 0; }
    $st = db()->prepare('DELETE FROM vertraute_geraete WHERE user_id = ?');
    $st->execute([$userId]);
    $n = $st->rowCount();
    if ($n > 0) {
        require_once __DIR__ . '/protokoll_lib.php';
        protokoll('verwaltung', 'zweitfaktor_geraete_vergessen',
                  'Gemerkte Geräte vergessen (' . $n . ') — ' . match ($weg) {
                      'vergessen'      => 'im Profil',
                      'passwort'       => 'mit dem Passwortwechsel',
                      'passwort_reset' => 'mit dem neuen Passwort',
                      default          => str_starts_with($weg, 'zweitfaktor_')
                                        ? 'mit dem Zweitfaktor' : $weg,
                  }, ['weg' => $weg, 'anzahl' => $n], $userId);
    }
    return $n;
}

/**
 * Abgelaufene Zeilen loeschen — der Schritt „Gemerkte Geräte" im
 * Aufraeumjob (`job_aufraeumen_schritte()`).
 *
 * JE ROLLE, weil jede Rolle ihre Gruppe und damit ihre Dauer hat. Eine Dauer
 * 0 loescht alle Zeilen der Gruppe — sie gelten ohnehin nicht mehr.
 *
 * @return int Zahl der geloeschten Zeilen.
 */
function zweitfaktor_geraete_aufraeumen(PDO $pdo): int
{
    if (!zweitfaktor_geraete_da($pdo)) { return 0; }
    $n = 0;
    foreach (array_keys(ROLLEN) as $rolle) {
        $tage = zweitfaktor_geraet_dauer($rolle);
        $st = $pdo->prepare('DELETE g FROM vertraute_geraete g JOIN users u ON u.id = g.user_id
                              WHERE u.role = ? AND g.angelegt_am <= UTC_TIMESTAMP() - INTERVAL '
                            . $tage . ' DAY');
        $st->execute([$rolle]);
        $n += $st->rowCount();
    }
    return $n;
}
