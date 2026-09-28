/* Wege der Seite Verwaltung → Installation (`admin_installation.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Nach dem Speichern leitet die Seite
 * in die Karte um, die gehandelt hat; Neuladen schickt nichts noch einmal.
 * Gespeichert wird der Logo-Standard, der schon gilt — der Weg ändert nichts.
 */

import { neuladenPruefen } from '../neuladen.mjs';

export const wege = [
  {
    name: 'admin-installation-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Speichern des Logo-Standards leitet nach #k-logo um; die Meldung steht '
        + 'einmal da, Neuladen ist ein GET ohne sie',
    fahren: k => neuladenPruefen(k, {
      name: 'admin-installation', seite: `${k.basis}/admin_installation.php`,
      feld: 'input[name="action"][value="logo_standard"]',
      meldung: 'Standard der Installation:', ziel: 'admin_installation.php',
    }),
  },
];
