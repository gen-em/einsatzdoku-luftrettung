<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
/* VOR dem POST-Zweig und nicht danach: Der Protokolleintrag entsteht beim
 * Speichern, und `function_exists('protokoll')` waere weiter unten `false`
 * gewesen — der Eintrag waere still ausgefallen (P5b/AP4). */
require_once __DIR__ . '/protokoll_lib.php';

/**
 * INSTALLATION — wie diese Anlage nach aussen auftritt (S8/AP3, E-S8-05/-12).
 *
 * AUS „RECHTSTEXTE" WIRD „INSTALLATION". Die Seite trug bis Web 15.1.0 zwei
 * Karten: Impressum und Datenschutz. Beide beantworten dieselbe Frage — was
 * zeigt diese Installation Menschen, die noch nicht angemeldet sind? —, und
 * das Logo beantwortet sie ebenso. Es lag auf der Wartungsseite, und das war
 * ein Befund (B-S8-10): Der Logo-Standard ist Gestaltung, keine Wartung.
 *
 * SEIT P5c/AP9 OHNE DIE RECHTSTEXTE (E-P5c-28). Sie stehen wieder auf einer
 * eigenen Seite, `admin_rechtstexte.php`, mit Reitern und einer Vorschau, die
 * beim Tippen mitläuft. Hier bleiben Name, Adressen und Logo — jede Karte mit
 * ihrem eigenen Knopf, weil jede eine Wahl ist, die sofort wirkt.
 *
 * WAS P5 HIER ERGAENZT (E-S8-12): Karten „Support-Adresse", „Registrierung"
 * und „Ankuendigungsbanner" — als weitere Karten DIESER Seite. Heute steht
 * dafuer kein Platzhalter (B-S8-11): Ein Kasten mit „kommt spaeter" ist eine
 * Zusage, die niemand gegeben hat.
 *
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
$nameMeldung = null;
$adrMeldung  = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'instanz_name') {
        /* DER NAME DIESER INSTALLATION (P5a/AP5, E-P5a-35). Eigenes
         * „Speichern", wie bei Logo und Adresssuche: Ein Tippfehler im Namen
         * soll die Rechtstexte nicht mit abweisen und umgekehrt. */
        [$ok, $meldung] = instanz_namen_setzen((string)($_POST['instanz_name'] ?? ''),
                                               (string)($_POST['instanz_kurz'] ?? ''));
        $nameMeldung = [$ok ? 'ok' : 'fehler', $meldung];
    } elseif (($_POST['action'] ?? '') === 'instanz_adressen') {
        /* DIE ADRESSEN DIESER INSTALLATION (P5a/AP5, E-P5a-40). Eigenes
         * „Speichern" wie beim Namen, und aus demselben Grund. */
        [$ok, $meldung] = instanz_adressen_setzen((string)($_POST['instanz_kontakt'] ?? ''),
                                                  (string)($_POST['betrieb_mail'] ?? ''));
        $adrMeldung = [$ok ? 'ok' : 'fehler', $meldung];
    } elseif (($_POST['action'] ?? '') === 'logo_standard') {
        $wahl = (string)($_POST['logo'] ?? '');
        if (!isset(INSTALLATION_LOGOS[$wahl])) {
            $logoMeldung = ['fehler', 'Unbekannte Logo-Wahl — es wurde nichts geändert.'];
        } else {
            app_state_setzen('logo_standard', $wahl);
            $logoMeldung = ['ok', 'Standard der Installation: ' . INSTALLATION_LOGOS[$wahl]
                . ($wahl === 'wechselnd'
                   ? '. Je Anmeldung wird neu gewürfelt — innerhalb einer Sitzung '
                     . 'bleibt das Logo stehen.'
                   : '.')
                . ' Wer im Profil keine eigene Wahl getroffen hat, sieht das ab sofort.'];
        }
    }
}

ui_seite_start(['titel' => 'Installation']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_installation']); ?>

  <?php ui_titelzeile(['titel' => 'Installation',
                       'unter' => 'Wie diese Installation nach außen auftritt — '
                                . '<a href="hilfe.php#11-5-installation">Handbuch: Installation</a>']); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <?php /* EINE SPALTE SEIT P5c/AP9 (E-P5c-28, F-P5c-46). Bis Web 21.0.0 stand
           rechts das Formular der vier Rechtstexte; sie haben eine eigene
           Seite bekommen (`admin_rechtstexte.php`, Vorschau beim Tippen).
           Übrig sind drei Karten — nach Design.md 9.26 kein Fall für zwei
           Spalten —, gesetzt mit der vorhandenen Variante
           `.form-raster-einspaltig`: höchstens Lesespalte breit, damit die
           kurzen Felder nicht über 1400 px laufen. `.form-spalte` bleibt,
           `menue.js` liest sie (9.26). */ ?>
  <div class="form-raster form-raster-einspaltig">

    <div class="form-spalte">
      <?php /* DER NAME STEHT ZUOBERST, und zwar vor dem Logo: Er ist das,
               was in jedem Browsertab, in jedem Mailbetreff und unter jeder
               Grussformel steht — das Logo sieht man nur auf Seiten dieser
               Anwendung. */ ?>
      <?php ui_karte_start(['titel' => 'Name', 'id' => 'k-name',
                            'zahl' => 'Wie diese Installation heißt']); ?>
        <?php if ($nameMeldung !== null): ?>
          <?= ui_meldung_markup($nameMeldung[0], $nameMeldung[1]) ?>
        <?php endif; ?>

        <p class="feld-hinweis">Der Name gilt für Browsertab, Kopfleiste,
          Anmeldeseite, Wartungsseite, Schlüsselblatt <strong>und jede
          E-Mail</strong> dieser Installation.</p>

        <form method="post" class="listen-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="instanz_name">
          <?php ui_feld(['name' => 'instanz_name', 'label' => 'Name',
                         'label_zusatz' => 'in E-Mails und auf dem Schlüsselblatt',
                         'wert' => instanz_name(),
                         'platzhalter' => INSTANZ_NAME_VORGABE,
                         'klein' => 'Höchstens ' . INSTANZ_MAX . ' Zeichen ohne '
                                  . 'Zeilenumbruch; leer heißt Vorgabe.']); ?>
          <?php ui_feld(['name' => 'instanz_kurz', 'label' => 'Kurzname',
                         'label_zusatz' => 'im Browsertab und in der Kopfleiste',
                         'wert' => instanz_kurz(),
                         'platzhalter' => INSTANZ_KURZ_VORGABE]); ?>
          <?= ui_knopf(['text' => 'Namen speichern', 'art' => 'primaer']) ?>
        </form>
      <?php ui_karte_ende(); ?>

      <?php /* DIE ADRESSEN STEHEN NEBEN DEM NAMEN, weil sie dasselbe Problem
               hatten: eine persoenliche Adresse, fest im Quelltext, in jeder
               Mail einer fremden Installation. */ ?>
      <?php ui_karte_start(['titel' => 'Adressen', 'id' => 'k-adressen',
                            'zahl' => 'Wohin Fragen und Betriebspost gehen']); ?>
        <?php if ($adrMeldung !== null): ?>
          <?= ui_meldung_markup($adrMeldung[0], $adrMeldung[1]) ?>
        <?php endif; ?>

        <p class="feld-hinweis">Beide dürfen leer bleiben — dann fällt die
          Kontaktzeile aus den Mails, und Betriebspost geht an alle mit
          Verwaltungsrecht.
          <a href="hilfe.php#11-5-installation">Handbuch: Installation</a></p>

        <form method="post" class="listen-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="instanz_adressen">
          <?php ui_feld(['name' => 'instanz_kontakt', 'label' => 'Kontaktadresse',
                         'art' => 'email',
                         'label_zusatz' => 'steht in jeder E-Mail an NutzerInnen',
                         'wert' => instanz_kontakt(),
                         'platzhalter' => 'leer = keine Kontaktzeile',
                         'klein' => 'Nicht der Absender — der steht als smtp.from '
                                  . 'in der config.php.']); ?>
          <?php ui_feld(['name' => 'betrieb_mail', 'label' => 'Betreiberadresse',
                         'art' => 'email',
                         'label_zusatz' => 'Warnungen zu Speicher und Sicherungen',
                         'wert' => betrieb_mail(),
                         'platzhalter' => 'leer = an alle mit Verwaltungsrecht']); ?>
          <?= ui_knopf(['text' => 'Adressen speichern', 'art' => 'primaer']) ?>
        </form>
      <?php ui_karte_ende(); ?>

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
          <p class="feld-hinweis">Kopfleiste, Browser-Symbol und Anmeldeseite —
             im Profil kann jede NutzerIn für sich abweichen.</p>
        </div>

        <form method="post">
          <?= csrf_field() ?><input type="hidden" name="action" value="logo_standard">
          <?php ui_segment(['name' => 'logo', 'id' => 'logo-standard',
                            'wert' => logo_standard(),
                            'optionen' => INSTALLATION_LOGOS]); ?>
          <?php $platzhalter = logo_platzhalter_liegt(); ?>
          <?php if ($platzhalter): ?>
            <?php /* EINE MELDUNG, KEIN KARTENTEXT (P5c/AP9): Der Hinweis nennt
                     einen Zustand, der von selbst verschwindet, sobald die
                     echten Dateien liegen — das ist, was `.meldung` sagt. */ ?>
            <?= ui_meldung_markup('info', 'Die echte Datei ersetzt ihn 1:1 — '
                . 'gleicher Name, gleiche Maße. Betroffen: '
                . implode(', ', $platzhalter) . '.',
                'Das Fahrzeug-Logo ist ein Platzhalter.') ?>
          <?php endif; ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Standard speichern', 'symbol' => 'haken',
                          'art' => 'primaer']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
    </div><?php /* .form-spalte */ ?>
  </div><?php /* .form-raster */ ?>

<?php ui_geruest_ende(); ?>
<?php /* KEINE SEITENSKRIPTE MEHR (P5c/AP9): `forms.js` hing an der
         Speichern-Leiste der Rechtstexte, `kopieren.js` an deren
         Textbausteinen — beide sind mit auf die Seite „Rechtstexte"
         gezogen. Name, Adressen und Logo sind schlichte Formulare. */ ?>
<?php ui_seite_ende(); ?>
