package org.genem.nadoku.handy.dienst

import java.time.Instant
import java.time.ZoneId
import java.time.ZoneOffset
import java.time.format.DateTimeFormatter
import java.util.Locale

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

    /**
     * Dienstbeginn für die Anzeige: „07:00" am selben Tag, sonst mit Datum.
     *
     * WARUM DAS DATUM NICHT IMMER STEHT: Die Zeile trägt es nur, wenn es
     * etwas sagt. Im gewöhnlichen Dienst ist der Beginn heute, und
     * „Dienst läuft seit 07:00 · kein GPS-Signal seit 43 min · keine
     * Aufzeichnung" ist bei 360 dp schon ohne acht weitere Zeichen knapp.
     *
     * WARUM ES ÜBERHAUPT STEHT: Ein Dienst, den niemand beendet hat, läuft
     * weiter. Wer am Montag die App öffnet, findet keinen Startknopf,
     * sondern „Dienst beenden" — und die Zeile sagte bis hierher
     * „läuft seit 07:00" und meinte Freitag. Das ist nicht bloß ungenau: Es
     * ist von einem Dienst, der vor zwölf Minuten begann, nicht zu
     * unterscheiden (Backlog Nr. 160).
     *
     * Der Wochentag steht vor dem Datum, weil er die Frage beantwortet, die
     * sich hier wirklich stellt — „war das vor dem Wochenende?" —, und weil
     * er vier Zeichen kostet. Er trägt seinen eigenen Punkt („Fr."), deshalb
     * folgt dahinter kein Komma; das Komma steht erst vor der Uhrzeit.
     *
     * DIE SPRACHE IST FEST DEUTSCH, nicht die des Geräts. Im Emulatorlauf zu
     * 0.15.0 stand dort „Aufzeichnung läuft seit **Tue** 08.09., 20:53" — das
     * Gerät war auf Englisch gestellt, und `Locale.getDefault()` folgte ihm
     * mitten in einen deutschen Satz hinein. Die App hat nur deutsche Texte
     * (kein `values-en`); ein englischer Wochentag darin ist kein
     * Entgegenkommen, sondern ein Bruch. Der Parameter bleibt, damit ein
     * Prüffall die Sprache setzen kann.
     */
    fun seit(beginn: Instant, jetzt: Instant,
             zone: ZoneId = ZoneId.systemDefault(),
             sprache: Locale = Locale.GERMAN): String {
        val b = beginn.atZone(zone)
        if (b.toLocalDate() == jetzt.atZone(zone).toLocalDate()) { return hhmm(beginn, zone) }
        return DateTimeFormatter.ofPattern("EE dd.MM., HH:mm", sprache).withZone(zone).format(beginn)
    }
}
