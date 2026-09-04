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
 * (Abnahme B2, neu geschnitten für Vertrag 1a).
 *
 * WAS HIER ANDERS IST ALS IN [KopplungTest]: Dort antwortet ein selbst
 * gebauter Prüfserver mit Antworten, die *nachgestellt* sind — die Fälle sind
 * dadurch vollständig, aber sie prüfen die Annahme mit, dass `pair.php`
 * tatsächlich so antwortet. Hier antwortet `pair.php` selbst. Beides wird
 * gebraucht: Der eine Lauf ist vollständig, der andere echt.
 *
 * DIESE KLASSE LIEF SEIT WEB 13.0.0 INS LEERE, und das war bekannt: Sie
 * koppelte über `Kopplungsdienst.koppeln(basis, code, geraet)` — den alten
 * Weg, den `pair.php` seither mit `400 {"error":"aktion"}` beantwortet.
 * `android/LIESMICH.md` beschrieb deshalb eine eigene Installation „vom Stand
 * vor S5". Das ist mit diesem Paket erledigt: Der Rundlauf läuft wieder gegen
 * den **aktuellen** Stand, und jener Abschnitt der Anleitung ist entfallen.
 *
 * VORBEREITUNG (steht im Prüfprotokoll, weil sie zum Ergebnis gehört):
 *
 * ```
 * sh tools/referenzdatensatz/einspielen/lokal_starten.sh
 * ./gradlew :handy:testDebugUnitTest -Pnadoku.rundlauf=http://127.0.0.1:8080/
 * ```
 *
 * **Codes werden nicht mehr vorbereitet** — es gibt keine Tabelle mehr, in
 * die man sie legen könnte. Den Schritt, den sonst ein Mensch im Browser tut,
 * übernimmt [Kopplungshilfe]; ihr Dateikopf begründet, warum sie dafür
 * `mariadb` aufruft.
 *
 * ÜBER KLARTEXT-HTTP, nicht HTTPS: Die lokale Installation trägt ein
 * selbstsigniertes Zertifikat. Es in den Vertrauensspeicher des Prüflaufs zu
 * legen hieße, dem Prüfstand etwas beizubringen, was die App nie tun darf.
 * Dass die App für jeden echten Rechnernamen **nur** HTTPS spricht (E-S4-14),
 * ist in [ServeradresseTest] belegt — dort, wo diese Regel wohnt.
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
        Kopplungsdienst(HttpNetzweg(), tresor, basis) { rueckstand }

    /**
     * JEDER FALL RÄUMT HINTER SICH AUF.
     *
     * Das ist hier keine Ordnungsliebe, sondern Voraussetzung: `MAX_GERAETE`
     * ist 5, und JUnit sichert keine Reihenfolge der Fälle zu. Ohne das
     * Aufräumen füllte der Grenzfall das Konto, und alles, was danach lief,
     * scheiterte an `device_limit` — an einem Zustand also, den ein anderer
     * Prüffall hinterlassen hat. Genau das ist beim ersten Lauf passiert.
     *
     * SEIT DER UMKEHR WIRD AUCH DIE SITZUNGSTABELLE GERÄUMT: Ein Fall, der
     * mitten im Ablauf endet, lässt eine offene Sitzung zurück, und die zählt
     * zehn Minuten lang gegen die Obergrenze des Servers (Backlog Nr. 95).
     */
    @After fun aufraeumen() {
        if (basis.isEmpty()) return
        if (tresor.gekoppelt()) dienst().trennen()
        datei.delete()
        Kopplungshilfe.aufraeumen()
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
        Kopplungshilfe.aufraeumen()
    }

    /** Der Gutfall: echte Sitzung, echtes Ja, echte `devices`-Zeile. */
    @Test fun kopplungGegenPairPhp() {
        assertNull(Kopplungshilfe.koppeln(dienst(), geraet))

        val z = tresor.lesen()
        assertNotNull(z)
        // Der Server vergibt seit Web 4.4.0 128 Bit: "dev-" + 32 Hexzeichen.
        assertTrue("Kennung: ${z!!.geraeteKennung}", z.geraeteKennung.startsWith("dev-"))
        assertEquals(36, z.geraeteKennung.length)
        assertTrue("Schlüssel zu kurz", z.schluessel.length >= 32)
        assertTrue("Nach dem Ja darf nichts mehr schweben", tresor.gekoppelt())
    }

    /**
     * `start` LIEFERT EINEN CODE AUS DEM VEREINBARTEN ALPHABET — und
     * Zugangsdaten, die bis zum Ja **schweben**.
     */
    @Test fun startLiefertCodeUndSchwebendeZugangsdaten() {
        val e = dienst().starten(geraet)
        assertTrue("start ergab $e", e is Sitzungsergebnis.Offen)

        val offen = e as Sitzungsergebnis.Offen
        assertTrue("Code passt nicht: ${offen.code}", Kopplungscode.gueltig(offen.code))
        assertTrue("Frist unplausibel: ${offen.fristSekunden}", offen.fristSekunden in 1..3600)

        assertNotNull(tresor.lesen())
        assertTrue("Die Daten müssten schweben", tresor.schwebend())
        assertTrue("gekoppelt() darf noch nicht wahr sein", !tresor.gekoppelt())
    }

    /**
     * DER ZUSTAND WECHSELT ERST, WENN EIN KONTO DEN CODE HAT. Vorher `offen`,
     * danach `beansprucht` — mit der maskierten Adresse, an der das zweite Tor
     * hängt (Vertrag 1a.2).
     */
    @Test fun statusWechseltVonOffenAufBeansprucht() {
        val offen = dienst().starten(geraet) as Sitzungsergebnis.Offen

        val vorher = dienst().nachfragen()
        assertTrue("Vor der Zuordnung: $vorher", vorher is Sitzungsstand.Offen)

        assertNull(Kopplungshilfe.codeZuordnen(offen.code))

        val nachher = dienst().nachfragen()
        assertTrue("Nach der Zuordnung: $nachher", nachher is Sitzungsstand.Beansprucht)
        val konto = (nachher as Sitzungsstand.Beansprucht).konto
        assertTrue("Adresse nicht maskiert: $konto", konto.contains("***"))
        assertTrue("Adresse ohne Domain: $konto", konto.contains("@"))
    }

    /**
     * `ja` VOR DER BEANSPRUCHUNG WIRD ABGEWIESEN, und die Sitzung bleibt
     * stehen (Vertrag 1a.3, `409 nicht_beansprucht`).
     */
    @Test fun jaVorDerBeanspruchungWirdAbgewiesen() {
        dienst().starten(geraet)

        val e = dienst().bestaetigen(ja = true)
        assertTrue("Erwartet: Abweisung, war $e", e is Bestaetigungsergebnis.Abgewiesen)
        assertNotNull("Die Sitzung muss bestehen bleiben", tresor.lesen())
    }

    /** Ein Nein löscht die Sitzung — danach ist die Kennung unbekannt. */
    @Test fun neinLoeschtDieSitzungAufBeidenSeiten() {
        val offen = dienst().starten(geraet) as Sitzungsergebnis.Offen
        assertNull(Kopplungshilfe.codeZuordnen(offen.code))
        val zugang = tresor.lesen()!!

        assertTrue(dienst().bestaetigen(ja = false) is Bestaetigungsergebnis.Abgebrochen)
        assertNull("Die Ablage muss leer sein", tresor.lesen())

        // Dieselben Zugangsdaten noch einmal vorlegen: Die Sitzung ist fort,
        // und der Server unterscheidet sie nicht von einer, die es nie gab
        // (Vertrag 1a.2: eine verworfene Sitzung ergibt 401, nicht 410).
        tresor.speichernSchwebend(zugang)
        val s = dienst().nachfragen()
        assertTrue("Erwartet: Abweisung, war $s", s is Sitzungsstand.Abgewiesen)
        assertEquals(
            Abweisung.SITZUNG_UNGUELTIG,
            (s as Sitzungsstand.Abgewiesen).art,
        )
    }

    /** Trennen: der Server löscht das Gerät, die App ihre Ablage (Vertrag 1b). */
    @Test fun trennenGegenPairPhp() {
        assertNull(Kopplungshilfe.koppeln(dienst(), geraet))

        assertEquals(Trennergebnis.Getrennt, dienst().trennen())
        assertNull(tresor.lesen())
    }

    /** Nach dem Trennen sind die Zugangsdaten wertlos — auch am Server. */
    @Test fun getrennteZugangsdatenWerdenAbgewiesen() {
        assertNull(Kopplungshilfe.koppeln(dienst(), geraet))
        val zugang = tresor.lesen()!!
        assertEquals(Trennergebnis.Getrennt, dienst().trennen())

        // Dieselben Zugangsdaten noch einmal vorlegen: Das Gerät ist fort.
        tresor.speichern(zugang)
        val e = dienst().trennen()
        assertTrue("Erwartet: nur lokal getrennt, war $e", e is Trennergebnis.NurLokal)
        assertNull(tresor.lesen())
    }

    /**
     * `MAX_GERAETE` (5) greift, und die App sagt, was hilft.
     *
     * ACHT DURCHGÄNGE, nicht fünf: Der Fall koppelt, bis die Grenze meldet,
     * statt genau sechsmal — so trägt er auch dann, wenn ein anderer Fall ein
     * Gerät hinterlassen hat. Codes muss er sich keine mehr aussuchen; jede
     * Runde holt ihren eigenen.
     */
    @Test fun geraeteGrenzeGreiftUndIstErklaert() {
        var grenze: Bestaetigungsergebnis.Abgewiesen? = null
        val angelegt = mutableListOf<org.genem.nadoku.handy.tresor.Zugangsdaten>()

        for (runde in 1..8) {
            if (grenze != null) break

            val start = dienst().starten(geraet)
            if (start !is Sitzungsergebnis.Offen) break
            assertNull(Kopplungshilfe.codeZuordnen(start.code))

            when (val e = dienst().bestaetigen(ja = true)) {
                is Bestaetigungsergebnis.Gekoppelt -> {
                    // Die Zugangsdaten MERKEN, nicht wegwerfen — nur mit ihnen
                    // lässt sich das Gerät hinterher wieder abmelden.
                    angelegt += tresor.lesen()!!
                    tresor.loeschen()
                }
                is Bestaetigungsergebnis.Abgewiesen ->
                    if (e.art == Abweisung.ZU_VIELE_GERAETE) grenze = e
                else -> Unit
            }
        }
        val gekoppelt = angelegt.size

        println("Rundlauf: $gekoppelt Geräte gekoppelt, Grenze gemeldet: ${grenze != null}")
        assertTrue("Es muss mindestens ein Gerät gekoppelt worden sein", gekoppelt >= 1)
        assertNotNull(
            "Nach MAX_GERAETE muss device_limit kommen — sonst greift die Grenze nicht",
            grenze,
        )
        /* Die Servermeldung wird durchgereicht: Sie sagt, was zu tun ist.
         *
         * „Geräte" MIT UMLAUT, und das ist ein Fund dieses Pakets: Bis
         * Android 0.10.1 suchte diese Zeile „Geraete". Sie ging durch,
         * solange `pair.php` seine Meldungen ohne Umlaute schrieb — seit S5
         * (Web 13.0.0) tut es das nicht mehr. Der Prüffall hätte den Wechsel
         * bemerken müssen und hat es nicht, weil er gegen einen Server vom
         * alten Stand lief (siehe Klassenkopf). */
        assertTrue(
            "Servermeldung fehlt: ${grenze!!.meldung}",
            grenze.meldung?.contains("Geräte") == true,
        )

        // Das Konto so hinterlassen, wie es vorgefunden wurde.
        for (z in angelegt) {
            tresor.speichern(z)
            dienst().trennen()
        }
        assertEquals(
            "Nach dem Aufräumen darf keine Kopplung mehr stehen",
            null, tresor.lesen(),
        )
    }
}
