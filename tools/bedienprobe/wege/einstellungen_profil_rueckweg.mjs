/* Weg der Seite Einstellungen → Profil: das Paar des Rückwegs
 * (`einstellungen.php?t=profil`, Karte „Zweitfaktor").
 * ===========================================================================
 *
 * Entstanden mit Konzept RW, RW-02 (E-RW-02, -06, -07, -14, -15). Nach der
 * SEITE benannt (E-PK-15); die Anmeldung, an der das Paar entsteht, gehört
 * zum Weg, weil die Karte sonst nichts zu zeigen hätte.
 *
 *   einstellungen-profil-rueckweg
 *                  Ein frisches Konto (Passwort im Browser gesetzt, mit
 *                  Schlüsselhülle) meldet sich an: Das Paar entsteht still,
 *                  ein Protokolleintrag, keine Mail. Die zweite Anmeldung
 *                  lässt es stehen. Der Endpunkt weist ohne Token, mit
 *                  falschem Token, ohne `ersetzen` bei vorhandenem Paar und
 *                  mit einer fremden Kurve ab; ein Fehlversuch zählt im Topf
 *                  `login`, eine Sperre dort hält ihn auf. Mit
 *                  eingeschaltetem Zweitfaktor zeigt die Karte die Zeile, und
 *                  „Rückweg erneuern" ersetzt mit dem Passwort: Protokoll
 *                  und Mail je eins. Das Demo-Konto bekommt keins.
 *
 * WARUM EIN KONTO MIT HÜLLE. `probekonto.mjs` legt Konten ohne
 * Schlüsselmaterial an — dort kann kein Paar entstehen, weil es keinen
 * Inhaltsschlüssel gibt, der den privaten Teil verpacken könnte. Dieses
 * Konto entsteht deshalb über die Einladung und `pw_handling.php`, im
 * Browser, wie jedes echte (`passwortSetzen()`).
 *
 * DIE SPERRE WIRD GESTELLT, NICHT ERKLOPFT. Zehn falsche Token sperrten im
 * Topf `login` auch die ADRESSE der Sandbox (`rate_merkmale()` zählt immer
 * beide) — und jede Anmeldung danach, bis 15 Minuten vergangen sind. Der
 * Weg misst deshalb, dass ein Fehlversuch unter dem Kontomerkmal zählt, und
 * setzt die Sperre dann von Hand; die Leiter selbst misst die Ratenprobe.
 * Beide Zeilen stellt er danach wieder her.
 */

import { execFileSync } from 'node:child_process';
import { php, probekontoRaeumen, eigenerKontext, passwortSchicken, passwortSetzen, WURZEL }
  from '../probekonto.mjs';

const ADRESSE  = 'bedienprobe-rueckweg@probe.invalid';
/* Das Passwort des Demo-Kontos ist öffentlich; der Läufer nimmt es aus
   `--demo-pw` mit derselben Vorgabe. Gebraucht wird es, damit die Sperre
   des Demo-Kontos mit einem GÜLTIGEN Token gemessen wird — mit einem
   falschen wiese schon die Passwortprüfung ab, und die Sperre stünde
   ungeprüft da. */
const DEMO_PW  = (() => {
  const i = process.argv.indexOf('--demo-pw');
  return i > 0 ? process.argv[i + 1] : 'nadokudemo0815';
})();
const PASSWORT = 'Bedienprobe-Rueckweg-Flint-Stern-7';

/** Ein PHP-Zeichenkettenliteral in einfachen Anführungszeichen. */
const q = s => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";
/** Die drei Spalten und die Zähler, die der Weg vergleicht. */
function stand(id) {
  return JSON.parse(php(`
    $id = ${Number(id)};
    $u = db()->query("SELECT rw_oeffentlich, rw_privat, rw_seit FROM users WHERE id = $id")
             ->fetch(PDO::FETCH_ASSOC);
    $z = static function (string $art) use ($id): int {
        $st = db()->prepare("SELECT COUNT(*) FROM protokoll_ereignisse
                              WHERE art = ? AND betroffen_user_id = ?");
        $st->execute([$art, $id]);
        return (int)$st->fetchColumn();
    };
    $st = db()->prepare("SELECT COUNT(*) FROM mail_warteschlange WHERE schluessel = ? AND empfaenger = ?");
    $st->execute(['rueckweg_erneuert', ${q(ADRESSE)}]);
    echo json_encode($u + ['angelegt' => $z('rueckweg_angelegt'), 'erneuert' => $z('rueckweg_erneuert'),
                           'mails' => (int)$st->fetchColumn()]);`));
}

/** Bis zu `ms` warten, bis das Paar in der Datenbank steht. */
async function paarAbwarten(id, ms) {
  const ende = Date.now() + ms;
  for (;;) {
    const st = stand(id);
    if (st.rw_oeffentlich || Date.now() > ende) { return st; }
    await new Promise(r => setTimeout(r, 250));
  }
}

/** Eine Anfrage an den Endpunkt aus der Seite heraus — über EdApi, wie die
 *  Anwendung selbst; `art` wählt, was mitgeht. */
function anfrage(s, art, passwort) {
  return s.evaluate(async ({ art, passwort }) => {
    const b64 = p => btoa(String.fromCharCode(...new Uint8Array(p)));
    const kurve = art === 'fremdeKurve' ? 'P-384' : 'P-256';
    const kp = await crypto.subtle.generateKey({ name: 'ECDSA', namedCurve: kurve }, true, ['sign']);
    const r = crypto.getRandomValues(new Uint8Array(160));
    const k = { oeffentlich: b64(await crypto.subtle.exportKey('spki', kp.publicKey)),
                privat: 'edk1:' + b64(r.buffer) };
    if (art === 'richtig' || art === 'fremdeKurve' || art === 'ersetzen') {
      k.token = (await EdCrypto.deriveKeys(passwort, KDF_SALT, KDF_ITER)).authToken;
    } else if (art === 'falsch') {
      k.token = '0'.repeat(64);
    }
    if (art === 'falsch' || art === 'fremdeKurve' || art === 'ersetzen') { k.ersetzen = 1; }
    const a = await EdApi.postJson('api/rueckweg_anlegen.php', k);
    return a.status;
  }, { art, passwort });
}

export const wege = [
  {
    name: 'einstellungen-profil-rueckweg',
    paket: 'RW-02', punkt: 'E-RW-02', rolle: 'demo',
    soll: 'Paar 0 → 1 (angelegt +1, Mails +0), zweite Anmeldung unverändert; '
        + 'ohne Token 403, falsches Token 403, vorhanden 409, fremde Kurve 400, '
        + 'Fehlversuch zählt, Sperre 429; Karte „eingerichtet", Erneuern mit '
        + 'Passwort (erneuert +1, Mails +1); Demo 403/403, kein Paar',
    async fahren(k) {
      const kontext = await eigenerKontext(k);
      const teile = [];
      let ok = true;
      const merke = (gut, text) => { teile.push(text); if (!gut) { ok = false; } };
      let id = 0;
      let ipZeilen = null;
      try {
        /* 1. Ein Konto mit Schlüsselhülle. */
        const s = await kontext.newPage();
        ({ id } = await passwortSetzen(s, k.basis, ADRESSE, PASSWORT, 'Bedienprobe Rückweg'));
        const st0 = stand(id);

        /* 2. Anmelden: das Paar entsteht still. */
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForURL(u => !/login\.php/.test(u.pathname), { timeout: 90000 });
        const standSeite = await s.evaluate(() => typeof RW_STAND === 'undefined' ? null : RW_STAND);
        const st1 = await paarAbwarten(id, 15000);
        merke(!st0.rw_oeffentlich && !!st1.rw_oeffentlich && st1.angelegt === 1 && st1.mails === 0,
          `Paar ${st0.rw_oeffentlich ? 1 : 0} → ${st1.rw_oeffentlich ? 1 : 0} (RW_STAND „${standSeite}"), `
          + `angelegt +${st1.angelegt}, Mails +${st1.mails}`);
        merke(/^edk1:/.test(st1.rw_privat || ''), 'privater Teil ' + String(st1.rw_privat || '').slice(0, 5));

        /* 3. Zweite Anmeldung: unverändert, und RW_STAND sagt „da". */
        await s.goto(`${k.basis}/logout.php`, { waitUntil: 'domcontentloaded' });
        await passwortSchicken(s, k.basis, ADRESSE, PASSWORT);
        await s.waitForURL(u => !/login\.php/.test(u.pathname), { timeout: 90000 });
        await s.waitForLoadState('networkidle');
        const standDa = await s.evaluate(() => RW_STAND);
        const st2 = stand(id);
        merke(st2.rw_oeffentlich === st1.rw_oeffentlich && st2.angelegt === 1 && standDa === 'da',
          `zweite Anmeldung ${st2.rw_oeffentlich === st1.rw_oeffentlich ? 'unverändert' : 'VERÄNDERT'} `
          + `(RW_STAND „${standDa}")`);

        /* 4. Der Endpunkt weist ab — und schreibt nichts. Die Fehlversuche
              zählen auch unter der Adresse der Sandbox; deren Zeilen im Topf
              `login` werden vorher gemerkt und im Schluss zurückgeschrieben. */
        ipZeilen = php(`echo json_encode(db()->query("SELECT * FROM rate_limits
          WHERE topf = 'login' AND merkmal LIKE 'ip:%'")->fetchAll(PDO::FETCH_ASSOC));`);
        const ohne    = await anfrage(s, 'ohne', PASSWORT);
        const falsch  = await anfrage(s, 'falsch', PASSWORT);
        const da      = await anfrage(s, 'richtig', PASSWORT);
        const kurve   = await anfrage(s, 'fremdeKurve', PASSWORT);
        const st3 = stand(id);
        merke(ohne === 403 && falsch === 403 && da === 409 && kurve === 400
              && st3.rw_oeffentlich === st1.rw_oeffentlich,
          `ohne Token ${ohne}, falsches Token ${falsch}, vorhanden ${da}, fremde Kurve ${kurve}, `
          + `Paar ${st3.rw_oeffentlich === st1.rw_oeffentlich ? 'unverändert' : 'VERÄNDERT'}`);

        /* 5. Der Topf `login`: Der Fehlversuch aus 4 zählt unter dem Konto,
              eine gestellte Sperre hält auch das richtige Token auf. */
        const merkmal = 'id:' + ADRESSE;
        const versuche = Number(php(`$st = db()->prepare("SELECT COALESCE(MAX(versuche), 0)
          FROM rate_limits WHERE topf = 'login' AND merkmal = ?"); $st->execute([${q(merkmal)}]);
          echo (int)$st->fetchColumn();`));
        php(`db()->prepare("UPDATE rate_limits SET gesperrt_bis = DATE_ADD(NOW(), INTERVAL 900 SECOND)
          WHERE topf = 'login' AND merkmal = ?")->execute([${q(merkmal)}]);`);
        const gesperrt = await anfrage(s, 'ersetzen', PASSWORT);
        php(`db()->prepare("DELETE FROM rate_limits WHERE topf = 'login' AND merkmal = ?")
          ->execute([${q(merkmal)}]);`);
        merke(versuche >= 1 && gesperrt === 429, `Topf login: ${versuche} Fehlversuch, Sperre → ${gesperrt}`);

        /* 6. Die Karte — erst mit eingeschaltetem Zweitfaktor. */
        execFileSync('php', ['tools/zweitfaktor/pruefkonto.php', ADRESSE],
                     { cwd: WURZEL, stdio: 'ignore' });
        await s.goto(`${k.basis}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
        const zeile = s.locator('#k-zweitfaktor .zeile', { hasText: 'Rückweg mit dem Wiederherstellungsschlüssel' });
        const plakette = ((await zeile.locator('.plakette').textContent().catch(() => '')) || '').trim();
        const knopf = await s.locator('#k-zweitfaktor [data-rueckweg-auf]').count();
        merke(plakette === 'eingerichtet' && knopf === 1, `Karte „${plakette}", Knopf ${knopf}`);

        /* 7. Erneuern: zuerst mit falschem Passwort, dann mit dem richtigen. */
        await s.click('#k-zweitfaktor [data-rueckweg-auf]');
        await s.fill('#rw-pw', 'falsch-falsch-falsch');
        await s.click('#dlg-rueckweg [data-rw-erneuern]');
        const fehler = s.locator('#dlg-rueckweg [data-rw-fehler]');
        await fehler.waitFor({ state: 'visible', timeout: 60000 });
        const fehlerText = ((await fehler.textContent()) || '').trim();
        const st4 = stand(id);
        await s.fill('#rw-pw', PASSWORT);
        await Promise.all([
          s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 90000 }),
          s.click('#dlg-rueckweg [data-rw-erneuern]'),
        ]);
        const meldung = ((await s.locator('.meldung', { hasText: 'Rückweg erneuert' }).first()
          .textContent().catch(() => '')) || '').replace(/\s+/g, ' ').trim();
        const st5 = stand(id);
        merke(st4.rw_oeffentlich === st1.rw_oeffentlich && /Passwort/.test(fehlerText),
          `falsches Passwort: „${fehlerText}", nichts gesendet`);
        merke(!!st5.rw_oeffentlich && st5.rw_oeffentlich !== st1.rw_oeffentlich
              && st5.erneuert === 1 && st5.mails === 1 && meldung !== '',
          `erneuert: neuer Wert, erneuert +${st5.erneuert}, Mails +${st5.mails}, Meldung ${meldung ? 'da' : 'fehlt'}`);

        /* 8. Das Demo-Konto (die Seite des Läufers): kein Paar, 403 zweimal —
              mit dem richtigen Token, ohne und mit `ersetzen`. */
        await k.seite.goto(`${k.basis}/index.php`, { waitUntil: 'domcontentloaded' });
        const demoStand = await k.seite.evaluate(() => typeof RW_STAND === 'undefined' ? null : RW_STAND);
        const demoSkript = await k.seite.locator('script[src*="rueckweg.js"]').count();
        const d1 = await anfrage(k.seite, 'richtig', DEMO_PW);
        const d2 = await anfrage(k.seite, 'ersetzen', DEMO_PW);
        const demoPaare = Number(php(`require_once "server/demo_lib.php"; $d = demo_id();
          echo $d === null ? -1 : (int)db()->query("SELECT COUNT(*) FROM users WHERE id = $d
            AND (rw_oeffentlich IS NOT NULL OR rw_privat IS NOT NULL OR rw_seit IS NOT NULL)")->fetchColumn();`));
        merke(demoStand === 'demo' && demoSkript === 0 && d1 === 403 && d2 === 403 && demoPaare === 0,
          `Demo: RW_STAND „${demoStand}", rueckweg.js ${demoSkript}, ohne/mit ersetzen ${d1}/${d2}, `
          + `${demoPaare} Paare`);

        return { ist: teile.join(' · '), ok, bemerkung: '' };
      } finally {
        await kontext.close();
        if (ipZeilen !== null) {
          php(`db()->exec("DELETE FROM rate_limits WHERE topf = 'login' AND merkmal LIKE 'ip:%'");
            foreach (json_decode(${q(ipZeilen)}, true) as $z) {
              $sp = array_keys($z);
              db()->prepare("INSERT INTO rate_limits (" . implode(", ", $sp) . ") VALUES ("
                . implode(", ", array_fill(0, count($sp), "?")) . ")")->execute(array_values($z));
            }`);
        }
        php(`db()->prepare("DELETE FROM mail_warteschlange WHERE empfaenger = ?")->execute([${q(ADRESSE)}]);`);
        probekontoRaeumen(ADRESSE);
      }
    },
  },
];
