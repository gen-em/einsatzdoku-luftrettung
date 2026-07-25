// Einsatzdoku — Oberflaeche 3: Reanimation (Anforderungen 1.4)
//
// Tasten: kurz UP/DOWN Navigation
//         kurz START: Rea beginnen; laeuft sie schon, oeffnet sich das Menue
//         lang UP Adrenalin | lang DOWN Rhythmuskontrolle
//         lang START: 2:00-Countdown neu starten (auch als erster Menuepunkt)
//         BACK verlaesst die Oberflaeche
// Lang-Druecke werden manuell ueber onKeyPressed/onKeyReleased erkannt.
// Das Untermenue ist selbst gezeichnet (Menu2 kann keine Farben), in Aufbau
// und Massen identisch mit dem Schnellmenue der Hauptseite (QuickMenuView),
// und enthaelt "Rea BEENDEN": schliesst die laufende Rea; erneuter Start
// beginnt eine neue Reanimation (mehrere pro Einsatz).
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.System;
using Toybox.Timer;
using Toybox.Lang;

class CprView extends WatchUi.View {

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        var w = dc.getWidth();
        var h = dc.getHeight();
        var cx = w / 2;
        var cy = h / 2;

        // Heller Grund (transflektives Display: bei Tageslicht am besten lesbar)
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_WHITE);
        dc.clear();

        if (!Cpr.active) {
            dc.setColor(Graphics.COLOR_RED, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, cy - 40, Graphics.FONT_MEDIUM, "Reanimation",
                Graphics.TEXT_JUSTIFY_CENTER);
            dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, cy + 4, Graphics.FONT_SMALL, "START = Beginn",
                Graphics.TEXT_JUSTIFY_CENTER);
            return;
        }

        // 1) Kopfbalken: Gesamtdauer der Reanimation
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.fillRectangle(0, 0, w, 56);          // bis an die Oberkante
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, 30, Graphics.FONT_NUMBER_MILD, _mmss(Cpr.elapsedS()),
            Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);

        // 2) Grosser 2:00-Countdown, mittig
        var r = Cpr.cycleRemainingS();
        dc.setColor(r == 0 ? Graphics.COLOR_RED : Graphics.COLOR_BLACK,
            Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, cy - 6, Graphics.FONT_NUMBER_THAI_HOT, _mmss(r),
            Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);

        // 3) Fortschrittsbalken: fuellt sich im Lauf des Zyklus von links
        //    nach rechts (leer bei Zyklusstart, voll bei 0:00)
        var bx = 34;
        var bw = w - 68;
        var by = cy + 44;
        var bh = 20;
        var passed = Const.CPR_CYCLE_S - r;
        if (passed < 0) { passed = 0; }
        if (passed > Const.CPR_CYCLE_S) { passed = Const.CPR_CYCLE_S; }
        var fill = (bw * passed) / Const.CPR_CYCLE_S;
        if (fill > 0) {
            dc.setColor(Graphics.COLOR_ORANGE, Graphics.COLOR_TRANSPARENT);
            dc.fillRectangle(bx, by, fill, bh);
        }
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
        dc.setPenWidth(2);
        dc.drawRectangle(bx, by, bw, bh);      // Rahmen: leerer Balken sichtbar
        dc.setPenWidth(1);

        // 4) Trennlinie und aktuelle Uhrzeit
        dc.drawLine(30, by + bh + 14, w - 30, by + bh + 14);
        var t = System.getClockTime();
        dc.drawText(cx, by + bh + 34, Graphics.FONT_NUMBER_MILD,
            t.hour.format("%02d") + ":" + t.min.format("%02d"),
            Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
    }

    function _mmss(s as Lang.Number) as Lang.String {
        return (s / 60).format("%d") + ":" + (s % 60).format("%02d");
    }
}

class CprDelegate extends WatchUi.BehaviorDelegate {

    var _timer as Timer.Timer or Null = null;
    var _heldKey = null;                       // gerade gehaltene Taste
    var _longFired as Lang.Boolean = false;    // lange Aktion schon ausgeloest?
    var _combo as Lang.Boolean = false;        // zweite Taste dazugekommen (Tastensperre)

    function initialize() { BehaviorDelegate.initialize(); }

    function onKeyPressed(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        // Zweite Taste, waehrend schon eine gehalten wird: Tastensperre der
        // Uhr — lange Aktion verwerfen (siehe ClockDelegate).
        if (_heldKey != null && k != _heldKey) {
            _combo = true;
            if (_timer != null) { _timer.stop(); }
            return true;
        }
        if (k == WatchUi.KEY_UP || k == WatchUi.KEY_DOWN || k == WatchUi.KEY_ENTER) {
            _heldKey = k;
            _longFired = false;
            _combo = false;
            if (_timer == null) { _timer = new Timer.Timer(); }
            _timer.start(method(:onHoldTimeout), Const.LONG_PRESS_MS, false);
            return true;
        }
        return false;
    }

    // Feuert nach 1 s Halten — waehrend die Taste noch gedrueckt ist
    function onHoldTimeout() as Void {
        if (_combo) { return; }
        _longFired = true;
        if (_heldKey == WatchUi.KEY_UP)         { Cpr.markAdrenalin(); }
        else if (_heldKey == WatchUi.KEY_DOWN)  { Cpr.markRhythmus(); }
        else if (_heldKey == WatchUi.KEY_ENTER) {
            // Countdown-Neustart nur bei laufender Rea (vibriert zweimal)
            if (Cpr.active) { Cpr.restartCycle(); }
        }
        WatchUi.requestUpdate();
    }

    function onKeyReleased(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        if (k != _heldKey) { return _heldKey != null; }
        if (_timer != null) { _timer.stop(); }
        _heldKey = null;
        if (_combo) { _combo = false; return true; }           // Tastensperre: nichts tun
        if (_longFired) { _longFired = false; return true; }   // lang: schon erledigt
        // kurz:
        if (k == WatchUi.KEY_UP)        { Nav.go(-1); }
        else if (k == WatchUi.KEY_DOWN) { Nav.go(1); }
        else if (Cpr.active)            { _pushMenu(); }   // START: Menue
        else                            { Cpr.start(); }   // START: Rea-Beginn
        return true;
    }

    // Falls das System lang-UP zusaetzlich als onMenu meldet: nicht doppeln
    function onMenu() as Lang.Boolean {
        if (!_longFired && Cpr.active) {
            _longFired = true;
            Cpr.markAdrenalin();
        }
        return true;
    }

    function onKey(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        return (k == WatchUi.KEY_UP || k == WatchUi.KEY_DOWN || k == WatchUi.KEY_ENTER);
    }

    function onBack() as Lang.Boolean { Nav.goTo(:clock); return true; }

    function _pushMenu() as Void {
        var v = new CprMenuView();
        WatchUi.pushView(v, new CprMenuDelegate(v), WatchUi.SLIDE_LEFT);
    }
}

// ---------------------------------------------------------------------------
// Selbst gezeichnetes, farbcodiertes Untermenue
// ---------------------------------------------------------------------------

class CprMenuView extends WatchUi.View {

    // [Label, Farbe, ID] — Aufbau und Masse wie das Schnellmenue der
    // Hauptseite (QuickMenuView); die Ereignisfarben bleiben erhalten.
    static const ITEMS = [
        ["Timer neu starten",  0x00FFFF, :restart],              // cyan
        ["Rhythmuskontrolle",  0xFFFF00, Const.R_RHYTHMUS],      // gelb
        ["Defibrillation",     0xFFAA00, Const.R_DEFI],          // bernstein
        ["Adrenalin",          0xFF55AA, Const.R_ADRENALIN],     // pink
        ["Amiodaron",          0xAA00FF, Const.R_AMIODARON],     // violett
        ["Zugang",             0xFF00FF, Const.R_ZUGANG],        // magenta
        ["Intubation",         0x00AAFF, Const.R_INTUBATION],    // blau
        ["Sonographie",        0x00FFAA, Const.R_SONO],          // tuerkis
        ["ROSC",               0x00FF00, Const.R_ROSC],          // gruen
        ["Tod",                0xAAAAAA, Const.R_TOD],           // grau
        ["Übersicht",          0xFFFFFF, :overview],             // weiss
        ["Rea BEENDEN",        0xFF0000, :stopRec]               // rot
    ];

    var index as Lang.Number = 0;

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;
        var rowH = 38;
        var n = ITEMS.size();

        for (var off = -2; off <= 2; off++) {
            var i = ((index + off) % n + n) % n;
            var item = ITEMS[i];
            var y = cy + off * rowH;
            if (off == 0) {
                dc.setColor(item[1] as Lang.Number, Graphics.COLOR_TRANSPARENT);
                dc.fillRoundedRectangle(14, y - rowH / 2 + 2,
                    dc.getWidth() - 28, rowH - 4, 8);
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

class StopRecConfirmDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) { Cpr.stopRecording(); }
        return true;
    }
}

class CprMenuDelegate extends WatchUi.BehaviorDelegate {

    var _v as CprMenuView;

    function initialize(v as CprMenuView) {
        BehaviorDelegate.initialize();
        _v = v;
    }

    function onPreviousPage() as Lang.Boolean {           // UP: endlos
        var n = CprMenuView.ITEMS.size();
        _v.index = (_v.index - 1 + n) % n;
        WatchUi.requestUpdate();
        return true;
    }

    function onNextPage() as Lang.Boolean {               // DOWN: endlos
        _v.index = (_v.index + 1) % CprMenuView.ITEMS.size();
        WatchUi.requestUpdate();
        return true;
    }

    function onSelect() as Lang.Boolean {                 // START
        var id = CprMenuView.ITEMS[_v.index][2];
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        if (id == :restart) {
            Cpr.restartCycle();                           // Countdown auf 2:00
        } else if (id == :overview) {
            _pushOverview();
        } else if (id == :stopRec) {
            // Sicherheitsabfrage vor dem Beenden der Rea-Aufzeichnung
            var dlg = new WatchUi.Confirmation("Rea beenden?");
            WatchUi.pushView(dlg, new StopRecConfirmDelegate(), WatchUi.SLIDE_LEFT);
        } else if (Const.R_RHYTHMUS.equals(id)) {
            Cpr.markRhythmus();                           // inkl. Countdown-Reset
        } else if (Const.R_DEFI.equals(id)) {
            Cpr.markDefi();                               // inkl. Countdown-Reset
        } else if (Const.R_ADRENALIN.equals(id)) {
            Cpr.markAdrenalin();
        } else {
            Cpr.markEvent(id as Lang.String);
        }
        return true;
    }

    function onBack() as Lang.Boolean {
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        return true;
    }

    // Uebersicht der aktuellen (letzten) Rea-Sitzung als scrollbare Liste
    function _pushOverview() as Void {
        var menu = new WatchUi.Menu2({ :title => "Rea-Zeiten" });
        var sess = Model.currentResus();
        if (sess == null) {
            menu.addItem(new WatchUi.MenuItem("Keine Reanimation", null, 0, null));
        } else {
            menu.addItem(new WatchUi.MenuItem(
                (sess["startLocal"] as Lang.String) + "  Beginn", null, -1, null));
            var evs = sess["events"] as Lang.Array;
            for (var i = 0; i < evs.size(); i++) {
                var ev = evs[i];
                menu.addItem(new WatchUi.MenuItem(
                    (ev[2] as Lang.String) + "  " + Const.RESUS_LABELS[ev[0]],
                    null, i, null));
            }
        }
        WatchUi.pushView(menu, new ListCloseDelegate(), WatchUi.SLIDE_LEFT);
    }
}
