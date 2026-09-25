<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/instanz_lib.php';
require_once __DIR__ . '/umgebung_lib.php';   // Vorsatz im Titel (P5c/AP1, E-P5c-70)
require_betreiberin();
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text() fuer die Zeitmarke des Blatts
/* `ui.php` SEIT P5c/AP9 — nicht für das Gerüst (es gibt keins), sondern für
 * die Meldung: Das Blatt nimmt denselben Baustein wie Code- und Notfallblatt
 * (`ui_meldung_markup()`, mit Symbol, M-P5c-01f). Bis dahin stand die Warnung
 * von Hand gebaut da, ohne Symbol. */
require_once __DIR__ . '/ui.php';

/**
 * DAS SCHLÜSSELBLATT — die einzige Seite, die die Geheimnisse ZEIGT
 * (S10, E-S10-10).
 *
 * WOZU ES DAS GIBT. `config.php` ist seit S10 Schlüsselträger der ganzen
 * Installation: Der Serverschlüssel öffnet jede Sicherung, der Server-Anteil
 * geht in den Datenschlüssel jedes Kontos ein. Geht die Datei verloren, ist
 * beides weg — und ohne einen zweiten Ort ist es dann endgültig weg. Dieser
 * zweite Ort ist Papier.
 *
 * WARUM PAPIER UND NICHT EINE DATEI. Eine heruntergeladene Datei liegt im
 * selben Rechner, im selben Backup, im selben Unglück. Ein Ausdruck in der
 * Betriebsakte überlebt einen Serverbrand, einen verschlüsselten Rechner und
 * einen gelöschten Ordner. Es gibt hier deshalb KEINEN Download-Knopf und
 * keinen QR-Code: Wer eine Datei will, druckt in PDF und weiss dann, dass er
 * es getan hat.
 *
 * ZWEI AUSDRUCKE, ZWEI ORTE — Betriebsakte und Passwortmanager der
 * BetreiberIn. Einer allein ist kein zweiter Ort.
 *
 * -------------------------------------------------------------------------
 * DIESE SEITE IST DIE EINZIGE AUSNAHME VON „NIE DER WERT, IMMER DIE KENNUNG".
 *
 * Überall sonst — Karte, Statuszeile, Meldung — steht ausschliesslich die
 * acht Zeichen lange Kennung. Hier stehen die 64 Hexzeichen selbst, und das
 * ist der Zweck des Blattes. Drei Riegel halten den Unterschied:
 *
 *   1. Nur die Rolle BetreiberIn (`require_betreiberin()`).
 *   2. `Cache-Control: no-store` und `Referrer-Policy: no-referrer` — die
 *      Seite soll weder im Zwischenspeicher des Browsers noch in der
 *      Adresszeile eines anderen Servers landen.
 *   3. Kein Gerüst, kein Menü, kein Verweis nach draussen. Wer sie aufruft,
 *      hat sie aufrufen wollen.
 *
 * KEIN GERÜST — DAS MUSTER IST `wartung_seite_html()` (S5 Paket W). Eine
 * Seite, die gedruckt wird, soll kein Menü, keine Kopfleiste und keine
 * Schublade mitdrucken. Der Unterschied zur Wartungsseite ist, dass diese
 * hier ANGEMELDET ist; das Gerüst fehlt trotzdem, und zwar aus demselben
 * Grund: Es gehört nicht auf das Blatt.
 */

/* Die Seite zeigt Geheimnisse. Sie gehört in keinen Zwischenspeicher und in
 * keine fremde Adresszeile. */
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

$skHex = (string)konfig('server_key', '');
$anHex = (string)konfig('kdf_anteil', '');
$anAlt = (string)konfig('kdf_anteil_alt', '');

$eintraege = [];
if (preg_match('/^[0-9a-f]{64}$/i', $skHex)) {
    $eintraege[] = ['name' => 'Serverschlüssel',
                    'eintrag' => 'server_key',
                    'hex' => $skHex,
                    /* SEIT WEB 20.42.0 AUCH DIE ZWEITFAKTOR-GEHEIMNISSE (E-P5c-42):
                     * Ohne diesen Schlüssel prüft die Anmeldung keinen Code aus
                     * der App mehr — es bleiben die Wiederherstellungscodes, die
                     * NICHT an ihm hängen, und das Zurücksetzen durch die
                     * Verwaltung. */
                    'wozu' => 'Öffnet alles Versiegelte: Komplett-Backup, Konto-Backups, '
                            . 'Zugänge der Backup-Ziele, Archive des Protokolls und die '
                            . 'Geheimnisse des Zweitfaktors.'];
}
if (preg_match('/^[0-9a-f]{64}$/i', $anHex)) {
    $eintraege[] = ['name' => 'Server-Anteil',
                    'eintrag' => 'kdf_anteil',
                    'hex' => $anHex,
                    'wozu' => 'Geht in den Datenschlüssel jedes Kontos ein; ohne ihn setzt '
                            . 'jede NutzerIn ihr Passwort über den '
                            . 'Wiederherstellungsschlüssel neu. Kein Datenverlust.'];
}
if (preg_match('/^[0-9a-f]{64}$/i', $anAlt)) {
    $eintraege[] = ['name' => 'Server-Anteil (bisheriger)',
                    'eintrag' => 'kdf_anteil_alt',
                    'hex' => $anAlt,
                    'wozu' => 'Nur während einer Rotation: öffnet die Konten, die sich '
                            . 'seit dem Wechsel noch nicht angemeldet haben.'];
}

$adresse = app_url();
$ohneSchema = preg_replace('#^https?://#', '', rtrim($adresse, '/')) ?? '';
$jetzt   = datum_zeit_text(gmdate('Y-m-d H:i:s'));
$umg     = umgebung();

/* DAS LOGO IST DER STANDARD DER INSTALLATION, nicht die Wahl des Kontos
 * (E-P5c-30): Das Blatt gehört der Anlage, nicht der Person, die es druckt.
 * Dieselbe Auflösung wie die Kachel unter Verwaltung → Installation. */
$logoStamm = logo_standard_aufgeloest() === 'fahrzeug'
           ? 'gen-em_logo_nef' : 'gen-em_logo_helicopter';

$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

/* DIE KOPFZEILEN VON HAND (P5a/AP4, E-P5a-15).
 *
 * Diese Seite baut ihre Huelle selbst und laeuft deshalb NICHT durch
 * `ui_seite_start()` — die eine Stelle, an der die Kopfzeilen sonst gesetzt
 * werden. Ohne diese Zeile stuende ausgerechnet das Blatt mit den beiden
 * Geheimnissen des Servers ohne CSP und ohne `nosniff` da.
 *
 * MIT NONCE, obwohl heute kein Inline-Block darauf steht: Wer hier je einen
 * ergaenzt, soll ihn benutzen koennen, statt eine still gebrochene Seite zu
 * hinterlassen. Ein ungenutzter Nonce kostet nichts. */
require_once __DIR__ . '/kopfzeilen_lib.php';
kopfzeilen_seite();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= e(umgebung_praefix()) ?>Schlüsselblatt — <?= e(instanz_kurz()) ?></title>
<link rel="stylesheet" href="<?= $h(asset('assets/style.css')) ?>">
</head>
<body class="blatt-seite">
<?php /* DER BAUSTEIN IST `.blatt-druck` (P5c/AP9, E-P5c-08, -30, M-P5c-01f) —
         wie Code- und Notfallblatt. Bis Web 21.0.0 stand das Blatt in der
         Lesespalte des Gerüsts (`.rahmen-lesespalte` + `.text`), mit dem Wert
         als `.codeblock-wert.blatt-wert` und vier Absätzen „Was damit zu tun
         ist". Jetzt genau eine A4-Seite: Kopf mit Marke und Kurzname, je Wert
         eine Kachel mit sechzehn nummerierten Gruppen, drei Zeilen darunter.

         DIE ERKLÄRTEXT-REGEL HAT HIER EINE GRENZE (E-P5c-30): Dieses Blatt wird
         gebraucht, wenn `config.php` weg ist — dann ist auch `hilfe.php` nicht
         sicher erreichbar. „Wozu" steht deshalb an der Kachel, „Wohin" in der
         Warnung, und der Verweis nennt zusätzlich den Weg ohne Server. */ ?>
<main class="blatt-druck">
<header class="blatt-kopf"><img src="<?= $h(asset('assets/images/' . $logoStamm . '.svg')) ?>" alt="" width="70" height="44"><span class="blatt-marke"><?= $h(instanz_kurz()) ?></span><span class="blatt-kopf-rechts"><?= $h($adresse !== '' ? $adresse : 'Diese Installation') ?><br>gedruckt am <?= $h($jetzt) ?></span></header>
<?php if ($umg !== null): ?>
<p class="blatt-umgebung"><strong><?= $h($umg['name']) ?></strong> — Testdaten, kein Echtbetrieb. Dieses Blatt gilt nur für diese Anlage.</p>
<?php endif; ?>
<h1>Schlüsselblatt</h1>
<?php if (!$eintraege): ?>
<?= ui_meldung_markup('warn', 'Unter Betrieb → Servereinstellungen, Karte „Schlüssel des '
      . 'Servers", lassen sie sich anlegen; danach dieses Blatt erneut drucken.',
      'Es steht noch kein Schlüssel in config.php.') ?>
<?php else: ?>
<?= ui_meldung_markup('warn', 'Es gehört an zwei getrennte Orte — Betriebsakte und '
      . 'Passwortmanager der BetreiberIn —, nicht neben den Server und nicht in dasselbe Backup.',
      'Dieses Blatt trägt die Geheimnisse dieser Installation im Klartext.') ?>
<?php foreach ($eintraege as $e): ?>
<?php /* SECHZEHN NUMMERIERTE GRUPPEN, WEIL ES ABGETIPPT WIRD — im Ernstfall,
         unter Zeitdruck, von Papier. 64 Zeichen am Stück verliert das Auge;
         und die Quartalsrückfrage (E-P5b-10) fragt „Serverschlüssel ·
         Gruppe 11" — bis elf zählt auf Papier niemand gern. Beim Nachtragen
         werden Leerzeichen und Groß-/Kleinschreibung wieder entfernt, die
         Lesehilfe kostet also nichts. */ ?>
<section class="blatt-kachel"><div class="blatt-kachel-kopf"><span class="blatt-kachel-name"><?= $h($e['name']) ?></span><span class="blatt-kachel-neben"><code><?= $h($e['eintrag']) ?></code> · Kennung <strong><?= $h((string)schluessel_kennung($e['hex'])) ?></strong></span></div>
<div class="blatt-druck-gruppen"><?php foreach (explode(' ', schluessel_gruppen($e['hex'])) as $nr => $g): ?><span class="blatt-druck-gruppe" data-nr="<?= $nr + 1 ?>"><?= $h($g) ?></span><?php endforeach; ?></div>
<p><?= $h($e['wozu']) ?></p></section>
<?php endforeach; ?>
<h2>Was damit zu tun ist</h2>
<p><strong>Wann neu.</strong> Nach jeder Rotation des Server-Anteils und nach jedem Neuanfang — alte Blätter vernichten.</p>
<p><strong>Zurücktragen.</strong> Betrieb → Servereinstellungen, „Nachtragen vom Blatt"; geschrieben wird nur, wenn die Kennung stimmt. Leerzeichen und Groß/Klein sind egal.</p>
<?php /* KNAPP, WEIL ES SONST NICHT AUF EINE SEITE PASST (gemessen, P5c/AP9):
         Im Härtefall — drei Werte, Kurzname 83 Zeichen, Adresse 62, dazu die
         Umgebungszeile — lief diese Zeile mit der langen Sprungmarke
         „#karte-schluessel-des-servers-seit-web-20-1-0" auf drei Zeilen und
         das Blatt auf zwei Seiten (1032 von 1017 px). Mit der Marke
         „#das-schluesselblatt" und ohne „Abschnitt …" sind es 1013 von 1017.
         Der Rückfall aus M-P5c-02 — die Umgebungszeile in den Kopf — half
         dort nicht: Neben einem langen Kurznamen wird die rechte Kopfspalte
         dadurch höher, nicht kürzer (1051 px). */ ?>
<p><strong>Mehr.</strong> Handbuch: <?= $h($ohneSchema !== '' ? $ohneSchema : '…') ?>/hilfe.php#das-schluesselblatt — ohne Server: <code>docs/Handbuch.md</code> auf github.com/gen-em/einsatzdoku-luftrettung.</p>
<?php endif; ?>
<footer class="blatt-fuss"><span>Schlüsselblatt · <?= $h(instanz_kurz()) ?> · Web <?= $h(WEB_VERSION) ?></span><span>Seite 1 von 1</span></footer>
<?php /* NUR AM BILDSCHIRM. Zwei Knöpfe auf einem Ausdruck sind zwei Kästen,
         die nichts tun — `.nur-bildschirm` nimmt sie aus dem Druck. Im Blatt
         und nicht darunter, wie beim Codeblatt. */ ?>
<p class="nur-bildschirm">
  <?php if ($eintraege): ?>
  <button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button>
  <?php endif; ?>
  <a class="knopf knopf-leise" href="betrieb_server.php"><span>Zurück zu den Servereinstellungen</span></a>
</p>
</main>
<script src="<?= $h(asset('assets/blatt-drucken.js')) ?>"></script>
</body>
</html>
