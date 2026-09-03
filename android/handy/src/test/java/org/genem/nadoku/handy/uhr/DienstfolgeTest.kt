package org.genem.nadoku.handy.uhr

import org.junit.Assert.assertEquals
import org.junit.Test

/**
 * Was ein Uhr-Ereignis mit dem Vordergrunddienst macht (E-S5Z-08).
 *
 * Vier Zeilen, und die zweite ist der Fehler, gegen den E2 gebaut ist: Bis
 * 0.8.1 gab es sie nicht — ein Dienstende von der Uhr schloss den Dienst im
 * Puffer und liess den Vordergrunddienst laufen (B-S5Z-04).
 */
class DienstfolgeTest {

    @Test fun einDienststartVonDerUhrStartetDenVordergrunddienst() {
        assertEquals(
            Dienstfolge.STARTEN,
            Dienstfolge.aus(liefVorher = false, laeuftNachher = true, dienstSteht = false),
        )
    }

    /**
     * Auch wenn der Dienst schon steht: `startService` auf einen laufenden
     * Dienst ist ein weiteres `onStartCommand`, kein zweiter Dienst — und
     * genau darauf baut die Aufzeichnung beim Wiederanlauf.
     */
    @Test fun einDienststartMeldetSichAuchBeiStehendemDienst() {
        assertEquals(
            Dienstfolge.STARTEN,
            Dienstfolge.aus(liefVorher = false, laeuftNachher = true, dienstSteht = true),
        )
    }

    @Test fun einDienstendeVonDerUhrBeendetDenVordergrunddienst() {
        assertEquals(
            Dienstfolge.BEENDEN,
            Dienstfolge.aus(liefVorher = true, laeuftNachher = false, dienstSteht = true),
        )
    }

    /**
     * **Ohne stehenden Dienst wird nichts angefasst.** Ein Stopp-Befehl an
     * einen Dienst, den es nicht gibt, startet ihn erst — und aus dem
     * Hintergrund wirft das ab Android 8 eine Ausnahme.
     */
    @Test fun ohneStehendenDienstGeschiehtNichts() {
        assertEquals(
            Dienstfolge.NICHTS,
            Dienstfolge.aus(liefVorher = true, laeuftNachher = false, dienstSteht = false),
        )
    }

    /** Ein Phasenwechsel ändert am Dienst nichts — und darf es nicht. */
    @Test fun einEreignisImLaufendenDienstAendertNichts() {
        assertEquals(
            Dienstfolge.NICHTS,
            Dienstfolge.aus(liefVorher = true, laeuftNachher = true, dienstSteht = true),
        )
    }

    @Test fun ohneDienstVorherUndNachherGeschiehtNichts() {
        assertEquals(
            Dienstfolge.NICHTS,
            Dienstfolge.aus(liefVorher = false, laeuftNachher = false, dienstSteht = false),
        )
    }
}
