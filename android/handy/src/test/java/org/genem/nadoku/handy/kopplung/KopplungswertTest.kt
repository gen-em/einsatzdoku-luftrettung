package org.genem.nadoku.handy.kopplung

import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File

/**
 * Zusage und Vergleichswert der Kopplung gegen die Vektoren der Referenz.
 *
 * Anlass: Nr. 563 — Handy und Webseite müssen dieselben sechs Ziffern
 * zeigen, sonst lehnt ein Mensch eine richtige Kopplung ab (oder, schlimmer,
 * gewöhnt sich an abweichende Werte). Der Block `kopplung` aus
 * `tools/proben/huelle/vektoren-2.json`, an Ort und Stelle gelesen.
 */
@RunWith(RobolectricTestRunner::class)
class KopplungswertTest {

    private val k: JSONObject by lazy {
        JSONObject(File("../../tools/proben/huelle/vektoren-2.json").readText()).getJSONObject("kopplung")
    }

    private fun hex(s: String): ByteArray =
        ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    private fun zuHex(b: ByteArray): String = b.joinToString("") { "%02x".format(it) }

    @Test fun zusageByteGleich() {
        assertEquals(k.getString("zusage_hex"), zuHex(Kopplungswert.zusage(hex(k.getString("n_g_hex")), hex(k.getString("spk_hex")))))
    }

    @Test fun wertWieReferenz() {
        val w = Kopplungswert.wert(
            hex(k.getString("konto_hex")), hex(k.getString("spk_hex")), hex(k.getString("n_g_hex")), hex(k.getString("n_b_hex")),
        )
        assertEquals(k.getString("wert"), w)
        assertTrue(Regex("[0-9]{6}").matches(w))
    }

    /**
     * Die vier Gegenlagen der Datei. Wo der Browser `zusage` meldet, passt
     * die Zusage nicht zu dem, was offengelegt wurde — das Handy prüft das
     * nicht selbst, aber seine Rechnung muss den Unterschied ergeben. Wo ein
     * Schlüssel getauscht ist, steht ein anderer Wert auf dem Bildschirm.
     */
    @Test fun gegenlagen() {
        val g = k.getJSONArray("gegenlagen")
        assertEquals(4, g.length())
        for (i in 0 until g.length()) {
            val f = g.getJSONObject(i)
            if (f.has("grund")) {
                assertEquals("zusage", f.getString("grund"))
                assertNotEquals(
                    f.getString("name"), f.getString("zusage_hex"),
                    zuHex(Kopplungswert.zusage(hex(f.getString("n_g_hex")), hex(f.getString("spk_hex")))),
                )
            } else {
                val w = Kopplungswert.wert(
                    hex(f.getString("konto_hex")), hex(f.getString("spk_hex")), hex(f.getString("n_g_hex")), hex(f.getString("n_b_hex")),
                )
                assertEquals(f.getString("name"), f.getString("anderer_wert"), w)
                assertNotEquals(f.getString("name"), k.getString("wert"), w)
            }
        }
    }

    /** Führende Nullen bleiben stehen: Der Wert ist eine Zeichenkette aus sechs Ziffern, keine Zahl. */
    @Test fun sechsZiffernAuchBeiKleinemWert() {
        val spk = hex(k.getString("spk_hex"))
        val konto = hex(k.getString("konto_hex"))
        val nB = ByteArray(16)
        var klein: String? = null
        // Eine von zehn Zufallszahlen ergibt einen Wert unter 100 000 — hier fest abgezählt, nicht gewürfelt.
        for (i in 0 until 400) {
            val nG = ByteArray(16).also { it[0] = i.toByte(); it[1] = (i shr 8).toByte() }
            val w = Kopplungswert.wert(konto, spk, nG, nB)
            assertEquals(6, w.length)
            if (w.startsWith("0")) klein = w
        }
        assertTrue("unter 400 Werten keiner mit führender Null", klein != null)
    }

    @Test fun zufallUndLaengen() {
        val a = Kopplungswert.zufall()
        assertEquals(16, a.size)
        assertFalse(a.contentEquals(Kopplungswert.zufall()))
        val spk = hex(k.getString("spk_hex"))
        assertTrue(runCatching { Kopplungswert.zusage(ByteArray(15), spk) }.isFailure)
        assertTrue(runCatching { Kopplungswert.zusage(ByteArray(16), spk.copyOf(64)) }.isFailure)
        assertTrue(runCatching { Kopplungswert.wert(spk, spk, ByteArray(16), ByteArray(17)) }.isFailure)
    }
}
