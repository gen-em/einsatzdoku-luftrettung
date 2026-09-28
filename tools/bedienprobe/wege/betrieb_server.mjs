/* Wege der Seite Betrieb → Servereinstellungen — Karte „Ankündigung".
 * ===========================================================================
 *
 * Entstanden mit P5c/AP1 (E-P5c-13, -55, -60). Der erste Weg, der nach der
 * SEITE heisst und nicht nach dem Paket (E-PK-15): Wer `betrieb_server.php`
 * aendert, findet hier, was auf ihr bedient wird.
 *
 *   betrieb-server-ankuendigung   setzen → erscheint → wegklicken → auf einer
 *                                 anderen Seite weg → nach Neuanmeldung
 *                                 wieder da → entfernen
 *   betrieb-server-rundmail       die Rückfrage nennt die Zahl der
 *                                 erreichbaren Konten; Abbrechen sendet nichts
 *   betrieb-server-neuladen       Karte „Anmeldung" (Schritt 18, SR-02):
 *                                 speichern leitet in die Karte um, die
 *                                 Meldung steht dort einmal, Neuladen
 *                                 schickt nichts noch einmal (Nr. 250,
 *                                 `neuladen.mjs` wie die zehn aus R4-11)
 *   betrieb-server-frischer-code  über ein gemerktes Gerät angemeldet (kein
 *                                 Code): „Schlüsselblatt drucken" führt auf
 *                                 die Bestätigung, nach dem Code steht das
 *                                 Blatt da (Schritt 18, SR-07, E-SR-20;
 *                                 P-SR-13 als Maschinenweg)
 *
 * DIE RUNDMAIL SELBST GEHT HIER NICHT HINAUS. Ob N Konten N Zeilen bekommen,
 * die Gegenstelle N annimmt und eine zweite am selben Tag abgewiesen wird,
 * misst die Mailprobe gegen einen SMTP-Nachbau (`proben.sh mail`). Hier geht
 * es um das, was nur ein Browser zeigt: dass die Rückfrage kommt, dass sie
 * die richtige Zahl nennt, und dass „Abbrechen" wirklich nichts tut.
 *
 * GEMESSEN WIRD AM DOM UND AN DER DATENBANK. Jeder Weg stellt seinen Stand
 * selbst her und räumt ihn im `finally` ab — eine liegengebliebene
 * Ankündigung stünde sonst über jeder Seite jedes folgenden Weges.
 */

import { execFileSync } from 'node:child_process';
import { neuladenPruefen } from '../neuladen.mjs';
import { probekontoAnlegen, probekontoRaeumen, eigenerKontext, passwortSchicken, php as phpK, WURZEL }
  from '../probekonto.mjs';
import { naechsterCode, SANDBOX } from '../../zweitfaktor/totp.mjs';

const SEITE  = k => `${k.basis}/betrieb_server.php`;
const TEXT   = 'Bedienprobe: Wartung am Dienstag, 20:00 bis 21:00. Danach geht alles weiter.';
const STREIFEN = '.hinweise .meldung-ankuendigung';

/** Ein kurzes PHP-Stück gegen die örtliche Installation fahren. */
function php(code) {
  return execFileSync('php', ['-r',
    'require "server/db.php"; require "server/ankuendigung_lib.php"; ' + code],
    { cwd: process.env.ED_WURZEL || '/home/user/einsatzdoku-luftrettung',
      encoding: 'utf-8' }).trim();
}

const aufraeumen = () => php('ankuendigung_entfernen();');
const gespeichert = () => php('$a = ankuendigung(); echo $a === null ? "" : $a["text"];');
/** Morgen in der Zeitzone der Anlage — die Karte verlangt ein Ende in der Zukunft. */
const morgen = () => php('echo (new DateTimeImmutable("tomorrow", '
  + 'new DateTimeZone((string)konfig("app.timezone"))))->format("Y-m-d");');
const protokollRundmail = () => Number(php('echo db()->query("SELECT COUNT(*) FROM '
  + 'protokoll_ereignisse WHERE art = \'rundmail\'")->fetchColumn();'));

/** Steht der Streifen auf der Seite, die gerade offen ist? */
const streifenDa = k => k.seite.evaluate((sel) => {
  const m = document.querySelector(sel);
  return m ? m.textContent.replace(/\s+/g, ' ').trim() : null;
}, STREIFEN);

/** Abschicken und auf die neue Seite warten. */
async function absenden(k, auswahl) {
  await Promise.all([
    k.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 }),
    k.seite.locator(auswahl).first().click(),
  ]);
}

export const wege = [
  {
    name: 'betrieb-server-ankuendigung',
    paket: 'P5c-AP1', punkt: 'E-P5c-13', rolle: 'admin',
    soll: 'Gesetzt erscheint der Streifen; weggeklickt ist er auch auf der '
        + 'nächsten Seite fort; nach Neuanmeldung wieder da; entfernt fort',
    async fahren(k) {
      aufraeumen();
      const schritte = [];
      try {
        await k.gehZu(SEITE(k));
        await k.seite.fill('#f-ank_text', TEXT);
        await k.seite.selectOption('#f-ank_ton', 'warn');
        await k.seite.fill('#f-ank_bis', morgen());
        await k.seite.fill('#f-ank_bis_zeit', '21:00');
        await absenden(k, '#k-ankuendigung button[name="action"][value="ankuendigung"]');
        const nachSpeichern = await streifenDa(k);
        schritte.push('gesetzt ' + (nachSpeichern ? 'sichtbar' : 'NICHT sichtbar'));
        await k.bild('betrieb-server-ankuendigung-gesetzt');

        /* DAS KREUZ. Auf dieser Seite gibt es kein `CSRF` im Skript — das
         * Formular schickt selbst ab und kommt zurueck. Beide Wege enden
         * gleich: Streifen fort. */
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 20000 })
            .catch(() => {}),
          k.seite.locator(`${STREIFEN} form[data-ankuendigung-weg] button`).first().click(),
        ]);
        const nachKreuz = await streifenDa(k);
        await k.gehZu(`${k.basis}/betrieb_status.php`);
        const andereSeite = await streifenDa(k);
        schritte.push('weggeklickt ' + (nachKreuz || andereSeite ? 'NOCH DA' : 'fort, auch auf Status'));

        await k.neuAnmelden();
        await k.gehZu(`${k.basis}/betrieb_status.php`);
        const nachNeu = await streifenDa(k);
        schritte.push('neu angemeldet ' + (nachNeu ? 'wieder da' : 'NICHT da'));
        await k.bild('betrieb-server-ankuendigung-nach-neuanmeldung');

        await k.gehZu(SEITE(k));
        await absenden(k, '#k-ankuendigung button[name="action"][value="ankuendigung_weg"]');
        const nachEntfernen = await streifenDa(k);
        const db = gespeichert();
        schritte.push('entfernt ' + (nachEntfernen || db ? 'NOCH DA' : 'fort'));

        const ok = !!nachSpeichern && nachSpeichern.includes('Wartung am Dienstag')
                && !nachKreuz && !andereSeite && !!nachNeu
                && !nachEntfernen && db === '';
        return { ist: schritte.join(' · '), ok,
                 bemerkung: ok ? '' : 'Soll: sichtbar · fort · wieder da · fort' };
      } finally {
        aufraeumen();
      }
    },
  },
  {
    name: 'betrieb-server-rundmail',
    paket: 'P5c-AP1', punkt: 'E-P5c-13', rolle: 'admin',
    soll: 'Die Rückfrage nennt die Zahl der erreichbaren Konten; Abbrechen '
        + 'sendet nichts und schreibt nichts ins Protokoll',
    async fahren(k) {
      aufraeumen();
      php('ankuendigung_setzen(' + JSON.stringify(TEXT) + ', "info", time() + 3600);');
      const zahl = Number(php('echo count(rundmail_empfaenger());'));
      const vorher = protokollRundmail();
      try {
        await k.gehZu(SEITE(k));
        const knopf = k.seite.locator('#k-ankuendigung button[value="rundmail"]');
        if (await knopf.isDisabled()) {
          return { ist: 'Knopf gesperrt', ok: false,
                   bemerkung: 'Heute ging schon eine Rundmail hinaus — die Probe '
                            + 'braucht einen Tag ohne (Mailprobe am selben Tag?)' };
        }
        await knopf.click();
        await k.seite.waitForSelector('dialog[open]', { timeout: 3000 }).catch(() => {});
        const dialog = await k.seite.evaluate(() => {
          const d = document.querySelector('dialog[open]');
          return d ? d.textContent.replace(/\s+/g, ' ').trim() : null;
        });
        await k.bild('betrieb-server-rundmail-rueckfrage');
        if (dialog) {
          await k.seite.locator('dialog[open] [data-act="no"]').click();
          await k.seite.waitForTimeout(300);
        }
        const nachher = protokollRundmail();
        const ok = !!dialog && dialog.includes(`${zahl} erreichbare Konten`)
                && dialog.includes(`An ${zahl} Konten senden`) && nachher === vorher;
        return {
          ist: (dialog ? `Rückfrage „…${zahl} erreichbare Konten…"` : 'keine Rückfrage')
             + ` · Protokoll ${vorher} → ${nachher}`,
          ok,
          bemerkung: ok ? '' : `Soll: Rückfrage mit ${zahl}, Protokoll unverändert`,
        };
      } finally {
        aufraeumen();
      }
    },
  },
  {
    name: 'betrieb-server-neuladen',
    paket: 'SR-02', punkt: 'Nr. 250', rolle: 'admin',
    soll: 'Karte „Anmeldung" speichern: POST → umgeleitet auf #k-anmeldung, Meldung '
        + 'einmal, Neuladen GET, kein zweiter Protokolleintrag',
    async fahren(k) {
      /* DIE LETZTE BETRIEBSSEITE MIT UMLEITUNG (Schritt 18, SR-02): bis Web
       * 21.7.0 gab sie ihr Ergebnis selbst aus. Gespeichert wird ein Wert,
       * der sicher anders ist als der jetzige — sonst hieße die Meldung
       * „Es gab nichts zu ändern", und ins Protokoll käme nichts. */
      const vorher = php('echo (string)app_state_lesen("zf_geraet_tage_verwaltung");');
      const neu = vorher === '14' ? '30' : '14';
      try {
        return await neuladenPruefen(k, {
          name: 'betrieb-server-anmeldung', seite: SEITE(k),
          feld: '#k-anmeldung input[name="action"][value="anmeldung"]',
          vorher: async (kk) => { await kk.seite.selectOption('#f-zf_geraet_tage_verwaltung', neu); },
          meldung: 'Gerät merken:', ziel: '#k-anmeldung',
          zaehler: () => Number(php('echo db()->query("SELECT COUNT(*) FROM protokoll_ereignisse '
                                  + 'WHERE art = \'einstellungen_anmeldung\'")->fetchColumn();')),
        });
      } finally {
        php(vorher === '' ? 'app_state_loeschen("zf_geraet_tage_verwaltung");'
                          : 'app_state_setzen("zf_geraet_tage_verwaltung", ' + JSON.stringify(vorher) + ');');
      }
    },
  },
  {
    name: 'betrieb-server-frischer-code',
    paket: 'SR-07', punkt: 'E-SR-20', rolle: 'admin',
    soll: 'über ein gemerktes Gerät angemeldet: „Schlüsselblatt drucken" → Bestätigung; '
        + 'Code → das Blatt; „Abbrechen" führt ohne Code zurück',
    async fahren(k) {
      /* EIN EIGENES KONTO DER ROLLE BETREIBERIN mit dem Geheimnis der
       * Sandbox — nicht das Prüfkonto des Läufers: Dessen Sitzung ist mit
       * Code entstanden und bliebe frisch, und ein Abmelden hier nähme sie
       * ihm. Die Dauer der Verwaltung steht auf 7 und danach wie vorher. */
      const ADR = 'bedienprobe-frisch@probe.invalid';
      const PW  = 'Bedienprobe-Frisch-Leuchtturm-4';
      probekontoAnlegen(ADR, PW, 'betreiberin', 'Bedienprobe Frisch');
      execFileSync('php', ['tools/zweitfaktor/pruefkonto.php', ADR], { cwd: WURZEL });
      const vorher = phpK('echo (string)app_state_lesen("zf_geraet_tage_verwaltung");');
      phpK('app_state_setzen("zf_geraet_tage_verwaltung", "7");');
      const kontext = await eigenerKontext(k);
      try {
        const s = await kontext.newPage();
        await passwortSchicken(s, k.basis, ADR, PW);
        await s.waitForSelector('#codeform', { timeout: 90000 });
        await s.fill('input[name="code"]', await naechsterCode(SANDBOX));
        await s.locator('#codeform label[for="sw-merken"]').click();
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.locator('#codeform button[type="submit"]').click()]);
        await s.goto(`${k.basis}/logout.php`, { waitUntil: 'domcontentloaded' });
        await passwortSchicken(s, k.basis, ADR, PW);
        await Promise.race([s.waitForURL(/index\.php/, { timeout: 90000 }),
                            s.waitForSelector('#codeform', { timeout: 90000 })]);
        const ohneCode = (await s.locator('#codeform').count()) === 0;

        /* Abbrechen: in die Karte zurück, ohne dass das Blatt aufgeht — nicht
         * auf das Blatt selbst, das wieder hierher schickte. */
        await s.goto(`${k.basis}/betrieb_server.php#k-schluessel`, { waitUntil: 'domcontentloaded' });
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.locator('#k-schluessel a[href="betrieb_schluesselblatt.php"]').first().click()]);
        const umweg = /\/zweitfaktor\.php\?bestaetigen=1/.test(s.url());
        await k.bild('zweitfaktor-bestaetigen', s);
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.locator('a', { hasText: 'Abbrechen' }).first().click()]);
        const nachAbbruch = new URL(s.url()).pathname.split('/').pop();

        /* Noch einmal, jetzt mit Code. */
        await s.goto(`${k.basis}/betrieb_server.php#k-schluessel`, { waitUntil: 'domcontentloaded' });
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.locator('#k-schluessel a[href="betrieb_schluesselblatt.php"]').first().click()]);
        await s.fill('#codeform input[name="code"]', await naechsterCode(SANDBOX));
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.locator('#codeform button[type="submit"]').click()]);
        const blatt = new URL(s.url()).pathname.split('/').pop();
        const ok = ohneCode && umweg && nachAbbruch === 'betrieb_server.php'
                && blatt === 'betrieb_schluesselblatt.php';
        return {
          ist: `zweite Anmeldung ${ohneCode ? 'ohne' : 'MIT'} Code · Blatt → `
             + `${umweg ? 'Bestätigung' : 'KEIN Umweg'} · Abbrechen → ${nachAbbruch} · mit Code → ${blatt}`,
          ok,
          bemerkung: ok ? '' : 'Soll: ohne Code · Bestätigung · Abbrechen → betrieb_server.php · '
                             + 'mit Code → betrieb_schluesselblatt.php',
        };
      } finally {
        await kontext.close();
        probekontoRaeumen(ADR);
        phpK(vorher === '' ? 'app_state_loeschen("zf_geraet_tage_verwaltung");'
                           : 'app_state_setzen("zf_geraet_tage_verwaltung", ' + JSON.stringify(vorher) + ');');
      }
    },
  },
];
