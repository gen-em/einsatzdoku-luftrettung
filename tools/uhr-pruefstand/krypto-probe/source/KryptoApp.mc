// Krypto-Probe — kann diese Uhr die Geraetehuelle bauen, und wie schnell?
// Werkzeug, kein Bestandteil der App (S11-02, Konzept S11). Anleitung:
// LIESMICH.md. Anlass: Nr. 43.
//
// DER ABLAUF. Je Schritt ein Timer-Aufruf, damit kein Schritt den Waechter
// der Laufzeit ausloest. Jede Zeile steht auf dem Display (Blaettern mit
// Hoch/Runter oder Wischen) und auf der Konsole des Simulators.
//
//   PAAR    Schluesselpaar aus festem d: stimmt der oeffentliche Teil nach
//           der Umrechnung in SEC1 mit dem Vektor?
//   ECDH    Z gegen den Vektor — in welcher Byteordnung kommt es?
//   HKDF    Schluesselableitung ueber HMAC gegen den Vektor
//   AES     AES-256-CBC samt Polsterung gegen den Vektor
//   HMAC    Pruefwert gegen den Vektor
//   HUELLE  die ganze Huelle je Fall byte-gleich
//   PACKEN  gibt es eine Packfunktion (deflate)?
//   T-PAAR  Zeit fuer ein frisches Schluesselpaar (dreimal)
//   KURVE   weist createPublicKey() einen Punkt neben der Kurve ab? (F-S11-85)
//   T-<form>-<n>  Spurstueck zu n Punkten in der Innenform binaer/json:
//           Bauen und Verschliessen in ms, Groesse, Speicher (Zuwachs in kB
//           gegen den Stand vor dem Schritt, solange alles lebt)
//   SIGNATUR  kennt das Geraet eine Signatur (ECDSA o. ae.)? (KG-02)
//   GERAETEPAAR  ein dauerhaftes Paar: beim ersten Lauf erzeugt, privater
//           Teil im Application.Storage; bei jedem weiteren Lauf daraus neu
//           gebaut — ist der oeffentliche Teil derselbe, auch ueber eine neue
//           FASSUNG der Probe (App-Update) hinweg? Zeit und Speicher. (KG-02)
//   T-STATISCH  ECDH mit dem dauerhaften Paar gegen den Empfaenger plus
//           HMAC-SHA256 ueber 7000 B (500 Punkte binaer): was ein Nachweis
//           der Herkunft ohne Signatur kostet (KG-02)
//
//   AB HIER DER KRYPTO-KERN DER APP (KG-03a): watch/source/Huelle.mc und
//   Huelle2.mc, mit uebersetzt (monkey.jungle) — nicht Krypto.mc daneben.
//   APP-BEREIT  der Selbsttest der App fuer Fassung 1, Huelle.bereit()
//   APP-HUELLE  Fassung 1 aus Huelle.mc je Fall byte-gleich
//   APP-INNEN   die Innenform der Punkte aus Huelle.mc gegen den Vektor
//   SID     Kennung des Absenders aus seinem Schluessel
//   ZS      das statische Geheimnis Z_s und der oeffentliche Teil des
//           Geraetepaars aus festem d
//   HUELLE2 die ganze Huelle der Fassung 2 je Fall byte-gleich
//   INNEN2  Kopf (12 Byte) und Punkte des Spurstuecks gegen den Vektor
//   ZUSAGE, WERT  die Kopplung: Zusage des Geraets und Vergleichswert
//   APP-BEREIT2  der Selbsttest der Fassung 2, Huelle2.bereit()
//   KOPPLUNG  der Ablauf auf der Uhr: neues Paar, Zusage, Offenlegen erst
//           mit Kontoschluessel und N_b, die erste Antwort gilt, Ablegen
//           und Wiederlesen aus dem Storage
//   GERAETEHUELLE, EINSATZ2, NEUSTART  mit dem abgelegten Paar und Zufall
//           verschliessen: Form, Kennung, Z_s aus dem Speicher und nach
//           einem nachgestellten Neustart aus dem Storage
//   T-FASSUNG  500 Punkte in Fassung 1 und 2: Zeit und Speicher; Z_s neu
//   ZAEHLER-…  der Zaehler z der Einsatzhuelle (E-KA-64): die Regel
//           max(jetzt, letztes + 1) ohne Uhr (FOLGE), am Speicher mit der
//           Zeit des Geraets und zweimal in derselben Sekunde (ZEIT), aus
//           dem Stand einer Uhr vor KG-03b (ALT), ueber das Trennen (TRENNEN)
//   Zeilen, die mit EXTERN beginnen, stehen NUR auf der Konsole: Huellen
//   mit Zufall, die die Referenz ausserhalb der Uhr oeffnet (LIESMICH).
//   ERGEBNIS  N gut, M Fehler — am Ende und zusaetzlich als erste Zeile
//   Die T-Zeilen stehen je Angabe in einer eigenen Zeile (rundes Display).
import Toybox.Application;
import Toybox.Application.Storage;
import Toybox.Cryptography;
import Toybox.Graphics;
import Toybox.Lang;
import Toybox.Math;
import Toybox.System;
import Toybox.Time;
import Toybox.Timer;
import Toybox.WatchUi;

class KryptoApp extends Application.AppBase {
    function initialize() { AppBase.initialize(); }
    function onStart(state as Dictionary or Null) as Void { Probe.starten(); }
    function onStop(state as Dictionary or Null) as Void { }
    function getInitialView() as [WatchUi.Views] or [WatchUi.Views, WatchUi.InputDelegates] {
        return [new ProbeView(), new ProbeDelegate()];
    }
}

module Probe {

    // Fassung dieser Probe. Wer ein App-Update nachstellt, hebt sie und baut
    // neu, OHNE den Speicher zu leeren (LIESMICH, KG-02).
    const FASSUNG = 1;

    var zeilen as Array<String> = [];
    var oben as Number = 0;
    var schritt as Number = 0;
    var gut as Number = 0;
    var fehler as Number = 0;
    var uhr as Timer.Timer or Null = null;

    function melde(t as String) as Void {
        System.println(t);
        zeilen.add(t);
        WatchUi.requestUpdate();
    }

    function urteil(name as String, ok as Boolean, zusatz as String) as Void {
        if (ok) { gut++; } else { fehler++; }
        melde(name + (ok ? " gut" : " FEHLER") + (zusatz.length() > 0 ? " " + zusatz : ""));
    }

    function starten() as Void {
        var d = System.getDeviceSettings();
        var s = System.getSystemStats();
        var v = d.monkeyVersion;
        melde("Geraet " + d.partNumber);
        melde("CIQ " + v[0] + "." + v[1] + "." + v[2] + ", " + (s.totalMemory / 1024) + " kB");
        uhr = new Timer.Timer();
        (uhr as Timer.Timer).start(new Lang.Method(Probe, :weiter), 100, false);
    }

    function fall(name as String) as Dictionary {
        for (var i = 0; i < Vektoren.FAELLE.size(); i++) {
            var f = Vektoren.FAELLE[i] as Dictionary;
            if ((f[:name] as String).equals(name)) { return f; }
        }
        return {};
    }

    function weiter() as Void {
        try {
            if (!lauf(schritt)) {
                // Das Ergebnis steht am Ende (fuer die Konsole) UND oben, damit
                // es auf der Uhr ohne Blaettern zu sehen ist.
                var e = "ERGEBNIS " + gut + " gut, " + fehler + " Fehler";
                melde(e);
                zeilen = [e].addAll(zeilen) as Array<String>;
                oben = 0;
                WatchUi.requestUpdate();
                return;
            }
        } catch (e instanceof Lang.Exception) {
            fehler++;
            melde("Schritt " + schritt + " FEHLER " + e.getErrorMessage());
        }
        schritt++;
        (uhr as Timer.Timer).start(new Lang.Method(Probe, :weiter), 50, false);
    }

    // Ein Schritt; false, wenn es keinen mehr gibt.
    function lauf(n as Number) as Boolean {
        var f = fall("kurz");
        if (n == 0) {
            var p = Krypto.paar(f[:fluechtig] as ByteArray);
            var roh = p.getPublicKey().getBytes() as ByteArray;
            var sec = Krypto.garminZuSec1(roh);
            var ok = Krypto.gleich(sec, f[:epk] as ByteArray);
            var zusatz = "(" + roh.size() + " B)";
            if (!ok) {
                var ohne = [0x04]b;
                ohne.addAll(roh);
                zusatz += Krypto.gleich(ohne, f[:epk] as ByteArray) ? " big-endian!" : " " + Krypto.hex(roh);
            }
            urteil("PAAR", ok, zusatz);
        } else if (n == 1) {
            Krypto.zOrdnung = "be";
            var z = Krypto.ecdh(Krypto.paar(f[:fluechtig] as ByteArray), Vektoren.EMPF_PUB);
            var soll = f[:z] as ByteArray;
            if (Krypto.gleich(z, soll)) {
                urteil("ECDH", true, "(big-endian)");
            } else if (Krypto.gleich(Krypto.umgedreht(z, 0, z.size()), soll)) {
                Krypto.zOrdnung = "le";
                urteil("ECDH", true, "(little-endian, umgedreht)");
            } else {
                urteil("ECDH", false, "(" + z.size() + " B) " + Krypto.hex(z));
            }
        } else if (n == 2) {
            var info = Krypto.text(Krypto.INFO);
            info.addAll(Vektoren.EMPF_PUB);
            var okm = Krypto.hkdf(f[:z] as ByteArray, f[:epk] as ByteArray, info);
            urteil("HKDF", Krypto.gleich(okm.slice(0, 32), f[:kEnc] as ByteArray)
                           && Krypto.gleich(okm.slice(32, 64), f[:kMac] as ByteArray), "");
        } else if (n == 3) {
            var ct = Krypto.aesCbc(f[:kEnc] as ByteArray, f[:iv] as ByteArray,
                                   Krypto.gepolstert(f[:klar] as ByteArray));
            urteil("AES", Krypto.gleich(ct, f[:ct] as ByteArray), "");
        } else if (n == 4) {
            var mac = [1]b;
            mac.addAll(f[:epk] as ByteArray);
            mac.addAll(f[:iv] as ByteArray);
            mac.addAll(f[:ct] as ByteArray);
            mac.addAll(f[:bindung] as ByteArray);
            urteil("HMAC", Krypto.gleich(Krypto.hmac(f[:kMac] as ByteArray, mac), f[:tag] as ByteArray), "");
        } else if (n == 5 || n == 6) {
            var g = fall(n == 5 ? "kurz" : "spur-binaer-3");
            var h = Krypto.verschliessen(g[:klar] as ByteArray, Vektoren.EMPF_PUB,
                                         g[:bindung] as ByteArray, g[:fluechtig] as ByteArray,
                                         g[:iv] as ByteArray, null);
            var soll = [1]b;
            soll.addAll(g[:epk] as ByteArray);
            soll.addAll(g[:iv] as ByteArray);
            soll.addAll(g[:ct] as ByteArray);
            soll.addAll(g[:tag] as ByteArray);
            urteil("HUELLE " + g[:name], Krypto.gleich(h, soll), "(" + h.size() + " B)");
        } else if (n == 7) {
            // Connect IQ 9.2.0 kennt kein deflate (api.mir durchsucht, S11-02);
            // gefragt wird trotzdem das Geraet.
            var da = (Toybox has :Compression) || (Toybox has :Zlib);
            melde("PACKEN " + (da ? "vorhanden" : "nicht vorhanden"));
        } else if (n == 8) {
            var t = "";
            for (var i = 0; i < 3; i++) {
                var t0 = System.getTimer();
                var p = Krypto.paar(null);
                p.getPublicKey().getBytes();
                t += (i > 0 ? "/" : "") + (System.getTimer() - t0);
            }
            melde("T-PAAR " + t + " ms");
        } else if (n >= 9 && n <= 12) {
            var punkte = (n <= 10) ? 200 : 500;
            var json = (n % 2 == 0);
            spurstueck(punkte, json);
        } else if (n == 13) {
            // Prueft createPublicKey(), ob der Punkt auf der Kurve liegt
            // (F-S11-85)? Y um eins veraendert. Eine Meldung, kein Urteil:
            // Huelle.schluesselSetzen() verlaesst sich darauf, und die Zeile
            // sagt, ob es das darf.
            var falsch = Vektoren.EMPF_PUB.slice(0, 65);
            falsch[64] = (falsch[64] + 1) & 0xff;
            var was = "nicht geprueft";
            try {
                Krypto.ecdh(Krypto.paar(f[:fluechtig] as ByteArray), falsch);
            } catch (e) {
                was = "abgewiesen";
            }
            melde("KURVE " + was);
        } else if (n == 14) {
            var welche = "";
            if (Toybox.Cryptography has :Signature)   { welche += " Signature"; }
            if (Toybox.Cryptography has :Sign)        { welche += " Sign"; }
            if (Toybox.Cryptography has :Ecdsa)       { welche += " Ecdsa"; }
            if (Toybox.Cryptography has :ECDSA)       { welche += " ECDSA"; }
            if (Toybox.Cryptography has :Signer)      { welche += " Signer"; }
            if (Toybox.Cryptography has :KEY_PAIR_ELLIPTIC_CURVE_ED25519) { welche += " Ed25519"; }
            melde("SIGNATUR " + (welche.length() > 0 ? "vorhanden:" + welche : "nicht vorhanden"));
        } else if (n == 15) {
            geraetepaar();
        } else if (n == 16) {
            statisch();
        } else if (n == 17) {
            var ok = Huelle.bereit();
            urteil("APP-BEREIT", ok && Krypto.zOrdnung.equals(Huelle._ordnung),
                   "(" + Huelle._ordnung + ")");
        } else if (n == 18 || n == 19) {
            var g = fall(n == 18 ? "kurz" : "spur-binaer-3");
            var h = Huelle.verschliessenRoh(g[:klar] as ByteArray, Vektoren.EMPF_PUB,
                                            g[:bindung] as ByteArray, g[:fluechtig] as ByteArray,
                                            g[:iv] as ByteArray);
            var soll = [1]b;
            soll.addAll(g[:epk] as ByteArray);
            soll.addAll(g[:iv] as ByteArray);
            soll.addAll(g[:ct] as ByteArray);
            soll.addAll(g[:tag] as ByteArray);
            urteil("APP-HUELLE " + g[:name], Krypto.gleich(h, soll), "(" + h.size() + " B)");
        } else if (n == 20) {
            var innen = Huelle.punkteInnen(punkte(0, 3, 1700000000), 0);
            urteil("APP-INNEN", Krypto.gleich(innen[0] as ByteArray, fall("spur-binaer-3")[:klar] as ByteArray)
                                && innen[1] == 1700000000 && innen[2] == 1700000010, "");
        } else if (n == 21) {
            urteil("SID", Krypto.gleich(Huelle2.sidVon(Vektoren2.SPK), Vektoren2.SID), "");
        } else if (n == 22) {
            var p = Huelle.paar(Vektoren2.GERAET_D);
            var spk = Huelle.geraetZuSec1(p.getPublicKey().getBytes() as ByteArray);
            urteil("ZS", Krypto.gleich(spk, Vektoren2.SPK)
                         && Krypto.gleich(Huelle.ecdh(p, Vektoren2.KONTO), Vektoren2.Z_S), "");
        } else if (n == 23 || n == 24) {
            var g = Vektoren2.FAELLE[n - 23] as Dictionary;
            var h = Huelle2.verschliessenRoh(g[:klar] as ByteArray, Vektoren2.KONTO, Vektoren2.SPK,
                                             Vektoren2.Z_S, g[:bindung] as ByteArray,
                                             g[:fluechtig] as ByteArray, g[:iv] as ByteArray);
            urteil("HUELLE2 " + g[:name], Krypto.gleich(h, g[:huelle] as ByteArray), "(" + h.size() + " B)");
        } else if (n == 25) {
            var innen = Huelle2.spurInnen(punkte(Vektoren2.SPUR_VON, 3, Vektoren2.SPUR_TS), false);
            urteil("INNEN2", Krypto.gleich(innen[0] as ByteArray,
                                           (Vektoren2.FAELLE[1] as Dictionary)[:klar] as ByteArray), "");
        } else if (n == 26) {
            urteil("ZUSAGE", Krypto.gleich(Huelle2.zusage(Vektoren2.N_G, Vektoren2.SPK), Vektoren2.ZUSAGE), "");
        } else if (n == 27) {
            var w = Huelle2.wert(Vektoren2.KONTO, Vektoren2.SPK, Vektoren2.N_G, Vektoren2.N_B);
            urteil("WERT", w.equals(Vektoren2.WERT), w);
        } else if (n == 28) {
            Huelle2.pruefungVergessen();        // messen, nicht den Vermerk lesen
            urteil("APP-BEREIT2", Huelle2.bereit(), "");
        } else if (n == 29) {
            kopplung();
        } else if (n == 30) {
            geraetehuelle();
        } else if (n == 31) {
            einsatz2();
        } else if (n == 32) {
            neustart();
        } else if (n == 33 || n == 34) {
            fassungen(n == 34);
        } else if (n == 35) {
            zsNeu();
        } else if (n == 36) {
            zaehler();
        } else {
            return false;
        }
        return true;
    }

    // Die Beispielspur der Referenz (beispielpunkte), Punkte von … von+n-1,
    // flach wie Track.readPoints(): Breite, Laenge, Hoehe, Zeit. In Double,
    // damit dieselben Zahlen entstehen wie in referenz.py.
    function punkte(von as Number, n as Number, ts as Number) as Array {
        var a = [];
        for (var i = 0; i < n; i++) {
            var k = von + i;
            a.add(47.7261d + k * 0.00031d);
            a.add(10.3159d + k * 0.00047d);
            a.add(674.0d + (k % 37) * 0.7d);
            a.add(ts + i * 5);
        }
        return a;
    }

    // Der Ablauf der Kopplung auf der Uhr (KG-03a), mit Zufall. Was die
    // Rechnung selbst betrifft, steht in ZUSAGE und WERT gegen den Vektor;
    // hier geht es um die Reihenfolge und das Ablegen.
    function kopplung() as Void {
        var ok = Huelle2.kopplungAbschliessen(Vektoren2.KONTO, Vektoren2.N_B) == null;   // laeuft keine
        var b = Huelle2.kopplungBeginnen();
        if (b == null) { urteil("KOPPLUNG", false, "kein dauerhaftes Paar"); return; }
        var spk = b[:spk] as ByteArray;
        ok = ok && spk.size() == 65 && (b[:zusage] as ByteArray).size() == 32;
        ok = ok && Huelle2.kopplungAbschliessen(Vektoren2.KONTO, Vektoren2.N_G.slice(0, 15)) == null;
        var a = Huelle2.kopplungAbschliessen(Vektoren2.KONTO, Vektoren2.N_B);
        if (a == null) { urteil("KOPPLUNG", false, "kein Abschluss"); return; }
        var nG = a[:ng] as ByteArray;
        var w = a[:wert] as String;
        ok = ok && nG.size() == 16 && w.length() == 6;
        ok = ok && Krypto.gleich(Huelle2.zusage(nG, spk), b[:zusage] as ByteArray);
        // Dieselben Werte noch einmal: dieselbe Antwort. Andere: keine.
        var a2 = Huelle2.kopplungAbschliessen(Vektoren2.KONTO, Vektoren2.N_B);
        ok = ok && a2 != null && w.equals((a2 as Dictionary)[:wert] as String);
        ok = ok && Huelle2.kopplungAbschliessen(Vektoren2.KONTO, Vektoren2.N_G) == null;
        ok = ok && Huelle2.kopplungAbschliessen(Vektoren2.SPK, Vektoren2.N_B) == null;
        ok = ok && Huelle2.kopplungUebernehmen() && Huelle2.hatPaar();
        ok = ok && !Huelle2.kopplungUebernehmen();                     // nur einmal
        Huelle2._spk = null;                                           // aus dem Storage lesen
        var s = Huelle2.spk();
        ok = ok && s != null && Krypto.gleich(s as ByteArray, spk);
        urteil("KOPPLUNG", ok, w);
        System.println("EXTERN kopplung " + Krypto.hex(spk) + " " + Krypto.hex(nG) + " "
                       + Krypto.hex(b[:zusage] as ByteArray) + " " + w);
    }

    // Mit dem abgelegten Paar verschliessen, wie es der Sendeweg tun wird.
    function geraetehuelle() as Void {
        Huelle.schluesselSetzen(Huelle.base64(Vektoren2.KONTO));
        var sid = Huelle2.sid() as ByteArray;
        var t0 = System.getTimer();
        var a = Huelle2.spurstueck("spur", "probe-spur", 7, punkte(7, 5, 1784277100), false);
        var t1 = System.getTimer();
        var b = Huelle2.spurstueck("spur", "probe-spur", 12, punkte(12, 2, 1784277125), true);
        var t2 = System.getTimer();
        var c = Huelle2.schlussstueck("ruhe", "probe-ruhe", 40, 1784277065);
        var ha = Huelle._ausBase64(a["huelle"] as String);
        var hc = Huelle._ausBase64(c["huelle"] as String);
        // 1 + 8 + 65 + 16, Chiffretext (12 + 5·14 = 82 -> 96; 12 -> 16), 32
        var ok = ha.size() == 90 + 96 + 32 && hc.size() == 90 + 16 + 32
                 && ha[0] == 2 && Krypto.gleich(ha.slice(1, 9), sid)
                 && a["ts_von"] == 1784277100 && a["ts_bis"] == 1784277120 && c["punkte"] == 0
                 && !(a["huelle"] as String).equals(b["huelle"] as String);
        urteil("GERAETEHUELLE", ok, "(" + ha.size() + " B)");
        melde("erste " + (t1 - t0) + " ms, zweite " + (t2 - t1) + " ms");
        var spk = Krypto.hex(Huelle2.spk() as ByteArray);
        System.println("EXTERN spur " + spk + " probe-spur 7:5 1784277100 " + a["huelle"]);
        System.println("EXTERN spur " + spk + " probe-spur 12:2 1784277125 " + b["huelle"]);
        System.println("EXTERN ruhe " + spk + " probe-ruhe 40:0 1784277065 " + c["huelle"]);
    }

    // Die Einsatzhuelle: derselbe Inhalt wie PHASEN2 und REA2 der Referenz.
    function einsatz2() as Void {
        var d = { "startedAt" => "2026-07-17T08:31:05Z",
                  "phases" => [[4, "2026-07-17T08:51:02Z", 47.5601d, 10.7002d],
                               [5, "2026-07-17T08:52:55Z", null, null]],
                  "resus" => [{ "start" => "2026-07-17T08:55:10Z",
                                "events" => [["adrenalin", "2026-07-17T08:58:02Z"]] }] };
        var z1 = Huelle2.zaehlerNaechster();
        var z2 = Huelle2.zaehlerNaechster();
        var innen = Huelle2.einsatzInnen(d, z2, true);
        var h = Huelle2.einsatz("probe-einsatz", d, z2, true);
        var kopf = "{\"fassung\":2,\"z\":" + z2 + ",\"start\":1784277065,\"schluss\":true,\"phasen\":[";
        urteil("EINSATZ2", z2 > z1 && innen.find(kopf) == 0, "z " + z2);
        System.println("EXTERN einsatz " + Krypto.hex(Huelle2.spk() as ByteArray) + " probe-einsatz "
                       + z2 + " 1784277065 " + h);
    }

    // Ein Neustart, nachgestellt: Was im Speicher lag, ist fort; Paar und Z_s
    // kommen aus dem Storage. Die Huelle muss ausserhalb aufgehen (EXTERN).
    function neustart() as Void {
        var vorher = Huelle2.statisch(Vektoren2.KONTO);
        Huelle2._spk = null;
        Huelle2._zs = null;
        Huelle2._zsKonto = null;
        var t0 = System.getTimer();
        var a = Huelle2.spurstueck("spur", "probe-spur", 14, punkte(14, 3, 1784277135), true);
        var t1 = System.getTimer();
        var nachher = Huelle2.statisch(Vektoren2.KONTO);
        urteil("NEUSTART", vorher != null && nachher != null
                           && Krypto.gleich(vorher as ByteArray, nachher as ByteArray), "(" + (t1 - t0) + " ms)");
        System.println("EXTERN spur " + Krypto.hex(Huelle2.spk() as ByteArray)
                       + " probe-spur 14:3 1784277135 " + a["huelle"]);
    }

    // 500 Punkte durch den Weg der App, Fassung 1 oder 2. Z_s liegt bereit.
    function fassungen(zwei as Boolean) as Void {
        var flat = punkte(0, 500, 1784277065);
        var vor = System.getSystemStats().usedMemory;
        var t0 = System.getTimer();
        var a = zwei ? Huelle2.spurstueck("spur", "probe-spur", 0, flat, false)
                     : Huelle.spurstueck("spur", "probe-spur", 0, flat);
        var t1 = System.getTimer();
        melde("T-FASSUNG " + (zwei ? "2" : "1") + ", 500 Punkte");
        melde((t1 - t0) + " ms, " + (a["huelle"] as String).length() + " Z.");
        melde("Speicher +" + ((System.getSystemStats().usedMemory - vor) / 1024) + " kB");
    }

    // Z_s neu rechnen (Paar aus dem Storage bauen, ECDH, ablegen) — was der
    // erste Sendelauf nach einer Kopplung einmal kostet. Danach aufraeumen:
    // Der naechste Lauf soll wieder ein neues Paar erzeugen.
    function zsNeu() as Void {
        Storage.deleteValue(Huelle2.ABLAGE_ZS);
        Huelle2._zs = null;
        var t0 = System.getTimer();
        Huelle2.statisch(Vektoren2.KONTO);
        melde("T-ZS " + (System.getTimer() - t0) + " ms");
        Huelle2.paarLoeschen();
        Huelle.schluesselLoeschen();
    }

    // Der Zaehler z der Einsatzhuelle (E-KA-64, KG-03b): Unix-Sekunde des
    // Verschliessens, lokal streng steigend. Laeuft NACH zsNeu(): Das Paar
    // ist dort schon geloescht, und der Schritt stellt "gz" selbst um. Am
    // Ende steht der hoechste Stand wieder im Speicher -- die Probe darf den
    // Zaehler des Simulators nicht zuruecklegen.
    function zaehler() as Void {
        var T = 1794300000;
        var M = Huelle2.Z_MAX;
        // Die Regel ohne Uhr und Speicher: was sich am Geraet nicht stellen laesst.
        var ok = Huelle2.zaehlerFolge(null, T) == T              // kein Vorgaenger
                 && Huelle2.zaehlerFolge(5, T) == T              // alter +1-Zaehler
                 && Huelle2.zaehlerFolge(T, T) == T + 1          // dieselbe Sekunde
                 && Huelle2.zaehlerFolge(T + 500, T) == T + 501  // die Zeit sprang zurueck
                 && Huelle2.zaehlerFolge(null, 0) == 1           // Uhr ohne Zeit
                 && Huelle2.zaehlerFolge(7, 0) == 8
                 && Huelle2.zaehlerFolge(null, -5) == 1
                 && Huelle2.zaehlerFolge(-3, 0) == 1             // Unsinn im Speicher
                 && Huelle2.zaehlerFolge("x", 10) == 10
                 && Huelle2.zaehlerFolge(M - 1, 100) == M        // die Grenze: stehen
                 && Huelle2.zaehlerFolge(M, 100) == M;           // bleiben, kein Umlauf
        urteil("ZAEHLER-FOLGE", ok, "");

        // Am Speicher, mit der Zeit des Geraets.
        var jetzt = Time.now().value();
        var z1 = Huelle2.zaehlerNaechster();
        var z2 = Huelle2.zaehlerNaechster();        // dieselbe Sekunde
        urteil("ZAEHLER-ZEIT", z1 >= jetzt && z2 > z1, "z " + z1 + ", jetzt " + jetzt + ", dann +" + (z2 - z1));

        Storage.setValue(Huelle2.ABLAGE_ZAEHLER, 5);            // Stand einer Uhr vor KG-03b
        var z3 = Huelle2.zaehlerNaechster();
        urteil("ZAEHLER-ALT", z3 >= jetzt, "5 -> " + z3);
        var hoch = (z3 > z2) ? z3 : z2;
        Storage.setValue(Huelle2.ABLAGE_ZAEHLER, hoch);

        Huelle2.paarLoeschen();
        var bleibt = Storage.getValue(Huelle2.ABLAGE_ZAEHLER);
        var z4 = Huelle2.zaehlerNaechster();
        urteil("ZAEHLER-TRENNEN", bleibt instanceof Number && bleibt == hoch && z4 > hoch, "z " + z4);
    }

    // Das dauerhafte Paar (KG-02). Meldungen, kein Urteil: Ein erster Lauf
    // erzeugt, ein zweiter vergleicht.
    function geraetepaar() as Void {
        var vor = System.getSystemStats().usedMemory;
        var t0 = System.getTimer();
        var priv = Storage.getValue("kg_priv");
        var pub = Storage.getValue("kg_pub");
        if (!(priv instanceof ByteArray) || !(pub instanceof ByteArray)) {
            var p = Krypto.paar(null);
            var pb = p.getPrivateKey().getBytes();
            var ob = p.getPublicKey().getBytes();
            if (pb == null) { melde("GERAETEPAAR privater Teil nicht lesbar (getBytes null)"); return; }
            Storage.setValue("kg_priv", pb as ByteArray);
            Storage.setValue("kg_pub", ob as ByteArray);
            Storage.setValue("kg_fassung", FASSUNG);
            Storage.setValue("kg_laeufe", 1);
            melde("GERAETEPAAR erzeugt, Fassung " + FASSUNG);
            melde("privat " + (pb as ByteArray).size() + " B, oeff. " + (ob as ByteArray).size() + " B");
            melde("T-ERZEUGEN " + (System.getTimer() - t0) + " ms");
            return;
        }
        var q = new Cryptography.KeyPair({
            :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1,
            :privateKey => priv as ByteArray});
        var neu = q.getPublicKey().getBytes() as ByteArray;
        var t1 = System.getTimer();
        var laeufe = Storage.getValue("kg_laeufe");
        var l = (laeufe instanceof Number) ? (laeufe as Number) + 1 : 1;
        Storage.setValue("kg_laeufe", l);
        var gleich = Krypto.gleich(neu, pub as ByteArray);
        melde("GERAETEPAAR " + (gleich ? "gleich" : "ANDERS") + ", Lauf " + l);
        melde("erzeugt in Fassung " + Storage.getValue("kg_fassung") + ", jetzt " + FASSUNG);
        melde("T-WIEDERHERST. " + (t1 - t0) + " ms");
        melde("Speicher +" + ((System.getSystemStats().usedMemory - vor) / 1024) + " kB");
    }

    // Statisch-statische Einigung plus HMAC ueber ein Spurstueck (KG-02).
    function statisch() as Void {
        var priv = Storage.getValue("kg_priv");
        if (!(priv instanceof ByteArray)) { melde("T-STATISCH ohne Paar"); return; }
        var vor = System.getSystemStats().usedMemory;
        var t0 = System.getTimer();
        var q = new Cryptography.KeyPair({
            :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1,
            :privateKey => priv as ByteArray});
        var z = Krypto.ecdh(q, Vektoren.EMPF_PUB);
        var t1 = System.getTimer();
        var daten = new [7000]b;
        for (var i = 0; i < 7000; i++) { daten[i] = i & 0xff; }
        var t2 = System.getTimer();
        var tag = Krypto.hmac(z, daten);
        var t3 = System.getTimer();
        melde("T-STATISCH");
        melde("ECDH " + (t1 - t0) + " ms");
        melde("HMAC 7000 B " + (t3 - t2) + " ms");
        melde("Tag " + tag.size() + " B +" + ((System.getSystemStats().usedMemory - vor) / 1024) + " kB");
    }

    // Ein Spurstueck bauen und verschliessen, mit Zeit und Speicher.
    function spurstueck(n as Number, json as Boolean) as Void {
        var vor = System.getSystemStats().usedMemory;
        var t0 = System.getTimer();
        var innen;
        if (json) {
            var s = "{\"p\":[";
            for (var i = 0; i < n; i++) {
                var la = 47.7261 + i * 0.00031, lo = 10.3159 + i * 0.00047;
                var he = 674.0 + (i % 37) * 0.7;
                s += (i > 0 ? "," : "") + "[" + la.format("%.6f") + "," + lo.format("%.6f")
                     + "," + he.format("%.1f") + "," + (i * 5) + "]";
            }
            innen = Krypto.text(s + "]}");
        } else {
            innen = new [n * 14]b;
            for (var i = 0; i < n; i++) {
                var la = 47.7261 + i * 0.00031, lo = 10.3159 + i * 0.00047;
                var he = 674.0 + (i % 37) * 0.7;
                var o = i * 14;
                innen.encodeNumber(Math.round(la * 1e7).toNumber(), Lang.NUMBER_FORMAT_SINT32, {:offset => o, :endianness => Lang.ENDIAN_BIG});
                innen.encodeNumber(Math.round(lo * 1e7).toNumber(), Lang.NUMBER_FORMAT_SINT32, {:offset => o + 4, :endianness => Lang.ENDIAN_BIG});
                innen.encodeNumber(Math.round(he).toNumber(), Lang.NUMBER_FORMAT_SINT16, {:offset => o + 8, :endianness => Lang.ENDIAN_BIG});
                innen.encodeNumber(i * 5, Lang.NUMBER_FORMAT_UINT32, {:offset => o + 10, :endianness => Lang.ENDIAN_BIG});
            }
        }
        var t1 = System.getTimer();
        var mitte = System.getSystemStats().usedMemory;
        var bindung = Krypto.text("spur");
        var h = Krypto.verschliessen(innen, Vektoren.EMPF_PUB, bindung, null, null, null);
        var t2 = System.getTimer();
        var nach = System.getSystemStats().usedMemory;
        var spitze = (mitte > nach ? mitte : nach) - vor;
        // KURZE ZEILEN (S11-02, Foto der Fenix 6 Pro vom 13.10.2026): Eine
        // Zeile mit allen Zahlen lief auf dem runden Display links und rechts
        // ueber den Rand. Je Angabe eine Zeile.
        melde("T-" + (json ? "json" : "binaer") + "-" + n);
        melde("bauen " + (t1 - t0) + " ms");
        melde("verschl. " + (t2 - t1) + " ms");
        melde(innen.size() + "/" + h.size() + " B +" + (spitze / 1024) + " kB");
    }
}

class ProbeView extends WatchUi.View {
    function initialize() { View.initialize(); }

    function onUpdate(dc as Graphics.Dc) as Void {
        dc.setColor(Graphics.COLOR_WHITE, Graphics.COLOR_BLACK);
        dc.clear();
        var h = dc.getFontHeight(Graphics.FONT_XTINY);
        var y = dc.getHeight() / 8;
        for (var i = Probe.oben; i < Probe.zeilen.size() && y < dc.getHeight() - h; i++) {
            dc.drawText(dc.getWidth() / 2, y, Graphics.FONT_XTINY, Probe.zeilen[i],
                        Graphics.TEXT_JUSTIFY_CENTER);
            y += h;
        }
    }
}

class ProbeDelegate extends WatchUi.BehaviorDelegate {
    function initialize() { BehaviorDelegate.initialize(); }

    function onNextPage() as Boolean {
        if (Probe.oben < Probe.zeilen.size() - 1) { Probe.oben++; }
        WatchUi.requestUpdate();
        return true;
    }

    function onPreviousPage() as Boolean {
        if (Probe.oben > 0) { Probe.oben--; }
        WatchUi.requestUpdate();
        return true;
    }
}
