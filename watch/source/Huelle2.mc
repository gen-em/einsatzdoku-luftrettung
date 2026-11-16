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
// IM SENDEWEG SEIT UHR 5.0.0 (KG-03b): Pair koppelt mit Geraetepaar und
// Vergleichswert, Uploader verschliesst in Fassung 2, Model zaehlt den
// Inhalt der Einsaetze (KG-09). Gemessen ist der Kern in der Krypto-Probe
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
     *   gz     der Geraetezaehler der Einsatzhuellen: das zuletzt vergebene
     *          z -- die Unix-Sekunde der letzten Aenderung an einem Einsatz
     *          oder mehr (inhaltZaehlen(), E-KA-69)
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

    /* DER ZAEHLER `z` DER EINSATZHUELLE (E-KA-69, -74; KG-09, E-KG-38).
     *
     * WOFUER. Der Browser des Kontos merkt sich je Einsatz den Zaehler der
     * zuletzt abgenommenen Huelle und weist eine mit kleinerem ab
     * (`zurueckgelegt`; z < 1 ist `innen` -- einsatz_lesen() in
     * tools/proben/huelle/referenz.py). Verlangt ist z >= o, nicht o + 1.
     *
     * DIE REGEL. `z` GEHOERT ZUM INHALT, NICHT ZUM VERSCHLIESSEN. Inhalt
     * ist alles, was innen steht ausser z selbst: Phasen samt Koordinaten,
     * Reanimation, `schluss`, `start`. Aendert er sich, vergibt die Uhr
     *     z = max(jetzt, gz + 1)
     * aus dem EINEN Geraetezaehler `gz` (Storage "gz", fuer alle Einsaetze,
     * ueberdauert Neustart und Trennen -- paarLoeschen() laesst ihn stehen)
     * und legt z MIT DEM PAKET ab: im Einsatz-Dictionary unter "z", daneben
     * unter "zh" den SHA-256 des Inhalts ohne z (Base64, wie alles im
     * Storage dieses Moduls). Jede Huelle, die danach aus diesem Paket
     * entsteht, traegt dasselbe z, wie oft und wann auch gesendet wird.
     * Zwei Saetze, an denen das gemessen wird (Vektor `zaehler` der
     * Referenz, Krypto-Probe ZAEHLER-…):
     *     gleiches z     => innen byte-gleich
     *     anderer Inhalt => groesseres z, ueber alle Einsaetze dieser Uhr
     *
     * DER BRUCH ZU E-KA-64 (Uhr 5.0.0). Bis KG-09 war z die Sekunde des
     * VERSCHLIESSENS, je Anfrage neu: Jeder Sendeversuch hob den Zaehler,
     * und eine Uhr, die nach einer Uebergabe ihren alten, unveraenderten
     * Stand nachsandte, gewann gegen den Abschluss des anderen Geraets.
     * Jetzt traegt der alte Stand den alten Zaehler und faellt durch.
     *
     * WORAN DIE AENDERUNG ERKANNT WIRD: am Hash, nicht an einem Merker
     * (E-KG-38). inhaltZaehlen() bildet die Innenform ohne z -- dieselben
     * Bytes wie einsatzInnen() ohne das Feld `"z":<zahl>,` --, vergleicht
     * ihren SHA-256 mit "zh" und vergibt nur bei Abweichung. Wer es ruft,
     * muss also nicht wissen, OB sich etwas geaendert hat; und einsatz()
     * prueft vor jedem Verschliessen selbst noch einmal: Nie geht ein Inhalt
     * unter einem z hinaus, das zu einem anderen gehoerte, auch wenn ein
     * Aufrufer das Zaehlen vergisst. Was nicht innen steht (endedAt,
     * Strecke, Anstieg, Anzeigezeiten), aendert z nicht.
     *
     * WANN z ENTSTEHT, entscheidet der Aufrufer: Gezaehlt beim AENDERN, ist
     * z die Sekunde der Aenderung (so meint es die Referenz). Gezaehlt erst
     * beim Verschliessen, ist es die Sekunde des ERSTEN Sendens dieses
     * Stands -- beide Saetze halten, aber ein lange liegender Stand bekaeme
     * einen jungen Zaehler. EIN PAKET AUS UHR 5.0.0 traegt weder "z" noch
     * "zh" und bekommt beides beim ersten Zaehlen; sein z liegt ueber jedem,
     * das diese Uhr je gesendet hat (gz).
     *
     * ABGELEGT, BEVOR IHN JEMAND BENUTZT. `gz` steht im Storage, bevor z im
     * Paket steht: Ein Absturz danach kostet eine Zahl, nie vergibt die Uhr
     * dieselbe ein zweites Mal fuer anderen Inhalt. Das PAKET legt der
     * Aufrufer ab (die Funktionen melden `neu`). Geht das verloren, bekommt
     * derselbe Inhalt beim naechsten Mal ein groesseres z -- das kostet die
     * Schaerfe der Regel, nicht ihre Richtung. Ein Paket, dessen z ueber
     * `gz` liegt (Speicher teils verloren), hebt `gz` mit.
     *
     * "JETZT" ist Time.now().value(), die Zeit der Uhr (hier unmittelbar,
     * weil die Krypto-Probe diesen Kern ohne Util.mc uebersetzt). SPRINGT
     * SIE ZURUECK, traegt gz + 1, bis die Zeit den Zaehler einholt. FEHLT
     * SIE (null, negativ, unplausibel klein), gilt dasselbe, und ohne
     * Vorgaenger ist z = 1, nie kleiner. Was unter "gz" keine Zahl >= 1 ist,
     * zaehlt als "kein Vorgaenger"; ein alter +1-Stand (vor Uhr 5.0.0)
     * springt ohne Umzug auf die Sekunde. ZAHLENBEREICH: Lang.Number,
     * 32 Bit mit Vorzeichen, bis 2147483647 = 19.01.2038 -- die Grenze der
     * Plattform (Time.now().value() ist selbst eine Number). An ihr BLEIBT
     * der Zaehler stehen, statt ins Negative umzulaufen; dort, und nur
     * dort, gilt "anderer Inhalt => groesseres z" nicht mehr.
     *
     * WAS DIE REGEL NICHT TRAEGT -- vier Grenzen (Kopf der Referenz); alle
     * stoeren erst den Geraetewechsel, nie die Folge dieser Uhr:
     * 1. VORAUSLAUFENDE UHR. Lag die Zeit einmal weit voraus, bleibt der
     *    hohe Stand; ein anderes Geraet faellt an diesem Einsatz durch, bis
     *    seine Zeit ihn ueberholt. Nichts an der Uhr senkt ihn.
     * 2. NACHGEHENDE UHR. Geht die Zeit nach oder fehlt sie, liegt das z
     *    einer ECHTEN Aenderung unter dem Stand des anderen Geraets, und
     *    diese Uhr faellt durch, obwohl niemand etwas zurueckgelegt hat.
     * 3. GLEICHSTAND (F-KA-112). z >= o nimmt gleiches z an -- es muss.
     *    Ueber Geraete hinweg sagt Gleichstand nichts.
     * 4. UHRZEIT, NICHT REIHENFOLGE. z belegt "spaeter geaendert", nicht
     *    "vollstaendiger".
     *
     * zaehlerFolge() ist die Regel ohne Uhr und Speicher, und Zaehlen wie
     * Innenform nehmen ein `jetzt` entgegen (null = die Zeit der Uhr),
     * damit die Probe auch misst, was sich am Geraet nicht stellen laesst. */
    const Z_MAX = 2147483647;
    const PAKET_Z = "z";        // Schluessel im Einsatz-Dictionary (Model.mc)
    const PAKET_HASH = "zh";
    const INNEN_KOPF = "{\"fassung\":2,";

    function zaehlerFolge(alt as Lang.Object or Null, jetzt as Lang.Number) as Lang.Number {
        var n = 1;
        if (alt instanceof Lang.Number && (alt as Lang.Number) >= 1) {
            var a = alt as Lang.Number;
            n = (a < Z_MAX) ? a + 1 : Z_MAX;
        }
        return (jetzt > n) ? jetzt : n;
    }

    /* Die Innenform hinter dem Kopf und OHNE z:
     * "start":…,"schluss":…,"phasen":[...],"rea":[...]} -- der Inhalt. */
    function _inhaltRest(d as Lang.Dictionary, schluss as Lang.Boolean) as Lang.String {
        var beginn = Huelle._sekunden(d["startedAt"] as Lang.String);
        return "\"start\":" + beginn.toString() + ",\"schluss\":" + (schluss ? "true" : "false") + ","
               + Huelle.einsatzRumpf(d, beginn);
    }

    function _restHash(rest as Lang.String) as Lang.ByteArray {
        var h = new Cryptography.Hash({:algorithm => Cryptography.HASH_SHA256});
        h.update(Huelle.text(INNEN_KOPF));
        h.update(Huelle.text(rest));
        return h.digest();
    }

    /* SHA-256 des Inhalts ohne z: ueber {"fassung":2,"start":…,"schluss":…,
     * "phasen":[...],"rea":[...]} -- einsatzInnen() ohne das Feld z. Rein:
     * liest nur. `schluss` gehoert zum Inhalt. */
    function inhaltHash(d as Lang.Dictionary, schluss as Lang.Boolean) as Lang.ByteArray {
        return _restHash(_inhaltRest(d, schluss));
    }

    /* Vergleichen und, nur bei Abweichung, zaehlen. true: z wurde vergeben. */
    function _zaehlen(d as Lang.Dictionary, rest as Lang.String, jetzt as Lang.Number or Null) as Lang.Boolean {
        var h = Huelle.base64(_restHash(rest));
        var z = d[PAKET_Z];
        var alt = d[PAKET_HASH];
        var gilt = z instanceof Lang.Number && (z as Lang.Number) >= 1;
        if (gilt && alt instanceof Lang.String && (alt as Lang.String).equals(h)) { return false; }
        var g = Storage.getValue(ABLAGE_ZAEHLER) as Lang.Object or Null;
        if (gilt && !(g instanceof Lang.Number && (g as Lang.Number) >= (z as Lang.Number))) { g = z as Lang.Number; }
        var n = zaehlerFolge(g, (jetzt != null) ? jetzt : Time.now().value());
        Storage.setValue(ABLAGE_ZAEHLER, n);    // zuerst der Geraetezaehler
        d[PAKET_Z] = n;
        d[PAKET_HASH] = h;
        return true;
    }

    /* DAS ZAEHLEN: Hat sich der Inhalt des Einsatzes `d` geaendert, seit
     * sein z vergeben wurde? Dann ein neues aus `gz`, abgelegt in `gz` und
     * in `d` ("z", "zh"). Gibt true zurueck, wenn `d` sich dabei geaendert
     * hat -- der Aufrufer legt das Paket dann ab. Der Zaehler steht danach
     * in d["z"]. `schluss`: Der Einsatz ist abgeschlossen (d["final"]) --
     * derselbe Wert wie beim Verschliessen, sonst zaehlt das Senden neu.
     * `jetzt`: null, ausser in der Probe. */
    function inhaltZaehlen(d as Lang.Dictionary, schluss as Lang.Boolean,
                           jetzt as Lang.Number or Null) as Lang.Boolean {
        return _zaehlen(d, _inhaltRest(d, schluss), jetzt);
    }

    /* Die Einsatzhuelle innen: {"fassung":2,"z","start","schluss",
     * "phasen":[...],"rea":[...]} -- die Schluessel in dieser Reihenfolge.
     * `z`: der Zaehler des Pakets; `start`: started_at als absolute Zeit,
     * auf die sich alle t beziehen; `schluss`: die Huelle des
     * abgeschlossenen Einsatzes. ZAEHLT VORHER (inhaltZaehlen()) und gibt
     * [innen as String, neu as Boolean] zurueck; `neu`: `d` hat ein neues z
     * bekommen und ist abzulegen. */
    function einsatzInnen(d as Lang.Dictionary, schluss as Lang.Boolean,
                          jetzt as Lang.Number or Null) as Lang.Array {
        var rest = _inhaltRest(d, schluss);
        var neu = _zaehlen(d, rest, jetzt);
        return [INNEN_KOPF + "\"z\":" + (d[PAKET_Z] as Lang.Number).toString() + "," + rest, neu];
    }

    /* Die Einsatzhuelle: [huelle as String, neu as Boolean]. */
    function einsatz(ref as Lang.String, d as Lang.Dictionary, schluss as Lang.Boolean) as Lang.Array {
        var i = einsatzInnen(d, schluss, null);
        return [verschliessen(Huelle.text(i[0] as Lang.String), "einsatz", ref, ""), i[1]];
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
