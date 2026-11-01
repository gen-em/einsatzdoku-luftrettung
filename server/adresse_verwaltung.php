<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/konto_lib.php';

/**
 * EINEN ADRESSWECHSEL DURCH DIE VERWALTUNG BESTAETIGEN ODER IHM WIDERSPRECHEN.
 *
 * WER HIER LANDET, hat den Link aus der Mail an die ALTE Adresse eines Kontos,
 * dessen Anmeldeadresse die Verwaltung geaendert hat. Bis zur Antwort — oder
 * bis die Frist ohne Widerspruch vergeht — ist die neue Adresse unbestaetigt,
 * und es geht kein Setz-Link hinaus (`adresse_unbestaetigt()`).
 *
 * OHNE ANMELDUNG, UND DAS IST DER ZWECK — wie bei `adresse_bestaetigen.php`.
 * Der Nachweis ist der Token aus dem Postfach der alten Adresse. Eine
 * Anmeldung ginge ausgerechnet dann nicht, wenn es darauf ankommt: Die
 * Anmeldeadresse ist ja gerade eine andere.
 *
 * DER AUFRUF TUT NICHTS, ERST DER KNOPF. Manche Mailprogramme und
 * Virenscanner rufen Links vorab ab; ein Link, der beim Aufruf widerspraeche,
 * setzte Adressen zurueck, ohne dass jemand geklickt hat. Deshalb zeigt GET
 * die Frage und zwei Knoepfe, und erst der POST antwortet. Kein CSRF-Token:
 * Es gibt keine Sitzung, und der Token im Formular IST die Berechtigung —
 * wer ihn nicht kennt, kann nichts ausloesen.
 *
 * KEIN RATENSCHUTZ. 32 Byte Zufall raet niemand (derselbe Grund wie dort).
 */

$konto = (int)($_GET['konto'] ?? $_POST['konto'] ?? 0);
$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$gueltig = $konto > 0 && preg_match('/^[0-9a-f]{64}$/', $token) === 1;
$ergebnis = null;
$antwort  = '';

if ($gueltig && ($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    $antwort = (string)($_POST['antwort'] ?? '');
    if (in_array($antwort, ['zustimmen', 'widersprechen'], true)) {
        $ergebnis = adresse_verwaltung_antwort($konto, $token, $antwort);
    }
}
/* Steht die Frage noch? Nur dann zeigt GET die Knoepfe — ein verbrauchter
 * Link sagt es gleich, statt erst nach dem Klick. */
$offen = $gueltig && $ergebnis === null ? adresse_unbestaetigt($konto) : null;
$passt = $offen !== null && hash_equals($offen['hash'], hash('sha256', $token));

ui_seite_start(['titel' => 'Adresswechsel']);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
  <?php ui_hinweise(); ?>
    <h1>Adresswechsel durch die Verwaltung</h1>

    <?php if ($ergebnis !== null && $ergebnis['ok'] && $antwort === 'zustimmen'): ?>
      <?= ui_meldung_markup('ok', 'Danke — der Wechsel ist bestätigt.') ?>
      <p class="text">Die neue Anmeldeadresse gilt ab jetzt ohne Einschränkung.
         Diese Adresse hier bekommt keine Nachrichten des Kontos mehr.</p>

    <?php elseif ($ergebnis !== null && $ergebnis['ok']): ?>
      <?= ui_meldung_markup('ok', 'Widersprochen — deine bisherige Adresse '
          . e($ergebnis['alt']) . ' gilt wieder.') ?>
      <p class="text">Alle Sitzungen des Kontos sind beendet, und offene Links zum
         Setzen eines Passworts gelten nicht mehr. Die BetreiberIn ist informiert.
         <strong>Ändere bitte nach der nächsten Anmeldung dein Passwort.</strong></p>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>

    <?php elseif ($ergebnis !== null): ?>
      <?= ui_meldung_markup('warn', e($ergebnis['grund'])) ?>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>

    <?php elseif ($passt): ?>
      <p class="text">Die Verwaltung hat die Anmeldeadresse deines Kontos geändert.
         Bis zum <?= e(datum_zeit_text(iso_utc($offen['bis']), ' um ')) ?> Uhr gilt die
         neue Adresse als unbestätigt, und es geht kein Link zum Setzen eines
         Passworts hinaus. Antwortest du nicht, gilt sie danach als bestätigt.</p>
      <p class="text"><strong>Hast du um den Wechsel gebeten?</strong></p>
      <form method="post" class="listen-form-fuss">
        <input type="hidden" name="konto" value="<?= $konto ?>">
        <input type="hidden" name="token" value="<?= e($token) ?>">
        <?= ui_knopf(['text' => 'Ja, Wechsel bestätigen', 'art' => 'primaer',
                      'name' => 'antwort', 'wert' => 'zustimmen']) ?>
        <?= ui_knopf(['text' => 'Nein, widersprechen', 'art' => 'gefahr',
                      'name' => 'antwort', 'wert' => 'widersprechen']) ?>
      </form>

    <?php else: ?>
      <?= ui_meldung_markup('warn', 'Dieser Link ist abgelaufen, schon benutzt oder '
          . 'unvollständig.') ?>
      <p class="text">Ist die Frist um, gilt die neue Adresse als bestätigt. Wer
         den Wechsel nicht wollte, wendet sich an die Verwaltung der Installation.</p>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>
    <?php endif; ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
