// Einsatzdoku — Eingabemodell, geraeteuebergreifend.
//
// Die Zielgeraete unterscheiden sich in zwei Achsen: fuenf oder drei Tasten,
// mit oder ohne Touch. Statt jede Oberflaeche verzweigen zu lassen, uebersetzt
// ActionDelegate die Roh-Eingaben einmal zentral in Aktionen. Die Delegates
// der Oberflaechen ueberschreiben nur noch die Aktionen, die sie brauchen.
//
// Aktion          fuenf Tasten (fenix6pro, fr945)   drei Tasten + Touch (venu3s)
// -------------------------------------------------------------------------
// PAGE_PREV       kurz UP                           Wischen hoch
// PAGE_NEXT       kurz DOWN                         Wischen runter
// SELECT_SHORT    kurz START                        kurz Action
// SELECT_LONG     lang START                        lang Action ODER lang Zurueck
// MARK_A          lang UP                           — (nur ueber Menue)
// MARK_B          lang DOWN                         — (nur ueber Menue)
// BACK            BACK                              kurz Zurueck, Wischen rechts
//
// Warum SELECT_LONG auf der Venu doppelt liegt: Das Handbuch der Venu 3 nennt
// ein Steuerungsmenue nach zwei Sekunden Halten der Action-Taste. Im Simulator
// trat es nicht auf, auf echter Hardware ist es ungeprueft. Faengt die Uhr den
// langen Action-Druck ab, bleibt die App ueber den langen Zurueck-Druck
// bedienbar. Beide Wege sind gegeneinander entprellt.
//
// Gemessene Grundlage aller Zuordnungen: docs/Geraete-Eingabe.md
using Toybox.WatchUi;
using Toybox.System;
using Toybox.Timer;
using Toybox.Lang;
using Toybox.Application.Properties;

module Input {

    // Hat das Geraet UP/DOWN? Zur Laufzeit nicht ermittelbar — Connect IQ
    // kennt keine solche Abfrage. Kommt darum zur Bauzeit aus
    // source-tasten5/ bzw. source-tasten3/ (monkey.jungle).
    function hasUpDown() as Lang.Boolean {
        return DeviceProfile.HAS_UP_DOWN;
    }

    // Touchbedienung aktiv? Auf Geraeten ohne UP/DOWN immer — ohne Touch
    // waeren sie unbedienbar, der Schalter waere dort ein Selbstmordknopf.
    // Auf Geraeten mit UP/DOWN und Touch (Fenix 7 und neuer) entscheidet die
    // App-Einstellung "touchEnabled"; Vorgabe ist an.
    function touchActive() as Lang.Boolean {
        var d = System.getDeviceSettings();
        if (!d.isTouchScreen) { return false; }
        if (!DeviceProfile.HAS_UP_DOWN) { return true; }
        var v = null;
        try { v = Properties.getValue("touchEnabled"); } catch (e) { v = null; }
        return (v == null) ? true : (v as Lang.Boolean);
    }

    // Bedienhinweise auf den Oberflaechen — je Profil andere Woerter
    function lSelect() as Lang.String     { return DeviceProfile.L_SELECT; }
    function lSelectHold() as Lang.String { return DeviceProfile.L_SELECT_HOLD; }
}

// ---------------------------------------------------------------------------
// Gemeinsame Basis aller Seiten-Delegates
// ---------------------------------------------------------------------------
//
// Lang-Druecke erkennt die App selbst: onKeyPressed startet einen Timer ueber
// Const.LONG_PRESS_MS, der noch waehrend des Haltens feuert. Das ist auf allen
// drei Geraeten gemessen und funktioniert (docs/Geraete-Eingabe.md).
//
// onKeyPressed gibt fuer verarbeitete Tasten TRUE zurueck und schluckt das
// Ereignis damit. Wichtig auf Touchgeraeten: Sonst erzeugte das System
// zusaetzlich onSelect — und onSelect entsteht dort auch durch eine
// Bildschirmberuehrung. Weil ActionDelegate onSelect bewusst nicht belegt,
// bleibt Tippen auf den Hauptseiten wirkungslos.
class ActionDelegate extends WatchUi.BehaviorDelegate {

    private var _timer as Timer.Timer or Null = null;
    private var _heldKey as Lang.Number or Null = null;
    private var _longFired as Lang.Boolean = false;
    private var _combo as Lang.Boolean = false;
    private var _lastLongMs as Lang.Number = -100000;
    private var _upDown as Lang.Boolean = false;

    // trackUpDown: Verarbeitet die Seite lange UP/DOWN-Druecke selbst
    // (nur die Reanimationsseite)? Dann laufen auch die kurzen Druecke ueber
    // diesen Weg. Sonst bleiben UP/DOWN beim System und kommen als
    // onNextPage/onPreviousPage zurueck — genau wie das Wischen der Venu.
    function initialize(trackUpDown as Lang.Boolean) {
        BehaviorDelegate.initialize();
        _upDown = trackUpDown && DeviceProfile.HAS_UP_DOWN;
    }

    // ---- Aktionen, von den Oberflaechen zu ueberschreiben -----------------

    function actPagePrev() as Lang.Boolean    { return true; }
    function actPageNext() as Lang.Boolean    { return true; }
    function actSelectShort() as Lang.Boolean { return true; }
    function actSelectLong() as Lang.Boolean  { return true; }
    function actMarkA() as Lang.Boolean       { return true; }
    function actMarkB() as Lang.Boolean       { return true; }
    function actBack() as Lang.Boolean        { return true; }

    // ---- Roh-Eingaben ------------------------------------------------------

    private function _tracks(k as Lang.Number) as Lang.Boolean {
        if (k == WatchUi.KEY_ENTER) { return true; }
        return _upDown && (k == WatchUi.KEY_UP || k == WatchUi.KEY_DOWN);
    }

    function onKeyPressed(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        // Zweite Taste waehrend der ersten: Das ist die Tastensperre der Uhr
        // (START + beliebige Taste) und kein Bedienwunsch. Alles schlucken.
        if (_heldKey != null && k != _heldKey) {
            _combo = true;
            if (_timer != null) { _timer.stop(); }
            return true;
        }
        if (!_tracks(k)) { return false; }
        _heldKey = k;
        _longFired = false;
        _combo = false;
        if (_timer == null) { _timer = new Timer.Timer(); }
        _timer.stop();
        _timer.start(method(:onHoldTimeout), Const.LONG_PRESS_MS, false);
        return true;
    }

    // Feuert nach 1 s — waehrend die Taste noch gedrueckt ist.
    function onHoldTimeout() as Void {
        if (_combo || _heldKey == null) { return; }
        _longFired = true;
        _lastLongMs = System.getTimer();
        if (_heldKey == WatchUi.KEY_UP)        { actMarkA(); }
        else if (_heldKey == WatchUi.KEY_DOWN) { actMarkB(); }
        else                                   { actSelectLong(); }
        WatchUi.requestUpdate();
    }

    function onKeyReleased(evt as WatchUi.KeyEvent) as Lang.Boolean {
        var k = evt.getKey();
        if (k != _heldKey) { return _heldKey != null; }
        if (_timer != null) { _timer.stop(); }
        _heldKey = null;
        if (_combo) { _combo = false; return true; }          // Tastensperre
        if (_longFired) { _longFired = false; return true; }  // lang: erledigt
        if (k == WatchUi.KEY_UP)   { return actPagePrev(); }
        if (k == WatchUi.KEY_DOWN) { return actPageNext(); }
        return actSelectShort();
    }

    // ---- Behaviors ---------------------------------------------------------

    // Quelle je nach Geraet: kurz UP/DOWN oder Wischen hoch/runter. Beides
    // liefert das System ueber dieselben Behaviors; eigener Wisch-Code ist
    // weder noetig noch moeglich (das Roh-Ereignis wird bei belegten Gesten
    // gar nicht erst zugestellt).
    function onPreviousPage() as Lang.Boolean { return actPagePrev(); }
    function onNextPage() as Lang.Boolean     { return actPageNext(); }

    // BACK-Taste oder Wischen nach rechts
    function onBack() as Lang.Boolean { return actBack(); }

    // onMenu ist geraeteabhaengig belegt:
    //   mit UP/DOWN  — langer UP-Druck (Fenix 6 Pro, FR945)
    //   ohne UP/DOWN — langer Zurueck-Druck (Venu 3s), zweiter Weg zu
    //                  SELECT_LONG neben dem langen Action-Druck
    function onMenu() as Lang.Boolean {
        if (!DeviceProfile.HAS_UP_DOWN) {
            // Entprellung gegen den eigenen Halte-Timer
            if (System.getTimer() - _lastLongMs < 400) { return true; }
            _lastLongMs = System.getTimer();
            return actSelectLong();
        }
        if (_longFired) { return true; }        // eigener Timer war schneller
        if (_upDown) { _longFired = true; return actMarkA(); }
        return true;                            // sonst schlucken
    }

    // onSelect bleibt bewusst unbelegt — siehe Kopfkommentar.
}
