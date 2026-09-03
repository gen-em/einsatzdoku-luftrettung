package org.genem.nadoku.handy.senden

import android.app.job.JobParameters
import android.app.job.JobService
import android.util.Log
import org.genem.nadoku.R
import org.genem.nadoku.handy.NAdokuApp

/**
 * Der Nachsende-Job (E-S5Z-09) — **die dünne Hülle um einen Sendelauf**.
 *
 * Er entscheidet nichts: Ob überhaupt geplant wird, steht in
 * [Nachsenden.planen], und was gesendet wird, weiss der [Sender]. Hier steht
 * nur, wie beides an den Job-Planer angeschlossen ist.
 *
 * `onStartJob` gibt **`true`** zurück und meldet sich später selbst
 * ([android.app.job.JobService.jobFinished]) — der Lauf gehört nicht auf den
 * Hauptfaden, und ein `false` hiesse „schon fertig", bevor irgendetwas
 * gesendet wurde.
 *
 * Eingereicht wird beim **Sendeausführer** (E-S5Z-11) und nicht auf einem
 * eigenen Faden: Damit überlappt der Job nie mit einem Lauf aus der
 * Oberfläche oder aus dem Dienst. Zwei Läufe auf demselben Puffer waren
 * bisher möglich und nur deshalb harmlos, weil der Server idempotent ist
 * (B-S5Z-11) — das ist kein Zustand, auf den man bauen sollte.
 */
class NachsendeDienst : JobService() {

    override fun onStartJob(params: JobParameters): Boolean {
        val app = NAdokuApp.von(this)
        Log.i(MARKE, "Nachsende-Job läuft an (Rückstand ${app.puffer.rueckstand()}).")

        app.sendelauf { bericht ->
            val rest = app.puffer.rueckstand()
            /* KEIN Dienst kann laufen, während dieser Job arbeitet — der Job
             * wird gar nicht erst geplant, solange einer läuft. Der Wert
             * steht trotzdem hier und nicht als `false` in der Zeile: Die
             * Entscheidung gehört an EINE Stelle, sonst driftet sie. */
            val nochmal = Nachsenden.planen(bericht, rest, app.klammer.laeuft())
            if (rest == 0 && !bericht.pausiert) {
                Sendehinweis.loeschen(this)
            } else if (bericht.pausiert) {
                Sendehinweis.stellen(this, getString(R.string.hinweis_schluessel_abgewiesen))
            }
            Log.i(MARKE, "Nachsende-Job fertig: Rückstand $rest, erneut=$nochmal")
            jobFinished(params, nochmal)
        }
        return true
    }

    /**
     * Der Lauf darf abgebrochen werden — er ist wiederholbar.
     *
     * `true` heisst „bitte noch einmal einplanen". Das ist hier richtig: Der
     * Puffer merkt sich, was bestätigt ist, der Server ist idempotent über
     * `client_ref` (Vertrag 2), und ein abgebrochener Lauf hinterlässt
     * deshalb keinen halben Zustand — nur unerledigte Arbeit.
     */
    override fun onStopJob(params: JobParameters): Boolean = true

    private companion object {
        const val MARKE = "NAdoku"
    }
}
