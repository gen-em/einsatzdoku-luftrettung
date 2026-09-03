// Netzprobe — beantwortet F-S5-11: Laesst der Connect-IQ-Simulator einen
// makeWebRequest gegen http://127.0.0.1:8080 zu, oder verlangt er TLS?
//
// Sie stellt EINE Anfrage an pair.php und zeigt den Ruecklaufcode gross an.
// Der Beleg liegt aber nicht auf dem Display, sondern im Zugriffsprotokoll
// des Servers: Was dort ankommt, hat den Simulator verlassen.
//
//   >= 0   die Anfrage ist hinaus (405 von pair.php ist ein VOLLTREFFER:
//          der Endpunkt lehnt GET ab, also hat er sie gesehen)
//   <  0   der Simulator hat sie nicht hinausgelassen
using Toybox.Application;
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.Communications;
using Toybox.Lang;
using Toybox.System;

var gCode = -999;
var gZiel = "http://127.0.0.1:8080/pair.php";

class Rueckruf {
    function initialize() { }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        gCode = code;
        System.println("NETZPROBE Ziel=" + gZiel + " Code=" + code.toString());
        WatchUi.requestUpdate();
    }
}

class ProbeView extends WatchUi.View {
    function initialize() { View.initialize(); }
    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        var mitte = dc.getHeight() / 2;
        dc.drawText(dc.getWidth() / 2, mitte - 40, Graphics.FONT_TINY,
                    "Netzprobe", Graphics.TEXT_JUSTIFY_CENTER);
        dc.drawText(dc.getWidth() / 2, mitte - 10, Graphics.FONT_LARGE,
                    gCode == -999 ? "..." : gCode.toString(),
                    Graphics.TEXT_JUSTIFY_CENTER);
        dc.drawText(dc.getWidth() / 2, mitte + 35, Graphics.FONT_XTINY,
                    gCode >= 0 ? "hinaus" : (gCode == -999 ? "laeuft" : "blockiert"),
                    Graphics.TEXT_JUSTIFY_CENTER);
    }
}

class ProbeApp extends Application.AppBase {
    var _cb;
    function initialize() { AppBase.initialize(); }
    function onStart(state) as Void {
        _cb = new Rueckruf();
        System.println("NETZPROBE start -> " + gZiel);
        Communications.makeWebRequest(
            gZiel, {},
            { :method => Communications.HTTP_REQUEST_METHOD_GET,
              :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON },
            _cb.method(:onResponse));
    }
    function getInitialView() { return [ new ProbeView() ]; }
}
