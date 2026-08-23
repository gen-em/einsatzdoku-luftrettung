/* AK-A3-3: Erscheinungsbild unveraendert — im Browser nachgerechnet.
 *
 * Fuer jede Probe wird dieselbe DOM einmal mit dem alten und einmal mit dem
 * neuen Stylesheet geladen; danach werden fuer JEDES Element die berechneten
 * Werte ALLER Eigenschaften verglichen, die in style.css ueberhaupt
 * vorkommen. Gemessen wird bei mehreren Fensterbreiten, damit auch die
 * gesammelten Media Queries mitgeprueft werden.
 */
const { chromium } = require('playwright');
const fs = require('fs');
const path = require('path');

const SEP = String.fromCharCode(1);
const SP  = process.argv[2];          // Ordner mit fixtures/
const ALT = process.argv[3];
const NEU = process.argv[4];
const PROBEN = (process.env.PROBEN || 'seiten.html,katalog.html').split(',');
const BREITEN = [1400, 1100, 1000, 900, 720, 700, 560, 520, 500];

function eigenschaften(css) {
  const s = css.replace(/\/\*[\s\S]*?\*\//g, '');
  const set = new Set();
  for (const m of s.matchAll(/([-a-zA-Z]+)\s*:/g)) {
    const p = m[1].toLowerCase();
    if (p.startsWith('--')) continue;
    set.add(p);
  }
  return [...set].sort();
}

(async () => {
  const cssAlt = fs.readFileSync(ALT, 'utf8');
  const cssNeu = fs.readFileSync(NEU, 'utf8');
  const props = [...new Set([...eigenschaften(cssAlt), ...eigenschaften(cssNeu)])];

  const browser = await chromium.launch({ executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' });
  const seite = await browser.newPage();
  let abweichungen = 0, gemessen = 0;

  async function messen(html, css, breite) {
    await seite.setViewportSize({ width: breite, height: 900 });
    await seite.setContent(
      '<!doctype html><html lang="de"><head><meta charset="utf-8">' +
      '<style>' + css + '</style></head><body>' + html + '</body></html>',
      { waitUntil: 'load' });
    await seite.evaluate(() => { if (document.activeElement && document.activeElement.blur) document.activeElement.blur(); });
    return await seite.evaluate(([props, sep]) => {
      const out = [];
      const alle = document.querySelectorAll('*');
      for (let i = 0; i < alle.length; i++) {
        const cs = getComputedStyle(alle[i]);
        const z = [];
        for (const p of props) z.push(cs.getPropertyValue(p));
        out.push(z.join(sep));
      }
      return { werte: out, gruppe: [...alle].map(el => {
        const g = el.closest('[data-paar]'); return g ? g.getAttribute('data-paar') : ''; }), wer: [...alle].map(el =>
        el.tagName.toLowerCase() + (el.id ? '#' + el.id : '')
        + (el.className && typeof el.className === 'string' ? '.' + el.className.trim().split(/\s+/).join('.') : '')
        + ' <' + (el.parentElement ? el.parentElement.tagName.toLowerCase()
                  + (el.parentElement.className && typeof el.parentElement.className === 'string'
                     ? '.' + el.parentElement.className.trim().split(/\s+/).join('.') : '') : '-') + '>') };
    }, [props, SEP]);
  }

  for (const probe of PROBEN) {
    const html = fs.readFileSync(path.join(SP, 'fixtures', probe), 'utf8');
    for (const b of BREITEN) {
      const ra = await messen(html, cssAlt, b);
      const rn = await messen(html, cssNeu, b);
      const a = ra.werte, n = rn.werte, wer = ra.wer, grp = ra.gruppe;
      if (a.length !== n.length) {
        console.log('  XX ' + probe + ' @' + b + 'px: verschiedene Elementzahl');
        abweichungen++; continue;
      }
      let diff = 0; const beispiele = []; const gruppen = new Set();
      for (let i = 0; i < a.length; i++) {
        if (a[i] !== n[i]) {
          diff++; if (grp[i]) gruppen.add(grp[i]);
          if (beispiele.length < 8) {
            const va = a[i].split(SEP), vn = n[i].split(SEP);
            const wo = [];
            for (let k = 0; k < props.length; k++) {
              if (va[k] !== vn[k]) wo.push(props[k] + ': ' + va[k] + ' -> ' + vn[k]);
            }
            beispiele.push('Element #' + i + '  ' + wer[i] + '\n         ' + wo.slice(0, 4).join(' | '));
          }
        }
      }
      gemessen += a.length;
      if (diff) {
        abweichungen += diff;
        console.log('  XX ' + probe + ' @' + b + 'px: ' + diff + ' von ' + a.length + ' Elementen weichen ab');
        if (gruppen.size) { console.log('    betroffene Paare (' + gruppen.size + '):');
          [...gruppen].sort().forEach(function (x) { console.log('      * ' + x); }); }
        else beispiele.forEach(function (x) { console.log('       ' + x); });
      } else {
        console.log('  OK ' + probe + ' @' + b + 'px: ' + a.length + ' Elemente, kein Unterschied');
      }
    }
  }
  await browser.close();
  console.log('');
  console.log(gemessen + ' Elementmessungen, ' + abweichungen + ' Abweichungen, '
              + props.length + ' Eigenschaften je Element');
  process.exit(abweichungen ? 1 : 0);
})();
