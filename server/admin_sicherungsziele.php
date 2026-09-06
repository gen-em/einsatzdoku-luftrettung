<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/sicherungsziel_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';   // edbak_groesse_text()

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

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aktion = (string)($_POST['action'] ?? '');
    $id = (int)($_POST['id'] ?? 0);

    if ($aktion === 'schluessel_anlegen') {
        [$ok, $was] = serverschluessel_eintragen();
        if ($ok) {
            $notice = 'Der Serverschlüssel steht jetzt in config.php. '
                    . 'Er gehört ins Wiederanlaufpaket — ohne ihn sind die '
                    . 'Zugangsdaten der Ziele nicht mehr zu öffnen.';
        } else {
            $error = $was . ' Der Schlüssel lässt sich von Hand eintragen: '
                   . 'die Zeile unten in config.php einfügen, gleich hinter '
                   . '„return [".';
        }
    } elseif ($aktion === 'ziel_speichern') {
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
              . ' gesendet (' . edbak_groesse_text($e['bytes']) . ').'
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
$vorschlag = $schluesselDa ? '' : serverschluessel_neu();
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
      'unter' => 'FTP-, FTPS- und SFTP-Gegenstellen, auf die Backups geschoben '
               . 'werden. Nicht zu verwechseln mit den Transportzielen unter '
               . '<a href="admin_stammdaten.php">Stammdaten</a> — das sind Zielkliniken.',
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

  <?php /* ---- Der Serverschlüssel ------------------------------------- */ ?>
  <?php if (!$schluesselDa): ?>
    <?php ui_karte_start(['titel' => 'Serverschlüssel fehlt', 'id' => 'k-schluessel-fehlt']); ?>
      <p class="feld-hinweis">Die Zugangsdaten der Ziele werden verschlüsselt in
         der Datenbank abgelegt. Der Schlüssel dazu steht in
         <code>config.php</code> und damit <strong>nicht</strong> im
         Datenbankdump: Wer die Datenbank hat, hat die Passwörter nicht.
         Solange kein Schlüssel eingetragen ist, lässt sich kein Ziel anlegen —
         ein Passwort im Klartext zu speichern kommt nicht in Frage.</p>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anlegen">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Serverschlüssel erzeugen und eintragen',
                        'symbol' => 'schloss', 'art' => 'primaer']) ?>
        </div>
      </form>
      <p class="feld-hinweis">Klappt das nicht (weil <code>config.php</code> nicht
         beschreibbar ist), diese Zeile von Hand einfügen, gleich hinter
         <code>return [</code>:</p>
      <?php /* KLEINE STUFE MIT „KOPIEREN" (E-S8-10, Backlog Nr. 78). Die
               Zeile ist zum Einfuegen in die `config.php` da — abtippen wird
               sie niemand. In der grossen Stufe stand sie gesperrt in
               Plakatgroesse und ohne Knopf. */ ?>
      <?= ui_codeblock_lang(serverschluessel_zeile($vorschlag), 'Zeile für die config.php') ?>
      <p class="feld-hinweis"><strong>Genau eine Zeile eintragen.</strong> Bei jedem
         Neuladen dieser Seite steht dort ein anderer Schlüssel — welcher es
         wird, ist gleich, aber es darf nur einer sein. Und er gehört ins
         Wiederanlaufpaket neben <code>config.php</code>: Geht er verloren,
         sind die Zugangsdaten neu einzutragen (verschmerzbar) und ein
         versiegeltes Komplettbackup nicht mehr zu öffnen (nicht verschmerzbar).</p>
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
                           'klein' => 'Der Aufräumjob schiebt neue Pakete auf die '
                                    . 'aktiven Ziele. Es wird nur ergänzt — auf dem '
                                    . 'Ziel wird nie etwas gelöscht.']); ?>
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
        $plaketten = (int)$z['aktiv'] === 1
            ? ui_plakette('aktiv', ['ton' => 'blau'])
            : ui_plakette('abgeschaltet', ['ton' => 'neutral']);
        if (($z['letzter_fehler'] ?? null) !== null) {
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
        if (($z['schluessel'] ?? null) !== null) { $klein .= ' · mit privatem Schlüssel'; }
        if (($z['letzter_erfolg'] ?? null) !== null) {
            /* NICHT noch einmal „zuletzt in Ordnung" — das steht schon als
               Plakette daneben. Bei 390 px umfliesst die Kleinzeile die
               Plaketten, und jedes doppelte Wort kostet dort eine Zeile. */
            $klein .= ' · ' . fmt_local((string)$z['letzter_erfolg'], 'd.m.Y · H:i') . ' Uhr';
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
        <?php
        $eintraege = [
            ['text' => 'Verbindung prüfen', 'symbol' => 'haken',
             'form' => 'zp-' . (int)$z['id']],
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
            'aktionen' => ui_zeilenaktionen(['eintraege' => $eintraege]),
        ]);
        if (($z['letzter_fehler'] ?? null) !== null) {
            /* DER FEHLER STEHT DA, BIS ER WEG IST. Ein Versand, der seit drei
               Wochen scheitert, ist sonst nur im Fehlerprotokoll des Webspace
               zu sehen — und an das kommt auf geteiltem Hosting nicht jede
               Betreiberin heran. */
            ui_zeile(['text' => 'Zuletzt gescheitert',
                      'klein' => (string)$z['letzter_fehler'],
                      'plaketten' => ui_plakette(
                          fmt_local((string)$z['letzter_lauf'], 'd.m.Y · H:i'),
                          ['ton' => 'rot'])]);
        }
        ?>
      <?php endforeach; ?>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Anlegen und Ändern ------------------------------------- */ ?>
  <?php if ($bearbeiten !== null && $schluesselDa && $tabelleDa): ?>
    <?php $neu = $bearbeiten === 0; ?>
    <?php ui_karte_start(['titel' => $neu ? 'Neues Ziel' : 'Ziel bearbeiten',
                          'id' => 'zielform']); ?>
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
                         'optionen' => SZ_PROTOKOLLE,
                         'wert' => (string)($form['protokoll'] ?? 'sftp'),
                         'klein' => 'SFTP erkennt den Server am Hostschlüssel wieder. '
                                  . 'FTPS verschlüsselt nur die Leitung — das Zertifikat '
                                  . 'wird von PHP nicht geprüft. FTP überträgt alles '
                                  . 'im Klartext, auch das Passwort.']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'host', 'label' => 'Rechnername', 'pflicht' => true,
                         'wert' => (string)($form['host'] ?? ''),
                         'platzhalter' => 'backup.example.de']); ?>
          <?php ui_feld(['name' => 'port', 'label' => 'Port', 'art' => 'number',
                         'pflicht' => true, 'attr' => 'min="1" max="65535"',
                         'wert' => (string)($form['port'] ?? SZ_PORTS['sftp']),
                         'klein' => 'Üblich: 22 für SFTP, 21 für FTP und FTPS.']); ?>
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
        <?php ui_schalter(['name' => 'passiv', 'label' => 'Passiver Modus (nur FTP und FTPS)',
                           'an' => (int)($form['passiv'] ?? 1) === 1,
                           'klein' => 'Fast immer richtig. Aus nur, wenn die Gegenstelle '
                                    . 'ausdrücklich aktives FTP verlangt.']); ?>
        <?php ui_schalter(['name' => 'aktiv', 'label' => 'Ziel benutzen',
                           'an' => (int)($form['aktiv'] ?? 1) === 1,
                           'klein' => 'Aus heisst: Es bleibt eingetragen, der Versand '
                                    . 'überspringt es aber.']); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => $neu ? 'Anlegen' : 'Speichern', 'symbol' => 'haken',
                        'art' => 'primaer']) ?>
          <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'neutral',
                        'href' => 'admin_sicherungsziele.php']) ?>
        </div>
      </form>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt', 'vorschau' => 'drei Protokolle']); ?>
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
    <p class="feld-hinweis"><strong>FTP überträgt alles im Klartext</strong>, auch den
       Nutzernamen und das Passwort. Es steht hier, weil es auf einfachem Webspace
       oft das Einzige ist, was angeboten wird. Wer die Wahl hat, wählt es nicht.</p>
    <p class="feld-hinweis">Die Zugangsdaten liegen verschlüsselt in der Datenbank;
       der Schlüssel steht in <code>config.php</code>. Ein Datenbankdump enthält
       die Passwörter deshalb nicht — und ein Backup der Installation, in das der
       Schlüssel hineingeriete, wäre nur scheinbar versiegelt.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/kopieren.js']]); ?>
