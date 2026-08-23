<?php
declare(strict_types=1);
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/demo_lib.php';

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
    /* Eine Schreibweise fuer alle Stellen (M1-13, email_lib.php). Hier stand
     * bisher nur trim(): Dass die Anmeldung trotzdem funktionierte, lag allein
     * an der Sortierregel der Datenbank. Nebenbei behoben: rate_erfolg('salt',
     * ...) unten meldete den Erfolg mit der Adresse WIE GETIPPT, waehrend
     * auth_salt.php unter der kleingeschriebenen zaehlt — wer "Max@..." tippte,
     * leerte seinen Salz-Zaehler nie. */
    $email = email_normalisieren($_POST['email'] ?? '');

    /* Mengenbremse des Demo-Kontos (E-P1-20).
     *
     * VOR der teuren Pruefung, wie jede Bremse hier — sonst bliebe der
     * Rechenaufwand als Angriffsflaeche offen.
     *
     * Sie haengt an der ADRESSE, nicht am Konto: Zu diesem Zeitpunkt ist noch
     * nicht nachgeschlagen, wer sich anmeldet, und das soll auch so bleiben
     * (der Zweig „Adresse unbekannt" darf nicht schneller sein als der
     * andere). Die Adresse des Demo-Kontos ist ohnehin oeffentlich — hier
     * verraet der Vergleich also nichts, was nicht im Handbuch steht.
     *
     * Die Meldung nennt den Grund: „zu viele Anmeldeversuche" waere hier
     * schlicht falsch — es hat niemand etwas falsch gemacht. */
    $istDemoAdresse = demo_ist_demo_adresse($email);
    if ($istDemoAdresse && !rate_demo_erlaubt()) {
        $bis = rate_demo_gesperrt_bis();
        $error = 'Das Demo-Konto wird gerade sehr häufig genutzt und ist '
               . 'vorübergehend gesperrt'
               . ($bis !== null ? ' — wieder ab ' . fmt_local($bis, 'H:i') . ' Uhr.' : '.')
               . ' Ein eigenes Konto ist davon nicht betroffen.';
        rate_gleiche_dauer($t0);
    } elseif (!rate_erlaubt('login', $email)) {
        $bis = rate_gesperrt_bis('login', $email);
        $error = 'Zu viele Anmeldeversuche. Bitte später erneut versuchen'
               . ($bis !== null ? ' — frühestens ab ' . fmt_local($bis, 'H:i') . ' Uhr.' : '.');
        rate_gleiche_dauer($t0);
    } else {
        // Der Browser sendet nie das Passwort, sondern das daraus
        // abgeleitete Token (siehe assets/crypto.js).
        $st = db()->prepare('SELECT id, password_hash, session_epoch, kdf_iter
                             FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        /* ---- Ein Token je Rundenzahl (M2-01, Schritt 3) -------------------
         *
         * Der Salz-Endpunkt nennt jeder Adresse dieselbe Liste von
         * Rundenzahlen, damit er nicht verraet, welche Konten es gibt. Der
         * Browser kann daher nicht wissen, welche fuer dieses Konto gilt — er
         * leitet fuer JEDE ab und schickt alle Token mit.
         *
         * DER SERVER WEISS ES und sucht sich das passende heraus. Das ist der
         * Grund, warum hier kein Durchprobieren stattfindet: Es gibt genau
         * EINE bcrypt-Pruefung, wie zuvor auch.
         *
         * Format: {"<runden>": "<64 Hexzeichen>", ...}. Streng geprueft, weil
         * es unangemeldet hereinkommt. Das alte Feld 'token' wird weiterhin
         * angenommen — ein Browser mit zwischengespeichertem alten Skript
         * schickt es noch, und die Anmeldung soll daran nicht scheitern.
         */
        $tokenNach = [];
        $roh = json_decode((string)($_POST['tokens'] ?? ''), true);
        if (is_array($roh)) {
            foreach ($roh as $runde => $tk) {
                $r = (int)$runde;
                /* Keine Pruefung gegen eine Liste erlaubter Werte: Der Server
                 * greift gleich unten NUR den Eintrag heraus, der zur
                 * gespeicherten Rundenzahl des Kontos gehoert. Ein Token unter
                 * einem beliebigen anderen Schluessel wird nie angesehen. */
                if ($r > 0 && is_string($tk) && preg_match('/^[0-9a-f]{64}$/', $tk)) {
                    $tokenNach[$r] = $tk;
                }
            }
        }
        if (isset($_POST['token']) && preg_match('/^[0-9a-f]{64}$/', (string)$_POST['token'])) {
            // Altes Feld: es galt immer die frueher fest verdrahtete Zahl.
            $tokenNach[310000] = $tokenNach[310000] ?? (string)$_POST['token'];
        }

        if ($u && $u['password_hash'] !== null) {
            $konto = (int)($u['kdf_iter'] ?? 0) ?: 310000;
            $token = $tokenNach[$konto] ?? '';
            $ok = $token !== '' && password_verify($token, $u['password_hash']);
        } else {
            // Unbekannte Adresse oder Konto ohne gesetztes Passwort: trotzdem
            // eine bcrypt-Pruefung, damit dieser Zweig nicht schneller ist.
            password_verify(reset($tokenNach) ?: '', AUTH_VERGLEICHSWERT);
            $ok = false;
        }

        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$u['id'];
            /* Stand des Sitzungszaehlers mitfuehren (M1-09). Jede Anfrage
             * vergleicht ihn in auth_guard.php gegen die Zeile; ein
             * Passwortwechsel erhoeht ihn und beendet damit alle Sitzungen,
             * die noch den alten Stand tragen. */
            $_SESSION['epoch']   = (int)($u['session_epoch'] ?? 0);
            /* Die Rolle wird NICHT mehr hier abgelegt (M1-05). Sie kam
             * frueher aus dieser einen Zeile und wurde nie wieder geprueft —
             * ein Rollenentzug wirkte erst nach dem naechsten Anmelden.
             * auth_guard.php liest sie jetzt bei jeder Anfrage aus der
             * Nutzerzeile, die dort ohnehin gelesen wird. */
            // Alte Sitzungsbremse aufraeumen: Auf Rechnern, die vor dieser
            // Fassung angemeldet waren, liegen die beiden Werte noch herum.
            unset($_SESSION['login_fails'], $_SESSION['login_last'], $_SESSION['role']);
            rate_erfolg('login', $email);
            // Auch den Zaehler des Salz-Endpunkts leeren — jede Anmeldung
            // verbraucht dort einen Versuch, und wer sich erfolgreich
            // anmeldet, soll sich nicht selbst aussperren.
            rate_erfolg('salt', $email);
            /* Die Demo-Bremse zaehlt GELUNGENE Anmeldungen — deshalb hier,
             * zwischen den beiden rate_erfolg()-Aufrufen, die Zaehler leeren.
             * Kein Widerspruch: Jene betreffen den Fehlversuchsschutz des
             * Kontos, dieser die Nutzungsmenge des Demo-Kontos. */
            if ($istDemoAdresse) { rate_demo_zaehlen(); }
            header('Location: index.php'); exit;
        }
        rate_misserfolg('login', $email);
        $error = 'Anmeldung fehlgeschlagen. E-Mail oder Passwort prüfen.';
        rate_gleiche_dauer($t0);
    }
}
require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start(['titel' => 'Anmelden', 'klasse' => 'login-body']);
?>
<main class="login-card">
  <img src="<?= e(logo_src()) ?>"
       alt="GenEM" class="login-logo">
  <h1>Einsatzdoku</h1>
  <?php /* Beide schliessen einander aus: Steht ein Fehler an, tritt der
           Hinweis zurueck. Die Reihenfolge in ui_meldung() ist deshalb
           ohne Wirkung. */ ?>
  <?php ui_meldung($error ? null : $hinweis, $error); ?>
  <form method="post" autocomplete="on" id="loginform">
    <?php /* Ein Token je Rundenzahl (M2-01). Das alte Feld 'token' entfaellt —
             der Server nimmt es weiterhin an, aber diese Seite fuellt es nicht
             mehr, weil sie nicht weiss, welche Rundenzahl fuer das Konto gilt. */ ?>
    <input type="hidden" name="tokens" id="toks">
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

    /* ---- Für jede genannte Rundenzahl ableiten (M2-01, Schritt 3) --------
     *
     * Der Salz-Endpunkt nennt jeder Adresse dieselbe Liste — er darf nicht
     * verraten, welche Zahl für dieses Konto gilt, sonst wäre die Antwort für
     * echte und erfundene Adressen wieder unterscheidbar. Der Browser rechnet
     * deshalb für alle und schickt alle Token; der Server nimmt das passende.
     *
     * WAS DAS KOSTET: Solange die Liste zwei Einträge hat, dauert die
     * Anmeldung doppelt so lange. Das ist der Übergangszustand während einer
     * Anhebung, nicht der Dauerzustand — steht nur ein Wert in der Liste, ist
     * alles wie vorher.
     *
     * Deshalb die Zwischenmeldung: Ohne sie wirkt die Seite in dieser Zeit
     * eingefroren, und zwar doppelt so lange wie gewohnt. */
    const runden = Array.isArray(d.iter) && d.iter.length ? d.iter : [310000];
    state.textContent = 'Schlüssel werden abgeleitet …';
    const tokens = {}, datenschluessel = {};
    for (const it of runden) {
      const k = await EdCrypto.deriveKeys(pw, d.salt, it);
      tokens[it] = k.authToken;
      datenschluessel[it] = k.dataKeyHex;
    }
    document.getElementById('toks').value = JSON.stringify(tokens);

    /* Der Datenschlüssel kann erst gesetzt werden, wenn feststeht, welche
     * Rundenzahl gilt — und das weiß erst die nächste, angemeldete Seite
     * (KDF_ITER aus auth_guard.php). Bei nur einer Zahl gibt es nichts zu
     * entscheiden, dann läuft es wie bisher.
     *
     * Die Ablage im Vormerkfach ist dasselbe Verfahren, das der
     * Passwortwechsel seit Web 4.5.0 benutzt (M2-07): Der neue Stand liegt
     * bereit, wird aber erst übernommen, wenn der Server ihn bestätigt hat. */
    if (runden.length === 1) {
      EdCrypto.setDataKey(datenschluessel[runden[0]]);
    } else {
      EdCrypto.merkeAbleitungen(datenschluessel, tokens);
    }
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
<?php ui_seite_ende(); ?>
