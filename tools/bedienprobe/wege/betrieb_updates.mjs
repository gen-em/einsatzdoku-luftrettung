/* Wege der Seite Betrieb → Updates (`betrieb_updates.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-11 (Backlog Nr. 250): Der Schalter des Wartungsmodus
 * leitet in seine Karte um; Neuladen schaltet nicht ein zweites Mal und
 * schreibt keinen zweiten Protokolleintrag. Ein und wieder aus in einem Weg;
 * `server/wartung.lock` räumt das `finally` in jedem Fall. Den Lauf der
 * Migrationen misst die Wartungsprobe (Erwartung 27, 31) — ohne Ausstehendes
 * zeigt diese Seite keinen Knopf dafür.
 *
 * STEHT DIE WARTUNG SCHON, fährt der Weg nicht: Er würde eine fremde
 * Wartung beenden.
 */

import { existsSync } from 'node:fs';
import { join } from 'node:path';
import { neuladenPruefen } from '../neuladen.mjs';
import { php, WURZEL } from '../probekonto.mjs';

const SPERRE = join(WURZEL, 'server', 'wartung.lock');
const eintraege = () => Number(php('echo (int)db()->query("SELECT COUNT(*) FROM'
  + ' protokoll_ereignisse WHERE art IN (\'wartung_an\', \'wartung_aus\')")->fetchColumn();'));

export const wege = [
  {
    name: 'betrieb-updates-neuladen',
    paket: 'R4-11', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Ein- und Ausschalten leiten je nach #k-wartungsmodus um; die Meldung '
        + 'steht je einmal da, Neuladen ist ein GET ohne sie und ohne Eintrag',
    async fahren(k) {
      if (existsSync(SPERRE)) {
        return { ist: 'wartung.lock steht schon', ok: false,
                 bemerkung: 'Der Weg beendet keine fremde Wartung — vorher ausschalten' };
      }
      try {
        const an = await neuladenPruefen(k, {
          name: 'betrieb-updates-an', seite: `${k.basis}/betrieb_updates.php`,
          feld: 'input[name="action"][value="wartung_an"]',
          meldung: 'Wartungsmodus eingeschaltet', ziel: 'betrieb_updates.php',
          zaehler: eintraege,
        });
        const aus = await neuladenPruefen(k, {
          name: 'betrieb-updates-aus', seite: `${k.basis}/betrieb_updates.php`,
          feld: 'input[name="action"][value="wartung_aus"]',
          meldung: 'Wartungsmodus ausgeschaltet.', ziel: 'betrieb_updates.php',
          zaehler: eintraege,
        });
        return { ist: 'an: ' + an.ist + ' | aus: ' + aus.ist, ok: an.ok && aus.ok,
                 bemerkung: an.ok && aus.ok ? '' : (an.bemerkung || aus.bemerkung) };
      } finally {
        php('@unlink(' + JSON.stringify(SPERRE) + ');');
      }
    },
  },
];
