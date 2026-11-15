package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Paketzeile
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/** Der Nachrichtenkörper gegen den Vertrag (JSON-Vertrag 3 und 4). */
@RunWith(RobolectricTestRunner::class)
class SendekoerperTest {

    private fun segment(final: Boolean = false, dienstRef: String? = "ad-1-0000100002") = Paketzeile(
        id = 1, clientRef = "ar-2-0000300004", art = Paketzeile.ART_RUHESEGMENT,
        tag = "2026-07-16", dienstRef = dienstRef,
        begonnenAt = "2026-07-16T05:02:11Z",
        beendetAt = if (final) "2026-07-16T06:00:00Z" else null,
        final = final, streckeM = null, anstiegM = null,
        bestaetigtSeq = 0, metadatenBestaetigt = false, fehlerhaft = false,
    )

    private fun einsatz(final: Boolean = true) = Paketzeile(
        id = 2, clientRef = "am-3-0000500006", art = Paketzeile.ART_EINSATZ,
        tag = "2026-07-16", dienstRef = "ad-1-0000100002",
        begonnenAt = "2026-07-16T08:31:05Z",
        beendetAt = if (final) "2026-07-16T09:12:40Z" else null,
        final = final, streckeM = 148230, anstiegM = 410,
        bestaetigtSeq = 0, metadatenBestaetigt = false, fehlerhaft = false,
    )

    /** Der Empfänger der Referenzvektoren (`tools/proben/huelle/vektoren.json`). */
    private val ks: ByteArray by lazy {
        val h = org.json.JSONObject(java.io.File("../../tools/proben/huelle/vektoren.json").readText())
            .getJSONObject("empfaenger").getString("oeffentlich_hex")
        ByteArray(h.length / 2) { h.substring(it * 2, it * 2 + 2).toInt(16).toByte() }
    }

    private val punkte = listOf(
        Rohpunkt(47.72611, 10.31862, 712.0, 1_784_279_465),
        Rohpunkt(47.72640, 10.31901, null, 1_784_279_475),
    )

    @Test fun ruhesegment() {
        val o = Sendekoerper.baue(segment(), seqVon = 240, punkte = punkte, kontoschluessel = ks)
        assertEquals("rest_segment", o.getString("kind"))
        assertEquals("ar-2-0000300004", o.getString("client_ref"))
        assertEquals("2026-07-16", o.getString("day"))
        assertEquals("ad-1-0000100002", o.getString("day_ref"))
        assertEquals("2026-07-16T05:02:11Z", o.getString("started_at"))
        assertFalse(o.getBoolean("final"))
        assertEquals(240, o.getJSONObject("track").getInt("seq_from"))
    }

    /** `ended_at` ist null, solange `final` false ist (Vertrag 3). */
    @Test fun endeIstNullSolangeNichtAbgeschlossen() {
        assertTrue(Sendekoerper.baue(segment(final = false), 0, punkte, kontoschluessel = ks).isNull("ended_at"))
        assertEquals(
            "2026-07-16T06:00:00Z",
            Sendekoerper.baue(segment(final = true), 0, punkte, kontoschluessel = ks).getString("ended_at"),
        )
    }

    /** Ohne Dienstkennung geht das Feld gar nicht mit — die Rückfallebene greift. */
    @Test fun ohneDienstkennungFehltDasFeld() {
        assertFalse(Sendekoerper.baue(segment(dienstRef = null), 0, punkte, kontoschluessel = ks).has("day_ref"))
    }

    /**
     * `track` ist ein verschlossenes Spurstück (Vertrag 3):
     * `seq_from`, `punkte`, `ts_von`, `ts_bis` im Klartext, dazu die Hülle —
     * und **keine** Liste `points` mehr, die der Server mit `400 klartext`
     * abwiese.
     */
    @Test fun spurIstEinVerschlossenesStueck() {
        val t = Sendekoerper.baue(segment(), 7, punkte, kontoschluessel = ks).getJSONObject("track")
        assertFalse(t.has("points"))
        assertEquals(7, t.getInt("seq_from"))
        assertEquals(2, t.getInt("punkte"))
        assertEquals(1_784_279_465L, t.getLong("ts_von"))
        assertEquals(1_784_279_475L, t.getLong("ts_bis"))
        val h = java.util.Base64.getDecoder().decode(t.getString("huelle"))
        // Kennbyte, EPK, IV, 2×14 Byte gepolstert auf 32, Prüfwort
        assertEquals(1 + 65 + 16 + 32 + 32, h.size)
        assertEquals(1, h[0].toInt())
    }

    /** Ohne Punkte kein `track` — ein Stück hat 1 bis 2000 (Vertrag 3.2). */
    @Test fun ohnePunkteKeinStueck() {
        assertFalse(Sendekoerper.baue(segment(), 0, emptyList(), kontoschluessel = ks).has("track"))
    }

    @Test fun einsatzTraegtKennzahlenUndPhasen() {
        val o = Sendekoerper.baue(
            einsatz(), 0, punkte, kontoschluessel = ks,
            phasen = listOf(
                Phaseneintrag(2, "2026-07-16T08:31:05Z", 47.7261, 10.3186),
                Phaseneintrag(4, "2026-07-16T08:51:02Z", null, null),
            ),
        )
        assertEquals("mission", o.getString("kind"))
        assertEquals(148230, o.getInt("distance_m"))
        assertEquals(410, o.getInt("ascent_m"))

        val phasen = o.getJSONArray("phases")
        assertEquals(2, phasen.length())
        assertEquals(2, phasen.getJSONObject(0).getInt("phase"))
        // Die Koordinate steht in der Einsatzhülle, nicht in phases[].
        assertFalse(phasen.getJSONObject(0).has("lat"))
        assertFalse(phasen.getJSONObject(0).has("lon"))
        assertTrue(o.getString("ort_huelle").isNotEmpty())
    }

    /**
     * `resus_sessions` geht **gar nicht** mit — nicht einmal als leere Liste.
     * Die Reanimation bleibt bei der Garmin. Eine leere Liste hieße
     * „es gibt keine"; ein fehlender Schlüssel heißt „dazu sage ich nichts",
     * und nur das ist wahr.
     */
    @Test fun reanimationWirdGarNichtErwaehnt() {
        val o = Sendekoerper.baue(einsatz(), 0, punkte, kontoschluessel = ks)
        assertFalse(o.has("resus_sessions"))
        assertFalse(o.has("resus"))
    }

    /** Ein Ruhesegment trägt keine Phasen und keine Kennzahlen. */
    @Test fun ruhesegmentTraegtKeinePhasen() {
        val o = Sendekoerper.baue(segment(), 0, punkte, kontoschluessel = ks)
        assertFalse(o.has("phases"))
        assertFalse(o.has("distance_m"))
        assertFalse(o.has("ort_huelle"))
    }

    /** Die harte Grenze des Servers: 512 KB Körper (Vertrag 6). */
    @Test fun einVollesTeilstueckBleibtWeitUnterDerKoerpergrenze() {
        val viele = (0 until Sender.CHUNK_PUNKTE).map {
            Rohpunkt(47.72611 + it * 0.0001, 10.31862 + it * 0.0001, 712.0 + it, 1_784_279_465L + it)
        }
        val bytes = Sendekoerper.baue(segment(), 0, viele, kontoschluessel = ks).toString().toByteArray(Charsets.UTF_8).size
        println("Körper mit ${Sender.CHUNK_PUNKTE} Punkten: $bytes Bytes " +
            "(Grenze ${Sender.HOECHSTE_KOERPERGROESSE})")
        assertTrue(
            "Ein volles Teilstück muss unter die Grenze passen — sonst gäbe es 413 im Normalbetrieb",
            bytes < Sender.HOECHSTE_KOERPERGROESSE,
        )
    }

    // ---- Fassung 2 (seit KA-06c) ------------------------------------------

    private val geraet = org.genem.nadoku.handy.tresor.SoftwareAbsender.erzeugen()
    private var z = 100L
    private val abschluss = java.time.Instant.parse("2026-07-16T06:00:00Z").epochSecond
    private fun f2() = Fassung2(geraet, { ++z }, { 1_800_000_000L })
    private fun auf(b64: String, art: String, ref: String, platz: String): ByteArray =
        Huellenoeffner.oeffnen2(java.util.Base64.getDecoder().decode(b64), geraet.spk, art, ref, platz)

    /**
     * Ein abgeschlossener Einsatz, letztes Stück: Die Einsatzhülle trägt den
     * Zähler und `schluss`, das Spurstück den Merker SCHLUSS, beide unter der
     * Absenderkennung des Geräts.
     */
    @Test fun fassung2AbgeschlossenUndLetztesStueck() {
        val o = Sendekoerper.baue(einsatz(final = true), 0, punkte, emptyList(), Huellenoeffner.konto, f2(), true)
        val h = java.util.Base64.getDecoder().decode(o.getString("ort_huelle"))
        assertEquals(2, h[0].toInt())
        org.junit.Assert.assertArrayEquals(geraet.sid(), h.copyOfRange(1, 9))
        val innen = org.json.JSONObject(String(auf(o.getString("ort_huelle"), "einsatz", "am-3-0000500006", ""), Charsets.UTF_8))
        assertEquals(101L, innen.getLong("z"))
        assertTrue(innen.getBoolean("schluss"))
        val spur = auf(o.getJSONObject("track").getString("huelle"), "spur", "am-3-0000500006", "0:2")
        assertEquals("SCHLUSS am letzten Stück", 1, spur[8].toInt())
    }

    /** Ein abgeschlossenes Paket, dessen Stück nicht alle offenen Punkte trägt: kein SCHLUSS. */
    @Test fun fassung2NichtLetztesStueckOhneSchluss() {
        val o = Sendekoerper.baue(einsatz(final = true), 0, punkte, emptyList(), Huellenoeffner.konto, f2(), false)
        val spur = auf(o.getJSONObject("track").getString("huelle"), "spur", "am-3-0000500006", "0:2")
        assertEquals(0, spur[8].toInt())
    }

    /** Nicht abgeschlossen: weder die Einsatzhülle noch das Stück sagt Schluss; jede Anfrage ein neues `z`. */
    @Test fun fassung2LaufendOhneSchluss() {
        val zaehler = f2()
        val a = Sendekoerper.baue(einsatz(final = false), 0, punkte, emptyList(), Huellenoeffner.konto, zaehler, true)
        val b = Sendekoerper.baue(einsatz(final = false), 0, punkte, emptyList(), Huellenoeffner.konto, zaehler, true)
        val ia = org.json.JSONObject(String(auf(a.getString("ort_huelle"), "einsatz", "am-3-0000500006", ""), Charsets.UTF_8))
        val ib = org.json.JSONObject(String(auf(b.getString("ort_huelle"), "einsatz", "am-3-0000500006", ""), Charsets.UTF_8))
        assertFalse(ia.getBoolean("schluss"))
        assertTrue("z steigt je Anfrage", ib.getLong("z") > ia.getLong("z"))
        assertEquals(0, auf(a.getJSONObject("track").getString("huelle"), "spur", "am-3-0000500006", "0:2")[8].toInt())
    }

    /**
     * Abgeschlossen, kein Punkt mehr offen: das leere Schlussstück (Vertrag
     * 3.4) — `punkte` 0, Platz `<seq>:0`, `ts_von` = `ts_bis` = Ende des
     * Pakets, innen nur der Kopf mit SCHLUSS.
     */
    @Test fun leeresSchlussstueckAmEnde() {
        val o = Sendekoerper.baue(segment(final = true), 240, emptyList(), emptyList(), Huellenoeffner.konto, f2(), true)
        val t = o.getJSONObject("track")
        assertEquals(240L, t.getLong("seq_from"))
        assertEquals(0, t.getInt("punkte"))
        assertEquals(abschluss, t.getLong("ts_von"))
        assertEquals(abschluss, t.getLong("ts_bis"))
        val innen = auf(t.getString("huelle"), "ruhe", "ar-2-0000300004", "240:0")
        assertEquals(12, innen.size)
        assertEquals(1, innen[8].toInt())
    }

    /** Fassung 1 kennt kein Schlussstück; ein laufendes Paket ohne Punkte hat in beiden Fassungen keins. */
    @Test fun keinSchlussstueckOhneFassung2OderOhneAbschluss() {
        assertFalse(Sendekoerper.baue(segment(final = true), 240, emptyList(), kontoschluessel = ks).has("track"))
        assertFalse(
            Sendekoerper.baue(segment(final = false), 240, emptyList(), emptyList(), Huellenoeffner.konto, f2(), true)
                .has("track"),
        )
    }
}
