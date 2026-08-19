/* Freitextsuche mit booleschen Operatoren (Baustein B10, Web 7.0.0).
 *
 * WARUM ES DIESEN BAUSTEIN GIBT
 * Die Suche filtert vollstaendig im Browser — sie MUSS es, weil Name,
 * Diagnose, Einsatzort und Einsatznummer Ende-zu-Ende-verschluesselt sind und
 * der Server sie nicht lesen kann (siehe suche.php). Bis Web 6.3.0 war die
 * Auswertung eine Zeile: Jedes Wort musste irgendwo vorkommen, UND ueber die
 * Woerter, ODER ueber die Felder. Das traegt fuer den Regelfall und scheitert
 * an zwei Fragen, die im Betrieb immer wieder auftauchen:
 *
 *   „Alle Reanimationen ODER Polytraumata"   — zwei Begriffe, einer genuegt
 *   „Bergwacht, aber keine Winde"            — ein Begriff soll FEHLEN
 *
 * BEWUSST KLEIN GEHALTEN. Es gibt vier Dinge: UND, ODER, NICHT und Klammern,
 * dazu die Phrase in Anfuehrungszeichen. Keine Feldnamen („dx:Sturz"), keine
 * Platzhalter, keine Bereiche — dafuer gibt es die Filterspalte, und sie ist
 * dort besser aufgehoben, weil sie Auswahllisten aus dem Bestand anbieten kann.
 *
 * DIE EINFACHE EINGABE BLEIBT EINFACH. Wer wie bisher zwei Woerter tippt,
 * bekommt wie bisher die UND-Verknuepfung: Zwischen zwei Begriffen ohne
 * Operator steht ein unsichtbares UND. Niemand muss die Operatoren kennen.
 *
 * Schreibweisen (Gross-/Kleinschreibung egal):
 *   UND   `UND`  `AND`  `&`   oder einfach ein Leerzeichen
 *   ODER  `ODER` `OR`   `|`
 *   NICHT `NICHT` `NOT` `!`   oder ein `-` unmittelbar vor dem Begriff
 *   Phrase  "zwei woerter"    — genau diese Folge, Leerzeichen eingeschlossen
 *   Klammern ( … )
 *
 * ODER bindet SCHWAECHER als UND: `a b ODER c` heisst `(a UND b) ODER c`.
 * Das ist die Lesart, die man aus Suchmasken kennt; wer es anders braucht,
 * klammert.
 *
 * ROBUSTHEIT GEHT VOR STRENGE. Eine halbfertige Eingabe ist der Normalfall —
 * die Trefferliste wird bei JEDEM Tastendruck neu gerechnet, und `(sturz`
 * ist auf dem Weg zu `(sturz ODER fraktur)` unvermeidlich. Deshalb wird nichts
 * bemaengelt: Fehlende schliessende Klammern gelten als gesetzt, ein Operator
 * ohne rechte Seite wird uebergangen, und ein Ausdruck, der sich gar nicht
 * deuten laesst, faellt auf die alte UND-Regel zurueck. Eine Fehlermeldung
 * beim Tippen waere lauter als das Ergebnis.
 *
 * Aufruf:
 *   const p = EdSuchtext.pruefer('sturz ODER fraktur -kind');
 *   p(heuhaufen)   // -> true/false; heuhaufen ist BEREITS kleingeschrieben
 *   EdSuchtext.pruefer('')   // -> null (kein Filter)
 */
(function (global) {
    'use strict';

    var UND   = { 'und': 1, 'and': 1, '&': 1 };
    var ODER  = { 'oder': 1, 'or': 1, '|': 1 };
    var NICHT = { 'nicht': 1, 'not': 1, '!': 1 };

    /**
     * Eingabe in Tokens zerlegen.
     *
     * Ergebnis je Token: { art: 'wort'|'auf'|'zu'|'und'|'oder'|'nicht',
     *                      text: <nur bei 'wort'> }
     *
     * Ein `-` zaehlt nur als NICHT, wenn es unmittelbar vor einem Begriff steht
     * (`-kind`, `-"zwei woerter"`, `-(a b)`). Sonst gehoert es zum Wort: In
     * Diagnosen und Ortsnamen stehen Bindestriche, und wer „St.-Anna" sucht,
     * meint keinen Ausschluss.
     */
    function zerlege(q) {
        var t = [], i = 0, n = q.length;
        while (i < n) {
            var c = q[i];
            if (c === ' ' || c === '\t' || c === '\n') { i++; continue; }
            if (c === '(') { t.push({ art: 'auf' }); i++; continue; }
            if (c === ')') { t.push({ art: 'zu' });  i++; continue; }
            if (c === '"' || c === '„' || c === '“') {
                // Phrase. Schliesst mit dem naechsten Anfuehrungszeichen oder
                // am Ende der Eingabe — Letzteres ist der Zustand beim Tippen.
                var ende = i + 1;
                while (ende < n && q[ende] !== '"' && q[ende] !== '“'
                       && q[ende] !== '”') { ende++; }
                var phrase = q.slice(i + 1, ende).trim();
                if (phrase !== '') { t.push({ art: 'wort', text: phrase }); }
                i = ende + 1;
                continue;
            }
            if (c === '&' || c === '|' || c === '!') {
                t.push({ art: c === '&' ? 'und' : (c === '|' ? 'oder' : 'nicht') });
                i++; continue;
            }
            if (c === '-') {
                /* Ausschluss nur, wenn das Minus FREI STEHT und unmittelbar ein
                 * Begriff folgt: am Anfang, nach einem Leerzeichen oder nach
                 * einer Klammer. Massgeblich ist das Zeichen davor, nicht das
                 * vorherige Token — sonst waere in `sturz -kopf` das Minus Teil
                 * des Wortes, weil links davon ein Wort steht.
                 * Umgekehrt bleibt „St.-Anna" ein Wort: Dort steht links vom
                 * Minus ein Buchstabe. */
                var davor = i > 0 ? q[i - 1] : ' ';
                var folgt = q[i + 1];
                var freiStehend = (davor === ' ' || davor === '\t' || davor === '\n'
                                   || davor === '(');
                if (freiStehend && folgt !== undefined
                    && folgt !== ' ' && folgt !== '-') {
                    t.push({ art: 'nicht' }); i++; continue;
                }
            }
            // Gewoehnliches Wort: laeuft bis zum naechsten Trennzeichen.
            var j = i;
            while (j < n && ' \t\n()"&|!'.indexOf(q[j]) === -1) { j++; }
            var w = q.slice(i, j);
            var kl = w.toLowerCase();
            if (UND[kl])       { t.push({ art: 'und' }); }
            else if (ODER[kl]) { t.push({ art: 'oder' }); }
            else if (NICHT[kl]){ t.push({ art: 'nicht' }); }
            else if (w !== '') { t.push({ art: 'wort', text: w }); }
            i = j;
        }
        return t;
    }

    /* Rekursiver Abstieg ueber die Tokenliste. Die drei Funktionen bilden die
     * Rangfolge ab: ODER schwaecher als UND, UND schwaecher als NICHT. */
    function parse(tokens) {
        var pos = 0;

        function schau() { return pos < tokens.length ? tokens[pos] : null; }

        function primaer() {
            var t = schau();
            if (t === null) { return null; }
            if (t.art === 'auf') {
                pos++;
                var inner = oder();
                if (schau() && schau().art === 'zu') { pos++; }   // fehlt sie: als gesetzt lesen
                return inner;
            }
            if (t.art === 'wort') {
                pos++;
                var s = t.text.toLowerCase();
                return function (hay) { return hay.indexOf(s) !== -1; };
            }
            if (t.art === 'nicht') {
                pos++;
                var inner2 = primaer();
                if (inner2 === null) { return null; }             // `-` ohne Begriff
                return function (hay) { return !inner2(hay); };
            }
            // Ein Operator an dieser Stelle ist eine unfertige Eingabe.
            pos++;
            return null;
        }

        function und() {
            var teile = [];
            for (;;) {
                var t = schau();
                if (t === null || t.art === 'zu' || t.art === 'oder') { break; }
                if (t.art === 'und') { pos++; continue; }          // ausgeschriebenes UND
                var vorher = pos;
                var p = primaer();
                if (p) { teile.push(p); }
                if (pos === vorher) { pos++; }                     // Stillstand ausschliessen
            }
            if (!teile.length) { return null; }
            if (teile.length === 1) { return teile[0]; }
            return function (hay) {
                for (var k = 0; k < teile.length; k++) { if (!teile[k](hay)) { return false; } }
                return true;
            };
        }

        function oder() {
            var teile = [];
            var erst = und();
            if (erst) { teile.push(erst); }
            while (schau() && schau().art === 'oder') {
                pos++;
                var w = und();
                if (w) { teile.push(w); }
            }
            if (!teile.length) { return null; }
            if (teile.length === 1) { return teile[0]; }
            return function (hay) {
                for (var k = 0; k < teile.length; k++) { if (teile[k](hay)) { return true; } }
                return false;
            };
        }

        var baum = oder();
        // Reste nach einer schliessenden Klammer zu viel: als UND anhaengen.
        while (pos < tokens.length) {
            var vorher2 = pos;
            var weiter = und();
            if (weiter && baum) {
                var links = baum, rechts = weiter;
                baum = function (hay) { return links(hay) && rechts(hay); };
            } else if (weiter) { baum = weiter; }
            if (pos === vorher2) { pos++; }
        }
        return baum;
    }

    /** Rueckfall: jedes Wort muss vorkommen (Verhalten bis Web 6.3.0). */
    function einfach(q) {
        var w = q.toLowerCase().split(/\s+/).filter(Boolean);
        if (!w.length) { return null; }
        return function (hay) {
            for (var k = 0; k < w.length; k++) { if (hay.indexOf(w[k]) === -1) { return false; } }
            return true;
        };
    }

    /**
     * Pruefer zu einer Eingabe bauen.
     * @param {string} q  Sucheingabe
     * @returns {?function(string):boolean} null = kein Filter
     */
    function pruefer(q) {
        q = (q || '').trim();
        if (q === '') { return null; }
        try {
            var p = parse(zerlege(q));
            return p || einfach(q);
        } catch (e) {
            // Nie an einer Eingabe scheitern: lieber die alte Regel als eine
            // leere Trefferliste ohne erkennbaren Grund.
            return einfach(q);
        }
    }

    /** Enthaelt die Eingabe ueberhaupt einen Operator? (nur fuer den Hinweis) */
    function mitOperatoren(q) {
        return /(^|\s)(und|and|oder|or|nicht|not)(\s|$)|[()&|!"]|(^|\s)-\S/i.test(q || '');
    }

    global.EdSuchtext = { pruefer: pruefer, mitOperatoren: mitOperatoren };
})(window);
