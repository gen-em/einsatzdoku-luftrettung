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

    /**
     * Das gültige Gerätepaar der Grundkopplung. Seit Android 0.27.0 (KA-21)
     * sendet der Sender nur in Fassung 2: Jeder Fall, der etwas hinausgehen
     * sehen will, braucht `huelle: 2` im Tresor UND ein Paar. Bis dahin lief
     * die Grundkopplung ohne beides und sendete Fassung 1.
     */
    private lateinit var gs: org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel

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
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", KONTOSCHLUESSEL, huelle2 = true))
        gs = gueltigesPaar()
    }

    /** Ein Gerätepaar, das eine Kopplung mit Vergleichswert hinterlassen hätte. */
    private fun gueltigesPaar() = org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel().also {
        it.beginnen()
        it.festlegen(Huellenoeffner.konto, ByteArray(16))
        assertTrue(it.uebernehmen())
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
        jetzt = { uhrzeit }, geraeteschluessel = gs,
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

        val s = Sender(puffer, HttpNetzweg(), tresor, adresse, geraeteschluessel = gs)
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
        basis = server.basis, jetzt = { uhrzeit }, bindung = b, geraeteschluessel = gs,
    )

    private fun abgeschlossenesSegment(ref: String): Long {
        val id = puffer.paketAnlegen(ref, Paketzeile.ART_RUHESEGMENT, "2026-07-16", "ad-1", "2026-07-16T05:00:00Z")
        val p = Stroeme.erzeuge(Stroeme.REISEFLUG).first()
        puffer.punktAnhaengen(id, p)
        puffer.paketSchliessen(id, "2026-07-16T06:00:00Z", null, null)
        return id
    }

    /**
     * F-KA-117: Ein Paket, dessen Punkte alle bestätigt sind, wird ohne neuen
     * Punkt abgeschlossen. Der Abschluss ist selbst Arbeit — `final` und
     * `ended_at` müssen hinaus, und erst danach ist das Paket fertig. Bis
     * Android 0.24.x blieb es liegen, ohne Anfrage und ohne Rückstand.
     */
    @Test fun einAbschlussOhneNeuenPunktGehtHinaus() {
        server.status = 200
        server.antwortAus = { koerper ->
            val o = org.json.JSONObject(koerper)
            val bis = o.optJSONObject("track")?.let { it.getLong("seq_from") + it.getInt("punkte") } ?: 1L
            """{"ok":true,"id":1,"stored_points":0,"next_seq":$bis}"""
        }
        val id = offenesSegment("ab-1-a")
        puffer.punktAnhaengen(id, Stroeme.erzeuge(Stroeme.REISEFLUG).first())
        sender().sendeAlles()
        assertEquals("der Teil-Upload ging hinaus", 1, server.anfragen.size)
        assertFalse(puffer.hatArbeit(puffer.paket(id)!!))

        puffer.paketSchliessen(id, "2026-07-16T08:00:00Z", null, null)
        assertTrue("der Abschluss ist Arbeit", puffer.hatArbeit(puffer.paket(id)!!))
        val bericht = sender().sendeAlles()

        assertEquals("der Abschluss ging hinaus", 2, server.anfragen.size)
        val k = server.letzterKoerper()
        assertTrue(k.getBoolean("final"))
        assertEquals("2026-07-16T08:00:00Z", k.getString("ended_at"))
        /* Seit 0.27.0 (KA-21) ist jede Anfrage Fassung 2, und dort trägt der
         * Abschluss ohne offenen Punkt das leere Schlussstück (Vertrag 3.4).
         * Bis dahin lief dieser Fall in Fassung 1 und ging ohne `track`. */
        val t = k.getJSONObject("track")
        assertEquals("das leere Schlussstück", 0, t.getInt("punkte"))
        assertEquals(1L, t.getLong("seq_from"))
        assertEquals(2, java.util.Base64.getDecoder().decode(t.getString("huelle"))[0].toInt())
        assertEquals(1, bericht.fertigePakete)
        assertNull("bestätigt und entsorgt", puffer.paket(id))
    }

    // ---- Eine Änderung, während die Anfrage unterwegs ist (Nr. 566) ---------

    /**
     * Antwortet wie der echte Server und führt bei der ERSTEN Anfrage
     * [dazwischen] aus — nachdem der Sender den Körper gebaut hat und bevor
     * seine Antwort eintrifft. Genau dort fällt im Dienst eine Phase.
     */
    private fun antworteUndAendereDazwischen(dazwischen: () -> Unit) {
        server.status = 200
        var erste = true
        var gespeichert = 0L                    // wie der Server: was er hat, behält er
        server.antwortAus = { koerper ->
            if (erste) { erste = false; dazwischen() }
            val o = org.json.JSONObject(koerper)
            o.optJSONObject("track")?.let { gespeichert = maxOf(gespeichert, it.getLong("seq_from") + it.getInt("punkte")) }
            """{"ok":true,"id":1,"stored_points":0,"next_seq":$gespeichert}"""
        }
    }

    private fun einsatzMitPhase(ref: String): Long {
        val id = puffer.paketAnlegen(ref, Paketzeile.ART_EINSATZ, "2026-07-16", "ad-3", "2026-07-16T07:00:00Z")
        puffer.phaseAnhaengen(id, 3, "2026-07-16T07:00:00Z", null, null, Dienstklammer.QUELLE_HANDY)
        return id
    }

    /**
     * Eine Phase fällt, während der Teil-Upload eines OFFENEN Einsatzes
     * unterwegs ist. Die Antwort gilt dem Stand mit einer Phase; die zweite
     * muss trotzdem hinaus, im selben Lauf — ohne neuen Punkt. Bis Android
     * 0.25.x setzte die Antwort `metadaten_bestaetigt` zurück auf 1, und die
     * Phase wartete auf den nächsten Punkt (beim stehenden Fahrzeug: auf den
     * Abschluss).
     */
    @Test fun einePhaseWaehrendDerAnfrageGehtNochHinaus() {
        val id = einsatzMitPhase("am-566-a")
        antworteUndAendereDazwischen {
            puffer.phaseAnhaengen(id, 4, "2026-07-16T07:05:00Z", null, null, Dienstklammer.QUELLE_HANDY)
        }
        sender().sendeAlles()

        assertEquals("die zweite Phase ging im selben Lauf hinaus", 2, server.anfragen.size)
        assertEquals(1, org.json.JSONObject(server.anfragen[0].koerper).getJSONArray("phases").length())
        assertEquals(2, server.letzterKoerper().getJSONArray("phases").length())
        assertFalse("danach ist nichts mehr offen", puffer.hatArbeit(puffer.paket(id)!!))
    }

    /**
     * Ein Ruhesegment wird geschlossen, während sein Teil-Upload unterwegs ist
     * (der Einsatz beginnt). Der Abschluss ist Arbeit (F-KA-117) und darf von
     * der Antwort auf den offenen Stand nicht verschluckt werden.
     */
    @Test fun einAbschlussWaehrendDerAnfrageGehtNochHinaus() {
        val id = offenesSegment("ar-566-b")
        puffer.punktAnhaengen(id, Stroeme.erzeuge(Stroeme.REISEFLUG).first())
        antworteUndAendereDazwischen {
            puffer.paketSchliessen(id, "2026-07-16T08:00:00Z", null, null)
        }
        val bericht = sender().sendeAlles()

        assertEquals("der Abschluss ging im selben Lauf hinaus", 2, server.anfragen.size)
        assertFalse(org.json.JSONObject(server.anfragen[0].koerper).getBoolean("final"))
        assertTrue(server.letzterKoerper().getBoolean("final"))
        assertEquals(1, bericht.fertigePakete)
        assertNull("bestätigt und entsorgt", puffer.paket(id))
    }

    /**
     * Die Uhr reicht einem ABGESCHLOSSENEN Einsatz eine Phase nach, während
     * seine letzte Anfrage unterwegs ist. Ohne den Abgleich des Stands wäre
     * das Paket nach der Antwort entsorgt worden — samt der nachgereichten
     * Phase, die der Server nie gesehen hat.
     */
    @Test fun eineNachgereichtePhaseWirdNichtMitEntsorgt() {
        val id = einsatzMitPhase("am-566-c")
        puffer.paketSchliessen(id, "2026-07-16T07:30:00Z", 100, 1)
        antworteUndAendereDazwischen {
            puffer.phaseAnhaengen(id, 9, "2026-07-16T07:29:00Z", null, null, "uhr")
        }
        val bericht = sender().sendeAlles()

        assertEquals("der nachgereichte Stand ging hinaus", 2, server.anfragen.size)
        assertEquals(1, org.json.JSONObject(server.anfragen[0].koerper).getJSONArray("phases").length())
        assertEquals(2, server.letzterKoerper().getJSONArray("phases").length())
        assertEquals("erst nach dem zweiten Stand entsorgt", 1, bericht.fertigePakete)
        assertNull(puffer.paket(id))
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
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", huelle2 = true))
        var geholt = 0
        val s = Sender(puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = { geholt++; KONTOSCHLUESSEL }, geraeteschluessel = gs)
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
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef", huelle2 = true))
        val s = Sender(puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = { null }, geraeteschluessel = gs)
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

    // ---- Fassung 2 über den Geräteschlüssel (seit KA-06f) -------------------

    /** Ein abgeschlossenes Ruhesegment mit ein paar Punkten und eine Kopplung, die Fassung 2 verlangt. */
    private fun fassung2Vorbereiten(): org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel {
        tresor.speichern(Zugangsdaten(
            "dev-pruef", "geheim-pruef", java.util.Base64.getEncoder().encodeToString(Huellenoeffner.konto), huelle2 = true,
        ))
        val gs = org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel()
        gs.beginnen()
        gs.festlegen(Huellenoeffner.konto, ByteArray(16))
        assertTrue(gs.uebernehmen())
        val k = klammer()
        k.beginnen(Modus.NUR_AUFZEICHNEN)
        for (p in Stroeme.erzeuge(Stroeme.DIENST_12H).take(40)) k.positionsfund(p)
        uhrzeit = Instant.parse("2026-07-16T17:00:00Z")
        k.beenden()
        return gs
    }

    private fun sender2(gs: org.genem.nadoku.handy.tresor.Geraeteschluessel) = Sender(
        puffer = puffer, netzweg = HttpNetzweg(), tresor = tresor, basis = server.basis,
        jetzt = { uhrzeit }, geraeteschluessel = gs,
    )

    /**
     * Der Sender verschließt über den Absender aus der Schlüsselablage: Die
     * Hülle trägt Fassung 2 und die Kennung des gültigen Schlüssels und geht
     * mit dem Kontoschlüssel auf.
     */
    @Test fun fassung2UeberDenGeraeteschluessel() {
        antworteVollstaendig()
        val gs = fassung2Vorbereiten()
        val bericht = sender2(gs).sendeAlles()
        assertTrue(bericht.sauber)
        assertEquals(1, bericht.fertigePakete)

        val o = server.letzterKoerper()
        val t = o.getJSONObject("track")
        val h = java.util.Base64.getDecoder().decode(t.getString("huelle"))
        assertEquals(2, h[0].toInt())
        assertEquals(gs.spk()!!.let { Geraetehuelle.sid(it) }.toList(), h.copyOfRange(1, 9).toList())
        val innen = Huellenoeffner.oeffnen2(
            h, gs.spk()!!, "ruhe", o.getString("client_ref"), "${t.getLong("seq_from")}:${t.getInt("punkte")}",
        )
        assertEquals(Geraetehuelle.SPUR_KOPF2 + 14 * t.getInt("punkte"), innen.size)
    }

    /**
     * Verweigert die Schlüsselablage die Vereinbarung, geht NICHTS hinaus —
     * keine Anfrage, keine Hülle der Fassung 1 als Ausweg —, das Paket bleibt
     * unberührt liegen, und der Lauf endet als „später erneut", statt den
     * Sendefaden mit einer Ausnahme zu beenden. Der nächste Lauf sendet.
     */
    @Test fun schluesselausfallSendetNichts() {
        antworteVollstaendig()
        val gs = fassung2Vorbereiten()
        val echt = gs.liefern()!!
        var faelltAus = true
        val wackelig = object : org.genem.nadoku.handy.tresor.Geraeteschluessel by gs {
            override fun liefern() = object : org.genem.nadoku.handy.tresor.Absender {
                override val spk get() = echt.spk
                override fun vereinbaren(konto: java.security.interfaces.ECPublicKey): ByteArray =
                    if (faelltAus) throw org.genem.nadoku.handy.tresor.Schluesselausfall(IllegalStateException("Keystore"))
                    else echt.vereinbaren(konto)
            }
        }
        val wartend = puffer.warteschlange().size
        val s = sender2(wackelig)

        val bericht = s.sendeAlles()
        assertTrue(bericht.spaeterErneut)
        assertFalse("das Paar ist da — vorübergehend, kein „neu koppeln“", bericht.herkunftFehlt)
        assertEquals(0, bericht.anfragen)
        assertEquals(0, bericht.fehlerhaft)
        assertTrue("nichts ist hinausgegangen", server.anfragen.isEmpty())
        assertEquals("das Paket liegt unverändert", wartend, puffer.warteschlange().size)
        assertEquals(0, puffer.abgewiesen())

        faelltAus = false
        val danach = s.sendeAlles()
        assertTrue(danach.sauber)
        assertEquals(1, danach.fertigePakete)
        assertEquals(2, java.util.Base64.getDecoder().decode(
            server.letzterKoerper().getJSONObject("track").getString("huelle"))[0].toInt())
    }

    // ---- Herkunft fehlt: `huelle: 2` ohne Gerätepaar (seit KA-15, E-KA-80) ----

    /** Die Fassung der zuletzt gesendeten Spurhülle. */
    private fun letzteFassung(): Int = java.util.Base64.getDecoder().decode(
        server.letzterKoerper().getJSONObject("track").getString("huelle"))[0].toInt()

    /** Was im Puffer liegt, als Text — Paket für Paket mit Bestätigungsmarke, Zustand und Punktzahl. */
    private fun pufferbild(): String = puffer.warteschlange().joinToString("|") {
        "${it.id}:${it.clientRef}:${it.bestaetigtSeq}:${it.final}:${puffer.punktzahl(it.id)}"
    } + " r=${puffer.rueckstand()} a=${puffer.abgewiesen()}"

    /** Ein Lauf, der wegen fehlender Herkunft nichts getan hat. */
    private fun haeltAn(bericht: Sendebericht, vorher: String) {
        assertTrue("der Zustand ist benannt", bericht.herkunftFehlt)
        assertTrue("der Nachsende-Job fragt wieder", bericht.spaeterErneut)
        assertFalse("kein 401: die Kopplung selbst gilt", bericht.pausiert)
        assertFalse(bericht.ohneSchluessel)
        assertFalse(bericht.sauber)
        assertEquals(0, bericht.anfragen)
        assertEquals(0, bericht.gesendetePunkte)
        assertEquals(0, bericht.fehlerhaft)
        assertTrue("nichts ist hinausgegangen — auch keine Fassung 1", server.anfragen.isEmpty())
        assertEquals("der Puffer liegt unverändert", vorher, pufferbild())
    }

    /**
     * Der Server hat `huelle: 2` gesagt, das Paar ist fort (nach dem Update
     * von 0.24.0): KEINE Anfrage, keine Hülle der Fassung 1 als Ausweg, der
     * Puffer unberührt, und der Bericht sagt „Herkunft fehlt". Nach dem
     * Neukoppeln — ein neues gültiges Paar — sendet derselbe Sender in
     * Fassung 2 unter der Kennung des neuen Schlüssels.
     */
    @Test fun huelle2OhnePaarSendetNichtsBisNeuGekoppelt() {
        antworteVollstaendig()
        val gs = fassung2Vorbereiten()
        gs.vergessen()                          // die Ablage ist leer, der Merker im Tresor steht
        assertNull(gs.liefern())
        assertTrue(tresor.lesen()!!.huelle2)
        val vorher = pufferbild()
        assertTrue("es liegt etwas zum Senden", puffer.rueckstand() > 0)
        val s = sender2(gs)

        haeltAn(s.sendeAlles(), vorher)
        haeltAn(s.sendeAlles(), vorher)         // und bleibt dabei, Lauf für Lauf
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.FEHLT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), gs),
        )

        // Neu gekoppelt: ein neues Paar wird gültig.
        gs.beginnen()
        gs.festlegen(Huellenoeffner.konto, ByteArray(16))
        assertTrue(gs.uebernehmen())
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.GESICHERT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), gs),
        )
        val danach = s.sendeAlles()
        assertFalse(danach.herkunftFehlt)
        assertTrue(danach.sauber)
        assertEquals(1, danach.fertigePakete)
        assertEquals(0, puffer.rueckstand())
        assertEquals(2, letzteFassung())
        val h = java.util.Base64.getDecoder().decode(
            server.letzterKoerper().getJSONObject("track").getString("huelle"))
        assertEquals(Geraetehuelle.sid(gs.spk()!!).toList(), h.copyOfRange(1, 9).toList())
        assertTrue("jede Anfrage in Fassung 2", server.anfragen.all {
            java.util.Base64.getDecoder().decode(
                org.json.JSONObject(it.koerper).getJSONObject("track").getString("huelle"))[0].toInt() == 2
        })
    }

    /** Ein Sender ganz ohne Ablage für das Paar (`null`) hält bei `huelle: 2` genauso an. */
    @Test fun huelle2OhneAblageSendetNichts() {
        antworteVollstaendig()
        fassung2Vorbereiten()
        val vorher = pufferbild()
        val s = Sender(puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit })
        haeltAn(s.sendeAlles(), vorher)
    }

    /**
     * Die Verwaltung nennt ein Paar, aber die Schlüsselablage gibt den
     * Eintrag nicht her — so sieht ein Keystore aus, der „nicht vorhanden"
     * meldet, ob der Eintrag nun fehlt oder der Dienst nur nicht antwortet.
     * Auch dann geht nichts hinaus. Und weil die Lage nirgends abgelegt ist:
     * Gibt die Ablage den Eintrag wieder her, sendet der nächste Lauf in
     * Fassung 2, ohne neue Kopplung.
     */
    @Test fun keystoreOhneEintragHaeltAnUndHeiltVonSelbst() {
        antworteVollstaendig()
        val gs = fassung2Vorbereiten()
        val name = gs.ablage.namen().single()
        val eintrag = gs.ablage.absender(name) as org.genem.nadoku.handy.tresor.SoftwareAbsender
        gs.ablage.loeschen(name)
        assertNotNull("die Verwaltung nennt das Paar noch", gs.inhalt)
        assertNull(gs.liefern())
        val vorher = pufferbild()
        val s = sender2(gs)

        haeltAn(s.sendeAlles(), vorher)

        gs.ablage.einlegen(name, eintrag)
        val danach = s.sendeAlles()
        assertFalse(danach.herkunftFehlt)
        assertTrue(danach.sauber)
        assertEquals(2, letzteFassung())
    }

    /**
     * Ohne `huelle: 2` geht seit Android 0.27.0 nichts hinaus (KA-21,
     * E-KA-60), ob ein Paar da ist oder nicht: Der Server nimmt Fassung 1
     * nicht mehr an, und eine Kopplung, der er nie `huelle: 2` gesagt hat,
     * kann keine Fassung 2 senden. Ersetzt `ohneHuelle2BleibtFassung1` —
     * die Lage, die er prüfte (Fassung 1 ohne `huelle: 2`), gibt es nicht
     * mehr.
     */
    @Test fun gekoppeltOhneHuelle2SendetNichts() {
        antworteVollstaendig()
        val gs = fassung2Vorbereiten()
        tresor.huelleSetzen(false)
        assertNotNull("das Paar ist da", gs.liefern())
        val vorher = pufferbild()
        val s = sender2(gs)

        haeltAn(s.sendeAlles(), vorher)
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.FEHLT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), gs),
        )

        gs.vergessen()
        haeltAn(s.sendeAlles(), vorher)
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.FEHLT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), gs),
        )
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.FEHLT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), null),
        )
    }

    /** Nicht gekoppelt ist die einzige Lage, die [org.genem.nadoku.handy.tresor.Herkunftsstand.OHNE] heißt. */
    @Test fun nurOhneKopplungIstDieHerkunftOhne() {
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.OHNE,
            org.genem.nadoku.handy.tresor.Herkunft.stand(null, gs),
        )
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.OHNE,
            org.genem.nadoku.handy.tresor.Herkunft.stand(null, null),
        )
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.FEHLT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(Zugangsdaten("dev-pruef", "geheim-pruef", KONTOSCHLUESSEL), gs),
        )
        assertEquals(
            org.genem.nadoku.handy.tresor.Herkunftsstand.GESICHERT,
            org.genem.nadoku.handy.tresor.Herkunft.stand(tresor.lesen(), gs),
        )
    }

    /**
     * Der Merker kommt erst mit dem Nachholen des Kontoschlüssels (`status`
     * trägt beides): Ohne Paar hält der Lauf, der ihn holt, an. Bis 0.26.0
     * hieß das „er sendet nicht noch einmal in Fassung 1"; seit 0.27.0 hielte
     * er ohnehin an, und der zweite Teil belegt deshalb die andere Richtung
     * (unten).
     */
    @Test fun huelle2AusDemNachholenGiltImSelbenLauf() {
        antworteVollstaendig()
        einSegmentMitPunkten(10)
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef"))
        val vorher = pufferbild()
        val s = Sender(
            puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = {
                tresor.huelleSetzen(true)
                tresor.kontoschluesselSetzen(KONTOSCHLUESSEL)
                KONTOSCHLUESSEL
            },
            geraeteschluessel = org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel(),
        )
        haeltAn(s.sendeAlles(), vorher)
    }

    /**
     * Mit Paar sendet der Lauf, der `huelle: 2` erst holt, im SELBEN Lauf in
     * Fassung 2 — er liest den Tresor nach dem Nachholen neu. Mit dem Stand
     * von davor (ohne `huelle: 2`) hielte er seit 0.27.0 an und wartete auf
     * den nächsten Lauf.
     */
    @Test fun nachgeholtesHuelle2SendetImSelbenLauf() {
        antworteVollstaendig()
        einSegmentMitPunkten(10)
        tresor.speichern(Zugangsdaten("dev-pruef", "geheim-pruef"))
        val s = Sender(
            puffer, HttpNetzweg(), tresor, server.basis, jetzt = { uhrzeit },
            schluesselHolen = {
                tresor.huelleSetzen(true)
                tresor.kontoschluesselSetzen(KONTOSCHLUESSEL)
                KONTOSCHLUESSEL
            },
            geraeteschluessel = gs,
        )
        val bericht = s.sendeAlles()
        assertFalse(bericht.herkunftFehlt)
        assertTrue(bericht.anfragen > 0)
        assertEquals(2, letzteFassung())
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

        /** Das Konto der Referenzvektoren, Base64 (`tools/proben/huelle/vektoren-2.json`; bis 0.27.0 aus `vektoren.json`, derselbe Schlüssel). */
        val KONTOSCHLUESSEL: String by lazy {
            java.util.Base64.getEncoder().encodeToString(Huellenoeffner.konto)
        }
    }
}
