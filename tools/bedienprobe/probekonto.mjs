/* Ein Konto mit bekanntem Passwort — für Wege, die weder das Prüfkonto noch
 * das Demo-Konto brauchen können (P5c/AP5).
 * ===========================================================================
 *
 * WOZU: Das Prüfkonto hat seinen Zweitfaktor schon, und das Demo-Konto darf
 * keinen haben. Wer den Zweitfaktor EINRICHTEN will, braucht ein Konto ohne
 * ihn, dessen Passwort er kennt. Zwei Wege tun das (`wege/zweitfaktor.mjs`,
 * `wege/einstellungen_profil.mjs`); die Anlage steht deshalb einmal, hier —
 * und nicht unter `wege/`, weil der Läufer dort jede Datei als Wegdatei lädt.
 *
 * DAS TOKEN ENTSTEHT WIE IM BROWSER (PBKDF2-SHA-256, 64 Byte, die zweite
 * Hälfte), gespeichert wird sein Hash. Die erste Hälfte ist die des
 * Datenschlüssels; das Konto hat keinen, und keiner der Wege öffnet
 * Patientendaten.
 *
 * AUFRÄUMEN VOR UND NACH: `probekontoAnlegen()` räumt ein übrig gebliebenes
 * Konto derselben Adresse zuerst ab — ein abgebrochener Lauf soll den
 * nächsten nicht an einer doppelten Adresse scheitern lassen.
 */

import { execFileSync } from 'node:child_process';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const HIER = dirname(fileURLToPath(import.meta.url));
export const WURZEL = process.env.ED_WURZEL || join(HIER, '..', '..');

export function php(code) {
  return execFileSync('php', ['-r', 'require "server/db.php"; ' + code],
    { cwd: WURZEL, encoding: 'utf-8' }).trim();
}

export function probekontoRaeumen(adresse) {
  php(`$st = db()->prepare("SELECT id FROM users WHERE email = ?");
       $st->execute([${JSON.stringify(adresse)}]);
       foreach ($st->fetchAll(PDO::FETCH_COLUMN) as $id) {
         db()->prepare("DELETE FROM protokoll_ereignisse WHERE betroffen_user_id = ?")->execute([$id]);
         db()->prepare("DELETE FROM users WHERE id = ?")->execute([$id]);
       }`);
}

/** Legt das Konto an und gibt seine Nummer zurück. */
export function probekontoAnlegen(adresse, passwort, rolle, name = 'Bedienprobe') {
  probekontoRaeumen(adresse);
  return Number(php(`
    $pw = ${JSON.stringify(passwort)}; $salz = bin2hex(random_bytes(16)); $iter = KDF_ITER_ZIEL;
    $tok = bin2hex(substr(hash_pbkdf2("sha256", $pw, hex2bin($salz), $iter, 64, true), 32, 32));
    db()->prepare("INSERT INTO users (email, name, role, password_hash, kdf_salt, kdf_iter)
                   VALUES (?, ?, ?, ?, ?, ?)")
        ->execute([${JSON.stringify(adresse)}, ${JSON.stringify(name)}, ${JSON.stringify(rolle)},
                   password_hash($tok, PASSWORD_DEFAULT), $salz, $iter]);
    echo db()->lastInsertId();`));
}

/** Ein eigener Browserkontext neben dem des Läufers — dieselbe Fenstergröße,
 *  dieselbe Nachsicht mit dem Zertifikat der Sandbox, keine Kartenkacheln. */
export async function eigenerKontext(k) {
  const kontext = await k.seite.context().browser().newContext({
    viewport: k.seite.viewportSize(), ignoreHTTPSErrors: true });
  await kontext.route('**/tile.openstreetmap.org/**', r => r.abort());
  return kontext;
}

/** Passwort abschicken; wohin es danach geht, entscheidet der Aufrufer. */
export async function passwortSchicken(s, basis, adresse, passwort) {
  await s.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await s.fill('input[name="email"]', adresse);
  await s.fill('input[name="password"]', passwort);
  await s.click('#loginform button[type="submit"]');
}

/** Ein Konto MIT Schlüsselhülle (Konzept RW, RW-02) — über die Einladung
 *  und `pw_handling.php`, im Browser, wie jedes echte. Wege, die einen
 *  Inhaltsschlüssel brauchen (das Paar des Rückwegs verpackt seinen privaten
 *  Teil damit), können `probekontoAnlegen()` nicht nehmen: Dessen Konten
 *  haben keine Hülle. Gibt Nummer und Wiederherstellungsschlüssel zurück. */
export async function passwortSetzen(s, basis, adresse, passwort, name = 'Bedienprobe',
                                     rolle = 'user') {
  probekontoRaeumen(adresse);
  const a = JSON.parse(php(`require_once "server/konto_lib.php";
    echo json_encode(konto_anlegen(${JSON.stringify(adresse)}, ${JSON.stringify(name)},
                                   ${JSON.stringify(rolle)}, "einladung"));`));
  await s.goto(`${basis}/pw_handling.php?token=${a.token}`, { waitUntil: 'domcontentloaded' });
  await s.fill('#pw1', passwort);
  await s.fill('#pw2', passwort);
  await s.click('#gobtn');
  await s.waitForSelector('#rcbox:not([hidden])', { timeout: 60000 });
  const rc = ((await s.locator('#rccode').textContent()) || '').trim();
  await s.check('#rcok');
  await Promise.all([
    s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 }),
    s.click('#gobtn'),
  ]);
  return { id: Number(a.id), rc };
}
