package org.genem.nadoku.handy.kopplung

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
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

/**
 * Kopplung und Trennen gegen einen echten HTTP-Server (Vertrag 1a und 1b).
 *
 * Der Prüfstand spricht echtes HTTP über die Rückschleife, damit
 * [HttpNetzweg] mitgeprüft wird — Kopfzeilen, Fehlerstrom bei 4xx, Zeitlimit.
 * Eine Attrappe des [Netzweg] prüfte nur die Auswertung.
 */
@RunWith(RobolectricTestRunner::class)
class KopplungTest {

    private lateinit var server: PruefServer
    private lateinit var tresor: Schluesseltresor
    private lateinit var datei: File
    private var rueckstand = 0

    private val geraet = Geraeteangabe(
        art = Geraeteangabe.ART_HANDY, teil = null,
        hersteller = "samsung", modell = "SM-S921B",
        br = 1080, ho = 2340, touch = true,
        fw = "16", sdk = 36, app = "0.2.0",
    )

    private fun dienst() = Kopplungsdienst(HttpNetzweg(), tresor) { rueckstand }

    @Before fun aufbauen() {
        server = PruefServer()
        val kontext = ApplicationProvider.getApplicationContext<Context>()
        datei = File(kontext.filesDir, "kopplungspruefung.bin")
        datei.delete()
        tresor = Schluesseltresor(datei, PruefTresorschluessel())
        rueckstand = 0
    }

    @After fun abbauen() {
        server.close()
        datei.delete()
    }

    // ---- Koppeln ----------------------------------------------------------

    @Test fun kopplungErfolg() {
        server.status = 200
        server.antwortkoerper =
            """{"device_id":"dev-2f8a1c4e9b0d7a63f15e8c2a4b96d038","api_key":"9c1f7ab35de0"}"""

        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet)

        assertEquals(Kopplungsergebnis.Gekoppelt, e)
        assertEquals("dev-2f8a1c4e9b0d7a63f15e8c2a4b96d038", tresor.lesen()?.geraeteKennung)
        assertEquals("9c1f7ab35de0", tresor.lesen()?.schluessel)

        // Der Weg selbst: POST an pair.php, JSON, ohne Auth-Kopfzeilen.
        val a = server.anfragen.single()
        assertTrue(a.zeile.startsWith("POST /pair.php "))
        assertTrue(a.kopfzeilen["Content-Type"]!!.startsWith("application/json"))
        assertNull(a.kopfzeilen["X-Device-Id"])
        assertNull(a.kopfzeilen["X-Api-Key"])
    }

    @Test fun kopplungSendetDenGeraeteBlockInDerFeldformAusESVier28() {
        server.antwortkoerper = """{"device_id":"dev-1","api_key":"k1"}"""
        dienst().koppeln(server.basis, "AB3K7Q", geraet)

        val k = server.letzterKoerper()
        assertEquals("AB3K7Q", k.getString("code"))
        val g = k.getJSONObject("geraet")
        assertEquals("handy", g.getString("art"))
        assertTrue("teil muss null sein — ein Handy hat keine", g.isNull("teil"))
        assertEquals("samsung", g.getString("hersteller"))
        assertEquals("SM-S921B", g.getString("modell"))
        assertEquals(1080, g.getInt("br"))
        assertEquals(2340, g.getInt("ho"))
        assertTrue(g.getBoolean("touch"))
        assertEquals("16", g.getString("fw"))
        assertEquals(36, g.getInt("sdk"))
        assertEquals("0.2.0", g.getString("app"))
        assertFalse("ciq gehoert nicht in den Handy-Block", g.has("ciq"))
    }

    @Test fun kopplungCodeWirdGrossgeschriebenUndVonTrennzeichenBefreit() {
        server.antwortkoerper = """{"device_id":"dev-1","api_key":"k1"}"""
        dienst().koppeln(server.basis, " ab3k-7q ", geraet)
        assertEquals("AB3K7Q", server.letzterKoerper().getString("code"))
    }

    @Test fun kopplung400() {
        server.status = 400
        server.antwortkoerper = """{"error":"code"}"""
        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet)
        assertEquals(Kopplungsergebnis.Abgewiesen(Abweisung.CODE_UNBRAUCHBAR), e)
        assertNull(tresor.lesen())
    }

    @Test fun kopplung404UngueltigerCode() {
        server.status = 404
        server.antwortkoerper = """{"error":"invalid"}"""
        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet)
        assertEquals(Kopplungsergebnis.Abgewiesen(Abweisung.CODE_UNGUELTIG), e)
    }

    @Test fun kopplung409GeraeteGrenze() {
        server.status = 409
        server.antwortkoerper =
            """{"error":"device_limit","meldung":"Es sind bereits 5 Geraete mit diesem Konto verbunden."}"""

        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet) as Kopplungsergebnis.Abgewiesen

        assertEquals(Abweisung.ZU_VIELE_GERAETE, e.art)
        // Der Fehlerstrom wird gelesen — sonst ginge genau die Meldung
        // verloren, die sagt, was zu tun ist.
        assertNotNull(e.meldung)
        assertTrue(e.meldung!!.contains("5 Geraete"))
    }

    @Test fun kopplung429Ratenschutz() {
        server.status = 429
        server.antwortkoerper = """{"error":"zu_viele_versuche","meldung":"Zu viele Kopplungsversuche."}"""
        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.ZU_VIELE_VERSUCHE, e.art)
    }

    @Test fun kopplung500() {
        server.status = 500
        server.antwortkoerper = """{"error":"server"}"""
        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.SERVERFEHLER, e.art)
    }

    @Test fun kopplungOhneVerbindung() {
        val adresse = server.basis
        server.close()                       // die Gegenstelle ist fort
        val e = dienst().koppeln(adresse, "AB3K7Q", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.KEINE_VERBINDUNG, e.art)
        assertNull(tresor.lesen())
    }

    /** 200 ohne Zugangsdaten ist kein Erfolg — und darf keiner werden. */
    @Test fun kopplung200OhneZugangsdatenGiltNichtAlsGekoppelt() {
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""
        val e = dienst().koppeln(server.basis, "AB3K7Q", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.UNBEKANNT, e.art)
        assertNull(tresor.lesen())
    }

    /** Ein Vertipper darf den Ratenschutz von pair.php nicht belasten. */
    @Test fun unbrauchbarerCodeWirdGarNichtGesendet() {
        val e = dienst().koppeln(server.basis, "AB3K7", geraet)
        assertEquals(Kopplungsergebnis.Abgewiesen(Abweisung.CODE_UNBRAUCHBAR), e)
        assertEquals("Es darf keine Anfrage hinausgegangen sein", 0, server.anfragen.size)
    }

    @Test fun codeMitAusgeschlossenenZeichenWirdAbgewiesen() {
        // 0, O, 1 und I fehlen im Alphabet von pair.php (PAIR_CHARS).
        for (code in listOf("AB3K70", "ABIK7Q", "AB3K7O", "AB1K7Q")) {
            val e = dienst().koppeln(server.basis, code, geraet)
            assertEquals(code, Kopplungsergebnis.Abgewiesen(Abweisung.CODE_UNBRAUCHBAR), e)
        }
        assertEquals(0, server.anfragen.size)
    }

    // ---- Trennen ----------------------------------------------------------

    private fun gekoppeltSein() {
        server.antwortkoerper = """{"device_id":"dev-7","api_key":"geheim-7"}"""
        server.status = 200
        dienst().koppeln(server.basis, "AB3K7Q", geraet)
        server.anfragen.clear()
    }

    @Test fun trennenErfolg() {
        gekoppeltSein()
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""

        val e = dienst().trennen(server.basis)

        assertEquals(Trennergebnis.Getrennt, e)
        assertNull("Lokal muss die Kopplung fort sein", tresor.lesen())

        val a = server.anfragen.single()
        assertTrue(a.zeile.startsWith("POST /pair.php "))
        assertEquals("dev-7", a.kopfzeilen["X-Device-Id"])
        assertEquals("geheim-7", a.kopfzeilen["X-Api-Key"])
        assertEquals("trennen", org.json.JSONObject(a.koerper).getString("aktion"))
    }

    @Test fun trennen401TrenntTrotzdemLokal() {
        gekoppeltSein()
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""

        val e = dienst().trennen(server.basis)

        assertEquals(Trennergebnis.NurLokal(Abweisung.CODE_UNGUELTIG), e)
        assertNull(tresor.lesen())
    }

    /**
     * Ohne Antwort wird lokal getrennt. Sonst bliebe ein Handy ohne Netz
     * dauerhaft an ein Konto gebunden, das es nicht mehr benutzen soll.
     */
    @Test fun trennenOhneAntwortTrenntLokal() {
        gekoppeltSein()
        val adresse = server.basis
        server.close()

        val e = dienst().trennen(adresse)

        assertEquals(Trennergebnis.NurLokal(Abweisung.KEINE_VERBINDUNG), e)
        assertNull(tresor.lesen())
    }

    /**
     * Rückstand sperrt das Trennen — und zwar VOLLSTÄNDIG: keine Anfrage,
     * keine lokale Löschung. Die Pakete gehören dem bisherigen Konto.
     */
    @Test fun rueckstandSperrtDasTrennen() {
        gekoppeltSein()
        rueckstand = 2

        val e = dienst().trennen(server.basis)

        assertEquals(Trennergebnis.Rueckstand(2), e)
        assertNotNull("Die Kopplung muss stehenbleiben", tresor.lesen())
        assertEquals("Es darf keine Anfrage hinausgegangen sein", 0, server.anfragen.size)
    }

    @Test fun trennenOhneKopplungTutNichts() {
        val e = dienst().trennen(server.basis)
        assertEquals(Trennergebnis.NichtGekoppelt, e)
        assertEquals(0, server.anfragen.size)
    }
}
