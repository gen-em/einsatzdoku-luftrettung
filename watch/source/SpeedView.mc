// Einsatzdoku — Oberflaeche: aktuelle Geschwindigkeit + Einsatzdistanz
//
// Der Block aus Zahl, Einheit und Distanz wird als Ganzes vertikal zentriert.
// "km/h" sitzt eng unter der Zahl (gehoert dazu), die Distanz mit Abstand
// darunter (eigene Aussage). Der Rea-Marker bleibt am unteren Rand.
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.Lang;

class SpeedView extends WatchUi.View {

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;

        var hZahl  = Ui.numH(dc, Graphics.FONT_NUMBER_THAI_HOT);
        var hEinh  = dc.getFontHeight(Graphics.FONT_TINY);
        var hDist  = dc.getFontHeight(Graphics.FONT_MEDIUM);
        var gEinh  = Ui.s(dc, 0);        // Zahl -> km/h: eng
        var gDist  = Ui.s(dc, 16);       // km/h -> Distanz: Absatz

        var blockH = hZahl + gEinh + hEinh + gDist + hDist;
        var y = (dc.getHeight() - blockH) / 2;

        // Geschwindigkeit gross (km/h)
        var kmh = Track.speedMs * 3.6;
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_NUMBER_THAI_HOT, kmh.format("%d"),
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hZahl + gEinh;

        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_TINY, "km/h",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hEinh + gDist;

        // Einsatzdistanz (nur bei laufendem Einsatz)
        if (Model.missionActive()) {
            dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y, Graphics.FONT_MEDIUM,
                (Track.distanceM / 1000.0).format("%.1f") + " km",
                Graphics.TEXT_JUSTIFY_CENTER);
        } else {
            dc.setColor(Graphics.COLOR_DK_GRAY, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y, Graphics.FONT_TINY, "kein Einsatz",
                Graphics.TEXT_JUSTIFY_CENTER);
        }

        Ui.drawResusMarker(dc);
    }
}

class SpeedDelegate extends ActionDelegate {
    function initialize() { ActionDelegate.initialize(false); }
    function actPageNext() as Lang.Boolean { Nav.go(1); return true; }
    function actPagePrev() as Lang.Boolean { Nav.go(-1); return true; }
    function actBack() as Lang.Boolean { Nav.goTo(:clock); return true; }
}
