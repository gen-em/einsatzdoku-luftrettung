<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/konten_einstellungen_lib.php';

/**
 * DIE SEITE NACH DER REGISTRIERUNG (P5b/AP3, E-P5b-02, -13).
 *
 * Sie sagt, was jetzt gilt: freigeschaltet und anmeldbar, oder wartend mit
 * Frist. Mehr tut sie nicht — der Statuswechsel ist zu diesem Zeitpunkt
 * laengst geschehen, naemlich in `pw_handling.php`, als das Passwort
 * gespeichert wurde.
 *
 * ---------------------------------------------------------------------------
 * WARUM SIE KEINEN TOKEN NIMMT
 * ---------------------------------------------------------------------------
 *
 * Der naheliegende Bau waere: Der Link aus der Mail fuehrt hierher, diese
 * Seite bestaetigt die Adresse, danach geht es zum Passwort. Er ist
 * verworfen, und zwar aus einem Grund, der im Bestand steht: `password_resets`
 * hat **keine Art-Spalte**. Jede unverbrauchte Zeile dort ist bei
 * `pw_handling.php` ein gueltiger Passwort-Setz-Token. Ein „nur
 * bestaetigender" Link waere also in Wahrheit ein Passwortlink — mit einem
 * Namen, der das Gegenteil sagt.
 *
 * Deshalb fuehrt der Link aus der Mail unmittelbar auf `pw_handling.php`
 * (wie beim Einladungsweg), und diese Seite ist das, was danach kommt.
 *
 * ---------------------------------------------------------------------------
 * WARUM EIN PARAMETER GENUEGT — UND KEINE SITZUNG
 * ---------------------------------------------------------------------------
 *
 * `pw_handling.php` fuehrt eine eigene Sitzung (`sitzung_starten('passwort')`,
 * seit Web 20.27.0; davor `pw_session_start()`), und
 * der naheliegende Weg waere, den Stand dort abzulegen. Er ist nicht
 * genommen: Diese Seite muesste dafuer dieselbe Sitzung oeffnen, also
 * Sitzungsnamen, Cookie-Parameter und `use_strict_mode` nachbauen — eine
 * Doppelung an genau der Stelle, an der eine Abweichung am teuersten ist.
 *
 * Der Parameter geht, WEIL HIER NICHTS STEHT, WAS GEHEIM WAERE. Auf dieser
 * Seite gibt es keine Kontodaten, keine Adresse, keinen Namen und keinen
 * Token — nur zwei allgemeine Saetze darueber, wie es nach einer
 * Registrierung weitergeht. Wer `?s=wartet` selbst in die Adresszeile
 * tippt, liest einen Text, den er auch im Handbuch faende. Ein Wert, der
 * nichts verraet, braucht keinen Schutz vor Faelschung.
 *
 * WER SIE OHNE PARAMETER AUFRUFT, bekommt die allgemeine Fassung. Das ist
 * kein Fehlerfall, sondern der Normalfall fuer jeden, der die Adresse aus
 * dem Verlauf noch einmal oeffnet.
 */

/* Eine geschlossene Liste: Alles andere ist die allgemeine Fassung. */
$stand = (string)($_GET['s'] ?? '');
if ($stand !== 'wartet' && $stand !== 'aktiv') { $stand = ''; }

require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start(['titel' => 'Konto anlegen', 'klasse' => 'anmeldung-body']);

$fristTage = konten_reg_frist_tage();
?>
<main class="anmeldung">
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel">Konto anlegen</h1>
  <p class="anmeldung-unter"><?= e(instanz_kurz()) ?></p>

  <?php if ($stand === 'wartet'): ?>
    <?= ui_meldung_markup('ok',
        'Deine Registrierung wartet jetzt auf die Freischaltung durch den '
      . 'Betreiber. Du bekommst eine Mail, sobald das Konto frei ist — in der '
      . 'Regel innerhalb von ' . $fristTage . ' Tagen; danach verfällt die '
      . 'Registrierung und kann neu gestellt werden.',
        'Adresse bestätigt.') ?>
    <?php /* DER WIEDERHERSTELLUNGSSCHLUESSEL IST SCHON GEZEIGT WORDEN, auf
             `pw_handling.php`, und dort auch bestaetigt. Der Satz steht hier
             trotzdem: Zwischen dem Zeigen und dem ersten Anmelden liegen bei
             dieser Betriebsart unter Umstaenden Wochen, und der Zettel wird
             genau in dieser Zeit weggeraeumt. */ ?>
    <p class="feld-hinweis">Bewahre den Wiederherstellungsschlüssel sicher auf —
       nach einem Passwort-Reset ist er der einzige Weg zu deinen Daten.</p>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>

  <?php elseif ($stand === 'aktiv'): ?>
    <?= ui_meldung_markup('ok',
        'Dein Konto ist angelegt und freigeschaltet. Du kannst dich ab sofort '
      . 'anmelden.', 'Adresse bestätigt.') ?>
    <p class="feld-hinweis">Bewahre den Wiederherstellungsschlüssel sicher auf —
       nach einem Passwort-Reset ist er der einzige Weg zu deinen Daten.</p>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>

  <?php else: ?>
    <?= ui_meldung_markup('info',
        'Hier steht, wie es nach einer Registrierung weitergeht. Diese Auskunft '
      . 'gibt es einmal, unmittelbar nach dem Festlegen des Passworts.') ?>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>
  <?php endif; ?>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php ui_seite_ende(); ?>
