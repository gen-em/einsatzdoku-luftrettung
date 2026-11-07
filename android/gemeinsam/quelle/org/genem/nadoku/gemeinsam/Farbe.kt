package org.genem.nadoku.gemeinsam

import androidx.compose.runtime.Composable
import androidx.compose.runtime.ReadOnlyComposable
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.colorResource
import org.genem.nadoku.R

/**
 * Die Farb-Token der Marke, so wie Compose sie braucht.
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
 * LEITWERTE, NICHT ROLLEN. Was hier steht, schaltet nicht um: die
 * Kopfleiste des Handys (Dunkelblau mit Schnee), die Uhr (immer dunkel), die
 * Bildmarke. Die Rollen des Handys -- Flaechen, Schrift, Handeln, Zustaende,
 * hell und dunkel -- stehen in [Rolle] (seit Android 0.22.0).
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

    // ---- Orange (Leitwerte; die Hauptaktion ist Rolle.aktionHaupt) -----------
    val orange: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange)
    val orangeTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange_tief)
    val orangeHell: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_orange_hell)

    // ---- Blau erklaert und bestaetigt --------------------------------------
    val blau: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau)
    val blauTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau_tief)
    val blauHell: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau_hell)
    /** Die Aktion des Dark Mode im Web (blau 300) -- auf der Uhr, die immer
     *  dunkel ist, die Flaeche des grossen Knopfs und die naechste Phase. */
    val blau300: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_blau_300)

    // ---- Rot warnt ---------------------------------------------------------
    val rot: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rot)
    val rotTief: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rot_tief)
    val rosa: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.marke_rosa)
}

/**
 * Die Farbrollen des Handys -- dieselben wie im Web (`--x` ist `rolle_x`).
 *
 * HELL UND DUNKEL STEHEN NICHT HIER, sondern in `values/rollen.xml` und
 * `values-night/rollen.xml`; Android waehlt nach der Systemeinstellung,
 * `colorResource` liest die passende Datei. Handeln ist Dunkelblau
 * ([aktion]), die eine Hauptaktion Orange ([aktionHaupt], „Dienst
 * beginnen"); ein Zustand in Ordnung ist gruen ([erfolgText]), eine
 * Eigenschaft blau ([infoText]), ein Fehler rot ([fehlerText]).
 *
 * `werkzeuge/farbabgleich.py` haelt beide Dateien gegen das Web,
 * `werkzeuge/kontraste.py` jedes Paar in beiden Modi.
 */
object Rolle {
    val flaecheSeite: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_flaeche_seite)
    val flaecheKarte: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_flaeche_karte)
    val text: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_text)
    val textTitel: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_text_titel)
    val gedaempft: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_gedaempft)
    val textLink: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_text_link)
    val linie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_linie)
    val linieStark: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_linie_stark)

    val aktion: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_aktion)
    val aufAktion: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_auf_aktion)
    val aktionHaupt: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_aktion_haupt)
    val aufAktionHaupt: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_auf_aktion_haupt)
    val auswahlFlaeche: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_auswahl_flaeche)
    val auswahlText: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_auswahl_text)

    val infoFlaeche: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_info_flaeche)
    val infoText: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_info_text)
    val infoLinie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_info_linie)
    val erfolgText: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_erfolg_text)
    val erfolgLinie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_erfolg_linie)
    val warnungLinie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_warnung_linie)
    val fehlerText: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_fehler_text)
    val fehlerLinie: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_fehler_linie)

    /**
     * Beendende Handlungen: VOLLFLAECHIG rot. Das Web zeichnet den
     * Gefahrknopf seit Repo-Commit e7bf4fc als Umriss; die App bleibt bei
     * der Flaeche (E-MA-36) -- auf einem Geraet im Einsatz muss die
     * beendende Handlung ohne Lesen erkennbar sein. Werte wie der Zaehler
     * offener Fehler im Web (`--gefahr`, `--auf-gefahr`).
     */
    val gefahr: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_gefahr)
    val aufGefahr: Color @Composable @ReadOnlyComposable get() = colorResource(R.color.rolle_auf_gefahr)
}
