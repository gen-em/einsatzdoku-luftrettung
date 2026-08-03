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
        var hKlein = dc.getFontHeight(Graphics.FONT_XTINY);
        var hGross = dc.getFontHeight(Graphics.FONT_LARGE);
        var hZahl  = dc.getFontHeight(Graphics.FONT_NUMBER_MILD);
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

        // --- Mittelblock ----------------------------------------------------
        // Rueckstand = nur abgeschlossene, unbestaetigte Pakete — das immer
        // offene laufende Ruhesegment zaehlt nicht als Rueckstand.
        var open = Model.backlogCount();
        var gGps = Ui.s(dc, 14);
        var hHaken = Ui.s(dc, 26);
        var blockH = (open == 0)
            ? hKlein + gGps + hGross + hHaken
            : hKlein + gGps + hZahl + hMitte;
        var y = (h - blockH) / 2;

        dc.setColor(gpsCol, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_XTINY, gpsTxt, Graphics.TEXT_JUSTIFY_CENTER);
        y += hKlein + gGps;

        if (open == 0) {
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

        // --- Unterer Block: Meldungen und Version ---------------------------
        // Erst sammeln, dann als Ganzes vom unteren Rand nach oben setzen.
        // So bleibt der Abstand gleich, egal wie viele Zeilen anfallen.
        var lines = [];
        if (Uploader.lastError != null) {
            lines.add([Uploader.lastError as Lang.String, Graphics.COLOR_LT_GRAY]);
        }
        if (Pair.status != null) {
            var ok = Pair.status.substring(0, 3).equals("Gek");
            lines.add([Pair.status, ok ? Graphics.COLOR_GREEN : Graphics.COLOR_YELLOW]);
        }
        if (Cpr.active) {
            lines.add([Cpr.paused ? "REA pausiert" : "REA läuft",
                       Cpr.paused ? Graphics.COLOR_YELLOW : Ui.ROT]);
        } else if (!Uploader.hasServer()) {
            lines.add(["Erst Server-Adresse setzen", Graphics.COLOR_YELLOW]);
        } else if (!Uploader.hasCredentials()) {
            lines.add([Input.lSelectHold() + ": Gerät koppeln", Graphics.COLOR_YELLOW]);
        }
        lines.add(["Version " + Const.APP_VERSION, Graphics.COLOR_DK_GRAY]);

        var uy = h - Ui.s(dc, 24) - lines.size() * hKlein;
        for (var i = 0; i < lines.size(); i++) {
            dc.setColor(lines[i][1] as Lang.Number, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, uy, Graphics.FONT_XTINY, lines[i][0] as Lang.String,
                Graphics.TEXT_JUSTIFY_CENTER);
            uy += hKlein;
        }
    }
}

class SyncDelegate extends ActionDelegate {

    var _fromStart as Lang.Boolean;

    function initialize(fromStart as Lang.Boolean) {
        ActionDelegate.initialize(false);
        _fromStart = fromStart;
    }

    // Geraete-Kopplung: Code-Eingabe oeffnen (START halten bzw. Action halten)
    function actSelectLong() as Lang.Boolean {
        Pair.openInput();
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
