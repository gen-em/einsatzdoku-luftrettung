// Einsatzdoku — Reanimationslogik, app-weit (laeuft beim Navigieren weiter).
// Grosser Timer: 2:00-Countdown, vibriert bei 0:00 und BLEIBT stehen.
// Neustart: lang START oder Menuepunkt "Timer neu starten" — und automatisch
// bei Rhythmuskontrolle (lang DOWN bzw. Menue) und Defibrillation (Menue).
//
// Drei Zustaende:
//   active=false            keine Reanimation
//   active=true, paused=false   laeuft
//   active=true, paused=true    angehalten, Entscheidung offen
// Die Pause entsteht ueber "Rea BEENDEN": der Countdown steht, die Uebersicht
// oeffnet sich, und dort wird fortgesetzt oder endgueltig geschlossen. Die
// Gesamtdauer laeuft waehrend der Pause WEITER — sie ist die tatsaechlich
// verstrichene Reanimationszeit und darf nicht zu kurz dokumentiert werden.
// Die Pause ist ein reiner Bedienzustand und wird nicht uebertragen.
using Toybox.Timer;
using Toybox.Lang;
using Toybox.WatchUi;
using Toybox.Application.Storage;

// Rueckrufe brauchen eine Klasse als Traeger (method() gibt es nur auf Objekten,
// nicht auf Modulen). Diese Huelle reicht den Timer-Tick ans Modul weiter.
class CprCb {
    function initialize() {}
    function tick() as Void { Cpr.tick(); }
}

module Cpr {

    var active as Lang.Boolean = false;
    var paused as Lang.Boolean = false;       // angehalten, Entscheidung offen
    var startEpoch as Lang.Number = 0;        // Reanimationsbeginn
    var cycleEndEpoch as Lang.Number = 0;     // 0 = Countdown steht bei 0:00
    var _timer as Timer.Timer or Null = null;
    var _cb as CprCb or Null = null;
    var _alarmFired as Lang.Boolean = false;
    var _vibeMore as Lang.Number = 0;          // Zyklusende-Vibration Teil 2

    function start() as Void {
        if (active) { restartCycle(); return; }  // Sicherheitsnetz (regulaer oeffnet kurz START dann das Menue)
        active = true;
        paused = false;
        startEpoch = Util.epochNow();
        Model.resusStart();                      // legt eine NEUE Sitzung an
        restartCycle();                          // vibriert 2x (Startbestaetigung)
        _startTimer();
        _persist();
    }

    // "Rea BEENDEN" (Untermenue): haelt den Countdown an, laesst die Sitzung
    // aber offen. Entschieden wird auf der Uebersicht — fortsetzen oder
    // endgueltig schliessen. Bis dahin geht nichts verloren.
    function pause() as Void {
        if (!active || paused) { return; }
        paused = true;
        cycleEndEpoch = 0;                     // Countdown steht
        _vibeMore = 0;
        _persist();
        WatchUi.requestUpdate();
    }

    // "Rea fortsetzen": weiter mit frischem 2:00-Zyklus.
    function resume() as Void {
        if (!active || !paused) { return; }
        paused = false;
        restartCycle();                        // vibriert 2x (Bestaetigung)
        WatchUi.requestUpdate();
    }

    // "Rea beenden" (auf der Uebersicht): schliesst die Sitzung endgueltig.
    // Ein erneuter Start beginnt danach eine neue Reanimation.
    function stopRecording() as Void {
        if (!active) { return; }
        active = false;
        paused = false;
        cycleEndEpoch = 0;
        if (_timer != null) { _timer.stop(); }
        _persist();
        WatchUi.requestUpdate();
    }

    function _startTimer() as Void {
        if (_timer == null) { _timer = new Timer.Timer(); }
        if (_cb == null) { _cb = new CprCb(); }
        _timer.start(_cb.method(:tick), 1000, true);
    }

    function _persist() as Void {
        Storage.setValue("cpr", {
            "a" => active, "s" => startEpoch, "c" => cycleEndEpoch,
            "p" => paused
        });
    }

    // Nach App-/Uhren-Neustart: laufende Rea nahtlos wiederaufnehmen.
    // Der Countdown laeuft anhand der Epochen korrekt weiter; ist sein Ende
    // waehrend des Neustarts verstrichen, steht er wie vorgesehen auf 0:00.
    function restore() as Void {
        var s = Storage.getValue("cpr");
        if (s instanceof Lang.Dictionary && s["a"] == true) {
            active = true;
            paused = (s["p"] == true);         // Pausenzustand uebersteht Neustart
            startEpoch = s["s"] != null ? s["s"] : Util.epochNow();
            cycleEndEpoch = s["c"] != null ? s["c"] : 0;
            if (cycleEndEpoch > 0 && Util.epochNow() >= cycleEndEpoch) {
                cycleEndEpoch = 0;               // Ende verpasst -> steht bei 0:00
                _alarmFired = true;              // nicht nachtraeglich vibrieren
            }
            _startTimer();
        }
    }

    function restartCycle() as Void {
        if (active && paused) { return; }      // pausiert: Countdown bleibt stehen
        cycleEndEpoch = Util.epochNow() + Const.CPR_CYCLE_S;
        _alarmFired = false;
        _vibeMore = 0;
        if (active) {
            Util.vibrateTwice();               // Bestaetigung: 2:00 laeuft neu
            _persist();
        }
    }

    function stop() as Void {                  // bei Dienstende
        stopRecording();
    }

    function tick() as Void {
        if (!active) { return; }
        if (paused) { WatchUi.requestUpdate(); return; }   // Gesamtdauer laeuft weiter
        // Display waehrend der Rea dauerhaft hell: das Backlight wird jede
        // Sekunde neu angestossen, bevor der System-Timeout dimmen kann.
        if (Toybox.Attention has :backlight) {
            try { Toybox.Attention.backlight(true); } catch (ex) { }
        }
        if (_vibeMore > 0) {
            _vibeMore -= 1;
            if (_vibeMore == 0) { Util.vibrateTwice(); }   // Pulse 4+5
        }
        if (cycleEndEpoch > 0 && Util.epochNow() >= cycleEndEpoch && !_alarmFired) {
            _alarmFired = true;
            cycleEndEpoch = 0;                 // steht bei 0:00 bis Neustart
            Util.vibrateCycleEnd();            // Pulse 1-3 (Teil 2 im naechsten Tick)
            _vibeMore = 2;
        }
        WatchUi.requestUpdate();               // Timeranzeige aktualisieren
    }

    // Sekunden seit Beginn (kleiner Timer)
    function elapsedS() as Lang.Number {
        return active ? (Util.epochNow() - startEpoch) : 0;
    }

    // Restsekunden des Countdowns (grosser Timer); 0 = steht
    function cycleRemainingS() as Lang.Number {
        if (!active || paused || cycleEndEpoch == 0) { return 0; }
        var r = cycleEndEpoch - Util.epochNow();
        return r > 0 ? r : 0;
    }

    function markAdrenalin() as Void { Model.resusEvent(Const.R_ADRENALIN); }

    function markRhythmus() as Void {
        Model.resusEvent(Const.R_RHYTHMUS);
        restartCycle();                        // fachliche Kopplung (Anf. 1.4)
    }

    function markDefi() as Void {
        Model.resusEvent(Const.R_DEFI);
        restartCycle();                        // Defi setzt den Zyklus neu an
    }

    function markEvent(type as Lang.String) as Void { Model.resusEvent(type); }
}
