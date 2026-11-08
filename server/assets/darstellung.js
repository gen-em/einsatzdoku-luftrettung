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
 * SCHEITERT DIE MELDUNG, bleibt die Seite trotzdem umgeschaltet — sie gilt
 * dann nur bis zum nächsten Seitenwechsel. Eine Fehlermeldung für eine
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

  knopf.addEventListener('click', function () {
    const dunkel = !istDunkel();
    wurzel.setAttribute('data-theme', dunkel ? 'dark' : 'light');
    beschriften();
    if (window.EdApi) {
      EdApi.postForm('api/darstellung.php', { wahl: dunkel ? 'dunkel' : 'hell' })
        .catch(function () { /* siehe Kopf: die Seite bleibt umgeschaltet */ });
    }
  });

  /* Ohne Klick folgt die Seite dem Gerät — auch wenn es umschaltet, während
     sie offen ist. Dann muss der Name mitgehen. */
  if (geraet.addEventListener) { geraet.addEventListener('change', beschriften); }
  beschriften();
})();
