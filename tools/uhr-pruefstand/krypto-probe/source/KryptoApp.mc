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
//   T-<form>-<n>  Spurstueck zu n Punkten in der Innenform binaer/json:
//           Bauen und Verschliessen in ms, Groesse, Speicher (Zuwachs in kB
//           gegen den Stand vor dem Schritt, solange alles lebt)
//   ERGEBNIS  N gut, M Fehler
import Toybox.Application;
import Toybox.Graphics;
import Toybox.Lang;
import Toybox.Math;
import Toybox.System;
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
        melde("Geraet " + d.partNumber + " CIQ " + v[0] + "." + v[1] + "." + v[2]
              + " Speicher " + (s.totalMemory / 1024) + " kB");
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
                melde("ERGEBNIS " + gut + " gut, " + fehler + " Fehler");
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
        } else {
            return false;
        }
        return true;
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
                innen.encodeNumber(Math.round(he * 10).toNumber(), Lang.NUMBER_FORMAT_SINT16, {:offset => o + 8, :endianness => Lang.ENDIAN_BIG});
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
        melde("T-" + (json ? "json" : "binaer") + "-" + n + " bauen " + (t1 - t0)
              + " ms, verschl. " + (t2 - t1) + " ms, " + innen.size() + "/" + h.size()
              + " B, +" + (spitze / 1024) + " kB");
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
