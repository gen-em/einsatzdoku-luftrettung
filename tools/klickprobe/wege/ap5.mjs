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

/** Ein Rettungsmittel über den Dialog der Standortseite anlegen (S9/AP5-4). */
async function anlegen(k, bid, name) {
  await k.gehZu(SEITE(k, bid));
  await k.seite.click('.karte-aktion[data-dialog="dlg-veh"]');
  await k.seite.waitForTimeout(150);
  const dlg = k.seite.locator('#dlg-veh');
  await dlg.locator('input[name="name"]').fill(name);
  await dlg.locator('select[name="typ"]').selectOption('standard');
  await dlg.locator('input[name="kind"][value="ground"]').check({ force: true });
  await Promise.all([
    k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
    dlg.locator('[data-fuell="knopf"]').click(),
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
async function loeschenAllg(k, bid, name, aktion) {
  await k.gehZu(SEITE(k, bid));
  const traf = await k.seite.evaluate(([n, a]) => {
    const zeilen = Array.from(document.querySelectorAll('section.sd-liste > .zeile'));
    const z = zeilen.find(x => (x.textContent || '').includes(n));
    if (!z) { return false; }
    for (let e = z.previousElementSibling; e; e = e.previousElementSibling) {
      if (e.tagName !== 'FORM') { break; }
      const f = e.querySelector('input[name="action"]');
      if (f && f.value === a) { e.submit(); return true; }
    }
    return false;
  }, [name, aktion]);
  if (traf) { await k.seite.waitForTimeout(400); }
  return traf;
}

/** Der Regelfall: ein Rettungsmittel. */
const loeschen = (k, bid, name) => loeschenAllg(k, bid, name, 'veh_del');

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
    was: 'Das Filterfeld der Besatzung blendet Zeilen und Zwischentitel aus — nicht den Anlegen-Weg',
    soll: 'Treffer < Gesamt · leere Rollen weg · „Anlegen" bleibt · Leerzustand bei 0 Treffern · Leeren stellt alles her',
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
          /* DIE FORMULARE IN DER LISTE SIND MIT S9/AP5-4 ENTFALLEN, und eine
             Zahl, die nur noch „0 von 0" sagen kann, ist kein Beleg. Gezählt
             wird deshalb, was an ihre Stelle getreten ist: der Öffner im
             Kartenkopf. Er steht AUSSERHALB der gefilterten Liste und muss
             sichtbar bleiben — sonst käme man aus einem leeren Filterergebnis
             nicht zum Anlegen. Die Formularzahl bleibt daneben stehen, als
             Wächter: Steigt sie je wieder über 0, filtert die Regel in
             `kartenfilter.js` wieder etwas Echtes, und der Weg misst es. */
          formulare: Array.from(c.querySelectorAll('.listen-form')).filter(sicht).length,
          anlegen: sicht(document.querySelector('.karte#k-besatzung .karte-aktion[data-dialog]')),
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
              && treffer.anlegen && nichts.anlegen
              && treffer.kreuz && !treffer.leer
              && nichts.zeilen === 0 && nichts.titel === 0 && nichts.leer
              && zurueck.zeilen === start.zeilen && zurueck.titel === start.titel
              && zurueck.formulare === start.formulare && !zurueck.kreuz;
      return {
        ist: `vorher ${start.zeilen} Zeilen / ${start.titel} Rollen / ${start.formulare} Formulare · `
           + `„kro" ${treffer.zeilen} / ${treffer.titel} / ${treffer.formulare}, „Anlegen" ${treffer.anlegen} · `
           + `„zzzz" ${nichts.zeilen} / ${nichts.titel} / Leerzustand ${nichts.leer}, „Anlegen" ${nichts.anlegen} · `
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

  /* ---- Die Dialoge (S9/AP5-4, E-S9-19, M-S9-07) --------------------------
   *
   * WARUM DIESE VIER WEGE UND KEIN BILD. Ein Bild zeigt einen offenen Dialog.
   * Es zeigt nicht, ob der Öffner ihn richtig gefüllt hat, ob ein abgelehntes
   * Speichern die Eingabe behält, ob der Typ die Felder umschaltet — und es
   * zeigt schon gar nicht, dass die GESCHLOSSENEN Dialoge geschlossen sind.
   * Genau das war F-S9-U-27: `.dialog{display:flex}` schlug die
   * Browservorgabe `dialog:not([open]){display:none}`, und alle fünf Dialoge
   * standen als Kästen am Seitenende — sichtbar erst auf einem
   * Vollseitenbild, unsichtbar in jedem Fensterausschnitt.
   */
  {
    name: 'ap5-dialoge-sind-zu',
    paket: 'AP5', punkt: 'F-S9-U-27', rolle: 'demo',
    was: 'Die Dialoge der Standortseite stehen im Markup und sind geschlossen unsichtbar',
    soll: 'jeder Dialog display:none und ohne Fläche; nach dem Öffnen genau einer sichtbar',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      const zu = await k.seite.evaluate(() =>
        Array.from(document.querySelectorAll('dialog.dialog')).map(d => ({
          id: d.id, offen: d.open, display: getComputedStyle(d).display,
          flaeche: d.getClientRects().length > 0,
        })));
      await k.seite.click('.karte-aktion[data-dialog="dlg-veh"]');
      await k.seite.waitForTimeout(150);
      const auf = await k.seite.evaluate(() =>
        Array.from(document.querySelectorAll('dialog.dialog'))
          .filter(d => d.getClientRects().length > 0).map(d => d.id));
      const ok = zu.length >= 4 && zu.every(d => !d.offen && d.display === 'none' && !d.flaeche)
              && auf.length === 1 && auf[0] === 'dlg-veh';
      return {
        ist: `${zu.length} Dialoge im Markup, davon sichtbar ${zu.filter(d => d.flaeche).length}; `
           + `nach dem Öffnen sichtbar: ${auf.join(', ') || 'keiner'}`,
        ok,
        bemerkung: ok ? '' : 'Ein geschlossener Dialog nimmt Platz ein — display gehört an [open]',
      };
    },
  },

  {
    name: 'ap5-dialog-anlegen-landet-auf-der-zeile',
    paket: 'AP5', punkt: 'E-S9-19', rolle: 'demo',
    was: 'Anlegen im Dialog führt auf die neue Zeile, hervorgehoben, ohne zusätzliche Meldung',
    soll: 'Adresse endet auf #td-<id> · Zeile orange-hell · keine Meldung am Seitenkopf',
    async fahren(k) {
      const bid = await ersterStandort(k);
      const NAME = 'KP Zielklinik Dialog';
      try {
        await k.gehZu(SEITE(k, bid));
        await k.seite.click('.karte-aktion[data-dialog="dlg-td"]');
        await k.seite.waitForTimeout(150);
        const kopf = await k.seite.evaluate(() => ({
          titel: document.querySelector('#dlg-td h2').textContent.trim(),
          unter: document.querySelector('#dlg-td .unterzeile').textContent.trim(),
          knopf: document.querySelector('#dlg-td [data-fuell="knopf"]').textContent.trim(),
        }));
        await k.seite.fill('#dlg-td input[name="name"]', NAME);
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
          k.seite.click('#dlg-td [data-fuell="knopf"]'),
        ]);
        await k.seite.waitForTimeout(250);
        const mass = await k.seite.evaluate(() => {
          const z = document.querySelector('.zeile:target');
          return {
            hash: location.hash,
            gefunden: !!z,
            text: z ? z.textContent.replace(/\s+/g, ' ').trim() : '',
            flaeche: z ? getComputedStyle(z).backgroundColor : '',
            meldungen: document.querySelectorAll('.meldung').length,
          };
        });
        await k.bild('ap5-dialog-landung');
        const orange = /rgb\(255,\s*235,\s*214\)/.test(mass.flaeche);
        const ok = /^#td-\d+$/.test(mass.hash) && mass.gefunden && orange
                && mass.text.includes(NAME) && mass.meldungen === 0
                && kopf.titel === 'Zielklinik anlegen' && kopf.knopf === 'Anlegen'
                && kopf.unter.startsWith('Standort ');
        return {
          ist: `Dialog „${kopf.titel}" / „${kopf.unter}" / Knopf „${kopf.knopf}" → `
             + `Adresse „${mass.hash}", Zeile gefunden: ${mass.gefunden}, `
             + `Fläche ${mass.flaeche || '—'}, Meldungen am Seitenkopf: ${mass.meldungen}`,
          ok,
          bemerkung: ok ? '' : 'Die Landung nach dem Anlegen stimmt nicht (E-S9-19)',
        };
      } finally {
        await loeschenAllg(k, bid, NAME, 'td_del');
      }
    },
  },

  {
    name: 'ap5-dialog-fehler-bleibt-im-dialog',
    paket: 'AP5', punkt: 'E-S9-19', rolle: 'demo',
    was: 'Ein abgelehntes Speichern lässt den Dialog offen, mit Meldung UND der Eingabe',
    soll: 'Dialog offen · Meldung im Dialog · 0 Meldungen am Seitenkopf · eingegebener Name steht noch da',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      /* Eine Dublette herstellen: der Name der ersten vorhandenen Zielklinik.
         Der Server weist sie ab — dieselbe Ablehnung, die eine NutzerIn
         bekommt, ohne dass die Probe eine Bedingung erfindet. */
      const vorhanden = await k.seite.evaluate(() => {
        const z = document.querySelector('.karte#k-zielkliniken section.sd-liste > .zeile');
        if (!z) { return null; }
        const t = z.querySelector('.zeile-text, strong, b');
        return (t ? t.textContent : z.textContent).trim().split('\n')[0].trim();
      });
      if (!vorhanden) { return { ist: 'Keine Zielklinik im Bestand', ok: false }; }
      await k.seite.click('.karte-aktion[data-dialog="dlg-td"]');
      await k.seite.waitForTimeout(150);
      await k.seite.fill('#dlg-td input[name="name"]', vorhanden);
      await Promise.all([
        k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
        k.seite.click('#dlg-td [data-fuell="knopf"]'),
      ]);
      await k.seite.waitForTimeout(300);
      const mass = await k.seite.evaluate(() => {
        const d = document.getElementById('dlg-td');
        return {
          offen: d.open,
          imDialog: (d.querySelector('.meldung')?.textContent || '').replace(/\s+/g, ' ').trim(),
          amKopf: Array.from(document.querySelectorAll('.meldung'))
            .filter(m => !m.closest('dialog')).length,
          name: d.querySelector('input[name="name"]').value,
        };
      });
      await k.bild('ap5-dialog-fehler');
      const ok = mass.offen && mass.imDialog !== '' && mass.amKopf === 0
              && mass.name === vorhanden;
      return {
        ist: `Dialog offen: ${mass.offen} · Meldung im Dialog: „${mass.imDialog}" · `
           + `Meldungen am Seitenkopf: ${mass.amKopf} · Feld trägt noch „${mass.name}"`,
        ok,
        bemerkung: ok ? '' : 'Der Fehlerweg verlässt den Dialog — dann ist die Eingabe weg',
      };
    },
  },

  {
    name: 'ap5-dialog-typ-steuert-die-felder',
    paket: 'AP5', punkt: 'E-S9-09 / E-S9-19', rolle: 'demo',
    was: 'Der Typ schaltet Betriebsart, Rollen, Fähigkeiten und Standortauswahl um',
    soll: 'Standard+Luft: 5 Rollen, Fähigkeiten an, keine Standortauswahl · Veranstaltung: Luft gesperrt, 0 Rollen, Standortauswahl an',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      await k.seite.click('.karte-aktion[data-dialog="dlg-veh"]');
      await k.seite.waitForTimeout(150);
      const lies = () => k.seite.evaluate(() => {
        const f = document.querySelector('#dlg-veh form');
        const sicht = (s) => { const e = f.querySelector(s); return !!e && e.getClientRects().length > 0; };
        return {
          rollen: Array.from(f.querySelectorAll('.rollehaken')).filter(l => l.getClientRects().length).length,
          rollenZeile: sicht('.rollen-zeile'),
          caps: sicht('.vehcaps-zeile'),
          luftGesperrt: f.querySelector('.vehkind-radio[value="air"]').disabled,
          bodenAn: f.querySelector('.vehkind-radio[value="ground"]').checked,
          festHinweis: sicht('[data-veh-fest]'),
          ohneVorlagen: sicht('[data-veh-ohne-vorlagen]'),
          standortWahl: sicht('#dlgveh-base'),
          heimatSatz: sicht('[data-veh-heimat]'),
        };
      });
      await k.seite.check('#dlg-veh .vehkind-radio[value="air"]', { force: true });
      await k.seite.waitForTimeout(120);
      const standard = await lies();
      await k.bild('ap5-dialog-typ-standard');
      await k.seite.selectOption('#dlgveh-typ', 'veranstaltung');
      await k.seite.waitForTimeout(120);
      const veranstaltung = await lies();
      await k.bild('ap5-dialog-typ-veranstaltung');
      const ok = standard.rollen === 5 && standard.rollenZeile && standard.caps
              && !standard.luftGesperrt && !standard.festHinweis
              && !standard.ohneVorlagen && !standard.standortWahl && standard.heimatSatz
              && veranstaltung.luftGesperrt && veranstaltung.bodenAn
              && veranstaltung.festHinweis && veranstaltung.rollen === 0
              && !veranstaltung.rollenZeile && !veranstaltung.caps
              && veranstaltung.ohneVorlagen && veranstaltung.standortWahl
              && !veranstaltung.heimatSatz;
      return {
        ist: `Standard+Luft: ${standard.rollen} Rollen, Fähigkeiten ${standard.caps}, `
           + `Standortauswahl ${standard.standortWahl}, Standortsatz ${standard.heimatSatz} · `
           + `Veranstaltung: Luft gesperrt ${veranstaltung.luftGesperrt}, Boden gesetzt `
           + `${veranstaltung.bodenAn}, ${veranstaltung.rollen} Rollen, Fähigkeiten `
           + `${veranstaltung.caps}, Standortauswahl ${veranstaltung.standortWahl}`,
        ok,
        bemerkung: ok ? '' : 'Die Umschaltung nach Typ stimmt nicht (E-S9-09)',
      };
    },
  },

  {
    name: 'ap5-dialog-bearbeiten-ist-vorbelegt',
    paket: 'AP5', punkt: 'E-S9-19', rolle: 'demo',
    was: '„Bearbeiten" in der Zeile füllt den Dialog aus dem Öffner — ohne die Seite neu zu laden',
    soll: 'jedes Feld trägt den Wert der Zeile; Titel „bearbeiten", Knopf „Änderung speichern"; keine Navigation',
    async fahren(k) {
      const bid = await ersterStandort(k);
      await k.gehZu(SEITE(k, bid));
      const vorher = await k.seite.evaluate(() => location.href);
      const soll = await k.seite.evaluate(() => {
        const a = document.querySelector('#k-rettungsmittel a[data-dialog="dlg-veh"]:not(.karte-aktion)');
        if (!a) { return null; }
        return { id: a.dataset.wId, name: a.dataset.wName, kurz: a.dataset.wKurz,
                 typ: a.dataset.wTyp, kind: a.dataset.wKind,
                 rollen: a.dataset.wRollen, caps: a.dataset.wCaps };
      });
      if (!soll) { return { ist: 'Kein Bearbeiten-Öffner in der Rettungsmittel-Karte', ok: false }; }
      await k.seite.click('#k-rettungsmittel a[data-dialog="dlg-veh"]:not(.karte-aktion)');
      await k.seite.waitForTimeout(200);
      const ist = await k.seite.evaluate(() => {
        const f = document.querySelector('#dlg-veh form');
        const menge = (s) => Array.from(f.querySelectorAll(s + ':checked')).map(i => i.value).sort().join(',');
        return {
          offen: document.getElementById('dlg-veh').open,
          titel: document.querySelector('#dlg-veh h2').textContent.trim(),
          knopf: document.querySelector('#dlg-veh [data-fuell="knopf"]').textContent.trim(),
          id: f.id.value, name: f.name.value, kurz: f.kurz.value, typ: f.typ.value,
          kind: (f.querySelector('.vehkind-radio:checked') || {}).value || '',
          rollen: menge('input[name="roles[]"]'), caps: menge('input[name="caps[]"]'),
          adresse: location.href,
        };
      });
      const sortiert = (s) => (s || '').split(',').filter(Boolean).sort().join(',');
      const ok = ist.offen && ist.adresse === vorher
              && ist.titel === 'Rettungsmittel bearbeiten'
              && ist.knopf === 'Änderung speichern'
              && ist.id === soll.id && ist.name === soll.name && ist.kurz === soll.kurz
              && ist.typ === soll.typ && ist.kind === soll.kind
              && ist.rollen === sortiert(soll.rollen) && ist.caps === sortiert(soll.caps);
      await k.bild('ap5-dialog-bearbeiten');
      return {
        ist: `„${ist.titel}" / Knopf „${ist.knopf}" · Kennung ${ist.id} · Name „${ist.name}" · `
           + `Kurzname „${ist.kurz}" · Typ ${ist.typ} · Betriebsart ${ist.kind} · `
           + `Rollen ${ist.rollen || '—'} · Fähigkeiten ${ist.caps || '—'} · `
           + `Adresse unverändert: ${ist.adresse === vorher}`,
        ok,
        bemerkung: ok ? '' : 'Der Öffner füllt den Dialog nicht vollständig',
      };
    },
  },

  {
    name: 'ap5-verwaltung-besatzung-anlegen',
    paket: 'AP5', punkt: 'Backlog Nr. 163', rolle: 'admin',
    was: 'Eine systemweite Besatzungs-Vorbelegung lässt sich anlegen (seit Web 9.10.0 unmöglich)',
    soll: 'Landung auf #crew-<id>, Zeile orange-hell, 0 Fehlermeldungen',
    async fahren(k) {
      /* DER FALL WIRD HERGESTELLT: Der Referenzbestand hat keinen einzigen
         systemweiten Standort, und ohne ihn gibt es nichts anzulegen. Der
         Weg legt Standort und Rettungsmittel an, prüft, und löscht den
         Standort wieder — die Kaskade nimmt alles mit. */
      const BASE = 'KP Verwaltungsstandort';
      const LISTE = `${k.basis}/admin_stammdaten.php?t=standorte`;
      let href = null;
      try {
        await k.gehZu(LISTE);
        await k.seite.fill('#adbase-name', BASE);
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
          k.seite.click('.listen-form button[type="submit"]'),
        ]);
        await k.seite.waitForTimeout(250);
        /* „Standort anlegen" landet auf der neuen Seite (E-S9-19). */
        const gelandet = await k.seite.evaluate(() => location.search + location.hash);
        href = await k.seite.evaluate(() => location.pathname.split('/').pop() + location.search);

        await k.seite.click('.karte-aktion[data-dialog="dlg-veh"]');
        await k.seite.waitForTimeout(150);
        await k.seite.fill('#dlgveh-name', 'KP Prüffalke');
        await k.seite.check('#dlg-veh .vehkind-radio[value="air"]', { force: true });
        await k.seite.waitForTimeout(120);
        await k.seite.check('#dlg-veh input[name="roles[]"][value="p1"]', { force: true });
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
          k.seite.click('#dlg-veh [data-fuell="knopf"]'),
        ]);
        await k.seite.waitForTimeout(250);

        await k.seite.click('.karte-aktion[data-dialog="dlg-crew"]');
        await k.seite.waitForTimeout(150);
        await k.seite.fill('#dlgcrew-name', 'KP Prüfperson');
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
          k.seite.click('#dlg-crew [data-fuell="knopf"]'),
        ]);
        await k.seite.waitForTimeout(250);
        const mass = await k.seite.evaluate(() => {
          const z = document.querySelector('.zeile:target');
          return {
            hash: location.hash, gefunden: !!z,
            text: z ? z.textContent.replace(/\s+/g, ' ').trim() : '',
            flaeche: z ? getComputedStyle(z).backgroundColor : '',
            fehler: document.querySelectorAll('.meldung-fehler').length,
          };
        });
        await k.bild('ap5-verwaltung-besatzung');
        const orange = /rgb\(255,\s*235,\s*214\)/.test(mass.flaeche);
        const ok = /^#crew-\d+$/.test(mass.hash) && mass.gefunden && orange
                && mass.text.includes('KP Prüfperson') && mass.fehler === 0
                && /t=standort&s=\d+/.test(gelandet);
        return {
          ist: `Standort angelegt → gelandet auf „${gelandet}" · Besatzung → Adresse `
             + `„${mass.hash}", Zeile gefunden: ${mass.gefunden}, Fläche ${mass.flaeche || '—'}, `
             + `Fehlermeldungen ${mass.fehler}`,
          ok,
          bemerkung: ok ? '' : 'Die systemweite Besatzungspflege schreibt nicht (Nr. 163)',
        };
      } finally {
        if (href) {
          await k.gehZu(`${k.basis}/${href}`);
          await k.seite.evaluate(() => {
            const f = document.querySelector('form[id^="f-adbdel-"]');
            if (f) { f.submit(); }
          });
          await k.seite.waitForTimeout(500);
        }
      }
    },
  },
];
