<?php
declare(strict_types=1);
// Noch nicht eingerichtet? -> Installer starten (erledigt sich nach 1x selbst).
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/mission_fields_lib.php';   // Spalten der Tagestabelle

// Spalten der Tagestabelle aus dem Feldkatalog (mission_fields.php, 'day_col').
// Tabellenkopf, Zeilenaufbau und Sortierung unten leiten sich alle hieraus ab.
$TAGESSPALTEN = mf_tagesspalten();

// Gewaehlter Tag: ?day=YYYY-MM-DD, sonst der neueste
// Stammdaten fuer die Flugtag-Dropdowns
$SD_BASES = db()->prepare('SELECT id, name FROM bases WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
$SD_BASES->execute([$userId]); $SD_BASES = $SD_BASES->fetchAll();
$SD_AC = db()->prepare('SELECT id, registration, p1, p2, hems, fr, other FROM aircraft
                        WHERE (user_id = ? OR user_id IS NULL) ORDER BY registration');
$SD_AC->execute([$userId]); $SD_AC = $SD_AC->fetchAll();
$DEF_AC = 0; $DEF_BASE = 0;
$defs = db()->prepare('SELECT kind, item_id FROM user_defaults WHERE user_id = ?');
$defs->execute([$userId]);
foreach ($defs->fetchAll() as $d) {
    if ($d['kind'] === 'base') { $DEF_BASE = (int)$d['item_id']; }
    if ($d['kind'] === 'aircraft') { $DEF_AC = (int)$d['item_id']; }
}
$SD_CREW = db()->prepare('SELECT role, name FROM crew_presets WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
$SD_CREW->execute([$userId]);
$SD_PRESETS = ['p1' => [], 'p2' => [], 'hems' => [], 'fr' => [], 'other' => []];
foreach ($SD_CREW->fetchAll() as $c) { $SD_PRESETS[$c['role']][] = $c['name']; }

$selDay = preg_match('/^\d{4}-\d{2}-\d{2}$/', $_GET['day'] ?? '') ? $_GET['day'] : null;
if ($selDay === null) {
    $q = db()->prepare('SELECT day FROM (
            SELECT day FROM missions WHERE user_id = ? AND deleted_at IS NULL
            UNION SELECT day FROM rest_segments WHERE user_id = ? AND deleted_at IS NULL
            UNION SELECT day FROM days WHERE user_id = ? AND deleted_at IS NULL
        ) t ORDER BY day DESC LIMIT 1');
    $q->execute([$userId, $userId, $userId]);
    $selDay = $q->fetchColumn() ?: null;
}

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
    header('Location: index.php' . ($selDay !== null ? '?day=' . urlencode((string)$selDay) : ''));
    exit;
}
$neueGeraete = geraete_neu(db(), $userId);
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Tagesübersicht — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/vendor/leaflet/leaflet.css') ?>">
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>

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
    <h1 id="daytitle">–</h1>
    <div id="loaderror" class="alert" hidden></div>
    <details class="daymeta" id="daymeta">
      <summary>Flugtag-Daten <span id="metahint" class="muted"></span>
        <span id="metanotes" class="metanotes"></span></summary>
      <form id="dayform" class="meta-form" data-dirty-track data-submit-on-ctrl-enter>
        <label>Maschine
          <select name="aircraft_id" id="acsel">
            <option value="">–</option>
            <?php foreach ($SD_AC as $a): ?>
              <option value="<?= (int)$a['id'] ?>"
                data-roles='<?= json_encode(['p1'=>(int)$a['p1'],'p2'=>(int)$a['p2'],'hems'=>(int)$a['hems'],'fr'=>(int)$a['fr'],'other'=>(int)$a['other']]) ?>'>
                <?= e($a['registration']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <label>Basis / Standort
          <select name="base_id">
            <option value="">–</option>
            <?php foreach ($SD_BASES as $b): ?>
              <option value="<?= (int)$b['id'] ?>"><?= e($b['name']) ?></option>
            <?php endforeach; ?>
          </select>
        </label>
        <div id="crewfields">
          <?php $RL = ['p1'=>'Pilot 1','p2'=>'Pilot 2','hems'=>'HEMS','fr'=>'Flugretter','other'=>'Sonstige'];
          foreach ($RL as $rk => $lbl): ?>
            <label class="crewrole" data-role="<?= $rk ?>" hidden><?= e($lbl) ?>
              <select name="crew_<?= $rk ?>">
                <option value="">–</option>
                <?php foreach ($SD_PRESETS[$rk] as $n): ?>
                  <option value="<?= e($n) ?>"><?= e($n) ?></option>
                <?php endforeach; ?>
              </select>
            </label>
          <?php endforeach; ?>
        </div>
        <p class="muted" id="sd-hint" <?= ($SD_AC || $SD_BASES) ? 'hidden' : '' ?>>
          Noch keine Stammdaten hinterlegt — unter
          <a href="einstellungen.php?t=stammdaten">Einstellungen → Stammdaten</a> anlegen.</p>
        <label>Notizen <textarea name="notes" rows="3" maxlength="2000"></textarea></label>
        <button type="submit" class="btn-primary">Speichern</button>
        <span id="savestate" class="muted"></span>
      </form>
    </details>
    <?php if (($_GET['umdatiert'] ?? '') === '1' && $selDay): ?>
      <p class="alert alert-ok">Der Einsatztag liegt jetzt am
        <?= e(date('d.m.Y', strtotime((string)$selDay))) ?>. Alle Zeitstempel
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
        <th class="sortable c-km"    data-key="km">Flug&nbsp;km</th>
      </tr></thead>
      <tbody></tbody>
    </table>
    <p id="empty" class="muted" hidden>Für diesen Tag sind keine Einsätze dokumentiert.</p>
    <div class="dayactions">
      <a href="einsatz_form.php" id="addmission" class="btn-primary">+ Einsatz nachtragen</a>
      <?php /* Umdatieren (A5.3): steht bewusst neben „Tag löschen" und nicht im
               Flugtag-Formular. Es ist keine Angabe zum Tag, sondern ein
               Eingriff in seine Zuordnung — mit Wirkung auf jeden Zeitstempel
               des Tages. */ ?>
      <a class="btn-plain" id="daydatelink"
         href="flugtag_datum.php?day=<?= e((string)$selDay) ?>"
         <?= $selDay ? '' : 'hidden' ?>>Datum ändern</a>
      <a class="btn-red" id="daydellink"
         href="flugtag_loeschen.php?day=<?= e((string)$selDay) ?>"
         <?= $selDay ? '' : 'hidden' ?>>Tag löschen</a>
    </div>

    <?php ui_footer(); ?>
  </main>
</div>

<script src="<?= asset('assets/crypto.js') ?>"></script>
<script src="<?= asset('assets/keyguard.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script>
const CSRF = '<?= e($_SESSION['csrf']) ?>';
const SEL_DAY = <?= json_encode($selDay) ?>;
const DEF_AC = <?= (int)$DEF_AC ?>;
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
const KDF_SALT = <?= json_encode($kdfSalt) ?>;
/* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
   gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
   bekommt einen anderen Schluessel. */
const KDF_ITER      = <?= json_encode($kdfIter) ?>;
const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;
const DEF_BASE = <?= (int)$DEF_BASE ?>;
/* Spalten der Tagestabelle — dieselbe Liste, aus der oben der Tabellenkopf
   entstanden ist. Der Titel fehlt hier bewusst: Er steht bereits im <thead>,
   und das Skript baut nur noch Zellen. */
const DAY_COLS = <?= json_encode(array_map(
        static fn(array $dc): array => ['col' => $dc['col'], 'art' => $dc['art'],
                                        'klasse' => $dc['klasse']],
        $TAGESSPALTEN), JSON_UNESCAPED_UNICODE) ?>;
const COLORS = ['#FF8F1F','#4280E5','#D63338','#1A2E4D','#0C8599','#9C36B5','#2F9E44','#8A5A00'];
let currentDay = null;

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
      <td${m._ort ? '' : ' class="dash"'}>${m._ort ? esc(m._ort) : '–'}</td>
      <td class="mono c-mid${m._age != null ? '' : ' dash'}">${m._age != null ? m._age : '–'}</td>
      <td${m._dx ? '' : ' class="dash"'}>${m._dx ? esc(m._dx) : '–'}</td>
      ${dcZellen}
      <td class="mono c-km">${fmtKm(m.distance_m)}</td>`;
    tr.addEventListener('click', () => location.href = 'einsatz.php?id=' + m.id);
    tbody.appendChild(tr);
  });
  document.querySelectorAll('#missions th.sortable').forEach(th => {
    th.classList.toggle('sorted', th.dataset.key === sortKey);
    th.querySelector('.arrow')?.remove();
    if (th.dataset.key === sortKey) {
      const a = document.createElement('span');
      a.className = 'arrow';
      a.textContent = sortDir > 0 ? ' ▲' : ' ▼';
      th.appendChild(a);
    }
  });
}

function extractOrt(addr){
  const parts = addr.split(',');
  let last = parts[parts.length - 1].trim();
  last = last.replace(/^\d{4,5}\s+/, '');
  return last;
}

// Maskierung: Baustein B7 (assets/html.js). Hier stand eine eigene Fassung
// ueber ein Hilfselement — sie maskierte drei Zeichen statt fuenf (M6-03).
const esc = EdHtml.escape;

function fmtDur(s){ if(s==null) return 'kein Ende'; const h=Math.floor(s/3600),m=Math.round(s%3600/60);
  // kompakt ohne Leerzeichen vor der Einheit, damit die Spalte einzeilig bleibt
  return h? `${h}h ${String(m).padStart(2,'0')}min` : `${m}min`; }
function fmtKm(m){ return m==null ? '<span class="dash">–</span>' : (m/1000).toFixed(1).replace('.',',')+' km'; }

function showLoadError(msg){
  const box = document.getElementById('loaderror');
  box.textContent = 'Die Tagesdaten konnten nicht geladen werden: ' + msg;
  box.hidden = false;
}

async function loadDay(day){
  let d;
  try {
    const res = await fetch('api/day.php?day='+encodeURIComponent(day));
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
   * Hinweis wären fehlende Flugtagangaben nicht von "noch nichts eingetragen"
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
      hinweis.textContent = 'Dieser Flugtag liegt im Papierkorb. Angaben zu Maschine, '
        + 'Basis und Besatzung werden nicht angezeigt und können nicht gespeichert '
        + 'werden, solange er dort liegt. Wiederherstellen unter '
        + 'Einstellungen → Papierkorb.';
    } else if (hinweis) {
      hinweis.remove();
    }
  }
  currentDay = d.day;
  document.getElementById('daytitle').textContent = 'Flugtag ' + fmtDay(d.day);
  const ddl = document.getElementById('daydellink');
  ddl.href = 'flugtag_loeschen.php?day=' + encodeURIComponent(d.day);
  ddl.hidden = false;
  const dat = document.getElementById('daydatelink');
  dat.href = 'flugtag_datum.php?day=' + encodeURIComponent(d.day);
  dat.hidden = false;

  // Flugtag-Felder befuellen
  const f = document.getElementById('dayform');
  // Vorbelegung: ohne gespeicherten Wert greifen Standard-Maschine/-Standort
  f.elements['aircraft_id'].value = (d.meta && d.meta.aircraft_id)
    ? d.meta.aircraft_id : (DEF_AC || '');
  f.elements['base_id'].value = (d.meta && d.meta.base_id)
    ? d.meta.base_id : (DEF_BASE || '');
  ['p1','p2','hems','fr','other'].forEach(r => {
    f.elements['crew_' + r].value = (d.meta && d.meta['crew_' + r]) ? d.meta['crew_' + r] : '';
  });
  f.elements['notes'].value = (d.meta && d.meta.notes) ? d.meta.notes : '';
  updateCrewFields();
  const parts = [];
  if (d.meta) {
    if (d.meta.aircraft_name) parts.push(d.meta.aircraft_name);
    if (d.meta.base_name) parts.push(d.meta.base_name);
    ['p1','p2','hems','fr','other'].forEach(r => { if (d.meta['crew_' + r]) parts.push(d.meta['crew_' + r]); });
    if (!parts.length && (d.meta.aircraft || d.meta.base || d.meta.crew)) {
      [d.meta.aircraft, d.meta.base, d.meta.crew].filter(Boolean).forEach(x => parts.push(x + ' (alt)'));
    }
  }
  document.getElementById('metahint').textContent = parts.length ? '— ' + parts.join(' · ') : '';
  document.getElementById('metanotes').textContent =
    (d.meta && d.meta.notes) ? d.meta.notes : '';
  document.getElementById('savestate').textContent = '';
  document.getElementById('addmission').href = 'einsatz_form.php?day=' + d.day;

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
  d.missions.forEach(m => {
    if (m.track.length > 1) {
      const line = L.polyline(m.track, { color: m._col, weight: trackWeight(), smoothFactor: 0 });
      layerGroup.addLayer(line);
      trackLines.push(line);
      m.track.forEach(p => bounds.push(p));
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
  for (const m of dayMissions) {
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
  }
  EdPat.zeigeUnlesbar(zahl);
  if (changed) renderMissionTable();
  if (pinBounds.length && !mapHasBounds) { map.fitBounds(pinBounds, { padding: [30, 30], maxZoom: 15 }); }
}

function updateCrewFields(){
  const sel = document.getElementById('acsel');
  const opt = sel.options[sel.selectedIndex];
  const roles = (opt && opt.dataset.roles) ? JSON.parse(opt.dataset.roles) : {};
  document.querySelectorAll('.crewrole').forEach(el => {
    el.hidden = !roles[el.dataset.role];
  });
}

async function init(){
  document.getElementById('acsel').addEventListener('change', updateCrewFields);
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
    if (!currentDay) return;
    const f = ev.target;
    const body = { day: currentDay,
      aircraft_id: f.elements['aircraft_id'].value || null,
      base_id: f.elements['base_id'].value || null,
      notes: f.elements['notes'].value };
    ['p1','p2','hems','fr','other'].forEach(r => body['crew_' + r] = f.elements['crew_' + r].value);
    const state = document.getElementById('savestate');
    state.textContent = 'Speichern…';
    const res = await fetch('api/day.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
      body: JSON.stringify(body)
    });
    if (res.ok) {
      state.textContent = 'Gespeichert.';
      loadDay(currentDay);
    } else {
      /* Den GRUND zeigen, nicht nur "Fehler".
       * Der wichtigste Fall ist ein Flugtag im Papierkorb: Die Angaben
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
  if (SEL_DAY) { loadDay(SEL_DAY); }
  else document.getElementById('daytitle').textContent = 'Noch keine Daten';
}
init();
</script>
</body>
</html>
