<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

// Einsatz-ID einlesen und Eigentum pruefen (liefert auch den Diensttag fuer die
// Seitenleiste). Ohne Treffer: sauberes 404.
$mid = (int)($_GET['id'] ?? 0);
$mq = db()->prepare('SELECT day_id FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
$mq->execute([$mid, $userId]);
$missionDayId = $mq->fetchColumn();
if ($missionDayId === false) { http_response_code(404); exit('Einsatz nicht gefunden.'); }
$missionDayId = $missionDayId === null ? null : (int)$missionDayId;
$nachtrag = ($_GET['nachtrag'] ?? '') === '1';
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Einsatz — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/vendor/leaflet/leaflet.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>

<div class="layout">
  <?php ui_days_sidebar($missionDayId); ?>

  <main class="page">
  <div class="pagehead">
    <div class="pagehead-text">
      <h1 id="title">Einsatz</h1>
      <p id="meta" class="muted"></p>
    </div>
    <div class="pagehead-actions">
      <?php /* AKTIONSMENÜ (A5.1, E4). Aus zwei Schaltflächen ist ein Menü mit
               drei Einträgen geworden — „Verschieben" kam hinzu.

               Gebaut aus <details>/<summary>, nicht aus einem eigenen
               Menü-Widget: Damit ist die Tastaturbedienung von Haus aus
               vollständig (Tabulator auf den Kopf, Enter oder Leertaste öffnet,
               Tabulator läuft weiter durch die Einträge), ohne dass sie hier
               nachgebaut und dabei halb vergessen würde.

               Das Schliessen daneben und mit Escape steht seit Web 5.10.0 in
               assets/aktionsmenu.js — die Diensttagübersicht hat dasselbe Menü,
               und zwei Fassungen desselben Verhaltens laufen auseinander. */ ?>
      <details class="aktionsmenu" id="aktionsmenu">
        <summary class="btn-edit">Aktionen</summary>
        <div class="aktionsliste">
          <a href="einsatz_form.php?id=<?= $mid ?>">Bearbeiten</a>
          <a href="einsatz_verschieben.php?id=<?= $mid ?>">Verschieben</a>
          <a class="gefahr" href="einsatz_loeschen.php?id=<?= $mid ?>">Löschen</a>
        </div>
      </details>
    </div>
  </div>

  <div id="loaderror" class="alert" hidden></div>

  <?php if (($_GET['verschoben'] ?? '') === '1'): ?>
    <?php /* Welchem Diensttag der Einsatz jetzt gehört, steht ohnehin im Kopf
             der Seite — die Bestätigung nennt deshalb nur, was NICHT geschehen
             ist. Genau das ist der Punkt, den man beim Verschieben wissen
             muss. */ ?>
    <p class="alert alert-ok">Der Einsatz gehört jetzt zum unten genannten
      Diensttag. Die Uhrzeiten sind unverändert geblieben.</p>
  <?php endif; ?>

  <?php if ($nachtrag): ?>
    <p class="alert alert-ok">Einsatz gespeichert.
      <a class="btn-edit" href="einsatz_form.php?d=<?= (int)$missionDayId ?>">Weiteren Einsatz nachtragen</a></p>
  <?php endif; ?>

  <dl class="fieldlist" id="fieldlist" hidden></dl>

  <section id="crew-section" hidden>
    <h2>Besatzung</h2>
    <dl class="fieldlist" id="crewlist"></dl>
  </section>

  <div id="map" class="map map-tall"></div>

  <section>
    <?php /* „Einsatzphasen" statt „Phasen" (Web 7.0.0). Der kurze Titel stand
             fuer sich allein auf der Seite und war dort eindeutig; im Gespraech
             und in der Uhr-App heisst es aber durchgaengig Einsatzphase, und
             eine Ueberschrift, die anders heisst als die Sache, kostet bei
             jedem Hinsehen einen Gedanken. */ ?>
    <h2>Einsatzphasen</h2>
    <table class="data" id="phases">
      <thead><tr><th>Nr.</th><th>Phase</th><th>Uhrzeit</th></tr></thead>
      <tbody id="phasebody"></tbody>
    </table>
  </section>

  <section id="resus-section" hidden>
    <div id="resus-tables"></div>
  </section>

  <?php ui_footer(); ?>
  </main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/keyguard.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/aktionsmenu.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script src="<?= asset('assets/luftlinie.js') ?>"></script>
<script>
const MID = <?= $mid ?>;
// Salt fuer die Schluesselableitung im Entsperrdialog. Der Wrap selbst
// kommt hier aus der API-Antwort (m.pat_wrap), nicht aus PHP.
const KDF_SALT = <?= json_encode($kdfSalt) ?>;
/* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
   gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
   bekommt einen anderen Schluessel. */
const KDF_ITER      = <?= json_encode($kdfIter) ?>;
const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;

// Maskierung: Baustein B7 (assets/html.js). Hier stand eine eigene Fassung
// ueber ein Hilfselement — sie maskierte drei Zeichen statt fuenf (M6-03).
const esc = EdHtml.escape;
function fmtDay(d){ const p = d.split('-'); return `${p[2]}.${p[1]}.${p[0]}`; }
function zeigeLadeFehler(msg){
  document.getElementById('title').textContent = 'Einsatz nicht geladen';
  const box = document.getElementById('loaderror');
  box.textContent = 'Die Einsatzdaten konnten nicht geladen werden: ' + msg;
  box.hidden = false;
}

const map = L.map('map');
/* Ausgangsausschnitt sofort setzen — dieselbe Zeile wie auf der Tages- und
 * der Zeitraumansicht. Ohne sie nimmt Leaflet Ebenen zwar entgegen, rechnet
 * ihre Bildschirmposition aber erst aus, wenn ein Ausschnitt feststeht; jeder
 * Zugriff auf einen so eingereihten Pin scheitert bis dahin. Hier faellt es
 * heute nicht auf, weil fitBounds() rechtzeitig kommt — auf der
 * Zeitraumansicht tat es das nicht. Der Wert ist derselbe, den diese Seite
 * ohnehin verwendet, wenn ein Einsatz keine Spur hat: kein zusaetzlicher
 * Sprung im Bild. */
map.setView([47.7, 10.3], 9);
attachBaseLayers(map);
attachFullscreenControl(map);

// Tracklinien: Staerke waechst beim Rauszoomen, damit kurze Tracks auf der
// Uebersicht sichtbar bleiben (smoothFactor 0: keine Wegvereinfachung).
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

function trackWeight(){
  // Duenne Linien: bei kleinem Massstab wirkten dicke Striche wie Balken.
  const z = map.getZoom();
  return z >= 14 ? 3 : z >= 10 ? 4 : 5;
}
map.on('zoomend', () => {
  const w = trackWeight();
  trackLines.forEach(l => l.setStyle({ weight: w }));
});

let phaseMarkers = [];        // [{marker, idx}]
let phasesVisible = false;    // Standard: Aus, keine Persistenz -> nach jedem
                               // Laden der Seite wieder aus
let phaseToggleBtn = null;

function buildPhaseMarkers(phases){
  // Kachel an der GPS-Position des Zeitstempels (nur wo die Uhr Fix hatte);
  // gestapelte gleiche Positionen leicht nebeneinander versetzt. Marker
  // werden erzeugt, aber NICHT sofort der Karte hinzugefuegt -- erst der
  // Toggle blendet sie ein. Die Hover-/Klick-Kopplung wird erst gebunden,
  // wenn ein Marker tatsaechlich auf der Karte landet (das DOM-Element
  // existiert vorher noch nicht); Leaflet feuert dafuer bei jedem
  // addTo(map) ein eigenes 'add'-Ereignis, auch bei spaeterem Wieder-
  // Einblenden.
  const groups = {};
  phases.forEach((p, idx) => {
    if (p.lat == null || p.lon == null) return;
    const key = p.lat.toFixed(4) + ',' + p.lon.toFixed(4);
    (groups[key] = groups[key] || []).push({ p, idx });
  });
  Object.values(groups).forEach(list => {
    list.forEach((e2, k) => {
      const icon = L.divIcon({
        className: 'phase-marker',
        html: `<span class="chip pm-chip" data-idx="${e2.idx}">${e2.p.phase}</span>`,
        iconSize: [24, 24],
        iconAnchor: [12 - k * 20, 12]
      });
      const mk = L.marker([e2.p.lat, e2.p.lon], { icon, keyboard: false });
      mk.on('add', () => bindMarkerHover(mk, e2.idx));
      if (phasesVisible) { mk.addTo(map); }
      phaseMarkers.push({ marker: mk, idx: e2.idx });
    });
  });
  if (phaseMarkers.length) { attachPhaseToggleControl(); }
}

function attachPhaseToggleControl(){
  const PhaseToggleControl = L.Control.extend({
    options: { position: 'topleft' },
    onAdd: function () {
      const wrap = L.DomUtil.create('div', 'leaflet-bar map-ctrl-phase');
      const btn = L.DomUtil.create('a', '', wrap);
      btn.href = '#';
      phaseToggleBtn = btn;
      aktualisierePhaseToggleBtn();
      L.DomEvent.disableClickPropagation(wrap);
      L.DomEvent.on(btn, 'click', L.DomEvent.stop)
        .on(btn, 'click', () => {
          phasesVisible = !phasesVisible;
          phaseMarkers.forEach(e2 =>
            phasesVisible ? e2.marker.addTo(map) : map.removeLayer(e2.marker));
          aktualisierePhaseToggleBtn();
        });
      return wrap;
    }
  });
  // Position 'topleft' unterhalb des Vollbild-Controls (P1), das beim
  // Karten-Aufbau bereits als erstes Control in dieser Ecke haengt --
  // Leaflet stapelt mehrere Controls derselben Ecke in Einfuegereihenfolge.
  map.addControl(new PhaseToggleControl());
}

function aktualisierePhaseToggleBtn(){
  if (!phaseToggleBtn) { return; }
  phaseToggleBtn.textContent = phasesVisible
    ? 'Einsatzphasen ausblenden' : 'Einsatzphasen anzeigen';
  phaseToggleBtn.title = phaseToggleBtn.textContent;
  phaseToggleBtn.classList.toggle('active', phasesVisible);
}

function bindMarkerHover(mk, idx){
  const el = mk.getElement();
  if (!el) return;
  el.addEventListener('mouseenter', () => hlPhase(idx, true));
  el.addEventListener('mouseleave', () => hlPhase(idx, false));
  el.addEventListener('click', () => hlPhase(idx, 'toggle'));
}

let hlActive = {};
function hlPhase(idx, on){
  if (on === 'toggle') { on = !hlActive[idx]; }
  hlActive[idx] = on;
  const row = document.querySelector(`#phasebody tr[data-idx="${idx}"]`);
  if (row) row.classList.toggle('hl', on);
  const chip = document.querySelector(`.pm-chip[data-idx="${idx}"]`);
  if (chip) {
    chip.classList.toggle('hl', on);
    const pm = phaseMarkers.find(e2 => e2.idx === idx);
    if (pm) pm.marker.setZIndexOffset(on ? 1000 : 0);
  }
}

/* ---- Reihenfolge des Inhaltskästchens (Web 7.0.0) ------------------------
 *
 * Bis Web 6.3.0 stand hier die Reihenfolge des FELDKATALOGS, und die
 * verschlüsselten Angaben hingen als Block hinten dran — sie kommen erst nach
 * dem Entsperren an und wurden deshalb einfach angehängt. Das Ergebnis las sich
 * rückwärts: erst Transport und Winde, dann ganz unten, wer eigentlich
 * behandelt wurde.
 *
 * Jetzt hat jede Zeile einen RANG, und eingefügt wird an der Stelle, an die sie
 * gehört — unabhängig davon, wann ihr Wert eintrifft. Die Ordnung folgt dem
 * Gang der Dokumentation: erst die Person, dann der Ort, dann was gefunden
 * wurde, dann was daraus folgte (Transport zuletzt, weil er das Ende des
 * Einsatzes beschreibt).
 *
 * Ein Feld ohne Eintrag hier bekommt RANG_SONST und steht damit am Ende statt
 * zu verschwinden: Ein neues Katalogfeld erscheint auch ohne Änderung an dieser
 * Liste. */
const RANG_SONST = 900;
/* Ob die Höhe des Einsatzorts gezeigt wird und wie hoch sie liegt. Beides
 * entscheidet init(), gebraucht wird es in zeigePat(): Dort entsteht die Zeile
 * „Einsatzort", und die Höhe gehört hinein. */
let hoeheZeigen = false;
let hoeheWert = null;
const RANG = {
  patlock:          5,     // Sperrhinweis bzw. Fehlermeldung: immer zuoberst
  mission_no:      10,
  pat_name:        20,
  pat_dob:         30,
  pat_loc:         40,
  pat_site_desc:   50,
  luftlinie:       60,
  pat_dx:          70,
  notes:           80,
  other_resources: 90,
  other_ema:      100,
  secondary:      110,
  false_alarm:    120,
  winch:          130, winch_cycles: 131, winch_cycles_pat: 132, winch_airload: 133,
  bergwacht:      140, bw_unit: 141, bw_info: 142,
  transport_mode: 150, na_escort: 151,
  transport_dest: 160,
  schockraum:     170
};

/**
 * Eine Zeile ins Inhaltskästchen einsortieren.
 *
 * dt und dd tragen denselben Rang als Datenattribut; eingefügt wird vor dem
 * ersten Element mit HÖHEREM Rang. Gleiche Ränge behalten damit ihre
 * Einfügereihenfolge — was bei den Unterfeldern der Winde die richtige ist.
 *
 * @param {number} rang
 * @param {string} dt  fertiges HTML der Beschriftung
 * @param {string} dd  fertiges HTML des Wertes
 */
function dlZeile(rang, dt, dd){
  const dl = document.getElementById('fieldlist');
  const vor = [...dl.children].find(el => Number(el.dataset.rang) > rang) || null;
  const dtEl = document.createElement('dt');
  const ddEl = document.createElement('dd');
  dtEl.dataset.rang = rang; ddEl.dataset.rang = rang;
  dtEl.innerHTML = dt; ddEl.innerHTML = dd;
  dl.insertBefore(dtEl, vor);
  dl.insertBefore(ddEl, vor);
  dl.hidden = false;
}

/** Zeile mit dieser Kennung wieder entfernen (Sperrhinweis beim zweiten Anlauf). */
function dlEntferne(id){
  document.querySelectorAll(`[data-zeile="${id}"]`).forEach(el => el.remove());
}

async function init(){
  const res = await fetch('api/mission.php?id=' + MID);
  const txt = await res.text();
  let m;
  try { m = JSON.parse(txt); }
  catch (e) {
    zeigeLadeFehler(txt.replace(/<[^>]*>/g, ' ').trim().slice(0, 300) || ('HTTP ' + res.status));
    return;
  }
  if (!res.ok || m.error) {
    zeigeLadeFehler(m.error === 'not_found'
      ? 'Einsatz nicht gefunden.'
      : (m.error || ('HTTP ' + res.status)) + (m.meldung ? ': ' + m.meldung : ''));
    return;
  }

  document.getElementById('title').textContent =
    `Einsatz ${m.day_no} · ${m.start_hhmm} Uhr`;

  const ORIGIN_LABEL = { watch: 'Uhr', manual: 'manuell', import: 'importiert' };
  const ORIGIN_KLASSE = { watch: 'badge-uhr', manual: 'badge-manuell', import: 'badge-import' };
  const zeitteil = m.has_p9
    ? `${m.start_hhmm} – ${m.end_hhmm} Uhr`
    : `${m.start_hhmm} Uhr – kein Ende`;
  const kennzeichen =
    `<span class="badge ${ORIGIN_KLASSE[m.origin] || 'badge-uhr'}">${ORIGIN_LABEL[m.origin] || 'Uhr'}</span>`
    + (m.edited ? ' · <span class="badge badge-editiert">editiert</span>' : '');

  /* Kopfzeile (Web 7.0.0 gestrafft).
     ENTFALLEN sind zwei Angaben. Die STRECKE stand hier als dritte Zahl neben
     zwei Uhrzeiten, ohne dass jemand sie an dieser Stelle sucht — sie gehört
     zur Auswertung und steht dort (Zeitraum-Übersicht, Suche, Export). Und das
     Wort „Diensttag" vor dem Datum sagte nichts, was das Datum nicht selbst
     sagt: Darunter folgen Rettungsmittel und Standort, damit ist klar, wovon
     die Rede ist.
     GEBLIEBEN ist die Unterscheidung, für die es das Wort einmal gab: Fällt
     das Datum des Dienstes vom echten Einsatzdatum ab — ein Einsatz um 01:30
     gehört zum Dienst des Vortags (E9) —, wird der Dienst ausdrücklich
     genannt. Ohne das sähe die Zuordnung wie ein Fehler aus.
     Bezeichnungen kommen aus den eingefrorenen Spalten des Diensttags (E8),
     nie aus den Stammdaten. */
  const teile = [];
  if (m.mission_day) { teile.push(fmtDay(m.mission_day)); }
  teile.push(zeitteil);
  if (m.day_vehicle_name) { teile.push(m.day_vehicle_name); }
  if (m.day_base_name) { teile.push(m.day_base_name); }
  if (!m.day) { teile.push('kein Diensttag zugeordnet'); }
  else if (m.day !== m.mission_day) { teile.push(`Dienst vom ${fmtDay(m.day)}`); }
  document.getElementById('meta').innerHTML =
    esc(teile.join(' · '))
    + ' <span class="artzeichen" title="' + esc(m.day_art_text || '')
    + '" aria-label="' + esc(m.day_art_text || '') + '">'
    + esc(m.day_art_zeichen || '') + '</span>'
    + ' · ' + kennzeichen;

  // Zusatzfelder (Server liefert nur befuellte), einsortiert nach RANG.
  const dl = document.getElementById('fieldlist');
  m.fields.forEach(f => {
    dlZeile(RANG[f.col] ?? RANG_SONST, esc(f.label), esc(f.value));
  });
  /* HÖHE DES EINSATZORTS — nur luftgebunden (A13, Konzept 4.6), und seit
   * Web 7.0.0 in der Zeile des Einsatzortes statt in einer eigenen (siehe
   * zeigePat). Steht KEIN Einsatzort da — er ist verschlüsselt, die Sitzung
   * kann gesperrt sein —, bekommt sie doch eine eigene Zeile: Die Höhe selbst
   * liegt im Klartext, sie zu verschweigen wäre kein Gewinn.
   *
   * Gerechnet wird sie unverändert für jeden Einsatz mit Track
   * (site_elevation_lib.php bleibt unangetastet); gezeigt wird sie nur, wo sie
   * etwas aussagt. Bodengebunden ist sie die Höhe der Straße. Bei einem noch
   * nicht zugeordneten Diensttag (day_kind === null, E26) bleibt sie verborgen:
   * Ob sie etwas aussagt, ist dann noch nicht entschieden.
   *
   * DIE STEIGUNG IST GANZ ENTFALLEN (Web 7.0.0). Sie war das Profil der
   * geflogenen Strecke und nicht das des Einsatzes — eine Zahl, aus der sich
   * nichts ableiten liess, was nicht Track und Höhe schon sagen. In Export,
   * Sicherung und Datenbank bleibt sie unverändert erhalten; nur diese Ansicht
   * zeigt sie nicht mehr.
   *
   * Die Werte gehen weiterhin in Export und Backup — dort steht die Art
   * daneben, und wer auswertet, kann selbst entscheiden. */
  hoeheZeigen = m.site_ele_m != null && m.day_kind === 'air';
  hoeheWert = m.site_ele_m;
  if (hoeheZeigen) {
    dlZeile(RANG.pat_loc, 'Höhe Einsatzort', `${m.site_ele_m} m`);
    markiere(RANG.pat_loc, 'hoehe');
  }
  dl.hidden = dl.children.length === 0;

  // Besatzung: Tagescrew, einzelne Rollen ggf. durch den Einsatz ueberschrieben
  // (Server hat die COALESCE-Regel bereits angewandt, siehe api/mission.php).
  const crewList = document.getElementById('crewlist');
  Object.values(m.crew_effektiv || {}).forEach(c => {
    crewList.insertAdjacentHTML('beforeend',
      `<dt>${esc(c.label)}</dt><dd>${esc(c.name)}`
      + (c.abw ? ' <span class="abw">(abw.)</span>' : '') + '</dd>');
  });
  document.getElementById('crew-section').hidden = crewList.children.length === 0;

  // Karte: Track (Start gruen, Ende rot), Einsatzort-Pin in Trackfarbe
  const bounds = [];
  if (m.track.length > 1) {
    const line = L.polyline(m.track, { color: '#FF8F1F', weight: trackWeight(), smoothFactor: 0 }).addTo(map);
    trackLines.push(line);
    L.circleMarker(m.track[0], { radius: 6, color: '#1B8A3A', fillColor: '#1B8A3A', fillOpacity: 1 })
      .addTo(map).bindPopup('Start');
    L.circleMarker(m.track[m.track.length - 1], { radius: 6, color: '#C62828', fillColor: '#C62828', fillOpacity: 1 })
      .addTo(map).bindPopup('Ende');
    m.track.forEach(p => bounds.push(p));
  }
  /* ZIELKLINIK-PIN — ohne Freischalten sichtbar (E40, A13o).
   *
   * Er steht hier und nicht in zeigePat(): Name und Koordinate der Zielklinik
   * liegen im Klartext, ihre Einstufung ist dieselbe. Was der Pin verrät, ist
   * wohin transportiert wurde — und das sagt der Name daneben ohnehin schon.
   * Linie und Einsatzort-Pin bleiben dagegen hinter dem Schlüssel: Sie verraten,
   * WO die Patientin war. */
  if (m.dest_lat != null && m.dest_lon != null) {
    L.marker([m.dest_lat, m.dest_lon], { icon: locPin(EdLuftlinie.FARBE), keyboard: false })
      .addTo(map).bindPopup('Zielklinik' + (m.dest_name ? '<br>' + esc(m.dest_name) : ''));
    bounds.push([m.dest_lat, m.dest_lon]);
  }

  if (bounds.length) {
    // Rand proportional zur Kartengröße, wie auf der Tagesübersicht — und
    // eine Zoom-Obergrenze, damit ein sehr kurzer Track (oder ein einzelner
    // Punkt) nicht bis auf Gebäude-Ebene heranzoomt.
    const px = map.getSize();
    map.fitBounds(bounds, { padding: [px.y * 0.125, px.x * 0.125], maxZoom: 15 });
  }
  else { map.setView([47.7, 10.3], 9); document.getElementById('map').classList.add('map-empty'); }

  // Phasen-Tabelle mit Hover-/Tipp-Kopplung zur Karte
  const pb = document.getElementById('phasebody');
  m.phases.forEach((p, idx) => {
    const tr = document.createElement('tr');
    tr.dataset.idx = idx;
    tr.innerHTML = `<td><span class="chip">${p.phase}</span></td><td>${esc(p.label)}</td><td class="mono">${p.time}</td>`;
    tr.addEventListener('mouseenter', () => hlPhase(idx, true));
    tr.addEventListener('mouseleave', () => hlPhase(idx, false));
    tr.addEventListener('click', () => hlPhase(idx, 'toggle'));
    pb.appendChild(tr);
  });
  buildPhaseMarkers(m.phases);

  // Reanimationen: eine Zeiten-Tabelle je Sitzung
  if (m.resus && m.resus.length) {
    document.getElementById('resus-section').hidden = false;
    const wrap = document.getElementById('resus-tables');
    m.resus.forEach((events, i) => {
      const h = document.createElement('h2');
      h.textContent = m.resus.length > 1 ? `Reanimation ${i + 1}` : 'Reanimation';
      wrap.appendChild(h);
      const t = document.createElement('table');
      t.className = 'data';
      t.innerHTML = '<thead><tr><th>Ereignis</th><th>Uhrzeit</th></tr></thead>';
      const tb = document.createElement('tbody');
      events.forEach(e2 => {
        const tr = document.createElement('tr');
        tr.innerHTML = `<td>${esc(e2.label)}</td><td class="mono">${e2.time}</td>`;
        tb.appendChild(tr);
      });
      t.appendChild(tb);
      wrap.appendChild(t);
    });
  }

  // Verschluesselte Angaben (Diagnose, Alter, Einsatzort) lokal entschluesseln
  if (m.pat_blob && m.pat_wrap) { await zeigePat(m, dl, bounds); }
}

/* Geschuetzte Angaben anzeigen. Ist der Inhaltsschluessel gesperrt, bietet
 * EdUnlock den Entsperrdialog an; bei Abbruch bleibt der Sperrhinweis stehen,
 * dessen Knopf diese Funktion erneut aufruft. Der Hinweis wird zu Beginn
 * entfernt, damit er beim zweiten Durchlauf nicht doppelt erscheint. */
async function zeigePat(m, dl, bounds){
  dlEntferne('patlock');
  const ck = await EdUnlock.ensureContentKey(m.pat_wrap, KDF_SALT, KDF_ITER);
  if (!ck) {
    dlZeile(RANG.patlock, 'Verschlüsselt 🔒',
      '<span class="muted">gesperrt — ' +
      '<button type="button" class="btn-plain unlockbtn">Entsperren</button></span>');
    markiere(RANG.patlock, 'patlock');
    document.querySelector('.unlockbtn')
      .addEventListener('click', () => zeigePat(m, dl, bounds));
    return;
  }

  const r = await EdPat.entschluessle(ck, m.pat_blob);
  if (r.zustand === 'unlesbar') {
    // Hier ist der Fall besonders deutlich zu benennen: Auf der Einzelansicht
    // sieht die NutzerIn genau einen Einsatz. Ein stiller Fehlschlag sieht
    // hier aus wie "keine geschuetzten Angaben erfasst" — also wie ein
    // normaler, unauffaelliger Zustand.
    dlZeile(RANG.patlock, 'Verschlüsselt ⚠',
      '<span class="patfehler">Für diesen Einsatz sind geschützte ' +
      'Angaben gespeichert, sie lassen sich mit dem aktuellen Schlüssel aber ' +
      '<strong>nicht lesen</strong>. Die Daten sind vorhanden und nicht verloren. ' +
      'Bitte den Wiederherstellungsschlüssel bereithalten und vor weiteren ' +
      'Schritten klären, warum der Schlüssel nicht passt.</span>');
    markiere(RANG.patlock, 'patlock');
    return;
  }

  const o = r.daten || {};
  if (o.mission_no != null && String(o.mission_no) !== '') {
    dlZeile(RANG.mission_no, 'Einsatznummer 🔒', esc(String(o.mission_no)));
  }
  const pname = EdPat.name(o);
  if (pname !== '') {
    dlZeile(RANG.pat_name, 'Name 🔒', esc(pname));
  }
  /* GEBURTSDATUM UND ALTER IN EINER ZEILE (Web 7.0.0). Sie standen als zwei
     Zeilen untereinander und sagten dasselbe zweimal — das Alter FOLGT aus dem
     Geburtsdatum, es ist keine zweite Angabe.
     Die Einheit wechselt mit dem Alter (EdPat.alterText): Bei einem Säugling
     ist „0" keine Auskunft, „3 Monate" oder „12 Tage" schon. Ohne Geburtsdatum
     bleibt es bei der Zeile „Alter" — dort steht dann der von Hand
     eingetragene, meist geschätzte Wert. */
  const alterTxt = EdPat.alterText(o, m.mission_day);
  if (o.dob != null) {
    dlZeile(RANG.pat_dob, 'Geburtsdatum 🔒',
      esc(EdPat.datumDe(o.dob))
      + (alterTxt ? ` <span class="muted">(${esc(alterTxt)})</span>` : ''));
  } else if (alterTxt) {
    dlZeile(RANG.pat_dob, 'Alter 🔒', esc(alterTxt));
  }
  /* EINSATZORT MIT HÖHE (Web 7.0.0). Die Höhe stand als eigene Zeile weit
     unten; sie ist aber eine Eigenschaft DIESES Ortes und sonst nichts. Steht
     hier ein Ort, wandert sie in seine Zeile — und die Ersatzzeile aus init(),
     die sie für den gesperrten Fall trägt, verschwindet dafür. */
  if (o.loc && o.loc.addr) {
    if (hoeheZeigen) { dlEntferne('hoehe'); }
    dlZeile(RANG.pat_loc, 'Einsatzort 🔒',
      esc(o.loc.addr)
      + (hoeheZeigen ? ` <span class="muted">(${esc(String(hoeheWert))} m)</span>` : ''));
    if (o.loc.lat != null) {
      L.marker([o.loc.lat, o.loc.lon], { icon: locPin('#FF8F1F'), keyboard: false })
        .addTo(map).bindPopup('Einsatzort<br>' + esc(o.loc.addr));
      if (!bounds.length) { map.setView([o.loc.lat, o.loc.lon], 13); }
    }
  }
  // Beschreibung steht direkt unter dem Einsatzort statt in der generischen
  // Zusatzfeldliste — sie liegt seit Web 3.3.0 im pat_blob (E5).
  if (o.site_desc != null) {
    dlZeile(RANG.pat_site_desc, 'Beschreibung Einsatzort 🔒', esc(String(o.site_desc)));
  }
  if (o.dx != null) {
    dlZeile(RANG.pat_dx, 'Diagnose 🔒', esc(String(o.dx)));
  }
  await zeichneLuftlinie(m, o, ck, bounds);
  dl.hidden = dl.children.length === 0;
}

/** Die zuletzt eingefuegte Zeile eines Rangs kennzeichnen, damit dlEntferne()
 *  sie wiederfindet. */
function markiere(rang, id){
  document.querySelectorAll(`[data-rang="${rang}"]`)
    .forEach(el => { el.dataset.zeile = id; });
}
/* ---- Luftlinie ohne GPS-Aufzeichnung (E34/E35, A13g–A13i, A13n) ----------
 *
 * Sie steht hier, im entschlüsselten Teil: Ihr mittlerer Stützpunkt ist der
 * Einsatzort, und der liegt im pat_blob. Ohne Freischalten gibt es deshalb
 * keine Linie — auch dann nicht, wenn Abfahrtort und Zielklinik im Klartext
 * bekannt wären (A13o).
 *
 * Die Regeln stehen in assets/luftlinie.js und nicht hier, weil die
 * Tagesübersicht dieselben braucht. Diese Funktion beschafft nur die vier
 * möglichen Quellen des Abfahrtorts — zwei hat der Server bereits aufgelöst,
 * zwei sind verschlüsselt und lassen sich nur hier lesen. */
async function zeichneLuftlinie(m, o, ck, bounds){
  /* „Letzter Einsatzort" ist der Einsatzort des VORGÄNGERS und damit
   * verschlüsselt. Der Server liefert dessen Blob nur bei genau dieser Regel
   * mit — auszuwerten ist er allein hier. */
  let prevSite = null;
  if (m.start_src === 'prev_site' && m.start_prev_blob) {
    const r = await EdPat.entschluessle(ck, m.start_prev_blob);
    if (r.zustand === 'ok' && r.daten && r.daten.loc) { prevSite = r.daten.loc; }
  }
  const abfahrt = EdLuftlinie.abfahrt(m.start_src, {
    base: m.start_base,
    prevDest: m.start_prev_dest,
    prevSite: prevSite,
    manual: o.start
  });
  const punkte = EdLuftlinie.punkte({
    hatTrack: m.track.length > 1,
    abfahrt: abfahrt,
    ort: o.loc,
    ziel: (m.dest_lat != null && m.dest_lon != null)
      ? { lat: m.dest_lat, lon: m.dest_lon, name: m.dest_name } : null
  });
  if (!punkte.length) { return; }

  EdLuftlinie.zeichne(map, punkte);
  // Pin an jedem Ende. Der Einsatzort hat seinen eigenen (oben), die Zielklinik
  // ebenso — bleibt der Abfahrtort.
  if (abfahrt) {
    L.marker([abfahrt.lat, abfahrt.lon], { icon: locPin(EdLuftlinie.FARBE), keyboard: false })
      .addTo(map).bindPopup('Abfahrtort' + (abfahrt.text ? '<br>' + esc(abfahrt.text) : ''));
  }
  // Die Länge ausdrücklich BENANNT — eine Luftlinie ist keine gefahrene
  // Strecke, und die Zahl daneben würde sonst als eine gelesen (E36).
  dlZeile(RANG.luftlinie, 'Luftlinie 🔒',
    `${esc(EdLuftlinie.text(punkte))}
     <span class="muted">(gerade Verbindung, kein aufgezeichneter Weg)</span>`);

  const px = map.getSize();
  map.fitBounds(bounds.concat(punkte.map(p => [p.lat, p.lon])),
    { padding: [px.y * 0.125, px.x * 0.125], maxZoom: 15 });
}

init();
</script>
</body>
</html>
