package org.genem.nadoku.gemeinsam

import org.json.JSONArray
import org.json.JSONObject

/**
 * Das Nachrichtenformat zwischen Uhr und Handy (E-S4-10) — **die eine
 * Beschreibung, von beiden Seiten benutzt**.
 *
 * WARUM ES IN `gemeinsam/` LIEGT UND NICHT ZWEIMAL: Ein Format, das zwei
 * Programme unabhängig voneinander lesen und schreiben, ist zwei Formate,
 * sobald sich eines ändert. Der Fehler fiele dabei nicht auf — der Data Layer
 * meldet keine unverstandene Nachricht, er stellt sie zu und niemand tut
 * etwas damit. Beide Module übersetzen deshalb denselben Quelltext.
 *
 * WAS NICHT DARIN VORKOMMT, IST DER PUNKT (E-S4-11): kein `api_key`, keine
 * `device_id`, keine Serveradresse. Die Uhr spricht nur mit dem Handy, nie
 * mit dem Server; ein gestohlener Uhr-Speicher gibt nichts preis. Der
 * Prüffall `NachrichtenformatTest.keine_zugangsdaten_im_format` zählt die
 * Schlüssel nach, statt es zu behaupten.
 *
 * DAS ZEITMASS IST DIE UHR (E-R45-1): Jedes Ereignis trägt den Zeitstempel
 * **der Uhr**, in Millisekunden seit der Epoche. Nicht den des Handys — die
 * Uhr war dabei, das Handy liegt im Rucksack.
 */
object Nachrichtenformat {

    /** Uhr → Handy: ein Bedienereignis. */
    const val PFAD_EREIGNIS = "/nadoku/ereignis"

    /** Handy → Uhr: die Quittung mit der höchsten übernommenen Nummer. */
    const val PFAD_QUITTUNG = "/nadoku/quittung"

    /** Handy → Uhr: der Anzeigestand (Dienst, Einsatz, Phasen). */
    const val PFAD_STAND = "/nadoku/stand"

    fun schreibe(m: Uhrmeldung): ByteArray = JSONObject().apply {
        put("uhr", m.uhrId)
        put("nr", m.nr)
        put("art", m.art.gespeichert)
        put("zeit", m.zeitMs)
        if (m.phase != null) put("phase", m.phase)
        if (m.einsatzRef != null) put("einsatz_ref", m.einsatzRef)
    }.toString().toByteArray(Charsets.UTF_8)

    /**
     * @return die Meldung, oder `null`, wenn die Nachricht unbrauchbar ist.
     *
     * **Unbrauchbar heißt: fallen lassen, nicht abstürzen.** Auf dem Handy
     * läuft dieser Weg in einem Dienst, den das System startet; eine geworfene
     * Ausnahme beendete ihn. Eine Nachricht aus einer künftigen Fassung der
     * Uhr-App wäre genau so ein Fall.
     */
    fun liesMeldung(rumpf: ByteArray): Uhrmeldung? = try {
        val o = JSONObject(String(rumpf, Charsets.UTF_8))
        val art = Ereignisart.ausGespeichertem(o.getString("art"))
        val uhrId = o.getString("uhr")
        if (art == null || uhrId.isBlank()) null
        else Uhrmeldung(
            uhrId = uhrId,
            nr = o.getLong("nr"),
            art = art,
            zeitMs = o.getLong("zeit"),
            phase = if (o.has("phase")) o.getInt("phase") else null,
            einsatzRef = if (o.has("einsatz_ref")) o.getString("einsatz_ref") else null,
        )
    } catch (e: Exception) {
        null
    }

    fun schreibe(q: Quittung): ByteArray =
        JSONObject().put("bis_nr", q.bisNr).toString().toByteArray(Charsets.UTF_8)

    fun liesQuittung(rumpf: ByteArray): Quittung? = try {
        Quittung(JSONObject(String(rumpf, Charsets.UTF_8)).getLong("bis_nr"))
    } catch (e: Exception) {
        null
    }

    fun schreibe(s: Standmeldung): ByteArray = JSONObject().apply {
        put("dienst", s.dienstLaeuft)
        put("modus", s.modus.gespeichert)
        put("einsatz", s.einsatzLaeuft)
        put("phase", s.laufendePhase)
        if (s.laufendeSeit != null) put("seit", s.laufendeSeit)
        /* NUR WENN ES ETWAS ZU SAGEN GIBT (E-S5Z-15). Ein fehlender Schlüssel
         * heisst „diese Handy-Fassung kennt den Ortungszustand nicht" — und
         * eine Uhr, die dann nichts anzeigt, sagt genau das Richtige. Ein
         * leerer Wert wäre eine Aussage, ein fehlender ist keine. */
        if (s.ortung != null) put("ortung", s.ortung)
        put(
            "phasen",
            JSONArray().apply {
                s.phasen.forEach { put(JSONArray().put(it.nummer).put(it.hhmm)) }
            },
        )
    }.toString().toByteArray(Charsets.UTF_8)

    fun liesStand(rumpf: ByteArray): Standmeldung? = try {
        val o = JSONObject(String(rumpf, Charsets.UTF_8))
        val liste = o.optJSONArray("phasen") ?: JSONArray()
        Standmeldung(
            dienstLaeuft = o.getBoolean("dienst"),
            modus = Modus.ausGespeichertem(o.optString("modus")),
            einsatzLaeuft = o.getBoolean("einsatz"),
            laufendePhase = o.optInt("phase", Phasen.FREI),
            laufendeSeit = if (o.has("seit")) o.getString("seit") else null,
            ortung = if (o.has("ortung")) o.getString("ortung") else null,
            phasen = buildList {
                for (i in 0 until liste.length()) {
                    val p = liste.getJSONArray(i)
                    add(Phasenmarke(p.getInt(0), p.getString(1)))
                }
            },
        )
    } catch (e: Exception) {
        null
    }
}

/** Was an der Uhr ausgelöst wurde. */
enum class Ereignisart(val gespeichert: String) {
    DIENST_BEGINNEN("dienst_beginnen"),
    DIENST_BEENDEN("dienst_beenden"),
    PHASE("phase"),
    EINSATZ_ABSCHLIESSEN("einsatz_abschliessen");

    companion object {
        fun ausGespeichertem(wert: String?): Ereignisart? =
            entries.firstOrNull { it.gespeichert == wert }
    }
}

/**
 * Ein Ereignis der Uhr auf dem Weg zum Handy.
 *
 * @param uhrId Kennung **dieser Installation** auf **dieser** Uhr. Sie steht
 *   hier, weil [nr] allein nicht reicht: Wird die Uhr zurückgesetzt oder die
 *   App neu eingerichtet, fängt der Zähler wieder bei 1 an — das Handy hielte
 *   jedes Ereignis für eine Doppelzustellung und verwürfe es stillschweigend.
 *   Mit der Kennung führt es die Buchhaltung je Uhr, und eine neue Uhr fängt
 *   sauber an.
 * @param nr fortlaufend und lückenlos, überlebt Neustarts (E-S4-10).
 * @param zeitMs der Zeitstempel **der Uhr** (E-R45-1).
 * @param einsatzRef nur beim Ereignis, das einen Einsatz **eröffnet**: die auf
 *   der Uhr gebildete `wm-`-Kennung. Sie ist der Idempotenz-Anker über den
 *   Funkabriss (E-S4-09) — meldet die Uhr dasselbe Ereignis nach verlorener
 *   Quittung erneut, erkennt das Handy den Einsatz daran wieder, statt einen
 *   zweiten anzulegen.
 */
data class Uhrmeldung(
    val uhrId: String,
    val nr: Long,
    val art: Ereignisart,
    val zeitMs: Long,
    val phase: Int? = null,
    val einsatzRef: String? = null,
)

/**
 * Die Quittung: **bis hierher ist alles übernommen** (E-S4-10).
 *
 * Sie nennt die höchste **lückenlos** übernommene Nummer, nicht die höchste
 * überhaupt gesehene. Der Unterschied entscheidet über Datenverlust: Ginge
 * Nr. 5 verloren und käme Nr. 6 an, hieße „bis 6" für die Uhr, dass sie
 * Nr. 5 löschen darf — und niemand sähe je, dass sie fehlt.
 */
data class Quittung(val bisNr: Long)

/** Eine gesetzte Phase, wie die Uhr sie anzeigt. */
data class Phasenmarke(val nummer: Int, val hhmm: String)

/**
 * Der Anzeigestand vom Handy (E-S4-10).
 *
 * DIE UHR BESITZT DEN ZUSTAND NICHT, sie spiegelt ihn — der Dienst läuft am
 * Handy. Auch der Modus kommt von dort: Im Nur-Aufzeichnen-Modus zeigt die
 * Uhr keine Phasenknöpfe (E-S4-20).
 */
data class Standmeldung(
    val dienstLaeuft: Boolean,
    val modus: Modus,
    val einsatzLaeuft: Boolean,
    val laufendePhase: Int,
    val laufendeSeit: String?,
    val phasen: List<Phasenmarke>,
    /**
     * Wie es um die Ortung steht — einer der [Ortungscode]; `null`, wenn das
     * Handy es nicht mitteilt (E-S5Z-15).
     *
     * WARUM DIE UHR DAS ERFÄHRT. Sie kann einen Dienst beginnen, während der
     * Standort des Handys aus ist — fragen kann sie niemanden, das Handy
     * liegt in der Tasche. Der Dienst wird trotzdem durchgelassen (F-S5Z-01
     * (c)), aber eine Uhr, die dann „Dienst läuft" zeigt, verschweigt genau
     * die Lücke, die hinterher niemand erklären kann. Dieselbe Begründung wie
     * bei `dienst_schwebt` (E-S4-10).
     *
     * ES REIST KEIN ZUGANGSDATUM MIT (E-S4-11): ein Kurzcode, sonst nichts.
     * Die Schlüsselmenge der **Uhrmeldung** — der Richtung, in der die
     * Sicherheitsaussage steht — bleibt davon unberührt.
     */
    val ortung: String? = null,
)

/**
 * Die Kurzcodes des Ortungszustands im Nachrichtenformat (E-S5Z-15).
 *
 * WARUM SIE IN `gemeinsam/` STEHEN und nicht auf jeder Seite als Zeichenkette:
 * Zwei Programme, die dieselben sechs Wörter unabhängig voneinander tippen,
 * tippen früher oder später eines verschieden — und der Data Layer meldet
 * keine unverstandene Nachricht, er stellt sie zu und niemand tut etwas
 * damit. Es ist derselbe Grund, aus dem dieses ganze Format hier liegt.
 *
 * Sie sind kurz, weil jede Nachricht über Funk geht; sie sind Zeichenketten
 * und keine Zahlen, weil eine Zahl in einem Protokollauszug nichts sagt.
 */
object Ortungscode {
    const val FREIGABE_FEHLT = "frei_fehlt"
    const val STANDORT_AUS = "aus"
    const val SUCHT = "sucht"
    const val KEIN_SIGNAL = "kein"
    const val UNGENAU = "ungenau"
    const val OK = "ok"

    /**
     * Die Codes, bei denen **nichts aufgezeichnet wird** — die Uhr zeigt für
     * alle vier denselben Satz.
     *
     * Ein Wortlaut für vier Ursachen, weil die Uhr keine davon beheben kann:
     * Das tut das Handy, und das vibriert. Was die Uhr beitragen kann, ist
     * die Nachricht „gerade entsteht keine Spur" ans Handgelenk.
     */
    val OHNE_AUFZEICHNUNG = setOf(FREIGABE_FEHLT, STANDORT_AUS, KEIN_SIGNAL, UNGENAU)

    /**
     * **Was die Uhr aus einem Code macht — drei Anzeigen für sechs Stufen.**
     *
     * Die Uhr stuft nicht ab: Sie kann keine der vier Ursachen beheben, das
     * tut das Handy. Für sie zerfallen die sechs Stufen deshalb in drei
     * Fälle, und diese Zusammenfassung steht **einmal** hier statt zweimal
     * ausgeschrieben — in der Anzeige der Uhr und in der Entscheidung des
     * Handys, wann es überhaupt meldet (E3).
     */
    enum class Anzeige {
        /** Rot: „keine Ortung · keine Aufzeichnung". */
        KEINE_ORTUNG,

        /** Sand: „GPS sucht". */
        SUCHEN,

        /** Nichts — es läuft, oder das Handy sagt nichts dazu. */
        STILL,
    }

    fun anzeige(code: String?): Anzeige = when {
        code in OHNE_AUFZEICHNUNG -> Anzeige.KEINE_ORTUNG
        code == SUCHT -> Anzeige.SUCHEN
        else -> Anzeige.STILL
    }
}

/**
 * Der Weg, auf dem eine Nachricht geht — **die Naht zur Hardware**.
 *
 * Dahinter steckt in der App der `MessageClient` des Wear Data Layer
 * (E-S4-10); im Prüfstand eine Attrappe. Diese Trennung ist der einzige Grund,
 * warum von C2 überhaupt etwas belegt ist: Ohne Uhr und ohne Emulator
 * (E-R45-7, E-R45-8) lässt sich der Data Layer nicht ausführen. Alles, was
 * hier drüber liegt — Puffer, Quittung, Nachlieferung, Doppelzustellung —
 * läuft dagegen auf der JVM.
 */
interface Nachrichtenweg {
    /**
     * @return `true`, wenn die Nachricht **zugestellt** wurde. `false` ist
     *   kein Fehler, sondern der Normalfall am Rand des Funklochs: Die
     *   Nachricht bleibt gepuffert und wird später identisch nachgeliefert.
     */
    fun sende(pfad: String, rumpf: ByteArray): Boolean
}
