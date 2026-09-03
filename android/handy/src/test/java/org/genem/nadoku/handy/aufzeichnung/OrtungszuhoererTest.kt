package org.genem.nadoku.handy.aufzeichnung

import org.junit.Assert.assertEquals
import org.junit.Test

/**
 * Der Zuhörer deklariert alle vier Methoden selbst (E-S5Z-05, B-S5Z-01).
 *
 * WAS DIESER FALL BELEGT UND WAS NICHT. Er belegt **nicht**, dass auf einem
 * Android-8-Gerät kein `AbstractMethodError` mehr fliegt — dafür bräuchte es
 * ein solches Gerät (Prüfliste 9.2 Punkt 16), und in diesem Container gibt es
 * nicht einmal einen Emulator. Er belegt, dass die Bauform steht, die den
 * Fehler ausschliesst: vier eigene Deklarationen statt einer.
 *
 * Er ist damit ein **Regressionsschutz**, kein Beweis — und genau deshalb
 * über Reflexion und nicht über einen Aufruf: Ein Aufruf ginge auch gegen ein
 * Lambda gut, solange nur `onLocationChanged` gerufen wird.
 */
class OrtungszuhoererTest {

    private val erwartet = setOf(
        "onLocationChanged",
        "onStatusChanged",
        "onProviderEnabled",
        "onProviderDisabled",
    )

    @Test
    fun der_zuhoerer_deklariert_alle_vier_methoden_selbst() {
        val eigene = Ortungszuhoerer::class.java.declaredMethods
            .map { it.name }
            .toSet()
        assertEquals(emptySet<String>(), erwartet - eigene)
    }

    /**
     * Die Gegenprobe: Ein SAM-Lambda — die Bauform von 0.7.7 — deklariert
     * genau eine davon. Ohne diese Zeile bewiese der Fall oben nichts über
     * den Unterschied, den er schützen soll.
     */
    @Suppress("ObjectLiteralToLambda")
    @Test
    fun ein_lambda_deklariert_nur_eine_und_das_ist_der_unterschied() {
        val alsLambda = android.location.LocationListener { }
        val eigene = alsLambda.javaClass.declaredMethods
            .map { it.name }
            .toSet()
        assertEquals(setOf("onLocationChanged"), erwartet intersect eigene)
    }
}
