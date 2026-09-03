package org.genem.nadoku.handy.senden

import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Wann nachgesendet wird (E-S5Z-09) — die reine Entscheidung.
 *
 * SIE IST DER KERN VON E2. Der belegte Fehler (Diagnose 1.3, H2) war, dass
 * es diese Entscheidung gar nicht gab: `spaeterErneut` hiess „später" ohne
 * ein Später. Diese Fälle prüfen, dass jetzt genau dann geplant wird, wenn
 * es hilft — und sonst nicht.
 */
class NachsendenTest {

    private val keinNetz = Sendebericht(spaeterErneut = true)
    private val sauber = Sendebericht(anfragen = 3)
    private val schluesselWeg = Sendebericht(pausiert = true, spaeterErneut = false)

    // ---- Nach einem Sendelauf ---------------------------------------------

    @Test fun keinNetzMitRueckstandWirdNachgesendet() {
        assertTrue(Nachsenden.planen(keinNetz, rueckstand = 2, dienstLaeuft = false))
    }

    @Test fun ohneRueckstandGibtEsNichtsNachzusenden() {
        assertFalse(Nachsenden.planen(keinNetz, rueckstand = 0, dienstLaeuft = false))
    }

    /**
     * **401 plant keinen Job.** Wiederholen hilft nicht; es hilft eine neue
     * Kopplung, und die kann nur ein Mensch. Ein Job, der es trotzdem alle
     * 30 Sekunden versucht, verbrennt Akku für ein sicheres Nein.
     */
    @Test fun einAbgewiesenerSchluesselPlantKeinenJob() {
        assertFalse(Nachsenden.planen(schluesselWeg, rueckstand = 5, dienstLaeuft = false))
    }

    /**
     * Ein sauberer Lauf mit Rückstand plant nichts: Er hat gerade alles
     * versucht, was ging. Was übrig blieb, ist nicht durch Warten zu holen —
     * abgewiesene Pakete etwa; die zeigt die Ansicht (E-S5Z-12).
     */
    @Test fun einSauberLaufMitRestPlantNichts() {
        assertFalse(Nachsenden.planen(sauber, rueckstand = 1, dienstLaeuft = false))
    }

    /** Läuft ein Dienst, sendet dort der Takt — ein zweiter Weg wäre nur einer mehr. */
    @Test fun waehrendEinesDienstesPlantNiemandNach() {
        assertFalse(Nachsenden.planen(keinNetz, rueckstand = 2, dienstLaeuft = true))
    }

    // ---- Beim App-Start (kein Lauf davor) ----------------------------------

    /**
     * **Der Fall, der den Prozesstod abfängt.** Stirbt die App, bevor der Job
     * geplant werden konnte, wartet die Nachlieferung sonst auf den nächsten
     * Dienst. Deshalb ist beim Start jeder Rückstand Grund genug — auch ohne
     * vorangegangenen Fehlversuch.
     */
    @Test fun beimStartGenuegtEinRueckstand() {
        assertTrue(Nachsenden.planen(bericht = null, rueckstand = 1, dienstLaeuft = false))
    }

    @Test fun beimStartOhneRueckstandGeschiehtNichts() {
        assertFalse(Nachsenden.planen(bericht = null, rueckstand = 0, dienstLaeuft = false))
    }

    @Test fun beimStartWaehrendEinesDienstesGeschiehtNichts() {
        assertFalse(Nachsenden.planen(bericht = null, rueckstand = 3, dienstLaeuft = true))
    }

    /**
     * Der Unterschied zwischen „Lauf" und „Start" in einer Zeile: **dieselben
     * Zahlen, zwei Antworten.** Ohne ihn wäre einer der beiden Wege falsch,
     * und es fiele nicht auf.
     */
    @Test fun startUndSauberLaufEntscheidenBeiGleichemRestVerschieden() {
        assertTrue(Nachsenden.planen(null, rueckstand = 1, dienstLaeuft = false))
        assertFalse(Nachsenden.planen(sauber, rueckstand = 1, dienstLaeuft = false))
    }
}
