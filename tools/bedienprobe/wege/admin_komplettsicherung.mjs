/* Wege der Seite Betrieb → Komplett-Backup (`admin_komplettsicherung.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Nach einer Handlung leitet die
 * Seite um; Neuladen stößt keinen zweiten Durchgang an. Gespeichert werden
 * Plan und Aufbewahrung, wie sie stehen — der Weg ändert nichts.
 */

import { neuladenPruefen } from '../neuladen.mjs';

export const wege = [
  {
    name: 'admin-komplettsicherung-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Speichern der Regeln leitet um; die Meldung steht einmal da, '
        + 'Neuladen ist ein GET ohne sie',
    fahren: k => neuladenPruefen(k, {
      name: 'admin-komplettsicherung', seite: `${k.basis}/admin_komplettsicherung.php`,
      feld: 'input[name="action"][value="regeln"]',
      meldung: 'Die Regeln wurden gespeichert.', ziel: 'admin_komplettsicherung.php',
    }),
  },
];
