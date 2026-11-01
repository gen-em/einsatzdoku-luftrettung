<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/format_lib.php';   // zahl_text() fuer die Aufstellung

/**
 * Zwischenseite fuer das Loeschen eines kompletten Diensttags.
 *
 * SIE BLEIBT EINE SEITE UND WIRD KEIN DIALOG. Was hier steht, ist
 * eine Aufstellung: Einsaetze, Phasen, Reanimationen, Ruhesegmente,
 * Trackpunkte. Ein Dialog, der eine sechszeilige Aufstellung traegt, ist
 * keiner mehr — und die Aufstellung ist der Grund, warum es die Seite gibt.
 * Der Rueckfragedialog (assets/confirm.js) ist fuer das Gegenteil da: eine
 * Handlung, die sich in einem Satz beschreiben laesst.
 */

$dayId = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$tag   = $dayId > 0 ? dt_laden($userId, $dayId) : null;
if ($tag === null) { ui_abbruch(404, 'Diensttag nicht gefunden.'); }
$scope = trash_scope_day($userId, $dayId);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja') {
    csrf_check();
    trash_delete_day($userId, $dayId);
    header('Location: index.php');
    exit;
}

require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/* WELCHER Diensttag gemeint ist, muss dastehen: Es koennen mehrere auf
 * einem Kalendertag liegen, und das Datum allein benennt ihn dann nicht.
 * Bezeichnungen kommen aus den eingefrorenen Spalten. */
$wer = [];
if ($tag['vehicle_name'] !== null && $tag['vehicle_name'] !== '') { $wer[] = (string)$tag['vehicle_name']; }
if ($tag['base_name']    !== null && $tag['base_name']    !== '') { $wer[] = (string)$tag['base_name']; }

ui_seite_start(['titel' => 'Diensttag löschen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $dayId]); ?>

  <?php ui_titelzeile([
      'titel'   => 'Diensttag löschen?',
      'zurueck' => ['text' => 'Zurück zum Diensttag', 'href' => 'index.php?d=' . $dayId],
  ]); ?>

  <?php ui_karte_start(['titel' => 'Diensttag ' . dt_lesbar($tag, true)]); ?>

    <p class="feld-hinweis">
      <?= ui_artzeichen($tag['kind'] === null ? null : (string)$tag['kind'], '',
                        $tag['vehicle_typ'] === null ? null : (string)$tag['vehicle_typ']) ?>
      <?= $wer ? e(implode(' · ', $wer)) : 'ohne Zuordnung von Standort und Rettungsmittel' ?>
    </p>

    <p>Es wird <strong>der komplette Diensttag</strong> gelöscht — nicht nur die
       Angaben zu Rettungsmittel und Besatzung. Das wandert mit:</p>

    <?php /* ZEILEN STATT AUFZAEHLUNG. In einer Aufzaehlung stuende die Zahl
             vorn im Fliesstext („6 Einsätze mit allen Angaben") und waere beim
             Ueberfliegen nicht zu finden — genau die Zahlen aber sind der
             Grund, warum diese Seite vor dem Loeschen steht. Deshalb links die
             Sache, rechts die Zahl als Plakette. */ ?>
    <?php
      $posten = [
        ['Einsätze mit allen Angaben',  (int)$scope['einsaetze']],
        ['Phasen-Zeitstempel',          (int)$scope['phasen']],
        ['Ruhesegmente',                (int)$scope['segmente']],
        ['GPS-Trackpunkte',             (int)$scope['punkte']],
      ];
      foreach ($posten as [$was, $zahl]) {
          ui_zeile([
              'text'  => $was,
              /* Null in Rot waere falsch — nichts zu loeschen ist keine
                 Warnung. Rot bekommt, was tatsaechlich verlorengeht. */
              'plaketten' => ui_plakette(zahl_text($zahl),
                                         ['ton' => $zahl > 0 ? 'rot' : 'neutral']),
          ]);
      }
      ui_zeile([
          'text'  => 'Diensttag-Angaben',
          'klein' => 'Rettungsmittel, Besatzung, Notizen',
          'plaketten' => $scope['meta']
              ? ui_plakette('vorhanden', ['ton' => 'rot'])
              : ui_plakette('keine', ['ton' => 'neutral']),
      ]);
    ?>

    <?= ui_meldung_markup('info', 'Der Diensttag bleibt ' . TRASH_DAYS
        . ' Tage im Papierkorb und kehrt beim Wiederherstellen mit allen '
        . 'Einsätzen zurück.') ?>

    <form method="post" action="diensttag_loeschen.php">
      <?= csrf_field() ?>
      <input type="hidden" name="d" value="<?= (int)$dayId ?>">
      <input type="hidden" name="confirm" value="ja">
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Ganzen Diensttag in den Papierkorb',
                      'art' => 'gefahr', 'symbol' => 'korb']) ?>
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                      'href' => 'index.php?d=' . (int)$dayId]) ?>
      </div>
    </form>

  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
