<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';

/**
 * Das Datum eines Einsatztages aendern (A5.3, Auftragspunkt 12).
 *
 * Der Anwendungsfall ist die FALSCH GESTELLTE UHR: Dabei sind Datum und
 * Uhrzeit gemeinsam falsch, und die Zeitstempel ziehen deshalb mit (E3). Wer
 * nur einen einzelnen Einsatz umhaengen will, dessen Uhrzeiten stimmen, ist
 * bei einsatz_verschieben.php richtig.
 *
 * Die Seite verlangt zwei Dinge, bevor sie handelt: ein freies Zieldatum (E2)
 * und eine Bestaetigung, die beziffert, was betroffen ist.
 */

$tag = (string)($_POST['day'] ?? $_GET['day'] ?? '');
if (pruef_kalendertag($tag, 'day') === null) {
    http_response_code(400); exit('Ungültiges Datum.');
}

$zustand = tz_tag_zustand($userId, $tag);
if (!$zustand['vorhanden'] && $zustand['einsaetze'] === 0 && $zustand['segmente'] === 0) {
    http_response_code(404); exit('Für diesen Tag ist nichts eingetragen.');
}
if ($zustand['im_papierkorb']) {
    http_response_code(409);
    exit('Dieser Flugtag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
}

$umfang = tz_tag_umfang($userId, $tag);
$fehler = null;
$ziel   = (string)($_POST['ziel'] ?? '');

/* BELEGTE DATEN, BEVOR ABGESCHICKT WIRD (Web 5.10.0).
 *
 * Die Kollisionsprüfung (E2) sass bisher allein in tz_tag_datum_aendern() —
 * also HINTER dem Absenden und hinter der Rückfrage „Alle Zeitstempel wandern
 * mit. Fortfahren?". Wer sie bejahte, bekam als Antwort, dass gar nichts
 * geschehen ist. Die Auskunft ist beim Server vorhanden, sie kam nur zu spät.
 *
 * Die Liste nennt jedes belegte Datum, den Papierkorb eingeschlossen: `days`
 * trägt `UNIQUE KEY uq_user_day (user_id, day)`, ein gelöschter Tag belegt sein
 * Datum weiterhin. Ebenso Daten, an denen Einsätze oder Ruhesegmente ohne
 * eigene `days`-Zeile liegen — tz_tag_zustand() zählt beides, und beides führt
 * zur Ablehnung.
 *
 * Der Server bleibt maßgeblich. Diese Liste ist eine Auskunft, keine Schranke:
 * Sie ist auf 400 Einträge gedeckelt und veraltet in dem Augenblick, in dem in
 * einem zweiten Fenster etwas angelegt wird. Deshalb ändert sie am Ablauf
 * nichts — geprüft wird weiterhin dort, wo geschrieben wird.
 */
$bq = db()->prepare('SELECT day FROM (
                       SELECT day FROM days          WHERE user_id = ?
                       UNION SELECT day FROM missions      WHERE user_id = ?
                       UNION SELECT day FROM rest_segments WHERE user_id = ?
                     ) t ORDER BY day DESC LIMIT 400');
$bq->execute([$userId, $userId, $userId]);
$belegt = $bq->fetchAll(PDO::FETCH_COLUMN);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja') {
    csrf_check();
    if (pruef_kalendertag($ziel, 'Zieldatum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
    } else {
        $e = tz_tag_datum_aendern($userId, $tag, $ziel);
        if ($e['ok']) {
            header('Location: index.php?day=' . urlencode($ziel) . '&umdatiert=1');
            exit;
        }
        $fehler = $e['meldung'];
    }
}
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Datum des Flugtags ändern · Einsatzdoku</title>
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>
<div class="layout">
  <?php ui_days_sidebar($tag); ?>
  <main class="page">
    <h1>Datum des Flugtags <?= e(tz_datum_lesbar($tag)) ?> ändern</h1>
    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

    <div class="card">
      <p>Diese Handlung ist für den Fall gedacht, dass <strong>die Uhr falsch
         gestellt war</strong>. Dann sind Datum und Uhrzeit gemeinsam falsch —
         deshalb <strong>wandern alle Zeitstempel mit</strong>, um dieselbe
         Zahl von Tagen. Die abgelesenen Uhrzeiten bleiben dabei stehen.</p>
      <p class="muted">Soll <em>nur ein einzelner Einsatz</em> zu einem anderen
         Tag gehören, seine Uhrzeiten aber stimmen, ist das ein anderer Fall:
         Auf der Einsatzseite gibt es dafür <strong>Aktionen →
         Verschieben</strong>.</p>
    </div>

    <div class="card">
      <?php // Ein- und Mehrzahl auseinanderhalten: „1 Einsätze" in einer
            // Rückfrage, die zur Vorsicht mahnt, liest sich wie ein Fehler.
            $anz = fn(int $n, string $eins, string $viele): string
                => $n . ' ' . ($n === 1 ? $eins : $viele); ?>
      <p class="muted">Betroffen sind:</p>
      <ul>
        <li><?= e($anz((int)$umfang['einsaetze'], 'Einsatz', 'Einsätze')) ?><?php
            if ($umfang['einsaetze_papierkorb']): ?> (dazu
            <?= (int)$umfang['einsaetze_papierkorb'] ?> im Papierkorb, die
            ebenfalls mitwandern)<?php endif; ?></li>
        <li><?= e($anz((int)$umfang['segmente'], 'Ruhesegment', 'Ruhesegmente')) ?><?php
            if ($umfang['segmente_papierkorb']): ?> (dazu
            <?= (int)$umfang['segmente_papierkorb'] ?> im Papierkorb)<?php endif; ?></li>
        <li><?= number_format((int)$umfang['punkte'], 0, ',', '.') ?> GPS-Trackpunkte</li>
        <li>Phasenzeiten und Reanimations-Protokolle der genannten Einsätze</li>
      </ul>
      <p class="muted">Alles davon wird <strong>gemeinsam</strong> geändert oder
         gar nicht. Liegt am Zieldatum bereits ein Einsatztag, wird die Änderung
         abgelehnt — zusammengeführt wird nicht. Ob das gewählte Datum frei ist,
         steht unter dem Feld.</p>
    </div>

    <form method="post" action="flugtag_datum.php" class="formcol"
          id="datumform" data-dirty-track
          data-confirm="Das Datum dieses Flugtags wird geändert. Alle Zeitstempel wandern mit. Fortfahren?"
          data-confirm-ok="Datum ändern" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <input type="hidden" name="day" value="<?= e($tag) ?>">
      <input type="hidden" name="confirm" value="ja">
      <label>Richtiges Datum
        <input type="date" name="ziel" id="zielfeld" required
               value="<?= e($ziel !== '' ? $ziel : $tag) ?>">
      </label>
      <p id="zielinfo" class="muted zielinfo" hidden></p>
      <button type="submit" class="btn-red">Datum ändern</button>
      <p class="login-aux"><a href="index.php?day=<?= e($tag) ?>"
         data-cancel-form="datumform"
         data-cancel-confirm="Das gewählte Datum geht verloren. Trotzdem abbrechen?"
         >Abbrechen</a></p>
    </form>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script>
/* Auskunft zum Zieldatum — siehe den Kommentar oben im PHP-Teil. Rein
   anzeigend: Der Knopf bleibt bedienbar, weil diese Liste veralten kann und
   die Entscheidung beim Server liegt. Sie nimmt der Ablehnung nur die
   Überraschung. */
(function () {
  const BELEGT = new Set(<?= json_encode($belegt, JSON_UNESCAPED_UNICODE) ?>);
  const VOLL   = <?= count($belegt) >= 400 ? 'true' : 'false' ?>;
  const AELTER = <?= json_encode($belegt ? min($belegt) : null) ?>;
  const ALTTAG = <?= json_encode($tag) ?>;
  const feld = document.getElementById('zielfeld');
  const box  = document.getElementById('zielinfo');
  if (!feld || !box) { return; }

  function de(iso) {
    const t = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
    return t ? `${t[3]}.${t[2]}.${t[1]}` : iso;
  }

  function zeige() {
    const v = feld.value;
    box.className = 'muted zielinfo';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) { box.hidden = true; return; }
    box.hidden = false;

    if (v === ALTTAG) {
      box.textContent = 'Das ist das bisherige Datum — es gäbe nichts zu ändern.';
      return;
    }
    if (BELEGT.has(v)) {
      box.className = 'zielinfo alert alert-warn';
      box.textContent = 'Am ' + de(v) + ' liegt bereits ein Einsatztag (oder es liegen '
                      + 'dort Einsätze bzw. Ruhesegmente — auch im Papierkorb belegt ein '
                      + 'Tag sein Datum weiter). Zwei Einsatztage lassen sich nicht '
                      + 'zusammenführen: Die Umdatierung würde abgelehnt, ohne etwas zu '
                      + 'ändern. Bitte ein freies Datum wählen oder den vorhandenen Tag '
                      + 'zuerst auflösen.';
      return;
    }
    if (VOLL && AELTER !== null && v < AELTER) {
      box.textContent = 'Für dieses Datum liegt hier keine Auskunft vor — die Prüfung '
                      + 'reicht nur bis zum ' + de(AELTER) + ' zurück. Liegt dort doch '
                      + 'ein Einsatztag, wird die Umdatierung abgelehnt, ohne etwas zu '
                      + 'ändern.';
      return;
    }
    box.textContent = 'Am ' + de(v) + ' ist nichts eingetragen — das Datum ist frei.';
  }

  feld.addEventListener('input', zeige);
  feld.addEventListener('change', zeige);
  zeige();
})();
</script>
</body>
</html>
