<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

/**
 * Import bestehender Einsatzlisten (Excel/CSV/ZIP) und Export.
 *
 * Eigene Seite, erscheint aber ueber ui_leiste_einstellungen() als Eintrag der
 * Einstellungen. Grund fuer die
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
    <?php /* DER UNTERTITEL IST DIE ABGRENZUNG (Backlog Nr. 119). Der
             Menuepunkt heisst „Import / Export" und verspricht damit alle Wege
             fuer Daten hinein und hinaus. Tatsaechlich traegt er zwei von
             acht: die Einsatzliste in beide Richtungen. Backup und GPS-Spuren
             liegen anderswo — und stehen bis Web 19.3.0 auf dieser Seite
             nirgends, auch nicht als Verweis. Der Name laesst sich nicht
             enger fassen, ohne den Export zu verstecken („Einsatzliste" sagt
             nicht, dass man dort eine Datei herausbekommt); also grenzt ein
             Satz ihn ein, und die Karte am Seitenende nennt den Rest.
             Vorbild Zeichen fuer Zeichen: admin_sicherungen.php (B-S8-08). */ ?>
    <?php ui_titelzeile([
        'titel' => 'Import / Export',
        'unter' => 'Die Einsatzliste als Ganzes — herein und hinaus. Das '
                 . 'vollständige Backup und einzelne GPS-Aufzeichnungen laufen '
                 . 'über andere Wege; welche das sind, steht unten unter '
                 . '„Was hier gilt".',
    ]); ?>
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
    <?php ui_karte_start(['titel' => '1. Datei wählen', 'id' => 'k-datei']); ?>
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
      <?php ui_karte_start(['titel' => '2. Prüfen und korrigieren', 'id' => 'k-pruefen']); ?>
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
        <div class="tabelle-scroll"><table class="tabelle" id="tabelle"></table></div>
      <?php ui_karte_ende(); ?>
    </div>

    <div id="schritt3" hidden>
      <?php ui_karte_start(['titel' => '3. Übernehmen', 'id' => 'k-uebernehmen']); ?>
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
      <p class="feld-hinweis" id="exp_gpx_pers_hint" hidden>Ohne personenbezogene Angaben
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
      <p class="feld-hinweis" id="exp_pw_hint" hidden>Ohne personenbezogene Angaben
         enthält die Datei keine Namen, keine Notizen und keine Koordinaten des
         Einsatzortes. <strong>Betriebsangaben bleiben enthalten:</strong>
         Einsatzzeiten, Transportziele, weitere Rettungsmittel und der Verlauf
         einer Reanimation. Der Passwortschutz lässt sich abwählen — eine
         bewusste Entscheidung sollte es bleiben.</p>
      <div id="exp_pw_fields" hidden>
        <?php ui_feld(['label' => 'Passwort', 'id' => 'exp_pw1', 'art' => 'password',
                       'klein' => 'Mindestens ' . PW_MIN_LAENGE . ' Zeichen.',
                       'attr' => ' minlength="' . PW_MIN_LAENGE . '" autocomplete="new-password"']); ?>
        <span class="pwstaerke" id="exp_pw_guete"></span>
        <?php ui_feld(['label' => 'Passwort wiederholen', 'id' => 'exp_pw2',
                       'art' => 'password', 'attr' => ' autocomplete="new-password"']); ?>
      </div>
      <p class="feld-hinweis">Das Passwort wird nirgends gespeichert und lässt sich nicht
         zurücksetzen. Geht es verloren, lässt sich die Datei nicht mehr öffnen —
         die Daten darin sind dann endgültig nicht mehr lesbar. Zum Öffnen wird
         zusätzlich 7-Zip (Windows) oder Keka bzw. The Unarchiver (macOS)
         benötigt; der Windows-Explorer kann solche Archive nicht öffnen.</p>

      <?php /* DER EINE KNOPF, DEN O8c UEBERSEHEN HAT (F-P3-BA). Er trug
               `btn-primary` — eine Klasse, die es seit dem Neubau des
               Stylesheets (Web 9.0.0) nicht mehr gibt. Gemessen: 23 px hoch,
               ohne Flaeche, ohne Rahmen, ohne Radius, in der Textschrift; der
               Nachbar `#commit` daneben ist 44 px, orange, Bricolage. Die
               Kennung `exp_go` bleibt — assets/export.js haengt daran. */ ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Export erstellen', 'art' => 'primaer',
                      'typ' => 'button', 'attr' => ' id="exp_go"']) ?>
      </div>
      <div id="exp_state" class="zustandszeile"></div>
    <?php ui_karte_ende(); ?>

    <?php /* DIE SEITE NENNT DIE ANDEREN WEGE (Backlog Nr. 119). Vorher stand
             in dieser Datei kein einziger Verweis — gemessen null. Eine Seite,
             die „Import / Export" heisst und sechs von acht Wegen weder traegt
             noch erwaehnt, schickt jede Suche ins Leere.

             Die Bauform ist nicht neu: eine zugeklappte Karte „Was hier gilt"
             als letzte der Seite, wie R74 Regel 5 es vorschreibt und wie sie
             auf neun anderen Seiten steht. Zugeklappt, weil import.php bei
             360 px ohnehin die laengste Seite der Anwendung ist.

             DIE ADRESSEN STEHEN NICHT ZWEIMAL: `einstellungen.php?t=backup`
             kommt aus ui_einstellungen_punkte() (ui.php) — von Hand
             abgeschrieben waere er beim naechsten Umbau an zwei Stellen
             falsch. Genau dieser Fehler war Backlog Nr. 151.

             UND DER GPS-SATZ VERSPRICHT KEINE SEITE, SONDERN EINEN DIENSTTAG:
             Ein Konto ohne Diensttag sieht auf der Tagesuebersicht gar kein
             Aktionsmenue (index.php, `hidden`). „Auf der Tagesuebersicht,
             am Diensttag, den du ansiehst" haelt auch dann. */ ?>
    <?php
      $punkte  = ui_einstellungen_punkte();
      $backupZ = 'einstellungen.php?t=backup';
      foreach ($punkte[0]['punkte'] as $pk) {
          if ($pk[0] === 'backup') { $backupZ = $pk[1]; break; }
      }
    ?>
    <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                          'vorschau' => 'Backup · GPS-Daten']); ?>
      <p class="feld-hinweis"><strong>Ein Export ist kein Backup.</strong> Die
      Datei hier ist zum Weiterverarbeiten in anderen Programmen gedacht: Sie
      trägt die Einsatzliste, aber keine GPS-Daten, keine Stammdaten und keine
      Einstellungen. Ein vollständiges Backup deines Kontos — alles in einer
      verschlüsselten <code>.edbak</code>-Datei — gibt es unter
      <a href="<?= ui_e($backupZ) ?>">Backup</a>.</p>

      <p class="feld-hinweis"><strong>Ein Backup einspielen</strong> geht
      ebenfalls dort und nicht hier. Der Unterschied zählt: Diese Seite
      <em>ergänzt</em> Einsätze aus einer fremden Liste, ein Backup stellt
      deinen eigenen Stand wieder her.</p>

      <p class="feld-hinweis"><strong>Eine GPS-Aufzeichnung als GPX einlesen</strong>
      läuft auf der <a href="index.php">Tagesübersicht</a> — am Diensttag, den
      du gerade ansiehst, über <strong>Aktionen</strong> (auf schmalen Geräten
      „&#183;&#183;&#183;") <strong>→ GPX importieren</strong>. Der Ort ist
      Absicht: Eine Aufzeichnung gehört immer zu <em>einem</em> Diensttag, und welcher
      das ist, weiß nur die Tagesübersicht. Solange das Konto noch keinen
      Diensttag hat, steht dieses Menü nicht zur Verfügung.</p>

      <p class="feld-hinweis"><strong>GPS-Daten hinaus</strong> gibt es an
      denselben beiden Stellen wie die Einsätze selbst: auf der
      <a href="index.php">Tagesübersicht</a> unter <strong>Aktionen →
      GPS-Daten als GPX</strong> für den ganzen Diensttag, und auf der Seite
      eines Einsatzes unter demselben Eintrag für dessen Aufzeichnung allein.</p>
    <?php ui_karte_ende(true); ?>

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
      const CREW_ROLLEN = <?= json_js(array_keys(CREW_ROLES)) ?>;
      const CREW_LABELS = <?= json_js(array_map(
              static fn(array $r): string => $r['label'], CREW_ROLES),
              JSON_UNESCAPED_UNICODE) ?>;
    </script>
    <script src="<?= asset('assets/import_profiles.js') ?>"></script>
    <script src="<?= asset('assets/import.js') ?>"></script>
    <script>
      const APP_TZ = <?= json_js($CFG['app']['timezone']) ?>;
      const WEB_VERSION = <?= json_js(WEB_VERSION) ?>;
      // Kennung des Kontos fuer den Exportdateinamen (export.js). Beide Werte
      // stammen aus auth_guard.php; die Bereinigung zu einem
      // dateisystemsicheren Segment passiert im Browser.
      const KONTO_NAME = <?= json_js($userName ?? '') ?>;
      const KONTO_MAIL = <?= json_js($userEmail ?? '') ?>;
    </script>
    <script src="<?= asset('assets/import_ui.js') ?>"></script>
    <script src="<?= asset('assets/export.js') ?>"></script>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
