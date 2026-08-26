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
