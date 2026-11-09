/* assets/darstellung.js — Mond und Sonne in der Kopfleiste.
 *
 * Konzept OF, OF-08 (E-OF-09, -43, -79). Der Knopf steht nur da, wenn das
 * Konto „Umschalter" oder „Folgt System" gewählt hat (`ui_kopf()`); dieses
 * Skript wird nur dann geladen.
 *
 * WAS ES TUT. Beim Laden: Name („Dunkel ein" / „Dunkel aus") und
 * `aria-pressed` nach dem, was die Seite gerade zeigt — ohne `data-theme`
 * entscheidet das Gerät, und das weiss nur der Browser. Beim Klick:
 * `data-theme` sofort umsetzen (die Seite wechselt ohne Neuladen) und die
 * Wahl an `api/darstellung.php` melden, das sie in der Sitzung ablegt; die
 * nächste Seite kommt dann schon im Markup richtig, ohne Aufblitzen.
 *
 * WELCHES ZEICHEN ZU SEHEN IST, entscheidet das Stylesheet, nicht dieses
 * Skript (`.kopf-darstellung`): So stimmt es auch vor dem ersten Klick.
 *
 * DIE MELDUNG UEBERDAUERT DEN SEITENWECHSEL (`ueberdauert`, seit Web
 * 24.9.2, Nr. 550). SCHEITERT SIE dennoch, bleibt die Seite umgeschaltet —
 * sie gilt dann nur bis zum nächsten Seitenwechsel. Eine Fehlermeldung für eine
 * Farbe wäre lauter als der Fehler.
 */
(function () {
  'use strict';
  const knopf = document.querySelector('[data-darstellung]');
  if (!knopf) { return; }
  const wurzel = document.documentElement;
  const geraet = window.matchMedia('(prefers-color-scheme: dark)');

  function istDunkel() {
    const t = wurzel.getAttribute('data-theme');
    if (t === 'dark') { return true; }
    if (t === 'light') { return false; }
    return geraet.matches;
  }

  function beschriften() {
    const d = istDunkel();
    knopf.setAttribute('aria-pressed', d ? 'true' : 'false');
    knopf.setAttribute('aria-label', d ? 'Dunkel aus' : 'Dunkel ein');
  }

  /* Die Meldung, solange sie unterwegs ist — sonst null. */
  let unterwegs = null;

  knopf.addEventListener('click', function () {
    const dunkel = !istDunkel();
    wurzel.setAttribute('data-theme', dunkel ? 'dark' : 'light');
    beschriften();
    if (window.EdApi) {
      /* `ueberdauert`: Wer gleich nach dem Klick einen Verweis nimmt, haette
         sonst die Wahl verloren — `EdApi` bricht beim Seitenwechsel ab, und
         die naechste Seite kam in der alten Farbe (Nr. 550). */
      const meldung = EdApi.postForm('api/darstellung.php', { wahl: dunkel ? 'dunkel' : 'hell' },
                                     { ueberdauert: true })
        .catch(function () { /* siehe Kopf: die Seite bleibt umgeschaltet */ });
      unterwegs = meldung;
      meldung.then(function () { if (unterwegs === meldung) { unterwegs = null; } });
    }
  });

  /* EIN VERWEIS WARTET AUF DIE MELDUNG (Nr. 550). `ueberdauert` rettet die
     Wahl, aber die naechste Seite kann den Server VOR ihr erreichen und kaeme
     dann noch in der alten Farbe. Solange die Meldung unterwegs ist, haelt
     ein Klick auf einen Verweis dieser Anlage an, bis sie da ist —
     hoechstens anderthalb Sekunden. Neuer Tab, Anker und fremde Seiten
     bleiben unberuehrt. */
  document.addEventListener('click', function (e) {
    if (!unterwegs || e.defaultPrevented || e.button !== 0
        || e.ctrlKey || e.metaKey || e.shiftKey || e.altKey) { return; }
    const a = e.target.closest ? e.target.closest('a[href]') : null;
    if (!a || a.target || a.hasAttribute('download')) { return; }
    const ziel = new URL(a.href, location.href);
    if (ziel.origin !== location.origin
        || (ziel.pathname === location.pathname && ziel.hash)) { return; }
    e.preventDefault();
    Promise.race([unterwegs, new Promise(function (r) { setTimeout(r, 1500); })])
      .then(function () { location.href = ziel.href; });
  });

  /* Ohne Klick folgt die Seite dem Gerät — auch wenn es umschaltet, während
     sie offen ist. Dann muss der Name mitgehen. */
  if (geraet.addEventListener) { geraet.addEventListener('change', beschriften); }
  beschriften();
})();
