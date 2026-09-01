<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/spur_lib.php';
require_once __DIR__ . '/gpx_lib.php';

/**
 * Die Spuren eines Diensttages, einzeln abrufbar (S2/AP4, E-S2-09).
 *
 * WOFUER. Der GPX-Abruf soll es je Einsatz UND je Ruhesegment geben. Fuer
 * Einsaetze gibt es dafuer eine Stelle — das Aktionsmenue der Einsatzansicht.
 * Ruhesegmente hatten bis hierher ueberhaupt keine: Sie erscheinen in der
 * Tagesansicht nur als schwarze Linie auf der Karte, ohne Zeile, ohne Popup,
 * und `api/day.php` liefert nicht einmal ihre Kennung. Ein Knopf je
 * Ruhesegment haette also nirgendwo hingekonnt.
 *
 * Diese Seite gibt beiden dieselbe Identitaet: die Karte des Tages, darunter
 * jede Spur als eigene Zeile — nummeriert wie in der Tagesansicht, mit ihrer
 * Stufe, ihrer Punktzahl und ihrem Abruf. Wer auf eine Zeile zeigt, sieht auf
 * der Karte, welche Linie gemeint ist.
 *
 * SERVERSEITIG GERENDERT, und das ist Absicht: Die Liste besteht aus dem
 * vorhandenen Baustein `ui_zeile()`. Sie im Browser aus Zeichenketten
 * nachzubauen hiesse, dasselbe Markup ein zweites Mal zu pflegen — und die
 * naechste Aenderung an `.zeile` traefe nur eine der beiden Stellen. An den
 * Browser geht nur, was die Karte braucht: die Punktfolgen.
 *
 * MEHRFACHAUSWAHL. Wer eine ganze Schicht in ein Kartenprogramm ziehen will,
 * laedt sonst zwoelf Dateien einzeln herunter und sortiert sie dort wieder
 * zusammen. Ein Kaestchen je Zeile und eine Sammelleiste — derselbe Weg wie
 * in der NutzerInnen-Liste (E-P3-41) — machen daraus EINE Datei mit mehreren
 * `<trk>`. Kein neuer Baustein: `ui_zeile(['vorn' => …])` traegt das Kaestchen
 * schon, `ui_speichern_leiste()` ist dieselbe Sammelleiste.
 *
 * OHNE JAVASCRIPT bleibt der EINZELNE Abruf: Die Kaestchen stehen in einem
 * gewoehnlichen GET-Formular und wuerden auch ohne Skript absenden, aber die
 * Sammelleiste erscheint erst, wenn etwas ausgewaehlt ist — und das entscheidet
 * das Skript. Die GPX-Verweise in den Zeilen sind davon unberuehrt.
 *
 * KEINE GESCHUETZTEN ANGABEN. Der Einsatzort liegt im `pat_blob`; diese Seite
 * fasst ihn nicht an. Was sie zeigt, sind laufende Nummern, Uhrzeiten und die
 * Spur selbst — Klartext, wie auf der Tageskarte auch.
 */

$dayId = (int)($_GET['d'] ?? 0);
$tag   = $dayId > 0 ? dt_laden($userId, $dayId, true) : null;
if (!$tag) {
    ui_abbruch(404, 'Zu dieser Kennung gibt es keinen Diensttag in diesem Konto.',
               ['zurueck' => 'index.php', 'zurueck_text' => 'Zur Tagesübersicht']);
}

$pdo = db();

/* Einsaetze nach Beginn — dieselbe Reihenfolge wie in der Tagesansicht, damit
 * Nummer und Farbe dort und hier dasselbe meinen. */
$st = $pdo->prepare('SELECT id, started_at, ended_at FROM missions
                      WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL
                      ORDER BY started_at, id');
$st->execute([$userId, $dayId]);
$einsaetze = $st->fetchAll(PDO::FETCH_ASSOC);

$st = $pdo->prepare('SELECT id, started_at, ended_at FROM rest_segments
                      WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL
                      ORDER BY started_at, id');
$st->execute([$userId, $dayId]);
$ruhe = $st->fetchAll(PDO::FETCH_ASSOC);

$spuren = [];
$nr = 0;
foreach ([['mission', $einsaetze], ['rest', $ruhe]] as [$art, $liste]) {
    $ids = array_map(fn($r) => (int)$r['id'], $liste);
    $punkteAlle = $ids ? spur_lesen_viele($pdo, $art, $ids) : [];
    foreach ($liste as $r) {
        $id = (int)$r['id'];
        if ($art === 'mission') { $nr++; }
        $punkte = $punkteAlle[$id] ?? [];
        $stand  = $punkte ? spur_stand($pdo, $art, $id)
                          : ['stufe' => 0, 'n_original' => 0];
        $spuren[] = [
            'art' => $art, 'id' => $id,
            'nr' => $art === 'mission' ? $nr : null,
            'von' => $r['started_at'], 'bis' => $r['ended_at'],
            'stufe' => (int)$stand['stufe'], 'n' => count($punkte),
            'n_original' => (int)$stand['n_original'],
            // Nur Ort: Die Karte braucht weder Hoehe noch Zeit, und was sie
            // nicht braucht, wird nicht uebertragen.
            'track' => array_map(fn($p) => [$p[1], $p[2]], $punkte),
        ];
    }
}

/* CHRONOLOGISCH, NICHT NACH ART. Der erste Entwurf listete erst alle Einsätze
 * und dann alle Ruhezeiten — die Reihenfolge, in der die beiden Abfragen
 * oben stehen. So liest sich aber kein Diensttag: Er verläuft in EINER Folge,
 * Einsatz, Ruhezeit, Einsatz. Zwei Gruppen zwingen dazu, zwischen ihnen hin
 * und her zu rechnen, um zu sehen, was worauf folgte.
 *
 * Die laufende Nummer der Einsätze steht schon fest (sie wird oben vergeben,
 * in der Reihenfolge der Einsätze) und bleibt davon unberührt; ebenso die
 * Farben auf der Karte, die nur die Einsätze der Reihe nach durchzählen.
 *
 * Der Sortierschlüssel ist derselbe wie in `gpx.php` — Beginn, Art, Kennung.
 * Damit steht die Datei in derselben Folge wie die Liste, aus der sie
 * ausgewählt wurde. */
usort($spuren, fn($a, $b) => [$a['von'], $a['art'], $a['id']]
                         <=> [$b['von'], $b['art'], $b['id']]);

$mitSpur = array_values(array_filter($spuren, fn($s) => $s['n'] > 0));

/* DIE AUSWAHL ERSCHEINT ERST AB ZWEI SPUREN. Ein Kaestchen neben der einzigen
 * Zeile einer Seite waehlt nichts aus — es fragt nur, wozu es da ist, und
 * fuehrt zu einer „Auswahl", die dieselbe Datei liefert wie der GPX-Verweis
 * daneben. */
$auswahlAn = count($mitSpur) >= 2;

ui_seite_start(['titel' => 'Spuren des Diensttages', 'karte' => true]);
?>

  <div class="titelzeile">
    <a class="rueckweg" href="index.php?d=<?= $dayId ?>">
      <?= ui_symbol('winkel', 'symbol-links') ?><span>Diensttag
        <?= e(fmt_local($tag['day'] . ' 12:00:00', 'd.m.Y')) ?></span>
    </a>
    <div class="titelzeile-haupt">
      <div class="titelzeile-text">
        <h1>Spuren des Diensttages</h1>
      </div>
    </div>
    <p class="titelzeile-unter">
      <?= count($mitSpur) ?> von <?= count($spuren) ?> Einträgen tragen eine Spur ·
      <?= array_sum(array_column($spuren, 'n')) ?> Punkte insgesamt
    </p>
  </div>

  <?php if (!$spuren): ?>
    <?= ui_meldung_markup('hinweis', 'An diesem Diensttag hängt weder ein '
        . 'Einsatz noch ein Ruhesegment.') ?>
  <?php else: ?>

  <div class="geo-spalte"><div id="map" class="geo"></div></div>

  <?php ui_karte_start(['titel' => 'Spuren', 'zahl' => (string)count($spuren)]); ?>
    <?php /* WARUM EIN HINWEIS UEBER DER LISTE: Eine GPX-Datei traegt den Weg
             bis zum Einsatzort — und der ist sonst das am strengsten
             geschuetzte Feld dieser Anwendung. Der Export bindet GPX-Spuren
             aus genau diesem Grund an die personenbezogenen Angaben
             (`api/export_data.php`). Hier gibt es keine anonyme Fassung, also
             gehoert der Satz an die Stelle, an der jemand herunterlaedt. */ ?>
    <?= ui_meldung_markup('hinweis', 'Eine Spur zeigt den gefahrenen oder '
        . 'geflogenen Weg mit Zeitstempeln — bei einem Einsatz also auch den '
        . 'Einsatzort, bei einer Ruhezeit den Aufenthalt der Besatzung '
        . 'zwischen den Einsätzen. Die Dateien sind damit so zu behandeln wie '
        . 'die geschützten Angaben selbst, obwohl sie ohne Schlüssel lesbar '
        . 'sind.') ?>
    <p class="feld-hinweis">Auf eine Zeile zeigen hebt die zugehörige Linie auf
       der Karte hervor; ein Klick zoomt auf sie. <strong>GPX</strong> lädt sie
       einzeln herunter.<?php if ($auswahlAn): ?> Mehrere Kästchen ankreuzen
       lädt die ausgewählten Spuren als <em>eine</em> Datei — jede bleibt darin
       eine eigene Spur.<?php endif; ?></p>

    <?php foreach ($spuren as $i => $s): ?>
      <?php
        $istEinsatz = $s['art'] === 'mission';
        $titel = $istEinsatz
            ? 'Einsatz ' . $s['nr']
            : 'Ruhezeit';
        $zeit = fmt_local((string)$s['von'], 'H:i')
              . ($s['bis'] ? '–' . fmt_local((string)$s['bis'], 'H:i') : '–offen') . ' Uhr';

        $plaketten = '';
        if ($s['n'] === 0) {
            $plaketten = ui_plakette('keine Spur', ['ton' => 'neutral']);
        } elseif ($s['stufe'] === SPUR_STUFE_DUENN) {
            $plaketten = ui_plakette('ausgedünnt · ' . $s['n'] . ' von '
                . $s['n_original'] . ' Punkten', ['ton' => 'orange']);
        } else {
            $plaketten = ui_plakette($s['n'] . ' Punkte', ['ton' => 'neutral']);
        }
        if ($s['n'] > 0) {
            $plaketten .= ' <a class="knopf knopf-leise" '
                . 'href="gpx.php?art=' . e($s['art']) . '&amp;id=' . (int)$s['id'] . '">'
                . 'GPX</a>';
        }
      ?>
      <?php
        /* Das Kaestchen steht in `vorn` und nicht bei den Aktionen: Es waehlt
         * die Zeile AUS, statt an ihr zu handeln — dieselbe Unterscheidung
         * wie in der NutzerInnen-Liste. `form=` bindet es an das Formular
         * unter der Karte; im Markup daneben zu stehen ist nicht noetig.
         *
         * Ein Eintrag ohne Spur bekommt ein abgeschaltetes Kaestchen und
         * keines weglassen: Ein fehlendes Kaestchen liesse die Zeile um seine
         * Breite nach links rutschen, und die Liste saehe aus, als waere sie
         * verrutscht. */
        $vorn = '';
        if ($auswahlAn) {
            $vorn = '<input type="checkbox" form="f-gpxwahl" name="auswahl[]"'
                  . ' data-spurwahl value="' . e($s['art']) . '-' . (int)$s['id'] . '"'
                  . ($s['n'] > 0 ? '' : ' disabled')
                  . ' aria-label="' . e($titel . ' · ' . $zeit
                      . ($s['n'] > 0 ? ' auswählen' : ' — keine Spur')) . '">';
        }
      ?>
      <?php ui_zeile([
          'klasse'    => 'spur-zeile',
          'vorn'      => $vorn,
          'text'      => $titel . ' · ' . $zeit,
          /* Die Kleinzeile traegt die IDENTITAET, nicht die Farbe. „auf der
           * Karte in Farbe 3" stand hier zuerst und sagt niemandem etwas —
           * die Verbindung zur Karte stellt das Zeigen her, nicht ein Wort. */
          'klein'     => ($istEinsatz ? 'Einsatz' : 'Ruhesegment')
                       . ' Nr. ' . $s['id']
                       . ($s['n'] === 0 ? ' · keine Aufzeichnung' : ''),
          'plaketten' => $plaketten,
          'attr'      => ' data-spur="' . $i . '" tabindex="0"',
      ]); ?>
    <?php endforeach; ?>
  <?php ui_karte_ende(); ?>

  <?php if ($auswahlAn): ?>
    <?php /* EIN GET-FORMULAR, kein POST: Der Abruf aendert nichts, und ein
             Download soll ein gewoehnlicher Seitenwechsel sein — mit Zurueck,
             Verlauf und der Moeglichkeit, den Verweis zu kopieren. Deshalb
             auch kein CSRF-Feld; die uebrigen lesenden Wege dieser Anwendung
             halten es genauso (M3-11). */ ?>
    <form method="get" action="gpx.php" id="f-gpxwahl" hidden>
      <input type="hidden" name="tag" value="<?= $dayId ?>">
    </form>
    <?php ui_speichern_leiste([
        'id' => 'gpxleiste', 'kein_haken' => true, 'form' => 'f-gpxwahl',
        'text' => 'Auswahl als GPX', 'symbol' => 'karte',
        'zahl' => 'gpxzahl', 'hinweis' => '0 ausgewählt',
    ]); ?>
  <?php endif; ?>
  <?php endif; ?>

<?php if ($spuren): ?>
<link rel="stylesheet" href="<?= asset('assets/vendor/leaflet/leaflet.css') ?>">
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_fullscreen.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<?php /* `geo.js` baut Kartenmarkierungen aus `edSymbol()` und braucht deshalb
         `symbol.js` VOR sich — sonst wirft es beim Laden „edSymbol is not
         defined". Dieselbe Reihenfolge wie in index.php und einsatz.php. */ ?>
<script src="<?= asset('assets/symbol.js') ?>"></script>
<script src="<?= asset('assets/geo.js') ?>"></script>
<script>
const SPUREN = <?= json_encode($spuren, JSON_UNESCAPED_UNICODE) ?>;

const map = L.map('map');
map.setView([47.7, 10.3], 9);
attachBaseLayers(map);
attachFullscreenControl(map);

const linien = [];
const bounds = [];
let nrFarbe = 0;

SPUREN.forEach((s, i) => {
  if (s.track.length < 2) { linien.push(null); return; }
  /* Dieselben Farben wie in der Tagesansicht: Einsaetze bekommen der Reihe
     nach eine eigene, Ruhesegmente sind schwarz und dezent. Wer von dort
     kommt, erkennt die Linien wieder. */
  const farbe = s.art === 'mission' ? EdGeo.spurFarbe(nrFarbe++) : EdGeo.ruheFarbe();
  const linie = L.polyline(s.track, {
    color: farbe, weight: s.art === 'mission' ? 4 : 3,
    opacity: 0.9, smoothFactor: 0,
  }).addTo(map);
  linien.push(linie);
  s.track.forEach(p => bounds.push(p));
});

if (bounds.length) {
  const px = map.getSize();
  map.fitBounds(L.latLngBounds(bounds),
    { padding: L.point(px.x * 0.125, px.y * 0.125), maxZoom: 15 });
}

/* DIE VERBINDUNG ZWISCHEN LISTE UND KARTE.
 *
 * Kein neuer Baustein und keine neue Farbe: Hervorgehoben wird ueber die
 * STAERKE der Linie und die Deckkraft der uebrigen — dieselben beiden Griffe,
 * die die Tagesansicht beim Zoomen benutzt. Ein Klick zoomt zusaetzlich auf
 * die Spur.
 *
 * `focus` neben `mouseenter`: Wer mit der Tastatur durch die Liste geht, soll
 * dieselbe Auskunft bekommen wie mit der Maus. Die Zeilen tragen dafuer
 * tabindex="0".
 *
 * ZWEI EINFLUESSE, EINE ZEICHENSTELLE. Auf die Deckkraft wirken das Zeigen
 * (Maus oder Tastatur) UND die Mehrfachauswahl. Zwei Funktionen, die beide an
 * derselben Linie drehen, ueberschreiben einander: Wer nach einer Auswahl mit
 * der Maus ueber die Liste faehrt und wieder herausgeht, saehe sonst alle
 * Linien gleich hell und die Auswahl waere von der Karte verschwunden.
 * Deshalb gibt es EINE Funktion `malen()`, die beide Zustaende liest.
 */
const zeilen = Array.from(document.querySelectorAll('.spur-zeile'));
const leiste = document.getElementById('gpxleiste');
const zahlEl = document.getElementById('gpxzahl');
let zeigt = null;                       // Index der Zeile, auf die gezeigt wird

function kasten(i) {
  return zeilen[i] ? zeilen[i].querySelector('[data-spurwahl]') : null;
}

function malen() {
  let gewaehlt = 0;
  zeilen.forEach((z, i) => { const b = kasten(i); if (b && b.checked) { gewaehlt++; } });

  linien.forEach((l, i) => {
    if (!l) { return; }
    const b = kasten(i);
    /* Ohne Auswahl sind alle gleich hell; mit Auswahl treten die uebrigen
       zurueck. Wird auf eine Zeile gezeigt, gilt nur sie — das Zeigen ist die
       unmittelbarere Aussage und ueberstimmt die Auswahl, solange es dauert. */
    const imBund = gewaehlt === 0 || (b !== null && b.checked);
    const an = zeigt === null ? imBund : i === zeigt;
    l.setStyle({ opacity: an ? 0.95 : 0.25,
                 weight: i === zeigt ? 7 : (SPUREN[i].art === 'mission' ? 4 : 3) });
  });
  zeilen.forEach(z => {
    z.classList.toggle('zeile-hervor', Number(z.dataset.spur) === zeigt);
  });

  if (leiste) {
    if (zahlEl) {
      zahlEl.textContent = gewaehlt === 1 ? '1 Spur ausgewählt'
                                          : gewaehlt + ' Spuren als eine Datei';
    }
    leiste.hidden = gewaehlt === 0;
  }
}

function hervorheben(idx) { zeigt = idx; malen(); }

zeilen.forEach(z => {
  const idx = Number(z.dataset.spur);
  const linie = linien[idx];
  z.addEventListener('mouseenter', () => hervorheben(idx));
  z.addEventListener('focus',      () => hervorheben(idx));
  z.addEventListener('mouseleave', () => hervorheben(null));
  z.addEventListener('blur',       () => hervorheben(null));
  z.addEventListener('click', (e) => {
    /* Der GPX-Verweis in der Zeile soll herunterladen, nicht zoomen — und das
       Auswahlkaestchen soll auswaehlen, nicht zoomen. */
    if (e.target.closest('a') || e.target.closest('[data-spurwahl]')) { return; }
    if (linie) { map.fitBounds(linie.getBounds(), { padding: L.point(30, 30), maxZoom: 16 }); }
  });
});

/* DIE MEHRFACHAUSWAHL kommt ohne sessionStorage aus, anders als die der
   NutzerInnen-Liste (E-P3-41): Dort gilt eine Auswahl ueber mehrere Seiten
   hinweg, hier steht der ganze Tag auf einer. Das Skript zaehlt und zeichnet;
   das Absenden macht der Browser mit dem gewoehnlichen GET-Formular. */
document.querySelectorAll('[data-spurwahl]').forEach(box => {
  box.addEventListener('change', malen);
});

malen();
</script>
<?php endif; ?>

<?php ui_seite_ende(); ?>
