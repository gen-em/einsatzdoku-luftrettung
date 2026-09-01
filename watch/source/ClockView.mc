// NAdoku — Oberflaeche 1: Uhrzeit + Phase
//
// kurz START: naechste Phase (2..9). Nach Phase 9 bleibt der Einsatz offen;
// kurz START zeigt dann die Bestaetigung "Einsatz beenden & senden?".
// lang START: farbcodiertes Schnellmenue (Phasen, Einsatzuebersicht gelb,
// Einsatz abschliessen gruen, Einsatztag beenden rot — beide mit Bestaetigung).
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.System;
using Toybox.Lang;
using Toybox.Timer;

class ClockView extends WatchUi.View {

    var _timer as Timer.Timer or Null = null;

    function initialize() { View.initialize(); }

    function onShow() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.start(method(:refresh), 1000, true);   // Uhrzeit im Sekundentakt
    }

    function onHide() as Void {
        if (_timer != null) { _timer.stop(); }
    }

    function refresh() as Void { WatchUi.requestUpdate(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;

        // Uhrzeit, Datum, Phasennummer und Phasenname bilden einen Block und
        // werden als Ganzes vertikal zentriert. Zwischen Datum und Phase steht
        // bewusst ein groesserer Abstand: Das sind zwei verschiedene Aussagen —
        // oben wann, unten wo im Einsatz.
        var hZeit  = Ui.numH(dc, Graphics.FONT_NUMBER_THAI_HOT);
        var hDatum = dc.getFontHeight(Graphics.FONT_TINY);
        var hNr    = Ui.numH(dc, Graphics.FONT_LARGE);
        var hName  = dc.getFontHeight(Graphics.FONT_TINY);
        var gZeit  = Ui.s(dc, 2);      // Uhrzeit -> Datum: eng, gehoert zusammen
        var gDatum = Ui.s(dc, 14);     // Datum -> Phase: Absatz
        var gNr    = Ui.s(dc, 4);      // Nummer -> Bezeichnung: eng

        var blockH = hZeit + gZeit + hDatum + gDatum + hNr + gNr + hName;
        // Leicht nach unten versetzt: Die Ziffernschrift laesst oben mehr Luft
        // als unten, rechnerisch mittig wirkt dadurch zu hoch.
        var y = (dc.getHeight() - blockH) / 2 + Ui.s(dc, 8);

        var t = System.getClockTime();
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_NUMBER_THAI_HOT,
            t.hour.format("%02d") + ":" + t.min.format("%02d"),
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hZeit + gZeit;

        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_TINY, Util.localDateShort(),
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hDatum + gDatum;

        dc.setColor(Ui.ORANGE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, Graphics.FONT_LARGE, Model.phase.toString(),
            Graphics.TEXT_JUSTIFY_CENTER);
        y += hNr + gNr;

        // Die Phasenbezeichnung steht dicht ueber dem unteren Kreisrand.
        // "Ankunft Einsatzort" passt dort in FONT_TINY nicht mehr, deshalb
        // wird die groesste Schrift gewaehlt, die in dieser Hoehe hineingeht.
        var label = Const.PHASE_LABELS[Model.phase] as Lang.String;
        var fName = Ui.fitFont(dc, label, y, hName,
            [Graphics.FONT_TINY, Graphics.FONT_XTINY]);
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, y, fName, label, Graphics.TEXT_JUSTIFY_CENTER);

        // Laufende Reanimation: roter Ring entlang der Luenette — peripher
        // erkennbar, ohne eine Textzeile zu belegen.
        if (Cpr.active) {
            dc.setPenWidth(Ui.s(dc, 9));
            dc.setColor(Cpr.paused ? Ui.BLAU : Ui.ROT,
                Graphics.COLOR_TRANSPARENT);
            var rad = (dc.getWidth() < dc.getHeight() ? dc.getWidth() : dc.getHeight()) / 2
                      - Ui.s(dc, 5);
            dc.drawCircle(cx, cy, rad);
            dc.setPenWidth(1);
        }
    }
}

class ClockDelegate extends ActionDelegate {

    function initialize() { ActionDelegate.initialize(false); }

    // kurz START: naechste Phase; nach Phase 9 die Abschluss-Bestaetigung
    function actSelectShort() as Lang.Boolean {
        if (Model.missionActive() && Model.phase >= 9) {
            pushFinishConfirm();               // Haltezustand: Abschluss bestaetigen
        } else {
            Model.nextPhase();
            WatchUi.requestUpdate();
        }
        return true;
    }

    // lang START: farbcodiertes Schnellmenue
    function actSelectLong() as Lang.Boolean {
        var v = new QuickMenuView();
        WatchUi.pushView(v, new QuickMenuDelegate(v), WatchUi.SLIDE_LEFT);
        return true;
    }

    static function pushFinishConfirm() as Void {
        var dlg = new WatchUi.Confirmation("Einsatz beenden & senden?");
        WatchUi.pushView(dlg, new FinishConfirmDelegate(), WatchUi.SLIDE_LEFT);
    }

    function actPageNext() as Lang.Boolean { Nav.go(1); return true; }
    function actPagePrev() as Lang.Boolean { Nav.go(-1); return true; }

    function actBack() as Lang.Boolean {
        // Versehentliches Beenden verhindern: Dienst laeuft weiter, App bleibt offen
        var dlg = new WatchUi.Confirmation("Dienst läuft. App verlassen?");
        WatchUi.pushView(dlg, new ExitConfirmDelegate(), WatchUi.SLIDE_LEFT);
        return true;
    }
}

class ExitConfirmDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) { System.exit(); }
        return true;
    }
}

class FinishConfirmDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) {
            Model.finishMission();
            WatchUi.requestUpdate();
        }
        return true;
    }
}

// Rueckruf fuer den verzoegerten Ansichtswechsel (siehe EndDay.begin)
class EndDayCb {
    function initialize() {}
    function fire() as Void { EndDay.show(); }
}

module EndDay {
    var _t as Timer.Timer or Null = null;
    var _cb as EndDayCb or Null = null;

    // Dienst beenden und die Sende-Ansicht zeigen. Der Ansichtswechsel darf
    // NICHT direkt in onResponse passieren: Die Bestaetigung schliesst danach
    // ihre eigene Ansicht und nimmt die neue gleich wieder mit — die App blieb
    // dadurch offen. Deshalb minimal verzoegert.
    function begin() as Void {
        Cpr.stop();
        Model.endService();
        if (_cb == null) { _cb = new EndDayCb(); }
        if (_t == null) { _t = new Timer.Timer(); }
        _t.start(_cb.method(:fire), 100, false);
    }

    function show() as Void {
        if (_t != null) { _t.stop(); }
        WatchUi.switchToView(new SendingView(), new SendingDelegate(), WatchUi.SLIDE_DOWN);
    }
}

class EndDayConfirmDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) { EndDay.begin(); }
        return true;
    }
}

// ---------------------------------------------------------------------------
// Dienstende: "Sende Daten..." — wartet auf die Server-Bestaetigung und
// beendet die App dann automatisch. Klappt es nicht binnen
// Const.END_SYNC_WAIT_S, folgt die Rueckfrage "Trotzdem beenden?".
// ---------------------------------------------------------------------------

class SendingView extends WatchUi.View {

    var _timer as Timer.Timer or Null = null;
    var _ticks as Lang.Number = 0;

    function initialize() { View.initialize(); }

    function onShow() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.start(method(:tick), 1000, true);
        Uploader.syncAll();
    }

    function onHide() as Void {
        if (_timer != null) { _timer.stop(); }
    }

    function tick() as Void {
        if (Uploader.allSynced()) {
            System.exit();                        // alles bestaetigt -> App zu
        }
        _ticks += 1;
        if (_ticks >= Const.END_SYNC_WAIT_S) {
            if (_timer != null) { _timer.stop(); }
            var dlg = new WatchUi.Confirmation("Sync unvollständig – trotzdem beenden?");
            WatchUi.pushView(dlg, new QuitConfirmDelegate(), WatchUi.SLIDE_LEFT);
            return;
        }
        Uploader.syncAll();                       // weiter versuchen
        WatchUi.requestUpdate();
    }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, cy - 20, Graphics.FONT_MEDIUM, "Synchronisiere…",
            Graphics.TEXT_JUSTIFY_CENTER);
        dc.setColor(Graphics.COLOR_LT_GRAY, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, cy + 18, Graphics.FONT_TINY, "vor dem Beenden",
            Graphics.TEXT_JUSTIFY_CENTER);
        dc.drawText(cx, cy + 44, Graphics.FONT_XTINY,
            (Model.pendingMissions.size() + Model.pendingRest.size()).toString() + " offen",
            Graphics.TEXT_JUSTIFY_CENTER);
    }
}

class SendingDelegate extends WatchUi.BehaviorDelegate {
    function initialize() { BehaviorDelegate.initialize(); }
    // Waehrend des Sendens keine Aktionen — BACK ueberspringt das Warten
    function onBack() as Lang.Boolean {
        var dlg = new WatchUi.Confirmation("Sync unvollständig – trotzdem beenden?");
        WatchUi.pushView(dlg, new QuitConfirmDelegate(), WatchUi.SLIDE_LEFT);
        return true;
    }
}

class QuitConfirmDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) {
            System.exit();                        // Daten bleiben gepuffert
        } else {
            // Warten: zurueck zum Startbildschirm (Details: Sync-Seite, DOWN)
            WatchUi.switchToView(new StartView(), new StartDelegate(), WatchUi.SLIDE_DOWN);
        }
        return true;
    }
}

// ---------------------------------------------------------------------------
// Farbcodiertes Schnellmenue (Muster wie CprMenuView, Eintraege dynamisch)
// ---------------------------------------------------------------------------

class QuickMenuView extends WatchUi.View {

    var items as Lang.Array<Lang.Array> = [];   // [Label, Farbe, ID]
    var index as Lang.Number = 0;

    function initialize() {
        View.initialize();
        // Reihenfolge: Uebersicht ist beim Oeffnen vorausgewaehlt (index 0);
        // ein Schritt nach OBEN landet per Umlauf auf "Einsatztag beenden",
        // nach UNTEN folgen die Phasen 2, 3, 4 ... Endlos-Scrollen bleibt.
        items.add(["Einsatzübersicht", 0xFFFF00, :overview]);          // gelb
        for (var p = 2; p <= 9; p++) {
            items.add([p.toString() + " " + Const.PHASE_LABELS[p], 0xFFFFFF, p]);
        }
        if (Model.missionActive()) {
            items.add(["Einsatz abschließen", 0x00FF00, :finish]);     // gruen
        }
        items.add(["Einsatztag beenden", 0xFF0000, :endDay]);          // rot
    }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;
        var rowH = Ui.s(dc, 38);
        var n = items.size();

        for (var off = -2; off <= 2; off++) {
            var i = ((index + off) % n + n) % n;
            var item = items[i];
            var y = cy + off * rowH;
            if (off == 0) {
                dc.setColor(item[1] as Lang.Number, Graphics.COLOR_TRANSPARENT);
                dc.fillRoundedRectangle(Ui.s(dc, 14), y - rowH / 2 + Ui.s(dc, 2),
                    dc.getWidth() - Ui.s(dc, 28), rowH - Ui.s(dc, 4), Ui.s(dc, 8));
                dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y, Graphics.FONT_SMALL,
                    item[0] as Lang.String,
                    Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            } else {
                dc.setColor(item[1] as Lang.Number, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y, Graphics.FONT_TINY,
                    item[0] as Lang.String,
                    Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            }
        }
    }
}

class QuickMenuDelegate extends WatchUi.BehaviorDelegate {

    var _v as QuickMenuView;

    function initialize(v as QuickMenuView) {
        BehaviorDelegate.initialize();
        _v = v;
    }

    function onPreviousPage() as Lang.Boolean {           // UP: endlos
        var n = _v.items.size();
        _v.index = (_v.index - 1 + n) % n;
        WatchUi.requestUpdate();
        return true;
    }

    function onNextPage() as Lang.Boolean {               // DOWN: endlos
        _v.index = (_v.index + 1) % _v.items.size();
        WatchUi.requestUpdate();
        return true;
    }

    function onSelect() as Lang.Boolean {                 // START
        var id = _v.items[_v.index][2];
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        if (id == :endDay) {
            var dlg = new WatchUi.Confirmation("Einsatztag beenden?");
            WatchUi.pushView(dlg, new EndDayConfirmDelegate(), WatchUi.SLIDE_LEFT);
        } else if (id == :finish) {
            ClockDelegate.pushFinishConfirm();
        } else if (id == :overview) {
            pushMissionOverview();
        } else if (id instanceof Lang.Number) {
            Model.setPhase(id as Lang.Number);            // neuer Zeitstempel
        }
        return true;
    }

    function onBack() as Lang.Boolean {
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        return true;
    }

    // "Einsatzuebersicht": scrollbare Liste der Phasen-Zeitstempel
    static function pushMissionOverview() as Void {
        var menu = new WatchUi.Menu2({ :title => "Zeiten" });
        var src = (Model.mission != null) ? Model.mission
                : (Model.pendingMissions.size() > 0
                    ? Model.pendingMissions[Model.pendingMissions.size() - 1] : null);
        if (src == null) {
            menu.addItem(new WatchUi.MenuItem("Kein Einsatz", null, 0, null));
        } else {
            var phases = src["phases"] as Lang.Array<Lang.Array>;
            for (var i = 0; i < phases.size(); i++) {
                var p = phases[i];
                var hhmm = (p.size() > 4)
                    ? p[4] as Lang.String
                    : (p[1] as Lang.String).substring(11, 16);
                menu.addItem(new WatchUi.MenuItem(
                    hhmm + "  " + Const.PHASE_LABELS[p[0] as Lang.Number],
                    null, i, null));
            }
        }
        WatchUi.pushView(menu, new ListCloseDelegate(), WatchUi.SLIDE_LEFT);
    }
}

// Delegate fuer reine Anzeige-Listen: jede Auswahl/Back schliesst nur
class ListCloseDelegate extends WatchUi.Menu2InputDelegate {
    function initialize() { Menu2InputDelegate.initialize(); }
    function onSelect(item as WatchUi.MenuItem) as Void { }
    function onBack() as Void { WatchUi.popView(WatchUi.SLIDE_RIGHT); }
}
