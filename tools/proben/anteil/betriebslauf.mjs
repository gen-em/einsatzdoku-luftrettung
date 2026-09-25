/* Betriebslauf — die Karte, das Blatt und die Zustände im ECHTEN Browser
 * (S10/AP3, E-S10-09 bis E-S10-12)
 * ===========================================================================
 *
 * WAS HIER GEMESSEN WIRD UND SONST NIRGENDS. `probe.php` prüft die
 * Zustandsmaschine als Funktion, `endpunkt.py` den Endpunkt, `umstellungslauf.mjs`
 * die stille Umstellung. Keiner von ihnen sieht die OBERFLÄCHE — und die
 * Zustände `abweichend` und `Rotation` sind genau die, die niemand je zu sehen
 * bekommt, bis sie eintreten. Nach AP2 standen sie im Prüfdokument unter „nicht
 * geprüft"; hier werden sie hergestellt, bedient und wieder zurückgestellt.
 *
 * SIE FASST `config.php` UND `app_state` AN und legt beides im `finally`
 * zurück, mit byteweisem Vergleich. **Gegen eine Testinstallation fahren,
 * nicht gegen den Produktivserver** — dieselbe Ansage wie bei den übrigen
 * Teilen der Anteilprobe.
 *
 * Aufruf:
 *   node tools/proben/anteil/betriebslauf.mjs
 *   node tools/proben/anteil/betriebslauf.mjs --motor firefox|webkit
 *
 * Rückgabewert: 0 = alle Erwartungen erfüllt, 1 = mindestens eine nicht.
 */
import { spawnSync } from 'node:child_process';
import { readFileSync, writeFileSync, copyFileSync, unlinkSync, existsSync } from 'node:fs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import('file://' + MODUL);
const pw = PW.default ?? PW;
const { motorWahl, starten, nachDemPasswort } = await import(
  'file:///home/user/einsatzdoku-luftrettung/tools/motor.mjs');

const WURZEL = '/home/user/einsatzdoku-luftrettung';
const CONFIG = WURZEL + '/server/config.php';
const SICHER = CONFIG + '.betriebslauf';
const BASIS = 'https://127.0.0.1:8443';
const ADMIN = 'admin@gen-em.org', ADMIN_PW = 'pruefstandzugang2026';
/* Das Konto mit Einsatzbestand aus dem Referenzdatensatz — dasselbe, das der
 * Umstellungslauf benutzt. Nicht das Admin-Konto: Es hat keine Einsätze, und
 * Abschnitt 8b will nach dem Reset etwas zu LESEN haben. */
const UMLAUF = 'umlauf-csv@gen-em.org', UMLAUF_PW = 'umlaufpruefung2026';
/* Der Wiederherstellungsschlüssel des Admin-Kontos. Er entsteht beim
 * Einrichten der Testinstallation (`lokal_einrichten.sh`, Schritt 6) und
 * liegt seither in dieser Datei. OHNE IHN entfällt Abschnitt 8b — als
 * *nicht gemessen, mit Grund*, nicht als roter Haken. */
const RC_DATEI = process.env.ADMIN_RC || '/tmp/admin-rc.json';
const NEU_PW = 'Neuanfangprobe!2026';
const motor = motorWahl(process.argv.slice(2));

let ok = 0, offen = 0, nichtGemessen = 0;
const pruefe = (was, ist, soll) => {
  const gleich = JSON.stringify(ist) === JSON.stringify(soll);
  gleich ? ok++ : offen++;
  console.log(`  ${gleich ? 'ok  ' : 'FEHL'} ${was.padEnd(54)} ${JSON.stringify(ist)}`
    + (gleich ? '' : `  erwartet: ${JSON.stringify(soll)}`));
};
const teil = (n) => console.log(`\n${n}`);

/** Ein Wert als PHP-Literal in EINFACHEN Anführungszeichen.
 *
 * DER TEURE FUND DIESES PAKETS (F-S10-AP3-05). Abschnitt 8b legt sechs Felder
 * des Admin-Kontos zurück, und die Werte standen als `JSON.stringify(...)` im
 * PHP-Code — also in DOPPELTEN Anführungszeichen. PHP ersetzt darin jede
 * Zeichenfolge, die wie eine Variable aussieht. Ein bcrypt-Hash sieht so aus:
 * `$2y$12$xdD.DxofamuKzHNEvq…` — daraus wurde `$2y$12.`, sieben Zeichen. Das
 * Konto war danach **mit keinem Passwort mehr erreichbar**, und der nächste
 * Lauf blieb schon an der Anmeldung stehen, ohne zu sagen, warum. Wer aus
 * JavaScript PHP-Quelltext baut, nimmt einfache Anführungszeichen — immer,
 * auch wenn der Wert heute harmlos aussieht. */
const phpStr = (s) => "'" + String(s).replace(/\\/g, '\\\\').replace(/'/g, "\\'") + "'";

/** Ein Stück PHP gegen dieselbe Installation — kein zweiter Datenbankzugang. */
function php(code) {
  const r = spawnSync('php', ['-r', `require ${JSON.stringify(WURZEL + '/server/db.php')};` + code],
                      { encoding: 'utf8' });
  if (r.status !== 0) { throw new Error('php: ' + (r.stderr || '').slice(0, 300)); }
  return r.stdout;
}
const marke = (k) => php(`$s=db()->prepare("SELECT v FROM app_state WHERE k=?");`
  + `$s->execute([${JSON.stringify(k)}]); echo (string)$s->fetchColumn();`).trim();
const markeSetzen = (k, v) => php(
  `$s=db()->prepare("INSERT INTO app_state (k,v) VALUES (?,?) `
  + `ON DUPLICATE KEY UPDATE v=VALUES(v)"); $s->execute([${JSON.stringify(k)},${JSON.stringify(v)}]);`);
const markeWeg = (k) => php(
  `$s=db()->prepare("DELETE FROM app_state WHERE k=?"); $s->execute([${JSON.stringify(k)}]);`);

/** Einen Eintrag in config.php setzen oder entfernen — am Prüfstand, von Hand.
 *
 * WARUM HIER GEWARTET WIRD. Die Anwendung verwirft nach jedem Schreiben den
 * Bytecode-Zwischenspeicher (`opcache_invalidate()` in
 * `config_eintrag_schreiben()`, aufgestellt in S2/AP7). Dieser Prüfstand
 * schreibt an der Anwendung VORBEI und kann das nicht — er sitzt in einem
 * anderen Prozess. OPcache prüft den Zeitstempel aber nur alle
 * `opcache.revalidate_freq` Sekunden (Vorgabe 2), und `php -S` läuft mit
 * eingeschaltetem OPcache: `opcache.enable_cli` gilt für die SAPI `cli`, der
 * eingebaute Server heißt `cli-server`. Ohne die Wartezeit liest die nächste
 * Anfrage die ALTE config.php — der Lauf stand in Abschnitt 5 vor einer Karte
 * im Zustand `bereit` und wartete 30 Sekunden auf ein Eingabefeld, das es in
 * dieser Lage nicht gibt (F-S10-AP3-01). Das ist ein Fehler DIESES Werkzeugs,
 * nicht der Anwendung. */
const OPCACHE_RUHE = 2200;
async function cfg(name, hex) {
  let t = readFileSync(CONFIG, 'utf8');
  const zeile = new RegExp(`^[ \\t]*(['"])${name}\\1\\s*=>.*\\r?\\n`, 'm');
  if (hex === null) { t = t.replace(zeile, ''); }
  else if (zeile.test(t)) { t = t.replace(zeile, `  '${name}' => '${hex}',\n`); }
  else { t = t.replace(/(return\s*(?:\[|array\s*\()\s*\r?\n)/, `$1  '${name}' => '${hex}',\n`); }
  writeFileSync(CONFIG, t);
  await new Promise(r => setTimeout(r, OPCACHE_RUHE));
}
const cfgLesen = (name) => {
  const m = new RegExp(`['"]${name}['"]\\s*=>\\s*'([0-9a-f]{64})'`).exec(readFileSync(CONFIG, 'utf8'));
  return m ? m[1] : null;
};

const browser = await starten(pw, motor, {});
const ctx = await browser.newContext({ ignoreHTTPSErrors: true });
const fehler = [];
ctx.on('pageerror', e => fehler.push('JS: ' + e.message));
ctx.on('console', m => {
  const t = m.text();
  if (m.type() !== 'error') { return; }
  if (/openstreetmap|ERR_ABORTED|Load request cancelled|NS_BINDING_ABORTED|status=2152398850|Failed to load resource/.test(t)) { return; }
  fehler.push('KONSOLE: ' + t.slice(0, 160));
});
const p = await ctx.newPage();

async function oeffne(pfad) {
  await p.goto(BASIS + pfad, { waitUntil: 'domcontentloaded' });
  await p.waitForTimeout(350);
}
const text = () => p.evaluate(() => document.body.innerText.replace(/\s+/g, ' '));

/** Einen Knopf der Karte DRÜCKEN — und, wo eine Rückfrage hängt, sie bejahen.
 *
 * NICHT `form.requestSubmit()` UND NICHT `form.submit()`. `requestSubmit()`
 * löst das Ereignis `submit` aus, und `confirm.js` fängt es ab: Der Aufruf
 * kehrt zurück, es passiert nichts, und der Lauf misst danach eine Seite, die
 * sich nie geändert hat — so gelesen in Abschnitt 6 (F-S10-AP3-02).
 * `form.submit()` ginge am Zuhörer vorbei, misst dann aber einen Weg, den
 * niemand geht. Der Prüfstand drückt deshalb den Knopf und beantwortet den
 * Dialog, wie es eine Betreiberin täte. */
/** Nach dem Absenden des Passworts: warten, den Code-Schritt des
 * Zweitfaktors gehen und bei einem Fehlschlag ABBRECHEN (P5c/AP5, E-P5c-43).
 *
 * Hier stand `waitForURL(… !login.php)`. Seit dem Zweitfaktor steht die
 * Seite nach dem Passwort des Admin-Kontos WIEDER unter `login.php` und
 * fragt nach dem Code — das Warten liefe 120 Sekunden ins Leere. Und das
 * Einrichtungstor (`zweitfaktor.php`) hätte es durchgelassen, obwohl von
 * dort keine Seite erreichbar ist, die dieser Lauf messen will.
 * `nachDemPasswort()` (motor.mjs) kennt beide.
 *
 * ES WIRFT, WIE VORHER DIE ZEITGRENZE. Ein Fehlschlag soll den Lauf beenden
 * und das `finally` `config.php` zurücklegen lassen — nur jetzt mit einem
 * Grund statt „Timeout 120000ms exceeded". */
async function angemeldetOderAbbruch(tab, konto) {
  const a = await nachDemPasswort(tab, { frist: 120000 });
  if (!a.angemeldet) { throw new Error(`Anmeldung als ${konto} gescheitert — ${a.meldung}`); }
}

/** In einer EIGENEN SITZUNG anmelden und auf eine Seite gehen, die den
 * Inhaltsschlüssel braucht.
 *
 * EIN EIGENER KONTEXT, KEIN ZWEITER TAB. Der Hauptkontext ist als
 * Betreiberin angemeldet; `/login.php` leitet dort sofort weiter, und der
 * Lauf wartete 30 Sekunden auf ein Anmeldefeld, das es auf der Zielseite
 * nicht gibt (F-S10-AP3-04). Ein eigener Kontext hat eigene Cookies — und
 * bildet zugleich ab, was gemeint ist: ein anderer Mensch, ein anderer
 * Browser.
 *
 * `/suche.php` UND NICHT DIE STARTSEITE: `EdUnlock`, `EdCrypto` und die
 * Konstanten `PAT_WRAP`/`KDF_SALT`/`KDF_ITER` stehen nur auf einer Seite mit
 * geschützten Angaben. Auf einer Seite ohne sie misst man, dass nichts
 * passiert — und hält es für einen Beleg. */
const nebenkontexte = [];
async function tabAnmelden(konto, passwort) {
  const k = await browser.newContext({ ignoreHTTPSErrors: true });
  nebenkontexte.push(k);
  k.on('pageerror', e => fehler.push('JS: ' + e.message));
  const tab = await k.newPage();
  await tab.goto(BASIS + '/login.php', { waitUntil: 'domcontentloaded' });
  await tab.fill('input[name="email"]', konto);
  await tab.fill('input[name="password"]', passwort);
  await tab.click('#loginform button[type="submit"]');
  await angemeldetOderAbbruch(tab, konto);
  await tab.goto(BASIS + '/suche.php', { waitUntil: 'domcontentloaded' });
  await tab.waitForFunction(
    () => typeof EdCrypto !== 'undefined' && typeof EdUnlock !== 'undefined',
    { timeout: 60000 }).catch(() => {});
  await tab.waitForTimeout(1500);
  return tab;
}

async function handlung(aktion, rueckfrage) {
  await p.evaluate((a) => {
    const f = [...document.querySelectorAll('form')]
      .find(x => x.querySelector('[name="action"]')?.value === a);
    if (!f) { throw new Error('Formular fehlt: ' + a); }
    f.querySelector('button[type="submit"]').click();
  }, aktion);
  if (rueckfrage) {
    await p.waitForSelector('dialog[open] [data-act="yes"]', { timeout: 15000 });
    await p.click('dialog[open] [data-act="yes"]');
  }
  await p.waitForTimeout(1500);
}

copyFileSync(CONFIG, SICHER);
const cfgVorher = readFileSync(CONFIG);
/* DIE SECHS FELDER DES ADMIN-KONTOS, bevor irgendetwas geschieht.
 * Abschnitt 8 stellt seine Hülle um und Abschnitt 8b setzt sein Passwort neu;
 * zurückgelegt wird auf DIESEN Stand — nicht auf den von vor 8b, denn der
 * hängt an einem Anteil, den das `finally` gleich wieder aus `config.php`
 * nimmt. Ein Konto halb zurückzulegen ist schlimmer, als es zu verstellen. */
const U_FELDER = ['password_hash', 'kdf_salt', 'kdf_iter',
                  'pat_wrap_pw', 'pat_wrap_rc', 'pat_key_check'];
const adminId = php(`$s=db()->prepare("SELECT id FROM users WHERE email=?");`
  + `$s->execute([${JSON.stringify(ADMIN)}]); echo (string)$s->fetchColumn();`).trim();
const adminVorher = JSON.parse(php(`$s=db()->prepare("SELECT ${U_FELDER.join(',')} `
  + `FROM users WHERE id=?"); $s->execute([${adminId}]);`
  + `echo json_encode($s->fetch(PDO::FETCH_ASSOC));`));
const adminHuelle = () => php(`$s=db()->prepare("SELECT LEFT(pat_wrap_pw,15) FROM users `
  + `WHERE id=?"); $s->execute([${adminId}]); echo (string)$s->fetchColumn();`).trim();
const adminFeld = (f) => php(`$s=db()->prepare("SELECT ${f} FROM users WHERE id=?");`
  + `$s->execute([${adminId}]); echo (string)$s->fetchColumn();`).trim();

/* DIE HÜLLEN ALLER KONTEN, bevor irgendetwas geschieht.
 *
 * WARUM AM ANFANG UND NICHT IN ABSCHNITT 7. Abschnitt 6b meldet ein Konto an
 * und lässt die stille Umstellung laufen — die packt den Inhaltsschlüssel mit
 * dem Datenschlüssel des NEUEN Anteils neu ein. Das ist nicht rückrechenbar:
 * Steht die alte Hülle nicht mehr zur Verfügung und ist der Anteil, mit dem
 * die neue gebaut wurde, aus `config.php` verschwunden, kommt das Konto an
 * seine geschützten Angaben NIE WIEDER heran — außer über seinen
 * Wiederherstellungsschlüssel.
 *
 * GENAU DAS IST PASSIERT (F-S10-AP3-08): Der Schnappschuss stand in
 * Abschnitt 7, also NACH 6b; zurückgelegt wurde auf einen Stand, der schon
 * umgestellt war, und das `finally` nahm den zugehörigen Anteil danach aus
 * `config.php`. `umlauf-csv@gen-em.org` war anschließend ausgesperrt und
 * musste neu eingerichtet werden. Der Schnappschuss gehört an den Anfang, und
 * die alte Hülle ist der einzige Rückweg, weil sie mit dem Anteil aufgeht,
 * den dasselbe `finally` wiederherstellt. */
const huellenVorher = JSON.parse(php(`$r=db()->query("SELECT id,pat_wrap_pw FROM users `
  + `WHERE pat_wrap_pw IS NOT NULL"); echo json_encode($r->fetchAll(PDO::FETCH_ASSOC));`));
const markeAnVorher = marke('kdf_anteil_kennung');
const markeSkVorher = marke('server_key_kennung');
const anteilEcht = cfgLesen('kdf_anteil');

console.log(`Betriebslauf — Motor ${motor}`);
console.log(`  config.php gesichert · app_state kdf_anteil_kennung=${markeAnVorher || '(leer)'}`);

try {
  await p.goto(BASIS + '/login.php', { waitUntil: 'domcontentloaded' });
  await p.fill('input[name="email"]', ADMIN);
  await p.fill('input[name="password"]', ADMIN_PW);
  await p.click('#loginform button[type="submit"]');
  await angemeldetOderAbbruch(p, ADMIN);

  /* ---- 1. Der Regelfall: bereit ---------------------------------------- */
  teil('1. Zustand „bereit" — Karte, Status und Blatt nennen DIESELBE Kennung');
  await oeffne('/betrieb_server.php');
  const kennung = marke('kdf_anteil_kennung');
  const karte = await text();
  pruefe('die Karte steht auf der Seite', /Schlüssel des Servers/.test(karte), true);
  pruefe('sie nennt die Kennung aus app_state',
         new RegExp('Kennung ' + kennung).test(karte), true);
  pruefe('sie zeigt den WERT nicht',
         karte.includes(anteilEcht) || karte.includes(anteilEcht.toUpperCase()), false);

  await oeffne('/betrieb_status.php');
  const status = await text();
  pruefe('die Statuszeile „Server-Anteil" steht da', /Server-Anteil/.test(status), true);
  pruefe('und nennt dieselbe Kennung',
         new RegExp('Server-Anteil Kennung ' + kennung).test(status), true);

  await oeffne('/betrieb_schluesselblatt.php');
  const blatt = await text();
  pruefe('das Blatt nennt dieselbe Kennung',
         new RegExp('Kennung ' + kennung).test(blatt), true);
  /* DIE EINE STELLE, AN DER DER WERT STEHEN MUSS — in Vierergruppen.
   *
   * SEIT P5c/AP9 JE GRUPPE EIN ELEMENT (`.blatt-druck-gruppe`, mit Nummer
   * darüber). Bis dahin stand der Wert als ein Absatz mit Leerzeichen
   * (`.blatt-wert`), und der Seitentext enthielt ihn am Stück. Jetzt steht
   * zwischen den Gruppen ein Zeilenwechsel des Seitentexts; gelesen wird
   * deshalb die Kachel des Anteils, Gruppe für Gruppe. */
  const gruppiert = (anteilEcht.match(/..../g) || []).join(' ');
  const blattWert = await p.evaluate(() => {
    const k = [...document.querySelectorAll('.blatt-kachel')]
      .find(x => /\bkdf_anteil\b/.test(x.querySelector('.blatt-kachel-neben')?.textContent || ''));
    return k ? [...k.querySelectorAll('.blatt-druck-gruppe')].map(g => g.textContent.trim()).join(' ') : '';
  });
  pruefe('und den Wert in Vierergruppen', blattWert.toLowerCase() === gruppiert.toLowerCase(), true);
  pruefe('drei Kennungen gleich (Karte = Status = Blatt)',
         [new RegExp('Kennung ' + kennung).test(karte),
          new RegExp('Kennung ' + kennung).test(status),
          new RegExp('Kennung ' + kennung).test(blatt)], [true, true, true]);

  /* ---- 2. Das Blatt im Druck, auf 210 mm -------------------------------- */
  teil('2. Das Blatt im DRUCK, Papierbreite 210 mm');
  /* 210 mm bei 96 dpi = 793,7 px; abzüglich 2 × 10 mm Rand des Druckers
   * bleiben rund 718 px. Gemessen wird in `media: print` — sonst misst man
   * den Bildschirm und nennt es Druck. */
  await p.emulateMedia({ media: 'print' });
  await p.setViewportSize({ width: 718, height: 1123 });
  await oeffne('/betrieb_schluesselblatt.php');
  const druck = await p.evaluate(() => {
    /* JE WERT EINE KACHEL MIT SECHZEHN GRUPPEN (P5c/AP9, `.blatt-druck`).
     * Bis dahin wurde hier ein Absatz `.blatt-wert` Gruppe für Gruppe über
     * Textbereiche vermessen. Jetzt ist jede Gruppe ein eigenes Element;
     * zerschnitten ist eine Gruppe, deren TEXT auf zwei Zeilen liegt. */
    const kacheln = [...document.querySelectorAll('.blatt-kachel')];
    if (!kacheln.length) { return { da: false }; }
    let zerschnitten = 0, gruppenMin = Infinity;
    for (const k of kacheln) {
      const gs = [...k.querySelectorAll('.blatt-druck-gruppe')];
      gruppenMin = Math.min(gruppenMin, gs.length);
      for (const g of gs) {
        const bereich = document.createRange();
        bereich.selectNodeContents(g);
        const oben = new Set([...bereich.getClientRects()].map(r2 => Math.round(r2.top)));
        if (oben.size > 1) { zerschnitten++; }
      }
    }
    const blatt = document.querySelector('.blatt-druck');
    return {
      da: true,
      gruppen: gruppenMin,
      zerschnitten,
      zeilen: kacheln.length,
      ueberlauf: Math.max(0, blatt.scrollWidth - blatt.clientWidth),
      knoepfe: [...document.querySelectorAll('.nur-bildschirm')]
        .filter(e => getComputedStyle(e).display !== 'none').length,
      flaeche: getComputedStyle(document.body).backgroundColor,
    };
  });
  pruefe('das Blatt trägt den Wert', druck.da, true);
  pruefe('16 Vierergruppen', druck.gruppen, 16);
  pruefe('keine Gruppe über zwei Zeilen zerschnitten', druck.zerschnitten, 0);
  pruefe('kein waagerechter Überlauf', druck.ueberlauf, 0);
  pruefe('die Bildschirmknöpfe werden nicht mitgedruckt', druck.knoepfe, 0);
  console.log(`  gemessen: ${druck.zeilen} Kachel(n) bei 718 px Papierbreite`);
  await p.screenshot({ path: WURZEL + '/tools/proben/anteil/ausgabe-blatt-druck.png',
                       fullPage: true });
  await p.emulateMedia({ media: 'screen' });
  await p.setViewportSize({ width: 1280, height: 900 });

  /* ---- 3. Zustand „abweichend" ------------------------------------------ */
  teil('3. Zustand „abweichend" — die Marke wird verstellt');
  markeSetzen('kdf_anteil_kennung', 'deadbeef');
  await oeffne('/betrieb_status.php');
  const st2 = await text();
  pruefe('Status nennt die ERWARTETE Kennung',
         /gebaut wurden die Hüllen mit deadbeef/.test(st2), true);
  await oeffne('/betrieb_server.php');
  const ka2 = await text();
  pruefe('die Karte bietet „Nachtragen vom Blatt" an',
         /Nachtragen vom Blatt/.test(ka2), true);
  pruefe('und den Neuanfang', /Server-Anteil neu erzeugen/.test(ka2), true);

  /* ---- 4. Nachtragen mit FALSCHEM Wert ---------------------------------- */
  teil('4. Nachtragen mit falschem Wert — es darf nichts geschrieben werden');
  const vorher = readFileSync(CONFIG);
  await p.fill('input[name="wert"]', 'f'.repeat(64));
  await p.evaluate(() => {
    const f = [...document.querySelectorAll('form')]
      .find(x => x.querySelector('[name="action"]')?.value === 'schluessel_anteil_nachtragen');
    const s = f.querySelector('[name="ersetzen"]'); if (s) { s.checked = true; }
  });
  await handlung('schluessel_anteil_nachtragen', false);
  const ka3 = await text();
  pruefe('die Meldung nennt BEIDE Kennungen',
         /Die Kennung dieses Werts ist [0-9a-f]{8}, erwartet ist deadbeef/.test(ka3), true);
  pruefe('config.php ist byte-gleich geblieben',
         readFileSync(CONFIG).equals(vorher), true);

  /* ---- 5. Nachtragen mit RICHTIGEM Wert ---------------------------------- */
  teil('5. Nachtragen mit richtigem Wert');
  /* Hergestellt wird die Lage so, wie sie im Ernstfall entsteht: Die Marke
   * nennt den echten Wert, in config.php steht ein anderer. */
  markeSetzen('kdf_anteil_kennung', markeAnVorher);
  await cfg('kdf_anteil', 'a'.repeat(64));
  await oeffne('/betrieb_server.php');
  await p.fill('input[name="wert"]', (anteilEcht.match(/..../g) || []).join(' '));
  await p.evaluate(() => {
    const f = [...document.querySelectorAll('form')]
      .find(x => x.querySelector('[name="action"]')?.value === 'schluessel_anteil_nachtragen');
    const s = f.querySelector('[name="ersetzen"]'); if (s) { s.checked = true; }
  });
  await handlung('schluessel_anteil_nachtragen', false);
  const ka4 = await text();
  pruefe('die Meldung bestätigt das Nachtragen',
         /Der Server-Anteil ist nachgetragen/.test(ka4), true);
  pruefe('config.php trägt wieder den echten Wert', cfgLesen('kdf_anteil'), anteilEcht);
  pruefe('die Gruppierung aus dem Blatt hat nicht gestört',
         /Kennung /.test(ka4), true);

  /* ---- 6. Rotation ------------------------------------------------------ */
  teil('6. Rotation — zwei Einträge, drei Zahlen, und der alte geht erst am Ende');
  await oeffne('/betrieb_server.php');
  await handlung('schluessel_anteil_wechseln', true);
  const neu = cfgLesen('kdf_anteil'), alt = cfgLesen('kdf_anteil_alt');
  pruefe('config.php trägt jetzt BEIDE Einträge',
         [neu !== null, alt !== null, neu !== alt], [true, true, true]);
  pruefe('der alte Eintrag ist der vorherige Wert', alt, anteilEcht);
  pruefe('app_state ist auf die NEUE Kennung gewandert',
         marke('kdf_anteil_kennung') !== markeAnVorher, true);
  const neuKennung = marke('kdf_anteil_kennung');
  const ka5 = await text();
  pruefe('die Karte meldet die Rotation', /Rotation läuft/.test(ka5), true);
  pruefe('„alten Anteil entfernen" wird NICHT angeboten, solange Konten offen sind',
         /Alten Anteil entfernen/.test(ka5), false);

  await oeffne('/betrieb_status.php');
  const st3 = await text();
  pruefe('Status zählt neu und alt getrennt',
         /auf dem aktuellen Anteil.*noch auf dem alten/.test(st3), true);

  /* ---- 6b. Die Zahl wandert, wenn sich ein Konto anmeldet --------------- */
  teil('6b. Eine echte Anmeldung schiebt ein Konto auf den neuen Anteil');
  /* DIE ABNAHME VERLANGT DIESE ZAHL AUSDRÜCKLICH — „nach Anmeldung eines
   * Kontos wandert eine Zahl (vorher/nachher genannt)". Abschnitt 7 stellt
   * die Lage gleich per SQL her, weil er nicht auf vier Anmeldungen warten
   * kann; HIER wird der Weg gegangen, den eine NutzerIn geht. Gemessen wird
   * an der Karte, nicht an der Datenbank: Die Frage ist, ob die BetreiberIn
   * den Fortschritt SIEHT. */
  const zahlen = (t) => {
    const m = /(\d+) auf dem aktuellen Anteil · (\d+) (?:noch )?auf dem alten/.exec(t);
    return m ? [Number(m[1]), Number(m[2])] : null;
  };
  const vorZahl = zahlen(ka5);
  pruefe('die Karte nennt zwei Zahlen', vorZahl !== null, true);

  const tabU = await tabAnmelden(UMLAUF, UMLAUF_PW);
  /* Die Umstellung läuft im Hintergrund; gewartet wird auf ihr Ergebnis in
   * der Datenbank, nicht auf eine feste Zahl von Millisekunden. */
  const uid = php(`$s=db()->prepare("SELECT id FROM users WHERE email=?");`
    + `$s->execute([${JSON.stringify(UMLAUF)}]); echo (string)$s->fetchColumn();`).trim();
  const huelle = () => php(`$s=db()->prepare("SELECT LEFT(pat_wrap_pw,15) FROM users `
    + `WHERE id=?"); $s->execute([${uid}]); echo (string)$s->fetchColumn();`).trim();
  let gewartet = 0;
  while (huelle() !== 'edka1:' + neuKennung + ':' && gewartet < 30000) {
    await tabU.waitForTimeout(500); gewartet += 500;
  }
  const huelleNachher = huelle();
  await tabU.close(); await tabU.context().close();

  await oeffne('/betrieb_server.php');
  const nachZahl = zahlen(await text());
  console.log(`  gemessen: ${JSON.stringify(vorZahl)} → ${JSON.stringify(nachZahl)}`
    + `  (Hülle des Kontos: ${huelleNachher}, nach ${gewartet} ms)`);
  pruefe('die Hülle des Kontos trägt jetzt die neue Kennung',
         huelleNachher, 'edka1:' + neuKennung + ':');
  pruefe('eine Zahl ist von alt nach neu gewandert',
         nachZahl && vorZahl
           && nachZahl[0] === vorZahl[0] + 1 && nachZahl[1] === vorZahl[1] - 1, true);

  /* ---- 7. Der alte Anteil geht, sobald niemand mehr auf ihm steht -------- */
  teil('7. „Alten Anteil entfernen" — erst bei null');
  /* Hergestellt, indem alle Hüllen auf die neue Kennung gesetzt werden. Das
   * ist dasselbe, was die stille Umstellung tut; hier von Hand, damit der
   * Lauf nicht auf vier Anmeldungen warten muss. */
  const neuK = neuKennung;
  const wrapsVorher = php(`$r=db()->query("SELECT id,pat_wrap_pw FROM users `
    + `WHERE pat_wrap_pw IS NOT NULL"); echo json_encode($r->fetchAll(PDO::FETCH_ASSOC));`);
  php(`db()->exec("UPDATE users SET pat_wrap_pw = CONCAT('edka1:${neuK}:', `
    + `SUBSTRING(pat_wrap_pw, 16)) WHERE pat_wrap_pw LIKE 'edka1:%'");`);
  await oeffne('/betrieb_server.php');
  const ka6 = await text();
  pruefe('jetzt wird „Alten Anteil entfernen" angeboten',
         /Alten Anteil entfernen/.test(ka6), true);
  await handlung('schluessel_anteil_alt_entfernen', true);
  pruefe('kdf_anteil_alt ist aus config.php entfernt', cfgLesen('kdf_anteil_alt'), null);
  /* Die Hüllen zurücklegen — sie gehören dem Referenzbestand. */
  php(`$d=json_decode(${phpStr(JSON.stringify(JSON.parse(wrapsVorher)))}, true);`
    + `$s=db()->prepare("UPDATE users SET pat_wrap_pw=? WHERE id=?");`
    + `foreach($d as $z){ $s->execute([$z["pat_wrap_pw"], $z["id"]]); }`);

  /* ---- 8. Der Neuanfang lässt sich nicht versehentlich wiederholen ------- */
  teil('8. Neuanfang — nur aus der Lage „abweichend", und nur einmal');
  const nachRotation = cfgLesen('kdf_anteil');

  /* VORBEREITUNG FÜR 8b, und sie muss VOR dem Neuanfang geschehen: Nur ein
   * Konto, dessen Hülle am JETZIGEN Anteil hängt, ist vom Neuanfang
   * betroffen. Das Admin-Konto steht nach `endpunkt.py` auf `edk1:` — und
   * eine `edk1:`-Hülle hängt an keinem Anteil und merkt vom Neuanfang
   * nichts. Gemessen wird an ihm und nicht an `umlauf-csv@`, weil nur von
   * ihm der Wiederherstellungsschlüssel bekannt ist. */
  const rcDa = existsSync(RC_DATEI);
  let ckVorher = null, probe = null;
  const PROBETEXT = 'Probeblock des Betriebslaufs — S10/AP3';
  if (rcDa) {
    spawnSync('python3', [WURZEL + '/tools/proben/anteil/huelle_stellen.py',
                          ADMIN, ADMIN_PW, 'edka1'], { encoding: 'utf8' });
    const tabV = await tabAnmelden(ADMIN, ADMIN_PW);
    /* EIN ECHTER CHIFFRETEXT, gebaut mit demselben `EdCrypto.encrypt()`, das
     * jeden `pat_blob` baut. Nach dem Reset wird er wieder geöffnet — das
     * ist die Zusage „kein Datenverlust", als Zahl statt als Satz. */
    const v = await tabV.evaluate(async (txt) => {
      const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      if (!ck) { return null; }
      return { ck, chiffre: await EdCrypto.encrypt(ck, txt) };
    }, PROBETEXT);
    await tabV.close(); await tabV.context().close();
    if (v) { ckVorher = v.ck; probe = v.chiffre; }
    pruefe('Vorbereitung 8b: Hülle des Admin-Kontos auf edka1: gestellt',
           adminHuelle().slice(0, 6), 'edka1:');
    pruefe('Vorbereitung 8b: Inhaltsschlüssel und Probeblock geholt',
           ckVorher !== null && probe !== null, true);
  } else {
    console.log(`  NICHT GEMESSEN: ${RC_DATEI} fehlt — ohne den `
      + `Wiederherstellungsschlüssel des Admin-Kontos entfällt Abschnitt 8b.`);
  }

  markeSetzen('kdf_anteil_kennung', 'deadbeef');
  await oeffne('/betrieb_server.php');
  await handlung('schluessel_anteil_neuanfang', true);
  const ka7 = await text();
  pruefe('der Neuanfang gelingt', /Ein neuer Server-Anteil ist eingetragen/.test(ka7), true);
  const nachNeu = cfgLesen('kdf_anteil');
  pruefe('config.php trägt einen anderen Wert', nachNeu !== nachRotation, true);
  pruefe('app_state nennt jetzt diesen Wert',
         marke('kdf_anteil_kennung') !== 'deadbeef', true);
  /* Der zweite Versuch — genau das, was ein F5 nach dem Absenden täte. */
  await p.evaluate(async () => {
    const k = document.querySelector('meta[name="csrf"]');
    const daten = new URLSearchParams();
    daten.set('action', 'schluessel_anteil_neuanfang');
    const t = document.querySelector('input[name="csrf"]');
    if (t) { daten.set('csrf', t.value); }
    const r = await fetch('betrieb_server.php', { method: 'POST', body: daten });
    window.__zweiter = await r.text();
  });
  const zweiter = await p.evaluate(() => String(window.__zweiter || ''));
  pruefe('ein zweiter Versuch wird abgewiesen',
         /Ein Neuanfang ist nur nötig/.test(zweiter), true);
  pruefe('und ändert nichts', cfgLesen('kdf_anteil'), nachNeu);

  /* ---- 8b. Nach dem Neuanfang: Meldung, Reset, Daten -------------------- */
  teil('8b. Nach dem Neuanfang — Meldung, Reset über den Wiederherstellungs'
       + 'schlüssel, Daten');
  /* DAS IST DIE ZUSAGE DES GANZEN VORGANGS: „kein Datenverlust — aber ein
   * Vorgang für alle." Sie steht im Handbuch, auf dem Schlüsselblatt und in
   * der Rückfrage vor dem Knopf. Ungemessen wäre sie ein Versprechen.
   *
   * Drei Schritte, und der dritte ist der eigentliche:
   *   1. Das Konto kommt noch HINEIN — das Passwort stimmt ja —, aber der
   *      Entsperrdialog sagt, der Anteil sei erneuert. NICHT „Passwort
   *      falsch": Diese Auskunft schickt die NutzerIn auf die falsche Fährte
   *      und die Verwaltung hinterher.
   *   2. Der Reset über den Wiederherstellungsschlüssel gelingt.
   *   3. Der Inhaltsschlüssel danach ist DERSELBE, und ein vorher gebauter
   *      Chiffretext geht wieder auf. Ein Reset, der die Daten verliert,
   *      gelingt auch. */
  if (!rcDa || ckVorher === null) {
    /* NICHT GEMESSEN IST NICHT ERFUELLT (S10/AP5, dieselbe Lehre wie
     * F-S10-AP5-04). Bis dahin stand hier nur ein `console.log`, und die
     * Schlusszeile meldete dann „40 von 40 erfüllt, 0 offen" — eine Zahl, die
     * grün aussieht und zehn Erwartungen verschweigt. Der Zähler trägt sie
     * jetzt bis in die Schlusszeile, wie es `umstellungslauf.mjs` tut. */
    nichtGemessen += 10;
    console.log('  übersprungen (siehe Abschnitt 8) — 10 Erwartungen NICHT gemessen');
  } else {
    const tabN = await tabAnmelden(ADMIN, ADMIN_PW);
    pruefe('das Konto kommt hinein (die Anmeldung gelingt)',
           !tabN.url().includes('login.php'), true);
    /* DER DIALOG WIRD ABSICHTLICH GERUFEN. Das Admin-Konto hat keine
     * geschützten Angaben — also fragt keine Seite von selbst danach, und
     * ein Lauf, der auf den Dialog wartet, wartet vergebens und nennt es
     * einen Befund (so gelesen im ersten Durchgang, F-S10-AP3-06).
     * `ensureContentKey()` ist derselbe Aufruf, den jede Seite mit
     * geschützten Angaben macht.
     *
     * UND DIE MELDUNG STEHT ERST NACH DER EINGABE. Der Dialog fragt zuerst
     * nach dem Passwort; erst wenn die Ableitung am fehlenden Anteil
     * scheitert, sagt er, woran. Das ist der Weg einer NutzerIn, und genau
     * der wird hier gegangen — Passwort eintragen, „Entsperren" drücken,
     * lesen, was dasteht. */
    await tabN.evaluate(() => {
      window.__ck = EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    });
    await tabN.waitForSelector('dialog[open] input[type="password"]', { timeout: 30000 });
    await tabN.fill('dialog[open] input[type="password"]', ADMIN_PW);
    await tabN.click('dialog[open] [data-act="yes"]');
    /* 600 000 PBKDF2-Runden; der Dialog sagt so lange „Schlüssel wird
     * abgeleitet …". Gewartet wird auf die FERTIGE Meldung, nicht auf eine
     * Anzahl Millisekunden. */
    await tabN.waitForFunction(() => {
      const m = document.querySelector('dialog[open] [data-msg]');
      return m && !m.hidden && !/abgeleitet/.test(m.textContent);
    }, { timeout: 60000 }).catch(() => {});
    const dlg = await tabN.evaluate(() =>
      (document.querySelector('dialog[open]')?.innerText || '').replace(/\s+/g, ' '));
    console.log('  Dialog: ' + dlg.slice(-180));
    pruefe('der Entsperrdialog nennt den ERNEUERTEN Anteil',
           /Server-Anteil wurde erneuert/.test(dlg), true);
    pruefe('und verweist auf den Wiederherstellungsschlüssel',
           /Wiederherstellungsschlüssel/.test(dlg), true);
    pruefe('und sagt NICHT „Passwort falsch"',
           /[Pp]asswort .{0,20}(falsch|stimmt nicht)/.test(dlg), false);
    await tabN.close(); await tabN.context().close();

    /* Schritt 2 — der Reset. Der Setz-Link entsteht so, wie ihn die
     * Verwaltung erzeugt (`admin_user.php`): eine Zeile in
     * `password_resets`. Gegangen wird der Weg danach IM BROWSER, weil dort
     * Salz, Hülle und Prüfsumme entstehen; ein Skript, das das nachbaut,
     * prüfte den Weg nicht mehr, den eine NutzerIn geht. */
    const jeton = php('echo bin2hex(random_bytes(32));').trim();
    php(`db()->prepare("UPDATE password_resets SET used_at=NOW() `
      + `WHERE user_id=? AND used_at IS NULL")->execute([${adminId}]);`
      + `db()->prepare("INSERT INTO password_resets (user_id, token_hash, expires_at) `
      + `VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))")`
      + `->execute([${adminId}, hash("sha256", ${JSON.stringify(jeton)})]);`);
    const rc = JSON.parse(readFileSync(RC_DATEI, 'utf8')).recovery_code;

    const tabR = await ctx.newPage();
    await tabR.goto(BASIS + '/pw_handling.php?token=' + jeton,
                    { waitUntil: 'domcontentloaded' });
    await tabR.waitForSelector('#rc', { timeout: 30000 });
    await tabR.fill('#rc', rc);
    await tabR.fill('#pw1', NEU_PW);
    await tabR.fill('#pw2', NEU_PW);
    await tabR.click('#pwform button[type="submit"]');
    await tabR.waitForTimeout(8000);
    const seiteNach = (await tabR.evaluate(
      () => document.body.innerText.replace(/\s+/g, ' '))).slice(0, 300);
    await tabR.close();
    const huelleNach = adminHuelle();
    pruefe('die neue Hülle hängt am NEUEN Anteil',
           huelleNach, 'edka1:' + marke('kdf_anteil_kennung') + ':');
    pruefe('pat_key_check ist UNVERÄNDERT',
           adminFeld('pat_key_check'), String(adminVorher.pat_key_check));
    if (huelleNach !== 'edka1:' + marke('kdf_anteil_kennung') + ':') {
      console.log('  Seite nach dem Reset: ' + seiteNach);
    }

    /* Schritt 3 — die Daten. Gemessen wird der Inhaltsschlüssel selbst und
     * ein echter Chiffretext, nicht die Zahl der Zeilen einer Liste: Eine
     * Liste erscheint auch dann, wenn jedes geschützte Feld leer bleibt
     * (`CLAUDE.md` 6 — eine grüne Zahl, die das Falsche misst). */
    const tabD = await tabAnmelden(ADMIN, NEU_PW);
    const nach = await tabD.evaluate(async (c) => {
      const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      if (!ck) { return { ck: null }; }
      let klar = null;
      try { klar = await EdCrypto.decrypt(ck, c); } catch (e) { klar = 'FEHLER: ' + e.message; }
      return { ck, klar, dialoge: document.querySelectorAll('dialog[open]').length };
    }, probe);
    await tabD.close(); await tabD.context().close();
    pruefe('die Anmeldung mit dem NEUEN Passwort gelingt', nach.ck !== null, true);
    pruefe('kein Entsperrdialog mehr', nach.dialoge, 0);
    pruefe('der Inhaltsschlüssel ist DERSELBE wie vor dem Neuanfang',
           nach.ck, ckVorher);
    pruefe('der vorher gebaute Chiffretext geht wieder auf', nach.klar, PROBETEXT);
    console.log(`  gemessen: Inhaltsschlüssel ${String(ckVorher).slice(0, 16)}… `
      + `vorher und nachher gleich · 1 von 1 Chiffretext geöffnet`);
  }

  teil('9. Keine Fehler aus der Anwendung');
  pruefe('0 Fehler', fehler, []);

} finally {
  copyFileSync(SICHER, CONFIG);
  try { unlinkSync(SICHER); } catch (e) { /* egal */ }
  if (markeAnVorher) { markeSetzen('kdf_anteil_kennung', markeAnVorher); } else { markeWeg('kdf_anteil_kennung'); }
  if (markeSkVorher) { markeSetzen('server_key_kennung', markeSkVorher); } else { markeWeg('server_key_kennung'); }
  php(`$w = json_decode(${phpStr(JSON.stringify(
        U_FELDER.map(f => adminVorher[f]).concat([Number(adminId)])))}, true);`
    + `$s=db()->prepare("UPDATE users SET ${U_FELDER.map(f => f + '=?').join(',')} `
    + `WHERE id=?"); $s->execute($w);`);
  php(`$d=json_decode(${phpStr(JSON.stringify(huellenVorher))}, true);`
    + `$s=db()->prepare("UPDATE users SET pat_wrap_pw=? WHERE id=?");`
    + `foreach($d as $z){ $s->execute([$z["pat_wrap_pw"], $z["id"]]); }`);
  const huellenJetzt = JSON.parse(php(`$r=db()->query("SELECT id,pat_wrap_pw FROM users `
    + `WHERE pat_wrap_pw IS NOT NULL"); echo json_encode($r->fetchAll(PDO::FETCH_ASSOC));`));
  const huellenGleich = JSON.stringify(huellenJetzt) === JSON.stringify(huellenVorher);
  console.log(`Schlüsselhüllen zurückgelegt: `
    + (huellenGleich ? `alle ${huellenVorher.length} byte-gleich` : 'ABWEICHUNG'));

  /* NACHGEZÄHLT, NICHT ANGENOMMEN: alle sechs Felder gegen den Stand vom
   * Anfang. Ein halb zurückgelegtes Konto ist schlimmer als ein verstelltes,
   * denn der nächste Lauf bleibt an der Anmeldung stehen und sucht den Grund
   * an der Anwendung (F-S10-AP3-05). */
  const abweichend = U_FELDER.filter(f => adminFeld(f) !== String(adminVorher[f] ?? ''));
  console.log(`Konto ${ADMIN} zurückgelegt: `
    + (abweichend.length === 0
       ? `alle ${U_FELDER.length} Felder gleich`
       : `ABWEICHUNG in ${JSON.stringify(abweichend)}`));
  const gleich = readFileSync(CONFIG).equals(cfgVorher);
  console.log(`\nconfig.php zurückgelegt: ${gleich ? 'byte-gleich' : 'ABWEICHUNG'}`);
  console.log(`app_state zurückgestellt auf: ${markeAnVorher || '(nicht gesetzt)'}`);
  await browser.close();
}

console.log(`\nErgebnis (${motor}): ${ok} von ${ok + offen} erfüllt, ${offen} offen`
  + (nichtGemessen ? `, ${nichtGemessen} NICHT gemessen (Grund oben).` : '.'));
process.exit(offen === 0 ? 0 : 1);
