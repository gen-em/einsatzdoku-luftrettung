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
