/* Wege der Seite Betrieb → Statistik (`betrieb_statistik.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-23 (Backlog Nr. 122 a, Bild M-R4-23): die Zeitraumwahl
 * über den Reitern. Das Mockup war gerendert, nicht bedient (Prüfdokument R4,
 * Abschnitt 1) — dieser Weg bedient es.
 *
 * GEMESSEN WIRD, WAS STILL SCHIEFGEHEN KANN:
 *   - der Knopf „Anwenden" ist mit Skript fort, und der Wechsel von „Von"
 *     nach „Bis" schickt NICHT ab — erst das Verlassen des Paars;
 *   - der eigene Zeitraum kommt an: Karte „Einsätze im Zeitraum", Spalten mit
 *     Wochen- und Tagesschnitt, eine Pille mit Kreuz;
 *   - der Reiter behält ihn, und unter NutzerInnen steht bei einem Zeitraum
 *     in der Vergangenheit „—" statt einer Zahl, die es nicht gibt
 *     (F-R4-66, E-R4-55);
 *   - das Kreuz nimmt ihn weg, und eine verkehrte Eingabe wird mit einer
 *     Meldung abgewiesen, statt still etwas anderes zu zeigen.
 */

const VON = '2026-03-01';
const BIS = '2026-05-31';

export const wege = [
  {
    name: 'statistik-zeitraum',
    paket: 'R4-23', punkt: 'Nr. 122', rolle: 'admin',
    soll: 'Von/Bis ausfüllen, Paar verlassen → eigener Zeitraum mit Schnittspalten und Pille mit '
        + 'Kreuz; der Reiter behält ihn, NutzerInnen zeigt „—"; das Kreuz nimmt ihn weg; '
        + 'Von nach Bis wird abgewiesen',
    async fahren(k) {
      const s = k.seite;
      const teile = [];
      const fehlt = [];
      await k.gehZu(`${k.basis}/betrieb_statistik.php?r=einsaetze`);
      const knopfWeg = await s.locator('.zeitraumwahl [data-absenden-knopf]').isHidden();
      if (!knopfWeg) { fehlt.push('„Anwenden" steht trotz Skript'); }

      /* Von füllen und nach Bis wechseln: kein Abschicken. */
      const vorher = s.url();
      await s.locator('#f-zeitraum-von').fill(VON);
      await s.locator('#f-zeitraum-bis').focus();
      await s.waitForTimeout(400);
      if (s.url() !== vorher) { fehlt.push('der Wechsel von „Von" nach „Bis" hat abgeschickt'); }

      /* Bis füllen und das Paar verlassen: abschicken. */
      await s.locator('#f-zeitraum-bis').fill(BIS);
      await Promise.all([
        s.waitForURL(/von=2026-03-01/, { timeout: 15000 }).catch(() => {}),
        s.locator('.reiter-punkt').first().focus(),
      ]);
      const eigen = await s.evaluate(() => ({
        url: location.search,
        titel: [...document.querySelectorAll('.karte-titel')].map((t) => t.textContent.trim()),
        spalten: [...document.querySelectorAll('#k-einsaetze-zeit thead th')]
          .map((t) => t.textContent.trim()).filter(Boolean),
        /* Das Kreuz trägt sein Etikett als `<title>` im SVG (`ui_symbol()`);
         * der Text der Pille steht im `<span>` davor. */
        pille: (document.querySelector('.zeitraumwahl-felder .listenfilter.aktiv > span') || {})
          .textContent?.trim() || '',
        kreuz: [...document.querySelectorAll('.zeitraumwahl-felder .listenfilter.aktiv svg title')]
          .some((t) => t.textContent.trim() === 'Zeitraum entfernen'),
        kennzahl: [...document.querySelectorAll('.kennzahl')].map((t) => t.textContent.replace(/\s+/g, ' ').trim()).pop(),
      }));
      teile.push(`eigen: ${eigen.url}, Spalten ${eigen.spalten.join(' / ')}, Pille „${eigen.pille}"`);
      if (!/von=2026-03-01/.test(eigen.url) || !/bis=2026-05-31/.test(eigen.url)) {
        fehlt.push('das Verlassen des Paars hat nicht abgeschickt');
      }
      if (!eigen.titel.includes('Einsätze im Zeitraum')) { fehlt.push('Karte „Einsätze im Zeitraum" fehlt'); }
      if (eigen.spalten.join('|') !== 'im Zeitraum|Ø je Woche|Ø je Tag') {
        fehlt.push(`Spalten ${eigen.spalten.join(' / ') || '—'}`);
      }
      if (eigen.pille !== '01.03. – 31.05.2026' || !eigen.kreuz) {
        fehlt.push(`Pille „${eigen.pille}"${eigen.kreuz ? '' : ' ohne Kreuz'}`);
      }
      if (!/Einsätze im Zeitraum/.test(eigen.kennzahl || '')) { fehlt.push('Kennzahl nicht „im Zeitraum"'); }
      await k.bild('statistik-zeitraum');

      /* Der Reiter behält den Zeitraum; „aktiv" steht als „—". */
      await Promise.all([
        s.waitForURL(/r=nutzer/, { timeout: 15000 }).catch(() => {}),
        s.locator('.reiter-punkt', { hasText: 'NutzerInnen' }).click(),
      ]);
      const nutzer = await s.evaluate(() => {
        const zeile = [...document.querySelectorAll('#k-konten-zeit tbody tr')]
          .find((z) => z.querySelector('th')?.textContent.trim().startsWith('Aktiv'));
        return { url: location.search, aktiv: zeile?.querySelector('td')?.textContent.trim() ?? '',
                 satz: /nur zählbar, wenn der Zeitraum bis heute reicht/.test(document.body.textContent) };
      });
      teile.push(`NutzerInnen: Aktiv „${nutzer.aktiv}"`);
      if (!/von=2026-03-01/.test(nutzer.url)) { fehlt.push('der Reiter hat den Zeitraum verloren'); }
      if (nutzer.aktiv !== '—' || !nutzer.satz) { fehlt.push(`Aktiv „${nutzer.aktiv}"${nutzer.satz ? '' : ', Satz fehlt'}`); }

      /* Das Kreuz nimmt ihn weg. */
      await Promise.all([
        s.waitForURL((u) => !/von=/.test(u.search), { timeout: 15000 }).catch(() => {}),
        s.locator('.zeitraumwahl-felder .listenfilter.aktiv').click(),
      ]);
      const zurueck = await s.evaluate(() => ({
        url: location.search,
        aktiv: (document.querySelector('.zeitraumwahl .filterreihe .listenfilter.aktiv') || {}).textContent?.trim() || '',
      }));
      teile.push(`Kreuz: ${zurueck.url}, aktiv „${zurueck.aktiv}"`);
      if (/von=/.test(zurueck.url) || zurueck.aktiv !== '30 Tage') { fehlt.push('das Kreuz nimmt den Zeitraum nicht weg'); }

      /* Von nach Bis: abgewiesen, mit Meldung. */
      await k.gehZu(`${k.basis}/betrieb_statistik.php?r=einsaetze&von=${BIS}&bis=${VON}`);
      const meldung = await s.locator('.meldung', { hasText: 'Von liegt nach Bis.' }).count();
      teile.push(`verkehrt: ${meldung} Meldung`);
      if (meldung !== 1) { fehlt.push('keine Meldung bei Von nach Bis'); }

      return { ist: teile.join('; '), ok: fehlt.length === 0, bemerkung: fehlt.join('; ') };
    },
  },
];
