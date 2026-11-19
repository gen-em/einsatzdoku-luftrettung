package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.nio.ByteBuffer
import java.util.Base64

/**
 * Die Bausteine der Gerätehülle, die keine Fassung haben: Runden, Höhe,
 * Schlüsselprüfung.
 *
 * Anlass: Nr. 43 — drei Umsetzungen (Garmin, Android, Browser) müssen
 * dasselbe Format byte-gleich schreiben. Die Hülle selbst und die Innenform
 * eines Spurstücks hält `Geraetehuelle2Test` gegen die Vektoren der Referenz
 * (`tools/proben/huelle/vektoren-2.json`); hier steht, was dort nicht eigens
 * vorkommt.
 *
 * BIS ANDROID 0.27.0 prüfte diese Datei auch die Hülle der Fassung 1 gegen
 * `vektoren.json`, Byte für Byte, samt Einsatzhülle und Öffnen. Mit KA-27
 * ist die Fassung aus der App gestrichen, und ihre Fälle mit ihr.
 */
@RunWith(RobolectricTestRunner::class)
class GeraetehuelleTest {

    /** Von der Null weg — nicht halb gerade wie `kotlin.math.round`. */
    @Test fun rundenWiePhp() {
        assertEquals(3L, Geraetehuelle.runden(2.5))
        assertEquals(-13L, Geraetehuelle.runden(-12.5))
        assertEquals(4501L, Geraetehuelle.runden(4500.5))
        assertEquals(0L, Geraetehuelle.runden(-0.4))
    }

    /** Höhe: ganze Meter, geklemmt; ohne Wert -32768. */
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

    /** Der Kontoschlüssel der Vektoren gilt; leer, `null` und ein Punkt neben der Kurve nicht. */
    @Test fun schluesselPruefung() {
        val konto = Huellenoeffner.konto
        assertTrue(Geraetehuelle.schluesselGueltig(Base64.getEncoder().encodeToString(konto)))
        assertFalse(Geraetehuelle.schluesselGueltig(null))
        assertFalse(Geraetehuelle.schluesselGueltig(""))
        val kaputt = konto.copyOf().also { it[64] = (it[64] + 1).toByte() }   // nicht auf der Kurve
        assertFalse(Geraetehuelle.schluesselGueltig(Base64.getEncoder().encodeToString(kaputt)))
    }
}
