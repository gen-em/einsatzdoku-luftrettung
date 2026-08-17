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
    <h2>Phasen</h2>
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
function fmtKm(m){ return m == null ? '–' : (m / 1000).toFixed(1).replace('.', ',') + ' km'; }
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
  phaseToggleBtn.textContent = phasesVisible ? 'Phasen ausblenden' : 'Phasen anzeigen';
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

  /* Kopfzeile: das ECHTE Einsatzdatum, dazu der Diensttag, zu dem er gehört.
     Beides ist nötig, seit sich der Diensttag vom Kalendertag gelöst hat (E9):
     Ein Einsatz um 01:30 gehört zum Dienst des Vortags, und ohne die Angabe
     sähe die Zuordnung wie ein Fehler aus. Bezeichnungen kommen aus den
     eingefrorenen Spalten des Diensttags (E8), nie aus den Stammdaten.
     Die Streckenangabe ist neutral beschriftet (Abschnitt 3.9). */
  const dienstTeile = [];
  if (m.day) { dienstTeile.push(fmtDay(m.day)); }
  if (m.day_vehicle_name) { dienstTeile.push(m.day_vehicle_name); }
  if (m.day_base_name) { dienstTeile.push(m.day_base_name); }
  const dienst = dienstTeile.length
    ? `Diensttag ${dienstTeile.join(' · ')}`
    : 'kein Diensttag zugeordnet';
  document.getElementById('meta').innerHTML =
    esc(`${zeitteil} · ${fmtKm(m.distance_m)} · ${dienst}`)
    + ' <span class="artzeichen" title="' + esc(m.day_art_text || '')
    + '" aria-label="' + esc(m.day_art_text || '') + '">'
    + esc(m.day_art_zeichen || '') + '</span>'
    + ' · ' + kennzeichen;

  // Zusatzfelder (Server liefert nur befuellte)
  const dl = document.getElementById('fieldlist');
  m.fields.forEach(f => {
    dl.insertAdjacentHTML('beforeend', `<dt>${esc(f.label)}</dt><dd>${esc(f.value)}</dd>`);
  });
  /* HÖHENANGABEN NUR LUFTGEBUNDEN (A13, Konzept 4.6).
   *
   * Gerechnet werden sie unverändert für jeden Einsatz mit Track
   * (site_elevation_lib.php bleibt unangetastet) — gezeigt werden sie nur, wo
   * sie etwas aussagen. Bodengebunden ist die Höhe des Einsatzorts die Höhe der
   * Straße, und die Steigung ist das Profil der Fahrstrecke; beides ist keine
   * Auskunft über den Einsatz. Bei einem noch nicht zugeordneten Diensttag
   * (day_kind === null, E26) bleiben sie ebenfalls verborgen: Ob sie etwas
   * aussagen, ist dann noch nicht entschieden.
   *
   * Die Werte gehen weiterhin in Export und Backup — dort steht die Art
   * daneben, und wer auswertet, kann selbst entscheiden. */
  if (m.site_ele_m != null && m.day_kind === 'air') {
    dl.insertAdjacentHTML('beforeend', `<dt>Höhe Einsatzort</dt><dd>${m.site_ele_m} m</dd>`);
  }
  if (m.ascent_m != null && m.day_kind === 'air') {
    dl.insertAdjacentHTML('beforeend', `<dt>Steigung</dt><dd>${m.ascent_m} m</dd>`);
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
  ['patlockdt', 'patlockdd'].forEach(id => {
    const el = document.getElementById(id);
    if (el) { el.remove(); }
  });
  const ck = await EdUnlock.ensureContentKey(m.pat_wrap, KDF_SALT, KDF_ITER);
  if (ck) {
    const r = await EdPat.entschluessle(ck, m.pat_blob);
    if (r.zustand === 'unlesbar') {
      // Hier ist der Fall besonders deutlich zu benennen: Auf der Einzelansicht
      // sieht die NutzerIn genau einen Einsatz. Ein stiller Fehlschlag sieht
      // hier aus wie "keine geschuetzten Angaben erfasst" — also wie ein
      // normaler, unauffaelliger Zustand.
      dl.insertAdjacentHTML('beforeend',
        '<dt id="patlockdt">Verschlüsselt ⚠</dt>' +
        '<dd id="patlockdd" class="patfehler">Für diesen Einsatz sind geschützte ' +
        'Angaben gespeichert, sie lassen sich mit dem aktuellen Schlüssel aber ' +
        '<strong>nicht lesen</strong>. Die Daten sind vorhanden und nicht verloren. ' +
        'Bitte den Wiederherstellungsschlüssel bereithalten und vor weiteren ' +
        'Schritten klären, warum der Schlüssel nicht passt.</dd>');
      dl.hidden = false;
      return;
    }
    {
      const o = r.daten || {};
      if (o.mission_no != null && String(o.mission_no) !== '') {
        dl.insertAdjacentHTML('beforeend', `<dt>Einsatznummer 🔒</dt><dd>${esc(String(o.mission_no))}</dd>`);
      }
      const pname = EdPat.name(o);
      if (pname !== '') {
        dl.insertAdjacentHTML('beforeend', `<dt>Name 🔒</dt><dd>${esc(pname)}</dd>`);
      }
      if (o.dob != null) {
        dl.insertAdjacentHTML('beforeend',
          `<dt>Geburtsdatum 🔒</dt><dd>${esc(EdPat.datumDe(o.dob))}</dd>`);
      }
      const alter = EdPat.alterAnzeige(o, m.mission_day);
      if (alter != null) {
        dl.insertAdjacentHTML('beforeend', `<dt>Alter 🔒</dt><dd>${esc(String(alter))}</dd>`);
      }
      if (o.dx != null) {
        dl.insertAdjacentHTML('beforeend', `<dt>Diagnose 🔒</dt><dd>${esc(String(o.dx))}</dd>`);
      }
      if (o.loc && o.loc.addr) {
        dl.insertAdjacentHTML('beforeend', `<dt>Einsatzort 🔒</dt><dd>${esc(o.loc.addr)}</dd>`);
        if (o.loc.lat != null) {
          L.marker([o.loc.lat, o.loc.lon], { icon: locPin('#FF8F1F'), keyboard: false })
            .addTo(map).bindPopup('Einsatzort<br>' + esc(o.loc.addr));
          if (!bounds.length) { map.setView([o.loc.lat, o.loc.lon], 13); }
        }
      }
      // Beschreibung steht direkt unter dem Einsatzort statt in der generischen
      // Zusatzfeldliste — sie liegt seit Web 3.3.0 im pat_blob (E5).
      if (o.site_desc != null) {
        dl.insertAdjacentHTML('beforeend',
          `<dt>Beschreibung Einsatzort 🔒</dt><dd>${esc(String(o.site_desc))}</dd>`);
      }
      dl.hidden = dl.children.length === 0;
    }
  } else {
    dl.insertAdjacentHTML('beforeend',
      '<dt id="patlockdt">Verschlüsselt 🔒</dt>' +
      '<dd id="patlockdd" class="muted">gesperrt — ' +
      '<button type="button" class="btn-plain unlockbtn">Entsperren</button></dd>');
    dl.hidden = false;
    document.querySelector('#patlockdd .unlockbtn')
      .addEventListener('click', () => zeigePat(m, dl, bounds));
  }
}
init();
</script>
</body>
</html>
