/* Vorschau eines Rechtstexts beim Tippen — Seite „Rechtstexte".
 * ===========================================================================
 * (P5c/AP9, E-P5c-28, Backlog Nr. 121, Mockup M-P5c-01d Variante 2)
 *
 * WAS ES TUT. 0,4 s nach dem letzten Tastendruck (oder einer Änderung des
 * Standdatums) schickt es Text und Stand an `api/rechtstext_vorschau.php`
 * und setzt das zurückgegebene Markup in die Karte „Vorschau". Gerendert
 * wird AUF DEM SERVER, mit `rt_html()` — derselbe Renderer wie die
 * öffentliche Seite; einen zweiten im Browser gibt es nicht.
 *
 * DIE PLAKETTE SAGT, WAS DASTEHT (Zustände aus dem Mockup, Teil 3):
 *   „gespeicherter Stand"   blau    — nichts getippt, wie ohne Skript
 *   „wird aktualisiert …"   neutral — getippt, Antwort steht aus
 *   „ungespeichert"         orange  — zeigt, was getippt ist
 * Scheitert ein Abruf, bleibt die letzte Vorschau stehen und darüber steht
 * eine Meldung; der Text ist davon nicht betroffen.
 *
 * LAUFENDE ABRUFE WERDEN VERWORFEN: Jede Anfrage trägt eine laufende Nummer,
 * und nur die Antwort auf die JÜNGSTE wird gezeigt. Kommt eine ältere
 * später an, hätte sie sonst einen neueren Stand überschrieben.
 *
 * DIE VORSCHAU FOLGT DEM FELD: Rollt das Textfeld, rollt der Vorschaukasten
 * an dieselbe Stelle in Prozent — ohne Quellzuordnung, das genügt für
 * „ungefähr dort".
 *
 * OHNE SKRIPT bleibt die Vorschau des gespeicherten Stands; die Seite
 * funktioniert vollständig ohne diese Datei. `EdApi` steht im <head>
 * (`assets/api.js`) und wird erst beim Abruf gelesen.
 */
(function () {
  'use strict';

  var feld   = document.querySelector('[data-rt-text]');
  var stand  = document.querySelector('[data-rt-stand]');
  var kasten = document.querySelector('[data-rt-vorschau]');
  var zustand = document.querySelector('[data-rt-zustand]');
  var fehler = document.querySelector('[data-rt-fehler]');
  if (!feld || !kasten || !zustand) { return; }

  var ziel = kasten.querySelector('.text') || kasten;
  var uhr = null;
  var nummer = 0;

  /* Die Plakette neu setzen. `ui_plakette()` baut `.plakette.plakette-<ton>`;
   * hier dieselbe Klasse, damit sie aussieht wie die vom Server. */
  function plakette(text, ton) {
    zustand.textContent = '';
    var p = document.createElement('span');
    p.className = 'plakette plakette-' + ton;
    p.textContent = text;
    zustand.appendChild(p);
  }

  function meldung(text) {
    if (!fehler) { return; }
    if (!text) { fehler.hidden = true; fehler.textContent = ''; return; }
    /* `EdHtml.meldung()` maskiert Text und Auftakt selbst und liefert
     * dasselbe Markup wie `ui_meldung_markup()` (Register: „Meldung im
     * Browser"). */
    fehler.innerHTML = EdHtml.meldung('warn', text,
      { auftakt: 'Vorschau gerade nicht erreichbar.' });
    fehler.hidden = false;
  }

  function abrufen() {
    var meine = ++nummer;
    /* OHNE `vorgang`: Den Satzanfang trägt der Auftakt der Meldung
     * („Vorschau gerade nicht erreichbar."); „Die Vorschau ist
     * fehlgeschlagen:" davor hieße dasselbe zweimal. */
    EdApi.postJson('api/rechtstext_vorschau.php',
                   { text: feld.value, stand: stand ? stand.value : '' })
      .then(function (a) {
        if (meine !== nummer) { return; }       // eine neuere Anfrage läuft
        if (!a.ok) {
          /* DIE LETZTE VORSCHAU BLEIBT STEHEN — sie ist nicht falsch, nur
           * nicht die neueste. Die Plakette sagt es. */
          meldung(a.meldung + ' Die letzte Vorschau bleibt stehen; der Text '
                  + 'ist davon nicht betroffen.');
          plakette('nicht aktuell', 'orange');
          return;
        }
        meldung('');
        /* Das Markup kommt aus `rt_html()` — derselben Funktion, deren
         * Ausgabe die öffentliche Seite ungeprüft einsetzt: Sie maskiert
         * jeden Text und lässt nur ihre eigenen Tags und geprüfte Verweise
         * durch. */
        ziel.innerHTML = a.daten.leer
          ? '<p class="feld-hinweis">Das Feld ist leer — die öffentliche Seite '
            + 'zeigt dann ihren Leertext.</p>'
          : a.daten.html;
        plakette('ungespeichert', 'orange');
        folgen();
      });
  }

  function geaendert() {
    plakette('wird aktualisiert …', 'neutral');
    if (uhr) { clearTimeout(uhr); }
    uhr = setTimeout(abrufen, 400);
  }

  /* Anteilig mitrollen: gleiche Stelle in Prozent. */
  function folgen() {
    var weg = feld.scrollHeight - feld.clientHeight;
    var anteil = weg > 0 ? feld.scrollTop / weg : 0;
    kasten.scrollTop = anteil * (kasten.scrollHeight - kasten.clientHeight);
  }

  feld.addEventListener('input', geaendert);
  if (stand) { stand.addEventListener('change', geaendert); }
  feld.addEventListener('scroll', folgen, { passive: true });
})();
