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
         abgelehnt — zusammengeführt wird nicht.</p>
    </div>

    <form method="post" action="flugtag_datum.php" class="formcol"
          id="datumform" data-dirty-track
          data-confirm="Das Datum dieses Flugtags wird geändert. Alle Zeitstempel wandern mit. Fortfahren?"
          data-confirm-ok="Datum ändern" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <input type="hidden" name="day" value="<?= e($tag) ?>">
      <input type="hidden" name="confirm" value="ja">
      <label>Richtiges Datum
        <input type="date" name="ziel" required
               value="<?= e($ziel !== '' ? $ziel : $tag) ?>">
      </label>
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
</body>
</html>
