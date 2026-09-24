<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/instanz_lib.php';
require_once __DIR__ . '/umgebung_lib.php';   // Vorsatz im Titel (P5c/AP1, E-P5c-70)
require_betreiberin();
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text() fuer die Zeitmarke des Blatts

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
                    'wozu' => 'Versiegelt die Zugangsdaten der Backup-Ziele, das '
                            . 'Komplett-Backup, die Konto-Backups und die Geheimnisse '
                            . 'des Zweitfaktors. Ohne ihn lässt sich keine versiegelte '
                            . 'Sicherung mehr öffnen, und die Anmeldung nimmt nur noch '
                            . 'Wiederherstellungscodes.'];
}
if (preg_match('/^[0-9a-f]{64}$/i', $anHex)) {
    $eintraege[] = ['name' => 'Server-Anteil',
                    'eintrag' => 'kdf_anteil',
                    'hex' => $anHex,
                    'wozu' => 'Geht in den Datenschlüssel JEDES Kontos ein. Ohne '
                            . 'ihn kommt niemand mehr an die geschützten Angaben — '
                            . 'bis jede NutzerIn ihr Passwort über den '
                            . 'Wiederherstellungsschlüssel neu gesetzt hat. Die '
                            . 'Daten selbst sind davon nicht betroffen.'];
}
if (preg_match('/^[0-9a-f]{64}$/i', $anAlt)) {
    $eintraege[] = ['name' => 'Server-Anteil (bisheriger)',
                    'eintrag' => 'kdf_anteil_alt',
                    'hex' => $anAlt,
                    'wozu' => 'Eine Rotation läuft. Dieser Wert öffnet die Hüllen '
                            . 'der Konten, die sich seit dem Wechsel noch nicht '
                            . 'angemeldet haben. Er verschwindet, sobald kein '
                            . 'Konto mehr auf ihm steht.'];
}

$adresse = app_url();
$jetzt   = datum_zeit_text(gmdate('Y-m-d H:i:s'), ', ');

/* Erkennungswert wie `asset()`, aber diese Seite lädt db.php ohnehin — der
 * Aufruf steht hier trotzdem ausgeschrieben, damit sie ohne `ui.php` auskommt
 * (kein Gerüst heisst auch: keine Gerüst-Bibliothek). */
$v = static function (string $rel): string {
    $t = @filemtime(__DIR__ . '/' . $rel);
    return $rel . ($t !== false ? '?v=' . $t : '');
};
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
<link rel="stylesheet" href="<?= $h($v('assets/style.css')) ?>">
</head>
<body class="blatt-seite">
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
    <div class="text">

      <h1>Schlüsselblatt</h1>
      <p class="feld-hinweis">
        <strong><?= $h($adresse !== '' ? $adresse : 'Diese Installation') ?></strong>
        · gedruckt am <?= $h($jetzt) ?>
      </p>

      <?php if (!$eintraege): ?>
        <div class="meldung meldung-warn" role="status">
          <p><strong>Es steht noch kein Schlüssel in <code>config.php</code>.</strong>
             Unter Betrieb → Servereinstellungen, Karte „Schlüssel des Servers",
             lassen sie sich anlegen. Danach dieses Blatt erneut drucken.</p>
        </div>
      <?php else: ?>

        <div class="meldung meldung-warn" role="status">
          <p><strong>Dieses Blatt trägt die Geheimnisse dieser Installation im
             Klartext.</strong> Es gehört nicht in die Ablage neben den Server
             und nicht in dasselbe Backup — sondern an <strong>zwei getrennte
             Orte</strong>: in die Betriebsakte und in den Passwortmanager der
             BetreiberIn. Einer allein ist kein zweiter Ort.</p>
        </div>

        <?php foreach ($eintraege as $e): ?>
          <h2><?= $h($e['name']) ?></h2>
          <p class="feld-hinweis"><?= $h($e['wozu']) ?></p>
          <p class="feld-hinweis">Eintrag in <code>config.php</code>:
             <code><?= $h($e['eintrag']) ?></code> · Kennung
             <strong><?= $h((string)schluessel_kennung($e['hex'])) ?></strong></p>
          <?php /* VIERERGRUPPEN, WEIL ES ABGETIPPT WIRD — im Ernstfall, unter
                   Zeitdruck, von Papier. 64 Zeichen am Stück verliert das
                   Auge; sechzehn Gruppen zu vier hält es. Beim Nachtragen
                   werden Leerzeichen und Gross-/Kleinschreibung wieder
                   entfernt, die Lesehilfe kostet also nichts. */ ?>
          <p class="codeblock-wert blatt-wert"><?= $h(schluessel_gruppen($e['hex'])) ?></p>
        <?php endforeach; ?>

        <h2>Was damit zu tun ist</h2>
        <p class="feld-hinweis"><strong>Wozu.</strong> Geht
           <code>config.php</code> verloren, sind beide Werte weg. Der
           Serverschlüssel nimmt jede versiegelte Sicherung mit; der
           Server-Anteil sperrt jede NutzerIn von ihren geschützten Angaben aus,
           bis sie ihr Passwort über den Wiederherstellungsschlüssel neu setzt.
           <strong>Kein Datenverlust</strong> — aber ein Vorgang für alle.</p>
        <p class="feld-hinweis"><strong>Wohin.</strong> Zwei Ausdrucke, zwei
           Orte: Betriebsakte und Passwortmanager der BetreiberIn. Nicht in den
           Serverordner, nicht in dasselbe Backup — das Blatt soll genau das
           überleben, was die Datei nicht überlebt.</p>
        <p class="feld-hinweis"><strong>Wann neu.</strong> Nach jeder
           <em>Rotation</em> des Server-Anteils und nach jedem
           <em>Neuanfang</em>. Ein altes Blatt ist dann nicht nur überflüssig,
           sondern irreführend — es zeigt einen Wert, der nichts mehr öffnet.
           Alte Ausdrucke vernichten.</p>
        <p class="feld-hinweis"><strong>Zurücktragen</strong> lässt sich ein
           Wert unter Betrieb → Servereinstellungen, <em>Nachtragen vom Blatt</em>.
           Die Seite rechnet die Kennung und schreibt nur bei Übereinstimmung —
           ein Tippfehler landet nicht in <code>config.php</code>.</p>

      <?php endif; ?>

      <?php /* NUR AM BILDSCHIRM. Zwei Knoepfe auf einem Ausdruck sind zwei
               Kaesten, die nichts tun — `.nur-bildschirm` blendet sie im
               Druck aus (Abschnitt „Druck" im Stylesheet). */ ?>
      <p class="feld-hinweis nur-bildschirm">
        <button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button>
        <a class="knopf knopf-leise" href="betrieb_server.php"><span>Zurück zu den Servereinstellungen</span></a>
      </p>

    </div>
  </main>
</div>
<script src="<?= $h($v('assets/blatt-drucken.js')) ?>"></script>
</body>
</html>
