package org.genem.nadoku.handy.tresor

import org.genem.nadoku.handy.kopplung.Kopplungswert
import org.genem.nadoku.handy.senden.Geraetehuelle
import org.genem.nadoku.handy.senden.Huellenoeffner
import org.json.JSONObject
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Rule
import org.junit.Test
import org.junit.rules.TemporaryFolder
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import java.io.File
import java.security.KeyPair
import java.security.interfaces.ECPublicKey
import javax.crypto.KeyAgreement

/**
 * Der Geräteschlüssel und die Ablage der Kopplung.
 *
 * Anlass: Nr. 563 — der Vergleichswert schützt nur, wenn das Gerät `N_g`
 * erst nach dem Festlegen herausgibt und danach bei seinem Paar bleibt; und
 * eine abgebrochene Kopplung darf das Paar nicht kosten, unter dem der
 * Rückstand noch hinausgeht. Seit KA-06f liegt der Schlüssel im Keystore und
 * die Verwaltung daneben: Die Übergänge laufen über zwei Ablagen, und jeder
 * muss einen Prozesstod an jeder Stelle vertragen.
 *
 * WAS HIER GEMESSEN WIRD: die Regeln ([AbgelegterGeraeteschluessel]) und die
 * Verwaltungsdatei ([DateiGeraeteschluessel]) — dieselben Klassen wie auf dem
 * Gerät —, über einer Schlüsselablage im Speicher
 * ([SpeicherSchluesselablage]).
 *
 * NICHT GEPRÜFT: [KeystoreSchluesselablage] — dass der Schlüssel im
 * `AndroidKeyStore` entsteht, ihn nicht verlässt und dort rechnet — und dass
 * der Schlüssel der Datei von dort kommt ([PruefTresorschluessel]). Beides
 * steht im Gerätetest (`GeraeteschluesselGeraetTest`, `GeraetTresorTest`).
 */
@RunWith(RobolectricTestRunner::class)
class GeraeteschluesselTest {

    @get:Rule val ordner = TemporaryFolder()

    private val konto: KeyPair = Geraetehuelle.neuesPaar()
    private val kontoSec1: ByteArray get() = Geraetehuelle.sec1(konto.public as ECPublicKey)
    private val nB = ByteArray(16) { (it + 1).toByte() }

    private fun offen(f: Festlegung): Festlegung.Offen = f as Festlegung.Offen
    private fun grund(f: Festlegung): Festlegung.Grund = (f as Festlegung.Abbruch).grund

    /** Die Namen, die in der Verwaltung stehen: gültig und Versuch (oder `null`). */
    private fun namen(inhalt: ByteArray?): Pair<String?, String?> {
        if (inhalt == null) return null to null
        val o = JSONObject(String(inhalt, Charsets.UTF_8))
        return o.optJSONObject("g")?.getString("a") to o.optJSONObject("v")?.getString("a")
    }

    /** Was der Absender vereinbart, ist das Geheimnis, das auch die Gegenseite errechnet. */
    @Test fun absenderVereinbartWieDieGegenseite() {
        val g = SoftwareAbsender.erzeugen()
        assertEquals(65, g.spk.size)
        assertEquals(8, g.sid().size)
        val hin = g.vereinbaren(konto.public as ECPublicKey)
        val her = KeyAgreement.getInstance("ECDH").run {
            init(konto.private); doPhase(Geraetehuelle.oeffentlich(g.spk), true); generateSecret()
        }
        assertEquals(32, hin.size)
        assertArrayEquals(hin, her)
        assertFalse(g.spk.contentEquals(SoftwareAbsender.erzeugen().spk))
        // Ein öffentlicher Teil neben der Kurve wird nicht angenommen.
        assertTrue(runCatching { SoftwareAbsender(g.privat, g.spk.copyOf().also { it[64] = (it[64] + 1).toByte() }) }.isFailure)
    }

    /** Der ganze Weg einer Kopplung: beginnen, festlegen, übernehmen. */
    @Test fun kopplungGanz() {
        val a = SpeicherGeraeteschluessel()
        assertNull(a.liefern())
        assertNull(a.spk())
        assertNull(a.schwebend())

        val beginn = a.beginnen()
        assertEquals(65, beginn.spk.size)
        assertEquals(32, beginn.zusage.size)
        assertNull("vor dem Abschluss gibt es kein gültiges Paar", a.liefern())
        assertFalse("ohne Festlegung wird nichts gültig", a.uebernehmen())
        assertNull("und es ist auch nichts gültig geworden", a.liefern())

        val f = offen(a.festlegen(kontoSec1, nB))
        assertArrayEquals("die Zusage passt zu dem, was offengelegt wird", beginn.zusage, Kopplungswert.zusage(f.nG, beginn.spk))
        assertEquals(Kopplungswert.wert(kontoSec1, beginn.spk, f.nG, nB), f.wert)

        assertTrue(a.uebernehmen())
        assertArrayEquals(beginn.spk, a.spk())
        assertNull("der Versuch ist aufgegangen", a.schwebend())
        assertFalse("ein zweites Mal gibt es nichts zu übernehmen", a.uebernehmen())
        assertEquals("genau ein Schlüssel liegt in der Ablage", 1, a.ablage.namen().size)
    }

    /** Die erste Antwort gilt: dieselbe noch einmal ist dieselbe, eine andere ist ein Abbruch. */
    @Test fun ersteAntwortGilt() {
        val a = SpeicherGeraeteschluessel()
        assertEquals(Festlegung.Grund.KEIN_VERSUCH, grund(a.festlegen(kontoSec1, nB)))
        a.beginnen()
        val erst = offen(a.festlegen(kontoSec1, nB))
        val wieder = offen(a.festlegen(kontoSec1, nB))
        assertArrayEquals(erst.nG, wieder.nG)
        assertEquals(erst.wert, wieder.wert)

        val anderesKonto = Geraetehuelle.sec1(Geraetehuelle.neuesPaar().public as ECPublicKey)
        assertEquals(Festlegung.Grund.ANDERES_PAAR, grund(a.festlegen(anderesKonto, nB)))
        assertEquals(Festlegung.Grund.ANDERES_PAAR, grund(a.festlegen(kontoSec1, nB.copyOf().also { it[0] = 99 })))
        // Der Abbruch hat die Festlegung nicht umgeschrieben.
        assertEquals(erst.wert, offen(a.festlegen(kontoSec1, nB)).wert)
    }

    /** Falsche Form legt nichts fest — danach gilt immer noch die erste richtige Antwort. */
    @Test fun falscheFormLegtNichtsFest() {
        val a = SpeicherGeraeteschluessel()
        a.beginnen()
        assertEquals(Festlegung.Grund.FORM, grund(a.festlegen(kontoSec1, ByteArray(15))))
        val nebenDerKurve = kontoSec1.also { it[64] = (it[64] + 1).toByte() }
        assertEquals(Festlegung.Grund.FORM, grund(a.festlegen(nebenDerKurve, nB)))
        assertEquals(Festlegung.Grund.FORM, grund(a.festlegen(ByteArray(65), nB)))
        assertFalse("eine falsche Form ist keine Festlegung", a.uebernehmen())
        assertNotNull(offen(a.festlegen(kontoSec1, nB)))
    }

    /** Eine abgebrochene Kopplung lässt das gültige Paar stehen; eine neue ersetzt es erst beim Übernehmen. */
    @Test fun abbruchLaesstDasGueltigePaar() {
        val a = SpeicherGeraeteschluessel()
        val erste = a.beginnen()
        a.festlegen(kontoSec1, nB)
        a.uebernehmen()

        val zweite = a.beginnen()
        assertFalse(erste.spk.contentEquals(zweite.spk))
        assertArrayEquals("noch gilt das alte", erste.spk, a.spk())
        assertEquals("zwei Schlüssel nebeneinander: gültig und Versuch", 2, a.ablage.namen().size)
        a.festlegen(kontoSec1, nB)
        a.verwerfen()
        assertArrayEquals(erste.spk, a.spk())
        assertNull(a.schwebend())
        assertEquals("der Schlüssel des Versuchs ist gelöscht", setOf(namen(a.inhalt).first), a.ablage.namen())
        assertEquals(Festlegung.Grund.KEIN_VERSUCH, grund(a.festlegen(kontoSec1, nB)))

        val dritte = a.beginnen()
        a.festlegen(kontoSec1, nB)
        assertTrue(a.uebernehmen())
        assertArrayEquals(dritte.spk, a.spk())
        assertEquals("der abgelöste Schlüssel ist gelöscht", setOf(namen(a.inhalt).first), a.ablage.namen())
    }

    /** Ein neues `beginnen()` verwirft den alten Versuch samt seiner Festlegung — und seinem Schlüssel. */
    @Test fun neuerVersuchIstFrisch() {
        val a = SpeicherGeraeteschluessel()
        val eins = a.beginnen()
        val f1 = offen(a.festlegen(kontoSec1, nB))
        val zwei = a.beginnen()
        assertFalse(eins.spk.contentEquals(zwei.spk))
        assertFalse(eins.zusage.contentEquals(zwei.zusage))
        assertFalse("ohne neue Festlegung wird nichts gültig", a.uebernehmen())
        assertEquals("der Schlüssel des älteren Versuchs ist gelöscht", setOf(namen(a.inhalt).second), a.ablage.namen())
        val anderesNb = ByteArray(16) { 7 }
        val f2 = offen(a.festlegen(kontoSec1, anderesNb))
        assertFalse(f1.nG.contentEquals(f2.nG))
    }

    /** Trennen vergisst beides — die Verwaltung und JEDEN Schlüssel der Ablage, auch einen Waisen. */
    @Test fun vergessen() {
        val a = SpeicherGeraeteschluessel()
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        a.beginnen()
        a.ablage.erzeugen("0123456789abcdef")      // ein Waise, von dem die Verwaltung nichts weiß
        assertEquals(3, a.ablage.namen().size)
        a.vergessen()
        assertNull(a.liefern())
        assertNull(a.schwebend())
        assertNull(a.inhalt)
        assertTrue("kein Schlüssel bleibt zurück", a.ablage.namen().isEmpty())
    }

    /**
     * Gültig ist nur, was Verwaltung UND Schlüsselablage gleich nennen: Fehlt
     * der Schlüssel, oder liegt unter dem Namen ein anderer, gibt es keinen
     * Absender — und ein solcher Versuch wird nicht gültig.
     */
    @Test fun verwaltungUndSchluesselMuessenUebereinstimmen() {
        val a = SpeicherGeraeteschluessel()
        val erste = a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        val gueltig = namen(a.inhalt).first!!

        // Ein Versuch, dessen Schlüssel vertauscht wurde, wird nicht gültig; das bisherige Paar bleibt.
        a.beginnen(); a.festlegen(kontoSec1, nB)
        val versuch = namen(a.inhalt).second!!
        a.ablage.einlegen(versuch, SoftwareAbsender.erzeugen())
        assertFalse("anderer Schlüssel unter dem Namen des Versuchs", a.uebernehmen())
        assertArrayEquals(erste.spk, a.spk())
        // … und einer, dessen Schlüssel fehlt, auch nicht.
        a.ablage.loeschen(versuch)
        assertFalse("kein Schlüssel unter dem Namen des Versuchs", a.uebernehmen())
        assertArrayEquals(erste.spk, a.spk())

        // Das gültige Paar: vertauscht oder fort heißt „keines" — nie ein fremder Schlüssel.
        a.ablage.einlegen(gueltig, SoftwareAbsender.erzeugen())
        assertNull("anderer Schlüssel unter dem gültigen Namen", a.liefern())
        a.ablage.loeschen(gueltig)
        assertNull(a.liefern())
        assertNull(a.spk())
    }

    /**
     * Der Prozesstod an JEDEM schreibenden Schritt jedes Übergangs. Danach
     * gilt, je Übergang: Das gültige Paar ist das bisherige oder — nur beim
     * Übernehmen — das festgelegte neue; nie keines, nie ein nicht
     * festgelegtes. Und der nächste Anlauf räumt auf, was liegen blieb.
     */
    @Test fun prozesstodAnJedemSchritt() {
        class Lage(val a: SpeicherGeraeteschluessel, val alt: ByteArray, val versuch: ByteArray)

        /** Ein gültiges Paar, daneben ein festgelegter Versuch. */
        fun lage(): Lage {
            val a = SpeicherGeraeteschluessel()
            val alt = a.beginnen().spk
            a.festlegen(kontoSec1, nB); check(a.uebernehmen())
            val versuch = a.beginnen().spk
            a.festlegen(kontoSec1, nB)
            return Lage(a, alt, versuch)
        }

        /** Stirbt vor dem `n`-ten Schritt von `tun`; ergibt die Lage und den neuen Prozess — oder `null`, wenn `tun` vorher fertig war. */
        fun stirbt(n: Int, tun: (SpeicherGeraeteschluessel) -> Unit): Pair<Lage, SpeicherGeraeteschluessel>? {
            val l = lage()
            l.a.ablage.draht.scharf(n)
            val gestorben = try { tun(l.a); false } catch (e: Prozesstod) { true }
            l.a.ablage.draht.aus()
            return if (gestorben) l to SpeicherGeraeteschluessel(l.a.inhalt, l.a.ablage) else null
        }

        fun rechnet(g: SpeicherGeraeteschluessel) = assertEquals(32, g.liefern()!!.vereinbaren(konto.public as ECPublicKey).size)

        fun aufgeraeumt(g: SpeicherGeraeteschluessel) {
            g.beginnen()
            val (gueltig, versuch) = namen(g.inhalt)
            assertEquals("nach dem nächsten Anlauf liegt kein Waise mehr", setOfNotNull(gueltig, versuch), g.ablage.namen())
        }

        var tode = 0

        // --- übernehmen: Verwaltung schreiben · bisherigen Schlüssel löschen
        var n = 1
        while (true) {
            val (l, neu) = stirbt(n) { it.uebernehmen() } ?: break
            val spk = neu.spk()
            assertNotNull("übernehmen, Tod vor Schritt $n: ein gültiges Paar bleibt", spk)
            rechnet(neu)
            if (spk!!.contentEquals(l.alt)) {
                assertArrayEquals("der Versuch steht noch", l.versuch, neu.schwebend()!!.spk)
                assertEquals("und ist noch festgelegt", Festlegung.Grund.ANDERES_PAAR, grund(neu.festlegen(kontoSec1, ByteArray(16))))
                assertTrue("der zweite Anlauf übernimmt", neu.uebernehmen())
            } else {
                assertNull("der Versuch ist aufgegangen", neu.schwebend())
            }
            assertArrayEquals(l.versuch, neu.spk())
            rechnet(neu)
            aufgeraeumt(neu)
            tode++; n++
        }
        assertEquals("übernehmen hat zwei schreibende Schritte", 3, n)

        // --- beginnen: Schlüssel erzeugen · Verwaltung schreiben · älteren Versuch löschen
        n = 1
        while (true) {
            val (l, neu) = stirbt(n) { it.beginnen() } ?: break
            assertArrayEquals("beginnen, Tod vor Schritt $n: das gültige Paar bleibt", l.alt, neu.spk())
            rechnet(neu)
            val schwebt = neu.schwebend()!!.spk
            if (schwebt.contentEquals(l.versuch)) {
                assertEquals("der ältere Versuch ist noch festgelegt", Festlegung.Grund.ANDERES_PAAR,
                    grund(neu.festlegen(kontoSec1, ByteArray(16))))
            } else {
                assertFalse("ein frischer Versuch ist nicht festgelegt und wird nicht gültig", neu.uebernehmen())
                assertArrayEquals(l.alt, neu.spk())
            }
            aufgeraeumt(neu)
            tode++; n++
        }
        assertEquals("beginnen hat drei schreibende Schritte", 4, n)

        // --- verwerfen: Verwaltung schreiben · Schlüssel des Versuchs löschen
        n = 1
        while (true) {
            val (l, neu) = stirbt(n) { it.verwerfen() } ?: break
            assertArrayEquals("verwerfen, Tod vor Schritt $n: das gültige Paar bleibt", l.alt, neu.spk())
            neu.schwebend()?.let { assertArrayEquals(l.versuch, it.spk) }
            neu.verwerfen()
            assertNull(neu.schwebend())
            assertArrayEquals(l.alt, neu.spk())
            aufgeraeumt(neu)
            tode++; n++
        }
        assertEquals("verwerfen hat zwei schreibende Schritte", 3, n)

        // --- vergessen: Verwaltung löschen · beide Schlüssel löschen
        n = 1
        while (true) {
            val (l, neu) = stirbt(n) { it.vergessen() } ?: break
            // Vor dem ersten Schritt ist nichts geschehen; danach ist alles fort — nie ein Zwischending.
            neu.spk()?.let { assertArrayEquals(l.alt, it); assertArrayEquals(l.versuch, neu.schwebend()!!.spk) }
                ?: assertNull(neu.schwebend())
            neu.vergessen()
            assertNull(neu.liefern())
            assertNull(neu.inhalt)
            assertTrue("vergessen, Tod vor Schritt $n: der zweite Anlauf löscht jeden Schlüssel", neu.ablage.namen().isEmpty())
            tode++; n++
        }
        assertEquals("vergessen hat drei schreibende Schritte", 4, n)

        assertEquals("gespielte Prozesstode", 10, tode)
    }

    /** Die Datei: Der Versuch übersteht den Prozesstod, mitten in der Kopplung und nach dem Festlegen. */
    @Test fun dateiUeberstehtNeustart() {
        val datei = File(ordner.root, DateiGeraeteschluessel.DATEINAME)
        val schluessel = PruefTresorschluessel()
        val ablage = SpeicherSchluesselablage()
        fun neu() = DateiGeraeteschluessel(datei, schluessel, ablage)
        val beginn = neu().beginnen()

        val nachStart = neu()
        assertArrayEquals(beginn.spk, nachStart.schwebend()!!.spk)
        assertArrayEquals(beginn.zusage, nachStart.schwebend()!!.zusage)
        val f = offen(nachStart.festlegen(kontoSec1, nB))

        val nachFestlegen = neu()
        assertEquals("ein neuer Prozess bleibt bei der ersten Antwort", Festlegung.Grund.ANDERES_PAAR,
            grund(nachFestlegen.festlegen(kontoSec1, ByteArray(16))))
        assertEquals(f.wert, offen(nachFestlegen.festlegen(kontoSec1, nB)).wert)
        assertTrue(nachFestlegen.uebernehmen())

        val fertig = neu().liefern()!!
        assertArrayEquals(beginn.spk, fertig.spk)
        // Und der abgelegte Schlüssel rechnet: Eine Hülle der Fassung 2 trägt seine Kennung und geht beim Konto auf.
        val h = Geraetehuelle.verschliessen2("x".toByteArray(), Huellenoeffner.konto, fertig, "spur", "ah-1-0000000001", "0:1")
        assertArrayEquals(Geraetehuelle.sid(beginn.spk), h.copyOfRange(1, 9))
        assertArrayEquals("x".toByteArray(), Huellenoeffner.oeffnen2(h, beginn.spk, "spur", "ah-1-0000000001", "0:1"))
        assertFalse(File(ordner.root, DateiGeraeteschluessel.DATEINAME + ".neu").exists())
    }

    /**
     * In der Verwaltungsdatei steht kein privater Schlüssel — auch
     * entschlüsselt nicht —, und auf der Platte weder der öffentliche Teil
     * noch `N_g` im Klartext.
     */
    @Test fun dateiOhneSchluessel() {
        val datei = File(ordner.root, DateiGeraeteschluessel.DATEINAME)
        val dateischluessel = PruefTresorschluessel()
        val ablage = SpeicherSchluesselablage()
        val a = DateiGeraeteschluessel(datei, dateischluessel, ablage)
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        a.beginnen()
        val nG = offen(a.festlegen(kontoSec1, nB)).nG
        val spk = a.spk()!!
        fun enthaelt(text: String, b: ByteArray) = text.contains(String(b, Charsets.ISO_8859_1)) ||
            text.contains(java.util.Base64.getEncoder().encodeToString(b).trimEnd('='))

        val platte = String(datei.readBytes(), Charsets.ISO_8859_1)
        assertFalse(enthaelt(platte, spk))
        assertFalse("N_g ist bis zum Offenlegen geheim", enthaelt(platte, nG))

        // `JSONObject` schreibt `/` als `\/` — für den Vergleich mit Base64 zurückgenommen.
        val klar = String(dateischluessel.entschluesseln(datei.readBytes()), Charsets.ISO_8859_1).replace("\\/", "/")
        assertTrue("Vorbedingung: der Klartext trägt den öffentlichen Teil", enthaelt(klar, spk))
        assertEquals(2, ablage.namen().size)
        for (name in ablage.namen()) {
            assertTrue("Vorbedingung: der Klartext nennt den Namen", klar.contains(name))
            assertFalse("kein privater Teil in der Verwaltung", enthaelt(klar, ablage.privat(name)!!))
            assertFalse(enthaelt(platte, ablage.privat(name)!!))
        }
    }

    /** Unlesbar ist wie nicht vorhanden: fremder Schlüssel, verdorbene Datei, Unsinn im Inhalt. */
    @Test fun unlesbarIstWieLeer() {
        val datei = File(ordner.root, DateiGeraeteschluessel.DATEINAME)
        val ablage = SpeicherSchluesselablage()
        val a = DateiGeraeteschluessel(datei, PruefTresorschluessel(), ablage)
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        assertNotNull(a.liefern())
        assertNull("anderer Schlüssel", DateiGeraeteschluessel(datei, PruefTresorschluessel(), ablage).liefern())
        datei.writeBytes(datei.readBytes().also { it[it.size - 1] = (it[it.size - 1] + 1).toByte() })
        assertNull("ein Byte verändert", a.liefern())
        val name = ablage.namen().single()
        val spk = java.util.Base64.getEncoder().encodeToString(ablage.absender(name)!!.spk)
        fun mit(inhalt: String) = SpeicherGeraeteschluessel(inhalt.toByteArray(), ablage)
        assertNotNull("Gegenprobe: so sieht ein lesbarer Inhalt aus", mit("""{"g":{"a":"$name","s":"$spk"}}""").liefern())
        assertNull(mit("kein JSON").liefern())
        assertNull("die Form bis 0.24.x: ein Paar statt eines Namens", mit("""{"g":"AAAA"}""").liefern())
        assertNull(mit("""{"g":{"a":"$name","s":"%%%"}}""").liefern())
        assertNull("kein Punkt der Kurve", mit("""{"g":{"a":"$name","s":"AAAA"}}""").liefern())
        assertNull("ein Name, der keiner ist", mit("""{"g":{"a":"../$name","s":"$spk"}}""").liefern())
        assertNull("ein Name ohne Schlüssel", mit("""{"g":{"a":"0000000000000000","s":"$spk"}}""").liefern())
    }

    /** Trennen räumt die Datei ab — und die Schlüssel. */
    @Test fun dateiVergessen() {
        val datei = File(ordner.root, DateiGeraeteschluessel.DATEINAME)
        val ablage = SpeicherSchluesselablage()
        val a = DateiGeraeteschluessel(datei, PruefTresorschluessel(), ablage)
        a.vergessen()      // ohne Datei: kein Fehler
        a.beginnen()
        assertTrue(datei.exists())
        assertEquals(1, ablage.namen().size)
        a.vergessen()
        assertFalse(datei.exists())
        assertNull(a.schwebend())
        assertTrue(ablage.namen().isEmpty())
    }

    /**
     * Die Ablage bis 0.24.x wird gelöscht: `geraet.bin` und ihre Nachbarin
     * `.neu`. Die neue Verwaltung, der Tresor und der Zähler bleiben.
     */
    @Test fun alteAblageWirdGeloescht() {
        assertTrue("ohne Datei: kein Fehler", AlteGeraeteablage.raeumen(ordner.root))

        val alt = File(ordner.root, "geraet.bin").also { it.writeText("alt") }
        val neben = File(ordner.root, "geraet.bin.neu").also { it.writeText("alt") }
        val bleiben = listOf(DateiGeraeteschluessel.DATEINAME, Schluesseltresor.DATEINAME, org.genem.nadoku.handy.senden.DateiZaehlerstand.DATEINAME, "geraet.binx")
            .map { File(ordner.root, it).also { f -> f.writeText("bleibt") } }
        assertEquals("geraet.bin", AlteGeraeteablage.DATEINAME)
        assertFalse("die neue Datei heißt anders", DateiGeraeteschluessel.DATEINAME.startsWith(AlteGeraeteablage.DATEINAME))

        assertTrue(AlteGeraeteablage.raeumen(ordner.root))
        assertFalse(alt.exists())
        assertFalse(neben.exists())
        for (f in bleiben) assertEquals(f.name, "bleibt", f.readText())

        // Nur die Nachbarin (ein Abbruch mitten im Schreiben unter 0.24.x): auch sie geht.
        neben.writeText("alt")
        assertTrue(AlteGeraeteablage.raeumen(ordner.root))
        assertFalse(neben.exists())
    }
}
