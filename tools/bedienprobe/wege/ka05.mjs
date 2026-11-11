/* Wege aus Konzept KA, Paket KA-05 — Fremdbestandteile im Browser.
 * ===========================================================================
 *
 * Auf der Importseite, die SheetJS lädt und `EdHtml` benutzt:
 *
 *   - Die geladene SheetJS-Fassung ist 0.20.3 (F-KA-11). 0.18.5 hatte zwei
 *     bekannte Lücken beim Lesen fremder Dateien.
 *   - `EdHtml.meldung()` liest nur EIGENE Eigenschaften seiner Optionen.
 *     Gesetzt wird `knopf` und `roh` am Prototyp aller Objekte — der Weg, den
 *     eine solche Lücke öffnet —, dann eine Meldung mit leerem
 *     Optionsobjekt gebaut. Bis Web 24.12.1 stand das geerbte Markup in der
 *     Meldung. Die Lücke selbst stellt der Weg nicht nach, nur das Gadget.
 */
export const wege = [
  {
    name: 'ka05-fremdbestandteile',
    paket: 'KA-05', punkt: 'F-KA-11', rolle: 'demo',
    soll: 'Importseite lädt SheetJS 0.20.3; EdHtml.meldung() übernimmt kein am '
        + 'Prototyp gesetztes knopf/roh, ein eigenes knopf wirkt',
    async fahren(k) {
      await k.gehZu(`${k.basis}/import.php`);
      const e = await k.seite.evaluate(() => {
        const fassung = (typeof XLSX === 'object' && XLSX.version) || '—';
        Object.prototype.knopf = '<i data-geerbt="1"></i>';
        Object.prototype.roh = true;
        let m;
        try { m = EdHtml.meldung('ok', '<b>x</b>', {}); } finally {
          delete Object.prototype.knopf; delete Object.prototype.roh;
        }
        const eigen = EdHtml.meldung('ok', 't', { knopf: '<a data-eigen="1"></a>' });
        return { fassung, geerbtKnopf: m.includes('data-geerbt'), geerbtRoh: m.includes('<b>x</b>'),
                 eigen: eigen.includes('data-eigen') };
      });
      const ok = e.fassung === '0.20.3' && !e.geerbtKnopf && !e.geerbtRoh && e.eigen;
      return { ist: `SheetJS ${e.fassung}, geerbtes knopf ${e.geerbtKnopf ? 'wirkt' : 'wirkt nicht'}, `
                  + `geerbtes roh ${e.geerbtRoh ? 'wirkt' : 'wirkt nicht'}, eigenes knopf ${e.eigen ? 'wirkt' : 'fehlt'}`,
               ok };
    },
  },
];
