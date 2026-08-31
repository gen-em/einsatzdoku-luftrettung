package org.genem.nadoku.handy.dienst

import org.junit.Assert.assertEquals
import org.junit.Test
import java.time.Instant
import java.time.ZoneId

/** Die Zeitformate des Vertrags (JSON-Vertrag 2). */
class ZeitTest {

    private val augenblick: Instant = Instant.parse("2026-07-16T08:31:05Z")

    @Test fun zeitstempelIstIsoInUtcMitZ() {
        assertEquals("2026-07-16T08:31:05Z", Zeit.iso(augenblick))
    }

    @Test fun bruchteileVonSekundenFallenWeg() {
        assertEquals(
            "Der Vertrag kennt nur Sekunden",
            "2026-07-16T08:31:05Z",
            Zeit.iso(Instant.parse("2026-07-16T08:31:05.987Z")),
        )
    }

    @Test fun spurpunkteTragenDieUnixEpoche() {
        assertEquals(1_784_190_665L, Zeit.epoche(augenblick))
    }

    /**
     * `day` ist das **lokale** Datum, nicht das UTC-Datum.
     *
     * Der Fall ist ein Nachtdienst: 00:30 Ortszeit in Berlin ist 22:30 UTC am
     * Vortag. Der Dienst gehört zum neuen Tag — wer nach UTC ginge, sortierte
     * ihn einen Tag zurück.
     */
    @Test fun derDiensttagIstDerLokaleTag() {
        val nachts = Instant.parse("2026-07-15T22:30:00Z")   // 00:30 in Berlin
        assertEquals("2026-07-16", Zeit.tag(nachts, ZoneId.of("Europe/Berlin")))
        assertEquals("2026-07-15", Zeit.tag(nachts, ZoneId.of("UTC")))
    }

    @Test fun anzeigezeitIstLokal() {
        assertEquals("10:31", Zeit.hhmm(augenblick, ZoneId.of("Europe/Berlin")))
        assertEquals("08:31", Zeit.hhmm(augenblick, ZoneId.of("UTC")))
    }
}
