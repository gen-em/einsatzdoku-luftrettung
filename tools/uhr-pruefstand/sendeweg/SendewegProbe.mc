// Probe des Sendewegs — NUR fuer die Prueffassung von sendeweg.sh, KEIN
// Bestandteil der App. sendeweg.sh kopiert watch/ nach
// $CIQ_BASIS/sendeweg-app, legt diese Datei dazu, startet sie aus
// NAdokuApp.onStart und haengt zwei Haken in die Kopie von Uploader.mc:
// `stueck()` nach dem Lesen der Punkte und `abfangen()` vor dem Absenden.
// Keine Anfrage verlaesst den Simulator; die Antworten des Servers spielt
// diese Datei selbst vor.
//
// Anlass: Nr. 570 — Kopplung im Dienst, ungebundene laufende Pakete,
// ungedeckeltes next_seq, wachsendes Wiederholungsstueck (KG-01); Nr. 573 —
// 401/403 ohne Fehlerfeld galten als Abmeldung, und die Frist raeumte ohne
// letzten Versuch (KG-06); Nr. 575 -- ein Dienst begann ohne Kopplung, und
// Trennen holte sofort einen neuen Code (KG-08); Nr. 577 -- Kopplung mit
// Vergleichswert und Fassung 2 (KG-03b): Wert erst nach dem Offenlegen,
// anderes Paar bricht ab, 202 wartet, abgelehnt legt kein Paar ab,
// `huelle: 2` schaltet Fassung 2 ein, das leere Schlussstueck.
//
// Je Fall eine Zeile "SENDEWEG ok|FEHL <fall> <was>", am Ende
// "SENDEWEG ENDE <faelle> <fehlschlaege>". Jeder Fall beginnt mit leerem
// Speicher und laeuft in einem eigenen Takt: Alle in einem Aufruf
// loesen den Watchdog aus (zu viele Verschliessungen am Stueck).
using Toybox.Lang;
using Toybox.System;
using Toybox.Timer;
using Toybox.Application.Storage;

class SendewegCb {
    function initialize() {}
    function los() as Void { SendewegProbe.takt(); }
}

module SendewegProbe {
    var _t as Timer.Timer or Null = null;
    var _cb as SendewegCb or Null = null;
    var _faelle as Lang.Number = 0;
    var _fehl as Lang.Number = 0;
    var seq as Lang.Number = -1;      // Anfang des zuletzt gelesenen Stuecks
    var n as Lang.Number = -1;        // seine Groesse in Punkten
    var abgefangen as Lang.Number = 0;
    var _schritt as Lang.Number = 0;
    var _a as Lang.String or Null = null;
    var _b as Lang.String or Null = null;

    function starten() as Void {
        _cb = new SendewegCb();
        _t = new Timer.Timer();
        (_t as Timer.Timer).start((_cb as SendewegCb).method(:los), 1500, true);
    }

    // Haken in Uploader._send, nach Track.readPoints
    function stueck(s as Lang.Number, m as Lang.Number) as Void { seq = s; n = m; }

    // Haken in Uploader._send, vor makeWebRequest: nie absenden. Der Rumpf
    // bleibt fuer die Faelle von KG-03b liegen.
    var rumpf as Lang.Dictionary or Null = null;
    function abfangen(b as Lang.Dictionary) as Lang.Boolean { abgefangen += 1; rumpf = b; return true; }

    function _melde(ok as Lang.Boolean, fall as Lang.String, was as Lang.String) as Void {
        _faelle += 1;
        if (!ok) { _fehl += 1; }
        System.println("SENDEWEG " + (ok ? "ok" : "FEHL") + " " + fall + " " + was);
    }

    // ---- Zustand ----------------------------------------------------------

    function _leer() as Void {
        Storage.clearValues();
        Model.serviceActive = false;
        Model.mission = null;
        Model.restSegment = null;
        Model.pendingMissions = [];
        Model.pendingRest = [];
        Uploader.abgemeldet = false;
        Uploader.gesperrt = null;
        Uploader.lastError = null;
        Uploader._busy = false;
        Uploader._einmalTrotz = false;
        Uploader._nichtVor = null;
        Pair.status = null;
        Pair.statusHint = null;
        Pair._sitzungVergessen();
        Huelle2.paarLoeschen();          // auch der Zwischenspeicher im Modul
        Huelle2._geprueft = null;
        seq = -1; n = -1; abgefangen = 0; rumpf = null;
    }

    function _schluessel() as Lang.String {
        var p = Huelle.paar(null);
        return Huelle.base64(Huelle.geraetZuSec1(p.getPublicKey().getBytes()));
    }

    function _koppeln(k as Lang.String) as Void {
        Storage.setValue("cred", { "d" => "sendeweg", "k" => "probe" }
                         as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
        Huelle.schluesselSetzen(k);
    }

    // Spurstueck `idx` (200 Punkte) eines abgeschlossenen Tracks
    function _stueckAblegen(ref as Lang.String, idx as Lang.Number) as Void {
        var a = [];
        for (var i = idx * 200; i < (idx + 1) * 200; i++) {
            a.addAll([47.0d + i * 0.0001d, 11.0d + i * 0.0001d, 600.0f, 1792224000 + i * 5]);
        }
        Storage.setValue(ref + "_" + idx.toString(), a as Lang.Array<Storage.ValueType>);
    }

    function _einsatz(ref as Lang.String) as Lang.Dictionary {
        return { "ref" => ref, "day" => "2026-11-10", "dref" => "d-sendeweg",
                 "startedAt" => "2026-11-10T08:00:00Z", "endedAt" => null,
                 "final" => false, "phases" => [], "resus" => [] };
    }

    // ---- Faelle -----------------------------------------------------------

    function takt() as Void {
        _schritt += 1;
        if (_schritt == 1) { _a = _schluessel(); _b = _schluessel(); return; }
        var a = _a as Lang.String;
        var b = _b as Lang.String;
        if (_schritt == 2) { _fall1(); }
        else if (_schritt == 3) { _fall2(a); }
        else if (_schritt == 4) { _fall3(); }
        else if (_schritt == 5) { _fall4(a, b); }
        else if (_schritt == 6) { _fall5a(a); }
        else if (_schritt == 7) { _fall5b(); }
        else if (_schritt == 8) { _fall6(a); }
        else if (_schritt == 9) { _fall7(a); }
        else if (_schritt == 10) { _fall8(a); }
        else if (_schritt == 11) { _fall9(a); }
        else if (_schritt == 12) { _fall10(a); }
        else if (_schritt == 13) { _fall11a(a); }
        else if (_schritt == 14) { _fall11b(); }
        else if (_schritt == 15) { _fall12(); }
        else if (_schritt == 16) { _fall13(a); }
        else if (_schritt == 17) { _fall14(a); }
        else if (_schritt == 18) { _fall15(); }
        else if (_schritt == 19) { _fall16(a); }
        else if (_schritt == 20) { _fall17(a); }
        else if (_schritt == 21) { _fall18(); }
        else if (_schritt == 22) { _fall19(a); }
        else {
            (_t as Timer.Timer).stop();
            System.println("SENDEWEG ENDE " + _faelle.toString() + " " + _fehl.toString());
        }
    }

    function _fall1() as Void {
        // 1. Ungekoppelt, im Dienst: keine Kopplungssitzung.
        _leer();
        Model.serviceActive = true;
        Pair.start();
        _melde("Erst Dienst beenden".equals(Pair.status), "koppeln-ungekoppelt-im-dienst",
               "status=" + (Pair.status == null ? "leer" : Pair.status));

    }

    function _fall2(a as Lang.String) as Void {
        // 2. Gekoppelt, ohne Rueckstand, im Dienst: kein Trennen.
        _leer();
        _koppeln(a);
        Model.serviceActive = true;
        Pair.start();
        _melde("Erst Dienst beenden".equals(Pair.status), "koppeln-gekoppelt-im-dienst",
               "status=" + (Pair.status == null ? "leer" : Pair.status));

    }

    function _fall3() as Void {
        // 3. Gegenprobe: gekoppelt ohne Schluessel, im Dienst -> holen bleibt erlaubt.
        _leer();
        Storage.setValue("cred", { "d" => "sendeweg", "k" => "probe" }
                         as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
        Model.serviceActive = true;
        Pair.start();
        _melde(!"Erst Dienst beenden".equals(Pair.status), "schluessel-holen-im-dienst",
               "status=" + (Pair.status == null ? "leer" : Pair.status));

    }

    function _fall4(a as Lang.String, b as Lang.String) as Void {
        // 4. Laufender Einsatz, Zugangsdaten gehen verloren, neue Kopplung mit
        //    anderem Schluessel: der laufende Einsatz bleibt liegen.
        _leer();
        _koppeln(a);
        Model.serviceActive = true;
        _stueckAblegen("m-sw-lauf", 0);
        Model.mission = _einsatz("m-sw-lauf");
        Pair.lokalTrennen();
        _koppeln(b);
        var job = Uploader._findJob();
        _melde(Uploader.istFremd("m-sw-lauf") && job == null, "bindung-laufender-einsatz",
               "fremd=" + Uploader.istFremd("m-sw-lauf").toString() + " job=" + (job == null ? "keiner" : "laufender Einsatz"));

    }

    var _n1 as Lang.Number = -1;

    function _fall5a(a as Lang.String) as Void {
        // 5. Gleiche Groesse bei Wiederholung: 200 gesendet, Antwort verloren,
        //    der Track waechst auf 400 -> wieder 0:200.
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-stueck", 0);
        Model.pendingMissions.add(_einsatz("m-sw-stueck"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        _n1 = n;
        Uploader.onResponse(-104, null);                  // keine Antwort
        _stueckAblegen("m-sw-stueck", 1);
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        _melde(_n1 == 200 && seq == 0 && n == 200, "stueck-wiederholt-gleich",
               "erst 0:" + _n1.toString() + " dann " + seq.toString() + ":" + n.toString());
    }

    function _fall5b() as Void {
        // ... quittiert -> das naechste Stueck beginnt bei 200
        Uploader.onResponse(200, { "ok" => true, "next_seq" => 200 });
        _melde(seq == 200 && n == 200, "stueck-danach-weiter",
               "naechstes " + seq.toString() + ":" + n.toString());

    }

    function _fall6(a as Lang.String) as Void {
        // 6. Quittung ueber das Gesendete hinaus wird gedeckelt.
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-deckel", 0);
        Model.pendingMissions.add(_einsatz("m-sw-deckel"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        Uploader.onResponse(200, { "ok" => true, "next_seq" => 100000 });
        var ack = Storage.getValue("ack_m-sw-deckel");
        _melde(ack instanceof Lang.Number && (ack as Lang.Number) <= 200, "next-seq-gedeckelt",
               "gesendet 200, quittiert " + (ack == null ? "nichts" : ack.toString()));

    }

    // 8. 401 ohne Fehlerfeld (ein Zwischenstueck): keine Abmeldung.
    function _fall8(a as Lang.String) as Void {
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-401", 0);
        Model.pendingMissions.add(_einsatz("m-sw-401"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        Uploader.onResponse(401, null);
        _melde(!Uploader.abgemeldet && Storage.getValue("abgem_seit") == null,
               "401-ohne-feld-keine-abmeldung", "abgemeldet=" + Uploader.abgemeldet.toString());
    }

    // 9. 403 mit fremdem Rumpf (Proxy-Seite): keine Sperre.
    function _fall9(a as Lang.String) as Void {
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-403", 0);
        Model.pendingMissions.add(_einsatz("m-sw-403"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        Uploader.onResponse(403, "<html>Forbidden</html>");
        _melde(Uploader.gesperrt == null && Storage.getValue("abgem_seit") == null,
               "403-ohne-feld-keine-sperre", "gesperrt=" + (Uploader.gesperrt == null ? "nein" : Uploader.gesperrt));
    }

    // 10. Gegenprobe: 401 mit `auth` ist eine Abmeldung.
    function _fall10(a as Lang.String) as Void {
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-auth", 0);
        Model.pendingMissions.add(_einsatz("m-sw-auth"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        Uploader.onResponse(401, { "error" => "auth" });
        _melde(Uploader.abgemeldet, "401-auth-ist-abmeldung", "abgemeldet=" + Uploader.abgemeldet.toString());
    }

    // 11a. Frist abgelaufen: erst ein Versuch, nichts geraeumt.
    function _fall11a(a as Lang.String) as Void {
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-frist", 0);
        var m = _einsatz("m-sw-frist");
        m["final"] = true;
        Model.pendingMissions.add(m);
        Uploader.abgemeldet = true;
        Storage.setValue("abgem_seit", Util.epochNow() - Const.PARK_FRIST_S - 100);
        Uploader.syncAll();
        _melde(Model.pendingMissions.size() == 1 && abgefangen > 0, "frist-erst-versuch",
               "liste=" + Model.pendingMissions.size().toString() + " versuche=" + abgefangen.toString());
    }

    // 11b. ... wieder abgewiesen (mit Feld): der naechste Lauf raeumt.
    function _fall11b() as Void {
        Uploader.onResponse(401, { "error" => "auth" });
        Uploader._busy = false;
        Uploader.syncAll();
        _melde(Model.pendingMissions.size() == 0, "frist-dann-geraeumt",
               "liste=" + Model.pendingMissions.size().toString());
    }

    function _fall7(a as Lang.String) as Void {
        // 7. Antwort ohne next_seq quittiert nichts (zuletzt: die alte Fassung
        //    bricht hier ab, und das zeigt die fehlende ENDE-Zeile).
        _leer();
        _koppeln(a);
        _stueckAblegen("m-sw-ohne", 0);
        Model.pendingMissions.add(_einsatz("m-sw-ohne"));
        Uploader._send({ "kind" => "mission", "data" => Model.pendingMissions[0] });
        Uploader.onResponse(200, { "ok" => true });
        var ack2 = Storage.getValue("ack_m-sw-ohne");
        _melde(ack2 == null, "ohne-next-seq", "quittiert " + (ack2 == null ? "nichts" : ack2.toString()));
    }

    function _fall12() as Void {
        // 12. Ungekoppelt: kurz START auf dem Startbildschirm beginnt keinen
        //     Dienst (KG-08). Die Server-Adresse setzt sendeweg.sh.
        _leer();
        new StartDelegate().actSelectShort();
        _melde(!Model.serviceActive, "kein-dienst-ohne-kopplung",
               "dienst=" + Model.serviceActive.toString());
        Model.serviceActive = false;
    }

    function _fall13(a as Lang.String) as Void {
        // 13. Trennen (Server antwortet 200): getrennt, KEIN neuer Code (KG-08).
        _leer();
        _koppeln(a);
        Pair.onTrennen(200);
        var st = Pair.status == null ? "leer" : Pair.status as Lang.String;
        _melde(!Uploader.hasCredentials() && "Getrennt".equals(st), "trennen-ohne-neuen-code",
               "status=" + st);
    }

    // ---- KG-03b: Kopplung mit Vergleichswert, Fassung 2 ---------------------

    const NB = "AAECAwQFBgcICQoLDA0ODw==";     // 16 Byte, wie der Browser sie zieht

    /* Eine Sitzung bis zum offengelegten N_g -- die Antworten spielt die
     * Probe vor. Jede Sitzung mit eigener Kennung: Die echte Antwort auf
     * `offenlegen` (127.0.0.1:1, Fehler) kommt spaeter und gehoert dann
     * zu keiner laufenden Sitzung mehr. */
    function _sitzung(dev as Lang.String, a as Lang.String) as Void {
        Pair._sitzungVergessen();
        Pair._dev = dev;
        Pair._key = "probe";
        Pair._abfragen = true;
        Huelle2.kopplungBeginnen();
        Pair._mitSchluessel = true;
        Pair.onStatus(dev, 200, { "zustand" => "beansprucht", "konto" => "pr***@pr***.de",
                                  "kontoschluessel" => a, "n_b" => NB });
    }

    function _fall14(a as Lang.String) as Void {
        // 14. Der Wert steht erst nach der 200 auf `offenlegen`.
        _leer();
        _sitzung("sw-k1", a);
        var vorher = Pair.wertAnzeige();
        Pair.onOffengelegt("sw-k1", 200, { "ok" => true });
        var w = Pair.wertAnzeige();
        _melde(vorher == null && w != null && (w as Lang.String).length() == 7,
               "wert-nach-offenlegen", "vorher=" + (vorher == null ? "keiner" : vorher)
               + " danach=" + (w == null ? "keiner" : w));
    }

    function _fall15() as Void {
        // 15. Ein anderes N_b in einer spaeteren Antwort bricht ab.
        Pair._abfragen = true;
        Pair.onStatus("sw-k1", 200, { "zustand" => "beansprucht", "konto" => "pr***@pr***.de",
                                      "kontoschluessel" => Huelle.base64(Huelle.geraetZuSec1(
                                          Huelle.paar(null).getPublicKey().getBytes())),
                                      "n_b" => NB });
        var st = Pair.status == null ? "leer" : Pair.status as Lang.String;
        _melde("Kopplung abgebrochen".equals(st) && Pair.wertAnzeige() == null,
               "anderes-paar-bricht-ab", "status=" + st);
    }

    function _fall16(a as Lang.String) as Void {
        // 16. Ja -> 202: die Uhr wartet; danach im Web abgelehnt: kein Paar.
        _leer();
        _sitzung("sw-k2", a);
        Pair.onOffengelegt("sw-k2", 200, { "ok" => true });
        Pair.onBestaetigt("sw-k2", true, 202, { "wartet" => "browser" });
        _melde(Pair.wartetWeb && Pair._abfragen, "ja-202-wartet",
               "wartet=" + Pair.wartetWeb.toString() + " abfragen=" + Pair._abfragen.toString());
        Pair.onStatus("sw-k2", 200, { "zustand" => "abgelehnt", "grund" => "zusage" });
        var st = Pair.status == null ? "leer" : Pair.status as Lang.String;
        var hi = Pair.statusHint == null ? "leer" : Pair.statusHint as Lang.String;
        _melde("Im Web abgelehnt".equals(st) && "Schlüssel passt nicht".equals(hi)
               && !Huelle2.hatPaar() && Storage.getValue("h2") == null && !Uploader.hasCredentials(),
               "abgelehnt-kein-paar", "status=" + st + " / " + hi + " paar=" + Huelle2.hatPaar().toString());
    }

    function _fall17(a as Lang.String) as Void {
        // 17. Gekoppelt mit `huelle: 2`: Paar abgelegt, Fassung 2 an.
        _leer();
        _sitzung("sw-k3", a);
        Pair.onOffengelegt("sw-k3", 200, { "ok" => true });
        Pair.onStatus("sw-k3", 200, { "zustand" => "gekoppelt", "kontoschluessel" => a, "huelle" => 2 });
        var hi = Pair.statusHint == null ? "leer" : Pair.statusHint as Lang.String;
        _melde(Uploader.hasCredentials() && Huelle2.hatPaar() && Pair.huelle2Gewuenscht()
               && "Herkunft gesichert".equals(hi),
               "gekoppelt-huelle-2", "paar=" + Huelle2.hatPaar().toString() + " h2="
               + Pair.huelle2Gewuenscht().toString() + " hinweis=" + hi);
    }

    function _fall18() as Void {
        // 18. ... und sendet Fassung 2: abgeschlossener Einsatz ohne offene
        //     Punkte -> leeres Schlussstueck, beide Huellen Kennbyte 2.
        //     (Den Selbsttest der Fassung 2 misst die Krypto-Probe.)
        Huelle2._geprueft = true;
        var m = _einsatz("m-sw-f2");
        m["final"] = true;
        m["endedAt"] = "2026-11-10T09:00:00Z";
        Model.pendingMissions = [m];
        Uploader._send({ "kind" => "mission", "data" => m });
        var r = rumpf;
        var gut = false;
        var was = "kein Rumpf";
        if (r != null) {
            var t = r["track"];
            var oh = r["ort_huelle"];
            if (t instanceof Lang.Dictionary && oh instanceof Lang.String) {
                var h = Huelle._ausBase64(t["huelle"] as Lang.String);
                var e = Huelle._ausBase64(oh as Lang.String);
                gut = t["punkte"] == 0 && t["seq_from"] == 0 && h[0] == 2 && e[0] == 2;
                was = "punkte=" + t["punkte"].toString() + " fassung=" + h[0].toString() + "/" + e[0].toString();
            } else {
                was = "track=" + (t == null ? "fehlt" : "da") + " ort=" + (oh == null ? "fehlt" : "da");
            }
        }
        _melde(gut, "fassung2-schlussstueck", was);
    }

    function _fall19(a as Lang.String) as Void {
        // 19. Gegenprobe: ohne `huelle: 2` Fassung 1 und kein Schlussstueck.
        _leer();
        _koppeln(a);
        var m = _einsatz("m-sw-f1");
        m["final"] = true;
        m["endedAt"] = "2026-11-10T09:00:00Z";
        Model.pendingMissions = [m];
        Uploader._send({ "kind" => "mission", "data" => m });
        var r = rumpf;
        var gut = false;
        if (r != null && r["ort_huelle"] instanceof Lang.String) {
            gut = r["track"] == null && Huelle._ausBase64(r["ort_huelle"] as Lang.String)[0] == 1;
        }
        _melde(gut, "fassung1-ohne-merker", "track=" + (r == null || r["track"] == null ? "fehlt" : "da"));
    }
}
