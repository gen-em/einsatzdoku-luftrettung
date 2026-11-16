/* Weg zu Backlog Nr. 568 — ein Gerätebuch, das nicht aufgeht.
 * ===========================================================================
 *
 * E-KA-92, Mockup M-KA-04. Liefert der Server ein Gerätebuch, das der
 * Browser nicht öffnen kann (beschädigt oder ersetzt), repariert der
 * Browser es selbst — er übernimmt dabei NICHTS vom Server:
 *
 *   - `geraetebuch-unlesbar-neu`: Dieser Browser hat keine eigene Fassung.
 *     Er legt ein neues, leeres Buch mit einer Version über der des Servers
 *     an; danach geht das Buch des Servers auf, und die Startseite sagt
 *     „Geräte neu verbinden“ (Merker dieses Browsers).
 *   - `geraetebuch-unlesbar-zurueck`: Dieser Browser hat eine eigene Fassung.
 *     Er legt sie still zurück; kein Hinweis.
 *
 * Das unlesbare Buch ist ein Zufallstext in der Form eines Blatts — genau
 * das, was `ka06b2-standbuch` im Prüfkonto liegen ließ und F-KA-125 auslöste.
 * Nur örtlich (SQL zum Aufräumen).
 *
 * Anlass: Nr. 568.
 */
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const WURZEL = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
const PRUEFKONTO = "(SELECT id FROM users WHERE email = 'admin@example.invalid')";
const buchLeeren = () => execFileSync('mariadb', ['nadoku', '-e',
  `DELETE FROM stand_blaetter WHERE user_id = ${PRUEFKONTO};`], { cwd: WURZEL, stdio: 'ignore' });

/* Ein gekoppeltes Gerät mit Schlüssel und Herkunft — ohne eines gibt es
 * nichts neu zu verbinden, und die Geräteseite quittiert den Merker sofort. */
const PROBEGERAET = 'probe-nr568-geraet';
const geraetAnlegen = () => execFileSync('mariadb', ['nadoku', '-e',
  `INSERT INTO devices (user_id, device_id, api_key_hash, label, active, geraet_art, spk, herkunft)
   SELECT id, '${PROBEGERAET}', 'x', 'Probegerät Nr. 568', 1, 'handy',
          REPLACE(TO_BASE64(CONCAT(0x04, RANDOM_BYTES(64))), CHAR(10), ''), 1 FROM users WHERE email = 'admin@example.invalid';`],
  { cwd: WURZEL, stdio: 'ignore' });
const geraetLoeschen = () => execFileSync('mariadb', ['nadoku', '-e',
  `DELETE FROM devices WHERE device_id = '${PROBEGERAET}';`], { cwd: WURZEL, stdio: 'ignore' });

async function ablageLeeren(k) {
  await k.seite.evaluate(() => new Promise((ok) => {
    try { for (const s of Object.keys(localStorage)) { if (s.startsWith('nadoku-geraete-neu-verbinden:')) { localStorage.removeItem(s); } } } catch (e) { /* egal */ }
    const r = indexedDB.deleteDatabase('nadoku-stand');
    r.onsuccess = r.onerror = r.onblocked = () => ok();
  }));
}

/* Ein Zufallstext als Gerätebuch, eine Version über dem Stand des Servers. */
const unlesbarAblegen = (k) => k.seite.evaluate(async () => {
  const t = EdStandbuch.apiTransport();
  const s = await t.stand();
  const b = crypto.getRandomValues(new Uint8Array(156));
  const a = await t.ablegen('buch', { version_alt: s.buch, version: s.buch + 1,
                                      text: 'edk2:' + btoa(String.fromCharCode(...b)) });
  return { ok: a.ok, version: s.buch + 1 };
});

/* Nach dem Laden der Geräteseite: Version des Servers, geht sein Buch auf? */
const lage = (k) => k.seite.evaluate(async () => {
  const t = EdStandbuch.apiTransport();
  const s = await t.stand();
  const srv = await t.holen('buch');
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  let auf = false;
  try { await EdStandbuch.geraetebuchOeffnen(ck, srv.text, null); auf = true; } catch (e) { auf = false; }
  return { version: s.buch, auf: auf, merker: await EdStandbuch.neuVerbinden(ck) };
});

async function geraeteseite(k) {
  await k.gehZu(`${k.basis}/einstellungen.php?t=geraete`);
  await k.seite.waitForTimeout(3000);              // der Abgleich läuft still im Hintergrund
}

export const wege = [
  {
    name: 'geraetebuch-unlesbar-neu',
    paket: 'KA-14', punkt: 'Nr. 568', rolle: 'admin',
    soll: 'ohne eigene Fassung: neues Buch über der Version des Servers, geht danach auf; '
        + 'Startseite zeigt „Geräte neu verbinden“',
    async fahren(k) {
      buchLeeren();
      geraetLoeschen();
      await k.gehZu(`${k.basis}/index.php`);
      await ablageLeeren(k);
      try {
        geraetAnlegen();
        await k.gehZu(`${k.basis}/index.php`);
        const gelegt = await unlesbarAblegen(k);
        await ablageLeeren(k);                       // dieser Browser hat keine eigene Fassung
        await geraeteseite(k);
        const l = await lage(k);
        const seite = await k.seite.evaluate((id) => {
          const p = document.getElementById('geraete-neu-verbinden');
          const z = document.querySelector(`[data-geraet="${id}"] .zeile-klein`);
          return { hinweis: !!p && !p.hidden && p.textContent.includes('Geräte neu verbinden'),
                   zeile: z ? z.textContent.trim() : null };
        }, PROBEGERAET);
        await k.gehZu(`${k.basis}/index.php`);
        await k.seite.waitForTimeout(1500);
        const banner = await k.seite.evaluate(() => {
          const p = document.getElementById('geraete-neu-verbinden');
          return p && !p.hidden ? p.textContent.replace(/\s+/g, ' ').trim() : null;
        });
        const ok = gelegt.ok && l.version > gelegt.version && l.auf && l.merker
          && seite.hinweis && !!seite.zeile && seite.zeile.startsWith('neu verbinden')
          && !!banner && banner.includes('Geräte neu verbinden');
        return { ist: `unlesbar abgelegt (v${gelegt.version}); danach v${l.version}, geht ${l.auf ? 'auf' : 'NICHT auf'}, `
                    + `Merker ${l.merker ? 'gesetzt' : 'FEHLT'}, Geräteseite ${seite.hinweis ? 'mit' : 'OHNE'} Hinweis, `
                    + `Zeile „${seite.zeile}“, Startseite ${banner ? '„' + banner.slice(0, 40) + '…“' : 'OHNE Hinweis'}`,
                 ok };
      } finally {
        geraetLoeschen();
        buchLeeren();
        await ablageLeeren(k).catch(() => {});
      }
    },
  },
  {
    name: 'geraetebuch-unlesbar-zurueck',
    paket: 'KA-14', punkt: 'Nr. 568', rolle: 'admin',
    soll: 'mit eigener Fassung: still zurückgeschrieben, geht danach auf, kein Merker',
    async fahren(k) {
      buchLeeren();
      await k.gehZu(`${k.basis}/index.php`);
      await ablageLeeren(k);
      try {
        /* Eine eigene Fassung entsteht: Der Browser legt ein (leeres) Buch an
         * und hält es in seiner Ablage. */
        await k.gehZu(`${k.basis}/index.php`);
        const eigen = await k.seite.evaluate(async () => {
          const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
          const buch = await EdStandbuch.seitenbuch(ck);
          return buch.zurueckschreiben('buch');
        });
        const gelegt = await unlesbarAblegen(k);
        await geraeteseite(k);
        const l = await lage(k);
        const ok = Number.isInteger(eigen) && gelegt.ok && l.version > gelegt.version && l.auf && !l.merker;
        return { ist: `eigene Fassung v${eigen}; unlesbar abgelegt (v${gelegt.version}); danach v${l.version}, `
                    + `geht ${l.auf ? 'auf' : 'NICHT auf'}, Merker ${l.merker ? 'GESETZT' : 'keiner'}`,
                 ok };
      } finally {
        buchLeeren();
        await ablageLeeren(k).catch(() => {});
      }
    },
  },
];
