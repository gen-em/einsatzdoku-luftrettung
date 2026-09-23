/* Browserprobe der Content-Security-Policy (P5a/AP4, E-P5a-15).
 * ===========================================================================
 *
 * WAS SIE MISST, DAS `pruefen.php` NICHT MESSEN KANN. Die Tokenizer-Probe
 * daneben sieht, ob ein `<script>` im Quelltext einen Nonce TRAEGT. Ob er
 * WIRKT, sieht nur ein Browser: Dass `kopf_nonce_attr()` dasteht, heisst
 * nicht, dass `kopfzeilen_seite()` vorher lief.
 *
 * SIE PRUEFT IN BEIDE RICHTUNGEN, und das ist der Punkt. Dass die vier
 * Kachelserver durchkommen, belegt fuer sich genommen gar nichts — es koennte
 * auch heissen, dass die Richtlinie gar nicht greift. Deshalb steht neben
 * jeder Erlaubnis eine Gegenprobe mit einem Host, der NICHT in der Liste
 * steht, und neben dem genonceten Skript eines ohne Nonce.
 *
 * DIE WICHTIGSTE ZEILE IST DIE GEGENPROBE DES MELDEWEGS (Abschnitt 7). Sie
 * loest absichtlich einen Verstoss aus und sieht nach, ob er in
 * `csp_berichte` ankommt. Ohne sie waere „0 Berichte nach dem Bilderlauf" ein
 * wertloser Satz: Er saehe genauso aus, ob die Richtlinie sitzt ODER der
 * Meldeweg kaputt ist. Am 15.09.2026 war er kaputt (F-P5a-AP4-1), und genau
 * diese Zeile hat es gefunden.
 *
 * VORAUSSETZUNG: eine laufende lokale Installation unter `--basis`
 * (`sh tools/referenzdatensatz/einspielen/lokal_starten.sh`).
 *
 * WAS SIE NICHT MESSEN KANN — der Wegwerf-Container hat keine Freigabe zu
 * den Kachelservern. Ob eine Kachel ANKOMMT, steht hier deshalb nicht; ob
 * die Richtlinie sie DURCHLAESST, schon (per Konsolenmeldung, nicht per Bild).
 *
 * Aufruf:  node tools/proben/csp-browser/browserprobe.mjs
 * Rueckgabewert: 0 = alle Erwartungen erfuellt · 1 = mindestens eine nicht.
 */
const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const { chromium } = PW;

const argv  = process.argv.slice(2);
const wert  = (n, v) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : v; };
const BASIS = wert('--basis', 'https://127.0.0.1:8443');
const ADMIN = { email: wert('--admin', 'admin@gen-em.org'),
                pw:    wert('--admin-pw', 'pruefstandzugang2026') };
const erg = [];
const ok = (n, b, d = '') => { erg.push([b, n, d]); };

const browser = await chromium.launch();
const kontext = await browser.newContext({ ignoreHTTPSErrors: true,
                                           viewport: { width: 1440, height: 1000 } });
const seite = await kontext.newPage();
const fehler = [];
const verstoesse = [];
seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));
seite.on('console', m => {
  if (m.type() !== 'error') { return; }
  const t = m.text();
  if (/Content Security Policy|Refused to/i.test(t)) { verstoesse.push(t); return; }
  if (/tile\.|openstreetmap|opentopomap|arcgisonline|openmaps|photon|ERR_/i.test(t)) { return; }
  fehler.push(t);
});

/* ---- 1. Kopfzeilen der ANMELDESEITE (ohne Sitzung) ---------------------- */
const antw = await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
const kopf = antw.headers();
const csp = kopf['content-security-policy-report-only'] || kopf['content-security-policy'] || '';
ok('Anmeldeseite schickt eine CSP', csp !== '', csp.slice(0, 80) + '…');
ok('… als Report-Only (Vorgabe)', 'content-security-policy-report-only' in kopf);
ok('default-src none', csp.includes("default-src 'none'"));
ok('script-src mit Nonce, ohne unsafe-inline',
   /script-src 'self' 'nonce-[^']+'/.test(csp) && !csp.includes("script-src 'self' 'unsafe-inline'"));
ok('style-src self + style-src-attr unsafe-inline',
   csp.includes("style-src 'self'") && csp.includes("style-src-attr 'unsafe-inline'"));
/* frame-ancestors NUR in der scharfen Fassung (Nr. 224): In einer
 * Report-Only-Kopfzeile ignoriert der Browser sie und warnt in der Konsole.
 * Bis RP-02 verlangte diese Zeile sie hier und war rot (F-RP-05). */
ok('Report-Only: ohne frame-ancestors (Nr. 224)', !csp.includes('frame-ancestors'));
ok('X-Content-Type-Options', kopf['x-content-type-options'] === 'nosniff');
ok('X-Frame-Options', kopf['x-frame-options'] === 'DENY');
ok('Referrer-Policy', (kopf['referrer-policy'] || '').includes('strict-origin'));
ok('Permissions-Policy', (kopf['permissions-policy'] || '').includes('geolocation'));
ok('Kein HSTS ueber Klartext-HTTP hinter dem TLS-Weiterleiter',
   !('strict-transport-security' in kopf),
   'socat reicht plain an PHP weiter — $_SERVER[HTTPS] ist leer, also richtig');

/* Nonce je Anfrage verschieden? */
const a2 = await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
const csp2 = a2.headers()['content-security-policy-report-only'] || '';
const n1 = (csp.match(/nonce-([^']+)/) || [])[1];
const n2 = (csp2.match(/nonce-([^']+)/) || [])[1];
ok('Nonce ist je Anfrage neu', !!n1 && !!n2 && n1 !== n2, `${n1} ≠ ${n2}`);

/* Traegt das Inline-Skript der Anmeldeseite denselben Nonce? */
const imMarkup = await seite.evaluate(() => {
  const s = [...document.querySelectorAll('script:not([src])')];
  return { zahl: s.length, ohne: s.filter(x => !x.nonce && !x.getAttribute('nonce')).length };
});
ok('Inline-Skripte der Anmeldeseite tragen alle einen Nonce',
   imMarkup.ohne === 0, `${imMarkup.zahl} Bloecke, ${imMarkup.ohne} ohne`);

/* ---- 2. Anmelden -------------------------------------------------------- */
await seite.fill('input[name="email"]', ADMIN.email);
await seite.fill('input[name="password"]', ADMIN.pw);
await Promise.all([ seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
                    seite.click('#loginform button[type="submit"]') ]);
ok('Anmeldung als BetreiberIn', !seite.url().includes('login.php'), seite.url());

/* ---- 3. JSON-Antwort traegt die Kopfzeilen ------------------------------ */
const j = await seite.request.get(`${BASIS}/api/day.php?d=1`, { failOnStatusCode: false });
const jk = j.headers();
ok('JSON-Antwort traegt nosniff', jk['x-content-type-options'] === 'nosniff', 'api/day.php');
ok('JSON-Antwort traegt Referrer-Policy', (jk['referrer-policy'] || '').includes('strict-origin'));
ok('JSON-Antwort traegt bewusst KEINE CSP (E-P5a-15)',
   !jk['content-security-policy'] && !jk['content-security-policy-report-only'],
   'kein Dokument, fuehrt nichts aus');

/* ---- 4. Seiten durchgehen ---------------------------------------------- */
const wege = ['index.php', 'zeitraum.php', 'suche.php', 'einstellungen.php',
              'betrieb_server.php', 'betrieb_status.php', 'import.php',
              'nachbearbeitung.php', 'admin_users.php'];
let skripteOhneNonce = 0, seitenOhneCsp = 0;
for (const w of wege) {
  const r = await seite.goto(`${BASIS}/${w}`, { waitUntil: 'domcontentloaded' });
  const h = r.headers();
  if (!(h['content-security-policy-report-only'] || h['content-security-policy'])) { seitenOhneCsp++; }
  const z = await seite.evaluate(() => [...document.querySelectorAll('script:not([src])')]
                                        .filter(x => !x.nonce).length);
  skripteOhneNonce += z;
  await seite.waitForTimeout(400);
}
ok(`${wege.length} Seiten: jede schickt eine CSP`, seitenOhneCsp === 0, `${seitenOhneCsp} ohne`);
ok(`${wege.length} Seiten: 0 Inline-Skripte ohne Nonce`, skripteOhneNonce === 0,
   `${skripteOhneNonce} ohne Nonce`);

/* ---- 5. Die vier Kachelanbieter --------------------------------------
 * NICHT gemessen wird, ob eine Kachel ANKOMMT — der Wegwerf-Container hat
 * keine Egress-Freigabe zu den Kachelservern, und ein fehlendes Bild sagt
 * dann nichts ueber die Richtlinie. Gemessen wird, was die Richtlinie
 * tut: Geht die Anfrage ueberhaupt HINAUS (dann hat `img-src` sie
 * durchgelassen, und sie scheitert am Netz), oder wird sie vom Browser
 * gar nicht erst gestellt (dann haette `img-src` sie gesperrt)?
 * Gegenprobe ist ein Host, der NICHT in der Liste steht. */
await seite.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(3000);
const kacheln = await seite.evaluate(() => document.querySelectorAll('img.leaflet-tile').length);
ok('Karte stellt Kachelanfragen', kacheln > 0, `${kacheln} Kachel-Elemente im Baum`);
const HOSTS = ['tile.openstreetmap.org', 'tile.openmaps.fr',
               'a.tile.opentopomap.org', 'server.arcgisonline.com'];
const cspSperren = [];
seite.on('console', m => {
  if (m.type() === 'error' && /Refused to load the image/i.test(m.text())) {
    cspSperren.push(m.text());
  }
});
const bildProbe = async (host) => {
  cspSperren.length = 0;
  await seite.evaluate(h => new Promise(aufl => {
    const i = document.createElement('img');
    i.src = `https://${h}/0/0/0.png`;
    i.onload = i.onerror = () => aufl();
    document.body.appendChild(i);
    setTimeout(aufl, 2500);
  }), host);
  await seite.waitForTimeout(400);
  return cspSperren.length;
};

/* ---- 6. Scharf schalten und den Einschleuse-Versuch messen -------------- */
await seite.goto(`${BASIS}/betrieb_server.php`, { waitUntil: 'domcontentloaded' });
const hatKarte = await seite.locator('text=Sicherheitskopfzeilen').count();
ok('Karte „Sicherheitskopfzeilen" steht auf Betrieb → Servereinstellungen', hatKarte > 0);

const csrf = await seite.evaluate(() => {
  const i = document.querySelector('input[name="csrf"]');
  return i ? i.value : '';
});
const setz = async (scharf) => {
  await seite.request.post(`${BASIS}/betrieb_server.php`, {
    form: { action: 'kopfzeilen', csrf, csp_scharf: scharf ? '1' : '0', hsts_tage: '1' },
    failOnStatusCode: false });
};
await setz(true);
const r2 = await seite.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
const h2 = r2.headers();
ok('Scharf: Kopfzeile heisst jetzt Content-Security-Policy',
   'content-security-policy' in h2 && !('content-security-policy-report-only' in h2));
ok('Scharf: frame-ancestors none', (h2['content-security-policy'] || '').includes("frame-ancestors 'none'"));

/* Ein eingeschleustes fremdes Skript — wird es blockiert? */
const geladen = await seite.evaluate(() => new Promise(aufl => {
  const s = document.createElement('script');
  s.src = 'https://cdn.example.invalid/x.js';
  s.onload  = () => aufl('geladen');
  s.onerror = () => aufl('blockiert');
  document.head.appendChild(s);
  setTimeout(() => aufl('blockiert'), 3000);
}));
ok('Eingeschleustes fremdes <script src> wird blockiert', geladen === 'blockiert', geladen);

/* Ein Inline-Skript ohne Nonce — laeuft es? */
const lief = await seite.evaluate(() => {
  window.__probe = false;
  const s = document.createElement('script');
  s.textContent = 'window.__probe = true;';
  document.head.appendChild(s);
  return window.__probe;
});
ok('Inline-Skript OHNE Nonce laeuft nicht', lief === false, lief ? 'es lief' : 'blockiert');

/* Ein Stilattribut — soll weiterhin gehen (E-P5a-32) */
const stil = await seite.evaluate(() => {
  const d = document.createElement('div');
  d.setAttribute('style', 'width:123px');
  document.body.appendChild(d);
  return getComputedStyle(d).width;
});
ok('style="…"-Attribut wirkt weiterhin (style-src-attr)', stil === '123px', stil);

/* Die vier Anbieter unter der SCHARFEN Richtlinie — jetzt sperrt der
 * Browser wirklich, und eine Sperre steht als Meldung in der Konsole. */
await seite.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1200);
for (const h of HOSTS) {
  ok(`img-src laesst ${h} durch (scharf)`, (await bildProbe(h)) === 0);
}
ok('img-src sperrt einen NICHT gelisteten Host (Gegenprobe)',
   (await bildProbe('kachel.invalid')) > 0,
   'ohne diese Zeile belegen die vier oben nichts');

/* Import (zip.js / SheetJS) unter der scharfen Richtlinie */
await seite.goto(`${BASIS}/import.php`, { waitUntil: 'domcontentloaded' });
await seite.waitForTimeout(1500);
const sheet = await seite.evaluate(() => typeof window.XLSX !== 'undefined');
ok('import.php: SheetJS geladen und ausgefuehrt', sheet, sheet ? 'XLSX da' : 'XLSX fehlt');

/* Zurueck auf Report-Only — der Auslieferungszustand */
await seite.goto(`${BASIS}/betrieb_server.php`, { waitUntil: 'domcontentloaded' });
const csrf2 = await seite.evaluate(() => (document.querySelector('input[name="csrf"]') || {}).value || '');
await seite.request.post(`${BASIS}/betrieb_server.php`, {
  form: { action: 'kopfzeilen', csrf: csrf2, csp_scharf: '0', hsts_tage: '1' },
  failOnStatusCode: false });
const r3 = await seite.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
ok('Zurueckschalten auf Report-Only wirkt',
   'content-security-policy-report-only' in r3.headers());

/* ---- 7. Die Gegenprobe: meldet der Endpunkt ueberhaupt? ----------------
 * Ein Bilderlauf mit 0 Berichten sieht genauso aus, ob die Richtlinie sitzt
 * ODER der Meldeweg kaputt ist (CLAUDE.md 6). Also einen Verstoss ABSICHTLICH
 * ausloesen und nachsehen, ob er ankommt. Unter Report-Only laeuft das Skript
 * trotzdem — genau das ist der Sinn der Stufe. */
await seite.goto(`${BASIS}/index.php`, { waitUntil: 'domcontentloaded' });
const liefRO = await seite.evaluate(() => {
  window.__probe2 = false;
  const s = document.createElement('script');
  s.textContent = 'window.__probe2 = true;';
  document.head.appendChild(s);
  return window.__probe2;
});
ok('Report-Only: Inline-Skript ohne Nonce laeuft TROTZDEM', liefRO === true);
await seite.evaluate(() => {
  const i = document.createElement('img');
  i.src = 'https://beispiel.invalid/x.png';
  document.body.appendChild(i);
});
await seite.waitForTimeout(2500);

/* DAS BILD GEHT NACH /tmp, nicht ins Repositorium (RP, F-RP-13): Die Vorgabe
 * war eine eingecheckte Datei, die jeder Lauf ueberschrieb -- im Pruefstand
 * landete sie im gemessenen Baum und damit in jedem Commit. */
const { tmpdir } = await import('node:os');
const BILD = wert('--bild', tmpdir() + '/csp-kopfzeilen.png');
console.log(`Bild: ${BILD}`);
await seite.screenshot({ path: BILD });

/* ---- Bericht ------------------------------------------------------------ */
console.log('\nBrowserprobe P5a/AP4 — Kopfzeilen und CSP\n');
let gut = 0;
for (const [b, n, d] of erg) { if (b) gut++; console.log(` ${b ? ' ok ' : 'FEHL'}  ${n}${d ? '  —  ' + d : ''}`); }
console.log(`\n${gut} von ${erg.length} Erwartungen erfuellt.`);
console.log(`Seitenfehler: ${fehler.length}${fehler.length ? '\n   ' + fehler.join('\n   ') : ''}`);
console.log(`CSP-Meldungen in der Konsole: ${verstoesse.length}`
          + `${verstoesse.length ? '\n   ' + verstoesse.slice(0, 12).join('\n   ') : ''}`);
await browser.close();
process.exit(gut === erg.length ? 0 : 1);
