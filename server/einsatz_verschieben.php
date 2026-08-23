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
if (!$mission) { http_response_code(404); exit('Einsatz nicht gefunden.'); }

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
        'zeichen'   => $sym['zeichen'],
        'arttext'   => $sym['text'],
        'wer'       => implode(' · ', $teile),
        'einsaetze' => $zahlen[(int)$t['id']] ?? 0,
    ];
}
$gedeckelt = count($tage) >= $LIMIT;
ui_seite_start(['titel' => 'Einsatz verschieben']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar($altDayId > 0 ? $altDayId : null); ?>
  <main class="page">
    <h1>Einsatz verschieben</h1>
    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

    <div class="card">
      <p>Der Einsatz vom
         <strong><?= e(fmt_local((string)$mission['started_at'], 'd.m.Y')) ?></strong>,
         <strong><?= e(fmt_local($mission['started_at'])) ?>
         bis <?= e(fmt_local($mission['ended_at'])) ?></strong> Uhr,
         wird einem anderen Diensttag zugeordnet.<?php
         if ($altTag !== null): ?> Derzeit gehört er zum Diensttag
         <strong><?= e(dt_lesbar($altTag, true)) ?></strong>.<?php endif; ?></p>
      <p class="muted">Die <strong>Uhrzeiten bleiben unverändert</strong> — es
         ändert sich allein, zu welchem Dienst der Einsatz gehört. Sollen auch die
         Zeiten mitwandern, weil die Uhr falsch gestellt war, ist
         <?php if ($altDayId > 0): ?>
           <a href="diensttag_datum.php?d=<?= (int)$altDayId ?>">Datum des Diensttags
           ändern</a> der richtige Weg.
         <?php else: ?>
           „Datum des Diensttags ändern" der richtige Weg.
         <?php endif; ?></p>
      <p class="muted">Gewählt wird ein <strong>vorhandener Diensttag</strong>.
         Angelegt wird hier keiner — dafür gibt es
         <a href="diensttag_neu.php">+ Diensttag anlegen</a> in der Leiste links.
         Ein späterer Upload derselben Uhr zieht den Einsatz nicht zurück.</p>
      <?php if ($gedeckelt): ?>
        <p class="muted">Die Liste zeigt die <?= (int)$LIMIT ?> jüngsten
           Diensttage. Ältere sind nicht darunter — das heißt nicht, dass es sie
           nicht gibt.</p>
      <?php endif; ?>
    </div>

    <?php if (!$auswahl): ?>
      <div class="card">
        <p class="alert alert-warn">Es gibt keinen anderen Diensttag, dem dieser
           Einsatz zugeordnet werden könnte. Bitte zuerst einen
           <a href="diensttag_neu.php">Diensttag anlegen</a>.</p>
        <p class="login-aux"><a href="einsatz.php?id=<?= $mid ?>">Zurück zum Einsatz</a></p>
      </div>
    <?php else: ?>
      <form method="post" action="einsatz_verschieben.php" class="formcol"
            id="verschiebeform" data-dirty-track>
        <?= csrf_field() ?>
        <input type="hidden" name="id" value="<?= $mid ?>">
        <label>Neuer Diensttag
          <?php /* Ein Auswahlfeld, kein Datumsfeld: Der Zieltag ist eine
                   Kennung, und die Liste sagt, welcher Dienst dahintersteht.
                   Art, Rettungsmittel und Einsatzzahl stehen mit im Eintrag —
                   zwei Dienste eines Kalendertags sind sonst nicht
                   auseinanderzuhalten. */ ?>
          <select name="ziel" required>
            <option value="">– Diensttag wählen –</option>
            <?php foreach ($auswahl as $a): ?>
              <option value="<?= (int)$a['id'] ?>" <?= $ziel === (int)$a['id'] ? 'selected' : '' ?>>
                <?= e($a['zeichen']) ?> <?= e($a['text']) ?><?php
                  echo $a['wer'] !== '' ? ' · ' . e($a['wer']) : ' · ohne Zuordnung';
                  echo ' · ' . (int)$a['einsaetze']
                     . ((int)$a['einsaetze'] === 1 ? ' Einsatz' : ' Einsätze'); ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <button type="submit" class="btn-primary">Einsatz verschieben</button>
        <p class="login-aux"><a href="einsatz.php?id=<?= $mid ?>"
           data-cancel-form="verschiebeform"
           data-cancel-confirm="Der gewählte Diensttag geht verloren. Trotzdem abbrechen?"
           >Abbrechen</a></p>
      </form>
    <?php endif; ?>
    <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
