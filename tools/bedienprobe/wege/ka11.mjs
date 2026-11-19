/* Wege aus Konzept KA, Paket KA-11 — Befunde und Rückweg in der Einsatzansicht.
 * ===========================================================================
 *
 * Mockup M-KA-03 (E-KA-87). Einen echten Hinweis „Uhrzeit prüfen" erzeugt
 * nur ein zweites Gerät mit nachgehender Uhr; den Kern dafür misst die
 * Hüllen- und die Ortsdatenprobe (`tools/proben/huelle/`, 606 und 17
 * Prüfungen). Hier geht es um die SEITE: Steht die Meldung an der richtigen
 * Stelle, mit dem richtigen Ton, und tun die Knöpfe, was sie sagen?
 *
 * Dazu fängt der Weg `assets/ortsdaten.js` ab und hängt eine Hülle um
 * `EdOrtsdaten.fuerAnsicht()`: Beim ersten Aufruf wirft sie, was der Kern
 * werfen würde (ein Fehler mit `grund`, und beim Hinweis `hinweis`), danach
 * lässt sie durch. Alles andere — Seite, Konto, Standbuch — ist echt.
 *
 *   - `ka11-uhrzeit-pruefen`: Meldung „Uhrzeit von … prüfen" an der Stelle
 *     der Karte, Ton warn, zwei Knöpfe; „Nicht jetzt" nimmt die Knöpfe,
 *     „Diesen Stand übernehmen" ruft `fuerAnsicht` mit `bestaetigt` = genau
 *     dem Hinweis und lädt die Seite neu.
 *   - `ka11-zurueckgelegt`, `ka11-ohne-herkunft`: rote Meldung mit dem Satz
 *     aus M-KA-03 C bzw. E, kein Knopf.
 *
 * Anlass: Nr. 563.
 */

const HINWEIS = { art: 'kleiner', z: 1784278265, absender: 'a499c9fb45a4f71c',
                  o: 1784278385, stand_absender: '5eb5678b49bd5225', vermerk: null };

/* Die Hülle um `fuerAnsicht`: `fall` sagt, was der erste Aufruf wirft. */
async function einmalWerfen(seite, fall) {
  await seite.route(/assets\/ortsdaten\.js/, async (route) => {
    const antwort = await route.fetch();
    const kopf = Object.assign({}, antwort.headers(), { 'cache-control': 'no-store' });
    delete kopf['content-length']; delete kopf['content-encoding'];
    const zusatz = `
;(function () {
  const echt = EdOrtsdaten.fuerAnsicht;
  let erst = true;
  window.__ka11 = { aufrufe: [] };
  EdOrtsdaten.fuerAnsicht = async function (m, ck, o) {
    window.__ka11.aufrufe.push(o && o.bestaetigt ? o.bestaetigt : null);
    /* Auch in die Sitzungsablage: Das Übernehmen lädt die Seite neu, und
       in WebKit kam der Neuladen schneller als die Abfrage des Wegs — der
       Weg las null (F-KA-134). Die Ablage überlebt den Neuladen. */
    if (o && o.bestaetigt) { try { sessionStorage.setItem('__ka11', JSON.stringify(o.bestaetigt)); } catch (x) { /* dann eben nicht */ } }
    if (erst) {
      erst = false;
      const e = new Error(${JSON.stringify(fall)});
      e.grund = ${JSON.stringify(fall)};
      if (e.grund === 'uhrzeit-pruefen') { e.hinweis = ${JSON.stringify(HINWEIS)}; e.art = 'einsatz'; }
      throw e;
    }
    if (o && o.bestaetigt) { return; }
    return echt.call(this, m, ck, o);
  };
})();`;
    await route.fulfill({ status: antwort.status(), headers: kopf, body: (await antwort.text()) + zusatz });
  });
}

async function einsatzMitOrt(k) {
  return k.seite.evaluate(async () => {
    const j = await (await fetch('api/suchindex.php', { credentials: 'same-origin' })).json();
    const m = (j.missions || []).find((x) => x.id && x.pat_blob);
    return m ? m.id : null;
  });
}

async function meldung(k) {
  await k.seite.waitForSelector('#map [data-ortsdaten] .meldung', { timeout: 20000 });
  return k.seite.evaluate(() => {
    const m = document.querySelector('#map [data-ortsdaten] .meldung');
    return { klasse: m.className, text: m.textContent.replace(/\s+/g, ' ').trim(),
             knoepfe: [...m.querySelectorAll('button')].map((b) => b.textContent.trim()) };
  });
}

function fehlerWeg(name, fall, satz) {
  return {
    name, paket: 'KA-11', punkt: 'E-KA-87', rolle: 'demo',
    soll: `rote Meldung „${satz}…“ an der Stelle der Karte, kein Knopf`,
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const id = await einsatzMitOrt(k);
      if (!id) { return { ist: 'kein Einsatz im Demo-Konto', ok: false }; }
      await einmalWerfen(k.seite, fall);
      try {
        await k.gehZu(`${k.basis}/einsatz.php?id=${id}`);
        const m = await meldung(k);
        const ok = m.klasse.includes('meldung-fehler') && m.text.includes(satz) && m.knoepfe.length === 0;
        return { ist: `${m.klasse.split(' ').pop()}: „${m.text.slice(0, 70)}…“, Knöpfe ${m.knoepfe.length}`, ok };
      } finally {
        await k.seite.unroute(/assets\/ortsdaten\.js/).catch(() => {});
      }
    },
  };
}

export const wege = [
  {
    name: 'ka11-uhrzeit-pruefen',
    paket: 'KA-11', punkt: 'E-KA-87', rolle: 'demo',
    soll: 'Meldung warn „Uhrzeit von … prüfen“ mit „Diesen Stand übernehmen“ und „Nicht jetzt“; '
        + '„Nicht jetzt“ nimmt die Knöpfe; Übernehmen bestätigt genau den Hinweis und lädt neu',
    async fahren(k) {
      await k.gehZu(`${k.basis}/index.php`);
      const id = await einsatzMitOrt(k);
      if (!id) { return { ist: 'kein Einsatz im Demo-Konto', ok: false }; }
      await einmalWerfen(k.seite, 'uhrzeit-pruefen');
      try {
        await k.gehZu(`${k.basis}/einsatz.php?id=${id}`);
        const m = await meldung(k);
        const warn = m.klasse.includes('meldung-warn') && m.text.includes('Uhrzeit von')
          && m.knoepfe.join('|') === 'Diesen Stand übernehmen|Nicht jetzt';
        await k.seite.click('[data-rueckweg="spaeter"]');
        const ohneKnopf = await k.seite.evaluate(() => !document.querySelector('[data-rueckweg]'));
        /* Noch einmal von vorn, dann übernehmen. */
        await k.gehZu(`${k.basis}/einsatz.php?id=${id}`);
        await meldung(k);
        await k.seite.evaluate(() => sessionStorage.removeItem('__ka11'));
        const neu = k.seite.waitForNavigation({ timeout: 20000 });
        await k.seite.click('[data-rueckweg="uebernehmen"]');
        await neu.catch(() => {});
        /* Gelesen NACH dem Neuladen, aus der Sitzungsablage (F-KA-134). */
        const h = await k.seite.evaluate(() => {
          try { return JSON.parse(sessionStorage.getItem('__ka11') || 'null'); } catch (x) { return null; }
        });
        const genau = JSON.stringify(h) === JSON.stringify(HINWEIS);
        const ok = warn && ohneKnopf && genau;
        return { ist: `Meldung ${warn ? 'wie M-KA-03 B' : `ABWEICHEND (${m.klasse}; ${m.knoepfe.join('|')})`}; `
                    + `„Nicht jetzt“ ${ohneKnopf ? 'nimmt die Knöpfe' : 'LÄSST SIE STEHEN'}; `
                    + `Übernehmen ${genau ? 'bestätigt genau den Hinweis' : 'bestätigt ETWAS ANDERES'}, Seite neu geladen`,
                 ok };
      } finally {
        await k.seite.unroute(/assets\/ortsdaten\.js/).catch(() => {});
      }
    },
  },
  fehlerWeg('ka11-zurueckgelegt', 'zurueckgelegt', 'Die Ortsdaten sind älter als der Stand'),
  fehlerWeg('ka11-ohne-herkunft', 'ohne-herkunft', 'Diese Ortsdaten tragen keinen Herkunftsnachweis'),
];
