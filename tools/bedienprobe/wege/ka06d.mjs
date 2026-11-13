/* Wege aus Konzept KA, Paket KA-06d — die Kopplung mit Vergleichswert.
 * ===========================================================================
 *
 * Ein nachgebautes Gerät (P-256-Paar, Zusage, Zufallszahl — wie die
 * Referenz `tools/proben/huelle/referenz.py`, Kopf „DER VERGLEICHSWERT DER
 * KOPPLUNG") koppelt über `pair.php`; die Person tippt den Code im Reiter
 * Geräte, verbindet, vergleicht und klickt (Mockup M-KA-01).
 *
 *   - `ka06d-kopplung-wert`: Das Gerät legt N_g offen, die Seite zeigt
 *     dieselbe Zahl, die das Gerät selbst rechnet; „Wert stimmt“ und das Ja
 *     des Geräts legen das Gerät an, mit `spk`. Weiche wie ausgeliefert:
 *     ohne Gerätebuch, also ohne `huelle`.
 *   - `ka06d-kopplung-buch`: dasselbe mit der Weiche nur im Browser der
 *     Probe umgelegt (wie `ka06b3.mjs`): das Gerät steht danach im
 *     Gerätebuch, `status` sagt `huelle: 2`; nach dem Entkoppeln trägt der
 *     Abgleich der Geräteseite `bis` ein.
 *   - `ka06d-kopplung-anders`: „Wert ist anders“ — `status` des Geräts sagt
 *     `abgelehnt`/`wert`, das Ja bekommt 409, kein Gerät.
 *   - `ka06d-kopplung-zusage`: Das Gerät legt ein anderes N_g offen, als es
 *     zugesagt hat — die Seite zeigt keine Zahl, sondern den Fehler, und
 *     `status` sagt `abgelehnt`/`zusage`.
 *
 * Danach löscht der Weg die angelegten Geräte des Prüfkontos (nur örtlich,
 * über die Datenbank); der Weg mit Gerätebuch leert davor und danach das
 * Standbuch des Prüfkontos.
 *
 * Anlass: Nr. 563.
 */
import { execFileSync } from 'node:child_process';
import { fileURLToPath } from 'node:url';
import { dirname, resolve } from 'node:path';

const WURZEL = resolve(dirname(fileURLToPath(import.meta.url)), '../../..');
const te = new TextEncoder();
const b64 = (u8) => Buffer.from(u8).toString('base64');
const ab = (s) => new Uint8Array(Buffer.from(s, 'base64'));
const sha = async (...teile) => {
  const n = teile.reduce((a, t) => a + t.length, 0); const x = new Uint8Array(n); let o = 0;
  for (const t of teile) { x.set(t, o); o += t.length; }
  return new Uint8Array(await crypto.subtle.digest('SHA-256', x));
};
const zufall = (n) => crypto.getRandomValues(new Uint8Array(n));

/** Ein Gerät, wie es die Referenz beschreibt. */
async function geraetBauen() {
  const paar = await crypto.subtle.generateKey({ name: 'ECDH', namedCurve: 'P-256' }, true, ['deriveBits']);
  const spk = new Uint8Array(await crypto.subtle.exportKey('raw', paar.publicKey));
  const nG = zufall(16);
  const zusage = await sha(te.encode('nadoku-kopplung-zusage-1'), nG, spk);
  return { spk, nG, zusage };
}
async function wertRechnen(K, spk, nG, nB) {
  const h = await sha(te.encode('nadoku-kopplung-wert-1'), K, spk, nG, nB);
  return String(new DataView(h.buffer).getUint32(0) % 1000000).padStart(6, '0');
}

async function pair(k, rumpf, g) {
  const kopf = { 'Content-Type': 'application/json' };
  if (g && g.id) { kopf['X-Device-Id'] = g.id; kopf['X-Api-Key'] = g.key; }
  const a = await k.seite.request.post(`${k.basis}/pair.php`, { data: rumpf, headers: kopf });
  let d = null; try { d = await a.json(); } catch (e) { d = null; }
  return { status: a.status(), d };
}

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

/** Start am Gerät, Code im Web, verbinden, bis zum Offenlegen. */
async function bisZumWert(k, nGOffen) {
  const g = await geraetBauen();
  const s = await pair(k, { aktion: 'start', geraet: { art: 'uhr', teil: '006-B3291-00' },
                            schluessel: { spk: b64(g.spk), zusage: b64(g.zusage) } });
  Object.assign(g, { id: s.d.device_id, key: s.d.api_key, code: s.d.code });
  await k.gehZu(`${k.basis}/einstellungen.php?t=geraete`);
  await k.seite.click('[data-dialog="koppeln"]');
  await k.seite.fill('#koppeln input[name="code"]', g.code);
  await Promise.all([k.seite.waitForNavigation(), k.seite.click('#koppeln .knopf-primaer')]);
  await Promise.all([k.seite.waitForNavigation(), k.seite.click('#koppeln .knopf-primaer')]);
  const st = await pair(k, { aktion: 'status' }, g);
  g.K = ab(st.d.kontoschluessel || ''); g.nB = ab(st.d.n_b || '');
  g.offen = await pair(k, { aktion: 'offenlegen', n_g: b64(nGOffen ? nGOffen : g.nG) }, g);
  g.wert = await wertRechnen(g.K, g.spk, g.nG, g.nB);
  return g;
}
const zahlLesen = async (k) => {
  await k.seite.waitForSelector('[data-kopplung-zahl]:visible, [data-kopplung-schritt="fehler"]:visible', { timeout: 20000 });
  return (await k.seite.locator('[data-kopplung-zahl]').innerText()).replace(/\s/g, '');
};
const PRUEFKONTO = "(SELECT id FROM users WHERE email='admin@example.invalid')";
const aufraeumen = () => execFileSync('mariadb', ['nadoku', '-e',
  `DELETE FROM devices WHERE user_id = ${PRUEFKONTO} AND spk IS NOT NULL; `
  + 'DELETE FROM pair_sessions WHERE spk IS NOT NULL;'], { cwd: WURZEL, stdio: 'ignore' });
/* Das Standbuch des Prüfkontos leeren: `ka06b2-standbuch` legt dort
 * Zufallstexte ab (Form ohne Inhalt), und ein solches Gerätebuch geht zu
 * Recht nicht auf. Nur örtlich. */
const buchLeeren = () => execFileSync('mariadb', ['nadoku', '-e',
  `DELETE FROM stand_blaetter WHERE user_id = ${PRUEFKONTO};`], { cwd: WURZEL, stdio: 'ignore' });

async function koppelnMitWert(k) {
  const g = await bisZumWert(k);
  const gezeigt = await zahlLesen(k);
  await k.seite.click('[data-kopplung-antwort="ja"]');
  try {
    await k.seite.waitForSelector('[data-kopplung-schritt="geraet"]:visible', { timeout: 10000 });
  } catch (e) {
    throw new Error('nach „Wert stimmt“: ' + (await k.seite.locator('#koppeln').innerText()).replace(/\s+/g, ' ').slice(0, 300));
  }
  const ja = await pair(k, { aktion: 'bestaetigen', antwort: 'ja' }, g);
  const st = await pair(k, { aktion: 'status' }, g);
  return { g, gezeigt, ja, st };
}

export const wege = [
  {
    name: 'ka06d-kopplung-wert',
    paket: 'KA-06', punkt: 'F-KA-17', rolle: 'admin',
    soll: 'gleiche Zahl auf Seite und Gerät; Wert stimmt + Ja → Gerät gekoppelt, ohne huelle (Weiche aus)',
    async fahren(k) {
      try {
        const e = await koppelnMitWert(k);
        const ok = e.g.offen.status === 200 && e.gezeigt === e.g.wert && e.ja.status === 200
          && e.st.d.zustand === 'gekoppelt' && !('huelle' in e.st.d);
        return { ist: `offenlegen ${e.g.offen.status}, Seite ${e.gezeigt}, Gerät ${e.g.wert}, Ja ${e.ja.status}, `
                    + `status ${JSON.stringify(e.st.d)}`, ok };
      } finally { aufraeumen(); }
    },
  },
  {
    name: 'ka06d-kopplung-buch',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'admin',
    soll: 'mit der Weiche an: Gerät im Gerätebuch, status huelle 2',
    async fahren(k) {
      buchLeeren();
      await weicheAn(k.seite);
      try {
        const e = await koppelnMitWert(k);
        const imBuch = await k.seite.evaluate(async (spk) => {
          const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
          const g = await (await EdStandbuch.seitenbuch(ck)).geraete();
          return Object.values(g).some((x) => EdCrypto.teile.toB64(x.spk) === spk && x.bis == null);
        }, b64(e.g.spk));
        /* Entkoppelt (hier über die Datenbank): Beim nächsten Laden der
         * Geräteseite bekommt der Eintrag `bis`. */
        execFileSync('mariadb', ['nadoku', '-e', `DELETE FROM devices WHERE device_id = '${e.g.id}'`],
                     { cwd: WURZEL, stdio: 'ignore' });
        await k.gehZu(`${k.basis}/einstellungen.php?t=geraete`);
        let bis = null;
        for (let i = 0; i < 20 && bis == null; i++) {
          await k.seite.waitForTimeout(500);
          bis = await k.seite.evaluate(async (spk) => {
            const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
            const buch = EdStandbuch.oeffnen({ konto: await EdStandbuch.kontoKennung(ck), schluessel: ck,
                                               transport: EdStandbuch.apiTransport() });
            const g = await buch.geraete();
            const x = Object.values(g).find((y) => EdCrypto.teile.toB64(y.spk) === spk);
            return x ? x.bis : null;
          }, b64(e.g.spk));
        }
        const ok = e.gezeigt === e.g.wert && e.ja.status === 200 && e.st.d.huelle === 2 && imBuch && bis != null;
        return { ist: `Seite ${e.gezeigt} = Gerät ${e.g.wert}; status ${JSON.stringify(e.st.d)}; `
                    + `Gerätebuch ${imBuch ? 'trägt es' : 'trägt es NICHT'}; nach dem Entkoppeln `
                    + `${bis != null ? 'mit bis' : 'OHNE bis'}`, ok };
      } finally {
        await k.seite.unroute(/assets\/inhalt2\.js/).catch(() => {});
        aufraeumen();
        buchLeeren();
      }
    },
  },
  {
    name: 'ka06d-kopplung-anders',
    paket: 'KA-06', punkt: 'F-KA-17', rolle: 'admin',
    soll: '„Wert ist anders“: status abgelehnt/wert, Ja 409, kein Gerät',
    async fahren(k) {
      try {
        const g = await bisZumWert(k);
        await zahlLesen(k);
        await k.seite.click('[data-kopplung-antwort="nein"]');
        await k.seite.waitForSelector('[data-kopplung-schritt="fehler"]:visible', { timeout: 10000 });
        const st = await pair(k, { aktion: 'status' }, g);
        const ja = await pair(k, { aktion: 'bestaetigen', antwort: 'ja' }, g);
        const ok = st.d.zustand === 'abgelehnt' && st.d.grund === 'wert' && ja.status === 409;
        return { ist: `status ${JSON.stringify(st.d)}, Ja ${ja.status}`, ok };
      } finally { aufraeumen(); }
    },
  },
  {
    name: 'ka06d-kopplung-zusage',
    paket: 'KA-06', punkt: 'F-KA-17', rolle: 'admin',
    soll: 'anderes N_g als zugesagt: Seite zeigt Fehler statt Zahl, status abgelehnt/zusage',
    async fahren(k) {
      try {
        const g = await bisZumWert(k, zufall(16));
        await k.seite.waitForSelector('[data-kopplung-schritt="fehler"]:visible', { timeout: 20000 });
        const zahl = (await k.seite.locator('[data-kopplung-zahl]').innerText()).trim();
        await k.seite.waitForTimeout(500);
        const st = await pair(k, { aktion: 'status' }, g);
        const ok = zahl === '' && st.d.zustand === 'abgelehnt' && st.d.grund === 'zusage';
        return { ist: `Zahl ${zahl ? 'GEZEIGT' : 'nicht gezeigt'}, status ${JSON.stringify(st.d)}`, ok };
      } finally { aufraeumen(); }
    },
  },
];
