package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Test

/** Die drei Zustände der Sync-Anzeige (Backlog Nr. 11). */
class SyncstandTest {

    @Test fun ohneServerAdresseNichtEingerichtet() {
        assertEquals(
            Syncstand.NichtEingerichtet(Syncstand.Fehlt.SERVERADRESSE),
            Syncstand.ermittle(null, gekoppelt = false, rueckstand = 0)
        )
        assertEquals(
            Syncstand.NichtEingerichtet(Syncstand.Fehlt.SERVERADRESSE),
            Syncstand.ermittle("", gekoppelt = true, rueckstand = 0)
        )
    }

    @Test fun ohneKopplungNichtEingerichtet() {
        assertEquals(
            Syncstand.NichtEingerichtet(Syncstand.Fehlt.KOPPLUNG),
            Syncstand.ermittle("https://beispieldomain.de/", gekoppelt = false, rueckstand = 0)
        )
    }

    @Test fun offenePaketeErgebenRueckstand() {
        assertEquals(
            Syncstand.Rueckstand(3),
            Syncstand.ermittle("https://beispieldomain.de/", gekoppelt = true, rueckstand = 3)
        )
    }

    @Test fun vollstaendigNurMitServerUndKopplung() {
        assertEquals(
            Syncstand.Vollstaendig,
            Syncstand.ermittle("https://beispieldomain.de/", gekoppelt = true, rueckstand = 0)
        )
    }

    /**
     * Der Fehler, den es an der Uhr schon einmal gab: „vollständig" bedeutete
     * „nichts zu senden" — auch ohne Kopplung. Eine App, die „alles gesendet"
     * sagt, während sie nirgendwohin sendet, ist schlimmer als eine, die
     * schweigt.
     */
    @Test fun ohneKopplungNiemalsVollstaendig() {
        val s = Syncstand.ermittle("https://beispieldomain.de/", gekoppelt = false, rueckstand = 0)
        assertEquals(Syncstand.NichtEingerichtet(Syncstand.Fehlt.KOPPLUNG), s)
    }
}
