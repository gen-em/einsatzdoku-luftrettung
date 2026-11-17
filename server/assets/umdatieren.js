/* assets/umdatieren.js — der Versatz einer Umdatierung im Standbuch
 * ===========================================================================
 *
 * WOZU (06e, F-KA-129, V-KA-05). Wer das Datum eines Diensttags ändert
 * (`diensttag_datum.php`), verschiebt alle Klartextzeiten des Tages. Die
 * Hüllen der Fassung 2 tragen ihre Zeit absolut; der Browser nimmt sie nur
 * an, wenn Klartextzeit − Hüllenzeit dem Versatz `d` im Standbuch gleicht.
 * Bis Web 25.x schrieb diesen Versatz niemand, und jede Hülle eines
 * umdatierten Tages scheiterte an `zeit`.
 *
 * BELEGT VOM BROWSER, NICHT BEHAUPTET VOM SERVER. Sonst könnte, wer den
 * Server beschreibt, Zeiten verschieben, und das Standbuch sähe es nicht:
 *   1. `diensttag_datum.php`: Beim Absenden merkt sich der Browser Tag,
 *      altes und neues Datum, Zeitzone und die Kennungen des Tages
 *      (`sessionStorage`, nur dieser Tab).
 *   2. Startseite mit `?umdatiert=1&d=<Tag>`: Er rechnet den Versatz SELBST
 *      — die Differenz der beiden Ortsmitternächte, wie der Server
 *      (`tz_tag_datum_aendern()`) — und trägt ihn je Kennung ein
 *      (`d += Versatz`). Danach lädt er die Seite neu, damit die Ansicht mit
 *      dem neuen Versatz öffnet.
 *   Der Merker gilt EINMAL und zehn Minuten; ohne ihn geschieht nichts. Was
 *   nicht eingetragen werden konnte, bleibt im Merker für den nächsten
 *   Aufruf.
 *
 * GRENZE. Bricht der Tab dazwischen ab (Schließen, anderes Gerät), fehlt der
 * Versatz, und die Ortsdaten des Tages gehen nicht auf — nicht verloren:
 * Zurückdatieren macht sie wieder lesbar.
 */
(() => {
  'use strict';
  const MERKER = 'nadoku-umdatieren';
  const FRIST = 600;

  const lesen = () => {
    try { return JSON.parse(sessionStorage.getItem(MERKER) || 'null'); } catch (e) { return null; }
  };
  const schreiben = (m) => {
    try { if (m) { sessionStorage.setItem(MERKER, JSON.stringify(m)); } else { sessionStorage.removeItem(MERKER); } }
    catch (e) { /* ohne Ablage kein Versatz — siehe Grenze */ }
  };

  /* 1. Die Seite des Umdatierens: merken, was abgeschickt würde — beim
     Laden und bei jeder Änderung des Datums. NICHT am `submit`: Die
     Rückfrage (`confirm.js`) fängt das Ereignis ab und schickt das Formular
     danach selbst, ohne ein zweites. Gelesen wird der Merker nur auf der
     Startseite nach einem gelungenen Umdatieren (`?umdatiert=1`). */
  const form = document.getElementById('datumform');
  if (form && form.dataset.umdatierenRefs) {
    const feld = document.getElementById('zielfeld');
    const merken = () => {
      let refs = [];
      try { refs = JSON.parse(form.dataset.umdatierenRefs || '[]'); } catch (e) { refs = []; }
      schreiben({ d: Number(form.querySelector('input[name="d"]').value), alt: form.dataset.umdatierenAlt,
                  ziel: EdFormat.datumLesen(feld ? feld.value : ''), tz: form.dataset.umdatierenTz,
                  refs: refs, t: Math.floor(Date.now() / 1000) });
    };
    merken();
    if (feld) { ['input', 'change'].forEach((art) => feld.addEventListener(art, merken)); }
    form.addEventListener('submit', merken, true);
    return;
  }

  /* 2. Die Startseite nach dem Umdatieren: eintragen. */
  const q = new URLSearchParams(location.search);
  if (q.get('umdatiert') !== '1') { return; }
  const m = lesen();
  if (!m || Number(q.get('d')) !== m.d || Math.floor(Date.now() / 1000) - m.t > FRIST) { return; }
  (async () => {
    if (typeof EdStandbuch === 'undefined' || typeof PAT_WRAP === 'undefined') { return; }
    const von = EdFormat.ortszeitZuEpoch(m.alt, '00:00', 0, m.tz);
    const bis = EdFormat.ortszeitZuEpoch(m.ziel, '00:00', 0, m.tz);
    if (von === null || bis === null) { schreiben(null); return; }
    const versatz = bis - von;
    if (!versatz || !m.refs.length) { schreiben(null); return; }
    const ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
    if (!ck) { return; }                      // gesperrt: der Merker bleibt für den nächsten Aufruf
    const buch = await EdStandbuch.seitenbuch(ck);
    const r = await buch.fortschreibenViele(m.refs.map((ref) => ({ ref: ref,
      aendern: (e) => { e.d = (e.d || 0) + versatz; if (!e.d) { delete e.d; } } })));
    const offen = r.filter((x) => x.fehler).map((x) => x.ref);
    schreiben(offen.length ? Object.assign({}, m, { refs: offen }) : null);
    q.delete('umdatiert');
    location.replace(location.pathname + '?' + q.toString());
  })().catch(() => { /* der Merker bleibt; die Ansicht meldet `zeit` */ });
})();
