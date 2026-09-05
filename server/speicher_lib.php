<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/adminbackup_lib.php';   // Ablagezahlen, Grenze, Schwellen, Marken

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
    edbak_marke_setzen(SPEICHER_K_STAND, gmdate('Y-m-d\TH:i:s\Z'));
    return ['datenbank' => $db, 'dateien' => $datei];
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
            'prozent'  => $grenze > 0 ? (int)floor($backups * 100 / $grenze) : 0,
        ],
        'gesamt'    => [
            'datenbank' => $db,
            'dateien'   => $dateien,
            'konto'     => $konto,
            'komplett'  => $komp,
            'summe'     => $gesamt,
            'bezug'     => $webspace,
            'prozent'   => $webspace > 0 ? (int)floor($gesamt * 100 / $webspace) : 0,
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
