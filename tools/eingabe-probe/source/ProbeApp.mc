// Eingabe-Probe — Werkzeug zum Ausmessen des Eingabeverhaltens eines Geraets.
// Kein Bestandteil der App. Anleitung und Messfolge: LIESMICH.md.
//
// Zwei Besonderheiten:
//   1. Jede Zeile traegt einen Millisekunden-Stempel seit App-Start.
//   2. onKeyPressed startet einen 1000-ms-Timer — exakt der Mechanismus, den
//      die Einsatzdoku fuer Langdruecke benutzt (Const.LONG_PRESS_MS).
//      Feuert er, erscheint "HALTE-TIMER".
//
// Entscheidende Ablesung:
//   HALTE-TIMER steht VOR dem KeyReleased  -> Langdruecke funktionieren.
//   HALTE-TIMER steht NACH dem KeyReleased -> KeyPressed kommt erst beim
//      Loslassen; auf diesem Geraet ist kein Langdruck moeglich.
//   Gar kein HALTE-TIMER                   -> die Taste wird abgefangen.
using Toybox.Application;
using Toybox.WatchUi;
using Toybox.Graphics;
using Toybox.System;
using Toybox.Timer;
using Toybox.Lang;

class ProbeApp extends Application.AppBase {

    function initialize() { AppBase.initialize(); }

    function onStart(state as Lang.Dictionary or Null) as Void {
        Probe.t0 = System.getTimer();
        Probe.deviceInfo();
    }

    function onStop(state as Lang.Dictionary or Null) as Void { }

    function getInitialView() as [WatchUi.Views] or [WatchUi.Views, WatchUi.InputDelegates] {
        return [new ProbeView(), new ProbeDelegate()];
    }
}

module Probe {

    const MAX_LINES = 8;
    const HOLD_MS = 1000;          // wie Const.LONG_PRESS_MS der echten App

    var lines as Lang.Array = [];
    var backCount as Lang.Number = 0;
    var t0 as Lang.Number = 0;

    // Millisekunden seit App-Start, rechtsbuendig auf 6 Stellen
    function stamp() as Lang.String {
        var ms = System.getTimer() - t0;
        var s = ms.toString();
        while (s.length() < 6) { s = " " + s; }
        return s;
    }

    function add(s as Lang.String) as Void {
        var line = stamp() + " ms  " + s;
        System.println(line);
        lines.add(line);
        if (lines.size() > MAX_LINES) {
            lines = lines.slice(lines.size() - MAX_LINES, null);
        }
        WatchUi.requestUpdate();
    }

    function deviceInfo() as Void {
        var d = System.getDeviceSettings();
        System.println("===== GERAET =====");
        System.println("partNumber    : " + d.partNumber);
        System.println("screen        : " + d.screenWidth.toString()
                       + " x " + d.screenHeight.toString());
        System.println("isTouchScreen : " + d.isTouchScreen.toString());
        var mv = d.monkeyVersion;
        System.println("monkeyVersion : " + mv[0].toString() + "."
                       + mv[1].toString() + "." + mv[2].toString());
        System.println("Halte-Timer   : " + HOLD_MS.toString() + " ms");
        System.println("==================");
    }
}

class ProbeView extends WatchUi.View {

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var w = dc.getWidth();
        var h = dc.getHeight();
        var cx = w / 2;

        dc.setColor(Graphics.COLOR_YELLOW, Graphics.COLOR_TRANSPARENT);
        dc.drawText(cx, h * 8 / 100, Graphics.FONT_XTINY, "Probe 2",
            Graphics.TEXT_JUSTIFY_CENTER);

        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        var y = h * 20 / 100;
        var step = h * 9 / 100;
        for (var i = 0; i < Probe.lines.size(); i++) {
            dc.drawText(cx, y, Graphics.FONT_XTINY,
                Probe.lines[i] as Lang.String, Graphics.TEXT_JUSTIFY_CENTER);
            y += step;
        }
    }
}

class ProbeDelegate extends WatchUi.BehaviorDelegate {

    private var _timer as Timer.Timer or Null = null;
    private var _heldKey as Lang.Number or Null = null;

    function initialize() { BehaviorDelegate.initialize(); }

    // ---- Halte-Timer, Nachbau des Mechanismus der echten App ---------------

    function onHoldFired() as Void {
        Probe.add("*** HALTE-TIMER gefeuert, Taste "
                  + (_heldKey == null ? "?" : _heldKey.toString()));
    }

    private function startHold(key as Lang.Number) as Void {
        _heldKey = key;
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.stop();
        _timer.start(method(:onHoldFired), Probe.HOLD_MS, false);
    }

    private function stopHold() as Void {
        if (_timer != null) { _timer.stop(); }
        _heldKey = null;
    }

    // ---- Roh-Ereignisse: Tasten -------------------------------------------

    function onKeyPressed(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        Probe.add("KeyPressed  " + k.toString() + "   -> Timer laeuft");
        startHold(k);
        return false;
    }

    function onKeyReleased(evt as WatchUi.KeyEvent) as Lang.Boolean {
        Probe.add("KeyReleased " + evt.getKey().toString());
        stopHold();
        return false;
    }

    function onKey(evt as WatchUi.KeyEvent) as Lang.Boolean {
        Probe.add("onKey       " + evt.getKey().toString());
        return false;
    }

    // ---- Roh-Ereignisse: Touch --------------------------------------------

    function onTap(evt as WatchUi.ClickEvent) as Lang.Boolean {
        var c = evt.getCoordinates();
        Probe.add("TAP  x=" + c[0].toString() + " y=" + c[1].toString());
        return false;
    }

    function onHold(evt as WatchUi.ClickEvent) as Lang.Boolean {
        var c = evt.getCoordinates();
        Probe.add("HOLD x=" + c[0].toString() + " y=" + c[1].toString());
        return false;
    }

    function onRelease(evt as WatchUi.ClickEvent) as Lang.Boolean {
        Probe.add("RELEASE");
        return false;
    }

    // Richtung: 0=hoch 1=rechts 2=runter 3=links
    function onSwipe(evt as WatchUi.SwipeEvent) as Lang.Boolean {
        Probe.add("SWIPE dir=" + evt.getDirection().toString());
        return false;
    }

    // ---- Behaviors ---------------------------------------------------------

    function onNextPage() as Lang.Boolean     { Probe.add(">> onNextPage");     return true; }
    function onPreviousPage() as Lang.Boolean { Probe.add(">> onPreviousPage"); return true; }
    function onNextMode() as Lang.Boolean     { Probe.add(">> onNextMode");     return true; }
    function onPreviousMode() as Lang.Boolean { Probe.add(">> onPreviousMode"); return true; }
    function onSelect() as Lang.Boolean       { Probe.add(">> onSelect");       return true; }
    function onMenu() as Lang.Boolean         { Probe.add(">> onMenu");         return true; }

    // Fuenfmal BACK beendet die Probe.
    function onBack() as Lang.Boolean {
        Probe.backCount += 1;
        Probe.add(">> onBack (" + Probe.backCount.toString() + "/5)");
        if (Probe.backCount >= 5) { System.exit(); }
        return true;
    }
}
