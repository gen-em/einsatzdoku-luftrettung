/* Wege der Seite Verwaltung → NutzerInnen (`admin_users.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): „Auswahl sichern" leitet nach dem
 * Sichern auf dieselbe Ansicht um, Zahl und Gründe kommen über die Sitzung
 * an (E-R4-33), und Neuladen sichert nicht noch einmal. Ausgewählt wird ein
 * Konto, das es nicht gibt (die Auswahl steht im sessionStorage, wie nach
 * einem Klick auf ein Kästchen) — die Seite meldet „0 Konto-Backups
 * erzeugt" und legt nichts an.
 */

import { neuladenPruefen } from '../neuladen.mjs';

export const wege = [
  {
    name: 'admin-users-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: '„Auswahl sichern" leitet um; die Meldung steht einmal da, Neuladen '
        + 'ist ein GET ohne sie',
    fahren: k => neuladenPruefen(k, {
      name: 'admin-users', seite: `${k.basis}/admin_users.php`,
      async vorher(k) {
        await k.seite.evaluate(() => sessionStorage.setItem(
          'ed-konten-auswahl-' + document.body.dataset.konto, '999999999'));
        await k.seite.reload({ waitUntil: 'domcontentloaded' });
      },
      feld: 'input[name="action"][value="sichern_auswahl"]',
      meldung: '0 Konto-Backups erzeugt.', ziel: 'admin_users.php',
    }),
  },
];
