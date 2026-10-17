// Rundlauf-Fuellung — NUR fuer die Prueffassung von rundlauf.sh, KEIN
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
using Toybox.Lang;
using Toybox.Application.Storage;

module Rundlauf {
    const T0 = 1792224000;            // 2026-10-17T08:00:00Z
    const EINSATZ = "m-rundlauf-0001";
    const RUHE = "r-rundlauf-0001";

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
}
