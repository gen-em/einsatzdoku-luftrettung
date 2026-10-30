/* GPX — Bauen und Lesen im Browser.
 * ==========================================================================
 *
 * WOFUER. Die GPS-Daten liegen verschlossen auf dem Server
 * (`track_stuecke`); oeffnen kann sie nur der Browser (`assets/ortsdaten.js`).
 * Damit gehoert die GPX-Datei hierher, in beide Richtungen:
 *   - BAUEN fuer den Abruf einer Spur oder einer Auswahl (Einsatzansicht,
 *     „GPS-Daten des Diensttages"). Der Server koennte es nicht: Er sieht
 *     nur verschlossene Stuecke.
 *   - LESEN fuer den Import. Der Browser liest die Datei, und der Server
 *     bekommt nur verschlossene Stuecke.
 *
 * EIN FESTES FORMAT. Aufbau, Beschreibungen, Stufentext und Dateinamen
 * stehen Zeile fuer Zeile in `docs/Export-Format.md` 3.5, und wer dieselbe
 * Spur zweimal laedt, soll dieselbe Datei bekommen. Ebenso fest sind die
 * Regeln des Lesens: UTF-8 Pflicht, Kodierungsdeklaration nur ASCII-vertraeglich, keine
 * Dokumenttyp-Deklaration, `time` Pflicht, Punkte nach Zeit sortiert.
 *
 *   EdGpx.bauenViele(spuren, name, erzeugtAm)   spuren: [{punkte, name, stufe, nOriginal}]
 *   EdGpx.bauen(punkte, name, stufe, nOriginal)
 *   EdGpx.spurname(art, id, vonTs)              „Einsatz 12 — 16.10.2026 08:15"
 *   EdGpx.dateiname(art, id, vonTs, stufe)
 *   EdGpx.dateinameTag(datum, anzahl, stufen)
 *   EdGpx.speichern(text, dateiname)            als Datei anbieten
 *   EdGpx.lesen(arrayBuffer)                    {punkte, name, segmente, ohneZeit, verworfen}
 *
 * Punkte haben die Form aus `EdOrtsdaten.spur()`: {lat, lon, ele, ts}.
 * Braucht: EdFormat (format.js).
 */
(function () {
  'use strict';

  const FASSUNG = '1.1';
  const NS = 'http://www.topografix.com/GPX/1/1';
  const CREATOR = 'Gen-EM NAdoku';
  const STUFE_DUENN = 3;
  /* Groesste Datei, die angenommen wird — wie `GPX_DATEI_MAX` bis 22.2.0. */
  const DATEI_MAX = 12 * 1024 * 1024;
  /* `LIMIT_TRACKPUNKTE_SPUR` (validate_lib.php); der Server prueft es noch
     einmal ueber die Stuecke. */
  const PUNKTE_MAX = 50000;
  /* Die Toleranzen der Ausduennung (`SPUR_TOL_WAAGERECHT_M`, `…_SENKRECHT_M`)
     fuer den Stufentext; die Seiten reichen sie als `GPX_TOL` herein. */
  const tol = () => (typeof GPX_TOL !== 'undefined' && GPX_TOL) ? GPX_TOL : [2, 3];

  function creator() {
    const v = document.documentElement.getAttribute('data-webversion');
    return CREATOR + (v ? ' ' + v : '');
  }

  /** XML-Maskierung wie `htmlspecialchars(…, ENT_QUOTES | ENT_XML1)`. */
  function e(s) {
    return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
      .replace(/"/g, '&quot;').replace(/'/g, '&apos;');
  }

  /** `iso_utc()`: 'Y-m-d\TH:i:s\Z'. */
  function isoUtc(ts) {
    return new Date((ts == null ? Math.floor(Date.now() / 1000) : ts) * 1000)
      .toISOString().replace(/\.\d{3}Z$/, 'Z');
  }

  /* Eine Zahl, wie PHP sie in eine Zeichenkette setzt — kurz, mit Punkt.
     Nur eine Exponentschreibweise (1e-7) waere kein xsd:decimal; die gibt es
     bei Koordinaten praktisch nicht, und dann eben mit sieben Stellen. */
  function zahl(x) {
    const s = String(x);
    return /e/i.test(s) ? Number(x).toFixed(7).replace(/0+$/, '').replace(/\.$/, '') : s;
  }

  function stufeText(stufe) { return stufe === STUFE_DUENN ? 'ausgedünnt' : 'Originalspur'; }
  function stufeMarke(stufe) { return stufe === STUFE_DUENN ? 'ausgeduennt' : 'original'; }

  /* Toleranz wie `number_format(x, 1, ',', '')` und nachlaufende Nullen weg. */
  function tolText(x) { return Number(x).toFixed(1).replace('.', ',').replace(/0+$/, '').replace(/,$/, ''); }

  function beschreibung(anzahl, stufe, nOriginal) {
    const t = stufeText(stufe);
    if (stufe === STUFE_DUENN && nOriginal != null && nOriginal > anzahl) {
      const [w, s] = tol();
      return t + ' — ' + anzahl + ' von ursprünglich ' + nOriginal + ' Punkten '
        + '(Douglas-Peucker, ' + tolText(w) + ' m waagerecht / ' + tolText(s) + ' m senkrecht)';
    }
    return t + ' — ' + anzahl + ' Punkte';
  }

  function trk(punkte, name, beschr) {
    let x = '<trk><name>' + e(name) + '</name><desc>' + e(beschr) + '</desc><trkseg>';
    for (const p of punkte) {
      x += '<trkpt lat="' + zahl(p.lat) + '" lon="' + zahl(p.lon) + '">';
      if (p.ele != null) { x += '<ele>' + zahl(p.ele) + '</ele>'; }
      x += '<time>' + isoUtc(p.ts) + '</time></trkpt>';
    }
    return x + '</trkseg></trk>\n';
  }

  function bauenViele(spuren, name, erzeugtAm) {
    let bloecke = '', anzahl = 0, punkte = 0, einzeln = '';
    const stufen = new Set();
    for (const s of spuren) {
      const n = s.punkte.length;
      const b = beschreibung(n, s.stufe, s.nOriginal);
      bloecke += trk(s.punkte, s.name, b);
      anzahl++; punkte += n; stufen.add(s.stufe); einzeln = b;
    }
    const beschr = anzahl === 1 ? einzeln
      : anzahl + ' Aufzeichnungen — ' + punkte + ' Punkte insgesamt · '
        + (stufen.size > 1 ? 'teils ausgedünnt — jede Aufzeichnung nennt ihre Stufe'
          : (stufen.has(STUFE_DUENN) ? 'alle ausgedünnt' : 'alle im Original'));
    return '<?xml version="1.0" encoding="UTF-8"?>\n'
      + '<gpx version="' + FASSUNG + '" creator="' + e(creator()) + '" xmlns="' + NS + '">\n'
      + '<metadata><name>' + e(name) + '</name><desc>' + e(beschr) + '</desc>'
      + '<time>' + isoUtc(erzeugtAm) + '</time></metadata>\n'
      + bloecke + '</gpx>\n';
  }

  function bauen(punkte, name, stufe, nOriginal) {
    return bauenViele([{ punkte, name, stufe, nOriginal }], name);
  }

  /* „Einsatz 12 — 16.10.2026 08:15" — `Export-Format.md` 3.5; die Ortszeit
     der Anlage wie `datum_zeit_text($von, ' ')`. */
  function spurname(art, id, vonTs, tz) {
    const n = (art === 'mission' ? 'Einsatz ' : 'Ruhezeit ') + id;
    if (vonTs == null) { return n; }
    const d = new Intl.DateTimeFormat('de-DE', { timeZone: tz || undefined, day: '2-digit',
      month: '2-digit', year: 'numeric' }).format(new Date(vonTs * 1000));
    return n + ' — ' + d + ' ' + EdFormat.uhrzeit(vonTs, tz);
  }

  /** `einsatz_000012_2026-10-16_0615_original.gpx` — Zeit in UTC, wie bisher. */
  function dateiname(art, id, vonTs, stufe) {
    let zeit = '';
    if (vonTs != null) {
      const i = new Date(vonTs * 1000).toISOString();
      zeit = '_' + i.slice(0, 10) + '_' + i.slice(11, 13) + i.slice(14, 16);
    }
    const name = (art === 'rest' ? 'ruhezeit' : 'einsatz') + '_' + String(id).padStart(6, '0')
      + zeit + '_' + stufeMarke(stufe) + '.gpx';
    return name.replace(/[^A-Za-z0-9._-]/g, '') || 'spur.gpx';
  }

  function dateinameTag(datum, anzahl, stufen) {
    const marke = new Set(stufen).size > 1 ? 'gemischt' : stufeMarke(stufen.length ? stufen[0] : 1);
    const name = 'diensttag_' + (datum || 'ohne-datum') + '_' + anzahl + '-spuren_' + marke + '.gpx';
    return name.replace(/[^A-Za-z0-9._-]/g, '') || 'spuren.gpx';
  }

  /** Den Text als Datei anbieten — `application/gpx+xml`, wie `gpx.php` es tat. */
  function speichern(text, name) {
    const url = URL.createObjectURL(new Blob([text], { type: 'application/gpx+xml' }));
    const a = document.createElement('a');
    a.href = url; a.download = name;
    document.body.appendChild(a);
    a.click();
    a.remove();
    setTimeout(() => URL.revokeObjectURL(url), 60000);
  }

  /* ---- Lesen ----------------------------------------------------------- */

  function fehler(text) { const x = new Error(text); x.gpx = true; return x; }

  /** `is_numeric()` in PHP 8: Vorzeichen, Dezimalzahl, Exponent, Leerraum. */
  const NUMERISCH = /^\s*[+-]?(\d+(\.\d*)?|\.\d+)([eE][+-]?\d+)?\s*$/;

  function koordinate(wert, grenze) {
    if (wert === null || wert === '' || !NUMERISCH.test(wert)) { return null; }
    const f = Number(wert);
    return (!isFinite(f) || f < -grenze || f > grenze) ? null : f;
  }

  /* Die Zeit wie `strtotime()` fuer xsd:dateTime: ohne Zonenangabe gilt UTC
     (die Anlage rechnet in UTC), Sekundenbruchteile fallen ab. */
  function zeit(text) {
    let t = text.trim();
    if (t === '') { return null; }
    if (/^\d{4}-\d{2}-\d{2}T\d{2}:\d{2}(:\d{2}(\.\d+)?)?$/.test(t)) { t += 'Z'; }
    const ms = Date.parse(t);
    return isNaN(ms) ? null : Math.floor(ms / 1000);
  }

  function lesen(puffer) {
    const roh = new Uint8Array(puffer);
    if (roh.length > DATEI_MAX) {
      throw fehler('Die Datei ist ' + (roh.length / 1048576).toFixed(1) + ' MB gross; angenommen werden '
        + Math.floor(DATEI_MAX / 1048576) + ' MB.');
    }
    if (roh.indexOf(0) !== -1) {
      throw fehler('Die Datei enthält ein Nullbyte und ist damit kein Text. Das deutet '
        + 'auf eine Kodierung wie UTF-16 hin — bitte als UTF-8 speichern und erneut versuchen.');
    }
    /* NICHT STRENG, MIT ABSICHT. Dieser Decoder tut, was `readAsText()` tut:
       Eine Latin-1-Datei kommt mit Ersatzzeichen an und wird uebernommen
       (die Koordinaten sind ASCII). */
    let xml = new TextDecoder('utf-8', { ignoreBOM: true }).decode(roh);
    if (xml.trim() === '' || xml.replace(/^﻿/, '').trim() === '') { throw fehler('Die Datei ist leer.'); }
    const kod = /^﻿?\s*<\?xml\b[^>]*\bencoding\s*=\s*["']([^"']*)["']/i.exec(xml);
    if (kod) {
      const name = kod[1].trim().toLowerCase();
      const vertraeglich = /^(?:utf-?8|us-?ascii|ascii|iso[-_]?8859[-_]?(?:[1-9]|1[0-6])|latin-?(?:[1-9]|10)|l[1-9]|windows-?125[0-8]|cp-?125[0-8]|macintosh|mac-?roman|koi8-?[ru])$/.test(name);
      if (!vertraeglich) {
        throw fehler('Die Datei nennt die Kodierung „' + kod[1].replace(/[^A-Za-z0-9._-]/g, '').slice(0, 20)
          + '". GPX schreibt UTF-8 vor — bitte so speichern und erneut versuchen.');
      }
      xml = xml.replace(/^(﻿?\s*<\?xml\b[^>]*\bencoding\s*=\s*)(["'])[^"']*\2/i, '$1$2UTF-8$2');
    }
    if (/<!DOCTYPE/i.test(xml)) {
      throw fehler('Die Datei enthält eine Dokumenttyp-Deklaration. GPX braucht keine, '
        + 'und angenommen wird sie deshalb nicht.');
    }
    const doc = new DOMParser().parseFromString(xml.replace(/^﻿/, ''), 'application/xml');
    const kaputt = doc.getElementsByTagName('parsererror')[0];
    if (kaputt) {
      /* Chromium legt den eigentlichen Satz in ein <div> unter einen Kopfsatz
         („This page contains the following errors"); Firefox schreibt ihn
         als ersten Text. */
      const quelle = kaputt.querySelector('div') || kaputt;
      const zeile = (quelle.textContent || '').trim().split('\n').filter(Boolean)[0] || 'unbekannter Fehler';
      throw fehler('Die Datei ist kein gültiges XML (' + zeile.slice(0, 160) + ').');
    }
    const wurzel = doc.documentElement;
    if ((wurzel.localName || '').toLowerCase() !== 'gpx') {
      throw fehler('Das ist keine GPX-Datei — das Wurzelelement heißt <' + wurzel.localName + '> statt <gpx>.');
    }
    /* DER NAMENSRAUM WIRD GENOMMEN, WIE ER KOMMT (GPX 1.0, 1.1 oder keiner). */
    const haupt = wurzel.namespaceURI || '';
    const kinder = (el, name) => [...el.children].filter(c => c.localName === name
      && (c.namespaceURI || '') === haupt);
    const text = (el, name) => { const k = kinder(el, name)[0]; return k ? k.textContent : ''; };

    const punkte = [];
    let segmente = 0, ohneZeit = 0, verworfen = 0, name = null;
    const meta = kinder(wurzel, 'metadata')[0];
    if (meta && text(meta, 'name') !== '') { name = text(meta, 'name'); }
    for (const t of kinder(wurzel, 'trk')) {
      if (name === null && text(t, 'name') !== '') { name = text(t, 'name'); }
      for (const seg of kinder(t, 'trkseg')) {
        segmente++;
        for (const pt of kinder(seg, 'trkpt')) {
          const la = koordinate(pt.getAttribute('lat'), 90);
          const lo = koordinate(pt.getAttribute('lon'), 180);
          if (la === null || lo === null) { verworfen++; continue; }
          const ts = zeit(text(pt, 'time'));
          if (ts === null) { ohneZeit++; continue; }
          const ele = text(pt, 'ele').trim();
          const h = ele === '' ? null : parseFloat(ele);
          punkte.push({ lat: la, lon: lo, ele: ele === '' ? null : (isNaN(h) ? 0 : h), ts });
        }
      }
    }
    if (!punkte.length && ohneZeit > 0) {
      throw fehler('Kein einziger der ' + ohneZeit + ' Punkte hat einen Zeitstempel. Ohne <time> gibt '
        + 'es keine Reihenfolge, kein Schneiden und keine Phasenzeiten — die Datei wird deshalb nicht angenommen.');
    }
    if (!punkte.length) {
      throw fehler('Die Datei enthält keinen einzigen Trackpunkt (<trkpt> in <trk><trkseg>). '
        + 'Wegpunkte und Routen liest dieser Import nicht.');
    }
    if (punkte.length > PUNKTE_MAX) {
      throw fehler('Die Datei hat ' + punkte.length + ' Punkte; angenommen werden bis zu ' + PUNKTE_MAX
        + '. Das sind ' + (PUNKTE_MAX / 3600).toFixed(1) + ' Stunden bei einem Punkt je Sekunde.');
    }
    /* Nach Zeit sortieren (stabil) und die Sequenz neu vergeben. */
    punkte.sort((a, b) => a.ts - b.ts);
    punkte.forEach((p, i) => { p.seq = i; });
    return { punkte, name, segmente, ohneZeit, verworfen };
  }

  window.EdGpx = { bauenViele, bauen, spurname, dateiname, dateinameTag, speichern, lesen };
})();
