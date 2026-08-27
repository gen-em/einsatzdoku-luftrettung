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
/* Spanne des Zeitraums fuer die Unterzeile (E-P3-37). Sie steht hier und
   nicht im Skript: Sie folgt aus Jahr und Monat, nicht aus den Daten — die
   Zahl der Diensttage traegt das Skript nach, sobald api/range.php da ist. */
if ($monat !== '') {
    $letzter = (int)date('t', (int)mktime(0, 0, 0, (int)$monat, 1, (int)$jahr));
    $spanne  = sprintf('01.%s. – %02d.%s.%s', $monat, $letzter, $monat, $jahr);
} else {
    $spanne = '01.01. – 31.12.' . $jahr;
}

ui_seite_start(['titel' => $titel, 'karte' => true]);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage',
                        'zeitraum' => ['jahr' => $jahr, 'monat' => $monat]]); ?>
    <?php
    /* SEGMENTWAHL NACH ART (E-P3-37, vorher eine eigene Tableiste `.arttabs`).
       Sie steht in den Aktionen der Titelzeile — am Desktop rechts neben dem
       Titel, mobil vollbreit darunter. Der Baustein bringt die Bedienung mit:
       Radios in einer Gruppe wandern von sich aus mit den Pfeiltasten, der
       eigene keydown-Handler der alten Tableiste ist damit entfallen. */
    ob_start();
    ui_segment(['name' => 'art', 'id' => 'artwahl', 'wert' => 'mix',
                'label' => 'Ansicht nach Art des Diensttags',
                'klasse' => 'segment-art',
                'optionen' => ['mix' => 'Gemischt', 'air' => 'Luft', 'ground' => 'Boden']]);
    $segment = ob_get_clean();
    ?>
    <?php ui_titelzeile([
        'zurueck' => $monat !== '' ? ['href' => 'zeitraum.php?y=' . $jahr, 'text' => $jahr] : null,
        'titel'   => $titel,
        'unter'   => 'Zeitraum · <span id="untertage">…</span> · ' . ui_e($spanne),
        'aktionen' => $segment,
    ]); ?>

    <div id="loaderror" hidden></div>

    <?php /* Sperrhinweis als Meldungs-Baustein. Er traegt seinen Knopf selbst;
             ohne Inhaltsschluessel bleiben Einsatzort, Alter und Diagnose leer
             — und damit auch die Karte, deren Pins aus dem Ort stammen. */ ?>
    <div id="lockbanner" hidden>
      <?php ui_meldung(
          'Geschützte Angaben sind gesperrt — Einsatzort, Alter und Diagnose bleiben '
        . 'verborgen, bis die Verschlüsselung entsperrt ist.',
          null, 'info', '      ',
          ['knopf' => ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                                'typ' => 'button', 'attr' => ' id="unlockbtn"'])]); ?>
    </div>

    <?php /* Der Hinweis auf neutrale Diensttage (E31). Ohne ihn wäre nicht
             erklärbar, warum die Summe der beiden Artenansichten kleiner ist
             als „Gemischt". */ ?>
    <div id="neutralhinweis" hidden></div>

    <?php /* Die Kacheln entstehen im Browser: Welche es gibt und wie sie
             heissen, hängt von der Ansicht ab (E-P3-37) und bei den
             Windenkacheln zusätzlich vom Bestand (E30, A13d). Unter 720 px
             sind VIER sichtbar, der Rest steht hinter „Weitere Statistik". */ ?>
    <div class="kennzahl-raster" id="kennzahlen" hidden></div>
    <button type="button" class="leiser-link kennzahl-mehr-knopf nur-unter-720"
            id="mehrstatistik" aria-expanded="false" aria-controls="kennzahlen" hidden>
      <?= ui_symbol('winkel') ?><span>Weitere Statistik</span>
    </button>

    <div id="rangemap" class="geo" hidden></div>

    <section class="karte karte-treffer">
      <div class="karte-kopf">
        <h2 class="karte-titel">Einsätze</h2>
        <span class="karte-zahl" id="einsatzzahl"></span>
        <?php /* Sortieren über dasselbe Blatt wie auf der Suchseite (E-P3-32):
                 auf dem Handy der einzige Weg zu einer anderen Ordnung, am
                 Desktop ein Aufklappmenü neben dem Tabellenkopf. */ ?>
        <div class="aktionen sortieren-aktion">
          <button type="button" class="karte-aktion karte-aktion-blau"
                  data-blatt="sortblatt" aria-expanded="false" aria-controls="sortblatt">
            <?= ui_symbol('sortieren') ?><span id="sortlabel">Datum</span>
          </button>
          <div class="blatt" id="sortblatt" hidden>
            <div class="blatt-griff" aria-hidden="true"></div>
            <h2 class="blatt-titel">Sortieren</h2>
            <div class="blatt-liste" id="sortliste"></div>
            <button type="button" class="knopf knopf-leise blatt-abbrechen" data-blatt-zu>
              <span>Abbrechen</span></button>
          </div>
        </div>
      </div>
      <div class="karte-inhalt">
        <p id="leer" class="feld-hinweis" hidden>In diesem Zeitraum sind keine Einsätze erfasst.</p>
        <?php /* Ab 720 px die Tabelle im eigenen Scrollbehälter, darunter die
                 dreizeilige Kachel mit Artzeichen und Datum — beide aus
                 demselben Zeilenbestand (E-P3-32/37, missiontable.js). */ ?>
        <div class="tabelle-scroll nur-ab-720">
          <table class="tabelle" id="rangetable" hidden>
            <thead></thead>
            <tbody id="rangebody"></tbody>
          </table>
        </div>
        <div class="kachelliste nur-unter-720" id="rangekacheln"></div>
      </div>
    </section>
<?php ui_geruest_ende(); ?>
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
<?php /* geo.js liefert das Standort-Haus (E-P3-40) und die Farben aus den
         Token — dieselbe Quelle wie auf Tages- und Einsatzkarte. */ ?>
<script src="<?= asset('assets/geo.js') ?>"></script>
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
let bases    = [];        // Standorte der Diensttage des Zeitraums (E-P3-40)
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

/* FARBEN AUS DEN TOKEN (E-P3-18). Zwei Hexwerte standen hier fest im Skript;
   seit O7 kommen sie aus :root, wie in geo.js. Die Hervorhebung war ROT und
   ist jetzt ORANGE: Rot heißt in dieser Oberfläche „Aufmerksamkeit" (Fehler,
   Löschen), und ein Höchstwert ist kein Fehler (E-P3-37). */
const _wurzel = getComputedStyle(document.documentElement);
const token = n => _wurzel.getPropertyValue(n).trim();
const FARBE_HERVOR = token('--orange');
const FARBE_NORMAL = token('--blau');

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

/* Ein Eintrag je Kachel — seit O7 mit GETRENNTER EINHEIT:
     wert     (k) => Zahl als Text, OHNE Einheit
     einheit  (k) => "km" | "h" | "m" | "" — sie wird kleiner gesetzt
   Vorher lieferte eine einzige Funktion "61,0 km" am Stück; der Baustein
   (ui_kennzahl / .kennzahl-einheit) setzt die Einheit aber kleiner, und das
   geht nur, wenn sie ein eigenes Element ist. */
function wertKm(meter){ return (meter / 1000).toFixed(1).replace('.', ','); }

/* SUMMEN OHNE NACHKOMMA, EINZELWERTE MIT (Mockup 30: „486 km" für die Summe,
   „61,0 km" für die längste Strecke). Eine Summe über Dutzende Einsätze auf
   100 m genau anzugeben behauptet eine Genauigkeit, die die Einzelwerte nicht
   haben — dieselbe Regel wie im Kopf der Trefferliste (O6). Die
   Tausendertrennung kommt von toLocaleString, damit „1.633" nicht als
   Kommazahl gelesen wird. */
function wertKmSumme(meter){ return Math.round(meter / 1000).toLocaleString('de-DE'); }
function wertGanz(n){ return Number(n).toLocaleString('de-DE'); }

/* Die acht Kacheln des bodengebundenen Rettungsdienstes. Höchster Einsatzort
   und Windenzahlen fehlen hier, weil sie sich über beide Arten nicht sinnvoll
   addieren lassen.

   MOBIL SICHTBAR sind die vier mit `mobil: true` (E-P3-37) — und das sind
   NICHT einfach die ersten vier: Für den Boden zählen Einsätze, Diensttage,
   Einsatzkilometer und die längste Einsatzdauer, für die Luft die
   Winden-Cycles statt des Durchschnitts. Die Auswahl steht deshalb an der
   Kachel und nicht als Positionsregel im Erzeuger. */
const KACHELN_BODEN = [
  { id: 'missioncount', label: 'Einsätze',   mobil: true,      wert: k => String(k.n) },
  { id: 'tage',         label: 'Diensttage', mobil: true,      wert: k => String(k.tage) },
  { id: 'avgmissions',  label: 'Ø Einsätze / Diensttag',       wert: k => k.tage > 0 ? fmtDe1(k.n / k.tage) : '–' },
  { id: 'secondary',    label: 'Sekundärtransporte',           wert: k => String(k.sek) },
  /* Die einzige Kachel, die es in der Luftansicht NICHT gibt (E32/A13f),
     obwohl der Haken auch luftgebunden zur Verfügung steht. */
  { id: 'fehl',         label: 'Fehleinsätze',                 wert: k => String(k.fehl) },
  { id: 'totalkm',      label: 'Einsatzkilometer gesamt', mobil: true,
    wert: k => wertKmSumme(k.km), einheit: 'km' },
  { id: 'maxkm',        label: 'Längste Einsatzstrecke',  extrem: 'maxKm',
    wert: k => k.maxKm.wert != null ? wertKm(k.maxKm.wert) : '–',
    einheit: k => k.maxKm.wert != null ? 'km' : '' },
  /* OHNE Einheit: fmtDur() liefert „1h 28min" — die Einheit steckt schon im
     Wert. Das Mockup schreibt „0:58 h"; die Anwendung schreibt Dauern seit
     jeher als „52min" / „1h 28min", und diese Schreibweise gilt (dieselbe
     dokumentierte Abweichung wie in O4 auf der Einsatzansicht). */
  { id: 'maxdauer',     label: 'Längste Einsatzdauer',    extrem: 'maxDauer', mobil: true,
    wert: k => k.maxDauer.wert != null ? fmtDur(k.maxDauer.wert) : '–' }
];

/* GEMISCHT ZEIGT VIER (E-P3-37) — das ist die Funktionsänderung dieses
   Pakets. Bisher teilte „Gemischt" den Bodensatz mit acht; über beide Arten
   hinweg sind Kilometer, Dauern und Fehleinsätze aber Äpfel und Birnen: Eine
   Flugstrecke von 61 km und eine Fahrstrecke von 12 km stehen für ganz
   verschiedene Einsätze, und ihre Summe beantwortet keine Frage, die jemand
   stellt. Was über beide Arten trägt, sind Anzahl, Diensttage, ihr Verhältnis
   und die Sekundärtransporte. Die übrigen Zahlen stehen unverändert in den
   beiden Artenansichten. */
const KACHELN_GEMISCHT = KACHELN_BODEN.slice(0, 4).map(d => ({ ...d, mobil: true }));

/* Die zehn Kacheln der Luftrettung — unverändert in Beschriftung und Umfang
   (A13f). Die beiden Windenkacheln stehen am ENDE, weil sie als einzige
   verschwinden können: eine Lücke mitten im Raster wäre schwerer zu lesen als
   eine kürzere letzte Reihe. */
const KACHELN_LUFT = [
  { id: 'missioncount', label: 'Einsätze',  mobil: true,       wert: k => String(k.n) },
  { id: 'tage',         label: 'Flugtage',  mobil: true,       wert: k => String(k.tage) },
  { id: 'avgmissions',  label: 'Ø Einsätze / Flugtag',         wert: k => k.tage > 0 ? fmtDe1(k.n / k.tage) : '–' },
  { id: 'secondary',    label: 'Sekundärtransporte',           wert: k => String(k.sek) },
  { id: 'totalkm',      label: 'Flugkilometer gesamt', mobil: true,
    wert: k => wertKmSumme(k.km), einheit: 'km' },
  { id: 'maxkm',        label: 'Längste Flugstrecke',   extrem: 'maxKm',
    wert: k => k.maxKm.wert != null ? wertKm(k.maxKm.wert) : '–',
    einheit: k => k.maxKm.wert != null ? 'km' : '' },
  { id: 'maxdauer',     label: 'Längste Einsatzdauer',  extrem: 'maxDauer',
    wert: k => k.maxDauer.wert != null ? fmtDur(k.maxDauer.wert) : '–' },
  { id: 'maxhoehe',     label: 'Höchster Einsatzort',   extrem: 'maxHoehe',
    wert: k => k.maxHoehe.wert != null ? wertGanz(k.maxHoehe.wert) : '–',
    einheit: k => k.maxHoehe.wert != null ? 'm' : '' },
  /* NUR BEI TATSAECHLICHEN WINDENEINSAETZEN (E30, A13d) — nicht schon, wenn
     das Rettungsmittel es könnte. Damit lässt sich „null Windeneinsätze"
     nicht mehr von „Winde nicht eingerichtet" unterscheiden; das ist gewollt,
     weil eine Dauerkachel mit dem Wert null nur Platz kostet. */
  { id: 'winchcycles',  label: 'Winden-Cycles', mobil: true,   wert: k => String(k.winden),
    nurWenn: liste => liste.some(m => m.winch) },
  { id: 'avgwinch',     label: 'Ø Winden-Cycles / Flugtag',
    wert: k => k.tage > 0 ? fmtDe1(k.winden / k.tage) : '–',
    nurWenn: liste => liste.some(m => m.winch) }
];

const KACHELSATZ = { air: KACHELN_LUFT, ground: KACHELN_BODEN, mix: KACHELN_GEMISCHT };

/* Spalten je Satz (E-P3-37: „Spaltenzahl folgt dem Satz"). Vier Kacheln in
   vier Spalten, acht in zwei Reihen zu vier, zehn in zwei Reihen zu fünf —
   so bleibt keine Reihe halb leer. Gilt erst ab 720 px; darunter sind es
   immer zwei. */
const SPALTEN_JE_SATZ = { air: 5, ground: 4, mix: 4 };

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
 * Kacheln es gibt, hängt an der Ansicht und am Bestand — ein fester Satz im
 * HTML müsste dieselbe Entscheidung ein zweites Mal treffen, und die
 * Ereignisse der Extremwert-Kacheln hängen an Elementen, die es je nach
 * Ansicht gar nicht gibt. Die Ereignisse werden deshalb hier vergeben, beim
 * Erzeugen.
 *
 * Das Markup ist dasselbe wie in ui_kennzahl() (ui.php) — hier von Hand
 * nachgebaut, weil die Kacheln erst im Browser entstehen. Wer den Baustein
 * ändert, ändert BEIDE Stellen.
 */
function zeichneStatistik(liste, tage){
  const k    = rechne(liste, tage);
  const grid = document.getElementById('kennzahlen');
  grid.innerHTML = '';
  grid.className = 'kennzahl-raster kennzahl-raster-' + SPALTEN_JE_SATZ[ansicht];

  const satz = KACHELSATZ[ansicht].filter(def => !def.nurWenn || def.nurWenn(liste));

  /* MOBIL VIER (E-P3-37). Welche vier, sagt die Kachel selbst (`mobil`).
     Fällt eine davon am Bestand weg — die Winden-Cycles ohne Windeneinsatz —,
     rückt die nächste des Satzes nach: Vier Kacheln füllen zwei Reihen zu
     zweit, drei ließen eine halbe Reihe leer. Das steht so nicht im Konzept;
     dort ist der Fall „Luftansicht ohne Winde" nicht bedacht. */
  const vorn = satz.filter(d => d.mobil);
  while (vorn.length < 4 && vorn.length < satz.length) {
    const naechste = satz.find(d => !vorn.includes(d));
    if (!naechste) { break; }
    vorn.push(naechste);
  }
  const mobilSatz = vorn.slice(0, 4);

  satz.forEach(def => {
    const tile = document.createElement('div');
    tile.className = 'kennzahl' + (mobilSatz.includes(def) ? '' : ' kennzahl-mehr');
    tile.dataset.kachel = def.id;

    const wert = document.createElement('p');
    wert.className = 'kennzahl-wert';
    wert.textContent = def.wert(k);
    const einheit = typeof def.einheit === 'function' ? def.einheit(k) : (def.einheit || '');
    if (einheit) {
      const e = document.createElement('span');
      e.className = 'kennzahl-einheit';
      e.textContent = einheit;
      wert.appendChild(e);
    }

    const lab = document.createElement('p');
    lab.className = 'kennzahl-label';
    lab.textContent = def.label;

    tile.appendChild(wert);
    tile.appendChild(lab);

    /* Extremwert-Kacheln behalten ihr bisheriges Verhalten: OHNE Kandidat
       bleiben sie stumm statt zu verschwinden (Konzept 4.6) — sie zeigen
       einen Gedankenstrich und sind nicht anklickbar. MIT Kandidat tragen
       sie seit O7 den Punkt oben rechts und den TAG in der Beschriftung
       (E-P3-37): „Längste Flugstrecke · 14.08." beantwortet die Frage
       „welcher Einsatz war das?" schon vor dem Klick. */
    if (def.extrem && k[def.extrem].mid != null) {
      const traeger = missions.find(m => m.id === k[def.extrem].mid);
      if (traeger) {
        const tag = document.createElement('span');
        tag.className = 'kennzahl-tag';
        tag.textContent = ' · ' + fmtTagKurz(traeger.day);
        lab.appendChild(tag);
      }
      tile.dataset.mid = k[def.extrem].mid;
      tile.classList.add('kennzahl-extrem');
      if (k[def.extrem].mid === fixierteMid) { tile.classList.add('aktiv'); }
      verdrahteExtremKachel(tile);
    }
    grid.appendChild(tile);
  });
  grid.hidden = satz.length === 0;

  /* „Weitere Statistik (n)" — nur mobil, und nur wenn es etwas zu zeigen gibt.
     In der gemischten Ansicht sind es vier von vier, der Knopf entfällt. */
  const knopf   = document.getElementById('mehrstatistik');
  const verdeckt = satz.length - mobilSatz.length;
  knopf.hidden = verdeckt <= 0;
  knopf.querySelector('span').textContent = 'Weitere Statistik (' + verdeckt + ')';
  setzeMehrStatistik(mehrOffen);
}

/** Tag eines Extremwerts, kurz: „14.08." — das Jahr steht im Seitentitel. */
function fmtTagKurz(iso){
  const t = String(iso || '');
  return t.length >= 10 ? t.slice(8, 10) + '.' + t.slice(5, 7) + '.' : t;
}

let mehrOffen = false;
function setzeMehrStatistik(offen){
  mehrOffen = offen;
  const grid  = document.getElementById('kennzahlen');
  const knopf = document.getElementById('mehrstatistik');
  grid.classList.toggle('kennzahl-raster-offen', offen);
  knopf.setAttribute('aria-expanded', offen ? 'true' : 'false');
  knopf.classList.toggle('offen', offen);
}
document.getElementById('mehrstatistik')
        .addEventListener('click', () => setzeMehrStatistik(!mehrOffen));

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
    document.querySelectorAll('.kennzahl.aktiv').forEach(t => t.classList.remove('aktiv'));
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
  /* Tabelle UND Kacheln: Unter 720 px steht dieselbe Liste als Kachelsatz,
     und eine Hervorhebung, die nur die Tabelle kennt, ginge dort ins Leere. */
  document.querySelectorAll('.hl-extrem').forEach(el => el.classList.remove('hl-extrem'));
  if (mid != null) {
    document.querySelectorAll(`#rangebody tr[data-mid="${mid}"], #rangekacheln [data-mid="${mid}"]`)
            .forEach(el => el.classList.add('hl-extrem'));
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
  document.querySelectorAll('.kennzahl.aktiv').forEach(t => t.classList.remove('aktiv'));
  wendeHervorhebungAn(null);
}

document.addEventListener('click', ev => {
  if (ev.target.closest('.kennzahl')) { return; }    // Kacheln haben eigene Logik
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
  /* Unter 720 px die Kachel statt der Tabelle — mit Artzeichen und Datum,
     weil die Einsätze aus verschiedenen Tagen kommen (E-P3-32/37). Beide
     Formen entstehen aus demselben Zeilenbestand; welche zu sehen ist, sagt
     das Stylesheet. */
  kacheln: document.getElementById('rangekacheln'),
  kachelOpts: { artDatum: true, knapp: true },
  sortKey: 'day', sortAsc: true,
  onSortChange: () => sortLabel(),
  onAfterDraw: (gesamt, gezeigt, zeilen) => {
    document.getElementById('leer').hidden = gesamt > 0;
    document.getElementById('rangetable').hidden = gesamt === 0;

    /* Kopf der Einsatzkarte: Zahl und km-Summe (Mockup 29/31). Anders als auf
       der Suchseite steht hier NIE „n von m": Der Zeitraum ist der Rahmen,
       nicht ein Filter über einem größeren Bestand — die Artenwahl daneben
       sagt schon, welcher Ausschnitt gemeint ist. */
    const km = zeilen.reduce((sum, m) => sum + (m.distance_m || 0), 0);
    const teile = [String(gesamt)];
    /* Ganze Kilometer, wie in der Suche: Die Summe über Dutzende Einsätze auf
       100 m genau anzugeben behauptet eine Genauigkeit, die keine Aussage
       trägt. */
    if (km > 0) { teile.push(Math.round(km / 1000).toLocaleString('de-DE') + ' km'); }
    document.getElementById('einsatzzahl').textContent = teile.join(' · ');

    wendeHervorhebungAn(fixierteMid);
  }
});

/* Sortierknopf und -blatt — dasselbe Markup und dieselbe Bedienung wie auf
   der Suchseite (E-P3-32). Unter 720 px gibt es keinen Tabellenkopf, über den
   sich sortieren ließe; das Blatt ist dort der einzige Weg. */
function sortLabel(){
  const sp = tabelle.spalten().find(x => x.key === tabelle.sortKey);
  const richtung = tabelle.sortKey === 'day'
    ? (tabelle.sortAsc ? 'älteste zuerst' : 'neueste zuerst')
    : (tabelle.sortAsc ? 'aufsteigend' : 'absteigend');
  document.getElementById('sortlabel').innerHTML = sp
    ? esc(sp.label) + '<span class="nur-ab-720">, ' + esc(richtung) + '</span>'
    : esc(richtung);
  const liste = document.getElementById('sortliste');
  liste.innerHTML = '';
  tabelle.spalten().forEach(sp2 => {
    const b = document.createElement('button');
    b.type = 'button';
    const aktiv = sp2.key === tabelle.sortKey;
    b.className = 'blatt-zeile' + (aktiv ? ' aktiv' : '');
    b.innerHTML = '<span>' + esc(sp2.label) + '</span>'
      + (aktiv ? edSymbol('pfeil-hoch', tabelle.sortAsc ? '' : 'symbol-oben', richtung) : '');
    b.addEventListener('click', () => {
      tabelle.setSort(sp2.key, aktiv ? !tabelle.sortAsc : true);
      sortLabel();
      if (window.edBlatt) { edBlatt.zu(); }
    });
    liste.appendChild(b);
  });
}

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
  const box   = document.getElementById('neutralhinweis');
  const dabei = !tabsAn || ansicht === 'mix';
  if (!dabei || tageArt.neutral === 0) { box.hidden = true; box.innerHTML = ''; return; }
  const n = tageArt.neutral;
  const text = (n === 1
      ? 'Ein Diensttag dieses Zeitraums ist mitgezählt, aber noch keiner Art zugeordnet'
      : `${n} Diensttage dieses Zeitraums sind mitgezählt, aber noch keiner Art zugeordnet`)
    + ' — ihnen fehlt Standort oder Rettungsmittel.';
  /* Derselbe Meldungs-Baustein wie überall (ui_meldung_markup), hier im
     Browser gebaut: Ob der Hinweis nötig ist, weiß erst die Antwort. */
  box.innerHTML = '<div class="meldung meldung-warn" role="status">'
    + edSymbol('warnung', 'symbol-gross')
    + '<p>' + esc(text) + '</p>'
    + '<div class="meldung-aktion">'
    + '<a class="knopf knopf-neutral" href="nachbearbeitung.php">'
    + '<span>Zuordnung nachtragen</span></a></div></div>';
  box.hidden = false;
}

/** Segmentwahl anzeigen und den aktiven Punkt setzen. */
function zeichneSegment(){
  const wahl = document.getElementById('artwahl');
  /* Die Wahl erscheint NUR, wenn im Zeitraum beide Arten vorliegen (E28) —
     ein einzelnes Segment wäre eine Wahl ohne Alternative. */
  wahl.hidden = !tabsAn;
  if (!tabsAn) { return; }
  wahl.querySelectorAll('input[type="radio"]').forEach(r => {
    r.checked = r.value === ansicht;
  });
}

/** Alles neu zeichnen, was am Tab haengt: Kacheln, Tabelle, Karte, Hinweis. */
function zeichne(){
  const liste = gefiltert();
  zeichneSegment();
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

  /* DAS STANDORT-HAUS (E-P3-40). Es steht auf jeder Karte, sobald Koordinaten
     vorliegen — und anders als die Einsatzorte braucht es dafür KEINEN
     Inhaltsschlüssel: Der Standort des Diensttags ist Klartext (api/range.php).
     Die Karte kann damit auch im gesperrten Zustand etwas zeigen; nur die
     Einsatzorte bleiben dann aus.

     In den beiden Artenansichten stehen nur die Standorte dieser Art: Eine
     Wache, an der in diesem Monat kein Hubschrauber stand, gehört nicht auf
     die Luftkarte. Standorte OHNE Art (neutrale Diensttage) bleiben immer
     stehen — sie könnten zu beidem gehören. */
  bases.forEach(b => {
    if (tabsAn && ansicht !== 'mix' && b.kind !== null && b.kind !== ansicht) { return; }
    EdGeo.markerStandort([b.lat, b.lon], b.name).addTo(pinLayer);
    bounds.push([b.lat, b.lon]);
  });

  liste.forEach(m => {
    if (m._lat == null) { return; }
    /* Der Einsatzort bleibt ein Kreis und wird kein Pin-Symbol: Auf einer
       Jahreskarte liegen Dutzende beieinander, und der Kreis lässt sich für
       die Hervorhebung aus einer Extremwert-Kachel einfärben und vergrößern —
       ein divIcon müsste dafür neu gebaut werden. */
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
    /* PADDING NACH DER KARTENGROESSE, nicht als feste 30 px (Muster aus O3,
       index.php). Das Standort-Schild ist breiter als sein Anker: Liegt der
       Standort am Rand des Ausschnitts, ragte das Schild bei 390 px aus der
       Karte und legte sich über die Bedienknöpfe. Ein Achtel der Kantenlänge
       je Seite hält es drin — und bleibt bei jeder Breite verhältnismäßig.
       Leaflet erwartet L.point(x, y); ein vertauschtes Paar fraß in O3 fast
       die ganze Kartenhöhe (F-P3-Z). */
    const px = map.getSize();
    map.fitBounds(L.latLngBounds(bounds),
      { padding: L.point(px.x * 0.125, px.y * 0.125), maxZoom: 15 });
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

/* Die Segmentwahl ist eine Radiogruppe: Der Wechsel mit den Pfeiltasten
   kommt vom Browser, der eigene keydown-Handler der alten Tableiste ist
   damit entfallen. Gehorcht wird `change`, nicht `click` — sonst löste die
   Tastaturbedienung nichts aus. */
document.getElementById('artwahl').addEventListener('change', ev => {
  if (ev.target.name === 'art') { setzeAnsicht(ev.target.value); }
});

function zeigeFehler(msg){
  const box = document.getElementById('loaderror');
  box.innerHTML = '<div class="meldung meldung-fehler" role="alert">'
    + edSymbol('warnung', 'symbol-gross')
    + '<p><strong>Nicht geladen.</strong> ' + esc(msg) + '</p></div>';
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
  bases      = d.bases || [];
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

  /* Die Unterzeile trägt die Zahl der Diensttage nach — sie steht erst mit
     der Antwort fest (PHP kennt nur die Spanne). Die Zahl gilt für den
     ganzen Zeitraum und wechselt NICHT mit der Ansicht: Sie beschreibt den
     Zeitraum, nicht den Ausschnitt; wie viele Tage auf eine Art entfallen,
     sagt die Kachel „Diensttage". */
  document.getElementById('untertage').textContent =
    tageGesamt === 1 ? '1 Diensttag' : tageGesamt + ' Diensttage';

  zeichne();
  sortLabel();
  // Die Ansicht von Anfang an ins Fragment schreiben, nicht erst beim ersten
  // Wechsel: Sonst zeigte ein sofort kopierter Link auf keine bestimmte.

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
