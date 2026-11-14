package org.genem.nadoku.handy.tresor

import org.genem.nadoku.handy.kopplung.Kopplungswert
import org.genem.nadoku.handy.senden.Geraetehuelle
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
 * Das Gerätepaar und die Ablage der Kopplung.
 *
 * Anlass: Nr. 563 — der Vergleichswert schützt nur, wenn das Gerät `N_g`
 * erst nach dem Festlegen herausgibt und danach bei seinem Paar bleibt; und
 * eine abgebrochene Kopplung darf das Paar nicht kosten, unter dem der
 * Rückstand noch hinausgeht.
 *
 * NICHT GEPRÜFT: dass der Schlüssel der Datei auf dem Gerät aus dem
 * `AndroidKeyStore` kommt — dieselbe eine Zeile wie beim Tresor
 * ([PruefTresorschluessel]), Prüfliste des Gerätetests.
 */
@RunWith(RobolectricTestRunner::class)
class GeraeteschluesselTest {

    @get:Rule val ordner = TemporaryFolder()

    private val konto: KeyPair = Geraetehuelle.neuesPaar()
    private val kontoSec1: ByteArray get() = Geraetehuelle.sec1(konto.public as ECPublicKey)
    private val nB = ByteArray(16) { (it + 1).toByte() }

    private fun offen(f: Festlegung): Festlegung.Offen = f as Festlegung.Offen
    private fun grund(f: Festlegung): Festlegung.Grund = (f as Festlegung.Abbruch).grund

    /** Ein erzeugtes Paar passt zusammen: ECDH von beiden Seiten ergibt dasselbe Geheimnis. */
    @Test fun erzeugtesPaarPasstZusammen() {
        val g = Geraetepaar.erzeugen()
        assertEquals(32, g.privat.size)
        assertEquals(65, g.spk.size)
        assertEquals(8, g.sid().size)
        val hin = KeyAgreement.getInstance("ECDH").run { init(g.schluessel()); doPhase(konto.public, true); generateSecret() }
        val her = KeyAgreement.getInstance("ECDH").run {
            init(konto.private); doPhase(Geraetehuelle.oeffentlich(g.spk), true); generateSecret()
        }
        assertArrayEquals(hin, her)
        assertFalse(g.spk.contentEquals(Geraetepaar.erzeugen().spk))
        assertFalse("kein Schlüssel im Text", g.toString().contains(g.privat.joinToString("") { "%02x".format(it) }))
        // Ein öffentlicher Teil neben der Kurve wird nicht angenommen.
        assertTrue(runCatching { Geraetepaar(g.privat, g.spk.copyOf().also { it[64] = (it[64] + 1).toByte() }) }.isFailure)
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

        val f = offen(a.festlegen(kontoSec1, nB))
        assertArrayEquals("die Zusage passt zu dem, was offengelegt wird", beginn.zusage, Kopplungswert.zusage(f.nG, beginn.spk))
        assertEquals(Kopplungswert.wert(kontoSec1, beginn.spk, f.nG, nB), f.wert)

        assertTrue(a.uebernehmen())
        assertArrayEquals(beginn.spk, a.spk())
        assertNull("der Versuch ist aufgegangen", a.schwebend())
        assertFalse("ein zweites Mal gibt es nichts zu übernehmen", a.uebernehmen())
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
        a.festlegen(kontoSec1, nB)
        a.verwerfen()
        assertArrayEquals(erste.spk, a.spk())
        assertNull(a.schwebend())
        assertEquals(Festlegung.Grund.KEIN_VERSUCH, grund(a.festlegen(kontoSec1, nB)))

        val dritte = a.beginnen()
        a.festlegen(kontoSec1, nB)
        assertTrue(a.uebernehmen())
        assertArrayEquals(dritte.spk, a.spk())
    }

    /** Ein neues `beginnen()` verwirft den alten Versuch samt seiner Festlegung. */
    @Test fun neuerVersuchIstFrisch() {
        val a = SpeicherGeraeteschluessel()
        val eins = a.beginnen()
        val f1 = offen(a.festlegen(kontoSec1, nB))
        val zwei = a.beginnen()
        assertFalse(eins.spk.contentEquals(zwei.spk))
        assertFalse(eins.zusage.contentEquals(zwei.zusage))
        assertFalse("ohne neue Festlegung wird nichts gültig", a.uebernehmen())
        val anderesNb = ByteArray(16) { 7 }
        val f2 = offen(a.festlegen(kontoSec1, anderesNb))
        assertFalse(f1.nG.contentEquals(f2.nG))
    }

    /** Trennen vergisst beides. */
    @Test fun vergessen() {
        val a = SpeicherGeraeteschluessel()
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        a.beginnen()
        a.vergessen()
        assertNull(a.liefern())
        assertNull(a.schwebend())
        assertNull(a.inhalt)
    }

    /** Die Datei: Der Versuch übersteht den Prozesstod, mitten in der Kopplung und nach dem Festlegen. */
    @Test fun dateiUeberstehtNeustart() {
        val datei = File(ordner.root, TresorGeraeteschluessel.DATEINAME)
        val schluessel = PruefTresorschluessel()
        val beginn = TresorGeraeteschluessel(datei, schluessel).beginnen()

        val nachStart = TresorGeraeteschluessel(datei, schluessel)
        assertArrayEquals(beginn.spk, nachStart.schwebend()!!.spk)
        assertArrayEquals(beginn.zusage, nachStart.schwebend()!!.zusage)
        val f = offen(nachStart.festlegen(kontoSec1, nB))

        val nachFestlegen = TresorGeraeteschluessel(datei, schluessel)
        assertEquals("ein neuer Prozess bleibt bei der ersten Antwort", Festlegung.Grund.ANDERES_PAAR,
            grund(nachFestlegen.festlegen(kontoSec1, ByteArray(16))))
        assertEquals(f.wert, offen(nachFestlegen.festlegen(kontoSec1, nB)).wert)
        assertTrue(nachFestlegen.uebernehmen())

        val fertig = TresorGeraeteschluessel(datei, schluessel).liefern()!!
        assertArrayEquals(beginn.spk, fertig.spk)
        // Und das abgelegte Paar rechnet: eine Hülle der Fassung 2 trägt seine Kennung.
        val h = Geraetehuelle.verschliessen2(ByteArray(3), kontoSec1, fertig, "spur", "ah-1-0000000001", "0:1")
        assertArrayEquals(Geraetehuelle.sid(beginn.spk), h.copyOfRange(1, 9))
        assertFalse(File(ordner.root, TresorGeraeteschluessel.DATEINAME + ".neu").exists())
    }

    /** Auf der Platte steht kein Schlüsselmaterial im Klartext — weder roh noch als Base64. */
    @Test fun dateiOhneKlartext() {
        val datei = File(ordner.root, TresorGeraeteschluessel.DATEINAME)
        val a = TresorGeraeteschluessel(datei, PruefTresorschluessel())
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        val paar = a.liefern()!!
        val platte = String(datei.readBytes(), Charsets.ISO_8859_1)
        fun enthaelt(b: ByteArray) = platte.contains(String(b, Charsets.ISO_8859_1)) ||
            platte.contains(java.util.Base64.getEncoder().encodeToString(b).trimEnd('='))
        assertFalse(enthaelt(paar.privat))
        assertFalse(enthaelt(paar.spk))
        assertFalse(enthaelt(paar.privat + paar.spk))
    }

    /** Unlesbar ist wie nicht vorhanden: fremder Schlüssel, verdorbene Datei, Unsinn im Inhalt. */
    @Test fun unlesbarIstWieLeer() {
        val datei = File(ordner.root, TresorGeraeteschluessel.DATEINAME)
        val a = TresorGeraeteschluessel(datei, PruefTresorschluessel())
        a.beginnen(); a.festlegen(kontoSec1, nB); a.uebernehmen()
        assertNull("anderer Schlüssel", TresorGeraeteschluessel(datei, PruefTresorschluessel()).liefern())
        datei.writeBytes(datei.readBytes().also { it[it.size - 1] = (it[it.size - 1] + 1).toByte() })
        assertNull("ein Byte verändert", a.liefern())
        assertNull(SpeicherGeraeteschluessel("kein JSON".toByteArray()).liefern())
        assertNull(SpeicherGeraeteschluessel("""{"g":"AAAA"}""".toByteArray()).liefern())
        assertNull(SpeicherGeraeteschluessel("""{"g":"%%%"}""".toByteArray()).liefern())
    }

    /** Trennen räumt die Datei ab. */
    @Test fun dateiVergessen() {
        val datei = File(ordner.root, TresorGeraeteschluessel.DATEINAME)
        val a = TresorGeraeteschluessel(datei, PruefTresorschluessel())
        a.vergessen()      // ohne Datei: kein Fehler
        a.beginnen()
        assertTrue(datei.exists())
        a.vergessen()
        assertFalse(datei.exists())
        assertNull(a.schwebend())
    }
}
