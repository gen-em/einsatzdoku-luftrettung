package org.genem.nadoku.handy.kopplung

import java.time.Instant
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.puffer.Raeumung

/**
 * Die Frist eines abgemeldeten Handys — 30 Tage, dann ist der Klartext fort.
 *
 * DER FALL (Backlog Nr. 471, Bedrohungsmodell Lage H). Ein Handy, das im Web
 * gelöscht oder gesperrt wurde, kann nicht mehr senden. Bis Android 0.20.0
 * hielt es seine abgeschlossenen Pakete — Spur und Phasen im Klartext —
 * danach ohne Ende; ein verlorenes Gerät trug sie unbegrenzt. Die
 * Garmin-Uhr räumt seit Uhr 4.1.0 30 Tage nach der ersten 401/403
 * (`Uploader.abmeldeFristPruefen`, E-R17-22); das Handy tut jetzt dasselbe.
 *
 * ZWEI FRISTEN, EINE REGEL. Die der Abmeldung räumt **alle abgeschlossenen
 * Pakete**; die der Bindung räumt den **gebundenen Rückstand** aus einer
 * früheren Kopplung, auch wenn das Handy inzwischen an ein anderes Konto
 * gekoppelt ist. Ein laufender Dienst bleibt in beiden Fällen: Sein offenes
 * Paket ist nicht abgeschlossen ([Puffer.rueckstandVerwerfen]).
 *
 * WER RUFT. Jeder Sendelauf am Anfang ([org.genem.nadoku.handy.senden.Sender])
 * und der Start der App — ein abgemeldetes Handy sendet womöglich nie wieder,
 * aber geöffnet wird es. Eine Annahme durch den Server hebt die Abmeldung auf
 * (`abmeldungVergessen`), und mit ihr die Frist.
 */
object Abmeldefrist {

    /** Räumt, was abgelaufen ist, und sagt, was gelöscht wurde. */
    fun raeumen(bindung: Rueckstandsbindung, puffer: Puffer, jetzt: Instant): Raeumung {
        bindung.fristenNachholen(jetzt)
        var pakete = 0
        var dienste = 0

        val gebundenBis = bindung.gebundenBis
        val gebundenFrist = bindung.gebundenFristBis
        if (gebundenBis != null && gebundenFrist != null && !jetzt.isBefore(gebundenFrist)) {
            val r = puffer.rueckstandVerwerfen(gebundenBis)
            pakete += r.pakete
            dienste += r.dienste
            bindung.loesen()
        }

        val abmeldeFrist = bindung.abmeldeFristBis
        if (abmeldeFrist != null && !jetzt.isBefore(abmeldeFrist)) {
            val r = puffer.rueckstandVerwerfen(Long.MAX_VALUE)
            pakete += r.pakete
            dienste += r.dienste
            /* WIE DIE UHR: Die Abmeldung ist danach vergessen. Kommt die
             * nächste 401, beginnt eine neue Frist — über Pakete, die nach
             * dieser Räumung entstanden sind. Eine Bindung zeigt danach auf
             * nichts mehr. */
            bindung.abmeldungVergessen()
            bindung.loesen()
        }
        return Raeumung(pakete = pakete, dienste = dienste)
    }

    /** Die frühere der beiden Fristen — für die Zeile der Dienstansicht. */
    fun naechsteFrist(bindung: Rueckstandsbindung): Instant? =
        listOfNotNull(bindung.abmeldeFristBis, bindung.gebundenFristBis).minOrNull()
}
