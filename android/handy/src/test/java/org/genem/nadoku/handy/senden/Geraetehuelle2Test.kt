package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.tresor.SoftwareAbsender
import org.json.JSONObject
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.math.BigInteger
import java.nio.ByteBuffer
import java.time.Instant
import java.util.Base64
import javax.crypto.Cipher
import javax.crypto.KeyAgreement
import javax.crypto.Mac
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * Die Gerätehülle, Fassung 2, gegen die Vektoren der Referenz.
 *
 * Anlass: Nr. 563 — Fassung 2 belegt Herkunft, Zeit und Vollständigkeit, und
 * drei Umsetzungen (Garmin, Android, Browser) müssen sie byte-gleich
 * schreiben. `tools/proben/huelle/vektoren-2.json` wird an Ort und Stelle
 * gelesen, nicht kopiert (wie in `GeraetehuelleTest`).
 *
 * WAS HIER GEMESSEN WIRD: (1) die Hülle selbst an JEDEM Fall der Datei, mit
 * dem Klartext der Datei — auch an denen der Uhr und des Kontos, denn die
 * Rechnung kennt keinen Unterschied; (2) die Innenformen, die das Handy
 * selbst baut — Spurstück mit und ohne SCHLUSS, das leere Schlussstück, die
 * Einsatzhülle mit Zähler —, aus Punkten und Phasen bis zur fertigen Hülle.
 * Den Fall „einsatz-json" baut das Handy NICHT aus Phasen nach: Er trägt
 * eine Reanimation, und die dokumentiert das Handy nicht; dafür stehen die
 * Einsatzfälle ohne Reanimation („zurueckgelegt", „wechsel-…").
 */
@RunWith(RobolectricTestRunner::class)
class Geraetehuelle2Test {

    private val v: JSONObject by lazy {
        // Arbeitsverzeichnis der Prüffälle ist android/handy (Gradle).
        JSONObject(File("../../tools/proben/huelle/vektoren-2.json").readText())
    }

    private fun hex(s: String): ByteArray =
        ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    private fun zuHex(b: ByteArray): String = b.joinToString("") { "%02x".format(it) }
    private fun b64(s: String): ByteArray = Base64.getDecoder().decode(s)

    private val konto: ByteArray get() = hex(v.getJSONObject("konto").getString("oeffentlich_hex"))

    private fun paar(wer: String): SoftwareAbsender {
        val o = if (wer == "konto") v.getJSONObject("konto") else v.getJSONObject("geraete").getJSONObject(wer)
        return SoftwareAbsender(hex(o.getString("privat_hex")), hex(o.getString("oeffentlich_hex")))
    }

    private fun liste(name: String): List<JSONObject> =
        v.getJSONArray(name).let { a -> (0 until a.length()).map { a.getJSONObject(it) } }

    private fun fall(name: String): JSONObject = liste("faelle").first { it.getString("name") == name }
    private fun einsatzfall(name: String): JSONObject = liste("einsaetze").first { it.getString("name") == name }

    /** Wie `_punkte(von, n)` der Referenz, mit absoluter Zeit ab `ts`. */
    private fun punkte(von: Int, n: Int, ts: Long): List<Rohpunkt> = (von until von + n).map { i ->
        Rohpunkt(47.7261 + i * 0.00031, 10.3159 + i * 0.00047, 674.0 + (i % 37) * 0.7, ts + (i - von) * 5L)
    }

    private val t0: Long get() = 1784277065L
    private val phasen2 = listOf(
        Phaseneintrag(4, Instant.ofEpochSecond(1784277065L + 1197).toString(), 47.5601, 10.7002),
        Phaseneintrag(5, Instant.ofEpochSecond(1784277065L + 1310).toString(), null, null),
    )

    // ---- Die Hülle ------------------------------------------------------

    /** Jeder Fall der Datei — Uhr, Handy, Konto —, mit festem flüchtigem Schlüssel und IV: dieselben Bytes. */
    @Test fun alleFaelleByteGleich() {
        val faelle = liste("faelle")
        assertEquals(8, faelle.size)
        assertEquals(3, faelle.count { it.getString("absender") == "handy" })
        for (f in faelle) {
            val absender = paar(f.getString("absender"))
            assertEquals(f.getString("name"), f.getString("sid_hex"), zuHex(absender.sid()))
            val h = Geraetehuelle.verschliessen2(
                hex(f.getString("klartext_hex")), konto, absender, f.getString("art"), f.getString("client_ref"),
                f.getString("platz"),
                fluechtig = Geraetehuelle.paarAus(hex(f.getString("fluechtig_privat_hex")), hex(f.getString("epk_hex"))),
                iv = hex(f.getString("iv_hex")),
            )
            assertArrayEquals(f.getString("name"), b64(f.getString("huelle_b64")), h)
        }
    }

    /** Die Kennung des Absenders für Konto und alle drei Geräte der Datei. */
    @Test fun sidWieReferenz() {
        assertEquals(v.getJSONObject("konto").getString("sid_hex"), zuHex(Geraetehuelle.sid(konto)))
        for (n in listOf("uhr", "handy", "fremd")) {
            val g = v.getJSONObject("geraete").getJSONObject(n)
            assertEquals(n, g.getString("sid_hex"), zuHex(Geraetehuelle.sid(hex(g.getString("oeffentlich_hex")))))
        }
    }

    /** Die Punktrechnung der Prüfhilfe stimmt — sonst mäßen die Fälle darunter einen eigenen Fehler. */
    @Test fun kurvenhilfeTrifftDieDatei() {
        for (f in liste("faelle")) {
            val d = BigInteger(f.getString("fluechtig_privat_hex"), 16)
            assertEquals(f.getString("epk_hex"), zuHex(Kurvenhilfe.punkt(d)))
            assertEquals(d, Kurvenhilfe.dAusName("nadoku-huelle2-vektor-fluechtig-" + f.getString("name")))
            assertEquals(f.getString("iv_hex"), zuHex(Kurvenhilfe.iv2(f.getString("name"))))
        }
    }

    // ---- Die Innenformen, vom Handy gebaut ------------------------------

    /** Fall „spur-3": drei Punkte ab Nummer 120, kein Schluss — aus Punkten bis zur Hülle. */
    @Test fun spurstueckAusPunkten() {
        val f = fall("spur-3")
        val p = punkte(120, 3, t0 + 600)
        assertEquals(f.getString("klartext_hex"), zuHex(Geraetehuelle.spurInnen2(p, t0 + 600, schluss = false)))
        val s = Geraetehuelle.spurstueck2(
            "spur", f.getString("client_ref"), 120, p, konto, paar("uhr"), schluss = false,
            fluechtig = Kurvenhilfe.fluechtig2("spur-3"), iv = Kurvenhilfe.iv2("spur-3"),
        )
        assertEquals(f.getString("huelle_b64"), s.getString("huelle"))
        assertEquals(120L, s.getLong("seq_from"))
        assertEquals(3, s.getInt("punkte"))
        assertEquals(t0 + 600, s.getLong("ts_von"))
        assertEquals(t0 + 610, s.getLong("ts_bis"))
    }

    /** Fall „spur-schluss": das letzte Stück trägt Bit 0 im Merker. */
    @Test fun spurstueckMitSchluss() {
        val f = fall("spur-schluss")
        val s = Geraetehuelle.spurstueck2(
            "spur", f.getString("client_ref"), 123, punkte(123, 2, t0 + 615), konto, paar("uhr"), schluss = true,
            fluechtig = Kurvenhilfe.fluechtig2("spur-schluss"), iv = Kurvenhilfe.iv2("spur-schluss"),
        )
        assertEquals(f.getString("huelle_b64"), s.getString("huelle"))
        val innen = Geraetehuelle.spurInnen2(punkte(123, 2, t0 + 615), t0 + 615, schluss = true)
        assertEquals(1, innen[8].toInt())
        assertEquals(12 + 2 * 14, innen.size)
    }

    /** Fall „ruhe-schluss-leer" (Handy): nur der Kopf, Platz `40:0`, `ts_von` innen und außen gleich. */
    @Test fun leeresSchlussstueck() {
        val f = fall("ruhe-schluss-leer")
        assertEquals("handy", f.getString("absender"))
        assertEquals("40:0", f.getString("platz"))
        val s = Geraetehuelle.schlussstueck2(
            "ruhe", f.getString("client_ref"), 40, t0, konto, paar("handy"),
            fluechtig = Kurvenhilfe.fluechtig2("ruhe-schluss-leer"), iv = Kurvenhilfe.iv2("ruhe-schluss-leer"),
        )
        assertEquals(f.getString("huelle_b64"), s.getString("huelle"))
        assertEquals(40L, s.getLong("seq_from"))
        assertEquals(0, s.getInt("punkte"))
        assertEquals(t0, s.getLong("ts_von"))
        assertEquals(t0, s.getLong("ts_bis"))
        // Innen: 12 Byte, ts_von = die Abschlusszeit, Merker SCHLUSS, sonst Nullen.
        val innen = hex(f.getString("klartext_hex"))
        assertEquals(12, innen.size)
        assertEquals(t0, ByteBuffer.wrap(innen).getLong(0))
        assertEquals(1, innen[8].toInt())
        assertEquals(0, innen[9].toInt() or innen[10].toInt() or innen[11].toInt())
    }

    /** Der Kopf: absolute Zeit als uint64, Merker nur SCHLUSS, Herkunft 0, Reserve 0. */
    @Test fun spurKopf() {
        val p = listOf(Rohpunkt(47.0, 10.0, null, 5_000_000_000L), Rohpunkt(47.1, 10.1, 12.0, 5_000_000_007L))
        val innen = Geraetehuelle.spurInnen2(p, 5_000_000_000L, schluss = false)
        val b = ByteBuffer.wrap(innen)
        assertEquals("über 2038 hinaus", 5_000_000_000L, b.getLong(0))
        assertEquals(0, b.getInt(8))
        assertEquals(7, b.getInt(12 + 14 + 10))
        assertArrayEquals(Geraetehuelle.spurInnen(p, 5_000_000_000L), innen.copyOfRange(12, innen.size))
    }

    /** Die Einsatzhülle innen: Reihenfolge der Schlüssel, Zähler, absoluter Beginn, leere `rea`. */
    @Test fun einsatzInnenWortgleich() {
        assertEquals(
            """{"fassung":2,"z":${t0 + 1320},"start":$t0,"schluss":false,"phasen":[""" +
                """{"phase":4,"t":1197,"lat":47.5601,"lon":10.7002},{"phase":5,"t":1310,"lat":null,"lon":null}],"rea":[]}""",
            Geraetehuelle.einsatzInnen2(Instant.ofEpochSecond(t0).toString(), phasen2, t0 + 1320, schluss = false),
        )
        assertEquals(
            """{"fassung":2,"z":1,"start":$t0,"schluss":true,"phasen":[],"rea":[]}""",
            Geraetehuelle.einsatzInnen2(Instant.ofEpochSecond(t0).toString(), emptyList(), 1, schluss = true),
        )
        assertTrue(runCatching { Geraetehuelle.einsatzInnen2(Instant.ofEpochSecond(t0).toString(), emptyList(), 0, true) }.isFailure)
    }

    /**
     * Drei Einsatzhüllen des Handys aus dem Block `einsaetze`, aus Phasen bis
     * zur Hülle: die mit Zähler 2 und die beiden der Übergabe (E-KA-64), deren
     * Zähler eine Unix-Sekunde ist.
     */
    @Test fun einsatzhuellenAusPhasen() {
        val ref = "m-20261111-0004"
        val beginn = Instant.ofEpochSecond(t0).toString()
        val handy = paar("handy")
        fun gebaut(name: String, phasen: List<Phaseneintrag>, z: Long, schluss: Boolean) = Geraetehuelle.einsatz2(
            ref, beginn, phasen, konto, handy, z, schluss,
            fluechtig = Kurvenhilfe.fluechtig2(name), iv = Kurvenhilfe.iv2(name),
        )
        assertEquals(einsatzfall("zurueckgelegt").getString("huelle_b64"), gebaut("e-z2", phasen2.take(1), 2, false))
        assertEquals(
            einsatzfall("wechsel-erstes-geraet").getString("huelle_b64"),
            gebaut("e-wechsel-handy", phasen2, t0 + 1320, false),
        )
        assertEquals(
            einsatzfall("wechsel-zurueck-zum-ersten").getString("huelle_b64"),
            gebaut("e-wechsel-handy-spaeter", phasen2, t0 + 1600, true),
        )
    }

    // ---- Zufall und Gegenlagen -------------------------------------------

    /** Eine echte Hülle (Zufall, frisches Gerätepaar) geht mit dem privaten Kontoschlüssel auf — und nur richtig gebunden. */
    @Test fun echteHuelleGehtAuf() {
        val geraet = SoftwareAbsender.erzeugen()
        val klar = "nadoku-handy-2".toByteArray()
        val h1 = Geraetehuelle.verschliessen2(klar, konto, geraet, "spur", "ah-1-0000000001", "0:1")
        val h2 = Geraetehuelle.verschliessen2(klar, konto, geraet, "spur", "ah-1-0000000001", "0:1")
        assertFalse("je Hülle ein neues flüchtiges Paar und ein neuer IV", h1.contentEquals(h2))
        assertEquals(2, h1[0].toInt())
        assertArrayEquals(geraet.sid(), h1.copyOfRange(1, 9))
        assertArrayEquals(klar, oeffnen2(h1, geraet.spk, "spur", "ah-1-0000000001", "0:1"))
        assertTrue("anderer Platz", runCatching { oeffnen2(h1, geraet.spk, "spur", "ah-1-0000000001", "1:1") }.isFailure)
        // Die Herkunft: Mit dem Schlüssel eines anderen Geräts im Gerätebuch stimmt das Prüfwort nicht.
        assertTrue("anderes Gerät", runCatching { oeffnen2(h1, paar("fremd").spk, "spur", "ah-1-0000000001", "0:1") }.isFailure)
    }

    /**
     * `Z_s` wird je Hülle beim Absender BESTELLT und nicht aufbewahrt (seit
     * KA-06f): eine Vereinbarung je Hülle, auch bei demselben Konto. Und es
     * ist das `Z_s` DIESES Absenders, das in die Hülle eingeht — ein
     * Absender, der unter fremdem `spk` mit eigenem Schlüssel rechnet, baut
     * eine Hülle, die unter keinem der beiden aufgeht.
     */
    @Test fun zsJeHuelleVomAbsender() {
        val geraet = SoftwareAbsender.erzeugen()
        val klar = "nadoku-handy-2".toByteArray()
        repeat(3) { Geraetehuelle.verschliessen2(klar, konto, geraet, "spur", "ah-1-0000000001", "0:1") }
        assertEquals("je Hülle eine Vereinbarung, nichts zwischengespeichert", 3, geraet.vereinbarungen)
        Geraetehuelle.einsatz2("m-1", Instant.ofEpochSecond(t0).toString(), phasen2, konto, geraet, 1, false)
        Geraetehuelle.schlussstueck2("ruhe", "ah-1-0000000001", 4, t0, konto, geraet)
        assertEquals(5, geraet.vereinbarungen)

        val anderer = SoftwareAbsender.erzeugen()
        val falsch = object : org.genem.nadoku.handy.tresor.Absender {
            override val spk get() = geraet.spk
            override fun vereinbaren(konto: java.security.interfaces.ECPublicKey) = anderer.vereinbaren(konto)
        }
        val h = Geraetehuelle.verschliessen2(klar, konto, falsch, "spur", "ah-1-0000000001", "0:1")
        assertArrayEquals("die Kennung nennt den vorgegebenen Schlüssel", geraet.sid(), h.copyOfRange(1, 9))
        assertTrue(runCatching { oeffnen2(h, geraet.spk, "spur", "ah-1-0000000001", "0:1") }.isFailure)
        assertTrue(runCatching { oeffnen2(h, anderer.spk, "spur", "ah-1-0000000001", "0:1") }.isFailure)
        // Ein Geheimnis falscher Länge wird nicht verarbeitet.
        val kurz = object : org.genem.nadoku.handy.tresor.Absender {
            override val spk get() = geraet.spk
            override fun vereinbaren(konto: java.security.interfaces.ECPublicKey) = ByteArray(31)
        }
        assertTrue(runCatching { Geraetehuelle.verschliessen2(klar, konto, kurz, "spur", "ah-1-0000000001", "0:1") }.isFailure)
    }

    /** Jede Störung der Datei an der Hülle selbst scheitert auch am Öffner dieses Prüffalls. */
    @Test fun stoerungenScheitern() {
        val buch = mapOf(
            v.getJSONObject("geraete").getJSONObject("uhr").getString("sid_hex") to paar("uhr").spk,
            v.getJSONObject("geraete").getJSONObject("handy").getString("sid_hex") to paar("handy").spk,
        )
        // Seit KA-07 scheitert jede Störung, auch die Hülle der Fassung 1 (am
        // Kennbyte); den Merker `verlangt2` hat die Referenz nicht mehr.
        val stoerungen = liste("stoerungen")
        assertEquals(14, stoerungen.size)
        for (s in stoerungen) {
            val h = b64(s.getString("huelle_b64"))
            val ging = runCatching {
                check(h.size >= 90 + 16 + 32 && (h.size - 122) % 16 == 0) { "laenge" }
                check(h[0].toInt() == 2) { "fassung" }
                val spk = buch[zuHex(h.copyOfRange(1, 9))] ?: error("absender")
                oeffnen2(h, spk, s.getString("art"), s.getString("client_ref"), s.getString("platz"))
            }
            assertTrue(s.getString("name"), ging.isFailure)
        }
    }

    // Öffnen gibt es in der App nicht — nur im Prüffall (`Huellenoeffner`, `oeffnen2()` der Referenz).
    private fun oeffnen2(h: ByteArray, spk: ByteArray, art: String, ref: String, platz: String): ByteArray =
        Huellenoeffner.oeffnen2(h, spk, art, ref, platz)
}
