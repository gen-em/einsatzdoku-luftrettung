package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.json.JSONArray
import org.json.JSONObject
import java.math.BigInteger
import java.nio.ByteBuffer
import java.nio.ByteOrder
import java.security.AlgorithmParameters
import java.security.KeyFactory
import java.security.KeyPair
import java.security.KeyPairGenerator
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
 * im Kopf von `tools/proben/huelle/referenz.py` und nur dort (Fassung 1);
 * die Prüffälle (`GeraetehuelleTest`) halten diese Umsetzung gegen dessen
 * Vektoren, Byte für Byte.
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
        val links = y.modPow(BigInteger.valueOf(2), feld)   // nicht BigInteger.TWO: erst ab API 33, minSdk ist 26 (Lint)
        val rechts = x.modPow(BigInteger.valueOf(3), feld).add(kurve.curve.a.multiply(x)).add(kurve.curve.b).mod(feld)
        require(links == rechts) { "Punkt liegt nicht auf P-256" }
        return KeyFactory.getInstance("EC")
            .generatePublic(ECPublicKeySpec(ECPoint(x, y), kurve)) as ECPublicKey
    }

    fun sec1(pub: ECPublicKey): ByteArray =
        byteArrayOf(0x04) + fest32(pub.w.affineX) + fest32(pub.w.affineY)

    private fun fest32(z: BigInteger): ByteArray {
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
}

