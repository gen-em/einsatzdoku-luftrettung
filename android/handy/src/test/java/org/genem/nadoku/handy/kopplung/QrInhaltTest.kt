package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Der Kopplungs-QR-Code (E-S4-15).
 *
 * Robolectric, weil `org.json` gebraucht wird — es ist ein Android-Bordmittel
 * und auf der reinen JVM nicht vorhanden.
 */
@RunWith(RobolectricTestRunner::class)
class QrInhaltTest {

    @Test fun gutfall() {
        val i = QrInhalt.lese("""{"server":"https://einsatz.beispieldomain.de/","code":"AB3K7Q"}""")
        assertEquals(QrInhalt("https://einsatz.beispieldomain.de/", "AB3K7Q"), i)
    }

    @Test fun serverWirdMitNormalisiert() {
        val i = QrInhalt.lese("""{"server":"einsatz.beispieldomain.de","code":"ab3k7q"}""")
        assertEquals("https://einsatz.beispieldomain.de/", i?.basis)
        assertEquals("AB3K7Q", i?.code)
    }

    /**
     * Die Kamera sieht alles Mögliche. Ein fremder Code ist kein Fehler,
     * sondern der Normalfall beim Zielen — also `null` und keine Ausnahme.
     */
    @Test fun fremdeQrCodesGebenNull() {
        assertNull(QrInhalt.lese(null))
        assertNull(QrInhalt.lese(""))
        assertNull(QrInhalt.lese("https://www.beispiel.de/paket/12345"))
        assertNull(QrInhalt.lese("WIFI:S:Gastnetz;T:WPA;P:geheim;;"))
        assertNull(QrInhalt.lese("{kaputt"))
        assertNull(QrInhalt.lese("{}"))
    }

    @Test fun unvollstaendigerInhaltGibtNull() {
        assertNull(QrInhalt.lese("""{"server":"https://beispieldomain.de/"}"""))
        assertNull(QrInhalt.lese("""{"code":"AB3K7Q"}"""))
        assertNull(QrInhalt.lese("""{"server":"einsatz","code":"AB3K7Q"}"""))
        assertNull(QrInhalt.lese("""{"server":"https://beispieldomain.de/","code":"AB3K7"}"""))
    }
}
