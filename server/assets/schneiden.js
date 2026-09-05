/* Gen-EM NAdoku — Schneidewerkzeug in der Tagesansicht (S4/A2b, E-S4-17).
 *
 * WOFUER. Wer waehrend eines Einsatzes keinen Knopf gedrueckt hat, hat den
 * Einsatz nicht — seine Spur aber schon: Sie liegt im Ruhesegment, in dem das
 * Geraet zu der Zeit aufgezeichnet hat. Diese Datei baut die Karte
 * „Ruhesegmente" und den aufklappbaren Schneide-Bereich darunter; geschnitten
 * wird ueber `api/schneiden.php`.
 *
 * WARUM EINE EIGENE DATEI UND NICHT DER INLINE-BLOCK VON index.php: Der ist
 * schon 600 Zeilen lang und traegt Karte, Tabelle, Entschluesselung und
 * Diensttag-Formular. Der Schnitt ist davon unabhaengig — er liest die
 * Segmentliste und ruft einen Endpunkt. Als eigene Datei laedt er ausserdem
 * mit eigenem Zeitstempel nach (asset(), Backlog Nr. 9).
 *
 * DIE ZEITLEISTE IST EINE ANZEIGE, KEINE BEDIENUNG. Gefuehrt wird ueber die
 * Zeitfelder; die Leiste zeigt, was daraus wird. Ein Ziehen an den Griffen
 * waere eine zweite Eingabe fuer dieselbe Zahl, und die beiden liefen
 * auseinander — die eine auf die Minute, die andere auf das Pixel (Mockup A0,
 * Vorschlag 2: „führend sind die Felder").
 */
'use strict';
const EdSchnitt = (() => {

  const esc = (t) => EdHtml.escape(t);

  let neuLaden = null;     // Rückruf: Tag neu holen, nachdem etwas geschnitten wurde

  /* ---- Zeit ---------------------------------------------------------------
   *
   * HIER WIRD NICHT UMGERECHNET, und das ist der Kern.
   *
   * Die Anwendung rechnet Zeiten IMMER auf dem Server in die App-Zeitzone
   * (`fmt_local()`); der Browser zeigt nur an — die Einsatztabelle bekommt
   * seit jeher `start_hhmm` und keine UTC-Zeichenkette. Die erste Fassung
   * dieser Datei tat es anders: Sie nahm `started_at` als UTC und rechnete
   * mit `new Date(...)` in die Zone des BROWSERS um. Auf einem Rechner, dessen
   * Zone zufaellig die der Anwendung ist, faellt das nie auf; im Container ist
   * sie UTC, und der Schnitt griff zwei Stunden daneben und nahm null Punkte
   * mit. Die Bedienung meldete dabei Erfolg.
   *
   * Jetzt liefert die API `start_hhmm`/`end_hhmm` (fertig formatiert),
   * `von_ts`/`bis_ts` (Epochensekunden, reine Geometrie) und
   * `start_tag`/`end_tag` (Kalendertage hinter dem Diensttag). Was der Browser
   * tut, ist Minutenarithmetik — und die kennt keine Zeitzone.
   */

  /** 'HH:MM' -> Minuten seit Mitternacht, oder null. */
  function minuten(v) {
    const t = String(v || '').trim();
    if (!/^([01]\d|2[0-3]):[0-5]\d$/.test(t)) { return null; }
    const [h, m] = t.split(':').map(Number);
    return h * 60 + m;
  }

  /** Minuten seit Mitternacht -> 'HH:MM'. */
  function ausMinuten(m) {
    const x = ((m % 1440) + 1440) % 1440;
    return String(Math.floor(x / 60)).padStart(2, '0') + ':'
         + String(x % 60).padStart(2, '0');
  }

  /* Epochensekunde eines gewaehlten Zeitpunkts, abgeleitet vom Segmentanfang.
   *
   * DIE EINE UNSCHAERFE, benannt statt versteckt: Faellt die Zeitumstellung
   * MITTEN in ein Segment, verschiebt sich die Vorschau um eine Stunde. Sie
   * ist eine Vorschau; verbindlich rechnet `pruef_ortszeit_zu_utc()` auf dem
   * Server, und das kennt die Umstellung. Ein Ruhesegment, das ueber 03:00
   * Uhr an einem der beiden Tage im Jahr laeuft, zeigt also einen um eine
   * Stunde verschobenen Balken — und schneidet trotzdem richtig. */
  function tsVon(seg, hhmm, tag) {
    const m0 = minuten(seg.start_hhmm), m = minuten(hhmm);
    if (m0 === null || m === null) { return null; }
    return seg.von_ts + ((tag - seg.start_tag) * 1440 + (m - m0)) * 60;
  }

  /* Auf welchen Kalendertag faellt diese Uhrzeit?
   *
   * GESUCHT WIRD DER TAG, DER DIE ZEIT INS SEGMENT LEGT. Ein Dienst laeuft
   * ueber Mitternacht: „00:20" kann derselbe Kalendertag sein oder der
   * naechste, und aus der Uhrzeit allein geht das nicht hervor. Statt zu
   * raten, wird beides ausprobiert — und was ins Segment faellt, gewinnt.
   * Passt keines, liegt die Zeit ausserhalb, und das ist eine Aussage, die
   * die Bedienung anzeigen kann. */
  function tagFuer(seg, hhmm) {
    const bis = seg.bis_ts != null ? seg.bis_ts : seg.von_ts + 86400;
    for (const t of [seg.start_tag, seg.start_tag + 1]) {
      const ts = tsVon(seg, hhmm, t);
      if (ts !== null && ts >= seg.von_ts && ts <= bis) { return t; }
    }
    return null;
  }

  function dauer(vonTs, bisTs) {
    const s = Math.max(0, bisTs - vonTs);
    const h = Math.floor(s / 3600), m = Math.round((s % 3600) / 60);
    return h > 0 ? (h + ' h ' + m + ' min') : (m + ' min');
  }

  /** Ende des Segments — auch wenn es noch laeuft. */
  function segBis(seg) { return seg.bis_ts != null ? seg.bis_ts : seg.von_ts + 3600; }
  function segBisHhmm(seg) {
    return seg.end_hhmm != null ? seg.end_hhmm
         : ausMinuten(minuten(seg.start_hhmm) + 60);
  }

  /* ---- Die Zeitleiste -----------------------------------------------------
   *
   * GEWOEHNLICHE ELEMENTE MIT PROZENTBREITEN, kein SVG. Die erste Fassung war
   * ein `<svg viewBox="0 0 640 120">` mit `width:100%`, wie im Mockup — und
   * ein viewBox skaliert seine BESCHRIFTUNG mit: auf 1280 px stand die
   * Uhrzeit richtig, auf 390 px war sie sechs Pixel hoch. Dieselbe Zahl in
   * zwei Groessen, je nach Fenster. So bleibt der Text Text und nur der
   * Balken skaliert.
   *
   * Alle Farben kommen aus Klassen (`.schnitt-bahn`, `.schnitt-wahl` und so
   * fort), damit kein Hexwert im Markup steht (CLAUDE.md 5). Was hier im
   * `style`-Attribut landet, sind ausschliesslich zur Laufzeit gerechnete
   * Prozentwerte — die kann kein Stylesheet vorhalten.
   */
  function leiste(seg, vonTs, bisTs, vonHhmm, bisHhmm) {
    const s0 = seg.von_ts, s1 = segBis(seg);
    const spanne = Math.max(1, s1 - s0);
    /* Prozent statt Pixel: Der Browser kennt die Breite des Behälters, das
     * Skript nicht — und soll sie auch nicht messen müssen. */
    const pct = (ts) => Math.max(0, Math.min(100, ((ts - s0) / spanne) * 100));

    let h = '<div class="schnitt-leiste" role="img" aria-label="'
          + esc('Zeitleiste des Segments von ' + seg.start_hhmm + ' bis '
                + segBisHhmm(seg)
                + (vonTs != null && bisTs != null
                    ? ', gewählt ' + vonHhmm + ' bis ' + bisHhmm : ''))
          + '"><div class="schnitt-bahn"></div>';

    /* Was früher schon herausgeschnitten wurde, wird gezeigt. Ohne diese
     * Fläche sähe die Leiste eine Lücke ohne Erklärung, und jemand schnitte
     * ein zweites Mal an derselben Stelle. */
    (seg.schnitte || []).forEach(sn => {
      if (sn.bis_ts < s0 || sn.von_ts > s1) { return; }
      const a = pct(sn.von_ts), b = pct(sn.bis_ts);
      h += '<div class="schnitt-weg" style="left:' + a.toFixed(2) + '%;width:'
         + Math.max(0.5, b - a).toFixed(2) + '%"></div>';
    });

    if (vonTs != null && bisTs != null && bisTs > vonTs) {
      const a = pct(vonTs), b = pct(bisTs);
      h += '<div class="schnitt-wahl" style="left:' + a.toFixed(2) + '%;width:'
         + Math.max(0.5, b - a).toFixed(2) + '%"></div>'
         + '<div class="schnitt-griff" style="left:' + a.toFixed(2) + '%"></div>'
         + '<div class="schnitt-griff" style="left:' + b.toFixed(2) + '%"></div>'
         /* Die Uhrzeiten AN den Griffen. Sie stehen auch in den Feldern —
          * hier ein zweites Mal, weil die Leiste sonst zeigt, WO geschnitten
          * wird, aber nicht WANN, und der Blick zwischen Balken und Feld
          * hin- und herspringen müsste. */
         /* NUR, WENN SIE ETWAS ANDERES SAGEN als die Ränder darunter. Ist
          * das ganze Segment gewählt — die Vorbelegung —, stünde sonst
          * dieselbe Uhrzeit zweimal übereinander. */
         + (vonHhmm !== seg.start_hhmm
             ? '<span class="schnitt-marke" style="left:' + a.toFixed(2) + '%">'
               + esc(vonHhmm) + '</span>' : '')
         + (bisHhmm !== segBisHhmm(seg)
             ? '<span class="schnitt-marke" style="left:' + b.toFixed(2) + '%">'
               + esc(bisHhmm) + '</span>' : '');
    }
    h += '</div><div class="schnitt-raender"><span>' + esc(seg.start_hhmm)
       + '</span><span>' + esc(segBisHhmm(seg)) + '</span></div>';
    return h;
  }

  /* ---- Der Schneide-Bereich ------------------------------------------------ */

  function feld(id, label, zusatz, wert) {
    return '<div class="feld">'
         + '<label class="feld-label" for="' + id + '">' + esc(label)
         + (zusatz ? ' <span class="feld-klein-inline">' + esc(zusatz) + '</span>' : '')
         + '</label>'
         + '<input class="feld-eingabe zeitfeld" type="text" inputmode="numeric" '
         + 'id="' + id + '" value="' + esc(wert || '') + '" '
         + 'placeholder="hh:mm" maxlength="5" autocomplete="off">'
         + '</div>';
  }

  function bereichMarkup(seg) {
    /* VORBELEGT MIT DEM GANZEN SEGMENT, nicht mit einem geratenen Ausschnitt.
     * „Die mittlere Stunde" waere eine Behauptung ueber etwas, das nur die
     * Bedienerin weiss. Das ganze Segment ist die ehrliche Vorbelegung:
     * sichtbar falsch, und in zwei Feldern berichtigt. */
    return '<div class="schnitt-bereich" data-schnitt-fuer="' + seg.id + '">'
      + '<div class="schnitt-vorschau" data-vorschau>'
      +   leiste(seg, seg.von_ts, segBis(seg), seg.start_hhmm, segBisHhmm(seg))
      + '</div>'
      + '<div class="schnitt-felder">'
      +   feld('s-beg-' + seg.id, 'Einsatzbeginn', 'Alarmierung', seg.start_hhmm)
      +   feld('s-end-' + seg.id, 'Einsatzende', '', segBisHhmm(seg))
      + '</div>'
      + '<div class="schnitt-felder">'
      +   feld('s-p3-' + seg.id, 'Ausrücken', 'optional', '')
      +   feld('s-p4-' + seg.id, 'Ankunft Einsatzort', 'optional', '')
      +   feld('s-p7-' + seg.id, 'Ankunft Klinik', 'optional', '')
      + '</div>'
      + '<p class="feld-klein">Alle weiteren Phasenzeiten kannst du danach im '
      +   'Einsatz nachtragen — Beginn und Ende genügen zum Schneiden.</p>'
      + '<div class="meldung meldung-info" role="status" data-vorher><p></p></div>'
      + '<div class="listen-form-fuss">'
      +   '<button class="knopf knopf-primaer" type="button" data-schneiden>'
      +     '<span>Einsatz erzeugen</span></button>'
      +   '<button class="knopf knopf-leise" type="button" data-abbrechen>'
      +     '<span>Abbrechen</span></button>'
      + '</div>'
      + '</div>';
  }

  /* Die gewaehlten Grenzen aus den Feldern — samt Tagesversatz und
   * Epochensekunden, oder ein Grund, warum es keine gibt. EINE Stelle fuer
   * beide Verbraucher (Vorschau und Senden); zwei Ableitungen derselben
   * Auswahl waeren zwei Gelegenheiten, sie unterschiedlich zu lesen. */
  function wahl(el, seg) {
    const bv = el.querySelector('#s-beg-' + seg.id).value;
    const ev = el.querySelector('#s-end-' + seg.id).value;
    if (minuten(bv) === null || minuten(ev) === null) {
      return { grund: 'Beginn und Ende müssen Uhrzeiten im Format hh:mm sein.' };
    }
    const bt = tagFuer(seg, bv), et = tagFuer(seg, ev);
    if (bt === null || et === null) {
      return { grund: 'Beginn und Ende müssen innerhalb des Segments liegen ('
                    + seg.start_hhmm + ' – ' + segBisHhmm(seg) + ').' };
    }
    const vonTs = tsVon(seg, bv, bt), bisTs = tsVon(seg, ev, et);
    if (bisTs <= vonTs) { return { grund: 'Das Ende liegt vor dem Beginn.' }; }
    return { bv, ev, bt, et, vonTs, bisTs };
  }

  /** Vorschau und Erklärtext nach jeder Eingabe nachziehen. */
  function bereichAuffrischen(el, seg) {
    const w = wahl(el, seg);
    const p = el.querySelector('[data-vorher] p');
    const knopf = el.querySelector('[data-schneiden]');

    el.querySelector('[data-vorschau]').innerHTML =
      leiste(seg, w.grund ? null : w.vonTs, w.grund ? null : w.bisTs, w.bv, w.ev);

    if (w.grund) { p.textContent = w.grund; knopf.disabled = true; return; }
    knopf.disabled = false;

    const reste = [];
    if (w.vonTs > seg.von_ts) { reste.push(seg.start_hhmm + ' – ' + w.bv); }
    if (w.bisTs < segBis(seg)) { reste.push(w.ev + ' – ' + segBisHhmm(seg)); }
    p.textContent =
      'Der Einsatz bekommt ' + w.bv + ' – ' + w.ev
      + (w.et > w.bt ? ' (am Folgetag)' : '') + ' · ' + dauer(w.vonTs, w.bisTs) + '. '
      + (reste.length
          ? 'Der übrige Zeitraum bleibt als Ruhesegment stehen (' + reste.join(' und ') + ').'
          : 'Das Segment wird dabei vollständig aufgebraucht.')
      + ' Der Schnitt lässt sich rückgängig machen, solange am Einsatz nichts '
      + 'Weiteres hängt.';
  }

  /* ---- Senden --------------------------------------------------------------- */

  async function ruf(nutzlast) {
    const a = await fetch('api/schneiden.php', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json', 'X-CSRF': (typeof CSRF === 'string' ? CSRF : '') },
      body: JSON.stringify(nutzlast)
    });
    let d = null;
    try { d = await a.json(); } catch (e) { d = null; }
    if (!a.ok || !d || d.error) {
      throw new Error((d && d.meldung) || 'Der Server hat den Vorgang abgelehnt.');
    }
    return d;
  }

  async function schneiden(el, seg) {
    const knopf = el.querySelector('[data-schneiden]');
    const p = el.querySelector('[data-vorher] p');
    const w = wahl(el, seg);
    if (w.grund) { p.textContent = w.grund; return; }

    /* PHASENZEITEN GEHEN MIT IHREM EIGENEN TAGESVERSATZ. Eine Phase um 00:10
     * an einem Einsatz, der um 23:50 beginnt, gehoert auf den Folgetag —
     * dieselbe Zweideutigkeit wie bei Beginn und Ende, und dieselbe Antwort. */
    const phasen = {};
    [['3', 's-p3-'], ['4', 's-p4-'], ['7', 's-p7-']].forEach(([nr, pre]) => {
      const v = el.querySelector('#' + pre + seg.id).value.trim();
      if (v && minuten(v) !== null) { phasen[nr] = v; }
    });

    knopf.disabled = true;
    try {
      const d = await ruf({ action: 'schneiden', rest_id: seg.id,
        beginn: w.bv, ende: w.ev, beginn_tag: w.bt, ende_tag: w.et, phasen });
      /* NEU LADEN STATT NACHZIEHEN. Der Schnitt aendert die Einsatztabelle,
       * die Karte, die Segmentliste und den Diensttag-Zeitraum. Vier Stellen
       * von Hand fortzuschreiben hiesse, vier Gelegenheiten zu schaffen, an
       * denen die Anzeige von der Datenbank abweicht. */
      await neuLaden();
      melde('ok', 'Der Einsatz ist entstanden — ' + d.genommen
                + ' Punkte sind zum Einsatz gewandert, '
                + d.geblieben + ' bleiben im Ruhesegment. '
                + 'Einsatzort, Alter und Diagnose trägst du im Einsatz nach.');
    } catch (e) {
      p.textContent = e.message;
      knopf.disabled = false;
    }
  }

  async function rueckgaengig(missionId) {
    try {
      const d = await ruf({ action: 'rueckgaengig', mission_id: missionId });
      await neuLaden();
      melde('ok', 'Der Schnitt ist zurückgenommen — ' + d.zurueck
                + ' Punkte liegen wieder im Ruhesegment, der Einsatz ist weg.');
    } catch (e) {
      melde('warn', e.message);
    }
  }

  /* Rückmeldung über der Segmentliste. Sie steht dort und nicht als
   * Einblendung: Die Anwendung kennt keinen Toast, und ein Vorgang, der Daten
   * verschiebt, soll eine Meldung hinterlassen, die stehen bleibt. */
  function melde(ton, text) {
    const ziel = document.getElementById('ruhezeilen');
    if (!ziel) { return; }
    const alt = ziel.parentNode.querySelector('[data-schnittmeldung]');
    if (alt) { alt.remove(); }
    const d = document.createElement('div');
    /* Die Klassennamen AUSGESCHRIEBEN und nicht zusammengesetzt. Ein
     * `'meldung-' + ton` liest kein Prüfmittel: Die Vollständigkeitsprüfung
     * sucht Klassen im Markup und fand hier „meldung-" ohne Regel. Sie hat
     * recht — was sie nicht sieht, kann sie auch nicht als verwaist melden,
     * wenn die Regel eines Tages verschwindet. */
    d.className = ton === 'ok' ? 'meldung meldung-ok' : 'meldung meldung-warn';
    d.setAttribute('role', 'status');
    d.setAttribute('data-schnittmeldung', '');
    d.innerHTML = '<p>' + esc(text) + '</p>';
    ziel.parentNode.insertBefore(d, ziel);
  }

  /* ---- Die Liste ------------------------------------------------------------ */

  function zeile(seg) {
    const zeit = seg.start_hhmm
               + (seg.end_hhmm ? ' – ' + seg.end_hhmm : ' – offen') + ' Uhr';
    const klein = (seg.bis_ts != null ? dauer(seg.von_ts, seg.bis_ts) + ' · ' : '')
                + (seg.n > 0 ? seg.n + ' Punkte' : 'keine Aufzeichnung')
                + (seg.final ? '' : ' · läuft noch');

    let plaketten = '';
    (seg.schnitte || []).forEach(sn => {
      plaketten += '<span class="plakette plakette-orange">'
                 + esc('geschnitten ' + sn.von_hhmm + ' – ' + sn.bis_hhmm)
                 + '</span> ';
    });

    let knoepfe = '';
    if (seg.n > 0) {
      knoepfe += '<button class="knopf knopf-neutral" type="button" data-schnitt-auf="'
               + seg.id + '" aria-expanded="false"><span>Schneiden</span></button>';
    }
    /* RUECKGAENGIG STEHT HIER — AM SEGMENT, NICHT AM EINSATZ.
     *
     * Der freigegebene Mockup schlug die Zeile des geschnittenen EINSATZES
     * vor. Die Einsatzliste ist aber eine sortierbare Tabelle mit einem
     * Spaltenkatalog (`mission_fields.php`) und hat keine Zeilenaktionen;
     * eine Aktionsspalte einzufuehren traefe alle drei Einsatztabellen
     * (Tagesansicht, Suche, Zeitraum) und waere eine neue Darstellung —
     * freigabepflichtig nach CLAUDE.md 5.
     *
     * Am Segment ist die Handlung ausserdem vollstaendig: Dort steht, was
     * fehlt, dorthin kommen die Punkte zurueck, und dort steht bereits die
     * Plakette, die den Schnitt nennt. Die Abweichung ist im Konzept
     * vermerkt und steht zur Entscheidung. */
    (seg.schnitte || []).forEach(sn => {
      knoepfe += '<button class="knopf knopf-leise" type="button" data-rueck="'
               + sn.mission_id + '"><span>Schnitt zurücknehmen</span></button>';
    });
    const aktionen = knoepfe
      ? '<div class="zeile-aktionen"><div class="zeile-knoepfe">' + knoepfe + '</div></div>'
      : '';

    return '<div class="zeile" data-ruhezeile="' + seg.id + '">'
         + '<div class="zeile-text">'
         +   '<span class="zeile-haupt">' + esc(zeit) + '</span>'
         +   '<span class="zeile-klein">' + esc('Ruhesegment Nr. ' + seg.id + ' · ' + klein) + '</span>'
         + '</div>'
         + (plaketten ? '<div class="zeile-plaketten">' + plaketten + '</div>' : '')
         + aktionen
         + '</div>';
  }

  function zeichnen(segmente) {
    const karte = document.getElementById('ruheliste');
    const ziel  = document.getElementById('ruhezeilen');
    if (!karte || !ziel) { return; }
    karte.hidden = segmente.length === 0;
    document.getElementById('rzahl').textContent = String(segmente.length);
    ziel.innerHTML = segmente.map(zeile).join('');

    ziel.querySelectorAll('[data-rueck]').forEach(b => {
      b.addEventListener('click', () => {
        b.disabled = true;
        rueckgaengig(Number(b.getAttribute('data-rueck')));
      });
    });

    segmente.forEach(seg => {
      const knopf = ziel.querySelector('[data-schnitt-auf="' + seg.id + '"]');
      if (!knopf) { return; }
      knopf.addEventListener('click', () => {
        const offen = ziel.querySelector('[data-schnitt-fuer="' + seg.id + '"]');
        if (offen) { offen.remove(); knopf.setAttribute('aria-expanded', 'false'); return; }
        /* IMMER NUR EINER OFFEN. Zwei Schneide-Bereiche nebeneinander waeren
         * zwei Vorschauen zu einer Karte, und die Zeitleiste des einen
         * beschriebe das Segment des anderen. */
        ziel.querySelectorAll('.schnitt-bereich').forEach(b => b.remove());
        ziel.querySelectorAll('[data-schnitt-auf]')
            .forEach(b => b.setAttribute('aria-expanded', 'false'));

        const zeileEl = ziel.querySelector('[data-ruhezeile="' + seg.id + '"]');
        zeileEl.insertAdjacentHTML('afterend', bereichMarkup(seg));
        knopf.setAttribute('aria-expanded', 'true');
        const el = ziel.querySelector('[data-schnitt-fuer="' + seg.id + '"]');

        el.addEventListener('input', () => bereichAuffrischen(el, seg));
        el.addEventListener('change', () => bereichAuffrischen(el, seg));
        el.querySelector('[data-abbrechen]').addEventListener('click', () => {
          el.remove(); knopf.setAttribute('aria-expanded', 'false'); knopf.focus();
        });
        el.querySelector('[data-schneiden]').addEventListener('click',
          () => schneiden(el, seg));
        bereichAuffrischen(el, seg);
        el.querySelector('#s-beg-' + seg.id).focus();
      });
    });
  }

  /* ---- GPX-Import (S4/A3, E-S4-18) ------------------------------------------
   *
   * Er wohnt in DIESER Datei und nicht in einer eigenen: Er endet dort, wo
   * der Schnitt anfaengt — die importierte Aufzeichnung wird ein
   * Ruhesegment, und die Einsaetze schneidet man danach heraus. Beide
   * bedienen dieselbe Karte, beide laden den Tag danach neu.
   *
   * DIE DATEI WIRD NUR GELESEN, NICHT GEPRUEFT. Was eine gueltige GPX-Datei
   * ist, entscheidet `gpx_lesen()` auf dem Server; hier eine zweite Meinung
   * einzubauen hiesse, zwei Wahrheiten zu haben — und die schwaechere davon
   * ist die, die jemand mit den Entwicklerwerkzeugen ausschaltet.
   */
  let gpxText = null;

  function gpxFehler(text) {
    const box = document.getElementById('gpx-fehler');
    box.hidden = !text;
    if (text) { box.querySelector('p').textContent = text; }
  }

  function gpxStarten(dayId, tagText) {
    const dlg = document.getElementById('gpxdialog');
    if (!dlg) { return; }
    const datei = document.getElementById('gpx-datei');
    const los   = document.getElementById('gpx-los');

    gpxText = null;
    datei.value = '';
    los.disabled = true;
    gpxFehler('');
    document.getElementById('gpx-tagsatz').textContent =
      'Die Spur wird dem Diensttag ' + tagText + ' zugeordnet.';

    datei.onchange = () => {
      const f = datei.files && datei.files[0];
      gpxText = null; los.disabled = true; gpxFehler('');
      if (!f) { return; }
      const leser = new FileReader();
      leser.onerror = () => gpxFehler('Die Datei ließ sich nicht lesen.');
      leser.onload = () => { gpxText = String(leser.result); los.disabled = false; };
      leser.readAsText(f);
    };

    document.getElementById('gpx-abbrechen').onclick = () => dlg.close();
    los.onclick = async () => {
      if (gpxText === null) { return; }
      los.disabled = true;
      gpxFehler('');
      try {
        const a = await fetch('api/gpx_import.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json',
                     'X-CSRF': (typeof CSRF === 'string' ? CSRF : '') },
          body: JSON.stringify({ day_id: dayId, ziel: gpxZiel(), xml: gpxText })
        });
        let d = null;
        try { d = await a.json(); } catch (e) { d = null; }
        if (!a.ok || !d || d.error) {
          throw new Error((d && d.meldung) || 'Der Server hat die Datei abgelehnt.');
        }
        dlg.close();
        await neuLaden();
        let t = d.art === 'mission'
          ? 'Die Datei ist als Einsatz übernommen — '
          : 'Die Datei ist als Ruhesegment übernommen — ';
        t += d.punkte + ' Punkte, ' + d.von + ' bis ' + d.bis + '.';
        if (d.ohne_zeit) { t += ' ' + d.ohne_zeit + ' Punkte ohne Zeitstempel sind nicht mitgekommen.'; }
        if (d.verworfen) { t += ' ' + d.verworfen + ' Punkte mit unbrauchbaren Koordinaten ebenfalls nicht.'; }
        if (d.art === 'rest') { t += ' Einsätze schneidest du jetzt heraus.'; }
        melde('ok', t);
      } catch (e) {
        gpxFehler(e.message);
        los.disabled = false;
      }
    };

    dlg.showModal();
    datei.focus();
  }

  function gpxZiel() {
    return document.getElementById('gpx-einsatz').checked ? 'einsatz' : 'ruhe';
  }

  /**
   * Einmal beim Laden der Seite aufrufen.
   * @param {function(): Promise} laden  holt den Diensttag neu (loadDay)
   */
  function starten(laden) { neuLaden = laden; }

  /** Nach jedem Laden des Diensttags. */
  function setzen(segmente) { zeichnen(segmente || []); }

  return { starten, setzen, rueckgaengig, gpxStarten };
})();
