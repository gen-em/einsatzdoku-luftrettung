package org.genem.nadoku.handy.aufzeichnung

import kotlin.math.asin
import kotlin.math.cos
import kotlin.math.min
import kotlin.math.sin
import kotlin.math.sqrt

/** Ein roher Positionsfund, wie ihn die Ortung liefert. */
data class Rohpunkt(
    val breite: Double,
    val laenge: Double,
    val hoehe: Double?,
    /** Unix-Epoche in Sekunden — das Format des Vertrags für Spurpunkte. */
    val zeit: Long,
    /** Geschätzter waagerechter Fehler in Metern; `null` = unbekannt. */
    val genauigkeitM: Float? = null,
)

/**
 * Die Ausdünnung — **wortgleich die der Garmin-Uhr** (`Track.mc`, E-S4-05).
 *
 * Ein Punkt wird übernommen, wenn seit dem letzten **übernommenen**
 * mindestens 15 m zurückgelegt wurden **oder** mindestens 10 s vergangen
 * sind, und nie öfter als einmal je Sekunde.
 *
 * WARUM NICHT EINE EIGENE, BESSERE REGEL. Weil zwei Messungen an dieser Zahl
 * hängen: die R19-Messung des Sendeverhaltens (Spitze 14 Anfragen an einem
 * Auslöser, Median 1 020 s Abstand) und der Messstand aus S2. Ein Client mit
 * eigener Abtastidee machte beide wertlos — und die Mengenbremse, die aus
 * ihnen entsteht (P5/R19), träfe die beiden Clients dann verschieden.
 *
 * DER VERGLEICH GEHT GEGEN DEN LETZTEN ÜBERNOMMENEN PUNKT, nicht gegen den
 * letzten gesehenen. Das ist der Unterschied zwischen „alle 10 s ein Punkt im
 * Stand" und „bei jeder Messung ein Punkt, sobald einmal 10 s vergangen
 * waren". `Track.mc` schreibt `_lastTs` nur beim Übernehmen fort; hier
 * ebenso, und `tools/referenzdatensatz/generator/spur.py` tut es auch — die
 * drei Umsetzungen sind gegeneinander nachgerechnet.
 *
 * STRECKE UND ANSTIEG entstehen wie auf der Uhr: Summe der Haversine-Abstände
 * zwischen den **aufgezeichneten** Punkten, Summe der positiven
 * Höhendifferenzen zwischen ihnen. Nicht aus der Weglänge eines Modells und
 * nicht aus jedem gesehenen Messwert — sonst zählte das GPS-Rauschen im
 * Stand als gefahrene Strecke.
 */
class Ausduenner {

    private var letzte: Rohpunkt? = null

    /** Aufgelaufene Strecke seit dem Zurücksetzen, in Metern. */
    var streckeM: Double = 0.0
        private set

    /** Aufgelaufener Anstieg seit dem Zurücksetzen, in Metern. */
    var anstiegM: Double = 0.0
        private set

    /** Der zuletzt übernommene Punkt — für die Phasen-Koordinate (B5). */
    val letzterPunkt: Rohpunkt? get() = letzte

    /**
     * @return `true`, wenn der Punkt übernommen wird. Nur dann gehört er in
     *   den Puffer.
     */
    fun nimm(punkt: Rohpunkt): Boolean {
        /* ERST DIE GENAUIGKEIT. Die Uhr verwirft alles unterhalb von
         * `QUALITY_POOR`; auf Android gibt es keine Stufen, sondern einen
         * geschätzten Fehler in Metern. 100 m ist bewusst großzügig gewählt:
         * Bei dieser Streuung ist der Fund kein GPS-Fund mehr, sondern aus
         * Funkzelle oder WLAN abgeleitet, und er läge weit jenseits der 15 m,
         * um die es bei der Ausdünnung geht. Ein strengerer Wert würfe im
         * Wald oder in der Klinikeinfahrt echte Punkte weg.
         *
         * DIE ZAHL IST BLIND GEWÄHLT und gehört auf die Prüfliste des
         * Gerätetests: Nur ein Dienst auf dem S24 zeigt, wie oft sie greift. */
        val g = punkt.genauigkeitM
        if (g != null && g > HOECHSTE_STREUUNG_M) return false

        val vorher = letzte
        if (vorher == null) {
            uebernimm(punkt)
            return true
        }

        val dt = punkt.zeit - vorher.zeit
        if (dt < MINDESTABSTAND_S) return false          // nie öfter als 1/s

        val d = abstandM(vorher.breite, vorher.laenge, punkt.breite, punkt.laenge)
        if (d < MINDESTSTRECKE_M && dt < HOECHSTABSTAND_S) return false

        streckeM += d
        val hVor = vorher.hoehe
        val hNeu = punkt.hoehe
        if (hVor != null && hNeu != null && hNeu > hVor) anstiegM += (hNeu - hVor)

        uebernimm(punkt)
        return true
    }

    private fun uebernimm(punkt: Rohpunkt) {
        letzte = punkt
    }

    /** Für den nächsten Einsatz bzw. das nächste Segment. */
    fun zuruecksetzen(kennzahlenAuch: Boolean = true) {
        letzte = null
        if (kennzahlenAuch) {
            streckeM = 0.0
            anstiegM = 0.0
        }
    }

    /**
     * Nur die Kennzahlen zurücksetzen, den letzten Punkt behalten.
     *
     * Der Fall ist der Übergang Ruhesegment → Einsatz: Die Ausdünnung soll
     * **nicht** neu anfangen (sonst entstünde direkt nach dem Schnitt ein
     * zweiter Punkt am selben Ort), Strecke und Anstieg des Einsatzes aber
     * schon (sie gehören zu ihm, nicht zum Dienst).
     */
    fun kennzahlenZuruecksetzen() {
        streckeM = 0.0
        anstiegM = 0.0
    }

    companion object {
        /** `Const.THIN_MIN_DIST_M` der Uhr. */
        const val MINDESTSTRECKE_M = 15.0

        /** `Const.THIN_MAX_GAP_S` der Uhr. */
        const val HOECHSTABSTAND_S = 10L

        /** `Const.THIN_MIN_GAP_S` der Uhr. */
        const val MINDESTABSTAND_S = 1L

        /** Blind gewählt, am Gerät nachzumessen (E-R45-7). */
        const val HOECHSTE_STREUUNG_M = 100f

        private const val ERDRADIUS_M = 6_371_000.0

        /**
         * Haversine — **dieselbe Formel** wie `Track._haversine()` der Uhr und
         * `wegpunkte.abstand_m()` des Referenzgenerators, mit demselben
         * Erdradius. Zwei Formeln für denselben Abstand liefen früher oder
         * später auseinander.
         */
        fun abstandM(breite1: Double, laenge1: Double, breite2: Double, laenge2: Double): Double {
            val p1 = Math.toRadians(breite1)
            val p2 = Math.toRadians(breite2)
            val dp = Math.toRadians(breite2 - breite1)
            val dl = Math.toRadians(laenge2 - laenge1)
            val h = sin(dp / 2) * sin(dp / 2) + cos(p1) * cos(p2) * sin(dl / 2) * sin(dl / 2)
            return 2.0 * ERDRADIUS_M * asin(min(1.0, sqrt(h)))
        }
    }
}
