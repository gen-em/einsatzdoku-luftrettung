package org.genem.nadoku.handy.senden

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.kopplung.Kopplungsdienst
import org.genem.nadoku.handy.kopplung.Netzantwort
import org.genem.nadoku.handy.kopplung.Netzweg
import org.genem.nadoku.handy.kopplung.Trennergebnis
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.genem.nadoku.handy.tresor.SoftwareAbsender
import org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel
import org.genem.nadoku.handy.tresor.Zugangsdaten
import org.json.JSONObject
import org.junit.After
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.time.Instant
import java.util.Base64

/**
 * Wann das Handy den Zähler `z` der Einsatzhülle vergibt (E-KA-69, KA-06f).
 *
 * Anlass: Nr. 563 — bis KA-06f hob jeder Sendeversuch den Zähler (E-KA-64),
 * und ein Handy, das nach einer Übergabe seinen alten, unveränderten Stand
 * nachsandte, gewann gegen den Abschluss des anderen Geräts.
 *
 * WAS HIER GEMESSEN WIRD, an Puffer, Sendekörper und Sender zusammen, mit
 * echtem SQLite und der echten Zählerdatei:
 *
 *     gleiches z     ⇒ innen byte-gleich
 *     anderer Inhalt ⇒ größeres z, über alle Einsätze des Geräts
 *
 * „Innen" ist, was der Browser nach dem Öffnen sieht — die Prüffälle öffnen
 * jede Hülle mit dem Konto der Referenzvektoren ([Huellenoeffner]).
 *
 * DER VEKTOR `zaehler` aus `tools/proben/huelle/vektoren-2.json` wird
 * Schritt für Schritt gespielt, alle zwölf. Seine Taten sind abstrakt
 * (`aendern`, `verschliessen`, `trennen`); das Handy spielt `aendern` mit
 * den drei Änderungen, die es kennt — Einsatz entsteht, Phase hinzu,
 * Abschluss. Eine Reanimation dokumentiert das Handy nicht; der Vektor
 * verlangt an keinem Schritt eine, es wird also keiner ausgelassen.
 *
 * WAS HIER NICHT GEMESSEN WIRD: die Übergabe an ein zweites Gerät und was
 * der Browser mit dem Zähler tut (`einsatz_lesen()` der Referenz, die
 * Browserprobe) — und ein echter Prozesstod. Gespielt wird er als
 * zurückgerollte Transaktion und als neu geöffneter Puffer auf derselben
 * Datei; ein gezogener Akku mitten im `fsync` ist das nicht.
 */
@RunWith(RobolectricTestRunner::class)
class EinsatzzaehlerTest {

    private lateinit var kontext: Context
    private lateinit var zaehlerdatei: File
    private lateinit var stand: DateiZaehlerstand
    private lateinit var puffer: Puffer
    private val offen = mutableListOf<AutoCloseable>()

    /** Die Uhr des Handys, in Sekunden — der Prüffall stellt sie. */
    private var jetzt = T0

    private val geraet = SoftwareAbsender.erzeugen()

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATENBANK)
        zaehlerdatei = File(kontext.filesDir, "pruef_" + DateiZaehlerstand.DATEINAME)
        zaehlerdatei.delete()
        stand = DateiZaehlerstand(zaehlerdatei)
        puffer = pufferOeffnen()
    }

    @After fun abbauen() {
        offen.forEach { runCatching { it.close() } }
        puffer.close()
        kontext.deleteDatabase(DATENBANK)
        zaehlerdatei.delete()
    }

    private fun pufferOeffnen(ablage: Zaehlerstand = stand) =
        Puffer(kontext, DATENBANK, Huellenzaehler(ablage) { jetzt })

    private fun iso(t: Long) = Instant.ofEpochSecond(t).toString()

    private fun einsatzAnlegen(ref: String): Long =
        puffer.paketAnlegen(ref, Paketzeile.ART_EINSATZ, "2026-07-16", "ad-1", iso(T0))

    private fun phase(id: Long, nummer: Int, breite: Double? = 47.5601) =
        puffer.phaseAnhaengen(id, nummer, iso(jetzt), breite, breite?.let { 10.7002 }, "handy")

    private fun z(id: Long): Long? = puffer.paket(id)!!.zaehler

    /** Verschließen wie der Sender — Stand aus einem Augenblick, dann die Hülle — und öffnen. */
    private fun innen(id: Long): ByteArray {
        val s = puffer.einsatzstand(id, zaehlerSichern = true)!!
        val o = Sendekoerper.baue(
            s.paket, s.paket.bestaetigtSeq, emptyList(), s.phasen, Huellenoeffner.konto, Fassung2(geraet) { jetzt },
        )
        return Huellenoeffner.oeffnen2(
            Base64.getDecoder().decode(o.getString("ort_huelle")), geraet.spk, "einsatz", s.paket.clientRef, "",
        )
    }

    private fun zAus(innen: ByteArray): Long = JSONObject(String(innen, Charsets.UTF_8)).getLong("z")

    /** Der Inhalt: alles, was innen steht, außer `z`. */
    private fun inhalt(innen: ByteArray): String =
        String(innen, Charsets.UTF_8).replaceFirst(Regex("\"z\":\\d+,"), "").also {
            assertFalse("z ließ sich herauslösen", it.contains("\"z\""))
        }

    // ---- Der Vektor der Referenz ------------------------------------------

    @Test fun vektorSchrittFuerSchritt() {
        val v = JSONObject(File("../../tools/proben/huelle/vektoren-2.json").readText()).getJSONObject("zaehler")
        assertEquals("der Vektor beginnt am frischen Gerät", 0L, v.getLong("letztes"))
        assertNull(stand.lesen())
        val schritte = v.getJSONArray("schritte").let { a -> (0 until a.length()).map { a.getJSONObject(it) } }
        assertEquals(12, schritte.size)

        val ids = mutableMapOf<String, Long>()
        val aenderungen = mutableMapOf<String, Int>()
        val gespielt = mutableListOf<String>()
        for ((i, s) in schritte.withIndex()) {
            jetzt = s.getLong("jetzt")
            val wo = "Schritt ${i + 1} (${s.getString("warum")})"
            when (s.getString("tat")) {
                "aendern" -> {
                    val name = s.getString("einsatz")
                    val n = aenderungen.merge(name, 1, Int::plus)!!
                    val id = ids[name]
                    when {
                        // Die erste Änderung: Der Einsatz entsteht.
                        id == null -> { ids[name] = einsatzAnlegen("am-$name"); gespielt += "anlegen" }
                        // Die letzte Änderung des Vektors: der Abschluss.
                        name == "c" && n == 2 -> { puffer.paketSchliessen(id, iso(jetzt), 100, 5); gespielt += "schliessen" }
                        else -> { phase(id, n + 2); gespielt += "phase" }
                    }
                    assertEquals("$wo: z am Paket", s.getLong("z"), z(ids[name]!!))
                }
                "verschliessen" -> {
                    assertEquals("$wo: z der Hülle", s.getLong("z"), zAus(innen(ids[s.getString("einsatz")]!!)))
                    gespielt += "verschliessen"
                }
                "trennen" -> {
                    assertTrue("$wo: der Vektor erwartet beim Trennen kein z", s.isNull("z"))
                    trennen()
                    gespielt += "trennen"
                }
                else -> error("$wo: unbekannte Tat ${s.getString("tat")}")
            }
            assertEquals("$wo: Gerätezähler danach", s.getLong("letztes"), stand.lesen())
        }
        println("Vektor zaehler: ${gespielt.size} Schritte gespielt — ${gespielt.groupingBy { it }.eachCount()}")
        assertEquals(12, gespielt.size)
        assertEquals(
            "alle drei Änderungen des Handys kamen vor",
            setOf("anlegen", "phase", "schliessen", "verschliessen", "trennen"), gespielt.toSet(),
        )
    }

    /**
     * Trennen, wie die App es tut: der echte [Kopplungsdienst] ohne Netz.
     * Tresor und Gerätepaar gehen, abgewiesene Pakete werden geräumt.
     */
    private fun trennen() {
        val datei = File(kontext.filesDir, "pruef_zaehler_tresor.bin").also { it.delete() }
        val tresor = Schluesseltresor(datei, PruefTresorschluessel())
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", Base64.getEncoder().encodeToString(Huellenoeffner.konto)))
        val gs = SpeicherGeraeteschluessel()
        val dienst = Kopplungsdienst(
            netzweg = object : Netzweg {
                override fun postJson(adresse: String, koerper: String, kopfzeilen: Map<String, String>) =
                    Netzantwort.KeineVerbindung("Prüffall")
            },
            tresor = tresor,
            rueckstand = { puffer.rueckstand() },
            raeumen = { puffer.abgewieseneRaeumen(null) },
            geraeteschluessel = gs,
        )
        assertTrue(dienst.trennen() is Trennergebnis.NurLokal)
        assertFalse("die Kopplung ist weg", tresor.gekoppelt())
        datei.delete()
    }

    /** Trennen lässt den Gerätezähler stehen — die Datei und die Zähler der Pakete. */
    @Test fun trennenLaesstDenZaehlerStehen() {
        jetzt = T0 + 7200                       // die Uhr ging vor
        val a = einsatzAnlegen("am-a")
        phase(a, 3)
        val vorher = stand.lesen()!!
        assertEquals(vorher, z(a))

        trennen()

        assertTrue("die Zählerdatei steht noch", zaehlerdatei.exists())
        assertEquals(vorher, stand.lesen())
        assertEquals("und der Zähler des Pakets auch", vorher, z(a))
        jetzt = T0                              // Uhr berichtigt, neue Kopplung
        val b = einsatzAnlegen("am-b")
        assertEquals("weiter über dem alten Stand", vorher + 1, z(b))
    }

    // ---- Die zwei Messsätze -----------------------------------------------

    /**
     * Ein Dienst mit zwei Einsätzen, allem, was das Handy an ihnen tut, und
     * Verschließen dazwischen. Nach jedem Schritt wird jede Hülle geöffnet;
     * über ALLE Paare der Aufnahmen müssen beide Sätze halten.
     */
    @Test fun gleichesZHeisstByteGleichUndAndererInhaltGroesseresZ() {
        data class Aufnahme(val schritt: String, val einsatz: String, val z: Long, val innen: ByteArray)
        val aufnahmen = mutableListOf<Aufnahme>()
        val ids = linkedMapOf<String, Long>()
        fun aufnehmen(schritt: String) {
            for ((name, id) in ids) repeat(2) {             // jede Hülle zweimal: Senden zählt nicht
                val i = innen(id)
                aufnahmen += Aufnahme(schritt, name, zAus(i), i)
                jetzt += 3
            }
        }

        ids["a"] = einsatzAnlegen("am-a"); aufnehmen("a entsteht")
        phase(ids["a"]!!, 2); aufnehmen("a Phase 2")
        puffer.punktAnhaengen(ids["a"]!!, Rohpunkt(47.56, 10.70, 800.0, jetzt)); aufnehmen("a Punkt (kein Inhalt der Hülle)")
        phase(ids["a"]!!, 3, breite = null); aufnehmen("a Phase 3 ohne Ort")
        phase(ids["a"]!!, 3); aufnehmen("a Phase 3 noch einmal, dieselbe Sekunde möglich")
        puffer.bestaetigungMerken(ids["a"]!!, 1); aufnehmen("a bestätigt (Buchführung)")
        puffer.paketSchliessen(ids["a"]!!, iso(jetzt), 1200, 30); aufnehmen("a abgeschlossen")
        ids["b"] = einsatzAnlegen("am-b"); aufnehmen("b entsteht")
        puffer.paketSchliessen(ids["a"]!!, iso(jetzt + 60), 1300, 31); aufnehmen("a noch einmal geschlossen (ohne Wirkung)")
        jetzt -= 3600                                       // die Uhr springt zurück
        phase(ids["b"]!!, 2); aufnehmen("b Phase 2 nach Uhrsprung")
        phase(ids["a"]!!, 9); aufnehmen("a Nachzügler-Phase am abgeschlossenen Einsatz")
        puffer.paketSchliessen(ids["b"]!!, iso(jetzt), null, null); aufnehmen("b abgeschlossen")

        var gleich = 0
        var anders = 0
        for (i in aufnahmen.indices) for (j in i + 1 until aufnahmen.size) {
            val x = aufnahmen[i]
            val y = aufnahmen[j]
            val wo = "${x.einsatz}@„${x.schritt}“ gegen ${y.einsatz}@„${y.schritt}“"
            if (x.z == y.z) {
                gleich++
                assertEquals("gleiches z, derselbe Einsatz: $wo", x.einsatz, y.einsatz)
                assertArrayEquals("gleiches z ⇒ innen byte-gleich: $wo", x.innen, y.innen)
            }
            if (x.einsatz != y.einsatz || inhalt(x.innen) != inhalt(y.innen)) {
                anders++
                assertNotEquals("anderer Inhalt ⇒ anderes z: $wo", x.z, y.z)
            }
            // Innerhalb eines Einsatzes ist die spätere Aufnahme der spätere Stand: Ihr z ist nie kleiner.
            if (x.einsatz == y.einsatz) assertTrue("z fällt nie: $wo", y.z >= x.z)
        }
        println("Messsätze: ${aufnahmen.size} Aufnahmen, $gleich Paare mit gleichem z, $anders Paare mit anderem Inhalt")
        assertTrue("es gab Paare mit gleichem z", gleich >= aufnahmen.size / 2)
        assertTrue("und Paare mit anderem Inhalt", anders > gleich)

        // Was kein Inhalt der Hülle ist, hat kein z gekostet — und was einer ist, je eines.
        val zFolge = aufnahmen.filter { it.einsatz == "a" }.map { it.schritt to it.z }.distinct()
        fun zVon(s: String) = zFolge.first { it.first.startsWith(s) }.second
        assertEquals("ein Punkt kostet kein z", zVon("a Phase 2"), zVon("a Punkt"))
        assertEquals("die Bestätigung kostet kein z", zVon("a Phase 3 noch einmal"), zVon("a bestätigt"))
        assertEquals("ein zweites Schließen kostet kein z", zVon("a abgeschlossen"), zVon("a noch einmal geschlossen"))
        assertTrue("Phase hinzu ⇒ größeres z", zVon("a Phase 2") > zVon("a entsteht"))
        assertTrue("dieselbe Phase noch einmal ⇒ größeres z", zVon("a Phase 3 noch einmal") > zVon("a Phase 3 ohne Ort"))
        assertTrue("Abschluss ⇒ größeres z", zVon("a abgeschlossen") > zVon("a bestätigt"))
        assertTrue("Nachzügler ⇒ größeres z", zVon("a Nachzügler") > zVon("a noch einmal geschlossen"))
    }

    /** Zwei Einsätze teilen EINEN Zähler: In der Reihenfolge der Änderungen steigt z streng, quer über beide. */
    @Test fun zweiEinsaetzeTeilenDenGeraetezaehler() {
        val folge = mutableListOf<Long>()
        val a = einsatzAnlegen("am-a"); folge += z(a)!!
        val b = einsatzAnlegen("am-b"); folge += z(b)!!           // dieselbe Sekunde
        phase(a, 2); folge += z(a)!!
        jetzt += 10
        phase(b, 2); folge += z(b)!!
        jetzt -= 500
        puffer.paketSchliessen(a, iso(jetzt), 0, 0); folge += z(a)!!
        puffer.paketSchliessen(b, iso(jetzt), 0, 0); folge += z(b)!!
        assertEquals(listOf(T0, T0 + 1, T0 + 2, T0 + 10, T0 + 11, T0 + 12), folge)
        assertEquals("die Datei hält den letzten", T0 + 12, stand.lesen())
    }

    /** Ein Ruhesegment trägt keine Einsatzhülle: kein Zähler, und der Gerätezähler bewegt sich nicht. */
    @Test fun ruhesegmentKostetKeinenZaehler() {
        val id = puffer.paketAnlegen("ar-1", Paketzeile.ART_RUHESEGMENT, "2026-07-16", "ad-1", iso(T0))
        puffer.punktAnhaengen(id, Rohpunkt(47.56, 10.70, 800.0, T0))
        puffer.paketSchliessen(id, iso(T0 + 60), null, null)
        assertNull(z(id))
        assertNull(puffer.einsatzstand(id, zaehlerSichern = true)!!.paket.zaehler)
        assertNull("nichts vergeben", stand.lesen())
    }

    // ---- Über den Sender: zwei Anfragen desselben Pakets --------------------

    private fun senderMitServer(): Triple<Sender, PruefServer, SpeicherGeraeteschluessel> {
        val server = PruefServer().also { offen += it }
        val datei = File(kontext.filesDir, "pruef_zaehler_sender.bin").also { it.delete() }
        val tresor = Schluesseltresor(datei, PruefTresorschluessel())
        tresor.speichern(
            Zugangsdaten("dev-pruef", "geheim-pruef", Base64.getEncoder().encodeToString(Huellenoeffner.konto), huelle2 = true),
        )
        val gs = SpeicherGeraeteschluessel()
        gs.beginnen()
        gs.festlegen(Huellenoeffner.konto, ByteArray(16))
        assertTrue(gs.uebernehmen())
        val sender = Sender(
            puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor, basis = server.basis,
            jetzt = { Instant.ofEpochSecond(jetzt) }, geraeteschluessel = gs,
        )
        // Wie der echte Server: `next_seq` ist, was er hat — auch bei einer Anfrage ohne `track`.
        var gespeichert = 0L
        server.antwortAus = { koerper ->
            val t = JSONObject(koerper).optJSONObject("track")
            if (t != null) gespeichert = maxOf(gespeichert, t.getLong("seq_from") + t.getInt("punkte"))
            """{"ok":true,"next_seq":$gespeichert}"""
        }
        return Triple(sender, server, gs)
    }

    private fun innenAus(anfrage: PruefServer.Anfrage, gs: SpeicherGeraeteschluessel): ByteArray {
        val o = JSONObject(anfrage.koerper)
        return Huellenoeffner.oeffnen2(
            Base64.getDecoder().decode(o.getString("ort_huelle")), gs.spk()!!, "einsatz", o.getString("client_ref"), "",
        )
    }

    /**
     * Der Sender selbst, gegen einen echten HTTP-Server: Der erste Versuch
     * scheitert am Server (500), der zweite — später, bei anderer Uhrzeit —
     * geht durch. Beide Anfragen tragen dasselbe z und innen dieselben
     * Bytes; der Gerätezähler hat sich vom Senden nicht bewegt. Dann eine
     * Phase: größeres z, anderer Inhalt. Dann der Abschluss: noch größer.
     */
    @Test fun senderZweiAnfragenEinZ() {
        val (sender, server, gs) = senderMitServer()
        val id = einsatzAnlegen("am-a")
        puffer.punktAnhaengen(id, Rohpunkt(47.56, 10.70, 800.0, T0 + 1))
        jetzt = T0 + 5
        phase(id, 2)
        val zVorher = z(id)!!
        assertEquals(T0 + 5, zVorher)

        server.status = 500
        jetzt = T0 + 60
        assertTrue(sender.sendeAlles().spaeterErneut)
        server.status = 200
        jetzt = T0 + 900
        assertTrue(sender.sendeAlles().sauber)

        assertEquals(2, server.anfragen.size)
        val erste = innenAus(server.anfragen[0], gs)
        val zweite = innenAus(server.anfragen[1], gs)
        assertEquals("das z der Änderung, nicht des Sendens", zVorher, zAus(erste))
        assertArrayEquals("gleiches z ⇒ innen byte-gleich über zwei Anfragen", erste, zweite)
        assertNotEquals(
            "außen jedes Mal neu verschlossen",
            JSONObject(server.anfragen[0].koerper).getString("ort_huelle"),
            JSONObject(server.anfragen[1].koerper).getString("ort_huelle"),
        )
        assertEquals("Senden bewegt den Gerätezähler nicht", zVorher, stand.lesen())
        assertEquals(zVorher, z(id))

        jetzt = T0 + 1000
        phase(id, 3)
        assertTrue(sender.sendeAlles().sauber)
        val dritte = innenAus(server.anfragen[2], gs)
        assertEquals("Phase hinzu ⇒ größeres z", T0 + 1000, zAus(dritte))
        assertNotEquals(inhalt(zweite), inhalt(dritte))
        assertFalse(JSONObject(String(dritte, Charsets.UTF_8)).getBoolean("schluss"))

        /* Der Abschluss, in derselben Sekunde: + 1. Davor ein Punkt, wie er
         * im Dienst fällt — er kostet kein z. (Bis F-KA-117 brauchte dieser
         * Fall den Punkt, sonst ging der Abschluss nicht hinaus; das misst
         * jetzt `SenderTest.einAbschlussOhneNeuenPunktGehtHinaus`.) */
        jetzt = T0 + 1000
        puffer.punktAnhaengen(id, Rohpunkt(47.57, 10.71, 801.0, jetzt))
        assertEquals("ein Punkt kostet kein z", T0 + 1000, z(id))
        puffer.paketSchliessen(id, iso(jetzt), 10, 1)
        val bericht = sender.sendeAlles()
        assertEquals(4, server.anfragen.size)
        val vierte = innenAus(server.anfragen[3], gs)
        assertEquals("Abschluss ⇒ größeres z", T0 + 1001, zAus(vierte))
        assertTrue(JSONObject(String(vierte, Charsets.UTF_8)).getBoolean("schluss"))
        assertEquals(inhalt(dritte).replace("\"schluss\":false", "\"schluss\":true"), inhalt(vierte))
        assertEquals(1, bericht.fertigePakete)
        assertNull("bestätigt und entsorgt", puffer.paket(id))
    }

    // ---- Prozesstod und Ausfall -------------------------------------------

    /**
     * Der Prozess stirbt, NACHDEM der Gerätezähler abgelegt ist und BEVOR
     * die Änderung im Puffer steht — gespielt als Transaktion, die nicht
     * festgeschrieben wird. Die Zahl ist verbraucht; der Einsatz steht mit
     * altem Inhalt UND altem Zähler da; die nächste Änderung bekommt eine
     * Zahl, die es noch nie gab.
     */
    @Test fun todZwischenZaehlerUndAenderung() {
        val id = einsatzAnlegen("am-a")
        val vorher = innen(id)
        assertEquals(T0, zAus(vorher))

        assertTrue(runCatching {
            puffer.imVorgang {
                phase(id, 2)
                error("Prozesstod")
            }
        }.isFailure)

        assertEquals("die Zahl ist verbraucht", T0 + 1, stand.lesen())
        assertEquals("das Paket trägt den alten Zähler", T0, z(id))
        assertTrue("und den alten Inhalt", puffer.phasen(id).isEmpty())
        assertArrayEquals("gleiches z ⇒ innen byte-gleich", vorher, innen(id))

        phase(id, 2)
        assertEquals("die verbrauchte Zahl kommt nicht wieder", T0 + 2, z(id))
    }

    /**
     * Neustart: Puffer und Zähler neu geöffnet, auf denselben Dateien, die
     * Uhr inzwischen zurückgestellt. Der Zähler des Pakets ist derselbe, die
     * Hülle innen byte-gleich; die nächste Änderung liegt darüber.
     */
    @Test fun ueberNeustartHinweg() {
        val id = einsatzAnlegen("am-a")
        jetzt = T0 + 30
        phase(id, 2)
        val vorher = innen(id)

        puffer.close()
        jetzt = T0 - 86_400
        stand = DateiZaehlerstand(zaehlerdatei)
        puffer = pufferOeffnen()

        assertEquals(T0 + 30, z(id))
        assertArrayEquals(vorher, innen(id))
        assertArrayEquals("auch beim zweiten Verschließen", vorher, innen(id))
        phase(id, 3)
        assertEquals(T0 + 31, z(id))
    }

    /**
     * Der Gerätezähler lässt sich nicht ablegen. Die Aufzeichnung geht vor:
     * Die Phase steht im Puffer. Das Paket steht dann OHNE Zähler da — nicht
     * mit dem alten, der zu anderem Inhalt gehörte. Verschlossen wird so
     * nichts; der Sender sendet nichts und meldet „später erneut". Geht die
     * Ablage wieder, vergibt das erste Verschließen einmal, und jede weitere
     * Anfrage trägt dasselbe z.
     */
    @Test fun zaehlerausfallVerliertKeinePhaseUndErfindetKeinZ() {
        var kaputt = false
        val wackelig = object : Zaehlerstand {
            override fun lesen() = stand.lesen()
            override fun schreiben(z: Long) = if (kaputt) throw java.io.IOException("Platte voll") else stand.schreiben(z)
        }
        puffer.close()
        puffer = pufferOeffnen(wackelig)
        val (sender, server, gs) = senderMitServer()

        val id = einsatzAnlegen("am-a")
        assertEquals(T0, z(id))
        kaputt = true
        jetzt = T0 + 50
        phase(id, 2)
        assertEquals("die Phase ist da", 1, puffer.phasen(id).size)
        assertNull("und der alte Zähler ist weg", z(id))

        assertTrue(runCatching { puffer.einsatzstand(id, zaehlerSichern = true) }.exceptionOrNull() is Zaehlerausfall)
        val bericht = sender.sendeAlles()
        assertTrue(bericht.spaeterErneut)
        assertEquals(0, bericht.anfragen)
        assertTrue("nichts ist hinausgegangen", server.anfragen.isEmpty())
        assertEquals(0, puffer.abgewiesen())
        assertNull(z(id))

        kaputt = false
        jetzt = T0 + 400
        server.status = 500
        sender.sendeAlles()
        server.status = 200
        jetzt = T0 + 800
        assertTrue(sender.sendeAlles().sauber)
        assertEquals(2, server.anfragen.size)
        val erste = innenAus(server.anfragen[0], gs)
        assertEquals("einmal vergeben, beim ersten Verschließen", T0 + 400, zAus(erste))
        assertArrayEquals(erste, innenAus(server.anfragen[1], gs))
        assertEquals(T0 + 400, z(id))
        assertNotNull(stand.lesen())
    }

    /** Fassung 1 kennt keinen Zähler: Der Sender vergibt für sie keinen, und die Hülle geht hinaus. */
    @Test fun fassung1VergibtNichtsBeimSenden() {
        val server = PruefServer().also { offen += it }
        val datei = File(kontext.filesDir, "pruef_zaehler_sender1.bin").also { it.delete() }
        val tresor = Schluesseltresor(datei, PruefTresorschluessel())
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", Base64.getEncoder().encodeToString(Huellenoeffner.konto)))
        var kaputt = false
        puffer.close()
        puffer = pufferOeffnen(object : Zaehlerstand {
            override fun lesen() = stand.lesen()
            override fun schreiben(z: Long) = if (kaputt) throw java.io.IOException("Platte voll") else stand.schreiben(z)
        })
        val id = einsatzAnlegen("am-a")
        kaputt = true
        phase(id, 2)                                         // ohne Zähler
        assertNull(z(id))

        val sender = Sender(puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor, basis = server.basis)
        server.antwortkoerper = """{"ok":true,"next_seq":0}"""
        assertTrue(sender.sendeAlles().sauber)
        assertEquals(1, server.anfragen.size)
        assertEquals(1, Base64.getDecoder().decode(server.letzterKoerper().getString("ort_huelle"))[0].toInt())
        assertNull("Fassung 1 hat keinen vergeben", z(id))
    }

    private companion object {
        const val DATENBANK = "pruef_einsatzzaehler.db"

        /** Wie `T0` der Referenz. */
        const val T0 = 1_784_277_065L
    }
}
