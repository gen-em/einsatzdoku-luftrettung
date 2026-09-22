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

  /* DER SENDER IST `EdApi.postForm` (Schritt 15, AP8). Das Feld `csrf` haengt
   * EdApi selbst an, die Erfolgsregel (HTTP UND Fachschluessel) liegt dort,
   * und ein Netzfehler kommt als `status: 0` zurueck statt als Wurf.
   *
   * DIE ARRAY-REGEL IST DER GRUND, WARUM HIER GENAU HINGESEHEN WURDE. Ein
   * Feld, dessen Wert ein Array ist, muss als `name[]` hinausgehen. Sonst
   * liest `api/schluesselblatt_pruefen.php` bei `$_POST['gruppen']` eine
   * Zeichenkette, `is_array()` schlaegt fehl (Zeile 190/191 dort), alle vier
   * Gruppen werden gegen den Leerstring gehalten und `rate_misserfolg()`
   * zaehlt einen Fehlversuch -- die Betreiberin sperrt sich aus ihrer eigenen
   * Rueckfrage aus, ohne dass eine Zeile falsch aussaehe. `EdApi.postForm`
   * fuehrt dieselbe Regel und nichts daneben: `csrf` zuerst, dann je Feld
   * `Array.isArray` -> `append(k + '[]', w)`, sonst `set(k, w)`
   * (`assets/api.js`, Zeilen 161-171, nachgelesen).
   *
   * `EdApi` NICHT beim Laden in eine Variable nehmen -- der Zugriff steht in
   * dieser Funktion. Auf `index.php` kommt `ui_geruest_ende()` vor diesem
   * Skript, `api.js` ist also schon da, wenn `stellen()` unten laeuft. */
  function senden(daten, vorgang) {
    return EdApi.postForm('api/schluesselblatt_pruefen.php', daten,
                          { vorgang: vorgang });
  }

  /* DIESER ENDPUNKT NENNT SEIN SATZFELD `text`, nicht `meldung`.
   *
   * Das war beim Umbau zuerst ein Problem und ist keins mehr: Die
   * Vorrangkette von `EdApi` liest `text` seit AP8 mit (`grund()` in
   * assets/api.js). Fuenf Saetze haengen daran -- kein_schluessel,
   * gesperrt (zweimal), keine_frage und falsch --, darunter der wichtigste
   * der ganzen Rueckfrage: „Stimmt nicht. Noch 2 Versuche, dann sperrt die
   * Anmeldung diesen Weg fuer 15 Minuten."
   *
   * Gefunden wurde die Luecke beim Gegenlesen: Diese Datei hatte dafuer
   * einen eigenen Helfer `fehlersatz()`, der `daten.text` VOR der fertigen
   * Meldung las -- und damit den Vorgangsnamen verlor, den der einheitliche
   * Satzbau vorschreibt. Der Helfer ist fort, die Kette kann es jetzt.
   *
   * DASS DER SERVER ZWEI NAMEN FUER DIESELBE SACHE FUEHRT, bleibt: neun
   * Stellen `text` (hier und in schluessel_erneuern.php), zweiunddreissig
   * `meldung` (alle uebrigen), keine schickt beides. Das zusammenzufuehren
   * ruehrt an Antwortvertraege und gehoert nicht in dieses Paket. */

  /* ---- Die Frage stellen ------------------------------------------------ */

  function stellen() {
    return senden({ aktion: 'stellen' }, 'Das Stellen der Frage').then(function (a) {
      /* `a.daten.ok` WIRD MITGEPRUEFT, und das ist kein Rest aus der alten
       * Fassung. `EdApi` prueft `daten.ok !== false` -- eine 200 mit einem
       * Rumpf OHNE das Feld besteht dort. Dieser Endpunkt setzt `ok` aber
       * immer, und die Zeilen darunter lesen `a.daten.fragen`. Ohne die
       * zweite Bedingung bliebe bei einer leeren 200 ein bedienbarer Knopf
       * ohne Felder stehen, und `forEach` wuerfe auf undefined. */
      if (!a.ok || !a.daten.ok) {
        melde(a.meldung);
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
    senden({ aktion: 'pruefen', gruppen: werte }, 'Die Prüfung').then(function (a) {
      if (a.ok && a.daten.ok) {   // siehe den Kommentar in stellen()
        if (typeof dlg.close === 'function') { dlg.close(); }
        else { dlg.removeAttribute('open'); }
        return;
      }
      melde(a.meldung);
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
      if (a.status === 429 || (a.daten && a.daten.rest === 0)) {
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
    /* NUR DER NETZFEHLER WIRD GEMELDET, so wie bisher: Das alte `.catch` fing
     * genau den Wurf des `fetch`, und `status: 0` ist derselbe Fall. Eine
     * abgelehnte Antwort (HTTP 400) blieb hier schon immer still -- das ist
     * eine Luecke, aber keine, die dieses Paket aufmacht. Statt des
     * Browsertexts „Failed to fetch" steht jetzt ein deutscher Satz in der
     * Konsole; den Fehlergegenstand selbst gibt EdApi nicht heraus. */
    /* OHNE `vorgang`: Die Konsolenzeile nennt den Vorgang bereits
     * („Spaeter nicht gemeldet:"). Mit einem zweiten Vorgangsnamen stuenden
     * dort zwei Fehlersaetze hintereinander -- gefunden beim Gegenlesen. */
    EdApi.postForm('api/rueckfrage.php', { antwort: 'blatt_spaeter' })
      .then(function (a) {
        if (a.status === 0) { console.error('Später nicht gemeldet:', a.meldung); }
      });
    if (typeof dlg.close === 'function') { dlg.close(); }
    else { dlg.removeAttribute('open'); }
  });

  /* ---- Auf -------------------------------------------------------------- */

  pruefen.disabled = true;
  if (typeof dlg.showModal === 'function') { dlg.showModal(); }
  else { dlg.setAttribute('open', ''); }
  stellen();
})();
