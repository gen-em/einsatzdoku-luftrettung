<?php
declare(strict_types=1);
/**
 * Einrichtungs-Assistent.
 * - Läuft nur, solange weder config.php noch install.lock existieren.
 * - Testet die DB-Verbindung, spielt schema.sql ein, legt den Admin an,
 *   schreibt config.php und setzt danach install.lock (Wiederausführungssperre).
 * Diese Datei benötigt selbst KEINE config.php.
 */

// E-Mail-Normalisierung (M1-13). Diese Datei laeuft VOR der Ersteinrichtung
// und kann weder db.php noch validate_lib.php laden — beide brauchen die
// config.php, die es hier noch nicht gibt. Deshalb die abhaengigkeitsfreie
// email_lib.php, dieselbe Fassung wie im Rest der Anwendung.
require_once __DIR__ . '/email_lib.php';

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
        . '<p class="muted small">Aus Sicherheitsgründen sollte <code>install.php</code> '
        . 'nach erfolgreicher Einrichtung vom Server gelöscht werden.</p>');
    exit;
}

$errors = [];
$done = false;
$setupLink = '';

/* ---- Nachweis von Dateisystemzugriff (M1-11, D9) --------------------------
 *
 * WAS DIESE SEITE OHNE IHN IST
 * Ein unangemeldeter Endpunkt, der eine Datenbank einrichtet, einen
 * Administrator anlegt und einen Einrichtungslink dafuer ausgibt. Wer eine
 * frisch hochgeladene Installation vor ihrem Betreiber findet, richtet sie
 * ein — und ist Administrator. Das Zeitfenster ist kurz, aber es ist genau
 * das Fenster, in dem niemand hinsieht.
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
            . "Diese Datei gehoert zur Ersteinrichtung der Einsatzdoku.\n"
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
        'from' => $in('smtp_from'), 'from_name' => $in('smtp_from_name') ?: 'Einsatzdoku',
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
        $errors[] = 'Bitte eine gültige Admin-E-Mail angeben.';
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

            // Bewusst OHNE Passwort: Der Server darf das Passwort nie sehen.
            // Es wird ueber pw_handling.php im Browser gesetzt; dort entstehen
            // zugleich Inhalts- und Wiederherstellungsschluessel.
            $pdo->prepare('INSERT INTO users (email, role, account_key) VALUES (?, "admin", ?)')
                ->execute([$adminEmail, bin2hex(random_bytes(8))]);
            $adminId = (int)$pdo->lastInsertId();
            $setupToken = bin2hex(random_bytes(32));
            $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                ->execute([$adminId, hash('sha256', $setupToken)]);
            $setupLink = $baseUrl . '/pw_handling.php?token=' . $setupToken;
        } catch (Throwable $ex) {
            $errors[] = 'Beim Anlegen der Tabellen/des Admins ist ein Fehler aufgetreten: '
                      . $ex->getMessage()
                      . ' — Tipp: eine leere Datenbank verwenden. Bestehende '
                      . 'Tabellen werden von der Einrichtung bewusst nicht mehr '
                      . 'gelöscht.';
        }
    }

    // config.php schreiben + Sperre setzen
    if (!$errors) {
        $config = [
            'db'  => ['dsn' => "mysql:host={$dbHost};dbname={$dbName};charset=utf8mb4",
                      'user' => $dbUser, 'pass' => $dbPass],
            'app' => ['base_url' => $baseUrl, 'timezone' => $timezone,
                      'logo_path' => $logoPath, 'max_body_bytes' => 524288],
            'smtp' => $smtp,
        ];
        $php = "<?php\n// Automatisch erzeugt vom Installer am " . date('c') . "\n"
             . "// Diese Datei enthält Zugangsdaten — niemals ins Git-Repo committen!\n"
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
        . 'Passwort des Administrator-Zugangs fest. Dabei wird einmalig dein '
        . 'Wiederherstellungsschlüssel angezeigt — bitte sicher notieren. '
        . 'Der Link ist 24 Stunden gültig.</p>'
        . '<p>' . ui_knopf(['text' => 'Passwort jetzt festlegen', 'href' => $setupLink,
                            'art' => 'primaer', 'breit' => true]) . '</p>'
        . '<p class="muted small">Falls der Link verlorengeht: Auf der Anmeldeseite '
        . 'lässt sich über „Passwort vergessen oder erstmalig setzen“ ein neuer '
        . 'anfordern (setzt funktionierende SMTP-Angaben voraus).</p>'
        . '<p class="muted small">Empfehlung: <code>install.php</code> jetzt vom Server '
        . 'löschen. Solange <code>install.lock</code> existiert, ist eine erneute '
        . 'Ausführung ohnehin blockiert.</p>');
    exit;
}

render_form($_POST ?? [], $errors, $nachweis, $nachweisMuster, $nachweisOk);


/* ---- Templates ---------------------------------------------------------- */
function render_form(array $v, array $errors, string $nachweis,
                     string $nachweisMuster, bool $nachweisOk): void {
    $val = fn(string $k, string $d = ''): string => h((string)($v[$k] ?? $d));
    $guessUrl = 'https://' . ($_SERVER['HTTP_HOST'] ?? 'einsatz.example.de');
    ob_start(); ?>
    <h1>Einsatzdoku einrichten</h1>
    <p class="muted">Diese Angaben werden in <code>config.php</code> gespeichert und die
       Datenbank wird angelegt. Der Installer läuft nur dieses eine Mal.</p>

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

      <fieldset>
        <legend>Nachweis</legend>
        <?php if (!$nachweisOk): ?>
          <div class="meldung meldung-fehler" role="alert">
            <?= ui_symbol('warnung', 'symbol-gross') ?>
            <p><strong>Kein Schreibrecht.</strong> Im Anwendungsverzeichnis lässt
               sich keine Datei anlegen. Damit kann die Einrichtung weder den
               Nachweis erzeugen noch später <code>config.php</code> schreiben.
               Bitte Schreibrechte auf das Verzeichnis
               <code><?= h(basename(__DIR__)) ?></code> setzen und die Seite neu
               laden.</p>
          </div>
        <?php else: ?>
          <p class="muted small">Im Anwendungsverzeichnis liegt jetzt eine Datei, deren
             Name mit <code><?= h($nachweisMuster) ?></code> beginnt. Trage die
             Zeichenfolge aus dem Dateinamen hier ein (oder den ganzen Dateinamen,
             oder den Inhalt der Datei — alle drei enthalten dieselbe Angabe).</p>
          <p class="muted small">Das belegt, dass du Zugriff auf dieses Verzeichnis
             hast. Ohne diesen Nachweis könnte jemand, der die frisch hochgeladene
             Installation vor dir findet, sich selbst als Administrator eintragen.</p>
          <label>Zeichenfolge aus dem Dateinamen
            <input name="nachweis" value="<?= $val('nachweis') ?>" required
                   autocomplete="off" spellcheck="false"></label>
        <?php endif; ?>
      </fieldset>

      <fieldset>
        <legend>Datenbank</legend>
        <label>Host <input name="db_host" value="<?= $val('db_host', 'localhost') ?>" required></label>
        <label>Datenbank-Name <input name="db_name" value="<?= $val('db_name') ?>" required></label>
        <label>Benutzer <input name="db_user" value="<?= $val('db_user') ?>" required></label>
        <label>Passwort <input type="password" name="db_pass"></label>
        <p class="muted small">Bitte eine <strong>leere</strong> Datenbank verwenden.
           Vorhandene Tabellen werden nicht gelöscht.</p>
      </fieldset>

      <fieldset>
        <legend>Administrator-Zugang</legend>
        <label>E-Mail (= Login) <input type="email" name="admin_email" value="<?= $val('admin_email') ?>" required></label>
        <p class="muted small">Das Passwort wird nicht hier gesetzt: Es verlässt den
           Browser nie. Nach der Einrichtung erscheint ein Link, über den du es
           festlegst — zusammen mit dem Wiederherstellungsschlüssel.</p>
      </fieldset>

      <fieldset>
        <legend>Anwendung</legend>
        <label>Basis-URL (ohne Slash am Ende)
          <input name="base_url" value="<?= $val('base_url', $guessUrl) ?>" required></label>
        <label>Zeitzone (Anzeige)
          <input name="timezone" value="<?= $val('timezone', 'Europe/Berlin') ?>"></label>
        <label>Logo-Pfad
          <input name="logo_path" value="<?= $val('logo_path', 'assets/images/gen-em_logo_helicopter.svg') ?>"></label>
      </fieldset>

      <fieldset>
        <legend>SMTP (für Passwort-Reset-Mails, optional)</legend>
        <p class="muted small">Kann leer bleiben — der Admin-Zugang funktioniert auch ohne.
           Ohne SMTP können NutzerInnen ihr Passwort aber nicht per Mail zurücksetzen.</p>
        <label>Host <input name="smtp_host" value="<?= $val('smtp_host') ?>"></label>
        <label>Port <input name="smtp_port" value="<?= $val('smtp_port', '465') ?>"></label>
        <label>Benutzer <input name="smtp_user" value="<?= $val('smtp_user') ?>"></label>
        <label>Passwort <input type="password" name="smtp_pass"></label>
        <label>Absender-Adresse <input name="smtp_from" value="<?= $val('smtp_from') ?>"></label>
        <label>Absender-Name <input name="smtp_from_name" value="<?= $val('smtp_from_name', 'Einsatzdoku') ?>"></label>
      </fieldset>

      <?= ui_knopf(['text' => 'Einrichten', 'art' => 'primaer', 'breit' => true]) ?>
    </form>
    <?php
    render_page('Einrichten', ob_get_clean());
}

function render_page(string $title, string $body): void {
    /* Die Seitenhuelle kommt aus ui.php — wie ueberall sonst (P0/A2).
       ui.php hat auf oberster Ebene keine Abhaengigkeit und laeuft deshalb
       auch hier, VOR der Ersteinrichtung: Es gibt zu diesem Zeitpunkt weder
       config.php noch db.php, also weder asset() noch favicon_tags(). Die
       Huelle faengt das ab (ui_asset(), ui_favicon()).

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
    require_once __DIR__ . '/ui.php';
    ui_seite_start(['titel' => $title, 'klasse' => 'anmeldung-body']);
    ui_kopf(['menue' => false]);
    echo '<main class="anmeldung">' . "\n";
    echo '  <div class="anmeldung-karte anmeldung-breit">' . "\n";
    echo $body, "\n";
    echo "  </div>\n</main>\n";
    ui_fuss_seite(['dunkel' => true]);
    ui_seite_ende();
}
