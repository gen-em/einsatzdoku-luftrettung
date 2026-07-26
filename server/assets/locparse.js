/* Einsatzort-Widget — Formaterkennung fuer Koordinaten und Plus Codes.
 *
 * Reine Funktionen ohne DOM- oder Fetch-Zugriff, damit die Regeln isoliert
 * (z. B. per Konsole oder Node) pruefbar sind. Erkannt werden Dezimalgrad,
 * Grad/Dezimalminuten (GDM) und Open-Location-Code-Vollcodes (Plus Codes);
 * alles Weitere faellt auf die bestehende Adresssuche zurueck.
 *
 * Reihenfolge der Pruefung (erste Uebereinstimmung gewinnt): Plus Code
 * (kollisionsfreier Zeichensatz) -> Grad/Dezimalminuten (enthaelt stets
 * Hemisphaeren-Buchstaben) -> Dezimalgrad -> sonst Adresse. Regex-Aufbau
 * bewusst defensiv: erst harte Ausschluesse, dann Match — eine faelschlich
 * als Koordinate erkannte Adresse waere der schlimmere Fehler, weil sie die
 * Adresssuche unterdrueckt.
 *
 * Rueckgabe von erkenneEinsatzort():
 *   {typ: null}                                    kein Format erkannt (-> Adresssuche)
 *   {typ: 'ungueltig'}                              Format erkannt, Wert ausserhalb des gueltigen Bereichs
 *   {typ: 'plus-kurz'}                              Plus-Code-Kurzform (kein Vollcode)
 *   {typ:'plus'|'gdm'|'dezimal', lat, lon, anzeige} erfolgreich erkannt und umgerechnet
 *
 * Benoetigt fuer den Plus-Code-Zweig die gevendorte Bibliothek
 * assets/openlocationcode.js (globales Objekt OpenLocationCode). Fehlt sie
 * (z. B. in einer isolierten Testumgebung), greift dieser Zweig einfach
 * nicht — GDM und Dezimalgrad funktionieren unabhaengig davon.
 */
(function () {
  'use strict';

  /** Eingabe vor jeder Pruefung: trimmen, Mehrfach-Leerraum auf eines reduzieren. */
  function normalisiereEingabe(s) {
    return String(s || '').trim().replace(/\s+/g, ' ');
  }

  // ---------------------------------------------------------------------
  // 4.1 Plus Code (Vollcode)
  // ---------------------------------------------------------------------

  function erkennePlusCode(s) {
    if (typeof OpenLocationCode === 'undefined') return null;
    // Vollcode: nur werten, wenn die GESAMTE Eingabe der Code ist (kein
    // umgebender Text) — hier wuerden sonst automatisch Koordinaten gesetzt.
    if (OpenLocationCode.isValid(s) && OpenLocationCode.isFull(s)) {
      const bereich = OpenLocationCode.decode(s);
      return {
        typ: 'plus',
        lat: bereich.latitudeCenter,
        lon: bereich.longitudeCenter,
        anzeige: s.toUpperCase(),
      };
    }
    // Kurzform: in der Praxis ueblicherweise mit angehaengtem Referenzort
    // kopiert (z. B. aus einer Karten-App: "4HJM+7Q Kempten"). Da hier keine
    // Koordinaten gesetzt werden (nur ein Hinweistext), ist die Erkennung
    // bewusst toleranter — geprueft wird das erste Leerraum-getrennte Token.
    if (OpenLocationCode.isValid(s) && OpenLocationCode.isShort(s)) {
      return { typ: 'plus-kurz' };
    }
    const erstesToken = s.split(' ')[0];
    if (erstesToken !== s && OpenLocationCode.isValid(erstesToken) && OpenLocationCode.isShort(erstesToken)) {
      return { typ: 'plus-kurz' };
    }
    return null;
  }

  // ---------------------------------------------------------------------
  // Gemeinsame Hilfsfunktion fuer 4.2 und 4.3: aus zwei Rohkomponenten
  // (Zahl + optionale Hemisphaere) lat/lon inkl. Vorzeichen/Vertauschung
  // bestimmen und den Wertebereich pruefen.
  // ---------------------------------------------------------------------

  /**
   * @returns {{lat:number, lon:number}|'ungueltig'}
   */
  function baueAusKomponenten(zahl1, hemi1, zahl2, hemi2) {
    hemi1 = hemi1 ? hemi1.toUpperCase() : null;
    hemi2 = hemi2 ? hemi2.toUpperCase() : null;
    let latZahl, latH, lonZahl, lonH;
    // Reihenfolge "E/O/W zuerst, N/S danach": Werte tauschen.
    if (hemi1 && 'EOW'.includes(hemi1)) {
      lonZahl = zahl1; lonH = hemi1;
      latZahl = zahl2; latH = hemi2;
    } else {
      latZahl = zahl1; latH = hemi1;
      lonZahl = zahl2; lonH = hemi2;
    }
    let lat = latZahl;
    let lon = lonZahl;
    if (latH === 'S') lat = -Math.abs(lat);
    else if (latH === 'N') lat = Math.abs(lat);
    if (lonH === 'W') lon = -Math.abs(lon);
    else if (lonH === 'E' || lonH === 'O') lon = Math.abs(lon);

    if (Math.abs(lat) > 90 || Math.abs(lon) > 180) return 'ungueltig';
    return { lat, lon };
  }

  function anzeigeDezimal(lat, lon) {
    return `${lat.toFixed(5)}, ${lon.toFixed(5)}`;
  }

  // ---------------------------------------------------------------------
  // 4.3 Grad/Dezimalminuten (GDM) — vor Dezimalgrad pruefen, da GDM-Eingaben
  // Ziffernpaare enthalten, die sonst als Dezimalgrad fehlinterpretiert
  // werden koennten. Pflicht: beide Komponenten benoetigen einen
  // Hemisphaeren-Buchstaben — das Unterscheidungsmerkmal ggue. Dezimalgrad.
  // ---------------------------------------------------------------------

  // Eine Komponente hat GENAU EINEN Hemisphaeren-Buchstaben, entweder vorn
  // oder hinten — deshalb als zwei sich ausschliessende Alternativen
  // formuliert (nicht als zwei unabhaengig optionale Gruppen). Das
  // verhindert, dass z. B. bei "N47 43.57 E010 19.02" der Buchstabe "E" der
  // zweiten Komponente faelschlich als (zusaetzlicher) Suffix der ersten
  // gelesen wird, und dass eine reine Dezimalzahl wie "47.7261 N" ueber
  // einen Nullbreite-Trenner zwischen Grad und Minuten in zwei GDM-Teile
  // zerlegt wird: der Grad/Minuten-Trenner muss daher nicht-leer sein
  // (Grad-Zeichen ODER mindestens ein Leerzeichen).
  const GDM_TRENNER = '(?:\\s*[°\\u00B0]\\s*|\\s+)';
  const GDM_MINUTEN = '(\\d{1,2}(?:[.,]\\d+)?)\\s*[\'\\u2032\\u2019]?';
  const GDM_PRAEFIX = '([NSEOWnseow])\\s*(\\d{1,3})' + GDM_TRENNER + GDM_MINUTEN;
  const GDM_SUFFIX = '(\\d{1,3})' + GDM_TRENNER + GDM_MINUTEN + '\\s*([NSEOWnseow])';
  // 6 Gruppen je Komponente (3 je Alternative); nicht getroffene bleiben
  // undefined, zaehlen aber fuer die Positionsnummerierung mit.
  const GDM_KOMPONENTE = '(?:' + GDM_PRAEFIX + '|' + GDM_SUFFIX + ')';
  const GDM_MUSTER = new RegExp(
    '^' + GDM_KOMPONENTE + '\\s*[,;]?\\s*' + GDM_KOMPONENTE + '$', 'i'
  );

  function erkenneGdm(s) {
    const m = s.match(GDM_MUSTER);
    if (!m) return null;
    let [, hVorn1, grad1, min1, grad1s, min1s, hHinten1,
            hVorn2, grad2, min2, grad2s, min2s, hHinten2] = m;
    const hemi1 = (hVorn1 || hHinten1 || '').toUpperCase() || null;
    const hemi2 = (hVorn2 || hHinten2 || '').toUpperCase() || null;
    grad1 = grad1 || grad1s; min1 = min1 || min1s;
    grad2 = grad2 || grad2s; min2 = min2 || min2s;
    if (!hemi1 || !hemi2) return null; // Pflichtfeld fehlt -> kein GDM, koennte Adresse sein

    const g1 = parseInt(grad1, 10);
    const g2 = parseInt(grad2, 10);
    const min1f = parseFloat(min1.replace(',', '.'));
    const min2f = parseFloat(min2.replace(',', '.'));
    if (min1f >= 60 || min2f >= 60) return { typ: 'ungueltig' };

    const zahl1 = g1 + min1f / 60;
    const zahl2 = g2 + min2f / 60;
    const res = baueAusKomponenten(zahl1, hemi1, zahl2, hemi2);
    if (res === 'ungueltig') return { typ: 'ungueltig' };
    return { typ: 'gdm', lat: res.lat, lon: res.lon, anzeige: anzeigeDezimal(res.lat, res.lon) };
  }

  // ---------------------------------------------------------------------
  // 4.2 Dezimalgrad
  // ---------------------------------------------------------------------

  // Eine Komponente mit Punkt-Dezimaltrenner, optionaler Hemisphaere danach.
  const DEZ_KOMPONENTE_PUNKT = '([+-]?\\d+(?:\\.\\d+)?)\\s*([NSEOWnseow])?';
  const DEZ_MUSTER_PUNKT = new RegExp(
    '^' + DEZ_KOMPONENTE_PUNKT + '\\s*[,;]?\\s*' + DEZ_KOMPONENTE_PUNKT + '$'
  );

  // Eine Komponente mit Komma-Dezimaltrenner, optionaler Hemisphaere danach.
  const DEZ_KOMPONENTE_KOMMA = '([+-]?\\d+(?:,\\d+)?)\\s*([NSEOWnseow])?';
  // Trenner zwischen den beiden Komponenten ist hier PFLICHT (Leerraum oder
  // Semikolon) — sonst waere "47,7261,10,3170" nicht von einer einzelnen
  // Dezimalkomma-Zahl mit vier Gruppen zu unterscheiden (4.2, Regel 2/3).
  const DEZ_MUSTER_KOMMA = new RegExp(
    '^' + DEZ_KOMPONENTE_KOMMA + '(?:;\\s*|\\s+)' + DEZ_KOMPONENTE_KOMMA + '$'
  );

  function erkenneDezimalgrad(s) {
    const enthaeltPunkt = s.includes('.');
    let m;
    let zahl1, zahl2;

    if (enthaeltPunkt) {
      m = s.match(DEZ_MUSTER_PUNKT);
      if (!m) return null;
      zahl1 = parseFloat(m[1]);
      zahl2 = parseFloat(m[3]);
    } else if (s.includes(',')) {
      m = s.match(DEZ_MUSTER_KOMMA);
      if (!m) return null; // mehrdeutige Kommakette o. ae. -> Adresssuche
      zahl1 = parseFloat(m[1].replace(',', '.'));
      zahl2 = parseFloat(m[3].replace(',', '.'));
    } else {
      // weder Punkt noch Komma: nur gueltig, wenn beide Komponenten einen
      // Hemisphaeren-Buchstaben tragen (sonst keine Nachkommastelle UND
      // keine Hemisphaere -> laut 4.2 keine Koordinate, z. B. Hausnummern).
      m = s.match(DEZ_MUSTER_PUNKT); // Muster passt strukturell auch ohne Punkt
      if (!m) return null;
      if (!m[2] || !m[4]) return null;
      zahl1 = parseFloat(m[1]);
      zahl2 = parseFloat(m[3]);
    }

    const hemi1 = m[2] || null;
    const hemi2 = m[4] || null;
    // Weder Hemisphaere noch Dezimalteil -> reine Ganzzahl (z. B. PLZ,
    // Hausnummer): laut 4.2 keine Koordinate, also ablehnen.
    const hatDezimalteil1 = /[.,]/.test(m[1]);
    const hatDezimalteil2 = /[.,]/.test(m[3]);
    if (!hatDezimalteil1 && !hemi1) return null;
    if (!hatDezimalteil2 && !hemi2) return null;

    const res = baueAusKomponenten(zahl1, hemi1, zahl2, hemi2);
    if (res === 'ungueltig') return { typ: 'ungueltig' };
    return { typ: 'dezimal', lat: res.lat, lon: res.lon, anzeige: anzeigeDezimal(res.lat, res.lon) };
  }

  // ---------------------------------------------------------------------
  // Einstiegspunkt
  // ---------------------------------------------------------------------

  /**
   * @param {string} eingabe Rohtext aus dem Einsatzort-Feld.
   * @returns {{typ:'plus'|'plus-kurz'|'gdm'|'dezimal'|'ungueltig'|null,
   *            lat?:number, lon?:number, anzeige?:string}}
   */
  function erkenneEinsatzort(eingabe) {
    const s = normalisiereEingabe(eingabe);
    if (s === '') return { typ: null };

    const plus = erkennePlusCode(s);
    if (plus) return plus;

    const gdm = erkenneGdm(s);
    if (gdm) return gdm;

    const dez = erkenneDezimalgrad(s);
    if (dez) return dez;

    return { typ: null };
  }

  const api = { erkenneEinsatzort };
  if (typeof module !== 'undefined' && module.exports) {
    module.exports = api; // Node — fuer isolierte Tests der reinen Funktionen
  }
  if (typeof window !== 'undefined') {
    window.EdLoc = api;
  }
})();
