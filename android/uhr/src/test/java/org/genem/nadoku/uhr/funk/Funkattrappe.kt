package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Nachrichtenweg

/**
 * Die Transport-Attrappe — **an ihrer Stelle steht in der App der Data Layer**.
 *
 * SIE IST DER GRUND, WARUM VON C2 ÜBERHAUPT ETWAS BELEGT IST. Der echte Data
 * Layer braucht zwei gekoppelte Geräte; es gibt weder Uhr (E-R45-7) noch
 * Emulator (E-R45-8). Was hier geprüft wird, ist alles **oberhalb** der
 * Naht — und das ist der Teil, in dem Ereignisse verlorengehen können.
 *
 * SIE KANN, WAS DER FUNK KANN: zustellen, nicht zustellen, und — der
 * unangenehmste Fall — **zustellen, ohne dass die Quittung zurückkommt**.
 * Genau dieser Fall erzeugt die Doppelzustellung, gegen die das Handy
 * gewappnet sein muss.
 */
class Funkattrappe : Nachrichtenweg {

    /** Was auf der Leitung war, in der Reihenfolge — Pfad und Rumpf. */
    val gesendet = mutableListOf<Pair<String, ByteArray>>()

    /** Steht die Funkstrecke? */
    var erreichbar: Boolean = true

    override fun sende(pfad: String, rumpf: ByteArray): Boolean {
        if (!erreichbar) return false
        gesendet.add(pfad to rumpf)
        return true
    }

    /** Die Rümpfe als Text — bequemer zu vergleichen als Bytes. */
    fun texte(): List<String> = gesendet.map { String(it.second, Charsets.UTF_8) }

    fun leeren() = gesendet.clear()
}

/** Eine Ablage im Arbeitsspeicher — sie überlebt einen „Neustart" der App. */
class Merkablage(private var inhalt: String = "") : Uhrpuffer.Ablage {
    override fun lies(): String = inhalt
    override fun schreib(inhalt: String) { this.inhalt = inhalt }
}
