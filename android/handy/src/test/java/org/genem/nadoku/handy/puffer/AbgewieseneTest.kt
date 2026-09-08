package org.genem.nadoku.handy.puffer

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
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
 *
 * SEIT ANDROID 0.14.0 AUCH DER RÄUMTEIL (Backlog Nr. 114, Krypto-Review
 * AN-2): Ein abgewiesenes Paket blieb bis dahin **für immer** liegen — samt
 * Spur, über Trennen und Neukopplung hinweg. Die Fälle unten schreiben die
 * Regel fest: nach der Frist und beim Trennen weg, laufende bleiben,
 * beendete `dienst`-Zeilen ohne Pakete gehen mit, die laufende nie.
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

    // ---- Der Räumteil (Backlog Nr. 114, Krypto-Review AN-2) ----------------

    /** Ein abgewiesener Einsatz mit Ende [beendet], samt einem Punkt und einer Phase. */
    private fun abgewiesenesPaket(ref: String, beendet: String, dienstRef: String? = null): Long {
        val id = puffer.paketAnlegen(
            clientRef = ref, art = Paketzeile.ART_EINSATZ, tag = beendet.substring(0, 10),
            dienstRef = dienstRef, begonnenAt = beendet,
        )
        puffer.punktAnhaengen(id, Rohpunkt(47.7261, 10.3186, 712.0, 1_700_000_000L))
        puffer.phaseAnhaengen(id, 2, beendet, null, null, Dienstklammer.QUELLE_HANDY)
        puffer.paketSchliessen(id, beendet, streckeM = 0, anstiegM = 0)
        puffer.alsFehlerhaftMerken(id)
        return id
    }

    private fun dienst(ref: String, begonnen: String, beendet: String? = null) {
        puffer.dienstBeginnen(ref, begonnen.substring(0, 10), begonnen, Modus.MIT_PHASENKNOEPFEN.gespeichert)
        if (beendet != null) puffer.dienstBeenden(ref, beendet)
    }

    private fun dienstRefs(): List<String> =
        puffer.readableDatabase.rawQuery("SELECT dienst_ref FROM dienst ORDER BY id", null)
            .use { c -> buildList { while (c.moveToNext()) add(c.getString(0)) } }

    /**
     * **Nach der Frist wird geräumt, vorher nicht.** Das ältere Paket geht
     * samt Punkt und Phase; das jüngere bleibt — und bleibt sichtbar.
     */
    @Test fun nachDerFristWirdGeraeumtVorherNicht() {
        val alt = abgewiesenesPaket("r-8", "2026-08-01T19:00:00Z")
        val jung = abgewiesenesPaket("r-9", "2026-09-02T19:00:00Z")
        assertEquals(2, puffer.abgewiesen())

        val r = puffer.abgewieseneRaeumen(vor = "2026-08-04T07:02:00Z")

        assertEquals(Raeumung(pakete = 1, dienste = 0), r)
        assertNull("das alte Paket ist weg", puffer.paket(alt))
        assertEquals("samt seiner Punkte", 0L, puffer.punktzahl(alt))
        assertTrue("und seiner Phasen", puffer.phasen(alt).isEmpty())
        assertNotNull("das junge steht noch", puffer.paket(jung))
        assertEquals(1, puffer.abgewiesen())
    }

    /** **Beim Trennen** (ohne Frist) geht alles Abgewiesene — der Rückstand nicht. */
    @Test fun ohneFristGehtAllesAbgewieseneUndNurDas() {
        abgewiesenesPaket("r-10", "2026-09-06T19:00:00Z")
        abgewiesenesPaket("r-11", "2026-09-07T05:00:00Z")
        paketMitRueckstand("r-12")

        val r = puffer.abgewieseneRaeumen(vor = null)

        assertEquals(2, r.pakete)
        assertEquals(0, puffer.abgewiesen())
        assertEquals("der Rückstand gehört nicht dazu", 1, puffer.rueckstand())
    }

    /**
     * Ein **laufendes** abgewiesenes Paket bleibt: Es wird noch beschrieben
     * (Teil-Upload mit 400) und fällt erst unter die Regel, wenn das
     * Dienstende es schließt.
     */
    @Test fun einLaufendesAbgewiesenesPaketBleibt() {
        val id = puffer.paketAnlegen(
            clientRef = "r-13", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-09-03",
            dienstRef = null, begonnenAt = "2026-09-03T07:02:00Z",
        )
        puffer.alsFehlerhaftMerken(id)

        assertEquals(0, puffer.abgewieseneRaeumen(vor = null).pakete)
        assertNotNull(puffer.paket(id))
        assertEquals(1, puffer.abgewiesen())
    }

    /**
     * **`dienst`-Zeilen gehen mit** — die beendeten ohne Pakete. Die laufende
     * bleibt in jedem Fall, und eine beendete, an der noch ein Paket hängt,
     * ebenso.
     */
    @Test fun beendeteDienstzeilenOhnePaketeGehenMit() {
        // 1. beendet, ihr einziges Paket ist abgewiesen -> beides geht
        dienst("ad-alt", "2026-08-01T07:00:00Z", "2026-08-01T19:00:00Z")
        abgewiesenesPaket("r-14", "2026-08-01T19:00:00Z", dienstRef = "ad-alt")
        // 2. beendet, aber ein Paket im Rückstand hängt noch daran -> bleibt
        dienst("ad-offen", "2026-08-02T07:00:00Z", "2026-08-02T19:00:00Z")
        val id = puffer.paketAnlegen(
            clientRef = "r-15", art = Paketzeile.ART_RUHESEGMENT, tag = "2026-08-02",
            dienstRef = "ad-offen", begonnenAt = "2026-08-02T07:00:00Z",
        )
        puffer.paketSchliessen(id, "2026-08-02T19:00:00Z", null, null)
        // 3. beendet und leer (alles bestätigt und entsorgt) -> geht
        dienst("ad-leer", "2026-08-03T07:00:00Z", "2026-08-03T19:00:00Z")
        // 4. läuft -> bleibt
        dienst("ad-laeuft", "2026-09-07T07:00:00Z")

        val r = puffer.abgewieseneRaeumen(vor = null)

        assertEquals(Raeumung(pakete = 1, dienste = 2), r)
        assertEquals(listOf("ad-offen", "ad-laeuft"), dienstRefs())
        assertEquals("ad-laeuft", puffer.laufenderDienst()!!.dienstRef)
    }

    /** Mit Frist bleibt auch eine leere Dienstzeile, deren Ende jünger ist. */
    @Test fun mitFristBleibtEineJungeLeereDienstzeile() {
        dienst("ad-alt", "2026-08-01T07:00:00Z", "2026-08-01T19:00:00Z")
        dienst("ad-jung", "2026-09-06T07:00:00Z", "2026-09-06T19:00:00Z")

        assertEquals(1, puffer.abgewieseneRaeumen(vor = "2026-08-08T00:00:00Z").dienste)
        assertEquals(listOf("ad-jung"), dienstRefs())
    }
}
