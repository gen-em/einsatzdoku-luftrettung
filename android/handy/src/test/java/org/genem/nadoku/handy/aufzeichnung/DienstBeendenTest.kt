package org.genem.nadoku.handy.aufzeichnung

import android.content.Intent
import android.os.Looper
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.handy.NAdokuApp
import org.genem.nadoku.handy.puffer.Paketzeile
import org.junit.Assert.assertFalse
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.Robolectric
import org.robolectric.RobolectricTestRunner
import org.robolectric.Shadows.shadowOf

/**
 * „Dienst beenden" aus einer Benachrichtigung schließt den Dienst im Puffer
 * (F-R17-54, R17-24).
 *
 * DIE LAGE. Der Knopf in der Akkuwarnung (ab 15 %) und in der Erinnerung
 * nach 26 Stunden ist eine `PendingIntent` mit der Aktion
 * [AufzeichnungsDienst.BEENDEN] an den Vordergrunddienst. Bis 0.18.0 rief
 * der darauf nur sein eigenes `beenden()` — Ortung ab, Sendelauf,
 * `stopSelf()` —, und die Klammer blieb offen: Die Hauptansicht zeigte
 * danach „Dienst läuft" ohne Aufzeichnung, und der nächste „Dienst beginnen"
 * setzte den alten fort. Die zwei anderen Wege (Rückfrage der Hauptansicht,
 * Dienstende von der Uhr) schließen die Klammer vorher selbst; deshalb sah
 * kein Prüffall die Lücke — `LebenszyklusTest` und `DienstklammerTest`
 * prüfen die Klammer, keiner schickte die Aktion an den Dienst.
 *
 * DIESER FALL TUT GENAU DAS, was die Benachrichtigung tut: Er schickt den
 * Intent an `onStartCommand()` eines unter Robolectric erzeugten Dienstes
 * und sieht danach in den Puffer. Gegen 0.18.0 ist er rot.
 */
@RunWith(RobolectricTestRunner::class)
class DienstBeendenTest {

    private val app: NAdokuApp = ApplicationProvider.getApplicationContext()

    private fun beendenAusDerBenachrichtigung() {
        val intent = Intent(app, AufzeichnungsDienst::class.java)
            .setAction(AufzeichnungsDienst.BEENDEN)
        Robolectric.buildService(AufzeichnungsDienst::class.java, intent)
            .create()
            .startCommand(0, 1)
        shadowOf(Looper.getMainLooper()).idle()
    }

    /** **Der Knopf schließt Dienst und offenes Ruhesegment im Puffer.** */
    @Test fun knopfInDerBenachrichtigungSchliesstDenDienst() {
        app.klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertTrue("Vorbedingung: der Dienst läuft", app.klammer.laeuft())
        assertNotNull("Vorbedingung: das erste Ruhesegment ist offen",
            app.puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))

        beendenAusDerBenachrichtigung()

        assertFalse("Nach „Dienst beenden“ läuft kein Dienst mehr", app.klammer.laeuft())
        assertNull("…und im Puffer steht kein laufender Dienst", app.puffer.laufenderDienst())
        assertNull("…und kein offenes Ruhesegment",
            app.puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT))
    }

    /** **Ein offener Einsatz geht mit** — das Sicherheitsnetz der Klammer. */
    @Test fun knopfSchliesstAuchDenOffenenEinsatz() {
        app.klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertTrue("Vorbedingung: Phase 2 öffnet einen Einsatz", app.klammer.phaseSetzen(2))
        assertNotNull(app.puffer.offenesPaket(Paketzeile.ART_EINSATZ))

        beendenAusDerBenachrichtigung()

        assertNull("Der Einsatz ist geschlossen", app.puffer.offenesPaket(Paketzeile.ART_EINSATZ))
        assertNull(app.puffer.laufenderDienst())
    }

    /**
     * **Wer die Klammer schon geschlossen hat, schließt sie nicht zweimal.**
     * Die Rückfrage der Hauptansicht und die Uhr rufen erst
     * `klammer.beenden()` und dann dieselbe Aktion; die bleibt dann ohne
     * Folgen für den Puffer.
     */
    @Test fun zweiterAufrufNachDerKlammerIstOhneFolgen() {
        app.klammer.beginnen(Modus.MIT_PHASENKNOEPFEN)
        assertTrue(app.klammer.beenden())

        beendenAusDerBenachrichtigung()

        assertNull(app.puffer.laufenderDienst())
        assertFalse("Ein zweites Beenden meldet nichts", app.klammer.beenden())
    }
}
