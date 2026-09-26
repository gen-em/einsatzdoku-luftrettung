package org.genem.nadoku.handy

import android.content.Context
import androidx.test.core.app.ApplicationProvider
import org.genem.nadoku.R
import org.junit.Assert.assertEquals
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner
import org.robolectric.annotation.Config

/**
 * Die drei Texte mit Zahl stehen seit Android 0.16.0 als `<plurals>`
 * (Konzept AR, E-AR-10).
 *
 * Bis dahin waren es `<string>`, und zwei davon kamen tatsaechlich mit 1 vor:
 * Die Restzeit der Kopplung rundet bei genau 60 s auf „1" Minute und zaehlt
 * unter einer Minute bis 1 Sekunde herunter. Dort stand „Noch 1 Minuten" und
 * „Noch 1 Sekunden". Lint meldete es seit 0.15.0 als `PluralsCandidate`.
 *
 * `qualifiers = "de"`: Die Mehrzahlregel haengt an der Sprache, nicht an der
 * Datei. Die App hat nur deutsche Texte, und die Regel soll die deutsche sein.
 */
@RunWith(RobolectricTestRunner::class)
@Config(qualifiers = "de")
class MehrzahlTest {

    private val res get() = ApplicationProvider.getApplicationContext<Context>().resources

    private fun text(id: Int, n: Int) = res.getQuantityString(id, n, n)

    @Test fun eineMinuteImSingular() {
        assertEquals("Noch 1 Minute", text(R.plurals.kopplung_rest_min, 1))
        assertEquals("Noch 10 Minuten", text(R.plurals.kopplung_rest_min, 10))
    }

    @Test fun eineSekundeImSingular() {
        assertEquals("Noch 1 Sekunde", text(R.plurals.kopplung_rest_sek, 1))
        assertEquals("Noch 59 Sekunden", text(R.plurals.kopplung_rest_sek, 59))
    }

    @Test fun dieDienstdauerZaehltStunden() {
        assertEquals("Dienst läuft seit 1 Stunde", text(R.plurals.warnung_dienstdauer_titel, 1))
        assertEquals("Dienst läuft seit 27 Stunden", text(R.plurals.warnung_dienstdauer_titel, 27))
    }
}
