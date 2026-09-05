<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/adminbackup_lib.php';
require_once __DIR__ . '/smtp.php';        // smtp_eingerichtet() (E-S2-15)

/**
 * KONTO-BACKUPS — die Pakete, die die VERWALTUNG je Konto anlegt.
 *
 * DER NAME IST DER BEFUND (E-S8-06, B-S8-08). „Backups" hiess hier dreierlei:
 * die Pakete der Verwaltung, das `.edbak`, das eine NutzerIn sich selbst
 * herunterlaedt, und der Komplett-Stand der Installation. Drei Dinge, ein
 * Wort — und der Untertitel dieser Seite sagt jetzt in einem Satz, welches
 * gemeint ist. Die beiden anderen heissen „Backup" (NutzerIn) und
 * „Komplett-Backup" (Installation).
 *
 * WAS IN S8/AP3 GEGANGEN IST: die Karte „Ablage". Pfad, Zustand, Belegung und
 * Reste stehen unter Betrieb → Servereinstellungen, zusammen mit der Grenze,
 * gegen die sie gemessen werden — und seit AP3 auch das juengste Paket. Was
 * hier bleibt, gilt JE KONTO.
 *
 * Der Werdegang bis dahin (E-P3-41, P3/O9c):
 *
 * WAS SICH GEAENDERT HAT. Bis Web 9.9.0 stand hier alles: eine Tabelle mit
 * jedem Konto und seinen Paketen, eine zweite mit jedem einzelnen Backup
 * der ganzen Installation, dazu je Zeile ein aufklappbares Formular zum
 * Einspielen, Freigeben und Loeschen. Bei dreissig Konten war das eine lange
 * Seite; bei dreihundert war es F-P3-F — sie las je Konto ein Verzeichnis UND
 * eine Begleitdatei, um eine Zeile zu zeigen, die man nie ansieht.
 *
 * Seit Web 9.8.0 liegen die Backups eines Kontos auf dessen Kontoseite,
 * seit Web 9.9.0 zaehlt die NutzerInnen-Liste, welche Konten faellig sind.
 * Hier bleibt, was WEDER zu einem Konto noch in eine Liste gehoert:
 *
 *   die REGELN       Erinnerungsintervall, Aufbewahrung je Konto,
 *                    Erinnerung an die Administration per E-Mail
 *   „OHNE KONTO"     Ordner, zu denen es keine Kontozeile mehr gibt — der
 *                    Fall „Konto geloescht und neu aufgesetzt" (A8.2). Sie
 *                    haben keine Kontoseite; ihr Weg ist nur hier.
 *   „WAS HIER GILT"  die drei Backups, die Freigabe, die Schluessel — eine
 *                    zugeklappte Karte am Ende, wie auf jeder Seite der drei
 *                    Bloecke (Regel 5 aus E-S8-01).
 *
 * Die Seite liest damit EIN Verzeichnis fuer die Zahlen (edbak_ablage_zahlen)
 * und EIN weiteres fuer die verwaisten Ordner. Die Kontenschleife ist fort.
 */

/**
 * Zeitbudget eines Durchgangs „Alle sichern" in Sekunden.
 *
 * Dieselbe Ueberlegung wie bei der Sammelaktion der NutzerInnen-Liste: Ein
 * Backup dauert gemessen 222 ms bei einem Konto mit 82 Einsaetzen, und
 * zwanzig Sekunden liegen unter der `max_execution_time`, die geteilter
 * Webspace ueblicherweise setzt.
 */
const SICHERN_BUDGET = 20.0;

$notice = null; $error = null; $bericht = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');
    /* Aus der Oberfläche kommt der Handgriff, nicht die Kennung (Kriterium 49).
     * Lässt er sich nicht auflösen, gibt es den Ordner nicht — dann laufen die
     * Aktionen unten ins Leere und melden das, statt auf einem geratenen Pfad
     * zu arbeiten. */
    $kennung = edbak_kennung_aus_handgriff((string)($_POST['handgriff'] ?? '')) ?? '';
    $datei   = (string)($_POST['datei'] ?? '');

    /* ---- Die Regeln: ein Formular, ein Speichern (E-P3-41) --------------- */
    if ($action === 'regeln') {
        $tage  = (int)($_POST['tage'] ?? 0);
        $pakete = (int)($_POST['pakete'] ?? 0);
        $teile = [];
        if ($tage < 1 || $tage > 3650) {
            $error = 'Bitte ein Erinnerungsintervall zwischen 1 und 3650 Tagen angeben.';
        } elseif ($tage !== edbak_intervall()) {
            edbak_marke_setzen('adminbackup_intervall', (string)$tage);
            $teile[] = 'Erinnerung nach ' . $tage . ' Tagen';
        }
        if ($error === null) {
            if ($pakete < 1 || $pakete > 100) {
                $error = 'Bitte eine Aufbewahrung zwischen 1 und 100 Paketen angeben.';
            } elseif ($pakete !== edbak_aufbewahrung()) {
                edbak_marke_setzen('adminbackup_aufbewahrung', (string)$pakete);
                $teile[] = 'Aufbewahrung ' . $pakete . ' Pakete';
            }
        }
        /* SPEICHERGRENZE UND WARNSCHWELLEN STEHEN SEIT WEB 15.1.0 NICHT
         * MEHR HIER (E-S8-05, B-S8-06). Sie wirkten auf Konto-Backups UND auf
         * die Komplett-Staende, standen aber unter „Backups", und die
         * Komplett-Seite verwies mit einem Satz auf sie. Jetzt liegen sie im
         * Betrieb unter „Servereinstellungen", zusammen mit der Belegung.
         *
         * Was hier bleibt, gilt JE KONTO: Erinnerung, Aufbewahrung, Admin-Mail.
         * Der Schnitt ist die Antwort auf Nr. 79 — die Ordnung folgt der
         * Zielgruppe (R74), nicht der Reihenfolge des Einbaus. */
        if ($error === null) {
            $mailAn = ($_POST['mail'] ?? '') === '1';
            if ($mailAn !== edbak_admin_mail_an()) {
                edbak_marke_setzen('adminbackup_mail', $mailAn ? '1' : '0');
                $teile[] = 'Erinnerung per E-Mail ' . ($mailAn ? 'ein' : 'aus');
                /* BEIM EINSCHALTEN DIE UHR NEU STELLEN. Stuende die alte Marke
                 * noch, kaeme die erste Mail erst sieben Tage nach der letzten
                 * von damals — und wer den Schalter gerade umgelegt hat,
                 * erwartet die naechste faellige Erinnerung, nicht eine aus
                 * dem Rhythmus einer abgeschalteten Zeit. */
                if ($mailAn) { edbak_marke_setzen('adminbackup_mail_last', ''); }
            }
            $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                             : 'Es gab nichts zu ändern.';
        }
    }

    /* ---- Alle sichern (A8.3) --------------------------------------------
     *
     * „ALLE" HEISST ALLE, aber nicht in einer Anfrage. Ein Backup dauert
     * gemessen 222 ms bei einem Konto mit 82 Einsaetzen; bei dreihundert
     * Konten liefe die Anfrage in die Zeitgrenze des Webspace und braeche
     * mittendrin ab — mit einem Teil erledigt und ohne Auskunft darueber,
     * welchem.
     *
     * Statt eines Merkzettels zwischen den Anfragen entscheidet die
     * REIHENFOLGE: Gesichert wird, was am laengsten her ist, zuerst. Wer eben
     * gesichert wurde, steht danach ganz hinten. Ein zweiter Klick macht
     * deshalb genau dort weiter, wo der erste aufgehoert hat — ohne dass sich
     * irgendetwas irgendetwas merken muesste. Nach genug Klicks ist jedes
     * Konto genau einmal drangewesen, und die Seite sagt jedes Mal, wie viele
     * noch offen sind.
     *
     * Schuebe im Hintergrund sind auf P5 vertagt (E-P3-41).
     */
    if ($action === 'sichern_alle') {
        /* EINE WARTESCHLANGE STATT EINER HEURISTIK (S2/AP6).
         *
         * Bis Web 12.0.0 gab es keinen Merkzettel: Die Konten wurden nach dem
         * Alter ihres letzten Backups sortiert, abgearbeitet, bis die Zeit
         * knapp wurde, und der zweite Klick sollte dort weitermachen, wo der
         * erste aufhoerte — wer eben gesichert wurde, steht ja hinten.
         *
         * Das traegt nur, wenn sich die Konten um mindestens einen ganzen Tag
         * unterscheiden: gerechnet wird in TAGEN. Wer heute alle Konten
         * sichert, hat danach lauter Nullen, und bei Gleichstand ist die
         * Reihenfolge beliebig — der zweite Klick nimmt womoeglich dieselben
         * Konten noch einmal, und die letzten kommen nie dran.
         *
         * Jetzt sagt die Schlange, wer noch offen ist. Sie wird von zwei
         * Seiten geleert: hier, solange diese Anfrage Zeit hat, und vom
         * Wartungsjob `adminbackup` in Schueben. */
        $laufend = edbak_auftrag_lesen();
        if ($laufend === null) { $laufend = edbak_auftrag_starten(); }

        if ((int)($laufend['ges'] ?? 0) === 0) {
            edbak_auftrag_schreiben(null);
            $error = 'Es gibt kein Konto mit Kontokennung. Bitte zuerst die Wartung '
                   . 'aufrufen und die Migration ausführen.';
        } else {
            $t0 = microtime(true);
            $e = edbak_auftrag_schub(
                static fn(): float => SICHERN_BUDGET - (microtime(true) - $t0), 0.2);
            $a = $e['auftrag'];
            $gut = (int)($a['gut'] ?? 0);
            $gesamt = (int)($a['ges'] ?? 0);
            $notice = $gut . ' von ' . $gesamt . ' '
                    . ($gesamt === 1 ? 'Konto gesichert.' : 'Konten gesichert.');
            if ($e['offen'] > 0) {
                $notice .= ' ' . $e['offen'] . ' '
                         . ($e['offen'] === 1 ? 'Konto ist' : 'Konten sind')
                         . ' noch offen — die Zeit für eine Anfrage reicht nicht für '
                         . 'alle. Der Aufräumjob macht in Schüben weiter; ein zweiter '
                         . 'Klick auf „Alle sichern" ebenfalls, und zwar genau dort, wo '
                         . 'dieser Durchgang aufgehört hat.';
            }
            if ($e['meldungen']) {
                $error = 'Nicht erzeugt (' . (int)($a['feh'] ?? 0) . '): '
                       . implode(' · ', $e['meldungen']);
            }
        }
    }

    /* ---- Einspielen aus einem Konto-Backup OHNE KONTO (A8.6) ---------- */
    if ($action === 'einspielen') {
        $ziel = edbak_ziel_konto((int)($_POST['ziel_user'] ?? 0));
        /* NUR DER KOPF FUER DIE ENTSCHEIDUNG (S2/AP6). edbak_weg() braucht
         * Herkunftskennung, Huelle und die Zahl der geschuetzten Angaben —
         * alles steht im Manifest. Das ganze Paket dafuer zu lesen hiesse bei
         * einem grossen Konto, 11 MB zu entpacken, um eine Ja/Nein-Frage zu
         * beantworten. */
        $paket = edbak_paket_kopf_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Das Paket liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts eingespielt.';
        } else {
            [$weg, $warum] = edbak_weg($paket, $ziel);
            if ($weg === 'gesperrt') {
                $error = 'Einspielen nicht möglich. ' . $warum;
            } elseif ($weg === 'freigabe') {
                $error = 'Unmittelbares Einspielen ist gesperrt. ' . $warum
                       . ' Bitte stattdessen das Paket für dieses Konto freigeben.';
            } else {
                try {
                    [$okE, $grundE, $bericht] =
                        edbak_paket_zurueckspielen($kennung, $datei, (int)$ziel['id']);
                    if ($okE) { $notice = 'Konto-Backup eingespielt in ' . $ziel['email'] . '.'; }
                    else { $error = (string)$grundE; }
                } catch (Throwable $ex) {
                    $error = 'Das Einspielen ist fehlgeschlagen (Kennung '
                           . fehler_kennung($ex, 'adminbackup') . ').';
                }
            }
        }
    }

    /* ---- Freigeben und widerrufen (A8.6) --------------------------------- */
    if ($action === 'freigeben') {
        $ziel = edbak_ziel_konto((int)($_POST['ziel_user'] ?? 0));
        $paket = edbak_paket_kopf_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Das Paket liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts freigegeben.';
        } elseif (edbak_freigeben($kennung, $datei, (int)$ziel['id'])) {
            $notice = 'Freigegeben für ' . $ziel['email'] . '. Die NutzerIn sieht das '
                    . 'Paket jetzt im eigenen Backup-Bereich und spielt es dort '
                    . 'mit ihrem Wiederherstellungsschlüssel ein.';
        } else {
            $error = 'Die Freigabe liess sich nicht speichern.';
        }
    }
    if ($action === 'widerrufen') {
        if (edbak_freigabe_widerrufen($kennung)) { $notice = 'Freigabe widerrufen.'; }
        else { $error = 'Die Freigabe liess sich nicht widerrufen.'; }
    }

    /* ---- Löschen (A8.8) -------------------------------------------------
     *
     * Ein Backup OHNE KONTO ist immer das letzte seiner Art: Es gibt kein
     * Konto mehr, das es neu erzeugen könnte. Deshalb ist die Bestätigung
     * hier immer die harte — die abgetippte Adresse aus der Begleitdatei.
     * Ist die Begleitdatei unlesbar, gibt es keine Adresse zum Abtippen; an
     * ihre Stelle tritt eine ausdrückliche Bestätigung (Kriterium 64).
     */
    if ($action === 'paket_loeschen' || $action === 'ordner_loeschen') {
        $sollAdresse = (string)($_POST['soll_email'] ?? '');
        $eingabe = (string)($_POST['confirm_email'] ?? '');
        $unlesbar = ($_POST['unlesbar'] ?? '') === '1';

        $bestaetigt = $unlesbar
            ? (($_POST['confirm_unlesbar'] ?? '') === 'ja')
            : edbak_bestaetigung_passt($eingabe, $sollAdresse);

        if (!$bestaetigt) {
            $error = $unlesbar
                ? 'Ohne die ausdrückliche Bestätigung wurde nichts gelöscht.'
                : 'Die eingegebene E-Mail-Adresse stimmt nicht überein — es wurde '
                . 'nichts gelöscht.';
        } elseif ($action === 'paket_loeschen') {
            $notice = edbak_paket_loeschen($kennung, $datei)
                ? 'Paket gelöscht.' : null;
            if ($notice === null) { $error = 'Das Paket liess sich nicht löschen.'; }
        } else {
            $notice = edbak_ordner_loeschen($kennung)
                ? 'Alle Pakete dieses Ordners wurden gelöscht.' : null;
            if ($notice === null) {
                $error = 'Der Ordner liess sich nicht vollständig löschen. Enthält er '
                       . 'Dateien, die nicht von dieser Anwendung stammen, bleibt er '
                       . 'bewusst stehen.';
            }
        }
    }
}

[$ablageBereit, $ablageGrund] = edbak_ablage_bereit();
$zahlen    = edbak_stand_zaehlen();
$ablage    = edbak_ablage_zahlen();
$speicher  = edbak_speicherstand();
/* DER DAUERHAFTE HINWEIS OHNE SMTP (E-S2-15). Ist eine Warnschwelle
 * ueberschritten und laesst sich keine Mail verschicken, steht die Warnung
 * hier — und zwar so lange, bis sie nicht mehr zutrifft. Eine Warnung, die
 * nur einmal aufblitzt, waere bei genau der Zielgruppe wirkungslos, die diese
 * Seite alle paar Wochen aufmacht. */
$offeneSchwellen = array_values(array_filter(array_map('intval',
    explode(',', (string)(edbak_marke_lesen('adminbackup_schwellen_offen') ?? '')))));
$offeneSchwellen = array_values(array_filter($offeneSchwellen,
    static fn($p) => $speicher['prozent'] >= $p));
/* Ein laufender Auftrag „Alle sichern" wird ANGEZEIGT, nicht nur abgearbeitet
 * (E-S2-14: „in Schüben mit Fortschrittsanzeige"). Ohne diese Zeile wäre der
 * Unterschied zwischen „läuft noch" und „ist liegengeblieben" nicht zu
 * sehen. */
$auftrag = edbak_auftrag_lesen();
$verwaist  = edbak_verwaiste();
$letzte    = edbak_marke_lesen('adminbackup_last');
$paketeOhneKonto = array_sum(array_map(static fn($v) => count($v['pakete']), $verwaist));
/* Die Vorschau im Kartenkopf nennt Ordner, Pakete UND Groesse (Mockup 08):
 * Ob sich das Aufklappen lohnt, entscheidet nicht die Zahl allein — zwei
 * Pakete zu 6 MB sind etwas anderes als zwei zu 600 MB. Die Groessen liegen
 * schon vor; `edbak_pakete()` liest sie beim Verzeichnislauf mit. */
$bytesOhneKonto = 0;
foreach ($verwaist as $v) {
    foreach ($v['pakete'] as $pk) { $bytesOhneKonto += (int)$pk['groesse']; }
}

/* Zielkonten für das Einspielen eines Backups ohne Konto. Eine Abfrage,
 * kein Dateizugriff — und nur nötig, wenn es überhaupt verwaiste Ordner gibt. */
$konten = $verwaist
    ? db()->query('SELECT id, email FROM users ORDER BY email')->fetchAll()
    : [];

ui_seite_start(['titel' => 'Konto-Backups']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin_sicherungen']); ?>

  <form method="post" id="f-alle" hidden
        data-confirm="Für alle Konten ein Konto-Backup erzeugen? Das dauert — je Konto wird der ganze Bestand gelesen und eine Datei geschrieben. Was in einem Durchgang nicht fertig wird, bleibt offen; ein zweiter Klick macht dort weiter."
        data-confirm-ok="Alle sichern" data-confirm-tone="normal">
    <?= csrf_field() ?><input type="hidden" name="action" value="sichern_alle">
  </form>

  <?php /* DER UNTERTITEL IST DIE ABGRENZUNG (B-S8-08). Er sagt in einem Satz,
           welches der drei Backups gemeint ist — das war bisher nirgends zu
           lesen, und der Name allein sagt es nicht. */ ?>
  <?php ui_titelzeile([
      'titel' => 'Konto-Backups',
      'unter' => 'Pakete, die die Verwaltung je Konto anlegt — nicht die Backups, '
               . 'die NutzerInnen selbst herunterladen. Die Pakete eines einzelnen '
               . 'Kontos stehen auf dessen Seite unter '
               . '<a href="admin_users.php">NutzerInnen</a>.',
      'aktionen' => ui_knopf(['text' => 'Alle sichern', 'symbol' => 'sicherung',
                              'art' => 'primaer', 'attr' => ' form="f-alle"']),
  ]); ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>
  <?php if ($auftrag): ?>
    <?= ui_meldung_markup('info', ($auftrag['gut'] ?? 0) . ' von '
        . ($auftrag['ges'] ?? 0) . ' Konten gesichert, '
        . edbak_auftrag_offen($auftrag) . ' offen'
        . ((int)($auftrag['feh'] ?? 0) > 0
            ? ', ' . (int)$auftrag['feh'] . ' gescheitert' : '')
        . '. Der Aufräumjob arbeitet den Rest in Schüben ab; „Alle sichern" '
        . 'macht sofort dort weiter.'
        . (($auftrag['seit'] ?? null)
            ? ' Begonnen ' . fmt_local(str_replace(['T', 'Z'], [' ', ''],
                (string)$auftrag['seit']), 'd.m.Y · H:i') . ' Uhr.'
            : ''), 'Auftrag läuft.') ?>
  <?php endif; ?>
  <?php if ($speicher['voll']): ?>
    <?= ui_meldung_markup('fehler', 'Die Speichergrenze ist erreicht ('
        . edbak_groesse_text($speicher['bytes']) . ' von '
        . edbak_groesse_text($speicher['grenze']) . '). Es wird nicht mehr '
        . 'gesichert. Es wurde nichts gelöscht und nichts überschrieben — bitte '
        . 'alte Pakete entfernen, die Aufbewahrung senken oder die Grenze '
        . 'erhöhen (Betrieb → Servereinstellungen).') ?>
  <?php elseif ($offeneSchwellen): ?>
    <?= ui_meldung_markup('warn', 'Die Ablage hat '
        . max($offeneSchwellen) . ' % der Speichergrenze erreicht ('
        . edbak_groesse_text($speicher['bytes']) . ' von '
        . edbak_groesse_text($speicher['grenze']) . '). '
        . (smtp_eingerichtet()
            ? 'Die Warnmail liess sich nicht verschicken.'
            : 'Es ist kein SMTP eingerichtet, deshalb steht die Warnung hier '
            . 'und geht nicht per E-Mail heraus.')) ?>
  <?php endif; ?>

  <?php if (!$ablageBereit): ?>
    <?= ui_meldung_markup('fehler', (string)$ablageGrund) ?>
  <?php endif; ?>

  <?php /* Rückmeldung nach der Rückspielung (E22). Die übersprungenen Einträge
           stehen NACH GRÜNDEN GETRENNT da: Wer eine Wiederherstellung beurteilen
           muss, braucht den Unterschied zwischen „war schon da" (gut) und
           „Aufbau unbrauchbar" (schlecht). Eine einzige Zahl beantwortet die
           Frage nicht, die man in diesem Moment hat. */ ?>
  <?php if ($bericht !== null): ?>
    <?= ui_meldung_markup('ok', 'Eingespielt: '
        . (int)($bericht['days'] ?? 0) . ' Diensttage, '
        . (int)($bericht['missions'] ?? 0) . ' Einsätze, '
        . (int)($bericht['rest_segments'] ?? 0) . ' Ruhezeiten. '
        . 'Ergänzt, nicht ersetzt — Vorhandenes bleibt stehen.') ?>
    <p class="codeblock"><?= e(json_encode($bericht, JSON_UNESCAPED_UNICODE)) ?></p>
  <?php endif; ?>

  <?php /* ---- Vier Zahlen, zwei davon ein Weg (Mockup 08) -----------------
       * „Konto-Backup überfällig" und „nie Konto-Backup" heissen wortgleich
       * wie die Filter, auf die sie zeigen (B-S8-07, B-S8-19). Vorher stand
       * hier „überfällig · Liste öffnen" und dort „Backup überfällig" — zwei
       * Namen fuer denselben Filter, und wer den einen sucht, findet den
       * anderen nicht. */ ?>
  <div class="kennzahl-raster kennzahl-raster-4">
    <?= ui_kennzahl(['wert' => number_format($ablage['pakete'], 0, ',', '.'),
                     'label' => ($ablage['pakete'] === 1 ? 'Paket · ' : 'Pakete · ')
                              . edbak_groesse_text($ablage['bytes'])]) ?>
    <?= ui_kennzahl(['wert' => number_format($zahlen['konten'], 0, ',', '.'),
                     'label' => 'Konten', 'href' => 'admin_users.php']) ?>
    <?= ui_kennzahl(['wert' => (string)$zahlen['ueberfaellig'],
                     'label' => 'Konto-Backup überfällig',
                     'ton' => $zahlen['ueberfaellig'] > 0 ? 'orange' : '',
                     'href' => 'admin_users.php?f=ueberfaellig']) ?>
    <?= ui_kennzahl(['wert' => (string)$zahlen['nie'],
                     'label' => 'nie Konto-Backup',
                     'ton' => $zahlen['nie'] > 0 ? 'rot' : '',
                     'href' => 'admin_users.php?f=nie']) ?>
  </div>

  <div class="form-raster">
  <div class="form-spalte">

    <?php /* ---- Regeln ---------------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Regeln']); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="regeln">
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'tage', 'label' => 'Erinnerung nach', 'art' => 'number',
                         'wert' => (string)edbak_intervall(),
                         'attr' => 'min="1" max="3650"',
                         'klein' => 'Tagen. Konten, deren letztes Konto-Backup älter '
                                  . 'ist, gelten als überfällig.']); ?>
          <?php ui_feld(['name' => 'pakete', 'label' => 'Aufbewahrung je Konto',
                         'art' => 'number', 'wert' => (string)edbak_aufbewahrung(),
                         'attr' => 'min="1" max="100"',
                         'klein' => 'Pakete. Ältere werden beim nächsten Sichern '
                                  . 'gelöscht — das jüngste und ein freigegebenes nie.']); ?>
        </div>
        <?php ui_schalter(['name' => 'mail', 'label' => 'Erinnerung an Admins per E-Mail',
                           'an' => edbak_admin_mail_an(),
                           'klein' => 'Liste der überfälligen Konten, höchstens einmal '
                                    . 'je Woche und nur, wenn es etwas zu melden gibt.']); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        </div>
      </form>
      <p class="feld-klein"><strong>Speichergrenze, Warnschwellen, Belegung und
         Ablage</strong> stehen unter
         <a href="betrieb_server.php">Betrieb → Servereinstellungen</a>: Sie
         gelten für Konto-Backups <em>und</em> Komplett-Backups zusammen und
         sind damit eine Einstellung der Installation, keine der Konten.</p>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">

    <?php /* ---- Backups ohne Konto (Mockup 08) --------------------------
         *
         * Zugeklappt: Im Regelfall ist die Karte leer, und eine Liste, die
         * meistens nichts enthält, soll nicht die halbe Seite einnehmen. Die
         * Vorschau im Kopf sagt, ob es sich lohnt.
         *
         * EINE ZEILE JE ORDNER, nicht je Paket. Vorher stand hier je Paket
         * eine Zeile mit eigenem ⋯-Menü — bei einem Ordner mit zehn Paketen
         * zehn Zeilen für eine Sache, die man einmal im Jahr anfasst. Jetzt
         * trägt der Ordner die beiden Wege (Einspielen, Freigeben) als leise
         * Knöpfe, und WELCHES Paket es sein soll, wird im Dialog gewählt —
         * dort, wo ohnehin das Zielkonto steht (Regel 3: Ausnahmen eine
         * Ebene tiefer). Löschen und „Ganzen Ordner löschen" liegen im
         * ⋯-Menü, weil beides endgültig ist. */ ?>
    <?php ui_karte_start(['titel' => 'Backups ohne Konto', 'id' => 'k-ohne',
                          'vorschau' => $verwaist
                              ? count($verwaist) . (count($verwaist) === 1 ? ' Ordner · ' : ' Ordner · ')
                                . $paketeOhneKonto . ($paketeOhneKonto === 1 ? ' Paket · ' : ' Pakete · ')
                                . edbak_groesse_text($bytesOhneKonto)
                              : 'keine']); ?>
      <p class="feld-hinweis">Pakete, deren Konto gelöscht wurde. Sie bleiben, bis
         jemand sie einspielt oder löscht — typisch nach „Konto gelöscht und neu
         aufgesetzt". Sie überleben die Löschung mit Absicht; genau dafür sind sie
         da. Einspielen geht nur in ein bestehendes Konto, und weicht die
         Kontokennung ab, ist der Weg die <strong>Freigabe</strong>: Die geschützten
         Angaben öffnet nur der Wiederherstellungsschlüssel der Person, und der
         liegt ausschließlich bei ihr.</p>

      <?php if (!$verwaist): ?>
        <p class="feld-hinweis">Zurzeit keine.</p>
      <?php endif; ?>

      <?php foreach ($verwaist as $v):
        $handgriff = edbak_handgriff((string)$v['account_key']);
        /* DIE KONTOKENNUNG IST DER TITEL, die Herkunft der Kleintext
           (Mockup 08). Der Ordner heisst nach der Kennung; wer ihn im
           Dateisystem sucht, sucht danach. Die Adresse steht in der
           Begleitdatei und kann fehlen — ein Titel, der manchmal
           „Herkunft unbekannt" heisst, ist als Titel untauglich. */
        $kennungKurz = substr((string)$v['account_key'], 0, 4) . '…'
                     . substr((string)$v['account_key'], -4);
        $herkunft = $v['lesbar'] && $v['email']
            ? 'Herkunft ' . (string)$v['email']
            : 'Herkunft unbekannt';
        $juengstes = $v['pakete'] ? edbak_zeitpunkt_text($v['pakete'][0]['erzeugt']) : null;
        $plaketten = '';
        if (!$v['lesbar']) { $plaketten .= ui_plakette('Begleitdatei nicht lesbar', ['ton' => 'orange']); }
        if ($v['freigabe']) { $plaketten .= ui_plakette('freigegeben', ['ton' => 'blau']); }
        /* Die Paketwahl des Dialogs — als Datenfeld am Knopf, damit derselbe
           Dialog für jeden Ordner taugt (assets/dialog.js füllt aus
           `data-w-*`). Aufbau: Datei|Beschriftung, Einträge durch \n. */
        $paketliste = [];
        foreach ($v['pakete'] as $pk) {
            /* Zeitpunkt und Groesse, nicht der volle Umfang: In einem
               Auswahlfeld steht der Text auf EINER Zeile, und
               „16 Diensttage · 88 Einsätze · 100 Ruhezeiten · davon 11 im
               Papierkorb" laeuft dort aus dem Feld heraus. Unterscheidbar
               sind die Pakete am Datum. */
            $paketliste[] = (string)$pk['datei'] . '|' . edbak_zeitpunkt_text($pk['erzeugt'])
                          . ' · ' . edbak_groesse_text((int)$pk['groesse']);
        }
        $wDaten = ' data-w-handgriff="' . e($handgriff) . '"'
                . ' data-w-titel="' . e('Kontokennung ' . $kennungKurz) . '"'
                . ' data-w-soll="' . e((string)($v['email'] ?? '')) . '"'
                . ' data-w-unlesbar="' . ($v['lesbar'] ? '' : '1') . '"'
                . ' data-w-pakete="' . e(implode("\n", $paketliste)) . '"';
        ui_zeile([
          'text'  => 'Kontokennung ' . $kennungKurz,
          'klein' => $herkunft . ' · ' . count($v['pakete'])
                   . (count($v['pakete']) === 1 ? ' Paket' : ' Pakete')
                   . ($juengstes !== null ? ' · jüngstes ' . $juengstes : ''),
          'plaketten' => $plaketten,
          'aktionen' =>
              ui_knopf(['text' => 'Einspielen', 'art' => 'leise', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-einspielen"' . $wDaten])
            . ui_knopf(['text' => 'Freigeben', 'art' => 'leise', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-freigeben"' . $wDaten])
            /* AKTIONSMENUE, NICHT ZEILENAKTIONEN (Mockup 08). Beides gibt es:
               `ui_zeilenaktionen()` zeigt die Eintraege ab 720 px als Knoepfe
               und nur darunter als Blatt — hier stuenden dann vier Knoepfe
               nebeneinander, zwei davon endgueltig. `ui_aktionen()` haelt sie
               auf JEDER Breite eine Ebene tiefer, und genau das verlangt
               Regel 3 (Ausnahmen eine Ebene tiefer). */
            . ui_aktionen(['titel' => 'Kontokennung ' . $kennungKurz,
                'id' => 'za-' . substr((string)$v['account_key'], 0, 8),
                'eintraege' => [
                  ['text' => 'Einzelnes Paket löschen', 'gefahr' => true, 'href' => '#',
                   'symbol' => 'korb',
                   'attr' => ' data-dialog="dlg-paket-weg"' . $wDaten],
                  ['text' => 'Ganzen Ordner löschen', 'gefahr' => true, 'href' => '#',
                   'symbol' => 'korb',
                   'attr' => ' data-dialog="dlg-ordner-weg"' . $wDaten],
              ]]),
        ]);
      endforeach; ?>
    <?php ui_karte_ende(true); ?>

    <?php /* ---- Was hier gilt (Regel 5 aus E-S8-01) -----------------------
         * Eine Karte je Seite, zugeklappt, am Ende. Sie beantwortet die
         * Frage, die dieser Seite ihren Namen gegeben hat: Welches der drei
         * Backups ist das hier? */ ?>
    <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                          'vorschau' => 'Drei Backups · Freigabe · Schlüssel']); ?>
      <p class="feld-hinweis"><strong>Drei Backups, drei Namen.</strong> Ein
         <strong>Konto-Backup</strong> legt die Verwaltung an; es liegt auf dem
         Server, adressiert über die Kontokennung. Ein <strong>Backup</strong>
         lädt eine NutzerIn sich im eigenen Bereich selbst herunter — es zählt
         hier nicht. Das <strong>Komplett-Backup</strong> der Installation ist
         ein Drittes und liegt unter
         <a href="admin_komplettsicherung.php">Betrieb → Komplett-Backup</a>.</p>
      <p class="feld-hinweis"><strong>Konto-Backups entstehen nie von selbst.</strong>
         Die geschützten Angaben bleiben mit dem Inhaltsschlüssel des Kontos
         verschlüsselt, und den hat der Server nicht — ein nächtlicher Lauf hätte
         nichts, womit er sie lesen könnte. Angestoßen werden sie hier über „Alle
         sichern", auf der Kontoseite je Konto oder über die Auswahl in der
         NutzerInnen-Liste.</p>
      <p class="feld-hinweis"><strong>Was angestoßen ist, arbeitet der
         Aufräumjob in Schüben ab</strong>; die Warteschlange überlebt einen
         Abbruch. Wie oft er läuft, hängt vom eingerichteten Auslöser ab —
         Kommandozeile, Token-Aufruf oder huckepack an einer Anfrage
         (<a href="betrieb_jobs.php">Betrieb → Hintergrundjobs</a>). Die
         wöchentliche Erinnerung fährt auf demselben Weg mit: Wird die Anwendung
         zwei Wochen nicht angefasst, kommt die Mail zwei Wochen später.</p>
      <p class="feld-hinweis"><strong>Die Freigabe</strong> ist der Weg, wenn die
         Kontokennung nicht passt — etwa nach „Konto gelöscht und neu aufgesetzt".
         Die Verwaltung gibt ein Paket für ein Konto frei; einspielen kann es nur
         die NutzerIn selbst, in ihrem Backup-Bereich, mit ihrem
         <strong>Wiederherstellungsschlüssel</strong>. Der liegt ausschließlich
         bei ihr — auch die Verwaltung hat ihn nicht.</p>
      <p class="feld-hinweis"><strong>Wohin die Pakete von hier aus gehen</strong>,
         steht unter <a href="admin_sicherungsziele.php">Backup-Ziele</a> —
         FTP-, FTPS- oder SFTP-Gegenstellen. Ohne ein solches Ziel liegen die
         Backups auf demselben Server, dessen Ausfall der Grund für ein Backup
         wäre. Die Ablage selbst ist über den Browser nicht erreichbar: eine
         <code>.htaccess</code> sperrt sie, und der Ordnername je Konto ist nicht
         zu erraten.</p>
    <?php ui_karte_ende(true); ?>

  </div><?php /* .form-spalte (rechts) */ ?>
  </div><?php /* .form-raster */ ?>

  <?php if ($verwaist): ?>
  <?php /* ---- Dialoge (assets/dialog.js) ---------------------------------- */ ?>
  <?php
  /* Das Auswahlfeld der Zielkonten steht in beiden Dialogen. Es ist eine
     Abfrage über `users` — bei dreihundert Konten dreihundert Zeilen in einem
     Auswahlfeld, was gerade noch geht. Ein Suchfeld dafür wäre ein eigener
     Baustein; er entsteht, wenn er gebraucht wird. */
  $zielwahl = ['' => '— Konto wählen —'];
  foreach ($konten as $z) { $zielwahl[(string)$z['id']] = (string)$z['email']; }
  ?>
  <?php /* DREI DIALOGE STATT ZWEI (S8/AP3). Einspielen und Freigeben teilten
           sich bis Web 15.1.0 ein Formular mit zwei Absendeknöpfen — beide
           verlangten dieselben Felder, also lag das nahe. Es war trotzdem
           falsch: Der Dialog hieß „Backup ohne Konto einspielen" und trug
           unten einen Knopf „Freigeben", der etwas ganz anderes tut (das
           Paket wandert NICHT ins Zielkonto, sondern wird der Person zum
           Selbst-Einspielen angeboten). Zwei Handlungen, zwei Dialoge, zwei
           Erklärungen. */ ?>

  <dialog class="dialog" id="dlg-einspielen">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="einspielen">
      <input type="hidden" name="handgriff" data-fuell="handgriff">
      <div class="dialog-kopf"><h2>Konto-Backup einspielen</h2></div>
      <div class="dialog-inhalt">
        <p><strong data-fuell="titel"></strong>. Eingespielt wird
           <strong>ergänzend</strong>: Vorhandenes im Zielkonto bleibt stehen.</p>
        <?php
        ui_feld(['name' => 'datei', 'label' => 'Paket', 'art' => 'select',
                 'optionen' => [], 'pflicht' => true,
                 'attr' => ' data-fuell-optionen="pakete"',
                 'klein' => 'Jüngstes zuerst.']);
        ui_feld(['name' => 'ziel_user', 'label' => 'Zielkonto', 'art' => 'select',
                 'optionen' => $zielwahl, 'pflicht' => true]);
        ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Zielkontos',
                 'pflicht' => true, 'attr' => ' autocomplete="off"',
                 'klein' => 'Zur Bestätigung abtippen — geprüft wird das Ziel, '
                          . 'nicht die Herkunft.']);
        ?>
        <p class="feld-hinweis">Stimmt die Kontokennung im Paket mit der des Zielkontos
           überein, lässt sich unmittelbar einspielen. Weicht sie ab und enthält das
           Paket geschützte Angaben, ist das gesperrt — dann ist die
           <strong>Freigabe</strong> der Weg.</p>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Einspielen', 'art' => 'primaer']) ?>
      </div>
    </form>
  </dialog>

  <dialog class="dialog" id="dlg-freigeben">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="freigeben">
      <input type="hidden" name="handgriff" data-fuell="handgriff">
      <div class="dialog-kopf"><h2>Konto-Backup freigeben</h2></div>
      <div class="dialog-inhalt">
        <p><strong data-fuell="titel"></strong>. Das Paket wandert <strong>nicht</strong>
           ins Zielkonto — es wird der Person angeboten. Sie sieht es in ihrem
           Backup-Bereich und spielt es dort mit ihrem
           <strong>Wiederherstellungsschlüssel</strong> selbst ein. Nur sie kann
           die geschützten Angaben öffnen.</p>
        <?php
        ui_feld(['name' => 'datei', 'label' => 'Paket', 'art' => 'select',
                 'optionen' => [], 'pflicht' => true,
                 'attr' => ' data-fuell-optionen="pakete"',
                 'klein' => 'Jüngstes zuerst.']);
        ui_feld(['name' => 'ziel_user', 'label' => 'Freigeben für', 'art' => 'select',
                 'optionen' => $zielwahl, 'pflicht' => true]);
        ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Kontos',
                 'pflicht' => true, 'attr' => ' autocomplete="off"',
                 'klein' => 'Zur Bestätigung abtippen.']);
        ?>
        <p class="feld-hinweis">Je Ordner gilt <strong>eine</strong> Freigabe. Eine
           neue ersetzt die bisherige; widerrufen wird sie auf der Kontoseite der
           Person.</p>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Freigeben', 'art' => 'primaer']) ?>
      </div>
    </form>
  </dialog>

  <dialog class="dialog" id="dlg-paket-weg">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="paket_loeschen">
      <input type="hidden" name="handgriff" data-fuell="handgriff">
      <input type="hidden" name="soll_email" data-fuell="soll">
      <input type="hidden" name="unlesbar" data-fuell="unlesbar">
      <div class="dialog-kopf"><h2>Einzelnes Paket löschen</h2></div>
      <div class="dialog-inhalt">
        <p><strong data-fuell="titel"></strong> — ein Paket endgültig entfernen. Zu
           diesem Ordner gibt es kein Konto mehr, das es neu erzeugen könnte.</p>
        <?php
        ui_feld(['name' => 'datei', 'label' => 'Paket', 'art' => 'select',
                 'optionen' => [], 'pflicht' => true,
                 'attr' => ' data-fuell-optionen="pakete"',
                 'klein' => 'Jüngstes zuerst.']);
        ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse der Herkunft',
                 'attr' => ' autocomplete="off"',
                 'klein' => 'Zur Bestätigung abtippen. Ist die Begleitdatei nicht '
                          . 'lesbar, gibt es keine Adresse — dann genügt der Haken.']);
        ?>
        <label><input type="checkbox" name="confirm_unlesbar" value="ja">
          Ich entferne ein Paket, das sich keinem Konto mehr zuordnen lässt.</label>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Löschen', 'art' => 'gefahr']) ?>
      </div>
    </form>
  </dialog>

  <dialog class="dialog" id="dlg-ordner-weg">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="ordner_loeschen">
      <input type="hidden" name="handgriff" data-fuell="handgriff">
      <input type="hidden" name="soll_email" data-fuell="soll">
      <input type="hidden" name="unlesbar" data-fuell="unlesbar">
      <div class="dialog-kopf"><h2>Ganzen Ordner löschen</h2></div>
      <div class="dialog-inhalt">
        <p><strong data-fuell="titel"></strong> — <strong>alle</strong> Pakete dieses
           Ordners endgültig entfernen. Danach ist von diesem Konto nichts mehr da.</p>
        <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse der Herkunft',
                       'attr' => ' autocomplete="off"',
                       'klein' => 'Zur Bestätigung abtippen. Ist die Begleitdatei nicht '
                                . 'lesbar, gibt es keine Adresse — dann genügt der Haken.']); ?>
        <label><input type="checkbox" name="confirm_unlesbar" value="ja">
          Ich entferne ein Paket, das sich keinem Konto mehr zuordnen lässt.</label>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Ordner löschen', 'art' => 'gefahr']) ?>
      </div>
    </form>
  </dialog>
  <?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/dialog.js']]); ?>
