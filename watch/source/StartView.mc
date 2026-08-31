// NAdoku — Startbildschirm "Dienst beginnen" (Anforderungen 1.1)
//
// Aufbau: Die oberen 75 % der Displayhoehe tragen den Block aus Bildmarke,
// Titel, Frage und Bedienhinweis — darin vertikal zentriert. Die unteren 25 %
// sind den Einrichtungshinweisen vorbehalten. Die Trennung ist unsichtbar,
// sorgt aber dafuer, dass der Block nicht springt, wenn unten ein Hinweis
// erscheint oder verschwindet.
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.System;
using Toybox.Lang;
using Toybox.Timer;

class StartView extends WatchUi.View {

    var _timer as Timer.Timer or Null = null;
    var _logo as WatchUi.BitmapResource or Null = null;

    function initialize() { View.initialize(); }

    // Nach "Trotzdem beenden? -> Nein": im Hintergrund weiter senden und den
    // Status live anzeigen, bis alles bestaetigt ist.
    function onShow() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.start(method(:refresh), 2000, true);
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
        var cx = dc.getWidth() / 2;

        if (_logo == null) {
            _logo = Ui.logoRes();   // Luft oder Boden, s. Ui.logoRes()
        }
        var logoH = _logo.getHeight();
        var logoW = _logo.getWidth();

        // Blockhoehe aus den Einzelhoehen. Die Abstaende sind bewusst eng
        // zwischen Frage und Bedienhinweis (sie gehoeren zusammen) und
        // groesser nach der Bildmarke.
        var hTitel   = dc.getFontHeight(Graphics.FONT_MEDIUM);
        var hFrage   = dc.getFontHeight(Graphics.FONT_SMALL);
        var fHinweis = Ui.fontHint(dc);
        var hHinweis = dc.getFontHeight(fHinweis);
        var gLogo    = Ui.s(dc, 6);      // Bildmarke -> Titel
        var gTitel   = Ui.s(dc, 10);     // Titel -> Frage
        var gFrage   = Ui.s(dc, 2);      // Frage -> Bedienhinweis (eng)

        var blockH = logoH + gLogo + hTitel + gTitel + hFrage + gFrage + hHinweis;
        var zone   = Ui.pct(dc, 75);                  // obere Zone
        var y      = (zone - blockH) / 2;
        if (y < Ui.s(dc, 4)) { y = Ui.s(dc, 4); }     // Notbremse bei engen Displays

        dc.drawBitmap(cx - logoW / 2, y, _logo as WatchUi.BitmapResource);
        y += logoH + gLogo;

        dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_MEDIUM, "NAdoku",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hTitel + gTitel;

        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_SMALL, "Dienst beginnen?",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hFrage + gFrage;

        dc.setColor(Ui.BLAU, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, fHinweis, Input.lSelect() + " drücken",
            Graphics.TEXT_JUSTIFY_CENTER);

        // Einrichtungshinweise direkt UNTER dem Hauptblock, nicht in der
        // unteren Zone zentriert. Ganz unten laeuft der Kreis so weit zu, dass
        // dort keine Textzeile mehr Platz findet — der Hinweis wurde dann
        // abgeschnitten, egal wie klein die Schrift gewaehlt wurde.
        // Reihenfolge der Einrichtung: erst die Server-Adresse (App-
        // Einstellungen in Garmin Connect), dann koppeln.
        // EINE Zeile, bewusst kurz. Zwei Zeilen passen hier nicht: Die zweite
        // saesse so tief, dass der zulaufende Kreis selbst in der kleinsten
        // Schrift keine 17 Zeichen mehr traegt. Was zu tun ist, steht
        // ausfuehrlich auf der Sync-Seite — einen Schritt nach unten.
        var warn = null;
        if (!Uploader.hasServer()) {
            warn = "Server fehlt";
        } else if (!Uploader.hasCredentials()) {
            warn = "Nicht gekoppelt";
        }
        if (warn == null) { return; }

        var hy = y + hHinweis + Ui.s(dc, 16);
        var f  = fHinweis;
        if (!_fits(dc, warn, hy, dc.getFontHeight(f), f)) {
            f = Graphics.FONT_XTINY;
        }
        dc.setColor(Ui.ROT, Graphics.COLOR_TRANSPARENT);   // Warnung, nicht Hinweis
        dc.drawText(cx, hy, f, warn, Graphics.TEXT_JUSTIFY_CENTER);
    }

    // Passt der Text in seiner Zeile noch vollstaendig auf das runde Display?
    function _fits(dc as Graphics.Dc, txt as Lang.String, top as Lang.Number,
                   lh as Lang.Number, font as Graphics.FontType) as Lang.Boolean {
        return dc.getTextWidthInPixels(txt, font) <= Ui.chordW(dc, top + lh);
    }
}

class StartDelegate extends ActionDelegate {

    function initialize() { ActionDelegate.initialize(false); }

    // Sync-Status & App-Version (kurz DOWN bzw. Wischen nach unten)
    function actPageNext() as Lang.Boolean {
        WatchUi.pushView(new SyncView(true), new SyncDelegate(true), WatchUi.SLIDE_UP);
        return true;
    }

    function actSelectShort() as Lang.Boolean {
        Model.beginService();
        Util.vibrateTwice();                       // fuehlbar: Aufzeichnung laeuft
        Nav.goTo(:clock);
        return true;
    }

    function actBack() as Lang.Boolean {
        // Kein return danach: System.exit() kehrt nicht zurueck, der Compiler
        // meldet die Anweisung als nicht erreichbar.
        System.exit();
    }
}
