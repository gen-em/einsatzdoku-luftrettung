package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Nachrichtenformat
import org.json.JSONObject
import java.io.File
import kotlin.random.Random

/**
 * Der Ereignispuffer der Uhr (E-S4-10) — **was noch nicht quittiert ist**.
 *
 * WARUM DIE UHR ÜBERHAUPT PUFFERT: Weil Bluetooth abreißt. Die Uhr am
 * Handgelenk, das Handy im Rucksack im Fahrzeug — zehn Meter und eine
 * Fahrzeugwand genügen. Ein Ereignis, das in diesem Augenblick nur gesendet
 * und nicht gemerkt würde, wäre weg, und niemand sähe es: Weder die Uhr (sie
 * hat gesendet) noch das Handy (es hat nie etwas bekommen). Genau das ist der
 * Fehler, den ein Puffer mit Quittung unmöglich macht.
 *
 * WAS GESPEICHERT WIRD, SIND DIE FERTIGEN NACHRICHTEN — die Bytes, die auf
 * die Leitung gehen, nicht die Absicht dahinter. Damit ist „identisch
 * nachliefern" (E-S4-10) keine Zusage, die man einhalten muss, sondern eine,
 * die man nicht brechen kann: Es gibt gar keinen zweiten Weg, die Nachricht
 * noch einmal zu bilden.
 *
 * KEINE DATENBANK, SONDERN EINE DATEI. Auf dem Handy liegt SQLite, weil dort
 * zwölf Stunden Spur mit zehntausenden Punkten anfallen. Hier sind es ein
 * paar Dutzend Zeilen je Dienst; eine Tabelle dafür wäre Aufwand ohne Gewinn.
 * Geschrieben wird über eine Zwischendatei und dann umbenannt — ein Absturz
 * mitten im Schreiben hinterlässt so den **alten** Stand und keinen halben.
 */
class Uhrpuffer(private val ablage: Ablage, private val zufall: Random = Random.Default) {

    /** Wo der Puffer liegt. Getrennt, damit er sich ohne Android prüfen lässt. */
    interface Ablage {
        fun lies(): String
        fun schreib(inhalt: String)
    }

    /** Die Ablage als Datei — der Weg in der App. */
    class DateiAblage(private val datei: File) : Ablage {
        override fun lies(): String = if (datei.exists()) datei.readText() else ""

        override fun schreib(inhalt: String) {
            val zwischen = File(datei.parentFile, datei.name + ".neu")
            zwischen.writeText(inhalt)
            if (!zwischen.renameTo(datei)) {
                datei.writeText(inhalt)
                zwischen.delete()
            }
        }
    }

    private var kopf = JSONObject()
    private val warteschlange = ArrayList<String>()

    init {
        laden()
    }

    /**
     * Die Kennung dieser Installation auf dieser Uhr.
     *
     * Sie entsteht beim ersten Zugriff und bleibt. Wird die App neu
     * eingerichtet, ist es eine **andere** Uhr — und das ist richtig so: Der
     * Ereigniszähler fängt dann wieder bei 1 an, und das Handy dürfte die
     * neuen Nummern nicht für alte halten (siehe `Puffer.uhrTabellen`).
     */
    val uhrId: String get() = kopf.getString(SCHLUESSEL_UHR)

    /** Bis zu welcher Nummer das Handy quittiert hat. */
    val quittiertBis: Long get() = kopf.optLong(SCHLUESSEL_QUITTIERT, 0L)

    /** Wie viele Nachrichten auf ihre Quittung warten. */
    fun anzahl(): Int = warteschlange.size

    /** Die wartenden Nachrichten, in der Reihenfolge ihres Entstehens. */
    fun wartende(): List<ByteArray> = warteschlange.map { it.toByteArray(Charsets.UTF_8) }

    /**
     * Die nächste Ereignisnummer — fortlaufend, lückenlos, über Neustarts
     * hinweg (E-S4-10).
     *
     * Sie wird **vor** dem Gebrauch gesichert. Ein Absturz dazwischen darf
     * eine Nummer überspringen (das Handy merkt es an der Lücke und wartet),
     * niemals eine doppelt vergeben (dann verschwände ein Ereignis
     * stillschweigend als vermeintliche Doppelzustellung).
     */
    fun naechsteNr(): Long {
        val n = kopf.optLong(SCHLUESSEL_NR, 0L) + 1
        kopf.put(SCHLUESSEL_NR, n)
        sichern()
        return n
    }

    /** Eine fertige Nachricht in die Warteschlange stellen. */
    fun anhaengen(rumpf: ByteArray) {
        warteschlange.add(String(rumpf, Charsets.UTF_8))
        sichern()
    }

    /**
     * Die Quittung verbuchen: alles bis [bisNr] darf weg.
     *
     * @return wie viele Nachrichten dadurch verschwunden sind
     */
    fun quittieren(bisNr: Long): Int {
        if (bisNr <= quittiertBis) return 0
        val vorher = warteschlange.size
        warteschlange.removeAll { zeile ->
            val nr = Nachrichtenformat.liesMeldung(zeile.toByteArray(Charsets.UTF_8))?.nr
            nr != null && nr <= bisNr
        }
        kopf.put(SCHLUESSEL_QUITTIERT, bisNr)
        sichern()
        return vorher - warteschlange.size
    }

    /**
     * Der Zählerspeicher für die `wm-`-Kennungen (E-S4-09).
     *
     * Er liegt in derselben Datei wie der Ereigniszähler — beide müssen
     * dasselbe überleben, und zwei Dateien wären zwei Gelegenheiten,
     * auseinanderzulaufen.
     */
    fun kennungsspeicher(): Kennungen.Zaehlerspeicher = object : Kennungen.Zaehlerspeicher {
        override fun lies(): Long = kopf.optLong(SCHLUESSEL_KENNUNG, 0L)

        override fun schreib(wert: Long) {
            kopf.put(SCHLUESSEL_KENNUNG, wert)
            sichern()
        }
    }

    private fun laden() {
        val zeilen = ablage.lies().split("\n").filter { it.isNotBlank() }
        kopf = if (zeilen.isEmpty()) JSONObject() else try {
            JSONObject(zeilen.first())
        } catch (e: Exception) {
            /* EIN UNLESBARER KOPF IST EIN NEUER ANFANG, kein Absturz. Die
             * Alternative wäre eine App, die sich nach einem halben Schreiben
             * nicht mehr starten lässt — auf einem Gerät ohne Tastatur und
             * ohne Dateiverwaltung. */
            JSONObject()
        }
        if (!kopf.has(SCHLUESSEL_UHR)) {
            kopf.put(SCHLUESSEL_UHR, "u-%05d%05d".format(zufall.nextInt(0, 1 shl 16), zufall.nextInt(0, 1 shl 16)))
            sichern()
        }
        warteschlange.clear()
        warteschlange.addAll(zeilen.drop(1))
    }

    private fun sichern() {
        ablage.schreib((listOf(kopf.toString()) + warteschlange).joinToString("\n") + "\n")
    }

    private companion object {
        const val SCHLUESSEL_UHR = "uhr"
        const val SCHLUESSEL_NR = "nr"
        const val SCHLUESSEL_QUITTIERT = "qb"
        const val SCHLUESSEL_KENNUNG = "kz"
    }
}
