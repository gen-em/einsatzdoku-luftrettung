package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/** Der `geraet`-Block der Handy-Kopplung (E-S4-28). */
@RunWith(RobolectricTestRunner::class)
class GeraeteangabeTest {

    @Test fun vomGeraetFuelltDieFelderUndFaelltNichtUm() {
        val g = Geraeteangabe.vomGeraet(breite = 1080, hoehe = 2340, appFassung = "0.2.0")
        assertEquals("handy", g.art)
        assertEquals(null, g.teil)
        assertEquals(1080, g.br)
        assertEquals(2340, g.ho)
        assertEquals(true, g.touch)
        assertEquals("0.2.0", g.app)
        // Hersteller, Modell, Fassung und SDK-Stufe kommen von Build.*; unter
        // Robolectric sind es Platzhalter. Geprüft wird, dass sie überhaupt
        // gelesen werden, nicht welcher Wert dort steht.
        assertTrue("sdk muss gelesen worden sein", (g.sdk ?: 0) > 0)
    }

    /** Vertrag 1a: an einer Statistikangabe scheitert keine Kopplung. */
    @Test fun fehlendeAngabenWerdenZuNullUndNichtZuEinemFehler() {
        val g = Geraeteangabe.vomGeraet(breite = null, hoehe = null, appFassung = null)
        val j = g.alsJson()
        assertTrue(j.isNull("br"))
        assertTrue(j.isNull("ho"))
        assertTrue(j.isNull("app"))
        assertEquals("handy", j.getString("art"))
    }

    @Test fun ciqStehtNichtImHandyBlock() {
        val j = Geraeteangabe.vomGeraet(1080, 2340, "0.2.0").alsJson()
        assertFalse(
            "ciq ist die Uhr-Plattform — ein Feld, das es für ein Handy nicht gibt, " +
                "ist etwas anderes als eines, das das Gerät nicht kennt",
            j.has("ciq")
        )
    }
}
