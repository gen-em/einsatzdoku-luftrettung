<?php
declare(strict_types=1);

/**
 * DAS NOTFALLBLATT — der Wiederherstellungsschluessel auf Papier
 * (P5b/AP9, E-P5b-09, -20; Mockup M-P5b-02d).
 *
 * ===========================================================================
 * ES SPEICHERT NICHTS, UND ES KANN AUCH NICHTS SPEICHERN
 * ===========================================================================
 *
 * Der Schluessel kommt per POST aus dem Browser und geht in dieselbe Antwort
 * zurueck. Er wird nicht gelesen, nicht geschrieben, nicht protokolliert. Das
 * ist keine Vorsicht, sondern die einzige Bauform, die moeglich ist: **Der
 * Server kennt diesen Schluessel nicht.** Er entsteht im Browser, wird dort
 * zum Verpacken des Inhaltsschluessels benutzt und ist danach fort — was
 * beim Server ankaeme, koennte er ohnehin nicht wiederholen.
 *
 * DARAUS FOLGT DIE EIGENSCHAFT, DIE DAS BLATT AUSMACHT: Es laesst sich
 * **spaeter nicht erneut drucken**. Wer es verliert, erzeugt einen neuen
 * Schluessel — das alte Blatt wird damit ungueltig, und genau das steht
 * darauf.
 *
 * ===========================================================================
 * KEINE ANMELDUNG NOETIG, UND DAS IST KEIN VERSEHEN
 * ===========================================================================
 *
 * Der Schluessel wird an ZWEI Stellen gezeigt, und an einer davon gibt es
 * noch keine Sitzung:
 *
 *   `pw_handling.php`   beim ERSTEN Setzen des Passworts — da ist niemand
 *                       angemeldet, das Konto existiert gerade erst
 *   Konto-Rueckfrage    nach einer Erneuerung, angemeldet
 *
 * Ein `auth_guard.php` hier haette den ersten Fall unmoeglich gemacht — also
 * genau den, in dem das Blatt am wichtigsten ist.
 *
 * WAS STATTDESSEN SCHUETZT: Diese Seite gibt nur wieder, was ihr gesendet
 * wurde, und zwar **gepruefte Werte in festem Text**. Der Code muss dem
 * Format entsprechen (20 Zeichen aus dem Alphabet, in fuenf Vierergruppen),
 * die Adresse muss eine Adresse sein; alles andere wird ausgelassen. Es gibt
 * keinen freien Text, keinen Verweis nach draussen und kein Markup aus der
 * Eingabe. Wer sich selbst etwas schickt, sieht sein eigenes Blatt.
 *
 * ===========================================================================
 * KEIN LOGO, KEIN BILD (Mockup-Anmerkung)
 * ===========================================================================
 *
 * „Text genuegt und druckt auf jedem Geraet." Ein Logo braucht eine Datei,
 * die Datei braucht einen Pfad, und ein Blatt, das an einem fehlenden Bild
 * haengt, ist ein Blatt mit einem leeren Kasten darauf. Die Zuordnung
 * traegt der Text: Adresse der Installation und Kontoadresse stehen oben,
 * damit das Blatt einem Konto zuzuordnen bleibt, wenn es in fuenf Jahren
 * gefunden wird.
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

/* ---- Was gesendet wurde, und ob es brauchbar ist ------------------------ */

/**
 * Der Wiederherstellungscode, oder `null`.
 *
 * DAS ALPHABET IST DAS AUS `crypto.js` (`RC_CHARS`) — ohne 0, 1, I, L, O und
 * U, weil die auf Papier mit O/0, 1/l und V/U verwechselt werden. Wer hier
 * grosszuegiger praefte als der Erzeuger, druckte ein Blatt mit einem Code,
 * den die Wiederherstellung spaeter ablehnt.
 *
 * FUENF VIERERGRUPPEN, 20 Zeichen. Das Mockup zeichnet sechzehn Gruppen zu
 * 64 Zeichen — das ist das Format des SCHLUESSELBLATTS (S10), aus dem die
 * Vorlage stammt, und nicht das des Wiederherstellungscodes. Gemessen an
 * `newRecoveryCode()`: 20 Zeichen, Bindestrich nach je vier.
 */
function nb_code(string $roh): ?string
{
    $c = strtoupper(trim($roh));
    return preg_match('/^[ABCDEFGHJKMNPQRSTVWXYZ23456789]{4}(-[ABCDEFGHJKMNPQRSTVWXYZ23456789]{4}){4}$/', $c)
        ? $c : null;
}

$code = nb_code((string)($_POST['code'] ?? ''));

/* DIE KONTOADRESSE: aus der SITZUNG, wenn es eine gibt, sonst aus dem POST.
 *
 * Die Reihenfolge ist wichtig. Wer angemeldet ist, bekommt seine eigene
 * Adresse aufs Blatt — unabhaengig davon, was im Formular stand. Nur der
 * Erstvergabe-Fall (noch keine Sitzung) nimmt den gesendeten Wert, und der
 * muss dann eine Adresse sein. */
$konto = '';
/* Siehe `doku_seite.php` — Art `lesend`, seit Web 20.27.0 nur mit Cookie
 * (F-ZE-2). Der Erstvergabe-Fall (noch keine Sitzung) bleibt genau der
 * Fall, in dem hier nichts startet und der gesendete Wert gilt. */
sitzung_starten('lesend');
$angemeldet = !empty($_SESSION['user_id']);
if ($angemeldet) {
    try {
        $st = db()->prepare('SELECT email FROM users WHERE id = ?');
        $st->execute([(int)$_SESSION['user_id']]);
        $konto = (string)($st->fetchColumn() ?: '');
    } catch (Throwable) { /* ohne Datenbank eben ohne Adresse */ }
}
if ($konto === '') {
    $roh = trim((string)($_POST['konto'] ?? ''));
    $konto = filter_var($roh, FILTER_VALIDATE_EMAIL) ? $roh : '';
}

/* `app_url()` steht in `instanz_lib.php` — nicht in `session_lib.php`,
 * wie ich zuerst geschrieben hatte. Die Seite laedt sonst ohne Fehler
 * und bricht erst an der Zeile darunter ab. */
require_once __DIR__ . '/instanz_lib.php';
/* Der Vorsatz „[Staging] " im Titel (P5c/AP1, E-P5c-70): Das Blatt baut
 * seine Huelle selbst und laeuft nicht durch `ui_seite_start()`. */
require_once __DIR__ . '/umgebung_lib.php';
$adresse = app_url();
$ohneSchema = preg_replace('#^https?://#', '', rtrim($adresse, '/')) ?? '';
/* MIT UHRZEIT seit P5c/AP9 — wie Code- und Schlüsselblatt (M-P5c-01f). Bis
 * dahin stand hier nur das Datum; wer am selben Tag zweimal einen neuen
 * Schlüssel erzeugt, hielt zwei Blätter in der Hand, die sich nur im Wert
 * unterschieden. */
$jetzt   = datum_zeit_text(gmdate('Y-m-d H:i:s'));
$umg     = umgebung();
/* DAS LOGO IST DIE WAHL DES KONTOS, ohne Sitzung der Standard der
 * Installation (E-P5c-30). `logo_src()` fragt `logo_stamm()` — und die steht
 * in `session_lib.php`; ohne sie fiel das Blatt still auf den Hubschrauber
 * zurück (F-P5c-149). */
require_once __DIR__ . '/session_lib.php';
/* `ui.php` für die Meldung (`ui_meldung_markup()`, mit Symbol) — derselbe
 * Baustein wie auf Code- und Schlüsselblatt (P5c/AP9). */
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
<?php /* DER BAUSTEIN IST `.blatt-druck` (P5c/AP9, E-P5c-08, -30, M-P5c-01f,
         M-P5c-02e) — wie Code- und Schlüsselblatt. Bis Web 21.0.0 stand das
         Blatt in der Lesespalte des Gerüsts, mit dem Wert als
         `.codeblock-wert.blatt-wert` am Stück; jetzt fünf nummerierte Gruppen
         in einer Kachel und genau eine A4-Seite. */ ?>
<main class="blatt-druck">
<header class="blatt-kopf"><img src="<?= $h(logo_src()) ?>" alt="" width="70" height="44"><span class="blatt-marke"><?= $h(instanz_kurz()) ?></span><span class="blatt-kopf-rechts"><?= $h($adresse) ?><?php
  if ($konto !== ''): ?><br>Konto <strong><?= $h($konto) ?></strong><?php endif; ?><br>gedruckt am <?= $h($jetzt) ?></span></header>
<?php if ($umg !== null): ?>
<p class="blatt-umgebung"><strong><?= $h($umg['name']) ?></strong> — Testdaten, kein Echtbetrieb. Dieses Blatt gilt nur für diese Anlage.</p>
<?php endif; ?>
<h1>Notfallblatt</h1>
<?php if ($code === null): ?>
<?php /* OHNE CODE KEIN BLATT — und der Grund steht da, statt einer leeren
         Seite. Hierher kommt, wer die Adresse aus dem Verlauf aufruft: Das
         Blatt entsteht nur in dem Augenblick, in dem der Schluessel gezeigt
         wird, denn danach kennt ihn niemand mehr — auch der Server nicht.

         „EINSTELLUNGEN → PROFIL", NICHT „→ KONTO" (F-P5c-53, F-P5c-61): Einen
         Reiter „Konto" gibt es nicht; bis Web 21.0.0 stand der falsche Weg
         hier und unter „Wann es ungültig wird". */ ?>
<?= ui_meldung_markup('warn', 'Der Wiederherstellungsschlüssel entsteht in deinem Browser und '
      . 'wird nirgends gespeichert — auch nicht bei uns. Brauchst du ein neues Blatt, erzeuge unter '
      . 'Einstellungen → Profil einen neuen Schlüssel; das alte Blatt wird damit ungültig.',
      'Dieses Blatt lässt sich nicht nachträglich drucken.') ?>
<?php else: ?>
<?= ui_meldung_markup('warn', 'Bewahre es dort auf, wo du es in fünf Jahren noch findest und '
      . 'niemand sonst: Dokumentenordner zu Hause, Bankschließfach, Passwortmanager.',
      'Dieses Blatt öffnet deine Patientendaten.') ?>
<section class="blatt-kachel"><div class="blatt-kachel-kopf"><span class="blatt-kachel-name">Wiederherstellungsschlüssel</span><span class="blatt-kachel-neben">20 Zeichen · Leerzeichen, Bindestriche und Groß/Klein sind egal</span></div>
<div class="blatt-druck-gruppen blatt-druck-gruppen-5"><?php foreach (explode('-', $code) as $nr => $g): ?><span class="blatt-druck-gruppe" data-nr="<?= $nr + 1 ?>"><?= $h($g) ?></span><?php endforeach; ?></div>
<p>Der Schlüssel entsteht in deinem Browser und wird nirgends gespeichert — dieses Blatt lässt sich nicht nachdrucken.</p></section>
<h2>Was dieses Blatt kann</h2>
<p>Deine Patientendaten verschlüsselt der Browser mit einem Schlüssel aus deinem Passwort; die BetreiberIn hat ihn nicht. Vergisst du das Passwort, ist dieses Blatt der <strong>einzige</strong> Weg zurück. Ohne Blatt und ohne Passwort bleiben Zeiten, Orte und Rettungsmittel — die Patientendaten sind weg.</p>
<h2>So benutzt du es</h2>
<ol><li>Anmeldeseite → „Passwort vergessen?" → Adresse eintragen.</li><li>Den Link aus der Mail öffnen und den Schlüssel von diesem Blatt eintippen.</li><li>Neues Passwort setzen. Du bekommst einen <strong>neuen</strong> Schlüssel und ein neues Blatt — dieses ist dann ungültig.</li></ol>
<h2>Wann es ungültig wird</h2>
<p>Sobald du unter Einstellungen → Profil den Schlüssel erneuerst, oder nach einer Wiederherstellung. Mehr im Handbuch, Kapitel „Verschlüsselung der Patientendaten": <?= $h($ohneSchema !== '' ? $ohneSchema : '…') ?>/hilfe.php#5-verschluesselung-der-patientendaten-pflicht</p>
<?php endif; ?>
<footer class="blatt-fuss"><span>Notfallblatt · <?= $h(instanz_kurz()) ?> · Web <?= $h(WEB_VERSION) ?> · freie Software (AGPL v3)</span><span>Seite 1 von 1</span></footer>
<?php /* NUR AM BILDSCHIRM: Druckknopf und Rückweg — derselbe Knopf und
         dasselbe Skript wie bei Schlüssel- und Codeblatt. KEIN DRUCKKNOPF OHNE
         SCHLÜSSEL: Im Ohne-Code-Zweig gibt es nichts zu drucken (aufgefallen
         am Bild `02g-notfallblatt-ohne`).

         DER RÜCKWEG HÄNGT DARAN, OB JEMAND ANGEMELDET IST. Beim ERSTEN Setzen
         des Passworts ist er es nicht — dann führt der Knopf zur Anmeldung,
         und das ist genau der nächste Schritt: Blatt drucken, dann anmelden. */ ?>
<p class="nur-bildschirm">
  <?php if ($code !== null): ?>
  <button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button>
  <?php endif; ?>
  <a class="knopf knopf-leise" href="<?= $angemeldet ? 'index.php' : 'login.php' ?>"><span><?= $angemeldet ? 'Zur Startseite' : 'Zur Anmeldung' ?></span></a>
</p>
</main>
<script src="<?= $h(asset('assets/blatt-drucken.js')) ?>"></script>
</body>
</html>
