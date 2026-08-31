package org.genem.nadoku.handy.kopplung

import android.graphics.ImageFormat
import androidx.camera.core.ImageProxy
import com.google.zxing.BinaryBitmap
import com.google.zxing.DecodeHintType
import com.google.zxing.NotFoundException
import com.google.zxing.PlanarYUVLuminanceSource
import com.google.zxing.common.HybridBinarizer
import com.google.zxing.qrcode.QRCodeReader

/**
 * Ein Kamerabild nach einem QR-Code absuchen (E-S4-15).
 *
 * NUR QR, kein allgemeiner Barcode-Leser: `QRCodeReader` statt
 * `MultiFormatReader`. Der Unterschied ist nicht die Rechenzeit, sondern die
 * Aussage — die App sucht genau eine Sache, und ein EAN-Code auf einer
 * Verpackung soll nicht erst gelesen und dann verworfen werden.
 *
 * DIE HELLIGKEITSEBENE GENÜGT. Ein QR-Code ist schwarzweiß; die Farbebenen des
 * Kamerabilds tragen nichts bei. CameraX liefert YUV_420_888, dessen erste
 * Ebene genau diese Helligkeitswerte sind — sie wird ohne Umrechnung
 * weitergereicht.
 *
 * DER SCHNITT WIRD NICHT WIEDERVERWENDET, UND DAS IST ABSICHT: `QRCodeReader`
 * ist nicht mehrfachbenutzbar ohne `reset()`, und ein Zustand, der über
 * Einzelbilder hinweg lebt, ist bei dreißig Bildern je Sekunde die erste
 * Stelle, an der etwas hängenbleibt.
 */
object QrLeser {

    private val HINWEISE = mapOf<DecodeHintType, Any>(
        // Genauer suchen. Die Kamera hält niemand still, und ein zweiter
        // Durchlauf über dasselbe Bild ist billiger als ein weiteres Bild.
        DecodeHintType.TRY_HARDER to true,
        DecodeHintType.CHARACTER_SET to "UTF-8",
    )

    /**
     * @return der Textinhalt des gefundenen QR-Codes oder `null` — kein
     *   Fund ist beim Zielen der Normalfall, kein Fehler.
     */
    fun lese(bild: ImageProxy): String? {
        if (bild.format != ImageFormat.YUV_420_888 && bild.format != ImageFormat.YUV_422_888 &&
            bild.format != ImageFormat.YUV_444_888
        ) {
            return null
        }

        val ebene = bild.planes[0]
        val puffer = ebene.buffer
        val bytes = ByteArray(puffer.remaining())
        puffer.get(bytes)

        return lese(bytes, ebene.rowStride, bild.height)
    }

    /**
     * Dieselbe Arbeit auf rohen Helligkeitswerten — damit sie ohne Kamera
     * prüfbar ist.
     *
     * @param zeilenbreite Bytes je Bildzeile. Sie ist NICHT immer gleich der
     *   Bildbreite: Kameras füllen Zeilen auf ein Vielfaches auf. Wer hier die
     *   Breite einsetzt, bekommt ein schräg verzogenes Bild und findet nie
     *   einen Code — ein Fehler, der wie „die Erkennung ist schlecht" aussieht.
     */
    fun lese(helligkeit: ByteArray, zeilenbreite: Int, hoehe: Int): String? {
        if (zeilenbreite <= 0 || hoehe <= 0) return null
        if (helligkeit.size < zeilenbreite * hoehe) return null

        val quelle = PlanarYUVLuminanceSource(
            helligkeit, zeilenbreite, hoehe,
            0, 0, zeilenbreite, hoehe,
            false,
        )
        return try {
            QRCodeReader().decode(BinaryBitmap(HybridBinarizer(quelle)), HINWEISE).text
        } catch (e: NotFoundException) {
            null            // nichts im Bild — der Normalfall
        } catch (e: Exception) {
            null            // unlesbar, verwackelt, halb im Bild
        }
    }
}
