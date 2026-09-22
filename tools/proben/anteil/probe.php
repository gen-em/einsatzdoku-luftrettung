<?php
declare(strict_types=1);

/**
 * Anteilprobe — rechnet der Server den Server-Anteil richtig, und erkennt er
 * die fuenf Lagen aus E-S10-09? (S10, Schritt 9b)
 *
 * WOFUER. Der Server-Anteil ist das zweite Geheimnis der Installation, und er
 * hat eine unangenehme Eigenschaft: Wenn er falsch ist, sieht das aus wie ein
 * falsches Passwort — fuer jede NutzerIn gleichzeitig. Genau dagegen steht die
 * Zustandsmaschine in `anteil_zustand()`, und genau die ist im Browser kaum zu
 * pruefen: Vier ihrer fuenf Lagen entstehen erst, wenn man `config.php` oder
 * `app_state` von Hand verstellt. Hier werden sie hergestellt, gemessen und
 * wieder zurueckgestellt.
 *
 * SIE FASST DIE INSTALLATION AN — ZWEIMAL, UND BEIDES WIRD ZURUECKGELEGT:
 *
 *   1. `app_state.kdf_anteil_kennung`. Der Wert wird vor dem Lauf gelesen und
 *      im `finally` wiederhergestellt — auch bei einem Abbruch. War er vorher
 *      nicht da, wird die Zeile geloescht.
 *   2. `server/config.php`. Nur Teil D (Schreibweg) fasst sie an, und nur
 *      dann, wenn `--schreiben` mitgegeben wird. Vorher wird eine Kopie
 *      angelegt, hinterher wird sie zurueckgeschoben und byteweise
 *      verglichen; die Probe sagt die Zahl.
 *
 * **Auf einer Installation mit Betrieb nicht fahren.** Fuer die Dauer des
 * Laufs steht in `app_state` eine fremde Kennung, und angemeldete Sitzungen
 * bekommen in dieser Zeit keinen Anteil ausgeliefert. Dasselbe gilt hier wie
 * fuer `tools/proben/wartung/`: gegen eine Testinstallation, nicht gegen den
 * Produktivserver.
 *
 * WARUM OHNE HTTP. Gemessen wird eine Rechnung und eine Zustandsmaschine,
 * kein Verhalten an Kopfzeilen. Die Probe laedt `db.php` und
 * `serverkrypto_lib.php` und ruft die Funktionen unmittelbar — dieselbe
 * Wahl wie `tools/proben/spur/`. Was AN DER OBERFLAECHE passiert, misst der
 * Browserlauf von AP2 und AP3; was hier gemessen wird, saehe man dort nicht.
 *
 * Aufruf:
 *   php tools/proben/anteil/probe.php               Teile A bis C
 *   php tools/proben/anteil/probe.php --schreiben   dazu Teil D (config.php)
 *
 * Rueckgabewert: 0 = alle Erwartungen erfuellt, 1 = mindestens eine nicht.
 */

$wurzel  = dirname(__DIR__, 3) . '/server';
$schreib = in_array('--schreiben', array_slice($argv, 1), true);

require_once $wurzel . '/db.php';
require_once $wurzel . '/serverkrypto_lib.php';

/* ---- Buchfuehrung -------------------------------------------------------- */

$erfuellt = 0;
$offen    = 0;

function pruefe(string $was, $ist, $soll): void
{
    global $erfuellt, $offen;
    $gleich = $ist === $soll;
    if ($gleich) { $erfuellt++; } else { $offen++; }
    printf("  %s %-58s %s\n",
        $gleich ? 'ok  ' : 'FEHL',
        $was,
        $gleich ? var_export($ist, true)
                : var_export($ist, true) . '  erwartet: ' . var_export($soll, true));
}

function teil(string $name): void { printf("\n%s\n", $name); }

/* Zwei feste Werte. Sie sind KEINE Zufallswerte: Eine Probe, die bei jedem
 * Lauf andere Zahlen misst, kann ihre Kennungen nicht in dieser Datei
 * nennen — und dann prueft sie die Rechnung gegen sich selbst. */
const A_HEX = '00112233445566778899aabbccddeeff'
            . '00112233445566778899aabbccddeeff';
const B_HEX = 'ffeeddccbbaa99887766554433221100'
            . 'ffeeddccbbaa99887766554433221100';

$merke = schluessel_marke_lesen('kdf_anteil_kennung');
printf("Anteilprobe — %s\n", date('c'));
printf("  app_state.kdf_anteil_kennung vorher: %s\n",
       $merke === null ? '(nicht gesetzt)' : $merke);

/** `config.php` im Speicher verstellen und die gemerkten Werte verwerfen. */
function stelle(?string $anteil, ?string $alt = null): void
{
    global $CFG;
    if ($anteil === null) { unset($CFG['kdf_anteil']); }
    else                  { $CFG['kdf_anteil'] = $anteil; }
    if ($alt === null) { unset($CFG['kdf_anteil_alt']); }
    else               { $CFG['kdf_anteil_alt'] = $alt; }
    config_gemerktes_verwerfen();
}

/** Die Marke setzen oder loeschen. */
function marke(?string $wert): void
{
    if ($wert === null) {
        db()->prepare('DELETE FROM app_state WHERE k = ?')
            ->execute(['kdf_anteil_kennung']);
    } else {
        schluessel_marke_setzen('kdf_anteil_kennung', $wert);
    }
}

$cfgSicherung = $CFG;

try {

/* ---- A. Die Rechnungen --------------------------------------------------- */

teil('A. Kennung und Konto-Anteil — die Rechnungen');

$kA = schluessel_kennung(A_HEX);
$kB = schluessel_kennung(B_HEX);

/* Von Hand gegengerechnet: SHA-256 ueber die 64 KLEINGESCHRIEBENEN
 * Hexzeichen, die ersten 8 Zeichen des Hexausdrucks. Die Zahl steht hier,
 * damit ein Umbau der Funktion auffaellt und nicht mitwandert. */
pruefe('Kennung A (8 Hex von SHA-256 ueber die Hexform)', $kA,
       substr(hash('sha256', strtolower(A_HEX)), 0, 8));
pruefe('Kennung B', $kB, substr(hash('sha256', strtolower(B_HEX)), 0, 8));
pruefe('Kennung ist 8 Zeichen lang', strlen((string)$kA), 8);
pruefe('A und B haben verschiedene Kennungen', $kA !== $kB, true);

/* GROSSSCHREIBUNG DARF NICHTS AENDERN. Wer vom Schluesselblatt abschreibt,
 * tippt gelegentlich gross — und bekaeme sonst die Meldung „das ist nicht der
 * Wert, mit dem die Huellen gebaut wurden" fuer genau denselben Wert. */
pruefe('Grossschreibung ergibt dieselbe Kennung',
       schluessel_kennung(strtoupper(A_HEX)), $kA);
pruefe('63 Hexzeichen ergeben keine Kennung',
       schluessel_kennung(substr(A_HEX, 0, 63)), null);
pruefe('null ergibt keine Kennung', schluessel_kennung(null), null);

$rohA = hex2bin(A_HEX);
$a7  = konto_anteil(7, $rohA);
$a8  = konto_anteil(8, $rohA);
$a7b = konto_anteil(7, (string)hex2bin(B_HEX));

pruefe('Konto-Anteil ist 64 Hexzeichen',
       preg_match('/^[0-9a-f]{64}$/', $a7) === 1, true);
pruefe('Konto-Anteil = HMAC-SHA256(anteil, "konto:7")',
       $a7, hash_hmac('sha256', 'konto:7', (string)$rohA));
/* DIE TRENNUNG IST DER ZWECK (E-S10-03): Wer den Anteil EINES Kontos hat,
 * darf daraus keinen anderen bilden koennen. */
pruefe('Konto 7 und Konto 8 bekommen Verschiedenes', $a7 !== $a8, true);
pruefe('derselbe Konto, anderer Anteil ergibt Verschiedenes', $a7 !== $a7b, true);

teil('A2. Die Anteil-Kennung im Huellenpraefix');

pruefe('edka1: mit Kennung wird gelesen',
       huelle_anteil_kennung('edka1:ab12cd34:AAAA'), 'ab12cd34');
pruefe('edk1: traegt keine Anteil-Kennung',
       huelle_anteil_kennung('edk1:AAAA'), null);
pruefe('Huelle ohne Praefix traegt keine', huelle_anteil_kennung('AAAA'), null);
pruefe('null traegt keine', huelle_anteil_kennung(null), null);
/* Sieben Zeichen, Grossbuchstaben, fehlender Doppelpunkt: alles KEINE
 * gueltige Kennung. Eine halb erkannte waere schlimmer als gar keine — der
 * Server verglichte dann gegen einen Wert, den niemand gebaut hat. */
pruefe('sieben Hexzeichen sind keine Kennung',
       huelle_anteil_kennung('edka1:ab12cd3:AAAA'), null);
pruefe('Grossbuchstaben sind keine Kennung',
       huelle_anteil_kennung('edka1:AB12CD34:AAAA'), null);
pruefe('ohne zweiten Doppelpunkt keine Kennung',
       huelle_anteil_kennung('edka1:ab12cd34AAAA'), null);

teil('A3. WRAP_RE nimmt beide Fassungen, PAT_BLOB_RE bleibt eng');

require_once $wurzel . '/validate_lib.php';
$b64 = str_repeat('A', 40);
pruefe('WRAP_RE nimmt edk1:',  preg_match(WRAP_RE, 'edk1:' . $b64) === 1, true);
pruefe('WRAP_RE nimmt edka1:', preg_match(WRAP_RE, 'edka1:ab12cd34:' . $b64) === 1, true);
pruefe('WRAP_RE nimmt ohne Praefix', preg_match(WRAP_RE, $b64) === 1, true);
pruefe('WRAP_RE weist eine halbe Kennung ab',
       preg_match(WRAP_RE, 'edka1:ab12cd3:' . $b64) === 1, false);
/* DER BLOB BLEIBT UNBERUEHRT, und das ist die Aussage von S10 in einer Zeile:
 * Der Anteil steckt im Datenschluessel, der Datenschluessel oeffnet die
 * Huelle, und in der Huelle liegt derselbe Inhaltsschluessel wie vorher. Kein
 * Datensatz wird angefasst, wenn ein Konto umstellt. */
pruefe('PAT_BLOB_RE nimmt edka1: NICHT',
       preg_match(PAT_BLOB_RE, 'edka1:ab12cd34:' . str_repeat('A', 60)) === 1, false);

/* ---- B. Die fuenf Lagen aus E-S10-09 ------------------------------------- */

teil('B. Die fuenf Lagen aus E-S10-09');

/* 1. nicht eingerichtet — kein Wert, keine Marke. Der Zustand jeder
 *    Installation unmittelbar nach dem Ausrollen von S10. */
stelle(null); marke(null);
$z = anteil_zustand(true);
pruefe('(1) nicht eingerichtet -> stand', $z['stand'], 'fehlt');
pruefe('(1) nichts wird ausgeliefert', anteil_ausgeliefert(), null);
pruefe('(1) die Marke bleibt leer', schluessel_marke_lesen('kdf_anteil_kennung'), null);

/* 2. bereit — Wert da, Marke wird beim ersten Lesen nachgetragen
 *    (E-S10-U-02). Das ist der Griff „Anlegen" auf der Karte. */
stelle(A_HEX); marke(null);
$z = anteil_zustand(true);
pruefe('(2) bereit -> stand', $z['stand'], 'bereit');
pruefe('(2) Kennung = Kennung von A', $z['kennung'], $kA);
pruefe('(2) die Marke ist nachgetragen',
       schluessel_marke_lesen('kdf_anteil_kennung'), $kA);
pruefe('(2) ausgeliefert wird die Kennung', anteil_ausgeliefert(), $kA);
pruefe('(2) ein Konto bekommt genau einen Anteil',
       array_keys(konto_anteile(7)), [$kA]);
pruefe('(2) und zwar den des Kontos', konto_anteile(7)[$kA], $a7);

/* 3. Rotation — zwei Werte, die Marke wandert auf den neuen. Beide Anteile
 *    werden ausgeliefert: Der Browser muss die alte Huelle noch oeffnen
 *    koennen, um sie mit dem neuen Anteil neu zu bauen. */
stelle(B_HEX, A_HEX); marke($kA);
$z = anteil_zustand(true);
pruefe('(3) Rotation -> stand', $z['stand'], 'rotation');
pruefe('(3) Kennung = neu', $z['kennung'], $kB);
pruefe('(3) Kennung_alt = alt', $z['kennung_alt'], $kA);
pruefe('(3) die Marke ist mitgewandert',
       schluessel_marke_lesen('kdf_anteil_kennung'), $kB);
pruefe('(3) neue Huellen entstehen mit dem NEUEN', anteil_ausgeliefert(), $kB);
pruefe('(3) ausgeliefert werden BEIDE',
       array_keys(konto_anteile(7)), [$kB, $kA]);

/* 4. abweichend — ein anderer Wert als der, mit dem gearbeitet wurde. Der
 *    Ernstfall: Es wird NICHTS ausgeliefert, und die Seite sagt die erwartete
 *    Kennung, statt „Passwort falsch" zu behaupten. */
stelle(B_HEX); marke($kA);
$z = anteil_zustand(true);
pruefe('(4) abweichend -> stand', $z['stand'], 'abweichend');
pruefe('(4) erwartet wird die Kennung aus app_state', $z['erwartet'], $kA);
pruefe('(4) vorhanden ist eine andere', $z['kennung'], $kB);
pruefe('(4) es wird NICHTS ausgeliefert', anteil_ausgeliefert(), null);
pruefe('(4) auch kein Konto-Anteil', konto_anteile(7), []);
pruefe('(4) die Marke bleibt unangetastet',
       schluessel_marke_lesen('kdf_anteil_kennung'), $kA);

/* 4b. Dieselbe Lage von der anderen Seite: Wert ganz weg, Marke da. Tritt
 *     nach einem Wiederanlauf aus einer Sicherung OHNE config.php auf. */
stelle(null); marke($kA);
$z = anteil_zustand(true);
pruefe('(4b) Wert weg, Marke da -> stand', $z['stand'], 'abweichend');
pruefe('(4b) erwartet wird trotzdem genannt', $z['erwartet'], $kA);
pruefe('(4b) es wird NICHTS ausgeliefert', anteil_ausgeliefert(), null);

/* 5. Neuanfang — beide Blaetter verloren, ein frischer Wert wird gesetzt und
 *    die Marke mit ihm. Danach ist die Lage wieder „bereit"; die Konten mit
 *    alter Huelle laufen in die Reset-Meldung, und der Reset traegt. */
stelle(B_HEX); marke($kB);
$z = anteil_zustand(true);
pruefe('(5) Neuanfang -> stand', $z['stand'], 'bereit');
pruefe('(5) ausgeliefert wird der neue', anteil_ausgeliefert(), $kB);
/* Die Huelle eines Kontos, das den Neuanfang nicht mitgemacht hat, traegt
 * eine Kennung, die in KONTO_ANTEILE nicht vorkommt — daran erkennt der
 * Browser die Lage und zeigt die Reset-Meldung. */
pruefe('(5) eine Huelle auf dem ALTEN Anteil ist keinem Anteil zuzuordnen',
       array_key_exists($kA, konto_anteile(7)), false);

/* Ein halber Wert ist wie keiner — an beiden Stellen dieselbe Linie. */
stelle(substr(A_HEX, 0, 63)); marke(null);
pruefe('(6) 63 Hexzeichen zaehlen wie kein Anteil',
       anteil_zustand(true)['stand'], 'fehlt');
pruefe('(6) kdf_anteil() liefert null', kdf_anteil(true), null);

/* ---- C. Die Rotation laeuft leer, wenn kein alter Wert dasteht ----------- */

teil('C. kdf_anteil_alt ohne Rotation');

stelle(A_HEX, B_HEX); marke($kA);
$z = anteil_zustand(true);
pruefe('(7) alter Wert da, Marke auf dem neuen -> Rotation', $z['stand'], 'rotation');
pruefe('(7) Kennung bleibt die des aktuellen', $z['kennung'], $kA);

stelle(A_HEX, null); marke($kA);
pruefe('(8) ohne alten Wert ist es schlicht bereit',
       anteil_zustand(true)['stand'], 'bereit');

/* ---- D. Der Schreibweg in config.php ------------------------------------- */

if ($schreib) {
    teil('D. config_eintrag_schreiben() an der echten config.php');

    $pfad  = $wurzel . '/config.php';
    $kopie = $pfad . '.anteilprobe';
    copy($pfad, $kopie);
    $vorher = (string)file_get_contents($pfad);

    try {
        /* Die Probe schreibt in `kdf_anteil_alt` — den einzigen der drei
         * Eintraege, der auf dieser Installation nicht in Gebrauch ist. Ein
         * Fehlschlag mitten im Lauf kostet damit nichts, was gebraucht wird. */
        $CFG = $cfgSicherung;
        config_gemerktes_verwerfen();

        [$ok, $wert] = config_eintrag_schreiben('kdf_anteil_alt', B_HEX);
        pruefe('(D1) ein neuer Eintrag laesst sich schreiben', $ok, true);
        pruefe('(D1) und steht danach in $CFG', $CFG['kdf_anteil_alt'] ?? null,
               strtolower(B_HEX));
        pruefe('(D1) kdf_anteil_alt() liest ihn', bin2hex((string)kdf_anteil_alt()),
               strtolower(B_HEX));

        /* ERSETZEN IST DIE AUSNAHME und muss angesagt werden. */
        [$ok2, $meldung] = config_eintrag_schreiben('kdf_anteil_alt', A_HEX);
        pruefe('(D2) ein gueltiger Eintrag wird nicht still ersetzt', $ok2, false);
        pruefe('(D2) und der Wert steht unveraendert',
               bin2hex((string)kdf_anteil_alt()), strtolower(B_HEX));

        [$ok3, ] = config_eintrag_schreiben('kdf_anteil_alt', A_HEX, true);
        pruefe('(D3) mit ersetzen=true geht es', $ok3, true);
        pruefe('(D3) und der Wert ist der neue',
               bin2hex((string)kdf_anteil_alt()), strtolower(A_HEX));

        [$ok4, ] = config_eintrag_schreiben('kdf_anteil_alt', null);
        pruefe('(D4) der Eintrag laesst sich entfernen', $ok4, true);
        pruefe('(D4) danach liest kdf_anteil_alt() nichts', kdf_anteil_alt(true), null);
        pruefe('(D4) und $CFG kennt ihn nicht mehr',
               array_key_exists('kdf_anteil_alt', $CFG), false);

        /* DIE GESCHLOSSENE LISTE. Ohne sie waere das Formular „Nachtragen vom
         * Blatt" eine Handhabe, einen beliebigen Eintrag in die
         * Konfigurationsdatei zu schreiben. */
        [$ok5, ] = config_eintrag_schreiben('db', A_HEX);
        pruefe('(D5) ein fremder Eintrag wird abgewiesen', $ok5, false);
        [$ok6, ] = config_eintrag_schreiben('kdf_anteil_alt', 'kein hex');
        pruefe('(D6) kein Hexwert wird abgewiesen', $ok6, false);

        /* DIE DATEI IST HINTERHER DIESELBE. Das ist die eigentliche Zusage des
         * Schreibwegs: Er fasst genau eine Zeile an. */
        $nachher = (string)file_get_contents($pfad);
        pruefe('(D7) config.php ist nach dem Entfernen wieder byte-gleich',
               $nachher === $vorher, true);
        pruefe('(D8) keine Nebendatei liegengeblieben',
               is_file($wurzel . '/config.neu.php'), false);
    } finally {
        copy($kopie, $pfad);
        @unlink($kopie);
        @unlink($wurzel . '/config.neu.php');
    }
} else {
    teil('D. config_eintrag_schreiben() — NICHT gefahren');
    echo "  (ohne --schreiben wird config.php nicht angefasst)\n";
}

} finally {
    /* ZURUECKSTELLEN, AUCH BEI EINEM ABBRUCH. Eine Probe, die eine fremde
     * Kennung in `app_state` liegen laesst, sperrt danach jede angemeldete
     * Sitzung von ihrem Anteil aus — und zwar still. */
    $CFG = $cfgSicherung;
    config_gemerktes_verwerfen();
    marke($merke);
    printf("\napp_state.kdf_anteil_kennung zurueckgestellt auf: %s\n",
           $merke === null ? '(nicht gesetzt)' : $merke);
}

printf("\nErgebnis: %d von %d Erwartungen erfuellt, %d offen.\n",
       $erfuellt, $erfuellt + $offen, $offen);
exit($offen === 0 ? 0 : 1);
