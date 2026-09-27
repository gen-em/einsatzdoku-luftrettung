/* Wege der Tagesübersicht an einem Nachtdienst (`index.php?d=…`).
 * ===========================================================================
 *
 * Entstanden mit R4-16 (Backlog Nr. 275): Der Referenzbestand trägt seit
 * dem Luftdienst D22 einen Dienst, an dem Einsätze vor UND nach Mitternacht
 * beginnen. Die Spalte „Beginn" sortiert über `start_sort` (Datum und Uhrzeit
 * in Ortszeit, E-ZE-32); über die Uhrzeit allein stünde 01:40 vor 23:50 —
 * still, ohne Meldung, in einer Reihenfolge, die richtig aussieht. Bis R4-16
 * gab es im Bestand keinen Tag, an dem das zu sehen gewesen wäre (AP9b in
 * Schritt 15 hat es an einem im Browser gebauten Tag belegt).
 *
 * DER TAG WIRD ÜBER DEN INHALT GESUCHT, wie im Bilderlauf (`__TAG_NACHT__`):
 * ein Luftdienst, dessen Einsätze an zwei Ortsdaten beginnen. Findet sich
 * keiner, ist der Weg rot — ein Bestand ohne den Fall belegt nichts.
 *
 * DREI SCHNITTSTELLEN RECHNEN `start_sort`, und Nr. 275 hielt fest, dass das
 * nur gelesen, nicht gefahren war: `api/day.php` (Tagesübersicht),
 * `api/range.php` (Zeitraum, nach `days.day` gefiltert) und
 * `api/suchindex.php` (Suche, nach dem echten Einsatzdatum). Der Weg holt den
 * Wert für jeden Einsatz des Nachtdienstes aus allen dreien und verlangt,
 * dass sie übereinstimmen und zwei Ortsdaten tragen. Die Tabelle sortiert in
 * allen drei Ansichten mit demselben Modul; ein falscher Wert aus einer
 * Schnittstelle sortierte dort still falsch.
 */

/* Beginn als Minuten seit dem Dienstbeginn: 23:50 → 1430, 01:40 → 100 + 1440.
 * Ein Wert kleiner als sein Vorgänger heißt „nach Mitternacht". */
function chronologisch(zeiten) {
  let tag = 0, vorher = -1;
  return zeiten.map((z) => {
    const [h, m] = z.split(':').map(Number);
    const min = h * 60 + m;
    if (vorher >= 0 && min < vorher) { tag += 1440; }
    vorher = min;
    return min + tag;
  });
}

export const wege = [
  {
    name: 'nachtdienst-sortierung-beginn',
    paket: 'R4-16', punkt: 'Nr. 275', rolle: 'demo',
    soll: 'Am Nachtdienst sortiert „Beginn" chronologisch: aufsteigend 23:50 vor '
        + '01:40, absteigend umgekehrt — nicht nach der Uhrzeit als Zeichenkette',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const tagId = await k.seite.evaluate(async (b) => {
        const liste = ((await (await fetch(b + '/api/day.php', { credentials: 'same-origin' })).json()).days) || [];
        for (const t of liste.filter((x) => x.kind === 'air')) {
          const i = await (await fetch(b + '/api/day.php?d=' + t.id, { credentials: 'same-origin' })).json();
          const daten = new Set(((i && i.missions) || []).map((x) => String(x.start_sort || '').slice(0, 10)));
          if (daten.size > 1) { return t.id; }
        }
        return null;
      }, k.basis);
      if (!tagId) {
        return { ist: 'kein Nachtdienst im Bestand', ok: false,
                 bemerkung: 'D22 fehlt — Referenzbestand älter als R4-16?' };
      }
      const quellen = await k.seite.evaluate(async ([b, id]) => {
        const hol = async (pfad) => (await fetch(b + pfad, { credentials: 'same-origin' })).json();
        const tag = await hol('/api/day.php?d=' + id);
        const karte = (liste) => Object.fromEntries((liste || [])
          .filter((m) => String(m.day_id ?? id) === String(id))
          .map((m) => [m.id, m.start_sort]));
        const [y, mo] = String(tag.day || '').split('-');
        return {
          day: karte(tag.missions),
          range: karte((await hol(`/api/range.php?y=${y}&m=${mo}`)).missions),
          suche: karte((await hol('/api/suchindex.php')).missions),
        };
      }, [k.basis, tagId]);
      const ids = Object.keys(quellen.day);
      const gleich = ids.length >= 3 && ['range', 'suche'].every((q) =>
        Object.keys(quellen[q]).length === ids.length
        && ids.every((i) => quellen[q][i] === quellen.day[i]));
      const zweiDaten = new Set(ids.map((i) => String(quellen.day[i]).slice(0, 10))).size === 2;
      await k.gehZu(`${k.basis}/index.php?d=${tagId}`);
      const kopf = k.seite.locator('th.sortable[data-key="start"]').first();
      await kopf.waitFor({ timeout: 15000 });

      /* Die Spalte „Beginn" über ihre Position im Kopf lesen, nicht über
       * eine feste Nummer: Die Tagesübersicht blendet Spalten aus (`ohne`). */
      const lies = () => k.seite.evaluate(() => {
        const ths = [...document.querySelectorAll('th.sortable')];
        const i = ths.findIndex((th) => th.dataset.key === 'start');
        const tabelle = ths[i] && ths[i].closest('table');
        return i < 0 || !tabelle ? [] : [...tabelle.querySelectorAll('tbody tr')]
          .map((tr) => (tr.children[i] && tr.children[i].textContent || '').trim())
          .filter((z) => /^\d{2}:\d{2}$/.test(z));
      });

      /* Zweimal klicken: aufsteigend, dann absteigend. Steht die Spalte schon
       * aufsteigend, kehrt der erste Klick sie um — deshalb wird nach jedem
       * Klick gelesen und die Richtung aus dem Pfeil bestimmt. */
      const ergebnisse = [];
      for (let n = 0; n < 2; n++) {
        await kopf.click();
        await k.seite.waitForTimeout(200);
        const auf = await k.seite.locator('th.sortable[data-key="start"] .arrow .symbol-oben').count() === 0;
        ergebnisse.push({ auf, zeiten: await lies() });
      }
      const aufst = ergebnisse.find((e) => e.auf);
      const abst = ergebnisse.find((e) => !e.auf);
      const steigt = (w) => w.every((x, i) => i === 0 || x > w[i - 1]);
      const okAuf = !!aufst && aufst.zeiten.length >= 3 && steigt(chronologisch(aufst.zeiten))
                    && aufst.zeiten.indexOf('23:50') < aufst.zeiten.indexOf('01:40');
      const okAb = !!abst && abst.zeiten.length >= 3
                   && abst.zeiten.join() === [...aufst.zeiten].reverse().join();
      await k.bild('nachtdienst-sortierung-beginn');
      const quelleOk = gleich && zweiDaten;
      return {
        ist: `aufsteigend ${aufst ? aufst.zeiten.join(' · ') : '—'}; absteigend ${abst ? abst.zeiten.join(' · ') : '—'}; `
           + `start_sort in day/range/suche: ${ids.length}/${Object.keys(quellen.range).length}/${Object.keys(quellen.suche).length} Einsätze, `
           + `${gleich ? 'gleich' : 'VERSCHIEDEN'}, ${zweiDaten ? 'zwei Ortsdaten' : 'NICHT zwei Ortsdaten'}`,
        ok: okAuf && okAb && quelleOk,
        bemerkung: !(okAuf && okAb) ? 'Reihenfolge nicht chronologisch — sortiert die Spalte nach start_hhmm?'
                 : !quelleOk ? 'start_sort weicht zwischen den Schnittstellen ab oder trägt kein Folgedatum' : '',
      };
    },
  },
];
