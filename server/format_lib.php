<?php
declare(strict_types=1);

/**
 * FORMAT — die eine Stelle, an der aus einem Wert ein Text fuer Menschen wird
 * ===========================================================================
 *
 * Entstanden in Schritt 15 AP7 (Zentralisierung, R83, E-ZE-23; F-ZE-1, F-ZE-3).
 *
 * WAS HIER STEHT: Groesse, Zahl, Anteil, Datum, Uhrzeit, relative Zeit, die
 * ISO-UTC-Marke und „heute". Also alles, was eine Zahl oder einen Zeitpunkt in
 * lesbaren Text verwandelt — und das Gegenstueck dazu, wo eines gebraucht wird.
 *
 * WAS HIER NICHT STEHT: Alles, was Text in einen Wert zurueckliest, um damit zu
 * RECHNEN. `local_to_utc()` steht weiter in `db.php` neben den uebrigen
 * Eingangsrechnungen; `iso_utc_lesen()` unten ist die eine Ausnahme, weil sie
 * das unmittelbare Gegenstueck zu `iso_utc()` ist und die beiden sonst wieder
 * auseinanderlaufen.
 *
 * WARUM ES SIE GIBT — der Befund, nicht die Absicht. Vor diesem Paket lagen
 * hier zwoelf Sachen an einunddreissig Stellen:
 *
 *   Bytes         DREI Fassungen. `edbak_groesse_text()` in
 *                 `adminbackup_lib.php` (42 Aufrufe in 10 Dateien),
 *                 `apk_groesse()` in `apk_lib.php` (1 Aufruf) und
 *                 `plattform_groesse()` in `plattform_lib.php` (7 Aufrufe).
 *                 Die dritte traegt im Kopf den Satz „dieselbe Schreibweise
 *                 wie edbak_groesse_text()" — und das stimmt nicht: vierte
 *                 Stufe „B", abgeschnittene Nachkommanullen, GB mit einer
 *                 statt zwei Stellen. Dieselben Bytes sahen je nach Seite
 *                 anders aus.
 *   Zahl          EINE Fassung (`stat_zahl()`), und die lag in einer SEITE.
 *                 Ein zweiter Verbraucher konnte sie gar nicht erreichen.
 *   Anteil        KEINE Fassung. Zehn Handrechnungen in fuenf Dateien, mit
 *                 drei verschiedenen Rundungen und fuenf Bauarten fuer den
 *                 Fall „keine Bezugsgroesse".
 *   relative Zeit ZWEI Fassungen, und sie sind auseinandergelaufen: 1 890
 *                 Sekundenwerte liefern verschiedenen Text (Einzelheiten bei
 *                 `zeit_relativ()`).
 *
 * WAS SIE LAEDT: nur `konfig_lib.php` (E-ZE-11). Das ist keine Sparsamkeit,
 * sondern Bedingung — `plattform_lib.php` wird von `install.php` geladen,
 * BEVOR es eine `config.php` gibt, und `smtp.php` laeuft im selben Weg. Wer
 * hier eine Fachbibliothek einbindet, nimmt dem Einrichter die Grundlage.
 *
 * DIE NAMEN TRAGEN KEIN PRAEFIX — `groesse_text()`, nicht `fmt_groesse_text()`.
 * Das folgt `konfig_lib.php` (`konfig()`) und nicht `mission_fields_lib.php`
 * (`mf_` davor): Ein Praefix ordnet einer Herkunft zu, und diese Funktionen sollen
 * keine Herkunft haben, sondern die eine Schreibweise des Hauses sein.
 *
 * DIE JS-SEITE HEISST GENAUSO. `server/assets/format.js` fuehrt `EdFormat`
 * mit denselben Regeln fuer den Browser (AP8 baut sie aus). Wer hier etwas
 * aendert, aendert es dort mit — sonst zeigt dieselbe Zahl je nach Weg zwei
 * Schreibweisen, und genau davon handelt dieses Paket.
 */

require_once __DIR__ . '/konfig_lib.php';

/* ====================================================================== *
 * ZEITPUNKT -> TEXT                                                      *
 * ====================================================================== */

/**
 * UTC-Zeitpunkt in Ortszeit (`app.timezone`), nach `$format`.
 *
 * STAND BIS WEB 20.31.0 IN `db.php` und ist hierher gezogen — mit 113
 * Aufrufen in 37 Dateien der meistbenutzte Formatierer des Projekts, und
 * `datum_text()` und `datum_zeit_text()` unten bauen auf ihm auf. Haette er
 * in `db.php` bleiben sollen, muesste diese Datei die Datenbankdatei laden
 * (oder die Rechnung ein zweites Mal fuehren) — beides waere das Gegenteil
 * dessen, wofuer es sie gibt.
 *
 * DER NAME BLEIBT, und zwar ausdruecklich: 113 Aufrufstellen umzubenennen
 * waere eine Aenderung an 37 Dateien ohne jeden Gewinn. `db.php` laedt diese
 * Datei, also findet jeder bisherige Aufrufer die Funktion unveraendert.
 *
 * `–` (Halbgeviertstrich) fuer null und leer: Das ist die Schreibweise des
 * Hauses fuer „es gibt hier nichts", nicht fuer „0".
 */
function fmt_local(?string $utc, string $format = 'H:i'): string {
    if ($utc === null || $utc === '') return '–';
    $dt = new DateTime($utc, new DateTimeZone('UTC'));
    $dt->setTimezone(new DateTimeZone((string)konfig('app.timezone')));
    return $dt->format($format);
}

/** Datum allein: „22.09.2026". */
function datum_text(?string $utc): string
{
    return fmt_local($utc, 'd.m.Y');
}

/**
 * Datum und Uhrzeit: „22.09.2026, 14:30".
 *
 * DER TRENNER WIRD ANGEHAENGT, NICHT INS FORMAT GESCHRIEBEN. Die naheliegende
 * Bauform `fmt_local($utc, 'd.m.Y' . $trenner . 'H:i')` geht fuer die drei
 * heutigen Trenner zufaellig gut, weil keiner einen Buchstaben enthaelt. Der
 * erste Trenner mit einem Buchstaben wird still zu Formatzeichen: aus
 * „ um " wuerde `u` (Mikrosekunden) und `m` (Monat), also
 * „22.09.2026 0000009 30:14" statt „22.09.2026 um 14:30". Und so ein Trenner
 * steht schon im Bestand — `einstellungen.php` baut „ um " aus zwei Aufrufen.
 *
 * DER TRENNER IST DAS KOMMA (P5c/AP9, E-P5c-37, Nr. 253): „TT.MM.JJJJ,
 * HH:MM", wie in den freigegebenen Mockups und auf dem Schluesselblatt.
 * Bis Web 21.0.0 liefen drei Trenner um — ein Leerzeichen (Vorgabe), ein
 * Mittelpunkt mit Leerzeichen (11 Aufrufer) und ein Komma (8) —, und
 * dieselbe Zeit stand auf zwei Seiten verschieden da. Schritt 15 hat sie
 * benannt, AP9 hat die Vorgabe umgestellt und das zweite Argument bei allen
 * 19 Aufrufern gestrichen. ZWEI BENANNTE AUSNAHMEN: „ um " in den
 * Mailtexten (`konto_lib.php`, `einstellungen.php`) — ein Satz, keine
 * Zeitangabe —, und das Leerzeichen im GPX-Spurnamen (`gpx.php`,
 * E-P5c-129): Der steht in der Datei, `Export-Format.md` legt ihn fest, und
 * `export.js` baut ihn im Browser gleich. Der Parameter bleibt dafuer; wer
 * ihn fuer etwas anderes braucht, fragt zuerst, ob es wirklich eine andere
 * Schreibweise sein soll.
 */
function datum_zeit_text(?string $utc, string $trenner = ', '): string
{
    if ($utc === null || $utc === '') { return fmt_local($utc); }
    return fmt_local($utc, 'd.m.Y') . $trenner . fmt_local($utc, 'H:i');
}

/**
 * Ein Zeitraum: „13.09. – 19.09.2026", über einen Jahreswechsel
 * „28.12.2026 – 03.01.2027" (P5c/AP2, Archiv des Protokolls). Beide Enden
 * in Ortszeit und EINSCHLIESSLICH — wer „bis 19.09." liest, meint den
 * ganzen 19.
 */
function zeitraum_text(?string $vonUtc, ?string $bisUtc): string
{
    $von = fmt_local($vonUtc, 'd.m.Y');
    $bis = fmt_local($bisUtc, 'd.m.Y');
    if ($von === $bis) { return $von; }
    return substr($von, 6) === substr($bis, 6)
        ? substr($von, 0, 6) . ' – ' . $bis
        : $von . ' – ' . $bis;
}

/** Datum und volle Stunde: „22.09.2026 14". Ein Einzelfall der Statusseite. */
function datum_stunde_text(?string $utc): string
{
    return fmt_local($utc, 'd.m.Y H');
}

/**
 * Alter als Text: „gerade eben", „vor 12 Minuten", „vor 3 Stunden".
 *
 * HIESS BIS WEB 20.31.0 `status_alter()` und lag in `status_lib.php`. Daneben
 * stand eine ZWEITE Fassung als Inline-Ternaer in `betrieb_updates.php` — und
 * die beiden waren auseinandergelaufen. Lueckenlos nachgerechnet ueber
 * 0 bis 200 000 Sekunden: **1 890 Sekundenwerte** lieferten verschiedenen
 * Text, in genau zwei zusammenhaengenden Baendern:
 *
 *   0 bis 89 s        „gerade eben" hier, „vor 1 Minuten" dort (die Kopie
 *                     kannte die 90-Sekunden-Schwelle nicht und erzwang mit
 *                     `max(1, x)` die Eins).
 *   3 600 bis 5 399 s „vor 60 bis 90 Minuten" hier, „vor 1 Stunden" dort (die
 *                     Kopie wechselte bei 3 600 s auf Stunden, diese Fassung
 *                     erst bei 5 400 s).
 *
 * Ausserhalb dieser beiden Baender: null Abweichungen. Diese Fassung gilt
 * (E-ZE-23) — die Umstellung ist damit eine der fuenf namentlich benannten
 * sichtbaren Folgen des ganzen Schritts.
 *
 * DIE DRITTE FOLGE, die dabei mitkommt: Die Kopie fing eine unlesbare
 * Zeitmarke nicht ab (`strtotime()` liefert `false`, `time() - false` ist
 * `time()`, angezeigt wurde „vor 20 718 Tagen" — waechst taeglich um eins).
 * Hier steht „unbekannt". Ausloesbar ist der Weg heute nicht.
 *
 * ES NIMMT AUCH DIE MySQL-FORM. Von den Aufrufern fuehren zwei ein
 * `DATETIME` ('2026-09-22 10:00:00') statt einer ISO-Marke; `iso_utc_lesen()`
 * liest beide. Eine strengere Pruefung liesse die Pausenzeile der
 * Statusseite stumm ausfallen.
 */
function zeit_relativ(?string $utc): string
{
    if ($utc === null || $utc === '') { return 'nie'; }
    $t = iso_utc_lesen($utc);
    if ($t === null) { return 'unbekannt'; }
    $s = time() - $t;
    if ($s < 90)     { return 'gerade eben'; }
    if ($s < 5400)   { return 'vor ' . (int)round($s / 60) . ' Minuten'; }
    if ($s < 172800) { return 'vor ' . (int)round($s / 3600) . ' Stunden'; }
    return 'vor ' . (int)round($s / 86400) . ' Tagen';
}

/**
 * Der heutige Kalendertag in der APP-Zeitzone, als 'Y-m-d'.
 *
 * WARUM ES DIE FUNKTION GIBT (FF-1, F-ZE-1): `date('Y-m-d')` nimmt die
 * Zeitzone der `php.ini`, nicht `app.timezone`. Auf einem Server in UTC ist
 * zwischen 0 und 2 Uhr Ortszeit „heute" damit GESTERN — und genau dieser Wert
 * belegt das Datumsfeld eines neuen Diensttags vor und begrenzt es als `max`.
 * Das ist eine der fuenf namentlich benannten sichtbaren Folgen (E-ZE-10).
 *
 * OHNE FORMAT-PARAMETER, und das mit Absicht: Alle Verbraucher wollen
 * 'Y-m-d'. Ein optionaler Formatparameter machte daraus einen allgemeinen
 * `date()`-Ersatz — dann landeten hier Aufrufe, die kein „heute" sind, und
 * die Zaehlzeile Z27 stuende auf 0, ohne dass die Sache eine Stelle haette.
 *
 * Der Rueckfall 'Europe/Berlin' folgt dem, was `migration_lib.php` und
 * `tageszuordnung_lib.php` heute schon schreiben; ohne ihn wuerfe ein leerer
 * Konfigurationswert mitten im Seitenaufbau.
 */
function heute_lokal(): string
{
    $zone = (string)konfig('app.timezone', 'Europe/Berlin');
    return (new DateTimeImmutable('now', new DateTimeZone($zone)))->format('Y-m-d');
}

/* ====================================================================== *
 * DIE ISO-UTC-MARKE — schreiben und lesen                                *
 * ====================================================================== */

/**
 * Die ISO-UTC-Marke: '2026-09-22T10:00:00Z'.
 *
 * IMMER MIT 'Z', NIE MIT ZONENVERSATZ. `gmdate('c')` schriebe '+00:00' und
 * veraenderte damit jedes bestehende Sicherungs- und GPX-Byte. Die Marke
 * steht in `docs/Backup-Format.md`, `docs/Export-Format.md` und in
 * `wartung.lock`, das ueber `jobs.php` an die Auslieferungskette geht.
 *
 * DER BINDESTRICH-ZWILLING GEHOERT NICHT HIERHER. `gmdate('Y-m-d\TH-i-s\Z')`
 * (mit Bindestrichen) baut DATEINAMEN in `adminbackup_lib.php` und
 * `komplett_lib.php`. Wer ihn beim Aufraeumen „mitnimmt", aendert Dateinamen
 * — und `komp_zeit_aus_name()` liest die Zeit des juengsten Komplett-Backups
 * aus genau diesem Namen.
 */
function iso_utc(?int $zeitstempel = null): string
{
    return gmdate('Y-m-d\TH:i:s\Z', $zeitstempel ?? time());
}

/**
 * Die ISO-UTC-Marke als Unix-Zeitstempel. Gegenstueck zu `iso_utc()`.
 *
 * NIMMT AUCH DIE MySQL-FORM ('2026-09-22 10:00:00'), weil zwei Verbraucher
 * genau die fuehren (`jobs_pause_bis()` und `jobs.letzter_lauf`). Eine
 * Fassung, die nur die T/Z-Form naehme, liesse die Pausenzeile der
 * Statusseite ausfallen und den Zaehler „Ausloeser" dauerhaft auf Rot stehen
 * — ohne Fehlermeldung.
 *
 * KEINE FORMPRUEFUNG, und das ist eine Entscheidung. Ein vorgeschaltetes
 * `preg_match` waere strenger als das, was heute in den Dateien STEHT:
 * `konto.json`, `app_state` und `wartung.lock` tragen Altbestand, der mit
 * demselben Leser gelesen wird. Eine engere Annahme wirkte rueckwirkend auf
 * Dateien, die niemand mehr neu schreibt.
 *
 * `null` heisst leer, fehlend oder unlesbar — drei Faelle, eine Antwort. Wer
 * sie unterscheiden muss, prueft den Eingabewert selbst.
 */
function iso_utc_lesen(?string $iso): ?int
{
    if ($iso === null || $iso === '') { return null; }
    $t = strtotime(str_replace(['T', 'Z'], [' ', ''], $iso) . ' UTC');
    return $t === false ? null : $t;
}

/* ====================================================================== *
 * ZAHL -> TEXT                                                           *
 * ====================================================================== */

/**
 * Zahl in deutscher Schreibweise: Komma dezimal, Punkt fuer die Tausender.
 *
 * OHNE EINHEIT UND OHNE SUFFIX. Nachgezaehlt ueber alle 27 Fundstellen: Kein
 * einziger Aufruf traegt die Einheit im `number_format` — sie haengt immer
 * beim Aufrufer (' GB', ' Zeilen', ' Anweisungen'). Ein Einheitenparameter
 * haette also null Verbraucher und waere eine Verallgemeinerung auf Verdacht.
 *
 * HIESS BIS WEB 20.31.0 `stat_zahl()` und lag in `betrieb_statistik.php` —
 * in einer SEITE, nicht in einer Bibliothek. Ein zweiter Verbraucher konnte
 * sie gar nicht erreichen; die uebrigen 26 Stellen schrieben deshalb
 * `number_format(x, s, ',', '.')` von Hand.
 */
function zahl_text(int|float $n, int $stellen = 0): string
{
    return number_format((float)$n, $stellen, ',', '.');
}

/**
 * Dateigrösse: KB unter einem Megabyte, MB darüber — und GB ab einem Gigabyte.
 *
 * DIE DRITTE STUFE KAM MIT DER SPEICHERGRENZE (S2/AP6). Sie wird in GB
 * angegeben (Vorgabe 2 GB); ohne diese Stufe hätte die Meldung „Die
 * Speichergrenze ist erreicht (2.048,0 MB von 2.048,0 MB)" gelautet — dieselbe
 * Zahl, die daneben als „2 GB" eingestellt wird, in einer anderen Einheit.
 *
 * HIESS BIS WEB 20.31.0 `edbak_groesse_text()` und lag in
 * `adminbackup_lib.php`. Zwei Dateien luden dafuer rund 2 600 Zeilen
 * Backup-Fachbibliothek samt deren Krypto- und Pruefschicht, und eine dritte
 * (`betrieb_sicherheit.php`) rief die Funktion, ohne sie je einzubinden — sie
 * war nur da, weil das Menue `status_lib.php` nachlud. Das ist der Beleg, aus
 * dem R83 entstanden ist.
 *
 * DREI STUFEN MIT DREI VERSCHIEDENEN NACHKOMMASTELLEN, und das ist kein
 * Schreibfehler: GB zwei, MB eine, KB null. Wer hier „vereinheitlicht",
 * aendert Text an 42 Stellen auf einmal — und der Bilderlauf faengt das
 * nicht, er misst Ueberlauf und Konsolenfehler. Dafuer gibt es den
 * Textvergleich (`tools/screenshots/vergleichen.py`).
 */
function groesse_text(int $bytes): string
{
    if ($bytes >= 1024 * 1024 * 1024) {
        return zahl_text($bytes / (1024 * 1024 * 1024), 2) . ' GB';
    }
    return $bytes < 1024 * 1024
        ? zahl_text($bytes / 1024, 0) . ' KB'
        : zahl_text($bytes / (1024 * 1024), 1) . ' MB';
}

/**
 * Groesse mit abgeschnittenen Nachkommanullen und der Stufe „B":
 * „2 GB", „1,5 MB", „512 B", „unbegrenzt".
 *
 * HIESS BIS WEB 20.31.0 `plattform_groesse()` und lag in
 * `plattform_lib.php`. Ihr Kopfkommentar behauptete „dieselbe Schreibweise
 * wie `edbak_groesse_text()`" — DAS STIMMTE NIE. Nachgemessen: vierte Stufe
 * „B", abgeschnittene Nachkommanullen, GB mit EINER statt zwei Stellen. Aus
 * 2 GB wird hier „2 GB" und dort „2,00 GB"; dieselben Bytes sahen je nach
 * Seite anders aus.
 *
 * SIE BLEIBT TROTZDEM EINE ZWEITE FUNKTION, und das ist kein Rueckfall in den
 * alten Zustand: Sie ist eine ANDERE Schreibweise, nicht eine zweite Fassung
 * derselben. Die Plattformseite stellt Grenzwerte gegenueber („>= 128 MB"),
 * und dort ist „2 GB" richtig und „2,00 GB" Laerm. Was R83 verbietet, ist
 * dieselbe Sache an zwei Stellen — nicht zwei Sachen an zwei Stellen. Der
 * Unterschied steht jetzt im Kommentar statt in einer falschen Behauptung.
 */
function groesse_kurz_text(int $b): string
{
    if ($b >= PHP_INT_MAX) { return 'unbegrenzt'; }
    foreach ([['GB', 1073741824], ['MB', 1048576], ['KB', 1024]] as [$e, $t]) {
        if ($b >= $t) { return rtrim(rtrim(zahl_text($b / $t, 1), '0'), ',') . ' ' . $e; }
    }
    return $b . ' B';
}

/**
 * Fuellstand gegen eine Grenze, mit EINER gemeinsamen Einheit:
 * „3 von 250 MB".
 *
 * VIERMAL WORTGLEICH IM BESTAND gewesen — Verwaltung (`admin_user.php`),
 * Kontoseite (`einstellungen.php`), Warnmail (`jobs_lib.php`) und die
 * 507-Antwort an das Geraet (`ingest.php`). Vier Ausgabewege, ein Satz.
 *
 * DIE GEMEINSAME EINHEIT IST ENTSCHIEDEN, NICHT GEERBT (Auftraggeber,
 * 22.09.2026). Der andere Weg waere `groesse_text()` je Wert gewesen — aus
 * „0 von 250 MB" wuerde dann „312 KB von 250,0 MB". Das ist genauer, aber es
 * ist eine sichtbare Textaenderung an vier Stellen UND im Antworttext an das
 * Geraet, und die Fuenferliste der benannten Ausnahmen (E-ZE-10) waechst
 * dafuer nicht.
 *
 * `(int)round(x / 1048576)` wortgetreu wie an allen vier Stellen: Der
 * Grenzwert entsteht als `$g['mb'] * 1024 * 1024` und ist per Bauart ein
 * glattes Vielfaches von 1 MiB.
 */
function groesse_paar_text(int $ist, int $grenze): string
{
    return (int)round($ist / 1048576) . ' von '
         . (int)round($grenze / 1048576) . ' MB';
}

/* ====================================================================== *
 * ANTEIL                                                                 *
 * ====================================================================== */

/**
 * Anteil in Prozent als ganze Zahl. Ohne Bezugsgroesse: 0.
 *
 * ZWEI FUNKTIONEN UND NICHT EINE, und der Grund ist gemessen: Von den zehn
 * Handrechnungen im Bestand runden FUENF ab, DREI kaufmaennisch und ZWEI gar
 * nicht. Das ist kein Zufall und kein Schlendrian — abgerundet wird dort, wo
 * der Wert eine SCHWELLE ausloest (die Warnung soll erst kommen, wenn die
 * Schwelle wirklich erreicht ist), kaufmaennisch dort, wo er nur gelesen
 * wird. Eine Funktion ohne diesen Schalter verschoebe den Ausloesezeitpunkt
 * der Speicher-Warnmail um bis zu einen Prozentpunkt, und das waere eine
 * sechste Ausnahme von E-ZE-10.
 *
 * NICHT AUF 100 GEDECKELT: Ein uebervolles Kontingent soll 104 melden und
 * nicht 100. Wer deckeln will, tut es beim Aufrufer — `betrieb_server.php`
 * macht das fuer die Balkenbreite mit `min(100, x)`.
 */
function prozent_wert(int|float $teil, int|float $ganz, string $rundung = 'ab'): int
{
    if ($ganz <= 0) { return 0; }
    /* Reihenfolge wortgetreu wie im Bestand: erst mal 100, dann geteilt.
     * `($teil / $ganz) * 100` entginge zwar der Zaehlzeile Z23, aendert aber
     * die Reihenfolge der Gleitkommaoperationen — bei `floor` kann das an
     * einer glatten Grenze kippen, und dann faellt eine Warnmail aus. Ein
     * Muster zu umgehen statt zu zentralisieren waere ausserdem genau das,
     * wogegen das Register steht (E-ZE-24). */
    $r = $teil * 100 / $ganz;
    return (int)($rundung === 'kauf' ? round($r) : floor($r));
}

/**
 * Anteil als Text: „42 %" — mit einfachem Leerzeichen vor dem Zeichen.
 *
 * OHNE BEZUGSGROESSE GIBT ES `$leer`, Vorgabe die leere Zeichenkette. Das ist
 * nicht Geschmack, sondern gemessen: Der Bildschirm laesst den Anteil weg,
 * wenn es keinen gibt („— Ingest gesperrt" liest sich wie ein abgeschnittener
 * Satz), die CSV-Zelle daneben muss aber 0 schreiben, weil eine leere Zelle
 * in einer Tabelle etwas anderes heisst. Fuer die CSV ist deshalb
 * `prozent_wert()` richtig und nicht diese Funktion.
 *
 * RUNDET KAUFMAENNISCH, damit die Prozentzahl zu der Zahl daneben passt.
 * Hiess bis Web 20.31.0 `stat_anteil()` und lag in `betrieb_statistik.php`.
 */
function prozent_text(int|float $teil, int|float $ganz, string $leer = ''): string
{
    if ($ganz <= 0) { return $leer; }
    return (string)prozent_wert($teil, $ganz, 'kauf') . ' %';
}
