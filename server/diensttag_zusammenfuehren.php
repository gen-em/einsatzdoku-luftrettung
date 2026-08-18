<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/**
 * Einen anderen Diensttag in den geoeffneten aufnehmen (E10-E13, E25, A6-A8).
 *
 * DER GEOEFFNETE TAG IST IMMER DER ZIELTAG. Das ist keine Bequemlichkeit,
 * sondern der Grund, warum der Einstieg ueberhaupt hier liegt und nicht in der
 * Tagesliste (E25): Der Vorgang ist NICHT UMKEHRBAR, und bei einer Auswahl von
 * zwei Zeilen in einer Liste ist die Richtung eine Frage der Lesart. Hier ist
 * sie eine Tatsache — der Tag, der stehenbleibt, steht im Titel.
 *
 * ZWEI SCHRITTE, WEIL ES ZWEI FRAGEN SIND. Erst welcher Tag aufgenommen wird,
 * dann was bei Widerspruechen gilt. Beides in einem Formular haette entweder
 * die Widersprueche aller Kandidaten gleichzeitig zeigen muessen oder die
 * Vorschau erst nach dem Absenden — und eine Vorschau, die man nur mit dem
 * Absenden bekommt, ist keine.
 *
 * Der Papierkorb kommt hier nicht vor (E13). Was das bedeutet, steht in der
 * Rueckfrage; die Begruendung in dt_zusammenfuehren().
 */

$zielId = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$ziel   = $zielId > 0 ? dt_laden($userId, $zielId, true) : null;
if ($ziel === null) { http_response_code(404); exit('Diensttag nicht gefunden.'); }
if ($ziel['deleted_at'] !== null) {
    http_response_code(409);
    exit('Dieser Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
}

$quellId = (int)($_POST['q'] ?? $_GET['q'] ?? 0);
$fehler  = null;
$vorschau = null;
$quelle   = null;

/* Die Wahl bei Widerspruechen. Vorbelegt mit dem Zieltag: Er ist der, den die
 * Nutzerin vor Augen hat, und ein Vorschlag, der nichts aendert, ist der
 * einzige, der sich ohne Nachdenken bestaetigen laesst. */
$wahl = [];
foreach (['vehicle', 'base', 'crew'] as $feld) {
    $wahl[$feld] = (($_POST['w_' . $feld] ?? 'ziel') === 'quelle') ? 'quelle' : 'ziel';
}

if ($quellId > 0) {
    $p = dt_merge_pruefen($userId, $zielId, $quellId);
    if (!$p['ok']) {
        $fehler = $p['meldung'];
    } else {
        $quelle   = $p['quelle'];
        $vorschau = dt_merge_vorschau($userId, $p['ziel'], $quelle);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja'
    && $vorschau !== null) {
    csrf_check();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $e = dt_zusammenfuehren($pdo, $userId, $zielId, $quellId, $wahl);
        if ($e['ok']) {
            $pdo->commit();
            header('Location: index.php?d=' . $zielId . '&aufgenommen=1');
            exit;
        }
        $pdo->rollBack();
        $fehler = $e['meldung'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $fehler = 'Das Zusammenführen ist fehlgeschlagen — es wurde nichts geändert. '
                . 'Kennung: ' . fehler_kennung($ex, 'diensttag_zusammenfuehren');
    }
}

$kandidaten = $vorschau === null ? dt_merge_kandidaten($userId, $zielId) : [];

/* Ein- und Mehrzahl auseinanderhalten: „1 Einsätze" in einer Rückfrage, die zur
 * Vorsicht mahnt, liest sich wie ein Fehler. */
$anz = static fn(int $n, string $eins, string $viele): string
    => $n . ' ' . ($n === 1 ? $eins : $viele);

/** Rettungsmittel und Standort eines Tags in einer Zeile — aus den
 *  eingefrorenen Spalten (E8), nie aus den heutigen Stammdaten. */
$wer = static function (array $t): string {
    $s = [];
    if (($t['vehicle_name'] ?? null) !== null && $t['vehicle_name'] !== '') { $s[] = (string)$t['vehicle_name']; }
    if (($t['base_name']    ?? null) !== null && $t['base_name']    !== '') { $s[] = (string)$t['base_name']; }
    return $s ? implode(' · ', $s) : 'ohne Zuordnung';
};
$zielSym = dt_art_symbol($ziel['kind'] === null ? null : (string)$ziel['kind']);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Diensttag aufnehmen · Einsatzdoku</title>
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>
<div class="layout">
  <?php ui_days_sidebar($zielId); ?>
  <main class="page">
    <h1>Anderen Diensttag in den <?= e(dt_lesbar($ziel, true)) ?> aufnehmen</h1>
    <p class="muted"><span class="artzeichen" title="<?= e($zielSym['text']) ?>"
         aria-label="<?= e($zielSym['text']) ?>"><?= e($zielSym['zeichen']) ?></span>
       <?= e($wer($ziel)) ?> — dieser Diensttag <strong>bleibt</strong>.</p>

    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

<?php if ($vorschau === null): ?>
    <?php /* ---- Schritt 1: Welcher Tag wird aufgenommen? ----------------- */ ?>
    <div class="card">
      <p>Wurde die App während eines Dienstes <strong>versehentlich mehrfach
         gestartet</strong>, entstehen mehrere Diensttage für einen einzigen
         tatsächlichen Dienst. Hier lassen sie sich wieder zusammenführen.</p>
      <p class="muted">Zur Wahl stehen die Diensttage der letzten
         <?= (int)DT_NACHBARSCHAFT_TAGE ?> Tage vor und nach diesem. Liegt der
         gesuchte weiter entfernt, ist zuerst sein <strong>Datum zu
         berichtigen</strong>.</p>
    </div>

    <?php if (!$kandidaten): ?>
      <p class="alert alert-info">In der zeitlichen Nachbarschaft dieses
         Diensttags liegt kein weiterer. Es gibt nichts aufzunehmen.</p>
      <p class="login-aux"><a href="index.php?d=<?= (int)$zielId ?>">Zurück zum Diensttag</a></p>
    <?php else: ?>
      <form method="get" action="diensttag_zusammenfuehren.php" class="formcol">
        <input type="hidden" name="d" value="<?= (int)$zielId ?>">
        <table class="data">
          <thead><tr>
            <th class="c-swatch"></th>
            <th>Diensttag</th>
            <th>Rettungsmittel · Standort</th>
            <th class="c-mid">Einsätze</th>
            <th class="c-mid">Ruhe</th>
            <th class="c-mid">Uhr-Kennungen</th>
          </tr></thead>
          <tbody>
          <?php foreach ($kandidaten as $k):
                $sym = dt_art_symbol($k['kind'] === null ? null : (string)$k['kind']);
                $id  = 'k' . (int)$k['id']; ?>
            <tr class="<?= $k['vereinbar'] ? '' : 'zeile-aus' ?>">
              <td><?php if ($k['vereinbar']): ?>
                    <input type="radio" name="q" id="<?= e($id) ?>" value="<?= (int)$k['id'] ?>" required>
                  <?php else: ?>
                    <input type="radio" id="<?= e($id) ?>" disabled aria-label="nicht wählbar">
                  <?php endif; ?></td>
              <td><label for="<?= e($id) ?>">
                    <span class="artzeichen" title="<?= e($sym['text']) ?>"
                          aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
                    <?= e(dt_lesbar($k, true)) ?></label></td>
              <td><?= e($wer($k)) ?><?php if (!$k['vereinbar']): ?>
                    <br><span class="muted small">Nicht wählbar: <?= e($sym['text']) ?>,
                    der geöffnete Diensttag ist <?= e($zielSym['text']) ?>.</span>
                  <?php endif; ?></td>
              <td class="c-mid"><?= (int)$k['einsaetze'] ?></td>
              <td class="c-mid"><?= (int)$k['segmente'] ?></td>
              <td class="c-mid"><?= (int)$k['kennungen'] ?></td>
            </tr>
          <?php endforeach; ?>
          </tbody>
        </table>
        <?php /* Ein GET-Formular: Der erste Schritt aendert nichts und darf
                 deshalb auch nach dem Zurückgehen im Browser wieder erscheinen.
                 Geschrieben wird erst im zweiten Schritt, per POST. */ ?>
        <button type="submit" class="btn-primary">Weiter zur Vorschau</button>
        <p class="login-aux"><a href="index.php?d=<?= (int)$zielId ?>">Abbrechen</a></p>
      </form>
    <?php endif; ?>

<?php else: ?>
    <?php /* ---- Schritt 2: Vorschau und Rückfrage ----------------------- */
          $quellSym = dt_art_symbol($quelle['kind'] === null ? null : (string)$quelle['kind']);
          $ergSym   = dt_art_symbol($vorschau['kind']); ?>
    <div class="card">
      <p>Aufgenommen wird:</p>
      <p><span class="artzeichen" title="<?= e($quellSym['text']) ?>"
               aria-label="<?= e($quellSym['text']) ?>"><?= e($quellSym['zeichen']) ?></span>
         <strong><?= e(dt_lesbar($quelle, true)) ?></strong> — <?= e($wer($quelle)) ?></p>
      <p class="muted">Danach gibt es diesen Diensttag <strong>nicht mehr</strong>.
         Seine Einsätze, Ruhesegmente und Uhr-Kennungen hängen am Diensttag
         <?= e(dt_lesbar($ziel, true)) ?>.</p>
    </div>

    <div class="card">
      <p class="muted">Es wandern:</p>
      <ul>
        <li><?= e($anz((int)$vorschau['einsaetze'], 'Einsatz', 'Einsätze')) ?><?php
            if ($vorschau['einsaetze_papierkorb']): ?> (dazu
            <?= (int)$vorschau['einsaetze_papierkorb'] ?> im Papierkorb, die
            ebenfalls mitwandern)<?php endif; ?></li>
        <li><?= e($anz((int)$vorschau['segmente'], 'Ruhesegment', 'Ruhesegmente')) ?><?php
            if ($vorschau['segmente_papierkorb']): ?> (dazu
            <?= (int)$vorschau['segmente_papierkorb'] ?> im Papierkorb)<?php endif; ?></li>
        <li><?= e($anz((int)$vorschau['kennungen'], 'Uhr-Kennung', 'Uhr-Kennungen')) ?><?php
            if ($vorschau['kennungen']): ?> — spätere Uploads dieses Dienstes
            landen dadurch von selbst im aufnehmenden Diensttag<?php endif; ?></li>
      </ul>
      <p class="muted">Der Diensttag umfasst danach:</p>
      <ul>
        <li>Datum <strong><?= e(dt_datum_lesbar((string)$vorschau['day'])) ?></strong></li>
        <li>Dienstbeginn <strong><?= $vorschau['started_at'] !== null
              ? e(fmt_local((string)$vorschau['started_at'])) : '—' ?></strong>,
            Dienstende <strong><?= $vorschau['ended_at'] !== null
              ? e(fmt_local((string)$vorschau['ended_at'])) : '—' ?></strong>
            <?php if ($vorschau['ende_offen']): ?>
              <span class="muted small">(kein Dienstende erfasst — beide Diensttage sind offen)</span>
            <?php elseif ($vorschau['ende_geerbt']): ?>
              <span class="muted small">(nur einer der beiden Diensttage hat ein
                erfasstes Ende; es gilt für den zusammengeführten)</span>
            <?php endif; ?></li>
        <li>Art <span class="artzeichen" title="<?= e($ergSym['text']) ?>"
                     aria-label="<?= e($ergSym['text']) ?>"><?= e($ergSym['zeichen']) ?></span>
            <strong><?= e($ergSym['text']) ?></strong></li>
      </ul>
    </div>

    <form method="post" action="diensttag_zusammenfuehren.php" class="formcol"
          id="mergeform"
          data-confirm="Die beiden Diensttage werden zusammengeführt. Das lässt sich nicht rückgängig machen. Fortfahren?"
          data-confirm-ok="Zusammenführen" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <input type="hidden" name="d" value="<?= (int)$zielId ?>">
      <input type="hidden" name="q" value="<?= (int)$quellId ?>">
      <input type="hidden" name="confirm" value="ja">

      <?php if ($vorschau['wahlen']): ?>
        <div class="card">
          <p>Die beiden Diensttage <strong>widersprechen sich</strong> an diesen
             Stellen. Was soll gelten?</p>
          <?php foreach ($vorschau['wahlen'] as $feld => $w): ?>
            <fieldset class="feldgruppe">
              <legend><?= e($w['titel']) ?></legend>
              <label><input type="radio" name="w_<?= e($feld) ?>" value="ziel"
                     <?= $wahl[$feld] === 'ziel' ? 'checked' : '' ?>>
                <?= e($w['ziel']) ?>
                <span class="muted small">(<?= e(dt_lesbar($ziel, true)) ?>, bleibt)</span></label>
              <label><input type="radio" name="w_<?= e($feld) ?>" value="quelle"
                     <?= $wahl[$feld] === 'quelle' ? 'checked' : '' ?>>
                <?= e($w['quelle']) ?>
                <span class="muted small">(<?= e(dt_lesbar($quelle, true)) ?>, wird aufgenommen)</span></label>
            </fieldset>
          <?php endforeach; ?>
          <?php if (isset($vorschau['wahlen']['crew'])): ?>
            <p class="muted small">Eine Rolle, die der gewählte Satz
               <em>nicht</em> besetzt, der andere aber schon, wird von dort
               übernommen — ein eingetragener Name geht nicht verloren.</p>
          <?php endif; ?>
        </div>
      <?php endif; ?>

      <div class="card">
        <p class="muted"><strong>Notizen</strong> beider Diensttage werden
           aneinandergehängt, nichts wird überschrieben.</p>
        <p class="muted">Das Zusammenführen ist <strong>nicht umkehrbar</strong>
           und läuft <strong>nicht über den Papierkorb</strong>: Dort läge ein
           leerer Diensttag, dessen Wiederherstellung die Einsätze nicht
           zurückholen könnte — sie hängen dann am aufnehmenden Tag.</p>
      </div>

      <button type="submit" class="btn-red">Diensttag aufnehmen</button>
      <p class="login-aux">
        <a href="diensttag_zusammenfuehren.php?d=<?= (int)$zielId ?>">Anderen Diensttag wählen</a>
        · <a href="index.php?d=<?= (int)$zielId ?>">Abbrechen</a></p>
    </form>
<?php endif; ?>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/forms.js') ?>"></script>
</body>
</html>
