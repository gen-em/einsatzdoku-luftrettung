package org.genem.nadoku.handy.aufzeichnung

import org.genem.nadoku.gemeinsam.Ortungscode

/**
 * In welchem Zustand die Ortung ist (E-S5Z-01).
 *
 * SECHS STUFEN UND NICHT ZWEI, weil „GPS an" vier verschiedene Lagen
 * zusammenwarf, von denen drei bedeuten, dass **nichts aufgezeichnet wird**:
 * Standort aus, kein Empfang, Signal zu ungenau. Die App behauptete in allen
 * dreien, sie zeichne auf (B-S5Z-07) — genau die Art Aussage, gegen die
 * E-S4-10 und B-S4-09 gebaut sind.
 *
 * Die Reihenfolge der Einträge ist die der Schwere: von „es fehlt die
 * Freigabe" bis „es läuft".
 */
enum class Ortungsstand {
    /** `ACCESS_FINE_LOCATION` ist nicht erteilt. */
    FREIGABE_FEHLT,

    /**
     * Der **GPS-Anbieter** ist aus.
     *
     * Absichtlich der Anbieter und nicht `isLocationEnabled()`: Im Modus
     * „Stromsparen" ist der Standort an und GPS aus — aufgezeichnet wird aber
     * nur mit GPS.
     */
    STANDORT_AUS,

    /** Anbieter an, der erste Fund steht noch aus, die Frist läuft. */
    SUCHT,

    /** Anbieter an, aber es kommt nichts mehr. */
    KEIN_SIGNAL,

    /** Es kommen Funde, aber keiner ist brauchbar — die Ausdünnung verwirft sie. */
    UNGENAU,

    /** Ein brauchbarer Fund liegt innerhalb der Frist. Nur hier wird aufgezeichnet. */
    OK,
    ;

    /** Wird in diesem Zustand aufgezeichnet? Genau einer sagt ja. */
    val zeichnetAuf: Boolean get() = this == OK

    /** Ist das ein Zustand, vor dem gewarnt werden muss (E-S5Z-04)? */
    val warnt: Boolean get() = this != OK && this != SUCHT

    /**
     * Der Kurzcode für die Standmeldung an die Uhr (E-S5Z-15).
     *
     * Die Zeichenketten stehen in `gemeinsam/`, weil beide Module sie lesen —
     * hier steht nur die Zuordnung.
     */
    val code: String
        get() = when (this) {
            FREIGABE_FEHLT -> Ortungscode.FREIGABE_FEHLT
            STANDORT_AUS -> Ortungscode.STANDORT_AUS
            SUCHT -> Ortungscode.SUCHT
            KEIN_SIGNAL -> Ortungscode.KEIN_SIGNAL
            UNGENAU -> Ortungscode.UNGENAU
            OK -> Ortungscode.OK
        }
}

/** Was mit der Warnmeldung (ID 3) zu geschehen hat. */
enum class Warnbefehl {
    /** Nichts tun — der Zustand hat sich nicht warnungsrelevant geändert. */
    NICHTS,

    /** Warnung stellen oder erneuern (Zustandswechsel oder Erinnerung). */
    POSTEN,

    /** Warnung zurückziehen — es wird wieder aufgezeichnet oder gesucht. */
    LOESCHEN,
}

/**
 * Der Ortungswächter (E-S5Z-06) — **die Zustandsmaschine ohne Android**.
 *
 * WOZU ER DA IST. `requestLocationUpdates` meldet nicht, dass nichts kommt.
 * Ein Zuhörer, der nie gerufen wird, sieht genauso aus wie einer, der auf
 * seinen ersten Fund wartet — und beide sahen bisher aus wie „GPS an".
 * Dieser Wächter misst die Stille und benennt sie.
 *
 * ER HÄLT KEINE UHR UND KEINEN FADEN. Jede Methode bekommt die Zeit
 * übergeben; damit ist er ohne Warten prüfbar, wie [Ausduenner] und
 * `Sendetakt`. Die Zeit, die der Vordergrunddienst einspeist, ist
 * `SystemClock.elapsedRealtime()` und nicht die Wanduhr: Die GPS-Zeit und die
 * Systemzeit können springen, eine Frist darf das nicht mitmachen.
 *
 * BRAUCHBAR HEISST: DIE AUSDÜNNUNG VERWIRFT DEN FUND NICHT (E-S5Z-02). Der
 * Wächter zählt nicht Sensorereignisse, sondern das, was tatsächlich in den
 * Puffer geht — dieselbe Regel, nach der `Track.mc` speichert und
 * `SyncView.mc` anzeigt. Eine Anzeige, die eine andere Schwelle benutzte als
 * die Aufzeichnung, wäre irreführend.
 *
 * @param jetztMs der Augenblick des Dienststarts
 * @param freigegeben ist `ACCESS_FINE_LOCATION` erteilt?
 * @param anbieterAn ist der GPS-Anbieter eingeschaltet?
 */
class Ortungswaechter(
    jetztMs: Long,
    private var freigegeben: Boolean,
    private var anbieterAn: Boolean,
) {

    /**
     * Beginn des laufenden Suchfensters: der Dienststart oder der Augenblick,
     * in dem der Anbieter wieder anging. Die Erstfix-Frist zählt ab hier.
     */
    private var fensterMs: Long = jetztMs

    private var letzterRoherMs: Long? = null
    private var letzterBrauchbarerMs: Long? = null

    /** Wann zuletzt gewarnt wurde — Grundlage der Erinnerung (Z-S5Z-04). */
    private var letzteWarnungMs: Long? = null

    /** Der aktuelle Zustand. */
    var stand: Ortungsstand = bewerte(jetztMs)
        private set

    /** Seit wann [stand] gilt — für „kein GPS-Signal seit 3 min". */
    var seitMs: Long = jetztMs
        private set

    /** Wann zuletzt ein **brauchbarer** Fund kam; `null` = noch keiner. */
    val letzterFundMs: Long? get() = letzterBrauchbarerMs

    /**
     * Der Anfangszustand kann schon ein Warnzustand sein — Dienststart bei
     * ausgeschaltetem Standort ist genau der Fall aus F-S5Z-01.
     */
    fun anfangsbefehl(jetztMs: Long): Warnbefehl = warnbefehl(jetztMs, gewechselt = true)

    // ---- Ereignisse --------------------------------------------------------

    /** Der GPS-Anbieter ging an: neues Suchfenster, Fristen neu. */
    fun anbieterAn(jetztMs: Long): Warnbefehl {
        if (!anbieterAn) {
            anbieterAn = true
            fensterNeu(jetztMs)
        }
        return fortschreiben(jetztMs)
    }

    /** Der GPS-Anbieter ging aus. */
    fun anbieterAus(jetztMs: Long): Warnbefehl {
        anbieterAn = false
        return fortschreiben(jetztMs)
    }

    /** Die Ortungsfreigabe hat sich geändert. */
    fun freigabe(erteilt: Boolean, jetztMs: Long): Warnbefehl {
        if (freigegeben != erteilt) {
            freigegeben = erteilt
            if (erteilt) fensterNeu(jetztMs)
        }
        return fortschreiben(jetztMs)
    }

    /**
     * Ein Fund ist eingegangen, den die Ausdünnung **verwirft** (zu grosse
     * Streuung). Er beweist, dass der Empfänger arbeitet — mehr nicht.
     */
    fun roherFund(jetztMs: Long): Warnbefehl {
        letzterRoherMs = jetztMs
        return fortschreiben(jetztMs)
    }

    /**
     * Ein Fund ist eingegangen, den die Ausdünnung **annimmt**.
     *
     * Er zählt auch als roher Fund: Ein brauchbarer Fund ist einer, und ohne
     * diese Zeile stünde nach einer Reihe guter Funde „kein Signal".
     */
    fun brauchbarerFund(jetztMs: Long): Warnbefehl {
        letzterRoherMs = jetztMs
        letzterBrauchbarerMs = jetztMs
        return fortschreiben(jetztMs)
    }

    /** Der Takt (Z-S5Z-03): Fristen nachrechnen, ohne dass etwas geschah. */
    fun tick(jetztMs: Long): Warnbefehl = fortschreiben(jetztMs)

    // ---- Innen -------------------------------------------------------------

    private fun fensterNeu(jetztMs: Long) {
        fensterMs = jetztMs
        letzterRoherMs = null
        letzterBrauchbarerMs = null
    }

    private fun fortschreiben(jetztMs: Long): Warnbefehl {
        val neu = bewerte(jetztMs)
        val gewechselt = neu != stand
        if (gewechselt) {
            stand = neu
            seitMs = jetztMs
        }
        return warnbefehl(jetztMs, gewechselt)
    }

    /**
     * Die Regel selbst — sechs Zeilen, in der Reihenfolge ihrer Vorfahrt.
     *
     * Gemessen wird immer gegen einen **Bezugspunkt**, nicht gegen den
     * Dienststart: Für „seit wann kein brauchbarer Fund" ist das der letzte
     * brauchbare Fund, und solange es keinen gab, der Beginn des Fensters.
     * Sonst meldete der erste ungenaue Fund einer Anfahrt sofort `UNGENAU`,
     * statt nach [FRIST_BETRIEB_MS].
     */
    private fun bewerte(jetztMs: Long): Ortungsstand {
        if (!freigegeben) return Ortungsstand.FREIGABE_FEHLT
        if (!anbieterAn) return Ortungsstand.STANDORT_AUS

        val brauchbar = letzterBrauchbarerMs
        if (brauchbar != null && jetztMs - brauchbar <= FRIST_BETRIEB_MS) return Ortungsstand.OK

        val roh = letzterRoherMs
        if (roh != null && jetztMs - roh <= FRIST_BETRIEB_MS) {
            // Der Empfänger arbeitet, aber nichts davon ist brauchbar.
            val seitBrauchbar = jetztMs - (brauchbar ?: fensterMs)
            return if (seitBrauchbar >= FRIST_BETRIEB_MS) Ortungsstand.UNGENAU
            else Ortungsstand.SUCHT
        }

        // Es kommt gar nichts. Vor dem ersten Fund gilt die längere Frist.
        if (roh == null && jetztMs - fensterMs < FRIST_ERSTFIX_MS) return Ortungsstand.SUCHT
        return Ortungsstand.KEIN_SIGNAL
    }

    /**
     * Wann gewarnt wird: bei jedem Wechsel **in** einen Warnzustand — auch
     * zwischen zwei Warnzuständen, denn der Grund ist ein anderer und der
     * Text auch —, und danach alle [ERINNERUNG_MS], solange er anhält
     * (F-S5Z-03: Das Handy steckt in der Tasche; eine einzige Vibration in
     * einer Anfahrt ist überhörbar).
     */
    private fun warnbefehl(jetztMs: Long, gewechselt: Boolean): Warnbefehl {
        if (!stand.warnt) {
            /* Gelöscht wird nur, was gestellt wurde. Ein `cancel` auf gut
             * Glück wäre harmlos, aber der Rückgabewert soll den Zustand
             * beschreiben und nicht eine Vorsichtsmassnahme. */
            val hatteWarnung = letzteWarnungMs != null
            letzteWarnungMs = null
            return if (hatteWarnung) Warnbefehl.LOESCHEN else Warnbefehl.NICHTS
        }
        val zuletzt = letzteWarnungMs
        if (gewechselt || zuletzt == null || jetztMs - zuletzt >= ERINNERUNG_MS) {
            letzteWarnungMs = jetztMs
            return Warnbefehl.POSTEN
        }
        return Warnbefehl.NICHTS
    }

    companion object {
        /**
         * Z-S5Z-01 — Frist bis zum ersten Fund, 120 s (F-S5Z-02).
         *
         * Ein GPS-Kaltstart ohne Netzhilfe braucht typisch 30 bis 60 s, in
         * Gebäuden länger. Kürzer warnte im Kaltstart falsch; länger liesse
         * eine ganze Anfahrt ohne Warnung.
         */
        const val FRIST_ERSTFIX_MS = 120_000L

        /**
         * Z-S5Z-02 — Frist ohne brauchbaren Fund im Betrieb, 60 s (F-S5Z-02).
         *
         * Bei 1-Hz-Abtastung sind das 60 ausgefallene Messungen. Die
         * Ausdünnung hält spätestens alle 10 s einen Punkt
         * ([Ausduenner.HOECHSTABSTAND_S]) — nach 60 s fehlen sechs
         * Pflichtpunkte. Kürzer warnte in jeder Unterführung.
         */
        const val FRIST_BETRIEB_MS = 60_000L

        /**
         * Z-S5Z-04 — Erinnerungsabstand der Warnung, 600 s (F-S5Z-03).
         *
         * Zehn Minuten: keine Dauerwarnung (Backlog 82), aber kein Dienst
         * läuft eine Stunde lang leer, ohne dass es jemand merkt.
         */
        const val ERINNERUNG_MS = 600_000L

        /**
         * Z-S5Z-03 — Wächtertakt, 10 s.
         *
         * Gleich [Ausduenner.HOECHSTABSTAND_S]: Feiner als der langsamste
         * erwartete Punkt braucht der Wächter nicht zu schauen.
         */
        const val TAKT_MS = 10_000L
    }
}

/**
 * Der Ortungszustand, wie ihn Dienst und Oberfläche teilen (E-S5Z-01, 4.4).
 *
 * Er wohnt in [org.genem.nadoku.handy.NAdokuApp.ortung] — **eine** Quelle im
 * Prozess. Zwei Exemplare wären zwei Wahrheiten über denselben Empfang, und
 * die Anzeige widerspräche der Benachrichtigung, ohne dass jemand sagen
 * könnte, welche recht hat.
 *
 * @param stand der Zustand selbst
 * @param seitMs seit wann er gilt, auf der **monotonen** Uhr
 *   (`SystemClock.elapsedRealtime()`) — für „kein GPS-Signal seit 3 min"
 * @param letzterFundMs wann zuletzt ein **brauchbarer** Fund kam; `null`,
 *   wenn seit dem Beginn des Suchfensters noch keiner kam
 */
data class Ortungslage(
    val stand: Ortungsstand,
    val seitMs: Long,
    val letzterFundMs: Long?,
)
