// NAdoku — die Geraetehuelle, Fassung 2: der Krypto-Kern (KG-03a).
//
// WAS DAS IST. Fassung 2 belegt dem Browser des Kontos, WELCHES Geraet eine
// Huelle verschlossen hat, wann die Daten entstanden sind und ob etwas
// fehlt. Dazu bekommt die Uhr ein eigenes, dauerhaftes Schluesselpaar; der
// Schluessel jeder Huelle entsteht aus zwei Geheimnissen -- dem fluechtigen
// wie in Fassung 1 und dem statischen dieses Paars gegen den
// Kontoschluessel. Signieren kann Connect IQ nicht (F-KG-11), eine zweite
// Schluesselvereinbarung schon.
//
// DAS FORMAT steht im Kopf von tools/proben/huelle/referenz.py, und nur
// dort (Fassung 2): Kennbyte 0x02 · Kennung des Absenders (8 Byte) ·
// fluechtiger oeffentlicher Schluessel · IV · AES-256-CBC · HMAC-SHA256.
// Weicht diese Datei davon ab, ist diese Datei falsch.
//
// NOCH NICHT IM SENDEWEG. Uploader, Pair und Model benutzen dieses Modul
// nicht; die App sendet Fassung 1, bis der Server Fassung 2 annimmt
// (KG-03b). Gemessen ist es in der Krypto-Probe
// (tools/uhr-pruefstand/krypto-probe/), die genau diese Datei uebersetzt,
// byte-gleich gegen die Vektoren der Referenz.
//
// DIE BAUSTEINE (ECDH samt Byteordnung, HKDF, AES, HMAC, Bindung,
// Innenform der Punkte, Rumpf der Einsatzhuelle) stehen in Huelle.mc und
// werden hier benutzt, nicht kopiert. Neu ist nur SHA-256 ohne Schluessel.
//
// WAS AUF DER UHR LIEGT. Der private Teil des Geraetepaars und das
// statische Geheimnis Z_s liegen im Application.Storage, ohne
// Hardware-Schutz, wie die Zugangsdaten ("cred") daneben (Bedrohungsmodell,
// Lage H). Wer die Uhr ausliest, kann fuer dieses Konto Huellen im Namen
// dieser Uhr bauen, bis sie im Web ausgetragen ist; oeffnen kann er damit
// nichts.
using Toybox.Lang;
using Toybox.Cryptography;
using Toybox.Application.Storage;
using Toybox.Time;

module Huelle2 {

    const INFO = "nadoku-huelle-2";
    const ABSENDER_INFO = "nadoku-absender-1";
    const ZUSAGE_INFO = "nadoku-kopplung-zusage-1";
    const WERT_INFO = "nadoku-kopplung-wert-1";

    const SID_LAENGE = 8;
    const NONCE_LAENGE = 16;
    const SPUR_KOPF = 12;
    const M_SCHLUSS = 1;       // die uebrigen Merker setzt nur das Konto

    /* Storage-Schluessel. Alles als Base64-Zeichenkette, wie der
     * Kontoschluessel in Huelle.mc.
     *   gpaar  privater Teil (32 Byte, in der Ordnung der Bibliothek) und
     *          oeffentlicher Teil (65 Byte SEC1) des Geraetepaars
     *   gzs    Kennung des Kontoschluessels (8 Byte) und Z_s (32 Byte) --
     *          gilt nur, solange die Kennung zum abgelegten Kontoschluessel
     *          passt
     *   gz     der Zaehler der Einsatzhuellen: die Unix-Sekunde des letzten
     *          Verschliessens oder mehr (zaehlerNaechster(), E-KA-64)
     *   kpruef2  Vermerk des Selbsttests, wie "kpruef" */
    const ABLAGE_PAAR = "gpaar";
    const ABLAGE_ZS = "gzs";
    const ABLAGE_ZAEHLER = "gz";
    const ABLAGE_PRUEFUNG = "kpruef2";

    var _geprueft as Lang.Boolean or Null = null;
    var _spk as Lang.ByteArray or Null = null;      // Zwischenspeicher
    var _zs as Lang.ByteArray or Null = null;
    var _zsKonto as Lang.ByteArray or Null = null;  // fuer welchen Kontoschluessel _zs gilt

    // Die laufende Kopplung -- nur im Speicher, bis sie uebernommen ist.
    var _kPriv as Lang.ByteArray or Null = null;
    var _kSpk as Lang.ByteArray or Null = null;
    var _kNg as Lang.ByteArray or Null = null;
    var _kKonto as Lang.ByteArray or Null = null;
    var _kNb as Lang.ByteArray or Null = null;

    // ---- Bausteine --------------------------------------------------------

    function sha256(d as Lang.ByteArray) as Lang.ByteArray {
        var h = new Cryptography.Hash({:algorithm => Cryptography.HASH_SHA256});
        h.update(d);
        return h.digest();
    }

    function _verkettet(marke as Lang.String, teile as Lang.Array<Lang.ByteArray>) as Lang.ByteArray {
        var aus = Huelle.text(marke);
        for (var i = 0; i < teile.size(); i++) { aus.addAll(teile[i]); }
        return aus;
    }

    /* Die Kennung eines Absenders: 8 Byte aus seinem statischen
     * oeffentlichen Schluessel (SEC1). */
    function sidVon(spk as Lang.ByteArray) as Lang.ByteArray {
        return sha256(_verkettet(ABSENDER_INFO, [spk])).slice(0, SID_LAENGE);
    }

    // ---- Der Selbsttest ---------------------------------------------------

    /* Kann diese Uhr Fassung 2 rechnen? Setzt den Selbsttest der Fassung 1
     * voraus (Byteordnung) und rechnet dann EINEN festen Fall der Referenz
     * nach (HuelleVektor2.mc): Stimmen Kennung und Pruefwort, tragen
     * SHA-256, beide Schluesselvereinbarungen und die Ableitung. Gemessen
     * wird beim ersten Aufruf je App-Fassung und Firmware; wie in Huelle.mc
     * bleibt nur ein gutes Ergebnis im Storage.
     *
     * Huelle.bereit() ruft das NICHT: Solange die App Fassung 1 sendet,
     * soll ein Fehlschlag hier das Senden nicht aufhalten und der erste
     * Start nichts kosten. KG-03b fragt hier, bevor es Fassung 2 sendet --
     * in einem eigenen Schritt, nicht im selben wie die erste Huelle. */
    function bereit() as Lang.Boolean {
        if (!Huelle.bereit()) { return false; }
        var g = _geprueft;
        if (g == null) {
            g = _messen();
            _geprueft = g;
        }
        return g;
    }

    function _messen() as Lang.Boolean {
        var kennung = Huelle._kennung();
        var alt = Storage.getValue(ABLAGE_PRUEFUNG);
        if (alt instanceof Lang.String && (alt as Lang.String).equals(kennung)) { return true; }
        var gut = false;
        try {
            var g = Huelle.paar(HuelleVektor2.D_GERAET);
            var spk = Huelle.geraetZuSec1(g.getPublicKey().getBytes() as Lang.ByteArray);
            var h = verschliessenRoh(HuelleVektor.KLAR, HuelleVektor.EMPF_PUB, spk,
                                     Huelle.ecdh(g, HuelleVektor.EMPF_PUB),
                                     HuelleVektor2.BINDUNG, HuelleVektor2.D, HuelleVektor2.IV);
            gut = Huelle.gleich(h.slice(1, 1 + SID_LAENGE), HuelleVektor2.SID)
                  && Huelle.gleich(h.slice(h.size() - 32, h.size()), HuelleVektor2.TAG);
        } catch (e) {
            gut = false;
        }
        if (gut) { Storage.setValue(ABLAGE_PRUEFUNG, kennung); }
        else { Storage.deleteValue(ABLAGE_PRUEFUNG); }
        return gut;
    }

    function pruefungVergessen() as Void {
        _geprueft = null;
        Storage.deleteValue(ABLAGE_PRUEFUNG);
    }

    // ---- Das Geraetepaar --------------------------------------------------

    function hatPaar() as Lang.Boolean {
        return Storage.getValue(ABLAGE_PAAR) instanceof Lang.String;
    }

    /* Privater und oeffentlicher Teil aus dem Storage, oder null. */
    function _abgelegt() as Lang.ByteArray or Null {
        var s = Storage.getValue(ABLAGE_PAAR);
        if (!(s instanceof Lang.String)) { return null; }
        var b = Huelle._ausBase64(s as Lang.String);
        return (b.size() == 97 && b[32] == 0x04) ? b : null;
    }

    /* Der oeffentliche Teil des Geraetepaars (SEC1, 65 Byte), oder null,
     * solange die Uhr keines hat. */
    function spk() as Lang.ByteArray or Null {
        var s = _spk;
        if (s == null) {
            var b = _abgelegt();
            if (b != null) {
                s = b.slice(32, 97);
                _spk = s;
            }
        }
        return s;
    }

    function sid() as Lang.ByteArray or Null {
        var s = spk();
        return (s == null) ? null : sidVon(s);
    }

    /* Das Paar vergessen -- beim Trennen der Kopplung. Der Zaehler der
     * Einsatzhuellen bleibt: Er darf nie zuruecklaufen, auch nicht ueber
     * eine neue Kopplung hinweg. */
    function paarLoeschen() as Void {
        _spk = null;
        _zs = null;
        _zsKonto = null;
        Storage.deleteValue(ABLAGE_PAAR);
        Storage.deleteValue(ABLAGE_ZS);
    }

    /* Z_s: das Geheimnis zwischen Geraetepaar und Kontoschluessel,
     * big-endian wie in der Referenz. Es ist je Paar und Konto fest und
     * wird deshalb EINMAL gerechnet und abgelegt (V-KA-02) -- im Speicher
     * fuer diesen Lauf, im Storage ueber den Neustart. Eine Huelle kostet
     * danach eine Schluesselvereinbarung wie in Fassung 1, nicht zwei; und
     * die erste Huelle nach dem Start nicht drei Rechnungen in einem Zug
     * (Waechter der Laufzeit).
     *
     * Abgelegt wird es mit der Kennung des Kontoschluessels: Kommt bei
     * einer neuen Kopplung ein anderer, passt sie nicht mehr, und es wird
     * neu gerechnet. Null, wenn die Uhr kein Paar hat. */
    function statisch(kontoPub as Lang.ByteArray) as Lang.ByteArray or Null {
        var z = _zs;
        var fuer = _zsKonto;
        if (z != null && fuer != null && Huelle.gleich(fuer, kontoPub)) { return z; }
        var kontoSid = sidVon(kontoPub);
        var s = Storage.getValue(ABLAGE_ZS);
        z = null;
        if (s instanceof Lang.String) {
            var b = Huelle._ausBase64(s as Lang.String);
            if (b.size() == SID_LAENGE + 32 && Huelle.gleich(b.slice(0, SID_LAENGE), kontoSid)) {
                z = b.slice(SID_LAENGE, SID_LAENGE + 32);
            }
        }
        if (z == null) {
            var p = _abgelegt();
            if (p == null) { return null; }
            z = Huelle.ecdh(new Cryptography.KeyPair({
                    :algorithm => Cryptography.KEY_PAIR_ELLIPTIC_CURVE_SECP256R1,
                    :privateKey => p.slice(0, 32)}), kontoPub);
            kontoSid.addAll(z);
            Storage.setValue(ABLAGE_ZS, Huelle.base64(kontoSid));
        }
        _zs = z;
        _zsKonto = kontoPub.slice(0, null);
        return z;
    }

    // ---- Die Huelle -------------------------------------------------------

    /* Die ganze Huelle, binaer. `spk` und `zS` gehoeren zum Absender; `d`
     * und `iv` sind nur fuer Selbsttest und Probe fest, sonst null. */
    function verschliessenRoh(klar as Lang.ByteArray, kontoPub as Lang.ByteArray,
                              spk as Lang.ByteArray, zS as Lang.ByteArray,
                              bind as Lang.ByteArray, d as Lang.ByteArray or Null,
                              iv as Lang.ByteArray or Null) as Lang.ByteArray {
        var p = Huelle.paar(d);
        var epk = Huelle.geraetZuSec1(p.getPublicKey().getBytes() as Lang.ByteArray);
        if (iv == null) { iv = Cryptography.randomBytes(16); }
        var ikm = Huelle.ecdh(p, kontoPub);
        ikm.addAll(zS);
        var okm = Huelle.hkdf(ikm, epk, _verkettet(INFO, [kontoPub, spk]));
        var kopf = [2]b;
        kopf.addAll(sidVon(spk));
        kopf.addAll(epk);
        kopf.addAll(iv);
        return Huelle.fertigen(kopf, okm, iv, klar, bind);
    }

    /* Mit dem Geraetepaar fuer das gekoppelte Konto verschliessen. Setzt
     * voraus, dass die Uhr Kontoschluessel und Paar hat (Huelle.hatSchluessel(),
     * hatPaar()) und bereit() gilt. */
    function verschliessen(klar as Lang.ByteArray, art as Lang.String, ref as Lang.String,
                           platz as Lang.String) as Lang.String {
        var konto = Huelle._empfaenger();
        return Huelle.base64(verschliessenRoh(klar, konto, spk() as Lang.ByteArray,
            statisch(konto) as Lang.ByteArray, Huelle.bindung(art, ref, platz), null, null));
    }

    // ---- Die Innenformen --------------------------------------------------

    /* Der Kopf eines Spurstuecks: ts_von als ABSOLUTE Zeit (8 Byte
     * big-endian; die Uhr rechnet mit 32 Bit, die oberen vier sind 0),
     * Merker, Zahl der Herkunftseintraege (Geraet: 0), Reserve. */
    function _spurKopf(innen as Lang.ByteArray, tsVon as Lang.Number, schluss as Lang.Boolean) as Void {
        for (var i = 0; i < SPUR_KOPF; i++) { innen[i] = 0; }
        innen.encodeNumber(tsVon, Lang.NUMBER_FORMAT_UINT32,
                           {:offset => 4, :endianness => Lang.ENDIAN_BIG});
        innen[8] = schluss ? M_SCHLUSS : 0;
    }

    /* Kopf und Punkte eines Spurstuecks; gibt [innen, ts_von, ts_bis]
     * zurueck. Die Punktzeiten zaehlen ab dem ts_von DIESES Kopfs -- dem
     * kleinsten Zeitpunkt des Stuecks, demselben Wert, der im Klartext
     * neben der Huelle steht. */
    function spurInnen(flat as Lang.Array, schluss as Lang.Boolean) as Lang.Array {
        var p = Huelle.punkteInnen(flat, SPUR_KOPF);
        _spurKopf(p[0] as Lang.ByteArray, p[1] as Lang.Number, schluss);
        return p;
    }

    /* Ein Spurstueck (Art "spur" oder "ruhe") aus den flachen Punkten von
     * Track.readPoints(), wie Huelle.spurstueck(). `schluss`: Es ist das
     * letzte Stueck der Aufzeichnung. */
    function spurstueck(art as Lang.String, ref as Lang.String, seqFrom as Lang.Number,
                        flat as Lang.Array, schluss as Lang.Boolean) as Lang.Dictionary {
        var n = flat.size() / 4;
        var p = spurInnen(flat, schluss);
        var platz = seqFrom.toString() + ":" + n.toString();
        return { "seq_from" => seqFrom, "punkte" => n, "ts_von" => p[1], "ts_bis" => p[2],
                 "huelle" => verschliessen(p[0] as Lang.ByteArray, art, ref, platz) };
    }

    /* Das leere Schlussstueck: nur der Kopf mit dem Merker SCHLUSS, Platz
     * "<seq>:0" -- wenn beim Abschluss kein Punkt mehr offen ist. `seq` ist
     * die Nummer, die der naechste Punkt bekommen haette; `tsVon` der Wert,
     * der im Klartext neben der Huelle steht. */
    function schlussstueck(art as Lang.String, ref as Lang.String, seq as Lang.Number,
                           tsVon as Lang.Number) as Lang.Dictionary {
        var innen = new [SPUR_KOPF]b;
        _spurKopf(innen, tsVon, true);
        return { "seq_from" => seq, "punkte" => 0, "ts_von" => tsVon, "ts_bis" => tsVon,
                 "huelle" => verschliessen(innen, art, ref, seq.toString() + ":0") };
    }

    /* DER ZAEHLER `z` DER EINSATZHUELLE (E-KA-64, KG-03b).
     *
     * DIE REGEL. z = Unix-Sekunde des Verschliessens, lokal streng steigend:
     *     z = max(jetzt, letztes z + 1)
     * EIN Zaehler fuer alle Einsaetze dieser Uhr, abgelegt unter "gz"; er
     * steigt mit jedem Verschliessen, also auch je Einsatz, braucht keinen
     * Platz im Einsatzpuffer und uebersteht das Trennen (paarLoeschen()).
     * Die Referenz verlangt je Einsatz z >= o (einsatz_lesen() in
     * tools/proben/huelle/referenz.py: z < o ist `zurueckgelegt`, z < 1 ist
     * `innen`) -- nicht o + 1. Bis KG-03b zaehlte er +1 ab 1; das trug auf
     * EINER Uhr, aber nicht ueber einen Geraetewechsel: Ein zweites Geraet
     * mit kleinerem Zaehler fiele als `zurueckgelegt` durch (Q-KA-13). Mit
     * der Sekunde steigt er auch ueber Geraete hinweg, solange deren Uhren
     * nicht weiter auseinandergehen als der Abstand zweier Uebergaben.
     *
     * 1. WOHER "JETZT" KOMMT. Time.now().value() -- dieselbe Quelle wie
     *    Util.epochNow(); hier unmittelbar, weil die Krypto-Probe diesen
     *    Kern ohne Util.mc uebersetzt. Es ist die Zeit der Uhr, nicht des
     *    Servers. SPRINGT SIE ZURUECK (Zuruecksetzen, Zeitzone von Hand),
     *    traegt "letztes z + 1": Der Zaehler laeuft der Uhr dann voraus und
     *    zaehlt +1, bis die Zeit ihn wieder einholt. IST SIE UNPLAUSIBEL
     *    KLEIN (Uhr ohne Zeit) oder null/negativ, gilt dasselbe, und ohne
     *    Vorgaenger ist z = 1 -- nie kleiner, die Referenz weist z < 1 ab.
     *    Lokal haelt die Regel also immer. WAS SIE NICHT TRAEGT: Eine Uhr,
     *    deren Zeit einmal weit VORAUS lag, behaelt den hohen Stand; und
     *    eine Uhr ohne Zeit liefert kleine Zahlen. Beides stoert erst den
     *    Geraetewechsel (das andere Geraet faellt als `zurueckgelegt`
     *    durch bzw. diese Uhr), nie die Folge dieser Uhr selbst.
     * 2. BESTEHENDER SPEICHER. Eine Uhr mit "gz" = kleine Zahl aus dem
     *    alten +1-Zaehler springt beim naechsten Verschliessen auf jetzt
     *    (max greift) -- groesser als jeder Stand, den der Server von ihr
     *    kennt. Kein Umzug, kein Fassungsmerker. Was unter "gz" keine Zahl
     *    >= 1 ist, zaehlt als "kein Vorgaenger".
     * 3. ZAHLENBEREICH. Lang.Number, 32 Bit mit Vorzeichen: traegt bis
     *    2147483647 = 19.01.2038. Long braechte hier nichts, denn
     *    Time.now().value() ist selbst eine Number -- die Grenze liegt in
     *    der Plattform, nicht in diesem Zaehler. Das Format verlangt nur
     *    eine ganze JSON-Zahl >= 1 ("z":<zahl>, ohne Anfuehrungszeichen,
     *    ohne Komma); Referenz (Python) und Browser (2^53) lesen jede
     *    Number und spaeter auch jeden Long unveraendert. An der Grenze
     *    BLEIBT der Zaehler stehen, statt ins Negative umzulaufen: gleiches
     *    z nimmt die Referenz an (z >= o), ein negatives nie.
     *
     * zaehlerFolge() ist die Regel ohne Uhr und Speicher, damit die Probe
     * auch die Faelle messen kann, die sich am Geraet nicht stellen lassen
     * (Zeit springt zurueck, keine Zeit, Grenze). */
    const Z_MAX = 2147483647;

    function zaehlerFolge(alt as Lang.Object or Null, jetzt as Lang.Number) as Lang.Number {
        var n = 1;
        if (alt instanceof Lang.Number && (alt as Lang.Number) >= 1) {
            var a = alt as Lang.Number;
            n = (a < Z_MAX) ? a + 1 : Z_MAX;
        }
        return (jetzt > n) ? jetzt : n;
    }

    /* Der naechste Zaehlerstand fuer eine Einsatzhuelle -- und abgelegt,
     * BEVOR ihn jemand benutzt: Ein Absturz danach kostet eine Zahl, keinen
     * Ruecklauf. */
    function zaehlerNaechster() as Lang.Number {
        var n = zaehlerFolge(Storage.getValue(ABLAGE_ZAEHLER), Time.now().value());
        Storage.setValue(ABLAGE_ZAEHLER, n);
        return n;
    }

    /* Die Einsatzhuelle innen: {"fassung":2,"z","start","schluss",
     * "phasen":[...],"rea":[...]} -- die Schluessel in dieser Reihenfolge.
     * `z`: der Zaehler; `start`: started_at als absolute Zeit, auf die sich
     * alle t beziehen; `schluss`: die Huelle des abgeschlossenen Einsatzes. */
    function einsatzInnen(d as Lang.Dictionary, z as Lang.Number, schluss as Lang.Boolean) as Lang.String {
        var beginn = Huelle._sekunden(d["startedAt"] as Lang.String);
        return "{\"fassung\":2,\"z\":" + z.toString() + ",\"start\":" + beginn.toString()
               + ",\"schluss\":" + (schluss ? "true" : "false") + ","
               + Huelle.einsatzRumpf(d, beginn);
    }

    function einsatz(ref as Lang.String, d as Lang.Dictionary, z as Lang.Number,
                     schluss as Lang.Boolean) as Lang.String {
        return verschliessen(Huelle.text(einsatzInnen(d, z, schluss)), "einsatz", ref, "");
    }

    // ---- Die Kopplung: Zusage und Vergleichswert --------------------------

    /* Was das Geraet bei `start` schickt, bevor es N_g offenlegt. */
    function zusage(nG as Lang.ByteArray, spk as Lang.ByteArray) as Lang.ByteArray {
        return sha256(_verkettet(ZUSAGE_INFO, [nG, spk]));
    }

    /* Der Vergleichswert: sechs Ziffern, die Uhr und Webseite zeigen. Die
     * ersten vier Byte des Hashs als vorzeichenlose Zahl -- in 64 Bit
     * gerechnet, weil die 32 Bit der Uhr ein Vorzeichen tragen. */
    function wert(kontoPub as Lang.ByteArray, spk as Lang.ByteArray, nG as Lang.ByteArray,
                  nB as Lang.ByteArray) as Lang.String {
        var h = sha256(_verkettet(WERT_INFO, [kontoPub, spk, nG, nB]));
        var n = 0l;
        for (var i = 0; i < 4; i++) { n = (n << 8) | h[i].toLong(); }
        return (n % 1000000l).format("%06d");
    }

    /* Schritt 1, bei `start`: ein NEUES Geraetepaar und N_g erzeugen. Gibt
     * {:spk, :zusage} zurueck (65 und 32 Byte) -- oder null, wenn das
     * Geraet den privaten Teil nicht herausgibt und also kein dauerhaftes
     * Paar halten kann.
     *
     * Das Paar bleibt im Speicher, bis kopplungUebernehmen() es ablegt: Eine
     * abgebrochene Kopplung laesst das bisherige Paar stehen, und was noch
     * im Puffer liegt, geht weiter unter dem Schluessel hinaus, den das
     * Konto kennt. */
    function kopplungBeginnen() as Lang.Dictionary or Null {
        kopplungVerwerfen();
        var p = Huelle.paar(null);
        var priv = p.getPrivateKey().getBytes();
        if (!(priv instanceof Lang.ByteArray) || (priv as Lang.ByteArray).size() != 32) { return null; }
        var s = Huelle.geraetZuSec1(p.getPublicKey().getBytes() as Lang.ByteArray);
        var nG = Cryptography.randomBytes(NONCE_LAENGE);
        _kPriv = priv as Lang.ByteArray;
        _kSpk = s;
        _kNg = nG;
        return { :spk => s, :zusage => zusage(nG, s) };
    }

    /* Schritt 3, bei `status`: Erst wenn Kontoschluessel und N_b da sind,
     * gibt die Uhr N_g heraus. Gibt {:ng, :wert} zurueck, oder null, wenn
     * keine Kopplung laeuft oder ein Wert die falsche Form hat.
     *
     * DIE ERSTE ANTWORT GILT. Kommen spaeter ein anderer Kontoschluessel
     * oder ein anderes N_b, gibt es null: Nach dem Offenlegen von N_g darf
     * sich nichts mehr aendern, was in den Vergleichswert eingeht. */
    function kopplungAbschliessen(kontoPub as Lang.ByteArray, nB as Lang.ByteArray) as Lang.Dictionary or Null {
        var s = _kSpk;
        var nG = _kNg;
        if (s == null || nG == null) { return null; }
        if (kontoPub.size() != 65 || kontoPub[0] != 0x04 || nB.size() != NONCE_LAENGE) { return null; }
        var k = _kKonto;
        var b = _kNb;
        if (k != null && b != null) {
            if (!Huelle.gleich(k, kontoPub) || !Huelle.gleich(b, nB)) { return null; }
        } else {
            _kKonto = kontoPub.slice(0, null);
            _kNb = nB.slice(0, null);
        }
        return { :ng => nG, :wert => wert(kontoPub, s, nG, nB) };
    }

    /* Die Kopplung ist angenommen: das neue Paar ablegen. Ein Z_s zum alten
     * Paar gilt nicht mehr. */
    function kopplungUebernehmen() as Lang.Boolean {
        var priv = _kPriv;
        var s = _kSpk;
        if (priv == null || s == null) { return false; }
        var b = priv.slice(0, null);
        b.addAll(s);
        paarLoeschen();
        Storage.setValue(ABLAGE_PAAR, Huelle.base64(b));
        _spk = s;
        kopplungVerwerfen();
        return true;
    }

    function kopplungVerwerfen() as Void {
        _kPriv = null;
        _kSpk = null;
        _kNg = null;
        _kKonto = null;
        _kNb = null;
    }
}
