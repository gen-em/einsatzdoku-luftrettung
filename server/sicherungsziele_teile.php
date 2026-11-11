<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)
/**
 * DIE KARTE „BACKUP-ZIELE" — zwei Verwender: Betrieb → Servereinstellungen
 * (letzte Karte) und Betrieb → Komplett-Backup (unter den Ständen; Konzept
 * OF, E-OF-24, E-OF-45).
 *
 * EINE STELLE, DAMIT DIE BEIDEN ORTE NICHT AUSEINANDERLAUFEN — dieselbe
 * Überlegung wie bei `zweitfaktor_teile.php`. Bis Web 24.6 waren die Ziele
 * eine eigene Seite; wer sie heute an zwei Orten sieht, soll dieselbe Karte
 * wiedererkennen, mit denselben Handgriffen.
 *
 * DIE FORMULARE POSTEN WEITER AN `admin_sicherungsziele.php`. Dort stehen
 * die Schreibwege, die Rollenprobe (BetreiberIn) und die Umleitung. Jedes
 * Formular trägt ein verborgenes Feld `zurueck` mit der Seite, auf die es
 * danach zurückgeht; angenommen werden nur die Seiten in
 * `SZ_ZURUECK_SEITEN`, alles andere führt auf die Servereinstellungen.
 *
 * WAS DIE EINBINDENDE SEITE TUT — drei Aufrufe und eine Meldung:
 *
 *     require_once __DIR__ . '/sicherungsziele_teile.php';
 *     $flash = flash_holen();
 *     $zielMeldung = null;
 *     if ($flash !== null && $flash['ort'] === SZ_KARTE_ID) {
 *         $zielMeldung = $flash;   // gehört in die Karte, nicht oben auf die Seite
 *         $flash = null;
 *     }
 *     …
 *     sicherungsziele_karte('betrieb_server.php', $zielMeldung);
 *     …
 *     sicherungsziele_dialoge('betrieb_server.php', $zielMeldung);   // AUSSERHALB jedes <form>
 *     ui_geruest_ende();
 *     ui_seite_ende(['skripte' => sicherungsziele_skripte()]);
 *
 * WARUM DIE KARTE `flash_holen()` NICHT SELBST LIEST: Es liest und löscht in
 * einem. Holte die Karte die Meldung, fände die Seite ihre eigene nicht mehr
 * — oder umgekehrt, je nachdem, wer zuerst fragt. Die Seite holt einmal und
 * reicht weiter, was nach `ort` der Karte gehört.
 *
 * `$meldung` IST GENAU DAS ARRAY AUS `flash_holen()`: `ton` (einer aus
 * `FLASH_TOENE`), `text` und `daten`. In `daten` reisen drei Dinge mit, die
 * die Karte selbst auswertet: `ergebnis` (die Schritte von „Verbindung
 * prüfen"), `bestand` (was „Nachsehen" auf dem Ziel fand) und `zielform`
 * (die verworfene Eingabe eines gescheiterten Speicherns — dann geht der
 * Dialog beim Laden wieder auf).
 *
 * DIE KARTE STEHT IN KEINEM ANDEREN FORMULAR. Sie trägt eigene Formulare,
 * und verschachtelte Formulare gibt es in HTML nicht; die Dialoge ebenso.
 */

require_once __DIR__ . '/ui.php';
require_once __DIR__ . '/sicherungsziel_lib.php';
require_once __DIR__ . '/format_lib.php';        // groesse_text(), datum_text(), datum_zeit_text()
require_once __DIR__ . '/adminbackup_lib.php';    // edbak_aufbewahrung()

/** Kennung der Karte — Anker der Umleitung und `ort` der Meldung. */
const SZ_KARTE_ID = 'k-backup-ziele';

/** Die Seiten, auf die `admin_sicherungsziele.php` zurückleitet. Die erste ist die Vorgabe. */
const SZ_ZURUECK_SEITEN = ['betrieb_server.php', 'admin_komplettsicherung.php'];

/** Die Felder des Zielformulars, die nach einem Fehlschlag zurückreisen — ohne Passwort und Schlüssel. */
const SZ_FORMFELDER = ['name', 'protokoll', 'host', 'port', 'nutzer', 'pfad',
                       'passiv', 'aktiv', 'aufraeumen', 'behalten_konto', 'behalten_komplett'];

/**
 * Die erlaubte Rückkehrseite aus einem Wunsch. Was nicht in der Liste
 * steht, wird die Vorgabe — nie ein freier Wert in `Location:`.
 */
function sz_zurueck_seite(string $wunsch): string
{
    return in_array($wunsch, SZ_ZURUECK_SEITEN, true) ? $wunsch : SZ_ZURUECK_SEITEN[0];
}

/** Die Skripte, die Karte und Dialoge brauchen (für `ui_seite_ende(['skripte' => …])`). */
function sicherungsziele_skripte(): array
{
    /* NUR `dialog.js`: Rückfragen (`confirm.js`) und Aktionsblatt
     * (`blatt.js`) lädt das Gerüst ohnehin. */
    return ['assets/dialog.js'];
}

/**
 * Der Zustand, den Karte und Dialoge beide brauchen — einmal je Aufruf
 * gelesen, nicht zweimal aus der Datenbank.
 *
 * @return array{tabelle:bool, schluessel:bool, ziele:list<array>, aktive:int, auto:bool}
 */
function sz_teile_lage(): array
{
    static $lage = null;
    if ($lage !== null) { return $lage; }
    $tabelle = sz_tabelle_da();
    $ziele = $tabelle ? sz_alle() : [];
    $lage = [
        'tabelle'    => $tabelle,
        'schluessel' => serverschluessel_da(),
        'ziele'      => $ziele,
        'aktive'     => count(array_filter($ziele, static fn($z) => (int)$z['aktiv'] === 1)),
        'auto'       => $tabelle && sz_auto_an(),
    ];
    return $lage;
}

/** Das verborgene Feld `zurueck` für jedes Formular der Karte. */
function sz_teile_zurueck_feld(string $zurueck): string
{
    return '<input type="hidden" name="zurueck" value="' . ui_e(sz_zurueck_seite($zurueck)) . '">';
}

/**
 * Die Klappkarte „Backup-Ziele".
 *
 * @param string     $zurueck die einbindende Seite (aus `SZ_ZURUECK_SEITEN`)
 * @param array|null $meldung die Meldung aus `flash_holen()`, wenn ihr `ort`
 *                            `SZ_KARTE_ID` ist — sonst null (siehe Kopf)
 */
function sicherungsziele_karte(string $zurueck, ?array $meldung = null): void
{
    $l = sz_teile_lage();
    $ziele = $l['ziele'];
    $aktive = $l['aktive'];
    $autoAn = $l['auto'];
    $daten = (array)($meldung['daten'] ?? []);
    $ergebnis = is_array($daten['ergebnis'] ?? null) ? $daten['ergebnis'] : null;
    $bestand  = is_array($daten['bestand'] ?? null) ? $daten['bestand'] : null;

    /* ZUGEKLAPPT SAGT DIE KARTE, OB ETWAS HINAUSGEHT. Stand: der Schalter;
     * Plakette: wie viele Ziele es bekommen — oder, wenn das gar nicht geht,
     * warum nicht. Ein gescheitertes aktives Ziel überstimmt die Zahl: Es ist
     * das, was man beim Überfliegen sehen muss. */
    $gescheitert = count(array_filter($ziele, static fn($z) => (int)$z['aktiv'] === 1
                                               && ($z['letzter_fehler'] ?? null) !== null));
    if (!$l['tabelle']) {
        $stand = 'Migration steht aus';
        $plakette = ui_plakette('fehlt', ['ton' => 'rot']);
    } elseif (!$l['schluessel']) {
        $stand = 'ohne Serverschlüssel';
        $plakette = ui_plakette('gesperrt', ['ton' => 'orange']);
    } else {
        $stand = $autoAn ? 'automatisch' : 'Versand aus';
        $plakette = $gescheitert > 0
            ? ui_plakette($gescheitert . ' gescheitert', ['ton' => 'rot'])
            : ($aktive === 0
                ? ui_plakette('keines', ['ton' => 'orange'])
                : ui_plakette($aktive . ' aktiv', ['ton' => 'blau']));
    }

    ui_karte_start(['titel' => 'Backup-Ziele', 'id' => SZ_KARTE_ID, 'zu' => true,
                    'stand' => $stand, 'plakette' => $plakette,
                    /* MIT MELDUNG OFFEN. Der Anker der Umleitung klappt sie
                     * über `klappkarte.js` ohnehin auf; das hier gilt auch
                     * ohne Skript. */
                    'offen' => $meldung !== null]);
    ?>
    <p class="feld-hinweis">SFTP- und FTPS-Gegenstellen, auf die der Versand neue Backups schiebt.</p>
    <?php
    /* ---- Die Meldung der letzten Handlung ----------------------------------
     * Die verworfene Eingabe eines Speicherns zeigt ihren Fehler IM DIALOG,
     * der dann wieder aufgeht (sicherungsziele_dialoge()); hier stünde er
     * hinter dem Schleier ein zweites Mal. */
    $zfId = is_array($daten['zielform'] ?? null) ? (int)($daten['zielform']['id'] ?? 0) : null;
    $imDialog = $zfId !== null && $l['tabelle'] && $l['schluessel']
        && ($zfId === 0 || in_array($zfId, array_map(static fn($z) => (int)$z['id'], $ziele), true));
    if ($meldung !== null && !$imDialog) {
        $ton = match ((string)($meldung['ton'] ?? '')) {
            'error', 'fehler' => 'fehler',
            'ok'              => 'ok',
            'warn'            => 'warn',
            default           => 'info',
        };
        echo ui_meldung_markup($ton, (string)($meldung['text'] ?? ''),
            $ergebnis !== null ? 'Ziel „' . (string)($ergebnis['ziel'] ?? '') . '“.' : ''), "\n";
    }

    /* ---- Was die Verbindungsprüfung getan hat --------------------------
     * JEDER SCHRITT, nicht nur „hat geklappt": Wer eine Verbindung
     * einrichtet, will wissen, WIE WEIT es kam — bis zur Anmeldung, bis zum
     * Schreiben, oder gar nicht erst los. */
    if ($ergebnis !== null && !empty($ergebnis['schritte'])) {
        echo '<h3 class="listen-form-titel">Was die Prüfung getan hat</h3>', "\n";
        foreach ((array)$ergebnis['schritte'] as $i => $s) {
            ui_zeile(['text' => (string)((int)$i + 1) . '. ' . (string)$s]);
        }
        if (empty($ergebnis['ok'])) {
            ui_zeile(['text' => 'Hier ging es nicht weiter.',
                      'klein' => (string)($ergebnis['meldung'] ?? ''),
                      'plaketten' => ui_plakette('abgebrochen', ['ton' => 'rot'])]);
        }
        if (!empty($ergebnis['uebernommen'])) {
            ui_zeile(['text' => 'Hostschlüssel übernommen',
                      'klein' => 'Er wird ab jetzt bei jeder Verbindung verglichen.',
                      'plaketten' => ui_plakette('neu', ['ton' => 'blau'])]);
        }
    }

    /* ---- Was auf dem Ziel liegt ----------------------------------------
     * EINE MOMENTAUFNAHME, die nur einmal angezeigt wird: Sie reist mit der
     * Umleitung über die Sitzung und wird nicht gespeichert — beim nächsten
     * Aufruf wäre sie eine Behauptung. FREMDE DATEIEN STEHEN MIT EIGENER
     * ZAHL DA: Wer sie sieht, weiß, dass dieses Ziel nicht nur uns gehört. */
    if ($bestand !== null) {
        echo '<h3 class="listen-form-titel">Was auf „' . ui_e((string)($bestand['ziel'] ?? ''))
           . '“ liegt</h3>', "\n";
        ui_zeile(['text' => 'Sicherungen dieser Installation',
                  'klein' => (int)$bestand['dateien'] === 0
                      ? 'Nichts von hier — noch nichts gesendet, oder unter einem anderen Pfad.'
                      : groesse_text((int)$bestand['bytes']) . ' in '
                      . (int)$bestand['ordner'] . ' Ordner'
                      . ((int)$bestand['ordner'] === 1 ? '' : 'n'),
                  'plaketten' => ui_plakette((string)(int)$bestand['dateien'] . ' '
                      . ((int)$bestand['dateien'] === 1 ? 'Datei' : 'Dateien'),
                      ['ton' => (int)$bestand['dateien'] > 0 ? 'blau' : 'neutral'])]);
        if (($bestand['aeltester'] ?? null) !== null) {
            ui_zeile(['text' => 'Ältester Stand',
                      'klein' => 'Der jüngste ist von '
                               . datum_zeit_text((string)$bestand['juengster']) . ' Uhr',
                      'plaketten' => ui_plakette(
                          datum_text((string)$bestand['aeltester']), ['ton' => 'neutral'])]);
        }
        ui_zeile(['text' => 'Fremde Dateien',
                  'klein' => (int)$bestand['fremd'] === 0
                      ? 'Nichts, was nicht von hier stammt.'
                      : groesse_text((int)$bestand['fremd_bytes'])
                      . ' — diese Anwendung fasst sie nie an.',
                  'plaketten' => ui_plakette((string)(int)$bestand['fremd'],
                      ['ton' => (int)$bestand['fremd'] > 0 ? 'orange' : 'gruen'])]);
    }

    /* ---- Ohne Tabelle oder Schlüssel geht nichts -----------------------
     * DER SERVERSCHLÜSSEL ENTSTEHT unter Betrieb → Servereinstellungen
     * (Karte „Schlüssel des Servers"), nicht hier: Er versiegelt auch das
     * Komplett-Backup und die Konto-Backups. Was bleibt, ist der Verweis —
     * eine Voraussetzung ohne Weg dorthin schickt die BetreiberIn auf die
     * Suche. */
    if (!$l['tabelle']) {
        echo ui_meldung_markup('fehler', 'Die Tabelle entsteht mit der Migration '
            . '„Backup-Ziele“ unter Betrieb → Updates.', 'Migration steht aus.'), "\n";
        echo '<div class="listen-form-fuss">'
           . ui_knopf(['text' => 'Zu den Updates', 'art' => 'neutral',
                       'href' => 'betrieb_updates.php'])
           . "</div>\n";
        ui_karte_ende(true);
        return;
    }
    if (!$l['schluessel']) {
        echo ui_meldung_markup('warn', 'Die Zugangsdaten der Ziele werden mit dem '
            . 'Serverschlüssel verschlüsselt; ohne ihn lässt sich kein Ziel anlegen.'), "\n";
        echo '<div class="listen-form-fuss">'
           . ui_knopf(['text' => 'Zu den Servereinstellungen', 'symbol' => 'schloss',
                       'art' => 'neutral', 'href' => 'betrieb_server.php#k-schluessel'])
           . "</div>\n";
    }

    /* ---- Die verborgenen Formulare der Zeilenhandlungen ----------------
     * Sie stehen VOR dem Formular des Schalters und nicht darin: Die Knöpfe
     * der Zeilen zeigen über `form="…"` auf sie, und verschachtelte
     * Formulare gibt es nicht. Die Kennungen tragen `sz-`, weil die Karte
     * auf den Servereinstellungen neben acht anderen steht.
     *
     * `zh-` und nicht `za-`: `ui_zeilenaktionen()` nummeriert seine Menüs
     * selbst als `za-1`, `za-2` … — zwei gleiche Kennungen, und „…" öffnete
     * statt des Menüs dieses Formular. */
    $verborgen = static function (string $kennung, string $aktion, int $id, string $attr = '')
                 use ($zurueck): void {
        echo '<form method="post" action="admin_sicherungsziele.php" id="' . ui_e($kennung)
           . '" hidden' . $attr . '>' . csrf_field() . sz_teile_zurueck_feld($zurueck)
           . '<input type="hidden" name="action" value="' . ui_e($aktion) . '">'
           . ($id > 0 ? '<input type="hidden" name="id" value="' . $id . '">' : '')
           . "</form>\n";
    };
    if ($l['schluessel'] && $aktive > 0) {
        $verborgen('sz-versand', 'jetzt_versenden', 0,
            ' data-confirm="Jetzt alle neuen Pakete auf die aktiven Ziele schieben? Je Ziel wird'
            . ' eine Verbindung aufgebaut und jede fehlende Datei übertragen; was in einem'
            . ' Durchgang nicht fertig wird, bleibt offen."'
            . ' data-confirm-ok="Versenden" data-confirm-tone="normal"');
    }
    foreach ($ziele as $z) {
        $zid = (int)$z['id'];
        $verborgen('sz-zp-' . $zid, 'ziel_pruefen', $zid);
        $verborgen('sz-zl-' . $zid, 'ziel_loeschen', $zid);
        $verborgen('sz-zh-' . $zid, 'abdruck_vergessen', $zid);
        $verborgen('sz-zb-' . $zid, 'ziel_bestand', $zid);
    }
    ?>
    <form method="post" action="admin_sicherungsziele.php">
      <?= csrf_field() ?><?= sz_teile_zurueck_feld($zurueck) ?>
      <input type="hidden" name="action" value="versand_schalter">
      <?php /* DER SCHALTER SAGT OB, NICHT WANN. Wann etwas läuft, entscheidet
               der eingerichtete Auslöser (Betrieb → Hintergrundjobs) — eine
               zweite Uhr hier wäre eine zweite Wahrheit. Ob der Versand am
               Ziel löscht, steht je Ziel an seiner Zeile („räumt dort auf"). */ ?>
      <?php ui_schalter(['name' => 'versand_auto', 'id' => 'sz-versand-auto',
                         'label' => 'Backups automatisch versenden', 'an' => $autoAn]); ?>
      <?php
      ui_zeile(['text' => 'Aktive Ziele',
                'klein' => $aktive === 0
                    ? 'keines — die Backups bleiben auf diesem Server'
                    : $aktive . ($aktive === 1 ? ' Ziel' : ' Ziele') . ' von ' . count($ziele),
                'plaketten' => $aktive === 0
                    ? ui_plakette('keines', ['ton' => 'orange'])
                    : ui_plakette((string)$aktive, ['ton' => 'blau'])]);

      foreach ($ziele as $z) {
          $zid = (int)$z['id'];
          $plaketten = (int)$z['aktiv'] === 1
              ? ui_plakette('aktiv', ['ton' => 'gruen'])
              : ui_plakette('abgeschaltet', ['ton' => 'neutral']);
          if (($z['letzter_fehler'] ?? null) !== null) {
              $plaketten .= ui_plakette('zuletzt gescheitert', ['ton' => 'rot']);
          } elseif (($z['letzter_erfolg'] ?? null) !== null) {
              $plaketten .= ui_plakette('zuletzt in Ordnung', ['ton' => 'gruen']);
          } else {
              $plaketten .= ui_plakette('nie geprüft', ['ton' => 'orange']);
          }
          if ((string)$z['protokoll'] === 'sftp' && ($z['fingerabdruck'] ?? null) !== null) {
              $plaketten .= ui_plakette('Hostschlüssel bekannt', ['ton' => 'gruen']);
          }
          $klein = strtoupper((string)$z['protokoll']) . ' · ' . (string)$z['nutzer'] . '@'
                 . (string)$z['host'] . ':' . (int)$z['port'] . ' · ' . (string)$z['pfad'];
          if (($z['schluessel'] ?? null) !== null) { $klein .= ' · mit privatem Schlüssel'; }
          /* DIE AUFBEWAHRUNG STEHT AN DER ZEILE, in BEIDEN Zuständen: Eine
           * Regel, die drüben löscht, gehört dorthin, wo man die Ziele
           * überfliegt, und „räumt dort nicht auf" ist eine Auskunft, kein
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
                 Plakette daneben, und bei 390 px kostet jedes doppelte Wort
                 eine Zeile. */
              $klein .= ' · ' . datum_zeit_text((string)$z['letzter_erfolg']) . ' Uhr';
          }
          $eintraege = [
              ['text' => 'Verbindung prüfen', 'symbol' => 'haken', 'form' => 'sz-zp-' . $zid],
              /* NACHSEHEN STEHT NEBEN PRÜFEN, weil es dasselbe kostet: eine
               * Verbindung auf Knopfdruck. Es löscht nichts. */
              ['text' => 'Nachsehen, was dort liegt', 'symbol' => 'lupe',
               'form' => 'sz-zb-' . $zid],
          ];
          if ($l['schluessel']) {
              /* BEARBEITEN ÖFFNET DEN DIALOG DIESES ZIELS. Ein Verweis und
               * kein Absendeknopf: Die Zeile steht im Formular des
               * Schalters, und ein Knopf ohne `form` schickte ohne Skript
               * DIESES ab. */
              $eintraege[] = ['text' => 'Bearbeiten', 'symbol' => 'stift',
                              'href' => '#dlg-sz-' . $zid,
                              'attr' => ' data-dialog="dlg-sz-' . $zid . '"'];
          }
          if ((string)$z['protokoll'] === 'sftp' && ($z['fingerabdruck'] ?? null) !== null) {
              $eintraege[] = ['text' => 'Hostschlüssel vergessen', 'symbol' => 'schloss-offen',
                              'art' => 'leise', 'form' => 'sz-zh-' . $zid,
                              'attr' => ' data-confirm="Den gespeicherten Hostschlüssel '
                                      . 'vergessen? Die nächste Prüfung übernimmt den, den '
                                      . 'der Server dann zeigt — das ist der Weg nach einem '
                                      . 'Schlüsselwechsel der Gegenstelle und sonst nichts."'
                                      . ' data-confirm-ok="Vergessen" data-confirm-tone="normal"'];
          }
          $eintraege[] = ['text' => 'Löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                          'form' => 'sz-zl-' . $zid,
                          'attr' => ' data-confirm="Das Ziel &quot;' . ui_e((string)$z['name'])
                                  . '&quot; entfernen? Was dort liegt, bleibt liegen — '
                                  . 'gelöscht wird auf dem Ziel nichts."'
                                  . ' data-confirm-ok="Entfernen" data-confirm-tone="gefahr"'];
          ui_zeile([
              'klasse' => 'zeile-umbruch',
              'text' => (string)$z['name'],
              'klein' => $klein,
              'plaketten' => $plaketten,
              /* ALLES INS MENÜ (`blatt_immer`): Fünf Knöpfe passen nicht in
               * die Zeile (gemessen +156 px Überlauf bei 768 px), und
               * „Löschen" stünde in Rot neben „Bearbeiten". */
              'aktionen' => ui_zeilenaktionen(['eintraege' => $eintraege,
                                               'blatt_immer' => true,
                                               'titel' => (string)$z['name']]),
          ]);
          if (($z['letzter_fehler'] ?? null) !== null) {
              /* DER FEHLER STEHT DA, BIS ER WEG IST — im Protokoll muss man
                 ihn suchen. */
              ui_zeile(['text' => 'Zuletzt gescheitert',
                        'klein' => (string)$z['letzter_fehler'],
                        'plaketten' => ui_plakette(
                            datum_zeit_text((string)$z['letzter_lauf']), ['ton' => 'rot'])]);
          }
      }

      /* DREI GRÜNDE FÜR „KEINE ZAHL", UND JEDER SAGT SEINEN. Gezählt werden
         Konto-Backups, Komplett-Stände und Archive des Protokolls; das Wort
         dafür ist „Sicherungen", nicht „Pakete". Die Zahl kostet eine
         Abfrage je Ziel und wird deshalb nur bei eingeschaltetem Versand
         gerechnet. */
      $rueck = $autoAn ? sz_versand_rueckstand() : null;
      $rueckKlein = !$autoAn ? 'keine Aussage, der Versand ist aus'
          : ($aktive === 0 ? 'keine Aussage, es gibt kein aktives Ziel'
          : ($rueck === null
              ? 'noch keine Aussage — ein Ziel lief noch nie erfolgreich'
              : ($rueck === 1 ? '1 Sicherung ist' : $rueck . ' Sicherungen sind')
                . ' neuer als der letzte erfolgreiche Versand (Schätzung)'));
      /* „JETZT VERSENDEN" STEHT AN DIESER ZEILE: Es arbeitet genau das ab,
       * was sie zählt. Dieser Weg fragt den Schalter nicht — der Klick ist
       * die Zustimmung. */
      ui_zeile(['klasse' => 'zeile-umbruch', 'text' => 'Wartet auf den nächsten Lauf',
                'klein' => $rueckKlein,
                'plaketten' => $rueck === null
                    ? ui_plakette('unbekannt', ['ton' => 'neutral'])
                    : ui_plakette((string)$rueck, ['ton' => $rueck > 0 ? 'orange' : 'gruen']),
                'aktionen' => $l['schluessel'] && $aktive > 0
                    ? ui_knopf(['text' => 'Jetzt versenden', 'symbol' => 'tausch',
                                'art' => 'neutral', 'attr' => ' form="sz-versand"'])
                    : '']);
      ?>
      <?php /* SPEICHERN STEHT ALS LETZTES IM ABSCHNITT (E-OF-32) und speichert
               nur den Schalter. „Ziel anlegen" daneben ist kein Absenden,
               sondern öffnet den Dialog (`type="button"`). */ ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        <?php if ($l['schluessel']): ?>
          <?= ui_knopf(['text' => 'Ziel anlegen', 'symbol' => 'plus', 'art' => 'neutral',
                        'typ' => 'button', 'attr' => ' data-dialog="dlg-sz-neu"']) ?>
        <?php endif; ?>
      </div>
    </form>
    <?php
    ui_karte_ende(true);
}

/**
 * Die Dialoge „Neues Ziel" und je Ziel „Ziel bearbeiten" — am Seitenende,
 * außerhalb jedes Formulars.
 *
 * EIN DIALOG JE ZIEL, nicht einer für alle mit `data-w-…`: Das Formular
 * unterscheidet sich je Ziel nicht nur in den Werten — der Schalter
 * „Hinterlegten Schlüssel entfernen" steht nur da, wo einer hinterlegt ist,
 * und die Kleinzeilen von Passwort und Schlüssel sagen dann etwas anderes.
 * Ziele gibt es eine Handvoll.
 *
 * @param string     $zurueck wie bei sicherungsziele_karte()
 * @param array|null $meldung wie bei sicherungsziele_karte(); trägt sie
 *                            `daten.zielform`, steht deren Eingabe im
 *                            Dialog, und er geht beim Laden wieder auf
 */
function sicherungsziele_dialoge(string $zurueck, ?array $meldung = null): void
{
    $l = sz_teile_lage();
    if (!$l['tabelle'] || !$l['schluessel']) { return; }

    $fehlForm = is_array($meldung['daten']['zielform'] ?? null)
        ? $meldung['daten']['zielform'] : null;
    $fehlId = $fehlForm !== null ? (int)($fehlForm['id'] ?? 0) : null;

    $faelle = [0 => null];
    foreach ($l['ziele'] as $z) { $faelle[(int)$z['id']] = $z; }
    foreach ($faelle as $zid => $z) {
        $fehler = null;
        $werte = $z ?? [];
        $gescheitert = $fehlId === $zid;
        if ($gescheitert) {
            /* NACH EINEM FEHLSCHLAG STEHT DA, WAS EINGEGEBEN WAR — ohne
             * Passwort und Schlüssel, die reisen nicht durch die Sitzung.
             * `schluessel` bleibt aus der Datenbankzeile: Es entscheidet nur,
             * ob „entfernen" angeboten wird. */
            $werte = array_merge($werte, (array)($fehlForm['werte'] ?? []));
            $fehler = (string)($meldung['text'] ?? '');
        }
        sz_teile_dialog($zid, $werte, $gescheitert, $fehler, $zurueck);
    }
    /* `array_key_exists`, NICHT `isset`: Der Fall „Neues Ziel" steht unter 0
       mit dem Wert null, und `isset()` hielte ihn fuer abwesend — nach einem
       abgewiesenen Anlegen ginge der Dialog nie wieder auf. */
    if ($fehlId !== null && array_key_exists($fehlId, $faelle)) {
        ?>
<script<?= kopf_nonce_attr() ?>>
/* NACH EINEM ABGELEHNTEN SPEICHERN GEHT DER DIALOG WIEDER AUF. `auf()` füllt
   bewusst nichts nach — im Markup steht schon die verworfene Eingabe.
   DOMContentLoaded, weil `dialog.js` erst mit `ui_seite_ende()` geladen wird. */
document.addEventListener('DOMContentLoaded', function () {
  if (window.edDialog) { window.edDialog.auf(<?= json_js('dlg-sz-' . ($fehlId === 0 ? 'neu' : $fehlId)) ?>); }
});
</script>
<?php
    }
}

/**
 * Ein Zieldialog. `$zid` 0 ist „Neues Ziel".
 *
 * @param array $form Datenbankzeile, nach einem Fehlschlag mit der Eingabe verschmolzen
 */
function sz_teile_dialog(int $zid, array $form, bool $gescheitert, ?string $fehler,
                         string $zurueck): void
{
    $neu = $zid === 0;
    /* EIGENE KENNUNGEN JE DIALOG: Dieselben Felder stehen in jedem Dialog,
     * und `ui_feld()` leitet die Kennung aus dem Namen ab — ohne Vorsilbe
     * zeigte jede Beschriftung auf das Feld des ersten Dialogs. */
    $p = 'sz' . $zid . '-';
    ?>
  <dialog class="dialog" id="dlg-sz-<?= $neu ? 'neu' : $zid ?>">
    <form method="post" action="admin_sicherungsziele.php">
      <?= csrf_field() ?><?= sz_teile_zurueck_feld($zurueck) ?>
      <input type="hidden" name="action" value="ziel_speichern">
      <input type="hidden" name="id" value="<?= $zid ?>">
      <div class="dialog-kopf">
        <h2><?= $neu ? 'Neues Ziel' : 'Ziel bearbeiten' ?></h2>
        <?php if (!$neu): ?><p class="unterzeile"><?= ui_e((string)($form['name'] ?? '')) ?></p><?php endif; ?>
      </div>
      <div class="dialog-inhalt">
        <?php if ($fehler !== null && $fehler !== ''): ?>
          <?= ui_meldung_markup('fehler', $fehler, 'Nicht gespeichert.') ?>
        <?php endif; ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'name', 'id' => $p . 'name', 'label' => 'Name', 'pflicht' => true,
                         'wert' => (string)($form['name'] ?? ''),
                         'klein' => 'Steht in Meldungen und im Versandprotokoll.']); ?>
          <?php ui_feld(['name' => 'protokoll', 'id' => $p . 'protokoll', 'label' => 'Protokoll',
                         'art' => 'select', 'optionen' => SZ_PROTOKOLLE,
                         'wert' => (string)($form['protokoll'] ?? 'sftp'),
                         'klein' => 'SFTP erkennt den Server am Hostschlüssel wieder, '
                                  . 'FTPS verschlüsselt nur die Leitung.']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'host', 'id' => $p . 'host', 'label' => 'Rechnername',
                         'pflicht' => true, 'wert' => (string)($form['host'] ?? ''),
                         'platzhalter' => 'backup.example.de']); ?>
          <?php ui_feld(['name' => 'port', 'id' => $p . 'port', 'label' => 'Port', 'art' => 'number',
                         'pflicht' => true, 'attr' => 'min="1" max="65535"',
                         'wert' => (string)($form['port'] ?? SZ_PORTS['sftp']),
                         'klein' => 'Üblich: 22 für SFTP, 21 für FTPS.']); ?>
        </div>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'nutzer', 'id' => $p . 'nutzer', 'label' => 'Nutzername',
                         'pflicht' => true, 'wert' => (string)($form['nutzer'] ?? '')]); ?>
          <?php ui_feld(['name' => 'pfad', 'id' => $p . 'pfad', 'label' => 'Pfad auf dem Ziel',
                         'wert' => (string)($form['pfad'] ?? '/'),
                         'klein' => 'Mit einem Unterordner je Konto.']); ?>
        </div>
        <?php ui_feld(['name' => 'geheim', 'id' => $p . 'geheim', 'label' => 'Passwort',
                       'art' => 'password', 'wert' => '',
                       'klein' => $neu
                           ? 'Bei privatem Schlüssel dessen Passphrase, leer, wenn er keine hat.'
                           : 'Leer lassen heißt unverändert.']); ?>
        <?php ui_feld(['name' => 'schluessel', 'id' => $p . 'schluessel',
                       'label' => 'Privater Schlüssel (nur SFTP)',
                       'art' => 'textarea', 'zeilen' => 4, 'wert' => '',
                       'platzhalter' => "-----BEGIN OPENSSH PRIVATE KEY-----\n"
                                      . "(der ganze Schlüssel)\n"
                                      . "-----END OPENSSH PRIVATE KEY-----",
                       'klein' => 'Vollständig mit BEGIN- und END-Zeile'
                                . (($form['schluessel'] ?? null) !== null
                                   ? '; leer lassen heißt, der hinterlegte bleibt.'
                                   : '.')]); ?>
        <?php if (($form['schluessel'] ?? null) !== null): ?>
          <?php ui_schalter(['name' => 'schluessel_weg', 'id' => $p . 'schluessel-weg',
                             'label' => 'Hinterlegten Schlüssel entfernen', 'an' => false,
                             'klein' => 'Danach wird wieder mit Passwort angemeldet.']); ?>
        <?php endif; ?>
        <?php ui_schalter(['name' => 'passiv', 'id' => $p . 'passiv',
                           'label' => 'Passiver Modus (nur FTPS)',
                           'an' => $gescheitert ? !empty($form['passiv'])
                                                : (int)($form['passiv'] ?? 1) === 1,
                           'klein' => 'Aus nur für eine Gegenstelle, die aktives FTP verlangt.']); ?>
        <?php ui_schalter(['name' => 'aktiv', 'id' => $p . 'aktiv', 'label' => 'Ziel benutzen',
                           'an' => $gescheitert ? !empty($form['aktiv'])
                                                : (int)($form['aktiv'] ?? 1) === 1,
                           'klein' => 'Aus heißt: Es bleibt eingetragen, der Versand überspringt es.']); ?>
        <?php /* DIE AUFBEWAHRUNG AUF DEM ZIEL IST EINE OPTION UND NIE DIE
                 VORGABE. Ein auswärtiges Ziel soll den Ausfall dieses Servers
                 überleben — samt eines Fehlers, der HIER zu viel löscht; ein
                 Versand, der drüben aufräumt, trägt ihn mit hinüber.

                 NACH EINEM FEHLSCHLAG GILT, WAS DAGESTANDEN HAT: Ein nicht
                 gesetzter Haken fehlt in der Eingabe ganz, die beiden Zahlen
                 aber nicht. Ohne diese Unterscheidung stünde der Haken nach
                 einem Tippfehler im Port wieder an. */ ?>
        <?php $aufAn = $gescheitert
                       ? !empty($form['aufraeumen'])
                       : (($form['behalten_konto'] ?? null) !== null
                          || ($form['behalten_komplett'] ?? null) !== null); ?>
        <?php ui_schalter(['name' => 'aufraeumen', 'id' => $p . 'aufraeumen',
                           'label' => 'Auf dem Ziel aufräumen', 'an' => $aufAn,
                           'klein' => 'Entfernt dort alte Sicherungen dieser Installation über '
                                    . 'der Zahl darunter, nie fremde Dateien.']); ?>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'behalten_konto', 'id' => $p . 'behalten-konto',
                         'label' => 'Je Konto behalten', 'art' => 'number',
                         'attr' => 'min="1" max="999"',
                         'wert' => (string)($form['behalten_konto'] ?? 6),
                         /* DIE VORGABE unter Verwaltung → Konto-Backups, keine
                            feste Zahl: Ein Konto kann eine eigene haben. */
                         'klein' => 'Hier gilt als Vorgabe ' . edbak_aufbewahrung()
                                  . ' je Konto, am Ziel darf es mehr sein.']); ?>
          <?php ui_feld(['name' => 'behalten_komplett', 'id' => $p . 'behalten-komplett',
                         'label' => 'Komplett-Stände behalten', 'art' => 'number',
                         'attr' => 'min="1" max="999"',
                         'wert' => (string)($form['behalten_komplett'] ?? 12),
                         'klein' => 'Aus einem Komplett-Stand lässt sich jedes Konto '
                                  . 'wiederherstellen, umgekehrt nicht.']); ?>
        </div>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => $neu ? 'Anlegen' : 'Speichern',
                      'symbol' => $neu ? 'plus' : 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  </dialog>
<?php
}
