/* Abnahme der Demo-Funktion.
 *
 * Fährt im Browser genau den Weg, den eine Administratorin ginge — und
 * danach den, den eine Besucherin geht. Kein SQL, keine Abkürzung.
 *
 *   1. Demo-Konto im Adminbereich ANLEGEN
 *   2. Als Demo-Konto anmelden, geschützte Angaben lesen
 *   3. Absichtlich verändern: Einsatz löschen, Stammdatum ändern
 *   4. ZURÜCKSETZEN (Adminbereich) und nachsehen, ob alles wieder da ist
 *   5. Konto-Identität: E-Mail und Passwort müssen abgewiesen werden
 *   S. Die Spurfälle (Nr. 500, Konzept RD): Tage und Einsätze mit Spuren
 *      der Stufe 2 und 3 zeichnen eine Linie; Schneiden und Zurücknehmen am
 *      Übungssegment der Stufe 2 (P-S11-30); Zurücknehmen eines Schnitts auf
 *      Stufe 1 geht, eines auf Stufe 2 nicht (F-RD-22); der Wartungslauf gibt
 *      dem Demo-Konto nichts (Nr. 459); ein Zeitraum lässt sich löschen —
 *      vor dem Reset, der alles zurückholt
 *
 * Aufruf: node demo_pruefen.mjs [basis] [schritte]
 *   schritte: Kommaliste aus anlegen,lesen,veraendern,spurfaelle,reset,sperren
 *
 * DIE SOLLZAHLEN KOMMEN AUS DER FIXTURE (`server/demo/fixture.json.gz`,
 * oder DEMO_FIXTURE): Diensttage, Einsätze und Ruhesegmente ohne Papierkorb,
 * dazu die im Papierkorb. Bis RD-07 standen hier 83 / 15 / 95 — der Bestand
 * vor dem Demo-Ausbau —, und die Probe war gegen jede spätere Fixture rot,
 * während ihre Anleitung „0 Befunde" sagte (Konzept RD, F-RD-26).
 *
 * „NICHT GEMESSEN" IST EIN REGULAERER AUSGANG, und die Schlusszeile nennt ihn
 * mit Grund: Es gibt schon ein Demo-Konto (dann fehlt der Knopf „anlegen"),
 * oder die Mengenbremse des Demo-Kontos greift (zwanzig Anmeldungen
 * je Stunde — zwei Laeufe hintereinander reichen). Wer sofort weitermessen
 * muss, leert den Topf ueber den Weg der Anwendung: `rate_erfolg('demo')` und
 * `rate_erfolg('demog')` mit gesetztem `$_SERVER['REMOTE_ADDR']`. Der
 * Rueckgabewert ist 0, solange es keine Befunde gibt — auch mit nicht
 * gemessenen Abschnitten; wer die Schlusszeile ueberliest, haelt einen halben
 * Lauf fuer einen ganzen.
 */
import { writeFileSync, mkdirSync, readFileSync } from 'node:fs';
import { gunzipSync } from 'node:zlib';
/* Der Code-Schritt des Zweitfaktors steht EINMAL, in motor.mjs. */
import { codeSchritt, motorWahl, starten } from '../../motor.mjs';

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = (m => m.default ?? m)(await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL));
/* Die Engine wählt der Läufer (MOTOR=… oder --motor). */
const MOTOR = motorWahl(process.argv);

const basis   = process.argv[2] || 'https://127.0.0.1:8443';
const schritte = (process.argv[3] || 'anlegen,lesen,veraendern,spurfaelle,reset,sperren').split(',');
const ordner  = process.env.AUSGABE || '/tmp/b6-demo';
const admin   = process.env.ADMIN_EMAIL || 'admin@example.invalid';
const adminPw = process.env.ADMIN_PASSWORT || 'pruefstandzugang2026';
const demo    = process.env.DEMO_EMAIL || 'demo@gen-em.org';
const demoPw  = process.env.DEMO_PASSWORT || 'nadokudemo0815';
mkdirSync(ordner, { recursive: true });

/* DER SOLLSTAND AUS DER FIXTURE — dieselbe Datei, die der Reset einspielt. */
const FIXTURE = process.env.DEMO_FIXTURE
  || new URL('../../../server/demo/fixture.json.gz', import.meta.url).pathname;
const fx = JSON.parse(gunzipSync(readFileSync(FIXTURE)).toString('utf8'));
const aktiv = (l) => String((l || []).filter((x) => x.deleted_at == null).length);
const geloescht = (l) => String((l || []).filter((x) => x.deleted_at != null).length);
const SOLL = {
  'diensttage': aktiv(fx.daten.days), 'einsätze': aktiv(fx.daten.missions),
  'ruhesegmente': aktiv(fx.daten.rest_segments),
  'einsätze im papierkorb': geloescht(fx.daten.missions),
};

/* KARTENKACHELN SIND KEIN KONSOLENFEHLER DIESER ANWENDUNG.
 *
 * Der Prüfstand hat keinen Weg ins Netz; jede Kachel scheitert. Früher
 * wurde am TEXT der Konsolenmeldung gefiltert — der trägt die Adresse aber
 * nicht immer: Nach mehreren Versuchen meldet Chromium nur noch
 * `Failed to load resource: net::ERR_TOO_MANY_RETRIES`, ohne zu sagen,
 * WELCHE Quelle. Ein Lauf stand deshalb auf „1 Konsolenfehler" und damit auf
 * Rückgabewert 1, obwohl keine Zeile der Anwendung etwas damit zu tun hatte.
 *
 * Gefiltert wird jetzt mit BELEG statt auf Verdacht: Die Adressen der
 * gescheiterten Anfragen werden mitgeschrieben, und eine Meldung ohne Adresse
 * gilt nur dann als Kachelrauschen, wenn im selben Lauf tatsächlich eine
 * fremde Kachel gescheitert ist. Scheitert etwas unter `127.0.0.1`, bleibt
 * der Fehler stehen — und das ist der Fall, für den die Zählung da ist. */
const KACHELFEHLER = /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|tile\.|opentopomap|arcgisonline/i;
const OHNE_ADRESSE = /^Failed to load resource:\s*net::ERR_[A-Z_]+$/;
const kachelGescheitert = [];
const lokal = /^https:\/\/(127\.0\.0\.1|localhost)(:|\/)/.test(basis);
const browser = await starten(PW, MOTOR);
const kontext = await browser.newContext({ ignoreHTTPSErrors: lokal, viewport: { width: 1500, height: 1100 } });
/* DER WARTUNGSLAUF BLEIBT AUS: Er packt und dünnt im
   Browser aus, sobald eine Seite ihn anstößt (Startseite, Reiter Backup),
   und das Ausdünnen ändert die Punkte — mitten in einem Vergleich wäre das
   eine Abweichung, die keine ist. Die Antwort sagt „nichts fällig" — ein
     Abbruch schriebe einen Fehler in die Konsole. Gemessen wird er in seiner
     eigenen Probe. */
await kontext.route('**/api/wartungslauf.php', (r) => r.fulfill({ status: 200,
    contentType: 'application/json', body: '{"ok":true,"spuren":[],"aufzeichnungen":0,"diensttage":0}' }));
const seite = await kontext.newPage();
const konsole = [];
seite.on('requestfailed', r => {
  const u = r.url();
  if (KACHELFEHLER.test(u)) { kachelGescheitert.push(u.slice(0, 80)); }
});
seite.on('console', m => {
  if (m.type() !== 'error') { return; }
  const t = m.text();
  if (KACHELFEHLER.test(t)) { return; }
  if (OHNE_ADRESSE.test(t.trim()) && kachelGescheitert.length > 0) { return; }
  konsole.push(t);
});
seite.on('pageerror', e => konsole.push('pageerror: ' + e.message));

const befunde = [];
let n = 0;
const pruefe = (ok, t) => { n++; if (!ok) befunde.push(t); };
const melde = (t) => console.log('  ' + t);
const ergebnis = { basis, schritte, pruefungen: 0, befunde, konsolenfehler: konsole };

/* DIE MENGENBREMSE DES DEMO-KONTOS IST KEIN BEFUND.
 *
 * Zwanzig Anmeldungen je Stunde und Adresse. Dieses Skript meldet
 * sich in fünf Abschnitten bis zu achtmal an — zwei Läufe hintereinander
 * reichen also, und beim dritten steht auf der Anmeldeseite:
 *
 *   „Das Demo-Konto wird gerade sehr häufig genutzt und ist vorübergehend
 *    gesperrt — wieder ab 21:55 Uhr."
 *
 * Früher las das niemand. `anmelden()` gab `false` zurück, die Erwartung
 * meldete „Anmeldung als Demo gescheitert", und der Abschnitt lief auf einer
 * Anmeldeseite weiter, bis irgendein Locator in eine Zeitgrenze rannte. Aus
 * einer wirksamen Schutzmassnahme wurde so ein Stapel roter Zeilen.
 *
 * Dieselbe Falle hat `umstellungslauf.mjs` bekommen und dort
 * dieselbe Lösung: Der Grund wird gelesen und der Abschnitt als **nicht
 * gemessen** gezählt — nicht als erfüllt und nicht als verfehlt. */
let nichtGemessen = 0;
let bremseBis = null;

async function anmelden(mail, pw) {
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.waitForTimeout(600);
  await seite.goto(`${basis}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', mail);
  await seite.fill('input[name="password"]', pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('#loginform button[type="submit"]'),
  ]);
  /* DER ZWEITFAKTOR. Die Administration dieses Laufs ist
   * das Prüfkonto, eine BetreiberIn mit Zweitfaktor; nach dem Passwort fragt
   * `login.php` nach dem Code. `codeSchritt()` geht ihn. Das Einrichtungstor
   * meldet er als Scheitern — seine Adresse enthält `login.php` nicht, und
   * der Riegel darunter läse sonst das Tor statt der Kontoliste. Der Grund
   * wird gesagt, weil er auf der Seite nicht steht. */
  const zf = await codeSchritt(seite);
  if (!zf.ok) {
    melde(`Anmeldung als ${mail}: ${zf.meldung}`);
    return false;
  }
  if (seite.url().includes('login.php')) {
    const t = await seite.locator('body').innerText().catch(() => '');
    const m = t.match(/vorübergehend gesperrt[^.]*?(\d{1,2}:\d{2})/i);
    if (m) { bremseBis = m[1]; }
  }
  return !seite.url().includes('login.php');
}

/** Anmelden und dabei die Mengenbremse von einem echten Fehlschlag trennen.
 *
 *  Liefert `true`, wenn der Abschnitt laufen darf. Bei aktiver Bremse zählt
 *  sie die übersprungenen Erwartungen mit und liefert `false` — OHNE einen
 *  Befund zu erzeugen, denn es ist keiner. */
async function anmeldenOderBremse(mail, pw, erwartungen, was) {
  bremseBis = null;
  if (await anmelden(mail, pw)) { return true; }
  if (bremseBis) {
    nichtGemessen += erwartungen;
    melde(`${was}: NICHT GEMESSEN — die Mengenbremse des Demo-Kontos greift `
        + `(wieder ab ${bremseBis} Uhr, E-P1-20). ${erwartungen} Erwartungen `
        + `übersprungen. Das ist kein Befund: Die Bremse tut, was sie soll.`);
    return false;
  }
  pruefe(false, `Anmeldung als ${mail} gescheitert (${was})`);
  return false;
}

async function rueckfragen(hoechstens = 3) {
  for (let i = 0; i < hoechstens; i++) {
    const ja = seite.locator('dialog[open] button[data-act="yes"]');
    try { await ja.first().waitFor({ state: 'visible', timeout: 5000 }); } catch { break; }
    await ja.first().click(); await seite.waitForTimeout(400);
  }
}

/** Kennzahlen der Adminseite auslesen. */
async function zustand() {
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  /* ZWEI TRAEGER STATT EINER TABELLE. Frueher stand
     der ganze Zustand in einer `table.data`; heute baut die Seite auf den
     Bausteinen auf, und die Zahlen des Bestands sind jetzt Kacheln
     (`.kennzahl`: Wert oben, Beschriftung darunter), die uebrigen Angaben
     Zeilen (`.zeile`: Beschriftung oben, Wert darunter). Beide liefern zwei
     Zeilen Text — nur in umgekehrter Reihenfolge, und getrennt durch einen
     Umbruch statt durch einen Tabulator. */
  /* GETEILT WIRD AN EINEM ODER MEHREREN UMBRUECHEN.
   *
   * `innerText` liefert fuer eine Kennzahlkachel heute `"83\n\nEinsätze"` —
   * zwei Umbrueche, weil Wert und Beschriftung zwei `<p>` sind. Geteilt wurde
   * hier an EINEM: Das zweite Stueck war die leere Zeichenkette, der
   * Schluessel damit leer, und `if (k)` weiter unten warf das Paar weg.
   *
   * Die Folge war keine rote Zeile, sondern etwas Schlimmeres: `zustand()`
   * lieferte die vier Kennzahlen (Diensttage, Einsätze, Ruhesegmente,
   * Geräte) GAR NICHT mehr, und jede Erwartung darauf verglich `undefined`
   * gegen '83'. Der Bericht meldete „Einsätze nach Reset: undefined" — was
   * wie ein Fehler der Anwendung aussieht und keiner war.
   *
   * `split(/\n+/)` statt `split('\n')`: Es traegt beide Formen, die alte wie
   * die heutige. Eine Probe, die am Abstand zweier Absaetze zerbricht, misst
   * die Gestaltung mit, und das soll sie nicht. */
  const paare = [];
  for (const t of await seite.locator('.kennzahl').allInnerTexts().catch(() => [])) {
    const [v, k] = t.split(/\n+/);
    paare.push([k, v]);
  }
  for (const t of await seite.locator('.zeile').allInnerTexts().catch(() => [])) {
    const [k, v] = t.split(/\n+/);
    paare.push([k, v]);
  }
  const aus = {};
  for (const p of paare) {
    const [k, v] = p.map(x => (x || '').trim());
    /* SCHLÜSSEL KLEINGESCHRIEBEN. Die Beschriftungen stehen per CSS in
       Versalien, und innerText liefert den GERENDERTEN Text — „EINSÄTZE",
       nicht „Einsätze". Ein Vergleich auf die Schreibweise im Markup fand
       hier nichts und meldete dreimal „undefined". Dieselbe Falle wie bei
       der Diagnose-Beschriftung in `angriffswerte.mjs`. */
    if (k) { aus[k.toLowerCase()] = v; }
  }
  /* LEER IST EIN ERGEBNIS, KEIN ZUSTAND. Steht kein Zustand auf der Seite,
     gibt es kein Demo-Konto — oder die Handlung ist gescheitert und die
     Seite trägt eine Fehlermeldung. Ohne diesen Zweig meldete die Prüfung
     fünfmal „undefined" und verschwieg den Grund, der daneben stand. */
  if (Object.keys(aus).length === 0) {
    const t = await seite.locator('body').innerText();
    const meldung = (t.match(/^.*(?:Fehler|fehlgeschlagen|nicht|bereits).*$/mi) || [''])[0];
    aus['__leer'] = meldung.trim().slice(0, 200) || 'kein Demo-Konto, keine Meldung';
  }
  return aus;
}

/* ---- RIEGEL: LAEUFT DAS HIER GEGEN DAS RICHTIGE KONTO? ------------------
 *
 * DIESES SKRIPT IST GEFAEHRLICH, und zwar an einer Stelle, die man ihm nicht
 * ansieht. Schritt 3 loescht einen Einsatz, Schritt 5 versucht die
 * E-Mail-Adresse zu aendern — beides in dem Konto, das unter `demo` erreichbar
 * ist. Beim Demo-Konto ist das folgenlos (der Reset holt alles zurueck, und
 * die Aenderung wird abgewiesen). Bei JEDEM ANDEREN Konto derselben Adresse
 * ist es keins von beidem.
 *
 * Genau das ist auf der Referenzinstallation passiert: Dort traegt das
 * REFERENZKONTO die Adresse demo@gen-em.org, und das Skript hat sie in
 * `gekapert@example.org` geaendert und einen Einsatz geloescht. Der Befund
 * „E-Mail-Aenderung wurde NICHT abgewiesen" stand danach im Bericht — richtig
 * gemeldet, aber zu spaet: Der Schaden war schon angerichtet, und der
 * Referenzstand musste neu aufgebaut werden.
 *
 * Der Riegel prueft VOR allem anderen: Gibt es ein Konto mit dieser Adresse,
 * das NICHT das Demo-Konto ist? Dann bricht der Lauf ab, ohne etwas zu
 * beruehren. Eine Pruefung, die ihren Pruefling zerstoeren kann, braucht eine
 * Grenze, die nicht davon abhaengt, dass die Bedienerin aufpasst. */
{
  /* ER MUSS NACH INNEN SCHLIESSEN, NICHT NACH AUSSEN.
   *
   * Die erste Fassung dieses Riegels versagte OFFEN: Sie meldete eine
   * gescheiterte Admin-Anmeldung ueber pruefe() — das notiert nur und laeuft
   * weiter — und las danach eine Anmeldeseite statt der Kontoliste. Darin
   * steht die Demo-Adresse nicht, also war `kontoDa` falsch, also griff der
   * Riegel nicht, also lief genau der Lauf durch, gegen den er geschrieben
   * wurde. Ein Riegel, der bei Unklarheit durchlaesst, ist keiner.
   *
   * Jetzt gilt: Wer nicht POSITIV feststellen kann, dass hier nichts
   * kaputtgeht, bricht ab. */
  const abbruch = (grund) => {
    console.error(`\nABBRUCH. ${grund}\n`
      + `Dieses Skript würde im Konto ${demo} einen Einsatz löschen und die\n`
      + `E-Mail-Adresse ändern. Es wurde nichts angefasst. Für die\n`
      + `Demo-Abnahme eine eigene Installation verwenden (oder das Konto\n`
      + `vorher im Adminbereich entfernen).`);
    return browser.close().then(() => process.exit(2));
  };

  if (!await anmelden(admin, adminPw)) {
    await abbruch(`Die Anmeldung als Administration (${admin}) auf ${basis} ist\n`
      + `gescheitert. Ohne sie lässt sich nicht feststellen, wem die Adresse\n`
      + `${demo} auf dieser Installation gehört.`);
  }
  /* zustand() statt eigener Textsuche: Es kennt die Falle mit den Versalien
     (die Beschriftungen stehen per CSS in Grossbuchstaben). */
  const alsDemo = ((await zustand())['konto'] || '') === demo;

  await seite.goto(`${basis}/admin_users.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(500);
  const liste = await seite.locator('body').innerText();
  /* NACHWEIS, DASS DIE LISTE WIRKLICH GELESEN WURDE: Die eigene Adresse der
     Administration steht immer darin. Fehlt sie, ist das keine Kontoliste —
     dann ist die Abwesenheit der Demo-Adresse nichts wert. */
  if (!liste.includes(admin)) {
    await abbruch(`Die Kontoliste auf ${basis} liess sich nicht lesen (die eigene\n`
      + `Adresse der Administration steht nicht darin). Ohne sie ist nicht\n`
      + `feststellbar, wem ${demo} gehört.`);
  }
  if (liste.includes(demo) && !alsDemo) {
    await abbruch(`Auf ${basis} gibt es ein Konto ${demo}, das NICHT als\n`
      + `Demo-Konto gekennzeichnet ist — vermutlich das Referenzkonto.`);
  }
}

// ---- 1. Anlegen ---------------------------------------------------------
if (schritte.includes('anlegen')) {
  pruefe(await anmelden(admin, adminPw), 'Anmeldung als Administration gescheitert');
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  /* DEN KNOPF GIBT ES NUR, WENN ES NOCH KEIN DEMO-KONTO GIBT.
   *
   * `admin_demo.php` zeigt entweder „Demo-Konto anlegen" ODER
   * „Zurücksetzen" — nie beides. Die Erwartung stand hier unbedingt und
   * meldete auf jeder Installation MIT Demo-Konto einen Befund, der keiner
   * ist. Gegriffen wird am `form`-Attribut statt am Text (dieselbe Falle wie
   * beim Reset-Knopf), und ist er nicht da, wird nachgesehen, WARUM: Steht
   * ein Demo-Konto auf der Seite, ist das die Antwort, und der Schritt zählt
   * als nicht gemessen statt als verfehlt. */
  const knopf = seite.locator('button[form="f-demo-anlegen"]');
  const schonDa = ((await zustand())['konto'] || '') !== '';
  if (await knopf.count() === 0 && schonDa) {
    nichtGemessen += 1;
    melde('Abschnitt 1 (anlegen): NICHT GEMESSEN — es gibt bereits ein '
        + 'Demo-Konto, der Knopf steht dann nicht auf der Seite. Die Zahlen '
        + 'darunter werden trotzdem geprüft (sie gelten für den Sollstand).');
  } else {
    pruefe(await knopf.count() > 0,
           'Knopf zum Anlegen fehlt (gesucht: button[form="f-demo-anlegen"])');
  }
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(500);
  if (await knopf.count() > 0) {
    await knopf.first().click();
    await seite.waitForTimeout(1000);
    for (let i = 0; i < 120; i++) {
      if (!/Demo-Konto anlegen/.test(await seite.locator('body').innerText())) break;
      await seite.waitForTimeout(2000);
    }
  }
  const z = await zustand();
  melde(`angelegt: ${JSON.stringify(z)}`);
  ergebnis.nach_anlegen = z;
  pruefe(!z.__leer, `Anlegen ohne Wirkung: ${z.__leer}`);
  for (const [k, v] of Object.entries(SOLL)) {
    pruefe((z[k] || '') === v, `${k} nach Anlegen: ${z[k]}, Soll aus der Fixture ${v}`);
  }
  /* ZWEI, nicht drei. Die Fixture fuehrt drei Eintraege,
     einer davon ist das virtuelle Geraet "Manuelle Einträge"; es wird nicht
     mehr eingespielt (demo_lib.php), und die Adminansicht zaehlt es auch
     nicht mehr mit (GERAETE_ECHT_SQL). Beides zusammen ergibt die Zahl, die
     auch die Geraeteliste zeigt. */
  pruefe((z['geräte'] || '') === '2', `Geräte: ${z['geräte']}`);
  await seite.screenshot({ path: `${ordner}/01-angelegt.png` });
}

// ---- 2. Als Demo anmelden, geschützte Angaben lesen ---------------------
if (schritte.includes('lesen')) {
  if (await anmeldenOderBremse(demo, demoPw, 5, 'Abschnitt 2 (lesen)')) {
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(3000);
  const text = await seite.locator('body').innerText();
  pruefe(/Demo-Konto\.?\s/i.test(text), 'Kein Demo-Banner auf der Übersicht');
  pruefe(/frei\s*erfunden/i.test(text), 'Banner nennt die Daten nicht als erfunden');
  pruefe(/30\s*Minuten/i.test(text), 'Banner nennt das Reset-Fenster nicht');
  const zeilen = await seite.locator('#missions tbody tr').count();
  pruefe(zeilen > 0, 'Keine Einsatzzeilen im Demo-Konto');
  // Geschützte Angaben: nur lesbar, wenn das Schlüsselmaterial passt
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  await seite.waitForTimeout(2500);
  /* DAS SCHLOSS IST EIN SVG, KEIN EMOJI.
   *
   * Hier stand `/diagnose\s*🔒\s*\n(.+)/i` — die Beschriftung, dann das
   * Schloss-Emoji, dann der Wert. `dtGeschuetzt()` in `einsatz.php` setzt das
   * Schloss heute über `edSymbol('schloss', …)` als `<svg>`, und `innerText`
   * liefert dafür NICHTS. Der Ausdruck fand nie etwas, und die Probe meldete
   * „Schlüsselmaterial passt nicht zum Chiffretext" — an einer Stelle, an der
   * der Klartext gut lesbar dasteht. Eine Probe, die eine Gestaltungsfrage
   * für eine Krypto-Aussage hält, sendet den nächsten Leser in die falsche
   * Richtung.
   *
   * Der Text wird jetzt am Zeilenpaar gelesen, das Schloss GETRENNT am
   * Markup geprüft — das ist ohnehin die schärfere Messung: `CLAUDE.md` 4
   * verlangt das Schloss an jedem verschlüsselten Feld, und bis hierher hat
   * das an dieser Stelle niemand nachgesehen. */
  const t2 = await seite.locator('body').innerText();
  const wert = t2.match(/(?:^|\n)\s*Diagnose\s*\n(.+)/i);
  pruefe(!!(wert && wert[1].trim().length > 3),
         'Diagnose nicht lesbar — Schlüsselmaterial passt nicht zum Chiffretext');
  const schloss = await seite.locator('.symbol-schutz').count();
  pruefe(schloss > 0,
         `Kein Schloss an den geschützten Feldern (CLAUDE.md 4) — gezählt: ${schloss}`);
  melde(`Diagnose gelesen: ${wert ? wert[1].trim().slice(0, 60) : '—'} `
      + `· ${schloss} Schlösser`);
  ergebnis.diagnose = wert ? wert[1].trim() : null;
  await seite.screenshot({ path: `${ordner}/02-demo-einsatz.png` });
  }
}

// ---- 3. Absichtlich verändern ------------------------------------------
if (schritte.includes('veraendern')) {
  if (await anmeldenOderBremse(demo, demoPw, 0, 'Abschnitt 3 (verändern)')) {
  // Einen Einsatz löschen
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(2500);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  const id = new URL(seite.url()).searchParams.get('id');
  await seite.goto(`${basis}/einsatz_loeschen.php?id=${id}`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(600);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
    seite.click('button[type="submit"], button.btn-red'),
  ]);
  await seite.waitForTimeout(800);
  melde(`Einsatz ${id} gelöscht`);
  // Ein Stammdatum ändern
  await seite.goto(`${basis}/einstellungen.php?t=standorte`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1200);
  const feld = seite.locator('input[name="name"]').first();
  if (await feld.count() > 0) {
    await feld.fill('VERÄNDERT durch Prüfung');
    await seite.locator('button').filter({ hasText: /Speichern|Anlegen|Hinzufügen/ }).first()
      .click().catch(() => {});
    await seite.waitForTimeout(1000);
    melde('Standort angelegt/geändert');
  }
  ergebnis.veraendert = { geloeschter_einsatz: id };
  await seite.screenshot({ path: `${ordner}/03-veraendert.png` });
  }
}

// ---- S. Die Spurfälle (Nr. 500, Konzept RD) ------------------------------
/* GESUCHT WIRD UEBER DEN INHALT (`api/day.php`), nicht ueber Kennungen —
 * wie im Bilderlauf. Was der Bestand nicht traegt, ist ein Befund: Die
 * Fixture soll jeden Fall haben (Nr. 500). */
if (schritte.includes('spurfaelle')) {
  if (await anmeldenOderBremse(demo, demoPw, 0, 'Abschnitt S (Spurfälle)')) {
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1500);
  const api = (pfad) => seite.evaluate(async ([b, p]) => {
    const r = await fetch(b + '/' + p, { credentials: 'same-origin' });
    return r.ok ? r.json() : null;
  }, [basis, pfad]);
  const tage = ((await api('api/day.php')) || {}).days || [];
  const stufe = (st) => Math.max(1, ...(st || []).map((x) => x.stufe || 1));
  const inhalt = {};
  for (const t of tage) { inhalt[t.id] = await api('api/day.php?d=' + t.id); }
  const segmente = [], einsaetze = [];
  for (const t of tage) {
    for (const r of (inhalt[t.id] || {}).rest_segments || []) { segmente.push({ tag: t.id, r }); }
    for (const m of (inhalt[t.id] || {}).missions || []) { einsaetze.push({ tag: t.id, m }); }
  }
  const segStufe = (x) => stufe(x.r.spur_stuecke), misStufe = (x) => stufe(x.m.spur_stuecke);
  const zahl = (f) => [segmente.filter((x) => segStufe(x) === f).length,
                       einsaetze.filter((x) => misStufe(x) === f).length];
  melde(`Spuren je Stufe (Segmente/Einsätze): 2 → ${zahl(2)}, 3 → ${zahl(3)}`);
  pruefe(zahl(2)[0] > 0 && zahl(2)[1] > 0 && zahl(3)[0] > 0 && zahl(3)[1] > 0,
         `Die Demo trägt nicht jede Stufe an Segment und Einsatz: 2 → ${zahl(2)}, 3 → ${zahl(3)}`);

  /* Jede Stufe zeichnet: der Tag eines Stufe-3-Segments, die Einsatzseite
     eines Stufe-3-Einsatzes mit Reanimation, wenn es ihn gibt. */
  const linien = async (pfad) => {
    await seite.goto(`${basis}/${pfad}`, { waitUntil: 'domcontentloaded' });
    await seite.waitForTimeout(3500);
    return seite.locator('.leaflet-overlay-pane path').count();
  };
  const s3 = segmente.find((x) => segStufe(x) === 3);
  if (s3) { const k = await linien(`index.php?d=${s3.tag}`); pruefe(k > 0, `Tag mit Stufe-3-Segment ohne Linie (${k})`); melde(`Tag mit Stufe-3-Segment: ${k} Linien`); }
  const e3 = einsaetze.find((x) => misStufe(x) === 3 && x.m.rea_n > 0) || einsaetze.find((x) => misStufe(x) === 3);
  if (e3) { const k = await linien(`einsatz.php?id=${e3.m.id}`); pruefe(k > 0, `Einsatz ${e3.m.id} (Stufe 3) ohne Linie (${k})`); melde(`Einsatz ${e3.m.id} auf Stufe 3: ${k} Linien`); }

  /* Schneiden und Zuruecknehmen am Uebungssegment der Stufe 2 (P-S11-30):
     ein Segment der Stufe 2 ohne Schnitt, mindestens 60 Minuten lang. */
  const ueb = segmente.find((x) => segStufe(x) === 2 && !(x.r.schnitte || []).length
                                && x.r.bis_ts - x.r.von_ts >= 3600);
  pruefe(!!ueb, 'Kein Übungssegment der Stufe 2 ohne Schnitt im Bestand');
  if (ueb) {
    await seite.goto(`${basis}/index.php?d=${ueb.tag}`, { waitUntil: 'domcontentloaded' });
    const zeile = seite.locator(`[data-ruhezeile="${ueb.r.id}"]`);
    await zeile.waitFor({ state: 'visible', timeout: 15000 });
    await zeile.locator('.zeile-knoepfe [data-schnitt-auf]').click();
    const bereich = seite.locator(`[data-schnitt-fuer="${ueb.r.id}"]`);
    await bereich.waitFor({ state: 'visible', timeout: 5000 });
    const [h, mi] = (await bereich.locator('input[id^="s-beg-"]').inputValue()).split(':').map(Number);
    const plus = (d) => { const x = (h * 60 + mi + d) % 1440; return String(Math.floor(x / 60)).padStart(2, '0') + ':' + String(x % 60).padStart(2, '0'); };
    await bereich.locator('input[id^="s-beg-"]').fill(plus(20));
    await bereich.locator('input[id^="s-end-"]').fill(plus(50));
    await bereich.locator('input[id^="s-end-"]').dispatchEvent('input');
    await seite.waitForTimeout(400);
    await bereich.locator('[data-schneiden]').click();
    await seite.waitForTimeout(800);
    await rueckfragen(1);
    await seite.locator('[data-schnittmeldung]').waitFor({ timeout: 20000 }).catch(() => {});
    const m1 = await seite.locator('[data-schnittmeldung]').innerText().catch(() => '');
    pruefe(/Der Einsatz ist entstanden/.test(m1), `Schneiden am Übungssegment (Stufe 2): „${m1.slice(0, 120)}"`);
    melde(`Schnitt am Übungssegment: ${m1.slice(0, 80)}`);
    const zurueck = seite.locator(`[data-ruhezeile="${ueb.r.id}"] .zeile-knoepfe [data-rueck]`);
    if (await zurueck.count()) {
      await zurueck.first().click();
      await rueckfragen(1);
      await seite.waitForTimeout(2500);
      const m2 = await seite.locator('[data-schnittmeldung]').innerText().catch(() => '');
      pruefe(/zurückgenommen/.test(m2), `Zurücknehmen am Übungssegment: „${m2.slice(0, 120)}"`);
      melde(`Zurücknehmen am Übungssegment: ${m2.slice(0, 60)}`);
    } else { pruefe(false, 'Nach dem Schnitt kein „Schnitt zurücknehmen" an der Zeile'); }
  }

  /* Zuruecknehmen: Schnitt auf Stufe 1 geht (F-RD-14), auf Stufe 2 nicht
     (F-RD-22, 409 verdichtet). */
  for (const [st, soll] of [[2, /zusammengefasst/], [1, /zurückgenommen/]]) {
    const x = segmente.find((y) => segStufe(y) === st && (y.r.schnitte || []).some((c) => c.mission_id != null)
                                 && y.r.bis_ts != null && y.r.bis_ts - y.r.von_ts > 3600);
    pruefe(!!x, `Kein Schnitt in einem Segment der Stufe ${st} im Bestand`);
    if (!x) { continue; }
    await seite.goto(`${basis}/index.php?d=${x.tag}`, { waitUntil: 'domcontentloaded' });
    const z = seite.locator(`[data-ruhezeile="${x.r.id}"] .zeile-knoepfe [data-rueck]`);
    await z.first().waitFor({ state: 'visible', timeout: 15000 });
    const vorher = konsole.length;
    await z.first().click();
    await rueckfragen(1);
    await seite.waitForTimeout(2500);
    /* DIE ABGELEHNTE RUECKNAHME ANTWORTET 409, und der Browser schreibt jede
       4xx-Antwort als „Failed to load resource" in die Konsole. Genau diese
       eine, angekuendigte Antwort zaehlt nicht als Fehler — jede andere neue
       Meldung bleibt stehen. */
    if (st === 2) {
      const neu = konsole.splice(vorher);
      konsole.push(...neu.filter((k) => !/status of 409/.test(k)));
    }
    const t = await seite.locator('[data-schnittmeldung]').innerText().catch(() => '');
    pruefe(soll.test(t), `Zurücknehmen eines Schnitts auf Stufe ${st}: „${t.slice(0, 120)}"`);
    melde(`Zurücknehmen auf Stufe ${st}: ${t.slice(0, 70)}`);
  }

  /* Zeitraum loeschen (endgueltig, mit Rueckfrage) an einem Segment der
     Stufe 1 ohne Schnitt, mindestens 30 Minuten lang: zehn Minuten nach dem
     Beginn, zehn Minuten lang. Der Reset danach holt die Punkte zurueck. */
  const loe = segmente.find((x) => segStufe(x) === 1 && !(x.r.schnitte || []).length
                                && x.r.n > 20 && x.r.bis_ts - x.r.von_ts >= 1800);
  pruefe(!!loe, 'Kein Segment der Stufe 1 zum Löschen eines Zeitraums');
  if (loe) {
    await seite.goto(`${basis}/index.php?d=${loe.tag}`, { waitUntil: 'domcontentloaded' });
    const zeile = seite.locator(`[data-ruhezeile="${loe.r.id}"]`);
    await zeile.waitFor({ state: 'visible', timeout: 15000 });
    await zeile.locator('.zeile-knoepfe [data-schnitt-auf]').click();
    const bereich = seite.locator(`[data-schnitt-fuer="${loe.r.id}"]`);
    await bereich.waitFor({ state: 'visible', timeout: 5000 });
    const [h, mi] = (await bereich.locator('input[id^="s-beg-"]').inputValue()).split(':').map(Number);
    const plus = (d) => { const x = (h * 60 + mi + d) % 1440; return String(Math.floor(x / 60)).padStart(2, '0') + ':' + String(x % 60).padStart(2, '0'); };
    await bereich.locator('input[id^="s-beg-"]').fill(plus(10));
    await bereich.locator('input[id^="s-end-"]').fill(plus(20));
    await bereich.locator('input[id^="s-end-"]').dispatchEvent('input');
    await seite.waitForTimeout(400);
    await bereich.locator('[data-zeitraum-loeschen]').click();
    await rueckfragen(1);
    await seite.waitForTimeout(2500);
    const t = await seite.locator('[data-schnittmeldung]').innerText().catch(() => '');
    pruefe(/Gelöscht/.test(t), `Zeitraum löschen: „${t.slice(0, 120)}"`);
    melde(`Zeitraum löschen: ${t.slice(0, 70)}`);
  }

  /* Der Wartungslauf gibt dem Demo-Konto nichts (Nr. 459) — gefragt am
     ECHTEN Endpunkt; `?roh` umgeht den Platzhalter dieses Skripts. */
  const w = await seite.evaluate(async () => {
    const r = await EdApi.postJson('api/wartungslauf.php?roh=1', { aktion: 'liste' });
    return { ok: r.ok, n: ((r.daten || {}).spuren || []).length };
  }).catch((e) => ({ ok: false, n: -1, e: String(e) }));
  pruefe(w.ok && w.n === 0, `Wartungslauf nennt dem Demo-Konto ${w.n} Spuren`);
  melde(`Wartungslauf für das Demo-Konto: ${w.n} Spuren`);
  ergebnis.spurfaelle = { stufe2: zahl(2), stufe3: zahl(3), wartungslauf: w.n };
  await seite.screenshot({ path: `${ordner}/04-spurfaelle.png` });
  }
}

// ---- 4. Zurücksetzen ----------------------------------------------------
if (schritte.includes('reset')) {
  pruefe(await anmelden(admin, adminPw), 'Anmeldung als Administration gescheitert');
  await seite.goto(`${basis}/admin_demo.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(700);
  const vorher = await zustand();
  melde(`vor dem Reset: ${JSON.stringify(vorher)}`);
  ergebnis.vor_reset = vorher;
  /* DER KNOPF HEISST „Zurücksetzen", NICHT „Auf Standard zurücksetzen".
   *
   * Hier stand der lange Text, und `hasText` fand nichts. Die Erwartung
   * meldete daraufhin richtig „Knopf fehlt" — und der Zweig darunter wurde
   * ÜBERSPRUNGEN. Der Lauf setzte also nie zurück, verglich danach die
   * unveränderten Zahlen gegen den Sollstand und meldete drei weitere rote
   * Zeilen. Sichtbar war das nur daran, dass der Papierkorb des Demo-Kontos
   * mit jedem Lauf um einen Eintrag wuchs.
   *
   * Gegriffen wird jetzt am `form`-Attribut (`f-demo-reset`) und nicht am
   * Text: Der Knopf steht ausserhalb seines Formulars und ist darüber
   * eindeutig; eine Beschriftung ist es nicht. Die Textsuche bleibt als
   * zweiter Weg stehen, falls das Attribut einmal wandert. */
  const knopf = seite.locator('button[form="f-demo-reset"]')
    .or(seite.locator('button', { hasText: /^\s*Zurücksetzen\s*$/ }));
  pruefe(await knopf.count() > 0,
         'Knopf zum Zurücksetzen fehlt (gesucht: button[form="f-demo-reset"])');
  if (await knopf.count() > 0) {
    await knopf.first().click();
    await rueckfragen();
    /* GEWARTET WIRD AUF DIE ZAHL, NICHT AUF EIN WORT. Der alte Lauf wartete
     * auf „zurückgesetzt" im Seitentext; steht die Meldung einmal anders da,
     * wartet er zwei Minuten und misst danach trotzdem. Die Marke „letzter
     * Reset" ist die Angabe, die sich durch die Handlung ZWINGEND ändert. */
    const markeVorher = vorher['letzter reset'] || '';
    for (let i = 0; i < 60; i++) {
      const t = await seite.locator('body').innerText();
      if (/zurückgesetzt|Fehler|konnte nicht/i.test(t)) { break; }
      await seite.waitForTimeout(1000);
      if (i % 3 === 2) {
        const z = await zustand();
        if ((z['letzter reset'] || '') !== markeVorher) { break; }
      }
    }
  }
  const nachher = await zustand();
  melde(`nach dem Reset: ${JSON.stringify(nachher)}`);
  ergebnis.nach_reset = nachher;
  for (const [k, v] of Object.entries(SOLL)) {
    pruefe((nachher[k] || '') === v, `${k} nach Reset: ${nachher[k]}, Soll aus der Fixture ${v}`);
  }
  pruefe((nachher['einsätze im papierkorb'] || '') === '5',
         `Papierkorb nach Reset: ${nachher['einsätze im papierkorb']}`);
  pruefe((nachher['geräte'] || '') === '2', `Geräte nach Reset: ${nachher['geräte']}`);
  // Nach dem Reset müssen die geschützten Angaben WEITER lesbar sein
  if (await anmeldenOderBremse(demo, demoPw, 1, 'Abschnitt 4 (nach dem Reset)')) {
  await seite.goto(`${basis}/index.php`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(3000);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.locator('#missions tbody tr').first().click(),
  ]);
  await seite.waitForTimeout(2500);
  const t3 = await seite.locator('body').innerText();
  const w3 = t3.match(/(?:^|\n)\s*Diagnose\s*\n(.+)/i);
  pruefe(!!(w3 && w3[1].trim().length > 3),
         'Nach dem Reset sind die geschützten Angaben NICHT lesbar');
  await seite.screenshot({ path: `${ordner}/04-nach-reset.png` });
  }
}

// ---- 5. Konto-Identität gesperrt ---------------------------------------
if (schritte.includes('sperren')) {
  if (await anmeldenOderBremse(demo, demoPw, 4, 'Abschnitt 5 (sperren)')) {
  await seite.goto(`${basis}/einstellungen.php?t=profil`, { waitUntil: 'domcontentloaded' });
  await seite.waitForTimeout(1200);
  /* DAS FORMULAR HEISST `pfform`, UND SEIN KNOPF STEHT DARIN.
   *
   * Hier stand `seite.locator('form').filter({ has: … }).locator('button')`
   * — „irgendein Knopf in irgendeinem Formular, das ein E-Mail-Feld
   * enthält". Das trifft auf der Anmeldeseite genauso zu wie im Profil, und
   * genau dort landete der Lauf, sobald die Mengenbremse die Anmeldung
   * abgewiesen hatte. Er klickte dann dreissig Sekunden lang ins Leere und
   * starb an einer Zeitgrenze, statt den Grund zu nennen.
   *
   * Gegriffen wird jetzt am `id` des Profilformulars. Fehlt es, ist das ein
   * eigener Befund und kein Zeitablauf — eine Probe soll sagen, was sie
   * nicht gefunden hat.
   *
   * ZWEI FELDER, NICHT EINS: Wer die E-Mail-Adresse ändert, muss das
   * aktuelle Passwort dazuschreiben (`old` im selben Formular). Ohne das
   * hätte die Abweisung einen zweiten möglichen Grund, und die Erwartung
   * bewiese nicht, was sie behauptet. */
  const pfForm = seite.locator('#pfform');
  pruefe(await pfForm.count() > 0,
         'Profilformular #pfform nicht gefunden — Abschnitt 5 misst nichts');
  const mailFeld = pfForm.locator('input[name="email"]').first();
  pruefe(await mailFeld.count() > 0,
         'Kein E-Mail-Feld im Profilformular — die Sperre wäre unbelegt');
  if (await mailFeld.count() > 0) {
    await mailFeld.fill('gekapert@example.org');
    const altFeld = pfForm.locator('input[name="old"]').first();
    if (await altFeld.count() > 0) { await altFeld.fill(demoPw); }
    await seite.locator('button', { hasText: /Profil speichern/ }).first().click();
    await seite.waitForTimeout(2000);
  }
  const t = await seite.locator('body').innerText();
  pruefe(/lassen sich E-Mail-Adresse und Passwort nicht ändern/i.test(t),
         'E-Mail-Änderung wurde NICHT abgewiesen');
  /* UND SIE STEHT AUCH WIRKLICH NOCH DA. Eine Meldung ist eine Meldung; die
   * Zusage ist, dass die Adresse unverändert bleibt. Das Feld trägt nach dem
   * Absenden wieder den alten Wert — gemessen, nicht angenommen. */
  pruefe((await mailFeld.inputValue().catch(() => '')) === demo,
         'Die E-Mail-Adresse im Formular ist nach der Abweisung nicht mehr die alte');
  /* DAS CSRF-TOKEN STEHT ALS `const`, NICHT AUF `window`.
   *
   * `ui_krypto_bootstrap()` schreibt `const CSRF = …` in ein klassisches
   * Skript; eine Deklaration mit `const` landet NICHT am `window`-Objekt.
   * `window.CSRF` war also immer `undefined`, der Meta-Anhänger existiert
   * nicht, und der Endpunkt antwortete folgerichtig `403 {"error":"csrf"}`.
   *
   * Gemessen wurde damit die CSRF-Sperre — nicht das, was hier gemeint war:
   * dass `api/kdf_upgrade.php` das Demo-Konto ÜBERSPRINGT.
   * Das ist die tragende Zusage an dieser Stelle: Das Demo-Konto
   * bekommt keinen Server-Anteil, seine Hülle bleibt `edk1:`, und die stille
   * Umstellung darf sie nicht anfassen.
   *
   * Aus dem `melde` ist deshalb eine ERWARTUNG geworden. Eine Zeile, die nur
   * ins Protokoll schreibt, fällt nicht auf, wenn sie das Falsche misst. */
  const st = await seite.evaluate(async () => {
    const c = (typeof CSRF !== 'undefined') ? CSRF
            : (document.querySelector('input[name=csrf]')?.value || '');
    const r = await fetch('api/kdf_upgrade.php', { method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': c },
      body: '{}' });
    return { status: r.status, text: (await r.text()).slice(0, 200) };
  }).catch(e => ({ status: 0, text: String(e) }));
  melde(`kdf_upgrade: ${st.status} ${st.text}`);
  ergebnis.kdf_upgrade = st;
  pruefe(st.status === 200 && /"uebersprungen"\s*:\s*"demo"/.test(st.text),
         `api/kdf_upgrade.php überspringt das Demo-Konto NICHT: `
       + `${st.status} ${st.text}`);
  // Passwort-Reset für die Demo-Adresse
  await seite.goto(`${basis}/logout.php`, { waitUntil: 'domcontentloaded' }).catch(() => {});
  await seite.goto(`${basis}/reset_request.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', demo);
  await seite.click('form:not([data-ankuendigung-weg]) button[type="submit"]');
  await seite.waitForTimeout(1500);
  ergebnis.reset_request = (await seite.locator('body').innerText()).slice(0, 200);
  await seite.screenshot({ path: `${ordner}/05-sperren.png` });
  }
}

ergebnis.pruefungen = n;
ergebnis.nicht_gemessen = nichtGemessen;
ergebnis.kachelfehler = kachelGescheitert.length;
writeFileSync(`${ordner}/ergebnis.json`, JSON.stringify(ergebnis, null, 2) + '\n');
console.log(`\nEinzelprüfungen: ${n}`);
console.log(`Konsolenfehler:  ${konsole.length}`
  + (kachelGescheitert.length
     ? `  (dazu ${kachelGescheitert.length} gescheiterte Kartenkacheln — der `
       + `Prüfstand hat keinen Weg ins Netz, sie zählen nicht mit)` : ''));
/* NICHT GEMESSEN IST NICHT ERFÜLLT. Die Zahl steht in der Schlusszeile und
 * nicht nur im Protokoll — sonst liest sich ein Lauf, der die Hälfte
 * übersprungen hat, wie ein vollständiger. */
if (nichtGemessen > 0) {
  console.log(`NICHT GEMESSEN:  ${nichtGemessen} — der Grund steht oben bei dem `
            + `Abschnitt, der übersprungen wurde.`);
}
console.log(befunde.length ? `BEFUNDE (${befunde.length})\n  ` + befunde.join('\n  ')
                           : 'Keine Befunde.');
await browser.close();
process.exit(befunde.length === 0 && konsole.length === 0 ? 0 : 1);
