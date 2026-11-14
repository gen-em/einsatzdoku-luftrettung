package org.genem.nadoku.handy.senden

import org.json.JSONObject
import java.io.File
import javax.crypto.Cipher
import javax.crypto.KeyAgreement
import javax.crypto.Mac
import javax.crypto.spec.IvParameterSpec
import javax.crypto.spec.SecretKeySpec

/**
 * Öffnen einer Gerätehülle der Fassung 2 — nur im Prüffall.
 *
 * Die App öffnet keine Hülle; das tut der Browser. Die Prüffälle brauchen
 * es trotzdem: Ob ein Stück den Merker SCHLUSS trägt oder eine Einsatzhülle
 * ihren Zähler, steht INNEN, und nur das Öffnen zeigt es. Der Schlüssel ist
 * das Konto der Referenzvektoren (`tools/proben/huelle/vektoren-2.json`),
 * die Rechnung die von `oeffnen2()` in `referenz.py`. Eine Stelle für
 * `Geraetehuelle2Test` und `SendekoerperTest` (seit KA-06c).
 */
object Huellenoeffner {

    private val v: JSONObject by lazy {
        // Arbeitsverzeichnis der Prüffälle ist android/handy (Gradle).
        JSONObject(File("../../tools/proben/huelle/vektoren-2.json").readText())
    }

    fun hex(s: String): ByteArray =
        ByteArray(s.length / 2) { s.substring(it * 2, it * 2 + 2).toInt(16).toByte() }

    /** Der öffentliche Schlüssel des Kontos der Vektoren, SEC1. */
    val konto: ByteArray get() = hex(v.getJSONObject("konto").getString("oeffentlich_hex"))

    fun oeffnen2(h: ByteArray, spk: ByteArray, art: String, ref: String, platz: String): ByteArray {
        val privat = Geraetehuelle.privat(hex(v.getJSONObject("konto").getString("privat_hex")))
        val epk = h.copyOfRange(9, 74)
        val iv = h.copyOfRange(74, 90)
        val ct = h.copyOfRange(90, h.size - 32)
        fun ecdh(gegen: ByteArray) = KeyAgreement.getInstance("ECDH").run {
            init(privat); doPhase(Geraetehuelle.oeffentlich(gegen), true); generateSecret()
        }
        val okm = Geraetehuelle.hkdf(ecdh(epk) + ecdh(spk), epk, "nadoku-huelle-2".toByteArray() + konto + spk)
        val tag = Mac.getInstance("HmacSHA256").run {
            init(SecretKeySpec(okm.copyOfRange(32, 64), "HmacSHA256"))
            doFinal(h.copyOfRange(0, h.size - 32) + Geraetehuelle.bindung(art, ref, platz))
        }
        check(tag.contentEquals(h.copyOfRange(h.size - 32, h.size))) { "pruefwert" }
        return Cipher.getInstance("AES/CBC/PKCS5Padding").run {
            init(Cipher.DECRYPT_MODE, SecretKeySpec(okm.copyOfRange(0, 32), "AES"), IvParameterSpec(iv))
            doFinal(ct)
        }
    }
}
