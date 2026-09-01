package org.genem.nadoku.gemeinsam

import kotlin.random.Random

/**
 * Die Client-Kennungen (`client_ref`, `day_ref`) — JSON-Vertrag 8, E-S4-09.
 *
 * DIE KENNUNG IST DER ANKER DER IDEMPOTENZ: Der Server erkennt an ihr, ob ein
 * Upload denselben Einsatz betrifft wie ein früherer. Sie muss auf **diesem
 * Gerät** eindeutig sein und darf sich niemals wiederholen — der eindeutige
 * Schlüssel auf dem Server lautet (Gerätekennung, `client_ref`).
 *
 * DIE BAUFORM IST DIE DER UHR SEIT 1.7.0: Präfix, fortlaufender Zähler im
 * Gerätespeicher, Zufallsanteil. **Kein Zeitstempel.** Die Uhr hatte einen,
 * und das hatte zwei Folgen, die für ein Handy genauso gelten:
 *
 *   1. Springt die Uhrzeit zurück (Zurücksetzen, Zeitzonenwechsel im
 *      Flugmodus, ein automatisch gestellter Netzzeitgeber), entstehen erneut
 *      Kennungen, die es schon gab — der nächste Upload träfe dann einen
 *      **fremden alten Einsatz desselben Geräts** und überschriebe ihn.
 *   2. Die Kennung verriete den Startzeitpunkt auf die Sekunde, auch wenn er
 *      später im Web berichtigt wurde.
 *
 * Der Zähler überlebt Neustarts und Zeitsprünge und ist die eigentliche
 * Zusicherung; der Zufallsanteil verhindert, dass sich Reihenfolge oder
 * Zeitpunkt ablesen lassen.
 *
 * DIE PRÄFIXE DES HANDYS (E-S4-09): `am-` Einsatz, `ar-` Ruhesegment,
 * `ad-` Dienst. Der an der Uhr ausgelöste Einsatz trägt `wm-` und wird **auf
 * der Uhr** gebildet (C2) — er ist der Idempotenz-Anker über den Funkabriss.
 * Der Server prüft Präfixe nicht; sie stehen im Vertrag, weil an ihnen
 * Verhalten hängt (die Sperrliste beim endgültigen Löschen) und weil am
 * Datensatz ablesbar bleiben soll, auf welchem Weg er entstand.
 */
class Kennungen(
    private val zaehler: Zaehlerspeicher,
    private val zufall: Random = Random.Default,
) {

    /** Wo der fortlaufende Zähler liegt — er muss Neustarts überleben. */
    interface Zaehlerspeicher {
        fun lies(): Long
        fun schreib(wert: Long)
    }

    fun einsatz(): String = neu(EINSATZ)
    fun ruhesegment(): String = neu(RUHESEGMENT)
    fun dienst(): String = neu(DIENST)

    fun neu(praefix: String): String {
        /* ZÄHLER ZUERST UND SOFORT SICHERN. Ein Absturz zwischen Lesen und
         * Schreiben darf höchstens eine Nummer überspringen, niemals eine
         * doppelt vergeben — eine übersprungene Nummer merkt niemand, eine
         * doppelte überschreibt einen fremden Datensatz. */
        var n = zaehler.lies()
        if (n < 0) n = 0
        n += 1
        if (n > UEBERLAUF) n = 1
        zaehler.schreib(n)

        val a = zufall.nextInt(0, 1 shl 16)
        val b = zufall.nextInt(0, 1 shl 16)
        return "%s-%d-%05d%05d".format(praefix, n, a, b)
    }

    companion object {
        const val EINSATZ = "am"
        const val RUHESEGMENT = "ar"
        const val DIENST = "ad"

        /** Kennung der Uhr für einen dort ausgelösten Einsatz (E-S4-09). */
        const val EINSATZ_UHR = "wm"

        /** Vertrag 3.2: höchstens 64 Zeichen. */
        const val HOECHSTLAENGE = 64

        /**
         * Wie bei der Uhr: unter dem 32-Bit-Bereich bleiben. Die Zahl ist
         * hier zwar 64 Bit breit, aber die Kennung soll auf beiden Geräten
         * gleich aussehen — und ein Zähler, der nie überläuft, wäre nur eine
         * andere Art, den Fall nicht bedacht zu haben.
         */
        const val UEBERLAUF = 2_000_000_000L
    }
}
