package org.genem.nadoku.handy.senden

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.gemeinsam.Phasen
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Stroeme
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.handy.dienst.Kennungen
import org.genem.nadoku.handy.dienst.Modus
import org.genem.nadoku.handy.dienst.Zeit
import org.genem.nadoku.handy.kopplung.Geraeteangabe
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.kopplung.Kopplungsdienst
import org.genem.nadoku.handy.kopplung.Kopplungsergebnis
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.time.Instant
import kotlin.random.Random

/**
 * **`mission`-Ketten gegen das echte `ingest.php`** (Abnahme B5).
 *
 * Wie in B4: 0 × `rejected`, 0 × `kept_*`, 0 × 400. Zusätzlich zu prüfen ist
 * hier, was nur ein Einsatz hat — Phasen, Kennzahlen, `final` mit `ended_at`
 * — und vor allem, dass **mehrfache Phaseneinträge erhalten bleiben**
 * (E-R45-12): Der Server ersetzt die Phasenliste bei jedem Upload, und wenn
 * er entdoppelte, fiele es genau hier auf.
 */
@RunWith(RobolectricTestRunner::class)
class MissionRundlaufTest {

    private val basis: String = System.getProperty("nadoku.rundlauf").orEmpty()

    private lateinit var kontext: Context
    private lateinit var puffer: Puffer
    private lateinit var tresor: Schluesseltresor
    private lateinit var tresordatei: File
    private var uhrzeit: Instant = Instant.parse("2026-07-16T05:00:00Z")

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        override fun lies() = wert
        override fun schreib(wert: Long) { this.wert = wert }
    }

    private val zaehler = Merkzaehler()
    private val ausduenner = Ausduenner()

    private val geraet = Geraeteangabe(
        art = Geraeteangabe.ART_HANDY, teil = null, hersteller = "samsung", modell = "SM-S921B",
        br = 1080, ho = 2340, touch = true, fw = "16", sdk = 36, app = "0.5.0",
    )

    @Before fun aufbauen() {
        assumeTrue("Kein Rundlauf gesetzt", basis.isNotEmpty())
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATENBANK)
        puffer = Puffer(kontext, DATENBANK)
        tresordatei = File(kontext.filesDir, "rundlauf-mission.bin")
        tresordatei.delete()
        tresor = Schluesseltresor(tresordatei, PruefTresorschluessel())
    }

    @After fun abbauen() {
        if (basis.isNotEmpty() && this::tresor.isInitialized && tresor.gekoppelt()) {
            Kopplungsdienst(HttpNetzweg(), tresor).trennen(basis)
        }
        if (this::puffer.isInitialized) puffer.close()
        if (this::kontext.isInitialized) kontext.deleteDatabase(DATENBANK)
        if (this::tresordatei.isInitialized) tresordatei.delete()
    }

    private fun sender() = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor,
        basis = { basis }, phasenLeser = { puffer.phasen(it) },
    )

    private fun klammer() = Dienstklammer(
        puffer = puffer, kennungen = Kennungen(zaehler, Random(4)),
        ausduenner = ausduenner, jetzt = { uhrzeit },
    )

    /**
     * Ein ganzer Dienst mit drei Einsätzen: Ruhesegment, Einsatz, Ruhesegment,
     * Einsatz … — genau die Kette, die ein Diensttag erzeugt.
     */
    @Test fun einDienstMitDreiEinsaetzenLaeuftVollstaendigDurch() {
        val e = Kopplungsdienst(HttpNetzweg(), tresor).koppeln(basis, "MA2B3C", geraet)
        assertEquals(Kopplungsergebnis.Gekoppelt, e)

        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)

        repeat(3) { runde ->
            // Bereitschaft
            for (p in Stroeme.erzeuge(listOf(Stroeme.Abschnitt(600, 0.0, 0.0)))) {
                k.positionsfund(p.copy(zeit = Zeit.epoche(uhrzeit) + (p.zeit - Stroeme.START_ZEIT)))
            }
            // Einsatz: Phasen 2 bis 9, dazwischen Fahrt
            for (n in Phasen.ERSTE..Phasen.LETZTE) {
                uhrzeit = uhrzeit.plusSeconds(300)
                assertTrue("Phase $n in Runde $runde", k.phaseSetzen(n))
                for (p in Stroeme.erzeuge(listOf(Stroeme.Abschnitt(120, 40.0, 0.0)))) {
                    k.positionsfund(
                        p.copy(zeit = Zeit.epoche(uhrzeit) + (p.zeit - Stroeme.START_ZEIT))
                    )
                }
            }
            uhrzeit = uhrzeit.plusSeconds(60)
            assertTrue(k.einsatzAbschliessen())
        }

        uhrzeit = uhrzeit.plusSeconds(600)
        k.beenden()

        val pakete = puffer.warteschlange()
        val punkte = pakete.sumOf { puffer.punktzahl(it.id) }
        val einsaetze = pakete.count { it.art == Paketzeile.ART_EINSATZ }
        val segmente = pakete.count { it.art == Paketzeile.ART_RUHESEGMENT }

        val bericht = sender().sendeAlles()

        println(
            "Rundlauf mission: $einsaetze Einsätze, $segmente Segmente, $punkte Punkte, " +
                "${bericht.anfragen} Anfragen, verworfen=${bericht.verworfen}, " +
                "übergangen=${bericht.uebergangen}, fehlerhaft=${bericht.fehlerhaft}"
        )

        assertEquals("Drei Einsätze", 3, einsaetze)
        assertEquals("Vier Ruhesegmente — vor, zwischen und nach den Einsätzen", 4, segmente)
        assertEquals("0 × rejected", emptyMap<String, Int>(), bericht.verworfen)
        assertEquals("0 × kept_*", emptyMap<String, Int>(), bericht.uebergangen)
        assertEquals("0 × 400", 0, bericht.fehlerhaft)
        assertTrue(bericht.sauber)
        assertEquals("Alle Punkte gesendet", punkte.toInt(), bericht.gesendetePunkte)
        assertEquals("Alle Pakete fertig", pakete.size, bericht.fertigePakete)
        assertEquals("Kein Rückstand", 0, puffer.rueckstand())
    }

    /**
     * **Mehrfache Phaseneinträge überstehen den Rundlauf** (E-R45-12).
     *
     * Der Server ersetzt die Phasenliste bei jedem Upload. Entdoppelte er
     * dabei — oder übergänge er die Liste —, käme hier ein `kept_phases`
     * zurück oder ein `rejected`.
     */
    @Test fun doppeltePhaseneintraegeUeberstehenDenRundlauf() {
        assertEquals(
            Kopplungsergebnis.Gekoppelt,
            Kopplungsdienst(HttpNetzweg(), tresor).koppeln(basis, "MD4E5F", geraet),
        )

        val k = klammer()
        k.beginnen(Modus.MIT_PHASENKNOEPFEN)
        k.positionsfund(
            org.genem.nadoku.handy.aufzeichnung.Rohpunkt(47.7261, 10.3186, 712.0, Zeit.epoche(uhrzeit))
        )

        // Phase 4 dreimal, mit verschiedenen Zeiten — zweimal Korrektur.
        for (versatz in listOf(0L, 300L, 600L)) {
            uhrzeit = uhrzeit.plusSeconds(versatz)
            k.phaseSetzen(4)
        }
        val einsatzId = puffer.offenesPaket(Paketzeile.ART_EINSATZ)!!.id
        assertEquals("Drei Einträge im Puffer", 3, puffer.phasen(einsatzId).size)

        uhrzeit = uhrzeit.plusSeconds(300)
        k.einsatzAbschliessen()
        uhrzeit = uhrzeit.plusSeconds(300)
        k.beenden()

        val bericht = sender().sendeAlles()

        println("Rundlauf Doppelphasen: verworfen=${bericht.verworfen}, " +
            "übergangen=${bericht.uebergangen}")
        assertEquals("Keine Phase darf verworfen werden", emptyMap<String, Int>(), bericht.verworfen)
        assertEquals(
            "Die Liste darf nicht übergangen werden — sonst entdoppelt jemand",
            emptyMap<String, Int>(), bericht.uebergangen,
        )
        assertTrue(bericht.sauber)
    }

    private companion object {
        const val DATENBANK = "rundlauf-mission.db"
    }
}
