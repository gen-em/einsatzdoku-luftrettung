package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.json.JSONObject
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.math.BigInteger
import java.nio.ByteBuffer
import java.security.AlgorithmParameters
import java.security.KeyFactory
import java.security.spec.ECGenParameterSpec
import java.security.spec.ECParameterSpec
import java.security.spec.ECPrivateKeySpec
import java.util.Base64
import javax.crypto.Cipher
import javax.crypto.KeyAgreement
import javax.crypto.Mac
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * Die Gerätehülle gegen die Vektoren der Referenz (S11-09).
 *
 * Anlass: Nr. 43 — drei Umsetzungen (Garmin, Android, Browser) müssen
 * dasselbe Format byte-gleich schreiben, und nur ein gemeinsamer Satz fester
 * Eingaben zeigt das: `tools/proben/huelle/vektoren.json`, gelesen an Ort und
 * Stelle und nicht kopiert — eine Kopie hier wäre eine zweite Wahrheit, die
 * beim nächsten `referenz.py erzeugen` still veraltet.
 */
@RunWith(RobolectricTestRunner::class)
class GeraetehuelleTest {

    private val vektoren: JSONObject by lazy {
        // Arbeitsverzeichnis der Prüffälle ist android/handy (Gradle).
        JSONObject(File("../../tools/proben/huelle/vektoren.json").readText())
    }

    private fun hex(s: String): ByteArray =
        if (s.isEmpty()) ByteArray(0) else ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    private val empfaenger: ByteArray get() = hex(vektoren.getJSONObject("empfaenger").getString("oeffentlich_hex"))

    /** Jeder Fall der Referenz, mit festem flüchtigem Schlüssel und IV: dieselben Bytes. */
    @Test fun alleFaelleByteGleich() {
        val faelle = vektoren.getJSONArray("faelle")
        assertEquals(6, faelle.length())
        for (i in 0 until faelle.length()) {
            val f = faelle.getJSONObject(i)
            val paar = Geraetehuelle.paarAus(hex(f.getString("fluechtig_privat_hex")), hex(f.getString("epk_hex")))
            val h = Geraetehuelle.verschliessen(
                hex(f.getString("klartext_hex")), empfaenger, f.getString("art"), f.getString("client_ref"),
                f.getString("platz"), fluechtig = paar, iv = hex(f.getString("iv_hex")),
            )
            assertArrayEquals(f.getString("name"), Base64.getDecoder().decode(f.getString("huelle_b64")), h)
        }
    }

    /** Die Innenform eines Spurstücks wie `referenz.py innen_binaer(beispielpunkte(3))`. */
    @Test fun spurInnenWieReferenz() {
        val soll = (0 until vektoren.getJSONArray("faelle").length())
            .map { vektoren.getJSONArray("faelle").getJSONObject(it) }
            .first { it.getString("name") == "spur-binaer-3" }
        val punkte = (0 until 3).map { i ->
            Rohpunkt(47.7261 + i * 0.00031, 10.3159 + i * 0.00047, 674.0 + (i % 37) * 0.7, i * 5L)
        }
        assertArrayEquals(hex(soll.getString("klartext_hex")), Geraetehuelle.spurInnen(punkte, 0))
        assertEquals("120:3", soll.getString("platz"))
    }

    /** Von der Null weg — nicht halb gerade wie `kotlin.math.round`. */
    @Test fun rundenWiePhp() {
        assertEquals(3L, Geraetehuelle.runden(2.5))
        assertEquals(-13L, Geraetehuelle.runden(-12.5))
        assertEquals(4501L, Geraetehuelle.runden(4500.5))
        assertEquals(0L, Geraetehuelle.runden(-0.4))
    }

    /** Höhe: ganze Meter, geklemmt; ohne Wert -32768 (E-S11-39, -66). */
    @Test fun hoeheGeklemmtUndUnbekannt() {
        val innen = Geraetehuelle.spurInnen(
            listOf(Rohpunkt(1.0, 2.0, null, 10), Rohpunkt(1.0, 2.0, 40_000.0, 11), Rohpunkt(-1.0, -2.0, 4500.5, 12)),
            tsVon = 10,
        )
        val b = ByteBuffer.wrap(innen)
        assertEquals(-32768, b.getShort(8).toInt())
        assertEquals(32767, b.getShort(14 + 8).toInt())
        assertEquals(4501, b.getShort(28 + 8).toInt())
        assertEquals(-10_000_000, b.getInt(28))
        assertEquals(2, b.getInt(28 + 10))
    }

    /** Eine echte Hülle (Zufall) geht mit dem privaten Schlüssel des Empfängers auf. */
    @Test fun echteHuelleGehtAuf() {
        val klar = "nadoku-handy".toByteArray()
        val h1 = Geraetehuelle.verschliessen(klar, empfaenger, "spur", "ah-1-0000000001", "0:1")
        val h2 = Geraetehuelle.verschliessen(klar, empfaenger, "spur", "ah-1-0000000001", "0:1")
        assertFalse("je Hülle ein neues Paar und ein neuer IV", h1.contentEquals(h2))
        assertArrayEquals(klar, oeffnen(h1, "spur", "ah-1-0000000001", "0:1"))
        // Eine andere Bindung geht nicht auf.
        assertTrue(runCatching { oeffnen(h1, "spur", "ah-1-0000000001", "1:1") }.isFailure)
    }

    /** Die Einsatzhülle: Zeiten seit Beginn, Reihenfolge der Phasen, leere `rea`. */
    @Test fun einsatzInnen() {
        val o = JSONObject(
            Geraetehuelle.einsatzInnen(
                "2026-07-16T08:31:05Z",
                listOf(
                    Phaseneintrag(2, "2026-07-16T08:31:05Z", 47.7261, 10.3186),
                    Phaseneintrag(4, "2026-07-16T08:51:02Z", null, null),
                ),
            ),
        )
        assertEquals(1, o.getInt("fassung"))
        assertEquals(0, o.getJSONArray("rea").length())
        val p = o.getJSONArray("phasen")
        assertEquals(0, p.getJSONObject(0).getInt("t"))
        assertEquals(47.7261, p.getJSONObject(0).getDouble("lat"), 1e-12)
        assertEquals(4, p.getJSONObject(1).getInt("phase"))
        assertEquals(1197, p.getJSONObject(1).getInt("t"))
        assertTrue(p.getJSONObject(1).isNull("lat"))
    }

    @Test fun schluesselPruefung() {
        assertTrue(Geraetehuelle.schluesselGueltig(Base64.getEncoder().encodeToString(empfaenger)))
        assertFalse(Geraetehuelle.schluesselGueltig(null))
        assertFalse(Geraetehuelle.schluesselGueltig(""))
        val kaputt = empfaenger.copyOf().also { it[64] = (it[64] + 1).toByte() }   // nicht auf der Kurve
        assertFalse(Geraetehuelle.schluesselGueltig(Base64.getEncoder().encodeToString(kaputt)))
    }

    // Öffnen gibt es in der App nicht — nur hier, im Prüffall.
    private fun oeffnen(h: ByteArray, art: String, ref: String, platz: String): ByteArray {
        val kurve = AlgorithmParameters.getInstance("EC").apply { init(ECGenParameterSpec("secp256r1")) }
            .getParameterSpec(ECParameterSpec::class.java)
        val privat = KeyFactory.getInstance("EC").generatePrivate(
            ECPrivateKeySpec(BigInteger(vektoren.getJSONObject("empfaenger").getString("privat_hex"), 16), kurve),
        )
        val epk = h.copyOfRange(1, 66)
        val iv = h.copyOfRange(66, 82)
        val ct = h.copyOfRange(82, h.size - 32)
        val z = KeyAgreement.getInstance("ECDH").run {
            init(privat); doPhase(Geraetehuelle.oeffentlich(epk), true); generateSecret()
        }
        val okm = Geraetehuelle.hkdf(z, epk, "nadoku-huelle-1".toByteArray() + empfaenger)
        val tag = Mac.getInstance("HmacSHA256").run {
            init(SecretKeySpec(okm.copyOfRange(32, 64), "HmacSHA256"))
            doFinal(h.copyOfRange(0, h.size - 32) + Geraetehuelle.bindung(art, ref, platz))
        }
        check(tag.contentEquals(h.copyOfRange(h.size - 32, h.size))) { "pruefwert" }
        return Cipher.getInstance("AES/CBC/PKCS5Padding").run {
            init(Cipher.DECRYPT_MODE, SecretKeySpec(okm.copyOfRange(0, 32), "AES"), IvParameterSpec(iv))
            doFinal(ct)
        }
    }
}
