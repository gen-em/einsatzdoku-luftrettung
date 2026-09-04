package org.genem.nadoku.handy.kopplung

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
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
import org.json.JSONObject
import java.io.File

/**
 * Kopplung und Trennen gegen einen echten HTTP-Server (Vertrag 1a und 1b).
 *
 * Der Prüfstand spricht echtes HTTP über die Rückschleife, damit
 * [HttpNetzweg] mitgeprüft wird — Kopfzeilen, Fehlerstrom bei 4xx, Zeitlimit.
 * Eine Attrappe des [Netzweg] prüfte nur die Auswertung.
 *
 * DREI ANLIEGEN STATT EINEM (R49, S5). Die Fälle folgen dem Ablauf von 1a:
 * `start` legt die Sitzung an, `status` fragt nach, `bestaetigen` entscheidet.
 * Was früher ein Fall war („Code hin, Zugangsdaten zurück"), ist jetzt eine
 * Kette — und die interessanten Fälle liegen zwischen ihren Gliedern:
 * schwebende Zugangsdaten, eine verlorene Antwort, ein Nein.
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

    private fun dienst() = Kopplungsdienst(HttpNetzweg(), tresor, server.basis) { rueckstand }

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

    /** Die Antwort auf `start`, wie `pair.php` sie schickt (1a.1). */
    private fun sitzungsantwort(code: String = "AB3K7Q", frist: Int = 600) {
        server.status = 200
        server.antwortkoerper = """
            {"code":"$code","device_id":"dev-3f9a","api_key":"8c1e","frist_s":$frist}
        """.trimIndent()
    }

    /** Eine schwebende Sitzung herstellen, ohne sie zu bestätigen. */
    private fun sitzungAnlegen(code: String = "AB3K7Q"): Sitzungsergebnis {
        sitzungsantwort(code)
        return dienst().starten(geraet)
    }

    // ---- start (1a.1) -----------------------------------------------------

    @Test fun startLiefertCodeUndLegtSchwebendeZugangsdatenAb() {
        val e = sitzungAnlegen()

        assertTrue("start ergab $e", e is Sitzungsergebnis.Offen)
        assertEquals("AB3K7Q", (e as Sitzungsergebnis.Offen).code)
        assertEquals(600, e.fristSekunden)

        // Die Daten liegen — aber das Gerät gilt NICHT als gekoppelt.
        assertNotNull("Zugangsdaten fehlen", tresor.lesen())
        assertTrue("Sie müssten schweben", tresor.schwebend())
        assertFalse("gekoppelt() darf hier nicht wahr sein", tresor.gekoppelt())
    }

    @Test fun startSendetDasPflichtfeldAktionUndDenGeraeteBlock() {
        sitzungAnlegen()

        val koerper = JSONObject(server.anfragen.single().koerper)
        assertEquals("start", koerper.getString("aktion"))

        // Der Block geht an `start`, nicht an `bestaetigen` (Vertrag 1a.1) —
        // die Bestätigungsseite im Browser zeigt ihn.
        val g = koerper.getJSONObject("geraet")
        assertEquals("handy", g.getString("art"))
        assertEquals("SM-S921B", g.getString("modell"))
        assertTrue("teil muss null sein", g.isNull("teil"))
        assertFalse("ciq gehört nicht in die Handy-Form", g.has("ciq"))
    }

    @Test fun startOhneKopfzeilen() {
        sitzungAnlegen()
        val kopf = server.anfragen.single().kopfzeilen
        assertNull("X-Device-Id gehört nicht an start", kopf["X-Device-Id"])
        assertNull("X-Api-Key gehört nicht an start", kopf["X-Api-Key"])
    }

    @Test fun startMitUnbrauchbaremCodeGiltNichtAlsErfolg() {
        // 200, aber der Code passt nicht zum Alphabet (0 und O gibt es nicht).
        server.status = 200
        server.antwortkoerper = """{"code":"AB0O7Q","device_id":"d","api_key":"k"}"""

        val e = dienst().starten(geraet)
        assertEquals(
            Abweisung.UNBEKANNT,
            (e as Sitzungsergebnis.Abgewiesen).art,
        )
        assertNull("Nichts darf abgelegt worden sein", tresor.lesen())
    }

    @Test fun startOhneFristNimmtDieVertragsvorgabe() {
        server.status = 200
        server.antwortkoerper = """{"code":"AB3K7Q","device_id":"d","api_key":"k"}"""

        val e = dienst().starten(geraet) as Sitzungsergebnis.Offen
        assertEquals(600, e.fristSekunden)
    }

    @Test fun startZuVieleSitzungen() {
        server.status = 429
        server.antwortkoerper = """{"error":"zu_viele_sitzungen","meldung":"Spaeter erneut"}"""

        val e = dienst().starten(geraet) as Sitzungsergebnis.Abgewiesen
        assertEquals(Abweisung.ZU_VIELE_VERSUCHE, e.art)
        assertEquals("Spaeter erneut", e.meldung)
    }

    // ---- status (1a.2) ----------------------------------------------------

    @Test fun statusOffen() {
        sitzungAnlegen()
        server.status = 200
        server.antwortkoerper = """{"zustand":"offen","rest_s":540}"""

        val s = dienst().nachfragen()
        assertEquals(540, (s as Sitzungsstand.Offen).restSekunden)
    }

    @Test fun statusBeansprucht() {
        sitzungAnlegen()
        server.status = 200
        server.antwortkoerper = """{"zustand":"beansprucht","konto":"ph***@gen-em.org","rest_s":300}"""

        val s = dienst().nachfragen() as Sitzungsstand.Beansprucht
        assertEquals("ph***@gen-em.org", s.konto)
        assertEquals(300, s.restSekunden)
    }

    /**
     * DER SPARSAME FALL: `beansprucht` ohne `konto`. Ohne Adresse wäre die
     * Frage am Gerät ein Ja ins Blaue — das zweite Tor fiele weg. Dann lieber
     * weiterfragen als eine leere Zeile anzeigen.
     */
    @Test fun statusBeansprochtOhneKontoBleibtOffen() {
        sitzungAnlegen()
        server.status = 200
        server.antwortkoerper = """{"zustand":"beansprucht","rest_s":300}"""

        assertTrue(dienst().nachfragen() is Sitzungsstand.Offen)
    }

    @Test fun statusSendetDieKopfzeilen() {
        sitzungAnlegen()
        server.anfragen.clear()
        server.antwortkoerper = """{"zustand":"offen","rest_s":540}"""
        dienst().nachfragen()

        val kopf = server.anfragen.single().kopfzeilen
        assertEquals("dev-3f9a", kopf["X-Device-Id"])
        assertEquals("8c1e", kopf["X-Api-Key"])
    }

    /**
     * Der Fall, in dem die Antwort auf `bestaetigen ja` verlorenging: Der
     * Server kennt das Gerät bereits. Die Zugangsdaten sind gültig — die App
     * darf sie als solche übernehmen (Vertrag 1a.2).
     */
    @Test fun statusGekoppelt() {
        sitzungAnlegen()
        server.status = 200
        server.antwortkoerper = """{"zustand":"gekoppelt"}"""

        assertTrue(dienst().nachfragen() is Sitzungsstand.Gekoppelt)
    }

    @Test fun statusAbgelaufen() {
        sitzungAnlegen()
        server.status = 410
        server.antwortkoerper = """{"error":"abgelaufen"}"""

        val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
        assertEquals(Abweisung.SITZUNG_ABGELAUFEN, s.art)
    }

    @Test fun statusAuthIstSitzungUngueltig() {
        sitzungAnlegen()
        server.status = 401
        server.antwortkoerper = """{"error":"auth"}"""

        val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
        assertEquals(Abweisung.SITZUNG_UNGUELTIG, s.art)
    }

    @Test fun statusOhneSitzungFragtGarNicht() {
        val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
        assertEquals(Abweisung.SITZUNG_UNGUELTIG, s.art)
        assertTrue("Ohne Zugangsdaten darf nichts hinausgehen", server.anfragen.isEmpty())
    }

    // ---- bestaetigen (1a.3) ----------------------------------------------

    @Test fun jaMachtDieZugangsdatenGueltig() {
        sitzungAnlegen()
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""

        assertTrue(dienst().bestaetigen(ja = true) is Bestaetigungsergebnis.Gekoppelt)
        assertFalse("Sie dürfen nicht mehr schweben", tresor.schwebend())
        assertTrue("Jetzt gilt die Kopplung", tresor.gekoppelt())
        assertEquals("dev-3f9a", tresor.lesen()?.geraeteKennung)
    }

    @Test fun jaSendetDieAntwortImRumpf() {
        sitzungAnlegen()
        server.anfragen.clear()
        server.antwortkoerper = """{"ok":true}"""
        dienst().bestaetigen(ja = true)

        val koerper = JSONObject(server.anfragen.single().koerper)
        assertEquals("bestaetigen", koerper.getString("aktion"))
        assertEquals("ja", koerper.getString("antwort"))
    }

    @Test fun neinRaeumtLokalAufUndSendetNein() {
        sitzungAnlegen()
        server.anfragen.clear()
        server.antwortkoerper = """{"ok":true}"""

        assertTrue(dienst().bestaetigen(ja = false) is Bestaetigungsergebnis.Abgebrochen)
        assertNull("Die Ablage muss leer sein", tresor.lesen())
        assertEquals("nein", JSONObject(server.anfragen.single().koerper).getString("antwort"))
    }

    /**
     * EIN NEIN RÄUMT AUCH OHNE ANTWORT AUF. Sonst bliebe ein Schlüssel liegen,
     * zu dem es nie ein Gerät gab — und die App zeigte beim nächsten Start
     * eine Sitzung an, die es nicht mehr gibt.
     */
    @Test fun neinRaeumtAuchOhneServerAuf() {
        sitzungAnlegen()
        server.close()

        assertTrue(dienst().bestaetigen(ja = false) is Bestaetigungsergebnis.Abgebrochen)
        assertNull(tresor.lesen())
    }

    @Test fun jaBeiZuVielenGeraetenLoeschtDieSchwebendeSitzung() {
        sitzungAnlegen()
        server.status = 409
        server.antwortkoerper = """{"error":"device_limit","meldung":"Es sind bereits 5 Geraete"}"""

        val e = dienst().bestaetigen(ja = true) as Bestaetigungsergebnis.Abgewiesen
        assertEquals(Abweisung.ZU_VIELE_GERAETE, e.art)
        assertEquals("Es sind bereits 5 Geraete", e.meldung)
        // Der Vertrag sagt: Die Sitzung ist serverseitig gelöscht (1a.3).
        assertNull("Der schwebende Schlüssel gehört zu nichts mehr", tresor.lesen())
    }

    /**
     * `409 nicht_beansprucht` LÄSST DIE SITZUNG STEHEN (Vertrag 1a.3) — sie
     * ist der einzige Fehlerfall des Ja, nach dem weiterprobiert werden darf.
     */
    @Test fun jaVorDerBeanspruchungLaesstDieSitzungStehen() {
        sitzungAnlegen()
        server.status = 409
        server.antwortkoerper = """{"error":"nicht_beansprucht"}"""

        dienst().bestaetigen(ja = true)
        assertNotNull("Die Sitzung muss bestehen bleiben", tresor.lesen())
        assertTrue(tresor.schwebend())
    }

    @Test fun jaOhneNetzLaesstDieSitzungStehen() {
        sitzungAnlegen()
        server.close()

        val e = dienst().bestaetigen(ja = true) as Bestaetigungsergebnis.Abgewiesen
        assertEquals(Abweisung.KEINE_VERBINDUNG, e.art)
        assertNotNull("Ohne Netz ist nichts entschieden", tresor.lesen())
    }

    // ---- Trennen (1b) ----------------------------------------------------

    @Test fun trennenOhneKopplung() {
        assertTrue(dienst().trennen() is Trennergebnis.NichtGekoppelt)
    }

    @Test fun trennenErfolg() {
        tresor.speichern(Zugangsdaten("dev-1", "k1"))
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""

        assertTrue(dienst().trennen() is Trennergebnis.Getrennt)
        assertNull(tresor.lesen())

        val a = server.anfragen.single()
        assertEquals("trennen", JSONObject(a.koerper).getString("aktion"))
        assertEquals("dev-1", a.kopfzeilen["X-Device-Id"])
    }

    /** LOKAL WIRD IMMER GETRENNT (E-S4-12) — auch ohne Antwort des Servers. */
    @Test fun trennenOhneServerTrenntTrotzdemLokal() {
        tresor.speichern(Zugangsdaten("dev-1", "k1"))
        server.close()

        val e = dienst().trennen() as Trennergebnis.NurLokal
        assertEquals(Abweisung.KEINE_VERBINDUNG, e.grund)
        assertNull(tresor.lesen())
    }

    /** Die Rückstandssperre (Backlog Nr. 14): NICHTS geschieht. */
    @Test fun trennenMitRueckstandTutNichts() {
        tresor.speichern(Zugangsdaten("dev-1", "k1"))
        rueckstand = 3

        val e = dienst().trennen() as Trennergebnis.Rueckstand
        assertEquals(3, e.pakete)
        assertNotNull("Die Kopplung muss stehen bleiben", tresor.lesen())
        assertTrue("Es darf nichts hinausgegangen sein", server.anfragen.isEmpty())
    }
}
