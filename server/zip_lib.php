<?php
declare(strict_types=1);

/**
 * ZIP — die eine Stelle, an der diese Anwendung ein Archiv öffnet oder baut
 * ===========================================================================
 *
 *     zip_verfuegbar()                           // ext/zip da?
 *     zip_bauen($ziel, ['teil.json' => $pfad])   // true | Fehlertext
 *     zip_eintrag($pfad, 'manifest.json')        // Inhalt | null
 *     zip_lesen($pfad, fn(callable $eintrag) …)  // mehrere Einträge, einmal geöffnet
 *     zip_oeffnen($pfad)                         // ZipArchive zum Lesen | null
 *
 * WARUM EINE EIGENE DATEI (P5c/AP2, E-P5c-57, R83). `new ZipArchive` stand
 * viermal in `adminbackup_lib.php`, jedes Mal mit eigener Prüfung auf die
 * Erweiterung und eigenem Umgang mit `open() !== true`. Das Archiv des
 * Protokolls wäre die fünfte Stelle gewesen. Das Register zählt `new
 * ZipArchive` außerhalb dieser Datei (Art `muster` — die Art `aufruf` sieht
 * `new X(` nicht, F-P5c-55) und hält die Zahl auf null.
 *
 * UNGEPACKT IST DIE VORGABE. Was hier hineingeht, ist versiegelt
 * (`sk_versiegeln()`, Kontopakete der Fassung 3, Archive des Protokolls) —
 * Chiffrat ist Zufallsrauschen, ein Packlauf darüber kostet Zeit und macht
 * die Datei minimal größer. Wer Klartext ablegt, sagt `packen: true`.
 *
 * ÜBER DATEIEN, NICHT ÜBER `addFromString()`. `ZipArchive` hält, was mit
 * `addFromString()` kommt, bis `close()` im Speicher; bei einem Kontopaket
 * sind das mehrere Teile zu je einigen MB gegen ein Budget von 64
 * (S2/AP6, gemessen). `zip_bauen()` nimmt deshalb Pfade, keine Inhalte.
 */

/** Ist die Erweiterung da? Ohne sie gibt es weder Backups noch Archive. */
function zip_verfuegbar(): bool
{
    return class_exists('ZipArchive');
}

/**
 * Ein Archiv aus Dateien bauen.
 *
 * @param string               $ziel   Pfad der entstehenden Datei; eine
 *                                     vorhandene wird ersetzt
 * @param array<string,string> $teile  Name im Archiv => Pfad auf der Platte
 * @param bool                 $packen false = gespeichert (CM_STORE)
 * @return true|string true, oder ein Satz, der sagt, was scheiterte. Bei
 *         einem Fehlschlag ist `$ziel` entfernt — halbe Archive bleiben
 *         nicht liegen.
 */
function zip_bauen(string $ziel, array $teile, bool $packen = false): bool|string
{
    if (!zip_verfuegbar()) {
        return 'Der PHP-Erweiterung „zip" fehlt (ext/zip).';
    }
    $zip = new ZipArchive();
    if ($zip->open($ziel, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
        return 'Die Datei lässt sich nicht anlegen.';
    }
    foreach ($teile as $name => $pfad) {
        if (!$zip->addFile($pfad, (string)$name)) {
            @$zip->close();
            @unlink($ziel);
            return 'Ein Teil ließ sich nicht in das Archiv legen.';
        }
        /* ERST HINZUFÜGEN, DANN DAS VERFAHREN SETZEN: `setCompressionName()`
         * greift auf einen Eintrag, den es schon gibt. Umgekehrt tut der
         * Aufruf nichts und meldet auch nichts (gemessen in S10/AP4). */
        if (!$packen) {
            $zip->setCompressionName((string)$name, ZipArchive::CM_STORE);
        }
    }
    if (!$zip->close()) {
        @unlink($ziel);
        return 'Das Archiv ließ sich nicht abschließen.';
    }
    return true;
}

/**
 * Einen Eintrag lesen. `null`, wenn es die Datei, die Erweiterung oder den
 * Eintrag nicht gibt — die Aufrufer unterscheiden diese Fälle nicht, und
 * eine Ausnahme dafür wäre Lärm.
 */
function zip_eintrag(string $pfad, string $name): ?string
{
    $inhalt = null;
    zip_lesen($pfad, static function (callable $eintrag) use ($name, &$inhalt): void {
        $inhalt = $eintrag($name);
    });
    return $inhalt;
}

/**
 * Ein Archiv zum Lesen öffnen — für Aufrufer, die mehr brauchen als
 * Einträge nach Namen (`locateName()`, mehrere Ausstiege mit `close()`).
 * `null`, wenn die Erweiterung fehlt oder die Datei kein lesbares Archiv ist.
 *
 * WARUM ES DAS GIBT, obwohl `zip_lesen()` daneben steht: Die Einspielung
 * eines Kontopakets (`edbak_paket_einspielen()`) prüft das Manifest, zählt
 * fehlende Teile, liest je Teil und steigt an sieben Stellen aus. Sie auf
 * einen Rückruf umzubauen hieße, den heikelsten Weg der Sicherung für eine
 * Stilfrage anzufassen. Die eine Stelle ist trotzdem erreicht: Wer ein Archiv
 * öffnet, öffnet es hier.
 */
function zip_oeffnen(string $pfad): ?ZipArchive
{
    if (!zip_verfuegbar() || !is_file($pfad)) { return null; }
    $zip = new ZipArchive();
    return $zip->open($pfad, ZipArchive::RDONLY) === true ? $zip : null;
}

/**
 * Ein Archiv einmal öffnen und mehrere Einträge lesen.
 *
 * `$arbeit` bekommt eine Funktion `fn(string $name): ?string`. Das Archiv ist
 * nach der Rückkehr geschlossen, auch wenn `$arbeit` wirft.
 *
 * @return bool false, wenn sich das Archiv nicht öffnen ließ — dann lief
 *         `$arbeit` nicht.
 */
function zip_lesen(string $pfad, callable $arbeit): bool
{
    $zip = zip_oeffnen($pfad);
    if ($zip === null) { return false; }
    try {
        $arbeit(static function (string $name) use ($zip): ?string {
            $roh = $zip->getFromName($name);
            return $roh === false ? null : $roh;
        });
    } finally {
        $zip->close();
    }
    return true;
}

/** Die Namen der Einträge — für ein Archiv, dessen Teile nicht fest sind. */
function zip_namen(string $pfad): ?array
{
    $zip = zip_oeffnen($pfad);
    if ($zip === null) { return null; }
    $namen = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $n = $zip->getNameIndex($i);
        if ($n !== false) { $namen[] = $n; }
    }
    $zip->close();
    return $namen;
}
