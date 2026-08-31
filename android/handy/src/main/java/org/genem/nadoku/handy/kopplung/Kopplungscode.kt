package org.genem.nadoku.handy.kopplung

/**
 * Der sechsstellige Kopplungscode (JSON-Vertrag 1a, `pair.php`).
 *
 * DAS ALPHABET IST NICHT VOLLSTÄNDIG, UND ZWAR ABSICHTLICH: `PAIR_CHARS` in
 * `server/db.php` lässt **0, O, 1 und I** weg, weil sie sich auf einem
 * Uhrendisplay nicht unterscheiden lassen. Die App prüft gegen genau dieses
 * Alphabet und genau diese Länge.
 *
 * WARUM SIE HIER ÜBERHAUPT GEPRÜFT WIRD, wo `pair.php` es auch tut: Ein
 * offensichtlich unbrauchbarer Code soll gar nicht erst gesendet werden. Der
 * Ratenschutz von `pair.php` zählt jeden Fehlversuch — wer sich vertippt und
 * das fünfmal wiederholt, sperrt sich sonst selbst aus, ohne je einen
 * ernstgemeinten Versuch gemacht zu haben.
 *
 * WAS NICHT PASSIERT: eine „Verbesserung" getippter Nullen zu O oder Einsen
 * zu I. Beide Zeichen fehlen im Alphabet, das Ziel wäre also ebenso falsch wie
 * die Eingabe. Wer eine 0 tippt, hat etwas anderes falsch gelesen — dann ist
 * die klare Ablehnung die ehrlichere Antwort als ein geratener Code.
 */
object Kopplungscode {

    /** Zeichenvorrat, wortgleich zu `PAIR_CHARS` in `server/db.php`. */
    const val ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"

    /** Länge, wortgleich zu `PAIR_LEN`. */
    const val LAENGE = 6

    private val MUSTER = Regex("^[$ALPHABET]{$LAENGE}$")

    /**
     * Großschreiben und Trennzeichen entfernen — Leerzeichen und Bindestriche
     * schreibt man beim Abtippen mit, und sie sind kein Fehler der NutzerIn.
     */
    fun normalisiere(eingabe: String?): String =
        (eingabe ?: "").uppercase().filter { it.isLetterOrDigit() }

    fun gueltig(code: String): Boolean = MUSTER.matches(code)
}
