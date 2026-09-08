/* Wege des Nachtragspakets AP4a — Kurzname im schmalen Band, Typ beim
 * Zusammenführen.
 * ===========================================================================
 *
 *   E-S9-09 / Nr. 69   Der Kurzname steht in der Leiste — in JEDER Breite
 *   M-S9-08 Variante 2 Im Band 1024–1199 px bleibt er sichtbar, das
 *                      Akkordeon rückt dort je Ebene 8 statt 12 px ein
 *   M-S9-09 Variante c Die Vorschau des Zusammenführens nennt den Typ, die
 *                      Wahlzeilen nennen Typ und Kurznamen
 *
 * WARUM DIESE WEGE UND NICHT DIE AUS AP4: Die beiden Wege in `ap4.mjs`
 * fahren bei 1280 px und lesen `textContent` — das liefert auch bei
 * `display:none` einen Wert. Sie belegen den Kurznamen in der Leiste, aber
 * NICHT seine Sichtbarkeit, und schon gar nicht die im Band 1024–1199 px,
 * das sie nie betreten. Hier wird deshalb über `getBoundingClientRect()`
 * gemessen, und der Weg beantwortet je Breite eine andere Frage.
 *
 * DER BESTAND WIRD HERGESTELLT UND ZURÜCKGESTELLT — dasselbe Muster wie in
 * `ap4.mjs`: Der Referenzbestand ist der Vergleichsstand der Kreisläufe, und
 * ein Weg, der eine Zuordnung stehen lässt, verschiebt ihn. Jede Zuordnung
 * wird in `finally` zurückgenommen.
 *
 * DER ABNAHMELAUF BRAUCHT FÜNF BREITEN, nicht die zwei aus der LIESMICH:
 *
 *   node tools/klickprobe/probe.mjs --nur AP4a \
 *        --breiten 390,1024,1100,1199,1280 --bilder
 *
 * Die Vorgabe (1280) und der dort genannte volle Lauf (390, 1280) liegen
 * BEIDE außerhalb des Bandes 1024–1199 px, um das es hier geht — ein Lauf
 * ohne 1024, 1100 und 1199 belegt von dieser Regel nichts und meldet
 * trotzdem „ok". Die drei Randwerte sind mit Absicht dabei: 1024 ist die
 * untere Kante, 1199 die obere, 1100 die Mitte.
 *
 * ZUSAMMENGEFÜHRT WIRD NICHTS. Weg 2 öffnet die Vorschau und liest sie; das
 * Zusammenführen selbst ist nicht umkehrbar und läuft nicht über den
 * Papierkorb (so steht es auf der Seite selbst). Eine Probe, die es fährt,
 * kostet einen Diensttag je Lauf.
 */

/* ---- Gemeinsames: einen Diensttag einem Rettungsmittel zuordnen ---------- */

/** Die Tagesübersicht öffnen und ihr Zuordnungsformular aufklappen. */
async function tagAuf(k, tag) {
  await k.gehZu(`${k.basis}/index.php?d=${tag}`);
  /* Das Formular ist zunächst `hidden` und wird von `api/day.php` gefüllt —
     vorher steht in `#vehsel` nichts (Fund aus AP4). */
  await k.seite.waitForFunction(
    () => { const s = document.getElementById('vehsel');
            return s && s.options.length > 1; }, null, { timeout: 20000 });
  if (await k.seite.evaluate(() => document.getElementById('dayform').hidden)) {
    await k.seite.locator('#tagdatenknopf').click();
    await k.seite.waitForSelector('#vehsel', { state: 'visible', timeout: 10000 });
  }
}

/** Den aktuellen Stand der Zuordnung lesen (für das Zurückstellen). */
async function zuordnungLesen(k, tag) {
  await tagAuf(k, tag);
  return k.seite.evaluate(() => ({
    veh: document.getElementById('vehsel').value,
    base: document.getElementById('basesel').value,
  }));
}

/** Zuordnen und auf die Rückmeldung warten — gespeichert wird per fetch. */
async function zuordnen(k, tag, vehWert, baseWert) {
  await tagAuf(k, tag);
  await k.seite.selectOption('#vehsel', vehWert);
  await k.seite.selectOption('#basesel', baseWert).catch(() => {});
  await k.seite.evaluate(() => { document.getElementById('savestate').textContent = ''; });
  await k.seite.locator('#dayform button[type="submit"]').first().click();
  await k.seite.waitForFunction(
    () => /gespeichert|Gespeichert/.test(
      document.getElementById('savestate').textContent || ''),
    null, { timeout: 15000 }).catch(() => {});
  await k.seite.waitForTimeout(300);
}

/** Der Auswahlwert des Bergwacht-Rettungsmittels (es trägt den Kurznamen). */
async function bergwachtWert(k, tag) {
  await tagAuf(k, tag);
  return k.seite.evaluate(() => {
    const o = Array.from(document.getElementById('vehsel').options)
      .find(x => /Bergwacht/i.test(x.textContent));
    return o ? o.value : null;
  });
}

export const wege = [
  {
    name: 'ap4a-kurzname-im-band',
    paket: 'AP4a', punkt: 'M-S9-08 V2', rolle: 'demo',
    was: 'Der Kurzname bleibt im Band 1024–1199 px sichtbar, der volle Name nicht',
    soll: 'je Breite: <1024 alle · 1024–1199 nur der Kurzname (8 px) · ≥1200 alle (12 px); die freie Breite wird gemeldet, nicht verlangt',
    async fahren(k) {
      const tag = k.kennung.tag;
      if (!tag) { throw new Error('Kein Diensttag im Bestand'); }
      const breite = k.seite.viewportSize().width;
      const bw = await bergwachtWert(k, tag);
      if (!bw) { return { ist: 'Kein Bergwacht-Rettungsmittel in der Auswahlliste', ok: false }; }
      const vorher = await zuordnungLesen(k, tag);
      try {
        await zuordnen(k, tag, bw, '');
        await k.gehZu(`${k.basis}/index.php?d=${tag}`);
        await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
        await k.seite.waitForTimeout(200);
        const m = await k.seite.evaluate(() => {
          const alle = Array.from(document.querySelectorAll('.leiste-liste .eintrag-neben'));
          const kurz = alle.filter(e => e.classList.contains('kurz'));
          /* SICHTBARKEIT ÜBER DIE GEMESSENE BREITE, nicht über den Text:
             `textContent` liefert auch bei `display:none` einen Wert — genau
             daran belegen die AP4-Wege diese Regel NICHT. */
          const sicht = e => e.getBoundingClientRect().width > 0;
          const ak = document.querySelector('.leiste-liste .akkordeon-inhalt');
          return {
            gesamt: alle.length,
            mitKurz: kurz.length,
            sichtbar: alle.filter(sicht).length,
            sichtbarKurz: kurz.filter(sicht).length,
            /* DIE ELLIPSE IST HIER KEIN SOLLWERT, SONDERN EINE ZAHL. Wie
               viel vom Kurznamen ankommt, haengt am Datum daneben: Bricolage
               Grotesque setzt Ziffern proportional, das Datum ist je nach
               Ziffern 76 bis 83 px breit, und `.eintrag-text` schrumpft
               nicht. Eine Probe, die `ellipsen === 0` verlangt, faellt je
               nach zugeordnetem Tag zufaellig gruen oder rot aus — genau das
               hat die Gegenprobe am 08.09.2026 gefunden (F-S9-U-13).
               Gemeldet wird deshalb die freie Breite; verlangt wird, dass
               der Kurzname im Band ueberhaupt STEHT. */
            ellipsen: kurz.filter(e => sicht(e) && e.scrollWidth > e.clientWidth + 1).length,
            frei: kurz.length ? Math.round(kurz[0].getBoundingClientRect().width) : 0,
            noetig: kurz.length ? kurz[0].scrollWidth : 0,
            datumBreite: kurz.length ? Math.round(
              kurz[0].closest('.eintrag').querySelector('.eintrag-text')
                .getBoundingClientRect().width) : 0,
            /* KEIN KURZNAME AN EINEM MEHRFACHEN TAG. Dort traegt die Zeile
               Datum UND Uhrzeit, und dem Nebentext blieben gemessen 3 px
               von 55 — eine Ellipse ohne Buchstaben (Fund der Gegenprobe,
               08.09.2026). Die Klasse darf dort nicht stehen. */
            kurzMitUhrzeit: kurz.filter(e => /\d{2}:\d{2}/.test(
              e.closest('.eintrag')?.querySelector('.eintrag-text')?.textContent || '')).length,
            text: kurz.map(e => e.textContent.trim())[0] || null,
            einrueckung: ak ? getComputedStyle(ak).paddingLeft : null,
          };
        });
        await k.bild('ap4a-kurzname-im-band');
        const band = breite >= 1024 && breite < 1200;
        const sollEin = band ? '8px' : '12px';
        const sollSicht = band ? m.mitKurz : m.gesamt;
        const ok = m.gesamt > 0 && m.mitKurz === 1 && m.text === 'BW Hoch'
                && m.sichtbar === sollSicht && m.sichtbarKurz === 1
                && m.kurzMitUhrzeit === 0
                && m.einrueckung === sollEin;
        return {
          ist: `${m.sichtbar} von ${m.gesamt} Nebentexten sichtbar, davon `
             + `${m.sichtbarKurz} mit Kurznamen („${m.text}"), Datum ${m.datumBreite} px, `
             + `frei ${m.frei} px für ${m.noetig} nötige (${m.ellipsen} Ellipsen), `
             + `${m.kurzMitUhrzeit} Kurznamen an einem mehrfachen Tag, `
             + `Einrückung ${m.einrueckung}`,
          ok,
          bemerkung: ok ? ''
            : `Soll bei ${breite} px: ${sollSicht} sichtbar, 1 davon Kurzname, `
            + `0 an mehrfachen Tagen, Einrückung ${sollEin}`,
        };
      } finally {
        await zuordnen(k, tag, vorher.veh, vorher.base);
      }
    },
  },

  {
    name: 'ap4a-typ-beim-zusammenfuehren',
    paket: 'AP4a', punkt: 'M-S9-09 Vc', rolle: 'demo',
    was: 'Die Vorschau nennt den Typ, die Wahlzeilen nennen Typ und Kurznamen',
    soll: 'Zeile „Typ" nennt BEIDE Typen und die Kleinzeile · Zusatz der Quelle nennt „Bergwacht" und „BW Hoch"',
    async fahren(k) {
      /* ZWEI TAGE, DIE SICH ZUSAMMENFÜHREN LASSEN: Der Bestand hat ein
         Datum doppelt (der eine Tag luft-, der andere bodengebunden). Erst
         wenn beide dieselbe Betriebsart haben, sind sie vereinbar — das
         besorgt die Zuordnung auf das Bergwacht-Rettungsmittel, das
         luftgebunden ist. Genau der Fall, um den es geht: gleiche
         Betriebsart, verschiedener Typ. */
      await k.gehZu(`${k.basis}/index.php`);
      await k.seite.evaluate(() => document.querySelectorAll('details').forEach(e => { e.open = true; }));
      await k.seite.waitForTimeout(200);
      const paar = await k.seite.evaluate(() => {
        const nach = {};
        for (const a of document.querySelectorAll('.leiste-liste .eintrag[href*="index.php?d="]')) {
          const id = Number(new URL(a.href).searchParams.get('d'));
          const datum = (a.querySelector('.eintrag-text')?.textContent || '').trim().slice(0, 10);
          const art = a.querySelector('svg title')?.textContent || '';
          (nach[datum] ||= []).push({ id, art });
        }
        const doppelt = Object.entries(nach).find(([, e]) => e.length === 2);
        if (!doppelt) { return null; }
        const [a, b] = doppelt[1];
        /* UMGESTELLT WIRD DER BODENGEBUNDENE TAG. Das Bergwacht-Rettungsmittel
           ist luftgebunden; wer den Tag umstellt, der es ohnehin schon ist,
           macht die beiden erst recht unvereinbar — genau daran ist diese
           Probe beim Bauen einmal gescheitert (08.09.2026). */
        const boden = [a, b].find(e => !/luftgebunden/i.test(e.art));
        const quelle = boden || b;
        const ziel = quelle === a ? b : a;
        return { datum: doppelt[0], ziel: ziel.id, quelle: quelle.id };
      });
      if (!paar) { return { ist: 'Kein Datum mit zwei Diensttagen im Bestand', ok: false }; }

      const bw = await bergwachtWert(k, paar.quelle);
      if (!bw) { return { ist: 'Kein Bergwacht-Rettungsmittel in der Auswahlliste', ok: false }; }
      const vorher = await zuordnungLesen(k, paar.quelle);
      try {
        await zuordnen(k, paar.quelle, bw, '');
        await k.gehZu(`${k.basis}/diensttag_zusammenfuehren.php`
                    + `?d=${paar.ziel}&q=${paar.quelle}`);
        const m = await k.seite.evaluate(() => {
          const zeilen = Array.from(document.querySelectorAll('.zeile')).map(z => ({
            haupt: (z.querySelector('.zeile-haupt')?.textContent || '').trim(),
            klein: (z.querySelector('.zeile-klein')?.textContent || '').trim(),
            plakette: (z.querySelector('.zeile-plaketten')?.textContent || '').trim(),
          }));
          const typ = zeilen.find(z => z.haupt === 'Typ') || null;
          const art = zeilen.find(z => z.haupt === 'Art') || null;
          const zusaetze = Array.from(document.querySelectorAll('.wahl-zeile'))
            .map(w => ({ text: (w.querySelector('.wahl-text')?.textContent || '').trim(),
                         zusatz: (w.querySelector('.wahl-zusatz')?.textContent || '').trim(),
                         hoehe: Math.round(w.getBoundingClientRect().height) }));
          return { typ, art, zusaetze, fehler: !!document.querySelector('.meldung-fehler') };
        });
        await k.bild('ap4a-typ-beim-zusammenfuehren');
        const mitBergwacht = m.zusaetze.filter(z => /Bergwacht/.test(z.zusatz));
        const mitKurz = m.zusaetze.filter(z => /BW Hoch/.test(z.zusatz));
        /* BEIDE TYPEN, NICHT EINER. Solange zwei Rettungsmittel zur Wahl
           stehen, kann die Zeile keinen einzelnen Wert behaupten — die Seite
           laedt beim Klick auf ein Radio nicht neu (Fund der Gegenprobe,
           08.09.2026). Sie nennt deshalb „X oder Y". */
        const ok = !!m.typ && / oder /.test(m.typ.plakette)
                && /Standard/.test(m.typ.plakette) && /Bergwacht/.test(m.typ.plakette)
                && m.typ.klein !== '' && !!m.art
                && mitBergwacht.length === 1 && mitKurz.length === 1;
        return {
          ist: m.typ
            ? `Zeile „Typ" = „${m.typ.plakette}" (Kleinzeile: „${m.typ.klein}"), `
            + `Zeile „Art" = „${m.art ? m.art.plakette : '—'}", `
            + `${m.zusaetze.length} Wahlzeilen, ${mitBergwacht.length} nennen den Typ, `
            + `${mitKurz.length} den Kurznamen`
            : 'Keine Zeile „Typ" in der Vorschau',
          ok,
          bemerkung: ok ? ''
            : 'Soll: Zeile „Typ" nennt „Standard oder Bergwacht" samt '
            + 'Kleinzeile, genau eine Wahlzeile nennt „Bergwacht" und „BW Hoch"',
        };
      } finally {
        await zuordnen(k, paar.quelle, vorher.veh, vorher.base);
      }
    },
  },
];
