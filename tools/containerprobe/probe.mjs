/* Containerprobe — haelt Fassung 4 gegen drei unabhaengige Umsetzungen.
 * ===========================================================================
 *
 * WOFUER (S2/AP5, E-S2-10). Fassung 4 zerlegt die Sicherung in versiegelte
 * Teile. Drei Dinge daran koennen schiefgehen, ohne dass es auffaellt:
 *
 *   1. Die Teile gehen wieder auf — aber nur in dem Browser, der sie
 *      geschrieben hat. Ein Werkzeug, das zum Handoeffnen taugt, gibt es
 *      dann nicht mehr.
 *   2. Die Spur kommt veraendert zurueck. Ein Blob ist Binaerinhalt; eine
 *      verschobene Zahl sieht darin aus wie jede andere.
 *   3. Die Bindung der Teile (AAD) haelt nicht. Ein fremdes oder
 *      vertauschtes Teil entsiegelt dann klaglos — mit demselben Passwort
 *      geht es ja auf —, und der Bestand eines anderen Kontos landet hier.
 *
 * DREI UMSETZUNGEN, EINE WAHRHEIT. Genau wie die GPX-Probe (AP4) haelt diese
 * Probe unabhaengige Wege gegeneinander:
 *
 *   PHP      `spur_lib.php` kodiert echte SPUR1-Blobs (spuren_bauen.php)
 *   Browser  `assets/crypto.js` + `assets/vendor/zipjs.min.js` versiegeln
 *            und packen — im ECHTEN Chromium, nicht in einem Nachbau
 *   Python   `vergleich/lesen.py` oeffnet und dekodiert wieder
 *
 * Stimmen alle drei ueberein, ist das mehr wert als jede Selbstprobe: Kein
 * Weg kann seinen eigenen Fehler bestaetigen.
 *
 * Aufruf:
 *   php -S 127.0.0.1:8080 -t server   (oder die uebliche Testinstallation)
 *   node tools/containerprobe/probe.mjs [basisadresse]
 *
 * Rueckgabewert: 0 = alle Erwartungen erfuellt, 1 = mindestens eine nicht.
 */
import { execFileSync } from 'node:child_process';
import { mkdtempSync, writeFileSync, rmSync } from 'node:fs';
import { tmpdir } from 'node:os';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = dirname(dirname(HIER));
const BASIS  = (process.argv[2] || 'http://127.0.0.1:8080').replace(/\/$/, '');
const PASSWORT = 'Containerprobe-2026!';
const RUNDEN = 310000;

let erwartungen = 0, offen = 0;
function pruefe(ok, was, wert = '') {
  erwartungen++;
  if (!ok) offen++;
  console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was.padEnd(56)} ${wert}`);
}

console.log(`Containerprobe gegen ${BASIS}`);

/* ---- Prueffutter aus PHP -------------------------------------------------- */

const futterRoh = execFileSync('php',
  [join(HIER, 'spuren_bauen.php'), '6', '300'], { encoding: 'utf8', maxBuffer: 1 << 28 });
const futter = JSON.parse(futterRoh);
console.log(`  Prueffutter aus spur_lib.php: ${futter.spuren.length} Spuren `
          + `zu je ${futter.punkte['1'].length} Punkten`);

const ordner = mkdtempSync(join(tmpdir(), 'containerprobe-'));
const browser = await chromium.launch();

try {
  const seite = await browser.newPage();
  /* Auf der echten Installation und nicht auf about:blank: `crypto.subtle`
     gibt es nur in einem sicheren Kontext, und die Skripte sollen so geladen
     werden, wie die Anwendung sie laedt. */
  const antwort = await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  pruefe(antwort && antwort.status() === 200, 'Die Testinstallation antwortet',
         'HTTP ' + (antwort ? antwort.status() : '—'));
  await seite.addScriptTag({ url: '/assets/crypto.js' });
  await seite.addScriptTag({ url: '/assets/vendor/zipjs.min.js' });
  pruefe(await seite.evaluate(() => typeof EdCrypto === 'object' && typeof zip === 'object'),
         'crypto.js und zip.js sind geladen');

  /* ---- Teil 1 — Versiegeln, Packen, und die Bindung der Teile ------------ */

  console.log('\n  Teil 1 — Versiegeln und die Bindung der Teile (Chromium)');

  const erg = await seite.evaluate(async ({ futter, pw, runden }) => {
    const te = new TextEncoder();
    const berichte = {};

    /* EINE PBKDF2 JE VORGANG: einmal ableiten, alle Teile damit versiegeln. */
    const t0 = performance.now();
    const vorgang = await EdCrypto.backupSchluessel(pw, runden);
    berichte.ableitung_ms = Math.round(performance.now() - t0);
    const kennung = EdCrypto.randomHex(16);

    /* Der Kern: die Nutzlast ohne Punktlisten, je Spur nur ihr Verweis. */
    const kern = {
      format: 'einsatzdoku-backup', version: 8, app: 'einsatzdoku-notarzt',
      created_at: '2026-08-31T12:00:00+00:00',
      user: { email: 'probe@gen-em.org', name: 'Containerprobe' },
      missions: futter.spuren.slice(0, 4).map((s, i) => ({
        client_ref: 'probe-m' + i, started_at: '2026-03-01T06:00:00',
        spur_ref: s.spur_ref, stufe: s.stufe, n_original: s.n_original, n: s.n,
      })),
      rest_segments: futter.spuren.slice(4).map((s, i) => ({
        client_ref: 'probe-r' + i, started_at: '2026-03-01T08:00:00',
        spur_ref: s.spur_ref, stufe: s.stufe, n_original: s.n_original, n: s.n,
      })),
    };

    /* Zwei Spurteile, damit „vertauscht" ueberhaupt einen Fall hat. */
    const spurteile = [
      { spuren: futter.spuren.slice(0, 3) },
      { spuren: futter.spuren.slice(3) },
    ];

    const teile = [];
    const rohe = {};
    let nr = 1;
    const gesamt = 1 + spurteile.length;

    const kernBytes = await EdCrypto.sealTeilJson(vorgang, kern,
      EdCrypto.aadTeil(kennung, 'kern.edbak', nr, gesamt));
    teile.push({ name: 'kern.edbak', art: 'kern',
                 sha256: await EdCrypto.sha256Hex(kernBytes) });
    rohe['kern.edbak'] = kernBytes;
    nr++;

    for (const [i, t] of spurteile.entries()) {
      const name = 'spuren/' + String(i + 1).padStart(4, '0') + '.edbak';
      const b = await EdCrypto.sealTeilJson(vorgang, t,
        EdCrypto.aadTeil(kennung, name, nr, gesamt));
      teile.push({ name, art: 'spuren', sha256: await EdCrypto.sha256Hex(b) });
      rohe[name] = b;
      nr++;
    }

    const manifest = {
      format: 'einsatzdoku-backup-manifest', fassung: 4, kennung,
      erzeugt_am: '2026-08-31T12:00:00+00:00', web_version: '11.0.0',
      teile, spurteile: spurteile.length, pat_key_check: null,
    };
    const manifestBytes = await EdCrypto.sealTeilJson(vorgang, manifest,
      EdCrypto.aadManifest());

    /* ---- Rundlauf im Browser selbst ---- */
    const kernZurueck = await EdCrypto.openTeilJson(vorgang, kernBytes,
      EdCrypto.aadTeil(kennung, 'kern.edbak', 1, gesamt), 'Der Kern');
    berichte.rundlauf_kern = JSON.stringify(kernZurueck) === JSON.stringify(kern);

    /* ---- Alle Teile tragen dasselbe Salz und dieselbe Rundenzahl ---- */
    const koepfe = [manifestBytes, ...Object.values(rohe)].map(b => EdCrypto.teilKopf(b));
    const salzHex = k => [...k.salt].map(x => x.toString(16).padStart(2, '0')).join('');
    berichte.ein_salz = new Set(koepfe.map(salzHex)).size === 1;
    berichte.eine_rundenzahl = new Set(koepfe.map(k => k.iter)).size === 1;
    berichte.fassung = new Set(koepfe.map(k => k.fassung)).size === 1 ? koepfe[0].fassung : -1;

    /* ---- Die Bindung: vertauscht, fremd, verfaelscht ---- */
    async function scheitert(fn) {
      try { await fn(); return null; } catch (e) { return e.message; }
    }
    const nameA = 'spuren/0001.edbak', nameB = 'spuren/0002.edbak';
    berichte.vertauscht = await scheitert(() => EdCrypto.openTeilJson(
      vorgang, rohe[nameA], EdCrypto.aadTeil(kennung, nameB, 3, gesamt), 'Ein Spurteil'));
    berichte.falsche_nummer = await scheitert(() => EdCrypto.openTeilJson(
      vorgang, rohe[nameA], EdCrypto.aadTeil(kennung, nameA, 3, gesamt), 'Ein Spurteil'));

    /* Ein Teil aus einer ZWEITEN Sicherung — dasselbe Passwort, andere
       Kennung. Ohne die Bindung ginge es klaglos auf. */
    const vorgang2 = await EdCrypto.backupSchluessel(pw, runden, vorgang.salt);
    const kennung2 = EdCrypto.randomHex(16);
    const fremdBytes = await EdCrypto.sealTeilJson(vorgang2, spurteile[0],
      EdCrypto.aadTeil(kennung2, nameA, 2, gesamt));
    berichte.fremd = await scheitert(() => EdCrypto.openTeilJson(
      vorgang, fremdBytes, EdCrypto.aadTeil(kennung, nameA, 2, gesamt), 'Ein Spurteil'));
    /* Gegenprobe: MIT seiner eigenen Kennung geht dasselbe Teil auf — der
       Unterschied liegt also wirklich an der Bindung und nicht am Schluessel. */
    berichte.fremd_mit_eigener_kennung = await scheitert(() => EdCrypto.openTeilJson(
      vorgang, fremdBytes, EdCrypto.aadTeil(kennung2, nameA, 2, gesamt), 'Ein Spurteil'));

    const verbogen = rohe[nameA].slice();
    verbogen[verbogen.length - 20] ^= 0x01;
    berichte.verfaelscht = await scheitert(() => EdCrypto.openTeilJson(
      vorgang, verbogen, EdCrypto.aadTeil(kennung, nameA, 2, gesamt), 'Ein Spurteil'));

    berichte.falsches_passwort = await scheitert(async () => {
      const v = await EdCrypto.backupSchluessel('etwas ganz anderes', runden, vorgang.salt);
      return EdCrypto.openTeilJson(v, rohe[nameA],
        EdCrypto.aadTeil(kennung, nameA, 2, gesamt), 'Ein Spurteil');
    });

    /* ---- Base64 fuer grosse Bytefolgen ---- */
    const gross = new Uint8Array(2 * 1024 * 1024);
    crypto.getRandomValues(gross.subarray(0, 65536));
    for (let i = 65536; i < gross.length; i++) { gross[i] = gross[i - 65536]; }
    const b64 = EdCrypto.toB64Gross(gross);
    const zurueck = EdCrypto.fromB64Gross(b64);
    berichte.b64_rundlauf = zurueck.length === gross.length
      && zurueck.every((v, i) => v === gross[i]);
    berichte.b64_laenge = b64.length;
    /* Und der alte Wandler daran — die Begruendung fuer den neuen. */
    berichte.b64_alt = await scheitert(async () => btoa(String.fromCharCode(...gross)));

    /* ---- Das ZIP: Speichern ohne Kompression ---- */
    const schreiber = new zip.Uint8ArrayWriter();
    const zw = new zip.ZipWriter(schreiber, { level: 0 });
    await zw.add('manifest.edbak', new zip.Uint8ArrayReader(manifestBytes), { level: 0 });
    for (const t of teile) {
      await zw.add(t.name, new zip.Uint8ArrayReader(rohe[t.name]), { level: 0 });
    }
    await zw.close();
    const zipBytes = await schreiber.getData();

    berichte.zip_magie = zipBytes[0] === 0x50 && zipBytes[1] === 0x4B;
    berichte.zip_groesse = zipBytes.length;
    berichte.roh_groesse = manifestBytes.length
      + teile.reduce((s, t) => s + rohe[t.name].length, 0);
    berichte.teile_zahl = teile.length + 1;

    /* ---- Formaterkennung ---- */
    berichte.art_zip  = EdCrypto.dateiArt(zipBytes);
    berichte.art_teil = EdCrypto.dateiArt(kernBytes);
    berichte.art_alt  = EdCrypto.dateiArt(await EdCrypto.sealBackup(pw, '{"a":1}', runden));
    berichte.art_fremd = EdCrypto.dateiArt(te.encode('irgendein anderer Dateiinhalt hier'));
    berichte.ist_backup_zip  = EdCrypto.isBackupFile(zipBytes);
    berichte.ist_backup_teil = EdCrypto.isBackupFile(kernBytes);
    berichte.teil_als_datei = await scheitert(() => EdCrypto.openBackup(pw, kernBytes));

    return { berichte, kennung, manifest,
             zipB64: EdCrypto.toB64Gross(zipBytes),
             fremdB64: EdCrypto.toB64Gross(fremdBytes) };
  }, { futter, pw: PASSWORT, runden: RUNDEN });

  const b = erg.berichte;
  pruefe(b.rundlauf_kern, 'Ein Teil geht in demselben Browser wieder auf');
  pruefe(b.fassung === 4, 'Jeder Teilkopf traegt die Fassung 4', 'Fassung ' + b.fassung);
  pruefe(b.ein_salz && b.eine_rundenzahl,
         'Alle Teile teilen Salz und Rundenzahl (eine PBKDF2 je Vorgang)',
         b.ableitung_ms + ' ms fuer die eine Ableitung');
  pruefe(!!b.vertauscht, 'Ein VERTAUSCHTES Teil wird abgewiesen',
         (b.vertauscht || '').slice(0, 46) + '…');
  pruefe(!!b.falsche_nummer, 'Dasselbe Teil unter falscher Nummer wird abgewiesen');
  pruefe(!!b.fremd, 'Ein Teil aus einer FREMDEN Sicherung wird abgewiesen');
  pruefe(b.fremd_mit_eigener_kennung === null,
         'Gegenprobe: mit seiner eigenen Kennung geht es auf',
         'der Unterschied liegt an der Bindung, nicht am Schluessel');
  pruefe(!!b.verfaelscht, 'Ein verfaelschtes Byte wird abgewiesen');
  pruefe(!!b.falsches_passwort, 'Ein falsches Passwort wird abgewiesen');
  pruefe(/Passwort/.test(b.vertauscht || '') && /vertauscht|fehlen|anderen/.test(b.vertauscht || ''),
         'Die Meldung nennt BEIDE Moeglichkeiten, nicht nur das Passwort');
  pruefe(b.b64_rundlauf, 'Base64 traegt 2 MB im Rundlauf',
         b.b64_laenge.toLocaleString('de-DE') + ' Zeichen');
  pruefe(b.b64_alt !== null,
         'Der alte Wandler scheitert daran (Begruendung fuer den neuen)',
         (b.b64_alt || 'er scheitert NICHT — dann ist der neue unnoetig').slice(0, 40));
  pruefe(b.zip_magie, 'Die Datei ist ein ZIP („PK")');
  /* OB WIRKLICH GESPEICHERT WIRD, sagt das Verfahren im Eintrag und nicht die
     Dateigroesse. Der erste Entwurf verglich sie („kleiner als die Summe der
     Teile") und schlug fehl: Bei drei Teilen zu 500 Byte ist der ZIP-Rahmen
     — lokaler Kopfsatz und zentrales Verzeichnis je Eintrag — groesser als
     jede Ersparnis. Die Zahl war richtig gerechnet und hat etwas anderes
     gemessen, als ihre Beschriftung sagte. Das Verfahren prueft Python
     unten, wo `zipfile` es unmittelbar hergibt; hier steht nur, was der
     Rahmen kostet. */
  console.log(`         (ZIP-Rahmen: ${b.zip_groesse} Byte fuer ${b.roh_groesse} Byte `
    + `Teile, ${b.zip_groesse - b.roh_groesse} Byte Verwaltung fuer `
    + `${b.teile_zahl} Eintraege)`);
  pruefe(b.art_zip === 'zip' && b.art_teil === 'teil' && b.art_alt === 'edbak'
         && b.art_fremd === null,
         'Die Formaterkennung unterscheidet vier Faelle',
         `${b.art_zip} / ${b.art_teil} / ${b.art_alt} / ${b.art_fremd}`);
  pruefe(b.ist_backup_zip && b.ist_backup_teil,
         'ZIP und Teil gelten als Datei dieser Anwendung');
  pruefe(/Teil einer mehrteiligen/.test(b.teil_als_datei || ''),
         'Ein einzeln geoeffnetes Teil sagt, dass es ein Teil ist',
         (b.teil_als_datei || '').slice(0, 46) + '…');

  /* ---- Die Datei auf die Platte, dann Python ----------------------------- */

  const zipBytes = Buffer.from(erg.zipB64, 'base64');
  writeFileSync(join(ordner, 'gut.edbak'), zipBytes);
  writeFileSync(join(ordner, 'fremdes_teil.bin'), Buffer.from(erg.fremdB64, 'base64'));
  writeFileSync(join(ordner, 'futter.json'), JSON.stringify(futter));
  console.log(`\n  Datei geschrieben: ${(zipBytes.length / 1024).toFixed(1)} kB`);

  console.log('\n  Teil 2 — Dieselbe Datei in Python (vergleich/lesen.py)');
  const py = execFileSync('python3',
    [join(HIER, 'lesen_pruefen.py'), ordner, PASSWORT],
    { encoding: 'utf8', cwd: WURZEL, maxBuffer: 1 << 28 });
  for (const zeile of py.split('\n')) {
    if (!zeile.trim()) continue;
    const m = zeile.match(/^\s*\[(ok |FEHL)\]/);
    if (m) { erwartungen++; if (m[1] === 'FEHL') offen++; }
    console.log(zeile.replace(/\s+$/, ''));
  }
} finally {
  await browser.close();
  rmSync(ordner, { recursive: true, force: true });
}

console.log(`\n  -> ${erwartungen} Erwartungen, ${offen} nicht erfuellt`);
process.exit(offen === 0 ? 0 : 1);
