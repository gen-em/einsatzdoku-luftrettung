/* Wege des Pakets Demo-Ausbau (Schritt 9d).
 * ===========================================================================
 *
 *   DA-01  Der Stammdatendialog zeigt die Fähigkeits-Häkchen nach der Regel
 *          des TYPS und nicht nach der Betriebsart allein (AP0, Web 20.3.0).
 *
 * WARUM DIESER WEG UND KEIN BILD. Die Häkchen stehen im Markup jeder Seite —
 * `hidden` setzt sie das Skript des Dialogs, und zwar erst, wenn jemand Typ
 * und Betriebsart gewählt hat. Ein Bilderlauf fotografiert den Dialog
 * geschlossen und meldet fröhlich „ohne Überlauf"; was die Regel tut, sieht
 * er nie. Genau deshalb gibt es die Klickprobe.
 *
 * SIEBEN FÄLLE, NICHT ACHT. Der Typ Veranstaltung ist auf bodengebunden
 * festgelegt (`VEHICLE_TYPEN`), das Luftfeld also gesperrt — die Kombination
 * Veranstaltung/Luft lässt sich im Dialog nicht herstellen. Sie steht
 * trotzdem nicht ungeprüft da: Die Prüfschicht erzwingt sie serverseitig
 * (`pruef_typ_betriebsart()`), und der Referenzbestand führt sie als Prüffall
 * („Sanitätsdienst Seefest", absichtlich mit `art: air` und einer Winde
 * gesendet).
 *
 * DIESER WEG SCHREIBT NICHTS. Er öffnet den Dialog, wählt, liest und schließt
 * ihn wieder — kein Speichern, kein Aufräumen nötig. Dass der SCHREIBWEG die
 * Regel ebenfalls anwendet, belegt der Einspiellauf des Referenzbestands
 * (das Rettungsmittel „Bergwachtnotarzt Sonnenau" kommt mit zwei Fähigkeiten
 * an) und der edbak-Kreislauf (es kommt mit beiden zurück).
 */

/**
 * Den Diensttag eines Datums finden — und darin den Einsatz einer Uhrzeit.
 *
 * ÜBER `api/day.php` UND NICHT DURCH ANKLICKEN. Die Wege unten messen, was
 * eine Seite ZEIGT; welchen Diensttag sie zeigt, ist nicht Gegenstand der
 * Messung. Zwanzig Diensttage in der Leiste durchzuklicken kostete mehr Zeit
 * als alle Wege zusammen — und scheiterte an dem Tag, an dem jemand die
 * Sortierung ändert.
 *
 * `start_hhmm` kommt fertig in Ortszeit heraus (api/day.php); über UTC zu
 * rechnen wäre der naheliegende und falsche Weg.
 */
async function tagVon(k, datum, hhmm) {
  const tage = await k.seite.evaluate(async (basis) => {
    const r = await fetch(basis + '/api/day.php', { credentials: 'same-origin' });
    return (await r.json()).days || [];
  }, k.basis);
  const t = tage.find(d => d.day === datum && (!hhmm || d.start_hhmm === hhmm));
  if (!t) { throw new Error(`Kein Diensttag ${datum}${hhmm ? ' ' + hhmm : ''} im Bestand`); }
  return t.id;
}

async function einsatzVon(k, tagId, hhmm) {
  const l = await k.seite.evaluate(async ([basis, d]) => {
    const r = await fetch(basis + '/api/day.php?d=' + d, { credentials: 'same-origin' });
    return await r.json();
  }, [k.basis, tagId]);
  const m = (l.missions || []).find(x => x.start_hhmm === hhmm);
  if (!m) { throw new Error(`Kein Einsatz um ${hhmm} am Diensttag ${tagId}`); }
  return m.id;
}

/* Je Fall: Typ, Betriebsart, und was danach dastehen muss.
 *   caps        sind die Fähigkeits-Häkchen sichtbar?
 *   zeile       welche der beiden Kleinzeilen neben „Fähigkeiten" steht?
 *   satz        welcher der beiden Sätze darunter steht? ('-' = keiner) */
const FAELLE = [
  { typ: 'standard',      kind: 'air',    caps: true,  zeile: 'luft',  satz: '-' },
  { typ: 'standard',      kind: 'ground', caps: false, zeile: '-',     satz: '-' },
  { typ: 'bergwacht',     kind: 'air',    caps: true,  zeile: 'immer', satz: 'ohne-rollen' },
  { typ: 'bergwacht',     kind: 'ground', caps: true,  zeile: 'immer', satz: 'ohne-rollen' },
  { typ: 'veranstaltung', kind: 'ground', caps: false, zeile: '-',     satz: 'ohne-vorlagen' },
  { typ: 'sonstiges',     kind: 'air',    caps: true,  zeile: 'luft',  satz: 'ohne-rollen' },
  { typ: 'sonstiges',     kind: 'ground', caps: false, zeile: '-',     satz: 'ohne-vorlagen' },
];

/** Zustand des offenen Dialogs, in einem Zug gelesen. */
async function stand(seite) {
  return seite.evaluate(() => {
    const dlg = document.getElementById('dlg-veh');
    if (!dlg) { return null; }
    const zeile = dlg.querySelector('.vehcaps-zeile');
    const luft  = dlg.querySelector('[data-veh-caps-luft]');
    const immer = dlg.querySelector('[data-veh-caps-immer]');
    const ohneV = dlg.querySelector('[data-veh-ohne-vorlagen]');
    const ohneR = dlg.querySelector('[data-veh-ohne-rollen]');
    const sicht = (e) => !!e && !e.hidden && e.offsetParent !== null;
    return {
      caps:  sicht(zeile),
      /* Welche Kleinzeile gilt — nur die sichtbare zählt. Stünden beide oder
         keine, wäre das ein Befund und kein „unbestimmt". */
      zeile: sicht(zeile) ? (sicht(luft) && !sicht(immer) ? 'luft'
                          : sicht(immer) && !sicht(luft) ? 'immer' : 'beide/keine')
                          : '-',
      satz:  sicht(ohneV) && sicht(ohneR) ? 'beide'
           : sicht(ohneV) ? 'ohne-vorlagen'
           : sicht(ohneR) ? 'ohne-rollen' : '-',
      /* Wie viele Häkchen es überhaupt gibt — eine sichtbare, aber leere
         Zeile wäre ein Angebot ohne Inhalt. */
      haken: dlg.querySelectorAll('.vehcaps input[name="caps[]"]').length,
    };
  });
}

export const wege = [
  {
    name: 'da-faehigkeiten-nach-typ',
    paket: 'AP0', punkt: 'DA-01', rolle: 'demo',
    was: 'Der Rettungsmittel-Dialog zeigt die Fähigkeiten nach Typ UND Betriebsart',
    soll: '7 von 7 Kombinationen: Häkchen sichtbar genau bei Standard/Luft, '
        + 'Bergwacht/Luft, Bergwacht/Boden und Sonstiges/Luft; Kleinzeile '
        + '„bei diesem Typ auch bodengebunden" nur bei Typ Bergwacht; je zwei '
        + 'Häkchen',
    async fahren(k) {
      /* AUF DER SEITE EINES STANDORTS, nicht auf der Standortliste. Dort
         stehen die Karten aufgeklappt, und der Öffner „Anlegen" ist
         sichtbar; auf der Liste liegen vier Öffner in zugeklappten Karten,
         und der erste davon ist ein „Bearbeiten". */
      await k.gehZu(`${k.basis}/einstellungen.php?t=standorte`);
      const bid = await k.seite.evaluate(() => {
        const a = document.querySelector('a[href*="t=standort&s="]');
        return a ? new URL(a.href, location.href).searchParams.get('s') : null;
      });
      if (!bid) { throw new Error('Kein Standort in der Liste — ist der Bestand leer?'); }
      await k.gehZu(`${k.basis}/einstellungen.php?t=standort&s=${bid}`);
      const OEFFNER = '[data-dialog="dlg-veh"][data-w-id="0"]';
      await k.seite.waitForSelector(OEFFNER, { state: 'visible', timeout: 20000 });

      const treffer = [];
      const ab = [];
      for (const f of FAELLE) {
        /* Der Dialog wird je Fall neu geöffnet. Ein einmal geöffneter und
           umgestellter Dialog trüge die Häkchen des Falls davor — und die
           Regel leert sie nur, wenn sie verschwinden, nicht wenn sie
           auftauchen. */
        await k.seite.evaluate(() => {
          const d = document.getElementById('dlg-veh');
          if (d && d.open) { d.close(); }
        });
        await k.seite.click(OEFFNER);
        await k.seite.waitForTimeout(250);

        await k.seite.selectOption('#dlgveh-typ', f.typ);
        await k.seite.waitForTimeout(120);
        /* Das Feld ist unsichtbar gestaltet (ui.php); bedient wird die
           Beschriftung — hier reicht der programmatische Weg samt
           `change`, weil `anpassen()` genau daran hängt. */
        await k.seite.evaluate((kind) => {
          const r = document.querySelector(`#dlg-veh .vehkind-radio[value="${kind}"]`);
          if (r && !r.disabled) { r.checked = true; r.dispatchEvent(new Event('change', { bubbles: true })); }
        }, f.kind);
        await k.seite.waitForTimeout(120);

        const ist = await stand(k.seite);
        /* EIN BILD VOM INTERESSANTEN FALL, nicht nur vom letzten. Der Weg
           endet bei Sonstiges/Boden — dort sind die Häkchen weg, und genau
           das zeigt die Änderung NICHT. */
        if (f.typ === 'bergwacht' && f.kind === 'ground') {
          await k.bild('da-bergwacht-boden-mit-faehigkeiten');
        }
        const gut = ist && ist.caps === f.caps && ist.zeile === f.zeile
                 && ist.satz === f.satz && (!f.caps || ist.haken === 2);
        if (gut) { treffer.push(`${f.typ}/${f.kind}`); }
        else {
          ab.push(`${f.typ}/${f.kind}: Häkchen ${ist ? ist.caps : '—'} (soll ${f.caps})`
                + `, Kleinzeile ${ist ? ist.zeile : '—'} (soll ${f.zeile})`
                + `, Satz ${ist ? ist.satz : '—'} (soll ${f.satz})`
                + `, ${ist ? ist.haken : '—'} Häkchen`);
        }
      }
      await k.bild('da-faehigkeiten-nach-typ');
      await k.seite.evaluate(() => {
        const d = document.getElementById('dlg-veh');
        if (d && d.open) { d.close(); }
      });
      return {
        ist: `${treffer.length} von ${FAELLE.length} Kombinationen wie erwartet`
           + (ab.length ? ' · ' + ab.join(' · ') : ''),
        ok: treffer.length === FAELLE.length,
        bemerkung: ab.join(' · '),
      };
    },
  },

  {
    name: 'da-windenkacheln-am-bodendienst',
    paket: 'AP3', punkt: 'DA-02', rolle: 'demo',
    was: 'Ein bodengebundener Bergwacht-Diensttag zeigt Winde und Bergwacht im Einsatzformular',
    soll: 'Gruppe „Bergrettung" vorhanden; Windeneinsatz gesetzt, Cycles 1, '
        + 'Cycles mit Patient 1, Luftverladung gesetzt, Bereitschaft „Bergwacht Felsgrat"',
    async fahren(k) {
      /* DIE PROBE AUF DIE REGELÄNDERUNG AUS AP0, und zwar am fertigen
         Bestand. Bis Web 20.3.0 verwarf die Prüfschicht die Fähigkeiten
         eines bodengebundenen Bergwacht-Rettungsmittels still; der Diensttag
         trug dann keine, und `cap_gate` ließ die ganze Gruppe weg. Was hier
         gemessen wird, ist nicht die Regel, sondern ihre FOLGE: dass im
         Formular tatsächlich etwas steht. */
      const tag = await tagVon(k, '2026-08-01');
      const id = await einsatzVon(k, tag, '08:40');
      await k.gehZu(`${k.basis}/einsatz_form.php?id=${id}`);
      await k.seite.waitForSelector('form', { timeout: 20000 });
      await k.seite.waitForTimeout(400);
      const m = await k.seite.evaluate(() => {
        const wert = (n) => {
          const e = document.querySelector(`[name="f_${n}"]`);
          if (!e) { return null; }
          return e.type === 'checkbox' ? (e.checked ? 1 : 0) : e.value;
        };
        const sicht = (e) => !!e && e.offsetParent !== null;
        return {
          gruppe: !!document.querySelector('#gruppe-bergrettung, [data-gruppe="bergrettung"]')
               || sicht(document.querySelector('[name="f_winch"]')?.closest('.feld, .karte')),
          winch: wert('winch'), cycles: wert('winch_cycles'),
          cyclesPat: wert('winch_cycles_pat'), airload: wert('winch_airload'),
          bergwacht: wert('bergwacht'), einheit: wert('bw_unit'),
          tagKind: document.body.dataset.kind || null,
        };
      });
      await k.bild('da-windenkacheln-am-bodendienst');
      const ok = m.gruppe && m.winch === 1 && String(m.cycles) === '1'
              && String(m.cyclesPat) === '1' && m.airload === 1
              && m.bergwacht === 1 && /Felsgrat/.test(String(m.einheit));
      return {
        ist: `Gruppe sichtbar: ${m.gruppe} · Winde ${m.winch}, Cycles ${m.cycles}, `
           + `mit Patient ${m.cyclesPat}, Luftverladung ${m.airload} · `
           + `Bergwacht ${m.bergwacht}, Bereitschaft „${m.einheit ?? '—'}"`,
        ok,
        bemerkung: ok ? ''
          : 'Der bodengebundene Bergwacht-Diensttag zeigt seine Fähigkeiten nicht '
          + '(AP0/Web 20.3.0 greift nicht, oder day_capabilities sind leer)',
      };
    },
  },

  {
    name: 'da-tag-ohne-standort',
    paket: 'AP3', punkt: 'DA-03', rolle: 'demo',
    was: 'Ein Diensttag ohne Standort führt kein Standortfeld, keine Rollen und ein Freitext-Ziel',
    soll: 'Standortfeld leer oder fehlend · 0 Rollenfelder · Transportziel mit Koordinate, '
        + 'aber ohne Vorschlagsliste',
    async fahren(k) {
      const tag = await tagVon(k, '2026-06-14', '19:30');
      await k.gehZu(`${k.basis}/index.php?d=${tag}`);
      await k.seite.waitForTimeout(1200);
      /* GEMESSEN WIRD DIE LESEANSICHT UND `api/day.php`, NICHT DAS
         AUSWAHLFELD. Ein `<select name="base_id">` ohne passende Option
         liefert den Wert seiner ERSTEN Option — hier „3" —, und das sagt
         nichts darüber, was am Diensttag steht. Der erste Entwurf dieses
         Wegs hat genau darauf geschaut und einen Standort gemeldet, den es
         nicht gibt. */
      const m = await k.seite.evaluate(async ([basis, d]) => {
        const r = await fetch(basis + '/api/day.php?d=' + d, { credentials: 'same-origin' });
        const j = await r.json();
        /* ÜBER DIE BESCHRIFTUNG DER ZEILE, nicht über den Text der Karte.
           Die Leseansicht baut je Angabe ein `<dt>`; der Text daneben ist
           frei. Die Tagesnotiz dieses Diensttags lautet „Kein Standort,
           Sanitätsraum in der Halle" — ein `textContent`-Test auf das Wort
           meldete daran einen Standort, den es nicht gibt. (Gefunden beim
           Bauen dieses Wegs, 15.09.2026.) */
        const beschriftungen = Array.from(
          document.querySelectorAll('#taglese .tagfeld dt')).map(e => e.textContent.trim());
        return {
          baseName: (j.meta || j).base_name ?? null,
          artText: (j.meta || j).art_text || '',
          leseStandort: beschriftungen.includes('Standort'),
          lese: beschriftungen.join(', ') || '—',
          rollen: document.querySelectorAll('#crewfields [name^="crew_"]').length,
        };
      }, [k.basis, tag]);
      const id = await einsatzVon(k, tag, '20:15');
      await k.gehZu(`${k.basis}/einsatz.php?id=${id}`);
      await k.seite.waitForTimeout(700);
      const e = await k.seite.evaluate(() => {
        const t = document.body.textContent || '';
        return {
          ziel: /Kreisklinik Steinach/.test(t),
          karte: !!document.querySelector('.leaflet-container'),
        };
      });
      await k.bild('da-tag-ohne-standort');
      const ok = m.baseName === null && !m.leseStandort && m.rollen === 0 && e.ziel;
      return {
        ist: `base_name ${m.baseName === null ? 'null' : `„${m.baseName}"`} · `
           + `Art „${m.artText}" · Zeile „Standort" in der Leseansicht: ${m.leseStandort} `
           + `(Zeilen: ${m.lese}) · ${m.rollen} Rollenfelder · Transportziel im Einsatz `
           + `gefunden: ${e.ziel} · Karte: ${e.karte}`,
        ok,
        bemerkung: ok ? '' : 'Der Tag ohne Standort führt doch einen Standort oder Rollen',
      };
    },
  },

  {
    name: 'da-bergwachtkarte-ohne-besatzung',
    paket: 'AP3', punkt: 'DA-04', rolle: 'demo',
    was: 'Ein Standort mit nur einem Bergwacht-Rettungsmittel zeigt Bereitschaften, aber keine Rollen',
    soll: 'Karte „Bergwacht" vorhanden (AP0: die Fähigkeit darf dort geführt werden), '
        + 'Besatzungskarte ohne Anlegen-Weg (Typ Bergwacht hat keine Rollen-Vorlagen)',
    async fahren(k) {
      /* DER STANDORT, DEN ES OHNE AP0 NICHT GEBEN KÖNNTE. Die Karte
         „Bergwacht-Bereitschaften" erschien bis Web 20.3.0 nur, wenn am
         Standort ein LUFTgebundenes Rettungsmittel stand. Eine
         Bergwachtstation mit einem bodengebundenen Notarzt hätte danach das
         Feld „Bereitschaft" im Einsatz gehabt — und keinen Ort, an dem sich
         Bereitschaften anlegen lassen. Drei der drei Bereitschaften dieses
         Standorts wären unsichtbar im Bestand gelegen. */
      await k.gehZu(`${k.basis}/einstellungen.php?t=standorte`);
      const bid = await k.seite.evaluate(() => {
        const a = Array.from(document.querySelectorAll('a.zeile[href*="t=standort&s="]'))
          .find(x => /Sonnenau/.test(x.textContent || ''));
        return a ? Number(new URL(a.href, location.href).searchParams.get('s')) : null;
      });
      if (!bid) { return { ist: 'Kein Standort „…Sonnenau" in der Liste', ok: false }; }
      await k.gehZu(`${k.basis}/einstellungen.php?t=standort&s=${bid}`);
      await k.seite.waitForTimeout(400);
      const m = await k.seite.evaluate(() => {
        const sicht = (e) => !!e && e.offsetParent !== null;
        const bw = document.querySelector('.karte#k-bergwacht');
        return {
          bergwachtKarte: sicht(bw),
          bereitschaften: bw ? bw.querySelectorAll('.zeile').length : 0,
          crewAnlegen: sicht(document.querySelector('.karte#k-besatzung .karte-aktion[data-dialog="dlg-crew"]')),
          rettungsmittel: document.querySelectorAll('.karte#k-rettungsmittel .zeile').length,
        };
      });
      await k.bild('da-bergwachtkarte-ohne-besatzung');
      const ok = m.bergwachtKarte && m.bereitschaften >= 3 && !m.crewAnlegen;
      return {
        ist: `Bergwacht-Karte sichtbar: ${m.bergwachtKarte} mit ${m.bereitschaften} `
           + `Bereitschaften · Besatzung anlegbar: ${m.crewAnlegen} · `
           + `${m.rettungsmittel} Rettungsmittel am Standort`,
        ok,
        bemerkung: ok ? ''
          : (m.bergwachtKarte
              ? 'Die Besatzungskarte bietet ein Anlegen an, obwohl der Typ keine Rollen führt'
              : 'Die Bergwacht-Karte fehlt — `veh_caps_erlaubt()` greift nicht (AP0)'),
      };
    },
  },
];
