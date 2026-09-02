<?php
declare(strict_types=1);

/**
 * Die APK-Dateien der Android-Apps (S4/A1, E-S4-16).
 *
 * WO SIE LIEGEN UND WARUM DORT. In `server/apk/`, und zwar NUR auf dem
 * Server: Das Verzeichnis steht in `.gitignore` UND in der Ausnahmeliste des
 * Deploys — dasselbe Muster wie `config.php` und `sicherungen/`. Ein
 * signiertes APK ist ein Erzeugnis und kein Quelltext; eingecheckt waere es
 * bei jeder Fassung ein zweistelliges MB im Verlauf, und die Signatur
 * gehoerte damit in dieselbe Ablage wie der Quelltext, den sie beglaubigt.
 * Hochgeladen wird per FTPS durch die Betreiberin.
 *
 * WAS DIESE DATEI TUT: Sie liest, was dort liegt. Nichts wird von Hand
 * gepflegt — Name, Groesse, Datum und Pruefsumme kommen aus der Datei selbst.
 * Eine Versionsangabe, die jemand eintippt, stimmt am Tag des Eintippens und
 * danach nie wieder.
 */

/** Wo die Dateien liegen. */
function apk_ordner(): string { return __DIR__ . '/apk'; }

/**
 * Was im Ordner liegt, neueste zuerst.
 *
 * NUR `*.apk`, UND DER NAME WIRD GEPRUEFT. Ein Verzeichnis, in das jemand per
 * FTP schreibt, ist kein vertrauenswuerdiger Eingang: Ein Dateiname mit `..`
 * oder einem Schraegstrich waere ein Weg aus dem Ordner heraus, und einer mit
 * einem Zeilenumbruch ginge durch eine HTTP-Kopfzeile. Was nicht auf das
 * Muster passt, wird uebergangen — still, denn es ist keine Fehlermeldung
 * wert, wenn dort eine `.DS_Store` liegt.
 *
 * DIE PRUEFSUMME WIRD BEI JEDEM AUFRUF GERECHNET. Bei 7 MB kostet das
 * wenige Millisekunden, und ein zwischengespeicherter Wert waere genau die
 * Zahl, die nach einem Austausch der Datei noch die alte nennt — bei einer
 * Pruefsumme ist das schlimmer als keine.
 *
 * @return list<array{datei:string,groesse:int,stand:int,sha256:string,version:?string}>
 */
function apk_liste(): array
{
    $ordner = apk_ordner();
    if (!is_dir($ordner)) { return []; }

    $aus = [];
    foreach ((array)scandir($ordner) as $name) {
        if (!is_string($name) || !preg_match('/^[A-Za-z0-9._-]+\.apk$/', $name)) { continue; }
        $pfad = $ordner . '/' . $name;
        if (!is_file($pfad)) { continue; }
        $aus[] = [
            'datei'   => $name,
            'groesse' => (int)filesize($pfad),
            'stand'   => (int)filemtime($pfad),
            'sha256'  => hash_file('sha256', $pfad),
            /* Die Version aus dem Dateinamen, wenn er eine traegt
             * (`nadoku-1.0.0.apk`). Sie AUS DEM APK zu lesen hiesse, ein
             * ZIP zu oeffnen und das Android-Binaer-XML des Manifests zu
             * entschluesseln — dafuer gaebe es keine Bibliothek im Haus, und
             * eine neue Abhaengigkeit fuer eine Anzeige waere der falsche
             * Preis (CLAUDE.md 4). Steht keine drin, steht keine da. */
            'version' => preg_match('/-(\d+\.\d+\.\d+)\.apk$/', $name, $t) ? $t[1] : null,
        ];
    }
    usort($aus, static fn($a, $b) => $b['stand'] <=> $a['stand']);
    return $aus;
}

/** Groesse lesbar — „7,2 MB". */
function apk_groesse(int $bytes): string
{
    return $bytes >= 1048576
        ? number_format($bytes / 1048576, 1, ',', '.') . ' MB'
        : number_format($bytes / 1024, 0, ',', '.') . ' KB';
}

/**
 * Die Pruefsumme in Vierergruppen.
 *
 * 64 Zeichen am Stueck sind nicht vergleichbar — wer nachrechnet, verliert
 * beim dritten Blockwechsel die Stelle. Gruppiert laesst sie sich zeilenweise
 * abgleichen. Der Wert selbst bleibt unveraendert; wer ihn kopiert, bekommt
 * die Leerzeichen mit und muss sie entfernen, und das ist der kleinere Preis.
 */
function apk_sha_lesbar(string $sha): string
{
    return trim(chunk_split($sha, 4, ' '));
}
