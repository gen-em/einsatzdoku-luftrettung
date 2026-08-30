<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Sitzungsende mit Raeumung im Browser (Baustein B6).
 *
 * WARUM ES DIESE DATEI GIBT
 * Es gibt zwei Wege, auf denen eine Sitzung endet, und sie verhalten sich
 * unterschiedlich:
 *
 *   Abmelden      logout.php     loest es RICHTIG: Eine reine
 *                                HTTP-Weiterleitung fuehrt nie JavaScript aus,
 *                                deshalb wird eine kurze Seite ausgeliefert,
 *                                die EdCrypto.clearSession() aufruft.
 *   Ablauf        auth_guard.php loest es NICHT: Es leitet per Kopfzeile um.
 *                                Daten- und Inhaltsschluessel bleiben im
 *                                sessionStorage liegen.
 *
 * Der Unterschied ist nicht theoretisch. Der Inhaltsschluessel entsperrt alle
 * geschuetzten Angaben; er ueberdauert dort genau so lange wie der Tab. Wer
 * seinen Rechner nach Ablauf der Sitzungsfrist stehen laesst, hat eine
 * abgelaufene Sitzung, aber einen liegengebliebenen Schluessel.
 *
 * Diese Funktion ist die eine Fassung fuer beide Wege — damit sie nicht
 * wieder auseinanderlaufen. Sie nennt zugleich den GRUND, denn der
 * Ablaufpfad haengte bisher einen Parameter an, den die Anmeldeseite gar
 * nicht auswertet: Wer nach der Frist weiterarbeiten will, landete ohne
 * jede Erklaerung auf der Anmeldeseite.
 */

/** Erlaubte Gruende. Andere Werte werden auf 'ende' abgebildet — der Grund
 *  landet in der Adresszeile und darf nicht frei setzbar sein. */
const SESSION_ENDE_GRUENDE = ['abgemeldet', 'abgelaufen', 'passwort', 'konto', 'ende'];

/**
 * Sitzung beenden OHNE Ausgabe.
 *
 * Fuer die Datenabrufe unter server/api/: Dort wuerde die Seite aus
 * session_beenden() als HTML in einem fetch() landen, das JSON erwartet — der
 * Aufrufer saehe einen Syntaxfehler beim Auswerten statt einer Aussage. Diese
 * Fassung raeumt nur die Sitzung; die Antwort formuliert die aufrufende
 * Stelle (auth_guard.php) als 401 mit Grund.
 *
 * Die Schluessel im Browser bleiben dabei zunaechst liegen. Das ist hier
 * richtig: Ein Datenabruf laeuft im Hintergrund, waehrend die Seite offen
 * bleibt — die naechste Seitenanfrage laeuft in auth_guard.php ohne
 * Sitzungskennung auf und geht ueber login.php, das die Schluessel raeumt.
 */
function session_verwerfen(): void
{
    if (session_status() === PHP_SESSION_NONE) { return; }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

/**
 * Beendet die Sitzung und liefert eine kurze Seite aus, die die Schluessel im
 * Browser raeumt und danach zur Anmeldung wechselt.
 *
 * Fuer Browser ohne JavaScript sorgt ein Meta-Refresh dafuer, dass die Seite
 * kein Sackgassenzustand wird. Die Schluessel liegen dann zwar noch im
 * sessionStorage — ohne JavaScript sind sie aber auch nicht verwendbar, und
 * der Tab raeumt sie beim Schliessen ohnehin ab.
 */
function session_beenden(string $grund = 'abgemeldet'): never
{
    if (!in_array($grund, SESSION_ENDE_GRUENDE, true)) { $grund = 'ende'; }

    if (session_status() === PHP_SESSION_NONE) {
        session_set_cookie_params([
            'httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/',
        ]);
        session_start();
    }
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
                  $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();

    $ziel = 'login.php?ende=' . rawurlencode($grund);
    $text = match ($grund) {
        'abgelaufen' => 'Die Sitzung ist abgelaufen — du wirst abgemeldet …',
        'passwort'   => 'Das Passwort wurde geändert — du wirst abgemeldet …',
        'konto'      => 'Das Konto steht nicht mehr zur Verfügung …',
        default      => 'Du wirst abgemeldet …',
    };

    http_response_code(200);
    header('Content-Type: text/html; charset=utf-8');
    /* Die Huelle wird hier geladen, nicht am Dateikopf: login.php und
       logout.php binden session_lib.php ein, ohne ui.php zu kennen — nur
       auth_guard.php tut das. ui.php hat auf oberster Ebene keine
       Abhaengigkeit, das Nachladen kostet also nichts. */
    require_once __DIR__ . '/ui.php';
    ui_seite_start([
        'titel'  => 'Abmelden',
        'klasse' => 'anmeldung-body',
        'kopf'   => '<noscript><meta http-equiv="refresh" content="0;url='
                    . e($ziel) . '"></noscript>',
    ]);
    ?>
<main class="anmeldung">
 <div class="anmeldung-karte">
  <p><?= e($text) ?></p>
  <p class="anmeldung-neben"><a href="<?= e($ziel) ?>">Zur Anmeldung</a></p>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<script>
// Beide Schluessel raeumen: Daten- UND Inhaltsschluessel. Faengt das Skript
// nicht, bleibt die Seite ueber den Verweis oben bedienbar.
try { EdCrypto.clearSession(); } catch (e) { /* Skript blockiert */ }
location.replace(<?= json_encode($ziel) ?>);
</script>
<?php
    ui_seite_ende();
    exit;
}

/**
 * Lesbarer Text zu einem Grund aus der Adresszeile — fuer die Anmeldeseite.
 * Liefert eine leere Zeichenkette, wenn nichts (Sinnvolles) angegeben ist.
 */
function session_ende_text(?string $grund): string
{
    return match ($grund) {
        'abgelaufen' => 'Die Sitzung ist nach 30 Minuten ohne Aktivität abgelaufen. '
                      . 'Bitte melde dich erneut an.',
        'abgemeldet' => 'Du wurdest abgemeldet.',
        'passwort'   => 'Das Passwort dieses Kontos wurde geändert. Diese Sitzung ist '
                      . 'damit beendet — bitte mit dem neuen Passwort anmelden.',
        'konto'      => 'Das Konto steht nicht mehr zur Verfügung.',
        default      => '',
    };
}


/* ---------------------------------------------------------------------------
 * LOGO-WAHL JE PROFIL  (E-P3-20, ab Web 9.7.0)
 *
 * Vier Werte stehen in `users.logo_wahl`:
 *
 *   ''             Standard der Installation — der Vorgabewert. Wer nie
 *                  gewaehlt hat, folgt dem Standard, und der kann sich
 *                  aendern (die Wahl dafuer entsteht in O9).
 *   'hubschrauber' Hubschrauber (RTH)
 *   'fahrzeug'     Fahrzeug (NEF)
 *   'wechselnd'    je Anmeldung neu gewuerfelt
 *
 * AUFGELOEST WIRD EINMAL, BEI DER ANMELDUNG. In der Sitzung steht danach
 * nicht die Wahl, sondern ihr ERGEBNIS ('hubschrauber' oder 'fahrzeug') —
 * sonst muesste ui_logo() bei jedem Seitenaufruf wuerfeln, und das Logo
 * spraenge innerhalb einer Sitzung von Seite zu Seite. „Wechselnd" heisst
 * je Anmeldung, nicht je Klick.
 *
 * Wer die Wahl im Profil aendert, muss sich deshalb NICHT neu anmelden:
 * einstellungen.php ruft dieselbe Funktion nach dem Speichern auf.
 * ------------------------------------------------------------------------ */

/** Vorbelegung, solange die Installation nichts anderes gesetzt hat. */
const LOGO_STANDARD_VORGABE = 'hubschrauber';

/**
 * Der Standard DIESER Installation (E-P3-19/20, einstellbar seit Web 9.10.0).
 *
 * Bis Web 9.9.0 war das eine Konstante. Jetzt steht der Wert in `app_state`
 * und laesst sich in der Wartung umstellen — eine Installation, die
 * ueberwiegend am Boden faehrt, soll nicht dauerhaft einen Hubschrauber im
 * Kopf tragen.
 *
 * JE ANFRAGE EINMAL GELESEN. logo_stamm() faellt auf diese Funktion zurueck
 * und wird auf jeder Seite mehrfach aufgerufen (Kopfleiste, Favicon).
 *
 * OHNE DATENBANK GILT DIE VORBELEGUNG. Diese Funktion laeuft auch dort, wo es
 * noch keine Datenbank gibt: im Einrichter, und auf der Anmeldeseite bevor
 * eine Verbindung steht. Eine Ausnahme darf die Seite nicht kosten — das Logo
 * ist Zierde, kein Zugang.
 */
function logo_standard(): string
{
    static $wert = null;
    if ($wert !== null) { return $wert; }
    $wert = LOGO_STANDARD_VORGABE;
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute(['logo_standard']);
        $v = (string)$st->fetchColumn();
        if ($v === 'fahrzeug' || $v === 'hubschrauber' || $v === 'wechselnd') { $wert = $v; }
    } catch (Throwable) {
        // Keine Datenbank, keine Tabelle, kein Eintrag: Vorbelegung.
    }
    return $wert;
}

/** Die waehlbaren Werte — auch die Pruefliste beim Speichern. */
const LOGO_WAHLEN = ['', 'hubschrauber', 'fahrzeug', 'wechselnd'];

/**
 * Wahl -> tatsaechliches Logo ('hubschrauber' | 'fahrzeug').
 *
 * `random_int` statt `rand`: Es ist ohnehin da (die Anwendung braucht es
 * fuer Tokens), und ein Zufall, der aus derselben Quelle kommt wie alles
 * andere, ist eine Sorge weniger. Kryptographisch muss er hier nicht sein.
 */
function logo_aufloesen(?string $wahl): string
{
    $w = (string)$wahl;
    if ($w === 'wechselnd') {
        return random_int(0, 1) === 1 ? 'fahrzeug' : 'hubschrauber';
    }
    if ($w === 'hubschrauber' || $w === 'fahrzeug') { return $w; }
    return logo_standard();
}

/**
 * Die Wahl in der Sitzung ablegen (Anmeldung, Profil-Speichern).
 *
 * NUR „WECHSELND" WIRD HIER AUFGELOEST — dort faellt der Wuerfel, und zwar je
 * Anmeldung; sonst spraenge das Logo beim Blaettern.
 *
 * Der Leerstring dagegen BLEIBT stehen und wird erst in logo_stamm()
 * aufgeloest. Bis Web 9.9.0 wurde auch er hier festgeschrieben — das war
 * richtig, solange der Standard eine Konstante war. Seit er eine Einstellung
 * ist, waere es falsch: Wer ihn in der Wartung umstellt, saehe die Wirkung
 * erst, wenn sich jede NutzerIn neu angemeldet hat. Jetzt wirkt sie sofort,
 * und zwar bei genau denen, die keine eigene Wahl getroffen haben.
 */
function logo_sitzung_setzen(?string $wahl): void
{
    $w = (string)$wahl;
    $_SESSION['logo_wahl'] = $w === 'wechselnd' ? logo_aufloesen($w) : $w;
}

/**
 * Dateistamm des Logos fuer die laufende Sitzung.
 *
 * DIE EINE STELLE, an der aus der Sitzung ein Dateiname wird — ui_logo()
 * (Kopfleiste) und favicon_tags() (Browser-Symbol) fragen beide hier.
 * Kopfleiste und Favicon wechseln damit zwangslaeufig gemeinsam (E-P3-20);
 * zwei getrennte Abfragen waeren zwei Gelegenheiten, es auseinanderlaufen
 * zu lassen.
 */
/**
 * Der Installationsstandard, aufgeloest auf ein tatsaechliches Logo.
 *
 * WARUM NICHT logo_standard() DIREKT (F-N1-C): Seit die Installation selbst
 * „wechselnd" waehlen kann, ist ihr Standard nicht mehr zwangslaeufig ein
 * Dateiname. Wuerde logo_stamm() den Rohwert weiterreichen, faelle
 * „wechselnd" durch jede Abfrage und landete stumm beim Hubschrauber — die
 * Einstellung waere da und taete nichts.
 *
 * DER WUERFEL FAELLT JE SITZUNG, nicht je Seitenaufruf. Sonst spraenge das
 * Logo beim Blaettern, und Kopfleiste und Favicon koennten auseinanderlaufen
 * — dieselbe Regel, die fuer die persoenliche Wahl gilt (logo_sitzung_setzen).
 *
 * DER ADMINWECHSEL WIRKT TROTZDEM SOFORT: Gemerkt wird nur das Ergebnis des
 * Wuerfelns. Steht in `app_state` etwas anderes als „wechselnd", gilt das
 * unmittelbar und der gemerkte Wurf bleibt unbeachtet liegen.
 */
function logo_standard_aufgeloest(): string
{
    $std = logo_standard();
    if ($std !== 'wechselnd') { return $std; }
    if (!isset($_SESSION)) { return logo_aufloesen('wechselnd'); }
    $gemerkt = (string)($_SESSION['logo_standard_wurf'] ?? '');
    if ($gemerkt !== 'hubschrauber' && $gemerkt !== 'fahrzeug') {
        $gemerkt = logo_aufloesen('wechselnd');
        $_SESSION['logo_standard_wurf'] = $gemerkt;
    }
    return $gemerkt;
}

function logo_stamm(): string
{
    /* In der Sitzung steht die WAHL, nicht immer das Ergebnis: „wechselnd"
     * ist schon bei der Anmeldung aufgeloest, der Leerstring erst hier —
     * damit ein Wechsel des Installationsstandards sofort wirkt und nicht
     * erst nach der naechsten Anmeldung (siehe logo_sitzung_setzen). */
    $w = (string)($_SESSION['logo_wahl'] ?? '');
    $auf = ($w === 'hubschrauber' || $w === 'fahrzeug') ? $w : logo_standard_aufgeloest();
    return $auf === 'fahrzeug' ? 'gen-em_logo_fahrzeug' : 'gen-em_logo_helicopter';
}
