package org.genem.nadoku.uhr

import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Phasen
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Das Bedienmodell der Uhr (Abnahme C1, E-S4-21).
 *
 * **Reine JVM, keine Oberfläche.** Genau darum geht es: Die Uhr ist blind
 * gebaut — es gibt keinen Emulator (E-R45-8) und keine Uhr (E-R45-7). Was in
 * Composables verteilt wäre, bliebe ungeprüft. Hier ist das Bedienmodell eine
 * Funktion, und diese Fälle sind der einzige Beleg, den es vor dem Gerätetest
 * gibt.
 */
class UhrbedienungTest {

    private val bedienung = Uhrbedienung()
    private var zeit = 1_000L

    private fun tick(ms: Long = 1000): Long { zeit += ms; return zeit }

    private val imDienst = Uhrzustand(
        dienstLaeuft = true, ansicht = Ansicht.LAUFEND, letzteBedienungMs = 1_000L,
    )

    private fun Uhrzustand.los(vararg ereignisse: Uhrereignis): Uhrbedienung.Ergebnis {
        var e = Uhrbedienung.Ergebnis(this)
        for (er in ereignisse) e = bedienung.verarbeite(e.zustand, er)
        return e
    }

    // ---- (b) Der Durchlauf --------------------------------------------------

    /** **Durchlauf 2 → 9**, jeder Druck eine Phase, jede eine Wirkung. */
    @Test fun derDurchlaufGehtVonZweiBisNeun() {
        var z = imDienst
        val gesetzte = mutableListOf<Int>()

        for (erwartet in Phasen.ERSTE..Phasen.LETZTE) {
            assertEquals("Der Knopf trägt Phase $erwartet", erwartet, z.naechstePhase)
            val e = bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(tick()))
            z = e.zustand
            e.wirkungen.filterIsInstance<Uhrwirkung.PhaseSetzen>().forEach { gesetzte += it.phase }
        }

        assertEquals((2..9).toList(), gesetzte)
        assertEquals(9, z.laufendePhase)
        assertNull("Nach Phase 9 gibt es keine nächste", z.naechstePhase)
    }

    /** **Der Abschluss kommt nur nach Rückfrage** (E-S4-21b). */
    @Test fun derAbschlussKommtNurNachRueckfrage() {
        var z = imDienst.copy(einsatzLaeuft = true, laufendePhase = Phasen.LETZTE)

        // Erster Druck: Es entsteht die Frage, KEINE Wirkung.
        val frage = bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(tick()))
        assertEquals(Ansicht.ABSCHLUSSFRAGE, frage.zustand.ansicht)
        assertTrue(
            "Ein versehentlicher letzter Tipp darf nichts beenden",
            frage.wirkungen.isEmpty(),
        )

        // Erst die Bestätigung wirkt.
        val fertig = bedienung.verarbeite(frage.zustand, Uhrereignis.Bestaetigt(tick()))
        assertEquals(listOf(Uhrwirkung.EinsatzAbschliessen), fertig.wirkungen)
        assertFalse(fertig.zustand.einsatzLaeuft)
        assertEquals(Ansicht.LAUFEND, fertig.zustand.ansicht)

        z = frage.zustand
        // Und ein Verwerfen führt zurück, ohne zu wirken.
        val zurueck = bedienung.verarbeite(z, Uhrereignis.Verworfen(tick()))
        assertTrue(zurueck.wirkungen.isEmpty())
        assertEquals(Ansicht.LAUFEND, zurueck.zustand.ansicht)
        assertTrue("Der Einsatz läuft weiter", zurueck.zustand.einsatzLaeuft)
    }

    @Test fun ohneEinsatzTraegtDerKnopfPhaseZwei() {
        assertEquals(Phasen.ERSTE, imDienst.naechstePhase)
        val e = bedienung.verarbeite(imDienst, Uhrereignis.GrosserKnopf(tick()))
        assertEquals(listOf(Uhrwirkung.PhaseSetzen(2, eroeffnet = true)), e.wirkungen)
        assertTrue("Die Phase startet den Einsatz", e.zustand.einsatzLaeuft)
    }

    @Test fun ohneDienstTutDerGrosseKnopfNichts() {
        val e = bedienung.verarbeite(Uhrzustand(), Uhrereignis.GrosserKnopf(tick()))
        assertTrue(e.wirkungen.isEmpty())
    }

    // ---- (c) Die Phasenliste ------------------------------------------------

    @Test fun haltenOeffnetDiePhasenliste() {
        val z = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4)
        val e = bedienung.verarbeite(z, Uhrereignis.GrosserKnopfGehalten(tick()))
        assertEquals(Ansicht.PHASENLISTE, e.zustand.ansicht)
        assertTrue(e.wirkungen.isEmpty())
    }

    /** **Direktwahl setzt die gewählte Phase jetzt.** */
    @Test fun dieDirektwahlSetztDieGewaehltePhase() {
        val z = imDienst.copy(
            einsatzLaeuft = true, laufendePhase = 7, ansicht = Ansicht.PHASENLISTE,
        )
        val e = bedienung.verarbeite(z, Uhrereignis.ListenwahL(3, tick()))

        assertEquals(listOf(Uhrwirkung.PhaseSetzen(3, eroeffnet = false)), e.wirkungen)
        assertEquals("Die laufende Phase ist jetzt die gewählte", 3, e.zustand.laufendePhase)
        assertEquals("Und die Liste schließt sich", Ansicht.LAUFEND, e.zustand.ansicht)
    }

    /**
     * **Eine Korrektur wird ein zweiter Eintrag** (E-R45-12).
     *
     * Die Uhr meldet die Phase erneut; sie ersetzt nichts. Dass daraus am
     * Server zwei Einträge werden, prüft `MissionRundlaufTest` — hier zählt,
     * dass die Uhr die Wirkung überhaupt ein zweites Mal auslöst.
     */
    @Test fun eineKorrekturLoestDieWirkungErneutAus() {
        var z = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4)
        val wirkungen = mutableListOf<Uhrwirkung>()

        repeat(3) {
            val e = bedienung.verarbeite(z, Uhrereignis.ListenwahL(4, tick()))
            z = e.zustand
            wirkungen += e.wirkungen
        }

        assertEquals(
            "Dreimal gewählt heisst dreimal gemeldet — nichts wird entdoppelt",
            List(3) { Uhrwirkung.PhaseSetzen(4, eroeffnet = false) }, wirkungen,
        )
    }

    @Test fun eineUnmoeglichePhaseWirdAbgewiesen() {
        val z = imDienst.copy(ansicht = Ansicht.PHASENLISTE)
        for (n in listOf(0, 1, 10, 99)) {
            assertTrue("Phase $n", bedienung.verarbeite(z, Uhrereignis.ListenwahL(n, tick())).wirkungen.isEmpty())
        }
    }

    @Test fun derVorzeitigeAbschlussAusDerListeFragtEbenfallsNach() {
        val z = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4, ansicht = Ansicht.PHASENLISTE)
        val e = bedienung.verarbeite(z, Uhrereignis.Abschluss(tick()))
        assertEquals(Ansicht.ABSCHLUSSFRAGE, e.zustand.ansicht)
        assertTrue(e.wirkungen.isEmpty())
    }

    // ---- (d) Die Sperre -----------------------------------------------------

    /** **Die Sperre greift nach der Frist.** */
    @Test fun dieSperreGreiftNachDerFrist() {
        val z = imDienst.copy(letzteBedienungMs = 10_000)

        val kurzDavor = bedienung.verarbeite(z, Uhrereignis.Zeitschlag(10_000 + Uhrbedienung.SPERRFRIST_MS - 1))
        assertFalse("Eine Millisekunde zu früh", kurzDavor.zustand.gesperrt)

        val genau = bedienung.verarbeite(z, Uhrereignis.Zeitschlag(10_000 + Uhrbedienung.SPERRFRIST_MS))
        assertTrue("Nach 10 s ist gesperrt", genau.zustand.gesperrt)
    }

    /** **Gesperrtes Tippen tut nichts.** */
    @Test fun gesperrtesTippenTutNichts() {
        val z = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4, gesperrt = true)

        for (e in listOf(
            Uhrereignis.GrosserKnopf(tick()),
            Uhrereignis.FreieTaste(tick()),
            Uhrereignis.ListenwahL(6, tick()),
            Uhrereignis.Abschluss(tick()),
            Uhrereignis.Dienstknopf(tick()),
        )) {
            val ergebnis = bedienung.verarbeite(z, e)
            assertTrue("$e darf gesperrt nichts tun", ergebnis.wirkungen.isEmpty())
            assertEquals("Und nichts ändern", z, ergebnis.zustand)
        }
    }

    /** **Entsperrt wird nur durch Halten.** */
    @Test fun entsperrtWirdNurDurchHalten() {
        val z = imDienst.copy(gesperrt = true)

        val zuKurz = bedienung.verarbeite(
            z, Uhrereignis.Halten(Uhrbedienung.HALTEDAUER_MS - 1, tick()),
        )
        assertTrue("Ein zu kurzes Halten ist ein Tippen", zuKurz.zustand.gesperrt)

        val langGenug = bedienung.verarbeite(
            z, Uhrereignis.Halten(Uhrbedienung.HALTEDAUER_MS, tick()),
        )
        assertFalse("Halten entsperrt", langGenug.zustand.gesperrt)
        assertTrue("Und wirkt sonst nichts", langGenug.wirkungen.isEmpty())
    }

    /** Die Sperre gilt für Touch **und** die freie Taste gleichermaßen. */
    @Test fun dieSperreGiltAuchFuerDieFreieTaste() {
        val z = imDienst.copy(gesperrt = true)
        assertTrue(bedienung.verarbeite(z, Uhrereignis.FreieTaste(tick())).wirkungen.isEmpty())
    }

    /** Abschaltbar (E-S4-21d): dann sperrt nichts. */
    @Test fun dieSperreLaesstSichAbschalten() {
        val ohne = Uhrbedienung(sperreAn = false)
        val z = imDienst.copy(letzteBedienungMs = 0)
        assertFalse(ohne.verarbeite(z, Uhrereignis.Zeitschlag(999_999)).zustand.gesperrt)
    }

    /** Vor dem Dienst gibt es nichts zu verstellen — die Startseite sperrt nicht. */
    @Test fun vorDemDienstSperrtNichts() {
        val z = Uhrzustand(letzteBedienungMs = 0)
        assertFalse(bedienung.verarbeite(z, Uhrereignis.Zeitschlag(999_999)).zustand.gesperrt)
    }

    /** Jede Bedienung frischt die Frist auf. */
    @Test fun jedeBedienungFrischtDieFristAuf() {
        val z = imDienst.copy(letzteBedienungMs = 1_000)
        val nachher = bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(9_000)).zustand
        assertEquals(9_000L, nachher.letzteBedienungMs)
        assertFalse(bedienung.verarbeite(nachher, Uhrereignis.Zeitschlag(18_000)).zustand.gesperrt)
    }

    // ---- (a) Die freie Taste ------------------------------------------------

    /** **Mit gemeldeter Taste löst sie „nächste Phase" aus** (E-S4-21a). */
    @Test fun dieFreieTasteTutDasselbeWieDerGrosseKnopf() {
        val z = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4)

        val ueberTaste = bedienung.verarbeite(z, Uhrereignis.FreieTaste(tick()))
        val ueberKnopf = bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(tick()))

        assertEquals(listOf(Uhrwirkung.PhaseSetzen(5, eroeffnet = false)), ueberTaste.wirkungen)
        assertEquals(ueberKnopf.wirkungen, ueberTaste.wirkungen)
        assertEquals(ueberKnopf.zustand.laufendePhase, ueberTaste.zustand.laufendePhase)
    }

    /** **Ohne Taste bleibt alles per Touch bedienbar** (E-S4-21a). */
    @Test fun ohneTasteBleibtAllesPerTouchBedienbar() {
        // Es gibt keinen Zustand und kein Ereignis, das eine Taste voraussetzt:
        // Der ganze Durchlauf, die Liste, der Abschluss und das Dienstende
        // laufen über GrosserKnopf, ListenwahL, Abschluss und Dienstknopf.
        var z = imDienst
        z = bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(tick())).zustand      // Phase 2
        z = bedienung.verarbeite(z, Uhrereignis.GrosserKnopfGehalten(tick())).zustand
        assertEquals(Ansicht.PHASENLISTE, z.ansicht)
        z = bedienung.verarbeite(z, Uhrereignis.ListenwahL(5, tick())).zustand
        assertEquals(5, z.laufendePhase)
        z = bedienung.verarbeite(z, Uhrereignis.Abschluss(tick())).zustand
        val fertig = bedienung.verarbeite(z, Uhrereignis.Bestaetigt(tick()))
        assertEquals(listOf(Uhrwirkung.EinsatzAbschliessen), fertig.wirkungen)
    }

    // ---- Der Dienst und der Nur-Aufzeichnen-Modus ---------------------------

    @Test fun derDienstBeginntOhneRueckfrageUndEndetMitEiner() {
        val start = bedienung.verarbeite(Uhrzustand(), Uhrereignis.Dienstknopf(tick()))
        assertEquals(listOf(Uhrwirkung.DienstBeginnen), start.wirkungen)
        assertTrue(start.zustand.dienstLaeuft)

        val frage = bedienung.verarbeite(start.zustand, Uhrereignis.Dienstknopf(tick()))
        assertEquals(Ansicht.DIENSTENDEFRAGE, frage.zustand.ansicht)
        assertTrue("Beenden ist eine beendende Handlung — erst die Rückfrage",
            frage.wirkungen.isEmpty())

        val ende = bedienung.verarbeite(frage.zustand, Uhrereignis.Bestaetigt(tick()))
        assertEquals(listOf(Uhrwirkung.DienstBeenden), ende.wirkungen)
        assertFalse(ende.zustand.dienstLaeuft)
        assertEquals(Ansicht.START, ende.zustand.ansicht)
    }

    /**
     * **Im Nur-Aufzeichnen-Modus zeigt die Uhr nur Dienst beginnen/beenden**
     * (E-S4-20) — und kein Phasendruck bewirkt etwas.
     */
    @Test fun imNurAufzeichnenModusGibtEsKeinePhasen() {
        val z = imDienst.copy(modus = Modus.NUR_AUFZEICHNEN)
        assertFalse(z.mitPhasen)

        assertTrue(bedienung.verarbeite(z, Uhrereignis.GrosserKnopf(tick())).wirkungen.isEmpty())
        assertTrue(bedienung.verarbeite(z, Uhrereignis.FreieTaste(tick())).wirkungen.isEmpty())
        assertEquals(
            "Auch die Phasenliste öffnet nicht",
            Ansicht.LAUFEND,
            bedienung.verarbeite(z, Uhrereignis.GrosserKnopfGehalten(tick())).zustand.ansicht,
        )

        // Dienst beenden geht weiterhin.
        val frage = bedienung.verarbeite(z, Uhrereignis.Dienstknopf(tick()))
        assertEquals(Ansicht.DIENSTENDEFRAGE, frage.zustand.ansicht)
    }

    @Test fun derModuswechselWaehrendDesDienstesBlendetNurAus() {
        val mit = imDienst.copy(einsatzLaeuft = true, laufendePhase = 4)
        val ohne = mit.copy(modus = Modus.NUR_AUFZEICHNEN)

        assertTrue(bedienung.verarbeite(mit, Uhrereignis.GrosserKnopf(tick())).wirkungen.isNotEmpty())
        assertTrue(bedienung.verarbeite(ohne, Uhrereignis.GrosserKnopf(tick())).wirkungen.isEmpty())
        assertEquals(
            "Was gesetzt war, bleibt sichtbar",
            4, ohne.laufendePhase,
        )
    }
}
