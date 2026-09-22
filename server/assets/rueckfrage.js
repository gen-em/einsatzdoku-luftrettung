/**
 * Die Konto-Rückfrage — „Hast du dein Notfallblatt noch?"
 * (P5b/AP9, E-P5b-09, -19, -20; Mockup M-P5b-02c, Teil 1).
 *
 * ===========================================================================
 * EIN DIALOG, DREI ABSCHNITTE, EIN EINZIGER ZUSTAND
 * ===========================================================================
 *
 *   frage      Ja · Nein · Später
 *   passwort   nach „Nein"
 *   schluessel der neue Wert, einmal sichtbar
 *
 * Die drei stehen alle im Markup und werden ein- und ausgeblendet. Keine
 * Nachladerei: Was hier gezeigt wird, steht spätestens ab Abschnitt 3 nur
 * noch im Arbeitsspeicher dieses Fensters, und ein `fetch` mittendrin wäre
 * eine Gelegenheit, es zu verlieren.
 *
 * ===========================================================================
 * DIE EINE STELLE, AN DER DIESE ANWENDUNG JEMANDEN FESTHÄLT
 * ===========================================================================
 *
 * In Abschnitt 3 lässt sich der Dialog nicht schließen — kein Esc, kein
 * Klick daneben, „Fertig" erst nach dem Haken. Das ist gegen die Gewohnheit
 * des Hauses und hier trotzdem richtig:
 *
 * **Der Wert auf dem Bildschirm ist nicht wiederherstellbar.** Er entstand
 * gerade im Browser, der Server kennt ihn nicht, und die alte Hülle ist
 * bereits überschrieben. Ein versehentliches Esc kostet den Rückweg zu allen
 * Patientendaten dieses Kontos — und man merkt es erst Monate später.
 *
 * Wer den Haken setzt, ohne zu notieren, hat sich entschieden. Das ist etwas
 * anderes als ein Rutscher auf der Tastatur.
 *
 * ===========================================================================
 * WAS PASSIERT, WENN DIE ERNEUERUNG ZWISCHEN BROWSER UND SERVER ABBRICHT
 * ===========================================================================
 *
 * Nichts Schlimmes, und das ist die Reihenfolge in `schluessel.js`: Der
 * Server schreibt die neue Hülle ERST, nachdem der Browser den entpackten
 * Inhaltsschlüssel gegen `pat_key_check` gehalten hat. Bricht es vorher ab,
 * steht die alte Hülle unverändert — das alte Notfallblatt gilt weiter.
 * Bricht es danach ab (Antwort verloren), gilt der neue Schlüssel, und der
 * steht auf dem Bildschirm. Deshalb wird er auch dann gezeigt, wenn das
 * Melden der Antwort an `api/rueckfrage.php` scheitert: Die Frist ist das
 * Unwichtigere von beidem.
 */
(function () {
  'use strict';

  /* ZWEI VERBRAUCHER, EIN SKRIPT (E-P5b-20, R83).
   *
   *   `[data-rueckfrage]`  der Dialog nach der Anmeldung — mit Abschnitt 1
   *                        (der Frage), geht beim Laden von selbst auf und
   *                        meldet jede Antwort an `api/rueckfrage.php`.
   *   `[data-schluessel]`  die Kontoseite unter Einstellungen — OHNE
   *                        Abschnitt 1, geht auf Knopfdruck auf und meldet
   *                        nichts. Dort gibt es keine Frist zu verschieben:
   *                        Wer freiwillig erneuert, ist nicht gefragt worden.
   *
   * Beide tragen dieselben Abschnitte 2 und 3 (`schluessel_teile.php`), und
   * das ist der Grund, warum es EIN Skript ist und nicht zwei. */
  const dlg = document.querySelector('[data-rueckfrage], [data-schluessel]');
  if (!dlg) { return; }
  const mitFrage = dlg.hasAttribute('data-rueckfrage');

  const teile = {};
  dlg.querySelectorAll('[data-rf]').forEach(function (el) {
    teile[el.getAttribute('data-rf')] = el;
  });

  const feldPw    = dlg.querySelector('#rf-pw');
  const fehler    = dlg.querySelector('[data-rf-fehler]');
  const codeFeld  = dlg.querySelector('[data-rf-code]');
  const druckWert = dlg.querySelector('[data-rf-druckwert]');
  const haken     = dlg.querySelector('[data-rf-notiert]');
  const fertig    = dlg.querySelector('[data-rf-fertig]');

  /* Ab Abschnitt 3 ist der Dialog verschlossen — siehe Kopf. */
  let festgehalten = false;

  function zeige(name) {
    for (const k in teile) { teile[k].hidden = k !== name; }
    if (name === 'passwort' && feldPw) { feldPw.focus(); }
  }

  function melde(text) {
    if (!fehler) { return; }
    fehler.textContent = text;
    fehler.hidden = text === '';
  }

  /* ---- Die Antwort an den Server ----------------------------------------
   *
   * Sie wird gemeldet, nicht abgewartet: Der Rückgabewert entscheidet über
   * nichts, was hier noch zu tun wäre. Ein Fehler landet im Fehlerprotokoll
   * des Browsers und sonst nirgends — die Frage kommt dann beim nächsten
   * Anmelden wieder, und das ist die richtige Richtung für einen Fehler
   * dieser Art.
   *
   * DER SENDER IST `EdApi.postForm` (Schritt 15 AP8, Zaehlzeile Z29). Er
   * setzt `csrf`, `credentials` und die Kopfzeile selbst und WIRFT NIE --
   * deshalb steht hier kein `catch` mehr. Kein `o.vorgang` und keine
   * Anzeige: Die Stille dieser Stelle ist gewollt, siehe oben.
   *
   * WARUM DAS PROTOKOLL AN `daten === null` HAENGT UND NICHT AN `!ok`. Es
   * soll genau die Faelle melden, die frueher das `catch` erreichten, und
   * das waren zwei: ein abgebrochenes `fetch` UND eine Antwort, die kein
   * JSON war. Der zweite ist der wichtigere -- eine leere Sitzung leitet
   * `auth_guard.php` auch auf `/api/` nach `login.php` um, und dann kommt
   * HTML mit HTTP 200 zurueck. An `!ok` gehaengt erschiene die Zeile
   * zusaetzlich bei einer 403 oder 400 mit gueltigem JSON; das waere
   * strenger als heute und damit eine Verhaltensaenderung. Dass diese
   * beiden Antworten hier ungeprueft durchlaufen, ist ein Befund und in
   * AP8 bewusst nicht behoben. */
  let gemeldet = false;
  async function antworte(antwort) {
    gemeldet = true;
    if (!mitFrage) { return null; }
    const ergebnis = await EdApi.postForm('api/rueckfrage.php', { antwort: antwort });
    if (ergebnis.daten === null) {
      console.error('Rückfrage nicht gemeldet:', ergebnis.meldung);
    }
    return ergebnis.daten;
  }

  function schliesse() {
    if (festgehalten) { return; }
    if (typeof dlg.close === 'function') { dlg.close(); } else { dlg.removeAttribute('open'); }
  }

  /* ---- Abschnitt 1 ------------------------------------------------------ */

  if (mitFrage) {
    dlg.querySelector('[data-rf-ja]').addEventListener('click', function () {
      antworte('beantwortet');
      schliesse();
    });

    dlg.querySelector('[data-rf-nein]').addEventListener('click', function () {
      melde('');
      if (feldPw) { feldPw.value = ''; }
      zeige('passwort');
    });

    const spaeter = dlg.querySelector('[data-rf-spaeter]');
    if (spaeter) {
      spaeter.addEventListener('click', function () {
        antworte('spaeter');
        schliesse();
      });
    }
  }

  /* ---- Abschnitt 2: die Erneuerung -------------------------------------- */

  dlg.querySelector('[data-rf-zurueck]').addEventListener('click', function () {
    melde('');
    if (mitFrage) { zeige('frage'); } else { schliesse(); }
  });

  const erzeugen = dlg.querySelector('[data-rf-erzeugen]');
  erzeugen.addEventListener('click', async function () {
    const pw = feldPw ? feldPw.value : '';
    if (pw === '') { melde('Bitte das Passwort eingeben.'); return; }

    melde('');
    erzeugen.disabled = true;
    try {
      /* DIE KOMPONENTE (E-P5b-20, R83). Sie rechnet, prüft gegen
       * `pat_key_check` und sendet; sie wirft mit einer Meldung, die man
       * anzeigen kann. Zwei Verbraucher haben sie: dieser Dialog und die
       * Kontoseite unter Einstellungen. */
      const code = await window.EdSchluessel.erneuern({
        pw:       pw,
        salt:     KDF_SALT,
        iter:     KDF_ITER,
        wrapPw:   PAT_WRAP,
        keyCheck: typeof PAT_KEY_CHECK !== 'undefined' ? PAT_KEY_CHECK : null,
        anteile:  typeof KONTO_ANTEILE !== 'undefined' ? KONTO_ANTEILE : null,
        csrf:     typeof CSRF !== 'undefined' ? CSRF : '',
      });

      /* AB HIER IST DER ALTE SCHLÜSSEL UNGÜLTIG. Der Wert steht auf dem
       * Bildschirm und nirgends sonst — deshalb erst zeigen, dann melden. */
      codeFeld.textContent = code;
      if (druckWert) { druckWert.value = code; }
      if (feldPw) { feldPw.value = ''; }
      festgehalten = true;
      zeige('schluessel');
      antworte('beantwortet');
    } catch (e) {
      melde(e && e.message ? e.message : 'Die Erneuerung ist fehlgeschlagen.');
    } finally {
      erzeugen.disabled = false;
    }
  });

  /* ---- Abschnitt 3 ------------------------------------------------------ */

  if (haken && fertig) {
    haken.addEventListener('change', function () {
      fertig.disabled = !haken.checked;
    });
    fertig.addEventListener('click', function () {
      if (!haken.checked) { return; }
      festgehalten = false;
      schliesse();
    });
  }

  /* Esc und der Klick daneben — beide gehen durch `cancel`, und beide werden
   * in Abschnitt 3 abgewiesen. */
  dlg.addEventListener('cancel', function (ev) {
    if (festgehalten) { ev.preventDefault(); }
  });

  /* WEGGEKLICKT OHNE ANTWORT. Der Server haelt den Dialog daraufhin bis zur
   * naechsten Anmeldung zurueck — er soll nicht bei jedem Aufruf der
   * Startseite wieder aufgehen. Die FRIST bleibt faellig; das ist der
   * Unterschied zu „Spaeter".
   *
   * `close` und nicht `cancel`, weil auch die Knoepfe hier durchkommen. Wer
   * schon geantwortet hat, meldet nicht zweimal — `gemeldet` merkt es sich. */
  dlg.addEventListener('close', function () {
    if (!gemeldet) { antworte('weggeklickt'); }
  });

  /* ---- Auf ------------------------------------------------------------- */

  function auf() {
    gemeldet = false;
    festgehalten = false;
    melde('');
    if (feldPw) { feldPw.value = ''; }
    zeige(mitFrage ? 'frage' : 'passwort');
    if (typeof dlg.showModal === 'function') {
      dlg.showModal();
    } else {
      /* Ohne `showModal` (sehr alter Browser) steht der Dialog offen da statt
       * gar nicht. Die Frage ist dann bedienbar, nur nicht modal. */
      dlg.setAttribute('open', '');
    }
  }

  if (mitFrage) {
    auf();
  } else {
    /* DER KNOPF AUF DER KONTOSEITE. `data-dialog` waere der Weg des Hauses
     * (`assets/dialog.js`), und er ist hier falsch: Er oeffnet den Dialog,
     * ohne die Abschnitte zu stellen — die Kontoseite saehe beim zweiten Mal
     * noch den Schluessel vom ersten. `auf()` raeumt auf und oeffnet dann. */
    const knopf = document.querySelector('[data-schluessel-auf]');
    if (knopf) { knopf.addEventListener('click', auf); }
  }
})();
