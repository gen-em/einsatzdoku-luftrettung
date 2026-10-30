package org.genem.nadoku.handy.kopplung

import org.genem.nadoku.BuildConfig
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.junit.Assume.assumeFalse
import org.junit.Assume.assumeTrue
import org.junit.Test

/**
 * Die tolerante Ergänzung der Server-Adresse.
 *
 * Die Regeln fangen ab, was jemand als `SERVER_BASIS` ins Bauskript
 * schreibt: Ein Fehler fällt beim Bauen auf und nicht bei der Kopplung.
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

    /** Nur HTTPS für jeden echten Rechnernamen. */
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
     * Ausnahme von „nur HTTPS" — **nur im Prüf-APK**.
     *
     * Sie ist keine Aufweichung: `127.0.0.1` und `localhost` taugen ohnehin
     * nicht als Adresse einer ausgelieferten App (ein Gerät im Feld erreichte
     * damit sich selbst). Ohne die Ausnahme liefe der Rundlauf gegen einen
     * TLS-Port, den der Prüfserver nicht hat — und die App bekäme etwas
     * beigebracht, was sie im Feld nie tun darf: einem selbstsignierten
     * Zertifikat zu trauen.
     *
     * Der Fall läuft nur im Debug-Buildtyp: `./gradlew build` prüft beide
     * Bauarten, und im Release gilt das Gegenteil (nächster Fall). Je Bauart
     * ist genau einer der beiden übersprungen — sichtbar, nicht still.
     */
    @Test fun oertlicheAdressenBehaltenHttp() {
        assumeTrue("nur im Prüf-APK", BuildConfig.DEBUG)
        assertEquals("http://127.0.0.1:8080/", Serveradresse.normalisiere("127.0.0.1:8080"))
        assertEquals("http://localhost:8080/", Serveradresse.normalisiere("localhost:8080"))
        assertEquals("http://127.0.0.1/", Serveradresse.normalisiere("https://127.0.0.1/"))
    }

    /**
     * Und im **Release** gibt es die Ausnahme nicht: Dort wird auch eine
     * IP-Adresse zu `https`. Sonst verschickte ein mit IP-Adresse gebautes
     * APK den Geräteschlüssel auf Android 8 im Klartext.
     */
    @Test fun imReleaseGiltHttpsAuchFuerOertlicheAdressen() {
        assumeFalse("nur im Release", BuildConfig.DEBUG)
        assertEquals("https://127.0.0.1:8080/", Serveradresse.normalisiere("127.0.0.1:8080"))
        assertEquals("https://localhost:8080/", Serveradresse.normalisiere("http://localhost:8080"))
        assertEquals("https://127.0.0.1/", Serveradresse.normalisiere("https://127.0.0.1/"))
    }

    /**
     * DIE FESTE ADRESSE DIESES BAULAUFS.
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
