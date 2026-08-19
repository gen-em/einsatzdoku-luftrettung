<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
require_once __DIR__ . '/diensttag_lib.php';        // dt_art_symbole()
require_once __DIR__ . '/mission_fields_lib.php';   // mf_optionen()

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
      <?php /* ---- FILTERGRUPPEN (neu geschnitten, Web 7.0.0) ----------------
               Die Spalte hatte sechs Blöcke, und drei davon liessen sich nicht
               erklären: „Zeit" enthielt Datum und Uhrzeit, „Werte" Alter,
               Strecke und Dauer, „Einsatz" einen einzigen Haken. Wer nach
               Einsätzen über 50 km suchte, musste raten, ob das eine Zeit-, eine
               Wert- oder eine Einsatzfrage ist.

               Jetzt schneidet die Gliederung nach dem, WORÜBER gefiltert wird:
               der Einsatz selbst (wann, wie weit, wie lange, überhaupt einer),
               die Patientin, der Transport, die Beteiligten, die Bergrettung.
               „Werte" ist damit ersatzlos entfallen — es war nie ein Gegenstand,
               sondern eine Datenart.

               DIE KURZNAMEN IM FRAGMENT BLEIBEN, WAS SIE SIND (kv, ab, lv …):
               Sie stehen in verschickten Links, und ein umbenannter Parameter
               bricht sie stillschweigend. Nur ihre GRUPPE hat sich geändert,
               und die entscheidet allein, welcher Block bei einem geteilten
               Link aufgeht. */ ?>
      <details class="filtergruppe" data-gruppe="einsatz">
        <summary>Einsatz</summary>
        <div class="filterfelder">
          <label>Datum von <input type="date" id="f-dv"></label>
          <label>Datum bis <input type="date" id="f-db"></label>
          <label>Alarmzeit von <input type="text" class="zeitfeld" id="f-zv"></label>
          <label>Alarmzeit bis <input type="text" class="zeitfeld" id="f-zb"></label>
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
          <?php /* Strecke und Dauer standen unter „Werte" — beides sind
                   Eigenschaften DIESES Einsatzes und gehören zu ihm.
                   Neutral beschriftet (Abschnitt 3.9): Die Suche führt beide
                   Arten in einer Ansicht, „Flugstrecke" wäre für die Hälfte der
                   Einsätze falsch. Die Flugterminologie bleibt allein den
                   Kacheln des Luftrettungs-Tabs vorbehalten (E32). */ ?>
          <label>Strecke von (km) <input type="number" id="f-kv" min="0" step="1"></label>
          <label>Strecke bis (km) <input type="number" id="f-kb" min="0" step="1"></label>
          <label>Einsatzdauer von (min) <input type="number" id="f-ev" min="0" step="1"></label>
          <label>Einsatzdauer bis (min) <input type="number" id="f-eb" min="0" step="1"></label>
          <?php /* Der Fehleinsatz ist selten. Er erscheint nur, wenn im Bestand
                   überhaupt einer dokumentiert ist — sonst ergäbe „ja" dauerhaft
                   null Treffer und „nein" den ganzen Bestand. Bis Web 6.3.0 fiel
                   dafür der ganze Block weg; jetzt trägt der Block auch die
                   Datums- und Zeitfilter und muss bleiben, also entscheidet die
                   Regel über das einzelne FELD (FELD_NUR_WENN). */ ?>
          <label id="lab-fe">Fehleinsatz <select id="f-fe" class="dreiwert"></select></label>
        </div>
      </details>

      <?php /* Alles, was die Person betrifft. Derzeit ist das der Altersfilter
               — und er wird es bleiben, solange die übrigen Angaben
               verschlüsselt sind: Nach einem Namen zu filtern hiesse, eine
               Auswahlliste aller Namen aufzubauen, und die wäre selbst ein
               Patientendatum. Gesucht wird nach ihnen über das Freitextfeld,
               das nach dem Entsperren auch die geschützten Felder durchsucht. */ ?>
      <details class="filtergruppe" data-gruppe="patient">
        <summary>Patient</summary>
        <div class="filterfelder">
          <label id="lab-av">Alter von <input type="number" id="f-av" min="0" max="130" step="1"></label>
          <label id="lab-ab">Alter bis <input type="number" id="f-ab" min="0" max="130" step="1"></label>
          <p class="muted" id="alterlock" hidden>Der Altersfilter braucht die
            entschlüsselten Angaben und ist deshalb gesperrt.</p>
        </div>
      </details>

      <details class="filtergruppe" data-gruppe="transport">
        <summary>Transport</summary>
        <div class="filterfelder">
          <?php /* Die Transportart speichert einen Code ('air'/'ground'/'ambulant')
                   und zeigt eine Beschriftung — die Liste kommt deshalb aus dem
                   Feldkatalog (mf_optionen(), Befund P17) und nicht aus einer
                   zweiten, hier abgeschriebenen Aufzählung. */ ?>
          <label>Transportart <select id="f-ta"></select></label>
          <label>NA-Begleitung <select id="f-nb" class="dreiwert"></select></label>
          <label>Transportziel <select id="f-tz"></select></label>
          <label>Sekundärtransport <select id="f-se" class="dreiwert"></select></label>
          <label>Schockraum <select id="f-sr" class="dreiwert"></select></label>
        </div>
      </details>

      <details class="filtergruppe" data-gruppe="wer">
        <summary>Beteiligte</summary>
        <div class="filterfelder">
          <label>Standort <select id="f-st"></select></label>
          <label>Rettungsmittel <select id="f-veh"></select></label>
          <?php /* Die Art steht beim Rettungsmittel, weil sie von ihm kommt
                   (E3): Sie ist keine eigene Angabe am Einsatz, sondern die
                   Eigenschaft des Rettungsmittels, mit dem der Diensttag
                   gefahren wurde. */ ?>
          <label>Art <select id="f-art"></select></label>
          <?php /* Die Besatzungsfilter entstehen aus dem Rollenkatalog CREW_ROLES
                   (db.php, E4) — nicht als fünf feste Flugrollen. Mit den
                   bodengebundenen Rollen wären es sieben geworden, und jede neue
                   Rolle hätte hier, im Skript unten und in der Filterlogik
                   gleichzeitig nachgezogen werden müssen.

                   Alle Rollen stehen NEBENEINANDER, ohne Trennung nach Art: Die
                   Suche zeigt beide Arten in einer Tabelle (Abschnitt 3.7.3),
                   und wer nach einem Fahrer sucht, will nicht vorher die Art
                   wählen. Ein Filter ohne Werte im Bestand bleibt leer — das ist
                   dieselbe Regel wie bei allen übrigen Auswahlfeldern hier. */
                foreach (CREW_ROLES as $rc => $rr): ?>
            <label><?= e($rr['label']) ?>
              <select id="f-crew-<?= e($rc) ?>" data-rolle="<?= e($rc) ?>"></select></label>
          <?php endforeach; ?>
          <label>Weiteres Rettungsmittel <select id="f-rm"></select></label>
        </div>
      </details>

      <?php /* BERGRETTUNG — Bergwacht und Winde in EINEM Block (Web 7.0.0).
               Sie standen als zwei getrennte Blöcke da und gehören fachlich
               zusammen: Beides ist Bergrettung, beides hängt an einer Fähigkeit
               des Rettungsmittels, und beides betrifft dieselben Standorte. Wer
               keines von beidem dokumentiert, sieht den Block gar nicht
               (GRUPPE_NUR_WENN) — vorher waren es zwei Blöcke, die dauerhaft
               null Treffer versprachen. */ ?>
      <details class="filtergruppe" data-gruppe="bergrettung">
        <summary>Bergrettung</summary>
        <div class="filterfelder">
          <label>Bergwacht <select id="f-bw" class="dreiwert"></select></label>
          <label>Bereitschaft <select id="f-bu"></select></label>
          <label>Windeneinsatz <select id="f-wi" class="dreiwert"></select></label>
          <label>Cycles von <input type="number" id="f-cv" min="0" max="8" step="1"></label>
          <label>Cycles bis <input type="number" id="f-cb" min="0" max="8" step="1"></label>
          <label>Cycles mit Patient von <input type="number" id="f-pv" min="0" max="8" step="1"></label>
          <label>Cycles mit Patient bis <input type="number" id="f-pb" min="0" max="8" step="1"></label>
          <label>Luftverladung <select id="f-lv" class="dreiwert"></select></label>
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
        weiteren Notarzt, weitere Rettungsmittel, Besatzung und Notizen.
        Weitere Filter in der Spalte links.</p>
      <?php /* Die Operatoren stehen aufklappbar da, nicht als Dauertext: Wer
               sie nicht braucht, tippt weiterhin einfach Wörter — genau so
               verhält sich die Suche ohne Operator auch (assets/suchtext.js).
               Ein Hinweis, den man bei jedem Suchvorgang überliest, wäre
               schlechter als einer, den man einmal aufklappt. */ ?>
      <details class="suchsyntax">
        <summary>Und / Oder / Nicht — Suchbegriffe verknüpfen</summary>
        <ul class="muted small">
          <li><code>sturz fraktur</code> — beide Begriffe (Leerzeichen heißt UND)</li>
          <li><code>sturz ODER fraktur</code> — mindestens einer
            (<code>OR</code> und <code>|</code> gehen auch)</li>
          <li><code>bergwacht -winde</code> — der erste ja, der zweite nicht
            (<code>NICHT</code>, <code>NOT</code> und <code>!</code> gehen auch)</li>
          <li><code>"zwei wörter"</code> — genau diese Folge</li>
          <li><code>(sturz ODER fraktur) oberstdorf</code> — Klammern binden
            zusammen; ohne sie bindet UND stärker als ODER</li>
        </ul>
        <p class="muted small">Groß- und Kleinschreibung spielt nirgends eine
          Rolle. Eine unfertige Eingabe wird nicht bemängelt — sie sucht
          weiter, so gut es geht.</p>
      </details>
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
<script src="<?= asset('assets/keyguard.js') ?>"></script>
<script src="<?= asset('assets/unlock.js') ?>"></script>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/patient.js') ?>"></script>
<?php /* Artsymbole für die Spalte „Art" der Einsatztabelle — dieselben wie in
         der Tagesleiste, aus dt_art_symbole() (Befund P9). */ ?>
<script>const ART_SYMBOLE = <?= json_encode(dt_art_symbole(), JSON_UNESCAPED_UNICODE) ?>;</script>
<script src="<?= asset('assets/missiontable.js') ?>"></script>
<script src="<?= asset('assets/zeitfeld.js') ?>"></script>
<?php /* Boolesche Freitextsuche (Baustein B10, Web 7.0.0). Eigene Datei, weil
         sie ohne die Seite prüfbar ist und keine Kenntnis von ihr braucht. */ ?>
<script src="<?= asset('assets/suchtext.js') ?>"></script>
<script>
const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
const KDF_SALT = <?= json_encode($kdfSalt) ?>;
/* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
   gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
   bekommt einen anderen Schluessel. */
const KDF_ITER      = <?= json_encode($kdfIter) ?>;
const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;

let missions = [];        // gesamter Bestand aus api/suchindex.php
let entsperrt = false;    // geschuetzte Angaben verfuegbar?

/* ZWEI AUSWAHLLISTEN MIT FESTEM WERTEVORRAT (Web 6.2.0).
 *
 * Sie unterscheiden sich von allen uebrigen Auswahlfeldern dieser Seite: Die
 * anderen entstehen aus dem BESTAND (jeder vorkommende Standort, jede
 * vorkommende Zielklinik). Art und Transportart haben dagegen einen festen,
 * im Code definierten Vorrat, und ihr gespeicherter Wert ist NICHT ihre
 * Beschriftung — 'air' heisst „luftgebunden" beziehungsweise „Luft". Beide
 * Listen stammen deshalb aus derselben Quelle wie die Anzeige:
 * dt_art_symbole() und der Feldkatalog (Befund P17). Eine hier abgeschriebene
 * Aufzaehlung waere die Stelle, an der die Suche eine Option kennt, die es
 * nicht mehr gibt. */
const ART_OPTIONEN = <?php
    $artOpt = [];
    foreach (dt_art_symbole() as $code => $sym) {
        // Der neutrale Diensttag hat im Katalog den leeren Schluessel — der ist
        // im Auswahlfeld schon fuer „(egal)" vergeben und bekommt hier einen
        // eigenen Wert. Er steht auch im URL-Fragment.
        $artOpt[] = ['wert' => $code === '' ? 'neutral' : $code,
                     'text' => $sym['zeichen'] . ' ' . $sym['text']];
    }
    echo json_encode($artOpt, JSON_UNESCAPED_UNICODE);
?>;
const TRANSPORT_OPTIONEN = <?php
    $FELDER = require __DIR__ . '/mission_fields.php';
    $taOpt = [];
    foreach (mf_optionen($FELDER['transport_mode']['options']) as $wert => $text) {
        $taOpt[] = ['wert' => (string)$wert, 'text' => (string)$text];
    }
    echo json_encode($taOpt, JSON_UNESCAPED_UNICODE);
?>;

/* Rollenkatalog und die Kurznamen ihrer Filter im URL-Fragment. Beides kommt
   aus CREW_ROLES (db.php, E4); die Kurznamen der fuenf Flugrollen sind
   historisch (c1…c5) und bleiben, weil sie in verschickten Links stehen. */
const CREW_ROLLEN = <?= json_encode(array_keys(CREW_ROLES)) ?>;
const CREW_FILTER = <?php
    /* Bis Web 5.10.0 standen die fuenf Filter einzeln im Katalog. Die
     * Zuordnung Rolle -> Kurzname steht jetzt hier, an EINER Stelle: Die
     * historischen Namen muessen erhalten bleiben (verschickte Links), neue
     * Rollen brauchen aber auch einen. 'crew_<rolle>' ist eindeutig und
     * kollidiert mit keinem bestehenden Namen. */
    $CREW_KURZ = ['p1' => 'c1', 'p2' => 'c2', 'hems' => 'c3', 'fr' => 'c4', 'other' => 'c5'];
    $liste = [];
    foreach (array_keys(CREW_ROLES) as $rc) {
        $liste[] = ['kurz'   => $CREW_KURZ[$rc] ?? ('crew_' . $rc),
                    'el'     => 'f-crew-' . $rc,
                    'art'    => 'text',
                    'gruppe' => 'wer'];
    }
    echo json_encode($liste);
?>;

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
  { kurz: 'dv', el: 'f-dv', art: 'text', gruppe: 'einsatz' },
  { kurz: 'db', el: 'f-db', art: 'text', gruppe: 'einsatz' },
  { kurz: 'zv', el: 'f-zv', art: 'text', gruppe: 'einsatz' },
  { kurz: 'zb', el: 'f-zb', art: 'text', gruppe: 'einsatz' },
  { kurz: 'wd', el: 'f-wd', art: 'haken', gruppe: 'einsatz' },
  /* Winde und Bergwacht liegen seit Web 7.0.0 in EINEM Block „Bergrettung".
     Die Kurznamen bleiben unveraendert — sie stehen in verschickten Links. */
  { kurz: 'wi', el: 'f-wi', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'cv', el: 'f-cv', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'cb', el: 'f-cb', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'pv', el: 'f-pv', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'pb', el: 'f-pb', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'lv', el: 'f-lv', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'bw', el: 'f-bw', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'bu', el: 'f-bu', art: 'text', gruppe: 'bergrettung' },
  { kurz: 'ta', el: 'f-ta', art: 'text', gruppe: 'transport' },
  { kurz: 'nb', el: 'f-nb', art: 'text', gruppe: 'transport' },
  { kurz: 'tz', el: 'f-tz', art: 'text', gruppe: 'transport' },
  { kurz: 'se', el: 'f-se', art: 'text', gruppe: 'transport' },
  { kurz: 'sr', el: 'f-sr', art: 'text', gruppe: 'transport' },
  { kurz: 'fe', el: 'f-fe', art: 'text', gruppe: 'einsatz' },
  { kurz: 'st', el: 'f-st', art: 'text', gruppe: 'wer' },
  /* 'ac' hiess bis Web 5.10.0 „Maschine" und filterte nach aircraft. Der
     Parametername BLEIBT, obwohl das Feld jetzt Rettungsmittel heisst: Die
     Namen im Fragment sind Teil verschickter Links, und ein umbenannter
     Parameter bricht sie stillschweigend. Was er filtert, ist unveraendert —
     der Name des Rettungsmittels des Diensttags. */
  { kurz: 'ac', el: 'f-veh', art: 'text', gruppe: 'wer' },
  { kurz: 'art', el: 'f-art', art: 'text', gruppe: 'wer' },
  /* Besatzungsfilter je Rolle. Die Kurznamen c1…c5 der fuenf Flugrollen sind
     ebenfalls in verschickten Links unterwegs und bleiben deshalb, was sie
     sind; die bodengebundenen Rollen bekommen eigene. Die Zuordnung steht
     serverseitig in CREW_KURZ und nicht als zweite Liste hier. */
  ...CREW_FILTER,
  { kurz: 'rm', el: 'f-rm', art: 'text', gruppe: 'wer' },
  /* Die Gruppe „werte" ist entfallen: Alter gehoert zur Patientin, Strecke und
     Dauer zum Einsatz. Die Kurznamen sind dieselben geblieben. */
  { kurz: 'av', el: 'f-av', art: 'text', gruppe: 'patient' },
  { kurz: 'ab', el: 'f-ab', art: 'text', gruppe: 'patient' },
  { kurz: 'kv', el: 'f-kv', art: 'text', gruppe: 'einsatz' },
  { kurz: 'kb', el: 'f-kb', art: 'text', gruppe: 'einsatz' },
  { kurz: 'ev', el: 'f-ev', art: 'text', gruppe: 'einsatz' },
  { kurz: 'eb', el: 'f-eb', art: 'text', gruppe: 'einsatz' }
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

/* Auswahlfeld mit festem Wertevorrat: Wert und Beschriftung sind zwei Dinge
 * (siehe ART_OPTIONEN oben). fuelleSelect() taugt dafuer nicht — es setzt
 * value und Text gleich und liest seine Werte aus dem Bestand. */
function fuelleCodeSelect(id, liste) {
  const el = $(id);
  el.innerHTML = '<option value="">(egal)</option>';
  liste.forEach(o => {
    const opt = document.createElement('option');
    opt.value = o.wert; opt.textContent = o.text;
    el.appendChild(opt);
  });
}

function baueAuswahllisten() {
  document.querySelectorAll('select.dreiwert').forEach(el => {
    el.innerHTML = '<option value="">(egal)</option>' +
                   '<option value="j">ja</option><option value="n">nein</option>';
  });

  fuelleCodeSelect('f-art', ART_OPTIONEN);
  fuelleCodeSelect('f-ta', TRANSPORT_OPTIONEN);
  fuelleSelect('f-bu', optionen(missions.map(m => m.bw_unit)));
  fuelleSelect('f-st', optionen(missions.map(m => m.base)));
  fuelleSelect('f-veh', optionen(missions.map(m => m.vehicle)));
  // Ein Auswahlfeld je Rolle des Katalogs — die Liste steht in CREW_ROLES und
  // wurde beim Aufbau der Seite in die Feldkennungen gegossen (f-crew-<rolle>).
  CREW_ROLLEN.forEach(r => {
    fuelleSelect('f-crew-' + r, optionen(missions.map(m => m.crew[r])));
  });
  fuelleSelect('f-rm', optionen(missions.flatMap(m => m.resources)));
  fuelleSelect('f-tz', optionen(missions.map(m => m.transport_dest)));
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
    m.transport_dest, m.bw_unit, m.bw_info, m.other_ema, m.notes,
    m.base, m.vehicle
  ].concat(CREW_ROLLEN.map(r => m.crew[r])).concat(m.resources);

  if (m._pat) {
    teile.push(m._pat.mission_no, m._pat.last, m._pat.first, m._pat.dx);
    // Beschreibung Einsatzort liegt seit Web 3.3.0 im pat_blob und ist damit
    // erst nach dem Entsperren durchsuchbar — wie Diagnose und Einsatzort.
    if (m._pat.site_desc) { teile.push(m._pat.site_desc); }
    if (m._pat.loc && m._pat.loc.addr) { teile.push(m._pat.loc.addr); }
    // Geburtsdatum in beiden Schreibweisen, damit sowohl "1985-03-12" als
    // auch "12.03.1985" gefunden wird.
    if (m._pat.dob) { teile.push(m._pat.dob, EdPat.datumDe(m._pat.dob)); }
  }

  m._hay = teile.filter(t => t != null && String(t).trim() !== '')
                .join('\n').toLowerCase();
}

/* Der Freitext-Prüfer wird EINMAL JE EINGABE gebaut, nicht je Einsatz: Bei
   1 600 Datensätzen wäre das Zerlegen des Ausdrucks sonst 1 600 Mal dieselbe
   Arbeit. anwenden() setzt ihn, trifft() benutzt ihn nur. */
let freitext = null;

function trifft(m) {
  /* Freitext: der Ausdruck muss auf den Heuhaufen dieses Einsatzes passen
     (assets/suchtext.js). Ohne Operatoren ist das wie bisher „jedes Wort muss
     irgendwo vorkommen" — UND über die Wörter, ODER über die Felder. */
  if (freitext !== null && !freitext(m._hay)) { return false; }

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
  if (!gleich('f-ta', m.transport_mode)) { return false; }
  if (!dreiwert('f-nb', m.na_escort)) { return false; }
  if (!dreiwert('f-fe', m.false_alarm)) { return false; }

  if (!gleich('f-st', m.base)) { return false; }
  if (!gleich('f-veh', m.vehicle)) { return false; }
  /* Die Art kommt vom DIENSTTAG, nicht vom Einsatz. Ein Einsatz an einem noch
     nicht zugeordneten Diensttag hat keine — er ist unter „ohne Zuordnung" zu
     finden und nicht etwa unter beiden Arten. */
  if (!gleich('f-art', m.kind == null ? 'neutral' : m.kind)) { return false; }
  for (const r of CREW_ROLLEN) {
    if (!gleich('f-crew-' + r, m.crew[r])) { return false; }
  }

  const rm = $('f-rm').value;
  if (rm !== '' && !m.resources.some(r => r.trim().toLowerCase() === rm.toLowerCase())) { return false; }

  if (!gleich('f-tz', m.transport_dest)) { return false; }

  // Alter nur, wenn entsperrt — sonst wäre jeder Einsatz ein Nicht-Treffer.
  if (entsperrt && !inBereich(m._age == null ? null : m._age, zahl('f-av'), zahl('f-ab'))) { return false; }

  const kv = zahl('f-kv'), kb = zahl('f-kb');
  if (!inBereich(m.distance_m, kv == null ? null : kv * 1000, kb == null ? null : kb * 1000)) { return false; }

  const ev = zahl('f-ev'), eb = zahl('f-eb');
  if (!inBereich(m.duration_s, ev == null ? null : ev * 60, eb == null ? null : eb * 60)) { return false; }

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

/* ====================================================================
 * Blöcke, die es nur bei passendem Bestand gibt (Web 5.10.0).
 *
 * Winde und Bergwacht sind Sache eines Teils der Standorte. Wer nie windet,
 * hatte trotzdem sechs Winden-Felder in der Spalte stehen — Filter, die
 * garantiert null Treffer ergeben, und zwar dauerhaft. Sie kosteten Platz und
 * Aufmerksamkeit und legten nahe, hier sei etwas einzustellen.
 *
 * Ein Eintrag je Block: die Bedingung, unter der er gebraucht wird. Geprüft
 * wird der GESAMTE Bestand, nicht die aktuelle Trefferliste — sonst
 * verschwände der Block, sobald ein anderer Filter die Winden-Einsätze gerade
 * ausschliesst, und die Spalte hüpfte beim Tippen.
 *
 * Ausnahme, die bleiben muss: Ein geteilter Link kann einen Filter aus einem
 * dieser Blöcke setzen. Dann wird der Block gezeigt, auch wenn der eigene
 * Bestand nichts dazu hat — ein gesetzter, aber unsichtbarer Filter, der die
 * Liste leer hält und sich nicht finden lässt, wäre das schlechtere Ergebnis.
 * ================================================================== */
const GRUPPE_NUR_WENN = {
  /* Ein Block statt zweier (Web 7.0.0): Winde und Bergwacht sind zusammen die
     Bergrettung, und wer keines von beidem dokumentiert, braucht keines von
     beiden Feldern. Die Bedingung ist die ODER-Verknüpfung der beiden
     bisherigen — der Block erscheint also auch dort, wo nur eines vorkommt. */
  bergrettung: m => m.winch || m.winch_airload
                    || m.winch_cycles != null || m.winch_cycles_pat != null
                    || m.bergwacht
                    || (m.bw_unit != null && m.bw_unit !== '')
                    || (m.bw_info != null && m.bw_info !== '')
};

/* ---- EINZELNE FELDER, die es nur bei passendem Bestand gibt --------------
 *
 * Dasselbe Prinzip eine Ebene tiefer. Nötig geworden, weil der Fehleinsatz
 * jetzt in einem Block steht, der bleiben muss: „Einsatz" trägt auch Datum,
 * Uhrzeit, Strecke und Dauer. Der Haken selbst ist aber unverändert selten —
 * wer keinen dokumentiert hat, hat an ihm nichts zu wählen („ja" ergäbe
 * dauerhaft null Treffer, „nein" den ganzen Bestand).
 *
 * `el` ist die Kennung des LABELS, nicht des Feldes: Versteckt gehört die
 * Beschriftung mit, sonst bliebe ein Wort ohne Bedienelement stehen. */
const FELD_NUR_WENN = {
  fe: { el: 'lab-fe', wenn: m => m.false_alarm }
};

function gruppenSichtbarkeit() {
  Object.keys(GRUPPE_NUR_WENN).forEach(name => {
    const block = document.querySelector(`.filtergruppe[data-gruppe="${name}"]`);
    if (!block) { return; }
    const gesetzt   = FILTER.some(f => f.gruppe === name && wertLesen(f) !== '');
    const vorhanden = missions.some(GRUPPE_NUR_WENN[name]);
    block.hidden = !vorhanden && !gesetzt;
  });
  Object.keys(FELD_NUR_WENN).forEach(kurz => {
    const regel = FELD_NUR_WENN[kurz];
    const lab = $(regel.el);
    if (!lab) { return; }
    // Ausnahme wie bei den Blöcken: Ein Filter aus einem geteilten Link bleibt
    // sichtbar, auch wenn der eigene Bestand nichts dazu hat.
    const gesetzt = FILTER.some(f => f.kurz === kurz && wertLesen(f) !== '');
    lab.hidden = !missions.some(regel.wenn) && !gesetzt;
  });
}

/* ---- Anzeige -------------------------------------------------------- */

/* ZEILEN JE SEITE (Web 5.10.0).
 *
 * Vorher gab es keine Grenze: Beim Öffnen der Suche stand der GESAMTE Bestand
 * als Tabelle da, und jeder Tastendruck im Suchfeld baute ihn neu auf. 200
 * Zeilen sind mehr, als man an einem Stück durchsieht, und wenig genug, dass
 * der Aufbau nicht auffällt. Gefunden wird über die Filter — wer scrollen
 * muss, hat noch nicht genug gefiltert.
 *
 * Was die Grenze NICHT antastet: Gefiltert, sortiert und gezählt wird über den
 * vollständigen Bestand. Die Zeile über der Tabelle nennt weiterhin die wahre
 * Trefferzahl, und die Sortierung entscheidet, welche 200 oben stehen. Unter
 * der Tabelle steht das Nachladen — sichtbar nur, wenn wirklich etwas fehlt. */
const ZEILEN_JE_SEITE = 200;

const tabelle = EdMissionTable.erzeuge({
  table: $('suchtable'),
  sortKey: 'day', sortAsc: false,   // neueste zuerst
  seite: ZEILEN_JE_SEITE,
  onSortChange: fragmentSchreiben,
  /* Die Ergebniszeile entsteht HIER und nicht in anwenden(): Auch das
     Nachladen zeichnet neu, ohne dass ein Filter sich geändert hätte. Stünde
     der Text dort, bliebe nach dem ersten Klick auf „Weitere 200 anzeigen"
     die alte Zahl stehen. */
  onAfterDraw: (gesamt, gezeigt) => {
    $('leer').hidden = gesamt > 0;
    $('suchtable').hidden = gesamt === 0;

    const n = aktiveFilter();
    $('filtercount').textContent = n > 0 ? `(${n} aktiv)` : '';
    const teile = [`${gesamt} von ${missions.length} Einsätzen`];
    if (n > 0 || $('f-q').value.trim() !== '') { teile.push('gefiltert'); }
    if (gezeigt < gesamt) { teile.push(`${gezeigt} angezeigt`); }
    $('ergebniszeile').textContent = teile.join(' · ');
  }
});

function anwenden() {
  freitext = EdSuchtext.pruefer($('f-q').value);
  tabelle.setData(missions.filter(trifft));
  fragmentSchreiben();
}

/* ---- Geschützte Angaben --------------------------------------------- */

async function entschluesselePat() {
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  entsperrt = !!ck;
  $('lockbanner').hidden = entsperrt || !missions.some(m => m.pat_blob);
  $('f-av').disabled = $('f-ab').disabled = !entsperrt;
  $('lab-av').classList.toggle('feld-gesperrt', !entsperrt);
  $('lab-ab').classList.toggle('feld-gesperrt', !entsperrt);
  $('alterlock').hidden = entsperrt;

  if (ck) {
    /* Ein unlesbarer Datensatz darf die Liste nicht zerstoeren — er darf aber
     * auch nicht aussehen wie einer ohne Angaben. Beides entscheidet EdPat,
     * nicht diese Seite (M6-06, Baustein B8). _pat setzt die Schleife dort
     * bereits; hier bleibt nur, was die Suche daraus macht. */
    const zahl = await EdPat.entschluessleListe(missions, ck);
    for (const m of missions) {
      if (m._patState !== 'ok') { continue; }
      const o = m._pat;
      m._dx  = o.dx != null ? o.dx : null;
      m._age = EdPat.alterAnzeige(o, m.day);
      if (o.loc && o.loc.addr) { m._ort = EdMissionTable.extractOrt(o.loc.addr); }
    }
    EdPat.zeigeUnlesbar(zahl);
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
    // Zeitfelder bewerten ihren Zustand selbst, aber nur auf Ereignisse hin;
    // wertSetzen() schreibt den Wert direkt. Ohne diese Zeile bliebe die rote
    // Markierung einer vorher ungueltigen Eingabe stehen.
    EdZeitfeld.pruefeAlle();
    // Ein Block, der nur wegen eines Filters aus einem geteilten Link stand,
    // hat mit dem Zuruecksetzen seinen Grund verloren.
    gruppenSichtbarkeit();
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
  /* Welche Spalten die Tabelle zeigt, entscheidet der GESAMTE Bestand und
     nicht die Trefferliste (A13d) — sonst käme und ginge die Windenspalte
     beim Tippen im Suchfeld. Der Aufruf steht deshalb hier, einmal, und nicht
     in anwenden(). */
  tabelle.setSpaltenBestand(missions);
  // Erst die Auswahllisten füllen, dann das Fragment anwenden — sonst hätten
  // die <select> die gespeicherten Werte noch gar nicht zur Auswahl.
  fragmentLesen();
  gruppenSichtbarkeit();   // Blöcke ohne Bezug zum Bestand fallen weg
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
