package org.genem.nadoku.handy.puffer

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Der Änderungszähler des Puffers.
 *
 * WOFÜR. Die Hauptansicht liest `rueckstand()`, `abgewiesen()` und
 * `verwerfbar()` nicht jede Sekunde über SQLite, sondern nur, wenn
 * [Puffer.aenderungen] sich bewegt hat. Das trägt nur, wenn **jede**
 * Änderung, die eine der drei Zahlen bewegen kann, den Zähler bewegt — und
 * zwar erst, wenn sie geschrieben ist. Beides misst dieser Fall, dazu die
 * eine Ausnahme: ein Punkt im offenen Paket.
 */
@RunWith(RobolectricTestRunner::class)
class AenderungszaehlerTest {

    private lateinit var puffer: Puffer

    @Before fun aufbauen() {
        puffer = Puffer(ApplicationProvider.getApplicationContext<Context>())
    }

    @After fun abbauen() = puffer.close()

    /** **Jeder Schritt, der eine der drei Zahlen ändert, bewegt den Zähler.** */
    @Test fun jedeAenderungDerDreiZahlenBewegtDenZaehler() {
        var gesehen = puffer.aenderungen
        var rueckstand = puffer.rueckstand()
        var abgewiesen = puffer.abgewiesen()
        var verwerfbar = puffer.verwerfbar()
        fun schritt(was: String, tu: () -> Unit) {
            tu()
            val zahlenAnders = puffer.rueckstand() != rueckstand ||
                puffer.abgewiesen() != abgewiesen || puffer.verwerfbar() != verwerfbar
            if (zahlenAnders) {
                assertTrue("$was ändert eine Zahl und muss den Zähler bewegen",
                    puffer.aenderungen > gesehen)
            }
            gesehen = puffer.aenderungen
            rueckstand = puffer.rueckstand(); abgewiesen = puffer.abgewiesen(); verwerfbar = puffer.verwerfbar()
        }

        val id = puffer.paketAnlegen(
            clientRef = "rs-1", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-03",
            dienstRef = null, begonnenAt = "2026-09-03T07:02:00Z",
        )
        schritt("Schließen") { puffer.paketSchliessen(id, "2026-09-03T19:02:00Z", 0, 0) }
        assertEquals(1, puffer.rueckstand())
        schritt("Abweisen") { puffer.alsFehlerhaftMerken(id) }
        assertEquals(1, puffer.abgewiesen())
        schritt("Verwerfen") { puffer.abgewieseneRaeumen(null) }
        assertEquals(0, puffer.verwerfbar())

        val zweites = puffer.paketAnlegen(
            clientRef = "rs-2", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-04",
            dienstRef = null, begonnenAt = "2026-09-04T07:02:00Z",
        )
        schritt("Schließen") { puffer.paketSchliessen(zweites, "2026-09-04T19:02:00Z", 0, 0) }
        schritt("Bestätigen") { puffer.bestaetigungMerken(zweites, 0) }
        schritt("Entsorgen") { puffer.paketEntsorgen(zweites) }
        assertEquals(0, puffer.rueckstand())
    }

    /**
     * **Ein Punkt im offenen Paket zählt nicht** — und das ist richtig, weil
     * er keine der drei Zahlen ändern kann. Zählte er, liefen die Abfragen im
     * Dienst wieder fast jede Sekunde.
     */
    @Test fun einPunktImOffenenPaketBewegtDenZaehlerNicht() {
        val id = puffer.paketAnlegen(
            clientRef = "rs-3", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-05",
            dienstRef = null, begonnenAt = "2026-09-05T07:02:00Z",
        )
        val vorher = puffer.aenderungen
        val zahlen = Triple(puffer.rueckstand(), puffer.abgewiesen(), puffer.verwerfbar())
        repeat(5) { i ->
            puffer.punktAnhaengen(id, Rohpunkt(breite = 47.0 + i / 1000.0, laenge = 11.0,
                hoehe = 700.0, zeit = 1_788_000_000L + i))
        }
        assertEquals("Fünf Punkte, kein Zählerschritt", vorher, puffer.aenderungen)
        assertEquals("…und die drei Zahlen sind gleich", zahlen,
            Triple(puffer.rueckstand(), puffer.abgewiesen(), puffer.verwerfbar()))
    }
}
