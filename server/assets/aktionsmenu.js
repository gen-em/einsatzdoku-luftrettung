/* Einsatzdoku — Verhalten des Aktionsmenüs oben rechts (A5.1).
 *
 * Das Menü selbst ist ein <details class="aktionsmenu"> mit einem <summary> als
 * Kopf und einer <div class="aktionsliste"> voller Verweise. <details> bringt
 * Öffnen und Schliessen samt vollstaendiger Tastaturbedienung von Haus aus mit;
 * was fehlt, ist zweierlei: das Schliessen ohne einen zweiten Klick auf den
 * Kopf (ein offenes Menue bleibt sonst ueber der Seite stehen und verdeckt die
 * Angaben darunter) und das Schliessen mit Escape.
 *
 * WARUM EINE EIGENE DATEI. Diese knapp zwanzig Zeilen standen in einem <script>
 * in einsatz.php. Die Flugtaguebersicht hat seit Web 5.10.0 dasselbe Menue —
 * und damit gaebe es zwei Fassungen desselben Verhaltens, die beim naechsten
 * Mal auseinanderlaufen. Genau die Sorte Doppelung, die das Projekt an anderer
 * Stelle bereits eingesammelt hat (Bausteine B7, B8).
 *
 * Die Datei bindet sich selbst: Wer sie einbindet, bekommt jedes Menue der
 * Seite verdrahtet, ohne einen Aufruf zu schreiben. Mehrere Menues auf einer
 * Seite sind vorgesehen, auch wenn es sie heute nicht gibt.
 *
 * Bewusst OHNE role="menu"/"menuitem": Diese Rollen versprechen Bedienung mit
 * den Pfeiltasten. Wer sie vergibt, ohne sie zu liefern, macht die Sache fuer
 * Vorleseprogramme schlechter als ohne — angekuendigt wird ein Menue, das sich
 * dann nicht wie eines bedienen laesst. Ein paar Verweise untereinander sind
 * hier die ehrlichere Beschreibung.
 */
'use strict';
(function () {
  const menus = () => document.querySelectorAll('details.aktionsmenu');

  /* Ein Klick daneben schliesst. Der Vergleich laeuft ueber contains(): Ein
     Klick INS Menue (auch auf einen seiner Verweise) darf es nicht schliessen,
     bevor der Verweis wirkt. */
  document.addEventListener('click', ev => {
    menus().forEach(menu => {
      if (menu.open && !menu.contains(ev.target)) { menu.open = false; }
    });
  });

  /* Escape schliesst und gibt den Fokus an den Kopf zurueck — sonst stuende er
     nach dem Schliessen in einem Bereich, den es nicht mehr gibt. */
  document.addEventListener('keydown', ev => {
    if (ev.key !== 'Escape') { return; }
    menus().forEach(menu => {
      if (!menu.open) { return; }
      menu.open = false;
      const kopf = menu.querySelector('summary');
      if (kopf) { kopf.focus(); }
    });
  });
})();
