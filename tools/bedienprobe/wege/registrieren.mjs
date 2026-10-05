/* Weg der Registrierung mit Rechenaufgabe (`registrieren.php`; Schritt 18,
 * SR-08, E-SR-26, Nr. 228). Nach der SEITE benannt (E-PK-15).
 * ===========================================================================
 *
 *   registrieren   Registrierung vorübergehend offen → neun Mal die Seite
 *                  laden und die Zeit bis zur Lösung des Workers messen
 *                  (Median, Höchstwert) → ausfüllen, nach der
 *                  Mindestausfülldauer absenden → Danke-Karte, Konto
 *                  `unbestaetigt` → dasselbe mit einem Worker, der sieben
 *                  Sekunden zu spät kommt: Der Knopf ist gesperrt, die
 *                  Zustandszeile sagt „Sicherheitsprüfung läuft …", und das
 *                  Formular geht von selbst ab, sobald die Lösung da ist.
 *
 * NUR UNGEDROSSELT. Das Konzept wollte auch eine Lage mit vierfacher
 * CPU-Drosselung über die Entwicklerwerkzeuge (`setCPUThrottlingRate`). Die
 * erreicht keinen Worker — gemessen, und Chromium lehnt sie am Worker-Ziel
 * ausdrücklich ab (F-SR-95). Eine Lage, die nur die Seite drosselt, hätte
 * eine grüne Zahl ohne Gegenstand geliefert. Die gedrosselte Zahl steht im
 * Prüfdokument von Konzept SR, gemessen mit einer CPU-Quote des
 * Betriebssystems; P-SR-14 misst auf dem alten Diensthandy.
 *
 * DER VERZÖGERTE WORKER ist eine Route, die die Datei sieben Sekunden
 * zurückhält. Ohne sie wäre die Lösung bei 16 Bit fast immer da, bevor die
 * Mindestausfülldauer (4 s) abgelaufen ist — der gesperrte Knopf ließe sich
 * nicht herstellen, ohne den Weg auf die Bitzahl zu stimmen.
 *
 * DIE BETRIEBSART wird auf `offen` gestellt und im `finally` zurückgelegt;
 * Konten, Mails und die Töpfe `reg*` ebenso. Ein eigener Kontext ohne
 * Anmeldung — die Rolle des Läufers ist angemeldet, und eine Registrierung
 * hat keine Sitzung von früher.
 */

import { eigenerKontext, php, probekontoRaeumen } from '../probekonto.mjs';

const ADRESSEN = ['bedienprobe-reg-1@probe.invalid', 'bedienprobe-reg-2@probe.invalid'];
const LAEUFE = 9;
const warten = (ms) => new Promise(r => setTimeout(r, ms));

function aufraeumen(mailStart) {
  for (const a of ADRESSEN) { probekontoRaeumen(a); }
  php(`db()->prepare("DELETE FROM mail_warteschlange WHERE empfaenger IN (?, ?)
                      OR (id > ? AND schluessel IN ('registrierung', 'registrierung_bekannt'))")
         ->execute([${JSON.stringify(ADRESSEN[0])}, ${JSON.stringify(ADRESSEN[1])}, ${Number(mailStart)}]);
       db()->exec("DELETE FROM rate_limits WHERE topf IN ('reg', 'regg', 'regz')");`);
}

/** Ausfüllen: Adresse, Name, jedes Häkchen, das die Seite zeigt. */
async function ausfuellen(s, adresse) {
  await s.fill('input[name="email"]', adresse);
  await s.fill('input[name="name"]', 'Bedienprobe Registrierung');
  for (const h of await s.$$('input[type="checkbox"][name^="ew["]')) { await h.check(); }
}

export const wege = [
  {
    name: 'registrieren',
    paket: 'SR-08', punkt: 'Nr. 228', rolle: 'demo',
    soll: 'Lösung ungedrosselt im Median unter 1 s; abgeschickt → Danke, Konto unbestaetigt; '
        + 'schneller als der Worker → Knopf gesperrt, „Sicherheitsprüfung läuft …", dann von selbst ab; '
        + 'keine Konsolenfehler',
    async fahren(k) {
      const vorArt = php('echo (string)app_state_lesen("konten_reg_art");');
      const mailStart = Number(php('echo (int)db()->query("SELECT COALESCE(MAX(id), 0) FROM mail_warteschlange")->fetchColumn();'));
      aufraeumen(mailStart);
      php('app_state_setzen("konten_reg_art", "offen");');
      const kontext = await eigenerKontext(k);
      const konsole = [];
      try {
        const s = await kontext.newPage();
        s.on('console', m => { if (m.type() === 'error') { konsole.push(m.text()); } });
        s.on('pageerror', e => konsole.push(String(e)));

        /* 1. Zeit bis zur Lösung — ab dem Beginn der Navigation, also mit
           Laden der Seite und Anlauf des Workers. */
        const zeiten = [];
        for (let i = 0; i < LAEUFE; i++) {
          await s.goto(`${k.basis}/registrieren.php`, { waitUntil: 'domcontentloaded' });
          await s.waitForFunction(() => document.querySelector('input[name="pow_loesung"]')?.value !== '',
                                  null, { timeout: 30000 });
          zeiten.push(Math.round(await s.evaluate(() => performance.now())));
        }
        const sortiert = [...zeiten].sort((a, b) => a - b);
        const median = sortiert[Math.floor(LAEUFE / 2)];
        const hoechst = sortiert[LAEUFE - 1];
        const zeileLeer = (await s.textContent('[data-pow-zustand]')).trim() === '';

        /* 2. Abschicken, wie ein Mensch: nach der Mindestausfülldauer. */
        await ausfuellen(s, ADRESSEN[0]);
        await k.bild('registrieren-formular', s);
        await warten(4600);
        await Promise.all([s.waitForNavigation({ waitUntil: 'domcontentloaded' }),
                           s.click('[data-pow-knopf]')]);
        const danke1 = (await s.textContent('body')).includes('Danke.');
        const konto1 = php(`$st = db()->prepare("SELECT status FROM users WHERE email = ?");
                            $st->execute([${JSON.stringify(ADRESSEN[0])}]); echo (string)$st->fetchColumn();`);

        /* 3. Schneller als der Worker. */
        await s.route('**/assets/pow-worker.js*', async r => { await warten(7000); await r.continue(); });
        await s.goto(`${k.basis}/registrieren.php`, { waitUntil: 'domcontentloaded' });
        await ausfuellen(s, ADRESSEN[1]);
        await warten(4600);
        await s.click('[data-pow-knopf]');
        await warten(300);
        const zustand = (await s.textContent('[data-pow-zustand]')).trim();
        const gesperrt = await s.$eval('[data-pow-knopf]', b => b.disabled);
        await k.bild('registrieren-sicherheitspruefung', s);
        await s.waitForSelector('text=Danke.', { timeout: 30000 });
        await s.unroute('**/assets/pow-worker.js*');
        const konto2 = php(`$st = db()->prepare("SELECT status FROM users WHERE email = ?");
                            $st->execute([${JSON.stringify(ADRESSEN[1])}]); echo (string)$st->fetchColumn();`);

        const ok = median < 1000 && zeileLeer && danke1 && konto1 === 'unbestaetigt'
          && zustand === 'Sicherheitsprüfung läuft …' && gesperrt && konto2 === 'unbestaetigt'
          && konsole.length === 0;
        return {
          ist: `Lösung nach Median ${median} ms, höchstens ${hoechst} ms (${LAEUFE} Läufe: ${zeiten.join(', ')}); `
             + `abgeschickt → ${danke1 ? 'Danke' : 'keine Danke-Karte'}, Konto ${konto1 || 'keins'}; `
             + `zu schnell → „${zustand}", Knopf ${gesperrt ? 'gesperrt' : 'frei'}, danach Konto ${konto2 || 'keins'}; `
             + `${konsole.length} Konsolenfehler`,
          ok,
          bemerkung: konsole.length ? konsole.join(' | ').slice(0, 200) : (zeileLeer ? '' : 'Zustandszeile nicht leer'),
        };
      } finally {
        await kontext.close();
        await warten(1500);   // die Mail kommt nach der Antwort
        aufraeumen(mailStart);
        if (vorArt === '') { php('db()->exec("DELETE FROM app_state WHERE k = \'konten_reg_art\'");'); }
        else { php(`app_state_setzen("konten_reg_art", ${JSON.stringify(vorArt)});`); }
      }
    },
  },
];
