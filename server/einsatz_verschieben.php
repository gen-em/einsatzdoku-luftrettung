<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Einen Einsatz einem anderen Diensttag zuordnen (A5.2, Auftragspunkt 13).
 *
 * Eigene Seite statt eines stillen Freischaltens des Datumsfeldes im Formular
 * (E4): Die Nebenwirkung — der Einsatz wechselt die Tageszugehoerigkeit — waere
 * an einem plötzlich beschreibbaren Feld nicht zu sehen. Eine bewusste,
 * benannte Handlung ist einem editierbaren Feld vorzuziehen.
 *
 * Die UHRZEITEN BLEIBEN. Wer die Uhrzeiten mitverschieben will, meint einen
 * anderen Fall — die falsch gestellte Uhr — und ist bei „Datum des Diensttags
 * ändern" richtig (diensttag_datum.php). Die Seite sagt das ausdruecklich.
 *
 * GEWAEHLT WIRD EIN DIENSTTAG, KEIN DATUM (Web 6.0.0). Bis dahin nahm die Seite
 * ein Datum entgegen, weil es je Kalendertag genau einen Flugtag gab; ein
 * fehlender wurde beim Verschieben angelegt. Seit E9 ist beides vorbei: Ein
 * Datum kann mehrere Dienste tragen, und einer davon ist gemeint. Die Liste
 * nennt deshalb die vorhandenen Diensttage mit allem, was sie unterscheidet —
 * Uhrzeit, Art, Rettungsmittel, Standort und Einsatzzahl. Angelegt wird hier
 * nichts: Ein neuer Diensttag als Nebenwirkung des Verschiebens waere eine
 * Ueberraschung, und „+ Diensttag anlegen" ist einen Klick entfernt.
 */

$mid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

$mq = db()->prepare('SELECT id, day_id, started_at, ended_at
                     FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
$mq->execute([$mid, $userId]);                                       // Datentrennung!
$mission = $mq->fetch();
if (!$mission) { ui_abbruch(404, 'Einsatz nicht gefunden.'); }

$altDayId = $mission['day_id'] !== null ? (int)$mission['day_id'] : 0;
$altTag   = $altDayId > 0 ? dt_laden($userId, $altDayId) : null;
$meldung  = null;
$fehler   = null;
$ziel     = (int)($_POST['ziel'] ?? 0);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if ($ziel <= 0) {
        $fehler = 'Bitte einen Diensttag wählen.';
    } else {
        $e = tz_einsatz_verschieben($userId, $mid, $ziel);
        if ($e['ok']) {
            // Der Einsatz bleibt derselbe; die Bestaetigung gehoert an seine
            // Seite, wo sich das Ergebnis unmittelbar nachsehen laesst.
            header('Location: einsatz.php?id=' . $mid . '&verschoben=1');
            exit;
        }
        $fehler = $e['meldung'];
    }
}

/* Auswahlliste: die vorhandenen Diensttage ohne den, an dem der Einsatz schon
   haengt, und ohne Papierkorb-Eintraege (dt_liste() laesst sie aus). Die Zahl
   ist gedeckelt — wo der Deckel greift, sagt der Hinweis unten, dass „nicht in
   der Liste" nicht „gibt es nicht" bedeutet. */
$LIMIT = 400;
$tage = dt_liste($userId, $LIMIT);

// Einsatzzahl je Diensttag in EINER Abfrage; ohne sie ist ein Tag von einem
// anderen desselben Datums oft nicht zu unterscheiden.
$zq = db()->prepare('SELECT day_id, COUNT(*) AS n FROM missions
                     WHERE user_id = ? AND deleted_at IS NULL AND day_id IS NOT NULL
                     GROUP BY day_id');
$zq->execute([$userId]);
$zahlen = [];
foreach ($zq->fetchAll() as $z) { $zahlen[(int)$z['day_id']] = (int)$z['n']; }

$auswahl = [];
foreach ($tage as $t) {
    if ((int)$t['id'] === $altDayId) { continue; }
    $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']);
    $teile = [];
    if ($t['vehicle_name'] !== null && $t['vehicle_name'] !== '') { $teile[] = (string)$t['vehicle_name']; }
    if ($t['base_name']    !== null && $t['base_name']    !== '') { $teile[] = (string)$t['base_name']; }
    $auswahl[] = [
        'id'        => (int)$t['id'],
        'text'      => dt_lesbar($t, true),
        'arttext'   => $sym['text'],
        'wer'       => implode(' · ', $teile),
        'einsaetze' => $zahlen[(int)$t['id']] ?? 0,
    ];
}
$gedeckelt = count($tage) >= $LIMIT;

/* Die Auswahlliste als Wert=>Text-Abbildung fuer ui_feld. Der Text traegt
 * alles, was zwei Dienste eines Kalendertags unterscheidet — Uhrzeit, Wer,
 * Einsatzzahl und Art. Die Art steht als WORT am Ende: In einem <option>
 * laesst sich kein SVG unterbringen, und die Auskunft soll nicht an einer
 * Grafik haengen, die jedes Betriebssystem anders zeichnet (E-P3-18). */
$zielOpt = ['' => '– Diensttag wählen –'];
foreach ($auswahl as $a) {
    $zielOpt[(string)(int)$a['id']] = $a['text']
        . ($a['wer'] !== '' ? ' · ' . $a['wer'] : ' · ohne Zuordnung')
        . ' · ' . (int)$a['einsaetze']
        . ((int)$a['einsaetze'] === 1 ? ' Einsatz' : ' Einsätze')
        . ' · ' . $a['arttext'];
}

ui_seite_start(['titel' => 'Einsatz verschieben']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $altDayId > 0 ? $altDayId : null]); ?>

  <?php ui_titelzeile([
      'titel'   => 'Einsatz verschieben',
      'zurueck' => ['text' => 'Zurück zum Einsatz', 'href' => 'einsatz.php?id=' . $mid],
  ]); ?>
  <?php ui_meldung(null, $fehler, 'info', '  '); ?>

  <p class="seiten-erklaerung">Die <strong>Uhrzeiten bleiben unverändert</strong>
     — es ändert sich allein, zu welchem Dienst der Einsatz gehört. Sollen auch
     die Zeiten mitwandern, weil die Uhr falsch gestellt war, ist
     <?php if ($altDayId > 0): ?><a href="diensttag_datum.php?d=<?= (int)$altDayId ?>">Datum
     des Diensttags ändern</a><?php else: ?>„Datum des Diensttags ändern"<?php endif; ?>
     der richtige Weg.</p>

  <?php ui_karte_start(['titel' => 'Einsatz vom '
      . fmt_local((string)$mission['started_at'], 'd.m.Y')]); ?>

    <?php
      ui_zeile([
          'text'  => fmt_local($mission['started_at']) . ' – '
                   . fmt_local($mission['ended_at']) . ' Uhr',
          'klein' => 'Diese Zeiten bleiben stehen.',
      ]);
      ui_zeile([
          'text'  => 'Derzeitiger Diensttag',
          'klein' => $altTag !== null ? dt_lesbar($altTag, true) : 'keiner',
          'href'  => $altDayId > 0 ? 'index.php?d=' . $altDayId : '',
      ]);
    ?>

    <?php if (!$auswahl): ?>
      <?= ui_meldung_markup('warn', 'Es gibt keinen anderen Diensttag, dem dieser '
          . 'Einsatz zugeordnet werden könnte.', '',
          ui_knopf(['text' => 'Diensttag anlegen', 'art' => 'primaer',
                    'symbol' => 'plus', 'href' => 'diensttag_neu.php'])) ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Zurück zum Einsatz', 'art' => 'leise',
                      'href' => 'einsatz.php?id=' . $mid]) ?>
      </div>
    <?php else: ?>
      <form method="post" action="einsatz_verschieben.php"
            id="verschiebeform" data-dirty-track>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $mid ?>">
        <?php /* Ein Auswahlfeld, kein Datumsfeld: Der Zieltag ist eine Kennung,
                 und die Liste sagt, welcher Dienst dahintersteht. */ ?>
        <?php ui_feld([
            'name' => 'ziel', 'label' => 'Neuer Diensttag', 'art' => 'select',
            'optionen' => $zielOpt, 'pflicht' => true,
            'wert' => $ziel > 0 ? (string)$ziel : '',
            'klein' => 'Gewählt wird ein vorhandener Diensttag; angelegt wird hier '
                     . 'keiner. Ein späterer Upload derselben Uhr zieht den '
                     . 'Einsatz nicht zurück.'
                     . ($gedeckelt
                        ? ' Die Liste zeigt die ' . (int)$LIMIT . ' jüngsten '
                        . 'Diensttage — ältere sind nicht darunter, und das heißt '
                        . 'nicht, dass es sie nicht gibt.'
                        : ''),
        ]); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Einsatz verschieben', 'art' => 'primaer',
                        'symbol' => 'tausch']) ?>
          <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                        'href' => 'einsatz.php?id=' . $mid,
                        'attr' => ' data-cancel-form="verschiebeform"'
                                . ' data-cancel-confirm="Der gewählte Diensttag geht'
                                . ' verloren. Trotzdem abbrechen?"']) ?>
        </div>
      </form>
    <?php endif; ?>

  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
