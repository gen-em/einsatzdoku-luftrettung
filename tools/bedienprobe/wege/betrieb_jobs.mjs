/* Wege der Seite Betrieb → Hintergrundjobs (`betrieb_jobs.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Anhalten und Aufheben leiten in die
 * Karte „Zustand" um; Neuladen setzt die Pause nicht ein zweites Mal. Der
 * Pausenhinweis der Karte sagt dasselbe wie die Meldung („… angehalten bis"),
 * solange die Pause gilt — `neuladenPruefen()` zählt deshalb Treffer.
 *
 * DER STAND DAVOR KOMMT ZURÜCK, wörtlich: Hält ein anderer Lauf die Jobs an
 * (der Kreislauf tut es), bleibt seine Pause stehen.
 */

import { neuladenPruefen } from '../neuladen.mjs';
import { php } from '../probekonto.mjs';

const SEITE = k => `${k.basis}/betrieb_jobs.php`;
const lesen = () => php('echo (string)(app_state_lesen("jobs_pause_bis") ?? "");');
const zurueck = w => php(w === ''
  ? 'app_state_loeschen("jobs_pause_bis");'
  : 'app_state_setzen("jobs_pause_bis", ' + JSON.stringify(w) + ');');

export const wege = [
  {
    name: 'betrieb-jobs-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Anhalten und Aufheben leiten je um; die Meldung steht je einmal da, '
        + 'Neuladen ist ein GET ohne sie',
    async fahren(k) {
      const vorher = lesen();
      try {
        php('app_state_loeschen("jobs_pause_bis");');
        const an = await neuladenPruefen(k, {
          name: 'betrieb-jobs-an', seite: SEITE(k),
          /* „Jobs anhalten" fragt zurück (`data-confirm`) — erst „Anhalten"
           * im Dialog schickt ab. */
          async absenden(k) {
            await k.seite.getByRole('button', { name: 'Jobs anhalten', exact: true }).click();
            await k.seite.waitForSelector('dialog[open]', { timeout: 4000 });
            await k.seite.locator('dialog[open] [data-act="yes"]').click();
          },
          meldung: 'Die Hintergrundarbeit ist angehalten bis', ziel: 'betrieb_jobs.php',
        });
        const aus = await neuladenPruefen(k, {
          name: 'betrieb-jobs-aus', seite: SEITE(k),
          feld: 'input[name="action"][value="jobs_pause_aus"]',
          meldung: 'Die Pause ist aufgehoben', ziel: 'betrieb_jobs.php',
        });
        return { ist: 'an: ' + an.ist + ' | aus: ' + aus.ist, ok: an.ok && aus.ok,
                 bemerkung: an.ok && aus.ok ? '' : (an.bemerkung || aus.bemerkung) };
      } finally {
        zurueck(vorher);
      }
    },
  },
];
