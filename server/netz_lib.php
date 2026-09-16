<?php
declare(strict_types=1);

/**
 * VERTRAUENSWUERDIGE PROXYS — wer darf sagen, woher eine Anfrage kommt?
 * (P5a/AP4, E-P5a-17; Vorbereitung E-PP-08, F-PP-3)
 *
 * DIE FRAGE DAHINTER. `ratelimit_lib.php` liest seit jeher nur `REMOTE_ADDR`
 * und begruendet das so: „Kopfzeilen von Zwischenstationen (X-Forwarded-For
 * und Verwandte) werden BEWUSST NICHT ausgewertet: Sie stammen vom Aufrufer
 * und liessen sich zum Zuruecksetzen des Zaehlers frei erfinden." Das ist
 * richtig — und es ist die falsche Antwort fuer eine Installation hinter
 * einem Reverse Proxy oder einem DDoS-Schutz. Dort ist `REMOTE_ADDR` die
 * Adresse des Proxys, und der Ratenschutz zaehlt **alle Nutzerinnen als
 * eine** und sperrt sie gemeinsam aus.
 *
 * DIE ANTWORT IST EINE LISTE, UND SIE IST LEER. `config.php` bekommt
 * `netz.vertrauenswuerdige_proxys` — Adressen oder CIDR-Bereiche, Vorgabe
 * **leer**. Leer heisst: genau das heutige Verhalten, `REMOTE_ADDR` und sonst
 * nichts. Wer eintraegt, sagt damit: „Von diesen Adressen glaube ich der
 * Kopfzeile." Das ist eine Aussage ueber die eigene Netztopologie, und
 * niemand ausser der Betreiberin kann sie treffen.
 *
 * DIE **LETZTE** ADRESSE, NICHT DIE ERSTE. `X-Forwarded-For` ist eine Liste,
 * an die jede Zwischenstation hinten anhaengt. Der Aufrufer kann die Liste
 * mit erfundenen Eintraegen beginnen — die **erste** Adresse ist deshalb die
 * unsicherste. Die letzte hat der Proxy geschrieben, dem wir vertrauen.
 *
 * (Bei mehreren eigenen Proxys hintereinander waere die vorletzte richtig.
 * Diese Anwendung nimmt die letzte und sagt es: Wer eine Kette betreibt,
 * konfiguriert sie so, dass sein aeusserster Proxy die Kopfzeile SETZT statt
 * anzuhaengen — das ist die uebliche Einstellung und die einzige, bei der
 * die Frage eine eindeutige Antwort hat.)
 *
 * DIESELBE LISTE GILT FUER `X-Forwarded-Proto` (E-P5a-16). Wer die
 * Client-Adresse faelschen koennte, koennte sonst auch „diese Anfrage kam
 * ueber HTTPS" behaupten und damit den HTTPS-Zwang und HSTS aushebeln.
 *
 * DIESE DATEI LAEDT NICHTS. Sie liest `$CFG` und `$_SERVER`, mehr nicht —
 * `kopfzeilen_lib.php` braucht sie, bevor eine Datenbankverbindung steht.
 */

/**
 * Die eingetragenen Proxys.
 *
 * @return list<string> Adressen oder CIDR-Bereiche; leer = Vorgabe
 */
function netz_proxys(): array
{
    $cfg = $GLOBALS['CFG'] ?? null;
    if (!is_array($cfg)) { return []; }
    $roh = $cfg['netz']['vertrauenswuerdige_proxys'] ?? [];
    if (is_string($roh)) { $roh = array_map('trim', explode(',', $roh)); }
    if (!is_array($roh)) { return []; }
    $aus = [];
    foreach ($roh as $e) {
        $e = trim((string)$e);
        if ($e !== '') { $aus[] = $e; }
    }
    return $aus;
}

/**
 * Liegt `$ip` in `$muster`? `$muster` ist eine Adresse oder ein CIDR-Bereich.
 *
 * ES RECHNET AUF BYTES, nicht auf Zeichenketten: `inet_pton()` liefert 4 bzw.
 * 16 Byte, und ein Praefixvergleich darauf gilt fuer IPv4 und IPv6
 * gleichermassen. Ein Vergleich auf der Textform („beginnt mit 10.") ist der
 * uebliche Fehler — er haelt `10.0.0.1` und `100.0.0.1` fuer verwandt.
 */
function netz_in_bereich(string $ip, string $muster): bool
{
    $a = @inet_pton($ip);
    if ($a === false) { return false; }

    $bits = null;
    if (str_contains($muster, '/')) {
        [$muster, $bitsRoh] = explode('/', $muster, 2);
        $bits = (int)$bitsRoh;
    }
    $b = @inet_pton(trim($muster));
    if ($b === false || strlen($a) !== strlen($b)) { return false; }

    $max = strlen($a) * 8;
    if ($bits === null) { $bits = $max; }
    if ($bits < 0 || $bits > $max) { return false; }
    if ($bits === 0) { return true; }

    $ganze = intdiv($bits, 8);
    $rest  = $bits % 8;
    if ($ganze > 0 && strncmp($a, $b, $ganze) !== 0) { return false; }
    if ($rest === 0) { return true; }

    $maske = 0xFF << (8 - $rest) & 0xFF;
    return (ord($a[$ganze]) & $maske) === (ord($b[$ganze]) & $maske);
}

/**
 * Steht die unmittelbare Gegenstelle (`REMOTE_ADDR`) in der Liste?
 *
 * Ohne Eintrag: immer `false` — und damit gilt ueberall das Verhalten von vor
 * Web 20.7.0.
 */
function netz_proxy_vertrauenswuerdig(): bool
{
    $liste = netz_proxys();
    if ($liste === []) { return false; }
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if ($ip === '') { return false; }
    foreach ($liste as $m) {
        if (netz_in_bereich($ip, $m)) { return true; }
    }
    return false;
}

/**
 * Die Adresse, mit der gerechnet wird.
 *
 * Ohne vertrauenswuerdigen Proxy ist das `REMOTE_ADDR` — wie bisher, und das
 * ist der Regelfall. Mit einem: die **letzte** Angabe aus `X-Forwarded-For`.
 *
 * DIE LAENGE WIRD GEKAPPT (45 Zeichen), weil der Wert als Merkmal in
 * `rate_limits.merkmal` (VARCHAR(190)) landet und die Kopfzeile vom Aufrufer
 * kommt. Eine erfundene Adresse von 10 kB darf keine Datenbankzeile sprengen.
 */
function netz_client_ip(): string
{
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? '');
    if (netz_proxy_vertrauenswuerdig()) {
        $roh = (string)($_SERVER['HTTP_X_FORWARDED_FOR'] ?? '');
        if ($roh !== '') {
            $teile = array_map('trim', explode(',', $roh));
            $letzte = (string)end($teile);
            /* GEPRUEFT, NICHT GEGLAUBT: Auch ein vertrauenswuerdiger Proxy
             * schreibt gelegentlich `unknown` oder eine Adresse mit Port. Was
             * `inet_pton()` nicht kennt, ist keine Adresse. */
            if ($letzte !== '' && @inet_pton($letzte) !== false) { $ip = $letzte; }
        }
    }
    return $ip !== '' ? mb_substr($ip, 0, 45) : 'unbekannt';
}
