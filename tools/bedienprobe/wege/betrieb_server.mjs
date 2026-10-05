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
 *   betrieb-server-schluesselwechsel  der Wechsel des Serverschlüssels im
 *                                 Browser (Schritt 18, SR-03): ohne Haken
 *                                 abgewiesen, mit Haken gewechselt, das
 *                                 Blatt mit drei Kacheln auf EINER A4-Seite,
 *                                 „Jetzt weiterarbeiten", die Rückfrage mit
 *                                 dem Satz voran, „Alten Schlüssel
 *                                 entfernen" — und die Bilder der Karte in
 *                                 drei Lagen (P-SR-05)
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

import { execFileSync, spawnSync } from 'node:child_process';
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
  {
    name: 'betrieb-server-schluesselwechsel',
    paket: 'SR-03', punkt: 'E-SR-63', rolle: 'admin',
    soll: 'ohne Haken abgewiesen; mit Haken gewechselt (Lage Wechsel); Blatt mit drei Kacheln '
        + 'auf einer A4-Seite; alles nachgewiesen; Rückfrage mit „Ein Wert hat gewechselt" '
        + 'beantwortet; nach frischem Stand „Alten Schlüssel entfernen" → Lage bereit mit dem neuen; '
        + 'der Rückweg der Probe stellt alles wieder her',
    async fahren(k) {
      /* DIE BUCHFÜHRUNG KOMMT AUS DER SCHLÜSSELWECHSELPROBE (`--merken`,
       * `--stand`, `--zurueck`): Stand sichern und Jobs anhalten, einen
       * kleinen Komplett-Stand unter dem neuen bauen, und am Ende mit
       * demselben Job zurück auf den alten Schlüssel, config.php byte-gleich.
       * Zwei Buchführungen für denselben Vorgang wären zwei, die auseinander
       * laufen. Ein eigenes Konto der Rolle BetreiberIn, angemeldet MIT Code —
       * jeder Griff an der Karte verlangt ihn frisch (E-SR-20). */
      const ADR = 'bedienprobe-schluessel@probe.invalid';
      const PW  = 'Bedienprobe-Schluessel-Leuchtturm-5';
      const PROBE = 'tools/proben/schluesselwechsel/probe.php';
      const probe = modus => spawnSync('php', [PROBE, modus], { cwd: WURZEL, encoding: 'utf-8' });
      const zustand = () => JSON.parse(phpK('require_once "server/serverkrypto_lib.php"; '
        + 'echo json_encode(serverschluessel_zustand(true));'));
      const phase = () => JSON.parse(phpK('require_once "server/schluesselwechsel_lib.php"; '
        + 'echo json_encode(sw_zustand()["phase"] ?? null);'));
      probekontoAnlegen(ADR, PW, 'betreiberin', 'Bedienprobe Schlüssel');
      execFileSync('php', ['tools/zweitfaktor/pruefkonto.php', ADR], { cwd: WURZEL });
      const m = probe('--merken');
      if (m.status !== 0) {
        probekontoRaeumen(ADR);
        return { ist: 'Probe --merken: ' + (m.stderr || m.stdout).trim(), ok: false,
                 bemerkung: 'Vorbedingung der Schlüsselwechselprobe nicht erfüllt' };
      }
      const A = JSON.parse(m.stdout).a;
      /* Die Rückfrage soll erst NACH dem Wechsel fällig sein — sonst ginge sie
       * beim Anmelden auf, ohne den Satz, um den es geht. `--merken` hat die
       * Marke schon gesichert; der Rückweg legt sie zurück. */
      phpK('require_once "server/einstieg_lib.php"; blatt_bestaetigt();');
      const kontext = await eigenerKontext(k);
      const schritte = [];
      let zurueck = null;
      try {
        const s = await kontext.newPage();
        const warte = () => s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 60000 });
        const karte = () => s.goto(`${k.basis}/betrieb_server.php#k-schluessel`,
                                   { waitUntil: 'domcontentloaded' });
        const seitenText = () => s.locator('body').innerText();
        await passwortSchicken(s, k.basis, ADR, PW);
        await s.waitForSelector('#codeform', { timeout: 90000 });
        await s.fill('input[name="code"]', await naechsterCode(SANDBOX));
        await Promise.all([warte(), s.locator('#codeform button[type="submit"]').click()]);

        /* 1. Lage „bereit": der Abschnitt „Serverschlüssel wechseln". Auf die
         *    Netzruhe gewartet — das erste Bild nach der Anmeldung zeigte sonst
         *    Knöpfe und Menü ohne Symbole (der Sprite kam später). */
        await karte();
        await s.waitForLoadState('networkidle').catch(() => {});
        await k.bild('betrieb-server-schluessel-bereit', s);
        const wechseln = s.locator('form:has(input[name="action"][value="schluessel_sk_wechseln"]) '
                                 + 'button[type="submit"]');
        await wechseln.click();
        await s.waitForSelector('dialog[open]', { timeout: 5000 });
        await Promise.all([warte(), s.locator('dialog[open] [data-act="yes"]').click()]);
        const ohneHaken = (await seitenText()).includes('Bitte zuerst bestätigen')
                       && zustand().stand === 'bereit';
        schritte.push('ohne Haken ' + (ohneHaken ? 'abgewiesen' : 'NICHT abgewiesen'));

        /* 2. Mit Haken: gewechselt, Lage „Wechsel". */
        await karte();
        await s.locator('label[for="sw-kopien_verstanden"]').click();
        await wechseln.click();
        await s.waitForSelector('dialog[open]', { timeout: 5000 });
        await Promise.all([warte(), s.locator('dialog[open] [data-act="yes"]').click()]);
        const z1 = zustand();
        const nachWechsel = await seitenText();
        const gewechselt = z1.stand === 'rotation' && z1.kennung_alt === A
                        && nachWechsel.includes('Der Serverschlüssel ist gewechselt (neu ' + z1.kennung);
        schritte.push('mit Haken ' + (gewechselt ? `gewechselt ${A} → ${z1.kennung}` : `NICHT (${z1.stand})`));
        await karte();
        const karte1 = await s.locator('#k-schluessel').innerText();
        const lageWechsel = karte1.includes('Wechsel läuft: neu ' + z1.kennung)
                         && karte1.includes('Bevor der bisherige gehen darf')
                         && !karte1.includes('Alten Schlüssel entfernen');
        schritte.push('Karte ' + (lageWechsel ? 'Lage Wechsel' : 'NICHT in der Lage Wechsel'));
        await k.bild('betrieb-server-schluessel-wechsel', s);

        /* 3. Das Blatt: drei Kacheln — zwei ohne Server-Anteil (H-SR-06,
         *    F-SR-82) —, EINE A4-Seite (E-SR-60: vier passten nicht; gemessen
         *    P5c/AP9 mit drei 1013 von 1017 px — und die Zeile „Wann neu" ist
         *    in der Lage Wechsel länger). */
        await s.goto(`${k.basis}/betrieb_schluesselblatt.php`, { waitUntil: 'domcontentloaded' });
        const kacheln = await s.locator('.blatt-kachel-name').allInnerTexts();
        const sollKacheln = Number(phpK('require_once "server/serverkrypto_lib.php"; '
          + 'echo anteil_zustand(true)["kennung"] !== null ? 3 : 2;'));
        await k.bild('schluesselblatt-wechsel', s);
        const pdf = await s.pdf({ format: 'A4', printBackground: true, preferCSSPageSize: true });
        const seiten = (pdf.toString('latin1').match(/\/Type\s*\/Page(?![s\w])/g) || []).length;
        schritte.push(`Blatt ${kacheln.length} Kacheln, ${seiten} Seite(n)`);

        /* 4. „Jetzt weiterarbeiten", bis alles nachgewiesen ist. Auf der
         *    örtlichen Anlage schafft der Wechsel selbst alles (ein Häppchen
         *    mit acht Sekunden), und der Knopf stünde nie da. Deshalb beginnt
         *    der Durchgang hier von vorn — dieselbe Lage wie nach einem
         *    abgebrochenen Lauf, und jedes Stück ist dann schon „neu". */
        /* Dazu die Frist vor dem Nachweis (zehn Minuten, E-SR-73) gekürzt —
         * wie die Probe: Die Jobs stehen still, außer dem Bedienweg
         * versiegelt niemand. */
        phpK('require_once "server/schluesselwechsel_lib.php"; $z = sw_zustand(); '
           + 'sw_zustand_setzen(array_merge($z, ["phase" => "umhuellen", "zweck" => SW_ZWECKE[0], '
           + '"cursor" => null, "erledigt" => 0, "zahlen" => [], "nachweis_alt" => 0, '
           + '"nachweis_fehler" => 0, "nachweis_begonnen" => null, '
           + '"nachweis_ab" => gmdate("Y-m-d H:i:s", time() - 1)]));');
        let klicks = 0;
        for (let i = 0; i < 20 && phase() !== 'fertig'; i++) {
          await karte();
          const weiter = s.locator('form:has(input[name="action"][value="schluessel_sk_weiter"]) '
                                 + 'button[type="submit"]');
          if (await weiter.count() === 0) { break; }
          await Promise.all([warte(), weiter.click()]);
          klicks++;
        }
        const fertig = phase() === 'fertig' && klicks > 0;
        schritte.push(`nachgewiesen ${fertig ? 'ja' : 'NEIN'} (${klicks}× weiterarbeiten)`);

        /* 5. Die Rückfrage auf der Startseite: mit dem Satz voran, gefragt
         *    nach den HEUTIGEN Werten — beantwortet wie vom Blatt. */
        await s.goto(`${k.basis}/index.php`, { waitUntil: 'domcontentloaded' });
        await s.waitForSelector('#dlg-blatt[open] #blatt-g3', { timeout: 15000 });
        const dlg = await s.locator('#dlg-blatt').innerText();
        const satz = dlg.includes('Ein Wert hat gewechselt') && dlg.includes('Kennung ' + A);
        await k.bild('blatt-rueckfrage-wechsel', s);
        const werte = JSON.parse(phpK('echo json_encode(["Serverschlüssel" => strtolower((string)konfig("server_key", "")), '
          + '"Server-Anteil" => strtolower((string)konfig("kdf_anteil", ""))]);'));
        for (let i = 0; i < 4; i++) {
          const lab = (await s.locator(`label[for="blatt-g${i}"]`).innerText()).trim();
          const t = lab.match(/^(.*) · Gruppe (\d+)$/);
          const hex = t ? (werte[t[1]] || '') : '';
          await s.fill(`#blatt-g${i}`, t ? hex.substr((Number(t[2]) - 1) * 4, 4) : '');
        }
        await s.locator('[data-blatt-pruefen]').click();
        await s.waitForFunction(() => !document.querySelector('#dlg-blatt')?.open, null, { timeout: 15000 })
          .catch(() => {});
        const beantwortet = phpK('echo (string)app_state_lesen("schluesselblatt_bestaetigt_am");') !== '';
        schritte.push(`Rückfrage ${satz ? 'mit Satz' : 'OHNE Satz'}, ${beantwortet ? 'beantwortet' : 'NICHT beantwortet'}`);

        /* 6. Ein frischer Stand unter dem neuen, dann „Alten Schlüssel
         *    entfernen" — Lage „bereit" mit dem neuen. */
        const st = probe('--stand');
        await karte();
        const karte2 = await s.locator('#k-schluessel').innerText();
        const entfernenDa = karte2.includes('Alten Schlüssel entfernen');
        await k.bild('betrieb-server-schluessel-abschluss', s);
        if (entfernenDa) {
          await s.locator('form:has(input[name="action"][value="schluessel_sk_alt_entfernen"]) '
                        + 'button[type="submit"]').click();
          await s.waitForSelector('dialog[open]', { timeout: 5000 });
          await Promise.all([warte(), s.locator('dialog[open] [data-act="yes"]').click()]);
        }
        const z2 = zustand();
        const entfernt = z2.stand === 'bereit' && z2.kennung === z1.kennung && z2.kennung_alt === null;
        schritte.push(`Stand ${st.status === 0 ? 'gebaut' : 'NICHT gebaut'} · entfernen `
                    + `${entfernenDa ? 'angeboten' : 'NICHT angeboten'} · danach ${z2.stand} ${z2.kennung}`);

        /* 7. Der Rückweg: mit demselben Job zurück auf A. */
        zurueck = probe('--zurueck');
        const zurueckOk = zurueck.status === 0 && zustand().kennung === A;
        schritte.push('Rückweg ' + (zurueckOk ? `auf ${A}` : 'NICHT sauber: ' + (zurueck.stdout || zurueck.stderr).trim()));

        const ok = ohneHaken && gewechselt && lageWechsel && kacheln.length === sollKacheln && seiten === 1
                && fertig && satz && beantwortet && st.status === 0 && entfernenDa && entfernt && zurueckOk;
        return { ist: schritte.join(' · '), ok,
                 bemerkung: ok ? '' : `Soll: abgewiesen · gewechselt · Lage Wechsel · ${sollKacheln} Kacheln auf 1 Seite · `
                                   + 'nachgewiesen · Rückfrage mit Satz beantwortet · entfernt · Rückweg sauber' };
      } finally {
        await kontext.close();
        if (zurueck === null) { probe('--zurueck'); }
        probekontoRaeumen(ADR);
      }
    },
  },
];
