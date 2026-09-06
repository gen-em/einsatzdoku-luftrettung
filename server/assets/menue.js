/* Gen-EM NAdoku — die drei Blöcke der Einstellungsleiste (E-S8-07).
 * ===========================================================================
 *
 * WOFUER. Fuer eine BetreiberIn stehen siebzehn Menuepunkte untereinander.
 * Gemessen am 05.09.2026 bei 1280 x 900: Die Liste ist 896 px hoch, die
 * Leiste bietet 783 px — 14 von 17 Eintraegen waren ohne Rollen erreichbar,
 * bei 720 px Fensterhoehe noch 10 von 17. Wer „Backup-Ziele" sucht, sieht
 * nicht, dass es den Eintrag gibt. Die Bloecke klappen deshalb.
 *
 * DIE VORGABE MACHT PHP, NICHT DIESES SKRIPT: „Einstellungen" ist offen,
 * dazu der Block der aktiven Seite; die uebrigen sind zu — in jeder Breite.
 * Der Serverzustand ist damit schon der Zielzustand, und es blitzt beim
 * Seitenaufruf nichts anderes auf. Warum die Vorgabe nicht mehr nach der
 * Breite unterscheidet, steht in `ui.php` an der erzeugenden Stelle.
 *
 * DIESES SKRIPT MACHT GENAU ZWEIERLEI:
 *
 *   1  Es legt ueber die Vorgabe, was in dieser Sitzung von Hand geaendert
 *      wurde.
 *   2  Es merkt sich jede Aenderung.
 *
 * WAS DIE NUTZERIN AENDERT, GILT FUER DIE SITZUNG. Der Zustand liegt im
 * sessionStorage, je Block ein Schluessel. `sessionStorage` und nicht
 * `localStorage`: Ein zugeklappter Block ist eine Entscheidung fuer diesen
 * Arbeitsgang, keine Einstellung — wer morgen wiederkommt, soll das Menue
 * wieder in der Vorgabe sehen. So verlangt es die Abnahme P-28: Der Zustand
 * ueberlebt den Seitenwechsel, nicht die Sitzung.
 *
 * DIE VORGABE HAT VORRANG BEIM WECHSEL DER AKTIVEN SEITE — genauer: Der
 * gemerkte Zustand gilt nur fuer Bloecke, die nicht gerade die aktive Seite
 * tragen. Sonst waere ein Block, den jemand einmal zugeklappt hat,
 * ausgerechnet dann zu, wenn er die Seite enthaelt, auf der man steht; der
 * aktive Eintrag stuende unsichtbar darin.
 *
 * OHNE sessionStorage (privates Fenster mit gesperrtem Speicher) bleibt es
 * bei der Vorgabe aus PHP — jeder Zugriff steht in einem try/catch. Ein
 * Menue, das nicht mehr aufklappt, weil ein Speicher fehlt, waere der
 * schlechtere Tausch.
 */
(function () {
  'use strict';

  var liste = document.querySelector('[data-menue]');
  if (!liste) { return; }

  var VORSATZ = 'nadoku.menue.';
  var bloecke = liste.querySelectorAll('.leiste-gruppe');

  function lies(schluessel) {
    try { return sessionStorage.getItem(VORSATZ + schluessel); }
    catch (e) { return null; }
  }

  function schreib(schluessel, offen) {
    try { sessionStorage.setItem(VORSATZ + schluessel, offen ? '1' : '0'); }
    catch (e) { /* gesperrter Speicher: die Vorgabe aus PHP traegt weiter */ }
  }

  Array.prototype.forEach.call(bloecke, function (block) {
    /* Der Block der aktiven Seite bleibt, wie PHP ihn gesetzt hat — offen.
     * `.aktiv` steht am Eintrag, nicht am Block; hier wird danach gesucht. */
    var traegtAktiv = !!block.querySelector('.eintrag.aktiv');
    var gemerkt = lies(block.dataset.gruppe);
    if (gemerkt !== null && !traegtAktiv) { block.open = gemerkt === '1'; }

    block.addEventListener('toggle', function () {
      schreib(block.dataset.gruppe, block.open);
    });
  });
})();


/* ===========================================================================
 * UNTERPUNKTE: DIE KARTEN DER OFFENEN SEITE  (S8/AP5, E-S8-15)
 *
 * Unter dem aktiven Menuepunkt stehen die Kartentitel der Seite als
 * Sprungmarken, und waehrend man liest, ist der Titel der obersten sichtbaren
 * Karte fett. Auf den langen Seiten des Bereichs — Status hat vier Karten,
 * Servereinstellungen zwei mit viel Inhalt, Komplett-Backup sechs — ersetzt
 * das die Frage „wo im Text bin ich gerade".
 *
 * SIE ENTSTEHEN AUS DEM DOKUMENT, NICHT AUS PHP. Naeher laege es, die Seite
 * ihre Karten anmelden zu lassen. Nur wird die Leiste VOR dem Inhalt
 * gezeichnet: Die Seite muesste ihre Kartentitel vorab an `ui_geruest_start()`
 * geben und ein zweites Mal an `ui_karte_start()` — zwei Listen derselben
 * Sache, und die eine laeuft der anderen davon, sobald jemand eine Karte
 * umbenennt. Hier liest das Skript die Karten, die tatsaechlich dastehen.
 * Der Preis ist ehrlich und klein: ohne JavaScript keine Sprungmarken. Die
 * Seite selbst bleibt vollstaendig bedienbar, und die Markierung braucht
 * ohnehin ein Skript.
 *
 * NUR KARTEN MIT `id`. Die id ist der Anker; eine Karte ohne id waere ein
 * Sprungziel ohne Sprung. Sie tragen den Vorsatz `k-` (S8/AP2 bis AP5).
 *
 * IN DER SCHUBLADE STEHEN SIE, ABER OHNE MARKIERUNG (Konzept AP5 (4)). Unter
 * 1024 px ist die Leiste ein Ueberbau, den man oeffnet, um woanders
 * hinzugehen — der Sprung zu einer Karte ist dort besonders nuetzlich, weil
 * die Seiten am Handy lang sind. Eine Markierung dagegen saehe niemand: Die
 * Schublade liegt VOR dem Inhalt, und wer sie oeffnet, sieht die Karten
 * nicht. Der Beobachter laeuft dort deshalb nicht.
 * ======================================================================== */
(function () {
  'use strict';

  var BREIT = 1024;
  var breit = window.matchMedia('(min-width:' + BREIT + 'px)').matches;

  var aktiv = document.querySelector('.leiste-liste a.eintrag.aktiv');
  var inhalt = document.getElementById('inhalt');
  if (!aktiv || !inhalt) { return; }

  var karten = [];
  Array.prototype.forEach.call(inhalt.querySelectorAll('.karte[id]'), function (k) {
    var titel = k.querySelector('.karte-titel');
    if (titel) { karten.push({ el: k, titel: titel.textContent.trim() }); }
  });
  /* EINE EINZIGE KARTE IST KEIN INHALTSVERZEICHNIS. Sie zu verlinken hiesse,
   * auf die Seite zu verweisen, auf der man steht. */
  if (karten.length < 2) { return; }

  var liste = document.createElement('div');
  liste.className = 'eintrag-unterliste';
  var marken = karten.map(function (k) {
    var a = document.createElement('a');
    a.className = 'eintrag-unter';
    a.href = '#' + k.el.id;
    a.textContent = k.titel;
    a.title = k.titel;
    /* IN DER SCHUBLADE MUSS DER SPRUNG SIE SCHLIESSEN. Sie liegt vor dem
     * Inhalt; ein Anker springt zwar, aber man sieht das Ziel nicht.
     * `schublade.js` schliesst von selbst nur bei einem `[data-schublade]` —
     * und das nimmt dem Klick seine Wirkung (`preventDefault`). Deshalb hier
     * ein eigener Klick auf den Schliessknopf, und zwar erst nach dem
     * Sprung: `setTimeout(0)` laesst den Browser den Anker zuerst
     * abarbeiten. */
    if (!breit) {
      a.addEventListener('click', function () {
        var knopf = document.querySelector('[data-schublade="zu"]');
        if (knopf) { setTimeout(function () { knopf.click(); }, 0); }
      });
    }
    liste.appendChild(a);
    return a;
  });
  aktiv.parentNode.insertBefore(liste, aktiv.nextSibling);

  /* Die Markierung nur am Schreibtisch — die Begruendung steht oben. */
  if (!breit) { return; }

  /* ---- Welche Karte ist gerade oben? ------------------------------------
   *
   * JE SPALTE DIE OBERSTE SICHTBARE (Konzept AP5 (4)). Die langen Seiten
   * stehen ab 1200 px zweispaltig; dann sind zwei Karten zugleich „oben",
   * eine links und eine rechts, und nur eine davon fett zu setzen waere eine
   * Behauptung.
   *
   * ZWEI FALLEN, BEIDE GEMESSEN UND BEIDE HIER BEHOBEN:
   *
   * 1  „Sichtbar" reicht nicht. Eine hohe Karte haengt mit zwei Pixeln
   *    Unterkante noch ins Bild und ist mit ihrer Oberkante bei -431 px
   *    weiterhin die oberste — sie blieb fett, waehrend man laengst die
   *    naechste las. Eine Karte zaehlt deshalb erst, wenn unter der
   *    Kopfleiste noch ein Saum von ihr steht. Der Beobachter bekommt
   *    DENSELBEN Saum als `rootMargin`, damit er genau dann meldet, wenn
   *    sich die Antwort aendert.
   *
   * 2  Eine Karte ausserhalb der Spalten ist keine dritte Spalte. Auf der
   *    Statusseite steht „Was hier gilt" unter beiden Spalten; als eigener
   *    Topf war sie immer die oberste ihres Topfes und damit dauerhaft fett —
   *    drei Markierungen auf einmal. Hat die Seite Spalten, markieren nur
   *    Karten in einer Spalte; hat sie keine, ist die Seite der eine Topf.
   *
   * DER TOPF WIRD AN DER LAGE ERKANNT, nicht an der Fensterbreite. Das
   * Zweispalten-Raster greift erst ab 1200 px; zwischen 1024 und 1200 stehen
   * dieselben `.form-spalte`-Kaesten UNTEREINANDER, und zwei Markierungen
   * waeren dort zwei in derselben sichtbaren Spalte. Der Schluessel ist
   * deshalb der linke Rand des Kastens: gleiche Kante, gleicher Topf. Das
   * kommt ohne eine zweite Stelle aus, an der die Schwelle 1200 stuende.
   */
  var kopf = parseInt(
      getComputedStyle(document.documentElement).getPropertyValue('--kopf'), 10) || 56;
  var SAUM = kopf + 24;
  var hatSpalten = !!inhalt.querySelector('.form-spalte');

  function topf(el) {
    if (!hatSpalten) { return 0; }
    var s = el.closest('.form-spalte');
    return s ? Math.round(s.getBoundingClientRect().left) : null;  // null = zaehlt nicht mit
  }

  function markieren() {
    var oben = new Map();
    karten.forEach(function (k, i) {
      var t = topf(k.el);
      if (t === null) { return; }
      var r = k.el.getBoundingClientRect();
      if (r.bottom <= SAUM || r.top >= window.innerHeight) { return; }
      var b = oben.get(t);
      if (!b || r.top < b.y) { oben.set(t, { i: i, y: r.top }); }
    });
    var treffer = new Set();
    oben.forEach(function (b) { treffer.add(b.i); });
    marken.forEach(function (a, i) { a.classList.toggle('hier', treffer.has(i)); });
  }

  var beobachter = new IntersectionObserver(markieren, {
    rootMargin: '-' + SAUM + 'px 0px 0px 0px',
    threshold: 0,
  });
  karten.forEach(function (k) { beobachter.observe(k.el); });
})();
