<?php
declare(strict_types=1);
// Noch nicht eingerichtet? -> Installer starten (erledigt sich nach 1x selbst).
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/mission_fields_lib.php';   // Spalten der Tagestabelle
require_once __DIR__ . '/diensttag_lib.php';

// Spalten der Tagestabelle aus dem Feldkatalog (mission_fields.php, 'day_col').
// Tabellenkopf, Zeilenaufbau und Sortierung unten leiten sich alle hieraus ab.
$TAGESSPALTEN = mf_tagesspalten();

/* Stammdaten fuer die Auswahlfelder des Diensttags. Es sind die eigenen und die
 * AUSGEWAEHLTEN zentralen Standorte samt ihren Rettungsmitteln (E16) — dieselbe
 * Menge, die dt_zuordnen() beim Speichern akzeptiert. Weicht das eine vom
 * anderen ab, wird ein zentraler Eintrag beim Speichern stillschweigend auf
 * NULL zurueckgesetzt. */
$SD_BASES    = dt_bases($userId);
$SD_VEHICLES = dt_vehicles($userId);
$SD_DEFAULTS = dt_standardwerte($userId);

/* Gewaehlter Diensttag: ?d=<Kennung>, sonst der juengste. NICHT mehr ein
 * Datum: Seit E9 koennen mehrere Diensttage auf einem Kalendertag liegen, ein
 * Datum bestimmt also keinen Tag mehr. */
$selDayId = (int)($_GET['d'] ?? 0);
if ($selDayId > 0 && dt_laden($userId, $selDayId) === null) { $selDayId = 0; }
if ($selDayId === 0) { $selDayId = dt_neuester($userId) ?? 0; }
$selDay = $selDayId > 0 ? $selDayId : null;

/* Neu hinzugekommene Geraete (M4-10). Die Startseite ist die Seite, auf der
 * nach der Anmeldung jede/r landet — ein Hinweis, der nur im Geraete-Reiter
 * stuende, erreichte genau die Person nicht, die dort nie hinsieht. Die
 * eigentliche Benachrichtigung ist die E-Mail beim Koppeln (pair.php).
 *
 * Der Hinweis laesst sich bestaetigen. Vorher stand er sieben Tage lang da
 * und war nicht wegzuklicken — eine Warnung, die man nicht loswird, wird
 * ueberlesen, und dann steht sie unbemerkt da, wenn sie einmal wirklich
 * gemeint ist. Post/Redirect/Get, damit ein Neuladen sie nicht wiederholt. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'geraete_ok') {
    csrf_check();
    geraete_hinweis_bestaetigen(db(), $userId);
    header('Location: index.php' . ($selDay !== null ? '?d=' . (int)$selDay : ''));
    exit;
}
$neueGeraete = geraete_neu(db(), $userId);
ui_seite_start(['titel' => 'Tagesübersicht', 'karte' => true]);
?>

<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $selDay]); ?>
    <?php if ($neueGeraete): ?>
      <?php /* Warnmeldung nach E-P3-16: Symbol, Text, Ausweg als Knopf IM
               Rahmen. Der Hinweis bleibt bestaetigbar (M4-10) — eine Warnung,
               die man nicht loswird, wird ueberlesen. */ ?>
      <div class="meldung meldung-warn" role="status">
        <?= ui_symbol('warnung', 'symbol-gross') ?>
        <p>
          <?= count($neueGeraete) === 1 ? 'Ein neues Gerät wurde' : count($neueGeraete) . ' neue Geräte wurden' ?>
          mit deinem Konto verbunden:
          <?php $teile = [];
                foreach ($neueGeraete as $g) {
                    $teile[] = ($g['label'] ?? $g['device_id'])
                             . ' (' . fmt_local($g['created_at'], 'd.m.Y H:i') . ')';
                }
                echo e(implode(', ', $teile)); ?>.
          Warst du das nicht, entferne
          <?= count($neueGeraete) === 1 ? 'das Gerät' : 'die Geräte' ?> unter
          <a href="einstellungen.php?t=geraete">Einstellungen → Geräte</a>.
        </p>
        <div class="meldung-aktion">
          <form method="post" action="index.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="geraete_ok">
            <?= ui_knopf(['text' => 'Verstanden, das war ich', 'art' => 'neutral']) ?>
          </form>
        </div>
      </div>
    <?php endif; ?>
    <?php /* TITELZEILE NACH E-P3-31: kein Rueckweg (das hier IST die
             Startseite), Titel „Samstag, 22.08.2026" — mobil kurz „Sa" —,
             Unterzeile Rettungsmittel · Standort, rechts das Aktionsmenue
             (E-P3-27): mobil „⋯" und ein Blatt von unten, am Desktop
             „Aktionen" als Aufklappmenue. Der Anlegen-Weg steht als erste
             Zeile auch dort; „Tag loeschen" ist rot und abgesetzt.

             Titel und Unterzeile fuellt loadDay() — die Seite kennt ihren
             Tag erst aus api/day.php. Das Menue bleibt verborgen, solange
             kein Tag gewaehlt ist: Beide Eintraege brauchen einen. */ ?>
    <div class="titelzeile">
      <div class="titelzeile-haupt">
        <div class="titelzeile-text">
          <h1 id="daytitle">–</h1>
        </div>
        <div class="titelzeile-aktionen" id="dayaktionen" <?= $selDay ? '' : 'hidden' ?>>
          <?= ui_aktionen(['titel' => 'Diensttag', 'id' => 'dayblatt', 'eintraege' => [
            ['text' => 'Einsatz nachtragen', 'symbol' => 'plus', 'anlegen' => true,
             'href' => 'einsatz_form.php', 'attr' => 'id="addmission-menu"'],
            ['text' => 'Diensttag-Daten bearbeiten', 'symbol' => 'stift',
             'href' => '#tagdaten', 'attr' => 'data-tagdaten-bearbeiten'],
            ['text' => 'Datum ändern', 'symbol' => 'kalender',
             'href' => 'diensttag_datum.php?d=' . (int)$selDay, 'attr' => 'id="daydatelink"'],
            ['text' => 'Anderen Diensttag aufnehmen', 'symbol' => 'ordner-plus',
             'href' => 'diensttag_zusammenfuehren.php?d=' . (int)$selDay, 'attr' => 'id="daymergelink"'],
            /* SPUREN DES TAGES (S2/AP4). Der Weg zu den GPX-Dateien fuehrt
             * ueber eine eigene Seite und nicht ueber je einen Knopf an der
             * Linie: Ruhesegmente haben in der Tagesansicht keine Zeile und
             * kein Popup, ein Knopf haette dort nirgendwo hingekonnt. Das
             * Symbol `karte` kommt aus dem vorhandenen Vorrat — fuer
             * „herunterladen" gibt es keines, und ein neues Zeichen braucht
             * dieselbe Freigabe wie ein neuer Baustein. */
            ['text' => 'Spuren als GPX', 'symbol' => 'karte',
             'href' => 'tag_spuren.php?d=' . (int)$selDay, 'attr' => 'id="dayspurenlink"'],
            ['text' => 'Tag löschen', 'symbol' => 'korb', 'gefahr' => true,
             'href' => 'diensttag_loeschen.php?d=' . (int)$selDay, 'attr' => 'id="daydellink"'],
          ]]) ?>
        </div>
      </div>
      <p class="titelzeile-unter" id="dayunter" hidden></p>
    </div>
    <div class="meldung meldung-fehler" id="loaderrorbox" role="alert" hidden>
      <?= ui_symbol('warnung', 'symbol-gross') ?><p id="loaderror"></p>
    </div>
    <div class="tag-raster">
    <?php /* DIENSTTAG-DATEN ALS KARTE MIT LESEZUSTAND (E-P3-31, Mockup 02/03).
             Bis Web 9.1.1 war das ein <details>-Aufklapper, dessen summary die
             eingefrorenen Namen UND die Notizen trug — als gesperrte
             Versalzeile (Fund F-P3-A, damit erledigt). Jetzt: eine Karte, die
             LIEST — Standort, Rettungsmittel, Besatzung, Notizen; am Desktop
             zweispaltig, mobil ohne Standort und Rettungsmittel (die stehen
             dort schon in der Unterzeile des Titels). „Bearbeiten" klappt
             dasselbe Formular in der Karte auf — es ist dasselbe wie vorher,
             mitsamt Dirty-Tracking. */ ?>
    <section class="karte karte-daten" id="tagdaten">
      <div class="karte-kopf">
        <h2 class="karte-titel">Diensttag-Daten</h2>
        <a class="karte-aktion karte-aktion-blau" href="#tagdaten"
           id="tagdatenknopf" data-tagdaten-bearbeiten aria-expanded="false">
          <?= ui_symbol('stift') ?><span>Bearbeiten</span>
        </a>
      </div>
      <div class="karte-inhalt">
        <div class="tag-lese" id="taglese"></div>
        <form id="dayform" class="tag-form" hidden data-dirty-track data-submit-on-ctrl-enter>
        <label>Standort
          <select name="base_id" id="basesel">
            <option value="">–</option>
            <?php foreach ($SD_BASES as $b): ?>
              <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?><?php
                /* Zentrale Standorte kenntlich machen: Sie werden von einer
                   Administratorin gepflegt, eigene Änderungen sind dort nicht
                   möglich. Ohne die Kennzeichnung stünden zwei gleichnamige
                   Einträge ohne Unterschied nebeneinander. */
                echo !empty($b['zentral']) ? ' (zentral)' : ''; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Rettungsmittel
          <?php /* Das Rettungsmittel entscheidet über Art, Rollen und Felder
                   (Abschnitt 3.2). Die Liste nennt deshalb den Standort mit:
                   Zwei Standorte können ein gleichnamiges NEF führen, und die
                   Auswahl muss ohne Nachdenken eindeutig sein. */ ?>
          <select name="vehicle_id" id="vehsel">
            <option value="">–</option>
            <?php foreach ($SD_VEHICLES as $v):
                  $sym = dt_art_symbol((string)$v['kind']); ?>
              <option value="<?= (int)$v['id'] ?>"
                      data-base="<?= (int)($v['base_id'] ?? 0) ?>"
                      data-kind="<?= e((string)$v['kind']) ?>">
                <?= e($v['name']) ?><?php
                  echo $v['base_name'] !== null ? ' · ' . e((string)$v['base_name']) : ''; ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <?php /* Die Besatzungsfelder entstehen im Browser aus dem EINGEFRORENEN
                 Rollensatz des Diensttags (`day_crew`, E8), den api/day.php
                 mitliefert — nicht aus einer festen Liste von fünf Flugrollen.
                 Ein bodengebundener Dienst zeigt damit Fahrer, Praktikant und
                 Sonstige (A3), ein neutraler keine (A7a). */ ?>
        <div id="crewfields"></div>
        <p class="feld-hinweis" id="crewhint" hidden></p>
        <p class="feld-hinweis" id="sd-hint" <?= ($SD_VEHICLES || $SD_BASES) ? 'hidden' : '' ?>>
          Noch keine Standorte hinterlegt — unter
          <a href="einstellungen.php?t=standorte">Einstellungen → Standorte</a> anlegen.</p>
        <label>Notizen <textarea name="notes" rows="3" maxlength="2000"></textarea></label>
        <div class="tag-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'art' => 'primaer']) ?>
          <span id="savestate" class="feld-klein-inline" role="status"></span>
        </div>
      </form>
      </div>
    </section>
    <?php /* Rueckmeldung nach dem Zusammenfuehren. Sie gehoert hierher und
             nicht auf die Zwischenseite: Die ist nach dem Vorgang
             verschwunden, und die Bestaetigung soll an dem Tag stehen, der
             jetzt alles traegt. Vollzug = blau mit Haken (E-P3-16). */ ?>
    <?php if (($_GET['aufgenommen'] ?? '') === '1' && $selDay): ?>
      <?= ui_meldung_markup('ok', 'Einsätze, Ruhesegmente und Uhr-Kennungen hängen '
            . 'jetzt an diesem Tag; der aufgenommene ist verschwunden.',
            'Die beiden Diensttage sind zusammengeführt.') ?>
    <?php endif; ?>
    <?php if (($_GET['umdatiert'] ?? '') === '1' && $selDay): ?>
      <?= ui_meldung_markup('ok', 'Alle Zeitstempel sind mitgewandert; die '
            . 'Uhrzeiten stehen unverändert da.', 'Der Diensttag ist umdatiert.') ?>
    <?php endif; ?>

    <?php /* Die Karte. Mobil 160 px ueber der Liste, ab 720 px 220, ab 1200
             300; ab 1600 rueckt sie in die rechte Spalte des Rasters und
             laeuft von der Hoehe der Diensttag-Daten bis unter die Tabelle
             (E-P3-31, Anlage G). */ ?>
    <div class="geo-spalte"><div id="map" class="geo"></div></div>

    <section class="karte karte-einsaetze" id="einsatzliste">
      <div class="karte-kopf">
        <h2 class="karte-titel">Einsätze</h2>
        <span class="karte-zahl" id="mzahl"></span>
        <?php /* Sortieren mobil: Die Kachel hat keine Spaltenkoepfe — der
                 leise Link oeffnet das Blatt mit den Spalten (E-P3-32). Er
                 ist bewusst KEINE zweite Kopfaktion: Die eine Handlung der
                 Karte bleibt „+ Nachtragen" (E-P3-25). */ ?>
        <button type="button" class="karte-sortieren nur-unter-720"
                data-blatt="sortblatt" aria-expanded="false" aria-controls="sortblatt">
          <?= ui_symbol('sortieren', '', 'Sortieren') ?>
        </button>
        <a class="karte-aktion karte-aktion-orange" id="addmission" href="einsatz_form.php">
          <?= ui_symbol('plus') ?><span>Nachtragen</span>
        </a>
      </div>
      <div class="karte-inhalt">
        <?php /* Sperrhinweis als Meldung mit Schloss und „Entsperren"
                 (E-P3-31). Der Knopf steht IN der Meldung, mittig rechts. */ ?>
        <div class="meldung meldung-info" id="lockbanner" role="status" hidden>
          <?= ui_symbol('schloss', 'symbol-gross') ?>
          <p>Geschützte Angaben sind gesperrt — Einsatzort, Alter und Diagnose
             bleiben verborgen, bis die Verschlüsselung entsperrt ist.</p>
          <div class="meldung-aktion">
            <?= ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                          'typ' => 'button', 'attr' => ' id="unlockbtn"']) ?>
          </div>
        </div>

        <?php /* Ab 720 px die Tabelle im eigenen Scrollbehaelter; darunter
                 die dreizeilige Kachel (E-P3-32). Beide entstehen aus
                 demselben Zeilenbestand in renderMissionTable(). */ ?>
        <div class="tabelle-scroll nur-ab-720">
          <table class="tabelle" id="missions">
            <thead><tr>
              <th class="streifen-spalte"></th>
              <th class="sortable mitte-spalte" data-key="no"    data-label="Nr.">Nr.</th>
              <th class="sortable mitte-spalte" data-key="start" data-label="Beginn">Beginn</th>
              <th class="sortable zahl-spalte"  data-key="dur"   data-label="Dauer">Dauer</th>
              <th class="sortable"              data-key="site"  data-label="Einsatzort">Einsatzort</th>
              <th class="sortable mitte-spalte" data-key="age"   data-label="Alter">Alter</th>
              <th class="sortable"             data-key="dx"    data-label="Diagnose">Diagnose</th>
              <?php /* Spaltentitel aus dem Feldkatalog. Bewusst unmaskiert: Der
                       Wert ist 'day_label' aus mission_fields.php und darf
                       Auszeichnung enthalten (Sekundär<br>Transport). Er stammt
                       aus einer Datei des Projekts, nie aus einer Eingabe.
                       data-label ist derselbe Text ohne Auszeichnung — fuer
                       das Sortierblatt. */
                    foreach ($TAGESSPALTEN as $dc): ?>
              <th class="sortable <?= $dc['art'] === 'check' ? 'haken-spalte ' : '' ?><?= e($dc['klasse']) ?>"
                  data-key="dc:<?= e($dc['col']) ?>"
                  data-label="<?= e(strip_tags(str_replace('<br>', ' ', (string)$dc['label']))) ?>"><?= $dc['label'] ?></th>
              <?php endforeach; ?>
              <th class="sortable zahl-spalte" data-key="km" data-label="km">km</th>
            </tr></thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="kachelliste nur-unter-720" id="missionskacheln"></div>
        <p id="empty" class="feld-hinweis" hidden>Für diesen Tag sind keine Einsätze dokumentiert.</p>
      </div>
    </section>
    </div><?php /* .tag-raster */ ?>

    <?php /* Das Sortierblatt (E-P3-32): dieselben Spalten wie der
             Tabellenkopf, mobil als Blatt von unten. Die Eintraege baut
             renderMissionTable() aus den Koepfen — eine zweite Spaltenliste
             gaebe es sonst hier. */ ?>
    <div class="blatt" id="sortblatt" hidden>
      <div class="blatt-griff" aria-hidden="true"></div>
      <h2 class="blatt-titel">Sortieren</h2>
      <div class="blatt-liste" id="sortliste"></div>
      <button type="button" class="knopf knopf-leise blatt-abbrechen" data-blatt-zu>
        <span>Abbrechen</span></button>
    </div>

<?php ui_geruest_ende(); ?>
<?php ui_krypto_bootstrap(['csrf' => true]); ?>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<?php /* missiontable.js liefert die gemeinsamen Bausteine der drei
         Einsatztabellen. Muss NACH html.js stehen: Die Datei liest EdHtml
         schon beim Laden. */ ?>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script src="<?= asset('assets/geo.js') ?>"></script>
<script src="<?= asset('assets/luftlinie.js') ?>"></script>
<script>
const SEL_DAY_ID = <?= json_encode($selDay) ?>;
const DEF_VEHICLE = <?= (int)($SD_DEFAULTS['vehicle_id'] ?? 0) ?>;
const DEF_BASE = <?= (int)($SD_DEFAULTS['base_id'] ?? 0) ?>;
/* Spalten der Tagestabelle — dieselbe Liste, aus der oben der Tabellenkopf
   entstanden ist. Der Titel fehlt hier bewusst: Er steht bereits im <thead>,
   und das Skript baut nur noch Zellen. */
const DAY_COLS = <?= json_encode(array_map(
        static fn(array $dc): array => ['col' => $dc['col'], 'art' => $dc['art'],
                                        'klasse' => $dc['klasse']],
        $TAGESSPALTEN), JSON_UNESCAPED_UNICODE) ?>;
/* Die Spurfarben kommen aus den Token (--spur-1..8, EdGeo.spurFarbe) — hier
   stand eine COLORS-Liste mit fuenf markenfremden Werten (F-P3-H). */
let currentDayId = null;
let currentDay = null;      // Datum des Diensttags, fuer die Altersberechnung
/* Standortkoordinate DIESES Diensttags, eingefroren (E8). Quelle des
   Abfahrtorts „Standort"; null, solange kein Standort zugeordnet oder keine
   Koordinate hinterlegt ist — dann entsteht für diese Regel keine Linie (A13i). */
let currentBase = null;

const map = L.map('map');
attachBaseLayers(map);
attachFullscreenControl(map);
map.setView([48.5, 10.5], 7); // Fallback, bis Daten da sind

let layerGroup = L.layerGroup().addTo(map);
const trackLines = [];
/* Die Marker kommen aus dem gemeinsamen Satz (assets/geo.js, E-P3-40) — hier
   stand ein Inline-SVG-Pin, wortgleich noch einmal in einsatz.php. */

let mapHasBounds = false;
function trackWeight(){
  // Duenne Linien: bei kleinem Massstab wirkten dicke Striche wie Balken.
  const z = map.getZoom();
  return z >= 14 ? 3 : z >= 10 ? 4 : 5;
}
map.on('zoomend', () => {
  const w = trackWeight();
  trackLines.forEach(l => l.setStyle({ weight: w }));
});

function fmtDay(iso){ const [y,m,d]=iso.split('-'); return `${d}.${m}.${y}`; }
let dayMissions = [];
let sortKey = 'start', sortDir = 1;

function sortVal(m, key){
  // Spalten aus dem Feldkatalog tragen den Schluessel 'dc:<spalte>'. Haken
  // sortieren als 0/1, Textspalten als kleingeschriebene Zeichenkette — wie
  // die uebrigen Textspalten der Tabelle auch.
  if (key.startsWith('dc:')) {
    const col = key.slice(3);
    const def = DAY_COLS.find(d => d.col === col);
    const v = m[col];
    if (!def || def.art === 'check') return v ? 1 : 0;
    return String(v ?? '').toLowerCase();
  }
  switch (key) {
    case 'no':
    case 'start': return m._no;
    case 'dur':   return m.duration_s == null ? -1 : m.duration_s;
    case 'site':  return (m._ort || '').toLowerCase();
    case 'age':   return m._age == null ? -1 : m._age;
    case 'dx':    return (m._dx || '').toLowerCase();
    case 'km':    return m.distance_m == null ? -1 : m.distance_m;
  }
  return 0;
}

function renderMissionTable(){
  const tbody = document.querySelector('#missions tbody');
  tbody.innerHTML = '';
  const list = [...dayMissions].sort((a, b) => {
    const va = sortVal(a, sortKey), vb = sortVal(b, sortKey);
    return (va < vb ? -1 : va > vb ? 1 : a._no - b._no) * sortDir;
  });
  list.forEach(m => {
    const tr = document.createElement('tr');
    tr.className = 'clickable';
    // Zellen der Katalogspalten in der Reihenfolge des Tabellenkopfes.
    // Haken aus dem Symbolvorrat, dunkelblau (E-P3-32).
    const dcZellen = DAY_COLS.map(d => {
      const v = m[d.col];
      if (d.art === 'check') {
        return `<td class="haken-spalte ${d.klasse}">${v ? edSymbol('haken', 'tabelle-haken', 'ja') : ''}</td>`;
      }
      const t = (v == null || v === '') ? '' : String(v);
      return `<td class="${d.klasse}${t ? '' : ' dash'}">${t ? esc(t) : '–'}</td>`;
    }).join('');
    /* NR., BEGINN UND ALTER MITTIG (S3/AP5, Block I). Eine laufende Nummer,
       eine Uhrzeit und ein Alter sind weder Flietext noch Groessen, die man
       an einer Kante vergleicht — rechtsbuendig gestellt fluchteten sie an
       einer Kante, die nichts bedeutet, und der mittige Spaltentitel stand
       ueber ihnen im Leeren.

       DIE DAUER TRAEGT JETZT AUCH HIER `zeit-spalte`. Sie fehlte an genau
       dieser Stelle: missiontable.js setzt sie seit F-N1-G, dieser Aufbau
       der Tagesuebersicht ist ein zweiter, aelterer — und ohne die Klasse
       brach „1h 06min" in schmaler Spalte nach der Stunde um und las sich
       wie zwei Angaben. Dass es zwei Aufbauten fuer dieselbe Tabelle gibt,
       ist der eigentliche Fund (F-S3-A). */
    tr.innerHTML = `<td class="streifen-spalte"><span class="streifen" style="background:${m._col}"></span></td>
      <td class="mitte-spalte">${m._no}</td>
      <td class="mitte-spalte">${m.start_hhmm}</td>
      <td class="zahl-spalte zeit-spalte">${EdMissionTable.zelleDauer(m.duration_s)}</td>
      ${zelleGeschuetzt(m, m._ort)}
      ${zelleGeschuetzt(m, m._age, null, 'mitte-spalte')}
      ${zelleGeschuetzt(m, m._dx)}
      ${dcZellen}
      <td class="zahl-spalte">${EdMissionTable.fmtKmZahl(m.distance_m)}</td>`;
    /* Die Zeile ist die Schaltflaeche — auch fuer die Tastatur (Backlog Nr. 16).
     * Bis Web 6.3.0 hatte sie hier nur einen Klick-Handler und `cursor:pointer`:
     * Die Tagesuebersicht war damit die einzige der drei Einsatztabellen, die
     * sich ausschliesslich mit der Maus oeffnen liess. Suche und
     * Zeitraum-Uebersicht bringen dieselben drei Zeilen seit Web 5.2.0 ueber
     * assets/missiontable.js mit; hier stehen sie jetzt woertlich genauso.
     * role="link" statt "button", weil die Handlung ein Seitenwechsel ist. */
    tr.tabIndex = 0;
    tr.setAttribute('role', 'link');
    const oeffne = () => { location.href = 'einsatz.php?id=' + m.id; };
    tr.addEventListener('click', oeffne);
    tr.addEventListener('keydown', ev => {
      // Leertaste bewusst mit: uebliche Ausloesung fuer fokussierte
      // Bedienelemente — ohne preventDefault scrollt die Seite stattdessen weg.
      if (ev.key === 'Enter' || ev.key === ' ' || ev.key === 'Spacebar') {
        ev.preventDefault();
        oeffne();
      }
    });
    tbody.appendChild(tr);
  });
  document.querySelectorAll('#missions th.sortable').forEach(th => {
    th.querySelector('.arrow')?.remove();
    if (th.dataset.key === sortKey) {
      const a = document.createElement('span');
      a.className = 'arrow';
      a.innerHTML = ' ' + edSymbol('pfeil-hoch', sortDir > 0 ? '' : 'symbol-oben',
        sortDir > 0 ? 'aufsteigend' : 'absteigend');
      th.appendChild(a);
    }
  });

  /* Die Kachelliste — dieselben Zeilen in derselben Reihenfolge, aus dem
     gemeinsamen Erzeuger (E-P3-32). Unter 720 px ist sie die einzige Form. */
  document.getElementById('missionskacheln').innerHTML =
    list.map(m => EdMissionTable.kachel(m, { farbe: m._col })).join('');

  /* Zahl und km-Summe im Kartenkopf: „4 · 140 km" (Mockup 02). */
  {
    const km = list.reduce((s, m) => s + (m.distance_m || 0), 0);
    document.getElementById('mzahl').textContent = list.length
      ? list.length + (km > 0 ? ' · ' + Math.round(km / 1000) + ' km' : '')
      : '';
  }

  /* Das Sortierblatt fuehrt dieselben Spalten wie der Tabellenkopf — es
     entsteht aus ihm, eine zweite Spaltenliste gibt es nicht (E-P3-32). Die
     aktive Spalte ist hervorgehoben und traegt die Richtung. */
  {
    const liste = document.getElementById('sortliste');
    liste.innerHTML = '';
    document.querySelectorAll('#missions th.sortable').forEach(th => {
      const b = document.createElement('button');
      b.type = 'button';
      const aktiv = th.dataset.key === sortKey;
      b.className = 'blatt-zeile' + (aktiv ? ' aktiv' : '');
      b.innerHTML = '<span>' + esc(th.dataset.label || '') + '</span>'
        + (aktiv ? edSymbol('pfeil-hoch', sortDir > 0 ? '' : 'symbol-oben',
                            sortDir > 0 ? 'aufsteigend' : 'absteigend') : '');
      b.addEventListener('click', () => {
        if (sortKey === th.dataset.key) { sortDir = -sortDir; }
        else { sortKey = th.dataset.key; sortDir = 1; }
        renderMissionTable();
        if (window.edBlatt) { edBlatt.zu(); }
      });
      liste.appendChild(b);
    });
  }
}

/* GEMEINSAME BAUSTEINE STATT EIGENER FASSUNGEN (A6, E-A6-07).
 *
 * Hier standen eigene Umsetzungen von extractOrt(), fmtDur() und fmtKm() —
 * und die erste war bereits auseinandergelaufen: Ihr fehlte die Pruefung auf
 * Buchstaben, die assets/missiontable.js seit E11 hat. Ein Altdatensatz mit
 * Koordinatentext im Ortsfeld zeigte deshalb AUF DER STARTSEITE das
 * Bruchstueck „10.31600", in Suche und Zeitraum-Uebersicht dagegen die ganze
 * Koordinate „47.72800, 10.31600".
 *
 * zeitraum.php holt dieselben Bausteine seit jeher so. Die SPALTEN-Mechanik
 * von missiontable.js uebernimmt diese Seite bewusst NICHT: Sie fuehrt die
 * Katalogspalten aus DAY_COLS, die die anderen beiden Tabellen nicht haben. */
const { extractOrt, fmtDur, fmtKm, zelleGeschuetzt } = EdMissionTable;

// Maskierung: Baustein B7 (assets/html.js). Hier stand eine eigene Fassung
// ueber ein Hilfselement — sie maskierte drei Zeichen statt fuenf (M6-03).
const esc = EdHtml.escape;

function showLoadError(msg){
  document.getElementById('loaderror').textContent =
    'Die Tagesdaten konnten nicht geladen werden: ' + msg;
  document.getElementById('loaderrorbox').hidden = false;
}

async function loadDay(dayId){
  let d;
  try {
    const res = await fetch('api/day.php?d='+encodeURIComponent(dayId));
    const txt = await res.text();
    try { d = JSON.parse(txt); }
    catch (e) {
      // Kein JSON: meist ein Server-/SQL-Fehler. Anfang der Antwort zeigen,
      // damit die Ursache sofort sichtbar ist statt einer leeren Seite.
      showLoadError(txt.replace(/<[^>]*>/g, ' ').trim().slice(0, 300) || ('HTTP ' + res.status));
      return;
    }
    if (d.error) { showLoadError(d.error + (d.meldung ? ': ' + d.meldung : '')); return; }
  } catch (e) { showLoadError(e.message); return; }
  document.getElementById('loaderrorbox').hidden = true;

  /* Liegt der Tag im Papierkorb, steht das jetzt in der Antwort. Ohne diesen
   * Hinweis wären fehlende Diensttagangaben nicht von "noch nichts eingetragen"
   * zu unterscheiden — wer seine Eingaben vermisst, sucht den Fehler bei sich. */
  {
    let hinweis = document.getElementById('daytrash');
    if (d.day_deleted_at) {
      if (!hinweis) {
        hinweis = document.createElement('p');
        hinweis.id = 'daytrash';
        hinweis.className = 'meldung meldung-warn';
        const main = document.querySelector('main.inhalt');
        main.insertBefore(hinweis, main.firstChild);
      }
      hinweis.textContent = 'Dieser Diensttag liegt im Papierkorb. Angaben zu '
        + 'Rettungsmittel, Standort und Besatzung werden nicht angezeigt und können '
        + 'nicht gespeichert werden, solange er dort liegt. Wiederherstellen unter '
        + 'Einstellungen → Papierkorb.';
    } else if (hinweis) {
      hinweis.remove();
    }
  }
  currentDayId = d.day_id;
  currentDay = d.day;
  /* Titel nach E-P3-31: „Samstag, 22.08.2026", mobil „Sa, 22.08.2026" — beide
     Fassungen stehen im Markup, das Stylesheet waehlt. Die Art steckt nicht
     mehr im Titel: Sie traegt das Rettungsmittel in der Unterzeile, und in
     der Leiste steht ihr Zeichen. Die Uhrzeit des Dienstbeginns steht in der
     Unterzeile — seit E9 koennen zwei Dienste auf einem Kalendertag liegen,
     und ohne sie saehen beide Seiten gleich aus. */
  {
    const [y, mo, dd] = d.day.split('-').map(Number);
    const wt = new Date(y, mo - 1, dd).getDay();
    const LANG = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
    const KURZ = ['So','Mo','Di','Mi','Do','Fr','Sa'];
    document.getElementById('daytitle').innerHTML =
      '<span class="wtag-lang">' + LANG[wt] + '</span>'
      + '<span class="wtag-kurz">' + KURZ[wt] + '</span>, ' + fmtDay(d.day);

    const unter = document.getElementById('dayunter');
    const teile = [];
    if (d.meta && d.meta.vehicle_name) { teile.push(d.meta.vehicle_name); }
    if (d.meta && d.meta.base_name)    { teile.push(d.meta.base_name); }
    if (!teile.length && d.meta && d.meta.art_text) { teile.push(d.meta.art_text); }
    if (d.meta && d.meta.started_at)   { teile.push('Dienstbeginn ' + d.meta.started_at); }
    unter.textContent = teile.join(' · ');
    unter.hidden = !teile.length;

    const blattTitel = document.querySelector('#dayblatt .blatt-titel');
    if (blattTitel) { blattTitel.textContent = 'Diensttag ' + fmtDay(d.day); }
  }
  document.getElementById('daydellink').href = 'diensttag_loeschen.php?d=' + d.day_id;
  document.getElementById('daydatelink').href = 'diensttag_datum.php?d=' + d.day_id;
  document.getElementById('daymergelink').href = 'diensttag_zusammenfuehren.php?d=' + d.day_id;
  document.getElementById('dayspurenlink').href = 'tag_spuren.php?d=' + d.day_id;
  document.getElementById('addmission-menu').href = 'einsatz_form.php?d=' + d.day_id;
  // Das Menü als Ganzes wird sichtbar, nicht die einzelnen Einträge: Ein
  // aufklappbarer Kopf mit leerer Liste wäre ein Angebot ohne Inhalt.
  document.getElementById('dayaktionen').hidden = false;

  // Diensttag-Felder befuellen
  const f = document.getElementById('dayform');
  /* Vorbelegung: ohne gespeicherten Wert greifen Standard-Rettungsmittel und
     -Standort. Sie sind ein VORSCHLAG im Formular und werden erst beim
     Speichern wirksam — eingefroren wird nur, was tatsächlich gespeichert
     wurde (E8). */
  f.elements['vehicle_id'].value = (d.meta && d.meta.vehicle_id)
    ? d.meta.vehicle_id : (DEF_VEHICLE || '');
  f.elements['base_id'].value = (d.meta && d.meta.base_id)
    ? d.meta.base_id : (DEF_BASE || '');
  f.elements['notes'].value = (d.meta && d.meta.notes) ? d.meta.notes : '';
  renderCrewFields(d.meta);
  zeigeTagLese(d.meta);
  document.getElementById('savestate').textContent = '';
  document.getElementById('addmission').href = 'einsatz_form.php?d=' + d.day_id;

  layerGroup.clearLayers();
  trackLines.length = 0;
  const bounds = [];

  // Ruhe-Track: schwarz, dezent
  d.rest_segments.forEach(seg => {
    if (seg.length > 1) {
      const rl = L.polyline(seg, { color: EdGeo.ruheFarbe(),
        weight: Math.max(3, trackWeight() - 1), opacity:0.9, smoothFactor:0 });
      layerGroup.addLayer(rl);
      trackLines.push(rl);
      seg.forEach(p => bounds.push(p));
    }
  });

  // Einsaetze: je eigene Farbe
  // Einsaetze: Nummer + Farbe stabil nach Alarmierungszeit vergeben
  // (API liefert aufsteigend nach Beginn), danach frei sortierbar.
  dayMissions = d.missions.map((m, i) => {
    m._no = i + 1;
    m._col = EdGeo.spurFarbe(i);
    return m;
  });
  currentBase = (d.meta && d.meta.base_lat != null && d.meta.base_lon != null)
    ? { lat: d.meta.base_lat, lon: d.meta.base_lon } : null;
  /* Standort-Haus (E-P3-40): immer sichtbar, sobald der Diensttag eine
     eingefrorene Standortkoordinate traegt — mit Namensschild. */
  if (currentBase) {
    layerGroup.addLayer(EdGeo.markerStandort([currentBase.lat, currentBase.lon],
      (d.meta && d.meta.base_name) || 'Standort'));
    bounds.push([currentBase.lat, currentBase.lon]);
  }
  d.missions.forEach(m => {
    if (m.track.length > 1) {
      const line = L.polyline(m.track, { color: m._col, weight: trackWeight(), smoothFactor: 0 });
      layerGroup.addLayer(line);
      trackLines.push(line);
      /* Richtungspfeile ab einer Zoomstufe, bei der sie nicht gedraengt
         stehen (E-P3-40) — die Verteilung rechnet assets/geo.js. */
      EdGeo.pfeile(map, layerGroup, m.track);
      m.track.forEach(p => bounds.push(p));
    }
    /* Zielklinik-Schild: Klartext, also ohne Freischalten (E40, A13o). Es
       steht hier und nicht in entschluesselePat() — dort landet nur, was den
       Schluessel braucht. Der NAME des Ziels liegt verschluesselt; das
       Schild traegt deshalb kein Namensschild, das Popup nennt den Einsatz. */
    if (m.dest_lat != null && m.dest_lon != null) {
      layerGroup.addLayer(EdGeo.markerZiel([m.dest_lat, m.dest_lon], '')
        .bindPopup(`Einsatz ${m._no}<br>Zielklinik`));
      bounds.push([m.dest_lat, m.dest_lon]);
    }
  });
  renderMissionTable();
  if (PAT_WRAP) { entschluesselePat(); }

  document.getElementById('empty').hidden = d.missions.length > 0;
  document.getElementById('missions').hidden = d.missions.length === 0;

  // Auto-Zoom: Track soll ca. 75 % der Karte einnehmen -> ~12.5 % Rand je Seite
  if (bounds.length) {
    mapHasBounds = true;
    const px = map.getSize();
    /* PADDING IST (x, y) — hier stand [px.y…, px.x…], also die Achsen
       vertauscht (Bestandsfehler, F-P3-Z). Bei der alten 380-px-Karte fiel
       das nicht auf; bei 300 px frass das vertauschte y-Padding 287 der
       300 Pixel, und der Zoom blieb auf der Fallback-Stufe haengen. */
    map.fitBounds(L.latLngBounds(bounds),
      { padding: L.point(px.x * 0.125, px.y * 0.125), maxZoom: 15 });
  }
}

/* Geschuetzte Angaben nachtragen. Ist der Inhaltsschluessel gesperrt, bietet
 * EdUnlock den Entsperrdialog an; bei Abbruch bleibt es beim Sperrhinweis.
 * Bewusst ohne await aufgerufen — der Kartenausschnitt unten soll nicht auf
 * die Entschluesselung warten. Ein erneuter Aufruf ueber den Entsperrknopf
 * ist gefahrlos: ohne Schluessel wurde vorher kein Pin gezeichnet. */
async function entschluesselePat(){
  const banner = document.getElementById('lockbanner');
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  if (!ck) { if (banner) banner.hidden = !dayMissions.some(m => m.pat_blob); return; }
  if (banner) banner.hidden = true;
  let changed = false;
  const pinBounds = [];
  /* ENTSCHLUESSELN UND ZAEHLEN GESCHIEHT AN EINER STELLE (M6-06, Baustein B8).
   *
   * Hier stand die Schleife ausgeschrieben — eine von fuenf fast gleichen im
   * Projekt. Sie unterschieden sich in Kleinigkeiten (welcher Zaehler wann
   * hochgeht, ob _pat gesetzt wird), und genau solche Kleinigkeiten sind es,
   * die beim naechsten Mal auseinanderlaufen. Was ANZUZEIGEN ist, bleibt
   * Sache der Seite; was ein Fehlschlag BEDEUTET, entscheidet EdPat. */
  const zahl = await EdPat.entschluessleListe(dayMissions, ck);
  for (let i = 0; i < dayMissions.length; i++) {
    const m = dayMissions[i];
    if (m._patState === 'unlesbar') { changed = true; continue; }
    if (m._patState !== 'ok') { continue; }
    const o = m._pat;
    if (o.dx != null) { m._dx = o.dx; changed = true; }
    // Alter: aus dem Geburtsdatum zum Einsatztag, sonst der eingetragene
    // Wert. Name und Geburtsdatum bleiben bewusst aus der Uebersicht.
    const alter = EdPat.alterAnzeige(o, currentDay);
    if (alter != null) { m._age = alter; changed = true; }
    if (o.loc && o.loc.addr) {
      m._ort = extractOrt(o.loc.addr);
      changed = true;
      if (o.loc.lat != null) {
        /* Einsatzort: oranger Kreis mit Pin (E-P3-40) — einheitlich fuer
           alle Einsaetze; welcher zu welchem gehoert, sagen Spurfarbe und
           Popup. */
        layerGroup.addLayer(EdGeo.markerEinsatzort([o.loc.lat, o.loc.lon],
          `Einsatz ${m._no}<br>` + esc(o.loc.addr)));
        pinBounds.push([o.loc.lat, o.loc.lon]);
      }
    }

    /* ---- Luftlinie je Einsatz ohne Track (E34/E35) ---------------------
     *
     * Hier — anders als in der Einsatzansicht — sind die beiden
     * Vorgänger-Quellen ohne Zusatzabfrage zu haben: Die Einsätze des Tages
     * liegen gemeinsam vor, aufsteigend nach Alarmierungszeit, und sind an
     * dieser Stelle bereits entschlüsselt (Konzept 4.6.1). Der VORHERIGE
     * Einsatz ist schlicht der davor in der Liste; Papierkorbeinträge sind gar
     * nicht erst dabei (A13q).
     *
     * Die Linie trägt die Farbe IHRES Einsatzes, nicht die einheitliche
     * Luftlinienfarbe: Bei acht Einsätzen an einem Tag wäre sonst nicht mehr
     * zu erkennen, welche zu welchem gehört. Gestrichelt bleibt sie überall —
     * das ist die Unterscheidung zum aufgezeichneten Track. */
    const vor = i > 0 ? dayMissions[i - 1] : null;
    const abfahrt = EdLuftlinie.abfahrt(m.start_src, {
      base: currentBase,
      prevDest: (vor && vor.dest_lat != null && vor.dest_lon != null)
        ? { lat: vor.dest_lat, lon: vor.dest_lon } : null,
      prevSite: (vor && vor._patState === 'ok' && vor._pat && vor._pat.loc)
        ? vor._pat.loc : null,
      manual: o.start
    });
    const punkte = EdLuftlinie.punkte({
      hatTrack: m.track.length > 1,
      abfahrt: abfahrt,
      ort: o.loc,
      ziel: (m.dest_lat != null && m.dest_lon != null)
        ? { lat: m.dest_lat, lon: m.dest_lon } : null
    });
    if (punkte.length) {
      EdLuftlinie.zeichne(map, punkte,
        { ziel: layerGroup, farbe: m._col, titel: `Einsatz ${m._no}` });
      punkte.forEach(p => pinBounds.push([p.lat, p.lon]));
      if (abfahrt) {
        /* Der Abfahrtort ist kein Mitglied des Marker-Satzes — er ist die
           gerechnete Gegenstelle der Luftlinie und bleibt ein leiser Punkt
           in der Spurfarbe seines Einsatzes. */
        layerGroup.addLayer(EdGeo.markerPunkt([abfahrt.lat, abfahrt.lon], m._col,
          `Einsatz ${m._no}<br>Abfahrtort`
            + (abfahrt.text ? '<br>' + esc(abfahrt.text) : '')));
      }
    }
  }
  EdPat.zeigeUnlesbar(zahl);
  if (changed) renderMissionTable();
  if (pinBounds.length && !mapHasBounds) { map.fitBounds(pinBounds, { padding: [30, 30], maxZoom: 15 }); }
}

/* BESATZUNGSFELDER AUS DEM EINGEFRORENEN ROLLENSATZ (E8, A3, A7a/A7b).
 *
 * Bis Web 5.10.0 standen hier fünf Auswahlfelder im Markup, und ein Skript
 * blendete sie nach den Rollen der gewählten Maschine ein und aus. Beides ist
 * jetzt falsch: Die Rollen hängen am DIENSTTAG (`day_crew`), nicht am
 * Rettungsmittel, und es sind je nach Art andere. Die Felder entstehen deshalb
 * aus der Antwort von api/day.php.
 *
 * Textfelder mit Vorschlagsliste statt Auswahlfelder — dieselbe Entscheidung
 * wie beim Einsatzformular (Web 5.5.0, E8): Wer aushilft, steht oft nicht in
 * den Stammdaten. Die Vorbelegungen des Standorts bleiben als Vorschlag.
 *
 * Ein neutraler Diensttag hat keine Rollen (E26). Dann steht dort ein Satz, der
 * sagt, WARUM nichts zu sehen ist, und verlinkt die Zuordnung — eine leere
 * Fläche wäre nicht von einem Fehler zu unterscheiden.
 */
function renderCrewFields(meta){
  const box = document.getElementById('crewfields');
  const hint = document.getElementById('crewhint');
  box.innerHTML = '';
  const crew = (meta && meta.crew) ? meta.crew : [];
  const presets = (meta && meta.presets) ? meta.presets : {};

  if (!crew.length) {
    hint.hidden = false;
    hint.textContent = (meta && meta.vehicle_id)
      ? 'Für dieses Rettungsmittel sind keine Besatzungsrollen angehakt — '
        + 'nachzutragen unter Einstellungen → Rettungsmittel.'
      : 'Noch kein Rettungsmittel zugeordnet: Dieser Diensttag ist neutral und '
        + 'zeigt keine Besatzungsrollen. Zeiten, Phasen, Track und Reanimation '
        + 'werden trotzdem vollständig erfasst.';
    return;
  }
  hint.hidden = true;

  crew.forEach(c => {
    const lab = document.createElement('label');
    lab.dataset.role = c.role;
    lab.textContent = c.label;
    const inp = document.createElement('input');
    inp.type = 'text';
    inp.name = 'crew_' + c.role;
    inp.maxLength = 120;
    inp.autocomplete = 'off';
    inp.value = c.name || '';
    const liste = presets[c.role] || [];
    if (liste.length) {
      const dl = document.createElement('datalist');
      dl.id = 'dl_crew_' + c.role;
      liste.forEach(n => {
        const o = document.createElement('option');
        o.value = n;
        dl.appendChild(o);
      });
      lab.appendChild(dl);
      inp.setAttribute('list', dl.id);
    }
    lab.appendChild(inp);
    box.appendChild(lab);
  });
}

/* Standort und Rettungsmittel gehören zusammen (E15): Ein Rettungsmittel eines
 * anderen Standorts wäre eine Zuordnung, die es nicht geben kann. Die Auswahl
 * zieht den Standort deshalb nach, statt eine falsche Kombination zuzulassen —
 * ohne Standort am Rettungsmittel (Bestandsdaten vor der Nachbearbeitung)
 * bleibt der gewählte stehen. */
function vehicleBaseSync(){
  const veh = document.getElementById('vehsel');
  const base = document.getElementById('basesel');
  const opt = veh.options[veh.selectedIndex];
  const bid = (opt && opt.dataset.base) ? parseInt(opt.dataset.base, 10) : 0;
  if (bid > 0) { base.value = String(bid); }
}

/* Lesezustand der Diensttag-Daten (E-P3-31): Standort, Rettungsmittel,
   Besatzung, Notizen — die EINGEFRORENEN Bezeichnungen aus dem Diensttag
   (E8), nie die heutigen Stammdaten. Mobil entfallen Standort und
   Rettungsmittel: Sie stehen dort schon in der Unterzeile des Titels
   (Mockup 02). Leere Felder erscheinen nicht (E-P3-33 sinngemaess). */
function zeigeTagLese(meta){
  const lese = document.getElementById('taglese');
  const zeilen = [];
  /* `doppelt` = mobil ausblenden (steht schon in der Unterzeile des Titels).
     `breit`   = ab 720 px ueber BEIDE Rasterspalten (F-N1-E): Die Besatzung
                 ist eine Aufzaehlung aus bis zu sieben Rollen und brach in
                 der halben Breite um, obwohl daneben nichts stand. */
  const zeile = (dt, dd, doppelt, breit) => zeilen.push(
    '<div class="tagfeld' + (doppelt ? ' tagfeld-doppelt' : '')
    + (breit ? ' tagfeld-breit' : '') + '"><dt>'
    + esc(dt) + '</dt><dd>' + esc(dd) + '</dd></div>');
  if (meta) {
    if (meta.base_name)    { zeile('Standort', meta.base_name, true, false); }
    if (meta.vehicle_name) { zeile('Rettungsmittel', meta.vehicle_name, true, false); }
    const crew = (meta.crew || []).filter(c => c.name)
      .map(c => c.label + ' ' + c.name).join(' · ');
    if (crew)        { zeile('Besatzung', crew, false, true); }
    if (meta.notes)  { zeile('Notizen', meta.notes, false, true); }
  }
  lese.innerHTML = zeilen.length ? zeilen.join('')
    : '<p class="tag-lese-leer">Noch keine Angaben — über „Bearbeiten" nachtragen.</p>';
}

/* „Bearbeiten" klappt dasselbe Formular in der Karte auf (E-P3-31); der
   zweite Klick — oder Speichern — klappt zurueck in den Lesezustand. */
function tagdatenBearbeiten(auf){
  const form = document.getElementById('dayform');
  const lese = document.getElementById('taglese');
  const knopf = document.getElementById('tagdatenknopf');
  form.hidden = !auf;
  lese.hidden = auf;
  knopf.setAttribute('aria-expanded', auf ? 'true' : 'false');
  if (auf) {
    const erstes = form.querySelector('select, input, textarea');
    if (erstes) { erstes.focus(); }
  }
}

async function init(){
  document.getElementById('vehsel').addEventListener('change', vehicleBaseSync);
  document.getElementById('unlockbtn').addEventListener('click', () => entschluesselePat());
  document.getElementById('tagdatenknopf').addEventListener('click', ev => {
    ev.preventDefault();
    tagdatenBearbeiten(document.getElementById('dayform').hidden);
  });
  /* Derselbe Weg aus dem Aktionsmenue: Blatt schliessen, Formular oeffnen,
     zur Karte rollen. */
  document.querySelector('#dayblatt [data-tagdaten-bearbeiten]')
    ?.addEventListener('click', ev => {
      ev.preventDefault();
      if (window.edBlatt) { edBlatt.zu(); }
      tagdatenBearbeiten(true);
      document.getElementById('tagdaten').scrollIntoView({ block: 'start' });
    });
  document.querySelectorAll('#missions th.sortable').forEach(th => {
    th.addEventListener('click', () => {
      if (sortKey === th.dataset.key) { sortDir = -sortDir; }
      else { sortKey = th.dataset.key; sortDir = 1; }
      renderMissionTable();
    });
  });
  document.getElementById('dayform').addEventListener('submit', async ev => {
    ev.preventDefault();
    if (!currentDayId) return;
    const f = ev.target;
    const body = { day_id: currentDayId,
      vehicle_id: f.elements['vehicle_id'].value || null,
      base_id: f.elements['base_id'].value || null,
      notes: f.elements['notes'].value,
      crew: {} };
    // Nur die Rollen, die dieser Diensttag anbietet — die Felder sind aus
    // seinem Rollensatz entstanden, also sind es genau sie.
    document.querySelectorAll('#crewfields input[name^="crew_"]').forEach(i => {
      body.crew[i.name.slice(5)] = i.value;
    });
    const state = document.getElementById('savestate');
    state.textContent = 'Speichern…';
    const res = await fetch('api/day.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
      body: JSON.stringify(body)
    });
    if (res.ok) {
      state.textContent = 'Gespeichert.';
      tagdatenBearbeiten(false);
      loadDay(currentDayId);
    } else {
      /* Den GRUND zeigen, nicht nur "Fehler".
       * Der wichtigste Fall ist ein Diensttag im Papierkorb: Die Angaben
       * wurden dann NICHT gespeichert, und das muss dastehen — vorher
       * meldete diese Stelle Erfolg für einen Vorgang, der nichts tat. */
      let grund = 'Fehler beim Speichern.';
      try {
        const d = await res.json();
        if (d.meldung) { grund = d.meldung; }
      } catch (e) { /* keine JSON-Antwort */ }
      state.textContent = grund;
    }
  });
  if (SEL_DAY_ID) { loadDay(SEL_DAY_ID); }
  else document.getElementById('daytitle').textContent = 'Noch keine Daten';
}
init();
</script>
<?php ui_seite_ende(); ?>
