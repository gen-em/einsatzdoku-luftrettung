// Rundlauf-Fuellung — NUR fuer die Prueffassungen von rundlauf.sh (und
// syncseite.sh, das damit zwei offene Pakete anlegt), KEIN
// Bestandteil der App (S11-08). rundlauf.sh kopiert watch/ nach
// $CIQ_BASIS/rundlauf-app, legt diese Datei dazu und ruft fuellen() aus
// NAdokuApp.onStart auf. Die App selbst kennt sie nicht.
//
// Anlass: Nr. 43 — die Uhr verschliesst seit 4.0.0; ob ihre Huellen
// aufgehen, zeigt nur ein Paket, das wirklich gesendet wurde.
//
// WAS SIE ANLEGT: einen abgeschlossenen Einsatz mit 600 Punkten (drei
// Speicherstuecke zu 200, also zwei Anfragen: 500 + 100), einer Phase ohne
// Ortung, einer Reanimation mit zwei Ereignissen, und ein abgeschlossenes
// Ruhesegment mit 3 Punkten. Die Werte waehlen die Raender: negative Breite
// und Laenge, eine Hoehe ueber 3276 m (F-S11-64), eine negative, Punkte
// ohne Hoehe. pruefen.php rechnet dieselben Werte und haelt die geoeffneten
// Huellen dagegen.
//
// ART --schrumpfen (F-R17-52, Anlass: Nr. 43): Vor den Einsatz kommen zwei
// GEPARKTE Pakete mit je einem Spurstueck. rundlauf.sh haengt zwei Haken in
// die Kopie des Uploaders: `waehrendAnfrage()` direkt nach dem Absenden und
// `bericht()` nach jeder angenommenen Antwort. Waehrend der LETZTEN Anfrage
// des Einsatzes verwirft der erste Haken alle geparkten -- die Liste
// schrumpft von drei auf einen Eintrag, genau der Weg ueber die Sync-Seite.
// Bis Uhr 4.0.0 entfernte die Antwort dann `pendingMissions[2]` und brach ab;
// seit 4.1.0 entfernt sie ueber die Referenz. `bericht()` schreibt in die
// Konsole des Simulators, was in der Liste und im Speicher uebrig ist.
//
// ART --neukoppeln / --fremdkonto (seit KG-05): `halten()` haelt das Senden
// 30 s an, sobald die Uhr ihren Kontoschluessel hat. In dieser Zeit loescht
// rundlauf.sh das Geraet; danach sendet die Uhr, bekommt 401 und hat einen
// Rueckstand mit bekanntem Schluessel. Bis Web 24.12.0 holte sich dafuer
// eine DEAKTIVIERTE Uhr den Schluessel; seit Web 24.12.1 gibt pair.php ihr
// keinen mehr (F-KA-27).
using Toybox.Lang;
using Toybox.System;
using Toybox.Application.Storage;

module Rundlauf {
    const T0 = 1792224000;            // 2026-10-17T08:00:00Z
    const EINSATZ = "m-rundlauf-0001";
    const RUHE = "r-rundlauf-0001";
    const SCHRUMPFEN = false;         // rundlauf.sh --schrumpfen setzt true
    const HALTEN = false;             // rundlauf.sh --neukoppeln/--fremdkonto setzt true
    const HALTEN_S = 30;
    const GEPARKT = ["m-rl-geparkt-1", "m-rl-geparkt-2"];
    var _ausgeloest as Lang.Boolean = false;

    function punkt(i as Lang.Number) as Lang.Array {
        var ele = null;
        if (i == 1) { ele = 4500.5f; }
        else if (i == 2) { ele = -12.5f; }
        else if (i % 50 != 0) { ele = (1800 + i).toFloat(); }
        return [-33.4489d + i * 0.0001d, -70.6693d + i * 0.0002d, ele, T0 + i * 5];
    }

    function fuellen() as Void {
        if (Storage.getValue("rl_gefuellt") != null) { return; }
        for (var c = 0; c < 3; c++) {
            var stueck = [];
            for (var i = c * 200; i < (c + 1) * 200; i++) { stueck.addAll(punkt(i)); }
            Storage.setValue(EINSATZ + "_" + c.toString(), stueck as Lang.Array<Storage.ValueType>);
        }
        if (SCHRUMPFEN) {
            for (var g = 0; g < GEPARKT.size(); g++) {
                var gref = GEPARKT[g] as Lang.String;
                Storage.setValue(gref + "_0", [47.2d, 11.3d, 500.0f, T0 - 900] as Lang.Array<Storage.ValueType>);
                Storage.setValue("bad_" + gref, Util.epochNow());   // geparkt, in der Frist
                Model.pendingMissions.add({ "ref" => gref, "day" => "2026-10-17",
                    "dref" => "d-rundlauf-0001", "final" => true });
            }
        }
        Storage.setValue(RUHE + "_0", [47.1d, 11.2d, 600.0f, T0 - 600,
                                       47.1001d, 11.2001d, null, T0 - 300,
                                       47.1002d, 11.2002d, 601.0f, T0 - 10] as Lang.Array<Storage.ValueType>);
        Model.pendingMissions.add({
            "ref" => EINSATZ, "day" => "2026-10-17", "dref" => "d-rundlauf-0001",
            "startedAt" => "2026-10-17T08:00:00Z", "endedAt" => "2026-10-17T08:50:00Z",
            "final" => true, "dist" => 12345, "asc" => 321,
            "phases" => [[2, "2026-10-17T08:00:00Z", -33.4489d, -70.6693d, "10:00"],
                         [3, "2026-10-17T08:05:00Z", null, null, "10:05"],
                         [4, "2026-10-17T08:20:00Z", -33.4389d, -70.6493d, "10:20"]],
            "resus" => [{ "start" => "2026-10-17T08:21:00Z", "startLocal" => "10:21",
                          "events" => [["adrenalin", "2026-10-17T08:22:30Z", "10:22"],
                                       ["defibrillation", "2026-10-17T08:23:00Z", "10:23"]] }]
        });
        Model.pendingRest.add({
            "ref" => RUHE, "day" => "2026-10-17", "dref" => "d-rundlauf-0001",
            "startedAt" => "2026-10-17T07:50:00Z", "endedAt" => "2026-10-17T08:00:00Z",
            "final" => true
        });
        Model.save();
        Storage.setValue("rl_gefuellt", true);
    }

    /* Arten der NACHHOLEN-Familie (rundlauf.sh, seit KG-03b): Die Uhr koppelt
     * wie vor Uhr 5.0.0, ohne Geraeteschluessel (E-KG-36). Nur in diesen
     * Prueffassungen eingehaengt. */
    function alteKopplung() as Lang.Boolean { return true; }

    /* Haken am Anfang von Uploader._send (nur HALTEN): true = jetzt nicht
     * senden. Die Frist beginnt beim ersten Versuch MIT Schluessel. */
    function halten() as Lang.Boolean {
        if (!HALTEN || !Huelle.hatSchluessel()) { return false; }
        var bis = Storage.getValue("rl_halt_bis");
        if (!(bis instanceof Lang.Number)) {
            bis = Util.epochNow() + HALTEN_S;
            Storage.setValue("rl_halt_bis", bis);
            System.println("RL-HALT " + HALTEN_S.toString() + " s");
        }
        return Util.epochNow() < (bis as Lang.Number);
    }

    /* Haken 1, direkt nach dem Absenden (nur --schrumpfen): Ist das die
     * zweite und letzte Anfrage des Einsatzes, alle geparkten verwerfen. */
    function waehrendAnfrage(ref as Lang.String) as Void {
        if (!SCHRUMPFEN || _ausgeloest) { return; }
        if (!EINSATZ.equals(ref) || Uploader._ackedSeq(EINSATZ) == 0) { return; }
        _ausgeloest = true;
        var n = Uploader.alleGeparktenVerwerfen();
        System.println("RL-SCHRUMPF verworfen=" + n.toString() + " offen=" + Model.pendingMissions.size().toString());
    }

    /* Haken 2, nach jeder angenommenen Antwort: was uebrig ist. Erwartet nach
     * der letzten Antwort des Einsatzes: keine Einsaetze mehr in der Liste,
     * kein Spurstueck mehr im Speicher -- weder vom Einsatz noch von den
     * geparkten. */
    function bericht() as Void {
        if (!SCHRUMPFEN || !_ausgeloest) { return; }
        var spuren = 0;
        var refs = [EINSATZ, GEPARKT[0], GEPARKT[1]];
        for (var i = 0; i < refs.size(); i++) {
            if (Storage.getValue((refs[i] as Lang.String) + "_0") != null) { spuren += 1; }
        }
        System.println("RL-SCHRUMPF liste=" + Model.pendingMissions.size().toString()
                       + " spuren=" + spuren.toString());
    }
}
