package org.genem.nadoku.handy.kopplung

import java.security.MessageDigest
import java.security.SecureRandom

/**
 * Die Rechnung der Kopplung mit Vergleichswert (JSON-Vertrag 1a.7).
 *
 * WOFÜR. Bei der Kopplung tauschen Handy und Konto ihre öffentlichen
 * Schlüssel über den Server — und wer den Server beschreibt, könnte beide
 * tauschen. Der Vergleichswert ist der zweite Kanal: sechs Ziffern, die das
 * Handy und die Webseite zeigen und die ein Mensch vergleicht. Sie hängen an
 * beiden Schlüsseln und an zwei Zufallszahlen, je einer von jeder Seite.
 *
 * WARUM SECHS ZIFFERN REICHEN: weil sie niemand vorab kennt. Das Handy legt
 * sich mit der [zusage] auf seine Zufallszahl `N_g` fest, BEVOR es die des
 * Browsers (`N_b`) sieht, und legt `N_g` erst offen, wenn Kontoschlüssel und
 * `N_b` da sind. Wer dazwischensitzt, muss seine Schlüssel wählen, ohne den
 * Wert ausrechnen zu können, und trifft je Versuch mit eins zu einer Million
 * — sichtbar als falscher Wert. **Die Reihenfolge ist deshalb Teil der
 * Rechnung:** Wer `N_g` vor dem Festlegen herausgibt, hat die Zusage
 * entwertet. Sie zu halten ist Sache der Ablage
 * ([org.genem.nadoku.handy.tresor.Geraeteschluessel.festlegen]); hier steht
 * nur, was gerechnet wird.
 *
 * DIE WAHRHEIT steht im Kopf von `tools/proben/huelle/referenz.py` („DER
 * VERGLEICHSWERT DER KOPPLUNG", `kopplung_zusage`, `kopplung_wert`) und nur
 * dort; `KopplungswertTest` hält diese Datei gegen den Block `kopplung` der
 * Vektoren. Die Zusage PRÜFT das Handy nicht — das tut der Browser, gegen
 * die Abschrift des Geräteschlüssels, die er vor seinem `N_b` gesehen hat.
 */
object Kopplungswert {

    const val NONCE_LAENGE = 16
    private val ZUSAGE_INFO = "nadoku-kopplung-zusage-1".toByteArray(Charsets.UTF_8)
    private val WERT_INFO = "nadoku-kopplung-wert-1".toByteArray(Charsets.UTF_8)

    /** `N_g`: 16 Byte aus dem Zufallsgenerator der Plattform. */
    fun zufall(): ByteArray = ByteArray(NONCE_LAENGE).also { SecureRandom().nextBytes(it) }

    private fun sha256(vararg teile: ByteArray): ByteArray =
        MessageDigest.getInstance("SHA-256").run { teile.forEach { update(it) }; digest() }

    /** Was bei `start` mitgeht: SHA-256("nadoku-kopplung-zusage-1" ‖ N_g ‖ SPK), 32 Byte. */
    fun zusage(nG: ByteArray, spk: ByteArray): ByteArray {
        require(nG.size == NONCE_LAENGE && spk.size == 65) { "laenge" }
        return sha256(ZUSAGE_INFO, nG, spk)
    }

    /**
     * Der Vergleichswert: die ersten vier Byte von
     * SHA-256("nadoku-kopplung-wert-1" ‖ K ‖ SPK ‖ N_g ‖ N_b) als
     * vorzeichenlose Zahl, modulo eine Million, sechs Ziffern mit führenden
     * Nullen.
     *
     * @param kontoschluessel K, der öffentliche Schlüssel des Kontos (SEC1, 65 Byte)
     * @param spk der öffentliche Teil des Gerätepaars (SEC1, 65 Byte)
     */
    fun wert(kontoschluessel: ByteArray, spk: ByteArray, nG: ByteArray, nB: ByteArray): String {
        require(kontoschluessel.size == 65 && spk.size == 65 && nG.size == NONCE_LAENGE && nB.size == NONCE_LAENGE) {
            "laenge"
        }
        val h = sha256(WERT_INFO, kontoschluessel, spk, nG, nB)
        var n = 0L
        for (i in 0 until 4) n = (n shl 8) or (h[i].toLong() and 0xFF)
        return (n % 1_000_000L).toString().padStart(6, '0')
    }
}
