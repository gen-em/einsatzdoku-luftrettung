<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/adminbackup_lib.php';
require_once __DIR__ . '/smtp.php';        // smtp_eingerichtet()
require_once __DIR__ . '/format_lib.php';  // groesse_text(), zahl_text(), datum_zeit_text()

/**
 * KONTO-BACKUPS — die Pakete, die die VERWALTUNG je Konto anlegt.
 *
 * DER NAME IST DER BEFUND. „Backups" kann dreierlei heissen:
 * die Pakete der Verwaltung, das `.edbak`, das eine NutzerIn sich selbst
 * herunterlaedt, und der Komplett-Stand der Installation. Drei Dinge, ein
 * Wort — und der Untertitel dieser Seite sagt in einem Satz, welches
 * gemeint ist. Die beiden anderen heissen „Backup" (NutzerIn) und
 * „Komplett-Backup" (Installation).
 *
 * KEINE KARTE „ABLAGE": Pfad, Zustand, Belegung, Reste und das juengste
 * Paket stehen unter Betrieb → Servereinstellungen, zusammen mit der Grenze,
 * gegen die sie gemessen werden. Was hier steht, gilt JE KONTO.
 *
 * KEINE TABELLE ALLER KONTEN UND PAKETE. Bei dreihundert Konten laese sie je
 * Konto ein Verzeichnis UND eine Begleitdatei, um eine Zeile zu zeigen, die
 * man nie ansieht. Die Backups eines Kontos liegen auf dessen Kontoseite,
 * und die NutzerInnen-Liste zaehlt, welche Konten faellig sind.
 * Hier steht, was WEDER zu einem Konto noch in eine Liste gehoert:
 *
 *   die REGELN       Erinnerungsintervall, Aufbewahrung je Konto,
 *                    Erinnerung an die Administration per E-Mail
 *   „OHNE KONTO"     Ordner, zu denen es keine Kontozeile mehr gibt — der
 *                    Fall „Konto geloescht und neu aufgesetzt". Sie
 *                    haben keine Kontoseite; ihr Weg ist nur hier.
 *   (Die drei Backups, die Freigabe und die Schluessel erklaert das
 *   Handbuch 11.4, nicht eine Karte hier.)
 *
 * Die Seite liest damit EIN Verzeichnis fuer die Zahlen (edbak_ablage_zahlen)
 * und EIN weiteres fuer die verwaisten Ordner. Eine Kontenschleife gibt es nicht.
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

/* UMLEITEN NACH DEM POST. Was gespeichert,
 * gesichert, eingespielt, freigegeben oder gelöscht hat, endet mit
 * `flash_setzen()` und einer Umleitung auf diese Seite; der Einspielbericht
 * reist über die Sitzung mit. Sonst wiederholt Neuladen die
 * Handlung. Eine Abweisung, die nichts geändert hat, bleibt auf der Seite. */
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* EIN FRISCHER CODE VOR DER FREIGABE, vor dem Token
     * wie ein Rollentor — dieselbe Handlung wie auf der Kontoseite. */
    if ((string)($_POST['action'] ?? '') === 'freigeben') {
        zweitfaktor_frisch_verlangen('freigeben_ablage');
    }
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');
    /* Aus der Oberfläche kommt der Handgriff, nicht die Kennung (Kriterium 49).
     * Lässt er sich nicht auflösen, gibt es den Ordner nicht — dann laufen die
     * Aktionen unten ins Leere und melden das, statt auf einem geratenen Pfad
     * zu arbeiten. */
    $kennung = edbak_kennung_aus_handgriff((string)($_POST['handgriff'] ?? '')) ?? '';
    $datei   = (string)($_POST['datei'] ?? '');

    /* ---- Die Regeln: ein Formular, ein Speichern --------------- */
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
        /* SPEICHERGRENZE UND WARNSCHWELLEN STEHEN NICHT HIER. Sie wirken auf
         * Konto-Backups UND auf die Komplett-Staende und liegen deshalb im
         * Betrieb unter „Servereinstellungen", zusammen mit der Belegung.
         *
         * Was hier steht, gilt JE KONTO: Erinnerung, Aufbewahrung, Admin-Mail.
         * Die Ordnung folgt der Zielgruppe, nicht der Reihenfolge des Einbaus. */
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
        /* Auch die abgewiesene Aufbewahrung leitet um, wenn das Intervall
         * davor schon gespeichert war; ohne jede Änderung bleibt sie stehen. */
        if ($error === null || $teile !== []) {
            flash_setzen($error !== null ? 'error' : 'notice', (string)($error ?? $notice));
            header('Location: admin_sicherungen.php');
            exit;
        }
    }

    /* ---- Alle sichern --------------------------------------------
     *
     * „ALLE" HEISST ALLE, aber nicht in einer Anfrage. Ein Backup dauert
     * gemessen 222 ms bei einem Konto mit 82 Einsaetzen; bei dreihundert
     * Konten liefe die Anfrage in die Zeitgrenze des Webspace und braeche
     * mittendrin ab — mit einem Teil erledigt und ohne Auskunft darueber,
     * welchem.
     *
     * Ein zweiter Klick macht deshalb dort weiter, wo der erste aufgehoert
     * hat; nach genug Klicks ist jedes Konto genau einmal drangewesen, und die
     * Seite sagt jedes Mal, wie viele noch offen sind.
     */
    if ($action === 'sichern_alle') {
        /* EINE WARTESCHLANGE STATT EINER HEURISTIK.
         *
         * Die Konten nach dem Alter ihres letzten Backups zu sortieren und
         * abzuarbeiten, bis die Zeit knapp wird — wer eben gesichert wurde,
         * steht ja hinten —, traegt nur, wenn sich die Konten um mindestens einen ganzen Tag
         * unterscheiden: gerechnet wird in TAGEN. Wer heute alle Konten
         * sichert, hat danach lauter Nullen, und bei Gleichstand ist die
         * Reihenfolge beliebig — der zweite Klick nimmt womoeglich dieselben
         * Konten noch einmal, und die letzten kommen nie dran.
         *
         * Deshalb sagt die Schlange, wer noch offen ist. Sie wird von zwei
         * Seiten geleert: hier, solange diese Anfrage Zeit hat, und vom
         * Wartungsjob `adminbackup` in Schueben. */
        $laufend = edbak_auftrag_lesen();
        if ($laufend === null) { $laufend = edbak_auftrag_starten(); }

        if ((int)($laufend['ges'] ?? 0) === 0) {
            edbak_auftrag_schreiben(null);
            $error = 'Es gibt kein Konto mit Kontokennung. Bitte zuerst unter '
                   . 'Betrieb → Updates die Migration ausführen.';
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
            /* Zahl und Gründe stehen nebeneinander da — die Gründe reisen
             * deshalb als zweite Meldung mit. */
            flash_setzen('notice', $notice, '', $error !== null ? ['fehler' => $error] : []);
            header('Location: admin_sicherungen.php');
            exit;
        }
    }

    /* ---- Einspielen aus einem Konto-Backup OHNE KONTO ---------- */
    if ($action === 'einspielen') {
        $ziel = edbak_ziel_konto((int)($_POST['ziel_user'] ?? 0));
        /* NUR DER KOPF FUER DIE ENTSCHEIDUNG. edbak_weg() braucht
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
                    if ($okE) {
                        $notice = 'Konto-Backup eingespielt in ' . $ziel['email'] . '.';
                        require_once __DIR__ . '/protokoll_lib.php';
                        protokoll('sicherung', 'kontobackup_eingespielt',
                            'Konto-Backup ' . $datei . ' in ' . $ziel['email'] . ' eingespielt '
                            . '(aus dem Ordner ' . $kennung . ')',
                            ['kennung' => $kennung, 'datei' => $datei], (int)$ziel['id']);
                    }
                    else { $error = (string)$grundE; }
                } catch (Throwable $ex) {
                    $error = 'Das Einspielen ist fehlgeschlagen (Kennung '
                           . fehler_kennung($ex, 'adminbackup') . ').';
                }
                /* Auch das Scheitern leitet um: Der Einspielweg kann mittendrin
                 * abbrechen, und was bis dahin angekommen ist, bleibt stehen. */
                flash_setzen($error !== null ? 'error' : 'notice', (string)($error ?? $notice), '',
                             $bericht !== null ? ['bericht' => $bericht] : []);
                header('Location: admin_sicherungen.php');
                exit;
            }
        }
    }

    /* ---- Freigeben und widerrufen --------------------------------- */
    if ($action === 'freigeben') {
        /* Regel, Protokoll und Mail an EINER Stelle. */
        [$okF, $meldungF] = edbak_freigabe_erteilen($kennung, $datei,
            (int)($_POST['ziel_user'] ?? 0), (string)($_POST['confirm_email'] ?? ''));
        if ($okF) {
            flash_setzen('notice', $meldungF);
            header('Location: admin_sicherungen.php');
            exit;
        }
        $error = $meldungF;
    }
    if ($action === 'widerrufen') {
        if (edbak_freigabe_widerrufen($kennung)) {
            flash_setzen('notice', 'Freigabe widerrufen.');
            header('Location: admin_sicherungen.php');
            exit;
        }
        $error = 'Die Freigabe liess sich nicht widerrufen.';
    }

    /* ---- Löschen -------------------------------------------------
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
            else {
                flash_setzen('notice', $notice);
                header('Location: admin_sicherungen.php');
                exit;
            }
        } else {
            $notice = edbak_ordner_loeschen($kennung)
                ? 'Alle Pakete dieses Ordners wurden gelöscht.' : null;
            if ($notice === null) {
                $error = 'Der Ordner liess sich nicht vollständig löschen. Enthält er '
                       . 'Dateien, die nicht von dieser Anwendung stammen, bleibt er '
                       . 'bewusst stehen.';
            }
            /* Auch das Scheitern leitet um: Eigene Baureste sind dann schon
             * geräumt, und ein misslungenes `rmdir()` folgt auf die Pakete. */
            flash_setzen($error !== null ? 'error' : 'notice', (string)($error ?? $notice));
            header('Location: admin_sicherungen.php');
            exit;
        }
    }
}

// Meldung, Gruende und Einspielbericht aus der Umleitung uebernehmen
$flash = flash_holen();
if ($flash !== null) {
    if ($flash['ton'] === 'error') { $error = $flash['text']; }
    else                           { $notice = $flash['text']; }
    if (is_string($flash['daten']['fehler'] ?? null)) { $error = $flash['daten']['fehler']; }
    if (is_array($flash['daten']['bericht'] ?? null))  { $bericht = $flash['daten']['bericht']; }
}

[$ablageBereit, $ablageGrund] = edbak_ablage_bereit();
$zahlen    = edbak_stand_zaehlen();
$ablage    = edbak_ablage_zahlen();
$speicher  = edbak_speicherstand();
/* DER DAUERHAFTE HINWEIS OHNE SMTP. Ist eine Warnschwelle
 * ueberschritten und laesst sich keine Mail verschicken, steht die Warnung
 * hier — und zwar so lange, bis sie nicht mehr zutrifft. Eine Warnung, die
 * nur einmal aufblitzt, waere bei genau der Zielgruppe wirkungslos, die diese
 * Seite alle paar Wochen aufmacht. */
$offeneSchwellen = array_values(array_filter(array_map('intval',
    explode(',', (string)(edbak_marke_lesen('adminbackup_schwellen_offen') ?? '')))));
$offeneSchwellen = array_values(array_filter($offeneSchwellen,
    static fn($p) => $speicher['prozent'] >= $p));
/* Ein laufender Auftrag „Alle sichern" wird ANGEZEIGT, nicht nur abgearbeitet
 * („in Schüben mit Fortschrittsanzeige"). Ohne diese Zeile wäre der
 * Unterschied zwischen „läuft noch" und „ist liegengeblieben" nicht zu
 * sehen. */
$auftrag = edbak_auftrag_lesen();
$verwaist  = edbak_verwaiste();
$letzte    = edbak_marke_lesen('adminbackup_last');
$paketeOhneKonto = array_sum(array_map(static fn($v) => count($v['pakete']), $verwaist));
/* Die Vorschau im Kartenkopf nennt Ordner, Pakete UND Groesse:
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

  <?php /* DER UNTERTITEL IST DIE ABGRENZUNG. Er sagt in einem Satz,
           welches der drei Backups gemeint ist — das war bisher nirgends zu
           lesen, und der Name allein sagt es nicht. */ ?>
  <?php ui_titelzeile([
      'titel' => 'Konto-Backups',
      'unter' => 'Pakete, die die Verwaltung je Konto anlegt — nicht die, die '
               . 'NutzerInnen selbst herunterladen; die eines Kontos stehen auf '
               . 'dessen Seite unter <a href="admin_users.php">NutzerInnen</a>.',
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
        . '. Der Aufräumjob arbeitet den Rest in Schüben zu '
        . (int)SICHERN_BUDGET . ' Sekunden ab; „Alle sichern" '
        . 'macht sofort dort weiter.'
        . (($auftrag['seit'] ?? null)
            /* OHNE str_replace: `edbak_auftrag_starten()` schreibt `seit` mit
               `iso_utc()`, und das `DateTime` in `fmt_local()` liest die
               T/Z-Marke selbst — nachgerechnet ueber 112 574 Zeitpunkte
               ueber Sommer- und Winterzeit, 0 Abweichungen. */
            ? ' Begonnen ' . datum_zeit_text((string)$auftrag['seit']) . ' Uhr.'
            : ''), 'Auftrag läuft.') ?>
  <?php endif; ?>
  <?php if ($speicher['voll']): ?>
    <?= ui_meldung_markup('fehler', 'Die Speichergrenze ist erreicht ('
        . groesse_text($speicher['bytes']) . ' von '
        . groesse_text($speicher['grenze']) . '). Es wird nicht mehr '
        . 'gesichert. Es wurde nichts gelöscht und nichts überschrieben — bitte '
        . 'alte Pakete entfernen, die Aufbewahrung senken oder die Grenze '
        . 'erhöhen (Betrieb → Servereinstellungen).') ?>
  <?php elseif ($offeneSchwellen): ?>
    <?= ui_meldung_markup('warn', 'Die Ablage hat '
        . max($offeneSchwellen) . ' % der Speichergrenze erreicht ('
        . groesse_text($speicher['bytes']) . ' von '
        . groesse_text($speicher['grenze']) . '). '
        . (smtp_eingerichtet()
            ? 'Die Warnmail liess sich nicht verschicken.'
            : 'Es ist kein SMTP eingerichtet, deshalb steht die Warnung hier '
            . 'und geht nicht per E-Mail heraus.')) ?>
  <?php endif; ?>

  <?php if (!$ablageBereit): ?>
    <?= ui_meldung_markup('fehler', (string)$ablageGrund) ?>
  <?php endif; ?>

  <?php /* Rückmeldung nach der Rückspielung. Die übersprungenen Einträge
           stehen NACH GRÜNDEN GETRENNT da: Wer eine Wiederherstellung beurteilen
           muss, braucht den Unterschied zwischen „war schon da" (gut) und
           „Aufbau unbrauchbar" (schlecht). Eine einzige Zahl beantwortet die
           Frage nicht, die man in diesem Moment hat. */ ?>
  <?php if ($bericht !== null):
      /* Verworfene Werte färben die Meldung orange. */
      $verworfen = edbak_verworfen_satz($bericht); ?>
    <?= ui_meldung_markup($verworfen === '' ? 'ok' : 'warn', 'Eingespielt: '
        . (int)($bericht['days'] ?? 0) . ' Diensttage, '
        . (int)($bericht['missions'] ?? 0) . ' Einsätze, '
        . (int)($bericht['rest_segments'] ?? 0) . ' Ruhezeiten. '
        . 'Ergänzt, nicht ersetzt — Vorhandenes bleibt stehen.' . $verworfen) ?>
    <p class="codeblock"><?= e(json_encode($bericht, JSON_UNESCAPED_UNICODE)) ?></p>
  <?php endif; ?>

  <?php /* ---- Vier Zahlen, zwei davon ein Weg -----------------------------
       * „Konto-Backup überfällig" und „nie Konto-Backup" heissen wortgleich
       * wie die Filter, auf die sie zeigen — zwei Namen fuer denselben
       * Filter, und wer den einen sucht, findet den anderen nicht. */ ?>
  <div class="kennzahl-raster kennzahl-raster-4">
    <?php /* `pakete_bytes`, NICHT `bytes`. `bytes` ist der ganze
             Ablagebaum — Komplett-Backups, Begleitdateien, .htaccess, Reste
             abgebrochener Läufe. Die Kachel sagt „Pakete", also muss sie die
             Pakete wiegen. Der Fehler fiele nicht auf, weil beide Zahlen
             plausibel aussehen. */ ?>
    <?= ui_kennzahl(['wert' => zahl_text($ablage['pakete'], 0),
                     'label' => ($ablage['pakete'] === 1 ? 'Paket · ' : 'Pakete · ')
                              . groesse_text($ablage['pakete_bytes'])]) ?>
    <?= ui_kennzahl(['wert' => zahl_text($zahlen['konten'], 0),
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
    <?php ui_karte_start(['titel' => 'Regeln', 'id' => 'k-regeln']); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="regeln">
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'tage', 'label' => 'Erinnerung nach', 'art' => 'number',
                         'wert' => (string)edbak_intervall(),
                         'attr' => 'min="1" max="3650"',
                         'klein' => 'Tagen ohne neues Konto-Backup gilt ein Konto '
                                  . 'als überfällig.']); ?>
          <?php ui_feld(['name' => 'pakete', 'label' => 'Aufbewahrung je Konto',
                         'art' => 'number', 'wert' => (string)edbak_aufbewahrung(),
                         'attr' => 'min="1" max="100"',
                         /* nicht „ein freigegebenes nie" — das waere zu weit:
                            `edbak_verdraengen()` schont nur eine Freigabe,
                            die noch nicht eingelöst ist. */
                         'klein' => 'Pakete; ältere löscht das nächste Sichern, das '
                                  . 'jüngste und ein noch nicht eingelöst '
                                  . 'freigegebenes nie.']); ?>
        </div>
        <?php ui_schalter(['name' => 'mail', 'label' => 'Erinnerung an Admins per E-Mail',
                           'an' => edbak_admin_mail_an(),
                           'klein' => 'Liste der überfälligen Konten, höchstens einmal '
                                    . 'je Woche und nur, wenn es etwas zu melden gibt.']); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        </div>
      </form>
      <p class="feld-hinweis">Speichergrenze und Ablage gelten für beide
         Backup-Arten und stehen deshalb unter
         <a href="betrieb_server.php">Betrieb → Servereinstellungen</a>.
         <a href="hilfe.php#11-4-konto-backups">Handbuch: Konto-Backups</a></p>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">

    <?php /* ---- Backups ohne Konto --------------------------------------
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
                                . groesse_text($bytesOhneKonto)
                              : 'keine']); ?>
      <p class="feld-hinweis">Pakete, deren Konto gelöscht wurde, bleiben mit
         Absicht liegen, bis jemand sie einspielt oder löscht.
         <a href="hilfe.php#backups-ohne-konto-und-die-freigabe">Handbuch: Backups ohne Konto</a></p>

      <?php if (!$verwaist): ?>
        <p class="feld-hinweis">Zurzeit keine.</p>
      <?php endif; ?>

      <?php foreach ($verwaist as $v):
        $handgriff = edbak_handgriff((string)$v['account_key']);
        /* DIE KONTOKENNUNG IST DER TITEL, die Herkunft der Kleintext.
           Der Ordner heisst nach der Kennung; wer ihn im
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
                          . ' · ' . groesse_text((int)$pk['groesse']);
        }
        $wDaten = ' data-w-handgriff="' . e($handgriff) . '"'
                . ' data-w-titel="' . e('Kontokennung ' . $kennungKurz) . '"'
                . ' data-w-soll="' . e((string)($v['email'] ?? '')) . '"'
                . ' data-w-unlesbar="' . ($v['lesbar'] ? '' : '1') . '"'
                . ' data-w-pakete="' . e(implode("\n", $paketliste)) . '"';
        /* DIE ZIELKONTEN DER FREIGABE JE ORDNER: nur das
         * Herkunftskonto oder eines mit derselben Adresse. Gibt es keines,
         * steht kein Knopf da — ein Dialog mit leerer Auswahl waere ein
         * Umweg ins Nein. */
        $zielePaket = array_map(static fn(array $z): string => $z['id'] . '|' . $z['email'],
                                edbak_freigabe_zielkonten((string)$v['account_key']));
        $wZiele = ' data-w-ziele="' . e(implode("\n", $zielePaket)) . '"';
        ui_zeile([
          'text'  => 'Kontokennung ' . $kennungKurz,
          'klein' => $herkunft . ' · ' . count($v['pakete'])
                   . (count($v['pakete']) === 1 ? ' Paket' : ' Pakete')
                   . ($juengstes !== null ? ' · jüngstes ' . $juengstes : ''),
          'plaketten' => $plaketten,
          'aktionen' =>
              ui_knopf(['text' => 'Einspielen', 'art' => 'leise', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-einspielen"' . $wDaten])
            . ($zielePaket
                ? ui_knopf(['text' => 'Freigeben', 'art' => 'leise', 'typ' => 'button',
                            'attr' => ' data-dialog="dlg-freigeben"' . $wDaten . $wZiele])
                : '')
            /* AKTIONSMENUE, NICHT ZEILENAKTIONEN. Beides gibt es:
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
  <?php /* DREI DIALOGE STATT ZWEI. Einspielen und Freigeben verlangen
           dieselben Felder, und ein Formular mit zwei Absendeknöpfen läge
           nahe. Es wäre trotzdem falsch: Ein Dialog „Backup ohne Konto
           einspielen" trüge unten einen Knopf „Freigeben", der etwas ganz
           anderes tut (das
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
                 'optionen' => [], 'pflicht' => true,
                 'attr' => ' data-fuell-optionen="ziele"',
                 'klein' => 'Nur das Konto, von dem das Backup stammt, oder eines mit '
                          . 'derselben E-Mail-Adresse.']);
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
        <?php /* Nicht derselbe Haken wie beim einzelnen Paket — gelöscht wird
                 der ganze Ordner. */ ?>
        <label><input type="checkbox" name="confirm_unlesbar" value="ja">
          Ich entferne Pakete, die sich keinem Konto mehr zuordnen lassen.</label>
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
