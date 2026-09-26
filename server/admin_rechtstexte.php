<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/rechtstexte_lib.php';
require_once __DIR__ . '/protokoll_lib.php';

/**
 * RECHTSTEXTE — die vier Texte, die diese Installation nach außen zeigt
 * (P5c/AP9, E-P5c-28, Backlog Nr. 121; Mockup M-P5c-01d, Variante 2).
 *
 * WIEDER EINE SEITE. Von S8 bis Web 21.0.0 war diese Adresse eine
 * Weiterleitung auf „Installation", wo die vier Texte untereinander in der
 * rechten Spalte standen — jeder mit einer Vorschau des GESPEICHERTEN Stands
 * darunter. Eine Vorschau unter einem Feld von 18 Zeilen steht bei 900 px
 * Fensterhöhe gerade noch im Bild, bei 720 px nicht mehr: Man tippt oben und
 * sieht unten nichts. Hier steht ab 1200 px die Vorschau NEBEN dem Feld und
 * läuft beim Tippen mit (`assets/rechtstext_vorschau.js`); darunter stapelt
 * sie sich wie vorher, ist also nie schlechter.
 *
 * EIN TEXT JE REITER, EIN FORMULAR JE TEXT. Die Reiter sind Verweise
 * (`ui_reiter()`), keine Sichtwechsel im selben Formular: So braucht der
 * Baustein kein Skript, und ein Reiterwechsel mit ungespeichertem Text fragt
 * über `data-cancel-form` nach — dieselbe Rückfrage wie jeder
 * Abbrechen-Verweis (`assets/forms.js`). Gespeichert wird genau der Text des
 * offenen Reiters.
 *
 * DER RENDERER BLEIBT AUF DEM SERVER (`rt_html()`). Die Vorschau beim Tippen
 * fragt `api/rechtstext_vorschau.php` — es gibt keinen zweiten Renderer im
 * Browser, der eines Tages anders läse als die öffentliche Seite. Ohne Skript
 * zeigt die Vorschau den gespeicherten Stand, wie bisher.
 *
 * KEINE UNTERPUNKTE IN DER LEISTE: Die Seite trägt Reiter, und `menue.js`
 * baut dann keine (E-P5c-25).
 */

$k = (string)($_POST['schluessel'] ?? $_GET['t'] ?? 'impressum');
if (!isset(RT_TEXTE[$k])) { $k = 'impressum'; }
$name = RT_TEXTE[$k];

$notice = null; $error = null;
/* Nach einem abgewiesenen Speichern steht im Feld, was getippt war — nicht
 * der gespeicherte Stand. Sonst wäre die Eingabe mit der Fehlermeldung weg. */
$eingabe = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $text  = (string)($_POST['text'] ?? '');
    $stand = trim((string)($_POST['stand'] ?? ''));
    $mangel = rt_pruefen($text, $stand);
    if ($mangel !== null) {
        $error = $name . ': ' . $mangel;
        $eingabe = ['inhalt' => $text, 'stand' => $stand === '' ? null : $stand];
    } else {
        $vorher = rt_lesen($k);
        $neu = str_replace(["\r\n", "\r"], "\n", $text);
        $standNeu = $stand === '' ? null : $stand;
        if ($neu !== $vorher['inhalt'] || $standNeu !== $vorher['stand']) {
            rt_speichern($k, $neu, $standNeu);
            if (function_exists('protokoll')) {
                $folge = isset(RT_EINWILLIGUNG[$k]) && RT_EINWILLIGUNG[$k]['art'] === 'annahme'
                    ? ' — mit neuem Stand sperrt das den nächsten Login, '
                    . 'bis alle Konten angenommen haben'
                    : '';
                protokoll('verwaltung', 'rechtstext_geaendert',
                          $name . ' geändert (Stand ' . ($standNeu ?? 'ohne Datum') . ')' . $folge,
                          ['schluessel' => $k, 'stand' => $standNeu]);
            }
            $notice = $name . ' gespeichert.';
        } else {
            $notice = 'Es gab nichts zu ändern.';
        }
    }
}

$t = $eingabe ?? rt_lesen($k);
$gespeichert = rt_lesen($k);
$leer = rt_leer($gespeichert['inhalt']);
/* MIT ZEITSTEMPEL, deshalb kein `datum_text()` (Schritt 15 AP7, benannte
 * Ausnahme, Register Z26): `stand` ist ein KALENDERTAG, keine UTC-Marke — eine
 * Umrechnung nach `app.timezone` verschöbe ihn. Gleiche Lage wie in
 * `rt_stand_markup()`. Bis Web 21.0.0 stand diese Zeile in
 * `admin_installation.php`. */
$kopfzahl = $gespeichert['stand'] !== null && strtotime($gespeichert['stand']) !== false
    ? 'Stand ' . date('d.m.Y', (int)strtotime($gespeichert['stand']))
    : 'ohne Standdatum';

ui_seite_start(['titel' => 'Rechtstexte']);
?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_rechtstexte']); ?>

  <?php ui_titelzeile(['titel' => 'Rechtstexte']); ?>
  <p class="seiten-erklaerung">Die vier Texte, die diese Installation nach außen
     zeigt — der Inhalt ist Sache der BetreiberIn.
     <a href="hilfe.php#11-5a-rechtstexte">Handbuch: Rechtstexte</a></p>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <?php
  /* DIE REITER TRAGEN `data-cancel-form` (F-P5c-57): Wer mit ungespeichertem
     Text den Reiter wechselt, wird gefragt — nur dann; ohne Änderung folgt
     der Verweis wie jeder andere. Beschriftet aus `RT_TEXTE`: Der vierte heißt
     „Vereinbarung zur Auftragsverarbeitung (AVV)", nicht „AVV" wie im Bild
     (Gestaltungsvorgabe 4, 17.09.2026); unter 720 px rollt die Reihe. */
  $punkte = [];
  foreach (RT_TEXTE as $schl => $titel) {
      $punkte[] = ['text' => $titel, 'href' => '?t=' . $schl, 'aktiv' => $schl === $k,
                   'attr' => ' data-cancel-form="rt-form"'
                           . ' data-cancel-confirm="Der geänderte Text ist nicht gespeichert. Trotzdem wechseln?"'];
  }
  ui_reiter(['label' => 'Rechtstexte', 'punkte' => $punkte]);
  ?>

  <?php /* EIN FORMULAR UM BEIDE SPALTEN: Die Speichern-Leiste steht darin
           (`forms.js` sucht sie mit f.querySelector), und sie gehört unter
           beide — die Vorschau ist Teil dessen, was man gerade bearbeitet.
           data-dirty-track hängt die Leiste an, data-submit-on-ctrl-enter
           sagt ihr Hinweistext zu. */ ?>
  <form method="post" id="rt-form" data-dirty-track data-submit-on-ctrl-enter>
    <?= csrf_field() ?>
    <input type="hidden" name="schluessel" value="<?= e($k) ?>">

    <div class="form-raster">
    <div class="form-spalte">
      <?php ui_karte_start(['titel' => $name, 'zahl' => $kopfzahl, 'id' => 'k-text',
                            'aktion' => ['text' => 'Ansehen', 'href' => RT_SEITEN[$k],
                                         'symbol' => 'winkel']]); ?>
        <?php ui_feld([
            'name'         => 'text',
            'id'           => 'rt-text',
            'label'        => 'Text',
            'label_zusatz' => '(Markdown: Überschriften, Absätze, Listen, Links)',
            'art'          => 'textarea',
            'zeilen'       => 18,
            'klasse'       => 'feld-fest',
            'wert'         => $t['inhalt'],
            'attr'         => ' data-rt-text',
            'klein'        => 'Kein HTML — Tags erscheinen als Text.',
        ]); ?>

        <?php /* DAS STANDDATUM WIRD VON HAND GESETZT. Automatisch wäre es
                 bequemer und an einem Rechtstext falsch: Das Datum ist eine
                 Aussage darüber, auf welchem Stand der Text INHALTLICH ist —
                 eine Kommakorrektur soll ihn nicht neu datieren. Leer heißt:
                 keine Standzeile auf der öffentlichen Seite. */ ?>
        <?php ui_feld([
            'name'  => 'stand',
            'id'    => 'rt-stand',
            'label' => 'Stand',
            'art'   => 'date',
            'wert'  => (string)($t['stand'] ?? ''),
            'attr'  => ' data-rt-stand',
            'klein' => $k === 'datenschutz'
                ? 'Erscheint als „Stand: …" am Ende der Seite und in der Android-App; '
                . 'wird nicht automatisch gesetzt.'
                : 'Erscheint als „Stand: …" am Ende der Seite; wird nicht automatisch gesetzt.',
        ]); ?>

        <div class="rechtstext-fuss">
          <?= $leer
              ? ui_plakette('leer', ['ton' => 'rot'])
              : ui_plakette('öffentlich', ['ton' => 'blau']) ?>
          <span class="feld-klein">Seite:
            <a href="<?= e(RT_SEITEN[$k]) ?>"><?= e(RT_SEITEN[$k]) ?></a></span>
        </div>
      <?php ui_karte_ende(); ?>

      <?php if ($k === 'datenschutz'): ?>
        <?php require_once __DIR__ . '/geocoder_lib.php'; ?>
        <?php $bausteine = geocoder_installation_an() ? 2 : 1; ?>
        <?php /* ZWEI TEXTBAUSTEINE, ZUGEKLAPPT. Die Anwendung liefert KEINEN
                 Rechtstext mit (R32) — was darin steht, ist Sache der
                 BetreiberIn. Was sie kann, ist VORSCHLAGEN, mit den Angaben,
                 die tatsächlich gelten. Der Baustein zur Adresssuche steht nur,
                 solange sie eingeschaltet ist (S9/AP2): ein Absatz über eine
                 Abfrage, die nicht stattfindet, wäre eine falsche Auskunft. Der
                 zu den Sicherheitsereignissen steht immer (P5a/AP8): Den
                 Ratenschutz gibt es in jeder Installation. */ ?>
        <?php ui_karte_start(['titel' => 'Textbausteine', 'id' => 'k-bausteine',
                              'vorschau' => $bausteine . ' zum Übernehmen']); ?>
          <?php if (geocoder_installation_an()): ?>
            <?= ui_codeblock_lang(
                  '### Adresssuche' . "\n\n"
                . 'Wenn du in ein Ortsfeld tippst oder auf der Karte einen Ort '
                . 'wählst, fragt dein Browser den Adressdienst '
                . geocoder_host() . '. Übertragen werden dabei der getippte '
                . 'Text beziehungsweise die gewählte Koordinate, dazu — wie bei '
                . 'jedem Abruf im Internet — deine IP-Adresse. Namen, Diagnosen '
                . 'und Einsatznummern werden nicht übertragen. Die Suche lässt '
                . 'sich abschalten: in deinem Profil unter „Datenschutz".',
                  'Vorschlag für den Abschnitt „Adresssuche"') ?>
          <?php endif; ?>
          <?php /* DER LETZTE SATZ STIMMT SEIT WEB 20.39.0 NICHT MEHR SO
                   (F-P5c-153): Bis Web 21.0.0 stand hier „Sicherungskopien der
                   Datenbank können diese Angaben enthalten". Seit P5c/AP2 geht
                   `sicherheit_ereignisse` OHNE ZEILEN ins Komplett-Backup
                   (`KOMP_OHNE_ZEILEN`), und das Archiv des Protokolls führt die
                   Sicherheitsereignisse ohne Merkmal und Person (E-P5c-39). */ ?>
          <?= ui_codeblock_lang(
                '### Schutz vor unbefugten Anmeldeversuchen' . "\n\n"
              . 'Wenn bei der Anmeldung mehrfach ein falsches Passwort eingegeben '
              . 'wird oder ein Gerät sich wiederholt mit einem ungültigen '
              . 'Schlüssel meldet, sperrt die Anwendung den betroffenen Zugang '
              . 'vorübergehend. Dafür werden die betroffene E-Mail-Adresse '
              . 'beziehungsweise IP-Adresse zusammen mit Zeitpunkt und Anzahl '
              . 'der Versuche gespeichert. Diese Angaben dienen ausschließlich '
              . 'der Abwehr von Angriffen, werden nicht ausgewertet und nach '
              . '**30 Tagen automatisch gelöscht**. Eine laufende Sperre kann '
              . 'die BetreiberIn vorzeitig aufheben; auch das wird mit '
              . 'Zeitpunkt vermerkt.' . "\n\n"
              . 'Sicherungskopien der Datenbank enthalten diese Angaben nicht.',
                'Vorschlag für den Abschnitt „Schutz vor unbefugten Anmeldeversuchen"') ?>
        <?php ui_karte_ende(true); ?>
      <?php endif; ?>
    </div><?php /* .form-spalte (links) */ ?>

    <div class="form-spalte">
      <?php /* DIE VORSCHAU (M-P5c-01d, Zustände Teil 3). Ohne Skript: der
               gespeicherte Stand, wie bisher. Mit Skript: nach 0,4 s Ruhe der
               getippte Text, vom Server gerendert; die Plakette sagt, welcher
               Stand gerade dasteht. Der Kasten rollt in sich und folgt dem Feld
               anteilig — ohne Quellzuordnung, das genügt für „ungefähr dort". */ ?>
      <?php ui_karte_start(['titel' => 'Vorschau', 'id' => 'k-vorschau',
                            'plakette' => '<span data-rt-zustand>'
                                        . ui_plakette('gespeicherter Stand', ['ton' => 'blau'])
                                        . '</span>']); ?>
        <div data-rt-fehler hidden></div>
        <div class="vorschau-rollt" data-rt-vorschau aria-live="polite">
          <div class="text">
            <?php if ($leer): ?>
              <p class="feld-hinweis">Noch kein Text gespeichert.</p>
            <?php else: ?>
              <?= rt_html($gespeichert['inhalt']) ?>
              <?= rt_stand_markup($gespeichert['stand']) ?>
            <?php endif; ?>
          </div>
        </div>
      <?php ui_karte_ende(); ?>
    </div><?php /* .form-spalte (rechts) */ ?>
    </div><?php /* .form-raster */ ?>

    <?php ui_speichern_leiste(['text' => 'Änderungen speichern',
                               'hinweis' => 'Es gibt ungespeicherte Änderungen',
                               'hinweis_vorlage' => 'Ungespeichert']); ?>
  </form>

<?php ui_geruest_ende(); ?>
<?php /* forms.js bringt ui_geruest_ende() NICHT mit — ohne diese Zeile bliebe
         die Speichern-Leiste unsichtbar, und zwar ohne Fehlermeldung.
         kopieren.js gehört zum Wertekasten der Textbausteine (Design.md 9.18). */ ?>
<?php /* DAS TOKEN FÜR `EdApi` — ohne das Krypto-Rüstzeug, das diese Seite nicht
         braucht (`ui_csrf_bootstrap()`, E-P5c-130). Fehlt es, antwortet der
         Vorschau-Endpunkt 403, und die Plakette steht auf „nicht aktuell". */ ?>
<?php ui_csrf_bootstrap(); ?>
<?php /* html.js (EdHtml.meldung) VOR dem Vorschauskript — es steht in keiner
         Immer-Liste; api.js (EdApi) steht im Kopf jeder Seite. */ ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js', 'assets/kopieren.js',
                                   'assets/html.js', 'assets/rechtstext_vorschau.js']]); ?>
