package org.genem.nadoku.handy

import java.io.Closeable
import java.net.InetAddress
import java.net.ServerSocket
import java.util.concurrent.CopyOnWriteArrayList

/**
 * Ein winziger HTTP-Server für die Prüffälle.
 *
 * WARUM SELBST GEBAUT UND KEINE BIBLIOTHEK. Der übliche Weg wäre
 * `MockWebServer` (OkHttp). Das hieße, für die Prüfung eine Abhängigkeit
 * aufzunehmen, die E-S4-04 für die App ausschließt — und sie stünde dann in
 * `libs.versions.toml`, wo die Liste der drei zugelassenen Fremdbestandteile
 * steht. Was hier gebraucht wird, sind sechzig Zeilen `ServerSocket`.
 * (`com.sun.net.httpserver` scheidet aus: Beim Übersetzen gegen `android.jar`
 * ist es nicht sichtbar.)
 *
 * WARUM ÜBERHAUPT EIN ECHTER SERVER und keine Attrappe des `Netzweg`: Mit
 * einer Attrappe bliebe [org.genem.nadoku.handy.kopplung.HttpNetzweg]
 * ungeprüft — also genau der Teil, der Kopfzeilen setzt, den Fehlerstrom bei
 * 4xx liest und Zeitlimits einhält. Der Prüfstand spricht deshalb echtes HTTP
 * über die Rückschleife.
 */
class PruefServer : Closeable {

    /** Was der Server als Nächstes antwortet. */
    var status: Int = 200
    var antwortkoerper: String = """{"ok":true}"""

    /**
     * Antwort aus der Anfrage gerechnet — für alles, was vom Körper abhängt.
     *
     * `ingest.php` antwortet mit `next_seq`, und die hängt daran, wie viele
     * Punkte ankamen. Ein fester Antworttext könnte das nicht nachstellen, und
     * eine Kette von Teilstücken bliebe damit ungeprüft.
     *
     * Wirft die Funktion, bricht die Verbindung ohne Antwort ab — genau das,
     * was ein Funkloch mitten in der Kette tut.
     */
    var antwortAus: ((koerper: String) -> String)? = null

    /** Alles, was hereinkam — die Prüffälle sehen hier nach. */
    val anfragen = CopyOnWriteArrayList<Anfrage>()

    data class Anfrage(
        val zeile: String,
        val kopfzeilen: Map<String, String>,
        val koerper: String,
    )

    private val buchse = ServerSocket(0, 16, InetAddress.getByName("127.0.0.1"))
    private val faden: Thread

    /** Basisadresse, wie die App sie kennt — mit abschließendem Strich. */
    val basis: String get() = "http://127.0.0.1:${buchse.localPort}/"

    init {
        faden = Thread {
            while (!buchse.isClosed) {
                try {
                    buchse.accept().use { v ->
                        val ein = v.getInputStream().bufferedReader(Charsets.UTF_8)
                        val zeile = ein.readLine() ?: return@use

                        val kopf = mutableMapOf<String, String>()
                        while (true) {
                            val z = ein.readLine() ?: break
                            if (z.isEmpty()) break
                            val t = z.indexOf(':')
                            if (t > 0) kopf[z.substring(0, t).trim()] = z.substring(t + 1).trim()
                        }

                        // Genau so viele Zeichen lesen, wie angekündigt sind:
                        // readText() blockierte bis zum Verbindungsende, und
                        // die Gegenseite wartet ja auf die Antwort.
                        val laenge = kopf.entries
                            .firstOrNull { it.key.equals("Content-Length", true) }
                            ?.value?.toIntOrNull() ?: 0
                        val puffer = CharArray(laenge)
                        var gelesen = 0
                        while (gelesen < laenge) {
                            val n = ein.read(puffer, gelesen, laenge - gelesen)
                            if (n < 0) break
                            gelesen += n
                        }
                        anfragen.add(Anfrage(zeile, kopf, String(puffer, 0, gelesen)))

                        val text = antwortAus?.invoke(anfragen.last().koerper) ?: antwortkoerper
                        val b = text.toByteArray(Charsets.UTF_8)
                        v.getOutputStream().write(
                            ("HTTP/1.1 $status ${text(status)}\r\n" +
                                "Content-Type: application/json; charset=utf-8\r\n" +
                                "Content-Length: ${b.size}\r\n" +
                                "Connection: close\r\n\r\n").toByteArray(Charsets.UTF_8)
                        )
                        v.getOutputStream().write(b)
                        v.getOutputStream().flush()
                    }
                } catch (e: Exception) {
                    if (buchse.isClosed) return@Thread
                }
            }
        }
        faden.isDaemon = true
        faden.start()
    }

    private fun text(code: Int) = when (code) {
        200 -> "OK"; 400 -> "Bad Request"; 401 -> "Unauthorized"
        404 -> "Not Found"; 409 -> "Conflict"; 429 -> "Too Many Requests"
        500 -> "Internal Server Error"; else -> "Status"
    }

    /** Der Körper der letzten Anfrage als JSON — der Prüffall sieht darin nach. */
    fun letzterKoerper(): org.json.JSONObject = org.json.JSONObject(anfragen.last().koerper)

    override fun close() {
        buchse.close()
        faden.join(2000)
    }
}
