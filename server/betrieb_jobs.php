<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/jobs_lib.php';
require_once __DIR__ . '/wartung_lib.php';

/**
 * BETRIEB → HINTERGRUNDJOBS (S8/AP2, E-S8-05).
 *
 * ZWEI KARTEN, EINE FRAGE: Läuft die Arbeit, die niemand sieht — und wenn
 * nein, woran hängt es? „Zustand" beantwortet den ersten Teil, „Auslöser" den
 * zweiten. Beide standen bisher auf der Wartungsseite zwischen sieben anderen
 * Blöcken.
 *
 * WARUM DAS ÜBERHAUPT SICHTBAR SEIN MUSS: Die Wartung ist gegenüber der
 * Anfrage still. Ein dauerhaft scheiternder Job ist von einem laufenden sonst
 * nicht zu unterscheiden — bis irgendwann auffällt, dass der Papierkorb seit
 * Monaten voll ist. Backlog Nr. 89 ist genau dieser Fall: Das geplante
 * Komplett-Backup lief von Web 12.2.0 bis 12.9.2 NIE, und die Wartungsseite
 * zeigte es an — nur las es niemand.
 */

$pdo = db();

/* Token fuer den Abruf ueber die Adresse neu erzeugen (S2/AP2).
 *
 * Ein neues Token macht das alte ungueltig. Das ist der Zweck: Wer den
 * bisherigen Zeitplan-Eintrag nicht mehr kennt oder ihn kompromittiert glaubt,
 * dreht hier ab. Die Folge — der alte Eintrag laeuft ins Leere — steht als
 * Rueckfrage daneben, damit sie niemanden ueberrascht. */
$jobsMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'jobs_token_neu') {
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
}

/* DIE JOBS ANHALTEN UND WIEDER FREIGEBEN (Backlog Nr. 118).
 *
 * Bis Web 19.3.0 ging das nur mit `php jobs.php --pause <Sekunden>`. Auf
 * geteiltem Hosting ist eine Kommandozeile die Ausnahme, nicht die Regel —
 * wer keine hat, konnte die Hintergrundarbeit nicht anhalten, wenn etwas
 * schieflief oder ein grosses Backup einzuspielen war. Die Seite zeigte den
 * Zustand an und nannte den Befehl, den sie selbst nicht ausloesen konnte.
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
                    . fmt_local((string)jobs_pause_bis(), 'd.m.Y H:i') . '.'];
            }
        }
    } catch (Throwable $ex) {
        $pauseMeldung = ['fehler', 'Die Pause ließ sich nicht setzen: '
                                . $ex->getMessage()];
    }
}

$jobs      = jobs_zustand();
$jobFehler = count(array_filter($jobs, fn($j) => !empty($j['letzter_fehler'])));
$jobNie    = count(array_filter($jobs, fn($j) => $j['letzter_lauf'] === null));
/* Eine ANGEHALTENE Wartung darf nicht aussehen wie eine arbeitende (S2/AP3).
 * Die Pause laeuft zwar von selbst ab, aber bis dahin geschieht nichts — und
 * wer das nicht sieht, sucht den Fehler woanders. */
$jobPause = jobs_pause_bis();

$tokenAdresse = null;
if ($jobs !== []) {
    try {
        $tokenAdresse = rtrim((string)($CFG['app']['base_url'] ?? ''), '/')
                      . '/jobs.php?token=' . jobs_token();
    } catch (Throwable $ex) { $tokenAdresse = null; }
}

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

               UND DER TEXT TRUG BIS WEB 19.3.0 SICHTBARES MARKUP: Hier stand
               `<code>php jobs.php --pause 0</code>`, aber
               `ui_meldung_markup()` escapt seinen Text — auf der Seite waren
               die spitzen Klammern zu lesen. Jetzt braucht es den Befehl
               nicht mehr, der Knopf daneben tut es. */ ?>
      <form method="post" action="betrieb_jobs.php" id="f-jobpause-aus" hidden>
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_aus">
      </form>
      <?= ui_meldung_markup('warn', 'Die Hintergrundarbeit ist angehalten bis '
          . fmt_local($jobPause, 'd.m.Y H:i') . '. Bis dahin wird nichts '
          . 'verdichtet, ausgedünnt, aufgeräumt, gesichert oder versendet — '
          . 'auch das Komplett-Backup nicht. Die Pause läuft von selbst ab.',
          '', ui_knopf(['text' => 'Pause aufheben', 'art' => 'neutral',
                        'typ' => 'submit', 'attr' => ' form="f-jobpause-aus"'])) ?>
    <?php endif; ?>
    <?php if ($jobs === []): ?>
      <p class="feld-hinweis">Die Tabelle <code>jobs</code> gibt es noch nicht —
         sie kommt mit einer Migration unter <a href="betrieb_updates.php">Updates</a>.</p>
    <?php else: foreach ($jobs as $j): ?>
      <?php
        $lauf = $j['letzter_lauf'] ? fmt_local((string)$j['letzter_lauf'], 'd.m.Y H:i') : 'nie';
        $plaketten = ui_plakette($lauf, ['ton' => $j['letzter_lauf'] ? 'blau' : 'neutral']);
        if ($j['letzter_ausloeser']) {
            $plaketten .= ui_plakette((string)$j['letzter_ausloeser'], ['ton' => 'neutral']);
        }
        if ($j['rueckstand'] !== null && (int)$j['rueckstand'] > 0) {
            $plaketten .= ui_plakette('Rückstand ' . (int)$j['rueckstand'], ['ton' => 'orange']);
        }
        /* DER FEHLERTEXT STEHT IN DER KLEINZEILE (Mockup 06) und nicht mehr
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
      <?php
        /* WAS LIEGENGEBLIEBEN IST, MIT KENNUNG (S2/AP3, E-S2-06).
         *
         * „3 Spuren mit Lücke" gibt der Betreiberin nichts in die Hand,
         * `mission:412` gibt ihr einen Fall. Die Listen stehen im Jobzustand —
         * sie fallen im Lauf ohnehin an, ihre Anzeige kostet also keine
         * einzige zusätzliche Abfrage. Gekappt bei JOB_LISTE_MAX je Art.
         *
         * Angezeigt wird der Stand des letzten VOLLSTÄNDIGEN Durchlaufs;
         * während eines laufenden zeigte die Liste sonst eine Mischung, in der
         * behobene Fälle stehenbleiben. */
        $zst   = json_decode((string)($j['zustand'] ?? ''), true);
        $stand = is_array($zst) ? ($zst['stand'] ?? []) : [];
        $benennung = [
            'luecke'      => ['Lücke in der Nummernfolge',
                              'Diese GPS-Daten werden NICHT verdichtet — die Position im '
                            . 'Blob ist die Nummer, eine Lücke verschöbe jeden Punkt '
                            . 'dahinter. Meist eine Uhr, die ein Teilstück nie '
                            . 'nachgeliefert hat.'],
            'zu_gross'    => ['Zu viele Punkte',
                              'Über 50 000 Punkte je Aufzeichnung. Solche GPS-Daten sind aus '
                            . 'einem Backup nicht wiederherstellbar; sie bleibt '
                            . 'deshalb als Zeilen stehen.'],
            'stufe3'      => ['Punkte auf ausgedünnten GPS-Daten',
                              'Erwartet werden hier null. Steht eine Zahl da, nimmt '
                            . 'die Uhr-Schnittstelle Punkte an, die sie nach der '
                            . 'Ausdünnung verwerfen sollte.'],
            'nachzuegler' => ['Wartet auf die Verdichtung',
                              'Zu diesen GPS-Daten sind noch Punkte nachgekommen. Sie '
                            . 'werden erst verdichtet und dann ausgedünnt.'],
            'fehler'      => ['Prüfung nicht bestanden',
                              'Die Rundlauf- oder Ausdünnungsprüfung hat angeschlagen. '
                            . 'Es wurde NICHTS gelöscht und NICHTS ersetzt.'],
        ];
        foreach ($benennung as $art => [$titel, $erklaerung]):
            $liste = $stand[$art] ?? [];
            if (!$liste) { continue; }
      ?>
        <?php ui_zeile([
            'text'  => $titel,
            'klein' => $erklaerung . ' — ' . implode(', ', array_slice($liste, 0, 12))
                     . (count($liste) > 12 ? ' …' : ''),
            'plaketten' => ui_plakette((string)count($liste),
                ['ton' => $art === 'nachzuegler' ? 'neutral' : 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php endforeach; endif; ?>
    <?php /* ANHALTEN OHNE KOMMANDOZEILE (Backlog Nr. 118). Hier stand bis
             Web 19.3.0: „Anhalten nur auf der Kommandozeile ... Ein Knopf dafür
             wäre eine neue Funktion und steht im Backlog (Nr. 118)." Auf
             geteiltem Hosting gibt es diese Kommandozeile in der Regel nicht.

             DIE RUECKFRAGE NENNT DEN PREIS VOLLSTAENDIG. Der alte Text zählte
             drei der sieben Jobs auf („verdichtet, ausgedünnt, aufgeräumt")
             und ließ das geplante Komplett-Backup und den Mailversand weg —
             harmlos, solange niemand danach entscheidet. Neben einem Knopf
             ist es die Entscheidungsgrundlage. */ ?>
    <?php if ($jobs !== [] && $jobPause === null): ?>
      <form method="post" action="betrieb_jobs.php">
        <?= csrf_field() ?><input type="hidden" name="action" value="jobs_pause_an">
        <h3 class="listen-form-titel">Anhalten</h3>
        <p class="feld-hinweis">Für den Fall, dass etwas schiefläuft oder ein
           großes Backup einzuspielen ist. Die Pause läuft von selbst ab —
           länger als <?= (int)(JOB_PAUSE_MAX_S / 3600) ?> Stunden am Stück
           geht nicht.</p>
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
                                . 'verdichtet, ausgedünnt, aufgeräumt, gesichert '
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

  <?php /* ---- Auslöser (E-S2-17) ---------------------------------------------
     *
     * Drei Wege zur selben Arbeit, damit die Hosterwahl offen bleibt. Die
     * Reihenfolge hier ist die Empfehlung: Kommandozeile zuerst.
     *
     * Das Token steht offen auf der Seite — sie ist ohnehin nur der
     * BetreiberIn zugänglich, und ein Geheimnis, das man erst aufklappen muss,
     * wird beim Einrichten abgetippt statt kopiert. Genau dafür gibt es seit
     * Web 15.1.0 den Knopf „Kopieren" (E-S8-10, Nr. 78). */ ?>
  <?php ui_karte_start(['titel' => 'Auslöser', 'id' => 'k-ausloeser']); ?>
    <?php if ($jobsMeldung): ?>
      <?= ui_meldung_markup($jobsMeldung[0], $jobsMeldung[1]) ?>
    <?php endif; ?>
    <p class="seiten-erklaerung">Dieselbe Arbeit über drei Wege. <strong>Einer
       genügt</strong> — eingerichtet werden muss keiner, dann läuft sie
       huckepack auf den Anfragen mit.</p>

    <h3 class="listen-form-titel">1. Kommandozeile <span class="feld-klein-inline">empfohlen</span></h3>
    <p class="feld-hinweis">Ein Eintrag im Cron des Webspace. Jede Minute ist
       unbedenklich: Ein Lauf ohne Arbeit kostet zwei Abfragen.</p>
    <?= ui_codeblock_lang('* * * * * php ' . __DIR__ . '/jobs.php') ?>

    <h3 class="listen-form-titel">2. Abruf über die Adresse</h3>
    <p class="feld-hinweis">Wo es keinen Cron auf der Kommandozeile gibt, aber
       einen zeitgesteuerten Abruf („Cronjob per URL"). Die Adresse enthält ein
       <strong>Geheimnis</strong> — sie gehört nicht in eine Mail und nicht in
       ein Ticket.</p>
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

    <h3 class="listen-form-titel">3. Huckepack auf einer Anfrage</h3>
    <p class="feld-hinweis">Der Rückfall, immer eingeschaltet. Er trägt
       höchstens <?= (int)JOB_BUDGET_ANFRAGE ?> Sekunden je Anfrage und wiederholt
       sich frühestens nach <?= (int)(JOB_ANFRAGE_PAUSE_S / 60) ?> Minuten — genug,
       damit eine Installation ohne jede Einrichtung nicht stillsteht, zu wenig
       für einen großen Rückstand. Wer 1. oder 2. eingerichtet hat, merkt ihn
       nicht.</p>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                        'vorschau' => 'Budget · Reihenfolge · Anhalten']); ?>
    <p class="feld-hinweis"><strong>Budget.</strong> Ein Lauf arbeitet, bis
       seine Zeit um ist, und hört dann auf — er bricht nichts ab, sondern
       merkt sich, wo er war. Die Kommandozeile bekommt
       <?= (int)JOB_BUDGET_CLI ?> Sekunden, der Abruf über die Adresse
       <?= (int)JOB_BUDGET_TOKEN ?>, die Anfrage <?= (int)JOB_BUDGET_ANFRAGE ?>.</p>
    <p class="feld-hinweis"><strong>Reihenfolge.</strong> Die Jobs laufen in
       der Reihenfolge dieser Liste, und was ins Restbudget nicht mehr passt,
       kommt beim nächsten Mal. Deshalb steht die eigentliche Arbeit vorn und
       das Sicherheitsnetz („Verwaiste GPS-Daten") hinten.</p>
    <p class="feld-hinweis"><strong>Anhalten</strong> geht seit Web 19.3.0
       über den Knopf in der Karte „Zustand" — und weiterhin auf der
       Kommandozeile: <code>php jobs.php --pause &lt;Sekunden&gt;</code>
       (<code>0</code> hebt auf). Beide Wege schreiben denselben Wert.
       Höchstens <?= (int)(JOB_PAUSE_MAX_S / 3600) ?> Stunden am Stück; die
       Pause läuft von selbst ab — eine vergessene hält die Installation nicht
       dauerhaft an.</p>
    <p class="feld-hinweis"><strong>Ein Rückstand ist kein Fehler.</strong> Er
       zählt auch mit, was einfach noch zu frisch ist: GPS-Daten werden erst zwei
       Wochen nach dem Einsatz verdichtet und sechs Monate danach ausgedünnt.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/kopieren.js']]); ?>
