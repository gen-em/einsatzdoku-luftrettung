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
 */

require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/totp_lib.php';
require_once __DIR__ . '/zweitfaktor_teile.php';

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
          . 'Geheimnis nicht sicher speichern — die BetreiberIn trägt ihn unter Betrieb → Server nach.'
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
