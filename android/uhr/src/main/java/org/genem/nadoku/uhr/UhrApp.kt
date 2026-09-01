package org.genem.nadoku.uhr

import android.app.Application
import android.content.Context
import android.os.Handler
import android.os.Looper
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Standmeldung
import org.genem.nadoku.gemeinsam.WearNachrichtenweg
import org.genem.nadoku.uhr.funk.Uhrfunk
import org.genem.nadoku.uhr.funk.Uhrpuffer
import org.genem.nadoku.uhr.funk.Uhrsteuerung
import java.io.File
import java.util.concurrent.Executors

/**
 * Die eine Stelle, an der die langlebigen Teile der Uhr-App wohnen.
 *
 * SIE MUSS DIE ANSICHT ÜBERLEBEN, und zwar aus einem Grund: Der
 * Ereignispuffer und der Zähler gehören zum **Gerät**, nicht zum Bildschirm.
 * Wer die App schließt, während eine Meldung unquittiert ist, darf sie nicht
 * verlieren; und der [UhrHorcher] nimmt Quittungen entgegen, während gar keine
 * Ansicht läuft.
 *
 * ALLES LÄUFT AUF EINEM EIGENEN FADEN, und das ist keine Vorsicht auf Vorrat:
 * `WearNachrichtenweg` wartet bis zu fünf Sekunden auf den Data Layer. Auf dem
 * Hauptfaden hinge die Uhr nach jedem Tippen. Der **eine** Faden ist dabei so
 * wichtig wie der Hintergrund: Zwei Fäden, die gleichzeitig Nummern vergeben
 * und in dieselbe Datei schreiben, erzeugen doppelte Ereignisnummern — genau
 * den Fehler, gegen den der ganze Puffer gebaut ist.
 */
class UhrApp : Application() {

    private val werkbank = Executors.newSingleThreadExecutor()
    private val hauptfaden = Handler(Looper.getMainLooper())

    /** Wer die Anzeige zeichnet — gerufen wird auf dem Hauptfaden. */
    var beobachter: ((Uhrzustand) -> Unit)? = null

    private val puffer: Uhrpuffer by lazy {
        Uhrpuffer(Uhrpuffer.DateiAblage(File(filesDir, PUFFERDATEI)))
    }

    private val funk: Uhrfunk by lazy { Uhrfunk(puffer, WearNachrichtenweg(this)) }

    private val steuerung: Uhrsteuerung by lazy {
        Uhrsteuerung(funk = funk, beiAenderung = { z -> hauptfaden.post { beobachter?.invoke(z) } })
    }

    /** Der zuletzt bekannte Anzeigestand — für eine Ansicht, die neu startet. */
    val zustand: Uhrzustand get() = steuerung.zustand

    fun ereignis(e: Uhrereignis) = werkbank.execute { steuerung.ereignis(e) }

    fun quittung(q: Quittung) = werkbank.execute { steuerung.quittungEingegangen(q) }

    fun stand(s: Standmeldung) = werkbank.execute { steuerung.standEingegangen(s) }

    /**
     * Nachliefern, was noch wartet.
     *
     * Gerufen beim Start der Ansicht: Wer die App öffnet, hat meistens einen
     * Grund, und ein voller Puffer ist einer davon. Ein eigener Wecker dafür
     * wäre ein Dienst auf der Uhr — Akku für einen Fall, den das Handy von
     * sich aus auflöst, sobald es wieder in Reichweite ist.
     */
    fun nachliefern() = werkbank.execute { steuerung.nachliefern() }

    companion object {
        private const val PUFFERDATEI = "uhrpuffer.txt"

        fun von(kontext: Context): UhrApp = kontext.applicationContext as UhrApp
    }
}
