<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';   // Ablagezahlen, Grenze, Schwellen, Marken
require_once __DIR__ . '/format_lib.php';        // iso_utc(), prozent_wert(), groesse_text()

/**
 * SPEICHER DER INSTALLATION (S8/AP2, E-S8-18, Mockup 07 Fassung 2).
 *
 * ZWEI BALKEN, ZWEI BEZUEGE — und das ist der ganze Grund fuer diese Datei:
 *
 *   Backups              gegen die SPEICHERGRENZE (eine Einstellung)
 *   Installation gesamt  gegen den WEBSPACE LAUT HOSTING (eine Angabe)
 *
 * Die Grenze gab es schon; sie lag unter „Backups" und wirkte doch auch auf
 * die Komplett-Staende (B-S8-06). Der zweite Bezug ist neu und beantwortet die
 * Frage, die vorher niemand beantworten konnte: Wie viel von dem, was der
 * Hoster verkauft, ist eigentlich belegt?
 *
 * WARUM DER FREIE WEBSPACE NICHT GEMESSEN WIRD. `disk_free_space()` liefert
 * auf gemeinsam genutztem Hosting den Datentraeger des HOSTS, nicht die Quota
 * dieses Kontos — eine Zahl im Terabyte-Bereich, die nichts mit dem Tarif zu
 * tun hat. Sie waere schlimmer als keine: Man glaubte, es sei Platz. Der
 * Webspace ist deshalb eine ANGABE der BetreiberIn (`webspace_gb`), und ohne
 * sie zeigt der zweite Balken nur die Summe, ohne Anteil und ohne Warnung.
 *
 * WARUM GEMESSEN UND NICHT BEI JEDEM AUFRUF GERECHNET. Der Verzeichnislauf
 * ueber das Anwendungsverzeichnis und die Summe ueber `information_schema`
 * kosten zusammen mehr, als eine Seite kosten darf — und die Zahlen aendern
 * sich in Stunden, nicht in Sekunden. Sie entstehen deshalb einmal taeglich im
 * Aufraeumjob und stehen mit Zeitstempel in `app_state`; die Seite liest nur.
 *
 * WAS NICHT MITZAEHLT: Pakete, die auf einem Backup-Ziel liegen. Sie sind der
 * Zweck des Versands — sie liegen ausserhalb dieses Webspace, und ihre Groesse
 * kennt nur das Ziel.
 */

/* Schluessel in `app_state`. Sie stehen hier und nicht verstreut im Code —
 * wer die Messung aendert, sieht beide Seiten auf einmal. */
const SPEICHER_K_DB       = 'speicher_db_bytes';
const SPEICHER_K_DATEIEN  = 'speicher_dateien_bytes';
const SPEICHER_K_STAND    = 'speicher_stand';
const SPEICHER_K_WEBSPACE = 'webspace_gb';
/* Das DB-Kontingent (P5a/AP2, E-P5a-11, PP-2). Warum eine ANGABE und keine
 * Messung: Kein Hoster macht das Kontingent abfragbar — `information_schema`
 * sagt, wie gross die Datenbank IST, nicht, wie gross sie sein DARF. */
const SPEICHER_K_DB_GB    = 'db_gb';
/* Welche Schwellen schon gemeldet sind, je Kontingent. Dieselbe Mechanik wie
 * `adminbackup_schwellen_gemeldet`: Wer aufraeumt und wieder unter die
 * Schwelle faellt, soll beim naechsten Ueberschreiten erneut gewarnt werden. */
const SPEICHER_K_GEMELDET = 'speicher_schwellen_gemeldet';

/** Vorgabe des DB-Kontingents in GB — die Zielgroesse Z2 des S2-Konzepts. */
const SPEICHER_DB_GB_VORGABE = 10.0;

/**
 * Groesse der Datenbank in Byte — Daten und Indizes.
 *
 * `information_schema.TABLES` liefert Schaetzwerte, keine exakten Groessen
 * (InnoDB fuehrt sie nicht mit). Fuer die Frage „wie viel Platz brauche ich?"
 * ist das genau richtig; fuer eine Abrechnung waere es das nicht. Der
 * Unterschied liegt bei InnoDB in der Groessenordnung des Fuellgrads der
 * Seiten — einstellige Prozente.
 */
function speicher_datenbank_bytes(PDO $pdo): int
{
    $q = $pdo->query('SELECT COALESCE(SUM(data_length + index_length), 0)
                      FROM information_schema.TABLES
                      WHERE table_schema = DATABASE()');
    return (int)$q->fetchColumn();
}

/**
 * Groesse des Anwendungsverzeichnisses in Byte — OHNE `sicherungen/`.
 *
 * Ohne, weil die Backups im ersten Balken schon gezaehlt sind und im zweiten
 * als eigene Segmente erscheinen: Sie zweimal in dieselbe Summe zu nehmen
 * ergaebe einen Balken, der ueber 100 % laeuft.
 *
 * Gezaehlt wird, was tatsaechlich da ist — Code, Symbole, Logos, das APK,
 * `vendor/`. Symbolische Verweise werden NICHT verfolgt: Sie zeigen
 * typischerweise aus dem Verzeichnis heraus, und was ausserhalb liegt, gehoert
 * nicht in diese Summe.
 */
function speicher_dateien_bytes(): int
{
    $wurzel = __DIR__;
    $ablage = realpath(edbak_wurzel());
    $summe  = 0;
    try {
        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($wurzel,
                FilesystemIterator::SKIP_DOTS | FilesystemIterator::CURRENT_AS_FILEINFO),
            RecursiveIteratorIterator::SELF_FIRST);
        foreach ($it as $eintrag) {
            /** @var SplFileInfo $eintrag */
            $pfad = $eintrag->getPathname();
            if ($ablage !== false && str_starts_with($pfad, $ablage)) { continue; }
            if ($eintrag->isLink() || !$eintrag->isFile()) { continue; }
            $summe += (int)@$eintrag->getSize();
        }
    } catch (Throwable $ex) {
        error_log('speicher: Verzeichnislauf fehlgeschlagen: ' . $ex->getMessage());
        return 0;
    }
    return $summe;
}

/**
 * Beide Messungen ausfuehren und ablegen. Laeuft im taeglichen Aufraeumjob.
 *
 * SIE SCHREIBT AUCH BEI EINEM TEILERGEBNIS. Scheitert der Verzeichnislauf,
 * steht dort 0 — und 0 ist auf der Seite als „nicht messbar" zu erkennen,
 * waehrend ein alter Wert mit frischem Zeitstempel eine Luege waere.
 */
function speicher_messen(PDO $pdo): array
{
    $db    = speicher_datenbank_bytes($pdo);
    $datei = speicher_dateien_bytes();
    edbak_marke_setzen(SPEICHER_K_DB, (string)$db);
    edbak_marke_setzen(SPEICHER_K_DATEIEN, (string)$datei);
    edbak_marke_setzen(SPEICHER_K_STAND, iso_utc());
    return ['datenbank' => $db, 'dateien' => $datei];
}

/**
 * Das DB-Kontingent in Byte. Ohne Angabe gilt die Vorgabe (Z2: 10 GB).
 *
 * ANDERS ALS BEIM WEBSPACE GIBT ES HIER EINE VORGABE, und das hat einen
 * Grund: Der Webspace ist je Tarif verschieden und ohne Angabe schlicht
 * unbekannt — ein geratener Wert waere schlimmer als keiner. Die 10 GB
 * dagegen sind die Untergrenze, die diese Anwendung nach Z2 tragen muss; sie
 * ist eine Zusage des Projekts und keine Vermutung ueber den Hoster. Wer mehr
 * hat, traegt mehr ein.
 */
function speicher_db_kontingent_bytes(): int
{
    $roh = (string)(edbak_marke_lesen(SPEICHER_K_DB_GB) ?? '');
    $gb  = $roh === '' ? SPEICHER_DB_GB_VORGABE : (float)$roh;
    return $gb > 0 ? (int)round($gb * 1024 * 1024 * 1024) : 0;
}

/**
 * Das Kontingent setzen. 0 stellt die Vorgabe wieder her.
 *
 * DIE UNTERGRENZE 0,01 GB IST KEINE SCHIKANE. Abgelegt wird mit zwei
 * Nachkommastellen; alles darunter rundete zu „0", und „0" heisst in dieser
 * Funktion „Vorgabe". Wer 0,005 einträgt, bekäme also stillschweigend 10 GB
 * zurück — eine Eingabe, die das Gegenteil dessen bewirkt, was sie sagt.
 * Lieber ein Nein mit Grund.
 */
function speicher_db_kontingent_setzen(float $gb): bool
{
    if ($gb < 0 || $gb > 100000) { return false; }
    if ($gb > 0 && $gb < 0.01)   { return false; }
    return edbak_marke_setzen(SPEICHER_K_DB_GB,
        $gb > 0 ? rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') : '');
}

/** Webspace laut Hosting in Byte — 0 heisst „nicht angegeben". */
function speicher_webspace_bytes(): int
{
    $gb = (float)(edbak_marke_lesen(SPEICHER_K_WEBSPACE) ?? 0);
    return $gb > 0 ? (int)round($gb * 1024 * 1024 * 1024) : 0;
}

/** Die Angabe setzen. 0 loescht sie wieder. */
function speicher_webspace_setzen(float $gb): bool
{
    if ($gb < 0 || $gb > 100000) { return false; }
    return edbak_marke_setzen(SPEICHER_K_WEBSPACE,
        $gb > 0 ? rtrim(rtrim(number_format($gb, 2, '.', ''), '0'), '.') : '');
}

/**
 * Alle Zahlen fuer die Seite — ein Aufruf, ein Bild.
 *
 * Die Ablagezahlen kommen frisch (Verzeichnislauf ueber `sicherungen/`, den
 * `edbak_ablage_zahlen()` ohnehin je Anfrage einmal macht); Datenbank und
 * Dateien kommen aus der Messung. Beides in einer Struktur, damit die Seite
 * keine Rechnung selbst anstellt: Was der Balken zeigt, steht hier.
 */
function speicher_uebersicht(): array
{
    $z      = edbak_ablage_zahlen();
    $grenze = edbak_grenze_bytes();
    $konto  = (int)$z['pakete_bytes'];
    $komp   = (int)$z['komplett_bytes'];
    /* `sonstige` sind Begleitdateien, `.htaccess` und Reste. Sie zaehlen auf
     * die Grenze (das tun sie seit Web 11.2.0) und gehoeren deshalb in den
     * Balken — als Teil der Konto-Backups, denn dort liegen sie. */
    $konto  += (int)$z['sonstige_bytes'];
    $backups = $konto + $komp;

    $db       = (int)(edbak_marke_lesen(SPEICHER_K_DB) ?? 0);
    $dateien  = (int)(edbak_marke_lesen(SPEICHER_K_DATEIEN) ?? 0);
    $stand    = edbak_marke_lesen(SPEICHER_K_STAND);
    $webspace = speicher_webspace_bytes();
    $gesamt   = $db + $dateien + $backups;

    /* edbak_ablage_bereit() liefert eine LISTE [bool, ?string] — hier benannt,
     * damit die Seite nicht mit Zahlenindizes hantiert. */
    [$ablageOk, $ablageGrund] = edbak_ablage_bereit();

    return [
        'stand'     => $stand,
        'schwellen' => edbak_schwellen(),
        'backups'   => [
            'konto'    => $konto,
            'komplett' => $komp,
            'summe'    => $backups,
            'bezug'    => $grenze,
            /* ABGERUNDET, nicht kaufmaennisch: Dieselbe Zahl ist Anzeige UND
             * Grenzwert — `speicher_ton()` entscheidet an ihr die Farbe. Mit
             * 'kauf' zeigte die Plakette 90 %, waehrend der Ton noch blau ist. */
            'prozent'  => prozent_wert($backups, $grenze, 'ab'),
        ],
        'gesamt'    => [
            'datenbank' => $db,
            'dateien'   => $dateien,
            'konto'     => $konto,
            'komplett'  => $komp,
            'summe'     => $gesamt,
            'bezug'     => $webspace,
            'prozent'   => prozent_wert($gesamt, $webspace, 'ab'),   // abgerundet, siehe oben
        ],
        'ablage'    => ['ok' => $ablageOk, 'grund' => $ablageGrund,
                        'pfad' => edbak_wurzel()],
        'reste'     => (int)$z['reste'],
        'pakete'    => (int)$z['pakete'],
        'ordner'    => (int)$z['ordner'],
    ];
}

/**
 * Welcher Ton gehoert zu diesem Prozentsatz?
 *
 * Dieselben Schwellen wie die Warnmail (Vorgabe 70/90): unter der ersten
 * neutral, ab der ersten orange, ab der letzten rot. EINE Regel fuer Balken,
 * Legende und Statusseite — sonst faerbt sich der Balken orange, waehrend der
 * Status noch „in Ordnung" sagt.
 */
function speicher_ton(int $prozent, array $schwellen): string
{
    if (!$schwellen) { return 'blau'; }
    if ($prozent >= (int)max($schwellen)) { return 'rot'; }
    if ($prozent >= (int)min($schwellen)) { return 'orange'; }
    return 'blau';
}

/* ---------------------------------------------------------------------------
 * WARNMAIL DER KONTINGENTE (P5a/AP2, E-P5a-11)
 * ---------------------------------------------------------------------------
 *
 * ZWEI BEFUNDE AUF EINMAL, UND DER ZWEITE IST DER UNANGENEHMERE.
 *
 * 1. Das DB-Kontingent bekommt eine Warnung — das verlangt E-P5a-11. Bis
 *    hierher gab es fuer die Datenbank ueberhaupt keine Grenze: Die
 *    Statusseite nannte ihre Groesse, und das war alles. Wer bei 10 GB
 *    ankommt, merkt es daran, dass der Hoster das Schreiben verweigert.
 *
 * 2. `edbak_schwellen_melden()` — die Warnung fuer die Speichergrenze der
 *    Backups, seit S8 vorhanden — WIRD IM BETRIEB VON NIEMANDEM AUFGERUFEN.
 *    Nachgemessen am 15.09.2026: `grep -rn "schwellen_melden" --include=*.php`
 *    findet die Definition und einen Aufruf in
 *    `tools/wiederherstellungs-probe/probe.php`. Sonst nichts. Die Funktion
 *    ist geschrieben, geprueft und tot — dieselbe Klasse Fehler wie Backlog
 *    Nr. 89 („Dieser Job lief von Web 12.2.0 bis 12.9.2 nie"). Sie wird hier
 *    mitgerufen, statt eine zweite Mechanik danebenzustellen.
 *
 * DIE SCHWELLEN SIND DIESELBEN wie ueberall (`edbak_schwellen()`, Vorgabe
 * 70/90). Eine eigene Schwelle je Kontingent waere die dritte Zahl fuer
 * dieselbe Frage.
 *
 * SIE LAEUFT IM TAEGLICHEN AUFRAEUMJOB, direkt nach der Messung — vorher
 * stuenden dort die Zahlen von gestern.
 */

/**
 * Die Kontingente gegen die Schwellen halten und, wo noetig, melden.
 *
 * @return array{gemeldet: list<string>, hinweis: list<string>, fehler: list<string>}
 */
function speicher_kontingente_melden(): array
{
    $aus = ['gemeldet' => [], 'hinweis' => [], 'fehler' => []];
    $schwellen = edbak_schwellen();
    if (!$schwellen) { return $aus; }

    $db       = (int)(edbak_marke_lesen(SPEICHER_K_DB) ?? 0);
    $dateien  = (int)(edbak_marke_lesen(SPEICHER_K_DATEIEN) ?? 0);
    if ($db === 0 && $dateien === 0) { return $aus; }   // noch nie gemessen

    $z       = edbak_ablage_zahlen();
    $backups = (int)$z['pakete_bytes'] + (int)$z['komplett_bytes'] + (int)$z['sonstige_bytes'];

    $kontingente = [
        'db' => ['titel' => 'Datenbank', 'ist' => $db,
                 'bezug' => speicher_db_kontingent_bytes(),
                 'rat'   => 'Alte Diensttage archivieren oder das Kontingent beim '
                          . 'Hoster erhöhen. Ist es erreicht, verweigert die '
                          . 'Datenbank das Schreiben — und dann geht nichts mehr '
                          . 'herein, auch nicht von der Uhr.'],
        'webspace' => ['titel' => 'Webspace', 'ist' => $db + $dateien + $backups,
                 'bezug' => speicher_webspace_bytes(),
                 'rat'   => 'Alte Komplett-Stände entfernen, die Aufbewahrung '
                          . 'senken oder den Tarif wechseln.'],
    ];

    /* Was schon gemeldet ist — je Kontingent eine Liste von Schwellen. */
    $roh = json_decode((string)(edbak_marke_lesen(SPEICHER_K_GEMELDET) ?? ''), true);
    $gemeldet = is_array($roh) ? $roh : [];

    $ziele = null;   // erst holen, wenn wirklich etwas hinausgeht
    $neu   = $gemeldet;

    foreach ($kontingente as $k => $c) {
        if ((int)$c['bezug'] <= 0) { continue; }         // keine Angabe, keine Warnung
        /* ABGERUNDET: Der Wert loest die Schwelle aus — die Warnmail soll
         * erst kommen, wenn die Schwelle wirklich erreicht ist. */
        $proz  = prozent_wert($c['ist'], $c['bezug'], 'ab');
        $alt   = array_map('intval', (array)($gemeldet[$k] ?? []));
        /* UNTERSCHRITTENE SCHWELLEN VERGESSEN — sonst waere die Warnung ein
         * einmaliges Ereignis im Leben einer Installation. */
        $bleibt = array_values(array_filter($alt, static fn(int $s): bool => $proz >= $s));
        $offen  = [];
        foreach ($schwellen as $s) {
            if ($proz >= $s && !in_array($s, $alt, true)) { $offen[] = $s; }
        }
        if (!$offen) { $neu[$k] = $bleibt; continue; }

        require_once __DIR__ . '/smtp.php';
        if (!smtp_eingerichtet()) {
            $aus['hinweis'][] = $c['titel'] . ' ' . $proz . ' %';
            $neu[$k] = $bleibt;
            continue;
        }
        if ($ziele === null) { $ziele = mail_betriebsziele(); }
        foreach ($offen as $s) {
            /* `wartet` ZAEHLT ALS ERLEDIGT. Die Marke unten entscheidet, ob
             * diese Schwelle je wieder gemeldet wird. Bei „liegt in der
             * Warteschlange" die Marke NICHT zu setzen hiesse: Der naechste
             * taegliche Lauf reiht dieselbe Warnung erneut ein, und eine
             * dreitaegige Mailstoerung ergaebe sie dreifach. Nur `abgelehnt`
             * — gar nicht erst eingereiht — laesst die Schwelle offen. */
            $ok = false;
            foreach ($ziele as $m) {
                if (mail_einreihen('speicher_kontingent', $m, [
                        'titel'      => $c['titel'],
                        'prozent'    => $s,
                        'belegt'     => groesse_text($c['ist']),
                        'kontingent' => groesse_text((int)$c['bezug']),
                        'rat'        => $c['rat'],
                    ]) !== MAIL_ABGELEHNT) {
                    $ok = true;
                }
            }
            if ($ok) { $bleibt[] = $s; $aus['gemeldet'][] = $c['titel'] . ' ' . $s . ' %'; }
            else     { $aus['fehler'][] = $c['titel'] . ' ' . $s . ' %'; }
        }
        sort($bleibt);
        $neu[$k] = array_values(array_unique($bleibt));
    }

    if ($neu !== $gemeldet) {
        edbak_marke_setzen(SPEICHER_K_GEMELDET, (string)json_encode($neu));
    }
    return $aus;
}

