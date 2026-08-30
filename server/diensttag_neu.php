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

/* Der Standortkatalog als Wert=>Text-Abbildung; ui_feld baut daraus die
 * Optionen. Der Rettungsmittelkatalog braucht ein `data-base` je Option und
 * steht deshalb von Hand im Markup. */
$baseOpt = ['' => '–'];
foreach ($SD_BASES as $b) {
    $baseOpt[(string)(int)$b['id']] = (string)$b['name']
        . (!empty($b['zentral']) ? ' (zentral)' : '');
}

ui_seite_start(['titel' => 'Diensttag anlegen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage']); ?>

  <?php ui_titelzeile([
      'titel'   => 'Diensttag anlegen',
      'zurueck' => ['text' => 'Zur Startseite', 'href' => 'index.php'],
  ]); ?>
  <?php ui_meldung(null, $fehler, 'info', '  '); ?>

  <p class="seiten-erklaerung">Für Dienste, an denen die Uhr nicht mitgelaufen
     ist. Der Diensttag erscheint danach in der Liste links; Besatzung und
     Einsätze trägst du dort nach. <strong>Mehrere Dienste an einem Kalendertag
     sind möglich</strong> — etwa ein Hubschrauberdienst am Tag und ein
     NEF-Nachtdienst am Abend. Damit sie sich unterscheiden lassen, gehört zu
     jedem eine Uhrzeit des Dienstbeginns; ohne Angabe gilt 00:00.</p>

  <?php ui_karte_start(['titel' => 'Neuer Diensttag']); ?>

    <?php /* KEINE SPEICHERN-LEISTE. Sie gehoert zu Formularen, die man
             BEARBEITET und deren Stand man verlieren kann — dort erscheint sie
             mit der ersten Aenderung und klebt unten fest. Hier ist der Knopf
             das Ziel des Weges und steht am Ende des Formulars, wo man ihn
             sucht. `data-dirty-track` bleibt trotzdem: Es traegt die
             Verlassen-Warnung und die bedingte Abbrechen-Rueckfrage; die
             Leiste ist nur einer seiner Verwender (assets/forms.js). */ ?>
    <form method="post" id="diensttagform" data-dirty-track data-submit-on-ctrl-enter>
      <?= csrf_field() ?>

      <div class="listen-form-felder">
        <?php ui_feld([
            'name' => 'day', 'label' => 'Datum', 'art' => 'date',
            'wert' => $tag, 'pflicht' => true,
            'attr' => ' max="' . e(date('Y-m-d')) . '"',
        ]); ?>

        <?php /* VON HAND, NICHT DURCH ui_feld — und der Grund ist die Klasse:
                 `zeitfeld` ist der Anker, an dem assets/zeitfeld.js die
                 24-Stunden-Maske aufhaengt. ui_feld setzt Klassen an der HUELLE
                 (`.feld`), nicht am Eingabefeld; ohne diese Klasse fiele die
                 Maske STILL aus, und das Feld nähme wieder alles an.

                 Textfeld statt type="time" (E1): Native Zeitfelder zeigen je
                 nach Systemsprache 12 Stunden mit AM/PM. */ ?>
        <div class="feld">
          <label class="feld-label" for="f-zeit">Dienstbeginn
            <span class="feld-klein-inline">optional, HH:MM</span></label>
          <input class="feld-eingabe zeitfeld" type="text" id="f-zeit" name="zeit"
                 value="<?= e($zeit) ?>" placeholder="z. B. 07:00" autocomplete="off">
        </div>

        <?php ui_feld([
            'name' => 'base_id', 'id' => 'basesel', 'label' => 'Standort',
            'art' => 'select', 'optionen' => $baseOpt,
            'wert' => $baseId > 0 ? (string)$baseId : '',
        ]); ?>

        <div class="feld">
          <label class="feld-label" for="vehsel">Rettungsmittel</label>
          <select class="feld-eingabe" id="vehsel" name="vehicle_id">
            <option value="">–</option>
            <?php foreach ($SD_VEHICLES as $v): ?>
              <option value="<?= (int)$v['id'] ?>" data-base="<?= (int)($v['base_id'] ?? 0) ?>"
                      <?= $vehId === (int)$v['id'] ? 'selected' : '' ?>>
                <?= e($v['name']) ?><?php
                  echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>

      <?php if (!$SD_BASES && !$SD_VEHICLES): ?>
        <?= ui_meldung_markup('info', 'Noch keine Standorte hinterlegt. Ohne '
            . 'Zuordnung bleibt der Diensttag neutral: Zeiten, Phasen, Track und '
            . 'Reanimation werden trotzdem vollständig erfasst.', '',
            ui_knopf(['text' => 'Zu den Standorten', 'art' => 'neutral',
                      'href' => 'einstellungen.php?t=standorte'])) ?>
      <?php endif; ?>

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Diensttag anlegen', 'art' => 'primaer',
                      'symbol' => 'plus']) ?>
        <?php /* Abbrechen mit Rückfrage (A4.1) — sie erscheint nur, wenn
                 tatsächlich etwas geändert wurde. Beim unveränderten Vorschlag
                 wäre sie eine Frage nach nichts. Das Attribut gehört an einen
                 <a>; forms.js sucht `a[data-cancel-form]`. */ ?>
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'href' => 'index.php',
                      'attr' => ' data-cancel-form="diensttagform"'
                              . ' data-cancel-confirm="Die Eingaben gehen verloren.'
                              . ' Trotzdem abbrechen?"']) ?>
      </div>
    </form>

  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
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
<?php ui_seite_ende(['skripte' => ['assets/forms.js', 'assets/zeitfeld.js']]); ?>
