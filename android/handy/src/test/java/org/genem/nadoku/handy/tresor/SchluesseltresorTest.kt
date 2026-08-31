package org.genem.nadoku.handy.tresor

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File

/**
 * Die Schlüsselablage (E-S4-13).
 *
 * Der wichtigste Fall ist [keinKlartextImSpeicherabbild]: Er durchsucht das
 * **gesamte** App-private Verzeichnis nach dem Schlüssel — nicht nur die
 * Tresordatei. Genau so entsteht der Fehler, den er sucht: nicht dadurch, dass
 * jemand den Schlüssel absichtlich im Klartext ablegt, sondern dadurch, dass
 * er nebenbei irgendwo landet.
 */
@RunWith(RobolectricTestRunner::class)
class SchluesseltresorTest {

    private lateinit var kontext: Context
    private lateinit var datei: File
    private lateinit var tresor: Schluesseltresor

    private val zugang = Zugangsdaten(
        geraeteKennung = "dev-2f8a1c4e9b0d7a63f15e8c2a4b96d038",
        schluessel = "9c1f7ab35de08246bb51907cf3ea62d4718abf5069c2d83e",
    )

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        datei = File(kontext.filesDir, Schluesseltresor.DATEINAME)
        datei.delete()
        tresor = Schluesseltresor(datei, PruefTresorschluessel())
    }

    @Test fun leererTresorGibtNichtsHer() {
        assertNull(tresor.lesen())
        assertFalse(tresor.gekoppelt())
    }

    @Test fun rundlauf() {
        tresor.speichern(zugang)
        assertTrue(tresor.gekoppelt())
        assertEquals(zugang, tresor.lesen())
    }

    @Test fun loeschenEntferntAllesAufDerPlatte() {
        tresor.speichern(zugang)
        tresor.loeschen()
        assertNull(tresor.lesen())
        assertFalse(datei.exists())
        assertFalse(File(datei.parentFile, datei.name + ".neu").exists())
    }

    /**
     * Die Abnahme aus dem Konzept: **kein Klartext im App-Speicherabbild.**
     *
     * Durchsucht wird das ganze App-private Verzeichnis, nicht nur die
     * Tresordatei — denn so entsteht der Fehler, den dieser Fall sucht: nicht
     * dadurch, dass jemand den Schlüssel absichtlich im Klartext ablegt,
     * sondern dadurch, dass er nebenbei irgendwo landet (Einstellungen,
     * Zwischendatei, Absturzbericht).
     *
     * Deshalb legt der Fall vorher AUCH die Einstellungen an: Ein Lauf, der
     * genau eine Datei durchsucht, hat nicht bewiesen, dass er suchen kann.
     */
    @Test fun keinKlartextImSpeicherabbild() {
        // Zweite Ablage anlegen, damit es überhaupt etwas zu verwechseln gibt.
        org.genem.nadoku.handy.Einstellungen(kontext).serverBasis =
            "einsatz.beispieldomain.de"
        tresor.speichern(zugang)

        val durchsucht = mutableListOf<File>()
        val gefunden = mutableListOf<String>()
        kontext.dataDir.walkTopDown().filter { it.isFile }.forEach { f ->
            durchsucht += f
            val inhalt = f.readBytes()
            if (enthaelt(inhalt, zugang.schluessel) || enthaelt(inhalt, zugang.geraeteKennung)) {
                gefunden += f.absolutePath
            }
        }

        // Die Zahl gehört ins Prüfprotokoll: Eine Prüfung, die null Dateien
        // durchsucht hat, findet auch null Treffer.
        println("Durchsuchte Dateien im App-Verzeichnis: ${durchsucht.size}")
        durchsucht.forEach { println("  durchsucht: ${it.absolutePath} (${it.length()} B)") }

        assertTrue("Es wurde keine einzige Datei durchsucht", durchsucht.size >= 2)
        assertTrue(
            "Die Tresordatei selbst war nicht dabei — dann sagt der Fall nichts",
            durchsucht.any { it.absolutePath == datei.absolutePath },
        )
        assertTrue("Klartext gefunden in: $gefunden", gefunden.isEmpty())
    }

    /** Die Ablage darf sich beim zweiten Schreiben nicht wiederholen. */
    @Test fun jederSchreibvorgangHatEinenNeuenZufallswert() {
        tresor.speichern(zugang)
        val ersteAblage = datei.readBytes()
        tresor.speichern(zugang)
        val zweiteAblage = datei.readBytes()

        assertNotEquals(
            "Zweimal derselbe Geheimtext heisst zweimal derselbe Zufallswert — das bricht GCM",
            ersteAblage.toList(), zweiteAblage.toList()
        )
        assertEquals(zugang, tresor.lesen())
    }

    /** Ein beschädigtes Paket ist wie keines — und darf nicht durchschlagen. */
    @Test fun beschaedigteAblageGiltAlsNichtGekoppelt() {
        tresor.speichern(zugang)
        val bytes = datei.readBytes()
        bytes[bytes.size - 1] = (bytes[bytes.size - 1].toInt() xor 0x01).toByte()
        datei.writeBytes(bytes)

        assertNull(tresor.lesen())
        assertFalse(tresor.gekoppelt())
    }

    @Test fun fremderSchluesselOeffnetDenTresorNicht() {
        tresor.speichern(zugang)
        val fremder = Schluesseltresor(datei, PruefTresorschluessel())
        assertNull(fremder.lesen())
    }

    /** E-S4-13: kein Klartext in Logs — auch nicht beiläufig. */
    @Test fun zugangsdatenZeigenSichNichtInDerTextfassung() {
        val text = zugang.toString()
        assertFalse("Der Schlüssel steht in toString()", text.contains(zugang.schluessel))
    }

    /** Kommt die Zeichenkette irgendwo in diesen Bytes vor? */
    private fun enthaelt(heuhaufen: ByteArray, nadelText: String): Boolean {
        val nadel = nadelText.toByteArray(Charsets.UTF_8)
        if (nadel.isEmpty() || heuhaufen.size < nadel.size) return false
        return (0..heuhaufen.size - nadel.size).any { i ->
            nadel.indices.all { j -> heuhaufen[i + j] == nadel[j] }
        }
    }
}
