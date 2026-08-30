// Einsatzdoku — Oberflaeche 3: Reanimation (Anforderungen 1.4)
//
// Tasten: kurz UP/DOWN Navigation
//         kurz START  ohne laufende Rea: Rea beginnen
//                     mit laufender Rea: Untermenue oeffnen
//         lang START  mit laufender Rea: 2:00-Countdown neu starten
//                     ohne laufende Rea: ohne Funktion
//         lang UP Adrenalin | lang DOWN Rhythmuskontrolle
//         BACK verlaesst die Oberflaeche
// Der haeufigste Griff unter Reanimationsbedingungen ist das Dokumentieren
// eines Ereignisses — deshalb liegt das Untermenue auf dem kurzen Druck.
// Auf Geraeten ohne UP/DOWN sind Adrenalin und Rhythmuskontrolle nur ueber
// das Untermenue erreichbar (docs/Geraete-Eingabe.md).
//
// Aufbau der Seite: oberes und unteres Feld je 25 % der Displayhoehe, das
// mittlere 50 %. Jedes Feld traegt seinen Inhalt vertikal zentriert.
//
// Das Untermenue ist selbst gezeichnet (Menu2 kann keine Farben) und enthaelt
// "Rea BEENDEN": haelt die Rea an und oeffnet die Uebersicht, wo fortgesetzt
// oder endgueltig geschlossen wird.
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

        // Heller Grund (transflektives Display: bei Tageslicht am besten lesbar)
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_WHITE);
        dc.clear();

        if (!Cpr.active) {
            var hT = dc.getFontHeight(Graphics.FONT_MEDIUM);
            var hH = dc.getFontHeight(Graphics.FONT_SMALL);
            var g  = Ui.s(dc, 10);
            var y0 = (h - (hT + g + hH)) / 2;
            dc.setColor(Ui.ROT, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y0, Graphics.FONT_MEDIUM, "Reanimation",
                Graphics.TEXT_JUSTIFY_CENTER);
            dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, y0 + hT + g, Graphics.FONT_SMALL,
                Input.lSelect() + " = Beginn", Graphics.TEXT_JUSTIFY_CENTER);
            return;
        }

        var topH = Ui.pct(dc, 25);          // oberes Feld
        var midH = Ui.pct(dc, 50);          // mittleres Feld
        var midY = topH;
        var botY = topH + midH;
        var botH = h - botY;

        // 1) Oberes Feld: Kopfbalken mit der Gesamtdauer der Reanimation
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.fillRectangle(0, 0, w, topH);
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
        // Nicht in der Feldmitte: Oben verengt sich das runde Display, dort
        // wirkt mittig zu hoch und zu nah am Rand. 62 % des Feldes sitzt besser.
        dc.drawText(cx, (topH * 62) / 100, Graphics.FONT_NUMBER_MILD,
            _mmss(Cpr.elapsedS()),
            Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);

        // 2) Mittleres Feld: Countdown und Fortschrittsbalken als ein Block,
        //    darin vertikal zentriert.
        var bh = Ui.s(dc, 20);                       // Balkenhoehe
        var gap = Ui.s(dc, 18);                      // Countdown -> Balken
        // Sichtbare Ziffernhoehe, nicht die Schriftbox: sonst zoege die leere
        // Unterlaenge den ganzen Block nach oben.
        var hCd = Ui.numH(dc, Graphics.FONT_NUMBER_THAI_HOT);
        var blockY = midY + (midH - (hCd + gap + bh)) / 2;

        if (Cpr.paused) {
            // FONT_LARGE, nicht FONT_NUMBER_*: Die Ziffernschriften enthalten
            // ausschliesslich Zahlen, Doppelpunkt und Punkt. Buchstaben
            // erscheinen dort als leere Kaestchen.
            dc.setColor(Ui.BLAU, Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, blockY + hCd / 2, Graphics.FONT_LARGE,
                "PAUSE", Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
        } else {
            var r = Cpr.cycleRemainingS();
            dc.setColor(r == 0 ? Ui.ROT : Graphics.COLOR_BLACK,
                Graphics.COLOR_TRANSPARENT);
            dc.drawText(cx, blockY, Graphics.FONT_NUMBER_THAI_HOT,
                _mmss(r), Graphics.TEXT_JUSTIFY_CENTER);
        }

        // Fortschrittsbalken: fuellt sich im Lauf des Zyklus von links nach
        // rechts (leer bei Zyklusstart, voll bei 0:00)
        var bx = Ui.s(dc, 34);
        var bw = w - 2 * bx;
        var by = blockY + hCd + gap;
        if (!Cpr.paused) {
            var rem = Cpr.cycleRemainingS();
            var passed = Const.CPR_CYCLE_S - rem;
            if (passed < 0) { passed = 0; }
            if (passed > Const.CPR_CYCLE_S) { passed = Const.CPR_CYCLE_S; }
            var fill = (bw * passed) / Const.CPR_CYCLE_S;
            if (fill > 0) {
                dc.setColor(Ui.BLAU, Graphics.COLOR_TRANSPARENT);
                dc.fillRectangle(bx, by, fill, bh);
            }
        }
        dc.setColor(Cpr.paused ? Graphics.COLOR_DK_GRAY : Graphics.COLOR_BLACK,
            Graphics.COLOR_TRANSPARENT);
        dc.setPenWidth(Ui.s(dc, 2));
        dc.drawRectangle(bx, by, bw, bh);           // Rahmen: leerer Balken sichtbar
        dc.setPenWidth(1);

        // 3) Unteres Feld: nur die Uhrzeit. Eine Trennlinie braucht es nicht —
        // der Fortschrittsbalken darueber trennt bereits sichtbar genug.
        // Etwas oberhalb der Feldmitte, weil das runde Display unten zulaeuft.
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
        var t = System.getClockTime();
        dc.drawText(cx, botY + (botH * 42) / 100, Graphics.FONT_NUMBER_MILD,
            t.hour.format("%02d") + ":" + t.min.format("%02d"),
            Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
    }

    function _mmss(s as Lang.Number) as Lang.String {
        return (s / 60).format("%d") + ":" + (s % 60).format("%02d");
    }
}

class CprDelegate extends ActionDelegate {

    // true: lange UP/DOWN-Druecke selbst verarbeiten (Adrenalin, Rhythmus)
    function initialize() { ActionDelegate.initialize(true); }

    function actPagePrev() as Lang.Boolean { Nav.go(-1); return true; }
    function actPageNext() as Lang.Boolean { Nav.go(1); return true; }

    function actSelectShort() as Lang.Boolean {
        if (Cpr.active) { _pushMenu(); }          // Ereignis dokumentieren
        else            { Cpr.start(); }          // Reanimation beginnen
        return true;
    }

    function actSelectLong() as Lang.Boolean {
        if (Cpr.active && !Cpr.paused) { Cpr.restartCycle(); }   // sonst ohne Funktion
        return true;
    }

    function actMarkA() as Lang.Boolean {
        if (Cpr.active && !Cpr.paused) { Cpr.markAdrenalin(); }
        return true;
    }

    function actMarkB() as Lang.Boolean {
        if (Cpr.active && !Cpr.paused) { Cpr.markRhythmus(); }
        return true;
    }

    function actBack() as Lang.Boolean { Nav.goTo(:clock); return true; }

    function _pushMenu() as Void {
        var v = new CprMenuView();
        WatchUi.pushView(v, new CprMenuDelegate(v), WatchUi.SLIDE_LEFT);
    }
}

// ---------------------------------------------------------------------------
// Selbst gezeichnetes, farbcodiertes Untermenue
// ---------------------------------------------------------------------------

class CprMenuView extends WatchUi.View {

    // [Label, Farbe, ID] — Reihenfolge und Farben wie im Rea-Protokoll.
    // Das Menue oeffnet auf "Timer neu starten". Ein Schritt nach OBEN landet
    // per Umlauf auf "Übersicht", zwei Schritte auf "Rea BEENDEN".
    // "Timer neu starten" ist weiss (reiner Timer-Befehl), "Übersicht" blau —
    // sonst waeren die beiden Nicht-Ereignisse an der Umlaufgrenze nicht
    // auseinanderzuhalten. Die Farben bleiben den Ereignissen vorbehalten.
    static const ITEMS = [
        ["Timer neu starten", 0xFFFFFF, :restart],           // weiss
        ["Rhythmuskontrolle", 0xFFFF00, Const.R_RHYTHMUS],   // gelb
        ["Defibrillation",    0xFFAA00, Const.R_DEFI],       // bernstein
        ["Adrenalin",         0xFF55AA, Const.R_ADRENALIN],  // pink
        ["Amiodaron",         0xAA00FF, Const.R_AMIODARON],  // violett
        ["Zugang",            0xFF00FF, Const.R_ZUGANG],     // magenta
        ["Intubation",        0x00AAFF, Const.R_INTUBATION], // blau
        ["Sonographie",       0x00FFAA, Const.R_SONO],       // tuerkis
        ["ROSC",              0x00FF00, Const.R_ROSC],       // gruen
        ["Tod",               0xAAAAAA, Const.R_TOD],        // grau
        ["Rea BEENDEN",       0xFF0000, :stopRec],           // rot
        ["Übersicht",         0x4280E5, :overview]           // Markenblau
    ];

    var index as Lang.Number = 0;

    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;
        // Darstellung wie das Schnellmenue der Hauptseite (QuickMenuView):
        // gleiche Zeilenhoehe, fuenf sichtbare Zeilen, gefuellte Auswahl.
        // Ein einheitliches Menuebild spart im Einsatz Umdenken.
        var rowH = Ui.s(dc, 38);
        var n = ITEMS.size();

        // 5 Zeilen: 2 davor, Auswahl, 2 danach — endlos (Modulo)
        for (var off = -2; off <= 2; off++) {
            var i = ((index + off) % n + n) % n;
            var item = ITEMS[i];
            var y = cy + off * rowH;
            var col = item[1] as Lang.Number;
            var label = item[0] as Lang.String;
            if (off == 0) {
                // Auswahl: gefuelltes Feld, schwarzer Text
                var pad = Ui.s(dc, 14);
                var boxW = dc.getWidth() - 2 * pad;
                // Sicherung fuer lange Begriffe ("Rhythmuskontrolle"): faellt
                // eine Stufe zurueck, statt am Feldrand abgeschnitten zu werden
                var font = Graphics.FONT_SMALL;
                if (dc.getTextWidthInPixels(label, font) > boxW - Ui.s(dc, 12)) {
                    font = Graphics.FONT_TINY;
                }
                dc.setColor(col, Graphics.COLOR_TRANSPARENT);
                dc.fillRoundedRectangle(pad, y - rowH / 2 + Ui.s(dc, 2),
                    boxW, rowH - Ui.s(dc, 4), Ui.s(dc, 8));
                dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y, font, label,
                    Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            } else {
                dc.setColor(col, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y, Graphics.FONT_TINY, label,
                    Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            }
        }
    }
}

class CprMenuDelegate extends WatchUi.BehaviorDelegate {

    var _v as CprMenuView;

    function initialize(v as CprMenuView) {
        BehaviorDelegate.initialize();
        _v = v;
    }

    function onPreviousPage() as Lang.Boolean {           // UP-Taste bzw. Wischen RUNTER
        var n = CprMenuView.ITEMS.size();
        _v.index = (_v.index - 1 + n) % n;
        WatchUi.requestUpdate();
        return true;
    }

    function onNextPage() as Lang.Boolean {               // DOWN-Taste bzw. Wischen HOCH
        _v.index = (_v.index + 1) % CprMenuView.ITEMS.size();
        WatchUi.requestUpdate();
        return true;
    }

    function onSelect() as Lang.Boolean {                 // START bzw. Action
        var id = CprMenuView.ITEMS[_v.index][2];
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        if (id == :restart) {
            if (Cpr.active && !Cpr.paused) { Cpr.restartCycle(); }
        } else if (id == :overview) {
            pushResusOverview();
        } else if (id == :stopRec) {
            // Kein Bestaetigungsdialog mehr: anhalten und die Uebersicht
            // zeigen. Dort wird fortgesetzt oder endgueltig geschlossen —
            // mit den dokumentierten Zeiten vor Augen.
            Cpr.pause();
            pushResusOverview();
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

    // Uebersicht der aktuellen (letzten) Rea-Sitzung. Selbst gezeichnet wie die
    // uebrigen Menues — das Systemmenue kann weder Farben noch Trennbalken.
    static function pushResusOverview() as Void {
        var v = new ResusOverviewView();
        WatchUi.pushView(v, new ResusOverviewDelegate(v), WatchUi.SLIDE_LEFT);
    }
}

// ---------------------------------------------------------------------------
// Rea-Uebersicht: Entscheidungen oben, darunter die Zeitstempel
// ---------------------------------------------------------------------------
//
// Ist die Reanimation pausiert, stehen "Rea beenden" und "Rea fortsetzen" ganz
// oben — die Entscheidung faellt damit mit den dokumentierten Zeiten vor Augen.
// Ein schmaler Trennbalken schneidet die Entscheidungen von den Zeiten ab. Er
// ist nicht anwaehlbar; das Blaettern ueberspringt ihn.
class ResusOverviewView extends WatchUi.View {

    var items as Lang.Array<Lang.Array> = [];      // [Label, Farbe, ID]
    var index as Lang.Number = 0;

    function initialize() {
        View.initialize();
        if (Cpr.active && Cpr.paused) {
            items.add(["Rea beenden",    0xFF0000, :finish]);   // rot
            items.add(["Rea fortsetzen", 0x00FF00, :resume]);   // gruen
            items.add(["Zeiten",         0xAAAAAA, :sep]);
        }
        var sess = Model.currentResus();
        if (sess == null) {
            items.add(["Keine Reanimation", 0xFFFFFF, :none]);
        } else {
            items.add([(sess["startLocal"] as Lang.String) + "  Beginn",
                       0xFFFFFF, :none]);
            var evs = sess["events"] as Lang.Array<Lang.Array>;
            for (var i = 0; i < evs.size(); i++) {
                var ev = evs[i];
                items.add([(ev[2] as Lang.String) + "  " + Const.RESUS_LABELS[ev[0]],
                           0xFFFFFF, :none]);
            }
        }
        // Zweiter Trennbalken ans Listenende: Die Liste laeuft um, hinter dem
        // letzten Zeitstempel folgt wieder "Rea beenden". Ohne Balken stiessen
        // Zeiten und Entscheidungen dort unvermittelt aneinander.
        if (Cpr.active && Cpr.paused) {
            items.add(["Aktionen", 0xAAAAAA, :sep]);
        }
    }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_BLACK);
        dc.clear();
        var cx = dc.getWidth() / 2;
        var cy = dc.getHeight() / 2;
        var rowH = Ui.s(dc, 38);
        var pad  = Ui.s(dc, 14);
        var boxW = dc.getWidth() - 2 * pad;
        var n = items.size();

        for (var off = -2; off <= 2; off++) {
            var i = ((index + off) % n + n) % n;
            var item = items[i];
            var y = cy + off * rowH;
            var col = item[1] as Lang.Number;
            var label = item[0] as Lang.String;

            if (item[2] == :sep) {
                // Trennbalken: halbe Zeilenhoehe, dunkel gefuellt
                var sh = rowH / 2;
                dc.setColor(Graphics.COLOR_DK_GRAY, Graphics.COLOR_TRANSPARENT);
                dc.fillRectangle(pad, y - sh / 2, boxW, sh);
                dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y, Graphics.FONT_XTINY, label,
                    Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            } else if (off == 0) {
                // Auswahl: gefuelltes Feld, schwarzer Text
                dc.setColor(col, Graphics.COLOR_TRANSPARENT);
                dc.fillRoundedRectangle(pad, y - rowH / 2 + Ui.s(dc, 2),
                    boxW, rowH - Ui.s(dc, 4), Ui.s(dc, 8));
                dc.setColor(Graphics.COLOR_BLACK, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y,
                    Ui.fitFont(dc, label, y - rowH / 2, rowH,
                        [Graphics.FONT_SMALL, Graphics.FONT_TINY, Graphics.FONT_XTINY]),
                    label, Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            } else {
                dc.setColor(col, Graphics.COLOR_TRANSPARENT);
                dc.drawText(cx, y,
                    Ui.fitFont(dc, label, y - rowH / 2, rowH,
                        [Graphics.FONT_TINY, Graphics.FONT_XTINY]),
                    label, Graphics.TEXT_JUSTIFY_CENTER | Graphics.TEXT_JUSTIFY_VCENTER);
            }
        }
    }
}

class ResusOverviewDelegate extends WatchUi.BehaviorDelegate {

    var _v as ResusOverviewView;

    function initialize(v as ResusOverviewView) {
        BehaviorDelegate.initialize();
        _v = v;
    }

    // Blaettern ueberspringt Trennbalken — sie sind keine Auswahl
    private function _step(dir as Lang.Number) as Lang.Boolean {
        var n = _v.items.size();
        var i = _v.index;
        for (var k = 0; k < n; k++) {
            i = ((i + dir) % n + n) % n;
            if (_v.items[i][2] != :sep) { break; }
        }
        _v.index = i;
        WatchUi.requestUpdate();
        return true;
    }

    function onPreviousPage() as Lang.Boolean { return _step(-1); }
    function onNextPage() as Lang.Boolean { return _step(1); }

    function onSelect() as Lang.Boolean {
        var id = _v.items[_v.index][2];
        if (id == :resume) {
            WatchUi.popView(WatchUi.SLIDE_RIGHT);
            Cpr.resume();
        } else if (id == :finish) {
            WatchUi.popView(WatchUi.SLIDE_RIGHT);
            Cpr.stopRecording();
        }
        return true;                          // Zeitstempel sind reine Anzeige
    }

    function onBack() as Lang.Boolean {
        WatchUi.popView(WatchUi.SLIDE_RIGHT);
        return true;
    }
}
