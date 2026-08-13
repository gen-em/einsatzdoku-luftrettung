<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';

/**
 * Passwort setzen — die einzige Stelle, an der ein Passwort ueber einen
 * Einmal-Link vergeben wird. Loest reset_confirm.php und einrichtung.php ab.
 *
 * Zwei Betriebsarten, die der Server allein aus dem Kontostand bestimmt
 * (niemals aus dem, was der Browser mitschickt):
 *
 *  - ERSTVERGABE  (pat_wrap_rc IS NULL): Das Konto hat noch keinen
 *    Inhaltsschluessel. Der Browser erzeugt ihn zusammen mit dem
 *    Wiederherstellungsschluessel, zeigt letzteren EINMALIG an und laesst ihn
 *    bestaetigen. Erst danach wandern Passwort-Hash, Salt und beide Huellen
 *    gemeinsam in die Datenbank.
 *
 *  - RESET (pat_wrap_rc vorhanden): Ein neues Passwort macht die
 *    Passwort-Huelle des Inhaltsschluessels wertlos. Der
 *    Wiederherstellungsschluessel ist deshalb zwingend — der Browser entpackt
 *    damit den Inhaltsschluessel und verpackt ihn fuer das neue Passwort neu.
 *
 * Der Server sieht in beiden Faellen nur Chiffretext: nie das Passwort, nie
 * den Wiederherstellungsschluessel, nie den Inhaltsschluessel. Geschrieben
 * wird ausschliesslich in einer Transaktion — sonst entstuende ein Konto, das
 * sich zwar anmelden laesst, dessen Daten aber unlesbar waeren.
 */

const WRAP_RE = '#^[A-Za-z0-9+/=]{20,4000}$#';

/* ---- Der Token gehoert nicht in die Adresszeile (M1-06) --------------------
 *
 * WAS DARAN FALSCH WAR
 * Der Token stand als Parameter in der Adresse. Damit landete er im Verlauf
 * des Browsers, im Zugriffsprotokoll des Webservers, in jedem Screenshot der
 * Seite — und ueber die Herkunftsadresse potenziell bei jeder Gegenstelle, die
 * von dieser Seite aus angefragt wird. Wer ihn hat, kann das Passwort setzen.
 *
 * STUFE 1 — Herkunftsadresse unterbinden.
 * Referrer-Policy: no-referrer nimmt die Vervielfachung durch Unterabrufe
 * heraus. Cache-Control: no-store haelt die Seite aus dem Zwischenspeicher.
 *
 * STUFE 2 — den Token gegen einen sitzungsgebundenen Wert tauschen.
 * Beim ersten Aufruf wandert er in eine Sitzung und die Seite leitet auf sich
 * selbst weiter, ohne Parameter. Ab da steht er in keiner Adresszeile mehr.
 *
 * WARUM EIN EIGENER SITZUNGSNAME UND SameSite=Lax
 * Der Klick kommt aus dem Mailprogramm, also von einer FREMDEN Seite. Ein
 * Cookie mit SameSite=Strict kaeme bei der Weiterleitung nicht zurueck — die
 * Seite waere danach eine Sackgasse. Lax wird bei Seitenaufrufen dieser Art
 * gesendet und haelt zugleich fremde POST-Anfragen ab.
 *
 * Der EIGENE Name (nicht der der Anwendung) ist dabei nicht Kosmetik: Wuerde
 * hier der Sitzungscookie der Anwendung mit Lax neu gesetzt, verloere eine
 * parallel offene, angemeldete Sitzung im selben Browser ihren Strict-Schutz.
 * Zwei Namen, zwei Sitzungen, keine Wechselwirkung.
 */
const PW_SESSION_NAME = 'EDPWSESS';

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');

function pw_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) { return; }
    session_name(PW_SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true, 'secure' => true, 'samesite' => 'Lax', 'path' => '/',
    ]);
    session_start();
}

$tokenAusAdresse = (string)($_GET['token'] ?? '');
$getauscht       = isset($_GET['w']);        // "weitergeleitet", zweiter Aufruf

if ($tokenAusAdresse !== '') {
    // Erster Aufruf: Token einlagern und ohne Parameter neu aufrufen. Ob er
    // gueltig ist, wird danach geprueft — die Weiterleitung erfolgt in jedem
    // Fall, sonst waere schon die Adresszeile die Auskunft, ob ein Token zieht.
    pw_session_start();
    session_regenerate_id(true);
    $_SESSION['pw_token'] = $tokenAusAdresse;
    header('Location: pw_handling.php?w=1', true, 302);
    exit;
}

pw_session_start();
$token = (string)($_SESSION['pw_token'] ?? '');

/* Kein Token in der Sitzung, obwohl die Weiterleitung gelaufen ist: Der
 * Browser hat den Cookie nicht angenommen. Das ist die einzige Sackgasse
 * dieses Weges — sie wird benannt, statt als "Link ungueltig" zu erscheinen
 * und die Person einen zweiten, ebenso wirkungslosen Link anfordern zu lassen. */
$keinCookie = ($token === '' && $getauscht);

$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT r.id, r.user_id, u.pat_key_check, u.pat_wrap_rc
                         FROM password_resets r
                         JOIN users u ON u.id = r.user_id
                         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch();
}

// Betriebsart: ohne Wiederherstellungs-Huelle gibt es noch keinen
// Inhaltsschluessel — dann ist dies die Erstvergabe.
$erstvergabe = $row !== null && $row['pat_wrap_rc'] === null;

$error = null; $done = false;
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $neuTok  = (string)($_POST['new_token'] ?? '');
    $neuSalt = (string)($_POST['new_salt'] ?? '');
    $wrapPw  = (string)($_POST['wrap_pw'] ?? '');
    $wrapRc  = (string)($_POST['wrap_rc'] ?? '');
    $keyChk  = (string)($_POST['key_check'] ?? '');
    $chkSoll = $row['pat_key_check'] ?? null;

    if (!preg_match('/^[0-9a-f]{64}$/', $neuTok) || !preg_match('/^[0-9a-f]{32}$/', $neuSalt)) {
        // Kommt nur vor, wenn JavaScript fehlt oder abbricht.
        $error = 'Speichern unvollständig — bitte JavaScript aktivieren und erneut versuchen.';
    } elseif (!preg_match(WRAP_RE, $wrapPw)) {
        $error = $erstvergabe
            ? 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.'
            : 'Der Wiederherstellungsschlüssel passt nicht. Es wurde nichts geändert.';
    } elseif ($erstvergabe && !preg_match(WRAP_RE, $wrapRc)) {
        $error = 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.';
    } elseif ($keyChk !== '' && !preg_match('/^[0-9a-f]{32}$/', $keyChk)) {
        $error = 'Die Prüfsumme des Inhaltsschlüssels ist unbrauchbar. Es wurde nichts geändert.';
    } elseif (!$erstvergabe && $chkSoll !== null && $keyChk !== $chkSoll) {
        /* Beim Zuruecksetzen wird der Inhaltsschluessel aus der
         * Wiederherstellungs-Huelle entpackt und in eine neue Passwort-Huelle
         * gepackt. Der Server kann keine der beiden oeffnen. Passt die
         * Pruefsumme nicht, steckt ein ANDERER Schluessel in der neuen Huelle
         * — danach waere jeder vorhandene Datensatz endgueltig unlesbar.
         * Bestandskonten ohne gespeicherte Pruefsumme werden angenommen und
         * bekommen sie unten; der Server kann sie nicht selbst berechnen. */
        $error = 'Der Inhaltsschlüssel gehört nicht zu diesem Konto. '
               . 'Es wurde nichts geändert.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($erstvergabe) {
                // Passwort und BEIDE Huellen in einem Zug — ein Konto ohne
                // Wiederherstellungs-Huelle waere nach einem Reset verloren.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                pat_wrap_pw = ?, pat_wrap_rc = ?,
                                                pat_key_check = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $wrapPw, $wrapRc, $keyChk !== '' ? $keyChk : null,
                               (int)$row['user_id']]);
            } else {
                // Reset: der Inhaltsschluessel bleibt derselbe, nur seine
                // Passwort-Huelle wird ersetzt. pat_wrap_rc bleibt unberuehrt,
                // damit der bekannte Wiederherstellungsschluessel gueltig bleibt.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                pat_wrap_pw = ?, pat_key_check = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $wrapPw, $keyChk !== '' ? $keyChk : null,
                               (int)$row['user_id']]);
            }
            /* ALLE offenen Tokens dieses Kontos entwerten, nicht nur den
             * benutzten (M1-09). reset_request.php laesst seit Web 4.4.0 zwar
             * nur noch einen gueltigen zu — aber der Einladungslink aus der
             * Nutzerverwaltung ist 24 Stunden gueltig und entsteht auf einem
             * anderen Weg. Wer sein Passwort setzt, soll danach keinen
             * fremden Weg mehr offen haben. */
            $pdo->prepare('UPDATE password_resets SET used_at = NOW()
                           WHERE user_id = ? AND used_at IS NULL')
                ->execute([(int)$row['user_id']]);
            /* Sitzungszaehler erhoehen (M1-09/D6): beendet jede offene Sitzung
             * dieses Kontos. Hier ist keine davon die eigene — wer diese Seite
             * benutzt, ist nicht angemeldet. Genau der Fall, um dessentwillen
             * der Zaehler existiert: Passwort zuruecksetzen, weil jemand
             * anders drin ist. */
            $pdo->prepare('UPDATE users SET session_epoch = session_epoch + 1 WHERE id = ?')
                ->execute([(int)$row['user_id']]);
            $pdo->commit();
            $done = true;
            // Der Token hat seinen Zweck erfuellt — die Sitzung dieser Seite
            // wird nicht laenger gebraucht (M1-06).
            unset($_SESSION['pw_token']);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $error = 'Speichern fehlgeschlagen. Bitte erneut versuchen.';
        }
    }
}
?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $erstvergabe ? 'Passwort festlegen' : 'Neues Passwort' ?> — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body class="login-body">
<div class="login-wrap">
<main class="login-card setup-card">
  <img src="<?= e(logo_src()) ?>" alt="Einsatzdoku" class="login-logo">

  <?php if ($done): ?>
    <h1>Fertig</h1>
    <?php if ($erstvergabe): ?>
      <p>Passwort gespeichert und die Verschlüsselung eingerichtet.
         Du kannst dich jetzt anmelden.</p>
      <p class="muted small">Bewahre den Wiederherstellungsschlüssel sicher auf —
         nach einem Passwort-Reset ist er der einzige Weg zu deinen Daten.</p>
    <?php else: ?>
      <p>Passwort gespeichert und die verschlüsselten Angaben übernommen.
         Du kannst dich jetzt anmelden.</p>
    <?php endif; ?>
    <p class="login-aux"><a href="login.php">Zur Anmeldung</a></p>

  <?php elseif ($keinCookie): ?>
    <h1>Cookie nötig</h1>
    <p class="alert">Dieser Browser hat den nötigen Cookie nicht angenommen.
       Der Link lässt sich deshalb nicht öffnen.</p>
    <p class="muted">Der Link aus der E-Mail wird beim ersten Öffnen aus der
       Adresszeile genommen und in einem Cookie abgelegt — er soll weder im
       Verlauf noch in Protokollen stehen bleiben. Ohne Cookies geht dieser
       Weg nicht; die Anmeldung selbst braucht ebenfalls einen.</p>
    <p class="muted">Bitte Cookies für diese Seite erlauben (auch ein privates
       Fenster mit strengen Einstellungen kann die Ursache sein) und den Link
       aus der E-Mail erneut öffnen.</p>
    <p class="login-aux"><a href="reset_request.php">Neuen Link anfordern</a></p>

  <?php elseif (!$row): ?>
    <h1>Link ungültig</h1>
    <p class="alert">Dieser Link ist ungültig oder abgelaufen.</p>
    <p class="login-aux"><a href="reset_request.php">Neuen Link anfordern</a></p>

  <?php elseif ($erstvergabe): ?>
    <h1>Passwort festlegen</h1>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <p>Diagnose, Alter und Einsatzort werden <strong>Ende-zu-Ende-verschlüsselt</strong>
       gespeichert. Der Schlüssel entsteht aus deinem Passwort und verlässt deinen
       Browser nie — der Server kann die Angaben nicht lesen.</p>
    <p><strong>Deshalb ist die Stärke deines Passworts unmittelbar die Stärke der
       Verschlüsselung.</strong> Weil der Server das Passwort nie sieht, kann er
       seine Güte auch nicht prüfen und ein schwaches nicht ausgleichen — es gibt
       keine zweite Hürde dahinter. Wähle etwas Langes, das du dir merken kannst;
       vier zufällige Wörter sind besser als acht verdrehte Zeichen.</p>
    <p><strong>Wichtig:</strong> Nach dem Festlegen wird einmalig dein persönlicher
       <strong>Wiederherstellungsschlüssel</strong> angezeigt. Er ist nach einem
       Passwort-Reset der einzige Weg zu deinen Daten — ausdrucken oder sicher ablegen.</p>

    <div id="rcbox" class="keybox" hidden>
      <strong>Dein Wiederherstellungsschlüssel</strong>
      <p class="codebig" id="rccode" style="font-size:1.25rem"></p>
      <label class="checklabel"><input type="checkbox" id="rcok">
        Ich habe den Schlüssel sicher notiert.</label>
    </div>

    <form method="post" id="pwform">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
      <input type="hidden" name="wrap_rc"   id="wrap_rc">
      <input type="hidden" name="key_check" id="key_check">
      <label>Passwort (min. 10 Zeichen)
        <input type="password" id="pw1" required minlength="10" autocomplete="new-password">
      </label>
      <label>Wiederholen
        <input type="password" id="pw2" required minlength="10" autocomplete="new-password">
      </label>
      <button type="submit" class="btn-primary" id="gobtn">Passwort festlegen</button>
      <p class="muted small" id="state" style="min-height:1.2em"></p>
    </form>

  <?php else: ?>
    <h1>Neues Passwort</h1>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>
    <p class="muted">Deine Einsatzdaten sind mit deinem Passwort verschlüsselt.
       Damit sie lesbar bleiben, brauchst du hier den
       <strong>Wiederherstellungsschlüssel</strong> aus der Einrichtung.</p>
    <form method="post" id="pwform">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
      <input type="hidden" name="key_check" id="key_check">
      <label>Wiederherstellungsschlüssel
        <input type="text" id="rc" required autocomplete="off" autocapitalize="characters"
               placeholder="ABCD-EFGH-JKMN-PQRS-TVWX">
      </label>
      <label>Neues Passwort (min. 10 Zeichen)
        <input type="password" id="pw1" required minlength="10" autocomplete="new-password">
      </label>
      <label>Wiederholen
        <input type="password" id="pw2" required minlength="10" autocomplete="new-password">
      </label>
      <button type="submit" class="btn-primary">Passwort speichern</button>
      <p class="muted small" id="state" style="min-height:1.2em"></p>
    </form>
  <?php endif; ?>
</main>
<footer class="sitefooter">© Gen-EM – OpenSource Software –
  <a href="https://github.com/gen-em/einsatzdoku-luftrettung/blob/main/LICENSE"
     target="_blank" rel="noopener">AGPL-3.0</a></footer>
</div>

<?php if ($row && !$done): ?>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<script>
const ERSTVERGABE = <?= $erstvergabe ? 'true' : 'false' ?>;
const WRAP_RC = <?= json_encode($erstvergabe ? null : $row['pat_wrap_rc']) ?>;
const state = document.getElementById('state');
const form  = document.getElementById('pwform');

// Reste einer frueheren Sitzung in diesem Browser entfernen: Ein alter
// Inhaltsschluessel im sessionStorage wuerde nach der Anmeldung faelschlich
// wiederverwendet und die Entschluesselung scheitern lassen.
EdCrypto.clearSession();

/* ---- Erstvergabe: Schluessel erzeugen, anzeigen, bestaetigen ---------- */
if (ERSTVERGABE) {
  let erzeugt = false;
  form.addEventListener('submit', async ev => {
    if (form.dataset.ready === '1') return;      // zweiter Durchlauf: senden
    ev.preventDefault();

    // Zweiter Klick: nur noch den Haken pruefen und absenden.
    if (erzeugt) {
      if (!document.getElementById('rcok').checked) {
        state.textContent = 'Bitte bestätigen, dass der Schlüssel notiert ist.';
        return;
      }
      form.dataset.ready = '1';
      form.submit();
      return;
    }

    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    if (pw1.length < 10) { state.textContent = 'Mindestens 10 Zeichen.'; return; }
    if (pw1 !== pw2)     { state.textContent = 'Die Passwörter stimmen nicht überein.'; return; }

    try {
      state.textContent = 'Schlüssel werden erzeugt …';
      const salt = EdCrypto.randomHex(16);
      const k    = await EdCrypto.deriveKeys(pw1, salt);
      const ck   = EdCrypto.randomHex(32);          // Inhaltsschluessel
      const rc   = EdCrypto.newRecoveryCode();
      const rk   = await EdCrypto.recoveryKeyHex(rc);

      document.getElementById('new_salt').value  = salt;
      document.getElementById('new_token').value = k.authToken;
      document.getElementById('wrap_pw').value   = await EdCrypto.encrypt(k.dataKeyHex, ck);
      document.getElementById('wrap_rc').value   = await EdCrypto.encrypt(rk, ck);
      // Pruefsumme des Inhaltsschluessels: Sie wird hier erstmals gesetzt und
      // ist ab jetzt der Massstab, an dem jedes spaetere Umpacken gemessen
      // wird. Ohne sie koennte ein Fehler beim Passwortwechsel eine Huelle
      // speichern, die einen ANDEREN Schluessel enthaelt — danach waere jeder
      // Datensatz endgueltig unlesbar.
      document.getElementById('key_check').value = await EdCrypto.contentKeyCheck(ck);

      // Ab hier darf das Passwort nicht mehr geaendert werden — die Huelle
      // ist bereits an den daraus abgeleiteten Schluessel gebunden.
      document.getElementById('pw1').readOnly = true;
      document.getElementById('pw2').readOnly = true;

      document.getElementById('rccode').textContent = rc;
      document.getElementById('rcbox').hidden = false;
      document.getElementById('gobtn').textContent = 'Speichern und abschließen';
      state.textContent = 'Schlüssel notieren, Haken setzen, dann abschließen.';
      erzeugt = true;
    } catch (e) {
      state.textContent = 'Fehlgeschlagen: ' + e.message;
    }
  });

/* ---- Reset: Inhaltsschluessel umpacken -------------------------------- */
} else {
  form.addEventListener('submit', async ev => {
    if (form.dataset.ready === '1') return;
    ev.preventDefault();

    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    const rc  = document.getElementById('rc').value.trim();
    if (pw1.length < 10) { state.textContent = 'Mindestens 10 Zeichen.'; return; }
    if (pw1 !== pw2)     { state.textContent = 'Die Passwörter stimmen nicht überein.'; return; }

    try {
      state.textContent = 'Wiederherstellungsschlüssel wird geprüft …';
      const rk = await EdCrypto.recoveryKeyHex(rc);
      let ck;
      try {
        ck = await EdCrypto.decrypt(rk, WRAP_RC);       // Inhaltsschluessel entpacken
      } catch (e) {
        state.textContent = 'Der Wiederherstellungsschlüssel passt nicht.';
        return;                                        // nichts absenden
      }

      state.textContent = 'Neues Passwort wird eingerichtet …';
      const salt = EdCrypto.randomHex(16);
      const k    = await EdCrypto.deriveKeys(pw1, salt);

      document.getElementById('new_salt').value  = salt;
      document.getElementById('new_token').value = k.authToken;
      document.getElementById('wrap_pw').value   = await EdCrypto.encrypt(k.dataKeyHex, ck);
      document.getElementById('key_check').value = await EdCrypto.contentKeyCheck(ck);

      form.dataset.ready = '1';
      form.submit();
    } catch (e) {
      state.textContent = 'Fehlgeschlagen: ' + e.message;
    }
  });
}
</script>
<?php endif; ?>
</body>
</html>
