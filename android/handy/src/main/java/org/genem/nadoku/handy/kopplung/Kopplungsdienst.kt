package org.genem.nadoku.handy.kopplung

import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.genem.nadoku.handy.tresor.Zugangsdaten
import org.json.JSONObject

/** Warum eine Kopplung nicht zustande kam. */
enum class Abweisung {
    /** Der Code passt nicht zu Alphabet und Länge — gar nicht erst gesendet. */
    CODE_UNBRAUCHBAR,

    /** 404 / `invalid`: unbekannt, abgelaufen oder schon verbraucht. */
    CODE_UNGUELTIG,

    /** 409 / `device_limit`: `MAX_GERAETE` erreicht. Nur im Web behebbar. */
    ZU_VIELE_GERAETE,

    /** 429 / `zu_viele_versuche`: Ratenschutz. Warten hilft, Tippen nicht. */
    ZU_VIELE_VERSUCHE,

    /** Kein Netz, kein TLS, Zeitüberschreitung. Später unverändert erneut. */
    KEINE_VERBINDUNG,

    /** 5xx oder eine Antwort, die kein brauchbares JSON ist. */
    SERVERFEHLER,

    /** Ein Fall, den diese Fassung noch nicht kennt. */
    UNBEKANNT,
}

sealed interface Kopplungsergebnis {
    /** Die Zugangsdaten liegen im Tresor. */
    data object Gekoppelt : Kopplungsergebnis

    /**
     * @param meldung Freitext des Servers, sofern vorhanden. Er wird als
     *   **zweite Zeile** angezeigt und ersetzt nie den eigenen Text: Die
     *   Meldungen von `pair.php` sind für die Weboberfläche geschrieben —
     *   ganze Sätze, ohne Umlaute. Dieselbe Überlegung wie in `Pair.mc`.
     */
    data class Abgewiesen(val art: Abweisung, val meldung: String? = null) : Kopplungsergebnis
}

sealed interface Trennergebnis {
    /** Der Server hat das Gerät gelöscht, die Ablage ist leer. */
    data object Getrennt : Trennergebnis

    /**
     * Lokal getrennt, der Servereintrag steht noch — er belegt einen der
     * `MAX_GERAETE` Plätze und ist im Web mit einem Klick zu entfernen.
     */
    data class NurLokal(val grund: Abweisung) : Trennergebnis

    /**
     * **Nichts ist geschehen.** Abgeschlossene, noch nicht gesendete Pakete
     * gehören dem bisherigen Konto; nach einer Neukopplung gingen sie an das
     * neue. Das wäre kein Datenverlust, sondern schlimmer — fremde Einsätze in
     * einem fremden Konto (Backlog Nr. 14).
     */
    data class Rueckstand(val pakete: Int) : Trennergebnis

    /** Es gab nichts zu trennen. */
    data object NichtGekoppelt : Trennergebnis
}

/**
 * Koppeln und Trennen gegen `pair.php` (JSON-Vertrag 1a und 1b, E-S4-12).
 *
 * DIE REIHENFOLGE IST ABFRAGEN → TRENNEN → NEU KOPPELN (Backlog Nr. 14). Der
 * Fall ist das geteilt genutzte Gerät: Bis zu dieser Regel führte der Weg
 * direkt in die Code-Eingabe, und wenn das Koppeln fehlschlug, blieben die
 * **alten** Zugangsdaten stehen — das Gerät dokumentierte stillschweigend
 * weiter auf das vorherige Konto. Niemand sah es ihm an, und die Person davor
 * bekam Einsätze, die sie nicht gefahren ist. Nach dem Trennen steht die App
 * **sichtbar** ohne Kopplung da.
 *
 * ENTSCHIEDEN WIRD AM FELD `error`, NICHT AM ZAHLENCODE. Der Schlüssel benennt
 * die Ursache, der Code nur ihre Klasse — und `pair.php` kann denselben Code
 * für mehr als eine Ursache verwenden. Der Zahlencode ist der Rückfall.
 */
class Kopplungsdienst(
    private val netzweg: Netzweg,
    private val tresor: Schluesseltresor,
    /**
     * Wie viele abgeschlossene, noch nicht bestätigte Pakete liegen?
     *
     * Als Funktion und nicht als Zahl, weil die Warteschlange erst mit B4
     * entsteht. Bis dahin liefert der Aufrufer 0 — und der Prüfstand liefert
     * etwas anderes, damit die Sperre trotzdem belegt ist.
     */
    private val rueckstand: () -> Int = { 0 },
) {

    fun koppeln(basis: String, codeEingabe: String, geraet: Geraeteangabe): Kopplungsergebnis {
        val code = Kopplungscode.normalisiere(codeEingabe)
        if (!Kopplungscode.gueltig(code)) {
            // Nicht senden: Der Ratenschutz von pair.php zählt jeden
            // Fehlversuch mit, auch einen offensichtlichen Vertipper.
            return Kopplungsergebnis.Abgewiesen(Abweisung.CODE_UNBRAUCHBAR)
        }

        val koerper = JSONObject()
            .put("code", code)
            .put("geraet", geraet.alsJson())
            .toString()

        // OHNE Auth-Kopfzeilen: Die Kopplung erzeugt die Zugangsdaten ja erst
        // (Vertrag 1a — der einzige Weg, der ohne sie auskommt).
        return when (val a = netzweg.postJson(Serveradresse.pair(basis), koerper, emptyMap())) {
            is Netzantwort.KeineVerbindung ->
                Kopplungsergebnis.Abgewiesen(Abweisung.KEINE_VERBINDUNG)

            is Netzantwort.Server -> werteKopplungAus(a)
        }
    }

    private fun werteKopplungAus(a: Netzantwort.Server): Kopplungsergebnis {
        val o = alsJson(a.koerper)
        val fehler = o?.optString("error").orEmpty()
        val meldung = o?.optString("meldung")?.ifEmpty { null }

        if (a.code == 200 && o != null) {
            val kennung = o.optString("device_id")
            val geheim = o.optString("api_key")
            if (kennung.isNotEmpty() && geheim.isNotEmpty()) {
                tresor.speichern(Zugangsdaten(kennung, geheim))
                return Kopplungsergebnis.Gekoppelt
            }
            // 200 ohne Zugangsdaten ist kein Erfolg, sondern ein unbekannter
            // Fall — er darf nicht als Kopplung durchgehen.
            return Kopplungsergebnis.Abgewiesen(Abweisung.UNBEKANNT, meldung)
        }

        val art = when {
            fehler == "device_limit" -> Abweisung.ZU_VIELE_GERAETE
            fehler == "zu_viele_versuche" -> Abweisung.ZU_VIELE_VERSUCHE
            fehler == "invalid" || a.code == 404 -> Abweisung.CODE_UNGUELTIG
            fehler == "code" || a.code == 400 -> Abweisung.CODE_UNBRAUCHBAR
            fehler == "server" || a.code >= 500 -> Abweisung.SERVERFEHLER
            else -> Abweisung.UNBEKANNT
        }
        return Kopplungsergebnis.Abgewiesen(art, meldung)
    }

    /**
     * Die Kopplung zurückgeben (Vertrag 1b).
     *
     * Der Server **löscht** das Gerät, er deaktiviert es nicht: Ein
     * deaktiviertes belegte weiter einen der `MAX_GERAETE` Plätze — und „zu
     * viele Geräte" ist genau der Fehler, in den ein geteiltes Gerät sonst
     * läuft. Hochgeladene Daten bleiben vollständig erhalten.
     */
    fun trennen(basis: String): Trennergebnis {
        val zugang = tresor.lesen() ?: return Trennergebnis.NichtGekoppelt

        val offen = rueckstand()
        if (offen > 0) return Trennergebnis.Rueckstand(offen)

        val antwort = netzweg.postJson(
            Serveradresse.pair(basis),
            JSONObject().put("aktion", "trennen").toString(),
            mapOf(
                "X-Device-Id" to zugang.geraeteKennung,
                "X-Api-Key" to zugang.schluessel,
            ),
        )

        // LOKAL WIRD IMMER GETRENNT — vor der Auswertung, damit kein Zweig
        // daran vorbeikommt.
        tresor.loeschen()

        return when (antwort) {
            is Netzantwort.KeineVerbindung ->
                Trennergebnis.NurLokal(Abweisung.KEINE_VERBINDUNG)

            is Netzantwort.Server -> {
                val o = alsJson(antwort.koerper)
                when {
                    antwort.code == 200 && o?.optBoolean("ok") == true -> Trennergebnis.Getrennt
                    o?.optString("error") == "zu_viele_versuche" || antwort.code == 429 ->
                        Trennergebnis.NurLokal(Abweisung.ZU_VIELE_VERSUCHE)
                    antwort.code == 401 -> Trennergebnis.NurLokal(Abweisung.CODE_UNGUELTIG)
                    else -> Trennergebnis.NurLokal(Abweisung.SERVERFEHLER)
                }
            }
        }
    }

    private fun alsJson(text: String?): JSONObject? =
        try {
            if (text.isNullOrBlank()) null else JSONObject(text)
        } catch (e: org.json.JSONException) {
            null
        }
}
