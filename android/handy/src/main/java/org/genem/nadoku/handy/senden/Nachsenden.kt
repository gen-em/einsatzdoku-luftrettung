package org.genem.nadoku.handy.senden

import android.app.job.JobInfo
import android.app.job.JobScheduler
import android.content.ComponentName
import android.content.Context
import android.util.Log

/**
 * Das Nachsenden nach dem Dienstende (E-S5Z-09) — **Planung und Entscheidung**.
 *
 * DER FEHLER, GEGEN DEN ES GEBAUT IST, ist belegt (Diagnose 1.3, H2/Kette B1):
 * Ein Dienstende, dessen Sendelauf nicht durchkam, wurde **nie wiederholt**.
 * `spaeterErneut` hiess bis 0.8.1 „später" ohne ein Später: Ausserhalb eines
 * laufenden Dienstes gab es keinen Zeitgeber, der noch einmal angeklopft
 * hätte. Erst der **nächste** Dienst sendete wieder — sein erster Takt kommt
 * eine Viertelstunde nach dem Start. Bis dahin stand der Diensttag im Web
 * ohne Ende und das letzte Ruhesegment „–offen".
 *
 * WARUM `JobScheduler` UND NICHT `WorkManager`. Der Planer ist ein Bordmittel
 * und kostet keine Abhängigkeit; `WorkManager` wäre eine, gehörte nach
 * `docs/Lizenzen.md` und setzt intern ohnehin auf denselben Planer. Was er
 * darüber hinaus könnte — verkettete Arbeit, Beobachtung des Fortschritts —
 * braucht hier niemand.
 *
 * WAS DER JOB NICHT TUT: einen laufenden Dienst anfassen, eine Rückfrage
 * stellen, etwas löschen. Er sendet, was liegt, und plant sich nach, wenn
 * etwas liegen bleibt.
 *
 * WAS ER BRAUCHT: ein **entsperrtes** Gerät nach einem Neustart. Die Ablage
 * der Zugangsdaten liegt in der anmeldungsgeschützten Speicherung, und der
 * Planer startet Jobs für Apps ohne Direct-Boot-Kennzeichnung erst nach dem
 * ersten Entsperren. Das ist richtig so und wird nicht umgangen.
 */
object Nachsenden {

    /**
     * Feste Kennung — `schedule()` mit derselben ersetzt den vorhandenen Job.
     * Damit ist [einplanen] von selbst mehrfach aufrufbar, ohne dass sich
     * Jobs stapeln.
     */
    const val JOB_ID = 1

    /** Z-S5Z-06: `JobInfo.DEFAULT_INITIAL_BACKOFF_MILLIS`. Die Plattform deckelt bei 5 h. */
    const val BACKOFF_MS = 30_000L

    private const val MARKE = "NAdoku"

    /**
     * **Die Entscheidung, ob nachgesendet werden muss** — reine Rechnung,
     * ohne Android, und deshalb prüfbar.
     *
     * @param bericht der gerade beendete Sendelauf, oder `null` beim
     *   App-Start (dann hat gerade keiner stattgefunden).
     *
     *   Der Unterschied ist keine Feinheit: Nach einem **Lauf** wird nur
     *   geplant, wenn er mit `spaeterErneut` endete — ein Lauf, der sauber
     *   durchlief und trotzdem Rückstand hinterlässt, hat gerade erst alles
     *   versucht, was ging. Beim **App-Start** dagegen ist jeder Rückstand
     *   Grund genug: Der Prozess kann gestorben sein, bevor der Job geplant
     *   wurde, und dann wartet die Nachlieferung sonst auf den nächsten
     *   Dienst.
     * @param rueckstand abgeschlossene, nicht vollständig bestätigte Pakete
     * @param dienstLaeuft läuft gerade ein Dienst? Dann sendet der Takt, und
     *   ein zweiter Weg auf denselben Puffer wäre nur ein zweiter Weg.
     */
    fun planen(bericht: Sendebericht?, rueckstand: Int, dienstLaeuft: Boolean): Boolean {
        if (dienstLaeuft) return false
        if (rueckstand <= 0) return false
        /* 401: Der Schlüssel ist abgewiesen. Wiederholen hilft nicht — es
         * hilft nur eine neue Kopplung, und die kann nur ein Mensch. Ein Job,
         * der es trotzdem alle 30 Sekunden versucht, verbrennt Akku und
         * erzeugt Serverlast für ein sicheres Nein. */
        if (bericht?.pausiert == true) return false
        if (bericht != null && !bericht.spaeterErneut) return false
        return true
    }

    /**
     * Den Job einplanen (oder den vorhandenen ersetzen).
     *
     * @param ueberNeustart `setPersisted` — braucht `RECEIVE_BOOT_COMPLETED`
     *   (E-S5Z-19). Nur der Weg über das Dienstende und den App-Start setzt
     *   es; der Job selbst plant sich über den Rückgabewert von `jobFinished`
     *   nach und braucht es dort nicht.
     */
    fun einplanen(kontext: Context, ueberNeustart: Boolean = true) {
        val planer = kontext.getSystemService(Context.JOB_SCHEDULER_SERVICE) as JobScheduler
        val job = JobInfo.Builder(JOB_ID, ComponentName(kontext, NachsendeDienst::class.java))
            /* NETWORK_TYPE_ANY und nicht UNMETERED: Ein Diensttag gehört
             * hochgeladen, auch wenn dabei ein paar hundert Kilobyte
             * Mobilfunk anfallen. Die Alternative wäre, auf WLAN zu warten —
             * und das kann bis zum Feierabend dauern. */
            .setRequiredNetworkType(JobInfo.NETWORK_TYPE_ANY)
            .setPersisted(ueberNeustart)
            .setBackoffCriteria(BACKOFF_MS, JobInfo.BACKOFF_POLICY_EXPONENTIAL)
            .build()
        val ergebnis = planer.schedule(job)
        Log.i(
            MARKE,
            "Nachsende-Job geplant (persist=$ueberNeustart): " +
                if (ergebnis == JobScheduler.RESULT_SUCCESS) "angenommen" else "ABGELEHNT",
        )
    }

    /** Den Job zurückziehen — es liegt nichts mehr an. */
    fun abbrechen(kontext: Context) {
        (kontext.getSystemService(Context.JOB_SCHEDULER_SERVICE) as JobScheduler).cancel(JOB_ID)
    }
}
