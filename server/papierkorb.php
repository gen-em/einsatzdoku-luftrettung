<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Aktionen des Papierkorbs. Wiederherstellen laeuft direkt (harmlos,
 * jederzeit umkehrbar); das endgueltige Loeschen zeigt vorher eine
 * Zwischenseite mit dem Umfang.
 *
 * SCHLUESSEL IST DIE KENNUNG DES DIENSTTAGS (Web 6.0.0), nicht mehr das Datum.
 * Die frueheren Formatpruefungen auf 'YYYY-MM-DD' sind damit zu Pruefungen auf
 * eine positive Kennung geworden — dieselbe Absicherung an derselben Stelle,
 * nur fuer den neuen Schluessel (M5-08).
 */

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
$dayId  = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) { csrf_check(); }

if ($isPost && $action === 'restore_day' && $dayId > 0) {
    trash_restore_day($userId, $dayId);
    header('Location: index.php?d=' . (int)$dayId); exit;
}
if ($isPost && $action === 'restore_mission' && $id > 0) {
    /* NICHT MEHR BEDINGUNGSLOS (Backlog Nr. 33). Liegt der Diensttag selbst im
     * Papierkorb, lehnt trash_restore_mission() ab — sonst entstuende ein
     * aktiver Einsatz an einem geloeschten Tag, also genau der halb sichtbare
     * Zustand, den das Einspielen einer Sicherung seit E-S1-19 verweigert.
     * Die Meldung geht ueber die Sitzung, weil danach umgeleitet wird. */
    $ergebnis = trash_restore_mission($userId, $id);
    if ($ergebnis === 'tag_im_papierkorb') {
        $_SESSION['flash_error'] =
            'Der Diensttag dieses Einsatzes liegt ebenfalls im Papierkorb. '
          . 'Stelle zuerst den Diensttag wieder her — der Einsatz bleibt so lange hier.';
        header('Location: papierkorb.php'); exit;
    }
    header('Location: index.php'); exit;
}
/* Dieselbe Pruefung wie beim Wiederherstellen (M5-08).
 *
 * Vorher stand hier nur die Rueckfrage. Das Wiederherstellen — die UMKEHRBARE
 * Handlung — pruefte den Schluessel, das endgueltige Loeschen nicht. Die
 * Zwischenseite weiter unten prueft zwar auch, aber erst NACH diesem Block:
 * Ein POST mit confirm=ja kam nie dort an.
 *
 * Praktisch ausgenutzt haette man das kaum — trash_purge_day() arbeitet mit
 * vorbereiteten Anweisungen, eine unsinnige Kennung trifft schlicht nichts. Aber
 * die schwaechere Pruefung ausgerechnet am unumkehrbaren Weg ist die falsche
 * Richtung, und beim naechsten Umbau von trash_purge_day() waere sie die
 * Stelle, an der es weh tut. */
if ($isPost && $action === 'purge_day' && ($_POST['confirm'] ?? '') === 'ja'
    && $dayId > 0) {
    trash_purge_day($userId, $dayId);
    header('Location: index.php'); exit;
}
if ($isPost && $action === 'purge_mission' && ($_POST['confirm'] ?? '') === 'ja' && $id > 0) {
    // Ebenso: Das Wiederherstellen verlangt $id > 0, das endgueltige Loeschen
    // verlangte gar nichts (M5-08).
    trash_purge_mission($userId, $id);
    header('Location: index.php'); exit;
}

/* ---- Zwischenseite fuer das endgueltige Loeschen ----------------------- */
$istTag = ($action === 'purge_day');
if ($istTag && $dayId <= 0) {
    ui_abbruch(400, 'Ungültiger Diensttag.',
               ['zurueck' => 'papierkorb.php', 'zurueck_text' => 'Zum Papierkorb']);
}
$zeigeListe = ($action !== 'purge_day' && $action !== 'purge_mission');

if (!$zeigeListe && $istTag) {
    $tag = dt_laden($userId, $dayId, true);
    if ($tag === null) { header('Location: papierkorb.php'); exit; }
    $scope = trash_scope_day($userId, $dayId);
    // Der Umfang zaehlt nur nicht-geloeschte Zeilen; im Papierkorb sind alle
    // markiert, deshalb hier direkt zaehlen.
    $c = db()->prepare('SELECT COUNT(*) FROM missions
                        WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL');
    $c->execute([$userId, $dayId]);
    $anzahl = (int)$c->fetchColumn();
    /* WAS NOCH AKTIV AM TAG HAENGT (Backlog Nr. 33). In aller Regel nichts —
     * aber wenn doch, geht es mit, und dann muss es hier stehen. Die Zahl
     * oben zaehlt nur die geloeschten; sie war damit zu klein. */
    $aktiv = trash_aktiv_am_tag($userId, $dayId);
} elseif (!$zeigeListe) {
    $st = db()->prepare('SELECT * FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL');
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    if (!$m) { header('Location: papierkorb.php'); exit; }
}

$trashDays     = $zeigeListe ? trash_list_days($userId) : [];
$trashMissions = $zeigeListe ? trash_list_missions($userId) : [];

/* Meldung aus der Sitzung abholen — dieselbe Mechanik wie in
 * admin_stammdaten.php und einstellungen.php. Sie wird gebraucht, seit das
 * Zurueckholen eines Einsatzes abgelehnt werden kann (Backlog Nr. 33): Nach
 * einer Umleitung ist eine Variable weg, und eine Handlung, die nichts tut
 * und nichts sagt, ist die schlechteste von beidem. */
$fehler = null;
if (!empty($_SESSION['flash_error'])) {
    $fehler = (string)$_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
ui_seite_start(['titel' => $zeigeListe ? 'Papierkorb' : 'Endgültig löschen']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar(null); ?>
  <main class="page">
  <?php if ($zeigeListe): ?>
    <h1>Papierkorb</h1>
    <?php ui_meldung(null, $fehler, 'info', '    '); ?>
    <p class="muted">Gelöschtes bleibt <?= TRASH_DAYS ?> Tage hier und wird danach
       automatisch endgültig entfernt.</p>

    <?php if (!$trashDays && !$trashMissions): ?>
      <div class="card"><p>Der Papierkorb ist leer.</p></div>
    <?php endif; ?>

    <?php if ($trashDays): ?>
      <h2>Diensttage</h2>
      <?php /* Datum UND Dienstbeginn: Seit E9 können mehrere Diensttage auf
               einem Kalendertag liegen, und im Papierkorb sind sie ohne die
               Uhrzeit nicht auseinanderzuhalten. Rettungsmittel und Art stehen
               daneben — aus den eingefrorenen Spalten (E8). */ ?>
      <table class="data trashtable">
        <thead><tr><th>Diensttag</th><th>Rettungsmittel</th><th>Einsätze</th>
                   <th>gelöscht am</th><th class="th-act">Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($trashDays as $t):
              $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']); ?>
          <tr>
            <td><span class="artzeichen" title="<?= e($sym['text']) ?>"
                      aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
                <?= e(dt_lesbar($t, true)) ?></td>
            <td><?= $t['vehicle_name'] !== null && $t['vehicle_name'] !== ''
                    ? e((string)$t['vehicle_name']) : '<span class="dash">–</span>' ?></td>
            <td><?= (int)$t['einsaetze'] ?></td>
            <td><?= e(fmt_local((string)$t['deleted_at'], 'd.m.Y H:i')) ?></td>
            <td><div class="rowactions">
              <form method="post" action="papierkorb.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="restore_day">
                <input type="hidden" name="d" value="<?= (int)$t['id'] ?>">
                <button class="btn-primary">Wiederherstellen</button>
              </form>
              <a class="btn-red"
                 href="papierkorb.php?action=purge_day&amp;d=<?= (int)$t['id'] ?>">Endgültig löschen</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <?php if ($trashMissions): ?>
      <h2>Einsätze</h2>
      <table class="data trashtable">
        <thead><tr><th>Einsatzdatum</th><th>Beginn</th><th>gelöscht am</th><th class="th-act">Aktionen</th></tr></thead>
        <tbody>
        <?php foreach ($trashMissions as $t): ?>
          <tr>
            <?php /* Das ECHTE Einsatzdatum aus `started_at` (E14) — der Einsatz
                     trägt seit Web 6.0.0 kein eigenes Datum mehr, und das
                     seines Dienstes kann ein anderes sein. */ ?>
            <td><?= e(fmt_local((string)$t['started_at'], 'd.m.Y')) ?></td>
            <td><?= e(fmt_local((string)$t['started_at'])) ?></td>
            <td><?= e(fmt_local((string)$t['deleted_at'], 'd.m.Y H:i')) ?></td>
            <td><div class="rowactions">
              <form method="post" action="papierkorb.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="restore_mission">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button class="btn-primary">Wiederherstellen</button>
              </form>
              <a class="btn-red"
                 href="papierkorb.php?action=purge_mission&amp;id=<?= (int)$t['id'] ?>">Endgültig löschen</a>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

  <?php else: ?>
    <h1>Endgültig löschen?</h1>
    <div class="card">
      <?php if ($istTag): ?>
        <p><strong>Diensttag <?= e(dt_lesbar($tag, true)) ?></strong><?php
             if ($tag['vehicle_name'] !== null && $tag['vehicle_name'] !== '') {
                 echo ' · ' . e((string)$tag['vehicle_name']);
             } ?>
           mit <?= $anzahl ?> Einsätzen, Ruhesegmenten und allen Tracks.</p>
        <?php /* AKTIVE EINTRÄGE AM GELÖSCHTEN TAG (Backlog Nr. 33). Sie gehen
                 seit Web 8.0.0 mit — vorher blieben sie ohne Diensttag zurück
                 und waren danach halb sichtbar. Deshalb werden sie hier
                 EINZELN genannt, nicht bloß gezählt: Wer sie behalten will,
                 muss sie erkennen können. */ ?>
        <?php if ($aktiv['einsaetze'] || $aktiv['segmente']): ?>
          <p class="alert alert-warn">An diesem Diensttag hängt außerdem noch
             <strong>Aktives</strong>, das <strong>mitgelöscht</strong> wird:</p>
          <ul>
            <?php foreach ($aktiv['einsaetze'] as $a): ?>
              <li>Einsatz vom <?= e(fmt_local((string)$a['started_at'], 'd.m.Y')) ?>,
                  <?= e(fmt_local((string)$a['started_at'])) ?> Uhr —
                  <a href="einsatz.php?id=<?= (int)$a['id'] ?>">ansehen</a>,
                  <a href="einsatz_verschieben.php?id=<?= (int)$a['id'] ?>">verschieben</a></li>
            <?php endforeach; ?>
            <?php if ($aktiv['segmente'] > 0): ?>
              <li><?= (int)$aktiv['segmente'] ?> Ruhesegment(e)</li>
            <?php endif; ?>
          </ul>
          <p class="muted">Wer einen davon behalten will, verschiebt ihn
             vorher an einen anderen Diensttag.</p>
        <?php endif; ?>
      <?php else: ?>
        <p><strong>Einsatz vom <?= e(fmt_local((string)$m['started_at'], 'd.m.Y')) ?>,
           <?= e(fmt_local((string)$m['started_at'])) ?> Uhr</strong></p>
      <?php endif; ?>
      <p class="alert">Dieser Schritt lässt sich <strong>nicht</strong> rückgängig machen.
         Die Daten sind danach unwiederbringlich fort — auch die verschlüsselten Angaben.</p>
      <p class="muted">Anschließend werden die betroffenen Einsätze für die Uhr gesperrt,
         damit sie nicht durch Nachlieferungen erneut angelegt werden.</p>
    </div>
    <form method="post" action="papierkorb.php" class="inline-form">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="<?= $istTag ? 'purge_day' : 'purge_mission' ?>">
      <input type="hidden" name="d" value="<?= (int)$dayId ?>">
      <input type="hidden" name="id" value="<?= (int)$id ?>">
      <input type="hidden" name="confirm" value="ja">
      <button class="btn-red">Ja, endgültig löschen</button>
      <a class="btn-plain" href="papierkorb.php">Abbrechen</a>
    </form>
  <?php endif; ?>
    <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
