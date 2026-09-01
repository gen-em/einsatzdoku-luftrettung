package org.genem.nadoku.uhr

import android.graphics.Bitmap
import android.graphics.Canvas
import android.os.Looper
import android.view.View
import androidx.activity.ComponentActivity
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.ComposeView
import org.genem.nadoku.gemeinsam.LogoWahl
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.Robolectric
import org.robolectric.RobolectricTestRunner
import org.robolectric.RuntimeEnvironment
import org.robolectric.Shadows.shadowOf
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import java.io.File

private const val UhrBausteine_SOLL_DP = 48.0

/**
 * Bilder der Uhr-Ansicht OHNE Emulator und ohne Gerät (E-S4-49).
 *
 * WARUM NICHT `captureToImage()`. Der naheliegende Weg über
 * compose-ui-test scheitert unter Robolectric mit
 * `ComposeTimeoutException: Condition still not satisfied after 2000 ms`
 * in `WindowCapture.forceRedraw`: Dort wird ein `OnDrawListener` angehängt,
 * `invalidate()` gerufen und danach in einer Schleife aus `Thread.sleep(10)`
 * gewartet. Robolectric hat aber nur EINEN Faden — der schlafende Prüfstand
 * ist genau der Faden, der den Zeichendurchgang ausführen müsste. Der
 * Rückruf kann nicht kommen. `waitUntil` schiebt zwar `advanceTimeByFrame()`
 * nach, das rührt aber nur den Coroutine-Zeitgeber an, nicht die
 * Nachrichtenschleife.
 *
 * Hier wird deshalb selbst gemessen, angeordnet und auf eine Bitmap
 * gezeichnet. Das braucht KEINE zusätzliche Abhängigkeit: `ComposeView`
 * steckt in `androidx.compose.ui`, das die App ohnehin einbindet.
 *
 * GRENZEN. Gezeichnet wird ein QUADRAT — die runde Maske des Uhrglases legt
 * das Gerät an, nicht die Ansicht. Was hier zu sehen ist, ist die
 * Anordnung, nicht der Beschnitt.
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w192dp-h192dp-round-xhdpi")
class UhrBildTest {

    private fun male(name: String, kante: Int, inhalt: @Composable () -> Unit): Double {
        val steuerung = Robolectric.buildActivity(ComponentActivity::class.java).setup()
        val activity = steuerung.get()

        val ansicht = ComposeView(activity)
        activity.setContentView(ansicht)
        ansicht.setContent(inhalt)
        shadowOf(Looper.getMainLooper()).idle()

        val fest = View.MeasureSpec.makeMeasureSpec(kante, View.MeasureSpec.EXACTLY)
        ansicht.measure(fest, fest)
        ansicht.layout(0, 0, kante, kante)
        shadowOf(Looper.getMainLooper()).idle()

        val bild = Bitmap.createBitmap(kante, kante, Bitmap.Config.ARGB_8888)
        ansicht.draw(Canvas(bild))

        val ordner = File("build/bilder")
        ordner.mkdirs()
        val ziel = File(ordner, "$name.png")
        ziel.outputStream().use { bild.compress(Bitmap.CompressFormat.PNG, 100, it) }

        /* GEGENPROBE IM FALL SELBST. Ein einfarbiges Rechteck wäre ein
         * gescheiterter Versuch, kein Bild. Gezählt wird, wie viele Punkte
         * NICHT die häufigste Farbe tragen. */
        val punkte = IntArray(kante * kante)
        bild.getPixels(punkte, 0, kante, 0, 0, kante, kante)
        val zaehlung = punkte.toList().groupingBy { it }.eachCount()
        val haeufigste = zaehlung.maxOf { it.value }
        val anteilFremd = 100.0 * (punkte.size - haeufigste) / punkte.size

        /* DIE KNOPFHÖHE WIRD NACHGEMESSEN, nicht angesehen. Sie ist die eine
         * Zahl, die E-S4-41 zusagt (48 dp), und genau sie war falsch: Auf der
         * kleinen Uhr staucht die Spalte den Knopf auf 35,5 dp, weil
         * `heightIn(min = …)` sich der Elternbeschränkung beugt. Ein Bild
         * allein hätte das nicht gezeigt — es sah gut aus. */
        val hoeheDp = knopfhoeheDp(punkte, kante)

        println(
            "BILD %s | %d Bytes | %dx%d | %d Farben | nicht-Grundfarbe %.2f %% | Knopf %.1f dp"
                .format(ziel.absolutePath, ziel.length(), kante, kante, zaehlung.size,
                        anteilFremd, hoeheDp)
        )
        check(ziel.length() > 0L) { "PNG ist leer" }
        check(zaehlung.size > 1) { "$name ist einfarbig — nichts gezeichnet" }
        return hoeheDp
    }

    /**
     * Die Höhe der orangenen Knopffläche in dp.
     *
     * Gezählt wird das höchste zusammenhängende Band von Zeilen, in denen
     * Markenorange vorkommt. Die Umrechnung ist der Bildmaßstab: Die Kante
     * trägt [kante] Punkte für 192 bzw. 227 dp.
     */
    private fun knopfhoeheDp(punkte: IntArray, kante: Int): Double {
        val orange = 0xFFFF8F1F.toInt()
        var beste = 0
        var laufend = 0
        for (y in 0 until kante) {
            val hat = (0 until kante).any { x -> punkte[y * kante + x] == orange }
            if (hat) { laufend += 1; if (laufend > beste) beste = laufend } else laufend = 0
        }
        val dpJePunkt = (if (kante == 384) 192.0 else 227.0) / kante
        return beste * dpJePunkt
    }

    @Test fun bodenmarke() {
        val dp = male("uhr-boden-192dp", 384) {
            UhrOberflaeche(app = null, logoWahl = LogoWahl.BODEN)
        }
        pruefeBedienhoehe("192 dp", dp)
    }

    @Test fun luftmarke() {
        male("uhr-luft-192dp", 384) { UhrOberflaeche(app = null, logoWahl = LogoWahl.LUFT) }
    }

    /* `sperreAn = false` stand hier einmal als eigener Fall. Er ist ersatzlos
     * gestrichen: Sein Bild war BYTEGLEICH mit dem der Bodenmarke, weil die
     * Sperre nur im laufenden Dienst greift — auf der Startseite gibt es
     * nichts zu sperren. Ein Prüffall, der zweimal dasselbe malt, ist kein
     * zweiter Beleg, sondern eine zweite Datei. */

    /** Galaxy Watch, 227 dp Rundbild — dieselbe Ansicht, andere Kante. */
    @Test fun groessereUhr() {
        RuntimeEnvironment.setQualifiers("w227dp-h227dp-round-xhdpi")
        val dp = male("uhr-boden-227dp", 454) {
            UhrOberflaeche(app = null, logoWahl = LogoWahl.BODEN)
        }
        pruefeBedienhoehe("227 dp", dp)
    }

    /**
     * **Der Knopf hält die zugesagten 48 dp** (E-S4-41) — auf jeder Uhrgröße.
     *
     * Die Zusicherung steht getrennt, weil sie das ist, worum es geht: Das
     * Bild ist der Beleg, die Zahl ist die Prüfung. Toleranz 1 dp für die
     * Rasterung auf ganze Bildpunkte.
     */
    private fun pruefeBedienhoehe(uhr: String, gemessen: Double) {
        check(gemessen >= UhrBausteine_SOLL_DP - 1.0) {
            "Bedienhöhe auf $uhr: %.1f dp statt %.0f dp (E-S4-41). Die Spalte staucht den Knopf."
                .format(gemessen, UhrBausteine_SOLL_DP)
        }
    }
}
