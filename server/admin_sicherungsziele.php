<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/sicherungsziel_lib.php';
require_once __DIR__ . '/format_lib.php';        // groesse_text(), datum_text(), datum_zeit_text()

/**
 * BACKUP-ZIELE — wohin die Backups geschoben werden (E-S2-22, S2/AP7).
 *
 * WARUM EINE EIGENE SEITE UND KEINE VIERTE KARTE AUF „BACKUPS".
 * Jene Seite ist seit P3/O9c ausdrücklich „die Regeln, und sonst nichts mehr"
 * — Zahlen und Schalter, keine Liste. Hier steht eine Liste mit einem
 * Formular je Eintrag, dazu ein Handgriff („Verbindung prüfen"), der etwas
 * TUT und eine Weile dauert. Das ist der Zuschnitt einer Seite.
 *
 * DREI DINGE, DIE HIER ZUSAMMENKOMMEN
 *   1. Der SERVERSCHLÜSSEL. Ohne ihn lässt sich kein Zugangsdatum speichern,
 *      und die Seite sagt das VORHER — nicht, nachdem jemand ein Passwort
 *      eingetippt hat.
 *   2. Die ZIELE selbst, mit Zustand: Wann lief zuletzt etwas, und wenn es
 *      schiefging, warum.
 *   3. Die PRÜFUNG, die tatsächlich schreibt, liest und wieder löscht — eine
 *      Anmeldung allein sagt nichts über Schreibrechte.
 */

/**
 * Zeitbudget eines Durchgangs „Jetzt versenden" in Sekunden.
 *
 * Dieselbe Überlegung wie bei „Alle sichern" (admin_sicherungen.php): Zwanzig
 * Sekunden liegen unter der `max_execution_time`, die geteilter Webspace
 * üblicherweise setzt. Was in einem Durchgang nicht fertig wird, bleibt
 * offen; ein zweiter Klick macht dort weiter.
 */
const SICHERN_BUDGET_VERSAND = 20.0;

$notice = null; $error = null; $ergebnis = null;
$bearbeiten = null;

$tabelleDa = sz_tabelle_da();
/* Ergebnis des Knopfes „Nachsehen“ (P5a/AP10) — null, solange niemand
 * geklickt hat. Es wird NICHT gespeichert: Es ist eine Momentaufnahme der
 * Gegenstelle, und eine gespeicherte Momentaufnahme ist beim nächsten
 * Aufruf eine Behauptung. */
$bestand = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aktion = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aktion === 'ziel_speichern') {
        /* Ein LEERES Passwortfeld heisst „nicht anfassen", nicht „löschen".
         * Deshalb `null` statt `''` — die Bibliothek unterscheidet beides. */
        $geheim = ($_POST['geheim'] ?? '') === '' ? null : (string)$_POST['geheim'];
        $schluessel = trim((string)($_POST['schluessel'] ?? ''));
        $schluesselNeu = $schluessel === '' ? null : $schluessel;
        if (($_POST['schluessel_weg'] ?? '') === '1') { $schluesselNeu = ''; }
        [$ok, $was] = sz_speichern($id > 0 ? $id : null, $_POST,
                                   $geheim, $schluesselNeu);
        if ($ok) {
            $notice = $id > 0 ? 'Das Ziel wurde geändert.' : 'Das Ziel wurde angelegt.';
            /* Der Fingerabdruck gehört zum Rechner. Zieht ein Ziel auf einen
             * anderen Host um, ist der alte Abdruck falsch — und ein falscher
             * Abdruck ist schlimmer als keiner, weil er jede Verbindung
             * blockiert und wie ein Angriff aussieht. */
            $vorher = $id > 0 ? sz_lesen($id) : null;
            if ($vorher !== null && ((string)$vorher['host'] !== (string)($_POST['host'] ?? '')
                || (int)$vorher['port'] !== (int)($_POST['port'] ?? 0))) {
                sz_fingerabdruck_merken($id, null);
                $notice .= ' Der Hostschlüssel wurde vergessen, weil sich der '
                         . 'Rechner geändert hat — er wird bei der nächsten '
                         . 'Prüfung neu übernommen.';
            }
        } else {
            $error = implode(' ', (array)$was);
            $bearbeiten = $id > 0 ? $id : 0;
        }
    } elseif ($aktion === 'ziel_loeschen' && $id > 0) {
        $z = sz_lesen($id);
        $notice = sz_loeschen($id)
            ? 'Das Ziel „' . (string)($z['name'] ?? '') . '" wurde entfernt. '
            . 'Was dort liegt, bleibt liegen — gelöscht wird auf dem Ziel nichts.'
            : 'Dieses Ziel gibt es nicht (mehr).';
    } elseif ($aktion === 'abdruck_vergessen' && $id > 0) {
        sz_fingerabdruck_merken($id, null);
        $notice = 'Der gespeicherte Hostschlüssel wurde vergessen. Die nächste '
                . 'Prüfung übernimmt den, den der Server dann zeigt — vorher '
                . 'vergewissern, dass er der richtige ist.';
    } elseif ($aktion === 'versand_schalter') {
        $an = ($_POST['versand_auto'] ?? '') === '1';
        if (sz_auto_setzen($an)) {
            $notice = $an
                ? 'Der Versand läuft ab jetzt mit dem Aufräumjob mit. Wie oft '
                . 'das ist, hängt vom eingerichteten Auslöser ab — nachzusehen '
                . 'unter Betrieb → Hintergrundjobs.'
                : 'Der Versand ist abgeschaltet. Die Ziele bleiben eingetragen; '
                . 'es geht nur nichts mehr von selbst hinaus.';
        } else {
            $error = 'Der Schalter liess sich nicht speichern.';
        }
    } elseif ($aktion === 'jetzt_versenden') {
        /* DIESER WEG FRAGT DEN SCHALTER NICHT. Hier hat gerade jemand
         * geklickt, und das ist die Zustimmung — der Schalter beantwortet
         * die andere Frage, nämlich ob es auch OHNE Klick passieren soll. */
        $anfang = microtime(true);
        $e = sz_versand_schub(static fn(): float => SICHERN_BUDGET_VERSAND
                                                  - (microtime(true) - $anfang), 5.0);
        $satz = $e['gesendet'] . ($e['gesendet'] === 1 ? ' Datei' : ' Dateien')
              . ' an ' . $e['ziele'] . ($e['ziele'] === 1 ? ' Ziel' : ' Ziele')
              . ' gesendet (' . groesse_text($e['bytes']) . ').'
              /* DER VERMERK STEHT IM ERFOLGSSATZ, nicht im Fehlerkasten
               * (S10/AP4, E-S10-U-09): Ein übergangenes Ziel ist keine
               * Störung, sondern eine Ansage. Er nennt die Namen, weil „1
               * übersprungen" ohne Namen niemanden zum richtigen Ziel
               * führt. */
              . ((int)($e['uebersprungen'] ?? 0) > 0
                  ? ' Übersprungen: ' . (int)$e['uebersprungen'] . ' ('
                    . implode(', ', array_slice(
                        (array)($e['uebersprungen_namen'] ?? []), 0, 3))
                    . (count((array)($e['uebersprungen_namen'] ?? [])) > 3 ? ' …' : '')
                    . ') — unverschlüsseltes Protokoll, bitte umstellen.'
                  : '')
              /* WAS DORT ENTFERNT WURDE, STEHT IM ERFOLGSSATZ (P5a/AP10).
               * Eine Löschung auf einer fremden Maschine ist die Sorte
               * Handlung, die man nicht erst auf einer anderen Seite
               * nachlesen sollte. Die vollständige Liste steht in
               * Betrieb / Status / Sicherheit. */
              . ((int)($e['geloescht'] ?? 0) > 0
                  ? ' Auf den Zielen entfernt: ' . (int)$e['geloescht']
                    . ((int)$e['geloescht'] === 1 ? ' alte Sicherung (' : ' alte Sicherungen (')
                    . groesse_text((int)($e['geloescht_bytes'] ?? 0))
                    . ') — nach der Aufbewahrungsregel des jeweiligen Ziels.'
                  : '')
              . ((int)($e['nicht_wieder'] ?? 0) > 0
                  ? ' ' . (int)$e['nicht_wieder'] . ' Sicherung'
                    . ((int)$e['nicht_wieder'] === 1 ? ' ging' : 'en gingen')
                    . ' nicht erneut hinaus — sie '
                    . ((int)$e['nicht_wieder'] === 1 ? 'wurde' : 'wurden')
                    . ' dort schon nach der Aufbewahrungsregel entfernt.'
                  : '')
              . ($e['fertig'] ? '' : ' Der Durchgang war nicht fertig — ein '
                              . 'zweiter Klick macht dort weiter, wo dieser aufhörte.');
        if ($e['fehler'] !== []) {
            $error = $satz . ' ' . implode(' ', $e['fehler']);
        } else {
            $notice = $e['ziele'] === 0
                ? 'Es ist kein aktives Ziel eingetragen — es wurde nichts gesendet.'
                : $satz;
        }
    } elseif ($aktion === 'ziel_pruefen' && $id > 0) {
        $z = sz_lesen($id);
        if ($z === null) {
            $error = 'Dieses Ziel gibt es nicht (mehr).';
        } else {
            $ergebnis = sz_verbindung_pruefen($z);
            $ergebnis['ziel'] = (string)$z['name'];
            /* Beim ERSTEN Mal wird der Hostschlüssel übernommen — und nur
             * dann. Steht schon einer, hat die Bibliothek bereits verglichen
             * und wäre gar nicht bis hierher gekommen, wenn er nicht passt. */
            if ($ergebnis['ok'] && ($z['fingerabdruck'] ?? null) === null
                && $ergebnis['fingerabdruck'] !== null) {
                sz_fingerabdruck_merken($id, (string)$ergebnis['fingerabdruck']);
                $ergebnis['uebernommen'] = true;
            }
            sz_lauf_merken($id, (bool)$ergebnis['ok'],
                           $ergebnis['ok'] ? null : (string)$ergebnis['meldung']);
        }
    } elseif ($aktion === 'ziel_bestand' && $id > 0) {
        /* ---- NACHSEHEN, WAS DORT LIEGT (P5a/AP10, E-P5a-03) --------------
         *
         * AUF KNOPFDRUCK UND NICHT BEI JEDEM SEITENAUFRUF. Diese Auskunft
         * kostet eine Verbindung und je Ordner eine Listenabfrage; bei drei
         * Zielen und dreissig Konten sind das neunzig Anfragen über eine
         * Leitung, die auch mal langsam ist. Dieselbe Überlegung wie bei
         * `sz_versand_rueckstand()`, und dieselbe wie beim Knopf „Verbindung
         * prüfen" daneben: Wer es wissen will, fragt.
         *
         * SIE LÖSCHT NICHTS. Das ist die Grundlage aus E-P5a-03 — die
         * Anzeige beantwortet die Frage vielleicht schon, und dann braucht
         * es die Löschregel gar nicht. */
        $z = sz_lesen($id);
        if ($z === null) {
            $error = 'Dieses Ziel gibt es nicht (mehr).';
        } elseif (!sz_protokoll_erlaubt((string)$z['protokoll'])) {
            $error = 'Dieses Ziel wird nicht mehr beschickt — sein Protokoll '
                   . 'überträgt im Klartext. Erst umstellen, dann nachsehen.';
        } else {
            $weg = null;
            try {
                $weg = sz_weg($z);
                $weg->verbinden();
                $bestand = sz_bestand($weg, $id);
                $bestand['ziel'] = (string)$z['name'];
            } catch (ZielFehler $e) {
                $error = 'Bei „' . (string)$z['name'] . '" ging es nicht weiter: '
                       . $e->getMessage();
            } finally {
                if ($weg !== null) { try { $weg->trennen(); } catch (Throwable $x) {} }
            }
        }
    }
}

if ($bearbeiten === null) {
    if (isset($_GET['neu'])) { $bearbeiten = 0; }
    elseif (isset($_GET['bearbeiten'])) { $bearbeiten = (int)$_GET['bearbeiten']; }
}
$ziele = $tabelleDa ? sz_alle() : [];
$form = null;
if ($bearbeiten !== null) {
    $form = $bearbeiten > 0 ? sz_lesen($bearbeiten) : null;
    if ($bearbeiten > 0 && $form === null) { $bearbeiten = null; }
}
/* Nach einem misslungenen Speichern stehen die Eingaben im POST — sonst
 * tippt man alles noch einmal, nur um zu erfahren, dass der Port fehlt. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error !== null && $bearbeiten !== null) {
    $form = array_merge((array)$form, $_POST);
}

$schluesselDa = serverschluessel_da();
/* `$vorschlag` ist mit S10 entfallen: Diese Seite wuerfelt keinen
 * Serverschluessel mehr — das tut die Karte „Schluessel des Servers" unter
 * Betrieb → Servereinstellungen (E-S10-12). Ein zweiter Ort, an dem bei jedem
 * Neuladen ein anderer Schluessel entsteht, waere die Stelle, an der jemand
 * zwei davon eintraegt. */
$aktiveZiele = count(array_filter($ziele, static fn($z) => (int)$z['aktiv'] === 1));
$autoAn = $tabelleDa && sz_auto_an();

ui_seite_start(['titel' => 'Backup-Ziele']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_sicherungsziele']); ?>

  <form method="post" id="f-versand" hidden
        data-confirm="Jetzt alle neuen Pakete auf die aktiven Ziele schieben? Das dauert — je Ziel wird eine Verbindung aufgebaut und jede fehlende Datei übertragen. Was in einem Durchgang nicht fertig wird, bleibt offen."
        data-confirm-ok="Versenden" data-confirm-tone="normal">
    <?= csrf_field() ?><input type="hidden" name="action" value="jetzt_versenden">
  </form>

  <?php ui_titelzeile([
      'titel' => 'Backup-Ziele',
      'unter' => 'FTPS- und SFTP-Gegenstellen, auf die Backups geschoben '
               . 'werden. Nicht zu verwechseln mit den Transportzielen unter '
               . '<a href="einstellungen.php?t=standorte">Standorte</a> — das sind Zielkliniken.',
      'aktionen' => $schluesselDa && $tabelleDa
          ? (($aktiveZiele > 0
              ? ui_knopf(['text' => 'Jetzt versenden', 'symbol' => 'tausch',
                          'art' => 'neutral', 'attr' => ' form="f-versand"'])
              : '')
             . ui_knopf(['text' => 'Ziel anlegen', 'symbol' => 'plus', 'art' => 'primaer',
                         'href' => '?neu=1']))
          : '',
  ]); ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if (!$tabelleDa): ?>
    <?= ui_meldung_markup('fehler', 'Die Tabelle für die Backup-Ziele fehlt '
        . 'noch. Sie entsteht mit der Migration „Backup-Ziele" — bitte '
        . 'einmal unter Betrieb → Updates die ausstehenden Updates ausführen.',
        'Migration steht aus.') ?>
    <p class="feld-hinweis"><a href="betrieb_updates.php">Zu den Updates</a></p>
  <?php endif; ?>

  <?php /* ---- Das Ergebnis der Verbindungsprüfung ------------------------
       Es steht ganz oben und mit JEDEM Schritt, nicht nur mit „hat geklappt":
       Wer eine Verbindung einrichtet, will wissen, WIE WEIT es kam — bis zur
       Anmeldung, bis zum Schreiben, oder gar nicht erst los. */ ?>
  <?php if ($ergebnis !== null): ?>
    <?= ui_meldung_markup($ergebnis['ok'] ? 'ok' : 'fehler',
        e((string)$ergebnis['meldung']),
        'Ziel „' . e((string)$ergebnis['ziel']) . '"') ?>
    <?php if ($ergebnis['schritte']): ?>
      <?php ui_karte_start(['titel' => 'Was die Prüfung getan hat', 'id' => 'k-pruefung']); ?>
        <?php foreach ($ergebnis['schritte'] as $i => $s): ?>
          <?php ui_zeile(['text' => (string)($i + 1) . '. ' . $s]); ?>
        <?php endforeach; ?>
        <?php if (!$ergebnis['ok']): ?>
          <?php ui_zeile(['text' => 'Hier ging es nicht weiter.',
                          'klein' => (string)$ergebnis['meldung'],
                          'plaketten' => ui_plakette('abgebrochen', ['ton' => 'rot'])]); ?>
        <?php endif; ?>
        <?php if (!empty($ergebnis['uebernommen'])): ?>
          <p class="feld-hinweis"><strong>Der Hostschlüssel wurde übernommen.</strong>
             Ab jetzt wird er bei jeder Verbindung verglichen; meldet sich der
             Server einmal mit einem anderen, bricht die Verbindung ab, bevor
             ein Passwort gesendet wird.</p>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>
  <?php endif; ?>

  <?php /* ---- Was auf dem Ziel liegt (P5a/AP10, E-P5a-03) ----------------
       DIE ANZEIGE IST DIE GRUNDLAGE, nicht die Löschregel. Backlog Nr. 49
       sagt es so: „oder eine blosse Anzeige des Belegten am Ziel, damit die
       Betreiberin es sieht und dort selbst entscheidet. Der zweite Weg
       löscht nichts und beantwortet die Frage vielleicht schon."

       FREMDE DATEIEN STEHEN MIT EIGENER ZAHL DA. Sie sind der Grund, warum
       die Löschregel eine Herkunftsprobe braucht — und wer sie sieht, weiß
       auf einen Blick, dass dieses Ziel nicht nur uns gehört. */ ?>
  <?php if ($bestand !== null): ?>
    <?php ui_karte_start(['titel' => 'Was auf „' . e((string)$bestand['ziel']) . '" liegt',
                          'id' => 'k-bestand']); ?>
      <?php
      ui_zeile(['text' => 'Sicherungen dieser Installation',
                'klein' => $bestand['dateien'] === 0
                    ? 'Dort liegt nichts von hier — entweder ist noch nichts gesendet '
                    . 'worden, oder es liegt unter einem anderen Pfad.'
                    : groesse_text((int)$bestand['bytes']) . ' in '
                    . (int)$bestand['ordner'] . ' Ordner'
                    . ((int)$bestand['ordner'] === 1 ? '' : 'n'),
                'plaketten' => ui_plakette((string)(int)$bestand['dateien'] . ' '
                    . ((int)$bestand['dateien'] === 1 ? 'Datei' : 'Dateien'),
                    ['ton' => (int)$bestand['dateien'] > 0 ? 'blau' : 'neutral'])]);
      if ($bestand['aeltester'] !== null) {
          ui_zeile(['text' => 'Ältester Stand',
                    'klein' => 'Der jüngste ist von '
                             . datum_zeit_text((string)$bestand['juengster'], ' · ') . ' Uhr',
                    'plaketten' => ui_plakette(
                        datum_text((string)$bestand['aeltester']), ['ton' => 'neutral'])]);
      }
      ui_zeile(['text' => 'Fremde Dateien',
                'klein' => (int)$bestand['fremd'] === 0
                    ? 'Auf diesem Ziel liegt nichts, was nicht von hier stammt.'
                    : groesse_text((int)$bestand['fremd_bytes'])
                    . ' — diese Anwendung fasst sie nie an, auch nicht mit '
                    . 'eingeschalteter Aufbewahrungsregel.',
                'plaketten' => ui_plakette((string)(int)$bestand['fremd'],
                    ['ton' => (int)$bestand['fremd'] > 0 ? 'orange' : 'blau'])]);
      ?>
      <p class="feld-hinweis">Eine Momentaufnahme, gelesen in diesem Augenblick —
         sie wird nicht gespeichert. Gezählt wird, was dem Namensmuster einer
         Sicherung entspricht; alles andere steht unter „Fremde Dateien".</p>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Der Serverschlüssel steht jetzt woanders (S10, E-S10-12) --
       DIE KARTE IST NACH BETRIEB → SERVEREINSTELLUNGEN GEZOGEN und dort mit
       dem Server-Anteil zusammengelegt („Schlüssel des Servers"). Der Grund
       ist das Ordnungsprinzip aus R74/E-S8-12: Der Serverschlüssel betrifft
       nicht die Backup-Ziele, sondern die INSTALLATION — er versiegelt auch
       das Komplett-Backup und, seit S10, die Konto-Backups. Er hier zu
       verwalten hiesse, ihn auf der Seite anzulegen, die ihn am wenigsten
       braucht.

       WAS BLEIBT, IST DER VERWEIS. Ohne Schlüssel lässt sich hier kein Ziel
       anlegen, und wer davorsteht, muss wissen wohin. Eine Seite, die eine
       Voraussetzung nennt, ohne den Weg dorthin zu zeigen, schickt die
       Betreiberin auf die Suche. */ ?>
  <?php if (!$schluesselDa): ?>
    <?php ui_karte_start(['titel' => 'Serverschlüssel fehlt', 'id' => 'k-schluessel-fehlt']); ?>
      <p class="feld-hinweis">Die Zugangsdaten der Ziele werden verschlüsselt in
         der Datenbank abgelegt. Der Schlüssel dazu steht in
         <code>config.php</code> und damit <strong>nicht</strong> im
         Datenbankdump: Wer die Datenbank hat, hat die Passwörter nicht.
         Solange kein Schlüssel eingetragen ist, lässt sich kein Ziel anlegen —
         ein Passwort im Klartext zu speichern kommt nicht in Frage.</p>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Zu den Servereinstellungen', 'symbol' => 'schloss',
                      'art' => 'primaer',
                      'href' => 'betrieb_server.php#k-schluessel']) ?>
      </div>
      <p class="feld-hinweis">Dort steht die Karte <strong>„Schlüssel des
         Servers"</strong> — sie legt ihn an, zeigt seine Kennung und druckt das
         Schlüsselblatt. Beides gehört ins Wiederanlaufpaket: Geht der Schlüssel
         verloren, sind die Zugangsdaten der Ziele neu einzutragen
         (verschmerzbar) und ein versiegeltes Komplett-Backup nicht mehr zu
         öffnen (nicht verschmerzbar).</p>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Versand ----------------------------------------------------
       Der Schalter sagt OB, nicht WANN. Wann etwas läuft, entscheidet der
       eingerichtete Auslöser (Betrieb → Hintergrundjobs) — eine zweite Uhr hier wäre
       eine zweite Wahrheit. */ ?>
  <?php if ($tabelleDa && $schluesselDa): ?>
    <?php ui_karte_start(['titel' => 'Versand', 'id' => 'k-versand']); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="versand_schalter">
        <?php ui_schalter(['name' => 'versand_auto', 'label' => 'Backups automatisch versenden',
                           'an' => $autoAn,
                           /* DER SATZ WAR BIS WEB 20.13.0 UNEINGESCHRÄNKT
                            * RICHTIG und ist es seit AP10 nicht mehr: Wo die
                            * Aufbewahrungsregel eines Ziels eingeschaltet
                            * ist, löscht der Versand dort sehr wohl. Eine
                            * Zusage, die neben einer Option steht, die sie
                            * aufhebt, ist schlimmer als keine. */
                           'klein' => 'Der Aufräumjob schiebt neue Pakete auf die '
                                    . 'aktiven Ziele. Es wird nur ergänzt — gelöscht '
                                    . 'wird dort nur, wo die Aufbewahrungsregel des '
                                    . 'Ziels ausdrücklich eingeschaltet ist.']); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        </div>
      </form>
      <?php
      ui_zeile(['text' => 'Aktive Ziele',
                'klein' => $aktiveZiele === 0
                    ? 'keines — es geht nichts hinaus'
                    : $aktiveZiele . ($aktiveZiele === 1 ? ' Ziel' : ' Ziele'),
                'plaketten' => $aktiveZiele === 0
                    ? ui_plakette('keines', ['ton' => 'orange'])
                    : ui_plakette((string)$aktiveZiele, ['ton' => 'blau'])]);
      $rueck = $autoAn ? sz_versand_rueckstand() : null;
      ui_zeile(['text' => 'Wartet auf den nächsten Lauf',
                'klein' => $rueck === null
                    ? 'noch keine Aussage — solange ein Ziel nie erfolgreich lief, '
                    . 'ist jede Zahl geraten'
                    : $rueck . ($rueck === 1 ? ' Paket' : ' Pakete')
                    . ' sind neuer als der letzte erfolgreiche Versand (Schätzung; '
                    . 'gezählt wird hier, nicht am Ziel)',
                'plaketten' => $rueck === null
                    ? ui_plakette('unbekannt', ['ton' => 'neutral'])
                    : ui_plakette((string)$rueck, ['ton' => $rueck > 0 ? 'orange' : 'blau'])]);
      ?>
      <p class="feld-hinweis">Der Versand schickt, was am Ziel FEHLT — verglichen
         werden Name und Größe. Eine abgebrochene Übertragung wird deshalb beim
         nächsten Lauf wiederholt und gilt nicht als erledigt.</p>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Die Liste ------------------------------------------------- */ ?>
  <?php if ($tabelleDa): ?>
    <?php ui_karte_start(['titel' => 'Ziele', 'id' => 'k-ziele', 'zahl' => count($ziele)]); ?>
      <?php if (!$ziele): ?>
        <p class="feld-hinweis">Es ist noch kein Ziel eingetragen. Ohne Ziel bleiben
           die Backups dort, wo sie entstehen — auf demselben Server, dessen
           Ausfall der Grund für ein Backup wäre.</p>
      <?php endif; ?>
      <?php foreach ($ziele as $z): ?>
        <?php
        $prot = strtoupper((string)$z['protokoll']);
        /* DAS ABGESCHAFFTE PROTOKOLL ZUERST (S10/AP4, E-S10-14). Es ist die
         * Auskunft, die hier zählt — „aktiv" daneben wäre irreführend, denn
         * beschickt wird dieses Ziel nicht mehr. */
        $tot = !sz_protokoll_erlaubt((string)$z['protokoll']);
        $plaketten = $tot ? ui_plakette('wird übergangen', ['ton' => 'rot']) : '';
        $plaketten .= (int)$z['aktiv'] === 1
            ? ui_plakette('aktiv', ['ton' => 'blau'])
            : ui_plakette('abgeschaltet', ['ton' => 'neutral']);
        if ($tot) {
            /* Kein „zuletzt gescheitert" daneben: Der Vermerk im Lauf IST die
             * Übergehung, und zwei rote Plaketten für eine Sache sagen nicht
             * mehr als eine. */
        } elseif (($z['letzter_fehler'] ?? null) !== null) {
            $plaketten .= ui_plakette('zuletzt gescheitert', ['ton' => 'rot']);
        } elseif (($z['letzter_erfolg'] ?? null) !== null) {
            $plaketten .= ui_plakette('zuletzt in Ordnung', ['ton' => 'blau']);
        } else {
            $plaketten .= ui_plakette('nie geprüft', ['ton' => 'orange']);
        }
        if ((string)$z['protokoll'] === 'sftp' && ($z['fingerabdruck'] ?? null) !== null) {
            $plaketten .= ui_plakette('Hostschlüssel bekannt', ['ton' => 'blau']);
        }
        $klein = $prot . ' · ' . (string)$z['nutzer'] . '@' . (string)$z['host']
               . ':' . (int)$z['port'] . ' · ' . (string)$z['pfad'];
        if ($tot) {
            $klein = $prot . ' überträgt im Klartext und wird seit Web 20.2.0 '
                   . 'nicht mehr beschickt — auf SFTP oder FTPS umstellen '
                   . '(Protokoll, Port und Zugangsdaten). · ' . $klein;
        }
        if (($z['schluessel'] ?? null) !== null) { $klein .= ' · mit privatem Schlüssel'; }
        /* DIE AUFBEWAHRUNG STEHT AN DER ZEILE, nicht nur im Formular
         * (P5a/AP10). Eine Regel, die drüben löscht, gehört dorthin, wo man
         * die Ziele überfliegt — und zwar in BEIDEN Zuständen: „aus" ist hier
         * die Auskunft, dass dort nichts entfernt wird, und nicht ein
         * fehlender Satz. */
        $bk = $z['behalten_konto'] === null ? null : (int)$z['behalten_konto'];
        $bm = $z['behalten_komplett'] === null ? null : (int)$z['behalten_komplett'];
        if ($bk !== null || $bm !== null) {
            $plaketten .= ui_plakette('räumt dort auf', ['ton' => 'orange']);
            $klein .= ' · behält dort ' . ($bk ?? '—') . ' je Konto und '
                    . ($bm ?? '—') . ' Komplett-Stände';
        } else {
            $klein .= ' · räumt dort nicht auf';
        }
        if (($z['letzter_erfolg'] ?? null) !== null) {
            /* NICHT noch einmal „zuletzt in Ordnung" — das steht schon als
               Plakette daneben. Bei 390 px umfliesst die Kleinzeile die
               Plaketten, und jedes doppelte Wort kostet dort eine Zeile. */
            $klein .= ' · ' . datum_zeit_text((string)$z['letzter_erfolg'], ' · ') . ' Uhr';
        }
        ?>
        <form method="post" id="zp-<?= (int)$z['id'] ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
          <input type="hidden" name="action" value="ziel_pruefen">
        </form>
        <form method="post" id="zl-<?= (int)$z['id'] ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
          <input type="hidden" name="action" value="ziel_loeschen">
        </form>
        <form method="post" id="za-<?= (int)$z['id'] ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
          <input type="hidden" name="action" value="abdruck_vergessen">
        </form>
        <form method="post" id="zb-<?= (int)$z['id'] ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
          <input type="hidden" name="action" value="ziel_bestand">
        </form>
        <?php
        $eintraege = [
            ['text' => 'Verbindung prüfen', 'symbol' => 'haken',
             'form' => 'zp-' . (int)$z['id']],
            /* NACHSEHEN STEHT NEBEN PRÜFEN, weil es dasselbe kostet: eine
             * Verbindung auf Knopfdruck. Es löscht nichts — das ist die
             * Grundlage aus E-P5a-03, und die Löschregel darunter ist die
             * Option, die man danach vielleicht gar nicht braucht. */
            ['text' => 'Nachsehen, was dort liegt', 'symbol' => 'lupe',
             'form' => 'zb-' . (int)$z['id']],
            ['text' => 'Bearbeiten', 'symbol' => 'stift',
             'href' => '?bearbeiten=' . (int)$z['id']],
        ];
        if ((string)$z['protokoll'] === 'sftp' && ($z['fingerabdruck'] ?? null) !== null) {
            $eintraege[] = ['text' => 'Hostschlüssel vergessen', 'symbol' => 'schloss-offen',
                            'art' => 'leise', 'form' => 'za-' . (int)$z['id'],
                            'attr' => ' data-confirm="Den gespeicherten Hostschlüssel '
                                    . 'vergessen? Die nächste Prüfung übernimmt den, den '
                                    . 'der Server dann zeigt — das ist der Weg nach einem '
                                    . 'Schlüsselwechsel der Gegenstelle und sonst nichts."'
                                    . ' data-confirm-ok="Vergessen" data-confirm-tone="normal"'];
        }
        $eintraege[] = ['text' => 'Löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                        'form' => 'zl-' . (int)$z['id'],
                        'attr' => ' data-confirm="Das Ziel &quot;' . e((string)$z['name'])
                                . '&quot; entfernen? Was dort liegt, bleibt liegen — '
                                . 'gelöscht wird auf dem Ziel nichts."'
                                . ' data-confirm-ok="Entfernen" data-confirm-tone="gefahr"'];
        ui_zeile([
            'text' => (string)$z['name'],
            'klein' => $klein,
            'plaketten' => $plaketten,
            /* ---- ALLES INS MENUE (P5a/AP10, `blatt_immer`) ---------------
             *
             * DIE ZEILE TRUEG SONST FUENF KNOEPFE. Mit „Nachsehen, was dort
             * liegt" ist die Knopfreihe zu breit geworden, und zwar gemessen:
             * Der Bilderlauf fand **+156 px waagerechten Überlauf bei 768 px**
             * und +120 bei 1024 (Verursacher `div.zeile-aktionen`). Den Text
             * zu kürzen half nicht genug (+49 / +13) — fünf Knöpfe passen
             * dort nicht, egal wie sie heissen.
             *
             * `blatt_immer` IST DAFUER DA, und die Begründung im Baustein
             * passt hier wörtlich (S8/AP6, Mockup 10): „Die Geräteliste trägt
             * drei Handlungen, von denen eine unumkehrbar ist. Als Knopfreihe
             * stünde ‚Entkoppeln' in Rot unmittelbar neben ‚Deaktivieren'."
             * Genau das war hier der Zustand — „Löschen" in Rot neben
             * „Bearbeiten", in jeder Zeile. Im Menü liegt es eine Ebene
             * tiefer, abgesetzt und rot.
             *
             * KEIN NEUER BAUSTEIN: `blatt_immer` ist eine vorhandene Option
             * derselben Funktion und steht so in `docs/Design.md` 9. */
            'aktionen' => ui_zeilenaktionen(['eintraege' => $eintraege,
                                             'blatt_immer' => true,
                                             'titel' => (string)$z['name']]),
        ]);
        if (($z['letzter_fehler'] ?? null) !== null) {
            /* DER FEHLER STEHT DA, BIS ER WEG IST. Ein Versand, der seit drei
               Wochen scheitert, ist sonst nur im Fehlerprotokoll des Webspace
               zu sehen — und an das kommt auf geteiltem Hosting nicht jede
               Betreiberin heran. */
            /* „GESCHEITERT" IST BEI EINEM ÜBERGANGENEN ZIEL DAS FALSCHE
               WORT (S10/AP4). Es ist nichts schiefgegangen — es wurde
               absichtlich nichts versucht. Der Vermerk steht in derselben
               Spalte, weil es dieselbe Spalte ist; die Überschrift sagt,
               was er bedeutet. */
            ui_zeile(['text' => $tot ? 'Zuletzt übergangen' : 'Zuletzt gescheitert',
                      'klein' => (string)$z['letzter_fehler'],
                      'plaketten' => ui_plakette(
                          datum_zeit_text((string)$z['letzter_lauf'], ' · '),
                          ['ton' => $tot ? 'orange' : 'rot'])]);
        }
        ?>
      <?php endforeach; ?>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Anlegen und Ändern ------------------------------------- */ ?>
  <?php if ($bearbeiten !== null && $schluesselDa && $tabelleDa): ?>
    <?php $neu = $bearbeiten === 0; ?>
    <?php /* EIN ALTZIEL WIRD NICHT DURCH BLOSSES SPEICHERN UMGESTELLT
             (S10/AP4, E-S10-U-13, Fund F-D).

             `ui_feld()` setzt `selected` nur bei Übereinstimmung. Fällt das
             Protokoll aus dem Katalog, wählt der Browser die ERSTE Option —
             `sftp` —, während Port 21 und die versiegelten Zugangsdaten
             stehenbleiben. Ein Druck auf „Speichern" ergäbe ein Ziel, das
             plausibel aussieht und beim nächsten Versand scheitert; die rote
             Plakette wäre dabei verschwunden, weil das Protokoll ja nicht
             mehr `ftp` ist. Also: Der Satz sagt, was zu tun ist, und das
             Protokollfeld beginnt LEER statt mit einer geratenen Wahl. */ ?>
    <?php $altziel = !$neu && isset($form['protokoll'])
                     && !sz_protokoll_erlaubt((string)$form['protokoll']); ?>
    <?php ui_karte_start(['titel' => $neu ? 'Neues Ziel' : 'Ziel bearbeiten',
                          'id' => 'zielform']); ?>
      <?php if ($altziel): ?>
        <?php ui_meldung(
            'Es überträgt im Klartext und wird seit Web 20.2.0 nicht mehr '
            . 'beschickt. Zum Weiterbenutzen sind drei Angaben neu zu setzen: '
            . 'Protokoll, Port und die Zugangsdaten. Die bisherigen '
            . 'Zugangsdaten werden nicht übernommen — sie gelten nicht '
            . 'notwendig auch für den verschlüsselten Weg, und geraten wird '
            . 'hier nichts.',
            null, 'warn', '',
            ['auftakt' => 'Dieses Ziel benutzt '
                        . strtoupper((string)$form['protokoll']) . '.']); ?>
      <?php endif; ?>
      <form method="post">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="ziel_speichern">
        <input type="hidden" name="id" value="<?= $neu ? 0 : (int)$bearbeiten ?>">
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'name', 'label' => 'Name', 'pflicht' => true,
                         'wert' => (string)($form['name'] ?? ''),
                         'klein' => 'Frei wählbar — er steht in Meldungen und im '
                                  . 'Versandprotokoll.']); ?>
          <?php ui_feld(['name' => 'protokoll', 'label' => 'Protokoll', 'art' => 'select',
                         'optionen' => $altziel
                             ? ['' => '— bitte wählen —'] + SZ_PROTOKOLLE
                             : SZ_PROTOKOLLE,
                         'wert' => $altziel ? '' : (string)($form['protokoll'] ?? 'sftp'),
                         'klein' => 'SFTP erkennt den Server am Hostschlüssel wieder. '
                                  . 'FTPS verschlüsselt nur die Leitung — das Zertifikat '
                                  . 'wird von PHP nicht geprüft.']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'host', 'label' => 'Rechnername', 'pflicht' => true,
                         'wert' => (string)($form['host'] ?? ''),
                         'platzhalter' => 'backup.example.de']); ?>
          <?php ui_feld(['name' => 'port', 'label' => 'Port', 'art' => 'number',
                         'pflicht' => true, 'attr' => 'min="1" max="65535"',
                         'wert' => (string)($form['port'] ?? SZ_PORTS['sftp']),
                         'klein' => 'Üblich: 22 für SFTP, 21 für FTPS.']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'nutzer', 'label' => 'Nutzername', 'pflicht' => true,
                         'wert' => (string)($form['nutzer'] ?? '')]); ?>
          <?php ui_feld(['name' => 'pfad', 'label' => 'Pfad auf dem Ziel',
                         'wert' => (string)($form['pfad'] ?? '/'),
                         'klein' => 'Der Ordner, in dem die Backups landen. Je Konto '
                                  . 'entsteht darunter ein Unterordner.']); ?>
        </div>
        <?php ui_feld(['name' => 'geheim', 'label' => 'Passwort', 'art' => 'password',
                       'wert' => '',
                       'klein' => $neu
                           ? 'Bei Anmeldung mit privatem Schlüssel: dessen Passphrase '
                           . '(leer lassen, wenn er keine hat).'
                           : 'Leer lassen heisst: unverändert. Was gespeichert ist, '
                           . 'wird nie zurück in dieses Feld geschrieben.']); ?>
        <?php ui_feld(['name' => 'schluessel', 'label' => 'Privater Schlüssel (nur SFTP)',
                       'art' => 'textarea', 'zeilen' => 4, 'wert' => '',
                       'platzhalter' => "-----BEGIN OPENSSH PRIVATE KEY-----\n"
                                      . "(der ganze Schlüssel)\n"
                                      . "-----END OPENSSH PRIVATE KEY-----",
                       'klein' => 'Vollständig einfügen, mit den BEGIN- und END-Zeilen. '
                                . 'Ist hier etwas eingetragen, wird damit angemeldet und '
                                . 'das Feld „Passwort" ist die Passphrase.'
                                . (($form['schluessel'] ?? null) !== null
                                   ? ' Zurzeit ist ein Schlüssel hinterlegt; leer lassen '
                                   . 'heisst unverändert.' : '')]); ?>
        <?php if (($form['schluessel'] ?? null) !== null): ?>
          <?php ui_schalter(['name' => 'schluessel_weg',
                             'label' => 'Hinterlegten Schlüssel entfernen',
                             'an' => false,
                             'klein' => 'Danach wird wieder mit Passwort angemeldet.']); ?>
        <?php endif; ?>
        <?php ui_schalter(['name' => 'passiv', 'label' => 'Passiver Modus (nur FTPS)',
                           'an' => (int)($form['passiv'] ?? 1) === 1,
                           'klein' => 'Fast immer richtig. Aus nur, wenn die Gegenstelle '
                                    . 'ausdrücklich aktives FTP verlangt.']); ?>
        <?php ui_schalter(['name' => 'aktiv', 'label' => 'Ziel benutzen',
                           'an' => (int)($form['aktiv'] ?? 1) === 1,
                           'klein' => 'Aus heisst: Es bleibt eingetragen, der Versand '
                                    . 'überspringt es aber.']); ?>

        <?php /* ---- Die Aufbewahrung auf DEM ZIEL (P5a/AP10, E-P5a-03) ----
                 SIE IST EINE OPTION UND NIE DIE VORGABE. Der Zweck eines
                 auswärtigen Ziels ist, den Ausfall dieses Servers zu
                 überleben — samt eines Fehlers, der HIER zu viel löscht.
                 Ein Versand, der drüben aufräumt, trägt genau diesen Fehler
                 mit hinüber. Wer den Haken setzt, entscheidet sich
                 ausdrücklich dafür, dass er beides will.

                 DER SATZ DARUNTER NENNT DIE DREI SICHERUNGEN, weil sie die
                 Antwort auf die Frage sind, die beim Setzen des Hakens
                 entsteht: „Und was, wenn dort noch etwas anderes liegt?" */ ?>
        <?php /* NACH EINEM FEHLSCHLAG GILT, WAS DAGESTANDEN HAT. `$form` ist
                 dann die Verschmelzung aus Datenbankzeile und `$_POST`, und
                 ein NICHT gesetzter Haken kommt in `$_POST` gar nicht vor —
                 die beiden Zahlen aber schon. Ohne diese Unterscheidung
                 stünde der Haken nach einem Tippfehler im Port wieder an,
                 obwohl ihn niemand gesetzt hat. */ ?>
        <?php $aufAn = $_SERVER['REQUEST_METHOD'] === 'POST'
                       ? !empty($_POST['aufraeumen'])
                       : (($form['behalten_konto'] ?? null) !== null
                          || ($form['behalten_komplett'] ?? null) !== null); ?>
        <?php ui_schalter(['name' => 'aufraeumen',
                           'label' => 'Auf dem Ziel aufräumen',
                           'an' => $aufAn,
                           'klein' => 'Aus ist die Vorgabe: Der Versand ergänzt nur, '
                                    . 'gelöscht wird dort nie. An heisst, dass alte '
                                    . 'Sicherungen dieser Installation dort entfernt '
                                    . 'werden, sobald mehr liegen als unten steht. '
                                    . 'Fremde Dateien bleiben immer; gelöscht wird nur, '
                                    . 'was dem Namensmuster entspricht UND im '
                                    . 'Versandprotokoll steht, nie unter der Zahl, und '
                                    . 'nie in einem Lauf, dessen eigener Versand '
                                    . 'gescheitert ist.']); ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'behalten_konto', 'label' => 'Je Konto behalten',
                         'art' => 'number', 'attr' => 'min="1" max="999"',
                         'wert' => (string)($form['behalten_konto'] ?? 6),
                         'klein' => 'Wie viele Konto-Sicherungen je Konto dort bleiben. '
                                  . 'Hier auf dem Server sind es zwei — dort darf es '
                                  . 'mehr sein, das ist der Sinn der Sache.']); ?>
          <?php ui_feld(['name' => 'behalten_komplett', 'label' => 'Komplett-Stände behalten',
                         'art' => 'number', 'attr' => 'min="1" max="999"',
                         'wert' => (string)($form['behalten_komplett'] ?? 12),
                         'klein' => 'Aus einem Komplett-Stand lässt sich jedes Konto '
                                  . 'wiederherstellen, umgekehrt nicht — deshalb hier '
                                  . 'die längere Reihe.']); ?>
        </div>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => $neu ? 'Anlegen' : 'Speichern', 'symbol' => 'haken',
                        'art' => 'primaer']) ?>
          <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'neutral',
                        'href' => 'admin_sicherungsziele.php']) ?>
        </div>
      </form>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt', 'vorschau' => 'zwei Protokolle']); ?>
    <p class="feld-hinweis"><strong>SFTP ist die Empfehlung.</strong> Es verschlüsselt
       nicht nur, es erkennt den Server auch wieder: Beim ersten Prüfen wird der
       Fingerabdruck des Hostschlüssels übernommen, danach bei jeder Verbindung
       verglichen. Passt er nicht, bricht die Verbindung ab, <em>bevor</em> ein
       Passwort gesendet wird.</p>
    <p class="feld-hinweis"><strong>FTPS verschlüsselt, prüft aber nichts.</strong> Die
       PHP-Erweiterung <code>ftp</code> nimmt jedes Zertifikat an, auch ein selbst
       ausgestelltes ohne Vertrauenskette (nachgemessen in
       <code>tools/versandprobe/</code>). Schutz gegen Mitlesen: ja. Schutz gegen
       einen untergeschobenen Server: nein.</p>
    <?php /* NICHT GESTRICHEN, SONDERN UMGESCHRIEBEN (S10/AP4, E-S10-U-16).
             Dieser Absatz rechtfertigte FTP („es steht hier, weil es auf
             einfachem Webspace oft das Einzige ist"). Seit AP4 ist er der
             EINZIGE Ort, an dem die rote Plakette an einem Altziel erklaert
             wird — ihn zu streichen hiesse, die Plakette unerklaert zu
             lassen. Die Streichung ist fuer den ENUM-Rueckbau vorgemerkt
             (Backlog Nr. 168 / Nr. 46). */ ?>
    <p class="feld-hinweis"><strong>FTP wird nicht mehr angeboten.</strong> Es überträgt
       alles im Klartext, auch den Nutzernamen und das Passwort — und eine
       Backup-Datei ist genau das, was man dabei nicht mitlesen lassen will. Seit
       Web 20.2.0 ist es weder wählbar noch wird es beschickt. Ein Ziel, das noch
       darauf steht, trägt in der Liste oben die Plakette <em>wird übergangen</em>
       und wird beim Versand übersprungen, statt im Klartext beliefert zu werden.
       Zum Umstellen sind drei Angaben neu zu setzen: Protokoll, Port und die
       Zugangsdaten.</p>
    <p class="feld-hinweis">Die Zugangsdaten liegen verschlüsselt in der Datenbank;
       der Schlüssel steht in <code>config.php</code>. Ein Datenbankdump enthält
       die Passwörter deshalb nicht — und ein Backup der Installation, in das der
       Schlüssel hineingeriete, wäre nur scheinbar versiegelt.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php /* `assets/kopieren.js` ist mit S10 entfallen: Es hing am
         `ui_codeblock_lang()` der Serverschluessel-Karte, und die ist nach
         Betrieb → Servereinstellungen gezogen (E-S10-12). Ein Skript ohne
         Baustein laedt bei jedem Aufruf ein paar Kilobyte fuer nichts. */ ?>
<?php ui_seite_ende(); ?>
