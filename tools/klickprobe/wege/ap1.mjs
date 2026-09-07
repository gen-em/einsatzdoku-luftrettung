/* Wege des Arbeitspakets AP1 — die eine Vorschlagsliste (E-S9-07, E-S9-08).
 * ===========================================================================
 *
 * Drei Wege, drei Prüfpunkte des Konzepts:
 *
 *   P-01  PS-2 mit gehaltener Maus            Soll 3 von 3 (vorher 0 von 3)
 *   P-03  eine Liste, Gruppenzeile, <= 2 Stammdaten
 *   P-02  keine <datalist> mehr im Dokument   Soll 0
 *
 * ALLE DREI KENNEN BEIDE FASSUNGEN — die alte (`.rmlist .rmopt`,
 * `.loc-suggest li`, `<datalist>`) und die neue (`.vorschlaege .vorschlag`).
 * Nur so entsteht die Aussage „vorher 0 von 3, nachher 3 von 3": Ein Weg, der
 * den alten Stand gar nicht bedienen kann, belegt nichts, sondern scheitert an
 * seinem eigenen Selektor.
 */

/* Beide Fassungen in einem Selektor. Die Reihenfolge im Dokument entscheidet,
 * welcher Eintrag „der erste" ist — nicht die Reihenfolge hier. */
const EINTRAG = '.vorschlaege .vorschlag, .rmlist .rmopt, .loc-suggest li';
const LISTE   = '.vorschlaege, .rmlist, .loc-suggest';

/** Sichtbare Trefferlisten zaehlen (beide Fassungen). */
async function offeneListen(seite) {
  return seite.evaluate((sel) => {
    return Array.from(document.querySelectorAll(sel)).filter(l => {
      if (l.hidden) { return false; }
      const s = getComputedStyle(l);
      if (s.display === 'none' || s.visibility === 'hidden') { return false; }
      return l.getBoundingClientRect().height > 0;
    }).length;
  }, LISTE);
}

/** Das Transportziel sichtbar machen: `show_if` haengt an der Transportart. */
async function transportzielAuf(seite) {
  await seite.evaluate(() => {
    const s = document.querySelector('select[name="f_transport_mode"]');
    if (s && s.value === 'ambulant') {
      s.value = 'air';
      s.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
  await seite.waitForTimeout(150);
}

/* STEHENDE KOORDINATEN LASSEN DIE ADRESSSUCHE RUHEN — und das ist richtig so
 * (ortsfeld.js: ein Vorschlag wuerde die bestaetigte Koordinate still
 * ueberschreiben). Der Weg zurueck fuehrt ueber das Kreuz am Chip, und genau
 * den geht diese Probe. Ohne diesen Schritt misst sie an einem bearbeiteten
 * Einsatz „0 Trefferzeilen" und haelt das faelschlich fuer einen Befund. */
async function koordinatenWeg(seite, praefix) {
  const x = seite.locator(`#${praefix}chips .rmx`);
  if (await x.count()) {
    await x.first().click();
    await seite.waitForTimeout(150);
  }
}

/* Die Besatzungsfelder des Einsatzes stehen hinter dem Haken „abweichende
 * Besatzung" (Katalog `crew_override`): Ohne ihn gilt die Tagesbesatzung, und
 * die Felder sind verborgen. */
async function besatzungAuf(seite) {
  await seite.evaluate(() => {
    /* Die Karte „Abweichende Besatzung" faengt zugeklappt an (`<details
     * class="karte-klappbar">`), und der Haken darin gilt: Ohne ihn steht die
     * Tagesbesatzung, und die Felder sind verborgen. Beides aufmachen. */
    document.querySelectorAll('details.karte-klappbar').forEach(d => { d.open = true; });
    const h = document.querySelector('input[name="f_crew_override"]');
    if (h && !h.checked) { h.click(); }
  });
  await seite.waitForTimeout(250);
}

export const wege = [
  {
    name: 'ap1-ps2-gehaltene-maus',
    paket: 'AP1', punkt: 'P-01', rolle: 'demo',
    was: 'Weitere Rettungsmittel: Vorschlag mit 300 ms gehaltener Maus übernehmen',
    soll: '3 von 3',
    async fahren(k) {
      await k.gehZu(k.kennung.formular);
      await k.seite.waitForSelector('#rminput', { timeout: 15000 });

      /* Drei Durchgaenge: einmal eine Vorbelegung des Standorts („RT" trifft
       * an beiden Standorten des Referenzbestands genau ein RTW), zweimal die
       * freie Eingabe. Beide haengen am selben Uebernahmeweg — der Unterschied
       * liegt nur in der Zeile, die getroffen wird. */
      const versuche = ['RT', 'Probe Bravo', 'Probe Charlie'];
      let geschafft = 0;
      const notiz = [];

      for (const text of versuche) {
        const vorher = await k.seite.locator('#rmchips .rmchip').count();
        await k.tippe('#rminput', text);
        await k.seite.waitForTimeout(250);
        const eintrag = k.seite.locator('.rmbox ' + EINTRAG).first();
        if (!await eintrag.count()) {
          notiz.push(`„${text}": keine Trefferzeile`);
          continue;
        }
        const beschriftung = (await eintrag.textContent() || '').trim();
        await k.haltenUndKlicken(eintrag, 300);
        const nachher = await k.seite.locator('#rmchips .rmchip').count();
        if (nachher === vorher + 1) {
          geschafft++;
        } else {
          notiz.push(`„${text}" (${beschriftung}): ${vorher} → ${nachher} Chips`);
        }
      }
      await k.bild('ap1-ps2-gehaltene-maus');
      return {
        ist: `${geschafft} von 3`,
        ok: geschafft === 3,
        bemerkung: notiz.join('; '),
      };
    },
  },

  {
    name: 'ap1-transportziel-eine-liste',
    paket: 'AP1', punkt: 'P-03', rolle: 'demo',
    was: 'Transportziel „Klin": eine Liste, Gruppenzeile, höchstens zwei Stammdaten',
    soll: '1 Liste · 2 Gruppen · ≤ 2 Stammdaten · Zeile ≥ Bedienhöhe',
    async fahren(k) {
      await k.gehZu(k.kennung.formular);
      await transportzielAuf(k.seite);
      await k.seite.waitForSelector('#f_transport_dest_addr', { timeout: 15000 });
      await koordinatenWeg(k.seite, 'f_transport_dest_');

      await k.tippe('#f_transport_dest_addr', 'Klin');
      /* Die Adressabfrage ist um 400 ms entprellt (ortsfeld.js) und laeuft
       * gegen die Attrappe — 900 ms sind reichlich und trotzdem knapp genug,
       * dass ein Ausbleiben auffaellt. */
      await k.seite.waitForTimeout(900);

      const listen = await offeneListen(k.seite);
      const zahlen = await k.seite.evaluate(() => ({
        datalisten: document.querySelectorAll('datalist').length,
        listen: document.querySelectorAll('input[list]').length,
        gruppen: document.querySelectorAll('.vorschlaege-gruppe').length,
        stammdaten: document.querySelectorAll('.vorschlag[data-art="stamm"]').length,
        adressen: document.querySelectorAll('.vorschlag[data-art="adresse"]').length,
        eintraege: document.querySelectorAll(
          '.vorschlaege .vorschlag, .rmlist .rmopt, .loc-suggest li').length,
      }));
      /* DIE ZEILENHOEHE ALS ZAHL, nicht als Behauptung: Die Liste traegt
       * `min-height: var(--knopf)` und soll damit beiden Bedienhoehen von
       * selbst folgen (R76). Gemessen wird, was dasteht. */
      const hoehe = await k.gemesseneHoehe('.vorschlaege .vorschlag');
      await k.bild('ap1-transportziel-eine-liste');

      const hoeheOk = hoehe !== null && hoehe >= k.bedienhoehe;
      const ok = listen === 1
              && zahlen.datalisten === 0 && zahlen.listen === 0
              && zahlen.gruppen === 2
              && zahlen.stammdaten > 0 && zahlen.stammdaten <= 2
              && zahlen.adressen > 0
              && hoeheOk;
      return {
        ist: `${listen} Liste(n) · ${zahlen.gruppen} Gruppen · `
           + `${zahlen.stammdaten} Stammdaten · ${zahlen.adressen} Adressen · `
           + `${zahlen.datalisten} <datalist> · Zeile ${hoehe} px`,
        ok,
        bemerkung: (zahlen.eintraege === 0 ? 'gar keine Trefferzeile; ' : '')
                 + (hoeheOk ? '' : `Zeilenhöhe unter der Bedienhöhe ${k.bedienhoehe} px`),
      };
    },
  },

  {
    name: 'ap1-besatzung-vorlage',
    paket: 'AP1', punkt: 'P-03', rolle: 'demo',
    was: 'Besatzungsfeld des Einsatzes: eigene Liste, Übernahme mit gehaltener Maus',
    soll: 'Wert gesetzt, 0 <datalist>, Zeile = Bedienhöhe',
    async fahren(k) {
      await k.gehZu(k.kennung.formular);
      await besatzungAuf(k.seite);

      /* Eine Rolle mit Vorbelegungen suchen — aus dem, was die SEITE selbst
       * mitbringt: in der neuen Fassung `SUGGEST_FELDER`, in der alten die
       * <datalist>-Eintraege. Eine feste Rolle waere geraten: welche Felder
       * ueberhaupt erscheinen, entscheidet der Rollensatz des Diensttags. */
      const ziel = await k.seite.evaluate(() => {
        const felder = Array.from(document.querySelectorAll('input[name^="f_crew_"]'))
          .filter(i => i.offsetParent !== null);
        for (const i of felder) {
          const spalte = i.name.replace(/^f_/, '');
          let namen = [];
          if (typeof SUGGEST_FELDER !== 'undefined' && SUGGEST_FELDER[spalte]) {
            namen = SUGGEST_FELDER[spalte].map(v => v.name);
          } else {
            const dl = document.getElementById('dl_' + spalte);
            if (dl) { namen = Array.from(dl.options).map(o => o.value); }
          }
          if (namen.length) { return { name: i.name, vorlage: namen[0] }; }
        }
        return null;
      });
      if (!ziel) { throw new Error('kein Besatzungsfeld mit Vorbelegungen gefunden'); }

      const anfang = ziel.vorlage.slice(0, 3);
      await k.tippe(`input[name="${ziel.name}"]`, anfang);
      await k.seite.waitForTimeout(400);

      const eintrag = k.seite.locator(EINTRAG).first();
      const gefunden = await eintrag.count();
      /* DIE EINZEILIGE ZEILE IST DIE PROBE AUF DIE BEDIENHOEHE. Am
       * Transportziel steht unter jedem Treffer eine zweite, gedaempfte Zeile
       * mit der Herkunft — die Zeile ist dort rund 51 px hoch und damit in
       * beiden Stufen dieselbe: `--knopf` ist eine UNTERGRENZE, kein Sollmass.
       * Das Besatzungsfeld hat nur die Hauptzeile; hier muss die Umschaltung
       * 44/36 (R76) tatsaechlich messbar sein. */
      const einzeilig = gefunden ? await k.gemesseneHoehe(EINTRAG) : null;
      let wert = '';
      if (gefunden) {
        await k.haltenUndKlicken(eintrag, 300);
        wert = await k.seite.inputValue(`input[name="${ziel.name}"]`);
      }
      const datalisten = await k.seite.evaluate(() => document.querySelectorAll('datalist').length);
      await k.bild('ap1-besatzung-vorlage');

      return {
        ist: gefunden
          ? `„${wert}" · ${datalisten} <datalist> · Zeile ${einzeilig} px`
          : `keine eigene Liste · ${datalisten} <datalist>`,
        ok: !!gefunden && wert === ziel.vorlage && datalisten === 0
            && einzeilig === k.bedienhoehe,
        bemerkung: `Feld ${ziel.name}, erwartet „${ziel.vorlage}"`
                 + (einzeilig !== null && einzeilig !== k.bedienhoehe
                    ? `; einzeilige Zeile ${einzeilig} px statt ${k.bedienhoehe}` : ''),
      };
    },
  },
];
