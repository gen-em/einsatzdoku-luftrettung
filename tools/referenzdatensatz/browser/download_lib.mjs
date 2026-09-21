/* Auf einen Download warten — und sagen, was war, wenn keiner kommt.
 *
 * WARUM ES DAS GIBT. Am 21.09.2026 ist der Kreislauf `edbak` gegen Staging
 * nach 15 Minuten gescheitert, und das Protokoll sagte genau einen Satz:
 *
 *     page.waitForEvent: Timeout 900000ms exceeded while waiting for event "download"
 *
 * Mehr nicht. Nicht, ob der Export ueberhaupt angelaufen war; nicht, wie weit
 * er kam; nicht, ob der Browser einen Fehler geworfen hatte. Fuenfzehn Minuten
 * Messung, und als Ergebnis die Auskunft „es kam nichts" — die Frage, die
 * man danach stellt (LIEF er langsam oder HING er?), beantwortete das
 * Protokoll nicht. Dieser Fehler stand vorher hinter dem Botschutz von
 * lima-city und war deshalb noch nie zu sehen.
 *
 * `referenz_export.mjs` hatte die halbe Antwort schon: eine Funktion
 * `mitFortschritt()`, die den Zustandstext alle drei Sekunden mitliest. Sie
 * hat den Abbruch aber nicht abgefangen, und die drei anderen Skripte hatten
 * sie gar nicht. Hier steht sie einmal, fuer alle vier.
 *
 * DER UNTERSCHIED, UM DEN ES GEHT, ist einer: „langsam" und „haengt" sehen im
 * Protokoll gleich aus, wenn niemand mitschreibt. Mit Verlauf sind sie
 * unterscheidbar — ein Export, der bis „182 von 182 Dateien" kam und dann
 * stehenblieb, ist ein anderer Befund als einer, der nie eine Zeile gemeldet
 * hat.
 *
 * DAS WARTEN WIRD VOR DEM KLICK ANGEMELDET, und das bleibt Sache des
 * Aufrufers: `waitForEvent()` horcht erst ab dem Aufruf. Wer es hinter das
 * Bestaetigen der Rueckfragen setzt, verliert das Rennen, sobald der Export
 * schneller fertig ist als die Schleife ihre letzten Leerlaeufe abwartet —
 * der Download kommt, niemand hoert zu, und das Skript wartet bis zum
 * Zeitlimit auf ein Ereignis, das laengst vorbei ist. Das sah schon einmal
 * aus wie ein Fehler der Anwendung und war einer des Pruefmittels.
 */

/**
 * @param seite    Playwright-Seite
 * @param warten   das VOR dem Klick angemeldete `seite.waitForEvent('download')`
 * @param feld     Auswahl des Zustandsfeldes, z. B. `#expstate`
 * @param konsole  die Sammelliste der Konsolenfehler des Skripts (optional)
 * @returns        den Download
 * @throws         Error mit Zustand, Verlauf und Konsolenfehlern, wenn keiner kommt
 */
export async function downloadMitFortschritt(seite, warten, feld, konsole = []) {
  const zustand = async () =>
    (await seite.locator(feld).textContent().catch(() => '') || '').trim();

  let letzter = '';
  const verlauf = [];
  const uhr = setInterval(async () => {
    const s = await zustand();
    if (s && s !== letzter) { letzter = s; verlauf.push(s); console.log(`     · ${s}`); }
  }, 3000);

  try {
    return await warten;
  } catch (fehler) {
    /* HIER STEHT DER GANZE ZWECK DIESER DATEI. Der urspruengliche Fehler
     * bleibt in der Meldung — er sagt die Zeitgrenze —, aber davor steht,
     * was die Anlage zuletzt von sich gegeben hat. */
    const jetzt = await zustand();
    const fehlerZeile = String(fehler && fehler.message || fehler).split('\n')[0];
    throw new Error(
      `Kein Download innerhalb der Zeitgrenze.\n`
      + `  Zustand von ${feld}: ${jetzt || '— leer —'}\n`
      + `  Verlauf:            ${verlauf.length
            ? verlauf.join('  →  ')
            : 'KEIN Fortschritt gemeldet — der Vorgang ist nie angelaufen '
              + 'oder meldet seinen Stand nicht'}\n`
      + `  Konsolenfehler:     ${konsole.length ? konsole.join(' | ') : 'keine'}\n`
      + `  Ursprung:           ${fehlerZeile}`);
  } finally {
    clearInterval(uhr);
  }
}

/* -------------------------------------------------------------- Selbstprobe
 *
 *     node tools/referenzdatensatz/browser/download_lib.mjs --selbstprobe
 *
 * Ohne Browser und ohne Anlage: Die Seite wird durch eine Attrappe ersetzt,
 * die vorgeschriebene Zustandstexte liefert. Geprueft wird nicht der gute
 * Fall — der ist trivial —, sondern ob im SCHLECHTEN Fall die Auskunft
 * entsteht, um derentwillen es diese Datei gibt.
 */
/* DIE SCHALTER STEHEN ALS MENGE DA, und zwar fuer `tools/kettenaufrufe/`:
 * Es liest die Schnittstelle eines `.mjs` aus `wert('--x'`/`flag('--x'` oder
 * aus genau dieser Menge. Ohne sie meldet es „keine Schnittstelle im
 * Quelltext gefunden, UNGEPRUEFT" — ehrlich, aber eine Null weniger. */
const BEKANNT = new Set(['--selbstprobe']);

if (process.argv.slice(2).some(a => BEKANNT.has(a))) {
  let erfuellt = 0, offen = 0;
  const pruefe = (ok, was) => {
    if (ok) { erfuellt++; } else { offen++; }
    console.log(`  [${ok ? 'ok ' : 'FEHL'}] ${was}`);
  };
  const attrappe = (texte) => {
    let i = 0;
    return { locator: () => ({ textContent: async () => texte[Math.min(i++, texte.length - 1)] }) };
  };
  const spaeter = (ms, wert) => new Promise(r => setTimeout(() => r(wert), ms));
  const platzt = (ms) => new Promise((_, x) =>
    setTimeout(() => x(new Error('page.waitForEvent: Timeout 900000ms exceeded while waiting for event "download"\nlogs')), ms));

  console.log('Selbstprobe `download_lib.mjs` — was steht da, wenn nichts kommt?\n');

  // 1. Der gute Fall gibt den Download durch.
  const dl = await downloadMitFortschritt(attrappe(['laeuft']), spaeter(10, { name: 'x.edbak' }), '#expstate', []);
  pruefe(dl && dl.name === 'x.edbak', 'Kommt der Download, wird er unveraendert durchgereicht');

  // 2. Timeout MIT Fortschritt: Zustand und Verlauf stehen in der Meldung.
  let m = '';
  try {
    await downloadMitFortschritt(attrappe(['Schritt 1 von 3', 'Schritt 2 von 3', 'Schritt 2 von 3']),
                                 platzt(7000), '#expstate', ['TypeError: x ist undefined']);
  } catch (f) { m = f.message; }
  pruefe(/Kein Download innerhalb der Zeitgrenze/.test(m), 'Timeout: die Meldung sagt, worum es geht');
  pruefe(/Zustand von #expstate: Schritt 2 von 3/.test(m),
         'Timeout: der LETZTE Zustand der Anlage steht da');
  pruefe(/Schritt 1 von 3\s+→\s+Schritt 2 von 3/.test(m),
         'Timeout: der VERLAUF steht da — daran haengt „langsam oder haengt?"');
  pruefe(/TypeError: x ist undefined/.test(m), 'Timeout: Konsolenfehler des Browsers stehen da');
  pruefe(/Timeout 900000ms/.test(m), 'Timeout: der urspruengliche Fehler geht nicht verloren');
  pruefe(!/\n.*\n.*logs/.test(m.split('Ursprung:')[1] || ''),
         'Timeout: vom Ursprungsfehler nur die erste Zeile, nicht sein ganzer Rumpf');

  // 3. Timeout OHNE jeden Fortschritt -- der eigentlich interessante Fall.
  let m2 = '';
  try {
    await downloadMitFortschritt(attrappe(['']), platzt(7000), '#expstate', []);
  } catch (f) { m2 = f.message; }
  pruefe(/KEIN Fortschritt gemeldet/.test(m2),
         'NIE ANGELAUFEN wird als solches benannt und nicht als leerer Verlauf');
  pruefe(/Zustand von #expstate: — leer —/.test(m2),
         'Ein leeres Zustandsfeld heisst „leer" und nicht ""');
  pruefe(/Konsolenfehler:\s+keine/.test(m2),
         'Keine Konsolenfehler heisst „keine" — kein stilles Weglassen');

  console.log(`\n  erfuellt: ${erfuellt} · offen: ${offen}`);
  process.exit(offen === 0 ? 0 : 1);
}
