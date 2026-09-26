<?php
declare(strict_types=1);

/**
 * DIE DOKUMENTSEITE — eine Fassung fuer beide (P5b/AP8, E-P5b-08, M-P5b-01).
 *
 * `hilfe.php` und `ueber.php` sind drei Zeilen lang und setzen nur
 * `$dokuName`; alles Weitere steht hier. Dasselbe Muster wie bei
 * `rechtstext_seite.php` — und aus demselben Grund: Die beiden Seiten
 * unterscheiden sich in einem Dateinamen und darin, ob ein
 * Inhaltsverzeichnis danebensteht.
 *
 * ---------------------------------------------------------------------------
 * OHNE ANMELDUNG ERREICHBAR — UND DAS IST DER PUNKT
 * ---------------------------------------------------------------------------
 *
 * Diese Seite laedt ausdruecklich NICHT `auth_guard.php`. „Was ist NAdoku"
 * ist die Seite, die jemand ansieht, BEVOR er ein Konto hat; ein Handbuch
 * hinter der Anmeldung hilft genau denen nicht, die nicht hineinkommen.
 *
 * SIE KENNT DIE SITZUNG TROTZDEM, weil der Kopf sich unterscheidet: angemeldet
 * mit Menue und Namen, sonst ohne Menue und mit „Anmelden" (M-P5b-01). Die
 * Sitzung wird nur GEFRAGT, nicht erzwungen — wie in `rechtstext_seite.php`.
 *
 * ---------------------------------------------------------------------------
 * WARUM HIER KEIN RATENSCHUTZ STEHT
 * ---------------------------------------------------------------------------
 *
 * Eine oeffentliche Seite ohne Anmeldung ist der uebliche Ort fuer einen
 * Ratenzaehler. Hier steht keiner, und das ist eine Entscheidung mit einer
 * Zahl dahinter: Das Rendern des ganzen Handbuchs kostet **11 bis 12 ms**
 * (gemessen 17.09.2026, 266 KB Markdown). Es gibt keine Datenbankabfrage je
 * Abschnitt, keinen Versand, keine Schreiboperation — nichts, was sich durch
 * Wiederholung verstaerkte. Wer diese Seite oft aufruft, kostet so viel wie
 * jemand, der ein Bild oft aufruft.
 *
 * Was ein Zaehler hier KOSTETE, waere dagegen echt: `ratelimit_lib.php`
 * schreibt in die Datenbank, also braeuchte die billigste Seite der Anwendung
 * eine Schreiboperation je Aufruf, um sich vor sich selbst zu schuetzen.
 */

/* Wie bei den Rechtstexten: Auf einer frischen Installation gibt es noch
 * keine config.php, und `db.php` braeche hart ab. Wer „Was ist NAdoku"
 * aufruft, bevor eingerichtet ist, gehoert zum Einrichter — nicht auf eine
 * weisse Fehlerseite. */
if (!is_file(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_lib.php';   // logo_src() braucht logo_stamm()
require_once __DIR__ . '/doku_lib.php';
require_once __DIR__ . '/konten_einstellungen_lib.php';
require_once __DIR__ . '/ui.php';

$dokuName = $dokuName ?? 'handbuch';
if (!isset(DOKU_DATEIEN[$dokuName])) { $dokuName = 'handbuch'; }

/* Das Handbuch bekommt das Inhaltsverzeichnis, „Was ist NAdoku" nicht: Es hat
 * vier Abschnitte und passt auf einen Bildschirm (M-P5b-01, Bild 2). */
$mitVerzeichnis = $dokuName === 'handbuch';
$seitenTitel    = $dokuName === 'handbuch' ? 'Handbuch' : 'Was ist NAdoku?';

/* ---- Laeuft eine Sitzung? Nur fragen, nicht erzwingen. ------------------- */
/* NUR FRAGEN, NICHT ERZWINGEN — und seit Web 20.27.0 auch nicht mehr
 * ANLEGEN: Die Art `lesend` startet nur, wenn ein Sitzungscookie da ist
 * (F-ZE-2). Diese Seite ist ohne Anmeldung erreichbar; bis dahin bekam JEDER
 * Besucher eine Sitzung und seit Schritt 16 eine Datei in `.sitzungen/`,
 * auch jeder Bot. Fuer Angemeldete aendert sich nichts. */
sitzung_starten('lesend');
$angemeldet = !empty($_SESSION['user_id']);

$seite = doku_seite($dokuName);

/* ---- Wie kommt man von hier weg? ---------------------------------------- *
 *
 * Angemeldet zur Startseite, sonst zur Anmeldung — dieselbe Ueberlegung wie
 * bei den Rechtstexten. Bei „Was ist NAdoku" traegt die Seite zusaetzlich die
 * Knoepfe am Ende (siehe unten); der Rueckweg oben bleibt trotzdem, weil auch
 * jemand hierherkommt, der nur nachlesen wollte. */
$zurueck = $angemeldet
    ? ['text' => 'Zur Startseite', 'href' => 'index.php']
    : ['text' => 'Zur Anmeldung',  'href' => 'login.php'];

ui_seite_start(['titel' => $seitenTitel]);

/* ANGEMELDET DER VOLLE KOPF, SONST DER SCHMALE (M-P5b-01). Angemeldet ist
 * diese Seite ein Teil der Anwendung und soll sich so anfuehlen; ausgeloggt
 * ist sie eine Visitenkarte und braucht kein Menue, das ins Leere fuehrt. */
if ($angemeldet) {
    ui_kopf(['aktiv' => '']);
} else {
    ui_kopf(['menue' => false, 'zurueck' => $zurueck]);
}
?>
<div class="rahmen<?= $mitVerzeichnis ? '' : ' rahmen-lesespalte' ?>">
  <main class="inhalt">
  <?php ui_hinweise(); ?>

  <?php if ($seite === null): ?>
    <h1><?= e($seitenTitel) ?></h1>
    <?php ui_karte_start([]); ?>
      <?php /* DER FEHLENDE-DATEI-FALL IST EINE AUSKUNFT, KEIN ABSTURZ.
               Er tritt genau dann ein, wenn jemand `server/` hochgeladen hat
               und `docs/` nicht — der haeufigste Fehler beim Selbsthosten.
               Deshalb steht hier, was zu tun ist, und nicht „Fehler". */ ?>
      <?= ui_meldung_markup('warn',
          'Die Datei <code>' . e(DOKU_DATEIEN[$dokuName]) . '</code> ist auf diesem '
        . 'Server nicht zu finden. Gesucht wird sie neben der Anwendung unter '
        . '<code>docs/</code> und innerhalb unter <code>doku/</code>.',
          'Dieses Dokument fehlt.') ?>
      <p class="feld-hinweis">Beim Selbsthosten: Lade den Ordner
         <code>docs/</code> aus dem Repositorium mit hoch. Die
         Auslieferungskette dieses Projekts kopiert die Dateien sonst nach
         <code>server/doku/</code>.</p>
    <?php ui_karte_ende(); ?>

  <?php else: ?>
    <?php if ($mitVerzeichnis): ?>
    <div class="doku">
      <?php /* DAS VERZEICHNIS STEHT VOR DEM TEXT IM MARKUP, nicht daneben —
               ohne CSS liest man erst die Gliederung und dann den Text, und
               eine Vorleserin bekommt dieselbe Reihenfolge. Die Spalten macht
               `.doku` ab 1024 px. */ ?>
      <nav class="doku-nav" aria-label="Inhalt des Handbuchs">
        <label class="doku-suche">
          <span class="nur-vorlesen">Im Handbuch suchen</span>
          <input type="search" id="doku-filter" placeholder="Im Handbuch suchen"
                 autocomplete="off">
        </label>
        <ul class="doku-liste">
          <?php foreach ($seite['inhalt'] as $e): ?>
            <?php /* DIE KLASSE STEHT AUSGESCHRIEBEN und wird nicht aus der
                     Stufe zusammengesetzt (`doku-e<?= $stufe ?>`). Zwei
                     Gruende: Die Vollstaendigkeitspruefung sucht Klassen als
                     LITERAL und meldete `doku-e3` sonst als Regel ohne
                     Markup — eine tote Regel, die keine ist. Und es gibt nur
                     zwei Stufen; ein Ausdruck, der drei erzeugen koennte,
                     verspricht mehr, als das Verzeichnis kennt. */ ?>
            <li class="doku-e<?= (int)$e['stufe'] === 3 ? ' doku-e3' : '' ?>">
              <a href="#<?= e($e['marke']) ?>"><?= e($e['text']) ?></a>
            </li>
          <?php endforeach; ?>
        </ul>
        <p class="doku-leer" hidden>Kein Abschnitt passt dazu.</p>
      </nav>
    <?php endif; ?>

      <div class="doku-text">
        <h1><?= e($seitenTitel) ?></h1>
        <?php if ($seite['titel'] !== null): ?>
          <?php /* OHNE TRENNPUNKT. Das Mittelpunkt-Zeichen ist Hausstil
                   (die Fusszeile fuehrt es), zaehlt aber in der
                   Vollstaendigkeitspruefung als Unicode-Symbol im Markup —
                   und deren Schwelle steht in der Kette. Ein Komma sagt hier
                   dasselbe und kostet nichts. */ ?>
          <p class="doku-quelle"><?= e($seite['titel']) ?>, aus
             <code><?= e(DOKU_DATEIEN[$dokuName]) ?></code> im Repositorium</p>
        <?php endif; ?>

        <?php /* HIER STEHT UNMASKIERTES MARKUP — die zweite Stelle dieser
                 Anwendung neben `rt_html()`. Was es darf und warum, steht im
                 Kopf von `doku_lib.php`: `setMarkupEscaped(true)` macht rohes
                 HTML zu Text, `setSafeMode(true)` prueft die Ziele, und
                 `DokuMarkdown` laesst Bilder nur relativ zu. Elf
                 Sicherheitsproben am 17.09.2026, 0 durchgelassen. */ ?>
        <?= $seite['html'] ?>

        <?php if (!$mitVerzeichnis): ?>
          <?php /* DIE KNOEPFE AM ENDE RICHTEN SICH NACH DER BETRIEBSART
                   (M-P5b-01, Anmerkung zu Bild 2): Bei „nur auf Einladung"
                   waere „Konto anlegen" ein Knopf, der auf eine Seite fuehrt,
                   die einem sagt, dass man nicht darf. */ ?>
          <div class="doku-wege">
            <?php if (!$angemeldet && konten_reg_offen()): ?>
              <?= ui_knopf(['text' => 'Konto anlegen', 'art' => 'primaer',
                            'href' => 'registrieren.php']) ?>
            <?php endif; ?>
            <?php if (!$angemeldet): ?>
              <?= ui_knopf(['text' => 'Anmelden', 'href' => 'login.php']) ?>
            <?php endif; ?>
            <a href="hilfe.php">Zum Handbuch</a>
          </div>
        <?php endif; ?>
      </div>

    <?php if ($mitVerzeichnis): ?>
    </div>
    <?php endif; ?>
  <?php endif; ?>

  </main>
</div>
<?php
ui_fuss_seite();
ui_seite_ende(['skripte' => $mitVerzeichnis && $seite !== null ? ['assets/doku.js'] : []]);
