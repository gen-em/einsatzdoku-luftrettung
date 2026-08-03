// Einsatzdoku — Oberflaeche: Statistik des laufenden Einsatztags
// Einsaetze = abgeschlossene Einsaetze des Tages (Alarmierung + Ende)
//
// Ueberschrift, Zahl und Beschriftung bilden einen Block und werden als
// Ganzes vertikal zentriert. Der Rea-Marker bleibt am unteren Rand.
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.Lang;

class StatsView extends WatchUi.View {

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;

        var hKopf  = dc.getFontHeight(Graphics.FONT_TINY);
        var hZahl  = Ui.numH(dc, Graphics.FONT_NUMBER_HOT);
        var hLabel = dc.getFontHeight(Graphics.FONT_MEDIUM);
        var gKopf  = Ui.s(dc, 14);
        var gZahl  = Ui.s(dc, 4);

        var blockH = hKopf + gKopf + hZahl + gZahl + hLabel;
        var y = (dc.getHeight() - blockH) / 2;

        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_TINY, "Heute",
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hKopf + gKopf;

        dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_NUMBER_HOT,
            Model.dayMissions.toString(), Graphics.TEXT_JUSTIFY_CENTER);
        y += hZahl + gZahl;

        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_MEDIUM, "Einsätze",
            Graphics.TEXT_JUSTIFY_CENTER);

        Ui.drawResusMarker(dc);
    }
}

class StatsDelegate extends ActionDelegate {
    function initialize() { ActionDelegate.initialize(false); }
    function actPageNext() as Lang.Boolean { Nav.go(1); return true; }
    function actPagePrev() as Lang.Boolean { Nav.go(-1); return true; }
    function actBack() as Lang.Boolean { Nav.goTo(:clock); return true; }
}
