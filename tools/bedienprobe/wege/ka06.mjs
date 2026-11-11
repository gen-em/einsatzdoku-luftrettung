/* Wege aus Konzept KA, Paket KA-06 (Ableitung, vorab) — F-KA-13 und F-KA-16.
 * ===========================================================================
 *
 * Auf einer angemeldeten Seite mit dem Rüstzeug (`crypto.js`, `unlock.js`):
 *
 *   - `ka06-ableitung`: Die Rundenzahl hat eine Untergrenze von 310 000, das
 *     Salz eine Form (32 Zeichen Hex). Eine Antwort des Salz-Endpunkts mit
 *     1 000 Runden oder einem leeren Salz wird abgewiesen, und zwar mit
 *     `status` — die Anmeldeseite sagt dann „derzeit nicht möglich" statt
 *     „Browser unterstützt nicht". Bis Web 24.13.0 nahm der Browser alles ab
 *     1 000 und jedes Salz.
 *   - `ka06-vormerkfach`: Das Fach hat eine Frist. Ein Fach von vor sieben
 *     Minuten und eines ohne Zeitstempel liefert `holeAbleitungen()` nicht
 *     mehr und räumt es; ein frisches liefert es. Danach die eigentliche
 *     Lücke: ein frisches Fach, dann die Startseite des Prüfkontos — ein
 *     Konto, dem weder Rückweg noch Kontopaar fehlt und dessen Startseite
 *     den Inhaltsschlüssel beim Laden nicht braucht. Bis Web 24.13.0 blieb
 *     das Fach dort stehen (gemessen; Suche, Import und die Startseite des
 *     Demo-Kontos fragen nach dem Schlüssel und räumten es schon immer).
 *     Eine Grenze bleibt: Einstellungen → Profil lädt `unlock.js` nicht,
 *     dort räumt erst die Frist.
 */
const SALZ = '0123456789abcdef0123456789abcdef';

export const wege = [
  {
    name: 'ka06-ableitung',
    paket: 'KA-06', punkt: 'F-KA-13', rolle: 'demo',
    soll: 'deriveKeys weist 1 000 Runden und ein leeres Salz ab, nimmt 310 000 mit '
        + 'gültigem Salz; anmeldeTokens weist eine Antwort mit 1 000 Runden mit status ab',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const e = await k.seite.evaluate(async (salz) => {
        const wirft = async f => { try { await f(); return null; } catch (x) { return x; } };
        const runden = await wirft(() => EdCrypto.deriveKeys('pw', salz, 1000));
        const leer = await wirft(() => EdCrypto.deriveKeys('pw', '', 600000));
        const gut = await wirft(() => EdCrypto.deriveKeys('pw', salz, 310000));
        const echt = window.fetch;
        window.fetch = async () => new Response(JSON.stringify({ salt: salz, iter: [1000] }),
          { status: 200, headers: { 'Content-Type': 'application/json' } });
        let antwort;
        try { antwort = await wirft(() => EdCrypto.anmeldeTokens('x@example.org', 'pw')); }
        finally { window.fetch = echt; }
        return { runden: !!runden, leer: !!leer, gut: !gut,
                 antwort: !!antwort, status: antwort ? String(antwort.status || '') : '' };
      }, SALZ);
      const ok = e.runden && e.leer && e.gut && e.antwort && e.status !== '';
      return { ist: `1 000 Runden ${e.runden ? 'abgewiesen' : 'angenommen'}, leeres Salz `
                  + `${e.leer ? 'abgewiesen' : 'angenommen'}, 310 000 ${e.gut ? 'angenommen' : 'abgewiesen'}, `
                  + `Antwort mit 1 000 Runden ${e.antwort ? 'abgewiesen' : 'angenommen'}`
                  + (e.antwort ? ` (status „${e.status}")` : ''),
               ok };
    },
  },
  {
    name: 'ka06-vormerkfach',
    paket: 'KA-06', punkt: 'F-KA-16', rolle: 'admin',
    soll: 'altes und zeitloses Fach werden verworfen, frisches geliefert; ein frisches '
        + 'Fach ist nach dem Laden der Startseite leer',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const e = await k.seite.evaluate(() => {
        const hf = { 600000: '00'.repeat(32) }, tk = { 600000: '11'.repeat(32) };
        const legen = o => sessionStorage.setItem('edkvor', JSON.stringify(o));
        legen({ hf, tk, t: Date.now() - 7 * 60 * 1000 });
        const alt = EdCrypto.holeAbleitungen() === null && sessionStorage.getItem('edkvor') === null;
        legen({ hf, tk });
        const zeitlos = EdCrypto.holeAbleitungen() === null && sessionStorage.getItem('edkvor') === null;
        legen({ hf, tk, t: Date.now() });
        const frisch = EdCrypto.holeAbleitungen() !== null;
        return { alt, zeitlos, frisch,
                 rw: typeof RW_STAND === 'undefined' ? '—' : RW_STAND,
                 kp: typeof KP_STAND === 'undefined' ? '—' : KP_STAND };
      });
      /* Das frische Fach liegt; jetzt die Startseite neu laden — sie braucht
       * den Inhaltsschlüssel nicht, und dem Konto fehlt nichts. */
      await k.gehZu(`${k.basis}/index.php`);
      await k.seite.waitForTimeout(1500);
      const nach = await k.seite.evaluate(() => sessionStorage.getItem('edkvor'));
      await k.seite.evaluate(() => sessionStorage.removeItem('edkvor'));
      const ok = e.alt && e.zeitlos && e.frisch && nach === null;
      return { ist: `altes Fach ${e.alt ? 'verworfen' : 'geliefert'}, zeitloses ${e.zeitlos ? 'verworfen' : 'geliefert'}, `
                  + `frisches ${e.frisch ? 'geliefert' : 'verworfen'}; nach der Startseite `
                  + `(RW_STAND ${e.rw}, KP_STAND ${e.kp}) ${nach === null ? 'leer' : 'belegt'}`,
               ok };
    },
  },
];
