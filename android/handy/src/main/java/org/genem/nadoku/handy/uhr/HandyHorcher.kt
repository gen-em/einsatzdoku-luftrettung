package org.genem.nadoku.handy.uhr

import android.util.Log
import com.google.android.gms.wearable.MessageEvent
import com.google.android.gms.wearable.WearableListenerService
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.gemeinsam.WearNachrichtenweg
import org.genem.nadoku.handy.NAdokuApp
import org.genem.nadoku.handy.aufzeichnung.AufzeichnungsDienst

/**
 * Was von der Uhr hereinkommt — **die dünne Hülle am Handy**.
 *
 * Sie liest, reicht an [Uhrannahme] weiter und antwortet zweimal: mit der
 * Quittung und mit dem Anzeigestand. Entschieden wird hier nichts — auch
 * nicht, ob der Absender die eigene Uhr ist: Die
 * Knotenliste kommt vom Data Layer, die Entscheidung aus [Uhrannahme].
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
 * **Nicht belegt**: dass diese Klasse gerufen wird.
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

        val weg = WearNachrichtenweg(this)

        /* WER SCHICKT DAS? Der Data
         * Layer stellt nur zwischen Apps gleichen Pakets und gleicher
         * Signatur zu — das ist der erste Boden. Der zweite: Der Absender
         * muss unter den verbundenen Knoten stehen. Steht er dort nicht,
         * gibt es weder Wirkung noch Quittung; eine echte Uhr liefert nach,
         * sobald sie verbunden ist. Entschieden wird das in `Uhrannahme`,
         * hier wird nur gelesen und gefragt. */
        val absender = Absender(ereignis.sourceNodeId, weg.verbundeneKnoten())
        if (!annahme.absenderBekannt(absender)) {
            Log.w(MARKE, "Ereignis von unbekanntem Knoten ${ereignis.sourceNodeId} verworfen")
            return
        }

        /* VOR dem Wirken gelesen — danach ist es zu spät. */
        val liefVorher = app.klammer.laeuft()
        val quittung = annahme.uebernimm(meldung)
        val laeuftNachher = app.klammer.laeuft()

        weg.sende(Nachrichtenformat.PFAD_QUITTUNG, Nachrichtenformat.schreibe(quittung))
        weg.sende(Nachrichtenformat.PFAD_STAND, Nachrichtenformat.schreibe(annahme.stand()))

        /* WAS MIT DEM VORDERGRUNDDIENST GESCHIEHT, entscheidet eine reine
         * Funktion. Nur `starten` genügt nicht — ein Dienstende von der Uhr
         * beendete ihn dann nie: Ortung liefe weiter, kein DIENSTENDE-Lauf,
         * und das Web sähe den Dienst bis zum nächsten Takt als laufend.
         *
         * Der `try` um `starten()` ist kein Beiwerk: Ab Android 12 ist
         * `startForegroundService` aus dem Hintergrund beschränkt, und ob
         * der Weg über einen `WearableListenerService` unter eine Ausnahme
         * fällt, ist nicht belegt. Fliegt sie, ist die
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
