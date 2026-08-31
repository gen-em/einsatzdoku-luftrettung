package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertFalse
import org.junit.Assert.assertTrue
import org.junit.Test

/** Alphabet und Länge des Kopplungscodes (server/db.php: PAIR_CHARS, PAIR_LEN). */
class KopplungscodeTest {

    @Test fun alphabetUndLaengeStimmenMitDemServerUeberein() {
        assertEquals("ABCDEFGHJKLMNPQRSTUVWXYZ23456789", Kopplungscode.ALPHABET)
        assertEquals(6, Kopplungscode.LAENGE)
        // Die vier verwechselbaren Zeichen fehlen — daran hängt der Sinn.
        for (z in listOf('0', 'O', '1', 'I')) {
            assertFalse("$z darf nicht im Alphabet stehen", Kopplungscode.ALPHABET.contains(z))
        }
    }

    @Test fun normalisierenGrossschreibtUndEntferntTrennzeichen() {
        assertEquals("AB3K7Q", Kopplungscode.normalisiere(" ab3 k-7q "))
        assertEquals("", Kopplungscode.normalisiere(null))
    }

    @Test fun gueltigeCodes() {
        assertTrue(Kopplungscode.gueltig("AB3K7Q"))
        assertTrue(Kopplungscode.gueltig("ZZZZZZ"))
        assertTrue(Kopplungscode.gueltig("234567"))
    }

    @Test fun ungueltigeCodes() {
        assertFalse("zu kurz", Kopplungscode.gueltig("AB3K7"))
        assertFalse("zu lang", Kopplungscode.gueltig("AB3K7QQ"))
        assertFalse("Null gibt es nicht", Kopplungscode.gueltig("AB3K70"))
        assertFalse("O gibt es nicht", Kopplungscode.gueltig("AB3K7O"))
        assertFalse("Eins gibt es nicht", Kopplungscode.gueltig("AB3K71"))
        assertFalse("I gibt es nicht", Kopplungscode.gueltig("AB3K7I"))
        assertFalse("klein geschrieben", Kopplungscode.gueltig("ab3k7q"))
        assertFalse("leer", Kopplungscode.gueltig(""))
    }
}
