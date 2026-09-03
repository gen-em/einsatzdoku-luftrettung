// NAdoku — die Kopplungsansicht: die Uhr ZEIGT den Code
//
// Seit 3.0.0 laeuft die Kopplung andersherum (S5, E-R49-1): Nicht die
// Traegerin tippt einen Code ein, den das Web erzeugt hat — die Uhr holt sich
// eine Sitzung, zeigt den Code, und ein Mensch traegt ihn im Web ein. Diese
// Seite ist die Anzeige dazu; der Ablauf dahinter steht in Pair.mc.
//
// WARUM EINE EIGENE ANSICHT und kein vierter Zustand des Mittelblocks der
// Sync-Seite (E-S5-24) — drei Gruende, jeder fuer sich ausreichend:
//   1. Der Code muss GROSS stehen, und er traegt Buchstaben. Eine
//      Ziffernschrift scheidet damit aus (Uhr-Layout_Regeln 3.1: sie kennt
//      keine Buchstaben und zeichnet leere Kaestchen), es bleibt fitFont
//      ueber die Textschriften.
//   2. Die Seite hat eine RESTZEIT, die weiterlaeuft. Der Mittelblock der
//      Sync-Seite hat keinen Zeitbegriff.
//   3. BACK bedeutet hier etwas anderes: Es bricht die Kopplung ab, statt zur
//      vorigen Seite zu blaettern. Zwei Bedeutungen derselben Taste in
//      derselben Ansicht waeren nicht erklaerbar.
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.Lang;
using Toybox.Timer;

class PairView extends WatchUi.View {

    var _timer as Timer.Timer or Null = null;

    function initialize() { View.initialize(); }

    /* Derselbe 2-s-Takt wie auf der Sync-Seite — er treibt die Restzeit, die
     * ohne ihn stehenbliebe. Die ABFRAGE beim Server laeuft langsamer und nie
     * ueberlappend; darueber entscheidet Pair.abfrageAnstossen() allein
     * (Const.PAIR_TAKT_MS, E-S5-25). Der Zeitgeber weiss davon nichts: Er
     * klopft nur an. */
    function onShow() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.start(method(:tick), 2000, true);
    }

    function onHide() as Void {
        if (_timer != null) { _timer.stop(); }
    }

    function tick() as Void {
        Pair.abfrageAnstossen();
        WatchUi.requestUpdate();
    }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var h  = dc.getHeight();
        var cx = dc.getWidth() / 2;

        var fKlein = Ui.fontHint(dc);
        var hKlein = dc.getFontHeight(fKlein);
        var hCode  = dc.getFontHeight(Graphics.FONT_LARGE);

        /* --- Unterer Block ZUERST (Uhr-Layout_Regeln 5.1) -------------------
         *
         * Seine Zeilenzahl schwankt: die Restzeit steht immer, der
         * Verbindungshinweis nur, wenn eine Abfrage nicht durchkam. Wuerde der
         * Codeblock unabhaengig davon mittig gesetzt, wuechsen beide einander
         * entgegen — genau der Fehler, an dem sich die Sync-Seite einmal
         * ueberlappt hat. */
        /* REIHENFOLGE: der VARIABLE Text nach oben, die Restzeit nach unten.
         *
         * Die unterste Zeile sitzt zwischen 84 und 91,5 % der Displayhoehe, und
         * dort traegt die Kreissehne nur noch 128 px (Fenix 6 Pro), 118 (FR945)
         * bzw. 193 (Venu 3s) — gemessen aus den Geraetedateien, nicht
         * geschaetzt. Die Restzeit passt dort mit 71/64/146 px sicher hinein
         * und ist in ihrer Laenge bekannt; der Verbindungshinweis ist es nicht
         * und lief in derselben Zeile um 48 bis 111 px ueber den Rand — ohne
         * Warnung, denn Ui.fitFont hat unter 320 px Displayhoehe gar keine
         * kleinere Schrift mehr zur Wahl (fontHint liefert dort selbst schon
         * FONT_XTINY). Uhr-Layout_Regeln 4.3 nennt genau diesen Fall.
         *
         * Eine Zeile hoeher traegt die Sehne 173/163/271 px — dort passt der
         * Hinweis. Das ist auch der Grund, warum die Sync-Seite an derselben
         * Verankerung ueberlebt: Ihre unterste Zeile ist immer "Version 3.0.0". */
        var lines = [];
        /* "Kopple…" waehrend das Ja unterwegs ist. Hellgrau nach
         * Uhr-Layout_Regeln 7 ("laeuft gerade, noch keine Aussage"), Wortlaut
         * aus 2.0.0 uebernommen. Der Block ist fuer eine schwankende
         * Zeilenzahl gebaut, der Codeblock darueber weicht von selbst aus. */
        if (Pair.jaLaeuft) { lines.add(["Kopple…", Graphics.COLOR_LT_GRAY]); }
        var netz = Pair.netzHinweis;
        if (netz != null) {
            lines.add([netz as Lang.String, Ui.ROT]);
        }
        var rest = Pair.restSekunden();
        if (rest > 60) {
            // Aufgerundet: Bei 540 s steht "noch 9 min", bei 541 "noch 10 min".
            // Abrunden hiesse, in der letzten Minute "noch 0 min" zu zeigen.
            lines.add(["noch " + ((rest + 59) / 60).toString() + " min",
                       Graphics.COLOR_LT_GRAY]);
        } else {
            // Unter einer Minute auf Sekunden umschalten und in Orange: Die
            // Restzeit ist ab hier eine Kennzahl, auf die es ankommt
            // (Uhr-Layout_Regeln 7). Rot waere falsch — noch ist nichts
            // schiefgegangen.
            lines.add(["noch " + rest.toString() + " s", Ui.ORANGE]);
        }

        var untenY = h - Ui.s(dc, 22) - lines.size() * hKlein;

        /* --- Codeblock im Raum darueber -------------------------------------
         *
         * Drei Zeilen, feste Zahl: wofuer der Code ist, der Code, wo er
         * hingehoert. Gerechnet wird mit der Hoehe von FONT_LARGE, auch wenn
         * fitFont am Ende eine kleinere Schrift waehlt — dann bleibt unter dem
         * Code etwas Luft, und das ist der harmlose Fall. Umgekehrt liefe der
         * Block dem unteren entgegen. */
        var gCode  = Ui.s(dc, 10);
        var blockH = hKlein + gCode + hCode + gCode + hKlein;
        var zone = untenY - Ui.s(dc, 8);
        var y = (zone - blockH) / 2;
        if (y < Ui.s(dc, 20)) { y = Ui.s(dc, 20); }

        var tOben = "Code für das Web";
        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y,
            Ui.fitFont(dc, tOben, y, hKlein, [fKlein, Graphics.FONT_XTINY]),
            tOben, Graphics.TEXT_JUSTIFY_CENTER);
        y += hKlein + gCode;

        /* Der Code selbst. KEINE Ziffernschrift (Uhr-Layout_Regeln 3.1): Das
         * Alphabet des Codes traegt Buchstaben, und FONT_NUMBER_* kennt nur
         * Ziffern — er erschiene als Reihe leerer Kaestchen, ohne Warnung und
         * ohne Fehler. Deshalb die Textschriften, von gross nach klein durch
         * fitFont. */
        var tCode = Pair.codeAnzeige();
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y,
            Ui.fitFont(dc, tCode, y, hCode,
                       [Graphics.FONT_LARGE, Graphics.FONT_MEDIUM, Graphics.FONT_SMALL]),
            tCode, Graphics.TEXT_JUSTIFY_CENTER);
        y += hCode + gCode;

        var tWeg = Pair.WEG_IM_WEB;
        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y,
            Ui.fitFont(dc, tWeg, y, hKlein, [fKlein, Graphics.FONT_XTINY]),
            tWeg, Graphics.TEXT_JUSTIFY_CENTER);

        // --- Unterer Block zeichnen ------------------------------------------
        for (var i = 0; i < lines.size(); i++) {
            dc.setColor(lines[i][1] as Lang.Number, Graphics.COLOR_TRANSPARENT);
            var txt = lines[i][0] as Lang.String;
            dc.drawText(cx, untenY,
                Ui.fitFont(dc, txt, untenY, hKlein, [fKlein, Graphics.FONT_XTINY]),
                txt, Graphics.TEXT_JUSTIFY_CENTER);
            untenY += hKlein;
        }
    }
}

/* BACK bricht die Kopplung ab (E-S5-23).
 *
 * Kein Bedienhinweis dafuer auf der Seite: Die Zurueck-Taste tut auf allen
 * drei Zielgeraeten dasselbe (Input.mc), und das Handbuch sagt es. Eine
 * vierte Hinweiszeile haette den Codeblock weiter nach oben gedraengt.
 *
 * Andere Tasten sind hier bewusst unbelegt. BehaviorDelegate schluckt sie;
 * auf einem Touchgeraet heisst das insbesondere, dass ein Tippen auf den Code
 * nichts ausloest — es gibt hier nichts auszuwaehlen. */
class PairDelegate extends WatchUi.BehaviorDelegate {
    function initialize() { BehaviorDelegate.initialize(); }

    function onBack() as Lang.Boolean {
        Pair.abbrechen();
        return true;
    }
}

/* Die Rueckbestaetigung — der zweite der beiden Tore aus E-R49-5.
 *
 * Bewusst der vorhandene Baustein WatchUi.Confirmation, wie beim Trennen, beim
 * Einsatzabschluss und beim Verlassen der App. Er traegt genau das, was hier
 * gebraucht wird: eine Frage, zwei Antworten, kein weiterer Inhalt.
 *
 * Die Frage nennt das KONTO, nicht das Geraet: Die Traegerin weiss, welche Uhr
 * sie in der Hand haelt; was sie nicht weiss, ist, in wessen Konto der Code
 * gelandet ist. Genau das ist der Angriff, den dieses Tor abfaengt — das
 * eigene Geraet im fremden Konto. */
class KoppelnDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }

    function onResponse(response) as Lang.Boolean {
        if (response != WatchUi.CONFIRM_YES) {
            Pair.ablehnen("Nicht gekoppelt");
            return true;
        }
        /* Schlaegt das Senden schon hier fehl, ist die Server-Adresse
         * zwischendurch geleert worden oder die Sitzung fort. Ohne diesen
         * Zweig bliebe die Kopplungsansicht mit einem Code stehen, den
         * niemand mehr einloest. */
        if (!Pair.bestaetigen("ja")) {
            Pair.ablehnen("Nicht gekoppelt");
        }
        return true;
    }
}
