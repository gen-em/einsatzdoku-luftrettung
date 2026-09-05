package org.genem.nadoku.handy

import android.content.Context
import android.graphics.Bitmap
import android.graphics.Canvas
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.R
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import kotlin.math.hypot

/**
 * Das adaptive Symbol, gezeichnet in zwei Größen — die Nachrechnung zu
 * Backlog Nr. 81, diesmal mit der richtigen Kachelgröße.
 *
 * WAS DER FEHLER WAR. Der Vordergrund (`symbol_vordergrund.xml`) stand bis
 * 0.13.0 mit **festen 52 × 33 dp** in der 108-dp-Kachel. Das stimmt, wenn die
 * Kachel 108 dp groß gezeichnet wird — und das tut niemand: Ein
 * Startprogramm zeichnet sie mit 48 bis 60 dp, der Kopf einer Benachrichtigung
 * mit rund 40. Die Ebene bekommt dann das Anderthalbfache davon als Fläche,
 * und ein festes 52-dp-Motiv füllt sie oder ragt über den sichtbaren Kreis
 * hinaus. Die Nachrechnung vom 02.09.2026 rechnete mit 108 dp und fand
 * deshalb nichts.
 *
 * WAS DIESER FALL MISST: den **Anteil**, den der Vordergrund an der Kachel
 * einnimmt — bei 40 dp und bei 108 dp. Mit Bruchteilen ist er bei beiden
 * derselbe; mit festen dp wäre er bei 40 dp mehr als doppelt so groß wie bei
 * 108. Dazu: Kein Vordergrundpixel liegt außerhalb des sicheren Kreises
 * (66 von 108 Teilen der Ebene).
 *
 * Erwartete Breite: 52 von 108 Teilen der Ebene, und die Ebene ist
 * anderthalbmal so groß wie die Kachel — also 0,4815 × 1,5 = **72 %** der
 * Kachel, bei jeder Größe.
 *
 * GRENZE: Robolectric maskiert wie AOSP (Kreis). Welche Form ein Hersteller
 * nimmt, sieht man nur dort — der Anteil ist davon unabhängig.
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "xhdpi")
class SymbolBildTest {

    /** 52 von 108 Teilen der Ebene, die Ebene ist 1,5 Kacheln breit. */
    private val sollBreite = 52.0 / 108.0 * 1.5

    /** Der sichere Kreis: 66 von 108 Teilen der Ebene, als Radius in Kacheln. */
    private val sicherRadius = 66.0 / 108.0 * 1.5 / 2.0

    @Test
    fun derVordergrundNimmtBeiJederGroesseDenselbenAnteilEin() {
        val kontext = ApplicationProvider.getApplicationContext<Context>()
        val dichte = kontext.resources.displayMetrics.density
        val hintergrund = kontext.getColor(R.color.marke_dunkelblau)

        val anteile = mutableMapOf<Int, Double>()
        for (kachelDp in listOf(40, 108)) {
            val px = (kachelDp * dichte).toInt()
            val symbol = checkNotNull(kontext.getDrawable(R.mipmap.symbol)) { "Symbol nicht ladbar" }
            symbol.setBounds(0, 0, px, px)
            val bild = Bitmap.createBitmap(px, px, Bitmap.Config.ARGB_8888)
            symbol.draw(Canvas(bild))
            val punkte = IntArray(px * px)
            bild.getPixels(punkte, 0, px, 0, 0, px, px)

            /* Die haeufigsten Farben, damit eine Fehlmessung erklaerbar ist
             * und nicht nur falsch. */
            val haeufig = punkte.toList().groupingBy { it }.eachCount()
                .entries.sortedByDescending { it.value }.take(4)
            println(
                "SYMBOL %3d dp: %d px, häufigste Farben ".format(kachelDp, px) +
                    haeufig.joinToString { "#%08X %.1f %%".format(it.key, it.value * 100.0 / punkte.size) }
            )

            /* Vordergrund = alles, was weder durchsichtig (ausserhalb der
             * Maske) noch Hintergrundfarbe ist. Kantenglaettung am Motivrand
             * zaehlt mit -- sie liegt innerhalb eines Pixels. Der Vergleich
             * mit dem Hintergrund ist tolerant (je Kanal bis 3): Die Ebenen
             * gehen durch eine Bitmap und einen Shader, und ein Rundungsfehler
             * im letzten Bit darf aus Dunkelblau keinen Vordergrund machen. */
            fun hintergrundnah(p: Int): Boolean = (0..2).all { k ->
                kotlin.math.abs(((p shr (k * 8)) and 0xFF) - ((hintergrund shr (k * 8)) and 0xFF)) <= 3
            }
            /* Der Rand, zur Erklaerung einer Fehlmessung: Was steht in den
             * aeussersten Pixeln, und wie viele sind nicht voll deckend? */
            val halbdurchsichtig = punkte.count { (it ushr 24) in 1..254 }
            println(
                "SYMBOL %3d dp: Rand (0,0)=#%08X (0,%d)=#%08X (%d,0)=#%08X Mitte=#%08X; %d Pixel halbdurchsichtig"
                    .format(kachelDp, punkte[0], px / 2, punkte[(px / 2) * px], px / 2, punkte[px / 2],
                            punkte[(px / 2) * px + px / 2], halbdurchsichtig)
            )

            var minX = px; var maxX = -1; var minY = px; var maxY = -1
            var weitester = 0.0
            val mitte = (px - 1) / 2.0
            for (y in 0 until px) for (x in 0 until px) {
                val p = punkte[y * px + x]
                /* NUR VOLL DECKENDE PIXEL zaehlen: Am Rand der Maske entstehen
                 * halbdurchsichtige, und deren Farbe ist nach dem Vormultiplizieren
                 * weder Hintergrund noch Motiv. Das Motiv selbst ist deckend. */
                if ((p ushr 24) != 0xFF || hintergrundnah(p)) continue
                if (x < minX) minX = x
                if (x > maxX) maxX = x
                if (y < minY) minY = y
                if (y > maxY) maxY = y
                val r = hypot(x - mitte, y - mitte)
                if (r > weitester) weitester = r
            }
            check(maxX >= 0) { "$kachelDp dp: kein Vordergrund gezeichnet" }

            val breite = (maxX - minX + 1) / px.toDouble()
            val hoehe = (maxY - minY + 1) / px.toDouble()
            val radius = weitester / px
            anteile[kachelDp] = breite
            println(
                "SYMBOL %3d dp: Vordergrund %.1f %% breit, %.1f %% hoch, weitester Punkt %.3f Kacheln von der Mitte (sicher bis %.3f)"
                    .format(kachelDp, breite * 100, hoehe * 100, radius, sicherRadius)
            )

            /* ± 2 Pixel Toleranz fuer Rundung und Kantenglaettung, auf die
             * Kachel bezogen -- bei 80 px sind das 2,5 %. */
            val toleranz = 2.0 / px
            check(breite in (sollBreite - toleranz)..(sollBreite + toleranz)) {
                "$kachelDp dp: Vordergrund %.1f %% breit statt %.1f %%".format(breite * 100, sollBreite * 100)
            }
            check(radius <= sicherRadius) {
                "$kachelDp dp: Vordergrund ragt aus dem sicheren Kreis (%.3f > %.3f)".format(radius, sicherRadius)
            }
        }

        /* Die eigentliche Zusicherung: Der Anteil haengt nicht von der
         * Groesse ab. Mit den festen 52 dp von 0.13.0 waere er bei 40 dp
         * 130 % gewesen (52 dp Motiv in 40 dp Kachel) und bei 108 dp 48 %. */
        val unterschied = kotlin.math.abs(anteile.getValue(40) - anteile.getValue(108))
        check(unterschied <= 0.03) {
            "Der Vordergrund skaliert nicht mit: %.1f %% bei 40 dp, %.1f %% bei 108 dp"
                .format(anteile.getValue(40) * 100, anteile.getValue(108) * 100)
        }
    }
}
