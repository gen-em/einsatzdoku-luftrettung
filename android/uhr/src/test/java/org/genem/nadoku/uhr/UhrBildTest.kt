package org.genem.nadoku.uhr

import android.graphics.Bitmap
import android.graphics.Canvas
import android.os.Looper
import android.view.View
import androidx.activity.ComponentActivity
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.ComposeView
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Ortungscode
import org.genem.nadoku.gemeinsam.Phasen
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.Robolectric
import org.robolectric.RobolectricTestRunner
import org.robolectric.RuntimeEnvironment
import org.robolectric.Shadows.shadowOf
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import java.io.File
import java.security.MessageDigest

private const val UhrBausteine_SOLL_DP = 48.0

/**
 * Die Flächenfarben der Knöpfe: Orange handelt, Rot beendet (E-S4-22a).
 * An ihnen erkennt die Messung ein Bedienelement im Bild.
 */
private val KNOPFFARBEN = setOf(0xFFFF8F1F.toInt(), 0xFFD63338.toInt())

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

    /**
     * Die Pruefsummen aller gezeichneten Bilder — Grundlage von [alleBilderSindVerschieden].
     *
     * Sie ist `companion`, weil JUnit je Prueffall ein neues Exemplar der
     * Klasse baut. Ein Feld am Exemplar saehe nach jedem Fall wieder leer aus.
     */
    private companion object {
        val pruefsummen = linkedMapOf<String, String>()
    }

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
        pruefsummen[name] = MessageDigest.getInstance("SHA-256")
            .digest(ziel.readBytes())
            .joinToString("") { "%02x".format(it) }

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

        /* DER BESCHNITT WIRD GERECHNET, NICHT GEMALT. Der Direktweg zeichnet
         * ein Quadrat — die runde Maske legt das Gerät an. Wo sie liegt, ist
         * aber bekannt: der einbeschriebene Kreis. Was außerhalb davon Farbe
         * trägt, sieht auf dem Glas niemand. */
        val drausen = ausserhalbDesGlases(punkte, kante, nurKnoepfe = false)
        val knopfDrausen = ausserhalbDesGlases(punkte, kante, nurKnoepfe = true)

        println(
            "BILD %s | %d Bytes | %dx%d | %d Farben | nicht-Grundfarbe %.2f %% | Knopf %.1f dp"
                .format(ziel.absolutePath, ziel.length(), kante, kante, zaehlung.size,
                        anteilFremd, hoeheDp) +
                " | ausserhalb des Glases %.2f %% (Knoepfe %.2f %%)".format(drausen, knopfDrausen)
        )
        check(ziel.length() > 0L) { "PNG ist leer" }
        check(zaehlung.size > 1) { "$name ist einfarbig — nichts gezeichnet" }

        /* KEIN BEDIENELEMENT DARF AUS DEM GLAS RAGEN.
         *
         * Die Zusicherung gilt den KNÖPFEN und nicht jedem Punkt — und das ist
         * kein Nachlassen, sondern die genauere Frage. Ein Stück Bildmarke am
         * Rand ist unschön; ein gekappter Knopf ist ein Bedienelement, das man
         * nicht trifft. Auf der 192-dp-Uhr passt die laufende Ansicht mit zwei
         * Knöpfen und Zustandszeile ohnehin nicht ohne Bildlauf (221 dp
         * Inhalt auf 192 dp) — dort auf jeden Punkt zu bestehen hieße, die
         * Ansicht auszudünnen, bis nichts mehr dasteht.
         *
         * Der Gesamtwert wird trotzdem gemeldet: Er ist die Zahl, die B-S4-08b
         * beziffert hat (13,55 % auf der Startseite, alles davon im Knopf). */
        check(knopfDrausen == 0.0) {
            ("%s: %.2f %% der Knopffläche liegen außerhalb des runden Glases " +
                "(B-S4-08b). Insgesamt außerhalb: %.2f %%.")
                .format(name, knopfDrausen, drausen)
        }
        return hoeheDp
    }

    /**
     * Wie viel Inhalt liegt **außerhalb des runden Glases**?
     *
     * Gezählt werden Punkte, die nicht die Grundfarbe tragen und weiter als
     * der halbe Kantenmaß vom Mittelpunkt entfernt sind. Auf einer runden Uhr
     * sind sie unsichtbar — der Rand schneidet sie ab, ohne dass es jemand
     * merkt. Genau so verschwand „löst am Handy aus", und genau so werden die
     * Ecken des großen Knopfes gekappt.
     *
     * @return Anteil in Prozent der Vordergrundpunkte
     */
    private fun ausserhalbDesGlases(punkte: IntArray, kante: Int, nurKnoepfe: Boolean): Double {
        val grund = punkte.toList().groupingBy { it }.eachCount().maxBy { it.value }.key
        val r = kante / 2.0
        var vorne = 0
        var drausen = 0
        for (y in 0 until kante) {
            for (x in 0 until kante) {
                val farbe = punkte[y * kante + x]
                if (if (nurKnoepfe) farbe !in KNOPFFARBEN else farbe == grund) continue
                vorne += 1
                val dx = x + 0.5 - r
                val dy = y + 0.5 - r
                if (dx * dx + dy * dy > r * r) drausen += 1
            }
        }
        return if (vorne == 0) 0.0 else 100.0 * drausen / vorne
    }

    /**
     * Die Höhe der orangenen Knopffläche in dp.
     *
     * Gezählt wird das höchste zusammenhängende Band von Zeilen, in denen
     * Markenorange vorkommt. Die Umrechnung ist der Bildmaßstab: Die Kante
     * trägt [kante] Punkte für 192 bzw. 227 dp.
     */
    private fun knopfhoeheDp(punkte: IntArray, kante: Int): Double {
        /* ZWEI KNOPFFARBEN, NICHT EINE. Der handelnde Knopf ist orange, der
         * beendende vollflächig rot (E-S4-22a) — „Einsatz abschließen" und
         * „Dienst beenden" tragen Rot. Die erste Fassung dieser Messung suchte
         * nur Orange und meldete für die laufende Ansicht 3,0 dp; das war kein
         * Fehler der Oberfläche, sondern des Prüfmittels. */
        val knopffarben = KNOPFFARBEN
        var beste = 0
        var laufend = 0
        for (y in 0 until kante) {
            val hat = (0 until kante).any { x -> punkte[y * kante + x] in knopffarben }
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

    /**
     * **Die laufende Ansicht mit dem längsten Text.**
     *
     * Nach der letzten Phase wird der große Knopf zu „Einsatz abschließen",
     * und darunter steht ein zweiter. Wenn der Beschnitt irgendwo zurückkehrt,
     * dann hier — nur die Startseite zu prüfen hieße, den engsten Fall
     * auszulassen.
     */
    @Test fun laufendeAnsichtMitLangemText() {
        val z = Uhrzustand(
            dienstLaeuft = true, einsatzLaeuft = true, laufendePhase = Phasen.LETZTE,
            laufendeSeit = "09:12", ansicht = Ansicht.LAUFEND,
        )
        val dp = male("uhr-laufend-192dp", 384) {
            UhrOberflaeche(logoWahl = LogoWahl.BODEN, anfang = z)
        }
        pruefeBedienhoehe("192 dp, laufend", dp)
    }

    /**
     * **Die Uhr sagt, wenn das Handy nichts aufzeichnet** (E-S5Z-15).
     *
     * Der Fall, für den F-S5Z-01 mit (c) entschieden wurde: Ein Dienst, der
     * an der Uhr begonnen wurde, während der Standort des Handys aus ist. Die
     * Uhr zeigt „keine Ortung · keine Aufzeichnung" — sonst stünde dort
     * „Dienst läuft" über einer Spur, die gar nicht entsteht.
     */
    @Test fun laufendOhneOrtung() {
        val z = Uhrzustand(
            dienstLaeuft = true, ansicht = Ansicht.LAUFEND,
            ortung = Ortungscode.STANDORT_AUS,
        )
        val dp = male("uhr-laufend-keine-ortung-192dp", 384) {
            UhrOberflaeche(logoWahl = LogoWahl.BODEN, anfang = z)
        }
        pruefeBedienhoehe("192 dp, ohne Ortung", dp)
    }

    /** Die leisere Stufe: Der Empfänger fängt sich noch ein. */
    @Test fun laufendGpsSucht() {
        val z = Uhrzustand(
            dienstLaeuft = true, ansicht = Ansicht.LAUFEND,
            ortung = Ortungscode.SUCHT,
        )
        val dp = male("uhr-laufend-gps-sucht-192dp", 384) {
            UhrOberflaeche(logoWahl = LogoWahl.BODEN, anfang = z)
        }
        pruefeBedienhoehe("192 dp, GPS sucht", dp)
    }

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
    /**
     * **Keine zwei Bilder sind gleich** (F-P3-AQ).
     *
     * Der Bilderlauf des Web meldete nach O9c „248 Bilder, 0 Überlauf" — 176
     * davon zeigten die Anmeldeseite. Hier ist derselbe Fehler schon einmal
     * von Hand gefunden worden: `sperreAn = false` malte byteweise dasselbe
     * wie die Bodenmarke und wurde gestrichen. Diese Zeile sorgt dafür, dass
     * es beim nächsten Mal auffällt, statt gelesen werden zu müssen.
     *
     * Der Fall läuft **zuletzt**: Er braucht die Bilder der anderen. JUnit
     * ordnet Prüffälle nach einem festen Hash, nicht nach Quelltext — deshalb
     * malt er selbst, statt sich auf eine Reihenfolge zu verlassen.
     */
    @Test fun alleBilderSindVerschieden() {
        bodenmarke()
        luftmarke()
        laufendeAnsichtMitLangemText()
        laufendOhneOrtung()
        laufendGpsSucht()
        groessereUhr()

        val doppelt = pruefsummen.entries
            .groupBy { it.value }
            .filter { it.value.size > 1 }
        check(doppelt.isEmpty()) {
            "Gleiche Bilder unter verschiedenem Namen (F-P3-AQ): " +
                doppelt.values.joinToString(" · ") { g -> g.joinToString(" = ") { it.key } }
        }
        println(
            "BILDERLAUF UHR — ${pruefsummen.size} Bilder, alle paarweise verschieden: " +
                pruefsummen.keys.joinToString(", ")
        )
    }

    private fun pruefeBedienhoehe(uhr: String, gemessen: Double) {
        check(gemessen >= UhrBausteine_SOLL_DP - 1.0) {
            "Bedienhöhe auf $uhr: %.1f dp statt %.0f dp (E-S4-41). Die Spalte staucht den Knopf."
                .format(gemessen, UhrBausteine_SOLL_DP)
        }
    }
}
