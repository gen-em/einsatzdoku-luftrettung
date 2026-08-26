<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

/**
 * Import bestehender Einsatzlisten (Excel/CSV/ZIP) und Export.
 *
 * Eigene Seite, erscheint aber ueber ui_leiste_einstellungen() als Eintrag der
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

/* Stammdaten fuer die Vorbelegung neu angelegter Diensttage (wie index.php).
 * Dieselbe Menge, die dt_zuordnen() beim Speichern annimmt: eigene und
 * ausgewaehlte zentrale Standorte samt ihren Rettungsmitteln (E16). */
require_once __DIR__ . '/diensttag_lib.php';
$SD_BASES    = dt_bases($userId);
$SD_VEHICLES = dt_vehicles($userId);
$SD_DEFAULTS = dt_standardwerte($userId);
$DEF_VEHICLE = (int)($SD_DEFAULTS['vehicle_id'] ?? 0);
$DEF_BASE    = (int)($SD_DEFAULTS['base_id'] ?? 0);
ui_seite_start(['titel' => 'Import / Export']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'import']); ?>
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

      <label>Rettungsmittel für neu angelegte Diensttage
        <select id="vehsel">
          <option value="">–</option>
          <?php foreach ($SD_VEHICLES as $v): $sym = dt_art_symbol((string)$v['kind']); ?>
            <option value="<?= (int)$v['id'] ?>"
              <?= (int)$v['id'] === $DEF_VEHICLE ? 'selected' : '' ?>>
              <?= e($v['name']) ?><?php
                echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
          <?php endforeach; ?>
        </select></label>

      <label>Standort für neu angelegte Diensttage
        <select id="basesel">
          <option value="">–</option>
          <?php foreach ($SD_BASES as $b): ?>
            <option value="<?= (int)$b['id'] ?>"
              <?= (int)$b['id'] === $DEF_BASE ? 'selected' : '' ?>><?= e($b['name']) ?><?php
              echo !empty($b['zentral']) ? ' (zentral)' : ''; ?></option>
          <?php endforeach; ?>
        </select></label>
      <p class="muted">Gilt nur für Diensttage, die der Import neu anlegt. Bestehende
         bleiben unangetastet, und beides lässt sich später je Diensttag in der
         Übersicht ändern.</p>
      <p class="muted">Ein Import legt <strong>je Kalendertag höchstens einen</strong>
         Diensttag neu an und ordnet alle Einsätze dieses Datums ihm zu. Mehrere
         Dienste an einem Tag lassen sich aus einer Tabelle nicht ableiten — wer sie
         braucht, teilt sie danach mit „Aktionen → Verschieben" auf.</p>
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
      <?php /* Eigene Kennung, damit assets/export.js bei „Alles" die ganze
               Zeile ausblenden kann statt nur die Felder auszugrauen
               (A6.3). */ ?>
      <p id="exp_zeitraum_row">
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
      <?php /* Tritt an die Stelle der GPX-Wahl, wenn die Schranke greift
               (A9, Web 5.8.0). Ein Track endet am Einsatzort — er nennt
               ihn genauer als jede Koordinatenspalte. Das gilt bodengebunden
               wie luftgebunden. */ ?>
      <p class="muted" id="exp_gpx_pers_hint" hidden>Ohne personenbezogene Angaben
         entfallen die GPX-Tracks — ein Track endet am Einsatzort.</p>

      <?php /* „Personenbezogene Angaben" statt „Patientendaten" (A9, Web
               5.8.0). Der Haken schaltet seit dieser Fassung auch die Namen
               der Besatzung, bw_info, den anderen Notarzt, die Notizen und
               die Koordinaten des Einsatzortes ab. Die Kennung exp_pat bleibt:
               Sie ist der Vertrag zu assets/export.js und
               api/export_data.php. */ ?>
      <div class="rolechecks">
        <label><input type="checkbox" id="exp_pat"> Personenbezogene Angaben einschließen</label>
      </div>
      <p class="muted" id="exp_pat_hint" hidden>Gesperrt — geschützte Angaben lassen sich
         gerade nicht entschlüsseln. Export ohne personenbezogene Angaben bleibt möglich.
         <button type="button" class="btn-plain unlockbtn" id="exp_pat_unlock">Entsperren</button></p>

      <?php /* Vorbelegt auf AN (A6.4, Web 5.7.0). In dieser Datei stehen die
               geschützten Angaben im Klartext; der Schutz ist der Normalfall,
               nicht die Ausnahme. Abwählen bleibt eine Handlung, Anwählen war
               vorher eine — die Vorbelegung dreht nur um, welche der beiden
               man bewusst treffen muss. */ ?>
      <div class="rolechecks">
        <label><input type="checkbox" id="exp_pw" checked> Mit Passwort schützen (AES-256)</label>
      </div>
      <?php /* Erscheint nur ohne personenbezogene Angaben. Kein selbsttätiges
               Abschalten (E31) — die Entscheidung von A6.4 bleibt, ihre
               Begründung hat sich mit A9 geändert.

               BIS WEB 5.7.0 stand hier: "Personenbezogen ist sie trotzdem" —
               denn die Schranke deckte nur die Patientendaten ab, Besatzung
               und Phasenkoordinaten gingen mit. Seit A9 stimmt das nicht mehr,
               und ein Hinweis, der etwas Falsches behauptet, ist schlimmer als
               keiner: Wer ihm glaubt, hält eine harmlose Datei für brisant —
               und beim nächsten Mal eine brisante für harmlos.

               Der Schutz bleibt trotzdem vorbelegt. Was in der Datei bleibt,
               ist Betriebswissen: wann geflogen wurde, wohin transportiert,
               mit welchen Rettungsmitteln, mit welchem Reanimationsverlauf.
               Kein Personenbezug, aber auch nichts, was ohne Weiteres in
               fremde Hände gehört. */ ?>
      <p class="muted small" id="exp_pw_hint" hidden>Ohne personenbezogene Angaben
         enthält die Datei keine Namen, keine Notizen und keine Koordinaten des
         Einsatzortes. <strong>Betriebsangaben bleiben enthalten:</strong>
         Einsatzzeiten, Transportziele, weitere Rettungsmittel und der Verlauf
         einer Reanimation. Der Passwortschutz lässt sich abwählen — eine
         bewusste Entscheidung sollte es bleiben.</p>
      <div class="rolechecks" id="exp_pw_fields" hidden>
        <label>Passwort (mind. 10 Zeichen)
          <input type="password" id="exp_pw1" minlength="10" autocomplete="new-password"></label>
        <span class="pwquality" id="exp_pw_guete"></span>
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
    <?php ui_krypto_bootstrap(['csrf' => true, 'einzug' => '    ']); ?>
    <script src="<?= asset('assets/html.js') ?>"></script>
    <?php /* Passwortguete fuer das Archivpasswort des Exports (B9, M2-03). */ ?>
    <script src="<?= asset('assets/pwquality.js') ?>"></script>
    <script src="<?= asset('assets/patient.js') ?>"></script>
    <?php /* ROLLENKATALOG FUER DIE SKRIPTE (E4).
             Er muss VOR import_profiles.js und import.js stehen: Beide leiten
             ihre Spaltenlisten beim Laden daraus ab. export.js und import_ui.js
             folgen weiter unten und sehen ihn dadurch ebenfalls.

             Die Quelle ist CREW_ROLES in server/db.php — nicht eine zweite
             Liste im Browser, die damit auseinanderlaufen könnte. */ ?>
    <script>
      const CREW_ROLLEN = <?= json_encode(array_keys(CREW_ROLES)) ?>;
      const CREW_LABELS = <?= json_encode(array_map(
              static fn(array $r): string => $r['label'], CREW_ROLES),
              JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="<?= asset('assets/import_profiles.js') ?>"></script>
    <script src="<?= asset('assets/import.js') ?>"></script>
    <script>
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

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
