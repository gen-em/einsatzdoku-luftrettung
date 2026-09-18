<?php
declare(strict_types=1);

/**
 * SICHERHEITSKOPFZEILEN AN EINER STELLE (P5a/AP4, E-P5a-15; Backlog Nr. 8, SP-5).
 *
 * WAS BIS WEB 20.6.0 GALT. Die Anwendung setzte **keine einzige**
 * Sicherheitskopfzeile aus PHP — `grep -rn 'Content-Security-Policy' server/`
 * fand genau einen Treffer, und der war ein Kommentar. Vier Kopfzeilen kamen
 * aus der `.htaccess`, und die liest nur Apache: Auf nginx, Caddy oder
 * LiteSpeed stand die Installation ohne `nosniff`, ohne `Referrer-Policy`,
 * ohne `X-Frame-Options` und ohne HSTS da, und niemand sah es.
 *
 * ES GIBT JETZT GENAU ZWEI SAETZE, und der Unterschied ist keine Feinheit:
 *
 *   kopfzeilen_seite()   fuer alles, was HTML ausliefert — mit CSP und Nonce
 *   kopfzeilen_json()    fuer die Endpunkte — ohne CSP, weil eine JSON-Antwort
 *                        kein Dokument ist und der Browser darin nichts
 *                        ausfuehrt. `nosniff` ist dort die wichtige Zeile: Sie
 *                        verhindert, dass ein Browser eine JSON-Antwort als
 *                        HTML deutet.
 *
 * DIE CSP KOMMT ZWEISTUFIG (E-P5a-15). Zuerst `…-Report-Only` mit einem
 * Berichtsendpunkt, **zwei Wochen im Betrieb**, dann scharf ueber die
 * Einstellung `csp_scharf`. Eine CSP, die man scharf einschaltet und dann
 * eine vergessene Quelle findet, bricht eine Seite bei jemandem, der gerade
 * dokumentiert.
 *
 * DER NONCE ENTSTEHT EINMAL JE ANFRAGE und ist damit die eine Stelle, an der
 * `script-src` haengt: `'self'` fuer die 82 zitierten Skripte, `'nonce-…'`
 * fuer die 25 Inline-Bloecke. Kein `'unsafe-inline'`.
 *
 * ---------------------------------------------------------------------------
 * DREI ABWEICHUNGEN VOM BAUPLAN SP-5 — jede gemessen, jede begruendet
 * ---------------------------------------------------------------------------
 *
 * 1. `style-src`. SP-5 nennt **ein** `style=`-Attribut („das eine in
 *    index.php wird umgebaut, dann faellt 'unsafe-inline'"). Nachgemessen am
 *    15.09.2026 waren es **dreizehn**: drei im PHP-Markup
 *    (`betrieb_server.php` zweimal, `index.php` einmal) und **zehn, die die
 *    Skripte zur Laufzeit per `innerHTML` schreiben** (`geo.js`,
 *    `missiontable.js`, `schneiden.js`). Alle dreizehn tragen BERECHNETE
 *    Werte: Prozentbreiten eines Balkens, Kartenfarben, Drehwinkel — in
 *    Klassen sind sie nicht aufloesbar.
 *
 *    DREI SIND AUFGELOEST (AP4): Der Speicherbalken traegt seine Breite jetzt
 *    in `data-breite` und bekommt sie nach dem Aufbau gesetzt, der
 *    Farbstreifen der Tagesuebersicht ebenso. Die Vollstaendigkeitspruefung
 *    findet im PHP-Markup ausserhalb von `vendor/` jetzt **0** Stilattribute;
 *    was sie noch zaehlt, sind die zehn in den Skripten.
 *
 *    DIE ZEHN ANDEREN BLEIBEN, und deshalb bleibt `style-src-attr
 *    'unsafe-inline'`. Sie stecken in Leaflet-`divIcon`-Vorlagen und in
 *    Zeilenvorlagen, die mit `innerHTML` entstehen; sie umzustellen hiesse,
 *    je Pfeil auf der Spur — es sind Hunderte — einen Listener zu haengen,
 *    der nach dem Einfuegen eine Drehung setzt. Das verschoebe die Rechnung,
 *    ohne die Angriffsflaeche zu aendern: `el.style.x = …` ist CSSOM und von
 *    der CSP ohnehin nicht erfasst.
 *
 *    WAS DAS OFFEN LAESST, IST ENG. Wer HTML einschleusen kann, darf
 *    Stilattribute setzen. `style-src 'self'` bleibt dabei scharf — ein
 *    eingeschleustes `<style>` und ein fremdes Stylesheet sind weiterhin
 *    blockiert —, und Daten herausschreiben laesst sich mit einem Stilattribut
 *    nicht: `img-src` steht auf vier Kacheldomains, `default-src 'none'`
 *    sperrt alles Uebrige.
 *
 *    ZWEI DINGE IM REPORT-ONLY-LAUF BEOBACHTEN: ob Leaflet oder SheetJS
 *    selbst `style`-ATTRIBUTE per `innerHTML` schreiben (dann kaemen Berichte
 *    zu `style-src-attr`), und ob ein Browser `style-src-attr` gar nicht
 *    kennt — der faellt auf `style-src 'self'` zurueck und blockiert die zehn
 *    Stellen. Beides faellt in der Report-Only-Phase auf und nicht danach.
 *
 * 2. `connect-src`. SP-5 schreibt `https://photon.komoot.io` fest. Der
 *    Adressdienst ist seit S9 aber **je Installation einstellbar**
 *    (`geocoder_dienst()`), und genau das ist der Weg, den F-SP-4 anpreist:
 *    den eigenen Photon betreiben. Ein fester Wert braeche ihn — ohne
 *    Fehlermeldung ausser in der Konsole. Die Zeile setzt deshalb den
 *    LAUFENDEN Dienst ein, und ist die Suche aus, steht er gar nicht erst da.
 *
 * 3. `img-src data:` — HIER STAND EIN IRRTUM, UND DER REPORT-ONLY-LAUF HAT
 *    IHN GEFUNDEN. SP-5 fuehrt `data:` mit. Nachgemessen wurde zuerst im
 *    Quelltext von `server/` und `assets/style.css`: 0 Treffer, also
 *    gestrichen — mit dem Satz „der Report-Only-Lauf sagt, wenn das ein
 *    Irrtum war". Er hat es gesagt: **140 Verstoesse** auf den vier
 *    Kartenseiten (`index.php` 65, `zeitraum.php` 31, `einsatz.php` 28,
 *    `tag_spuren.php` 16), alle mit der Quelle `data`.
 *
 *    URSACHE: `leaflet.js` traegt eine eingebaute Konstante — ein 1x1
 *    Pixel grosses, durchsichtiges GIF als `data:image/gif;base64,…`
 *    (`L.Util.emptyImageUrl`). Leaflet setzt sie als `src`, wenn es eine
 *    Kachel wegraeumt. Sie steht in einer minifizierten Bibliothek und
 *    nicht in unserem Quelltext, und genau deshalb hat die Zaehlung sie
 *    nicht gesehen. `data:` steht seither drin.
 *
 *    WAS DAS KOSTET, ausgesprochen: `data:` in `img-src` erlaubt jedem
 *    eingeschleusten Markup ein beliebiges Bild aus der Zeichenkette
 *    selbst. Das ist die schwaechste Zeile dieser Richtlinie. Sie steht
 *    hier trotzdem, weil die Alternative — Leaflet patchen — eine
 *    vendorierte Bibliothek von ihrem Original entfernen wuerde, und weil
 *    ein Bild kein Skript ausfuehrt: Der Weg zum Datenschluessel bleibt
 *    ueber `script-src` verschlossen.
 *
 *    DIE EIGENTLICHE LEHRE: Eine Zaehlung im eigenen Quelltext misst nicht,
 *    was der Browser tut. Dafuer ist die Report-Only-Phase da — und dafuer
 *    muss ihr Meldeweg funktionieren (siehe `kopf_melde_url()`).
 *
 * ---------------------------------------------------------------------------
 * HSTS: EINE ZAHL, EINE STELLE
 * ---------------------------------------------------------------------------
 *
 * `Strict-Transport-Security` steht bis Web 20.6.0 in der `.htaccess` mit
 * `max-age=31536000` und `Header always set` — und `set` ERSETZT, was PHP
 * gesetzt hat. Eine Einstellung neben dieser Zeile waere auf Apache eine
 * Luege gewesen. Die Zeile ist deshalb aus der `.htaccess` verschwunden; hier
 * ist die einzige Stelle.
 *
 * DER PREIS STEHT MIT DA: Die Vorgabe ist **ein Tag** (E-P5a-15) — auf einer
 * Installation, die bisher ein Jahr gesendet hat, sinkt die Bindung damit,
 * bis jemand den Wert hochstellt. Das ist Absicht und keine Nachlaessigkeit:
 * Ein falsch gesetzter langer HSTS sperrt eine Domain aus, und zwar fuer die
 * ganze Dauer. Die Einstellung sagt es, die Statusseite sagt es, und im
 * Pruefdokument steht es als Punkt fuer die Betreiberin.
 *
 * `includeSubDomains` bleibt aussen vor, bis geklaert ist, dass keine
 * Subdomain ohne TLS laeuft (SP-5) — und die Staging-Adresse dieser
 * Auslieferungskette ist genau so eine Subdomain. Die Anschrift steht
 * absichtlich nicht hier: Sie ist eine Eigenschaft der Installation und
 * gehoert in die Geheimnisse der Kette, nicht in den Quelltext.
 *
 * ---------------------------------------------------------------------------
 * WAS DIESE DATEI NICHT TUT
 * ---------------------------------------------------------------------------
 *
 * Sie laedt nichts ausser `db.php` und `instanz_lib.php`, und sie kommt
 * **ohne Datenbank aus**: Jede Einstellung hat eine Vorgabe, und faellt die
 * Abfrage aus, gilt die. Der Grund ist die Wartungsseite — sie antwortet,
 * waehrend die Datenbank umgebaut wird, und soll trotzdem ihre Kopfzeilen
 * bekommen.
 *
 * `instanz_lib.php` STEHT AUSGESCHRIEBEN, obwohl `db.php` es ohnehin zieht:
 * Das HTTPS-Tor braucht `INSTANZ_KURZ_VORGABE` fuer seinen Titel und ist die
 * Antwort, die auch dann stehen soll, wenn sonst nichts steht. Eine
 * Abhaengigkeit ueber zwei Ecken ist genau die, die beim naechsten Umbau
 * still wegfaellt. Die Datei laedt selbst nichts (dort ausgeschrieben).
 */

/* `db.php` NUR, WENN ES SCHON EINE KONFIGURATION GIBT (Nr. 214).
 *
 * `db.php` verlangt `config.php` hart (`$CFG = require ...`), und das ist dort
 * richtig: Jede regulaere Seite laeuft nach der Einrichtung. Genau eine laeuft
 * davor — `install.php`, und sie zieht `ui.php`, damit ihr Formular aussieht
 * wie die Anwendung. Ueber `ui_seite_start()` landete sie hier, hier in
 * `db.php` und dort im Fatal Error: Nach P5a/AP4 war die Anwendung nicht mehr
 * installierbar, und zwar genau so lange, wie noch niemand sie installiert
 * hatte. Der Kopf oben sagt, diese Datei komme „ohne Datenbank aus"; sie kam
 * nur nicht ohne `config.php` aus, und das ist derselbe Fall eine Ebene
 * tiefer.
 *
 * `is_file()` statt eines abgefangenen `require`: Ein fehlgeschlagenes
 * `require` waere ein Fatal Error und kein Ausnahmefall, den man fangen
 * koennte. Die beiden Funktionen, um die es geht, sind unten mit
 * `function_exists()` abgesichert — mehr braucht diese Datei aus `db.php`
 * nicht (nachgemessen: `app_state_lesen()` und `app_state_setzen()`, sonst
 * nichts). */
if (is_file(__DIR__ . '/config.php')) {
    require_once __DIR__ . '/db.php';
}
require_once __DIR__ . '/instanz_lib.php';

/* ---- Einstellungen, je mit Vorgabe --------------------------------------- */

/** Schluessel in `app_state`. */
const KOPF_K_CSP_SCHARF = 'csp_scharf';
const KOPF_K_HSTS_TAGE  = 'hsts_tage';

/** Vorgabe der HSTS-Dauer in Tagen (E-P5a-15). Stufen: 1 · 7 · 365. */
const KOPF_HSTS_VORGABE = 1;
const KOPF_HSTS_STUFEN  = [1, 7, 365];

/** Die vier Kacheldomains aus `assets/map_layers.js` — dort nachgemessen. */
const KOPF_KACHELN = [
    'https://tile.openstreetmap.org',
    'https://tile.openmaps.fr',
    'https://*.tile.opentopomap.org',
    'https://server.arcgisonline.com',
];

/* DIE VIER GATTER (Nr. 214). Ohne `config.php` gibt es `app_state_lesen()`
 * nicht — dann gilt die Vorgabe, dieselbe, die auch bei fehlender Tabelle
 * gilt. Fuer den Einrichter ist das die richtige Antwort: Report-Only und
 * ein Tag HSTS sind die vorsichtigen Werte, und geschrieben wird vor der
 * Einrichtung ohnehin nichts (die beiden Setzer melden `false`). */

/** Ist die CSP scharf geschaltet? Vorgabe: nein (Report-Only). */
function kopf_csp_scharf(): bool
{
    if (!function_exists('app_state_lesen')) { return false; }
    return app_state_lesen(KOPF_K_CSP_SCHARF) === '1';
}

/** Die CSP scharf schalten oder wieder auf Report-Only stellen. */
function kopf_csp_scharf_setzen(bool $an): bool
{
    if (!function_exists('app_state_setzen')) { return false; }
    return app_state_setzen(KOPF_K_CSP_SCHARF, $an ? '1' : '0');
}

/** HSTS-Dauer in Tagen. 0 heisst: keine Kopfzeile senden. */
function kopf_hsts_tage(): int
{
    if (!function_exists('app_state_lesen')) { return KOPF_HSTS_VORGABE; }
    $v = app_state_lesen(KOPF_K_HSTS_TAGE);
    if ($v === null || $v === '') { return KOPF_HSTS_VORGABE; }
    $n = (int)$v;
    return in_array($n, KOPF_HSTS_STUFEN, true) || $n === 0 ? $n : KOPF_HSTS_VORGABE;
}

/** Die Dauer setzen. Nur die Stufen und 0 (aus) sind erlaubt. */
function kopf_hsts_tage_setzen(int $tage): bool
{
    if ($tage !== 0 && !in_array($tage, KOPF_HSTS_STUFEN, true)) { return false; }
    if (!function_exists('app_state_setzen')) { return false; }
    return app_state_setzen(KOPF_K_HSTS_TAGE, (string)$tage);
}

/* ---- Der Nonce ----------------------------------------------------------- */

/**
 * Der Nonce dieser Anfrage — einmal erzeugt, immer derselbe.
 *
 * 16 Byte aus `random_bytes()`, base64. Die CSP-Spezifikation verlangt
 * mindestens 128 Bit Entropie; 16 Byte sind genau das.
 *
 * ER DARF NICHT AUS DER SITZUNG KOMMEN und auch nicht aus der Zeit: Ein
 * Nonce, den ein Angreifer vorhersagen kann, ist keiner. Und er darf sich
 * innerhalb einer Antwort nicht aendern — sonst passte er zur Kopfzeile
 * nicht mehr.
 */
function kopf_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) { $nonce = base64_encode(random_bytes(16)); }
    return $nonce;
}

/**
 * Das fertige Attribut fuer einen Inline-Block: ` nonce="…"`.
 *
 * ES STEHT AN JEDEM INLINE-`<script>`, und zwar an jedem einzelnen. Ein
 * vergessener Block faellt in der Report-Only-Phase als Bericht auf und nach
 * dem Scharfschalten als tote Seite — deshalb zaehlt Stufe 1 des Prueftors
 * sie nach (`tools/cspprobe/`).
 */
function kopf_nonce_attr(): string
{
    return ' nonce="' . htmlspecialchars(kopf_nonce(), ENT_QUOTES, 'UTF-8') . '"';
}

/* ---- Die Kopfzeilen ------------------------------------------------------ */

/** Kam diese Anfrage ueber HTTPS? Beruecksichtigt vertrauenswuerdige Proxys. */
function kopf_https(): bool
{
    if (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off') {
        return true;
    }
    if ((string)($_SERVER['REQUEST_SCHEME'] ?? '') === 'https') { return true; }
    /* `X-Forwarded-Proto` NUR von einem Proxy, dem diese Installation
     * ausdruecklich vertraut (E-P5a-17). Die Kopfzeile kommt sonst vom
     * Aufrufer, und wer sie erfinden darf, schaltet HSTS und den HTTPS-Zwang
     * mit einer Zeile ab. */
    if (function_exists('netz_proxy_vertrauenswuerdig') && netz_proxy_vertrauenswuerdig()) {
        $p = strtolower(trim((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')));
        /* Bei mehreren Zwischenstationen steht dort eine Liste; die LETZTE
         * Angabe stammt vom Proxy, dem wir vertrauen. */
        if ($p !== '') {
            $teile = array_map('trim', explode(',', $p));
            return end($teile) === 'https';
        }
    }
    return false;
}

/**
 * Die Richtlinie als Zeichenkette.
 *
 * @param bool $mitNonce false fuer Seiten ohne eigene Skripte (Wartungsseite)
 */
function kopf_csp(bool $mitNonce = true): string
{
    $skript = $mitNonce
        ? "'self' 'nonce-" . kopf_nonce() . "'"
        : "'self'";

    /* Der Adressdienst kommt zur LAUFZEIT herein und nur, wenn die Suche an
     * ist (Abweichung 2 im Dateikopf). `geocoder_lib.php` wird nicht
     * geladen — die Wartungsseite haette daran keine Freude; ist sie da,
     * wird sie gefragt. */
    $verbinde = ["'self'"];
    if (function_exists('geocoder_installation_an') && function_exists('geocoder_dienst')) {
        try {
            if (geocoder_installation_an()) {
                $u = parse_url(geocoder_dienst());
                if (!empty($u['host'])) {
                    $verbinde[] = ($u['scheme'] ?? 'https') . '://' . $u['host']
                                . (isset($u['port']) ? ':' . (int)$u['port'] : '');
                }
            }
        } catch (Throwable $ex) { /* ohne Datenbank: nur 'self' */ }
    }

    $teile = [
        "default-src 'none'",
        'script-src ' . $skript,
        "style-src 'self'",
        "style-src-attr 'unsafe-inline'",
        'img-src ' . implode(' ', array_merge(["'self'", 'data:'], KOPF_KACHELN)),
        "font-src 'self'",
        'connect-src ' . implode(' ', $verbinde),
        "worker-src 'self' blob:",
        "base-uri 'none'",
        "form-action 'self'",
        "object-src 'none'",
    ];
    /* `frame-ancestors` NUR IN DER SCHARFEN FASSUNG (Backlog Nr. 224).
     *
     * In einer Report-Only-Richtlinie wird die Direktive vom Browser
     * IGNORIERT — so steht es in CSP Level 3, und WebKit sagt es laut:
     * „The Content Security Policy directive 'frame-ancestors' is ignored
     * when delivered in a report-only policy." Ein Fehler je Seitenaufruf.
     *
     * WAS DAS GEKOSTET HAT, IST NICHT DER SCHUTZ, SONDERN DAS PRUEFMITTEL.
     * Der Clickjacking-Schutz steht unabhaengig davon in
     * `X-Frame-Options: DENY` (unten, in beiden Faellen). Die Zeile hier war
     * in Report-Only wirkungslos — sie hat nichts geschuetzt und nichts
     * gemeldet. Was sie tat, war: den Bilderlauf mit WebKit auf JEDER Seite
     * einen Konsolenfehler melden zu lassen. Ein Pruefmittel, das ueberall
     * rauscht, findet nichts mehr; der echte Fehler stuende daneben und
     * fiele nicht auf. Gemessen am 16.09.2026: 16 Konsolenfehler bei 16
     * Bildern, Chromium und Firefox 0.
     *
     * SCHARF GESCHALTET GEHOERT SIE DAZU, und dann wirkt sie auch. Deshalb
     * steht sie unten im `if` und nicht hier.
     */
    if (kopf_csp_scharf()) {
        $teile[] = "frame-ancestors 'none'";
    }

    /* DER BERICHTSENDPUNKT GEHOERT IN BEIDE FASSUNGEN: Auch eine scharfe
     * Richtlinie soll melden, was sie blockiert hat — sonst merkt niemand,
     * dass eine Seite gerade halb ist.
     *
     * ZWEI MECHANISMEN, UND EINER DAVON IST EINE FALLE. `report-uri` ist
     * abgekuendigt, wird aber von jedem heutigen Browser verstanden und
     * nimmt eine RELATIVE Adresse — sie loest sich gegen die Seite auf und
     * stimmt damit in jeder Installation, auch in einem Unterverzeichnis.
     * `report-to` nennt dagegen nur einen NAMEN; wo der hinzeigt, steht in
     * einer eigenen Kopfzeile `Reporting-Endpoints`.
     *
     * DIE FALLE: Stehen beide da, BEVORZUGT Chromium `report-to` — und
     * verwirft den Bericht ersatzlos, wenn die Gruppe nicht aufgeloest
     * werden kann. Gemessen am 15.09.2026: mit `report-to csp` und einer
     * `Reporting-Endpoints`-Zeile, die eine relative Adresse trug, kamen
     * **null** Berichte an; ohne `report-to` kam derselbe Verstoss sofort
     * als Zeile in `csp_berichte` (F-P5a-AP4-1). Die Report-Only-Phase
     * waere eine Wartezeit ohne Erkenntnis gewesen, und der leere Kasten
     * auf der Servereinstellungsseite haette wie ein gutes Zeichen
     * ausgesehen.
     *
     * DESHALB: `report-to` nur dann, wenn `kopf_melde_url()` eine
     * vollstaendige, vertrauenswuerdige Adresse liefert — also ueber HTTPS
     * mit brauchbarem Host. Sonst traegt `report-uri` die Meldung allein.
     * Eine Kopfzeile, die auf eine Gruppe zeigt, die es nicht gibt, ist
     * schlimmer als keine. */
    $teile[] = 'report-uri api/csp_bericht.php';
    if (kopf_melde_url() !== '') { $teile[] = 'report-to csp'; }

    return implode('; ', $teile);
}

/**
 * Die VOLLSTAENDIGE Adresse des Berichtsendpunkts — oder leer.
 *
 * `Reporting-Endpoints` braucht eine Adresse, die der Browser als
 * vertrauenswuerdig ansieht; eine relative reicht ihm nicht (gemessen:
 * siehe die Falle im Kopf von `kopf_csp()`). Sie wird aus der laufenden
 * Anfrage gebaut und nicht aus `app.base_url`: Steht dort ein anderer Name
 * als der, unter dem die Seite gerade abgerufen wird, waere der Endpunkt
 * fremder Herkunft — und der Browser schickte erst recht nichts.
 *
 * DER PFAD WIRD AUS DEM LAUFENDEN SKRIPT ABGELEITET, damit eine
 * Installation in einem Unterverzeichnis (`/einsatz/`) stimmt. Laeuft das
 * Skript selbst unter `api/`, faellt dieses Stueck weg — sonst zeigte die
 * Adresse auf `api/api/`.
 *
 * LEER, WENN DIE ANFRAGE NICHT UEBER HTTPS KAM. Dann faellt `report-to`
 * weg und `report-uri` traegt allein; das ist genau die Lage auf einem
 * Entwicklungsrechner ohne Zertifikat.
 */
function kopf_melde_url(): string
{
    if (!kopf_https()) { return ''; }
    $host = (string)($_SERVER['HTTP_HOST'] ?? '');
    if ($host === '' || !preg_match('/^[A-Za-z0-9._\-]+(:[0-9]{1,5})?$/', $host)) {
        return '';
    }
    $pfad = str_replace('\\', '/', dirname((string)($_SERVER['SCRIPT_NAME'] ?? '/')));
    if (substr($pfad, -4) === '/api') { $pfad = substr($pfad, 0, -4); }
    $pfad = rtrim($pfad, '/');
    return 'https://' . $host . $pfad . '/api/csp_bericht.php';
}

/**
 * Alle Kopfzeilen einer HTML-Seite.
 *
 * Wird aus `ui_seite_start()` gerufen — der einen Huelle, durch die jede
 * Seite laeuft. Die drei Stellen mit eigener Huelle (`wartung_lib.php`,
 * `betrieb_schluesselblatt.php`, `apk.php`) rufen sie selbst.
 *
 * @param bool $mitNonce false, wenn die Seite gar keine Skripte hat
 */
function kopfzeilen_seite(bool $mitNonce = true): void
{
    if (headers_sent()) { return; }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    header('Permissions-Policy: geolocation=(self), camera=(), microphone=()');
    $melde = kopf_melde_url();
    if ($melde !== '') { header('Reporting-Endpoints: csp="' . $melde . '"'); }

    $csp = kopf_csp($mitNonce);
    header((kopf_csp_scharf() ? 'Content-Security-Policy: '
                              : 'Content-Security-Policy-Report-Only: ') . $csp);

    kopf_hsts();
}

/**
 * Der schmale Satz fuer JSON-Endpunkte.
 *
 * KEINE CSP. Eine JSON-Antwort ist kein Dokument; der Browser fuehrt darin
 * nichts aus, und eine Richtlinie dort waere eine Kopfzeile ohne Wirkung.
 * `nosniff` ist dagegen genau hier die wichtige Zeile: Ohne sie darf ein
 * Browser eine JSON-Antwort als HTML deuten, wenn der Inhalt danach aussieht.
 */
function kopfzeilen_json(): void
{
    if (headers_sent()) { return; }
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    kopf_hsts();
}

/**
 * HSTS — nur ueber HTTPS.
 *
 * ÜBER HTTP WÄRE SIE WIRKUNGSLOS UND GEFÄHRLICH ZUGLEICH: Der Browser
 * ignoriert sie ohnehin (RFC 6797, 7.2), und wer sie dort setzt, glaubt an
 * einen Schutz, den er nicht hat.
 */
function kopf_hsts(): void
{
    $tage = kopf_hsts_tage();
    if ($tage <= 0 || !kopf_https()) { return; }
    header('Strict-Transport-Security: max-age=' . ($tage * 86400));
}

/* ---------------------------------------------------------------------------
 * HTTPS-ZWANG (P5a/AP4, E-P5a-16; PP-6 Muss)
 * ---------------------------------------------------------------------------
 *
 * WAS OHNE IHN PASSIERT, UND WARUM ES SO AERGERLICH IST. Die Sitzungscookies
 * tragen seit jeher `secure` (`auth_guard.php`, `session_lib.php`). Ueber HTTP
 * sendet der Browser sie deshalb NICHT — die Anmeldung scheitert, und zwar
 * STUMM: Das Formular kommt zurueck, als waere das Passwort falsch. Wer das
 * nicht weiss, sucht den Fehler bei sich.
 *
 * DIE SEITE SAGT ES STATTDESSEN. Kein Umleiten: Eine Weiterleitung auf `https`
 * sieht aus, als waere alles in Ordnung, und verdeckt die Ursache — auf einem
 * Server ohne TLS landete man in einer Schleife. Die Seite nennt die
 * https-Adresse, und der Mensch entscheidet.
 *
 * (Auf Apache kommt es dazu meist gar nicht: `server/.htaccess` leitet schon
 * per `mod_rewrite` um. Diese Pruefung ist fuer alles andere — nginx, Caddy,
 * LiteSpeed —, wo jene Datei nicht gelesen wird.)
 *
 * AUSNAHME `localhost` UND `127.0.0.1`. Browser behandeln sie als sicheren
 * Kontext, `secure`-Cookies werden dort auch ueber HTTP gesendet, und so
 * laeuft der Pruefstand.
 *
 * NICHT GETORT: `install.php` (wer einrichtet, hat unter Umstaenden noch kein
 * Zertifikat), die Kommandozeile und die Geraete-Endpunkte `ingest.php` und
 * `pair.php` — deren Zusage steht im JSON-Vertrag, und eine neue Antwortart
 * dort waere eine Client-Aenderung (E-P5a-16 nennt sie ausdruecklich nicht).
 */

/** Laeuft die Anfrage gegen localhost? Dann gilt sie als sicherer Kontext. */
function kopf_lokal(): bool
{
    $n = strtolower((string)($_SERVER['SERVER_NAME'] ?? ''));
    $a = (string)($_SERVER['SERVER_ADDR'] ?? '');
    return in_array($n, ['localhost', '127.0.0.1', '::1', '[::1]'], true)
        || in_array($a, ['127.0.0.1', '::1'], true);
}

/**
 * Das Tor. Ueber HTTP endet hier jede Anfrage, die es aufruft.
 *
 * Aufgerufen aus `auth_guard.php` (alle angemeldeten Seiten) und aus
 * `login.php` (die Seite, auf der es auffaellt).
 */
function https_tor(): void
{
    if (PHP_SAPI === 'cli' || PHP_SAPI === 'phpdbg') { return; }
    if (kopf_https() || kopf_lokal()) { return; }

    http_response_code(403);
    header('Content-Type: text/html; charset=utf-8');
    header('Cache-Control: no-store');
    header('X-Content-Type-Options: nosniff');

    $h = static fn(string $t): string => htmlspecialchars($t, ENT_QUOTES, 'UTF-8');
    $host = (string)($_SERVER['HTTP_HOST'] ?? ($_SERVER['SERVER_NAME'] ?? ''));
    $pfad = (string)($_SERVER['REQUEST_URI'] ?? '/');
    $ziel = $host !== '' ? 'https://' . $host . $pfad : '';

    /* OHNE `ui.php`, aus demselben Grund wie die Wartungsseite: Dessen Huelle
     * zieht ueber `ui_favicon()` und `logo_stamm()` die Datenbank herein, und
     * diese Antwort soll auch dann stehen, wenn sonst nichts steht. Das
     * Stylesheet ist verlinkt — es ist eine statische Datei. */
    echo '<!doctype html><html lang="de"><head><meta charset="utf-8">'
       . '<meta name="viewport" content="width=device-width,initial-scale=1">'
       . '<title>Nur über HTTPS — ' . $h(INSTANZ_KURZ_VORGABE) . '</title>'
       . '<link rel="stylesheet" href="assets/style.css"></head><body>'
       . '<div class="rahmen rahmen-lesespalte"><main class="inhalt"><div class="text">'
       . '<h1>Diese Anwendung läuft nur über HTTPS</h1>'
       . '<div class="meldung meldung-warn" role="status"><p>'
       . 'Du hast sie über <strong>http://</strong> aufgerufen. Über diesen Weg '
       . 'kann sie nicht arbeiten.</p></div>'
       . '<p><strong>Warum nicht einfach umleiten?</strong> Weil eine Umleitung '
       . 'aussieht, als wäre alles in Ordnung. Die Anwendung führt '
       . 'Patientendaten; über eine unverschlüsselte Verbindung liest jedes '
       . 'Gerät zwischen dir und dem Server mit — auch dein Passwort. Und ihre '
       . 'Sitzungscookies tragen die Markierung <code>secure</code>: Der Browser '
       . 'schickt sie über HTTP gar nicht erst mit. Die Anmeldung würde also '
       . 'scheitern, <em>ohne zu sagen, warum</em>.</p>'
       . ($ziel !== ''
           ? '<p><a class="knopf knopf-neutral" href="' . $h($ziel) . '"><span>'
             . 'Dieselbe Seite über HTTPS öffnen</span></a></p>'
           : '')
       . '<p class="feld-hinweis">Kommt danach eine Zertifikatswarnung oder gar '
       . 'keine Antwort, ist auf dem Server noch kein TLS eingerichtet. Das ist '
       . 'eine Sache des Hostings, nicht dieser Anwendung — die meisten Hoster '
       . 'schalten Let\'s Encrypt mit einem Klick frei.</p>'
       . '</div></main></div></body></html>';
    exit;
}

