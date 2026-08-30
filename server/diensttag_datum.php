<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Das Datum eines Diensttages aendern (A5.3, Auftragspunkt 12).
 *
 * Der Anwendungsfall ist die FALSCH GESTELLTE UHR: Dabei sind Datum und
 * Uhrzeit gemeinsam falsch, und die Zeitstempel ziehen deshalb mit (E3). Wer
 * nur einen einzelnen Einsatz umhaengen will, dessen Uhrzeiten stimmen, ist
 * bei einsatz_verschieben.php richtig.
 *
 * KEINE KOLLISIONSPRUEFUNG MEHR (Web 6.0.0). Bis dahin verlangte die Seite ein
 * FREIES Zieldatum und fuehrte dafuer eine eigene Liste belegter Daten mit —
 * die Folge des Tagesschluessels `UNIQUE KEY uq_user_day`. Seit E9 sind mehrere
 * Diensttage je Kalendertag der vorgesehene Fall (A1); es gibt nichts mehr zu
 * kollidieren, und die Liste ist ersatzlos entfallen. Geblieben ist die
 * Rueckfrage, die beziffert, was betroffen ist.
 */

$dayId = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$tag   = $dayId > 0 ? dt_laden($userId, $dayId, true) : null;
if ($tag === null) {
    ui_abbruch(404, 'Diensttag nicht gefunden.');
}
if ($tag['deleted_at'] !== null) {
    ui_abbruch(409, 'Dieser Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
}

$umfang = tz_tag_umfang($userId, $dayId);
$fehler = null;
$ziel   = (string)($_POST['ziel'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja') {
    csrf_check();
    if (pruef_kalendertag($ziel, 'Zieldatum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
    } else {
        $e = tz_tag_datum_aendern($userId, $dayId, $ziel);
        if ($e['ok']) {
            header('Location: index.php?d=' . (int)$dayId . '&umdatiert=1');
            exit;
        }
        $fehler = $e['meldung'];
    }
}
ui_seite_start(['titel' => 'Datum des Diensttags ändern']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $dayId]); ?>

  <?php /* Der Titel nennt den Diensttag NICHT mehr mit — er stand als
           „Datum des Diensttags 21.02.2026 07:30 ändern" in einer Zeile, die
           bei 390 px vierzeilig wurde. Welcher Tag gemeint ist, sagt jetzt der
           Kartentitel darunter, und der Rueckweg fuehrt zu ihm. */ ?>
  <?php ui_titelzeile([
      'titel'   => 'Datum ändern',
      'zurueck' => ['text' => 'Zurück zum Diensttag', 'href' => 'index.php?d=' . $dayId],
  ]); ?>
  <?php ui_meldung(null, $fehler, 'info', '  '); ?>

  <p class="seiten-erklaerung">Diese Handlung ist für den Fall gedacht, dass
     <strong>die Uhr falsch gestellt war</strong>. Dann sind Datum und Uhrzeit
     gemeinsam falsch — deshalb <strong>wandern alle Zeitstempel mit</strong>,
     um dieselbe Zahl von Tagen. Die abgelesenen Uhrzeiten bleiben stehen.</p>

  <?php ui_karte_start(['titel' => 'Diensttag ' . dt_lesbar($tag, true)]); ?>

    <?php /* ZEILEN STATT AUFZAEHLUNG (O11) — dieselbe Ueberlegung wie in
             diensttag_loeschen.php: Die Zahlen sind der Grund, warum diese
             Seite vor der Aenderung steht, und in einer Aufzaehlung standen sie
             vorn im Fliesstext. */ ?>
    <p>Betroffen sind:</p>
    <?php
      $einsPk = (int)$umfang['einsaetze_papierkorb'];
      $segPk  = (int)$umfang['segmente_papierkorb'];
      ui_zeile([
          'text'  => 'Einsätze',
          'klein' => $einsPk > 0
              ? ($einsPk === 1 ? '1 weiterer im Papierkorb wandert mit'
                               : $einsPk . ' weitere im Papierkorb wandern mit')
              : '',
          'plaketten' => ui_plakette((string)(int)$umfang['einsaetze'], ['ton' => 'orange']),
      ]);
      ui_zeile([
          'text'  => 'Ruhesegmente',
          'klein' => $segPk > 0
              ? ($segPk === 1 ? '1 weiteres im Papierkorb wandert mit'
                              : $segPk . ' weitere im Papierkorb wandern mit')
              : '',
          'plaketten' => ui_plakette((string)(int)$umfang['segmente'], ['ton' => 'orange']),
      ]);
      ui_zeile([
          'text' => 'GPS-Trackpunkte',
          'plaketten' => ui_plakette(number_format((int)$umfang['punkte'], 0, ',', '.'),
                                     ['ton' => 'orange']),
      ]);
      ui_zeile([
          'text'  => 'Phasenzeiten und Reanimations-Protokolle',
          'klein' => 'der genannten Einsätze',
      ]);
      ui_zeile(['text' => 'Start- und Endzeit des Diensttags selbst']);
    ?>

    <?= ui_meldung_markup('warn', 'Alles davon wird gemeinsam geändert oder gar '
        . 'nicht.') ?>

    <?php /* `data-dirty-track` UND `data-confirm` am selben Formular — bis
             Web 9.11.0 fragte der Browser danach ein zweites Mal
             („Änderungen werden möglicherweise nicht gespeichert"), weil
             forms.js vom Absenden nie erfuhr. confirm.js sagt jetzt ab
             (F-P3-AY). Diese Seite war die einzige mit beiden Attributen. */ ?>
    <form method="post" action="diensttag_datum.php" id="datumform" data-dirty-track
          data-confirm="Das Datum dieses Diensttags wird geändert. Alle Zeitstempel wandern mit. Fortfahren?"
          data-confirm-titel="Datum des Diensttags ändern"
          data-confirm-ok="Datum ändern" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <input type="hidden" name="d" value="<?= (int)$dayId ?>">
      <input type="hidden" name="confirm" value="ja">
      <?php ui_feld([
          'name' => 'ziel', 'id' => 'zielfeld', 'label' => 'Richtiges Datum',
          'art' => 'date', 'pflicht' => true,
          'wert' => $ziel !== '' ? $ziel : (string)$tag['day'],
      ]); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Datum ändern', 'art' => 'gefahr',
                      'symbol' => 'kalender']) ?>
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                      'href' => 'index.php?d=' . (int)$dayId,
                      'attr' => ' data-cancel-form="datumform"'
                              . ' data-cancel-confirm="Das gewählte Datum geht'
                              . ' verloren. Trotzdem abbrechen?"']) ?>
      </div>
    </form>

  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Nur ein einzelner Einsatz?', 'zu' => true]); ?>
    <p>Soll <em>nur ein einzelner Einsatz</em> zu einem anderen Diensttag
       gehören, seine Uhrzeiten aber stimmen, ist das ein anderer Fall: Auf der
       Einsatzseite gibt es dafür <strong>Aktionen → Verschieben</strong>.</p>
    <p class="feld-hinweis">Ein belegtes Zieldatum ist <strong>kein
       Hindernis</strong>: Mehrere Diensttage an einem Kalendertag sind zulässig.
       Sie stehen danach in der Leiste links untereinander, unterschieden durch
       die Uhrzeit des Dienstbeginns.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
