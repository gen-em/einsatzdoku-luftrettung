package org.genem.nadoku.handy.senden

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test
import org.junit.rules.TemporaryFolder
import java.io.File

/**
 * Der Gerätezähler der Einsatzhülle — die Folge selbst (E-KA-69; bis KA-06f
 * E-KA-64).
 *
 * Anlass: Nr. 563 — eine Hülle mit kleinerem Zähler als der zuletzt
 * abgenommenen weist der Browser als `zurueckgelegt` ab. Zählt das Handy
 * einmal rückwärts — nach einem Neustart, nach dem Trennen, weil seine Uhr
 * zurückspringt —, sieht sein eigener, richtiger Stand aus wie ein Angriff.
 *
 * Hier steht nur, WIE der eine Zähler des Geräts zählt. WANN er zählt — bei
 * einer Änderung am Inhalt, nie beim Verschließen — misst
 * `EinsatzzaehlerTest` am Puffer und am Sender.
 */
class HuellenzaehlerTest {

    @get:Rule val ordner = TemporaryFolder()

    private val t = 1_784_277_065L

    /** Die Regel selbst: max(jetzt, letztes z + 1), nie unter 1. */
    @Test fun folge() {
        assertEquals("ohne Vorgänger: die Uhrzeit", t, Huellenzaehler.folge(null, t))
        assertEquals("die Uhr ist weiter", t + 10, Huellenzaehler.folge(t, t + 10))
        assertEquals("dieselbe Sekunde", t + 1, Huellenzaehler.folge(t, t))
        assertEquals("die Uhr läuft rückwärts", t + 1, Huellenzaehler.folge(t, t - 3600))
        assertEquals("keine Zeit, kein Vorgänger", 1L, Huellenzaehler.folge(null, 0))
        assertEquals("negative Zeit", 1L, Huellenzaehler.folge(null, -5))
        assertEquals("ein Stand unter 1 zählt nicht", 1L, Huellenzaehler.folge(0, 0))
        assertEquals("ein Stand unter 1 zählt nicht", t, Huellenzaehler.folge(-7, t))
        assertEquals("alter +1-Zähler springt auf die Uhrzeit", t, Huellenzaehler.folge(41, t))
        assertEquals("an der Grenze bleibt er stehen", Huellenzaehler.Z_MAX, Huellenzaehler.folge(Huellenzaehler.Z_MAX, t))
        assertEquals(Huellenzaehler.Z_MAX, Huellenzaehler.folge(Long.MAX_VALUE, t))
        assertEquals(Huellenzaehler.Z_MAX, Huellenzaehler.folge(null, Long.MAX_VALUE))
    }

    /** Streng steigend, auch bei vielen Hüllen in derselben Sekunde. */
    @Test fun strengSteigendInEinerSekunde() {
        val z = Huellenzaehler(SpeicherZaehlerstand()) { t }
        val folge = List(50) { z.naechster() }
        assertEquals(t, folge.first())
        assertEquals((t until t + 50).toList(), folge)
    }

    /** Die Uhr läuft rückwärts: Der Zähler läuft ihr voraus, bis sie ihn einholt. */
    @Test fun uhrLaeuftRueckwaerts() {
        var jetzt = t
        val z = Huellenzaehler(SpeicherZaehlerstand()) { jetzt }
        assertEquals(t, z.naechster())
        jetzt = t - 86_400
        assertEquals(t + 1, z.naechster())
        assertEquals(t + 2, z.naechster())
        jetzt = t + 2          // eingeholt, aber nicht überholt
        assertEquals(t + 3, z.naechster())
        jetzt = t + 100
        assertEquals(t + 100, z.naechster())
    }

    /** Neustart: ein neuer Zähler auf demselben Speicher setzt fort — auch wenn die Uhr inzwischen zurückging. */
    @Test fun ueberNeustartHinweg() {
        val stand = SpeicherZaehlerstand()
        val vorher = Huellenzaehler(stand) { t }.naechster()
        assertEquals(vorher + 1, Huellenzaehler(stand) { t - 500 }.naechster())
        assertEquals(vorher + 2, Huellenzaehler(stand) { t - 500 }.naechster())
        assertEquals(t + 60, Huellenzaehler(stand) { t + 60 }.naechster())
    }

    /** Abgelegt, bevor ihn jemand benutzt: Scheitert das Schreiben, gibt es keinen Zähler. */
    @Test fun ohneAblageKeinZaehler() {
        val kaputt = object : Zaehlerstand {
            override fun lesen(): Long? = 5
            override fun schreiben(z: Long) = throw java.io.IOException("Platte voll")
        }
        val wurf = runCatching { Huellenzaehler(kaputt) { t }.naechster() }.exceptionOrNull()
        assertTrue("als Zaehlerausfall, damit Puffer und Sender ihn erkennen", wurf is Zaehlerausfall)
    }

    /** Zwei Zähler auf einer Ablage, viele Fäden: keine Zahl zweimal. */
    @Test fun zweiInstanzenVieleFaeden() {
        val stand = SpeicherZaehlerstand()
        val a = Huellenzaehler(stand) { t }
        val b = Huellenzaehler(stand) { t }
        val gesehen = java.util.Collections.synchronizedList(mutableListOf<Long>())
        val faeden = (0 until 8).map { i ->
            Thread { repeat(200) { gesehen.add((if (i % 2 == 0) a else b).naechster()) } }.also { it.start() }
        }
        faeden.forEach { it.join() }
        assertEquals(1600, gesehen.size)
        assertEquals(1600, gesehen.toSet().size)
        assertEquals(t + 1599, gesehen.max())
    }

    /** Die Datei: Der Stand überdauert den Prozess. */
    @Test fun dateiUeberNeustart() {
        val datei = File(ordner.root, DateiZaehlerstand.DATEINAME)
        assertNull(DateiZaehlerstand(datei).lesen())
        assertEquals(t, Huellenzaehler(DateiZaehlerstand(datei)) { t }.naechster())
        assertEquals(t.toString(), datei.readText())
        assertEquals(t + 1, Huellenzaehler(DateiZaehlerstand(datei)) { t - 10 }.naechster())
        assertEquals(t + 1, DateiZaehlerstand(datei).lesen())
        assertFalse(File(ordner.root, DateiZaehlerstand.DATEINAME + ".neu").exists())
    }

    /**
     * Trennen: Der Tresor und das Gerätepaar werden gelöscht, der Zähler
     * liegt in einer eigenen Datei und bleibt. Gespielt wie in der App — die
     * Dateien der Kopplung verschwinden, die des Zählers wird nicht
     * angefasst —, und eine neue Kopplung zählt weiter.
     */
    @Test fun ueberdauertDasTrennen() {
        val zaehlerdatei = File(ordner.root, DateiZaehlerstand.DATEINAME)
        val tresor = File(ordner.root, "tresor.bin").also { it.writeText("x") }
        val geraet = File(ordner.root, "geraet.bin").also { it.writeText("x") }
        val vorher = Huellenzaehler(DateiZaehlerstand(zaehlerdatei)) { t + 7200 }.naechster()   // die Uhr ging vor

        tresor.delete(); geraet.delete()                                                        // Trennen

        val nachher = Huellenzaehler(DateiZaehlerstand(zaehlerdatei)) { t }.naechster()         // Uhr berichtigt
        assertEquals(vorher + 1, nachher)
    }

    /** Unlesbar ist wie nicht vorhanden: Der Zähler beginnt bei der Uhrzeit. */
    @Test fun unlesbareDatei() {
        val datei = File(ordner.root, DateiZaehlerstand.DATEINAME)
        datei.writeText("keine Zahl")
        assertNull(DateiZaehlerstand(datei).lesen())
        assertEquals(t, Huellenzaehler(DateiZaehlerstand(datei)) { t }.naechster())
        datei.writeText("  ${t + 5}\n")
        assertEquals("Leerraum stört nicht", t + 5, DateiZaehlerstand(datei).lesen())
    }
}
