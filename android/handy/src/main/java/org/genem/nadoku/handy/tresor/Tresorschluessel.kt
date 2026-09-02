package org.genem.nadoku.handy.tresor

import android.security.keystore.KeyGenParameterSpec
import android.security.keystore.KeyProperties
import java.security.KeyStore
import javax.crypto.Cipher
import javax.crypto.KeyGenerator
import javax.crypto.SecretKey
import javax.crypto.spec.GCMParameterSpec

/**
 * Verschlüsselt und entschlüsselt die Ablage des Tresors (E-S4-13).
 *
 * ZWEI TEILE, GETRENNT GEHALTEN: die **Hülle** (AES-256-GCM mit frischem
 * Zufallswert je Schreibvorgang) und die **Herkunft des Schlüssels**. Die
 * Hülle steht in [GcmTresorschluessel] und ist auf jedem Weg dieselbe; woher
 * der Schlüssel kommt, unterscheidet die Umsetzungen.
 *
 * WARUM DIE TRENNUNG ÜBERHAUPT EXISTIERT — und das gehört gesagt, nicht
 * verschwiegen: Der **`AndroidKeyStore` steht im Prüfstand nicht zur
 * Verfügung** (Robolectric meldet `KeyStoreException: AndroidKeyStore not
 * found`). Ohne diese Trennung wäre der gesamte Tresor ungeprüft. Mit ihr ist
 * geprüft, was geprüft werden kann — die Hülle, der Rundlauf, die Abwesenheit
 * von Klartext im Speicherabbild —, und ungeprüft bleibt genau **eine** Zeile:
 * dass der Schlüssel aus dem Keystore kommt und nicht exportierbar ist. Die
 * steht auf der Prüfliste des Gerätetests.
 */
interface Tresorschluessel {
    /** Ergibt `Zufallswert || Geheimtext+Prüfsumme`. */
    fun verschluesseln(klartext: ByteArray): ByteArray

    /** Kehrt [verschluesseln] um; wirft, wenn das Paket nicht stimmt. */
    fun entschluesseln(paket: ByteArray): ByteArray
}

/**
 * Die Hülle: AES-256-GCM.
 *
 * GCM UND NICHT CBC, weil GCM die Unverfälschtheit mitprüft. Ein vertauschtes
 * Byte in der Ablage ergibt dann einen Fehler und nicht stillschweigend einen
 * anderen Schlüssel — der dann als „Schlüssel ungültig" (401) beim Server
 * aufschlüge, wo niemand die Ursache vermuten würde.
 *
 * DER ZUFALLSWERT IST JE SCHREIBVORGANG NEU und steht unverschlüsselt vor dem
 * Geheimtext; das ist die vorgesehene Bauform. Ein zweites Mal derselbe
 * Zufallswert mit demselben Schlüssel bricht GCM vollständig — deshalb kommt
 * er aus dem Zufallsgenerator der Plattform und nirgendwo sonst her.
 */
abstract class GcmTresorschluessel : Tresorschluessel {

    protected abstract fun schluessel(): SecretKey

    override fun verschluesseln(klartext: ByteArray): ByteArray {
        val c = Cipher.getInstance(VERFAHREN)
        c.init(Cipher.ENCRYPT_MODE, schluessel())    // erzeugt den Zufallswert selbst
        val geheim = c.doFinal(klartext)
        val zufall = c.iv
        require(zufall.size == ZUFALL_BYTES) {
            "Unerwartete Länge des Zufallswerts: ${zufall.size}"
        }
        return zufall + geheim
    }

    override fun entschluesseln(paket: ByteArray): ByteArray {
        require(paket.size > ZUFALL_BYTES) { "Ablage zu kurz für ein GCM-Paket" }
        val c = Cipher.getInstance(VERFAHREN)
        c.init(
            Cipher.DECRYPT_MODE,
            schluessel(),
            GCMParameterSpec(PRUEFSUMME_BITS, paket, 0, ZUFALL_BYTES),
        )
        return c.doFinal(paket, ZUFALL_BYTES, paket.size - ZUFALL_BYTES)
    }

    companion object {
        const val VERFAHREN = "AES/GCM/NoPadding"
        const val ZUFALL_BYTES = 12      // die für GCM vorgesehene Länge
        const val PRUEFSUMME_BITS = 128
        const val SCHLUESSEL_BITS = 256
    }
}

/**
 * Der Schlüssel liegt im **Android Keystore** (E-S4-13).
 *
 * Er wird dort erzeugt und verlässt ihn nie: Die App bekommt kein
 * Schlüsselmaterial zu sehen, sondern nur einen Griff darauf. Wer das
 * Dateisystem des Geräts kopiert — und sei es mit Root-Rechten —, hat damit
 * den Geheimtext und nicht den Schlüssel.
 *
 * `setUserAuthenticationRequired` ist **aus**, und das ist eine Entscheidung:
 * Der Vordergrunddienst muss auch dann senden können, wenn das Gerät seit
 * Stunden in der Tasche liegt und gesperrt ist. Ein Schlüssel, der die
 * Bildschirmsperre verlangt, hielte die Aufzeichnung im Einsatz an — genau
 * dann, wenn niemand ein Handy entsperrt.
 */
class KeystoreTresorschluessel(
    private val name: String = STANDARDNAME,
) : GcmTresorschluessel() {

    override fun schluessel(): SecretKey {
        val ks = KeyStore.getInstance(KEYSTORE).apply { load(null) }
        (ks.getEntry(name, null) as? KeyStore.SecretKeyEntry)?.let { return it.secretKey }

        val erzeuger = KeyGenerator.getInstance(KeyProperties.KEY_ALGORITHM_AES, KEYSTORE)
        erzeuger.init(
            KeyGenParameterSpec.Builder(
                name,
                KeyProperties.PURPOSE_ENCRYPT or KeyProperties.PURPOSE_DECRYPT,
            )
                .setBlockModes(KeyProperties.BLOCK_MODE_GCM)
                .setEncryptionPaddings(KeyProperties.ENCRYPTION_PADDING_NONE)
                .setKeySize(SCHLUESSEL_BITS)
                .setUserAuthenticationRequired(false)
                .build()
        )
        return erzeuger.generateKey()
    }

    companion object {
        const val KEYSTORE = "AndroidKeyStore"
        const val STANDARDNAME = "nadoku-tresor"
    }
}
