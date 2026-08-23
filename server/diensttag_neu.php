<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/**
 * Diensttag von Hand anlegen — fuer Dienste, an denen die Uhr nicht lief.
 *
 * ES WIRD IMMER EIN NEUER ANGELEGT (E9). Bis Web 5.10.0 stand hier ein
 * `INSERT IGNORE` und davor die Frage, ob es den Tag schon gibt: Ein Flugtag war
 * ein Kalendertag, und einen zweiten am selben Datum konnte es nicht geben.
 * Genau das hat sich geaendert — ein Hubschrauberdienst am Tag und ein
 * NEF-Nachtdienst am Abend sind zwei Diensttage an einem Datum (A1). Die
 * Doppelanlage-Pruefung ist deshalb ersatzlos entfallen.
 *
 * Standort und Rettungsmittel werden hier gleich mit erfasst. Sie sind
 * freiwillig — ein Diensttag ohne sie ist neutral und funktioniert (E26) —, aber
 * mit ihnen entstehen Art, Rollensatz und Faehigkeiten sofort, statt in der
 * Nachbearbeitung zu landen. Alles davon wird beim Anlegen EINGEFROREN (E8).
 */

$fehler = null;
$tag    = (string)($_POST['day'] ?? date('Y-m-d'));
$zeit   = (string)($_POST['zeit'] ?? '');
$baseId = (int)($_POST['base_id'] ?? 0);
$vehId  = (int)($_POST['vehicle_id'] ?? 0);

$SD_BASES    = dt_bases($userId);
$SD_VEHICLES = dt_vehicles($userId);
$SD_DEFAULTS = dt_standardwerte($userId);
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $baseId = (int)($SD_DEFAULTS['base_id'] ?? 0);
    $vehId  = (int)($SD_DEFAULTS['vehicle_id'] ?? 0);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    /* Kalendertag statt blossem Muster: Das Muster liess den 30. Februar durch,
     * und local_to_utc() haette ihn danach stillschweigend verschoben. */
    if (pruef_kalendertag($tag, 'Datum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
    }
    /* Der Dienstbeginn ist freiwillig. Ohne Angabe gilt Ortsmitternacht —
     * dieselbe Ersatzregel wie in der Migration, damit die Sortierung nicht an
     * einem NULL haengt. Mit Angabe lassen sich zwei Dienste eines Tages
     * auseinanderhalten, ohne sie erst nachzubearbeiten. */
    $startedAt = null;
    if (!$fehler) {
        $zeit = trim($zeit);
        if ($zeit !== '') {
            $startedAt = local_to_utc($tag, $zeit, 0);
            if ($startedAt === null) { $fehler = 'Bitte eine gültige Uhrzeit eintragen (HH:MM).'; }
        }
    }

    if (!$fehler) {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            $dayId = dt_anlegen($pdo, $userId, $tag, $startedAt,
                                $vehId > 0 ? $vehId : null,
                                $baseId > 0 ? $baseId : null);
            $pdo->commit();
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) { $pdo->rollBack(); }
            $fehler = 'Der Diensttag konnte nicht angelegt werden.';
            $dayId = 0;
        }
        if (!$fehler) {
            header('Location: index.php?d=' . (int)$dayId);
            exit;
        }
    }
}
ui_seite_start(['titel' => 'Diensttag anlegen']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar(null); ?>
  <main class="page">
    <h1>Diensttag anlegen</h1>
    <?php ui_meldung(null, $fehler); ?>

    <div class="card">
      <p class="muted">Für Dienste, an denen die Uhr nicht mitgelaufen ist. Der
         Diensttag erscheint danach in der Liste links; Besatzung und Einsätze
         trägst du dort nach.</p>
      <p class="muted"><strong>Mehrere Dienste an einem Kalendertag sind
         möglich</strong> — etwa ein Hubschrauberdienst am Tag und ein
         NEF-Nachtdienst am Abend. Damit sie sich unterscheiden lassen, gehört zu
         jedem eine Uhrzeit des Dienstbeginns; ohne Angabe gilt 00:00.</p>
      <form method="post" id="diensttagform" class="formcol" data-dirty-track data-submit-on-ctrl-enter>
        <?= csrf_field() ?>
        <label>Datum
          <input type="date" name="day" required value="<?= e($tag) ?>"
                 max="<?= e(date('Y-m-d')) ?>"></label>
        <label>Dienstbeginn <span class="muted small">optional, HH:MM</span>
          <?php /* Textfeld statt type="time" (E1): Native Zeitfelder zeigen je
                   nach Systemsprache 12 Stunden mit AM/PM. Format und Maske
                   sichert assets/zeitfeld.js über die Klasse. */ ?>
          <input type="text" class="zeitfeld" name="zeit" value="<?= e($zeit) ?>"
                 placeholder="z. B. 07:00" autocomplete="off"></label>
        <label>Standort
          <select name="base_id" id="basesel">
            <option value="">–</option>
            <?php foreach ($SD_BASES as $b): ?>
              <option value="<?= (int)$b['id'] ?>" <?= $baseId === (int)$b['id'] ? 'selected' : '' ?>>
                <?= e($b['name']) ?><?= !empty($b['zentral']) ? ' (zentral)' : '' ?></option>
            <?php endforeach; ?>
          </select></label>
        <label>Rettungsmittel
          <select name="vehicle_id" id="vehsel">
            <option value="">–</option>
            <?php foreach ($SD_VEHICLES as $v): $sym = dt_art_symbol((string)$v['kind']); ?>
              <option value="<?= (int)$v['id'] ?>" data-base="<?= (int)($v['base_id'] ?? 0) ?>"
                      <?= $vehId === (int)$v['id'] ? 'selected' : '' ?>>
                <?= e($sym['zeichen']) ?> <?= e($v['name']) ?><?php
                  echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
            <?php endforeach; ?>
          </select></label>
        <?php if (!$SD_BASES && !$SD_VEHICLES): ?>
          <p class="muted">Noch keine Standorte hinterlegt — unter
             <a href="einstellungen.php?t=standorte">Einstellungen →
             Standorte</a> anlegen. Ohne Zuordnung bleibt der Diensttag
             neutral: Zeiten, Phasen, Track und Reanimation werden trotzdem
             vollständig erfasst.</p>
        <?php endif; ?>
        <button class="btn-primary">Diensttag anlegen</button>
        <?php /* Abbrechen mit Rückfrage (A4.1) — sie erscheint nur, wenn
                 tatsächlich etwas geändert wurde. Beim unveränderten Vorschlag
                 wäre sie eine Frage nach nichts. */ ?>
        <p class="login-aux"><a href="index.php"
           data-cancel-form="diensttagform"
           data-cancel-confirm="Die Eingaben gehen verloren. Trotzdem abbrechen?"
           >Abbrechen</a></p>
      </form>
    </div>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script src="<?= asset('assets/zeitfeld.js') ?>"></script>
<script>
/* Standort und Rettungsmittel gehören zusammen (E15): Die Auswahl eines
   Rettungsmittels zieht seinen Standort nach, statt eine Kombination
   zuzulassen, die es nicht geben kann. Ohne Standort am Rettungsmittel
   (Bestandsdaten vor der Nachbearbeitung) bleibt der gewählte stehen. */
(function () {
  const veh = document.getElementById('vehsel');
  const base = document.getElementById('basesel');
  if (!veh || !base) { return; }
  veh.addEventListener('change', () => {
    const opt = veh.options[veh.selectedIndex];
    const bid = (opt && opt.dataset.base) ? parseInt(opt.dataset.base, 10) : 0;
    if (bid > 0) { base.value = String(bid); }
  });
})();
</script>
<?php ui_seite_ende(); ?>
