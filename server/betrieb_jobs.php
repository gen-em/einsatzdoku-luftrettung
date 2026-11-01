<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/jobs_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text() fuer die Zeitangaben unten

/**
 * BETRIEB → HINTERGRUNDJOBS.
 *
 * ZWEI KARTEN, EINE FRAGE: Läuft die Arbeit, die niemand sieht — und wenn
 * nein, woran hängt es? „Zustand" beantwortet den ersten Teil, „Auslöser" den
 * zweiten. Beide standen bisher auf der Wartungsseite zwischen sieben anderen
 * Blöcken.
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
 * UMLEITEN NACH DEM POST: Ein neues Token
 * endet mit `flash_setzen()` und einer Umleitung in die Karte „Auslöser" —
 * sonst erzeugt Neuladen ein zweites und macht das eben eingetragene
 * ungueltig. Das Token selbst reist nicht mit; die Seite liest es danach
 * frisch. Scheitert das Erzeugen, ist nichts gespeichert, und die Seite
 * bleibt stehen. */
$jobsMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'jobs_token_neu') {
    /* FRISCHER CODE, wie ein Rollentor VOR
     * csrf_check(). Gilt fuer alle drei Tokens dieser Seite. */
    zweitfaktor_frisch_verlangen('jobs_token_neu');
    csrf_check();
    try {
        jobs_token(true);
        $jobsMeldung = ['ok', 'Ein neues Token steht bereit. Der bisherige '
                            . 'Zeitplan-Eintrag funktioniert damit nicht mehr — '
                            . 'bitte die Adresse dort austauschen.'];
    } catch (Throwable $ex) {
        $jobsMeldung = ['fehler', 'Das Token ließ sich nicht erzeugen: '
                                . $ex->getMessage()];
    }
    if ($jobsMeldung[0] === 'ok') {
        flash_setzen($jobsMeldung[0], $jobsMeldung[1], 'k-ausloeser');
        header('Location: betrieb_jobs.php#k-ausloeser');
        exit;
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
        kette_token(true);
        $ketteMeldung = ['ok', 'Ein neues Kettentoken steht bereit. Die Auslieferung '
                             . 'scheitert, bis es in GitHub als JOBS_TOKEN dieser '
                             . 'Umgebung eingetragen ist.'];
    } catch (Throwable $ex) {
        $ketteMeldung = ['fehler', 'Das Token ließ sich nicht erzeugen: '
                                 . $ex->getMessage()];
    }
    if ($ketteMeldung[0] === 'ok') {
        flash_setzen($ketteMeldung[0], $ketteMeldung[1], 'k-kette');
        header('Location: betrieb_jobs.php#k-kette');
        exit;
    }
}

/* Das Health-Token erzeugen oder die Abfrage abschalten — ohne Dateizugang.
 * Derselbe Weg wie bei den beiden Tokens darueber.
 * EIN Block fuer beide Handlungen, weil die Frische-Liste je Handlung genau
 * einen Aufruf zaehlt (Z43). */
$healthMeldung = null;
$healthAktion  = (string)($_POST['action'] ?? '');
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && ($healthAktion === 'health_token_neu' || $healthAktion === 'health_token_aus')) {
    zweitfaktor_frisch_verlangen('health_token');
    csrf_check();
    try {
        if ($healthAktion === 'health_token_neu') {
            $hatteEins = health_token() !== '';
            health_token_neu();
            $healthMeldung = ['ok', $hatteEins
                ? 'Ein neues Health-Token steht bereit. Ein Monitoring mit dem bisherigen '
                  . 'bekommt ab jetzt 403 — bitte dort austauschen.'
                : 'Das Health-Token steht bereit. Trag es im Monitoring als Kopfzeile '
                  . 'X-Health-Token ein.'];
        } else {
            health_token_aus();
            $healthMeldung = ['ok', 'Die Health-Abfrage ist abgeschaltet; jede Anfrage '
                                  . 'bekommt 403.'];
        }
    } catch (Throwable $ex) {
        $healthMeldung = ['fehler', $ex->getMessage()];
    }
    if ($healthMeldung[0] === 'ok') {
        flash_setzen($healthMeldung[0], $healthMeldung[1], 'k-health');
        header('Location: betrieb_jobs.php#k-health');
        exit;
    }
}

/* DIE JOBS ANHALTEN UND WIEDER FREIGEBEN.
 *
 * Mit `php jobs.php --pause <Sekunden>` allein ginge das nicht: Auf
 * geteiltem Hosting ist eine Kommandozeile die Ausnahme, nicht die Regel —
 * wer keine hat, koennte die Hintergrundarbeit nicht anhalten, wenn etwas
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
 * laenger als zwei Stunden laesst die Bibliothek ohnehin nicht zu. */
/* EINE Liste, zwei Verwender: die Pruefung unten und die Tastenreihe im
 * Formular. Zwei Listen liefen beim ersten Zusatzwert auseinander — und der
 * Fehler waere still, weil die Pruefung die Wahl dann einfach abweist. */
$JOB_PAUSE_DAUERN = [900 => '15 Min.', 1800 => '30 Min.',
                     3600 => '1 Std.', 7200 => '2 Std.'];

/* UMLEITEN NACH DEM POST: Anhalten und
 * Aufheben enden mit `flash_setzen()` und einer Umleitung in die Karte
 * „Zustand" — sonst setzt Neuladen die Pause ein zweites Mal, mit neuer
 * Frist. Eine Dauer, die nicht auf der Liste steht, aendert nichts und
 * bleibt auf der Seite; ebenso ein Schreibfehler von `jobs_pause()`. */
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
                $pauseMeldung = ['ok', 'Die Hintergrundarbeit ist angehalten bis '
                    . datum_zeit_text((string)jobs_pause_bis()) . '.'];
            }
        }
    } catch (Throwable $ex) {
        $pauseMeldung = ['fehler', 'Die Pause ließ sich nicht setzen: '
                                . $ex->getMessage()];
    }
    if ($pauseMeldung[0] === 'ok') {
        flash_setzen($pauseMeldung[0], $pauseMeldung[1], 'k-zustand');
        header('Location: betrieb_jobs.php#k-zustand');
        exit;
    }
}

// Meldung aus der Umleitung in die Karte, die gehandelt hat
$flash = flash_holen();
if ($flash !== null) {
    if ($flash['ort'] === 'k-zustand')       { $pauseMeldung ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-ausloeser') { $jobsMeldung  ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-kette')     { $ketteMeldung ??= [$flash['ton'], $flash['text']]; }
    elseif ($flash['ort'] === 'k-health')    { $healthMeldung ??= [$flash['ton'], $flash['text']]; }
}

$jobs      = jobs_zustand();
$jobFehler = count(array_filter($jobs, fn($j) => !empty($j['letzter_fehler'])));
$jobNie    = count(array_filter($jobs, fn($j) => $j['letzter_lauf'] === null));
/* Eine ANGEHALTENE Wartung darf nicht aussehen wie eine arbeitende.
 * Die Pause laeuft zwar von selbst ab, aber bis dahin geschieht nichts — und
 * wer das nicht sieht, sucht den Fehler woanders. */
$jobPause = jobs_pause_bis();

$tokenAdresse = null;
if ($jobs !== []) {
    try {
        $tokenAdresse = app_url('jobs.php?token=' . jobs_token());
    } catch (Throwable $ex) { $tokenAdresse = null; }
}
$ketteToken = null;
if ($jobs !== []) {
    try {
        $ketteToken = kette_token();
    } catch (Throwable $ex) { $ketteToken = null; }
}
/* '' = keine Abfrage eingerichtet; null = nicht lesbar (Datenbank). */
$healthToken = null;
try { $healthToken = health_token(); } catch (Throwable $ex) { $healthToken = null; }

ui_seite_start(['titel' => 'Hintergrundjobs']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_jobs']); ?>

  <?php ui_titelzeile(['titel' => 'Hintergrundjobs']); ?>
  <?= wartung_balken() ?>

  <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand',
      'plakette' => $jobs === []
          ? ui_plakette('Migration ausstehend', ['ton' => 'neutral'])
          : ($jobPause !== null ? ui_plakette('angehalten', ['ton' => 'orange'])
             : ($jobFehler > 0
                ? ui_plakette($jobFehler === 1 ? '1 Fehler' : $jobFehler . ' Fehler',
                              ['ton' => 'rot'])
                : ($jobNie === count($jobs)
                   ? ui_plakette('noch nie gelaufen', ['ton' => 'neutral'])
                   : ui_plakette('läuft', ['ton' => 'blau']))))]); ?>
    <?php if ($pauseMeldung): ?>
      <?= ui_meldung_markup($pauseMeldung[0], $pauseMeldung[1]) ?>
    <?php endif; ?>
    <?php if ($jobPause !== null): ?>
      <?php /* DAS FORMULAR STEHT VERSTECKT DANEBEN, DER KNOPF ZEIGT DARAUF.
               `ui_meldung_markup()` nimmt den Knopf als Zeichenkette und setzt
               ihn in `.meldung-aktion` — ein <form> mitten in einer Meldung
               ginge nicht, und zwei Formulare ineinander erst recht nicht.
               Dasselbe Muster wie bei „Alle sichern" in admin_sicherungen.php.

               UND DER TEXT TRAEGT KEIN MARKUP: `ui_meldung_markup()`
               escapt seinen Text — ein `<code>` waere auf der Seite mit
               spitzen Klammern zu lesen. Einen Befehl braucht es nicht, der
               Knopf daneben tut es. */ ?>
      <form method="post" action="betrieb_jobs.php" id="f-jobpause-aus" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_aus">
      </form>
      <?= ui_meldung_markup('warn', 'Die Hintergrundarbeit ist angehalten bis '
          . datum_zeit_text($jobPause) . '. Bis dahin wird nichts '
          . 'aufgeräumt, gesichert oder versendet — '
          . 'auch das Komplett-Backup nicht. Die Pause läuft von selbst ab.',
          '', ui_knopf(['text' => 'Pause aufheben', 'art' => 'neutral',
                        'typ' => 'submit', 'attr' => ' form="f-jobpause-aus"'])) ?>
    <?php endif; ?>
    <?php if ($jobs === []): ?>
      <p class="feld-hinweis">Die Tabelle <code>jobs</code> gibt es noch nicht —
         sie kommt mit einer Migration unter <a href="betrieb_updates.php">Updates</a>.</p>
    <?php else: foreach ($jobs as $j): ?>
      <?php
        $lauf = $j['letzter_lauf'] ? datum_zeit_text((string)$j['letzter_lauf']) : 'nie';
        $plaketten = ui_plakette($lauf, ['ton' => $j['letzter_lauf'] ? 'blau' : 'neutral']);
        if ($j['letzter_ausloeser']) {
            $plaketten .= ui_plakette((string)$j['letzter_ausloeser'], ['ton' => 'neutral']);
        }
        if ($j['rueckstand'] !== null && (int)$j['rueckstand'] > 0) {
            $plaketten .= ui_plakette('Rückstand ' . (int)$j['rueckstand'], ['ton' => 'orange']);
        }
        /* DER FEHLERTEXT STEHT IN DER KLEINZEILE und nicht
           als eigene Meldung darunter. Der Grund ist der Platz: Bei sieben
           Jobs stand die Zeile „Fehler" siebenmal möglich, und jede Meldung
           kostete eine eigene Fläche. Was der Fehler war, gehört an den Job —
           dorthin, wo man ihn liest. */
        $klein = (string)$j['beschreibung'];
        if (!empty($j['letzter_fehler'])) {
            $plaketten .= ui_plakette('Fehler', ['ton' => 'rot']);
            $klein .= ' · zuletzt: „' . (string)$j['letzter_fehler'] . '"';
        }
        ui_zeile(['text' => (string)$j['titel'], 'klein' => $klein,
                  'plaketten' => $plaketten]);
      ?>
    <?php endforeach; endif; ?>
    <?php /* ANHALTEN OHNE KOMMANDOZEILE: Auf geteiltem Hosting gibt es diese
             Kommandozeile in der Regel nicht.

             DIE RUECKFRAGE NENNT DEN PREIS VOLLSTAENDIG — alle sieben Jobs,
             auch das geplante Komplett-Backup und den Mailversand. Neben
             einem Knopf ist sie die Entscheidungsgrundlage. */ ?>
    <?php if ($jobs !== [] && $jobPause === null): ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_an">
        <h3 class="listen-form-titel">Anhalten</h3>
        <p class="feld-hinweis">Für den Fall, dass etwas schiefläuft oder ein
           großes Backup einzuspielen ist — die Pause läuft nach höchstens
           <?= (int)(JOB_PAUSE_MAX_S / 3600) ?> Stunden von selbst ab.
           <a href="hilfe.php#12-4-hintergrundjobs">Handbuch: Hintergrundjobs</a></p>
        <?php ui_segment(['name' => 'dauer', 'wert' => '1800',
                          'label' => 'Dauer der Pause',
                          'optionen' => array_map('strval',
                              array_combine(array_map('strval',
                                  array_keys($JOB_PAUSE_DAUERN)),
                                  array_values($JOB_PAUSE_DAUERN)))]); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Jobs anhalten', 'art' => 'neutral',
                        'typ' => 'submit',
                        'attr' => ' data-confirm="Während der Pause wird nichts '
                                . 'aufgeräumt, gesichert '
                                . 'und versendet — auch das geplante '
                                . 'Komplett-Backup nicht."'
                                . ' data-confirm-ok="Anhalten"'
                                /* Kein „danger": Anhalten loescht nichts und
                                   laeuft von selbst ab. Der rote Ton ist fuer
                                   das Unumkehrbare da. */
                                . ' data-confirm-tone="normal"']) ?>
        </div>
      </form>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Auslöser ---------------------------------------------
     *
     * Drei Wege zur selben Arbeit, damit die Hosterwahl offen bleibt. Die
     * Reihenfolge hier ist die Empfehlung: Kommandozeile zuerst.
     *
     * Das Token steht offen auf der Seite — sie ist ohnehin nur der
     * BetreiberIn zugänglich, und ein Geheimnis, das man erst aufklappen muss,
     * wird beim Einrichten abgetippt statt kopiert. Dafür gibt es den Knopf
     * „Kopieren". */ ?>
  <?php ui_karte_start(['titel' => 'Auslöser', 'id' => 'k-ausloeser']); ?>
    <?php if ($jobsMeldung): ?>
      <?= ui_meldung_markup($jobsMeldung[0], $jobsMeldung[1]) ?>
    <?php endif; ?>
    <?php /* EIN SATZ JE KARTE. Die Erklaerung steht im Handbuch 12.4, „Die
             drei Auslöser, Budget und Reihenfolge". Die Warnung zur Adresse
             bleibt als Meldung. */ ?>
    <p class="feld-hinweis">Einer der drei Wege genügt; ohne Einrichtung läuft
       die Arbeit huckepack auf den Anfragen mit.
       <a href="hilfe.php#die-drei-ausloeser-budget-und-reihenfolge">Wie die drei Wege arbeiten</a></p>

    <h3 class="listen-form-titel">1. Kommandozeile <span class="feld-klein-inline">empfohlen</span></h3>
    <?= ui_codeblock_lang('* * * * * php ' . __DIR__ . '/jobs.php') ?>

    <h3 class="listen-form-titel">2. Abruf über die Adresse</h3>
    <?= ui_meldung_markup('warn', 'Die Adresse enthält ein Geheimnis — sie gehört '
        . 'nicht in eine Mail und nicht in ein Ticket.') ?>
    <?php if ($tokenAdresse !== null): ?>
      <?= ui_codeblock_lang($tokenAdresse, 'Adresse') ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_token_neu">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Neues Token erzeugen', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' data-confirm="Das bisherige Token wird damit '
                                . 'ungültig. Ein bestehender Zeitplan-Eintrag '
                                . 'läuft danach ins Leere."'
                                . ' data-confirm-ok="Neu erzeugen"']) ?>
        </div>
      </form>
    <?php endif; ?>

    <h3 class="listen-form-titel">3. Huckepack auf einer Anfrage <span class="feld-klein-inline">immer an</span></h3>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Auslieferungskette -------------
     *
     * Das zweite Token. Es steht neben den Auslösern und nicht darin, weil es
     * keiner ist: Mit ihm schaltet die Auslieferung Wartung und Backup. Die
     * Bausteine sind die der Karte darüber — Codeblock mit „Kopieren",
     * Warnmeldung, leiser Knopf mit Rückfrage —, kein neuer (`CLAUDE.md` 5). */ ?>
  <?php ui_karte_start(['titel' => 'Auslieferungskette', 'id' => 'k-kette']); ?>
    <?php if ($ketteMeldung): ?>
      <?= ui_meldung_markup($ketteMeldung[0], $ketteMeldung[1]) ?>
    <?php endif; ?>
    <p class="feld-hinweis">Mit diesem Token schaltet die Auslieferung Wartung und
       Komplett-Backup dieser Anlage. Es gehört als Geheimnis <code>JOBS_TOKEN</code>
       in die GitHub-Umgebung, die hierher ausliefert.
       <a href="hilfe.php#die-drei-ausloeser-budget-und-reihenfolge">Wozu zwei Tokens</a></p>
    <?= ui_meldung_markup('warn', 'Ein Geheimnis wie ein Passwort — nicht in eine '
        . 'Adresse, nicht in eine Mail, nicht in ein Ticket.') ?>
    <?php if ($ketteToken !== null): ?>
      <?= ui_codeblock_lang($ketteToken, 'Kettentoken') ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="kette_token_neu">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Neues Kettentoken erzeugen', 'art' => 'leise',
                        'typ' => 'submit',
                        'attr' => ' data-confirm="Das bisherige Kettentoken wird damit '
                                . 'ungültig. Die nächste Auslieferung scheitert, bis '
                                . 'das neue in GitHub als JOBS_TOKEN steht."'
                                . ' data-confirm-ok="Neu erzeugen"']) ?>
        </div>
      </form>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Monitoring ----------------------
     *
     * Das dritte Token, fuer `api/health.php`. Dieselben Bausteine wie in den
     * zwei Karten darueber — Codeblock mit „Kopieren", Warnmeldung, leise
     * Knoepfe mit Rueckfrage —, kein neuer (`CLAUDE.md` 5). */ ?>
  <?php ui_karte_start(['titel' => 'Monitoring', 'id' => 'k-health']); ?>
    <?php if ($healthMeldung): ?>
      <?= ui_meldung_markup($healthMeldung[0], $healthMeldung[1]) ?>
    <?php endif; ?>
    <p class="feld-hinweis">Mit diesem Token fragt ein eigenes Monitoring den Zustand
       der Anlage ab — <code>api/health.php</code> mit der Kopfzeile
       <code>X-Health-Token</code>. Die Antwort nennt Fassung, Datenbank, Migration,
       Alter der Jobs und Speicher, keine Daten.
       <a href="hilfe.php#12-9-health-endpunkt-fuer-das-eigene-monitoring">Wie das Monitoring fragt</a></p>
    <?php if ($healthToken === null): ?>
      <?= ui_meldung_markup('fehler', 'Das Health-Token ist gerade nicht lesbar.') ?>
    <?php elseif ($healthToken === ''): ?>
      <p class="feld-hinweis">Keine Abfrage eingerichtet — jede Anfrage bekommt 403.</p>
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
      <?= ui_codeblock_lang($healthToken, 'Health-Token') ?>
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
  <?php ui_karte_ende(); ?>
  <?php /* KEINE KARTE „WAS HIER GILT":
           Budget, Reihenfolge, Anhalten auf der Kommandozeile und „Ein
           Rückstand ist kein Fehler" stehen im Handbuch 12.4. */ ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/kopieren.js']]); ?>
