<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';

// Einsatz-ID einlesen und Eigentum pruefen (liefert auch den Diensttag fuer die
// Seitenleiste). Ohne Treffer: sauberes 404.
$mid = (int)($_GET['id'] ?? 0);
$mq = db()->prepare('SELECT day_id FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
$mq->execute([$mid, $userId]);
$missionDayId = $mq->fetchColumn();
if ($missionDayId === false) { ui_abbruch(404, 'Einsatz nicht gefunden.'); }
$missionDayId = $missionDayId === null ? null : (int)$missionDayId;
$nachtrag = ($_GET['nachtrag'] ?? '') === '1';
ui_seite_start(['titel' => 'Einsatz', 'karte' => true]);
?>

<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $missionDayId]); ?>
  <?php /* Titelzeile nach E-P3-33: Rueckweg zum Diensttag, Titel „Einsatz N"
           (ab 720 px mit Uhrzeit), Unterzeile mit Zeitspanne,
           Herkunfts-Plakette und Rettungsmittel. „Bearbeiten" ist die eine
           Haupthandlung und steht als Primaerknopf am Titel; Verschieben und
           Loeschen liegen im Blatt. Der Kopf wird von init() aus der
           API-Antwort gefuellt — dieselbe Arbeitsteilung wie auf der
           Startseite. */ ?>
  <?php
    /* DER SPURZUSTAND, EINMAL (S2/AP4, E-S2-09).
     *
     * Er entscheidet zweierlei, und die beiden duerfen nicht auseinanderlaufen:
     * ob es den GPX-Abruf im Aktionsmenue ueberhaupt gibt, und was die Plakette
     * in der Metazeile sagt. Deshalb hier eine Ermittlung, nicht zwei — die
     * Plakette bekommt den Wert unten als Konstante, statt ihn noch einmal aus
     * der API zu holen und dabei vielleicht etwas anderes zu erfahren. */
    require_once __DIR__ . '/spur_lib.php';
    $spurStand  = spur_stand(db(), 'mission', $mid);
    $spurPunkte = $spurStand['stufe'] === 1
        ? (spur_zahlen(db(), 'mission', [$mid])[$mid] ?? 0)
        : $spurStand['n_gespeichert'] + max(0, $spurStand['naechste_seq'] - $spurStand['n_original']);
    $hatSpur = $spurPunkte > 0;
  ?>
  <div class="titelzeile">
    <a class="rueckweg" id="tagzurueck" href="index.php<?= $missionDayId !== null ? '?d=' . $missionDayId : '' ?>" hidden>
      <?= ui_symbol('winkel', 'symbol-links') ?><span id="tagzurueck-text"></span>
    </a>
    <div class="titelzeile-haupt">
      <div class="titelzeile-text">
        <h1 id="title">Einsatz</h1>
      </div>
      <div class="titelzeile-aktionen">
        <?= ui_knopf(['text' => 'Bearbeiten', 'art' => 'primaer', 'symbol' => 'stift',
                      'href' => 'einsatz_form.php?id=' . $mid]) ?>
        <?php
          /* DER GPX-ABRUF STEHT IM VORHANDENEN AKTIONSMENUE (S2/AP4).
           *
           * Nicht als eigener Knopf an der Karte: Die Leaflet-Karte dieser
           * Seite ist kein Baustein `karte`, sondern ein blankes
           * `<div class="geo">` ohne Kopf und ohne Platz fuer eine Aktion —
           * ein Knopf dort waere eine neue Darstellung und damit
           * freigabepflichtig (Design.md 9). Und nicht als zweiter
           * Primaerknopf neben „Bearbeiten": zwei primaere Knoepfe sind
           * ausdruecklich ein Anti-Muster.
           *
           * OHNE SYMBOL. Der Vorrat hat keines fuer „herunterladen"; ein neues
           * Zeichen braucht dieselbe Freigabe wie ein neuer Baustein. Der
           * Export-Knopf traegt aus demselben Grund keines.
           *
           * NUR WENN ES EINE SPUR GIBT. Ein Eintrag, der zu einer Fehlermeldung
           * fuehrt, ist schlechter als keiner. */
          $eintraege = [
              ['text' => 'Verschieben', 'symbol' => 'tausch',
               'href' => 'einsatz_verschieben.php?id=' . $mid],
          ];
          if ($hatSpur) {
              /* MIT RUECKFRAGE, wie beim Export. Der grosse Export laesst vor
               * dem Schreiben bestaetigen, dass die Datei personenbezogene
               * Angaben im Klartext traegt (`assets/export.js`, DIALOG_PATIENT)
               * — eine Spur endet am Einsatzort und tut damit dasselbe. Ohne
               * die Rueckfrage haette dieselbe Anwendung zwei Tueren mit zwei
               * verschiedenen Massstaeben. */
              $eintraege[] = ['text' => 'Spur als GPX',
                              'href' => 'gpx.php?art=mission&id=' . $mid,
                              'attr' => 'data-confirm="Die Datei zeigt den '
                                      . 'gefahrenen oder geflogenen Weg — also '
                                      . 'auch den Einsatzort — mit Zeitstempeln. '
                                      . 'Ab dem Speichern schützt die '
                                      . 'Verschlüsselung dieser Anwendung sie '
                                      . 'nicht mehr." data-confirm-ok="Herunterladen"'];
          }
          $eintraege[] = ['text' => 'Löschen', 'symbol' => 'korb', 'gefahr' => true,
                          'href' => 'einsatz_loeschen.php?id=' . $mid];
        ?>
        <?= ui_aktionen(['id' => 'einsatzblatt', 'titel' => 'Einsatz',
                         'eintraege' => $eintraege]) ?>
      </div>
    </div>
    <p class="titelzeile-unter" id="meta" hidden></p>
  </div>

  <div class="meldung meldung-fehler" id="loaderrorbox" role="alert" hidden>
    <?= ui_symbol('warnung', 'symbol-gross') ?>
    <p id="loaderror"></p>
  </div>

  <?php if (($_GET['verschoben'] ?? '') === '1'): ?>
    <?php /* Welchem Diensttag der Einsatz jetzt gehört, steht ohnehin im Kopf
             der Seite — die Bestätigung nennt deshalb nur, was NICHT geschehen
             ist. Genau das ist der Punkt, den man beim Verschieben wissen
             muss. */
          ui_meldung('Der Einsatz gehört jetzt zum oben genannten Diensttag. '
              . 'Die Uhrzeiten sind unverändert geblieben.', null, 'ok'); ?>
  <?php endif; ?>

  <?php if ($nachtrag):
          ui_meldung('Einsatz gespeichert.', null, 'ok', '', [
              'knopf' => ui_knopf(['text' => 'Weiteren Einsatz nachtragen',
                                   'art' => 'neutral',
                                   'href' => 'einsatz_form.php?d=' . (int)$missionDayId]),
          ]); ?>
  <?php endif; ?>

  <?php /* Der Zustand der geschuetzten Angaben als EINE Meldung ueber den
           Karten (E-P3-33) — nicht mehr als Zeile in der Feldliste. Drei
           Faelle, JS blendet den passenden ein: gesperrt (mit Entsperrknopf),
           entsperrt, unlesbar. */ ?>
  <div class="meldung meldung-info" id="lockbanner" role="status" hidden>
    <?= ui_symbol('schloss', 'symbol-gross') ?>
    <p>Geschützte Angaben sind gesperrt — Einsatzort, PatientIn und Diagnose
       bleiben verborgen, bis die Verschlüsselung entsperrt ist.</p>
    <div class="meldung-aktion">
      <?= ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                    'typ' => 'button', 'attr' => ' id="unlockbtn"']) ?>
    </div>
  </div>
  <div class="meldung meldung-info" id="freibanner" role="status" hidden>
    <?= ui_symbol('schloss-offen', 'symbol-gross') ?>
    <p>Geschützte Angaben sind entsperrt, bis du dich abmeldest.</p>
  </div>
  <div class="meldung meldung-fehler" id="patfehlerbanner" role="alert" hidden>
    <?= ui_symbol('warnung', 'symbol-gross') ?>
    <p>Für diesen Einsatz sind geschützte Angaben gespeichert, sie lassen sich
       mit dem aktuellen Schlüssel aber <strong>nicht lesen</strong>. Die Daten
       sind vorhanden und nicht verloren. Bitte den Wiederherstellungsschlüssel
       bereithalten und vor weiteren Schritten klären, warum der Schlüssel
       nicht passt.</p>
  </div>

  <?php /* Vier Karten in der Rangfolge aus E-P3-33, dazu Besatzung (sie stand
           schon immer auf dieser Seite; die Karte ist ihre neue Huelle). Die
           DOM-Reihenfolge ist die MOBILE Reihenfolge (Mockup 19): Angaben,
           Karte, Phasen, Reanimation. Ab 1200 px zieht das Raster Karte und
           Phasen in die rechte Spalte (klebend) und die Reanimation in die
           linke — Mockup 20. Leere Felder werden nicht gerendert, leere
           Karten bleiben versteckt. */ ?>
  <div class="einsatz-raster">
    <section class="karte karte-block-einsatz" hidden>
      <div class="karte-kopf"><h2 class="karte-titel">Einsatz</h2></div>
      <div class="karte-inhalt">
        <div class="tag-lese" id="liste-einsatz"></div>
        <div class="zeile-plaketten" id="plaketten" hidden></div>
      </div>
    </section>

    <?php /* PLAKETTE STATT NEUN SCHLOESSERN (F-N1-B). Bis O4 trug jedes
             geschuetzte Feld ein Schloss-Emoji; O4 hat sie durch EINE Meldung
             ueber den Karten ersetzt — und damit die Auskunft verloren,
             WELCHES Feld geschuetzt ist. Sie kommt zurueck, aber an der
             richtigen Ebene: In dieser Karte ist ALLES verschluesselt, also
             sagt es die Karte einmal. Die drei geschuetzten Felder der
             Einsatz-Karte stehen zwischen Klartextfeldern und tragen ihr
             Schloss einzeln (siehe zeigePat). */ ?>
    <section class="karte karte-block-patientin" hidden>
      <div class="karte-kopf">
        <h2 class="karte-titel">PatientIn</h2>
        <?= ui_plakette('verschlüsselt', ['ton' => 'blau']) ?>
      </div>
      <div class="karte-inhalt"><div class="tag-lese" id="liste-patientin"></div></div>
    </section>

    <section class="karte karte-block-transport" hidden>
      <div class="karte-kopf"><h2 class="karte-titel">Transport</h2></div>
      <div class="karte-inhalt"><div class="tag-lese" id="liste-transport"></div></div>
    </section>

    <section class="karte karte-block-besatzung" id="crew-section" hidden>
      <div class="karte-kopf"><h2 class="karte-titel">Besatzung</h2></div>
      <div class="karte-inhalt"><div class="tag-lese" id="crewlist"></div></div>
    </section>

    <div class="einsatz-neben">
      <div class="geo-spalte"><div id="map" class="geo"></div></div>
      <section class="karte karte-block-phasen" id="phasen-karte" hidden>
        <div class="karte-kopf">
          <h2 class="karte-titel">Einsatzphasen</h2>
          <span class="karte-zahl" id="phasendauer"></span>
        </div>
        <?php /* „Einsatzphasen" statt „Phasen" (Web 7.0.0): Im Gespraech und
                 in der Uhr-App heisst es durchgaengig Einsatzphase, und eine
                 Ueberschrift, die anders heisst als die Sache, kostet bei
                 jedem Hinsehen einen Gedanken. */ ?>
        <div class="phasen" id="phasebody"></div>
      </section>
    </div>

    <?php /* VERSTECKT, BIS ES ETWAS ZU ZEIGEN GIBT (F-N1-H). Die Karte stand
             bisher immer da und sagte „keine" — auf einer Seite, auf der
             jede andere leere Karte verschwindet (E-P3-33: „leere Felder
             werden nicht gerendert, leere Karten bleiben versteckt"). Sie
             war die einzige Ausnahme, und sie kostete auf jedem Einsatz ohne
             Reanimation eine Karte Aufmerksamkeit fuer die Auskunft, dass
             nichts passiert ist. Das Skript blendet sie ein, wenn es eine
             Sitzung gibt. */ ?>
    <section class="karte karte-block-reanimation" id="resus-section" hidden>
      <div class="karte-kopf">
        <h2 class="karte-titel">Reanimation</h2>
        <span class="karte-zahl" id="resus-zahl"></span>
      </div>
      <div id="resus-tables" hidden></div>
    </section>
  </div><?php /* .einsatz-raster */ ?>

<?php ui_geruest_ende(); ?>
<?php /* Ruestzeug der Verschluesselung (Baustein ui_krypto_bootstrap()).
         OHNE PAT_WRAP: Diese Seite bekommt die Huelle aus der API-Antwort
         (m.pat_wrap), nicht aus PHP. */ ?>
<?php ui_krypto_bootstrap(['wrap' => false]); ?>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script src="<?= asset('assets/geo.js') ?>"></script>
<script src="<?= asset('assets/luftlinie.js') ?>"></script>
<script>
const MID = <?= $mid ?>;
/* Der Spurzustand kommt vom Server (S2/AP4) und nicht aus der API-Antwort:
   Dasselbe Ergebnis entscheidet oben ueber den Menueeintrag; zwei Quellen
   koennten auseinanderlaufen. */
const SPUR = <?= json_encode(['hat' => $hatSpur, 'stufe' => (int)$spurStand['stufe'],
                              'n' => (int)$spurPunkte,
                              'n0' => (int)$spurStand['n_original']]) ?>;

// Maskierung: Baustein B7 (assets/html.js). Hier stand eine eigene Fassung
// ueber ein Hilfselement — sie maskierte drei Zeichen statt fuenf (M6-03).
const esc = EdHtml.escape;
function fmtDay(d){ const p = d.split('-'); return `${p[2]}.${p[1]}.${p[0]}`; }
function zeigeLadeFehler(msg){
  document.getElementById('title').textContent = 'Einsatz nicht geladen';
  document.getElementById('loaderror').textContent =
    'Die Einsatzdaten konnten nicht geladen werden: ' + msg;
  document.getElementById('loaderrorbox').hidden = false;
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
function trackWeight(){
  // Duenne Linien: bei kleinem Massstab wirkten dicke Striche wie Balken.
  const z = map.getZoom();
  return z >= 14 ? 3 : z >= 10 ? 4 : 5;
}
map.on('zoomend', () => {
  const w = trackWeight();
  trackLines.forEach(l => l.setStyle({ weight: w }));
});

let M = null;                  // die API-Antwort — hlPhase braucht Track und Phasen
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
        html: `<span class="pm-chip" data-idx="${e2.idx}">${e2.p.phase}</span>`,
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

/* ---- Teilstueck der angetippten Phase (E-P3-33, Mockup 20) ----------------
 *
 * Die angetippte Phase hebt ihr Teilstueck des Tracks hervor: den Abschnitt
 * von der vorigen Phase mit GPS-Position bis zu ihr selbst, in Blau ueber der
 * orangen Spur. Der Track traegt keine Zeitstempel — die Enden des Abschnitts
 * werden deshalb als naechstgelegene Trackpunkte der beiden Phasenpositionen
 * bestimmt. Ohne GPS an einer der Phasen bleibt es bei Zeile und Marker. */
const teilLinien = {};
function naechsterTrackpunkt(lat, lon){
  let best = 0, bd = Infinity;
  M.track.forEach((p, i) => {
    const dx = p[0] - lat, dy = p[1] - lon, d = dx * dx + dy * dy;
    if (d < bd) { bd = d; best = i; }
  });
  return best;
}
/* Wo eine Phase auf der Spur liegt: bevorzugt der Server-Index nach
 * Zeitstempel (track_idx, api/mission.php) — er funktioniert auch dort, wo
 * die Uhr fuer die Phase keinen GPS-Fix hatte. Nur als Rueckfall (aeltere
 * Antworten) der naechste Punkt zur Phasen-Koordinate. */
function phasenIndex(p){
  if (!p) { return null; }
  if (p.track_idx != null) { return p.track_idx; }
  if (p.lat != null) { return naechsterTrackpunkt(p.lat, p.lon); }
  return null;
}
function teilstueck(idx){
  if (!M || M.track.length < 2) { return null; }
  let b = phasenIndex(M.phases[idx]);
  if (b == null) { return null; }
  let j = idx - 1, a = null;
  while (j >= 0 && (a = phasenIndex(M.phases[j])) == null) { j--; }
  if (a == null) { return null; }
  if (a > b) { const t = a; a = b; b = t; }
  if (b - a < 1) { return null; }
  return M.track.slice(a, b + 1);
}

let hlActive = {};
function hlPhase(idx, on){
  if (on === 'toggle') { on = !hlActive[idx]; }
  hlActive[idx] = on;
  const row = document.querySelector(`#phasebody .phasen-zeile[data-idx="${idx}"]`);
  if (row) row.classList.toggle('hl', on);
  const chip = document.querySelector(`.pm-chip[data-idx="${idx}"]`);
  if (chip) {
    chip.classList.toggle('hl', on);
    const pm = phaseMarkers.find(e2 => e2.idx === idx);
    if (pm) pm.marker.setZIndexOffset(on ? 1000 : 0);
  }
  if (on && !teilLinien[idx]) {
    const seg = teilstueck(idx);
    if (seg) {
      teilLinien[idx] = L.polyline(seg, {
        color: EdGeo.spurFarbe(1), weight: trackWeight() + 1, smoothFactor: 0
      }).addTo(map);
    }
  } else if (!on && teilLinien[idx]) {
    map.removeLayer(teilLinien[idx]);
    delete teilLinien[idx];
  }
}

/* ---- Reihenfolge innerhalb der Karten (Web 7.0.0, seit O4 je Karte) ------
 *
 * Jede Zeile hat einen RANG, und eingefuegt wird an der Stelle, an die sie
 * gehoert — unabhaengig davon, wann ihr Wert eintrifft (die geschuetzten
 * Angaben kommen erst nach dem Entsperren an). Die Ordnung folgt dem Gang
 * der Dokumentation. Ein Feld ohne Eintrag hier bekommt RANG_SONST und steht
 * am Ende der Einsatz-Karte statt zu verschwinden: Ein neues Katalogfeld
 * erscheint auch ohne Aenderung an dieser Liste. */
const RANG_SONST = 900;
/* Ob die Hoehe des Einsatzorts gezeigt wird und wie hoch sie liegt. Beides
 * entscheidet init(), gebraucht wird es in zeigePat(): Dort entsteht die
 * Zeile „Einsatzort", und die Hoehe gehoert in ihre Kleinzeile. */
let hoeheZeigen = false;
let hoeheWert = null;
const RANG = {
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
  bw_info:        142,
  transport_mode: 150,
  transport_dest: 160,
  schockraum:     170
};

/* In welcher Karte eine Zeile landet. Fehlt ein Feld hier, faellt es in die
 * Einsatz-Karte — dieselbe Rueckfalllinie wie RANG_SONST. */
const KARTE_ZIEL = {
  mission_no: 'patientin', pat_name: 'patientin', pat_dob: 'patientin',
  transport_mode: 'transport', transport_dest: 'transport', schockraum: 'transport'
};
const LISTEN = {
  einsatz: 'liste-einsatz', patientin: 'liste-patientin', transport: 'liste-transport'
};

/**
 * Eine Zeile in die Leseansicht einer Karte einsortieren und die Karte
 * sichtbar machen. dt und dd sind fertiges HTML.
 */
function zeile(karte, rang, dt, dd){
  const dl = document.getElementById(LISTEN[karte] || LISTEN.einsatz);
  const feld = document.createElement('div');
  /* Schlichtes tagfeld, NICHT tagfeld-doppelt: „doppelt" ist die
   * Startseiten-Logik „mobil ausblenden, weil schon in der Unterzeile" —
   * hier gilt sie nicht, und die Liste ist ohnehin einspaltig. */
  feld.className = 'tagfeld';
  feld.dataset.rang = rang;
  feld.innerHTML = `<dt>${dt}</dt><dd>${dd}</dd>`;
  const vor = [...dl.children].find(el => Number(el.dataset.rang) > rang) || null;
  dl.insertBefore(feld, vor);
  dl.closest('.karte').hidden = false;
}

/** Zeile mit dieser Kennung wieder entfernen (Hoehen-Ersatzzeile nach dem
 *  Entsperren, siehe zeigePat). */
function zeileEntferne(id){
  document.querySelectorAll(`[data-zeile="${id}"]`).forEach(el => el.remove());
}
/** Die zuletzt eingefuegte Zeile eines Rangs kennzeichnen, damit
 *  zeileEntferne() sie wiederfindet. */
function markiere(rang, id){
  document.querySelectorAll(`[data-rang="${rang}"]`)
    .forEach(el => { el.dataset.zeile = id; });
}

function plakette(ton, text){
  return `<span class="plakette plakette-${ton}">${esc(text)}</span>`;
}
/* Beschriftung eines Feldes, das im pat_blob liegt (F-N1-B). Nur fuer Felder
 * der Einsatz-Karte: Dort stehen sie zwischen Klartextfeldern, und ohne
 * Kennzeichen sieht man ihnen nicht an, dass sie den Server nie im Klartext
 * erreichen. In der PatientIn-Karte waere das Zeichen an jeder Zeile Laerm —
 * dort sagt es die Plakette der Karte einmal.
 *
 * Das <title> ist keine Zier: Ohne es waere das Symbol fuer einen
 * Bildschirmleser stumm (aria-hidden), und die Auskunft ginge genau denen
 * verloren, die sie nicht sehen koennen. */
function dtGeschuetzt(text){
  return esc(text)
    + (typeof edSymbol === 'function'
       ? edSymbol('schloss', 'symbol-schutz', 'Ende-zu-Ende-verschlüsselt') : '');
}
function fmtKm(meter){
  return (meter / 1000).toFixed(1).replace('.', ',') + ' km';
}
/* Dauer im Stil der Tagesuebersicht („51min", „1h 32min") — nicht „0:51 h"
 * wie im Mockup: Die Schreibweise ist projektweit dieselbe (E-P3-32,
 * dokumentierte Abweichung). */
function fmtDauer(min){
  if (min == null || min < 0) { return ''; }
  const h = Math.floor(min / 60), r = min % 60;
  return h > 0 ? `${h}h ${r}min` : `${r}min`;
}
function minutenDiff(a, b){
  const [ah, am] = a.split(':').map(Number), [bh, bm] = b.split(':').map(Number);
  let d = (bh * 60 + bm) - (ah * 60 + am);
  if (d < 0) { d += 24 * 60; }   // ueber Mitternacht
  return d;
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
  M = m;

  /* Rueckweg „‹ Sa, 22.08.2026" — Wochentag lang/kurz wie der Titel der
   * Startseite (dieselben Utility-Klassen). */
  if (m.day) {
    const dt = new Date(m.day + 'T12:00:00');
    const LANG = ['Sonntag','Montag','Dienstag','Mittwoch','Donnerstag','Freitag','Samstag'];
    const KURZ = ['So','Mo','Di','Mi','Do','Fr','Sa'];
    const wt = dt.getDay();
    document.getElementById('tagzurueck-text').innerHTML =
      '<span class="wtag-lang">' + LANG[wt] + '</span>'
      + '<span class="wtag-kurz">' + KURZ[wt] + '</span>, ' + fmtDay(m.day);
    document.getElementById('tagzurueck').hidden = false;
  }

  /* Titel „Einsatz N", ab 720 px mit „· 07:42 Uhr" (E-P3-33). */
  document.getElementById('title').innerHTML =
    `Einsatz ${m.day_no}<span class="nur-ab-720"> · ${esc(m.start_hhmm)} Uhr</span>`;

  /* Unterzeile: Zeitspanne, Herkunfts-Plakette (dazu „editiert", wenn von
   * Hand veraendert), Rettungsmittel und Standort aus den EINGEFRORENEN
   * Spalten des Diensttags (E8). Faellt das Datum des Dienstes vom echten
   * Einsatzdatum ab (E9), wird der Dienst ausdruecklich genannt — ohne das
   * saehe die Zuordnung wie ein Fehler aus. */
  const ORIGIN_LABEL = { watch: 'Uhr', manual: 'manuell', import: 'importiert' };
  const zeitteil = m.has_p9
    ? `${m.start_hhmm} – ${m.end_hhmm} Uhr`
    : `${m.start_hhmm} Uhr – kein Ende`;
  /* SPUR-PLAKETTE (S2/AP4, E-S2-09): welche Fassung der Spur hier liegt.
     Sie steht in derselben Zeile wie „Uhr" und „editiert" — auch das sind
     Aussagen ueber die Beschaffenheit des Datensatzes, nicht ueber den
     Einsatz. Ohne Spur keine Plakette; ein leeres Etikett erklaert nichts. */
  const spurPlakette = SPUR.hat
    ? ' ' + plakette(SPUR.stufe === 3 ? 'orange' : 'neutral',
        SPUR.stufe === 3
          ? `Spur ausgedünnt · ${SPUR.n} von ${SPUR.n0} Punkten`
          : `Spur · ${SPUR.n} Punkte`)
    : '';
  const kennzeichen = plakette('neutral', ORIGIN_LABEL[m.origin] || 'Uhr')
    + (m.edited ? ' ' + plakette('neutral', 'editiert') : '')
    + spurPlakette;
  const rest = [];
  if (m.day_vehicle_name) { rest.push(m.day_vehicle_name); }
  if (m.day_base_name) { rest.push(m.day_base_name); }
  if (!m.day) { rest.push('kein Diensttag zugeordnet'); }
  else if (m.day !== m.mission_day) { rest.push(`Dienst vom ${fmtDay(m.day)}`); }
  const meta = document.getElementById('meta');
  meta.innerHTML = esc(zeitteil) + ' ' + kennzeichen
    + (rest.length ? ' ' + esc(rest.join(' · ')) : '');
  meta.hidden = false;

  /* Zusatzfelder (Server liefert nur befuellte) auf die Karten verteilen.
   * Winde, Bergwacht, Sekundaer und Fehleinsatz werden NICHT als Zeilen
   * gerendert, sondern unten als Plaketten gebuendelt (E-P3-33). */
  const wert = {};
  m.fields.forEach(f => { wert[f.col] = f.value; });
  const PLAKETTEN_FELDER = new Set(['secondary', 'false_alarm', 'winch',
    'winch_cycles', 'winch_cycles_pat', 'winch_airload', 'bergwacht',
    'bw_unit', 'na_escort']);
  m.fields.forEach(f => {
    if (PLAKETTEN_FELDER.has(f.col)) { return; }
    let dd = esc(f.value);
    /* Transportart und NA-Begleitung in EINER Zeile: „Luft, mit
     * NA-Begleitung" — die Begleitung ist eine Eigenschaft des Transports,
     * keine zweite Angabe (Mockup 19). */
    if (f.col === 'transport_mode' && wert.na_escort) { dd += ', mit NA-Begleitung'; }
    zeile(KARTE_ZIEL[f.col] || 'einsatz', RANG[f.col] ?? RANG_SONST, esc(f.label), dd);
  });

  const plaketten = [];
  if (wert.winch) {
    let t = 'Winde';
    if (wert.winch_cycles) { t += ' · ' + wert.winch_cycles + ' Cycles'; }
    if (wert.winch_cycles_pat) { t += ', ' + wert.winch_cycles_pat + ' mit PatientIn'; }
    if (wert.winch_airload) { t += ' · Luftverladung'; }
    plaketten.push(plakette('orange', t));
  }
  if (wert.bergwacht) {
    /* Traegt der Einheitsname das Wort schon („Bergwacht Sonnenau"), wird
     * nicht noch einmal „Bergwacht" davorgesetzt. */
    const bw = wert.bw_unit
      ? (/^bergwacht/i.test(wert.bw_unit) ? wert.bw_unit : 'Bergwacht ' + wert.bw_unit)
      : 'Bergwacht';
    plaketten.push(plakette('orange', bw));
  }
  if (wert.secondary) { plaketten.push(plakette('blau', 'Sekundär')); }
  if (wert.false_alarm) { plaketten.push(plakette('rot', 'Fehleinsatz')); }
  if (plaketten.length) {
    const box = document.getElementById('plaketten');
    box.innerHTML = plaketten.join('');
    box.hidden = false;
    box.closest('.karte').hidden = false;
  }

  /* HOEHE DES EINSATZORTS — nur luftgebunden (A13, Konzept 4.6). Sie gehoert
   * in die Kleinzeile des Einsatzortes (Mockup 19); solange der verschluesselt
   * ist, traegt eine eigene Zeile sie trotzdem: Die Hoehe selbst liegt im
   * Klartext, sie zu verschweigen waere kein Gewinn. Bodengebunden ist sie
   * die Hoehe der Strasse und bleibt verborgen; bei einem noch nicht
   * zugeordneten Diensttag (day_kind === null, E26) ebenso. */
  hoeheZeigen = m.site_ele_m != null && m.day_kind === 'air';
  hoeheWert = m.site_ele_m;
  if (hoeheZeigen) {
    zeile('einsatz', RANG.pat_loc, 'Höhe Einsatzort', `${m.site_ele_m} m`);
    markiere(RANG.pat_loc, 'hoehe');
  }

  // Besatzung: Tagescrew, einzelne Rollen ggf. durch den Einsatz ueberschrieben
  // (Server hat die COALESCE-Regel bereits angewandt, siehe api/mission.php).
  const crewList = document.getElementById('crewlist');
  Object.values(m.crew_effektiv || {}).forEach(c => {
    crewList.insertAdjacentHTML('beforeend',
      `<div class="tagfeld"><dt>${esc(c.label)}</dt><dd>${esc(c.name)}`
      + (c.abw ? ' <span class="lese-klein">(abweichend vom Diensttag)</span>' : '')
      + '</dd></div>');
  });
  document.getElementById('crew-section').hidden = crewList.children.length === 0;

  /* ---- Karte: Spur, Schilder, Ringe, Pfeile (E-P3-33/40, Mockup 26) ------
   *
   * Die Spur faehrt in der ersten Spurfarbe (Orange). Start und Ende der
   * Spur sind Ringe: blau = Start, rot = Ende — am Schild des Ortes, an dem
   * die Spur beginnt oder endet, sonst als eigener Ringpunkt. Der Standort
   * kommt als Haus-Schild aus den eingefrorenen Tageskoordinaten, das
   * Transportziel als Klinik-Schild (Klartext wie sein Name, E40, A13o). */
  const bounds = [];
  const NAH_M = 200;   // naeher als das gilt als „am Schild"
  const start = m.track.length > 1 ? m.track[0] : null;
  const ende  = m.track.length > 1 ? m.track[m.track.length - 1] : null;
  function ringFuer(latlng){
    if (!latlng) { return ''; }
    const ll = L.latLng(latlng);
    const s = start && ll.distanceTo(L.latLng(start)) < NAH_M;
    const e2 = ende && ll.distanceTo(L.latLng(ende)) < NAH_M;
    return s && e2 ? 'beide' : s ? 'start' : e2 ? 'ende' : '';
  }
  const schildOrte = [];
  if (m.base_lat != null && m.base_lon != null) {
    const ll = [m.base_lat, m.base_lon];
    EdGeo.markerStandort(ll, m.day_base_name || 'Standort', ringFuer(ll)).addTo(map);
    schildOrte.push(ll);
    bounds.push(ll);
  }
  if (m.dest_lat != null && m.dest_lon != null) {
    const ll = [m.dest_lat, m.dest_lon];
    EdGeo.markerZiel(ll, m.dest_name || 'Zielklinik', ringFuer(ll)).addTo(map);
    schildOrte.push(ll);
    bounds.push(ll);
  }
  function ringLos(latlng, art, titel){
    // Nur wenn kein Schild in der Naehe den Ring schon traegt.
    const ll = L.latLng(latlng);
    if (schildOrte.some(s => ll.distanceTo(L.latLng(s)) < NAH_M)) { return; }
    EdGeo.markerRing(latlng, art, titel).addTo(map);
  }
  if (m.track.length > 1) {
    const line = L.polyline(m.track, {
      color: EdGeo.spurFarbe(0), weight: trackWeight(), smoothFactor: 0
    }).addTo(map);
    trackLines.push(line);
    EdGeo.pfeile(map, map, m.track);
    const beide = L.latLng(start).distanceTo(L.latLng(ende)) < NAH_M;
    if (beide) { ringLos(start, 'beide', 'Start und Ende der Aufzeichnung'); }
    else {
      ringLos(start, 'start', 'Start der Aufzeichnung');
      ringLos(ende, 'ende', 'Ende der Aufzeichnung');
    }
    m.track.forEach(p => bounds.push(p));
  }

  if (bounds.length) {
    /* Rand proportional zur Kartengroesse; PADDING IST (x, y) — F-P3-Z. Und
     * eine Zoom-Obergrenze, damit ein sehr kurzer Track (oder ein einzelner
     * Punkt) nicht bis auf Gebaeude-Ebene heranzoomt. */
    const px = map.getSize();
    map.fitBounds(bounds, { padding: L.point(px.x * 0.125, px.y * 0.125), maxZoom: 15 });
  }
  else { map.setView([47.7, 10.3], 9); }

  /* ---- Einsatzphasen: Zeilen mit Minutenabstand (E-P3-33, Mockup 19) -----
   * Nummer, Name, Uhrzeit; klein darunter der Abstand zur vorigen Phase.
   * Im Kartenkopf die Gesamtdauer. Hover/Tipp koppelt an die Karte. */
  const pb = document.getElementById('phasebody');
  let vorige = null;
  m.phases.forEach((p, idx) => {
    const abstand = vorige ? minutenDiff(vorige, p.time) : null;
    const row = document.createElement('div');
    row.className = 'phasen-zeile';
    row.dataset.idx = idx;
    row.innerHTML = `<span class="phasen-nr">${p.phase}</span>`
      + `<span class="phasen-name">${esc(p.label)}</span>`
      + `<span class="phasen-zeit">${p.time}`
      + (abstand != null ? `<span class="phasen-abstand">+${abstand} min</span>` : '')
      + '</span>';
    row.addEventListener('mouseenter', () => hlPhase(idx, true));
    row.addEventListener('mouseleave', () => hlPhase(idx, false));
    row.addEventListener('click', () => hlPhase(idx, 'toggle'));
    pb.appendChild(row);
    vorige = p.time;
  });
  if (m.phases.length) {
    document.getElementById('phasen-karte').hidden = false;
    if (m.has_p9) {
      document.getElementById('phasendauer').textContent =
        fmtDauer(minutenDiff(m.start_hhmm, m.end_hhmm));
    }
  }
  buildPhaseMarkers(m.phases);

  /* Reanimation: OHNE SITZUNG BLEIBT DIE KARTE FORT (F-N1-H) — wie jede
   * andere leere Karte dieser Seite. Mit Sitzung eine Ereignisliste je
   * Sitzung, im Zeilenbild der Phasen. */
  if (m.resus && m.resus.length) {
    document.getElementById('resus-section').hidden = false;
    document.getElementById('resus-zahl').textContent =
      m.resus.length > 1 ? String(m.resus.length) : '';
    const wrap = document.getElementById('resus-tables');
    wrap.hidden = false;
    m.resus.forEach((events, i) => {
      if (m.resus.length > 1) {
        wrap.insertAdjacentHTML('beforeend',
          `<h3 class="phasen-zwischentitel">Reanimation ${i + 1}</h3>`);
      }
      const liste = document.createElement('div');
      liste.className = 'phasen';
      events.forEach(e2 => {
        liste.insertAdjacentHTML('beforeend',
          `<div class="phasen-zeile"><span class="phasen-nr"></span>`
          + `<span class="phasen-name">${esc(e2.label)}</span>`
          + `<span class="phasen-zeit">${e2.time}</span></div>`);
      });
      wrap.appendChild(liste);
    });
  }

  // Verschluesselte Angaben (Diagnose, Alter, Einsatzort) lokal entschluesseln
  if (m.pat_blob && m.pat_wrap) { await zeigePat(m, bounds); }
}

/* Geschuetzte Angaben anzeigen. Ist der Inhaltsschluessel gesperrt, bietet
 * EdUnlock den Entsperrdialog an; bei Abbruch bleibt die Sperr-Meldung
 * stehen, deren Knopf diese Funktion erneut aufruft (E-P3-33: der Zustand
 * steht als Meldung ueber den Karten, nicht als Zeile in der Liste). */
async function zeigePat(m, bounds){
  const ck = await EdUnlock.ensureContentKey(m.pat_wrap, KDF_SALT, KDF_ITER);
  if (!ck) {
    document.getElementById('lockbanner').hidden = false;
    return;
  }
  document.getElementById('lockbanner').hidden = true;

  const r = await EdPat.entschluessle(ck, m.pat_blob);
  if (r.zustand === 'unlesbar') {
    // Auf der Einzelansicht sieht die NutzerIn genau einen Einsatz. Ein
    // stiller Fehlschlag saehe hier aus wie "keine geschuetzten Angaben
    // erfasst" — also wie ein normaler, unauffaelliger Zustand. Deshalb die
    // deutliche Fehlermeldung.
    document.getElementById('patfehlerbanner').hidden = false;
    return;
  }
  document.getElementById('freibanner').hidden = false;

  const o = r.daten || {};
  if (o.mission_no != null && String(o.mission_no) !== '') {
    zeile('patientin', RANG.mission_no, 'Einsatznummer', esc(String(o.mission_no)));
  }
  const pname = EdPat.name(o);
  if (pname !== '') {
    zeile('patientin', RANG.pat_name, 'Name', esc(pname));
  }
  /* GEBURTSDATUM UND ALTER IN EINER ZEILE (Web 7.0.0): Das Alter FOLGT aus
     dem Geburtsdatum, es ist keine zweite Angabe. Die Einheit wechselt mit
     dem Alter (EdPat.alterText): Bei einem Saeugling ist „0" keine Auskunft,
     „3 Monate" oder „12 Tage" schon. Ohne Geburtsdatum bleibt es bei der
     Zeile „Alter" — dort steht dann der von Hand eingetragene, meist
     geschaetzte Wert. */
  const alterTxt = EdPat.alterText(o, m.mission_day);
  if (o.dob != null) {
    zeile('patientin', RANG.pat_dob, 'Geboren',
      esc(EdPat.datumDe(o.dob))
      + (alterTxt ? `<span class="lese-klein">${esc(alterTxt)}</span>` : ''));
  } else if (alterTxt) {
    zeile('patientin', RANG.pat_dob, 'Alter', esc(alterTxt));
  }
  /* EINSATZORT MIT KLEINZEILE (Mockup 19): Hoehe, Luftlinie und Strecke sind
     Eigenschaften DIESES Ortes bzw. des Weges dorthin und stehen klein unter
     der Adresse. Die Ersatzzeile der Hoehe aus init() verschwindet dafuer;
     die Luftlinie traegt zeichneLuftlinie() nach, sobald sie gerechnet ist. */
  if (o.loc && o.loc.addr) {
    if (hoeheZeigen) { zeileEntferne('hoehe'); }
    const klein = [];
    if (hoeheZeigen) { klein.push(`${hoeheWert} m`); }
    if (m.distance_m != null) { klein.push('Strecke ' + fmtKm(m.distance_m)); }
    zeile('einsatz', RANG.pat_loc, dtGeschuetzt('Einsatzort'),
      esc(o.loc.addr)
      + `<span class="lese-klein" id="ortklein"${klein.length ? '' : ' hidden'}>`
      + esc(klein.join(' · ')) + '</span>');
    if (o.loc.lat != null) {
      EdGeo.markerEinsatzort([o.loc.lat, o.loc.lon],
        'Einsatzort<br>' + esc(o.loc.addr)).addTo(map);
      if (!bounds.length) { map.setView([o.loc.lat, o.loc.lon], 13); }
    }
  }
  // Beschreibung steht direkt unter dem Einsatzort statt in der generischen
  // Zusatzfeldliste — sie liegt seit Web 3.3.0 im pat_blob (E5).
  if (o.site_desc != null) {
    zeile('einsatz', RANG.pat_site_desc, dtGeschuetzt('Beschreibung Einsatzort'),
          esc(String(o.site_desc)));
  }
  if (o.dx != null) {
    zeile('einsatz', RANG.pat_dx, dtGeschuetzt('Diagnose'), esc(String(o.dx)));
  }
  await zeichneLuftlinie(m, o, ck, bounds);
}

/* ---- Luftlinie ohne GPS-Aufzeichnung (E34/E35, A13g–A13i, A13n) ----------
 *
 * Sie steht hier, im entschluesselten Teil: Ihr mittlerer Stuetzpunkt ist der
 * Einsatzort, und der liegt im pat_blob. Ohne Freischalten gibt es deshalb
 * keine Linie — auch dann nicht, wenn Abfahrtort und Zielklinik im Klartext
 * bekannt waeren (A13o).
 *
 * Die Regeln stehen in assets/luftlinie.js und nicht hier, weil die
 * Tagesuebersicht dieselben braucht. Diese Funktion beschafft nur die vier
 * moeglichen Quellen des Abfahrtorts — zwei hat der Server bereits aufgeloest,
 * zwei sind verschluesselt und lassen sich nur hier lesen. */
async function zeichneLuftlinie(m, o, ck, bounds){
  /* „Letzter Einsatzort" ist der Einsatzort des VORGAENGERS und damit
   * verschluesselt. Der Server liefert dessen Blob nur bei genau dieser Regel
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
  // Punkt am Abfahrtort. Der Einsatzort hat seinen eigenen Marker (oben), die
  // Zielklinik ihr Schild — bleibt der Abfahrtort.
  if (abfahrt) {
    EdGeo.markerPunkt([abfahrt.lat, abfahrt.lon], EdGeo.spurFarbe(0),
      'Abfahrtort' + (abfahrt.text ? '<br>' + esc(abfahrt.text) : '')).addTo(map);
  }
  /* Die Laenge in der Kleinzeile des Einsatzortes (Mockup 19), ausdruecklich
   * BENANNT — eine Luftlinie ist keine gefahrene Strecke (E36); die Erklaerung
   * „gerade Verbindung, kein aufgezeichneter Weg" steht im Popup der Linie.
   * Gibt es keine Einsatzort-Zeile (Adresse leer), eine eigene Zeile. */
  const klein = document.getElementById('ortklein');
  const text = 'Luftlinie ' + fmtKm(EdLuftlinie.meter(punkte));
  if (klein) {
    klein.textContent = klein.textContent
      ? klein.textContent + ' · ' + text : text;
    klein.hidden = false;
  } else {
    zeile('einsatz', RANG.luftlinie, 'Luftlinie',
      `${esc(fmtKm(EdLuftlinie.meter(punkte)))}
       <span class="lese-klein">gerade Verbindung, kein aufgezeichneter Weg</span>`);
  }

  const px = map.getSize();
  map.fitBounds(bounds.concat(punkte.map(p => [p.lat, p.lon])),
    { padding: L.point(px.x * 0.125, px.y * 0.125), maxZoom: 15 });
}

document.getElementById('unlockbtn').addEventListener('click', () => {
  if (M) { zeigePat(M, []); }
});

init();
</script>
<?php ui_seite_ende(); ?>
