// Mitschrift der Reanimation — NUR fuer die Prueffassung von rea.sh, KEIN
// Bestandteil der App. rea.sh haengt vier Haken in die Kopie von watch/:
// jeden Vibrationsaufruf (Util._vibe, Profile ueber profil()), jedes gebuchte Ereignis
// (Model.resusEvent), jeden Seitenwechsel (Nav.go, Nav.goTo) und das Oeffnen
// von Untermenue und Uebersicht. Die Zeilen liest rea.sh aus der Konsole.
//
// Anlass: Nr. 571 — Tastenbelegung, Sperre und Vibrationen der Reanimation.
using Toybox.Lang;
using Toybox.System;
using Toybox.Attention;

module ReaProbe {
    // Jedes Profil, das Util.mc anlegt, laeuft hier durch (rea.sh ersetzt
    // dort `new Attention.VibeProfile(` durch `ReaProbe.profil(`): Die Felder
    // eines fertigen Profils liest Connect IQ nicht heraus.
    var _ms as Lang.Array<Lang.Number> = [];
    var _staerke as Lang.Number = 0;

    function profil(staerke as Lang.Number, ms as Lang.Number) as Attention.VibeProfile {
        _ms.add(ms);
        if (staerke > _staerke) { _staerke = staerke; }
        return new Attention.VibeProfile(staerke, ms);
    }

    // "REAVIB 200,200,200 @100" — an und aus abwechselnd, in ms, dahinter
    // die hoechste Staerke des Musters.
    function vib(p as Lang.Array) as Void {
        var t = "";
        for (var i = 0; i < _ms.size(); i++) { t += (i > 0 ? "," : "") + _ms[i].toString(); }
        System.println("REAVIB " + t + " @" + _staerke.toString());
        _ms = [];
        _staerke = 0;
    }
    function ereignis(type as Lang.String) as Void { System.println("REAEVENT " + type); }
    function seite(s as Lang.String) as Void { System.println("REANAV " + s); }
    function menue(label as Lang.String) as Void { System.println("REAMENU " + label); }
    function uebersicht() as Void { System.println("REAUEBERSICHT"); }
}
