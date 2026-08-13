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
    <div id="loaderror" class="alert" hidden></div>

    <p id="lockbanner" class="alert alert-info" hidden>
      Geschützte Angaben sind gesperrt — Einsatzort, Alter und Diagnose bleiben
      verborgen, bis die Verschlüsselung entsperrt ist.
      <button type="button" class="btn-plain unlockbtn" id="unlockbtn">Entsperren</button>
    </p>

    <div id="rangemap" class="map" hidden></div>

    <div class="stats-grid" id="statsgrid" hidden>
      <div class="stat-tile"><span class="stat-value" id="st-missioncount">–</span>
        <span class="stat-label">Einsätze</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-flightdays">–</span>
        <span class="stat-label">Flugtage</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-avgmissions">–</span>
        <span class="stat-label">Ø Einsätze / Flugtag</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-winchcycles">–</span>
        <span class="stat-label">Anzahl Winden-Cycles</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-avgwinch">–</span>
        <span class="stat-label">Ø Winden-Cycles / Flugtag</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-secondary">–</span>
        <span class="stat-label">Sekundärtransporte</span></div>
      <div class="stat-tile"><span class="stat-value" id="st-totalkm">–</span>
        <span class="stat-label">Flugkilometer gesamt</span></div>
      <div class="stat-tile" id="tile-maxkm"><span class="stat-value" id="st-maxkm">–</span>
        <span class="stat-label">Längste Flugstrecke</span></div>
      <div class="stat-tile" id="tile-maxdauer"><span class="stat-value" id="st-maxdauer">–</span>
        <span class="stat-label">Längste Einsatzdauer</span></div>
      <div class="stat-tile" id="tile-maxhoehe"><span class="stat-value" id="st-maxhoehe">–</span>
        <span class="stat-label">Höchster Einsatzort</span></div>
    </div>

    <!-- Spalten, Sortierung und Zeilenaufbau kommen aus assets/missiontable.js,
         gemeinsam mit suche.php. Kopf und Rumpf bleiben hier leer. -->
    <table class="data" id="rangetable">
      <thead></thead>
      <tbody id="rangebody"></tbody>
    </table>
    <p id="leer" class="muted" hidden>In diesem Zeitraum sind keine Einsätze erfasst.</p>
    <?php ui_footer(); ?>
  </main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/keyguard.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script>
const JAHR  = <?= json_encode($jahr) ?>;
const MONAT = <?= json_encode($monat) ?>;
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
const KDF_SALT = <?= json_encode($kdfSalt) ?>;

// Karte bleibt ausgeblendet (CSS [hidden]), bis feststeht, dass mindestens
// ein Pin gezeichnet wird — preferCanvas fuer performantes Rendering bei
// mehreren hundert Einsaetzen.
const map = L.map('rangemap', { preferCanvas: true });
attachBaseLayers(map);
attachFullscreenControl(map);

let missions = [];
let fixierteMid = null;   // per Klick festgesetzte Einsatz-ID oder null

const FARBE_HERVOR = '#D63338';  // Newroz Rot
const FARBE_NORMAL  = '#4280E5'; // Max Blau

// Formatierung und Ortsauswertung teilen sich beide Uebersichten; die
// Definitionen stehen in assets/missiontable.js.
const esc        = EdMissionTable.esc;
const fmtTag     = EdMissionTable.fmtTag;
const fmtDur     = EdMissionTable.fmtDur;
const extractOrt = EdMissionTable.extractOrt;
function fmtDe1(n){ return n.toFixed(1).replace('.', ','); }

// Statistiktabelle: alle Kennzahlen kommen unverschluesselt aus api/range.php,
// sind also sofort verfuegbar — unabhaengig von der lokalen Entschluesselung
// der geschuetzten Felder (Ort/Alter/Diagnose).
function zeichneStatistik(liste, tage){
  const n = liste.length;
  const windenSumme = liste.reduce((s, m) => s + (m.winch_cycles || 0), 0);
  const kmSumme     = liste.reduce((s, m) => s + (m.distance_m || 0), 0);

  document.getElementById('st-missioncount').textContent = n;
  document.getElementById('st-flightdays').textContent   = tage;
  document.getElementById('st-avgmissions').textContent  = tage > 0 ? fmtDe1(n / tage) : '–';
  document.getElementById('st-winchcycles').textContent  = windenSumme;
  document.getElementById('st-avgwinch').textContent     = tage > 0 ? fmtDe1(windenSumme / tage) : '–';
  document.getElementById('st-secondary').textContent    = liste.filter(m => m.secondary).length;
  document.getElementById('st-totalkm').textContent      =
    (kmSumme / 1000).toFixed(1).replace('.', ',') + ' km';

  // Extremwert-Kacheln: EIN Durchlauf ermittelt Wert UND Traeger-Einsatz.
  // Gleichstand: es gewinnt der zuerst gefundene Einsatz — api/range.php
  // liefert ORDER BY started_at, also der zeitlich frueheste. Deshalb strikt
  // "grösser als" statt "grösser gleich" vergleichen.
  let maxKmMid = null, maxKmWert = null;
  let maxDauerMid = null, maxDauerWert = null;
  let maxHoeheMid = null, maxHoeheWert = null;
  liste.forEach(m => {
    if (m.distance_m != null && (maxKmWert == null || m.distance_m > maxKmWert)) {
      maxKmWert = m.distance_m; maxKmMid = m.id;
    }
    if (m.duration_s != null && (maxDauerWert == null || m.duration_s > maxDauerWert)) {
      maxDauerWert = m.duration_s; maxDauerMid = m.id;
    }
    if (m.site_ele_m != null && (maxHoeheWert == null || m.site_ele_m > maxHoeheWert)) {
      maxHoeheWert = m.site_ele_m; maxHoeheMid = m.id;
    }
  });

  setzeExtremKachel('tile-maxkm', 'st-maxkm', maxKmMid,
    maxKmWert != null ? (maxKmWert / 1000).toFixed(1).replace('.', ',') + ' km' : '–');
  setzeExtremKachel('tile-maxdauer', 'st-maxdauer', maxDauerMid,
    maxDauerWert != null ? fmtDur(maxDauerWert) : '–');
  setzeExtremKachel('tile-maxhoehe', 'st-maxhoehe', maxHoeheMid,
    maxHoeheWert != null ? maxHoeheWert + ' m' : '–');

  document.getElementById('statsgrid').hidden = false;
}

// Beschriftet eine Extremwert-Kachel und macht sie interaktiv, sofern es
// einen Traeger-Einsatz gibt. Ohne Kandidat (Anzeige "–") bleibt die Kachel
// ohne data-mid und ohne Interaktions-Klasse.
function setzeExtremKachel(tileId, valueId, mid, text){
  document.getElementById(valueId).textContent = text;
  const tile = document.getElementById(tileId);
  if (mid != null) {
    tile.dataset.mid = mid;
    tile.classList.add('stat-tile-link');
  } else {
    delete tile.dataset.mid;
    tile.classList.remove('stat-tile-link', 'aktiv');
  }
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

['tile-maxkm', 'tile-maxdauer', 'tile-maxhoehe'].forEach(id => {
  const tile = document.getElementById(id);
  tile.addEventListener('mouseenter', () => {
    if (tile.dataset.mid == null) return;
    wendeHervorhebungAn(Number(tile.dataset.mid));
  });
  tile.addEventListener('mouseleave', () => {
    wendeHervorhebungAn(fixierteMid);
  });
  tile.addEventListener('click', () => {
    if (tile.dataset.mid == null) return;
    const mid = Number(tile.dataset.mid);
    if (fixierteMid === mid) { loeseFixierung(); return; }
    document.querySelectorAll('.stat-tile-link.aktiv').forEach(t => t.classList.remove('aktiv'));
    fixierteMid = mid;
    tile.classList.add('aktiv');
    wendeHervorhebungAn(mid);
    const zeile = document.querySelector(`#rangebody tr[data-mid="${mid}"]`);
    if (zeile) { zeile.scrollIntoView({ block: 'center', behavior: 'smooth' }); }
  });
});

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
  onAfterDraw: () => {
    document.getElementById('leer').hidden = missions.length > 0;
    document.getElementById('rangetable').hidden = missions.length === 0;
    wendeHervorhebungAn(fixierteMid);
  }
});

function zeichne(){ tabelle.setData(missions); }

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
  zeichne();
  zeichneStatistik(missions, d.tage);

  if (PAT_WRAP) { await entschluesselePat(); }
})();

/* Geschuetzte Angaben nachtragen. Ist der Inhaltsschluessel gesperrt, bietet
 * EdUnlock den Entsperrdialog an; bei Abbruch bleibt es beim Sperrhinweis,
 * dessen Knopf diese Funktion erneut aufruft. Ein zweiter Durchlauf ist
 * gefahrlos: ohne Schluessel wurde vorher kein Pin gezeichnet. */
async function entschluesselePat(){
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT);
  const banner = document.getElementById('lockbanner');
  if (!ck) { banner.hidden = !missions.some(m => m.pat_blob); return; }
  banner.hidden = true;
  let geaendert = false;
  const zahl = { ok: 0, leer: 0, unlesbar: 0 };
  const pinBounds = [];
  for (const m of missions) {
    if (!m.pat_blob) { zahl.leer++; continue; }
    const r = await EdPat.entschluessle(ck, m.pat_blob);
    if (r.zustand !== 'ok') { m._patFehler = true; zahl.unlesbar++; geaendert = true; continue; }
    zahl.ok++;
    {
      const o = r.daten;
      if (o.dx != null) { m._dx = o.dx; geaendert = true; }
      const alter = EdPat.alterAnzeige(o, m.day);   // Alter zum jeweiligen Einsatztag
      if (alter != null) { m._age = alter; geaendert = true; }
      if (o.loc && o.loc.addr) { m._ort = extractOrt(o.loc.addr); geaendert = true; }
      // Einheitlicher Pin (Max Blau) je Einsatzort; kein Clustering in v1.
      if (o.loc && o.loc.lat != null) {
        m._marker = L.circleMarker([o.loc.lat, o.loc.lon], {
          radius: 6, weight: 2, color: '#fff', fillColor: FARBE_NORMAL, fillOpacity: 1
        }).addTo(map).bindPopup(`${fmtTag(m.day)}<br>${esc(o.loc.addr)}`);
        pinBounds.push([o.loc.lat, o.loc.lon]);
      }
    }
  }
  EdPat.zeigeUnlesbar(zahl);
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

document.getElementById('unlockbtn').addEventListener('click', () => entschluesselePat());
</script>
</body>
</html>
