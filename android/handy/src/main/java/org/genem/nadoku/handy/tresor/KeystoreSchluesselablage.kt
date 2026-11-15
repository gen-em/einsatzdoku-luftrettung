package org.genem.nadoku.handy.tresor

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import org.genem.nadoku.handy.senden.Geraetehuelle
import java.security.KeyPairGenerator
import java.security.KeyStore
import java.security.PrivateKey
import java.security.interfaces.ECPublicKey
import java.security.spec.ECGenParameterSpec
import javax.crypto.KeyAgreement

/**
 * Die Schlüsselablage des Geräts: der **Android Keystore** (E-KA-71).
 *
 * WAS HIER ENTSTEHT. Ein Paar auf P-256, erzeugt IM Keystore, mit genau
 * einem Zweck: Schlüsselvereinbarung (`PURPOSE_AGREE_KEY`, ECDH). Kein
 * Signieren, kein Entschlüsseln — was der Eintrag nicht darf, kann auch
 * niemand mit ihm tun, der die App übernimmt. Der private Teil ist nicht
 * exportierbar; die App hält einen Griff, kein Material.
 *
 * WAS BEWUSST FEHLT:
 *  - `setUserAuthenticationRequired` und `setUnlockedDeviceRequired`: Das
 *    Senden läuft im Hintergrund und darf nicht an der Bildschirmsperre
 *    hängen (wie [KeystoreTresorschluessel], und aus demselben Grund).
 *  - `setIsStrongBoxBacked`: nicht verlangt (E-KA-71). Viele Geräte haben
 *    keine StrongBox, und ein Handy ohne sie soll koppeln können.
 *
 * WAS DER PRÜFSTAND NICHT ERREICHT: diese ganze Datei. Robolectric bringt
 * keinen `AndroidKeyStore` mit; die Regeln darüber sind mit einer Ablage
 * im Speicher geprüft, diese Umsetzung nur im Gerätetest
 * (`GeraeteschluesselGeraetTest`).
 *
 * @param vorsatz steht vor jedem Namen und grenzt die Einträge dieser
 *   Ablage von allen anderen im Keystore der App ab — [namen] und damit das
 *   Aufräumen sehen nur, was so beginnt. Der Gerätetest nimmt einen eigenen.
 */
class KeystoreSchluesselablage(
    private val vorsatz: String = VORSATZ,
) : Schluesselablage {

    private fun keystore(): KeyStore = KeyStore.getInstance(KEYSTORE).apply { load(null) }

    override fun erzeugen(name: String): ByteArray {
        val erzeuger = KeyPairGenerator.getInstance(KeyProperties.KEY_ALGORITHM_EC, KEYSTORE)
        erzeuger.initialize(
            KeyGenParameterSpec.Builder(vorsatz + name, KeyProperties.PURPOSE_AGREE_KEY)
                .setAlgorithmParameterSpec(ECGenParameterSpec(KURVE))
                .setUserAuthenticationRequired(false)
                .build(),
        )
        return Geraetehuelle.sec1(erzeuger.generateKeyPair().public as ECPublicKey)
    }

    override fun absender(name: String): Absender? = try {
        val ks = keystore()
        val privat = ks.getKey(vorsatz + name, null) as? PrivateKey
        val oeffentlich = ks.getCertificate(vorsatz + name)?.publicKey as? ECPublicKey
        if (privat == null || oeffentlich == null) null else KeystoreAbsender(privat, Geraetehuelle.sec1(oeffentlich))
    } catch (e: Exception) {
        /* NICHT BENUTZBAR IST WIE NICHT VORHANDEN: Das Handy sendet in
         * diesem Lauf nichts, wo der Server Fassung 2 verlangt (seit KA-15,
         * E-KA-80; bis dahin Fassung 1), und der nächste fragt wieder.
         * Gelöscht wird hier nichts — ein Ausfall des Keystore-Dienstes
         * geht vorüber, ein gelöschter Eintrag nicht. */
        null
    }

    override fun loeschen(name: String) {
        val ks = keystore()
        if (ks.containsAlias(vorsatz + name)) ks.deleteEntry(vorsatz + name)
    }

    override fun namen(): Set<String> =
        keystore().aliases().asSequence().filter { it.startsWith(vorsatz) }.map { it.removePrefix(vorsatz) }.toSet()

    /**
     * Der Griff auf einen Eintrag. `privat` ist KEIN Schlüsselmaterial,
     * sondern ein Verweis in den Keystore (`getEncoded()` ist `null`).
     */
    private class KeystoreAbsender(private val privat: PrivateKey, spk: ByteArray) : Absender {

        override val spk: ByteArray = spk.copyOf()

        override fun vereinbaren(konto: ECPublicKey): ByteArray = try {
            /* DER ANBIETER IST GENANNT, nicht dem Zufall der Auswahl
             * überlassen: Nur der des Keystores kann mit einem Griff
             * rechnen. Das Ergebnis ist die X-Koordinate, 32 Byte
             * big-endian — dieselben Bytes wie aus der Software-Rechnung,
             * gemessen im Gerätetest gegen den privaten Kontoschlüssel der
             * Vektoren. */
            KeyAgreement.getInstance("ECDH", KEYSTORE).run { init(privat); doPhase(konto, true); generateSecret() }
        } catch (e: Exception) {
            throw Schluesselausfall(e)
        }

        override fun toString(): String = "KeystoreAbsender(Schlüssel im Keystore)"
    }

    companion object {
        const val KEYSTORE = KeystoreTresorschluessel.KEYSTORE
        const val KURVE = "secp256r1"

        /** Nicht `nadoku-tresor…`: Der Schlüssel der Dateien darf vom Aufräumen hier nie getroffen werden. */
        const val VORSATZ = "nadoku-geraet-"
    }
}
