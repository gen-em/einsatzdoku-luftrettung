/**
 * Die Betreiber-Rückfrage zum Schlüsselblatt (P5b/AP9, E-P5b-10, -21;
 * Mockup M-P5b-02c, Teil 2).
 *
 * DER NAME IST `schluesselblatt.js` UND NICHT `blatt.js`. Den zweiten gibt es
 * längst: das Aktionsmenü, das mobil von unten auffährt (`window.edBlatt`).
 * Ich hatte diese Datei zuerst so genannt und die andere damit überschrieben
 * — aufgefallen an `git status`, nicht an einem Fehler, denn beide sind
 * gültiges JavaScript und keine Seite lädt beide. „Blatt" heisst in diesem
 * Haus zweierlei; der längere Name sagt, welches gemeint ist.
 *
 * ===========================================================================
 * DIE FELDER ENTSTEHEN HIER, WEIL NUR DER SERVER WEISS, WELCHE
 * ===========================================================================
 *
 * Beim Öffnen fragt dieses Skript `aktion=stellen`. Der Server würfelt vier
 * Positionen (zwei je Wert), legt sie in seine Sitzung und nennt sie. Erst
 * dann stehen hier vier Felder mit den richtigen Beschriftungen.
 *
 * Der Umweg ist der Zweck: Ein Blatt, nach dem immer dieselben Gruppen
 * gefragt werden, lässt sich mit einem Zettel beantworten, auf dem vier
 * Gruppen stehen.
 *
 * ===========================================================================
 * VIER FELDER ZU VIER ZEICHEN, MIT SPRUNG
 * ===========================================================================
 *
 * `maxlength=4` und ein Sprung ins nächste Feld, sobald vier Zeichen stehen
 * (Mockup: „automatischer Sprung zum nächsten Feld"). Das ist Bequemlichkeit
 * und kein Zwang — wer tabbt, tabbt; wer zurückgeht, kann korrigieren.
 *
 * ES WIRD NICHTS VALIDIERT, WÄHREND GETIPPT WIRD. Eine rote Umrandung nach
 * dem dritten Zeichen sagte „falsch", wo nur „noch nicht fertig" gemeint ist.
 * Geprüft wird auf Knopfdruck, vom Server, und dann für alle vier auf einmal.
 *
 * ===========================================================================
 * WAS DIESES SKRIPT NIE SIEHT
 * ===========================================================================
 *
 * Die Werte. Der Server schickt Positionen und die achtstellige Kennung,
 * sonst nichts; verglichen wird dort. Ein Vergleich hier wäre eine Bitte,
 * keine Wache — und er müsste die Werte im Browser haben, also genau das
 * preisgeben, wonach gefragt wird.
 */
(function () {
  'use strict';

  const dlg = document.querySelector('[data-blatt-dialog]');
  if (!dlg) { return; }

  const felder   = dlg.querySelector('[data-blatt-felder]');
  const fehler   = dlg.querySelector('[data-blatt-fehler]');
  const kennzeile = dlg.querySelector('[data-blatt-kennung]');
  const pruefen  = dlg.querySelector('[data-blatt-pruefen]');
  const spaeter  = dlg.querySelector('[data-blatt-spaeter]');

  function melde(text) {
    fehler.textContent = text;
    fehler.hidden = text === '';
  }

  function senden(daten) {
    const leib = new URLSearchParams();
    leib.set('csrf', typeof CSRF !== 'undefined' ? CSRF : '');
    for (const k in daten) {
      if (Array.isArray(daten[k])) {
        daten[k].forEach(function (w) { leib.append(k + '[]', w); });
      } else {
        leib.set(k, daten[k]);
      }
    }
    return fetch('api/schluesselblatt_pruefen.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: leib.toString(),
    }).then(async function (a) {
      let d = {};
      try { d = await a.json(); } catch (e) { /* unten */ }
      return { status: a.status, daten: d };
    });
  }

  /* ---- Die Frage stellen ------------------------------------------------ */

  function stellen() {
    return senden({ aktion: 'stellen' }).then(function (a) {
      if (!a.daten.ok) {
        melde(a.daten.text || 'Die Frage lässt sich gerade nicht stellen ('
                            + a.status + ').');
        pruefen.disabled = true;
        return;
      }
      pruefen.disabled = false;
      melde('');

      /* Die Kennung als Hinweis, WELCHES Blatt gemeint ist. Mehrere Werte
       * haben mehrere Kennungen; genannt wird die des ersten — sie steht auf
       * demselben Blatt wie die übrigen. */
      if (kennzeile && a.daten.kennung && a.daten.kennung.length) {
        kennzeile.textContent = '(Kennung ' + a.daten.kennung[0].kennung + ')';
      }

      felder.textContent = '';
      a.daten.fragen.forEach(function (f, i) {
        const huelle = document.createElement('div');
        huelle.className = 'feld blatt-gruppe';

        const label = document.createElement('label');
        label.className = 'feld-label';
        label.setAttribute('for', 'blatt-g' + i);
        label.textContent = f.name + ' · Gruppe ' + f.nr;

        const ein = document.createElement('input');
        ein.className = 'feld-eingabe blatt-gruppe-feld';
        ein.id = 'blatt-g' + i;
        ein.type = 'text';
        ein.maxLength = 4;
        ein.autocomplete = 'off';
        ein.spellcheck = false;
        ein.setAttribute('inputmode', 'latin');

        /* SPRUNG INS NAECHSTE FELD nach vier Zeichen. Nur vorwärts und nur,
         * wenn wirklich vier stehen — ein Sprung beim Löschen wäre eine
         * Falle. */
        ein.addEventListener('input', function () {
          if (ein.value.length >= 4) {
            const naechst = felder.querySelector('#blatt-g' + (i + 1));
            if (naechst) { naechst.focus(); }
          }
        });

        huelle.appendChild(label);
        huelle.appendChild(ein);
        felder.appendChild(huelle);
      });

      const erstes = felder.querySelector('input');
      if (erstes) { erstes.focus(); }
    });
  }

  /* ---- Prüfen ----------------------------------------------------------- */

  pruefen.addEventListener('click', function () {
    const werte = Array.prototype.map.call(
      felder.querySelectorAll('input'), function (e) { return e.value; });
    if (werte.length === 0 || werte.some(function (w) { return w.trim() === ''; })) {
      melde('Bitte alle vier Gruppen eintragen.');
      return;
    }

    pruefen.disabled = true;
    senden({ aktion: 'pruefen', gruppen: werte }).then(function (a) {
      if (a.daten.ok) {
        if (typeof dlg.close === 'function') { dlg.close(); }
        else { dlg.removeAttribute('open'); }
        return;
      }
      melde(a.daten.text || 'Die Prüfung ist fehlgeschlagen (' + a.status + ').');
      /* GESPERRT HEISST ZU. Weitere Felder anzubieten, während der Topf
       * gesperrt ist, wäre eine Einladung zum Weiterraten — und jeder
       * Versuch verlängerte die Sperre über die Leiter. */
      /* 429 heisst „schon gesperrt"; `rest === 0` heisst „mit DIESEM Versuch
       * gesperrt". Beides ist derselbe Zustand, und beides muss die Felder
       * wegnehmen.
       *
       * Ich hatte nur die 429 behandelt. Gemessen: Der dritte Fehlversuch
       * meldete „Dieser Weg ist jetzt für eine Weile gesperrt" — und liess
       * vier Felder und einen bedienbaren Knopf stehen. Wer dann tippt,
       * bekommt beim naechsten Klick die 429 und hat die Sperre ueber die
       * Leiter verlaengert, ohne es zu wollen. */
      if (a.status === 429 || a.daten.rest === 0) {
        felder.textContent = '';
        pruefen.disabled = true;
        return;
      }
      /* DIE FRAGE BLEIBT DIESELBE (siehe Endpunkt): Neue Positionen bei
       * jedem Fehlversuch wären neue Würfel für den, der rät. Die Felder
       * werden nur geleert. */
      felder.querySelectorAll('input').forEach(function (e) { e.value = ''; });
      const erstes = felder.querySelector('input');
      if (erstes) { erstes.focus(); }
      pruefen.disabled = false;
    });
  });

  /* ---- Später ----------------------------------------------------------- */

  spaeter.addEventListener('click', function () {
    /* KEIN ENDPUNKT, NUR EIN MERKMAL IN DER SITZUNG — über
     * `api/rueckfrage.php`, das dafür `blatt_spaeter` kennt. Warum nicht
     * sieben Tage: siehe Kopf von `blatt_dialog.php`. */
    const leib = new URLSearchParams();
    leib.set('csrf', typeof CSRF !== 'undefined' ? CSRF : '');
    leib.set('antwort', 'blatt_spaeter');
    fetch('api/rueckfrage.php', {
      method: 'POST', credentials: 'same-origin',
      headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
      body: leib.toString(),
    }).catch(function (e) { console.error('Später nicht gemeldet:', e); });
    if (typeof dlg.close === 'function') { dlg.close(); }
    else { dlg.removeAttribute('open'); }
  });

  /* ---- Auf -------------------------------------------------------------- */

  pruefen.disabled = true;
  if (typeof dlg.showModal === 'function') { dlg.showModal(); }
  else { dlg.setAttribute('open', ''); }
  stellen();
})();
