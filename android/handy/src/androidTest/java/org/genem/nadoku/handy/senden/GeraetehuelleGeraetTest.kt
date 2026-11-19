package org.genem.nadoku.handy.senden

import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import org.genem.nadoku.handy.tresor.Absender
import org.json.JSONObject
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Test
import org.junit.runner.RunWith
import java.security.interfaces.ECPublicKey
import javax.crypto.KeyAgreement

/**
 * Die Gerätehülle auf dem KRYPTOANBIETER DES TELEFONS.
 *
 * WOFÜR ES DIESE DATEI GIBT. `Geraetehuelle2Test` läuft unter Robolectric und
 * rechnet damit mit der Java-Kryptografie des Rechners (SunEC, SunJCE). Auf
 * dem Telefon rechnet Conscrypt — ein anderer Anbieter, mit eigenen
 * Eigenheiten bei Kurvenparametern, Schlüsselformaten und der Polsterung.
 * Dass beide dieselben Bytes liefern, zeigt nur ein Lauf auf einem
 * Android-System: die acht Fälle der Referenz, aus den Testdaten
 * (`tools/proben/huelle/vektoren-2.json`, über `androidTest.assets`).
 *
 * SEIT ANDROID 0.27.1 FASSUNG 2 (KA-27). Bis dahin prüfte diese Datei die
 * sechs Vektoren der Fassung 1 (`vektoren.json`); die Fassung ist aus der
 * App gestrichen, und mit ihr ihre Vektoren. Der Absender rechnet hier mit
 * dem festen Schlüssel der Vektoren, nicht im Keystore — geprüft wird die
 * Hülle, nicht die Schlüsselablage (die prüft `GeraeteschluesselGeraetTest`).
 *
 * Aufruf: `android/LIESMICH.md`, „Der Emulator", `adb shell am instrument`.
 */
@RunWith(AndroidJUnit4::class)
class GeraetehuelleGeraetTest {

    private fun hex(s: String): ByteArray =
        if (s.isEmpty()) ByteArray(0) else ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    private val vektoren: JSONObject by lazy {
        JSONObject(InstrumentationRegistry.getInstrumentation().context.assets
            .open("vektoren-2.json").bufferedReader().readText())
    }

    private val konto: ByteArray get() = hex(vektoren.getJSONObject("konto").getString("oeffentlich_hex"))

    /** Ein Absender mit festem Schlüssel aus der Datei — nur für diesen Prüffall. */
    private fun absender(wer: String): Absender {
        val o = if (wer == "konto") vektoren.getJSONObject("konto")
        else vektoren.getJSONObject("geraete").getJSONObject(wer)
        val privat = Geraetehuelle.privat(hex(o.getString("privat_hex")))
        val oeffentlich = hex(o.getString("oeffentlich_hex"))
        return object : Absender {
            override val spk: ByteArray = oeffentlich
            override fun vereinbaren(konto: ECPublicKey): ByteArray =
                KeyAgreement.getInstance("ECDH").run { init(privat); doPhase(konto, true); generateSecret() }
        }
    }

    @Test fun achtVektorenByteGleich() {
        val faelle = vektoren.getJSONArray("faelle")
        assertEquals(8, faelle.length())
        for (i in 0 until faelle.length()) {
            val f = faelle.getJSONObject(i)
            val h = Geraetehuelle.verschliessen2(
                hex(f.getString("klartext_hex")), konto, absender(f.getString("absender")), f.getString("art"),
                f.getString("client_ref"), f.getString("platz"),
                fluechtig = Geraetehuelle.paarAus(hex(f.getString("fluechtig_privat_hex")), hex(f.getString("epk_hex"))),
                iv = hex(f.getString("iv_hex")),
            )
            assertArrayEquals(f.getString("name"), java.util.Base64.getDecoder().decode(f.getString("huelle_b64")), h)
        }
    }

    @Test fun kurvenpruefungAufDemTelefon() {
        val kaputt = konto.copyOf().also { it[64] = (it[64] + 1).toByte() }
        assertFalse(Geraetehuelle.schluesselGueltig(java.util.Base64.getEncoder().encodeToString(kaputt)))
    }
}
