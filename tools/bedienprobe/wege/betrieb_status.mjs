/* Wege der Seite Betrieb → Status (`betrieb_status.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Die Testmail leitet in die Karte
 * „E-Mail" um; Neuladen verschickt keine zweite. Welcher der drei Ausgänge
 * kommt (hinaus, wartet, nicht eingereiht), hängt am Versand der Anlage —
 * gemessen wird die Umleitung, nicht die Zustellung.
 *
 * DER WEG STELLT SEINEN STAND HER UND RÄUMT AB: Der Ratenschutz erlaubt drei
 * Testmails je Stunde und Konto; wer die Probe öfter fährt, stünde sonst vor
 * „Zu viele Testmails" (das bleibt ohne Umleitung stehen, E-R4-35). Der
 * Zähler `testmail` wird vorher geleert, die Mail der Probe nachher aus der
 * Warteschlange genommen.
 */

import { neuladenPruefen } from '../neuladen.mjs';
import { php } from '../probekonto.mjs';

const letzteMail = () => Number(php('echo (int)db()->query('
  + '"SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange")->fetchColumn();'));
const mails = () => Number(php('echo (int)db()->query('
  + '"SELECT COUNT(*) FROM mail_warteschlange")->fetchColumn();'));

export const wege = [
  {
    name: 'betrieb-status-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Die Testmail leitet nach #k-mail um; die Meldung steht einmal da, '
        + 'Neuladen ist ein GET ohne sie und reiht keine zweite Mail ein',
    async fahren(k) {
      php('db()->exec("DELETE FROM rate_limits WHERE topf = \'testmail\'");');
      const vorher = letzteMail();
      try {
        return await neuladenPruefen(k, {
          name: 'betrieb-status', seite: `${k.basis}/betrieb_status.php`,
          feld: 'input[name="action"][value="testmail"]',
          meldung: /Die Testmail ist an |Der erste Versuch ist gescheitert|Die Testmail wurde nicht eingereiht/,
          ziel: 'betrieb_status.php', zaehler: mails,
        });
      } finally {
        php('db()->exec("DELETE FROM mail_warteschlange WHERE id > ' + vorher + '");');
      }
    },
  },
];
