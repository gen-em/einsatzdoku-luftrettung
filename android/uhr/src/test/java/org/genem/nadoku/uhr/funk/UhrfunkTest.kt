package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.Quittung
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import kotlin.random.Random

/**
 * Puffer, Quittung und Nachlieferung der Uhr (Abnahme C2, E-S4-10).
 *
 * DREI DER FÜNF ABNAHMEFÄLLE LIEGEN HIER: Funkabriss mit Nachlieferung,
 * Doppelzustellung nach verlorener Quittung, Uhr-Neustart mit gefülltem
 * Puffer. Die beiden anderen — kein zweiter Einsatz am Handy, Phasenkonflikt
 * — liegen auf der Gegenseite (`UhrannahmeTest` im Handy-Modul), weil dort
 * entschieden wird.
 *
 * Robolectric wegen `org.json`; sonst steckt hier kein Android.
 */
@RunWith(RobolectricTestRunner::class)
class UhrfunkTest {

    private val weg = Funkattrappe()
    private val ablage = Merkablage()
    private fun funk(a: Merkablage = ablage) = Uhrfunk(Uhrpuffer(a, Random(7)), weg)

    // ---- Funkabriss mit Nachlieferung ---------------------------------------

    /**
     * **Funkabriss mit Nachlieferung** — und zwar *identisch*.
     *
     * Verglichen werden die Bytes und nicht die Absicht: Eine zweite,
     * „gleichwertige" Nachricht mit neuer Nummer wäre am Handy ein zweites
     * Ereignis.
     */
    @Test fun einFunkabrissKostetNichts() {
        val f = funk()
        weg.erreichbar = false

        f.melde(Ereignisart.DIENST_BEGINNEN, 1_000)
        f.melde(Ereignisart.PHASE, 2_000, phase = 2, einsatzRef = f.einsatzkennung())

        assertFalse("Die Uhr weiss, dass nichts angekommen ist", f.handyErreichbar)
        assertEquals("Beides wartet", 2, f.offen())
        assertEquals("Auf der Leitung war nichts", 0, weg.gesendet.size)

        weg.erreichbar = true
        assertEquals("Beide gehen nach", 2, f.nachliefern())
        assertTrue(f.handyErreichbar)
        assertEquals("Und beide warten weiter — bis zur Quittung", 2, f.offen())

        val nummern = weg.texte().map { Nachrichtenformat.liesMeldung(it.toByteArray())!!.nr }
        assertEquals("In der Reihenfolge des Entstehens", listOf(1L, 2L), nummern)
    }

    @Test fun nachgeliefertWirdWortgleich() {
        val f = funk()
        f.melde(Ereignisart.PHASE, 2_000, phase = 3)
        val zuerst = weg.texte().single()

        weg.leeren()
        f.nachliefern()

        assertEquals("Dieselben Bytes, nicht eine neue Nachricht", zuerst, weg.texte().single())
    }

    /** Bricht der Funk mittendrin ab, bleibt der Rest liegen — in Reihenfolge. */
    @Test fun einAbrissMittendrinLiefertNichtsUeberSeinenVorgaengerHinaus() {
        val f = funk()
        weg.erreichbar = false
        f.melde(Ereignisart.DIENST_BEGINNEN, 1_000)
        f.melde(Ereignisart.PHASE, 2_000, phase = 2)
        f.melde(Ereignisart.PHASE, 3_000, phase = 3)

        /* Die Attrappe stellt genau eine Nachricht zu und macht dann zu. */
        weg.erreichbar = true
        val einmal = object : org.genem.nadoku.gemeinsam.Nachrichtenweg {
            var uebrig = 1
            override fun sende(pfad: String, rumpf: ByteArray): Boolean =
                if (uebrig-- > 0) weg.sende(pfad, rumpf) else false
        }
        val zaehe = Uhrfunk(Uhrpuffer(ablage, Random(7)), einmal)

        assertEquals(1, zaehe.nachliefern())
        assertFalse(zaehe.handyErreichbar)
        assertEquals("Nichts wurde verworfen", 3, zaehe.offen())
        assertEquals(
            "Und der Dienststart war der erste, nicht irgendeiner",
            Ereignisart.DIENST_BEGINNEN,
            Nachrichtenformat.liesMeldung(weg.gesendet.first().second)!!.art,
        )
    }

    // ---- Doppelzustellung nach verlorener Quittung ---------------------------

    /**
     * **Die verlorene Quittung.**
     *
     * Die Uhr kann nicht unterscheiden, ob das Ereignis nicht ankam oder ob
     * nur die Quittung ausblieb. Sie liefert nach — und erzeugt damit
     * absichtlich eine Doppelzustellung. Dass daraus am Handy kein zweiter
     * Einsatz wird, ist Sache der Gegenseite (`UhrannahmeTest`).
     */
    @Test fun ohneQuittungWirdNachgeliefert() {
        val f = funk()
        f.melde(Ereignisart.PHASE, 2_000, phase = 2, einsatzRef = f.einsatzkennung())
        assertEquals(1, f.offen())

        weg.leeren()
        f.nachliefern()
        assertEquals("Dieselbe Nachricht ein zweites Mal", 1, weg.gesendet.size)
        assertEquals("Und sie trägt dieselbe Nummer", 1L,
            Nachrichtenformat.liesMeldung(weg.gesendet.single().second)!!.nr)
    }

    @Test fun eineQuittungRaeumtDenPufferBisDorthin() {
        val f = funk()
        repeat(4) { f.melde(Ereignisart.PHASE, 2_000L + it, phase = 2 + it) }
        assertEquals(4, f.offen())

        assertEquals("Drei verschwinden", 3, f.quittung(Quittung(bisNr = 3)))
        assertEquals("Die vierte wartet weiter", 1, f.offen())
        assertTrue(f.bestaetigt(3))
        assertFalse(f.bestaetigt(4))

        weg.leeren()
        f.nachliefern()
        assertEquals("Nachgeliefert wird nur noch die vierte", listOf(4L),
            weg.texte().map { Nachrichtenformat.liesMeldung(it.toByteArray())!!.nr })
    }

    /** Eine zweite, gleiche Quittung ändert nichts — sie kommt vor. */
    @Test fun eineDoppelteQuittungIstFolgenlos() {
        val f = funk()
        repeat(2) { f.melde(Ereignisart.PHASE, 2_000L + it, phase = 2 + it) }
        assertEquals(2, f.quittung(Quittung(2)))
        assertEquals(0, f.quittung(Quittung(2)))
        assertEquals(0, f.offen())
    }

    // ---- Uhr-Neustart mit gefülltem Puffer -----------------------------------

    /**
     * **Uhr-Neustart mit gefülltem Puffer.**
     *
     * Der neue Funk liest dieselbe Ablage. Geprüft wird dreierlei: Die
     * Nachrichten sind noch da, sie sind **wortgleich**, und der Zähler läuft
     * weiter — eine wiederverwendete Nummer wäre am Handy ein Ereignis, das
     * stillschweigend als Doppelzustellung verschwindet.
     */
    @Test fun einNeustartVerliertNichts() {
        val vorher = funk()
        weg.erreichbar = false
        vorher.melde(Ereignisart.DIENST_BEGINNEN, 1_000)
        vorher.melde(Ereignisart.PHASE, 2_000, phase = 2, einsatzRef = vorher.einsatzkennung())
        val uhrIdVorher = Nachrichtenformat.liesMeldung(
            Uhrpuffer(ablage, Random(7)).wartende().first()
        )!!.uhrId

        // „Neustart": neues Exemplar über derselben Ablage.
        val nachher = funk()
        assertEquals("Der Puffer ist noch voll", 2, nachher.offen())

        weg.erreichbar = true
        nachher.nachliefern()
        val meldungen = weg.gesendet.map { Nachrichtenformat.liesMeldung(it.second)!! }
        assertEquals(listOf(1L, 2L), meldungen.map { it.nr })
        assertEquals("Dieselbe Uhr", listOf(uhrIdVorher, uhrIdVorher), meldungen.map { it.uhrId })

        val naechste = nachher.melde(Ereignisart.PHASE, 3_000, phase = 3)
        assertEquals("Der Zähler läuft weiter, er beginnt nicht von vorn", 3L, naechste.nr)
    }

    /** Eine andere Uhr ist eine andere Uhr — die Kennung unterscheidet sie. */
    @Test fun zweiUhrenTragenVerschiedeneKennungen() {
        val eine = funk(Merkablage()).melde(Ereignisart.DIENST_BEGINNEN, 1_000)
        val andere = Uhrfunk(Uhrpuffer(Merkablage(), Random(99)), weg)
            .melde(Ereignisart.DIENST_BEGINNEN, 1_000)

        assertEquals("Beide fangen bei 1 an", andere.nr, eine.nr)
        assertNotEquals("Aber sie sind unterscheidbar", andere.uhrId, eine.uhrId)
    }

    /** Die `wm-`-Kennung hat die Bauform aus E-S4-09 — Präfix, Zähler, Zufall. */
    @Test fun dieEinsatzkennungTraegtDasUhrPraefix() {
        val f = funk()
        val eine = f.einsatzkennung()
        val zweite = f.einsatzkennung()

        assertTrue(eine, eine.startsWith("wm-"))
        assertTrue("Der Zähler läuft weiter", zweite.startsWith("wm-2-"))
        assertNotEquals(eine, zweite)
        assertTrue("Höchstens 64 Zeichen (Vertrag 3.2)", eine.length <= 64)
    }
}
