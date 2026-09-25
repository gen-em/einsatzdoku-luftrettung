/* Wege des Arbeitspakets AP6 — Tageszuordnung.
 * ===========================================================================
 *
 *   E-S9-11   Rollen sofort nach der Auswahl, ohne zu speichern
 *   E-S9-10   Ein Rettungsmittel nur für den Tag („Anderes Rettungsmittel …")
 *   Nr. 169   …und seit P5c/AP8 (Web 21.0.0) mit den Rollen seiner
 *             Betriebsart (E-P5c-47). Bis dahin führte ein solcher Tag keine;
 *             vier Wege hier maßen genau das und sind umgedreht, ein fünfter
 *             misst den Tag in der Luft. Das Konzept nannte dafür
 *             `wege/index.mjs` — die Wege zum Tagesfahrzeug stehen aber hier,
 *             und ein zweiter Ort für dieselbe Sache wäre einer zu viel.
 *
 * ALLE WEGE ÄNDERN DEN DIENSTTAG UND STELLEN IHN ZURÜCK. Der
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

/** Die Rollen der Betriebsarten (CREW_ROLES, db.php) — in Katalogreihenfolge. */
const ROLLEN_LUFT  = ['p1', 'p2', 'hems', 'fr', 'other'];
const ROLLEN_BODEN = ['driver', 'trainee', 'other'];
const gleich = (a, b) => a.length === b.length && a.every((x, i) => x === b[i]);
/* DIE ANDERE ART als die des Tages. Ein Umweg über das Tagesfahrzeug misst
   nur dann etwas, wenn dort KEIN Feld für die Namen des Tages steht — und
   seit Nr. 169 steht dort eines, wenn die Art dieselbe ist. „other" teilen
   sich beide Arten: Ein Name dort steht auch im Feld der anderen Art, die
   übrigen nicht. */
const andereArt = kind => (kind === 'ground' ? 'air' : 'ground');
const rollenDer = art => (art === 'air' ? ROLLEN_LUFT : ROLLEN_BODEN);

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


/** Welche Besatzungsrollen bietet das EINSATZFORMULAR dieses Tages an?
 *
 * GEMESSEN WIRD DAS TOR, NICHT DIE SICHTBARKEIT. Über den Rollenfeldern
 * liegen drei Schichten, und nur die unterste ist hier gemeint:
 *   1. Die Karte „Abweichende Besatzung vom Diensttag" ist ein zugeklapptes
 *      `<details>`.
 *   2. `DIV.childfields` trägt `hidden`, solange der Haken
 *      `f_crew_override` nicht gesetzt ist (`.parentcheck`).
 *   3. Jedes Feld, das `role_gate` nicht besteht, trägt `hidden` an seinem
 *      EIGENEN `LABEL.fld-sub` — serverseitig gesetzt (einsatz_form.php).
 * Ein erster Entwurf dieses Helfers zählte sichtbare Felder und bekam überall
 * 0: Schicht 1 und 2 hatten alles verdeckt. Der Haken selbst ist zudem ein
 * gestalteter Schalter, dessen `input` außerhalb des Sichtfensters liegt —
 * `check()` scheitert daran. Deshalb wird nichts geklickt und nichts
 * aufgeklappt, sondern Schicht 3 unmittelbar gelesen.
 *
 * Gespeichert wird nichts: Der Weg öffnet das Formular für einen NEUEN Einsatz
 * und verlässt es wieder. Die Feldnamen tragen das Präfix `f_`.
 */
async function rollenImEinsatzformular(k, tagId) {
  await k.gehZu(`${k.basis}/einsatz_form.php?d=${tagId}`);
  await k.seite.waitForSelector('input[name="f_crew_override"]',
                                { timeout: 20000, state: 'attached' });
  await k.seite.waitForTimeout(400);
  return await k.seite.evaluate(() => Array.from(
    document.querySelectorAll('input[name^="f_crew_"]'))
    .filter(i => i.name !== 'f_crew_override')
    .filter(i => { const l = i.closest('label'); return l && !l.hasAttribute('hidden'); })
    .map(i => i.name.replace(/^f_crew_/, '')));
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
    was: '„Anderes Rettungsmittel …" klappt drei Felder auf; der Typ legt die Betriebsart fest, die Art die Rollen',
    soll: 'Felder auf, ohne Betriebsart 0 Rollenfelder und ein Satz dazu; Veranstaltung → luftgebunden gesperrt, Boden gesetzt, Rollen des Bodens (Nr. 169)',
    async fahren(k) {
      await formularAuf(k);
      const anfang = await stand(k);
      try {
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(450);
        const auf = await stand(k);

        await k.seite.selectOption('#adhoc-typ', 'veranstaltung');
        /* Die Rollen kommen aus der Vorschau (`api/day.php?vorschau=adhoc`) —
           ein Umlauf zum Server, deshalb länger warten als für die Regel. */
        await k.seite.waitForTimeout(800);
        const regel = await k.seite.evaluate(() => ({
          luftGesperrt: document.querySelector('#adhocfelder .vehkind-radio[value=air]').disabled,
          bodenGesetzt: document.querySelector('#adhocfelder .vehkind-radio[value=ground]').checked,
          festSichtbar: !document.querySelector('[data-adhoc-fest]').hidden,
        }));
        const boden = await stand(k);
        await k.bild('ap6-adhoc-felder');

        const satz = (auf.hinweis || '').includes('folgen der Betriebsart');
        const ok = auf.adhocAuf && auf.rollen.length === 0 && satz
                && regel.luftGesperrt && regel.bodenGesetzt && regel.festSichtbar
                && gleich(boden.rollen, ROLLEN_BODEN);
        return {
          ist: `Felder auf: ${auf.adhocAuf} · ohne Betriebsart ${auf.rollen.length} Rollenfelder, `
             + `Satz: ${satz} · Veranstaltung → Luft gesperrt ${regel.luftGesperrt}, `
             + `Boden gesetzt ${regel.bodenGesetzt}, Hinweis „fest" ${regel.festSichtbar} · `
             + `Rollen ${boden.rollen.join(', ') || '—'}`,
          ok,
          bemerkung: ok ? '' : 'Die Felder, die Typregel oder die Rollen der Betriebsart greifen nicht (E-S9-10, Nr. 169)',
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
    soll: 'Nach dem Neuladen: Auswahl „adhoc", Name und Typ zurück, Standortkennung aus dem Treffer, die Rollen des Bodens (Nr. 169); der Name steht in der Tagesliste',
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
        /* Die Rollenfelder eines Adhoc-Tags zeichnet die Vorschau (Nr. 169). */
        await k.seite.waitForTimeout(900);
        const m = await k.seite.evaluate(() => ({
          veh:   document.getElementById('vehsel').value,
          name:  document.getElementById('adhoc-name').value,
          typ:   document.getElementById('adhoc-typ').value,
          kind:  (document.querySelector('#adhocfelder .vehkind-radio:checked') || {}).value,
          baseId: document.getElementById('adhoc-base-id').value,
          rollen: Array.from(document.querySelectorAll('#crewfields label')).map(l => l.dataset.role),
        }));
        /* IN DER TAGESLISTE: Sie kommt aus der Momentaufnahme (`dt_liste()`),
           nicht aus den Stammdaten — genau deshalb findet sie ein
           Rettungsmittel, das es als Stammdatensatz nie gab. */
        const inListe = await k.seite.evaluate(n =>
          document.body.textContent.includes(n), NAME);
        await k.bild('ap6-adhoc-gespeichert');

        const ok = m.veh === 'adhoc' && m.name === NAME && m.typ === 'sonstiges'
                && m.kind === 'ground' && m.baseId !== '' && gleich(m.rollen, ROLLEN_BODEN)
                && inListe;
        return {
          ist: `Kennung aus dem Treffer „${kennung}" · nach dem Neuladen Auswahl „${m.veh}", `
             + `Name „${m.name}", Typ ${m.typ}, Betriebsart ${m.kind}, `
             + `Standortkennung „${m.baseId}", Rollen ${m.rollen.join(', ') || '—'} · `
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
         am 09.09.2026 gegen beide Fassungen.

         SEIT P5c/AP8 HAT DAS TAGESFAHRZEUG ROLLEN — die seiner Betriebsart
         (Nr. 169). Der Umweg geht deshalb über die ANDERE Art als die des
         Tages: Dort steht für die Namen kein Feld, genau wie vorher in gar
         keinem. Die Lücke, die dieser Weg misst, bleibt damit erreichbar. */
      const tag = await tagMitBesatzung(k);
      if (!tag) {
        return { ist: 'Kein Diensttag mit Besatzungsnamen im Bestand', ok: false,
                 bemerkung: 'Der Weg braucht einen Tag mit mindestens einem Namen' };
      }
      const anfang = tag.stand;
      try {
        /* Hin: das Tagesfahrzeug in der anderen Art — das Formular zeigt
           deren Rollen, aber keines der Felder, in denen die Namen stehen. */
        const art = andereArt(tag.kind);
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(600);
        await k.seite.fill('#adhoc-name', 'KP Umweg');
        await k.seite.check(`#adhocfelder .vehkind-radio[value=${art}]`, { force: true });
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
             + `nach dem Umweg über das Tagesfahrzeug (${art}, ohne die Felder der Namen) `
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

  {
    name: 'ap6-einsatzformular-rollen-der-art-am-adhoc-tag',
    paket: 'AP6', punkt: 'Frage 11 (a) + 3b, Nr. 169', rolle: 'demo',
    was: 'Das Einsatzformular eines Tages mit „Anderem Rettungsmittel" bietet genau die Rollen seiner Betriebsart an',
    soll: 'Vorher die Rollen des Rettungsmittels, nachher die der anderen Art (nicht die des früheren) — und die Namen stehen weiter in der Leseansicht des Tages',
    async fahren(k) {
      /* ZWEI ADHOC-TAGE MÜSSEN GLEICH AUSSEHEN (Frage 11, entschieden am
         09.09.2026). `dt_rollensatz_einfrieren()` löscht beim Wechsel nur
         LEERE Rollen — benannte Zeilen bleiben in `day_crew` stehen. Ohne die
         Abfrage in einsatz_form.php bot ein UMGESTELLTER Adhoc-Tag deshalb
         die Rollen des früheren Rettungsmittels an, ein FRISCH ANGELEGTER
         keine. Gefragt ist der Dienst.

         SEIT P5c/AP8 FÜHRT DER DIENST DIE ROLLEN SEINER ART (Nr. 169). Die
         Regel aus Frage 11 gilt weiter, nur mit einer anderen Menge: Ein Tag,
         der auf das Tagesfahrzeug der ANDEREN Art umgestellt wird, bietet
         dessen Rollen an, nicht die des früheren Rettungsmittels — auch wenn
         deren Namen noch in `day_crew` stehen.

         DIE NAMEN BLEIBEN. Entschieden wurde (a): Die Leseansicht berichtet
         weiter, was gespeichert ist. Der Weg misst deshalb BEIDES — die Rollen
         im Einsatzformular UND die Namen in der Leseansicht. Fällt eines von
         beiden, ist die Entscheidung verletzt. */
      const tag = await tagMitBesatzung(k);
      if (!tag) {
        return { ist: 'Kein Diensttag mit Besatzungsnamen im Bestand', ok: false,
                 bemerkung: 'Der Weg braucht einen Tag mit mindestens einem Namen' };
      }
      const anfang = tag.stand;
      try {
        const vorher = await rollenImEinsatzformular(k, tag.id);

        await k.gehZu(`${k.basis}/index.php?d=${tag.id}`);
        await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf');
        await k.seite.waitForTimeout(400);
        const art = andereArt(tag.kind);
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(600);
        await k.seite.fill('#adhoc-name', 'KP Frage 11');
        await k.seite.check(`#adhocfelder .vehkind-radio[value=${art}]`, { force: true });
        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
        await k.seite.waitForTimeout(1400);

        const nachher = await rollenImEinsatzformular(k, tag.id);

        await k.gehZu(`${k.basis}/index.php?d=${tag.id}`);
        await k.seite.waitForTimeout(700);
        const lese = await k.seite.evaluate(() =>
          document.getElementById('taglese').textContent.replace(/\s+/g, ' ').trim());
        const namenDa = tag.namen.every(n => lese.includes(n));

        const soll = rollenDer(art);
        const ok = vorher.length > 0 && gleich(nachher, soll) && namenDa;
        return {
          ist: `Einsatzformular: ${vorher.length} Rollen am Tag mit Rettungsmittel `
             + `(${vorher.join(', ') || '—'}), ${nachher.length} am Tag mit `
             + `„Anderem Rettungsmittel" (${art}: ${nachher.join(', ') || '—'}) · `
             + `${tag.namen.length} Namen in der Leseansicht: ${namenDa}`,
          ok,
          bemerkung: ok ? ''
            : (vorher.length === 0
                ? 'Schon vor dem Wechsel bot das Einsatzformular keine Rolle an — der Tag taugt nicht als Probe'
                : (!gleich(nachher, soll)
                    ? 'Der Adhoc-Tag bietet nicht genau die Rollen seiner Art an (Frage 11, Punkt 3b; Nr. 169)'
                    : 'Die Leseansicht hat die Besatzungsnamen verloren (Entscheidung a verletzt)')),
        };
      } finally {
        await k.gehZu(`${k.basis}/index.php?d=${tag.id}`).catch(() => {});
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf').catch(() => {});
        await k.seite.waitForTimeout(400);
        await k.seite.selectOption('#vehsel', anfang.veh).catch(() => {});
        await k.seite.waitForTimeout(600);
        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit())
          .catch(() => {});
        await k.seite.waitForTimeout(1200);
      }
    },
  },

  {
    name: 'p5c-ap8-adhoc-tag-in-der-luft',
    paket: 'P5c/AP8', punkt: 'Nr. 169, E-P5c-47', rolle: 'demo',
    was: 'Ein Tag mit „Anderem Rettungsmittel" in der Luft zeigt die Rollen der Luft — vor dem Speichern, danach und im Einsatzformular',
    soll: 'Dreimal p1, p2, hems, fr, other: in der Vorschau, nach dem Neuladen und im Einsatzformular',
    async fahren(k) {
      /* DIE ABNAHME AUS DEM KONZEPT, wörtlich: „Tag Luft zeigt p1, p2, hems,
         fr, other" (E-P5c-47). Den Boden messen die Wege darüber. Gemessen
         wird an drei Stellen, weil es drei Wege zu derselben Menge gibt —
         die Vorschau (`api/day.php?vorschau=adhoc`), der eingefrorene Satz
         nach dem Speichern (`dt_zuordnen()`) und das Tor im Einsatzformular
         (`einsatz_form.php`). Stimmt einer nicht mit den anderen, sähe die
         Seite vor und nach dem Speichern verschieden aus. */
      const tag = await formularAuf(k);
      const anfang = await stand(k);
      try {
        await k.seite.selectOption('#vehsel', 'adhoc');
        await k.seite.waitForTimeout(400);
        await k.seite.fill('#adhoc-name', 'KP Luft 169');
        await k.seite.selectOption('#adhoc-typ', 'standard');
        await k.seite.waitForTimeout(200);
        await k.seite.check('#adhocfelder .vehkind-radio[value=air]', { force: true });
        await k.seite.waitForTimeout(800);
        const vorschau = (await stand(k)).rollen;

        await k.seite.evaluate(() => document.getElementById('dayform').requestSubmit());
        await k.seite.waitForTimeout(1500);

        await k.gehZu(`${k.basis}/index.php?d=${tag}`);
        await k.seite.waitForSelector('#tagdatenknopf', { timeout: 20000 });
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf');
        await k.seite.waitForTimeout(900);
        const geladen = await stand(k);
        await k.bild('p5c-ap8-adhoc-luft');

        const formular = await rollenImEinsatzformular(k, tag);

        const ok = gleich(vorschau, ROLLEN_LUFT) && geladen.veh === 'adhoc'
                && gleich(geladen.rollen, ROLLEN_LUFT) && gleich(formular, ROLLEN_LUFT);
        return {
          ist: `Vorschau ${vorschau.join(', ') || '—'} · nach dem Neuladen („${geladen.veh}") `
             + `${geladen.rollen.join(', ') || '—'} · Einsatzformular ${formular.join(', ') || '—'}`,
          ok,
          bemerkung: ok ? '' : 'Die Rollen der Luft fehlen an mindestens einer der drei Stellen (Nr. 169)',
        };
      } finally {
        await k.gehZu(`${k.basis}/index.php?d=${tag}`).catch(() => {});
        await k.seite.waitForTimeout(600);
        await k.seite.click('#tagdatenknopf').catch(() => {});
        await k.seite.waitForTimeout(400);
        await zurueck(k, anfang.veh, anfang.base);
      }
    },
  },
];
