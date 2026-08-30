// Einsatzdoku — gemeinsame Helfer
// HINWEIS: Erster Wurf, noch nicht gegen das SDK kompiliert.
using Toybox.Time;
using Toybox.Time.Gregorian;
using Toybox.Lang;
using Toybox.Attention;
using Toybox.System;
using Toybox.Math;
using Toybox.Application.Storage;

module Util {

    // ---- Client-Kennung fuer Einsaetze und Ruhesegmente (M7-03) -----------
    //
    // WAS SIE IST
    // Die Kennung ("client_ref") ist der Anker der Idempotenz: Der Server
    // erkennt an ihr, ob ein Upload denselben Einsatz betrifft wie ein
    // frueherer. Sie muss auf DIESEM Geraet eindeutig sein und darf sich
    // niemals wiederholen — der eindeutige Schluessel auf dem Server lautet
    // (Geraetekennung, client_ref).
    //
    // WAS VORHER FALSCH WAR
    // Sie bestand aus Praefix plus Zeitstempel in Sekunden ("m-1785000000").
    // Zwei Folgen:
    //
    //   1. SPRINGT DIE UHRZEIT ZURUECK — nach einem Zuruecksetzen des Geraets,
    //      nach einem Wechsel der Zeitzone im Flugmodus —, entstehen erneut
    //      Kennungen, die es schon gab. Der naechste Upload trifft dann einen
    //      FREMDEN, alten Einsatz desselben Geraets und ueberschreibt ihn.
    //   2. Sie verraet den Startzeitpunkt auf die Sekunde, auch wenn er
    //      spaeter im Web korrigiert wurde.
    //
    // WIE SIE JETZT ENTSTEHT
    // Ein fortlaufender Zaehler im Geraetespeicher plus zwei Zufallswerte.
    // Der Zaehler ueberlebt Neustarts und Zeitspruenge und ist die eigentliche
    // Zusicherung: Er wiederholt sich nicht, ganz gleich, was die Uhrzeit tut.
    // Der Zufallsanteil verhindert, dass sich aus der Kennung die Reihenfolge
    // oder ein Zeitpunkt ablesen laesst.
    //
    // DER ZEITSTEMPEL IST GANZ ENTFALLEN, nicht nur ergaenzt worden — sonst
    // bliebe Punkt 2 bestehen. Der Startzeitpunkt steht ohnehin als
    // "startedAt" im Datensatz, dort gehoert er hin und dort ist er
    // korrigierbar.
    //
    // VERTRAEGLICH: Der Server prueft das Format nicht; die Idempotenz haengt
    // allein an der Gleichheit der Zeichenkette. Kennungen, die vor dem Update
    // entstanden und noch in der Warteschlange liegen, bleiben gueltig.
    var _refSeeded as Lang.Boolean = false;

    function newRef(kind as Lang.String) as Lang.String {
        // Zaehler zuerst und sofort sichern: Ein Absturz zwischen Lesen und
        // Schreiben darf hoechstens eine Nummer ueberspringen, niemals eine
        // doppelt vergeben.
        var n = Storage.getValue("refseq");
        if (!(n instanceof Lang.Number) || n < 0) { n = 0; }
        n = n + 1;
        if (n > 2000000000) { n = 1; }        // Ueberlauf des 32-Bit-Werts meiden
        Storage.setValue("refseq", n);

        if (!_refSeeded) {
            // Einmal je App-Start streuen. Der Zaehler geht mit ein, damit
            // zwei Starts nach einem Zuruecksetzen der Uhrzeit nicht dieselbe
            // Folge liefern.
            Math.srand(Time.now().value() ^ n);
            _refSeeded = true;
        }
        var a = Math.rand() & 0xFFFF;
        var b = Math.rand() & 0xFFFF;
        return kind + "-" + n.format("%d") + "-"
             + a.format("%05d") + b.format("%05d");
    }

    // Aktueller Zeitpunkt als ISO 8601 UTC ("2026-07-16T08:31:05Z")
    function isoNow() as Lang.String {
        return isoFromMoment(Time.now());
    }

    function isoFromMoment(moment as Time.Moment) as Lang.String {
        // Die Felder von Gregorian.Info sind nominell nullbar; bei FORMAT_SHORT
        // sind es immer Zahlen. Die Zusicherungen benennen das.
        var g = Gregorian.utcInfo(moment, Time.FORMAT_SHORT);
        return Lang.format("$1$-$2$-$3$T$4$:$5$:$6$Z", [
            (g.year as Lang.Number).format("%04d"),
            (g.month as Lang.Number).format("%02d"),
            (g.day as Lang.Number).format("%02d"),
            (g.hour as Lang.Number).format("%02d"),
            (g.min as Lang.Number).format("%02d"),
            (g.sec as Lang.Number).format("%02d")]);
    }

    // Betriebstag = lokales Datum des Dienstbeginns ("YYYY-MM-DD")
    function localDay() as Lang.String {
        var g = Gregorian.info(Time.now(), Time.FORMAT_SHORT);
        return Lang.format("$1$-$2$-$3$", [
            (g.year as Lang.Number).format("%04d"),
            (g.month as Lang.Number).format("%02d"),
            (g.day as Lang.Number).format("%02d")]);
    }

    // Kurzes Anzeigedatum, z. B. "Mo, 20.07." (eigene Wochentagsliste,
    // damit die Sprache nicht von der Geraete-Locale abhaengt)
    const WD = ["So", "Mo", "Di", "Mi", "Do", "Fr", "Sa"];

    function localDateShort() as Lang.String {
        var g = Gregorian.info(Time.now(), Time.FORMAT_SHORT);
        var wd = WD[((g.day_of_week as Lang.Number) - 1) % 7];
        return wd + ", " + (g.day as Lang.Number).format("%02d")
                  + "." + (g.month as Lang.Number).format("%02d") + ".";
    }

    function epochNow() as Lang.Number {
        return Time.now().value();
    }

    // Lokale Uhrzeit "HH:MM" fuer die Anzeige auf der Uhr
    function localHHMM() as Lang.String {
        var g = Gregorian.info(Time.now(), Time.FORMAT_SHORT);
        return g.hour.format("%02d") + ":" + g.min.format("%02d");
    }

    // Zwei kurze Vibrationen (Rea-Beginn)
    function vibrateTwice() as Void {
        _vibe([
            new Attention.VibeProfile(75, 300),
            new Attention.VibeProfile(0, 200),
            new Attention.VibeProfile(75, 300)
        ]);
    }

    // Absturzsicherer Vibrationsaufruf (Hardware-Limit: max. 8 Profile!)
    function _vibe(p as Lang.Array) as Void {
        if (Attention has :vibrate) {
            try { Attention.vibrate(p as Lang.Array<Attention.VibeProfile>); } catch (ex) { }
        }
    }

    // Zyklusende Teil 1: drei kraeftige Pulse (5 Profile — unter dem Limit).
    // Cpr.tick() haengt einen Tick spaeter zwei weitere an (insgesamt 5).
    function vibrateCycleEnd() as Void {
        _vibe([
            new Attention.VibeProfile(90, 300), new Attention.VibeProfile(0, 200),
            new Attention.VibeProfile(90, 300), new Attention.VibeProfile(0, 200),
            new Attention.VibeProfile(90, 300)
        ]);
    }

    // Kraeftiger Bestaetigungs-Puls (Phase/Ereignis dokumentiert)
    function vibrateShort() as Void {
        _vibe([new Attention.VibeProfile(80, 200)]);
    }
}
