/* Umstellungslauf — stellt der BROWSER die Schlüsselhülle von selbst um?
 * (S10/AP2, E-S10-07)
 * ===========================================================================
 *
 * WAS HIER GEMESSEN WIRD, KANN KEIN ANDERES PRÜFMITTEL MESSEN.
 * `tools/proben/anteil/endpunkt.py` baut die neue Hülle selbst und schickt sie
 * an `api/kdf_upgrade.php` — damit ist gezeigt, dass der SERVER sie richtig
 * annimmt und richtig abweist. Ob `unlock.js` sie beim Anmelden von selbst
 * baut, steht damit NICHT fest. Das ist der eigentliche Vorgang von S10, und
 * er läuft nur in einem echten Browser.
 *
 * SIE STELLT IHRE EIGENE VORAUSSETZUNG HER. Die Umstellung lässt sich je
 * Konto genau EINMAL beobachten; danach ist sie gelaufen. Der Lauf setzt die
 * Hülle des Prüfkontos deshalb vorher auf `edk1:` zurück
 * (`huelle_stellen.py`) — sonst misst der zweite Aufruf etwas anderes als der
 * erste, und das Prüfmittel bestätigte sich selbst.
 *
 * GEMESSEN WIRD AN `umlauf-csv@gen-em.org`, nicht am Admin-Konto: Der
 * Referenzbestand liegt dort (83 Einsätze), und die Frage dieses Pakets ist
 * nicht „läuft es durch", sondern „sind die geschützten Angaben danach noch
 * lesbar". Das Admin-Konto hat 0 Einsätze und könnte diese Frage nicht
 * beantworten.
 *
 * **Gegen eine Testinstallation fahren, nicht gegen den Produktivserver.**
 *
 * Aufruf:
 *   node tools/proben/anteil/umstellungslauf.mjs
 *   node tools/proben/anteil/umstellungslauf.mjs --motor firefox
 *   node tools/proben/anteil/umstellungslauf.mjs --motor webkit
 *
 * Rückgabewert: 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.
 */
import { spawnSync } from 'node:child_process';
const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import('file://' + MODUL);
const pw = PW.default ?? PW;
const { motorWahl, starten, nachDemPasswort } = await import(
  'file:///home/user/einsatzdoku-luftrettung/tools/motor.mjs');

const BASIS = 'https://127.0.0.1:8443';
/* Das ADMIN-Konto hat 0 Einsätze (der Referenzbestand liegt im Demo- und in
 * den beiden Umlauf-Konten). Gemessen wird deshalb an `umlauf-csv@…`: Es
 * trägt 83 Einsätze, steht auf `edk1:` und ist damit der einzige Bestand, an
 * dem sich „vorher lesbar / nachher lesbar" überhaupt zeigen lässt. */
const KONTO = 'umlauf-csv@gen-em.org', KONTO_PW = 'umlaufpruefung2026';
const motor = motorWahl(process.argv.slice(2));

let ok = 0, offen = 0, nichtGemessen = 0;
const pruefe = (was, ist, soll) => {
  const gleich = JSON.stringify(ist) === JSON.stringify(soll);
  gleich ? ok++ : offen++;
  console.log(`  ${gleich ? 'ok  ' : 'FEHL'} ${was.padEnd(56)} ${JSON.stringify(ist)}`
    + (gleich ? '' : `  erwartet: ${JSON.stringify(soll)}`));
};

const browser = await starten(pw, motor, {});
const ctx = await browser.newContext({ ignoreHTTPSErrors: true });

/* Jeder Aufruf von api/kdf_upgrade.php wird mitgeschnitten — die Abnahme
 * verlangt „0 Aufrufe beim zweiten Anmelden". */
let upgrades = [];
ctx.on('response', r => {
  if (r.url().includes('kdf_upgrade.php')) { upgrades.push(r.status()); }
});
/* ---- Konsolenfehler: ZWEI TOEPFE, UND NUR EINER ZAEHLT ------------------
 *
 * Der Prüfstand erzeugt Fehler, die nichts mit der Anwendung zu tun haben,
 * und sie in eine Zahl mit den echten zu werfen macht die Zahl wertlos:
 *
 *   - `tile.openstreetmap.org` — die Kartenkacheln liegen AUSSERHALB und
 *     werden vom Egress-Filter des Containers abgewiesen. Auf der echten
 *     Installation kommen sie an; hier nie.
 *   - `ERR_ABORTED` auf eigene Schriften — der Browser bricht laufende
 *     Anfragen ab, wenn die Probe zur nächsten Seite weitergeht. Das ist
 *     eine Eigenschaft des Laufs, keine der Seite.
 *
 * Gezählt wird deshalb, was aus der ANWENDUNG kommt: JS-Ausnahmen
 * (`pageerror`) und Konsolenfehler, die nicht zu einer dieser beiden Sorten
 * gehören. Die übrigen werden trotzdem genannt — eine Zahl, die
 * verschweigt, was sie aussortiert hat, ist die andere Art, sich selbst zu
 * bestätigen (`CLAUDE.md` 6). */
const konsole = [];       // zählt: Fehler der Anwendung
const umfeld = [];        // nennt: Fehler des Prüfstands
/* JEDE ENGINE NENNT DEN ABBRUCH ANDERS, und das ist genau die Falle, gegen
 * die AP3b die drei Motoren eingeführt hat: Chromium sagt `ERR_ABORTED`,
 * WebKit `Load request cancelled`, Firefox `NS_BINDING_ABORTED`. Wer nur
 * gegen die Chromium-Schreibweise filtert, bekommt in den anderen beiden
 * eine rote Zahl für dasselbe harmlose Verhalten — oder, schlimmer, filtert
 * später einmal umgekehrt zu viel weg. */
const ABBRUCH = /ERR_ABORTED|Load request cancelled|NS_BINDING_ABORTED/;
/* Firefox meldet denselben Abbruch NICHT als abgewiesene Anfrage, sondern als
 * JavaScript-Fehler mit einem ZAHLENCODE: „downloadable font: download failed
 * … status=2152398850". Das ist 0x804B0002 = NS_BINDING_ABORTED — Gecko
 * bricht die Schriftanfrage ab, wenn die Probe zur nächsten Seite weitergeht.
 * Eine Falle, die genau so nur in einer der drei Engines auftritt: Chromium
 * und WebKit melden hier gar nichts. Erkannt wird sie eng — nur für eine
 * EIGENE Adresse unter `/assets/`, damit kein echter Fehler mit
 * durchrutscht. */
const FF_SCHRIFTABBRUCH =
  /downloadable font: download failed[\s\S]*status=2152398850[\s\S]*127\.0\.0\.1[\s\S]*\/assets\//;
const istUmfeld = (t) => /tile\.openstreetmap\.org/.test(t)
  || ABBRUCH.test(t)
  || FF_SCHRIFTABBRUCH.test(t)
  || (/ERR_CONNECTION_RESET/.test(t) && !/127\.0\.0\.1/.test(t));
ctx.on('console', m => {
  if (m.type() !== 'error') { return; }
  // Eine nackte „Failed to load resource" ohne URL gehört zu der Anfrage,
  // die `requestfailed` gleich daneben nennt — sie wird dort gezählt.
  if (/Failed to load resource/.test(m.text())) { umfeld.push(m.text()); return; }
  (istUmfeld(m.text()) ? umfeld : konsole).push(m.text());
});
ctx.on('requestfailed', r => {
  const t = r.url() + ' — ' + (r.failure() ? r.failure().errorText : '?');
  (istUmfeld(t) ? umfeld : konsole).push('requestfailed ' + t);
});
ctx.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const seite = await ctx.newPage();

/* NICHT `waitUntil: 'networkidle'`. Die Kartenkacheln liegen bei
 * `tile.openstreetmap.org` und werden vom Egress-Filter des Containers
 * abgewiesen; Firefox und WebKit versuchen es weiter, und „das Netz ist
 * ruhig" tritt nie ein — der Lauf lief dort in die Zeitgrenze, waehrend
 * Chromium durchkam. Gewartet wird stattdessen auf das, worauf es ankommt:
 * dass die Krypto-Bausteine der Seite geladen sind. */
async function oeffne(p, pfad) {
  await p.goto(BASIS + pfad, { waitUntil: 'domcontentloaded' });
  await p.waitForFunction(
    () => typeof EdCrypto !== 'undefined' && typeof EdUnlock !== 'undefined',
    null, { timeout: 60000 });
}

/**
 * Anmelden — und eine abgewiesene Anmeldung MELDEN statt auf sie zu warten.
 *
 * WARUM DAS NICHT NUR `waitForURL` IST. Beim Bauen von AP2 blieb dieser Lauf
 * dreimal in der Zeitgrenze stehen, und die Meldung lautete „Timeout
 * 180000ms exceeded" — was aussieht wie ein hängender Server. Tatsächlich
 * stand auf der Anmeldeseite ein Satz: „Das Demo-Konto wird gerade sehr
 * häufig genutzt und ist vorübergehend gesperrt." Die Mengenbremse des
 * Demo-Kontos (E-P1-20: 20 Anmeldungen je Stunde) hatte zugeschlagen, weil
 * die Probe selbst sie ausgelöst hatte.
 *
 * Drei Minuten warten und dann das Falsche melden ist die schlechteste
 * Antwort von allen. Diese Fassung sieht nach jedem Versuch nach, ob die
 * Seite eine Meldung trägt, und gibt sie zurück — der Aufrufer entscheidet
 * dann, ob das ein Fehlschlag ist oder ein „nicht gemessen, und warum".
 */
async function anmelden(mail, pass) {
  await seite.goto(BASIS + '/logout.php', { waitUntil: 'domcontentloaded' });
  await seite.goto(BASIS + '/login.php', { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pass);
  await seite.click('#loginform button[type="submit"]');
  /* NICHT MEHR `waitForURL(… !login.php)` (P5c/AP5, E-P5c-43). Ein Konto
   * mit Zweitfaktor steht nach dem Passwort WIEDER unter `login.php` und
   * wird nach dem Code gefragt; `nachDemPasswort()` (motor.mjs) geht diesen
   * Schritt und meldet das Einrichtungstor als Scheitern. Die beiden Konten
   * dieses Laufs (`umlauf-csv@…`, Demo) haben keinen Zweitfaktor — der
   * Schritt steht hier, damit ein anderes Konto nicht 120 Sekunden ins
   * Leere wartet.
   *
   * Auf `'load'` wartet auch dieser Weg nicht: Er sieht auf das Dokument,
   * nicht auf die Kartenkacheln von `tile.openstreetmap.org`, die hinter dem
   * Egress-Filter nie ankommen (daran blieb die Demo-Anmeldung einmal
   * stehen, obwohl die Seite laengst da war). */
  const a = await nachDemPasswort(seite, { frist: 120000 });
  if (a.angemeldet) { return null; }
  /* Die Meldung der Seite zuerst — sie traegt die Mengenbremse des
   * Demo-Kontos im Wortlaut; die des Motors, wo die Seite keine hat. */
  const text = await seite.evaluate(() =>
    (document.querySelector('.meldung-fehler p, .meldung p, [data-msg]')
      || {}).textContent || '').catch(() => '');
  return (text || '').trim() || a.meldung || 'Anmeldung ohne Meldung fehlgeschlagen';
}

console.log(`Umstellungslauf — Motor ${motor}`);

/* ---- Voraussetzung herstellen: die Hülle zurück auf `edk1:` ------------- */
{
  const r = spawnSync('python3',
    ['tools/proben/anteil/huelle_stellen.py', KONTO, KONTO_PW, 'edk1'],
    { cwd: '/home/user/einsatzdoku-luftrettung', encoding: 'utf8' });
  process.stdout.write('  ' + (r.stdout || r.stderr || '').trim() + '\n');
  if (r.status !== 0) {
    console.error('Die Voraussetzung liess sich nicht herstellen — Abbruch.');
    await browser.close();
    process.exit(2);
  }
}

/* ---- 1. Erstes Anmelden: die Umstellung läuft still -------------------- */
console.log('\n1. Erstes Anmelden (Hülle ist edk1:)');
upgrades = [];
{
  const fehler = await anmelden(KONTO, KONTO_PW);
  if (fehler) { console.error('  Anmeldung abgewiesen: ' + fehler); await browser.close(); process.exit(2); }
}
// Eine Seite, die den Inhaltsschlüssel braucht — sie löst das Vormerkfach auf.
await oeffne(seite, '/suche.php');
await seite.waitForTimeout(1500);
pruefe('kdf_upgrade.php wurde gerufen', upgrades.length >= 1, true);
pruefe('und mit 200 beantwortet', upgrades.every(s => s === 200), true);
pruefe('kein Entsperrdialog', await seite.locator('dialog.dialog').count(), 0);
pruefe('0 Fehler aus der Anwendung', konsole, []);
console.log(`  (dazu ${umfeld.length} Fehler des Prüfstands: Kartenkacheln von `
  + `tile.openstreetmap.org und abgebrochene Anfragen beim Seitenwechsel — `
  + `beides Eigenschaften des Containers, nicht der Seite)`);

/* ---- 2. Zweites Anmelden: nichts mehr zu tun --------------------------- */
console.log('\n2. Zweites Anmelden (Hülle ist jetzt edka1:)');
upgrades = [];
{
  const fehler = await anmelden(KONTO, KONTO_PW);
  if (fehler) { console.error('  Anmeldung abgewiesen: ' + fehler); await browser.close(); process.exit(2); }
}
await oeffne(seite, '/suche.php');
await seite.waitForTimeout(1500);
pruefe('kdf_upgrade.php wird NICHT mehr gerufen', upgrades.length, 0);
pruefe('kein Entsperrdialog', await seite.locator('dialog.dialog').count(), 0);

/* ---- 3. Die Daten sind lesbar ------------------------------------------ */
/* DIE EIGENTLICHE FRAGE DIESES PAKETS. Eine Umstellung, nach der die
 * geschützten Angaben nicht mehr aufgehen, wäre ein Totalverlust — und sie
 * sähe von außen genauso aus wie eine gelungene, solange niemand hinsieht.
 * Gemessen wird deshalb der ENTSCHLÜSSELTE Inhalt, nicht der Statuscode. */
console.log('\n3. Geschützte Angaben nach der Umstellung');
await oeffne(seite, '/suche.php');
await seite.waitForTimeout(1500);
const lesbar = await seite.evaluate(async () => {
  const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
  if (!ck) { return { ck: false }; }
  const r = await fetch('api/suchindex.php', { credentials: 'same-origin' });
  const j = await r.json().catch(() => null);
  const liste = (j && (j.missions || j.eintraege || j.items)) || [];
  let mitBlob = 0, geoeffnet = 0;
  for (const m of liste) {
    const blob = m.pat_blob || m.blob;
    if (!blob) { continue; }
    mitBlob++;
    try { JSON.parse(await EdCrypto.decrypt(ck, blob)); geoeffnet++; } catch (e) {}
  }
  return { ck: true, schluessel: Object.keys(j || {}), gesamt: liste.length,
           mitBlob, geoeffnet };
});
pruefe('der Inhaltsschlüssel liegt vor', lesbar.ck, true);
console.log(`  gemessen: ${lesbar.gesamt} Einträge, davon ${lesbar.mitBlob} mit `
  + `verschlüsseltem Block, davon ${lesbar.geoeffnet} geöffnet `
  + `(Antwortfelder: ${JSON.stringify(lesbar.schluessel)})`);
pruefe('jeder verschlüsselte Block lässt sich öffnen',
       lesbar.mitBlob > 0 && lesbar.geoeffnet === lesbar.mitBlob, true);

/* ---- 4. Die HKDF-Dauer messen (die Zahl aus dem Konzept) --------------- */
console.log('\n4. Dauer der zusätzlichen HKDF-Ableitung');
const dauer = await seite.evaluate(async () => {
  const haelfte = EdCrypto.randomHex(32);
  const kennung = ANTEIL_KENNUNG;
  const anteile = KONTO_ANTEILE;
  // einmal warmlaufen, dann 50 Messungen
  await EdCrypto.datenschluesselZu(haelfte, kennung, anteile);
  /* ALS BLOCK GEMESSEN, NICHT JE ABLEITUNG. `performance.now()` ist in
   * Chromium und Firefox gegen Seitenkanäle gerastert (0,1 ms bzw. gröber);
   * eine Einzelmessung unterhalb des Rasters ergibt 0,000 ms und sagt nur,
   * dass sie kleiner ist als die Uhr. 500 Ableitungen am Stück liegen weit
   * darüber, und die Zahl je Ableitung ist dann ein echter Mittelwert. */
  const N = 500;
  const a = performance.now();
  for (let i = 0; i < N; i++) {
    await EdCrypto.datenschluesselZu(haelfte, kennung, anteile);
  }
  const gesamt = performance.now() - a;
  return { median: gesamt / N, gesamt, n: N };
});
console.log(`  ${dauer.n} Ableitungen in ${dauer.gesamt.toFixed(1)} ms `
  + `= ${dauer.median.toFixed(4)} ms je Ableitung`);
pruefe('unter 5 ms je Ableitung', dauer.median < 5, true);

/* ---- 5. Entsperrdialog in einem neuen Tab ------------------------------ */
console.log('\n5. Entsperrdialog öffnet die edka1:-Hülle');
const tab = await ctx.newPage();
await tab.addInitScript(() => {
  try { sessionStorage.clear(); } catch (e) {}
});
await oeffne(tab, '/suche.php');
await tab.waitForTimeout(800);
const dlg = tab.locator('dialog.dialog');
pruefe('der Dialog erscheint', await dlg.count(), 1);
if (await dlg.count()) {
  await tab.fill('dialog.dialog input[type="password"]', KONTO_PW);
  await tab.click('dialog.dialog [data-act="yes"]');
  await tab.waitForTimeout(2500);
  pruefe('nach richtigem Passwort ist der Dialog zu',
         await tab.locator('dialog.dialog[open]').count(), 0);
}
/* DEN ZWEITEN TAB SCHLIESSEN, UND ZWAR SOFORT. Der Prüfstand fährt gegen
 * `php -S`, und der bedient EINE Anfrage zur Zeit. Ein zweiter offener Tab
 * mit einer Karte hält die Leitung besetzt; der nächste Schritt wartete
 * daraufhin minutenlang auf eine Seite, die einzeln in 1,7 Sekunden da ist
 * (gemessen 14.09.2026). Das sah aus wie ein Fehler der Anmeldung und war
 * einer des Prüfmittels. */
await tab.close();

/* ---- 6. Demo-Konto bleibt auf edk1: ------------------------------------ */
console.log('\n6. Demo-Konto');
upgrades = [];
const demoFehler = await anmelden('demo@gen-em.org', 'nadokudemo0815');
if (demoFehler) {
  /* NICHT GEMESSEN, UND WARUM — statt eines roten Hakens für etwas, das
   * die Anwendung richtig macht. Die Mengenbremse des Demo-Kontos
   * (E-P1-20) lässt 20 Anmeldungen je Stunde zu; wer diesen Lauf mehrfach
   * hintereinander fährt, löst sie aus. Das ist kein Fehler der
   * Umstellung, und es als einen zu zählen wäre eine falsche Zahl. */
  console.log(`  NICHT GEMESSEN — ${demoFehler}`);
  console.log('  (Die Mengenbremse des Demo-Kontos, E-P1-20: 20 Anmeldungen '
    + 'je Stunde. Sie löst sich von selbst; der Demo-Teil misst beim '
    + 'nächsten Lauf wieder.)');
  nichtGemessen += 5;
} else {
await oeffne(seite, '/suche.php');
await seite.waitForTimeout(1500);
const demoStand = await seite.evaluate(() => ({
  stand: typeof ANTEIL_STAND !== 'undefined' ? ANTEIL_STAND : null,
  anteile: typeof KONTO_ANTEILE !== 'undefined' ? KONTO_ANTEILE : 'undef',
  wrap: typeof PAT_WRAP !== 'undefined' ? String(PAT_WRAP).slice(0, 5) : null,
}));
pruefe('ANTEIL_STAND ist demo', demoStand.stand, 'demo');
pruefe('KONTO_ANTEILE ist null', demoStand.anteile, null);
pruefe('die Hülle bleibt edk1:', demoStand.wrap, 'edk1:');
pruefe('der Endpunkt meldet uebersprungen (200)',
       upgrades.every(s => s === 200), true);
pruefe('kein Entsperrdialog', await seite.locator('dialog.dialog').count(), 0);
}

await browser.close();
console.log(`\nErgebnis (${motor}): ${ok} von ${ok + offen} erfüllt, ${offen} offen`
  + (nichtGemessen ? `, ${nichtGemessen} nicht gemessen (Grund oben).` : '.'));
console.log(`HKDF je Ableitung: ${dauer.median.toFixed(4)} ms `
  + `(${dauer.n} Stück in ${dauer.gesamt.toFixed(1)} ms)`);
process.exit(offen === 0 ? 0 : 1);
