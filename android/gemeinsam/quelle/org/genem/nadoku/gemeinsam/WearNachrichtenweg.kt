package org.genem.nadoku.gemeinsam

import android.content.Context
import android.util.Log
import com.google.android.gms.tasks.Tasks
import com.google.android.gms.wearable.Wearable
import java.util.concurrent.TimeUnit

/**
 * Der Data Layer — **die dünne Hülle** (E-S4-10).
 *
 * HIER IST DIE GRENZE DES BELEGTEN. Alles oberhalb dieser Klasse — Puffer,
 * Quittung, Nachlieferung, Doppelzustellung, die Buchführung am Handy — läuft
 * auf der JVM und ist geprüft. Was diese Klasse tut, ist im Container **nicht**
 * ausführbar: Der Data Layer braucht zwei gekoppelte Geräte, die
 * Play-Dienste und eine Bluetooth-Strecke. Es gibt weder Uhr (E-R45-7) noch
 * Emulator (E-R45-8).
 *
 * DESHALB IST SIE SO KLEIN WIE MÖGLICH: Knoten holen, senden, Erfolg melden.
 * Keine Entscheidung, keine Zustandsführung, kein Zwischenspeicher — sonst
 * läge ungeprüfte Logik in einer Klasse, die niemand ausführen kann. Was hier
 * schiefgeht, geht **sichtbar** schief: `false` heißt „nicht zugestellt", und
 * die Schicht darüber liefert nach.
 *
 * SIE BLOCKIERT. `Tasks.await` wartet auf das Ergebnis, und das gehört nicht
 * auf den Hauptthread. Die Aufrufer sind ein `WearableListenerService`
 * (eigener Thread) und der Sendeweg der Uhr; beide rufen aus dem Hintergrund.
 *
 * KEINE ZIELWAHL: Gesendet wird an **alle** verbundenen Knoten. Ein Handy hat
 * höchstens eine gekoppelte Uhr dieser App, und die Nachricht an einen
 * fremden Knoten wäre folgenlos — er hat die App nicht. Eine Auswahl nach
 * Fähigkeiten (`CapabilityClient`) wäre zusätzliche ungeprüfte Logik an der
 * Stelle, an der am wenigsten davon liegen soll.
 */
class WearNachrichtenweg(
    private val kontext: Context,
    private val wartezeitS: Long = WARTEZEIT_S,
) : Nachrichtenweg {

    override fun sende(pfad: String, rumpf: ByteArray): Boolean = try {
        val knoten = Tasks.await(
            Wearable.getNodeClient(kontext).connectedNodes, wartezeitS, TimeUnit.SECONDS,
        )
        var zugestellt = 0
        for (k in knoten) {
            try {
                Tasks.await(
                    Wearable.getMessageClient(kontext).sendMessage(k.id, pfad, rumpf),
                    wartezeitS, TimeUnit.SECONDS,
                )
                zugestellt += 1
            } catch (e: Exception) {
                Log.i(MARKE, "Knoten ${k.id} nicht erreicht: ${e.javaClass.simpleName}")
            }
        }
        zugestellt > 0
    } catch (e: Exception) {
        /* KEIN ABSTURZ AN DIESER STELLE. Ohne Play-Dienste, ohne gekoppelte
         * Uhr oder mit abgeschaltetem Bluetooth wirft der Data Layer — und
         * das ist der Normalfall am Rand des Funklochs, kein Programmfehler.
         * Gemeldet wird „nicht zugestellt"; die Nachricht bleibt gepuffert. */
        Log.i(MARKE, "Data Layer nicht erreichbar: ${e.javaClass.simpleName}")
        false
    }

    private companion object {
        const val MARKE = "NAdokuFunk"

        /**
         * Fünf Sekunden. Länger zu warten hilft nicht: Ist die Uhr in
         * Reichweite, antwortet der Data Layer in Millisekunden; ist sie es
         * nicht, antwortet er gar nicht. Die Wartezeit ist nur die Schranke
         * gegen einen Aufruf, der nie zurückkommt.
         */
        const val WARTEZEIT_S = 5L
    }
}
