package org.genem.nadoku.handy.senden

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Stroeme
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Modus
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
 * **Wiedergabe der Referenz-Payloads** gegen das echte `ingest.php`
 * (Abnahme B4).
 *
 * Aus den Strömen von B3 erzeugt die Sendelogik Anfrageketten, die
 * vollständig durchlaufen müssen:
 *
 * ```
 * 0 × rejected · 0 × kept_* · 0 × 400 · seq lückenlos · Chunkgrenze eingehalten
 * ```
 *
 * WARUM DAS MEHR IST ALS [SenderTest]. Dort antwortet ein Prüfserver mit dem,
 * was der Prüffall einstellt — die Fälle sind vollständig, prüfen aber die
 * Annahme mit, dass `ingest.php` so antwortet. Hier antwortet `ingest.php`
 * selbst, mit `validate_lib.php` dahinter. Ein Feld, das die App falsch
 * benennt, landet dann in `rejected` und nicht in einem grünen Prüffall.
 *
 * VORBEREITUNG steht in `android/LIESMICH.md`, Abschnitt „Der Server-Rundlauf".
 * Die Fälle **räumen hinter sich auf** und geben die Kopplung zurück.
 */
@RunWith(RobolectricTestRunner::class)
class SendeRundlaufTest {

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

    private val geraet = Geraeteangabe(
        art = Geraeteangabe.ART_HANDY, teil = null, hersteller = "samsung", modell = "SM-S921B",
        br = 1080, ho = 2340, touch = true, fw = "16", sdk = 36, app = "0.4.0",
    )

    @Before fun aufbauen() {
        assumeTrue(
            "Kein Rundlauf: -Pnadoku.rundlauf=<Basis> nicht gesetzt",
            basis.isNotEmpty(),
        )
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATENBANK)
        puffer = Puffer(kontext, DATENBANK)
        tresordatei = File(kontext.filesDir, "rundlauf-senden.bin")
        tresordatei.delete()
        tresor = Schluesseltresor(tresordatei, PruefTresorschluessel())
    }

    @After fun abbauen() {
        if (basis.isNotEmpty() && tresor.gekoppelt()) {
            Kopplungsdienst(HttpNetzweg(), tresor).trennen(basis)
        }
        if (this::puffer.isInitialized) puffer.close()
        if (this::kontext.isInitialized) kontext.deleteDatabase(DATENBANK)
        if (this::tresordatei.isInitialized) tresordatei.delete()
    }

    private fun koppeln(code: String) {
        val e = Kopplungsdienst(HttpNetzweg(), tresor).koppeln(basis, code, geraet)
        assertEquals("Der Rundlauf braucht eine Kopplung", Kopplungsergebnis.Gekoppelt, e)
    }

    private fun sender() = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor,
        basis = { basis }, phasenLeser = { puffer.phasen(it) },
    )

    /* EIN Zähler für den ganzen Prüffall, nicht einer je Klammer.
     *
     * Der erste Wurf machte es falsch — und die UNIQUE-Bedingung auf
     * `dienst.dienst_ref` hat es gefangen: Ein frischer Zähler beginnt wieder
     * bei 1 und liefert mit demselben Zufallsstartwert dieselbe Kennung. Genau
     * davor schützt in der App der Zählerspeicher, der Neustarts überlebt
     * (Vertrag 8) — der Prüfstand muss ihn also genauso führen. */
    private val zaehler = Merkzaehler()

    private fun klammer() = Dienstklammer(
        puffer = puffer,
        kennungen = Kennungen(zaehler, Random(4)),
        ausduenner = Ausduenner(),
        jetzt = { uhrzeit },
    )

    /**
     * Ein vollständiger 12-h-Dienst als Ruhesegment-Kette — der
     * Nur-Aufzeichnen-Fall (E-S4-20), und zugleich die längste Kette, die
     * dieser Client erzeugt.
     */
    @Test fun zwoelfStundenDienstLaeuftVollstaendigDurch() {
        koppeln("RA2B3C")

        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.DIENST_12H)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        k.beenden()

        val punkte = puffer.warteschlange().sumOf { puffer.punktzahl(it.id) }
        val bericht = sender().sendeAlles()

        println(
            "Rundlauf 12-h-Dienst: ${bericht.anfragen} Anfragen, " +
                "${bericht.gesendetePunkte} von $punkte Punkten, " +
                "verworfen=${bericht.verworfen}, übergangen=${bericht.uebergangen}, " +
                "fehlerhaft=${bericht.fehlerhaft}"
        )

        assertEquals("0 × rejected", emptyMap<String, Int>(), bericht.verworfen)
        assertEquals("0 × kept_*", emptyMap<String, Int>(), bericht.uebergangen)
        assertEquals("0 × 400", 0, bericht.fehlerhaft)
        assertTrue("Nicht pausiert", !bericht.pausiert)
        assertTrue("Kein späterer Versuch nötig", !bericht.spaeterErneut)
        assertEquals("Alle Punkte gesendet", punkte.toInt(), bericht.gesendetePunkte)
        assertEquals("Das Paket ist fertig und entsorgt", 1, bericht.fertigePakete)
        assertTrue("Der Puffer ist leer", puffer.warteschlange().isEmpty())
        assertEquals("Kein Rückstand", 0, puffer.rueckstand())
    }

    /**
     * Die kurzen Ströme einzeln — jeder als eigener Dienst, damit ein Fehler
     * an einem Strom nicht von einem anderen verdeckt wird.
     */
    @Test fun dieKurzenStroemeLaufenEbenfallsDurch() {
        koppeln("RD4E5F")

        var anfragenGesamt = 0
        var punkteGesamt = 0
        for (name in listOf("reiseflug", "anfahrt_boden", "stand_einsatzort", "stadtfahrt")) {
            val k = klammer()
            k.beginnen(Modus.NUR_AUFZEICHNEN)
            for (p in Stroeme.erzeuge(checkNotNull(Stroeme.NACH_NAME[name]))) k.positionsfund(p)
            uhrzeit = uhrzeit.plusSeconds(43_200)
            k.beenden()

            val bericht = sender().sendeAlles()
            anfragenGesamt += bericht.anfragen
            punkteGesamt += bericht.gesendetePunkte

            assertEquals("$name: 0 × rejected", emptyMap<String, Int>(), bericht.verworfen)
            assertEquals("$name: 0 × kept_*", emptyMap<String, Int>(), bericht.uebergangen)
            assertEquals("$name: 0 × 400", 0, bericht.fehlerhaft)
            assertTrue("$name: sauber", bericht.sauber)
        }
        println("Rundlauf kurze Ströme: $anfragenGesamt Anfragen, $punkteGesamt Punkte")
        assertEquals("901 + 301 + 91 + 331", 1624, punkteGesamt)
        assertEquals("Kein Rückstand", 0, puffer.rueckstand())
    }

    /**
     * **Nachzügler**: Dieselbe Kette ein zweites Mal senden darf nichts
     * doppelt anlegen (Vertrag 2, Idempotenz über Gerät + `client_ref`).
     *
     * Das ist der Fall, für den es die Kennung gibt — und der Fall, den
     * `HttpURLConnection` beim Verbindungsabbruch von selbst herbeiführt.
     */
    @Test fun einZweitesSendenLegtNichtsDoppeltAn() {
        koppeln("RG6H7J")

        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) k.positionsfund(p)
        val segment = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!
        uhrzeit = Instant.parse("2026-07-16T06:00:00Z")
        k.beenden()

        // Erster Lauf: geht durch, das Paket wird entsorgt.
        assertTrue(sender().sendeAlles().sauber)

        /* Zweiter Lauf mit DEMSELBEN Paket: Es wird von Hand wieder angelegt,
         * mit derselben client_ref und denselben Punkten — genau das, was ein
         * Nachzügler tut. Der Server muss es wiedererkennen. */
        val zweiteId = puffer.paketAnlegen(
            segment.clientRef, segment.art, segment.tag, segment.dienstRef, segment.begonnenAt,
        )
        val k2 = klammer()
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) {
            puffer.punktAnhaengen(zweiteId, p)
        }
        puffer.paketSchliessen(zweiteId, "2026-07-16T06:00:00Z", null, null)

        val bericht = sender().sendeAlles()

        println("Nachzügler: ${bericht.anfragen} Anfragen, verworfen=${bericht.verworfen}")
        assertEquals("0 × rejected auch beim zweiten Mal", emptyMap<String, Int>(), bericht.verworfen)
        assertEquals(0, bericht.fehlerhaft)
        assertTrue("Der Server nimmt den Nachzügler an", bericht.sauber)
    }

    private companion object {
        const val DATENBANK = "rundlauf-senden.db"
    }
}
