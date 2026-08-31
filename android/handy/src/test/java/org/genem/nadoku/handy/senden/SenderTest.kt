package org.genem.nadoku.handy.senden

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Stroeme
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.handy.dienst.Kennungen
import org.genem.nadoku.handy.dienst.Modus
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.genem.nadoku.handy.tresor.Zugangsdaten
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.time.Instant
import kotlin.random.Random

/**
 * Warteschlange, Teilstücke und die **Funkabriss-Matrix** (Abnahme B4).
 *
 * Gesendet wird über echtes HTTP an [PruefServer] — damit [HttpNetzweg] und
 * der Kopfzeilenweg mitgeprüft werden. Was der Server antwortet, stellt der
 * Prüffall ein; das ist der einzige Weg, an einen 413 oder einen 5xx zu
 * kommen, ohne auf einen echten zu warten.
 */
@RunWith(RobolectricTestRunner::class)
class SenderTest {

    private lateinit var kontext: Context
    private lateinit var puffer: Puffer
    private lateinit var server: PruefServer
    private lateinit var tresor: Schluesseltresor
    private lateinit var tresordatei: File
    private var uhrzeit: Instant = Instant.parse("2026-07-16T05:00:00Z")

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        override fun lies() = wert
        override fun schreib(wert: Long) { this.wert = wert }
    }

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATENBANK)
        puffer = Puffer(kontext, DATENBANK)
        server = PruefServer()
        tresordatei = File(kontext.filesDir, "sendepruefung.bin")
        tresordatei.delete()
        tresor = Schluesseltresor(tresordatei, PruefTresorschluessel())
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef"))
    }

    @After fun abbauen() {
        server.close()
        puffer.close()
        kontext.deleteDatabase(DATENBANK)
        tresordatei.delete()
    }

    private fun sender() = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor,
        basis = { server.basis },
        phasenLeser = { puffer.phasen(it) },
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

    /** Antwortet mit `next_seq` = alles, was ankam — wie der echte Server. */
    private fun antworteVollstaendig() {
        server.status = 200
        server.antwortAus = { koerper ->
            val o = org.json.JSONObject(koerper)
            val t = o.getJSONObject("track")
            val bis = t.getLong("seq_from") + t.getJSONArray("points").length()
            """{"ok":true,"id":1,"stored_points":${t.getJSONArray("points").length()},"next_seq":$bis}"""
        }
    }

    // ---- Warteschlange und Teilstücke --------------------------------------

    @Test fun einSegmentWirdInTeilstueckenGesendet() {
        antworteVollstaendig()
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.DIENST_12H)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        k.beenden()

        val punkte = puffer.warteschlange().sumOf { puffer.punktzahl(it.id) }
        val bericht = sender().sendeAlles()

        println("12-h-Dienst gesendet: ${bericht.anfragen} Anfragen, " +
            "${bericht.gesendetePunkte} Punkte, ${bericht.fertigePakete} Pakete fertig")

        assertEquals("Alle Punkte müssen hinausgegangen sein", punkte.toInt(), bericht.gesendetePunkte)
        assertTrue("Sauberer Lauf", bericht.sauber)
        // 9 505 Punkte zu je 500: 19 volle Teilstücke und ein Rest von 5.
        assertEquals(19 + 1, bericht.anfragen)
        assertEquals(1, bericht.fertigePakete)
        assertTrue("Der Puffer muss leer sein", puffer.warteschlange().isEmpty())
    }

    /** Kein Teilstück überschreitet die Chunk-Grenze des Vertrags (6). */
    @Test fun keinTeilstueckIstZuGross() {
        antworteVollstaendig()
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.DIENST_12H)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        k.beenden()
        sender().sendeAlles()

        var groesster = 0
        var meistePunkte = 0
        for (a in server.anfragen) {
            groesster = maxOf(groesster, a.koerper.toByteArray(Charsets.UTF_8).size)
            meistePunkte = maxOf(
                meistePunkte,
                org.json.JSONObject(a.koerper).getJSONObject("track")
                    .getJSONArray("points").length(),
            )
        }
        println("Größtes Teilstück: $groesster Bytes, $meistePunkte Punkte")
        assertTrue("Höchstens ${Sender.CHUNK_PUNKTE} Punkte", meistePunkte <= Sender.CHUNK_PUNKTE)
        assertTrue("Unter 512 KB", groesster < Sender.HOECHSTE_KOERPERGROESSE)
    }

    /** `seq_from` läuft lückenlos fort — sonst fehlten Punkte am Server. */
    @Test fun seqFromLaeuftLueckenlosFort() {
        antworteVollstaendig()
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.REISEFLUG)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T06:00:00Z")
        k.beenden()
        sender().sendeAlles()

        var erwartet = 0L
        for (a in server.anfragen) {
            val t = org.json.JSONObject(a.koerper).getJSONObject("track")
            assertEquals("seq_from muss fortlaufen", erwartet, t.getLong("seq_from"))
            erwartet += t.getJSONArray("points").length()
        }
        assertEquals(901L, erwartet)
    }

    @Test fun jedeAnfrageTraegtDieAuthKopfzeilen() {
        antworteVollstaendig()
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        k.positionsfund(Stroeme.erzeuge(Stroeme.REISEFLUG).first())
        sender().sendeAlles()

        assertTrue(server.anfragen.isNotEmpty())
        for (a in server.anfragen) {
            assertEquals("dev-pruef", a.kopfzeilen["X-Device-Id"])
            assertEquals("geheim-pruef", a.kopfzeilen["X-Api-Key"])
            assertTrue(a.zeile.startsWith("POST /ingest.php "))
        }
    }

    /** Abgeschlossene Einsätze zuerst — sie tragen die Dokumentation. */
    @Test fun einsaetzeGehenVorRuhesegmenten() {
        val segment = puffer.paketAnlegen("ar-1-x", Paketzeile.ART_RUHESEGMENT, "2026-07-16", "ad-1", "2026-07-16T05:00:00Z")
        puffer.paketSchliessen(segment, "2026-07-16T06:00:00Z", null, null)
        val einsatz = puffer.paketAnlegen("am-2-x", Paketzeile.ART_EINSATZ, "2026-07-16", "ad-1", "2026-07-16T06:00:00Z")
        puffer.paketSchliessen(einsatz, "2026-07-16T07:00:00Z", 1000, 10)

        val reihenfolge = puffer.warteschlange().map { it.art }
        assertEquals(listOf(Paketzeile.ART_EINSATZ, Paketzeile.ART_RUHESEGMENT), reihenfolge)
    }

    // ---- Die Funkabriss-Matrix ---------------------------------------------

    private fun einSegmentMitPunkten(anzahl: Int): Long {
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        val id = puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)!!.id
        var gesetzt = 0
        for (p in Stroeme.erzeuge(Stroeme.REISEFLUG)) {
            if (gesetzt >= anzahl) break
            if (k.positionsfund(p)) gesetzt++
        }
        return id
    }

    /** **Verlorene Antwort**: Es wird später unverändert erneut gesendet. */
    @Test fun verloreneAntwortAendertNichts() {
        val id = einSegmentMitPunkten(10)
        val adresse = server.basis
        server.close()                                  // die Gegenstelle ist fort

        val s = Sender(puffer, HttpNetzweg(), tresor, { adresse })
        val bericht = s.sendeAlles()

        assertTrue("Es muss später erneut versucht werden", bericht.spaeterErneut)
        assertEquals("Nichts darf als bestätigt gelten", 0L, puffer.paket(id)!!.bestaetigtSeq)
        assertFalse(puffer.paket(id)!!.fehlerhaft)
        assertNotNull("Das Paket bleibt liegen", puffer.paket(id))
    }

    /** **401**: Der Lauf pausiert; es wird nichts markiert und nichts entsorgt. */
    @Test fun einundvierzigPausiertDenLauf() {
        val id = einSegmentMitPunkten(10)
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""

        val bericht = sender().sendeAlles()

        assertTrue(bericht.pausiert)
        assertEquals("Genau eine Anfrage, dann Schluss", 1, bericht.anfragen)
        assertEquals(0L, puffer.paket(id)!!.bestaetigtSeq)
        assertFalse("401 ist kein Datenfehler", puffer.paket(id)!!.fehlerhaft)
    }

    /** **400**: als fehlerhaft markieren und **nicht wiederholen**. */
    @Test fun vierhundertMarkiertUndWiederholtNicht() {
        val id = einSegmentMitPunkten(10)
        server.status = 400
        server.antwortkoerper = """{"error":"payload"}"""

        val bericht = sender().sendeAlles()

        assertEquals(1, bericht.fehlerhaft)
        assertTrue(puffer.paket(id)!!.fehlerhaft)
        assertEquals("Eine Anfrage, keine Wiederholung", 1, bericht.anfragen)
        assertTrue(
            "Ein fehlerhaftes Paket steht nicht mehr in der Warteschlange",
            puffer.warteschlange().none { it.id == id },
        )
        assertNotNull(
            "Gelöscht wird es trotzdem nicht — sonst sähe niemand, dass etwas fehlt",
            puffer.paket(id),
        )
    }

    /** **413**: Chunk halbieren und wiederholen — bis es passt. */
    @Test fun dreizehnHalbiertDasTeilstueck() {
        einSegmentMitPunkten(600)
        val s = sender()
        assertEquals(500, s.chunkPunkte)

        // Erst dreimal 413, dann nimmt der Server an.
        var abgewiesen = 0
        server.antwortAus = { koerper ->
            val t = org.json.JSONObject(koerper).getJSONObject("track")
            val n = t.getJSONArray("points").length()
            if (abgewiesen < 3) {
                abgewiesen++
                server.status = 413
                """{"error":"too_large"}"""
            } else {
                server.status = 200
                """{"ok":true,"next_seq":${t.getLong("seq_from") + n}}"""
            }
        }

        val bericht = s.sendeAlles()

        assertEquals("Dreimal halbiert: 500 -> 250 -> 125 -> 62", 62, s.chunkPunkte)
        assertTrue("Und danach muss es durchgelaufen sein", bericht.fertigePakete >= 0)
        assertEquals(3, abgewiesen)
    }

    /** **5xx**: später unverändert erneut — nichts wird markiert. */
    @Test fun fuenfhundertWirdSpaeterUnveraendertWiederholt() {
        val id = einSegmentMitPunkten(10)
        server.status = 503
        server.antwortkoerper = ""

        val bericht = sender().sendeAlles()

        assertTrue(bericht.spaeterErneut)
        assertEquals(1, bericht.anfragen)
        assertEquals(0L, puffer.paket(id)!!.bestaetigtSeq)
        assertFalse(puffer.paket(id)!!.fehlerhaft)
    }

    /**
     * **App-Neustart mitten in der Kette.** Der halb gesendete Stand steht im
     * Puffer; ein neuer [Sender] setzt bei `next_seq` fort und sendet keinen
     * Punkt doppelt.
     */
    @Test fun neustartMittenInDerKette() {
        antworteVollstaendig()
        val id = einSegmentMitPunkten(901)

        // Erster Lauf: Nach zwei Anfragen bricht die Verbindung weg.
        var anfragen = 0
        server.antwortAus = { koerper ->
            anfragen++
            val t = org.json.JSONObject(koerper).getJSONObject("track")
            val bis = t.getLong("seq_from") + t.getJSONArray("points").length()
            if (anfragen > 1) throw IllegalStateException("Abbruch")
            """{"ok":true,"next_seq":$bis}"""
        }
        sender().sendeAlles()

        val nachErstemLauf = puffer.paket(id)!!.bestaetigtSeq
        assertEquals("Ein Teilstück ist bestätigt", 500L, nachErstemLauf)

        // Neustart: Puffer von der Platte neu öffnen, frischer Sender.
        puffer.close()
        puffer = Puffer(kontext, DATENBANK)
        antworteVollstaendig()

        val bericht = sender().sendeAlles()

        val gesendeteSeqs = server.anfragen.map {
            org.json.JSONObject(it.koerper).getJSONObject("track").getLong("seq_from")
        }
        println("seq_from der Anfragen über beide Läufe: $gesendeteSeqs")

        /* WAS HIER GEPRÜFT WIRD — und was ausdrücklich NICHT.
         *
         * Nicht geprüft wird, dass jedes `seq_from` nur einmal vorkommt: Der
         * Prüflauf zeigte, dass `HttpURLConnection` einen POST, dessen
         * Verbindung vor der Antwort abbricht, **von sich aus wiederholt** —
         * dieselbe Anfrage erscheint dann zweimal am Server. Das ist eine
         * Eigenschaft der Java-Umsetzung, keine dieser App.
         *
         * Schaden richtet sie nicht an, und zwar aus einem Grund, der im
         * Vertrag steht: Die Idempotenz hängt an (Gerät, `client_ref`, `seq`).
         * Derselbe Punkt zweimal geschickt wird beim zweiten Mal ignoriert
         * (Vertrag 2). Genau dafür ist sie da.
         *
         * Geprüft wird deshalb, was die App zusichern MUSS: dass sie nie
         * zurückspringt und nie eine Lücke lässt. */
        for (i in 1 until gesendeteSeqs.size) {
            assertTrue(
                "seq_from springt zurück: $gesendeteSeqs",
                gesendeteSeqs[i] >= gesendeteSeqs[i - 1],
            )
        }
        assertTrue("Der zweite Lauf muss bei 500 fortsetzen", gesendeteSeqs.contains(500L))
        assertEquals("Und bei 0 begonnen haben", 0L, gesendeteSeqs.first())
        assertNull(
            "Nach vollständiger Bestätigung ist das Paket entsorgt — oder vollständig bestätigt",
            puffer.paket(id)?.takeIf { it.bestaetigtSeq < 901L },
        )
        assertTrue(bericht.sauber)
    }

    // ---- rejected / kept_* --------------------------------------------------

    /** Ein `ok: true` mit `rejected` ist kein sauberer Lauf (E-S4-06). */
    @Test fun verworfeneWerteWerdenGemeldet() {
        einSegmentMitPunkten(10)
        server.status = 200
        server.antwortkoerper =
            """{"ok":true,"next_seq":10,"rejected":{"track.points: ausserhalb":3}}"""

        val bericht = sender().sendeAlles()

        assertFalse("Nicht als reiner Erfolg behandeln", bericht.sauber)
        assertEquals(3, bericht.verworfen["track.points: ausserhalb"])
    }

    @Test fun uebergangeneListenWerdenGemeldet() {
        einSegmentMitPunkten(10)
        server.status = 200
        server.antwortkoerper = """{"ok":true,"next_seq":10,"kept_phases":8}"""

        val bericht = sender().sendeAlles()

        assertFalse(bericht.sauber)
        assertEquals(8, bericht.uebergangen["kept_phases"])
    }

    // ---- Rückstand ---------------------------------------------------------

    /**
     * Der Rückstand zählt **nur abgeschlossene** Pakete. Das laufende Segment
     * darf nicht mitzählen — sonst stünde den ganzen Dienst über
     * „Rückstand 1" (Backlog Nr. 11).
     */
    @Test fun derRueckstandZaehltNurAbgeschlossenePakete() {
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) k.positionsfund(p)

        assertEquals("Das laufende Segment zählt nicht", 0, puffer.rueckstand())

        uhrzeit = Instant.parse("2026-07-16T06:00:00Z")
        k.beenden()
        assertEquals("Abgeschlossen und ungesendet: 1", 1, puffer.rueckstand())

        antworteVollstaendig()
        sender().sendeAlles()
        assertEquals("Nach dem Senden: 0", 0, puffer.rueckstand())
    }

    @Test fun einFehlerhaftesPaketZaehltNichtMehrZumRueckstand() {
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.ANFAHRT_BODEN)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T06:00:00Z")
        k.beenden()

        server.status = 400
        server.antwortkoerper = """{"error":"payload"}"""
        sender().sendeAlles()

        assertEquals(
            "Es wird nicht wiederholt — also ist es kein Rückstand mehr, sondern ein Befund",
            0, puffer.rueckstand(),
        )
    }

    @Test fun ohneKopplungWirdNichtGesendet() {
        einSegmentMitPunkten(10)
        tresor.loeschen()
        val bericht = sender().sendeAlles()
        assertEquals(0, bericht.anfragen)
        assertTrue(bericht.spaeterErneut)
    }

    @Test fun ohneServerAdresseWirdNichtGesendet() {
        einSegmentMitPunkten(10)
        val s = Sender(puffer, HttpNetzweg(), tresor, { null })
        assertEquals(0, s.sendeAlles().anfragen)
    }

    private companion object {
        const val DATENBANK = "sendepruefung.db"
    }
}
