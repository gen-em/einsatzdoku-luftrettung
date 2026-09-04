package org.genem.nadoku.handy.kopplung

import org.genem.nadoku.BuildConfig

/**
 * Die Server-Adresse — **fest** seit R63 (Backlog Nr. 84).
 *
 * WAS SICH GEÄNDERT HAT UND WARUM. Bis Android 0.10.1 war die Adresse ein
 * Eingabefeld, wahlweise gefüllt aus einem QR-Code (E-S4-15). Beides ist
 * ersatzlos entfallen. Der Grund ist nicht Bequemlichkeit, sondern der
 * Zuschnitt des Dienstes: Diese App gehört zu **einer** Installation. Ein
 * Adressfeld verlangte von jeder NutzerIn eine Angabe, die für alle dieselbe
 * ist — und war zugleich die einzige Stelle, an der ein Tippfehler die App
 * still an einen fremden Server hängen konnte.
 *
 * WER EINE EIGENE INSTALLATION BETREIBT, baut ein eigenes APK. Die Adresse
 * steht deshalb im **Bauskript** (`buildConfigField SERVER_BASIS`), nicht als
 * Konstante hier: Ein Selbsthoster ändert eine Zeile Gradle und keine Zeile
 * Kotlin. Für den Prüfstand ist dasselbe Feld der Weg zur örtlichen
 * Installation (`-Pnadoku.serverBasis=http://127.0.0.1:8080/`).
 *
 * DIE TOLERANZREGELN BLEIBEN — sie haben nur ein anderes Gegenüber. Vorher
 * fingen sie ab, was ein Mensch abtippt; jetzt fangen sie ab, was jemand ins
 * Bauskript schreibt. `nadoku.gen-em.org` ohne Schema, mit `http://`, mit
 * angehängtem `pair.php`: In allen drei Fällen entsteht dieselbe Basis. Der
 * Unterschied zu früher ist, dass ein Fehler jetzt **beim Bauen** entsteht und
 * nicht bei der Kopplung — [BASIS] wirft, wenn nichts Brauchbares dasteht.
 *
 * NUR HTTPS (E-S4-14). Ein eingetragenes `http://` wird zu `https://`, und es
 * gibt keinen Ausnahmeschalter — mit einer benannten Ausnahme: `localhost` und
 * IPv4-Adressen behalten ihr `http`, weil der Prüfstand ohne TLS gegen
 * `127.0.0.1:8080` spricht und eine Zwangsumleitung ihn ins Leere laufen
 * ließe. Für jeden echten Rechnernamen gilt HTTPS ohne Ausnahme; ein
 * Geräteschlüssel im Klartext wäre die Kopplung nicht wert.
 *
 * GESPEICHERT WIRD NICHTS MEHR. Die Basis ist eine Konstante des Baulaufs;
 * `Einstellungen.serverBasis` ist mit dieser Fassung entfallen.
 */
object Serveradresse {

    /**
     * Die Basisadresse dieser App, mit abschließendem `/`.
     *
     * ES WIRFT, WENN DER BAUWERT UNBRAUCHBAR IST — und das ist Absicht. Eine
     * App, die mit einer kaputten Adresse startet und erst beim ersten
     * Kopplungsversuch „keine Verbindung" sagt, verschweigt die Ursache. Ein
     * Fehlschlag beim ersten Zugriff trifft den, der ihn beheben kann: die
     * Person, die das APK gebaut hat.
     */
    val BASIS: String by lazy {
        normalisiere(BuildConfig.SERVER_BASIS)
            ?: error(
                "SERVER_BASIS ist keine brauchbare Adresse: '${BuildConfig.SERVER_BASIS}'. " +
                    "Erwartet wird ein Rechnername wie nadoku.gen-em.org."
            )
    }

    /**
     * Macht aus einer Eingabe die Basisadresse (mit abschließendem `/`).
     * `null`, wenn daraus keine brauchbare Adresse wird.
     */
    fun normalisiere(eingabe: String?): String? {
        val roh = eingabe?.trim() ?: return null
        if (roh.isEmpty()) return null

        // 1. Schema abtrennen (E-S4-14). Ein anderes Schema als http/https ist
        //    keine Server-Adresse, sondern ein Missverständnis oder ein
        //    Angriffsversuch — beides abweisen. Ob am Ende `https` steht,
        //    entscheidet Schritt 5 am Rechnernamen, nicht der Eingabetext.
        val trenner = roh.indexOf("://")
        val ohneSchema = if (trenner < 0) {
            roh
        } else {
            when (roh.substring(0, trenner).lowercase()) {
                "https", "http" -> roh.substring(trenner + 3)
                else -> return null
            }
        }

        // 2. Anhängsel abschneiden, die keine Basis sind.
        var rest = ohneSchema.substringBefore('?').substringBefore('#')

        // 3. Rechnername und Pfad trennen. Der RECHNERNAME WIRD KLEIN
        //    GESCHRIEBEN, der Pfad nicht: Rechnernamen sind ohne Rücksicht auf
        //    Groß- und Kleinschreibung gleich, Pfade sind es auf einem
        //    Linux-Server nicht — "/NAdoku/" und "/nadoku/" sind dort zwei
        //    Verzeichnisse.
        val strich = rest.indexOf('/')
        val rechner = (if (strich < 0) rest else rest.substring(0, strich)).lowercase()
        var pfad = if (strich < 0) "" else rest.substring(strich)

        if (rechner.isEmpty() || !RECHNER.matches(rechner)) return null

        // 4. Endet der Pfad auf eine Datei, zurück bis zum letzten "/" —
        //    damit auch eine abgeschriebene Endpunktadresse trägt.
        val letzterStrich = pfad.lastIndexOf('/')
        if (letzterStrich >= 0 && pfad.substringAfterLast('/').contains('.')) {
            pfad = pfad.substring(0, letzterStrich + 1)
        }
        if (!pfad.endsWith("/")) pfad = "$pfad/"

        // 5. Das Schema entsteht aus dem Rechnernamen, nicht aus der Eingabe.
        //    HTTPS ist die Regel (E-S4-14). Die Ausnahme ist der Prüfstand:
        //    `localhost` und eine IPv4-Adresse behalten `http`, weil der
        //    örtliche PHP-Server ohne TLS antwortet. Sie taugen ohnehin nicht
        //    als Adresse einer ausgelieferten App — ein Gerät im Feld erreicht
        //    unter 127.0.0.1 sich selbst und keinen Server.
        val schema = if (OERTLICH.matches(rechner)) "http" else "https"

        return "$schema://$rechner$pfad"
    }

    /**
     * Ein Rechnername ohne Namensauflösung: `localhost` oder eine
     * IPv4-Adresse, je mit oder ohne Port. Der Port gehört mit ins Muster —
     * `rechner` trägt ihn, und `localhost:8080` ist genau die Form, mit der
     * der Prüfstand spricht.
     */
    private val OERTLICH = Regex("^(localhost|(\\d{1,3}\\.){3}\\d{1,3})(:\\d{1,5})?$")

    /**
     * Ein brauchbarer Rechnername, mit oder ohne Port:
     *
     *  - ein Name mit Punkt und Endung (`einsatz.beispieldomain.de`),
     *  - eine IPv4-Adresse (der Prüfstand spricht mit `127.0.0.1`),
     *  - `localhost`.
     *
     * Der Punkt bzw. die Zifferngruppen sind die eigentliche Prüfung: Ohne sie
     * ist „einsatz" kein Rechnername, sondern ein Vertipper — und eine
     * Adresse, die erst beim ersten Senden auffällt, fällt zur schlechtesten
     * Zeit auf. Zugangsdaten in der Adresse (`benutzer:wort@rechner`) fallen
     * durch, weil `@` und `:` vor dem Port nicht vorgesehen sind.
     */
    private val RECHNER = Regex(
        "^(" +
            "localhost" +
            "|(\\d{1,3}\\.){3}\\d{1,3}" +
            "|[a-z0-9]([a-z0-9-]*[a-z0-9])?(\\.[a-z0-9]([a-z0-9-]*[a-z0-9])?)*\\.[a-z]{2,}" +
            ")(:\\d{1,5})?$"
    )

    fun ingest(basis: String): String = basis + "ingest.php"
    fun pair(basis: String): String = basis + "pair.php"

    /* DIE BEIDEN RECHTSTEXTE (seit 0.13.0).
     *
     * Sie stehen hier neben den zwei Endpunkten, weil sie dieselbe Herkunft
     * haben: die eine eingebaute Serveradresse (R63). Eine zweite Adresse
     * fuer die Rechtstexte waere eine zweite Stelle, an der man sie aendern
     * muesste — und eine, an der sie auf ein anderes Haus zeigen koennten
     * als der Server, auf den die App ihre Daten schickt.
     *
     * Beide Seiten sind OHNE ANMELDUNG erreichbar (`rechtstext_seite.php`);
     * die App braucht dafuer weder Kopplung noch Konto. Der Inhalt ist ein
     * Betreibertext aus der Datenbank — liegt keiner vor, zeigt die Seite
     * das und nicht eine leere Seite.
     *
     * Sie werden im BROWSER geoeffnet, nicht in der App: Ein eingebauter
     * Betrachter waere ein WebView, und den hat diese App bewusst nicht
     * (er waere die einzige Stelle, an der fremdes Markup im Prozess der
     * App liefe). */
    fun datenschutz(basis: String): String = basis + "datenschutz.php"
    fun impressum(basis: String): String = basis + "impressum.php"
}
