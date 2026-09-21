<?php
declare(strict_types=1);

/**
 * KONFIG — der eine Leser fuer `config.php`
 * ===========================================================================
 *
 *     konfig('app.timezone')                  // 'Europe/Berlin'
 *     konfig('netz.vertrauenswuerdige_proxys', [])
 *     konfig('server_key', '')                // oberste Ebene, kein Punkt
 *     konfig_alles()                          // die ganze Ablage
 *     konfig_verwerfen()                      // nach einem Schreibvorgang
 *
 * WOGEGEN. Bis Web 20.26.3 lag die Konfiguration an ZWEI Stellen gleichzeitig:
 * `db.php` las die Datei beim Laden in die globale `$CFG` (Zeile 9), und fuenf
 * weitere Dateien lasen sie bei Bedarf ein zweites Mal — `smtp.php` sogar
 * dreimal. Gemessen am 20.09.2026: **7 Lesestellen in 5 Dateien, 46 Zugriffe
 * auf `$CFG` in 11 Dateien**. Wer einen Wert brauchte, hatte die Wahl zwischen
 * `global $CFG` (setzt voraus, dass `db.php` schon geladen ist) und einem
 * eigenen `require` (liest die Datei noch einmal von der Platte). 10c bringt
 * drei weitere Schluessel mit (`app.umgebung`, `betrieb.health_token`,
 * `mail.postfach`) — jeder waere eine achte bis zehnte Lesestelle geworden
 * (E-ZE-02, E-ZE-14).
 *
 * DIESE DATEI LAEDT NICHTS, UND DAS IST KEINE SPARSAMKEIT, SONDERN BEDINGUNG.
 * Drei Verbraucher stehen VOR `db.php`:
 *
 * - `install.php` laeuft auf einer Anlage, die `config.php` noch gar nicht
 *   hat — und laedt `db.php` bewusst nie.
 * - `sitzung_lib.php` wird aus `db.php` heraus gerufen, WAEHREND diese laedt.
 * - `plattform_lib.php` haengt an derselben Kette (`sitzung_ablage()` zieht
 *   es fuer die stuendliche Schreibprobe nach).
 *
 * Zoege diese Datei irgendetwas nach, das seinerseits `db.php` erreicht, liefe
 * `sitzung_ablage()` in einer halb geladenen `db.php` — und `require_once`
 * verdeckte den Zyklus, statt ihn zu melden. Der Kommentar in
 * `sitzung_lib.php` (bei `require_once plattform_lib.php`) sagt dasselbe von
 * der anderen Seite. **Wer hier ein `require` einfuegt, bricht das.**
 *
 * FEHLT `config.php`, IST DAS KEIN FEHLER, sondern der Normalfall vor der
 * Einrichtung: Jede Abfrage liefert dann ihre Vorgabe. Der Einrichter zeigt
 * auf dieser Grundlage seine Startseite, statt mit einem Fatal abzubrechen.
 *
 * GEMERKT WIRD IN EINEM `static`, wie bei `db()` — nicht in einer globalen
 * Variablen, die jeder anfassen kann. Wer `config.php` SCHREIBT, ruft danach
 * `konfig_verwerfen()`; die naechste Abfrage liest die Datei neu.
 * `config_eintrag_schreiben()` in `serverkrypto_lib.php` tut das ueber
 * `config_gemerktes_verwerfen()`. Ohne diesen Aufruf zeigte die Seite, die
 * gerade geschrieben hat, noch den alten Stand — derselbe Fehler wie in
 * S2/AP7, und er sieht aus wie „hat nicht gespeichert".
 *
 * WAS DIESE DATEI NICHT TUT: schreiben. `config.php` wird an genau einer
 * Stelle geschrieben (`config_eintrag_schreiben()`), und die gehoert zur
 * Serverkrypto, weil sie Hexschluessel pruefen und die Datei versiegeln muss.
 */

/**
 * Der Speicher hinter `konfig()`. Nicht von aussen rufen — dafuer gibt es
 * `konfig()`, `konfig_alles()` und `konfig_verwerfen()`.
 *
 * Ein `static` laesst sich von aussen nicht zuruecksetzen; deshalb nimmt die
 * Funktion den Verwurf als Argument. Dasselbe Muster wie
 * `sitzung_ablage_stand()`.
 */
function konfig_speicher(bool $verwerfen = false): array
{
    static $cfg = null;
    if ($verwerfen) { $cfg = null; return []; }
    if ($cfg === null) {
        $datei = __DIR__ . '/config.php';
        /* `is_file()` VOR dem `require`: Ein `@require` auf eine fehlende
         * Datei ist trotzdem ein Fatal, das `@` unterdrueckt nur die
         * Warnung. Der Fall „noch nicht eingerichtet" ist erwartet. */
        $roh = is_file($datei) ? require $datei : [];
        $cfg = is_array($roh) ? $roh : [];
    }
    return $cfg;
}

/**
 * Ein Wert aus `config.php`, ueber einen Punktpfad.
 *
 * `konfig('app.timezone')` steigt zwei Ebenen hinab. Ein Pfad ohne Punkt
 * liest die oberste Ebene (`konfig('server_key')`) — auch mit einem Namen,
 * der erst zur Laufzeit feststeht. Fehlt der Wert oder fehlt die ganze
 * Datei, kommt `$vorgabe` zurueck.
 *
 * KEIN TYPZWANG. Die Funktion gibt zurueck, was dasteht; die Aufrufstelle
 * sagt mit ihrem Cast, was sie erwartet ((string), (int), (array)). Ein
 * Zwang hier haette fuer `netz.vertrauenswuerdige_proxys` (Feld) und
 * `app.max_body_bytes` (Zahl) zwei verschiedene Funktionen gebraucht.
 */
function konfig(string $pfad, mixed $vorgabe = null): mixed
{
    $wert = konfig_speicher();
    foreach (explode('.', $pfad) as $teil) {
        if (!is_array($wert) || !array_key_exists($teil, $wert)) { return $vorgabe; }
        $wert = $wert[$teil];
    }
    return $wert;
}

/**
 * Die ganze Ablage — fuer den einen Fall, der ueber alle Schluessel laeuft:
 * `config_eintrag_schreiben()` vergleicht nach dem Schreiben Eintrag fuer
 * Eintrag, ob die neu gelesene Datei sonst unveraendert ist.
 */
function konfig_alles(): array
{
    return konfig_speicher();
}

/** Das Gemerkte wegwerfen. Nach jedem Schreibvorgang an `config.php`. */
function konfig_verwerfen(): void
{
    konfig_speicher(true);
}
