package org.genem.nadoku.handy.uhr

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
        val quittung = annahme.uebernimm(meldung)

        val weg = WearNachrichtenweg(this)
        weg.sende(Nachrichtenformat.PFAD_QUITTUNG, Nachrichtenformat.schreibe(quittung))
        weg.sende(Nachrichtenformat.PFAD_STAND, Nachrichtenformat.schreibe(annahme.stand()))

        if (app.klammer.laeuft()) AufzeichnungsDienst.starten(this)
    }
}
