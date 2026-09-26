/* Wege des Zweitfaktors (`zweitfaktor.php`, P5c/AP5).
 * ===========================================================================
 *
 * Entstanden mit P5c/AP5 (E-P5c-41, -61, -87; Bild M-P5c-02b). Nach der
 * SEITE benannt (E-PK-15).
 *
 *   zweitfaktor-einrichten      Einrichtungstor für eine Pflichtrolle: der
 *                               QR-Code, gelesen mit jsQR aus einem Abzug,
 *                               ist Zeichen für Zeichen die angezeigte
 *                               otpauth-Adresse; mit dem Code eingeschaltet
 *                               stehen zehn Codes da, und „Weiter" wird erst
 *                               mit dem Haken frei
 *
 * EIN EIGENES KONTO, KEINE DER BEIDEN ROLLEN DES LÄUFERS: Das Prüfkonto hat
 * seinen Zweitfaktor schon, und das Demo-Konto darf keinen haben. Der Weg
 * legt ein Konto der Rolle support mit bekanntem Passwort an
 * (`probekonto.mjs`), meldet es in einem EIGENEN Kontext an und räumt es im
 * `finally` wieder ab.
 *
 * DER QR-CODE WIRD AUF EINER LEEREN SEITE GELESEN, nicht auf der Anwendung:
 * Deren Content-Security-Policy lässt kein fremdes Skript zu, und das ist
 * richtig so. Der Abzug des `svg.qr` geht als Bild auf `about:blank`, dort
 * liest jsQR (`tools/bedienprobe/vendor/jsQR.js`, nur Prüfwerkzeug) ihn aus
 * einem Canvas. Der Decoder rechnet also am BILD, das der Browser gezeichnet
 * hat — nicht an der Adresse im Markup, die ohnehin danebensteht.
 */

import { readFileSync, unlinkSync } from 'node:fs';
import { join, dirname } from 'node:path';
import { fileURLToPath } from 'node:url';
import { naechsterCode, zaehlerdatei } from '../../zweitfaktor/totp.mjs';
import { probekontoAnlegen, probekontoRaeumen, eigenerKontext, passwortSchicken }
  from '../probekonto.mjs';

const HIER = dirname(fileURLToPath(import.meta.url));
const JSQR = readFileSync(join(HIER, '..', 'vendor', 'jsQR.js'), 'utf-8');
const ADRESSE = 'bedienprobe-zweitfaktor@probe.invalid';
const PASSWORT = 'Bedienprobe-Zweitfaktor-Anker-Glas-7';

export const wege = [
  {
    name: 'zweitfaktor-einrichten',
    paket: 'P5c-AP5', punkt: 'E-P5c-87', rolle: 'demo',
    soll: 'QR gelesen = otpauth-Adresse; nach dem Code 10 Codes; „Weiter" gesperrt, '
        + 'mit Haken frei; danach die Startseite',
    async fahren(k) {
      probekontoAnlegen(ADRESSE, PASSWORT, 'support', 'Bedienprobe Zweitfaktor');
      const kontext = await eigenerKontext(k);
      let geheimnis = null;
      try {
        const s = await kontext.newPage();
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForURL(/zweitfaktor\.php/, { timeout: 90000 });
        await s.waitForSelector('svg.qr:not([hidden])', { timeout: 10000 });
        const adresse = await s.locator('.zweitfaktor-einrichtung a.knopf').getAttribute('href');
        geheimnis = (await s.locator('.zweitfaktor-einrichtung .codeblock-wert').textContent())
          .replace(/\s+/g, '');
        await k.bild('zweitfaktor-tor');
        const abzug = (await s.locator('svg.qr').screenshot()).toString('base64');

        /* jsQR auf einer leeren Seite: ohne CSP, ohne die Anwendung. */
        const leer = await kontext.newPage();
        await leer.setContent('<canvas id="c"></canvas>');
        await leer.addScriptTag({ content: JSQR });
        const gelesen = await leer.evaluate(async (b64) => {
          const img = new Image();
          img.src = 'data:image/png;base64,' + b64;
          await img.decode();
          const c = document.getElementById('c');
          c.width = img.width; c.height = img.height;
          const g = c.getContext('2d');
          g.drawImage(img, 0, 0);
          const d = g.getImageData(0, 0, c.width, c.height);
          const r = window.jsQR(d.data, d.width, d.height);
          return r ? r.data : null;
        }, abzug);
        await leer.close();

        await s.fill('#f-totp', await naechsterCode(geheimnis));
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.locator('form button[type="submit"]').first().click(),
        ]);
        const codes = await s.locator('.codeblock-liste li').count();
        const vorher = await s.locator('[data-zf-weiter]').isDisabled();
        await s.check('[data-zf-gesichert]');
        const nachher = await s.locator('[data-zf-weiter]').isDisabled();
        await k.bild('zweitfaktor-codes');
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.locator('[data-zf-weiter]').click(),
        ]);
        const ziel = new URL(s.url()).pathname.split('/').pop();

        const qrGleich = gelesen !== null && gelesen === adresse;
        const ok = qrGleich && codes === 10 && vorher === true && nachher === false
                && ziel === 'index.php';
        return {
          ist: `QR ${gelesen === null ? 'nicht gelesen' : (qrGleich ? '= Adresse' : '≠ Adresse')} · `
             + `${codes} Codes · Weiter ${vorher ? 'gesperrt' : 'frei'} → `
             + `${nachher ? 'gesperrt' : 'frei'} · ${ziel}`,
          ok,
          bemerkung: ok ? '' : `gelesen: ${gelesen} · angezeigt: ${adresse}`,
        };
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
