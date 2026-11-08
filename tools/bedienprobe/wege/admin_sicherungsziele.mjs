/* Wege der Seite Betrieb → Backup-Ziele (`admin_sicherungsziele.php`).
 * ===========================================================================
 *
 * Nach einer Handlung leitet die
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
      /* SEIT WEB 24.7 eine Karte in den Servereinstellungen (Konzept OF,
         E-OF-24, -45); admin_sicherungsziele.php nimmt nur noch POSTs an
         und leitet zurueck. */
      name: 'admin-sicherungsziele', seite: `${k.basis}/betrieb_server.php`,
      feld: 'input[name="action"][value="versand_schalter"]',
      meldung: 'Der Versand ist', ziel: 'betrieb_server.php#k-backup-ziele',
    }),
  },
];
