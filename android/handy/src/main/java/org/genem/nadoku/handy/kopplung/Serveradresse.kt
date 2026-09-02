package org.genem.nadoku.handy.kopplung

/**
 * Die Server-Adresse, tolerant ergänzt (E-S4-12).
 *
 * WARUM TOLERANZ. Was jemand aus dem Browser abliest oder abtippt, ist der
 * Name der Anwendung — `einsatz.beispieldomain.de`. Was die App braucht, ist
 * eine vollständige Adresse mit Schema und Pfad. Die Garmin-Uhr macht das seit
 * jeher (`Uploader._serverUrl()`), und aus demselben Grund: Eine App, die auf
 * einem fehlenden `https://` besteht, erzeugt einen Fehler, den niemand als
 * solchen erkennt — die Adresse *sieht* ja richtig aus.
 *
 * NUR HTTPS (E-S4-14). Ein getipptes `http://` wird zu `https://`, und es gibt
 * keinen Ausnahmeschalter. Androids Vorgabe verbietet Klartextverkehr ohnehin;
 * ein Geräteschlüssel im Klartext über HTTP wäre die Kopplung nicht wert. Die
 * Ergänzung geschieht still, weil die Alternative — eine Fehlermeldung — der
 * NutzerIn eine Entscheidung abverlangte, die es gar nicht gibt.
 *
 * GESPEICHERT WIRD DIE BASIS, nicht der Endpunkt. Die Uhr speichert die
 * `ingest.php`-Adresse und rechnet die Basis daraus zurück; für zwei Endpunkte
 * (`ingest.php` und `pair.php`) ist der umgekehrte Weg der kürzere.
 */
object Serveradresse {

    /**
     * Macht aus einer Eingabe die Basisadresse (mit abschließendem `/`).
     * `null`, wenn daraus keine brauchbare Adresse wird.
     */
    fun normalisiere(eingabe: String?): String? {
        val roh = eingabe?.trim() ?: return null
        if (roh.isEmpty()) return null

        // 1. Schema abtrennen und auf HTTPS ziehen (E-S4-14). Ein anderes
        //    Schema als http/https ist keine Server-Adresse, sondern ein
        //    Missverständnis oder ein Angriffsversuch — beides abweisen.
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

        return "https://$rechner$pfad"
    }

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
}
