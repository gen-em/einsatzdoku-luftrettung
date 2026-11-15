package org.genem.nadoku.handy.tresor

import android.security.keystore.KeyInfo
import android.security.keystore.KeyProperties
import android.util.Log
import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import org.genem.nadoku.handy.senden.Geraetehuelle
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
import java.io.File
import java.security.KeyFactory
import java.security.KeyStore
import java.security.PrivateKey
import java.security.interfaces.ECPublicKey
import java.security.spec.ECPrivateKeySpec
import javax.crypto.Cipher
import javax.crypto.KeyAgreement
import javax.crypto.Mac
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * Der Geräteschlüssel im ECHTEN Android Keystore (Gerätetest, seit KA-06f).
 *
 * Anlass: Nr. 563 — der Geräteschlüssel belegt die Herkunft einer Hülle nur,
 * solange ihn niemand kopieren kann (F-KA-85, E-KA-71).
 *
 * WOFÜR ES DIESE DATEI GIBT. `GeraeteschluesselTest` prüft die Regeln der
 * Kopplung über einer Schlüsselablage im Speicher — mit gewöhnlichen
 * Schlüsseln, weil Robolectric keinen `AndroidKeyStore` mitbringt. Ungeprüft
 * bleibt dort die ganze [KeystoreSchluesselablage]: dass der Schlüssel IM
 * Keystore entsteht, ihn nicht verlässt, nur vereinbaren darf, keine
 * Entsperrung verlangt — und dass, was der Keystore vereinbart, dieselben
 * Bytes sind wie aus der Software-Rechnung, sodass die Hülle beim Konto
 * aufgeht. Das zeigt nur ein Lauf auf einem Android-System:
 *
 *     ./gradlew :handy:connectedDebugAndroidTest
 *
 * KEINE BACKTICK-NAMEN MIT LEERZEICHEN (Kopf von `GeraetTresorTest`).
 *
 * Was auch dieser Prüffall nicht belegt, steht am Ende der Datei.
 */
@RunWith(AndroidJUnit4::class)
class GeraeteschluesselGeraetTest {

    /* EIGENE NAMEN, damit der Prüffall auf einem Gerät mit gekoppelter App
     * nichts anfasst: ein eigener Vorsatz im Keystore (das Aufräumen der
     * Ablage sieht nur, was so beginnt), ein eigener Schlüssel und eine
     * eigene Datei für die Verwaltung. */
    private val vorsatz = "nadoku-pruefung-geraet-"
    private val dateischluessel = "nadoku-pruefung-geraet.tresor"
    private val nachbar = "nadoku-pruefung-nachbar-"

    private val verzeichnis: File get() = InstrumentationRegistry.getInstrumentation().targetContext.filesDir
    private val datei: File get() = File(verzeichnis, "absender-pruefung.bin")

    private fun keystore(): KeyStore = KeyStore.getInstance(KeystoreSchluesselablage.KEYSTORE).apply { load(null) }
    private fun eintraege(beginn: String): List<String> = keystore().aliases().toList().filter { it.startsWith(beginn) }

    private fun ablage() = KeystoreSchluesselablage(vorsatz)
    private fun geraeteschluessel() = DateiGeraeteschluessel(datei, KeystoreTresorschluessel(dateischluessel), ablage())

    private fun hex(s: String): ByteArray =
        if (s.isEmpty()) ByteArray(0) else ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    /** Das Konto der Referenzvektoren der Fassung 2 (`tools/proben/huelle/vektoren-2.json`, über `androidTest.assets`). */
    private val konto: JSONObject by lazy {
        JSONObject(InstrumentationRegistry.getInstrumentation().context.assets
            .open("vektoren-2.json").bufferedReader().readText()).getJSONObject("konto")
    }
    private val kontoSec1: ByteArray get() = hex(konto.getString("oeffentlich_hex"))
    private val kontoPrivat: PrivateKey get() = Geraetehuelle.privat(hex(konto.getString("privat_hex")))

    private fun ecdhKonto(gegen: ByteArray): ByteArray = KeyAgreement.getInstance("ECDH").run {
        init(kontoPrivat); doPhase(Geraetehuelle.oeffentlich(gegen), true); generateSecret()
    }

    /** `oeffnen2()` der Referenz, mit dem privaten Schlüssel des Kontos — wie `Huellenoeffner` im Prüfstand. */
    private fun oeffnen2(h: ByteArray, spk: ByteArray, art: String, ref: String, platz: String): ByteArray {
        val epk = h.copyOfRange(9, 74)
        val iv = h.copyOfRange(74, 90)
        val okm = Geraetehuelle.hkdf(ecdhKonto(epk) + ecdhKonto(spk), epk, "nadoku-huelle-2".toByteArray() + kontoSec1 + spk)
        val tag = Mac.getInstance("HmacSHA256").run {
            init(SecretKeySpec(okm.copyOfRange(32, 64), "HmacSHA256"))
            doFinal(h.copyOfRange(0, h.size - 32) + Geraetehuelle.bindung(art, ref, platz))
        }
        check(tag.contentEquals(h.copyOfRange(h.size - 32, h.size))) { "pruefwert" }
        return Cipher.getInstance("AES/CBC/PKCS5Padding").run {
            init(Cipher.DECRYPT_MODE, SecretKeySpec(okm.copyOfRange(0, 32), "AES"), IvParameterSpec(iv))
            doFinal(h.copyOfRange(90, h.size - 32))
        }
    }

    @Before
    fun aufraeumen() {
        // VOR jedem Fall, nicht nur danach — wie in `GeraetTresorTest`.
        val ks = keystore()
        for (a in eintraege(vorsatz) + eintraege(nachbar) + eintraege(dateischluessel)) ks.deleteEntry(a)
        datei.delete()
        File(verzeichnis, datei.name + ".neu").delete()
    }

    @After
    fun hinterher() = aufraeumen()

    @Test
    fun schluessel_entsteht_im_AndroidKeyStore_und_bleibt_dort() {
        val name = "00000000000000a1"
        assertTrue("Vorbedingung: kein Eintrag", eintraege(vorsatz).isEmpty())

        val spk = ablage().erzeugen(name)

        assertEquals(listOf(vorsatz + name), eintraege(vorsatz))
        assertEquals(65, spk.size)
        Geraetehuelle.oeffentlich(spk)      // ein Punkt auf P-256, sonst wirft es
        assertArrayEquals(
            "der öffentliche Teil am Eintrag ist der, den erzeugen() genannt hat",
            spk, Geraetehuelle.sec1(keystore().getCertificate(vorsatz + name).publicKey as ECPublicKey),
        )

        val privat = keystore().getKey(vorsatz + name, null) as PrivateKey

        /* DIE ZUSICHERUNG, um derentwillen der Schlüssel hier liegt: Die App
         * hält einen Griff, kein Material. */
        assertNull("getEncoded() muss null sein — sonst ist der private Teil exportierbar", privat.encoded)
        assertNull(privat.format)
        assertFalse(
            "der private Wert darf sich nicht als Schlüsselangabe herausholen lassen",
            runCatching {
                KeyFactory.getInstance("EC").getKeySpec(privat, ECPrivateKeySpec::class.java).s
            }.isSuccess,
        )

        val info = KeyFactory.getInstance(privat.algorithm, KeystoreSchluesselablage.KEYSTORE)
            .getKeySpec(privat, KeyInfo::class.java)
        assertEquals("nur Schlüsselvereinbarung, sonst nichts", KeyProperties.PURPOSE_AGREE_KEY, info.purposes)
        assertFalse("keine Entsperrpflicht: gesendet wird im Hintergrund", info.isUserAuthenticationRequired)
        assertEquals("im Keystore erzeugt, nicht eingeführt", KeyProperties.ORIGIN_GENERATED, info.origin)
        assertEquals(256, info.keySize)

        /* NUR BERICHTET, NICHT VERLANGT (E-KA-71): wo der Keystore dieses
         * Geräts den Schlüssel hält. 1 = gesicherte Umgebung (TEE),
         * 2 = StrongBox, 0 = Software, -1/-2 = unbekannt. */
        Log.i("NAdokuPruefung", "Geraeteschluessel: securityLevel=${info.securityLevel}")
        println("Geraeteschluessel: securityLevel=${info.securityLevel}")
    }

    @Test
    fun keystore_vereinbart_dasselbe_wie_die_Gegenseite() {
        val a = ablage()
        val spk = a.erzeugen("00000000000000a2")
        val absender = a.absender("00000000000000a2")!!
        assertArrayEquals(spk, absender.spk)

        val zS = absender.vereinbaren(Geraetehuelle.oeffentlich(kontoSec1))
        assertEquals(32, zS.size)
        assertArrayEquals(
            "Z_s aus dem Keystore = ECDH(Konto privat, Gerät öffentlich)",
            ecdhKonto(spk), zS,
        )
        assertFalse("kein Schlüssel im Text", absender.toString().contains(spk.joinToString("") { "%02x".format(it) }))
        assertNull("ein Name ohne Eintrag ergibt keinen Absender", a.absender("00000000000000ff"))
    }

    @Test
    fun huelle_mit_Keystore_Schluessel_geht_beim_Konto_auf() {
        val a = ablage()
        val spk = a.erzeugen("00000000000000a3")
        val absender = a.absender("00000000000000a3")!!
        val klar = "nadoku-handy-keystore".toByteArray()

        val h = Geraetehuelle.verschliessen2(klar, kontoSec1, absender, "spur", "ah-1-0000000001", "0:1")

        assertEquals(2, h[0].toInt())
        assertArrayEquals(Geraetehuelle.sid(spk), h.copyOfRange(1, 9))
        assertArrayEquals(klar, oeffnen2(h, spk, "spur", "ah-1-0000000001", "0:1"))
        assertTrue("anderer Platz", runCatching { oeffnen2(h, spk, "spur", "ah-1-0000000001", "1:1") }.isFailure)

        // Die Herkunft: Unter dem Schlüssel eines ANDEREN Eintrags stimmt das Prüfwort nicht.
        val fremd = a.erzeugen("00000000000000a4")
        assertTrue("anderes Gerät", runCatching { oeffnen2(h, fremd, "spur", "ah-1-0000000001", "0:1") }.isFailure)
    }

    @Test
    fun kopplung_mit_echten_Eintraegen() {
        val nB = ByteArray(16) { (it + 1).toByte() }
        val g = geraeteschluessel()
        assertNull(g.liefern())

        // Erste Kopplung: beginnen, festlegen, übernehmen, liefern.
        val erste = g.beginnen()
        assertEquals("ein Eintrag: der Versuch", 1, eintraege(vorsatz).size)
        assertNull("vor dem Übernehmen gilt nichts", g.liefern())
        assertFalse("ohne Festlegung wird nichts gültig", g.uebernehmen())
        assertTrue(g.festlegen(kontoSec1, nB) is Festlegung.Offen)
        assertTrue(g.uebernehmen())
        assertArrayEquals(erste.spk, g.liefern()!!.spk)
        assertEquals(1, eintraege(vorsatz).size)

        // Ein neuer Prozess findet dasselbe Paar, und es rechnet.
        val nachStart = geraeteschluessel().liefern()
        assertNotNull(nachStart)
        assertArrayEquals(erste.spk, nachStart!!.spk)
        val h = Geraetehuelle.verschliessen2("x".toByteArray(), kontoSec1, nachStart, "ruhe", "ah-1-0000000002", "0:1")
        assertArrayEquals("x".toByteArray(), oeffnen2(h, erste.spk, "ruhe", "ah-1-0000000002", "0:1"))

        // Zweite Kopplung: Der Versuch liegt NEBEN dem gültigen Eintrag, und ein Abbruch lässt den stehen.
        val zweite = geraeteschluessel().beginnen()
        assertEquals("zwei Einträge: gültig und Versuch", 2, eintraege(vorsatz).size)
        assertArrayEquals("noch gilt das erste Paar", erste.spk, geraeteschluessel().spk())
        geraeteschluessel().verwerfen()
        assertEquals("der Eintrag des Versuchs ist gelöscht", 1, eintraege(vorsatz).size)
        assertArrayEquals(erste.spk, geraeteschluessel().spk())

        // Dritte: übernommen — das neue Paar gilt, der Eintrag des alten ist gelöscht.
        val dritte = geraeteschluessel().beginnen()
        assertFalse(zweite.spk.contentEquals(dritte.spk))
        val neu = geraeteschluessel()       // ein neuer Prozess mitten in der Kopplung
        assertArrayEquals(dritte.spk, neu.schwebend()!!.spk)
        assertTrue(neu.festlegen(kontoSec1, nB) is Festlegung.Offen)
        assertTrue(neu.uebernehmen())
        assertArrayEquals(dritte.spk, geraeteschluessel().spk())
        assertEquals(1, eintraege(vorsatz).size)
        val h2 = Geraetehuelle.verschliessen2("y".toByteArray(), kontoSec1, geraeteschluessel().liefern()!!, "spur", "m-1", "0:1")
        assertArrayEquals("y".toByteArray(), oeffnen2(h2, dritte.spk, "spur", "m-1", "0:1"))
        assertTrue("unter dem abgelösten Schlüssel geht sie nicht auf", runCatching { oeffnen2(h2, erste.spk, "spur", "m-1", "0:1") }.isFailure)
    }

    @Test
    fun vergessen_raeumt_Eintraege_und_Datei_und_sonst_nichts() {
        // Ein Nachbar im selben Keystore, unter anderem Vorsatz — wie der Schlüssel des Tresors.
        KeystoreSchluesselablage(nachbar).erzeugen("00000000000000b1")

        val g = geraeteschluessel()
        g.beginnen(); g.festlegen(kontoSec1, ByteArray(16)); assertTrue(g.uebernehmen())
        g.beginnen()
        ablage().erzeugen("00000000000000a5")       // ein Waise, von dem die Verwaltung nichts weiß
        assertEquals(3, eintraege(vorsatz).size)
        assertTrue(datei.exists())
        assertTrue("der Schlüssel der Verwaltungsdatei liegt im Keystore", keystore().containsAlias(dateischluessel))

        g.vergessen()

        assertTrue("kein Eintrag des Geräteschlüssels bleibt", eintraege(vorsatz).isEmpty())
        assertFalse(datei.exists())
        assertNull(geraeteschluessel().liefern())
        assertNull(geraeteschluessel().schwebend())
        assertEquals("der Nachbar ist unberührt", 1, eintraege(nachbar).size)
        assertTrue("und der Schlüssel der Datei auch", keystore().containsAlias(dateischluessel))
    }

    @Test
    fun verwaltungsdatei_traegt_keinen_Schluessel_im_Klartext() {
        val g = geraeteschluessel()
        g.beginnen()
        val nG = (g.festlegen(kontoSec1, ByteArray(16)) as Festlegung.Offen).nG
        assertTrue(g.uebernehmen())
        val spk = g.spk()!!
        val platte = String(datei.readBytes(), Charsets.ISO_8859_1)
        fun enthaelt(b: ByteArray) = platte.contains(String(b, Charsets.ISO_8859_1)) ||
            platte.contains(java.util.Base64.getEncoder().encodeToString(b).trimEnd('='))
        assertFalse(enthaelt(spk))
        assertFalse(enthaelt(nG))
        assertFalse("auch der Name des Eintrags steht nicht lesbar da", platte.contains(eintraege(vorsatz).single().removePrefix(vorsatz)))
    }

    /*
     * WAS AUCH DIESER PRÜFFALL NICHT BELEGT:
     *
     * - Ein EMULATOR hat keinen Hardware-Sicherheitsanker; der Keystore ist
     *   dort in Software nachgebildet. `getEncoded() == null` und der Zweck
     *   des Eintrags gelten, die Aussage „auch mit Root-Rechten nicht
     *   auslesbar" hängt an der gesicherten Umgebung eines echten Geräts.
     *   `securityLevel` wird deshalb berichtet und nicht verlangt.
     * - Dass der Schlüssel bei GESPERRTEM Bildschirm rechnet: Geprüft ist die
     *   Eigenschaft des Eintrags (keine Nutzerauthentisierung verlangt), nicht
     *   ein Sendelauf hinter der Sperre. Das bleibt Handprüfung.
     * - Den Prozesstod zwischen zwei Schritten: Den spielt
     *   `GeraeteschluesselTest.prozesstodAnJedemSchritt` über der
     *   Speicher-Ablage; hier laufen die Schritte nur durch.
     */
}
