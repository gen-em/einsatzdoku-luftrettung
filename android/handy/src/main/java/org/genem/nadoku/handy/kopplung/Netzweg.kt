package org.genem.nadoku.handy.kopplung

import java.io.IOException
import java.net.HttpURLConnection
import java.net.URL
import javax.net.ssl.SSLException

/**
 * Was vom Server zurückkam.
 *
 * DIE UNTERSCHEIDUNG IST NICHT KOSMETISCH. „Kein Netz" und „der Server sagt
 * nein" verlangen entgegengesetzte Reaktionen: Das eine wiederholt man später
 * unverändert, das andere nie wieder. Die Garmin-Uhr drückt denselben
 * Unterschied über negative Zahlencodes aus (`Pair.onResponse`, `code < 0`);
 * in Kotlin ist ein eigener Typ die ehrlichere Form derselben Auskunft.
 */
sealed interface Netzantwort {
    data class Server(val code: Int, val koerper: String?) : Netzantwort
    data class KeineVerbindung(val ursache: String) : Netzantwort
}

/**
 * Der Sendeweg. Als Schnittstelle, damit die Prüffälle einen eigenen
 * einsetzen können — und damit die Auswertung der Antworten (der Teil, an dem
 * etwas falsch sein kann) ohne Netz prüfbar ist.
 */
interface Netzweg {
    fun postJson(adresse: String, koerper: String, kopfzeilen: Map<String, String>): Netzantwort
}

/**
 * Der echte Sendeweg: `HttpURLConnection`, ein Bordmittel (E-S4-04).
 *
 * KEINE FREMDE HTTP-BIBLIOTHEK. Was hier gebraucht wird, ist ein POST mit drei
 * Kopfzeilen und einem Zeitlimit. Dafür eine Bibliothek mitzunehmen, hieße,
 * sie in `docs/Lizenzen.md` zu führen und bei jeder Sicherheitsmeldung zu
 * verfolgen — für Code, den die Plattform mitbringt.
 *
 * ZEITLIMITS SIND PFLICHT, nicht Feinschliff: Ohne sie wartet die App im
 * Funkloch, bis Android sie abräumt. Die Werte sind großzügig, weil die
 * Gegenseite ein Mobilfunknetz ist.
 */
class HttpNetzweg(
    private val verbindungslimitMs: Int = 15_000,
    private val lesegrenzeMs: Int = 30_000,
) : Netzweg {

    override fun postJson(
        adresse: String,
        koerper: String,
        kopfzeilen: Map<String, String>,
    ): Netzantwort {
        var v: HttpURLConnection? = null
        return try {
            v = (URL(adresse).openConnection() as HttpURLConnection).apply {
                requestMethod = "POST"
                doOutput = true
                connectTimeout = verbindungslimitMs
                readTimeout = lesegrenzeMs
                useCaches = false
                /* KEINE UMLEITUNG WIRD BEFOLGT (seit 0.13.0).
                 *
                 * Die Vorgabe der Plattform ist `true`: Antwortet die
                 * Gegenstelle mit 3xx, ruft `HttpURLConnection` die neue
                 * Adresse von sich aus auf — und nimmt die Kopfzeilen mit.
                 * In diesen Kopfzeilen steht `X-Api-Key`, der
                 * Geraeteschluessel. Er ginge damit an eine Adresse, die
                 * nicht die ist, an die die App ihn schicken wollte.
                 *
                 * BEI EIGENER ADRESSE UND TLS IST DAS FOLGENLOS, und genau
                 * deshalb steht die Zeile hier: Sie kostet nichts und deckt
                 * den Fall ab, in dem es einmal nicht mehr folgenlos waere —
                 * ein falsch eingerichteter Reverse Proxy, eine
                 * Weiterleitung auf eine fremde Domaene, ein Netz, das sich
                 * dazwischensetzt. Eine Umleitung ist an dieser Stelle
                 * ohnehin kein erwarteter Fall: Die App spricht mit genau
                 * zwei Endpunkten einer fest eingebauten Adresse (R63).
                 *
                 * FOLGE FUER DIE FEHLERBEHANDLUNG: Der 3xx kommt als
                 * `Netzantwort.Server(3xx, …)` heraus — die Stromwahl unten
                 * (`code in 200..399`) liest ihn aus dem Eingabestrom, also
                 * den Rumpf der Umleitungsantwort, und der ist meist leer.
                 * Erfolg ist im Kopplungsdienst nur `code == 200`; alles
                 * andere geht durch `artAus()`, und das ordnet den 3xx
                 * ausdruecklich als SERVERFEHLER ein; ohne jene Zeile hiesse
                 * er "unbekannt", und das sagt der Nutzerin nichts. Der Sendeweg
                 * ist davon unberuehrt: `Sendeantwort.lese()` behandelt
                 * alles ausser 200/400/401/413 als "spaeter erneut" — es
                 * geht also kein Paket verloren.
                 *
                 * Gefunden bei der Play-Console-Vorbereitung (Schritt 6). */
                instanceFollowRedirects = false
                setRequestProperty("Content-Type", "application/json; charset=utf-8")
                setRequestProperty("Accept", "application/json")
                kopfzeilen.forEach { (name, wert) -> setRequestProperty(name, wert) }
            }
            v.outputStream.use { it.write(koerper.toByteArray(Charsets.UTF_8)) }

            val code = v.responseCode
            // Ab 400 liegt der Körper im Fehlerstrom. Ihn zu übergehen hieße,
            // genau die Meldung wegzuwerfen, die sagt, was zu tun ist
            // ("device_limit": erst ein Gerät im Web löschen).
            val strom = if (code in 200..399) v.inputStream else v.errorStream
            val text = strom?.bufferedReader(Charsets.UTF_8)?.use { it.readText() }
            Netzantwort.Server(code, text)
        } catch (e: SSLException) {
            // Eigener Zweig, weil die Ursache eine andere ist: nicht "kein
            // Netz", sondern "die Gegenstelle ist nicht die, für die sie sich
            // ausgibt". Nur HTTPS (E-S4-14) heisst auch: keinen Ausweg anbieten.
            Netzantwort.KeineVerbindung("TLS: ${e.message}")
        } catch (e: IOException) {
            Netzantwort.KeineVerbindung(e.message ?: e.javaClass.simpleName)
        } finally {
            v?.disconnect()
        }
    }
}
