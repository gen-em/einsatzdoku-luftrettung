<?php
declare(strict_types=1);

/**
 * DER SERVERSCHLÜSSEL — das eine Geheimnis, das der Server selbst hat
 * (E-S2-21, S2/AP7).
 *
 * WARUM ES IHN ÜBERHAUPT GIBT, OBWOHL DIESE ANWENDUNG SONST NICHTS WEISS
 * Diagnose, Alter und Einsatzort werden im Browser ver- und entschlüsselt;
 * der Server sieht sie nie. Das bleibt so. Ab AP7 gibt es aber zwei Dinge,
 * die der Server ohne jeden Browser lesen können MUSS, weil sie zu einem
 * Zeitpunkt gebraucht werden, an dem niemand angemeldet ist:
 *
 *   1. die Zugangsdaten der Sicherungsziele (Passwort, privater Schlüssel) —
 *      der Versandjob läuft nachts über den Job-Einstieg,
 *   2. das Komplettbackup der Installation (AP8), sobald es das Haus verlässt.
 *
 * Beides ist kein Patientendatum. Der Serverschlüssel ist deshalb KEINE
 * Aufweichung der Zusage aus CLAUDE.md Abschnitt 4 — er schützt Betriebs-
 * geheimnisse, nicht Behandlungsdaten, und er kann die verschlüsselten Felder
 * auch nicht öffnen.
 *
 * WARUM ER IN config.php LIEGT UND NICHT IN DER DATENBANK
 * Der Zweck ist der Fall „jemand hat die Datenbank". Ein Schlüssel, der neben
 * dem Chiffretext in derselben Tabelle steht, hilft dann niemandem. Für das
 * Komplettbackup wird es zwingend: Der Dump enthält jede Tabelle: läge der
 * Schlüssel in einer davon, läge er im Backup, und das Backup wäre nur
 * scheinbar versiegelt.
 *
 * Damit gehört er ins WIEDERANLAUFPAKET — config.php plus Serverschlüssel
 * plus Zugang zum Sicherungsziel, getrennt aufbewahrt. Ohne ihn sind die
 * Zugangsdaten der Ziele verloren (neu eintragen, mehr nicht) und ein
 * versiegeltes Komplettbackup ist Müll (das ist die schwere Folge). Das
 * Runbook in `docs/Technik.md` sagt es an der Stelle noch einmal.
 *
 * WAS PASSIERT, WENN ER SICH ÄNDERT
 * Nichts Stilles. `sk_oeffnen()` gibt `null` zurück, und jeder Aufrufer sagt
 * dann, was Sache ist: „mit einem anderen Serverschlüssel gespeichert".
 * Ein stillschweigend leeres Passwort wäre die schlechteste aller Antworten —
 * der Versand liefe in eine Anmeldung ohne Passwort und meldete „Zugang
 * verweigert", und niemand käme auf die Ursache.
 */

/* Kennung des Formats, wie `edk1:` im Browser (crypto.js). Sie steht vorn,
 * damit ein Feld in der Datenbank auf einen Blick als versiegelt zu erkennen
 * ist — und damit eine spätere zweite Fassung neben der ersten existieren
 * kann, ohne dass geraten werden muss. */
const SK_PRAEFIX = 'edsk1:';

/* AES-256-GCM, dieselbe Wahl wie im Browser. 12 Byte Nonce (der von GCM
 * vorgesehene Normalfall), 16 Byte Prüfsumme. */
const SK_NONCE_LEN = 12;
const SK_TAG_LEN   = 16;

/**
 * Der Schlüssel als 32 Rohbytes — oder null, wenn keiner eingetragen ist.
 *
 * Erwartet werden 64 Hexzeichen in `config.php`. Alles andere ist ein
 * Eintragsfehler und wird wie „nicht vorhanden" behandelt: Ein halber
 * Schlüssel darf keine halben Chiffren erzeugen.
 */
function serverschluessel(bool $frisch = false): ?string
{
    static $roh = false;                      // false = noch nicht gelesen
    if ($frisch) { $roh = false; }
    if ($roh !== false) { return $roh; }
    global $CFG;
    $hex = (string)($CFG['server_key'] ?? '');
    if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) { return $roh = null; }
    $bin = hex2bin(strtolower($hex));
    return $roh = ($bin === false ? null : $bin);
}

/** Kurzform für die Oberfläche. */
function serverschluessel_da(): bool
{
    return serverschluessel() !== null;
}

/**
 * Ein frischer Schlüssel als 64 Hexzeichen.
 *
 * `random_bytes()` und nichts anderes: Es ist die einzige Quelle in PHP, die
 * bei fehlender Entropie wirft, statt schwache Bytes zu liefern.
 */
function serverschluessel_neu(): string
{
    return bin2hex(random_bytes(32));
}

/** Die Zeile, die in `config.php` gehört — genau so, wie sie dort steht. */
function serverschluessel_zeile(string $hex): string
{
    return "    'server_key' => '" . $hex . "',";
}

/**
 * Versiegeln. Gibt `edsk1:` + base64(nonce ‖ prüfsumme ‖ chiffre) zurück.
 *
 * DER ZWECK GEHT IN DIE ZUSATZDATEN und ist damit Teil der Prüfsumme. Er
 * verhindert das Umhängen: Ein versiegeltes FTP-Passwort aus Ziel 3 lässt
 * sich nicht als Passwort von Ziel 7 einsetzen, obwohl beide mit demselben
 * Schlüssel versiegelt sind — die Prüfsumme passt dann nicht mehr. Ohne
 * diesen Zusatz wäre der Chiffretext eine Münze, die überall gilt.
 *
 * @throws RuntimeException wenn kein Serverschlüssel eingetragen ist. Das ist
 *         Absicht: Wer versiegeln will und nicht kann, darf nicht im Klartext
 *         weitermachen.
 */
function sk_versiegeln(string $klartext, string $zweck): string
{
    $k = serverschluessel();
    if ($k === null) {
        throw new RuntimeException('Es ist kein Serverschlüssel eingetragen '
            . '(config.php, Eintrag server_key).');
    }
    $nonce = random_bytes(SK_NONCE_LEN);
    $tag   = '';
    $ct = openssl_encrypt($klartext, 'aes-256-gcm', $k, OPENSSL_RAW_DATA,
                          $nonce, $tag, 'edsk1|' . $zweck, SK_TAG_LEN);
    if ($ct === false) {
        throw new RuntimeException('Verschlüsseln fehlgeschlagen.');
    }
    return SK_PRAEFIX . base64_encode($nonce . $tag . $ct);
}

/**
 * Öffnen. Gibt den Klartext zurück — oder `null`.
 *
 * `null` heisst genau eines: Dieses Paket lässt sich mit DIESEM Schlüssel und
 * DIESEM Zweck nicht öffnen. Ob der Schlüssel fehlt, ein anderer ist oder der
 * Chiffretext beschädigt wurde, unterscheidet die Funktion bewusst nicht —
 * jede dieser Unterscheidungen wäre für einen Angreifer eine Auskunft und für
 * die Betreiberin keine Hilfe. Was die Betreiberin braucht, steht in der
 * Oberfläche: „mit einem anderen Serverschlüssel gespeichert".
 */
function sk_oeffnen(string $paket, string $zweck): ?string
{
    $k = serverschluessel();
    if ($k === null) { return null; }
    if (!str_starts_with($paket, SK_PRAEFIX)) { return null; }
    $roh = base64_decode(substr($paket, strlen(SK_PRAEFIX)), true);
    if ($roh === false || strlen($roh) < SK_NONCE_LEN + SK_TAG_LEN) { return null; }
    $nonce = substr($roh, 0, SK_NONCE_LEN);
    $tag   = substr($roh, SK_NONCE_LEN, SK_TAG_LEN);
    $ct    = substr($roh, SK_NONCE_LEN + SK_TAG_LEN);
    $klar = openssl_decrypt($ct, 'aes-256-gcm', $k, OPENSSL_RAW_DATA,
                            $nonce, $tag, 'edsk1|' . $zweck);
    return $klar === false ? null : $klar;
}

/**
 * Ist dieser Wert ein versiegeltes Paket?
 *
 * Gebraucht an den Stellen, die entscheiden müssen, ob ein Feld schon
 * versiegelt ist oder noch im Klartext ankommt (Formular).
 */
function sk_versiegelt(string $wert): bool
{
    return str_starts_with($wert, SK_PRAEFIX);
}

/**
 * Den Schlüssel in `config.php` eintragen — wenn die Datei beschreibbar ist.
 *
 * Gibt `[true, hex]` zurück, wenn er drinsteht, sonst `[false, meldung]`.
 *
 * WARUM DIESE FUNKTION ÜBERHAUPT SCHREIBT. Der Weg ohne sie hiesse: eine
 * Zeile abschreiben, per FTP in `config.php` einfügen, Datei hochladen. Das
 * geht — und ist genau die Art Handgriff, bei der ein Zeichen verlorengeht
 * und danach niemand weiss, warum der Versand nicht läuft. Klappt das
 * Schreiben nicht, bleibt der Weg von Hand; die Oberfläche zeigt dann die
 * fertige Zeile.
 *
 * WIE HIER GESCHRIEBEN WIRD, DAMIT NICHTS KAPUTTGEHT
 *   1. Es wird NUR ergänzt, nie ersetzt: Steht schon ein `server_key` in der
 *      Datei, bricht die Funktion ab. Ein Überschreiben würde jedes bereits
 *      versiegelte Feld unlesbar machen.
 *   2. Geschrieben wird in eine NEBENDATEI mit Endung `.php` und erst danach
 *      umbenannt. Die Endung ist kein Zufall: `server/` ist das Wurzel-
 *      verzeichnis des Webservers. Eine `config.php.tmp` läge dort als
 *      Textdatei mit dem Datenbankpasswort — abrufbar über den Browser.
 *   3. Die Nebendatei wird VOR dem Umbenennen eingelesen und geprüft: Sie
 *      muss ein Feld ergeben, das die alten Abschnitte unverändert enthält
 *      und den neuen Schlüssel dazu. Erst dann ersetzt sie das Original.
 */
function serverschluessel_eintragen(): array
{
    global $CFG;
    $pfad = __DIR__ . '/config.php';
    if (!is_file($pfad)) {
        return [false, 'config.php wurde nicht gefunden.'];
    }
    if (serverschluessel_da()) {
        return [false, 'Es steht bereits ein Serverschlüssel in config.php.'];
    }
    $inhalt = @file_get_contents($pfad);
    if ($inhalt === false) {
        return [false, 'config.php liess sich nicht lesen.'];
    }
    if (preg_match("/'server_key'\s*=>/", $inhalt)) {
        return [false, 'In config.php steht bereits ein Eintrag server_key — '
            . 'er ist aber keine 64 Hexzeichen. Bitte von Hand berichtigen.'];
    }
    if (!is_writable($pfad) || !is_writable(__DIR__)) {
        return [false, 'config.php ist nicht beschreibbar.'];
    }

    /* Eingefügt wird direkt hinter dem Beginn des Feldes — der einzigen
     * Stelle, die in jeder Fassung dieser Datei vorkommt.
     *
     * ZWEI SCHREIBWEISEN, UND DIE HÄUFIGERE STAND ZUERST NICHT DA. Der
     * Installer schreibt die Datei mit `var_export()`, und das ergibt
     * `return array (` — nicht `return [`. Nur `config.example.php` benutzt
     * die kurze Form. Der erste Versuch traf deshalb ausgerechnet jede
     * echte Installation nicht (Browserprobe S2/AP7).
     *
     * Die Einrückung wird von der Zeile darunter ABGESCHRIEBEN: `var_export`
     * rückt zwei Zeichen ein, die Beispieldatei vier. Eine feste Einrückung
     * sähe in einer der beiden Fassungen schief aus. */
    $hex = serverschluessel_neu();
    $treffer = 0;
    $neu = preg_replace_callback(
        '/(return\s*(?:\[|array\s*\()\s*\R)([ \t]*)/',
        static fn(array $m): string =>
            $m[1] . $m[2] . "'server_key' => '" . $hex . "',\n" . $m[2],
        $inhalt, 1, $treffer);
    if ($neu === null || $treffer !== 1) {
        return [false, 'In config.php war weder „return [" noch „return array (" '
            . 'zu finden.'];
    }

    $tmp = __DIR__ . '/config.neu.php';
    if (@file_put_contents($tmp, $neu, LOCK_EX) === false) {
        return [false, 'Die neue config.php liess sich nicht schreiben.'];
    }
    @chmod($tmp, 0640);

    /* GEGENPROBE VOR DEM UMBENENNEN. Eine kaputte config.php legt die ganze
     * Anwendung still — jede Seite lädt sie. Deshalb wird die Nebendatei erst
     * ausgeführt und Abschnitt für Abschnitt mit dem verglichen, was gerade
     * gilt. Schlägt das fehl, bleibt das Original stehen. */
    $probe = null;
    try {
        $probe = include $tmp;
    } catch (Throwable $e) {
        $probe = null;
    }
    $heil = is_array($probe)
        && ($probe['server_key'] ?? null) === $hex
        && ($probe['db']  ?? null) == ($CFG['db']  ?? null)
        && ($probe['app'] ?? null) == ($CFG['app'] ?? null)
        && ($probe['smtp'] ?? null) == ($CFG['smtp'] ?? null);
    if (!$heil) {
        @unlink($tmp);
        return [false, 'Die geänderte config.php hat die Gegenprobe nicht '
            . 'bestanden. Es wurde nichts ersetzt.'];
    }
    if (!@rename($tmp, $pfad)) {
        @unlink($tmp);
        return [false, 'Die geänderte config.php liess sich nicht an ihren '
            . 'Platz schieben.'];
    }
    @chmod($pfad, 0640);
    /* DEN BYTECODE-ZWISCHENSPEICHER VERWERFEN. OPcache merkt sich die
     * übersetzte config.php und prüft ihren Zeitstempel SEKUNDENGENAU. Wird
     * sie in derselben Sekunde ersetzt, in der die alte Fassung übersetzt
     * wurde, gilt sie als unverändert — und die nächste Anfrage liest
     * weiterhin die Datei OHNE Serverschlüssel. Gemessen in der Browserprobe
     * (S2/AP7): Die Seite meldete „steht jetzt in config.php", und der
     * unmittelbar folgende Aufruf zeigte wieder „Serverschlüssel fehlt". */
    if (function_exists('opcache_invalidate')) { @opcache_invalidate($pfad, true); }
    /* Der gelesene Wert liegt in einer `static` — ohne diese Zeile gaebe
     * serverschluessel() im selben Aufruf noch „kein Schluessel" zurueck, und
     * die Seite zeigte nach dem Anlegen weiter den Hinweis, dass keiner da
     * ist. */
    $CFG['server_key'] = $hex;
    serverschluessel(true);
    return [true, $hex];
}
