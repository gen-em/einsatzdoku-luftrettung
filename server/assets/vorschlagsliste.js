/* Vorschlagsliste — EIN Baustein fuer jede Trefferliste unter einem Feld
 * (S9/AP1, E-S9-07).
 * ===========================================================================
 *
 * WARUM ES DIESE DATEI GIBT. Dieselbe Sache stand bis Web 15.5.2 in drei
 * Fassungen nebeneinander, und eine vierte kam vom Browser:
 *
 *   1  die Photon-Liste des Ortsfelds (`.loc-suggest` in assets/ortsfeld.js) —
 *      uebernimmt auf `mousedown`, kennt keine Tastatur, keine Gruppen;
 *   2  die Liste der weiteren Rettungsmittel (`.rmlist` in einsatz_form.php) —
 *      uebernimmt auf `click`, kennt Enter und Escape, aber keine Pfeiltasten;
 *   3  die native `<datalist>` an Transportziel und Besatzungsfeldern —
 *      gezeichnet vom Browser UEBER dem Feld, mobil oft gar nicht.
 *
 * Aus dieser Doppelung sind zwei gemeldete Fehler geworden:
 *
 *   PS-2 (Backlog 102). Ein Mausklick ist `mousedown` -> `blur` -> `mouseup`
 *   -> `click`. Fassung 2 versteckte die Liste 150 ms nach `blur`; wer die
 *   Maus laenger haelt — am Schreibtisch keine Seltenheit —, findet den Knopf
 *   beim `mouseup` schon `hidden`, und der Browser feuert kein `click`. Die
 *   Liste schliesst, uebernommen wird nichts. Fassung 1 macht es richtig
 *   (`mousedown` mit `preventDefault`, vor `blur`) — nur eben an einer
 *   anderen Stelle.
 *
 *   PS-6 (Backlog 106) und Nr. 68. Das Transportziel trug Fassung 1 UND
 *   Fassung 3 zugleich: Beim Tippen oeffneten zwei Listen uebereinander, und
 *   die native verdeckte die eigene. An den Besatzungsfeldern stand allein
 *   die native — auf dem Handy also nichts.
 *
 * Ein Baustein behebt beides auf einmal, und zwar an allen Feldern zugleich.
 *
 * WAS ER KANN, UND WARUM GENAU DAS:
 *
 *   GRUPPEN mit Zeile. Das Transportziel zeigt oben hoechstens zwei
 *   Stammdatentreffer („Zielkliniken"), darunter die Adressvorschlaege
 *   („Adressen"). Zwei Herkuenfte in einer Liste ohne Trennung waeren eine
 *   Liste, in der niemand weiss, was ein Klick tut: Ein Stammdatentreffer
 *   setzt Name UND Koordinate, ein Adresstreffer nur die Koordinate. Eine
 *   einzelne Gruppe traegt KEINE Gruppenzeile — eine Gruppe ist keine Gruppe.
 *
 *   SYMBOL UND NEBENZEILE sagen, woher der Eintrag kommt (Klinik gegen Pin,
 *   „Stammdaten · mit Koordinate" gegen die Postleitzahl). Das ist dieselbe
 *   Auskunft wie die Gruppenzeile, nur je Zeile — und sie bleibt sichtbar,
 *   wenn die Gruppenzeile nach oben aus dem Bild gescrollt ist.
 *
 *   TASTATUR. Pfeiltasten wandern, Enter uebernimmt, Escape schliesst. Die
 *   `<datalist>` konnte das; ohne diesen Baustein waere ihr Ausbau ein
 *   Rueckschritt fuer alle, die nicht zeigen.
 *
 *   UEBERNAHME AUF `mousedown`, nie auf `click` — siehe PS-2 oben. Das ist
 *   die eine Regel, die dieser Baustein nicht zur Wahl stellt.
 *
 * WAS ER NICHT KANN UND NICHT KENNEN MUSS: woher die Eintraege stammen. Er
 * bekommt fertige Gruppen und meldet die Wahl zurueck; ob dahinter eine
 * Adressabfrage, eine Stammdatenliste oder eine Vorbelegung steht, ist Sache
 * des Aufrufers. Deshalb passt derselbe Baustein an das Ortsfeld, an die
 * weiteren Rettungsmittel und an jedes Besatzungsfeld.
 *
 * ABHAENGIGKEITEN: assets/html.js (EdHtml.escape) fuer die Maskierung und
 * assets/symbol.js (edSymbol) fuer die Zeichen. Beide sind Bausteine des
 * Projekts; eigene Kopien davon waeren genau die Doppelung, die diese Datei
 * abschafft. symbol.js laedt am Seitenende (ui_geruest_ende) und ist damit
 * erst nach dem Aufbau da — gezeichnet wird aber erst beim Tippen, und dann
 * steht es. Fehlt es doch, bleibt die Zeile ohne Zeichen lesbar.
 */
(function (global) {
    'use strict';

    /* Der Aufschub nach `blur` gibt einem `mousedown` Zeit, noch
     * durchzukommen, und er darf nicht zuschlagen, wenn der Fokus
     * zurueckkehrt (der Lupen-Knopf des Ortsfelds nimmt ihn und gibt ihn
     * zurueck). Dieselbe Zahl und dieselbe Wache wie bisher in ortsfeld.js —
     * dort steht die Begruendung ausfuehrlich (F-P3-AJ). */
    var BLUR_MS = 150;

    function maskiere(t) {
        /* EdHtml.escape ist der Baustein dafuer (assets/html.js, B7). Fehlt
         * er, ist das ein Einbindungsfehler und kein Fall fuer eine stille
         * Ersatzfassung — die zweite Fassung war der Anfang der Doppelung,
         * die dieser Baustein beendet. */
        return EdHtml.escape(t);
    }

    /**
     * Getippten Teil fett hervorheben — auf dem MASKIERTEN Text, damit eine
     * Entitaet (`&amp;`) nicht mitten entzweigeschnitten wird.
     *
     * Die Hervorhebung ist `<b>`, nicht `<mark class="treffer">`: Der orange
     * hinterlegte Treffer der Suche (E-P3-36) traegt dort eine Aussage — „hier
     * steht dein Wort in einem langen Text". In einer Vorschlagsliste steht das
     * Wort immer am Anfang, und die Flaeche kaeme unter die Zeilenmarkierung
     * (Rauch mit orangem Strich) zu liegen. So auch im freigegebenen Mockup
     * M-S9-03.
     */
    function hervor(text, teil) {
        var m = maskiere(text);
        var q = String(teil == null ? '' : teil).trim();
        if (q === '') { return m; }
        var muster = maskiere(q).replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
        try {
            return m.replace(new RegExp('(' + muster + ')', 'i'), '<b>$1</b>');
        } catch (e) {
            return m;                      // unbrauchbares Muster: unveraendert
        }
    }

    function zeichen(name, klasse) {
        if (typeof edSymbol !== 'function' || !name) { return ''; }
        return edSymbol(name, klasse || '');
    }

    /**
     * Eine Verwendung aufbauen.
     *
     * @param {object} opt
     *   feld       Pflicht. Das Eingabefeld, an dem die Liste haengt.
     *   behaelter  Pflicht. Das Element, in das die Liste gehaengt wird; es
     *              traegt `position:relative` (`.loc-widget`, `.rmbox`).
     *   liste      Vorhandenes <ul>; fehlt es, wird eines erzeugt und an den
     *              Behaelter gehaengt.
     *   beiWahl    Rueckruf mit dem gewaehlten Eintrag.
     *   beiEingabe Rueckruf bei jedem Tastendruck im Feld (optional) — fuer
     *              Verwendungen, die ihre Treffer selbst nachladen.
     * @returns {object} Steuerobjekt
     */
    function init(opt) {
        opt = opt || {};
        var feld = opt.feld;
        var behaelter = opt.behaelter;
        if (!feld || !behaelter) { return null; }

        var liste = opt.liste || null;
        if (!liste) {
            liste = document.createElement('ul');
            behaelter.appendChild(liste);
        }
        liste.className = 'vorschlaege';
        liste.hidden = true;
        liste.setAttribute('role', 'listbox');

        var beiWahl = typeof opt.beiWahl === 'function' ? opt.beiWahl : null;

        /* Die Kennung braucht die Liste fuer `aria-controls` und
         * `aria-activedescendant`. Sie aus dem Feld abzuleiten haelt sie
         * eindeutig, solange die Feldkennung es ist — und die ist es, sonst
         * faende getElementById schon heute das falsche Feld. */
        if (!liste.id) {
            liste.id = (feld.id || feld.name || 'feld') + '-vorschlaege';
        }
        feld.setAttribute('autocomplete', 'off');
        feld.setAttribute('role', 'combobox');
        feld.setAttribute('aria-autocomplete', 'list');
        feld.setAttribute('aria-expanded', 'false');
        feld.setAttribute('aria-controls', liste.id);

        var eintraege = [];        // die Daten je sichtbarer Zeile
        var zeilen = [];           // die <li> dazu, gleiche Reihenfolge
        var aktiv = -1;

        function offen() { return !liste.hidden; }

        function markiere(i) {
            if (zeilen[aktiv]) {
                zeilen[aktiv].classList.remove('aktiv');
                zeilen[aktiv].setAttribute('aria-selected', 'false');
            }
            aktiv = (i >= 0 && i < zeilen.length) ? i : -1;
            if (zeilen[aktiv]) {
                zeilen[aktiv].classList.add('aktiv');
                zeilen[aktiv].setAttribute('aria-selected', 'true');
                feld.setAttribute('aria-activedescendant', zeilen[aktiv].id);
                if (zeilen[aktiv].scrollIntoView) {
                    zeilen[aktiv].scrollIntoView({ block: 'nearest' });
                }
            } else {
                feld.removeAttribute('aria-activedescendant');
            }
        }

        function verstecke() {
            liste.innerHTML = '';
            liste.hidden = true;
            eintraege = [];
            zeilen = [];
            aktiv = -1;
            feld.setAttribute('aria-expanded', 'false');
            feld.removeAttribute('aria-activedescendant');
        }

        function waehle(i) {
            var e = eintraege[i];
            if (!e) { return; }
            verstecke();
            if (beiWahl) { beiWahl(e); }
        }

        /**
         * Gruppen zeichnen.
         *
         * @param {Array} gruppen [{titel, eintraege:[...]}] — ein Eintrag ist
         *   {haupt, neben, symbol, wert, art, neu}. `haupt` und `neben` sind
         *   ROHTEXT und werden hier maskiert; `treffer` ist der getippte Teil,
         *   der in `haupt` fett erscheint.
         */
        function zeige(gruppen, treffer) {
            liste.innerHTML = '';
            eintraege = [];
            zeilen = [];
            aktiv = -1;

            var gefuellt = (gruppen || []).filter(function (g) {
                return g && g.eintraege && g.eintraege.length;
            });

            /* OB EINE GRUPPENZEILE ERSCHEINT, ENTSCHEIDET DER AUFRUFER, nicht
             * dieser Baustein: Er setzt `titel` oder laesst ihn weg. Der Grund
             * ist, dass die Regel nicht mechanisch ist (M-S9-03): Das
             * Besatzungsfeld zeigt „Vorlagen des Standorts" auch als EINZIGE
             * Gruppe — dort sagt die Zeile, woher die Namen kommen und dass
             * Freitext daneben moeglich bleibt. Der Einsatzort zeigt allein
             * Adressen und traegt KEINE Zeile: Eine Ueberschrift ohne
             * Gegenstueck ist keine Gliederung. Beides hier zu raten hiesse,
             * die Entscheidung an der Stelle zu treffen, die den Zusammenhang
             * nicht kennt. */
            gefuellt.forEach(function (g) {
                if (g.titel) {
                    var kopf = document.createElement('li');
                    kopf.className = 'vorschlaege-gruppe';
                    kopf.setAttribute('role', 'presentation');
                    kopf.textContent = g.titel;
                    liste.appendChild(kopf);
                }
                g.eintraege.forEach(function (e) {
                    var i = eintraege.length;
                    var li = document.createElement('li');
                    /* Zwei Zeilen statt einer Zusammensetzung: Die
                     * Vollstaendigkeitspruefung liest eine Zuweisung an
                     * `className` und einen Aufruf von `classList.add` als
                     * sicheren Beleg, einen
                     * zusammengeklebten Namen dagegen nicht — und meldet die
                     * Regel dann als „im Markup nicht gefunden". Ein
                     * Pruefmittel, das an der Schreibweise scheitert, ist
                     * billiger zu bedienen als zu belehren. */
                    li.className = 'vorschlag';
                    if (e.neu) { li.classList.add('vorschlag-neu'); }
                    li.id = liste.id + '-' + i;
                    li.setAttribute('role', 'option');
                    li.setAttribute('aria-selected', 'false');
                    /* Die HERKUNFT als Datenattribut, nicht nur als Symbol:
                     * Der Aufrufer unterscheidet danach, was ein Klick tut
                     * (Stammdatentreffer setzt Name UND Koordinate), und die
                     * Klickprobe zaehlt danach ihre Sollzahlen. */
                    if (e.art) { li.setAttribute('data-art', e.art); }
                    li.innerHTML =
                        zeichen(e.symbol) +
                        '<span class="vorschlag-text">' +
                        '<span class="vorschlag-haupt">' +
                        (e.neu ? maskiere(e.haupt) : hervor(e.haupt, treffer)) +
                        '</span>' +
                        (e.neben
                            ? '<span class="vorschlag-neben">' + maskiere(e.neben) + '</span>'
                            : '') +
                        '</span>';
                    /* `mousedown` mit `preventDefault`: VOR dem `blur` des
                     * Feldes und ohne ihn auszuloesen. Ein `click` kaeme erst
                     * nach `mouseup` — und damit womoeglich nie (PS-2). */
                    li.addEventListener('mousedown', function (ev) {
                        ev.preventDefault();
                        waehle(i);
                    });
                    li.addEventListener('mouseenter', function () { markiere(i); });
                    liste.appendChild(li);
                    eintraege.push(e);
                    zeilen.push(li);
                });
            });

            liste.hidden = eintraege.length === 0;
            feld.setAttribute('aria-expanded', liste.hidden ? 'false' : 'true');
        }

        feld.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape') {
                if (offen()) { ev.preventDefault(); verstecke(); }
                return;
            }
            if (!offen()) { return; }
            if (ev.key === 'ArrowDown') {
                ev.preventDefault();
                markiere(aktiv + 1 >= zeilen.length ? 0 : aktiv + 1);
            } else if (ev.key === 'ArrowUp') {
                ev.preventDefault();
                markiere(aktiv - 1 < 0 ? zeilen.length - 1 : aktiv - 1);
            } else if (ev.key === 'Enter') {
                /* Ohne Markierung uebernimmt Enter den ERSTEN Eintrag — so
                 * verhielt sich die Rettungsmittel-Liste seit Web 7.0.0, und
                 * wer eine Liste offen hat und Enter drueckt, meint sie. */
                ev.preventDefault();
                waehle(aktiv >= 0 ? aktiv : 0);
            } else if (ev.key === 'Tab') {
                verstecke();
            }
        });

        feld.addEventListener('blur', function () {
            setTimeout(function () {
                if (document.activeElement === feld) { return; }
                verstecke();
            }, BLUR_MS);
        });

        return {
            zeige: zeige,
            verstecke: verstecke,
            offen: offen,
            anzahl: function () { return eintraege.length; },
            element: function () { return liste; }
        };
    }

    global.EdVorschlaege = { init: init, hervor: hervor };
})(window);
