// Einsatzdoku — app-weiter Zustand + Persistenz.
// Alles Wichtige liegt in einem Dictionary, das nach jeder Aenderung in den
// persistenten Storage geschrieben wird -> uebersteht App-/Uhren-Neustart.
using Toybox.Application.Storage;
using Toybox.Lang;

module Model {

    var serviceActive as Lang.Boolean = false;
    var day as Lang.String or Null = null;        // Betriebstag "YYYY-MM-DD"
    var phase as Lang.Number = 1;

    // Dienstkennung (JSON-Vertrag 1.3, Web-Konzept 4.4). Sie entsteht bei
    // "Einsatztag starten" und bleibt fuer ALLE Uploads dieses Dienstes
    // unveraendert — gleiches Muster wie client_ref, gleiche Idempotenz.
    //
    // WOZU. Bis Web 5.10.0 war ein Flugtag ein KALENDERTAG, und der Server
    // ordnete ueber (Konto, Datum) zu. Seit Web 6.0.0 ist ein Diensttag eine
    // eigene Zeile: Zwei Dienste an einem Kalendertag sind der vorgesehene Fall
    // — ein Hubschrauberdienst am Tag, ein NEF-Nachtdienst am Abend. Aus dem
    // Datum allein laesst sich dann nicht mehr ableiten, welcher gemeint ist.
    // Die Kennung sagt es.
    //
    // Die Uhr erfaehrt dabei NICHTS ueber die Einsatzart (E21). Sie sagt nur,
    // WELCHER Dienst — nicht, was fuer einer.
    var dayRef as Lang.String or Null = null;

    // Aktiver Einsatz: null oder Dictionary
    // { "ref", "startedAt", "endedAt", "phases" => [[p, iso, lat, lon, hhmm], ...],
    //   "resus" => [ { "start", "startLocal", "events" => [[type, iso, hhmm]] }, ... ],
    //   "dist", "asc" (bei Einsatzende eingefroren), "final" => Boolean }
    var mission as Lang.Dictionary or Null = null;

    // Abgeschlossene, aber noch nicht (fertig) hochgeladene Einsaetze
    var pendingMissions as Lang.Array<Lang.Dictionary> = [];

    // Aktives Ruhe-Segment: null oder { "ref", "startedAt", "endedAt", "final" }
    var restSegment as Lang.Dictionary or Null = null;
    var pendingRest as Lang.Array<Lang.Dictionary> = [];

    // Sende-Rueckstand: nur ABGESCHLOSSENE, noch unbestaetigte Pakete.
    // Das laufende Segment/der laufende Einsatz zaehlt bewusst nicht mit.
    function backlogCount() as Lang.Number {
        // Nur Pakete zaehlen, fuer die tatsaechlich noch etwas zu senden ist.
        // Fertig uebertragene Eintraege, die nur noch in der Liste stehen,
        // werden dabei gleich entsorgt (Selbstheilung) — sonst zeigte die
        // Sync-Seite dauerhaft "1 Paket offen", obwohl alles angekommen ist.
        var n = 0;
        var changed = false;
        for (var i = pendingMissions.size() - 1; i >= 0; i--) {
            var m = pendingMissions[i];
            if (!Uploader.hasWork(m["ref"] as Lang.String)) {
                if (m["final"] == true) { pendingMissions.remove(m); changed = true; }
            } else if (m["final"] == true) {
                n += 1;
            }
        }
        for (var j = pendingRest.size() - 1; j >= 0; j--) {
            var r = pendingRest[j];
            if (!Uploader.hasWork(r["ref"] as Lang.String)) {
                if (r["final"] == true) { pendingRest.remove(r); changed = true; }
            } else if (r["final"] == true) {
                n += 1;
            }
        }
        if (changed) { save(); }      // damit die Bereinigung Neustarts ueberlebt
        return n;
    }
    var dayMissions as Lang.Number = 0;   // ABGESCHLOSSENE Einsaetze des Tages
                                          // (Alarmierung + dokumentiertes Ende)

    function load() as Void {
        var s = Storage.getValue(Const.K_STATE);
        if (s instanceof Lang.Dictionary) {
            // Storage.getValue() liefert einen Sammeltyp ueber alle speicherbaren
            // Arten. Was hier steht, hat save() geschrieben — die Zusicherungen
            // benennen die Struktur, die dort ohnehin schon feststeht.
            serviceActive   = true.equals(s["svc"]);
            day             = s["day"] as Lang.String or Null;
            phase           = s["ph"]   != null ? s["ph"] as Lang.Number : 1;
            mission         = s["mis"] as Lang.Dictionary or Null;
            restSegment     = s["rest"] as Lang.Dictionary or Null;
            pendingMissions = s["pm"] != null
                ? s["pm"] as Lang.Array<Lang.Dictionary> : [];
            pendingRest     = s["pr"] != null
                ? s["pr"] as Lang.Array<Lang.Dictionary> : [];
            dayMissions = s["dm"] != null ? s["dm"] as Lang.Number : 0;
            dayRef          = s["dref"] as Lang.String or Null;
        }
    }

    function save() as Void {
        Storage.setValue(Const.K_STATE, {
            "svc" => serviceActive, "day" => day, "ph" => phase,
            "mis" => mission, "rest" => restSegment,
            "pm" => pendingMissions, "pr" => pendingRest,
            "dm" => dayMissions, "dref" => dayRef
        });
    }

    // ---- Dienst-Klammer (Anforderungen 1.1) --------------------------------

    // Die Kennung des LAUFENDEN Dienstes; fehlt sie, wird sie nachgezogen.
    //
    // DER FALL, DEN DAS ABFAENGT: Ein Dienst laeuft bereits, waehrend die Uhr
    // von 1.7.0 auf 1.8.0 aktualisiert wird. Der gespeicherte Zustand hat dann
    // kein "dref", der Dienst laeuft aber weiter — ohne diese Zeile truege
    // jeder Einsatz bis Dienstende keine Kennung.
    //
    // Der Server kommt damit zurecht: dt_zu_dayref() bindet eine UNBEKANNTE
    // Kennung an den Diensttag, an dem der Datensatz schon haengt, statt einen
    // zweiten anzulegen. Der laufende Dienst liegt dort bereits als Diensttag —
    // angelegt ueber die Rueckfallebene ueber (Konto, Datum).
    //
    // OHNE laufenden Dienst gibt sie null zurueck und erfindet nichts. Ein
    // Einsatz ausserhalb der Dienstklammer gehoert zu keinem bekannten Dienst;
    // fuer ihn ist die Rueckfallebene ueber das Datum die richtige Auskunft.
    function ensureDayRef() as Lang.String or Null {
        if (dayRef == null && serviceActive) {
            dayRef = Util.newRef("d");
            save();
        }
        return serviceActive ? dayRef : null;
    }

    function beginService() as Void {
        serviceActive = true;
        day = Util.localDay();
        phase = 1;
        dayMissions = 0;
        dayRef = Util.newRef("d");     // eine Kennung je Dienst (E9)
        _startRestSegment();
        save();
        Track.startPositioning();
    }

    function endService() as Void {
        _closeRestSegment();
        if (mission != null) { _finishMission(); }   // Sicherheitsnetz

        // Frischer Start beim naechsten Oeffnen: Zaehler, Phase und Tag
        // zuruecksetzen. Die Warteschlange (pendingMissions/pendingRest)
        // bleibt bewusst erhalten — sonst gingen noch nicht bestaetigte
        // Einsaetze verloren; sie wird beim naechsten Start weiter gesendet.
        serviceActive = false;
        phase         = 1;
        day           = null;
        mission       = null;
        restSegment   = null;
        dayMissions   = 0;
        // Die Kennung verfaellt mit dem Dienst. Sie stehenzulassen waere
        // gefaehrlich: Ein danach begonnener Einsatz truege die Kennung eines
        // BEENDETEN Dienstes und landete auf dem Server an dessen Diensttag.
        // Noch nicht gesendete Einsaetze verlieren dadurch nichts — sie fuehren
        // ihre eigene Kopie im Paket (siehe _startMission).
        dayRef        = null;
        save();
        Track.stopPositioning();
        Uploader.syncAll();
    }

    // ---- Phasen (Anforderungen 1.2) ----------------------------------------

    function missionActive() as Lang.Boolean { return mission != null; }

    // kurz START auf Oberflaeche 1: naechste Phase.
    // Nach Phase 9 bleibt der Einsatz OFFEN (Haltezustand) — der Abschluss
    // erfolgt nur ueber die Bestaetigung (finishMission), nie automatisch.
    function nextPhase() as Void {
        if (mission != null && phase >= 9) { return; }
        if (phase < 1 || phase >= 9) { phase = 1; }
        setPhase(phase + 1);
    }

    // Direktes Setzen (auch Schnellmenue): erneutes Setzen frueherer Phasen
    // erzeugt schlicht einen weiteren Zeitstempel (keine Korrektur).
    function setPhase(p as Lang.Number) as Void {
        if (p < 2 || p > 9) { return; }
        if (mission == null) { _startMission(); }    // Phase 2..9 ohne Einsatz -> Einsatz beginnt

        // Lokale Kopie: Die Typpruefung verfolgt eine Null-Pruefung nur ueber
        // lokale Variablen, nicht ueber ein Modul-Feld hinweg. _startMission()
        // hat gerade gesetzt, der Ruecksprung kann also nicht eintreten.
        var m = mission;
        if (m == null) { return; }

        var pos = Track.lastLatLon();
        // [phase, isoUTC, lat, lon, lokaleAnzeige]
        (m["phases"] as Lang.Array).add([p, Util.isoNow(), pos[0], pos[1], Util.localHHMM()]);
        phase = p;
        Util.vibrateShort();
        save();
    }

    // Bestaetigter Einsatz-Abschluss ("Einsatz beenden & senden?")
    function finishMission() as Void {
        if (mission == null) { return; }
        _finishMission();
    }

    function _startMission() as Void {
        _closeRestSegment();
        mission = {
            "ref" => Util.newRef("m"),      // Zaehler + Zufall, kein Zeitstempel (M7-03)
            "day" => Util.localDay(),      // Tag des EINSATZbeginns (0:00-Wechsel)
            "dref" => ensureDayRef(),      // Dienst, zu dem dieser Einsatz gehoert
            "startedAt" => Util.isoNow(), "endedAt" => null,
            "phases" => [], "resus" => [],
            "final" => false
        };
        Track.beginMissionTrack(mission["ref"] as Lang.String);
    }

    function _finishMission() as Void {
        var m = mission;                     // lokal: s. Hinweis in markPhase()
        if (m == null) { return; }
        Cpr.stopRecording();                 // laufende Rea sauber schliessen
        // Einsatzende = Zeit der (letzten) Phase 9; ohne Phase 9 der
        // Abschluss-Zeitpunkt als Rueckfall.
        var end = Util.isoNow();
        var ph = m["phases"] as Lang.Array;
        for (var i = 0; i < ph.size(); i++) {
            if ((ph[i] as Lang.Array)[0] == 9) {
                end = (ph[i] as Lang.Array)[1] as Lang.String;
            }
        }
        m["endedAt"] = end;
        // Kilometer/Anstieg einfrieren: gehoeren zu DIESEM Einsatz, auch wenn
        // der Upload erst spaeter (waehrend eines neuen Einsatzes) gelingt
        m["dist"] = Track.distanceM.toNumber();
        m["asc"]  = Track.ascentM.toNumber();
        m["final"] = true;
        dayMissions += 1;                  // zaehlt erst mit bestaetigtem Ende
        Track.endMissionTrack();
        pendingMissions.add(m);
        mission = null;
        phase = 1;
        _startRestSegment();
        save();
        Uploader.syncAll();
    }

    // ---- Ruhe-Segmente (Anforderungen 1.3) ---------------------------------

    function _startRestSegment() as Void {
        restSegment = {
            "ref" => Util.newRef("r"),      // wie beim Einsatz (M7-03)
            "day" => Util.localDay(),      // Tag des Segmentbeginns
            "dref" => ensureDayRef(),      // Dienst, zu dem dieses Segment gehoert
            "startedAt" => Util.isoNow(), "endedAt" => null, "final" => false
        };
        Track.beginRestTrack(restSegment["ref"] as Lang.String);
    }

    function _closeRestSegment() as Void {
        var r = restSegment;                 // lokal: s. Hinweis in markPhase()
        if (r == null) { return; }
        r["endedAt"] = Util.isoNow();
        r["final"] = true;
        Track.endRestTrack();
        pendingRest.add(r);
        restSegment = null;
    }

    // ---- Reanimation (Anforderungen 1.4, mehrere pro Einsatz moeglich) -----
    // Jeder Rea-Start legt eine NEUE Sitzung an; "Aufzeichnung beenden"
    // schliesst sie. Zeitstempel liegen beim Einsatz; laeuft ausnahmsweise
    // keiner, wird implizit einer gestartet.

    function resusStart() as Void {
        if (mission == null) { _startMission(); }
        var m = mission;                     // lokal: s. Hinweis in markPhase()
        if (m == null) { return; }
        (m["resus"] as Lang.Array).add({
            "start" => Util.isoNow(), "startLocal" => Util.localHHMM(),
            "events" => []
        });
        save();
    }

    function resusEvent(type as Lang.String) as Void {
        if (mission == null || (mission["resus"] as Lang.Array).size() == 0) {
            resusStart();
        }
        var m = mission;                     // lokal: s. Hinweis in markPhase()
        if (m == null) { return; }
        var sessions = m["resus"] as Lang.Array;
        var cur = sessions[sessions.size() - 1] as Lang.Dictionary;
        // [typ, isoUTC, lokaleAnzeige]
        (cur["events"] as Lang.Array).add([type, Util.isoNow(), Util.localHHMM()]);
        Util.vibrateShort();
        save();
    }

    // Letzte (= aktuelle) Rea-Sitzung, fuer die Uebersicht auf der Uhr
    function currentResus() as Lang.Dictionary or Null {
        var m = mission;                     // lokal: s. Hinweis in markPhase()
        if (m == null) { return null; }
        var sessions = m["resus"] as Lang.Array;
        return sessions.size() > 0
            ? sessions[sessions.size() - 1] as Lang.Dictionary : null;
    }
}
