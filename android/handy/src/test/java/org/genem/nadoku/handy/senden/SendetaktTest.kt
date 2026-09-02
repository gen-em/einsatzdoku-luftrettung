package org.genem.nadoku.handy.senden

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import java.time.Instant

/** Wann gesendet wird (E-S4-07). */
class SendetaktTest {

    private val takt = Sendetakt()
    private val jetzt: Instant = Instant.parse("2026-07-16T10:00:00Z")

    @Test fun jedesEreignisSendetSofort() {
        val geradeEben = jetzt.minusSeconds(5)
        for (a in listOf(
            Sendetakt.Ausloeser.PHASENWECHSEL,
            Sendetakt.Ausloeser.EINSATZABSCHLUSS,
            Sendetakt.Ausloeser.DIENSTENDE,
            Sendetakt.Ausloeser.WIEDERVERBINDUNG,
        )) {
            assertTrue("$a muss sofort senden", takt.faellig(a, jetzt, geradeEben))
        }
    }

    @Test fun derTaktWartetFuenfzehnMinuten() {
        assertFalse(takt.faellig(Sendetakt.Ausloeser.TAKT, jetzt, jetzt.minusSeconds(899)))
        assertTrue(takt.faellig(Sendetakt.Ausloeser.TAKT, jetzt, jetzt.minusSeconds(900)))
    }

    @Test fun ohneVorigenVersuchWirdSofortGesendet() {
        assertTrue(takt.faellig(Sendetakt.Ausloeser.TAKT, jetzt, null))
    }

    /**
     * 900 s liegen dicht am gemessenen Median der Garmin (1 020 s, R19) —
     * daran hängt die spätere Mengenbremse, die beide Clients gleich
     * behandeln soll.
     */
    @Test fun derAbstandBleibtBeiFuenfzehnMinuten() {
        org.junit.Assert.assertEquals(900L, Sendetakt.ABSTAND_S)
    }
}
