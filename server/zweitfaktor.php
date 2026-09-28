<?php
declare(strict_types=1);

/**
 * DAS EINRICHTUNGSTOR DES ZWEITFAKTORS (P5c/AP5, E-P5c-53, -61; M-P5c-02b
 * Bild 4).
 *
 * Support, Admin und BetreiberIn ohne Zweitfaktor landen hier, bevor eine
 * andere Seite aufgeht — das Tor dazu steht in `auth_guard.php`, und diese
 * Seite ist seine einzige Ausnahme neben dem Abmelden.
 *
 * IN DER ANMELDEHÜLLE, NICHT IM GERÜST (E-P5c-61). Das Gerüst zeigte eine
 * Leiste, deren Einträge alle wieder hierher führten.
 *
 * ZWEI SCHRITTE, EINE ADRESSE:
 *   1. GET: Geheimnis zeigen (QR, Verweis, Base32) und nach einem Code
 *      fragen. Eingeschaltet wird erst mit einem bestätigten Code
 *      (E-P5c-54) — wer das Scannen abbricht, sperrt sich nicht aus.
 *   2. POST mit gültigem Code: eingeschaltet, die zehn Codes einmal
 *      sichtbar, „Weiter" nach dem Haken.
 *
 * DASSELBE GEHEIMNIS BEIM NEULADEN. Eine angefangene Einrichtung behält ihr
 * Geheimnis, bis sie abgeschlossen ist. Zöge jedes Neuladen ein neues, passte
 * die App, die eben gescannt hat, zu keinem Code mehr — und niemand sähe,
 * warum.
 *
 * WER HIER NICHTS ZU TUN HAT — keine Pflichtrolle, schon eingeschaltet,
 * Spalten noch nicht da —, geht zur Startseite.
 *
 * SEIT WEB 21.9.0 EIN ZWEITER MODUS: DIE BESTAETIGUNG (Schritt 18, SR-07,
 * E-SR-20). `?bestaetigen=1&zurueck=…` fragt nach einem Code — aus der App
 * oder einem Wiederherstellungscode, im Topf `totp` wie der Code-Schritt der
 * Anmeldung — und macht ihn damit frisch (`ZF_FRISCH_S`). Dahin schickt
 * `zweitfaktor_frisch_verlangen()` jede Handlung der Liste in `db.php`, wenn
 * der letzte Code aelter ist. Zurueck geht es nur auf eine Seite der Liste
 * (`zweitfaktor_zurueck()`); „Abbrechen" fuehrt dorthin ohne Frist. Die Seite
 * steht in `WARTUNG_AUSNAHMEN`: Die BetreiberIn braucht sie vor den
 * Schluesselgriffen, und die liegen im Wartungsmodus offen.
 */

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/totp_lib.php';
require_once __DIR__ . '/zweitfaktor_teile.php';

/* ---- DIE BESTAETIGUNG (SR-07) ---------------------------------------------
 *
 * SCHON FRISCH ODER KEIN ZWEITFAKTOR: nichts zu fragen, gleich zurueck.
 * GESPERRT (fuenf falsche Codes im Topf `totp`): Die Seite sagt bis wann und
 * bietet nur den Rueckweg — die Anmeldung endet deshalb nicht, anders als im
 * Code-Schritt, weil die Sitzung schon mit einem Code entstanden ist. */
if (isset($_GET['bestaetigen'])) {
    require_once __DIR__ . '/ratelimit_lib.php';
    require_once __DIR__ . '/format_lib.php';   // fmt_local() fuer die Sperrzeit
    $zurueck  = zweitfaktor_zurueck((string)($_GET['zurueck'] ?? ''));
    $nochmal  = isset($_GET['nochmal']);
    /* „Abbrechen" fuehrt auf `zurueck` — ausser die Handlung ist eine Seite
     * (das Blatt): Dort hiesse zurueck wieder hierher. */
    $abbruch  = isset($_GET['abbruch']) ? zweitfaktor_zurueck((string)$_GET['abbruch']) : $zurueck;
    $mitRc    = ($_GET['art'] ?? '') === 'rc';
    if (zweitfaktor_frisch()) { header('Location: ' . $zurueck, true, 303); exit; }
    $merkmale = [rate_merkmal_kennung((string)($row['email'] ?? ''))];
    $bFehler = null; $bAuftakt = '';
    $gesperrt = !rate_erlaubt('totp', null, $merkmale);
    require_once __DIR__ . '/passkey_lib.php';
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$gesperrt) {
        csrf_check();
        /* DER PASSKEY ZAEHLT WIE EIN CODE (SR-09, E-SR-32): Antwort im Feld
         * `passkey_antwort`, Herausforderung in `passkey_best` (einmal). */
        $mitPk = !$mitRc && ($_POST['passkey_antwort'] ?? '') !== '';
        if ($mitPk) {
            $pkAblage = is_array($_SESSION['passkey_best'] ?? null) ? $_SESSION['passkey_best'] : [];
            unset($_SESSION['passkey_best']);
            $pkAntwort = json_decode((string)$_POST['passkey_antwort'], true);
            $pkR = pk_anmeldung_pruefen($userId, $pkAblage, is_array($pkAntwort) ? $pkAntwort : []);
            $pr = $pkR['ok'] ? ['ok' => true, 'art' => 'passkey'] : ['ok' => false, 'art' => null, 'passkey' => true];
        } else {
            $pr = totp_anmeldung_pruefen($userId, (string)($_POST[$mitRc ? 'rc' : 'code'] ?? ''),
                                         $mitRc ? 'code' : 'app');
        }
        if ($pr['ok']) {
            rate_erfolg('totp', null, $merkmale);
            $_SESSION['zf_frisch_bis'] = time() + ZF_FRISCH_S;
            if ($pr['art'] === 'code') {
                require_once __DIR__ . '/protokoll_lib.php';
                protokoll('verwaltung', 'totp_code_benutzt',
                          'Mit einem Wiederherstellungscode bestätigt',
                          ['codes_offen' => (int)($pr['codes_offen'] ?? 0)], $userId);
            }
            /* NACH EINEM POST: Die Handlung wurde nicht ausgefuehrt und wird
             * nicht nachgespielt (E-SR-20) — die Seite sagt es in der Karte,
             * aus der sie kam. Nach einer Seite (dem Schluesselblatt) gibt es
             * nichts zu wiederholen; der Ruecksprung zeigt sie. */
            if ($nochmal) {
                $ort = str_contains($zurueck, '#') ? substr($zurueck, strpos($zurueck, '#') + 1) : '';
                flash_setzen('info', 'Code bestätigt — bitte die Handlung noch einmal auslösen.', $ort);
            }
            header('Location: ' . $zurueck, true, 303);
            exit;
        }
        rate_misserfolg('totp', null, $merkmale);
        $gesperrt = !rate_erlaubt('totp', null, $merkmale);
        if (!$gesperrt && !empty($pr['passkey'])) {
            $bAuftakt = 'Der Passkey wurde nicht angenommen.';
            $bFehler  = 'Nimm den Code aus der App — oder versuche es noch einmal.';
        } elseif (!$gesperrt && !empty($pr['geheimnis_fehlt'])) {
            $bAuftakt = 'Der Code lässt sich hier nicht prüfen.';
            $bFehler  = 'Der Zweitfaktor wurde mit einem anderen Serverschlüssel eingerichtet. '
                      . 'Nimm einen Wiederherstellungscode.';
        } elseif (!$gesperrt) {
            $bAuftakt = 'Der Code passt nicht.';
            $bFehler  = $mitRc ? 'Jeder Wiederherstellungscode gilt einmal — ein benutzter ist verbraucht.'
                               : 'Er gilt 30 Sekunden — den nächsten aus der App nehmen.';
        }
    }
    if ($gesperrt) {
        $sp = rate_sperre('totp', null, $merkmale);
        $bAuftakt = 'Zu viele falsche Codes für dieses Konto.';
        $bFehler  = ($sp['bis'] ?? null) !== null
                  ? 'Wieder ab ' . fmt_local($sp['bis'], 'H:i') . ' Uhr. Bis dahin geht diese Handlung nicht.'
                  : 'Bitte später erneut versuchen.';
    }
    $hier = 'zweitfaktor.php?bestaetigen=1&zurueck=' . rawurlencode($zurueck) . ($nochmal ? '&nochmal=1' : '')
          . (isset($_GET['abbruch']) ? '&abbruch=' . rawurlencode($abbruch) : '');
    /* Der Passkey als Weg daneben — wie im Code-Schritt (E-SR-32). */
    $pkBest = null;
    if (!$gesperrt && !$mitRc && pk_verfuegbar() && pk_zahl($userId) > 0) {
        $pkAblage = [];
        pk_herausforderung_stellen($pkAblage, 300);
        $_SESSION['passkey_best'] = $pkAblage;
        $pkBest = ['herausforderung' => $pkAblage['herausforderung'], 'rp_id' => pk_ursprung()['rp_id'],
                   'kennungen' => pk_kennungen($userId)];
    }

    ui_seite_start(['titel' => 'Code bestätigen', 'klasse' => 'anmeldung-body']);
?>
<main class="anmeldung">
 <?php ui_hinweise(); ?>
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel"><?= e(instanz_kurz()) ?></h1>
  <p class="anmeldung-unter">Einsatzdokumentation Notarzt</p>
  <h2 class="anmeldung-schritt">Code bestätigen</h2>
  <?php if ($gesperrt): ?>
  <?php ui_meldung(null, $bFehler, 'info', '  ', ['auftakt_fehler' => $bAuftakt]); ?>
  <?php else: ?>
  <form method="post" action="<?= e($hier . ($mitRc ? '&art=rc' : '')) ?>" id="codeform">
    <?= csrf_field() ?>
    <p class="feld-hinweis">Für diese Handlung fragt NAdoku noch einmal nach dem Code —
       danach gilt er <?= (int)(ZF_FRISCH_S / 60) ?> Minuten lang auch für die übrigen.</p>
    <?php ui_meldung(null, $bFehler, 'info', '    ', ['auftakt_fehler' => $bAuftakt]); ?>
    <?php if ($pkBest !== null): ?>
    <div data-passkey-bestaetigen hidden data-pk-formular="passkeyform"
         data-pk-herausforderung="<?= e($pkBest['herausforderung']) ?>"
         data-pk-rp-id="<?= e($pkBest['rp_id']) ?>"
         data-pk-kennungen="<?= e((string)json_encode($pkBest['kennungen'])) ?>">
      <?= ui_knopf(['text' => 'Mit Passkey bestätigen', 'art' => 'neutral', 'breit' => true,
                    'typ' => 'button', 'attr' => ' data-passkey-knopf']) ?>
      <div data-passkey-zustand></div>
      <p class="feld-klein">oder der Code aus der App:</p>
    </div>
    <?php endif; ?>
    <?php if ($mitRc) {
        ui_feld(['name' => 'rc', 'label' => 'Wiederherstellungscode', 'klasse' => 'feld-code',
                 'platzhalter' => 'XXXX XXXX',
                 'attr' => ' autocomplete="off" autocapitalize="characters" spellcheck="false" autofocus required',
                 'klein' => 'Jeder Code gilt einmal.']);
    } else {
        ui_feld(['name' => 'code', 'label' => 'Code aus der App', 'klasse' => 'feld-code',
                 'platzhalter' => '000 000',
                 'attr' => ' inputmode="numeric" autocomplete="one-time-code" autofocus required']);
    } ?>
    <div class="listen-form-fuss">
      <?= ui_knopf(['text' => 'Bestätigen', 'art' => 'primaer', 'breit' => true]) ?>
    </div>
  </form>
  <?php if ($pkBest !== null): ?>
  <form method="post" action="<?= e($hier) ?>" id="passkeyform" hidden>
    <?= csrf_field() ?><input type="hidden" name="passkey_antwort" value="">
  </form>
  <?php endif; ?>
  <p class="anmeldung-neben"><a href="<?= e($mitRc ? $hier : $hier . '&art=rc') ?>"><?=
      $mitRc ? 'Code aus der App verwenden' : 'Wiederherstellungscode verwenden' ?></a></p>
  <?php endif; ?>
  <p class="anmeldung-neben"><a href="<?= e($abbruch) ?>">Abbrechen</a></p>
 </div>

 <nav class="fuss-anmeldung" aria-label="Über diese Anwendung">
   <a href="ueber.php">Was ist NAdoku?</a>
   <a href="hilfe.php">Handbuch</a>
   <a href="impressum.php">Impressum</a>
   <a href="datenschutz.php">Datenschutz</a>
 </nav>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php ui_seite_ende(['skripte' => $pkBest !== null ? ['assets/passkey.js'] : []]);
    exit;
}

$zustand = totp_zustand($userId);
$codes   = null;
/* NACH DEM RÜCKWEG (Konzept RW, RW-03; M-RW-01 Bild 3): `login.php` schickt
 * eine Pflichtrolle unmittelbar hierher und legt diese Marke ab. Sie gilt
 * EINMAL — gelesen und weggenommen —, damit die Meldung nicht bei jedem
 * Neuladen des Tors wiederkommt. */
$nachRueckweg = !empty($_SESSION['zf_nach_rueckweg']);
unset($_SESSION['zf_nach_rueckweg']);
$fehler  = null;
$fehlerAuftakt = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $r = totp_einrichtung_abschliessen($userId, (string)($_POST['code'] ?? ''));
    if ($r['ok']) {
        $codes = $r['codes'];
    } elseif (($r['grund'] ?? '') === 'code') {
        $fehlerAuftakt = 'Der Code passt nicht.';
        $fehler = 'Er gilt 30 Sekunden — den nächsten aus der App nehmen. '
                . 'Passt keiner, stimmt oft die Uhrzeit des Handys nicht.';
    }
    $zustand = totp_zustand($userId);
}

if ($codes === null
    && (!rolle_braucht_zweitfaktor($userRole) || $zustand['fehlt'] || $zustand['an'])) {
    header('Location: index.php');
    exit;
}

$roh = null;
$grund = null;
if ($codes === null) {
    $roh = $zustand['angefangen'] ? totp_geheimnis($userId) : null;
    if ($roh === null) {
        $b = totp_einrichtung_beginnen($userId);
        $roh = $b['geheimnis'] ?? null;
        $grund = $b['grund'] ?? null;
    }
}

ui_seite_start(['titel' => 'Zweitfaktor einrichten', 'klasse' => 'anmeldung-body']);
?>
<main class="anmeldung">
 <?php ui_hinweise(); ?>
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel"><?= e(instanz_kurz()) ?></h1>
  <p class="anmeldung-unter">Einsatzdokumentation Notarzt</p>
<?php if ($codes !== null): ?>
  <?php /* SCHRITT 2: DIE CODES. Kein Formular um den Codeblock — sein
           Druckformular darf in keinem anderen stehen. „Weiter" ist ein
           eigenes, das nur zur Startseite führt; das Tor lässt jetzt durch. */ ?>
  <h2 class="anmeldung-schritt">Zweitfaktor ist eingeschaltet</h2>
  <?php zf_codes($codes); ?>
  <form method="get" action="index.php">
    <div class="listen-form-fuss">
      <?= ui_knopf(['text' => 'Weiter', 'art' => 'primaer', 'breit' => true,
                    'attr' => ' data-zf-weiter']) ?>
    </div>
    <p class="feld-klein">„Weiter" wird frei, sobald das Häkchen gesetzt ist.</p>
  </form>
<?php elseif ($roh === null): ?>
  <?php /* KEIN GEHEIMNIS ZU HABEN. Das Tor schweigt ohne Serverschlüssel
           (auth_guard.php) — wer trotzdem hier ist, kam über die Adresse.
           Die Meldung sagt, was fehlt, und der Weg hinaus steht darunter. */ ?>
  <h2 class="anmeldung-schritt">Zweitfaktor einrichten</h2>
  <?php ui_meldung(null, $grund === 'serverschluessel'
        ? 'Auf dieser Anlage ist kein Serverschlüssel eingetragen. Ohne ihn lässt sich das '
          . 'Geheimnis nicht sicher speichern — die BetreiberIn trägt ihn unter Betrieb → Servereinstellungen nach.'
        : 'Die Einrichtung ist gerade nicht möglich. Bitte später erneut versuchen.'); ?>
<?php else: ?>
  <form method="post">
    <?= csrf_field() ?>
    <h2 class="anmeldung-schritt">Zweitfaktor einrichten</h2>
    <?php if ($nachRueckweg): ?>
    <?php ui_meldung('Für die Rolle ' . rolle_text($userRole) . ' ist er Pflicht — richte ihn jetzt '
                   . 'mit dem neuen Gerät ein.', null, 'ok', '    ',
                   ['auftakt' => 'Zweitfaktor zurückgesetzt.']); ?>
    <?php else: ?>
    <p class="feld-hinweis">Für die Rolle <strong><?= e(rolle_text($userRole)) ?></strong> ist er Pflicht — danach geht es weiter.</p>
    <?php endif; ?>
    <?php ui_meldung(null, $fehler, 'info', '    ', ['auftakt_fehler' => $fehlerAuftakt]); ?>
    <?php zf_einrichtung($roh, (string)($row['email'] ?? '')); ?>
    <div class="listen-form-fuss">
      <?= ui_knopf(['text' => 'Einschalten', 'art' => 'primaer', 'breit' => true,
                    'symbol' => 'haken']) ?>
    </div>
  </form>
<?php endif; ?>
<?php if ($codes === null): ?>
  <p class="anmeldung-neben"><a href="logout.php">Abmelden</a></p>
<?php endif; ?>
  <p class="zustandszeile" id="loginstate"></p>
 </div>

 <nav class="fuss-anmeldung" aria-label="Über diese Anwendung">
   <a href="ueber.php">Was ist NAdoku?</a>
   <a href="hilfe.php">Handbuch</a>
   <a href="impressum.php">Impressum</a>
   <a href="datenschutz.php">Datenschutz</a>
 </nav>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php ui_seite_ende(['skripte' => ZF_SKRIPTE]); ?>
