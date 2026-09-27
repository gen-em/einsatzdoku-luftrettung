/* Wege der Zeitraumübersicht mit Seitengrenze (`zeitraum.php`).
 * ===========================================================================
 *
 * Entstanden mit R4-17 (Backlog Nr. 37): Die Tabelle zeigt seit Web 21.4.0
 * höchstens 200 Zeilen. Das Demo-Konto hat 109 Einsätze — an ihm entstünde
 * keiner der Zustände, um die es geht. Die Einsatzliste aus `api/range.php`
 * wird deshalb auf dem Weg in den Browser vervielfacht
 * (`einsaetzeVervielfachen()` in `tools/motor.mjs`); der Browser geht damit
 * den echten Weg, und die Anwendung bekommt keine Prüftür.
 *
 * GEMESSEN WIRD, WAS STILL SCHIEFGEHEN KANN:
 *   - 200 Zeilen, die Nachladezeile, „200 angezeigt" im Kopf und die Kopfzahl
 *     als Summe über den ganzen Zeitraum;
 *   - der Sprung aus einer Extremwert-Kachel zu einer Zeile JENSEITS der 200.
 *     Ohne `zeigeEinsatz()` leuchten Kachel und Pin, und die Zeile gibt es
 *     nicht — kein Fehler, keine Meldung, nur ein Klick ins Leere;
 *   - die Karte behält alle Pins, auch die der nicht gezeichneten Zeilen.
 */
import { einsaetzeVervielfachen } from '../../motor.mjs';

/* Zwölffach: Die Luftansicht des Demo-Jahres hat 40 Einsätze, und erst über
 * 400 Zeilen steht eine Kachelzeile in EINER der beiden Sortierrichtungen
 * sicher jenseits der 200 (siehe unten). Vierfach ergab 160 — gemessen. */
const FAKTOR = 12;

export const wege = [
  {
    name: 'zeitraum-seitengrenze',
    paket: 'R4-17', punkt: 'Nr. 37', rolle: 'demo',
    soll: 'Jahr mit mehr als 200 Einsätzen: 200 Zeilen, Nachladezeile, Kopf „N · … km · 200 '
        + 'angezeigt"; alle Pins auf der Karte; eine Extremwert-Kachel holt ihre Zeile '
        + 'jenseits der 200 in die Tabelle und hebt sie hervor',
    async fahren(k) {
      const aufheben = await einsaetzeVervielfachen(k.seite, '**/api/range.php*', FAKTOR);
      try {
        await k.gehZu(`${k.basis}/zeitraum.php?y=2026`);
        await k.seite.locator('#rangebody tr').first().waitFor({ timeout: 30000 });

        /* Die Luftansicht: Nur die Artenansichten tragen Extremwert-Kacheln,
         * „Gemischt" hat vier Kacheln ohne Einsatzbezug. */
        await k.seite.locator('#artwahl input[value="air"] + label').click();
        await k.seite.waitForTimeout(300);

        const lies = () => k.seite.evaluate(() => ({
          zeilen: document.querySelectorAll('#rangebody tr').length,
          kopf: (document.getElementById('einsatzzahl').textContent || '').trim(),
          mehr: [...document.querySelectorAll('.mehrzeile:not([hidden]) button:not([hidden])')]
            .map((b) => b.textContent.trim()),
          /* `missions` steht im Seitenskript auf oberster Ebene (kein Modul) —
           * die Pins zählen, die tatsächlich auf der Karte liegen. */
          pins: missions.filter((m) => m._marker).length,
          mitOrt: missions.filter((m) => m.kind === 'air' && m._lat != null).length,
        }));
        const vorher = await lies();
        const gesamt = parseInt(vorher.kopf, 10);
        const okSeite = gesamt > 200 && vorher.zeilen === 200
          && vorher.kopf.endsWith('· 200 angezeigt')
          && vorher.mehr.length === 2 && vorher.mehr[0] === 'Weitere 200 anzeigen'
          && vorher.mehr[1] === `Alle ${gesamt} anzeigen`;
        const okPins = vorher.pins === vorher.mitOrt && vorher.pins > 200;
        /* Ohne Seitengrenze gibt es keine Zeile jenseits der 200 — die Suche
         * unten fände dann keine Kachel und gäbe dem Bestand die Schuld. */
        if (!okSeite) {
          return { ist: `Kopf „${vorher.kopf}", ${vorher.zeilen} Zeilen, Knöpfe ${vorher.mehr.join(' / ') || '—'}`,
                   ok: false, bemerkung: 'Seitengrenze, Kopf oder Nachladezeile weicht ab' };
        }

        /* Eine Kachel suchen, deren Zeile NICHT dasteht. Steht sie in der
         * voreingestellten Richtung unter den ersten 200, kehrt ein Klick
         * auf „Datum" die Reihenfolge um — bei mehr als 400 Zeilen steht sie
         * dann sicher dahinter. */
        const suche = () => k.seite.evaluate(() => {
          for (const t of document.querySelectorAll('.kennzahl[data-mid]')) {
            if (!document.querySelector(`#rangebody tr[data-mid="${t.dataset.mid}"]`)) {
              return t.dataset.mid;
            }
          }
          return null;
        });
        let mid = await suche();
        if (!mid) {
          await k.seite.locator('th.sortable[data-key="day"]').first().click();
          await k.seite.waitForTimeout(200);
          mid = await suche();
        }
        if (!mid) {
          return { ist: `${vorher.kopf}; keine Kachel jenseits der 200`, ok: false,
                   bemerkung: 'Keine Extremwert-Kachel mit einer Zeile jenseits der 200 — Bestand zu klein?' };
        }
        await k.seite.locator(`.kennzahl[data-mid="${mid}"]`).first().click();
        await k.seite.waitForTimeout(400);
        const nachher = await k.seite.evaluate((m) => {
          const zeile = document.querySelector(`#rangebody tr[data-mid="${m}"]`);
          const alle = [...document.querySelectorAll('#rangebody tr')];
          return {
            zeilen: alle.length,
            da: !!zeile,
            hervor: !!zeile && zeile.classList.contains('hl-extrem'),
            stelle: zeile ? alle.indexOf(zeile) + 1 : 0,
            kopf: (document.getElementById('einsatzzahl').textContent || '').trim(),
          };
        }, mid);
        /* In ganzen Seiten nachgeladen — oder bis zum Ende, wenn die letzte
         * Seite kürzer ist. */
        const okSprung = nachher.da && nachher.hervor && nachher.stelle > 200
          && (nachher.zeilen % 200 === 0 || nachher.zeilen === gesamt);
        await k.bild('zeitraum-seitengrenze');
        return {
          ist: `Kopf „${vorher.kopf}", ${vorher.zeilen} Zeilen, Knöpfe ${vorher.mehr.join(' / ')}; `
             + `Pins ${vorher.pins} von ${vorher.mitOrt}; Kachel → Zeile ${nachher.stelle} `
             + `${nachher.hervor ? 'hervorgehoben' : 'NICHT hervorgehoben'}, jetzt ${nachher.zeilen} Zeilen`,
          ok: okPins && okSprung,
          bemerkung: !okPins ? 'Die Karte zeichnet nicht alle Pins des Zeitraums'
                   : !okSprung ? 'Der Sprung aus der Kachel holt die Zeile nicht in die Tabelle' : '',
        };
      } finally {
        await aufheben();
      }
    },
  },
];
