package org.genem.nadoku.handy.kopplung

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.handy.tresor.PruefTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeTrue
import org.junit.After
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File

/**
 * **Server-Rundlauf der Kopplung** gegen eine echte Installation
 * (Abnahme B2).
 *
 * WAS HIER ANDERS IST ALS IN [KopplungTest]: Dort antwortet ein selbst
 * gebauter Prüfserver mit Antworten, die *nachgestellt* sind — die Fälle sind
 * dadurch vollständig, aber sie prüfen die Annahme mit, dass `pair.php`
 * tatsächlich so antwortet. Hier antwortet `pair.php` selbst. Beides wird
 * gebraucht: Der eine Lauf ist vollständig, der andere echt.
 *
 * VORBEREITUNG (steht im Prüfprotokoll, weil sie zum Ergebnis gehört):
 *
 * ```
 * sh tools/referenzdatensatz/einspielen/lokal_starten.sh
 * mariadb -e "DELETE FROM pair_codes; DELETE FROM devices;
 *   INSERT INTO pair_codes (user_id, code) VALUES (1,'AB3K7Q'), …" nadoku
 * ./gradlew :handy:testDebugUnitTest -Pnadoku.rundlauf=http://127.0.0.1:8080/
 * ```
 *
 * Die Kopplungscodes werden **von Hand in die Tabelle gelegt**, und das ist
 * bewusst so: Geprüft wird der Weg *App → `pair.php` → `devices`-Zeile*. Wie
 * ein Code entsteht, ist eine Frage der Weboberfläche und nicht Gegenstand
 * dieses Falls — er ist die Vorbedingung, nicht der Prüfling.
 *
 * ÜBER KLARTEXT-HTTP, nicht HTTPS: Die lokale Installation trägt ein
 * selbstsigniertes Zertifikat. Es in den Vertrauensspeicher des Prüflaufs zu
 * legen hieße, dem Prüfstand etwas beizubringen, was die App nie tun darf.
 * Dass die App **nur** HTTPS spricht (E-S4-14), ist in
 * [ServeradresseTest] belegt — dort, wo diese Regel wohnt.
 */
@RunWith(RobolectricTestRunner::class)
class KopplungRundlaufTest {

    private val basis: String = System.getProperty("nadoku.rundlauf").orEmpty()

    private lateinit var tresor: Schluesseltresor
    private lateinit var datei: File

    private val geraet = Geraeteangabe(
        art = Geraeteangabe.ART_HANDY, teil = null,
        hersteller = "samsung", modell = "SM-S921B",
        br = 1080, ho = 2340, touch = true,
        fw = "16", sdk = 36, app = "0.2.0",
    )

    private fun dienst(rueckstand: Int = 0) =
        Kopplungsdienst(HttpNetzweg(), tresor) { rueckstand }

    /**
     * JEDER FALL RÄUMT HINTER SICH AUF.
     *
     * Das ist hier keine Ordnungsliebe, sondern Voraussetzung: `MAX_GERAETE`
     * ist 5, und JUnit sichert keine Reihenfolge der Fälle zu. Ohne das
     * Aufräumen füllte der Grenzfall das Konto, und alles, was danach lief,
     * scheiterte an `device_limit` — an einem Zustand also, den ein anderer
     * Prüffall hinterlassen hat. Genau das ist beim ersten Lauf passiert.
     */
    @After fun aufraeumen() {
        if (basis.isEmpty()) return
        if (tresor.gekoppelt()) dienst().trennen(basis)
        datei.delete()
    }

    @Before fun aufbauen() {
        assumeTrue(
            "Kein Rundlauf: -Pnadoku.rundlauf=<Basis> nicht gesetzt",
            basis.isNotEmpty(),
        )
        val kontext = ApplicationProvider.getApplicationContext<Context>()
        datei = File(kontext.filesDir, "rundlauf.bin")
        datei.delete()
        tresor = Schluesseltresor(datei, PruefTresorschluessel())
    }

    /** Der Gutfall: echter Code, echte Zugangsdaten, echte `devices`-Zeile. */
    @Test fun kopplungGegenPairPhp() {
        val e = dienst().koppeln(basis, "AB3K7Q", geraet)
        assertEquals(Kopplungsergebnis.Gekoppelt, e)

        val z = tresor.lesen()
        assertNotNull(z)
        // Der Server vergibt seit Web 4.4.0 128 Bit: "dev-" + 32 Hexzeichen.
        assertTrue("Kennung: ${z!!.geraeteKennung}", z.geraeteKennung.startsWith("dev-"))
        assertEquals(36, z.geraeteKennung.length)
        assertTrue("Schlüssel zu kurz", z.schluessel.length >= 32)
    }

    /** Ein Code ist EINMAL einlösbar (Vertrag 1a). */
    @Test fun einCodeGiltNurEinmal() {
        assertEquals(Kopplungsergebnis.Gekoppelt, dienst().koppeln(basis, "CD4M8R", geraet))
        // Ordentlich trennen und nicht bloss die Ablage wegwerfen: Sonst
        // bliebe eine Geraetezeile stehen, zu der niemand mehr den Schluessel
        // hat -- sie belegte einen der fuenf Plaetze bis in alle Ewigkeit.
        assertEquals(Trennergebnis.Getrennt, dienst().trennen(basis))

        val zweiter = dienst().koppeln(basis, "CD4M8R", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.CODE_UNGUELTIG, zweiter.art)
        assertNull(tresor.lesen())
    }

    /** Ein Code, den es nie gab, ist von einem verbrauchten nicht zu unterscheiden. */
    @Test fun unbekannterCodeWirdAbgewiesen() {
        val e = dienst().koppeln(basis, "ZZZZZZ", geraet) as Kopplungsergebnis.Abgewiesen
        assertEquals(Abweisung.CODE_UNGUELTIG, e.art)
    }

    /** Trennen: der Server löscht das Gerät, die App ihre Ablage (Vertrag 1b). */
    @Test fun trennenGegenPairPhp() {
        assertEquals(Kopplungsergebnis.Gekoppelt, dienst().koppeln(basis, "EF5N9S", geraet))

        val e = dienst().trennen(basis)

        assertEquals(Trennergebnis.Getrennt, e)
        assertNull(tresor.lesen())
    }

    /** Nach dem Trennen sind die Zugangsdaten wertlos — auch am Server. */
    @Test fun getrennteZugangsdatenWerdenAbgewiesen() {
        assertEquals(Kopplungsergebnis.Gekoppelt, dienst().koppeln(basis, "GH6P2T", geraet))
        val zugang = tresor.lesen()!!
        assertEquals(Trennergebnis.Getrennt, dienst().trennen(basis))

        // Dieselben Zugangsdaten noch einmal vorlegen: Das Gerät ist fort.
        tresor.speichern(zugang)
        val e = dienst().trennen(basis)
        assertTrue("Erwartet: nur lokal getrennt, war $e", e is Trennergebnis.NurLokal)
        assertNull(tresor.lesen())
    }

    /**
     * `MAX_GERAETE` (5) greift, und die App sagt, was hilft.
     *
     * ACHT eigene Codes, nicht fünf: Die Reihenfolge der Prüffälle ist nicht
     * zugesichert, und die anderen Fälle lassen bis zu zwei Geräte stehen. Der
     * Fall koppelt deshalb, bis die Grenze meldet, statt genau sechsmal — und
     * er benutzt einen eigenen Codevorrat, damit er keinem anderen einen
     * wegnimmt.
     */
    @Test fun geraeteGrenzeGreiftUndIstErklaert() {
        val codes = listOf(
            "LA2B3C", "LD4E5F", "LG6H7J", "LK8L9M",
            "LN2P3Q", "LR4S5T", "LU6V7W", "LX8Y9Z",
        )
        var grenze: Kopplungsergebnis.Abgewiesen? = null
        val angelegt = mutableListOf<org.genem.nadoku.handy.tresor.Zugangsdaten>()

        for (c in codes) {
            if (grenze != null) break
            when (val e = dienst().koppeln(basis, c, geraet)) {
                is Kopplungsergebnis.Gekoppelt -> {
                    // Die Zugangsdaten MERKEN, nicht wegwerfen — nur mit ihnen
                    // lässt sich das Gerät hinterher wieder abmelden.
                    angelegt += tresor.lesen()!!
                    tresor.loeschen()
                }
                is Kopplungsergebnis.Abgewiesen ->
                    if (e.art == Abweisung.ZU_VIELE_GERAETE) grenze = e
            }
        }
        val gekoppelt = angelegt.size

        println("Rundlauf: $gekoppelt Geräte gekoppelt, Grenze gemeldet: ${grenze != null}")
        assertTrue("Es muss mindestens ein Gerät gekoppelt worden sein", gekoppelt >= 1)
        assertNotNull(
            "Nach MAX_GERAETE muss device_limit kommen — sonst greift die Grenze nicht",
            grenze,
        )
        // Die Servermeldung wird durchgereicht: Sie sagt, was zu tun ist.
        assertTrue(
            "Servermeldung fehlt: ${grenze!!.meldung}",
            grenze.meldung?.contains("Geraete") == true,
        )

        // Das Konto so hinterlassen, wie es vorgefunden wurde.
        for (z in angelegt) {
            tresor.speichern(z)
            dienst().trennen(basis)
        }
        assertEquals(
            "Nach dem Aufräumen darf keine Kopplung mehr stehen",
            null, tresor.lesen(),
        )
    }
}
