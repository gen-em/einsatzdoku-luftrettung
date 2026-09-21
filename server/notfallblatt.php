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
$adresse = app_url();
$jetzt   = fmt_local(gmdate('Y-m-d H:i:s'), 'd.m.Y');
$h = static fn(string $s): string => htmlspecialchars($s, ENT_QUOTES, 'UTF-8');

$v = static function (string $rel): string {
    $t = @filemtime(__DIR__ . '/' . $rel);
    return $rel . ($t !== false ? '?v=' . $t : '');
};

/* Kopfzeilen von Hand — diese Seite baut ihre Huelle selbst und laeuft nicht
 * durch `ui_seite_start()`. Dieselbe Ueberlegung wie beim Schluesselblatt. */
require_once __DIR__ . '/kopfzeilen_lib.php';
kopfzeilen_seite();
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<meta name="robots" content="noindex, nofollow">
<title>Notfallblatt<?= $adresse !== '' ? ' — ' . $h($adresse) : '' ?></title>
<link rel="stylesheet" href="<?= $h($v('assets/style.css')) ?>">
</head>
<body class="blatt-seite">
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
    <div class="text">

      <h1>Notfallblatt</h1>
      <p class="feld-hinweis">
        <strong>NAdoku</strong>
        <?php if ($adresse !== ''): ?> · <?= $h($adresse) ?><?php endif; ?>
        <?php if ($konto !== ''): ?> · Konto <strong><?= $h($konto) ?></strong><?php endif; ?>
        · gedruckt am <?= $h($jetzt) ?>
      </p>

<?php if ($code === null): ?>

      <?php /* OHNE CODE KEIN BLATT — und der Grund steht da, statt einer
               leeren Seite. Hierher kommt, wer die Adresse aus dem Verlauf
               aufruft: Das Blatt entsteht nur in dem Augenblick, in dem der
               Schluessel gezeigt wird, denn danach kennt ihn niemand mehr —
               auch der Server nicht. */ ?>
      <div class="meldung meldung-warn" role="status">
        <p><strong>Dieses Blatt lässt sich nicht nachträglich drucken.</strong>
           Der Wiederherstellungsschlüssel entsteht in deinem Browser und wird
           nirgends gespeichert — auch nicht bei uns. Es gibt ihn nur in dem
           Moment, in dem er angezeigt wird.</p>
      </div>
      <p>Du brauchst ein neues Blatt? Dann erzeuge unter
         <strong>Einstellungen → Konto</strong> einen <strong>neuen</strong>
         Wiederherstellungsschlüssel. Das alte Blatt wird damit ungültig.</p>

<?php else: ?>

      <div class="meldung meldung-warn" role="status">
        <p><strong>Dieses Blatt öffnet deine Patientendaten.</strong> Es gehört
           nicht in die Schreibtischschublade neben dem Rechner und nicht in
           dieselbe Tasche wie das Handy — sondern an einen Ort, den du in fünf
           Jahren noch findest und niemand sonst: Dokumentenordner zu Hause,
           Bankschließfach, Passwortmanager.</p>
      </div>

      <h2>Wiederherstellungsschlüssel</h2>
      <p class="feld-hinweis">20 Zeichen in fünf Vierergruppen — beim Eintippen
         sind Leerzeichen, Bindestriche und Groß/Klein egal.</p>
      <?php /* `.blatt-wert` ist der Baustein des Schluesselblatts (S10): Er
               haelt den Wert vom Zeilenumbruch frei und verbietet den
               Seitenumbruch mitten darin. Ein halb abgetippter Schluessel
               sieht aus wie ein falscher. */ ?>
      <p class="codeblock-wert blatt-wert"><?= $h($code) ?></p>

      <h2>Was dieses Blatt kann — und was ohne es verloren ist</h2>
      <p>Deine Patientendaten verschlüsselt der Browser mit einem Schlüssel,
         der aus deinem Passwort entsteht. Der Betreiber hat diesen Schlüssel
         nicht. Vergisst du dein Passwort, ist dieses Blatt der
         <strong>einzige</strong> Weg zurück: Mit dem
         Wiederherstellungsschlüssel setzt du ein neues Passwort und behältst
         alle Daten.</p>
      <p>Ohne Blatt und ohne Passwort kann niemand die verschlüsselten
         Einsätze öffnen — die Zeiten, Orte und Rettungsmittel bleiben, die
         Patientendaten sind weg.</p>

      <h2>So benutzt du es</h2>
      <ol>
        <li>Anmeldeseite → <em>Passwort vergessen?</em> → Adresse eintragen.</li>
        <li>Den Link aus der Mail öffnen und den Schlüssel von diesem Blatt
            eintippen.</li>
        <li>Neues Passwort setzen. Danach bekommst du einen
            <strong>neuen</strong> Schlüssel und ein neues Blatt — dieses hier
            ist dann ungültig.</li>
      </ol>

      <h2>Wann es ungültig wird</h2>
      <p>Sobald du unter <strong>Einstellungen → Konto</strong> den Schlüssel
         erneuerst, oder nach einer Wiederherstellung. NAdoku fragt dich nach
         30 Tagen, nach 6 Monaten und dann jährlich, ob du dieses Blatt noch
         hast.</p>

<?php endif; ?>

      <?php /* NUR AM BILDSCHIRM. Dieses Blatt ist zum Drucken da — der Weg
               ueber das Browsermenue ist vorhanden, aber nicht der, den
               jemand geht, der die Seite zum ersten Mal sieht. Derselbe
               Knopf und dasselbe Skript wie beim Schluesselblatt (S10);
               `.nur-bildschirm` nimmt beides aus dem Ausdruck.

               DER RUECKWEG HAENGT DARAN, OB JEMAND ANGEMELDET IST. Beim
               ERSTEN Setzen des Passworts ist er es nicht — dann fuehrt der
               Knopf zur Anmeldung, und das ist genau der naechste Schritt:
               Blatt drucken, dann anmelden. */ ?>
      <p class="feld-hinweis nur-bildschirm">
        <?php if ($code !== null): ?><?php /* KEIN DRUCKKNOPF OHNE SCHLUESSEL.
             Im Ohne-Code-Zweig gibt es nichts zu drucken; der Knopf stuende
             da und truege einen leeren Zettel aus dem Drucker. Aufgefallen
             am Bild `02g-notfallblatt-ohne`. */ ?>
        <button type="button" class="knopf knopf-primaer" data-drucken hidden><span>Drucken</span></button>
        <?php endif; ?>
        <a class="knopf knopf-leise" href="<?= $angemeldet ? 'index.php' : 'login.php' ?>"><span><?= $angemeldet ? 'Zur Startseite' : 'Zur Anmeldung' ?></span></a>
      </p>

      <p class="text-stand">Notfallblatt · NAdoku ist freie Software (AGPL v3)</p>

    </div>
  </main>
</div>
<script src="<?= $h($v('assets/blatt-drucken.js')) ?>"></script>
</body>
</html>
