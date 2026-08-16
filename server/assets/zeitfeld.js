/* Einsatzdoku — Zeiteingabe im 24-Stunden-Format (Auftragspunkt 4, E1).
 *
 * WARUM ES DIESE DATEI GIBT. <input type="time"> zeigt sein Format nach der
 * Sprach- bzw. Regionseinstellung des Betriebssystems an. Steht dort eine
 * 12-Stunden-Region, erscheint die Eingabe als "01:30 PM" — auch bei deutscher
 * Oberflaeche, auch wenn die Seite lang="de" traegt. Erzwingen laesst sich das
 * nicht: weder ueber ein Attribut, noch ueber CSS, noch ueber JavaScript. Der
 * uebertragene Wert ist zwar immer "HH:MM", aber gelesen und getippt wird die
 * Anzeige, und in der Notfalldokumentation ist eine Uhrzeit mit AM/PM eine
 * Fehlerquelle.
 *
 * Deshalb: ein gewoehnliches Textfeld, dessen Format hier gesichert wird.
 * Datumsfelder bleiben nativ (E1) — dort ist die Anzeige kosmetisch, der Wert
 * ist ISO, und ein selbstgebauter Kalender waere mobil deutlich schlechter zu
 * bedienen.
 *
 * DIE PRUEFSCHICHT LIEGT NICHT HIER. Was hier geschieht, ist Bequemlichkeit
 * und Fehlervermeidung im Browser. Verbindlich geprueft wird serverseitig:
 * local_to_utc() in db.php prueft Muster UND Wertebereich ("25:00" passt auf
 * \d{2}:\d{2}, ergaebe aber stillschweigend den naechsten Tag). Wer hier etwas
 * lockert, oeffnet keine Luecke; wer die Serverpruefung anfasst, schon.
 *
 * Verwendung: <input type="text" class="zeitfeld"> — mehr nicht. Diese Datei
 * ruestet beim Laden alle vorhandenen Felder aus und beobachtet das Dokument,
 * damit auch spaeter erzeugte Felder (Phasenzeilen im Einsatzformular) erfasst
 * werden, ohne dass die erzeugende Stelle etwas davon wissen muss.
 */
'use strict';
const EdZeitfeld = (() => {

  const MUSTER = /^([01]\d|2[0-3]):[0-5]\d$/;

  /** Ist der Wert eine gueltige Uhrzeit? Leer gilt als gueltig (= keine Angabe). */
  function gueltig(wert) {
    const v = String(wert == null ? '' : wert).trim();
    return v === '' || MUSTER.test(v);
  }

  /* Ziffernfolge -> "HH:MM", oder null, wenn daraus keine Uhrzeit wird.
   *
   * Die Faelle sind bewusst eng gehalten. Erlaubt sind nur Eingaben, deren
   * Bedeutung eindeutig ist:
   *   "9"    -> 09:00      "930"  -> 09:30
   *   "09"   -> 09:00      "0930" -> 09:30
   * Nicht erlaubt ist das Raten bei zwei Ziffern hinter dem Doppelpunkt, die
   * keine gueltige Minute ergeben ("9:75"): Ein Zurechtbiegen auf 10:15 waere
   * eine stille Aenderung einer dokumentierten Zeit. */
  function normalisiere(wert) {
    const roh = String(wert == null ? '' : wert).trim();
    if (roh === '') { return ''; }
    if (MUSTER.test(roh)) { return roh; }

    const ziffern = roh.replace(/\D/g, '');
    let h = null, m = null;
    if (ziffern.length === 1)      { h = ziffern;              m = '0'; }
    else if (ziffern.length === 2) { h = ziffern;              m = '0'; }
    else if (ziffern.length === 3) { h = ziffern.slice(0, 1);  m = ziffern.slice(1); }
    else if (ziffern.length === 4) { h = ziffern.slice(0, 2);  m = ziffern.slice(2); }
    else { return null; }

    const hh = Number(h), mm = Number(m);
    if (hh > 23 || mm > 59) { return null; }
    return String(hh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
  }

  /** Zustand des Feldes anzeigen (rot + aria-invalid bei Unsinn). */
  function markiere(feld) {
    const ok = gueltig(feld.value);
    if (ok) { feld.removeAttribute('aria-invalid'); }
    else    { feld.setAttribute('aria-invalid', 'true'); }
    /* setCustomValidity greift nur, wo das Formular ueber einen Absende-Knopf
     * laeuft — die native Pruefung findet VOR dem submit-Ereignis statt. Das
     * Einsatzformular verschluesselt im submit-Ereignis und ruft danach
     * form.submit() auf; das umgeht die Pruefung, hat sie an dieser Stelle aber
     * bereits bestanden. */
    if (typeof feld.setCustomValidity === 'function') {
      feld.setCustomValidity(ok ? '' : 'Bitte eine Uhrzeit als HH:MM eintragen (24 Stunden).');
    }
  }

  /* Waehrend des Tippens: Ziffern durchlassen, Doppelpunkt nach zwei Stellen
   * selbst setzen. Bewusst KEINE Korrektur des Wertes hier — wer "1" tippt,
   * bekommt sonst "01:00" unter den Fingern weg und kann die zweite Ziffer
   * nicht mehr anhaengen. Zurechtgerueckt wird erst beim Verlassen. */
  function beiEingabe(feld) {
    const vorher = feld.value;
    let ziffern = vorher.replace(/\D/g, '').slice(0, 4);
    let neu = ziffern;
    if (ziffern.length > 2) { neu = ziffern.slice(0, 2) + ':' + ziffern.slice(2); }
    else if (vorher.includes(':') && ziffern.length === 2) { neu = ziffern + ':'; }
    if (neu !== vorher) {
      const amEnde = feld.selectionStart === vorher.length;
      feld.value = neu;
      if (amEnde) { feld.setSelectionRange(neu.length, neu.length); }
    }
    markiere(feld);
    /* Sobald die Eingabe vollstaendig ist, gilt sie — ohne dass erst das Feld
     * verlassen werden muss. Die Suche haengt an 'change'; ohne diese Zeile
     * fiele sie gegenueber dem frueheren nativen Feld zurueck, das bei
     * vollstaendiger Zeit von selbst meldet. */
    if (feld.value !== '' && MUSTER.test(feld.value)) {
      feld.dispatchEvent(new Event('change', { bubbles: true }));
    }
  }

  function beiVerlassen(feld) {
    const n = normalisiere(feld.value);
    if (n !== null) { feld.value = n; }
    markiere(feld);
    feld.dispatchEvent(new Event('change', { bubbles: true }));
  }

  /** Ein einzelnes Feld ausruesten. Mehrfachaufruf ist folgenlos. */
  function ruesteAus(feld) {
    if (!feld || feld.dataset.zeitfeldBereit === '1') { return; }
    feld.dataset.zeitfeldBereit = '1';
    feld.type = 'text';
    feld.inputMode = 'numeric';
    feld.maxLength = 5;
    feld.autocomplete = 'off';
    if (!feld.placeholder) { feld.placeholder = 'HH:MM'; }
    /* pattern erledigt die Abweisung im Browser fuer alle Formulare, die
     * ueber einen Absende-Knopf laufen — ohne eigenes Zutun und ohne dass eine
     * aufrufende Seite daran denken muss. */
    if (!feld.pattern) { feld.pattern = '([01][0-9]|2[0-3]):[0-5][0-9]'; }
    if (!feld.title) { feld.title = 'Uhrzeit als HH:MM (24 Stunden)'; }
    feld.addEventListener('input', () => beiEingabe(feld));
    feld.addEventListener('blur', () => beiVerlassen(feld));
    markiere(feld);
  }

  /* Zustand aller Felder neu bewerten. Gebraucht, wenn Werte von aussen
   * gesetzt werden, ohne ein Ereignis auszuloesen — etwa beim Zuruecksetzen
   * der Suchfilter; sonst bliebe die rote Markierung an einem geleerten Feld
   * stehen. */
  function pruefeAlle(wurzel) {
    (wurzel || document).querySelectorAll('input.zeitfeld').forEach(markiere);
  }

  /** Alle Felder mit .zeitfeld unterhalb von wurzel ausruesten. */
  function ruesteAlleAus(wurzel) {
    (wurzel || document).querySelectorAll('input.zeitfeld').forEach(ruesteAus);
  }

  function start() {
    ruesteAlleAus(document);
    /* Nachtraeglich erzeugte Felder: Die Phasenzeilen im Einsatzformular
     * entstehen erst im Browser. Der Beobachter erspart es der erzeugenden
     * Stelle, diese Datei zu kennen. */
    if (typeof MutationObserver === 'function') {
      new MutationObserver(aenderungen => {
        aenderungen.forEach(a => a.addedNodes.forEach(k => {
          if (k.nodeType !== 1) { return; }
          if (k.matches && k.matches('input.zeitfeld')) { ruesteAus(k); }
          else { ruesteAlleAus(k); }
        }));
      }).observe(document.documentElement, { childList: true, subtree: true });
    }
  }

  if (document.readyState === 'loading') {
    document.addEventListener('DOMContentLoaded', start);
  } else {
    start();
  }

  return { gueltig, normalisiere, markiere, pruefeAlle, ruesteAus, ruesteAlleAus };
})();
