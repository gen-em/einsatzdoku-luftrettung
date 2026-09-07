/* Photon-Attrappe fuer die Klickprobe (S9/AP1, E-S9-16).
 * ===========================================================================
 *
 * WARUM EINE ATTRAPPE UND NICHT DER ECHTE DIENST. Zwei Gruende, und beide
 * wiegen:
 *
 *   1  DER PRUEFSTAND HAT KEINEN NETZZUGANG zu photon.komoot.io. In der
 *      Arbeitsumgebung setzt die Egress-Sperre Chromiums TLS-Handschlag
 *      zurueck (derselbe Befund wie bei den Kartenkacheln, F-P3-AC). Eine
 *      Probe, die auf eine Antwort wartet, die nie kommt, misst nur ihre
 *      eigene Zeitgrenze.
 *
 *   2  EINE PRUEFUNG BRAUCHT EINE ZAHL. „Die Liste zeigt Adressen" ist keine
 *      Aussage; „die Liste zeigt vier Adressen unter zwei Stammdatentreffern"
 *      ist eine. Der echte Dienst liefert je nach Bestand und Tagesform
 *      verschieden viele Treffer — damit waere jeder Sollwert geraten.
 *
 * WAS SIE NICHT ERSETZT: den Beweis, dass der echte Dienst antwortet und wie.
 * Das steht als Punkt auf der Prueflliste des Auftraggebers (ein echter
 * Photon-Treffer am Gerät) und im Prüfdokument unter „was nicht geprüft
 * werden konnte".
 *
 * DIE ERFUNDENEN ORTE folgen dem Referenzbestand (Talwang, Westried,
 * Steinach, Auwiesen, Felsberg, Nordstadt, Sonnenau) — keine echten Orte,
 * dieselbe Regel wie in tools/referenzdatensatz/quelldaten/pruefen.py.
 */

/* Der Katalog. Jede Zeile ist ein Photon-Merkmal, wie der Dienst es liefert:
 * `properties` mit name/street/housenumber/postcode/city, `geometry` mit
 * [lon, lat]. Gefiltert wird ueber Namen, Strasse und Ort — wie eine
 * Adresssuche es taete. */
const KATALOG = [
  { name: 'Klinikum Westried',        street: 'Ahornweg',      housenumber: '4',  postcode: '87001', city: 'Westried',  lon: 10.310, lat: 47.720 },
  { name: 'Klinik Talwang',           street: 'Bergstraße',    housenumber: '12', postcode: '87002', city: 'Talwang',   lon: 10.280, lat: 47.690 },
  { name: 'Kreisklinik Steinach',     street: 'Am Anger',      housenumber: '2',  postcode: '87003', city: 'Steinach',  lon: 10.350, lat: 47.740 },
  { name: 'Unfallklinik Felsberg',    street: 'Felsweg',       housenumber: '7',  postcode: '87004', city: 'Felsberg',  lon: 10.400, lat: 47.660 },
  { name: 'Talwanger Hauptplatz',     street: 'Hauptplatz',    housenumber: '1',  postcode: '87002', city: 'Talwang',   lon: 10.282, lat: 47.688 },
  { name: 'Westrieder Seeweg',        street: 'Seeweg',        housenumber: '18', postcode: '87001', city: 'Westried',  lon: 10.305, lat: 47.725 },
  { name: 'Auwiesen Nord',            street: 'Wiesenstraße',  housenumber: '33', postcode: '87005', city: 'Auwiesen',  lon: 10.240, lat: 47.770 },
  { name: 'Nordstadt Marktplatz',     street: 'Marktplatz',    housenumber: '5',  postcode: '87006', city: 'Nordstadt', lon: 10.210, lat: 47.800 },
  { name: 'Sonnenauer Höhenweg',      street: 'Höhenweg',      housenumber: '9',  postcode: '87007', city: 'Sonnenau',  lon: 10.450, lat: 47.640 },
  { name: 'Steinacher Bahnhofstraße', street: 'Bahnhofstraße', housenumber: '21', postcode: '87003', city: 'Steinach',  lon: 10.352, lat: 47.742 },
];

/* Dieselbe Obergrenze wie die echte Abfrage: `limit=6` (ortsfeld.js). Wer sie
 * hier hoeher setzte, bekaeme Sollzahlen, die im Betrieb nie entstehen. */
export const GRENZE = 6;

export function treffer(q) {
  const s = String(q || '').trim().toLowerCase();
  if (s === '') { return []; }
  return KATALOG.filter(e =>
    e.name.toLowerCase().includes(s)
    || e.street.toLowerCase().includes(s)
    || e.city.toLowerCase().includes(s)).slice(0, GRENZE);
}

function merkmal(e) {
  return {
    type: 'Feature',
    geometry: { type: 'Point', coordinates: [e.lon, e.lat] },
    properties: {
      name: e.name, street: e.street, housenumber: e.housenumber,
      postcode: e.postcode, city: e.city, countrycode: 'DE',
    },
  };
}

/** Vorwaertssuche: was `…/api/?q=…` liefert. */
export function antwortSuche(q) {
  return { type: 'FeatureCollection', features: treffer(q).map(merkmal) };
}

/** Umkehrsuche: was `…/reverse?lat=…&lon=…` liefert — immer genau ein Treffer. */
export function antwortUmkehr() {
  return { type: 'FeatureCollection', features: [merkmal(KATALOG[4])] };
}

/* Die Adressen, gegen die Photon heute fest verdrahtet ist (ortsfeld.js:82,
 * ortswahl.js:34). Ab AP2 traegt die Installation ihre eigene; das Muster
 * kommt dann aus der Einstellung, dieser Eintrag bleibt als Vorgabe. */
export const MUSTER = ['**photon.komoot.io/**', '**/photon.komoot.io/**'];

/** Playwright-Route: beantwortet jede Anfrage an den Geocoder aus dem Katalog. */
export async function route(r) {
  const url = new URL(r.request().url());
  const umkehr = url.pathname.includes('reverse');
  const q = url.searchParams.get('q') || '';
  await r.fulfill({
    status: 200,
    contentType: 'application/json; charset=utf-8',
    body: JSON.stringify(umkehr ? antwortUmkehr() : antwortSuche(q)),
  });
}
