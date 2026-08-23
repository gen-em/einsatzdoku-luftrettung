<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
require_once __DIR__ . '/diensttag_lib.php';   // dt_art_symbole() fuer die Tabelle

/**
 * Alle Einsaetze eines Jahres oder Monats: Karte, Statistiktabelle und eine
 * Tabelle aller Einsaetze — bewusst ohne Farbmarkierung und ohne Tagesnummer,
 * dafuer mit Datum. Die Daten holt der Browser von api/range.php und
 * entschluesselt die geschuetzten Angaben selbst (wie auf der Tagesuebersicht);
 * die Karten-Pins nutzen dieselben entschluesselten Koordinaten. Die Karte
 * bleibt ausgeblendet, wenn kein Einsatz Koordinaten hat oder der
 * Inhaltsschluessel gesperrt ist.
 */

$jahr  = (string)($_GET['y'] ?? '');
$monat = (string)($_GET['m'] ?? '');
if (!preg_match('/^\d{4}$/', $jahr)) { header('Location: index.php'); exit; }
if ($monat !== '' && !preg_match('/^\d{2}$/', $monat)) { $monat = ''; }

$MONATSNAMEN = ['', 'Januar', 'Februar', 'März', 'April', 'Mai', 'Juni',
    'Juli', 'August', 'September', 'Oktober', 'November', 'Dezember'];
$titel = $monat !== ''
    ? $MONATSNAMEN[(int)$monat] . ' ' . $jahr
    : 'Jahr ' . $jahr;
ui_seite_start(['titel' => $titel, 'karte' => true]);
ui_topbar('uebersicht');
?>
<div class="layout">
  <?php ui_days_sidebar(null); ?>

  <main class="page">
    <h1><?= e($titel) ?></h1>
    <div id="loaderror" class="alert" hidden></div>

    <p id="lockbanner" class="alert alert-info" hidden>
      Geschützte Angaben sind gesperrt — Einsatzort, Alter und Diagnose bleiben
      verborgen, bis die Verschlüsselung entsperrt ist.
      <button type="button" class="btn-plain unlockbtn" id="unlockbtn">Entsperren</button>
    </p>

    <?php /* TABLEISTE NACH ART (E28, A13a). Sie entsteht erst im Browser und
             bleibt verborgen, solange im Zeitraum nur EINE Art vorliegt — dann
             gäbe es nichts zu wählen. Die Beschriftungen stehen hier und nicht
             im Skript, damit sie ohne JavaScript im Quelltext auffindbar
             sind. */ ?>
    <div class="arttabs" id="arttabs" role="tablist"
         aria-label="Ansicht nach Art des Diensttags" hidden>
      <button type="button" class="arttab" data-tab="mix" role="tab"
              id="tab-mix" aria-selected="true">Gemischt</button>
      <button type="button" class="arttab" data-tab="air" role="tab"
              id="tab-air" aria-selected="false">Luftrettung</button>
      <button type="button" class="arttab" data-tab="ground" role="tab"
              id="tab-ground" aria-selected="false">Bodengebundener Rettungsdienst</button>
    </div>

    <div id="tabpanel" role="tabpanel" aria-labelledby="tab-mix">
      <?php /* Der Hinweis auf neutrale Diensttage (E31). Ohne ihn wäre nicht
               erklärbar, warum die Summe der beiden Artentabs kleiner ist als
               „Gemischt". */ ?>
      <p id="neutralhinweis" class="muted neutralhinweis" hidden></p>

      <div id="rangemap" class="map" hidden></div>

      <?php /* Die Kacheln entstehen im Browser: Welche es gibt und wie sie
               heissen, hängt vom Tab ab (E32, E33) und bei den Windenkacheln
               zusätzlich vom Bestand (E30, A13d). */ ?>
      <div class="stats-grid" id="statsgrid" hidden></div>

      <!-- Spalten, Sortierung und Zeilenaufbau kommen aus assets/missiontable.js,
           gemeinsam mit suche.php. Kopf und Rumpf bleiben hier leer. -->
      <table class="data" id="rangetable">
        <thead></thead>
        <tbody id="rangebody"></tbody>
      </table>
      <p id="leer" class="muted" hidden>In diesem Zeitraum sind keine Einsätze erfasst.</p>
    </div>
    <?php ui_footer(); ?>
  </main>
</div>

<?php ui_krypto_bootstrap(); ?>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<?php /* Die Artsymbole VOR der Tabelle setzen — sie stammen aus
         dt_art_symbole() (diensttag_lib.php) und sind damit dieselben wie in
         der Tagesleiste. Gleiches Muster wie CREW_ROLLEN in import.php
         (Befund P9); assets/missiontable.js führt einen Rückfall, falls die
         Vorgabe fehlt. */ ?>
<script>const ART_SYMBOLE = <?= json_encode(dt_art_symbole(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script>
const JAHR  = <?= json_encode($jahr) ?>;
const MONAT = <?= json_encode($monat) ?>;

// Karte bleibt ausgeblendet (CSS [hidden]), bis feststeht, dass mindestens
// ein Pin gezeichnet wird — preferCanvas fuer performantes Rendering bei
// mehreren hundert Einsaetzen.
const map = L.map('rangemap', { preferCanvas: true });
/* AUSGANGSAUSSCHNITT SETZEN, BEVOR EIN PIN DAZUKOMMT.
 *
 * Ohne ihn gilt die Karte als "noch nicht bereit": Leaflet nimmt eine Ebene
 * dann zwar entgegen, stellt sie aber zurueck (whenReady) und rechnet ihre
 * Bildschirmposition NICHT aus. Ein spaeteres setStyle() auf so einen Pin
 * scheitert mit "this._point is undefined" — genau das stand beim Aufbau der
 * Zeitraumansicht in der Browser-Konsole, weil die Hervorhebung nach jedem
 * Neuzeichnen der Tabelle ueber alle Pins laeuft, waehrend fitBounds() erst
 * danach kommt.
 *
 * index.php loest das seit jeher mit derselben Zeile. Der Ausschnitt ist ein
 * Platzhalter; fitBounds() ueberschreibt ihn, sobald die Pins stehen. */
map.setView([48.5, 10.5], 7);   // Fallback, bis Daten da sind
attachBaseLayers(map);
attachFullscreenControl(map);

let missions = [];
let fixierteMid = null;   // per Klick festgesetzte Einsatz-ID oder null

/* Diensttage des Zeitraums, gesamt und nach Art (api/range.php). Sie zaehlen
   auch Diensttage OHNE Einsatz mit und sind der Divisor der Durchschnitte —
   deshalb kommen sie aus der Datenbank und nicht aus der Einsatzliste. */
let tageGesamt = 0;
let tageArt = { air: 0, ground: 0, neutral: 0 };

/* Tableiste und gewaehlte Ansicht (E28).
 *
 * `tabsAn` sagt, ob es ueberhaupt etwas zu waehlen gibt: nur wenn im Zeitraum
 * BEIDE Arten vorliegen. `ansicht` ist dann der gewaehlte Tab; ohne Tableiste
 * ist es die eine vorhandene Art und bestimmt allein die BESCHRIFTUNG — die
 * Ansicht zeigt in diesem Fall alles, auch die neutralen Diensttage. */
let tabsAn = false;
let ansicht = 'mix';

const FARBE_HERVOR = '#D63338';  // Newroz Rot
const FARBE_NORMAL  = '#4280E5'; // Max Blau

/* Alle Pins in EINER Ebene. Der Tab filtert die Karte mit (A13b), und eine
   Ebene laesst sich leeren, ohne die Karte selbst anzufassen. */
const pinLayer = L.layerGroup().addTo(map);

// Formatierung und Ortsauswertung teilen sich beide Uebersichten; die
// Definitionen stehen in assets/missiontable.js.
const esc        = EdMissionTable.esc;
const fmtTag     = EdMissionTable.fmtTag;
const fmtDur     = EdMissionTable.fmtDur;
const extractOrt = EdMissionTable.extractOrt;
function fmtDe1(n){ return n.toFixed(1).replace('.', ','); }

function fmtKmDe(meter){ return (meter / 1000).toFixed(1).replace('.', ',') + ' km'; }

/* ====================================================================
 * KACHELN JE TAB (Abschnitt 3.7.2, E32/E33).
 *
 * Die Beschriftungen sind tababhaengig: Der Luftrettungs-Tab behaelt die
 * gewohnte Flugterminologie, die uebrigen sprechen neutral. Fuer eine rein
 * luftgebundene Nutzung aendert sich an der Auswertung damit nichts — genau
 * das ist der Zweck (A13f).
 *
 * Ein Eintrag je Kachel:
 *   id       Kennung, nur fuer die Hervorhebung und zum Wiederfinden
 *   label    Beschriftung (tababhaengig — deshalb steht sie HIER und nicht
 *            einmal im HTML)
 *   text     (k) => Anzeigewert; `k` sind die einmal gerechneten Kennzahlen
 *   extrem   Name des Extremwerts in `k`; macht die Kachel anklickbar und
 *            verknuepft sie mit ihrem Traeger-Einsatz
 *   nurWenn  (liste) => bool, datengetriebene Sichtbarkeit (E30, A13d)
 * ================================================================== */

/* Die acht neutralen Kacheln. Sie gelten fuer den bodengebundenen Tab UND
   fuer „Gemischt" — dieselbe Menge, dieselben Worte (E33). Zwei Saetze mit
   identischem Inhalt waeren zwei Stellen, an denen die naechste Aenderung
   nur zur Haelfte ankaeme. Hoechster Einsatzort und Windenzahlen fehlen hier,
   weil sie sich ueber beide Arten nicht sinnvoll addieren lassen. */
const KACHELN_NEUTRAL = [
  { id: 'missioncount', label: 'Einsätze',                 text: k => String(k.n) },
  { id: 'tage',         label: 'Diensttage',               text: k => String(k.tage) },
  { id: 'avgmissions',  label: 'Ø Einsätze / Diensttag',   text: k => k.tage > 0 ? fmtDe1(k.n / k.tage) : '–' },
  { id: 'secondary',    label: 'Sekundärtransporte',       text: k => String(k.sek) },
  /* Die einzige Kachel, die es im Luftrettungs-Tab NICHT gibt (E32/A13f),
     obwohl der Haken auch luftgebunden zur Verfuegung steht. In „Gemischt"
     zaehlt sie luftgebundene Fehleinsaetze mit — die Zahl bleibt dadurch
     vollstaendig. */
  { id: 'fehl',         label: 'Fehleinsätze',             text: k => String(k.fehl) },
  { id: 'totalkm',      label: 'Einsatzkilometer gesamt',  text: k => fmtKmDe(k.km) },
  { id: 'maxkm',        label: 'Längste Einsatzstrecke',   extrem: 'maxKm',
    text: k => k.maxKm.wert != null ? fmtKmDe(k.maxKm.wert) : '–' },
  { id: 'maxdauer',     label: 'Längste Einsatzdauer',     extrem: 'maxDauer',
    text: k => k.maxDauer.wert != null ? fmtDur(k.maxDauer.wert) : '–' }
];

/* Die zehn Kacheln der Luftrettung — der heutige Bestand, unveraendert in
   Beschriftung und Umfang (A13f). Die beiden Windenkacheln stehen am ENDE,
   weil sie als einzige verschwinden koennen: eine Luecke mitten im Raster
   waere schwerer zu lesen als eine kuerzere letzte Reihe. */
const KACHELN_LUFT = [
  { id: 'missioncount', label: 'Einsätze',                 text: k => String(k.n) },
  { id: 'tage',         label: 'Flugtage',                 text: k => String(k.tage) },
  { id: 'avgmissions',  label: 'Ø Einsätze / Flugtag',     text: k => k.tage > 0 ? fmtDe1(k.n / k.tage) : '–' },
  { id: 'secondary',    label: 'Sekundärtransporte',       text: k => String(k.sek) },
  { id: 'totalkm',      label: 'Flugkilometer gesamt',     text: k => fmtKmDe(k.km) },
  { id: 'maxkm',        label: 'Längste Flugstrecke',      extrem: 'maxKm',
    text: k => k.maxKm.wert != null ? fmtKmDe(k.maxKm.wert) : '–' },
  { id: 'maxdauer',     label: 'Längste Einsatzdauer',     extrem: 'maxDauer',
    text: k => k.maxDauer.wert != null ? fmtDur(k.maxDauer.wert) : '–' },
  { id: 'maxhoehe',     label: 'Höchster Einsatzort',      extrem: 'maxHoehe',
    text: k => k.maxHoehe.wert != null ? k.maxHoehe.wert + ' m' : '–' },
  /* NUR BEI TATSAECHLICHEN WINDENEINSAETZEN (E30, A13d) — nicht schon, wenn
     das Rettungsmittel es koennte. Damit laesst sich „null Windeneinsaetze"
     nicht mehr von „Winde nicht eingerichtet" unterscheiden; das ist gewollt,
     weil eine Dauerkachel mit dem Wert null nur Platz kostet. */
  { id: 'winchcycles',  label: 'Anzahl Winden-Cycles',     text: k => String(k.winden),
    nurWenn: liste => liste.some(m => m.winch) },
  { id: 'avgwinch',     label: 'Ø Winden-Cycles / Flugtag',
    text: k => k.tage > 0 ? fmtDe1(k.winden / k.tage) : '–',
    nurWenn: liste => liste.some(m => m.winch) }
];

const KACHELSATZ = { air: KACHELN_LUFT, ground: KACHELN_NEUTRAL, mix: KACHELN_NEUTRAL };

/* Alle Kennzahlen in EINEM Durchlauf. Sie kommen unverschluesselt aus
 * api/range.php, sind also sofort verfuegbar — unabhaengig von der lokalen
 * Entschluesselung der geschuetzten Felder (Ort/Alter/Diagnose).
 *
 * Gleichstand bei den Extremwerten: Es gewinnt der zuerst gefundene Einsatz —
 * api/range.php liefert ORDER BY started_at, also der zeitlich frueheste.
 * Deshalb strikt "grösser als" statt "grösser gleich" vergleichen. */
function rechne(liste, tage){
  const k = { n: liste.length, tage: tage, winden: 0, km: 0, sek: 0, fehl: 0,
              maxKm:    { mid: null, wert: null },
              maxDauer: { mid: null, wert: null },
              maxHoehe: { mid: null, wert: null } };
  liste.forEach(m => {
    k.winden += m.winch_cycles || 0;
    k.km     += m.distance_m || 0;
    if (m.secondary)   { k.sek++; }
    if (m.false_alarm) { k.fehl++; }
    if (m.distance_m != null && (k.maxKm.wert == null || m.distance_m > k.maxKm.wert)) {
      k.maxKm = { mid: m.id, wert: m.distance_m };
    }
    if (m.duration_s != null && (k.maxDauer.wert == null || m.duration_s > k.maxDauer.wert)) {
      k.maxDauer = { mid: m.id, wert: m.duration_s };
    }
    if (m.site_ele_m != null && (k.maxHoehe.wert == null || m.site_ele_m > k.maxHoehe.wert)) {
      k.maxHoehe = { mid: m.id, wert: m.site_ele_m };
    }
  });
  return k;
}

/* Baut das Kachelraster neu auf. NEU AUFBAUEN statt beschriften: Welche
 * Kacheln es gibt, haengt am Tab und am Bestand — ein fester Satz im HTML
 * muesste dieselbe Entscheidung ein zweites Mal treffen, und die Ereignisse
 * der Extremwert-Kacheln haengen an Elementen, die es je nach Tab gar nicht
 * gibt. Die Ereignisse werden deshalb hier vergeben, beim Erzeugen. */
function zeichneStatistik(liste, tage){
  const k    = rechne(liste, tage);
  const grid = document.getElementById('statsgrid');
  grid.innerHTML = '';
  KACHELSATZ[ansicht].forEach(def => {
    if (def.nurWenn && !def.nurWenn(liste)) { return; }
    const tile = document.createElement('div');
    tile.className = 'stat-tile';
    tile.dataset.kachel = def.id;
    const wert = document.createElement('span');
    wert.className = 'stat-value';
    wert.textContent = def.text(k);
    const lab = document.createElement('span');
    lab.className = 'stat-label';
    lab.textContent = def.label;
    tile.appendChild(wert);
    tile.appendChild(lab);
    /* Extremwert-Kacheln behalten ihr bisheriges Verhalten: OHNE Kandidat
       bleiben sie stumm statt zu verschwinden (Konzept 4.6) — sie zeigen
       einen Gedankenstrich und sind nicht anklickbar. */
    if (def.extrem && k[def.extrem].mid != null) {
      tile.dataset.mid = k[def.extrem].mid;
      tile.classList.add('stat-tile-link');
      verdrahteExtremKachel(tile);
    }
    grid.appendChild(tile);
  });
  grid.hidden = false;
}

/* Hervorhebung des Traeger-Einsatzes: ueberfahren zeigt, klicken setzt fest.
 * Die Kacheln entstehen bei jedem Zeichnen neu, die Ereignisse also auch —
 * eine Delegation am Raster koennte `mouseenter` nicht nutzen (es steigt
 * nicht auf), und `mouseover` feuert zusaetzlich bei jedem Wechsel zwischen
 * Wert und Beschriftung innerhalb derselben Kachel. */
function verdrahteExtremKachel(tile){
  const mid = Number(tile.dataset.mid);
  tile.addEventListener('mouseenter', () => wendeHervorhebungAn(mid));
  tile.addEventListener('mouseleave', () => wendeHervorhebungAn(fixierteMid));
  tile.addEventListener('click', () => {
    if (fixierteMid === mid) { loeseFixierung(); return; }
    document.querySelectorAll('.stat-tile-link.aktiv').forEach(t => t.classList.remove('aktiv'));
    fixierteMid = mid;
    tile.classList.add('aktiv');
    wendeHervorhebungAn(mid);
    const zeile = document.querySelector(`#rangebody tr[data-mid="${mid}"]`);
    if (zeile) { zeile.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
  });
}

// Wendet den zur angegebenen Einsatz-ID gehoerenden Hervorhebungszustand auf
// Tabellenzeile und Karten-Pin an (mid === null loescht jede Hervorhebung).
// Einzige Stelle, die Hervorhebung anwendet — kein Aufaddieren von Klassen.
function wendeHervorhebungAn(mid){
  document.querySelectorAll('#rangebody tr.hl-extrem').forEach(tr => tr.classList.remove('hl-extrem'));
  if (mid != null) {
    const zeile = document.querySelector(`#rangebody tr[data-mid="${mid}"]`);
    if (zeile) { zeile.classList.add('hl-extrem'); }
  }
  missions.forEach(m => {
    if (!m._marker) return;   // regulaer: keine Koordinaten oder Inhaltsschluessel gesperrt
    if (mid != null && m.id === mid) {
      m._marker.setStyle({ fillColor: FARBE_HERVOR, radius: 9 });
      m._marker.bringToFront();
    } else {
      m._marker.setStyle({ fillColor: FARBE_NORMAL, radius: 6, weight: 2, color: '#fff' });
    }
  });
}

function loeseFixierung(){
  if (fixierteMid == null) return;
  fixierteMid = null;
  document.querySelectorAll('.stat-tile-link.aktiv').forEach(t => t.classList.remove('aktiv'));
  wendeHervorhebungAn(null);
}

document.addEventListener('click', ev => {
  if (ev.target.closest('.stat-tile')) { return; }   // Kacheln haben eigene Logik
  if (ev.target.closest('.leaflet-marker-icon, .leaflet-interactive')) { return; }
  loeseFixierung();
});

/* Die Trefferliste selbst (Spalten, Sortierung, Zeilenaufbau, Klick auf die
 * Zeile) steckt in assets/missiontable.js und wird mit suche.php geteilt.
 * Hier bleibt nur, was zu dieser Seite gehoert: das Ein-/Ausblenden von
 * Tabelle und Leermeldung sowie die Hervorhebung aus den Extremwert-Kacheln,
 * die nach jedem Neuzeichnen erneut angewendet werden muss — die Zeilen sind
 * dann neu und haetten ihre Markierung sonst verloren.
 */
const tabelle = EdMissionTable.erzeuge({
  table: document.getElementById('rangetable'),
  sortKey: 'day', sortAsc: true,
  onAfterDraw: (gesamt) => {
    document.getElementById('leer').hidden = gesamt > 0;
    document.getElementById('rangetable').hidden = gesamt === 0;
    wendeHervorhebungAn(fixierteMid);
  }
});

/* ====================================================================
 * Tabs nach Art (Abschnitt 3.7.1).
 *
 * Die Tableiste erscheint NUR, wenn im Zeitraum beide Arten vorliegen (E28) —
 * ein einzelner Tab waere eine Wahl ohne Alternative. Liegt nur eine Art vor,
 * bestimmt sie allein die Beschriftung der Kacheln; gezeigt wird trotzdem
 * ALLES, einschliesslich der Einsaetze neutraler Diensttage. Sonst fehlten sie
 * in der einzigen Ansicht, die es dann gibt.
 * ================================================================== */

/** Die Einsaetze der gewaehlten Ansicht (A13b: Kacheln, Tabelle und Karte). */
function gefiltert(){
  if (!tabsAn) { return missions; }
  if (ansicht === 'air')    { return missions.filter(m => m.kind === 'air'); }
  if (ansicht === 'ground') { return missions.filter(m => m.kind === 'ground'); }
  return missions;   // „Gemischt" enthaelt auch die neutralen Diensttage (E31)
}

/** Divisor der Durchschnitte: die Diensttage der gewaehlten Ansicht. */
function tageDerAnsicht(){
  if (!tabsAn) { return tageGesamt; }
  if (ansicht === 'air')    { return tageArt.air; }
  if (ansicht === 'ground') { return tageArt.ground; }
  return tageGesamt;
}

/* Der Hinweis auf neutrale Diensttage (E31). Er steht ueberall dort, wo
 * neutrale Diensttage MITGEZAEHLT werden — in „Gemischt" und in einer Ansicht
 * ohne Tableiste. In den beiden Artentabs nicht: Dort sind sie nicht dabei,
 * und ein Hinweis auf etwas Nichtgezaehltes verwirrt mehr, als er erklaert. */
function zeigeNeutralHinweis(){
  const p = document.getElementById('neutralhinweis');
  const dabei = !tabsAn || ansicht === 'mix';
  if (!dabei || tageArt.neutral === 0) { p.hidden = true; return; }
  const n = tageArt.neutral;
  p.innerHTML = (n === 1
      ? 'Ein Diensttag dieses Zeitraums ist mitgezählt, aber noch keiner Art zugeordnet'
      : `${n} Diensttage dieses Zeitraums sind mitgezählt, aber noch keiner Art zugeordnet`)
    + ' — ihnen fehlt Standort oder Rettungsmittel. '
    + '<a href="nachbearbeitung.php">Zuordnung nachtragen</a>';
  p.hidden = false;
}

/** Tableiste beschriften und den aktiven Tab markieren. */
function zeichneTabs(){
  const leiste = document.getElementById('arttabs');
  leiste.hidden = !tabsAn;
  if (!tabsAn) { return; }
  leiste.querySelectorAll('.arttab').forEach(b => {
    const an = b.dataset.tab === ansicht;
    b.classList.toggle('aktiv', an);
    b.setAttribute('aria-selected', an ? 'true' : 'false');
    // Nur der aktive Tab ist mit der Tabulatortaste erreichbar; zwischen den
    // Tabs wird mit den Pfeiltasten gewechselt (uebliche Bedienung einer
    // Tableiste).
    b.tabIndex = an ? 0 : -1;
  });
  document.getElementById('tabpanel').setAttribute('aria-labelledby', 'tab-' + ansicht);
}

/** Alles neu zeichnen, was am Tab haengt: Kacheln, Tabelle, Karte, Hinweis. */
function zeichne(){
  const liste = gefiltert();
  zeichneTabs();
  zeigeNeutralHinweis();
  /* Die Spaltensichtbarkeit der Tabelle richtet sich nach DIESER Liste: Im
     bodengebundenen Tab gibt es keine Windeneinsaetze, also auch keine
     Windenspalte (A13d). */
  tabelle.setSpaltenBestand(liste);
  tabelle.setData(liste);
  zeichneStatistik(liste, tageDerAnsicht());
  zeichneKarte(liste);
}

/* Karte neu bestuecken. Die Pins entstehen erst nach dem Entschluesseln —
 * vorher hat kein Einsatz Koordinaten, und die Karte bleibt ausgeblendet.
 * Beim Tabwechsel werden sie verworfen und neu gesetzt, statt sie zu
 * verstecken: Ein Pin, der nicht auf der Karte liegt, hat keine
 * Bildschirmposition, und ein spaeteres setStyle() aus der Hervorhebung
 * scheiterte daran ("this._point is undefined"). */
function zeichneKarte(liste){
  pinLayer.clearLayers();
  missions.forEach(m => { m._marker = null; });
  const bounds = [];
  liste.forEach(m => {
    if (m._lat == null) { return; }
    m._marker = L.circleMarker([m._lat, m._lon], {
      radius: 6, weight: 2, color: '#fff', fillColor: FARBE_NORMAL, fillOpacity: 1
    }).addTo(pinLayer).bindPopup(`${fmtTag(m.day)}<br>${esc(m._addr || '')}`);
    bounds.push([m._lat, m._lon]);
  });
  const karte = document.getElementById('rangemap');
  karte.hidden = bounds.length === 0;
  if (bounds.length) {
    // Die Karte war bis hierhin ausgeblendet (display:none) -> ihre Groesse
    // war beim Initialisieren unbekannt; invalidateSize() vor fitBounds ist
    // Pflicht, sonst bleiben die Kacheln grau oder falsch zugeschnitten.
    map.invalidateSize();
    map.fitBounds(bounds, { padding: [30, 30], maxZoom: 15 });
  }
}

/* Tabwechsel. Die Festsetzung aus einer Extremwert-Kachel wird dabei gelöst —
 * sie zeigte auf einen Einsatz, den der neue Tab womoeglich gar nicht
 * enthaelt. */
function setzeAnsicht(neu){
  if (!tabsAn || neu === ansicht) { return; }
  ansicht = neu;
  fixierteMid = null;
  zeichne();
  fragmentSchreiben();
}

/* DER TAB STEHT IM URL-FRAGMENT, nicht als Abfrageparameter (A13b) — wie der
 * gesamte Filterzustand der Suche. Fragmente werden nicht an den Server
 * gesendet und landen damit nicht im Zugriffsprotokoll; ausserdem ist der Tab
 * eine Frage der Ansicht, nicht der Daten, die api/range.php liefert.
 * replaceState statt location.hash: sonst waechst die Chronik mit jedem
 * Tabwechsel. */
function fragmentSchreiben(){
  if (!tabsAn) { return; }
  history.replaceState(null, '', location.pathname + location.search + '#t=' + ansicht);
}

function fragmentLesen(){
  const p = new URLSearchParams(location.hash.replace(/^#/, ''));
  const t = p.get('t');
  // Ein Tab, den es in diesem Zeitraum nicht gibt, wird still verworfen —
  // ein geteilter Link kann aus einem Zeitraum mit beiden Arten stammen.
  if (tabsAn && (t === 'mix' || t === 'air' || t === 'ground')) { ansicht = t; }
}

/* Ein von Hand geaendertes oder eingefuegtes Fragment gilt auch dann, wenn die
 * Seite schon offen ist: Ein Wechsel von `#t=ground` auf `#t=air` ist fuer den
 * Browser KEINE neue Seite, es wird also nicht neu geladen. Ohne diese Zeile
 * stuende in der Adresszeile ein anderer Tab als auf dem Bildschirm. */
window.addEventListener('hashchange', () => {
  const vorher = ansicht;
  fragmentLesen();
  if (ansicht !== vorher) { fixierteMid = null; zeichne(); }
});

document.querySelectorAll('.arttab').forEach(b => {
  b.addEventListener('click', () => setzeAnsicht(b.dataset.tab));
});
/* Pfeiltasten in der Tableiste. Ohne sie waere die Leiste zwar erreichbar,
   aber nur der aktive Tab — die uebrigen sind bewusst aus der
   Tabulatorreihenfolge genommen. */
document.getElementById('arttabs').addEventListener('keydown', ev => {
  if (ev.key !== 'ArrowLeft' && ev.key !== 'ArrowRight') { return; }
  const tabs = [...document.querySelectorAll('.arttab')];
  const i = tabs.findIndex(b => b.dataset.tab === ansicht);
  if (i < 0) { return; }
  ev.preventDefault();
  const j = (i + (ev.key === 'ArrowRight' ? 1 : tabs.length - 1)) % tabs.length;
  setzeAnsicht(tabs[j].dataset.tab);
  tabs[j].focus();
});

function zeigeFehler(msg){
  const box = document.getElementById('loaderror');
  box.textContent = 'Die Daten konnten nicht geladen werden: ' + msg;
  box.hidden = false;
}

(async () => {
  let d;
  try {
    const url = 'api/range.php?y=' + encodeURIComponent(JAHR)
              + (MONAT ? '&m=' + encodeURIComponent(MONAT) : '');
    const res = await fetch(url);
    const txt = await res.text();
    try { d = JSON.parse(txt); }
    catch (e) {
      zeigeFehler(txt.replace(/<[^>]*>/g, ' ').trim().slice(0, 300) || ('HTTP ' + res.status));
      return;
    }
    if (d.error) { zeigeFehler(d.error + (d.meldung ? ': ' + d.meldung : '')); return; }
  } catch (e) { zeigeFehler(e.message); return; }

  missions = d.missions;
  tageGesamt = d.tage || 0;
  tageArt = d.tage_art || { air: 0, ground: 0, neutral: 0 };

  /* Welche Ansicht es gibt, entscheiden die DIENSTTAGE des Zeitraums, nicht
     die Einsaetze (E28): Ein bodengebundener Dienst ohne einen einzigen
     Einsatz ist trotzdem ein bodengebundener Dienst, und die Kachel
     „Diensttage" zaehlt ihn. */
  if (tageArt.air > 0 && tageArt.ground > 0) { tabsAn = true; ansicht = 'mix'; }
  else if (tageArt.air > 0)    { ansicht = 'air'; }
  else if (tageArt.ground > 0) { ansicht = 'ground'; }
  else { ansicht = 'mix'; }   // nur neutrale Diensttage oder gar keine
  fragmentLesen();

  zeichne();
  // Den Tab von Anfang an ins Fragment schreiben, nicht erst beim ersten
  // Wechsel: Sonst zeigte ein sofort kopierter Link auf keinen bestimmten Tab.
  fragmentSchreiben();

  if (PAT_WRAP) { await entschluesselePat(); }
})();

/* Geschuetzte Angaben nachtragen. Ist der Inhaltsschluessel gesperrt, bietet
 * EdUnlock den Entsperrdialog an; bei Abbruch bleibt es beim Sperrhinweis,
 * dessen Knopf diese Funktion erneut aufruft. Ein zweiter Durchlauf ist
 * gefahrlos: ohne Schluessel wurde vorher kein Pin gezeichnet. */
async function entschluesselePat(){
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  const banner = document.getElementById('lockbanner');
  if (!ck) { banner.hidden = !missions.some(m => m.pat_blob); return; }
  banner.hidden = true;
  // Entschluesseln und zaehlen an einer Stelle (M6-06, Baustein B8).
  const zahl = await EdPat.entschluessleListe(missions, ck);
  for (const m of missions) {
    if (m._patState !== 'ok') { continue; }
    const o = m._pat;
    if (o.dx != null) { m._dx = o.dx; }
    const alter = EdPat.alterAnzeige(o, m.day);   // Alter zum jeweiligen Einsatztag
    if (alter != null) { m._age = alter; }
    if (o.loc && o.loc.addr) { m._ort = extractOrt(o.loc.addr); }
    /* Die Koordinate wird am Einsatz VERMERKT, der Pin aber erst in
       zeichneKarte() gesetzt: Welche Pins auf der Karte liegen, entscheidet
       der Tab, und der kann nach dem Entschluesseln noch wechseln. */
    if (o.loc && o.loc.lat != null) {
      m._lat = o.loc.lat; m._lon = o.loc.lon; m._addr = o.loc.addr;
    }
  }
  EdPat.zeigeUnlesbar(zahl);
  /* Immer neu zeichnen: Die Tabelle hat jetzt Ort, Alter und Diagnose, und
     die Karte ihre Pins. Ein „nur wenn sich etwas geaendert hat" waere hier
     eine zweite Buchfuehrung ueber dieselbe Schleife — die Ersparnis ist ein
     Neuaufbau der Tabelle, den niemand bemerkt. */
  zeichne();
}

document.getElementById('unlockbtn').addEventListener('click', () => entschluesselePat());
</script>
<?php ui_seite_ende(); ?>
