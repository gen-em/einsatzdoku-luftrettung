// NAdoku — die Geraetehuelle: Spur, Phasenkoordinaten und Reanimation
// verschlossen senden.
//
// WAS DAS IST. ingest.php nimmt Ortsdaten nur verschlossen an und weist Klartext mit `400 klartext` ab. Die Uhr
// verschliesst mit dem OEFFENTLICHEN Schluessel des Kontos, den sie bei der
// Kopplung bekommt (pair.php, `kontoschluessel`). Oeffnen kann sie selbst
// nichts — und soll es nicht: Den privaten Teil hat nur der Browser der
// Kontoinhaberin. Eine verlorene Uhr gibt deshalb auch keine gesendeten
// Einsaetze preis.
//
// DAS FORMAT steht im Kopf von tools/proben/huelle/referenz.py, und nur dort
// (Fassung 1): Kennbyte 0x01 · fluechtiger oeffentlicher Schluessel (65 Byte
// SEC1) · IV · AES-256-CBC · HMAC-SHA256, Schluessel ueber ECDH auf P-256
// und HKDF. Die Bausteine hier stammen aus der Krypto-Probe
// (tools/uhr-pruefstand/krypto-probe/); dort sind sie gegen die
// Vektoren gemessen, auf drei Uhren im Simulator und auf der Fenix 6 Pro.
//
// GARMINS BYTEORDNUNG. Toybox.Cryptography fuehrt Schluessel je
// Koordinate little-endian und ohne das Praefix 0x04, und liefert das
// gemeinsame Geheimnis Z im Simulator und auf der Fenix 6 Pro ebenfalls
// little-endian. Fuer FR 945 und Venu 3s ist das angenommen, nicht gemessen.
// Deshalb ANNIMMT die App es nicht, sondern MISST es: Vor dem
// ersten Senden rechnet sie einen festen Fall aus der Referenz nach
// (HuelleVektor.mc). Stimmt Z erst umgedreht, ist die Ordnung "le"; stimmt
// danach das Pruefwort, traegt die ganze Kette. Stimmt keines, sendet sie
// nicht — eine Huelle, die im Browser nicht aufgeht, waere ein stiller
// Datenverlust, und "Uhr kann nicht verschluesseln" auf der Sync-Seite ist
// eine Auskunft.
//
// DIE ANZEIGE AUF DER UHR liest den Klartextpuffer: Die
// Uhr zeigt, was sie selbst aufgezeichnet hat; verschlossen wird erst auf
// dem Weg hinaus.
using Toybox.Lang;
using Toybox.Cryptography;
using Toybox.StringUtil;
using Toybox.System;
using Toybox.Math;
using Toybox.Time;
using Toybox.Time.Gregorian;
using Toybox.Application.Storage;

module Huelle {

    const INFO = "nadoku-huelle-1";

    /* Storage-Schluessel. Der Kontoschluessel liegt als Base64 neben "cred"
     * — als Zeichenkette, weil Storage nicht auf jeder
     * Fassung ein ByteArray nimmt. Er ist oeffentlich; ihn zu kennen
     * erlaubt nur, Huellen FUER dieses Konto zu bauen. */
    const ABLAGE_SCHLUESSEL = "kschl";
    /* Ergebnis des Selbsttests, mit Fassung und Firmware: Nach einem
     * Firmware-Update rechnet die Uhr ihn neu — genau dann koennte sich die
     * Byteordnung aendern. */
    const ABLAGE_PRUEFUNG = "kpruef";

    const HOEHE_UNBEKANNT = -32768;
    const HOEHE_GRENZE = 32767;       // ganze Meter, geklemmt

    // "be", "le", "kaputt" — oder null, solange nicht gemessen.
    var _ordnung as Lang.String or Null = null;
    var _empfPub as Lang.ByteArray or Null = null;   // Zwischenspeicher

    // ---- Der Kontoschluessel -------------------------------------------

    /* Den Schluessel aus der Kopplungsantwort ablegen. Gibt zurueck, ob er
     * brauchbar war. Ein unbrauchbarer (null, falsche Laenge, kein Punkt
     * der Kurve) loescht einen alten: Die Uhr soll lieber sagen, dass ihr
     * einer fehlt, als mit einem verschliessen, der nicht mehr gilt. */
    function schluesselSetzen(b64 as Lang.Object or Null) as Lang.Boolean {
        _empfPub = null;
        if (!(b64 instanceof Lang.String) || b64.length() == 0) {
            Storage.deleteValue(ABLAGE_SCHLUESSEL);
            return false;
        }
        var roh = null;
        try {
            roh = _ausBase64(b64 as Lang.String);
            if (roh.size() != 65 || roh[0] != 0x04) { roh = null; }
            else {
                // Liegt er auf der Kurve? createPublicKey wirft sonst.
                Cryptography.createPublicKey(
                    Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1 as Cryptography.HashAlgorithm,
                    sec1ZuGeraet(roh));
            }
        } catch (e) {
            roh = null;
        }
        if (roh == null) {
            Storage.deleteValue(ABLAGE_SCHLUESSEL);
            return false;
        }
        Storage.setValue(ABLAGE_SCHLUESSEL, b64 as Lang.String);
        return true;
    }

    function hatSchluessel() as Lang.Boolean {
        return Storage.getValue(ABLAGE_SCHLUESSEL) instanceof Lang.String;
    }

    function schluesselLoeschen() as Void {
        _empfPub = null;
        Storage.deleteValue(ABLAGE_SCHLUESSEL);
    }

    function _empfaenger() as Lang.ByteArray {
        var e = _empfPub;
        if (e == null) {
            e = _ausBase64(Storage.getValue(ABLAGE_SCHLUESSEL) as Lang.String);
            _empfPub = e;
        }
        return e;
    }

    // ---- Der Selbsttest -------------------------------------------------

    /* Kann diese Uhr verschliessen? Beim ersten Aufruf je App-Fassung und
     * Firmware wird gemessen (zwei Schluesselvereinbarungen, auf der
     * Fenix 6 Pro rund 0,2 s), danach steht das Ergebnis im Storage.
     *
     * NUR EIN GUELTIGES ERGEBNIS BLEIBT. Landete auch "kaputt" im Storage,
     * und zwar fuer jede Ausnahme beim Messen -- knapper Speicher beim
     * ersten Start mit vollem Puffer, ein Watchdog mitten in den
     * Schluesselvereinbarungen --, wuerde eine einmalige Ursache zum
     * Dauerzustand: Die Uhr sendete nichts mehr, und nichts im Geraet
     * setzte den Vermerk zurueck, bis ein Update die Kennung aendert. Also
     * gilt "kaputt" nur bis zum naechsten Start (`_ordnung` im Speicher)
     * und wird dann einmal neu gemessen. */
    function bereit() as Lang.Boolean {
        if (_ordnung == null) { _ordnung = _messen(); }
        return !"kaputt".equals(_ordnung);
    }

    function _kennung() as Lang.String {
        var fw = System.getDeviceSettings().firmwareVersion;
        var f = "";
        if (fw != null) {
            var a = fw as Lang.Array<Lang.Number>;
            for (var i = 0; i < a.size(); i++) { f += (i > 0 ? "." : "") + a[i].toString(); }
        }
        return Const.APP_VERSION + "|" + f;
    }

    function _messen() as Lang.String {
        var kennung = _kennung();
        var alt = Storage.getValue(ABLAGE_PRUEFUNG);
        if (alt instanceof Lang.String) {
            var s = alt as Lang.String;
            var i = s.find("#");
            if (i != null && s.substring(0, i).equals(kennung)) {
                var gemerkt = s.substring(i + 1, s.length()) as Lang.String;
                if ("be".equals(gemerkt) || "le".equals(gemerkt)) { return gemerkt; }
            }
        }
        var ergebnis = "kaputt";
        try {
            var p = paar(HuelleVektor.D);
            var z = _geheimnisRoh(p, HuelleVektor.EMPF_PUB);
            var ordnung = null;
            if (gleich(z, HuelleVektor.Z)) { ordnung = "be"; }
            else if (gleich(umgedreht(z, 0, z.size()), HuelleVektor.Z)) { ordnung = "le"; }
            if (ordnung != null) {
                _ordnung = ordnung;
                var h = verschliessenRoh(HuelleVektor.KLAR, HuelleVektor.EMPF_PUB,
                                         HuelleVektor.BINDUNG, HuelleVektor.D, HuelleVektor.IV);
                var tag = h.slice(h.size() - 32, h.size());
                if (gleich(tag, HuelleVektor.TAG)) { ergebnis = ordnung; }
            }
        } catch (e) {
            ergebnis = "kaputt";
        }
        if ("kaputt".equals(ergebnis)) {
            Storage.deleteValue(ABLAGE_PRUEFUNG);
        } else {
            Storage.setValue(ABLAGE_PRUEFUNG, kennung + "#" + ergebnis);
        }
        return ergebnis;
    }

    /* Den Vermerk des Selbsttests vergessen -- beim Trennen der Kopplung:
     * Neu koppeln soll ein frischer Anfang sein, auch fuer die Frage, ob diese Uhr verschliessen kann. Gemessen wird beim naechsten
     * `bereit()`. */
    function pruefungVergessen() as Void {
        _ordnung = null;
        Storage.deleteValue(ABLAGE_PRUEFUNG);
    }

    // ---- Bausteine (aus der Krypto-Probe) --------------------------------

    function umgedreht(b as Lang.ByteArray, von as Lang.Number, bis as Lang.Number) as Lang.ByteArray {
        var aus = new [bis - von]b;
        for (var i = 0; i < bis - von; i++) { aus[i] = b[bis - 1 - i]; }
        return aus;
    }

    function sec1ZuGeraet(pub as Lang.ByteArray) as Lang.ByteArray {
        var aus = umgedreht(pub, 1, 33);
        aus.addAll(umgedreht(pub, 33, 65));
        return aus;
    }

    function geraetZuSec1(pub as Lang.ByteArray) as Lang.ByteArray {
        var aus = [0x04]b;
        aus.addAll(umgedreht(pub, 0, 32));
        aus.addAll(umgedreht(pub, 32, 64));
        return aus;
    }

    function gleich(a as Lang.ByteArray, b as Lang.ByteArray) as Lang.Boolean {
        if (a.size() != b.size()) { return false; }
        var d = 0;
        for (var i = 0; i < a.size(); i++) { d = d | (a[i] ^ b[i]); }
        return d == 0;
    }

    function text(s as Lang.String) as Lang.ByteArray {
        return StringUtil.convertEncodedString(s, {
            :fromRepresentation => StringUtil.REPRESENTATION_STRING_PLAIN_TEXT,
            :toRepresentation => StringUtil.REPRESENTATION_BYTE_ARRAY}) as Lang.ByteArray;
    }

    function base64(b as Lang.ByteArray) as Lang.String {
        return StringUtil.convertEncodedString(b, {
            :fromRepresentation => StringUtil.REPRESENTATION_BYTE_ARRAY,
            :toRepresentation => StringUtil.REPRESENTATION_STRING_BASE64}) as Lang.String;
    }

    function _ausBase64(s as Lang.String) as Lang.ByteArray {
        return StringUtil.convertEncodedString(s, {
            :fromRepresentation => StringUtil.REPRESENTATION_STRING_BASE64,
            :toRepresentation => StringUtil.REPRESENTATION_BYTE_ARRAY}) as Lang.ByteArray;
    }

    function hmac(k as Lang.ByteArray, d as Lang.ByteArray) as Lang.ByteArray {
        var h = new Cryptography.HashBasedMessageAuthenticationCode({
            :algorithm => Cryptography.HASH_SHA256, :key => k});
        h.update(d);
        return h.digest();
    }

    // HKDF-SHA256 (RFC 5869), aus HMAC gebaut; 64 Byte Ausgabe.
    function hkdf(ikm as Lang.ByteArray, salz as Lang.ByteArray, info as Lang.ByteArray) as Lang.ByteArray {
        var prk = hmac(salz, ikm);
        var e1 = info.slice(0, null);
        e1.add(1);
        var t1 = hmac(prk, e1);
        var e2 = t1.slice(0, null);
        e2.addAll(info);
        e2.add(2);
        var t2 = hmac(prk, e2);
        var aus = t1.slice(0, null);
        aus.addAll(t2);
        return aus;
    }

    // Polsterung PKCS#7 — Toybox.Cryptography.Cipher verlangt volle Bloecke.
    function gepolstert(k as Lang.ByteArray) as Lang.ByteArray {
        var n = 16 - (k.size() % 16);
        var aus = k.slice(0, null);
        for (var i = 0; i < n; i++) { aus.add(n); }
        return aus;
    }

    // Ein Schluesselpaar: aus festem d (big-endian, nur fuer den Selbsttest)
    // oder neu erzeugt — je Huelle ein fluechtiges.
    function paar(d as Lang.ByteArray or Null) as Cryptography.KeyPair {
        if (d == null) {
            return new Cryptography.KeyPair({
                :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1});
        }
        return new Cryptography.KeyPair({
            :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1,
            :privateKey => umgedreht(d, 0, 32)});
    }

    function _geheimnisRoh(p as Cryptography.KeyPair, empfPub as Lang.ByteArray) as Lang.ByteArray {
        var ka = new Cryptography.KeyAgreement({
            :protocol => Cryptography.KEY_AGREEMENT_ECDH,
            :privateKey => p.getPrivateKey()});
        /* Der erste Parameter heisst in der Signatur HashAlgorithm, gemeint
         * ist KeyPairAlgorithm — daher der Cast. */
        ka.addKey(Cryptography.createPublicKey(
            Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1 as Cryptography.HashAlgorithm,
            sec1ZuGeraet(empfPub)));
        return ka.generateSecret();
    }

    // Z in big-endian, wie WebCrypto und referenz.py es fuehren.
    function ecdh(p as Cryptography.KeyPair, empfPub as Lang.ByteArray) as Lang.ByteArray {
        var z = _geheimnisRoh(p, empfPub);
        return "le".equals(_ordnung) ? umgedreht(z, 0, z.size()) : z;
    }

    /* Die Bindung (Kopf von referenz.py): je Feld 2 Byte Laenge
     * big-endian, dann UTF-8 — Art, client_ref des Eigentuemers, Platz. */
    function bindung(art as Lang.String, ref as Lang.String, platz as Lang.String) as Lang.ByteArray {
        var aus = []b;
        var felder = [art, ref, platz];
        for (var i = 0; i < 3; i++) {
            var b = text(felder[i] as Lang.String);
            aus.add((b.size() >> 8) & 0xFF);
            aus.add(b.size() & 0xFF);
            aus.addAll(b);
        }
        return aus;
    }

    /* Die ganze Huelle, binaer. `d` und `iv` nur fuer den Selbsttest fest. */
    function verschliessenRoh(klar as Lang.ByteArray, empfPub as Lang.ByteArray,
                              bind as Lang.ByteArray, d as Lang.ByteArray or Null,
                              iv as Lang.ByteArray or Null) as Lang.ByteArray {
        var p = paar(d);
        var epk = geraetZuSec1(p.getPublicKey().getBytes() as Lang.ByteArray);
        if (iv == null) { iv = Cryptography.randomBytes(16); }
        var z = ecdh(p, empfPub);
        var info = text(INFO);
        info.addAll(empfPub);
        var okm = hkdf(z, epk, info);
        var kEnc = okm.slice(0, 32);
        var kMac = okm.slice(32, 64);
        var c = new Cryptography.Cipher({:algorithm => Cryptography.CIPHER_AES256,
            :mode => Cryptography.MODE_CBC, :key => kEnc, :iv => iv});
        var ct = c.encrypt(gepolstert(klar));
        var aus = [1]b;
        aus.addAll(epk);
        aus.addAll(iv);
        aus.addAll(ct);
        var mac = aus.slice(0, null);
        mac.addAll(bind);
        aus.addAll(hmac(kMac, mac));
        return aus;
    }

    function verschliessen(klar as Lang.ByteArray, art as Lang.String, ref as Lang.String,
                           platz as Lang.String) as Lang.String {
        return base64(verschliessenRoh(klar, _empfaenger(), bindung(art, ref, platz), null, null));
    }

    // ---- Die Innenformen ---------------------------------------------------

    /* Von der Null weg runden, wie PHPs round() und referenz.py — sonst
     * laege ein halber Meter auf der Uhr anders als auf dem Server. */
    function _runden(x as Lang.Float or Lang.Double) as Lang.Number {
        if (x >= 0) { return Math.floor(x + 0.5).toNumber(); }
        return -(Math.floor(-x + 0.5).toNumber());
    }

    /* Ein Spurstueck (Art "spur" oder "ruhe") aus den flachen Punkten von
     * Track.readPoints() — je Punkt lat, lon, ele, epoch. Innen 14 Byte je
     * Punkt, big-endian: Breite und Laenge in 1e-7 Grad
     * (int32), Hoehe in ganzen Metern (int16, -32768 = keine), Zeit relativ
     * zu ts_von (uint32). Platz "<seq_from>:<punkte>". */
    function spurstueck(art as Lang.String, ref as Lang.String, seqFrom as Lang.Number,
                        flat as Lang.Array) as Lang.Dictionary {
        var n = flat.size() / 4;
        var von = flat[3] as Lang.Number;
        var bis = von;
        for (var i = 1; i < n; i++) {
            var t = flat[i * 4 + 3] as Lang.Number;
            if (t < von) { von = t; }
            if (t > bis) { bis = t; }
        }
        var innen = new [n * 14]b;
        var be = {:endianness => Lang.ENDIAN_BIG};
        for (var i = 0; i < n; i++) {
            var o = i * 14;
            var ele = flat[i * 4 + 2];
            var h = HOEHE_UNBEKANNT;
            if (ele != null) {
                h = _runden(ele as Lang.Float);
                if (h > HOEHE_GRENZE) { h = HOEHE_GRENZE; }
                if (h < -HOEHE_GRENZE) { h = -HOEHE_GRENZE; }
            }
            be[:offset] = o;
            innen.encodeNumber(_runden((flat[i * 4] as Lang.Double) * 1e7d), Lang.NUMBER_FORMAT_SINT32, be);
            be[:offset] = o + 4;
            innen.encodeNumber(_runden((flat[i * 4 + 1] as Lang.Double) * 1e7d), Lang.NUMBER_FORMAT_SINT32, be);
            be[:offset] = o + 8;
            innen.encodeNumber(h, Lang.NUMBER_FORMAT_SINT16, be);
            be[:offset] = o + 10;
            innen.encodeNumber((flat[i * 4 + 3] as Lang.Number) - von, Lang.NUMBER_FORMAT_UINT32, be);
        }
        var platz = seqFrom.toString() + ":" + n.toString();
        return { "seq_from" => seqFrom, "punkte" => n, "ts_von" => von, "ts_bis" => bis,
                 "huelle" => verschliessen(innen, art, ref, platz) };
    }

    /* "2026-07-16T08:31:05Z" -> Unix-Sekunden. Gregorian.moment rechnet in
     * UTC; die Zeichenketten schreibt Util.isoFromMoment, also in genau
     * dieser Form. */
    function _sekunden(iso as Lang.String) as Lang.Number {
        return Gregorian.moment({
            :year => iso.substring(0, 4).toNumber(),
            :month => iso.substring(5, 7).toNumber(),
            :day => iso.substring(8, 10).toNumber(),
            :hour => iso.substring(11, 13).toNumber(),
            :minute => iso.substring(14, 16).toNumber(),
            :second => iso.substring(17, 19).toNumber()
        }).value();
    }

    function _zahl(x as Lang.Object or Null) as Lang.String {
        if (x == null) { return "null"; }
        return (x as Lang.Double).format("%.7f");
    }

    /* Die Einsatzhuelle (Art "einsatz", Platz leer; JSON-Vertrag 3.4):
     * {"fassung":1,"phasen":[{"phase","t","lat","lon"}],
     * "rea":[{"t","ereignisse":[{"art","t"}]}]}, alle Zeiten in Sekunden
     * seit started_at. Immer VOLLSTAENDIG — sie wird mit der
     * Phasenliste ersetzt (Vertrag 3.1), die Uhr schickt beide zusammen. */
    function einsatz(ref as Lang.String, d as Lang.Dictionary) as Lang.String {
        var beginn = _sekunden(d["startedAt"] as Lang.String);
        var s = "{\"fassung\":1,\"phasen\":[";
        var raw = d["phases"] as Lang.Array<Lang.Array>;
        for (var i = 0; i < raw.size(); i++) {
            var p = raw[i];
            s += (i > 0 ? "," : "") + "{\"phase\":" + (p[0] as Lang.Number).toString()
                 + ",\"t\":" + (_sekunden(p[1] as Lang.String) - beginn).toString()
                 + ",\"lat\":" + _zahl(p[2]) + ",\"lon\":" + _zahl(p[3]) + "}";
        }
        s += "],\"rea\":[";
        var sitzungen = d["resus"] as Lang.Array or Null;
        if (sitzungen != null) {
            for (var j = 0; j < sitzungen.size(); j++) {
                var sess = sitzungen[j] as Lang.Dictionary;
                s += (j > 0 ? "," : "") + "{\"t\":"
                     + (_sekunden(sess["start"] as Lang.String) - beginn).toString()
                     + ",\"ereignisse\":[";
                var ev = sess["events"] as Lang.Array<Lang.Array>;
                for (var k = 0; k < ev.size(); k++) {
                    /* Die Arten sind Konstanten aus Const.mc (Vertrag 3.3),
                     * reines ASCII ohne Anfuehrungszeichen — ein Maskieren
                     * braucht es nicht. */
                    s += (k > 0 ? "," : "") + "{\"art\":\"" + (ev[k][0] as Lang.String)
                         + "\",\"t\":" + (_sekunden(ev[k][1] as Lang.String) - beginn).toString() + "}";
                }
                s += "]}";
            }
        }
        s += "]}";
        return verschliessen(text(s), "einsatz", ref, "");
    }
}
