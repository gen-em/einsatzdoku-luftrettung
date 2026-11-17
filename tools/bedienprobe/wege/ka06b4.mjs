/* Weg aus Konzept KA, Paket KA-06b-4 — der Anhebelauf.
 * ===========================================================================
 *
 * `ka06b4-anhebelauf`, in dieser Reihenfolge und am Demo-Konto. Seit
 * Web 25.0.0 trägt die ausgelieferte Fixture keinen Altbestand mehr (Format
 * 4, KA-13); der Weg setzt deshalb zuerst mit der letzten Fixture im alten
 * Format zurück (`tools/referenzdatensatz/fixture/alt/fixture-format3.json.gz`, Stand
 * e2e4d8f) — genau der Bestand, den der Lauf auf Produktiv vorfindet:
 *
 *   1. (Bis Web 25.x: Weiche im Browser der Probe aus, nichts zieht um. Die
 *      Weiche ist seit Web 26.0.0 zurückgebaut.)
 *   2. Die Startseite lädt, und still zieht alles um — der Server nennt keinen Altbestand mehr,
 *      Kontopaar und Rückweg tragen `edk2:`, und jedes Blatt des Standbuchs
 *      ist geschlossen.
 *   3. Noch einmal geladen: Es geschieht nichts mehr (die Versionen der
 *      Blätter bleiben).
 *   4. Ein Block mit falschem `blob_alt` wird übersprungen, nicht geschrieben.
 *   5. Die Gerätedaten der Fassung 1 (06e, KA-18): Danach nennt der Server
 *      keine Einsatzhülle und keine Spur der Fassung 1 mehr, und ein
 *      Einsatz mit Ortsdaten zeigt „Vom Browser übernommen am …“
 *      (M-KA-03 D) samt Karte.
 *
 * Danach setzt der Weg das Demo-Konto zurück (`demo_zuruecksetzen()`), damit
 * die übrigen Wege den Bestand vorfinden, den sie kennen — nur örtlich —, und
 * meldet die Rolle neu an.
 *
 * Anlass: Nr. 563.
 */
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const WURZEL = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');

const stand = (k) => k.seite.evaluate(async () =>
  (await fetch('api/format_anheben.php', { credentials: 'same-origin' })).json());
const geraete = (k) => k.seite.evaluate(async () =>
  (await (await fetch('api/format_anheben.php?geraete=1', { credentials: 'same-origin' })).json()).offen);
const blaetter = (k) => k.seite.evaluate(async () => (await EdStandbuch.apiTransport().stand()).blaetter);

export const wege = [
  {
    name: 'ka06b4-anhebelauf',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'demo',
    soll: 'kein Altbestand mehr, kp/rw edk2:, 16 Blätter zu; Gerätedaten der Fassung 1 übernommen; '
        + 'zweiter Lauf ändert nichts; falsches blob_alt wird übersprungen',
    async fahren(k) {
      try {
        execFileSync('php', ['-r', 'require "server/db.php"; require "server/demo_lib.php"; demo_zuruecksetzen($argv[1]);',
                             resolve(WURZEL, 'tools/referenzdatensatz/fixture/alt/fixture-format3.json.gz')],
                     { cwd: WURZEL, stdio: 'ignore' });
        /* Den Altbestand lesen, BEVOR sich jemand anmeldet — ohne Weiche hebt
           schon die erste Seite nach der Anmeldung. */
        const sql = (q) => execFileSync('mariadb', ['-N', 'nadoku', '-e', q], { cwd: WURZEL, encoding: 'utf8' }).trim();
        const DEMO = "(SELECT id FROM users WHERE email = 'demo@gen-em.org')";
        const vorher = { offen: Number(sql(`SELECT COUNT(*) FROM missions WHERE user_id = ${DEMO}
          AND pat_blob IS NOT NULL AND pat_blob <> '' AND pat_blob NOT LIKE 'edk2:%'`)) };
        const altE = { offen: {
          einsaetze: Number(sql(`SELECT COUNT(*) FROM missions WHERE user_id = ${DEMO} AND SUBSTRING(ort_huelle, 1, 1) = X'01'`)),
          spuren: Number(sql(`SELECT COUNT(DISTINCT s.owner_type, s.owner_id) FROM track_stuecke s
            LEFT JOIN missions m ON s.owner_type = 'mission' AND m.id = s.owner_id
            LEFT JOIN rest_segments r ON s.owner_type = 'rest' AND r.id = s.owner_id
            WHERE COALESCE(m.user_id, r.user_id) = ${DEMO} AND SUBSTRING(s.huelle, 1, 1) = X'01'`)) } };
        /* Ein Einsatz, dessen Hülle noch Fassung 1 ist — er muss danach „übernommen“ tragen. */
        const ansicht = Number(sql(`SELECT id FROM missions WHERE user_id = ${DEMO}
          AND SUBSTRING(ort_huelle, 1, 1) = X'01' ORDER BY id LIMIT 1`)) || null;
        await k.neuAnmelden();
        const ausGleich = vorher.offen > 0;
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
        /* 5. Die Gerätedaten: Vorher stand Fassung 1 da (die alte Fixture
           verschließt serverseitig), jetzt nicht mehr. */
        let gd = null;
        for (let i = 0; i < 90; i++) {
          gd = await geraete(k);
          if (gd.einsaetze === 0 && gd.spuren === 0) { break; }
          await k.seite.waitForTimeout(1000);
        }
        await k.gehZu(`${k.basis}/einsatz.php?id=${ansicht}`);
        await k.seite.waitForTimeout(6000);
        const d = await k.seite.evaluate(() => [...document.querySelectorAll('[data-ortsdaten]')]
          .map((x) => x.textContent.replace(/\s+/g, ' ').trim()));
        const mitD = d.some((t) => t.includes('Vom Browser übernommen am'));
        const ohneFehler = !d.some((t) => t.includes('lassen sich nicht öffnen') || t.includes('keinen Herkunftsnachweis'));
        const ok = altE.offen.einsaetze > 0 && gd.einsaetze === 0 && gd.spuren === 0 && mitD && ohneFehler
          && ausGleich && nachher.offen === 0 && !nachher.kp && !nachher.rw && zu === 16
          && ruhig && falsch.ok && falsch.angehoben === 0 && falsch.gleich;
        return { ist: `Altbestand vorher ${vorher.offen}; danach ${nachher.offen}, kp ${nachher.kp ? 'alt' : 'neu'}, rw ${nachher.rw ? 'alt' : 'neu'}, `
                    + `${zu} von 16 Blättern zu; zweiter Lauf ${ruhig ? 'ohne Änderung' : 'ÄNDERT'}; `
                    + `falsches blob_alt: angehoben ${falsch.angehoben}, Block ${falsch.gleich ? 'unverändert' : 'GEÄNDERT'}; `
                    + `Fassung 1 vorher ${altE.offen.einsaetze} Einsätze/${altE.offen.spuren} Spuren, danach: ${gd.einsaetze} Einsätze, ${gd.spuren} Spuren; Einsatz ${ansicht}: `
                    + `${mitD ? '„übernommen“' : 'OHNE Kennzeichen'}${ohneFehler ? '' : ', mit FEHLER'}`,
                 ok };
      } finally {
        execFileSync('php', ['-r', 'require "server/db.php"; require "server/demo_lib.php"; demo_zuruecksetzen();'],
                     { cwd: WURZEL, stdio: 'ignore' });
        /* DER RESET BEENDET DIE SITZUNG (`session_epoch`), aber die Seite
         * merkt es erst an einer Anfrage im Hintergrund und leitet DANN zur
         * Anmeldung um — nach der Wache in `gehZu()`. In Firefox und WebKit
         * stand der nächste Weg deshalb auf `login.php?ende=passwort`
         * (`mr-oeffner-markiert`, F-KA-106). Also hier neu anmelden. */
        await k.neuAnmelden();
      }
    },
  },
];
