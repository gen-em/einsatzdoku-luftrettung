<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';

/**
 * Einen Einsatz einem anderen Flugtag zuordnen (A5.2, Auftragspunkt 13).
 *
 * Eigene Seite statt eines stillen Freischaltens des Datumsfeldes im Formular
 * (E4): Die Nebenwirkung — der Einsatz wechselt die Tageszugehoerigkeit — waere
 * an einem plötzlich beschreibbaren Feld nicht zu sehen. Eine bewusste,
 * benannte Handlung ist einem editierbaren Feld vorzuziehen.
 *
 * Die UHRZEITEN BLEIBEN. Wer die Uhrzeiten mitverschieben will, meint einen
 * anderen Fall — die falsch gestellte Uhr — und ist bei „Datum des Tages
 * ändern" richtig (flugtag_datum.php). Die Seite sagt das ausdruecklich.
 */

$mid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

$mq = db()->prepare('SELECT id, day, started_at, ended_at
                     FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
$mq->execute([$mid, $userId]);                                       // Datentrennung!
$mission = $mq->fetch();
if (!$mission) { http_response_code(404); exit('Einsatz nicht gefunden.'); }

$tag     = (string)$mission['day'];
$meldung = null;
$fehler  = null;
$ziel    = (string)($_POST['ziel'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (pruef_kalendertag($ziel, 'Zieldatum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
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

/* Vorschlagsliste: die Tage, die es schon gibt — plus der Vor- und Folgetag des
   Einsatzes, weil das der haeufigste Fall ist (ein Dienst ueber Mitternacht).
   Die Liste ist ein Angebot, kein Zwang: Das Feld nimmt jedes Datum an, und
   ein fehlender Tag wird beim Verschieben angelegt (E14). */
$tq = db()->prepare('SELECT day FROM days WHERE user_id = ? AND deleted_at IS NULL
                     ORDER BY day DESC LIMIT 400');
$tq->execute([$userId]);
$vorhandeneTage = $tq->fetchAll(PDO::FETCH_COLUMN);
$nachbarn = [date('Y-m-d', strtotime($tag . ' -1 day')),
             date('Y-m-d', strtotime($tag . ' +1 day'))];
$vorschlaege = array_values(array_unique(array_merge($nachbarn, $vorhandeneTage)));
sort($vorschlaege);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Einsatz verschieben · Einsatzdoku</title>
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>
<div class="layout">
  <?php ui_days_sidebar($tag); ?>
  <main class="page">
    <h1>Einsatz verschieben</h1>
    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

    <div class="card">
      <p>Der Einsatz vom <strong><?= e(tz_datum_lesbar($tag)) ?></strong>,
         <strong><?= e(fmt_local($mission['started_at'])) ?>
         bis <?= e(fmt_local($mission['ended_at'])) ?></strong> Uhr,
         wird einem anderen Flugtag zugeordnet.</p>
      <p class="muted">Die <strong>Uhrzeiten bleiben unverändert</strong> — es
         ändert sich allein, zu welchem Tag der Einsatz gehört. Sollen auch die
         Zeiten mitwandern, weil die Uhr falsch gestellt war, ist
         <a href="flugtag_datum.php?day=<?= e($tag) ?>">Datum des Flugtags
         ändern</a> der richtige Weg.</p>
      <p class="muted">Existiert am Zieldatum noch kein Flugtag, wird einer
         angelegt — mit deiner Standard-Vorbelegung für Standort und Maschine.
         Ein späterer Upload derselben Uhr zieht den Einsatz nicht zurück.</p>
    </div>

    <form method="post" action="einsatz_verschieben.php" class="formcol"
          id="verschiebeform" data-dirty-track>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $mid ?>">
      <label>Neuer Flugtag
        <input type="date" name="ziel" required list="dl_tage"
               value="<?= e($ziel !== '' ? $ziel : $nachbarn[1]) ?>">
      </label>
      <datalist id="dl_tage">
        <?php foreach ($vorschlaege as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?>
      </datalist>
      <button type="submit" class="btn-primary">Einsatz verschieben</button>
      <p class="login-aux"><a href="einsatz.php?id=<?= $mid ?>"
         data-cancel-form="verschiebeform"
         data-cancel-confirm="Das gewählte Datum geht verloren. Trotzdem abbrechen?"
         >Abbrechen</a></p>
    </form>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/forms.js') ?>"></script>
</body>
</html>
