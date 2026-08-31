// Einsatzdoku — Sync-Status & App-Version (eigene Seite)
//
// Waehrend des Dienstes im Seiten-Pager zwischen Statistik und Rea;
// vom Startbildschirm aus per DOWN erreichbar (BACK fuehrt zurueck).
// Die Seite stoesst im Hintergrund weiter Sendeversuche an.
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.Lang;
using Toybox.Timer;
using Toybox.Application.Storage;
using Toybox.Position;

class SyncView extends WatchUi.View {

    var _fromStart as Lang.Boolean;
    var _timer as Timer.Timer or Null = null;

    function initialize(fromStart as Lang.Boolean) {
        View.initialize();
        _fromStart = fromStart;
    }

    function onShow() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.start(method(:refresh), 2000, true);
        if (!Uploader.allSynced()) { Uploader.syncAll(); }
    }

    function onHide() as Void {
        if (_timer != null) { _timer.stop(); }
    }

    function refresh() as Void {
        if (!Uploader.allSynced()) { Uploader.syncAll(); }
        WatchUi.requestUpdate();
    }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var w = dc.getWidth();
        var h = dc.getHeight();
        var cx = w / 2;

        // Aufbau: GPS-Guete, darunter die Hauptaussage zum Rueckstand — dieser
        // Block sitzt vertikal in der Mitte. Fehlergrund, Kopplungsmeldung,
        // Einrichtungshinweis und Version bilden unten einen eigenen Block.
        // Eine Ueberschrift braucht die Seite nicht, die Aussage traegt sich
        // selbst.
        var fKlein = Ui.fontHint(dc);
        var hKlein = dc.getFontHeight(fKlein);
        var hGross = dc.getFontHeight(Graphics.FONT_LARGE);
        var hZahl  = Ui.numH(dc, Graphics.FONT_NUMBER_MILD);
        var hMitte = dc.getFontHeight(Graphics.FONT_SMALL);

        // --- GPS-Guete -----------------------------------------------------
        // Spiegelt exakt die Schwelle, ab der Track.mc Punkte speichert
        // (< QUALITY_POOR wird verworfen) — sonst waere die Anzeige irrefuehrend.
        var gpsTxt = "GPS aus (kein Dienst)";
        var gpsCol = Graphics.COLOR_DK_GRAY;
        if (Model.serviceActive) {
            var q = Position.QUALITY_NOT_AVAILABLE;
            var pi = Position.getInfo();
            if (pi != null && pi.accuracy != null) { q = pi.accuracy; }
            if (q >= Position.QUALITY_USABLE) {
                gpsTxt = "GPS gut"; gpsCol = Graphics.COLOR_GREEN;
            } else if (q >= Position.QUALITY_POOR) {
                gpsTxt = "GPS ausreichend"; gpsCol = Graphics.COLOR_GREEN;
            } else {
                gpsTxt = "GPS zu schwach"; gpsCol = Ui.ROT;
            }
        }

        /* --- Kann die Uhr ueberhaupt senden? (Backlog Nr. 11) ---------------
         *
         * Die Seite beantwortet die Frage "ist alles uebertragen?". Bis
         * hierher las sie dafuer allein Model.backlogCount() — und die Zahl
         * beantwortet eine ANDERE Frage: "liegen abgeschlossene Pakete
         * bereit?". Vor dem ersten Dienst ist sie zu Recht 0, und daraus wurde
         * ein gruenes "Sync vollstaendig" ueber einen Weg, den die Uhr nie
         * benutzt hat — waehrend unten in derselben Anzeige "Erst
         * Server-Adresse setzen" stand. Zwei Aussagen, die einander
         * widersprachen, nebeneinander.
         *
         * Der gruene Zustand setzt deshalb BEIDES voraus: eine Server-Adresse
         * (App-Einstellungen) und eine Kopplung. Fehlt eines davon, tritt der
         * Einrichtungszustand an seine Stelle, und der bisherige Fusszeilen-
         * hinweis wird zur Hauptaussage.
         *
         * Reihenfolge wie bei der Einrichtung selbst — erst die Adresse, dann
         * koppeln. Ohne Adresse ist Koppeln gar nicht moeglich (Pair.request
         * bricht ab), ein Hinweis darauf waere also die falsche Reihenfolge.
         */
        var schritt = null;                    // null = eingerichtet
        if (!Uploader.hasServer()) {
            schritt = "Erst Server-Adresse setzen";
        } else if (!Uploader.hasCredentials()) {
            schritt = Input.lSelectHold() + ": Gerät koppeln";
        }
        // Rueckstand = nur abgeschlossene, unbestaetigte Pakete — das immer
        // offene laufende Ruhesegment zaehlt nicht als Rueckstand.
        var open = Model.backlogCount();
        /* Der Einrichtungszustand ersetzt NUR den gruenen Fall. Liegt ein
         * Rueckstand vor, bleibt die Zahl die Hauptaussage: Sie ist wahr, sie
         * ist die dringlichere Information, und der Grund steht dann als
         * Hinweis darunter. Ein Widerspruch entsteht dabei nicht — es sind
         * Pakete offen, und daneben steht, warum. */
        var einrichten = (schritt != null && open == 0);

        // --- Unterer Block zuerst -------------------------------------------
        // Meldungen und Version stehen unten. Ihre Zeilenzahl schwankt, deshalb
        // wird ihr Platz VOR dem Mittelblock bestimmt — sonst waechst der untere
        // Block dem oberen entgegen und beide ueberlappen.
        var lines = [];
        if (Uploader.lastError != null) {
            lines.add([Uploader.lastError as Lang.String, Ui.ROT]);
        }
        if (Pair.status != null) {
            var pc = Ui.ROT;                       // :error
            if (Pair.statusKind == :ok) { pc = Graphics.COLOR_GREEN; }
            else if (Pair.statusKind == :busy) { pc = Graphics.COLOR_LT_GRAY; }
            lines.add([Pair.status, pc]);
            /* Zweite Zeile: was zu tun ist. Sie kommt nur bei den Faellen, wo
             * es etwas zu tun gibt. Bewusst gedaempft — die Ursache steht
             * darueber, das hier ist der Weg heraus. Die Zeilenzahl geht in
             * untenY ein, der Mittelblock weicht also von selbst aus. */
            if (Pair.statusHint != null) {
                lines.add([Pair.statusHint as Lang.String, Graphics.COLOR_LT_GRAY]);
            }
        }
        if (Cpr.active) {
            lines.add([Cpr.paused ? "REA pausiert" : "REA läuft",
                       Cpr.paused ? Ui.BLAU : Ui.ROT]);
        } else if (schritt != null && !einrichten) {
            // Steht der Schritt schon in der Mitte, wird er hier NICHT
            // wiederholt — zweimal dieselbe Zeile auf einem Uhrendisplay ist
            // verschenkter Platz.
            lines.add([schritt as Lang.String, Ui.ROT]);
        }
        lines.add(["Version " + Const.APP_VERSION, Graphics.COLOR_DK_GRAY]);

        var untenY = h - Ui.s(dc, 22) - lines.size() * hKlein;

        // --- Mittelblock ----------------------------------------------------
        // Zentriert wird im Raum OBERHALB des unteren Blocks.
        var gGps = Ui.s(dc, 14);
        var hHaken = Ui.s(dc, 26);
        var hZust  = dc.getFontHeight(Graphics.FONT_MEDIUM);
        var gZust  = Ui.s(dc, 6);              // Zustand -> Weg heraus (eng)
        var blockH;
        if (einrichten) {
            blockH = hKlein + gGps + hZust + gZust + hKlein;
        } else if (open == 0) {
            blockH = hKlein + gGps + hGross + hHaken;
        } else {
            blockH = hKlein + gGps + hZahl + hMitte;
        }
        var zone = untenY - Ui.s(dc, 8);
        var y = (zone - blockH) / 2;
        if (y < Ui.s(dc, 20)) { y = Ui.s(dc, 20); }

        dc.setColor(gpsCol, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, fKlein, gpsTxt, Graphics.TEXT_JUSTIFY_CENTER);
        y += hKlein + gGps;

        if (einrichten) {
            /* Zwei Zeilen: WAS ist los, und WAS hilft — dasselbe Muster wie
             * bei der Kopplungsmeldung (Pair.status / Pair.statusHint). Der
             * Zustand traegt Rot wie ueberall sonst, wo die Einrichtung fehlt
             * (StartView, Fusszeile dieser Seite); der Weg heraus steht
             * gedaempft darunter und nimmt dem Zustand nicht die Aufmerksamkeit.
             *
             * Kein Haken, kein Symbol: Der Zustand ist weder erledigt noch
             * fehlgeschlagen — es ist schlicht noch nichts eingerichtet. */
            var tZ = "Nicht eingerichtet";
            dc.setColor(Ui.ROT, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y,
                Ui.fitFont(dc, tZ, y, hZust,
                           [Graphics.FONT_MEDIUM, Graphics.FONT_SMALL, fKlein]),
                tZ, Graphics.TEXT_JUSTIFY_CENTER);
            var sy = y + hZust + gZust;
            var tS = schritt as Lang.String;
            dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, sy,
                Ui.fitFont(dc, tS, sy, hKlein, [fKlein, Graphics.FONT_XTINY]),
                tS, Graphics.TEXT_JUSTIFY_CENTER);
        } else if (open == 0) {
            dc.setColor(Graphics.COLOR_GREEN, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y, Graphics.FONT_LARGE, "Sync vollständig",
                Graphics.TEXT_JUSTIFY_CENTER);
            // Haken selbst zeichnen (die Geraeteschrift kennt das Glyph nicht)
            var hy = y + hGross + Ui.s(dc, 8);
            dc.setPenWidth(Ui.s(dc, 5));
            dc.drawLine(cx - Ui.s(dc, 14), hy, cx - Ui.s(dc, 4), hy + Ui.s(dc, 10));
            dc.drawLine(cx - Ui.s(dc, 4), hy + Ui.s(dc, 10), cx + Ui.s(dc, 15), hy - Ui.s(dc, 11));
            dc.setPenWidth(1);
        } else {
            dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y, Graphics.FONT_NUMBER_MILD, open.toString(),
                Graphics.TEXT_JUSTIFY_CENTER);
            dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y + hZahl, Graphics.FONT_SMALL,
                open == 1 ? "Paket offen" : "Pakete offen",
                Graphics.TEXT_JUSTIFY_CENTER);
        }

        // --- Unterer Block zeichnen -----------------------------------------
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

class SyncDelegate extends ActionDelegate {

    var _fromStart as Lang.Boolean;

    function initialize(fromStart as Lang.Boolean) {
        ActionDelegate.initialize(false);
        _fromStart = fromStart;
    }

    // Geraete-Kopplung (START halten bzw. Action halten). NICHT direkt in die
    // Code-Eingabe: Besteht schon eine Kopplung, fragt Pair.start() zuerst und
    // trennt sie ausdruecklich — Begruendung dort (Backlog Nr. 14).
    function actSelectLong() as Lang.Boolean {
        Pair.start();
        return true;
    }

    function actPageNext() as Lang.Boolean {
        if (_fromStart) { return true; }           // vom Start: keine Nachbarseiten
        Nav.go(1); return true;
    }

    function actPagePrev() as Lang.Boolean {
        if (_fromStart) { return true; }
        Nav.go(-1); return true;
    }

    function actBack() as Lang.Boolean {
        if (_fromStart) {
            WatchUi.popView(WatchUi.SLIDE_DOWN);   // zurueck zum Startbildschirm
        } else {
            Nav.goTo(:clock);
        }
        return true;
    }
}
