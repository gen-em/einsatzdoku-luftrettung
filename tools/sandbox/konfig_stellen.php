<?php
declare(strict_types=1);

/**
 * KONFIG STELLEN — eine Lage in `config.php` herstellen und wieder zuruecknehmen
 * ===========================================================================
 *
 *     require_once __DIR__ . '/../../sandbox/konfig_stellen.php';   // aus tools/proben/<name>/
 *
 *     $zurueck = konfig_stellen(['kdf_anteil' => null]);   // Anteil weg
 *     … pruefen …
 *     $zurueck();                                          // Stand wie vorher
 *
 * WOFUER. Fuenf Proben stellen Schluessellagen her, um zu pruefen, was die
 * Anwendung dann tut: ein anderer Serverschluessel, ein unbrauchbarer
 * Eintrag, gar keiner, ein Anteil ohne Marke. Bis Web 20.26.3 ging das ueber
 * die globale `$CFG` — eine Zuweisung, und `serverschluessel(true)` las sie
 * beim naechsten Mal.
 *
 * WARUM ES DIESE DATEI GIBT. Mit Schritt 15 AP2 (Web 20.27.0) ist die
 * globale `$CFG` entfallen; die Anwendung liest ueber `konfig()` aus der
 * Datei. Eine Zuweisung an `$CFG` erreicht seither NIEMANDEN mehr — sie
 * scheitert nicht, sie tut nur nichts. Gemessen am 21.09.2026: **30
 * Erwartungen in fuenf Proben** standen danach auf „nicht erfuellt", und
 * zwar ohne dass die Anwendung einen Fehler gehabt haette.
 *
 * DER WEG IST JETZT DERSELBE WIE IN DER ANWENDUNG: `config.php` schreiben,
 * dann `konfig_verwerfen()`. Genau das tut `config_eintrag_schreiben()` in
 * `serverkrypto_lib.php`. Ein zweiter Weg — etwa eine Hintertuer in
 * `konfig_lib.php`, die nur Proben benutzen — waere ein Weg, den niemand
 * pflegt, und stuende in Produktionscode. `CLAUDE.md` sagt an drei Stellen,
 * dass es das nicht gibt.
 *
 * EINE STELLE, FUENF VERBRAUCHER — dieselbe Ueberlegung wie bei
 * `tools/motor.mjs`: fuenfmal geschrieben waere sie viermal richtig und
 * einmal falsch.
 *
 * DIE TIEFE IST ZWEI, NICHT EINS. Die vier Proben liegen seit PK-04/2 unter
 * `tools/proben/<name>/`, nicht mehr flach unter `tools/`. Der `../`-Zaehler
 * im `require_once` ist deshalb die Stelle, die bei einem Umzug bricht — und
 * zwar STILL, denn git fuehrt eine Umbenennung und eine Aenderung ohne
 * Konflikt zusammen. Drei der vier Aufrufe sind am 22.09.2026 genau so
 * gebrochen: kein Konflikt, kein Hinweis, `require_once` auf eine Datei, die
 * es unter dem gerechneten Pfad nicht gibt. Wer eine Probe verschiebt, zaehlt
 * hier nach; `php -r` mit `realpath()` ueber alle vier ist eine Zeile.
 *
 * WAS SIE NICHT TUT:
 *
 * - **Sie prueft die Werte nicht.** `config_eintrag_schreiben()` laesst nur
 *   `CONFIG_SCHREIBBAR` zu und verlangt 64 Hexzeichen; das ist richtig fuer
 *   die Anwendung und falsch fuer eine Probe, die gerade den unbrauchbaren
 *   Eintrag herstellen will.
 * - **Sie ist nicht nebenlaeufig.** Zwei Proben gleichzeitig gegen dieselbe
 *   Anlage waren noch nie vorgesehen.
 * - **Sie gehoert nicht auf eine Anlage mit Betrieb.** Sie schreibt die
 *   echte `config.php`. Der Rueckweg steht im Rueckgabewert — wer ihn nicht
 *   ruft, laesst die Anlage in der gestellten Lage stehen.
 */

/**
 * Werte in `config.php` setzen (oder mit `null` entfernen) und den Rueckweg
 * zurueckgeben.
 *
 * Nur die OBERSTE Ebene — mehr braucht keine der fuenf Proben, und eine
 * Punktpfad-Schreibweise waere eine Schnittstelle, die gegen einen Fall
 * entworfen ist (R83).
 *
 * @param  array<string,?string> $werte  Schluessel => Wert, `null` entfernt
 * @param  ?string $pfad  Welche `config.php`? Ohne Angabe die der Anlage.
 *         `komplettprobe` laeuft gegen eine KOPIE von `server/` und schreibt
 *         deren eigene Datei — waere der Pfad fest, stellte die Probe die
 *         Lage in der echten Anlage her und maesse sie in der Kopie.
 * @return callable():void               stellt den vorigen Stand wieder her
 */
function konfig_stellen(array $werte, ?string $pfad = null): callable
{
    $pfad = $pfad ?? konfig_stellen_pfad();

    /* DER RUECKWEG LEGT DIE BYTES ZURUECK, NICHT EINEN `var_export`.
     *
     * Die `config.php` des Installers traegt einen Kopfkommentar („Automatisch
     * erzeugt … niemals ins Git-Repo committen … server_key versiegelt die
     * Zugangsdaten der Backup-Ziele"). Ein `var_export` des gelesenen Feldes
     * haette ihn stillschweigend geloescht — gemessen am 21.09.2026 beim
     * ersten Lauf dieser Datei: Der Inhalt stimmte, der Kopf war fort. Auf
     * einer echten Anlage waere das ein Verlust, den niemand bemerkt, bis
     * jemand die Datei aufmacht. */
    $vorherText = is_file($pfad) ? (string)file_get_contents($pfad) : null;

    $neu = konfig_stellen_lesen($pfad);
    foreach ($werte as $k => $v) {
        if ($v === null) { unset($neu[$k]); } else { $neu[$k] = $v; }
    }
    konfig_stellen_schreiben($pfad, $neu);

    $zurueckgelegt = false;
    $zurueck = static function () use ($pfad, $vorherText, &$zurueckgelegt): void {
        if ($zurueckgelegt) { return; }
        $zurueckgelegt = true;
        konfig_stellen_bytes($pfad, $vorherText);
    };

    /* UND EINE EINZIGE ABBRUCHSICHERUNG, DIE DEN ERSTEN STAND HAELT.
     *
     * Hier stand zuerst ein `register_shutdown_function($zurueck)` JE AUFRUF.
     * Das ist falsch, und zwar auf eine Art, die erst am Ende auffaellt:
     * Abschlussfunktionen laufen in der Reihenfolge ihrer Anmeldung. Die
     * aeussere legte den Urstand zurueck — und die dritte, spaeter
     * angemeldete, schrieb danach ihren Zwischenstand darueber. Gemessen:
     * Nach einem Lauf der `versandprobe` stand ein ZUFAELLIGER `server_key`
     * in der `config.php`, und damit waeren die versiegelten
     * Sicherungsziele der Anlage nicht mehr zu oeffnen gewesen.
     *
     * Jetzt merkt sich die erste gestellte Lage den Urstand, und nur sie
     * meldet die Sicherung an. Jede weitere verlaesst sich darauf. */
    konfig_stellen_urstand($pfad, $vorherText);

    return $zurueck;
}

/**
 * Den Urstand der Datei einmal merken und EINE Abbruchsicherung anmelden.
 *
 * `register_shutdown_function` greift nicht bei `kill -9` und nicht bei
 * einem Fatal im Speicherlimit. Wer die Probe so beendet, legt die Datei von
 * Hand zurueck — sie steht als `config.php.vor-probe` daneben.
 */
function konfig_stellen_urstand(string $pfad, ?string $text): void
{
    /* JE PFAD EINMAL, nicht einmal ueberhaupt: `komplettprobe` stellt in der
     * Kopie, und eine Probe koennte beide anfassen. Ein einziges Flag haette
     * den zweiten Pfad ungesichert gelassen. */
    static $gemerkt = [];
    if (isset($gemerkt[$pfad])) { return; }
    $gemerkt[$pfad] = true;

    /* EINE KOPIE AUF DER PLATTE, nicht nur im Speicher: Der haerteste
     * Abbruch ist der, bei dem PHP nichts mehr ausfuehrt. */
    if ($text !== null) { @file_put_contents($pfad . '.vor-probe', $text); }

    register_shutdown_function(static function () use ($pfad, $text): void {
        konfig_stellen_bytes($pfad, $text);
        @unlink($pfad . '.vor-probe');
    });
}

/** Die Datei auf einen frueheren Text zuruecksetzen (oder entfernen). */
function konfig_stellen_bytes(string $pfad, ?string $text): void
{
    /* IST DAS ZIEL UEBERHAUPT NOCH DA?
     *
     * `komplettprobe` arbeitet in einem Wegwerfverzeichnis und raeumt es am
     * Ende weg — die Abbruchsicherung lief danach und schrieb ins Leere. Das
     * war keine Gefahr, aber eine `PHP Warning` mitten in der Ausgabe einer
     * Probe, und die verdeckt beim naechsten Mal einen echten Befund. Wer
     * sein Verzeichnis abgeraeumt hat, braucht keinen Rueckweg mehr. */
    if (!is_dir(dirname($pfad))) { return; }

    if ($text === null) { @unlink($pfad); }
    else                { file_put_contents($pfad, $text); }
    if (function_exists('opcache_invalidate')) { @opcache_invalidate($pfad, true); }
    if (function_exists('konfig_verwerfen')) { konfig_verwerfen(); }
    if (function_exists('config_gemerktes_verwerfen')) { config_gemerktes_verwerfen(); }
}

/** Wo liegt die `config.php` der Anlage? ZWEI Ebenen hoch: Die Datei liegt
 * seit Konzept BR unter tools/sandbox/ (E-BR-03 (4)). Mit einer Ebene zeigte
 * der Pfad auf tools/server/ — und der Rueckweg oben kehrt bei fehlendem
 * Verzeichnis STILL zurueck. */
function konfig_stellen_pfad(): string
{
    return dirname(__DIR__, 2) . '/server/config.php';
}

/** Den heutigen Stand lesen — ohne `konfig()`, das hier gerade umgangen wird. */
function konfig_stellen_lesen(string $pfad): array
{
    if (!is_file($pfad)) { return []; }
    $roh = require $pfad;
    return is_array($roh) ? $roh : [];
}

/**
 * Schreiben, und zwar so, dass die naechste Anfrage es auch sieht.
 *
 * DREI SCHRITTE, UND ALLE DREI SIND NOETIG:
 *
 * 1. `var_export` — dieselbe Form, die `install.php` schreibt
 *    (`return array (`). Wer hier `[` erzeugte, haette eine Datei, die
 *    anders aussieht als die des Installers, und die naechste Instanz suchte
 *    den Unterschied.
 * 2. `opcache_invalidate` — OPcache merkt sich die uebersetzte `config.php`
 *    und prueft ihren Zeitstempel SEKUNDENGENAU. Wird sie in derselben
 *    Sekunde ersetzt, in der die alte Fassung uebersetzt wurde, gilt sie als
 *    unveraendert. Derselbe Grund wie in `config_eintrag_schreiben()`.
 * 3. `konfig_verwerfen()` — der Merker in `konfig_lib.php`. Ohne ihn liest
 *    dieselbe Anfrage weiter den alten Stand, und genau das ist der Fehler,
 *    den diese Datei beheben soll.
 */
function konfig_stellen_schreiben(string $pfad, array $werte): void
{
    file_put_contents($pfad, "<?php\nreturn " . var_export($werte, true) . ";\n");
    if (function_exists('opcache_invalidate')) { @opcache_invalidate($pfad, true); }
    if (function_exists('konfig_verwerfen')) { konfig_verwerfen(); }
    /* Und die Merker der Serverkrypto, falls sie geladen ist: `serverschluessel()`,
     * `kdf_anteil()` und `anteil_zustand()` halten ihren Wert ebenfalls in
     * einer `static`. `config_gemerktes_verwerfen()` raeumt beides. */
    if (function_exists('config_gemerktes_verwerfen')) { config_gemerktes_verwerfen(); }
}
