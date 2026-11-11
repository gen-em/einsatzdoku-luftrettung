package org.genem.nadoku.handy.senden

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/** Die Antworten von `ingest.php` (JSON-Vertrag 5). */
@RunWith(RobolectricTestRunner::class)
class SendeantwortTest {

    @Test fun erfolg() {
        val a = Sendeantwort.lese(200, """{"ok":true,"id":17,"stored_points":212,"next_seq":452}""")
            as Sendeantwort.Angekommen
        assertEquals(17L, a.id)
        assertEquals(212, a.gespeichertePunkte)
        assertEquals(452L, a.naechsteSeq)
        assertTrue("Nichts verworfen, nichts übergangen", a.vollstaendig)
    }

    /**
     * **Ein `ok: true` mit `rejected` ist kein reiner Erfolg.**
     * Der Upload ist angekommen, aber nicht vollständig übernommen.
     */
    @Test fun erfolgMitVerworfenenWerten() {
        val a = Sendeantwort.lese(
            200,
            """{"ok":true,"id":17,"next_seq":10,"rejected":{"phases.phase: ausserhalb von 2…9":2}}""",
        ) as Sendeantwort.Angekommen
        assertFalse(a.vollstaendig)
        assertEquals(2, a.verworfen["phases.phase: ausserhalb von 2…9"])
    }

    /** `kept_*` sagt: eine ganze Liste wurde übergangen, der Stand blieb. */
    @Test fun erfolgMitUebergangenerListe() {
        val a = Sendeantwort.lese(200, """{"ok":true,"next_seq":3,"kept_phases":8}""")
            as Sendeantwort.Angekommen
        assertFalse(a.vollstaendig)
        assertEquals(8, a.uebergangen["kept_phases"])
    }

    /**
     * **`kept_points` und `kept_meta`**: Ohne sie sähe ein spätes
     * Abschlusspaket wie ein Erfolg aus.
     */
    @Test fun keptPointsUndKeptMetaSindKeinReinerErfolg() {
        val a = Sendeantwort.lese(200, """{"ok":true,"next_seq":40,"kept_points":12,"kept_meta":1}""")
            as Sendeantwort.Angekommen
        assertFalse(a.vollstaendig)
        assertEquals(12, a.uebergangen["kept_points"])
        assertEquals(1, a.uebergangen["kept_meta"])
    }

    /** **403** nach Grund — und ohne Körper ein ehrliches „unbekannt". */
    @Test fun vierhundertdreiNennenDenGrund() {
        assertEquals(Sendeantwort.Gesperrt(Sperrgrund.GERAET_DEAKTIVIERT),
            Sendeantwort.lese(403, """{"error":"device_disabled"}"""))
        assertEquals(Sendeantwort.Gesperrt(Sperrgrund.KONTO_GESPERRT),
            Sendeantwort.lese(403, """{"error":"konto","grund":"gesperrt"}"""))
        assertEquals(Sendeantwort.Gesperrt(Sperrgrund.KONTO_WARTET),
            Sendeantwort.lese(403, """{"error":"konto","grund":"wartet"}"""))
        assertEquals(Sendeantwort.Gesperrt(Sperrgrund.KONTO_UNBESTAETIGT),
            Sendeantwort.lese(403, """{"error":"konto","grund":"unbestaetigt"}"""))
        assertEquals("konto mit unbekanntem Grund bleibt eine Sperre",
            Sendeantwort.Gesperrt(Sperrgrund.UNBEKANNT), Sendeantwort.lese(403, """{"error":"konto","grund":"neu"}"""))
    }

    /**
     * **Ohne das Feld des Vertrags ist nichts endgültig** (seit Android
     * 0.23.0, KA-04, F-KA-18). Bis dahin galt ein 403 ohne Rumpf als Sperre
     * (Beginn der 30-Tage-Frist), jede 400 als „fehlerhaft". Dieselben Zahlen
     * schickt auch ein Zwischenstück.
     */
    @Test fun ohneVertragsfeldIstNichtsEndgueltig() {
        assertEquals(Sendeantwort.SpaeterErneut(403), Sendeantwort.lese(403, null))
        assertEquals(Sendeantwort.SpaeterErneut(403), Sendeantwort.lese(403, "<html>"))
        assertEquals(Sendeantwort.SpaeterErneut(403), Sendeantwort.lese(403, """{"error":"forbidden"}"""))
        assertEquals(Sendeantwort.SpaeterErneut(401), Sendeantwort.lese(401, null))
        assertEquals(Sendeantwort.SpaeterErneut(401), Sendeantwort.lese(401, "<html>Anmeldung im WLAN</html>"))
        assertEquals(Sendeantwort.SpaeterErneut(400), Sendeantwort.lese(400, null))
        assertEquals(Sendeantwort.SpaeterErneut(400), Sendeantwort.lese(400, "Bad Request"))
        assertEquals("klartext: parken, nicht verwerfen (Vertrag 5)",
            Sendeantwort.SpaeterErneut(400, Sendeantwort.WARTEN_KONTO_VOLL_S),
            Sendeantwort.lese(400, """{"error":"klartext","grund":"track.points"}"""))
    }

    /** **Wartezeiten**: Kopfzeile vor Vorgabe, gedeckelt. */
    @Test fun wartezeitenAusKopfzeileOderVorgabe() {
        assertEquals(Sendeantwort.SpaeterErneut(429, 120), Sendeantwort.lese(429, null, 120))
        assertEquals(Sendeantwort.SpaeterErneut(429, Sendeantwort.WARTEN_429_S), Sendeantwort.lese(429, null))
        assertEquals(Sendeantwort.SpaeterErneut(503, 5), Sendeantwort.lese(503, null, 5))
        assertEquals(Sendeantwort.SpaeterErneut(503, Sendeantwort.WARTEN_503_S), Sendeantwort.lese(503, null))
        assertEquals(Sendeantwort.SpaeterErneut(429, Sendeantwort.WARTEN_HOECHSTENS_S),
            Sendeantwort.lese(429, null, 999_999))
        assertEquals("Andere 5xx ohne Wartezeit", Sendeantwort.SpaeterErneut(500), Sendeantwort.lese(500, null, 60))
        assertEquals(Sendeantwort.KontoVoll,
            Sendeantwort.lese(507, """{"error":"kontingent","grund":"Das Konto ist voll"}"""))
    }

    @Test fun dieVierFehlerpfadeDesVertrags() {
        assertEquals(Sendeantwort.Fehlerhaft, Sendeantwort.lese(400, """{"error":"payload"}"""))
        assertEquals(Sendeantwort.SchluesselAbgewiesen, Sendeantwort.lese(401, """{"error":"auth"}"""))
        assertEquals(Sendeantwort.ZuGross, Sendeantwort.lese(413, """{"error":"too_large"}"""))
        assertEquals(Sendeantwort.SpaeterErneut(503, Sendeantwort.WARTEN_503_S), Sendeantwort.lese(503, null))
        assertEquals(Sendeantwort.SpaeterErneut(500), Sendeantwort.lese(500, "kaputt"))
    }

    /** 200 ohne `ok` ist kein Erfolg — und darf keiner werden. */
    @Test fun zweihundertOhneOkIstKeinErfolg() {
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, """{"ok":false}"""))
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, ""))
        assertEquals(Sendeantwort.SpaeterErneut(200), Sendeantwort.lese(200, "kein json"))
    }
}
