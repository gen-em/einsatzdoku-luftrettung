/* Favicons aus den Logodateien erzeugen.
 * ===========================================================================
 *
 * WOFUER. Die Anwendung liefert je Logo drei Fassungen aus: farbig (SVG),
 * weiss (SVG, fuer die dunkle Kopfleiste) und Favicon (PNG, 64 x 64). Die
 * beiden SVG werden von Hand gepflegt; das PNG ist eine ABLEITUNG und soll
 * keine sein, die jemand in einem Bildprogramm nachbaut — sonst driften
 * Logo und Favicon auseinander, sobald eine Farbe sich aendert.
 *
 * Genau das war der Fall: Die Logodateien trugen bis P3 Naeherungen der
 * Markenfarben (Rot E3322B statt D63338, Blau 587ABC statt 4280E5, Orange
 * F7941D statt FF8F1F), und das Favicon trug sie mit. Die verbindlichen
 * Markenwerte stehen in docs/Design.md, Abschnitt 2.
 *
 * WANN LAUFEN LASSEN. Immer, wenn eine Logodatei sich aendert — insbesondere,
 * wenn Philipp das echte NEF-Logo liefert und den Platzhalter ersetzt
 * (Konzept P3, 10.5).
 *
 * AUFRUF
 *   node tools/logos/erzeugen.mjs
 *
 * Erzeugt aus jeder Quelldatei ein PNG mit durchsichtigem Grund, in das die
 * Zeichnung vollstaendig hineinpasst (object-fit: contain) — dieselbe Bauform
 * wie das bisherige favicon.png.
 *
 * Chromium kommt aus Playwright; ein eigener Bildkonverter (ImageMagick,
 * rsvg) waere eine weitere Abhaengigkeit fuer eine Aufgabe, die der Browser
 * ohnehin kann und den die Pruefwerkzeuge ohnehin mitbringen.
 */
import { readFileSync, writeFileSync } from 'node:fs';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const { chromium } = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);

const HIER   = dirname(fileURLToPath(import.meta.url));
const BILDER = join(HIER, '..', '..', 'server', 'assets', 'images');

const KANTE = 64;
// Namen wie die Dateien im Repositorium. Sie haben sich mit dem echten
// NEF-Logo geaendert (fahrzeug -> nef, favicon.png -> favicon_helicopter.png);
// dieses Werkzeug zeigte danach auf Dateien, die es nicht mehr gibt, und ein
// Aufruf brach mit ENOENT ab.
const AUFGABEN = [
  { quelle: 'gen-em_logo_helicopter.svg', ziel: 'favicon_helicopter.png' },
  { quelle: 'gen-em_logo_nef.svg',        ziel: 'favicon_nef.png' },
];

const browser = await chromium.launch();
const seite = await browser.newPage({
  viewport: { width: KANTE, height: KANTE },
  deviceScaleFactor: 1,
});

for (const { quelle, ziel } of AUFGABEN) {
  const svg = readFileSync(join(BILDER, quelle));
  const daten = 'data:image/svg+xml;base64,' + svg.toString('base64');
  await seite.setContent(
    `<body style="margin:0;width:${KANTE}px;height:${KANTE}px">` +
    `<img src="${daten}" style="width:100%;height:100%;object-fit:contain">` +
    `</body>`);
  await seite.waitForTimeout(150);
  /* NACHSEHEN, OB DAS BILD UEBERHAUPT GELADEN HAT (S3/AP11).
   *
   * Es hatte einmal nicht: Ein XML-Kommentar mit einem doppelten Bindestrich
   * darin macht die SVG ungueltig — XML verbietet `--` im Kommentar. Der
   * Browser zeigte daraufhin sein Platzhalterbild („kaputtes Bild"), dieses
   * Werkzeug fotografierte es, schrieb es als Favicon und meldete
   * zufrieden „64 x 64, 865 Bytes". Ein Werkzeug, das bei einem Fehlschlag
   * eine Datei schreibt, ist schlimmer als eines, das nichts tut. */
  const geladen = await seite.evaluate(() => {
    const i = document.querySelector('img');
    return i.complete && i.naturalWidth > 0;
  });
  if (!geladen) {
    throw new Error(quelle + ': liess sich nicht laden — vermutlich keine '
      + 'gueltige SVG (XML verbietet u. a. `--` im Kommentar). '
      + 'Es wurde NICHTS geschrieben.');
  }
  const bild = await seite.screenshot({ omitBackground: true });
  writeFileSync(join(BILDER, ziel), bild);
  console.log(`${quelle}  ->  ${ziel}  (${KANTE} x ${KANTE}, ${bild.length} Bytes)`);
}

await browser.close();
