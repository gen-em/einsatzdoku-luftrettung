package org.genem.nadoku.handy.aufzeichnung

/**
 * Dieselben Positionsströme wie `android/werkzeuge/stroeme.py` — **noch
 * einmal, unabhängig**.
 *
 * WARUM NICHT EINFACH DIE PUNKTE AUS EINER DATEI LESEN. Weil dann beide
 * Seiten dieselbe Eingabe sähen und nur die Ausdünnung verglichen würde. So
 * erzeugen zwei Umsetzungen in zwei Sprachen denselben Strom; weichen sie ab,
 * weichen auch die Punktzahlen ab, und der Prüffall meldet es. Verglichen
 * werden am Ende **drei** unabhängige Wege: analytisch (im Kopf, in
 * `stroeme.py` als Sollwert notiert), die Referenzregel aus
 * `tools/referenzdatensatz/generator/spur.py` und diese App.
 *
 * KEIN ZUFALL, KEINE TRIGONOMETRIE IM ERZEUGER. Im Erzeuger stehen nur +, −,
 * × und ÷ — Operationen, die in Python und Kotlin bitgleich rechnen. Der
 * Faktor für den Längengrad ist deshalb eine feste Dezimalzahl und kein
 * `cos()`-Aufruf: Zwei Näherungen desselben Kosinus wären die eine Stelle, an
 * der die beiden Ströme auseinanderlaufen könnten.
 */
object Stroeme {

    const val GRAD_BREITE_M = 111_320.0

    /** cos(47,7°) — der Breitengrad des Referenzdatensatzes. */
    const val COS_47_7 = 0.672367

    /** 1/√2 — der Anteil je Achse bei 45 Grad Kurs. */
    const val JE_ACHSE = 0.7071067811865476

    const val START_BREITE = 47.7261
    const val START_LAENGE = 10.3186
    const val START_HOEHE = 712.0

    /** Fester Zeitpunkt, kein „jetzt": Ein Prüffall darf nicht vom Kalender abhängen. */
    const val START_ZEIT = 1_784_279_400L

    /** @param dauerS Sekunden, [tempoMs] m/s, [steigenMs] m/s Höhenänderung. */
    data class Abschnitt(val dauerS: Int, val tempoMs: Double, val steigenMs: Double)

    fun erzeuge(abschnitte: List<Abschnitt>): List<Rohpunkt> {
        var breite = START_BREITE
        var laenge = START_LAENGE
        var hoehe = START_HOEHE
        var zeit = START_ZEIT

        val punkte = ArrayList<Rohpunkt>()
        punkte.add(Rohpunkt(breite, laenge, hoehe, zeit))

        for (a in abschnitte) {
            val jeAchse = a.tempoMs * JE_ACHSE
            val dBreite = jeAchse / GRAD_BREITE_M
            val dLaenge = jeAchse / (GRAD_BREITE_M * COS_47_7)
            repeat(a.dauerS) {
                zeit += 1
                breite += dBreite
                laenge += dLaenge
                hoehe += a.steigenMs
                punkte.add(Rohpunkt(breite, laenge, hoehe, zeit))
            }
        }
        return punkte
    }

    /** Die fünf Ströme, wortgleich zu `stroeme.py`. */
    val REISEFLUG = listOf(Abschnitt(100, 60.0, 2.0), Abschnitt(800, 60.0, 0.0))
    val ANFAHRT_BODEN = listOf(Abschnitt(600, 12.0, 0.0))
    val STAND_EINSATZORT = listOf(Abschnitt(900, 0.0, 0.0))
    val STADTFAHRT = buildList {
        repeat(10) {
            add(Abschnitt(60, 8.0, 0.0))
            add(Abschnitt(30, 0.0, 0.0))
        }
    }
    val DIENST_12H = buildList {
        add(Abschnitt(7200, 0.0, 0.0))
        repeat(3) {
            add(Abschnitt(120, 60.0, 3.0))
            add(Abschnitt(900, 60.0, 0.0))
            add(Abschnitt(1200, 0.0, 0.0))
            add(Abschnitt(900, 60.0, 0.0))
            add(Abschnitt(600, 0.0, 0.0))
        }
        add(Abschnitt(7200, 0.0, 0.0))
        add(Abschnitt(17640, 0.0, 0.0))
    }

    val NACH_NAME = mapOf(
        "reiseflug" to REISEFLUG,
        "anfahrt_boden" to ANFAHRT_BODEN,
        "stand_einsatzort" to STAND_EINSATZORT,
        "stadtfahrt" to STADTFAHRT,
        "dienst12h" to DIENST_12H,
    )
}
