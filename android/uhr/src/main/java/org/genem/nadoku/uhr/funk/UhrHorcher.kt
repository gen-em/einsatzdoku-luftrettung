package org.genem.nadoku.uhr.funk

import com.google.android.gms.wearable.MessageEvent
import com.google.android.gms.wearable.WearableListenerService
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.genem.nadoku.uhr.UhrApp

/**
 * Was vom Handy hereinkommt (E-S4-10) — **die dünne Hülle auf der Uhr**.
 *
 * Sie entscheidet nichts: lesen, weiterreichen, fertig. Das System startet sie
 * auch dann, wenn keine Ansicht läuft — genau darum kommt eine Quittung an,
 * während die App geschlossen ist, und der Puffer räumt sich auf.
 *
 * **Nicht belegt** (E-R45-7, E-R45-8): dass diese Klasse überhaupt gerufen
 * wird. Der Data Layer ist im Container nicht ausführbar. Belegt ist alles
 * dahinter — [Uhrsteuerung] und [Uhrfunk] gegen eine Attrappe.
 */
class UhrHorcher : WearableListenerService() {

    override fun onMessageReceived(ereignis: MessageEvent) {
        val app = UhrApp.von(this)
        when (ereignis.path) {
            Nachrichtenformat.PFAD_QUITTUNG ->
                Nachrichtenformat.liesQuittung(ereignis.data)?.let { app.quittung(it) }

            Nachrichtenformat.PFAD_STAND ->
                Nachrichtenformat.liesStand(ereignis.data)?.let { app.stand(it) }

            /* Ein fremder Pfad wird STILL fallengelassen. Er kann von einer
             * neueren Fassung der Handy-App stammen; ein Absturz dafür wäre
             * die schlechteste aller Antworten. */
            else -> Unit
        }
    }
}
