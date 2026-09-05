package org.genem.nadoku.handy

import android.graphics.Bitmap
import android.graphics.Canvas
import android.os.Looper
import android.view.View
import androidx.activity.ComponentActivity
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.ui.Modifier
import androidx.compose.ui.platform.ComposeView
import org.genem.nadoku.R
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.Robolectric
import org.robolectric.RobolectricTestRunner
import org.robolectric.Shadows.shadowOf
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import kotlin.math.abs

/**
 * Die Zweierwahl, nachgemessen — der Fall vom S24 (05.09.2026).
 *
 * WARUM EIN EIGENER FALL, wo `HandyBildTest` doch jede Dienstansicht malt:
 * Der misst die Bedienhöhe an den **farbigen** Knöpfen (Orange, Rot). Die
 * Zweierwahl trägt keine davon, und so blieb unbemerkt, dass ihre Hälften
 * die Zeile nicht füllten — 42 dp Hellblau in einem 48-dp-Rahmen, ein
 * ungefärbter Streifen unten, der Text drei Punkte zu hoch, der Trennstrich
 * mit Höhe 0. Alles im Bild, in 78 Bildern, und von keiner Regel gemessen.
 *
 * GEMESSEN WIRD IN EINER ROLLENDEN SPALTE, wie in der Dienstansicht — denn
 * genau dort entsteht der Fehler: Erst eine unendliche Höchsthöhe nimmt
 * `fillMaxHeight()` seine Wirkung. In einer festen Spalte sähe alles richtig
 * aus, und der Fall bewiese nichts.
 *
 * Drei Zusicherungen, jede mit Zahl:
 *
 * | Gemessen | Soll |
 * |---|---|
 * | Höhe der hellblauen Fläche | mindestens 48 dp abzüglich der zwei Rahmenlinien |
 * | Trennstrich zwischen den Hälften | eine Spalte in `gedaempft` über die ganze Höhe |
 * | Mitte der Schrift gegen Mitte der Fläche | höchstens 2,5 dp auseinander |
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w360dp-h800dp-xhdpi")
class ZweierwahlBildTest {

    private val breiteDp = 360
    private val hoeheDp = 200

    @Test
    fun dieGewaehlteHaelfteFuelltDieZeileUndDerTrennstrichSteht() {
        val activity = Robolectric.buildActivity(ComponentActivity::class.java).setup().get()
        val dichte = activity.resources.displayMetrics.density
        val blauHell = activity.getColor(R.color.marke_blau_hell)
        val gedaempft = activity.getColor(R.color.marke_gedaempft)
        val schnee = activity.getColor(R.color.marke_schnee)

        val ansicht = ComposeView(activity)
        activity.setContentView(ansicht)
        ansicht.setContent {
            /* Rollende Spalte mit Polster -- dieselbe Umgebung wie in
             * `DienstAnsicht`, wo die Zeile steht. */
            Column(Modifier.verticalScroll(rememberScrollState()).padding(Abstand.vier)) {
                Zweierwahl(
                    links = "Mit Phasenknöpfen",
                    rechts = "Nur aufzeichnen",
                    linksGewaehlt = false,
                ) {}
            }
        }
        shadowOf(Looper.getMainLooper()).idle()

        val breitePx = (breiteDp * dichte).toInt()
        val hoehePx = (hoeheDp * dichte).toInt()
        val fest = { n: Int -> View.MeasureSpec.makeMeasureSpec(n, View.MeasureSpec.EXACTLY) }
        ansicht.measure(fest(breitePx), fest(hoehePx))
        ansicht.layout(0, 0, breitePx, hoehePx)
        shadowOf(Looper.getMainLooper()).idle()

        val bild = Bitmap.createBitmap(breitePx, hoehePx, Bitmap.Config.ARGB_8888)
        ansicht.draw(Canvas(bild))
        val punkte = IntArray(breitePx * hoehePx)
        bild.getPixels(punkte, 0, breitePx, 0, 0, breitePx, hoehePx)
        fun bei(x: Int, y: Int) = punkte[y * breitePx + x]

        // ---- 1. Die hellblaue Flaeche ist so hoch wie die Zeile -------------
        val blaueZeilen = (0 until hoehePx).filter { y -> (0 until breitePx).any { bei(it, y) == blauHell } }
        val randZeilen = (0 until hoehePx).filter { y -> (0 until breitePx).any { bei(it, y) == gedaempft } }
        check(blaueZeilen.isNotEmpty() && randZeilen.isNotEmpty()) { "Nichts gezeichnet" }
        val blauDp = blaueZeilen.size / dichte.toDouble()
        val rahmenDp = (randZeilen.last() - randZeilen.first() + 1) / dichte.toDouble()
        println(
            "ZWEIERWAHL: Rahmen %.1f dp, Hellblau %.1f dp (Soll: mindestens %d dp abzüglich 2 dp Rahmen)"
                .format(rahmenDp, blauDp, BEDIENHOEHE.value.toInt())
        )
        check(rahmenDp >= BEDIENHOEHE.value - 1.0) { "Zeile unter der Bedienhöhe: %.1f dp".format(rahmenDp) }
        check(blauDp >= rahmenDp - 2.0 - 1.0) {
            "Die gewählte Hälfte füllt die Zeile nicht: %.1f dp Blau in %.1f dp Rahmen".format(blauDp, rahmenDp)
        }

        // ---- 2. Der Trennstrich steht ueber die ganze Hoehe -----------------
        /* Eine Spalte, die in (fast) jeder blauen Zeile `gedaempft` traegt --
         * die Rahmenlinien links und rechts zaehlen nicht, sie liegen
         * ausserhalb der blauen Zeilen nicht anders als innerhalb; der
         * Trennstrich ist die einzige senkrechte Linie ZWISCHEN den
         * Haelften. Vor der Behebung hatte er Hoehe 0. */
        val trennspalten = (breitePx / 3 until breitePx * 2 / 3).filter { x ->
            blaueZeilen.count { y -> bei(x, y) == gedaempft } >= blaueZeilen.size - 2
        }
        println("ZWEIERWAHL: Trennstrich in ${trennspalten.size} Spalte(n) bei ${trennspalten.firstOrNull()} px")
        check(trennspalten.isNotEmpty()) { "Kein Trennstrich zwischen den Hälften" }

        // ---- 3. Die Schrift steht in der Mitte des RAHMENS ------------------
        /* GEGEN DEN RAHMEN, NICHT GEGEN DIE BLAUE FLAECHE: Vor der Behebung
         * war die Schrift in ihrer 42-dp-Haelfte durchaus mittig -- nur die
         * Haelfte nicht in der 48-dp-Zeile. Wer gegen das Blau misst, misst
         * den Fehler nicht.
         *
         * Schriftzeilen: zwischen den Rahmenlinien, im mittleren Band der
         * rechten Haelfte (die gerundeten Ecken am rechten Rand und der
         * Trennstrich bleiben draussen), alles, was weder Blau noch Schnee
         * ist -- also Schrift samt Kantenglaettung. */
        val abTrenner = trennspalten.last() + 1
        val halbBreite = breitePx - abTrenner
        val xVon = abTrenner + halbBreite / 5
        val xBis = breitePx - halbBreite / 5
        val schriftZeilen = (randZeilen.first() + 1 until randZeilen.last()).filter { y ->
            (xVon until xBis).any { x ->
                val p = bei(x, y)
                p != blauHell && p != schnee && (p ushr 24) != 0
            }
        }
        check(schriftZeilen.isNotEmpty()) { "Keine Schrift auf der gewählten Hälfte" }
        val schriftMitte = (schriftZeilen.first() + schriftZeilen.last()) / 2.0
        val rahmenMitte = (randZeilen.first() + randZeilen.last()) / 2.0
        val versatzDp = abs(schriftMitte - rahmenMitte) / dichte
        println(
            "ZWEIERWAHL: Schriftmitte %.1f px, Rahmenmitte %.1f px, Versatz %.2f dp (Soll höchstens 2,5 dp)"
                .format(schriftMitte, rahmenMitte, versatzDp)
        )
        check(versatzDp <= 2.5) { "Schrift nicht in der Mitte: %.2f dp Versatz".format(versatzDp) }
    }
}
