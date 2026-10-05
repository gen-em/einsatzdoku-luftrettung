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
 *   zweitfaktor-merken          „Dieses Gerät n Tage merken" (Schritt 18,
 *                               SR-02): der Haken nennt die Dauer der
 *                               Rollengruppe; angehakt fragt die nächste
 *                               Anmeldung im selben Browser keinen Code, und
 *                               das Profil zählt ein Gerät
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
import { execFileSync } from 'node:child_process';
import { naechsterCode, zaehlerdatei, SANDBOX } from '../../zweitfaktor/totp.mjs';
import { probekontoAnlegen, probekontoRaeumen, eigenerKontext, passwortSchicken, php, WURZEL }
  from '../probekonto.mjs';

const HIER = dirname(fileURLToPath(import.meta.url));
const JSQR = readFileSync(join(HIER, '..', 'vendor', 'jsQR.js'), 'utf-8');
const ADRESSE = 'bedienprobe-zweitfaktor@probe.invalid';
const PASSWORT = 'Bedienprobe-Zweitfaktor-Anker-Glas-7';
const ADRESSE_M = 'bedienprobe-merken@probe.invalid';

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
        await k.bild('zweitfaktor-tor', s);
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
        await k.bild('zweitfaktor-codes', s);
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
  {
    name: 'zweitfaktor-merken',
    paket: 'SR-02', punkt: 'E-SR-18', rolle: 'demo',
    soll: 'Haken „Dieses Gerät 30 Tage merken" (NutzerIn); angehakt: zweite Anmeldung '
        + 'ohne Code; Profil: „Gemerkte Geräte" 1',
    async fahren(k) {
      /* EIN KONTO DER ROLLE user MIT DEM GEHEIMNIS DER SANDBOX
       * (`tools/zweitfaktor/pruefkonto.php`, dieselben Funktionen wie das
       * Tor). Die Dauer der NutzerInnen steht fest auf 30 und nachher wieder
       * auf dem Stand von vorher. */
      probekontoAnlegen(ADRESSE_M, PASSWORT, 'user', 'Bedienprobe Merken');
      execFileSync('php', ['tools/zweitfaktor/pruefkonto.php', ADRESSE_M], { cwd: WURZEL });
      const vorher = php('echo (string)app_state_lesen("zf_geraet_tage_user");');
      php('app_state_setzen("zf_geraet_tage_user", "30");');
      const kontext = await eigenerKontext(k);
      try {
        const s = await kontext.newPage();
        await passwortSchicken(s, k.basis, ADRESSE_M, PASSWORT);
        await s.waitForSelector('#codeform', { timeout: 90000 });
        const text = (await s.locator('#codeform .schalter-text').first().textContent() || '')
          .replace(/\s+/g, ' ').trim();
        await s.fill('input[name="code"]', await naechsterCode(SANDBOX));
        await s.locator('#codeform label[for="sw-merken"]').click();
        /* Der Schalter gleitet; ohne das Warten zeigte das Bild ihn auf dem
         * Weg, also scheinbar aus, obwohl er an war. */
        await s.evaluate(() => Promise.all(document.getAnimations().map(a => a.finished)));
        await k.bild('zweitfaktor-code-merken', s);
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.locator('#codeform button[type="submit"]').click(),
        ]);
        const nachCode = new URL(s.url()).pathname.split('/').pop();
        await s.goto(`${k.basis}/logout.php`, { waitUntil: 'domcontentloaded' });
        await passwortSchicken(s, k.basis, ADRESSE_M, PASSWORT);
        /* NICHT AUF `login.php` WARTEN: Dort steht die Seite schon, bevor das
         * Formular abgeschickt ist (das Passwort wird im Browser erst
         * abgeleitet) — die erste Fassung las den Stand davor und meldete
         * „MIT Code". Gewartet wird auf eines der beiden Ziele. */
        await Promise.race([
          s.waitForURL(/index\.php/, { timeout: 90000 }),
          s.waitForSelector('#codeform', { timeout: 90000 }),
        ]);
        await s.waitForLoadState('domcontentloaded');
        const zweite = new URL(s.url()).pathname.split('/').pop();
        const ohneCode = zweite === 'index.php' && (await s.locator('#codeform').count()) === 0;
        await s.goto(`${k.basis}/einstellungen.php?t=profil#k-zweitfaktor`,
                     { waitUntil: 'domcontentloaded' });
        const zeile = await s.evaluate(() => {
          const z = [...document.querySelectorAll('#k-zweitfaktor .zeile')]
            .find(e => e.textContent.includes('Gemerkte Geräte'));
          return z ? z.querySelector('.plakette')?.textContent.trim() : null;
        });
        const zeileZf = s.locator('#k-zweitfaktor .zeile', { hasText: 'Gemerkte Geräte' }).first();
        if (await zeileZf.count()) { await zeileZf.scrollIntoViewIfNeeded(); }
        await k.bild('einstellungen-zweitfaktor-geraete', s);
        const ok = text.startsWith('Dieses Gerät 30 Tage merken') && nachCode === 'index.php'
                && ohneCode && zeile === '1';
        return {
          ist: `Haken „${text.slice(0, 30)}…" · nach Code ${nachCode} · zweite Anmeldung `
             + `${ohneCode ? 'ohne Code' : 'MIT Code'} · Profil ${zeile ?? '—'}`,
          ok,
          bemerkung: ok ? '' : 'Soll: 30 Tage · index.php · ohne Code · 1',
        };
      } finally {
        await kontext.close();
        probekontoRaeumen(ADRESSE_M);
        php(vorher === '' ? 'app_state_loeschen("zf_geraet_tage_user");'
                          : 'app_state_setzen("zf_geraet_tage_user", ' + JSON.stringify(vorher) + ');');
      }
    },
  },
];
