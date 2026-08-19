<?php
declare(strict_types=1);
/**
 * Gemeinsame Layout-Bausteine (Topbar, Einsatztage-Leiste, Fusszeile).
 * Voraussetzung: auth_guard.php ist geladen ($userId, $userEmail, $userName)
 * samt ist_admin() — die eine Rollenpruefung (M1-15).
 */

function ui_user_label(): string {
    global $userName, $userEmail;
    return ($userName !== null && $userName !== '') ? $userName : (string)$userEmail;
}

/** Kopfleiste: Vogel-Icon + Titel + Name; Menü Übersicht / Suche / ⚙ */
function ui_topbar(string $active): void { ?>
<header class="topbar">
  <a class="brand" href="index.php">
    <img src="<?= asset('assets/images/gen-em_logo_helicopter_weiss.svg') ?>" alt="">
    <span>Einsatzdokumentation Notarzt – <?= e(ui_user_label()) ?></span>
  </a>
  <nav class="mainnav">
    <a href="index.php" <?= $active === 'uebersicht' ? 'class="active"' : '' ?>>Übersicht</a>
    <a href="suche.php" <?= $active === 'suche' ? 'class="active"' : '' ?>>Suche</a>
    <a class="gearlink <?= $active === 'einstellungen' ? 'active' : '' ?>"
       href="einstellungen.php?t=profil" title="Einstellungen" aria-label="Einstellungen">
      <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" fill="currentColor" aria-hidden="true">
        <path d="M19.14 12.94c.04-.3.06-.61.06-.94 0-.32-.02-.64-.07-.94l2.03-1.58c.18-.14.23-.41.12-.61l-1.92-3.32c-.12-.22-.37-.29-.59-.22l-2.39.96c-.5-.38-1.03-.7-1.62-.94l-.36-2.54c-.04-.24-.24-.41-.48-.41h-3.84c-.24 0-.43.17-.47.41l-.36 2.54c-.59.24-1.13.57-1.62.94l-2.39-.96c-.22-.08-.47 0-.59.22L2.74 8.87c-.12.21-.08.47.12.61l2.03 1.58c-.05.3-.09.63-.09.94s.02.64.07.94l-2.03 1.58c-.18.14-.23.41-.12.61l1.92 3.32c.12.22.37.29.59.22l2.39-.96c.5.38 1.03.7 1.62.94l.36 2.54c.05.24.24.41.48.41h3.84c.24 0 .44-.17.47-.41l.36-2.54c.59-.24 1.13-.56 1.62-.94l2.39.96c.22.08.47 0 .59-.22l1.92-3.32c.12-.22.07-.47-.12-.61l-2.01-1.58zM12 15.6c-1.98 0-3.6-1.62-3.6-3.6s1.62-3.6 3.6-3.6 3.6 1.62 3.6 3.6-1.62 3.6-3.6 3.6z"/>
        <circle cx="12" cy="12" r="1.9"/>
      </svg></a>
  </nav>
</header>
<?php }

/**
 * Untermenue der Einstellungen — identisch auf einstellungen.php, admin_users.php,
 * admin_user.php und admin_stammdaten.php. Die Administration (eigener,
 * abgesetzter Block) erscheint nur fuer Admins.
 *
 * $active: profil | standorte | rettungsmittel | backup | import | geraete
 *          | admin | admin_standorte | admin_rettungsmittel | admin_sicherungen
 *          | wartung
 *
 * ZWEI EINTRAEGE STATT EINEM (Web 7.0.0). „Standortdaten" hiess der Punkt, an
 * dem sich alles sammelte: Standorte, Rettungsmittel, Besatzungen, Zielkliniken,
 * Bergwacht. Der Name war irrefuehrend — Standortdaten sind die Daten EINES
 * Standorts, hier standen aber die Standorte selbst und ihr gesamter Inhalt.
 *
 * Jetzt trennt der Schnitt danach, was man tut:
 *   Standorte       Standorte anlegen und auswaehlen — und sonst nichts.
 *   Rettungsmittel  was an den ausgewaehlten Standorten haengt.
 * Dieselbe Zweiteilung gilt in der Administration fuer die systemweiten
 * Eintraege.
 */
function ui_settings_sidebar(string $active): void {
    $items = [
        'profil'         => ['einstellungen.php?t=profil', 'Profil'],
        'standorte'      => ['einstellungen.php?t=standorte', 'Standorte'],
        'rettungsmittel' => ['einstellungen.php?t=rettungsmittel', 'Rettungsmittel'],
        'backup'         => ['einstellungen.php?t=backup', 'Backup'],
        // Eigene Seite (import.php), erscheint aber als Eintrag der
        // Einstellungen — wie admin_stammdaten.php.
        'import'         => ['import.php', 'Import / Export'],
        'geraete'        => ['einstellungen.php?t=geraete', 'Geräte'],
    ];
    ?>
  <aside class="daylist">
    <h2>Einstellungen</h2>
    <ul>
      <?php foreach ($items as $key => [$href, $label]): ?>
        <li><a href="<?= $href ?>" <?= $active === $key ? 'class="active"' : '' ?>><?= $label ?></a></li>
      <?php endforeach; ?>
      <li><a href="logout.php" data-confirm="Wirklich abmelden?" data-confirm-ok="Abmelden"
             data-confirm-tone="normal">Abmelden</a></li>
    </ul>
    <?php if (ist_admin()): ?>
      <h2 class="sidebar-subhead">Administration</h2>
      <ul>
        <li><a href="admin_users.php" <?= $active === 'admin' ? 'class="active"' : '' ?>>NutzerInnenverwaltung</a></li>
        <?php /* Aufgeteilt wie in der Kontoansicht (Web 7.0.0): erst die
                 Standorte, dann was an ihnen haengt. „Zentrale Stammdaten" war
                 EIN Eintrag fuer sechs Datenarten. */ ?>
        <li><a href="admin_stammdaten.php?t=standorte" <?= $active === 'admin_standorte' ? 'class="active"' : '' ?>>Standorte systemweit</a></li>
        <li><a href="admin_stammdaten.php?t=rettungsmittel" <?= $active === 'admin_rettungsmittel' ? 'class="active"' : '' ?>>Rettungsmittel systemweit</a></li>
        <li><a href="admin_sicherungen.php" <?= $active === 'admin_sicherungen' ? 'class="active"' : '' ?>>Sicherungen</a></li>
        <?php /* Wartung war bis Web 4.5.2 nur ueber die direkte Adresse
           erreichbar. Das machte die Auskunft aus M3-05 wertlos: Sie meldet,
           dass der Aufraeumjob dauerhaft scheitert — auf einer Seite, die
           niemand oeffnet, meldet sie das niemandem.

           Der Eintrag ist gefahrlos: Die Seite fuehrt beim blossen Aufrufen
           NICHTS aus (Abnahmekriterium A19), sie zeigt nur an, was anstuende. */ ?>
        <li><a href="update.php" <?= $active === 'wartung' ? 'class="active"' : '' ?>>Wartung</a></li>
      </ul>
    <?php endif; ?>
  </aside>
<?php }

/**
 * Diensttage-Leiste (serverseitig, auf allen Inhaltsseiten identisch).
 *
 * SIE LISTET JETZT DIENSTTAGE, NICHT KALENDERTAGE (E9, Web 6.0.0). Bis dahin
 * entstand die Liste aus den vorkommenden DATEN in drei Tabellen — sie musste
 * es, weil ein Flugtag ohne eigene Zeile Einsaetze haben konnte. Seit
 * `missions.day_id` ein Fremdschluessel ist, gibt es das nicht mehr: Jeder
 * Einsatz haengt an einer Zeile in `days`, und diese Zeile IST der Eintrag.
 *
 * Zwei Dienste an einem Kalendertag stehen deshalb als zwei Zeilen
 * untereinander. Auseinandergehalten werden sie durch die Uhrzeit des
 * Dienstbeginns — aber nur DANN: Im Regelfall, ein Dienst am Tag, kostet sie
 * nur Breite in einer Leiste, die auf schmalen Geraeten ohnehin knapp ist.
 *
 * Das Symbol der Art (E27, A7c) steht am Anfang der Zeile mit einer
 * Textalternative in `title` und `aria-label` — die Auskunft haengt nicht an
 * der Grafik.
 */
function ui_days_sidebar(?int $currentDayId): void {
    global $userId;
    require_once __DIR__ . '/diensttag_lib.php';
    $tage = dt_liste($userId, 500);

    // Nach Jahr -> Monat gruppieren (je Y => M => [Diensttage]), Reihenfolge
    // bleibt absteigend, da $tage bereits absteigend sortiert aus der DB kommt.
    $monatsnamen = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
        'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
    $baum = [];
    foreach ($tage as $t) {
        $d = (string)$t['day'];
        $baum[substr($d, 0, 4)][substr($d, 5, 2)][] = $t;
    }

    // Welches Jahr/Monat soll offen sein? Der aktuell gewaehlte Diensttag hat
    // Vorrang, sonst der juengste vorhandene (oberstes Jahr/oberster Monat).
    $aktuellesDatum = null;
    foreach ($tage as $t) {
        if ($currentDayId !== null && (int)$t['id'] === $currentDayId) {
            $aktuellesDatum = (string)$t['day'];
            break;
        }
    }
    $offenesJahr = null; $offenerMonat = null;
    if ($aktuellesDatum !== null) {
        $offenesJahr  = substr($aktuellesDatum, 0, 4);
        $offenerMonat = substr($aktuellesDatum, 5, 2);
    } elseif ($tage) {
        $offenesJahr  = substr((string)$tage[0]['day'], 0, 4);
        $offenerMonat = substr((string)$tage[0]['day'], 5, 2);
    }
    ?>
<aside class="daylist">
  <h2>Diensttage</h2>
  <div class="dayyears">
    <?php if (!$baum): ?><p class="muted daylist-empty">noch keine</p><?php endif; ?>
    <?php foreach ($baum as $jahr => $monate):
        // Achtung: PHP macht aus numerischen Array-Schluesseln Integer
        // ("2026" -> 2026, "12" -> 12, "07" bleibt String). Deshalb ueberall
        // ausdruecklich nach String wandeln — sonst bricht e() unter
        // strict_types ab und Monatsvergleiche schlagen ab Oktober fehl.
        $jahrS = (string)$jahr; ?>
      <details class="yearblock" <?= $jahrS === $offenesJahr ? 'open' : '' ?>>
        <summary><a class="zeitlink" href="zeitraum.php?y=<?= e($jahrS) ?>"><?= e($jahrS) ?></a></summary>
        <?php foreach ($monate as $monat => $monatsTage):
            $monatS = str_pad((string)$monat, 2, '0', STR_PAD_LEFT); ?>
          <details class="monthblock"
                    <?= ($jahrS === $offenesJahr && $monatS === $offenerMonat) ? 'open' : '' ?>>
            <summary><a class="zeitlink"
                        href="zeitraum.php?y=<?= e($jahrS) ?>&amp;m=<?= e($monatS) ?>"><?= e($monatsnamen[(int)$monatS]) ?></a></summary>
            <ul>
              <?php foreach ($monatsTage as $t):
                  $sym = dt_art_symbol($t['kind'] === null ? null : (string)$t['kind']);
                  $titel = $sym['text'];
                  if ($t['vehicle_name'] !== null && $t['vehicle_name'] !== '') {
                      $titel = (string)$t['vehicle_name'] . ' — ' . $sym['text'];
                  } ?>
                <li><a href="index.php?d=<?= (int)$t['id'] ?>"
                       <?= (int)$t['id'] === $currentDayId ? 'class="active"' : '' ?>><span
                       class="artzeichen" title="<?= e($titel) ?>"
                       aria-label="<?= e($titel) ?>"><?= e($sym['zeichen']) ?></span>
                       <?= e(dt_lesbar($t, (bool)$t['mehrfach'])) ?></a></li>
              <?php endforeach; ?>
            </ul>
          </details>
        <?php endforeach; ?>
      </details>
    <?php endforeach; ?>
  </div>
    <?php
      require_once __DIR__ . '/trash_lib.php';
      $trashLeer = !trash_list_days($userId) && !trash_list_missions($userId);
      require_once __DIR__ . '/nachbearbeitung_lib.php';
      $nbOffen = nb_offen_gesamt($userId);
    ?>
    <?php /* Die Nachbearbeitung erscheint NUR, solange etwas offen ist (E24,
             A12). Ein dauerhafter Eintrag fuer eine einmalige Aufgabe waere
             genau der Hinweis, den man nicht loswird. */ ?>
    <?php if ($nbOffen > 0): ?>
      <a class="dayadd nachbearbeitung" href="nachbearbeitung.php"
         title="Zuordnungen nachtragen">
        Zuordnung offen (<?= (int)$nbOffen ?>)
      </a>
    <?php endif; ?>
    <a class="dayadd" href="diensttag_neu.php" title="Diensttag von Hand anlegen">
      + Diensttag anlegen
    </a>
    <a class="trashlink<?= $trashLeer ? ' leer' : '' ?>" href="papierkorb.php"
       title="<?= $trashLeer ? 'Papierkorb ist leer' : 'Papierkorb' ?>">
      <!-- viewBox auf die Zeichnung zugeschnitten (x 4-20, y 3-21): Ohne den
           Leerraum entspricht die CSS-Hoehe direkt der sichtbaren Groesse. -->
      <svg viewBox="4 3 16 18" xmlns="http://www.w3.org/2000/svg"
           fill="currentColor" aria-hidden="true">
        <path d="M9 3h6l1 1h4v2H4V4h4l1-1zM6 7h12l-1 13.1c-.06.5-.5.9-1 .9H8c-.5 0-.94-.4-1-.9L6 7zm3.5 2.6v9h1.6v-9H9.5zm3.4 0v9h1.6v-9h-1.6z"/>
      </svg>
      <span>Papierkorb</span>
    </a>

</aside>
<?php }

/**
 * Markup eines ORTSFELDES — Bezeichnung plus optionale Koordinaten (E37/E39).
 *
 * Gegenstueck zu assets/ortsfeld.js: Diese Funktion erzeugt die Elemente, das
 * Skript belebt sie. Beide bilden die Kennungen aus demselben PRAEFIX; wer eine
 * siebte Verwendung braucht, schreibt einen Aufruf hier und ein
 * EdOrtsfeld.init() dort — und nicht wieder 250 Zeilen (Vorpruefung V8).
 *
 * ZWEI FORMEN, gesteuert ueber 'feld':
 *
 *   feld = true   Vollstaendiges Widget mit eigener Beschriftung und
 *                 Textfeld. So steht es im Einsatzformular.
 *   feld = false  NUR das Zubehoer — Suchfeld, Vorschlagsliste, Zustandszeile,
 *                 Chip und die versteckten Koordinatenfelder. Das
 *                 Bezeichnungsfeld existiert dann bereits und traegt lediglich
 *                 die Kennung `<praefix>addr`. Gebraucht in den
 *                 Stammdatenformularen: Dort ist der Name ein gewachsenes
 *                 Eingabefeld einer Flex-Zeile, und es einzufassen haette das
 *                 Layout gebrochen, ohne etwas zu gewinnen.
 *
 * Schluessel:
 *   praefix     Pflicht. Bildet `<p>addr`, `<p>such`, `<p>lat`, `<p>lon`,
 *               `<p>suggest`, `<p>state`, `<p>chips`, `<p>dl`.
 *   such        eigenes Suchfeld erzeugen (getrennte Suche, siehe ortsfeld.js)
 *   such_hinweis / such_platzhalter
 *   label, hinweis, platzhalter, max, wert           (nur bei feld = true)
 *   name        POST-Name des Bezeichnungsfeldes; null = keiner (der Wert
 *               wandert dann verschluesselt in den pat_blob)
 *   lat_name / lon_name, lat / lon                   Koordinatenfelder
 *   datalist    Liste von Namen fuer eine <datalist> (Stammdaten-Vorschlaege)
 *   klasse      zusaetzliche Klasse am Rahmen (z. B. 'loc-inline')
 */
function ui_ortsfeld(array $o): void
{
    $p = (string)$o['praefix'];
    $mitFeld = ($o['feld'] ?? true) !== false;
    $mitSuche = !empty($o['such']);
    $dl = $o['datalist'] ?? null;

    $versteckt = !empty($o['versteckt']) ? ' hidden' : '';

    if ($mitFeld): ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
        <label><?= e((string)($o['label'] ?? '')) ?>
          <?php if (!empty($o['hinweis'])): ?>
            <span class="muted small"><?= e((string)$o['hinweis']) ?></span>
          <?php endif; ?>
          <input type="text" id="<?= e($p) ?>addr" autocomplete="off"
                 <?= isset($o['name']) && $o['name'] !== null ? 'name="' . e((string)$o['name']) . '"' : '' ?>
                 <?= isset($o['max']) ? 'maxlength="' . (int)$o['max'] . '"' : '' ?>
                 <?= $dl !== null ? 'list="' . e($p) . 'dl"' : '' ?>
                 placeholder="<?= e((string)($o['platzhalter'] ?? '')) ?>"
                 value="<?= e((string)($o['wert'] ?? '')) ?>">
        </label>
    <?php else: ?>
      <div class="loc-widget <?= e((string)($o['klasse'] ?? '')) ?>"<?= $versteckt ?>>
    <?php endif; ?>

    <?php if ($mitSuche): ?>
      <?php /* Getrennte Suche: Das Namensfeld darüber bleibt der Name
               („Standort Kempten" ist keine Adresse). Hier wird gesucht oder
               eine Koordinate eingefügt; übernommen werden nur die
               Koordinaten. */ ?>
      <label class="fld-sub"><?= e((string)($o['such_hinweis'] ?? 'Koordinaten (optional)')) ?>
        <input type="text" id="<?= e($p) ?>such" autocomplete="off"
               placeholder="<?= e((string)($o['such_platzhalter']
                   ?? 'Adresse suchen — auch Koordinaten oder Plus Code')) ?>">
      </label>
    <?php endif; ?>

      <ul id="<?= e($p) ?>suggest" class="loc-suggest" hidden></ul>
      <?php /* Meldungszeile unmittelbar unter dem Feld: Sie sagt etwas über
               DIESES Eingabefeld aus („Koordinaten gesetzt — dieses Feld ist
               die Bezeichnung", „Bezeichnung fehlt"), nicht über den Chip
               darunter. */ ?>
      <p class="locstate" id="<?= e($p) ?>state"></p>
      <?php /* Bestätigte Koordinaten stehen als Chip UNTER dem Textfeld, nicht
               darin — sonst vernichtet die erste getippte Bezeichnung sie. */ ?>
      <div class="rmchips" id="<?= e($p) ?>chips"></div>
      <input type="hidden" id="<?= e($p) ?>lat"
             <?= isset($o['lat_name']) ? 'name="' . e((string)$o['lat_name']) . '"' : '' ?>
             value="<?= e((string)($o['lat'] ?? '')) ?>">
      <input type="hidden" id="<?= e($p) ?>lon"
             <?= isset($o['lon_name']) ? 'name="' . e((string)$o['lon_name']) . '"' : '' ?>
             value="<?= e((string)($o['lon'] ?? '')) ?>">
      <?php if ($dl !== null): ?>
        <datalist id="<?= e($p) ?>dl">
          <?php foreach ((array)$dl as $s): ?><option value="<?= e((string)$s) ?>"><?php endforeach; ?>
        </datalist>
      <?php endif; ?>
      </div>
<?php }

/** Fusszeile: im Dokumentfluss, rechtsbündig unter dem Inhalt */
function ui_footer(): void { ?>
  <script src="<?= asset('assets/confirm.js') ?>"></script>
  <script src="<?= asset('assets/daylist.js') ?>"></script>
<footer class="sitefooter">© Gen-EM – OpenSource Software –
  <a href="https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE"
     target="_blank" rel="noopener">AGPL-3.0</a>
  <span class="ver">v<?= e(WEB_VERSION) ?></span></footer>
<?php }
