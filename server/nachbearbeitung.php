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

/* Die Zahl fuer den Kartenkopf: eigene und zentrale Stammdatensaetze
 * zusammen. Sie steht neben dem Titel, weil eine Liste ohne Zahl nicht sagt,
 * wie viel Arbeit sie ist. */
$sdOffenEigen = array_sum(array_map('count', $offeneSd));
$sdOffenZentral = array_sum(array_map('count', $offeneSdZ));

ui_seite_start(['titel' => 'Zuordnung nachtragen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage']); ?>

  <?php ui_titelzeile(['titel' => 'Zuordnung nachtragen']); ?>
  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <?php if (!$moeglich): ?>

    <?= ui_meldung_markup('ok',
        'Es ist nichts nachzutragen. Der Standortbezug ist in dieser '
      . 'Installation schon verbindlich — entweder war die Nachbearbeitung '
      . 'bereits abgeschlossen, oder es war von Anfang an eine '
      . 'Neuinstallation.', '',
        ui_knopf(['text' => 'Zur Startseite', 'art' => 'neutral',
                  'href' => 'index.php'])) ?>

  <?php else: ?>

    <p class="seiten-erklaerung">Der Umbau auf Diensttage hat den mechanischen
       Teil automatisch erledigt. <strong>Zwei Zuordnungen lassen sich nicht
       ableiten</strong> — sie stehen hier, weil Raten schlechter wäre als
       Fragen. Nichts davon ist dringend: Ein Diensttag ohne Zuordnung
       funktioniert, es fehlen nur Art, Besatzungsrollen und die artabhängigen
       Felder. Diese Seite verschwindet von selbst, sobald beide Listen leer
       sind.</p>

    <?php /* ---------------------------------------------------------- 1 --
             DIENSTTAGE. Die Tabelle davor hatte fuenf Spalten, darunter zwei
             Auswahlfelder und einen Knopf in EINER Zelle — bei 360 px lief sie
             waagerecht aus dem Bild, und die Auswahl war praktisch nicht zu
             treffen. Jetzt je Diensttag ein Formularblock aus dem
             Listenbaustein: Ueberschrift, Kennzeile, zwei Felder (ab 720 px
             nebeneinander), ein Knopf. */ ?>
    <?php /* KURZER KARTENTITEL. „Diensttage ohne Standort oder Rettungsmittel"
             bricht bei 390 px auf zwei Zeilen, und die Zahl im Kartenkopf
             rutscht darunter auf eine dritte. Was fehlt, sagt ohnehin jede
             Kennzeile. */ ?>
    <?php ui_karte_start(['titel' => 'Diensttage ohne Zuordnung',
                          'zahl'  => count($offeneTage)]); ?>

      <?php if (!$offeneTage): ?>
        <p class="feld-hinweis">Alle Diensttage sind zugeordnet.</p>
      <?php else: ?>

        <p class="feld-hinweis">Datum, Zeitraum und Einsatzzahl stehen dabei, weil
           sich ohne sie nicht entscheiden lässt, welcher Dienst gemeint war. Mit
           dem Speichern werden Art, Rollensatz, Fähigkeiten und Bezeichnungen
           <strong>eingefroren</strong> — spätere Änderungen an den Standorten
           wirken darauf nicht mehr.</p>

        <?php if (!$SD_BASES && !$SD_VEHICLES): ?>
          <?= ui_meldung_markup('warn', 'Es stehen keine Standorte und '
              . 'Rettungsmittel zur Verfügung. Bitte zuerst welche anlegen oder '
              . 'einen vordefinierten Standort auswählen.', '',
              ui_knopf(['text' => 'Zu den Standorten', 'art' => 'neutral',
                        'href' => 'einstellungen.php?t=standorte'])) ?>
        <?php endif; ?>

        <?php foreach ($offeneTage as $t): $tid = (int)$t['id'];
              $kenn = [];
              $kenn[] = ($t['started_at'] !== null ? fmt_local((string)$t['started_at']) : '–')
                      . ' – ' . ($t['ended_at'] !== null ? fmt_local((string)$t['ended_at']) : '–');
              $kenn[] = (int)$t['einsaetze'] === 1
                      ? '1 Einsatz' : (int)$t['einsaetze'] . ' Einsätze';
              $bisher = [];
              if ($t['vehicle_name'] !== null && $t['vehicle_name'] !== '') { $bisher[] = (string)$t['vehicle_name']; }
              if ($t['base_name'] !== null && $t['base_name'] !== '') { $bisher[] = (string)$t['base_name']; }
              $kenn[] = $bisher ? 'bisher ' . implode(' · ', $bisher) : 'ohne Angaben';
              /* Der Standortkatalog als Wert=>Text-Abbildung — ui_feld baut
                 daraus die Optionen. Der Rettungsmittelkatalog braucht ein
                 `data-base` je Option und steht deshalb von Hand darunter. */
              $baseOpt = ['' => 'Standort –'];
              foreach ($SD_BASES as $b) { $baseOpt[(string)(int)$b['id']] = (string)$b['name']; }
        ?>
          <div class="listen-form">
            <h3 class="listen-form-titel">
              <?= ui_artzeichen($t['kind'] === null ? null : (string)$t['kind']) ?>
              <a href="index.php?d=<?= $tid ?>"><?= e(dt_lesbar($t, true)) ?></a>
            </h3>
            <?php /* `.feld-hinweis`, nicht `.feld-klein`: Der Text steht VOR
                     einem Feld, und dafuer gibt es die Regel
                     `.feld-hinweis + form{margin-top:...}` schon. `.feld-klein`
                     hat nur einen Abstand nach OBEN und klebte am
                     Standort-Etikett darunter. */ ?>
            <p class="feld-hinweis"><?= e(implode(' · ', $kenn)) ?></p>
            <form method="post" action="nachbearbeitung.php">
              <?= csrf_field() ?><input type="hidden" name="action" value="tag_zuordnen">
              <input type="hidden" name="day_id" value="<?= $tid ?>">
              <div class="listen-form-felder">
                <?php ui_feld([
                    'name' => 'base_id', 'id' => 'nb-base-' . $tid,
                    'label' => 'Standort', 'art' => 'select',
                    'optionen' => $baseOpt,
                    'wert' => (string)(int)($t['base_id'] ?? 0) === '0'
                            ? '' : (string)(int)$t['base_id'],
                ]); ?>
                <?php /* VON HAND, NICHT DURCH ui_feld. Jede Option braucht ein
                         `data-base`, aus dem das Skript unten den Standort
                         nachzieht; ui_feld kennt nur Wert und Text, und ihm
                         dafuer einen Attributkanal beizubringen hiesse, den
                         Baustein fuer einen Einzelfall aufzubohren. Das Markup
                         ist dasselbe: `.feld` mit `.feld-label` und
                         `.feld-eingabe`.

                         `nb-veh` ist ein SKRIPTANKER, keine Gestaltung. */ ?>
                <div class="feld">
                  <label class="feld-label" for="nb-veh-<?= $tid ?>">Rettungsmittel</label>
                  <select class="feld-eingabe nb-veh" id="nb-veh-<?= $tid ?>" name="vehicle_id">
                    <option value="">Rettungsmittel –</option>
                    <?php foreach ($SD_VEHICLES as $v): ?>
                      <option value="<?= (int)$v['id'] ?>" data-base="<?= (int)($v['base_id'] ?? 0) ?>"
                              <?= (int)($t['vehicle_id'] ?? 0) === (int)$v['id'] ? 'selected' : '' ?>>
                        <?= e($v['name']) ?><?php
                          echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
                    <?php endforeach; ?>
                  </select>
                </div>
              </div>
              <div class="listen-form-fuss">
                <?= ui_knopf(['text' => 'Zuordnung speichern', 'art' => 'primaer',
                              'symbol' => 'haken']) ?>
              </div>
            </form>
          </div>
        <?php endforeach; ?>

      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php /* ---------------------------------------------------------- 2 --
             STAMMDATEN. Ein Block je Art, eigene und (fuer Admins) zentrale
             getrennt: Sie brauchen verschiedene Standortlisten, und die
             Verwechslung waere folgenreich — ein zentraler Eintrag an einem
             persoenlichen Standort erschiene in keiner Auswahlliste. */ ?>
    <?php
      $blocks = [['Eigene Einträge ohne Standort', $offeneSd, false, $SD_BASES, $sdOffenEigen]];
      if (ist_admin()) {
          $blocks[] = ['Zentrale Einträge ohne Standort', $offeneSdZ, true,
                       $zentraleBases, $sdOffenZentral];
      }
      foreach ($blocks as [$kartentitel, $liste, $istZentral, $basen, $anzahl]):
    ?>
      <?php ui_karte_start(['titel' => $kartentitel, 'zahl' => $anzahl]); ?>

        <?php if (!$anzahl): ?>
          <p class="feld-hinweis">Alles zugeordnet.</p>
        <?php else: ?>

          <p class="feld-hinweis">Jeder Eintrag gehört zu genau einem Standort
             (E15). Wo die Migration ihn nicht ableiten konnte — bei mehreren
             oder bei keinem Standort —, blieb er offen.</p>

          <?php if (!$basen): ?>
            <?= ui_meldung_markup('warn', 'Es steht kein passender Standort zur '
                . 'Verfügung' . ($istZentral
                    ? ' — bitte zuerst unter „Standorte systemweit" einen anlegen.'
                    : '.')) ?>
          <?php endif; ?>

          <?php foreach ($liste as $tabelle => $zeilen): ?>
            <?php foreach ($zeilen as $z): $zid = (int)$z['id'];
                  $anker = ($istZentral ? 'z' : 'e') . '-' . $tabelle . '-' . $zid;
                  $bOpt = ['' => 'Standort wählen –'];
                  foreach ($basen as $b) { $bOpt[(string)(int)$b['id']] = (string)$b['name']; }
            ?>
              <div class="listen-form">
                <h3 class="listen-form-titel"><?= e((string)$z['name']) ?></h3>
                <p class="feld-hinweis"><?= e(NB_STAMMDATEN[$tabelle]) ?></p>
                <form method="post" action="nachbearbeitung.php">
                  <?= csrf_field() ?><input type="hidden" name="action" value="sd_zuordnen">
                  <input type="hidden" name="tabelle" value="<?= e($tabelle) ?>">
                  <input type="hidden" name="id" value="<?= $zid ?>">
                  <input type="hidden" name="zentral" value="<?= $istZentral ? '1' : '0' ?>">
                  <?php ui_feld([
                      'name' => 'base_id', 'id' => 'nb-sd-' . $anker,
                      'label' => 'Standort', 'art' => 'select',
                      'optionen' => $bOpt, 'pflicht' => true,
                  ]); ?>
                  <div class="listen-form-fuss">
                    <?= ui_knopf(['text' => 'Zuordnen', 'art' => 'primaer',
                                  'symbol' => 'haken']) ?>
                  </div>
                </form>
              </div>
            <?php endforeach; ?>
          <?php endforeach; ?>

        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endforeach; ?>

    <?php /* ---------------------------------------------------------- 3 -- */ ?>
    <?php ui_karte_start(['titel' => 'Standortbezug verbindlich machen']); ?>

      <?php if ($sdGesamt): ?>
        <?php /* Die Bedingung gilt fuer die TABELLE, nicht fuer eine
                 Zeilenmenge: Ein einziger offener Eintrag — auch aus einem
                 anderen Konto — liesse das ALTER TABLE scheitern. Deshalb steht
                 hier die Gesamtzahl und nicht nur die eigene. Als Zeilen mit
                 Plakette statt als Aufzaehlung: Die Zahl ist die Auskunft, und
                 in einer Aufzaehlung stand sie vorn im Fliesstext. */ ?>
        <p>Noch offen, über alle Konten hinweg:</p>
        <?php foreach ($sdGesamt as $tab => $n): ?>
          <?php ui_zeile([
              'text' => NB_STAMMDATEN[$tab] ?? (string)$tab,
              'plaketten' => ui_plakette((string)(int)$n, ['ton' => 'orange']),
          ]); ?>
        <?php endforeach; ?>
        <p class="feld-hinweis">Solange davon etwas offen ist, bleibt die Spalte
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
          <?php /* Der Dialogtitel steht ausdruecklich dabei (O11): „Bestaetigen"
                   waere hier zu wenig — es geht um eine Schemaaenderung, und
                   der Titel ist das erste, was ein Screenreader vorliest. */ ?>
          <form method="post" action="nachbearbeitung.php"
                data-confirm="Die Spalte base_id bekommt in fünf Tabellen die Bedingung NOT NULL. Das ist eine Schemaänderung und lässt sich nicht über den Papierkorb zurücknehmen. Fortfahren?"
                data-confirm-titel="Standortbezug verbindlich machen"
                data-confirm-ok="Bedingung setzen" data-confirm-tone="danger">
            <?= csrf_field() ?><input type="hidden" name="action" value="notnull">
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Standortbezug verbindlich machen',
                            'art' => 'gefahr', 'symbol' => 'datenbank']) ?>
            </div>
          </form>
        <?php else: ?>
          <p class="feld-hinweis">Diesen letzten Schritt führt eine Administratorin
             aus — er ändert das Datenbankschema und gilt für alle Konten.</p>
        <?php endif; ?>
      <?php endif; ?>

    <?php ui_karte_ende(); ?>

    <?php if ($nichtsOffen): ?>
      <?= ui_meldung_markup('ok', 'Für dein Konto ist nichts mehr offen. Diese '
          . 'Seite verschwindet aus der Leiste links, sobald auch die Bedingung '
          . 'gesetzt ist.') ?>
    <?php endif; ?>

  <?php endif; ?>
<?php ui_geruest_ende(); ?>
<?php /* confirm.js kommt aus ui_geruest_ende() (ui.php) — eine zweite Einbindung
         haette den Rueckfragedialog doppelt geoeffnet. */ ?>
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
