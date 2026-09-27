/* Wege der Seite Betrieb → Backup-Ziele (`admin_sicherungsziele.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Nach einer Handlung leitet die
 * Seite um; Neuladen schickt nichts noch einmal — beim Speichern eines Ziels
 * hieße das: samt Passwort. Gespeichert wird der Versandschalter, wie er
 * steht — der Weg ändert nichts und braucht kein erreichbares Ziel.
 */

import { neuladenPruefen } from '../neuladen.mjs';

export const wege = [
  {
    name: 'admin-sicherungsziele-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Speichern des Versandschalters leitet um; die Meldung steht einmal da, '
        + 'Neuladen ist ein GET ohne sie',
    fahren: k => neuladenPruefen(k, {
      name: 'admin-sicherungsziele', seite: `${k.basis}/admin_sicherungsziele.php`,
      feld: 'input[name="action"][value="versand_schalter"]',
      meldung: 'Der Versand ist', ziel: 'admin_sicherungsziele.php',
    }),
  },
];
