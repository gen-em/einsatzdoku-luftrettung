/* Wege der Seite Betrieb → Status → Sicherheit (`betrieb_sicherheit.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250). Der ältere Weg dieser Seite steht
 * noch unter dem Namen seines Pakets (`p5a-ap8.mjs`, E-PK-15 steht aus).
 *
 * „Aufheben" leitet nach dem Bestätigen auf die Seite um; Neuladen hebt nichts
 * ein zweites Mal auf und schreibt kein zweites Ereignis. Die Sperre legt der
 * Weg selbst an (auf ein erfundenes Merkmal, wie `p5a-ap8.mjs`) und räumt sie
 * im `finally` ab.
 */

import { neuladenPruefen } from '../neuladen.mjs';
import { php } from '../probekonto.mjs';

const MERKMAL = 'id:neuladen-r411@example.invalid';
const TOPF    = 'login';

const anlegen = () => php('db()->prepare("INSERT INTO rate_limits (topf, merkmal, versuche,'
  + ' fenster_start, gesperrt_bis, stufe, stufe_bis) VALUES (?,?,9,NOW(),'
  + ' DATE_ADD(NOW(), INTERVAL 900 SECOND),1,DATE_ADD(NOW(), INTERVAL 1 DAY))'
  + ' ON DUPLICATE KEY UPDATE gesperrt_bis = VALUES(gesperrt_bis)")'
  + '->execute(["' + TOPF + '", "' + MERKMAL + '"]);');
const aufraeumen = () => php('db()->prepare("DELETE FROM rate_limits WHERE merkmal = ?")'
  + '->execute(["' + MERKMAL + '"]); db()->prepare("DELETE FROM sicherheit_ereignisse'
  + ' WHERE merkmal = ?")->execute(["' + MERKMAL + '"]);');
const ereignisse = () => Number(php('$s = db()->prepare("SELECT COUNT(*) FROM'
  + ' sicherheit_ereignisse WHERE merkmal = ? AND art = \'aufgehoben\'");'
  + ' $s->execute(["' + MERKMAL + '"]); echo $s->fetchColumn();'));

export const wege = [
  {
    name: 'betrieb-sicherheit-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: '„Aufheben" leitet nach der Rückfrage um; die Meldung steht einmal da, '
        + 'Neuladen ist ein GET ohne sie und schreibt kein zweites Ereignis',
    async fahren(k) {
      aufraeumen();
      anlegen();
      try {
        return await neuladenPruefen(k, {
          name: 'betrieb-sicherheit', seite: `${k.basis}/betrieb_sicherheit.php`,
          async absenden(k) {
            await k.seite.locator('#k-sperren .zeile').filter({ hasText: 'neuladen-r411' })
              .locator('.zeile-aktionen button, .zeile-aktionen a').first().click();
            await k.seite.waitForSelector('dialog[open], .blatt[open]', { timeout: 4000 });
            await k.seite.getByRole('button', { name: 'Aufheben', exact: true }).last().click();
          },
          meldung: 'Die Sperre ist aufgehoben.', ziel: 'betrieb_sicherheit.php',
          zaehler: ereignisse,
        });
      } finally {
        aufraeumen();
      }
    },
  },
];
