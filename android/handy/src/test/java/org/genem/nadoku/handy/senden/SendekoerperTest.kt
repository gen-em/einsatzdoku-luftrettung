package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Paketzeile
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/** Der Nachrichtenkörper gegen den Vertrag (JSON-Vertrag 3 und 4). */
@RunWith(RobolectricTestRunner::class)
class SendekoerperTest {

    private fun segment(final: Boolean = false, dienstRef: String? = "ad-1-0000100002") = Paketzeile(
        id = 1, clientRef = "ar-2-0000300004", art = Paketzeile.ART_RUHESEGMENT,
        tag = "2026-07-16", dienstRef = dienstRef,
        begonnenAt = "2026-07-16T05:02:11Z",
        beendetAt = if (final) "2026-07-16T06:00:00Z" else null,
        final = final, streckeM = null, anstiegM = null,
        bestaetigtSeq = 0, metadatenBestaetigt = false, fehlerhaft = false,
    )

    private fun einsatz(final: Boolean = true) = Paketzeile(
        id = 2, clientRef = "am-3-0000500006", art = Paketzeile.ART_EINSATZ,
        tag = "2026-07-16", dienstRef = "ad-1-0000100002",
        begonnenAt = "2026-07-16T08:31:05Z",
        beendetAt = if (final) "2026-07-16T09:12:40Z" else null,
        final = final, streckeM = 148230, anstiegM = 410,
        bestaetigtSeq = 0, metadatenBestaetigt = false, fehlerhaft = false,
    )

    private val punkte = listOf(
        Rohpunkt(47.72611, 10.31862, 712.0, 1_784_279_465),
        Rohpunkt(47.72640, 10.31901, null, 1_784_279_475),
    )

    @Test fun ruhesegment() {
        val o = Sendekoerper.baue(segment(), seqVon = 240, punkte = punkte)
        assertEquals("rest_segment", o.getString("kind"))
        assertEquals("ar-2-0000300004", o.getString("client_ref"))
        assertEquals("2026-07-16", o.getString("day"))
        assertEquals("ad-1-0000100002", o.getString("day_ref"))
        assertEquals("2026-07-16T05:02:11Z", o.getString("started_at"))
        assertFalse(o.getBoolean("final"))
        assertEquals(240, o.getJSONObject("track").getInt("seq_from"))
    }

    /** `ended_at` ist null, solange `final` false ist (Vertrag 3). */
    @Test fun endeIstNullSolangeNichtAbgeschlossen() {
        assertTrue(Sendekoerper.baue(segment(final = false), 0, punkte).isNull("ended_at"))
        assertEquals(
            "2026-07-16T06:00:00Z",
            Sendekoerper.baue(segment(final = true), 0, punkte).getString("ended_at"),
        )
    }

    /** Ohne Dienstkennung geht das Feld gar nicht mit — die Rückfallebene greift. */
    @Test fun ohneDienstkennungFehltDasFeld() {
        assertFalse(Sendekoerper.baue(segment(dienstRef = null), 0, punkte).has("day_ref"))
    }

    /**
     * `track.points` ist ein Array aus `[lat, lon, ele_m, epoch_s]` und **muss
     * eine Liste sein** — ein Objekt mit den Schlüsseln „0", „1" … wird
     * abgelehnt (Vertrag 3). `ele_m` darf null sein.
     */
    @Test fun spurpunkteSindArraysMitVierWerten() {
        val punkteArray = Sendekoerper.baue(segment(), 0, punkte)
            .getJSONObject("track").getJSONArray("points")
        assertEquals(2, punkteArray.length())

        val erster = punkteArray.getJSONArray(0)
        assertEquals(4, erster.length())
        assertEquals(47.72611, erster.getDouble(0), 1e-9)
        assertEquals(10.31862, erster.getDouble(1), 1e-9)
        assertEquals(712.0, erster.getDouble(2), 1e-9)
        assertEquals(1_784_279_465L, erster.getLong(3))

        assertTrue("ele darf null sein", punkteArray.getJSONArray(1).isNull(2))
    }

    @Test fun einsatzTraegtKennzahlenUndPhasen() {
        val o = Sendekoerper.baue(
            einsatz(), 0, punkte,
            phasen = listOf(
                Phaseneintrag(2, "2026-07-16T08:31:05Z", 47.7261, 10.3186),
                Phaseneintrag(4, "2026-07-16T08:51:02Z", null, null),
            ),
        )
        assertEquals("mission", o.getString("kind"))
        assertEquals(148230, o.getInt("distance_m"))
        assertEquals(410, o.getInt("ascent_m"))

        val phasen = o.getJSONArray("phases")
        assertEquals(2, phasen.length())
        assertEquals(2, phasen.getJSONObject(0).getInt("phase"))
        assertEquals(47.7261, phasen.getJSONObject(0).getDouble("lat"), 1e-9)
        assertTrue("lat/lon dürfen null sein", phasen.getJSONObject(1).isNull("lat"))
    }

    /**
     * `resus_sessions` geht **gar nicht** mit — nicht einmal als leere Liste.
     * Die Reanimation bleibt bei der Garmin (E-R45-1). Eine leere Liste hieße
     * „es gibt keine"; ein fehlender Schlüssel heißt „dazu sage ich nichts",
     * und nur das ist wahr.
     */
    @Test fun reanimationWirdGarNichtErwaehnt() {
        val o = Sendekoerper.baue(einsatz(), 0, punkte)
        assertFalse(o.has("resus_sessions"))
        assertFalse(o.has("resus"))
    }

    /** Ein Ruhesegment trägt keine Phasen und keine Kennzahlen. */
    @Test fun ruhesegmentTraegtKeinePhasen() {
        val o = Sendekoerper.baue(segment(), 0, punkte)
        assertFalse(o.has("phases"))
        assertFalse(o.has("distance_m"))
    }

    /** Die harte Grenze des Servers: 512 KB Körper (Vertrag 6). */
    @Test fun einVollesTeilstueckBleibtWeitUnterDerKoerpergrenze() {
        val viele = (0 until Sender.CHUNK_PUNKTE).map {
            Rohpunkt(47.72611 + it * 0.0001, 10.31862 + it * 0.0001, 712.0 + it, 1_784_279_465L + it)
        }
        val bytes = Sendekoerper.baue(segment(), 0, viele).toString().toByteArray(Charsets.UTF_8).size
        println("Körper mit ${Sender.CHUNK_PUNKTE} Punkten: $bytes Bytes " +
            "(Grenze ${Sender.HOECHSTE_KOERPERGROESSE})")
        assertTrue(
            "Ein volles Teilstück muss unter die Grenze passen — sonst gäbe es 413 im Normalbetrieb",
            bytes < Sender.HOECHSTE_KOERPERGROESSE,
        )
    }
}
