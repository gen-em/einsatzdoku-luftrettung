package org.genem.nadoku.handy.uhr

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Uhrmeldung
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.handy.dienst.Zeit
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
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
import java.time.Instant
import kotlin.random.Random

/**
 * Die Annahme der Uhr-Ereignisse am Handy (Abnahme C2, E-S4-10).
 *
 * HIER LIEGEN ZWEI DER FÜNF ABNAHMEFÄLLE: **Doppelzustellung nach verlorener
 * Quittung** (kein zweiter Einsatz, der `wm-`-Anker greift) und
 * **Phasenkonflikt Uhr/Handy** (beide Einträge gesendet). Die drei anderen
 * liegen auf der Uhr-Seite, weil dort gepuffert wird.
 *
 * Robolectric, weil echtes SQLite läuft: Die Buchführung über die
 * übernommenen Nummern ist der Kern der Sache, und eine Attrappe prüfte genau
 * sie nicht.
 */
@RunWith(RobolectricTestRunner::class)
class UhrannahmeTest {

    private lateinit var kontext: Context
    private lateinit var puffer: Puffer
    private lateinit var klammer: Dienstklammer
    private lateinit var annahme: Uhrannahme
    private var uhrzeit: Instant = Instant.parse("2026-07-16T05:00:00Z")

    private class Merkzaehler(var wert: Long = 0) : Kennungen.Zaehlerspeicher {
        override fun lies() = wert
        override fun schreib(wert: Long) { this.wert = wert }
    }

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATEI)
        puffer = Puffer(kontext, DATEI)
        klammer = Dienstklammer(
            puffer = puffer,
            kennungen = Kennungen(Merkzaehler(), Random(4)),
            ausduenner = Ausduenner(),
            jetzt = { uhrzeit },
        )
        annahme = Uhrannahme(puffer, klammer) { Modus.MIT_PHASENKNOEPFEN }
    }

    @After fun abbauen() {
        puffer.close()
        kontext.deleteDatabase(DATEI)
    }

    private var nr = 0L
    private fun meldung(
        art: Ereignisart,
        zeit: String,
        phase: Int? = null,
        einsatzRef: String? = null,
        uhrId: String = UHR,
        nummer: Long? = null,
    ) = Uhrmeldung(
        uhrId = uhrId,
        nr = nummer ?: ++nr,
        art = art,
        zeitMs = Instant.parse(zeit).toEpochMilli(),
        phase = phase,
        einsatzRef = einsatzRef,
    )

    // ---- Der Dienststart der Uhr --------------------------------------------

    /**
     * **`started_at` ist die Auslösezeit der Uhr** (E-S4-10) — nicht die
     * Ankunft. Zwischen beiden können im Funkloch Minuten liegen, und der
     * Dienst begann, als die Uhr gedrückt wurde.
     */
    @Test fun derDienststartTraegtDieZeitDerUhr() {
        uhrzeit = Instant.parse("2026-07-16T05:20:00Z")     // das Handy erfährt es später
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))

        val dienst = klammer.laufenderDienst()
        assertNotNull(dienst)
        assertEquals("2026-07-16T05:00:00Z", dienst!!.begonnenAt)
        assertTrue("Der Dienst trägt die ad-Kennung des HANDYS (E-R45-13)",
            dienst.dienstRef.startsWith("ad-"))
    }

    /** Die Quittung nennt die höchste lückenlos übernommene Nummer. */
    @Test fun jedesEreignisWirdQuittiert() {
        assertEquals(1L, annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z")).bisNr)
        assertEquals(2L, annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2)).bisNr)
    }

    // ---- Doppelzustellung nach verlorener Quittung ---------------------------

    /**
     * **Doppelzustellung: kein zweiter Einsatz.**
     *
     * Dieselbe Nachricht, zweimal — wortgleich, wie die Uhr sie nachliefert.
     * Am Ende steht **ein** Einsatz mit **einer** Phase, und die Quittung geht
     * beide Male zurück.
     */
    @Test fun eineDoppelzustellungLegtKeinenZweitenEinsatzAn() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        val phase = meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM)

        val erste = annahme.uebernimm(phase)
        val zweite = annahme.uebernimm(phase)

        assertEquals("Auch die Doppelzustellung wird quittiert", erste, zweite)
        assertEquals("Ein Einsatz", 1, einsaetze().size)
        assertEquals("Eine Phase", 1, puffer.phasen(einsaetze().single().id).size)
        assertEquals("Und er trägt die Kennung der Uhr", WM, einsaetze().single().clientRef)
    }

    /**
     * **Der `wm-`-Anker greift auch ohne Buchführung.**
     *
     * Zweiter Boden: Wäre der Puffer des Handys neu (App neu eingerichtet),
     * kennte die Buchführung die Nummer nicht — die Kennung führt trotzdem auf
     * denselben Einsatz zurück. Nachgestellt wird das mit einer **anderen**
     * Nummer, die die Buchführung durchlässt.
     */
    @Test fun derAnkerGreiftAuchWennDieNummerNeuIst() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))
        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))

        assertEquals("Immer noch ein Einsatz", 1, einsaetze().size)
        assertEquals("Aber zwei Phaseneinträge — die zweite Nummer war neu (E-R45-12)",
            2, puffer.phasen(einsaetze().single().id).size)
    }

    /** Auch ein **abgeschlossener** Einsatz wird über die Kennung wiedergefunden. */
    @Test fun einNachzueglerFindetDenAbgeschlossenenEinsatz() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))
        annahme.uebernimm(meldung(Ereignisart.EINSATZ_ABSCHLIESSEN, "2026-07-16T05:40:00Z"))
        assertEquals(1, einsaetze().size)

        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:30:00Z", phase = 9, einsatzRef = WM))

        assertEquals("Kein zweiter Einsatz", 1, einsaetze().size)
        assertEquals("Die nachgereichte Phase gehört zu ihm",
            listOf(2, 9), puffer.phasen(einsaetze().single().id).map { it.nummer })
    }

    // ---- Lücke in der Reihe --------------------------------------------------

    /**
     * **Die Quittung wandert nicht über eine Lücke.**
     *
     * Fehlt Nr. 2 und kommt Nr. 3, bleibt quittiert bei 1. Anders herum dürfte
     * die Uhr Nr. 2 löschen — und niemand sähe je, dass sie fehlt.
     */
    @Test fun eineLueckeHaeltDieQuittungAn() {
        assertEquals(1L, annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z", nummer = 1)).bisNr)
        assertEquals("Nr. 2 fehlt — es bleibt bei 1",
            1L, annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:20:00Z", phase = 3, nummer = 3)).bisNr)
        assertEquals(listOf(3L), puffer.uhrOffeneNummern(UHR))

        assertEquals("Mit Nr. 2 springt sie auf 3",
            3L, annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, nummer = 2)).bisNr)
        assertEquals("Und die Einzelbuchung ist abgeräumt", emptyList<Long>(), puffer.uhrOffeneNummern(UHR))
    }

    /**
     * **Eine zurückgesetzte Uhr fängt sauber an.**
     *
     * Ihr Zähler beginnt wieder bei 1. Ohne die Uhr-Kennung hielte das Handy
     * jedes ihrer Ereignisse für eine Doppelzustellung und verwürfe es
     * **stillschweigend**.
     */
    @Test fun eineNeueUhrFaengtSauberAn() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z", nummer = 1))
        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM, nummer = 2))
        assertEquals(1, puffer.phasen(einsaetze().single().id).size)

        annahme.uebernimm(
            meldung(Ereignisart.PHASE, "2026-07-16T05:20:00Z", phase = 3,
                uhrId = "u-9999999999", nummer = 1)
        )

        assertEquals("Die Phase der neuen Uhr zählt", 2, puffer.phasen(einsaetze().single().id).size)
        assertEquals(1L, puffer.uhrStand("u-9999999999"))
        assertEquals("Die alte Buchführung bleibt daneben stehen", 2L, puffer.uhrStand(UHR))
    }

    // ---- Phasenkonflikt Uhr/Handy -------------------------------------------

    /**
     * **Phasenkonflikt: beide Einträge werden gesendet** (E-R45-12).
     *
     * Handy und Uhr setzen dieselbe Phase, dreißig Sekunden auseinander. Es
     * entstehen **zwei** Einträge mit verschiedenen Quellen — und der
     * Datensatz ist danach **wieder sendepflichtig**, obwohl der Server ihn
     * schon einmal bestätigt hatte. Ohne diesen zweiten Teil stünde die
     * Korrektur zwar im Puffer, käme aber nie an (Fund B-S4-05).
     */
    @Test fun einPhasenkonfliktErzeugtZweiEintraegeUndBeideGehenRaus() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        uhrzeit = Instant.parse("2026-07-16T05:10:00Z")
        klammer.phaseSetzen(3)

        val einsatz = einsaetze().single()
        puffer.bestaetigungMerken(einsatz.id, 0)             // der Server hat ihn schon
        assertFalse("Vorher gibt es nichts zu tun", puffer.hatArbeit(puffer.paket(einsatz.id)!!))

        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:30Z", phase = 3, einsatzRef = WM))

        val phasen = puffer.phasen(einsatz.id)
        assertEquals("Zwei Einträge, nichts wird entdoppelt", 2, phasen.size)
        assertEquals(listOf(3, 3), phasen.map { it.nummer })
        assertEquals(
            listOf("2026-07-16T05:10:00Z", "2026-07-16T05:10:30Z"), phasen.map { it.at },
        )
        assertEquals("Und am Datensatz bleibt ablesbar, woher sie kommen",
            listOf(Dienstklammer.QUELLE_HANDY, Dienstklammer.QUELLE_UHR),
            puffer.phasenquellen(einsatz.id))
        assertTrue("Der Einsatz muss erneut gesendet werden",
            puffer.hatArbeit(puffer.paket(einsatz.id)!!))
    }

    /**
     * Die Koordinate kommt aus der **eigenen Spur** des Handys (E-S4-10) —
     * auch für ein Ereignis der Uhr, und auch dann, wenn der Punkt noch im
     * Ruhesegment liegt.
     */
    @Test fun dieKoordinateKommtAusDerSpurDesHandys() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        val alarm = Instant.parse("2026-07-16T05:10:00Z")
        klammer.positionsfund(
            Rohpunkt(breite = 48.1372, laenge = 11.5756, hoehe = 519.0,
                zeit = Zeit.epoche(alarm.minusSeconds(5)))
        )

        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))

        val phase = puffer.phasen(einsaetze().single().id).single()
        assertEquals(48.1372, phase.breite!!, 1e-6)
        assertEquals(11.5756, phase.laenge!!, 1e-6)
    }

    /** Liegt nichts in der Toleranz, bleibt die Koordinate leer — nichts wird erfunden. */
    @Test fun ohnePunktInDerNaeheBleibtDieKoordinateLeer() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        klammer.positionsfund(
            Rohpunkt(48.1372, 11.5756, 519.0, Zeit.epoche(Instant.parse("2026-07-16T05:00:10Z")))
        )

        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))

        assertNull(puffer.phasen(einsaetze().single().id).single().breite)
    }

    // ---- Dienstende ----------------------------------------------------------

    @Test fun dasDienstendeVonDerUhrSchliesstAlles() {
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEGINNEN, "2026-07-16T05:00:00Z"))
        annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2, einsatzRef = WM))
        annahme.uebernimm(meldung(Ereignisart.DIENST_BEENDEN, "2026-07-16T17:00:00Z"))

        assertFalse("Kein Dienst mehr", klammer.laeuft())
        assertNull("Und kein offenes Paket", puffer.offenesPaket(Paketzeile.ART_EINSATZ))
        assertNull(puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
        assertEquals("Die Zeit ist die der Uhr",
            "2026-07-16T17:00:00Z", puffer.paketNach(WM)!!.beendetAt)
    }

    /** Eine Phase ohne laufenden Dienst läuft ins Leere — gilt aber als erledigt. */
    @Test fun einEreignisInsLeereWirdTrotzdemQuittiert() {
        val q = annahme.uebernimm(meldung(Ereignisart.PHASE, "2026-07-16T05:10:00Z", phase = 2))
        assertEquals("Sonst lieferte die Uhr es für immer nach", 1L, q.bisNr)
        assertNull(puffer.offenesPaket(Paketzeile.ART_EINSATZ))
    }

    private fun einsaetze(): List<Paketzeile> =
        puffer.warteschlange().filter { it.art == Paketzeile.ART_EINSATZ }

    private companion object {
        const val DATEI = "pruef_uhrannahme.db"
        const val UHR = "u-1234512345"
        const val WM = "wm-1-1234512345"
    }
}
