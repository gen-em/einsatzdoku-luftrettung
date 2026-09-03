package org.genem.nadoku.handy.senden

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import java.time.Instant

/** Wann gesendet wird (E-S4-07). */
class SendetaktTest {

    private val takt = Sendetakt()
    private val jetzt: Instant = Instant.parse("2026-07-16T10:00:00Z")

    /**
     * **Jedes von einem Menschen ausgelöste Ereignis sendet sofort.**
     *
     * `WIEDERVERBINDUNG` stand hier bis 0.8.1 mit in der Liste und ist mit
     * E2 herausgenommen — nicht, weil sich die Regel gelockert hätte,
     * sondern weil dieser eine Auslöser **nicht** von einem Menschen kommt,
     * sondern vom `ConnectivityManager`. Seine Bremse steht unten.
     */
    @Test fun jedesEreignisSendetSofort() {
        val geradeEben = jetzt.minusSeconds(5)
        for (a in listOf(
            Sendetakt.Ausloeser.PHASENWECHSEL,
            Sendetakt.Ausloeser.EINSATZABSCHLUSS,
            Sendetakt.Ausloeser.DIENSTENDE,
        )) {
            assertTrue("$a muss sofort senden", takt.faellig(a, jetzt, geradeEben))
        }
    }

    // ---- Wiederverbindung (E-S5Z-10, Z-S5Z-05) -----------------------------

    /**
     * Ein flatterndes Mobilfunknetz meldet `onAvailable` im Sekundentakt, und
     * jeder Lauf kostet mindestens eine Anfrage mit bcrypt-Prüfung am Server.
     * Die Frist steht auf beiden Seiten ihrer Grenze belegt.
     */
    @Test fun eineWiederverbindungNach59SekundenIstNichtFaellig() {
        assertFalse(
            takt.faellig(
                Sendetakt.Ausloeser.WIEDERVERBINDUNG, jetzt, jetzt.minusSeconds(59),
            )
        )
    }

    @Test fun eineWiederverbindungNach60SekundenIstFaellig() {
        assertTrue(
            takt.faellig(
                Sendetakt.Ausloeser.WIEDERVERBINDUNG, jetzt, jetzt.minusSeconds(60),
            )
        )
    }

    /** Ohne vorigen Versuch gibt es nichts abzuwarten. */
    @Test fun dieErsteWiederverbindungSendetSofort() {
        assertTrue(takt.faellig(Sendetakt.Ausloeser.WIEDERVERBINDUNG, jetzt, null))
    }

    /**
     * **Die Bremse gilt nur ihr.** Ein Dienstende zehn Sekunden nach einer
     * Wiederverbindung muss durchgehen — sonst hinge der Abschluss-Upload an
     * einer Frist, die für das Funknetz gemacht ist.
     */
    @Test fun dieBremseGiltNurDerWiederverbindung() {
        val vorZehnSekunden = jetzt.minusSeconds(10)
        assertFalse(
            takt.faellig(Sendetakt.Ausloeser.WIEDERVERBINDUNG, jetzt, vorZehnSekunden)
        )
        assertTrue(
            takt.faellig(Sendetakt.Ausloeser.DIENSTENDE, jetzt, vorZehnSekunden)
        )
    }

    @Test fun derWiederverbindungsabstandBleibtBeiEinerMinute() {
        org.junit.Assert.assertEquals(60L, Sendetakt.WIEDERVERBINDUNG_ABSTAND_S)
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
