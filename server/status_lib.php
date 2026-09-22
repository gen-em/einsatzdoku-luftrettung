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
require_once __DIR__ . '/mail_lib.php';   // Lage der Warteschlange (P5a/AP5)
require_once __DIR__ . '/plattform_lib.php';
require_once __DIR__ . '/format_lib.php';   // groesse_text(), zeit_relativ(), datum_zeit_text() (AP7)

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
    /* Die zwei Geheimnisse des Servers (S10, E-S10-09). Beide Zustände kommen
     * aus derselben Quelle wie die Karte in den Servereinstellungen — eine
     * Statusseite, die anders rechnet als die Seite, auf die sie verweist,
     * wäre eine zweite Wahrheit. */
    $skZustand  = serverschluessel_zustand();
    $anZustand  = anteil_zustand();
    $anZaehlung = anteil_zaehlung();

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

    /* Konten, die noch auf einem ALTWERT der Liste stehen (Backlog Nr. 136,
     * Fund F-9a-02). Die Zeile darunter meldete bisher nur verwaiste Werte —
     * also den Fall, dass jemand einen Eintrag ZU FRUEH aus KDF_ITER_LISTE
     * gestrichen hat. Die Frage davor beantwortete sie nicht: WANN darf er
     * gestrichen werden? db.php nennt dafuer eine SQL-Abfrage von Hand, und
     * SP-1 nimmt an, die Wartungsseite sage es. Sie sagte es nicht.
     *
     * Solange hier eine Zahl > 0 steht, rechnet JEDE Anmeldung zweimal ab
     * (Uebergangszustand, db.php) — die Zahl ist damit auch die Auskunft
     * darueber, was der Uebergang gerade kostet. */
    $kdfAlt = 0;
    $kdfDemoAlt = false;
    if (count($kdfListe) > 1) {
        /* OHNE DAS DEMO-KONTO (Nachbesserung 07.09.2026, Gegenpruefung Fund
         * 11). Es wird alle 30 Minuten aus der Fixture eingespielt -- mit der
         * Rundenzahl, die die Fixture traegt --, und die stille Anhebung
         * ueberspringt es ausdruecklich (api/kdf_upgrade.php, E-P1-19: ein
         * Upgrade passte bis zum naechsten Reset nicht mehr zu seinen
         * oeffentlichen Zugangsdaten). Es zieht also nie nach, aus zwei
         * Gruenden, nicht aus einem -- die erste Fassung dieses Kommentars
         * nannte nur den Reset (zweite Gegenpruefung, Wiederaufnahme).
         * Gezaehlt stuende hier fuer immer "1 Konto unter dem Zielwert",
         * und die Zeile verloere den einen Zweck, den sie hat: zu sagen,
         * wann der Altwert weg darf. Das Demo-Konto bekommt deshalb seinen
         * eigenen Satz -- aber nur, solange sein Wert in der Liste STEHT.
         * Steht er nicht mehr darin, ist das Demo-Konto eines der
         * blockierten Konten aus der roten Zeile darueber, und ein Satz,
         * der behauptet, der Altwert bleibe in der Liste, waere falsch. */
        require_once __DIR__ . '/demo_lib.php';
        $demoId = demo_id();
        /* NUR WERTE AUS DER LISTE: Ein Konto auf einem verwaisten Wert steht
         * schon in der roten Zahl darueber und zieht NICHT still nach -- es
         * kann sich gar nicht anmelden. Bis zur Wiederaufnahme der zweiten
         * Gegenpruefung zaehlte es hier trotzdem mit. */
        $sta = $pdo->prepare("SELECT COUNT(*) FROM users
                              WHERE password_hash IS NOT NULL AND kdf_iter <> ? AND id <> ?
                                AND kdf_iter IN ($platz)");
        $sta->execute(array_merge([KDF_ITER_ZIEL, $demoId ?? 0], $kdfListe));
        $kdfAlt = (int)$sta->fetchColumn();
        if ($demoId !== null) {
            $std = $pdo->prepare('SELECT kdf_iter FROM users WHERE id = ?');
            $std->execute([$demoId]);
            $demoIter = (int)$std->fetchColumn();
            $kdfDemoAlt = $demoIter !== KDF_ITER_ZIEL && in_array($demoIter, $kdfListe, true);
        }
    }

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
                  ? datum_zeit_text($wartung['seit'], ' · ') . ' Uhr'
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
       kein Versand auf ein Backup-Ziel.
       SEIT S10 MIT KENNUNG — und mit einer dritten Lage: „abweichend" heisst,
       dass in `config.php` ein ANDERER Schlüssel steht als der, mit dem
       versiegelt wurde. Das sah bis dahin aus wie ein beschädigtes Backup. */
    $server[] = status_z('Serverschlüssel',
        $skZustand['stand'] === 'bereit'
            ? 'Kennung ' . $skZustand['kennung'] . ' — Komplett-Backups, '
              . 'Konto-Backups und Backup-Ziele können versiegeln'
            : ($skZustand['stand'] === 'fehlt'
                ? 'Fehlt. Ohne ihn gibt es kein Komplett-Backup, kein '
                  . 'Konto-Backup und keinen Versand auf ein Backup-Ziel'
                : 'In config.php steht Kennung ' . ($skZustand['kennung'] ?? '—')
                  . ', versiegelt wurde mit ' . $skZustand['erwartet']
                  . '. Versiegeltes lässt sich nicht öffnen, bis der richtige '
                  . 'Wert nachgetragen ist (Blatt)'),
        $skZustand['stand'] === 'bereit' ? 'blau' : 'rot',
        ['bereit' => 'vorhanden', 'fehlt' => 'fehlt',
         'abweichend' => 'abweichend'][$skZustand['stand']] ?? '?',
        $skZustand['stand'] === 'bereit' ? null : 'betrieb_server.php#k-schluessel');

    /* ---- Server-Anteil (S10, E-S10-09) ---------------------------------
     *
     * DIE ZEILE, DIE ES OHNE S10 NICHT GEBEN MUSSTE — und die ohne sie die
     * teuerste Störung der Anwendung unsichtbar liesse: Ein Anteil, der nicht
     * zu den Hüllen passt, sieht für JEDE NutzerIn gleichzeitig aus wie ein
     * falsches Passwort. Hier steht, was wirklich los ist, samt der Kennung,
     * die nachzutragen wäre.
     *
     * BLAU, SOLANGE DER ÜBERGANG LÄUFT. Das ist kein Fehler, sondern ein
     * Zustand mit Ende: Jedes Konto stellt beim nächsten Anmelden um. Rot
     * wäre eine Aufforderung zu etwas, das niemand tun kann und niemand tun
     * muss. Dieselbe Unterscheidung wie bei der Zeile darunter
     * („Schlüsselableitung"), und aus demselben Grund.
     *
     * DAS DEMO-KONTO ZÄHLT NICHT MIT (Backlog Nr. 155): Es bleibt bauartbedingt
     * ohne Anteil, und es als „noch offen" zu führen hiesse, eine Zahl zu
     * zeigen, die nie auf null geht. Genau dieser Fehler ist der Zeile
     * „Schlüsselableitung" schon einmal unterlaufen. */
    $anOffen = (int)$anZaehlung['ohne'] + (int)$anZaehlung['alt'];
    if ($anZustand['stand'] === 'fehlt') {
        /* NEUTRAL UND NICHT ROT — die Ampel in `Design.md` 9.23 ist dazu
         * eindeutig: Rot heisst „arbeitet nicht, oder es geht etwas
         * verloren", neutral heisst „nicht eingerichtet". Ohne Server-Anteil
         * arbeitet ALLES wie vor Web 20.0.0; es geht nichts verloren, es
         * fehlt nur ein zusaetzlicher Schutz.
         *
         * Das Konzept sah hier Rot vor (E-S10-09). Das ist beim Bauen
         * verworfen worden, und zwar aus dem Grund, den die Ampeltabelle
         * selbst nennt: Nach dem Merge steht JEDE Installation in diesem
         * Zustand. Eine rote Zeile, die „alles in Ordnung, aber tu mal was"
         * bedeutet, bringt Rot das Lesen ab — und dann wird auch die Zeile
         * darueber nicht mehr gelesen, bei der Rot heisst, dass niemand mehr
         * hereinkommt. */
        $anText = 'Nicht eingerichtet. Alles läuft wie vor Web 20.0.0 — der '
                . 'Schutz gegen einen Datenbankabzug fehlt aber. Anlegen unter '
                . 'Betrieb → Servereinstellungen, danach Schlüsselblatt drucken';
        $anTon = 'neutral'; $anPlak = 'nicht eingerichtet';
    } elseif ($anZustand['stand'] === 'abweichend') {
        $anText = 'In config.php steht Kennung ' . ($anZustand['kennung'] ?? '—')
                . ', gebaut wurden die Hüllen mit ' . $anZustand['erwartet']
                . '. Solange das so ist, kommt niemand mit umgestellter Hülle an '
                . 'seine geschützten Angaben — und die Anmeldemaske sagt nicht, '
                . 'warum. Wert vom Schlüsselblatt nachtragen';
        $anTon = 'rot'; $anPlak = 'abweichend';
    } else {
        $teile = [$anZaehlung['neu'] . ' Konto/Konten auf dem aktuellen Anteil'];
        if ($anZustand['kennung_alt'] !== null) {
            $teile[] = $anZaehlung['alt'] . ' noch auf dem alten';
        } elseif ($anZaehlung['alt'] > 0) {
            $teile[] = $anZaehlung['alt'] . ' auf einem unbekannten';
        }
        if ($anZaehlung['ohne'] > 0) { $teile[] = $anZaehlung['ohne'] . ' noch ohne'; }
        $anText = 'Kennung ' . $anZustand['kennung'] . ' — ' . implode(', ', $teile)
                . '. Jedes Konto stellt beim nächsten Anmelden von selbst um';
        if ($anZaehlung['demo'] > 0) {
            $anText .= '. Das Demo-Konto bleibt ohne Anteil und zählt hier nicht mit';
        }
        if ($anZustand['kennung_alt'] !== null && $anZaehlung['alt'] === 0) {
            $anText .= '. Der alte Anteil lässt sich jetzt entfernen';
        }
        /* BLAU IN ALLEN DREI FÄLLEN, und das ist Absicht: „bereit", „Übergang
         * läuft" und „alten Anteil entfernen" sind Auskünfte, keine Störungen.
         * Orange hiesse „hier stimmt etwas nicht", rot „hier kommt niemand
         * mehr herein" — beides wäre für einen Zustand mit Ende falsch, und
         * eine Farbe, die auch für Normalzustände warnt, wird beim vierten
         * Mal nicht mehr gelesen. */
        $anTon  = 'blau';
        $anPlak = $anOffen > 0
            ? 'Übergang läuft'
            : ($anZustand['stand'] === 'rotation' ? 'alten entfernen' : 'in Ordnung');
    }
    $server[] = status_z('Server-Anteil', $anText, $anTon, $anPlak,
        ($anZustand['stand'] === 'bereit' && $anOffen === 0)
            ? null : 'betrieb_server.php#k-schluessel');

    $kdfText = $kdfVerwaist === []
        ? 'Alle Konten rechnen mit einer Rundenzahl, die diese Fassung anbietet ('
          . implode(', ', array_map('strval', $kdfListe)) . ')'
        : $kdfSumme . ' Konto/Konten tragen eine Rundenzahl, die diese Fassung '
          . 'nicht anbietet — sie können sich nicht anmelden. Behebung: den '
          . 'fehlenden Wert in KDF_ITER_LISTE (server/db.php) wieder aufnehmen';
    if ($kdfAlt > 0) {
        $kdfText .= '. ' . $kdfAlt . ' Konto/Konten stehen noch unter dem Zielwert '
                  . KDF_ITER_ZIEL . ' — sie ziehen still nach, sobald sie sich das '
                  . 'nächste Mal anmelden. Bis dahin rechnet jede Anmeldung zweimal '
                  . 'ab; erst wenn hier keine Zahl mehr steht, darf der Altwert aus '
                  . 'KDF_ITER_LISTE (server/db.php) gestrichen werden';
    }
    if ($kdfDemoAlt) {
        /* Der Satz steht auch dann, wenn alle anderen Konten nachgezogen
         * sind: Solange die Fixture den Altwert traegt, darf er nicht aus
         * der Liste -- das Demo-Konto koennte sich sonst nicht mehr anmelden. */
        $kdfText .= '. Das Demo-Konto steht auf der Rundenzahl seiner Fixture und '
                  . 'zieht nicht nach (die stille Anhebung überspringt es, und der Reset '
                  . 'spielt die Fixture alle 30 Minuten neu ein) — der Altwert bleibt in '
                  . 'der Liste, bis der Referenzbestand neu gebaut ist (Backlog Nr. 155)';
    }
    $server[] = status_z('Schlüsselableitung', $kdfText,
        $kdfVerwaist === [] ? 'blau' : 'rot',
        $kdfVerwaist !== [] ? 'Anmeldung blockiert'
            : ($kdfAlt > 0 ? 'Übergang läuft' : 'in Ordnung'));

    /* Dass diese Seite überhaupt antwortet, beweist die Erreichbarkeit — die
       Zeile sagt deshalb die GRÖSSE. „Nicht erreichbar" käme nie zur Anzeige;
       es gäbe keine Seite. */
    $server[] = status_z('Datenbank',
        $sp['stand'] !== null
            ? groesse_text($sp['gesamt']['datenbank'])
              . ' · Dateien ' . groesse_text($sp['gesamt']['dateien'])
              . ' · gemessen ' . zeit_relativ($sp['stand'])
            : 'Noch nicht gemessen — die Messung läuft im täglichen Aufräumjob',
        $sp['stand'] !== null ? 'blau' : 'neutral',
        $sp['stand'] !== null ? 'erreichbar' : 'ungemessen',
        'betrieb_server.php');

    /* ---- DIE VERBINDUNGSGRENZE (P5a/AP9, E-P5a-18) ----------------------
     *
     * Die Zeile darueber sagt, dass die Datenbank erreichbar IST — das
     * beweist diese Seite dadurch, dass es sie gibt. Sie sagt nicht, wie oft
     * sie es NICHT war. Genau das ist die Zahl, an der man merkt, dass
     * `max_user_connections` des Hosters fuer diesen Betrieb zu eng steht:
     * Wer 1203 bekommt, sieht eine 503 und liefert spaeter nach — es faellt
     * niemandem auf, bis es auffaellt.
     *
     * DER ZAEHLER STEHT IN EINER DATEI, nicht in der Datenbank. Warum, steht
     * bei `ueberlast_vermerken()` in `wartung_lib.php` (E-P5a-50): In dem
     * Moment, in dem gezaehlt werden muesste, gibt es keine Verbindung.
     *
     * WANN ORANGE — und warum nicht einfach „ab Spitze 10". Die Ampel soll
     * sagen, wie es JETZT steht, und sie muss wieder gruen werden koennen.
     * Eine Spitze von 41 aus dem letzten Herbst faerbte sie sonst fuer immer,
     * und was sich nie aendert, liest bald niemand mehr. Sie faerbt deshalb
     * bei zehn Vorfaellen in der LAUFENDEN Stunde — und zusaetzlich, wenn
     * die Spitze diese Schwelle erreicht hat und der letzte Vorfall keine 24
     * Stunden her ist. Damit geht eine Nacht, in der es dreissigmal eng war,
     * nicht unter, nur weil gerade Ruhe ist; und nach einem ruhigen Tag ist
     * die Zeile von selbst wieder blau. */
    $ul = ueberlast_stand();
    if (!$ul['schreibbar']) {
        /* DIE NULL, DIE NICHTS BEDEUTET (CLAUDE.md 6). Ohne diesen Zweig
         * saehe eine Installation, in der die Datei nicht angelegt werden
         * kann, aus wie eine ohne einen einzigen Vorfall. */
        $ulText = 'Nicht gezählt — die Zähldatei neben wartung.lock lässt sich '
                . 'nicht schreiben. Eine Null bedeutet hier nichts';
        $ulTon  = 'orange';
        $ulPlak = 'ungezählt';
    } elseif ($ul['gesamt'] === 0) {
        $ulText = 'Keine abgewiesene Verbindung seit Beginn der Zählung · '
                . 'persistente Verbindungen sind aus';
        $ulTon  = 'blau';
        $ulPlak = 'in Ordnung';
    } else {
        $ulAkut  = $ul['stunde'] === gmdate('Y-m-d H') ? $ul['n'] : 0;
        $ulFrisch = $ul['letzt'] !== null
                 && strtotime($ul['letzt'] . ' UTC') > time() - 86400;
        $ulEng   = $ulAkut >= UEBERLAST_ORANGE
                || ($ulFrisch && $ul['spitze'] >= UEBERLAST_ORANGE);
        $ulText = ($ulAkut > 0
                    ? $ulAkut . ' in dieser Stunde'
                    : 'in dieser Stunde keine')
                . ' · Spitze ' . $ul['spitze'] . ' je Stunde'
                . ($ul['spitze_stunde'] !== null
                    ? ' (' . datum_stunde_text($ul['spitze_stunde'] . ':00:00') . ' Uhr)'
                    : '')
                . ' · insgesamt ' . $ul['gesamt']
                . ' · zuletzt ' . zeit_relativ($ul['letzt'])
                . ($ulEng ? ' — max_user_connections beim Hoster anheben lassen' : '');
        $ulTon  = $ulEng ? 'orange' : 'blau';
        $ulPlak = $ulEng ? 'zu eng' : $ul['gesamt'] . ' gezählt';
    }
    $server[] = status_z('Verbindungen', $ulText, $ulTon, $ulPlak);

    /* ---- DIE MODELLTABELLE UND IHR NACHLOESE-JOB (P5a/AP11, E-P5a-21) ---
     *
     * WOGEGEN. `pair.php` löst die Teilenummer einer Uhr im Moment der
     * Kopplung auf. Trifft sie dabei auf eine ältere Tabelle, bleibt das
     * Modell leer und die Geräteart steht auf der ungeprüften Selbstauskunft
     * des Geräts — die Uhr-App sendet dort fest „uhr". Der Job zieht das
     * nach, sobald eine neue Tabelle da ist.
     *
     * DREI ZUSTÄNDE, und der mittlere ist der Grund für die Zeile: Ein Update
     * hat eine neue Tabelle mitgebracht, der Job hat sie noch nicht
     * verarbeitet. Ohne diesen Hinweis wäre das ein Zustand, den niemand
     * sieht — er löst sich beim nächsten Jobdurchlauf von selbst, und wenn
     * nicht, merkt es keiner. */
    require_once __DIR__ . '/geraetemodelle_lib.php';
    $gmStand = gm_stand_lesen();
    $gmSoll  = gm_tabellen_hash();
    $gmZahl  = count(GERAETE_MODELLE);
    if ($gmStand['hash'] === null) {
        $gmText = $gmZahl . ' Teilenummern · noch nie nachgelöst — der Job holt '
                . 'es beim nächsten Lauf nach';
        $gmTon  = 'neutral';
        $gmPlak = 'ungeprüft';
    } elseif ($gmStand['hash'] !== $gmSoll) {
        $gmText = $gmZahl . ' Teilenummern · die Tabelle hat sich geändert, der '
                . 'Nachlöse-Job zieht beim nächsten Lauf nach';
        $gmTon  = 'orange';
        $gmPlak = 'steht aus';
    } else {
        $gmText = $gmZahl . ' Teilenummern · zuletzt nachgelöst '
                . datum_zeit_text((string)$gmStand['am']) . ' Uhr · '
                . $gmStand['nachgeloest'] . ' nachgezogen, '
                . $gmStand['unbekannt'] . ' unbekannt (Handys und fremde Modelle, '
                . 'sie bleiben unberührt)';
        $gmTon  = 'blau';
        $gmPlak = 'aktuell';
    }
    $server[] = status_z('Gerätemodelle', $gmText, $gmTon, $gmPlak,
                         'betrieb_jobs.php');

    $server[] = status_z('PHP und Zeitzone',
        PHP_VERSION . ' · Anzeige in ' . date_default_timezone_get()
        . ' · gespeichert wird UTC',
        'neutral', PHP_SAPI);

    /* ---- RATENSCHUTZ (P5a/AP6, E-P5a-05) --------------------------------
     *
     * ZWEI ZEILEN, NICHT EINE KARTE. Die vollstaendige Sicherheitssicht —
     * alle Sperren mit Knopf „aufheben", die Ereignisse der letzten 30 Tage,
     * die Verlangsamungsphasen — ist eine UNTERSEITE von Status und wartet
     * auf Mockup M-P5a-01 (E-P5a-08, AP8). Wer sie jetzt hier baut, baut sie
     * zweimal.
     *
     * Was NICHT warten kann, ist die Verlangsamung: Sie aendert das Verhalten
     * der Anmeldung fuer alle, und eine Betreiberin, die nicht weiss, dass
     * sie laeuft, sucht den Fehler am Server. E-P5a-05 nennt sie
     * ausdruecklich „orange". */
    require_once __DIR__ . '/ratelimit_lib.php';
    $vBremse = rate_verlangsamung(true);
    if ($vBremse['stufe'] > 0) {
        $server[] = status_z('Verlangsamung',
            'Stufe ' . $vBremse['stufe'] . ' — jede fehlgeschlagene Anmeldung '
            . 'wartet ' . rtrim(rtrim(number_format($vBremse['sekunden'], 1, ',', ''), '0'), ',')
            . ' Sekunden. Gezählt sind ' . $vBremse['versuche'] . ' Fehlversuche in den '
            . 'letzten 15 Minuten. Wer das richtige Passwort hat, kommt durch',
            'orange', 'aktiv', 'betrieb_sicherheit.php#k-bremse-global');
    }

    $rsSperren = rate_sperren_aktiv(200);
    if ($rsSperren !== []) {
        /* `$rsZeile` und nicht `$sp` — jene Variable traegt in dieser Funktion
         * seit S8 den SPEICHERSTAND, und die Schleife hat sie beim ersten
         * Versuch ueberschrieben. Die Folge war kein Syntaxfehler, sondern
         * ein 500er dreihundert Zeilen weiter unten
         * (`speicher_ton(): Argument #2 must be of type array, null given`) —
         * und zwar NUR, wenn gerade etwas gesperrt war. */
        /* `art` KOMMT AUS DER BIBLIOTHEK, nicht aus einem zweiten
         * `str_starts_with` hier (P5a/AP8). Die Ableitung stand dreimal im
         * Bestand; jetzt steht sie einmal, in `rate_sperren_aktiv()`. */
        $konten = 0; $adressen = 0; $hoechste = 0;
        foreach ($rsSperren as $rsZeile) {
            if ($rsZeile['art'] === 'konto') { $konten++; } else { $adressen++; }
            $hoechste = max($hoechste, $rsZeile['stufe']);
        }
        $teile = [];
        if ($adressen > 0) { $teile[] = $adressen . ' Anschluss' . ($adressen === 1 ? '' : 'e'); }
        if ($konten > 0)   { $teile[] = $konten . ' Name' . ($konten === 1 ? '' : 'n'); }
        $server[] = status_z('Gesperrt',
            implode(' und ', $teile) . ' — höchste Stufe ' . $hoechste
            . '. Eine Sperre ist ein Ereignis, kein Fehler: Sie läuft von selbst ab',
            $hoechste >= rate_stufe_hoechste() ? 'orange' : 'blau',
            count($rsSperren) . ($hoechste >= rate_stufe_hoechste() ? ' · Stufe ' . $hoechste : ''),
            'betrieb_sicherheit.php#k-sperren');
    }

    /* ---- ABGEWIESENE GERAETEANMELDUNGEN (P5a/AP7, E-P5a-02) -------------
     *
     * Die zweite der beiden Stellen, an denen die Mengenbremse sichtbar wird;
     * die erste ist die Kontoseite am Geraet selbst. Hier steht sie, weil die
     * Betreiberin es sonst nie erfaehrt: Das Geraet gehoert einer Nutzerin,
     * die Statusseite gehoert ihr — und die Uhr, die seit Montag nichts mehr
     * hochlaedt, ist ihr Problem, sobald jemand nach den fehlenden Daten
     * fragt.
     *
     * KEIN LINK. Der Vermerk steht auf der Kontoseite der BESITZERIN, und
     * dorthin fuehrt von der Betriebsseite kein Weg — eine Verknuepfung, die
     * auf einer Rechteprüfung endet, ist schlechter als keine.
     *
     * IM TRY, WEIL ES DIE SPALTEN IM DEPLOY-FENSTER NOCH NICHT GIBT. Die
     * Statusseite ist genau die Seite, die in diesem Fenster aufgerufen wird
     * — sie darf daran nicht scheitern. */
    /* SIE FRAGT NICHT SELBST, SIE RUFT (P5a/AP8). Bis Web 20.11.0 stand hier
     * eine eigene Abfrage auf dieselben zwei Spalten, und die
     * Sicherheitsseite haette eine zweite daneben gestellt. Zwei Abfragen auf
     * denselben Bestand laufen auseinander, sobald eine von beiden eine
     * Bedingung dazubekommt. `sicherheit_bremse_geraete()` faengt den
     * Deploy-Fall selbst ab und liefert dann eine leere Liste. */
    $abgRows = sicherheit_bremse_geraete();
    if ($abgRows !== []) {
        $abgErst  = $abgRows[0];
        $abgSumme = 0;
        foreach ($abgRows as $r) { $abgSumme += $r['anzahl']; }
        $klein = 'Gerät „' . $abgErst['name'] . '": ' . $abgErst['anzahl']
               . ' abgewiesene Anmeldungen'
               . ($abgErst['seit'] !== null
                  ? ' seit ' . datum_zeit_text($abgErst['seit']) : '')
               . (count($abgRows) > 1 ? ' (und ' . (count($abgRows) - 1) . ' weitere)' : '')
               . '. Fast immer ein veralteter Schlüssel — das Gerät koppelt neu, '
               . 'und der Vermerk verschwindet beim nächsten gelungenen Upload';
        $server[] = status_z('Abgewiesene Geräte', $klein, 'orange',
            (string)$abgSumme, 'betrieb_sicherheit.php#k-bremse');
    }

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
            zeit_relativ($smtpLetzte) . ' · '
            . datum_zeit_text($smtpLetzte, ' · ')
            . ' Uhr'
            . ($gut ? '' : '. Die Ursache steht im Fehlerprotokoll des Webspace — '
                          . 'geprüft wird der Host, nicht die Zugangsdaten'),
            $gut ? 'blau' : 'rot',
            $gut ? 'zugestellt' : 'fehlgeschlagen');
    }

    /* DIE WARTESCHLANGE (P5a/AP5, E-P5a-41). Bis Web 20.7.0 sagte die Zeile
       „Letzter Versand" alles, was es zu sagen gab — sie sagte aber nur
       etwas über den LETZTEN Versuch. Eine Einladung, die vor zwei Tagen
       scheiterte, war danach unsichtbar, und die Marke stand längst. Diese
       Zeile ist die fehlende Auskunft: Liegt etwas? Ist etwas endgültig
       liegengeblieben, und für wen?

       ZWEI TÖNE, KEINE DREI. „Wartet" ist ORANGE, nicht rot: Eine Zeile in
       der Leiter ist der Normalfall eines kurz gestörten Mailservers und
       heilt von selbst. Rot ist erst, was nicht mehr heilt. */
    $lage = mail_lage();
    if ($lage !== null) {
        if ($lage['unzustellbar'] > 0) {
            $wer = implode(', ', $lage['adressen']);
            if ($lage['unzustellbar'] > count($lage['adressen'])) {
                $wer .= ' und ' . ($lage['unzustellbar'] - count($lage['adressen'])) . ' weitere';
            }
            $mail[] = status_z('Warteschlange',
                'Endgültig nicht zugestellt an ' . $wer
                . ($lage['grund'] !== null ? '. Zuletzt: ' . $lage['grund'] : '')
                . '. Die Zeilen verfallen nach 30 Tagen',
                'rot', $lage['unzustellbar'] . ' unzustellbar');
        } elseif ($lage['offen'] > 0) {
            $mail[] = status_z('Warteschlange',
                $lage['offen'] . ($lage['offen'] === 1 ? ' Nachricht wartet' : ' Nachrichten warten')
                . ' auf einen weiteren Versuch. Der Job `mail` holt sie nach; '
                . 'die Leiter geht über 24 Stunden',
                'orange', $lage['offen'] . ' wartet');
        } else {
            $mail[] = status_z('Warteschlange',
                'Nichts liegt an'
                . ($lage['zuspaet'] > 0
                   ? '. ' . $lage['zuspaet'] . ' Nachricht(en) sind abgelaufen, bevor sie '
                     . 'zugestellt werden konnten — ein Reset-Link gilt eine Stunde'
                   : ''),
                'blau', 'leer');
        }
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
                . datum_zeit_text($jobPause, ' · ')
                . ' Uhr. Aufheben über Betrieb → Hintergrundjobs '
                . '(oder php jobs.php --pause 0)',
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
                : time() - (int)iso_utc_lesen($letzterLauf);
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
                . zeit_relativ($letzterLauf)
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
                $klein = 'Rückstand — zuletzt gelaufen ' . zeit_relativ($j['letzter_lauf']);
            } elseif ($j['letzter_lauf'] === null) {
                $ton = 'neutral'; $pl = 'noch nie';
                $klein = (string)$j['beschreibung'];
            } else {
                $ton = 'blau'; $pl = 'in Ordnung';
                $klein = 'Zuletzt gelaufen ' . zeit_relativ($j['letzter_lauf']);
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
            'Jüngster Stand ' . zeit_relativ($kompZeit)
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
        /* ÜBERGANGENE ZIELE SIND EINE EIGENE SCHUBLADE (S10/AP4, E-S10-U-15).
         *
         * Ein Ziel mit abgeschafftem Protokoll trägt einen Vermerk in
         * `letzter_fehler` und hätte damit in `$mitFehler` gestanden: rot,
         * dauerhaft, und mit dem Text „Übergangen …" in der Zeile
         * „Letzter Fehler". Das ist die falsche Auskunft — es ist nichts
         * kaputt, es ist etwas umzustellen. Sortiert wird deshalb am
         * PROTOKOLL und nicht am Text der Meldung: Das ist die Wahrheit,
         * der Text ist nur ihre Beschreibung.
         *
         * Ton ORANGE und nicht rot (`Design.md` 9.23): „braucht
         * Aufmerksamkeit", nicht „ist kaputt". Es geht deswegen kein Backup
         * verloren — die Pakete liegen weiter da. */
        $umzustellen = array_values(array_filter($aktiv,
            static fn($z) => !sz_protokoll_erlaubt((string)$z['protokoll'])));
        $aktiv = array_values(array_filter($aktiv,
            static fn($z) => sz_protokoll_erlaubt((string)$z['protokoll'])));
        $nieVersandt = array_values(array_filter($aktiv,
            static fn($z) => empty($z['letzter_lauf'])));
        $mitFehler = array_values(array_filter($aktiv,
            static fn($z) => !empty($z['letzter_fehler'])));
        if ($umzustellen !== []) {
            $ton = 'orange';
            $pl  = count($umzustellen) . ' umzustellen';
            $klein = count($umzustellen) . ' aktives Ziel überträgt '
                   . 'unverschlüsselt und wird nicht mehr beschickt ('
                   . implode(', ', array_map(
                        static fn($z) => (string)$z['name'],
                        array_slice($umzustellen, 0, 2)))
                   . ') — auf SFTP oder FTPS umstellen';
        } elseif ($mitFehler !== []) {
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

    /* ---- WÄCHST EIN ZIEL, OHNE DASS DORT JE ETWAS ENTFERNT WURDE?
     *      (P5a/AP10, E-P5a-03) ------------------------------------------
     *
     * Der Versand ergänzt nur. Bei zwei Sicherungen je Konto und Monat läuft
     * ein Ziel damit über kurz oder lang voll — und niemand merkt es hier,
     * weil auf der Gegenstelle nichts von dieser Anwendung nachsieht. Genau
     * das ist Backlog Nr. 49.
     *
     * DIE ZEILE FRAGT DIE ZIELE NICHT. Sie liest das Versandprotokoll: Ein
     * Ziel, auf das seit über einem Monat geschickt wird und von dem nie
     * etwas entfernt wurde, wächst. Drei FTP-Verbindungen bei jedem Aufruf
     * der Statusseite wären eine Seite, die zehn Sekunden lädt — dieselbe
     * Überlegung wie bei `sz_versand_rueckstand()`.
     *
     * WAS SIE DAMIT NICHT SIEHT: eine Betreiberin, die dort von Hand
     * aufgeräumt hat. Der Satz sagt deshalb „es ist nie etwas entfernt
     * worden" und nicht „dort liegt zu viel" — er beschreibt, was diese
     * Installation weiß, nicht den Zustand der Gegenstelle.
     *
     * NUR FÜR ZIELE OHNE REGEL. Wo die Aufbewahrung eingeschaltet ist,
     * räumt der Versand selbst auf; die Zeile wäre dort eine Mahnung an
     * jemanden, der schon gehandelt hat (`sz_waechst()` filtert das). */
    $waechst = sz_waechst();
    if ($waechst !== []) {
        $erstes = $waechst[0];
        $backups[] = status_z('Aufbewahrung am Ziel',
            count($waechst) === 1
                ? 'Auf „' . $erstes['name'] . '" liegen ' . $erstes['dateien']
                  . ' Sicherungen (' . groesse_text($erstes['bytes'])
                  . '), und es ist dort nie etwas entfernt worden — seit '
                  . datum_text($erstes['seit'])
                : count($waechst) . ' Ziele wachsen seit über einem Monat, ohne dass '
                  . 'dort je etwas entfernt wurde — das größte ist „'
                  . $erstes['name'] . '" mit ' . groesse_text($erstes['bytes']),
            'orange',
            count($waechst) === 1 ? 'wächst' : count($waechst) . ' wachsen',
            'admin_sicherungsziele.php');
    }

    /* Speicher: derselbe Ton wie der Balken auf den Servereinstellungen —
       `speicher_ton()` ist die eine Regel dafür (S8/AP2). */
    $proz = (int)$sp['backups']['prozent'];
    $tonS = speicher_ton($proz, $sp['schwellen']);
    $backups[] = status_z('Speicher der Backups',
        $proz . ' % der Speichergrenze belegt · '
        . groesse_text($sp['backups']['summe']) . ' von '
        . groesse_text($sp['backups']['bezug'])
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

    /* ---- Plattform (P5a/AP2, E-P5a-19) ----------------------------------
     *
     * DIESELBE FUNKTION, DIE `install.php` VOR DER EINRICHTUNG FRAGT. Was die
     * Einrichtung verlangt, muss die Installation auch im dritten Jahr noch
     * erfuellen — und ein Hoster kann eine PHP-Fassung oder ein Weblimit
     * jederzeit umstellen, ohne jemanden zu fragen. Zwei Listen liefen dafuer
     * auseinander; es gibt deshalb nur eine (`plattform_lib.php`).
     *
     * MUSS-ABWEICHUNG IST ROT, EMPFOHLEN-ABWEICHUNG IST EIN HINWEIS. „Kein
     * Hinweis faerbt die Ampel" (Vorbereitung, Abschnitt 2): Die Ampel bleibt
     * den Zustaenden vorbehalten, die Technik.md 4.99e nennt. Ein
     * abgeschalteter OPcache ist kein Betriebsproblem, und eine Zahl im
     * Menuepunkt, die davon kaeme, schickte jemanden auf die Suche nach einem
     * Fehler, den es nicht gibt. Deshalb steht bei Empfohlen `neutral`.
     *
     * OHNE NETZ. `plattform_pruefen(..., mitNetz: false)`: Die Seite waehlt
     * keinen Mailserver an. Ein haengender hielte sonst bei jedem Aufruf einen
     * PHP-Arbeitsprozess; die Frage „antwortet er?" beantwortet der Knopf
     * „Testmail an mich" darueber.
     *
     * NUR ABWEICHENDE EMPFOHLEN-ZEILEN. Erfuellte Empfehlungen sind
     * Bestaetigung ohne Handlung — zehn davon draengten die drei Zeilen weg,
     * auf die es ankommt. Die Schlusszeile nennt dafuer die Zahl.
     */
    $plattform = [];
    $pBefunde  = plattform_pruefen($pdo, false);
    $pZahlen   = plattform_zaehlen($pBefunde);
    $empfGesamt = 0; $empfOk = 0;
    foreach ($pBefunde as $f) {
        if ($f['stufe'] === 'empfohlen') {
            $empfGesamt++;
            if ($f['ok'] === true) { $empfOk++; continue; }
            if ($f['ok'] === null) { continue; }   // nicht messbar: nicht als Mangel zeigen
            $plattform[] = status_z($f['name'],
                'Empfohlen: ' . $f['soll'] . ' · gemessen: ' . $f['gemessen']
                . ($f['klein'] !== '' ? ' — ' . $f['klein'] : ''),
                'neutral', 'Hinweis');
            continue;
        }
        /* `knapp` IST ERFUELLT, ABER NICHT MEHR LANGE (P5a/AP10, PP-5).
         * Heute trägt nur der freie Platz das Feld: rot unter dem Einfachen
         * des größten Komplett-Backups, orange unter dem Zweifachen. Ein
         * Befund ohne das Feld verhält sich wie vorher — `?? false`. */
        $knapp = (bool)($f['knapp'] ?? false);
        $ton = $f['ok'] === true ? ($knapp ? 'orange' : 'blau')
             : ($f['ok'] === null ? 'neutral' : 'rot');
        $plakette = $f['ok'] === true ? ($knapp ? 'knapp' : $f['gemessen'])
                  : ($f['ok'] === null ? 'nicht messbar' : 'fehlt');
        $plattform[] = status_z($f['name'],
            'Gebraucht: ' . $f['soll'] . ' · gemessen: ' . $f['gemessen']
            . ($f['klein'] !== '' ? ' — ' . $f['klein'] : ''),
            $ton, (string)$plakette,
            $f['einstellung'] === 'db_gb' ? 'betrieb_server.php' : null);
    }
    $plattform[] = status_z('Empfohlen insgesamt',
        $empfOk . ' von ' . $empfGesamt . ' erfüllt. Eine Abweichung steht oben als '
        . 'Hinweis; sie färbt die Ampel nicht — die Anwendung läuft vollständig, '
        . 'nur langsamer oder mit einem Handgriff mehr',
        'neutral', $empfOk . '/' . $empfGesamt);

    return [
        'karten' => [
            ['titel' => 'Server',          'id' => 'k-server',  'zeilen' => $server],
            ['titel' => 'E-Mail',          'id' => 'k-mail',    'zeilen' => $mail],
            ['titel' => 'Hintergrundjobs', 'id' => 'k-jobs',    'zeilen' => $jobZeilen],
            ['titel' => 'Backups',         'id' => 'k-backups', 'zeilen' => $backups],
            ['titel' => 'Plattform',       'id' => 'k-plattform', 'zeilen' => $plattform],
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
            'plattform_muss' => $pZahlen['muss_offen'],
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
