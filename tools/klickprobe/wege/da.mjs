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
];
