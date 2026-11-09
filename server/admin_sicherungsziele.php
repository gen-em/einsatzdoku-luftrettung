<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
/* NUR DIE BETREIBERIN, wie der Kopf der Rollen in `db.php` sagt; die
 * Rollenprobe prüft es je Handlung. */
require_betreiberin();
require_once __DIR__ . '/sicherungsziele_teile.php';   // SZ_KARTE_ID, sz_zurueck_seite(), SZ_FORMFELDER
require_once __DIR__ . '/format_lib.php';              // groesse_text()

/**
 * BACKUP-ZIELE — der Schreibweg, keine Seite mehr.
 *
 * WARUM HIER NICHTS MEHR ANGEZEIGT WIRD. Seit Web 24.7 (Konzept OF, E-OF-24,
 * E-OF-45) stehen die Ziele als Klappkarte an zwei Orten: in den
 * Servereinstellungen und im Komplett-Backup. Die Karte entsteht einmal in
 * `sicherungsziele_teile.php`; ihre Formulare posten hierher. Diese Datei
 * nimmt den POST an, tut die Handlung und leitet IMMER um — auf die Seite,
 * von der das Formular kam (`zurueck`, nur aus `SZ_ZURUECK_SEITEN`), mit
 * dem Anker der Karte. Die Meldung reist per `flash_setzen()` mit dem
 * `ort` der Karte; die einbindende Seite reicht sie der Karte durch.
 *
 * AUCH JEDER FEHLER LEITET UM. Bis Web 24.6 blieb eine Abweisung auf dieser
 * Seite stehen, die Eingabe im Formular. Die Seite gibt es nicht mehr; ein
 * Fehler, der hier stehen bliebe, stünde auf einer weissen Fläche. Ein
 * gescheitertes Speichern schickt deshalb seine Eingabe (ohne Passwort und
 * Schlüssel) in der Meldung mit, und der Dialog geht drüben wieder auf.
 *
 * EIN GET führt auf die Servereinstellungen — alte Lesezeichen, die
 * Statuszeilen und `?neu=1`/`?bearbeiten=…` aus der Zeit der Seite landen
 * damit bei der Karte, statt ins Leere.
 *
 * DREI DINGE, DIE HIER ZUSAMMENKOMMEN
 *   1. Der SERVERSCHLÜSSEL. Ohne ihn lässt sich kein Zugangsdatum speichern;
 *      die Karte bietet „Ziel anlegen" dann gar nicht erst an.
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

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ' . SZ_ZURUECK_SEITEN[0] . '#' . SZ_KARTE_ID);
    exit;
}

/* DAS TOKEN VOR ALLEM ANDEREN, auch vor dem Ziel der Umleitung. */
csrf_check();
$ziel = sz_zurueck_seite(post_text('zurueck')) . '#' . SZ_KARTE_ID;

/**
 * Meldung hinterlegen und zurück zur Karte. Endet immer.
 *
 * @param array $daten was die Karte ausser dem Satz braucht (siehe Kopf der Teile)
 */
$zurueck = static function (string $ton, string $text, array $daten = []) use ($ziel): never {
    flash_setzen($ton, $text, SZ_KARTE_ID, $daten);
    header('Location: ' . $ziel);
    exit;
};

if (!sz_tabelle_da()) {
    $zurueck('error', 'Die Tabelle für die Backup-Ziele fehlt noch — bitte unter '
        . 'Betrieb → Updates die ausstehenden Updates ausführen.');
}

$aktion = post_text('action');
$id = (int)($_POST['id'] ?? 0);

if ($aktion === 'ziel_speichern') {
    /* Ein LEERES Passwortfeld heisst „nicht anfassen", nicht „löschen".
     * Deshalb `null` statt `''` — die Bibliothek unterscheidet beides. */
    $geheim = ($_POST['geheim'] ?? '') === '' ? null : post_text('geheim');
    $schluessel = trim(post_text('schluessel'));
    $schluesselNeu = $schluessel === '' ? null : $schluessel;
    if (($_POST['schluessel_weg'] ?? '') === '1') { $schluesselNeu = ''; }
    /* DER ALTE STAND VOR DEM SPEICHERN.
     * Stuende `sz_lesen()` erst nach `sz_speichern()`, laese es
     * die neue Zeile: Der Vergleich unten schluege nie an, und ein
     * SFTP-Ziel auf neuem Host behielte den Abdruck des alten — die
     * naechste Verbindung scheiterte als „ANDERER Hostschluessel". */
    $vorher = $id > 0 ? sz_lesen($id) : null;
    if ($id > 0 && $vorher === null) {
        $zurueck('error', 'Dieses Ziel gibt es nicht (mehr).');
    }
    [$ok, $was] = sz_speichern($id > 0 ? $id : null, $_POST, $geheim, $schluesselNeu);
    if (!$ok) {
        /* DIE EINGABE REIST MIT, damit niemand alles noch einmal tippt, nur
         * um zu erfahren, dass der Port fehlt. NUR DIE FELDER DES FORMULARS:
         * `csrf`, `action` und `zurueck` gehören nicht hinein, Passwort und
         * Schlüssel bleiben aus der Sitzung. */
        $werte = [];
        foreach (SZ_FORMFELDER as $f) {
            if (isset($_POST[$f])) { $werte[$f] = post_text($f); }
        }
        $zurueck('error', implode(' ', (array)$was),
                 ['zielform' => ['id' => $id > 0 ? $id : 0, 'werte' => $werte]]);
    }
    $notice = $id > 0 ? 'Das Ziel wurde geändert.' : 'Das Ziel wurde angelegt.';
    /* Der Fingerabdruck gehört zum Rechner. Zieht ein Ziel auf einen
     * anderen Host um, ist der alte Abdruck falsch — und ein falscher
     * Abdruck ist schlimmer als keiner, weil er jede Verbindung
     * blockiert und wie ein Angriff aussieht. Verglichen wird mit
     * der gespeicherten Zeile, nicht mit `$_POST` — `sz_speichern()`
     * normalisiert, und ein Leerzeichen am Host ist kein Umzug. */
    $nachher = $vorher !== null ? sz_lesen($id) : null;
    if ($vorher !== null && $nachher !== null
        && ((string)$vorher['host'] !== (string)$nachher['host']
            || (int)$vorher['port'] !== (int)$nachher['port'])) {
        sz_fingerabdruck_merken($id, null);
        $notice .= ' Der Hostschlüssel wurde vergessen, weil sich der Rechner geändert '
                 . 'hat — er wird bei der nächsten Prüfung neu übernommen.';
    }
    $zurueck('notice', $notice);
}

if ($aktion === 'ziel_loeschen' && $id > 0) {
    $z = sz_lesen($id);
    if ($z !== null && sz_loeschen($id)) {
        $zurueck('notice', 'Das Ziel „' . (string)$z['name'] . '“ wurde entfernt. '
            . 'Was dort liegt, bleibt liegen — gelöscht wird auf dem Ziel nichts.');
    }
    $zurueck('error', 'Dieses Ziel gibt es nicht (mehr).');
}

if ($aktion === 'abdruck_vergessen' && $id > 0) {
    sz_fingerabdruck_merken($id, null);
    $zurueck('notice', 'Der gespeicherte Hostschlüssel wurde vergessen. Die nächste '
        . 'Prüfung übernimmt den, den der Server dann zeigt — vorher '
        . 'vergewissern, dass er der richtige ist.');
}

if ($aktion === 'versand_schalter') {
    $an = ($_POST['versand_auto'] ?? '') === '1';
    if (!sz_auto_setzen($an)) {
        $zurueck('error', 'Der Schalter liess sich nicht speichern.');
    }
    $zurueck('notice', $an
        ? 'Der Versand läuft ab jetzt mit dem Aufräumjob mit — wie oft, hängt vom '
          . 'Auslöser unter Betrieb → Hintergrundjobs ab.'
        : 'Der Versand ist abgeschaltet; die Ziele bleiben eingetragen.');
}

if ($aktion === 'jetzt_versenden') {
    /* DIESER WEG FRAGT DEN SCHALTER NICHT. Hier hat gerade jemand
     * geklickt, und das ist die Zustimmung — der Schalter beantwortet
     * die andere Frage, nämlich ob es auch OHNE Klick passieren soll. */
    $anfang = microtime(true);
    $e = sz_versand_schub(static fn(): float => SICHERN_BUDGET_VERSAND
                                              - (microtime(true) - $anfang), 5.0);
    $satz = $e['gesendet'] . ($e['gesendet'] === 1 ? ' Datei' : ' Dateien')
          . ' an ' . $e['ziele'] . ($e['ziele'] === 1 ? ' Ziel' : ' Ziele')
          . ' gesendet (' . groesse_text($e['bytes']) . ').'
          /* WAS DORT ENTFERNT WURDE, STEHT IM ERFOLGSSATZ.
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
        $zurueck('error', $satz . ' ' . implode(' ', $e['fehler']));
    }
    $zurueck('notice', $e['ziele'] === 0
        ? 'Es ist kein aktives Ziel eingetragen — es wurde nichts gesendet.'
        : $satz);
}

if ($aktion === 'ziel_pruefen' && $id > 0) {
    $z = sz_lesen($id);
    if ($z === null) {
        $zurueck('error', 'Dieses Ziel gibt es nicht (mehr).');
    }
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
    /* Meldung, Schritte und „übernommen" reisen als Ergebnis mit; ein
     * Hostschlüssel ist öffentlich, ein Zugangsdatum steht nicht darin. */
    $zurueck($ergebnis['ok'] ? 'ok' : 'fehler', (string)$ergebnis['meldung'],
             ['ergebnis' => $ergebnis]);
}

if ($aktion === 'ziel_bestand' && $id > 0) {
    /* ---- NACHSEHEN, WAS DORT LIEGT --------------
     *
     * AUF KNOPFDRUCK UND NICHT BEI JEDEM SEITENAUFRUF. Diese Auskunft
     * kostet eine Verbindung und je Ordner eine Listenabfrage; bei drei
     * Zielen und dreissig Konten sind das neunzig Anfragen über eine
     * Leitung, die auch mal langsam ist. Wer es wissen will, fragt.
     *
     * SIE LÖSCHT NICHTS, UND SIE WIRD NICHT GESPEICHERT. Bis Web 24.6 stand
     * sie in der Antwort auf den POST; jetzt reist sie einmal mit der
     * Umleitung über die Sitzung und ist danach weg — eine gespeicherte
     * Momentaufnahme wäre beim nächsten Aufruf eine Behauptung. */
    $z = sz_lesen($id);
    if ($z === null) {
        $zurueck('error', 'Dieses Ziel gibt es nicht (mehr).');
    }
    $weg = null;
    $bestand = null;
    $fehler = null;
    try {
        $weg = sz_weg($z);
        $weg->verbinden();
        $bestand = sz_bestand($weg, $id);
        $bestand['ziel'] = (string)$z['name'];
    } catch (ZielFehler $e) {
        $fehler = 'Bei „' . (string)$z['name'] . '“ ging es nicht weiter: ' . $e->getMessage();
    } finally {
        if ($weg !== null) { try { $weg->trennen(); } catch (Throwable $x) {} }
    }
    if ($fehler !== null) {
        $zurueck('error', $fehler);
    }
    $zurueck('info', 'Eine Momentaufnahme, die nicht gespeichert wird; was nicht dem '
        . 'Namensmuster einer Sicherung entspricht, zählt als fremd.',
        ['bestand' => $bestand]);
}

/* EINE UNBEKANNTE HANDLUNG — ein veraltetes Formular oder ein Ziel ohne
 * Kennung. Auch das geht zurück, mit einem Satz statt einer leeren Seite. */
$zurueck('error', 'Diese Handlung kennt die Karte „Backup-Ziele“ nicht.');
