<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

/**
 * Import bestehender Einsatzlisten (Excel/CSV/ZIP) und Export.
 *
 * Eigene Seite, erscheint aber ueber ui_settings_sidebar() als Eintrag der
 * Einstellungen — dasselbe Muster wie admin_stammdaten.php. Grund fuer die
 * eigene Datei statt eines weiteren Zweigs in einstellungen.php: Die
 * Review-Tabelle bringt eine Menge Markup und Logik mit; in einer Datei mit
 * bereits ueber tausend Zeilen waere das nicht mehr zu ueberblicken.
 *
 * Diese Seite enthaelt bewusst KEINE Verarbeitungslogik. Das Lesen der Datei,
 * das Pruefen und das Verschluesseln passieren im Browser (assets/import.js,
 * assets/import_ui.js). Der Server bekommt Patientendaten nur als
 * Chiffretext zu sehen — deshalb kann es hier auch keinen Datei-Upload geben.
 *
 * Seit Web 2.10.0 sitzt darunter der Exportblock (assets/export.js). Er folgt
 * derselben Regel in die andere Richtung: api/export_data.php liefert nur
 * Rohdaten, der gesamte Dateiaufbau samt Entschluesselung passiert im Browser.
 * Die Feldlisten aller Formate stehen in docs/Export-Format.md.
 */

// Stammdaten fuer die Vorbelegung neu angelegter Flugtage (wie index.php)
$SD_BASES = db()->prepare('SELECT id, name FROM bases
                           WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
$SD_BASES->execute([$userId]); $SD_BASES = $SD_BASES->fetchAll();
$SD_AC = db()->prepare('SELECT id, registration FROM aircraft
                        WHERE (user_id = ? OR user_id IS NULL) ORDER BY registration');
$SD_AC->execute([$userId]); $SD_AC = $SD_AC->fetchAll();

$DEF_AC = 0; $DEF_BASE = 0;
$defs = db()->prepare('SELECT kind, item_id FROM user_defaults WHERE user_id = ?');
$defs->execute([$userId]);
foreach ($defs->fetchAll() as $d) {
    if ($d['kind'] === 'base')     { $DEF_BASE = (int)$d['item_id']; }
    if ($d['kind'] === 'aircraft') { $DEF_AC   = (int)$d['item_id']; }
}
?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Import / Export — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<div class="layout">
  <?php ui_settings_sidebar('import'); ?>

  <main class="page">
    <h1>Import / Export</h1>

    <p class="muted">Übernimmt eine vorhandene Einsatzliste (Excel oder CSV) in
       dieses Konto. Die Datei wird <strong>nicht hochgeladen</strong> — sie wird in
       deinem Browser gelesen, geprüft und dort verschlüsselt. Der Server erhält
       Name, Geburtsdatum, Diagnose und Einsatzort ausschließlich als Chiffretext.</p>

    <div id="lockwarn" class="alert" hidden>Die geschützten Angaben lassen sich gerade
      nicht verschlüsseln — die Verschlüsselung ist in dieser Sitzung gesperrt. Ohne
      sie ist kein Import möglich.
      <button type="button" class="btn-plain unlockbtn" id="lockwarn_unlock">Entsperren</button></div>
    <div id="fehler" class="alert" hidden></div>

    <!-- ---------------------------------------------------------------- 1 -->
    <h2>1. Datei wählen</h2>
    <div class="settings-form">
      <label>Datei (.xlsx, .xls, .csv, .zip)
        <input type="file" id="datei" accept=".xlsx,.xls,.csv,.ods,.zip"></label>
      <p class="muted">Ein Archiv aus dem CSV-Export (.zip) kann direkt gewählt
         werden — die Tabelle darin wird von selbst gefunden. Ist das Archiv mit
         einem Passwort geschützt, wird danach gefragt.</p>

      <label>Format
        <select id="profil"></select></label>
      <p class="alert" id="profilwarnung" hidden></p>

      <div id="params"></div>

      <label>Maschine für neu angelegte Flugtage
        <select id="acsel">
          <option value="">–</option>
          <?php foreach ($SD_AC as $a): ?>
            <option value="<?= (int)$a['id'] ?>"
              <?= (int)$a['id'] === $DEF_AC ? 'selected' : '' ?>><?= e($a['registration']) ?></option>
          <?php endforeach; ?>
        </select></label>

      <label>Basis für neu angelegte Flugtage
        <select id="basesel">
          <option value="">–</option>
          <?php foreach ($SD_BASES as $b): ?>
            <option value="<?= (int)$b['id'] ?>"
              <?= (int)$b['id'] === $DEF_BASE ? 'selected' : '' ?>><?= e($b['name']) ?></option>
          <?php endforeach; ?>
        </select></label>
      <p class="muted">Gilt nur für Flugtage, die es noch nicht gibt. Bestehende Tage
         bleiben unangetastet, und beides lässt sich später je Tag in der Übersicht ändern.</p>
    </div>

    <!-- ---------------------------------------------------------------- 2 -->
    <div id="schritt2" hidden>
      <h2>2. Prüfen und korrigieren</h2>
      <p class="muted" id="bilanz"></p>
      <p>
        <button type="button" class="btn-plain" data-filter="alle">Alle Zeilen</button>
        <button type="button" class="btn-plain" data-filter="probleme">Nur Probleme</button>
        <button type="button" class="btn-plain" data-filter="dubletten">Nur Dubletten</button>
      </p>
      <p class="muted">Gelb = Hinweis, Rot = Fehler. Zellen sind direkt änderbar;
         nach jeder Änderung wird die Zeile neu geprüft. Fehlerhafte Zeilen blockieren
         nur sich selbst — entweder korrigieren oder überspringen.</p>
      <div class="imp-wrap"><table class="imp-table" id="tabelle"></table></div>
    </div>

    <!-- ---------------------------------------------------------------- 3 -->
    <div id="schritt3" hidden>
      <h2>3. Übernehmen</h2>
      <p class="muted" id="bereit"></p>
      <button type="button" class="btn-primary" id="commit" disabled>Import ausführen</button>
      <p class="muted" id="commitstate" style="min-height:1.3em"></p>
    </div>

    <!-- --------------------------------------------------------- Export -->
    <h2>Export</h2>
    <p class="muted">Erzeugt eine Datei aus den vorhandenen Einsätzen dieses Kontos —
       zum Weiterverarbeiten in anderen Programmen. Der Aufbau der Datei passiert
       vollständig <strong>in deinem Browser</strong>.</p>

    <div class="settings-form" id="exportform">
      <div class="rolechecks">
        <span class="rolechecks-hint">Zeitraum:</span>
        <label><input type="radio" name="exp_zr" value="range" checked> Von–Bis</label>
        <label><input type="radio" name="exp_zr" value="all"> Alles</label>
      </div>
      <p>
        <label>Von <input type="date" id="exp_von"></label>
        <label>Bis <input type="date" id="exp_bis"></label>
      </p>

      <label>Format
        <select id="exp_fmt">
          <option value="b">CSV (Standard)</option>
          <option value="a" selected>Excel (Standard)</option>
          <option value="c">Excel (GuteSeele)</option>
        </select></label>
      <div class="rolechecks" id="exp_gpx_row" hidden>
        <label><input type="checkbox" id="exp_gpx" checked> GPX-Tracks einschließen</label>
      </div>

      <div class="rolechecks">
        <label><input type="checkbox" id="exp_pat"> Patientendaten einschließen</label>
      </div>
      <p class="muted" id="exp_pat_hint" hidden>Gesperrt — geschützte Angaben lassen sich
         gerade nicht entschlüsseln. Export ohne Patientendaten bleibt möglich.
         <button type="button" class="btn-plain unlockbtn" id="exp_pat_unlock">Entsperren</button></p>

      <div class="rolechecks">
        <label><input type="checkbox" id="exp_pw"> Mit Passwort schützen (AES-256)</label>
      </div>
      <div class="rolechecks" id="exp_pw_fields" hidden>
        <label>Passwort (mind. 8 Zeichen)
          <input type="password" id="exp_pw1" minlength="8" autocomplete="new-password"></label>
        <label>Passwort wiederholen
          <input type="password" id="exp_pw2" autocomplete="new-password"></label>
      </div>
      <p class="muted">Das Passwort wird nirgends gespeichert und lässt sich nicht
         zurücksetzen. Geht es verloren, lässt sich die Datei nicht mehr öffnen —
         die Daten darin sind dann endgültig nicht mehr lesbar. Zum Öffnen wird
         zusätzlich 7-Zip (Windows) oder Keka bzw. The Unarchiver (macOS)
         benötigt; der Windows-Explorer kann solche Archive nicht öffnen.</p>

      <button type="button" class="btn-primary" id="exp_go">Export erstellen</button>
      <p class="muted" id="exp_state" style="min-height:1.3em"></p>
    </div>

    <script src="<?= asset('assets/vendor/xlsx.full.min.js') ?>"></script>
    <script src="<?= asset('assets/vendor/zipjs.min.js') ?>"></script>
    <script src="<?= asset('assets/crypto.js') ?>"></script>
    <script src="<?= asset('assets/keyguard.js') ?>"></script>
    <script src="<?= asset('assets/unlock.js') ?>"></script>
    <script src="<?= asset('assets/patient.js') ?>"></script>
    <script src="<?= asset('assets/import_profiles.js') ?>"></script>
    <script src="<?= asset('assets/import.js') ?>"></script>
    <script>
      const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
      const KDF_SALT = <?= json_encode($kdfSalt) ?>;
      const CSRF = <?= json_encode($_SESSION['csrf'] ?? '') ?>;
      const APP_TZ = <?= json_encode($CFG['app']['timezone']) ?>;
      const WEB_VERSION = <?= json_encode(WEB_VERSION) ?>;
      // Kennung des Kontos fuer den Exportdateinamen (export.js). Beide Werte
      // stammen aus auth_guard.php; die Bereinigung zu einem
      // dateisystemsicheren Segment passiert im Browser.
      const KONTO_NAME = <?= json_encode($userName ?? '') ?>;
      const KONTO_MAIL = <?= json_encode($userEmail ?? '') ?>;
    </script>
    <script src="<?= asset('assets/import_ui.js') ?>"></script>
    <script src="<?= asset('assets/export.js') ?>"></script>

  <?php ui_footer(); ?>
  </main>
</div>
</body>
</html>
