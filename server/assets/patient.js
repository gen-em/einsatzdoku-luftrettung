/* Geschuetzte PatientInnendaten — gemeinsame Hilfsfunktionen.
 *
 * Diese Datei rechnet nur; sie sieht die Daten erst, nachdem crypto.js sie
 * entschluesselt hat. Eingebunden von index.php, einsatz.php, einsatz_form.php
 * und zeitraum.php, damit alle vier dieselbe Altersberechnung verwenden.
 */
(function () {
  'use strict';

  /**
   * Alter zum Einsatzzeitpunkt aus dem Geburtsdatum.
   * @param {string} dob   Geburtsdatum "JJJJ-MM-TT"
   * @param {string} tag   Einsatztag "JJJJ-MM-TT" (ohne Angabe: heute)
   * @returns {number|null} volle Lebensjahre, oder null bei ungueltiger Eingabe
   */
  function alterAm(dob, tag) {
    if (!dob || !/^\d{4}-\d{2}-\d{2}$/.test(dob)) { return null; }
    const g = new Date(dob + 'T00:00:00');
    const b = (tag && /^\d{4}-\d{2}-\d{2}$/.test(tag))
      ? new Date(tag + 'T00:00:00') : new Date();
    if (isNaN(g.getTime()) || isNaN(b.getTime()) || g > b) { return null; }

    let jahre = b.getFullYear() - g.getFullYear();
    // Geburtstag im Bezugsjahr noch nicht erreicht? Dann ein Jahr abziehen.
    const vorGeburtstag = (b.getMonth() < g.getMonth())
      || (b.getMonth() === g.getMonth() && b.getDate() < g.getDate());
    if (vorGeburtstag) { jahre--; }
    return jahre >= 0 && jahre <= 130 ? jahre : null;
  }

  /**
   * Anzuzeigendes Alter: bevorzugt aus dem Geburtsdatum berechnet, sonst der
   * von Hand eingetragene Wert (z. B. geschaetzt bei unbekannter Person).
   */
  function alterAnzeige(pat, tag) {
    if (!pat) { return null; }
    const berechnet = alterAm(pat.dob, tag);
    if (berechnet !== null) { return berechnet; }
    return (pat.age != null) ? pat.age : null;
  }

  /** "Nachname, Vorname" — je nachdem, was vorhanden ist. */
  function name(pat) {
    if (!pat) { return ''; }
    const n = (pat.last || '').trim();
    const v = (pat.first || '').trim();
    if (n && v) { return n + ', ' + v; }
    return n || v || '';
  }

  /** "JJJJ-MM-TT" -> "TT.MM.JJJJ" */
  function datumDe(iso) {
    if (!iso || !/^\d{4}-\d{2}-\d{2}$/.test(iso)) { return ''; }
    const [j, m, t] = iso.split('-');
    return `${t}.${m}.${j}`;
  }

  /* ---- Entschluesseln und anzeigen (Baustein B8) ------------------------
   *
   * WARUM ES DIESEN BAUSTEIN GIBT
   * Fuenf Stellen entschluesseln je Datensatz den Patientenblock und fangen
   * einen Fehlschlag ab, ohne ihn nach aussen sichtbar zu machen. Die Absicht
   * ist richtig — ein unlesbarer Datensatz darf die Liste nicht zerstoeren —,
   * es fehlt aber die Unterscheidung:
   *
   *   Einsatz OHNE Angaben        zeigt "–"
   *   Einsatz NICHT ENTSCHLUESSELBAR  zeigt ebenfalls "–"
   *
   * Das ist der Anfang einer Kette, an deren Ende Daten verschwinden: Wer den
   * Unterschied nicht sieht, merkt nicht, dass sein Inhaltsschluessel nicht
   * mehr passt — und erstellt als Naechstes eine Sicherung.
   *
   * Diese Schleife liefert deshalb je Datensatz einen ZUSTAND und zaehlt mit.
   */

  /** Anzeigezeichen fuer einen Datensatz, der sich nicht entschluesseln laesst.
   *  Bewusst NICHT der Gedankenstrich, der "keine Angaben" bedeutet. */
  const ZEICHEN_UNLESBAR = '⚠';

  /**
   * Entschluesselt EINEN Patientenblock.
   *
   * Der eine Ort, an dem die Unterscheidung getroffen wird. Die Seiten, die
   * darauf aufbauen, brauchen ihre eigene Darstellung — sie sollen sich aber
   * nicht jede fuer sich ueberlegen, was ein Fehlschlag bedeutet.
   *
   * @returns {Promise<{zustand:'ok'|'leer'|'unlesbar', daten:Object|null}>}
   */
  async function entschluessle(ck, blob) {
    if (!blob || !ck) { return { zustand: 'leer', daten: null }; }
    try {
      return { zustand: 'ok', daten: JSON.parse(await EdCrypto.decrypt(ck, blob)) || {} };
    } catch (e) {
      return { zustand: 'unlesbar', daten: null };
    }
  }

  /**
   * Setzt oder entfernt den Hinweis auf unlesbare Datensaetze.
   *
   * Legt das Hinweisfeld bei Bedarf als erstes Kind von <main class="page">
   * an, damit jede Seite es bekommt, ohne dass ihr Aufbau angefasst werden
   * muss.
   */
  function zeigeUnlesbar(zahl) {
    const main = document.querySelector('main.page');
    if (!main) { return; }
    let el = document.getElementById('patwarn');
    if (!zahl || !zahl.unlesbar) { if (el) { el.remove(); } return; }
    if (!el) {
      el = document.createElement('p');
      el.id = 'patwarn';
      el.className = 'alert alert-warn';
      main.insertBefore(el, main.firstChild);
    }
    el.textContent = hinweisUnlesbar(zahl);
  }

  /**
   * Entschluesselt den Patientenblock einer Liste von Einsaetzen.
   *
   * Schreibt je Einsatz:
   *   m._pat      entschluesseltes Objekt (oder null)
   *   m._patState 'ok' | 'leer' | 'unlesbar'
   *
   * @param {Array}  liste     Einsaetze mit dem Feld pat_blob
   * @param {string} ck        Inhaltsschluessel (hex) oder null/leer
   * @returns {Promise<{ok:number, leer:number, unlesbar:number, gesperrt:boolean}>}
   */
  async function entschluessleListe(liste, ck) {
    const zahl = { ok: 0, leer: 0, unlesbar: 0, gesperrt: !ck };
    for (const m of (liste || [])) {
      const blob = m && m.pat_blob;
      if (!blob) { m._pat = null; m._patState = 'leer'; zahl.leer++; continue; }
      if (!ck)   { m._pat = null; m._patState = 'leer'; continue; }
      const r = await entschluessle(ck, blob);
      m._pat = r.daten;
      m._patState = r.zustand;
      // Fehlschlag wird NICHT verschluckt: Der Zustand bleibt am Datensatz
      // stehen, damit die Anzeige ihn kenntlich machen kann.
      if (r.zustand === 'unlesbar') { m._patFehler = true; zahl.unlesbar++; }
      else { zahl.ok++; }
    }
    return zahl;
  }

  /**
   * Hinweistext, wenn Datensaetze unlesbar sind — oder eine leere Zeichenkette.
   *
   * Sind es VIELE, ist die Ursache grundsaetzlich (falscher Inhaltsschluessel)
   * und nicht ein beschaedigter Einzeldatensatz. Der Text unterscheidet die
   * beiden Faelle, weil die Antwort darauf eine andere ist.
   */
  function hinweisUnlesbar(zahl) {
    if (!zahl || !zahl.unlesbar) { return ''; }
    if (zahl.ok === 0 && zahl.unlesbar > 1) {
      return `Keiner der ${zahl.unlesbar} geschützten Einträge lässt sich entschlüsseln. `
           + 'Das deutet auf einen nicht passenden Schlüssel hin — die Daten sind '
           + 'vorhanden, aber ohne den richtigen Schlüssel nicht lesbar. Vor weiteren '
           + 'Schritten bitte den Wiederherstellungsschlüssel bereithalten.';
    }
    return zahl.unlesbar === 1
      ? '1 Eintrag lässt sich nicht entschlüsseln und ist mit ' + ZEICHEN_UNLESBAR + ' gekennzeichnet.'
      : `${zahl.unlesbar} Einträge lassen sich nicht entschlüsseln und sind mit `
        + ZEICHEN_UNLESBAR + ' gekennzeichnet.';
  }

  window.EdPat = { alterAm, alterAnzeige, name, datumDe,
                   entschluessle, entschluessleListe, hinweisUnlesbar,
                   zeigeUnlesbar, ZEICHEN_UNLESBAR };
})();
