<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/rechtstexte_lib.php';

/**
 * INSTALLATION — wie diese Anlage nach aussen auftritt (S8/AP3, E-S8-05/-12).
 *
 * AUS „RECHTSTEXTE" WIRD „INSTALLATION". Die Seite trug bis Web 15.1.0 zwei
 * Karten: Impressum und Datenschutz. Beide beantworten dieselbe Frage — was
 * zeigt diese Installation Menschen, die noch nicht angemeldet sind? —, und
 * das Logo beantwortet sie ebenso. Es lag auf der Wartungsseite, und das war
 * ein Befund (B-S8-10): Der Logo-Standard ist Gestaltung, keine Wartung.
 *
 * DREI KARTEN, EIN SPEICHERN — mit einer Ausnahme. Impressum und Datenschutz
 * teilen sich die Speichern-Leiste, wie bisher; man pflegt sie in einem Zug.
 * Das Logo hat einen EIGENEN Knopf, weil es kein Text ist, der reift, sondern
 * eine Wahl, die sofort wirkt — auch fuer bereits angemeldete Konten. Ein
 * gemeinsames Speichern haette bedeutet, dass ein halbfertiger Rechtstext die
 * Logo-Wahl aufhaelt.
 *
 * WAS P5 HIER ERGAENZT (E-S8-12): Karten „Support-Adresse", „Registrierung"
 * und „Ankuendigungsbanner" — als weitere Karten DIESER Seite. Heute steht
 * dafuer kein Platzhalter (B-S8-11): Ein Kasten mit „kommt spaeter" ist eine
 * Zusage, die niemand gegeben hat.
 *
 * DIE VORSCHAU KOMMT VOM SERVER, nicht aus JavaScript. Ein zweiter Renderer
 * im Browser waere genau die Stelle, an der die Regeln auseinanderlaufen: Er
 * muesste dieselbe Positivliste fuer Linkziele, dieselbe Maskierreihenfolge
 * und dieselben Zeichenfilter fuehren, und beim naechsten Fund wuerde einer
 * von beiden vergessen. Sie zeigt also den zuletzt GESPEICHERTEN Stand — das
 * Konzept laesst das ausdruecklich zu (E-P3-38).
 */

/**
 * Liegt der NEF-Platzhalter noch (E-P3-19)?
 *
 * Gefragt wird die DATEI, nicht eine Zahl im Code: Der Platzhalter traegt das
 * Wort in seinem Kopfkommentar, und sobald die echte Datei an seiner Stelle
 * liegt, verschwindet der Hinweis von selbst. Gelesen werden nur die ersten
 * 400 Byte — der Kommentar steht ganz oben.
 *
 * DIE DATEINAMEN HABEN SICH GEAENDERT. Erwartet wurde ein Ersatz 1:1 unter
 * gleichem Namen; gekommen ist er als 'gen-em_logo_nef*'. Beide Namen stehen
 * deshalb in der Liste: Eine aeltere Installation, die noch den Platzhalter
 * unter dem alten Namen traegt, soll den Hinweis weiterhin bekommen.
 */
function logo_platzhalter_liegt(): array
{
    $liegt = [];
    foreach (['gen-em_logo_nef.svg', 'gen-em_logo_nef_weiss.svg',
              'gen-em_logo_fahrzeug.svg', 'gen-em_logo_fahrzeug_weiss.svg'] as $datei) {
        $pfad = __DIR__ . '/assets/images/' . $datei;
        if (!is_file($pfad)) { continue; }
        $f = @fopen($pfad, 'rb');
        if ($f === false) { continue; }
        $kopf = (string)fread($f, 400);
        fclose($f);
        if (str_contains($kopf, 'PLATZHALTER')) { $liegt[] = $datei; }
    }
    return $liegt;
}

/* Die drei Wahlmoeglichkeiten des Installationsstandards. „wechselnd" ist
 * seit Web 9.14.0 auch hier waehlbar (F-N1-C) — eine Installation, die beide
 * Rettungsmittel fuehrt, hat denselben Grund dafuer wie eine einzelne Person.
 * Aufgeloest wird es in logo_standard_aufgeloest(). */
const INSTALLATION_LOGOS = [
    'hubschrauber' => 'Hubschrauber (RTH)',
    'fahrzeug'     => 'Fahrzeug (NEF)',
    'wechselnd'    => 'wechselnd',
];

$notice = null; $error = null;
$logoMeldung = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'logo_standard') {
        $wahl = (string)($_POST['logo'] ?? '');
        if (!isset(INSTALLATION_LOGOS[$wahl])) {
            $logoMeldung = ['fehler', 'Unbekannte Logo-Wahl — es wurde nichts geändert.'];
        } else {
            db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                           ON DUPLICATE KEY UPDATE v = VALUES(v)')
                ->execute(['logo_standard', $wahl]);
            $logoMeldung = ['ok', 'Standard der Installation: ' . INSTALLATION_LOGOS[$wahl]
                . ($wahl === 'wechselnd'
                   ? '. Je Anmeldung wird neu gewürfelt — innerhalb einer Sitzung '
                     . 'bleibt das Logo stehen.'
                   : '.')
                . ' Wer im Profil keine eigene Wahl getroffen hat, sieht das ab sofort.'];
        }
    } else {
        /* ERST ALLES PRUEFEN, DANN ALLES SPEICHERN. Sonst stuende nach einem
         * abgelehnten zweiten Text der erste schon in der Datenbank, und die
         * Meldung „nicht gespeichert" waere zur Haelfte falsch. */
        $eingaben = [];
        foreach (RT_TEXTE as $k => $name) {
            $eingaben[$k] = [
                'text'  => (string)($_POST['text_' . $k] ?? ''),
                'stand' => trim((string)($_POST['stand_' . $k] ?? '')),
            ];
            $mangel = rt_pruefen($eingaben[$k]['text'], $eingaben[$k]['stand']);
            if ($mangel !== null) {
                $error = $name . ': ' . $mangel;
                break;
            }
        }

        if ($error === null) {
            $geaendert = [];
            foreach (RT_TEXTE as $k => $name) {
                $vorher = rt_lesen($k);
                /* Zeilenenden vergleichbar machen: Der Browser schickt CRLF aus
                 * einem <textarea>, gespeichert wurde LF. Ohne das meldete jedes
                 * Speichern eine Aenderung. */
                $neu = str_replace(["\r\n", "\r"], "\n", $eingaben[$k]['text']);
                $standNeu = $eingaben[$k]['stand'] === '' ? null : $eingaben[$k]['stand'];
                if ($neu !== $vorher['inhalt'] || $standNeu !== $vorher['stand']) {
                    rt_speichern($k, $neu, $standNeu);
                    $geaendert[] = $name;
                }
            }
            $notice = $geaendert
                ? implode(' und ', $geaendert) . ' gespeichert.'
                : 'Es gab nichts zu ändern.';
        }
    }
}

$texte = rt_alle();

ui_seite_start(['titel' => 'Installation']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_installation']); ?>

  <?php ui_titelzeile(['titel' => 'Installation',
                       'unter' => 'Wie diese Installation nach außen auftritt']); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <?php /* Links das Logo, rechts die beiden Texte (Mockup 09). Der zweite
           Zweig des Zweispalters ist ein schlichtes <div> — die Karten
           bringen ihren Abstand selbst mit (`.karte { margin-bottom }`), es
           braucht dafuer keine eigene Regel. Unter 1200 px ist der
           Zweispalter ein Block, und alles steht untereinander. */ ?>
  <div class="zweispalter">

    <div>
      <?php ui_karte_start(['titel' => 'Logo', 'id' => 'k-logo',
                            'zahl' => 'Standard dieser Installation']); ?>
        <?php if ($logoMeldung !== null): ?>
          <?= ui_meldung_markup($logoMeldung[0], $logoMeldung[1]) ?>
        <?php endif; ?>

        <?php /* Die Kachel zeigt, was gerade gilt — aufgeloest, also bei
                 „wechselnd" das Ergebnis dieser Sitzung. Sie steht auf
                 Dunkelblau, weil das Logo dort steht, wo man es am haeufigsten
                 sieht: in der Kopfleiste. */ ?>
        <?php $stamm = logo_standard_aufgeloest() === 'fahrzeug'
                     ? 'gen-em_logo_nef' : 'gen-em_logo_helicopter'; ?>
        <?php $masse = ui_logo_masse(34); ?>
        <div class="logo-vorschau">
          <div class="logo-kachel">
            <img src="<?= e(ui_asset('assets/images/' . $stamm . '_weiss.svg')) ?>"
                 width="<?= (int)$masse['breite'] ?>" height="<?= (int)$masse['hoehe'] ?>"
                 alt="">
          </div>
          <p class="feld-hinweis">Kopfleiste, Browser-Symbol und Anmeldeseite.
             NutzerInnen können im Profil für sich davon abweichen; die
             Anmeldeseite zeigt immer den Standard.</p>
        </div>

        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="logo_standard">
          <?php ui_segment(['name' => 'logo', 'id' => 'logo-standard',
                            'wert' => logo_standard(),
                            'optionen' => INSTALLATION_LOGOS]); ?>
          <p class="feld-hinweis">Die Änderung wirkt <strong>sofort</strong>, auch für
             bereits angemeldete Konten; eine im Profil getroffene Wahl bleibt
             unberührt. <strong>Wechselnd</strong> würfelt je Anmeldung neu —
             innerhalb einer Sitzung bleibt das Logo stehen, damit es beim
             Blättern nicht springt.</p>
          <?php $platzhalter = logo_platzhalter_liegt(); ?>
          <?php if ($platzhalter): ?>
            <p class="feld-klein">Das Fahrzeug-Logo (NEF) ist ein
               <strong>Platzhalter</strong> — es steht hier, damit die Logo-Wahl
               vollständig gebaut und geprüft werden kann, bevor die echte Datei
               vorliegt. Sie ersetzt ihn 1:1: gleicher Name, gleiche Maße, kein
               Eingriff im Code. Betroffen: <?= e(implode(', ', $platzhalter)) ?>.
               Dieser Hinweis verschwindet von selbst, sobald die echten Dateien
               liegen.</p>
          <?php endif; ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Standard speichern', 'symbol' => 'haken',
                          'art' => 'primaer']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
    </div>

    <?php /* data-dirty-track haengt die Speichern-Leiste an das Formular
             (assets/forms.js) — ohne das Attribut erschiene sie nie, und zwar
             lautlos. data-submit-on-ctrl-enter, weil der Hinweistext der Leiste
             es zusagt. */ ?>
    <form method="post" data-dirty-track data-submit-on-ctrl-enter>
      <?= csrf_field() ?>

      <?php foreach (RT_TEXTE as $k => $name):
        $t = $texte[$k];
        $leer = rt_leer($t['inhalt']);
        /* Der Kartenkopf traegt den Stand — dieselbe Auskunft, die die
           oeffentliche Seite unten zeigt. */
        $kopfzahl = $t['stand'] !== null && strtotime($t['stand']) !== false
            ? 'Stand ' . date('d.m.Y', (int)strtotime($t['stand']))
            : 'ohne Standdatum';
      ?>
        <?php /* „Ansehen" oeffnet die oeffentliche Seite. Sie war bisher nur
                 ueber die Fusszeile erreichbar — und wer gerade einen Text
                 bearbeitet, will ihn dort sehen, wo ihn andere sehen. */ ?>
        <?php ui_karte_start(['titel' => $name, 'zahl' => $kopfzahl,
                              'id' => 'k-' . $k,
                              'aktion' => ['text' => 'Ansehen',
                                           'href' => RT_SEITEN[$k],
                                           'symbol' => 'winkel']]); ?>

          <?php ui_feld([
              'name'         => 'text_' . $k,
              'id'           => 'rt-' . $k,
              'label'        => 'Text',
              'label_zusatz' => '(Markdown: Überschriften, Absätze, Listen, Links)',
              'art'          => 'textarea',
              'zeilen'       => 18,
              'klasse'       => 'feld-fest',
              'wert'         => $t['inhalt'],
              'klein'        => 'Erlaubt: ## Überschrift, ### Unterüberschrift, '
                              . 'Absätze, - Listen, 1. Nummerierung, '
                              . '[Text](https://…). Kein HTML — Tags erscheinen '
                              . 'als Text.',
          ]); ?>

          <?php /* DAS STANDDATUM WIRD VON HAND GESETZT. Automatisch waere es
                   bequemer und an einem Rechtstext falsch: Das Datum ist eine
                   Aussage darueber, auf welchem Stand der Text INHALTLICH ist —
                   eine Kommakorrektur soll ihn nicht neu datieren. Leer heisst:
                   keine Standzeile auf der oeffentlichen Seite. */ ?>
          <?php ui_feld([
              'name'  => 'stand_' . $k,
              'id'    => 'rt-stand-' . $k,
              'label' => 'Stand',
              'art'   => 'date',
              'wert'  => (string)($t['stand'] ?? ''),
              'klein' => 'Erscheint als „Stand: …" am Ende der Seite. Leer '
                       . 'lassen heißt: kein Datum. Wird nicht automatisch '
                       . 'gesetzt — bei einem Rechtstext ist das Datum eine '
                       . 'Aussage.'
                       . ($k === 'datenschutz'
                          ? ' Dieser Text erscheint auch in der Android-App.'
                          : ''),
          ]); ?>

          <?php if (!$leer): ?>
            <div class="vorschau">
              <h4>Vorschau</h4>
              <div class="text">
                <?= rt_html($t['inhalt']) ?>
                <?= rt_stand_markup($t['stand']) ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="rechtstext-fuss">
            <?= $leer
                ? ui_plakette('leer', ['ton' => 'rot'])
                : ui_plakette('öffentlich', ['ton' => 'blau']) ?>
            <span class="feld-klein">Seite:
              <a href="<?= e(RT_SEITEN[$k]) ?>"><?= e(RT_SEITEN[$k]) ?></a></span>
          </div>

        <?php ui_karte_ende(); ?>
      <?php endforeach; ?>

      <?php /* Die Leiste steht INNERHALB des Formulars — forms.js sucht sie mit
               f.querySelector('[data-speichern]'). Ausserhalb erschiene sie nie,
               ohne Fehlermeldung. */ ?>
      <?php ui_speichern_leiste(['text' => 'Änderungen speichern',
                                 'hinweis' => 'Es gibt ungespeicherte Änderungen',
                                 'hinweis_vorlage' => 'Ungespeichert']); ?>
    </form>

  </div>

  <?php /* Die Vorschau zeigt den GESPEICHERTEN Stand. Das ist keine
           Einschraenkung, die man verschweigt — wer gerade getippt hat und
           unten nichts davon sieht, haelt den Editor fuer kaputt. */ ?>
  <p class="feld-hinweis">Impressum und Datenschutzerklärung sind
     <strong>ohne Anmeldung</strong> erreichbar und in jeder Fußzeile verlinkt.
     Der Inhalt ist Sache des Betreibers; die Anwendung liefert keinen Text mit.
     Die Vorschau zeigt den zuletzt <strong>gespeicherten</strong> Stand — sie
     entsteht auf dem Server, mit demselben Renderer wie die öffentliche Seite.</p>

<?php ui_geruest_ende(); ?>
<?php /* forms.js bringt ui_geruest_ende() NICHT mit (nur symbol, schublade,
         blatt, confirm) — ohne diese Zeile bliebe die Speichern-Leiste
         unsichtbar, und zwar ohne jede Fehlermeldung. */ ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
