<?php
declare(strict_types=1);

/**
 * Die öffentliche Rechtstextseite — eine Fassung für beide (R32, P3/O10).
 *
 * `impressum.php` und `datenschutz.php` sind zwei Zeilen lang und setzen nur
 * `$rtSchluessel`; alles Weitere steht hier. Der Unterschied zwischen den
 * beiden ist ein Wort in der Überschrift — dafür zwei Dateien mit demselben
 * Inhalt zu führen, wäre die Sorte Verdopplung, die beim dritten Dokument
 * (R33, Nutzungsbedingungen) zur dritten Kopie würde.
 *
 * OHNE ANMELDUNG ERREICHBAR. Diese Seite lädt ausdrücklich NICHT
 * `auth_guard.php`: Der Guard leitet Nichtangemeldete auf die Anmeldung um,
 * und das ist bei einem Impressum genau falsch — es muss ohne jede Hürde
 * erreichbar sein.
 *
 * SIE KENNT DIE SITZUNG TROTZDEM, weil der Leerzustand für Admins einen Weg
 * zum Editor zeigt. Gelesen wird die Rolle aus der DATENBANK, nicht aus der
 * Sitzung — dieselbe Regel wie im Guard (M1-05): Wer zum Zeitpunkt der
 * Anmeldung Admin war, ist es jetzt vielleicht nicht mehr.
 */

/* config.php fehlt, solange die Anwendung nicht eingerichtet ist. db.php
 * bricht dann hart ab; login.php fängt das seit jeher mit derselben Zeile.
 * Ein Impressum ist das erste, was jemand auf einer frischen Installation
 * aufruft — es darf keinen weißen Fehler zeigen. */
if (!is_file(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }

require_once __DIR__ . '/db.php';
/* Für logo_src(): Ohne session_lib.php fände es logo_stamm() nicht und fiele
 * still auf den Hubschrauber zurück, gleich was die Installation eingestellt
 * hat — genau der Fund F-P3-AN, nur eine Seite weiter. */
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/rechtstexte_lib.php';
require_once __DIR__ . '/ui.php';

$rtSchluessel = $rtSchluessel ?? 'impressum';
if (!isset(RT_TEXTE[$rtSchluessel])) { $rtSchluessel = 'impressum'; }
$titel = RT_TEXTE[$rtSchluessel];

/* ---- Läuft gerade eine Sitzung, und ist es eine Administration? ------------
 *
 * NUR FRAGEN, NICHT ERZWINGEN. session_start() nimmt ein vorhandenes Cookie
 * an und legt sonst eine leere Sitzung an — es meldet niemanden an und leitet
 * niemanden um.
 *
 * Die Rolle kommt aus der Datenbank. Sie in der Sitzung mitzuführen wäre
 * schneller und an dieser Stelle falsch: Eine zurückgenommene Adminrolle
 * würde bis zur nächsten Anmeldung weiter gelten. */
$istAdmin = false;
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'httponly' => true, 'secure' => !empty($_SERVER['HTTPS']),
        'samesite' => 'Strict', 'path' => '/',
    ]);
    @session_start();
}
if (!empty($_SESSION['user_id'])) {
    try {
        $st = db()->prepare('SELECT role FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $istAdmin = rolle_darf_verwalten((string)$st->fetchColumn());
    } catch (Throwable) {
        // Keine Datenbank: dann eben kein Adminhinweis. Die Seite bleibt lesbar.
    }
}

$text = rt_lesen($rtSchluessel);
$leer = rt_leer($text['inhalt']);

/* ---- Zurück: wohin? -------------------------------------------------------
 *
 * Wer angemeldet ist, will auf die Startseite; wer nicht, zur Anmeldung —
 * dorthin, wo er herkam. Mockup 32 zeigt „Anmeldung", Mockup 34 „Zurück zur
 * Anmeldung"; beide meinen denselben Weg. */
$zurueck = !empty($_SESSION['user_id'])
    ? ['text' => 'Zur Startseite', 'href' => 'index.php']
    : ['text' => 'Zur Anmeldung',  'href' => 'login.php'];

ui_seite_start(['titel' => $titel]);
ui_kopf(['menue' => false, 'zurueck' => $zurueck]);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
    <h1><?= e($titel) ?></h1>

    <?php ui_karte_start([]); ?>
      <?php if ($leer): ?>
        <?php /* DER LEERZUSTAND IST EINE GUELTIGE ANTWORT, kein Fehler — die
                 Anwendung liefert keinen Rechtstext mit. Deshalb der Ton
                 „info" und nicht „warn": Für eine Besucherin ist das eine
                 Auskunft über den Betreiber, keine Störung. */ ?>
        <?= ui_meldung_markup('info',
            'Der Betreiber dieser Installation hat ' .
            ($rtSchluessel === 'impressum'
                ? 'noch kein Impressum hinterlegt.'
                : 'noch keine Datenschutzerklärung hinterlegt.')) ?>
        <?php if ($istAdmin): ?>
          <p class="feld-hinweis">Du bist als Administration angemeldet und kannst
             den Text unter <a href="admin_installation.php">Einstellungen →
             Installation</a> hinterlegen.</p>
        <?php endif; ?>
      <?php else: ?>
        <div class="text">
          <?php /* HIER UND NUR HIER steht unmaskiertes Markup auf einer Seite
                   dieser Anwendung. rt_html() maskiert seine Eingabe
                   vollständig, bevor es Struktur erkennt, und erzeugt
                   ausschliesslich h2/h3/p/br/ul/ol/li/a mit href — geprüft
                   in tools/rechtstexte/. */ ?>
          <?= rt_html($text['inhalt']) ?>
          <?= rt_stand_markup($text['stand']) ?>
        </div>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>
  </main>
</div>
<?php
ui_fuss_seite();
ui_seite_ende();
