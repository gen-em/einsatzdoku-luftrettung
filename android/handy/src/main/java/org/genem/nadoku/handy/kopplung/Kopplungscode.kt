package org.genem.nadoku.handy.kopplung

/**
 * Der sechsstellige Kopplungscode (JSON-Vertrag 1a, `pair.php`).
 *
 * DIE RICHTUNG HAT SICH GEDREHT (R49, S5). Bis Android 0.10.1 tippte die
 * NutzerIn den Code hier ein, und diese Klasse fing Vertipper ab, bevor der
 * Ratenschutz von `pair.php` sie zählte. Jetzt **erzeugt der Server** den Code
 * und die App **zeigt** ihn — geprüft wird also nicht mehr eine Eingabe,
 * sondern eine Serverantwort.
 *
 * WARUM DIE PRÜFUNG TROTZDEM BLEIBT, obwohl niemand mehr tippt: Ein `start`,
 * das etwas anderes als sechs Zeichen aus diesem Alphabet zurückgibt, ist kein
 * Code — und ein Bildschirm, der `null` oder eine leere Zeichenkette groß
 * anzeigt, schickt jemanden zum Browser, wo er nichts eintragen kann. Der
 * Fehler soll dort auffallen, wo er entsteht.
 *
 * DAS ALPHABET IST NICHT VOLLSTÄNDIG, UND ZWAR ABSICHTLICH: `PAIR_CHARS` in
 * `server/db.php` lässt **0, O, 1 und I** weg, weil sie sich auf einem
 * Uhrendisplay nicht unterscheiden lassen. Die App prüft gegen genau dieses
 * Alphabet und genau diese Länge.
 */
object Kopplungscode {

    /** Zeichenvorrat, wortgleich zu `PAIR_CHARS` in `server/db.php`. */
    const val ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789"

    /** Länge, wortgleich zu `PAIR_LEN`. */
    const val LAENGE = 6

    private val MUSTER = Regex("^[$ALPHABET]{$LAENGE}$")

    /**
     * Großschreiben und Trennzeichen entfernen.
     *
     * Sie wird noch gebraucht, obwohl niemand mehr tippt: Was aus `start`
     * kommt, geht durch dieselbe Reinigung wie früher eine Eingabe — der
     * Vertrag stellt es dem Server frei, den Code zu gruppieren, und die
     * Weboberfläche nimmt ihn ihrerseits „mit und ohne Leerzeichen, mit und
     * ohne Bindestrich" an (Vertrag 1a.1). Eine App, die eine Schreibweise
     * voraussetzt, die der Vertrag nicht zusichert, ist an dieser Stelle
     * strenger als der Vertrag.
     */
    fun normalisiere(eingabe: String?): String =
        (eingabe ?: "").uppercase().filter { it.isLetterOrDigit() }

    fun gueltig(code: String): Boolean = MUSTER.matches(code)

    /**
     * Zum Anzeigen: zwei Dreiergruppen, `AB3 K7Q`.
     *
     * SO STEHT ER AUCH IM VERTRAG (1a.1: „Das Gerät zeigt ihn in zwei
     * Dreiergruppen") UND AUF DER BESTÄTIGUNGSSEITE IM BROWSER. Beides
     * dieselbe Gruppierung zu geben, ist kein Schmuck: Wer sechs Zeichen von
     * einem Bildschirm auf einen anderen überträgt, verliert ohne Gliederung
     * die Stelle — und ein falsch abgetippter Code kostet zehn Minuten
     * Wartezeit, weil die Sitzung erst verfallen muss.
     *
     * Was nicht passt, kommt unverändert zurück: Ein Code, den [gueltig] nicht
     * anerkennt, wird nicht auch noch hübsch gemacht.
     */
    fun gruppiert(code: String): String =
        if (gueltig(code)) code.substring(0, 3) + " " + code.substring(3) else code
}
