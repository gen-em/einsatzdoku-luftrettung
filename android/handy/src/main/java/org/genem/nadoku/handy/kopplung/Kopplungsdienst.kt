package org.genem.nadoku.handy.kopplung

import org.genem.nadoku.handy.tresor.Schluesseltresor
import org.genem.nadoku.handy.tresor.Zugangsdaten
import org.json.JSONObject

/** Warum ein Kopplungsschritt nicht durchging. */
enum class Abweisung {
    /** 401 / `auth`: Kennung unbekannt oder Schlüssel falsch. Von vorn. */
    SITZUNG_UNGUELTIG,

    /** 410 / `abgelaufen`: Die zehn Minuten sind um. Von vorn. */
    SITZUNG_ABGELAUFEN,

    /** 409 / `device_limit`: `MAX_GERAETE` erreicht. Nur im Web behebbar. */
    ZU_VIELE_GERAETE,

    /** 429 / `zu_viele_versuche` oder `zu_viele_sitzungen`: Ratenschutz. */
    ZU_VIELE_VERSUCHE,

    /** Kein Netz, kein TLS, Zeitüberschreitung. Später unverändert erneut. */
    KEINE_VERBINDUNG,

    /** 5xx oder eine Antwort, die kein brauchbares JSON ist. */
    SERVERFEHLER,

    /** Ein Fall, den diese Fassung noch nicht kennt. */
    UNBEKANNT,
}

/** Was `start` ergeben hat (Vertrag 1a.1). */
sealed interface Sitzungsergebnis {
    /**
     * Die Sitzung steht. Der [code] gehört auf den Bildschirm, die
     * Zugangsdaten liegen **schwebend** im Tresor.
     *
     * @param fristSekunden die volle Gültigkeitsdauer, nicht eine Restzeit.
     */
    data class Offen(val code: String, val fristSekunden: Int) : Sitzungsergebnis

    data class Abgewiesen(val art: Abweisung, val meldung: String? = null) : Sitzungsergebnis
}

/** Was `status` ergeben hat (Vertrag 1a.2). */
sealed interface Sitzungsstand {
    /** Noch hat niemand den Code eingegeben. */
    data class Offen(val restSekunden: Int) : Sitzungsstand

    /**
     * Ein Konto hat den Code eingegeben und wartet auf das Ja am Gerät.
     *
     * @param konto die **maskierte** Adresse (`ph***@gen-em.org`). Sie ist
     *   eine Zeichenkette für Menschen, kein Adressformat — sie wird angezeigt
     *   und nirgends zerlegt oder gespeichert (Vertrag 1a.2).
     */
    data class Beansprucht(val konto: String, val restSekunden: Int) : Sitzungsstand

    /**
     * Zu diesen Kopfzeilen gibt es bereits ein Gerät. Das ist der Fall, in dem
     * die Antwort auf `bestaetigen ja` verlorenging — die Zugangsdaten sind
     * gültig, das Gerät gilt als gekoppelt.
     */
    data object Gekoppelt : Sitzungsstand

    data class Abgewiesen(val art: Abweisung, val meldung: String? = null) : Sitzungsstand
}

/** Was `bestaetigen` ergeben hat (Vertrag 1a.3). */
sealed interface Bestaetigungsergebnis {
    /** Nach `ja`: Das Gerät existiert, die Zugangsdaten gelten dauerhaft. */
    data object Gekoppelt : Bestaetigungsergebnis

    /** Nach `nein`: Sitzung und Zugangsdaten sind fort — auf beiden Seiten. */
    data object Abgebrochen : Bestaetigungsergebnis

    data class Abgewiesen(val art: Abweisung, val meldung: String? = null) : Bestaetigungsergebnis
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
 * Koppeln und Trennen gegen `pair.php` (JSON-Vertrag 1a und 1b).
 *
 * DIE KOPPLUNG IST UMGEKEHRT WORDEN (R49, S5, Web 13.0.0). Bis Android 0.10.1
 * erzeugte das **Web** den Code und die App **sendete** ihn. Jetzt bittet das
 * Gerät um eine Sitzung, **zeigt** den Code, und ein Mensch tippt ihn im
 * Browser ein; zuletzt bestätigt das Gerät das Konto, das der Server nennt.
 * Drei Anliegen statt einem:
 *
 * 1. [starten] — ohne Kopfzeilen. Ergibt Anzeigecode **und** Zugangsdaten.
 * 2. [nachfragen] — mit Kopfzeilen, höchstens alle fünf Sekunden.
 * 3. [bestaetigen] — mit Kopfzeilen, `ja` oder `nein`.
 *
 * WARUM DAS SICHERER IST ALS VORHER: Es gibt zwei Tore statt einem. Vorher
 * entschied allein, wer den Code kannte. Jetzt scheitert ein **fremdes Gerät
 * im eigenen Konto** an der Bestätigungsseite im Web (sie zeigt Art und
 * Modell), und das **eigene Gerät im fremden Konto** am Ja auf dem Gerät —
 * dort steht die maskierte Adresse, und eine fremde Domain fällt auf.
 *
 * DIE ZUGANGSDATEN SCHWEBEN BIS ZUM JA. Sie liegen ab [starten] im Tresor,
 * aber `ingest.php` weist sie mit `401` ab, weil es das Gerät noch nicht gibt.
 * Sie werden trotzdem sofort gespeichert und nicht im Arbeitsspeicher
 * gehalten: [nachfragen] und [bestaetigen] brauchen sie als Kopfzeilen, und
 * ein Prozesstod zwischen zwei Abfragen darf die Sitzung nicht wertlos machen.
 * Was den Unterschied zwischen „schwebend" und „gültig" trägt, ist
 * [Schluesseltresor.gekoppelt] — es wird erst nach dem Ja gesetzt.
 *
 * DIE REIHENFOLGE IST ABFRAGEN → TRENNEN → NEU KOPPELN (Backlog Nr. 14). Der
 * Fall ist das geteilt genutzte Gerät: Bis zu dieser Regel führte der Weg
 * direkt in die Kopplung, und wenn sie fehlschlug, blieben die **alten**
 * Zugangsdaten stehen — das Gerät dokumentierte stillschweigend weiter auf das
 * vorherige Konto.
 *
 * ENTSCHIEDEN WIRD AM FELD `error`, NICHT AM ZAHLENCODE. Der Schlüssel benennt
 * die Ursache, der Code nur ihre Klasse — und `pair.php` kann denselben Code
 * für mehr als eine Ursache verwenden. Der Zahlencode ist der Rückfall.
 */
class Kopplungsdienst(
    private val netzweg: Netzweg,
    private val tresor: Schluesseltresor,
    /**
     * Die Basisadresse. Vorgabe ist die feste Adresse dieses Baulaufs (R63);
     * der Prüfstand setzt sie auf seine örtliche Installation.
     */
    private val basis: String = Serveradresse.BASIS,
    /**
     * Wie viele abgeschlossene, noch nicht bestätigte Pakete liegen?
     *
     * Als Funktion und nicht als Zahl, weil die Warteschlange erst mit B4
     * entsteht. Bis dahin liefert der Aufrufer 0 — und der Prüfstand liefert
     * etwas anderes, damit die Sperre trotzdem belegt ist.
     */
    private val rueckstand: () -> Int = { 0 },
) {

    /**
     * Schritt 1: um eine Kopplungssitzung bitten (Vertrag 1a.1).
     *
     * OHNE KOPFZEILEN — das Gerät hat noch keine. Der `geraet`-Block geht
     * **hier** mit und nicht erst beim Bestätigen: Die Kontoinhaberin soll auf
     * der Bestätigungsseite im Browser sehen, *was* da koppeln will. (Konzept
     * S4 Abschnitt 13 sagt an dieser Stelle „nach `bestaetigen`" — das ist
     * überholt; maßgeblich ist Vertrag 1a.1.)
     *
     * NICHT IDEMPOTENT. Jeder Aufruf legt eine neue Sitzung an und zählt gegen
     * die Adressgrenze. Deshalb ruft die Oberfläche ihn genau einmal je
     * Kopplungsversuch und nicht bei jedem Neuzeichnen.
     */
    fun starten(geraet: Geraeteangabe): Sitzungsergebnis {
        val koerper = JSONObject()
            .put("aktion", "start")
            .put("geraet", geraet.alsJson())
            .toString()

        return when (val a = netzweg.postJson(Serveradresse.pair(basis), koerper, emptyMap())) {
            is Netzantwort.KeineVerbindung ->
                Sitzungsergebnis.Abgewiesen(Abweisung.KEINE_VERBINDUNG)

            is Netzantwort.Server -> {
                val o = alsJson(a.koerper)
                val meldung = meldungAus(o)

                if (a.code == 200 && o != null) {
                    val code = o.optString("code")
                    val kennung = o.optString("device_id")
                    val geheim = o.optString("api_key")
                    val frist = o.optInt("frist_s", 0)

                    if (Kopplungscode.gueltig(code) && kennung.isNotEmpty() && geheim.isNotEmpty()) {
                        // SCHWEBEND ablegen: Die Daten sind echt, das Gerät
                        // gibt es aber erst nach dem Ja (Vertrag 1a.1).
                        tresor.speichernSchwebend(Zugangsdaten(kennung, geheim))
                        return Sitzungsergebnis.Offen(code, if (frist > 0) frist else FRIST_VORGABE)
                    }
                    // 200 ohne brauchbare Sitzung ist kein Erfolg, sondern ein
                    // unbekannter Fall — er darf nicht als Kopplung durchgehen.
                    return Sitzungsergebnis.Abgewiesen(Abweisung.UNBEKANNT, meldung)
                }

                Sitzungsergebnis.Abgewiesen(artAus(o, a.code), meldung)
            }
        }
    }

    /**
     * Schritt 2: nachfragen, ob jemand den Code eingegeben hat (Vertrag 1a.2).
     *
     * HÖCHSTENS ALLE FÜNF SEKUNDEN und nie, bevor die vorige Antwort da ist —
     * den Takt hält die Oberfläche. Ein Verbindungsfehler ist **kein** Grund
     * aufzuhören: Die Sitzung lebt auf dem Server weiter, bis die Frist
     * abläuft; deshalb ist [Abweisung.KEINE_VERBINDUNG] hier ein Zustand zum
     * Weiterfragen und kein Abbruch.
     */
    fun nachfragen(): Sitzungsstand {
        val zugang = tresor.lesen()
            ?: return Sitzungsstand.Abgewiesen(Abweisung.SITZUNG_UNGUELTIG)

        val antwort = netzweg.postJson(
            Serveradresse.pair(basis),
            JSONObject().put("aktion", "status").toString(),
            kopfzeilen(zugang),
        )

        return when (antwort) {
            is Netzantwort.KeineVerbindung ->
                Sitzungsstand.Abgewiesen(Abweisung.KEINE_VERBINDUNG)

            is Netzantwort.Server -> {
                val o = alsJson(antwort.koerper)
                val meldung = meldungAus(o)

                if (antwort.code == 200 && o != null) {
                    val rest = o.optInt("rest_s", 0)
                    return when (o.optString("zustand")) {
                        "offen" -> Sitzungsstand.Offen(rest)
                        "beansprucht" -> {
                            val konto = o.optString("konto")
                            // Ohne Konto wäre der Dialog ein Ja ins Blaue —
                            // genau das zweite Tor fiele weg. Dann lieber
                            // weiterfragen als eine leere Zeile anzeigen.
                            if (konto.isEmpty()) Sitzungsstand.Offen(rest)
                            else Sitzungsstand.Beansprucht(konto, rest)
                        }
                        "gekoppelt" -> Sitzungsstand.Gekoppelt
                        else -> Sitzungsstand.Abgewiesen(Abweisung.UNBEKANNT, meldung)
                    }
                }

                Sitzungsstand.Abgewiesen(artAus(o, antwort.code), meldung)
            }
        }
    }

    /**
     * Schritt 3: Ja oder Nein zu dem Konto sagen (Vertrag 1a.3).
     *
     * NACH `ja` GILT DAS GERÄT — und erst hier hören die Zugangsdaten auf zu
     * schweben. NACH `nein` ist die Sitzung fort, auf beiden Seiten; die
     * lokale Ablage wird geleert, **auch wenn der Server nicht antwortet**.
     * Sonst bliebe ein Schlüssel liegen, zu dem es nie ein Gerät gab.
     *
     * `nein` IST IN JEDEM ZUSTAND ERLAUBT, auch nach Fristablauf — so bricht
     * die App ab, wenn jemand die Ansicht verlässt.
     */
    fun bestaetigen(ja: Boolean): Bestaetigungsergebnis {
        val zugang = tresor.lesen()
            ?: return Bestaetigungsergebnis.Abgewiesen(Abweisung.SITZUNG_UNGUELTIG)

        val antwort = netzweg.postJson(
            Serveradresse.pair(basis),
            JSONObject().put("aktion", "bestaetigen").put("antwort", if (ja) "ja" else "nein")
                .toString(),
            kopfzeilen(zugang),
        )

        if (!ja) {
            // Beim Nein wird IMMER lokal aufgeräumt, vor der Auswertung —
            // damit kein Zweig daran vorbeikommt (dieselbe Regel wie beim
            // Trennen).
            tresor.loeschen()
            return Bestaetigungsergebnis.Abgebrochen
        }

        return when (antwort) {
            is Netzantwort.KeineVerbindung ->
                Bestaetigungsergebnis.Abgewiesen(Abweisung.KEINE_VERBINDUNG)

            is Netzantwort.Server -> {
                val o = alsJson(antwort.koerper)
                val meldung = meldungAus(o)

                if (antwort.code == 200 && o?.optBoolean("ok") == true) {
                    tresor.bestaetigen()
                    return Bestaetigungsergebnis.Gekoppelt
                }

                val art = artAus(o, antwort.code)
                // Bei `device_limit` ist die Sitzung serverseitig gelöscht
                // (Vertrag 1a.3) — der schwebende Schlüssel gehört zu nichts
                // mehr und muss weg, sonst hinge er bis zum nächsten Start.
                if (art == Abweisung.ZU_VIELE_GERAETE || art == Abweisung.SITZUNG_ABGELAUFEN ||
                    art == Abweisung.SITZUNG_UNGUELTIG
                ) {
                    tresor.loeschen()
                }
                Bestaetigungsergebnis.Abgewiesen(art, meldung)
            }
        }
    }

    /**
     * Die Kopplung zurückgeben (Vertrag 1b).
     *
     * Der Server **löscht** das Gerät, er deaktiviert es nicht: Ein
     * deaktiviertes belegte weiter einen der `MAX_GERAETE` Plätze — und „zu
     * viele Geräte" ist genau der Fehler, in den ein geteiltes Gerät sonst
     * läuft. Hochgeladene Daten bleiben vollständig erhalten.
     */
    fun trennen(): Trennergebnis {
        val zugang = tresor.lesen() ?: return Trennergebnis.NichtGekoppelt

        val offen = rueckstand()
        if (offen > 0) return Trennergebnis.Rueckstand(offen)

        val antwort = netzweg.postJson(
            Serveradresse.pair(basis),
            JSONObject().put("aktion", "trennen").toString(),
            kopfzeilen(zugang),
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
                    else -> Trennergebnis.NurLokal(artAus(o, antwort.code))
                }
            }
        }
    }

    private fun kopfzeilen(zugang: Zugangsdaten): Map<String, String> = mapOf(
        "X-Device-Id" to zugang.geraeteKennung,
        "X-Api-Key" to zugang.schluessel,
    )

    /**
     * Von der Serverantwort zur Ursache. Das Feld `error` schlägt den
     * Zahlencode; der Code trägt nur, wo kein Schlüssel dasteht.
     */
    private fun artAus(o: JSONObject?, code: Int): Abweisung {
        return when (o?.optString("error").orEmpty()) {
            "auth" -> Abweisung.SITZUNG_UNGUELTIG
            "abgelaufen" -> Abweisung.SITZUNG_ABGELAUFEN
            "device_limit" -> Abweisung.ZU_VIELE_GERAETE
            "zu_viele_versuche", "zu_viele_sitzungen" -> Abweisung.ZU_VIELE_VERSUCHE
            "nicht_beansprucht" -> Abweisung.UNBEKANNT
            "server" -> Abweisung.SERVERFEHLER
            else -> when {
                code == 401 -> Abweisung.SITZUNG_UNGUELTIG
                code == 410 -> Abweisung.SITZUNG_ABGELAUFEN
                code == 429 -> Abweisung.ZU_VIELE_VERSUCHE
                code >= 500 -> Abweisung.SERVERFEHLER
                else -> Abweisung.UNBEKANNT
            }
        }
    }

    /**
     * Der Freitext des Servers, sofern vorhanden. Er wird als **zweite Zeile**
     * angezeigt und ersetzt nie den eigenen Text: Die Meldungen von `pair.php`
     * sind für die Weboberfläche geschrieben — ganze Sätze in Fließtext.
     * Dieselbe Überlegung wie in `Pair.mc`.
     *
     * BIS ZU DIESEM PAKET STAND HIER „ohne Umlaute". Das galt einmal und
     * gilt seit S5 nicht mehr: `pair.php` schreibt „Es sind bereits 5
     * Geräte …". Aufgefallen ist es an einem Prüffall, der auf den
     * umlautlosen Text prüfte und deshalb gegen den heutigen Server
     * fehlschlug — die Annahme war seit Web 13.0.0 falsch und stand
     * unbemerkt in drei Dateien.
     */
    private fun meldungAus(o: JSONObject?): String? =
        o?.optString("meldung")?.ifEmpty { null }

    private fun alsJson(text: String?): JSONObject? =
        try {
            if (text.isNullOrBlank()) null else JSONObject(text)
        } catch (e: org.json.JSONException) {
            null
        }

    companion object {
        /**
         * Der Abfragetakt von `status` in Millisekunden (Vertrag 1a.2:
         * „höchstens alle fünf Sekunden").
         *
         * ER STEHT HIER UND NICHT IN DER OBERFLÄCHE, obwohl die Oberfläche
         * ihn einhält: Die Zahl gehört zum Vertrag, nicht zum Bildschirm. Wer
         * den Dienst liest, soll sehen, wie oft er gefragt werden darf, ohne
         * eine Activity aufzuschlagen.
         */
        const val ABFRAGETAKT_MS = 5_000L

        /**
         * Rückfall, wenn der Server keine Frist nennt. Der Vertrag nennt 600 s
         * (1a.1); die Zahl steht hier nur, damit die Anzeige nicht bei null
         * beginnt — verlässlich ist `rest_s` aus `status`.
         */
        const val FRIST_VORGABE = 600
    }
}
