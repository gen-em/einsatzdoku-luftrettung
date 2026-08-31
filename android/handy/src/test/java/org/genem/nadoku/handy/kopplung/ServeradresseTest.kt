package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Test

/** Die tolerante Ergänzung der Server-Adresse (E-S4-12, E-S4-14). */
class ServeradresseTest {

    @Test fun blosserRechnernameBekommtSchemaUndStrich() {
        assertEquals(
            "https://einsatz.beispieldomain.de/",
            Serveradresse.normalisiere("einsatz.beispieldomain.de")
        )
    }

    @Test fun leerzeichenUndGrossschreibungStoerenNicht() {
        assertEquals(
            "https://einsatz.beispieldomain.de/",
            Serveradresse.normalisiere("  HTTPS://einsatz.beispieldomain.de  ")
        )
    }

    /** E-S4-14: nur HTTPS, ohne Ausnahmeschalter. */
    @Test fun httpWirdZuHttps() {
        assertEquals(
            "https://einsatz.beispieldomain.de/",
            Serveradresse.normalisiere("http://einsatz.beispieldomain.de/")
        )
    }

    @Test fun einAbgeschriebenerEndpunktTraegtAuch() {
        assertEquals(
            "https://einsatz.beispieldomain.de/",
            Serveradresse.normalisiere("https://einsatz.beispieldomain.de/ingest.php")
        )
        assertEquals(
            "https://einsatz.beispieldomain.de/nadoku/",
            Serveradresse.normalisiere("einsatz.beispieldomain.de/nadoku/pair.php")
        )
    }

    @Test fun unterverzeichnisBleibtErhalten() {
        assertEquals(
            "https://beispieldomain.de/nadoku/",
            Serveradresse.normalisiere("beispieldomain.de/nadoku")
        )
    }

    @Test fun abfrageUndSprungmarkeFallenWeg() {
        assertEquals(
            "https://beispieldomain.de/",
            Serveradresse.normalisiere("beispieldomain.de/?t=geraete#code")
        )
    }

    @Test fun unbrauchbaresGibtNull() {
        assertNull(Serveradresse.normalisiere(null))
        assertNull(Serveradresse.normalisiere(""))
        assertNull(Serveradresse.normalisiere("   "))
        assertNull(Serveradresse.normalisiere("einsatz"))          // kein Punkt
        assertNull(Serveradresse.normalisiere("ftp://beispieldomain.de/"))
        assertNull(Serveradresse.normalisiere("javascript:alert(1)"))
        assertNull(Serveradresse.normalisiere("https:///pfad"))    // kein Rechner
        assertNull(Serveradresse.normalisiere("https://ein sat z.de/"))
    }

    /** Der Prüfstand spricht mit 127.0.0.1 — das muss durchgehen. */
    @Test fun rueckschleifeUndLocalhostSindGueltig() {
        assertEquals("https://127.0.0.1:8443/", Serveradresse.normalisiere("127.0.0.1:8443"))
        assertEquals("https://localhost:8080/", Serveradresse.normalisiere("localhost:8080"))
    }

    @Test fun endpunkteHaengenAnDerBasis() {
        val b = "https://beispieldomain.de/nadoku/"
        assertEquals("https://beispieldomain.de/nadoku/ingest.php", Serveradresse.ingest(b))
        assertEquals("https://beispieldomain.de/nadoku/pair.php", Serveradresse.pair(b))
    }
}
