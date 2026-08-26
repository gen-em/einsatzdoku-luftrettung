<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';

/**
 * Zwischenseite fuer das Loeschen eines Einsatzes: zeigt erst den Umfang,
 * erst der zweite Schritt legt ihn in den Papierkorb. Bewusst serverseitig
 * (kein JavaScript) — so greift die Sicherung auch, wenn Dialoge blockiert
 * sind, und der Umfang ist vorher sichtbar.
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

$title = 'Einsatz löschen';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
ui_seite_start(['titel' => 'Einsatz löschen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $m['day_id'] !== null ? (int)$m['day_id'] : null]); ?>
    <h1>Einsatz löschen?</h1>

    <div class="card">
      <p><strong>Einsatz vom <?= e(fmt_local((string)$m['started_at'], 'd.m.Y')) ?>,
         <?= e(fmt_local((string)$m['started_at'])) ?> Uhr</strong></p>
      <p class="muted">Folgendes wandert mit in den Papierkorb:</p>
      <ul>
        <li><?= (int)$scope['phasen'] ?> Phasen-Zeitstempel</li>
        <li><?= (int)$scope['reas'] ?> Reanimations-Protokolle</li>
        <li><?= number_format((int)$scope['punkte'], 0, ',', '.') ?> GPS-Trackpunkte</li>
        <li>alle erfassten Angaben inkl. der verschlüsselten Felder</li>
      </ul>
      <p>Der Einsatz bleibt <strong><?= TRASH_DAYS ?> Tage</strong> im Papierkorb
         (unten auf der Übersicht) und lässt sich bis dahin wiederherstellen.
         Danach wird er endgültig entfernt.</p>
    </div>

    <form method="post" action="einsatz_loeschen.php" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="confirm" value="ja">
      <button class="btn-red">In den Papierkorb</button>
      <a class="btn-plain" href="einsatz.php?id=<?= (int)$id ?>">Abbrechen</a>
    </form>
<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
