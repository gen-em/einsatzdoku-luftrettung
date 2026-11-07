/* Favicons und Raster der Produktmarke aus den Vektorvorlagen erzeugen.
 * ===========================================================================
 *
 * WOFUER. Die Anwendung liefert die Produktmarke NAdoku in drei Fassungen
 * als SVG aus (farbig, farbig-negativ, weiss — Branding-Repo
 * `gen-em/gen-em-branding`, `1 Logos/Produktmarken/NAdoku/`, seit
 * Schritt 26). Alles Uebrige ist ABLEITUNG und soll keine sein, die jemand
 * in einem Bildprogramm nachbaut — sonst driften Zeichen und Favicon
 * auseinander, sobald eine Farbe sich aendert. Genau das war einmal der
 * Fall: Die Logodateien trugen Naeherungen der Markenfarben, und das Favicon
 * trug sie mit (Backlog Nr. 62).
 *
 * WAS ENTSTEHT, je Zeichen (Hubschrauber, NEF):
 *   favicon-<stamm>.svg        das farbige Zeichen mittig in einem Quadrat,
 *                              durchsichtig — fuer Browser, die SVG nehmen
 *   favicon-<stamm>-32.png     dasselbe in 32 px
 *   apple-touch-<stamm>.png    180 px auf Schnee, Zeichen 70 % breit (iOS
 *                              rundet selbst ab)
 *   <stamm>-farbig.png,        500 px breit — die Quelle der Bildmarken der
 *   <stamm>-farbig-negativ.png Android-App (`android/werkzeuge/bildmarken.sh`;
 *                              die App nennt die dunkle Fassung „weiss", wie
 *                              das Web bis Schritt 26)
 * und einmal `server/favicon.ico` (16, 32, 48 px, Hubschrauber, der
 * Standard der Installation).
 *
 * Das Rezept folgt `9 Technik/Werkzeuge/erzeuge_favicons.py` des
 * Branding-Repos: farbig auch bei 16 px (Entscheidung der Betreiberin,
 * 06.11.2026). Nicht erzeugt werden die 192- und 512-px-Symbole fuer ein
 * Web-App-Manifest — das kommt mit R70 in Schritt 13.
 *
 * WANN LAUFEN LASSEN. Immer, wenn eine Datei der Produktmarke sich aendert.
 *
 * AUFRUF
 *   node tools/erzeugen/logos.mjs
 *
 * Chromium kommt aus Playwright; ein eigener Bildkonverter waere eine weitere
 * Abhaengigkeit fuer eine Aufgabe, die der Browser ohnehin kann und den die
 * Pruefwerkzeuge ohnehin mitbringen. Die .ico schreibt dieses Skript selbst:
 * Sie ist ein Verzeichnis von PNG-Bildern mit einem Kopf von 6 + 16 Byte je
 * Bild.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const HIER   = dirname(fileURLToPath(import.meta.url));
const SERVER = join(HIER, '..', '..', 'server');
const BILDER = join(SERVER, 'assets', 'images');
const SCHNEE = '#fffcfa';
const STAEMME = ['nadoku-hubschrauber', 'nadoku-nef'];

/* Das Zeichen mittig in ein 1000er-Quadrat, `anteil` der Kante breit oder
 * hoch — das groessere Mass entscheidet. Der XML-Kommentar der Vorlage
 * faellt weg: Er erklaert die Vorlage, nicht das Favicon. */
function quadrat(quelle, anteil, grund) {
  const text = readFileSync(join(BILDER, quelle), 'utf8').replace(/<!--[\s\S]*?-->/g, '');
  const vb = /viewBox="([^"]+)"/.exec(text)[1];
  const [, , w, h] = vb.split(/[\s,]+/).map(Number);
  const s = 1000;
  const breite = w >= h ? s * anteil : s * anteil * w / h;
  const hoehe  = w >= h ? s * anteil * h / w : s * anteil;
  const innen = text.replace(/^[\s\S]*?<svg[^>]*>/, '').replace(/<\/svg>\s*$/, '');
  const rahmen = grund ? `<rect width="${s}" height="${s}" fill="${grund}"/>` : '';
  return `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ${s} ${s}">${rahmen}`
    + `<svg x="${((s - breite) / 2).toFixed(1)}" y="${((s - hoehe) / 2).toFixed(1)}"`
    + ` width="${breite.toFixed(1)}" height="${hoehe.toFixed(1)}" viewBox="${vb}">${innen}</svg></svg>\n`;
}

const browser = await chromium.launch();
const seite = await browser.newPage({ deviceScaleFactor: 1 });

/* Ein SVG-Text als PNG in breite x hoehe. NACHSEHEN, OB DAS BILD
 * UEBERHAUPT GELADEN HAT: Ein XML-Kommentar mit `--` darin macht eine SVG
 * ungueltig, der Browser zeigt sein Platzhalterbild, und ein Werkzeug, das
 * es fotografiert und schreibt, ist schlimmer als eines, das nichts tut. */
async function raster(svgText, breite, hoehe, name) {
  await seite.setViewportSize({ width: breite, height: hoehe });
  const daten = 'data:image/svg+xml;base64,' + Buffer.from(svgText).toString('base64');
  await seite.setContent(
    `<body style="margin:0;width:${breite}px;height:${hoehe}px">` +
    `<img src="${daten}" style="width:100%;height:100%;object-fit:contain">` +
    `</body>`);
  await seite.waitForTimeout(150);
  const geladen = await seite.evaluate(() => {
    const i = document.querySelector('img');
    return i.complete && i.naturalWidth > 0;
  });
  if (!geladen) {
    throw new Error(name + ': liess sich nicht laden — vermutlich keine '
      + 'gueltige SVG. Es wurde NICHTS geschrieben.');
  }
  return seite.screenshot({ omitBackground: true });
}

function schreibe(datei, inhalt) {
  writeFileSync(datei, inhalt);
  console.log(`${datei.replace(SERVER + '/', 'server/')}  (${inhalt.length} Bytes)`);
}

const ico = [];
for (const stamm of STAEMME) {
  const fav = quadrat(`${stamm}-farbig.svg`, 0.96, null);
  schreibe(join(BILDER, `favicon-${stamm}.svg`), fav);
  schreibe(join(BILDER, `favicon-${stamm}-32.png`), await raster(fav, 32, 32, stamm));
  schreibe(join(BILDER, `apple-touch-${stamm}.png`),
           await raster(quadrat(`${stamm}-farbig.svg`, 0.70, SCHNEE), 180, 180, stamm));
  for (const fassung of ['farbig', 'farbig-negativ']) {
    const svg = readFileSync(join(BILDER, `${stamm}-${fassung}.svg`), 'utf8');
    const vb = /viewBox="([^"]+)"/.exec(svg)[1].split(/[\s,]+/).map(Number);
    schreibe(join(BILDER, `${stamm}-${fassung}.png`),
             await raster(svg, 500, Math.round(500 * vb[3] / vb[2]), `${stamm}-${fassung}`));
  }
  if (stamm === 'nadoku-hubschrauber') {
    for (const g of [16, 32, 48]) { ico.push([g, await raster(fav, g, g, stamm)]); }
  }
}
await browser.close();

/* ICO: Kopf (reserviert, Typ 1, Anzahl), je Bild ein Eintrag mit Breite,
 * Hoehe, Farben, reserviert, Ebenen, Bittiefe, Groesse und Versatz, dann
 * die PNG-Daten hintereinander. */
const kopf = Buffer.alloc(6 + 16 * ico.length);
kopf.writeUInt16LE(0, 0); kopf.writeUInt16LE(1, 2); kopf.writeUInt16LE(ico.length, 4);
let versatz = kopf.length;
ico.forEach(([g, png], i) => {
  const e = 6 + 16 * i;
  kopf.writeUInt8(g, e); kopf.writeUInt8(g, e + 1); kopf.writeUInt8(0, e + 2); kopf.writeUInt8(0, e + 3);
  kopf.writeUInt16LE(1, e + 4); kopf.writeUInt16LE(32, e + 6);
  kopf.writeUInt32LE(png.length, e + 8); kopf.writeUInt32LE(versatz, e + 12);
  versatz += png.length;
});
schreibe(join(SERVER, 'favicon.ico'), Buffer.concat([kopf, ...ico.map(([, p]) => p)]));
