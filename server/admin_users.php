<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/smtp.php';
require_admin();
require_once __DIR__ . '/adminbackup_lib.php';

/**
 * NUTZERINNEN — die Liste, ausgelegt auf mehrere hundert Konten (E-P3-41, O9b).
 *
 * WAS SICH GEAENDERT HAT. Bis Web 9.8.0 war das eine ungefilterte Tabelle
 * ueber ALLE Konten mit vier Spalten, dazu ein Anlegen-Formular darunter und
 * je Zeile ein Loeschknopf. Bei dreissig Konten geht das; bei dreihundert ist
 * es eine Seite, die man durchscrollt, um eine Zeile zu suchen.
 *
 * Jetzt: vier Statuskacheln, eine Suche nach Name oder Adresse, fuenf
 * Filterplaketten mit Zahl, sieben Spalten, FUENFZIG Konten je Seite mit
 * Seitenwechsel — und eine Sammelleiste, deren Auswahl ueber die Seiten
 * hinweg gilt. Das Anlegen steht als „+ Anlegen" im Kartenkopf und oeffnet
 * einen Dialog; das LOESCHEN eines Kontos steht nur noch auf der Kontoseite,
 * wo die Entscheidung ueber die Backups mit dazugehoert (E25).
 *
 * WO DIE ARBEIT LIEGT, UND WARUM SIE DORT VERTRETBAR IST.
 *
 * Der Backup-Stand eines Kontos steht NICHT in der Datenbank, sondern im
 * Dateisystem. Drei Fragen der Seite haengen daran: die beiden roten Kacheln,
 * zwei der fuenf Filter und die Spalte „Backup". Ihn je Zeile zu holen
 * hiesse bei 300 Konten 300 Verzeichnisdurchlaeufe — genau der Fehler, den
 * die alte Backup-Seite gemacht hat (F-P3-F).
 *
 * Stattdessen: EIN Durchlauf der Ablagewurzel (edbak_staende(), je Ordner eine
 * kleine JSON-Datei) und EINE Abfrage ueber die Konten. Gemessen an 304 Konten:
 * 3,2 ms fuer die Ablage, 3,3 ms fuer die Abfrage, 3,2 ms fuers Werten.
 *
 * Gesucht, gefiltert und sortiert wird danach IM SPEICHER, nicht in SQL. Der
 * Grund ist nicht Bequemlichkeit: Zwei der fuenf Filter und eine der sieben
 * Sortierungen kennen kein SQL, weil ihre Angabe im Dateisystem liegt. Eine
 * halbe Filterung in SQL und eine halbe in PHP waere zwei Wege fuer dieselbe
 * Frage — und der zweite haette die falschen Zahlen. Was der Browser bekommt,
 * sind in jedem Fall hoechstens fuenfzig Zeilen.
 *
 * Die Grenze davon steht in docs/Backlog.md Nr. 37: Bei einigen tausend
 * Konten kippt das Verhaeltnis, und dann braucht der Backup-Stand eine
 * Spalte in der Datenbank statt eines Verzeichnisdurchlaufs.
 */

const KONTEN_JE_SEITE = 50;

/**
 * Zeitbudget einer Sammelaktion in Sekunden.
 *
 * KEINE ZAHL VON KONTEN, SONDERN EINE ZEIT. Ein Backup liest den ganzen
 * Bestand eines Kontos und schreibt eine Datei; gemessen an einem Konto mit
 * 82 Einsaetzen sind das 222 ms, an einem leeren 7 ms. Eine feste Obergrenze
 * von n Konten waere deshalb entweder fuer kleine Bestaende unnoetig streng
 * oder fuer grosse zu grosszuegig — und eine Grenze von genau einer Seite
 * machte die Auswahl UEBER Seiten hinweg gegenstandslos, die diese Liste
 * ausdruecklich zusagt.
 *
 * Zwanzig Sekunden liegen unter der `max_execution_time`, die geteilter
 * Webspace ueblicherweise setzt (30 bis 60 s). Was in dieser Zeit nicht
 * fertig wird, bleibt AUSGEWAEHLT und wird beim naechsten Klick erledigt —
 * die Seite sagt, wie viele das sind. Jedes einzelne Backup ist fuer sich
 * abgeschlossen; ein Halt zwischen zweien hinterlaesst nichts Halbes.
 */
const KONTEN_SAMMELBUDGET = 20.0;

/* DIE NAMEN TRAGEN IHREN ORT (O9b). `const FILTER` gibt es in suche.php
 * bereits — dort ist es der Katalog der Suchfilter. Zwei Seiten, zwei
 * Anfragen: heute stossen sie nicht zusammen. Aber eine Konstante auf
 * Dateiebene ist eine GLOBALE Konstante, und der Tag, an dem beide Seiten
 * eine gemeinsame Datei einbinden, ist der Tag mit dem Fatal. Dasselbe gilt
 * fuer die Funktionen weiter unten: `weg()` waere ein Name, den man ein
 * zweites Mal vergibt, ohne nachzusehen. */

/** Die fuenf Filter — Schluessel, Beschriftung und die Regel dazu. */
const KONTEN_FILTER = [
    'alle'        => 'Alle',
    'admins'      => 'Admins',
    /* WORTGLEICH MIT DEN KENNZAHLEN, die auf sie zeigen (S8/AP3, B-S8-07,
     * B-S8-19). Bis Web 15.1.0 hiess die Kennzahl „Backup überfällig" und der
     * Filter ebenso, die Kennzahl auf der Backup-Seite aber „überfällig ·
     * Liste öffnen" und der zweite Filter „Nie gesichert" gegen „nie
     * gesichert" — vier Namen fuer zwei Filter. Und „Backup" allein war
     * ohnehin zweideutig: Gemeint ist das Paket der VERWALTUNG, nicht das,
     * was eine NutzerIn sich selbst herunterlaedt (E-S8-06). */
    'ueberfaellig'=> 'Konto-Backup überfällig',
    'nie'         => 'nie Konto-Backup',
    'ohne-geraet' => 'Ohne Gerät',
];

/** Die sortierbaren Spalten — Schluessel und Beschriftung des Kopfes. */
const KONTEN_SPALTEN = [
    'konto'      => 'Konto',
    'rolle'      => 'Rolle',
    'seit'       => 'Seit',
    'angemeldet' => 'Zuletzt angemeldet',
    'geraete'    => 'Geräte',
    'sicherung'  => 'Konto-Backup',
];

$notice = null; $error = null; $setzLink = null;
$auswahlVerbraucht = false; $auswahlRest = '';

/**
 * Ein Wert aus der Adresse als Zeichenkette — oder die Vorgabe.
 *
 * `(string)($_GET['q'] ?? '')` sieht harmlos aus und ist es nicht: Bei
 * `?q[]=x` liefert PHP ein ARRAY, und die Umwandlung erzeugt die Warnung
 * „Array to string conversion" und den Wert „Array". Auf einer Installation
 * mit sichtbaren Warnungen stünde die Meldung mitten in der Seite. Was kein
 * String ist, ist keine Eingabe.
 */
function konten_param(string $name, string $vorgabe = ''): string
{
    $v = $_GET[$name] ?? null;
    return is_string($v) ? $v : $vorgabe;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---- Nutzer anlegen (M1-10) ----------------------------------------
     *
     * DREI DINGE WAREN HIER FALSCH, UND ZWAR ALLE DREI AN DERSELBEN STELLE:
     *
     *  1. DUBLETTE. Bei vorhandener Adresse warf die Datenbank eine
     *     ungefangene Ausnahme — der Admin sah eine weisse Seite oder einen
     *     Servertfehler statt einer Auskunft.
     *
     *  2. HALBER ZUSTAND. Konto und Setz-Token entstanden in zwei getrennten
     *     Anweisungen. Scheiterte die zweite, blieb ein Konto ohne jeden Weg
     *     zu einem Passwort zurueck — anmelden unmoeglich, und "Passwort
     *     vergessen" haette es zwar geheilt, aber niemand wusste davon.
     *
     *  3. VERWORFENER RUECKGABEWERT. smtp_send() liefert true/false; hier
     *     wurde es ignoriert und in jedem Fall "Setz-Link per E-Mail
     *     verschickt" gemeldet. Bei einem Fehlschlag existierte das Konto,
     *     ein gueltiger Token lag in der Datenbank — nur hatte niemand den
     *     Link, und es sagte auch niemand etwas.
     *
     * Der Versand liegt bewusst NACH dem Commit und wird NICHT ueber
     * antwort_abschliessen() entkoppelt: Hier soll das Ergebnis ja gerade
     * angezeigt werden. Eine messbare Antwortzeit ist an dieser Stelle
     * unbedenklich — die Seite ist nur fuer Admins erreichbar und beantwortet
     * keine Frage, die man nicht ohnehin stellen darf.
     */
    if ($action === 'user_add') {
        $email = email_pruefen($_POST['email'] ?? '');
        /* ROLLE AUS DEM FORMULAR — UND ZWEIMAL GEPRUEFT (R75).
         *
         * rolle_normieren() faengt alles ab, was nicht im Katalog steht; die
         * zweite Pruefung faengt den Fall, der davon nicht erfasst wird: Ein
         * Admin schickt 'betreiberin', obwohl das Auswahlfeld ihm die Option
         * gar nicht angeboten hat. Die Oberflaeche blendet sie aus, aber ein
         * ausgeblendetes Feld ist keine Pruefung — ein POST von Hand kennt
         * das Feld trotzdem. Wer nicht selbst BetreiberIn ist, kann keine
         * anlegen; das Konto entsteht dann als Admin. */
        $role  = rolle_normieren($_POST['role'] ?? 'user');
        if ($role === 'betreiberin' && !ist_betreiberin()) { $role = 'admin'; }
        $name  = trim((string)($_POST['name'] ?? ''));

        if ($email === null) {
            $error = 'Ungültige E-Mail-Adresse (höchstens 190 Zeichen).';
        } else {
            // Vorab pruefen: Das ergibt die verstaendliche Meldung. Die
            // Ausnahme unten faengt trotzdem — zwischen Pruefung und Einfuegung
            // kann ein zweiter Vorgang dieselbe Adresse anlegen.
            $vorh = db()->prepare('SELECT id FROM users WHERE email = ?');
            $vorh->execute([$email]);
            if ($vorh->fetch()) {
                $error = 'Es gibt bereits ein Konto mit dieser E-Mail-Adresse.';
            } else {
                $token = bin2hex(random_bytes(32));
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    /* Kontokennung bei der Anlage, nicht spaeter (E17).
                     * Sie ist ab hier unveraenderlich und der Ordnername des
                     * Admin-Backups; ein Konto ohne sie waere ein Konto, das
                     * sich nicht sichern laesst. */
                    $pdo->prepare('INSERT INTO users (email, name, role, account_key)
                                   VALUES (?, ?, ?, ?)')
                        ->execute([$email, $name !== '' ? $name : null, $role,
                                   bin2hex(random_bytes(8))]);
                    $uid = (int)$pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                                   VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                        ->execute([$uid, hash('sha256', $token)]);
                    $pdo->commit();
                    $angelegt = true;
                } catch (PDOException $ex) {
                    $pdo->rollBack();
                    $angelegt = false;
                    if (ist_dublettenfehler($ex)) {
                        $error = 'Es gibt bereits ein Konto mit dieser E-Mail-Adresse.';
                    } else {
                        error_log('user_add: ' . $ex->getMessage());
                        $error = 'Der Zugang konnte nicht angelegt werden. '
                               . 'Es wurde nichts gespeichert.';
                    }
                }

                if ($angelegt) {
                    $link = $CFG['app']['base_url'] . '/pw_handling.php?token=' . $token;
                    $ok = smtp_send($email,
                        'Willkommen bei der Gen-EM Einsatzdokumentation Notarzt',
                        "Hallo,\n\n"
                        . "für dich wurde ein Zugang zur Gen-EM Einsatzdokumentation Notarzt angelegt.\n"
                        . "Über den folgenden Link legst du dein persönliches Passwort fest — der Link ist\n"
                        . "24 Stunden gültig:\n\n"
                        . $link . "\n\n"
                        . "Dabei wird auch dein Wiederherstellungsschlüssel angezeigt. Bitte notiere ihn dir\n"
                        . "sicher — ohne ihn lassen sich die verschlüsselten Angaben nach einem späteren\n"
                        . "Passwort-Reset von niemandem mehr öffnen.\n\n"
                        . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
                        . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n");
                    if ($ok) {
                        $notice = 'Konto angelegt — Setz-Link per E-Mail verschickt.';
                    } else {
                        // Das Konto steht, nur die Mail kam nicht weg. Den Link
                        // hier zeigen ist der einzige Weg, der die Person noch
                        // erreicht; sonst bliebe ein unbrauchbares Konto stehen.
                        $notice = 'Konto angelegt — die E-Mail konnte NICHT verschickt werden.';
                        $setzLink = $link;
                    }
                }
            }
        }
    }

    /* ---- Auswahl sichern (Sammelleiste) ----------------------------------
     *
     * Die Auswahl kommt als EIN Feld mit kommagetrennten Kennungen, nicht als
     * `auswahl[]`: Sie gilt ueber alle Seiten, und die Kaestchen der uebrigen
     * Seiten stehen gar nicht im Formular. Das Skript unten fuehrt die Liste.
     */
    if ($action === 'sichern_auswahl') {
        $ids = array_values(array_unique(array_filter(array_map(
            'intval', explode(',', (string)($_POST['auswahl'] ?? ''))),
            static fn(int $i) => $i > 0)));
        if (!$ids) {
            $error = 'Es war kein Konto ausgewählt.';
        } else {
            $t0 = microtime(true);
            $gut = 0; $schlecht = []; $rest = [];
            foreach ($ids as $n => $id) {
                /* Vor jedem WEITEREN Backup pruefen, nicht nach dem
                 * letzten: So bricht die Reihe zwischen zwei Backups ab
                 * und nie mitten in einem. Das erste laeuft immer — sonst
                 * koennte eine Anfrage gar nichts tun und trotzdem melden,
                 * sie sei fertig. */
                if ($n > 0 && microtime(true) - $t0 > KONTEN_SAMMELBUDGET) {
                    $rest = array_slice($ids, $n);
                    break;
                }
                [$ok, $grund, ] = edbak_sicherung_erzeugen($id);
                if ($ok) { $gut++; } else { $schlecht[] = $grund; }
            }
            $notice = $gut . ' ' . ($gut === 1 ? 'Konto-Backup' : 'Konto-Backups')
                    . ' erzeugt.';
            if ($rest) {
                $notice .= ' ' . count($rest) . ' ' . (count($rest) === 1 ? 'Konto ist' : 'Konten sind')
                         . ' noch ausgewählt — die Zeit für eine Anfrage reicht nicht für alle '
                         . 'auf einmal. Noch einmal auf „Auswahl sichern" klicken macht weiter.';
            }
            /* DIE ERLEDIGTEN SIND AUS DER AUSWAHL RAUS. Bliebe sie unveraendert,
             * sagte die Sammelleiste danach weiter „50 ausgewählt", und der
             * naechste Klick sicherte dieselben Konten noch einmal, ohne dass
             * jemand das gemeint haette. Das Skript unten setzt sie auf den
             * Rest — bei vollstaendiger Erledigung also auf leer. */
            $auswahlVerbraucht = true;
            $auswahlRest = implode(',', $rest);
            if ($schlecht) {
                /* Gleichlautende Gruende zusammenfassen: Bei vielen Konten
                 * stuende derselbe Satz sonst dutzendfach untereinander und
                 * verdeckte den einen, der anders lautet. */
                $error = 'Nicht erzeugt: ' . implode(' · ', array_unique($schlecht));
            }
        }
    }
}

/* ---- Bestand einmal lesen ------------------------------------------------
 *
 * Eine Abfrage, ein Verzeichnisdurchlauf. Die Geraetezahl kommt als JOIN und
 * nicht als Unterabfrage je Zeile — sonst waeren es so viele Abfragen wie
 * Konten. `manual-%` bleibt draussen: Das ist der Platzhalter fuer von Hand
 * nachgetragene Einsaetze, kein Geraet.
 *
 * Die GROUP-BY-Liste nennt alle nicht aggregierten Spalten ausdruecklich.
 * MariaDB liesse `GROUP BY u.id` zu, MySQL mit ONLY_FULL_GROUP_BY nicht in
 * jeder Fassung — und eine Abfrage, die auf der einen Datenbank laeuft und
 * auf der anderen abbricht, faellt erst beim Umzug auf.
 */
$alle = db()->query(
    'SELECT u.id, u.email, u.name, u.role, u.created_at, u.last_login, u.account_key,
            COUNT(d.id) AS geraete
       FROM users u
       LEFT JOIN devices d ON d.user_id = u.id AND d.device_id NOT LIKE \'manual-%\'
      GROUP BY u.id, u.email, u.name, u.role, u.created_at, u.last_login, u.account_key'
)->fetchAll();

$staende = edbak_staende();
foreach ($alle as &$k) {
    $k['geraete'] = (int)$k['geraete'];
    $k['stand'] = edbak_stand_aus_karte($k['account_key'], $staende);
}
unset($k);

/* ---- Die Kacheln zaehlen den GANZEN Bestand ------------------------------
 *
 * Sie stehen ueber der Suche und sagen, wie es um die Installation steht —
 * nicht, wie es um das aussieht, was gerade im Suchfeld steht. Die Zahlen an
 * den Filterplaketten dagegen beziehen sich auf die laufende Suche: Sie
 * beantworten „was bringt mir dieser Filter jetzt?".
 *
 * SIE TRETEN AN DIE STELLE DER BACKUP-ERINNERUNG (A8.4). Bis Web 9.8.0
 * stand oben auf dieser Seite eine Warnung aus edbak_erinnerung() — mit der
 * ausdruecklichen Begruendung, sie stehe HIER und nicht nur auf der
 * Backup-Seite, weil ein Hinweis auf einer Seite, die man erst oeffnet,
 * wenn man ohnehin sichern will, niemandem etwas meldet. Diese Begruendung
 * gilt weiter; erfuellt wird sie jetzt besser.
 *
 * Denn edbak_erinnerung() liest EINE Marke: wann zuletzt IRGENDEIN Konto
 * gesichert wurde. Wer ein Konto taeglich sichert und dreihundert nie, bekam
 * damit „alles in Ordnung" gemeldet. Die beiden rechten Kacheln zaehlen
 * dagegen Konten — und sie fuehren mit einem Klick in genau die Liste, um die
 * es geht. Die alte Marke bleibt in der Bibliothek und traegt weiterhin die
 * Regelseite (admin_sicherungen.php, O9c).
 */
$gesamt = [
    'konten'       => count($alle),
    'admins'       => 0,
    'ueberfaellig' => 0,
    'nie'          => 0,
];
foreach ($alle as $k) {
    /* „Admins" zaehlt JEDES Konto mit Verwaltungsrechten, also auch die
     * BetreiberInnen (R75). Die Kennzahl beantwortet die Frage „wie viele
     * koennen hier verwalten?" — und darauf ist eine BetreiberIn ein Ja.
     * Wer wissen will, wer betreibt, sieht die Rollenspalte: Sie nennt drei
     * Werte, die Kennzahl fasst zwei davon zusammen. */
    if (rolle_darf_verwalten($k['role'])) { $gesamt['admins']++; }
    if ($k['stand']['stand'] === 'ueberfaellig') { $gesamt['ueberfaellig']++; }
    if ($k['stand']['stand'] === 'nie') { $gesamt['nie']++; }
}

/* ---- Suche --------------------------------------------------------------- */
$q = trim(konten_param('q'));
$gesucht = $alle;
if ($q !== '') {
    /* mb_stripos, nicht stripos: Namen tragen Umlaute, und `stripos` kennt
     * bei UTF-8 kein Ö als Grossbuchstaben von ö. */
    $gesucht = array_values(array_filter($alle, static function (array $k) use ($q): bool {
        return mb_stripos((string)$k['email'], $q) !== false
            || mb_stripos((string)($k['name'] ?? ''), $q) !== false;
    }));
}

/** Trifft ein Konto den Filter? Die eine Stelle, an der die Regeln stehen. */
function konten_trifft(array $k, string $f): bool
{
    return match ($f) {
        'admins'       => rolle_darf_verwalten($k['role']),
        'ueberfaellig' => $k['stand']['stand'] === 'ueberfaellig',
        'nie'          => $k['stand']['stand'] === 'nie',
        'ohne-geraet'  => $k['geraete'] === 0,
        default        => true,
    };
}

$zahlen = [];
foreach (array_keys(KONTEN_FILTER) as $f) {
    $zahlen[$f] = count(array_filter($gesucht, static fn($k) => konten_trifft($k, $f)));
}

$filter = konten_param('f', 'alle');
if (!isset(KONTEN_FILTER[$filter])) { $filter = 'alle'; }
$gefiltert = array_values(array_filter($gesucht,
    static fn($k) => konten_trifft($k, $filter)));

/* ---- Sortieren -----------------------------------------------------------
 *
 * Serverseitig, nicht im Browser: Bei fuenfzig Zeilen je Seite waere eine
 * Sortierung im Browser eine Sortierung der SEITE — sie schoebe die Zeilen
 * innerhalb der ersten fuenfzig um und liesse die uebrigen 250 unberuehrt.
 * Das sieht aus wie eine Sortierung und ist keine.
 */
$sort = konten_param('sort', 'konto');
if (!isset(KONTEN_SPALTEN[$sort])) { $sort = 'konto'; }
$ab = konten_param('dir', 'auf') === 'ab';

/**
 * Ein Name wird zu einem Sortierschluessel.
 *
 * OHNE DAS STEHT „Özdemir" HINTER „Zeller". `mb_strtolower` macht aus Ö ein ö,
 * und ö liegt in der Byte-Reihenfolge hinter z — in einer Namensliste heisst
 * das: Wer einen Namen mit Umlaut sucht, findet ihn am Ende, nach allem
 * anderen. Gemessen am Pruefbestand: „Ömer Sommer" stand vor der Aenderung an
 * erster Stelle der absteigenden Sortierung.
 *
 * Die deutsche Lesart (ae/oe/ue/ss) ist dieselbe wie in `slug()`
 * (assets/export.js) — eine Regel im Haus, nicht zwei. Uebrige Akzente gehen
 * auf den Grundbuchstaben zurueck.
 *
 * KEIN `Collator` (intl): Die Erweiterung ist auf geteiltem Webspace nicht
 * verlaesslich da, und eine Sortierung, die je nach Installation anders
 * ausfaellt, ist schlimmer als eine, die ueberall gleich naeherungsweise ist.
 */
function konten_sortschluessel(string $text): string
{
    $s = mb_strtolower($text, 'UTF-8');
    $s = strtr($s, ['ä' => 'ae', 'ö' => 'oe', 'ü' => 'ue', 'ß' => 'ss']);
    $s = strtr($s, [
        'á' => 'a', 'à' => 'a', 'â' => 'a', 'ã' => 'a', 'å' => 'a', 'ā' => 'a',
        'ç' => 'c', 'ć' => 'c', 'č' => 'c',
        'é' => 'e', 'è' => 'e', 'ê' => 'e', 'ë' => 'e', 'ē' => 'e',
        'í' => 'i', 'ì' => 'i', 'î' => 'i', 'ï' => 'i', 'ı' => 'i', 'ī' => 'i',
        'ğ' => 'g', 'ñ' => 'n', 'ń' => 'n',
        'ó' => 'o', 'ò' => 'o', 'ô' => 'o', 'õ' => 'o', 'ø' => 'o', 'ō' => 'o',
        'ś' => 's', 'š' => 's', 'ş' => 's',
        'ú' => 'u', 'ù' => 'u', 'û' => 'u', 'ū' => 'u',
        'ý' => 'y', 'ÿ' => 'y', 'ź' => 'z', 'ż' => 'z', 'ž' => 'z', 'ł' => 'l',
    ]);
    /* Was danach noch als kombinierendes Zeichen uebrig ist (etwa das
     * nachgestellte Punkt-oben aus dem tuerkischen İ), faellt weg. */
    return (string)preg_replace('/\p{Mn}/u', '', $s);
}

/** Der Wert, nach dem eine Spalte sortiert. */
function konten_sortwert(array $k, string $sort): string
{
    return match ($sort) {
        /* Drei Stufen, absteigend nach Rechten: BetreiberIn, Admin,
         * NutzerIn. Aufsteigend steht damit oben, wer am meisten darf. */
        'rolle'      => match (rolle_normieren($k['role'])) {
            'betreiberin' => '0', 'admin' => '1', default => '2',
        },
        'seit'       => (string)($k['created_at'] ?? ''),
        /* Nie angemeldet sortiert ans ENDE der aufsteigenden Reihenfolge und
         * nicht an den Anfang: Ein leerer Wert ist kein frueher Zeitpunkt. */
        'angemeldet' => (string)($k['last_login'] ?? '9999'),
        'geraete'    => str_pad((string)$k['geraete'], 6, '0', STR_PAD_LEFT),
        /* Backup: die Zahl der Tage seit der letzten. Was nie gesichert
         * wurde oder keine Kennung hat, gilt als unendlich alt — aufsteigend
         * steht das Frischeste oben, absteigend das Dringlichste. */
        /* max(0, …): Ein Paket mit einem Zeitpunkt in der Zukunft ergaebe
         * negative Tage, und „-5" mit Nullen aufgefuellt („0000-5")
         * sortierte irgendwohin. Kommt nicht vor — kostet aber nichts. */
        'sicherung'  => str_pad((string)max(0, (int)($k['stand']['tage'] ?? 99999)), 6, '0', STR_PAD_LEFT),
        default      => konten_sortschluessel(($k['name'] ?? '') !== ''
                            ? (string)$k['name'] : (string)$k['email']),
    };
}
usort($gefiltert, static function (array $a, array $b) use ($sort, $ab): int {
    $r = konten_sortwert($a, $sort) <=> konten_sortwert($b, $sort);
    /* Zweiter Schluessel ist immer die Adresse: Ohne ihn stuenden gleiche
     * Werte (etwa „1 Gerät") in einer Reihenfolge, die sich von Aufruf zu
     * Aufruf aendern kann — und ein Seitenwechsel zeigte dann Zeilen doppelt
     * oder gar nicht. */
    if ($r === 0) { $r = strcmp((string)$a['email'], (string)$b['email']); }
    return $ab ? -$r : $r;
});

/* ---- Seite --------------------------------------------------------------- */
$treffer = count($gefiltert);
$seiten  = max(1, (int)ceil($treffer / KONTEN_JE_SEITE));
$seite   = max(1, min($seiten, (int)konten_param('s', '1')));
$zeilen  = array_slice($gefiltert, ($seite - 1) * KONTEN_JE_SEITE, KONTEN_JE_SEITE);
$von     = $treffer === 0 ? 0 : ($seite - 1) * KONTEN_JE_SEITE + 1;
$bis     = min($treffer, $seite * KONTEN_JE_SEITE);

/** Eine Adresse dieser Seite mit geaenderten Parametern. */
function konten_weg(array $neu = []): string
{
    $p = array_merge([
        'q'    => konten_param('q'),
        'f'    => konten_param('f'),
        'sort' => konten_param('sort'),
        'dir'  => konten_param('dir'),
        's'    => konten_param('s'),
    ], $neu);
    $p = array_filter($p, static fn($v) => (string)$v !== '');
    return 'admin_users.php' . ($p ? '?' . http_build_query($p) : '');
}

ui_seite_start(['titel' => 'NutzerInnen']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin']); ?>

  <?php ui_titelzeile(['titel' => 'NutzerInnen']); ?>
  <p class="seiten-erklaerung">Jedes Konto hat eine eigene Seite mit allen
     Verwaltungsaufgaben: Kontodaten, Geräte und Konto-Backups.
     Ein Klick auf eine Zeile öffnet sie.</p>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if ($setzLink !== null): ?>
    <?= ui_meldung_markup('warn',
        'Der Einladungslink konnte nicht per E-Mail zugestellt werden. Das Konto '
        . 'ist angelegt, der Link 24 Stunden gültig — bitte auf einem anderen Weg '
        . 'an die Person selbst weitergeben. Wer ihn hat, kann das Passwort des '
        . 'neuen Kontos setzen. Die Ursache steht im Fehlerprotokoll des Webspace.') ?>
    <p class="codeblock"><?= e($setzLink) ?></p>
  <?php endif; ?>

  <?php /* ---- Die vier Statuskacheln (Mockup 41) --------------------------
       Jede ist ein Weg in die Liste, die sie meint. Die beiden linken tragen
       keinen Ton — sie sind Bestandszahlen, keine Befunde. */ ?>
  <?php /* NULL IST KEIN BEFUND. Der Ton haengt an der Zahl, nicht an der
           Kachel: „0 Konto-Backup überfällig" in Warnorange behauptete ein
           Problem, wo gerade keines ist — und wer das ein paarmal gesehen
           hat, sieht die Farbe nicht mehr, wenn sie einmal etwas bedeutet.
           Bei 0 ist die Kachel eine gewoehnliche Bestandszahl.

           Die linke Kachel ist NICHT „aktiv": Sie zaehlt den Bestand, sie
           ist kein Filterzustand. Welcher Filter gilt, sagt die Plakettenreihe
           in der Karte darunter (Mockup 41).

           JEDE KACHEL LOESCHT DIE SUCHE MIT. Sie nennt eine Zahl aus dem
           GANZEN Bestand — bliebe beim Klick ein Suchbegriff stehen, führte
           „7 Admins" auf eine Liste mit dreien, und die Kachel hätte gelogen.
           Die Filterplaketten darunter machen es umgekehrt richtig: Sie
           zählen innerhalb der Suche und behalten sie deshalb. */ ?>
  <div class="kennzahl-raster kennzahl-raster-4">
    <?= ui_kennzahl(['wert' => number_format($gesamt['konten'], 0, ',', '.'),
                     'label' => 'Konten',
                     'href' => konten_weg(['f' => '', 'q' => '', 's' => ''])]) ?>
    <?= ui_kennzahl(['wert' => (string)$gesamt['admins'], 'label' => 'Admins',
                     'href' => konten_weg(['f' => 'admins', 'q' => '', 's' => ''])]) ?>
    <?= ui_kennzahl(['wert' => (string)$gesamt['ueberfaellig'],
                     'label' => 'Konto-Backup überfällig',
                     'ton' => $gesamt['ueberfaellig'] > 0 ? 'orange' : '',
                     'href' => konten_weg(['f' => 'ueberfaellig', 'q' => '', 's' => ''])]) ?>
    <?= ui_kennzahl(['wert' => (string)$gesamt['nie'], 'label' => 'nie Konto-Backup',
                     'ton' => $gesamt['nie'] > 0 ? 'rot' : '',
                     'href' => konten_weg(['f' => 'nie', 'q' => '', 's' => ''])]) ?>
  </div>

  <?php ui_karte_start([
      'titel' => 'Konten', 'id' => 'k-konten',
      'zahl' => number_format($treffer, 0, ',', '.'),
      'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus', 'art' => 'orange',
                   'href' => '#', 'attr' => 'data-dialog="dlg-anlegen"'],
  ]); ?>

    <?php /* ---- Suche und Filter ---------------------------------------- */ ?>
    <div class="listenkopf">
      <form method="get" class="listensuche" role="search">
        <?php foreach (['f' => $filter] as $n => $v): if ($v !== '' && $v !== 'alle'): ?>
          <input type="hidden" name="<?= e($n) ?>" value="<?= e($v) ?>">
        <?php endif; endforeach; ?>
        <?php if ($sort !== 'konto'): ?>
          <input type="hidden" name="sort" value="<?= e($sort) ?>">
        <?php endif; ?>
        <?php if ($ab): ?><input type="hidden" name="dir" value="ab"><?php endif; ?>
        <label class="nur-vorlesen" for="q">Name oder E-Mail suchen</label>
        <div class="suchfeld">
          <?= ui_symbol('lupe', 'suchfeld-lupe') ?>
          <input type="search" id="q" name="q" value="<?= e($q) ?>"
                 placeholder="Name oder E-Mail" autocomplete="off">
        </div>
        <button class="knopf knopf-neutral nur-vorlesen" type="submit">Suchen</button>
      </form>
      <div class="filterreihe">
        <?php foreach (KONTEN_FILTER as $key => $text): ?>
          <a class="listenfilter<?= $filter === $key ? ' aktiv' : '' ?>"
             href="<?= e(konten_weg(['f' => $key === 'alle' ? '' : $key, 's' => ''])) ?>"
             <?= $filter === $key ? 'aria-current="true"' : '' ?>><span><?= e($text) ?></span>
            <span class="listenfilter-zahl"><?= (int)$zahlen[$key] ?></span></a>
        <?php endforeach; ?>
      </div>
    </div>

    <?php if (!$zeilen): ?>
      <p class="feld-hinweis"><?= $q !== '' || $filter !== 'alle'
          ? 'Kein Konto passt zu Suche und Filter.'
          : 'Es gibt noch kein Konto.' ?></p>
    <?php else: ?>

    <?php /* ---- Ab 720 px die Tabelle ------------------------------------ */ ?>
    <div class="tabelle-scroll nur-ab-720">
      <table class="tabelle tabelle-konten">
        <thead><tr>
          <th class="wahl-spalte"><span class="nur-vorlesen">Auswahl</span></th>
          <?php foreach (KONTEN_SPALTEN as $key => $text):
            $istAktiv = $sort === $key;
            $neuAb = $istAktiv && !$ab; ?>
            <th class="sortable"
                <?= $istAktiv ? 'aria-sort="' . ($ab ? 'descending' : 'ascending') . '"' : '' ?>>
              <a href="<?= e(konten_weg(['sort' => $key === 'konto' ? '' : $key,
                                  'dir' => $neuAb ? 'ab' : '', 's' => ''])) ?>"><?= e($text) ?><?php
                if ($istAktiv): ?><span class="arrow"><?= ui_symbol('pfeil-hoch',
                    $ab ? 'symbol-oben' : '', $ab ? 'absteigend' : 'aufsteigend') ?></span><?php
                endif; ?></a>
            </th>
          <?php endforeach; ?>
          <th class="oeffnen-spalte"></th>
        </tr></thead>
        <tbody>
        <?php foreach ($zeilen as $k):
          [$standText, $standTon] = edbak_stand_plakette($k['stand']);
          $ziel = 'admin_user.php?id=' . (int)$k['id']; ?>
          <tr class="clickable" data-ziel="<?= e($ziel) ?>">
            <td class="wahl-spalte"><input type="checkbox" data-kontowahl
                value="<?= (int)$k['id'] ?>"
                aria-label="<?= e((string)($k['name'] ?: $k['email'])) ?> auswählen"></td>
            <td>
              <span class="konto-name"><?= e((string)($k['name'] ?: '—')) ?></span>
              <span class="konto-mail"><?= e((string)$k['email']) ?></span>
            </td>
            <?php /* FUENF SPALTEN MITTIG (S3/AP5, Block B). Rolle, Seit,
                     Zuletzt angemeldet, Geraete und Backup standen links,
                     ihre Titel aber mittig (F-N1-G) — die Ueberschrift stand
                     ueber nichts. Keiner dieser Werte ist Flietext oder eine
                     Groesse zum Vergleichen; mittig stehen sie unter ihrem
                     Titel. KONTO bleibt linksbuendig: Name und Adresse sind
                     Text und werden gelesen, nicht verglichen. */ ?>
            <td class="mitte-spalte"><?= e(rolle_text($k['role'])) ?></td>
            <td class="mitte-spalte"><?= e($k['created_at'] ? fmt_local($k['created_at'], 'd.m.Y') : '—') ?></td>
            <td class="mitte-spalte"><?= e($k['last_login'] ? fmt_local($k['last_login'], 'd.m.Y') : '—') ?></td>
            <td class="mitte-spalte"><?= (int)$k['geraete'] ?></td>
            <td class="mitte-spalte"><?= ui_plakette($standText, ['ton' => $standTon]) ?></td>
            <td class="oeffnen-spalte"><a class="oeffnen" href="<?= e($ziel) ?>"><?=
              ui_symbol('winkel', 'symbol-rechts') ?><span>Öffnen</span></a></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </div>

    <?php /* ---- Unter 720 px die Zeilen ----------------------------------
         Dieselben Angaben, andere Form: Name und Adresse untereinander, der
         Stand als Plakette, das Kaestchen davor. Eine Tabelle mit acht
         Spalten auf 360 px waere dasselbe wie in der Einsatzliste (E-P3-32):
         Sie gibt den wichtigen Spalten null Pixel und behaelt die Zahlen. */ ?>
    <div class="nur-unter-720">
      <?php foreach ($zeilen as $k):
        [$standText, $standTon] = edbak_stand_plakette($k['stand']);
        $klein = [rolle_text($k['role'])];
        $klein[] = (int)$k['geraete'] . ($k['geraete'] === 1 ? ' Gerät' : ' Geräte');
        $klein[] = 'zuletzt ' . ($k['last_login'] ? fmt_local($k['last_login'], 'd.m.Y') : '—');
        ui_zeile([
          'vorn'  => '<input type="checkbox" data-kontowahl value="' . (int)$k['id']
                   . '" aria-label="' . e((string)($k['name'] ?: $k['email'])) . ' auswählen">',
          'text'  => (string)($k['name'] ?: $k['email']),
          'klein' => ($k['name'] ? $k['email'] . ' · ' : '') . implode(' · ', $klein),
          'href'  => 'admin_user.php?id=' . (int)$k['id'],
          'plaketten' => ui_plakette($standText, ['ton' => $standTon]),
        ]);
      endforeach; ?>
    </div>

    <?php /* ---- Fuss: Zaehlung und Seitenwechsel -------------------------- */ ?>
    <div class="listenfuss">
      <p class="listenzahl">Konten <?= $von ?>–<?= $bis ?> von
         <?= number_format($treffer, 0, ',', '.') ?></p>
      <?php if ($seiten > 1): ?>
        <nav class="seitenwahl" aria-label="Seiten">
          <a class="seitenknopf<?= $seite <= 1 ? ' aus' : '' ?>"
             <?= $seite > 1 ? 'href="' . e(konten_weg(['s' => (string)($seite - 1)])) . '"' : 'aria-disabled="true"' ?>
             aria-label="Vorige Seite"><?= ui_symbol('winkel', 'symbol-links') ?></a>
          <?php
          /* Erste, letzte und die Nachbarn der aktuellen Seite; dazwischen
             eine Ellipse. Bei sieben Seiten stehen alle da, bei siebzig nicht
             — eine Leiste, die mit dem Bestand waechst, ist keine Leiste. */
          $zeigen = [1, $seiten, $seite, $seite - 1, $seite + 1];
          $zeigen = array_values(array_unique(array_filter($zeigen,
              static fn($n) => $n >= 1 && $n <= $seiten)));
          sort($zeigen);
          $vorher = 0;
          foreach ($zeigen as $n):
            if ($vorher && $n > $vorher + 1): ?>
              <span class="seitenluecke" aria-hidden="true">…</span>
            <?php endif; $vorher = $n; ?>
            <a class="seitenknopf<?= $n === $seite ? ' aktiv' : '' ?>"
               href="<?= e(konten_weg(['s' => $n === 1 ? '' : (string)$n])) ?>"
               <?= $n === $seite ? 'aria-current="page"' : '' ?>><?= $n ?></a>
          <?php endforeach; ?>
          <a class="seitenknopf<?= $seite >= $seiten ? ' aus' : '' ?>"
             <?= $seite < $seiten ? 'href="' . e(konten_weg(['s' => (string)($seite + 1)])) . '"' : 'aria-disabled="true"' ?>
             aria-label="Nächste Seite"><?= ui_symbol('winkel', 'symbol-rechts') ?></a>
        </nav>
      <?php endif; ?>
    </div>
    <?php endif; ?>

  <?php ui_karte_ende(); ?>

  <?php /* ---- Sammelleiste: gilt ueber alle Seiten ----------------------- */ ?>
  <form method="post" id="f-auswahl" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="sichern_auswahl">
    <input type="hidden" name="auswahl" id="auswahlfeld" value="">
  </form>
  <?php ui_speichern_leiste([
      'id' => 'sammelleiste', 'kein_haken' => true, 'form' => 'f-auswahl',
      'text' => 'Auswahl sichern', 'symbol' => 'sicherung',
      'zahl' => 'auswahlzahl', 'hinweis' => '0 ausgewählt',
  ]); ?>

  <?php /* ---- Anlegen als Dialog (assets/dialog.js) ---------------------- */ ?>
  <dialog class="dialog" id="dlg-anlegen">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="user_add">
      <div class="dialog-kopf"><h2>Konto anlegen</h2></div>
      <div class="dialog-inhalt">
        <p>Das Konto entsteht ohne Passwort; die Person bekommt einen Link, mit dem
           sie es selbst setzt. Der Link ist 24 Stunden gültig.</p>
        <?php ui_feld(['name' => 'email', 'label' => 'E-Mail (Anmeldung)', 'art' => 'email',
                       'pflicht' => true, 'attr' => 'placeholder="neue@adresse.de"']); ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'name', 'label' => 'Name',
                         'attr' => 'maxlength="120" placeholder="z. B. Vorname Nachname"',
                         'klein' => 'Kann später ergänzt werden.']); ?>
          <?php /* Die Option „BetreiberIn" sieht nur, wer selbst eine ist
                   (R75). Geprueft wird sie oben im Schreibweg noch einmal —
                   das Ausblenden ist die Anzeige der Regel, nicht die
                   Regel. */ ?>
          <?php ui_feld(['name' => 'role', 'label' => 'Rolle', 'art' => 'select',
                         'optionen' => rollen_auswahl(),
                         'klein'    => ist_betreiberin()
                             ? 'BetreiberIn kann zusätzlich den Bereich Betrieb.'
                             : null]); ?>
        </div>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Anlegen', 'art' => 'primaer', 'symbol' => 'plus']) ?>
      </div>
    </form>
  </dialog>

<?php ui_geruest_ende(); ?>
<script>
  document.body.dataset.konto = <?= json_encode((string)$userId) ?>;
<?php if ($auswahlVerbraucht): ?>
  document.body.dataset.auswahlRest = <?= json_encode($auswahlRest) ?>;
<?php endif; ?>
</script>
<script>
/* AUSWAHL UEBER SEITEN HINWEG (E-P3-41).
 *
 * Die Sammelleiste soll „n ausgewählt" auch dann noch sagen, wenn man auf
 * Seite 3 weitergeblättert hat. Die Kaestchen der uebrigen Seiten stehen aber
 * gar nicht im Markup — es sind hoechstens fuenfzig.
 *
 * Die Liste steht deshalb im sessionStorage und nicht in der Adresse: Eine
 * Adresse mit dreihundert Kennungen waere unbrauchbar lang und stuende im
 * Verlauf, im Protokoll des Servers und im Verweis auf die naechste Seite.
 * sessionStorage gilt fuer diesen Tab und endet mit ihm — genau die
 * Lebensdauer, die eine Auswahl hat.
 *
 * Beim Absenden wandert die Liste in ein verstecktes Feld; der Server
 * bekommt sie als eine kommagetrennte Zeichenkette.
 */
(function () {
  'use strict';
  /* DER SCHLUESSEL TRAEGT DIE KENNUNG DER ANGEMELDETEN PERSON. sessionStorage
     gilt fuer den TAB, nicht fuer die Anmeldung: Meldet sich in demselben Tab
     eine andere Administratorin an, saehe sie sonst die Auswahl ihrer
     Vorgaengerin — angehakte Kaestchen, die sie nie gesetzt hat, an einer
     Leiste, die zum Sichern einlaedt. Verschiedene Installationen trennt der
     Browser ohnehin: sessionStorage haengt am Ursprung. */
  var SCHLUESSEL = 'ed-konten-auswahl-' + document.body.dataset.konto;
  var leiste = document.getElementById('sammelleiste');
  var zahl   = document.getElementById('auswahlzahl');
  var feld   = document.getElementById('auswahlfeld');
  var kaesten = document.querySelectorAll('[data-kontowahl]');

  function lesen() {
    try {
      var roh = sessionStorage.getItem(SCHLUESSEL);
      return roh ? roh.split(',').filter(Boolean) : [];
    } catch (e) { return []; }
  }
  function schreiben(liste) {
    try { sessionStorage.setItem(SCHLUESSEL, liste.join(',')); } catch (e) { /* egal */ }
  }

  /* Nach einer ausgefuehrten Sammelaktion bleibt genau das ausgewaehlt, was
     die Anfrage nicht mehr geschafft hat — meistens nichts. */
  if (document.body.dataset.auswahlRest !== undefined) {
    try { sessionStorage.setItem(SCHLUESSEL, document.body.dataset.auswahlRest); }
    catch (e) { /* egal */ }
  }

  var auswahl = lesen();

  function zeichne() {
    var n = auswahl.length;
    if (zahl) { zahl.textContent = n === 1 ? '1 ausgewählt' : n + ' ausgewählt'; }
    if (feld) { feld.value = auswahl.join(','); }
    if (leiste) { leiste.hidden = n === 0; }
  }

  /* JEDES KONTO STEHT ZWEIMAL IM MARKUP: einmal als Tabellenzeile (ab 720 px)
     und einmal als Kachelzeile (darunter). Sichtbar ist immer nur eine — aber
     im DOM sind es zwei Kaestchen mit derselben Kennung. Wer nur das
     angeklickte nachfuehrt, hat nach einem Wechsel der Fensterbreite ein
     Kaestchen, das leer aussieht, obwohl das Konto ausgewaehlt ist. Also
     immer beide setzen. */
  function setzeAlle(wert, an) {
    kaesten.forEach(function (b) { if (b.value === wert) { b.checked = an; } });
  }

  kaesten.forEach(function (box) {
    if (auswahl.indexOf(box.value) >= 0) { box.checked = true; }
    box.addEventListener('change', function () {
      var i = auswahl.indexOf(box.value);
      if (box.checked && i < 0) { auswahl.push(box.value); }
      if (!box.checked && i >= 0) { auswahl.splice(i, 1); }
      setzeAlle(box.value, box.checked);
      schreiben(auswahl);
      zeichne();
    });
  });
  zeichne();

  /* Eine Zeile ist anklickbar — aber nicht dort, wo schon etwas anderes auf
     den Klick wartet (Kaestchen, Verweis). */
  document.querySelectorAll('tr.clickable[data-ziel]').forEach(function (tr) {
    tr.addEventListener('click', function (ev) {
      if (ev.target.closest('input,a,label,button')) { return; }
      location.href = tr.getAttribute('data-ziel');
    });
  });
})();
</script>
<?php ui_seite_ende(['skripte' => ['assets/dialog.js']]); ?>
