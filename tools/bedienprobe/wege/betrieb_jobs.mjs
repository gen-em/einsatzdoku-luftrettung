/* Wege der Seite Betrieb → Hintergrundjobs (`betrieb_jobs.php`).
 * ===========================================================================
 *
 * Pausieren und Aufheben leiten in die
 * Karte „Zustand" um; Neuladen setzt die Pause nicht ein zweites Mal. Der
 * Pausenhinweis der Karte sagt dasselbe wie die Meldung („… pausiert bis", bis Web 24.6 „angehalten"),
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
    soll: 'Pausieren und Aufheben leiten je um; die Meldung steht je einmal da, '
        + 'Neuladen ist ein GET ohne sie',
    async fahren(k) {
      const vorher = lesen();
      try {
        php('app_state_loeschen("jobs_pause_bis");');
        const an = await neuladenPruefen(k, {
          name: 'betrieb-jobs-an', seite: SEITE(k),
          /* „Pausieren" fragt zurück (`data-confirm`) — erst „Pausieren"
           * im Dialog schickt ab (bis Web 24.6 „Jobs anhalten", E-OF-10). */
          async absenden(k) {
            await k.seite.getByRole('button', { name: 'Pausieren', exact: true }).click();
            await k.seite.waitForSelector('dialog[open]', { timeout: 4000 });
            await k.seite.locator('dialog[open] [data-act="yes"]').click();
          },
          meldung: 'Die Hintergrundarbeit ist pausiert bis', ziel: 'betrieb_jobs.php',
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
