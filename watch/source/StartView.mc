// Einsatzdoku — Startbildschirm "Dienst beginnen" (Anforderungen 1.1)
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
            _logo = WatchUi.loadResource(Rez.Drawables.Logo) as WatchUi.BitmapResource;
        }
        var logoH = _logo.getHeight();
        var logoW = _logo.getWidth();

        // Blockhoehe aus den Einzelhoehen. Die Abstaende sind bewusst eng
        // zwischen Frage und Bedienhinweis (sie gehoeren zusammen) und
        // groesser nach der Bildmarke.
        var hTitel   = dc.getFontHeight(Graphics.FONT_MEDIUM);
        var hFrage   = dc.getFontHeight(Graphics.FONT_SMALL);
        var hHinweis = dc.getFontHeight(Graphics.FONT_XTINY);
        var gLogo    = Ui.s(dc, 6);      // Bildmarke -> Titel
        var gTitel   = Ui.s(dc, 10);     // Titel -> Frage
        var gFrage   = Ui.s(dc, 2);      // Frage -> Bedienhinweis (eng)

        var blockH = logoH + gLogo + hTitel + gTitel + hFrage + gFrage + hHinweis;
        var zone   = Ui.pct(dc, 75);                  // obere Zone
        var y      = (zone - blockH) / 2;
        if (y < Ui.s(dc, 4)) { y = Ui.s(dc, 4); }     // Notbremse bei engen Displays

        dc.drawBitmap(cx - logoW / 2, y, _logo);
        y += logoH + gLogo;

        dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_MEDIUM, "Einsatzdoku",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hTitel + gTitel;

        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_SMALL, "Dienst beginnen?",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hFrage + gFrage;

        dc.setColor(Ui.BLAU, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_XTINY, Input.lSelect() + " drücken",
            Graphics.TEXT_JUSTIFY_CENTER);

        // Untere Zone: Einrichtung in der richtigen Reihenfolge — erst die
        // Server-Adresse (App-Einstellungen in Garmin Connect), dann koppeln.
        var hy = zone + (dc.getHeight() - zone - hHinweis * 2) / 2;
        dc.setColor(Graphics.COLOR_YELLOW, Graphics.COLOR_TRANSPARENT);
        if (!Uploader.hasServer()) {
            dc.drawText(cx, hy, Graphics.FONT_XTINY,
                "Server in Garmin Connect", Graphics.TEXT_JUSTIFY_CENTER);
            dc.drawText(cx, hy + hHinweis, Graphics.FONT_XTINY,
                "eintragen", Graphics.TEXT_JUSTIFY_CENTER);
        } else if (!Uploader.hasCredentials()) {
            dc.drawText(cx, hy, Graphics.FONT_XTINY,
                "Nicht gekoppelt —", Graphics.TEXT_JUSTIFY_CENTER);
            dc.drawText(cx, hy + hHinweis, Graphics.FONT_XTINY,
                Input.lPageDown(), Graphics.TEXT_JUSTIFY_CENTER);
        }
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
        System.exit();
        return true;
    }
}
