package org.genem.nadoku.handy.dienst

import java.time.Instant
import java.time.ZoneId
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter

/**
 * Zeitangaben, wie der Vertrag sie verlangt (JSON-Vertrag 2).
 *
 * ZWEI FORMATE, UND DIE VERWECHSLUNG IST TEUER:
 *
 *  - **Zeitstempel** sind ISO 8601 **in UTC** mit `Z`, sekundengenau:
 *    `2026-07-16T08:31:05Z`. Sie stehen an `started_at`, `ended_at`, an jeder
 *    Phase und an jeder Reanimation.
 *  - **Spurpunkte** tragen die **Unix-Epoche in Sekunden**, eine blanke Zahl.
 *
 *  - **`day`** ist etwas Drittes: das **lokale** Datum des Dienstbeginns.
 *    Nicht das UTC-Datum — ein Nachtdienst, der um 00:30 Ortszeit beginnt,
 *    gehört zum neuen Tag, und in UTC wäre es noch der alte. Seit Vertrag 1.3
 *    ist `day` ohnehin nur noch Sortier- und Anzeigedatum; die Zuordnung
 *    leistet `day_ref`.
 *
 * DIE ZEITZONE WIRD NICHT FESTGENAGELT. `ZoneId.systemDefault()` ist richtig:
 * Wer in einer anderen Zeitzone Dienst tut, hat dort auch seinen Diensttag.
 * Für die Prüfung ist sie einsetzbar, damit ein Prüffall nicht davon abhängt,
 * wo die Maschine steht.
 */
object Zeit {

    private val ISO: DateTimeFormatter =
        DateTimeFormatter.ofPattern("yyyy-MM-dd'T'HH:mm:ss'Z'").withZone(ZoneOffset.UTC)

    private val TAG: DateTimeFormatter = DateTimeFormatter.ofPattern("yyyy-MM-dd")

    /** Zeitstempel für den Vertrag: ISO 8601, UTC, sekundengenau. */
    fun iso(augenblick: Instant): String = ISO.format(augenblick.truncatedTo(java.time.temporal.ChronoUnit.SECONDS))

    /** Unix-Epoche in Sekunden — das Format der Spurpunkte. */
    fun epoche(augenblick: Instant): Long = augenblick.epochSecond

    /**
     * Lokales Datum (`day`) zu einem Augenblick.
     *
     * `atZone(...).toLocalDate()` und **nicht** `LocalDate.ofInstant(...)`:
     * Letzteres gibt es erst ab API 34, unser minSdk ist 26. Der Unterschied
     * fiele erst auf einem älteren Gerät auf — und dort als Absturz.
     */
    fun tag(augenblick: Instant, zone: ZoneId = ZoneId.systemDefault()): String =
        TAG.format(augenblick.atZone(zone).toLocalDate())

    /** Lokale Uhrzeit „HH:MM" für die Anzeige — nie für den Vertrag. */
    fun hhmm(augenblick: Instant, zone: ZoneId = ZoneId.systemDefault()): String =
        DateTimeFormatter.ofPattern("HH:mm").withZone(zone).format(augenblick)
}
