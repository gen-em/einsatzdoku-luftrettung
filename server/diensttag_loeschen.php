<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/** Zwischenseite fuer das Loeschen eines kompletten Diensttags. */

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
$sym = dt_art_symbol($tag['kind'] === null ? null : (string)$tag['kind']);
ui_seite_start(['titel' => 'Diensttag löschen']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar($dayId); ?>
  <main class="page">
    <h1>Diensttag <?= e(dt_lesbar($tag, true)) ?> löschen?</h1>

    <div class="card">
      <?php /* WELCHER Diensttag gemeint ist, muss dastehen: Seit E9 koennen
               mehrere auf einem Kalendertag liegen, und das Datum allein
               benennt ihn dann nicht mehr. Bezeichnungen kommen aus den
               eingefrorenen Spalten (E8). */ ?>
      <p><span class="artzeichen" title="<?= e($sym['text']) ?>"
               aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
         <?php $wer = [];
               if ($tag['vehicle_name'] !== null && $tag['vehicle_name'] !== '') { $wer[] = (string)$tag['vehicle_name']; }
               if ($tag['base_name']    !== null && $tag['base_name']    !== '') { $wer[] = (string)$tag['base_name']; }
               echo $wer ? e(implode(' · ', $wer)) : '<em>ohne Zuordnung von Standort und Rettungsmittel</em>'; ?>
      </p>
      <p class="muted">Es wird <strong>der komplette Diensttag</strong> gelöscht —
         nicht nur die Angaben zu Rettungsmittel und Besatzung:</p>
      <ul>
        <li><?= (int)$scope['einsaetze'] ?> Einsätze mit allen Angaben</li>
        <li><?= (int)$scope['phasen'] ?> Phasen-Zeitstempel,
            <?= (int)$scope['reas'] ?> Reanimations-Protokolle</li>
        <li><?= (int)$scope['segmente'] ?> Ruhesegmente</li>
        <li><?= number_format((int)$scope['punkte'], 0, ',', '.') ?> GPS-Trackpunkte</li>
        <li><?= $scope['meta'] ? 'Diensttag-Angaben (Rettungsmittel, Besatzung, Notizen)'
                               : 'keine Diensttag-Angaben hinterlegt' ?></li>
      </ul>
      <p>Der Diensttag bleibt <strong><?= TRASH_DAYS ?> Tage</strong> im Papierkorb
         und kehrt beim Wiederherstellen <strong>mit allen Einsätzen</strong>
         zurück.</p>
    </div>

    <form method="post" action="diensttag_loeschen.php" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="d" value="<?= (int)$dayId ?>">
      <input type="hidden" name="confirm" value="ja">
      <button class="btn-red">Ganzen Diensttag in den Papierkorb</button>
      <a class="btn-plain" href="index.php?d=<?= (int)$dayId ?>">Abbrechen</a>
    </form>
    <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
