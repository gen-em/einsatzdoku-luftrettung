/* Weg aus Konzept KA, Paket KA-06b-4 — der Anhebelauf.
 * ===========================================================================
 *
 * `ka06b4-anhebelauf`, in dieser Reihenfolge und am Demo-Konto, dessen
 * Bestand aus der Fixture im alten Format liegt (`edk1:`):
 *
 *   1. Weiche wie ausgeliefert: Die Startseite lädt, und nichts zieht um —
 *      der Server nennt danach denselben Altbestand wie davor.
 *   2. Weiche nur im Browser der Probe umgelegt (wie `ka06b3.mjs`): Die
 *      Startseite lädt, und still zieht alles um — der Server nennt keinen
 *      Altbestand mehr, Kontopaar und Rückweg tragen `edk2:`, und jedes
 *      Blatt des Standbuchs ist geschlossen.
 *   3. Noch einmal geladen: Es geschieht nichts mehr (die Versionen der
 *      Blätter bleiben).
 *   4. Ein Block mit falschem `blob_alt` wird übersprungen, nicht geschrieben.
 *
 * Danach setzt der Weg das Demo-Konto zurück (`demo_zuruecksetzen()`), damit
 * die übrigen Wege den Bestand vorfinden, den sie kennen — nur örtlich.
 *
 * Anlass: Nr. 563.
 */
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const WURZEL = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
const WEICHE = /const SCHREIBT_FASSUNG_2 = false;/;

async function weicheAn(seite) {
  await seite.route(/assets\/inhalt2\.js/, async (route) => {
    const antwort = await route.fetch();
    const kopf = Object.assign({}, antwort.headers(), { 'cache-control': 'no-store' });
    delete kopf['content-length']; delete kopf['content-encoding'];
    await route.fulfill({ status: antwort.status(), headers: kopf,
      body: (await antwort.text()).replace(WEICHE, 'const SCHREIBT_FASSUNG_2 = true;') });
  });
}

const stand = (k) => k.seite.evaluate(async () =>
  (await fetch('api/format_anheben.php', { credentials: 'same-origin' })).json());
const blaetter = (k) => k.seite.evaluate(async () => (await EdStandbuch.apiTransport().stand()).blaetter);

export const wege = [
  {
    name: 'ka06b4-anhebelauf',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'demo',
    soll: 'Weiche aus: nichts zieht um; Weiche an: kein Altbestand mehr, kp/rw edk2:, 16 Blätter zu; '
        + 'zweiter Lauf ändert nichts; falsches blob_alt wird übersprungen',
    async fahren(k) {
      try {
        await k.gehZu(`${k.basis}/index.php`);
        const vorher = await stand(k);
        await k.seite.waitForTimeout(3000);
        await k.gehZu(`${k.basis}/index.php`);
        await k.seite.waitForTimeout(3000);
        const ausGleich = (await stand(k)).offen === vorher.offen && vorher.offen > 0;

        await weicheAn(k.seite);
        await k.gehZu(`${k.basis}/index.php`);
        let nachher = null;
        for (let i = 0; i < 60; i++) {           // still im Hintergrund: abwarten
          await k.seite.waitForTimeout(1000);
          nachher = await stand(k);
          if (nachher.offen === 0 && !nachher.kp && !nachher.rw) { break; }
        }
        await k.seite.waitForTimeout(3000);       // das Schließen der Blätter
        const zu = await k.seite.evaluate(async () => {
          const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
          const b = await EdStandbuch.seitenbuch(ck);
          let n = 0;
          for (let nr = 0; nr < 16; nr++) { if (await b.blattZuNr(nr)) { n++; } }
          return n;
        });
        const v1 = await blaetter(k);
        await k.gehZu(`${k.basis}/index.php`);
        await k.seite.waitForTimeout(4000);
        const v2 = await blaetter(k);
        const ruhig = JSON.stringify(v1) === JSON.stringify(v2);
        /* Falsches blob_alt: ein Block in Form, aber an der falschen Stelle. */
        const falsch = await k.seite.evaluate(async () => {
          const j = await (await fetch('api/suchindex.php')).json();
          const m = (j.missions || []).find((x) => x.pat_blob);
          const b = crypto.getRandomValues(new Uint8Array(12 + 512 + 16));
          const text = 'edk2:' + btoa(String.fromCharCode(...b));
          const a = await EdApi.postJson('api/format_anheben.php',
            { missions: [{ id: m.id, pat_blob: text, blob_alt: 'edk1:gibtesnicht' }] });
          const danach = (await (await fetch('api/mission.php?id=' + m.id)).json()).pat_blob;
          return { ok: a.ok, angehoben: a.daten && a.daten.angehoben, gleich: danach === m.pat_blob };
        });
        const ok = ausGleich && nachher.offen === 0 && !nachher.kp && !nachher.rw && zu === 16
          && ruhig && falsch.ok && falsch.angehoben === 0 && falsch.gleich;
        return { ist: `Weiche aus: Altbestand ${vorher.offen} → ${ausGleich ? 'gleich' : 'GEÄNDERT'}; `
                    + `Weiche an: Altbestand ${nachher.offen}, kp ${nachher.kp ? 'alt' : 'neu'}, rw ${nachher.rw ? 'alt' : 'neu'}, `
                    + `${zu} von 16 Blättern zu; zweiter Lauf ${ruhig ? 'ohne Änderung' : 'ÄNDERT'}; `
                    + `falsches blob_alt: angehoben ${falsch.angehoben}, Block ${falsch.gleich ? 'unverändert' : 'GEÄNDERT'}`,
                 ok };
      } finally {
        await k.seite.unroute(/assets\/inhalt2\.js/).catch(() => {});
        execFileSync('php', ['-r', 'require "server/db.php"; require "server/demo_lib.php"; demo_zuruecksetzen();'],
                     { cwd: WURZEL, stdio: 'ignore' });
      }
    },
  },
];
