// Die Geraetehuelle auf der Garmin-Uhr — die Bausteine, die die Probe misst.
// Format: Kopf von tools/proben/huelle/referenz.py (Fassung 1). Kein
// Bestandteil der App; S11-08 uebernimmt, was die Probe bestaetigt.
//
// GARMINS BYTEORDNUNG (SDK 9.2.0, Toybox.Cryptography): Schluessel sind
// „byte-vertauscht", je Koordinate little-endian, und ohne das Praefix 0x04.
// Der Server liefert und erwartet SEC1 (0x04 ‖ X ‖ Y, big-endian) — die
// Umrechnung steht hier, und OB sie stimmt, ist die erste Frage der Probe.
// Fuer das gemeinsame Geheimnis Z sagt die Dokumentation nichts; die Probe
// misst, in welcher Ordnung es kommt (`zOrdnung`).
import Toybox.Lang;
import Toybox.Cryptography;
import Toybox.StringUtil;

module Krypto {

    const INFO = "nadoku-huelle-1";

    // "be" oder "le" — wie generateSecret() das Geheimnis liefert. Setzt die
    // Probe, nachdem sie es gegen den Vektor gehalten hat.
    var zOrdnung as String = "be";

    function umgedreht(b as ByteArray, von as Number, bis as Number) as ByteArray {
        var aus = new [bis - von]b;
        for (var i = 0; i < bis - von; i++) { aus[i] = b[bis - 1 - i]; }
        return aus;
    }

    function sec1ZuGarmin(pub as ByteArray) as ByteArray {
        var aus = umgedreht(pub, 1, 33);
        aus.addAll(umgedreht(pub, 33, 65));
        return aus;
    }

    function garminZuSec1(pub as ByteArray) as ByteArray {
        var aus = [0x04]b;
        aus.addAll(umgedreht(pub, 0, 32));
        aus.addAll(umgedreht(pub, 32, 64));
        return aus;
    }

    function gleich(a as ByteArray, b as ByteArray) as Boolean {
        if (a.size() != b.size()) { return false; }
        var d = 0;
        for (var i = 0; i < a.size(); i++) { d = d | (a[i] ^ b[i]); }
        return d == 0;
    }

    function hex(b as ByteArray) as String {
        return StringUtil.convertEncodedString(b, {
            :fromRepresentation => StringUtil.REPRESENTATION_BYTE_ARRAY,
            :toRepresentation => StringUtil.REPRESENTATION_STRING_HEX}) as String;
    }

    function text(s as String) as ByteArray {
        return StringUtil.convertEncodedString(s, {
            :fromRepresentation => StringUtil.REPRESENTATION_STRING_PLAIN_TEXT,
            :toRepresentation => StringUtil.REPRESENTATION_BYTE_ARRAY}) as ByteArray;
    }

    function hmac(k as ByteArray, d as ByteArray) as ByteArray {
        var h = new Cryptography.HashBasedMessageAuthenticationCode({
            :algorithm => Cryptography.HASH_SHA256, :key => k});
        h.update(d);
        return h.digest();
    }

    // HKDF-SHA256 (RFC 5869), aus HMAC gebaut; 64 Byte Ausgabe.
    function hkdf(ikm as ByteArray, salz as ByteArray, info as ByteArray) as ByteArray {
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
    function gepolstert(k as ByteArray) as ByteArray {
        var n = 16 - (k.size() % 16);
        var aus = k.slice(0, null);
        for (var i = 0; i < n; i++) { aus.add(n); }
        return aus;
    }

    function aesCbc(k as ByteArray, iv as ByteArray, d as ByteArray) as ByteArray {
        var c = new Cryptography.Cipher({:algorithm => Cryptography.CIPHER_AES256,
            :mode => Cryptography.MODE_CBC, :key => k, :iv => iv});
        return c.encrypt(d);
    }

    // Ein Schluesselpaar: aus festem d (big-endian, nur fuer Pruefvektoren)
    // oder neu erzeugt.
    function paar(d as ByteArray or Null) as Cryptography.KeyPair {
        if (d == null) {
            return new Cryptography.KeyPair({
                :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1});
        }
        return new Cryptography.KeyPair({
            :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1,
            :privateKey => umgedreht(d, 0, 32)});
    }

    // Z in big-endian, wie WebCrypto und referenz.py es fuehren.
    function ecdh(p as Cryptography.KeyPair, empfPub as ByteArray) as ByteArray {
        var ka = new Cryptography.KeyAgreement({
            :protocol => Cryptography.KEY_AGREEMENT_ECDH,
            :privateKey => p.getPrivateKey()});
        ka.addKey(Cryptography.createPublicKey(
            Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1 as Cryptography.HashAlgorithm,
            sec1ZuGarmin(empfPub)));
        var z = ka.generateSecret();
        return zOrdnung.equals("le") ? umgedreht(z, 0, z.size()) : z;
    }

    // Die ganze Huelle. `d` und `iv` nur fuer Pruefvektoren fest, sonst null.
    // `zw` nimmt die Zwischenwerte auf, wenn die Probe sie sehen will.
    function verschliessen(klar as ByteArray, empfPub as ByteArray, bindung as ByteArray,
                           d as ByteArray or Null, iv as ByteArray or Null,
                           zw as Dictionary or Null) as ByteArray {
        var p = paar(d);
        var epk = garminZuSec1(p.getPublicKey().getBytes() as ByteArray);
        if (iv == null) { iv = Cryptography.randomBytes(16); }
        var z = ecdh(p, empfPub);
        var info = text(INFO);
        info.addAll(empfPub);
        var okm = hkdf(z, epk, info);
        var kEnc = okm.slice(0, 32);
        var kMac = okm.slice(32, 64);
        var ct = aesCbc(kEnc, iv, gepolstert(klar));
        var aus = [1]b;
        aus.addAll(epk);
        aus.addAll(iv);
        aus.addAll(ct);
        var mac = aus.slice(0, null);
        mac.addAll(bindung);
        var tag = hmac(kMac, mac);
        aus.addAll(tag);
        if (zw != null) {
            zw[:epk] = epk; zw[:z] = z; zw[:kEnc] = kEnc; zw[:kMac] = kMac;
            zw[:ct] = ct; zw[:tag] = tag;
        }
        return aus;
    }
}
