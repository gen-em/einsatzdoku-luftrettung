/* Bildaufnahme aller Seiten in acht Breiten (P3, Anlage F).
 * ===========================================================================
 *
 * WOFUER. P3 baut die Oberfläche neu, und die Frage nach jedem Arbeitspaket
 * ist immer dieselbe: Sieht die Seite in ALLEN Breiten so aus, wie sie soll —
 * und ist unterwegs nichts verlorengegangen? Diese Frage beantwortet kein
 * Kaskadenvergleich; sie beantwortet nur ein Bild.
 *
 * Der Stilvergleich (tools/stilvergleich/) misst berechnete Werte und ist auf
 * „nichts hat sich geändert" gebaut. In einem beabsichtigten Redesign liefert
 * er Tausende Abweichungen, die niemand gegen einen Plan hält. Er ruht während
 * P3 und wird in O12 neu geeicht. Dieses Werkzeug tritt an seine Stelle — mit
 * einer anderen Frage: nicht „was hat sich geändert?", sondern „stimmt es?"
 *
 * WAS ES MISST (und damit belegt, statt zu behaupten):
 *   - waagerechter Überlauf   scrollWidth > innerWidth  je Seite und Breite
 *   - Konsolenfehler          je Seite und Breite
 *   - Knopfhöhen              ZWEI Sollwerte seit Web 15.5.0 (E-S8-09, R76):
 *                             44 px am Fingergerät und unter 1024 px,
 *                             36 px am Zeigergerät ab 1024 px. Benannte
 *                             Ausnahme: der Filterknopf neben dem
 *                             48-px-Suchfeld der Suche (O6)
 *   - Kontraste der Token     aus dem Stylesheet gerechnet (P-P3-05)
 *
 * AUFRUF
 *   sh tools/referenzdatensatz/einspielen/lokal_starten.sh    (einmal)
 *   node tools/screenshots/aufnehmen.mjs
 *   node tools/screenshots/aufnehmen.mjs --nur 10-,12-        (Teilmenge)
 *   node tools/screenshots/aufnehmen.mjs --klein              (1x statt 2x)
 *   node tools/screenshots/aufnehmen.mjs --finger             (Fingergerät)
 *
 * ZEIGER ODER FINGER. Ohne `--finger` laeuft der Browser als Zeigergeraet
 * (`hasTouch:false`) — das ist der Regelfall an einem Bildschirm ab 1024 px
 * und damit das, was die Bilder zeigen sollen. Mit `--finger` laeuft
 * derselbe Lauf als Fingergeraet; dort gelten ueberall 44 px. Das Konzept S8
 * beschrieb es andersherum (Finger als Regel, Zeiger als Zugabe); gedreht
 * wurde es, weil ein Bildschirm ab 1024 px in aller Regel eine Maus hat und
 * die Bilder den Regelfall zeigen sollen. Beide Laeufe messen, nur der
 * Sollwert unterscheidet sich.
 *
 * AUSGABE unter tools/screenshots/ausgabe/ (steht in .gitignore):
 *   einzeln/<seite>-<breite>.png      die Einzelbilder
 *   texte/<seite>-<breite>.txt        der SICHTBARE Text derselben Seite
 *                                     (document.body.innerText) — die Grundlage
 *                                     des Textvergleichs in vergleichen.py
 *   bogen/<seite>.png                 der Kontaktbogen, acht Breiten nebeneinander
 *   bericht.md, bericht.json          Zahlen und Befunde
 *
 * GRENZEN. Gemessen wird Chromium. WebKit (Safari, iOS) und Gecko (Firefox)
 * stehen in dieser Umgebung nicht zur Verfügung; was nur dort auffiele, fällt
 * hier nicht auf. Bedienzustände sind nur so weit erfasst, wie die Seitenliste
 * sie als `vorher`-Schritte führt.
 */
import { mkdirSync, writeFileSync, readFileSync, rmSync, existsSync } from 'node:fs';
import { spawnSync } from 'node:child_process';
import { dirname, join } from 'node:path';
import { fileURLToPath } from 'node:url';
import { createHash } from 'node:crypto';

/* Hinter einem HTTPS_PROXY (etwa in der Claude-Umgebung) braucht Nodes
 * eingebautes fetch die Variable NODE_USE_ENV_PROXY — und die wird nur beim
 * Prozessstart gelesen. Nachtraeglich gesetzt gehen die Abrufe am Proxy
 * vorbei und laufen in die Egress-Sperre. Deshalb startet sich das Skript
 * einmal selbst neu, wenn die Variable fehlt. Ohne Proxy: kein Neustart. */
if ((process.env.HTTPS_PROXY || process.env.https_proxy) && !process.env.NODE_USE_ENV_PROXY) {
  const { spawnSync } = await import('node:child_process');
  const kind = spawnSync(process.execPath, process.argv.slice(1), {
    stdio: 'inherit', env: { ...process.env, NODE_USE_ENV_PROXY: '1' },
  });
  process.exit(kind.status ?? 1);
}

const MODUL = process.env.PLAYWRIGHT_MODUL
  || '/opt/node22/lib/node_modules/playwright/index.mjs';
const PW = await import(MODUL.startsWith('/') ? 'file://' + MODUL : MODUL);
const { motorWahl, starten } = await import(
  new URL('../motor.mjs', import.meta.url).href);

const HIER   = dirname(fileURLToPath(import.meta.url));
const WURZEL = join(HIER, '..', '..');
const AUSGABE = join(HIER, 'ausgabe');

const argv = process.argv.slice(2);
const flag = (n) => argv.includes(n);
const wert = (n, s) => { const i = argv.indexOf(n); return i >= 0 ? argv[i + 1] : s; };

const BASIS  = wert('--basis', 'https://127.0.0.1:8443');
const DEMO   = { email: wert('--demo', 'demo@gen-em.org'),  pw: wert('--demo-pw', 'nadokudemo0815') };
const ADMIN  = { email: wert('--admin', 'admin@gen-em.org'), pw: wert('--admin-pw', 'pruefstandzugang2026') };
const SKALA  = flag('--klein') ? 1 : 2;
const FINGER = flag('--finger');
const FILTER = (wert('--nur', '') || '').split(',').filter(Boolean);
const MOTOR  = motorWahl(argv);

/* Fuer den Wartungsmodus einer FERNEN Installation — siehe den Block bei
 * `wartungAn()`. Ohne Token bleibt es beim lokalen Weg. */
const JOBS_TOKEN = wert('--jobs-token', '');

/* DAS UMGEBUNGSETIKETT (P5c/AP1, E-P5c-05). Mit `--etikett Staging` misst
 * der Lauf auf JEDER Seite, ob der Titel mit „[Staging] " beginnt und ob die
 * Kopfleiste — wo es eine gibt — `kopf-umgebung` traegt. Ohne den Schalter
 * misst er die Gegenrichtung: kein Vorsatz, keine rote Leiste. Anlass: Die
 * Abnahme von AP1 verlangt „Praefix auf allen Seiten, Kopfleiste rot auf
 * allen Seiten mit Kopfleiste" — ein Bild zeigt die Farbe, aber keines den
 * Seitentitel, und 58 Seiten von Hand aufzurufen ist keine Messung.
 * Die Lage selbst stellt der Aufrufer her (`app.umgebung` in `config.php`);
 * dieser Lauf schreibt nichts. */
const ETIKETT = wert('--etikett', '');

/* UNBEKANNTE SCHALTER SIND EIN FEHLER, KEIN SCHWEIGEN (16.09.2026).
 *
 * `wert()` sucht sich seine Kennzeichnung aus argv und laesst alles andere
 * liegen. Das ist bequem und war jahrelang folgenlos — bis die
 * Auslieferungskette diesen Lauf mit `--konto`/`--passwort` aufrief, die es
 * hier nie gab. Beide wurden STILL verworfen; der Lauf nahm die eingebauten
 * Vorgaben, meldete sich mit `demo@gen-em.org` bei STAGING an und scheiterte
 * eine Ebene spaeter mit "Anmeldung gescheitert" — einer Meldung, die den
 * wahren Grund nicht nennt und an der falschen Stelle suchen laesst.
 *
 * Die Pruefung kostet zehn Zeilen und faengt jeden kuenftigen Tippfehler an
 * der Stelle, an der er entsteht. `--motor` und sein Wert stehen in der
 * Liste, weil motorWahl() sie aus demselben argv liest. */
const BEKANNT = new Set(['--basis', '--demo', '--demo-pw', '--admin', '--admin-pw',
                         '--klein', '--finger', '--nur', '--stufe', '--selbstprobe',
                         '--motor', '--jobs-token', '--etikett']);
const MIT_WERT = new Set(['--basis', '--demo', '--demo-pw', '--admin', '--admin-pw',
                          '--nur', '--motor', '--jobs-token', '--stufe', '--etikett']);
for (let i = 0; i < argv.length; i++) {
  const a = argv[i];
  if (!a.startsWith('--')) continue;
  if (!BEKANNT.has(a)) {
    console.error(`Unbekannter Schalter: ${a}`);
    console.error(`Bekannt sind: ${[...BEKANNT].join(' ')}`);
    process.exit(2);
  }
  if (MIT_WERT.has(a)) i++;          // den Wert ueberspringen
}

/* Acht Breiten, je mit einer realistischen Höhe. Die Höhe entscheidet nur
 * darüber, wie viel ohne Scrollen sichtbar ist — aufgenommen wird die ganze
 * Seite; sie steuert aber, was `position:sticky` und `100vh` tun. */
const ALLE_BREITEN = [
  { b:  360, h:  800, art: 'Handy'   },
  { b:  390, h:  844, art: 'Handy'   },
  { b:  420, h:  900, art: 'Handy'   },
  { b:  768, h: 1024, art: 'Tablet'  },
  { b: 1024, h:  768, art: 'Tablet'  },
  { b: 1280, h:  900, art: 'Desktop' },
  { b: 1440, h:  900, art: 'Desktop' },
  { b: 1920, h: 1080, art: 'Desktop' },
];

/* ---- Die drei Stufen (--stufe, E-PK-14) ---------------------------------
 *
 * klein  berührte Seiten (`--nur`), DREI Breiten, Chromium
 * neben  alle Seiten, acht Breiten, Chromium
 * haupt  alle Seiten, acht Breiten, ALLE DREI Engines
 *
 * DIE RISIKOLISTE IST WEG, und das ist eine Entscheidung mit Preis. Sie
 * nannte zehn Seiten mit Container-Abfragen, `:has()`, `dvh` und `sticky`
 * und ließ Firefox und WebKit nur diese fahren — eine von Hand gepflegte
 * Liste, die in eine Richtung altert: Wer ein solches Merkmal neu einbaut,
 * muss daran denken. Der EINZIGE WebKit-Fund des Projekts (Nr. 185) lag auf
 * einer Seite, die NICHT darauf stand. Dreißig Minuten bei einer Hauptstufe
 * sind kein Preis, der eine Liste rechtfertigt, die das Gesuchte verfehlt.
 *
 * Ohne `--stufe` bleibt es bei allen acht Breiten — so lief das Werkzeug
 * vor PK-04, und die Kette ruft es weiter so. */
const STUFE = wert('--stufe', '');
if (STUFE && !['klein', 'neben', 'haupt'].includes(STUFE)) {
  console.error(`Unbekannte Stufe: ${STUFE} (klein, neben, haupt)`);
  process.exit(2);
}
const BREITEN = STUFE === 'klein'
  ? ALLE_BREITEN.filter(x => [360, 1024, 1920].includes(x.b))
  : ALLE_BREITEN;

const { seiten } = JSON.parse(readFileSync(join(HIER, 'seiten.json'), 'utf-8'));

const liste = (() => {
  // WELCHE SEITEN. `--nur` filtert nach Namensanfang; ohne Filter alle.
  // Die Risikoliste und `--risiko` sind mit E-PK-14 entfallen (Begründung
  // oben bei den Stufen).
  return FILTER.length
    ? seiten.filter(s => FILTER.some(f => s.name.startsWith(f)))
    : seiten;
})();

/* DER RIEGEL GEGEN EIN STILLES DURCHLAUFEN (Backlog Nr. 220).
 *
 * Ein Eintrag mit `"wartung": true` braucht gegen eine FERNE Installation ein
 * `--jobs-token` — die lokale Datei wirkt dort nicht. Ohne den Riegel liefe
 * der Lauf weiter und legte acht Bilder der ANMELDESEITE ab, mit acht
 * Konsolenfehlern; genau so ist es am 17.09.2026 passiert. Eine Zahl, die
 * dabei entsteht, misst nicht die Wartungsseite, sondern das Misslingen.
 *
 * Geprueft wird die GEFILTERTE Liste: `--nur 05-datenschutz` gegen ein fernes
 * Staging braucht kein Token, weil dort kein solcher Eintrag steht. */
const OERTLICH = /^https?:\/\/(127\.0\.0\.1|localhost|\[::1\])([:/]|$)/i.test(BASIS);
const WARTUNGSEITEN = liste.filter(s => s.wartung).map(s => s.name);
/* NICHT ABBRECHEN, SONDERN DIESE SEITEN AUSFALLEN LASSEN. Ein Abbruch waere
 * die bequemere Zeile und die schlechtere: Von fuenfzig Seiten haengen zwei am
 * Wartungsmodus, und die anderen achtundvierzig sind messbar. Der Lauf misst
 * also, was er messen kann, nennt die zwei beim Namen und wird am Ende
 * trotzdem ROT — `ausgefallen` geht in den Rueckgabewert. Gesagt wird es
 * ausserdem hier, VOR den zwoelf Minuten Laufzeit. */
const OHNE_WARTUNGSWEG = (WARTUNGSEITEN.length && !OERTLICH && !JOBS_TOKEN)
  ? `kein Weg zum Wartungsmodus auf ${BASIS} — --jobs-token fehlt (Nr. 220)`
  : null;
if (OHNE_WARTUNGSWEG) {
  console.error(`WARNUNG: ${WARTUNGSEITEN.join(', ')} brauchen den `
    + `Wartungsmodus, und ${BASIS} liegt nicht auf diesem Rechner — die Datei `
    + `server/wartung.lock wirkt dort nicht. Diese Seiten FALLEN AUS; der `
    + `Lauf misst die uebrigen und endet rot. Mit --jobs-token <Token> `
    + `(Betrieb → Hintergrundjobs) werden sie gemessen.`);
}

/* WAS NICHT ALS KONSOLENFEHLER ZAEHLT — und warum die Unterscheidung noetig
 * ist: Ein Bericht, der jede rote Zeile meldet, wird nach zwei Laeufen
 * weggeklickt, und dann geht der echte Fehler mit unter.
 *
 * 1  Kartenkacheln und Ortssuche sind bewusste Laufzeitquellen (map_layers.js
 *    und ortsfeld.js nennen Herkunft und Lizenz). Ein gescheiterter Abruf sagt
 *    ueber die Anwendung nichts. Erkannt am NAMEN der Quelle, gleich mit
 *    welchem Fehler sie scheitert.
 * 2  Der Statuscode der SEITE SELBST. Die Abbruchseite antwortet mit 404 oder
 *    409 — das ist ihre Aufgabe, nicht ihr Fehler. Chromium meldet trotzdem
 *    "Failed to load resource". Erkannt wird das daran, dass die Fundstelle
 *    der Meldung die Seitenadresse selbst ist.
 * 3  Verbindungsfehler auf einer FREMDEN Adresse. Hier liegt der Grund, warum
 *    es diese dritte Klasse ueberhaupt gibt: Der Pruef-Browser kommt in dieser
 *    Umgebung nicht an die Kachelserver, und die Egress-Sperre setzt die
 *    Verbindung zurueck — der Fehlercode steht dann in der Meldung, ohne dass
 *    der Gastgebername ueberall mitkaeme (Erklaerung im Absatz zu
 *    kachelAntwort() weiter unten).
 *
 * ---- WARUM KLASSE 3 AN DIE FUNDSTELLE GEBUNDEN IST (Backlog Nr. 176) -------
 *
 * Bis Runde 3 stand Klasse 3 in DEMSELBEN Muster wie Klasse 1, und geprueft
 * wurde es gegen den Meldungstext. Damit fiel jeder Abruf auf dem EIGENEN
 * Server unter das Kartenrauschen, sobald er mit einem der Codes scheiterte:
 * ERR_CONNECTION_RESET, ERR_CONNECTION_CLOSED und ERR_ABORTED. Der Bericht
 * konnte "0 Konsolenfehler" melden fuer eine Seite, auf der das Stylesheet
 * nicht angekommen ist.
 *
 * GEMESSEN, in zwei Schritten. Der Fund: von fuenf gebauten Faellen mit
 * lokaler Fundstelle verschluckte das alte Muster DREI (die drei Codes) und
 * zaehlte zwei (ERR_CONNECTION_REFUSED und einen HTTP-Status). Der Nachweis:
 * die ERSTEN ZEHN Faelle der Selbstprobe unten, woertlich durch die alte
 * Funktion geschickt
 * (`git show origin/main:… | sed -n '/^const KACHELRAUSCHEN =/,/^}/p'`)
 * — SECHS von zehn richtig, vier falsch: die drei lokalen Abbrueche und der
 * Fall ohne Fundstelle. Dieselben zehn durch die neue Funktion: zehn von zehn;
 * die Probe hat seit der Gegenpruefung FUENFZEHN Faelle und meldet 15 von 15.
 * Und am laufenden Browser, mit angehaltenem PHP-Server: zwei Konsolenfehler
 * auf der eigenen Basis, davon verwarf der alte Filter EINEN, der neue KEINEN.
 *
 * Seither gilt Klasse 3 nur, wenn die Fundstelle NICHT die eigene Basis ist.
 * Die Hostliste (Klasse 1) ist unveraendert; ein Kachelabruf traegt seinen
 * Gastgeber in der Fundstelle und faellt weiter heraus.
 *
 * ZWEI ENTSCHEIDUNGEN DABEI, beide bewusst zur lauten Seite hin:
 *
 *   - OHNE ZUORDENBARE FUNDSTELLE WIRD GEZAEHLT. Eine Meldung ohne
 *     `location().url`, mit einer Fundstelle, die keine Adresse ist
 *     (`<anonymous>`), oder mit undurchsichtiger Herkunft (`data:`, `blob:`)
 *     laesst sich nicht zuordnen. Lieber eine Zeile zu viel im Bericht als
 *     eine stille Luecke; wer sie erklaert, erklaert sie mit Zahl.
 *   - DER CODE WIRD NUR IM TEXT GESUCHT, nicht mehr auch in der Fundstelle.
 *     Ein Fehlercode kommt in keiner URL vor; die zweite Suche war ohne
 *     Wirkung und verdeckte nur, worauf es ankommt.
 *
 * Was diese Klasse WEITERHIN verschweigt: einen Verbindungsfehler auf einer
 * fremden Adresse, die gar nicht abgerufen werden duerfte. Hier stand zuerst,
 * das messe tools/quelltext/ (vollstaendigkeit) — das war FALSCH, als es hier stand:
 * Dessen Gruppe 5 kannte zwei Zusagen, und kein Werkzeug zaehlte "keine fremde
 * Quelle zur Laufzeit" nach. Seit dem 14.09.2026 tut es das (Backlog Nr. 179,
 * Pruefung `fremde Quelle`) — aber AM QUELLTEXT, nicht zur Laufzeit: Es
 * meldet jede absolute Adresse in eigenem Code gegen eine Ausnahmeliste mit
 * Grund. Was zur Laufzeit dazukommt, sieht weiterhin niemand; eine
 * Content-Security-Policy schickt die Anwendung nicht (Backlog Nr. 181).
 *
 * SELBSTPROBE: `node tools/screenshots/aufnehmen.mjs --selbstprobe` haelt
 * diese Funktion gegen fuenfzehn gebaute Faelle und nennt die Zahl. Sie laeuft
 * ohne Browser und ohne Server und loescht die Ausgabe des letzten Laufs
 * nicht.
 *
 * JEDE DER DREI KLASSEN TRAEGT MINDESTENS EINEN FALL — und das ist nicht
 * selbstverstaendlich, sondern der zweite Anlauf. Der erste Entwurf hatte zehn
 * Faelle und meldete 10 von 10 AUCH DANN, wenn man Klasse 1 oder Klasse 3
 * loeschte: Alle verwerfenden Faelle trugen einen Kachelgastgeber in der URL,
 * also fing sie Klasse 1 — und fiel die weg, fing sie Klasse 3. Die Probe
 * belegte damit die Richtung, um die es ging, ueberhaupt nicht. Die drei
 * Faelle, die das aufloesen, sind Nr. 11 bis 13; Nr. 14 und 15 halten den
 * Grundsatz "nicht zuordenbar wird gezaehlt"; die Gegenprobe dazu steht in
 * der LIESMICH ("Traegt jede Klasse einen Fall?") und wird von Hand gefahren:
 * Wer eine Klassenzeile loescht, muss die Probe rot sehen.
 */
const FREMDE_QUELLEN =
  /tile\.|openstreetmap|opentopomap|arcgisonline|photon\.komoot/i;
const VERBINDUNGSCODES =
  /ERR_TUNNEL_CONNECTION_FAILED|ERR_NAME_NOT_RESOLVED|ERR_INTERNET_DISCONNECTED|ERR_CONNECTION_RESET|ERR_CONNECTION_CLOSED|ERR_ABORTED/i;

/* DREI Antworten, nicht zwei: 'eigen', 'fremd' oder 'keine'.
 *
 * Verglichen wird ueber `URL.origin`, nicht ueber startsWith: das vertraegt
 * einen abschliessenden Schraegstrich in --basis und rechnet Vorgabeports mit.
 *
 * WARUM DREI. Der erste Entwurf hatte ein `istEigeneHerkunft()`, das bei einer
 * Fundstelle wie `<anonymous>` `false` lieferte — Klasse 3 las das als "fremd"
 * und verwarf die Meldung. Damit wurde eine LEERE Fundstelle gezaehlt und eine
 * UNLESBARE verworfen, genau entgegen dem Grundsatz zwei Absaetze weiter oben.
 * 'keine' fasst beide Faelle zusammen und wird gezaehlt. Dazu gehoert auch die
 * undurchsichtige Herkunft (`data:`, `blob:`): `URL.origin` liefert dort die
 * Zeichenkette "null", und die ist keine Auskunft. */
function herkunft(ort) {
  if (!ort) return 'keine';
  let o;
  try {
    o = new URL(ort).origin;
  } catch {
    return 'keine';
  }
  if (!o || o === 'null') return 'keine';
  return o === new URL(BASIS).origin ? 'eigen' : 'fremd';
}

function istRauschen(meldung, seitenAdresse) {
  const text = meldung.text();
  const ort = (meldung.location && meldung.location().url) || '';
  // 1 Fremde Laufzeitquellen, am Namen erkannt.
  if (FREMDE_QUELLEN.test(text) || FREMDE_QUELLEN.test(ort)) return true;
  // 2 Der Statuscode der Seite selbst — und WIRKLICH nur ein Statuscode.
  if (ort && seitenAdresse && ort.split('#')[0] === seitenAdresse.split('#')[0]
      && !VERBINDUNGSCODES.test(text)) return true;
  // 3 Verbindungsfehler — aber nur auf einer NACHWEISBAR fremden Fundstelle
  //   (Nr. 176). 'keine' zaehlt, siehe herkunft().
  if (VERBINDUNGSCODES.test(text) && herkunft(ort) === 'fremd') return true;
  return false;
}

/* ---- Selbstprobe der Rauschunterscheidung -------------------------------
 *
 * Zehn Faelle, je mit Sollwert. Der Punkt der Sache sind die Faelle 3 bis 5:
 * Sie sind der Grund fuer Nr. 176 und waren am alten Muster stumm. Fall 10
 * haelt die Gegenrichtung fest — ein Kachelabruf bleibt Rauschen. */
const SELBSTPROBE = [
  { nr:  1, soll: 'rauschen', grund: 'Kachelserver, Verbindung zurueckgesetzt',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  'https://tile.openstreetmap.org/12/2200/1400.png' },
  { nr:  2, soll: 'rauschen', grund: 'Ortssuche, Verbindung zurueckgesetzt',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  'https://photon.komoot.io/api/?q=Tal' },
  { nr:  3, soll: 'fehler',   grund: 'eigener Server, Symbol zurueckgesetzt (Nr. 176)',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  'EIGEN/assets/images/symbole/haus.svg?v=19.3.1' },
  { nr:  4, soll: 'fehler',   grund: 'eigener Server, Stylesheet abgeschnitten (Nr. 176)',
    text: 'Failed to load resource: net::ERR_CONNECTION_CLOSED',
    ort:  'EIGEN/assets/style.css' },
  { nr:  5, soll: 'fehler',   grund: 'eigener Server, Abruf abgeraeumt (Nr. 176)',
    text: 'Failed to load resource: net::ERR_ABORTED',
    ort:  'EIGEN/api/day.php?d=32' },
  { nr:  6, soll: 'fehler',   grund: 'eigener Server, Verbindung abgelehnt',
    text: 'Failed to load resource: net::ERR_CONNECTION_REFUSED',
    ort:  'EIGEN/assets/symbol.js' },
  { nr:  7, soll: 'fehler',   grund: 'eigener Server, HTTP 500',
    text: 'Failed to load resource: the server responded with a status of 500 (Internal Server Error)',
    ort:  'EIGEN/api/day.php?d=32' },
  { nr:  8, soll: 'rauschen', grund: 'die Seite selbst antwortet mit 404 (Abbruchseite)',
    text: 'Failed to load resource: the server responded with a status of 404 (Not Found)',
    ort:  'SEITE' },
  { nr:  9, soll: 'fehler',   grund: 'Verbindungsfehler ohne Fundstelle — wird gezaehlt',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  '' },
  { nr: 10, soll: 'rauschen', grund: 'Unterbereich eines Kachelservers',
    text: 'Failed to load resource: net::ERR_CONNECTION_CLOSED',
    ort:  'https://a.tile.openstreetmap.org/12/2200/1400.png' },
  /* 11 bis 13 halten je EINE Klasse. Ohne sie bliebe die Probe gruen, wenn man
   * eine Klassenzeile loescht — siehe den Kopfkommentar. */
  { nr: 11, soll: 'rauschen', grund: 'NUR Klasse 1: Kachelserver ohne Verbindungscode (HTTP 500)',
    text: 'Failed to load resource: the server responded with a status of 500 (Internal Server Error)',
    ort:  'https://tile.openstreetmap.org/12/2200/1400.png' },
  { nr: 12, soll: 'rauschen', grund: 'NUR Klasse 3: fremde Herkunft ausserhalb der Gastgeberliste',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  'https://beispiel.example/irgendwas.js' },
  { nr: 13, soll: 'fehler',   grund: 'NUR die Schranke an Klasse 2: die Seite selbst verliert die Verbindung',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  'SEITE' },
  /* 14 und 15: eine Fundstelle, die sich nicht zuordnen laesst, wird GEZAEHLT
   * — wie die leere in Fall 9. Der erste Entwurf verwarf sie. */
  { nr: 14, soll: 'fehler',   grund: 'Fundstelle ist keine Adresse (<anonymous>)',
    text: 'Failed to load resource: net::ERR_CONNECTION_RESET',
    ort:  '<anonymous>' },
  { nr: 15, soll: 'fehler',   grund: 'undurchsichtige Herkunft (data:) — URL.origin sagt "null"',
    text: 'Failed to load resource: net::ERR_CONNECTION_CLOSED',
    ort:  'data:text/html,<p>x' },
];

if (flag('--selbstprobe')) {
  const seitenAdresse = BASIS + '/einsatz.php?id=1';
  let erfuellt = 0;
  console.log('Selbstprobe istRauschen() — Basis ' + BASIS + '\n');
  for (const f of SELBSTPROBE) {
    const ort = f.ort === 'SEITE' ? seitenAdresse : f.ort.replace('EIGEN', BASIS);
    const meldung = { text: () => f.text, location: () => ({ url: ort }) };
    const ist = istRauschen(meldung, seitenAdresse) ? 'rauschen' : 'fehler';
    const ok = ist === f.soll;
    if (ok) erfuellt++;
    console.log(`${ok ? ' ok ' : 'FEHL'}  ${String(f.nr).padStart(2)}  `
      + `soll ${f.soll.padEnd(8)} ist ${ist.padEnd(8)}  ${f.grund}`);
  }
  console.log(`\n${erfuellt} von ${SELBSTPROBE.length} Faellen erwartungsgemaess, `
    + `${SELBSTPROBE.length - erfuellt} nicht.`);
  process.exit(erfuellt === SELBSTPROBE.length ? 0 : 1);
}

rmSync(AUSGABE, { recursive: true, force: true });
mkdirSync(join(AUSGABE, 'einzeln'), { recursive: true });
mkdirSync(join(AUSGABE, 'bogen'), { recursive: true });
mkdirSync(join(AUSGABE, 'texte'), { recursive: true });

const browser = await starten(PW, MOTOR, { finger: FINGER });

/* Kartenkacheln liefert NODE, nicht der Browser (Fund aus O3).
 *
 * Der Pruef-Browser kommt in der Claude-Umgebung nicht an tile.openstreetmap.org:
 * Direktverbindungen setzt die Egress-Sperre zurueck, und auch mit
 * --proxy-server bricht der TLS-Handschlag nach dem CONNECT ab
 * (ERR_CONNECTION_RESET; per NetLog belegt, unabhaengig von TLS-Version und
 * Post-Quantum-Merkmalen — der Weg ist fuer diesen Browser schlicht zu).
 * Nodes fetch kommt durch den Umgebungsproxy dagegen zuverlaessig an (siehe
 * Neustart-Weiche oben). Also faengt eine Playwright-Route die Kachelabrufe ab
 * und beantwortet sie aus einem Node-Abruf — mit Lager je URL, damit 232
 * Aufnahmen die Kachelserver nicht 232-fach fragen. Nebeneffekt: Die Bilder
 * werden deterministischer, und ohne Proxy (lokaler Rechner) funktioniert
 * derselbe Weg unveraendert direkt. */
const kachelLager = new Map();
async function kachelAntwort(route) {
  const url = route.request().url();
  try {
    if (!kachelLager.has(url)) {
      const a = await fetch(url, { headers: { 'User-Agent': 'einsatzdoku-pruefwerkzeug/1.0' } });
      kachelLager.set(url, {
        status: a.status,
        ct: a.headers.get('content-type') || 'image/png',
        body: Buffer.from(await a.arrayBuffer()),
      });
    }
    const k = kachelLager.get(url);
    await route.fulfill({ status: k.status, contentType: k.ct, body: k.body });
  } catch {
    await route.abort('failed');
  }
}
const KACHELMUSTER = [
  'https://tile.openstreetmap.org/**',
  'https://*.tile.openstreetmap.org/**',
];

/* EINE Seite je Rolle, nicht eine je Aufnahme.
 *
 * Der Inhaltsschluessel liegt nach der Anmeldung im sessionStorage — und der
 * ist an die REGISTERKARTE gebunden, nicht an den Browserkontext. Der erste
 * Entwurf oeffnete je Aufnahme eine neue Seite; jede davon startete mit
 * leerem sessionStorage, und auf jedem Bild stand der Entsperrdialog statt
 * des Inhalts. Genau die Angaben, um die es geht (Einsatzort, Diagnose,
 * Alter), waren auf keinem der 232 Bilder zu sehen.
 *
 * Also: anmelden, die Seite behalten, fuer jede Breite nur die Fenstergroesse
 * aendern. Das haelt den Schluessel und ist nebenbei erheblich schneller. */
/* Die eigentliche Anmeldung — als eigener Schritt, weil sie MITTEN IM LAUF
 * wiederholt werden muss (siehe `sitzungHalten`). */
async function anmeldenAuf(seite, rolle) {
  if (rolle === 'aus') { return true; }
  const konto = rolle === 'admin' ? ADMIN : DEMO;
  await seite.goto(`${BASIS}/login.php`, { waitUntil: 'domcontentloaded' });
  await seite.fill('input[name="email"]', konto.email);
  await seite.fill('input[name="password"]', konto.pw);
  await Promise.all([
    seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
    seite.click('#loginform button[type="submit"]'),
  ]);
  if (seite.url().includes('login.php')) { return false; }

  /* DAS EINWILLIGUNGSTOR DURCHKLICKEN (P5b/AP4, Web 20.19.0).
   *
   * Hat die Installation Nutzungsbedingungen oder eine Vereinbarung zur
   * Auftragsverarbeitung in Kraft, landet JEDES angemeldete Konto auf
   * `einwilligung.php`, bis es angenommen hat. Für den Bilderlauf heißt
   * das: Jede Aufnahme zeigt das Tor statt der Seite, die gemessen werden
   * soll.
   *
   * Gefunden am 16.09.2026, und der Lauf hat es selbst gemeldet — „OHNE
   * BILD: 32 Aufnahmen — 32× Seite leitete auf die Anmeldung um". Ohne
   * diesen Block stünde die nächste Instanz vor 52 Konsolenfehlern und
   * einer Zahl, die nichts misst.
   *
   * ES WIRD GEKLICKT UND NICHT ÜBERGANGEN: Das Tor ist echtes Verhalten der
   * Anwendung, kein Hindernis des Prüfstands. Ein Bilderlauf, der es
   * aushebelte, misste eine Anwendung, die es so nicht gibt. */
  if (seite.url().includes('einwilligung.php')) {
    /* HAEKCHEN, NICHT SCHALTER (seit Web 20.22.1). Bis dahin stand hier
     * `.schalter-box`; das Tor fuehrte Schiebeschalter, und die sind durch
     * gewoehnliche Kontrollkaestchen ersetzt worden — eine
     * Willenserklaerung kennt nur eine Richtung.
     *
     * DER AUSWAHLPFAD IST ABSICHTLICH ALLGEMEIN (`input[type=checkbox]`
     * innerhalb des Formulars) und nicht an einer Klasse aufgehaengt: Diese
     * Stelle ist beim Umbau umgefallen, weil sie eine Klasse kannte, die
     * die Anwendung geaendert hat. Der Fehler war gut zu sehen, weil die
     * Anmeldung seit Web 20.21.1 die Meldung der Seite mitgibt — ohne sie
     * stuende hier nur „Anmeldung gescheitert". */
    const boxen = await seite.locator('form input[type=checkbox]').count();
    for (let i = 0; i < boxen; i++) {
      await seite.locator('form input[type=checkbox]').nth(i)
                 .evaluate(el => { el.checked = true; });
    }
    await Promise.all([
      seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }).catch(() => {}),
      seite.click('form:not([data-ankuendigung-weg]) button[type="submit"]'),
    ]);
    if (seite.url().includes('einwilligung.php')) { return false; }
  }
  return true;
}

async function anmelden(rolle) {
  const kontext = await browser.newContext({
    ignoreHTTPSErrors: true, deviceScaleFactor: SKALA,
    viewport: { width: 1280, height: 900 },
    hasTouch: FINGER,
  });
  for (const muster of KACHELMUSTER) await kontext.route(muster, kachelAntwort);
  const seite = await kontext.newPage();
  if (!await anmeldenAuf(seite, rolle)) {
    const konto = rolle === 'admin' ? ADMIN : DEMO;
    /* DIE MELDUNG DER SEITE MITNEHMEN. „Anmeldung gescheitert" allein laesst
     * raten; der haeufigste Grund ist kein falsches Passwort, sondern der
     * Ratenschutz: Wer den Lauf mehrmals hintereinander startet, stolpert
     * ueber den Demo-Topf („vorübergehend gesperrt — wieder ab HH:MM").
     * Das ist richtiges Verhalten der Anwendung und kein Fehler des
     * Pruefstands — man muss es nur lesen koennen. */
    const grund = await seite.locator('.meldung, .hinweis, .warnung')
      .allTextContents().then(t => t.join(' · ').replace(/\s+/g, ' ').trim())
      .catch(() => '');
    throw new Error(`Anmeldung als ${konto.email} gescheitert`
      + (grund ? ` — die Seite sagt: ${grund.slice(0, 200)}` : ''));
  }

  /* Die Fehlersammlung haengt an der Seite und wird je Aufnahme geleert. */
  const fehler = [];
  let adresse = '';
  seite.on('console', m => {
    if (m.type() === 'error' && !istRauschen(m, adresse)) {
      const ort = (m.location && m.location().url) || '';
      fehler.push(m.text() + (ort ? '  [' + ort + ']' : ''));
    }
  });
  seite.on('pageerror', e => fehler.push('pageerror: ' + e.message));

  /* ---- Die Eingabeart haelt nicht von selbst (Fund aus S8/AP7) -----------
   *
   * `hasTouch` am Kontext setzt sie richtig — aber nur bis zum ersten
   * VOLLSEITEN-SCREENSHOT. Danach meldet der Browser wieder `hover: hover`
   * und `pointer: fine`, und ab 1024 px misst der Lauf 36 statt der
   * erwarteten 44 px. Gemessen im ersten `--finger`-Lauf: bei 360 px
   * `hover:false fine:false`, ab 390 px `true`/`true` — der Lauf meldete
   * daraufhin 28 „falsche" Knopfhoehen, die keine waren. Ein Pruefmittel,
   * das seine eigene Emulation verliert, misst etwas anderes, als es sagt.
   *
   * `Emulation.setEmulatedMedia` HILFT HIER NICHT: Es kennt `prefers-*` und
   * `forced-colors`, nicht `hover` und `pointer` — der Aufruf laeuft durch
   * und aendert nichts (gemessen). Was hilft, ist
   * `Emulation.setTouchEmulationEnabled`; die beiden Medienmerkmale folgen
   * daraus. Es wird deshalb vor JEDER Breite erneut gesendet. */
  /* NUR CHROMIUM HAT CDP. Seit AP3b faehrt der Lauf auch Firefox und WebKit
   * (Nr. 183); `newCDPSession` wirft dort. Der Aufruf haengt ohnehin am
   * Fingerlauf — der Zeigerlauf braucht ihn nicht (siehe unten). */
  const cdp = (FINGER && MOTOR === 'chromium') ? await kontext.newCDPSession(seite) : null;
  const eingabeart = async () => {
    /* NUR IM FINGERLAUF SENDEN. `setTouchEmulationEnabled {enabled:false}`
     * ist NICHT das Gegenteil von `{enabled:true}`: Gemessen an einem
     * Kontext mit `hasTouch:false` (also bereits Zeigergeraet) kippte der
     * Aufruf `hover` und `pointer` auf `none`/`coarse` — der Zeigerlauf mass
     * daraufhin 44 px, wo 36 stehen sollten, also denselben Fehler
     * spiegelverkehrt. Der Zeigerlauf braucht ihn ohnehin nicht: Sein
     * Zustand ist der Grundzustand des Browsers und geht nicht verloren. */
    if (!FINGER || !cdp) { return; }
    await cdp.send('Emulation.setTouchEmulationEnabled',
      { enabled: true, maxTouchPoints: 5 }).catch(() => {});
  };
  await eingabeart();

  return { kontext, seite, fehler, rolle, eingabeart, setzeAdresse: (a) => { adresse = a; } };
}

const rollen = { aus: await anmelden('aus'), demo: await anmelden('demo'), admin: await anmelden('admin') };

/* ---- Platzhalter auflösen -------------------------------------------------
 *
 * Die Seitenliste kann keine Kennungen enthalten: Sie gehört ins
 * Repositorium, die Kennungen gehören zu EINER Installation. Sie werden
 * deshalb zur Laufzeit aus dem Bestand geholt — über dieselben Wege, die eine
 * NutzerIn ginge. */
async function platzhalter() {
  const s = rollen.demo.seite;
  /* UEBER DIE SITZUNGSWACHE (Web 9.10.1). Diese Funktion laeuft als erste im
     Lauf und ist damit die erste, die einen faelligen Demo-Reset ausloest.
     Landete sie auf der Anmeldeseite, fand sie keine Einsatzzeile, lieferte
     ein leeres Verzeichnis — und alle vier Einsatzseiten wurden mit ihrem
     eigenen Platzhalter als Adresse aufgerufen. Der lokale Server antwortet
     darauf mit 200 und der Startseite; acht Bilder je Seite, alle falsch,
     kein Fehler. Genau die Falle aus F-P3-AH, eine Ebene tiefer. */
  await gehZu(rollen.demo, `${BASIS}/index.php`, 'index.php');

  /* Auf die ERSTE Einsatzzeile warten, nicht auf eine feste Zeit. Die
   * Tagesübersicht holt ihre Einsätze über api/day.php nach; eine Wartezeit
   * ist geraten, ein Selektor nicht. Der erste Anlauf wartete 1,5 s und lief
   * in einen Timeout beim Klick — die Tabelle war noch leer. */
  await s.waitForSelector('#missions tbody tr, .kachel', { timeout: 30000 }).catch(() => {});
  await s.waitForTimeout(300);

  /* Kennungen aus dem Seitenzustand lesen statt aus einem Klick: Ein Klick
   * prüft den Bedienweg, und der wird anderswo geprüft — hier soll er nur
   * eine Adresse liefern, und dabei darf er nicht die Aufnahme aufhalten. */
  const kennung = await s.evaluate(() => ({
    tag: (typeof currentDayId !== 'undefined' && currentDayId) || null,
    einsatz: (typeof dayMissions !== 'undefined' && dayMissions[0]) ? dayMissions[0].id : null,
  })).catch(() => ({ tag: null, einsatz: null }));

  let { tag, einsatz } = kennung;
  if (!einsatz) {                       // Rückfall: über den Klick
    await Promise.all([
      s.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 30000 }),
      s.locator('#missions tbody tr').first().click(),
    ]).catch(() => {});
    einsatz = new URL(s.url()).searchParams.get('id');
  }

  /* NULL STATT EINES RUECKFALLS. Bis Web 9.10.1 lieferte ein nicht
     aufloesbarer Platzhalter entweder 'index.php' (Tag-Seiten) oder gar
     keinen Eintrag (Einsatz-Seiten). Beides endete in einem Bild der
     falschen Seite unter dem richtigen Namen: einmal die Startseite als
     „Diensttag Datum aendern", einmal die Startseite als „Einsatzformular".
     Ein fehlender Wert ist jetzt ausdruecklich `null` und fuehrt dazu, dass
     die Seite NICHT fotografiert wird. */
  const p = {
    '__EINSATZ__':     einsatz ? `einsatz.php?id=${einsatz}`            : null,
    '__FORMULAR__':    einsatz ? `einsatz_form.php?id=${einsatz}`       : null,
    '__VERSCHIEBEN__': einsatz ? `einsatz_verschieben.php?id=${einsatz}`: null,
    '__LOESCHEN__':    einsatz ? `einsatz_loeschen.php?id=${einsatz}`   : null,
    '__TAG_DATUM__':    tag ? `diensttag_datum.php?d=${tag}`    : null,
    '__TAG_LOESCHEN__': tag ? `diensttag_loeschen.php?d=${tag}` : null,
    /* Die Zusammenfuehrung braucht ihren Zieltag genauso — sie stand bis O11
       OHNE Parameter in der Seitenliste und lieferte deshalb 404 mit der
       Abbruchseite. Acht Bilder, alle von der falschen Seite, und der Lauf
       meldete „kein Ueberlauf". Dieselbe Falle wie F-P3-AH und F-P3-AQ, ein
       drittes Mal. */
    '__TAG_ZUSAMMEN__': tag ? `diensttag_zusammenfuehren.php?d=${tag}` : null,
    '__TAG_SPUREN__':   tag ? `tag_spuren.php?d=${tag}`               : null,
  };

  /* DIE STANDORTSEITE BRAUCHT IHREN STANDORT (S9/AP5). Sie ersetzt den
     Reiter „Rettungsmittel", und dessen Adresse gibt es nicht mehr. Die
     Kennung kommt aus der Liste — ueber denselben Weg, den eine NutzerIn
     geht —, und ein nicht aufgeloester Platzhalter ist `null` und fuehrt
     dazu, dass die Seite NICHT fotografiert wird. Ein Ruecklauf auf die
     Liste ergaebe zwei Namen fuer dasselbe Bild; genau davor warnt der
     Kommentar darueber. */
  const d = rollen.demo.seite;
  await gehZu(rollen.demo, `${BASIS}/einstellungen.php?t=standorte`,
              'einstellungen.php?t=standorte');
  const sHref = await d.locator('a.zeile[href*="t=standort&s="]').first()
                       .getAttribute('href').catch(() => null);
  p['__STANDORT__'] = sHref || null;

  /* `__ADMIN_STANDORT__` STAND HIER BIS S9/AP5b. Der Platzhalter zeigte auf
     die Standortseite der systemweiten Stammdatenpflege; sie ist mit dem
     Modell gestrichen (R39). Er war ohnehin nie aufloesbar — der
     Referenzbestand hat keinen zentralen Standort, und die acht Aufnahmen
     fielen jedes Mal aus (F-S9-U-33). */
  const a = rollen.admin.seite;
  await gehZu(rollen.admin, `${BASIS}/admin_users.php`, 'admin_users.php');
  const href = await a.locator('a[href*="admin_user.php?id="]').first()
                      .getAttribute('href').catch(() => null);
  p['__KONTO__'] = href || null;

  /* ---- DREI SEITEN, DIE ES ERST SEIT DEM DEMO-AUSBAU GIBT ----------------
   *
   * Die Platzhalter darueber nehmen den ERSTEN Diensttag der Uebersicht und
   * seinen ersten Einsatz. Das ist fuer die meisten Seiten richtig — sie
   * sollen irgendeinen befuellten Tag zeigen — und fuer drei Faelle falsch:
   * Der erste Tag hat einen Standort, eine Spur und keine Winde, und genau
   * die drei Gegenteile sind seit dem Demo-Ausbau im Bestand.
   *
   *   __TAG_OHNE_STANDORT__  Tagesuebersicht eines Diensttags OHNE Standort
   *                          (D20) — kein Standortfeld, keine Rollen
   *   __EINSATZ_WINDE__      Einsatzansicht eines BODEN-Bergwachteinsatzes
   *                          mit Winde (D19) — die Windenkacheln, die es vor
   *                          Web 20.3.0 am Boden nicht geben konnte
   *   __TAG_LUFTLINIE__      Tageskarte eines Diensttags, dessen Einsaetze
   *                          Koordinaten, aber KEINE Spur haben (D21) — die
   *                          gestrichelten Luftlinien aus `start_src`,
   *                          `dest_lat`/`dest_lon` (E34/E35)
   *
   * GESUCHT WIRD UEBER DEN INHALT, NICHT UEBER EINEN NAMEN ODER EINE NUMMER.
   * Kennungen wandern bei jedem Neubau des Referenzbestands, und ein Name
   * („Boxkampf …") waere ein zweiter Ort, an dem die Quelldaten stehen. Der
   * Bestand wird deshalb gefragt: ein Tag ohne `base_name`, ein Einsatz mit
   * `winch` an einem bodengebundenen Bergwachttag, ein Tag ohne Spurpunkte.
   * Findet sich keiner, bleibt der Platzhalter `null` — die Seite wird dann
   * NICHT fotografiert und steht im Bericht als nicht aufgeloest. Das ist die
   * richtige Antwort: Ein Bestand ohne diese Faelle soll keine Bilder
   * liefern, die so aussehen, als haette er sie.
   *
   * ZWEI GEZIELTE ABFRAGEN STATT EINUNDZWANZIG. `api/day.php?d=` liefert die
   * Spur mit; ueber alle Diensttage zu gehen waere ein paar Megabyte JSON bei
   * jedem Lauf. Die Liste sagt schon, welche Tage ueberhaupt in Frage kommen:
   * `base_name === null` fuer die beiden Veranstaltungstage, `art_symbol ===
   * 'bergwacht'` mit `kind === 'ground'` fuer die Windenkacheln. */
  const tagListe = await s.evaluate(async (b) => {
    const r = await fetch(b + '/api/day.php', { credentials: 'same-origin' });
    return (await r.json()).days || [];
  }, BASIS).catch(() => []);

  const tagInhalt = (id) => s.evaluate(async ([b, d]) => {
    const r = await fetch(b + '/api/day.php?d=' + d, { credentials: 'same-origin' });
    return await r.json();
  }, [BASIS, id]).catch(() => null);

  p['__TAG_OHNE_STANDORT__'] = null;
  p['__TAG_LUFTLINIE__']     = null;
  p['__EINSATZ_WINDE__']     = null;

  for (const t of tagListe.filter((x) => x.base_name === null)) {
    const i = await tagInhalt(t.id);
    const m = (i && i.missions) || [];
    if (!m.length) { continue; }
    const mitSpur = m.filter((x) => (x.track || []).length > 0).length;
    if (!p['__TAG_OHNE_STANDORT__'] && mitSpur > 0) {
      p['__TAG_OHNE_STANDORT__'] = `index.php?d=${t.id}`;
    }
    if (!p['__TAG_LUFTLINIE__'] && mitSpur === 0
        && m.some((x) => x.dest_lat !== null || x.start_src !== null)) {
      p['__TAG_LUFTLINIE__'] = `index.php?d=${t.id}`;
    }
  }

  for (const t of tagListe.filter((x) => x.art_symbol === 'bergwacht'
                                      && x.kind === 'ground')) {
    const i = await tagInhalt(t.id);
    const m = ((i && i.missions) || []).find((x) => x.winch === true);
    if (m) { p['__EINSATZ_WINDE__'] = `einsatz.php?id=${m.id}`; break; }
  }

  const fehlend = Object.entries(p).filter(([, v]) => v === null).map(([k]) => k);
  if (fehlend.length) {
    console.log('NICHT AUFGELÖST (diese Seiten werden nicht fotografiert): '
                + fehlend.join(', '));
  }
  return p;
}

const PLATZ = await platzhalter();

/* ---- Bedienschritte vor der Aufnahme -------------------------------------- */
/* EIN UNBEKANNTER SCHRITT IST EIN FEHLER, kein Achselzucken (O11).
 *
 * Diese Funktion hatte kein `else`: Wer `"vorher": ["dialog"]` in die
 * Seitenliste schrieb, bekam anstandslos acht Bilder OHNE Dialog und einen
 * Bericht „0 Ueberlauf, 0 Konsolenfehler". Dieselbe Falle wie F-P3-AH
 * (Seite ohne Parameter), F-P3-AQ (verlorene Sitzung) und F-P3-AV (falscher
 * Statuscode) — zum vierten Mal, und jedes Mal ist das Muster dasselbe: Das
 * Werkzeug tut etwas anderes als bestellt und meldet Erfolg.
 *
 * Rueckgabe: null bei Erfolg, sonst der Fehlertext. */
/* ---- Die Kopplungskarte braucht ein GERAET auf der anderen Seite (S5) -----
 *
 * Zwei ihrer drei Zustaende entstehen erst, wenn jemand einen Code eingibt,
 * den ein Geraet gezeigt hat. Die Probe ist dieses Geraet: Sie holt sich ueber
 * `pair.php` mit `aktion=start` eine Kopplungssitzung — genau so, wie eine Uhr
 * es tut, ueber echtes HTTP aus dem Seitenkontext. Keine Attrappe, kein
 * SQL-Handgriff; was fotografiert wird, ist der Zustand, den die Anwendung
 * wirklich zeigt.
 *
 * DER CODE WIRD JE SCHRITT EINMAL GEHOLT und ueber alle acht Breiten
 * wiederverwendet. Das ist kein Geiz, sondern noetig: Der Ratenschutz-Topf
 * `pair_start` laesst 20 Aufrufe je zehn Minuten und Adresse zu (E-S5-33).
 * Ein Lauf mit einer Sitzung je Breite braeuchte sechzehn und stuende damit
 * knapp vor der Sperre — zusammen mit `tools/proben/kopplung/rundlauf.mjs` im
 * selben Zeitfenster darueber.
 *
 * Der Wartezustand braucht ueberhaupt nur EINEN Durchgang: Er haengt an der
 * PHP-Sitzung des Browsers, und die ueberlebt den Wechsel der Fensterbreite.
 * Ab der zweiten Breite steht die Karte schon richtig da.
 *
 * WAS ZURUECKBLEIBT: eine Kopplungssitzung, die nach zehn Minuten verfaellt,
 * und keine Geraetezeile — das Geraet sagt in diesem Lauf nie Ja. */
const kopplungsSitzungen = new Map();

async function kopplungSitzung(seite, schluessel, fehlerSammler) {
  if (kopplungsSitzungen.has(schluessel)) { return kopplungsSitzungen.get(schluessel); }
  const a = await seite.evaluate(async () => {
    try {
      const r = await fetch('pair.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ aktion: 'start', geraet: { art: 'uhr', teil: '006-B4261-00' } }) });
      return { status: r.status, ...(await r.json()) };
    } catch (e) { return { status: 0, error: String(e) }; }
  });
  if (a.status !== 200 || !a.code) {
    fehlerSammler.push(`Kopplungssitzung nicht bekommen (HTTP ${a.status}, `
      + `${a.error || '—'}) — steht der Topf \`pair_start\` voll? Er lässt 20 Aufrufe `
      + 'je 10 Minuten und Adresse zu; ein Lauf von tools/proben/kopplung/rundlauf.mjs '
      + 'im selben Zeitfenster kann ihn gefüllt haben.');
    return null;
  }
  kopplungsSitzungen.set(schluessel, a);
  return a;
}

async function vorher(seite, schritte, fehlerSammler) {
  const BEKANNT = ['schublade', 'kopplung-rueckfrage', 'kopplung-warten', 'tagdaten-adhoc',
                   'notfallblatt'];
  for (const schritt of schritte || []) {
    if (!BEKANNT.includes(schritt)) {
      fehlerSammler.push(`Unbekannter Bedienschritt „${schritt}" — bekannt sind: `
                       + BEKANNT.join(', '));
      continue;
    }
    if (schritt === 'kopplung-rueckfrage' || schritt === 'kopplung-warten') {
      /* Steht die Karte schon im Wartezustand, ist nichts mehr zu tun — die
         PHP-Sitzung traegt ihn ueber die Breiten hinweg. */
      if (await seite.locator('#kopplung-warten').count()) { continue; }
      const sitzung = await kopplungSitzung(seite, schritt, fehlerSammler);
      if (!sitzung) { continue; }
      const feld = seite.locator('#koppeln input[name="code"]');
      if (!(await feld.count())) {
        fehlerSammler.push('Kein Feld „Code vom Gerät" auf der Seite — steht die '
                         + 'Kopplungskarte in einem anderen Zustand (Gerätelimit erreicht)?');
        continue;
      }
      await feld.fill(sitzung.code);
      await Promise.all([seite.waitForNavigation({ timeout: 30000 }),
                         seite.locator('#koppeln .knopf-primaer').click()]);
      if (schritt === 'kopplung-warten') {
        await Promise.all([seite.waitForNavigation({ timeout: 30000 }),
                           seite.locator('#koppeln .knopf-primaer').click()]);
      }
      await seite.waitForLoadState('networkidle');
      continue;
    }
    if (schritt === 'tagdaten-adhoc') {
      /* DAS FORMULAR „DIENSTTAG-DATEN" MIT AUFGEKLAPPTEM „ANDEREM
         RETTUNGSMITTEL" (S9/AP6, E-S9-10). Das Konzept verlangt fuer diesen
         Zustand ein Bild und kein Mockup — die Felder sind vorhandene
         Bausteine, zu sehen ist nur, wie sie zusammenstehen.

         DER SCHRITT SCHREIBT NICHTS. Er oeffnet das Formular und waehlt den
         letzten Eintrag der Auswahl; gespeichert wird erst durch „Speichern",
         und das drueckt hier niemand. Der Diensttag bleibt also, wie er ist —
         wichtig, weil der Bilderlauf am Referenzbestand faehrt. */
      const knopf = seite.locator('#tagdatenknopf');
      if (!(await knopf.count())) { continue; }
      if ((await seite.locator('#dayform').getAttribute('hidden')) !== null) {
        await knopf.click();
        await seite.waitForTimeout(400);
      }
      const wahl = seite.locator('#vehsel');
      if (await wahl.count()) {
        await wahl.selectOption('adhoc');
        await seite.waitForTimeout(450);
      }
      continue;
    }
    if (schritt === 'notfallblatt') {
      /* DAS NOTFALLBLATT MIT SCHLUESSEL (P5b/AP9).
         `notfallblatt.php` nimmt den Wiederherstellungsschluessel per POST
         entgegen und speichert ihn nicht — es gibt also keine Adresse, unter
         der die gefuellte Fassung per GET zu haben waere. Genau das ist ihr
         Wesen, und der Bilderlauf muss es nachbauen statt umgehen: Er baut
         dasselbe Formular, das `pw_handling.php` abschickt.

         DER CODE IST ERFUNDEN und entspricht nur dem Format (20 Zeichen aus
         dem Alphabet ohne 0/1/I/L/O/U, fuenf Vierergruppen). Er oeffnet
         nichts — die Seite prueft ihn gegen das Muster, nicht gegen ein
         Konto, und mehr braucht ein Bild nicht. */
      await Promise.all([
        seite.waitForNavigation({ timeout: 30000 }),
        seite.evaluate(() => {
          const f = document.createElement('form');
          f.method = 'post';
          f.action = 'notfallblatt.php';
          for (const [n, w] of [['code', 'K7MQ-3RXP-9TWB-2FHZ-VNJ4'],
                                ['konto', 'probe@example.org']]) {
            const i = document.createElement('input');
            i.type = 'hidden'; i.name = n; i.value = w;
            f.appendChild(i);
          }
          document.body.appendChild(f);
          f.submit();
        }),
      ]);
      await seite.waitForLoadState('networkidle');
      if (!(await seite.locator('.codeblock-wert').count())) {
        fehlerSammler.push('Notfallblatt ohne Schlüsselblock — der Code wurde '
                         + 'abgewiesen (Format?) oder die Seite hat den '
                         + 'Ohne-Code-Zweig gezeigt.');
      }
      continue;
    }
    if (schritt === 'schublade') {
      /* Die Schublade gibt es nur unter 1024 px — darueber steht die Leiste
         fest daneben, und der Menueknopf ist ausgeblendet. Ein Klick darauf
         lief in einen Timeout und meldete einen Fehler, den es nicht gibt.
         Deshalb: erst fragen, ob es das Bedienelement gerade gibt. */
      const knopf = seite.locator('[data-schublade="auf"]').first();
      if (await knopf.count() && await knopf.isVisible()) {
        await knopf.click();
        await seite.waitForTimeout(350);
      }
    }
  }
}

/* ---- DIE SITZUNGSWACHE (Web 9.10.1) ---------------------------------------
 *
 * WAS HIER SCHIEFGING, UND WARUM ES NIEMAND SAH.
 *
 * Der Lauf meldete „31 Seiten, 0 Ueberlauf, 0 Konsolenfehler" — und 22 der
 * 31 Seiten waren Bilder der ANMELDESEITE. 176 von 248 Einzelbildern. Sie
 * waren byteweise identisch; nachgewiesen mit `md5sum`, 23 Dateien je Breite
 * mit derselben Pruefsumme.
 *
 * Die Ursache steht nicht in diesem Werkzeug, sondern in der Anwendung:
 * Das Demo-Konto setzt sich alle 30 Minuten zurueck, und dabei erhoeht
 * `demo_zuruecksetzen()` die Sitzungs-Epoche (server/demo_lib.php,
 * `session_epoch = session_epoch + 1`). `auth_guard.php` beendet daraufhin
 * jede offene Sitzung dieses Kontos — auch unsere. Der Lauf braucht
 * mehrere Minuten und loest den faelligen Reset durch seine EIGENEN
 * Anfragen aus; ab da fotografiert er die Anmeldeseite.
 *
 * Die alte Pruefung (`if (seite.url().includes('login.php')) throw`) stand
 * EINMAL, unmittelbar nach dem Anmelden. Danach hat nichts mehr hingesehen.
 *
 * ZWEI DINGE SIND NOETIG, UND BEIDE STEHEN HIER:
 *
 *   1. Bemerken. Nach jedem `goto` wird geprueft, ob die Seite noch die
 *      gemeinte ist. Eine Umleitung auf die Anmeldung ist kein Bild wert.
 *   2. Weitermachen. Ein Sitzungsverlust ist im Demo-Betrieb NORMAL, nicht
 *      aussergewoehnlich — der Reset gehoert zum Konto. Also wird neu
 *      angemeldet und die Aufnahme einmal wiederholt.
 *
 * Hilft auch das nicht, wird NICHT fotografiert, sondern ein Fehler
 * vermerkt. Ein fehlendes Bild ist eine Auskunft; ein falsches ist eine
 * Luege, die durch jede weitere Pruefung durchmarschiert.
 */
function istAnmeldung(seite) {
  return seite.url().includes('login.php');
}

/* Bringt die Seite auf `adresse` und stellt sicher, dass sie auch dort ist.
 * Rueckgabe: { status, verloren } — `verloren` sagt, dass die Sitzung neu
 * aufgebaut werden musste (fuer den Bericht). Wirft nicht; Fehler landen in
 * `rolle.fehler`. */
async function gehZu(rolle, adresse, zielPfad) {
  const seite = rolle.seite;
  let status = 0, verloren = false;
  for (let versuch = 0; versuch < 2; versuch++) {
    try {
      const antwort = await seite.goto(adresse, { waitUntil: 'domcontentloaded', timeout: 45000 });
      status = antwort ? antwort.status() : 0;
    } catch (e) {
      rolle.fehler.push('laden: ' + e.message);
      return { status, verloren };
    }
    /* Die Anmeldeseite ist nur dann die richtige Antwort, wenn sie auch
       gemeint war (01-anmeldung, Rolle „aus"). */
    if (!istAnmeldung(seite) || zielPfad.startsWith('login.php')) {
      return { status, verloren };
    }
    if (versuch === 1) { break; }
    verloren = true;
    if (!await anmeldenAuf(seite, rolle.rolle)) {
      rolle.fehler.push('Sitzung verloren und Neuanmeldung gescheitert');
      return { status, verloren };
    }
  }
  rolle.fehler.push('Sitzung verloren: die Seite leitet auf die Anmeldung um '
                  + '(auch nach Neuanmeldung) — kein Bild aufgenommen');
  return { status, verloren, abbruch: true };
}

/* ---- Eine Aufnahme --------------------------------------------------------- */
const bericht = { basis: BASIS, skala: SKALA, seiten: [], knopf: [], etikett: [],
                  etikettGeprueft: { titel: 0, kopf: 0 }, stand: new Date().toISOString() };
/* Aufnahmen, bei denen die Sitzung mitten im Lauf neu aufgebaut werden
 * musste (Demo-Reset), und solche, die deshalb GAR NICHT entstanden. */
const verlorene = [];
/* AUSGEFALLENE AUFNAHMEN, JE MIT GRUND (S9/AP5-6). Vorher stand hier eine
   Liste aus Zeichenketten, und der Bericht schrieb darueber pauschal „die
   Seite leitete auch nach einer Neuanmeldung auf die Anmeldung um". Es gibt
   aber ZWEI Gruende, und der zweite ist ein ganz anderer Befund: Ein
   Platzhalter, der sich nicht aufloesen laesst, heisst „diese Seite gibt es
   im Bestand nicht" — nicht „die Sitzung ging verloren". Beim Lauf zu AP5-4
   meldete der Bericht acht verlorene Sitzungen, wo in Wahrheit acht Bilder
   einer Seite fehlten, die es ohne systemweiten Standort gar nicht gibt.
   Ein Pruefmittel, das den falschen Grund nennt, schickt die naechste Suche
   in die falsche Richtung. */
const ausgefallen = [];

/* ---- Der Wartungsmodus als Zustand der Installation (S5 Paket W) ---------
 *
 * KEIN BEDIENSCHRITT IM BROWSER, deshalb nicht in vorher(): Die Wartungsseite
 * entsteht nicht dadurch, dass jemand etwas klickt, sondern dadurch, dass eine
 * DATEI auf dem Server liegt.
 *
 * DER SATZ, DER HIER STAND, WAR EINE ANNAHME — UND SIE IST GEPLATZT.
 * „Der Bilderlauf laeuft auf derselben Maschine wie die Installation und legt
 * sie deshalb selbst an." Das stimmte, solange von Hand gemessen wurde. Seit
 * Stufe 2 der Auslieferungskette laeuft der Lauf auf einem GitHub-Laeufer
 * gegen ein fernes Staging: Die Datei entsteht dann im CHECKOUT, Staging
 * bleibt offen, `index.php` leitet den nicht angemeldeten Aufruf zur Anmeldung
 * um — acht Aufnahmen ohne Bild und acht Konsolenfehler, gemessen am
 * 17.09.2026 (Backlog Nr. 220).
 *
 * ES IST DER DRITTE FALL DERSELBEN ANNAHME. `kreislauf.py` hielt die Jobs
 * ueber die lokale Kommandozeile an (Nr. 219), `demo_kennzeichnen.php`
 * braucht `db()` auf dem Server — und hier der Wartungsschalter. Wer ein
 * Werkzeug gegen `--basis` misst und dabei in `server/` schreibt, baut diese
 * Annahme ein, ohne sie hinzuschreiben.
 *
 * ZWEI WEGE, und welcher passt, haengt an `--jobs-token`:
 *
 *   MIT TOKEN ueber `jobs.php?aktion=wartung_an`, gefahren von
 *   `tools/kette/tor.py` — dem einen Client dieser Schnittstelle. Derselbe
 *   Weg, den `kreislauf.py` seit Web 20.16.0 fuer die Job-Pause geht.
 *
 *   OHNE TOKEN ueber die Datei, wie bisher. Wer auf seinem Rechner misst,
 *   merkt von der Aenderung nichts.
 *
 * Ein Eintrag mit "wartung": true schaltet vor seinen acht Breiten ein und
 * danach wieder aus. Zusaetzlich haengt das Ausschalten am Prozessende:
 * Bliebe die Datei nach einem Abbruch liegen, waere JEDE weitere Aufnahme
 * ein Bild der Wartungsseite, und der Bericht meldete „0 Ueberlauf" fuer
 * zweihundert Bilder desselben Textes — genau die Falle aus F-P3-AQ, wo 176
 * von 248 Bildern die Anmeldeseite zeigten.
 *
 * EINE FREMDE WARTUNG WIRD NICHT ANGEFASST. Liegt beim Einschalten schon
 * eine Datei, ruehrt der Lauf sie nicht an und loescht sie am Ende auch
 * nicht — sonst oeffnete ein Bilderlauf eine Installation, die jemand
 * ausdruecklich geschlossen hat. */
const WARTUNGSDATEI = join(WURZEL, 'server', 'wartung.lock');
const TOR = join(WURZEL, 'tools', 'kette', 'tor.py');
let wartungVonUns = false;

/* Ein Aufruf an tor.py, synchron. SYNCHRON IST PFLICHT, nicht Geschmack:
 * `wartungAus()` haengt an `process.on('exit')`, und dort laeuft nichts
 * Asynchrones mehr. */
function tor(befehl) {
  const e = spawnSync('python3', [TOR, befehl, '--basis', BASIS,
                                  '--token', JOBS_TOKEN], { encoding: 'utf8' });
  if (e.status !== 0) {
    const grund = (e.stderr || e.stdout || `Rückgabewert ${e.status}`).trim();
    throw new Error(`tor.py ${befehl} gegen ${BASIS} fehlgeschlagen: ${grund}`);
  }
  try { return JSON.parse(e.stdout); } catch { return {}; }
}

/* Das Ausschalten ist MISSLUNGEN und die Anlage steht noch zu. Geht in den
 * Rueckgabewert und in den Bericht — siehe den Block darueber. */
let wartungHaengt = false;

/* Gibt bei Erfolg null zurueck, sonst den GRUND als Text. Wirft NICHT:
 * Ein Schluckauf der Leitung darf nicht den ganzen Lauf wegwerfen, samt
 * Bericht ueber die 48 Seiten, die gelungen sind. Die Aufnahme, die daran
 * haengt, faellt aus — und das ist etwas, das gezaehlt wird. */
function wartungAn() {
  if (JOBS_TOKEN) {
    let zustand;
    try { zustand = tor('zustand'); }
    catch (ex) { return `Zustand nicht abfragbar: ${ex.message}`; }
    /* Fremde Wartung nicht anfassen — dieselbe Regel wie unten, nur ueber die
     * Leitung gefragt statt am Dateisystem. */
    if (zustand?.wartung?.aktiv) { return null; }

    /* DIE FLAGGE STEHT VOR DEM AUFRUF, und das ist der Unterschied zwischen
     * einer Datei und einer Leitung: `writeFileSync` schreibt oder wirft.
     * Ein HTTP-Aufruf hat einen DRITTEN Ausgang — ausgefuehrt, aber nicht
     * bestaetigt. Bliebe die Flagge dann falsch, schaltete niemand mehr aus,
     * und die Anlage stuende zu, weil eine Antwort verlorenging. */
    wartungVonUns = true;
    try { tor('wartung-an'); }
    catch (ex) { return `Wartungsmodus nicht einschaltbar: ${ex.message}`; }
    return null;
  }
  if (existsSync(WARTUNGSDATEI)) { return null; }   // fremde Wartung nicht anfassen
  wartungVonUns = true;
  try {
    writeFileSync(WARTUNGSDATEI, JSON.stringify({
      seit: new Date().toISOString().replace(/\.\d+Z$/, 'Z'), von: 'Bilderlauf' }) + '\n');
  } catch (ex) { return `wartung.lock nicht schreibbar: ${ex.message}`; }
  return null;
}
function wartungAus() {
  if (!wartungVonUns) { return; }
  if (JOBS_TOKEN) {
    /* IM AUSSCHALTEN WIRD NICHT GEWORFEN. Diese Funktion haengt an
     * `process.on('exit')`; eine Ausnahme dort verdeckt den eigentlichen
     * Grund des Abbruchs.
     *
     * UND DIE FLAGGE BLEIBT STEHEN. Sie hier zurueckzusetzen hiesse, jeden
     * weiteren Versuch zu entwaffnen: `wartungAus()` laeuft nach JEDEM
     * Eintrag und noch einmal am Prozessende. Ein einmaliger Schluckauf des
     * Servers heilt sich so von selbst; ohne das Stehenbleiben bliebe die
     * Anlage zu, und der Lauf meldete trotzdem gruen. */
    try { tor('wartung-aus'); } catch (ex) {
      wartungHaengt = true;
      console.error(`ACHTUNG: Der Wartungsmodus auf ${BASIS} liess sich NICHT `
                    + `ausschalten (${ex.message}). Die Installation ist noch `
                    + `geschlossen — von Hand nachsehen: Betrieb → Updates.`);
      return;                      // Flagge bleibt: der naechste Versuch kommt
    }
    wartungHaengt = false;
    wartungVonUns = false;
    return;
  }
  if (existsSync(WARTUNGSDATEI)) { rmSync(WARTUNGSDATEI); }
  wartungVonUns = false;
}
process.on('exit', wartungAus);
for (const sig of ['SIGINT', 'SIGTERM']) {
  process.on(sig, () => { wartungAus(); process.exit(1); });
}

for (const eintrag of liste) {
  /* Ein Platzhalter, der in PLATZ steht, MUSS einen Wert haben — sonst gibt
     es diese Seite im Bestand nicht, und ein Bild waere geraten. */
  const aufgeloest = Object.prototype.hasOwnProperty.call(PLATZ, eintrag.pfad)
    ? PLATZ[eintrag.pfad] : eintrag.pfad;
  if (aufgeloest === null) {
    for (const { b } of BREITEN) {
      ausgefallen.push({ was: `${eintrag.name} @ ${b}`,
                         grund: `Platzhalter ${eintrag.pfad} nicht auflösbar` });
    }
    console.log(`${eintrag.name.padEnd(34)} OHNE BILD — Platzhalter ${eintrag.pfad} nicht auflösbar`);
    continue;
  }
  let pfad = aufgeloest;
  /* Ob dieser Eintrag von einer im Lauf ermittelten Kennung abhaengt —
     davon haengt ab, ob ein 404 heilbar ist (siehe unten). */
  const ausPlatzhalter = Object.prototype.hasOwnProperty.call(PLATZ, eintrag.pfad);
  let nachgeloest = false;
  /* EINE UNBEKANNTE ROLLE SAGT ES, statt drei Zeilen weiter an `undefined`
   * zu scheitern. Am 17.09.2026 stand `"rolle": "user"` in `seiten.json` —
   * die gibt es nicht (`aus`, `demo`, `admin`), und der Lauf brach mit
   * „Cannot read properties of undefined (reading 'seite')" ab: eine Meldung,
   * die den Dateinamen nicht nennt und die Ursache erst recht nicht. */
  const rolle = rollen[eintrag.rolle || 'demo'];
  if (!rolle) {
    throw new Error(`Seite "${eintrag.name}": Rolle "${eintrag.rolle}" gibt es nicht. `
                  + `Erlaubt sind: ${Object.keys(rollen).join(', ')}.`);
  }
  const seite = rolle.seite;
  const zeile = { name: eintrag.name, gruppe: eintrag.gruppe, pfad, breiten: [] };
  const bilder = [];

  if (eintrag.wartung) {
    /* Ein Fehlschlag hier ist eine NICHT GEMESSENE Seite, kein Abbruch des
     * Laufs: Die uebrigen 48 sind gemessen, und der Bericht soll sie nennen.
     * `ausgefallen` geht in den Bericht UND in den Rueckgabewert. */
    const grund = OHNE_WARTUNGSWEG || wartungAn();
    if (grund) {
      for (const { b } of BREITEN) {
        ausgefallen.push({ was: `${eintrag.name} @ ${b}`, grund });
      }
      console.log(`${eintrag.name.padEnd(34)} OHNE BILD — ${grund}`);
      continue;
    }
  }

  for (const { b, h, art } of BREITEN) {
    let adresse = `${BASIS}/${pfad}`;
    rolle.fehler.length = 0;
    rolle.setzeAdresse(adresse);
    await seite.setViewportSize({ width: b, height: h });
    /* DIE EINGABEART WIRD VOR JEDER BREITE NEU GESETZT. Warum, steht in
     * `anmelden()`: Der Vollseiten-Screenshot der vorigen Breite hat sie
     * verloren. */
    await rolle.eingabeart();

    let hin = await gehZu(rolle, adresse, pfad);
    let status = hin.status;

    /* ---- 404 NACH EINEM DEMO-RESET: KENNUNGEN NEU HOLEN (S8/AP7) --------
     *
     * `platzhalter()` laeuft einmal, zu Beginn. Das Demo-Konto setzt sich
     * alle 30 Minuten zurueck, ein voller Lauf dauert laenger als das — und
     * danach zeigen `?d=` und `?id=` auf Zeilen, die es nicht mehr gibt.
     * Gemessen am 06.09.2026: Die Einsatzseiten (frueh im Lauf) standen, die
     * sechs Tag- und Aktionsseiten dahinter antworteten mit 404; 48 von 368
     * Aufnahmen fielen aus. Kein Fehler der Anwendung, sondern eine Kennung,
     * die dem Bestand davongelaufen ist.
     *
     * Deshalb: EINMAL JE SEITE neu aufloesen und denselben Aufruf
     * wiederholen. Nicht oefter — ein 404, der auch mit frischen Kennungen
     * bleibt, ist ein echter, und den soll der Bericht zeigen. */
    if (ausPlatzhalter && status === 404 && (eintrag.status || 200) !== 404
        && !nachgeloest) {
      nachgeloest = true;
      Object.assign(PLATZ, await platzhalter());
      if (PLATZ[eintrag.pfad]) {
        pfad = PLATZ[eintrag.pfad];
        zeile.pfad = pfad;
        adresse = `${BASIS}/${pfad}`;
        await seite.setViewportSize({ width: b, height: h });
        await rolle.eingabeart();
        rolle.fehler.length = 0;
        rolle.setzeAdresse(adresse);
        hin = await gehZu(rolle, adresse, pfad);
        status = hin.status;
        console.log(`${''.padEnd(34)} Kennung erneuert (Demo-Reset): ${eintrag.name} → ${pfad}`);
      }
    }
    /* DER STATUS MUSS STIMMEN (O11). Eine Seite, die 404 liefert, zeigt die
       Abbruchseite — ein Bild davon unter dem Namen einer anderen Seite ist
       so wertlos wie ein Bild der Anmeldung. Erwartet wird 200; eine Seite,
       die es anders meint (03-abbruchseite), sagt das in der Seitenliste
       ausdruecklich mit "status". */
    const sollStatus = eintrag.status || 200;
    if (status && status !== sollStatus && !hin.abbruch) {
      rolle.fehler.push(`Status ${status}, erwartet ${sollStatus} — kein Bild aufgenommen`);
      hin.abbruch = true;
    }
    if (hin.verloren && !hin.abbruch) {
      /* Kein Fehler, aber eine Auskunft: Der Reset des Demo-Kontos ist im
         Lauf normal, und wer den Bericht liest, soll wissen, dass hier neu
         angemeldet wurde. */
      verlorene.push(`${eintrag.name} @ ${b}`);
    }
    if (!hin.abbruch) {
      try {
        await seite.waitForTimeout(eintrag.karte ? 900 : 400);
        await vorher(seite, eintrag.vorher, rolle.fehler);
        await seite.waitForTimeout(150);
      } catch (e) {
        rolle.fehler.push('laden: ' + e.message);
      }
    }

    /* ---- ERST MESSEN, WENN DAS STYLESHEET GREIFT (Fund aus S8/AP7) ------
     *
     * `domcontentloaded` heisst nicht, dass `style.css` angewendet ist.
     * Gemessen an der Abbruchseite bei 1024 px: `getComputedStyle` lieferte
     * fuer den Knopf `height: auto`, `font-family: Times New Roman`,
     * `border-width: 0` — die ungestaltete Seite, Hoehe 35 px statt 36. Der
     * Lauf meldete das als „Knopf mit falscher Hoehe", und es war keiner.
     *
     * Gefaehrlicher ist die Gegenrichtung: Eine ungestaltete Seite laeuft
     * nicht ueber und wirft keinen Konsolenfehler — sie meldet zweimal
     * Null. Das Token `--knopf` steht nur in `:root` von `style.css`; ist es
     * da, ist das Stylesheet da. */
    await seite.waitForFunction(
      () => getComputedStyle(document.documentElement)
              .getPropertyValue('--knopf').trim() !== '',
      null, { timeout: 5000 })
      .catch(() => rolle.fehler.push(
        'Stylesheet nach 5 s nicht angewendet — gemessen wurde die ungestaltete Seite'));

    const mass = await seite.evaluate(() => ({
      scrollWidth: document.documentElement.scrollWidth,
      innerWidth: window.innerWidth,
      /* WER ueberlaeuft? Ohne diese Auskunft ist „Ueberlauf bei 360" eine
         Zahl, mit der niemand etwas anfangen kann: Man weiss, DASS die Seite
         zu breit ist, nicht WOVON. Gesucht wird das Element, das am weitesten
         nach rechts reicht und dessen Elternteil das nicht auch tut — also
         der Verursacher, nicht die Kette darueber. */
      taeter: (function () {
        var grenze = window.innerWidth, bester = null, weiteste = grenze;
        var alle = document.querySelectorAll('body *');
        for (var i = 0; i < alle.length; i++) {
          var el = alle[i];
          var r = el.getBoundingClientRect();
          if (r.width === 0 || r.right <= grenze + 1) { continue; }
          var pr = el.parentElement ? el.parentElement.getBoundingClientRect() : null;
          if (pr && pr.right > grenze + 1) { continue; }   // Elternteil laeuft auch ueber

          /* WER IN EINEM SCROLLENDEN KASTEN STECKT, IST KEIN TAETER
           * (P5b/AP8). Ein `<code>` in einem `<pre class="overflow-x:auto">`
           * reicht weit ueber den Rand hinaus — und wird von seinem
           * Elternteil abgeschnitten, also schiebt es die SEITE nicht. Beide
           * Bedingungen oben treffen trotzdem zu: Es laeuft ueber, und sein
           * Elternteil tut es nicht.
           *
           * Am 17.09.2026 nannte der Bericht deshalb `code (626 px)` als
           * Verursacher des Ueberlaufs auf `hilfe.php`. Der wirkliche
           * Verursacher waren die 43 TABELLEN des Handbuchs (368 px bei
           * 360 px Breite). Ich habe zuerst den Code umbrechen lassen, was
           * nichts aenderte — die Zahl blieb bei +124 px, weil sie nie von
           * dort kam. Eine Messung, die auf den Falschen zeigt, kostet mehr
           * als eine, die gar nichts sagt. */
          var klemmt = false;
          for (var a = el.parentElement; a && a !== document.body; a = a.parentElement) {
            var ox = getComputedStyle(a).overflowX;
            if (ox === 'auto' || ox === 'scroll' || ox === 'hidden') { klemmt = true; break; }
          }
          if (klemmt) { continue; }
          if (r.right > weiteste) {
            weiteste = r.right;
            bester = el.tagName.toLowerCase()
                   + (el.id ? '#' + el.id : '')
                   + (el.className && typeof el.className === 'string'
                      ? '.' + el.className.trim().split(/\s+/).join('.') : '');
          }
        }
        return bester ? bester + '  (' + Math.round(weiteste) + ' px)' : null;
      })(),
      /* NUR SICHTBARE KNOEPFE messen. Der erste Entwurf mass alle und meldete
         Dutzende mit Hoehe 0: den X-Knopf der Schublade, der ab 1024 px
         `display:none` ist, und die Eintraege in einem geschlossenen
         Aktionsblatt. Ein Knopf, den es gerade nicht gibt, ist nicht zu hoch
         und nicht zu niedrig — er ist nicht da. */
      /* `.sprungziel` MISST MIT (S9/AP5). Die Pille der Sprungliste ist
         `height:var(--knopf)` hoch, also 44/36 — aber sie traegt nicht
         `.knopf`, und eine Auswahl, die nur `.knopf` kennt, haette diese
         Zusage nie gemessen. Genau so ist `.listenfilter` seit O6 ungemessen
         geblieben und der Export-Knopf vier Monate ungestaltet (F-P3-BA).
         Wer ein neues Bedienelement baut, traegt es hier ein. */
      /* KARTEN, DIE AUS DEM SEITENGERUEST AUSGEBROCHEN SIND (Nr. 225).
         Eine Karte gehoert in `main.inhalt`. Haengt sie daneben — weil ein
         `ui_karte_ende()` zu viel `div.rahmen` mitgeschlossen hat —, liegt
         sie ueber die volle Fensterbreite unter der Seitenleiste hindurch.

         DIE DREI ANDEREN ZAHLEN SEHEN DAS NICHT, und das ist der Grund, warum
         diese hier steht: `scrollWidth` bleibt gleich `innerWidth` (es laeuft
         nichts ueber, es liegt nur falsch), die Konsole bleibt still, die
         Knopfhoehen stimmen. Die Profilseite meldete zehn Tage lang drei
         Nullen und war kaputt; gefunden wurde es beim ANSEHEN eines Bildes. */
      titel: document.title,
      kopf: (document.querySelector('header.kopf') || {}).className || null,
      ausbruch: Array.from(document.querySelectorAll('section.karte, details.karte'))
        .filter(el => !el.closest('main.inhalt'))
        .map(el => ((el.querySelector('h2, h3') || {}).textContent || '(ohne Titel)')
                    .trim().replace(/\s+/g, ' ').slice(0, 40)),
      karten: document.querySelectorAll('section.karte, details.karte').length,
      knoepfe: Array.from(document.querySelectorAll('.knopf, .sprungziel'))
        .filter(el => el.offsetParent !== null || el.getClientRects().length > 0)
        .map(el => ({
          text: (el.textContent || '').trim().replace(/\s+/g, ' ').slice(0, 24)
                || el.getAttribute('aria-label') || '(ohne Text)',
          hoehe: Math.round(el.getBoundingClientRect().height),
          /* Zwilling des grossen Suchfeldes: Der Filterknopf steht daneben
           * und ist so hoch wie es — 48 statt 44 px. Die Regel P-P3-04
           * sichert eine MINDESTflaeche; groesser ist kein Verstoss, nur
           * ungleich. Damit die Zahl im Bericht trotzdem etwas heisst, wird
           * dieser eine Fall benannt statt stillschweigend geduldet. */
          suchzwilling: !!el.closest('.suchzeile'),
        })),
    })).catch(() => ({ scrollWidth: 0, innerWidth: b, knoepfe: [], taeter: null,
                       ausbruch: [], karten: 0 }));

    const datei = join(AUSGABE, 'einzeln', `${eintrag.name}-${b}.png`);
    if (hin.abbruch) {
      /* KEIN BILD. Ein Bild der Anmeldeseite unter dem Namen einer anderen
         Seite ist schlimmer als gar keines: Es sieht wie ein Beleg aus. */
      ausgefallen.push({ was: `${eintrag.name} @ ${b}`,
                         grund: 'Seite leitete auf die Anmeldung um' });
      rmSync(datei, { force: true });
    } else {
      /* GANZSEITIG IST DIE REGEL UND NICHT DAS GESETZ (P5b/AP8).
       *
       * `hilfe.php` zeigt das ganze Handbuch auf einer Seite: 305 KB HTML,
       * als Abzug 19 MB je Breite — und ab 1024 px scheiterte
       * `fullPage:true` ganz, weil Chromium eine Hoechsthoehe hat. Eine
       * Seite, die laenger ist als diese Grenze, kann man nicht in einem
       * Bild fotografieren; das ist keine Einstellung, sondern Physik.
       *
       * Fuer solche Seiten steht `"ganzseitig": false` in `seiten.json`:
       * Dann wird der SICHTBARE Ausschnitt abgezogen. Was dabei verloren
       * geht, ist nur das Bild — die Messungen (Ueberlauf, Konsolenfehler,
       * Knopfhoehen, Karten ausserhalb) laufen ueber das ganze Dokument und
       * bleiben unveraendert.
       *
       * UND DAS SCHEITERN SPRICHT JETZT. Hier stand `.catch(() => {})`: Der
       * Abzug misslang still, die Datei entstand nicht, und der
       * Kontaktbogen stuerzte eine Funktion spaeter mit `ENOENT` ab — auf
       * einen Dateinamen, der nie erklaerte, warum er fehlt. Eine
       * verschluckte Ausnahme kostet immer genau so viel Zeit wie die
       * Strecke zwischen ihr und dem Ort, an dem es auffaellt. */
      const ganz = eintrag.ganzseitig !== false;
      try {
        await seite.screenshot({ path: datei, fullPage: ganz });
        bilder.push({ datei, b, art });
        /* DER SICHTBARE TEXT DANEBEN (Schritt 15/AP7).
         *
         * WARUM DAS BILD NICHT REICHT: Ein Umbau, der nur die Formatierung
         * zentralisiert, darf keinen Buchstaben aendern — und genau das
         * belegt ein Bild nicht. Ueberlauf, Konsolenfehler und Knopfhoehen
         * melden 0, auch wenn aus „2,00 GB" ein „2 GB" geworden ist.
         *
         * WARUM NICHT DER PIXELVERGLEICH: Er ist gefahren worden und hat
         * auf UNVERAENDERTEM Code 303 von 496 Bildern als abweichend
         * gemeldet. Ursache ist der Countdown im Demo-Banner („in etwa
         * 43 188 Minuten"), der auf jeder Seite des Demo-Kontos steht und
         * sich zwischen zwei Laeufen zwangslaeufig aendert. Ein
         * Textvergleich kann solche Zeilen benennen und ausnehmen; ein
         * Pixelvergleich kann es nicht.
         *
         * `innerText` und nicht `textContent`: Ersteres gibt den Text so
         * wieder, wie er DARGESTELLT wird — ohne ausgeblendete Elemente,
         * mit den Umbruechen der Darstellung. Das ist die Frage, die hier
         * gestellt wird. */
        const text = await seite.evaluate(() => document.body.innerText)
                                .catch(() => null);
        if (text !== null) {
          writeFileSync(join(AUSGABE, 'texte', `${eintrag.name}-${b}.txt`), text);
        }
      } catch (e) {
        ausgefallen.push({ was: `${eintrag.name} @ ${b}`,
                           grund: `Abzug misslang (${ganz ? 'ganzseitig' : 'Ausschnitt'}): `
                                + String(e && e.message || e).split('\n')[0] });
        rmSync(datei, { force: true });
      }
    }

    zeile.breiten.push({
      breite: b, status,
      ueberlauf: mass.scrollWidth > mass.innerWidth ? mass.scrollWidth - mass.innerWidth : 0,
      taeter: mass.taeter || null,
      ausbruch: mass.ausbruch || [],
      karten: mass.karten || 0,
      konsole: rolle.fehler.slice(),
    });
    /* DAS ETIKETT — je Seite und Breite, in beiden Richtungen (s. `ETIKETT`).
     * Eine Seite, die auf die Anmeldung umleitete, hat kein Bild und zaehlt
     * hier auch nicht: Ihr Titel waere der der Anmeldeseite. */
    if (!hin.abbruch && typeof mass.titel === 'string') {
      const vorsatz = ETIKETT ? `[${ETIKETT}] ` : '';
      bericht.etikettGeprueft.titel++;
      const titelOk = ETIKETT ? mass.titel.startsWith(vorsatz) : !mass.titel.startsWith('[');
      if (!titelOk) {
        bericht.etikett.push({ seite: eintrag.name, breite: b, was: 'Titel', ist: mass.titel });
      }
      if (mass.kopf !== null) {
        bericht.etikettGeprueft.kopf++;
        const rot = /\bkopf-umgebung\b/.test(mass.kopf);
        if (rot !== !!ETIKETT) {
          bericht.etikett.push({ seite: eintrag.name, breite: b, was: 'Kopfleiste', ist: mass.kopf });
        }
      }
    }
    for (const k of mass.knoepfe) {
      /* ZWEI SOLLWERTE (E-S8-09). 36 px gilt nur, wo beides zutrifft:
       * Zeigergeraet UND mindestens 1024 px — dieselbe Bedingung wie im
       * Stylesheet. Eine Zahl hier, die dort nicht steht, machte den
       * Pruefstand zur zweiten Quelle. */
      const soll = k.suchzwilling ? 48 : ((!FINGER && b >= 1024) ? 36 : 44);
      if (k.hoehe !== soll) bericht.knopf.push({ seite: eintrag.name, breite: b, soll, ...k });
    }
  }

  wartungAus();

  await kontaktbogen(eintrag.name, bilder);
  bericht.seiten.push(zeile);
  const ueber = zeile.breiten.filter(x => x.ueberlauf).map(x => x.breite);
  const kons  = zeile.breiten.reduce((n, x) => n + x.konsole.length, 0);
  /* Der Ausbruch haengt am Markup, nicht an der Breite — er steht bei allen
     acht gleich. Einmal nennen, nicht achtmal. */
  const raus  = (zeile.breiten.find(x => x.ausbruch.length) || {}).ausbruch || [];
  console.log(`${eintrag.name.padEnd(34)} ${ueber.length ? 'Überlauf bei ' + ueber.join(', ') : 'kein Überlauf'}` +
              `${kons ? '  ·  ' + kons + ' Konsolenfehler' : ''}` +
              `${raus.length ? '  ·  ' + raus.length + ' Karte(n) außerhalb von main.inhalt: '
                             + raus.join(', ') : ''}`);
}

/* ---- Kontaktbogen ---------------------------------------------------------
 *
 * Acht Bilder nebeneinander, jedes mit seiner Breite beschriftet, auf einem
 * Blatt. Gebaut wird er im Browser selbst: Die Einzelbilder gehen als
 * data:-Adressen in eine Seite, die anschliessend fotografiert wird. Ein
 * Bildbearbeitungswerkzeug als weitere Abhängigkeit wäre für diese eine
 * Aufgabe zu viel. */
async function kontaktbogen(name, bilder) {
  const SPALTE = 300;
  const teile = bilder.map(({ datei, b, art }) => {
    const daten = 'data:image/png;base64,' + readFileSync(datei).toString('base64');
    return `<figure style="margin:0;width:${SPALTE}px">
      <figcaption style="font:600 13px/1.4 system-ui;color:#1A2E4D;padding:6px 0">
        ${b} px <span style="color:#6E6459;font-weight:400">· ${art}</span></figcaption>
      <img src="${daten}" style="width:100%;display:block;border:1px solid #E3DAC6;background:#fff">
    </figure>`;
  });
  const seite = await rollen.aus.kontext.newPage();
  await seite.setViewportSize({ width: SPALTE * 8 + 9 * 12, height: 800 });
  await seite.setContent(`<body style="margin:0;padding:12px;background:#F7F5ED">
    <h1 style="font:600 18px/1.4 system-ui;color:#1A2E4D;margin:0 0 10px">${name}</h1>
    <div style="display:flex;gap:12px;align-items:flex-start">${teile.join('')}</div></body>`);
  await seite.waitForTimeout(250);
  await seite.screenshot({ path: join(AUSGABE, 'bogen', `${name}.png`), fullPage: true });
  await seite.close();
}

/* ---- Bericht --------------------------------------------------------------- */
const gesamtUeberlauf = bericht.seiten.reduce((n, s) => n + s.breiten.filter(b => b.ueberlauf).length, 0);
const gesamtKonsole   = bericht.seiten.reduce((n, s) => n + s.breiten.reduce((m, b) => m + b.konsole.length, 0), 0);
const bilderZahl      = bericht.seiten.length * BREITEN.length;

let md = `# Bildaufnahme — Bericht\n\n`;
md += `Stand ${bericht.stand} · Basis ${BASIS} · Maßstab ${SKALA}× · `
   + `Eingabe **${FINGER ? 'Finger' : 'Zeiger'}** (Sollhöhe ${FINGER ? '44 px überall' : '44 px unter 1024, 36 px ab 1024'})\n\n`;
md += `| | |\n|---|---|\n`;
md += `| Seiten | ${bericht.seiten.length} |\n`;
md += `| Breiten | ${BREITEN.map(x => x.b).join(', ')} |\n`;
md += `| Einzelbilder | ${bilderZahl} |\n`;
md += `| Waagerechter Überlauf | **${gesamtUeberlauf}** von ${bilderZahl} |\n`;
md += `| Konsolenfehler | **${gesamtKonsole}** |\n`;
md += `| Knöpfe mit falscher Höhe | **${bericht.knopf.length}** |\n`;
md += `| Etikett ${ETIKETT ? '„' + ETIKETT + '“' : '(keins erwartet)'} | **${bericht.etikett.length}** Abweichungen `
   + `(${bericht.etikettGeprueft.titel} Titel, ${bericht.etikettGeprueft.kopf} Kopfleisten geprüft) |\n\n`;
md += `## Je Seite\n\n| Seite | Gruppe | Überlauf bei | Verursacher | Konsole |\n|---|---|---|---|---|\n`;
for (const s of bericht.seiten) {
  const breit = s.breiten.filter(x => x.ueberlauf);
  const u = breit.map(x => `${x.breite} (+${x.ueberlauf})`).join(', ') || '—';
  const t = [...new Set(breit.map(x => x.taeter).filter(Boolean))].join('<br>') || '—';
  const k = s.breiten.reduce((n, x) => n + x.konsole.length, 0);
  md += `| ${s.name} | ${s.gruppe} | ${u} | ${t} | ${k || '—'} |\n`;
}
if (gesamtKonsole) {
  md += `\n## Konsolenfehler im Wortlaut\n\n`;
  for (const s of bericht.seiten) {
    for (const b of s.breiten) {
      for (const f of b.konsole) md += `- \`${s.name}\` @ ${b.breite}: ${f}\n`;
    }
  }
}
if (bericht.knopf.length) {
  md += `\n## Knöpfe mit falscher Höhe\n\n| Seite | Breite | Knopf | Höhe |\n|---|---|---|---|\n`;
  for (const k of bericht.knopf) {
    md += `| ${k.seite} | ${k.breite} | ${k.text} | ${k.hoehe} px (soll ${k.soll}) |\n`;
  }
}
/* DIE SITZUNG STEHT IM BERICHT, nicht nur in der Konsole. Wer den Bericht
 * spaeter liest, muss sehen koennen, ob die Bilder ueberhaupt die gemeinten
 * Seiten zeigen — genau das war bis Web 9.10.1 nicht der Fall. */
bericht.sitzung = { neu_angemeldet: verlorene, ohne_bild: ausgefallen };
if (verlorene.length || ausgefallen.length) {
  md += `\n## Sitzung\n\n`;
  if (verlorene.length) {
    md += `Bei ${verlorene.length} Aufnahmen war die Sitzung fort und wurde neu `
       +  `aufgebaut; das Bild entstand danach. Im Demo-Konto ist das normal — `
       +  `sein Reset alle 30 Minuten erhöht die Sitzungs-Epoche.\n\n`;
    for (const v of verlorene) md += `- ${v}\n`;
  }
  if (ausgefallen.length) {
    md += `\n**${ausgefallen.length} Aufnahmen sind AUSGEFALLEN.** Für sie gibt `
       +  `es kein Bild; das ist Absicht — ein Bild der falschen Seite unter dem `
       +  `Namen einer anderen sieht wie ein Beleg aus. Der Grund steht je `
       +  `Zeile.\n\n`;
    for (const a of ausgefallen) md += `- ${a.was} — ${a.grund}\n`;
  }
}

writeFileSync(join(AUSGABE, 'bericht.md'), md);
writeFileSync(join(AUSGABE, 'bericht.json'), JSON.stringify(bericht, null, 2) + '\n');

console.log(`\n${bilderZahl} Einzelbilder, ${bericht.seiten.length} Kontaktbögen.`);
console.log(`Überlauf: ${gesamtUeberlauf} · Konsolenfehler: ${gesamtKonsole}`
  + ` · Knöpfe falscher Höhe: ${bericht.knopf.length}`  + ` (${FINGER ? 'Finger, 44 px' : 'Zeiger, 44/36 px'})`);
/* DIE ZAHL NENNT, WAS SIE GEMESSEN HAT (CLAUDE.md 6): nicht „0 Ausbrüche",
   sondern „n Karten geprüft, 0 außerhalb". Eine Seite ohne Karten meldete
   sonst dieselbe Null wie eine geprüfte. */
const gesamtKarten   = bericht.seiten.reduce((n, z) =>
  n + ((z.breiten.find(x => x.karten) || {}).karten || 0), 0);
const gesamtAusbruch = bericht.seiten.reduce((n, z) =>
  n + ((z.breiten.find(x => x.ausbruch && x.ausbruch.length) || {}).ausbruch || []).length, 0);
console.log(`Karten im Seitengerüst: ${gesamtKarten} geprüft · `
  + `${gesamtAusbruch} außerhalb von main.inhalt (Nr. 225)`);
if (verlorene.length)   { console.log(`Sitzung neu aufgebaut: ${verlorene.length}× (Demo-Reset, normal)`); }
if (ausgefallen.length) {
  /* NACH GRUND GEZAEHLT, nicht in einen Topf: „8 ohne Bild" sagt nichts, „8
     ohne Bild, Grund: Platzhalter nicht auflösbar" sagt, wo man nachsieht. */
  const nachGrund = {};
  for (const a of ausgefallen) { nachGrund[a.grund] = (nachGrund[a.grund] || 0) + 1; }
  const teile = Object.entries(nachGrund).map(([g, n]) => `${n}× ${g}`).join(' · ');
  console.log(`OHNE BILD: ${ausgefallen.length} Aufnahmen — ${teile}`);
}
/* ---- Gegenprobe gegen doppelte Bilder (E-PK-14) -------------------------
 *
 * WOZU. Acht Breiten je Seite sind acht Dateien — und wenn das Werkzeug
 * die Breite nicht wirklich umstellt, sind es acht IDENTISCHE Dateien, bei
 * denen alles grün meldet: kein Überlauf, keine Konsolenfehler, die richtige
 * Zahl Bilder. Eine Zahl, die entsteht, ohne gemessen zu haben — genau der
 * Fall, gegen den Grundsatz 7 geschrieben ist.
 *
 * GEZAEHLT WIRD JE SEITE, nicht über den ganzen Ordner: Zwei Seiten dürfen
 * gleich aussehen (eine Rückfrage über derselben Seite), acht Breiten
 * DERSELBEN Seite nicht. */
const doppelte = [];
let gelesen = 0;
for (const z of bericht.seiten) {
  const summen = new Map();
  for (const { b } of BREITEN) {
    const datei = join(AUSGABE, 'einzeln', `${z.name}-${b}.png`);
    if (!existsSync(datei)) continue;
    gelesen++;
    const summe = createHash('md5').update(readFileSync(datei)).digest('hex');
    if (!summen.has(summe)) summen.set(summe, []);
    summen.get(summe).push(b);
  }
  for (const [, breiten] of summen) {
    if (breiten.length > 1) doppelte.push(`${z.name}: ${breiten.join(', ')} px`);
  }
}
/* DIE ZAHL NENNT, WAS SIE GELESEN HAT — und das ist hier keine Förmlichkeit:
   Die erste Fassung dieser Prüfung sah im Ordner `seiten/` nach, der Ordner
   heißt aber `einzeln/`. Sie meldete „0 mit gleichen Bildern", ohne eine
   einzige Datei geöffnet zu haben. Gefunden beim Gegenprobieren, nicht beim
   Lesen. */
console.log(`Bildgleichheit: ${gelesen} Bilder aus ${bericht.seiten.length} Seiten `
  + `gelesen · ${doppelte.length} mit gleichen Bildern über mehrere Breiten`);
if (gelesen === 0 && bilderZahl > 0) {
  console.log('  ACHTUNG: kein Bild gelesen, obwohl welche entstanden sind — '
    + 'die Prüfung hat nichts gemessen.');
}
for (const d of doppelte) console.log(`  ${d}`);

console.log(`Etikett ${ETIKETT ? '„' + ETIKETT + '“' : '(keins erwartet)'}: `
  + `${bericht.etikettGeprueft.titel} Titel und ${bericht.etikettGeprueft.kopf} Kopfleisten geprüft · `
  + `${bericht.etikett.length} Abweichungen`);
for (const e of bericht.etikett.slice(0, 20)) {
  console.log(`  ${e.seite} @ ${e.breite}: ${e.was} „${e.ist}“`);
}
if (bericht.etikett.length > 20) console.log(`  … und ${bericht.etikett.length - 20} weitere`);

console.log(`Bericht: ${join(AUSGABE, 'bericht.md')}`);

await browser.close();
/* Eine ausgefallene Aufnahme ist ein Fehlschlag: Der Lauf hat seine Frage
   nicht beantwortet. */
if (wartungHaengt) {
  console.error(`ACHTUNG: ${BASIS} steht noch im Wartungsmodus — das `
    + `Ausschalten ist misslungen. Von Hand: Betrieb → Updates.`);
}
process.exit(gesamtUeberlauf === 0 && gesamtKonsole === 0
             && bericht.knopf.length === 0 && ausgefallen.length === 0
             && bericht.etikett.length === 0
             && !wartungHaengt ? 0 : 1);
