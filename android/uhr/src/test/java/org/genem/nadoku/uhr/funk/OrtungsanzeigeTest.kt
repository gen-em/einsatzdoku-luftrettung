package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ortungscode
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotEquals
import org.junit.Test

/**
 * Sechs Stufen, **drei** Anzeigen (E3).
 *
 * WOZU DIE ZUSAMMENFASSUNG ÜBERHAUPT GEPRÜFT WIRD. Sie beantwortet zwei
 * Fragen an zwei Enden, und sie muss beide gleich beantworten:
 *
 * 1. **Die Uhr** fragt: Was zeige ich an?
 * 2. **Das Handy** fragt: Muss ich überhaupt eine Nachricht schicken?
 *
 * Liefen die beiden auseinander, meldete das Handy Wechsel, die am Handgelenk
 * nichts ändern (Funkaufwacher für nichts) — oder, schlimmer, es meldete
 * einen Wechsel **nicht**, den die Uhr angezeigt hätte. Deshalb steht die
 * Regel einmal in `gemeinsam/` und wird hier auf ihre Kanten geprüft.
 */
class OrtungsanzeigeTest {

    @Test fun vierCodesFuehrenAufKeineOrtung() {
        for (code in Ortungscode.OHNE_AUFZEICHNUNG) {
            assertEquals(
                "Code $code",
                Ortungscode.Anzeige.KEINE_ORTUNG,
                Ortungscode.anzeige(code),
            )
        }
        assertEquals(4, Ortungscode.OHNE_AUFZEICHNUNG.size)
    }

    @Test fun suchenIstEineEigeneAnzeige() {
        assertEquals(Ortungscode.Anzeige.SUCHEN, Ortungscode.anzeige(Ortungscode.SUCHT))
    }

    @Test fun beiOkZeigtDieUhrNichts() {
        assertEquals(Ortungscode.Anzeige.STILL, Ortungscode.anzeige(Ortungscode.OK))
    }

    /**
     * **`null` ist STILL und nicht KEINE_ORTUNG.** Eine Handy-Fassung vor E1
     * schickt das Feld nicht mit; daraus „keine Ortung" zu machen wäre eine
     * Behauptung über etwas, das die Uhr nicht weiss — derselbe dritte
     * Zustand wie bei `handyErreichbar` (B-S4-09).
     */
    @Test fun ohneAngabeZeigtDieUhrNichts() {
        assertEquals(Ortungscode.Anzeige.STILL, Ortungscode.anzeige(null))
    }

    /** Ein unbekannter Code (künftige Handy-Fassung) darf nichts erfinden. */
    @Test fun einUnbekannterCodeZeigtNichts() {
        assertEquals(Ortungscode.Anzeige.STILL, Ortungscode.anzeige("etwas_neues"))
    }

    /**
     * **Der Grund, warum das Handy die Anzeige vergleicht und nicht den
     * Zustand:** Diese beiden sind verschieden und sehen gleich aus. Ohne
     * diese Zeile ginge die Ersparnis beim nächsten Umbau verloren, ohne dass
     * es jemandem auffiele.
     */
    @Test fun standortAusUndKeinSignalSehenAmHandgelenkGleichAus() {
        assertNotEquals(Ortungscode.STANDORT_AUS, Ortungscode.KEIN_SIGNAL)
        assertEquals(
            Ortungscode.anzeige(Ortungscode.STANDORT_AUS),
            Ortungscode.anzeige(Ortungscode.KEIN_SIGNAL),
        )
    }

    /** Und dieser Wechsel ist einer, der gemeldet werden muss. */
    @Test fun derWechselVonKeinerOrtungZuOkIstEinWechselDerAnzeige() {
        assertNotEquals(
            Ortungscode.anzeige(Ortungscode.KEIN_SIGNAL),
            Ortungscode.anzeige(Ortungscode.OK),
        )
    }
}
