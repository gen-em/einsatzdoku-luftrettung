<?php
declare(strict_types=1);

/**
 * DIE KONTO-RUECKFRAGE (P5b/AP9, E-P5b-09, -19, -20; Mockup M-P5b-02c, Teil 1).
 *
 * „Hast du dein Notfallblatt noch?" — nach 30 Tagen, nach 6 Monaten, dann
 * jaehrlich. Drei Zustaende in EINEM Dialog:
 *
 *   1. die Frage            Ja · Nein · Spaeter
 *   2. das Passwort         nach „Nein" — die Erneuerung braucht es
 *   3. der neue Schluessel  einmalig sichtbar, mit Haken und Druckknopf
 *
 * ---------------------------------------------------------------------------
 * WARUM DREI ABSCHNITTE IN EINEM DIALOG UND NICHT DREI DIALOGE
 * ---------------------------------------------------------------------------
 *
 * Weil es EIN Vorgang ist und der Weg dazwischen nicht verlorengehen darf.
 * Zustand 3 zeigt einen Wert, den danach niemand mehr erzeugen kann — auch
 * der Server nicht. Ein Dialog, der sich dabei schliesst und einen neuen
 * oeffnet, ist ein Dialog mehr, bei dem das schiefgehen kann.
 *
 * ---------------------------------------------------------------------------
 * WAS OHNE JAVASCRIPT PASSIERT
 * ---------------------------------------------------------------------------
 *
 * Der Dialog erscheint gar nicht — `rueckfrage.js` oeffnet ihn. Das ist kein
 * Versaeumnis: Die Erneuerung RECHNET im Browser (PBKDF2, HKDF, AES-GCM), es
 * gibt keinen serverseitigen Ersatzweg und kann keinen geben, denn der Server
 * kennt den Inhaltsschluessel nicht. Ein Formular, das ohne Skript sichtbar
 * waere und nichts tun koennte, waere schlimmer als keines.
 *
 * „Spaeter" und „Ja" WAEREN ohne Skript machbar. Sie stehen trotzdem im
 * selben Dialog, weil eine halbe Frage — „Ja/Spaeter, aber Nein geht hier
 * nicht" — die Frage entwertet.
 *
 * Erwartet: `$rueckfrageStand` aus `einstieg_zustand()`.
 */

require_once __DIR__ . '/einstieg_lib.php';

/* WIE LANGE IST DAS HER? Die Frage nennt den Abstand, nicht das Datum — „vor
 * 30 Tagen" ist die Auskunft, die jemand braucht, um sich zu erinnern; ein
 * Datum muesste er erst umrechnen. Gerechnet wird aus der RUNDE, nicht aus
 * einem gespeicherten Zeitpunkt: Wann der Schluessel entstand, steht nirgends
 * — und soll auch nirgends stehen. */
$rfRunde = (int)($rueckfrageStand['rueckfrage_runde'] ?? 0);
$rfWann  = [0 => 'Vor <strong>30 Tagen</strong>',
            1 => 'Vor <strong>6 Monaten</strong>'][$rfRunde] ?? 'Vor <strong>einem Jahr</strong>';

/* Ist „Spaeter" noch moeglich? Dreimal je Runde (E-P5b-19). Der Knopf bleibt
 * danach stehen und sagt es — ein Knopf, der still verschwindet, liest sich
 * wie ein Fehler. */
$rfGeschoben = (int)($rueckfrageStand['rueckfrage_verschoben'] ?? 0);
$rfSpaeterAuf = $rfGeschoben < RUECKFRAGE_SPAETER_MAX;
?>
<dialog class="dialog" id="dlg-rueckfrage" data-rueckfrage>

  <?php /* ---- ZUSTAND 1: die Frage ------------------------------------- */ ?>
  <div class="dialog-teil" data-rf="frage">
    <div class="dialog-kopf">
      <h2>Hast du dein Notfallblatt noch?</h2>
      <p class="feld-hinweis">Konto-Sicherheit</p>
    </div>
    <div class="dialog-inhalt">
      <p><?= $rfWann ?> hast du deinen Wiederherstellungsschlüssel bekommen.
         Ohne ihn und ohne Passwort kann niemand deine verschlüsselten Daten
         öffnen — auch wir nicht.</p>
      <p class="feld-hinweis">Wir fragen nach 30 Tagen, nach 6 Monaten und
         dann jährlich.</p>
    </div>
    <?php /* DREI KNOEPFE IN EINER REIHE, drei Gewichte (Mockup): Primaer
             „Ja", Neutral „Nein", Leise „Spaeter". Die Reihenfolge ist die
             der Wahrscheinlichkeit, nicht die der Wichtigkeit — die meisten
             haben ihr Blatt. */ ?>
    <div class="dialog-fuss dialog-fuss-lang">
      <?= ui_knopf(['text' => 'Ja, liegt sicher', 'art' => 'primaer',
                    'typ' => 'button', 'attr' => ' data-rf-ja']) ?>
      <?= ui_knopf(['text' => 'Nein — neuen Schlüssel erzeugen', 'art' => 'neutral',
                    'typ' => 'button', 'attr' => ' data-rf-nein']) ?>
      <?php if ($rfSpaeterAuf): ?>
        <?= ui_knopf(['text' => 'Später (7 Tage)', 'art' => 'leise',
                      'typ' => 'button', 'attr' => ' data-rf-spaeter']) ?>
      <?php else: ?>
        <?php /* AUFGEBRAUCHT — und das steht da, statt dass der Knopf fehlt.
                 Dreimal geschoben heisst: Die Frage kommt bei jeder Anmeldung
                 wieder, bis sie beantwortet ist. */ ?>
        <p class="feld-hinweis">Dreimal verschoben — weiter geht es nicht.
           Die Frage steht bei der nächsten Anmeldung wieder da.</p>
      <?php endif; ?>
    </div>
  </div>

  <?php require __DIR__ . '/schluessel_teile.php'; ?>

</dialog>
