package org.genem.nadoku.handy.tresor

import androidx.test.ext.junit.runners.AndroidJUnit4
import org.junit.Assert.assertArrayEquals
import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import java.security.KeyStore

/**
 * Die EINE Zeile, die der Prüfstand nicht erreicht (S4, Gerätetest).
 *
 * WOFÜR ES DIESE DATEI GIBT. `PruefTresorschluessel` prüft den ganzen
 * Umschlag — AES-256-GCM, frischer Zufallswert, Rundlauf, kein Klartext auf
 * der Platte —, aber mit einem Schlüssel aus dem gewöhnlichen
 * `KeyGenerator`. Ungeprüft blieb damit genau die überschriebene Methode
 * [KeystoreTresorschluessel.schluessel]: dass der Schlüssel **im**
 * `AndroidKeyStore` entsteht und ihn **nicht verlässt**. Robolectric bringt
 * keinen Keystore mit (`KeyStoreException: AndroidKeyStore not found`).
 *
 * ER LÄUFT NUR AUF EINEM ANDROID-SYSTEM — Emulator oder Gerät:
 *
 *     ./gradlew :handy:connectedDebugAndroidTest
 *
 * Ein Emulator genügt und ist im Container erreichbar; die Annahme E-R45-8
 * („kein Emulator") ist widerlegt. Was ein Emulator NICHT belegt, steht am
 * Ende dieser Datei.
 */
@RunWith(AndroidJUnit4::class)
class GeraetTresorTest {

    /*
     * KEINE BACKTICK-NAMEN MIT LEERZEICHEN, so gern man sie in Prueffaellen
     * haette. Sie sind hier nicht Geschmack, sondern ein Baufehler:
     *
     *   D8: Space characters in SimpleName '...' are not allowed prior to
     *       DEX version 040
     *
     * DEX 040 gibt es ab API 30; dieses Modul steht auf `minSdk = 26`
     * (E-S4-03). Der Fehler faellt erst beim Dexen des Test-APK auf, nicht
     * beim Uebersetzen -- wer die Namen zurueckaendert, merkt es also erst
     * zwei Minuten spaeter und an einer Meldung, die nicht nach dem Namen
     * klingt.
     */

    /** Ein eigener Name je Lauf-Klasse, damit der Prüffall nichts überschreibt. */
    private val name = "nadoku-tresor-pruefung"

    private fun keystore(): KeyStore =
        KeyStore.getInstance(KeystoreTresorschluessel.KEYSTORE).apply { load(null) }

    @Before
    fun aufraeumen() {
        /* VOR jedem Fall, nicht danach. Ein Lauf, der mittendrin abbricht,
         * ließe sonst einen Schlüssel stehen, und der nächste Lauf prüfte
         * gegen einen Zustand, den er nicht hergestellt hat. */
        val ks = keystore()
        if (ks.containsAlias(name)) { ks.deleteEntry(name) }
    }

    @Test
    fun schluessel_entsteht_im_AndroidKeyStore() {
        val ks = keystore()
        assertFalse("Vorbedingung: der Name ist frei", ks.containsAlias(name))

        KeystoreTresorschluessel(name).verschluesseln("x".toByteArray())

        assertTrue(
            "Nach dem ersten Gebrauch muss der Schlüssel unter seinem Namen im " +
                "AndroidKeyStore liegen",
            keystore().containsAlias(name),
        )
    }

    @Test
    fun schluessel_verlaesst_den_Keystore_nicht() {
        KeystoreTresorschluessel(name).verschluesseln("x".toByteArray())

        val eintrag = keystore().getEntry(name, null) as KeyStore.SecretKeyEntry
        val schluessel = eintrag.secretKey

        /* DAS IST DIE ZUSICHERUNG, um derentwillen der Keystore benutzt wird.
         * Ein Schlüssel, den die Anwendung als Bytes bekommt, liegt danach im
         * Speicher der Anwendung — und damit in jedem Speicherabbild. Ein
         * Keystore-Schlüssel gibt `null` zurück: Die App hält einen GRIFF,
         * kein Material. */
        assertNull(
            "getEncoded() muss null sein — sonst ist der Schlüssel exportierbar " +
                "und der Keystore hat seinen Zweck verfehlt",
            schluessel.encoded,
        )
        assertEquals("AES", schluessel.algorithm)
    }

    @Test
    fun rundlauf_ueber_den_echten_Keystore() {
        val klartext = "geraeteschluessel-und-serveradresse".toByteArray()
        val tresor = KeystoreTresorschluessel(name)

        val paket = tresor.verschluesseln(klartext)
        assertArrayEquals(klartext, tresor.entschluesseln(paket))
    }

    @Test
    fun schluessel_ueberlebt_einen_neuen_Griff() {
        /* Die App wird beendet und neu gestartet; das Objekt ist ein anderes,
         * der Schlüssel muss derselbe sein. Wäre er es nicht, ließe sich die
         * Ablage nach jedem Neustart nicht mehr lesen — und die Kopplung wäre
         * ohne Fehlermeldung verloren. */
        val paket = KeystoreTresorschluessel(name).verschluesseln("bleibt".toByteArray())
        val zurueck = KeystoreTresorschluessel(name).entschluesseln(paket)
        assertArrayEquals("bleibt".toByteArray(), zurueck)
    }

    @Test
    fun jeder_Schreibvorgang_bekommt_frischen_Zufallswert() {
        /* Zweimal derselbe Zufallswert mit demselben Schlüssel bricht GCM
         * vollständig. Der Prüfstand belegt das für die Hülle; hier gilt es
         * für den echten Keystore-Schlüssel, der den Zufallswert selbst
         * erzeugt. */
        val tresor = KeystoreTresorschluessel(name)
        val klartext = "derselbe Klartext".toByteArray()

        val a = tresor.verschluesseln(klartext)
        val b = tresor.verschluesseln(klartext)

        val zufallA = a.copyOfRange(0, GcmTresorschluessel.ZUFALL_BYTES)
        val zufallB = b.copyOfRange(0, GcmTresorschluessel.ZUFALL_BYTES)
        assertFalse(
            "Zwei Schreibvorgänge dürfen nie denselben Zufallswert tragen",
            zufallA.contentEquals(zufallB),
        )
        assertFalse("und damit auch nie denselben Geheimtext", a.contentEquals(b))
    }

    @Test
    fun verfaelschtes_Paket_wird_abgelehnt() {
        val tresor = KeystoreTresorschluessel(name)
        val paket = tresor.verschluesseln("unverfaelscht".toByteArray())

        /* Ein Byte im Geheimtext kippen. GCM muss das bemerken — sonst käme
         * stillschweigend ein anderer Schlüssel heraus, der beim Server als
         * 401 aufschlüge, wo niemand die Ursache vermutete. */
        val verfaelscht = paket.copyOf()
        val stelle = GcmTresorschluessel.ZUFALL_BYTES + 1
        verfaelscht[stelle] = (verfaelscht[stelle].toInt() xor 0x01).toByte()

        var geworfen: Throwable? = null
        try { tresor.entschluesseln(verfaelscht) } catch (e: Throwable) { geworfen = e }
        assertNotNull("Ein verfälschtes Paket muss werfen, nicht entschlüsseln", geworfen)
    }

    /*
     * WAS AUCH DIESER PRÜFFALL NICHT BELEGT — und das gehört hierher, weil es
     * sonst niemand mitliest:
     *
     * Ein EMULATOR hat keinen Hardware-Sicherheitsanker. Der `AndroidKeyStore`
     * ist dort in Software nachgebildet; `getEncoded() == null` gilt, aber die
     * Aussage „auch mit Root-Rechten nicht auslesbar" hängt am
     * Trusted Execution Environment eines echten Geräts.
     *
     * Belegt ist damit: die Schnittstelle stimmt, der Schlüssel entsteht unter
     * seinem Namen, überlebt, ist nicht exportierbar, und der Umschlag trägt.
     * Nicht belegt ist die Härte des Ankers. Das bleibt auf der Prüfliste des
     * Gerätetests.
     */
}
