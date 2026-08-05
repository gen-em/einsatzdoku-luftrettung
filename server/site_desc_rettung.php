<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

/**
 * VORÜBERGEHENDE SEITE — entfaellt zusammen mit der Spalte missions.site_desc.
 *
 * Mit Web 3.3.0 ist die Beschreibung des Einsatzortes in den verschluesselten
 * pat_blob umgezogen. Die alten Werte stehen weiterhin im Klartext in der
 * Spalte missions.site_desc; sie koennen dort nicht bleiben, aber auch nicht
 * automatisch umziehen: pat_blob entsteht ausschliesslich im Browser, der
 * Server kennt den Inhaltsschluessel nicht (docs/Technik.md).
 *
 * Deshalb dieser Weg: eine Textdatei zum Herunterladen, aus der sich die Werte
 * von Hand in die Einsaetze nachtragen lassen. Kein automatischer Umzug, keine
 * Loeschmigration — die Spalte bleibt bestehen, bis das Nachtragen bestaetigt
 * ist. Erst dann werden Spalte, diese Datei, der Leisteneintrag in ui.php und
 * die Hilfsfunktion site_desc_rest_vorhanden() zusammen entfernt.
 *
 * Datentrennung nach user_id in JEDER Abfrage dieser Datei.
 */

$st = db()->prepare(
    "SELECT id, day, started_at, site_desc
       FROM missions
      WHERE user_id = ? AND deleted_at IS NULL
        AND site_desc IS NOT NULL AND site_desc <> ''
      ORDER BY started_at");
$st->execute([$userId]);                                   // Datentrennung!
$rows = $st->fetchAll(PDO::FETCH_ASSOC);

/* ---- Download ---------------------------------------------------------- */
if (($_GET['download'] ?? '') === '1' && $rows) {
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="beschreibung-einsatzort-'
           . date('Y-m-d') . '.txt"');
    echo "Beschreibung Einsatzort — Klartextbestand vor Web 3.3.0\n";
    echo "Erzeugt am " . date('d.m.Y H:i') . ", " . count($rows) . " Einsätze.\n";
    echo "Spalten: Einsatzdatum | Beginn (Ortszeit) | interne Einsatz-Nr | Text\n";
    echo str_repeat('-', 72) . "\n";
    foreach ($rows as $r) {
        // Einsatz-Nr ist die interne id — nur zur Zuordnung beim Nachtragen,
        // sie steht in der Adresse der Einsatzansicht (einsatz.php?id=…).
        echo implode(' | ', [
            date('d.m.Y', strtotime((string)$r['day'])),
            fmt_local($r['started_at']),                    // Zeitzone aus der Konfiguration
            '#' . (int)$r['id'],
            str_replace(["\r", "\n"], ' ', (string)$r['site_desc']),
        ]) . "\n";
    }
    exit;
}
?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Beschreibungen sichern — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<div class="layout">
  <?php ui_settings_sidebar('site_desc'); ?>

  <main class="page page-narrow">
    <h1>Beschreibungen sichern</h1>

    <?php if (!$rows): ?>
      <div class="alert alert-ok">Es sind keine Beschreibungen im Klartext mehr
        vorhanden. Diese Seite wird nicht mehr gebraucht.</div>
      <p class="muted"><a href="einstellungen.php?t=profil">Zurück zu den Einstellungen</a></p>
    <?php else: ?>
      <p>Die Angabe <strong>Beschreibung Einsatzort</strong> steht seit dieser
         Version im verschlüsselten Block des Einsatzes. Für die
         <strong><?= count($rows) ?></strong> Einsätze aus der Zeit davor liegt
         der Text noch unverschlüsselt in der Datenbank.</p>

      <p>Ein automatischer Umzug ist nicht möglich: Der verschlüsselte Block
         entsteht ausschließlich im Browser, der Server kann nichts hineinschreiben.
         Die Textdatei enthält deshalb alle Altwerte mit Datum, Beginn und interner
         Einsatznummer, damit sie sich im Formular von Hand nachtragen lassen.</p>

      <p><a class="btn-primary" href="site_desc_rettung.php?download=1">Textdatei herunterladen</a></p>

      <div class="alert alert-info">Die alten Werte verschwinden durch das
        Nachtragen <strong>nicht</strong> von selbst — die Datenbankspalte bleibt
        vorerst bestehen und wird erst in einer späteren Auslieferung entfernt.
        Bis dahin bleibt diese Seite erreichbar.</div>

      <h2>Vorschau</h2>
      <table class="imp-table">
        <tr><th>Datum</th><th>Beginn</th><th>Nr.</th><th>Beschreibung</th></tr>
        <?php foreach (array_slice($rows, 0, 20) as $r): ?>
          <tr>
            <td class="mono"><?= e(date('d.m.Y', strtotime((string)$r['day']))) ?></td>
            <td class="mono"><?= e(fmt_local($r['started_at'])) ?></td>
            <td class="mono"><a href="einsatz.php?id=<?= (int)$r['id'] ?>">#<?= (int)$r['id'] ?></a></td>
            <td><?= e((string)$r['site_desc']) ?></td>
          </tr>
        <?php endforeach; ?>
      </table>
      <?php if (count($rows) > 20): ?>
        <p class="muted">… und <?= count($rows) - 20 ?> weitere in der Textdatei.</p>
      <?php endif; ?>
    <?php endif; ?>

  <?php ui_footer(); ?>
  </main>
</div>
</body>
</html>
