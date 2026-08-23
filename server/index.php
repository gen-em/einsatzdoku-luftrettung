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
ui_topbar('uebersicht');
?>

<div class="layout">
  <?php ui_days_sidebar($selDay); ?>

  <main class="page">
    <?php if ($neueGeraete): ?>
      <div class="alert alert-warn geraetehinweis">
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
        <?php /* Der Knopf gehoert IN den Rahmen und muss wie ein Knopf
                 aussehen. Als unterstrichener Text unter dem Absatz war er
                 kaum zu sehen — und ein Hinweis, dessen Ausweg man nicht
                 findet, ist derselbe Hinweis, der nicht wegzuklicken ist. */ ?>
        <form method="post" action="index.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="geraete_ok">
          <button type="submit">Verstanden, das war ich</button>
        </form>
      </div>
    <?php endif; ?>
    <?php /* AKTIONSMENÜ DES DIENSTTAGS (Web 5.10.0).
             „Datum ändern" und „Tag löschen" standen bis dahin als zwei
             Schaltflächen unter der Tabelle, direkt neben „+ Einsatz
             nachtragen" — also das Alltagsgeschäft und zwei Eingriffe in den
             Bestand nebeneinander in einer Reihe. Auf der Einsatzseite ist
             genau diese Trennung seit Web 5.6.0 gezogen: die eine Haupt-
             handlung im Fluss der Seite, alles Weitere im Menü oben rechts.
             Die Diensttagübersicht folgt jetzt derselben Ordnung, mit demselben
             Bauteil (.aktionsmenu) und demselben Verhalten
             (assets/aktionsmenu.js).

             Das Menü bleibt verborgen, solange kein Tag gewählt ist — beide
             Einträge brauchen einen Diensttag. loadDay() blendet es ein. */ ?>
    <div class="pagehead">
      <div class="pagehead-text">
        <h1 id="daytitle">–</h1>
      </div>
      <div class="pagehead-actions">
        <details class="aktionsmenu" id="dayaktionen" <?= $selDay ? '' : 'hidden' ?>>
          <summary class="btn-edit">Aktionen</summary>
          <div class="aktionsliste">
            <a id="daydatelink"
               href="diensttag_datum.php?d=<?= (int)$selDay ?>">Datum ändern</a>
            <?php /* „Anderen Diensttag aufnehmen" (E25): Der Einstieg liegt im
                     ZIELTAG und nicht in der Tagesliste, damit die Richtung
                     eindeutig ist — der geöffnete Tag bleibt, der gewählte
                     verschwindet. Bei einer Auswahl von zwei Zeilen in einer
                     Liste wäre sie eine Frage der Lesart, und der Vorgang ist
                     nicht umkehrbar (E13).

                     Der Eintrag steht über „Tag löschen": beides sind Eingriffe
                     in den Bestand, aber nur einer davon ist endgültig. */ ?>
            <a id="daymergelink"
               href="diensttag_zusammenfuehren.php?d=<?= (int)$selDay ?>">Anderen Diensttag aufnehmen</a>
            <a class="gefahr" id="daydellink"
               href="diensttag_loeschen.php?d=<?= (int)$selDay ?>">Tag löschen</a>
          </div>
        </details>
      </div>
    </div>
    <div id="loaderror" class="alert" hidden></div>
    <details class="daymeta" id="daymeta">
      <summary>Diensttag-Daten <span id="metahint" class="muted"></span>
        <span id="metanotes" class="metanotes"></span></summary>
      <form id="dayform" class="meta-form" data-dirty-track data-submit-on-ctrl-enter>
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
                <?= e($sym['zeichen']) ?> <?= e($v['name']) ?><?php
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
        <p class="muted" id="crewhint" hidden></p>
        <p class="muted" id="sd-hint" <?= ($SD_VEHICLES || $SD_BASES) ? 'hidden' : '' ?>>
          Noch keine Standorte hinterlegt — unter
          <a href="einstellungen.php?t=standorte">Einstellungen → Standorte</a> anlegen.</p>
        <label>Notizen <textarea name="notes" rows="3" maxlength="2000"></textarea></label>
        <button type="submit" class="btn-primary">Speichern</button>
        <span id="savestate" class="muted"></span>
      </form>
    </details>
    <?php /* Rückmeldung nach dem Zusammenführen. Sie gehört hierher und nicht
             auf die Zwischenseite: Die ist nach dem Vorgang verschwunden, und
             die Bestätigung soll an dem Tag stehen, der jetzt alles trägt. */ ?>
    <?php if (($_GET['aufgenommen'] ?? '') === '1' && $selDay): ?>
      <p class="alert alert-ok">Die beiden Diensttage sind zusammengeführt.
        Einsätze, Ruhesegmente und Uhr-Kennungen hängen jetzt an diesem Tag;
        der aufgenommene ist verschwunden.</p>
    <?php endif; ?>
    <?php if (($_GET['umdatiert'] ?? '') === '1' && $selDay): ?>
      <p class="alert alert-ok">Der Diensttag ist umdatiert. Alle Zeitstempel
        sind mitgewandert; die Uhrzeiten stehen unverändert da.</p>
    <?php endif; ?>
    <p id="lockbanner" class="alert alert-info" hidden>
      Geschützte Angaben sind gesperrt — Einsatzort, Alter und Diagnose bleiben
      verborgen, bis die Verschlüsselung entsperrt ist.
      <button type="button" class="btn-plain unlockbtn" id="unlockbtn">Entsperren</button>
    </p>
    <div id="map" class="map"></div>
    <table class="data" id="missions">
      <thead><tr>
        <th class="c-swatch"></th>
        <th class="sortable c-no"   data-key="no">Nr.</th>
        <th class="sortable c-mid"  data-key="start">Beginn</th>
        <th class="sortable c-mid"  data-key="dur">Dauer</th>
        <th class="sortable"        data-key="site">Einsatzort</th>
        <th class="sortable c-mid"   data-key="age">Alter</th>
        <th class="sortable"        data-key="dx">Diagnose</th>
        <?php /* Spaltentitel aus dem Feldkatalog. Bewusst unmaskiert: Der Wert
                 ist 'day_label' aus mission_fields.php und darf Auszeichnung
                 enthalten (Sekundär<br>Transport). Er stammt aus einer Datei
                 des Projekts, nie aus einer Eingabe. */
              foreach ($TAGESSPALTEN as $dc): ?>
        <th class="sortable c-dc <?= e($dc['klasse']) ?>"
            data-key="dc:<?= e($dc['col']) ?>"><?= $dc['label'] ?></th>
        <?php endforeach; ?>
        <?php /* Neutral beschriftet (Abschnitt 3.9): Die Einsatztabelle spricht
                 durchgehend artunabhängig, auch im luftgebundenen Dienst. Die
                 Flugterminologie bleibt allein den Kacheln der
                 Zeitraumübersicht vorbehalten (E32). */ ?>
        <th class="sortable c-km"    data-key="km">km</th>
      </tr></thead>
      <tbody></tbody>
    </table>
    <p id="empty" class="muted" hidden>Für diesen Tag sind keine Einsätze dokumentiert.</p>
    <?php /* Nur noch die eine Handlung, die zum täglichen Erfassen gehört.
             „Datum ändern" und „Tag löschen" stehen seit Web 5.10.0 im
             Aktionsmenü oben rechts: Umdatieren ist keine Angabe zum Tag,
             sondern ein Eingriff in seine Zuordnung — mit Wirkung auf jeden
             Zeitstempel des Tages —, und Löschen erst recht. */ ?>
    <div class="dayactions">
      <a href="einsatz_form.php" id="addmission" class="btn-primary">+ Einsatz nachtragen</a>
    </div>

    <?php ui_footer(); ?>
  </main>
</div>

<?php ui_krypto_bootstrap(['csrf' => true]); ?>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<?php /* missiontable.js liefert die gemeinsamen Bausteine der drei
         Einsatztabellen. Muss NACH html.js stehen: Die Datei liest EdHtml
         schon beim Laden. */ ?>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script src="<?= asset('assets/aktionsmenu.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
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
const COLORS = ['#FF8F1F','#4280E5','#D63338','#1A2E4D','#0C8599','#9C36B5','#2F9E44','#8A5A00'];
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
// Einsatzort als klassischer Karten-Pin in der Einsatzfarbe (SVG-DivIcon)
function locPin(color){
  return L.divIcon({
    className: 'locpin',
    html: `<svg width="30" height="42" viewBox="0 0 30 42" xmlns="http://www.w3.org/2000/svg">
      <path d="M15 1C7.3 1 1 7.2 1 14.9 1 25.4 15 41 15 41s14-15.6 14-26.1C29 7.2 22.7 1 15 1z"
            fill="${color}" stroke="#fff" stroke-width="2"/>
      <circle cx="15" cy="14.5" r="5" fill="#fff"/></svg>`,
    iconSize: [30, 42], iconAnchor: [15, 41], popupAnchor: [0, -34]
  });
}

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
    // Zellen der Katalogspalten in der Reihenfolge des Tabellenkopfes
    const dcZellen = DAY_COLS.map(d => {
      const v = m[d.col];
      if (d.art === 'check') {
        return `<td class="checkcol c-dc ${d.klasse}">${v ? '✓' : ''}</td>`;
      }
      const t = (v == null || v === '') ? '' : String(v);
      return `<td class="c-dc ${d.klasse}${t ? '' : ' dash'}">${t ? esc(t) : '–'}</td>`;
    }).join('');
    tr.innerHTML = `<td class="c-swatch"><span class="swatch" style="background:${m._col}"></span></td>
      <td class="mono c-no">${m._no}</td>
      <td class="mono c-mid">${m.start_hhmm}</td>
      <td class="c-mid">${fmtDur(m.duration_s)}</td>
      ${zelleGeschuetzt(m, m._ort)}
      ${zelleGeschuetzt(m, m._age, 'mono c-mid')}
      ${zelleGeschuetzt(m, m._dx)}
      ${dcZellen}
      <td class="mono c-km">${fmtKm(m.distance_m)}</td>`;
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
      a.textContent = sortDir > 0 ? ' ▲' : ' ▼';
      th.appendChild(a);
    }
  });
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
  const box = document.getElementById('loaderror');
  box.textContent = 'Die Tagesdaten konnten nicht geladen werden: ' + msg;
  box.hidden = false;
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
  document.getElementById('loaderror').hidden = true;

  /* Liegt der Tag im Papierkorb, steht das jetzt in der Antwort. Ohne diesen
   * Hinweis wären fehlende Diensttagangaben nicht von "noch nichts eingetragen"
   * zu unterscheiden — wer seine Eingaben vermisst, sucht den Fehler bei sich. */
  {
    let hinweis = document.getElementById('daytrash');
    if (d.day_deleted_at) {
      if (!hinweis) {
        hinweis = document.createElement('p');
        hinweis.id = 'daytrash';
        hinweis.className = 'alert alert-warn';
        const main = document.querySelector('main.page');
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
  /* Titel: Datum, dazu die Uhrzeit des Dienstbeginns, wenn es eine gibt.
     Seit E9 können mehrere Dienste auf einem Kalendertag liegen — ohne die
     Uhrzeit sähen die beiden Seiten gleich aus. Das Symbol der Art steht davor
     (E27) und trägt seine Textalternative in title/aria-label (A7c). */
  {
    const t = document.getElementById('daytitle');
    t.textContent = '';
    const sym = document.createElement('span');
    sym.className = 'artzeichen';
    sym.textContent = (d.meta && d.meta.art_zeichen) ? d.meta.art_zeichen : '';
    sym.title = (d.meta && d.meta.art_text) ? d.meta.art_text : '';
    sym.setAttribute('aria-label', sym.title);
    t.appendChild(sym);
    let text = ' Diensttag ' + fmtDay(d.day);
    if (d.meta && d.meta.started_at) { text += ', ' + d.meta.started_at; }
    if (d.meta && d.meta.vehicle_name) { text += ' · ' + d.meta.vehicle_name; }
    t.appendChild(document.createTextNode(text));
  }
  document.getElementById('daydellink').href = 'diensttag_loeschen.php?d=' + d.day_id;
  document.getElementById('daydatelink').href = 'diensttag_datum.php?d=' + d.day_id;
  document.getElementById('daymergelink').href = 'diensttag_zusammenfuehren.php?d=' + d.day_id;
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
  /* Kopfzeile des aufklappbaren Blocks: die EINGEFRORENEN Bezeichnungen aus
     dem Diensttag (E8), nie die heutigen Stammdaten. Ein umbenanntes
     Rettungsmittel ändert an einem dokumentierten Dienst nichts (A4). */
  const parts = [];
  if (d.meta) {
    if (d.meta.vehicle_name) parts.push(d.meta.vehicle_name);
    if (d.meta.base_name) parts.push(d.meta.base_name);
    (d.meta.crew || []).forEach(c => { if (c.name) parts.push(c.name); });
  }
  document.getElementById('metahint').textContent = parts.length ? '— ' + parts.join(' · ') : '';
  document.getElementById('metanotes').textContent =
    (d.meta && d.meta.notes) ? d.meta.notes : '';
  document.getElementById('savestate').textContent = '';
  document.getElementById('addmission').href = 'einsatz_form.php?d=' + d.day_id;

  layerGroup.clearLayers();
  trackLines.length = 0;
  const bounds = [];

  // Ruhe-Track: schwarz, dezent
  d.rest_segments.forEach(seg => {
    if (seg.length > 1) {
      const rl = L.polyline(seg, { color:'#8A8378', weight: Math.max(3, trackWeight() - 1),
        opacity:0.9, smoothFactor:0 });
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
    m._col = COLORS[i % COLORS.length];
    return m;
  });
  currentBase = (d.meta && d.meta.base_lat != null && d.meta.base_lon != null)
    ? { lat: d.meta.base_lat, lon: d.meta.base_lon } : null;
  d.missions.forEach(m => {
    if (m.track.length > 1) {
      const line = L.polyline(m.track, { color: m._col, weight: trackWeight(), smoothFactor: 0 });
      layerGroup.addLayer(line);
      trackLines.push(line);
      m.track.forEach(p => bounds.push(p));
    }
    /* Zielklinik-Pin: Klartext, also ohne Freischalten (E40, A13o). Er steht
       hier und nicht in entschluesselePat() — dort landet nur, was den
       Schlüssel braucht. */
    if (m.dest_lat != null && m.dest_lon != null) {
      layerGroup.addLayer(L.marker([m.dest_lat, m.dest_lon],
        { icon: locPin(EdLuftlinie.FARBE), keyboard: false })
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
    map.fitBounds(L.latLngBounds(bounds),
      { padding: [px.y * 0.125, px.x * 0.125], maxZoom: 15 });
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
        layerGroup.addLayer(L.marker([o.loc.lat, o.loc.lon],
          { icon: locPin(m._col), keyboard: false })
          .bindPopup(`Einsatz ${m._no}<br>` + esc(o.loc.addr)));
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
        layerGroup.addLayer(L.marker([abfahrt.lat, abfahrt.lon],
          { icon: locPin(m._col), keyboard: false })
          .bindPopup(`Einsatz ${m._no}<br>Abfahrtort`
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
    lab.className = 'crewrole';
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

async function init(){
  document.getElementById('vehsel').addEventListener('change', vehicleBaseSync);
  document.getElementById('unlockbtn').addEventListener('click', () => entschluesselePat());
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
