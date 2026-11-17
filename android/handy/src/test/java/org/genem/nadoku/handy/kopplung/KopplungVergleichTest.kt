package org.genem.nadoku.handy.kopplung

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.PruefServer
import org.genem.nadoku.handy.senden.Kurvenhilfe
import org.genem.nadoku.handy.tresor.Herkunft
import org.genem.nadoku.handy.tresor.Herkunftsstand
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.genem.nadoku.handy.tresor.SpeicherGeraeteschluessel
import org.genem.nadoku.handy.tresor.Zugangsdaten
import org.json.JSONObject
import org.junit.After
import org.junit.Assert.assertArrayEquals
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
import java.math.BigInteger
import java.util.Base64

/**
 * Die Kopplung mit Vergleichswert am Handy (seit KA-06c, JSON-Vertrag 1a.7).
 *
 * Wie [KopplungTest] gegen echtes HTTP über die Rückschleife. Der Server
 * antwortet je Anliegen (`antwortAus`), weil eine Abfrage im Zustand
 * `beansprucht` zwei Anfragen auslöst: `status`, dann `offenlegen`.
 *
 * WAS HIER GEMESSEN WIRD — der Ablauf, nicht die Rechnung: dass der Schlüssel
 * mit dem Start geht, dass die App erst nach dem Beanspruchen offenlegt, dass
 * der gezeigte Wert der ist, den Zusage, Kontoschlüssel und beide
 * Zufallszahlen ergeben, dass das erste Paar gilt, und was bei Ablehnung,
 * Warten und Abschluss im Tresor bleibt. Die Rechnung selbst halten
 * [KopplungswertTest] und die Vektoren der Referenz.
 */
@RunWith(RobolectricTestRunner::class)
class KopplungVergleichTest {

    private lateinit var server: PruefServer
    private lateinit var tresor: Schluesseltresor
    private lateinit var datei: File
    private lateinit var gs: SpeicherGeraeteschluessel

    private val geraet = Geraeteangabe(
        art = Geraeteangabe.ART_HANDY, teil = null,
        hersteller = "samsung", modell = "SM-S921B",
        br = 1080, ho = 2340, touch = true,
        fw = "16", sdk = 36, app = "0.24.0",
    )

    private val konto = Kurvenhilfe.punkt(BigInteger.valueOf(424242))
    private val kontoB64 = Base64.getEncoder().encodeToString(konto)
    private val nB = ByteArray(16) { (it * 7 + 3).toByte() }
    private val nBB64 = Base64.getEncoder().encodeToString(nB)

    private fun dienst() = Kopplungsdienst(HttpNetzweg(), tresor, server.basis, geraeteschluessel = gs)

    @Before fun aufbauen() {
        server = PruefServer()
        val kontext = ApplicationProvider.getApplicationContext<Context>()
        datei = File(kontext.filesDir, "kopplungvergleich.bin")
        datei.delete()
        tresor = Schluesseltresor(datei, PruefTresorschluessel())
        gs = SpeicherGeraeteschluessel()
    }

    @After fun abbauen() {
        server.close()
        datei.delete()
    }

    private fun starten(): JSONObject {
        server.status = 200
        server.antwortAus = null
        server.antwortkoerper = """{"code":"AB3K7Q","device_id":"dev-3f9a","api_key":"8c1e","frist_s":600}"""
        assertTrue(dienst().starten(geraet) is Sitzungsergebnis.Offen)
        return server.letzterKoerper()
    }

    /** `beansprucht` mit `n_b` und Kontoschlüssel; `offenlegen` mit `ok`. */
    private fun beansprucht(zusatz: String = "", ks: String = "\"$kontoB64\"", nb: String = nBB64) {
        server.status = 200
        server.antwortAus = { k ->
            when (JSONObject(k).getString("aktion")) {
                "status" -> """{"zustand":"beansprucht","konto":"ph***@gen-em.org","rest_s":420,""" +
                    """"kontoschluessel":$ks,"n_b":"$nb"$zusatz}"""
                "offenlegen" -> """{"ok":true}"""
                else -> """{"error":"unerwartet"}"""
            }
        }
    }

    private fun anliegen(aktion: String) = server.anfragen.map { JSONObject(it.koerper) }
        .filter { it.getString("aktion") == aktion }

    @Test fun startSchicktGeraeteschluesselUndZusage() {
        val k = starten()
        val s = k.getJSONObject("schluessel")
        val spk = Base64.getDecoder().decode(s.getString("spk"))
        assertEquals(65, spk.size)
        assertEquals(0x04, spk[0].toInt())
        assertEquals(32, Base64.getDecoder().decode(s.getString("zusage")).size)
        assertNull("noch kein gültiges Paar", gs.liefern())
        assertNotNull("der Versuch liegt", gs.schwebend())
    }

    /**
     * Ohne Geräteschlüssel kein Start (seit Android 0.27.0, KA-21): Seit
     * Web 26.0.0 weist der Server `start` ohne Block `schluessel` ab, und
     * eine Kopplung ohne Paar könnte nichts senden. Es geht keine Anfrage
     * hinaus, und im Tresor schwebt nichts. Ersetzt `ohneGeraeteschluesselKeinBlock`
     * (bis 0.26.0: Start ohne Block).
     */
    @Test fun ohneGeraeteschluesselKeinStart() {
        server.antwortkoerper = """{"code":"AB3K7Q","device_id":"dev-3f9a","api_key":"8c1e","frist_s":600}"""
        val e = Kopplungsdienst(HttpNetzweg(), tresor, server.basis).starten(geraet)
        assertEquals(Sitzungsergebnis.Abgewiesen(Abweisung.UNBEKANNT), e)
        assertTrue("keine Anfrage", server.anfragen.isEmpty())
        assertNull("nichts schwebt", tresor.lesen())
    }

    /** Verweigert der Keystore das neue Paar, endet der Start ebenso — ohne Anfrage, ohne Absturz. */
    @Test fun keystoreVerweigertDasPaarKeinStart() {
        server.antwortkoerper = """{"code":"AB3K7Q","device_id":"dev-3f9a","api_key":"8c1e","frist_s":600}"""
        val verweigert = object : org.genem.nadoku.handy.tresor.Geraeteschluessel by gs {
            override fun beginnen(): org.genem.nadoku.handy.tresor.Kopplungsbeginn =
                throw IllegalStateException("Keystore")
        }
        val e = Kopplungsdienst(HttpNetzweg(), tresor, server.basis, geraeteschluessel = verweigert).starten(geraet)
        assertEquals(Sitzungsergebnis.Abgewiesen(Abweisung.UNBEKANNT), e)
        assertTrue("keine Anfrage", server.anfragen.isEmpty())
        assertNull(tresor.lesen())
    }

    /** `400 schluessel`, Grund `fehlt` (Web 26.0.0): ein Fehler, keine Sitzung. */
    @Test fun schluesselFehltIstEinFehler() {
        server.status = 400
        server.antwortkoerper = """{"error":"schluessel","grund":"fehlt"}"""
        val e = dienst().starten(geraet)
        assertTrue(e is Sitzungsergebnis.Abgewiesen)
        assertEquals(Abweisung.UNBEKANNT, (e as Sitzungsergebnis.Abgewiesen).art)
        assertNull("nichts schwebt", tresor.lesen())
    }

    /**
     * DER GEZEIGTE WERT ist der, den Kontoschlüssel, SPK und beide
     * Zufallszahlen ergeben — und die offengelegte Zufallszahl passt zur
     * Zusage aus dem Start. Das ist genau, was der Browser nachrechnet.
     */
    @Test fun beanspruchtLegtOffenUndZeigtDenWert() {
        val start = starten().getJSONObject("schluessel")
        beansprucht()
        val s = dienst().nachfragen() as Sitzungsstand.Vergleich
        val offen = anliegen("offenlegen").single()
        val nG = Base64.getDecoder().decode(offen.getString("n_g"))
        val spk = Base64.getDecoder().decode(start.getString("spk"))
        assertArrayEquals(Base64.getDecoder().decode(start.getString("zusage")), Kopplungswert.zusage(nG, spk))
        assertEquals(Kopplungswert.wert(konto, spk, nG, nB), s.wert)
        assertEquals("ph***@gen-em.org", s.konto)
        assertEquals(420, s.restSekunden)
        assertFalse(s.wartetBrowser)
    }

    /** Offenlegen ist wiederholbar: dieselbe Zufallszahl bei jeder Abfrage, derselbe Wert. */
    @Test fun wiederholteAbfrageLegtDasselbeOffen() {
        starten()
        beansprucht()
        val a = dienst().nachfragen() as Sitzungsstand.Vergleich
        val b = dienst().nachfragen() as Sitzungsstand.Vergleich
        assertEquals(a.wert, b.wert)
        val ng = anliegen("offenlegen").map { it.getString("n_g") }
        assertEquals(2, ng.size)
        assertEquals(ng[0], ng[1])
    }

    @Test fun wartetBrowserWirdGemeldet() {
        starten()
        beansprucht(zusatz = ""","wartet":"browser"""")
        assertTrue((dienst().nachfragen() as Sitzungsstand.Vergleich).wartetBrowser)
    }

    /** DAS ERSTE PAAR GILT: Ein Server, der später ein anderes `n_b` nennt, bekommt keinen zweiten Wert. */
    @Test fun anderesPaarIstEinAbbruch() {
        starten()
        beansprucht()
        dienst().nachfragen()
        beansprucht(nb = Base64.getEncoder().encodeToString(ByteArray(16) { 9 }))
        val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
        assertEquals(Abweisung.UNBEKANNT, s.art)
        assertEquals("nur das erste offenlegen", 1, anliegen("offenlegen").size)
    }

    /** F-KA-108: `n_b` ohne Kontoschlüssel endet sauber, ohne offenzulegen. */
    @Test fun ohneKontoschluesselKeinOffenlegen() {
        starten()
        beansprucht(ks = "null")
        val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
        assertEquals(Abweisung.KONTO_OHNE_SCHLUESSEL, s.art)
        assertTrue(anliegen("offenlegen").isEmpty())
        assertNull(gs.schwebend())
    }

    @Test fun imWebAbgelehntRaeumtAuf() {
        for ((grund, art) in listOf("wert" to Abweisung.IM_WEB_ABGELEHNT, "zusage" to Abweisung.SCHLUESSEL_PASST_NICHT)) {
            starten()
            server.antwortAus = null
            server.antwortkoerper = """{"zustand":"abgelehnt","grund":"$grund"}"""
            val s = dienst().nachfragen() as Sitzungsstand.Abgewiesen
            assertEquals(art, s.art)
            assertNull("Zugangsdaten fort", tresor.lesen())
            assertNull("kein Paar abgelegt", gs.liefern())
            assertNull("Versuch verworfen", gs.schwebend())
        }
    }

    /** `202 wartet: browser` ist kein Fehler: die Zugangsdaten schweben weiter. */
    @Test fun jaWartetAufDenBrowser() {
        starten()
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.status = 202
        server.antwortkoerper = """{"wartet":"browser"}"""
        assertTrue(dienst().bestaetigen(ja = true) is Bestaetigungsergebnis.WartetBrowser)
        assertTrue(tresor.schwebend())
        assertNull(gs.liefern())
    }

    @Test fun jaImWebAbgelehnt() {
        starten()
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.status = 409
        server.antwortkoerper = """{"error":"abgelehnt","grund":"wert"}"""
        val e = dienst().bestaetigen(ja = true) as Bestaetigungsergebnis.Abgewiesen
        assertEquals(Abweisung.IM_WEB_ABGELEHNT, e.art)
        assertNull(tresor.lesen())
    }

    /** Mit `huelle: 2` wird der Versuch das gültige Paar, und der Tresor merkt sich Fassung 2. */
    @Test fun jaMitHuelle2UebernimmtDasPaar() {
        val spk = Base64.getDecoder().decode(starten().getJSONObject("schluessel").getString("spk"))
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.status = 200
        server.antwortkoerper = """{"ok":true,"kontoschluessel":"$kontoB64","huelle":2}"""
        assertTrue(dienst().bestaetigen(ja = true) is Bestaetigungsergebnis.Gekoppelt)
        assertTrue(tresor.gekoppelt())
        assertTrue(tresor.lesen()!!.huelle2)
        assertArrayEquals(spk, gs.liefern()!!.spk)
    }

    /**
     * Ohne `huelle` (Weiche des Browsers aus, ein Server vor 25.0.0):
     * gekoppelt, aber kein Paar — und seit 0.27.0 (KA-21) heißt das
     * „Herkunft fehlt", nicht mehr Fassung 1.
     */
    @Test fun jaOhneHuelleHerkunftFehlt() {
        starten()
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.status = 200
        server.antwortkoerper = """{"ok":true,"kontoschluessel":"$kontoB64"}"""
        assertTrue(dienst().bestaetigen(ja = true) is Bestaetigungsergebnis.Gekoppelt)
        assertFalse(tresor.lesen()!!.huelle2)
        assertNull(gs.liefern())
        assertEquals(Herkunftsstand.FEHLT, Herkunft.stand(tresor.lesen(), gs))
    }

    /** Der Fall der verlorenen Antwort: `status` sagt `gekoppelt` mit `huelle: 2`. */
    @Test fun statusGekoppeltMitHuelle2() {
        starten()
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.antwortkoerper = """{"zustand":"gekoppelt","kontoschluessel":"$kontoB64","huelle":2}"""
        val s = dienst().nachfragen() as Sitzungsstand.Gekoppelt
        assertTrue(s.huelle2)
        assertNotNull(gs.liefern())
    }

    @Test fun neinVerwirftDenVersuch() {
        starten()
        server.antwortAus = null
        server.antwortkoerper = """{"ok":true}"""
        assertTrue(dienst().bestaetigen(ja = false) is Bestaetigungsergebnis.Abgebrochen)
        assertNull(gs.schwebend())
    }

    @Test fun trennenVergisstDasPaar() {
        jaMitHuelle2UebernimmtDasPaar()
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""
        assertTrue(dienst().trennen() is Trennergebnis.Getrennt)
        assertNull(gs.liefern())
    }

    // ---- Herkunft fehlt (seit KA-15, E-KA-80) --------------------------------

    private fun bindung(): Rueckstandsbindung {
        val kontext = ApplicationProvider.getApplicationContext<Context>()
        kontext.getSharedPreferences("rueckstand", Context.MODE_PRIVATE).edit().clear().commit()
        return Rueckstandsbindung(kontext)
    }

    private fun dienstMitRueckstand(b: Rueckstandsbindung?) = Kopplungsdienst(
        HttpNetzweg(), tresor, server.basis,
        rueckstand = { 2 }, bindung = b, hoechstePaketId = { 17L }, geraeteschluessel = gs,
    )

    /**
     * `status` sagt `huelle: 2`, das Handy hat kein Paar: Der Tresor merkt
     * sich trotzdem Fassung 2 — was der Server verlangt, nicht was das Handy
     * kann. Bis KA-15 merkte er sich hier Fassung 1, und das Handy sendete
     * ohne Herkunftsnachweis weiter.
     */
    @Test fun statusMitHuelle2OhnePaarMerktFassung2() {
        tresor.speichern(Zugangsdaten("dev-1", "k1"))
        server.status = 200
        server.antwortkoerper = """{"zustand":"gekoppelt","kontoschluessel":"$kontoB64","huelle":2}"""
        assertNull(gs.liefern())
        assertEquals(kontoB64, dienst().schluesselHolen())
        assertTrue(tresor.lesen()!!.huelle2)
        assertEquals(Herkunftsstand.FEHLT, Herkunft.stand(tresor.lesen(), gs))
    }

    /**
     * Ohne `huelle` in der Antwort merkt sich der Tresor kein Fassung 2 —
     * und die Herkunft fehlt (seit 0.27.0, KA-21; bis dahin: Fassung 1,
     * [Herkunftsstand.OHNE]).
     */
    @Test fun statusOhneHuelleHerkunftFehlt() {
        tresor.speichern(Zugangsdaten("dev-1", "k1"))
        server.status = 200
        server.antwortkoerper = """{"zustand":"gekoppelt","kontoschluessel":"$kontoB64"}"""
        assertEquals(kontoB64, dienst().schluesselHolen())
        assertFalse(tresor.lesen()!!.huelle2)
        assertEquals(Herkunftsstand.FEHLT, Herkunft.stand(tresor.lesen(), gs))
    }

    /**
     * Fehlt die Herkunft, sperrt der Rückstand das Trennen nicht: Er kann
     * ohnehin nicht gehen, und „neu koppeln" führt über das Trennen. Er
     * bleibt liegen, gebunden an den Kontoschlüssel dieser Kopplung.
     */
    @Test fun herkunftFehltTrenntTrotzRueckstandUndBindet() {
        tresor.speichern(Zugangsdaten("dev-1", "k1", kontoB64, huelle2 = true))
        val b = bindung()
        server.status = 200
        server.antwortkoerper = """{"ok":true}"""

        assertTrue(dienstMitRueckstand(b).trennen() is Trennergebnis.Getrennt)
        assertNull("Die Kopplung ist fort", tresor.lesen())
        assertEquals(17L, b.gebundenBis)
        assertEquals(Bindungsstand.GLEICH, b.stand(kontoB64))
        assertEquals(Bindungsstand.ANDERS, b.stand("SCHLUESSEL-B"))
    }

    /**
     * Ist das Paar da, bleibt die Sperre, wie sie war. Ohne `huelle: 2`
     * fehlt die Herkunft seit 0.27.0 (KA-21) und das Trennen geht trotz
     * Rückstand, gebunden (bis dahin: Sperre wie mit Paar).
     */
    @Test fun rueckstandSperrtWeiterWoDieHerkunftNichtFehlt() {
        val spk = Base64.getDecoder().decode(starten().getJSONObject("schluessel").getString("spk"))
        beansprucht()
        dienst().nachfragen()
        server.antwortAus = null
        server.status = 200
        server.antwortkoerper = """{"ok":true,"kontoschluessel":"$kontoB64","huelle":2}"""
        assertTrue(dienst().bestaetigen(ja = true) is Bestaetigungsergebnis.Gekoppelt)
        assertArrayEquals(spk, gs.liefern()!!.spk)
        val anfragen = server.anfragen.size
        val b = bindung()

        assertTrue(dienstMitRueckstand(b).trennen() is Trennergebnis.Rueckstand)
        assertNotNull("Die Kopplung bleibt", tresor.lesen())
        assertNotNull("und das Paar", gs.liefern())
        assertNull(b.gebundenBis)
        assertEquals("nichts an den Server", anfragen, server.anfragen.size)

        // Ohne Bindung lässt sich nichts binden: Die Sperre gilt ausnahmslos.
        gs.vergessen()
        assertTrue(dienstMitRueckstand(null).trennen() is Trennergebnis.Rueckstand)
        assertNotNull(tresor.lesen())

        // Ohne `huelle: 2` fehlt die Herkunft, mit Paar oder ohne: trennen und binden.
        tresor.huelleSetzen(false)
        server.antwortkoerper = """{"ok":true}"""
        assertEquals(Herkunftsstand.FEHLT, Herkunft.stand(tresor.lesen(), gs))
        assertTrue(dienstMitRueckstand(b).trennen() is Trennergebnis.Getrennt)
        assertNull("Die Kopplung ist fort", tresor.lesen())
        assertEquals(17L, b.gebundenBis)
    }
}
