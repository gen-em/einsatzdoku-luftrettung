package org.genem.nadoku.uhr

import android.content.Context
import android.util.Log
import android.view.KeyEvent
import androidx.wear.input.WearableButtons

/**
 * Meldet dieses Gerät eine **freie Zusatztaste**? (E-S4-21a)
 *
 * WAS DAHINTERSTECKT. Wear-OS-Uhren haben mindestens die Ein/Aus-Taste, und
 * die ist für Apps grundsätzlich gesperrt. Manche Sportmodelle haben freie
 * Zusatztasten; auf der Galaxy-Watch-Linie ist **keine** zu erwarten (Home und
 * Zurück sind systemgebunden). Verlässlich weiß es erst die Abfrage am Gerät.
 *
 * **DAS BEDIENBILD HÄNGT NICHT DARAN.** Wo eine freie Taste gemeldet wird,
 * legt die App „nächste Phase" darauf — dasselbe, was der große Knopf tut.
 * Wo keine gemeldet wird, ändert sich nichts: Alles muss mit Touch allein
 * vollständig sein. Diese Klasse beantwortet deshalb eine Frage und trifft
 * keine Entscheidung.
 *
 * LÜNETTE UND KRONE SCROLLEN NUR und lösen nie eine Handlung aus (E-S4-21a) —
 * sie tauchen hier gar nicht auf, weil sie keine Tasten sind.
 *
 * UNGEPRÜFT: Es gibt keinen Emulator und keine Uhr. Was `WearableButtons`
 * meldet, sagt erst das Gerät; diese Klasse fängt nur ab, dass die Abfrage
 * selbst nicht zum Absturz führt.
 */
object Tastenabfrage {

    /**
     * Die Kennung der freien Taste, oder `null`.
     *
     * Genommen wird die **erste** gemeldete: Mehr als eine Handlung hat die
     * App im Einsatz nicht anzubieten, und die Wahl zwischen zwei Tasten wäre
     * eine Einstellung, die niemand vor dem ersten Einsatz trifft.
     */
    fun freieTaste(kontext: Context): Int? = try {
        val anzahl = WearableButtons.getButtonCount(kontext)
        KANDIDATEN.take(anzahl.coerceAtLeast(0)).firstOrNull { kennung ->
            WearableButtons.getButtonInfo(kontext, kennung) != null
        }
    } catch (e: Exception) {
        /* Die Abfrage selbst darf die App nicht kosten. Auf einem Gerät ohne
         * die Wear-Eingabeschicht — oder in einer Prüfumgebung — wirft sie;
         * die Antwort ist dann „keine", und alles bleibt per Touch bedienbar. */
        Log.i(MARKE, "Tastenabfrage nicht möglich: ${e.message}")
        null
    }

    /**
     * Die Tastenkennungen, die Wear OS für **freie** Tasten vergibt.
     *
     * `KEYCODE_STEM_1` bis `_3` sind genau dafür da: Tasten, die kein
     * System-Ereignis auslösen. Home und Zurück stehen bewusst nicht in der
     * Liste — sie gehören dem System, und eine App, die sie belegt, nimmt der
     * NutzerIn den Weg heraus.
     */
    private val KANDIDATEN = listOf(
        KeyEvent.KEYCODE_STEM_1,
        KeyEvent.KEYCODE_STEM_2,
        KeyEvent.KEYCODE_STEM_3,
    )

    private const val MARKE = "NAdoku"
}
