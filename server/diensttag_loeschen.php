<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Zwischenseite fuer das Loeschen eines kompletten Diensttags.
 *
 * SIE BLEIBT EINE SEITE UND WIRD KEIN DIALOG (P3/O11). Was hier steht, ist
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

/* WELCHER Diensttag gemeint ist, muss dastehen: Seit E9 koennen mehrere auf
 * einem Kalendertag liegen, und das Datum allein benennt ihn dann nicht mehr.
 * Bezeichnungen kommen aus den eingefrorenen Spalten (E8). */
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
      <?= ui_artzeichen($tag['kind'] === null ? null : (string)$tag['kind']) ?>
      <?= $wer ? e(implode(' · ', $wer)) : 'ohne Zuordnung von Standort und Rettungsmittel' ?>
    </p>

    <p>Es wird <strong>der komplette Diensttag</strong> gelöscht — nicht nur die
       Angaben zu Rettungsmittel und Besatzung. Das wandert mit:</p>

    <?php /* ZEILEN STATT AUFZAEHLUNG (O11). In der Aufzaehlung stand die Zahl
             vorn im Fliesstext („6 Einsätze mit allen Angaben") und war beim
             Ueberfliegen nicht zu finden — genau die Zahlen aber sind der
             Grund, warum diese Seite vor dem Loeschen steht. Jetzt links die
             Sache, rechts die Zahl als Plakette. */ ?>
    <?php
      $posten = [
        ['Einsätze mit allen Angaben',  (int)$scope['einsaetze'], null],
        ['Phasen-Zeitstempel',          (int)$scope['phasen'],    null],
        ['Reanimations-Protokolle',     (int)$scope['reas'],      null],
        ['Ruhesegmente',                (int)$scope['segmente'],  null],
        ['GPS-Trackpunkte',             (int)$scope['punkte'],    null],
      ];
      foreach ($posten as [$was, $zahl, $klein]) {
          ui_zeile([
              'text'  => $was,
              'klein' => $klein ?? '',
              /* Null in Rot waere falsch — nichts zu loeschen ist keine
                 Warnung. Rot bekommt, was tatsaechlich verlorengeht. */
              'plaketten' => ui_plakette(number_format($zahl, 0, ',', '.'),
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
