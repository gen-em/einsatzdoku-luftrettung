<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/**
 * Suche ueber den gesamten Einsatzbestand.
 *
 * Der Server liefert hier nur das Geruest. Den Bestand holt der Browser einmal
 * von api/suchindex.php und filtert danach vollstaendig lokal — ohne weitere
 * Serveranfragen und ohne dass ein Suchbegriff das Geraet verlaesst. Das ist
 * keine Bequemlichkeit, sondern Bedingung: Einsatznummer, Name, Geburtsdatum,
 * Diagnose und Einsatzort liegen Ende-zu-Ende-verschluesselt im pat_blob. Eine
 * serverseitige Suche darueber ist konstruktionsbedingt unmoeglich, und ein
 * Suchbegriff wie ein Nachname waere selbst schon ein Patientendatum.
 *
 * Der vollstaendige Filterzustand steht im URL-Fragment (#...), nie im
 * Query-String — Fragmente werden nicht an den Server gesendet und landen
 * daher nicht im Zugriffsprotokoll. Die Parameternamen sind in Technik.md
 * dokumentiert.
 */
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Suche · Einsatzdoku</title>
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('suche'); ?>
<div class="layout layout-suche">
  <!-- Statt der Einsatztage-Leiste: die Filter. Auf der Suchseite waeren
       einzelne Tage sinnlos, hier geht es gerade um den Gesamtbestand.
       Bewusst NICHT die Klasse .daylist wiederverwendet — die ist auf feste
       Fensterhoehe mit overflow:hidden gesetzt und wuerde eine lange
       Filterliste abschneiden. -->
  <aside class="filterspalte">
    <h2>Filter</h2>

    <div class="filtergruppen">
      <details class="filtergruppe" data-gruppe="zeit">
        <summary>Zeit</summary>
        <div class="filterfelder">
          <label>Datum von <input type="date" id="f-dv"></label>
          <label>Datum bis <input type="date" id="f-db"></label>
          <label>Alarmzeit von <input type="time" id="f-zv"></label>
          <label>Alarmzeit bis <input type="time" id="f-zb"></label>
          <div class="wochentage" id="f-wd">
            <span class="wtlabel">Wochentag</span>
            <label><input type="checkbox" value="1"> Mo</label>
            <label><input type="checkbox" value="2"> Di</label>
            <label><input type="checkbox" value="3"> Mi</label>
            <label><input type="checkbox" value="4"> Do</label>
            <label><input type="checkbox" value="5"> Fr</label>
            <label><input type="checkbox" value="6"> Sa</label>
            <label><input type="checkbox" value="7"> So</label>
          </div>
        </div>
      </details>

      <details class="filtergruppe" data-gruppe="art">
        <summary>Art des Einsatzes</summary>
        <div class="filterfelder">
          <label>Windeneinsatz <select id="f-wi" class="dreiwert"></select></label>
          <label>Cycles von <input type="number" id="f-cv" min="0" max="8" step="1"></label>
          <label>Cycles bis <input type="number" id="f-cb" min="0" max="8" step="1"></label>
          <label>Cycles mit Patient von <input type="number" id="f-pv" min="0" max="8" step="1"></label>
          <label>Cycles mit Patient bis <input type="number" id="f-pb" min="0" max="8" step="1"></label>
          <label>Luftverladung <select id="f-lv" class="dreiwert"></select></label>
          <label>Bergwacht <select id="f-bw" class="dreiwert"></select></label>
          <label>Bereitschaft <select id="f-bu"></select></label>
          <label>Sekundärtransport <select id="f-se" class="dreiwert"></select></label>
          <label>Schockraum <select id="f-sr" class="dreiwert"></select></label>
          <label>Reanimation <select id="f-re" class="dreiwert"></select></label>
          <label>Herkunft <select id="f-hk"></select></label>
          <div class="reatypen" id="f-rt">
            <span class="wtlabel">Reanimations-Ereignis</span>
          </div>
        </div>
      </details>

      <details class="filtergruppe" data-gruppe="wer">
        <summary>Beteiligte und Ziel</summary>
        <div class="filterfelder">
          <label>Standort <select id="f-st"></select></label>
          <label>Maschine <select id="f-ac"></select></label>
          <label>Pilot 1 <select id="f-c1"></select></label>
          <label>Pilot 2 <select id="f-c2"></select></label>
          <label>HEMS-TC <select id="f-c3"></select></label>
          <label>Flugretter <select id="f-c4"></select></label>
          <label>Sonstige <select id="f-c5"></select></label>
          <label>Weiteres Rettungsmittel <select id="f-rm"></select></label>
          <label>Transportziel <select id="f-tz"></select></label>
        </div>
      </details>

      <details class="filtergruppe" data-gruppe="werte">
        <summary>Werte</summary>
        <div class="filterfelder">
          <label id="lab-av">Alter von <input type="number" id="f-av" min="0" max="130" step="1"></label>
          <label id="lab-ab">Alter bis <input type="number" id="f-ab" min="0" max="130" step="1"></label>
          <label>Flugstrecke von (km) <input type="number" id="f-kv" min="0" step="1"></label>
          <label>Flugstrecke bis (km) <input type="number" id="f-kb" min="0" step="1"></label>
          <label>Einsatzdauer von (min) <input type="number" id="f-ev" min="0" step="1"></label>
          <label>Einsatzdauer bis (min) <input type="number" id="f-eb" min="0" step="1"></label>
          <label>Höhe Einsatzort von (m) <input type="number" id="f-hv" step="1"></label>
          <label>Höhe Einsatzort bis (m) <input type="number" id="f-hb" step="1"></label>
          <p class="muted" id="alterlock" hidden>Der Altersfilter braucht die
            entschlüsselten Angaben und ist deshalb gesperrt.</p>
        </div>
      </details>
    </div>

    <div class="filterfuss">
      <button type="button" class="btn-plain" id="reset">Filter zurücksetzen</button>
      <span class="muted" id="filtercount"></span>
    </div>
  </aside>

  <main class="page">
    <h1>Suche</h1>
    <div id="loaderror" class="alert" hidden></div>

    <p id="lockbanner" class="alert alert-info" hidden>
      Geschützte Angaben sind gesperrt — Einsatznummer, Name, Geburtsdatum,
      Alter, Diagnose und Einsatzort werden nicht durchsucht und bleiben in der
      Trefferliste verborgen.
      <button type="button" class="btn-plain unlockbtn" id="unlockbtn">Entsperren</button>
    </p>

    <div class="suchbox">
      <label class="suchfreitext">Suchbegriff
        <input type="search" id="f-q" autocomplete="off" spellcheck="false"
               placeholder="Mehrere Wörter: alle müssen vorkommen">
      </label>
      <p class="muted suchhinweis">Durchsucht Einsatznummer, Name, Geburtsdatum,
        Diagnose, Einsatzort, Transportziel, Beschreibung, Bergwacht-Angaben,
        anderen Notarzt, weitere Rettungsmittel, Besatzung und Notizen.
        Weitere Filter in der Spalte links.</p>
    </div>

    <p class="muted ergebniszeile" id="ergebniszeile">Bestand wird geladen …</p>

    <p id="leer" class="muted" hidden>Keine Treffer.</p>
    <table class="data" id="suchtable" hidden>
      <thead></thead>
      <tbody></tbody>
    </table>

    <?php ui_footer(); ?>
  </main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script>
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
const KDF_SALT = <?= json_encode($kdfSalt) ?>;
const RESUS_LABELS = <?= json_encode(RESUS_LABELS, JSON_UNESCAPED_UNICODE) ?>;

let missions = [];        // gesamter Bestand aus api/suchindex.php
let entsperrt = false;    // geschuetzte Angaben verfuegbar?

const $ = id => document.getElementById(id);

/* ====================================================================
 * Filterkatalog — EINE Liste, aus der sich alles ableitet: Auslesen der
 * Oberflaeche, Schreiben ins URL-Fragment, Wiederherstellen daraus und
 * das Zaehlen aktiver Filter. Ein neuer Filter braucht genau einen
 * Eintrag hier plus sein Feld in der Filterspalte und seine Zeile in trifft().
 * 'gruppe' sagt, in welchem aufklappbaren Block das Feld steht — daraus
 * leitet sich ab, welche Bloecke bei einem geteilten Link aufgehen. Der
 * Freitext steht in der Hauptspalte und hat deshalb keine Gruppe.
 *
 * 'kurz' ist der Parametername im Fragment. Diese Namen sind Teil der
 * geteilten Links und duerfen nicht nachtraeglich umbenannt werden —
 * sonst brechen bereits verschickte Links. Sie sind in Technik.md
 * dokumentiert.
 * ================================================================== */
const FILTER = [
  { kurz: 'q', el: 'f-q', art: 'text', gruppe: null },
  { kurz: 'dv', el: 'f-dv', art: 'text', gruppe: 'zeit' },
  { kurz: 'db', el: 'f-db', art: 'text', gruppe: 'zeit' },
  { kurz: 'zv', el: 'f-zv', art: 'text', gruppe: 'zeit' },
  { kurz: 'zb', el: 'f-zb', art: 'text', gruppe: 'zeit' },
  { kurz: 'wd', el: 'f-wd', art: 'haken', gruppe: 'zeit' },
  { kurz: 'wi', el: 'f-wi', art: 'text', gruppe: 'art' },
  { kurz: 'cv', el: 'f-cv', art: 'text', gruppe: 'art' },
  { kurz: 'cb', el: 'f-cb', art: 'text', gruppe: 'art' },
  { kurz: 'pv', el: 'f-pv', art: 'text', gruppe: 'art' },
  { kurz: 'pb', el: 'f-pb', art: 'text', gruppe: 'art' },
  { kurz: 'lv', el: 'f-lv', art: 'text', gruppe: 'art' },
  { kurz: 'bw', el: 'f-bw', art: 'text', gruppe: 'art' },
  { kurz: 'bu', el: 'f-bu', art: 'text', gruppe: 'art' },
  { kurz: 'se', el: 'f-se', art: 'text', gruppe: 'art' },
  { kurz: 'sr', el: 'f-sr', art: 'text', gruppe: 'art' },
  { kurz: 're', el: 'f-re', art: 'text', gruppe: 'art' },
  { kurz: 'rt', el: 'f-rt', art: 'haken', gruppe: 'art' },
  { kurz: 'hk', el: 'f-hk', art: 'text', gruppe: 'art' },
  { kurz: 'st', el: 'f-st', art: 'text', gruppe: 'wer' },
  { kurz: 'ac', el: 'f-ac', art: 'text', gruppe: 'wer' },
  { kurz: 'c1', el: 'f-c1', art: 'text', gruppe: 'wer' },
  { kurz: 'c2', el: 'f-c2', art: 'text', gruppe: 'wer' },
  { kurz: 'c3', el: 'f-c3', art: 'text', gruppe: 'wer' },
  { kurz: 'c4', el: 'f-c4', art: 'text', gruppe: 'wer' },
  { kurz: 'c5', el: 'f-c5', art: 'text', gruppe: 'wer' },
  { kurz: 'rm', el: 'f-rm', art: 'text', gruppe: 'wer' },
  { kurz: 'tz', el: 'f-tz', art: 'text', gruppe: 'wer' },
  { kurz: 'av', el: 'f-av', art: 'text', gruppe: 'werte' },
  { kurz: 'ab', el: 'f-ab', art: 'text', gruppe: 'werte' },
  { kurz: 'kv', el: 'f-kv', art: 'text', gruppe: 'werte' },
  { kurz: 'kb', el: 'f-kb', art: 'text', gruppe: 'werte' },
  { kurz: 'ev', el: 'f-ev', art: 'text', gruppe: 'werte' },
  { kurz: 'eb', el: 'f-eb', art: 'text', gruppe: 'werte' },
  { kurz: 'hv', el: 'f-hv', art: 'text', gruppe: 'werte' },
  { kurz: 'hb', el: 'f-hb', art: 'text', gruppe: 'werte' }
];

/* ---- Werte lesen und setzen ---------------------------------------- */

function wertLesen(f) {
  if (f.art === 'haken') {
    return [...$(f.el).querySelectorAll('input[type=checkbox]')]
      .filter(c => c.checked).map(c => c.value).join(',');
  }
  return $(f.el).value.trim();
}

function wertSetzen(f, v) {
  if (f.art === 'haken') {
    const gesetzt = new Set((v || '').split(',').filter(Boolean));
    $(f.el).querySelectorAll('input[type=checkbox]')
      .forEach(c => { c.checked = gesetzt.has(c.value); });
    return;
  }
  const el = $(f.el);
  // Ein Wert, den die Auswahlliste (noch) nicht kennt, wird still verworfen —
  // das passiert z. B., wenn ein geteilter Link auf eine Besatzung zeigt, die
  // im Bestand der empfangenden Person nicht vorkommt.
  el.value = v || '';
}

/* ---- URL-Fragment --------------------------------------------------- */

function fragmentSchreiben() {
  const p = new URLSearchParams();
  FILTER.forEach(f => { const v = wertLesen(f); if (v !== '') { p.set(f.kurz, v); } });
  p.set('s', tabelle.sortKey);
  p.set('sd', tabelle.sortAsc ? 'a' : 'd');
  // replaceState statt Zuweisung an location.hash: sonst waechst die
  // Chronik mit jedem Tastendruck im Suchfeld.
  history.replaceState(null, '', location.pathname + '#' + p.toString());
}

function fragmentLesen() {
  const roh = location.hash.replace(/^#/, '');
  if (roh === '') { return false; }
  const p = new URLSearchParams(roh);
  FILTER.forEach(f => { if (p.has(f.kurz)) { wertSetzen(f, p.get(f.kurz)); } });
  if (p.has('s')) { tabelle.setSort(p.get('s'), p.get('sd') !== 'd'); }
  return FILTER.some(f => p.has(f.kurz));
}

function aktiveFilter() {
  // Der Freitext zaehlt nicht mit — er steht sichtbar im eigenen Feld.
  return FILTER.filter(f => f.kurz !== 'q' && wertLesen(f) !== '').length;
}

/* ---- Auswahllisten aus dem Bestand ---------------------------------- */

/* Werte ohne Rücksicht auf Groß-/Kleinschreibung zusammenfassen; angezeigt
 * wird die zuerst gefundene Schreibweise. Sortiert nach deutschen Regeln. */
function optionen(werte) {
  const map = new Map();
  werte.forEach(v => {
    if (v == null) { return; }
    const s = String(v).trim();
    if (s === '') { return; }
    const k = s.toLowerCase();
    if (!map.has(k)) { map.set(k, s); }
  });
  return [...map.values()].sort((a, b) => a.localeCompare(b, 'de'));
}

function fuelleSelect(id, werte, egal) {
  const el = $(id);
  el.innerHTML = '';
  const leer = document.createElement('option');
  leer.value = ''; leer.textContent = egal || '(egal)';
  el.appendChild(leer);
  werte.forEach(w => {
    const o = document.createElement('option');
    o.value = w; o.textContent = w;
    el.appendChild(o);
  });
}

function baueAuswahllisten() {
  document.querySelectorAll('select.dreiwert').forEach(el => {
    el.innerHTML = '<option value="">(egal)</option>' +
                   '<option value="j">ja</option><option value="n">nein</option>';
  });

  fuelleSelect('f-hk', []);
  [['watch', 'von der Uhr'], ['manual', 'von Hand'], ['import', 'importiert']]
    .forEach(([v, t]) => {
      const o = document.createElement('option');
      o.value = v; o.textContent = t;
      $('f-hk').appendChild(o);
    });

  fuelleSelect('f-bu', optionen(missions.map(m => m.bw_unit)));
  fuelleSelect('f-st', optionen(missions.map(m => m.base)));
  fuelleSelect('f-ac', optionen(missions.map(m => m.aircraft)));
  fuelleSelect('f-c1', optionen(missions.map(m => m.crew.p1)));
  fuelleSelect('f-c2', optionen(missions.map(m => m.crew.p2)));
  fuelleSelect('f-c3', optionen(missions.map(m => m.crew.hems)));
  fuelleSelect('f-c4', optionen(missions.map(m => m.crew.fr)));
  fuelleSelect('f-c5', optionen(missions.map(m => m.crew.other)));
  fuelleSelect('f-rm', optionen(missions.flatMap(m => m.resources)));
  fuelleSelect('f-tz', optionen(missions.map(m => m.transport_dest)));

  // Reanimations-Ereignisse: nur Arten, die im Bestand auch vorkommen.
  const vorhanden = new Set(missions.flatMap(m => m.resus_types));
  const box = $('f-rt');
  box.querySelectorAll('label').forEach(l => l.remove());
  Object.keys(RESUS_LABELS).filter(t => vorhanden.has(t)).forEach(t => {
    const lab = document.createElement('label');
    const cb = document.createElement('input');
    cb.type = 'checkbox'; cb.value = t;
    lab.appendChild(cb);
    lab.appendChild(document.createTextNode(' ' + RESUS_LABELS[t]));
    box.appendChild(lab);
  });
  box.hidden = vorhanden.size === 0;
}

/* ---- Filterlogik ---------------------------------------------------- */

function zahl(id) {
  const v = $(id).value.trim();
  if (v === '') { return null; }
  const n = Number(v);
  return isNaN(n) ? null : n;
}

function hakenWerte(id) {
  return [...$(id).querySelectorAll('input[type=checkbox]')]
    .filter(c => c.checked).map(c => c.value);
}

/** ISO-Wochentag 1 (Mo) … 7 (So) — über UTC gerechnet, damit die Zeitzone
 *  des Browsers das Datum nicht um einen Tag verschiebt. */
function wochentag(iso) {
  const [y, m, d] = iso.split('-').map(Number);
  const wd = new Date(Date.UTC(y, m - 1, d)).getUTCDay();
  return wd === 0 ? 7 : wd;
}

/** "hh:mm" -> Minuten seit Mitternacht, oder null. */
function minuten(v) {
  const t = /^(\d{2}):(\d{2})$/.exec(v.trim());
  return t ? Number(t[1]) * 60 + Number(t[2]) : null;
}

/** Dreiwert-Auswahl gegen einen Ja/Nein-Wert prüfen. */
function dreiwert(id, wert) {
  const v = $(id).value;
  if (v === '') { return true; }
  return v === 'j' ? !!wert : !wert;
}

/** Auswahlwert gegen ein Feld prüfen — ohne Rücksicht auf Groß-/Kleinschreibung. */
function gleich(id, wert) {
  const v = $(id).value;
  if (v === '') { return true; }
  return String(wert || '').trim().toLowerCase() === v.toLowerCase();
}

function inBereich(wert, von, bis) {
  if (von != null && (wert == null || wert < von)) { return false; }
  if (bis != null && (wert == null || wert > bis)) { return false; }
  return true;
}

/**
 * Heuhaufen für die Freitextsuche. Wird bei jedem Laden und nach jedem
 * Entsperren neu gebaut, weil dann die geschützten Felder dazukommen.
 * Die Felder sind mit Zeilenumbrüchen getrennt, damit ein Suchwort nicht
 * zufällig über eine Feldgrenze hinweg trifft.
 */
function baueHeuhaufen(m) {
  const teile = [
    m.transport_dest, m.site_desc, m.bw_unit, m.bw_info, m.other_ema, m.notes,
    m.base, m.aircraft,
    m.crew.p1, m.crew.p2, m.crew.hems, m.crew.fr, m.crew.other
  ].concat(m.resources);

  if (m._pat) {
    teile.push(m._pat.mission_no, m._pat.last, m._pat.first, m._pat.dx);
    if (m._pat.loc && m._pat.loc.addr) { teile.push(m._pat.loc.addr); }
    // Geburtsdatum in beiden Schreibweisen, damit sowohl "1985-03-12" als
    // auch "12.03.1985" gefunden wird.
    if (m._pat.dob) { teile.push(m._pat.dob, EdPat.datumDe(m._pat.dob)); }
  }

  m._hay = teile.filter(t => t != null && String(t).trim() !== '')
                .join('\n').toLowerCase();
}

function trifft(m) {
  // Freitext: jedes Wort muss irgendwo vorkommen, nicht zwingend im
  // selben Feld (UND über die Wörter, ODER über die Felder).
  const q = $('f-q').value.trim().toLowerCase();
  if (q !== '') {
    const woerter = q.split(/\s+/).filter(Boolean);
    if (!woerter.every(w => m._hay.includes(w))) { return false; }
  }

  const dv = $('f-dv').value, db = $('f-db').value;
  if (dv !== '' && m.day < dv) { return false; }
  if (db !== '' && m.day > db) { return false; }

  const zv = minuten($('f-zv').value), zb = minuten($('f-zb').value);
  if (zv != null || zb != null) {
    if (m.start_min == null) { return false; }
    if (zv != null && zb != null && zv > zb) {
      // Fenster über Mitternacht, z. B. 22:00–06:00
      if (!(m.start_min >= zv || m.start_min <= zb)) { return false; }
    } else {
      if (zv != null && m.start_min < zv) { return false; }
      if (zb != null && m.start_min > zb) { return false; }
    }
  }

  const wd = hakenWerte('f-wd');
  if (wd.length && !wd.includes(String(wochentag(m.day)))) { return false; }

  if (!dreiwert('f-wi', m.winch)) { return false; }
  if (!inBereich(m.winch_cycles, zahl('f-cv'), zahl('f-cb'))) { return false; }
  if (!inBereich(m.winch_cycles_pat, zahl('f-pv'), zahl('f-pb'))) { return false; }
  if (!dreiwert('f-lv', m.winch_airload)) { return false; }
  if (!dreiwert('f-bw', m.bergwacht)) { return false; }
  if (!gleich('f-bu', m.bw_unit)) { return false; }
  if (!dreiwert('f-se', m.secondary)) { return false; }
  if (!dreiwert('f-sr', m.schockraum)) { return false; }
  if (!dreiwert('f-re', m.resus_count > 0)) { return false; }

  const rt = hakenWerte('f-rt');
  // Mehrfachauswahl: der Einsatz muss ALLE gewählten Ereignisarten enthalten.
  if (rt.length && !rt.every(t => m.resus_types.includes(t))) { return false; }

  if ($('f-hk').value !== '' && m.origin !== $('f-hk').value) { return false; }

  if (!gleich('f-st', m.base)) { return false; }
  if (!gleich('f-ac', m.aircraft)) { return false; }
  if (!gleich('f-c1', m.crew.p1)) { return false; }
  if (!gleich('f-c2', m.crew.p2)) { return false; }
  if (!gleich('f-c3', m.crew.hems)) { return false; }
  if (!gleich('f-c4', m.crew.fr)) { return false; }
  if (!gleich('f-c5', m.crew.other)) { return false; }

  const rm = $('f-rm').value;
  if (rm !== '' && !m.resources.some(r => r.trim().toLowerCase() === rm.toLowerCase())) { return false; }

  if (!gleich('f-tz', m.transport_dest)) { return false; }

  // Alter nur, wenn entsperrt — sonst wäre jeder Einsatz ein Nicht-Treffer.
  if (entsperrt && !inBereich(m._age == null ? null : m._age, zahl('f-av'), zahl('f-ab'))) { return false; }

  const kv = zahl('f-kv'), kb = zahl('f-kb');
  if (!inBereich(m.distance_m, kv == null ? null : kv * 1000, kb == null ? null : kb * 1000)) { return false; }

  const ev = zahl('f-ev'), eb = zahl('f-eb');
  if (!inBereich(m.duration_s, ev == null ? null : ev * 60, eb == null ? null : eb * 60)) { return false; }

  if (!inBereich(m.site_ele_m, zahl('f-hv'), zahl('f-hb'))) { return false; }

  return true;
}

/** Blöcke aufklappen, in denen etwas gesetzt ist. Wird nur beim Start
 *  gerufen — später soll der Zustand der Person erhalten bleiben, auch wenn
 *  sie einen Block mit gesetztem Filter von Hand zuklappt. */
function gruppenOeffnen() {
  document.querySelectorAll('.filtergruppe').forEach(d => {
    d.open = FILTER.some(f => f.gruppe === d.dataset.gruppe && wertLesen(f) !== '');
  });
}

/* ---- Anzeige -------------------------------------------------------- */

const tabelle = EdMissionTable.erzeuge({
  table: $('suchtable'),
  sortKey: 'day', sortAsc: false,   // neueste zuerst
  pfeilInitial: true,
  onSortChange: fragmentSchreiben,
  onAfterDraw: n => {
    $('leer').hidden = n > 0;
    $('suchtable').hidden = n === 0;
  }
});

function anwenden() {
  const treffer = missions.filter(trifft);
  tabelle.setData(treffer);

  const n = aktiveFilter();
  $('filtercount').textContent = n > 0 ? `(${n} aktiv)` : '';
  const teile = [`${treffer.length} von ${missions.length} Einsätzen`];
  if (n > 0 || $('f-q').value.trim() !== '') { teile.push('gefiltert'); }
  $('ergebniszeile').textContent = teile.join(' · ');

  fragmentSchreiben();
}

/* ---- Geschützte Angaben --------------------------------------------- */

async function entschluesselePat() {
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT);
  entsperrt = !!ck;
  $('lockbanner').hidden = entsperrt || !missions.some(m => m.pat_blob);
  $('f-av').disabled = $('f-ab').disabled = !entsperrt;
  $('lab-av').classList.toggle('feld-gesperrt', !entsperrt);
  $('lab-ab').classList.toggle('feld-gesperrt', !entsperrt);
  $('alterlock').hidden = entsperrt;

  if (ck) {
    for (const m of missions) {
      if (!m.pat_blob) { continue; }
      try {
        const o = JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob)) || {};
        m._pat = o;
        m._dx  = o.dx != null ? o.dx : null;
        m._age = EdPat.alterAnzeige(o, m.day);
        if (o.loc && o.loc.addr) { m._ort = EdMissionTable.extractOrt(o.loc.addr); }
      } catch (e) { /* einzelner Datensatz nicht lesbar: Rest trotzdem zeigen */ }
    }
  }
  missions.forEach(baueHeuhaufen);
  anwenden();
}

/* ---- Start ---------------------------------------------------------- */

function verdrahten() {
  // input deckt Tippen, Datums-, Zeit- und Zahlenfelder ab; change ergänzt
  // Auswahllisten und Haken.
  $('f-q').addEventListener('input', anwenden);
  // Freitext steht in der Hauptspalte, alle uebrigen Filter in der linken.
  document.querySelectorAll('#f-q, .filterspalte input, .filterspalte select')
    .forEach(el => { el.addEventListener('change', anwenden); });
  $('reset').addEventListener('click', () => {
    FILTER.forEach(f => wertSetzen(f, ''));
    anwenden();
  });
  $('unlockbtn').addEventListener('click', () => entschluesselePat());
}

(async () => {
  try {
    const r = await fetch('api/suchindex.php');
    const d = await r.json();
    if (d.error) { throw new Error(d.meldung || d.error); }
    missions = d.missions || [];
  } catch (e) {
    $('loaderror').textContent = 'Der Einsatzbestand konnte nicht geladen werden: ' + e.message;
    $('loaderror').hidden = false;
    $('ergebniszeile').textContent = '';
    return;
  }

  baueAuswahllisten();
  verdrahten();
  // Erst die Auswahllisten füllen, dann das Fragment anwenden — sonst hätten
  // die <select> die gespeicherten Werte noch gar nicht zur Auswahl.
  fragmentLesen();
  gruppenOeffnen();   // Blöcke aus einem geteilten Link sichtbar machen
  missions.forEach(baueHeuhaufen);
  anwenden();

  // Auch ohne Wrap aufrufen: dann liefert EdUnlock sofort null, es erscheint
  // kein Dialog, und der Altersfilter wird korrekt als unbenutzbar markiert.
  entschluesselePat();
})();
</script>
</body>
</html>
