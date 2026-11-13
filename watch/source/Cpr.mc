// NAdoku — Reanimationslogik, app-weit (laeuft beim Navigieren weiter).
// Grosser Timer: 2:00-Countdown; er erinnert bei 0:30 und 0:10, vibriert
// bei 0:00 und BLEIBT dort stehen. Neu startet ihn eine Rhythmuskontrolle
// (kurz START), eine Defibrillation (DOWN oder Menue) und das Fortsetzen
// nach einer Pause — einen Neustart ohne Eintrag gibt es seit Uhr 4.5.0
// nicht mehr (E-KG-07).
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
//
// VIBRATION (E-KG-11 bis -13): kurz bestaetigt eine Buchung — einmal ohne,
// zweimal mit Neustart des Countdowns; lang gehoert dem Countdown. Die
// Muster stehen in Util.reaKurz/reaLang, je eines in einem Aufruf.
using Toybox.Timer;
using Toybox.Lang;
using Toybox.System;
using Toybox.WatchUi;
using Toybox.Application.Storage;

// Rueckrufe brauchen eine Klasse als Traeger (method() gibt es nur auf Objekten,
// nicht auf Modulen). Diese Huelle reicht den Timer-Tick ans Modul weiter.
class CprCb {
    function initialize() {}
    function tick() as Void { Cpr.tick(); }
}

module Cpr {

    const SPERRE_MS = 5000;                   // E-KG-06, seit Uhr 4.6.0 5 s (E-KG-29)
    const ERINNERUNG_1_S = 30;                // E-KG-11
    const ERINNERUNG_2_S = 10;

    var active as Lang.Boolean = false;
    var paused as Lang.Boolean = false;       // angehalten, Entscheidung offen
    var startEpoch as Lang.Number = 0;        // Reanimationsbeginn
    var cycleEndEpoch as Lang.Number = 0;     // 0 = Countdown steht bei 0:00
    var _timer as Timer.Timer or Null = null;
    var _cb as CprCb or Null = null;
    var _alarmFired as Lang.Boolean = false;
    var _erinnert1 as Lang.Boolean = false;   // 0:30 schon gemeldet
    var _erinnert2 as Lang.Boolean = false;   // 0:10 schon gemeldet
    // Zuletzt gebucht, je Ereignisart (System.getTimer(), ms). Nur im
    // Speicher: Nach einem Neustart der App beginnt die Sperre neu, und das
    // ist hinzunehmen — sie faengt Doppeldruecke ab, keine Neustarts.
    var _zuletzt as Lang.Dictionary = {};

    function start() as Void {
        if (active) { return; }               // kurz START bucht dann die Rhythmuskontrolle
        active = true;
        paused = false;
        startEpoch = Util.epochNow();
        _zuletzt = {};
        Model.resusStart();                      // legt eine NEUE Sitzung an
        restartCycle();
        /* AUCH UNMITTELBAR NACH DEM BEGINN GILT DIE SPERRE (E-KG-06): Ein
         * zweiter Druck auf START in den naechsten 5 s ist der Doppeldruck,
         * keine Rhythmuskontrolle. */
        _zuletzt[Const.R_RHYTHMUS] = System.getTimer();
        Util.reaKurz(2);                         // Countdown laeuft
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
        _persist();
        WatchUi.requestUpdate();
    }

    // "Rea fortsetzen": weiter mit frischem 2:00-Zyklus.
    function resume() as Void {
        if (!active || !paused) { return; }
        paused = false;
        restartCycle();
        Util.reaKurz(2);                       // Countdown laeuft neu
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
        // s. Hinweis in Uploader._send(): lokal halten, in einem Zug anlegen.
        var tmr = _timer;
        if (tmr == null) { tmr = new Timer.Timer(); _timer = tmr; }
        var cb = _cb;
        if (cb == null) { cb = new CprCb(); _cb = cb; }
        tmr.start(cb.method(:tick), 1000, true);
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
    // Eine Erinnerung, deren Zeitpunkt im Neustart lag, kommt nicht nach.
    function restore() as Void {
        var s = Storage.getValue("cpr");
        if (s instanceof Lang.Dictionary && true.equals(s["a"])) {
            active = true;
            paused = true.equals(s["p"]);      // Pausenzustand uebersteht Neustart
            startEpoch = s["s"] != null ? s["s"] as Lang.Number : Util.epochNow();
            cycleEndEpoch = s["c"] != null ? s["c"] as Lang.Number : 0;
            if (cycleEndEpoch > 0 && Util.epochNow() >= cycleEndEpoch) {
                cycleEndEpoch = 0;               // Ende verpasst -> steht bei 0:00
                _alarmFired = true;              // nicht nachtraeglich vibrieren
            }
            var r = cycleRemainingS();
            _erinnert1 = cycleEndEpoch == 0 || r <= ERINNERUNG_1_S;
            _erinnert2 = cycleEndEpoch == 0 || r <= ERINNERUNG_2_S;
            _startTimer();
        }
    }

    /* Den Countdown neu ansetzen — OHNE Vibration: Wer ihn neu startet,
     * bestaetigt mit seinem eigenen Muster (start, resume, buchen). */
    function restartCycle() as Void {
        if (!active || paused) { return; }     // pausiert: Countdown bleibt stehen
        cycleEndEpoch = Util.epochNow() + Const.CPR_CYCLE_S;
        _alarmFired = false;
        _erinnert1 = false;
        _erinnert2 = false;
        _persist();
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
        if (cycleEndEpoch > 0) {
            var r = cycleEndEpoch - Util.epochNow();
            if (r <= 0) {
                if (!_alarmFired) {
                    _alarmFired = true;
                    _erinnert1 = true;
                    _erinnert2 = true;
                    cycleEndEpoch = 0;         // steht bei 0:00 bis Neustart
                    Util.reaLang(3);
                }
            } else if (r <= ERINNERUNG_2_S && !_erinnert2) {
                _erinnert2 = true;
                _erinnert1 = true;
                Util.reaLang(2);
            } else if (r <= ERINNERUNG_1_S && !_erinnert1) {
                _erinnert1 = true;
                Util.reaLang(1);
            }
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

    /* EIN EREIGNIS BUCHEN — der eine Weg fuer Tasten und Menue.
     *
     * Nur bei laufender Rea; in der Pause steht der Countdown, und gebucht
     * wird nichts. DIE SPERRE (E-KG-06): Dieselbe Art innerhalb von 5 s
     * wird nicht noch einmal gebucht, gleich ueber welchen Weg; eine andere
     * Art geht sofort. Ein abgewiesener Druck gibt kein Zeichen (E-KG-14).
     * Gibt zurueck, ob gebucht wurde. */
    function buchen(type as Lang.String) as Lang.Boolean {
        if (!active || paused) { return false; }
        var jetzt = System.getTimer();
        var vorher = _zuletzt[type];
        if (vorher instanceof Lang.Number && jetzt - (vorher as Lang.Number) < SPERRE_MS) {
            return false;
        }
        _zuletzt[type] = jetzt;
        Model.resusEvent(type);
        if (Const.R_RHYTHMUS.equals(type) || Const.R_DEFI.equals(type)) {
            restartCycle();                    // fachliche Kopplung (Anf. 1.4)
            Util.reaKurz(2);
        } else {
            Util.reaKurz(1);
        }
        WatchUi.requestUpdate();
        return true;
    }
}
