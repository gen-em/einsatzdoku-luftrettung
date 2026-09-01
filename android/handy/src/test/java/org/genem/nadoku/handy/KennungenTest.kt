package org.genem.nadoku.handy

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.genem.nadoku.gemeinsam.Kennungen
import kotlin.random.Random

/** Die Client-Kennungen (Vertrag 8, E-S4-09). */
class KennungenTest {

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        var schreibvorgaenge = 0
        override fun lies() = wert
        override fun schreib(wert: Long) {
            this.wert = wert
            schreibvorgaenge++
        }
    }

    @Test fun praefixeNachESVier09() {
        val k = Kennungen(Merkzaehler(), Random(1))
        assertTrue(k.einsatz().startsWith("am-"))
        assertTrue(k.ruhesegment().startsWith("ar-"))
        assertTrue(k.dienst().startsWith("ad-"))
        assertEquals("wm", Kennungen.EINSATZ_UHR)
    }

    /** Bauform der Uhr seit 1.7.0: Präfix, Zähler, zehnstelliger Zufall. */
    @Test fun bauform() {
        val k = Kennungen(Merkzaehler(41), Random(7))
        val kennung = k.einsatz()
        val teile = kennung.split("-")
        assertEquals(3, teile.size)
        assertEquals("am", teile[0])
        assertEquals("42", teile[1])
        assertEquals("Der Zufallsanteil hat zehn Stellen", 10, teile[2].length)
        assertTrue(teile[2].all { it.isDigit() })
    }

    /** Vertrag 3.2: höchstens 64 Zeichen, keine Leerzeichen. */
    @Test fun bleibtInnerhalbDerVertragsgrenzen() {
        val k = Kennungen(Merkzaehler(Kennungen.UEBERLAUF - 1), Random(3))
        val kennung = k.dienst()
        assertTrue(kennung.length <= Kennungen.HOECHSTLAENGE)
        assertTrue(!kennung.contains(" "))
    }

    /** KEIN ZEITSTEMPEL — der Grund, warum die Uhr die Bauform 1.7.0 hat. */
    @Test fun keinZeitstempelInDerKennung() {
        val zaehler = Merkzaehler()
        val k = Kennungen(zaehler, Random(11))
        val jetzt = System.currentTimeMillis() / 1000
        repeat(20) {
            val kennung = k.einsatz()
            val zahlen = kennung.split("-")[2]
            // Eine Sekundenzahl der Gegenwart hat zehn Stellen und begänne
            // mit 17…; der Zufallsanteil darf sie nicht zufällig treffen —
            // geprüft wird, dass er nicht DIE Zeit ist.
            assertNotEquals(jetzt.toString(), zahlen)
        }
    }

    /**
     * Der Zähler wird VOR der Ausgabe gesichert. Ein Absturz dazwischen darf
     * höchstens eine Nummer überspringen, niemals eine doppelt vergeben.
     */
    @Test fun derZaehlerWirdSofortGesichert() {
        val zaehler = Merkzaehler()
        val k = Kennungen(zaehler, Random(5))
        k.einsatz()
        assertEquals(1, zaehler.schreibvorgaenge)
        assertEquals(1L, zaehler.wert)
    }

    @Test fun derZaehlerLaeuftUeberOhneSichZuWiederholen() {
        val zaehler = Merkzaehler(Kennungen.UEBERLAUF)
        val k = Kennungen(zaehler, Random(5))
        assertTrue(k.einsatz().startsWith("am-1-"))
    }

    @Test fun einKaputterZaehlerWirdGeheilt() {
        val zaehler = Merkzaehler(-17)
        val k = Kennungen(zaehler, Random(5))
        assertTrue(k.einsatz().startsWith("am-1-"))
    }

    /** Zweitausend Kennungen, keine doppelte — das ist die ganze Zusage. */
    @Test fun keineKennungWiederholtSich() {
        val k = Kennungen(Merkzaehler(), Random(99))
        val gesehen = HashSet<String>()
        repeat(2000) {
            val kennung = if (it % 3 == 0) k.einsatz() else k.ruhesegment()
            assertTrue("Kennung doppelt: $kennung", gesehen.add(kennung))
        }
        assertEquals(2000, gesehen.size)
    }
}
