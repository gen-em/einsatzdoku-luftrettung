/* Weg zu F-KA-129 — ein Diensttag wird umdatiert, die Ortsdaten gehen danach auf.
 * ===========================================================================
 *
 * Konzept KA, 06e (KA-20, E-KA-101). Die Hüllen der Fassung 2 tragen ihre
 * Zeit absolut; nach dem Umdatieren gehen sie nur auf, wenn der Browser den
 * Versatz im Standbuch belegt hat (`assets/umdatieren.js`). Am Demo-Konto
 * (Fixture Format 4, alles Fassung 2):
 *
 *   `umdatieren-versatz`  Diensttag eines Einsatzes mit Ortsdaten einen Tag
 *        vor, über die Seite samt Rückfrage → Startseite → der Einsatz öffnet
 *        ohne Fehlermeldung, mit Spur; danach zurück und wieder ohne Fehler.
 *   `umdatieren-ohne-versatz`  Gegenprobe: dasselbe mit gesperrtem
 *        `umdatieren.js` → der Einsatz meldet „lassen sich nicht öffnen“.
 *
 * Danach setzt jeder Weg das Demo-Konto zurück (nur örtlich).
 *
 * Anlass: Nr. 590.
 */
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const WURZEL = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
const reset = () => execFileSync('php', ['-r', 'require "server/db.php"; require "server/demo_lib.php"; demo_zuruecksetzen();'],
                                 { cwd: WURZEL, stdio: 'ignore' });

/* Ein Einsatz mit Hülle und Spur und sein Diensttag. */
const waehlen = (k) => k.seite.evaluate(async () => {
  const j = await (await fetch('api/suchindex.php')).json();
  for (const m of j.missions || []) {
    const d = await (await fetch('api/mission.php?id=' + m.id)).json();
    if (d.ort_huelle && (d.spur_stuecke || []).length) { return { mid: m.id, day: d.day_id, tag: m.dienst_day }; }
  }
  return null;
});

async function umdatieren(k, w, nachTag) {
  await k.gehZu(`${k.basis}/diensttag_datum.php?d=${w.day}`);
  const tt = nachTag.slice(8, 10) + '.' + nachTag.slice(5, 7) + '.' + nachTag.slice(0, 4);
  await k.seite.fill('#zielfeld', tt);
  await k.seite.click('#datumform button[type="submit"]');
  await Promise.all([k.seite.waitForURL(/index\.php/, { timeout: 90000 }),
                     k.seite.locator('dialog[open] [data-act="yes"]').click()]);
  await k.seite.waitForTimeout(5000);           // Versatz eintragen, neu laden
}

async function ansicht(k, w) {
  await k.gehZu(`${k.basis}/einsatz.php?id=${w.mid}`);
  await k.seite.waitForTimeout(6000);
  return k.seite.evaluate(() => {
    const t = [...document.querySelectorAll('[data-ortsdaten]')].map((x) => x.textContent.replace(/\s+/g, ' ').trim());
    return { fehler: t.some((x) => x.includes('nicht öffnen')), spur: document.querySelectorAll('.leaflet-overlay-pane path').length };
  });
}

const tagPlus = (tag, n) => new Date(Date.parse(tag + 'T12:00:00Z') + n * 86400000).toISOString().slice(0, 10);

export const wege = [
  {
    name: 'umdatieren-versatz',
    paket: 'KA-20', punkt: 'F-KA-129', rolle: 'demo',
    soll: 'nach dem Umdatieren (vor und zurück) öffnet der Einsatz ohne Fehler, mit Spur',
    async fahren(k) {
      try {
        reset(); await k.neuAnmelden();
        await k.gehZu(`${k.basis}/index.php`);
        const w = await waehlen(k);
        if (!w) { return { ist: 'kein Einsatz mit Hülle und Spur im Demo-Bestand', ok: false }; }
        const vorher = await ansicht(k, w);
        await umdatieren(k, w, tagPlus(w.tag, 1));
        const vor = await ansicht(k, w);
        await umdatieren(k, w, w.tag);
        const zurueck = await ansicht(k, w);
        const ok = !vorher.fehler && !vor.fehler && !zurueck.fehler && vor.spur > 0 && zurueck.spur > 0;
        return { ist: `Einsatz ${w.mid}: vorher ${vorher.fehler ? 'FEHLER' : 'gut'}; einen Tag vor ${vor.fehler ? 'FEHLER' : 'gut'} `
                    + `(Spur ${vor.spur}); zurück ${zurueck.fehler ? 'FEHLER' : 'gut'} (Spur ${zurueck.spur})`, ok };
      } finally { reset(); await k.neuAnmelden(); }
    },
  },
  {
    name: 'umdatieren-ohne-versatz',
    paket: 'KA-20', punkt: 'F-KA-129', rolle: 'demo',
    soll: 'Gegenprobe: ohne umdatieren.js meldet der umdatierte Einsatz „lassen sich nicht öffnen“',
    async fahren(k) {
      try {
        reset(); await k.neuAnmelden();
        await k.seite.route(/assets\/umdatieren\.js/, (r) => r.fulfill({ status: 200, contentType: 'text/javascript', body: '' }));
        await k.gehZu(`${k.basis}/index.php`);
        const w = await waehlen(k);
        if (!w) { return { ist: 'kein Einsatz mit Hülle und Spur im Demo-Bestand', ok: false }; }
        await umdatieren(k, w, tagPlus(w.tag, 1));
        const vor = await ansicht(k, w);
        return { ist: `Einsatz ${w.mid} einen Tag vor, ohne Versatz: ${vor.fehler ? 'Fehlermeldung (erwartet)' : 'OHNE Fehler'}`,
                 ok: vor.fehler };
      } finally {
        await k.seite.unroute(/assets\/umdatieren\.js/).catch(() => {});
        reset(); await k.neuAnmelden();
      }
    },
  },
];
