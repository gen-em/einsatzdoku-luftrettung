<?php
declare(strict_types=1);

/**
 * DAS NOTFALLBLATT — der Wiederherstellungsschluessel auf Papier.
 *
 * ===========================================================================
 * DER SCHLUESSEL KOMMT NICHT HIERHER (seit Web 24.11.0, KA-02, F-KA-01)
 * ===========================================================================
 *
 * Bis Web 24.10.1 kam er per POST aus dem Browser und ging in dieselbe
 * Antwort zurueck. Gespeichert wurde nichts — aber der Schluessel lief durch
 * das Netz und durch jedes Anfrageprotokoll dazwischen (WAF, Proxy,
 * TLS-Inspektion, Hoster), und er oeffnet den Inhaltsschluessel ohne
 * Passwort und ohne Server-Anteil. Der Kopf hier sagte damals „Der Server
 * kennt diesen Schluessel nicht" — fuer die Ablage stimmte das, fuer den
 * Transport nicht.
 *
 * JETZT LIEFERT DIESE SEITE NUR DAS LEERE BLATT. Den Code setzt
 * `assets/blatt-uebergabe.js` im Browser ein, per `textContent`, aus dem
 * Fenster, das das Blatt geoeffnet hat (`postMessage`, gleicher Ursprung).
 * Ein POST wird mit 400 abgewiesen, ohne ihn zu lesen oder mitzuschreiben —
 * ein alter Aufrufer scheitert laut, statt den Schluessel wieder zu
 * schicken.
 *
 * DARAUS FOLGT WEITER DIE EIGENSCHAFT, DIE DAS BLATT AUSMACHT: Es laesst sich
 * **spaeter nicht erneut drucken**. Ohne das Fenster, das den Schluessel eben
 * gezeigt hat, bleibt es leer und sagt warum.
 *
 * ===========================================================================
 * KEINE ANMELDUNG NOETIG, UND DAS IST KEIN VERSEHEN
 * ===========================================================================
 *
 * Das Blatt wird an ZWEI Stellen gedruckt, und an einer davon gibt es noch
 * keine Sitzung:
 *
 *   `pw_handling.php`   beim ERSTEN Setzen des Passworts — da ist niemand
 *                       angemeldet; die Kontoadresse kommt dann mit dem Code
 *                       aus dem Ursprungsfenster (sie ist kein Geheimnis)
 *   Konto-Rueckfrage    nach einer Erneuerung, angemeldet; die Adresse kommt
 *                       aus der Sitzung, und die geht vor
 *
 * ===========================================================================
 * KEIN LOGO-BILD AUS DER EINGABE, KEIN MARKUP AUS DER EINGABE
 * ===========================================================================
 *
 * Alles, was hier aus dem Browser kommt, steht als `textContent` im Blatt und
 * ist vorher gegen sein Format gehalten (20 Zeichen aus dem Alphabet in fuenf
 * Vierergruppen; eine Adresse). Text, Aufbau und Logo kommen vom Server.
 */

if (!is_file(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/format_lib.php';   // datum_text() fuer die Zeitmarke des Blatts

/* Wie beim Schluesselblatt: Diese Seite zeigt ein Geheimnis. Sie gehoert in
 * keinen Zwischenspeicher und in keine fremde Adresszeile. */
header('Cache-Control: no-store, no-cache, must-revalidate, private');
header('Pragma: no-cache');
header('Referrer-Policy: no-referrer');
header('X-Robots-Tag: noindex, nofollow, noarchive');

/* ---- Nichts annehmen -----------------------------------------------------
 *
 * EIN POST WIRD ABGEWIESEN, NICHT GELESEN (KA-02). Kein Feld wird ausgewertet
 * und nichts protokolliert — auch nicht, dass einer kam. */
if (($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Dieses Blatt nimmt keine Werte an.\n";
    exit;
}

/* DIE KONTOADRESSE aus der SITZUNG, wenn es eine gibt. Ohne Sitzung (Erst-
 * vergabe) setzt sie das Skript aus dem Ursprungsfenster ein. */
$konto = '';
/* Siehe `doku_seite.php` — Art `lesend`, nur mit Cookie. */
sitzung_starten('lesend');
$angemeldet = !empty($_SESSION['user_id']);
if ($angemeldet) {
    try {
        $st = db()->prepare('SELECT email FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $konto = (string)($st->fetchColumn() ?: '');
    } catch (Throwable) { /* ohne Datenbank eben ohne Adresse */ }
}

/* `app_url()` steht in `instanz_lib.php` — nicht in `session_lib.php`,
 * wie ich zuerst geschrieben hatte. Die Seite laedt sonst ohne Fehler
 * und bricht erst an der Zeile darunter ab. */
require_once __DIR__ . '/instanz_lib.php';
/* Der Vorsatz „[Staging] " im Titel: Das Blatt baut
 * seine Huelle selbst und laeuft nicht durch `ui_seite_start()`. */
require_once __DIR__ . '/umgebung_lib.php';
$adresse = app_url();
$ohneSchema = preg_replace('#^https?://#', '', rtrim($adresse, '/')) ?? '';
/* MIT UHRZEIT — wie Code- und Schlüsselblatt: Wer am selben Tag zweimal
 * einen neuen Schlüssel erzeugt, hielte sonst zwei Blätter in der Hand, die
 * sich nur im Wert unterschieden. */
$jetzt   = datum_zeit_text(gmdate('Y-m-d H:i:s'));
$umg     = umgebung();
/* DAS LOGO IST DIE WAHL DES KONTOS, ohne Sitzung der Standard der
 * Installation. `logo_src()` fragt `logo_stamm()` — und die steht
 * in `session_lib.php`; ohne sie fiele das Blatt still auf den Hubschrauber
 * zurück. */
require_once __DIR__ . '/session_lib.php';
/* `ui.php` für die Meldung (`ui_meldung_markup()`, mit Symbol) — derselbe
 * Baustein wie auf Code- und Schlüsselblatt. */
require_once __DIR__ . '/ui.php';
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');


/* Kopfzeilen von Hand — diese Seite baut ihre Huelle selbst und laeuft nicht
 * durch `ui_seite_start()`. Dieselbe Ueberlegung wie beim Schluesselblatt. */
require_once __DIR__ . '/kopfzeilen_lib.php';
kopfzeilen_seite();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title><?= $h(umgebung_praefix()) ?>Notfallblatt<?= $adresse !== '' ? ' — ' . $h($adresse) : '' ?></title>
<link rel="stylesheet" href="<?= $h(asset('assets/style.css')) ?>">
</head>
<body class="blatt-seite">
<?php /* DER BAUSTEIN IST `.blatt-druck` — wie Code- und Schlüsselblatt:
         fünf nummerierte Gruppen in einer Kachel und genau eine A4-Seite. */ ?>
<main class="blatt-druck" data-blatt-empfang="notfallblatt">
<header class="blatt-kopf"><img src="<?= $h(logo_src()) ?>" alt="" width="70" height="44"><span class="blatt-marke"><?= $h(instanz_kurz()) ?></span><span class="blatt-kopf-rechts"><?= $h($adresse) ?><span data-blatt-kontozeile<?= $konto === '' ? ' hidden' : '' ?>><br>Konto <strong data-blatt-konto><?= $h($konto) ?></strong></span><br>gedruckt am <?= $h($jetzt) ?></span></header>
<?php if ($umg !== null): ?>
<p class="blatt-umgebung"><strong><?= $h($umg['name']) ?></strong> — Testdaten, kein Echtbetrieb. Dieses Blatt gilt nur für diese Anlage.</p>
<?php endif; ?>
<h1>Notfallblatt</h1>
<?php /* OHNE CODE KEIN BLATT — und der Grund steht da, statt einer leeren
         Seite. Hierher kommt, wer die Adresse aus dem Verlauf aufruft: Das
         Blatt entsteht nur in dem Augenblick, in dem der Schluessel gezeigt
         wird, denn danach kennt ihn niemand mehr. Ohne JavaScript ebenso.

         „EINSTELLUNGEN → PROFIL", NICHT „→ KONTO": Einen Reiter „Konto"
         gibt es nicht. */ ?>
<div data-blatt-ohne>
<?= ui_meldung_markup('warn', 'Der Wiederherstellungsschlüssel entsteht in deinem Browser und '
      . 'wird nirgends gespeichert — auch nicht bei uns. Brauchst du ein neues Blatt, erzeuge unter '
      . 'Einstellungen → Profil einen neuen Schlüssel; das alte Blatt wird damit ungültig.',
      'Dieses Blatt lässt sich nicht nachträglich drucken.') ?>
</div>
<div data-blatt-mit hidden>
<?= ui_meldung_markup('warn', 'Bewahre es dort auf, wo du es in fünf Jahren noch findest und '
      . 'niemand sonst: Dokumentenordner zu Hause, Bankschließfach, Passwortmanager.',
      'Dieses Blatt öffnet deine Patientendaten.') ?>
<section class="blatt-kachel"><div class="blatt-kachel-kopf"><span class="blatt-kachel-name">Wiederherstellungsschlüssel</span><span class="blatt-kachel-neben">20 Zeichen · Leerzeichen, Bindestriche und Groß/Klein sind egal</span></div>
<div class="blatt-druck-gruppen blatt-druck-gruppen-5"><?php for ($nr = 1; $nr <= 5; $nr++): ?><span class="blatt-druck-gruppe" data-nr="<?= $nr ?>" data-blatt-gruppe></span><?php endfor; ?></div>
<p>Der Schlüssel entsteht in deinem Browser und hat ihn nie verlassen — auch nicht für dieses Blatt. Es lässt sich nicht nachdrucken.</p></section>
<h2>Was dieses Blatt kann</h2>
<p>Deine Patientendaten verschlüsselt der Browser mit einem Schlüssel aus deinem Passwort; die BetreiberIn hat ihn nicht. Vergisst du das Passwort, ist dieses Blatt der <strong>einzige</strong> Weg zurück. Ohne Blatt und ohne Passwort bleiben Zeiten, Orte und Rettungsmittel — die Patientendaten sind weg.</p>
<h2>So benutzt du es</h2>
<ol><li>Anmeldeseite → „Passwort vergessen?" → Adresse eintragen.</li><li>Den Link aus der Mail öffnen und den Schlüssel von diesem Blatt eintippen.</li><li>Neues Passwort setzen. Du bekommst einen <strong>neuen</strong> Schlüssel und ein neues Blatt — dieses ist dann ungültig.</li></ol>
<h2>Wann es ungültig wird</h2>
<p>Sobald du unter Einstellungen → Profil den Schlüssel erneuerst, oder nach einer Wiederherstellung. Mehr im Handbuch, Kapitel „Verschlüsselung der Patientendaten": <?= $h($ohneSchema !== '' ? $ohneSchema : '…') ?>/hilfe.php#5-verschluesselung-der-patientendaten-pflicht</p>
</div>
<footer class="blatt-fuss"><span>Notfallblatt · <?= $h(instanz_kurz()) ?> · Web <?= $h(WEB_VERSION) ?> · freie Software (AGPL v3)</span><span>Seite 1 von 1</span></footer>
<?php /* NUR AM BILDSCHIRM: Druckknopf und Rückweg — derselbe Knopf und
         dasselbe Skript wie bei Schlüssel- und Codeblatt. KEIN DRUCKKNOPF OHNE
         SCHLÜSSEL: Im Ohne-Code-Zweig gibt es nichts zu drucken (aufgefallen
         am Bild `02g-notfallblatt-ohne`).

         DER RÜCKWEG HÄNGT DARAN, OB JEMAND ANGEMELDET IST. Beim ERSTEN Setzen
         des Passworts ist er es nicht — dann führt der Knopf zur Anmeldung,
         und das ist genau der nächste Schritt: Blatt drucken, dann anmelden. */ ?>
<p class="nur-bildschirm">
  <span data-blatt-mit hidden><button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button></span>
  <a class="knopf knopf-leise" href="<?= $angemeldet ? 'index.php' : 'login.php' ?>"><span><?= $angemeldet ? 'Zur Startseite' : 'Zur Anmeldung' ?></span></a>
</p>
</main>
<script src="<?= $h(asset('assets/blatt-drucken.js')) ?>"></script>
<script src="<?= $h(asset('assets/blatt-uebergabe.js')) ?>"></script>
</body>
</html>
