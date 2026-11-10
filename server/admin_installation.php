<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
/* VOR dem POST-Zweig und nicht danach: Der Protokolleintrag entsteht beim
 * Speichern, und `function_exists('protokoll')` waere weiter unten `false`
 * gewesen — der Eintrag waere still ausgefallen. */
require_once __DIR__ . '/protokoll_lib.php';
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/konten_einstellungen_lib.php';

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
 * DAS DEMO-KONTO STEHT HIER (Konzept OF, OF-05, E-OF-08, -46), weil es
 * dieselbe Frage beantwortet: Es ist das, was die Installation Fremden zum
 * Ausprobieren zeigt. Bis Web 24.5 hatte es eine eigene Seite
 * (`admin_demo.php`) und seinen Schalter in den Servereinstellungen — zwei
 * Orte fuer eine Sache. Die Kontrollzahlen des Resets bleiben bis OF-06 auf
 * `admin_demo.php` (E-OF-71).
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
$demoMeldung = null;
$bericht     = null;

/* UMLEITEN NACH DEM POST. Was gespeichert hat,
 * endet mit `flash_setzen()` und einer Umleitung in die Karte, die gehandelt
 * hat — Ort und Anker sind ihre `id`. Sonst wiederholt Neuladen das
 * Speichern. Ein abgewiesener Wert bleibt auf der Seite. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* DIE ADRESSEN NUR DURCH DIE BETREIBERIN (seit Web 24.12.0, KA-03,
     * F-KA-20) — das Rollentor vor dem Formular-Token, wie ueberall. */
    if (($_POST['action'] ?? '') === 'instanz_adressen') { require_betreiberin(); }
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
         * „Speichern" wie beim Namen, und aus demselben Grund.
         *
         * NUR DIE BETREIBERIN, MIT PROTOKOLL UND MAIL AN DIE ALTE ADRESSE
         * (seit Web 24.12.0, KA-03, F-KA-20). Bis dahin aenderte jeder Admin
         * beide ohne Spur — und Sammelmails ueber auffaellige
         * Anmeldeversuche gingen danach an seine Adresse. */
        $altKontakt = instanz_kontakt();
        $altBetrieb = betrieb_mail();
        [$ok, $meldung] = instanz_adressen_setzen(post_text('instanz_kontakt'),
                                                  post_text('betrieb_mail'));
        if ($ok) {
            /* AUS DER TABELLE, NICHT AUS `instanz_kontakt()`: Die Funktion
             * merkt sich ihren Wert je Anfrage und nennte hier den alten. */
            $neuKontakt = trim((string)(app_state_lesen('instanz_kontakt') ?? ''));
            $neuBetrieb = trim((string)(app_state_lesen('betrieb_mail') ?? ''));
            $geaendert = [];
            if ($neuKontakt !== $altKontakt) { $geaendert[] = ['Kontaktadresse', $altKontakt, $neuKontakt]; }
            if ($neuBetrieb !== $altBetrieb) { $geaendert[] = ['Betreiberadresse', $altBetrieb, $neuBetrieb]; }
            if ($geaendert !== []) {
                protokoll('verwaltung', 'installation_adressen',
                    implode('; ', array_map(static fn(array $g): string =>
                        $g[0] . ': ' . ($g[1] !== '' ? $g[1] : '(leer)') . ' → ' . ($g[2] !== '' ? $g[2] : '(leer)'),
                        $geaendert)),
                    ['felder' => array_column($geaendert, 0)]);
                require_once __DIR__ . '/mail_lib.php';
                foreach ($geaendert as [$was, $alt, $neu]) {
                    if ($alt === '') { continue; }
                    mail_einreihen('installation_adresse', $alt, [
                        'kern' => 'die ' . $was . " dieser Installation wurde geändert —\n"
                                . "diese Adresse gilt dafür nicht mehr.\n\n"
                                . "War das nicht beabsichtigt, bitte die BetreiberIn fragen;\n"
                                . 'die Änderung steht im Protokoll (Reiter Verwaltung).']);
                }
            }
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
    } elseif (($_POST['action'] ?? '') === 'demo_anmeldung') {
        /* DER EINE SCHALTER (E-OF-46). „Aus" sperrt nur die Anmeldung; Konto
         * und Daten bleiben, und der Reset ruht von selbst — ihn loest nur
         * eine Anfrage des Demo-Kontos aus (`demo_reset_wenn_faellig()`). Die
         * Rueckfrage beim Wechsel auf „nur auf Einladung" bleibt in den
         * Servereinstellungen und schaltet denselben Wert. */
        $an = !empty($_POST['demo_anmeldung']);
        if ($an !== konten_demo_anmeldung_an()) {
            app_state_setzen(KONTEN_K_DEMO_ANMELDUNG, $an ? '1' : '0');
            konten_einstellungen_neu_lesen();
            protokoll('verwaltung', 'einstellungen_konten',
                      'Demo-Anmeldung ' . ($an ? 'zugelassen' : 'abgeschaltet'),
                      ['geaendert' => [KONTEN_K_DEMO_ANMELDUNG]]);
        }
        flash_setzen('ok', $an ? 'Die Demo-Anmeldung ist zugelassen.'
                               : 'Die Demo-Anmeldung ist abgeschaltet; Konto und Daten bleiben.',
                     'k-demo');
        header('Location: admin_installation.php#k-demo');
        exit;
    } elseif (in_array($_POST['action'] ?? '', ['demo_anlegen', 'demo_reset', 'demo_entfernen'], true)) {
        /* ANLEGEN, ZURUECKSETZEN, ENTFERNEN — bis Web 24.5 auf
         * `admin_demo.php`, Wortlaut und Fehlerbehandlung von dort. */
        $aktion = post_text('action');
        $ok = null;
        try {
            if ($aktion === 'demo_anlegen') {
                $bericht = demo_anlegen();
                $ok = 'Demo-Konto angelegt und Standardzustand eingespielt.';
            } elseif ($aktion === 'demo_reset') {
                $bericht = demo_zuruecksetzen();
                $ok = 'Demo-Konto auf den Standardzustand zurückgesetzt.';
                /* NUR DER RESET VON HAND. Der selbsttaetige schriebe bis zu
                 * 48 Eintraege am Tag in den Reiter mit der langen Frist. */
                protokoll('verwaltung', 'demo_zurueckgesetzt',
                          'Demo-Konto von Hand auf den Standardzustand zurückgesetzt');
            } elseif (($_POST['confirm'] ?? '') === 'ja') {
                $r = demo_entfernen();
                if ($r['ok']) { $ok = 'Demo-Konto entfernt.'; }
                else          { $demoMeldung = ['fehler', $r['grund']]; }
            }
        } catch (Throwable $ex) {
            /* PDOException ERBT von RuntimeException — sie zuerst, sonst kaeme
             * jeder Datenbankfehler im Wortlaut in die Seite. Saetze, die
             * demo_lib.php FUER die Verwaltung wirft, gehen woertlich durch. */
            $kennung = null;
            if ($ex instanceof PDOException) {
                $kennung = fehler_kennung($ex, 'admin_demo');
                $text = ist_dublettenfehler($ex)
                    ? 'Der Vorgang ist fehlgeschlagen, weil ein Wert aus der Fixture '
                      . 'auf diesem Server bereits vergeben ist — am ehesten eine '
                      . 'Gerätekennung. Es wurde nichts geändert. Einzelheiten stehen '
                      . 'im Protokoll unter der Kennung ' . $kennung . '.'
                    : 'Der Vorgang ist an der Datenbank gescheitert; es wurde nichts '
                      . 'geändert. Einzelheiten stehen im Protokoll unter der '
                      . 'Kennung ' . $kennung . '.';
            } elseif ($ex instanceof RuntimeException) {
                $text = $ex->getMessage();
            } else {
                $kennung = fehler_kennung($ex, 'admin_demo');
                $text = 'Der Vorgang ist fehlgeschlagen; es wurde nichts geändert. '
                      . 'Einzelheiten stehen im Protokoll unter der Kennung ' . $kennung . '.';
            }
            /* DAS ENTFERNEN IST KEINE TRANSAKTION (`konto_loeschen()` raeumt
             * Backups und Spuren vor der Kontozeile) — „nichts geaendert"
             * waere falsch; ein zweites „Entfernen" fuehrt es zu Ende. */
            if ($aktion === 'demo_entfernen') {
                $kennung ??= fehler_kennung($ex, 'admin_demo');
                flash_setzen('error', 'Das Entfernen ist mittendrin gescheitert — ein Teil '
                    . 'des Demo-Kontos kann schon gelöscht sein. Ein zweites „Entfernen" '
                    . 'führt es zu Ende. Einzelheiten stehen im Protokoll unter der Kennung '
                    . $kennung . '.', 'k-demo');
                header('Location: admin_installation.php#k-demo');
                exit;
            }
            $demoMeldung = ['fehler', $text];
        }
        if ($ok !== null) {
            flash_setzen('ok', $ok, 'k-demo', $bericht !== null ? ['bericht' => $bericht] : []);
            header('Location: admin_installation.php#k-demo');
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
    elseif ($flash['ort'] === 'k-demo')     { $demoMeldung = $paar; }
    elseif ($flash['ton'] === 'error')      { $error = $flash['text']; }
    else                                    { $notice = $flash['text']; }
    if (is_array($flash['daten']['bericht'] ?? null)) { $bericht = $flash['daten']['bericht']; }
}

$demoId    = demo_id();
$demoAn    = konten_demo_anmeldung_an();
$fixtureDa = demo_fixture_vorhanden();
$demoMail  = null;
if ($demoId !== null) {
    $st = db()->prepare('SELECT email FROM users WHERE id = ?');
    $st->execute([$demoId]);
    $demoMail = (string)$st->fetchColumn();
}

ui_seite_start(['titel' => 'Installation']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_installation']); ?>

  <?php ui_titelzeile(['titel' => 'Installation',
                       'unter' => 'Wie diese Installation nach außen auftritt']); ?>

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
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Namen speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
          </div>
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
          Verwaltungsrecht.</p>

        <?php if (!ist_betreiberin()): ?>
          <p class="feld-hinweis">Ändern kann diese Adressen nur die BetreiberIn.</p>
        <?php endif; ?>
        <form method="post" class="listen-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="instanz_adressen">
          <?php ui_feld(['name' => 'instanz_kontakt', 'label' => 'Kontaktadresse',
                         'art' => 'email',
                         'label_zusatz' => 'steht in jeder E-Mail an NutzerInnen',
                         'wert' => instanz_kontakt(),
                         'attr' => ist_betreiberin() ? '' : ' disabled',
                         'platzhalter' => 'leer = keine Kontaktzeile',
                         'klein' => 'Nicht der Absender — der steht als smtp.from '
                                  . 'in der config.php.']); ?>
          <?php ui_feld(['name' => 'betrieb_mail', 'label' => 'Betreiberadresse',
                         'art' => 'email',
                         'label_zusatz' => 'Warnungen zu Speicher und Sicherungen',
                         'wert' => betrieb_mail(),
                         'attr' => ist_betreiberin() ? '' : ' disabled',
                         'platzhalter' => 'leer = an alle mit Verwaltungsrecht']); ?>
          <div class="listen-form-fuss">
            <?php if (ist_betreiberin()): ?>
            <?= ui_knopf(['text' => 'Adressen speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
            <?php endif; ?>
          </div>
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

      <?php /* ---- Demo-Konto (E-OF-08, -46) ------------------------------
               Die Formulare der Handlungen liegen ausserhalb der Karte und
               werden ueber `form="…"` bedient — die Kopfaktion ist ein
               Absendeknopf (ui_karte_start, `aktion.form`). KEINE RUECKFRAGE
               VOR DEM ZURUECKSETZEN: Der Verlust ist der Zweck, und
               dasselbe geschieht 30 Minuten nach jeder ersten Aenderung von
               selbst. „Entfernen" ist endgueltig und fragt. */ ?>
      <form method="post" id="f-demo-anlegen" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_anlegen">
      </form>
      <form method="post" id="f-demo-reset" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_reset">
      </form>
      <form method="post" id="f-demo-weg" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_entfernen">
        <input type="hidden" name="confirm" value="ja">
      </form>
      <?php $gesperrt = $fixtureDa ? '' : ' disabled';
      ui_karte_start(['titel' => 'Demo-Konto', 'id' => 'k-demo',
          'zahl' => 'Ein Konto zum Ausprobieren',
          'aktion' => $demoId === null
              ? ['text' => 'Demo-Konto anlegen', 'symbol' => 'plus',
                 'form' => 'f-demo-anlegen', 'attr' => $gesperrt]
              : ['text' => 'Auf Standard zurücksetzen', 'symbol' => 'tausch',
                 'form' => 'f-demo-reset', 'attr' => $gesperrt]]); ?>
        <?php if ($demoMeldung !== null): ?>
          <?= ui_meldung_markup($demoMeldung[0], $demoMeldung[1]) ?>
        <?php endif; ?>
        <?php /* DIE WARNUNG GILT, SOLANGE MAN SICH ANMELDEN KANN — sie ist der
                 einzige Ort im Projekt, an dem die Ende-zu-Ende-Verschluesselung
                 bewusst ausgesetzt ist. „Aus" nimmt sie weg (E-OF-08). */ ?>
        <?php if ($demoId !== null && $demoAn): ?>
          <?= ui_meldung_markup('warn',
              'In diesem Konto liegt das Schlüsselmaterial auf dem Server — '
            . 'niemals echte Daten darin erfassen.',
              'Schlüsselmaterial auf dem Server') ?>
        <?php endif; ?>
        <?php if (!$fixtureDa): ?>
          <?= ui_meldung_markup('fehler',
              'Es liegt keine Fixture unter server/demo/fixture.json.gz; sie entsteht mit '
            . '„php tools/referenzdatensatz/fixture/erzeugen.php".',
              'Keine Fixture') ?>
        <?php endif; ?>

        <p class="feld-hinweis">Es setzt sich 30 Minuten nach der ersten Änderung
           selbst auf den Standardzustand zurück, ohne Änderung einmal am Tag.</p>

        <?php if ($demoId === null): ?>
          <?php ui_zeile(['text' => 'Konto', 'klein' => 'noch keines angelegt']); ?>
        <?php else:
          $letzter = demo_letzter_reset();
          $restSek = demo_reset_in();
          ui_zeile(['text' => 'Konto', 'klein' => (string)$demoMail,
                    'aktionen' => ui_knopf(['text' => 'Entfernen', 'art' => 'leise',
                        'attr' => ' form="f-demo-weg" data-confirm="Demo-Konto samt allen Daten '
                                . 'endgültig entfernen?" data-confirm-ok="Entfernen"'])]);
          ui_zeile(['text' => 'Letzter Reset',
                    'klein' => $letzter > 0 ? fmt_local(gmdate('Y-m-d H:i:s', $letzter)) : 'unbekannt']);
          ui_zeile(['text' => 'Nächster Reset',
                    'klein' => !$demoAn ? 'ruht — nur ein Upload eines Demo-Geräts löst ihn noch aus'
                        : ($restSek <= 0 ? 'bei der nächsten Anfrage'
                        : ($restSek < 3600 ? 'in ' . (int)ceil($restSek / 60) . ' Minuten'
                                           : 'ab ' . datum_zeit_text(gmdate('Y-m-d H:i:s', time() + $restSek))))]);
        endif; ?>

        <form method="post" class="listen-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="demo_anmeldung">
          <?php ui_schalter(['name' => 'demo_anmeldung', 'label' => 'Demo-Anmeldung zulassen',
              'an' => $demoAn,
              'klein' => 'Aus heißt: Die Demo-Adresse gilt bei der Anmeldung als unbekannt; '
                       . 'Konto und Daten bleiben.']); ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>

      <?php /* DER BERICHT DES LETZTEN LAUFS — ein Messwert, nur nach einem
               Anlegen oder Zuruecksetzen, zugeklappt. */ ?>
      <?php if ($bericht !== null): ?>
        <?php ui_karte_start(['titel' => 'Bericht des letzten Laufs', 'id' => 'k-demo-bericht',
                              'zu' => true, 'stand' => 'Zahlen des Resets']); ?>
          <pre><?= e(json_encode($bericht,
              JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
        <?php ui_karte_ende(true); ?>
      <?php endif; ?>
    </div><?php /* .form-spalte */ ?>
  </div><?php /* .form-raster */ ?>

<?php ui_geruest_ende(); ?>
<?php /* KEINE SEITENSKRIPTE: Name, Adressen und Logo sind schlichte
         Formulare. */ ?>
<?php ui_seite_ende(); ?>
