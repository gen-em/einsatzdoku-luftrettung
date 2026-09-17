<?php
declare(strict_types=1);

/**
 * DIE BETREIBER-RUECKFRAGE ZUM SCHLUESSELBLATT (P5b/AP9, E-P5b-10, -21;
 * Mockup M-P5b-02c, Teil 2).
 *
 * Alle drei Monate, beim Anmelden, nur fuer die Rolle BetreiberIn: vier
 * zufaellig gewaehlte Vierergruppen vom Blatt abtippen.
 *
 * ---------------------------------------------------------------------------
 * DIESER DIALOG IST LEER, WENN ER AUFGEHT
 * ---------------------------------------------------------------------------
 *
 * Die vier Felder und ihre Beschriftungen entstehen erst, wenn
 * `api/schluesselblatt_pruefen.php` mit `aktion=stellen` gesagt hat, WELCHE
 * Gruppen gefragt sind. Das ist kein Umweg, sondern die Sache selbst: Der
 * Server wuerfelt die Positionen je Anzeige neu, und ein Formular, das sie
 * schon im Markup traegt, wuerfelt nicht — es liest ab, was PHP beim
 * Seitenaufbau entschieden hat, und das waere fuer jeden Aufruf derselben
 * Seite dasselbe.
 *
 * ---------------------------------------------------------------------------
 * WAS HIER NICHT STEHT
 * ---------------------------------------------------------------------------
 *
 * Kein Wert, nirgends. Nicht im Markup, nicht in einem versteckten Feld,
 * nicht in der Antwort des Endpunkts. Die einzige Auskunft ist die
 * ACHTSTELLIGE KENNUNG — sie sagt, welches Blatt gemeint ist, und verraet
 * nichts. Das ist die Regel des ganzen Hauses („nie der Wert, immer die
 * Kennung"), und `betrieb_schluesselblatt.php` ist ihre einzige Ausnahme.
 *
 * Und kein Knopf „Schluessel erneuern". E-P5b-10 sagt, warum: Den
 * Serverschluessel zu wechseln hiesse, jede versiegelte Sicherung neu zu
 * umhuellen — ein S10-Vorgang, kein Dialog. Wer sein Blatt verloren hat,
 * druckt es neu; der Schluessel bleibt derselbe.
 */
?>
<dialog class="dialog" id="dlg-blatt" data-blatt-dialog>
  <div class="dialog-kopf">
    <h2>Schlüsselblatt bestätigen</h2>
    <p class="feld-hinweis" data-blatt-unter>Betrieb</p>
  </div>
  <div class="dialog-inhalt">
    <p class="meldung meldung-fehler" data-blatt-fehler role="alert" hidden></p>

    <p>Alle drei Monate: Bitte trage vier Gruppen vom
       <strong>Schlüsselblatt</strong> ein <span data-blatt-kennung></span>.
       Die Werte stehen nur auf dem Blatt — der Server zeigt sie nie.</p>

    <?php /* HIER HINEIN SCHREIBT `blatt.js` die vier Felder. Ein leerer
             Behaelter ist ehrlicher als vier Platzhalterfelder, die beim
             Laden kurz mit falschen Beschriftungen dastehen. */ ?>
    <div class="blatt-gruppen" data-blatt-felder></div>

    <p class="feld-hinweis">Groß/Klein und Leerzeichen sind egal. Blatt
       verlegt? <a href="betrieb_schluesselblatt.php">Betrieb →
       Schlüsselblatt neu drucken</a> — das ändert keinen Schlüssel.</p>
  </div>
  <?php /* „SPAETER" HEISST HIER „BIS ZUR NAECHSTEN ANMELDUNG", NICHT
           „7 TAGE" — und das ist eine Abweichung von E-P5b-10, die begruendet
           sein will.
 
           Die Konto-Rueckfrage schiebt um 7 Tage, weil sie DREI eigene
           Spalten am Konto hat (`rueckfrage_naechste`, `_runde`,
           `_verschoben`). Der Schluesselblatt-Stand ist etwas anderes: EIN
           Datum in `app_state`, installationsweit, und es heisst
           `schluesselblatt_bestaetigt_am`. Um sieben Tage zu schieben,
           muesste dort ein Datum stehen, an dem NICHTS bestaetigt wurde —
           eine Unwahrheit in genau dem Feld, das die Frage beantwortet.
 
           Und sie waere fuer ALLE: Eine BetreiberIn, die schiebt, naehme die
           Frage auch der anderen weg, die sie gerade beantworten wollte.
 
           Ein Merkmal in der Sitzung tut, was gemeint war — die Frage geht
           heute weg und steht beim naechsten Anmelden wieder da —, ohne
           irgendwo etwas Falsches zu hinterlassen. */ ?>
  <div class="dialog-fuss">
    <?= ui_knopf(['text' => 'Später', 'art' => 'leise', 'typ' => 'button',
                  'attr' => ' data-blatt-spaeter']) ?>
    <?= ui_knopf(['text' => 'Prüfen', 'art' => 'primaer', 'typ' => 'button',
                  'attr' => ' data-blatt-pruefen']) ?>
  </div>
</dialog>
