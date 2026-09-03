package org.genem.nadoku.handy.aufzeichnung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Die Ausdünnung gegen die Soll-Zahlen aus `werkzeuge/stroeme.py`.
 *
 * DIE ZAHLEN STEHEN NICHT HIER, sondern in
 * `src/test/resources/stroeme.txt` — erzeugt von einer **unabhängigen**
 * Umsetzung derselben Regel (der Portierung aus
 * `tools/referenzdatensatz/generator/spur.py`, die ihrerseits aus
 * `watch/source/Track.mc` stammt). Eine Zahl, die aus derselben Umsetzung
 * stammt, die sie belegen soll, belegt nichts.
 */
class AusduennerTest {

    private data class Soll(
        val name: String,
        val rohpunkte: Int,
        val behalten: Int,
        val streckeM: Double,
        val anstiegM: Double,
        val schwellenabstandM: Double,
    )

    private val soll: Map<String, Soll> by lazy {
        val text = checkNotNull(
            javaClass.classLoader?.getResourceAsStream("stroeme.txt")
        ) { "stroeme.txt fehlt — werkzeuge/stroeme.py laufen lassen" }
            .bufferedReader().readText()

        text.lineSequence()
            .filter { it.isNotBlank() && !it.startsWith("#") }
            .map { zeile ->
                val f = zeile.split(";")
                Soll(f[0], f[1].toInt(), f[2].toInt(), f[3].toDouble(), f[4].toDouble(), f[5].toDouble())
            }
            .associateBy { it.name }
    }

    private fun durchlauf(name: String): Triple<Int, Double, Double> {
        val strom = Stroeme.erzeuge(checkNotNull(Stroeme.NACH_NAME[name]))
        val a = Ausduenner()
        var behalten = 0
        for (p in strom) if (a.nimm(p)) behalten++
        return Triple(behalten, a.streckeM, a.anstiegM)
    }

    private fun pruefe(name: String) {
        val s = checkNotNull(soll[name]) { "Kein Sollwert für $name" }
        val strom = Stroeme.erzeuge(checkNotNull(Stroeme.NACH_NAME[name]))

        assertEquals("Rohpunkte $name — die beiden Erzeuger sind auseinandergelaufen",
            s.rohpunkte, strom.size)

        val (behalten, strecke, anstieg) = durchlauf(name)
        println("$name: ${strom.size} roh -> $behalten behalten, " +
            "%.1f m Strecke, %.1f m Anstieg (Soll: ${s.behalten}, %.1f, %.1f)"
                .format(strecke, anstieg, s.streckeM, s.anstiegM))

        assertEquals("Behaltene Punkte $name", s.behalten, behalten)
        // Millimeter: Die beiden Haversine-Umsetzungen rechnen mit derselben
        // Formel, aber nicht zwangsläufig mit demselben letzten Bit.
        assertEquals("Strecke $name", s.streckeM, strecke, 0.001)
        assertEquals("Anstieg $name", s.anstiegM, anstieg, 0.001)
    }

    @Test fun reiseflug() = pruefe("reiseflug")
    @Test fun anfahrtBoden() = pruefe("anfahrt_boden")
    @Test fun standEinsatzort() = pruefe("stand_einsatzort")
    @Test fun stadtfahrt() = pruefe("stadtfahrt")

    /**
     * Der 12-h-Dienst — die Abnahmefrage „Größenordnung der
     * Referenz-Diensttage" (Konzept, B3).
     *
     * Der Referenzdatensatz trägt **56 587 Punkte auf 16 Diensten**, also rund
     * **3 537 je Diensttag**. Dieser Strom ergibt spürbar mehr, und das ist
     * **richtig so**: Der Referenzgenerator tastet bewusst gröber ab (3 s in
     * der Luft, 5 s am Boden, 30 s im Halt, **60 s im Ruhesegment** —
     * `spur.py` nennt als Grund die Größe der Fixture, die bei jedem Deploy
     * hochgeladen wird). Das Handy wertet die Regel wie die Uhr **jede
     * Sekunde** aus und hält im Stand einen Punkt alle 10 s — allein im
     * Ruhesegment das Sechsfache.
     *
     * Geprüft wird deshalb die **Größenordnung**, nicht die Gleichheit: Der
     * Wert muss zwischen dem Einfachen und dem Vierfachen des Referenzmittels
     * liegen. Alles darunter hieße, dass Punkte verlorengehen; alles darüber,
     * dass die Ausdünnung nicht greift.
     */
    @Test fun zwoelfStundenDienst() {
        pruefe("dienst12h")

        val (behalten, _, _) = durchlauf("dienst12h")
        val referenzJeDienst = 56_587.0 / 16.0        // messprotokoll.json
        val verhaeltnis = behalten / referenzJeDienst
        println("12-h-Dienst: $behalten Punkte, Referenzmittel %.0f, Verhältnis %.2fx"
            .format(referenzJeDienst, verhaeltnis))

        assertTrue("Zu wenige Punkte ($behalten) — geht etwas verloren?", verhaeltnis >= 1.0)
        assertTrue("Zu viele Punkte ($behalten) — greift die Ausdünnung?", verhaeltnis <= 4.0)
    }

    // ---- Die Regel selbst, in ihren Einzelteilen ---------------------------

    @Test fun derErstePunktWirdImmerGenommen() {
        val a = Ausduenner()
        assertTrue(a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000)))
    }

    @Test fun nieOefterAlsEinmalJeSekunde() {
        val a = Ausduenner()
        a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000))
        // 1 km weiter, aber in derselben Sekunde: trotzdem nicht.
        assertFalse(a.nimm(Rohpunkt(47.7351, 10.3186, 712.0, 1000)))
    }

    @Test fun abFuenfzehnMeternWirdGenommen() {
        val a = Ausduenner()
        a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000))
        // 14 m nach Norden: zu wenig, und 1 s ist zu kurz.
        assertFalse(a.nimm(Rohpunkt(47.7261 + 14.0 / 111_320.0, 10.3186, 712.0, 1001)))
        // 16 m: reicht.
        assertTrue(a.nimm(Rohpunkt(47.7261 + 16.0 / 111_320.0, 10.3186, 712.0, 1002)))
    }

    @Test fun abZehnSekundenWirdAuchOhneBewegungGenommen() {
        val a = Ausduenner()
        val p = Rohpunkt(47.7261, 10.3186, 712.0, 1000)
        a.nimm(p)
        assertFalse(a.nimm(p.copy(zeit = 1009)))
        assertTrue(a.nimm(p.copy(zeit = 1010)))
    }

    /**
     * Verglichen wird gegen den zuletzt ÜBERNOMMENEN Punkt, nicht gegen den
     * zuletzt gesehenen. Sonst entstünde nach jeder 10-s-Lücke ein Punkt bei
     * jeder folgenden Messung.
     */
    @Test fun derVergleichGehtGegenDenLetztenUebernommenen() {
        val a = Ausduenner()
        val p = Rohpunkt(47.7261, 10.3186, 712.0, 1000)
        a.nimm(p)
        repeat(9) { i -> assertFalse(a.nimm(p.copy(zeit = 1001L + i))) }
        assertTrue(a.nimm(p.copy(zeit = 1010)))
        // Direkt danach wieder von vorn: die nächsten neun sind wieder nichts.
        repeat(9) { i -> assertFalse(a.nimm(p.copy(zeit = 1011L + i))) }
        assertTrue(a.nimm(p.copy(zeit = 1020)))
    }

    @Test fun grobUngenauePunkteWerdenVerworfen() {
        val a = Ausduenner()
        assertFalse(
            "Ein Fund mit 500 m Streuung ist kein GPS-Fund",
            a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000, genauigkeitM = 500f)),
        )
        assertTrue(a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000, genauigkeitM = 8f)))
    }

    @Test fun ohneGenauigkeitsangabeWirdGenommen() {
        val a = Ausduenner()
        assertTrue(a.nimm(Rohpunkt(47.7261, 10.3186, 712.0, 1000, genauigkeitM = null)))
    }

    /** Anstieg zählt nur aufwärts — sonst wäre er die Summe der Höhenwechsel. */
    @Test fun anstiegZaehltNurAufwaerts() {
        val a = Ausduenner()
        a.nimm(Rohpunkt(47.7261, 10.3186, 700.0, 1000))
        a.nimm(Rohpunkt(47.7261, 10.3186, 750.0, 1010))
        a.nimm(Rohpunkt(47.7261, 10.3186, 600.0, 1020))
        a.nimm(Rohpunkt(47.7261, 10.3186, 620.0, 1030))
        assertEquals(70.0, a.anstiegM, 0.001)
    }

    // ---- brauchbar(): dieselbe Schwelle fuer Aufzeichnung und Anzeige ----
    //
    // Sie steht seit E1 als eigene Funktion da, weil der Ortungswaechter sie
    // ebenfalls braucht (E-S5Z-02). Diese Faelle belegen die GRENZE selbst --
    // 100 m ist die Zahl im Code, und ein "> " statt "≥" verschoebe sie um
    // einen Meter, ohne dass ein Stromfall das merkte.

    @Test fun neunundneunzigMeterSindNochBrauchbar() {
        assertTrue(Ausduenner.brauchbar(punktMit(99f)))
    }

    @Test fun genauHundertMeterSindNochBrauchbar() {
        assertTrue(Ausduenner.brauchbar(punktMit(100f)))
    }

    @Test fun einhunderteinsIstNichtMehrBrauchbar() {
        assertFalse(Ausduenner.brauchbar(punktMit(101f)))
    }

    /** Kein Wert ist kein Grund zum Wegwerfen -- das waere stiller Verlust. */
    @Test fun ohneAngabeGiltDerFundAlsBrauchbar() {
        assertTrue(Ausduenner.brauchbar(punktMit(null)))
    }

    /** Und `nimm()` benutzt genau diese Regel, statt eine zweite zu fuehren. */
    @Test fun nimmUndBrauchbarSindSichEinig() {
        for (g in listOf(null, 0f, 8f, 99f, 100f, 101f, 500f)) {
            val punkt = punktMit(g)
            assertEquals(
                "Streuung $g",
                Ausduenner.brauchbar(punkt),
                Ausduenner().nimm(punkt),
            )
        }
    }

    private fun punktMit(genauigkeitM: Float?) =
        Rohpunkt(47.7261, 10.3186, 712.0, 1000, genauigkeitM = genauigkeitM)

    @Test fun haversineStimmtMitDerReferenzUeberein() {
        // 1 Grad Breite am Äquator: rund 111,19 km (2 pi R / 360).
        assertEquals(111_194.9, Ausduenner.abstandM(0.0, 0.0, 1.0, 0.0), 1.0)
        assertEquals(0.0, Ausduenner.abstandM(47.7261, 10.3186, 47.7261, 10.3186), 1e-9)
    }
}
