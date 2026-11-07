/* Favicons der Produktmarke aus dem Branding-Repo holen, Raster erzeugen.
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
 * WAS GEHOLT WIRD, je Zeichen (Hubschrauber, NEF), seit Repo-Commit
 * e7bf4fc aus `1 Logos/Produktmarken/NAdoku/Favicon/<hubschrauber|nef>/`
 * (dort erzeugt von `9 Technik/Werkzeuge/erzeuge_favicons.py`; das Zeichen
 * fuellt 92 % der Breite):
 *   favicon.svg          -> favicon-<stamm>.svg
 *   favicon-32.png       -> favicon-<stamm>-32.png
 *   apple-touch-icon.png -> apple-touch-<stamm>.png   (180 px auf Schnee)
 * und einmal `favicon.ico` des Hubschraubers -> `server/favicon.ico` (16,
 * 32, 48 px, der Standard der Installation). Bis dahin zeichnete dieses
 * Skript die Favicons selbst nach dem Rezept des Repos; zwei Erzeuger fuer
 * dieselben Dateien laufen frueher oder spaeter auseinander (E-MA-35).
 * Nicht geholt: `icon-192/512`, `icon-maskable-512` und `site.webmanifest`
 * — sie gehoeren zum Manifest, und das kommt mit R70 in Schritt 13.
 *
 * WAS ENTSTEHT, je Zeichen:
 *   <stamm>-farbig.png,        500 px breit — die Quelle der Bildmarken der
 *   <stamm>-farbig-negativ.png Android-App (`android/werkzeuge/bildmarken.sh`;
 *                              die App nennt die dunkle Fassung „weiss", wie
 *                              das Web bis Schritt 26)
 *
 * WANN LAUFEN LASSEN. Immer, wenn eine Datei der Produktmarke sich aendert
 * oder das Repo nachgezogen wird.
 *
 * AUFRUF
 *   BRANDING_REPO=/pfad/zu/gen-em-branding node tools/erzeugen/logos.mjs
 * (Vorgabe: `gen-em-branding` neben diesem Repositorium.)
 *
 * Chromium kommt aus Playwright; ein eigener Bildkonverter waere eine weitere
 * Abhaengigkeit fuer eine Aufgabe, die der Browser ohnehin kann und den die
 * Pruefwerkzeuge ohnehin mitbringen.
 */
import { copyFileSync, existsSync, readFileSync, statSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const HIER   = dirname(fileURLToPath(import.meta.url));
const SERVER = join(HIER, '..', '..', 'server');
const BILDER = join(SERVER, 'assets', 'images');
const STAEMME = ['nadoku-hubschrauber', 'nadoku-nef'];
const REPO = process.env.BRANDING_REPO || join(HIER, '..', '..', '..', 'gen-em-branding');
const FAVICON = join(REPO, '1 Logos', 'Produktmarken', 'NAdoku', 'Favicon');
if (!existsSync(FAVICON)) {
  console.error(`Branding-Repo nicht gefunden: ${FAVICON}\nBRANDING_REPO setzen. Es wurde NICHTS geschrieben.`);
  process.exit(1);
}

function hole(quelle, ziel) {
  copyFileSync(quelle, ziel);
  console.log(`${ziel.replace(SERVER + '/', 'server/')}  (${statSync(ziel).size} Bytes, aus dem Repo)`);
}
for (const stamm of STAEMME) {
  const set = join(FAVICON, stamm === 'nadoku-nef' ? 'nef' : 'hubschrauber');
  hole(join(set, 'favicon.svg'), join(BILDER, `favicon-${stamm}.svg`));
  hole(join(set, 'favicon-32.png'), join(BILDER, `favicon-${stamm}-32.png`));
  hole(join(set, 'apple-touch-icon.png'), join(BILDER, `apple-touch-${stamm}.png`));
}
hole(join(FAVICON, 'hubschrauber', 'favicon.ico'), join(SERVER, 'favicon.ico'));

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

for (const stamm of STAEMME) {
  for (const fassung of ['farbig', 'farbig-negativ']) {
    const svg = readFileSync(join(BILDER, `${stamm}-${fassung}.svg`), 'utf8');
    const vb = /viewBox="([^"]+)"/.exec(svg)[1].split(/[\s,]+/).map(Number);
    schreibe(join(BILDER, `${stamm}-${fassung}.png`),
             await raster(svg, 500, Math.round(500 * vb[3] / vb[2]), `${stamm}-${fassung}`));
  }
}
await browser.close();
