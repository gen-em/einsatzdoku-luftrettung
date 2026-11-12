/* Wege aus Konzept KA, Paket KA-06b-2 — der Server für das Standbuch.
 * ===========================================================================
 *
 * Über `EdStandbuch.apiTransport()` aus `assets/standbuch.js`, also über
 * dieselbe Schnittstelle, die der Browser später benutzt, gegen
 * `api/standbuch.php` (Technik 4.42):
 *
 *   - `ka06b2-standbuch`: `stand()` liefert 16 Blätter und das Gerätebuch;
 *     ein Blatt wird abgelegt und kommt Zeichen für Zeichen zurück; wer eine
 *     veraltete Version nennt, bekommt den Konflikt MIT dem Blatt des
 *     Servers; dasselbe für das Gerätebuch. Danach drei Formfehler, je mit
 *     400 und dem Feld: ein Text außerhalb der Größenklassen, eine Version,
 *     die nicht steigt, und ein `wer` außerhalb von 0…15.
 *   - `ka06b2-fremdkonto`: Ein zweites Konto (Demo) sieht das Blatt des
 *     Prüfkontos nicht.
 *
 * Die Texte sind keine echten Blätter: Der Server prüft nur die Form
 * (`edk2:`, strenges Base64, 12 + Größenklasse + 16 Byte) und die
 * Versionsregel, öffnen kann er nichts. Ob ein Blatt aufgeht, misst die
 * Hüllenprobe. Die Probe liest erst den Stand, damit sie auf einer
 * Anlage, auf der sie schon lief, mit der dort liegenden Version weiterzählt.
 */
const WER = 3;

/* Ein Text in der Form eines Blatts: 12 + 128 + 16 Byte, zufällig. */
const textBauen = (laenge = 156) => `(() => {
  const b = crypto.getRandomValues(new Uint8Array(${laenge}));
  return 'edk2:' + btoa(String.fromCharCode(...b));
})()`;

export const wege = [
  {
    name: 'ka06b2-standbuch',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'admin',
    soll: '16 Blätter und Buch; Blatt abgelegt und gleich zurück; veraltete Version → Konflikt mit dem '
        + 'Blatt des Servers (auch Buch); 400 für Text außerhalb der Klasse, nicht steigende Version, wer 16',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const e = await k.seite.evaluate(async ({ wer, bau, krumm }) => {
        const t = EdStandbuch.apiTransport();
        const neu = eval(bau);
        const s = await t.stand();
        const form = s.blaetter.length === 16 && s.blaetter.every(Number.isInteger) && Number.isInteger(s.buch);
        const v = s.blaetter[wer];
        const a = await t.ablegen(wer, { version_alt: v, version: v + 1, text: neu });
        const h = await t.holen(wer);
        const gleich = a.ok && h && h.version === v + 1 && h.text === neu;
        const k1 = await t.ablegen(wer, { version_alt: v, version: v + 2, text: eval(bau) });
        const konflikt = !k1.ok && k1.konflikt && k1.version === v + 1 && k1.text === neu;
        const vb = s.buch;
        const b1 = await t.ablegen('buch', { version_alt: vb, version: vb + 1, text: eval(bau) });
        const b2 = await t.ablegen('buch', { version_alt: vb, version: vb + 1, text: eval(bau) });
        const buch = b1.ok && !b2.ok && b2.version === vb + 1;
        const roh = async (k) => {
          const r = await EdApi.postJson('api/standbuch.php', k);
          return r.status + ':' + ((r.daten && r.daten.feld) || '');
        };
        const fehler = [
          await roh({ aktion: 'ablegen', wer, version_alt: v + 1, version: v + 2, text: eval(krumm) }),
          await roh({ aktion: 'ablegen', wer, version_alt: v + 1, version: v + 1, text: eval(bau) }),
          await roh({ aktion: 'holen', wer: 16 }),
        ];
        return { form, gleich, konflikt, buch, fehler };
      }, { wer: WER, bau: textBauen(), krumm: textBauen(12 + 100 + 16) });
      const ok = e.form && e.gleich && e.konflikt && e.buch
        && e.fehler.join(' ') === '400:text 400:version 400:wer';
      return { ist: `Stand ${e.form ? 'in Form' : 'NICHT in Form'}, Blatt ${e.gleich ? 'gleich zurück' : 'NICHT gleich'}, `
                  + `veraltete Version ${e.konflikt ? 'Konflikt mit Serverblatt' : 'KEIN Konflikt'}, `
                  + `Buch ${e.buch ? 'wie Blatt' : 'abweichend'}, Formfehler ${e.fehler.join(' ')}`,
               ok };
    },
  },
  {
    name: 'ka06b2-fremdkonto',
    paket: 'KA-06', punkt: 'F-KA-04', rolle: 'admin',
    soll: 'das Blatt des Prüfkontos ist im Demo-Konto nicht zu sehen',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const eigen = await k.seite.evaluate(async (wer) =>
        (await EdStandbuch.apiTransport().holen(wer)), WER);
      const d = await k.rolle('demo');
      await d.gehZu(`${d.basis}/index.php`);
      const fremd = await d.seite.evaluate(async (wer) =>
        (await EdStandbuch.apiTransport().holen(wer)), WER);
      const ok = !!(eigen && eigen.text) && (!fremd || fremd.text !== eigen.text);
      return { ist: `Prüfkonto ${eigen && eigen.text ? 'hat ein Blatt' : 'hat KEIN Blatt'}, `
                  + `Demo-Konto ${fremd && fremd.text === (eigen && eigen.text) ? 'sieht es' : 'sieht es nicht'}`,
               ok };
    },
  },
];
