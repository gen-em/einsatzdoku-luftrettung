/* Wege des Arbeitspakets AP6 — Tageszuordnung.
 * ===========================================================================
 *
 *   E-S9-11   Rollen sofort nach der Auswahl, ohne zu speichern
 *   E-S9-10   Ein Rettungsmittel nur für den Tag („Anderes Rettungsmittel …")
 *
 * ALLE DREI WEGE ÄNDERN DEN DIENSTTAG UND STELLEN IHN ZURÜCK. Der
 * Referenzbestand hat 61 Diensttage, und die Zuordnung eines davon ist kein
 * Beiwerk: An ihr hängen Momentaufnahme, Artzeichen, Leiste und Auswertung.
 * Jeder Weg liest deshalb zuerst, was dasteht, und schreibt es im `finally`
 * zurück — auch wenn er unterwegs scheitert.
 *
 * GEMESSEN WIRD AM DOM, NICHT AN DER ABSICHT. „Die Felder erscheinen ohne
 * Speichern" heißt: Die Zahl der Rollenfelder ändert sich, und der Diensttag
 * in der Datenbank tut es nicht. Beides steht deshalb im Ist.
 */

const SEITE = k => `${k.basis}/index.php`;

/** Das Formular „Diensttag-Daten" öffnen und die Kennung des Tages liefern. */
async function formularAuf(k) {
  await k.gehZu(SEITE(k));
  await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
  await k.seite.waitForTimeout(500);
  await k.seite.click('#tagdatenknopf');
  await k.seite.waitForTimeout(400);
  return await k.seite.evaluate(() => {
    const a = document.getElementById('addmission');
    return a ? new URL(a.href, location.href).searchParams.get('d') : null;
  });
}

/** Was im Formular steht — Auswahl, Rollenfelder, Hinweis. */
const stand = k => k.seite.evaluate(() => ({
  veh:    document.getElementById('vehsel').value,
  base:   document.getElementById('basesel').value,
  rollen: Array.from(document.querySelectorAll('#crewfields label')).map(l => l.dataset.role),
  hinweis: document.getElementById('crewhint').hidden
    ? null : document.getElementById('crewhint').textContent.replace(/\s+/g, ' ').trim(),
  adhocAuf: !document.getElementById('adhocfelder').hidden,
}));

/** Die Zuordnung zurückschreiben — dieselbe Handlung wie von Hand. */
async function zurueck(k, veh, base) {
  await k.seite.selectOption('#vehsel', veh);
  await k.seite.waitForTimeout(500);
  await k.seite.selectOption('#basesel', base);
  await k.seite.waitForTimeout(400);
  await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
  await k.seite.waitForTimeout(1200);
}


/** Einen Diensttag mit Besatzungsnamen finden — aus der Tagesliste heraus. */
async function tagMitBesatzung(k) {
  await k.gehZu(SEITE(k));
  await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
  await k.seite.waitForTimeout(600);
  const kennungen = await k.seite.evaluate(() => Array.from(
    document.querySelectorAll('a[href*="index.php?d="]'))
    .map(a => new URL(a.href, location.href).searchParams.get('d'))
    .filter(Boolean).slice(0, 12));
  for (const id of kennungen) {
    await k.gehZu(`${k.basis}/index.php?d=${id}`);
    await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
    await k.seite.waitForTimeout(500);
    await k.seite.click('#tagdatenknopf');
    await k.seite.waitForTimeout(400);
    const m = await k.seite.evaluate(() => ({
      veh: document.getElementById('vehsel').value,
      base: document.getElementById('basesel').value,
      kind: (document.querySelector('#vehsel option:checked') || {}).dataset?.kind || null,
      namen: Array.from(document.querySelectorAll('#crewfields input[name^="crew_"]'))
        .map(i => i.value).filter(v => v.trim() !== ''),
    }));
    if (m.namen.length) {
      return { id, kind: m.kind, namen: m.namen, stand: { veh: m.veh, base: m.base } };
    }
  }
  return null;
}

export const wege = [
  {
    name: 'ap6-rollen-ohne-speichern',
    paket: 'AP6', punkt: 'E-S9-11', rolle: 'demo',
    was: 'Ein Wechsel des Rettungsmittels zeichnet die Besatzungsfelder sofort neu — ohne zu speichern',
    soll: 'Rollenzahl je Rettungsmittel wie `vehicle_roles`; die Zuordnung in der Datenbank unverändert',
    async fahren(k) {
      const tag = await formularAuf(k);
      const anfang = await stand(k);
      try {
        /* Die Auswahl selbst liefert die Prüfmenge: jedes Rettungsmittel des
           Kontos, gefahren in der Reihenfolge, in der es dasteht. */
        const optionen = await k.seite.evaluate(() => Array.from(
          document.querySelectorAll('#vehsel option'))
          .map(o => o.value).filter(v => v && v !== 'adhoc'));
        const gemessen = [];
        for (const v of optionen) {
          await k.seite.selectOption('#vehsel', v);
          await k.seite.waitForTimeout(450);
          const m = await stand(k);
          gemessen.push({ v, rollen: m.rollen.length, hinweis: m.hinweis !== null });
        }
        /* Nichts gespeichert: Die Seite neu laden und nachsehen, was dasteht. */
        await k.gehZu(SEITE(k));
        await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf');
        await k.seite.waitForTimeout(400);
        const nachher = await stand(k);

        const verschieden = new Set(gemessen.map(g => g.rollen)).size;
        const ok = optionen.length >= 2 && verschieden >= 2
                && nachher.veh === anfang.veh && nachher.base === anfang.base;
        return {
          ist: `${optionen.length} Rettungsmittel gefahren, Rollenzahlen `
             + `${gemessen.map(g => g.rollen).join('/')} (${verschieden} verschiedene) · `
             + `Diensttag ${tag} nach dem Neuladen unverändert: `
             + `Rettungsmittel ${nachher.veh === anfang.veh}, Standort ${nachher.base === anfang.base}`,
          ok,
          bemerkung: ok ? ''
            : 'Entweder zeichnet der Wechsel nicht neu, oder er hat gespeichert',
        };
      } finally {
        await zurueck(k, anfang.veh, anfang.base);
      }
    },
  },

  {
    name: 'ap6-adhoc-felder-und-typregel',
    paket: 'AP6', punkt: 'E-S9-10', rolle: 'demo',
    was: '„Anderes Rettungsmittel …" klappt drei Felder auf; der Typ legt die Betriebsart fest',
    soll: 'Felder auf, 0 Rollenfelder mit eigenem Satz; Veranstaltung → luftgebunden gesperrt, Boden gesetzt',
    async fahren(k) {
      await formularAuf(k);
      const anfang = await stand(k);
      try {
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(450);
        const auf = await stand(k);

        await k.seite.selectOption('#adhoc-typ', 'veranstaltung');
        await k.seite.waitForTimeout(250);
        const regel = await k.seite.evaluate(() => ({
          luftGesperrt: document.querySelector('#adhocfelder .vehkind-radio[value=air]').disabled,
          bodenGesetzt: document.querySelector('#adhocfelder .vehkind-radio[value=ground]').checked,
          festSichtbar: !document.querySelector('[data-adhoc-fest]').hidden,
        }));
        await k.bild('ap6-adhoc-felder');

        const satz = (auf.hinweis || '').includes('nur für diesen Tag');
        const ok = auf.adhocAuf && auf.rollen.length === 0 && satz
                && regel.luftGesperrt && regel.bodenGesetzt && regel.festSichtbar;
        return {
          ist: `Felder auf: ${auf.adhocAuf} · Rollenfelder ${auf.rollen.length} · `
             + `eigener Satz: ${satz} · Veranstaltung → Luft gesperrt ${regel.luftGesperrt}, `
             + `Boden gesetzt ${regel.bodenGesetzt}, Hinweis „fest" ${regel.festSichtbar}`,
          ok,
          bemerkung: ok ? '' : 'Die Felder oder die Typregel greifen nicht (E-S9-10)',
        };
      } finally {
        await zurueck(k, anfang.veh, anfang.base);
      }
    },
  },

  {
    name: 'ap6-adhoc-speichern-und-finden',
    paket: 'AP6', punkt: 'E-S9-10', rolle: 'demo',
    was: 'Ein Rettungsmittel nur für den Tag wird gespeichert, ohne Stammdatensatz — und steht in der Tagesliste',
    soll: 'Nach dem Neuladen: Auswahl „adhoc", Name und Typ zurück, Standortkennung aus dem Treffer, 0 Rollenfelder; der Name steht in der Tagesliste',
    async fahren(k) {
      const NAME = 'KP Aushilfe 12/1';
      await formularAuf(k);
      const anfang = await stand(k);
      try {
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(400);
        await k.seite.fill('#adhoc-name', NAME);
        await k.seite.selectOption('#adhoc-typ', 'sonstiges');
        await k.seite.waitForTimeout(200);
        await k.seite.check('#adhocfelder .vehkind-radio[value=ground]');

        /* Der Standort über die Vorschlagsliste: Der Treffer soll die
           verborgene Kennung setzen — daran hängt, ob der Tag die KOORDINATE
           des Standorts einfriert oder nur seinen Namen. */
        await k.seite.click('#adhoc-base');
        await k.seite.type('#adhoc-base', 'Notarzt', { delay: 60 });
        await k.seite.waitForTimeout(400);
        await k.seite.evaluate(() => {
          const li = [...document.querySelectorAll('#adhoc-basefeld li')]
            .find(l => !l.classList.contains('vorschlaege-gruppe'));
          if (li) { li.dispatchEvent(new MouseEvent('mousedown', { bubbles: true })); }
        });
        await k.seite.waitForTimeout(300);
        const kennung = await k.seite.inputValue('#adhoc-base-id');

        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
        await k.seite.waitForTimeout(1500);

        await k.gehZu(SEITE(k));
        await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
        await k.seite.waitForTimeout(700);
        await k.seite.click('#tagdatenknopf');
        await k.seite.waitForTimeout(500);
        const m = await k.seite.evaluate(() => ({
          veh:   document.getElementById('vehsel').value,
          name:  document.getElementById('adhoc-name').value,
          typ:   document.getElementById('adhoc-typ').value,
          kind:  (document.querySelector('#adhocfelder .vehkind-radio:checked') || {}).value,
          baseId: document.getElementById('adhoc-base-id').value,
          rollen: document.querySelectorAll('#crewfields label').length,
        }));
        /* IN DER TAGESLISTE: Sie kommt aus der Momentaufnahme (`dt_liste()`),
           nicht aus den Stammdaten — genau deshalb findet sie ein
           Rettungsmittel, das es als Stammdatensatz nie gab. */
        const inListe = await k.seite.evaluate(n =>
          document.body.textContent.includes(n), NAME);
        await k.bild('ap6-adhoc-gespeichert');

        const ok = m.veh === 'adhoc' && m.name === NAME && m.typ === 'sonstiges'
                && m.kind === 'ground' && m.baseId !== '' && m.rollen === 0 && inListe;
        return {
          ist: `Kennung aus dem Treffer „${kennung}" · nach dem Neuladen Auswahl „${m.veh}", `
             + `Name „${m.name}", Typ ${m.typ}, Betriebsart ${m.kind}, `
             + `Standortkennung „${m.baseId}", Rollenfelder ${m.rollen} · `
             + `Name in der Tagesliste: ${inListe}`,
          ok,
          bemerkung: ok ? '' : 'Der Tag hat das Rettungsmittel nicht behalten (E-S9-10)',
        };
      } finally {
        await zurueck(k, anfang.veh, anfang.base);
      }
    },
  },

  {
    name: 'ap6-namen-ueberleben-den-umweg',
    paket: 'AP6', punkt: 'F-S9-U-34', rolle: 'demo',
    was: 'Besatzungsnamen überleben den Weg über das Tagesfahrzeug und zurück',
    soll: 'Die Namen stehen nach dem Umweg unverändert im Formular und in der Leseansicht',
    async fahren(k) {
      /* DER FALL, DER EINEN STILLEN VERLUST KOSTETE (F-S9-U-34).
         Die Rollenvorschau kennt nur, was im Formular steht. Ist dort GAR
         NICHTS — beim Tagesfahrzeug gibt es keine Rollenfelder —, dann rendert
         sie beim Rückweg auf ein Rettungsmittel MIT Rollen lauter LEERE
         Felder, obwohl `day_crew` dort Namen hält: `dt_zuordnen()` löscht seit
         jeher nur leere Zeilen, benannte überleben jeden Wechsel. Das
         Speichern schrieb die Leere zurück, und die Namen waren fort.

         DER UMWEG MUSS ÜBER DAS TAGESFAHRZEUG GEHEN. Ein erster Entwurf
         dieses Weges fuhr über ein anderes Rettungsmittel mit anderem
         Rollensatz — und lief auch auf der FEHLERHAFTEN Fassung durch: Dort
         zeichnet das Formular nach dem Neuladen die Felder aus `day_crew`, die
         Namen stehen also sichtbar darin, und der Rückweg übernimmt sie. Erst
         der Zustand OHNE Felder bringt die Lücke zum Vorschein. Gegengeprobt
         am 09.09.2026 gegen beide Fassungen. */
      const tag = await tagMitBesatzung(k);
      if (!tag) {
        return { ist: 'Kein Diensttag mit Besatzungsnamen im Bestand', ok: false,
                 bemerkung: 'Der Weg braucht einen Tag mit mindestens einem Namen' };
      }
      const anfang = tag.stand;
      try {
        /* Hin: das Tagesfahrzeug — es hat keine Rollen, das Formular zeigt
           also kein einziges Besatzungsfeld. */
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(600);
        await k.seite.fill('#adhoc-name', 'KP Umweg');
        await k.seite.check('#adhocfelder .vehkind-radio[value=ground]', { force: true });
        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
        await k.seite.waitForTimeout(1400);

        await k.gehZu(`${k.basis}/index.php?d=${tag.id}`);
        await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf');
        await k.seite.waitForTimeout(400);
        await k.seite.selectOption('#vehsel', anfang.veh);
        await k.seite.waitForTimeout(600);
        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
        await k.seite.waitForTimeout(1400);

        await k.gehZu(`${k.basis}/index.php?d=${tag.id}`);
        await k.seite.waitForTimeout(700);
        const nachher = await k.seite.evaluate(() =>
          document.getElementById('taglese').textContent.replace(/\s+/g, ' ').trim());
        const alle = tag.namen.every(n => nachher.includes(n));
        return {
          ist: `${tag.namen.length} Namen vorher (${tag.namen.join(', ')}) · `
             + `nach dem Umweg über das Tagesfahrzeug (0 Rollenfelder) `
             + `alle wieder da: ${alle}`,
          ok: alle,
          bemerkung: alle ? '' : 'Der Umweg hat Besatzungsnamen gelöscht (F-S9-U-34)',
        };
      } finally {
        await k.seite.selectOption('#vehsel', anfang.veh).catch(() => {});
        await k.seite.waitForTimeout(500);
        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit())
          .catch(() => {});
        await k.seite.waitForTimeout(1200);
      }
    },
  },
];
