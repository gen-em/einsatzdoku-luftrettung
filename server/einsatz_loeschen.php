<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';

/**
 * Zwischenseite fuer das Loeschen eines Einsatzes: zeigt erst den Umfang,
 * erst der zweite Schritt legt ihn in den Papierkorb. Bewusst serverseitig
 * (kein JavaScript) — so greift die Absicherung auch, wenn Dialoge blockiert
 * sind, und der Umfang ist vorher sichtbar.
 *
 * Sie bleibt aus demselben Grund eine Seite wie `diensttag_loeschen.php`: Der
 * Umfang ist eine Aufstellung, und eine Aufstellung gehoert nicht in einen
 * Rueckfragedialog (P3/O11).
 */

$id = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$scope = trash_scope_mission($userId, $id);
if (!$scope) { ui_abbruch(404, 'Einsatz nicht gefunden.'); }
$m = $scope['mission'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja') {
    csrf_check();
    trash_delete_mission($userId, $id);
    header('Location: index.php' . ($m['day_id'] !== null ? '?d=' . (int)$m['day_id'] : ''));
    exit;
}

require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
ui_seite_start(['titel' => 'Einsatz löschen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $m['day_id'] !== null ? (int)$m['day_id'] : null]); ?>

  <?php ui_titelzeile([
      'titel'   => 'Einsatz löschen?',
      'zurueck' => ['text' => 'Zurück zum Einsatz', 'href' => 'einsatz.php?id=' . (int)$id],
  ]); ?>

  <?php ui_karte_start(['titel' => 'Einsatz vom '
      . fmt_local((string)$m['started_at'], 'd.m.Y') . ', '
      . fmt_local((string)$m['started_at']) . ' Uhr']); ?>

    <p>Folgendes wandert mit in den Papierkorb:</p>

    <?php /* Dieselbe Aufstellung wie beim Diensttag, aus denselben Gruenden
             als Zeilen mit Plakette statt als Aufzaehlung (O11). */ ?>
    <?php
      foreach ([
        ['Phasen-Zeitstempel',     (int)$scope['phasen']],
        ['Reanimations-Protokolle', (int)$scope['reas']],
        ['GPS-Trackpunkte',        (int)$scope['punkte']],
      ] as [$was, $zahl]) {
          ui_zeile([
              'text' => $was,
              'plaketten' => ui_plakette(number_format($zahl, 0, ',', '.'),
                                         ['ton' => $zahl > 0 ? 'rot' : 'neutral']),
          ]);
      }
      ui_zeile([
          'text'  => 'Alle erfassten Angaben',
          'klein' => 'einschließlich der verschlüsselten Felder',
          'plaketten' => ui_plakette('vollständig', ['ton' => 'rot']),
      ]);
    ?>

    <?= ui_meldung_markup('info', 'Der Einsatz bleibt ' . TRASH_DAYS
        . ' Tage im Papierkorb und lässt sich bis dahin wiederherstellen. '
        . 'Danach wird er endgültig entfernt.', '',
        ui_knopf(['text' => 'Zum Papierkorb', 'art' => 'neutral',
                  'symbol' => 'korb', 'href' => 'papierkorb.php'])) ?>

    <form method="post" action="einsatz_loeschen.php">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="confirm" value="ja">
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'In den Papierkorb', 'art' => 'gefahr',
                      'symbol' => 'korb']) ?>
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                      'href' => 'einsatz.php?id=' . (int)$id]) ?>
      </div>
    </form>

  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
