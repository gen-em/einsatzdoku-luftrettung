package org.genem.nadoku.handy.aufzeichnung

import org.junit.Assert.assertEquals
import org.junit.Test

/**
 * Der Akkuwächter (Backlog Nr. 82) — **ohne Android und ohne Warten**.
 *
 * Er bekommt jede Messung übergeben, wie [Ortungswaechter] seine Zeit. Was
 * hier geprüft wird, ist die Zusicherung, an der der ganze Punkt hängt:
 * **eine Warnung je erreichter Stufe, nicht je Messung.**
 */
class AkkuwaechterTest {

    private fun w() = Akkuwaechter()

    // ---- Die drei Schwellen ----------------------------------------------

    @Test fun ueberFuenfundzwanzigProzentPassiertNichts() {
        val a = w()
        assertEquals(Akkubefehl.NICHTS, a.messen(100, laedt = false))
        assertEquals(Akkubefehl.NICHTS, a.messen(40, laedt = false))
        assertEquals(Akkubefehl.NICHTS, a.messen(26, laedt = false))
        assertEquals(Akkustufe.OK, a.stand)
    }

    @Test fun beiFuenfundzwanzigWirdGewarnt() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
        assertEquals(Akkustufe.KNAPP, a.stand)
    }

    @Test fun bei15Und10WirdErneutGewarnt() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
        assertEquals(Akkubefehl.POSTEN, a.messen(15, laedt = false))
        assertEquals(Akkustufe.NIEDRIG, a.stand)
        assertEquals(Akkubefehl.POSTEN, a.messen(10, laedt = false))
        assertEquals(Akkustufe.KRITISCH, a.stand)
    }

    /**
     * DIE ZUSICHERUNG DES GANZEN PUNKTS: Zwischen zwei Stufen wird **nicht**
     * gewarnt, auch wenn hundertmal gemessen wird. Sonst stünde bei 24 %
     * zwölf Stunden lang dieselbe Meldung, und die bei 10 % ginge darin unter.
     */
    @Test fun innerhalbEinerStufeWirdNurEinmalGewarnt() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
        for (p in 24 downTo 16) {
            assertEquals("bei $p %", Akkubefehl.NICHTS, a.messen(p, laedt = false))
        }
        // Erst die nächste Stufe warnt wieder.
        assertEquals(Akkubefehl.POSTEN, a.messen(15, laedt = false))
    }

    /** Ein Sprung überspringt keine Warnung — er löst die tiefste aus. */
    @Test fun einSprungWarntMitDerTiefstenStufe() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(8, laedt = false))
        assertEquals(Akkustufe.KRITISCH, a.stand)
        // Danach kommt nichts mehr — tiefer geht es nicht.
        assertEquals(Akkubefehl.NICHTS, a.messen(3, laedt = false))
        assertEquals(Akkubefehl.NICHTS, a.messen(0, laedt = false))
    }

    // ---- Das Angebot zum Dienstende --------------------------------------

    @Test fun erstAb15GibtEsDasAngebotZumDienstende() {
        assertEquals(false, Akkustufe.OK.bietetDienstende)
        assertEquals(false, Akkustufe.KNAPP.bietetDienstende)
        assertEquals(true, Akkustufe.NIEDRIG.bietetDienstende)
        assertEquals(true, Akkustufe.KRITISCH.bietetDienstende)
    }

    // ---- Am Kabel ---------------------------------------------------------

    @Test fun amKabelWirdDieWarnungZurueckgezogen() {
        val a = w()
        a.messen(15, laedt = false)
        assertEquals(Akkubefehl.LOESCHEN, a.messen(15, laedt = true))
    }

    @Test fun amKabelOhneVorherigeWarnungPassiertNichts() {
        assertEquals(Akkubefehl.NICHTS, w().messen(50, laedt = true))
    }

    /**
     * NACH DEM LADEN WARNT DIESELBE SCHWELLE ERNEUT. Das ist gewollt: Ein
     * zweites Absacken ist ein zweites Ereignis. Ohne das Zurücksetzen bliebe
     * der zweite Abstieg stumm — und der ist der gefährlichere, weil das
     * Kabel dann nicht mehr da ist.
     */
    @Test fun nachDemLadenWarntDieselbeSchwelleWieder() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(15, laedt = false))
        assertEquals(Akkubefehl.LOESCHEN, a.messen(60, laedt = true))
        assertEquals(Akkubefehl.NICHTS, a.messen(80, laedt = true))
        // Kabel gezogen, Stand fällt wieder.
        assertEquals(Akkubefehl.NICHTS, a.messen(40, laedt = false))
        assertEquals(Akkubefehl.POSTEN, a.messen(15, laedt = false))
    }

    /**
     * EIN ANSTIEG OHNE KABEL IST KEINE ENTWARNUNG. Der Ladestand springt beim
     * Abkühlen oder nach einer Neuberechnung des Systems; von 9 % auf 12 % ist
     * Rauschen, kein Grund, die Warnung zurückzuziehen.
     */
    @Test fun rauschenInnerhalbDerWarnstufenZiehtNichtsZurueck() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(9, laedt = false))
        assertEquals(Akkubefehl.NICHTS, a.messen(12, laedt = false))
        assertEquals(Akkubefehl.NICHTS, a.messen(11, laedt = false))
    }

    /** Über die Schwelle zurück (ohne Kabel) ist dagegen eine Entwarnung. */
    @Test fun wiederUeberDerSchwelleZiehtDieWarnungZurueck() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
        assertEquals(Akkubefehl.LOESCHEN, a.messen(30, laedt = false))
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
    }

    // ---- Grenzen ----------------------------------------------------------

    @Test fun unsinnigeWerteWerdenEingefangen() {
        val a = w()
        assertEquals(Akkubefehl.POSTEN, a.messen(-5, laedt = false))   // -> 0 %
        assertEquals(Akkustufe.KRITISCH, a.stand)
        val b = w()
        assertEquals(Akkubefehl.NICHTS, b.messen(150, laedt = false))  // -> 100 %
        assertEquals(Akkustufe.OK, b.stand)
    }

    @Test fun dieStufenGrenzenSindEinschliesslich() {
        assertEquals(Akkustufe.OK, Akkustufe.zu(26))
        assertEquals(Akkustufe.KNAPP, Akkustufe.zu(25))
        assertEquals(Akkustufe.KNAPP, Akkustufe.zu(16))
        assertEquals(Akkustufe.NIEDRIG, Akkustufe.zu(15))
        assertEquals(Akkustufe.NIEDRIG, Akkustufe.zu(11))
        assertEquals(Akkustufe.KRITISCH, Akkustufe.zu(10))
        assertEquals(Akkustufe.KRITISCH, Akkustufe.zu(0))
    }

    @Test fun zuruecksetzenMachtDenWaechterWieNeu() {
        val a = w()
        a.messen(10, laedt = false)
        a.zuruecksetzen()
        assertEquals(Akkustufe.OK, a.stand)
        assertEquals(Akkustufe.OK, a.gewarntBis)
        assertEquals(Akkubefehl.POSTEN, a.messen(25, laedt = false))
    }
}
