package org.genem.nadoku.gemeinsam

import androidx.annotation.DrawableRes
import androidx.compose.foundation.Image
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.size
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.layout.ContentScale
import androidx.compose.ui.res.painterResource
import androidx.compose.ui.unit.Dp
import org.genem.nadoku.R
import kotlin.random.Random

/**
 * Die Logo-Wahl -- dieselbe Dreier-Wahl wie im Web-Konto (Design.md 2.3) und
 * an der Garmin-Uhr (`logoWahl`, Const.mc), E-S4-22b.
 *
 * Die Zahlen sind die der Garmin, damit die drei Stellen dieselbe Sprache
 * sprechen; gespeichert wird in den App-Einstellungen (B3).
 */
enum class LogoWahl(val zahl: Int) {
    LUFT(0),
    BODEN(1),

    /**
     * Vorgabe der App. EINMAL JE APP-START gewuerfelt und dann stehengelassen
     * -- Design.md 2.3: "Ein Logo, das bei jedem Seitenaufruf wechselt, ist
     * kein Logo, sondern ein Flackern."
     */
    WECHSELND(2);

    companion object {
        fun ausZahl(zahl: Int): LogoWahl = entries.firstOrNull { it.zahl == zahl } ?: WECHSELND
    }
}

/** Welches der beiden Motive tatsaechlich gezeichnet wird. */
enum class Motiv { LUFT, BODEN }

/**
 * Loest die Logo-Wahl in ein Motiv auf.
 *
 * WARUM DER WURF EIN OBJEKT-ZUSTAND IST und nicht bei jedem Aufruf neu
 * geschieht: "Wechselnd" heisst wechselnd von Start zu Start, nicht von
 * Bildschirm zu Bildschirm. Die Uhr wuerfelt eigenstaendig (E-S4-22b) -- sie
 * uebernimmt vom Handy die EINSTELLUNG, nicht den Wurf; damit brauchen die
 * beiden Geraete sich darueber nicht abzustimmen, wie schon bei der Garmin.
 */
object Bildmarke {

    /**
     * Der Wurf dieses App-Starts. `lazy` ist hier die ganze Zusicherung: Der
     * Ausdruck laeuft genau einmal je Prozess, und danach steht der Wert.
     */
    private val wurfDiesesStarts: Motiv by lazy {
        if (Random.nextBoolean()) Motiv.LUFT else Motiv.BODEN
    }

    fun motiv(wahl: LogoWahl): Motiv = when (wahl) {
        LogoWahl.LUFT -> Motiv.LUFT
        LogoWahl.BODEN -> Motiv.BODEN
        LogoWahl.WECHSELND -> wurfDiesesStarts
    }

    /**
     * Anteil der Kachelbreite, den das Motiv einnimmt.
     *
     * DIE STUFUNG GEHT UEBER DIE FLAECHE, NICHT UEBER DIE HOEHE (E-S4-22b,
     * S3-Erkenntnis Punkt K). Die Luftmarke liegt quer (400,16 x 249,81, also
     * 1,602:1), die Bodenmarke ist quadratisch (420 x 420). Auf gleiche Hoehe
     * gebracht, wirkt die quadratische deutlich schwerer.
     *
     * Die 78 % sind nicht geschaetzt: Sie sind der Wert, mit dem
     * tools/uhr-bilder/erzeugen.sh die Kacheln der Garmin-Uhr rastert, und
     * dort aus den vorhandenen Dateien zurueckgerechnet. Beide Motive sind
     * damit praktisch gleich hoch: 1/1,602 = 62,4 % gegen 78 % Breite bei
     * gleicher Flaechenwirkung.
     */
    private const val ANTEIL_BODEN = 0.78f

    @DrawableRes
    fun ressource(motiv: Motiv, aufDunkel: Boolean): Int = when {
        motiv == Motiv.LUFT && aufDunkel -> R.drawable.marke_luft_weiss
        motiv == Motiv.LUFT -> R.drawable.marke_luft_farbig
        aufDunkel -> R.drawable.marke_boden_weiss
        else -> R.drawable.marke_boden_farbig
    }

    /**
     * Breitenanteil des Motivs in seiner quadratischen Kachel.
     * Oeffentlich, weil die Pruefung ihn nachrechnet.
     */
    fun breitenanteil(motiv: Motiv): Float =
        if (motiv == Motiv.BODEN) ANTEIL_BODEN else 1f
}

/**
 * Die Bildmarke, gezeichnet in eine QUADRATISCHE Kachel.
 *
 * Der Aufrufer gibt die Kachelkante an, nicht die Bildhoehe -- das ist die
 * Umsetzung von "gestuft wird ueber die Flaeche, nicht die Hoehe". Innerhalb
 * der Kachel steht die Luftmarke auf voller Breite, die Bodenmarke auf 78 %;
 * beide mittig. Es ist genau das Rezept der Garmin-Kacheln.
 *
 * @param kachel Kantenlaenge der quadratischen Kachel
 * @param aufDunkel true = weisse Fassung (dunkelblauer Kopf des Handys,
 *        Asphaltgrund der Uhr), false = farbige Fassung auf hellem Grund
 *        (Design.md 2.3)
 */
@Composable
fun Bildmarke(
    motiv: Motiv,
    kachel: Dp,
    aufDunkel: Boolean,
    modifier: Modifier = Modifier,
    beschreibung: String? = null,
) {
    Box(modifier = modifier.size(kachel), contentAlignment = Alignment.Center) {
        Image(
            painter = painterResource(Bildmarke.ressource(motiv, aufDunkel)),
            contentDescription = beschreibung,
            contentScale = ContentScale.Fit,
            modifier = Modifier.size(kachel * Bildmarke.breitenanteil(motiv)),
        )
    }
}
