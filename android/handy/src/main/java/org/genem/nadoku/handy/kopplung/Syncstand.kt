package org.genem.nadoku.handy.kopplung

/**
 * Die drei Zustände der Sync-Anzeige.
 *
 * Sie sind von der Garmin-Uhr übernommen, und zwar wörtlich — nicht aus
 * Bequemlichkeit, sondern weil die falsche Auskunft naheliegt: „vollständig"
 * als „nichts zu senden" wäre auch dann wahr, wenn gar keine Kopplung
 * besteht. Eine App, die „alles gesendet" sagt,
 * während sie nirgendwohin sendet, ist schlimmer als eine, die schweigt.
 *
 * **„Vollständig" gilt deshalb nur, wenn Server UND Kopplung vorhanden sind.**
 */
sealed interface Syncstand {

    /** Rot. Es fehlt etwas Grundsätzliches — der nächste Schritt gehört dazu. */
    data class NichtEingerichtet(val fehlt: Fehlt) : Syncstand

    /** Orange. Gekoppelt, aber N abgeschlossene Pakete warten. */
    data class Rueckstand(val pakete: Int) : Syncstand

    /** Blau. Server da, Kopplung da, nichts offen. */
    data object Vollstaendig : Syncstand

    enum class Fehlt { SERVERADRESSE, KOPPLUNG }

    companion object {
        /**
         * @param rueckstand nur ABGESCHLOSSENE, noch unbestätigte Pakete. Das
         *   laufende Segment zählt bewusst nicht mit — sonst stünde während
         *   des ganzen Dienstes „Rückstand 1", und die Anzeige verlöre den
         *   einen Zweck, den sie hat.
         */
        fun ermittle(basis: String?, gekoppelt: Boolean, rueckstand: Int): Syncstand = when {
            basis.isNullOrEmpty() -> NichtEingerichtet(Fehlt.SERVERADRESSE)
            !gekoppelt -> NichtEingerichtet(Fehlt.KOPPLUNG)
            rueckstand > 0 -> Rueckstand(rueckstand)
            else -> Vollstaendig
        }
    }
}
