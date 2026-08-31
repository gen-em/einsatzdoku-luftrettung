/* Bedienhilfen für die Browserproben — an EINER Stelle.
 *
 * WOFUER. Seit P3 sind Kontrollkästchen und Segmentwahlen der Anwendung
 * **unsichtbar**: `ui_schalter()` und `ui_segment()` (ui.php) rendern ein
 * `<input>` mit `position:absolute; opacity:0; width:0; height:0` und daneben
 * ein `<label for="…">`, das die sichtbare Taste ist (style.css, Abschnitt zu
 * `.segment-box`/`.schalter-box`). Wer bedient, klickt die Beschriftung — das
 * gilt für eine NutzerIn wie für eine Probe.
 *
 * `page.check()` klickt dagegen das Feld selbst und wartet darauf, dass es
 * sichtbar wird. Das wird es nie. Der Lauf endet nach 30 s mit
 * „element is not visible", und zwar an einer Stelle, an der die Anwendung
 * vollkommen in Ordnung ist. Genau daran ist der CSV-Kreislauf seit P3
 * gescheitert, ohne dass es jemandem aufgefallen wäre (F-S2-A, dieselbe
 * Familie: Prüfmittel nicht mit dem Redesign nachgezogen).
 *
 * WARUM EINE EIGENE DATEI und nicht zweimal dieselben sechs Zeilen: Vier
 * Kopien einer Fehlerlesung sind der Grund, aus dem F-S2-A ein halbes Jahr
 * unbemerkt blieb. Zwei Kopien sind eine zu viel.
 *
 * NICHT für jedes Kästchen nötig. Ein gewöhnliches, sichtbares
 * `<input type="checkbox">` — etwa `#rcok` auf der Seite zum Setzen des
 * Passworts — wird weiterhin unmittelbar bedient; die Hilfen unten merken
 * das selbst und nehmen dann den kurzen Weg.
 */

/** Gemeinsamer Kern: Feld finden, Zustand herstellen, Erfolg belegen. */
async function schalten(seite, auswahl, sollAn) {
  const feld = seite.locator(auswahl).first();
  await feld.waitFor({ state: 'attached', timeout: 30000 });

  if (await feld.isChecked() === sollAn) { return 'stand schon so'; }

  // Sichtbar? Dann der kurze Weg — so bedient es auch eine NutzerIn.
  if (await feld.isVisible().catch(() => false)) {
    if (sollAn) { await feld.check(); } else { await feld.uncheck(); }
    return 'unmittelbar';
  }

  // Unsichtbar: über die zugehörige Beschriftung, das ist die sichtbare Taste.
  const id = await feld.getAttribute('id');
  if (!id) {
    throw new Error(`${auswahl} ist unsichtbar und hat keine Kennung — ohne `
      + '<label for="…"> lässt sich das Feld nicht bedienen.');
  }
  // Anführungszeichen im Attributwert maskieren. Die Kennungen der Anwendung
  // sind harmlos (`exp_pat`, `sg-exp_zr-all-1`), aber ein Auswahlausdruck,
  // der bei einer unerwarteten Kennung still etwas anderes trifft, wäre
  // schlimmer als einer, der abbricht.
  const taste = seite.locator(`label[for="${id.replace(/"/g, '\\"')}"]`).first();
  await taste.click({ timeout: 30000 });

  // BELEGEN, nicht hoffen. Ein Klick auf die Beschriftung kann ins Leere
  // gehen (verdeckt, ausgeblendet, `for` zeigt woandershin); ohne diese
  // Prüfung liefe der Lauf mit falschen Einstellungen weiter und meldete am
  // Ende eine Abweichung, deren Ursache dann niemand mehr findet.
  if (await feld.isChecked() !== sollAn) {
    throw new Error(`${auswahl}: Klick auf label[for="${id}"] hat den Zustand `
      + `nicht geändert (erwartet: ${sollAn ? 'gesetzt' : 'nicht gesetzt'}).`);
  }
  return 'über die Beschriftung';
}

/** Kästchen, Schalter oder Segmenttaste SETZEN. */
export async function ankreuzen(seite, auswahl) {
  return schalten(seite, auswahl, true);
}

/** Kästchen oder Schalter ABWÄHLEN. */
export async function abwaehlen(seite, auswahl) {
  return schalten(seite, auswahl, false);
}
