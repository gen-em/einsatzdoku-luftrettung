package org.genem.nadoku.handy.senden

import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import org.json.JSONObject
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Test
import org.junit.runner.RunWith

/**
 * Die Gerätehülle auf dem KRYPTOANBIETER DES TELEFONS.
 *
 * WOFÜR ES DIESE DATEI GIBT. `GeraetehuelleTest` läuft unter Robolectric und
 * rechnet damit mit der Java-Kryptografie des Rechners (SunEC, SunJCE). Auf
 * dem Telefon rechnet Conscrypt — ein anderer Anbieter, mit eigenen
 * Eigenheiten bei Kurvenparametern, Schlüsselformaten und der Polsterung.
 * Dass beide dieselben Bytes liefern, zeigt nur ein Lauf auf einem
 * Android-System: die sechs Vektoren der Referenz, aus den Testdaten
 * (`tools/proben/huelle/vektoren.json`, über `androidTest.assets`).
 *
 * Aufruf: `android/LIESMICH.md`, „Der Emulator", `adb shell am instrument`.
 */
@RunWith(AndroidJUnit4::class)
class GeraetehuelleGeraetTest {

    private fun hex(s: String): ByteArray =
        if (s.isEmpty()) ByteArray(0) else ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    private val vektoren: JSONObject by lazy {
        JSONObject(InstrumentationRegistry.getInstrumentation().context.assets
            .open("vektoren.json").bufferedReader().readText())
    }

    @Test fun sechsVektorenByteGleich() {
        val empf = hex(vektoren.getJSONObject("empfaenger").getString("oeffentlich_hex"))
        val faelle = vektoren.getJSONArray("faelle")
        assertEquals(6, faelle.length())
        for (i in 0 until faelle.length()) {
            val f = faelle.getJSONObject(i)
            val h = Geraetehuelle.verschliessen(
                hex(f.getString("klartext_hex")), empf, f.getString("art"), f.getString("client_ref"),
                f.getString("platz"),
                fluechtig = Geraetehuelle.paarAus(hex(f.getString("fluechtig_privat_hex")), hex(f.getString("epk_hex"))),
                iv = hex(f.getString("iv_hex")),
            )
            assertArrayEquals(f.getString("name"), java.util.Base64.getDecoder().decode(f.getString("huelle_b64")), h)
        }
    }

    @Test fun kurvenpruefungAufDemTelefon() {
        val empf = hex(vektoren.getJSONObject("empfaenger").getString("oeffentlich_hex"))
        val kaputt = empf.copyOf().also { it[64] = (it[64] + 1).toByte() }
        assertFalse(Geraetehuelle.schluesselGueltig(java.util.Base64.getEncoder().encodeToString(kaputt)))
    }
}
