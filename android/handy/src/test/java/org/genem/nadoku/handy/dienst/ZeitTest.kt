package org.genem.nadoku.handy.dienst

import org.junit.Assert.assertEquals
import org.junit.Test
import java.time.Instant
import java.time.ZoneId
import java.util.Locale

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

    /**
     * Der Dienstbeginn traegt das Datum nur, wenn er nicht von heute ist
     * (Backlog Nr. 160).
     *
     * Der Fall dahinter: Wer den Dienst am Freitag nicht beendet, sieht am
     * Montag weiter "laeuft seit 07:00" -- von einem Dienst, der vor zwoelf
     * Minuten begann, nicht zu unterscheiden.
     */
    @Test fun derDienstbeginnTraegtDasDatumNurWennErNichtVonHeuteIst() {
        val berlin = ZoneId.of("Europe/Berlin")
        val freitag = Instant.parse("2026-09-04T05:00:00Z")     // Fr 07:00 Ortszeit

        assertEquals(
            "Am selben Tag bleibt die Zeile so kurz wie bisher",
            "07:00",
            Zeit.seit(freitag, Instant.parse("2026-09-04T05:12:00Z"), berlin, Locale.GERMAN),
        )
        assertEquals(
            "Am Montag sagt sie, dass der Dienst vom Freitag ist",
            "Fr. 04.09., 07:00",
            Zeit.seit(freitag, Instant.parse("2026-09-07T05:12:00Z"), berlin, Locale.GERMAN),
        )
    }

    /**
     * Entschieden wird nach dem ORTSDATUM, nicht nach UTC.
     *
     * Ein Nachtdienst, der um 00:30 Ortszeit beginnt, laeuft um 01:00 noch
     * am selben Tag -- in UTC waeren es zwei verschiedene. Wer nach UTC
     * ginge, haengte jeder Nachtschicht ab Mitternacht ein Datum an.
     */
    @Test fun fuerDenTageswechselZaehltDieOrtszeit() {
        val berlin = ZoneId.of("Europe/Berlin")
        val kurzNachMitternacht = Instant.parse("2026-09-04T22:30:00Z")   // 00:30 Ortszeit am 5.
        val halbeStundeSpaeter  = Instant.parse("2026-09-04T23:00:00Z")   // 01:00 Ortszeit am 5.
        assertEquals(
            "00:30",
            Zeit.seit(kurzNachMitternacht, halbeStundeSpaeter, berlin, Locale.GERMAN),
        )
    }
}
