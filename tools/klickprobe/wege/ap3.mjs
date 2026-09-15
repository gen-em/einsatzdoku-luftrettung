/* Wege des Arbeitspakets AP3 — Karte und Zeichen (E-S9-03, -04, -12, -13).
 * ===========================================================================
 *
 *   P-08  Schildmasse nachgemessen              Soll 32/32/32/38/28/14/20 px
 *   P-10  Pfeile zeigen in Spurrichtung         Soll 12 von 12 auf 0,1 Grad
 *   P-12  Wording „GPS-Daten"                   Soll 0 sichtbare „Spur"
 *   Nr.153 Der Abfahrtort-Punkt hat einen Kasten
 *   E-S9-13 Artzeichen: sechs Zeichen, ein Satz
 *
 * WARUM DIE SCHILDER HIER GEMESSEN WERDEN UND NICHT IM BILDERLAUF: Der
 * fotografiert Seiten und misst Ueberlauf, Konsolenfehler und Knopfhoehen.
 * Ein Kartenzeichen ist kein Knopf (R76 gilt fuer es nicht, S3/AP7), und
 * seine Groesse steht auf keinem Bild als Zahl. Das Konzept verlangt
 * „nachgemessen im Browser" — das ist dieser Weg.
 *
 * UND WARUM DIE PFEILE NICHT AUS DEM BERECHNETEN STIL GELESEN WERDEN:
 * `getComputedStyle` meldet die Drehmatrix auch dann, wenn sie gar nicht
 * wirkt (Inline-Element, Backlog Nr. 72). Gemessen wird deshalb die
 * BILDSCHIRMMATRIX des SVG (`getScreenCTM`) — sie beschreibt, was gezeichnet
 * wurde, nicht was dastand.
 */

/* Die Sollmasse aus M-S9-01 V1, freigegeben am 06.09.2026. Aussenmass
 * einschliesslich der Schattenringe. */
const SOLL = {
  ohne: 32, start: 32, ende: 32, beide: 38,
  kreis: 28, ringpunkt: 14, ringBeide: 20,
};

let EINSAETZE = null;
async function einsaetze(k) {
  if (EINSAETZE) { return EINSAETZE; }
  await k.gehZu(k.kennung.tagesuebersicht || (k.basis + '/index.php'));
  await k.seite.waitForFunction(
    () => typeof dayMissions !== 'undefined' && dayMissions.length, null, { timeout: 20000 });
  const l = await k.seite.evaluate(() => ({
    tag: currentDayId,
    liste: dayMissions.map(m => ({ id: m.id, spur: (m.track || []).length })),
  }));
  const mit = l.liste.find(m => m.spur > 1);
  if (!mit) { throw new Error('Kein Einsatz mit Aufzeichnung im Bestand'); }
  EINSAETZE = { tag: l.tag, mitSpur: mit.id, punkte: mit.spur };
  return EINSAETZE;
}

/**
 * Eine Karte suchen, auf der ein RINGPUNKT tatsaechlich steht.
 *
 * WARUM DAS SUCHEN NOETIG IST. `einsatz.php` zeichnet den Ring am Anfang und
 * am Ende der Spur nur dann, wenn dort nicht schon ein SCHILD steht
 * (`ringLos()`, Abstand NAH_M): Der Standort und die Zielklinik tragen ihren
 * Ring selbst. Beginnt und endet die Spur an einem Schild, gibt es keinen
 * freistehenden Ringpunkt — und dann laesst sich seine Antippflaeche nicht
 * messen.
 *
 * GENAU DAS IST MIT DEM DEMO-AUSBAU EINGETRETEN. Der Standort
 * „Notarztstandort Talwang" fuehrte bis dahin bewusst KEINE Koordinaten
 * (E-P1-02); seine Bodeneinsaetze hatten deshalb kein Standort-Schild und
 * immer einen freistehenden Ring am Spuranfang. Seit E-DA-09 hat er welche,
 * das Schild steht da, und der Ring faellt weg — richtig so, und fuer diesen
 * Weg der Grund, warum er nicht mehr den erstbesten Einsatz nehmen darf.
 *
 * Gesucht wird ueber `api/day.php`, nicht durch Anklicken: Der Weg misst ein
 * MASS, nicht einen Bedienpfad, und eine Suche ueber zwanzig Diensttage im
 * Browser waere teurer als der Weg selbst.
 */
async function ringpunktSuchen(k) {
  const tage = await k.seite.evaluate(async (basis) => {
    const r = await fetch(basis + '/api/day.php', { credentials: 'same-origin' });
    const j = await r.json();
    return (j.days || []).map(d => d.id);
  }, k.basis);
  for (const tag of tage) {
    const ids = await k.seite.evaluate(async ([basis, d]) => {
      const r = await fetch(basis + '/api/day.php?d=' + d, { credentials: 'same-origin' });
      const j = await r.json();
      return (j.missions || []).map(m => m.id);
    }, [k.basis, tag]);
    for (const id of ids) {
      await k.gehZu(k.basis + '/einsatz.php?id=' + id);
      await k.seite.waitForSelector('.leaflet-container', { timeout: 20000 }).catch(() => {});
      await k.seite.waitForTimeout(900);
      const px = await k.seite.evaluate(() => {
        const e = document.querySelector('.geo-ringpunkt-feld');
        if (!e) { return null; }
        const r = e.getBoundingClientRect();
        return Math.round(Math.min(r.width, r.height));
      });
      if (px !== null) { return { px, einsatz: id, tag }; }
    }
  }
  return { px: null, einsatz: null, tag: null };
}

/** Eine Seite mit Karte, damit die Marker-Regeln geladen sind. */
async function karteSeite(k) {
  const e = await einsaetze(k);
  await k.gehZu(k.basis + '/einsatz.php?id=' + e.mitSpur);
  await k.seite.waitForSelector('.leaflet-container', { timeout: 20000 }).catch(() => {});
  await k.seite.waitForTimeout(1200);
}

/**
 * Aussenmass eines Zeichens: Kasten plus groesster Schatten-Spread.
 *
 * Die Umschliessende (`getBoundingClientRect`) kennt den `box-shadow` NICHT —
 * wer nur sie misst, meldet fuer den Doppelrand 30 px statt 38 und haelt das
 * fuer eine Abweichung. Der Spread ist die vierte Laenge je Schattenangabe.
 */
function messen() {
  const halter = document.createElement('div');
  halter.style.cssText = 'position:absolute;left:-9999px;top:0';
  document.body.appendChild(halter);
  const sym = '<svg class="symbol" viewBox="0 0 24 24"></svg>';
  const aussen = (el) => {
    const r = el.getBoundingClientRect();
    let max = 0;
    for (const teil of getComputedStyle(el).boxShadow.split(/,(?![^()]*\))/)) {
      const zahlen = (teil.match(/-?[\d.]+px/g) || []).map(parseFloat);
      if (zahlen.length >= 4) { max = Math.max(max, zahlen[3]); }
    }
    return Math.round(r.width + 2 * max);
  };
  const bau = (html, wahl) => {
    const d = document.createElement('div');
    d.innerHTML = html; halter.appendChild(d);
    return aussen(d.querySelector(wahl));
  };
  const schild = (ring) => bau(
    '<span class="geo-schild' + (ring ? ' geo-ring-' + ring : '') + '">'
    + '<span class="geo-schild-kasten">' + sym + '</span></span>', '.geo-schild-kasten');
  const erg = {
    ohne: schild(''), start: schild('start'), ende: schild('ende'), beide: schild('beide'),
    kreis: bau('<span class="geo-kreis">' + sym + '</span>', '.geo-kreis'),
    ringpunkt: bau('<span class="geo-ringpunkt geo-ringpunkt-start"></span>', '.geo-ringpunkt'),
    ringBeide: bau('<span class="geo-ringpunkt geo-ringpunkt-beide"></span>', '.geo-ringpunkt'),
  };
  const s1 = halter.querySelector('.geo-schild-kasten .symbol');
  const s2 = halter.querySelector('.geo-kreis .symbol');
  erg.symbolSchild = s1 ? Math.round(s1.getBoundingClientRect().width) : null;
  erg.symbolKreis  = s2 ? Math.round(s2.getBoundingClientRect().width) : null;
  halter.remove();
  return erg;
}

export const wege = [
  {
    name: 'ap3-schildmasse',
    paket: 'AP3', punkt: 'P-08', rolle: 'demo',
    was: 'Die Kartenzeichen nach M-S9-01 V1 — Außenmaß einschließlich der Ringe',
    soll: 'ohne 32 · Start 32 · Ende 32 · beide 38 · Kreis 28 · Ringpunkt 14 · Ring beide 20 px',
    async fahren(k) {
      await karteSeite(k);
      const m = await k.seite.evaluate(messen);
      /* Und die Antippflaeche am ECHTEN Marker der Karte — sie ist
       * durchsichtig und steht in keinem gerechneten Kasten (WCAG 2.5.8,
       * 24 px; die Zeichnung darin bleibt 14). */
      const ring = await ringpunktSuchen(k);
      m.feld = ring.px;
      await k.bild('ap3-schildmasse');
      const ab = Object.entries(SOLL).filter(([n, v]) => m[n] !== v)
        .map(([n, v]) => n + ' ' + m[n] + ' statt ' + v);
      const symOk = m.symbolSchild === 18 && m.symbolKreis === 16;
      const feldOk = m.feld === 24;
      if (!feldOk) { ab.push('Antippfläche ' + m.feld + ' statt 24'); }
      return {
        ist: 'ohne ' + m.ohne + ' · Start ' + m.start + ' · Ende ' + m.ende
           + ' · beide ' + m.beide + ' · Kreis ' + m.kreis + ' · Ringpunkt ' + m.ringpunkt
           + ' · Ring beide ' + m.ringBeide + ' px · Symbol im Schild ' + m.symbolSchild
           + ', im Kreis ' + m.symbolKreis + ' px · Antippfläche des Ringpunkts '
           + m.feld + ' px (gemessen an Einsatz ' + (ring.einsatz ?? '—') + ')',
        ok: ab.length === 0 && symOk && feldOk,
        bemerkung: ab.join(', ') || (symOk ? '' : 'Symbolgröße: Soll 18 / 16 px'),
      };
    },
  },

  {
    name: 'ap3-pfeile-drehen',
    paket: 'AP3', punkt: 'P-10', rolle: 'demo',
    was: 'Die Richtungspfeile drehen sich — einmal ganz herum in 30-Grad-Schritten',
    soll: '12 von 12 auf 0,1 Grad, 8 von 8 schrägen mit größerem Umriss, dazu '
        + 'jeder Pfeil auf der Spur — Pfeile erwartet, sobald die Spur am Bildschirm '
        + 'länger als 280 px ist (geo.js, ABSTAND_PX × 2)',
    async fahren(k) {
      await karteSeite(k);
      const GRADE = [0, 30, 60, 90, 120, 150, 180, 210, 240, 270, 300, 330];
      /* WIE DER WINKEL GEMESSEN WIRD — und warum nicht mehr mit
         `getScreenCTM()` (AP3b, Nr. 186).

         Bis zum 14.09.2026 las dieser Weg die Drehung aus der
         Bildschirmmatrix des inneren `<svg>`. Das ist in Chromium und Gecko
         richtig und in WebKit falsch: WebKit rechnet die CSS-Transformation
         eines HTML-Vorfahren NICHT in die Matrix des SVG hinein. Gemessen,
         fuenf Winkel, drei Motoren:

             chromium/firefox   30° → getScreenCTM 30 · computed 30
             webkit             30° → getScreenCTM  0 · computed 30

         Die Pfeile DREHEN SICH also auch in WebKit — der Umriss waechst bei
         30° in allen drei Motoren von 16 auf 22 px. Falsch war die Messung,
         und sie meldete „1 von 12", was wie ein Anwendungsfehler aussah.

         Gemessen wird deshalb jetzt ZWEIERLEI, beides motorunabhaengig: die
         berechnete Matrix des drehenden Elements (die Drehung GILT) und der
         Umriss (sie wird auch GEZEICHNET — bei 30° muss der Kasten breiter
         sein als bei 0°). Beide Messungen liegen in EINEM `evaluate`, damit
         der Leser nur einmal dasteht. */
      const { mess, echt } = await k.seite.evaluate((GRADE) => {
        const winkel = (el) => {
          const t = getComputedStyle(el).transform;
          const m = t && t !== 'none' ? t.match(/matrix\(([^)]+)\)/) : null;
          const r = el.getBoundingClientRect();
          if (!m) { return { grad: 0, breite: Math.round(r.width) }; }
          const v = m[1].split(',').map(Number);
          const g = ((Math.atan2(v[1], v[0]) * 180 / Math.PI) % 360 + 360) % 360;
          return { grad: +g.toFixed(1), breite: Math.round(r.width) };
        };
        const halter = document.createElement('div');
        halter.style.cssText = 'position:absolute;left:-9999px;top:0;display:flex;gap:8px';
        document.body.appendChild(halter);
        GRADE.forEach(g => {
          const d = document.createElement('div');
          d.innerHTML = '<span class="geo-pfeil" style="transform:rotate(' + g + 'deg)">'
            + '<svg class="symbol" viewBox="0 0 24 24"></svg></span>';
          halter.appendChild(d);
        });
        const mess = Array.from(halter.querySelectorAll('.geo-pfeil')).map((e, i) => {
          const w = winkel(e);
          return { soll: GRADE[i], ist: w.grad, breite: w.breite };
        });
        halter.remove();
        const echt = Array.from(document.querySelectorAll('.geo-pfeil')).map(e => {
          const roh = parseFloat((e.getAttribute('style') || '').replace(/[^\-0-9.]/g, '')) || 0;
          const w = winkel(e);
          return { soll: ((roh % 360) + 360) % 360, ist: w.grad };
        });
        return { mess, echt };
      }, GRADE);
      /* DER UMRISS ALS GEGENPROBE. Ein schraeg gedrehtes Quadrat ist breiter
         als ein ungedrehtes — wenn also KEIN gedrehter Pfeil breiter ist als
         der ungedrehte, gilt die Drehung nicht, und das faellt hier auf, auch
         wenn die Matrix stimmt.
         GEZAEHLT WERDEN NUR DIE ECHT SCHRAEGEN. Vielfache von 90° scheiden
         aus, nicht nur 0° und 180°: Ein um 90° gedrehtes Quadrat ist genauso
         breit wie ein ungedrehtes. Der erste Entwurf zaehlte sie mit und
         meldete in allen drei Motoren „8 von 10" — kein Fund, sondern
         Geometrie. */
      const breit0 = (mess.find(m => m.soll === 0) || {}).breite || 0;
      const schraegeWinkel = mess.filter(m => m.soll % 90 !== 0);
      const gewachsen = schraegeWinkel.filter(m => m.breite > breit0).length;
      const schraeg = schraegeWinkel.length;
      /* WIE LANG IST DIE SPUR AM BILDSCHIRM? `geo.js` zeichnet einen Pfeil
         alle 140 px und gar keinen, wenn die ganze Spur kürzer als zwei
         Abstände ist (E-P3-33/40, „herausgezoomt verschwinden sie von
         selbst"). Bei 390 px Fensterbreite ist die Karte so klein, dass genau
         das eintritt — und dann sind NULL Pfeile das richtige Ergebnis, nicht
         ein Fehlschlag.
         Gemerkt beim ersten Lauf über zwei Breiten (S9/AP5-6): Der Weg war
         für 1280 px geschrieben und meldete bei 390 px „0 von 0" als
         verfehlt. Statt einer Breitenschwelle im Weg wird die Länge GEMESSEN
         — `getTotalLength()` am Spur-Pfad ist genau die Zahl, aus der
         `geo.js` seine Entscheidung trifft. */
      const spurPx = await k.seite.evaluate(() => {
        const pfad = document.querySelector('.leaflet-overlay-pane path');
        return pfad && pfad.getTotalLength ? Math.round(pfad.getTotalLength()) : 0;
      });
      await k.bild('ap3-pfeile-drehen');
      const nah = (a, b) => Math.abs(((a - b + 540) % 360) - 180) < 0.1;
      const treffer = mess.filter(m => nah(m.ist, m.soll)).length;
      const echtOk = echt.filter(e => e.ist !== null && nah(e.ist, e.soll)).length;
      const erwartet = spurPx >= 280;
      return {
        ist: treffer + ' von ' + mess.length + ' in der Aufstellung · '
           + gewachsen + ' von ' + schraeg + ' schräge Pfeile mit größerem Umriss ('
           + breit0 + ' px ungedreht) · '
           + echtOk + ' von ' + echt.length + ' auf der Spur des Einsatzes'
           + ' · Spur am Bildschirm ' + spurPx + ' px'
           + (erwartet ? '' : ' (unter 280 px — geo.js zeichnet dort keine Pfeile)'),
        ok: treffer === mess.length && echtOk === echt.length && gewachsen === schraeg
            && (erwartet ? echt.length > 0 : echt.length === 0),
        bemerkung: treffer !== mess.length
          ? mess.filter(m => !nah(m.ist, m.soll))
                .map(m => m.soll + '° → ' + m.ist + '°').join(', ')
          : (erwartet && echt.length === 0
              ? 'Die Spur ist lang genug für Pfeile, es sind aber keine da'
              : (!erwartet && echt.length > 0
                  ? 'Pfeile bei einer Spur unter 280 px — die Schwelle greift nicht'
                  : '')),
      };
    },
  },

  {
    name: 'ap3-geo-punkt-sichtbar',
    paket: 'AP3', punkt: 'Nr. 162', rolle: 'demo',
    was: 'Der Abfahrtort-Punkt hat einen Kasten und zeigt seine Spurfarbe',
    soll: '12 × 12 px, Farbe sichtbar',
    async fahren(k) {
      await karteSeite(k);
      const m = await k.seite.evaluate(() => {
        const halter = document.createElement('div');
        halter.style.cssText = 'position:absolute;left:-9999px;top:0';
        halter.innerHTML = '<span class="geo-punkt" style="background:#1F4E9C"></span>';
        document.body.appendChild(halter);
        const e = halter.firstElementChild;
        const r = e.getBoundingClientRect(), cs = getComputedStyle(e);
        const erg = { b: Math.round(r.width), h: Math.round(r.height),
                      display: cs.display, farbe: cs.backgroundColor };
        halter.remove();
        return erg;
      });
      const ok = m.b === 12 && m.h === 12 && m.display !== 'inline'
              && m.farbe === 'rgb(31, 78, 156)';
      return {
        ist: m.b + ' × ' + m.h + ' px · display ' + m.display + ' · Fläche ' + m.farbe,
        ok,
        bemerkung: ok ? '' : 'vor Web 15.9.0: 4 × 18 px, display inline, Farbe unsichtbar',
      };
    },
  },

  {
    name: 'ap3-artzeichen-sechs',
    paket: 'AP3', punkt: 'E-S9-13', rolle: 'demo',
    was: 'Sechs Artzeichen aus einer Hand — drei Betriebsarten, drei Typen',
    soll: '6 von 6 mit Anker und Herkunft',
    async fahren(k) {
      /* Die Typen kommen erst mit AP4 ins Datenmodell; die Zeichen sind schon
       * da und muessen sich zeichnen lassen. Geprueft wird der Vorrat selbst:
       * laedt die Datei, traegt sie den Anker `#i`, nennt ihr Kopf die
       * Herkunft? Ein fehlender Anker ist der einzige stille Fehler des
       * Symbolwegs — der Browser malt dann nichts und sagt nichts. */
      await k.gehZu(k.basis + '/index.php');
      const namen = ['hubschrauber', 'fahrzeug', 'ohne-zuordnung',
                     'bergwacht', 'veranstaltung', 'sonstiges'];
      const erg = await k.seite.evaluate(async (namen) => {
        const aus = [];
        for (const n of namen) {
          try {
            const r = await fetch('assets/images/symbole/' + n + '.svg');
            const t = await r.text();
            aus.push({ name: n, da: r.ok, anker: /id="i"/.test(t),
                       pfade: (t.match(/<path/g) || []).length,
                       quelle: /Quelle:/.test(t) });
          } catch (e) { aus.push({ name: n, da: false, anker: false, pfade: 0, quelle: false }); }
        }
        return aus;
      }, namen);
      const gut = erg.filter(e => e.da && e.anker && e.pfade > 0 && e.quelle);
      return {
        ist: gut.length + ' von ' + namen.length + ' · '
           + erg.map(e => e.name + ' ' + e.pfade + ' Pfade').join(', '),
        ok: gut.length === namen.length,
        bemerkung: erg.filter(e => !(e.da && e.anker && e.pfade > 0 && e.quelle))
          .map(e => e.name + ': ' + (!e.da ? 'fehlt' : !e.anker ? 'kein Anker id="i"'
            : !e.quelle ? 'kein „Quelle:" im Kopf' : 'keine Pfade')).join('; '),
      };
    },
  },

  {
    name: 'ap3-windenkacheln-nach-faehigkeit',
    paket: 'AP3', punkt: 'P-11', rolle: 'demo',
    was: 'Die Windenkacheln folgen der Fähigkeit des Diensttags, nicht der Zählung',
    soll: 'mit Winde: 2 Kacheln · Fähigkeit ohne Windeneinsatz: 2 Kacheln mit „0" · ohne Fähigkeit: 0',
    async fahren(k) {
      const zaehle = async (jahr, monat) => {
        await k.gehZu(k.basis + '/zeitraum.php?y=' + jahr
          + (monat ? '&m=' + monat : ''));
        await k.seite.waitForTimeout(2000);
        return k.seite.evaluate(() => {
          const t = Array.from(document.querySelectorAll('.kennzahl'))
            .map(e => (e.textContent || '').replace(/\s+/g, ' ').trim());
          const winde = t.filter(x => /Winden-Cycles/.test(x));
          return { alle: t.length, winde: winde.length, texte: winde,
                   ansicht: (document.querySelector('.segment input:checked') || {}).value || '—' };
        });
      };

      /* Welcher Einsatz im Januar traegt die Winde? Ueber dieselbe Antwort,
       * die auch die Seite benutzt — nicht ueber SQL. */
      const jan = await k.seite.evaluate(async (basis) => {
        const r = await fetch(basis + '/api/range.php?y=2026&m=01');
        const d = await r.json();
        return { faehig: d.faehigkeiten,
                 mitWinde: (d.missions || []).filter(m => m.winch).map(m => m.id) };
      }, k.basis);
      if (!jan.mitWinde.length) {
        throw new Error('Januar 2026 hat keinen Windeneinsatz — Bestand geändert?');
      }
      const einsatz = jan.mitWinde[0];

      const vorher = await zaehle(2026, '01');
      let ohneEinsatz = null, ohneFaehigkeit = null, zurueck = false;
      try {
        /* Den Haken über das FORMULAR herausnehmen — nicht per SQL. Geprüft
         * wird, was die Anwendung anzeigt, wenn die Fähigkeit steht und
         * niemand die Winde geflogen hat. */
        await k.gehZu(k.basis + '/einsatz_form.php?id=' + einsatz);
        /* `state: 'attached'`, nicht sichtbar: Der Haken ist ein `.schalter-box`
         * mit `opacity:0;width:0;height:0` — bedient wird er ueber sein Label,
         * gelesen und gesetzt wird er hier ueber das Element selbst. */
        await k.seite.waitForSelector('input[name="f_winch"]',
          { state: 'attached', timeout: 15000 });
        await k.seite.evaluate(() => {
          const h = document.querySelector('input[name="f_winch"]');
          if (h && h.checked) { h.click(); }
        });
        await k.seite.waitForTimeout(300);
        await Promise.all([
          k.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 25000 }),
          k.seite.evaluate(() => document.querySelector('form#missionform, form').submit()),
        ]);
        ohneEinsatz = await zaehle(2026, '01');
        ohneFaehigkeit = await zaehle(2026, '11');
        zurueck = true;
      } finally {
        if (zurueck) {
          await k.gehZu(k.basis + '/einsatz_form.php?id=' + einsatz);
          /* `state: 'attached'`, nicht sichtbar: Der Haken ist ein `.schalter-box`
         * mit `opacity:0;width:0;height:0` — bedient wird er ueber sein Label,
         * gelesen und gesetzt wird er hier ueber das Element selbst. */
        await k.seite.waitForSelector('input[name="f_winch"]',
          { state: 'attached', timeout: 15000 });
          await k.seite.evaluate(() => {
            const h = document.querySelector('input[name="f_winch"]');
            if (h && !h.checked) { h.click(); }
          });
          await k.seite.waitForTimeout(300);
          await Promise.all([
            k.seite.waitForNavigation({ waitUntil: 'domcontentloaded', timeout: 25000 }),
            k.seite.evaluate(() => document.querySelector('form#missionform, form').submit()),
          ]);
        }
      }
      await k.bild('ap3-windenkacheln');

      const nullwert = (ohneEinsatz.texte || []).some(t => /(^|\D)0(\D|$)/.test(t));
      const ok = vorher.winde === 2 && ohneEinsatz.winde === 2
              && nullwert && ohneFaehigkeit.winde === 0;
      return {
        ist: 'Januar mit Windeneinsatz: ' + vorher.winde + ' Kacheln · '
           + 'Januar ohne Windeneinsatz, Fähigkeit steht: ' + ohneEinsatz.winde
           + ' Kacheln ' + JSON.stringify(ohneEinsatz.texte) + ' · '
           + 'November ohne Fähigkeit: ' + ohneFaehigkeit.winde + ' Kacheln · '
           + 'faehigkeiten aus der API: ' + JSON.stringify(jan.faehig),
        ok,
        bemerkung: ok ? ''
          : 'vor Web 15.9.0 verschwanden die Kacheln, sobald kein Einsatz die Winde trug',
      };
    },
  },

  {
    name: 'ap3-wording-gps-daten',
    paket: 'AP3', punkt: 'P-12', rolle: 'demo',
    was: 'Wo die NutzerIn liest, steht „GPS-Daten" — nicht „Spur"',
    soll: '0 sichtbare „Spur" auf vier Seiten (eine Ausnahme: der GPX-Fachbegriff)',
    async fahren(k) {
      const e = await einsaetze(k);
      const seiten = [
        ['Tagesübersicht', k.basis + '/index.php?d=' + e.tag],
        ['Einsatzansicht', k.basis + '/einsatz.php?id=' + e.mitSpur],
        ['GPS-Daten des Tages', k.basis + '/tag_spuren.php?d=' + e.tag],
        ['Zeitraum', k.basis + '/zeitraum.php?y=2026&m=01'],
      ];
      const funde = [];
      let gps = 0;
      for (const [name, url] of seiten) {
        await k.gehZu(url);
        await k.seite.waitForTimeout(1800);
        const r = await k.seite.evaluate(() => {
          const t = document.body.innerText;
          return { spur: (t.match(/\bSpur\w*/g) || []),
                   gps: (t.match(/GPS-Daten/g) || []).length };
        });
        gps += r.gps;
        /* DIE EINE ERLAUBTE: „jede Aufzeichnung bleibt darin eine eigene
         * Spur" erklaert den Aufbau der GPX-DATEI — dort heisst es so
         * (Ausnahme `spur-gpx-fachbegriff` der Wortliste). */
        const uebrig = name === 'GPS-Daten des Tages' ? r.spur.slice(1) : r.spur;
        if (uebrig.length) { funde.push(name + ': ' + uebrig.join(', ')); }
      }
      await k.bild('ap3-wording-gps-daten');
      return {
        ist: funde.length + ' Seite(n) mit „Spur" · ' + gps + ' Nennungen „GPS-Daten"',
        ok: funde.length === 0 && gps > 0,
        bemerkung: funde.join(' | '),
      };
    },
  },
];
