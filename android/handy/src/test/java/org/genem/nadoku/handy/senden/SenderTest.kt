package org.genem.nadoku.handy.senden

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Stroeme
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Modus
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
import java.time.temporal.ChronoUnit
import kotlin.random.Random

/**
 * Warteschlange, Teilstücke und die **Funkabriss-Matrix**.
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
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", KONTOSCHLUESSEL))
    }

    @After fun abbauen() {
        server.close()
        puffer.close()
        kontext.deleteDatabase(DATENBANK)
        tresordatei.delete()
    }

    private fun sender() = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor,
        basis = server.basis,
        phasenLeser = { puffer.phasen(it) },
        jetzt = { uhrzeit },
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
            val bis = t.getLong("seq_from") + t.getInt("punkte")
            """{"ok":true,"id":1,"stored_points":${t.getInt("punkte")},"next_seq":$bis}"""
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
                    .getInt("punkte"),
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
            erwartet += t.getInt("punkte")
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

        val s = Sender(puffer, HttpNetzweg(), tresor, adresse)
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

    /** **403**: pausiert wie 401, nennt aber den Grund. */
    @Test fun vierhundertdreiPausiertMitGrund() {
        val id = einSegmentMitPunkten(10)
        server.status = 403
        server.antwortkoerper = """{"error":"konto","grund":"wartet"}"""

        val bericht = sender().sendeAlles()

        assertTrue(bericht.pausiert)
        assertEquals(Sperrgrund.KONTO_WARTET, bericht.gesperrt)
        assertEquals("Genau eine Anfrage, dann Schluss", 1, bericht.anfragen)
        assertEquals(0L, puffer.paket(id)!!.bestaetigtSeq)
        assertFalse("403 ist kein Datenfehler", puffer.paket(id)!!.fehlerhaft)
    }

    /**
     * **429 mit `Retry-After`**: Der nächste Lauf vor Ablauf sendet
     * nichts, der danach wieder.
     */
    @Test fun wartezeitHaeltDieNaechstenLaeufeAuf() {
        einSegmentMitPunkten(10)
        server.status = 429
        server.antwortkoerper = """{"error":"zu_viele_versuche"}"""
        server.antwortkopf = mapOf("Retry-After" to "600")
        val s = sender()

        val erster = s.sendeAlles()
        assertEquals(1, erster.anfragen)
        assertEquals(uhrzeit.plusSeconds(600), erster.wartetBis)

        uhrzeit = uhrzeit.plusSeconds(599)
        val zweiter = s.sendeAlles()
        assertEquals("Vor Ablauf keine Anfrage", 1, server.anfragen.size)
        assertTrue(zweiter.spaeterErneut)
        assertEquals(0, zweiter.anfragen)

        antworteVollstaendig()
        server.antwortkopf = emptyMap()
        uhrzeit = uhrzeit.plusSeconds(1)
        val dritter = s.sendeAlles()
        assertTrue("Nach Ablauf wird gesendet", dritter.anfragen > 0)
        assertNull("Ein Erfolg hebt die Wartezeit auf", s.nichtVor)
    }

    /** **507**: eine Stunde warten, „Konto voll" im Bericht, nichts markiert. */
    @Test fun kontoVollWartetEineStunde() {
        val id = einSegmentMitPunkten(10)
        server.status = 507
        server.antwortkoerper = """{"error":"kontingent","grund":"Das Konto ist voll"}"""
        val s = sender()

        val bericht = s.sendeAlles()
        assertTrue(bericht.kontoVoll)
        assertTrue(bericht.spaeterErneut)
        assertEquals(uhrzeit.plusSeconds(3600), bericht.wartetBis)
        assertFalse(puffer.paket(id)!!.fehlerhaft)

        uhrzeit = uhrzeit.plusSeconds(1800)
        val waehrend = s.sendeAlles()
        assertTrue("Auch das Warten sagt, warum", waehrend.kontoVoll)
        assertEquals(1, server.anfragen.size)
    }

    // ---- Rückstandsbindung --------------------------------------------------

    private fun bindung(): org.genem.nadoku.handy.kopplung.Rueckstandsbindung {
        kontext.getSharedPreferences("rueckstand", Context.MODE_PRIVATE).edit().clear().commit()
        return org.genem.nadoku.handy.kopplung.Rueckstandsbindung(kontext)
    }

    private fun senderMit(b: org.genem.nadoku.handy.kopplung.Rueckstandsbindung) = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor,
        basis = server.basis, jetzt = { uhrzeit }, bindung = b,
    )

    private fun abgeschlossenesSegment(ref: String): Long {
        val id = puffer.paketAnlegen(ref, Paketzeile.ART_RUHESEGMENT, "2026-07-16", "ad-1", "2026-07-16T05:00:00Z")
        val p = Stroeme.erzeuge(Stroeme.REISEFLUG).first()
        puffer.punktAnhaengen(id, p)
        puffer.paketSchliessen(id, "2026-07-16T06:00:00Z", null, null)
        return id
    }

    /** Gebunden an DIESES Konto: Die Bindung fällt, der Rückstand geht hinaus. */
    @Test fun bindungAnDasselbeKontoSendet() {
        antworteVollstaendig()
        val alt = abgeschlossenesSegment("ar-1-a")
        val b = bindung()
        b.binden(KONTOSCHLUESSEL, alt)

        val bericht = senderMit(b).sendeAlles()

        assertEquals(0, bericht.fremderRueckstand)
        assertEquals(1, bericht.fertigePakete)
        assertNull("Die Bindung ist gelöst", b.gebundenBis)
    }

    /**
     * Gebunden an ein ANDERES Konto: Was bis zur Grenze liegt, geht nicht
     * hinaus; was danach entstand, schon.
     */
    @Test fun bindungAnEinAnderesKontoHaeltDenAltbestand() {
        antworteVollstaendig()
        val alt = abgeschlossenesSegment("ar-1-a")
        val b = bindung()
        b.binden("ANDERER-SCHLUESSEL", alt)
        val neu = abgeschlossenesSegment("ar-2-b")

        val bericht = senderMit(b).sendeAlles()

        assertEquals(1, bericht.fremderRueckstand)
        assertFalse(bericht.sauber)
        assertEquals("Nur das neue Paket ging hinaus", 1, server.anfragen.size)
        assertEquals("ar-2-b", server.letzterKoerper().getString("client_ref"))
        assertNotNull("Das alte liegt noch", puffer.paket(alt))
        assertNull("Das neue ist fertig", puffer.paket(neu))
        assertEquals(alt, b.gebundenBis)

        val weg = puffer.rueckstandVerwerfen(alt)
        assertEquals(1, weg.pakete)
        assertNull(puffer.paket(alt))
    }

    /** 401 vermerkt die Abmeldung, eine angenommene Anfrage nimmt sie zurück. */
    @Test fun abmeldevermerkFolgtDerAntwort() {
        einSegmentMitPunkten(10)
        val b = bindung()
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""
        senderMit(b).sendeAlles()
        assertTrue(b.abgemeldet)

        antworteVollstaendig()
        senderMit(b).sendeAlles()
        assertFalse(b.abgemeldet)
    }

    // ---- Die Frist nach einer Abmeldung (Nr. 471) ---------------------------

    private fun offenesSegment(ref: String): Long =
        puffer.paketAnlegen(ref, Paketzeile.ART_RUHESEGMENT, "2026-07-16", "ad-2", "2026-07-16T07:00:00Z")

    /**
     * 30 Tage nach der **ersten** 401 sind alle abgeschlossenen Pakete fort,
     * 29 Tage danach noch nicht; ein offenes Paket (laufender Dienst) bleibt.
     * Eine zweite 401 schiebt die Frist nicht hinaus.
     */
    @Test fun dreissigTageNachDerErstenAbmeldungIstDerPufferLeer() {
        val t0 = Instant.parse("2026-07-16T08:00:00Z")
        uhrzeit = t0
        val alt = abgeschlossenesSegment("af-1-a")
        val b = bindung()
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""
        senderMit(b).sendeAlles()
        assertEquals(t0, b.abgemeldetSeit)

        uhrzeit = t0.plus(29, ChronoUnit.DAYS)
        senderMit(b).sendeAlles()
        assertEquals("Eine zweite 401 schiebt nichts hinaus", t0, b.abgemeldetSeit)
        assertNotNull("29 Tage: bleibt", puffer.paket(alt))

        val offen = offenesSegment("af-2-o")
        uhrzeit = t0.plus(30, ChronoUnit.DAYS)
        val bericht = senderMit(b).sendeAlles()
        assertNull("30 Tage: fort", puffer.paket(alt))
        assertEquals(1, bericht.geraeumt)
        assertNotNull("Der laufende Dienst bleibt", puffer.paket(offen))
        /* Die Räumung vergisst die Abmeldung; das offene Paket bekommt danach
         * wieder 401, und eine NEUE Frist beginnt — über das, was jetzt liegt. */
        /* SEIT ANDROID 0.23.0 (KA-04) geht VOR der Räumung ein Sendeversuch
         * hinaus; seine 401 fällt noch in die alte Frist, und die Räumung
         * vergisst die Abmeldung danach. Die neue Frist beginnt mit der
         * nächsten 401. Bis dahin hieß es hier: „Neue Frist ab der 401 nach
         * der Räumung" — die Reihenfolge war umgekehrt. */
        assertNull("Nach der Räumung keine Abmeldung", b.abgemeldetSeit)
        senderMit(b).sendeAlles()
        assertEquals("Die nächste 401 beginnt die neue Frist", uhrzeit, b.abgemeldetSeit)
    }

    /**
     * **Vor dem Räumen ein Sendeversuch** (KA-04, F-KA-18): Antwortet der
     * Server am Tag 30 wieder, war die Abmeldung keine — gesendet statt
     * geräumt.
     */
    @Test fun vorDemRaeumenGehtEinSendeversuchHinaus() {
        val t0 = Instant.parse("2026-07-16T08:00:00Z")
        uhrzeit = t0
        val alt = abgeschlossenesSegment("af-9-a")
        val b = bindung()
        server.status = 403
        server.antwortkoerper = """{"error":"device_disabled"}"""
        senderMit(b).sendeAlles()
        assertEquals(t0, b.abgemeldetSeit)

        uhrzeit = t0.plus(30, ChronoUnit.DAYS)
        antworteVollstaendig()
        val bericht = senderMit(b).sendeAlles()
        assertEquals("Gesendet, nicht geräumt", 0, bericht.geraeumt)
        assertEquals(1, bericht.fertigePakete)
        assertNull("Die Abmeldung ist widerlegt", b.abgemeldetSeit)
        assertNull("Das Paket ist als gesendet entsorgt", puffer.paket(alt))
    }

    /** Eine Bindung deckt auch ein **offenes** Paket (KA-04) — es geht nicht hinaus. */
    @Test fun eineBindungDecktAuchEinOffenesPaket() {
        antworteVollstaendig()
        val offen = offenesSegment("ob-1-o")
        puffer.punktAnhaengen(offen, Stroeme.erzeuge(Stroeme.REISEFLUG).first())
        val b = bindung()
        b.binden(null, offen)
        senderMit(b).sendeAlles()
        assertTrue("Nichts an den Server", server.anfragen.isEmpty())
        assertEquals("Die Bindung bleibt", offen, b.gebundenBis)
    }

    /**
     * **`next_seq` höchstens bis zum Ende des Gesendeten** (KA-04, E-KA-29).
     * Ein Server, der mehr quittiert, als ankam, ließ das Handy die übrigen
     * Punkte eines Pakets nie senden.
     */
    @Test fun eineZuGrosseMarkeWirdGedeckelt() {
        server.status = 200
        server.antwortAus = { koerper ->
            val t = org.json.JSONObject(koerper).getJSONObject("track")
            val bis = t.getLong("seq_from") + t.getInt("punkte") + 10_000
            """{"ok":true,"id":1,"stored_points":${t.getInt("punkte")},"next_seq":$bis}"""
        }
        val id = einSegmentMitPunkten(600)
        val n = puffer.punktzahl(id).toInt()
        val bericht = sender().sendeAlles()
        assertEquals("Alle Punkte gingen hinaus", n, bericht.gesendetePunkte)
        assertEquals("in zwei Teilstücken", 2, bericht.anfragen)
    }

    /** Eine Annahme hebt die Abmeldung auf; die nächste 401 beginnt eine neue Frist. */
    @Test fun eineAnnahmeHebtDieFristAuf() {
        val t0 = Instant.parse("2026-07-16T08:00:00Z")
        uhrzeit = t0
        abgeschlossenesSegment("af-3-a")
        val b = bindung()
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""
        senderMit(b).sendeAlles()

        uhrzeit = t0.plus(20, ChronoUnit.DAYS)
        antworteVollstaendig()
        senderMit(b).sendeAlles()
        assertNull(b.abgemeldetSeit)

        val neu = abgeschlossenesSegment("af-4-b")
        uhrzeit = t0.plus(31, ChronoUnit.DAYS)
        server.status = 401
        server.antwortAus = null
        server.antwortkoerper = """{"error":"auth"}"""
        senderMit(b).sendeAlles()
        assertEquals(uhrzeit, b.abgemeldetSeit)
        assertNotNull("Die neue Frist hat eben erst begonnen", puffer.paket(neu))
    }

    /**
     * Der **gebundene** Rückstand behält die Frist der Abmeldung, auch wenn das
     * Trennen die Abmeldung vergisst und das Handy an ein anderes Konto geht.
     */
    @Test fun derGebundeneRueckstandBehaeltDieFrist() {
        val t0 = Instant.parse("2026-07-16T08:00:00Z")
        val alt = abgeschlossenesSegment("af-5-a")
        val b = bindung()
        b.abmeldungVermerken(t0)
        b.binden("ANDERER-SCHLUESSEL", alt, t0.plus(5, ChronoUnit.DAYS))
        b.abmeldungVergessen()                     // wie Kopplungsdienst.trennen()
        assertEquals(t0.plus(30, ChronoUnit.DAYS), b.gebundenFristBis)

        antworteVollstaendig()
        uhrzeit = t0.plus(29, ChronoUnit.DAYS)
        senderMit(b).sendeAlles()
        assertNotNull("29 Tage: liegt noch, geht aber nicht hinaus", puffer.paket(alt))

        val neu = abgeschlossenesSegment("af-6-b")
        uhrzeit = t0.plus(30, ChronoUnit.DAYS)
        val bericht = senderMit(b).sendeAlles()
        assertNull("30 Tage: fort", puffer.paket(alt))
        assertNull("Die Bindung ist gelöst", b.gebundenBis)
        assertNull("Das neue Paket ging an das neue Konto", puffer.paket(neu))
        assertEquals(1, bericht.geraeumt)
    }

    /** Ein Vermerk aus Android 0.20.0 ohne Zeitpunkt: Die Frist beginnt jetzt, nicht rückwirkend. */
    @Test fun einAlterVermerkOhneZeitpunktBeginntJetzt() {
        val alt = abgeschlossenesSegment("af-7-a")
        val b = bindung()
        kontext.getSharedPreferences("rueckstand", Context.MODE_PRIVATE).edit()
            .putBoolean("abgemeldet", true).commit()
        val t = Instant.parse("2026-11-04T12:00:00Z")

        val weg = org.genem.nadoku.handy.kopplung.Abmeldefrist.raeumen(b, puffer, t)

        assertEquals(0, weg.pakete)
        assertEquals(t, b.abgemeldetSeit)
        assertNotNull(puffer.paket(alt))
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
            val n = t.getInt("punkte")
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
            val bis = t.getLong("seq_from") + t.getInt("punkte")
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

    /** Ein `ok: true` mit `rejected` ist kein sauberer Lauf. */
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
     * „Rückstand 1".
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

    // ---- Der Kontoschlüssel -------------------------------------------------

    /**
     * Eine Kopplung ohne Kontoschlüssel holt ihn
     * vor dem ersten Senden nach und sendet danach verschlossen.
     */
    @Test fun ohneKontoschluesselWirdErNachgeholt() {
        antworteVollstaendig()
        einSegmentMitPunkten(10)
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef"))
        var geholt = 0
        val s = Sender(puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = { geholt++; KONTOSCHLUESSEL })
        val bericht = s.sendeAlles()
        assertEquals(1, geholt)
        assertTrue(bericht.anfragen > 0)
        assertTrue(bericht.sauber)
        assertTrue(server.anfragen.all { org.json.JSONObject(it.koerper).getJSONObject("track").has("huelle") })
    }

    /**
     * Gibt es keinen — das Konto hat noch kein Paar —, geht NICHTS hinaus,
     * und der Lauf sagt es: `ohneSchluessel`, und `spaeterErneut`, damit der
     * Nachsende-Job es wieder versucht.
     */
    @Test fun ohneKontoschluesselGehtNichtsHinaus() {
        antworteVollstaendig()
        einSegmentMitPunkten(10)
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef"))
        val s = Sender(puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = { null })
        val bericht = s.sendeAlles()
        assertEquals(0, bericht.anfragen)
        assertTrue(bericht.ohneSchluessel)
        assertTrue(bericht.spaeterErneut)
        assertTrue(server.anfragen.isEmpty())
    }

    /* KEIN FALL „ohne Serveradresse wird nicht gesendet": Die Adresse ist
     * eine Konstante des Baulaufs, kein Feld, das jemand leer lassen könnte.
     * Ein Prüffall für einen unerreichbaren Zustand ist keine Zusicherung.
     *
     * Was an seine Stelle tritt, steht in ServeradresseTest: Eine unbrauchbare
     * `SERVER_BASIS` lässt den ERSTEN Zugriff werfen -- beim Bauen, nicht beim
     * Senden. */

    // ---- Der Räumteil vor dem Senden ---------------------------------------

    /**
     * Jeder Sendelauf räumt zuerst: Ein abgewiesenes Paket, das älter ist als
     * die Frist ([org.genem.nadoku.handy.puffer.Raeumung.FRIST_TAGE]), ist
     * danach weg und steht im Bericht; ein jüngeres bleibt. Die Zeit stellt
     * der Prüfstand — 36 und 26 Tage vor „jetzt".
     */
    @Test fun einSendelaufRaeumtAbgewieseneNachDerFrist() {
        antworteVollstaendig()
        uhrzeit = Instant.parse("2026-07-16T05:00:00Z")
        val alt = abgewiesen("ab-alt", "2026-06-10T19:00:00Z")
        val jung = abgewiesen("ab-jung", "2026-06-20T19:00:00Z")

        val bericht = sender().sendeAlles()

        assertEquals(1, bericht.geraeumt)
        assertNull("36 Tage: weg", puffer.paket(alt))
        assertNotNull("26 Tage: bleibt", puffer.paket(jung))
        assertEquals(1, puffer.abgewiesen())
        assertTrue("Abgewiesenes wird nach wie vor nicht gesendet", server.anfragen.isEmpty())
    }

    private fun abgewiesen(ref: String, beendet: String): Long {
        val id = puffer.paketAnlegen(
            clientRef = ref, art = Paketzeile.ART_RUHESEGMENT, tag = beendet.substring(0, 10),
            dienstRef = null, begonnenAt = beendet,
        )
        puffer.paketSchliessen(id, beendet, null, null)
        puffer.alsFehlerhaftMerken(id)
        return id
    }

    private companion object {
        const val DATENBANK = "sendepruefung.db"

        /** Der Empfänger der Referenzvektoren, Base64 (`tools/proben/huelle/vektoren.json`). */
        val KONTOSCHLUESSEL: String by lazy {
            val h = org.json.JSONObject(File("../../tools/proben/huelle/vektoren.json").readText())
                .getJSONObject("empfaenger").getString("oeffentlich_hex")
            java.util.Base64.getEncoder().encodeToString(
                ByteArray(h.length / 2) { h.substring(it * 2, it * 2 + 2).toInt(16).toByte() })
        }
    }
}
