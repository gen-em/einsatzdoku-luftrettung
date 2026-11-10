/* Druckblätter ohne Netz — Notfallblatt und Codeblatt (KA-02, F-KA-01, -36).
 * ===========================================================================
 *
 * WOFÜR. Der Wiederherstellungsschlüssel öffnet den Inhaltsschlüssel ohne
 * Passwort und ohne Server-Anteil. Bis KA-02 ging er zum Drucken per POST an
 * `notfallblatt.php` — über das Netz, durch jeden Proxy und jedes
 * Anfrageprotokoll dazwischen. Jetzt verlässt er den Tab nicht: Das
 * Blatt öffnet sich leer, meldet sich beim Fenster, das es geöffnet hat, und
 * bekommt seine Werte von dort über `postMessage`.
 *
 * WARUM `postMessage` UND NICHT DIE FRAGMENTKENNUNG (`#code`). Die erreicht
 * den Server zwar nicht, steht aber im Verlauf, und viele Browser gleichen
 * den Verlauf zwischen Geräten ab. Eine Nachricht von Fenster zu Fenster
 * steht nirgends.
 *
 * ZWEI SEITEN, EINE DATEI:
 *
 *   Absender   jeder Knopf mit `data-blatt-ziel` (`notfallblatt.php` oder
 *              `codeblatt.php`). Die Werte stehen in `data-blatt-code`,
 *              `data-blatt-konto` und `data-blatt-codes` (JSON-Liste); wer
 *              den Code erst im Browser erzeugt, setzt das Attribut dann.
 *   Empfänger  ein Blatt mit `data-blatt-empfang`. Es fragt sein Ursprungs-
 *              fenster und füllt sich mit `textContent` — nie mit Markup.
 *
 * WER ANTWORTEN DARF. Beide Seiten prüfen den Ursprung (`location.origin`)
 * UND das Fenster: Der Absender antwortet nur dem Fenster, das er selbst
 * geöffnet hat; das Blatt nimmt nur Nachrichten von seinem `opener`. Eine
 * fremde Seite, die das Blatt öffnet, bekommt nichts hinein — ihr Ursprung
 * ist ein anderer.
 *
 * OHNE URSPRUNGSFENSTER KEIN BLATT. Wer die Adresse aus dem Verlauf aufruft,
 * sieht die Meldung „lässt sich nicht nachträglich drucken" — wie bisher.
 */
(function () {
  'use strict';
  /* EINMAL JE SEITE: Zweimal eingebunden öffnete jeder Klick zwei Fenster. */
  if (window.EdBlattUebergabe) { return; }
  window.EdBlattUebergabe = true;

  const BEREIT = 'nadoku-blatt-bereit';
  const DATEN  = 'nadoku-blatt-daten';
  const ZIELE  = ['notfallblatt.php', 'codeblatt.php'];
  const URSPRUNG = window.location.origin;

  /* ---- Absender --------------------------------------------------------- */

  document.addEventListener('click', function (e) {
    const knopf = e.target && e.target.closest ? e.target.closest('[data-blatt-ziel]') : null;
    if (!knopf) { return; }
    e.preventDefault();
    const ziel = knopf.getAttribute('data-blatt-ziel');
    if (ZIELE.indexOf(ziel) < 0) { return; }

    const daten = { art: DATEN };
    if (knopf.dataset.blattCode)  { daten.code  = knopf.dataset.blattCode; }
    if (knopf.dataset.blattKonto) { daten.konto = knopf.dataset.blattKonto; }
    if (knopf.dataset.blattCodes) {
      try { daten.codes = JSON.parse(knopf.dataset.blattCodes); } catch (_) { daten.codes = []; }
    }

    const fenster = window.open(ziel, '_blank');
    if (!fenster) {
      /* EIN BLOCKIERTES FENSTER SAGT ES, statt still nichts zu tun. */
      const hinweis = knopf.parentNode && knopf.parentNode.querySelector('[data-blatt-blockiert]');
      if (hinweis) { hinweis.hidden = false; }
      return;
    }
    function antworten(ev) {
      if (ev.source !== fenster || ev.origin !== URSPRUNG) { return; }
      if (!ev.data || ev.data.art !== BEREIT) { return; }
      window.removeEventListener('message', antworten);
      fenster.postMessage(daten, URSPRUNG);
    }
    window.addEventListener('message', antworten);
    /* Nach zwei Minuten ist das Fenster entweder da oder zu. */
    window.setTimeout(function () { window.removeEventListener('message', antworten); }, 120000);
  });

  /* ---- Empfänger -------------------------------------------------------- */

  const blatt = document.querySelector('[data-blatt-empfang]');
  if (!blatt) { return; }
  const art = blatt.getAttribute('data-blatt-empfang');
  const opener = window.opener;
  if (!opener) { return; }

  /* DAS ALPHABET IST DAS AUS `crypto.js` (`RC_CHARS`) und `totp_lib.php`
   * (`TOTP_CODE_ZEICHEN`) — ohne 0, 1, I, L, O und U. Wer hier großzügiger
   * prüfte als der Erzeuger, druckte einen Code, den niemand annimmt. */
  const Z = 'ABCDEFGHJKMNPQRSTVWXYZ23456789';
  const CODE_RE = new RegExp('^[' + Z + ']{4}(-[' + Z + ']{4}){4}$');
  const ZF_RE   = new RegExp('^[' + Z + ']{8}$');
  const ADRESSE_RE = /^[^\s@<>"']{1,64}@[^\s@<>"']{1,190}\.[^\s@<>"']{1,40}$/;

  function zeigen() {
    blatt.querySelectorAll('[data-blatt-ohne]').forEach(function (el) { el.hidden = true; });
    blatt.querySelectorAll('[data-blatt-mit]').forEach(function (el) { el.hidden = false; });
  }
  function kontoSetzen(konto) {
    const feld = blatt.querySelector('[data-blatt-konto]');
    /* DIE SITZUNG GEHT VOR: Steht die Adresse schon vom Server da, bleibt sie. */
    if (!feld || feld.textContent !== '' || typeof konto !== 'string') { return; }
    if (!ADRESSE_RE.test(konto)) { return; }
    feld.textContent = konto;
    const zeile = blatt.querySelector('[data-blatt-kontozeile]');
    if (zeile) { zeile.hidden = false; }
  }

  function fuellen(d) {
    if (art === 'notfallblatt') {
      const code = typeof d.code === 'string' ? d.code.trim().toUpperCase() : '';
      if (!CODE_RE.test(code)) { return; }
      const gruppen = blatt.querySelectorAll('[data-blatt-gruppe]');
      const teile = code.split('-');
      if (gruppen.length !== teile.length) { return; }
      gruppen.forEach(function (el, i) { el.textContent = teile[i]; });
      kontoSetzen(d.konto);
      zeigen();
      return;
    }
    if (art === 'codeblatt') {
      const liste = blatt.querySelector('[data-blatt-codes]');
      if (!liste || !Array.isArray(d.codes)) { return; }
      const codes = [];
      d.codes.forEach(function (roh) {
        const c = typeof roh === 'string' ? roh.replace(/[\s-]+/g, '').toUpperCase() : '';
        if (ZF_RE.test(c) && codes.length < 10) { codes.push(c); }
      });
      if (codes.length === 0) { return; }
      codes.forEach(function (c) {
        const li = document.createElement('li');
        li.textContent = c.slice(0, 4) + ' ' + c.slice(4);
        liste.appendChild(li);
      });
      zeigen();
    }
  }

  window.addEventListener('message', function empfangen(ev) {
    if (ev.source !== opener || ev.origin !== URSPRUNG) { return; }
    if (!ev.data || ev.data.art !== DATEN) { return; }
    window.removeEventListener('message', empfangen);
    fuellen(ev.data);
  });
  try { opener.postMessage({ art: BEREIT }, URSPRUNG); } catch (_) { /* fremdes Fenster */ }
})();
