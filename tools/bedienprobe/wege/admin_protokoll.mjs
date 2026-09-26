/* Wege der Seite Verwaltung → Protokoll (`admin_protokoll.php`).
 * ===========================================================================
 *
 * Entstanden mit P5c/AP2 (E-P5c-10, -25, -26; Bild M-P5c-01a). Nach der
 * SEITE benannt (E-PK-15).
 *
 *   admin-protokoll-reiter      Reiter wechseln; die Leiste trägt keine
 *                               Unterpunkte; eine Fehlerkennung im Suchfeld
 *                               landet auf „System"
 *   admin-protokoll-zeilen      eine Zeile aufklappen; aufklappbare und feste
 *                               Zeilen sind gleich hoch (F-P5c-13); das
 *                               Auswahlfeld „Art" schickt ohne Knopf ab
 *
 * WER DIE SEITE SIEHT, UND WER NICHT, misst die Rollenprobe
 * (`tools/proben/rollen/`) gegen die Matrix — hier geht es um das, was nur
 * ein Browser zeigt.
 *
 * DIE ZWEI MESSZEILEN legt der Weg selbst an — eine mit `daten`, eine ohne,
 * mit gleich kurzem Text — und räumt sie im `finally` ab. Die Höhe hängt am
 * Text; zwei Zeilen verschiedener Länge zu vergleichen maße den Umbruch,
 * nicht die Regel.
 */

import { execFileSync } from 'node:child_process';

const SEITE = k => `${k.basis}/admin_protokoll.php`;
const MARKE = 'Bedienprobe-Messzeile';

function php(code) {
  return execFileSync('php', ['-r',
    'require "server/db.php"; require "server/protokoll_lib.php"; ' + code],
    { cwd: process.env.ED_WURZEL || '/home/user/einsatzdoku-luftrettung',
      encoding: 'utf-8' }).trim();
}
const aufraeumen = () => php('db()->prepare("DELETE FROM protokoll_ereignisse WHERE text LIKE ?")'
  + '->execute(["' + MARKE + '%"]);');

export const wege = [
  {
    name: 'admin-protokoll-reiter',
    paket: 'P5c-AP2', punkt: 'E-P5c-25', rolle: 'admin',
    soll: 'Reiter „Jobs" aktiv und in der Adresse; 0 Unterpunkte in der Leiste; '
        + 'eine Fehlerkennung im Suchfeld führt auf „System"',
    async fahren(k) {
      await k.gehZu(SEITE(k));
      await Promise.all([
        k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        k.seite.locator('.reiter-punkt', { hasText: 'Jobs' }).first().click(),
      ]);
      const jobs = await k.seite.evaluate(() => ({
        aktiv: (document.querySelector('.reiter-punkt.aktiv') || {}).textContent,
        adresse: location.search,
        unter: document.querySelectorAll('.eintrag-unter').length,
        menue: (document.querySelector('.leiste-liste a.eintrag.aktiv') || {}).textContent?.trim(),
      }));
      await k.bild('admin-protokoll-jobs');

      await k.seite.fill('#q', '0badc0de');
      await Promise.all([
        k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }),
        k.seite.press('#q', 'Enter'),
      ]);
      const system = await k.seite.evaluate(() => ({
        aktiv: (document.querySelector('.reiter-punkt.aktiv') || {}).textContent,
        q: (document.querySelector('#q') || {}).value,
      }));
      const ok = jobs.aktiv === 'Jobs' && jobs.adresse.includes('r=jobs') && jobs.unter === 0
              && jobs.menue === 'Protokoll' && system.aktiv === 'System' && system.q === '0badc0de';
      return {
        ist: `aktiv „${jobs.aktiv}" · ${jobs.adresse} · ${jobs.unter} Unterpunkte · `
           + `Leiste „${jobs.menue}" · Kennung → „${system.aktiv}"`,
        ok, bemerkung: ok ? '' : 'Soll: Jobs · r=jobs · 0 · Protokoll · System',
      };
    },
  },
  {
    name: 'admin-protokoll-zeilen',
    paket: 'P5c-AP2', punkt: 'F-P5c-13', rolle: 'admin',
    soll: 'Aufgeklappt stehen die Angaben da; beide Messzeilen gleich hoch; '
        + 'die Art-Auswahl schickt ab, der Knopf „Filtern" ist verborgen',
    async fahren(k) {
      aufraeumen();
      /* Eine mit, eine ohne `daten` — als Urheber „cli", damit keine
       * Kontoadresse die Zeile länger macht als die andere. */
      /* VIER ZEILEN, GEMESSEN WERDEN DIE MITTLEREN: Die erste Zeile einer
       * Liste hat oben keinen Innenabstand, die letzte unten keinen und keine
       * Trennlinie. Neueste zuerst: Rand oben, B (fest), A (mit Angaben),
       * Rand unten. */
      php('protokoll("verwaltung", "wartung_aus", "' + MARKE + ' Rand unten");');
      php('protokoll("verwaltung", "wartung_an", "' + MARKE + ' A", ["weg" => "probe"]);');
      php('protokoll("verwaltung", "wartung_aus", "' + MARKE + ' B");');
      php('protokoll("verwaltung", "wartung_aus", "' + MARKE + ' Rand oben");');
      try {
        await k.gehZu(SEITE(k) + '?q=' + encodeURIComponent(MARKE));
        const hoehen = await k.seite.evaluate((marke) => {
          const h = {};
          /* Die ganze Zeile: bei der aufklappbaren das `<details>` (zu),
           * dessen Trennlinie an die Stelle der Zeilenlinie tritt. */
          document.querySelectorAll('.protokoll-liste .zeilen > .zeile, '
            + '.protokoll-liste .zeilen > details.zeile-mehr').forEach(z => {
            const t = (z.querySelector('.zeile-haupt') || {}).textContent || '';
            if (t === marke + ' A') { h.mehr = Math.round(z.getBoundingClientRect().height * 10) / 10; }
            if (t === marke + ' B') { h.fest = Math.round(z.getBoundingClientRect().height * 10) / 10; }
          });
          return h;
        }, MARKE);
        const zeile = k.seite.locator('details.zeile-mehr > summary', { hasText: MARKE + ' A' });
        await zeile.click();
        const offen = await k.seite.evaluate(() => {
          const d = document.querySelector('details.zeile-mehr[open] .zeile-daten');
          return d ? d.textContent.replace(/\s+/g, ' ').trim() : null;
        });
        await k.bild('admin-protokoll-aufgeklappt');

        const knopfVerborgen = await k.seite.evaluate(() =>
          !!document.querySelector('[data-absenden-knopf]')?.hidden);
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          k.seite.selectOption('select[data-absenden]', 'wartung_an'),
        ]);
        const nachArt = await k.seite.evaluate(() => ({
          adresse: location.search,
          arten: [...document.querySelectorAll('.protokoll-liste .zeile-plaketten .plakette')]
            .map(p => p.textContent.trim()),
        }));

        const gleich = hoehen.mehr !== undefined && hoehen.mehr === hoehen.fest;
        const ok = gleich && !!offen && offen.includes('weg') && knopfVerborgen
                && nachArt.adresse.includes('art=wartung_an')
                && nachArt.arten.length > 0 && nachArt.arten.every(a => a === 'Wartung an');
        return {
          ist: `Höhe mit Angaben ${hoehen.mehr} px, fest ${hoehen.fest} px · `
             + `aufgeklappt „${offen}" · Knopf ${knopfVerborgen ? 'verborgen' : 'SICHTBAR'} · `
             + `Art: ${nachArt.arten.length} Zeilen „${[...new Set(nachArt.arten)].join(', ')}"`,
          ok,
          bemerkung: ok ? '' : 'Soll: gleiche Höhe · Angaben sichtbar · Knopf verborgen · nur „Wartung an"',
        };
      } finally {
        aufraeumen();
      }
    },
  },
];
