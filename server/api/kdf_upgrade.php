<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId, $kdfIter

/**
 * POST api/kdf_upgrade.php — stille Anhebung der Rundenzahl (M2-01, Schritt 4)
 *
 * WAS HIER GESCHIEHT
 * Das Konto rechnet seine Schluesselableitung bisher mit einer niedrigeren
 * Rundenzahl. Der Browser hat sich soeben angemeldet, hat also das Passwort
 * gehabt, und kann daraus beide Ableitungen bilden — die alte und die neue.
 * Er entpackt den Inhaltsschluessel mit dem alten Datenschluessel, verpackt
 * ihn mit dem neuen und schickt beides hierher. Der Server tauscht
 * Token-Hash, Rundenzahl und Schluesselhuelle in EINER Transaktion.
 *
 * Body: {
 *   "alt_token": 64 Hex,     Nachweis: das Token der BISHERIGEN Rundenzahl
 *   "neu_token": 64 Hex,     Token der neuen Rundenzahl
 *   "neu_iter":  Zahl aus KDF_ITER_LISTE,
 *   "wrap_pw":   neue Schluesselhuelle (entfaellt bei Konten ohne Huelle),
 *   "key_check": Pruefsumme des Inhaltsschluessels (32 Hex, optional)
 * }
 * Antwort: { ok: true } — oder { error: ... }
 *
 * ---- WARUM DAS ALTE TOKEN VERLANGT WIRD ----------------------------------
 *
 * Dieser Endpunkt setzt den Hash, gegen den sich das Konto anmeldet. Er ist
 * damit funktional eine Passwortaenderung. Ohne Nachweis koennte, wer eine
 * offene Sitzung uebernimmt, ein beliebiges Token setzen und das Konto
 * uebernehmen — die Sitzung allein darf dafuer nicht genuegen. Wer das alte
 * Token nennen kann, kennt das Passwort; genau das ist die Voraussetzung
 * dafuer, dass die neue Ableitung ueberhaupt zum selben Passwort gehoert.
 *
 * ---- WARUM DER SITZUNGSZAEHLER NICHT ERHOEHT WIRD ------------------------
 *
 * Der Passwortwechsel erhoeht ihn und beendet damit alle anderen Sitzungen —
 * dort ist das der Zweck (M1-09). Hier waere es ein Fehler: Das Passwort hat
 * sich NICHT geaendert, niemand hat einen Verdacht geaeussert, und die
 * Anhebung laeuft ungefragt im Hintergrund. Wuerde sie Sitzungen beenden,
 * fluege bei jeder Anmeldung stillschweigend jedes andere offene Fenster
 * hinaus, ohne dass jemand versteht, warum.
 *
 * ---- WAS BEI EINEM FEHLSCHLAG PASSIERT -----------------------------------
 *
 * Nichts. Die Transaktion faellt zurueck, das Konto behaelt seine alte
 * Rundenzahl und meldet sich beim naechsten Mal wieder damit an. Die Anhebung
 * ist eine Verbesserung, kein Vorgang, dessen Scheitern jemanden aufhalten
 * duerfte — deshalb meldet der Browser einen Fehlschlag auch nicht.
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }
if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
    json_out(['error' => 'csrf'], 403);
}

$b = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($b)) { json_out(['error' => 'format'], 400); }

$altToken = (string)($b['alt_token'] ?? '');
$neuToken = (string)($b['neu_token'] ?? '');
$neuIter  = (int)($b['neu_iter'] ?? 0);
$wrapPw   = isset($b['wrap_pw'])   ? (string)$b['wrap_pw']   : null;
$keyChk   = isset($b['key_check']) ? (string)$b['key_check'] : null;

if (!preg_match('/^[0-9a-f]{64}$/', $altToken)
    || !preg_match('/^[0-9a-f]{64}$/', $neuToken)) {
    json_out(['error' => 'token'], 400);
}
/* Nur Werte aus der Liste. Eine frei waehlbare Rundenzahl waere ein Weg,
 * ein Konto auf einen absurd niedrigen Wert zu setzen — und der Betroffene
 * saehe davon nichts, weil die Anmeldung weiterhin funktioniert. */
if (!in_array($neuIter, KDF_ITER_LISTE, true)) { json_out(['error' => 'iter'], 400); }
// Nur nach oben. Eine Anhebung, die senkt, ist keine.
if ($neuIter <= $kdfIter) { json_out(['error' => 'nicht_noetig'], 400); }

// Dieselbe Laengengrenze wie beim Passwortwechsel (M2-08): Die Pruefung
// begrenzt, das Speichern schneidet NICHT ab.
if ($wrapPw !== null && !preg_match('#^[A-Za-z0-9+/=]{20,4000}$#', $wrapPw)) {
    json_out(['error' => 'wrap'], 400);
}
if ($keyChk !== null && $keyChk !== '' && !preg_match('/^[0-9a-f]{32}$/', $keyChk)) {
    json_out(['error' => 'key_check'], 400);
}

$pdo = db();
$st = $pdo->prepare('SELECT password_hash, pat_wrap_pw, pat_key_check
                     FROM users WHERE id = ?');
$st->execute([$userId]);
$u = $st->fetch();
if (!$u || $u['password_hash'] === null) { json_out(['error' => 'konto'], 400); }

// Nachweis: Wer das alte Token nennen kann, kennt das Passwort.
if (!password_verify($altToken, (string)$u['password_hash'])) {
    json_out(['error' => 'nachweis'], 403);
}

/* Huelle und Pruefsumme gehoeren zusammen und sind Pflicht, SOBALD das Konto
 * eine Huelle hat. Ohne neue Huelle waere die alte nach dem Tausch nicht mehr
 * zu oeffnen — der Datenschluessel hat sich geaendert — und die geschuetzten
 * Angaben waeren verloren. Lieber gar nichts tun. */
if ($u['pat_wrap_pw'] !== null && ($wrapPw === null || $wrapPw === '')) {
    json_out(['error' => 'wrap_fehlt'], 400);
}

try {
    $pdo->beginTransaction();
    $pdo->prepare('UPDATE users SET password_hash = ?, kdf_iter = ? WHERE id = ?')
        ->execute([password_hash($neuToken, PASSWORD_DEFAULT), $neuIter, $userId]);
    if ($u['pat_wrap_pw'] !== null) {
        /* Die Pruefsumme darf sich NICHT aendern: Es ist derselbe
         * Inhaltsschluessel, nur anders verpackt. Weicht sie ab, hat der
         * Browser etwas anderes verpackt als das, was drinstand — dann lieber
         * abbrechen, als eine Huelle zu speichern, die zu nichts passt. */
        if ($keyChk !== null && $keyChk !== '' && $u['pat_key_check'] !== null
            && !hash_equals((string)$u['pat_key_check'], $keyChk)) {
            $pdo->rollBack();
            json_out(['error' => 'key_check_abweichung'], 409);
        }
        $pdo->prepare('UPDATE users SET pat_wrap_pw = ? WHERE id = ?')
            ->execute([$wrapPw, $userId]);
    }
    $pdo->commit();
} catch (Throwable $ex) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_out(['error' => 'fehlgeschlagen'], 500);
}

json_out(['ok' => true, 'iter' => $neuIter]);
