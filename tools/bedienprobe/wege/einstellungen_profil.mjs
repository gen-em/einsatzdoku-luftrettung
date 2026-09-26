/* Wege der Seite Einstellungen → Profil (`einstellungen.php?t=profil`).
 * ===========================================================================
 *
 * Entstanden mit P5c/AP5 (E-P5c-15, -53, -54; Abnahme im Konzept, AP5). Nach
 * der SEITE benannt (E-PK-15).
 *
 *   einstellungen-profil-zweitfaktor
 *                  Eine NutzerIn schaltet den Zweitfaktor auf der
 *                  Profilkarte ein (Base32 von der Seite gelesen, der Code
 *                  in Node gerechnet), meldet sich ab und wieder an: ein
 *                  falscher Code wird abgewiesen, DERSELBE Code wie beim
 *                  Einschalten auch, der nächste lässt sie hinein. Nach
 *                  „Zurück zur Anmeldung" im Code-Schritt ist das
 *                  Vormerkfach leer.
 *
 * WARUM DIE WIEDERHOLUNG IM FENSTER GEPRÜFT WIRD: Ein Code ist ±1 Zeitschritt
 * gültig, rund 90 s. Wird der Code vom Einschalten erst danach noch einmal
 * geschickt, weist ihn schon die Uhr ab — und der Weg bewiese nichts über
 * `totp_schritt`. Der Weg rechnet deshalb nach, ob der Schritt beim zweiten
 * Versuch noch im Fenster lag, und nennt es; lag er draußen, ist der Weg
 * verfehlt statt grün.
 *
 * DAS VORMERKFACH ist `sessionStorage['edkvor']`: Die Anmeldeseite legt die
 * abgeleiteten Hälften dort ab, bevor sie das Passwort schickt. Der
 * Code-Schritt lädt kein `unlock.js` und öffnet nichts; bricht jemand ab,
 * räumt die Anmeldeseite das Fach (E-P5c-53). Gemessen wird VORHER (belegt)
 * und NACHHER (leer) — ein Fach, das nie belegt war, wäre auch „leer".
 */

import { unlinkSync } from 'node:fs';
import { naechsterCode, base32, code, zaehlerdatei } from '../../zweitfaktor/totp.mjs';
import { probekontoAnlegen, probekontoRaeumen, eigenerKontext, passwortSchicken }
  from '../probekonto.mjs';

const ADRESSE = 'bedienprobe-profil@probe.invalid';
const PASSWORT = 'Bedienprobe-Profil-Kiesel-Mond-4';
const schrittJetzt = () => Math.floor(Date.now() / 1000 / 30);

/** Den Zeitschritt suchen, zu dem ein Code gehört — nahe an jetzt. */
function schrittVon(roh, c) {
  const j = schrittJetzt();
  for (let s = j - 2; s <= j + 2; s++) { if (code(roh, s) === c) { return s; } }
  return null;
}

/** Ein Code, der in keinem Schritt um jetzt gilt. */
function falscherCode(roh) {
  const j = schrittJetzt();
  const gueltig = new Set([-2, -1, 0, 1, 2].map(d => code(roh, j + d)));
  for (let n = 123456; ; n = (n + 111111) % 1000000) {
    const c = String(n).padStart(6, '0');
    if (!gueltig.has(c)) { return c; }
  }
}

async function codeSchicken(s, c) {
  await s.fill('#codeform input[name="code"]', c);
  await Promise.all([
    s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    s.click('#codeform button[type="submit"]'),
  ]);
}
const seiteIst = s => new URL(s.url()).pathname.split('/').pop();
const codeSchrittDa = async s => seiteIst(s) === 'login.php'
  && (await s.locator('#codeform').count()) === 1;
const fehlerText = async s => ((await s.locator('#codeform .meldung').textContent()
  .catch(() => '')) || '').replace(/\s+/g, ' ').trim();

export const wege = [
  {
    name: 'einstellungen-profil-zweitfaktor',
    paket: 'P5c-AP5', punkt: 'E-P5c-53', rolle: 'demo',
    soll: 'eingeschaltet mit 10 Codes; danach falscher Code und derselbe Code abgewiesen, '
        + 'der nächste → Startseite; nach „Zurück zur Anmeldung" Vormerkfach belegt → leer',
    async fahren(k) {
      probekontoAnlegen(ADRESSE, PASSWORT, 'user', 'Bedienprobe Profil');
      const kontext = await eigenerKontext(k);
      const teile = [];
      let ok = true;
      const merke = (gut, text) => { teile.push(text); if (!gut) { ok = false; } };
      let geheimnis = null;
      try {
        const s = await kontext.newPage();

        /* 1. Ohne Zweitfaktor: das Passwort genügt. */
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForURL(u => !/login\.php/.test(u.pathname), { timeout: 90000 });

        /* 2. Einschalten auf der Profilkarte. */
        await s.goto(`${k.basis}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.click('#k-zweitfaktor button[value="zf_beginnen"]'),
        ]);
        geheimnis = (await s.locator('#k-zweitfaktor .codeblock-wert').textContent())
          .replace(/\s+/g, '');
        const roh = base32(geheimnis);
        const c1 = await naechsterCode(geheimnis);
        const s1 = schrittVon(roh, c1);
        await s.fill('#f-totp', c1);
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.click('#k-zweitfaktor button[value="zf_einschalten"]'),
        ]);
        const codes = await s.locator('#k-zweitfaktor .codeblock-liste li').count();
        merke(codes === 10, `eingeschaltet, ${codes} Codes`);

        /* 3. Abmelden, anmelden: jetzt kommt der Code-Schritt. */
        await s.goto(`${k.basis}/logout.php`, { waitUntil: 'domcontentloaded' });
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForSelector('#codeform', { timeout: 90000 });

        /* 4. Ein falscher Code. */
        await codeSchicken(s, falscherCode(roh));
        const f1 = await fehlerText(s);
        merke(await codeSchrittDa(s) && /Code passt nicht/.test(f1), 'falscher Code '
          + (/Code passt nicht/.test(f1) ? 'abgewiesen' : `nicht abgewiesen (${f1 || seiteIst(s)})`));

        /* 5. Derselbe Code wie beim Einschalten — noch im Fenster? */
        await codeSchicken(s, c1);
        /* Erst NACH der Antwort gemessen: Die Uhr läuft nur vorwärts, und was
           jetzt noch im Fenster liegt, lag es auch, als der Server prüfte. */
        const abstand = schrittJetzt() - s1;
        const f2 = await fehlerText(s);
        const imFenster = abstand <= 1;
        merke(imFenster && await codeSchrittDa(s) && /Code passt nicht/.test(f2),
          `derselbe Code ${/Code passt nicht/.test(f2) ? 'abgewiesen' : 'angenommen'} `
          + `(${imFenster ? 'im Fenster' : 'außerhalb des Fensters — nichts bewiesen'}, `
          + `Abstand ${abstand} Schritt)`);

        /* 6. Der nächste Code lässt hinein. */
        if (await codeSchrittDa(s)) {
          await codeSchicken(s, await naechsterCode(geheimnis));
        }
        const ziel = seiteIst(s);
        merke(ziel === 'index.php', `nächster Code → ${ziel}`);

        /* 7. Abbruch im Code-Schritt räumt das Vormerkfach. */
        await s.goto(`${k.basis}/logout.php`, { waitUntil: 'domcontentloaded' });
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForSelector('#codeform', { timeout: 90000 });
        const vorher = await s.evaluate(() => sessionStorage.getItem('edkvor') !== null);
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.click('a[href="login.php?abbrechen=1"]'),
        ]);
        await s.waitForLoadState('load');
        const nachher = await s.evaluate(() => sessionStorage.getItem('edkvor') !== null);
        const formDa = (await s.locator('#loginform').count()) === 1;
        merke(vorher && !nachher && formDa, `Vormerkfach ${vorher ? 'belegt' : 'leer'} → `
          + `${nachher ? 'belegt' : 'leer'}`);

        return { ist: teile.join(' · '), ok, bemerkung: '' };
      } finally {
        await kontext.close();
        probekontoRaeumen(ADRESSE);
        /* Der Zähler gehört zu einem Geheimnis, das es nach dem Lauf nicht
           mehr gibt. */
        if (geheimnis) { try { unlinkSync(zaehlerdatei(geheimnis)); } catch { /* nie angelegt */ } }
      }
    },
  },
];
