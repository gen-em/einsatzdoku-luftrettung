package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.tresor.Absender
import org.json.JSONArray
import org.json.JSONObject
import java.math.BigInteger
import java.nio.ByteBuffer
import java.nio.ByteOrder
import java.security.AlgorithmParameters
import java.security.KeyFactory
import java.security.KeyPair
import java.security.KeyPairGenerator
import java.security.MessageDigest
import java.security.PrivateKey
import java.security.SecureRandom
import java.security.interfaces.ECPublicKey
import java.security.spec.ECGenParameterSpec
import java.security.spec.ECParameterSpec
import java.security.spec.ECPoint
import java.security.spec.ECPrivateKeySpec
import java.security.spec.ECPublicKeySpec
import java.time.Instant
import javax.crypto.Cipher
import javax.crypto.KeyAgreement
import javax.crypto.Mac
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec
import kotlin.math.floor

/**
 * Die Gerätehülle.
 *
 * WAS SIE IST. `ingest.php` nimmt Spur und Phasenkoordinaten nur
 * verschlossen an und weist Klartext mit `400 klartext` ab. Das
 * Handy verschließt mit dem ÖFFENTLICHEN Schlüssel des Kontos aus der
 * Kopplung; öffnen kann nur der Browser der Kontoinhaberin. Das Format steht
 * im Kopf von `tools/proben/huelle/referenz.py` und nur dort; die Prüffälle
 * (`GeraetehuelleTest`, `Geraetehuelle2Test`) halten diese Umsetzung gegen
 * dessen Vektoren, Byte für Byte.
 *
 * ZWEI FASSUNGEN NEBENEINANDER (seit KA-06c). Fassung 1 verschließt gegen
 * den Kontoschlüssel allein und belegt nicht, wer verschlossen hat. Fassung 2
 * nimmt den Geräteschlüssel dazu ([Absender]): Der Schlüssel der Hülle entsteht
 * aus dem flüchtigen UND dem statischen Geheimnis, und der Browser des Kontos
 * erkennt daran das Gerät, das er bei der Kopplung über den Vergleichswert
 * bestätigt hat. Welche Fassung gilt, entscheidet der Aufrufer; diese
 * Datei rechnet nur.
 *
 * FASSUNG 1 SENDET NIEMAND MEHR (seit Android 0.27.0, KA-21): Seit Web
 * 26.0.0 nimmt der Server sie nicht mehr an (E-KA-60), und der
 * [Sendekoerper] kennt nur noch Fassung 2. [spurstueck] und [einsatz] stehen
 * trotzdem weiter hier, mit ihren Vektortests (`GeraetehuelleTest`): als
 * Referenz, solange das Altformat im Anhebelauf des Browsers noch gelesen
 * wird. Sie fallen mit KA-07 (E-KA-99). Bis 0.26.0 sendete eine Kopplung
 * ohne `huelle: 2` sie weiter.
 *
 * DAS STATISCHE GEHEIMNIS RECHNET DIESE DATEI NICHT SELBST (seit KA-06f,
 * E-KA-71): Sie bestellt es beim [Absender], hinter dem auf dem Gerät der
 * Android Keystore steht. Einen privaten Geräteschlüssel bekommt sie nie zu
 * sehen.
 *
 * NUR BORDMITTEL: ECDH über `KeyAgreement`, AES-256-CBC über
 * `Cipher`, HMAC-SHA256 über `Mac`, HKDF daraus von Hand (RFC 5869, zwei
 * Runden für 64 Byte). Keine Fremdbibliothek; `docs/Lizenzen.md` bleibt
 * unberührt.
 *
 * ANDERS ALS DIE GARMIN braucht das Handy keinen Selbsttest der Byteordnung:
 * JCA liefert Schlüssel und Geheimnis big-endian wie WebCrypto, und die
 * Prüffälle laufen auf derselben Plattformschicht wie die App.
 *
 * WAS DAS HANDY NICHT VERSCHLIESST, weil es das nicht hat: die Reanimation
 * (sie bleibt bei der Garmin). Die Einsatzhülle trägt deshalb eine
 * leere `rea`-Liste — „es gibt keine", und das stimmt für ein Handy immer.
 */
object Geraetehuelle {

    const val FASSUNG = 1
    private val INFO = "nadoku-huelle-1".toByteArray(Charsets.UTF_8)

    const val FASSUNG2 = 2
    private val INFO2 = "nadoku-huelle-2".toByteArray(Charsets.UTF_8)
    private val ABSENDER_INFO = "nadoku-absender-1".toByteArray(Charsets.UTF_8)
    const val SID_LAENGE = 8

    /** Kopf eines Spurstücks der Fassung 2: `ts_von` (8), Merker, Zahl der Herkunftseinträge, Reserve (2). */
    const val SPUR_KOPF2 = 12

    /**
     * Der eine Merker, den ein Gerät setzt: letztes Stück der Aufzeichnung.
     * LUECKE (Bit 1) und UEBERNOMMEN (Bit 2) setzt nur das Konto — ein Gerät,
     * das sie setzt, weist der Browser als `innen` ab (`spur_lesen()` der
     * Referenz). Deshalb gibt es sie hier nicht einmal als Konstante.
     */
    const val M_SCHLUSS = 1

    /** Höhe ohne Wert in der Innenform. */
    const val HOEHE_UNBEKANNT = -32768
    /** Ganze Meter, geklemmt. */
    const val HOEHE_GRENZE = 32767

    private val kurve: ECParameterSpec by lazy {
        AlgorithmParameters.getInstance("EC").apply { init(ECGenParameterSpec("secp256r1")) }
            .getParameterSpec(ECParameterSpec::class.java)
    }

    // ---- Schlüssel ------------------------------------------------------

    /** SEC1 unkomprimiert (`0x04 ‖ X ‖ Y`, 65 Byte) → öffentlicher Schlüssel. Wirft bei Unsinn. */
    fun oeffentlich(sec1: ByteArray): ECPublicKey {
        require(sec1.size == 65 && sec1[0] == 0x04.toByte()) { "kein SEC1-Punkt" }
        val x = BigInteger(1, sec1.copyOfRange(1, 33))
        val y = BigInteger(1, sec1.copyOfRange(33, 65))
        /* LIEGT ER AUF DER KURVE? `KeyFactory` prüft das nicht — gemessen am
         * 17.10.2026 im Prüffall `schluesselPruefung`: Ein Punkt mit
         * verändertem Y ging durch. Ein Schlüssel neben der Kurve ist der
         * Anfang eines Angriffs auf ECDH (invalid curve), und ein Server, der
         * einen solchen schickt, soll keine Hülle bekommen.
         * y² = x³ + a·x + b (mod p). */
        val feld = (kurve.curve.field as java.security.spec.ECFieldFp).p
        require(x < feld && y < feld) { "Koordinate ausserhalb des Feldes" }
        val links = y.modPow(BigInteger.valueOf(2), feld)   // nicht BigInteger.TWO: erst ab API 33, minSdk ist 31 (Lint)
        val rechts = x.modPow(BigInteger.valueOf(3), feld).add(kurve.curve.a.multiply(x)).add(kurve.curve.b).mod(feld)
        require(links == rechts) { "Punkt liegt nicht auf P-256" }
        return KeyFactory.getInstance("EC")
            .generatePublic(ECPublicKeySpec(ECPoint(x, y), kurve)) as ECPublicKey
    }

    fun sec1(pub: ECPublicKey): ByteArray =
        byteArrayOf(0x04) + fest32(pub.w.affineX) + fest32(pub.w.affineY)

    /** 32 Byte big-endian, wie SEC1 und die Referenz sie schreiben. */
    internal fun fest32(z: BigInteger): ByteArray {
        val b = z.toByteArray()
        return when {
            b.size == 32 -> b
            b.size > 32 -> b.copyOfRange(b.size - 32, b.size)   // führendes 0x00
            else -> ByteArray(32 - b.size) + b
        }
    }

    /** Ist das ein brauchbarer Kontoschlüssel (Base64 eines Kurvenpunkts)? */
    fun schluesselGueltig(b64: String?): Boolean {
        if (b64.isNullOrEmpty()) return false
        return try {
            oeffentlich(java.util.Base64.getDecoder().decode(b64)); true
        } catch (e: Exception) {
            false
        }
    }

    /**
     * Nur für Prüffälle: der private Schlüssel zu `d` (32 Byte big-endian).
     * Die App hat seit KA-06f kein `d` mehr in der Hand — der Geräteschlüssel
     * liegt im Keystore —; die Prüffälle öffnen damit Hüllen mit dem
     * Kontoschlüssel der Vektoren.
     */
    fun privat(d: ByteArray): PrivateKey {
        require(d.size == 32) { "kein privater P-256-Schlüssel" }
        return KeyFactory.getInstance("EC").generatePrivate(ECPrivateKeySpec(BigInteger(1, d), kurve))
    }

    /** Ein frisches Schlüsselpaar auf P-256 — das flüchtige, je Hülle eines. */
    fun neuesPaar(): KeyPair = KeyPairGenerator.getInstance("EC")
        .apply { initialize(ECGenParameterSpec("secp256r1"), SecureRandom()) }.generateKeyPair()

    /** Nur für Prüffälle: ein Schlüsselpaar aus festem `d` und seinem SEC1-Punkt. */
    fun paarAus(d: ByteArray, sec1: ByteArray): KeyPair = KeyPair(
        oeffentlich(sec1),
        KeyFactory.getInstance("EC").generatePrivate(ECPrivateKeySpec(BigInteger(1, d), kurve)),
    )

    // ---- Die Hülle ------------------------------------------------------

    /** Die Bindung: je Feld 2 Byte Länge (big-endian) ‖ UTF-8. */
    fun bindung(art: String, clientRef: String, platz: String): ByteArray {
        var aus = ByteArray(0)
        for (feld in listOf(art, clientRef, platz)) {
            val b = feld.toByteArray(Charsets.UTF_8)
            require(b.size <= 0xFFFF) { "Bindung zu lang" }
            aus += byteArrayOf((b.size shr 8).toByte(), (b.size and 0xFF).toByte()) + b
        }
        return aus
    }

    private fun hmac(k: ByteArray, d: ByteArray): ByteArray =
        Mac.getInstance("HmacSHA256").run { init(SecretKeySpec(k, "HmacSHA256")); doFinal(d) }

    /** HKDF-SHA256 (RFC 5869), 64 Byte Ausgabe. */
    fun hkdf(ikm: ByteArray, salz: ByteArray, info: ByteArray): ByteArray {
        val prk = hmac(salz, ikm)
        val t1 = hmac(prk, info + byteArrayOf(1))
        val t2 = hmac(prk, t1 + info + byteArrayOf(2))
        return t1 + t2
    }

    /**
     * Eine Hülle bauen. `fluechtig` und `iv` nur für Prüffälle fest; sonst
     * je Hülle ein neues Schlüsselpaar und ein zufälliger IV.
     */
    fun verschliessen(
        klar: ByteArray, empfaengerSec1: ByteArray, art: String, clientRef: String, platz: String,
        fluechtig: KeyPair? = null, iv: ByteArray? = null,
    ): ByteArray {
        val empf = oeffentlich(empfaengerSec1)
        val paar = fluechtig ?: KeyPairGenerator.getInstance("EC")
            .apply { initialize(ECGenParameterSpec("secp256r1"), SecureRandom()) }.generateKeyPair()
        val epk = sec1(paar.public as ECPublicKey)
        val vektor = iv ?: ByteArray(16).also { SecureRandom().nextBytes(it) }
        val z = KeyAgreement.getInstance("ECDH").run {
            init(paar.private); doPhase(empf, true); generateSecret()
        }
        val okm = hkdf(z, epk, INFO + empfaengerSec1)
        val ct = Cipher.getInstance("AES/CBC/PKCS5Padding").run {
            init(Cipher.ENCRYPT_MODE, SecretKeySpec(okm.copyOfRange(0, 32), "AES"), IvParameterSpec(vektor))
            doFinal(klar)
        }
        val kopf = byteArrayOf(FASSUNG.toByte()) + epk + vektor
        val tag = hmac(okm.copyOfRange(32, 64), kopf + ct + bindung(art, clientRef, platz))
        return kopf + ct + tag
    }

    // ---- Die Hülle, Fassung 2 ---------------------------------------------

    /** Die Kennung eines Absenders: die ersten 8 Byte von SHA-256("nadoku-absender-1" ‖ SPK). */
    fun sid(spk: ByteArray): ByteArray {
        require(spk.size == 65 && spk[0] == 0x04.toByte()) { "kein SEC1-Punkt" }
        return MessageDigest.getInstance("SHA-256").digest(ABSENDER_INFO + spk).copyOfRange(0, SID_LAENGE)
    }

    private fun ecdh(privat: PrivateKey, gegen: ECPublicKey): ByteArray =
        KeyAgreement.getInstance("ECDH").run { init(privat); doPhase(gegen, true); generateSecret() }

    /**
     * Eine Hülle der Fassung 2: Kennbyte `0x02` ‖ SID ‖ EPK ‖ IV ‖ CT ‖ TAG.
     *
     * ZWEI GEHEIMNISSE. `Z_e` aus dem flüchtigen Paar wie in Fassung 1 —
     * deshalb öffnet ein gefundenes Handy keine alte Hülle — und `Z_s` aus
     * dem Geräteschlüssel. Das Prüfwort stimmt nur, wenn der Absender mit dem
     * privaten Teil zu dem Schlüssel rechnen konnte, der im Gerätebuch des Kontos steht. Das
     * ist ein gemeinsames Geheimnis, keine Signatur: Es beweist die Herkunft
     * dem Browser des Kontos und sonst niemandem (Kopf der Referenz).
     *
     * `Z_s` WIRD JE HÜLLE NEU BESTELLT ([Absender.vereinbaren]), nicht
     * abgelegt wie auf der Garmin und auch nicht über Aufrufe hinweg
     * gehalten. Dort kostet eine Schlüsselvereinbarung spürbar Laufzeit;
     * hier kostet sie einen Gang zum Keystore je Hülle, und das sind ein
     * oder zwei je Anfrage. Wer `Z_s` hat, baut Hüllen im Namen des Handys
     * — ein aufbewahrtes `Z_s` wäre der Schlüssel, den der Keystore gerade
     * nicht herausgibt. Hier lebt es bis zum Ende dieser Funktion.
     *
     * @throws org.genem.nadoku.handy.tresor.Schluesselausfall wenn die
     *   Schlüsselablage die Vereinbarung nicht hergibt
     *
     * `fluechtig` und `iv` nur für Prüffälle fest.
     */
    fun verschliessen2(
        klar: ByteArray, kontoSec1: ByteArray, absender: Absender, art: String, clientRef: String,
        platz: String, fluechtig: KeyPair? = null, iv: ByteArray? = null,
    ): ByteArray {
        val konto = oeffentlich(kontoSec1)
        val paar = fluechtig ?: neuesPaar()
        val epk = sec1(paar.public as ECPublicKey)
        val vektor = iv ?: ByteArray(16).also { SecureRandom().nextBytes(it) }
        require(vektor.size == 16) { "IV hat nicht 16 Byte" }
        val spk = absender.spk
        val zS = absender.vereinbaren(konto)
        require(zS.size == 32) { "Z_s hat nicht 32 Byte" }
        val okm = hkdf(ecdh(paar.private, konto) + zS, epk, INFO2 + kontoSec1 + spk)
        zS.fill(0)
        val ct = Cipher.getInstance("AES/CBC/PKCS5Padding").run {
            init(Cipher.ENCRYPT_MODE, SecretKeySpec(okm.copyOfRange(0, 32), "AES"), IvParameterSpec(vektor))
            doFinal(klar)
        }
        val kopf = byteArrayOf(FASSUNG2.toByte()) + sid(spk) + epk + vektor
        val tag = hmac(okm.copyOfRange(32, 64), kopf + ct + bindung(art, clientRef, platz))
        return kopf + ct + tag
    }

    private fun b64(b: ByteArray): String = java.util.Base64.getEncoder().encodeToString(b)

    // ---- Die Innenformen ------------------------------------------------

    /** Von der Null weg runden, wie PHPs `round()` und `referenz.py` — nicht `kotlin.math.round` (halb gerade). */
    fun runden(x: Double): Long = if (x >= 0) floor(x + 0.5).toLong() else -floor(-x + 0.5).toLong()

    /**
     * Ein Spurstück innen: je Punkt 14 Byte, big-endian —
     * Breite und Länge in 1e-7 Grad (`int32`), Höhe in ganzen Metern
     * (`int16`, `-32768` = keine), Zeit relativ zu `tsVon` (`uint32`).
     */
    fun spurInnen(punkte: List<Rohpunkt>, tsVon: Long): ByteArray {
        val puffer = ByteBuffer.allocate(punkte.size * 14).order(ByteOrder.BIG_ENDIAN)
        for (p in punkte) {
            val h = p.hoehe?.let { runden(it).coerceIn(-HOEHE_GRENZE.toLong(), HOEHE_GRENZE.toLong()) }
                ?: HOEHE_UNBEKANNT.toLong()
            puffer.putInt(runden(p.breite * 1e7).toInt())
            puffer.putInt(runden(p.laenge * 1e7).toInt())
            puffer.putShort(h.toInt().toShort())
            puffer.putInt(maxOf(0L, p.zeit - tsVon).toInt())
        }
        return puffer.array()
    }

    /**
     * Das Spurstück für `track` (JSON-Vertrag 3): `seq_from`, `punkte`,
     * `ts_von`, `ts_bis` im Klartext, dazu die Hülle. Platz `"<seq>:<n>"`.
     */
    fun spurstueck(
        art: String, clientRef: String, seqVon: Long, punkte: List<Rohpunkt>, kontoschluessel: ByteArray,
    ): JSONObject {
        val von = punkte.minOf { it.zeit }
        val bis = punkte.maxOf { it.zeit }
        val platz = "$seqVon:${punkte.size}"
        return JSONObject()
            .put("seq_from", seqVon)
            .put("punkte", punkte.size)
            .put("ts_von", von)
            .put("ts_bis", bis)
            .put("huelle", b64(verschliessen(spurInnen(punkte, von), kontoschluessel, art, clientRef, platz)))
    }

    /**
     * Die Einsatzhülle innen: `{"fassung":1,"phasen":[…],"rea":[]}`,
     * Zeiten in Sekunden seit `started_at`. Die Phasen stehen in
     * derselben Reihenfolge wie `phases[]` der Nachricht.
     */
    fun einsatzInnen(begonnenAt: String, phasen: List<Phaseneintrag>): String {
        val beginn = Instant.parse(begonnenAt).epochSecond
        val liste = JSONArray()
        for (p in phasen) {
            liste.put(
                JSONObject()
                    .put("phase", p.nummer)
                    .put("t", Instant.parse(p.at).epochSecond - beginn)
                    .put("lat", p.breite ?: JSONObject.NULL)
                    .put("lon", p.laenge ?: JSONObject.NULL),
            )
        }
        return JSONObject().put("fassung", 1).put("phasen", liste).put("rea", JSONArray()).toString()
    }

    fun einsatz(clientRef: String, begonnenAt: String, phasen: List<Phaseneintrag>, kontoschluessel: ByteArray): String =
        b64(verschliessen(einsatzInnen(begonnenAt, phasen).toByteArray(Charsets.UTF_8), kontoschluessel,
            "einsatz", clientRef, ""))

    // ---- Die Innenformen, Fassung 2 ---------------------------------------

    /**
     * Der Kopf eines Spurstücks: `ts_von` als ABSOLUTE Zeit (`uint64`
     * big-endian), Merker, Zahl der Herkunftseinträge (Gerät: immer 0),
     * Reserve. In Fassung 1 stand die absolute Zeit nur im Klartext neben der
     * Hülle und ließ sich dort verschieben; jetzt steht sie auch innen, und
     * der Browser hält beide gegeneinander (`zeit`).
     */
    private fun spurKopf2(tsVon: Long, schluss: Boolean): ByteArray {
        require(tsVon >= 0) { "ts_von vor 1970" }
        return ByteBuffer.allocate(SPUR_KOPF2).order(ByteOrder.BIG_ENDIAN)
            .putLong(tsVon).put((if (schluss) M_SCHLUSS else 0).toByte()).put(0).putShort(0).array()
    }

    /**
     * Ein Spurstück innen, Fassung 2: der Kopf, dann je Punkt 14 Byte wie in
     * Fassung 1 — die Punktzeiten relativ zum `tsVon` DIESES Kopfs.
     */
    fun spurInnen2(punkte: List<Rohpunkt>, tsVon: Long, schluss: Boolean): ByteArray =
        spurKopf2(tsVon, schluss) + spurInnen(punkte, tsVon)

    /**
     * Das Spurstück für `track`, Fassung 2. Außen dieselben Felder wie
     * [spurstueck]; `ts_von` ist der kleinste Zeitpunkt des Stücks und steht
     * innen und außen gleich.
     *
     * @param schluss Dieses Stück ist das letzte der Aufzeichnung — es trägt
     *   ALLE noch offenen Punkte, und das Paket ist abgeschlossen. Ein
     *   Stück, das `schluss` trägt, ohne bis ans Ende zu reichen, zählt beim
     *   Lesen nicht als Schluss; eines, das fehlt, fällt als `ohne-schluss` auf.
     */
    fun spurstueck2(
        art: String, clientRef: String, seqVon: Long, punkte: List<Rohpunkt>, kontoschluessel: ByteArray,
        absender: Absender, schluss: Boolean, fluechtig: KeyPair? = null, iv: ByteArray? = null,
    ): JSONObject {
        require(punkte.isNotEmpty()) { "ohne Punkte: schlussstueck2()" }
        val von = punkte.minOf { it.zeit }
        val bis = punkte.maxOf { it.zeit }
        val platz = "$seqVon:${punkte.size}"
        return JSONObject()
            .put("seq_from", seqVon)
            .put("punkte", punkte.size)
            .put("ts_von", von)
            .put("ts_bis", bis)
            .put("huelle", b64(verschliessen2(spurInnen2(punkte, von, schluss), kontoschluessel, absender,
                art, clientRef, platz, fluechtig, iv)))
    }

    /**
     * Das LEERE SCHLUSSSTÜCK (JSON-Vertrag 3.4, Konzept KA „KA-06d Entwurf"
     * Punkt 8): Ist beim Abschluss kein Punkt mehr offen, gäbe es kein
     * Stück, das SCHLUSS tragen könnte — und der Browser meldete eine
     * vollständige Aufzeichnung als `ohne-schluss`. Dann geht dieses Stück:
     * nur der Kopf, `punkte` 0, Platz `<seq>:0`.
     *
     * @param seq die Nummer, die der nächste Punkt bekommen hätte — sie
     *   schließt an das letzte Stück an
     * @param abschlussZeit Unix-Sekunde des Abschlusses; steht als `ts_von`
     *   und `ts_bis` im Klartext und als `ts_von` im Kopf, überall gleich
     */
    fun schlussstueck2(
        art: String, clientRef: String, seq: Long, abschlussZeit: Long, kontoschluessel: ByteArray,
        absender: Absender, fluechtig: KeyPair? = null, iv: ByteArray? = null,
    ): JSONObject = JSONObject()
        .put("seq_from", seq)
        .put("punkte", 0)
        .put("ts_von", abschlussZeit)
        .put("ts_bis", abschlussZeit)
        .put("huelle", b64(verschliessen2(spurKopf2(abschlussZeit, true), kontoschluessel, absender,
            art, clientRef, "$seq:0", fluechtig, iv)))

    /**
     * Die Einsatzhülle innen, Fassung 2:
     * `{"fassung":2,"z":…,"start":…,"schluss":…,"phasen":[…],"rea":[]}`.
     *
     * VON HAND GESCHRIEBEN, nicht über `JSONObject`: Die Referenz legt die
     * Reihenfolge der Schlüssel fest, und `JSONObject` verspricht keine. Die
     * Zahlen schreibt `Double.toString()` — für jede Koordinate, die in
     * Mitteleuropa vorkommt, dieselben Zeichen wie Pythons `json.dumps`; bei
     * Beträgen unter 0,001 weicht die Schreibweise ab (`1.0E-5` statt
     * `1e-05`), der Wert nicht, und gültiges JSON ist beides.
     *
     * @param z der Zähler des Pakets (`paket.zaehler`, E-KA-69) — derselbe, solange der Inhalt derselbe ist
     * @param schluss die Hülle des abgeschlossenen Einsatzes (`final`)
     */
    fun einsatzInnen2(begonnenAt: String, phasen: List<Phaseneintrag>, z: Long, schluss: Boolean): String {
        require(z >= 1) { "Zähler unter 1" }
        val beginn = Instant.parse(begonnenAt).epochSecond
        val liste = phasen.joinToString(",") { p ->
            "{\"phase\":${p.nummer},\"t\":${Instant.parse(p.at).epochSecond - beginn}," +
                "\"lat\":${zahl(p.breite)},\"lon\":${zahl(p.laenge)}}"
        }
        return "{\"fassung\":2,\"z\":$z,\"start\":$beginn,\"schluss\":$schluss,\"phasen\":[$liste],\"rea\":[]}"
    }

    /** Eine Koordinate als JSON-Zahl; ohne Wert — oder mit einem, der keine Zahl ist — `null`. */
    private fun zahl(x: Double?): String = if (x == null || x.isNaN() || x.isInfinite()) "null" else x.toString()

    fun einsatz2(
        clientRef: String, begonnenAt: String, phasen: List<Phaseneintrag>, kontoschluessel: ByteArray,
        absender: Absender, z: Long, schluss: Boolean, fluechtig: KeyPair? = null, iv: ByteArray? = null,
    ): String = b64(verschliessen2(einsatzInnen2(begonnenAt, phasen, z, schluss).toByteArray(Charsets.UTF_8),
        kontoschluessel, absender, "einsatz", clientRef, "", fluechtig, iv))
}

