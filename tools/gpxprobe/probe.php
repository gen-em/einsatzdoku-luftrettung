<?php
declare(strict_types=1);

/**
 * GPX-Probe — liefert der Abruf die richtige Spur, richtig ausgezeichnet?
 * (S2/AP4, E-S2-09, Backlog Nr. 3)
 *
 * WOFUER. Der GPX-Abruf ist die erste Datei, die dieser Server ausliefert, und
 * er beantwortet drei Fragen, die alle drei schiefgehen koennen, ohne dass es
 * jemandem auffaellt:
 *
 *   1. Ist die Datei gueltiges GPX 1.1? Die Reihenfolge der Kindelemente ist
 *      im Schema eine `xsd:sequence`, keine `xsd:choice` — wer `<desc>` hinten
 *      anhaengt, schreibt eine Datei, die manche Programme klaglos lesen und
 *      andere ablehnen.
 *   2. Steht die richtige Spur drin? Nach sechs Monaten ist es die
 *      ausgeduennte, davor das Original (E-S2-09).
 *   3. Sieht man ihr an, welche von beiden es ist?
 *
 * DREI SAEULEN, und die erste ist das amtliche Schema.
 *
 *   Teil 0  `DOMDocument::schemaValidate()` gegen das vendorierte
 *           GPX-1.1-XSD von topografix.com. Vor jedem Lauf wird die
 *           SHA-256-Summe der Schemadatei geprueft — ein Schemalauf gegen ein
 *           veraendertes Schema belegt nichts.
 *   Teil 1  eine handgeschriebene Strukturpruefung. Sie bleibt NEBEN dem
 *           Schemalauf, weil sie andere Fragen stellt: der Schemalauf sagt
 *           „gueltig", diese Pruefung sagt WORAN es liegt, und sie faengt
 *           zwei Dinge, die das Schema durchlaesst — ein Komma als
 *           Dezimaltrenner in `lat`/`lon` (das Schema sieht dann nur einen
 *           ungueltigen Dezimalwert, nicht die Ursache) und eine Datei ganz
 *           ohne `<trkpt>`, die das Schema erlaubt und die trotzdem keine
 *           Spur ist.
 *   Teil 2  Vergleich PUNKT FUER PUNKT gegen die GPX-Dateien im
 *           eingecheckten Referenzexport — erzeugt von der ganz anderen
 *           Umsetzung in `assets/export.js`, im Browser. Zwei unabhaengige
 *           Wege, dieselbe Spur. Das belegt mehr als jedes Schema: ein
 *           Schema sagt nichts darueber, ob die richtigen Punkte drinstehen.
 *
 * SIE LEGT IHR EIGENES KONTO AN und raeumt es am Ende ab; bestehende Daten
 * fasst sie nicht an. Die Hintergrundjobs haelt sie an, solange sie laeuft —
 * sonst duennte einer mitten in der Messung eine Spur aus.
 *
 * Aufruf:
 *   php tools/gpxprobe/probe.php [basisadresse]
 *   (Vorgabe: http://127.0.0.1:8080)
 *
 * Rueckgabewert: 0 = alles erfuellt, 1 = mindestens eine Erwartung nicht.
 */

$wurzel = dirname(__DIR__, 2) . '/server';
require_once $wurzel . '/config.php';
require_once $wurzel . '/db.php';
require_once $wurzel . '/spur_lib.php';
require_once $wurzel . '/gpx_lib.php';
require_once $wurzel . '/jobs_lib.php';

$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$pdo   = db();

$erwartungen = 0; $offen = 0;
function pruefe(bool $ok, string $was, string $wert = ''): void {
    global $erwartungen, $offen;
    $erwartungen++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-56s %s\n", $ok ? 'ok ' : 'FEHL', $was, $wert);
}

/* ---- Das amtliche Schema ------------------------------------------------- */

/** Das vendorierte GPX-1.1-XSD und seine Pruefsumme (Herkunft: LIESMICH.md). */
const GPX_XSD      = __DIR__ . '/gpx11.xsd';
const GPX_XSD_SHA  = '9e4d1988b862edbe556305b130f8f6f1b29864fefd0dc02d5dab04ccdd1f34d6';

/* ---- Eigene Strukturpruefung, NEBEN dem Schemalauf ---------------------- */

/**
 * Die Regeln, die die Spezifikation aufstellt, von Hand nachgezogen.
 *
 * Wichtig sind die beiden Reihenfolgen: In `metadataType` steht `<desc>` VOR
 * `<time>`, in `trkType` zwischen `<name>` und `<trkseg>`. Wer sie vertauscht,
 * bekommt eine Datei, die wohlgeformt ist und trotzdem nicht dem Schema
 * entspricht — ein Fehler, den kein XML-Parser meldet.
 *
 * @return list<string> leer = in Ordnung
 */
function gpx_struktur_pruefen(string $xml): array
{
    $f = [];
    $vorher = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    if (!$doc->loadXML($xml)) {
        foreach (libxml_get_errors() as $e) { $f[] = 'nicht wohlgeformt: ' . trim($e->message); }
        libxml_clear_errors(); libxml_use_internal_errors($vorher);
        return $f;
    }
    libxml_clear_errors(); libxml_use_internal_errors($vorher);

    $w = $doc->documentElement;
    if ($w === null || $w->localName !== 'gpx') { return ['Wurzel ist nicht <gpx>']; }
    if ($w->namespaceURI !== GPX_NS)      { $f[] = 'falscher Namensraum: ' . (string)$w->namespaceURI; }
    if ($w->getAttribute('version') !== '1.1') { $f[] = 'version ist nicht 1.1'; }
    if ($w->getAttribute('creator') === '')    { $f[] = 'creator fehlt (Pflichtattribut)'; }

    /** Kindelementnamen in Dokumentreihenfolge. */
    $kinder = function (?DOMNode $k): array {
        $aus = [];
        if ($k === null) { return $aus; }
        foreach ($k->childNodes as $c) {
            if ($c->nodeType === XML_ELEMENT_NODE) { $aus[] = $c->localName; }
        }
        return $aus;
    };
    /** Steht die Liste in der Reihenfolge der erlaubten Folge? */
    $folgeOk = function (array $ist, array $soll): bool {
        $i = 0;
        foreach ($ist as $name) {
            $pos = array_search($name, $soll, true);
            if ($pos === false) { return false; }
            if ($pos < $i) { return false; }
            $i = $pos;
        }
        return true;
    };

    $oben = $kinder($w);
    if (!$folgeOk($oben, ['metadata', 'wpt', 'rte', 'trk', 'extensions'])) {
        $f[] = 'Kinder von <gpx> in falscher Reihenfolge: ' . implode(',', $oben);
    }

    foreach ($w->getElementsByTagNameNS(GPX_NS, 'metadata') as $m) {
        $k = $kinder($m);
        if (!$folgeOk($k, ['name', 'desc', 'author', 'copyright', 'link',
                           'time', 'keywords', 'bounds', 'extensions'])) {
            $f[] = 'Kinder von <metadata> in falscher Reihenfolge: ' . implode(',', $k);
        }
    }
    foreach ($w->getElementsByTagNameNS(GPX_NS, 'trk') as $t) {
        $k = $kinder($t);
        if (!$folgeOk($k, ['name', 'cmt', 'desc', 'src', 'link', 'number',
                           'type', 'extensions', 'trkseg'])) {
            $f[] = 'Kinder von <trk> in falscher Reihenfolge: ' . implode(',', $k);
        }
    }

    $n = 0;
    foreach ($w->getElementsByTagNameNS(GPX_NS, 'trkpt') as $p) {
        $n++;
        $la = $p->getAttribute('lat'); $lo = $p->getAttribute('lon');
        if ($la === '' || $lo === '') { $f[] = "trkpt $n ohne lat/lon"; continue; }
        if (!is_numeric($la) || abs((float)$la) > 90.0)  { $f[] = "trkpt $n: lat $la ausserhalb ±90"; }
        if (!is_numeric($lo) || abs((float)$lo) > 180.0) { $f[] = "trkpt $n: lon $lo ausserhalb ±180"; }
        // Dezimaltrenner: ein Komma waere die deutsche Schreibweise und hier falsch.
        if (str_contains($la, ',') || str_contains($lo, ',')) { $f[] = "trkpt $n: Komma als Dezimaltrenner"; }
        $k = $kinder($p);
        if (!$folgeOk($k, ['ele', 'time', 'magvar', 'geoidheight', 'name', 'cmt',
                           'desc', 'src', 'link', 'sym', 'type', 'fix', 'sat',
                           'hdop', 'vdop', 'pdop', 'ageofdgpsdata', 'dgpsid',
                           'extensions'])) {
            $f[] = "trkpt $n: Kinder in falscher Reihenfolge: " . implode(',', $k);
        }
        foreach ($p->getElementsByTagNameNS(GPX_NS, 'time') as $z) {
            if (!preg_match('/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}:\d{2}(\.\d+)?(Z|[+-]\d{2}:\d{2})$/',
                            trim($z->textContent))) {
                $f[] = "trkpt $n: <time> ist kein xsd:dateTime: " . trim($z->textContent);
            }
        }
    }
    if ($n === 0) { $f[] = 'kein einziger <trkpt>'; }
    return $f;
}

/** Die Punkte aus einer GPX-Datei, in Dokumentreihenfolge. */
function gpx_punkte(string $xml): array
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $aus = [];
    foreach ($doc->getElementsByTagNameNS(GPX_NS, 'trkpt') as $p) {
        $ele = null; $ts = null;
        foreach ($p->childNodes as $c) {
            if ($c->nodeType !== XML_ELEMENT_NODE) { continue; }
            if ($c->localName === 'ele')  { $ele = (float)$c->textContent; }
            if ($c->localName === 'time') { $ts = strtotime(trim($c->textContent)); }
        }
        $aus[] = [(float)$p->getAttribute('lat'), (float)$p->getAttribute('lon'), $ele, $ts];
    }
    return $aus;
}

/**
 * Die `<trk>` einer Datei einzeln — Name, Beschreibung und Punkte.
 *
 * Fuer die Mehrfachauswahl reicht `gpx_punkte()` nicht: Sie sammelt alle
 * `<trkpt>` des Dokuments ein und saehe deshalb nicht, ob die Spuren als
 * mehrere `<trk>` nebeneinander stehen oder zu einer zusammengeklebt wurden —
 * genau der Fehler, der die falschen Verbindungslinien zieht.
 *
 * @return list<array{name:string,desc:string,punkte:list<array>,segmente:int}>
 */
function gpx_trks(string $xml): array
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    $aus = [];
    foreach ($doc->getElementsByTagNameNS(GPX_NS, 'trk') as $t) {
        $name = ''; $desc = '';
        foreach ($t->childNodes as $c) {
            if ($c->nodeType !== XML_ELEMENT_NODE) { continue; }
            if ($c->localName === 'name') { $name = trim($c->textContent); }
            if ($c->localName === 'desc') { $desc = trim($c->textContent); }
        }
        $punkte = [];
        foreach ($t->getElementsByTagNameNS(GPX_NS, 'trkpt') as $p) {
            $ele = null; $ts = null;
            foreach ($p->childNodes as $c) {
                if ($c->nodeType !== XML_ELEMENT_NODE) { continue; }
                if ($c->localName === 'ele')  { $ele = (float)$c->textContent; }
                if ($c->localName === 'time') { $ts = strtotime(trim($c->textContent)); }
            }
            $punkte[] = [(float)$p->getAttribute('lat'), (float)$p->getAttribute('lon'), $ele, $ts];
        }
        $aus[] = ['name' => $name, 'desc' => $desc, 'punkte' => $punkte,
                  'segmente' => $t->getElementsByTagNameNS(GPX_NS, 'trkseg')->length];
    }
    return $aus;
}

/** Der Inhalt von `<metadata><desc>`. */
function gpx_kopf_desc(string $xml): string
{
    $doc = new DOMDocument();
    $doc->loadXML($xml);
    foreach ($doc->getElementsByTagNameNS(GPX_NS, 'metadata') as $m) {
        foreach ($m->getElementsByTagNameNS(GPX_NS, 'desc') as $d) {
            return trim($d->textContent);
        }
    }
    return '';
}

/* ---- Konto, Anmeldung, Abruf --------------------------------------------- */

$email = 'gpxprobe@gen-em.org';
$token = bin2hex(random_bytes(32));          // was der Browser sonst ableitet

/* KONTO PER SQL, Anmeldung ECHT ueber login.php. Die Ableitung des
 * Anmeldetokens gehoert in den Browser (PBKDF2 ueber das Passwort); sie hier
 * nachzubauen pruefte nichts, was diese Probe angeht. Der Hash wird deshalb
 * ueber ein selbst gewaehltes Token gesetzt — der Anmeldeweg selbst laeuft
 * danach unveraendert, samt Sitzung und auth_guard. */
$pdo->prepare('DELETE FROM users WHERE email = ?')->execute([$email]);
$pdo->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
               VALUES (?, 'GPX-Probe', 'user', ?, '', 310000)")
    ->execute([$email, password_hash($token, PASSWORD_DEFAULT)]);
$uid = (int)$pdo->lastInsertId();

$keks = tempnam(sys_get_temp_dir(), 'gpxprobe');

function ruf(string $pfad, ?array $post = null): array {
    global $basis, $keks;
    $ch = curl_init($basis . '/' . ltrim($pfad, '/'));
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_HEADER => true,
        CURLOPT_COOKIEJAR => $keks, CURLOPT_COOKIEFILE => $keks,
        CURLOPT_FOLLOWLOCATION => false, CURLOPT_TIMEOUT => 60,
    ]);
    if ($post !== null) {
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
    }
    $roh  = (string)curl_exec($ch);
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $klen = (int)curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    curl_close($ch);
    return ['code' => $code, 'kopf' => substr($roh, 0, $klen), 'leib' => substr($roh, $klen)];
}

echo "GPX-Probe gegen $basis\n";
echo "  Konto $email (uid $uid)\n";

jobs_pause(900);
$aufraeumen = function () use ($pdo, $uid, $keks) {
    jobs_pause(0);
    $q = $pdo->prepare('SELECT id FROM missions WHERE user_id = ?');
    $q->execute([$uid]);
    $ids = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    if ($ids) { spur_loeschen($pdo, 'mission', $ids); }
    $q = $pdo->prepare('SELECT id FROM rest_segments WHERE user_id = ?');
    $q->execute([$uid]);
    $ids = array_map('intval', $q->fetchAll(PDO::FETCH_COLUMN));
    if ($ids) { spur_loeschen($pdo, 'rest', $ids); }
    $pdo->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
    @unlink($keks);
};

try {

/* ---- Teil 0 — Das amtliche Schema ---------------------------------------- */

echo "\n  Teil 0 — GPX 1.1 gegen das amtliche Schema\n";

/* DIE SUMME ZUERST. Ein Schemalauf gegen ein veraendertes Schema belegt
 * nichts — und ein Schema, das jemand „passend gemacht" hat, faellt sonst
 * niemandem auf. Die Datei liegt hier byteweise so, wie sie von
 * topografix.com kam; die Herkunft steht in LIESMICH.md und docs/Lizenzen.md. */
$sha = is_file(GPX_XSD) ? hash_file('sha256', GPX_XSD) : '';
pruefe($sha === GPX_XSD_SHA,
       'Das vendorierte XSD ist unveraendert',
       $sha === GPX_XSD_SHA ? substr($sha, 0, 16) . '… (26 665 Byte)'
                            : 'SHA-256 weicht ab: ' . substr($sha, 0, 16) . '…');

/** Gegen das amtliche Schema. @return list<string> leer = gueltig */
function gpx_schema_pruefen(string $xml): array {
    $vorher = libxml_use_internal_errors(true);
    $doc = new DOMDocument();
    $f = [];
    if (!$doc->loadXML($xml)) { $f[] = 'nicht wohlgeformt'; }
    elseif (!$doc->schemaValidate(GPX_XSD)) {
        foreach (libxml_get_errors() as $e) { $f[] = trim($e->message); }
        if (!$f) { $f[] = 'ungueltig ohne Meldung'; }
    }
    libxml_clear_errors();
    libxml_use_internal_errors($vorher);
    return $f;
}

/* ---- Teil 1 — Anmeldung und Datentrennung -------------------------------- */

echo "\n  Teil 1 — Ohne Anmeldung nichts, mit Anmeldung nur das Eigene\n";

$a = ruf('gpx.php?art=mission&id=1');
pruefe($a['code'] !== 200,
       'Unangemeldet liefert der Abruf keine Datei',
       'HTTP ' . $a['code']);

ruf('login.php');                             // Sitzung und CSRF holen
$an = ruf('login.php', ['email' => $email, 'token' => $token]);
pruefe($an['code'] === 302 || $an['code'] === 200,
       'Anmeldung geht durch', 'HTTP ' . $an['code']);

/* Ein Einsatz eines FREMDEN Kontos — der Referenzbestand hat welche. */
$fremd = (int)$pdo->query('SELECT tb.owner_id FROM track_blobs tb
                            JOIN missions m ON m.id = tb.owner_id
                           WHERE tb.owner_type = "mission" LIMIT 1')->fetchColumn();
$b = ruf('gpx.php?art=mission&id=' . $fremd);
pruefe($b['code'] === 404,
       'Ein fremder Einsatz ergibt 404, nicht 403',
       "Einsatz $fremd -> HTTP {$b['code']} (403 verriete, dass es ihn gibt)");

/* ---- Teil 2 — Punkt fuer Punkt gegen den Referenzexport ------------------ */

echo "\n  Teil 2 — Serverseitig gebaut gegen browserseitig gebaut\n";

/* DIE STAERKSTE PRUEFUNG DIESES PAKETS. Der eingecheckte Referenzexport
 * enthaelt die GPX-Dateien, die `assets/export.js` gebaut hat — eine voellig
 * andere Umsetzung, in einer anderen Sprache, im Browser. Stimmen beide
 * ueberein, ist das mehr wert als ein Schemalauf.
 *
 * SIE HAT SICH EINMAL SELBST BETROGEN, und deshalb zaehlt sie seit R64/AP4
 * mit (Fund F-R64-04). Die Zuordnung laeuft ueber die INTERNE Kennung im
 * Dateinamen; was sie nicht wiederfindet, uebersprang die Schleife STILL.
 * Nach dem Neuaufbau des Referenzbestands verglich sie noch EINE Datei
 * statt 171 und meldete trotzdem „0 Abweichungen". Eine Untergrenze allein
 * hilft nicht, weil die Zahl legitim schwankt: Sobald der Nachlauf eine Spur
 * verdichtet hat, ist sie nicht mehr roh und gehoert uebersprungen.
 *
 * Was NICHT schwanken darf, ist die Zuordnung selbst: Jede GPX-Datei des
 * Referenzexports MUSS eine Zeile im Demo-Konto haben. Findet sie keine, ist
 * die Referenz aelter als die Datenbank — und dann misst dieser Teil etwas
 * anderes als bestellt. Beides steht jetzt als eigene Erwartung da, mit
 * Zahl. */
$zipPfad = glob(dirname(__DIR__) . '/referenzdatensatz/referenz/*csv*.zip')[0] ?? null;
$vergleiche = 0; $abweichungen = []; $dateien = 0;
$uebersprungenStufe = 0;   // verdichtet -> zu Recht uebersprungen
$uebersprungenFehlt = 0;   // keine Zeile im Konto -> Referenz und Bestand passen nicht
$uebersprungenName  = 0;   // Dateiname ohne Kennung -> Format geaendert
if ($zipPfad === null) {
    pruefe(false, 'Referenzexport gefunden', 'kein *csv*.zip unter tools/referenzdatensatz/referenz/');
} else {
    $zip = new ZipArchive();
    $zip->open($zipPfad);
    $demo = (int)$pdo->query('SELECT id FROM users WHERE email = "demo@gen-em.org"')->fetchColumn();
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!str_ends_with($name, '.gpx')) { continue; }
        if (!preg_match('~/(mission|rest)_(\d+)_~', $name, $m)) { $uebersprungenName++; continue; }
        $typ = $m[1] === 'mission' ? 'mission' : 'rest';
        $id  = (int)$m[2];

        // Nur Spuren, die es hier noch gibt und die NICHT ausgeduennt sind:
        // eine ausgeduennte hat zu Recht andere Punkte.
        $stand = spur_stand($pdo, $typ, $id);
        $t = $typ === 'mission' ? 'missions' : 'rest_segments';
        $q = $pdo->prepare("SELECT COUNT(*) FROM `$t` WHERE id = ? AND user_id = ?");
        $q->execute([$id, $demo]);
        if (!(int)$q->fetchColumn()) { $uebersprungenFehlt++; continue; }
        // Eine verdichtete Spur hat zu Recht andere Punkte. Das ist der EINE
        // Grund, aus dem hier etwas uebersprungen werden darf -- und er wird
        // gezaehlt, damit „0 Abweichungen" nicht „0 Vergleiche" heissen kann.
        if ($stand['stufe'] !== SPUR_STUFE_ROH) { $uebersprungenStufe++; continue; }

        $sollXml = $zip->getFromIndex($i);
        $soll = gpx_punkte($sollXml);
        $ist  = gpx_punkte(gpx_bauen(spur_lesen($pdo, $typ, $id), 'x', $stand['stufe']));
        $dateien++;
        if (count($soll) !== count($ist)) {
            $abweichungen[] = "$name: " . count($soll) . ' gegen ' . count($ist) . ' Punkte';
            continue;
        }
        foreach ($soll as $k => $ps) {
            $pi = $ist[$k];
            $vergleiche += 4;
            if (abs($ps[0] - $pi[0]) > 1e-9 || abs($ps[1] - $pi[1]) > 1e-9) {
                $abweichungen[] = "$name Punkt $k: Ort";
                break;
            }
            if (($ps[2] === null) !== ($pi[2] === null)
                || ($ps[2] !== null && abs($ps[2] - $pi[2]) > 1e-9)) {
                $abweichungen[] = "$name Punkt $k: Hoehe " . var_export($ps[2], true)
                                . ' gegen ' . var_export($pi[2], true);
                break;
            }
            if ($ps[3] !== $pi[3]) {
                $abweichungen[] = "$name Punkt $k: Zeit " . var_export($ps[3], true)
                                . ' gegen ' . var_export($pi[3], true);
                break;
            }
        }
    }
    /* GEGENPROBE: Sind die browsergebauten Referenzdateien selbst gueltig?
     * Wenn nicht, waere der Vergleich oben ein Vergleich gegen etwas
     * Kaputtes — und der Export haette seit je ungueltige Dateien geliefert. */
    $refUngueltig = 0; $refGeprueft = 0; $refErste = null;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $name = $zip->getNameIndex($i);
        if (!str_ends_with($name, '.gpx')) { continue; }
        $refGeprueft++;
        $ff = gpx_schema_pruefen($zip->getFromIndex($i));
        if ($ff) { $refUngueltig++; if ($refErste === null) { $refErste = "$name: {$ff[0]}"; } }
    }
    $zip->close();
    pruefe($refUngueltig === 0,
           'Auch die browsergebauten Referenzdateien sind schemagueltig',
           $refUngueltig === 0 ? "$refGeprueft Dateien, 0 ungueltig"
                               : "$refUngueltig von $refGeprueft — erste: $refErste");
    pruefe($uebersprungenFehlt === 0,
           'Jede GPX-Datei des Referenzexports hat eine Zeile im Demo-Konto',
           $uebersprungenFehlt === 0
               ? "$refGeprueft Dateien zugeordnet, 0 ohne Gegenstueck"
               : "$uebersprungenFehlt von $refGeprueft ohne Gegenstueck — die "
                 . 'Referenz ist aelter als die Datenbank; dieser Teil misst dann '
                 . 'etwas anderes als bestellt');
    pruefe($uebersprungenName === 0,
           'Jeder GPX-Dateiname traegt seine Kennung',
           "$uebersprungenName Namen ohne Kennung");
    pruefe($dateien > 0 && !$abweichungen,
           'Jeder Punkt stimmt mit der Browserfassung ueberein',
           $abweichungen
               ? count($abweichungen) . ' Abweichungen — erste: ' . $abweichungen[0]
               : "$dateien von $refGeprueft Dateien verglichen ($vergleiche "
                 . "Einzelvergleiche, 0 Abweichungen); uebersprungen: "
                 . "$uebersprungenStufe verdichtet");
}

/* ---- Teil 3 — Struktur und Kennzeichnung, je Stufe ----------------------- */

echo "\n  Teil 3 — Struktur und Kennzeichnung ueber den ECHTEN Abrufweg\n";

/* Eine eigene Spur je Stufe, im eigenen Konto — damit der Abruf ueber HTTP
 * laufen kann, ohne fremde Daten anzufassen. */
$pdo->prepare("INSERT INTO days (user_id, day) VALUES (?, '2026-03-01')")->execute([$uid]);
$dayId = (int)$pdo->lastInsertId();
$eigene = [];
foreach ([SPUR_STUFE_ROH, SPUR_STUFE_DUENN] as $stufe) {
    $pdo->prepare("INSERT INTO missions (user_id, client_ref, day_id, started_at, ended_at, final, origin)
                   VALUES (?, ?, ?, '2026-03-01T06:00:00', '2026-03-01T08:00:00', 1, 'manual')")
        ->execute([$uid, 'gpxprobe-' . $stufe, $dayId]);
    $mid = (int)$pdo->lastInsertId();
    $punkte = [];
    for ($i = 0; $i < 300; $i++) {
        $punkte[] = [$i, 47.0 + $i * 0.0002 + ($i % 7 === 0 ? 0.00004 : 0),
                     11.0 + $i * 0.0001, 700.0 + ($i % 11), 1772000000 + $i * 10];
    }
    if ($stufe === SPUR_STUFE_DUENN) {
        $behalten = spur_ausduennen($punkte, []);
        $duenn = []; foreach ($behalten as $k) { $duenn[] = $punkte[$k]; }
        spur_blob_schreiben($pdo, 'mission', $mid,
            spur_kodieren($duenn, SPUR_STUFE_DUENN, count($punkte)),
            SPUR_STUFE_DUENN, count($punkte), count($duenn));
        $eigene[$stufe] = ['id' => $mid, 'n' => count($duenn), 'n0' => count($punkte)];
    } else {
        spur_blob_schreiben($pdo, 'mission', $mid,
            spur_kodieren($punkte, SPUR_STUFE_ROH, count($punkte)),
            SPUR_STUFE_ROH, count($punkte), count($punkte));
        $eigene[$stufe] = ['id' => $mid, 'n' => count($punkte), 'n0' => count($punkte)];
    }
}

foreach ($eigene as $stufe => $e) {
    $wort = $stufe === SPUR_STUFE_DUENN ? 'ausgeduennt' : 'Original';
    $r = ruf('gpx.php?art=mission&id=' . $e['id']);
    pruefe($r['code'] === 200, "$wort: Abruf liefert eine Datei", 'HTTP ' . $r['code']);

    $schema = gpx_schema_pruefen($r['leib']);
    pruefe(!$schema, "$wort: gueltig gegen das amtliche GPX-1.1-Schema",
           $schema ? count($schema) . ' — erste: ' . $schema[0]
                   : strlen($r['leib']) . ' Byte, ' . $e['n'] . ' Punkte');

    $fehler = gpx_struktur_pruefen($r['leib']);
    pruefe(!$fehler, "$wort: auch die eigene Strukturpruefung ohne Beanstandung",
           $fehler ? count($fehler) . ' — erste: ' . $fehler[0] : '');

    pruefe(count(gpx_punkte($r['leib'])) === $e['n'],
           "$wort: Punktzahl entspricht der Stufe",
           count(gpx_punkte($r['leib'])) . ' von ' . $e['n0'] . ' Originalpunkten');

    $soll = gpx_stufe_text($stufe);
    $treffer = substr_count($r['leib'], '<desc>' . htmlspecialchars($soll, ENT_QUOTES | ENT_XML1, 'UTF-8'));
    pruefe($treffer === 2,
           "$wort: Kennzeichnung steht in <metadata> UND in <trk>",
           "$treffer von 2 (\"$soll\")");

    preg_match('/filename="([^"]*)"/', $r['kopf'], $m);
    $datei = $m[1] ?? '';
    pruefe(str_contains($datei, gpx_stufe_marke($stufe)),
           "$wort: der DATEINAME traegt sie ebenfalls", $datei);
    pruefe((bool)preg_match('/^[A-Za-z0-9._-]+$/', $datei),
           "$wort: Dateiname nur aus unbedenklichen Zeichen");
    pruefe(str_contains($r['kopf'], 'Content-Disposition: attachment'),
           "$wort: wird als Anhang ausgeliefert, nicht angezeigt");
}

/* Die Kennzeichnung muss die Stufen UNTERSCHEIDEN — sonst stuende zweimal
 * dasselbe da und die Erwartung oben ginge trotzdem auf. */
pruefe(gpx_stufe_text(SPUR_STUFE_ROH) !== gpx_stufe_text(SPUR_STUFE_DUENN)
       && gpx_stufe_marke(SPUR_STUFE_ROH) !== gpx_stufe_marke(SPUR_STUFE_DUENN),
       'Die beiden Kennzeichnungen sind verschieden',
       gpx_stufe_text(SPUR_STUFE_ROH) . ' / ' . gpx_stufe_text(SPUR_STUFE_DUENN));

/* ---- Teil 4 — Grenzfaelle ------------------------------------------------ */

echo "\n  Teil 4 — Grenzfaelle\n";

$pdo->prepare("INSERT INTO missions (user_id, client_ref, day_id, started_at, final, origin)
               VALUES (?, 'gpxprobe-leer', ?, '2026-03-01T09:00:00', 1, 'manual')")
    ->execute([$uid, $dayId]);
$leer = (int)$pdo->lastInsertId();
$r = ruf('gpx.php?art=mission&id=' . $leer);
pruefe($r['code'] === 404 && str_contains($r['leib'], 'keine_spur'),
       'Ein Einsatz ohne Spur ergibt kein leeres GPX',
       'HTTP ' . $r['code'] . ' ' . trim(substr($r['leib'], 0, 40)));

$r = ruf('gpx.php?art=unfug&id=1');
pruefe($r['code'] === 400, 'Eine unbekannte Art wird abgewiesen', 'HTTP ' . $r['code']);
$r = ruf('gpx.php?art=mission&id=0');
pruefe($r['code'] === 400, 'Kennung 0 wird abgewiesen', 'HTTP ' . $r['code']);
$r = ruf('gpx.php?art=mission&id=999999999');
pruefe($r['code'] === 404, 'Eine Kennung, die es nicht gibt, ergibt 404', 'HTTP ' . $r['code']);

/* DER ABRUF LIEGT NICHT UNTER api/ — und das ist keine Geschmacksfrage.
 * `ist_api_aufruf()` entscheidet allein am Pfad: enthaelt er `/api/`, bekommt
 * eine abgelaufene Sitzung JSON 401 statt der Anmeldeseite. Der GPX-Abruf ist
 * der erste Link der Oberflaeche, den eine Nutzerin selbst anklickt; nach
 * einer Mittagspause saehe sie sonst `{"error":"session_ende"}` im Fenster. */
$r = ruf('api/gpx.php?art=mission&id=' . $eigene[SPUR_STUFE_ROH]['id']);
pruefe($r['code'] === 404,
       'Unter api/ gibt es den Abruf NICHT (Sitzungsende waere sonst JSON)',
       'HTTP ' . $r['code']);

/* RATENSCHUTZ: Nur Fehlgriffe zaehlen. Zehn davon, dann Ruhe — ein gelungener
 * Abruf darf nicht aufs Kontingent gehen, sonst traefe die Bremse die
 * Spurenseite eines Tages mit zwoelf Eintraegen. */
$codes = [];
for ($i = 0; $i < 12; $i++) {
    $codes[] = ruf('gpx.php?art=mission&id=' . (900000000 + $i))['code'];
}
pruefe(in_array(429, $codes, true),
       'Wiederholte Fehlgriffe laufen in den Ratenschutz',
       implode(' ', $codes));
/* Und danach wieder freigeben, damit die folgenden Teile messen koennen. */
$pdo->prepare("DELETE FROM rate_limits WHERE topf = 'pair'")->execute();
$r = ruf('gpx.php?art=mission&id=' . $eigene[SPUR_STUFE_ROH]['id']);
pruefe($r['code'] === 200,
       'Ein gelungener Abruf geht nicht aufs Kontingent',
       'HTTP ' . $r['code'] . ' nach dem Zuruecksetzen des Zaehlers');

/* Papierkorb: derselbe Einsatz, der eben noch ging. */
$pdo->prepare('UPDATE missions SET deleted_at = UTC_TIMESTAMP() WHERE id = ?')
    ->execute([$eigene[SPUR_STUFE_ROH]['id']]);
$r = ruf('gpx.php?art=mission&id=' . $eigene[SPUR_STUFE_ROH]['id']);
pruefe($r['code'] === 404,
       'Ein Einsatz im Papierkorb ist fuer den Abruf nicht vorhanden',
       'HTTP ' . $r['code'] . ' (vorher 200)');
$pdo->prepare('UPDATE missions SET deleted_at = NULL WHERE id = ?')
    ->execute([$eigene[SPUR_STUFE_ROH]['id']]);

/* ---- Teil 5 — Sichtbar auf der Seite, nicht nur in der Datei ------------- */

echo "\n  Teil 5 — Die Kennzeichnung ist VOR dem Herunterladen sichtbar (E-S2-09)\n";

/* Die Abnahme verlangt „Kennzeichnung sichtbar". Eine Auszeichnung, die nur
 * in der Datei steht, sieht erst, wer sie schon heruntergeladen hat. Geprueft
 * wird deshalb das ausgelieferte HTML der Einsatzansicht. */
foreach ($eigene as $stufe => $e) {
    $wort = $stufe === SPUR_STUFE_DUENN ? 'ausgeduennt' : 'Original';
    $seite = ruf('einsatz.php?id=' . $e['id']);
    pruefe($seite['code'] === 200, "$wort: Einsatzansicht laedt", 'HTTP ' . $seite['code']);

    $link = 'gpx.php?art=mission&amp;id=' . $e['id'];
    pruefe(str_contains($seite['leib'], $link) || str_contains($seite['leib'], 'gpx.php?art=mission&id=' . $e['id']),
           "$wort: der Abruf steht im Aktionsmenue",
           str_contains($seite['leib'], 'Spur als GPX') ? '„Spur als GPX" gefunden' : 'NICHT gefunden');

    /* Die Plakette entsteht im Browser aus der Konstante SPUR; im HTML steht
     * deshalb die Konstante, nicht das fertige Etikett. Geprueft wird sie —
     * sie ist die Quelle, aus der die Plakette gebaut wird. */
    pruefe((bool)preg_match('/const SPUR = (\{[^;]*\});/', $seite['leib'], $mm),
           "$wort: der Spurzustand steht auf der Seite", $mm[1] ?? 'nicht gefunden');
    if (isset($mm[1])) {
        $sp = json_decode($mm[1], true);
        pruefe(is_array($sp) && ($sp['stufe'] ?? 0) === $stufe && ($sp['n'] ?? 0) === $e['n'],
               "$wort: und nennt Stufe und Punktzahl richtig",
               'stufe ' . ($sp['stufe'] ?? '?') . ', n ' . ($sp['n'] ?? '?')
               . ' (erwartet ' . $stufe . '/' . $e['n'] . ')');
    }
}

/* Kein toter Eintrag: Ein Einsatz ohne Spur bietet den Abruf gar nicht an. */
$seite = ruf('einsatz.php?id=' . $leer);
pruefe($seite['code'] === 200 && !str_contains($seite['leib'], 'Spur als GPX'),
       'Ohne Spur gibt es den Menueeintrag nicht',
       'HTTP ' . $seite['code'] . ', „Spur als GPX" '
       . (str_contains($seite['leib'], 'Spur als GPX') ? 'STEHT DA' : 'fehlt — richtig'));

/* ---- Teil 6 — Die Spurenseite des Diensttages (E-S2-09: je Ruhesegment) -- */

echo "\n  Teil 6 — Auch Ruhesegmente haben einen Abruf (tag_spuren.php)\n";

/* RUHESEGMENTE HATTEN BIS AP4 KEINE STELLE in der Oberflaeche: In der
 * Tagesansicht sind sie nur eine schwarze Linie, ohne Zeile und ohne Popup.
 * Die Spurenseite gibt ihnen dieselbe Identitaet wie den Einsaetzen — sie ist
 * der Grund, warum die Abnahme „je Einsatz UND je Ruhesegment" erfuellbar ist. */
$pdo->prepare("INSERT INTO rest_segments (user_id, client_ref, day_id, started_at, ended_at, final)
               VALUES (?, 'gpxprobe-ruhe', ?, '2026-03-01T10:00:00', '2026-03-01T11:00:00', 1)")
    ->execute([$uid, $dayId]);
$rid = (int)$pdo->lastInsertId();
$rp = [];
for ($i = 0; $i < 80; $i++) {
    $rp[] = [$i, 47.5 + $i * 0.0003, 11.5 + $i * 0.0002, 800.0, 1772100000 + $i * 10];
}
spur_blob_schreiben($pdo, 'rest', $rid, spur_kodieren($rp, SPUR_STUFE_ROH, 80),
                    SPUR_STUFE_ROH, 80, 80);

$seite = ruf('tag_spuren.php?d=' . $dayId);
pruefe($seite['code'] === 200, 'Die Spurenseite laedt', 'HTTP ' . $seite['code']);
pruefe(str_contains($seite['leib'], 'art=rest&amp;id=' . $rid)
       || str_contains($seite['leib'], 'art=rest&id=' . $rid),
       'Das Ruhesegment hat dort einen eigenen GPX-Abruf', "Ruhesegment $rid");
foreach ($eigene as $stufe => $e) {
    pruefe(str_contains($seite['leib'], 'art=mission&amp;id=' . $e['id'])
           || str_contains($seite['leib'], 'art=mission&id=' . $e['id']),
           'Auch der Einsatz (Stufe ' . $stufe . ') steht in der Liste', 'Einsatz ' . $e['id']);
}
pruefe(str_contains($seite['leib'], 'keine Spur'),
       'Ein Eintrag ohne Spur steht da, aber ohne Abruf',
       'Plakette „keine Spur" gefunden');
pruefe(substr_count($seite['leib'], 'data-spur=') >= 4,
       'Jede Zeile ist mit der Karte verknuepfbar',
       substr_count($seite['leib'], 'data-spur=') . ' Zeilen mit data-spur');

$r = ruf('gpx.php?art=rest&id=' . $rid);
pruefe($r['code'] === 200, 'Der Abruf eines Ruhesegments liefert eine Datei',
       'HTTP ' . $r['code'] . ', ' . strlen($r['leib']) . ' Byte');
$schema = gpx_schema_pruefen($r['leib']);
pruefe(!$schema, 'und sie ist schemagueltig',
       $schema ? $schema[0] : count(gpx_punkte($r['leib'])) . ' Punkte');
preg_match('/filename="([^"]*)"/', $r['kopf'], $mm);
pruefe(str_starts_with($mm[1] ?? '', 'ruhezeit_'),
       'Der Dateiname sagt, dass es eine Ruhezeit ist', $mm[1] ?? '—');

/* Ein fremder Diensttag ist auch hier nicht zu haben. */
$fremdTag = (int)$pdo->query('SELECT id FROM days WHERE user_id <> ' . $uid . ' LIMIT 1')->fetchColumn();
if ($fremdTag) {
    $r = ruf('tag_spuren.php?d=' . $fremdTag);
    pruefe($r['code'] === 404 || str_contains($r['leib'], 'keinen Diensttag'),
           'Ein fremder Diensttag ist nicht einsehbar',
           'HTTP ' . $r['code']);
}

/* ---- Teil 7 — Mehrfachauswahl: mehrere Spuren in EINER Datei ------------- */

echo "\n  Teil 7 — Mehrere ausgewaehlte Spuren als eine Datei\n";

/* WOFUER. Wer eine ganze Schicht in ein Kartenprogramm ziehen will, laedt
 * sonst zwoelf Dateien einzeln herunter. Die Auswahl macht daraus eine —
 * und die Frage, die dabei schiefgehen kann, ist NICHT „sind alle Punkte
 * drin", sondern „stehen sie als mehrere <trk> nebeneinander". Zwei Spuren in
 * EIN <trkseg> geschrieben ergibt eine Datei, die jedes Kartenprogramm
 * klaglos oeffnet und in der es eine gerade Linie quer ueber das Land zieht,
 * vom Ende der einen zum Anfang der naechsten. */

/* Zaehler zuruecksetzen: Teil 7 greift absichtlich einige Male daneben. */
$pdo->prepare("DELETE FROM rate_limits WHERE topf = 'pair'")->execute();

$wahl = ['mission-' . $eigene[SPUR_STUFE_ROH]['id'],
         'mission-' . $eigene[SPUR_STUFE_DUENN]['id'],
         'rest-' . $rid];
$r = ruf('gpx.php?tag=' . $dayId . '&auswahl=' . implode(',', $wahl));
pruefe($r['code'] === 200, 'Eine Auswahl von drei Spuren liefert eine Datei',
       'HTTP ' . $r['code'] . ', ' . strlen($r['leib']) . ' Byte');

$schema = gpx_schema_pruefen($r['leib']);
pruefe(!$schema, 'und sie ist gegen das amtliche Schema gueltig',
       $schema ? $schema[0] : 'gueltig');
$struktur = gpx_struktur_pruefen($r['leib']);
pruefe(!$struktur, 'und auch die Strukturpruefung ist zufrieden',
       $struktur ? $struktur[0] : 'keine Beanstandung');

$trks = gpx_trks($r['leib']);
pruefe(count($trks) === 3, 'Sie enthaelt DREI <trk>, nicht eine zusammengeklebte Spur',
       count($trks) . ' <trk>');
pruefe(count(array_filter($trks, fn($t) => $t['segmente'] === 1)) === count($trks),
       'Jede Spur hat genau EIN <trkseg>',
       implode('/', array_column($trks, 'segmente')) . ' Segmente je Spur');

/* PUNKT FUER PUNKT gegen die Einzelabrufe. Die Auswahl darf nichts anderes
 * liefern als das, was drei einzelne Abrufe liefern — nur in einer Datei. */
$einzelPunkte = [];
foreach ([[
    'mission', $eigene[SPUR_STUFE_ROH]['id']], ['mission', $eigene[SPUR_STUFE_DUENN]['id']],
    ['rest', $rid]] as [$a, $i]) {
    $e = ruf('gpx.php?art=' . $a . '&id=' . $i);
    $einzelPunkte[$a . '-' . $i] = gpx_punkte($e['leib']);
}
$summe = array_sum(array_map('count', $einzelPunkte));
$inDatei = array_sum(array_map(fn($t) => count($t['punkte']), $trks));
pruefe($summe === $inDatei && $summe > 0,
       'Die Punktzahl der Auswahl ist die Summe der Einzelabrufe',
       $inDatei . ' gegen ' . $summe . ' Punkte');

$vgl = 0; $abw = null;
foreach ($trks as $t) {
    /* Der Name traegt die Kennung — dieselbe Stelle wie beim Einzelabruf. */
    if (!preg_match('/^(Einsatz|Ruhezeit) (\d+)/', $t['name'], $mm)) {
        $abw = 'Name ohne Kennung: ' . $t['name']; break;
    }
    $schl = ($mm[1] === 'Einsatz' ? 'mission-' : 'rest-') . $mm[2];
    if (!isset($einzelPunkte[$schl])) { $abw = "Spur $schl war gar nicht gewaehlt"; break; }
    $soll = $einzelPunkte[$schl];
    if (count($soll) !== count($t['punkte'])) {
        $abw = "$schl: " . count($soll) . ' gegen ' . count($t['punkte']) . ' Punkte'; break;
    }
    foreach ($soll as $k => $ps) {
        $pi = $t['punkte'][$k];
        $vgl += 4;
        if (abs($ps[0] - $pi[0]) > 1e-9 || abs($ps[1] - $pi[1]) > 1e-9
            || ($ps[2] === null) !== ($pi[2] === null)
            || ($ps[2] !== null && abs($ps[2] - $pi[2]) > 1e-9)
            || $ps[3] !== $pi[3]) { $abw = "$schl Punkt $k"; break 2; }
    }
}
pruefe($abw === null && $vgl > 0,
       'Jeder Punkt ist derselbe wie im Einzelabruf',
       $abw === null ? "$vgl Einzelvergleiche, 0 Abweichungen" : 'Abweichung: ' . $abw);

/* DIE STUFE STEHT AN JEDER SPUR, nicht nur ueber der Datei — sonst waere die
 * Kennzeichnung aus E-S2-09 in einer gemischten Auswahl verloren. */
$mitDuenn = array_filter($trks, fn($t) => str_contains($t['desc'], 'ausgedünnt'));
$mitRoh   = array_filter($trks, fn($t) => str_contains($t['desc'], 'Originalspur'));
pruefe(count($mitDuenn) === 1 && count($mitRoh) === 2,
       'Jede Spur nennt ihre eigene Stufe',
       count($mitDuenn) . '× ausgeduennt, ' . count($mitRoh) . '× Original');

$kopf = gpx_kopf_desc($r['leib']);
pruefe(str_contains($kopf, '3 Spuren') && str_contains($kopf, 'teils ausgedünnt'),
       'Der Kopf sagt, was die Datei als Ganzes ist', $kopf);

preg_match('/filename="([^"]*)"/', $r['kopf'], $mm);
pruefe(str_starts_with($mm[1] ?? '', 'diensttag_2026-03-01_3-spuren')
       && str_contains($mm[1] ?? '', 'gemischt'),
       'Der Dateiname nennt Tag, Anzahl und die gemischte Stufe', $mm[1] ?? '—');

/* CHRONOLOGISCH. Die Seite gruppiert nach Einsaetzen und Ruhezeiten; die
 * Datei folgt der Zeit. Geprueft wird gegen die Datenbank, nicht gegen eine
 * erwartete Liste — sonst prueft der Test seine eigene Annahme. */
$sollFolge = [];
foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$a, $tab]) {
    $q = $pdo->prepare("SELECT id, started_at FROM `$tab` WHERE user_id = ? AND day_id = ?");
    $q->execute([$uid, $dayId]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $z) {
        if (in_array($a . '-' . (int)$z['id'], $wahl, true)) {
            $sollFolge[] = [(string)$z['started_at'], $a, (int)$z['id'],
                            $a . '-' . (int)$z['id']];
        }
    }
}
usort($sollFolge, fn($x, $y) => [$x[0], $x[1], $x[2]] <=> [$y[0], $y[1], $y[2]]);
$istFolge = array_map(function ($t) {
    preg_match('/^(Einsatz|Ruhezeit) (\d+)/', $t['name'], $m);
    return ($m[1] === 'Einsatz' ? 'mission-' : 'rest-') . $m[2];
}, $trks);
pruefe($istFolge === array_column($sollFolge, 3),
       'Die Spuren stehen chronologisch, wie der Tag verlaufen ist',
       implode(' ', $istFolge));

/* GRENZFAELLE DER AUSWAHL. */
$r2 = ruf('gpx.php?tag=' . $dayId . '&auswahl=' . implode(',', $wahl) . ',mission-' . $leer);
pruefe($r2['code'] === 200 && count(gpx_trks($r2['leib'])) === 3,
       'Ein ausgewaehlter Eintrag ohne Spur erzeugt kein leeres <trk>',
       'HTTP ' . $r2['code'] . ', ' . count(gpx_trks($r2['leib'])) . ' <trk>');

$r2 = ruf('gpx.php?tag=' . $dayId . '&auswahl=mission-' . $fremd . ',rest-' . $rid);
pruefe($r2['code'] === 200 && count(gpx_trks($r2['leib'])) === 1,
       'Eine fremde Kennung faellt heraus, die eigene bleibt',
       'HTTP ' . $r2['code'] . ', ' . count(gpx_trks($r2['leib'])) . ' <trk>');

$r2 = ruf('gpx.php?tag=' . $dayId . '&auswahl=mission-' . $fremd);
pruefe($r2['code'] === 404, 'Eine Auswahl aus lauter fremden Kennungen ergibt 404',
       'HTTP ' . $r2['code']);

$r2 = ruf('gpx.php?tag=' . $dayId . '&auswahl=mission-abc');
pruefe($r2['code'] === 400, 'Eine unsinnige Auswahl wird abgewiesen', 'HTTP ' . $r2['code']);

$r2 = ruf('gpx.php?tag=' . $dayId . '&auswahl='
          . implode(',', array_fill(0, GPX_AUSWAHL_MAX + 1, 'mission-1')));
pruefe($r2['code'] === 400, 'Mehr als ' . GPX_AUSWAHL_MAX . ' Spuren auf einmal: abgewiesen',
       'HTTP ' . $r2['code']);

$fremdTag2 = (int)$pdo->query('SELECT id FROM days WHERE user_id <> ' . $uid . ' LIMIT 1')->fetchColumn();
if ($fremdTag2) {
    $r2 = ruf('gpx.php?tag=' . $fremdTag2 . '&auswahl=mission-1');
    pruefe($r2['code'] === 404, 'Ein fremder Diensttag liefert auch als Auswahl nichts',
           'HTTP ' . $r2['code']);
}

/* DER SPEICHER. Die Datei entsteht vollstaendig im Arbeitsspeicher, weil ihre
 * Laenge in die Kopfzeile gehoert — die Mengengrenze (GPX_AUSWAHL_MAX) ist
 * genau dafuer da. Hier wird nachgerechnet, was sie wirklich kostet: der
 * Bibliotheksweg mit der groessten Spur des Bestandes, so oft wiederholt, wie
 * die Grenze erlaubt. */
$groesste = $pdo->query('SELECT owner_type, owner_id, n_gespeichert FROM track_blobs
                          ORDER BY n_gespeichert DESC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
if ($groesste) {
    $pk = spur_lesen($pdo, (string)$groesste['owner_type'], (int)$groesste['owner_id']);
    $vor = memory_get_peak_usage(true);
    $folge = (function () use ($pk) {
        for ($i = 0; $i < GPX_AUSWAHL_MAX; $i++) {
            yield ['punkte' => $pk, 'name' => 'Spur ' . $i,
                   'stufe' => SPUR_STUFE_ROH, 'n_original' => count($pk)];
        }
    })();
    $gross = gpx_bauen_viele($folge, 'Lasttest');
    $spitze = memory_get_peak_usage(true);
    pruefe($spitze < 64 * 1024 * 1024,
           GPX_AUSWAHL_MAX . ' Spuren der groessten Art bleiben im Speicherbudget',
           sprintf('%d Punkte je Spur, Datei %.1f MB, Spitze %.1f MB von 64 MB (vorher %.1f MB)',
                   count($pk), strlen($gross) / 1048576,
                   $spitze / 1048576, $vor / 1048576));
    unset($gross, $pk);
}

/* DIE REIHENFOLGE AUF DER SEITE IST DIESELBE WIE IN DER DATEI.
 *
 * Damit die Frage ueberhaupt eine Antwort hat, braucht der Tag ein
 * Ruhesegment VOR den Einsaetzen: Lagen alle Ruhezeiten hinter allen
 * Einsaetzen, saehe eine nach Art gruppierte Liste genauso aus wie eine
 * chronologische, und die Pruefung belegte nichts. */
$pdo->prepare("INSERT INTO rest_segments (user_id, client_ref, day_id, started_at, ended_at, final)
               VALUES (?, 'gpxprobe-frueh', ?, '2026-03-01T05:00:00', '2026-03-01T05:50:00', 1)")
    ->execute([$uid, $dayId]);
$rFrueh = (int)$pdo->lastInsertId();
$fp = [];
for ($i = 0; $i < 40; $i++) {
    $fp[] = [$i, 47.4 + $i * 0.0002, 11.4 + $i * 0.0002, 790.0, 1772090000 + $i * 10];
}
spur_blob_schreiben($pdo, 'rest', $rFrueh, spur_kodieren($fp, SPUR_STUFE_ROH, 40),
                    SPUR_STUFE_ROH, 40, 40);

$seite = ruf('tag_spuren.php?d=' . $dayId);
preg_match_all('/data-spurwahl value="([^"]+)"/', $seite['leib'], $mm);
$seitenFolge = $mm[1];
$sollAlle = [];
foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$a, $tab]) {
    $q = $pdo->prepare("SELECT id, started_at FROM `$tab`
                         WHERE user_id = ? AND day_id = ? AND deleted_at IS NULL");
    $q->execute([$uid, $dayId]);
    foreach ($q->fetchAll(PDO::FETCH_ASSOC) as $z) {
        $sollAlle[] = [(string)$z['started_at'], $a, (int)$z['id'], $a . '-' . (int)$z['id']];
    }
}
usort($sollAlle, fn($x, $y) => [$x[0], $x[1], $x[2]] <=> [$y[0], $y[1], $y[2]]);
pruefe($seitenFolge === array_column($sollAlle, 3),
       'Die Liste steht chronologisch, nicht nach Art gruppiert',
       implode(' ', $seitenFolge));
pruefe($seitenFolge[0] === 'rest-' . $rFrueh,
       'Eine Ruhezeit VOR dem ersten Einsatz steht auch davor',
       'zuerst: ' . ($seitenFolge[0] ?? '—'));

/* Und die Datei folgt derselben Folge wie die Liste. */
$rAlle = ruf('gpx.php?tag=' . $dayId . '&auswahl=' . implode(',', $seitenFolge));
$folgeDatei = array_map(function ($t) {
    preg_match('/^(Einsatz|Ruhezeit) (\d+)/', $t['name'], $m);
    return ($m[1] === 'Einsatz' ? 'mission-' : 'rest-') . $m[2];
}, gpx_trks($rAlle['leib']));
$ohneLeer = array_values(array_filter($seitenFolge, fn($k) => $k !== 'mission-' . $leer));
pruefe($folgeDatei === $ohneLeer,
       'Die Datei steht in derselben Folge wie die Liste',
       implode(' ', $folgeDatei));

/* DIE AUSWAHL IST AUF DER SEITE ZU BEDIENEN, nicht nur ueber die Adresse. */
/* `data-spurwahl value=` und nicht bloss `data-spurwahl`: Der Name steht auch
 * dreimal im Skript darunter, und eine Zahl, die Kaestchen und Skriptstellen
 * zusammenzaehlt, misst etwas anderes, als ihre Beschriftung sagt. */
$kaesten = substr_count($seite['leib'], 'data-spurwahl value=');
$abgeschaltet = substr_count($seite['leib'], 'data-spurwahl value="mission-' . $leer . '" disabled');
pruefe($kaesten === count($sollAlle),
       'Jede Zeile der Spurenseite traegt ein Auswahlkaestchen',
       $kaesten . ' Kaestchen fuer ' . count($sollAlle) . ' Eintraege des Tages');
pruefe($abgeschaltet === 1,
       'Der Eintrag ohne Spur hat ein abgeschaltetes Kaestchen',
       $abgeschaltet === 1 ? 'Einsatz ' . $leer . ' ist nicht waehlbar' : 'nicht gefunden');
pruefe(str_contains($seite['leib'], 'id="f-gpxwahl"')
       && str_contains($seite['leib'], 'id="gpxleiste"'),
       'und die Sammelleiste steht darunter',
       'Formular und Leiste gefunden');
pruefe(str_contains($seite['leib'], 'name="tag" value="' . $dayId . '"'),
       'Das Formular traegt den Diensttag', 'tag=' . $dayId);

} finally {
    $aufraeumen();
    echo "\n  Konto, Spuren und Sitzung der Probe wieder entfernt.\n";
}

printf("\n  -> %d Erwartungen, %d nicht erfuellt\n", $erwartungen, $offen);
exit($offen === 0 ? 0 : 1);
