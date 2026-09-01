package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.Phasen
import org.genem.nadoku.gemeinsam.Phasenmarke
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Standmeldung
import org.genem.nadoku.gemeinsam.Uhrmeldung
import org.genem.nadoku.uhr.Ansicht
import org.genem.nadoku.uhr.Uhrereignis
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import kotlin.random.Random

/**
 * Bedienung und Funk zusammen (Abnahme C2, E-S4-10).
 *
 * HIER LIEGT DER FÜNFTE ABNAHMEFALL: **Dienststart an der Uhr ohne
 * erreichbares Handy** — schwebend angezeigt, keine Aufzeichnungslücke
 * verschwiegen. Dazu die Naht selbst: dass aus jeder Wirkung genau eine
 * Meldung wird und die `wm-`-Kennung nur dort entsteht, wo ein Einsatz
 * eröffnet wird.
 */
@RunWith(RobolectricTestRunner::class)
class UhrsteuerungTest {

    private val weg = Funkattrappe()
    private val ablage = Merkablage()
    private val funk = Uhrfunk(Uhrpuffer(ablage, Random(7)), weg)
    private var uhrzeit = 1_000L
    private val steuerung = Uhrsteuerung(funk = funk, jetzt = { uhrzeit })

    private fun tipp(): Long { uhrzeit += 1_000; return uhrzeit }
    private fun meldungen(): List<Uhrmeldung> =
        weg.gesendet.map { Nachrichtenformat.liesMeldung(it.second)!! }

    // ---- Dienststart ohne erreichbares Handy --------------------------------

    /**
     * **Der schwebende Dienststart** (E-S4-10).
     *
     * „Ein an der Uhr ausgelöster Dienststart wirkt erst mit Zustellung ans
     * Handy — vorher läuft dort kein GPS." Die Uhr zeigt genau das: Dienst
     * gestartet, aber **nicht bestätigt**. Eine Uhr, die in diesem Augenblick
     * nur „Dienst läuft" zeigte, verschwiege die Aufzeichnungslücke.
     */
    @Test fun einDienststartOhneHandyStehtSchwebend() {
        weg.erreichbar = false
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))

        val z = steuerung.zustand
        assertTrue("An der Uhr läuft er", z.dienstLaeuft)
        assertFalse("Am Handy noch nicht", z.dienstBestaetigt)
        assertTrue("Und die Uhr sagt es", z.dienstSchwebt)
        assertFalse(z.handyErreichbar)
        assertEquals("Die Meldung wartet", 1, z.gepuffert)
    }

    /** Mit der Quittung hört das Schweben auf — und die Aufzeichnung läuft. */
    @Test fun mitDerQuittungIstDerDienstBestaetigt() {
        weg.erreichbar = false
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))

        weg.erreichbar = true
        steuerung.nachliefern()
        assertTrue("Zugestellt heisst noch nicht quittiert", steuerung.zustand.dienstSchwebt)

        steuerung.quittungEingegangen(Quittung(bisNr = 1))
        assertFalse(steuerung.zustand.dienstSchwebt)
        assertEquals("Und der Puffer ist leer", 0, steuerung.zustand.gepuffert)
    }

    /**
     * Auch eine **Standmeldung** beendet das Schweben.
     *
     * Sie ist der zweite Weg zur selben Auskunft: Sagt das Handy „Dienst
     * läuft", dann läuft er dort — unabhängig davon, ob die Quittung
     * unterwegs verlorenging.
     */
    @Test fun eineStandmeldungBeendetDasSchweben() {
        weg.erreichbar = false
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        assertTrue(steuerung.zustand.dienstSchwebt)

        steuerung.standEingegangen(stand(dienstLaeuft = true))
        assertFalse(steuerung.zustand.dienstSchwebt)
    }

    // ---- Aus jeder Wirkung genau eine Meldung -------------------------------

    /** Die `wm-`-Kennung entsteht **nur** beim eröffnenden Ereignis (E-S4-09). */
    @Test fun nurDieEroeffnendePhaseTraegtEineEinsatzkennung() {
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))   // Phase 2: eröffnet
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))   // Phase 3: läuft schon

        val phasen = meldungen().filter { it.art == Ereignisart.PHASE }
        assertEquals(listOf(2, 3), phasen.map { it.phase })
        assertNotNull("Die erste eröffnet", phasen[0].einsatzRef)
        assertTrue(phasen[0].einsatzRef!!.startsWith("wm-"))
        assertNull("Die zweite gehört zum selben Einsatz", phasen[1].einsatzRef)
    }

    /**
     * Nach dem Abschluss eröffnet die nächste Phase **neu** — mit einer
     * anderen Kennung.
     *
     * Dieselbe Kennung wäre am Handy derselbe Einsatz: Zwei Einsätze eines
     * Dienstes verschmölzen zu einem, und die Trennung wäre nicht mehr
     * herstellbar.
     */
    @Test fun nachDemAbschlussBeginntEineNeueKennung() {
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))
        steuerung.ereignis(Uhrereignis.Abschluss(tipp()))
        steuerung.ereignis(Uhrereignis.Bestaetigt(tipp()))
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))

        val kennungen = meldungen().filter { it.art == Ereignisart.PHASE }.mapNotNull { it.einsatzRef }
        assertEquals("Zwei Einsätze, zwei Kennungen", 2, kennungen.size)
        assertEquals(2, kennungen.toSet().size)
    }

    /** Der Zeitstempel ist der **der Uhr** und der des Auslösens (E-R45-1). */
    @Test fun dieMeldungTraegtDieZeitDerUhr() {
        uhrzeit = 1_784_279_400_000L
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        assertEquals(1_784_279_401_000L, meldungen().single().zeitMs)
    }

    /** Jede Bedienung wird genau einmal gemeldet — auch die Korrektur. */
    @Test fun jedeWirkungWirdGenauEinmalGemeldet() {
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))
        repeat(3) { steuerung.ereignis(Uhrereignis.ListenwahL(4, tipp())) }
        steuerung.ereignis(Uhrereignis.Abschluss(tipp()))
        steuerung.ereignis(Uhrereignis.Bestaetigt(tipp()))

        assertEquals(
            listOf(
                Ereignisart.DIENST_BEGINNEN, Ereignisart.PHASE, Ereignisart.PHASE,
                Ereignisart.PHASE, Ereignisart.PHASE, Ereignisart.EINSATZ_ABSCHLIESSEN,
            ),
            meldungen().map { it.art },
        )
        assertEquals("Nummern lückenlos", (1L..6L).toList(), meldungen().map { it.nr })
    }

    /** Ein gesperrtes Tippen meldet nichts — es tut ja nichts (E-S4-21d). */
    @Test fun gesperrtWirdNichtsGemeldet() {
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        weg.leeren()
        uhrzeit += 20_000
        steuerung.ereignis(Uhrereignis.Zeitschlag(uhrzeit))
        assertTrue("Die Sperre ist zu", steuerung.zustand.gesperrt)

        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))
        assertEquals(0, weg.gesendet.size)
    }

    // ---- Der Stand vom Handy führt -----------------------------------------

    /**
     * **Nach einem Neustart der Uhr-App ist ihre Anzeige leer — der Dienst
     * läuft weiter.** Die Standmeldung stellt sie wieder her.
     */
    @Test fun derStandVomHandyFuehrt() {
        steuerung.standEingegangen(
            stand(dienstLaeuft = true).copy(
                einsatzLaeuft = true, laufendePhase = 4, laufendeSeit = "09:12",
                phasen = listOf(Phasenmarke(2, "09:05"), Phasenmarke(4, "09:12")),
            )
        )
        val z = steuerung.zustand
        assertTrue(z.dienstLaeuft)
        assertEquals(4, z.laufendePhase)
        assertEquals(2, z.phasen.size)
        assertEquals("Und die Ansicht folgt", Ansicht.LAUFEND, z.ansicht)
    }

    /** Der Modus kommt vom Handy — ohne Phasenknöpfe gibt es keine (E-S4-20). */
    @Test fun derModusKommtVomHandy() {
        steuerung.standEingegangen(stand(dienstLaeuft = true).copy(modus = Modus.NUR_AUFZEICHNEN))
        assertFalse(steuerung.zustand.mitPhasen)

        weg.leeren()
        steuerung.ereignis(Uhrereignis.GrosserKnopf(tipp()))
        assertEquals("Ohne Phasen meldet der grosse Knopf nichts", 0, weg.gesendet.size)
    }

    /** Eine Standmeldung reisst niemanden aus einer Rückfrage. */
    @Test fun eineStandmeldungUeberschreibtKeineRueckfrage() {
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        steuerung.ereignis(Uhrereignis.Dienstknopf(tipp()))
        assertEquals(Ansicht.DIENSTENDEFRAGE, steuerung.zustand.ansicht)

        steuerung.standEingegangen(stand(dienstLaeuft = true))
        assertEquals(Ansicht.DIENSTENDEFRAGE, steuerung.zustand.ansicht)
    }

    private fun stand(dienstLaeuft: Boolean) = Standmeldung(
        dienstLaeuft = dienstLaeuft, modus = Modus.MIT_PHASENKNOEPFEN, einsatzLaeuft = false,
        laufendePhase = Phasen.FREI, laufendeSeit = null, phasen = emptyList(),
    )
}
