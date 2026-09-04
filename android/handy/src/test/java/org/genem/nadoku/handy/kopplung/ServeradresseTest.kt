package org.genem.nadoku.handy.kopplung

import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Test

/**
 * Die tolerante Ergänzung der Server-Adresse (E-S4-12, E-S4-14).
 *
 * SEIT R63 PRÜFT DIESE KLASSE ETWAS ANDERES ALS FRÜHER, obwohl die Fälle
 * fast dieselben geblieben sind: Die Regeln fingen bis Android 0.10.1 ab,
 * was ein Mensch in ein Eingabefeld tippt. Das Feld gibt es nicht mehr — sie
 * fangen jetzt ab, was jemand als `SERVER_BASIS` ins Bauskript schreibt.
 * Dieselbe Frage, ein anderer Zeitpunkt: Ein Fehler fällt beim Bauen auf und
 * nicht bei der Kopplung.
 */
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

    /** E-S4-14: nur HTTPS für jeden echten Rechnernamen. */
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

    /**
     * DIE ÖRTLICHE INSTALLATION BEHÄLT IHR `http` — die eine benannte
     * Ausnahme von E-S4-14.
     *
     * Sie ist keine Aufweichung: `127.0.0.1` und `localhost` taugen ohnehin
     * nicht als Adresse einer ausgelieferten App (ein Gerät im Feld erreichte
     * damit sich selbst). Ohne die Ausnahme liefe der Rundlauf gegen einen
     * TLS-Port, den der Prüfserver nicht hat — und die App bekäme etwas
     * beigebracht, was sie im Feld nie tun darf: einem selbstsignierten
     * Zertifikat zu trauen.
     */
    @Test fun oertlicheAdressenBehaltenHttp() {
        assertEquals("http://127.0.0.1:8080/", Serveradresse.normalisiere("127.0.0.1:8080"))
        assertEquals("http://localhost:8080/", Serveradresse.normalisiere("localhost:8080"))
        assertEquals("http://127.0.0.1/", Serveradresse.normalisiere("https://127.0.0.1/"))
    }

    /**
     * DIE FESTE ADRESSE DIESES BAULAUFS (R63, Backlog Nr. 84).
     *
     * Der Prüflauf setzt `SERVER_BASIS` nicht um, prüft also den Wert, mit dem
     * gebaut wurde. Zugesichert wird hier nicht die Domain — die kann ein
     * Selbsthoster ändern —, sondern dass überhaupt eine brauchbare Basis
     * entsteht: mit Schema, mit abschließendem Strich, und ohne dass der
     * Zugriff wirft.
     */
    @Test fun dieFesteBasisIstBrauchbar() {
        val b = Serveradresse.BASIS
        assertTrue("Basis ohne Schema: $b", b.startsWith("https://") || b.startsWith("http://"))
        assertTrue("Basis ohne abschließenden Strich: $b", b.endsWith("/"))
        assertEquals("Sie muss ihr eigener Festpunkt sein", b, Serveradresse.normalisiere(b))
    }

    @Test fun endpunkteHaengenAnDerBasis() {
        val b = "https://beispieldomain.de/nadoku/"
        assertEquals("https://beispieldomain.de/nadoku/ingest.php", Serveradresse.ingest(b))
        assertEquals("https://beispieldomain.de/nadoku/pair.php", Serveradresse.pair(b))
    }
}
