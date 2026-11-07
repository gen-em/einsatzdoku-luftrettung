<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
/* VOR dem POST-Zweig und nicht danach: Der Protokolleintrag entsteht beim
 * Speichern, und `function_exists('protokoll')` waere weiter unten `false`
 * gewesen — der Eintrag waere still ausgefallen. */
require_once __DIR__ . '/protokoll_lib.php';

/**
 * INSTALLATION — wie diese Anlage nach aussen auftritt.
 *
 * DIESE SEITE BEANTWORTET EINE FRAGE: Was zeigt diese Installation Menschen,
 * die noch nicht angemeldet sind? Name, Adressen und Logo — jede Karte mit
 * ihrem eigenen Knopf, weil jede eine Wahl ist, die sofort wirkt. Der
 * Logo-Standard ist Gestaltung, keine Wartung. Die Rechtstexte stehen auf
 * einer eigenen Seite, `admin_rechtstexte.php`, mit Reitern und einer
 * Vorschau, die beim Tippen mitläuft.
 *
 * KEIN PLATZHALTER fuer kuenftige Karten: Ein Kasten mit „kommt spaeter" ist
 * eine Zusage, die niemand gegeben hat.
 *
 */

/* Die drei Wahlmoeglichkeiten des Installationsstandards. „wechselnd" ist
 * auch hier waehlbar — eine Installation, die beide
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

/* UMLEITEN NACH DEM POST. Was gespeichert hat,
 * endet mit `flash_setzen()` und einer Umleitung in die Karte, die gehandelt
 * hat — Ort und Anker sind ihre `id`. Sonst wiederholt Neuladen das
 * Speichern. Ein abgewiesener Wert bleibt auf der Seite. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    if (($_POST['action'] ?? '') === 'instanz_name') {
        /* DER NAME DIESER INSTALLATION. Eigenes
         * „Speichern", wie bei Logo und Adresssuche: Ein Tippfehler im Namen
         * soll die Rechtstexte nicht mit abweisen und umgekehrt. */
        [$ok, $meldung] = instanz_namen_setzen(post_text('instanz_name'),
                                               post_text('instanz_kurz'));
        if ($ok) {
            flash_setzen('ok', $meldung, 'k-name');
            header('Location: admin_installation.php#k-name');
            exit;
        }
        $nameMeldung = ['fehler', $meldung];
    } elseif (($_POST['action'] ?? '') === 'instanz_adressen') {
        /* DIE ADRESSEN DIESER INSTALLATION. Eigenes
         * „Speichern" wie beim Namen, und aus demselben Grund. */
        [$ok, $meldung] = instanz_adressen_setzen(post_text('instanz_kontakt'),
                                                  post_text('betrieb_mail'));
        if ($ok) {
            flash_setzen('ok', $meldung, 'k-adressen');
            header('Location: admin_installation.php#k-adressen');
            exit;
        }
        $adrMeldung = ['fehler', $meldung];
    } elseif (($_POST['action'] ?? '') === 'logo_standard') {
        $wahl = post_text('logo');
        if (!isset(INSTALLATION_LOGOS[$wahl])) {
            $logoMeldung = ['fehler', 'Unbekannte Logo-Wahl — es wurde nichts geändert.'];
        } elseif (!app_state_setzen('logo_standard', $wahl)) {
            /* Nr. 345: Bis Web 23.6.2 stand hier „Standard der Installation:
             * …" auch dann, wenn das Schreiben scheiterte. */
            $logoMeldung = ['fehler', 'Der Logo-Standard ließ sich nicht speichern; '
                . 'es wurde nichts geändert. Die Einzelheiten stehen im Protokoll unter System.'];
        } else {
            flash_setzen('ok', 'Standard der Installation: ' . INSTALLATION_LOGOS[$wahl]
                . ($wahl === 'wechselnd'
                   ? '. Je Anmeldung wird neu gewürfelt — innerhalb einer Sitzung '
                     . 'bleibt das Logo stehen.'
                   : '.')
                . ' Wer im Profil keine eigene Wahl getroffen hat, sieht das ab sofort.', 'k-logo');
            header('Location: admin_installation.php#k-logo');
            exit;
        }
    }
}

// Meldung aus der Umleitung in ihre Karte legen — oder nach oben
$flash = flash_holen();
if ($flash !== null) {
    $paar = [$flash['ton'], $flash['text']];
    if ($flash['ort'] === 'k-name')         { $nameMeldung = $paar; }
    elseif ($flash['ort'] === 'k-adressen') { $adrMeldung = $paar; }
    elseif ($flash['ort'] === 'k-logo')     { $logoMeldung = $paar; }
    elseif ($flash['ton'] === 'error')      { $error = $flash['text']; }
    else                                    { $notice = $flash['text']; }
}

ui_seite_start(['titel' => 'Installation']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_installation']); ?>

  <?php ui_titelzeile(['titel' => 'Installation',
                       'unter' => 'Wie diese Installation nach außen auftritt — '
                                . '<a href="hilfe.php#11-5-installation">Handbuch: Installation</a>']); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <?php /* EINE SPALTE. Drei Karten — nach Design.md 9.26 kein Fall für zwei
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
                         'label_zusatz' => 'im Browsertab, in der Kopfleiste und auf der Anmeldeseite',
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
                     ? 'nadoku-nef' : 'nadoku-hubschrauber'; ?>
        <?php $masse = ui_logo_masse(34); ?>
        <div class="logo-vorschau">
          <div class="logo-kachel">
            <img src="<?= e(ui_asset('assets/images/' . $stamm . '-farbig-negativ.svg')) ?>"
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
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Standard speichern', 'symbol' => 'haken',
                          'art' => 'primaer']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
    </div><?php /* .form-spalte */ ?>
  </div><?php /* .form-raster */ ?>

<?php ui_geruest_ende(); ?>
<?php /* KEINE SEITENSKRIPTE: Name, Adressen und Logo sind schlichte
         Formulare. */ ?>
<?php ui_seite_ende(); ?>
