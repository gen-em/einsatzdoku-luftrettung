<?php
declare(strict_types=1);

/**
 * DIE SCHLUESSELERNEUERUNG ALS MARKUP — zwei Abschnitte, zwei Dialoge
 * (P5b/AP9, E-P5b-20; Mockup M-P5b-02c, Zustaende 2 und 3).
 *
 *   passwort    das Passwort, das der Server ohnehin nachprueft
 *   schluessel  der neue Wert, einmal sichtbar, mit Haken und Druckknopf
 *
 * ---------------------------------------------------------------------------
 * WARUM DIESE DATEI UEBERHAUPT EXISTIERT
 * ---------------------------------------------------------------------------
 *
 * E-P5b-20 sagt: **eine Komponente, zwei Verbraucher** (R83). Das galt zuerst
 * nur fuer das Rechnen (`assets/schluessel.js`) und den Endpunkt. Beim Bau des
 * zweiten Verbrauchers — der Kontoseite unter Einstellungen — stand dieselbe
 * Anzeige ein zweites Mal vor mir: derselbe Codeblock, derselbe Haken,
 * derselbe Druckknopf, dieselbe Bedingung „Fertig erst nach dem Haken".
 *
 * Zwei Fassungen davon waeren zwei Stellen, an denen der Haken vergessen
 * werden kann. Der Haken ist hier aber nicht Zierat: Hinter ihm steht ein
 * Wert, den niemand wiederherstellen kann. Also eine Datei, zwei Einbinder.
 *
 * Erwartet: nichts. Der Dialog drumherum bringt Kopf und Kennung mit,
 * `assets/rueckfrage.js` bedient beide Fassungen.
 */
?>
  <?php /* ---- ZUSTAND 2: das Passwort ----------------------------------- */ ?>
  <div class="dialog-teil" data-rf="passwort" hidden>
    <div class="dialog-kopf">
      <h2>Neuen Wiederherstellungsschlüssel erzeugen</h2>
      <p class="feld-hinweis">Konto-Sicherheit</p>
    </div>
    <div class="dialog-inhalt">
      <p>Der neue Schlüssel ersetzt den alten; das alte Notfallblatt wird
         damit ungültig. Dafür brauchen wir einmal dein Passwort.</p>
      <?php ui_feld(['id' => 'rf-pw', 'label' => 'Passwort', 'art' => 'password',
                     'attr' => ' autocomplete="current-password"',
                     'klein' => 'Der Schlüssel entsteht in deinem Browser. Der '
                              . 'Server erhält nur den neu verpackten '
                              . 'Datenschlüssel.']); ?>
      <p class="meldung meldung-fehler" data-rf-fehler role="alert" hidden></p>
    </div>
    <div class="dialog-fuss">
      <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                    'attr' => ' data-rf-zurueck']) ?>
      <?= ui_knopf(['text' => 'Schlüssel erzeugen', 'art' => 'primaer',
                    'typ' => 'button', 'attr' => ' data-rf-erzeugen']) ?>
    </div>
  </div>

  <?php /* ---- ZUSTAND 3: der neue Schluessel ------------------------------
           DIESELBE ANSICHT WIE BEIM ERSTEN SETZEN DES PASSWORTS
           (`pw_handling.php`): Codeblock, Haken „notiert", Druckknopf. Das ist
           kein Zufall, sondern die Anmerkung des Mockups — wer den Schluessel
           zum zweiten Mal sieht, soll dieselbe Ansicht wiedererkennen.

           DAS DRUCKFORMULAR GEHT AUF `notfallblatt.php` und traegt den Wert
           in einem versteckten Feld. Es wird per JavaScript gefuellt und in
           einem neuen Fenster geoeffnet — POST, weil der Schluessel nicht in
           eine Adresszeile, einen Verlauf oder ein Zugriffsprotokoll gehoert.

           `csrf_field()` FEHLT HIER ABSICHTLICH: `notfallblatt.php` prueft
           kein Token, weil es auch ohne Sitzung erreichbar sein muss (beim
           ersten Setzen des Passworts gibt es keine). Es schreibt nichts, es
           liest nichts — es gibt zurueck, was ihm gesendet wurde. Ein Token
           an einer Stelle, die nichts aendert, ist Zierat. */ ?>
  <div class="dialog-teil" data-rf="schluessel" hidden>
    <div class="dialog-kopf">
      <h2>Dein neuer Wiederherstellungsschlüssel</h2>
      <p class="feld-hinweis">Konto-Sicherheit</p>
    </div>
    <div class="dialog-inhalt">
      <div class="codeblock">
        <p class="codeblock-titel">Nur jetzt sichtbar — danach nie wieder</p>
        <p class="codeblock-wert" data-rf-code></p>
      </div>
      <label class="rf-haken">
        <input type="checkbox" data-rf-notiert>
        <span>Ich habe den Schlüssel sicher notiert oder das Notfallblatt
              gedruckt.</span>
      </label>
    </div>
    <div class="dialog-fuss">
      <form method="post" action="notfallblatt.php" target="_blank"
            rel="noopener" class="rf-druck">
        <input type="hidden" name="code" data-rf-druckwert>
        <?= ui_knopf(['text' => 'Notfallblatt drucken', 'art' => 'primaer',
                      'symbol' => 'drucken']) ?>
      </form>
      <?php /* „FERTIG" IST ERST NACH DEM HAKEN ZU HABEN. Der Dialog laesst
               sich auch nicht mit Esc schliessen, solange er hier steht —
               `rueckfrage.js` haelt beides zusammen. Das ist die eine Stelle
               dieser Anwendung, an der ein Dialog jemanden festhaelt, und sie
               ist es wert: Was hier auf dem Bildschirm steht, kann niemand
               wiederherstellen. */ ?>
      <?= ui_knopf(['text' => 'Fertig', 'art' => 'neutral', 'typ' => 'button',
                    'attr' => ' data-rf-fertig disabled']) ?>
    </div>
  </div>
