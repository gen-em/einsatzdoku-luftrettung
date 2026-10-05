/* pow.js — die Rechenaufgabe der Registrierung steuern (Schritt 18, SR-08;
 * E-SR-26, E-SR-88 bis -93).
 *
 * EIN GRIFF, `[data-pow]` am Formular von `registrieren.php`. Es trägt die
 * Bitzahl (`data-pow-bits`) und die Adresse des Workers (`data-pow-worker`,
 * mit Erkennungswert aus `asset()`); die Aufgabe steht im versteckten Feld
 * `pow_aufgabe`, die Lösung kommt in `pow_loesung`.
 *
 * DIE RECHNUNG BEGINNT BEIM LADEN, nicht beim Absenden: Sie läuft, während
 * die Person Adresse und Namen tippt, und ist in aller Regel fertig, bevor
 * jemand auf „Konto anlegen" drückt. Nur wer schneller ist als der Worker,
 * sieht den Knopf kurz gesperrt mit „Sicherheitsprüfung läuft …" — dann
 * geht das Formular von selbst ab, sobald die Lösung da ist.
 *
 * NIE STILL WARTEN: Kann der Browser nicht rechnen (kein Worker, kein
 * WebCrypto, Worker gesperrt), sagt die Zustandszeile es, und das Formular
 * geht nicht ab. Ohne Lösung bekäme es die Danke-Karte und keine Mail
 * (F-SR-92) — das wäre für einen Menschen die schlechteste Antwort.
 *
 * DER KNOPF STEHT FREI IM MARKUP und wird nur hier gesperrt (Muster
 * `zweitfaktor.js`). Ohne Skript bleibt der Satz unter dem Knopf stehen, den
 * der Server hineinschreibt; mit Skript nimmt diese Datei ihn als Erstes weg.
 */
(function () {
  'use strict';
  const form = document.querySelector('[data-pow]');
  if (!form) { return; }
  const feld = form.querySelector('input[name="pow_loesung"]');
  const aufgabe = form.querySelector('input[name="pow_aufgabe"]');
  const knopf = form.querySelector('[data-pow-knopf]');
  const zustand = form.querySelector('[data-pow-zustand]');
  if (!feld || !aufgabe || !zustand) { return; }

  let fertig = false;
  let wartet = false;
  let kaputt = false;

  function sagen(text) { zustand.textContent = text; }

  function scheitern() {
    kaputt = true;
    if (knopf) { knopf.disabled = false; }
    zustand.innerHTML = EdHtml.meldung('fehler',
      'Die Sicherheitsprüfung lässt sich in diesem Browser nicht rechnen. '
      + 'Ein aktueller Browser hilft — oder die BetreiberIn legt dir ein Konto an.');
  }

  sagen('');

  let worker = null;
  try {
    worker = new Worker(form.dataset.powWorker);
  } catch (e) {
    worker = null;
  }
  if (!worker) { scheitern(); return; }

  worker.onmessage = ev => {
    const d = ev.data || {};
    if (d.fehler || typeof d.loesung !== 'string') { scheitern(); return; }
    feld.value = d.loesung;
    fertig = true;
    worker.terminate();
    if (wartet) {
      sagen('');
      form.submit();
    }
  };
  worker.onerror = () => { scheitern(); };
  worker.postMessage({ aufgabe: aufgabe.value, bits: Number(form.dataset.powBits) });

  form.addEventListener('submit', ev => {
    if (fertig) { return; }
    ev.preventDefault();
    if (kaputt) { return; }
    wartet = true;
    if (knopf) { knopf.disabled = true; }
    sagen('Sicherheitsprüfung läuft …');
  });
})();
