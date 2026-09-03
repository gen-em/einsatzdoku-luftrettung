package org.genem.nadoku.handy.uhr

import android.util.Log
import com.google.android.gms.wearable.MessageEvent
import com.google.android.gms.wearable.WearableListenerService
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.WearNachrichtenweg
import org.genem.nadoku.handy.NAdokuApp
import org.genem.nadoku.handy.aufzeichnung.AufzeichnungsDienst

/**
 * Was von der Uhr hereinkommt (E-S4-10) — **die dünne Hülle am Handy**.
 *
 * Sie liest, reicht an [Uhrannahme] weiter und antwortet zweimal: mit der
 * Quittung und mit dem Anzeigestand. Entschieden wird hier nichts.
 *
 * DIE QUITTUNG GEHT AUCH BEI EINER DOPPELZUSTELLUNG ZURÜCK. Genau dann ist
 * sie am wichtigsten: Die Uhr liefert nach, weil die erste Quittung verloren
 * ging — bliebe die zweite aus, täte sie es für immer.
 *
 * DER VORDERGRUNDDIENST WIRD HIER GESTARTET und nicht in der Oberfläche: Ein
 * an der Uhr ausgelöster Dienststart soll GPS anwerfen, auch wenn das Handy
 * in der Tasche liegt und niemand die App geöffnet hat. Ohne diese Zeile
 * stünde in der Uhr „Dienst läuft" und es liefe keine Aufzeichnung.
 *
 * **Nicht belegt** (E-R45-7, E-R45-8): dass diese Klasse gerufen wird.
 */
class HandyHorcher : WearableListenerService() {

    override fun onMessageReceived(ereignis: MessageEvent) {
        if (ereignis.path != Nachrichtenformat.PFAD_EREIGNIS) return
        val meldung = Nachrichtenformat.liesMeldung(ereignis.data) ?: return

        val app = NAdokuApp.von(this)
        val annahme = Uhrannahme(
            app.puffer,
            app.klammer,
            modus = { app.einstellungen.letzterModus },
            ortung = { app.ortung?.stand?.code },
        )

        /* VOR dem Wirken gelesen — danach ist es zu spät (E-S5Z-08). */
        val liefVorher = app.klammer.laeuft()
        val quittung = annahme.uebernimm(meldung)
        val laeuftNachher = app.klammer.laeuft()

        val weg = WearNachrichtenweg(this)
        weg.sende(Nachrichtenformat.PFAD_QUITTUNG, Nachrichtenformat.schreibe(quittung))
        weg.sende(Nachrichtenformat.PFAD_STAND, Nachrichtenformat.schreibe(annahme.stand()))

        /* WAS MIT DEM VORDERGRUNDDIENST GESCHIEHT, entscheidet eine reine
         * Funktion (E-S5Z-08). Hier stand bis 0.8.1 nur `starten` — ein
         * Dienstende von der Uhr beendete ihn nie: Ortung lief weiter, kein
         * DIENSTENDE-Lauf, und das Web sah den Dienst bis zum nächsten Takt
         * als laufend (B-S5Z-04).
         *
         * Der `try` um `starten()` ist kein Beiwerk: Ab Android 12 ist
         * `startForegroundService` aus dem Hintergrund beschränkt, und ob
         * der Weg über einen `WearableListenerService` unter eine Ausnahme
         * fällt, ist nicht belegt (B-S5Z-08, B4.2). Fliegt sie, ist die
         * Aufzeichnung nicht gestartet — aber die Quittung ist raus und die
         * App lebt. Der Absturz wäre das schlechtere Ergebnis. */
        when (Dienstfolge.aus(liefVorher, laeuftNachher, AufzeichnungsDienst.steht)) {
            Dienstfolge.STARTEN -> try {
                AufzeichnungsDienst.starten(this)
            } catch (e: IllegalStateException) {
                Log.w(MARKE, "Vordergrunddienst nicht startbar (Hintergrund): ${e.message}")
            }

            Dienstfolge.BEENDEN -> AufzeichnungsDienst.beenden(this)
            Dienstfolge.NICHTS -> Unit
        }
    }

    private companion object {
        const val MARKE = "NAdoku"
    }
}
