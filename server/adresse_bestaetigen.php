<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/konto_lib.php';

/**
 * DEN WECHSEL DER ANMELDEADRESSE BESTAETIGEN (P5b/AP5, E-P5b-16).
 *
 * OHNE ANMELDUNG, UND DAS IST DER ZWECK. Wer diese Seite aufruft, hat den
 * Link aus dem Postfach der NEUEN Adresse — genau das ist der Nachweis, um
 * den es geht. Eine Anmeldung zusaetzlich zu verlangen, wuerde nichts
 * beweisen, was der Passwortnachweis beim Vormerken nicht schon bewiesen
 * hat, und sie waere gerade dann unmoeglich, wenn sie am meisten hülfe: Wer
 * sich vertippt hat und aus Versehen eine fremde Adresse eingetragen hat,
 * bekommt den Link nicht — und genau deshalb aendert sich nichts.
 *
 * KEIN RATENSCHUTZ. Der Token hat 32 Byte Zufall; ihn zu raten ist keine
 * Frage der Versuchszahl. Ein Topf hier wuerde eine Adresse sperren, die
 * gerade ihren eigenen Link anklickt, weil der Mailclient ihn zweimal
 * abruft.
 */

$token = (string)($_GET['token'] ?? '');
$ergebnis = null;

if ($token !== '' && preg_match('/^[0-9a-f]{64}$/', $token)) {
    $ergebnis = adresse_bestaetigen($token);
}

ui_seite_start(['titel' => 'Anmeldeadresse bestätigen']);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
  <?php ui_hinweise(); ?>
    <h1>Anmeldeadresse</h1>

    <?php if ($ergebnis !== null && $ergebnis['ok']): ?>
      <?= ui_meldung_markup('ok', 'Das hat geklappt — deine Anmeldeadresse ist jetzt '
          . e($ergebnis['neu']) . '.') ?>
      <p class="text">Melde dich ab jetzt mit der neuen Adresse an. Dein Passwort
         und alle deine Daten bleiben unverändert — <strong>es hat sich nur der
         Name geändert, unter dem du hereinkommst</strong>.</p>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>

    <?php elseif ($ergebnis !== null): ?>
      <?= ui_meldung_markup('warn', e($ergebnis['grund'])) ?>
      <p class="text"><strong>Deine bisherige Adresse gilt weiter.</strong> Es ist
         nichts verlorengegangen, und du kannst dich wie gewohnt anmelden.</p>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>

    <?php else: ?>
      <?= ui_meldung_markup('warn', 'Dieser Link ist unvollständig.') ?>
      <p class="text">Kopiere ihn noch einmal vollständig aus der Nachricht —
         manche Mailprogramme brechen lange Links um. <strong>Deine bisherige
         Adresse gilt weiter</strong>, bis der Wechsel bestätigt ist.</p>
      <p class="text"><a href="login.php">Zur Anmeldung</a></p>
    <?php endif; ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
