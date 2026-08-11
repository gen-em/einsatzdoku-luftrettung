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

$token = (string)($_GET['token'] ?? $_POST['token'] ?? '');
$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT r.id, r.user_id, u.pat_wrap_rc
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

    if (!preg_match('/^[0-9a-f]{64}$/', $neuTok) || !preg_match('/^[0-9a-f]{32}$/', $neuSalt)) {
        // Kommt nur vor, wenn JavaScript fehlt oder abbricht.
        $error = 'Speichern unvollständig — bitte JavaScript aktivieren und erneut versuchen.';
    } elseif (!preg_match(WRAP_RE, $wrapPw)) {
        $error = $erstvergabe
            ? 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.'
            : 'Der Wiederherstellungsschlüssel passt nicht. Es wurde nichts geändert.';
    } elseif ($erstvergabe && !preg_match(WRAP_RE, $wrapRc)) {
        $error = 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($erstvergabe) {
                // Passwort und BEIDE Huellen in einem Zug — ein Konto ohne
                // Wiederherstellungs-Huelle waere nach einem Reset verloren.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                pat_wrap_pw = ?, pat_wrap_rc = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $wrapPw, $wrapRc, (int)$row['user_id']]);
            } else {
                // Reset: der Inhaltsschluessel bleibt derselbe, nur seine
                // Passwort-Huelle wird ersetzt. pat_wrap_rc bleibt unberuehrt,
                // damit der bekannte Wiederherstellungsschluessel gueltig bleibt.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                pat_wrap_pw = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $wrapPw, (int)$row['user_id']]);
            }
            $pdo->prepare('UPDATE password_resets SET used_at = NOW() WHERE id = ?')
                ->execute([(int)$row['id']]);
            $pdo->commit();
            $done = true;
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
      <input type="hidden" name="token"     value="<?= e($token) ?>">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
      <input type="hidden" name="wrap_rc"   id="wrap_rc">
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
      <input type="hidden" name="token"     value="<?= e($token) ?>">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
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
