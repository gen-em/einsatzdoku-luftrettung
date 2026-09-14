/* AK-A3-3: Erscheinungsbild unveraendert — im Browser nachgerechnet.
 *
 * Fuer jede Probe wird dieselbe DOM einmal mit dem alten und einmal mit dem
 * neuen Stylesheet geladen; danach werden fuer JEDES Element die berechneten
 * Werte ALLER Eigenschaften verglichen, die in style.css ueberhaupt
 * vorkommen. Gemessen wird bei mehreren Fensterbreiten, damit auch die
 * gesammelten Media Queries mitgeprueft werden.
 */
const PW = require('playwright');
const fs = require('fs');
const path = require('path');

const SEP = String.fromCharCode(1);
/* `--motor <name>` wird vor den Stellungsangaben herausgenommen, damit SP,
 * ALT und NEU an ihrer Stelle bleiben, gleich wo der Schalter steht. */
const ARGV = (() => {
  const a = process.argv.slice(2);
  const i = a.indexOf('--motor');
  if (i >= 0) { a.splice(i, 2); }
  return a;
})();
const SP  = ARGV[0];                  // Ordner mit fixtures/
const ALT = ARGV[1];
const NEU = ARGV[2];
const PROBEN = (process.env.PROBEN || 'seiten.html,katalog.html').split(',');
/* WIE VIELE ABWEICHENDE ELEMENTE AUSGESCHRIEBEN WERDEN (Vorgabe 8).
 *
 * Die Zahl stand fest verdrahtet, und das ist einmal teuer geworden: Ein Lauf
 * meldete „22 von 1502 Elementen weichen ab" und schrieb acht davon aus — die
 * acht, die sich aus der Hoehe des Dokuments erklaerten. Die vierzehn
 * uebrigen waren die eigentliche Aenderung (`a.zeile:hover` und Nachbarn) und
 * blieben ungesehen; wer nur die Ausgabe liest, haelt die Liste fuer
 * vollstaendig, weil nichts sagt, dass sie es nicht ist. Die Kopfzeile nennt
 * die richtige Zahl — die Beispielliste tut es seither auch:
 *   BEISPIELE=99 node stilvergleich.js …
 * (S9/AP5-2, 08.09.2026) */
const BEISPIELE = parseInt(process.env.BEISPIELE || '8', 10);
/* DREIZEHN BREITEN, NEU GEEICHT IN P3/O12.
 *
 * Die alten neun (1400, 1100, 1000, 900, 720, 700, 560, 520, 500) stammen aus
 * P0 und lagen um die damaligen Schwellen. Das Redesign hat andere: 720, 1024,
 * 1200 und 1600. Die Liste deckt jetzt jede davon von BEIDEN Seiten ab und
 * reicht bis 360 px hinunter — die schmalste Breite, die das Konzept zusagt.
 * Ohne 1024 und 1600 haette der Vergleich die halben Media-Bloecke nie
 * gesehen. */
const BREITEN = [1920, 1680, 1440, 1280, 1100, 1024, 900, 768, 720, 560, 420, 390, 360];

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
  /* Motorwahl und Firefox-Voreinstellung liegen in tools/motor.mjs — eine
   * Stelle fuer alle drei Pruefmittel. Diese Datei ist CommonJS, das Modul
   * ist ESM; der dynamische Import laeuft deshalb hier drin. */
  const { motorWahl, starten } = await import(
    require('url').pathToFileURL(path.join(__dirname, '..', 'motor.mjs')).href);
  const MOTOR = motorWahl(process.argv);

  const cssAlt = fs.readFileSync(ALT, 'utf8');
  const cssNeu = fs.readFileSync(NEU, 'utf8');
  const props = [...new Set([...eigenschaften(cssAlt), ...eigenschaften(cssNeu)])];

  /* DREI MOTOREN, EINE ZAHL JE MOTOR (AP3b, Nr. 183).
   *
   * Dieser Vergleich misst DIFFERENZIELL — alt gegen neu INNERHALB eines
   * Motors. Engine-Eigenheiten kuerzen sich darin heraus, und genau deshalb
   * ist der dreifache Lauf nicht dreimal dieselbe Null: Unterstuetzt ein
   * Motor eine neue Regel nicht, rechnet er „neu" wie „alt" und meldet
   * WENIGER Abweichungen. Die Aussage ist also nicht die Zahl, sondern ihre
   * UEBEREINSTIMMUNG ueber die drei Laeufe. Gemessen am 14.09.2026 an der
   * einen Regel aus AP3b: 46 150 Elementmessungen und 104 Abweichungen in
   * allen dreien — 8 Auswahlfelder mal 13 Breiten, und die einzige geaenderte
   * Eigenschaft ist `contain`. Je Motor 14 bis 18 Sekunden.
   *
   * `CHROMIUM=<pfad>` gilt weiter, aber nur fuer Chromium — Firefox und
   * WebKit nimmt Playwright aus PLAYWRIGHT_BROWSERS_PATH. */
  const auf = MOTOR === 'chromium'
    ? { executablePath: process.env.CHROMIUM || '/opt/pw-browsers/chromium-1194/chrome-linux/chrome' }
    : {};
  const browser = await starten(PW, MOTOR, { optionen: auf });
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
          if (beispiele.length < BEISPIELE) {
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
              + props.length + ' Eigenschaften je Element  [' + MOTOR + ']');
  process.exit(abweichungen ? 1 : 0);
})();
