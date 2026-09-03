package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.Ortungscode
import org.genem.nadoku.gemeinsam.Phasenmarke
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Standmeldung
import org.genem.nadoku.gemeinsam.Uhrmeldung
import org.json.JSONObject
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Das Nachrichtenformat zwischen Uhr und Handy (Abnahme C2, E-S4-10).
 *
 * Robolectric, weil `org.json` gebraucht wird — in einem gewöhnlichen
 * JVM-Prüflauf sind die Klassen aus `android.jar` leere Hüllen.
 */
@RunWith(RobolectricTestRunner::class)
class NachrichtenformatTest {

    private val meldung = Uhrmeldung(
        uhrId = "u-1234512345", nr = 42, art = Ereignisart.PHASE,
        zeitMs = 1_784_279_400_123L, phase = 3, einsatzRef = "wm-7-1234512345",
    )

    @Test fun eineMeldungUeberstehtDenRundlauf() {
        assertEquals(meldung, Nachrichtenformat.liesMeldung(Nachrichtenformat.schreibe(meldung)))
    }

    @Test fun dieQuittungUeberstehtDenRundlauf() {
        val q = Quittung(bisNr = 17)
        assertEquals(q, Nachrichtenformat.liesQuittung(Nachrichtenformat.schreibe(q)))
    }

    @Test fun derStandUeberstehtDenRundlauf() {
        val s = Standmeldung(
            dienstLaeuft = true, modus = Modus.MIT_PHASENKNOEPFEN, einsatzLaeuft = true,
            laufendePhase = 4, laufendeSeit = "09:12",
            phasen = listOf(Phasenmarke(2, "09:05"), Phasenmarke(4, "09:12")),
        )
        assertEquals(s, Nachrichtenformat.liesStand(Nachrichtenformat.schreibe(s)))
    }

    // ---- Der Ortungszustand (E-S5Z-15) -------------------------------------

    @Test fun derOrtungszustandUeberstehtDenRundlauf() {
        val s = standmeldung(ortung = Ortungscode.STANDORT_AUS)
        assertEquals(s, Nachrichtenformat.liesStand(Nachrichtenformat.schreibe(s)))
        assertEquals(
            Ortungscode.STANDORT_AUS,
            Nachrichtenformat.liesStand(Nachrichtenformat.schreibe(s))?.ortung,
        )
    }

    /**
     * **Der Fall, der die Vertraeglichkeit ausmacht:** eine Nachricht ohne das
     * Feld — also von einer Handy-Fassung vor E1. Sie muss lesbar bleiben und
     * `null` liefern, nicht scheitern und nicht "ok" erfinden.
     */
    @Test fun eineNachrichtOhneDasFeldLiefertNullUndScheitertNicht() {
        val ohne = standmeldung(ortung = null)
        val rumpf = Nachrichtenformat.schreibe(ohne)
        assertFalse(
            "Ein null-Zustand darf den Schluessel gar nicht erst schreiben",
            JSONObject(String(rumpf, Charsets.UTF_8)).has("ortung"),
        )
        assertEquals(null, Nachrichtenformat.liesStand(rumpf)?.ortung)
    }

    /**
     * Die Standmeldung waechst um **einen** Schluessel, und um welchen, steht
     * hier. Der Fall ist das Gegenstueck zu [keineZugangsdatenImFormat]: Dort
     * wird gezaehlt, was die Uhr sendet, hier, was sie empfaengt.
     */
    @Test fun dieStandmeldungTraegtGenauEinenSchluesselMehr() {
        val mit = JSONObject(
            String(Nachrichtenformat.schreibe(standmeldung(Ortungscode.OK)), Charsets.UTF_8)
        ).keys().asSequence().toSet()
        assertEquals(
            setOf("dienst", "modus", "einsatz", "phase", "seit", "phasen", "ortung"),
            mit,
        )
    }

    /** Jeder Code laeuft durch — auch der, den heute niemand schickt. */
    @Test fun alleSechsCodesUeberstehenDenRundlauf() {
        val alle = listOf(
            Ortungscode.FREIGABE_FEHLT, Ortungscode.STANDORT_AUS, Ortungscode.SUCHT,
            Ortungscode.KEIN_SIGNAL, Ortungscode.UNGENAU, Ortungscode.OK,
        )
        for (code in alle) {
            val s = standmeldung(code)
            assertEquals(code, Nachrichtenformat.liesStand(Nachrichtenformat.schreibe(s))?.ortung)
        }
        assertEquals("Sechs Stufen, sechs Codes", 6, alle.toSet().size)
    }

    /**
     * Die vier Codes, bei denen die Uhr warnt, sind genau die vier, in denen
     * nichts aufgezeichnet wird — `sucht` und `ok` gehoeren nicht dazu.
     */
    @Test fun ohneAufzeichnungSindGenauVierCodes() {
        assertEquals(
            setOf(
                Ortungscode.FREIGABE_FEHLT, Ortungscode.STANDORT_AUS,
                Ortungscode.KEIN_SIGNAL, Ortungscode.UNGENAU,
            ),
            Ortungscode.OHNE_AUFZEICHNUNG,
        )
    }

    private fun standmeldung(ortung: String?) = Standmeldung(
        dienstLaeuft = true, modus = Modus.MIT_PHASENKNOEPFEN, einsatzLaeuft = true,
        laufendePhase = 4, laufendeSeit = "09:12",
        phasen = listOf(Phasenmarke(2, "09:05"), Phasenmarke(4, "09:12")),
        ortung = ortung,
    )

    /**
     * **Kein Zugangsdatum verlässt das Handy** (E-S4-11).
     *
     * Gezählt wird, statt behauptet: Die Nachricht trägt genau sechs
     * Schlüssel, und kein weiterer darf sich später dazuschleichen. Ein
     * `api_key` oder eine `device_id` auf der Uhr wäre der eine Fehler, den
     * die ganze Entscheidung „die Uhr spricht nie mit dem Server" verhindern
     * soll — und er fiele niemandem auf, weil alles weiter funktionierte.
     */
    @Test fun keineZugangsdatenImFormat() {
        val o = JSONObject(String(Nachrichtenformat.schreibe(meldung), Charsets.UTF_8))
        assertEquals(
            setOf("uhr", "nr", "art", "zeit", "phase", "einsatz_ref"),
            o.keys().asSequence().toSet(),
        )
    }

    /** Ohne Phase und ohne Einsatzkennung bleiben die Felder ganz weg. */
    @Test fun leereFelderStehenNichtInDerNachricht() {
        val schlank = meldung.copy(art = Ereignisart.DIENST_BEGINNEN, phase = null, einsatzRef = null)
        val o = JSONObject(String(Nachrichtenformat.schreibe(schlank), Charsets.UTF_8))
        assertEquals(setOf("uhr", "nr", "art", "zeit"), o.keys().asSequence().toSet())
        assertEquals(schlank, Nachrichtenformat.liesMeldung(Nachrichtenformat.schreibe(schlank)))
    }

    /**
     * **Unbrauchbar heißt fallen lassen, nicht abstürzen.**
     *
     * Diese Nachrichten laufen in einem Dienst auf, den das System startet;
     * eine geworfene Ausnahme beendete ihn. Eine Nachricht aus einer künftigen
     * Fassung der Gegenseite ist genau so ein Fall.
     */
    @Test fun unbrauchbaresWirdFallengelassen() {
        for (murks in listOf("", "kein json", "{}", """{"art":"tanzen","uhr":"u-1","nr":1,"zeit":1}""")) {
            assertNull(murks, Nachrichtenformat.liesMeldung(murks.toByteArray()))
        }
        assertNull(Nachrichtenformat.liesQuittung("{}".toByteArray()))
        assertNull(Nachrichtenformat.liesStand("[]".toByteArray()))
    }
}
