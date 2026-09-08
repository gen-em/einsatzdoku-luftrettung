/* Wege des Arbeitspakets AP5 — Standortseiten: Sprungliste, Kartenfilter,
 * Artzeichen, angesprungene Zeile.
 * ===========================================================================
 *
 *   E-S9-14 / Nr. 44   Sprungliste ab sechs Einträgen, Pille mit Artzeichen
 *   E-S9-18            Filterfeld in den langen Listen, filtert im Browser
 *   F-S9-K-04          Artzeichen in der Rettungsmittel-Zeile
 *   M-S9-05            Die angesprungene Zeile wird orange (`:target`)
 *
 * WARUM DIESE WEGE UND KEIN BILD. Die Abnahme des Konzepts verlangt für die
 * Schwelle „ab sechs" ein Bild („5 → nein, 6 → ja"). Am Referenzbestand ist
 * das nicht zu fotografieren: Der größte Standort hat **drei**
 * Rettungsmittel, und der Bilderlauf nimmt genau ihn. Ein Bild könnte also
 * nur zeigen, dass keine Sprungliste da ist — was auch dann so aussähe, wenn
 * die Schwelle bei zwanzig läge oder die Liste gar nicht gebaut wäre.
 *
 * Der Weg stellt den Fall deshalb HER: Er legt über das Formular so lange
 * Rettungsmittel an, bis fünf dastehen (keine Liste), legt eines mehr an
 * (Liste, sechs Pillen) — und räumt in `finally` alles wieder ab. Damit ist
 * die Schwelle in BEIDE Richtungen belegt, mit Zahlen, und der
 * Referenzbestand steht danach wie vorher. Dasselbe Muster wie in `ap4.mjs`
 * und bei den Windenkacheln in AP3.
 *
 * DER FILTER WIRD BEDIENT, NICHT GELESEN. `hidden` steht im DOM auch dann,
 * wenn die Regel `[hidden]{display:none}` fehlte; gemessen wird deshalb an
 * `offsetParent`/`getClientRects()`, also an dem, was wirklich verschwindet.
 */

const LISTE = k => `${k.basis}/einstellungen.php?t=standorte`;
const SEITE = (k, bid) => `${k.basis}/einstellungen.php?t=standort&s=${bid}`;

/** Kennung des ersten Standorts — aus der Liste gelesen, nicht geraten. */
async function ersterStandort(k) {
  await k.gehZu(LISTE(k));
  const bid = await k.seite.evaluate(() => {
    const a = document.querySelector('a.zeile[href*="t=standort&s="]');
    return a ? Number(new URL(a.href, location.href).searchParams.get('s')) : null;
  });
  if (!bid) { throw new Error('Kein Standort in der Liste'); }
  return bid;
}

/** Ein Rettungsmittel über das Formular der Standortseite anlegen. */
async function anlegen(k, bid, name) {
  await k.gehZu(SEITE(k, bid));
  const form = k.seite.locator(`form.ac-form:has(input[name="base_id"][value="${bid}"])`).first();
  await form.locator('input[name="name"]').fill(name);
  await form.locator('select[name="typ"]').selectOption('standard');
  await form.locator('input[name="kind"][value="ground"]').check({ force: true });
  await Promise.all([
    k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
    form.locator('button[type="submit"]').first().click(),
  ]);
  await k.seite.waitForTimeout(200);
}

/**
 * Ein Rettungsmittel wieder abräumen — über sein verborgenes Löschformular,
 * denselben Weg, den der Knopf nach der Rückfrage geht.
 *
 * DIE FORMULARE STEHEN NEBEN DER ZEILE, nicht darin (`sd_zeile()`): die
 * Reihenfolge in `section.sd-liste` ist FORM(def), FORM(del), DIV.zeile. Ein
 * `closest('.zeile')` findet vom Formular aus nichts, und `parentElement`
 * liefert die ganze Sektion. Gesucht wird deshalb von der Zeile rückwärts.
 */
async function loeschen(k, bid, name) {
  await k.gehZu(SEITE(k, bid));
  const traf = await k.seite.evaluate((n) => {
    const zeilen = Array.from(document.querySelectorAll('section.sd-liste > .zeile'));
    const z = zeilen.find(x => (x.textContent || '').includes(n));
    if (!z) { return false; }
    for (let e = z.previousElementSibling; e; e = e.previousElementSibling) {
      if (e.tagName !== 'FORM') { break; }
      const a = e.querySelector('input[name="action"]');
      if (a && a.value === 'veh_del') { e.submit(); return true; }
    }
    return false;
  }, name);
  if (traf) { await k.seite.waitForTimeout(400); }
  return traf;
}

/** Was auf der Standortseite gerade sichtbar ist. */
const STAND = () => {
  const sicht = el => !!el && (el.offsetParent !== null || el.getClientRects().length > 0);
  const nav = document.querySelector('.sprungliste');
  return {
    rettungsmittel: document.querySelectorAll('#' + CSS.escape(
      document.querySelector('.karte#k-rettungsmittel section.sd-liste').id)
      + ' > .zeile').length,
    sprungliste: sicht(nav),
    pillen: nav ? nav.querySelectorAll('.sprungziel').length : 0,
    pillenMitZeichen: nav ? nav.querySelectorAll('.sprungziel svg.symbol').length : 0,
    pillenHoehe: nav && nav.querySelector('.sprungziel')
      ? Math.round(nav.querySelector('.sprungziel').getBoundingClientRect().height) : 0,
    zieleDa: nav ? Array.from(nav.querySelectorAll('.sprungziel'))
      .every(a => !!document.getElementById(a.getAttribute('href').slice(1))) : null,
  };
};

export const wege = [
  {
    name: 'ap5-sprungliste-ab-sechs',
    paket: 'AP5', punkt: 'E-S9-14 / Nr. 44', rolle: 'demo',
    was: 'Die Sprungliste erscheint ab sechs Rettungsmitteln und nicht darunter',
    soll: '5 → keine Liste · 6 → Liste mit 6 Pillen, jede mit Artzeichen und gültigem Ziel',
    async fahren(k) {
      const bid = await ersterStandort(k);
      /* `ersterStandort()` laesst uns auf der LISTE stehen — dort gibt es die
         Karte gar nicht. Erst die Seite oeffnen, dann messen. */
      await k.gehZu(SEITE(k, bid));
      const vorher = await k.seite.evaluate(STAND);
      const gebraucht = 6 - vorher.rettungsmittel;
      if (gebraucht < 1) {
        return { ist: `Der Standort hat schon ${vorher.rettungsmittel} Rettungsmittel — `
                    + 'der Weg kann die Schwelle nicht von unten zeigen', ok: false };
      }
      const namen = Array.from({ length: gebraucht }, (_, i) => `KP Sprung ${i + 1}`);
      try {
        /* Bis EINS UNTER die Schwelle fuellen und messen … */
        for (const n of namen.slice(0, -1)) { await anlegen(k, bid, n); }
        await k.gehZu(SEITE(k, bid));
        const bei5 = await k.seite.evaluate(STAND);
        await k.bild('ap5-sprungliste-fuenf');
        /* … dann das sechste. */
        await anlegen(k, bid, namen[namen.length - 1]);
        await k.gehZu(SEITE(k, bid));
        const bei6 = await k.seite.evaluate(STAND);
        await k.bild('ap5-sprungliste-sechs');
        const ok = bei5.rettungsmittel === 5 && bei5.sprungliste === false
                && bei6.rettungsmittel === 6 && bei6.sprungliste === true
                && bei6.pillen === 6 && bei6.pillenMitZeichen === 6
                && bei6.zieleDa === true;
        return {
          ist: `bei ${bei5.rettungsmittel}: ${bei5.sprungliste ? 'Liste' : 'keine Liste'} · `
             + `bei ${bei6.rettungsmittel}: ${bei6.sprungliste ? 'Liste' : 'keine Liste'} `
             + `mit ${bei6.pillen} Pillen, ${bei6.pillenMitZeichen} mit Artzeichen, `
             + `Ziele vorhanden: ${bei6.zieleDa}, Pillenhöhe ${bei6.pillenHoehe} px`,
          ok,
          bemerkung: ok ? '' : 'Die Schwelle greift nicht wie festgelegt',
        };
      } finally {
        for (const n of namen) { await loeschen(k, bid, n); }
      }
    },
  },

  {
    name: 'ap5-sprung-faerbt-die-zeile',
    paket: 'AP5', punkt: 'M-S9-05', rolle: 'demo',
    was: 'Ein Klick auf eine Pille springt zur Zeile und färbt sie',
    soll: 'Zeile trägt Orange-hell, Adresse endet auf #veh-<id>, Pille wird nicht doppelt gefärbt',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      const vorher = await k.seite.evaluate(STAND);
      const gebraucht = 6 - vorher.rettungsmittel;
      const namen = Array.from({ length: Math.max(0, gebraucht) },
                               (_, i) => `KP Ziel ${i + 1}`);
      try {
        for (const n of namen) { await anlegen(k, bid, n); }
        await k.gehZu(SEITE(k, bid));
        await k.seite.locator('.sprungliste .sprungziel').first().click();
        await k.seite.waitForTimeout(300);
        const mass = await k.seite.evaluate(() => {
          const hash = location.hash;
          const z = hash ? document.getElementById(hash.slice(1)) : null;
          const r = z ? z.getBoundingClientRect() : null;
          return {
            hash,
            zeileDa: !!z,
            flaeche: z ? getComputedStyle(z).backgroundColor : null,
            /* Sitzt sie unter der Kopfleiste? `scroll-padding-top` soll das
               ohne ein eigenes `scroll-margin-top` leisten. */
            oben: r ? Math.round(r.top) : null,
            kopf: Math.round(parseFloat(
              getComputedStyle(document.documentElement).getPropertyValue('--kopf'))),
          };
        });
        await k.bild('ap5-sprung-faerbt-die-zeile');
        /* --orange-hell ist #FFEBD6 = rgb(255, 235, 214). */
        const orange = /rgb\(255,\s*235,\s*214\)/.test(mass.flaeche || '');
        const ok = /^#veh-\d+$/.test(mass.hash) && mass.zeileDa && orange
                && mass.oben !== null && mass.oben >= mass.kopf;
        return {
          ist: `Adresse „${mass.hash}" · Zeile gefunden: ${mass.zeileDa} · `
             + `Fläche ${mass.flaeche} · Oberkante ${mass.oben} px `
             + `(Kopfleiste ${mass.kopf} px)`,
          ok,
          bemerkung: ok ? ''
            : (orange ? 'Die Zeile sitzt hinter der Kopfleiste'
                      : 'Die angesprungene Zeile ist nicht hervorgehoben'),
        };
      } finally {
        for (const n of namen) { await loeschen(k, bid, n); }
      }
    },
  },

  {
    name: 'ap5-filter-blendet-aus',
    paket: 'AP5', punkt: 'E-S9-18', rolle: 'demo',
    was: 'Das Filterfeld der Besatzung blendet Zeilen, Zwischentitel und Anlegen-Formulare aus',
    soll: 'Treffer < Gesamt · leere Rollen weg · 0 Formulare · Leerzustand bei 0 Treffern · Leeren stellt alles her',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      const zaehl = () => k.seite.evaluate(() => {
        const box = document.querySelector('.kartenfilter[data-kartenfilter]');
        if (!box) { return null; }
        const c = document.getElementById(box.getAttribute('data-kartenfilter'));
        const sicht = el => el.offsetParent !== null || el.getClientRects().length > 0;
        return {
          zeilen: Array.from(c.querySelectorAll(':scope > .zeile')).filter(sicht).length,
          titel: Array.from(c.querySelectorAll(':scope > .sd-rolle')).filter(sicht).length,
          formulare: Array.from(c.querySelectorAll('.listen-form')).filter(sicht).length,
          leer: sicht(document.querySelector('.kartenfilter-leer')),
          kreuz: sicht(document.querySelector('.kartenfilter-x')),
        };
      });
      const start = await zaehl();
      if (!start) { return { ist: 'Kein Filterfeld auf der Seite', ok: false }; }
      const feld = k.seite.locator('.kartenfilter input').first();
      /* Ein Wortteil, den es im Referenzbestand genau einmal gibt. */
      await feld.fill('kro');
      await k.seite.waitForTimeout(150);
      const treffer = await zaehl();
      await k.bild('ap5-filter-treffer');
      await feld.fill('zzzz');
      await k.seite.waitForTimeout(150);
      const nichts = await zaehl();
      await k.bild('ap5-filter-leerzustand');
      /* Escape leert das Feld — derselbe Weg wie das Kreuz. */
      await feld.press('Escape');
      await k.seite.waitForTimeout(150);
      const zurueck = await zaehl();
      const ok = treffer.zeilen > 0 && treffer.zeilen < start.zeilen
              && treffer.titel < start.titel && treffer.formulare === 0
              && treffer.kreuz && !treffer.leer
              && nichts.zeilen === 0 && nichts.titel === 0 && nichts.leer
              && zurueck.zeilen === start.zeilen && zurueck.titel === start.titel
              && zurueck.formulare === start.formulare && !zurueck.kreuz;
      return {
        ist: `vorher ${start.zeilen} Zeilen / ${start.titel} Rollen / ${start.formulare} Formulare · `
           + `„kro" ${treffer.zeilen} / ${treffer.titel} / ${treffer.formulare} · `
           + `„zzzz" ${nichts.zeilen} / ${nichts.titel} / Leerzustand ${nichts.leer} · `
           + `nach Escape ${zurueck.zeilen} / ${zurueck.titel} / ${zurueck.formulare}`,
        ok,
        bemerkung: ok ? '' : 'Der Filter blendet nicht das aus, was er soll — oder stellt es nicht wieder her',
      };
    },
  },

  {
    name: 'ap5-artzeichen-in-der-zeile',
    paket: 'AP5', punkt: 'F-S9-K-04', rolle: 'demo',
    was: 'Jede Rettungsmittel-Zeile trägt ihr Artzeichen links, mit Typ',
    soll: 'so viele Zeichen wie Zeilen, jedes mit Textalternative',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      const mass = await k.seite.evaluate(() => {
        const c = document.querySelector('.karte#k-rettungsmittel section.sd-liste');
        const zeilen = Array.from(c.querySelectorAll(':scope > .zeile'));
        return {
          zeilen: zeilen.length,
          mitZeichen: zeilen.filter(z => z.querySelector('.zeile-vorn svg')).length,
          alternativen: zeilen.map(z => {
            const t = z.querySelector('.zeile-vorn svg title');
            return t ? t.textContent.trim() : null;
          }),
        };
      });
      await k.bild('ap5-artzeichen-in-der-zeile');
      const ok = mass.zeilen > 0 && mass.mitZeichen === mass.zeilen
              && mass.alternativen.every(a => a && a.length > 0);
      return {
        ist: `${mass.mitZeichen} von ${mass.zeilen} Zeilen mit Artzeichen · `
           + `Textalternativen: ${mass.alternativen.map(a => `„${a}"`).join(', ')}`,
        ok,
        bemerkung: ok ? '' : 'Eine Zeile ohne Zeichen oder ein Zeichen ohne Textalternative',
      };
    },
  },
];
