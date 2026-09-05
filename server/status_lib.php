<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
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
 * DIE ERHEBUNG DER STATUSSEITE — ohne eine Zeile Markup (S8/AP5).
 *
 * WARUM DIESE DATEI ENTSTANDEN IST. Bis Web 15.3.3 stand alles in
 * `betrieb_status.php`: die Abfragen, die Ampelentscheidung und die Ausgabe.
 * Das reichte, solange nur diese eine Seite die Antwort brauchte. Mit dem
 * Zaehler am Menuepunkt „Status" (S8/AP5, Konzept (3)) braucht sie eine
 * zweite Stelle — und ein Zaehler, der seine eigene Rechnung anstellt, sagt
 * frueher oder spaeter etwas anderes als die Seite, auf die er fuehrt. Das
 * waere schlimmer als kein Zaehler: Er stuende auf „2", die Seite zeigte
 * drei Punkte, und niemand wuesste, welcher der beiden luegt.
 *
 * Deshalb gibt es genau eine Erhebung. `status_karten()` liefert die Zeilen
 * samt Ton; `betrieb_status.php` zeichnet sie, `status_ampel()` zaehlt sie.
 * Wer eine Zeile hinzufuegt, aendert eine Stelle, und beide ziehen nach.
 *
 * DIE AMPEL IST EINE TABELLE, KEINE MEINUNG (Konzept S8, Ampeltabelle):
 *
 *   blau     es ist in Ordnung
 *   orange   es braucht Aufmerksamkeit, arbeitet aber
 *   rot      es arbeitet NICHT (oder etwas geht dabei verloren)
 *   neutral  nicht eingerichtet, oder eine reine Zahl ohne Wertung
 *
 * NUR ORANGE UND ROT WERDEN GEZAEHLT. Neutral heisst „hier ist nichts
 * eingerichtet" und ist keine Aufforderung; blau ist die Ruhe selbst.
 *
 * WAS DIE ERHEBUNG KOSTET. Jede Zeile hoechstens eine Abfrage oder einen
 * Dateizugriff; die teuerste Auskunft — die Groesse von Datenbank und
 * Dateien — kommt aus `app_state` und wurde im Aufraeumjob gemessen
 * (S8/AP2). Die Seite selbst baut sich damit in Millisekunden auf. Fuer den
 * Zaehler, der auf JEDER Seite des Einstellungsbereichs steht, ist das
 * trotzdem zu viel — dafuer gibt es `status_ampel()` mit Zwischenspeicher.
 */

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

/** Eine Zeile der Statusseite. Nur Daten — das Markup entsteht in der Seite. */
function status_z(string $text, string $klein, string $ton, string $plakette,
                  ?string $href = null): array
{
    return ['text' => $text, 'klein' => $klein, 'ton' => $ton,
            'plakette' => $plakette, 'href' => $href];
}

/**
 * Alles, was die Statusseite zeigt — als Liste von Karten mit Zeilen.
 *
 * Rückgabe unter 'karten': je Karte ['titel', 'id', 'zeilen'], und jede
 * Zeile ist ein Rückgabewert von `status_z()`. Die Reihenfolge ist Server,
 * E-Mail, Hintergrundjobs, Backups. Welche Karte in welcher Spalte steht,
 * entscheidet die Seite — das ist Anordnung, keine Auskunft.
 *
 * Unter 'zahlen' stehen die Rohwerte für die Menüzähler aus derselben
 * Erhebung.
 *
 * ALLES WIRD EINGESAMMELT, BEVOR ETWAS ENTSCHIEDEN WIRD. Die Reihenfolge der
 * Erhebung ist die der Abhängigkeiten, nicht die der Ausgabe; beides zu
 * vermischen hieße, dass beim nächsten Umbau eine Zeile wegfällt und mit ihr
 * eine Messung, die eine andere Zeile braucht.
 */
function status_erhebung(): array
{
    $pdo = db();

    $wartung    = wartung_daten();
    $lauf       = migrationen_lauf($pdo, false);
    $stand      = migrationen_stand($pdo);
    $schluessel = serverschluessel_da();

    /* Verwaiste Rundenzahlen: Konten, deren `kdf_iter` diese Fassung nicht
     * mehr anbietet. Sie können sich NICHT anmelden, und an der Anmeldemaske
     * ist die Ursache nicht zu erkennen (siehe db.php, KDF_ITER_LISTE). Die
     * Prüfung stand bis Web 15.0.0 auf der Wartungsseite. */
    $kdfListe = KDF_ITER_LISTE;
    $platz    = implode(',', array_fill(0, count($kdfListe), '?'));
    $stk = $pdo->prepare("SELECT kdf_iter, COUNT(*) AS n FROM users
                          WHERE password_hash IS NOT NULL AND kdf_iter NOT IN ($platz)
                          GROUP BY kdf_iter ORDER BY kdf_iter");
    $stk->execute($kdfListe);
    $kdfVerwaist = $stk->fetchAll();
    $kdfSumme    = array_sum(array_column($kdfVerwaist, 'n'));

    $sp          = speicher_uebersicht();
    $jobs        = jobs_zustand();
    $jobPause    = jobs_pause_bis();
    $zahlen      = edbak_stand_zaehlen();
    [$ablageBereit, $ablageGrund] = edbak_ablage_bereit();
    $kompStaende = komp_staende();
    $kompPlan    = komp_plan();
    $ziele       = sz_tabelle_da() ? sz_alle() : [];
    $smtpDa      = smtp_eingerichtet();
    $smtpLetzte  = (string)(edbak_marke_lesen('smtp_last') ?? '');
    $smtpOk      = edbak_marke_lesen('smtp_last_ok');

    /* ---- Server --------------------------------------------------------- */
    $server = [];
    $wAktiv = wartung_aktiv();
    $server[] = status_z('Serverbetrieb',
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
    $server[] = status_z('Updates',
        $offen > 0
            ? $offen . ($offen === 1 ? ' Migration steht aus' : ' Migrationen stehen aus')
            : 'Alles aktuell · ' . $stand['zahl'] . ' ausgeführt'
              . ($stand['letzte'] !== null ? ' · zuletzt ' . $stand['letzte'] : ''),
        $offen > 0 ? 'orange' : 'blau',
        $offen > 0 ? 'steht aus' : 'aktuell',
        'betrieb_updates.php');

    /* ROT UND MIT WEG: Ohne Serverschlüssel entsteht kein Komplett-Backup und
       kein Versand auf ein Backup-Ziel. */
    $server[] = status_z('Serverschlüssel',
        $schluessel
            ? 'Vorhanden — Komplett-Backups und Backup-Ziele können versiegeln'
            : 'Fehlt. Ohne ihn gibt es kein Komplett-Backup und keinen Versand '
              . 'auf ein Backup-Ziel',
        $schluessel ? 'blau' : 'rot',
        $schluessel ? 'vorhanden' : 'fehlt',
        $schluessel ? null : 'admin_sicherungsziele.php');

    $server[] = status_z('Schlüsselableitung',
        $kdfVerwaist === []
            ? 'Alle Konten rechnen mit einer Rundenzahl, die diese Fassung anbietet ('
              . implode(', ', array_map('strval', $kdfListe)) . ')'
            : $kdfSumme . ' Konto/Konten tragen eine Rundenzahl, die diese Fassung '
              . 'nicht anbietet — sie können sich nicht anmelden. Behebung: den '
              . 'fehlenden Wert in KDF_ITER_LISTE (server/db.php) wieder aufnehmen',
        $kdfVerwaist === [] ? 'blau' : 'rot',
        $kdfVerwaist === [] ? 'in Ordnung' : 'Anmeldung blockiert');

    /* Dass diese Seite überhaupt antwortet, beweist die Erreichbarkeit — die
       Zeile sagt deshalb die GRÖSSE. „Nicht erreichbar" käme nie zur Anzeige;
       es gäbe keine Seite. */
    $server[] = status_z('Datenbank',
        $sp['stand'] !== null
            ? edbak_groesse_text($sp['gesamt']['datenbank'])
              . ' · Dateien ' . edbak_groesse_text($sp['gesamt']['dateien'])
              . ' · gemessen ' . status_alter($sp['stand'])
            : 'Noch nicht gemessen — die Messung läuft im täglichen Aufräumjob',
        $sp['stand'] !== null ? 'blau' : 'neutral',
        $sp['stand'] !== null ? 'erreichbar' : 'ungemessen',
        'betrieb_server.php');

    $server[] = status_z('PHP und Zeitzone',
        PHP_VERSION . ' · Anzeige in ' . date_default_timezone_get()
        . ' · gespeichert wird UTC',
        'neutral', PHP_SAPI);

    /* ---- E-Mail --------------------------------------------------------- */
    $mail = [];
    $mail[] = status_z('SMTP',
        $smtpDa
            ? 'Eingerichtet in der config.php'
            : 'Nicht eingerichtet. Ohne SMTP gibt es keine Einladungslinks, keine '
              . 'Setz-Links und keine Erinnerung an überfällige Konto-Backups',
        $smtpDa ? 'blau' : 'neutral',
        $smtpDa ? 'eingerichtet' : 'nicht eingerichtet');

    /* SEIT WEB 15.3.0 WIRD DER VERSAND VERMERKT (Z-01). Vorher konnte niemand
       sagen, ob je eine Mail hinausging: `smtp_eingerichtet()` prüft die
       config.php, nicht den Mailserver. Ein falsches Passwort fiel erst auf,
       wenn jemand einen Setz-Link erwartete. */
    if ($smtpLetzte === '') {
        $mail[] = status_z('Letzter Versand',
            $smtpDa
                ? 'Seit dem Ausrollen dieser Fassung wurde nichts versendet — '
                  . 'oder es ist noch keine Mail angefallen'
                : 'Ohne SMTP wird nichts versendet',
            'neutral', 'kein Versand');
    } else {
        $gut = $smtpOk === '1';
        $mail[] = status_z('Letzter Versand',
            status_alter($smtpLetzte) . ' · '
            . fmt_local(str_replace(['T', 'Z'], [' ', ''], $smtpLetzte), 'd.m.Y · H:i')
            . ' Uhr'
            . ($gut ? '' : '. Die Ursache steht im Fehlerprotokoll des Webspace — '
                          . 'geprüft wird der Host, nicht die Zugangsdaten'),
            $gut ? 'blau' : 'rot',
            $gut ? 'zugestellt' : 'fehlgeschlagen');
    }

    $entkoppelt = antwort_entkoppelbar();
    $mail[] = status_z('Antwort und Versand',
        $entkoppelt
            ? '„Passwort vergessen" dauert für vorhandene und unbekannte '
              . 'Adressen gleich lang — die Antwort ist fertig, bevor der '
              . 'Versand beginnt'
            : 'Diese PHP-Anbindung kennt weder fastcgi_finish_request noch '
              . 'litespeed_finish_request. Im ungünstigen Fall verrät die '
              . 'Dauer von „Passwort vergessen", ob es zu einer Adresse ein '
              . 'Konto gibt',
        $entkoppelt ? 'blau' : 'orange',
        $entkoppelt ? 'entkoppelt' : 'nicht sicher');

    /* ---- Hintergrundjobs ------------------------------------------------- */
    $jobZeilen = [];
    if ($jobs === []) {
        $jobZeilen[] = status_z('Jobs', 'Die Tabelle `jobs` fehlt — der Migrationslauf '
            . 'nach dem Ausrollen von Web 10.1.0 steht noch aus',
            'rot', 'Migration ausstehend', 'betrieb_updates.php');
    } else {
        if ($jobPause !== null) {
            $jobZeilen[] = status_z('Pause',
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
            $jobZeilen[] = status_z('Auslöser', 'Noch kein Lauf. Bis dahin geschieht nichts — '
                . 'weder Aufräumen noch Verdichten noch Versand',
                'rot', 'nie gelaufen', 'betrieb_jobs.php');
        } else {
            $tonA = $alterS > 86400 ? 'rot'
                  : (($ausloeser === 'anfrage') ? 'orange' : 'blau');
            $jobZeilen[] = status_z('Auslöser',
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
            $jobZeilen[] = status_z((string)$j['titel'], $klein, $ton, $pl, 'betrieb_jobs.php');
        }
    }

    /* ---- Backups --------------------------------------------------------- */
    $backups = [];

    /* Komplett-Backup: der jüngste Stand gegen den Plan. „Nie" ist bei Plan
       „aus" eine Entscheidung und bei jedem anderen Plan ein Fehler — deshalb
       hängt der Ton am Plan und nicht allein am Bestand. */
    $juengster = $kompStaende ? $kompStaende[0] : null;
    $kompZeit  = $juengster['zeit'] ?? null;
    if ($juengster === null) {
        $backups[] = status_z('Komplett-Backup',
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
        $backups[] = status_z('Komplett-Backup',
            'Jüngster Stand ' . status_alter($kompZeit)
            . ' · Plan: ' . (KOMP_PLAENE[$kompPlan] ?? $kompPlan)
            . ' · ' . count($kompStaende)
            . (count($kompStaende) === 1 ? ' Stand aufbewahrt' : ' Stände aufbewahrt'),
            $faellig ? 'orange' : 'blau',
            $faellig ? 'überfällig' : 'aktuell',
            'admin_komplettsicherung.php');
    }

    $krank = (int)$zahlen['ueberfaellig'] + (int)$zahlen['nie'];
    $backups[] = status_z('Konto-Backups',
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
        $backups[] = status_z('Backup-Ziele',
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
        $backups[] = status_z('Backup-Ziele', $klein, $ton, $pl, 'admin_sicherungsziele.php');
    }

    /* Speicher: derselbe Ton wie der Balken auf den Servereinstellungen —
       `speicher_ton()` ist die eine Regel dafür (S8/AP2). */
    $proz = (int)$sp['backups']['prozent'];
    $tonS = speicher_ton($proz, $sp['schwellen']);
    $backups[] = status_z('Speicher der Backups',
        $proz . ' % der Speichergrenze belegt · '
        . edbak_groesse_text($sp['backups']['summe']) . ' von '
        . edbak_groesse_text($sp['backups']['bezug'])
        . ' · Warnschwellen ' . implode(', ', $sp['schwellen']) . ' %',
        $tonS === 'neutral' ? 'blau' : $tonS,
        $proz . ' %',
        'betrieb_server.php');

    $backups[] = status_z('Ablage',
        $ablageBereit
            ? (string)$sp['ablage']['pfad'] . ' · ' . $sp['pakete'] . ' Pakete in '
              . $sp['ordner'] . ' Ordnern'
            : (string)($ablageGrund ?? 'Nicht beschreibbar — es entsteht kein Backup'),
        $ablageBereit ? 'blau' : 'rot',
        $ablageBereit ? 'beschreibbar' : 'nicht beschreibbar',
        'betrieb_server.php');

    return [
        'karten' => [
            ['titel' => 'Server',          'id' => 'k-server',  'zeilen' => $server],
            ['titel' => 'E-Mail',          'id' => 'k-mail',    'zeilen' => $mail],
            ['titel' => 'Hintergrundjobs', 'id' => 'k-jobs',    'zeilen' => $jobZeilen],
            ['titel' => 'Backups',         'id' => 'k-backups', 'zeilen' => $backups],
        ],
        /* DIE ROHZAHLEN FUER DIE MENUEZAEHLER. Sie stammen aus derselben
         * Erhebung wie die Karten — nicht aus einer zweiten Rechnung. Ein
         * Zaehler, der eigene Abfragen stellt, sagt frueher oder spaeter
         * etwas anderes als die Seite, auf die er fuehrt. */
        'zahlen' => [
            'updates_offen' => $offen,
            'job_fehler'    => count(array_filter($jobs,
                static fn($j) => (string)($j['letzter_fehler'] ?? '') !== '')),
            'backups_krank' => $krank,
        ],
    ];
}

/** Nur die Karten — der Regelfall für die Seite. */
function status_karten(): array
{
    return status_erhebung()['karten'];
}

/** Zählt orange und rot in einer Kartenliste aus `status_karten()`. */
function status_zaehlen(array $karten): array
{
    $z = ['orange' => 0, 'rot' => 0];
    foreach ($karten as $k) {
        foreach ($k['zeilen'] as $zeile) {
            if ($zeile['ton'] === 'orange' || $zeile['ton'] === 'rot') { $z[$zeile['ton']]++; }
        }
    }
    return $z;
}


/* ---------------------------------------------------------------------------
 * DER ZWISCHENSPEICHER FÜR DEN MENÜZÄHLER (S8/AP5, Konzept (3))
 *
 * Der Zähler am Menüpunkt „Status" steht auf JEDER Seite des
 * Einstellungsbereichs. Die volle Erhebung dafür bei jedem Seitenaufruf zu
 * fahren, wäre die falsche Rechnung: Sie kostet ein gutes Dutzend Abfragen
 * für eine Zahl, die sich zwischen zwei Klicks so gut wie nie ändert.
 *
 * SECHZIG SEKUNDEN. Die Zahl steht als JSON in `app_state`, mit dem Zeitpunkt
 * ihrer Entstehung. Ist sie älter, wird neu gerechnet und geschrieben.
 *
 * WARUM app_state UND NICHT DIE SITZUNG. Der Zustand gehört der Installation,
 * nicht der Anmeldung: Zwei BetreiberInnen sollen dieselbe Zahl sehen, und
 * eine frisch angemeldete soll nicht erst eine eigene Erhebung auslösen.
 *
 * DIE STATUSSEITE SELBST BENUTZT DEN SPEICHER NICHT — sie rechnet immer neu
 * und frischt ihn dabei auf. Eine Statusseite, die einen Zustand zeigt, den
 * es nicht mehr gibt, wäre schlechter als keine; ein Menüzähler, der eine
 * Minute nachhängt, ist es nicht.
 */
const STATUS_CACHE_KEY = 'status_ampel';
const STATUS_CACHE_S   = 60;

/** Schreibt eine frisch gezählte Ampel in den Zwischenspeicher. */
function status_ampel_merken(array $z): void
{
    edbak_marke_setzen(STATUS_CACHE_KEY, json_encode(
        ['o' => (int)$z['orange'], 'r' => (int)$z['rot'], 't' => time()]));
}

/**
 * Die Ampel für den Menüzähler — aus dem Zwischenspeicher, sonst frisch.
 *
 * Rückgabe: ['orange' => int, 'rot' => int]. Bei einem Fehler in der Erhebung
 * (fehlende Tabelle vor der Migration, Datenbank weg) ist es 0/0 und der
 * Zähler bleibt aus: Ein Menüpunkt, der wegen einer kaputten Zählung eine
 * rote Zahl trägt, schickt jemanden auf die Suche nach einem Problem, das er
 * nicht hat.
 */
function status_ampel(): array
{
    $roh = edbak_marke_lesen(STATUS_CACHE_KEY);
    if ($roh !== null) {
        $d = json_decode($roh, true);
        if (is_array($d) && isset($d['o'], $d['r'], $d['t'])
            && time() - (int)$d['t'] < STATUS_CACHE_S) {
            return ['orange' => (int)$d['o'], 'rot' => (int)$d['r']];
        }
    }
    try {
        $z = status_zaehlen(status_erhebung()['karten']);
    } catch (Throwable $ex) {
        error_log('status_ampel: Erhebung fehlgeschlagen: ' . $ex->getMessage());
        return ['orange' => 0, 'rot' => 0];
    }
    status_ampel_merken($z);
    return $z;
}


/* ---------------------------------------------------------------------------
 * DIE ZÄHLER AM MENÜ (S8/AP5, Konzept (3))
 *
 * Vier Menüpunkte tragen eine Zahl, wenn es etwas zu tun gibt:
 *
 *   Status            orange + rot der Ampel   rot, sobald ein Punkt rot ist
 *   Updates           ausstehende Migrationen  neutral
 *   Hintergrundjobs   Jobs mit Fehler          rot
 *   Konto-Backups     überfällig + nie         orange
 *
 * KEINE NULL. Ein Zähler erscheint nur, wenn er über null steht — eine „0"
 * am Menüpunkt ist keine Auskunft, sondern eine Verzierung, und sie nimmt
 * dem Fall, in dem wirklich etwas ansteht, die Aufmerksamkeit.
 *
 * ZWEI SPEICHER, WEIL ZWEI ROLLEN. „Konto-Backups" steht im Block
 * Verwaltung und gilt schon für eine Admin; die drei anderen stehen im Block
 * Betrieb. Eine Admin ohne Betriebsrechte soll nicht die volle Erhebung
 * bezahlen, um eine Zahl zu sehen, die sie gar nicht sieht — deshalb hat der
 * billige Teil (eine Abfrage) einen eigenen Schlüssel.
 *
 * BEI EINEM FEHLER BLEIBT DER ZÄHLER AUS. Vor der Migration fehlen Tabellen,
 * und dann wirft die Erhebung. Ein Menüpunkt, der wegen einer kaputten
 * Zählung eine rote Zahl trägt, schickt jemanden auf die Suche nach einem
 * Problem, das er nicht hat.
 */
const MENUE_CACHE_BETRIEB = 'menue_zaehler_betrieb';
const MENUE_CACHE_KONTO   = 'menue_zaehler_konto';

/** Liest einen Zählerspeicher, wenn er jünger als 60 s ist. */
function menue_cache_lesen(string $key): ?array
{
    $roh = edbak_marke_lesen($key);
    if ($roh === null) { return null; }
    $d = json_decode($roh, true);
    if (!is_array($d) || !isset($d['t']) || time() - (int)$d['t'] >= STATUS_CACHE_S) {
        return null;
    }
    return $d;
}

/**
 * Die drei Zähler des Blocks Betrieb.
 *
 * Rückgabe je Schlüssel: ['n' => int, 'ton' => 'rot'|'orange'|'neutral'].
 * Fehlt ein Schlüssel, trägt der Menüpunkt keine Zahl.
 */
function menue_zaehler_betrieb(): array
{
    $d = menue_cache_lesen(MENUE_CACHE_BETRIEB);
    if ($d === null) {
        try {
            $e = status_erhebung();
        } catch (Throwable $ex) {
            error_log('menue_zaehler_betrieb: Erhebung fehlgeschlagen: ' . $ex->getMessage());
            return [];
        }
        $a = status_zaehlen($e['karten']);
        status_ampel_merken($a);
        $d = ['s' => $a['orange'] + $a['rot'], 'sr' => $a['rot'],
              'u' => (int)$e['zahlen']['updates_offen'],
              'j' => (int)$e['zahlen']['job_fehler'],
              't' => time()];
        edbak_marke_setzen(MENUE_CACHE_BETRIEB, (string)json_encode($d));
    }
    $z = [];
    if ((int)$d['s'] > 0) { $z['betrieb_status']    = ['n' => (int)$d['s'], 'ton' => (int)$d['sr'] > 0 ? 'rot' : 'orange']; }
    if ((int)$d['u'] > 0) { $z['betrieb_updates']   = ['n' => (int)$d['u'], 'ton' => 'neutral']; }
    if ((int)$d['j'] > 0) { $z['betrieb_jobs']      = ['n' => (int)$d['j'], 'ton' => 'rot']; }
    return $z;
}

/** Der Zähler des Blocks Verwaltung — überfällige und nie gesicherte Konten. */
function menue_zaehler_konto(): array
{
    $d = menue_cache_lesen(MENUE_CACHE_KONTO);
    if ($d === null) {
        try {
            $zahlen = edbak_stand_zaehlen();
        } catch (Throwable $ex) {
            error_log('menue_zaehler_konto: Zählung fehlgeschlagen: ' . $ex->getMessage());
            return [];
        }
        $d = ['k' => (int)$zahlen['ueberfaellig'] + (int)$zahlen['nie'], 't' => time()];
        edbak_marke_setzen(MENUE_CACHE_KONTO, (string)json_encode($d));
    }
    return (int)$d['k'] > 0
        ? ['admin_sicherungen' => ['n' => (int)$d['k'], 'ton' => 'orange']]
        : [];
}
