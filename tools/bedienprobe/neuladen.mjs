/* Neuladen nach einer Handlung — wiederholt der Browser etwas? (R4-11)
 * ===========================================================================
 *
 * WOZU: Backlog Nr. 250. Eine Seite, die ihr POST-Ergebnis selbst ausgibt,
 * hinterlässt ein Formular im Verlauf des Browsers; „Neu laden" schickt es
 * noch einmal ab. Seit Web 21.1.9 leiten alle Seiten unter Verwaltung und
 * Betrieb nach einer Handlung um (außer `betrieb_server.php`, Schritt 18),
 * und die Meldung kommt über die Sitzung an (`flash_setzen()`).
 *
 * WAS EIN WEG HIER MISST — drei Dinge, die nur ein Browser zeigt:
 *   1. Die Antwort auf das Absenden ist eine UMLEITUNG aus einem POST
 *      (`redirectedFrom()` der Navigation ist ein POST).
 *   2. Die Meldung steht auf der Seite, auf der die Umleitung endet.
 *   3. „Neu laden" schickt ein GET, und die Meldung ist danach fort — sie
 *      stand einmal da, nicht bei jedem Aufruf.
 * Dazu, wo ein Weg es kann, ein Zähler aus der Datenbank vor und nach dem
 * Neuladen: Er darf sich nicht bewegen.
 *
 * NICHT UNTER `wege/`, weil der Läufer dort jede Datei als Wegdatei lädt
 * (dasselbe wie `probekonto.mjs`). Die Wege stehen in den Dateien ihrer
 * Seite (E-PK-15).
 */

/**
 * Den Knopf des Formulars finden, in dem `feld` steht — auch wenn der Knopf
 * außerhalb steht und über `form=` absendet (Muster „Pause aufheben",
 * „Ausstehende ausführen"). Gibt eine Auswahl für `locator()` zurück.
 */
async function knopfZu(k, feld) {
  const id = await k.seite.evaluate((sel) => {
    const e = document.querySelector(sel);
    const f = e && (e.form || e.closest('form'));
    if (!f) { return null; }
    if (!f.id) { f.id = 'neuladen-formular'; }
    return f.id;
  }, feld);
  if (!id) { throw new Error('Kein Formular um ' + feld); }
  return `#${id} button:not([type="button"]), button[form="${id}"]:not([type="button"])`;
}

/** Wie oft steht `muster` (Text oder RegExp) im sichtbaren Text? */
async function treffer(k, muster) {
  const text = await k.seite.evaluate(() => document.body.textContent.replace(/\s+/g, ' '));
  if (muster instanceof RegExp) {
    return (text.match(new RegExp(muster.source, 'g')) || []).length;
  }
  return text.split(muster).length - 1;
}

/**
 * @param k          der Werkzeugkasten des Läufers
 * @param o.name     Name für das Bild
 * @param o.seite    Adresse der Seite
 * @param o.feld     Auswahl eines Elements IM Formular (meist
 *                   `input[name="action"][value="…"]`) — der Knopf wird daraus
 *                   gesucht; oder
 * @param o.absenden optional: async (k) => {} — klickt selbst, etwa durch eine
 *                   Rückfrage hindurch
 * @param o.vorher   optional: async (k) => {} — Formular füllen o. ä.
 * @param o.meldung  Text oder RegExp der Meldung. GEZÄHLT, nicht gesucht: Die
 *                   Meldung ist, was die Seite nach der Umleitung dem
 *                   Neuladen voraus hat — genau ein Treffer (auf
 *                   `betrieb_jobs.php` sagt ein Dauerhinweis dasselbe).
 * @param o.zaehler  optional: () => number — ein Zähler aus der Datenbank,
 *                   der sich beim Neuladen nicht bewegen darf
 * @param o.ziel     optional: Teil der Adresse, auf der die Umleitung enden muss
 * @return {Promise<{ist:string, ok:boolean, bemerkung:string}>}
 */
export async function neuladenPruefen(k, o) {
  const schritte = [];
  await k.gehZu(o.seite);
  if (o.vorher) { await o.vorher(k); }

  const klick = o.absenden
    ? o.absenden(k)
    : knopfZu(k, o.feld).then(sel => k.seite.locator(sel).first().click());
  const [antwort] = await Promise.all([
    k.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    klick,
  ]);
  const von = antwort ? antwort.request().redirectedFrom() : null;
  const ausPost = !!von && von.method() === 'POST';
  const adresse = k.seite.url();
  schritte.push(ausPost ? 'POST → umgeleitet' : 'KEINE Umleitung aus einem POST');
  const zielOk = !o.ziel || adresse.includes(o.ziel);
  if (!zielOk) { schritte.push('endet auf ' + adresse); }

  const n1 = await treffer(k, o.meldung);
  await k.bild('neuladen-' + (o.name || 'seite'));

  const vorherZahl = o.zaehler ? o.zaehler() : null;
  const neu = await k.seite.reload({ waitUntil: 'domcontentloaded' });
  const methode = neu ? neu.request().method() : '—';
  const n2 = await treffer(k, o.meldung);
  const nachherZahl = o.zaehler ? o.zaehler() : null;
  schritte.push(`Meldung ${n1}×, nach dem Neuladen ${n2}×`);
  schritte.push('Neuladen ' + methode);
  if (o.zaehler) { schritte.push(`Zähler ${vorherZahl} → ${nachherZahl}`); }

  const ok = ausPost && zielOk && n1 === n2 + 1 && methode === 'GET'
          && vorherZahl === nachherZahl;
  return { ist: schritte.join(' · '), ok,
           bemerkung: ok ? '' : 'Soll: POST → umgeleitet · Meldung einmal mehr als nach dem '
                              + 'Neuladen · Neuladen GET' + (o.zaehler ? ' · Zähler unverändert' : '') };
}
