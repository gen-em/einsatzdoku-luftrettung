package org.genem.nadoku.handy.senden

import java.math.BigInteger
import java.security.AlgorithmParameters
import java.security.KeyPair
import java.security.MessageDigest
import java.security.spec.ECFieldFp
import java.security.spec.ECGenParameterSpec
import java.security.spec.ECParameterSpec

/**
 * Punktrechnung auf P-256 — nur für Prüffälle.
 *
 * Anlass: Nr. 563 — die Vektoren der Fassung 2 leiten ihre festen Schlüssel
 * aus Namen ab (`schluessel_aus_name()` in `referenz.py`), und manche Fälle
 * nennen den flüchtigen Schlüssel nicht eigens. Um sie byte-gleich
 * nachzubauen, braucht der Prüffall zu einem privaten `d` den öffentlichen
 * Punkt, und den geben die Bordmittel nicht her.
 *
 * LANGSAM UND NICHT GEHEIMNISFEST, mit Absicht schlicht: affine Koordinaten,
 * Verdoppeln und Addieren. In der App hat das nichts verloren — dort erzeugt
 * die Plattform die Paare.
 */
object Kurvenhilfe {

    private val kurve: ECParameterSpec = AlgorithmParameters.getInstance("EC")
        .apply { init(ECGenParameterSpec("secp256r1")) }.getParameterSpec(ECParameterSpec::class.java)
    private val p: BigInteger = (kurve.curve.field as ECFieldFp).p
    private val a: BigInteger = kurve.curve.a
    val ordnung: BigInteger = kurve.order

    private fun plus(p1: Pair<BigInteger, BigInteger>?, p2: Pair<BigInteger, BigInteger>?): Pair<BigInteger, BigInteger>? {
        if (p1 == null) return p2
        if (p2 == null) return p1
        val (x1, y1) = p1
        val (x2, y2) = p2
        if (x1 == x2 && (y1 + y2).mod(p).signum() == 0) return null
        val s = if (x1 == x2) {
            (x1 * x1 * BigInteger.valueOf(3) + a) * (y1 * BigInteger.valueOf(2)).modInverse(p)
        } else {
            (y2 - y1) * (x2 - x1).mod(p).modInverse(p)
        }.mod(p)
        val x3 = (s * s - x1 - x2).mod(p)
        return x3 to (s * (x1 - x3) - y1).mod(p)
    }

    /** `d·G` als SEC1-Punkt (65 Byte). */
    fun punkt(d: BigInteger): ByteArray {
        var erg: Pair<BigInteger, BigInteger>? = null
        var lauf: Pair<BigInteger, BigInteger>? = kurve.generator.affineX to kurve.generator.affineY
        for (i in 0 until d.bitLength()) {
            if (d.testBit(i)) erg = plus(erg, lauf)
            lauf = plus(lauf, lauf)
        }
        val (x, y) = erg!!
        return byteArrayOf(0x04) + Geraetehuelle.fest32(x) + Geraetehuelle.fest32(y)
    }

    /** `schluessel_aus_name()` der Referenz: SHA-256 des Namens, in den gültigen Bereich gebracht. */
    fun dAusName(name: String): BigInteger =
        BigInteger(1, MessageDigest.getInstance("SHA-256").digest(name.toByteArray()))
            .mod(ordnung - BigInteger.ONE) + BigInteger.ONE

    /** Der feste flüchtige Schlüssel eines Vektors der Fassung 2 (`_fest()` der Referenz). */
    fun fluechtig2(name: String): KeyPair {
        val d = dAusName("nadoku-huelle2-vektor-fluechtig-$name")
        return Geraetehuelle.paarAus(Geraetehuelle.fest32(d), punkt(d))
    }

    /** Der feste IV eines Vektors der Fassung 2. */
    fun iv2(name: String): ByteArray =
        MessageDigest.getInstance("SHA-256").digest("nadoku-huelle2-vektor-iv-$name".toByteArray()).copyOfRange(0, 16)
}
