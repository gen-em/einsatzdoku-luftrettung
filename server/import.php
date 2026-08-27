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
    <?php ui_titelzeile(['titel' => 'Import / Export']); ?>
    <p class="seiten-erklaerung">Übernimmt eine vorhandene Einsatzliste (Excel oder
       CSV) in dieses Konto. Die Datei wird <strong>nicht hochgeladen</strong> — sie
       wird in deinem Browser gelesen, geprüft und dort verschlüsselt. Der Server
       erhält Name, Geburtsdatum, Diagnose und Einsatzort nur als Chiffretext.</p>

    <div id="lockwarn" hidden>
      <?php ui_meldung(
          'Die geschützten Angaben lassen sich gerade nicht verschlüsseln — die '
        . 'Verschlüsselung ist in dieser Sitzung gesperrt. Ohne sie ist kein Import '
        . 'möglich.', null, 'warn', '      ',
          ['knopf' => ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                                'typ' => 'button', 'attr' => ' id="lockwarn_unlock"'])]); ?>
    </div>
    <div id="fehler" hidden></div>

    <?php /* DREI SCHRITTE, DREI KARTEN (E-P3-35). Schritt 2 und 3 sind
             verborgen, bis der vorige getan ist — die Schrittfolge steht damit
             als Zahl im Kartenkopf und nicht als Überschrift im Fließtext. */ ?>
    <?php ui_karte_start(['titel' => '1. Datei wählen']); ?>
      <?php ui_feld(['label' => 'Datei', 'id' => 'datei', 'art' => 'file',
                     'klein' => 'Excel (.xlsx, .xls, .ods), CSV oder ein Archiv aus dem '
                              . 'CSV-Export (.zip) — die Tabelle darin wird von selbst '
                              . 'gefunden. Ist das Archiv mit einem Passwort geschützt, '
                              . 'wird danach gefragt.',
                     'attr' => ' accept=".xlsx,.xls,.csv,.ods,.zip"']); ?>

      <div class="feld">
        <label class="feld-label" for="profil">Format</label>
        <select class="feld-eingabe" id="profil"></select>
      </div>
      <div id="profilwarnung" hidden></div>

      <div id="params"></div>

      <div class="listen-form-felder">
        <div class="feld">
          <label class="feld-label" for="vehsel">Rettungsmittel für neue Diensttage</label>
          <select class="feld-eingabe" id="vehsel">
            <option value="">–</option>
            <?php foreach ($SD_VEHICLES as $v): $sym = dt_art_symbol((string)$v['kind']); ?>
              <option value="<?= (int)$v['id'] ?>"
                <?= (int)$v['id'] === $DEF_VEHICLE ? 'selected' : '' ?>>
                <?= e($v['name']) ?><?php
                  echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="feld">
          <label class="feld-label" for="basesel">Standort für neue Diensttage</label>
          <select class="feld-eingabe" id="basesel">
            <option value="">–</option>
            <?php foreach ($SD_BASES as $b): ?>
              <option value="<?= (int)$b['id'] ?>"
                <?= (int)$b['id'] === $DEF_BASE ? 'selected' : '' ?>><?= e($b['name']) ?><?php
                echo !empty($b['zentral']) ? ' (systemweit)' : ''; ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <p class="feld-klein">Beides gilt nur für Diensttage, die der Import neu anlegt;
         bestehende bleiben unangetastet und lassen sich später je Diensttag ändern.
         Ein Import legt <strong>je Kalendertag höchstens einen</strong> Diensttag an
         und ordnet alle Einsätze dieses Datums ihm zu — mehrere Dienste an einem Tag
         lassen sich aus einer Tabelle nicht ableiten; wer sie braucht, teilt sie
         danach über „Aktionen → Verschieben" auf.</p>
    <?php ui_karte_ende(); ?>

    <div id="schritt2" hidden>
      <?php ui_karte_start(['titel' => '2. Prüfen und korrigieren']); ?>
        <p class="feld-hinweis" id="bilanz"></p>
        <?php /* Die Filterwahl als Segment: drei Zustände, von denen genau
                 einer gilt — dasselbe Muster wie die Artenwahl im Zeitraum. */ ?>
        <?php ui_segment(['name' => 'impfilter', 'id' => 'impfilter', 'wert' => 'alle',
                          'label' => 'Zeilen filtern',
                          'optionen' => ['alle' => 'Alle Zeilen',
                                         'probleme' => 'Nur Probleme',
                                         'dubletten' => 'Nur Dubletten']]); ?>
        <p class="feld-klein">Gelb = Hinweis, Rot = Fehler. Zellen sind direkt
           änderbar; nach jeder Änderung wird die Zeile neu geprüft. Fehlerhafte
           Zeilen blockieren nur sich selbst — entweder korrigieren oder
           überspringen.</p>
        <div class="tabelle-scroll"><table class="imp-table" id="tabelle"></table></div>
      <?php ui_karte_ende(); ?>
    </div>

    <div id="schritt3" hidden>
      <?php ui_karte_start(['titel' => '3. Übernehmen']); ?>
        <p class="feld-hinweis" id="bereit"></p>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Import ausführen', 'art' => 'primaer',
                        'typ' => 'button', 'attr' => ' id="commit" disabled']) ?>
        </div>
        <div id="commitstate" class="zustandszeile"></div>
      <?php ui_karte_ende(); ?>
    </div>

    <?php ui_karte_start(['titel' => 'Export', 'id' => 'exportform']); ?>
      <p class="feld-hinweis">Erzeugt eine Datei aus den vorhandenen Einsätzen dieses
         Kontos — zum Weiterverarbeiten in anderen Programmen. Der Aufbau der Datei
         passiert vollständig <strong>in deinem Browser</strong>.</p>
      <div class="feld">
        <span class="feld-label">Zeitraum</span>
        <?php ui_segment(['name' => 'exp_zr', 'wert' => 'range',
                          'label' => 'Zeitraum des Exports',
                          'optionen' => ['range' => 'Von–Bis', 'all' => 'Alles']]); ?>
      </div>
      <?php /* Eigene Kennung, damit assets/export.js bei „Alles" die ganze
               Zeile ausblenden kann statt nur die Felder auszugrauen
               (A6.3). */ ?>
      <div id="exp_zeitraum_row" class="listen-form-felder">
        <?php ui_feld(['label' => 'Von', 'id' => 'exp_von', 'art' => 'date']); ?>
        <?php ui_feld(['label' => 'Bis', 'id' => 'exp_bis', 'art' => 'date']); ?>
      </div>

      <div class="feld">
        <label class="feld-label" for="exp_fmt">Format</label>
        <select class="feld-eingabe" id="exp_fmt">
          <option value="b">CSV (Standard)</option>
          <option value="a" selected>Excel (Standard)</option>
          <option value="c">Excel (GuteSeele)</option>
        </select>
      </div>
      <div id="exp_gpx_row" hidden>
        <?php ui_schalter(['id' => 'exp_gpx', 'name' => 'exp_gpx', 'an' => true,
                           'label' => 'GPX-Tracks einschließen']); ?>
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
      <?php ui_schalter(['id' => 'exp_pat', 'name' => 'exp_pat',
                         'label' => 'Personenbezogene Angaben einschließen']); ?>
      <div id="exp_pat_hint" hidden>
        <?php ui_meldung(
            'Gesperrt — geschützte Angaben lassen sich gerade nicht entschlüsseln. '
          . 'Ein Export ohne personenbezogene Angaben bleibt möglich.', null, 'warn', '        ',
            ['knopf' => ui_knopf(['text' => 'Entsperren', 'art' => 'neutral', 'typ' => 'button',
                                  'klasse' => 'unlockbtn', 'attr' => ' id="exp_pat_unlock"'])]); ?>
      </div>

      <?php /* Vorbelegt auf AN (A6.4, Web 5.7.0). In dieser Datei stehen die
               geschützten Angaben im Klartext; der Schutz ist der Normalfall,
               nicht die Ausnahme. Abwählen bleibt eine Handlung, Anwählen war
               vorher eine — die Vorbelegung dreht nur um, welche der beiden
               man bewusst treffen muss. */ ?>
      <?php ui_schalter(['id' => 'exp_pw', 'name' => 'exp_pw', 'an' => true,
                         'label' => 'Mit Passwort schützen (AES-256)']); ?>
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
      <div id="exp_pw_fields" hidden>
        <?php ui_feld(['label' => 'Passwort', 'id' => 'exp_pw1', 'art' => 'password',
                       'klein' => 'Mindestens 10 Zeichen.',
                       'attr' => ' minlength="10" autocomplete="new-password"']); ?>
        <span class="pwstaerke" id="exp_pw_guete"></span>
        <?php ui_feld(['label' => 'Passwort wiederholen', 'id' => 'exp_pw2',
                       'art' => 'password', 'attr' => ' autocomplete="new-password"']); ?>
      </div>
      <p class="muted">Das Passwort wird nirgends gespeichert und lässt sich nicht
         zurücksetzen. Geht es verloren, lässt sich die Datei nicht mehr öffnen —
         die Daten darin sind dann endgültig nicht mehr lesbar. Zum Öffnen wird
         zusätzlich 7-Zip (Windows) oder Keka bzw. The Unarchiver (macOS)
         benötigt; der Windows-Explorer kann solche Archive nicht öffnen.</p>

      <button type="button" class="btn-primary" id="exp_go">Export erstellen</button>
      <div id="exp_state" class="zustandszeile"></div>
    <?php ui_karte_ende(); ?>

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
