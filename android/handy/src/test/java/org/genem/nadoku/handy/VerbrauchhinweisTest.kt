package org.genem.nadoku.handy

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Der Hinweis auf den Stromverbrauch, einmal je Installation (Backlog Nr. 82).
 *
 * WAS HIER GEPRÜFT WIRD UND WAS NICHT. Geprüft wird der **Merker** — dass er
 * frisch aus ist, gesetzt werden kann und einen neuen Zugriff überlebt. Das
 * ist die Zusicherung „einmal je Installation, nicht bei jedem Dienstbeginn",
 * und sie ist der Kern des Backlog-Punkts („Nicht als Dauerwarnung").
 *
 * NICHT geprüft wird, wie der Dialog aussieht. Der Bilderlauf kann das nicht:
 * Ein Compose-`AlertDialog` rendert in einem eigenen Fenster, und die
 * Bildaufnahme über die Wurzel-Composable sieht davon nichts (gemessen: 1 dp
 * Inhalt, 0 Knöpfe). Am Emulator ist der Akku-Dialog derselben Bauform
 * belegt.
 *
 * ZWEI MERKER, NICHT EINER — und das ist der Grund für diese Klasse. Der
 * Akku-Dialog erscheint nur, wenn die Freistellung noch NICHT steht; wer sie
 * vorher gesetzt hat, sieht ihn nie und bekommt stattdessen diesen hier. Hinge
 * beides an einem Merker, bekäme genau diese Person keinen von beiden.
 */
@RunWith(RobolectricTestRunner::class)
class VerbrauchhinweisTest {

    private lateinit var kontext: Context

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        // Frische Installation nachstellen: Die Ablage ist zwischen den
        // Fällen nicht garantiert leer.
        kontext.getSharedPreferences("nadoku", Context.MODE_PRIVATE)
            .edit().clear().commit()
    }

    @Test fun frischIstDerHinweisNochNichtGezeigt() {
        assertFalse(Einstellungen(kontext).verbrauchHinweisGezeigt)
    }

    @Test fun einmalGesetztBleibtErGesetzt() {
        Einstellungen(kontext).verbrauchHinweisGezeigt = true

        // Neuer Zugriff auf dieselbe Ablage — so, wie ihn ein Neustart der App
        // macht. Ein Merker, der nur im Arbeitsspeicher steht, meldete sich
        // beim nächsten Dienstbeginn wieder.
        assertTrue(Einstellungen(kontext).verbrauchHinweisGezeigt)
    }

    /**
     * DIE BEIDEN MERKER SIND UNABHÄNGIG. Wer den Akku-Dialog gesehen hat, darf
     * den Verbrauchshinweis trotzdem noch bekommen — und umgekehrt.
     */
    @Test fun akkuUndVerbrauchZaehlenGetrennt() {
        val e = Einstellungen(kontext)
        e.akkuHinweisGezeigt = true

        assertTrue(Einstellungen(kontext).akkuHinweisGezeigt)
        assertFalse(
            "Der Akku-Dialog darf den Verbrauchshinweis nicht miterledigen",
            Einstellungen(kontext).verbrauchHinweisGezeigt,
        )
    }
}
