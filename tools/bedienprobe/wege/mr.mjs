/* Wege der Mockup-Runde 9c, Arbeitspaket AP4 — das Aktionsblatt (Nr. 124).
 * ===========================================================================
 *
 * Drei Wege, und jeder misst etwas, das ein Bild nicht zeigt:
 *
 *   MR-01  Der Öffner ist markiert, solange sein Blatt offen ist — an ALLEN
 *          vier Bauarten, nicht nur an den beiden Bausteinen (E-MR-25).
 *   MR-02  Das Blatt fährt auf und wieder zu, und danach ist es WIRKLICH weg
 *          (`hidden`), nicht bloß verschoben.
 *   MR-03  Am Schreibtisch fährt nichts, und das Aufklappmenü geht SOFORT zu
 *          — nicht erst nach der Fahrtdauer.
 *
 * WARUM MR-02 DEN NACHLAUF BRAUCHT. Das Skript nimmt das Blatt erst nach der
 * Rückfahrt aus dem Fluss. Ein Weg, der gleich nach `Escape` misst, fände es
 * sichtbar und meldete einen Fehler, den es nicht gibt; einer, der gar nicht
 * misst, fände nie, dass es hängen bleibt. Gewartet wird deshalb die
 * GERECHNETE Fahrtdauer plus Puffer — dieselbe Zahl, die auch `blatt.js`
 * benutzt, und nicht eine zweite im Prüfmittel.
 *
 * WARUM MR-03 EIGENS DASTEHT. Am Schreibtisch hat das Blatt
 * `transition:none`; `transitionend` kommt dort nie. Ein Skript, das darauf
 * wartet, ließe das Aufklappmenü eine Viertelsekunde zu lange stehen — und
 * das sieht man einem Bild nicht an, weil es am Ende ja zu ist.
 */

/* Die vier Bauarten von Öffnern, je an der Seite, auf der sie stehen.
 * Nachgezählt am Code (E-MR-25): 6 × `ui_aktionen()`, 9 ×
 * `ui_zeilenaktionen()`, der Pin-Knopf des Ortsfelds, 3 handgeschriebene
 * Sortierblatt-Knöpfe. Hier steht je ein Vertreter — wer eine fünfte Bauart
 * baut, trägt sie dazu. */
const OEFFNER = [
  { seite: 'index.php',                   id: 'dayblatt',  was: 'ui_aktionen' },
  { seite: 'index.php',                   id: 'sortblatt', was: 'Sortierblatt index.php' },
  { seite: 'einstellungen.php?t=geraete', id: 'za-1',      was: 'ui_zeilenaktionen' },
  { seite: 'suche.php',                   id: 'sortblatt', was: 'Sortierblatt suche.php' },
  { seite: 'zeitraum.php?y=2026',         id: 'sortblatt', was: 'Sortierblatt zeitraum.php' },
];

const ORANGE_HELL = 'rgb(255, 235, 214)';   /* --orange-hell */
const ORANGE_TIEF = 'rgb(194, 90, 0)';      /* --orange-tief */

/** Die gerechnete Fahrtdauer des Blattes in Millisekunden — dieselbe Frage,
 *  die `blatt.js` stellt. 0 heißt „keine Fahrt". */
async function fahrtMs(seite, id) {
  return seite.evaluate((id) => {
    const el = document.getElementById(id);
    if (!el) { return -1; }
    const roh = (getComputedStyle(el).transitionDuration || '').split(',')[0].trim();
    const zahl = parseFloat(roh);
    if (!zahl) { return 0; }
    return /ms$/.test(roh) ? zahl : zahl * 1000;
  }, id);
}

/** Zustand von Öffner und Blatt in einem Zug. */
async function stand(seite, id) {
  return seite.evaluate((id) => {
    const bl = document.getElementById(id);
    const kn = document.querySelector('[data-blatt="' + id + '"]');
    if (!bl || !kn) { return null; }
    const ck = getComputedStyle(kn);
    const rb = bl.getBoundingClientRect();
    return {
      hidden: bl.hidden,
      expanded: kn.getAttribute('aria-expanded'),
      flaeche: ck.backgroundColor,
      schrift: ck.color,
      unten: Math.round(rb.bottom),
      fensterhoehe: window.innerHeight,
      fokusAmKnopf: document.activeElement === kn,
    };
  }, id);
}

export const wege = [
  {
    name: 'mr-oeffner-markiert',
    paket: 'AP4', punkt: 'MR-01', rolle: 'demo',
    was: 'Jede Bauart von Öffner trägt die Markierung, solange ihr Blatt offen ist',
    soll: '5 von 5 Öffnern: Fläche --orange-hell, Schrift --orange-tief, aria-expanded=true; '
        + 'nach dem Schließen alle zurück und der Fokus wieder am Knopf',
    async fahren(k) {
      /* BEI 390 px, UND ZWAR ABSICHTLICH. Zwei der fünf Öffner gibt es nur
         am Handy — der Sortier-Knopf im Kartenkopf trägt `nur-unter-720`,
         und das Zeilen-„⋯" steht ohne `blatt_immer` ebenfalls nur dort. Bei
         der Vorgabebreite 1280 px sind sie im Markup, aber unsichtbar, und
         der Klick läuft in die Zeitgrenze: ein Fehlschlag, der wie ein Befund
         aussieht und keiner ist. */
      await k.seite.setViewportSize({ width: 390, height: 900 });
      const treffer = [];
      const ab = [];
      for (const o of OEFFNER) {
        await k.gehZu(`${k.basis}/${o.seite}`);
        await k.seite.waitForTimeout(500);
        const da = await k.seite.evaluate(
          (id) => !!document.querySelector('[data-blatt="' + id + '"]'), o.id);
        if (!da) { ab.push(`${o.was}: Öffner nicht auf der Seite`); continue; }
        await k.seite.click(`[data-blatt="${o.id}"]`);
        await k.seite.waitForTimeout(400);
        const auf = await stand(k.seite, o.id);
        await k.seite.keyboard.press('Escape');
        await k.seite.waitForTimeout(Math.round(await fahrtMs(k.seite, o.id)) + 250);
        const zu = await stand(k.seite, o.id);
        const gut = auf && zu
          && auf.flaeche === ORANGE_HELL && auf.schrift === ORANGE_TIEF
          && auf.expanded === 'true' && auf.hidden === false
          && zu.expanded === 'false' && zu.hidden === true && zu.fokusAmKnopf;
        if (gut) { treffer.push(o.was); }
        else {
          ab.push(`${o.was}: offen ${auf ? auf.flaeche + '/' + auf.schrift + ' exp=' + auf.expanded : '—'}`
                + `, zu ${zu ? 'hidden=' + zu.hidden + ' exp=' + zu.expanded + ' Fokus=' + zu.fokusAmKnopf : '—'}`);
        }
      }
      await k.bild('mr-oeffner-markiert');
      return {
        ist: `${treffer.length} von ${OEFFNER.length} Öffnern markiert und sauber zurückgestellt`
           + (ab.length ? ' · ' + ab.join(' · ') : ''),
        ok: treffer.length === OEFFNER.length,
        bemerkung: ab.join(' · '),
      };
    },
  },

  {
    name: 'mr-blatt-faehrt-auf',
    paket: 'AP4', punkt: 'MR-02', rolle: 'demo',
    was: 'Das Blatt fährt herauf und wieder hinunter — und ist danach wirklich weg',
    soll: 'bei 390 px: Fahrtdauer > 0; kurz nach dem Öffnen unterwegs (nicht schon oben), '
        + 'danach Unterkante = Fensterunterkante; nach der Rückfahrt hidden=true',
    async fahren(k) {
      await k.seite.setViewportSize({ width: 390, height: 900 });
      await k.gehZu(`${k.basis}/index.php`);
      await k.seite.waitForTimeout(600);
      const ms = await fahrtMs(k.seite, 'dayblatt');
      await k.seite.click('[data-blatt="dayblatt"]');
      /* MITTEN IN DER FAHRT messen, nicht am Ende: Ein Blatt ohne Bewegung
         stünde hier schon oben, und genau das soll auffallen. */
      await k.seite.waitForTimeout(Math.max(40, Math.round(ms / 4)));
      const unterwegs = await k.seite.evaluate(() => {
        const t = getComputedStyle(document.getElementById('dayblatt')).transform;
        const m = t.match(/matrix\(([^)]+)\)/);
        return m ? Math.round(parseFloat(m[1].split(',')[5])) : 0;
      });
      await k.seite.waitForTimeout(Math.round(ms) + 250);
      const offen = await stand(k.seite, 'dayblatt');
      await k.bild('mr-blatt-faehrt-auf');
      await k.seite.keyboard.press('Escape');
      /* GLEICH NACH DEM ESCAPE ist es noch da — das ist die Zusage, nicht der
         Fehler: Ohne sie wäre die Rückfahrt unsichtbar. */
      await k.seite.waitForTimeout(Math.max(30, Math.round(ms / 4)));
      const waehrendRueckfahrt = await stand(k.seite, 'dayblatt');
      await k.seite.waitForTimeout(Math.round(ms) + 300);
      const danach = await stand(k.seite, 'dayblatt');
      const ok = ms > 0 && unterwegs > 0 && offen && !offen.hidden
              && offen.unten === offen.fensterhoehe
              && waehrendRueckfahrt && waehrendRueckfahrt.hidden === false
              && danach && danach.hidden === true;
      return {
        ist: `Fahrtdauer ${Math.round(ms)} ms · unterwegs ${unterwegs} px unter der Ruhelage · `
           + `offen Unterkante ${offen ? offen.unten + '/' + offen.fensterhoehe : '—'} · `
           + `während der Rückfahrt hidden=${waehrendRueckfahrt ? waehrendRueckfahrt.hidden : '—'} · `
           + `danach hidden=${danach ? danach.hidden : '—'}`,
        ok,
        bemerkung: ok ? ''
          : (ms === 0 ? 'Keine Fahrt am Handy — die Regel greift nicht'
            : (unterwegs === 0 ? 'Schon oben, als gemessen wurde: es springt statt zu fahren'
              : 'Der Zustand nach der Rückfahrt stimmt nicht')),
      };
    },
  },

  {
    name: 'mr-schreibtisch-ohne-fahrt',
    paket: 'AP4', punkt: 'MR-03', rolle: 'demo',
    was: 'Am Schreibtisch fährt nichts, und das Aufklappmenü geht sofort zu',
    soll: 'bei 1280 px: Fahrtdauer 0 ms; nach Escape ist es binnen 60 ms hidden '
        + '(nicht erst nach der Fahrtdauer)',
    async fahren(k) {
      await k.seite.setViewportSize({ width: 1280, height: 900 });
      await k.gehZu(`${k.basis}/index.php`);
      await k.seite.waitForTimeout(600);
      await k.seite.click('[data-blatt="dayblatt"]');
      await k.seite.waitForTimeout(300);
      const ms = await fahrtMs(k.seite, 'dayblatt');
      const offen = await stand(k.seite, 'dayblatt');
      await k.bild('mr-schreibtisch-ohne-fahrt');
      await k.seite.keyboard.press('Escape');
      await k.seite.waitForTimeout(60);
      const gleichDanach = await stand(k.seite, 'dayblatt');
      const ok = ms === 0 && offen && !offen.hidden
              && offen.flaeche === ORANGE_HELL
              && gleichDanach && gleichDanach.hidden === true;
      return {
        ist: `Fahrtdauer ${ms} ms · offen hidden=${offen ? offen.hidden : '—'}, `
           + `Fläche ${offen ? offen.flaeche : '—'} · 60 ms nach Escape hidden=`
           + `${gleichDanach ? gleichDanach.hidden : '—'}`,
        ok,
        bemerkung: ok ? ''
          : (ms !== 0 ? 'Am Schreibtisch wird gefahren — der 1024er Block greift nicht'
            : 'Das Aufklappmenü steht nach dem Schließen noch offen'),
      };
    },
  },
];
