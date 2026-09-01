package org.genem.nadoku.handy.dienst

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.gemeinsam.Phasen
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.aufzeichnung.Stroeme
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.time.Instant
import kotlin.random.Random

/**
 * Die **Lebenszyklus-Matrix** (Abnahme B5, E-S4-08).
 *
 * Der Lebenszyklus ist der von `Model.mc` — und er ist die eine Stelle, an der
 * ein Fehler nicht auffällt, sondern still einen Einsatz kostet.
 */
@RunWith(RobolectricTestRunner::class)
class LebenszyklusTest {

    private lateinit var kontext: Context
    private lateinit var puffer: Puffer
    private var uhrzeit: Instant = Instant.parse("2026-07-16T05:00:00Z")

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        override fun lies() = wert
        override fun schreib(wert: Long) { this.wert = wert }
    }

    private val zaehler = Merkzaehler()
    private val ausduenner = Ausduenner()

    private fun klammer() = Dienstklammer(
        puffer = puffer,
        kennungen = Kennungen(zaehler, Random(4)),
        ausduenner = ausduenner,
        jetzt = { uhrzeit },
    )

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATENBANK)
        puffer = Puffer(kontext, DATENBANK)
    }

    @After fun abbauen() {
        puffer.close()
        kontext.deleteDatabase(DATENBANK)
    }

    private fun dienstMitSpur(): Dienstklammer {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        // Eine Spur, aus der die Phasen-Koordinate kommen kann.
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) {
            k.positionsfund(p.copy(zeit = Zeit.epoche(uhrzeit) - 600 + (p.zeit - Stroeme.START_ZEIT)))
        }
        return k
    }

    // ---- Phase startet den Einsatz -----------------------------------------

    /** **Eine Phase 2–9 ohne laufenden Einsatz startet den Einsatz.** */
    @Test fun phaseOhneEinsatzStartetDenEinsatz() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertFalse(k.einsatzLaeuft())

        assertTrue(k.phaseSetzen(2))

        assertTrue("Der Einsatz muss laufen", k.einsatzLaeuft())
        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!
        assertTrue("Kennung mit Handy-Präfix", einsatz.clientRef.startsWith("am-"))
        assertEquals(2, k.laufendePhase())
        assertEquals(1, puffer.phasen(einsatz.id).size)
    }

    /** Und schließt dabei das Ruhesegment — **im selben Augenblick**. */
    @Test fun dasRuhesegmentSchliesstNahtlos() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id

        uhrzeit = Instant.parse("2026-07-16T08:31:05Z")
        k.phaseSetzen(2)

        val segment = puffer.paket(segmentId)!!
        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!
        assertTrue("Das Segment ist abgeschlossen", segment.final)
        assertEquals(
            "Segmentende und Einsatzbeginn sind derselbe Augenblick — kein Loch, kein Überlappen",
            segment.beendetAt, einsatz.begonnenAt,
        )
        assertEquals("2026-07-16T08:31:05Z", einsatz.begonnenAt)
        assertNull("Es darf kein zweites Segment offen sein",
            puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
    }

    @Test fun einZweiterPhasendruckStartetKeinenZweitenEinsatz() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.phaseSetzen(2)
        val ersteId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id
        k.phaseSetzen(3)
        assertEquals(ersteId, puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id)
    }

    @Test fun phasenAusserhalbZweiBisNeunWerdenAbgewiesen() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        for (n in listOf(0, 1, 10, -3, 99)) {
            assertFalse("Phase $n gibt es nicht (Vertrag 7)", k.phaseSetzen(n))
        }
        assertFalse("Und kein Einsatz darf entstanden sein", k.einsatzLaeuft())
    }

    @Test fun ohneDienstGibtEsKeinePhase() {
        assertFalse(klammer().phaseSetzen(2))
    }

    // ---- Der Durchlauf ------------------------------------------------------

    @Test fun derDurchlaufGehtVonZweiBisNeun() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertEquals("Ohne Einsatz ist die nächste Phase 2", 2, k.naechstePhase())

        for (n in Phasen.ERSTE..Phasen.LETZTE) {
            assertEquals(n, k.naechstePhase())
            assertTrue(k.phaseSetzen(n))
            assertEquals(n, k.laufendePhase())
        }
        assertNull("Nach Phase 9 gibt es keine nächste — dort steht der Abschluss",
            k.naechstePhase())
    }

    // ---- Mehrfache Einträge (E-R45-12) --------------------------------------

    /**
     * **Eine erneut gesetzte Phase ist ein zweiter Eintrag** und keine
     * Korrektur am ersten (Vertrag 3, E-R45-12).
     */
    @Test fun doppeltePhaseneintraegeBleibenErhalten() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)

        uhrzeit = Instant.parse("2026-07-16T08:31:05Z")
        k.phaseSetzen(4)
        uhrzeit = Instant.parse("2026-07-16T08:39:20Z")
        k.phaseSetzen(4)

        val phasen = puffer.phasen(puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id)
        assertEquals("Beide Einträge müssen stehen", 2, phasen.size)
        assertEquals(listOf(4, 4), phasen.map { it.nummer })
        assertEquals(
            listOf("2026-07-16T08:31:05Z", "2026-07-16T08:39:20Z"),
            phasen.map { it.at },
        )
    }

    // ---- Phasen-Koordinate --------------------------------------------------

    /** Die Koordinate kommt aus der **eigenen Spur** (E-S4-10). */
    @Test fun diePhasenKoordinateKommtAusDerEigenenSpur() {
        val k = dienstMitSpur()
        k.phaseSetzen(2)

        val phase = puffer.phasen(puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id).single()
        assertNotNull("Es liegt ein Punkt in Reichweite", phase.breite)
        assertNotNull(phase.laenge)
    }

    /**
     * Ohne Punkt in Reichweite bleibt sie **null** — der Vertrag erlaubt das
     * ausdrücklich. Eine erfundene Koordinate wäre schlimmer als keine.
     */
    @Test fun ohnePunktInReichweiteBleibtDieKoordinateNull() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.positionsfund(Rohpunkt(47.7261, 10.3186, 712.0, Zeit.epoche(uhrzeit)))

        // Eine Stunde später: der Punkt ist weit außerhalb der 30 s.
        uhrzeit = uhrzeit.plusSeconds(3600)
        k.phaseSetzen(2)

        val phase = puffer.phasen(puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id).single()
        assertNull(phase.breite)
        assertNull(phase.laenge)
    }

    /**
     * Der Punkt darf aus dem **Ruhesegment** stammen: Die Phase, die den
     * Einsatz startet, fällt in einen Augenblick, in dem der letzte Punkt noch
     * dort lag.
     */
    @Test fun dieKoordinateDarfAusDemRuhesegmentStammen() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.positionsfund(Rohpunkt(47.7261, 10.3186, 712.0, Zeit.epoche(uhrzeit)))
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        assertEquals(1L, puffer.punktzahl(segmentId))

        uhrzeit = uhrzeit.plusSeconds(5)
        k.phaseSetzen(2)

        val phase = puffer.phasen(puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id).single()
        assertEquals(47.7261, phase.breite!!, 1e-9)
    }

    // ---- Abschluss ----------------------------------------------------------

    /** `ended_at` ist die Zeit der **letzten Phase 9**. */
    @Test fun derAbschlussNimmtDieZeitDerLetztenPhaseNeun() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.phaseSetzen(2)
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id

        uhrzeit = Instant.parse("2026-07-16T09:12:40Z")
        k.phaseSetzen(9)
        // Fünf Minuten später findet jemand den Abschlussknopf.
        uhrzeit = Instant.parse("2026-07-16T09:17:00Z")
        assertTrue(k.einsatzAbschliessen())

        val einsatz = puffer.paket(einsatzId)!!
        assertTrue(einsatz.final)
        assertEquals(
            "Der Einsatz endete um 9:12, nicht um 9:17",
            "2026-07-16T09:12:40Z", einsatz.beendetAt,
        )
    }

    @Test fun ohnePhaseNeunNimmtDerAbschlussDenAugenblick() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.phaseSetzen(2)
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id

        uhrzeit = Instant.parse("2026-07-16T09:17:00Z")
        k.einsatzAbschliessen()

        assertEquals("2026-07-16T09:17:00Z", puffer.paket(einsatzId)!!.beendetAt)
    }

    /** Nach dem Abschluss beginnt **nahtlos** das nächste Ruhesegment. */
    @Test fun nachDemAbschlussBeginntDasNaechsteRuhesegment() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        val erstesSegment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        k.phaseSetzen(2)
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id

        uhrzeit = Instant.parse("2026-07-16T09:17:00Z")
        k.einsatzAbschliessen()

        val zweitesSegment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)
        assertNotNull("Es muss ein neues Segment offen sein", zweitesSegment)
        assertTrue("Und es ist ein anderes", zweitesSegment!!.id != erstesSegment)
        assertEquals(
            "Einsatzende und Segmentbeginn sind derselbe Augenblick",
            puffer.paket(einsatzId)!!.beendetAt, zweitesSegment.begonnenAt,
        )
        assertNull("Und kein Einsatz mehr offen", puffer.offenesPaket(Paketzeile.ART_EINSATZ))
    }

    /** Strecke und Anstieg werden beim Abschluss **eingefroren**. */
    @Test fun kennzahlenWerdenEingefroren() {
        val k = dienstMitSpur()
        k.phaseSetzen(2)
        for (p in Stroeme.erzeuge(Stroeme.REISEFLUG)) {
            k.positionsfund(p.copy(zeit = Zeit.epoche(uhrzeit) + (p.zeit - Stroeme.START_ZEIT)))
        }
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id
        val streckeVorher = k.streckeM()

        uhrzeit = uhrzeit.plusSeconds(3600)
        k.einsatzAbschliessen()

        val einsatz = puffer.paket(einsatzId)!!
        assertEquals(streckeVorher.toInt(), einsatz.streckeM)
        assertTrue("Es muss eine Strecke gewesen sein", einsatz.streckeM!! > 0)
        assertEquals("Für das neue Segment beginnen sie neu", 0.0, k.streckeM(), 0.001)
    }

    @Test fun abschlussOhneEinsatzTutNichts() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertFalse(k.einsatzAbschliessen())
    }

    /** Dienstende schließt auch einen vergessenen Einsatz (Sicherheitsnetz). */
    @Test fun dasDienstendeSchliesstEinenOffenenEinsatzMit() {
        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.phaseSetzen(2)
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id

        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        assertTrue(k.beenden())

        val einsatz = puffer.paket(einsatzId)!!
        assertTrue("Der Einsatz muss abgeschlossen sein", einsatz.final)
        assertEquals("2026-07-16T17:00:00Z", einsatz.beendetAt)
        assertNull(puffer.offenesPaket(Paketzeile.ART_EINSATZ))
        assertNull(puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
    }

    // ---- Nur-Aufzeichnen ----------------------------------------------------

    /**
     * Im Nur-Aufzeichnen-Modus entsteht **keine** `mission` — auch nicht
     * dadurch, dass jemand von außen eine Phase setzt. Der Modus blendet die
     * Knöpfe aus; er ist kein Sonderweg in der Klammer (E-S4-20).
     */
    @Test fun einNurAufzeichnenDienstErzeugtKeineMission() {
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.STADTFAHRT)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        k.beenden()

        assertEquals(0, zaehleArt(Paketzeile.ART_EINSATZ))
        assertEquals(1, zaehleArt(Paketzeile.ART_RUHESEGMENT))
    }

    /**
     * **Der Moduswechsel mitten im Dienst ändert nichts am bereits
     * Aufgezeichneten** (E-S4-20) — und ein Umstieg auf „mit Knöpfen"
     * schließt das laufende Segment erst, wenn tatsächlich eine Phase gesetzt
     * wird.
     */
    @Test fun derModuswechselSchliesstNichtsVonSelbst() {
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) k.positionsfund(p)
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        val punkteVorher = puffer.punktzahl(segmentId)

        k.modusWechseln(Modus.MIT_PHASENKNOEPFEN)

        assertFalse("Das Segment bleibt offen", puffer.paket(segmentId)!!.final)
        assertEquals(punkteVorher, puffer.punktzahl(segmentId))
        assertEquals(0, zaehleArt(Paketzeile.ART_EINSATZ))

        // Erst die Phase schließt es.
        k.phaseSetzen(2)
        assertTrue(puffer.paket(segmentId)!!.final)
        assertEquals(1, zaehleArt(Paketzeile.ART_EINSATZ))
    }

    private fun zaehleArt(art: String): Int =
        puffer.readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket WHERE art = ?", arrayOf(art),
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    private companion object {
        const val DATENBANK = "lebenszyklus.db"
    }
}
