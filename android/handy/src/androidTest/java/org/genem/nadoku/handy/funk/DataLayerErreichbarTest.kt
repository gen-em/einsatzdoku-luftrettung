package org.genem.nadoku.handy.funk

import androidx.test.ext.junit.runners.AndroidJUnit4
import androidx.test.platform.app.InstrumentationRegistry
import com.google.android.gms.common.ConnectionResult
import com.google.android.gms.common.GoogleApiAvailability
import com.google.android.gms.tasks.Tasks
import com.google.android.gms.wearable.Wearable
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import java.util.concurrent.TimeUnit

/**
 * Wie weit trägt der Wear Data Layer ohne ein zweites Gerät? (S4/D2)
 *
 * WOFÜR ES DIESE DATEI GIBT. `android/LIESMICH.md` führte den Data Layer als
 * ungeprüft mit der Begründung, er brauche „zwei gekoppelte Geräte **und** die
 * Play-Dienste; im Container gibt es beides nicht". Die zweite Hälfte davon
 * ist **falsch**: Das Wear-OS-3-Abbild bringt `com.google.android.gms` mit
 * (gemessen: 22.48.14). Dieser Prüffall stellt fest, wie weit es damit
 * tatsächlich trägt — statt es weiter anzunehmen.
 *
 * ER PRÜFT DIE ERREICHBARKEIT, NICHT DIE ZUSTELLUNG. Zustellen kann der Data
 * Layer erst mit einem Gegenüber. Was hier belegt wird, ist die Schicht
 * darunter: dass die Bibliothek antwortet, dass es einen lokalen Knoten mit
 * Kennung gibt, und dass die Liste der verbundenen Knoten leer ist — was ohne
 * gekoppeltes Telefon die richtige Antwort ist.
 *
 * DAMIT WIRD DIE GRENZE MESSBAR statt behauptet: Fällt dieser Prüffall auf
 * einem Gerät durch, liegt es an den Play-Diensten. Läuft er durch und die
 * Zustellung klemmt trotzdem, liegt es an der Kopplung.
 */
@RunWith(AndroidJUnit4::class)
class DataLayerErreichbarTest {

    private val ctx get() = InstrumentationRegistry.getInstrumentation().targetContext

    companion object {
        /* GROSSZUEGIG, UND DAS IST GEMESSEN. Auf einem Emulator ohne KVM
         * braucht GMS lange, bis es antwortet: Sein `phenotype.db` meldete im
         * Lauf vom 02.09.2026 einen 60 Sekunden blockierten Verbindungspool,
         * waehrend es ueber Conscrypt nach Hause telefonierte. Mit 30 s war
         * nicht zu unterscheiden, ob der Dienst langsam ist oder gar nicht
         * antwortet -- und genau das ist die Frage, um die es hier geht. */
        private const val WARTE_S = 120L
    }

    @Test
    fun play_dienste_sind_vorhanden() {
        val stand = GoogleApiAvailability.getInstance().isGooglePlayServicesAvailable(ctx)
        assertEquals(
            "Ohne Play-Dienste gibt es keinen Data Layer. Ergebniscode: $stand",
            ConnectionResult.SUCCESS,
            stand,
        )
    }

    @Test
    fun der_lokale_Knoten_hat_eine_Kennung() {
        /* DAS IST DER EIGENTLICHE BELEG. Antwortet der NodeClient mit einem
         * lokalen Knoten samt Kennung, dann läuft die Wearable-API auf diesem
         * System — die Bibliothek ist eingebunden, der Dienst erreichbar, die
         * Berechtigungen stimmen. Alles, was dann noch fehlt, ist ein
         * Gegenüber. */
        val knoten = Tasks.await(Wearable.getNodeClient(ctx).localNode, WARTE_S, TimeUnit.SECONDS)
        assertNotNull("Kein lokaler Knoten — die Wearable-API antwortet nicht", knoten)
        assertTrue("Der lokale Knoten muss eine Kennung tragen", knoten.id.isNotEmpty())
    }

    @Test
    fun ohne_Gegenueber_ist_die_Knotenliste_leer() {
        /* KEIN FEHLER, SONDERN DIE RICHTIGE ANTWORT. Ohne gekoppeltes Telefon
         * gibt es keinen verbundenen Knoten. Der Fall steht hier, weil er die
         * Grenze festschreibt: Wer diesen Prüffall auf einem gekoppelten Paar
         * laufen lässt, bekommt eine nicht-leere Liste — und genau daran ist
         * zu erkennen, dass die Kopplung steht. */
        val knoten = Tasks.await(Wearable.getNodeClient(ctx).connectedNodes, WARTE_S, TimeUnit.SECONDS)
        assertNotNull(knoten)
        assertEquals(
            "Ohne gekoppeltes Gegenüber darf kein Knoten verbunden sein; " +
                "gefunden: ${knoten.map { it.displayName }}",
            0,
            knoten.size,
        )
    }
}
