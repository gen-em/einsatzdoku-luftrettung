/* Weg der Passkeys (Einstellungen → Profil → Zweitfaktor; Schritt 18, SR-09).
 * ===========================================================================
 *
 * Entstanden mit SR-09 (E-SR-29 bis -32, Nr. 350). Nach der SEITE benannt
 * (E-PK-15), wie `einstellungen_profil_rueckweg.mjs` daneben.
 *
 *   einstellungen-profil-passkey   Code bestätigen → Passkey hinzufügen mit
 *                                  Bezeichnung → die Karte zeigt ihn →
 *                                  abmelden → anmelden → „Mit Passkey
 *                                  bestätigen" mit „Gerät merken" →
 *                                  angemeldet, das Gerät zählt, „Hinzufügen"
 *                                  steht ohne neuen Code da (frisch) →
 *                                  entfernen → über das gemerkte Gerät ohne
 *                                  Code angemeldet: „Hinzufügen" ist ein
 *                                  Verweis → ein neuer Browser fragt wieder
 *                                  nach dem Code, ohne Passkey-Knopf
 *
 * NUR CHROMIUM. Den Authenticator stellt das DevTools-Protokoll
 * (`WebAuthn.enable`, `WebAuthn.addVirtualAuthenticator`: ctap2, eingebaut,
 * mit Nutzerprüfung) — Firefox und WebKit haben unter Playwright keinen. Dort
 * meldet der Weg „nicht gemessen", und das ist VERFEHLT, nicht grün
 * (`probe.mjs`: ein Weg, der nicht gefahren werden konnte, gilt als
 * verfehlt).
 *
 * DIE ADRESSE WIRD GESTELLT. Für eine IP-Adresse gibt es keine Passkeys
 * (E-SR-42), und die Sandbox steht auf 127.0.0.1. Der Weg setzt
 * `app.base_url` auf dieselbe Anlage unter `localhost` und fährt seinen
 * eigenen Kontext dort; im `finally` legt er `config.php` byte-gleich zurück
 * (daneben liegt so lange `config.php.vor-probe`, wie bei
 * `tools/sandbox/konfig_stellen.php`). Nach beiden Schritten wartet er
 * 3,2 Sekunden — OPcache sieht die Datei erst nach `revalidate_freq` und
 * rechnet in ganzen Sekunden; 2,2 s reichten nicht immer (F-SR-31).
 *
 * EIN KONTO DER ROLLE user MIT DEM GEHEIMNIS DER SANDBOX
 * (`tools/zweitfaktor/pruefkonto.php`) — wie `zweitfaktor-merken`.
 */

import { readFileSync, writeFileSync, unlinkSync } from 'node:fs';
import { join } from 'node:path';
import { execFileSync } from 'node:child_process';
import { naechsterCode, SANDBOX } from '../../zweitfaktor/totp.mjs';
import { probekontoAnlegen, probekontoRaeumen, eigenerKontext, passwortSchicken, php, WURZEL }
  from '../probekonto.mjs';

const ADRESSE = 'bedienprobe-passkey@probe.invalid';
const PASSWORT = 'Bedienprobe-Passkey-Kiesel-Mond-4';
const KONFIG = join(WURZEL, 'server', 'config.php');

const warten = (ms) => new Promise(r => setTimeout(r, ms));

/** `app.base_url` stellen — über dieselben Helfer wie die PHP-Proben. */
function basisStellen(url) {
  php(`require_once "tools/sandbox/konfig_stellen.php"; $p = konfig_stellen_pfad();
       $n = konfig_stellen_lesen($p); $n["app"]["base_url"] = ${JSON.stringify(url)};
       konfig_stellen_schreiben($p, $n);`);
}

/** Der Weg über die Anmeldung bis zur Seite danach; wartet auf eines der Ziele. */
async function anmelden(s, basis) {
  await passwortSchicken(s, basis, ADRESSE, PASSWORT);
  await Promise.race([
    s.waitForURL(/index\.php/, { timeout: 90000 }),
    s.waitForSelector('#codeform', { timeout: 90000 }),
  ]);
  await s.waitForLoadState('domcontentloaded');
}

export const wege = [
  {
    name: 'einstellungen-profil-passkey',
    paket: 'SR-09', punkt: 'E-SR-32', rolle: 'demo',
    soll: 'hinzufügen mit Bezeichnung → Karte 1 von 10; Anmeldung „Mit Passkey bestätigen" '
        + '→ index.php, Gerät gemerkt, Hinzufügen ohne neuen Code; entfernt → 0 von 10; '
        + 'über das Gerät: Verweis; neuer Browser: Code, kein Knopf',
    async fahren(k) {
      const motor = k.seite.context().browser().browserType().name();
      if (motor !== 'chromium') {
        return { ist: `nicht gemessen — ${motor} hat keinen virtuellen Authenticator`, ok: false,
                 bemerkung: 'Der Weg braucht Chromium (CDP WebAuthn); siehe Kopf der Wegdatei' };
      }
      const basisL = k.basis.replace('//127.0.0.1', '//localhost');
      const vorher = readFileSync(KONFIG);
      writeFileSync(KONFIG + '.vor-probe', vorher);
      probekontoAnlegen(ADRESSE, PASSWORT, 'user', 'Bedienprobe Passkey');
      execFileSync('php', ['tools/zweitfaktor/pruefkonto.php', ADRESSE], { cwd: WURZEL });
      /* Die Dauer der NutzerInnen fest auf 30 wie in `zweitfaktor-merken` —
         bei 0 gäbe es den Haken nicht. Nachher wieder wie vorher. */
      const tageVorher = php('echo (string)app_state_lesen("zf_geraet_tage_user");');
      php('app_state_setzen("zf_geraet_tage_user", "30");');
      basisStellen(basisL);
      await warten(3200);
      const kontext = await eigenerKontext(k);
      let zweiter = null;
      try {
        const s = await kontext.newPage();
        const cdp = await kontext.newCDPSession(s);
        await cdp.send('WebAuthn.enable');
        await cdp.send('WebAuthn.addVirtualAuthenticator', { options: {
          protocol: 'ctap2', transport: 'internal', hasResidentKey: true,
          hasUserVerification: true, isUserVerified: true, automaticPresenceSimulation: true } });

        /* 1. Mit Code angemeldet (frisch), Passkey hinzufügen */
        await anmelden(s, basisL);
        const knopfVorher = await s.locator('[data-passkey-bestaetigen]').count();
        await s.fill('input[name="code"]', await naechsterCode(SANDBOX));
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
          s.locator('#codeform button[type="submit"]').click(),
        ]);
        await s.goto(`${basisL}/einstellungen.php?t=profil#k-zweitfaktor`, { waitUntil: 'domcontentloaded' });
        await s.waitForSelector('[data-passkey-anlegen]:not([hidden])', { timeout: 10000 });
        await s.fill('input[name="pk_bezeichnung"]', 'Bedienprobe');
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
          s.locator('[data-passkey-anlegen] [data-passkey-knopf]').click(),
        ]);
        const karte = async () => s.evaluate(() => {
          const zeilen = [...document.querySelectorAll('#k-zweitfaktor .zeile')];
          const haupt = e => e.querySelector('.zeile-haupt')?.textContent.trim();
          const kopf = zeilen.find(e => haupt(e) === 'Passkeys');
          return {
            plakette: kopf ? kopf.querySelector('.plakette')?.textContent.trim() : null,
            probe: zeilen.some(e => haupt(e) === 'Bedienprobe' && e.textContent.includes('Entfernen')),
            anlegen: !!document.querySelector('[data-passkey-anlegen]'),
            verweis: [...document.querySelectorAll('#k-zweitfaktor a')]
              .some(a => a.textContent.includes('Zuerst Code bestätigen')),
          };
        });
        const nachAnlegen = await karte();
        const zeileP = s.locator('#k-zweitfaktor .zeile', { hasText: 'Bedienprobe' }).first();
        if (await zeileP.count()) { await zeileP.scrollIntoViewIfNeeded(); }
        await k.bild('einstellungen-zweitfaktor-passkeys', s);

        /* 2. Abmelden, mit Passkey anmelden, Gerät merken */
        await s.goto(`${basisL}/logout.php`, { waitUntil: 'domcontentloaded' });
        await anmelden(s, basisL);
        await s.waitForSelector('[data-passkey-bestaetigen]:not([hidden])', { timeout: 10000 });
        await s.locator('#codeform label[for="sw-merken"]').click();
        await s.evaluate(() => Promise.all(document.getAnimations().map(a => a.finished)));
        await k.bild('login-passkey', s);
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
          s.locator('[data-passkey-bestaetigen] [data-passkey-knopf]').click(),
        ]);
        const nachPasskey = new URL(s.url()).pathname.split('/').pop();
        const geraet = (await kontext.cookies()).some(c => c.name === 'EDGERAET');
        await s.goto(`${basisL}/einstellungen.php?t=profil#k-zweitfaktor`, { waitUntil: 'domcontentloaded' });
        const frisch = await karte();

        /* 3. Entfernen (frisch, also ohne Umweg) */
        await s.locator('#k-zweitfaktor .zeile', { hasText: 'Bedienprobe' })
          .getByRole('button', { name: 'Entfernen', exact: true }).click();
        await s.waitForSelector('dialog[open]', { timeout: 4000 });
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
          s.locator('dialog[open] [data-act="yes"]').click(),
        ]);
        const nachEntfernen = await karte();

        /* 4. Über das gemerkte Gerät: kein Code, also nicht frisch → Verweis */
        await s.goto(`${basisL}/logout.php`, { waitUntil: 'domcontentloaded' });
        await anmelden(s, basisL);
        const ueberGeraet = new URL(s.url()).pathname.split('/').pop();
        await s.goto(`${basisL}/einstellungen.php?t=profil#k-zweitfaktor`, { waitUntil: 'domcontentloaded' });
        const unfrisch = await karte();

        /* 5. Ein neuer Browser: Code-Schritt, und kein Passkey-Knopf mehr */
        zweiter = await eigenerKontext(k);
        const s2 = await zweiter.newPage();
        await anmelden(s2, basisL);
        const code2 = await s2.locator('#codeform').count();
        const knopf2 = await s2.locator('[data-passkey-bestaetigen]').count();

        const ok = knopfVorher === 0
                && nachAnlegen.plakette === '1 von 10' && nachAnlegen.probe
                && nachPasskey === 'index.php' && geraet && frisch.anlegen && !frisch.verweis
                && nachEntfernen.plakette === '0 von 10' && !nachEntfernen.probe
                && ueberGeraet === 'index.php' && !unfrisch.anlegen && unfrisch.verweis
                && code2 === 1 && knopf2 === 0;
        return {
          ist: `vorher Knopf ${knopfVorher} · Karte ${nachAnlegen.plakette ?? '—'}`
             + `${nachAnlegen.probe ? ' mit Zeile' : ' OHNE Zeile'} · Passkey → ${nachPasskey}`
             + ` · Gerät ${geraet ? 'gemerkt' : 'NICHT gemerkt'} · danach ${frisch.anlegen ? 'Hinzufügen' : 'kein Hinzufügen'}`
             + ` · entfernt ${nachEntfernen.plakette ?? '—'} · über Gerät ${ueberGeraet}, `
             + `${unfrisch.verweis ? 'Verweis' : 'KEIN Verweis'} · neuer Browser: Code ${code2}, Knopf ${knopf2}`,
          ok,
          bemerkung: ok ? '' : 'Soll: 0 · 1 von 10 mit Zeile · index.php · gemerkt · Hinzufügen · '
                             + '0 von 10 · index.php, Verweis · Code 1, Knopf 0',
        };
      } finally {
        await kontext.close();
        if (zweiter) { await zweiter.close(); }
        writeFileSync(KONFIG, vorher);
        try { unlinkSync(KONFIG + '.vor-probe'); } catch { /* schon fort */ }
        await warten(3200);
        probekontoRaeumen(ADRESSE);
        php(tageVorher === '' ? 'app_state_loeschen("zf_geraet_tage_user");'
                              : 'app_state_setzen("zf_geraet_tage_user", ' + JSON.stringify(tageVorher) + ');');
      }
    },
  },
];
