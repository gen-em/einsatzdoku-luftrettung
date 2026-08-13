<?php
declare(strict_types=1);
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ratelimit_lib.php';

// Zeitpunkt fuer die konstante Antwortdauer im Fehlerzweig — muss VOR jeder
// Arbeit stehen, sonst misst er nicht die ganze Anfrage.
$t0 = microtime(true);
session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/']);
session_start();

if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

/* Grund des Sitzungsendes anzeigen.
 *
 * Wer nach Ablauf der Frist weiterarbeiten wollte, landete bisher OHNE JEDE
 * ERKLAERUNG auf dieser Seite: Der Ablaufpfad haengte ?timeout=1 an, und diese
 * Seite wertete den Parameter nicht aus. Aus Sicht der NutzerIn verschwand die
 * Anwendung einfach. Der alte Parametername wird weiter erkannt, damit ein
 * offener Tab mit alter Adresse nicht ins Leere laeuft. */
$hinweis = session_ende_text($_GET['ende'] ?? null);
if ($hinweis === '' && isset($_GET['timeout'])) { $hinweis = session_ende_text('abgelaufen'); }

/* ---- Anmeldung ------------------------------------------------------------
 *
 * DIE BREMSE LAG FRUEHER IN DER SITZUNG DES AUFRUFERS. Fuenf Fehlversuche,
 * dann dreissig Sekunden Pause — gezaehlt in $_SESSION. Wer das Cookie
 * wegwarf, hatte wieder fuenf Versuche frei; ein Skript, das gar kein Cookie
 * annimmt, hatte nie eines verbraucht. Das war keine Bremse, sondern eine
 * Bequemlichkeit gegen Vertippen. Gezaehlt wird jetzt in der Datenbank, je
 * Kontokennung UND je IP-Adresse (ratelimit_lib.php).
 *
 * ZWEI EIGENSCHAFTEN, DIE HIER DIE ARBEIT MACHEN
 *   1. Die Sperre greift VOR der Abfrage und vor bcrypt. Sonst kann ein
 *      Gesperrter den Server weiter rechnen lassen.
 *   2. Der Fehlerzweig dauert immer gleich lang. Bei unbekannter Adresse lief
 *      frueher gar keine Passwortpruefung, bei bekannter eine bcrypt-Pruefung
 *      — der Unterschied sagte, welche Adressen es gibt. Deshalb der
 *      Vergleich gegen einen festen Wert plus rate_gleiche_dauer().
 *
 * BEWUSST IN KAUF GENOMMEN: Wer eine Adresse kennt, kann das Konto durch
 * Fehlversuche zeitweise sperren. Die Sperre ist kurz und die Meldung nennt
 * ihr Ende; die Alternative — nur nach IP zaehlen — liesse ein verteiltes
 * Durchprobieren einer einzelnen Adresse voellig ungebremst.
 */
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (!rate_erlaubt('login', $email)) {
        $bis = rate_gesperrt_bis('login', $email);
        $error = 'Zu viele Anmeldeversuche. Bitte später erneut versuchen'
               . ($bis !== null ? ' — frühestens ab ' . fmt_local($bis, 'H:i') . ' Uhr.' : '.');
        rate_gleiche_dauer($t0);
    } else {
        // Der Browser sendet nie das Passwort, sondern das daraus
        // abgeleitete Token (siehe assets/crypto.js).
        $st = db()->prepare('SELECT id, password_hash, role FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        $token = (string)($_POST['token'] ?? '');
        if ($u && $u['password_hash'] !== null) {
            $ok = $token !== '' && password_verify($token, $u['password_hash']);
        } else {
            // Unbekannte Adresse oder Konto ohne gesetztes Passwort: trotzdem
            // eine bcrypt-Pruefung, damit dieser Zweig nicht schneller ist.
            password_verify($token, AUTH_VERGLEICHSWERT);
            $ok = false;
        }

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$u['id'];
            $_SESSION['role']    = $u['role'];
            // Alte Sitzungsbremse aufraeumen: Auf Rechnern, die vor dieser
            // Fassung angemeldet waren, liegen die beiden Werte noch herum.
            unset($_SESSION['login_fails'], $_SESSION['login_last']);
            rate_erfolg('login', $email);
            // Auch den Zaehler des Salz-Endpunkts leeren — jede Anmeldung
            // verbraucht dort einen Versuch, und wer sich erfolgreich
            // anmeldet, soll sich nicht selbst aussperren.
            rate_erfolg('salt', $email);
            header('Location: index.php'); exit;
        }
        rate_misserfolg('login', $email);
        $error = 'Anmeldung fehlgeschlagen. E-Mail oder Passwort prüfen.';
        rate_gleiche_dauer($t0);
    }
}
?><!doctype html>
<html lang="de">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Anmelden — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?>
</head>
<body class="login-body">
<main class="login-card">
  <img src="<?= e(logo_src()) ?>"
       alt="GenEM" class="login-logo">
  <h1>Einsatzdoku</h1>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
  <?php if ($hinweis && !$error): ?><p class="alert alert-info"><?= e($hinweis) ?></p><?php endif; ?>
  <form method="post" autocomplete="on" id="loginform">
    <input type="hidden" name="token" id="tok">
    <label>E-Mail
      <input type="email" name="email" required autofocus autocomplete="username">
    </label>
    <label>Passwort
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <button type="submit" class="btn-primary">Anmelden</button>
  </form>
  <p class="login-aux"><a href="reset_request.php">Passwort vergessen?</a></p>
  <p class="muted" id="loginstate" style="min-height:1.2em"></p>
</main>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<script>
// Der Browser leitet aus dem Passwort zwei Schluessel ab: das Auth-Token
// (geht zum Server) und den Daten-Schluessel (bleibt hier, entsperrt das
// PatientInnendaten-Modul). Das Passwort selbst verlaesst den Browser nie.
document.getElementById('loginform').addEventListener('submit', async ev => {
  const f = ev.target;
  if (f.dataset.ready === '1') return;               // zweiter Durchlauf: senden
  ev.preventDefault();
  const state = document.getElementById('loginstate');
  try {
    state.textContent = 'Schlüssel wird abgeleitet…';
    // Schluessel einer frueheren Sitzung verwerfen — sonst wuerde ein alter
    // Inhaltsschluessel weiterverwendet (etwa nach Kontowechsel im selben Tab).
    EdCrypto.clearSession();
    const email = f.elements['email'].value.trim().toLowerCase();
    const pw = f.elements['password'].value;
    const r = await fetch('auth_salt.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    // Der Salz-Endpunkt hat seit Web 4.4.0 einen Ratenschutz. Ohne diese
    // Abfrage lief eine Sperre in den catch-Zweig und meldete "Dieser Browser
    // unterstuetzt die noetige Verschluesselung nicht" — eine Auskunft, die
    // nicht nur unbrauchbar, sondern falsch ist.
    if (r.status === 429) {
      const d429 = await r.json().catch(() => ({}));
      state.textContent = d429.meldung || 'Zu viele Versuche. Bitte später erneut.';
      return;
    }
    if (!r.ok) {
      state.textContent = 'Anmeldung derzeit nicht möglich. Bitte später erneut.';
      return;
    }
    const d = await r.json();
    const k = await EdCrypto.deriveKeys(pw, d.salt);
    document.getElementById('tok').value = k.authToken;
    EdCrypto.setDataKey(k.dataKeyHex);               // fuer diese Sitzung
    f.elements['password'].value = '';               // verlaesst den Browser nie
    f.dataset.ready = '1';
    state.textContent = '';
    f.submit();
  } catch (e2) {
    // Ohne Web-Krypto ist keine Anmeldung moeglich: Das Passwort duerfte den
    // Browser nicht verlassen, und ohne abgeleitetes Token gibt es keinen Weg.
    state.textContent = 'Dieser Browser unterstützt die nötige Verschlüsselung nicht.';
  }
});
</script>
<?php /* Footer im Fluss unter der Karte */ ?>
<footer class="sitefooter">© Gen-EM – OpenSource Software – <a href="https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE" target="_blank" rel="noopener">AGPL-3.0</a></footer>
</div>
</body>
</html>
