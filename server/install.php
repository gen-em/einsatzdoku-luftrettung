<?php
declare(strict_types=1);
/**
 * Einrichtungs-Assistent.
 * - Läuft nur, solange weder config.php noch install.lock existieren.
 * - Testet die DB-Verbindung, spielt schema.sql ein, legt die BetreiberIn an,
 *   schreibt config.php und setzt danach install.lock (Wiederausführungssperre).
 * Diese Datei benötigt selbst KEINE config.php.
 */

// E-Mail-Normalisierung (M1-13). Diese Datei laeuft VOR der Ersteinrichtung
// und kann weder db.php noch validate_lib.php laden — beide brauchen die
// config.php, die es hier noch nicht gibt. Deshalb die abhaengigkeitsfreie
// email_lib.php, dieselbe Fassung wie im Rest der Anwendung.
require_once __DIR__ . '/email_lib.php';

/* DIE SEITENHUELLE MUSS HIER STEHEN, NICHT IN render_page() (Web 9.10.1).
 *
 * Sie stand seit P3/O2 in render_page() selbst — an der Stelle, an der sie
 * gebraucht wird. Das war falsch, und zwar toedlich: Die Aufrufer BAUEN ihr
 * Argument mit ui_meldung_markup() (Zeile 62), ui_knopf() (Zeile 66) und
 * ui_symbol(), und PHP wertet Argumente VOR dem Aufruf aus. Die Huelle wurde
 * also erst geladen, nachdem die Funktionen daraus schon gebraucht worden
 * waren. Jeder der drei Zweige endete in „Call to undefined function".
 *
 * DAS TRAF JEDE NEUINSTALLATION: index.php leitet ohne config.php hierher,
 * und der Deploy liefert diese Datei aus. Aufgefallen ist es niemandem, weil
 * der Einrichter genau einmal im Leben einer Installation laeuft — und die
 * bestehende laeuft laengst. Gefunden bei der Bestandsaufnahme zu O10.
 *
 * ui.php hat auf oberster Ebene keine Abhaengigkeit und laeuft deshalb auch
 * hier, VOR der Ersteinrichtung: Es gibt zu diesem Zeitpunkt weder config.php
 * noch db.php, also weder asset() noch favicon_tags(). Die Huelle faengt das
 * ab (ui_asset(), ui_favicon()). */
require_once __DIR__ . '/ui.php';

$configPath = __DIR__ . '/config.php';
$lockPath   = __DIR__ . '/install.lock';
$schemaPath = __DIR__ . '/schema.sql';

/* Sitzung des Einrichters wie die der Anwendung absichern (M1-19).
 *
 * Hier stand ein blankes session_start(). Die Anwendung setzt an ihrer
 * Sitzung seit jeher httponly/secure/SameSite — der Einrichter nicht, obwohl
 * er das Datenbank-Passwort im Formular fuehrt und die Sitzung das
 * Formular-Token traegt.
 *
 * use_strict_mode zusaetzlich: Ohne das uebernimmt PHP eine Sitzungskennung,
 * die der Browser mitbringt, auch wenn es sie nie vergeben hat. Wer eine
 * Kennung setzen kann, kennt damit die Sitzung, in der gleich eingerichtet
 * wird. Der Einrichter laeuft genau einmal und ungeschuetzt — das ist der
 * Zeitpunkt, an dem so etwas zaehlt.
 *
 * SameSite=Lax statt Strict: Der Einrichter wird typischerweise ueber einen
 * Link aus einer Anleitung oder dem Kundenmenue des Hosters geoeffnet.
 */
session_set_cookie_params([
    'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']),
    'samesite' => 'Lax', 'path' => '/',
]);
ini_set('session.use_strict_mode', '1');
session_start();
if (empty($_SESSION['inst_csrf'])) { $_SESSION['inst_csrf'] = bin2hex(random_bytes(32)); }

function h(string $s): string { return htmlspecialchars($s, ENT_QUOTES, 'UTF-8'); }

/* ---- Wiederausführungssperre ------------------------------------------- */
if (file_exists($configPath) || file_exists($lockPath)) {
    http_response_code(403);
    render_page('Bereits eingerichtet',
        ui_meldung_markup('info', 'Die Anwendung ist bereits eingerichtet. '
            . 'Der Installer ist gesperrt.')
        . '<p>' . ui_knopf(['text' => 'Zur Anwendung', 'href' => 'index.php',
                            'art' => 'neutral']) . '</p>'
        . '<p class="feld-hinweis">Aus Sicherheitsgründen sollte <code>install.php</code> '
        . 'nach erfolgreicher Einrichtung vom Server gelöscht werden.</p>');
    exit;
}

$errors = [];
$done = false;
$setupLink = '';

/* ---- Nachweis von Dateisystemzugriff (M1-11, D9) --------------------------
 *
 * WAS DIESE SEITE OHNE IHN IST
 * Ein unangemeldeter Endpunkt, der eine Datenbank einrichtet, das erste
 * Konto anlegt und einen Einrichtungslink dafuer ausgibt. Wer eine frisch
 * hochgeladene Installation vor ihrer Betreiberin findet, richtet sie ein —
 * und ist die BetreiberIn. Das Zeitfenster ist kurz, aber es ist genau das
 * Fenster, in dem niemand hinsieht.
 *
 * WAS IHN SCHLIESST
 * Die Seite legt eine Datei mit einer Zufallskennung an und verlangt diese
 * Kennung im Formular. Wer sie nennen kann, hat Zugriff auf das Verzeichnis —
 * und wer den hat, koennte die Anwendung ohnehin beliebig veraendern. Der
 * Nachweis kostet den Betreiber einen Blick in den Dateimanager seines
 * Hosters und den Angreifer den Angriff.
 *
 * DIE KENNUNG STEHT IM DATEINAMEN, nicht nur im Inhalt. Das ist der
 * eigentliche Kniff: Liegt das Verzeichnis im Web-Wurzelverzeichnis — was bei
 * Einfachhosting die Regel ist —, waere eine Datei mit festem Namen ueber die
 * Adresszeile abrufbar, und der Nachweis waere keiner. Einen Namen aus 128 Bit
 * Zufall kann dagegen nur nennen, wer das Verzeichnis SIEHT; ein
 * Verzeichnislisting gibt es bei keinem verbreiteten Webserver von selbst.
 * Die .htaccess sperrt die Datei zusaetzlich — als zweite Schranke, nicht als
 * erste.
 *
 * DIE KENNUNG HAENGT AN DER DATEI, NICHT AN DER SITZUNG. Ein erster Entwurf
 * legte sie in der Sitzung ab und erzeugte je Sitzung eine eigene Datei. Die
 * Pruefung im Container hat gezeigt, wohin das fuehrt: Jeder Aufruf der Seite
 * — auch der eines Neugierigen, auch ein Vorschau-Abruf des Browsers — liess
 * eine weitere Datei liegen. Wer danach ins Verzeichnis sieht, findet mehrere
 * und weiss nicht, welche seine ist.
 *
 * Die Sitzungsbindung braucht es auch gar nicht: Die Kennung ist deshalb
 * geheim, weil man das Verzeichnis SEHEN muss, um sie zu lesen — nicht, weil
 * sie an eine Sitzung gebunden waere. Eine vorhandene Datei wird deshalb
 * uebernommen statt ersetzt. Das haelt zugleich einen Aerger fern, den die
 * Sitzungsfassung geoeffnet haette: Wer die Datei bei jedem Aufruf neu
 * schreiben liesse, koennte einem Betreiber mitten in der Einrichtung die
 * Kennung unter den Haenden wegziehen.
 */
$nachweisMuster = 'install-nachweis-';
$nachweisOk     = true;
$nachweis       = '';

$vorhanden = glob(__DIR__ . '/' . $nachweisMuster . '*.txt') ?: [];
sort($vorhanden);
foreach ($vorhanden as $i => $datei) {
    if (!preg_match('/' . preg_quote($nachweisMuster, '/') . '([0-9a-f]{32})\.txt$/',
                    $datei, $tr)) { continue; }
    if ($nachweis === '') { $nachweis = $tr[1]; }
    elseif ($tr[1] !== $nachweis) { @unlink($datei); }   // Rest aus alten Staenden
}
if ($nachweis === '') { $nachweis = bin2hex(random_bytes(16)); }   // 128 Bit

$nachweisDatei = __DIR__ . '/' . $nachweisMuster . $nachweis . '.txt';

if (!is_writable(__DIR__)) {
    // Ohne Schreibrecht kann die Einrichtung ohnehin keine config.php
    // anlegen. Das gehoert an den Anfang und nicht ans Ende.
    $nachweisOk = false;
} elseif (!file_exists($nachweisDatei)) {
    $inhalt = $nachweis . "\n\n"
            . "Diese Datei gehoert zur Ersteinrichtung von Gen-EM NAdoku.\n"
            . "Die Zeichenfolge oben ist im Einrichtungsformular einzutragen.\n"
            . "Sie beweist, dass die einrichtende Person Zugriff auf dieses\n"
            . "Verzeichnis hat. Nach der Einrichtung wird die Datei geloescht;\n"
            . "sie kann auch jederzeit von Hand geloescht werden.\n";
    if (@file_put_contents($nachweisDatei, $inhalt, LOCK_EX) === false) {
        $nachweisOk = false;
    } else {
        @chmod($nachweisDatei, 0640);
    }
}

/* ---- Formular verarbeiten ---------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['inst_csrf'] ?? '', $_POST['csrf'] ?? '')) {
        $errors[] = 'Ungültiges Formular-Token. Bitte Seite neu laden.';
    }

    $in = fn(string $k): string => trim((string)($_POST[$k] ?? ''));

    $dbHost = $in('db_host'); $dbName = $in('db_name');
    $dbUser = $in('db_user'); $dbPass = (string)($_POST['db_pass'] ?? '');
    $adminEmail = email_normalisieren($_POST['admin_email'] ?? '');
    $baseUrl = rtrim($in('base_url'), '/');
    $timezone = $in('timezone') ?: 'Europe/Berlin';
    $logoPath = $in('logo_path') ?: 'assets/images/gen-em_logo_helicopter.svg';

    $smtp = [
        'host' => $in('smtp_host'), 'port' => (int)($in('smtp_port') ?: 465),
        'user' => $in('smtp_user'), 'pass' => (string)($_POST['smtp_pass'] ?? ''),
        'from' => $in('smtp_from'), 'from_name' => $in('smtp_from_name') ?: 'Gen-EM NAdoku',
    ];

    /* Nachweis zuerst pruefen (M1-11).
     *
     * Vor allem anderen: Wer ihn nicht hat, soll auch keine Rueckmeldung
     * darueber bekommen, ob eine Datenbankverbindung zustande kaeme. Der
     * Vergleich laeuft ueber hash_equals — die Kennung ist ein Geheimnis.
     *
     * Grosszuegig beim Format: Wer statt der Zeichenfolge den ganzen
     * Dateinamen hineinkopiert, hat verstanden, was gemeint war. */
    $eingabe = strtolower(trim($in('nachweis')));
    $eingabe = preg_replace('/^' . preg_quote($nachweisMuster, '/') . '/', '', $eingabe);
    $eingabe = preg_replace('/\.txt$/', '', (string)$eingabe);
    if (!hash_equals($nachweis, (string)$eingabe)) {
        $errors[] = 'Der Nachweis stimmt nicht. Bitte die Zeichenfolge aus dem '
                  . 'Dateinamen im Anwendungsverzeichnis eintragen (siehe unten).';
    }

    // Validierung
    if ($dbHost === '' || $dbName === '' || $dbUser === '') {
        $errors[] = 'Datenbank-Host, -Name und -Benutzer sind erforderlich.';
    }
    if (!filter_var($adminEmail, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Bitte eine gültige E-Mail-Adresse für den Zugang angeben.';
    }
    if (!preg_match('#^https?://#', $baseUrl)) {
        $errors[] = 'Die Basis-URL muss mit http:// oder https:// beginnen.';
    }
    if (!in_array($timezone, DateTimeZone::listIdentifiers(), true)) {
        $errors[] = 'Unbekannte Zeitzone.';
    }
    if (!is_writable(__DIR__)) {
        $errors[] = 'Das Verzeichnis ist nicht beschreibbar — config.php kann nicht '
                  . 'angelegt werden. Schreibrechte setzen und erneut versuchen.';
    }
    if (!is_readable($schemaPath)) {
        $errors[] = 'schema.sql wurde nicht gefunden (muss neben install.php liegen).';
    }
    /* DIE ERWEITERUNGEN, OHNE DIE ES SPAETER KLEMMT (S2/AP6).
     *
     * Bis Web 12.0.0 hat der Installer gar keine Erweiterung geprueft. Das
     * ging gut, solange alles Benoetigte im PHP-Kern steckt — seit das
     * Admin-Backup ein ZIP ist, gilt das nicht mehr. Ohne `ext/zip` faellt
     * es sonst erst beim ersten Backup-Lauf auf, und dann als „liess sich
     * nicht schreiben" auf einer Installation, die laengst in Betrieb ist.
     *
     * Hier steht die Pruefung deshalb VOR der Einrichtung. Sie ist nicht
     * vollstaendig — sie nennt, was diese Anwendung nachweislich braucht und
     * was ein Hoster tatsaechlich abschalten kann. */
    foreach (['zip' => 'Backups sind ZIP-Dateien (ext/zip, Klasse ZipArchive)',
              'zlib' => 'Spuren werden komprimiert gespeichert (ext/zlib)',
              'openssl' => 'Zufall und Pruefsummen (ext/openssl)',
              'mbstring' => 'Texte in UTF-8 (ext/mbstring)'] as $erw => $wofuer) {
        if (!extension_loaded($erw)) {
            $errors[] = 'Die PHP-Erweiterung „' . $erw . '" fehlt: ' . $wofuer
                      . '. Bitte beim Hoster freischalten lassen.';
        }
    }

    // DB-Verbindung testen
    $pdo = null;
    if (!$errors) {
        $dsn = "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4";
        try {
            $pdo = new PDO($dsn, $dbUser, $dbPass, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        } catch (Throwable $ex) {
            $errors[] = 'Datenbank-Verbindung fehlgeschlagen: ' . $ex->getMessage();
        }
    }

    // Schema einspielen + Admin anlegen
    if (!$errors && $pdo !== null) {
        try {
            /* HIER STAND EIN HAEKCHEN "Vorhandene Tabellen vorher loeschen"
             * (M1-11, D9). Es ist ersatzlos entfallen.
             *
             * Es war die einzige Stelle im ganzen Projekt, an der ein
             * unangemeldeter Aufruf jede Tabelle der Datenbank haette leeren
             * koennen — abgesichert durch nichts als die Annahme, dass diese
             * Seite nur einmal und nur vom Betreiber aufgerufen wird.
             *
             * Ersetzt wurde es NICHT durch eine Sicherheitsabfrage, sondern
             * gestrichen: Im Betrieb wird es nicht gebraucht. Wer eine
             * Datenbank mit Altbestand neu einrichten will, legt eine leere
             * Datenbank an oder leert die vorhandene mit dem Werkzeug seines
             * Hosters — beides bewusste Handlungen an der richtigen Stelle. */
            run_sql_file($pdo, $schemaPath);

            /* Bewusst OHNE Passwort: Der Server darf das Passwort nie sehen.
             * Es wird ueber pw_handling.php im Browser gesetzt; dort entstehen
             * zugleich Inhalts- und Wiederherstellungsschluessel.
             *
             * DAS ERSTE KONTO IST DIE BETREIBERIN (R75, Web 15.0.0). Wer eine
             * Installation einrichtet, ist per Definition die, die sie
             * betreibt — und sie ist in diesem Augenblick die Einzige. Legte
             * die Einrichtung ein Admin-Konto an, stuende die frische
             * Installation ohne Zugang zu ihrem eigenen Betriebsbereich da:
             * Serverschluessel, Wartungsmodus, Migrationen, Speichergrenze.
             * Der Weg zurueck fuehrte ueber die Datenbank. */
            $pdo->prepare('INSERT INTO users (email, role, account_key)
                           VALUES (?, "betreiberin", ?)')
                ->execute([$adminEmail, bin2hex(random_bytes(8))]);
            $adminId = (int)$pdo->lastInsertId();
            $setupToken = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                ->execute([$adminId, hash('sha256', $setupToken)]);
            $setupLink = $baseUrl . '/pw_handling.php?token=' . $setupToken;
        } catch (Throwable $ex) {
            $errors[] = 'Beim Anlegen der Tabellen/des ersten Kontos ist ein Fehler aufgetreten: '
                      . $ex->getMessage()
                      . ' — Tipp: eine leere Datenbank verwenden. Bestehende '
                      . 'Tabellen werden von der Einrichtung bewusst nicht mehr '
                      . 'gelöscht.';
        }
    }

    // config.php schreiben + Sperre setzen
    if (!$errors) {
        /* DER SERVERSCHLUESSEL ENTSTEHT HIER UND NIRGENDWO SONST (E-S2-21,
         * S2/AP7). Er versiegelt die Zugangsdaten der Backup-Ziele und —
         * ab AP8 — das Komplettbackup. Eine neue Installation bekommt ihn
         * mit, ohne dass jemand daran denken muss; bestehende Installationen
         * tragen ihn ueber die Seite „Backup-Ziele" nach.
         *
         * ER GEHOERT INS WIEDERANLAUFPAKET. Steht in docs/Technik.md,
         * Abschnitt 7, und im Kopf dieser config.php gleich mit — denn wer
         * die Datei zum ersten Mal oeffnet, liest sie und nicht das Runbook.
         * Geht er verloren, sind die Zugangsdaten der Ziele neu einzutragen
         * und ein versiegeltes Komplettbackup ist nicht mehr zu oeffnen. */
        $config = [
            'db'  => ['dsn' => "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                      'user' => $dbUser, 'pass' => $dbPass],
            'app' => ['base_url' => $baseUrl, 'timezone' => $timezone,
                      'logo_path' => $logoPath, 'max_body_bytes' => 524288],
            'smtp' => $smtp,
            'server_key' => bin2hex(random_bytes(32)),
        ];
        $php = "<?php\n// Automatisch erzeugt vom Installer am " . date('c') . "\n"
             . "// Diese Datei enthält Zugangsdaten — niemals ins Git-Repo committen!\n"
             . "// server_key versiegelt die Zugangsdaten der Backup-Ziele und das\n"
             . "// Komplettbackup. Geht er verloren, sind versiegelte Komplettbackups\n"
             . "// nicht mehr zu öffnen. Er gehört zusammen mit dieser Datei ins\n"
             . "// getrennt aufbewahrte Wiederanlaufpaket (docs/Technik.md, Runbook).\n"
             . 'return ' . var_export($config, true) . ";\n";

        if (file_put_contents($configPath, $php, LOCK_EX) === false) {
            $errors[] = 'config.php konnte nicht geschrieben werden.';
        } else {
            @chmod($configPath, 0640);
            file_put_contents($lockPath, 'installed ' . date('c') . "\n");
            // Nachweisdatei hat ihren Zweck erfuellt (M1-11). Sie bleibt nicht
            // liegen — eine Datei mit einem Geheimnis im Namen soll nicht
            // laenger existieren als noetig.
            @unlink($nachweisDatei);
            $done = true;
        }
    }
}

/* ---- SQL-Datei ausführen (statementweise) ------------------------------ */
function run_sql_file(PDO $pdo, string $path): void {
    $sql = file_get_contents($path);
    // Kommentarzeilen entfernen, dann an ';' am Zeilenende trennen.
    $sql = preg_replace('/^\s*--.*$/m', '', $sql);
    foreach (preg_split('/;\s*[\r\n]+/', $sql) as $stmt) {
        $stmt = trim($stmt);
        if ($stmt !== '') { $pdo->exec($stmt); }
    }
}

/* ---- Ausgabe ------------------------------------------------------------ */
if ($done) {
    render_page('Einrichtung abgeschlossen',
        ui_meldung_markup('ok', 'Einrichtung erfolgreich. Die Konfiguration wurde '
            . 'gespeichert und der Installer ist jetzt gesperrt.', 'Fertig.')
        . '<p><strong>Letzter Schritt:</strong> Über den folgenden Link legst du das '
        . 'Passwort des BetreiberIn-Zugangs fest. Dabei wird einmalig dein '
        . 'Wiederherstellungsschlüssel angezeigt — bitte sicher notieren. '
        . 'Der Link ist 24 Stunden gültig.</p>'
        . '<p>' . ui_knopf(['text' => 'Passwort jetzt festlegen', 'href' => $setupLink,
                            'art' => 'primaer', 'breit' => true]) . '</p>'
        . '<p class="feld-hinweis">Falls der Link verlorengeht: Auf der Anmeldeseite '
        . 'lässt sich über „Passwort vergessen oder erstmalig setzen“ ein neuer '
        . 'anfordern (setzt funktionierende SMTP-Angaben voraus).</p>'
        . '<p class="feld-hinweis">Empfehlung: <code>install.php</code> jetzt vom Server '
        . 'löschen. Solange <code>install.lock</code> existiert, ist eine erneute '
        . 'Ausführung ohnehin blockiert.</p>');
    exit;
}

render_form($_POST ?? [], $errors, $nachweis, $nachweisMuster, $nachweisOk);


/* ---- Templates ---------------------------------------------------------- */
function render_form(array $v, array $errors, string $nachweis,
                     string $nachweisMuster, bool $nachweisOk): void {
    $guessUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'einsatz.example.de');
    ob_start(); ?>
    <h1>Gen-EM NAdoku einrichten</h1>
    <p class="seiten-erklaerung">Diese Angaben werden in <code>config.php</code>
       gespeichert und die Datenbank wird angelegt. Der Einrichter läuft nur
       dieses eine Mal.</p>

    <?php /* Maskierung HIER, an der Ausgabestelle (M1-18).
       Vorher maskierten zwei der zehn Meldungen ihren variablen Teil selbst,
       und die Ausgabe gab alles unmaskiert weiter. Das ging gut, solange
       niemand eine elfte Meldung mit Fremdtext ergänzt — genau die wäre dann
       eine Lücke gewesen, und zwar eine unsichtbare: Neun Meldungen ohne h()
       sahen ja richtig aus. Maskieren am Ausgabepunkt kann man nicht
       vergessen; es gibt nur diese eine Stelle. */ ?>
    <?php foreach ($errors as $e): ?><?= ui_meldung_markup('fehler', $e) ?><?php endforeach; ?>

    <form method="post" autocomplete="off">
      <input type="hidden" name="csrf" value="<?= h($_SESSION['inst_csrf']) ?>">

      <?php /* FUENF KARTEN STATT FUENF <fieldset> (O10). Die Elementregeln
               fuer fieldset/legend standen in der Uebergangsschicht des
               Stylesheets; sie sind mit O11 gefallen, und der Einrichter
               staende sonst jetzt ohne Gestaltung da. Eine Karte mit Titel
               ist ohnehin das, was E-P3-35 fuer eine Feldgruppe vorsieht. */ ?>

      <?php ui_karte_start(['titel' => 'Nachweis']); ?>
        <?php if (!$nachweisOk): ?>
          <?= ui_meldung_markup('fehler',
              'Im Anwendungsverzeichnis lässt sich keine Datei anlegen. Damit kann '
            . 'die Einrichtung weder den Nachweis erzeugen noch später config.php '
            . 'schreiben. Bitte Schreibrechte auf das Verzeichnis '
            . basename(__DIR__) . ' setzen und die Seite neu laden.',
              'Kein Schreibrecht.') ?>
        <?php else: ?>
          <p class="feld-hinweis">Im Anwendungsverzeichnis liegt jetzt eine Datei, deren
             Name mit <code><?= h($nachweisMuster) ?></code> beginnt. Trage die
             Zeichenfolge aus dem Dateinamen hier ein (oder den ganzen Dateinamen,
             oder den Inhalt der Datei — alle drei enthalten dieselbe Angabe).</p>
          <p class="feld-hinweis">Das belegt, dass du Zugriff auf dieses Verzeichnis
             hast. Ohne diesen Nachweis könnte jemand, der die frisch hochgeladene
             Installation vor dir findet, sich selbst als BetreiberIn eintragen.</p>
          <?php ui_feld(['name' => 'nachweis', 'label' => 'Zeichenfolge aus dem Dateinamen',
                         'wert' => (string)($v['nachweis'] ?? ''), 'pflicht' => true,
                         'attr' => ' autocomplete="off" spellcheck="false"']); ?>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'Datenbank']); ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'db_host', 'label' => 'Host', 'pflicht' => true,
                         'wert' => (string)($v['db_host'] ?? 'localhost')]); ?>
          <?php ui_feld(['name' => 'db_name', 'label' => 'Datenbank-Name', 'pflicht' => true,
                         'wert' => (string)($v['db_name'] ?? '')]); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'db_user', 'label' => 'Benutzer', 'pflicht' => true,
                         'wert' => (string)($v['db_user'] ?? '')]); ?>
          <?php ui_feld(['name' => 'db_pass', 'label' => 'Passwort', 'art' => 'password']); ?>
        </div>
        <p class="feld-hinweis">Bitte eine <strong>leere</strong> Datenbank verwenden.
           Vorhandene Tabellen werden nicht gelöscht.</p>
      <?php ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'Zugang der BetreiberIn']); ?>
        <?php ui_feld(['name' => 'admin_email', 'label' => 'E-Mail (= Anmeldung)',
                       'art' => 'email', 'pflicht' => true,
                       'wert' => (string)($v['admin_email'] ?? '')]); ?>
        <p class="feld-hinweis">Dieses erste Konto bekommt die Rolle
           <strong>BetreiberIn</strong>: Es kann alles, was ein Admin kann, und
           zusätzlich den Bereich <em>Betrieb</em> — Serverbetrieb, Updates,
           Hintergrundjobs, Speicher, Komplett-Backup und Backup-Ziele. Weitere
           Konten legst du später in der Verwaltung an.</p>
        <p class="feld-hinweis">Das Passwort wird nicht hier gesetzt: Es verlässt den
           Browser nie. Nach der Einrichtung erscheint ein Link, über den du es
           festlegst — zusammen mit dem Wiederherstellungsschlüssel.</p>
      <?php ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'Anwendung']); ?>
        <?php ui_feld(['name' => 'base_url', 'label' => 'Basis-URL', 'pflicht' => true,
                       'wert' => (string)($v['base_url'] ?? $guessUrl),
                       'klein' => 'Ohne Schrägstrich am Ende.']); ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'timezone', 'label' => 'Zeitzone (Anzeige)',
                         'wert' => (string)($v['timezone'] ?? 'Europe/Berlin')]); ?>
          <?php ui_feld(['name' => 'logo_path', 'label' => 'Logo-Pfad',
                         'wert' => (string)($v['logo_path'] ?? 'assets/images/gen-em_logo_helicopter.svg'),
                         'klein' => 'Nur für ein EIGENES Logo. Zeigt der Pfad auf eines '
                                  . 'der beiden mitgelieferten, entscheidet die Logo-Wahl '
                                  . '(Betrieb und Profil).']); ?>
        </div>
      <?php ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'SMTP', 'zahl' => 'optional']); ?>
        <p class="feld-hinweis">Kann leer bleiben — der Zugang funktioniert auch
           ohne. Ohne SMTP können NutzerInnen ihr Passwort aber nicht per Mail
           zurücksetzen.</p>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'smtp_host', 'label' => 'Host',
                         'wert' => (string)($v['smtp_host'] ?? '')]); ?>
          <?php ui_feld(['name' => 'smtp_port', 'label' => 'Port',
                         'wert' => (string)($v['smtp_port'] ?? '465')]); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'smtp_user', 'label' => 'Benutzer',
                         'wert' => (string)($v['smtp_user'] ?? '')]); ?>
          <?php ui_feld(['name' => 'smtp_pass', 'label' => 'Passwort', 'art' => 'password']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'smtp_from', 'label' => 'Absender-Adresse',
                         'wert' => (string)($v['smtp_from'] ?? '')]); ?>
          <?php ui_feld(['name' => 'smtp_from_name', 'label' => 'Absender-Name',
                         'wert' => (string)($v['smtp_from_name'] ?? 'Gen-EM NAdoku')]); ?>
        </div>
      <?php ui_karte_ende(); ?>

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Einrichten', 'art' => 'primaer', 'breit' => true,
                      'symbol' => 'haken']) ?>
      </div>
    </form>
    <?php
    render_page('Einrichten', ob_get_clean());
}

function render_page(string $title, string $body): void {
    /* Die Seitenhuelle kommt aus ui.php — wie ueberall sonst (P0/A2).
       Geladen wird sie am DATEIANFANG, nicht hier; die Begruendung steht
       dort (Web 9.10.1).

       SEIT P3/O2 GILT DAS GEMEINSAME STYLESHEET (Backlog Nr. 18, E-P3-02).
       Bis Web 8.0.1 brachte diese Seite ihre Gestaltung im Kopf mit — 17
       Hexwerte, eigene Knopf- und Meldungsklassen, eine zweite Schriftgroesse
       —, und die Begruendung dafuer lautete: Der Einrichter soll auch dann
       bedienbar aussehen, wenn am Stylesheet etwas fehlt.

       Der Preis war hoeher als der Nutzen. Der Einrichter war die einzige
       Seite, die bei einer Farbaenderung nicht mitzog, die einzige mit einer
       eigenen `.btn-link`-Regel (die im Stylesheet daneben deshalb nie
       greifen konnte — Backlog Nr. 18) und die einzige ohne Fusszeile. Und
       das Stylesheet liegt neben ihm im selben Verzeichnis: Faellt es aus,
       ist die Anwendung ohnehin nicht eingerichtet.

       Der relative Pfad funktioniert hier, weil der Einrichter im selben
       Verzeichnis liegt wie die Anwendung; ohne asset() fehlt nur der
       Erkennungswert an der Adresse, und der Einrichter laeuft genau einmal. */
    /* DIE OEFFENTLICHE HUELLE, nicht die der Anmeldung (O10).
     *
     * Bis Web 9.10.1 stand hier `anmeldung-body` mit `.anmeldung-karte`: die
     * dunkelblaue Flaeche und die schmale Karte des Anmeldeformulars. Die ist
     * fuer zwei Zeilen gemacht — der Einrichter traegt fuenf Feldgruppen und
     * half sich schon mit `.anmeldung-breit`, was der Sache nach die
     * Lesespalte ist, nur unter falschem Namen. Dazu hatte die Kopfleiste
     * dieselbe Farbe wie die Flaeche darunter (beide --dunkelblau), das Logo
     * schwebte also ohne sichtbare Leiste.
     *
     * Das Konzept widersprach sich an dieser Stelle: E-P3-38 nennt „dunkle
     * Huelle", Tabelle 5.4 fuehrt den Einrichter unter „Oeffentlich". Es gilt
     * die Tabelle — dieselbe Huelle wie Abbruchseite, Impressum und
     * Datenschutz. */
    ui_seite_start(['titel' => $title]);
    ui_kopf(['menue' => false]);
    echo '<div class="rahmen rahmen-lesespalte">' . "\n";
    echo '  <main class="inhalt">' . "\n";
    echo $body, "\n";
    echo "  </main>\n</div>\n";
    /* OHNE die Verweise auf Impressum und Datenschutz: Diese Seite laeuft
       VOR der Ersteinrichtung, die beiden Rechtstextseiten brauchen aber eine
       Datenbank und leiten ohne config.php hierher zurueck. Der Verweis waere
       eine Schleife. */
    ui_fuss_seite(['rechtslinks' => false]);
    ui_seite_ende();
}
