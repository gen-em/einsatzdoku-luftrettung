<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

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
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($titel) ?> · Einsatzdoku</title>
  <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>
<div class="layout">
  <?php ui_days_sidebar(null); ?>

  <main class="page">
    <h1><?= e($titel) ?></h1>
    <p class="muted" id="summary">wird geladen …</p>
    <div id="loaderror" class="alert" hidden></div>

    <p id="lockbanner" class="alert alert-info" hidden>
      Geschützte Angaben sind gesperrt — bitte neu anmelden, um Einsatzort,
      Alter und Diagnose zu sehen.
    </p>

    <div id="rangemap" class="map" hidden></div>

    <div class="stats-grid" id="statsgrid" hidden>
      <div class="stat-tile"><span class="stat-value" id="st-avgmissions">–</span>
        <span class="stat-label">Ø Einsätze / Flugtag</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-avgwinch">–</span>
        <span class="stat-label">Ø Winden / Flugtag</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-winchcount">–</span>
        <span class="stat-label">Windeneinsätze</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-missioncount">–</span>
        <span class="stat-label">Einsätze</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-secondary">–</span>
        <span class="stat-label">Sekundärtransporte</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-maxkm">–</span>
        <span class="stat-label">Längste Flugstrecke</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-maxdauer">–</span>
        <span class="stat-label">Längste Einsatzdauer</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-maxhoehe">–</span>
        <span class="stat-label">Höchster Einsatzort</span></div>
    </div>

    <table class="data" id="rangetable">
      <thead><tr>
        <th class="sortable c-date" data-key="day">Datum</th>
        <th class="sortable c-mid"  data-key="start">Beginn</th>
        <th class="sortable c-mid"  data-key="dur">Dauer</th>
        <th class="sortable"        data-key="site">Einsatzort</th>
        <th class="sortable c-mid"  data-key="age">Alter</th>
        <th class="sortable"        data-key="dx">Diagnose</th>
        <th class="sortable c-winde" data-key="winch">Winde</th>
        <th class="sortable c-bw"    data-key="bw">Bergwacht</th>
        <th class="sortable c-sek"   data-key="sec">Sekundär<br>Transport</th>
        <th class="sortable c-mid"   data-key="km">Flug&nbsp;km</th>
      </tr></thead>
      <tbody id="rangebody"></tbody>
    </table>
    <p id="leer" class="muted" hidden>In diesem Zeitraum sind keine Einsätze erfasst.</p>
    <?php ui_footer(); ?>
  </main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script>
const JAHR  = <?= json_encode($jahr) ?>;
const MONAT = <?= json_encode($monat) ?>;
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;

// Karte bleibt ausgeblendet (CSS [hidden]), bis feststeht, dass mindestens
// ein Pin gezeichnet wird — preferCanvas fuer performantes Rendering bei
// mehreren hundert Einsaetzen.
const map = L.map('rangemap', { preferCanvas: true });
attachBaseLayers(map);
attachFullscreenControl(map);

let missions = [];
let sortKey = 'day', sortAsc = true;

function esc(t){ const d = document.createElement('div'); d.textContent = t; return d.innerHTML; }
function fmtTag(iso){ const [y,m,d] = iso.split('-'); return `${d}.${m}.${y}`; }
function fmtDur(s){ if(s==null) return 'kein Ende'; const h=Math.floor(s/3600),m=Math.round(s%3600/60);
  return h? `${h}h ${String(m).padStart(2,'0')}min` : `${m}min`; }
function fmtKm(m){ return m==null ? '<span class="dash">–</span>' : (m/1000).toFixed(1).replace('.',',')+' km'; }
function extractOrt(addr){
  const parts = addr.split(',');
  let last = parts[parts.length - 1].trim();
  return last.replace(/^\d{4,5}\s+/, '');
}
function fmtDe1(n){ return n.toFixed(1).replace('.', ','); }

// Statistiktabelle: alle acht Kennzahlen kommen unverschluesselt aus
// api/range.php, sind also sofort verfuegbar — unabhaengig von der lokalen
// Entschluesselung der geschuetzten Felder (Ort/Alter/Diagnose).
function zeichneStatistik(liste, tage){
  const n = liste.length;
  const windenSumme = liste.reduce((s, m) => s + (m.winch_cycles || 0), 0);
  const distanzen = liste.map(m => m.distance_m).filter(v => v != null);
  const dauern    = liste.map(m => m.duration_s).filter(v => v != null);
  const hoehen    = liste.map(m => m.site_ele_m).filter(v => v != null);

  document.getElementById('st-avgmissions').textContent = tage > 0 ? fmtDe1(n / tage) : '–';
  document.getElementById('st-avgwinch').textContent    = tage > 0 ? fmtDe1(windenSumme / tage) : '–';
  document.getElementById('st-winchcount').textContent  = liste.filter(m => m.winch).length;
  document.getElementById('st-missioncount').textContent = n;
  document.getElementById('st-secondary').textContent   = liste.filter(m => m.secondary).length;
  document.getElementById('st-maxkm').textContent =
    distanzen.length ? (Math.max(...distanzen) / 1000).toFixed(1).replace('.', ',') + ' km' : '–';
  document.getElementById('st-maxdauer').textContent =
    dauern.length ? fmtDur(Math.max(...dauern)) : '–';
  document.getElementById('st-maxhoehe').textContent =
    hoehen.length ? Math.max(...hoehen) + ' m' : '–';
  document.getElementById('statsgrid').hidden = false;
}

function sortWert(m, key){
  switch(key){
    case 'day':   return m.day;
    case 'start': return m.start_hhmm;
    case 'dur':   return m.duration_s == null ? -1 : m.duration_s;
    case 'site':  return (m._ort || '').toLowerCase();
    case 'age':   return m._age == null ? -1 : m._age;
    case 'dx':    return (m._dx || '').toLowerCase();
    case 'winch': return m.winch ? 1 : 0;
    case 'bw':    return m.bergwacht ? 1 : 0;
    case 'sec':   return m.secondary ? 1 : 0;
    case 'km':    return m.distance_m == null ? -1 : m.distance_m;
  }
  return '';
}

function zeichne(){
  const tb = document.getElementById('rangebody');
  tb.innerHTML = '';
  const sortiert = missions.slice().sort((a,b) => {
    const x = sortWert(a, sortKey), y = sortWert(b, sortKey);
    const r = (x > y) - (x < y);
    return sortAsc ? r : -r;
  });
  sortiert.forEach(m => {
    const tr = document.createElement('tr');
    tr.className = 'clickable';
    tr.innerHTML =
      `<td class="mono c-date">${fmtTag(m.day)}</td>
       <td class="mono c-mid">${m.start_hhmm}</td>
       <td class="c-mid">${fmtDur(m.duration_s)}</td>
       <td${m._ort ? '' : ' class="dash"'}>${m._ort ? esc(m._ort) : '–'}</td>
       <td class="mono c-mid${m._age != null ? '' : ' dash'}">${m._age != null ? m._age : '–'}</td>
       <td${m._dx ? '' : ' class="dash"'}>${m._dx ? esc(m._dx) : '–'}</td>
       <td class="checkcol c-winde">${m.winch ? '✓' : ''}</td>
       <td class="checkcol c-bw">${m.bergwacht ? '✓' : ''}</td>
       <td class="checkcol c-sek">${m.secondary ? '✓' : ''}</td>
       <td class="mono c-mid">${fmtKm(m.distance_m)}</td>`;
    tr.addEventListener('click', () => { location.href = 'einsatz.php?id=' + m.id; });
    tb.appendChild(tr);
  });
  document.getElementById('leer').hidden = missions.length > 0;
  document.getElementById('rangetable').hidden = missions.length === 0;
}

document.querySelectorAll('#rangetable th.sortable').forEach(th => {
  th.addEventListener('click', () => {
    const k = th.dataset.key;
    if (sortKey === k) { sortAsc = !sortAsc; } else { sortKey = k; sortAsc = true; }
    document.querySelectorAll('#rangetable th .arrow').forEach(a => a.remove());
    const pfeil = document.createElement('span');
    pfeil.className = 'arrow';
    pfeil.textContent = sortAsc ? ' ▲' : ' ▼';
    th.appendChild(pfeil);
    zeichne();
  });
});

function zeigeFehler(msg){
  const box = document.getElementById('loaderror');
  box.textContent = 'Die Daten konnten nicht geladen werden: ' + msg;
  box.hidden = false;
  document.getElementById('summary').textContent = '';
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
  const km = missions.reduce((s, m) => s + (m.distance_m || 0), 0);
  document.getElementById('summary').textContent =
    `${missions.length} Einsätze an ${d.tage} Flugtagen · ${(km/1000).toFixed(1).replace('.', ',')} km`;
  zeichne();
  zeichneStatistik(missions, d.tage);

  if (PAT_WRAP) {
    const ck = await EdCrypto.getContentKey(PAT_WRAP);
    const banner = document.getElementById('lockbanner');
    if (!ck) { banner.hidden = !missions.some(m => m.pat_blob); return; }
    banner.hidden = true;
    let geaendert = false;
    const pinBounds = [];
    for (const m of missions) {
      if (!m.pat_blob) continue;
      try {
        const o = JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob)) || {};
        if (o.dx != null) { m._dx = o.dx; geaendert = true; }
        const alter = EdPat.alterAnzeige(o, m.day);   // Alter zum jeweiligen Einsatztag
        if (alter != null) { m._age = alter; geaendert = true; }
        if (o.loc && o.loc.addr) { m._ort = extractOrt(o.loc.addr); geaendert = true; }
        // Einheitlicher Pin (Max Blau) je Einsatzort; kein Clustering in v1.
        if (o.loc && o.loc.lat != null) {
          L.circleMarker([o.loc.lat, o.loc.lon], {
            radius: 6, weight: 2, color: '#fff', fillColor: '#4280E5', fillOpacity: 1
          }).addTo(map).bindPopup(`${fmtTag(m.day)}<br>${esc(o.loc.addr)}`);
          pinBounds.push([o.loc.lat, o.loc.lon]);
        }
      } catch (e) { /* einzelner Datensatz nicht lesbar: Rest trotzdem zeigen */ }
    }
    if (geaendert) { zeichne(); }
    if (pinBounds.length) {
      // Karte war bis hierhin ausgeblendet (display:none) -> Groesse war beim
      // Initialisieren unbekannt; invalidateSize() vor fitBounds ist Pflicht,
      // sonst bleiben die Kacheln grau/falsch zugeschnitten.
      document.getElementById('rangemap').hidden = false;
      map.invalidateSize();
      map.fitBounds(pinBounds, { padding: [30, 30], maxZoom: 15 });
    }
  }
})();
</script>
</body>
</html>
