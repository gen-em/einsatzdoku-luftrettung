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
];
