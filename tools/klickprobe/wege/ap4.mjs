/* Wege des Arbeitspakets AP4 — Rettungsmittel: Typ, Kurzname, Standort optional.
 * ===========================================================================
 *
 *   E-S9-09  Vier Typen, ein Kurzname, der Standort wird freiwillig
 *   Nr. 69   Der Kurzname steht in der Leiste
 *   PS-10.1  Typ und Kurzname überleben Zuordnung und Rückweg
 *
 * WARUM DIESE WEGE DURCH DAS FORMULAR GEHEN UND NICHT ÜBER SQL: Die
 * Regeln je Typ (Rollen-Vorlagen nur bei „Standard", Betriebsart bei
 * „Veranstaltung" fest auf Boden, Standort nur bei „Standard" Pflicht)
 * stehen in `pruef_rettungsmittel()` — und genau dort laufen alle drei
 * Schreibwege durch. Ein SQL-INSERT beweist davon nichts.
 *
 * DIE WEGE STELLEN HER UND STELLEN ZURÜCK. Der Referenzbestand ist der
 * Vergleichsstand der Kreisläufe; ein Weg, der ein Rettungsmittel stehen
 * lässt, verschiebt ihn. Jeder Weg, der etwas anlegt, räumt in `finally`
 * wieder ab — dasselbe Muster wie bei den Windenkacheln in AP3.
 */

const STAMM = k => `${k.basis}/einstellungen.php?t=rettungsmittel`;

/** Verborgene Kennung des Standortblocks, in dem das Anlegen-Formular steht. */
async function ersterStandort(k) {
  await k.gehZu(STAMM(k));
  const bid = await k.seite.evaluate(() => {
    const f = document.querySelector('form.ac-form input[name="base_id"]');
    return f ? Number(f.value) : null;
  });
  if (!bid) { throw new Error('Kein Standortblock mit Rettungsmittel-Formular gefunden'); }
  return bid;
}

/** Ein Rettungsmittel über das Formular anlegen. Gibt seine Kennung zurück. */
async function anlegen(k, bid, { name, kurz, typ, kind, ohneStandort }) {
  await k.gehZu(STAMM(k));
  /* Der Standortblock ist zugeklappt (`<details>`); ohne Öffnen sind die
     Felder zwar im Markup, aber nicht bedienbar — und die Probe soll den
     Weg gehen, den eine Person geht. */
  await k.seite.evaluate((id) => {
    const d = document.getElementById('sd-' + id);
    if (d && d.tagName === 'DETAILS') { d.open = true; }
    document.querySelectorAll('details').forEach(e => { e.open = true; });
  }, bid);
  const form = k.seite.locator(`form.ac-form:has(input[name="base_id"][value="${bid}"])`).first();
  await form.locator('input[name="name"]').fill(name);
  await form.locator('input[name="kurz"]').fill(kurz || '');
  await form.locator('select[name="typ"]').selectOption(typ);
  if (kind) { await form.locator(`input[name="kind"][value="${kind}"]`).check({ force: true }); }
  if (ohneStandort) { await form.locator('input[name="ohne_standort"]').check({ force: true }); }
  await Promise.all([
    k.seite.waitForNavigation({ waitUntil: 'domcontentloaded' }).catch(() => {}),
    form.locator('button[type="submit"]').first().click(),
  ]);
  await k.seite.waitForTimeout(200);
}

/* WIE EINE ZEILE UND IHR LÖSCHFORMULAR ZUSAMMENFINDEN (Fund beim Bau dieser
 * Probe, 07.09.2026). Zwei Fallen stecken darin:
 *
 *   1. `sd_zeile()` legt die Formulare NEBEN die Zeile, nicht hinein — die
 *      Reihenfolge in `section.sd-liste` ist FORM(def), FORM(del), DIV.zeile.
 *      Ein `closest('.zeile')` vom Formular aus findet deshalb nichts, und
 *      wer statt dessen `parentElement` nimmt, bekommt die GANZE Sektion und
 *      damit jeden Namen darin. Die erste Fassung dieser Probe hat auf diesem
 *      Weg drei Datensätze stehen lassen.
 *   2. `form.id` liefert NICHT die Kennung des Formulars, sondern das Element
 *      `<input name="id">` darin — die HTML-Formularsammlung überschattet die
 *      Eigenschaft. Gelesen wird deshalb `getAttribute('id')`.
 *
 * Gesucht wird von der ZEILE aus rückwärts. */
const ZEILEN_LESEN = () => {
  const raus = [];
  document.querySelectorAll('section.sd-liste').forEach(sec => {
    const karte = sec.closest('details') || sec.closest('.karte');
    const titel = karte
      ? (karte.querySelector('summary')?.textContent
         || karte.querySelector('.karte-titel, h2')?.textContent || '').trim()
      : '';
    sec.querySelectorAll(':scope > .zeile').forEach(z => {
      let del = null;
      for (let e = z.previousElementSibling; e; e = e.previousElementSibling) {
        if (e.tagName !== 'FORM') { break; }
        const a = e.querySelector('input[name="action"]');
        if (a && a.value === 'veh_del') { del = e; break; }
      }
      if (!del) { return; }
      raus.push({
        id: Number(del.querySelector('input[name="id"]').value),
        karte: titel.split('\n')[0].trim(),
        formular: del.getAttribute('id'),
        text: (z.textContent || '').replace(/\s+/g, ' ').trim(),
        zeichen: z.querySelector('svg title')?.textContent
              || z.querySelector('svg')?.getAttribute('aria-label') || null,
      });
    });
  });
  return raus;
};

/** Alle Rettungsmittel der Stammdatenseite mit ihren sichtbaren Angaben. */
async function liste(k) {
  await k.gehZu(STAMM(k));
  await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
  return k.seite.evaluate(ZEILEN_LESEN);
}

/**
 * Ein Rettungsmittel wieder abräumen.
 *
 * Über das verborgene Formular der Zeile — denselben Weg, den der Knopf nach
 * der Rückfrage geht. Gibt zurück, ob etwas zu löschen war; ein Weg, der sein
 * Aufräumen nicht belegen kann, hat nicht aufgeräumt.
 */
async function loeschen(k, name) {
  await k.gehZu(STAMM(k));
  await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
  const traf = await k.seite.evaluate(([lesen, n]) => {
    // eslint-disable-next-line no-new-func
    const zeilen = new Function('return (' + lesen + ')()')();
    const z = zeilen.find(x => x.text.includes(n));
    if (!z) { return false; }
    document.getElementById(z.formular).submit();
    return true;
  }, [ZEILEN_LESEN.toString(), name]);
  if (traf) { await k.seite.waitForTimeout(400); }
  return traf;
}

export const wege = [
  {
    name: 'ap4-typen-anlegen',
    paket: 'AP4', punkt: 'E-S9-09', rolle: 'demo',
    was: 'Je ein Rettungsmittel der drei neuen Typen über das Formular anlegen',
    soll: '3 angelegt · Bergwacht behält Luft · Veranstaltung wird Boden · beide ohne Rollen',
    async fahren(k) {
      const bid = await ersterStandort(k);
      const proben = [
        { name: 'KP Bergwacht', kurz: 'KP BW', typ: 'bergwacht', kind: 'air' },
        { name: 'KP Veranstaltung', kurz: '', typ: 'veranstaltung', kind: 'air', ohneStandort: true },
        { name: 'KP Sonstiges', kurz: 'KP So', typ: 'sonstiges', kind: 'ground', ohneStandort: true },
      ];
      try {
        for (const p of proben) { await anlegen(k, bid, p); }
        const l = await liste(k);
        const treffer = proben.map(p => l.find(z => z.text.includes(p.name)) || null);
        await k.bild('ap4-typen-anlegen');
        const gefunden = treffer.filter(Boolean).length;
        /* Die Kleinzeile nennt den Typ; „keine Rollen" steht dort, wo ein
           Standard-Rettungsmittel seine Rollen aufzählt. */
        const bergwacht = treffer[0] ? treffer[0].text : '';
        const veranst   = treffer[1] ? treffer[1].text : '';
        const ohneKarte = treffer.slice(1).every(t => t && /Ohne Standort/i.test(t.karte));
        return {
          ist: `${gefunden} von 3 angelegt · Bergwacht: „${bergwacht.slice(0, 60)}" · `
             + `Veranstaltung und Sonstiges in der Karte „Ohne Standort": ${ohneKarte ? 'ja' : 'nein'}`,
          ok: gefunden === 3 && ohneKarte,
          bemerkung: gefunden === 3 ? '' : 'Nicht alle drei erschienen in der Liste',
        };
      } finally {
        for (const p of proben) { await loeschen(k, p.name); }
      }
    },
  },

  {
    name: 'ap4-veranstaltung-boden',
    paket: 'AP4', punkt: 'E-S9-09', rolle: 'demo',
    was: 'Betriebsart bei Typ „Veranstaltung" — luftgebunden gewählt, Boden gespeichert',
    soll: 'gewählt luftgebunden · gespeichert bodengebunden · 0 Fähigkeiten',
    async fahren(k) {
      const bid = await ersterStandort(k);
      const name = 'KP Fest am See';
      try {
        await anlegen(k, bid, { name, kurz: '', typ: 'veranstaltung', kind: 'air', ohneStandort: true });
        const l = await liste(k);
        const z = l.find(x => x.text.includes(name));
        /* Das Artzeichen trägt die Textalternative — sie nennt Typ UND
           Betriebsart („Veranstaltung, bodengebunden"). Daran ist die
           erzwungene Betriebsart abzulesen, ohne in die Datenbank zu sehen. */
        const alt = z ? z.zeichen : null;
        await k.bild('ap4-veranstaltung-boden');
        const ok = !!z && /bodengebunden/i.test(alt || '') && /Veranstaltung/i.test(alt || '');
        return {
          ist: `gewählt luftgebunden · Zeichen sagt „${alt || '—'}"`,
          ok,
          bemerkung: ok ? '' : 'Die Betriebsart wurde nicht auf Boden gezwungen',
        };
      } finally {
        await loeschen(k, name);
      }
    },
  },

  {
    name: 'ap4-standard-braucht-standort',
    paket: 'AP4', punkt: 'E-S9-09', rolle: 'demo',
    was: 'Typ „Standard" ohne Standort — die Prüfschicht lehnt ab und sagt warum',
    soll: '1 Fehlermeldung, die den Standort nennt · 0 angelegt',
    async fahren(k) {
      const bid = await ersterStandort(k);
      const name = 'KP Ohne Wache';
      try {
        await anlegen(k, bid, { name, kurz: '', typ: 'standard', kind: 'air', ohneStandort: true });
        const meldung = await k.seite.evaluate(() => {
          const e = document.querySelector('.meldung-fehler, .meldung.fehler, [role="alert"]');
          return e ? e.textContent.replace(/\s+/g, ' ').trim() : null;
        });
        const l = await liste(k);
        const da = l.some(z => z.text.includes(name));
        await k.bild('ap4-standard-braucht-standort');
        const nennt = /Standort/i.test(meldung || '');
        return {
          ist: `Meldung: „${(meldung || '—').slice(0, 90)}" · angelegt: ${da ? 'ja' : 'nein'}`,
          ok: nennt && !da,
          bemerkung: da ? 'Der Datensatz ist trotz fehlendem Standort entstanden' : '',
        };
      } finally {
        await loeschen(k, name);
      }
    },
  },

  {
    name: 'ap4-kurzname-in-der-leiste',
    paket: 'AP4', punkt: 'Nr. 69', rolle: 'demo',
    was: 'Die Diensttage-Leiste zeigt den Kurznamen, der Tooltip die volle Bezeichnung',
    soll: 'Nebentext = Kurzname · title = volle Bezeichnung',
    async fahren(k) {
      await k.gehZu(k.kennung.tagesuebersicht || (k.basis + '/index.php'));
      await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
      await k.seite.waitForTimeout(200);
      /* Der Referenzbestand hat ein Rettungsmittel mit Kurznamen
         („Bergwacht Hochkreuth" / „BW Hoch"); ein Diensttag trägt es nur,
         wenn er ihm zugeordnet wurde. Gemessen wird deshalb, ob die Leiste
         ÜBERHAUPT nach der Regel arbeitet: Wo ein Kurzname eingefroren ist,
         steht er im Nebentext; der title nennt weiter den vollen Namen. */
      const zeilen = await k.seite.evaluate(() => Array.from(
        document.querySelectorAll('.akkordeon-inhalt .eintrag')).map(a => ({
          neben: (a.querySelector('.eintrag-neben')?.textContent || '').trim(),
          titel: a.getAttribute('title') || '',
        })));
      await k.bild('ap4-kurzname-in-der-leiste');
      const mitNeben = zeilen.filter(z => z.neben && z.neben !== '—');
      /* Kein Nebentext darf LÄNGER sein als sein title — der Kurzname ist
         die Abkürzung, der title die Auflösung. */
      const verletzt = mitNeben.filter(z => z.titel && !z.titel.startsWith(z.neben)
                                        && z.titel.length < z.neben.length);
      return {
        ist: `${zeilen.length} Einträge, ${mitNeben.length} mit Nebentext, `
           + `${verletzt.length} bei denen der Nebentext länger ist als der Tooltip`,
        ok: zeilen.length > 0 && verletzt.length === 0,
        bemerkung: verletzt.length ? 'Nebentext und Tooltip passen nicht zusammen' : '',
      };
    },
  },

  {
    name: 'ap4-ohne-standort-sichtbar',
    paket: 'AP4', punkt: 'E-S9-18', rolle: 'demo',
    was: 'Ein Rettungsmittel ohne Standort ist auf der Stammdatenseite auffindbar',
    soll: 'Karte „Ohne Standort" mit 2 Einträgen (Referenzbestand)',
    async fahren(k) {
      const l = await liste(k);
      const ohne = l.filter(z => /Ohne Standort/i.test(z.karte));
      await k.bild('ap4-ohne-standort-sichtbar');
      return {
        ist: `${ohne.length} Rettungsmittel in der Karte „Ohne Standort" `
           + `(${ohne.map(z => z.text.split(' ')[0]).join(', ') || '—'}), `
           + `${l.length} insgesamt`,
        ok: ohne.length === 2 && l.length === 6,
        bemerkung: ohne.length === 2 ? '' : 'Soll: 2 ohne Standort, 6 insgesamt',
      };
    },
  },
  {
    name: 'ap4-zuordnen-friert-ein',
    paket: 'AP4', punkt: 'PS-10.1', rolle: 'demo',
    was: 'Einen Diensttag dem Bergwacht-Rettungsmittel zuordnen — Typ und Kurzname frieren ein',
    soll: 'Leiste zeigt „BW Hoch" · Zeichen sagt „Bergwacht, …" · danach zurückgestellt',
    async fahren(k) {
      const tag = k.kennung.tag;
      if (!tag) { throw new Error('Kein Diensttag im Bestand'); }
      const seiteAuf = async () => {
        await k.gehZu(`${k.basis}/index.php?d=${tag}`);
        /* Das Zuordnungsformular ist zunächst `hidden` und wird von
           `api/day.php` gefüllt — vorher steht in `#vehsel` nichts. */
        await k.seite.waitForFunction(
          () => { const s = document.getElementById('vehsel');
                  return s && s.options.length > 1; }, null, { timeout: 20000 });
        /* UND ES IST ZUGEKLAPPT (E-P3-31): Die Karte zeigt den Lesezustand,
           „Bearbeiten" klappt das Formular auf. Ohne diesen Klick sind die
           Felder im Markup, aber nicht bedienbar — Playwright wartet dann
           sechzig Mal auf ein Element, das sichtbar werden soll und nicht
           wird. Die Probe geht denselben Weg wie eine Person. */
        if (await k.seite.evaluate(() => document.getElementById('dayform').hidden)) {
          await k.seite.locator('#tagdatenknopf').click();
          await k.seite.waitForSelector('#vehsel', { state: 'visible', timeout: 10000 });
        }
      };
      await seiteAuf();
      const vorher = await k.seite.evaluate(() => ({
        veh: document.getElementById('vehsel').value,
        base: document.getElementById('basesel').value,
      }));
      /* DAS SPEICHERN GEHT ÜBER `api/day.php`, NICHT ÜBER EINE UMLEITUNG
         (index.php:1145 — `submit` wird abgefangen und als fetch gesendet).
         Gewartet wird deshalb auf die Rückmeldung in `#savestate`, nicht auf
         eine Navigation; ein `waitForNavigation` liefe hier in die Zeitgrenze
         und die Probe meldete einen Fehler, den es nicht gibt. */
      const zuordnen = async (vehWert, baseWert) => {
        await seiteAuf();
        await k.seite.selectOption('#vehsel', vehWert);
        await k.seite.selectOption('#basesel', baseWert).catch(() => {});
        await k.seite.evaluate(() => document.getElementById('savestate').textContent = '');
        await k.seite.locator('#dayform button[type="submit"]').first().click();
        await k.seite.waitForFunction(
          () => /gespeichert|Gespeichert/.test(
            document.getElementById('savestate').textContent || ''),
          null, { timeout: 15000 }).catch(() => {});
        await k.seite.waitForTimeout(300);
      };
      const bwWert = await k.seite.evaluate(() => {
        const o = Array.from(document.getElementById('vehsel').options)
          .find(x => /Bergwacht/i.test(x.textContent));
        return o ? o.value : null;
      });
      if (!bwWert) { return { ist: 'Kein Bergwacht-Rettungsmittel in der Auswahlliste', ok: false }; }
      try {
        await zuordnen(bwWert, '');
        await seiteAuf();
        await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
        const z = await k.seite.evaluate((id) => {
          const a = document.querySelector(`.akkordeon-inhalt .eintrag[href*="d=${id}"]`);
          if (!a) { return null; }
          return {
            neben: (a.querySelector('.eintrag-neben')?.textContent || '').trim(),
            titel: a.getAttribute('title') || '',
            zeichen: a.querySelector('svg title')?.textContent
                  || a.querySelector('svg')?.getAttribute('aria-label') || null,
          };
        }, tag);
        await k.bild('ap4-zuordnen-friert-ein');
        const ok = !!z && z.neben === 'BW Hoch' && /Bergwacht/i.test(z.zeichen || '')
                && /Bergwacht Hochkreuth/.test(z.titel);
        return {
          ist: z ? `Nebentext „${z.neben}" · Tooltip „${z.titel}" · Zeichen „${z.zeichen}"`
                 : 'Der Tag steht nicht in der Leiste',
          ok,
          bemerkung: ok ? '' : 'Kurzname oder Typzeichen kommen in der Leiste nicht an',
        };
      } finally {
        await zuordnen(vorher.veh, vorher.base);
      }
    },
  },
];
