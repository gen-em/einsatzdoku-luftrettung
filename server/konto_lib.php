<?php
declare(strict_types=1);
/**
 * DER LEBENSZYKLUS EINES KONTOS (P5b/AP2, E-P5b-11, -12; R37 (1)).
 *
 * ---------------------------------------------------------------------------
 * WARUM ES DIESE DATEI GIBT
 * ---------------------------------------------------------------------------
 *
 * Ein Konto entsteht an vier Stellen und ein Token an vier Stellen, und bis
 * Web 20.16.0 brachte jede ihren eigenen Code mit:
 *
 *   admin_users.php    Konto + Kontokennung + 24-h-Setz-Token, in EINER
 *                      Transaktion (seit E17, mit dem Kommentar zum „halben
 *                      Zustand" davor)
 *   install.php        Konto (BetreiberIn) + 24-h-Token — OHNE Transaktion
 *   admin_user.php     nur Token, 1 h, entwertet Vorgaenger
 *   reset_request.php  nur Token, 1 h, entwertet Vorgaenger
 *
 * VIER FASSUNGEN DERSELBEN SACHE, und sie waren nicht gleich: Die eine hatte
 * eine Transaktionsklammer, die andere nicht; zwei entwerteten die alten
 * Token, zwei nicht (was bei einer Neuanlage richtig ist, aber aus einem
 * anderen Grund). Die Laufzeiten standen als SQL-Literale im Text —
 * `INTERVAL 24 HOUR` an zwei Stellen, `INTERVAL 1 HOUR` an zwei anderen.
 *
 * Das ist Backlog Nr. 202 Paket 1 (Token). **10b loest es hier**, und zwar
 * nicht aus Ordnungsliebe: Die Selbstregistrierung aus AP3 waere die FUENFTE
 * Fassung geworden, und sie ist die einzige, die von aussen erreichbar ist.
 *
 * ---------------------------------------------------------------------------
 * DIE VIER ZUSTAENDE EINES KONTOS (E-P5b-12, R37 (2))
 * ---------------------------------------------------------------------------
 *
 *   unbestaetigt  Registriert, Adresse noch nicht bestaetigt. Verfaellt nach
 *                 48 Stunden, fest (KONTEN_UNBESTAETIGT_H).
 *   wartet        Adresse bestaetigt, wartet auf die Freischaltung durch die
 *                 Verwaltung. Verfaellt nach der eingestellten Frist.
 *   aktiv         Der Normalfall. JEDES Bestandskonto bekommt ihn bei der
 *                 Migration — alles andere waere eine Aussage ueber Konten,
 *                 die es zum Zeitpunkt der Migration noch gar nicht gab.
 *   gesperrt      Von der Verwaltung gesperrt ODER in der Loeschkarenz
 *                 (E-P5b-16). `gesperrt_grund` unterscheidet beides.
 *
 * DER UEBERGANG IST NICHT FREI. `konto_status_setzen()` prueft ihn, und zwar
 * gegen eine Tabelle und nicht gegen eine Reihe von `if`-Zweigen: Ein Konto,
 * das von `aktiv` nach `unbestaetigt` zurueckfaellt, waere ein Konto, dessen
 * Besitzerin sich plotzlich nicht mehr anmelden kann, ohne dass jemand es
 * angeordnet hat.
 *
 * ---------------------------------------------------------------------------
 * WO DER STATUS GEPRUEFT WIRD — UND WO NICHT
 * ---------------------------------------------------------------------------
 *
 *   auth_guard.php   jede angemeldete Seite. `gesperrt` beendet die Sitzung
 *                    mit dem Endegrund `gesperrt`; `wartet` und
 *                    `unbestaetigt` fuehren auf eine Seite, die den Stand
 *                    sagt (sie koennen sich ohnehin nicht anmelden, aber eine
 *                    Sitzung kann aelter sein als die Statusaenderung).
 *   ingest.php       `403` mit JSON-Grund. Die Uhr puffert dann und schickt
 *                    spaeter — `403` heisst ihr heute „abgemeldet", der Grund
 *                    im Rumpf unterscheidet. Keine Client-Stufe noetig.
 *
 * NICHT geprueft wird in `login.php`: Dort faellt die Entscheidung frueher,
 * weil ein gesperrtes Konto gar nicht erst eine Sitzung bekommen soll. Der
 * Zweig sitzt im Erfolgspfad, unmittelbar nach der Passwortpruefung.
 */

/* `db.php` UND `protokoll_lib.php` NUR MIT KONFIGURATION — wegen des
 * Einrichters (dieselbe Falle wie in Nr. 215).
 *
 * `install.php` ist der vierte Aufrufer dieser Bibliothek und der einzige,
 * der laeuft, BEVOR es eine `config.php` gibt: Er schreibt sie erst, nachdem
 * er das erste Konto angelegt hat. Er bringt deshalb seine EIGENE
 * PDO-Verbindung mit — deshalb nimmt jede Funktion hier ein `?PDO` entgegen
 * und faellt nur ohne eines auf `db()` zurueck.
 *
 * Das Konzept sagt in E-P5b-11, alle vier Token-Stellen zoegen um. Das
 * stimmt — aber die vierte zieht nur um, wenn die Bibliothek ohne
 * Konfiguration ladbar ist. Waere sie es nicht, bliebe `install.php` als
 * fuenfte Fassung stehen, und zwar genau die, der die Transaktionsklammer
 * fehlte. */
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/db.php';
    require_once __DIR__ . '/protokoll_lib.php';
}
require_once __DIR__ . '/email_lib.php';

/* ---- Zustaende ----------------------------------------------------------- */

/** Die vier Zustaende, in der Reihenfolge ihres Lebenslaufs. */
const KONTO_STATUS = [
    'unbestaetigt' => 'unbestätigt',
    'wartet'       => 'wartet auf Freischaltung',
    'aktiv'        => 'aktiv',
    'gesperrt'     => 'gesperrt',
];

/**
 * Erlaubte Uebergaenge: von => [nach, ...].
 *
 * WARUM EINE TABELLE UND NICHT EINE REIHE VON `if`. Ein Uebergang, der
 * nirgends steht, ist ein Uebergang, den niemand nachlesen kann. Und die
 * Rueckwege sind die interessanten: Aus `gesperrt` geht es nach `aktiv`
 * zurueck (Entsperren, Ruecknahme der Selbstloeschung), aus `aktiv` aber
 * NICHT nach `unbestaetigt` — eine bestaetigte Adresse wird nicht wieder
 * unbestaetigt, und ein Konto, das sich ploetzlich nicht mehr anmelden kann,
 * ohne dass jemand es angeordnet hat, waere ein Fehler mit Ansage.
 */
const KONTO_UEBERGAENGE = [
    'unbestaetigt' => ['wartet', 'aktiv', 'gesperrt'],
    'wartet'       => ['aktiv', 'gesperrt'],
    'aktiv'        => ['gesperrt'],
    'gesperrt'     => ['aktiv'],
];

/** Gruende einer Sperre. `selbstloeschung` ist die Karenz aus E-P5b-16. */
const KONTO_SPERRGRUND_SELBSTLOESCHUNG = 'selbstloeschung';

/* ---- Laufzeiten der Token, als Konstanten statt als SQL-Literale --------- */

/** Einladung und Ersteinrichtung: 24 Stunden. */
const TOKEN_EINLADUNG_S = 86400;

/** Passwort zuruecksetzen: eine Stunde. */
const TOKEN_RESET_S = 3600;

/* ---- Konto anlegen ------------------------------------------------------- */

/**
 * Ein Konto anlegen — Zeile, Kontokennung, Setz-Token, alles in EINER
 * Transaktion.
 *
 * @param string $email   bereits geprueft und normalisiert (email_pruefen())
 * @param string $name    darf leer sein
 * @param string $rolle   `user` | `admin` | `betreiberin`
 * @param string $quelle  `einladung` | `registrierung` | `einrichtung`
 * @param string $status  Anfangszustand; `einladung` und `einrichtung` legen
 *                        `aktiv` an, die Registrierung `unbestaetigt`
 *
 * @return array{id:int, token:string} Die Kontonummer und der KLARTEXT-Token
 *         (der Hash steht in der Datenbank; den Klartext gibt es genau
 *         einmal, hier).
 *
 * @throws PDOException bei einer Dublette — der Aufrufer faengt sie und
 *         macht daraus seine Meldung. Diese Funktion kennt die Oberflaeche
 *         nicht.
 *
 * DIE TRANSAKTIONSKLAMMER IST DER ZWECK DER FUNKTION. `install.php` hatte
 * keine: Ein Abbruch zwischen `INSERT users` und `INSERT password_resets`
 * hinterliess ein Konto ohne Weg hinein — anmelden ging nicht (kein
 * Passwort), und der Einrichter lief nicht mehr (`install.lock` stand). Eine
 * Installation, aus der man sich selbst ausgesperrt hat.
 */
function konto_anlegen(string $email, string $name, string $rolle,
                       string $quelle, string $status = 'aktiv',
                       ?PDO $pdo = null): array
{
    if (!isset(KONTO_STATUS[$status])) {
        throw new InvalidArgumentException('Unbekannter Kontostatus: ' . $status);
    }

    $token = bin2hex(random_bytes(32));
    $pdo ??= db();
    $pdo->beginTransaction();
    try {
        /* `bestaetigt_am` wird MITGESCHRIEBEN, wenn das Konto schon aktiv
         * entsteht (Einladung, Einrichtung). Sonst stuende dort NULL bei
         * einem Konto, dessen Adresse die Verwaltung eingetippt hat — und
         * die Spalte hiesse „nie bestaetigt", wo „nicht noetig" gemeint
         * ist. */
        $pdo->prepare('INSERT INTO users (email, name, role, account_key, status, bestaetigt_am)
                       VALUES (?, ?, ?, ?, ?, ' . ($status === 'aktiv' ? 'NOW()' : 'NULL') . ')')
            ->execute([$email, $name !== '' ? $name : null, $rolle,
                       bin2hex(random_bytes(8)), $status]);
        $uid = (int)$pdo->lastInsertId();

        $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                       VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))')
            ->execute([$uid, hash('sha256', $token), TOKEN_EINLADUNG_S]);

        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        throw $ex;
    }

    /* DAS PROTOKOLL STEHT AUSSERHALB DER TRANSAKTION, und das ist Absicht:
     * Ein Protokolleintrag, der nicht geschrieben werden kann, darf die
     * Kontoanlage nicht zurueckrollen (V7). Innerhalb der Klammer waere er
     * genau das. */
    /* `function_exists` wegen des Einrichters: Er hat noch keine
     * `config.php` und damit kein `protokoll_lib.php`. Der Eintrag „erstes
     * Konto angelegt" faellt dort aus — und das ist richtig so, denn er
     * traege ohnehin keinen Urheber: Zu diesem Zeitpunkt gibt es noch
     * niemanden, der handeln koennte. */
    if (function_exists('protokoll')) {
        protokoll('verwaltung', 'konto_angelegt',
                  'Konto ' . $email . ' angelegt (' . $quelle . ', Rolle ' . $rolle
                . ', Status ' . $status . ')',
                  ['quelle' => $quelle, 'rolle' => $rolle, 'status' => $status], $uid);
    }

    return ['id' => $uid, 'token' => $token];
}

/* ---- Token ausstellen ---------------------------------------------------- */

/**
 * Einen Token ausstellen und alle vorherigen entwerten.
 *
 * @param int $userId
 * @param int $laufzeitS `TOKEN_EINLADUNG_S` oder `TOKEN_RESET_S`
 *
 * @return string der KLARTEXT-Token
 *
 * „EIN GUELTIGER TOKEN JE KONTO" GILT DAMIT AN ALLEN VIER STELLEN. Bisher
 * galt die Regel an zweien: `admin_user.php` und `reset_request.php`
 * entwerteten die Vorgaenger, `admin_users.php` und `install.php` nicht. Dort
 * war es unschaedlich, weil das Konto gerade erst entstand und keinen
 * Vorgaenger haben KANN — aber „unschaedlich, weil es den Fall nicht gibt"
 * ist eine Begruendung, die beim naechsten Umbau verfaellt. Jetzt tut es die
 * eine Funktion immer, und der Fall kann nicht mehr entstehen.
 */
function reset_token_ausstellen(int $userId, int $laufzeitS, ?PDO $pdo = null): string
{
    $token = bin2hex(random_bytes(32));
    $pdo ??= db();

    /* Erst entwerten, dann ausstellen — in dieser Reihenfolge, sonst
     * entwertete der zweite Schritt den soeben ausgestellten mit. */
    $pdo->prepare('UPDATE password_resets SET used_at = NOW()
                    WHERE user_id = ? AND used_at IS NULL')->execute([$userId]);
    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                   VALUES (?, ?, DATE_ADD(NOW(), INTERVAL ? SECOND))')
        ->execute([$userId, hash('sha256', $token), $laufzeitS]);

    return $token;
}

/* ---- Status ------------------------------------------------------------- */

/**
 * Den Status eines Kontos aendern, mit Pruefung des Uebergangs.
 *
 * @param string|null $grund nur bei `gesperrt` — er steht in der Meldung, die
 *                           die Nutzerin sieht, und im Protokoll
 * @return bool `false`, wenn der Uebergang nicht erlaubt ist oder das Konto
 *              nicht existiert. Dann ist NICHTS geaendert.
 */
function konto_status_setzen(int $userId, string $neu, ?string $grund = null): bool
{
    if (!isset(KONTO_STATUS[$neu])) { return false; }

    $pdo = db();
    $st = $pdo->prepare('SELECT status, email FROM users WHERE id = ?');
    $st->execute([$userId]);
    $zeile = $st->fetch(PDO::FETCH_ASSOC);
    if (!$zeile) { return false; }

    $alt = (string)$zeile['status'];
    if ($alt === $neu) { return true; }          // nichts zu tun, kein Fehler
    if (!in_array($neu, KONTO_UEBERGAENGE[$alt] ?? [], true)) {
        error_log('konto_status_setzen: Übergang ' . $alt . ' -> ' . $neu
                . ' ist nicht vorgesehen (Konto ' . $userId . ').');
        return false;
    }

    if ($neu === 'gesperrt') {
        $pdo->prepare('UPDATE users SET status = ?, gesperrt_seit = NOW(), gesperrt_grund = ?
                        WHERE id = ?')->execute([$neu, $grund, $userId]);
    } elseif ($neu === 'aktiv') {
        /* ENTSPERREN RAEUMT AUF. Bleiben `gesperrt_seit` und `loeschung_am`
         * stehen, traegt ein wieder aktives Konto den Vermerk einer Sperre,
         * die nicht mehr gilt — und der Loeschjob fände ein `loeschung_am`
         * in der Vergangenheit und loeschte ein Konto, dessen Besitzerin die
         * Loeschung gerade zurueckgenommen hat. Das ist kein Schoenheits-,
         * sondern ein Datenverlustfehler. */
        $pdo->prepare('UPDATE users SET status = ?, gesperrt_seit = NULL,
                              gesperrt_grund = NULL, loeschung_am = NULL,
                              bestaetigt_am = COALESCE(bestaetigt_am, NOW())
                        WHERE id = ?')->execute([$neu, $userId]);
    } else {
        $pdo->prepare('UPDATE users SET status = ? WHERE id = ?')->execute([$neu, $userId]);
    }

    protokoll('verwaltung', 'konto_status',
              'Konto ' . $zeile['email'] . ': ' . KONTO_STATUS[$alt] . ' → '
            . KONTO_STATUS[$neu] . ($grund !== null ? ' (' . $grund . ')' : ''),
              ['von' => $alt, 'nach' => $neu, 'grund' => $grund], $userId);

    return true;
}

/** Den Status eines Kontos lesen. `null` = es gibt das Konto nicht. */
function konto_status(int $userId): ?string
{
    $st = db()->prepare('SELECT status FROM users WHERE id = ?');
    $st->execute([$userId]);
    $v = $st->fetchColumn();
    return $v === false ? null : (string)$v;
}

/**
 * Der Satz, den eine Nutzerin zu ihrem Status zu lesen bekommt.
 *
 * An EINER Stelle, weil er an drei erscheint: Anmeldeseite, Sperrseite und
 * — gekuerzt — im JSON-Rumpf von `ingest.php`.
 */
function konto_status_text(string $status, ?string $grund = null): string
{
    return match ($status) {
        'unbestaetigt' => 'Diese Registrierung ist noch nicht bestätigt. Sieh in '
                        . 'deinem Postfach nach — auch im Spam-Ordner.',
        'wartet'       => 'Deine Registrierung liegt zur Freischaltung vor. Du bekommst '
                        . 'eine Nachricht, sobald sie erledigt ist.',
        'gesperrt'     => $grund === KONTO_SPERRGRUND_SELBSTLOESCHUNG
                        ? 'Du hast die Löschung dieses Kontos beantragt. Melde dich an, '
                        . 'um sie zurückzunehmen.'
                        : 'Dieses Konto ist gesperrt. Wende dich an die Verwaltung.',
        default        => '',
    };
}

/* ---- Adresswechsel mit Bestaetigung (E-P5b-16) --------------------------- */

/** Laufzeit des Bestaetigungslinks fuer eine neue Adresse: 24 Stunden. */
const TOKEN_ADRESSE_S = 86400;

/**
 * Eine neue Anmeldeadresse vormerken und den Bestaetigungslink ausstellen.
 *
 * **Die alte bleibt die gueltige, bis der Klick kommt.** Bis Web 20.19.0
 * schrieb `einstellungen.php` die neue Adresse sofort — mit Passwortnachweis
 * und Hinweismail an die alte, aber ohne jede Pruefung, ob die neue
 * ueberhaupt erreichbar ist. Ein Tippfehler sperrte damit aus: Die Anmeldung
 * laeuft ueber die Adresse, und „Passwort vergessen" schickt an eine
 * Adresse, die es nicht gibt.
 *
 * @return string der KLARTEXT-Token fuer den Link
 */
function adresse_vormerken(int $userId, string $neueAdresse): string
{
    $token = bin2hex(random_bytes(32));
    db()->prepare('UPDATE users
                      SET email_neu = ?, email_neu_token_hash = ?,
                          email_neu_bis = DATE_ADD(NOW(), INTERVAL ? SECOND)
                    WHERE id = ?')
        ->execute([$neueAdresse, hash('sha256', $token), TOKEN_ADRESSE_S, $userId]);
    return $token;
}

/**
 * Einen Bestaetigungslink einloesen.
 *
 * @return array{ok:bool, grund:string, alt:string, neu:string}
 *
 * DER ZWEITE, DER DIESELBE ADRESSE VORGEMERKT HAT, SCHEITERT HIER — und das
 * ist die richtige Stelle: `email_neu` traegt bewusst KEIN UNIQUE, weil eine
 * Sperre beim Vormerken verraten haette, dass jemand anders dieselbe Adresse
 * vorgemerkt hat. Das UNIQUE auf `email` faengt es beim Klick, und dann ist
 * die Auskunft „diese Adresse wird bereits verwendet" auch wahr.
 */
function adresse_bestaetigen(string $token): array
{
    $pdo = db();

    $st = $pdo->prepare('SELECT id, email, email_neu FROM users
                          WHERE email_neu_token_hash = ?
                            AND email_neu_bis > NOW()
                            AND email_neu IS NOT NULL');
    $st->execute([hash('sha256', $token)]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    if (!$z) {
        return ['ok' => false, 'alt' => '', 'neu' => '',
                'grund' => 'Dieser Link ist abgelaufen oder wurde schon benutzt. '
                         . 'Stelle den Wechsel in den Einstellungen noch einmal.'];
    }

    $alt = (string)$z['email'];
    $neu = (string)$z['email_neu'];

    try {
        $pdo->prepare('UPDATE users
                          SET email = ?, email_neu = NULL,
                              email_neu_token_hash = NULL, email_neu_bis = NULL
                        WHERE id = ?')->execute([$neu, (int)$z['id']]);
    } catch (PDOException $ex) {
        if (ist_dublettenfehler($ex)) {
            /* Die Vormerkung bleibt stehen: Vielleicht wird die andere
             * Adresse frei, und dann genuegt ein zweiter Klick auf denselben
             * Link — solange er gilt. */
            return ['ok' => false, 'alt' => $alt, 'neu' => $neu,
                    'grund' => 'Diese E-Mail-Adresse wird inzwischen von einem anderen '
                             . 'Konto verwendet. Die Adresse wurde nicht geändert.'];
        }
        throw $ex;
    }

    /* OHNE ADRESSEN IM TEXT (E-P5b-16). Das Protokoll haelt, DASS gewechselt
     * wurde, und die Kontonummer — nicht die beiden Adressen. Ein Audit, in
     * dem jede je benutzte Adresse eines Kontos steht, ist ein Verzeichnis
     * von Adressen und nicht ein Verzeichnis von Handlungen. */
    protokoll('verwaltung', 'adresse_geaendert',
              'Anmeldeadresse geändert (bestätigt über den Link an die neue Adresse)',
              [], (int)$z['id']);

    return ['ok' => true, 'grund' => '', 'alt' => $alt, 'neu' => $neu];
}

/** Eine vorgemerkte Adresse verwerfen (Abbruch durch die Nutzerin). */
function adresse_vormerkung_loeschen(int $userId): void
{
    db()->prepare('UPDATE users SET email_neu = NULL, email_neu_token_hash = NULL,
                          email_neu_bis = NULL WHERE id = ?')->execute([$userId]);
}

/* ---- Selbstloeschung mit Karenz (E-P5b-16) ------------------------------- */

/** Karenz zwischen Antrag und endgueltiger Loeschung, in Tagen. */
const KONTO_KARENZ_TAGE = 30;

/**
 * Die Loeschung des eigenen Kontos beantragen.
 *
 * Das Konto geht auf `gesperrt` mit dem Grund `selbstloeschung`, und
 * `loeschung_am` traegt den Termin. **Die Anmeldung ist der Rueckzug** —
 * `login.php` nimmt sie zurueck, ohne dass es dafuer einen Knopf braucht.
 *
 * WARUM KEIN EIGENER RUECKNAHMEWEG: Ein Link in der Mail, der etwas anderes
 * tut als anmelden, waere ein zweiter Weg mit eigenem Token und eigener
 * Frist — und er muesste ohne Passwort wirken, sonst braucht man ohnehin die
 * Anmeldung. Ein Rueckzug ohne Passwort ist aber genau das, was ein
 * Angreifer wollte, der die Loeschung verhindern will, um weiter mitzulesen.
 *
 * @return string der Termin als `Y-m-d H:i:s` (UTC)
 */
function konto_loeschung_beantragen(int $userId): string
{
    $pdo = db();
    $pdo->prepare('UPDATE users
                      SET status = "gesperrt", gesperrt_seit = NOW(),
                          gesperrt_grund = ?,
                          loeschung_am = DATE_ADD(NOW(), INTERVAL ? DAY)
                    WHERE id = ?')
        ->execute([KONTO_SPERRGRUND_SELBSTLOESCHUNG, KONTO_KARENZ_TAGE, $userId]);

    $st = $pdo->prepare('SELECT loeschung_am, email FROM users WHERE id = ?');
    $st->execute([$userId]);
    $z = $st->fetch(PDO::FETCH_ASSOC) ?: ['loeschung_am' => null, 'email' => ''];

    protokoll('verwaltung', 'loeschung_beantragt',
              'Konto ' . $z['email'] . ' zur Löschung angemeldet, Termin '
            . (string)$z['loeschung_am'],
              ['termin' => $z['loeschung_am']], $userId);

    return (string)$z['loeschung_am'];
}

/**
 * Konten, deren Karenz abgelaufen ist.
 *
 * Der Job holt sie sich hier und nicht mit eigener Abfrage: Die Bedingung
 * „welche sind faellig" gehoert neben die, die den Termin setzt.
 */
function konto_loeschung_faellig(int $grenze = 50): array
{
    $st = db()->prepare('SELECT id, email FROM users
                          WHERE status = "gesperrt"
                            AND gesperrt_grund = ?
                            AND loeschung_am IS NOT NULL
                            AND loeschung_am <= NOW()
                          ORDER BY loeschung_am
                          LIMIT ' . (int)$grenze);
    $st->execute([KONTO_SPERRGRUND_SELBSTLOESCHUNG]);
    return $st->fetchAll(PDO::FETCH_ASSOC);
}

/* ---- Loeschen ------------------------------------------------------------ */

/**
 * Ein Konto endgueltig loeschen — derselbe Weg fuer die Admin-Loeschung und
 * den Loeschjob der Karenz (E-P5b-16).
 *
 * @param bool $mitSicherungen E25: Sollen die Konto-Backups mit? Die Vorgabe
 *                             ist `true` und folgt der Zusage, dass nach der
 *                             Loeschung nichts mehr lesbar ist.
 *
 * @return array{ok:bool, grund:string} `ok=false` heisst: NICHTS wurde
 *         geloescht. Der Grund ist fuer die Oberflaeche.
 *
 * DIE REIHENFOLGE IST DER GANZE INHALT DIESER FUNKTION:
 *
 * 1. Die Backups zuerst — danach waere die Kontokennung fort, und der Ordner
 *    liesse sich nur noch ueber die Uebersicht der verwaisten Backups finden.
 * 2. Die Spuren und Schnitte von Hand: Sie haengen an keinem Fremdschluessel
 *    (polymorph ueber `owner_type`/`owner_id`) und ueberleben die Kaskade.
 *    Das ist F-S2-B, gemessen mit 6 202 931 verwaisten Spurpunkten.
 * 3. Der Protokolleintrag VOR dem DELETE. `protokoll_ereignisse` hat
 *    absichtlich keinen Fremdschluessel auf `users`, aber der Urheber kommt
 *    aus der Sitzung, und die Zeile soll geschrieben sein, bevor irgendetwas
 *    schiefgehen kann.
 * 4. Dann erst `DELETE FROM users` — die Kaskade nimmt vierzehn Tabellen mit.
 */
function konto_loeschen(int $userId, bool $mitSicherungen = true): array
{
    require_once __DIR__ . '/adminbackup_lib.php';
    require_once __DIR__ . '/spur_lib.php';

    $pdo = db();
    $st = $pdo->prepare('SELECT email, account_key FROM users WHERE id = ?');
    $st->execute([$userId]);
    $zeile = $st->fetch(PDO::FETCH_ASSOC);
    if (!$zeile) { return ['ok' => false, 'grund' => 'Dieses Konto gibt es nicht mehr.']; }

    $email   = (string)$zeile['email'];
    $kennung = (string)($zeile['account_key'] ?? '');

    /* 1. Backups (E25) */
    if ($mitSicherungen) {
        $weg = edbak_konto_ordner_loeschen($kennung !== '' ? $kennung : null);
        if (!$weg) {
            /* Nicht loeschen, wenn die Zusage nicht gehalten werden kann. Ein
             * Konto zu entfernen und das Backup stehen zu lassen, OBWOHL das
             * Gegenteil gewaehlt wurde, waere der schlechteste der drei
             * moeglichen Ausgaenge. */
            return ['ok' => false,
                    'grund' => 'Die Konto-Backups dieses Kontos liessen sich nicht '
                             . 'entfernen — das Konto wurde deshalb NICHT gelöscht. '
                             . 'Bitte unter „Konto-Backups" nachsehen.'];
        }
    }

    /* 2. Was die Kaskade nicht mitnimmt (F-S2-B) */
    foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tab]) {
        $ids = $pdo->prepare("SELECT id FROM `$tab` WHERE user_id = ?");
        $ids->execute([$userId]);
        spur_loeschen($pdo, $typ, $ids->fetchAll(PDO::FETCH_COLUMN));
    }
    schnitte_loeschen($pdo, 'konto', [$userId]);

    /* 3. Das Protokoll VOR dem DELETE */
    protokoll('verwaltung', 'konto_geloescht',
              'Konto ' . $email . ' endgültig gelöscht'
            . ($mitSicherungen ? ' (samt Konto-Backups)' : ' — Konto-Backups bleiben'),
              ['sicherungen' => $mitSicherungen], $userId);

    /* 4. Die Kaskade */
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$userId]);

    /* 5. WAS AN KEINEM FREMDSCHLUESSEL HAENGT: die `app_state`-Eintraege
     *    dieses Kontos (P5b/AP6).
     *
     * `mengen:<id>` und `mengen_gemeldet:<id>` sind Schluessel/Wert-Zeilen,
     * keine Tabelle mit `user_id` — die Kaskade erreicht sie nicht. Sie
     * blieben sonst liegen, und das ist nicht nur unordentlich: `users.id`
     * ist AUTO_INCREMENT, aber ein Wiederanlauf aus einer Sicherung kann
     * eine Id erneut vergeben. Das neue Konto faende dann den Mengenstand
     * des alten vor — und stuende womoeglich sofort an seiner Grenze,
     * ohne einen einzigen Einsatz.
     *
     * Gefunden am 16.09.2026 beim Pruefen der Mengengrenze: Ein Pruefkonto
     * bekam beim ersten Upload einen Cache-Wert aus einem frueheren Lauf
     * und wurde mit 507 abgewiesen, obwohl es leer war. */
    /* GELOESCHT, nicht auf Leerstring gesetzt. Das Hausmuster für „Marke
     * zurücksetzen" ist `app_state_setzen($k, '')` — hier geht es aber
     * nicht um eine Marke, die wieder gebraucht wird, sondern um ein Konto,
     * das es nicht mehr gibt. Eine Zeile, die nie wieder gelesen wird, ist
     * Ballast. */
    try {
        $pdo->prepare('DELETE FROM app_state WHERE k IN (?, ?)')
            ->execute(['mengen:' . $userId, 'mengen_gemeldet:' . $userId]);
    } catch (Throwable $ex) {
        error_log('app_state-Reste von Konto ' . $userId . ': ' . $ex->getMessage());
    }

    return ['ok' => true, 'grund' => ''];
}
