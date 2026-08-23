<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Das Datum eines Diensttages aendern (A5.3, Auftragspunkt 12).
 *
 * Der Anwendungsfall ist die FALSCH GESTELLTE UHR: Dabei sind Datum und
 * Uhrzeit gemeinsam falsch, und die Zeitstempel ziehen deshalb mit (E3). Wer
 * nur einen einzelnen Einsatz umhaengen will, dessen Uhrzeiten stimmen, ist
 * bei einsatz_verschieben.php richtig.
 *
 * KEINE KOLLISIONSPRUEFUNG MEHR (Web 6.0.0). Bis dahin verlangte die Seite ein
 * FREIES Zieldatum und fuehrte dafuer eine eigene Liste belegter Daten mit —
 * die Folge des Tagesschluessels `UNIQUE KEY uq_user_day`. Seit E9 sind mehrere
 * Diensttage je Kalendertag der vorgesehene Fall (A1); es gibt nichts mehr zu
 * kollidieren, und die Liste ist ersatzlos entfallen. Geblieben ist die
 * Rueckfrage, die beziffert, was betroffen ist.
 */

$dayId = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$tag   = $dayId > 0 ? dt_laden($userId, $dayId, true) : null;
if ($tag === null) {
    http_response_code(404); exit('Diensttag nicht gefunden.');
}
if ($tag['deleted_at'] !== null) {
    http_response_code(409);
    exit('Dieser Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
}

$umfang = tz_tag_umfang($userId, $dayId);
$fehler = null;
$ziel   = (string)($_POST['ziel'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja') {
    csrf_check();
    if (pruef_kalendertag($ziel, 'Zieldatum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
    } else {
        $e = tz_tag_datum_aendern($userId, $dayId, $ziel);
        if ($e['ok']) {
            header('Location: index.php?d=' . (int)$dayId . '&umdatiert=1');
            exit;
        }
        $fehler = $e['meldung'];
    }
}
ui_seite_start(['titel' => 'Datum des Diensttags ändern']);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar($dayId); ?>
  <main class="page">
    <h1>Datum des Diensttags <?= e(dt_lesbar($tag, true)) ?> ändern</h1>
    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

    <div class="card">
      <p>Diese Handlung ist für den Fall gedacht, dass <strong>die Uhr falsch
         gestellt war</strong>. Dann sind Datum und Uhrzeit gemeinsam falsch —
         deshalb <strong>wandern alle Zeitstempel mit</strong>, um dieselbe
         Zahl von Tagen. Die abgelesenen Uhrzeiten bleiben dabei stehen.</p>
      <p class="muted">Soll <em>nur ein einzelner Einsatz</em> zu einem anderen
         Diensttag gehören, seine Uhrzeiten aber stimmen, ist das ein anderer
         Fall: Auf der Einsatzseite gibt es dafür <strong>Aktionen →
         Verschieben</strong>.</p>
      <p class="muted">Ein belegtes Zieldatum ist <strong>kein Hindernis</strong>:
         Mehrere Diensttage an einem Kalendertag sind zulässig. Sie stehen danach
         in der Leiste links untereinander, unterschieden durch die Uhrzeit des
         Dienstbeginns.</p>
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
        <li>Start- und Endzeit des Diensttags selbst</li>
      </ul>
      <p class="muted">Alles davon wird <strong>gemeinsam</strong> geändert oder
         gar nicht.</p>
    </div>

    <form method="post" action="diensttag_datum.php" class="formcol"
          id="datumform" data-dirty-track
          data-confirm="Das Datum dieses Diensttags wird geändert. Alle Zeitstempel wandern mit. Fortfahren?"
          data-confirm-ok="Datum ändern" data-confirm-tone="danger">
      <?= csrf_field() ?>
      <input type="hidden" name="d" value="<?= (int)$dayId ?>">
      <input type="hidden" name="confirm" value="ja">
      <label>Richtiges Datum
        <input type="date" name="ziel" id="zielfeld" required
               value="<?= e($ziel !== '' ? $ziel : (string)$tag['day']) ?>">
      </label>
      <button type="submit" class="btn-red">Datum ändern</button>
      <p class="login-aux"><a href="index.php?d=<?= (int)$dayId ?>"
         data-cancel-form="datumform"
         data-cancel-confirm="Das gewählte Datum geht verloren. Trotzdem abbrechen?"
         >Abbrechen</a></p>
    </form>
    <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
