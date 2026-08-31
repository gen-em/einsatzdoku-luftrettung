package org.genem.nadoku.handy

import android.app.Application
import android.content.Context
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.handy.dienst.Kennungen
import org.genem.nadoku.handy.puffer.Puffer

/**
 * Die eine Stelle, an der die langlebigen Teile der App wohnen.
 *
 * WARUM ES SIE GIBT. Oberfläche und Vordergrunddienst arbeiten am **selben**
 * Dienst: Der Dienst zeichnet auf, während die Oberfläche längst beendet ist,
 * und die Oberfläche zeigt an, was der Dienst gerade tut. Zwei Exemplare von
 * [Dienstklammer] oder [Puffer] wären zwei Wahrheiten über einen Dienst — und
 * SQLite ließe beide gleichzeitig schreiben.
 *
 * Der Zustand selbst liegt trotzdem **nicht hier**, sondern im Puffer
 * (siehe [Dienstklammer]). Diese Klasse hält nur die Zugänge dorthin offen.
 */
class NAdokuApp : Application() {

    val puffer: Puffer by lazy { Puffer(this) }

    val einstellungen: Einstellungen by lazy { Einstellungen(this) }

    val ausduenner: Ausduenner by lazy { Ausduenner() }

    val klammer: Dienstklammer by lazy {
        Dienstklammer(
            puffer = puffer,
            kennungen = Kennungen(einstellungen.kennungszaehler()),
            ausduenner = ausduenner,
        )
    }

    companion object {
        fun von(kontext: Context): NAdokuApp =
            kontext.applicationContext as NAdokuApp
    }
}
