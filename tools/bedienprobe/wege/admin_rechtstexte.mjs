/* Wege der Seite Verwaltung → Rechtstexte (`admin_rechtstexte.php`).
 * ===========================================================================
 *
 * Entstanden mit P5c/AP9 (E-P5c-28, Backlog Nr. 121; Mockup M-P5c-01d,
 * Variante 2). Nach der SEITE benannt (E-PK-15).
 *
 *   admin-rechtstexte-vorschau   tippen → nach 0,4 s steht der getippte Text
 *                                in der Vorschau, vom Server gerendert, HTML
 *                                maskiert; die Plakette sagt „ungespeichert"
 *   admin-rechtstexte-rueckfrage Reiterwechsel mit ungespeichertem Text fragt
 *                                nach; „Abbrechen" bleibt, „Verwerfen" wechselt
 *                                und speichert nichts
 *
 * WER DIE SEITE UND DEN ENDPUNKT ERREICHT, misst die Rollenprobe
 * (`tools/proben/rollen/`) gegen die Matrix — hier geht es um das, was nur
 * ein Browser zeigt. Der erste Browserlauf fand genau so etwas: Die Seite
 * trug kein `CSRF`, der Endpunkt antwortete 403, und die Plakette stand auf
 * „nicht aktuell" (F-P5c-154).
 *
 * BEIDE WEGE SPEICHERN NICHTS. Der Text der Nutzungsbedingungen wird vorher
 * und nachher aus der Datenbank gelesen; ein Unterschied ist ein Befund.
 */

import { execFileSync } from 'node:child_process';

const SEITE = k => `${k.basis}/admin_rechtstexte.php?t=nutzungsbedingungen`;
const ZEILE = '\n\n## Bedienprobe-Abschnitt\n\nEin <b>Tag</b> bleibt Text.';

function gespeichert() {
  return execFileSync('php', ['-r',
    'require "server/db.php"; require "server/ui.php"; require "server/rechtstexte_lib.php"; '
    + 'echo json_encode(rt_lesen("nutzungsbedingungen"));'],
    { cwd: process.env.ED_WURZEL || '/home/user/einsatzdoku-luftrettung',
      encoding: 'utf-8' }).trim();
}

/** Ans Ende des Felds tippen — wie ein Mensch, damit `input` feuert. */
async function tippen(k) {
  await k.seite.locator('#rt-text').click();
  await k.seite.keyboard.press('Control+End');
  await k.seite.keyboard.type(ZEILE);
}

const plakette = k => k.seite.evaluate(() =>
  (document.querySelector('[data-rt-zustand]') || {}).textContent?.trim());

export const wege = [
  {
    name: 'admin-rechtstexte-vorschau',
    paket: 'P5c-AP9', punkt: 'E-P5c-28', rolle: 'admin',
    soll: 'Plakette „gespeicherter Stand" → „ungespeichert"; die Vorschau zeigt '
        + 'die neue Überschrift als h2 und „<b>" als Text; keine Fehlermeldung',
    async fahren(k) {
      const vorher = gespeichert();
      await k.gehZu(SEITE(k));
      const anfang = await plakette(k);
      await tippen(k);
      /* 0,4 s Entprellung plus Antwort; gewartet wird auf den ZUSTAND, nicht
       * auf eine feste Zeit — sonst mäße der Weg die Geschwindigkeit der
       * Anlage. */
      await k.seite.waitForFunction(() =>
        /ungespeichert|nicht aktuell/.test(
          document.querySelector('[data-rt-zustand]')?.textContent || ''),
        null, { timeout: 8000 }).catch(() => {});
      const ende = await plakette(k);
      const vorschau = await k.seite.evaluate(() => {
        const v = document.querySelector('[data-rt-vorschau]');
        const f = document.querySelector('[data-rt-fehler]');
        return {
          h2: [...v.querySelectorAll('h2')].some(h => h.textContent === 'Bedienprobe-Abschnitt'),
          maskiert: v.innerHTML.includes('&lt;b&gt;Tag&lt;/b&gt;'),
          echtesB: !!v.querySelector('b'),
          fehler: f && !f.hidden ? f.textContent.replace(/\s+/g, ' ').trim() : '',
        };
      });
      await k.bild('admin-rechtstexte-vorschau');
      const nachher = gespeichert();
      const ok = anfang === 'gespeicherter Stand' && ende === 'ungespeichert'
              && vorschau.h2 && vorschau.maskiert && !vorschau.echtesB
              && vorschau.fehler === '' && vorher === nachher;
      return {
        ist: `Plakette „${anfang}" → „${ende}" · h2 ${vorschau.h2 ? 'da' : 'FEHLT'} · `
           + `<b> ${vorschau.maskiert && !vorschau.echtesB ? 'maskiert' : 'NICHT maskiert'} · `
           + `Meldung „${vorschau.fehler}" · gespeichert ${vorher === nachher ? 'unverändert' : 'GEÄNDERT'}`,
        ok,
        bemerkung: ok ? '' : 'Soll: gespeicherter Stand → ungespeichert · h2 · maskiert · '
                           + 'keine Meldung · nichts gespeichert',
      };
    },
  },
  {
    name: 'admin-rechtstexte-rueckfrage',
    paket: 'P5c-AP9', punkt: 'F-P5c-57', rolle: 'admin',
    soll: 'Reiter „Impressum" mit ungespeichertem Text: Dialog; „Abbrechen" '
        + 'bleibt mit Text, „Verwerfen" wechselt auf t=impressum; nichts gespeichert',
    async fahren(k) {
      const vorher = gespeichert();
      await k.gehZu(SEITE(k));
      await tippen(k);
      const reiter = k.seite.locator('.reiter-punkt', { hasText: 'Impressum' }).first();

      await reiter.click();
      await k.seite.waitForSelector('dialog[open]', { timeout: 4000 }).catch(() => {});
      const ersterDialog = await k.seite.evaluate(() =>
        (document.querySelector('dialog[open] [data-text]') || {}).textContent || '');
      await k.bild('admin-rechtstexte-rueckfrage');
      await k.seite.locator('dialog[open] [data-act="no"]').click().catch(() => {});
      await k.seite.waitForTimeout(300);
      const geblieben = await k.seite.evaluate(() => ({
        adresse: location.search,
        text: (document.querySelector('#rt-text') || {}).value || '',
      }));

      await reiter.click();
      await k.seite.waitForSelector('dialog[open]', { timeout: 4000 }).catch(() => {});
      await Promise.all([
        k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
        k.seite.locator('dialog[open] [data-act="yes"]').click().catch(() => {}),
      ]);
      const danach = await k.seite.evaluate(() => location.search);
      const nachher = gespeichert();

      const ok = ersterDialog.includes('nicht gespeichert')
              && geblieben.adresse.includes('t=nutzungsbedingungen')
              && geblieben.text.includes('Bedienprobe-Abschnitt')
              && danach.includes('t=impressum') && vorher === nachher;
      return {
        ist: `Dialog „${ersterDialog}" · Abbrechen → ${geblieben.adresse}, Text `
           + `${geblieben.text.includes('Bedienprobe-Abschnitt') ? 'steht' : 'WEG'} · `
           + `Verwerfen → ${danach} · gespeichert ${vorher === nachher ? 'unverändert' : 'GEÄNDERT'}`,
        ok,
        bemerkung: ok ? '' : 'Soll: Dialog · bleibt mit Text · t=impressum · nichts gespeichert',
      };
    },
  },
];
