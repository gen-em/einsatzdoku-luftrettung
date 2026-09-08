/* Kartenfilter — eine lange Liste in einer Karte im Browser durchsuchen.
 * ==========================================================================
 *
 * S9/AP5, Mockup M-S9-06. Ein Feld mit Lupe über der Liste; Tippen blendet
 * aus, was nicht passt. Ohne Anfrage, ohne Neuladen, ohne Adressänderung.
 *
 * WARUM IM BROWSER UND NICHT AUF DEM SERVER. Die Liste steht ohnehin schon
 * ganz im Dokument — sie wird beim Aufruf der Standortseite vollständig
 * gerendert. Eine Serverabfrage lüde dieselben Daten ein zweites Mal und
 * kostete bei jedem Tastendruck einen Umlauf; die Filterreihe der Suchseite
 * geht bewusst den anderen Weg, weil sie einen Bestand filtert, der NICHT
 * vollständig da ist (Tausende Einsätze, seitenweise geladen).
 *
 * WAS GEFILTERT WIRD: der sichtbare Text einer Zeile, klein geschrieben,
 * Umlaute normalisiert. Ein schlichtes Enthalten-Sein, keine UND/ODER/NICHT-
 * Syntax: `EdSuchtext` kann das (suchtext.js), aber es beantwortet eine
 * andere Frage. Wer in einer Liste von zwanzig Namen filtert, tippt drei
 * Buchstaben und sieht nach — er baut keine Abfrage.
 *
 * VIER DINGE, DIE EIN NAIVER FILTER FALSCH MACHT. Alle vier fallen beim
 * ersten Tastendruck auf, und drei davon stehen weder im Mockup noch im
 * Konzept:
 *
 *   1. DIE VERSTECKTEN FORMULARE. `sd_zeile()` legt die POST-Formulare
 *      (Löschen, Als Vorbelegung) NEBEN die Zeile, nicht hinein — sie tragen
 *      `.nur-vorlesen` und sind unsichtbar, aber sie sind Kinder desselben
 *      Behälters. Ein Filter, der über alle Kinder läuft, versteckt sie mit;
 *      dann zeigt das Aktionsmenü einer sichtbaren Zeile über `form=` auf ein
 *      Formular, das `display:none` trägt, und der Knopf tut nichts.
 *      Gefiltert wird deshalb ausschließlich über `.zeile`.
 *
 *   2. ZWISCHENTITEL. Die Besatzungskarte gliedert nach Rollen
 *      (`h3.sd-rolle`). Bleibt „Pilot 1" stehen, während darunter keine Zeile
 *      mehr steht, sieht die Karte aus, als wäre der Bestand leer statt
 *      gefiltert. Ein Zwischentitel verschwindet mit seiner Gruppe — und mit
 *      ihm alles, was zu ihr gehört.
 *
 *   3. DIE ANLEGEN-FORMULARE. In der Besatzungskarte steht je ROLLE eines
 *      (`sd_form()` in der Rollenschleife, einstellungen.php) — fünf Stück an
 *      einem Standort mit fünf Rollen. Blendet man nur Zeilen aus, stehen
 *      unter einem einzigen Treffer vier verwaiste Formulare. Solange
 *      gefiltert wird, sind sie deshalb alle verborgen: Ein Filter ist ein
 *      Lesezustand, kein Eingabezustand. Der Leerzustand sagt ausdrücklich,
 *      wie man wieder zum Anlegen kommt.
 *
 *   4. DIE HINWEISE IN EINER GRUPPE. „Noch keine Einträge." steht je Rolle
 *      und wäre beim Filtern gelogen. Er hängt an der Gruppe und geht mit ihr.
 *      Der Hinweis am KOPF der Karte bleibt: Er beschreibt die Liste, nicht
 *      ihren Inhalt.
 *
 * DER LEERZUSTAND steht als verborgener Absatz im Markup (`ui_kartenfilter()`)
 * und wird hier nur ein- und ausgeblendet. Ein Text, den das Skript
 * zusammensetzt, liefe an der Wortliste vorbei.
 *
 * KEIN ZUSTAND IN DER ADRESSE. Der Filter ist eine Lesehilfe, kein
 * Standpunkt: Er soll nach dem Neuladen weg sein, und er soll keine Adresse
 * erzeugen, die jemand teilt und die beim Empfänger eine halbe Liste zeigt.
 * Deshalb auch kein `history.replaceState`.
 */
(function () {
  'use strict';

  /** Kleinschreibung plus Umlaute — „Müller" findet sich auch als „muller". */
  function flach(s) {
    return (s || '').toLowerCase()
      .replace(/ä/g, 'a').replace(/ö/g, 'o').replace(/ü/g, 'u').replace(/ß/g, 'ss')
      .replace(/\s+/g, ' ').trim();
  }

  Array.prototype.forEach.call(
    document.querySelectorAll('.kartenfilter[data-kartenfilter]'),
    function (feldbox) {
      var zielId = feldbox.getAttribute('data-kartenfilter');
      var liste  = document.getElementById(zielId);
      var eingabe = feldbox.querySelector('input');
      var kreuz   = feldbox.querySelector('.kartenfilter-x');
      var leer = document.querySelector('.kartenfilter-leer[data-leer-fuer="'
                                        + zielId + '"]');
      if (!liste || !eingabe) { return; }

      var kinder = Array.prototype.slice.call(liste.children);

      /* Der Text einer Zeile wird EINMAL gelesen und gemerkt. Ihn bei jedem
         Tastendruck neu aus dem DOM zu holen, kostet bei vierzig Zeilen
         vierzig Layoutabfragen je Buchstabe. */
      var zeilen = kinder.filter(function (e) { return e.classList.contains('zeile'); })
        .map(function (z) { return { el: z, text: flach(z.textContent) }; });
      if (!zeilen.length) { return; }

      /* Gruppen: ein Zwischentitel und alles bis zum nächsten. Was keiner
         Gruppe angehört (die Zielklinikenliste hat gar keine), steht in
         keiner Liste und wird nur über `zeilen` behandelt. */
      var gruppen = [];
      kinder.forEach(function (e) {
        if (e.classList.contains('sd-rolle')) {
          gruppen.push({ titel: e, teile: [], zeilen: [] });
        } else if (gruppen.length) {
          var g = gruppen[gruppen.length - 1];
          g.teile.push(e);
          if (e.classList.contains('zeile')) { g.zeilen.push(e); }
        }
      });

      /* Anlegen-Formulare — auch die in den Gruppen, deshalb über die ganze
         Sektion und nicht nur über die direkten Kinder. */
      var formulare = Array.prototype.slice.call(
        liste.querySelectorAll('.listen-form'));

      function anwenden() {
        var q = flach(eingabe.value);
        var filtert = q !== '';
        var sichtbar = 0;

        zeilen.forEach(function (z) {
          var passt = !filtert || z.text.indexOf(q) !== -1;
          z.el.hidden = !passt;
          if (passt) { sichtbar++; }
        });
        formulare.forEach(function (f) { f.hidden = filtert; });
        gruppen.forEach(function (g) {
          var weg = filtert && g.zeilen.every(function (z) { return z.hidden; });
          g.titel.hidden = weg;
          g.teile.forEach(function (e) {
            /* Zeilen behalten ihr eigenes Urteil, Formulare ihres; alles
               übrige (Hinweise) folgt der Gruppe. */
            if (e.classList.contains('zeile')
                || e.classList.contains('listen-form')) { return; }
            e.hidden = weg;
          });
        });
        if (leer) { leer.hidden = !(filtert && sichtbar === 0); }
        if (kreuz) { kreuz.hidden = !filtert; }
      }

      eingabe.addEventListener('input', anwenden);
      /* Escape leert das Feld — dieselbe Taste, die eine Vorschlagsliste
         schliesst. `keydown`, weil `keyup` bei gedrückt gehaltener Taste
         erst am Ende kommt. */
      eingabe.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && eingabe.value !== '') {
          e.preventDefault(); eingabe.value = ''; anwenden();
        }
      });
      if (kreuz) {
        kreuz.addEventListener('click', function () {
          eingabe.value = ''; anwenden(); eingabe.focus();
        });
      }
      anwenden();
    });
})();
