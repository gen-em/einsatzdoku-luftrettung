/* Wege aus Konzept KA, Paket KA-06b-3 — die Verbraucher des neuen Kerns.
 * ===========================================================================
 *
 * Die Weiche (`SCHREIBT_FASSUNG_2` in assets/inhalt2.js) steht in der
 * Anwendung aus. Diese Wege legen sie NUR IM BROWSER DER PROBE um: Playwright
 * fängt die Antwort für `inhalt2.js` ab und ersetzt `= false` durch `= true`
 * (`page.route`). Damit läuft die echte Seite, der echte Endpunkt und die
 * echte Datenbank — nur der Schalter ist ein anderer.
 *
 *   - `ka06b3-patient-weiche-an`: einen Einsatz mit geschützten Angaben im
 *     Formular speichern. Danach trägt er `edk2:`, ein Blatt des Standbuchs
 *     ist gestiegen, und der Block geht wieder auf. Dieselbe Anfrage noch
 *     einmal gesendet (das Blatt darin ist jetzt veraltet) bekommt 409 und
 *     ändert nichts.
 *   - `ka06b3-patient-weiche-aus`: dieselbe Seite mit der Weiche, wie sie
 *     ausgeliefert ist — der Einsatz trägt wieder `edk1:`, und die Anfrage
 *     hat kein Feld `blaetter`.
 *
 * Anlass: Nr. 563.
 */
const WEICHE = /const SCHREIBT_FASSUNG_2 = false;/;

async function weicheAn(seite) {
  await seite.route(/assets\/inhalt2\.js/, async (route) => {
    const antwort = await route.fetch();
    const text = await antwort.text();
    if (!WEICHE.test(text)) { throw new Error('Weiche in inhalt2.js nicht gefunden'); }
    /* OHNE Länge und Kodierung des Originals: `true` ist ein Zeichen kürzer
       als `false`, und eine Antwort mit falscher Länge verwirft der Browser
       (net::ERR_FAILED) — dann fehlte `EdInhalt` auf der ganzen Seite. */
    const kopf = Object.assign({}, antwort.headers(), { 'cache-control': 'no-store' });
    delete kopf['content-length']; delete kopf['content-encoding'];
    await route.fulfill({ status: antwort.status(), headers: kopf,
                          body: text.replace(WEICHE, 'const SCHREIBT_FASSUNG_2 = true;') });
  });
}

/* Ein Einsatz des Kontos mit geschützten Angaben: aus dem Suchindex. */
async function einsatzMitAngaben(k) {
  return k.seite.evaluate(async () => {
    const r = await fetch('api/suchindex.php', { credentials: 'same-origin' });
    const j = await r.json();
    const m = (j.missions || []).find((x) => x.pat_blob && x.id);
    return m ? { id: m.id, ref: m.client_ref } : null;
  });
}

const blobVon = (k, id) => k.seite.evaluate(async (id) =>
  (await (await fetch('api/mission.php?id=' + id, { credentials: 'same-origin' })).json()).pat_blob || null, id);

const standSumme = (k) => k.seite.evaluate(async () => {
  const s = await EdStandbuch.apiTransport().stand();
  return s.blaetter.reduce((a, b) => a + b, 0);
});

/* Das Formular speichern und die Anfrage mitschneiden. */
async function speichern(k, id) {
  let rumpf = null;
  const fang = (r) => {
    if (r.method() === 'POST' && r.url().includes('einsatz_form.php')) { rumpf = r.postData(); }
  };
  k.seite.on('request', fang);
  await k.gehZu(`${k.basis}/einsatz_form.php?id=${id}`);
  await k.seite.waitForTimeout(1500);                   // patLaden(): die Angaben stehen im Formular
  await Promise.all([
    k.seite.waitForURL(/einsatz\.php/, { timeout: 30000 }),
    k.seite.evaluate(() => document.getElementById('missionform').requestSubmit()),
  ]);
  k.seite.off('request', fang);
  return rumpf;
}

export const wege = [
  {
    name: 'ka06b3-patient-weiche-an',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'demo',
    soll: 'mit der Weiche an: edk2: gespeichert, Blatt gestiegen, Block geht auf; '
        + 'dieselbe Anfrage noch einmal → 409, Block unverändert',
    async fahren(k) {
      await weicheAn(k.seite);
      try {
        await k.gehZu(`${k.basis}/index.php`);
        const m = await einsatzMitAngaben(k);
        if (!m) { return { ist: 'kein Einsatz mit geschützten Angaben im Demo-Konto', ok: false }; }
        const vorher = await standSumme(k);
        const rumpf = await speichern(k, m.id);
        await k.gehZu(`${k.basis}/index.php`);
        const blob = await blobVon(k, m.id);
        const nachher = await standSumme(k);
        const auf = await k.seite.evaluate(async ({ blob, ref }) => {
          try {
            const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
            return !!JSON.parse(await EdStandbuch.patOeffnen(ck, blob, ref));
          } catch (e) { return false; }
        }, { blob, ref: m.ref });
        /* Dieselbe Anfrage noch einmal: Ihr Blatt nennt die alte Version. */
        const nochmal = await k.seite.request.post(`${k.basis}/einsatz_form.php?id=${m.id}`, {
          data: rumpf, headers: { 'Content-Type': 'application/x-www-form-urlencoded' }, maxRedirects: 0 });
        const blobDanach = await blobVon(k, m.id);
        const mitBlatt = typeof rumpf === 'string' && rumpf.includes('blaetter=');
        const ok = String(blob).startsWith('edk2:') && nachher > vorher && auf && mitBlatt
          && nochmal.status() === 409 && blobDanach === blob;
        return { ist: `gespeichert ${String(blob).slice(0, 5)}, Blätter ${vorher} → ${nachher}, `
                    + `Feld blaetter ${mitBlatt ? 'mitgeschickt' : 'FEHLT'}, Block ${auf ? 'geht auf' : 'geht NICHT auf'}, `
                    + `Wiederholung HTTP ${nochmal.status()}, Block danach ${blobDanach === blob ? 'unverändert' : 'GEÄNDERT'}`,
                 ok };
      } finally {
        await k.seite.unroute(/assets\/inhalt2\.js/);
      }
    },
  },
  {
    name: 'ka06b3-patient-weiche-aus',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'demo',
    soll: 'mit der ausgelieferten Weiche: edk1: gespeichert, kein Feld blaetter',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const m = await einsatzMitAngaben(k);
      if (!m) { return { ist: 'kein Einsatz mit geschützten Angaben im Demo-Konto', ok: false }; }
      const rumpf = await speichern(k, m.id);
      await k.gehZu(`${k.basis}/index.php`);
      const blob = await blobVon(k, m.id);
      const mitBlatt = typeof rumpf === 'string' && rumpf.includes('blaetter=');
      const ok = String(blob).startsWith('edk1:') && !mitBlatt;
      return { ist: `gespeichert ${String(blob).slice(0, 5)}, Feld blaetter ${mitBlatt ? 'MITGESCHICKT' : 'nicht da'}`, ok };
    },
  },
];
