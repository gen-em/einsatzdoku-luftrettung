<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/nachbearbeitung_lib.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/**
 * Einmalige Nachbearbeitung nach dem Umbau auf Diensttage (E24, A12).
 *
 * Zwei Listen, und beide gibt es nur, weil Raten hier schlimmer waere als
 * Fragen (die ausfuehrliche Begruendung steht im Kopf von
 * nachbearbeitung_lib.php):
 *
 *   1. Diensttage ohne Standort oder ohne Rettungsmittel. Sie funktionieren —
 *      Zeiten, Phasen, Track und Reanimation sind vollstaendig (A7a) —, haben
 *      aber keine Art, keine Rollen und keine artabhaengigen Felder (E26).
 *   2. Stammdatensaetze ohne Standortzuordnung. Der Standortbezug ist
 *      verbindlich (E15); wo die Migration ihn nicht ableiten konnte, blieb die
 *      Spalte leer und NULLBAR.
 *
 * DIE SEITE VERSCHWINDET, SOBALD BEIDE LISTEN LEER SIND. Erst dann wird
 * `base_id` in den fuenf Stammdatentabellen auf NOT NULL gesetzt — die zweite
 * Stufe aus A12. Danach gleichen sich aktualisierte Installation und
 * Neuinstallation vollstaendig (Problem P6).
 */

$notice = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = (string)($_POST['action'] ?? '');

    /* ---- Diensttag zuordnen -------------------------------------------- */
    if ($action === 'tag_zuordnen') {
        $dayId = (int)($_POST['day_id'] ?? 0);
        $tag = $dayId > 0 ? dt_laden($userId, $dayId) : null;
        if ($tag === null) {
            $error = 'Dieser Diensttag ist nicht vorhanden. Es wurde nichts geändert.';
        } else {
            $pdo = db();
            $pdo->beginTransaction();
            try {
                /* Dieselbe Funktion, die auch das Formular und der Import
                 * benutzen: Sie schreibt die Kennungen UND friert Art,
                 * Bezeichnungen, Standortkoordinaten, Rollensatz und
                 * Faehigkeiten ein (E8). Eine eigene Fassung hier waere die
                 * Stelle, an der die Nachbearbeitung etwas anderes tut als das
                 * Formular — und genau das darf sie nicht (A7b). */
                dt_zuordnen($pdo, $userId, $dayId,
                            isset($_POST['vehicle_id']) ? (int)$_POST['vehicle_id'] : null,
                            isset($_POST['base_id'])    ? (int)$_POST['base_id']    : null);
                $pdo->commit();
                $notice = 'Diensttag ' . dt_lesbar($tag, true) . ' zugeordnet.';
            } catch (Throwable $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = 'Die Zuordnung ist fehlgeschlagen. Es wurde nichts geändert.';
            }
        }
    }

    /* ---- Stammdatensatz einem Standort zuweisen ------------------------ */
    if ($action === 'sd_zuordnen') {
        $tabelle = (string)($_POST['tabelle'] ?? '');
        $id      = (int)($_POST['id'] ?? 0);
        $zentral = ($_POST['zentral'] ?? '') === '1';
        $baseId  = (int)($_POST['base_id'] ?? 0);

        if (!array_key_exists($tabelle, NB_STAMMDATEN)) {
            $error = 'Unbekannte Stammdatenart. Es wurde nichts geändert.';
        } elseif ($zentral && !ist_admin()) {
            // Zentrale Eintraege gehoeren den Admins (nachbearbeitung_lib.php).
            $error = 'Systemweite Standorte lassen sich nur von einer Administratorin '
                   . 'zuordnen. Es wurde nichts geändert.';
        } else {
            /* Der Zielstandort muss zur Zeile passen: Ein ZENTRALER Eintrag
             * gehoert an einen zentralen Standort, ein persoenlicher an einen,
             * den die NutzerIn hat. Sonst entstuende eine Zuordnung, die in
             * keiner Auswahlliste je erscheint. */
            $baseOk = false;
            if ($zentral) {
                $q = db()->prepare('SELECT id FROM bases WHERE id = ? AND user_id IS NULL');
                $q->execute([$baseId]);
                $baseOk = $q->fetchColumn() !== false;
            } else {
                $baseOk = dt_base_erlaubt(db(), $userId, $baseId) !== null;
            }
            if (!$baseOk) {
                $error = 'Bitte einen passenden Standort wählen. Es wurde nichts geändert.';
            } else {
                // Der Tabellenname stammt aus NB_STAMMDATEN, nicht aus der
                // Anfrage; ein Platzhalter ist dafuer ohnehin nicht moeglich.
                $wo = $zentral ? 'user_id IS NULL' : 'user_id = ?';
                $st = db()->prepare("UPDATE `$tabelle` SET base_id = ?
                                     WHERE id = ? AND base_id IS NULL AND $wo");
                $st->execute($zentral ? [$baseId, $id] : [$baseId, $id, $userId]);
                $notice = $st->rowCount() > 0
                    ? 'Zuordnung gespeichert.'
                    : 'Dieser Eintrag war bereits zugeordnet. Es wurde nichts geändert.';
            }
        }
    }

    /* ---- Zweite Stufe: base_id auf NOT NULL ---------------------------- */
    if ($action === 'notnull') {
        /* NUR ADMINS. Das Formular erscheint ohnehin nur fuer sie — aber ein
         * Knopf, den die Oberflaeche nicht zeigt, ist keine Pruefung: Diese
         * Handlung aendert das SCHEMA und gilt fuer alle Konten. Sie gehoert
         * deshalb hier abgesichert, nicht nur dort verborgen. */
        if (!ist_admin()) {
            $error = 'Diesen Schritt führt eine Administratorin aus — er ändert das '
                   . 'Datenbankschema und gilt für alle Konten. Es wurde nichts geändert.';
        } else {
            $e = nb_notnull_ziehen();
            if ($e['ok']) { $notice = $e['meldung']; } else { $error = $e['meldung']; }
        }
    }

    // Post/Redirect/Get: Ein Neuladen soll die Zuordnung nicht wiederholen.
    if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
    if ($error !== null)  { $_SESSION['flash_error']  = $error; }
    header('Location: nachbearbeitung.php');
    exit;
}

if (!empty($_SESSION['flash_notice'])) {
    $notice = $_SESSION['flash_notice']; unset($_SESSION['flash_notice']);
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error']; unset($_SESSION['flash_error']);
}

$moeglich   = nb_moeglich();
$offeneTage = $moeglich ? nb_offene_tage($userId) : [];
$offeneSd   = $moeglich ? nb_offene_stammdaten($userId) : [];
$offeneSdZ  = ($moeglich && ist_admin()) ? nb_offene_stammdaten($userId, true) : [];
$sdGesamt   = $moeglich ? nb_stammdaten_offen_gesamt() : [];

$SD_BASES    = dt_bases($userId);
$SD_VEHICLES = dt_vehicles($userId);
$zentraleBases = [];
if (ist_admin()) {
    $zentraleBases = db()->query('SELECT id, name FROM bases WHERE user_id IS NULL
                                  ORDER BY name')->fetchAll();
}
$nichtsOffen = !$offeneTage && !$offeneSd && !$offeneSdZ;
ui_seite_start(['titel' => 'Zuordnung nachtragen']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar(null); ?>
  <main class="page">
    <h1>Zuordnung nachtragen</h1>
    <?php if ($notice): ?><p class="alert alert-ok"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

    <?php if (!$moeglich): ?>
      <div class="card">
        <p>Es ist nichts nachzutragen. Der Standortbezug ist in dieser
           Installation schon verbindlich — entweder war die Nachbearbeitung
           bereits abgeschlossen, oder es war von Anfang an eine
           Neuinstallation.</p>
        <p class="login-aux"><a href="index.php">Zur Übersicht</a></p>
      </div>
    <?php else: ?>

    <div class="card">
      <p>Der Umbau auf Diensttage hat den mechanischen Teil automatisch
         erledigt. <strong>Zwei Zuordnungen lassen sich nicht ableiten</strong> —
         sie stehen hier, weil Raten schlechter wäre als Fragen.</p>
      <p class="muted">Nichts davon ist dringend: Ein Diensttag ohne Zuordnung
         funktioniert. Zeiten, Phasen, Track und Reanimation sind vollständig
         erfasst; es fehlen die Art, die Besatzungsrollen und die artabhängigen
         Felder. Diese Seite verschwindet von selbst, sobald beide Listen leer
         sind.</p>
    </div>

    <!-- ------------------------------------------------------------ 1 -->
    <h2>Diensttage ohne Standort oder Rettungsmittel</h2>
    <?php if (!$offeneTage): ?>
      <div class="card"><p>Alle Diensttage sind zugeordnet.</p></div>
    <?php else: ?>
      <p class="muted">Datum, Zeitraum und Einsatzzahl stehen dabei, weil sich
         ohne sie nicht entscheiden lässt, welcher Dienst gemeint war. Mit dem
         Speichern werden Art, Rollensatz, Fähigkeiten und Bezeichnungen
         <strong>eingefroren</strong> — spätere Änderungen an den Standorten
         wirken darauf nicht mehr.</p>
      <?php if (!$SD_BASES && !$SD_VEHICLES): ?>
        <p class="alert alert-warn">Es stehen keine Standorte und Rettungsmittel
           zur Verfügung. Bitte zuerst unter
           <a href="einstellungen.php?t=standorte">Einstellungen →
           Standorte</a> welche anlegen oder einen vordefinierten Standort
           auswählen.</p>
      <?php endif; ?>
      <table class="data">
        <thead><tr><th>Diensttag</th><th>Zeitraum</th><th>Einsätze</th>
                   <th>bisher</th><th class="th-act">Zuordnen</th></tr></thead>
        <tbody>
        <?php foreach ($offeneTage as $t): $tid = (int)$t['id'];
              $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']); ?>
          <tr>
            <td><span class="artzeichen" title="<?= e($sym['text']) ?>"
                      aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
              <a href="index.php?d=<?= $tid ?>"><?= e(dt_lesbar($t, true)) ?></a></td>
            <td class="mono"><?= $t['started_at'] !== null ? e(fmt_local((string)$t['started_at'])) : '–' ?>
              – <?= $t['ended_at'] !== null ? e(fmt_local((string)$t['ended_at'])) : '–' ?></td>
            <td><?= (int)$t['einsaetze'] ?></td>
            <td class="muted small"><?php
              $bisher = [];
              if ($t['vehicle_name'] !== null && $t['vehicle_name'] !== '') { $bisher[] = (string)$t['vehicle_name']; }
              if ($t['base_name'] !== null && $t['base_name'] !== '') { $bisher[] = (string)$t['base_name']; }
              echo $bisher ? e(implode(' · ', $bisher)) : 'ohne Angaben'; ?></td>
            <td class="th-act">
              <form method="post" action="nachbearbeitung.php" class="inline-form">
                <?= csrf_field() ?><input type="hidden" name="action" value="tag_zuordnen">
                <input type="hidden" name="day_id" value="<?= $tid ?>">
                <select name="base_id">
                  <option value="">Standort –</option>
                  <?php foreach ($SD_BASES as $b): ?>
                    <option value="<?= (int)$b['id'] ?>"
                            <?= (int)($t['base_id'] ?? 0) === (int)$b['id'] ? 'selected' : '' ?>>
                      <?= e($b['name']) ?></option>
                  <?php endforeach; ?>
                </select>
                <select name="vehicle_id" class="nb-veh">
                  <option value="">Rettungsmittel –</option>
                  <?php foreach ($SD_VEHICLES as $v): $vs = dt_art_symbol((string)$v['kind']); ?>
                    <option value="<?= (int)$v['id'] ?>" data-base="<?= (int)($v['base_id'] ?? 0) ?>"
                            <?= (int)($t['vehicle_id'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
                      <?= e($vs['zeichen']) ?> <?= e($v['name']) ?><?php
                        echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
                  <?php endforeach; ?>
                </select>
                <button class="btn-primary">Speichern</button>
              </form>
            </td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    <?php endif; ?>

    <!-- ------------------------------------------------------------ 2 -->
    <h2>Stammdaten ohne Standort</h2>
    <?php if (!$offeneSd && !$offeneSdZ): ?>
      <div class="card"><p>Alle deine Stammdatensätze sind einem Standort
         zugeordnet.</p></div>
    <?php else: ?>
      <p class="muted">Jeder Eintrag gehört zu genau einem Standort (E15). Wo die
         Migration ihn nicht ableiten konnte — bei mehreren oder bei keinem
         Standort —, blieb er offen.</p>
    <?php endif; ?>

    <?php
      /* Ein Block je Art, eigene und (fuer Admins) zentrale getrennt: Sie
       * brauchen verschiedene Standortlisten, und die Verwechslung waere
       * folgenreich — ein zentraler Eintrag an einem persoenlichen Standort
       * erschiene in keiner Auswahlliste. */
      $blocks = [['eigene', $offeneSd, false, $SD_BASES]];
      if (ist_admin()) { $blocks[] = ['zentrale', $offeneSdZ, true, $zentraleBases]; }
      foreach ($blocks as [$titel, $liste, $istZentral, $basen]):
        if (!$liste) { continue; } ?>
      <h3><?= $istZentral ? 'Zentrale Einträge' : 'Eigene Einträge' ?></h3>
      <?php if (!$basen): ?>
        <p class="alert alert-warn">Es steht kein passender Standort zur
           Verfügung<?= $istZentral ? ' — bitte zuerst unter „Standorte systemweit" einen anlegen.'
                                    : '.' ?></p>
      <?php endif; ?>
      <?php foreach ($liste as $tabelle => $zeilen): ?>
        <h4><?= e(NB_STAMMDATEN[$tabelle]) ?></h4>
        <table class="data">
          <tbody>
          <?php foreach ($zeilen as $z): ?>
            <tr>
              <td><?= e((string)$z['name']) ?></td>
              <td class="th-act">
                <form method="post" action="nachbearbeitung.php" class="inline-form">
                  <?= csrf_field() ?><input type="hidden" name="action" value="sd_zuordnen">
                  <input type="hidden" name="tabelle" value="<?= e($tabelle) ?>">
                  <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                  <input type="hidden" name="zentral" value="<?= $istZentral ? '1' : '0' ?>">
                  <select name="base_id" required>
                    <option value="">Standort wählen –</option>
                    <?php foreach ($basen as $b): ?>
                      <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
                    <?php endforeach; ?>
                  </select>
                  <button class="btn-primary">Zuordnen</button>
                </form>
              </td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
      <?php endforeach; ?>
    <?php endforeach; ?>

    <!-- ------------------------------------------------------------ 3 -->
    <h2>Standortbezug verbindlich machen</h2>
    <div class="card">
      <?php if ($sdGesamt): ?>
        <?php /* Die Bedingung gilt fuer die TABELLE, nicht fuer eine
                 Zeilenmenge: Ein einziger offener Eintrag — auch aus einem
                 anderen Konto — liesse das ALTER TABLE scheitern. Deshalb steht
                 hier die Gesamtzahl und nicht nur die eigene. */ ?>
        <p>Noch offen, über alle Konten hinweg:</p>
        <ul>
          <?php foreach ($sdGesamt as $tab => $n): ?>
            <li><?= (int)$n ?> × <?= e(NB_STAMMDATEN[$tab] ?? $tab) ?></li>
          <?php endforeach; ?>
        </ul>
        <p class="muted">Solange davon etwas offen ist, bleibt die Spalte
           <code>base_id</code> nullbar. Die Bedingung gilt für die ganze
           Tabelle — ein einziger offener Eintrag, auch aus einem anderen Konto,
           verhindert sie. Bei mehreren Konten heißt das: Alle müssen ihre
           Zuordnungen nachtragen.</p>
      <?php else: ?>
        <p>Es ist kein Stammdatensatz mehr ohne Standort — in keinem Konto. Damit
           lässt sich der Standortbezug jetzt <strong>verbindlich</strong> machen:
           <code>base_id</code> bekommt die Bedingung <code>NOT NULL</code>.
           Danach stimmen aktualisierte Installation und Neuinstallation in genau
           den fünf Spalten überein, in denen sie sich bis dahin unterschieden.</p>
        <?php if (ist_admin()): ?>
          <form method="post" action="nachbearbeitung.php" class="inline-form"
                data-confirm="Die Spalte base_id bekommt in fünf Tabellen die Bedingung NOT NULL. Das ist eine Schemaänderung und lässt sich nicht über den Papierkorb zurücknehmen. Fortfahren?"
                data-confirm-ok="Bedingung setzen" data-confirm-tone="danger">
            <?= csrf_field() ?><input type="hidden" name="action" value="notnull">
            <button class="btn-red">Standortbezug verbindlich machen</button>
          </form>
        <?php else: ?>
          <p class="muted">Diesen letzten Schritt führt eine Administratorin aus —
             er ändert das Datenbankschema und gilt für alle Konten.</p>
        <?php endif; ?>
      <?php endif; ?>
    </div>

    <?php if ($nichtsOffen): ?>
      <p class="alert alert-ok">Für dein Konto ist nichts mehr offen. Diese Seite
         verschwindet aus der Leiste links, sobald auch die Bedingung gesetzt
         ist.</p>
    <?php endif; ?>

    <?php endif; ?>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/confirm.js') ?>"></script>
<script>
/* Standort und Rettungsmittel gehören zusammen (E15): Die Auswahl eines
   Rettungsmittels zieht seinen Standort nach. Ohne Standort am Rettungsmittel
   (selbst noch nicht nachbearbeitet) bleibt der gewählte stehen. */
document.querySelectorAll('select.nb-veh').forEach(function (veh) {
  veh.addEventListener('change', function () {
    var opt = veh.options[veh.selectedIndex];
    var bid = (opt && opt.dataset.base) ? parseInt(opt.dataset.base, 10) : 0;
    if (bid > 0) {
      var base = veh.form.querySelector('select[name="base_id"]');
      if (base) { base.value = String(bid); }
    }
  });
});
</script>
<?php ui_seite_ende(); ?>
