package org.genem.nadoku.handy.puffer

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Abgewiesene Pakete tauchen jetzt irgendwo auf (B-S5Z-06, E-S5Z-12).
 *
 * DER FEHLER, GEGEN DEN DIESE ZEILEN STEHEN: `alsFehlerhaftMerken` nimmt ein
 * Paket aus der Warteschlange **und** aus dem Rückstand. Beides ist richtig —
 * es wird nicht wiederholt (Vertrag 5), und ein Rückstand, der sich nie
 * abbaut, wäre eine Anzeige ohne Aussage. Die Folge war aber, dass es
 * **nirgends** mehr auftauchte: Die App sagte „Alles gesendet", während beim
 * Server ein Segment offen blieb.
 *
 * Geprüft wird gegen ein **echtes SQLite** (Robolectric), nicht gegen eine
 * Attrappe: Die Zusicherung ist eine über eine Abfrage, und eine Attrappe
 * bestätigte nur, dass sie so geschrieben wurde, wie sie geschrieben wurde.
 */
@RunWith(RobolectricTestRunner::class)
class AbgewieseneTest {

    private lateinit var puffer: Puffer

    @Before fun aufbauen() {
        puffer = Puffer(ApplicationProvider.getApplicationContext<Context>())
    }

    @After fun abbauen() = puffer.close()

    /** Ein abgeschlossenes, unbestätigtes Paket — der Normalfall im Rückstand. */
    private fun paketMitRueckstand(ref: String): Long {
        val id = puffer.paketAnlegen(
            clientRef = ref, art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-03",
            dienstRef = null, begonnenAt = "2026-09-03T07:02:00Z",
        )
        puffer.paketSchliessen(id, "2026-09-03T19:02:00Z", streckeM = 0, anstiegM = 0)
        return id
    }

    @Test fun ohneAbweisungIstDieZahlNull() {
        paketMitRueckstand("r-1")
        assertEquals(0, puffer.abgewiesen())
        assertEquals(1, puffer.rueckstand())
    }

    /**
     * **Der Kern:** Ein abgewiesenes Paket verlässt den Rückstand und
     * erscheint in der neuen Zahl. Beide Zeilen zusammen sind die Zusicherung
     * — die erste allein galt schon vorher, und genau sie war das Problem.
     */
    @Test fun einAbgewiesenesPaketVerlaesstDenRueckstandUndErscheintHier() {
        val id = paketMitRueckstand("r-2")
        assertEquals(1, puffer.rueckstand())

        puffer.alsFehlerhaftMerken(id)

        assertEquals("aus dem Rückstand genommen", 0, puffer.rueckstand())
        assertEquals("aber nicht mehr unsichtbar", 1, puffer.abgewiesen())
    }

    @Test fun mehrereAbgewieseneWerdenGezaehlt() {
        val a = paketMitRueckstand("r-3")
        val b = paketMitRueckstand("r-4")
        paketMitRueckstand("r-5")

        puffer.alsFehlerhaftMerken(a)
        puffer.alsFehlerhaftMerken(b)

        assertEquals(2, puffer.abgewiesen())
        assertEquals(1, puffer.rueckstand())
    }

    /**
     * Ein abgewiesenes Paket bleibt auch aus der **Warteschlange** heraus.
     * Sonst liefe der Sender in dieselbe 400 wieder und wieder.
     */
    @Test fun einAbgewiesenesPaketWirdNichtMehrGesendet() {
        val id = paketMitRueckstand("r-6")
        assertEquals(1, puffer.warteschlange().size)

        puffer.alsFehlerhaftMerken(id)

        assertEquals(0, puffer.warteschlange().size)
    }

    /**
     * Ein **laufendes** Paket zählt in keiner der beiden Zahlen — es ist
     * weder abgeschlossen noch abgewiesen (Backlog Nr. 11: sonst stünde
     * während des ganzen Dienstes „Rückstand 1").
     */
    @Test fun dasLaufendePaketZaehltNirgends() {
        puffer.paketAnlegen(
            clientRef = "r-7", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-03",
            dienstRef = null, begonnenAt = "2026-09-03T07:02:00Z",
        )
        assertEquals(0, puffer.rueckstand())
        assertEquals(0, puffer.abgewiesen())
    }
}
