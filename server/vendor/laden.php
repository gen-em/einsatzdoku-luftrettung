<?php
declare(strict_types=1);

/**
 * DER LADER FÜR DIE BEIDEN FREMDEN BIBLIOTHEKEN (S2/AP7).
 *
 * WARUM ES HIER KEINEN COMPOSER GIBT. Die Anwendung wird per FTP auf einen
 * Webspace geschoben; ein `composer install` läuft dort nicht, und ein
 * Composer-Autoloader, der aus einer Datei mit Zeitstempeln und absoluten
 * Pfaden besteht, wäre ein Erzeugnis im Repositorium, das niemand liest.
 * Beide Bibliotheken folgen PSR-4 — dafür genügen zwanzig Zeilen.
 *
 * WAS HIER LIEGT UND WOHER (docs/Lizenzen.md, Abschnitt 3):
 *   phpseclib3/            phpseclib 3.0.57, MIT — SFTP-Adapter
 *   ParagonIE/ConstantTime/ constant_time_encoding 2.7.0, MIT — von
 *                          phpseclib vorausgesetzt (Base64/Hex ohne
 *                          datenabhängige Laufzeit)
 *
 * DIE PRÜFSUMMEN stehen in `phpseclib3.sha256` und
 * `ParagonIE-ConstantTime.sha256`, eine Zeile je Datei. Der Kopfkommentar je
 * Datei, den `docs/Lizenzen.md` sonst verlangt, ist hier durch die Listen
 * ersetzt: Bei 348 Dateien wäre er 348-mal von Hand einzutragen und beim
 * ersten Austausch 348-mal falsch. Nachrechnen:
 *
 *   cd server/vendor && sha256sum -c phpseclib3.sha256
 *
 * AUSTAUSCH BEI EINEM UPDATE: Verzeichnis löschen, neue Fassung hineinlegen,
 * Listen neu erzeugen, Version hier und in `docs/Lizenzen.md` nachziehen.
 * Nicht hineinpatchen.
 */

spl_autoload_register(static function (string $klasse): void {
    static $wurzeln = [
        'phpseclib3\\'             => __DIR__ . '/phpseclib3/',
        'ParagonIE\\ConstantTime\\' => __DIR__ . '/ParagonIE/ConstantTime/',
    ];
    foreach ($wurzeln as $praefix => $ordner) {
        if (!str_starts_with($klasse, $praefix)) { continue; }
        $rest = substr($klasse, strlen($praefix));
        $datei = $ordner . str_replace('\\', '/', $rest) . '.php';
        /* realpath() UND EIN VERGLEICH MIT DEM ORDNER: Der Klassenname kommt
         * in dieser Anwendung zwar immer aus dem eigenen Code, aber ein Lader,
         * der aus einem Namen einen Pfad baut, gehört abgesichert — sonst wäre
         * er die Stelle, an der aus `..\..` irgendwann ein Einbruch wird. */
        $echt = realpath($datei);
        if ($echt !== false && str_starts_with($echt, realpath($ordner) . '/')) {
            require_once $echt;
        }
        return;
    }
});

/* phpseclib bringt eine eigene Startdatei mit (Prüfung auf
 * mbstring.func_overload). Composer würde sie über `autoload.files` einbinden;
 * hier steht sie ausdrücklich. */
require_once __DIR__ . '/phpseclib3/bootstrap.php';
