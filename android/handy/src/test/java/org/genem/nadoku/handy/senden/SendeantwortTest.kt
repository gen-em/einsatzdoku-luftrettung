package org.genem.nadoku.handy.senden

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/** Die Antworten von `ingest.php` (JSON-Vertrag 5). */
@RunWith(RobolectricTestRunner::class)
class SendeantwortTest {

    @Test fun erfolg() {
        val a = Sendeantwort.lese(200, """{"ok":true,"id":17,"stored_points":212,"next_seq":452}""")
            as Sendeantwort.Angekommen
        assertEquals(17L, a.id)
        assertEquals(212, a.gespeichertePunkte)
        assertEquals(452L, a.naechsteSeq)
        assertTrue("Nichts verworfen, nichts übergangen", a.vollstaendig)
    }

    /**
     * **Ein `ok: true` mit `rejected` ist kein reiner Erfolg** (E-S4-06).
     * Der Upload ist angekommen, aber nicht vollständig übernommen.
     */
    @Test fun erfolgMitVerworfenenWerten() {
        val a = Sendeantwort.lese(
            200,
            """{"ok":true,"id":17,"next_seq":10,"rejected":{"phases.phase: ausserhalb von 2…9":2}}""",
        ) as Sendeantwort.Angekommen
        assertFalse(a.vollstaendig)
        assertEquals(2, a.verworfen["phases.phase: ausserhalb von 2…9"])
    }

    /** `kept_*` sagt: eine ganze Liste wurde übergangen, der Stand blieb. */
    @Test fun erfolgMitUebergangenerListe() {
        val a = Sendeantwort.lese(200, """{"ok":true,"next_seq":3,"kept_phases":8}""")
            as Sendeantwort.Angekommen
        assertFalse(a.vollstaendig)
        assertEquals(8, a.uebergangen["kept_phases"])
    }

    @Test fun dieVierFehlerpfadeDesVertrags() {
        assertEquals(Sendeantwort.Fehlerhaft, Sendeantwort.lese(400, """{"error":"payload"}"""))
        assertEquals(Sendeantwort.SchluesselAbgewiesen, Sendeantwort.lese(401, """{"error":"auth"}"""))
        assertEquals(Sendeantwort.ZuGross, Sendeantwort.lese(413, """{"error":"too_large"}"""))
        assertEquals(Sendeantwort.SpaeterErneut(503), Sendeantwort.lese(503, null))
        assertEquals(Sendeantwort.SpaeterErneut(500), Sendeantwort.lese(500, "kaputt"))
    }

    /** 200 ohne `ok` ist kein Erfolg — und darf keiner werden. */
    @Test fun zweihundertOhneOkIstKeinErfolg() {
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, """{"ok":false}"""))
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, ""))
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, "kein json"))
    }
}
