/* Wege der Seite Verwaltung → Konto-Backups (`admin_sicherungen.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Nach einer Handlung leitet die
 * Seite um; Neuladen schickt nichts noch einmal. Gespeichert werden die
 * Regeln, wie sie stehen — „Es gab nichts zu ändern", der Weg ändert nichts.
 */

import { neuladenPruefen } from '../neuladen.mjs';

export const wege = [
  {
    name: 'admin-sicherungen-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Speichern der Regeln leitet um; die Meldung steht einmal da, '
        + 'Neuladen ist ein GET ohne sie',
    fahren: k => neuladenPruefen(k, {
      name: 'admin-sicherungen', seite: `${k.basis}/admin_sicherungen.php`,
      feld: 'input[name="action"][value="regeln"]',
      meldung: 'Es gab nichts zu ändern.', ziel: 'admin_sicherungen.php',
    }),
  },
];
