package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.Nachrichtenweg
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Uhrmeldung

/**
 * Der Funk der Uhr: melden, puffern, nachliefern (E-S4-10).
 *
 * DAS VERSPRECHEN LAUTET: **Kein Ereignis geht verloren, und keines wirkt
 * zweimal.** Die erste Hälfte hält der Puffer — gemerkt wird, bevor gesendet
 * wird, und gelöscht erst nach der Quittung. Die zweite Hälfte hält das Handy
 * (die Nummer ist der Ausweis; eine bekannte Nummer wird quittiert und nicht
 * noch einmal gewirkt).
 *
 * WARUM ES BEIDE HÄLFTEN BRAUCHT: Weil die Quittung selbst verlorengehen
 * kann. Die Uhr sieht dann nur „keine Antwort" und muss nachliefern — sie
 * kann nicht unterscheiden, ob das Ereignis nicht ankam oder ob nur die
 * Quittung nicht zurückkam. Beim häufigeren dieser beiden Fälle ist
 * Nachliefern richtig; beim anderen ist es eine Doppelzustellung, und die
 * abzufangen ist Aufgabe des Empfängers, nicht des Senders.
 *
 * `handyErreichbar` IST EINE BEOBACHTUNG, KEINE VERBINDUNGSANZEIGE: Sie sagt,
 * ob der letzte Sendeversuch zugestellt wurde. Etwas Genaueres gibt es nicht
 * — der Data Layer meldet keinen Verbindungszustand, nur Erfolg oder
 * Misserfolg der einzelnen Nachricht.
 */
class Uhrfunk(
    private val puffer: Uhrpuffer,
    private val weg: Nachrichtenweg,
) {

    private val kennungen = Kennungen(puffer.kennungsspeicher())

    /** Wurde der letzte Sendeversuch zugestellt? */
    var handyErreichbar: Boolean = true
        private set

    fun offen(): Int = puffer.anzahl()

    fun quittiertBis(): Long = puffer.quittiertBis

    /** Ist dieses Ereignis beim Handy angekommen? */
    fun bestaetigt(nr: Long): Boolean = nr <= puffer.quittiertBis

    /**
     * Eine `wm-`-Kennung bilden (E-S4-09) — für ein Ereignis, das einen
     * Einsatz **eröffnet**.
     *
     * Sie entsteht **auf der Uhr** und reist mit der gepufferten Nachricht
     * mit. Genau dadurch ist sie über den Funkabriss hinweg dieselbe: Das
     * Handy erkennt den Einsatz bei der Nachlieferung wieder, statt einen
     * zweiten anzulegen.
     */
    fun einsatzkennung(): String = kennungen.neu(Kennungen.EINSATZ_UHR)

    /**
     * Ein Ereignis melden: **erst merken, dann senden.**
     *
     * Die Reihenfolge ist der ganze Punkt. Umgekehrt — senden, und bei
     * Misserfolg merken — verlöre genau die Ereignisse, bei denen das Senden
     * nicht sauber scheitert, sondern hängen bleibt.
     */
    fun melde(
        art: Ereignisart,
        zeitMs: Long,
        phase: Int? = null,
        einsatzRef: String? = null,
    ): Uhrmeldung {
        val meldung = Uhrmeldung(
            uhrId = puffer.uhrId,
            nr = puffer.naechsteNr(),
            art = art,
            zeitMs = zeitMs,
            phase = phase,
            einsatzRef = einsatzRef,
        )
        val rumpf = Nachrichtenformat.schreibe(meldung)
        puffer.anhaengen(rumpf)
        handyErreichbar = weg.sende(Nachrichtenformat.PFAD_EREIGNIS, rumpf)
        return meldung
    }

    /**
     * Alles Wartende erneut senden — **unverändert** (E-S4-10).
     *
     * Die Reihenfolge bleibt die des Entstehens: Der Dienststart muss vor der
     * ersten Phase ankommen, sonst legte das Handy die Phase in einen Dienst,
     * den es noch nicht gibt. Bricht der Funk mittendrin ab, wird abgebrochen
     * statt weitergemacht — der Rest käme sonst ohne seinen Vorgänger an.
     *
     * @return wie viele Nachrichten zugestellt wurden
     */
    fun nachliefern(): Int {
        var zugestellt = 0
        for (rumpf in puffer.wartende()) {
            if (!weg.sende(Nachrichtenformat.PFAD_EREIGNIS, rumpf)) {
                handyErreichbar = false
                return zugestellt
            }
            zugestellt += 1
        }
        handyErreichbar = true
        return zugestellt
    }

    /**
     * Eine Quittung verbuchen.
     *
     * @return wie viele Nachrichten dadurch aus dem Puffer verschwunden sind
     */
    fun quittung(q: Quittung): Int {
        val weg = puffer.quittieren(q.bisNr)
        if (weg > 0) handyErreichbar = true
        return weg
    }
}
