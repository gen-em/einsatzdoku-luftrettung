<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/jobs_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text(), zeit_relativ_kurz()

/**
 * BETRIEB → HINTERGRUNDJOBS.
 *
 * ZWEI KARTEN, EINE FRAGE: Läuft die Arbeit, die niemand sieht — und wenn
 * nein, woran hängt es? „Zustand" beantwortet den ersten Teil, „Auslöser" den
 * zweiten. Beide standen bisher auf der Wartungsseite zwischen sieben anderen
 * Blöcken.
 *
 * SEIT KONZEPT OF (OF-06, E-OF-10, -21, Bild `OF-P24-vorschlag.png`): oben
 * die offene Karte „Jobs vorübergehend pausieren", darunter der Zustand als
 * offene Karte, Auslöser, Auslieferungskette und Monitoring als Klappkarten
 * mit Stand und Plakette im Kopf. Wer nachsieht, ob die Arbeit läuft, liest
 * die ersten zwei Karten; die Tokens braucht nur, wer einrichtet.
 *
 * WARUM DAS ÜBERHAUPT SICHTBAR SEIN MUSS: Die Wartung ist gegenüber der
 * Anfrage still. Ein dauerhaft scheiternder Job ist von einem laufenden sonst
 * nicht zu unterscheiden — bis irgendwann auffällt, dass der Papierkorb seit
 * Monaten voll ist oder ein geplantes Komplett-Backup nie lief.
 */

$pdo = db();

/* Token fuer den Abruf ueber die Adresse neu erzeugen.
 *
 * Ein neues Token macht das alte ungueltig. Das ist der Zweck: Wer den
 * bisherigen Zeitplan-Eintrag nicht mehr kennt oder ihn kompromittiert glaubt,
 * dreht hier ab. Die Folge — der alte Eintrag laeuft ins Leere — steht als
 * Rueckfrage daneben, damit sie niemanden ueberrascht.
 *
 * EINMAL GEZEIGT, KEINE UMLEITUNG (seit Web 24.12.0, KA-03, F-KA-19,
 * E-KA-10). Die drei Tokens liegen nur noch gehasht; der Klartext steht
 * genau einmal in der Antwort, die ihn erzeugt hat (`$neuesToken`). Bis
 * dahin leitete die Seite nach dem POST um und las das Token danach frisch —
 * im Klartext, bei jedem Aufruf. Eine Umleitung ginge jetzt nur ueber die
 * Sitzungsdatei, und ein Geheimnis gehoert nicht hinein. Der Preis: Neuladen
 * fragt der Browser nach, und wer bestaetigt, erzeugt ein weiteres Token. */
$jobsMeldung = null;
/** Ein eben erzeugtes Token: ['karte' => …, 'wert' => …, 'titel' => …]. */
$neuesToken = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'jobs_token_neu') {
    /* FRISCHER CODE, wie ein Rollentor VOR
     * csrf_check(). Gilt fuer alle drei Tokens dieser Seite. */
    zweitfaktor_frisch_verlangen('jobs_token_neu');
    csrf_check();
    try {
        $neuesToken = ['karte' => 'k-ausloeser', 'titel' => 'Adresse',
                       'wert' => app_url('jobs.php?token=' . jobs_token_neu())];
        $jobsMeldung = ['ok', 'Ein neues Token steht bereit — die Adresse steht unten '
                            . 'genau dieses eine Mal. Der bisherige Zeitplan-Eintrag '
                            . 'funktioniert damit nicht mehr; bitte die Adresse dort '
                            . 'austauschen.'];
    } catch (Throwable $ex) {
        $jobsMeldung = ['fehler', 'Das Token ließ sich nicht erzeugen: '
                                . $ex->getMessage()];
    }
}

/* Das Kettentoken neu erzeugen — derselbe
 * Weg wie beim Job-Token darueber: Umleitung nach dem POST, das Token reist
 * nicht mit. Die Folge ist eine andere: Nicht ein Zeitplan-Eintrag laeuft ins
 * Leere, sondern die naechste Auslieferung, bis das neue Token in GitHub als
 * `JOBS_TOKEN` steht. */
$ketteMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kette_token_neu') {
    zweitfaktor_frisch_verlangen('kette_token_neu');
    csrf_check();
    try {
        $neuesToken = ['karte' => 'k-kette', 'titel' => 'Kettentoken',
                       'wert' => kette_token_neu()];
        $ketteMeldung = ['ok', 'Ein neues Kettentoken steht bereit — es steht unten genau '
                             . 'dieses eine Mal. Die Auslieferung scheitert, bis es in '
                             . 'GitHub als JOBS_TOKEN dieser Umgebung eingetragen ist.'];
    } catch (Throwable $ex) {
        $ketteMeldung = ['fehler', 'Das Token ließ sich nicht erzeugen: '
                                 . $ex->getMessage()];
    }
}

/* Das Health-Token erzeugen oder die Abfrage abschalten — ohne Dateizugang.
 * Derselbe Weg wie bei den beiden Tokens darueber.
 * EIN Block fuer beide Handlungen, weil die Frische-Liste je Handlung genau
 * einen Aufruf zaehlt (Z43). */
$healthMeldung = null;
$healthAktion  = post_text('action');
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($healthAktion === 'health_token_neu' || $healthAktion === 'health_token_aus')) {
    zweitfaktor_frisch_verlangen('health_token');
    csrf_check();
    try {
        if ($healthAktion === 'health_token_neu') {
            $hatteEins = health_token_gespeichert() !== '';
            $neuesToken = ['karte' => 'k-health', 'titel' => 'Health-Token',
                           'wert' => health_token_neu()];
            $healthMeldung = ['ok', ($hatteEins
                ? 'Ein neues Health-Token steht bereit. Ein Monitoring mit dem bisherigen '
                  . 'bekommt ab jetzt 403 — bitte dort austauschen.'
                : 'Das Health-Token steht bereit. Trag es im Monitoring als Kopfzeile '
                  . 'X-Health-Token ein.') . ' Es steht unten genau dieses eine Mal.'];
        } else {
            health_token_aus();
            $healthMeldung = ['ok', 'Die Health-Abfrage ist abgeschaltet; jede Anfrage '
                                  . 'bekommt 403.'];
        }
    } catch (Throwable $ex) {
        $healthMeldung = ['fehler', $ex->getMessage()];
    }
    if ($healthMeldung[0] === 'ok' && $neuesToken === null) {
        flash_setzen($healthMeldung[0], $healthMeldung[1], 'k-health');
        header('Location: betrieb_jobs.php#k-health');
        exit;
    }
}

/* DIE JOBS PAUSIEREN UND WIEDER FREIGEBEN.
 *
 * Mit `php jobs.php --pause <Sekunden>` allein ginge das nicht: Auf
 * geteiltem Hosting ist eine Kommandozeile die Ausnahme, nicht die Regel —
 * wer keine hat, koennte die Hintergrundarbeit nicht pausieren, wenn etwas
 * schieflaeuft oder ein grosses Backup einzuspielen ist.
 *
 * SERVERSEITIG DERSELBE WEG: `jobs_pause()` aus `jobs_lib.php`, derselbe
 * Schluessel `jobs_pause_bis` in `app_state`, dieselbe Deckelung auf
 * JOB_PAUSE_MAX_S. Kein zweiter Weg, den niemand pflegt — hier kommt nur
 * ein zweiter Ausloeser fuer dieselbe Funktion dazu.
 *
 * DER ZWEIG STEHT VOR `jobs_pause_bis()` WEITER UNTEN. Stuende er danach,
 * zeigte die Seite nach dem Klick den Zustand von vor dem Klick — dieselbe
 * Falle, die `wartung_aktiv()` mit `clearstatcache()` umgeht.
 *
 * DIE DAUERN SIND EINE GESCHLOSSENE LISTE, keine freie Zahl: Ein Textfeld
 * muesste die Einheit erklaeren (der Code rechnet in SEKUNDEN, die
 * Oberflaeche sagte bis heute faelschlich „Minuten"), und ein Vertipper
 * saehe aus wie ein Fehler der Anwendung. Vier Werte decken den Zweck ab;
 * laenger als zwei Stunden laesst die Bibliothek ohnehin nicht zu.
 * Kommandozeile und Kette nehmen weiter jede Zahl bis JOB_PAUSE_MAX_S: Ihre
 * Aufrufer sind Pruefmittel, die die Dauer ihres Laufs kennen. */
/* EINE Liste, zwei Verwender: die Pruefung unten und die Segmentwahl im
 * Formular. Zwei Listen liefen beim ersten Zusatzwert auseinander — und der
 * Fehler waere still, weil die Pruefung die Wahl dann einfach abweist. */
$JOB_PAUSE_DAUERN = [900 => '15 Min.', 1800 => '30 Min.',
                     3600 => '1 Std.', 7200 => '2 Std.'];

/* UMLEITEN NACH DEM POST: Pausieren und Aufheben enden mit `flash_setzen()`
 * und einer Umleitung — sonst setzt Neuladen die Pause ein zweites Mal, mit
 * neuer Frist. Eine Dauer, die nicht auf der Liste steht, aendert nichts und
 * bleibt auf der Seite; ebenso ein Schreibfehler von `jobs_pause()`.
 *
 * OHNE ANKER: Die Karte „Jobs vorübergehend pausieren" steht als erste unter
 * dem Titel, und dort steht auch die Meldung. Sie traegt keine `id` — mit
 * einer stuende sie als fuenfter Unterpunkt in der Leiste (`menue.js` liest
 * `.karte[id]`), und das freigegebene Bild (OF-P24) zeigt vier. Der Ort der
 * Meldung heisst trotzdem `k-pause`, damit `flash_holen()` sie zuordnet. */
$pauseMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['action'] ?? '', ['jobs_pause_an', 'jobs_pause_aus'], true)) {
    csrf_check();
    try {
        if (($_POST['action'] ?? '') === 'jobs_pause_aus') {
            jobs_pause(0);
            $pauseMeldung = ['ok', 'Die Pause ist aufgehoben — die Hintergrundarbeit '
                                . 'läuft wieder.'];
        } else {
            $sek = (int)($_POST['dauer'] ?? 0);
            if (!isset($JOB_PAUSE_DAUERN[$sek])) {
                $pauseMeldung = ['fehler', 'Bitte eine der angebotenen Dauern wählen.'];
            } else {
                jobs_pause($sek);
                $pauseMeldung = ['ok', 'Die Hintergrundarbeit ist pausiert bis '
                    . datum_zeit_text((string)jobs_pause_bis()) . '.'];
            }
        }
    } catch (Throwable $ex) {
        $pauseMeldung = ['fehler', 'Die Pause ließ sich nicht setzen: '
                                . $ex->getMessage()];
    }
    if ($pauseMeldung[0] === 'ok') {
        flash_setzen($pauseMeldung[0], $pauseMeldung[1], 'k-pause');
        header('Location: betrieb_jobs.php');
        exit;
    }
}

// Meldung aus der Umleitung in die Karte, die gehandelt hat
$flash = flash_holen();
if ($flash !== null) {
    if ($flash['ort'] === 'k-pause')         { $pauseMeldung ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-ausloeser') { $jobsMeldung  ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-kette')     { $ketteMeldung ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-health')    { $healthMeldung ??= [$flash['ton'], $flash['text']]; }
}

$jobs      = jobs_zustand();
$jobFehler = count(array_filter($jobs, fn($j) => !empty($j['letzter_fehler'])));
$jobNie    = count(array_filter($jobs, fn($j) => $j['letzter_lauf'] === null));
/* Eine PAUSIERTE Wartung darf nicht aussehen wie eine arbeitende.
 * Die Pause laeuft zwar von selbst ab, aber bis dahin geschieht nichts — und
 * wer das nicht sieht, sucht den Fehler woanders. */
$jobPause = jobs_pause_bis();

/* DER AUSLOESER IN WORTEN, nicht als Rohwert. In der Tabelle `jobs` steht
 * `anfrage`, `cli` oder `token` — Bezeichner, die niemand ausser dem Code
 * liest. Ein unbekannter Wert erscheint roh, statt zu verschwinden. */
$AUSLOESER_TEXT = ['anfrage' => 'Anfrage', 'cli' => 'Kommandozeile', 'token' => 'Adresse'];

/* DER ZULETZT BENUTZTE AUSLOESER fuer Stand und Plakette der Klappkarte —
 * dieselbe Rechnung und dieselben Schwellen wie die Zeile „Auslöser" der
 * Statusseite (`status_lib.php`): ueber 24 Stunden still ist rot, nur der
 * Huckepack-Weg ist orange. */
$letzterLauf = null; $letzterWeg = null;
foreach ($jobs as $j) {
    if ($j['letzter_lauf'] !== null
        && ($letzterLauf === null || $j['letzter_lauf'] > $letzterLauf)) {
        $letzterLauf = (string)$j['letzter_lauf'];
        $letzterWeg  = (string)($j['letzter_ausloeser'] ?? '');
    }
}
if ($letzterLauf === null) {
    $ausloeserStand    = 'noch kein Lauf';
    $ausloeserPlakette = ui_plakette('nie gelaufen', ['ton' => 'rot']);
} else {
    $ausloeserStand = 'zuletzt ' . ($AUSLOESER_TEXT[$letzterWeg] ?? $letzterWeg)
                    . ', ' . zeit_relativ_kurz($letzterLauf);
    $alterS = time() - (int)iso_utc_lesen($letzterLauf);
    $ausloeserPlakette = $alterS > 86400
        ? ui_plakette('über 24 h her', ['ton' => 'rot'])
        : ($letzterWeg === 'anfrage' ? ui_plakette('huckepack', ['ton' => 'orange'])
                                     : ui_plakette('läuft', ['ton' => 'gruen']));
}

/* OB EIN TOKEN EINGERICHTET IST — nicht welches (KA-03): true/false, null =
 * nicht lesbar (Datenbank). Den Wert kennt nur, wer ihn eben erzeugt hat. */
$jobsDa = null;
try { $jobsDa = jobs_token_eingerichtet(); } catch (Throwable $ex) { $jobsDa = null; }
$ketteDa = null;
try { $ketteDa = kette_token_gespeichert() !== ''; } catch (Throwable $ex) { $ketteDa = null; }
$healthDa = null;
try { $healthDa = health_token_gespeichert() !== ''; } catch (Throwable $ex) { $healthDa = null; }
/** Das eben erzeugte Token dieser Karte, einmal — sonst nichts. */
$einmal = static function (string $karte) use ($neuesToken): string {
    if ($neuesToken === null || $neuesToken['karte'] !== $karte) { return ''; }
    return ui_codeblock_lang((string)$neuesToken['wert'], (string)$neuesToken['titel']);
};

ui_seite_start(['titel' => 'Hintergrundjobs']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_jobs']); ?>

  <?php ui_titelzeile(['titel' => 'Hintergrundjobs']); ?>
  <?= wartung_balken() ?>

  <?php /* ---- Jobs vorübergehend pausieren -------------------------------
     *
     * EINE EIGENE KARTE OBEN (Konzept OF, E-OF-10, -21). Bis Web 24 stand
     * „Anhalten" als letzter Abschnitt der Zustandskarte, unter elf Zeilen —
     * wer die Jobs in Eile stillstellen wollte, suchte den Knopf.
     *
     * DIE RUECKFRAGE NENNT DEN PREIS VOLLSTAENDIG — alle Jobs, auch das
     * geplante Komplett-Backup und den Mailversand. Neben einem Knopf ist
     * sie die Entscheidungsgrundlage.
     *
     * Ohne Tabelle `jobs` (vor der Migration) gibt es nichts zu pausieren;
     * die Karte entfaellt dann, wie bisher das Formular. */ ?>
  <?php if ($jobs !== []): ?>
    <?php ui_karte_start(['titel' => 'Jobs vorübergehend pausieren',
        'plakette' => $jobPause !== null
            ? ui_plakette('pausiert bis ' . zeit_relativ_kurz($jobPause), ['ton' => 'orange'])
            : ui_plakette('laufen', ['ton' => 'gruen'])]); ?>
      <?php if ($pauseMeldung): ?>
        <?= ui_meldung_markup($pauseMeldung[0], $pauseMeldung[1]) ?>
      <?php endif; ?>
      <?php if ($jobPause !== null): ?>
        <p class="feld-hinweis">Bis <?= ui_e(datum_zeit_text($jobPause)) ?> wird nichts
           aufgeräumt, gesichert oder versendet — auch das Komplett-Backup nicht; die
           Pause endet von selbst.</p>
        <form method="post" action="betrieb_jobs.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_aus">
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Pause aufheben', 'art' => 'neutral', 'typ' => 'submit']) ?>
          </div>
        </form>
      <?php else: ?>
        <p class="feld-hinweis">Pausiert alle Jobs, etwa wenn etwas schiefläuft oder ein
           großes Backup einzuspielen ist; die Pause endet von selbst.</p>
        <form method="post" action="betrieb_jobs.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_an">
          <div class="feld">
            <span class="feld-label">Wie lange</span>
            <?php ui_segment(['name' => 'dauer', 'wert' => '1800',
                              'label' => 'Wie lange',
                              'optionen' => array_combine(
                                  array_map('strval', array_keys($JOB_PAUSE_DAUERN)),
                                  array_values($JOB_PAUSE_DAUERN))]); ?>
          </div>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Pausieren', 'art' => 'neutral', 'symbol' => 'uhrzeit',
                          'typ' => 'submit',
                          'attr' => ' data-confirm="Während der Pause wird nichts '
                                  . 'aufgeräumt, gesichert '
                                  . 'und versendet — auch das geplante '
                                  . 'Komplett-Backup nicht."'
                                  . ' data-confirm-ok="Pausieren"'
                                  /* Kein „danger": Pausieren loescht nichts und
                                     laeuft von selbst ab. Der rote Ton ist fuer
                                     das Unumkehrbare da. */
                                  . ' data-confirm-tone="normal"']) ?>
          </div>
        </form>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand',
      'plakette' => $jobs === []
          ? ui_plakette('Migration ausstehend', ['ton' => 'neutral'])
          : ($jobPause !== null ? ui_plakette('pausiert', ['ton' => 'orange'])
             : ($jobFehler > 0
                ? ui_plakette($jobFehler === 1 ? '1 Fehler' : $jobFehler . ' Fehler',
                              ['ton' => 'rot'])
                : ($jobNie === count($jobs)
                   ? ui_plakette('noch nie gelaufen', ['ton' => 'neutral'])
                   : ui_plakette('läuft', ['ton' => 'gruen']))))]); ?>
    <?php if ($jobs === []): ?>
      <p class="feld-hinweis">Die Tabelle <code>jobs</code> gibt es noch nicht —
         sie kommt mit einer Migration unter <a href="betrieb_updates.php">Updates</a>.</p>
    <?php else: foreach ($jobs as $j): ?>
      <?php
        /* ZWEI PLAKETTEN, DIE NICHT UMBRECHEN (E-OF-21): der Zeitpunkt kurz
           („heute 23:24" statt „08.11.2026, 23:24") und der Auslöser in
           Worten. Rückstand und Fehler kommen nur dazu, wenn es sie gibt. */
        $plaketten = ui_plakette(zeit_relativ_kurz($j['letzter_lauf']),
                                 ['ton' => $j['letzter_lauf'] ? 'blau' : 'neutral']);
        $weg = (string)($j['letzter_ausloeser'] ?? '');
        if ($weg !== '') {
            $plaketten .= ui_plakette($AUSLOESER_TEXT[$weg] ?? $weg, ['ton' => 'neutral']);
        }
        if ($j['rueckstand'] !== null && (int)$j['rueckstand'] > 0) {
            $plaketten .= ui_plakette('Rückstand ' . (int)$j['rueckstand'], ['ton' => 'orange']);
        }
        /* DER FEHLERTEXT STEHT IN DER KLEINZEILE, und zwar STATT des Satzes,
           was der Job tut — ein Satz je Zeile. Was der Job tut, sagt sein
           Titel; was schiefging, sagt nur dieser Text. */
        $klein = (string)$j['beschreibung'];
        if (!empty($j['letzter_fehler'])) {
            $plaketten .= ui_plakette('Fehler', ['ton' => 'rot']);
            $klein = 'Zuletzt: „' . (string)$j['letzter_fehler'] . '"';
        }
        ui_zeile(['text' => (string)$j['titel'], 'klein' => $klein,
                  'plaketten' => $plaketten, 'klasse' => 'zeile-umbruch']);
      ?>
    <?php endforeach; endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Auslöser ---------------------------------------------
     *
     * Drei Wege zur selben Arbeit, damit die Hosterwahl offen bleibt. Die
     * Reihenfolge hier ist die Empfehlung: Kommandozeile zuerst.
     *
     * ZUGEKLAPPT (E-OF-21, E-OF-42): Stand und Plakette sagen im Kopf, ob
     * ein Weg eingerichtet ist; aufklappen muss nur, wer ihn einrichtet. Seit
     * Web 24.12.0 (KA-03) steht ein Token nur in der Antwort, die es erzeugt
     * hat — mit „Kopieren", damit es nicht abgetippt wird —, sonst steht da,
     * ob eines eingerichtet ist. Mit einer Meldung steht die Karte offen. */ ?>
  <?php ui_karte_start(['titel' => 'Auslöser', 'id' => 'k-ausloeser', 'zu' => true,
                        'offen' => $jobsMeldung !== null,
                        'stand' => $ausloeserStand, 'plakette' => $ausloeserPlakette]); ?>
    <?php if ($jobsMeldung): ?>
      <?= ui_meldung_markup($jobsMeldung[0], $jobsMeldung[1]) ?>
    <?php endif; ?>
    <?php /* EIN SATZ JE KARTE. Die Erklaerung steht im Handbuch 12.4, „Die
             drei Auslöser, Budget und Reihenfolge". Die Warnung zur Adresse
             bleibt als Meldung. */ ?>
    <p class="feld-hinweis">Einer der drei Wege genügt; ohne Einrichtung läuft
       die Arbeit huckepack auf den Anfragen mit.</p>

    <h3 class="listen-form-titel">1. Kommandozeile <span class="feld-klein-inline">empfohlen</span></h3>
    <?= ui_codeblock_lang('* * * * * php ' . __DIR__ . '/jobs.php') ?>

    <h3 class="listen-form-titel">2. Abruf über die Adresse</h3>
    <?= ui_meldung_markup('warn', 'Die Adresse enthält ein Geheimnis — sie gehört '
        . 'nicht in eine Mail und nicht in ein Ticket.') ?>
    <?= $einmal('k-ausloeser') ?>
    <?php if ($jobsDa === false): ?>
      <p class="feld-hinweis">Noch kein Token eingerichtet — der Abruf über die Adresse ist aus.</p>
    <?php elseif ($jobsDa === true && $neuesToken === null): ?>
      <p class="feld-hinweis">Ein Token ist eingerichtet. Es wird nur beim Erzeugen
         einmal gezeigt; wer es nicht mehr hat, erzeugt ein neues.</p>
    <?php endif; ?>
    <?php if ($jobsDa !== null): ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_token_neu">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => $jobsDa ? 'Neues Token erzeugen' : 'Token erzeugen', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' data-confirm="Das bisherige Token wird damit '
                                . 'ungültig. Ein bestehender Zeitplan-Eintrag '
                                . 'läuft danach ins Leere."'
                                . ' data-confirm-ok="Neu erzeugen"']) ?>
        </div>
      </form>
    <?php endif; ?>

    <h3 class="listen-form-titel">3. Huckepack auf einer Anfrage <span class="feld-klein-inline">immer an</span></h3>
  <?php ui_karte_ende(true); ?>

  <?php /* ---- Auslieferungskette -------------
     *
     * Das zweite Token. Es steht neben den Auslösern und nicht darin, weil es
     * keiner ist: Mit ihm schaltet die Auslieferung Wartung und Backup. Die
     * Bausteine sind die der Karte darüber — Codeblock mit „Kopieren",
     * Warnmeldung, leiser Knopf mit Rückfrage —, kein neuer (`CLAUDE.md` 5).
     *
     * DIE PLAKETTE SAGT NUR, WAS DIE SEITE WEISS: ob ein Token bereitsteht.
     * Ob es in GitHub eingetragen ist, sieht sie nicht — „eingerichtet"
     * waere eine Behauptung. */ ?>
  <?php ui_karte_start(['titel' => 'Auslieferungskette', 'id' => 'k-kette', 'zu' => true,
                        'offen' => $ketteMeldung !== null,
                        'stand' => $ketteDa === null ? 'Token nicht lesbar'
                                 : ($ketteDa ? 'Geheimnis JOBS_TOKEN' : 'kein Token eingerichtet'),
                        'plakette' => $ketteDa === null
                            ? ui_plakette('nicht lesbar', ['ton' => 'rot'])
                            : ($ketteDa ? ui_plakette('bereit', ['ton' => 'gruen'])
                                        : ui_plakette('aus', ['ton' => 'neutral']))]); ?>
    <?php if ($ketteMeldung): ?>
      <?= ui_meldung_markup($ketteMeldung[0], $ketteMeldung[1]) ?>
    <?php endif; ?>
    <p class="feld-hinweis">Mit diesem Token schaltet die Auslieferung Wartung und
       Komplett-Backup dieser Anlage; es gehört als Geheimnis <code>JOBS_TOKEN</code>
       in die GitHub-Umgebung, die hierher ausliefert.</p>
    <?= ui_meldung_markup('warn', 'Ein Geheimnis wie ein Passwort — nicht in eine '
        . 'Adresse, nicht in eine Mail, nicht in ein Ticket.') ?>
    <?= $einmal('k-kette') ?>
    <?php if ($ketteDa === true && $neuesToken === null): ?>
      <p class="feld-hinweis">Ein Kettentoken ist eingerichtet. Es wird nur beim Erzeugen
         einmal gezeigt; wer es nicht mehr hat, erzeugt ein neues und trägt es in GitHub ein.</p>
    <?php endif; ?>
    <?php if ($ketteDa !== null): ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="kette_token_neu">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => $ketteDa ? 'Neues Kettentoken erzeugen' : 'Kettentoken erzeugen', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' data-confirm="Das bisherige Kettentoken wird damit '
                                . 'ungültig. Die nächste Auslieferung scheitert, bis '
                                . 'das neue in GitHub als JOBS_TOKEN steht."'
                                . ' data-confirm-ok="Neu erzeugen"']) ?>
        </div>
      </form>
    <?php endif; ?>
  <?php ui_karte_ende(true); ?>

  <?php /* ---- Monitoring ----------------------
     *
     * Das dritte Token, fuer `api/health.php`. Dieselben Bausteine wie in den
     * zwei Karten darueber — Codeblock mit „Kopieren", Warnmeldung, leise
     * Knoepfe mit Rueckfrage —, kein neuer (`CLAUDE.md` 5).
     *
     * „Keine Abfrage eingerichtet" stand bis Web 24 als zweiter Absatz in der
     * Karte; es ist jetzt ihr Stand im Kopf — ein Satz je Karte. */ ?>
  <?php ui_karte_start(['titel' => 'Monitoring', 'id' => 'k-health', 'zu' => true,
                        'offen' => $healthMeldung !== null,
                        'stand' => $healthDa === null ? 'Token nicht lesbar'
                                 : (!$healthDa ? 'keine Abfrage eingerichtet'
                                               : 'Kopfzeile X-Health-Token'),
                        'plakette' => $healthDa === null
                            ? ui_plakette('nicht lesbar', ['ton' => 'rot'])
                            : (!$healthDa ? ui_plakette('aus', ['ton' => 'neutral'])
                                          : ui_plakette('an', ['ton' => 'gruen']))]); ?>
    <?php if ($healthMeldung): ?>
      <?= ui_meldung_markup($healthMeldung[0], $healthMeldung[1]) ?>
    <?php endif; ?>
    <p class="feld-hinweis">Mit diesem Token fragt ein eigenes Monitoring
       <code>api/health.php</code> mit der Kopfzeile <code>X-Health-Token</code> ab;
       die Antwort nennt Fassung, Datenbank, Migration, Alter der Jobs und Speicher,
       keine Daten.</p>
    <?= $einmal('k-health') ?>
    <?php if ($healthDa === null): ?>
      <?= ui_meldung_markup('fehler', 'Das Health-Token ist gerade nicht lesbar.') ?>
    <?php elseif (!$healthDa): ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="health_token_neu">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Health-Token erzeugen', 'art' => 'leise', 'typ' => 'submit']) ?>
        </div>
      </form>
    <?php else: ?>
      <?= ui_meldung_markup('warn', 'Ein Geheimnis — nicht in eine Mail, nicht in ein '
          . 'Ticket. In der Adresse statt der Kopfzeile steht es im Zugriffsprotokoll '
          . 'des Hosters.') ?>
      <?php if ($neuesToken === null): ?>
      <p class="feld-hinweis">Ein Health-Token ist eingerichtet. Es wird nur beim Erzeugen
         einmal gezeigt; wer es nicht mehr hat, erzeugt ein neues.</p>
      <?php endif; ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Neues Health-Token erzeugen', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' name="action" value="health_token_neu"'
                                . ' data-confirm="Das bisherige Health-Token wird damit '
                                . 'ungültig. Ein Monitoring mit dem alten bekommt 403."'
                                . ' data-confirm-ok="Neu erzeugen"']) ?>
          <?= ui_knopf(['text' => 'Abfrage abschalten', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' name="action" value="health_token_aus"'
                                . ' data-confirm="Das Health-Token wird gelöscht. Jede '
                                . 'Anfrage an api/health.php bekommt danach 403."'
                                . ' data-confirm-ok="Abschalten"']) ?>
        </div>
      </form>
    <?php endif; ?>
  <?php ui_karte_ende(true); ?>
  <?php /* KEINE KARTE „WAS HIER GILT":
           Budget, Reihenfolge, Pausieren auf der Kommandozeile und „Ein
           Rückstand ist kein Fehler" stehen im Handbuch 12.4. */ ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/kopieren.js']]); ?>
