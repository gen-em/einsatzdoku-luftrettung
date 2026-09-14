/* Motorwahl — ein Prüfmittel, drei Engines (AP3b, Backlog Nr. 183).
 * ===========================================================================
 *
 * WARUM ES DIESE DATEI GIBT. Seit dem 14.09.2026 liegen drei Playwright-
 * Engines auf dem Prüfstand: Chromium, Firefox (Gecko) und WebKit. Drei
 * Werkzeuge fahren einen Browser — Bilderlauf, Klickprobe, Stilvergleich —,
 * und alle drei brauchen dieselben zwei Griffe: die Wahl des Motors und die
 * Firefox-Voreinstellung unten. Stünden sie dreimal da, stünde die
 * Voreinstellung früher oder später an zwei Stellen richtig und an einer
 * falsch. Sie steht deshalb hier, einmal.
 *
 * DIE FIREFOX-VOREINSTELLUNG IST KEINE FEINHEIT, SONDERN DIE BEDINGUNG DAFÜR,
 * DASS FIREFOX ÜBERHAUPT DASSELBE STYLESHEET MISST. Gemessen am 14.09.2026,
 * leere Seite, 1280 px:
 *
 *     chromium  (hover:hover)=true   (pointer:fine)=true
 *     firefox   (hover:hover)=false  (pointer:fine)=false   <-- ohne Voreinstellung
 *     webkit    (hover:hover)=true   (pointer:fine)=true
 *
 * Headless Firefox meldet also "kein Zeiger, kein Hover". Damit ist der
 * gesamte Media-Block der 36-px-Bedienhöhe
 * `(hover:hover) and (pointer:fine) and (min-width:1024px)` (E-S8-09/R76)
 * für Firefox unsichtbar — die Klickprobe meldete prompt "Zeile 44 px statt
 * 36", und der Bilderlauf hätte auf jeder Seite ab 1024 px falsche
 * Knopfhöhen gemeldet. Schlimmer als die falsche Meldung ist der stumme Fall:
 * Ein Stilvergleich, der diesen Block nie betritt, meldet dort keine
 * Abweichung — eine schmeichelhafte Null.
 *
 * Gecko bildet die Zeigermerkmale auf zwei Voreinstellungen ab, als Bitmaske:
 * 1 = grob, 2 = fein, 4 = Hover. Gemessen: 2 ergibt `pointer:fine` OHNE
 * `hover:hover`, erst 6 ergibt beides. Es muss also 6 sein.
 *
 * IM FINGERLAUF NICHT. `hasTouch: true` am Kontext setzt in ALLEN DREI
 * Engines `pointer:coarse` und `hover:none` (gemessen, ebenfalls 14.09.2026);
 * dafür braucht Firefox keine Voreinstellung — und bekäme sie es doch, würde
 * sie den Fingerlauf zum Zeigerlauf machen. Deshalb hängt `starten()` am
 * Schalter `finger`.
 */

export const MOTOREN = ['chromium', 'firefox', 'webkit'];

/* Fein + Hover als Gecko-Bitmaske. Siehe Kopfkommentar — 2 allein genügt
 * nicht, 6 ist die Zahl. */
const ZEIGER_FIREFOX = {
  'ui.primaryPointerCapabilities': 6,
  'ui.allPointerCapabilities': 6,
};

/** Liest `--motor <name>` aus einer Argumentliste (Vorgabe: chromium).
 *  Ein unbekannter Name ist ein Abbruch, keine stille Rückfallebene: Wer
 *  `--motor gecko` tippt, soll das erfahren und nicht ungewollt Chromium
 *  messen. */
export function motorWahl(argv) {
  const i = argv.indexOf('--motor');
  const name = i >= 0 ? argv[i + 1] : (process.env.MOTOR || 'chromium');
  if (!MOTOREN.includes(name)) {
    console.error(`Unbekannter Motor "${name}". Erlaubt: ${MOTOREN.join(', ')}.`);
    process.exit(2);
  }
  return name;
}

/** Startet den gewählten Motor. `pw` ist das geladene Playwright-Modul.
 *  `finger` sagt, ob der Lauf ein Fingergerät nachstellt; `optionen` reicht
 *  weitere Startangaben durch (der Stilvergleich setzt so seinen
 *  `executablePath` für Chromium). */
export async function starten(pw, name, { finger = false, optionen = {} } = {}) {
  const auf = (name === 'firefox' && !finger)
    ? { ...optionen, firefoxUserPrefs: { ...ZEIGER_FIREFOX, ...(optionen.firefoxUserPrefs || {}) } }
    : { ...optionen };
  return await pw[name].launch(auf);
}
