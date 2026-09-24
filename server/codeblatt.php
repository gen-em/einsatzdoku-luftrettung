<?php
declare(strict_types=1);

/**
 * DAS CODEBLATT — die zehn Wiederherstellungscodes des Zweitfaktors auf
 * Papier (P5c/AP5, E-P5c-41, -42; Mockup M-P5c-02b-codeblatt).
 *
 * ES SPEICHERT NICHTS. Die Codes kommen per POST aus der Seite, die sie eben
 * einmal gezeigt hat (Einrichtungstor oder Profilkarte), und gehen in dieselbe
 * Antwort zurück. Gespeichert ist von jedem nur ein Prüfwert
 * (`password_hash()`, `totp_codes`) — deshalb lässt sich das Blatt später
 * nicht nachdrucken, und genau das steht darauf.
 *
 * NUR MIT SITZUNG. Anders als das Notfallblatt, das auch beim ersten Setzen
 * des Passworts gedruckt wird: Codes gibt es nur für ein angemeldetes Konto,
 * und die Adresse auf dem Blatt kommt aus der Sitzung, nie aus dem Formular.
 * Ohne Sitzung zeigt die Seite, warum es kein Blatt gibt.
 *
 * GEPRÜFTE WERTE IN FESTEM TEXT: Ein Code muss das Format haben, das
 * `totp_codes_erzeugen()` erzeugt (acht Zeichen aus `TOTP_CODE_ZEICHEN`);
 * alles andere fällt weg. Kein freier Text aus der Eingabe, kein Markup.
 *
 * DER BAUSTEIN IST `.blatt-druck` (E-P5c-08, M-P5c-01f) — dieses Blatt ist
 * sein erster Verwender; Schlüssel- und Notfallblatt ziehen in AP9 nach.
 * Genau eine A4-Seite (`@page`), auf Staging mit der Umgebungszeile
 * (E-P5c-50).
 */

if (!is_file(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/format_lib.php';
require_once __DIR__ . '/totp_lib.php';
require_once __DIR__ . '/instanz_lib.php';
require_once __DIR__ . '/umgebung_lib.php';
require_once __DIR__ . '/ui.php';

/* Diese Seite zeigt Geheimnisse: in keinen Zwischenspeicher, in keine fremde
 * Adresszeile — wie Schlüssel- und Notfallblatt. */
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

sitzung_starten('lesend');
$konto = '';
if (!empty($_SESSION['user_id'])) {
    try {
        $st = db()->prepare('SELECT email FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $konto = (string)($st->fetchColumn() ?: '');
    } catch (Throwable) { /* ohne Datenbank eben ohne Adresse */ }
}

$codes = [];
if ($konto !== '') {
    foreach ((array)($_POST['codes'] ?? []) as $roh) {
        $c = is_string($roh) ? totp_code_normieren($roh) : null;
        if ($c !== null && count($codes) < TOTP_CODES) { $codes[] = $c; }
    }
}

$adresse = app_url();
$ohneSchema = preg_replace('#^https?://#', '', rtrim($adresse, '/')) ?? '';
$jetzt   = datum_zeit_text(gmdate('Y-m-d H:i:s'), ', ');
$umg     = umgebung();
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

require_once __DIR__ . '/kopfzeilen_lib.php';
kopfzeilen_seite();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $h(umgebung_praefix()) ?>Wiederherstellungscodes<?= $adresse !== '' ? ' — ' . $h($adresse) : '' ?></title>
<link rel="stylesheet" href="<?= $h(asset('assets/style.css')) ?>">
</head>
<body class="blatt-seite">
<main class="blatt-druck">
<header class="blatt-kopf"><img src="<?= $h(logo_src()) ?>" alt="" width="70" height="44"><span class="blatt-marke"><?= $h(instanz_kurz()) ?></span><span class="blatt-kopf-rechts"><?= $h($adresse) ?><?php
  if ($konto !== ''): ?><br>Konto <strong><?= $h($konto) ?></strong><?php endif; ?><br>gedruckt am <?= $h($jetzt) ?></span></header>
<?php if ($umg !== null): ?>
<p class="blatt-umgebung"><strong><?= $h($umg['name']) ?></strong> — Testdaten, kein Echtbetrieb. Dieses Blatt gilt nur für diese Anlage.</p>
<?php endif; ?>
<h1>Wiederherstellungscodes</h1>
<?php if ($codes === []): ?>
<?php /* OHNE CODES KEIN BLATT, und der Grund steht da. Hierher kommt, wer
         die Adresse aus dem Verlauf aufruft oder nicht angemeldet ist: Die
         Codes gibt es nur in dem Augenblick, in dem sie gezeigt werden. */ ?>
<?= ui_meldung_markup('warn', 'Die Codes gibt es nur in dem Moment, in dem sie angezeigt '
      . 'werden — gespeichert ist nur ein Prüfwert je Code. Brauchst du ein neues Blatt, '
      . 'erzeuge unter Einstellungen → Profil neue Codes; die alten werden damit ungültig.',
      'Dieses Blatt lässt sich nicht nachträglich drucken.') ?>
<?php else: ?>
<?= ui_meldung_markup('warn', 'Bewahre das Blatt getrennt vom Handy auf — ein Blatt in der '
      . 'Handyhülle hilft dem, der beides findet.',
      'Diese Codes ersetzen dein Handy bei der Anmeldung.') ?>
<section class="blatt-kachel"><div class="blatt-kachel-kopf"><span class="blatt-kachel-name">Zehn Codes für den Zweitfaktor</span><span class="blatt-kachel-neben">je acht Zeichen · jeder gilt einmal · Leerzeichen sind egal</span></div>
<ol class="blatt-codes"><?php foreach ($codes as $c): ?><li><?= $h(totp_code_anzeige($c)) ?></li><?php endforeach; ?></ol>
<p>Gespeichert ist nur ein Prüfwert je Code — dieses Blatt lässt sich nicht nachdrucken.</p></section>
<h2>Was dieses Blatt kann</h2>
<p>Fehlt dein Handy, meldest du dich mit deinem Passwort und <strong>einem</strong> dieser Codes an. Jeder gilt einmal; hake ihn danach ab.</p>
<h2>So benutzt du es</h2>
<ol><li>Anmelden wie immer, mit E-Mail und Passwort.</li><li>Beim Code-Schritt „Wiederherstellungscode verwenden" wählen und einen Code von diesem Blatt eintippen.</li><li>Danach unter Einstellungen → Profil den Zweitfaktor mit dem neuen Handy neu einrichten.</li></ol>
<h2>Wann es ungültig wird</h2>
<p>Sobald du neue Codes erzeugst oder die Verwaltung deinen Zweitfaktor zurücksetzt. Mehr im Handbuch, Abschnitt „Zweitfaktor": <?= $h($ohneSchema) ?>/hilfe.php#3-1f-zweitfaktor</p>
<?php endif; ?>
<footer class="blatt-fuss"><span>Wiederherstellungscodes · <?= $h(instanz_kurz()) ?> · Web <?= $h(WEB_VERSION) ?> · freie Software (AGPL v3)</span><span>Seite 1 von 1</span></footer>
<?php /* NUR AM BILDSCHIRM: Druckknopf und Rueckweg — derselbe Knopf und
         dasselbe Skript wie bei Schluessel- und Notfallblatt. Im Blatt und
         nicht darunter, damit sie am Bildschirm auf der Seite stehen und
         nicht am linken Fensterrand; `.nur-bildschirm` nimmt sie aus dem
         Druck. */ ?>
<p class="nur-bildschirm">
  <?php if ($codes !== []): ?>
  <button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button>
  <?php endif; ?>
  <a class="knopf knopf-leise" href="<?= $konto !== '' ? 'index.php' : 'login.php' ?>"><span><?= $konto !== '' ? 'Zur Startseite' : 'Zur Anmeldung' ?></span></a>
</p>
</main>
<script src="<?= $h(asset('assets/blatt-drucken.js')) ?>"></script>
</body>
</html>
