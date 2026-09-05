<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/migration_lib.php';
require_once __DIR__ . '/jobs_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/speicher_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';
require_once __DIR__ . '/komplett_lib.php';
require_once __DIR__ . '/sicherungsziel_lib.php';
require_once __DIR__ . '/smtp.php';

/**
 * BETRIEB -> STATUS (S8/AP4, E-S8-16, Mockup 03).
 *
 * EINE SEITE, DIE MAN ANSIEHT UND DANN WEISS, OB ETWAS ZU TUN IST.
 *
 * Der Befund dahinter (B-S8-03, B-S8-12): Die Auskunft ueber den Betrieb lag
 * verstreut — der Serverschluessel meldete sich als rote Karte auf der Seite
 * der Backup-Ziele, die Schluesselableitung als rote Karte auf der
 * Wartungsseite, der Speicherstand als Balken unter den Backups, die
 * Job-Fehler als Plakette in einer Liste. Jede fuer sich richtig; zusammen
 * ergaben sie kein Bild. Wer wissen wollte, ob diese Installation in Ordnung
 * ist, musste sechs Seiten aufrufen und auf jeder wissen, worauf zu achten
 * ist.
 *
 * REIN LESEND — mit EINER Ausnahme. Die Seite fasst zusammen und verweist;
 * geaendert wird auf der zustaendigen Seite. Die Ausnahme ist „Serverschluessel
 * erzeugen und eintragen": Ohne ihn laeuft weder Komplett-Backup noch
 * Backup-Ziel, und der Weg dorthin ist ein Knopf — ihn hier NICHT anzubieten
 * hiesse, von der Seite, die das Problem meldet, auf eine andere zu schicken,
 * wo derselbe Knopf steht.
 *
 * DIE AMPEL IST EINE TABELLE, KEINE MEINUNG. Welcher Zustand welchen Ton
 * bekommt, steht im Konzept S8 (Abschnitt „Ampeltabelle") und hier in
 * `status_zeile()` — an EINER Stelle, damit „orange" auf dieser Seite ueberall
 * dasselbe heisst:
 *
 *   blau     es ist in Ordnung
 *   orange   es braucht Aufmerksamkeit, arbeitet aber
 *   rot      es arbeitet NICHT (oder etwas geht dabei verloren)
 *   neutral  nicht eingerichtet, oder eine reine Zahl ohne Wertung
 *
 * KEINE NEUEN TOENE (Design.md 9.4). Die vier gibt es; neu ist nur, dass sie
 * auf dieser Seite eine feste Bedeutung tragen.
 *
 * WARUM NICHTS ZWISCHENGESPEICHERT WIRD. Jede Zeile kostet hoechstens eine
 * Abfrage oder einen Dateizugriff; die teuerste Auskunft — die Groesse von
 * Datenbank und Dateien — kommt aus `app_state` und wurde im Aufraeumjob
 * gemessen (S8/AP2). Ein Zwischenspeicher haette den Nachteil, den eine
 * Statusseite am wenigsten haben darf: Er zeigte einen Zustand, den es nicht
 * mehr gibt.
 */

/* ---- Der Baustein dieser Seite: eine Zeile mit Ampel ---------------------
 *
 * Sie ist `ui_zeile()` mit einer Plakette und einem Link — kein neuer
 * Baustein. Diese Funktion nimmt der Seite nur das Wiederholen ab und macht
 * die Ampel zaehlbar: `$GLOBALS['status_zaehler']` sammelt, wie viele Punkte
 * Aufmerksamkeit brauchen, damit die Meldung oben eine Zahl nennen kann,
 * ohne dass jemand sie von Hand pflegt.
 */
$GLOBALS['status_zaehler'] = ['orange' => 0, 'rot' => 0];

function status_zeile(string $text, string $klein, string $ton, string $plakette,
                      ?string $href = null, string $aktion = ''): void
{
    if ($ton === 'orange' || $ton === 'rot') { $GLOBALS['status_zaehler'][$ton]++; }
    ui_zeile([
        'text'      => $text,
        'klein'     => $klein,
        'href'      => $href,
        'plaketten' => ui_plakette($plakette, ['ton' => $ton]),
        'aktionen'  => $aktion,
    ]);
}

/** „vor 3 Stunden" — für Zeitpunkte, bei denen das Alter die Aussage ist. */
function status_alter(?string $utc): string
{
    if ($utc === null || $utc === '') { return 'nie'; }
    $t = strtotime(str_replace(['T', 'Z'], [' ', ''], $utc) . ' UTC');
    if ($t === false) { return 'unbekannt'; }
    $s = time() - $t;
    if ($s < 90)     { return 'gerade eben'; }
    if ($s < 5400)   { return 'vor ' . (int)round($s / 60) . ' Minuten'; }
    if ($s < 172800) { return 'vor ' . (int)round($s / 3600) . ' Stunden'; }
    return 'vor ' . (int)round($s / 86400) . ' Tagen';
}

/* ---- Alles einsammeln, bevor etwas ausgegeben wird ----------------------
 *
 * Die Reihenfolge der Ausgabe ist die der Karten; die Reihenfolge der
 * Erhebung ist die der Abhaengigkeiten. Beides zu vermischen hiesse, mitten
 * im Markup eine Abfrage zu stellen — und dann faellt beim naechsten Umbau
 * eine Zeile weg und mit ihr eine Messung, die eine andere Zeile braucht.
 */
$pdo = db();

$wartung   = wartung_daten();
$lauf      = migrationen_lauf($pdo, false);
$stand     = migrationen_stand($pdo);
$schluessel = serverschluessel_da();

/* Verwaiste Rundenzahlen: Konten, deren `kdf_iter` diese Fassung nicht mehr
 * anbietet. Sie koennen sich NICHT anmelden, und an der Anmeldemaske ist die
 * Ursache nicht zu erkennen (siehe db.php, KDF_ITER_LISTE). Die Pruefung stand
 * bis Web 15.0.0 auf der Wartungsseite. */
$kdfListe = KDF_ITER_LISTE;
$platz    = implode(',', array_fill(0, count($kdfListe), '?'));
$stk = $pdo->prepare("SELECT kdf_iter, COUNT(*) AS n FROM users
                      WHERE password_hash IS NOT NULL AND kdf_iter NOT IN ($platz)
                      GROUP BY kdf_iter ORDER BY kdf_iter");
$stk->execute($kdfListe);
$kdfVerwaist = $stk->fetchAll();
$kdfSumme    = array_sum(array_column($kdfVerwaist, 'n'));

$sp        = speicher_uebersicht();
$jobs      = jobs_zustand();
$jobPause  = jobs_pause_bis();
$zahlen    = edbak_stand_zaehlen();
[$ablageBereit, $ablageGrund] = edbak_ablage_bereit();
$kompStaende = komp_staende();
$kompPlan    = komp_plan();
$ziele       = sz_tabelle_da() ? sz_alle() : [];
$smtpDa      = smtp_eingerichtet();
$smtpLetzte  = (string)(edbak_marke_lesen('smtp_last') ?? '');
$smtpOk      = edbak_marke_lesen('smtp_last_ok');

ui_seite_start(['titel' => 'Status']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_status']); ?>

  <?php ui_titelzeile([
      'titel' => 'Status',
      'unter' => 'Was diese Installation gerade meldet — rein lesend. '
               . 'Geändert wird auf der Seite, auf die die Zeile führt.',
      'aktionen' => ui_knopf(['text' => 'Aktualisieren', 'symbol' => 'sortieren',
                              'art' => 'neutral', 'href' => 'betrieb_status.php']),
  ]); ?>

  <?= wartung_balken() ?>

  <?php
  /* Die Meldung oben entsteht ZULETZT — sie zählt, was die Karten gefunden
     haben. Ausgegeben wird sie aber ZUERST. Deshalb wird der ganze Rest in
     einen Puffer geschrieben und danach ausgegeben: Ein Vorlauf, der die
     Zeilen zweimal rechnet, wäre die schlechtere Lösung — die Messungen
     kosten Abfragen. */
  ob_start();
  ?>

  <div class="form-raster">
  <div class="form-spalte">

    <?php ui_karte_start(['titel' => 'Server', 'id' => 'k-server']); ?>
      <?php
      $wAktiv = wartung_aktiv();
      status_zeile('Serverbetrieb',
          $wAktiv
              ? 'Wartungsmodus seit '
                . ($wartung['seit'] !== null
                    ? fmt_local(str_replace(['T', 'Z'], [' ', ''], $wartung['seit']), 'd.m.Y · H:i') . ' Uhr'
                    : 'unbekannt')
                . ($wartung['von'] !== null ? ' von ' . $wartung['von'] : '')
                . ' — alle anderen Anfragen bekommen 503'
              : 'Offen für alle Konten',
          $wAktiv ? 'orange' : 'blau',
          $wAktiv ? 'Wartung' : 'offen',
          'betrieb_updates.php');

      $offen = (int)$lauf['offen'];
      status_zeile('Updates',
          $offen > 0
              ? $offen . ($offen === 1 ? ' Migration steht aus' : ' Migrationen stehen aus')
              : 'Alles aktuell · ' . $stand['zahl'] . ' ausgeführt'
                . ($stand['letzte'] !== null ? ' · zuletzt ' . $stand['letzte'] : ''),
          $offen > 0 ? 'orange' : 'blau',
          $offen > 0 ? 'steht aus' : 'aktuell',
          'betrieb_updates.php');

      /* ROT UND MIT KNOPF: die einzige Handlung dieser Seite. Ohne
         Serverschlüssel entsteht kein Komplett-Backup und kein Versand auf
         ein Backup-Ziel — und der Weg dorthin ist ein Knopf, kein Formular. */
      status_zeile('Serverschlüssel',
          $schluessel
              ? 'Vorhanden — Komplett-Backups und Backup-Ziele können versiegeln'
              : 'Fehlt. Ohne ihn gibt es kein Komplett-Backup und keinen Versand '
                . 'auf ein Backup-Ziel',
          $schluessel ? 'blau' : 'rot',
          $schluessel ? 'vorhanden' : 'fehlt',
          $schluessel ? null : 'admin_sicherungsziele.php');

      status_zeile('Schlüsselableitung',
          $kdfVerwaist === []
              ? 'Alle Konten rechnen mit einer Rundenzahl, die diese Fassung anbietet ('
                . implode(', ', array_map('strval', $kdfListe)) . ')'
              : $kdfSumme . ' Konto/Konten tragen eine Rundenzahl, die diese Fassung '
                . 'nicht anbietet — sie können sich nicht anmelden. Behebung: den '
                . 'fehlenden Wert in KDF_ITER_LISTE (server/db.php) wieder aufnehmen',
          $kdfVerwaist === [] ? 'blau' : 'rot',
          $kdfVerwaist === [] ? 'in Ordnung' : 'Anmeldung blockiert');

      /* Dass diese Seite überhaupt antwortet, beweist die Erreichbarkeit —
         die Zeile sagt deshalb die GRÖSSE, und die ist die Auskunft, die man
         hier sucht. „Nicht erreichbar" käme nie zur Anzeige; es gäbe keine
         Seite. Das steht so da, statt eine Zeile zu führen, die immer blau
         ist und nichts misst. */
      status_zeile('Datenbank',
          $sp['stand'] !== null
              ? edbak_groesse_text($sp['gesamt']['datenbank'])
                . ' · Dateien ' . edbak_groesse_text($sp['gesamt']['dateien'])
                . ' · gemessen ' . status_alter($sp['stand'])
              : 'Noch nicht gemessen — die Messung läuft im täglichen Aufräumjob',
          $sp['stand'] !== null ? 'blau' : 'neutral',
          $sp['stand'] !== null ? 'erreichbar' : 'ungemessen',
          'betrieb_server.php');

      status_zeile('PHP und Zeitzone',
          PHP_VERSION . ' · Anzeige in ' . date_default_timezone_get()
          . ' · gespeichert wird UTC',
          'neutral', PHP_SAPI);
      ?>
    <?php ui_karte_ende(); ?>

    <?php ui_karte_start(['titel' => 'E-Mail', 'id' => 'k-mail']); ?>
      <?php
      status_zeile('SMTP',
          $smtpDa
              ? 'Eingerichtet in der config.php'
              : 'Nicht eingerichtet. Ohne SMTP gibt es keine Einladungslinks, keine '
                . 'Setz-Links und keine Erinnerung an überfällige Konto-Backups',
          $smtpDa ? 'blau' : 'neutral',
          $smtpDa ? 'eingerichtet' : 'nicht eingerichtet');

      /* SEIT WEB 15.3.0 WIRD DER VERSAND VERMERKT (Z-01). Vorher konnte
         niemand sagen, ob je eine Mail hinausging: `smtp_eingerichtet()`
         prüft die config.php, nicht den Mailserver. Ein falsches Passwort
         fiel erst auf, wenn jemand einen Setz-Link erwartete. */
      if ($smtpLetzte === '') {
          status_zeile('Letzter Versand',
              $smtpDa
                  ? 'Seit dem Ausrollen dieser Fassung wurde nichts versendet — '
                    . 'oder es ist noch keine Mail angefallen'
                  : 'Ohne SMTP wird nichts versendet',
              'neutral', 'kein Versand');
      } else {
          $gut = $smtpOk === '1';
          status_zeile('Letzter Versand',
              status_alter($smtpLetzte) . ' · '
              . fmt_local(str_replace(['T', 'Z'], [' ', ''], $smtpLetzte), 'd.m.Y · H:i')
              . ' Uhr'
              . ($gut ? '' : '. Die Ursache steht im Fehlerprotokoll des Webspace — '
                            . 'geprüft wird der Host, nicht die Zugangsdaten'),
              $gut ? 'blau' : 'rot',
              $gut ? 'zugestellt' : 'fehlgeschlagen');
      }

      status_zeile('Antwort und Versand',
          antwort_entkoppelbar()
              ? '„Passwort vergessen" dauert für vorhandene und unbekannte '
                . 'Adressen gleich lang — die Antwort ist fertig, bevor der '
                . 'Versand beginnt'
              : 'Diese PHP-Anbindung kennt weder fastcgi_finish_request noch '
                . 'litespeed_finish_request. Im ungünstigen Fall verrät die '
                . 'Dauer von „Passwort vergessen", ob es zu einer Adresse ein '
                . 'Konto gibt',
          antwort_entkoppelbar() ? 'blau' : 'orange',
          antwort_entkoppelbar() ? 'entkoppelt' : 'nicht sicher');
      ?>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">

    <?php ui_karte_start(['titel' => 'Hintergrundjobs', 'id' => 'k-jobs']); ?>
      <?php if ($jobs === []): ?>
        <?php status_zeile('Jobs', 'Die Tabelle `jobs` fehlt — der Migrationslauf '
            . 'nach dem Ausrollen von Web 10.1.0 steht noch aus',
            'rot', 'Migration ausstehend', 'betrieb_updates.php'); ?>
      <?php else: ?>
        <?php
        if ($jobPause !== null) {
            status_zeile('Pause',
                'Die Hintergrundarbeit ist angehalten bis '
                . fmt_local(str_replace(['T', 'Z'], [' ', ''], $jobPause), 'd.m.Y · H:i')
                . ' Uhr. Aufheben: php jobs.php --pause 0',
                'orange', 'angehalten', 'betrieb_jobs.php');
        }

        /* DER AUSLÖSER IST DIE WICHTIGSTE ZEILE DIESER KARTE. Ein Job ohne
           Fehler und ohne Rückstand sieht gesund aus — auch dann, wenn ihn
           seit drei Wochen niemand angestoßen hat. */
        $letzterLauf = null; $ausloeser = null;
        foreach ($jobs as $j) {
            if ($j['letzter_lauf'] !== null
                && ($letzterLauf === null || $j['letzter_lauf'] > $letzterLauf)) {
                $letzterLauf = $j['letzter_lauf'];
                $ausloeser   = $j['letzter_ausloeser'];
            }
        }
        $alterS = $letzterLauf === null ? null
                : time() - (int)strtotime(str_replace(['T', 'Z'], [' ', ''], $letzterLauf) . ' UTC');
        $wege = ['cli' => 'Kommandozeile (Cron)', 'token' => 'Abruf über die Adresse',
                 'anfrage' => 'huckepack an einer Anfrage'];
        if ($letzterLauf === null) {
            status_zeile('Auslöser', 'Noch kein Lauf. Bis dahin geschieht nichts — '
                . 'weder Aufräumen noch Verdichten noch Versand',
                'rot', 'nie gelaufen', 'betrieb_jobs.php');
        } else {
            $tonA = $alterS > 86400 ? 'rot'
                  : (($ausloeser === 'anfrage') ? 'orange' : 'blau');
            status_zeile('Auslöser',
                ($wege[$ausloeser] ?? (string)$ausloeser) . ' · zuletzt '
                . status_alter($letzterLauf)
                . ($ausloeser === 'anfrage'
                    ? '. Der Huckepack-Weg läuft höchstens alle fünf Minuten und '
                      . 'nur, wenn jemand eine Seite aufruft — für einen gewachsenen '
                      . 'Bestand zu wenig'
                    : ''),
                $tonA,
                /* Die Plakette sagt den ZUSTAND, nicht noch einmal den Weg —
                   der steht schon in der Kleinzeile. */
                $alterS > 86400 ? 'über 24 h her'
                                : ($ausloeser === 'anfrage' ? 'huckepack' : 'läuft'),
                'betrieb_jobs.php');
        }

        foreach ($jobs as $j) {
            $fehler = (string)($j['letzter_fehler'] ?? '');
            $rueck  = $j['rueckstand'] === null ? null : (int)$j['rueckstand'];
            if ($fehler !== '') {
                $ton = 'rot'; $pl = 'scheitert';
                $klein = 'Letzter Fehler: ' . $fehler;
            } elseif ($rueck !== null && $rueck > 0) {
                $ton = 'orange'; $pl = $rueck . ' offen';
                $klein = 'Rückstand — zuletzt gelaufen ' . status_alter($j['letzter_lauf']);
            } elseif ($j['letzter_lauf'] === null) {
                $ton = 'neutral'; $pl = 'noch nie';
                $klein = (string)$j['beschreibung'];
            } else {
                $ton = 'blau'; $pl = 'in Ordnung';
                $klein = 'Zuletzt gelaufen ' . status_alter($j['letzter_lauf']);
            }
            status_zeile((string)$j['titel'], $klein, $ton, $pl, 'betrieb_jobs.php');
        }
        ?>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php ui_karte_start(['titel' => 'Backups', 'id' => 'k-backups']); ?>
      <?php
      /* Komplett-Backup: der jüngste Stand gegen den Plan. „Nie" ist bei
         Plan „aus" eine Entscheidung und bei jedem anderen Plan ein Fehler —
         deshalb hängt der Ton am Plan und nicht allein am Bestand. */
      $juengster = $kompStaende ? $kompStaende[0] : null;
      $kompZeit  = $juengster['zeit'] ?? null;
      if ($juengster === null) {
          status_zeile('Komplett-Backup',
              $kompPlan === 'aus'
                  ? 'Kein Stand vorhanden, und es ist kein Plan gesetzt. Das ist eine '
                    . 'Entscheidung — gegen „der Webspace ist weg" hilft dann nichts'
                  : 'Kein Stand vorhanden, obwohl ein Plan gesetzt ist ('
                    . (KOMP_PLAENE[$kompPlan] ?? $kompPlan) . ')',
              $kompPlan === 'aus' ? 'neutral' : 'rot',
              $kompPlan === 'aus' ? 'kein Plan' : 'nie',
              'admin_komplettsicherung.php');
      } else {
          $faellig = komp_faellig();
          status_zeile('Komplett-Backup',
              'Jüngster Stand ' . status_alter($kompZeit)
              . ' · Plan: ' . (KOMP_PLAENE[$kompPlan] ?? $kompPlan)
              . ' · ' . count($kompStaende)
              . (count($kompStaende) === 1 ? ' Stand aufbewahrt' : ' Stände aufbewahrt'),
              $faellig ? 'orange' : 'blau',
              $faellig ? 'überfällig' : 'aktuell',
              'admin_komplettsicherung.php');
      }

      $krank = (int)$zahlen['ueberfaellig'] + (int)$zahlen['nie'];
      status_zeile('Konto-Backups',
          $krank === 0
              ? $zahlen['konten'] . ' Konten, keines überfällig'
              : (int)$zahlen['ueberfaellig'] . ' überfällig, ' . (int)$zahlen['nie']
                . ' nie gesichert — von ' . $zahlen['konten'] . ' Konten',
          $krank === 0 ? 'blau' : 'orange',
          $krank === 0 ? 'aktuell' : $krank . ' offen',
          'admin_sicherungen.php');

      /* Backup-Ziele: ein Ziel, das aktiv ist und nie etwas bekommen hat, ist
         der gefährlichste Zustand — es sieht eingerichtet aus. */
      $aktiv = array_values(array_filter($ziele, static fn($z) => !empty($z['aktiv'])));
      if ($ziele === []) {
          status_zeile('Backup-Ziele',
              'Kein Ziel eingetragen. Die Konto-Backups liegen damit auf demselben '
              . 'Server, dessen Ausfall der Grund für ein Backup wäre',
              'neutral', 'keines', 'admin_sicherungsziele.php');
      } else {
          $nieVersandt = array_values(array_filter($aktiv,
              static fn($z) => empty($z['letzter_lauf'])));
          $mitFehler = array_values(array_filter($aktiv,
              static fn($z) => !empty($z['letzter_fehler'])));
          if ($mitFehler !== []) {
              $ton = 'rot'; $pl = count($mitFehler) . ' mit Fehler';
              $klein = 'Letzter Fehler: ' . (string)$mitFehler[0]['letzter_fehler'];
          } elseif ($nieVersandt !== []) {
              $ton = 'orange'; $pl = 'nie versendet';
              $klein = count($nieVersandt) . ' aktives Ziel ohne jeden Versand — '
                     . 'eingerichtet sieht es trotzdem aus';
          } else {
              $ton = $aktiv === [] ? 'neutral' : 'blau';
              $pl  = $aktiv === [] ? 'keines aktiv' : count($aktiv) . ' aktiv';
              $klein = count($ziele) . ' eingetragen · Versand '
                     . (sz_auto_an() ? 'automatisch' : 'nur von Hand');
          }
          status_zeile('Backup-Ziele', $klein, $ton, $pl, 'admin_sicherungsziele.php');
      }

      /* Speicher: derselbe Ton wie der Balken auf den Servereinstellungen —
         `speicher_ton()` ist die eine Regel dafür (S8/AP2). */
      $proz = (int)$sp['backups']['prozent'];
      $tonS = speicher_ton($proz, $sp['schwellen']);
      status_zeile('Speicher der Backups',
          $proz . ' % der Speichergrenze belegt · '
          . edbak_groesse_text($sp['backups']['summe']) . ' von '
          . edbak_groesse_text($sp['backups']['bezug'])
          . ' · Warnschwellen ' . implode(', ', $sp['schwellen']) . ' %',
          $tonS === 'neutral' ? 'blau' : $tonS,
          $proz . ' %',
          'betrieb_server.php');

      status_zeile('Ablage',
          $ablageBereit
              ? (string)$sp['ablage']['pfad'] . ' · ' . $sp['pakete'] . ' Pakete in '
                . $sp['ordner'] . ' Ordnern'
              : (string)($ablageGrund ?? 'Nicht beschreibbar — es entsteht kein Backup'),
          $ablageBereit ? 'blau' : 'rot',
          $ablageBereit ? 'beschreibbar' : 'nicht beschreibbar',
          'betrieb_server.php');
      ?>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (rechts) */ ?>
  </div><?php /* .form-raster */ ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                        'vorschau' => 'Ampel · lesend · eine Ausnahme']); ?>
    <p class="feld-hinweis"><strong>Die Ampel hat vier Töne, und sie bedeuten
       auf dieser Seite überall dasselbe.</strong> <em>Blau</em>: es ist in
       Ordnung. <em>Orange</em>: es braucht Aufmerksamkeit, arbeitet aber.
       <em>Rot</em>: es arbeitet nicht — oder es geht dabei etwas verloren.
       <em>Neutral</em>: nicht eingerichtet, oder eine reine Zahl ohne
       Wertung.</p>
    <p class="feld-hinweis"><strong>Die Seite ändert nichts.</strong> Jede
       Zeile führt auf die Seite, die zuständig ist. Die einzige Ausnahme ist
       der fehlende Serverschlüssel — dort ist der Weg ein Knopf, und von der
       Seite, die das Problem meldet, auf eine andere zu schicken, wo derselbe
       Knopf steht, wäre ein Umweg ohne Zweck.</p>
    <p class="feld-hinweis"><strong>Die Zahlen sind nicht alle gleich alt.</strong>
       Wartungsmodus, Migrationen, Jobs, Konto-Backups und die Ablage werden
       bei jedem Aufruf gelesen. Die Größe von Datenbank und Dateien kommt aus
       der täglichen Messung im Aufräumjob — die Zeile „Datenbank" sagt, wann
       sie entstanden ist. Ein Zwischenspeicher über das Ganze gibt es
       bewusst nicht: Eine Statusseite, die einen Zustand zeigt, den es nicht
       mehr gibt, ist schlechter als keine.</p>
  <?php ui_karte_ende(true); ?>

  <?php
  $rumpf = ob_get_clean();
  $z = $GLOBALS['status_zaehler'];
  if ($z['rot'] > 0 || $z['orange'] > 0) {
      $teile = [];
      if ($z['rot'] > 0)    { $teile[] = $z['rot'] . ($z['rot'] === 1 ? ' Punkt arbeitet nicht' : ' Punkte arbeiten nicht'); }
      if ($z['orange'] > 0) { $teile[] = $z['orange'] . ($z['orange'] === 1 ? ' Punkt braucht Aufmerksamkeit' : ' Punkte brauchen Aufmerksamkeit'); }
      echo ui_meldung_markup($z['rot'] > 0 ? 'fehler' : 'warn',
          implode(' · ', $teile) . '. Sie stehen unten rot beziehungsweise orange; '
          . 'jede Zeile führt dorthin, wo sich etwas ändern lässt.');
  } else {
      echo ui_meldung_markup('info',
          'Wartungsmodus aus, keine ausstehende Migration, Jobs laufen, Backups '
          . 'aktuell, Speicher unter der Warnschwelle.', 'Alles läuft.');
  }
  echo $rumpf;
  ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
