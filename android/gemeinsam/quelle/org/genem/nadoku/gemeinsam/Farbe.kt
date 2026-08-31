package org.genem.nadoku.gemeinsam

import androidx.compose.runtime.Composable
import androidx.compose.runtime.ReadOnlyComposable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.colorResource
import org.genem.nadoku.R

/**
 * Die Farb-Token der Marke, so wie Compose sie braucht (E-S4-22a).
 *
 * DIE WERTE STEHEN NICHT HIER. Sie stehen in `gemeinsam/res/values/farben.xml`,
 * und dieses Objekt holt sie von dort. Der Umweg ist Absicht: Die
 * XML-Ressourcen brauchen die Werte ohnehin -- der Hintergrund des
 * Launcher-Symbols, die Themen der beiden Module --, und ein Hexwert, der
 * einmal in Kotlin und einmal in XML steht, ist genau der Zustand, den
 * `:root` im Web beseitigt hat.
 *
 * `werkzeuge/farbabgleich.py` haelt die XML-Datei gegen `:root` und meldet
 * jede Abweichung; damit ist die Kette geschlossen: Web -> farben.xml -> App.
 *
 * WARUM ES @Composable-EIGENSCHAFTEN SIND: `colorResource` braucht den
 * Ressourcen-Zugriff der Compose-Umgebung. Wer eine Farbe ausserhalb einer
 * Composable braucht -- etwa fuer die Benachrichtigung des Vordergrunddienstes
 * --, nimmt `ContextCompat.getColor(kontext, R.color.marke_orange)`; auch das
 * liest dieselbe Datei.
 */
object Farbe {

    // ---- Flaechen ----------------------------------------------------------
    val schnee: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_schnee)
    val rauch: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rauch)
    val sand: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_sand)

    // ---- Schrift -----------------------------------------------------------
    val asphalt: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_asphalt)
    val dunkelblau: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_dunkelblau)
    val gedaempft: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_gedaempft)
    val aufDunkel: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_auf_dunkel)

    // ---- Linien ------------------------------------------------------------
    val linie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_linie)

    // ---- Orange handelt ----------------------------------------------------
    val orange: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange)
    val orangeTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange_tief)
    val orangeHell: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange_hell)

    // ---- Blau erklaert und bestaetigt --------------------------------------
    val blau: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau)
    val blauTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau_tief)
    val blauHell: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau_hell)

    // ---- Rot warnt ---------------------------------------------------------
    val rot: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rot)
    val rotTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rot_tief)
    val rosa: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rosa)

    /**
     * Der Primaerknopf traegt DUNKELBLAUE Schrift auf Orange, nicht weisse
     * (E-P3-15): Weiss auf Orange sind 2,3:1 und damit unlesbar, Dunkelblau
     * auf Orange 5,97:1. Dieselbe Entscheidung wie im Web -- die Mockups
     * zeigen an dieser Stelle Weiss, der Entscheidungstext gilt.
     */
    val knopfPrimaerFlaeche: Color @Composable @ReadOnlyComposable get() = orange
    val knopfPrimaerSchrift: Color @Composable @ReadOnlyComposable get() = dunkelblau

    /**
     * Beendende Handlungen: vollflaechig rot mit weisser Schrift (E-S4-22a).
     * Das ist bewusst groesser aufgetragen als der rote Rahmen des
     * Web-Gefahrknopfs -- auf einem Geraet im Einsatz muss die beendende
     * Handlung ohne Lesen erkennbar sein. Weiss auf `--rot` sind 4,78:1 -- ueber AA.
     * werkzeuge/kontraste.py rechnet alle Paare der App nach.
     */
    val knopfBeendenFlaeche: Color @Composable @ReadOnlyComposable get() = rot
    val knopfBeendenSchrift: Color @Composable @ReadOnlyComposable get() = aufDunkel
}
