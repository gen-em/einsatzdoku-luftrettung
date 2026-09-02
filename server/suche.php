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
ui_seite_start(['titel' => 'Suche']);
?>
<?php /* Statt der Diensttage-Leiste: die Filter. Auf der Suchseite waeren
         einzelne Tage sinnlos, hier geht es gerade um den Gesamtbestand.

         SEIT P3/O2 IST ES DIESELBE LEISTE wie ueberall — nicht mehr eine
         eigene `.filterspalte`. Genau davor warnt die Vormerkliste aus
         Konzept P0 (10.5): Haengt der Schubladenmechanismus an der Funktion
         statt an der Klasse, bleibt die Suchseite als einzige ohne mobiles
         Menue. `ui_geruest_start()` mit leiste => 'filter' oeffnet die Leiste
         und ueberlaesst der Seite den Inhalt; ui_leiste_ende() schliesst sie
         und oeffnet den Inhalt. */ ?>
<?php ui_geruest_start(['aktiv' => 'suche', 'leiste' => 'filter', 'titel' => 'Filter']); ?>

    <?php
      /* ---- Bausteine dieser Seite -------------------------------------
       *
       * Zwei kleine Erzeuger, damit die fünf Filtergruppen unten nicht
       * fünfmal dasselbe Markup ausschreiben. Sie stehen HIER und nicht in
       * ui.php: Der Dreiwert („egal / ja / nein") ist eine Eigenart der
       * Suche, und die Filtergruppe ist das Akkordeon aus O2 mit einer
       * Zahl im Kopf. Was beide benutzen — Akkordeon, Segmentwahl —, kommt
       * aus dem gemeinsamen Vorrat. */
      $gruppe_auf = function (string $name, string $titel, bool $offen = false): void { ?>
        <details class="akkordeon filtergruppe" data-gruppe="<?= e($name) ?>"<?= $offen ? ' open' : '' ?>>
          <summary class="akkordeon-zeile">
            <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
            <span class="akkordeon-text"><?= e($titel) ?></span>
            <?php /* Zahl gesetzter Filter dieser Gruppe (E-P3-36) — blau, weil
                     sie einen Zustand nennt und keine Warnung ist. Gefüllt
                     wird sie im Skript, aus demselben Filterkatalog, aus dem
                     auch die Plaketten entstehen. */ ?>
            <span class="filterzahl plakette plakette-blau" data-zahl="<?= e($name) ?>" hidden></span>
          </summary>
          <div class="filterfelder">
      <?php };
      $gruppe_zu = function (): void { ?>
          </div>
        </details>
      <?php };
      /* Dreiwert als Segmentwahl (E-P3-36, Mockup 27). Die gespeicherten
       * Werte bleiben '' / 'j' / 'n' — sie stehen in verschickten Links. */
      $dreiwert = function (string $id, string $titel): void { ?>
        <div class="feldblock" id="lab-<?= e(substr($id, 2)) ?>">
          <span class="feld-label"><?= e($titel) ?></span>
          <?php ui_segment(['id' => $id, 'name' => 'sg-' . $id, 'label' => $titel,
                            'wert' => '', 'klasse' => 'segment-filter',
                            'optionen' => ['' => 'egal', 'j' => 'ja', 'n' => 'nein']]); ?>
        </div>
      <?php };
    ?>

    <div class="leiste-liste filtergruppen">
      <?php /* ---- FILTERGRUPPEN (Schnitt aus Web 7.0.0, Gestalt aus P3/O6) ---
               Die Spalte hatte sechs Blöcke, und drei davon liessen sich nicht
               erklären: „Zeit" enthielt Datum und Uhrzeit, „Werte" Alter,
               Strecke und Dauer, „Einsatz" einen einzigen Haken. Wer nach
               Einsätzen über 50 km suchte, musste raten, ob das eine Zeit-, eine
               Wert- oder eine Einsatzfrage ist.

               Jetzt schneidet die Gliederung nach dem, WORÜBER gefiltert wird:
               der Einsatz selbst (wann, wie weit, wie lange, überhaupt einer),
               die Patientin, der Transport, die Beteiligten, die Bergrettung.

               DIE KURZNAMEN IM FRAGMENT BLEIBEN, WAS SIE SIND (kv, ab, lv …):
               Sie stehen in verschickten Links, und ein umbenannter Parameter
               bricht sie stillschweigend. */ ?>
      <?php $gruppe_auf('einsatz', 'Einsatz', true); ?>
          <?php /* Von/bis nebeneinander (E-P3-36): Sie gehören zusammen und
                   kosten untereinander die doppelte Höhe der Spalte.

                   DER NAME STEHT ÜBER DEM PAAR, NICHT IM LINKEN FELD
                   (F-N1-J). Vorher hieß die linke Beschriftung „Strecke von
                   (km)" und die rechte „bis" — in einer 240 px breiten Leiste
                   brach die linke auf zwei Zeilen um, die rechte nicht, und
                   die beiden Eingabefelder standen auf verschiedener Höhe.
                   Jetzt trägt ein `.feld-label` den Namen und die Felder
                   heißen „von" und „bis": gleich kurz, gleich hoch. Was die
                   Bildschirmleserin hört, bleibt vollständig — das steht im
                   `aria-label` des Feldes. */ ?>
          <div class="feldblock">
            <span class="feld-label">Datum</span>
            <div class="fld-reihe">
              <label for="f-dv">von <input type="date" id="f-dv"
                     aria-label="Datum von"></label>
              <label for="f-db">bis <input type="date" id="f-db"
                     aria-label="Datum bis"></label>
            </div>
          </div>
          <div class="feldblock">
            <span class="feld-label">Wochentage</span>
            <?php /* Mehrfachwahl in der Gestalt der Segmentwahl: dieselben
                     Tasten, aber Kästchen statt Radioknöpfen — mehrere Tage
                     gleichzeitig sind der Regelfall. */ ?>
            <div class="segment segment-mehrfach wochentage" id="f-wd" role="group"
                 aria-label="Wochentage">
              <?php foreach ([1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do',
                              5 => 'Fr', 6 => 'Sa', 7 => 'So'] as $nr => $kurz): ?>
                <input type="checkbox" class="segment-box" id="wt-<?= $nr ?>" value="<?= $nr ?>">
                <label class="segment-taste" for="wt-<?= $nr ?>"><?= $kurz ?></label>
              <?php endforeach; ?>
            </div>
          </div>
          <div class="feldblock">
            <span class="feld-label">Alarmzeit</span>
            <div class="fld-reihe">
              <label for="f-zv">von <input type="text" class="zeitfeld" id="f-zv"
                     placeholder="hh:mm" aria-label="Alarmzeit von"></label>
              <label for="f-zb">bis <input type="text" class="zeitfeld" id="f-zb"
                     placeholder="hh:mm" aria-label="Alarmzeit bis"></label>
            </div>
          </div>
          <?php /* Strecke und Dauer standen unter „Werte" — beides sind
                   Eigenschaften DIESES Einsatzes und gehören zu ihm.
                   Neutral beschriftet (Abschnitt 3.9): Die Suche führt beide
                   Arten in einer Ansicht, „Flugstrecke" wäre für die Hälfte der
                   Einsätze falsch. */ ?>
          <div class="feldblock">
            <span class="feld-label">Strecke <span class="feld-klein-inline">km</span></span>
            <div class="fld-reihe">
              <label for="f-kv">von <input type="number" id="f-kv" min="0" step="1"
                     aria-label="Strecke von, Kilometer"></label>
              <label for="f-kb">bis <input type="number" id="f-kb" min="0" step="1"
                     aria-label="Strecke bis, Kilometer"></label>
            </div>
          </div>
          <div class="feldblock">
            <span class="feld-label">Dauer <span class="feld-klein-inline">min</span></span>
            <div class="fld-reihe">
              <label for="f-ev">von <input type="number" id="f-ev" min="0" step="1"
                     aria-label="Dauer von, Minuten"></label>
              <label for="f-eb">bis <input type="number" id="f-eb" min="0" step="1"
                     aria-label="Dauer bis, Minuten"></label>
            </div>
          </div>
          <?php /* Der Fehleinsatz ist selten. Er erscheint nur, wenn im Bestand
                   überhaupt einer dokumentiert ist — sonst ergäbe „ja" dauerhaft
                   null Treffer und „nein" den ganzen Bestand — die Sichtbarkeit
                   entsteht seit S3/AP9 aus dem Feldkatalog. */
                $dreiwert('f-fe', 'Fehleinsatz'); ?>
      <?php $gruppe_zu(); ?>

      <?php /* Alles, was die Person betrifft. Derzeit ist das der Altersfilter
               — und er wird es bleiben, solange die übrigen Angaben
               verschlüsselt sind: Nach einem Namen zu filtern hiesse, eine
               Auswahlliste aller Namen aufzubauen, und die wäre selbst ein
               Patientendatum. Gesucht wird nach ihnen über das Freitextfeld,
               das nach dem Entsperren auch die geschützten Felder durchsucht. */ ?>
      <?php $gruppe_auf('patient', 'PatientIn'); ?>
          <div class="feldblock">
            <span class="feld-label">Alter</span>
            <div class="fld-reihe">
              <label id="lab-av" for="f-av">von <input type="number" id="f-av"
                     min="0" max="130" step="1" aria-label="Alter von"></label>
              <label id="lab-ab" for="f-ab">bis <input type="number" id="f-ab"
                     min="0" max="130" step="1" aria-label="Alter bis"></label>
            </div>
          </div>
          <p class="feld-hinweis" id="alterlock" hidden>Der Altersfilter braucht die
             entsperrte Verschlüsselung — das Alter liegt geschützt vor.</p>
      <?php $gruppe_zu(); ?>

      <?php $gruppe_auf('transport', 'Transport'); ?>
          <label for="f-ta">Transportart <select id="f-ta"></select></label>
          <?php $dreiwert('f-nb', 'NA-Begleitung'); ?>
          <label for="f-tz">Transportziel <select id="f-tz"></select></label>
          <?php $dreiwert('f-se', 'Sekundärtransport');
                $dreiwert('f-sr', 'Schockraum'); ?>
      <?php $gruppe_zu(); ?>

      <?php $gruppe_auf('wer', 'Beteiligte'); ?>
          <label for="f-st">Standort <select id="f-st"></select></label>
          <label for="f-veh">Rettungsmittel <select id="f-veh"></select></label>
          <label for="f-art">Art <select id="f-art"></select></label>
          <?php /* Ein Auswahlfeld je Besatzungsrolle des Katalogs (E4). Welche
                   Rollen es gibt, sagt CREW_ROLES — nicht diese Seite. */
                foreach (CREW_ROLES as $rc => $rolle): ?>
            <label for="f-crew-<?= e($rc) ?>"><?= e($rolle['label']) ?>
              <select id="f-crew-<?= e($rc) ?>" data-rolle="<?= e($rc) ?>"></select></label>
          <?php endforeach; ?>
          <label for="f-rm">Weiteres Rettungsmittel <select id="f-rm"></select></label>
      <?php $gruppe_zu(); ?>

      <?php $gruppe_auf('bergrettung', 'Bergrettung'); ?>
          <?php $dreiwert('f-bw', 'Bergwacht'); ?>
          <label for="f-bu">Bereitschaft <select id="f-bu"></select></label>
          <?php $dreiwert('f-wi', 'Windeneinsatz'); ?>
          <div class="feldblock">
            <span class="feld-label">Windenzyklen</span>
            <div class="fld-reihe">
              <label for="f-cv">von <input type="number" id="f-cv" min="0" max="8"
                     step="1" aria-label="Windenzyklen von"></label>
              <label for="f-cb">bis <input type="number" id="f-cb" min="0" max="8"
                     step="1" aria-label="Windenzyklen bis"></label>
            </div>
          </div>
          <div class="feldblock">
            <span class="feld-label">Zyklen mit PatientIn</span>
            <div class="fld-reihe">
              <label for="f-pv">von <input type="number" id="f-pv" min="0" max="8"
                     step="1" aria-label="Zyklen mit PatientIn, von"></label>
              <label for="f-pb">bis <input type="number" id="f-pb" min="0" max="8"
                     step="1" aria-label="Zyklen mit PatientIn, bis"></label>
            </div>
          </div>
          <?php $dreiwert('f-lv', 'Luftverladung'); ?>
      <?php $gruppe_zu(); ?>
    </div>

    <?php /* Fuß der Leiste (E-P3-36): „Filter zurücksetzen" immer, „n Treffer
             zeigen" nur in der Schublade — am Desktop steht die Trefferzahl
             ohnehin daneben, und der Knopf schlösse nichts. */ ?>
    <div class="leiste-fuss filterfuss">
      <?= ui_knopf(['text' => 'Filter zurücksetzen', 'art' => 'leise', 'breit' => true,
                    'symbol' => 'schliessen', 'typ' => 'button', 'attr' => ' id="reset"']) ?>
      <?= ui_knopf(['text' => 'Treffer zeigen', 'art' => 'primaer', 'breit' => true,
                    'typ' => 'button', 'klasse' => 'nur-schublade',
                    'attr' => ' id="trefferzeigen" data-schublade="zu"']) ?>
    </div>
<?php ui_leiste_ende(); ?>

    <?php ui_titelzeile(['titel' => 'Suche']); ?>

    <div class="meldung meldung-fehler" id="loaderrorbox" role="alert" hidden>
      <?= ui_symbol('warnung', 'symbol-gross') ?>
      <p id="loaderror"></p>
    </div>

    <div class="meldung meldung-info" id="lockbanner" role="status" hidden>
      <?= ui_symbol('schloss', 'symbol-gross') ?>
      <p>Geschützte Angaben sind gesperrt — Einsatznummer, Name, Geburtsdatum,
         Alter, Diagnose und Einsatzort werden nicht durchsucht und bleiben in
         der Trefferliste verborgen.</p>
      <div class="meldung-aktion">
        <?= ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                      'typ' => 'button', 'attr' => ' id="unlockbtn"']) ?>
      </div>
    </div>

    <?php /* Suchfeld und Filterknopf in EINER Zeile (Mockup 27/28). Das Feld
             ist 48 px hoch — es ist die Haupthandlung dieser Seite; der Knopf
             daneben steht nur, solange die Leiste eine Schublade ist. */ ?>
    <div class="suchzeile">
      <div class="suchfeld">
        <?= ui_symbol('lupe', 'symbol-gross suchfeld-lupe') ?>
        <label class="nur-vorlesen" for="f-q">Suchbegriff</label>
        <input type="search" id="f-q" autocomplete="off" spellcheck="false"
               placeholder="Suchen">
        <button type="button" class="suchfeld-x" id="qleeren" hidden
                title="Suchfeld leeren"><?= ui_symbol('schliessen', 'symbol-gross', 'Suchfeld leeren') ?></button>
      </div>
      <?php /* Der Filterknopf traegt die Zahl gesetzter Filter (E-P3-36) —
               dieselbe, die auch in den Gruppenkoepfen steht. Von Hand
               gebaut, weil ui_knopf() genau einen Text kennt und hier ein
               zweites Element danebensteht. */ ?>
      <button type="button" class="knopf knopf-neutral filterknopf nur-schublade"
              data-schublade="auf" aria-controls="leiste">
        <span>Filter</span>
        <span class="plakette plakette-blau" id="filterknopfzahl" hidden></span>
      </button>
    </div>

    <p class="suchhinweis feld-hinweis">Mehrere Wörter: alle müssen vorkommen ·
      durchsucht Einsatznummer, Name, Ort, Diagnose, Notizen
      <button type="button" class="leiser-link" id="syntaxknopf"
              aria-expanded="false" aria-controls="suchsyntax">Und / Oder / Nicht verknüpfen</button></p>

    <?php /* Die Operatoren stehen aufklappbar da, nicht als Dauertext: Wer
             sie nicht braucht, tippt weiterhin einfach Wörter — genau so
             verhält sich die Suche ohne Operator auch (assets/suchtext.js). */ ?>
    <div class="suchsyntax" id="suchsyntax" hidden>
      <ul>
        <li><code>sturz fraktur</code> — beide Begriffe (Leerzeichen heißt UND)</li>
        <li><code>sturz ODER fraktur</code> — mindestens einer
          (<code>OR</code> und <code>|</code> gehen auch)</li>
        <li><code>bergwacht -winde</code> — der erste ja, der zweite nicht
          (<code>NICHT</code>, <code>NOT</code> und <code>!</code> gehen auch)</li>
        <li><code>"zwei wörter"</code> — genau diese Folge</li>
        <li><code>(sturz ODER fraktur) oberstdorf</code> — Klammern binden
          zusammen; ohne sie bindet UND stärker als ODER</li>
      </ul>
      <p class="feld-hinweis">Groß- und Kleinschreibung spielt nirgends eine
        Rolle. Eine unfertige Eingabe wird nicht bemängelt — sie sucht
        weiter, so gut es geht.</p>
    </div>

    <?php /* Die gesetzten Filter als blaue Plaketten mit ✕ (E-P3-36): Sie
             sagen im Inhalt, was in der Leiste steht — auf dem Handy ist die
             sonst zu. Jede Plakette nimmt ihren Filter zurück. */ ?>
    <div class="zeile-plaketten filterplaketten" id="filterplaketten" hidden></div>

    <section class="karte karte-treffer">
      <div class="karte-kopf">
        <h2 class="karte-titel">Treffer</h2>
        <span class="karte-zahl" id="trefferzahl"></span>
        <?php /* Sortieren über ein Blatt — dieselben Spalten wie der
                 Tabellenkopf, und auf dem Handy der einzige Weg (E-P3-32).
                 Am Desktop erscheint dasselbe Markup als Aufklappmenü. */ ?>
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
        <p id="leer" class="feld-hinweis" hidden>Keine Treffer.</p>
        <?php /* Ab 720 px die Tabelle im eigenen Scrollbehälter, darunter die
                 dreizeilige Kachel mit Artzeichen und Datum — beide aus
                 demselben Zeilenbestand (E-P3-32/36, missiontable.js). */ ?>
        <div class="tabelle-scroll nur-ab-720">
          <table class="tabelle" id="suchtable" hidden>
            <thead></thead>
            <tbody></tbody>
          </table>
        </div>
        <div class="kachelliste nur-unter-720" id="suchkacheln"></div>
      </div>
    </section>

<?php ui_geruest_ende(); ?>
<?php ui_krypto_bootstrap(); ?>
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
<?php /* geo.js NUR wegen der Spurfarben: Sie stehen als Token in :root, und
         EdGeo.spurFarbe() ist die eine Stelle, die sie liest. Leaflet braucht
         diese Seite nicht — die Markerfunktionen bleiben ungenutzt. */ ?>
<script src="<?= asset('assets/geo.js') ?>"></script>
<script>
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
                     'text' => $sym['text']];
    }
    echo json_encode($artOpt, JSON_UNESCAPED_UNICODE);
?>;
/* WELCHE FELDER DER KATALOG KENNT UND WELCHER ART SIE SIND (S3/AP9,
   E-S3-08). Erzeugt aus mission_fields.php, samt Unterfeldern — keine
   zweite Liste, die man beim naechsten Feld nachzupflegen vergisst. Der
   Browser braucht die ART, weil „gefuellt" je nach Art etwas anderes heisst:
   Bei einem Haken zaehlt nur `wahr`, bei einer Auswahl zaehlt auch die
   Null („0 Cycles" ist eine Angabe). */
const KATALOG_ART = <?php
    $FELDER = require __DIR__ . '/mission_fields.php';
    $arten = [];
    $sammle = function (array $felder) use (&$sammle, &$arten): void {
        foreach ($felder as $col => $f) {
            if (mf_ist_spalte($f)) { $arten[$col] = (string)($f['type'] ?? 'text'); }
            if (!empty($f['children'])) { $sammle($f['children']); }
        }
    };
    $sammle($FELDER);
    echo json_encode($arten, JSON_UNESCAPED_UNICODE);
?>;
const TRANSPORT_OPTIONEN = <?php
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
                    'gruppe' => 'wer',
                    /* Anzeigename fuer die Plakettenzeile (E-P3-36) — aus dem
                     * Rollenkatalog, nicht abgeschrieben. */
                    'titel'  => CREW_ROLES[$rc]['label']];
    }
    echo json_encode($liste);
?>;

const $ = id => document.getElementById(id);
/* Maskierung aus dem gemeinsamen Baustein (assets/html.js) — dieselbe
   Fassung wie in den Tabellen und Kacheln. */
const esc = EdHtml.escape;

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
  { kurz: 'dv', el: 'f-dv', art: 'text', gruppe: 'einsatz', titel: 'seit' },
  { kurz: 'db', el: 'f-db', art: 'text', gruppe: 'einsatz', titel: 'bis' },
  { kurz: 'zv', el: 'f-zv', art: 'text', gruppe: 'einsatz', titel: 'Alarm ab' },
  { kurz: 'zb', el: 'f-zb', art: 'text', gruppe: 'einsatz', titel: 'Alarm bis' },
  { kurz: 'wd', el: 'f-wd', art: 'haken', gruppe: 'einsatz', titel: 'Wochentage' },
  /* Winde und Bergwacht liegen seit Web 7.0.0 in EINEM Block „Bergrettung".
     Die Kurznamen bleiben unveraendert — sie stehen in verschickten Links. */
  { kurz: 'wi', el: 'f-wi', art: 'segment', gruppe: 'bergrettung', spalte: 'winch', titel: 'Windeneinsatz' },
  { kurz: 'cv', el: 'f-cv', art: 'text', gruppe: 'bergrettung', spalte: 'winch_cycles', titel: 'Cycles ab' },
  { kurz: 'cb', el: 'f-cb', art: 'text', gruppe: 'bergrettung', spalte: 'winch_cycles', titel: 'Cycles bis' },
  { kurz: 'pv', el: 'f-pv', art: 'text', gruppe: 'bergrettung', spalte: 'winch_cycles_pat', titel: 'Cycles m. Pat. ab' },
  { kurz: 'pb', el: 'f-pb', art: 'text', gruppe: 'bergrettung', spalte: 'winch_cycles_pat', titel: 'Cycles m. Pat. bis' },
  { kurz: 'lv', el: 'f-lv', art: 'segment', gruppe: 'bergrettung', spalte: 'winch_airload', titel: 'Luftverladung' },
  { kurz: 'bw', el: 'f-bw', art: 'segment', gruppe: 'bergrettung', spalte: 'bergwacht', titel: 'Bergwacht' },
  { kurz: 'bu', el: 'f-bu', art: 'text', gruppe: 'bergrettung', spalte: 'bw_unit' },
  { kurz: 'ta', el: 'f-ta', art: 'text', gruppe: 'transport', spalte: 'transport_mode', titel: 'Transportart' },
  { kurz: 'nb', el: 'f-nb', art: 'segment', gruppe: 'transport', spalte: 'na_escort', titel: 'NA-Begleitung' },
  { kurz: 'tz', el: 'f-tz', art: 'text', gruppe: 'transport', spalte: 'transport_dest', titel: 'Ziel' },
  { kurz: 'se', el: 'f-se', art: 'segment', gruppe: 'transport', spalte: 'secondary', titel: 'Sekundärtransport' },
  { kurz: 'sr', el: 'f-sr', art: 'segment', gruppe: 'transport', spalte: 'schockraum', titel: 'Schockraum' },
  { kurz: 'fe', el: 'f-fe', art: 'segment', gruppe: 'einsatz', spalte: 'false_alarm', titel: 'Fehleinsatz' },
  { kurz: 'st', el: 'f-st', art: 'text', gruppe: 'wer', titel: 'Standort' },
  /* 'ac' hiess bis Web 5.10.0 „Maschine" und filterte nach aircraft. Der
     Parametername BLEIBT, obwohl das Feld jetzt Rettungsmittel heisst: Die
     Namen im Fragment sind Teil verschickter Links, und ein umbenannter
     Parameter bricht sie stillschweigend. Was er filtert, ist unveraendert —
     der Name des Rettungsmittels des Diensttags. */
  { kurz: 'ac', el: 'f-veh', art: 'text', gruppe: 'wer', titel: 'Rettungsmittel' },
  { kurz: 'art', el: 'f-art', art: 'text', gruppe: 'wer', titel: 'Art' },
  /* Besatzungsfilter je Rolle. Die Kurznamen c1…c5 der fuenf Flugrollen sind
     ebenfalls in verschickten Links unterwegs und bleiben deshalb, was sie
     sind; die bodengebundenen Rollen bekommen eigene. Die Zuordnung steht
     serverseitig in CREW_KURZ und nicht als zweite Liste hier. */
  ...CREW_FILTER,
  { kurz: 'rm', el: 'f-rm', art: 'text', gruppe: 'wer', titel: 'Weiteres Mittel' },
  /* Die Gruppe „werte" ist entfallen: Alter gehoert zur Patientin, Strecke und
     Dauer zum Einsatz. Die Kurznamen sind dieselben geblieben. */
  { kurz: 'av', el: 'f-av', art: 'text', gruppe: 'patient', titel: 'Alter ab' },
  { kurz: 'ab', el: 'f-ab', art: 'text', gruppe: 'patient', titel: 'Alter bis' },
  { kurz: 'kv', el: 'f-kv', art: 'text', gruppe: 'einsatz', titel: 'ab km' },
  { kurz: 'kb', el: 'f-kb', art: 'text', gruppe: 'einsatz', titel: 'bis km' },
  { kurz: 'ev', el: 'f-ev', art: 'text', gruppe: 'einsatz', titel: 'ab min' },
  { kurz: 'eb', el: 'f-eb', art: 'text', gruppe: 'einsatz', titel: 'bis min' }
];

/* ---- Werte lesen und setzen ---------------------------------------- */

function wertLesen(f) {
  if (f.art === 'haken') {
    return [...$(f.el).querySelectorAll('input[type=checkbox]')]
      .filter(c => c.checked).map(c => c.value).join(',');
  }
  /* Segmentwahl (Dreiwert, P3/O6): Werttraeger ist der gewaehlte Radioknopf.
     Die gespeicherten Werte sind unveraendert '' / 'j' / 'n' — sie stehen in
     verschickten Links. */
  if (f.art === 'segment') {
    const an = $(f.el).querySelector('input:checked');
    return an ? an.value : '';
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
  if (f.art === 'segment') {
    const gruppe = $(f.el);
    const treffer = gruppe.querySelector(`input[value="${v || ''}"]`)
                 || gruppe.querySelector('input[value=""]');
    if (treffer) { treffer.checked = true; }
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
  /* Die Dreiwert-Felder sind seit P3/O6 Segmentwahlen aus dem Markup
     (egal / ja / nein) — hier ist nichts mehr zu füllen. */
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

/** Dreiwert-Segmentwahl gegen einen Ja/Nein-Wert prüfen. */
function dreiwert(id, wert) {
  const an = $(id).querySelector('input:checked');
  const v = an ? an.value : '';
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
  const irgendwas = FILTER.some(f => f.kurz !== 'q' && wertLesen(f) !== '');
  document.querySelectorAll('.filtergruppe').forEach(d => {
    const eigene = FILTER.some(f => f.gruppe === d.dataset.gruppe && wertLesen(f) !== '');
    /* Ohne jeden gesetzten Filter bleibt „Einsatz" offen (Mockup 28): Eine
       Spalte aus fuenf zugeklappten Zeilen sagt nicht, was sie kann. */
    d.open = eigene || (!irgendwas && d.dataset.gruppe === 'einsatz');
  });
}

/* ====================================================================
 * FILTER, DIE ES NUR BEI PASSENDEM BESTAND GIBT (Web 5.10.0, neu gefasst
 * in S3/AP9 nach E-S3-08).
 *
 * Wer nie windet, hatte sechs Winden-Felder in der Spalte stehen — Filter,
 * die garantiert null Treffer ergeben, und zwar dauerhaft. Sie kosteten
 * Platz und Aufmerksamkeit und legten nahe, hier sei etwas einzustellen.
 *
 * BIS S3 STANDEN HIER ZWEI HANDGEPFLEGTE LISTEN: `GRUPPE_NUR_WENN` mit
 * einer Bedingung je Block und `FELD_NUR_WENN` mit einer je Einzelfeld.
 * Genau der Einzelfall-Wildwuchs, den der Feldkatalog abschaffen sollte —
 * jedes neue Feld hätte einen dritten Eintrag gebraucht, und wer ihn
 * vergisst, merkt es nie: Ein dauerhaft leerer Filter sieht aus wie ein
 * Filter.
 *
 * JETZT ENTSTEHT DIE REGEL AUS DEM KATALOG. Jeder Filter, der zu einer
 * Katalogspalte gehört, trägt sie als `spalte`; `KATALOG_ART` sagt, welcher
 * Art sie ist. Sichtbar ist ein Filter, wenn der Bestand zu seiner Spalte
 * etwas führt. Ein Filter OHNE Spalte — Zeitraum, Uhrzeit, Wochentag,
 * Strecke, Dauer, Alter, Standort, Rettungsmittel, Besatzung — ist immer
 * sinnvoll und steht immer da. Ein BLOCK verschwindet, wenn alle seine
 * Filter verschwunden sind; er braucht keine eigene Bedingung mehr.
 *
 * EINE ABFRAGE, KEIN FELD-FUER-FELD-SCAN: Der ganze Bestand liegt seit
 * Web 5.10.0 ohnehin im Browser (api/suchindex.php, einmal je Seitenaufruf,
 * fünf SQL-Abfragen unabhängig von der Zahl der Einsätze). Die Sichtbarkeit
 * entsteht in EINEM Durchgang über dieses Feld — nicht in einem je Filter.
 * Eine zusätzliche Serverabfrage, wie E-S3-08 sie beschreibt, wäre dieselbe
 * Auskunft ein zweites Mal geholt.
 *
 * Geprüft wird der GESAMTE Bestand, nicht die aktuelle Trefferliste — sonst
 * verschwände ein Filter, sobald ein anderer die passenden Einsätze gerade
 * ausschliesst, und die Spalte hüpfte beim Tippen.
 *
 * Ausnahme, die bleiben muss: Ein geteilter Link kann einen Filter setzen,
 * zu dem der eigene Bestand nichts hat. Dann wird er gezeigt — ein
 * gesetzter, aber unsichtbarer Filter, der die Liste leer hält und sich
 * nicht finden lässt, wäre das schlechtere Ergebnis.
 * ================================================================== */

/** Trägt dieser Wert eine Angabe? Die Art entscheidet, was „leer" heisst. */
function wertGefuellt(v, art) {
  if (v === null || v === undefined || v === '') { return false; }
  /* Ein Haken zählt nur gesetzt: `false` und `0` sind bei ihm dasselbe wie
     „nicht erfasst". Bei jeder anderen Art ist die Null eine Angabe —
     „0 Cycles" heisst, dass jemand hingesehen hat. */
  if (art === 'checkbox') { return v !== 0 && v !== false && v !== '0'; }
  return true;
}

/** Spalten, zu denen der Bestand etwas führt — EIN Durchgang über alles. */
function spaltenMitBestand() {
  const raus = new Set();
  const spalten = [...new Set(FILTER.filter(f => f.spalte).map(f => f.spalte))];
  for (const m of missions) {
    for (const c of spalten) {
      if (!raus.has(c) && wertGefuellt(m[c], KATALOG_ART[c])) { raus.add(c); }
    }
    if (raus.size === spalten.length) { break; }   // mehr gibt es nicht zu finden
  }
  return raus;
}

/** Der Kasten, in dem ein Filter samt Beschriftung steht. */
function filterKasten(f) {
  const el = $(f.el);
  if (!el) { return null; }
  /* `.feldblock` zuerst: Ein Zahlenpaar („Cycles von/bis") steht darin als
     zwei Filter, und versteckt gehört das Paar samt Überschrift. Erst wenn
     es keinen gibt, ist das umschliessende <label> der Kasten — sonst
     verschwände bei „von" nur das Wort „von". */
  return el.closest('.feldblock') || el.closest('label') || null;
}

function gruppenSichtbarkeit() {
  const bestand = spaltenMitBestand();
  const sichtbar = {};        // Gruppe -> hat mindestens einen sichtbaren Filter

  FILTER.forEach(f => {
    if (!f.gruppe) { return; }                 // das Freitextfeld
    const zeigen = !f.spalte
                || bestand.has(f.spalte)
                || wertLesen(f) !== '';
    if (zeigen) { sichtbar[f.gruppe] = true; }
    if (!f.spalte) { return; }                 // immer da, nichts zu schalten
    const kasten = filterKasten(f);
    if (kasten) { kasten.hidden = !zeigen; }
  });

  document.querySelectorAll('.filtergruppe').forEach(block => {
    block.hidden = !sichtbar[block.dataset.gruppe];
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

let trefferworte = [];   // die hervorzuhebenden Suchwoerter (E-P3-36)

const tabelle = EdMissionTable.erzeuge({
  table: $('suchtable'),
  /* Unter 720 px die Kachel statt der Tabelle — mit Artzeichen und Datum,
     weil die Treffer aus verschiedenen Tagen kommen, und einzeiliger
     Diagnose (E-P3-32/36). Beide Formen entstehen aus demselben
     Zeilenbestand; welche zu sehen ist, sagt das Stylesheet. */
  kacheln: $('suchkacheln'),
  kachelOpts: { artDatum: true, knapp: true },
  hervor: maskiert => EdSuchtext.hervor(maskiert, trefferworte),
  sortKey: 'day', sortAsc: false,   // neueste zuerst
  seite: ZEILEN_JE_SEITE,
  onSortChange: () => { fragmentSchreiben(); sortLabel(); },
  /* Die Ergebniszeile entsteht HIER und nicht in anwenden(): Auch das
     Nachladen zeichnet neu, ohne dass ein Filter sich geändert hätte. Stünde
     der Text dort, bliebe nach dem ersten Klick auf „Weitere 200 anzeigen"
     die alte Zahl stehen. */
  onAfterDraw: (gesamt, gezeigt, zeilen) => {
    $('leer').hidden = gesamt > 0;
    $('suchtable').hidden = gesamt === 0;

    /* Kopf der Trefferkarte: Zahl und km-Summe (Mockup 27/28). Die
       Bestandszahl steht NUR dabei, wenn gefiltert wird — ohne Filter waere
       „82 von 82" eine Zahl, die sich selbst erklaert. Sie ist die einzige
       bewusste Ergaenzung gegenueber dem Mockup: Ohne sie ginge die Auskunft
       „wie viel vom Ganzen" verloren, die es seit Web 5.10.0 gibt. */
    const n = aktiveFilter();
    const gefiltert = n > 0 || $('f-q').value.trim() !== '';
    const km = zeilen.reduce((s, m) => s + (m.distance_m || 0), 0);
    const teile = [gefiltert ? `${gesamt} von ${missions.length}` : String(gesamt)];
    /* Ganze Kilometer: Die Summe ueber Dutzende Einsaetze auf 100 m genau
       anzugeben behauptet eine Genauigkeit, die keine Aussage traegt. */
    if (km > 0) { teile.push(Math.round(km / 1000).toLocaleString('de-DE') + ' km'); }
    if (gezeigt < gesamt) { teile.push(`${gezeigt} angezeigt`); }
    $('trefferzahl').textContent = teile.join(' · ');

    zeigeFilterzustand(gesamt);
  }
});

/* ---- Filterzustand: Plaketten, Gruppenzahlen, Knopfzahl (E-P3-36) ------
 *
 * Alles aus DEMSELBEN Katalog: Was ein Filter heisst, steht als `titel` an
 * seinem Eintrag, was er gerade sagt, liest wertLesen(). Eine zweite Liste
 * mit Beschriftungen waere die Stelle, an der beide auseinanderlaufen. */
const WOCHENTAGE = { 1: 'Mo', 2: 'Di', 3: 'Mi', 4: 'Do', 5: 'Fr', 6: 'Sa', 7: 'So' };

/** Was auf der Plakette steht: „seit 01.01.2026", „Sa, So", „Art: Luft". */
function plakettenText(f, wert) {
  if (f.art === 'haken') {
    return wert.split(',').map(v => WOCHENTAGE[v] || v).join(', ');
  }
  if (f.art === 'segment') { return `${f.titel}: ${wert === 'j' ? 'ja' : 'nein'}`; }
  if (f.kurz === 'dv' || f.kurz === 'db') {
    const [y, m, d] = wert.split('-');
    return `${f.titel} ${d}.${m}.${y}`;
  }
  /* Auswahlfelder zeigen ihre BESCHRIFTUNG, nicht ihren Wert: 'air' heisst
     „luftgebunden" (siehe ART_OPTIONEN). */
  const el = $(f.el);
  const text = (el.tagName === 'SELECT' && el.selectedOptions.length)
    ? el.selectedOptions[0].textContent : wert;
  return `${f.titel}: ${text}`;
}

function zeigeFilterzustand(treffer) {
  const gesetzt = FILTER.filter(f => f.kurz !== 'q' && wertLesen(f) !== '');

  // Plaketten im Inhalt — jede nimmt ihren Filter zurueck.
  const box = $('filterplaketten');
  box.innerHTML = '';
  gesetzt.forEach(f => {
    const p = document.createElement('span');
    p.className = 'plakette plakette-blau';
    p.innerHTML = esc(plakettenText(f, wertLesen(f)))
      + `<button type="button" class="plakette-weg" aria-label="Filter entfernen">`
      + edSymbol('schliessen') + '</button>';
    p.querySelector('button').addEventListener('click', () => {
      wertSetzen(f, '');
      if (f.art === 'text' && $(f.el).classList.contains('zeitfeld')) { EdZeitfeld.pruefeAlle(); }
      gruppenSichtbarkeit();
      anwenden();
    });
    box.appendChild(p);
  });
  box.hidden = gesetzt.length === 0;

  // Zahl je Gruppe im Akkordeonkopf.
  document.querySelectorAll('[data-zahl]').forEach(el => {
    const n = gesetzt.filter(f => f.gruppe === el.dataset.zahl).length;
    el.textContent = n;
    el.hidden = n === 0;
  });

  // Der Filterknopf neben dem Suchfeld traegt dieselbe Zahl.
  const knopfzahl = $('filterknopfzahl');
  knopfzahl.textContent = gesetzt.length;
  knopfzahl.hidden = gesetzt.length === 0;

  /* „n Treffer zeigen" im Fuss der Schublade — die Zahl lebt, weil die Suche
     schon beim Tippen filtert (E-P3-36). */
  $('trefferzeigen').querySelector('span').textContent =
    treffer === 1 ? '1 Treffer zeigen' : `${treffer} Treffer zeigen`;
}

/** Beschriftung des Sortierknopfs: Spalte und Richtung im Klartext. */
function sortLabel() {
  const sp = tabelle.spalten().find(s => s.key === tabelle.sortKey);
  /* Beim Datum sagt „neueste zuerst" mehr als „absteigend" (Mockup 28) —
     überall sonst ist die Richtung selbst die Auskunft. Mobil bleibt nur der
     Spaltenname stehen, daneben zeigt der Pfeil die Richtung. */
  const richtung = tabelle.sortKey === 'day'
    ? (tabelle.sortAsc ? 'älteste zuerst' : 'neueste zuerst')
    : (tabelle.sortAsc ? 'aufsteigend' : 'absteigend');
  $('sortlabel').innerHTML = sp
    ? esc(sp.label) + '<span class="nur-ab-720">, ' + esc(richtung) + '</span>'
    : esc(richtung);
  // Das Blatt fuehrt dieselben Spalten wie der Kopf — keine zweite Liste.
  const liste = $('sortliste');
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
      fragmentSchreiben();
      sortLabel();
      if (window.edBlatt) { edBlatt.zu(); }
    });
    liste.appendChild(b);
  });
}

function anwenden() {
  const q = $('f-q').value;
  freitext = EdSuchtext.pruefer(q);
  trefferworte = EdSuchtext.woerter(q);
  $('qleeren').hidden = q.trim() === '';
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

/* ---- Farbstreifen der Treffer (E-P3-36, Mockup 27/28) ------------------
 *
 * DIESELBE FARBE WIE AUF DER TAGESKARTE: Ein Einsatz traegt hier die
 * Spurfarbe, die er an SEINEM Diensttag hat — die Position innerhalb des
 * Tages entscheidet, nicht die Position in der Trefferliste. Wer einen
 * Treffer oeffnet und von dort auf den Tag geht, findet dieselbe Farbe
 * wieder; eine Farbe nach Listenposition waere blosse Zierde und wechselte
 * bei jeder Sortierung.
 *
 * Gerechnet wird einmal ueber den Gesamtbestand, nicht je Trefferliste. */
function spurfarben() {
  const jeTag = new Map();
  missions.forEach(m => {
    const k = m.day_id == null ? 'x' : String(m.day_id);
    if (!jeTag.has(k)) { jeTag.set(k, []); }
    jeTag.get(k).push(m);
  });
  jeTag.forEach(liste => {
    liste.sort((a, b) => (a.start_min || 0) - (b.start_min || 0));
    liste.forEach((m, i) => { m._col = EdGeo.spurFarbe(i); });
  });
}

function verdrahten() {
  // input deckt Tippen, Datums-, Zeit- und Zahlenfelder ab; change ergänzt
  // Auswahllisten und Haken.
  $('f-q').addEventListener('input', anwenden);
  /* ALLE FILTER LIEGEN IN DER LEISTE (F-P3-AG).
   *
   * Hier stand `.filterspalte` — die eigene Leiste der Suchseite, die O2
   * ersatzlos gestrichen hat (sie steht auf der Streichliste). Der Selektor
   * traf seit Web 9.1.0 nichts mehr: Kein Datum, keine Auswahl, kein Haken
   * loeste noch eine neue Suche aus; nur das Freitextfeld mit seinem eigenen
   * Zuhoerer wirkte. Gemessen an 82 Einsaetzen: „Datum von 01.12.2026"
   * lieferte unveraendert 82 Treffer.
   *
   * Der Zuhoerer haengt jetzt an der Leiste selbst und ausserdem an `input`,
   * damit Datums-, Zahlen- und Zeitfelder schon beim Tippen filtern — die
   * Schublade nennt ihre Trefferzahl lebend (E-P3-36). */
  const leiste = document.getElementById('leiste');
  ['input', 'change'].forEach(ereignis => {
    leiste.addEventListener(ereignis, ev => {
      if (ev.target.closest('input, select')) { anwenden(); }
    });
  });
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

  // Das ✕ im Suchfeld (E-P3-36) — es erscheint erst mit Inhalt.
  $('qleeren').addEventListener('click', () => {
    $('f-q').value = '';
    anwenden();
    $('f-q').focus();
  });

  /* Der Hinweis auf die Und/Oder/Nicht-Syntax klappt den Kasten auf. Kein
     <details>: Der Auslöser steht MITTEN im Hinweistext, und ein <summary>
     kann dort nicht stehen. */
  $('syntaxknopf').addEventListener('click', () => {
    const kasten = $('suchsyntax');
    kasten.hidden = !kasten.hidden;
    $('syntaxknopf').setAttribute('aria-expanded', kasten.hidden ? 'false' : 'true');
  });
}

(async () => {
  try {
    const r = await fetch('api/suchindex.php');
    const d = await r.json();
    if (d.error) { throw new Error(d.meldung || d.error); }
    missions = d.missions || [];
  } catch (e) {
    $('loaderror').textContent = 'Der Einsatzbestand konnte nicht geladen werden: ' + e.message;
    $('loaderrorbox').hidden = false;
    $('trefferzahl').textContent = '';
    return;
  }

  spurfarben();
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
  sortLabel();

  // Auch ohne Wrap aufrufen: dann liefert EdUnlock sofort null, es erscheint
  // kein Dialog, und der Altersfilter wird korrekt als unbenutzbar markiert.
  entschluesselePat();
})();
</script>
<?php ui_seite_ende(); ?>
