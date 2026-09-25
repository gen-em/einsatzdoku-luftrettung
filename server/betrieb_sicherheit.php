<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/ratelimit_lib.php';
// BEHEBUNG, NICHT FORMSACHE (AP7, R83): Bis Web 20.31.0 rief diese Seite
// edbak_groesse_text(), ohne adminbackup_lib.php je einzubinden — die Funktion
// war nur da, weil ui_seite_start() das Menue baut und
// ui_einstellungen_punkte() dafuer status_lib.php nachlaedt. Aus genau diesem
// Befund ist dieses Paket entstanden; jetzt haengt die Seite an der Sache selbst.
require_once __DIR__ . '/format_lib.php';

/**
 * BETRIEB -> STATUS -> SICHERHEIT (P5a/AP8, E-P5a-08).
 *
 * WOFUER ES DIESE SEITE GIBT. Der Ratenschutz aus AP6 und die Mengenbremse
 * aus AP7 arbeiten still. Auf der Statusseite stehen davon drei Zeilen —
 * „Verlangsamung", „Gesperrt", „Abgewiesene Geraete" —, und die sagen, DASS
 * etwas ist. Wer wissen will, WER gesperrt ist, seit wann, auf welcher
 * Sprosse, und wer es wieder aufheben will, fand bis Web 20.11.0 nichts.
 *
 * WARUM EINE UNTERSEITE UND KEIN MENUEPUNKT (E-P5a-08). Fuer eine
 * BetreiberIn stehen siebzehn Eintraege in der Leiste; ein achtzehnter fuer
 * eine Seite, die man an guten Tagen nie braucht, waere an der falschen
 * Stelle teuer. Die Seite haengt deshalb an Status, wo die drei Zeilen
 * stehen, die hierher fuehren. `ui_geruest_start(['menue' => 'betrieb_status'])`
 * haelt den Eintrag „Status" aktiv — dasselbe Muster wie `admin_user.php`
 * unter `admin_users.php`. In `ui_einstellungen_punkte()` ist nichts
 * einzutragen.
 *
 * SIE AENDERT ETWAS, UND ZWAR GENAU EINES: Eine Sperre aufheben. Das ist der
 * Unterschied zur Elternseite, die rein liest. Er ist gewollt — eine
 * Kollegin, die sich ausgesperrt hat, ruft an, und die Betreiberin soll ihr
 * helfen koennen, ohne in die Datenbank zu greifen.
 *
 * -------------------------------------------------------------------------
 * WAS HIER IM KLARTEXT STEHT, UND WARUM DAS EINE ENTSCHEIDUNG IST
 * -------------------------------------------------------------------------
 *
 * IP-Adressen und E-Mail-Adressen, beides unverschluesselt (E-P5a-08,
 * E-P5a-46). Ohne sie waere die Liste „irgendwo war irgendwer gesperrt" und
 * damit wertlos: Man kann eine Sperre nicht aufheben, ohne zu wissen, welche.
 *
 * DIE FOLGE WIRD BENANNT, NICHT UEBERGANGEN: Die Liste fuehrt IP- und
 * E-Mail-Adressen, und der Datenschutztext der Installation gehoert deshalb
 * nachgezogen; einen Textbaustein dafuer schlaegt `admin_rechtstexte.php`
 * (Reiter Datenschutzerklärung) vor — die Anwendung liefert keinen
 * Rechtstext mit, R32, sie kann nur vorschlagen. BIS WEB 20.38.0 stand hier
 * auch, `sicherheit_ereignisse` fahre in jeder Komplettsicherung mit; seit
 * P5c/AP2 geht die Tabelle OHNE ZEILEN hinein (`KOMP_OHNE_ZEILEN`).
 *
 * -------------------------------------------------------------------------
 * SECHS KARTEN, WIE E-P5a-08 SIE NENNT
 * -------------------------------------------------------------------------
 *
 * Bis P5a/AP10 waren es fuenf — „Loeschungen auf Sicherungszielen" kam erst
 * mit der Loeschregel je Ziel (E-P5a-03), denn eine Karte, die sagt „hier
 * steht noch nichts, weil es die Sache noch nicht gibt", ist keine Auskunft,
 * sondern Laerm. Hier stand bis Web 21.1.0 noch „fuenf, nicht sechs".
 *
 * -------------------------------------------------------------------------
 * NUR VORHANDENE BAUSTEINE (Konzept 2.5)
 * -------------------------------------------------------------------------
 *
 * Karte, Zeile, Plakette, Zeilenaktionen, Meldung, Knopf, Wertekasten. Keine
 * TABELLE, obwohl die Mockup-Skizze fuer die erste Karte eine nennt:
 * `docs/Design.md` 9.0 fuehrt „eine Liste von Eintraegen" ausdruecklich auf
 * `ui_zeile()` in einer Karte und die `<table>` unter „nicht"; die Tabelle
 * ist dort fuer „Zahlen nebeneinander vergleichen" vorgesehen und ist im
 * Bestand ueberhaupt kein Baustein, sondern rohes Markup auf sechs Seiten.
 * Eine Liste mit einer Handlung je Eintrag ist der Fall, fuer den
 * `ui_zeilenaktionen()` gebaut ist.
 *
 * DREI FALLEN DES VORRATS, alle nachgelesen und alle hier umgangen:
 *
 *   1. EINE EINGEKLAPPTE KARTE ZEIGT WEDER PLAKETTE NOCH KOPFAKTION.
 *      `ui_karte_start()` kehrt im `<details>`-Zweig zurueck, bevor beides
 *      ausgegeben wird. Wer `vorschau` setzt, verliert die Plakette still —
 *      ohne Fehlermeldung. Die Karten hier sind deshalb offen; was in die
 *      Plakette gehoert, steht in der Vorschau nicht noch einmal.
 *   2. `ui_knopf()` KENNT KEIN `form`. Der Schluessel, mit dem ein Knopf ein
 *      Formular ausserhalb seiner selbst absendet, gibt es nur in
 *      `ui_zeilenaktionen()`. Ein `ui_knopf(['form' => ...])` ergaebe einen
 *      Knopf, der nichts tut.
 *   3. `href_ganz` SCHLIESST AKTIONEN AUS. Eine Zeile, die als Ganzes ein
 *      Link ist, zeigt rechts den Winkel und verschluckt `aktionen`.
 */

require_once __DIR__ . '/mail_lib.php';

/* ---- Aufheben ------------------------------------------------------------
 *
 * `rate_sperre_aufheben()` UND NICHT `rate_erfolg()`: Jene bildet die
 * Merkmale aus dem AUFRUFER — hier waere das die Adresse der Betreiberin.
 * Der Knopf loeschte ihre eigene Zeile, meldete Erfolg, und die Sperre bliebe
 * stehen. Die Begruendung steht ausgeschrieben im Kopf der Funktion.
 *
 * `$wer` IST DIE KONTOKENNUNG DER HANDELNDEN. Ohne sie bliebe die Spalte
 * `wer` in `sicherheit_ereignisse` leer, und das Ereignis „aufgehoben" sagte
 * nicht, wer aufgehoben hat — also genau das, wofuer es da ist. */
$meldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'aufheben') {
    csrf_check();
    $topf    = (string)($_POST['topf'] ?? '');
    $merkmal = (string)($_POST['merkmal'] ?? '');
    if ($topf === '' || $merkmal === '') {
        $meldung = ['fehler', 'Es war nicht zu erkennen, welche Sperre gemeint war.'];
    } elseif (rate_sperre_aufheben($topf, $merkmal, (string)$userEmail)) {
        $meldung = ['ok', 'Die Sperre ist aufgehoben. Der Vorgang steht unten '
                        . 'in den Ereignissen — mit deinem Namen.'];
    } else {
        /* KEINE FEHLERMELDUNG, SONDERN EINE AUSKUNFT. Der haeufigste Grund
         * ist, dass die Sperre zwischen Anzeige und Klick von selbst
         * abgelaufen ist; das ist kein Fehler, sondern das gewuenschte Ende. */
        $meldung = ['warn', 'Diese Sperre gibt es nicht mehr — vermutlich ist sie '
                          . 'abgelaufen, während die Seite offen stand.'];
    }
}

/* ---- Erheben -------------------------------------------------------------
 *
 * NACH dem Aufheben, damit die Liste den Stand NACH dem Klick zeigt.
 * Dieselbe Reihenfolge und derselbe Grund wie bei der Testmail auf der
 * Elternseite. */
$sperren   = rate_sperren_aktiv(200);
$bremse    = rate_verlangsamung(true);
$ereign    = sicherheit_ereignisse([], null, 200);
$geraete   = sicherheit_bremse_geraete();
/* Die sechste Karte (P5a/AP10). Sie hat in AP8 gefehlt, weil es weder
 * Tabelle noch Schreibweg gab — beides entsteht mit der Aufbewahrungsregel. */
require_once __DIR__ . '/sicherungsziel_lib.php';
$loeschungen = sz_loeschungen();
$mailregel = sicherheit_mailregel();

$ingestEreignisse = array_values(array_filter($ereign['zeilen'],
    static fn(array $z): bool => in_array($z['topf'], ['ingest', 'ingest_ip'], true)));
$phasen = array_values(array_filter($ereign['zeilen'],
    static fn(array $z): bool => $z['art'] === 'verlangsamung'));

/** Ein Merkmal so schreiben, wie ein Mensch es liest. */
$merkmalText = static function (string $m): string {
    if (str_starts_with($m, 'id:'))  { return substr($m, 3); }
    if (str_starts_with($m, 'ip:'))  { return substr($m, 3); }
    return $m === RATE_GLOBAL_MERKMAL ? 'die ganze Installation' : $m;
};

/** Eine Restzeit in Worten. Minuten genügen — auf die Sekunde kommt es nicht an. */
$restText = static function (int $s): string {
    if ($s <= 60) { return 'unter einer Minute'; }
    $m = (int)round($s / 60);
    return $m < 60 ? 'noch ' . $m . ' Minuten'
                   : 'noch ' . (int)round($m / 60) . ' Stunden';
};

ui_seite_start(['titel' => 'Sicherheit']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_status']); ?>

  <?php ui_titelzeile([
      'zurueck' => ['text' => 'Status', 'href' => 'betrieb_status.php'],
      'titel'   => 'Sicherheit',
      'unter'   => 'Wer gerade ausgesperrt ist, was in den letzten 30 Tagen '
                 . 'geschehen ist — und der eine Knopf, der etwas ändert.',
  ]); ?>

  <?= wartung_balken() ?>

  <?php if ($meldung): ?>
    <?= ui_meldung_markup($meldung[0], $meldung[1]) ?>
  <?php endif; ?>

  <?php /* EIN FORMULAR JE ZEILE, und nicht eines mit versteckten Feldern, die
           ein Skript füllt. Die Elternseite gibt ihr Testmail-Formular einmal
           aus, weil es keine Zeile betrifft; hier trägt jede Zeile ihren
           eigenen Topf und ihr eigenes Merkmal. Ein gemeinsames Formular
           bräuchte JavaScript, um beides vor dem Absenden zu setzen — und
           damit einen Knopf, der ohne Skript nichts tut. Bei höchstens 200
           Sperren wiegen 200 versteckte Formulare nichts. */ ?>

  <?php /* ---- 1. Aktive Sperren ------------------------------------------ */ ?>
  <?php ui_karte_start(['titel' => 'Aktive Sperren', 'id' => 'k-sperren',
      'zahl' => count($sperren) > 0 ? (string)count($sperren) : null]); ?>
    <?php if ($sperren === []): ?>
      <p class="feld-hinweis">Zurzeit ist nichts gesperrt — der Normalfall.</p>
    <?php else: ?>
      <p class="feld-hinweis">Eine Sperre läuft von selbst ab; aufheben nur, wenn
         jemand jetzt hereinmuss.
         <a href="hilfe.php#11-4b-sicherheit-wer-ausgesperrt-ist-und-wie-man-ihn-wieder-hereinlaesst">Handbuch: Sicherheit</a></p>
      <?php foreach ($sperren as $i => $sp): ?>
        <form method="post" action="betrieb_sicherheit.php" id="f-auf-<?= $i ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="action" value="aufheben">
          <input type="hidden" name="topf" value="<?= e($sp['topf']) ?>">
          <input type="hidden" name="merkmal" value="<?= e($sp['merkmal']) ?>">
        </form>
        <?php ui_zeile([
            'text'  => $merkmalText($sp['merkmal']),
            'klein' => ($sp['art'] === 'konto' ? 'Kontokennung' : 'Anschluss')
                     . ' · Topf ' . $sp['topf']
                     . ' · Stufe ' . $sp['stufe']
                     . ' · bis ' . datum_zeit_text($sp['bis']) . ' Uhr ('
                     . $restText($sp['rest']) . ')'
                     . ' · ' . $sp['versuche'] . ' Fehlversuche im Fenster',
            'plaketten' => ui_plakette('Stufe ' . $sp['stufe'],
                ['ton' => $sp['stufe'] >= rate_stufe_hoechste() ? 'orange' : 'blau']),
            'aktionen' => ui_zeilenaktionen([
                'titel'     => $merkmalText($sp['merkmal']),
                'eintraege' => [[
                    'text'   => 'Aufheben',
                    'symbol' => 'schliessen',
                    'art'    => 'gefahr',
                    'form'   => 'f-auf-' . $i,
                    'attr'   => ' data-confirm="Sperre für „' . e($merkmalText($sp['merkmal']))
                              . '“ jetzt aufheben? Der Zähler wird gelöscht; ein '
                              . 'weiterer Fehlversuch fängt wieder bei Stufe 1 an."'
                              . ' data-confirm-ok="Aufheben"',
                ]],
            ]),
        ]); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- 2. Verlangsamung ------------------------------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Verlangsamung', 'id' => 'k-bremse-global',
      'plakette' => $bremse['stufe'] > 0
          ? ui_plakette('Stufe ' . $bremse['stufe'], ['ton' => 'orange'])
          : ui_plakette('ruhig', ['ton' => 'blau'])]); ?>
    <?php if ($bremse['stufe'] > 0): ?>
      <?= ui_meldung_markup('warn', 'Jede <strong>fehlgeschlagene</strong> Anmeldung '
          . 'wartet zurzeit ' . e(rtrim(rtrim(number_format($bremse['sekunden'], 1, ',', ''), '0'), ','))
          . ' Sekunden. Gezählt sind ' . (int)$bremse['versuche'] . ' Fehlversuche in den '
          . 'letzten 15 Minuten. <strong>Wer das richtige Passwort hat, kommt '
          . 'ohne Verzögerung durch</strong>, und wer schon gesperrt ist, wird gar '
          . 'nicht erst verlangsamt.', 'Die Bremse läuft.') ?>
    <?php else: ?>
      <p class="feld-hinweis">Die Anmeldung antwortet normal; die Bremse greift
         erst ab <?= (int)(rate_bremse_schwellen()[0] ?? 200) ?> Fehlversuchen je
         15 Minuten über die ganze Installation.</p>
    <?php endif; ?>

    <?php /* DIE PHASEN SIND ANSTIEGE, KEINE ZEITRAEUME — und das steht hier,
             weil eine Karte, die „Phasen" verspricht und Punkte zeigt, eine
             falsche Auskunft gibt. Vermerkt wird, WENN DIE STUFE STEIGT
             (`sicherheit_verlangsamung_vermerken()`); ein Ende hat kein
             eigenes Ereignis. Daher „seit", nicht „von bis". */ ?>
    <?php if ($phasen === []): ?>
      <p class="feld-hinweis">In den letzten 30 Tagen ist die Bremse nicht
         angesprungen.</p>
    <?php else: ?>
      <?php foreach ($phasen as $p): ?>
        <?php ui_zeile([
            'text'  => 'Stufe ' . $p['stufe'] . ' erreicht',
            'klein' => datum_zeit_text($p['zeitpunkt']) . ' Uhr'
                     . ($p['versuche'] !== null
                        ? ' · ' . $p['versuche'] . ' Fehlversuche je 15 Minuten' : ''),
            'plaketten' => ui_plakette('Stufe ' . $p['stufe'], ['ton' => 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php endif; ?>
    <p class="feld-klein"><a href="hilfe.php#sicherheit-schwellen-aufraeumen-mailregel">Handbuch: wann die Bremse greift und was vermerkt wird</a></p>
  <?php ui_karte_ende(); ?>

  <?php /* ---- 3. Mengenbremse -------------------------------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Mengenbremse der Geräte', 'id' => 'k-bremse',
      'zahl' => count($geraete) > 0 ? (string)count($geraete) : null]); ?>
    <p class="feld-hinweis">Ab 30 fehlgeschlagenen Geräteanmeldungen je
       Viertelstunde greift dieselbe Leiter wie bei der Anmeldung — trifft es ein echtes
       Gerät, hilft neu koppeln.
       <a href="hilfe.php#sicherheit-schwellen-aufraeumen-mailregel">Handbuch: Mengenbremse</a></p>
    <?php if ($geraete === []): ?>
      <p class="feld-hinweis">Kein Gerät hat abgewiesene Anmeldungen.</p>
    <?php else: ?>
      <?php foreach ($geraete as $g): ?>
        <?php ui_zeile([
            'text'  => $g['name'],
            'klein' => $g['anzahl'] . ' abgewiesene Anmeldungen'
                     . ($g['seit'] !== null
                        ? ' seit ' . datum_zeit_text($g['seit']) . ' Uhr' : ''),
            'plaketten' => ui_plakette((string)$g['anzahl'], ['ton' => 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php if ($ingestEreignisse !== []): ?>
      <p class="feld-klein"><strong>Gesperrte Geräte und Adressen der letzten
         30 Tage:</strong></p>
      <?php foreach ($ingestEreignisse as $z): ?>
        <?php ui_zeile([
            'text'  => $merkmalText((string)$z['merkmal']),
            'klein' => ($z['topf'] === 'ingest' ? 'Gerätekennung' : 'Anschluss')
                     . ' · ' . datum_zeit_text($z['zeitpunkt']) . ' Uhr'
                     . ' · Stufe ' . $z['stufe'],
            'plaketten' => ui_plakette($z['art'] === 'aufgehoben' ? 'aufgehoben' : 'gesperrt',
                ['ton' => $z['art'] === 'aufgehoben' ? 'neutral' : 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- 4. Ereignisse ---------------------------------------------- */ ?>
  <?php /* DIE KURZSICHT BLEIBT, DIE GANZE LISTE STEHT IM PROTOKOLL (P5c/AP2,
           E-P5c-10): Hier wird aufgehoben; gesucht, gefiltert und geblättert
           wird im Reiter Sicherheit, zusammen mit den CSP-Berichten. */ ?>
  <?php ui_karte_start(['titel' => 'Ereignisse der letzten 30 Tage', 'id' => 'k-ereignisse',
      'zahl' => $ereign['gesamt'] > 0 ? (string)$ereign['gesamt'] : null,
      'aktion' => ['text' => 'Im Protokoll', 'href' => 'admin_protokoll.php?r=sicherheit']]); ?>
    <?php /* WAS HIER NICHT STEHT, UND ZWAR AUSDRUECKLICH: Ein Sperrereignis
             entsteht nur an den fuenf Toepfen MIT LEITER — `login`,
             `login_ip`, `salt`, `ingest`, `ingest_ip`. Die uebrigen neun
             (Kopplung, Reset, Demo, Testmail, CSP) sperren ueber den
             Rueckfallweg OHNE Protokollzeile. Ohne diesen Satz liest sich
             eine kurze Liste als „es war fast nichts", obwohl neun Toepfe gar
             nicht berichten. */ ?>
    <p class="feld-hinweis">Vermerkt werden nur die fünf Töpfe mit Sperrleiter,
       nicht jede Sperre.
       <a href="hilfe.php#11-4b-sicherheit-wer-ausgesperrt-ist-und-wie-man-ihn-wieder-hereinlaesst">Handbuch: welche Töpfe</a></p>
    <?php if ($ereign['zeilen'] === []): ?>
      <p class="feld-hinweis">Keine Ereignisse in den letzten 30 Tagen.</p>
    <?php else: ?>
      <?php foreach ($ereign['zeilen'] as $z): ?>
        <?php
          $ton = match ($z['art']) {
              'aufgehoben'    => 'neutral',
              'verlangsamung' => 'orange',
              default         => $z['stufe'] >= rate_stufe_hoechste() ? 'orange' : 'blau',
          };
          $was = match ($z['art']) {
              'aufgehoben'    => 'aufgehoben',
              'verlangsamung' => 'Verlangsamung',
              default         => 'gesperrt',
          };
        ?>
        <?php ui_zeile([
            'text'  => $merkmalText((string)($z['merkmal'] ?? '')),
            'klein' => datum_zeit_text($z['zeitpunkt']) . ' Uhr'
                     . ($z['topf'] !== null ? ' · Topf ' . $z['topf'] : '')
                     . ($z['stufe'] > 0 ? ' · Stufe ' . $z['stufe'] : '')
                     . ($z['bis'] !== null
                        ? ' · bis ' . datum_zeit_text($z['bis']) . ' Uhr' : '')
                     . ($z['wer'] !== null && $z['wer'] !== ''
                        ? ' · durch ' . $z['wer'] : ''),
            'plaketten' => ui_plakette($was, ['ton' => $ton]),
        ]); ?>
      <?php endforeach; ?>
      <?php if ($ereign['gesamt'] > count($ereign['zeilen'])): ?>
        <p class="feld-klein">die jüngsten <?= count($ereign['zeilen']) ?> von
           <?= $ereign['gesamt'] ?></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- 5. Löschungen auf Sicherungszielen (P5a/AP10, E-P5a-08) ----
           SIE HAT IN AP8 GEFEHLT, und zwar mit Ansage: Es gab damals weder
           Tabelle noch Schreibweg noch einen `app_state`-Schlüssel, aus dem
           sich etwas hätte zeigen lassen. Eine Karte, die sagt „hier steht
           noch nichts, weil es die Sache noch nicht gibt", wäre kein Befund
           gewesen, sondern Lärm.

           SIE STEHT AUF DIESER SEITE UND NICHT BEI DEN ZIELEN, weil sie
           dieselbe Frage beantwortet wie ihre Nachbarn: Was hat diese
           Installation getan, das jemand nachvollziehen können muss? Eine
           Löschung auf einer fremden Maschine ist genau das. */ ?>
  <?php ui_karte_start(['titel' => 'Löschungen auf Sicherungszielen',
      'id' => 'k-ziele',
      'zahl' => $loeschungen['gesamt'] > 0 ? (string)$loeschungen['gesamt'] : null]); ?>
    <p class="feld-hinweis">Auf einem Ziel gelöscht wird nur, wo die
       Aufbewahrungsregel eingeschaltet ist, und nur, was diese Installation
       selbst dorthin geschickt hat.
       <a href="hilfe.php#12-7-backup-ziele">Handbuch: Backup-Ziele</a></p>
    <?php if ($loeschungen['zeilen'] === []): ?>
      <p class="feld-hinweis">In den letzten 30 Tagen ist auf keinem Ziel etwas
         entfernt worden.</p>
    <?php else: ?>
      <?php foreach ($loeschungen['zeilen'] as $z): ?>
        <?php ui_zeile([
            'text'  => (string)($z['ziel'] ?? '—') . ' · ' . (string)$z['ordner'],
            'klein' => (string)$z['datei'] . ' · '
                     . groesse_text((int)$z['bytes']) . ' · '
                     . datum_zeit_text((string)$z['geloescht_am']) . ' Uhr'
                     . ((string)($z['grund'] ?? '') !== ''
                        ? ' · ' . (string)$z['grund'] : ''),
            'plaketten' => ui_plakette('entfernt', ['ton' => 'neutral']),
        ]); ?>
      <?php endforeach; ?>
      <?php if ($loeschungen['gesamt'] > count($loeschungen['zeilen'])): ?>
        <p class="feld-klein">die jüngsten <?= count($loeschungen['zeilen']) ?> von
           <?= $loeschungen['gesamt'] ?></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- 6. Mailregel ----------------------------------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Meldung per Mail', 'id' => 'k-mail',
      'plakette' => $mailregel['an']
          ? ui_plakette('an', ['ton' => 'blau'])
          : ui_plakette('aus', ['ton' => 'neutral'])]); ?>
    <p class="feld-hinweis">Erreicht eine Sperre die letzte Sprosse (Stufe
       <?= (int)$mailregel['stufe'] ?>) oder die Verlangsamung ihre vierte Stufe,
       geht höchstens eine Sammelmeldung je Stunde hinaus.
       <a href="hilfe.php#sicherheit-schwellen-aufraeumen-mailregel">Handbuch: Meldung per Mail</a></p>
    <?php ui_zeile([
        'text'  => $mailregel['an'] ? 'Meldung ist eingeschaltet' : 'Meldung ist abgeschaltet',
        'klein' => 'Umstellen unter Betrieb → Servereinstellungen, Karte „Ratenschutz"',
        'href'  => 'betrieb_server.php#k-ratenschutz',
    ]); ?>
    <?php ui_zeile([
        'text'  => $mailregel['zuletzt'] !== null
                 ? 'Zuletzt gemeldet am ' . datum_zeit_text($mailregel['zuletzt']) . ' Uhr'
                 : 'Bisher wurde nichts gemeldet',
        'klein' => $mailregel['ziele'] === []
                 ? 'Es ist keine Empfängeradresse zu ermitteln — weder eine '
                 . 'Betreiberadresse noch ein Konto mit Verwaltungsrecht'
                 : 'Empfänger: ' . implode(', ', $mailregel['ziele']),
        'plaketten' => $mailregel['ziele'] === []
                 ? ui_plakette('ohne Empfänger', ['ton' => 'orange']) : '',
    ]); ?>
  <?php ui_karte_ende(); ?>

  <?php /* DIE KARTE „WAS HIER GILT" IST MIT P5c/AP9 ENTFALLEN (E-P5c-49): Klartext,
           30 Tage, die eine Handlung und der Datenschutztext stehen im
           Handbuch 11.4b. */ ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
