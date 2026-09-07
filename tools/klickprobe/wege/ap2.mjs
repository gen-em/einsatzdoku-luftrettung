/* Wege des Arbeitspakets AP2 — Geocoder und Kartendialog (E-S9-05, E-S9-06).
 * ===========================================================================
 *
 *   P-05  Dialog aus fuenf Einbauorten                  Soll 5 von 5
 *   P-05  Treffer setzt das Kreuz, uebernimmt nichts
 *   P-07  Spur im Dialog, Karte auf der Spur bei leerem Feld
 *   P-06  Kontoschalter aus -> 0 Anfragen an den Dienst
 *   P-32  Installationsschalter aus -> Kontoschalter ausgegraut
 *   P-32  Hinweis am Ortsfeld: einmal je Seite, mit dem Namen des Dienstes
 *
 * ZWEI DIESER WEGE SCHALTEN EINSTELLUNGEN UM und stellen sie im `finally`
 * zurueck — ein Fehlschlag darf den Pruefstand nicht in einem Zustand
 * hinterlassen, den der naechste Lauf fuer den Normalfall haelt. Geschaltet
 * wird ueber die Formulare (`k.schalterKonto`, `k.schalterInstallation`),
 * nicht per SQL: Gemessen werden soll die Wirkung des Schalters, nicht die
 * einer Spalte.
 *
 * ZWEI EINSAETZE, NICHT EINER. Der manuelle Abfahrtort steht nur im Formular
 * eines Einsatzes OHNE Aufzeichnung (einsatz_form.php: `if (!$hatTrack)`),
 * die Spur im Dialog nur bei einem MIT. Ein Weg, der beides am selben Einsatz
 * suchte, meldete „4 von 5" und haette nichts gefunden, was fehlt. Die beiden
 * Kennungen werden deshalb aus der Tagesuebersicht geholt (`dayMissions`) und
 * nicht in diese Datei geschrieben — ein anderer Bestand hat andere Nummern.
 */

/* ---- Die beiden Einsaetze, einmal je Lauf --------------------------------- */
let EINSAETZE = null;

async function einsaetze(k) {
  if (EINSAETZE) { return EINSAETZE; }
  await k.gehZu(k.kennung.tagesuebersicht || (k.basis + '/index.php'));
  await k.seite.waitForFunction(
    () => typeof dayMissions !== 'undefined' && dayMissions.length,
    null, { timeout: 20000 });
  const l = await k.seite.evaluate(() => dayMissions.map(
    m => ({ id: m.id, spur: (m.track || []).length })));
  const mit  = l.find(m => m.spur > 1);
  const ohne = l.find(m => m.spur <= 1);
  if (!mit || !ohne) {
    throw new Error('Der Bestand hat nicht beides — Einsatz mit Aufzeichnung '
      + `(${mit ? mit.id : 'keiner'}) und ohne (${ohne ? ohne.id : 'keiner'}). `
      + 'Referenzbestand neu einspielen.');
  }
  EINSAETZE = { mitSpur: mit.id, ohneSpur: ohne.id, punkte: mit.spur };
  return EINSAETZE;
}

const formular = (k, id) => `${k.basis}/einsatz_form.php?id=${id}`;

/* ---- Die fuenf Einbauorte des EINEN Dialogs (M-S9-04, Anmerkung 6) --------
 *
 * Sie stehen auf drei Seiten und in zwei Rollen, und jeder bringt seinen Weg
 * dorthin selbst mit — samt dem Griff, der ihn ueberhaupt sichtbar macht. */
const EINBAUORTE = [
  {
    name: 'Einsatzort', rolle: 'demo', praefix: 'loc',
    async auf(k) { const e = await einsaetze(k); await k.gehZu(formular(k, e.mitSpur)); },
  },
  {
    name: 'Manueller Abfahrtort', rolle: 'demo', praefix: 'start',
    async auf(k) {
      const e = await einsaetze(k);
      await k.gehZu(formular(k, e.ohneSpur));
      /* Der Block haengt an der Auswahl „Manueller Ort" — ohne sie steht er
       * mit `hidden` da. */
      await k.seite.selectOption('#start_src', 'manual');
      await k.seite.waitForTimeout(200);
    },
  },
  {
    name: 'Transportziel', rolle: 'demo', praefix: 'f_transport_dest_',
    async auf(k) {
      const e = await einsaetze(k);
      await k.gehZu(formular(k, e.mitSpur));
      await transportzielAuf(k.seite);
    },
  },
  {
    name: 'Standort (Konto)', rolle: 'demo', praefix: 'sdbase',
    async auf(k) { await k.gehZu(k.basis + '/einstellungen.php?t=standorte'); },
  },
  {
    name: 'Standort (systemweit)', rolle: 'admin', praefix: 'adbase',
    async auf(k) { await k.gehZu(k.basis + '/admin_stammdaten.php'); },
  },
];

/** Das Transportziel sichtbar machen: `show_if` haengt an der Transportart. */
async function transportzielAuf(seite) {
  await seite.evaluate(() => {
    const s = document.querySelector('select[name="f_transport_mode"]');
    if (s && s.value === 'ambulant') {
      s.value = 'air';
      s.dispatchEvent(new Event('change', { bubbles: true }));
    }
  });
  await seite.waitForTimeout(200);
}

/** Den Kartendialog ueber Pin-Knopf und Blatt oeffnen. */
async function dialogOeffnen(k, praefix) {
  const pin = k.seite.locator(`[data-blatt="${praefix}ortsblatt"]`).first();
  if (!(await pin.count())) { return null; }
  await pin.scrollIntoViewIfNeeded();
  await pin.click();
  await k.seite.waitForTimeout(250);
  const zeile = k.seite
    .locator(`[data-ortswahl="karte"][data-praefix="${praefix}"]`).first();
  if (!(await zeile.count())) { return null; }
  await zeile.click();
  await k.seite.waitForSelector('dialog.dialog-karte[open]', { timeout: 8000 });
  await k.seite.waitForTimeout(700);          // Leaflet baut auf
  return k.seite.locator('dialog.dialog-karte');
}

async function dialogSchliessen(seite) {
  const zu = seite.locator('dialog.dialog-karte [data-act="zu"]');
  if (await zu.count()) { await zu.first().click(); }
  await seite.waitForTimeout(250);
}

/* Eine bestaetigte Koordinate laesst die Adresssuche ruhen und den Dialog auf
 * sich stehen (ortsfeld.js). Der Weg zurueck fuehrt ueber das Kreuz am Chip —
 * ohne ihn misst die Probe an einem bearbeiteten Einsatz einen Ausgangszustand,
 * den ein frisch nachgetragener Einsatz nie hat. */
async function ortLeeren(k, praefix) {
  const x = k.seite.locator(`#${praefix}chips .rmx`);
  if (await x.count()) { await x.first().click(); await k.seite.waitForTimeout(200); }
  await k.seite.fill(`#${praefix}addr`, '');
  await k.seite.waitForTimeout(150);
}

export const wege = [
  {
    name: 'ap2-dialog-fuenf-einbauorte',
    paket: 'AP2', punkt: 'P-05', rolle: 'demo',
    was: 'Der Kartendialog öffnet aus allen fünf Einbauorten, mit Karte darin',
    soll: '5 von 5',
    async fahren(k) {
      let auf = 0;
      const notiz = [];
      for (const ort of EINBAUORTE) {
        const kk = await k.rolle(ort.rolle);
        try {
          await ort.auf(kk);
          const dlg = await dialogOeffnen(kk, ort.praefix);
          if (!dlg) { notiz.push(ort.name + ': kein Pin-Knopf'); continue; }
          const karte = await kk.seite
            .locator('dialog.dialog-karte .leaflet-container').count();
          if (karte) { auf++; } else { notiz.push(ort.name + ': Dialog ohne Karte'); }
          await dialogSchliessen(kk.seite);
        } catch (f) {
          notiz.push(ort.name + ': '
            + (f && f.message ? f.message.split('\n')[0].slice(0, 70) : String(f)));
        }
      }
      return {
        ist: `${auf} von ${EINBAUORTE.length}`,
        ok: auf === EINBAUORTE.length,
        bemerkung: notiz.join('; '),
      };
    },
  },

  {
    name: 'ap2-treffer-setzt-nur-das-kreuz',
    paket: 'AP2', punkt: 'P-05', rolle: 'demo',
    was: 'Ein Suchtreffer im Dialog verschiebt die Karte; erst „Übernehmen" schreibt',
    soll: 'Feld leer nach dem Treffer, Koordinate nach „Übernehmen"',
    async fahren(k) {
      const e = await einsaetze(k);
      await k.gehZu(formular(k, e.mitSpur));
      await ortLeeren(k, 'loc');

      const dlg = await dialogOeffnen(k, 'loc');
      if (!dlg) { throw new Error('Dialog öffnet nicht'); }
      const suchfeld = k.seite.locator('dialog.dialog-karte [data-suche]');
      if (!(await suchfeld.count())) { throw new Error('kein Suchfeld im Dialog'); }

      await suchfeld.first().click();
      await suchfeld.first().type('Talwang', { delay: 40 });
      await k.seite.waitForTimeout(900);
      const eintrag = k.seite
        .locator('dialog.dialog-karte .vorschlaege .vorschlag').first();
      if (!(await eintrag.count())) {
        throw new Error('kein Suchtreffer im Dialog (Attrappe erreichbar?)');
      }
      /* Auch hier mit gehaltener Maus — die Liste im Dialog ist derselbe
       * Baustein wie am Ortsfeld und muss dieselbe Probe bestehen (P-01). */
      await k.haltenUndKlicken(eintrag, 300);
      await k.seite.waitForTimeout(400);

      /* NACH DEM TREFFER darf im FORMULAR nichts stehen (F1). */
      const feldNachTreffer  = await k.seite.inputValue('#locaddr');
      const chipsNachTreffer = await k.seite.locator('#locchips .rmchip').count();
      const suchfeldWert     = await suchfeld.first().inputValue();

      await k.bild('ap2-treffer-setzt-nur-das-kreuz');
      await k.seite.locator('dialog.dialog-karte [data-act="ok"]').first().click();
      await k.seite.waitForTimeout(800);
      const chipsNachOk = await k.seite.locator('#locchips .rmchip').count();

      const ok = feldNachTreffer === '' && chipsNachTreffer === 0
              && suchfeldWert !== '' && chipsNachOk === 1;
      return {
        ist: `nach Treffer: Feld „${feldNachTreffer}", ${chipsNachTreffer} Chips, `
           + `Suchfeld „${suchfeldWert.slice(0, 24)}…" · `
           + `nach Übernehmen: ${chipsNachOk} Chip`,
        ok,
        bemerkung: ok ? '' : 'ein Treffer darf nur das Kreuz setzen (F1)',
      };
    },
  },

  {
    name: 'ap2-spur-im-dialog',
    paket: 'AP2', punkt: 'P-07', rolle: 'demo',
    was: 'Die Aufzeichnung liegt im Dialog; bei leerem Feld passt die Karte auf sie',
    soll: 'Linie + Ringpunkte, Legende sichtbar, keine Pfeile',
    async fahren(k) {
      const e = await einsaetze(k);
      await k.gehZu(formular(k, e.mitSpur));
      await ortLeeren(k, 'loc');

      const dlg = await dialogOeffnen(k, 'loc');
      if (!dlg) { throw new Error('Dialog öffnet nicht'); }
      /* Die Spur kommt ueber api/mission.php nach — der Dialog wartet nicht
       * auf sie, die Probe schon. */
      await k.seite.waitForSelector('dialog.dialog-karte .leaflet-overlay-pane path',
        { timeout: 12000 }).catch(() => {});

      const b = await k.seite.evaluate(() => {
        const d = document.querySelector('dialog.dialog-karte');
        const legende = d.querySelector('[data-legende]');
        return {
          linien:  d.querySelectorAll('.leaflet-overlay-pane path').length,
          ringe:   d.querySelectorAll('.geo-ringpunkt').length,
          legende: legende ? !legende.hidden : false,
          pfeile:  d.querySelectorAll('.geo-pfeil').length,
        };
      });
      await k.bild('ap2-spur-im-dialog');
      await dialogSchliessen(k.seite);

      /* VIER RINGPUNKTE, NICHT ZWEI: zwei auf der Karte (Start und Ende) und
       * zwei in der Legende — dieselbe Klasse, derselbe Zaehler. */
      const ok = b.linien >= 1 && b.ringe >= 4 && b.legende && b.pfeile === 0;
      return {
        ist: `${b.linien} Linie(n) aus ${e.punkte} Punkten · ${b.ringe} Ringpunkte `
           + `(2 Karte + 2 Legende) · Legende ${b.legende ? 'sichtbar' : 'VERSTECKT'} · `
           + `${b.pfeile} Pfeile`,
        ok,
        bemerkung: b.pfeile
          ? 'Pfeile gehören nicht in den Auswahldialog (E-S9-06 b)' : '',
      };
    },
  },

  {
    name: 'ap2-kontoschalter-aus-keine-anfrage',
    paket: 'AP2', punkt: 'P-06', rolle: 'demo',
    was: 'Kontoschalter aus: keine Anfrage an den Adressdienst, kein Suchfeld, kein Hinweis',
    soll: 'an: >0 Anfragen · aus: 0 Anfragen, 0 Suchfelder, 0 Hinweiszeilen',
    async fahren(k) {
      /* GEZAEHLT WIRD, WAS AN DEN DIENST GEHT — und der Dienst ist der, den
       * die Installation eingetragen hat, nicht ein Name im Muster. Ein
       * `/geocod/`-Muster zaehlte `assets/geocoder.js` mit und meldete „1
       * Anfrage" fuer eine Datei aus dem eigenen Haus (gemessen 07.09.2026).
       * Der Rechnername kommt deshalb von der Seite selbst — und von einer
       * Seite MIT Ortsfeld, denn nur die traegt den Bootstrap. */
      const e0 = await einsaetze(k);
      await k.gehZu(formular(k, e0.mitSpur));
      const wirt = await k.seite.evaluate(
        () => { try { return new URL(window.GEO_DIENST).host; } catch { return ''; } });
      if (!wirt) { throw new Error('GEO_DIENST steht nicht im Dokument'); }

      let anfragen = [];
      const horcher = (r) => { if (r.url().includes(wirt)) { anfragen.push(r.url()); } };
      k.seite.on('request', horcher);

      /** Einmal tippen und einmal die Karte waehlen, und dabei mitzaehlen. */
      const durchgang = async (bild) => {
        anfragen = [];
        await k.gehZu(formular(k, e0.mitSpur));
        await ortLeeren(k, 'loc');
        await k.tippe('#locaddr', 'Talwang');
        await k.seite.waitForTimeout(1500);
        const listen = await k.seite.locator('#locsuggest .vorschlag').count();
        const hinweise = await k.seite.locator('.loc-datenschutz').count();
        const dlg = await dialogOeffnen(k, 'loc');
        const suchfelder = dlg
          ? await k.seite.locator('dialog.dialog-karte [data-suche]').count() : -1;
        if (bild) { await k.bild(bild); }
        if (dlg) {
          /* „Uebernehmen" loest die Umkehrsuche aus — der zweite der beiden
           * Wege zum Dienst. Ohne ihn misst der Durchgang nur die Haelfte. */
          await k.seite.locator('dialog.dialog-karte [data-act="ok"]').first().click();
          await k.seite.waitForTimeout(1500);
        }
        return { anfragen: anfragen.length, listen, hinweise, suchfelder };
      };

      try {
        /* GEGENPROBE ZUERST. „0 Anfragen" bei ausgeschaltetem Schalter belegt
         * nur dann etwas, wenn derselbe Weg bei eingeschaltetem Schalter
         * Anfragen zeigt — sonst misst die Probe eine Attrappe, die gar nicht
         * gefragt wird, und meldet stolz eine Null. */
        await k.schalterKonto(true);
        const an = await durchgang(null);
        await k.schalterKonto(false);
        const aus = await durchgang('ap2-kontoschalter-aus');

        const ok = an.anfragen > 0 && aus.anfragen === 0
                && aus.suchfelder === 0 && aus.hinweise === 0 && aus.listen === 0;
        return {
          ist: `an: ${an.anfragen} Anfragen an ${wirt}, ${an.listen} Vorschläge, `
             + `${an.suchfelder} Suchfeld · aus: ${aus.anfragen} Anfragen, `
             + `${aus.listen} Vorschläge, ${aus.suchfelder} Suchfelder, `
             + `${aus.hinweise} Hinweiszeilen`,
          ok,
          bemerkung: an.anfragen === 0
            ? 'Gegenprobe leer — die Probe hätte eine Anfrage gar nicht gesehen'
            : (aus.anfragen ? aus.anfragen && anfragen[0] ? String(anfragen[0]).slice(0, 80) : '' : ''),
        };
      } finally {
        k.seite.off('request', horcher);
        await k.schalterKonto(true);
      }
    },
  },

  {
    name: 'ap2-installation-graut-konto-aus',
    paket: 'AP2', punkt: 'P-32', rolle: 'demo',
    was: 'Installationsschalter aus: der Kontoschalter steht ausgegraut, mit Grund',
    soll: 'Schalter gesperrt, Grund genannt',
    async fahren(k) {
      try {
        await k.schalterInstallation(false);
        await k.gehZu(k.basis + '/einstellungen.php?t=profil');
        /* ZUM BILD SCROLLEN. Der Schuss geht auf den Sichtbereich, nicht auf
         * die ganze Seite; ohne diese Zeile zeigt der Beleg für „ausgegraut"
         * die Karte „Angaben" — eine grüne Zahl mit einem Bild, das sie nicht
         * belegt (CLAUDE.md 6). */
        await k.seite.locator('#k-datenschutz').first().scrollIntoViewIfNeeded();
        await k.seite.waitForTimeout(200);
        const b = await k.seite.evaluate(() => {
          const karte = document.getElementById('k-datenschutz');
          if (!karte) { return { fehlt: true }; }
          const box = karte.querySelector('.schalter-box');
          const p = karte.querySelector('.plakette');
          return {
            fehlt: false,
            gesperrt: !!(box && box.disabled),
            nenntInstallation: /Installation/.test(karte.textContent || ''),
            plakette: p ? p.textContent.trim() : '',
          };
        });
        await k.bild('ap2-installation-graut-konto-aus');
        if (b.fehlt) {
          return { ist: 'Karte „Datenschutz" fehlt', ok: false, bemerkung: '' };
        }
        return {
          ist: `Schalter ${b.gesperrt ? 'gesperrt' : 'BEDIENBAR'} · `
             + `Grund ${b.nenntInstallation ? 'genannt' : 'FEHLT'} · `
             + `Plakette „${b.plakette}"`,
          ok: b.gesperrt && b.nenntInstallation,
          bemerkung: '',
        };
      } finally {
        await k.schalterInstallation(true);
      }
    },
  },

  {
    name: 'ap2-hinweis-am-ortsfeld',
    paket: 'AP2', punkt: 'P-32', rolle: 'demo',
    was: 'Der Hinweis unter dem Ortsfeld steht genau einmal je Seite und nennt den Dienst',
    soll: '1 Hinweis bei mehreren Ortsfeldern, Dienstname darin',
    async fahren(k) {
      const e = await einsaetze(k);
      await k.gehZu(formular(k, e.ohneSpur));
      await k.seite.locator('.loc-datenschutz').first().scrollIntoViewIfNeeded();
      await k.seite.waitForTimeout(200);
      const b = await k.seite.evaluate(() => {
        const n = document.querySelectorAll('.loc-datenschutz');
        return {
          anzahl: n.length,
          text: n.length ? n[0].textContent.replace(/\s+/g, ' ').trim() : '',
          ortsfelder: document.querySelectorAll('.loc-widget').length,
        };
      });
      await k.bild('ap2-hinweis-am-ortsfeld');
      const ok = b.anzahl === 1 && b.ortsfelder > 1 && /photon/i.test(b.text);
      return {
        ist: `${b.anzahl} Hinweis bei ${b.ortsfelder} Ortsfeldern · `
           + `„${b.text.slice(0, 52)}…"`,
        ok,
        bemerkung: ok ? '' : 'erwartet: genau einer, mit dem Namen des Dienstes',
      };
    },
  },
];
