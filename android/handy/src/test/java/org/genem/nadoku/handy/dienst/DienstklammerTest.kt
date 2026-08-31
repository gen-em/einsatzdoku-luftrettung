package org.genem.nadoku.handy.dienst

import android.content.Context
import androidx.test.core.app.ApplicationProvider
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
 * Die Dienstklammer samt **Wiederaufnahme** (E-S4-08, Abnahme B3).
 *
 * Robolectric, weil hier echtes SQLite läuft — der Puffer ist der Ort, an dem
 * die Wiederaufnahme entsteht, und eine Attrappe prüfte genau ihn nicht.
 */
@RunWith(RobolectricTestRunner::class)
class DienstklammerTest {

    private lateinit var kontext: Context
    private lateinit var puffer: Puffer
    private var uhrzeit: Instant = Instant.parse("2026-07-16T05:00:00Z")

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        override fun lies() = wert
        override fun schreib(wert: Long) { this.wert = wert }
    }

    private val zaehler = Merkzaehler()

    private fun neueKlammer(ausduenner: Ausduenner = Ausduenner()) = Dienstklammer(
        puffer = puffer,
        kennungen = Kennungen(zaehler, Random(4)),
        ausduenner = ausduenner,
        jetzt = { uhrzeit },
    )

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(PRUEFDATENBANK)
        puffer = Puffer(kontext, PRUEFDATENBANK)
    }

    @After fun abbauen() {
        puffer.close()
        kontext.deleteDatabase(PRUEFDATENBANK)
    }

    // ---- Die Klammer -------------------------------------------------------

    @Test fun dienstBeginnenLegtKennungTagUndErstesRuhesegmentAn() {
        val klammer = neueKlammer()
        val beginn = klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)

        assertTrue("Der erste Dienst ist neu", beginn.neu)
        assertTrue(beginn.dienst.dienstRef.startsWith("ad-"))
        assertEquals("2026-07-16", beginn.dienst.tag)
        assertEquals("2026-07-16T05:00:00Z", beginn.dienst.begonnenAt)

        val segment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)
        assertNotNull("Mit dem Dienst öffnet ein Ruhesegment", segment)
        assertTrue(segment!!.clientRef.startsWith("ar-"))
        assertEquals(beginn.dienst.dienstRef, segment.dienstRef)
        assertFalse(segment.final)
    }

    /** E-R45-13: Ein zweiter Dienststart ist kein neuer Dienst. */
    @Test fun einZweiterDienststartIstKeinNeuerDienst() {
        val klammer = neueKlammer()
        val erster = klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)

        uhrzeit = Instant.parse("2026-07-16T09:00:00Z")
        val zweiter = klammer.beginnen(Modus.NUR_AUFZEICHNEN)

        assertFalse("Der zweite Start ist nur die Anzeige des laufenden Dienstes", zweiter.neu)
        assertEquals(erster.dienst.dienstRef, zweiter.dienst.dienstRef)
        assertEquals(
            "Auch der Modus wird dabei nicht umgeworfen",
            Modus.MIT_PHASENKNOEPFEN, klammer.modus(),
        )
    }

    @Test fun dienstBeendenSchliesstDasRuhesegment() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id

        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        assertTrue(klammer.beenden())

        assertNull("Kein laufender Dienst mehr", klammer.laufenderDienst())
        assertNull("Kein offenes Segment mehr", puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
        val segment = puffer.paket(segmentId)!!
        assertTrue(segment.final)
        assertEquals("2026-07-16T17:00:00Z", segment.beendetAt)
    }

    @Test fun beendenOhneDienstTutNichts() {
        assertFalse(neueKlammer().beenden())
    }

    // ---- Moduswahl ---------------------------------------------------------

    @Test fun derModusWirdMitDemDienstGemerkt() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.NUR_AUFZEICHNEN)
        assertEquals(Modus.NUR_AUFZEICHNEN, klammer.modus())
    }

    /** E-S4-20: Der Wechsel während des Dienstes ist verlustfrei. */
    @Test fun derModuswechselAendertNichtsAmBereitsAufgezeichneten() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.NUR_AUFZEICHNEN)
        val segment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!

        abspielen(klammer, Stroeme.ANFAHRT_BODEN)
        val vorher = puffer.punktzahl(segment.id)
        val refVorher = puffer.paket(segment.id)!!.clientRef

        assertTrue(klammer.modusWechseln(Modus.MIT_PHASENKNOEPFEN))

        assertEquals(Modus.MIT_PHASENKNOEPFEN, klammer.modus())
        assertEquals("Kein Punkt darf verlorengehen", vorher, puffer.punktzahl(segment.id))
        assertEquals("Das Segment bleibt dasselbe", refVorher, puffer.paket(segment.id)!!.clientRef)
        assertFalse("Und es bleibt offen", puffer.paket(segment.id)!!.final)
        assertEquals(
            "Es entsteht kein zweites Segment",
            segment.id, puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id,
        )
    }

    // ---- Aufzeichnung ------------------------------------------------------

    @Test fun ohneDienstWirdNichtsAufgezeichnet() {
        val klammer = neueKlammer()
        assertFalse(klammer.positionsfund(Rohpunkt(47.7261, 10.3186, 712.0, 1000)))
    }

    @Test fun punkteLandenImOffenenRuhesegment() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.NUR_AUFZEICHNEN)
        val segment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!

        val behalten = abspielen(klammer, Stroeme.ANFAHRT_BODEN)

        assertEquals(301, behalten)
        assertEquals(301L, puffer.punktzahl(segment.id))
    }

    /** Die Sequenznummern müssen lückenlos bei 0 beginnen (Vertrag 2). */
    @Test fun sequenznummernSindLueckenlos() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.NUR_AUFZEICHNEN)
        val segment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!
        abspielen(klammer, Stroeme.STADTFAHRT)

        val alle = puffer.punkte(segment.id, 0, 100_000)
        assertEquals(331, alle.size)
        // Aufsteigend in der Zeit und ohne Lücke — die Reihenfolge IST die seq.
        for (i in 1 until alle.size) {
            assertTrue("Zeit läuft rückwärts bei $i", alle[i].zeit > alle[i - 1].zeit)
        }
        assertEquals(0, puffer.punkte(segment.id, 331, 10).size)
    }

    // ---- Wiederaufnahme ----------------------------------------------------

    /**
     * **Absturz der App.** Ein neues Exemplar von [Dienstklammer] auf
     * demselben Puffer findet den Dienst vor und zeichnet weiter auf.
     */
    @Test fun wiederaufnahmeNachAbsturzDerApp() {
        val erste = neueKlammer()
        val beginn = erste.beginnen(Modus.NUR_AUFZEICHNEN)
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        abspielen(erste, Stroeme.ANFAHRT_BODEN)
        val vorher = puffer.punktzahl(segmentId)

        // Die App stürzt ab: alles im Arbeitsspeicher ist fort.
        val zweite = neueKlammer()

        val gefunden = zweite.laufenderDienst()
        assertNotNull("Der Dienst muss wiedergefunden werden", gefunden)
        assertEquals(beginn.dienst.dienstRef, gefunden!!.dienstRef)
        assertEquals(Modus.NUR_AUFZEICHNEN, zweite.modus())

        abspielen(zweite, Stroeme.ANFAHRT_BODEN)
        assertTrue(
            "Es muss weiter in dasselbe Segment geschrieben werden",
            puffer.punktzahl(segmentId) > vorher,
        )
        assertEquals(
            "Und es darf kein zweites Segment entstehen",
            segmentId, puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id,
        )
    }

    /**
     * **Neustart des Handys.** Auch die Datenbankverbindung ist fort — der
     * Puffer wird von der Platte neu geöffnet.
     */
    @Test fun wiederaufnahmeNachNeustartDesHandys() {
        val erste = neueKlammer()
        val beginn = erste.beginnen(Modus.MIT_PHASENKNOEPFEN)
        abspielen(erste, Stroeme.STADTFAHRT)
        val punkteVorher = puffer.punktzahl(puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id)

        // Neustart: Puffer schließen und aus derselben Datei neu öffnen.
        puffer.close()
        puffer = Puffer(kontext, PRUEFDATENBANK)

        val nachher = neueKlammer()
        val gefunden = nachher.laufenderDienst()
        assertNotNull(gefunden)
        assertEquals(beginn.dienst.dienstRef, gefunden!!.dienstRef)
        assertEquals(
            "Kein Punkt darf den Neustart verlieren",
            punkteVorher,
            puffer.punktzahl(puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id),
        )
        assertTrue("Und der Dienst lässt sich beenden", nachher.beenden())
    }

    /**
     * Nach dem Neustart fängt die Ausdünnung von vorn an — der erste Punkt
     * danach wird genommen. Das ist richtig: Wo das Handy war, während es aus
     * war, weiß niemand; ein Vergleich gegen einen Punkt von vor dem Neustart
     * wäre eine Behauptung.
     */
    @Test fun nachDemNeustartWirdDerErstePunktGenommen() {
        val erste = neueKlammer()
        erste.beginnen(Modus.NUR_AUFZEICHNEN)
        val segmentId = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        erste.positionsfund(Rohpunkt(47.7261, 10.3186, 712.0, 1000))
        assertEquals(1L, puffer.punktzahl(segmentId))

        val nachher = neueKlammer()      // frischer Ausdünner
        assertTrue(nachher.positionsfund(Rohpunkt(47.7261, 10.3186, 712.0, 1001)))
        assertEquals(2L, puffer.punktzahl(segmentId))
    }

    // ---- Ein ganzer Dienst -------------------------------------------------

    @Test fun einNurAufzeichnenDienstErgibtGenauEineSegmentkette() {
        val klammer = neueKlammer()
        klammer.beginnen(Modus.NUR_AUFZEICHNEN)
        abspielen(klammer, Stroeme.DIENST_12H)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        klammer.beenden()

        // Genau ein Ruhesegment, kein einziger Einsatz (E-S4-20).
        assertNull(puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
        assertNull(puffer.offenesPaket(Paketzeile.ART_EINSATZ))
        assertEquals(1, zaehleArt(Paketzeile.ART_RUHESEGMENT))
        assertEquals(0, zaehleArt(Paketzeile.ART_EINSATZ))
    }

    private fun zaehleArt(art: String): Int =
        puffer.readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket WHERE art = ?", arrayOf(art),
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    private fun abspielen(klammer: Dienstklammer, abschnitte: List<Stroeme.Abschnitt>): Int {
        var behalten = 0
        for (p in Stroeme.erzeuge(abschnitte)) if (klammer.positionsfund(p)) behalten++
        return behalten
    }

    private companion object {
        const val PRUEFDATENBANK = "pruefpuffer.db"
    }
}
