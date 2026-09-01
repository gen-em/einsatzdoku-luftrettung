package org.genem.nadoku.handy.dienst

import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Dienstzeile
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Phasen
import java.time.Instant

/**
 * Die Dienstklammer — der Lebenszyklus, **wortgleich zur Garmin-Uhr**
 * (`Model.mc`, E-S4-08).
 *
 * ```
 * Dienst beginnen  ->  day_ref (ad-) entsteht, erstes Ruhesegment (ar-) öffnet
 *   Phase 2..9     ->  ohne laufenden Einsatz: Einsatz (am-) beginnt,
 *                      Ruhesegment schließt                      [B5]
 *   Einsatz abschl.->  final + ended_at, danach neues Ruhesegment [B5]
 * Dienst beenden   ->  schließt beides (Sicherheitsnetz)
 * ```
 *
 * WAS HIER **NICHT** IM ARBEITSSPEICHER STEHT: der Zustand. Er liegt im
 * Puffer, und diese Klasse liest ihn bei jedem Zugriff von dort. Der Grund
 * ist der Vordergrunddienst: Die Aufzeichnung läuft weiter, während die
 * Oberfläche beendet ist, und beide sehen denselben Dienst. Ein Zustand im
 * Arbeitsspeicher wäre zwei Zustände.
 *
 * DAMIT IST DIE WIEDERAUFNAHME KEIN SONDERFALL, sondern die Regel: Nach einem
 * Absturz der App oder einem Neustart des Handys findet [laufenderDienst] den
 * Dienst vor, in dem das Gerät steckt, und die Aufzeichnung geht weiter.
 *
 * EIN ZWEITER DIENSTSTART BEI LAUFENDEM DIENST ist kein neuer Dienst
 * (E-R45-13), sondern die Anzeige „läuft seit …". [beginnen] gibt deshalb den
 * **laufenden** zurück, statt einen zweiten anzulegen — ein zweiter riss den
 * Dienst in zwei Diensttage, und niemand sähe es der App an.
 */
class Dienstklammer(
    private val puffer: Puffer,
    private val kennungen: Kennungen,
    private val ausduenner: Ausduenner = Ausduenner(),
    private val jetzt: () -> Instant = Instant::now,
) {

    fun laufenderDienst(): Dienstzeile? = puffer.laufenderDienst()

    fun laeuft(): Boolean = laufenderDienst() != null

    /**
     * Dienst beginnen — oder den laufenden zurückgeben (E-R45-13).
     *
     * @return der laufende Dienst und ob er soeben entstanden ist
     */
    fun beginnen(modus: Modus): Dienstbeginn {
        puffer.laufenderDienst()?.let { return Dienstbeginn(it, neu = false) }

        val augenblick = jetzt()
        val dienstRef = kennungen.dienst()
        val tag = Zeit.tag(augenblick)
        puffer.dienstBeginnen(dienstRef, tag, Zeit.iso(augenblick), modus.gespeichert)

        // Das erste Ruhesegment öffnet mit dem Dienst. Ohne es fiele die Zeit
        // bis zum ersten Einsatz aus der Spur — und genau aus ihr schneidet
        // der Browser später die vergessenen Einsätze heraus (E-S4-17).
        ruhesegmentOeffnen(dienstRef, tag, augenblick)

        ausduenner.zuruecksetzen()
        return Dienstbeginn(puffer.laufenderDienst()!!, neu = true)
    }

    /**
     * Dienst beenden — schließt Ruhesegment **und** einen etwa noch offenen
     * Einsatz.
     *
     * DAS SICHERHEITSNETZ IST ABSICHT: Wer den Einsatzabschluss vergisst und
     * den Dienst beendet, hat einen abgeschlossenen Einsatz und kein
     * Datenleck in der Warteschlange. Dieselbe Stelle in `Model.endService()`.
     */
    fun beenden(): Boolean {
        val dienst = puffer.laufenderDienst() ?: return false
        val augenblick = jetzt()
        val iso = Zeit.iso(augenblick)

        puffer.offenesPaket(Paketzeile.ART_EINSATZ)?.let { einsatz ->
            puffer.paketSchliessen(
                einsatz.id, iso,
                ausduenner.streckeM.toInt(), ausduenner.anstiegM.toInt(),
            )
        }
        puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)?.let { segment ->
            puffer.paketSchliessen(segment.id, iso, null, null)
        }

        puffer.dienstBeenden(dienst.dienstRef, iso)
        ausduenner.zuruecksetzen()
        return true
    }

    /**
     * Den Modus während des Dienstes wechseln (E-S4-20).
     *
     * **Verlustfrei:** Es wird nichts geschlossen und nichts geöffnet, nur
     * vermerkt. Bereits Gesendetes bleibt unberührt; ein Umstieg auf „mit
     * Knöpfen" schließt das laufende Segment erst, wenn tatsächlich eine
     * Phase gesetzt wird.
     */
    fun modusWechseln(modus: Modus): Boolean {
        val dienst = puffer.laufenderDienst() ?: return false
        puffer.modusSetzen(dienst.dienstRef, modus.gespeichert)
        return true
    }

    fun modus(): Modus = Modus.ausGespeichertem(puffer.laufenderDienst()?.modus)

    /**
     * Einen Positionsfund einsortieren.
     *
     * Er landet im **offenen** Paket — dem laufenden Einsatz, wenn es einen
     * gibt, sonst dem laufenden Ruhesegment. Ohne laufenden Dienst wird nichts
     * aufgezeichnet: Ein Punkt außerhalb der Klammer gehörte zu nichts.
     *
     * @return `true`, wenn der Punkt übernommen wurde (die Ausdünnung ihn
     *   nicht verworfen hat)
     */
    fun positionsfund(punkt: Rohpunkt): Boolean {
        if (!laeuft()) return false
        val ziel = puffer.offenesPaket(Paketzeile.ART_EINSATZ)
            ?: puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)
            ?: return false

        if (!ausduenner.nimm(punkt)) return false
        puffer.punktAnhaengen(ziel.id, punkt)
        return true
    }

    // ---- Phasen und Einsätze (E-S4-08, B5) ---------------------------------

    /**
     * Die laufende Phase — die zuletzt gesetzte des laufenden Einsatzes.
     *
     * Ohne Einsatz ist es [Phasen.FREI] (1). Das ist ein **Anzeigezustand**
     * und erzeugt keinen Eintrag (Vertrag 7).
     */
    fun laufendePhase(): Int {
        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ) ?: return Phasen.FREI
        return puffer.phasen(einsatz.id).lastOrNull()?.nummer ?: Phasen.FREI
    }

    /**
     * Eine Phase setzen — **der Kern des Lebenszyklus** (E-S4-08).
     *
     * EINE PHASE 2–9 OHNE LAUFENDEN EINSATZ **STARTET** DEN EINSATZ und
     * schließt dabei das Ruhesegment. Das ist keine Bequemlichkeit, sondern
     * die Bedienung: Wer alarmiert wird, drückt „Alarmierung" — und nicht
     * vorher „Einsatz beginnen". Dieselbe Stelle in `Model.setPhase()`.
     *
     * EIN ERNEUT GESETZTE PHASE IST EIN **ZWEITER EINTRAG**, keine Korrektur
     * am ersten (E-R45-12, Vertrag 3): „Eine erneut gesetzte Phase ist eine
     * Korrektur und damit eine Information. Kein Client und kein Schreibweg
     * darf sie entdoppeln."
     *
     * @param quelle `handy` oder `uhr` — am Datensatz bleibt ablesbar, auf
     *   welchem Weg er entstand.
     * @param zeitpunkt bei einem Ereignis der Uhr deren Zeitstempel (E-S4-10),
     *   sonst jetzt.
     * @return `false`, wenn die Nummer nicht übertragbar ist oder kein Dienst
     *   läuft
     */
    fun phaseSetzen(
        nummer: Int,
        quelle: String = QUELLE_HANDY,
        zeitpunkt: Instant = jetzt(),
    ): Boolean {
        if (!Phasen.uebertragbar(nummer)) return false
        val dienst = puffer.laufenderDienst() ?: return false

        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ)
            ?: einsatzBeginnen(dienst.dienstRef, dienst.tag, zeitpunkt)

        /* DIE KOORDINATE KOMMT AUS DER EIGENEN SPUR (E-S4-10): der zeitlich
         * nächste Punkt innerhalb von ± 30 s, sonst null. Eine erfundene
         * Koordinate wäre schlimmer als keine — der Vertrag lässt null
         * ausdrücklich zu. */
        val punkt = puffer.punktNaheZeit(
            dienst.dienstRef, Zeit.epoche(zeitpunkt), KOORDINATEN_TOLERANZ_S,
        )
        puffer.phaseAnhaengen(
            einsatz.id, nummer, Zeit.iso(zeitpunkt), punkt?.breite, punkt?.laenge, quelle,
        )
        return true
    }

    /**
     * Der Durchlauf: die nächste Phase (E-S4-21b).
     *
     * Nach der letzten gibt es **keine** nächste — dort wird der Knopf zu
     * „Einsatz abschließen". `null` ist genau diese Auskunft und nicht etwa
     * ein Rückfall auf Phase 2, der einen Einsatz stillschweigend von vorn
     * begänne.
     */
    fun naechstePhase(): Int? = Phasen.naechste(laufendePhase())

    /**
     * Den Einsatz abschließen — **ein eigener Bedienschritt**, nie automatisch
     * (E-S4-08).
     *
     * `ended_at` ist die Zeit der **letzten Phase 9**, sonst der Augenblick
     * des Abschlusses. Der Unterschied ist keine Feinheit: Wer die Endzeit
     * gesetzt und danach noch fünf Minuten gebraucht hat, um den Knopf zu
     * finden, hat den Einsatz um 9:12 beendet und nicht um 9:17.
     *
     * Strecke und Anstieg werden dabei **eingefroren**: Sie gehören zu diesem
     * Einsatz, auch wenn der Upload erst während des nächsten gelingt.
     */
    fun einsatzAbschliessen(): Boolean {
        val dienst = puffer.laufenderDienst() ?: return false
        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ) ?: return false

        val letztePhaseNeun = puffer.phasen(einsatz.id).lastOrNull { it.nummer == Phasen.LETZTE }
        val ende = letztePhaseNeun?.at ?: Zeit.iso(jetzt())

        puffer.paketSchliessen(
            einsatz.id, ende, ausduenner.streckeM.toInt(), ausduenner.anstiegM.toInt(),
        )

        // Danach beginnt das nächste Ruhesegment — die Spur läuft nahtlos
        // weiter, und der Browser findet später keine Lücke.
        ausduenner.kennzahlenZuruecksetzen()
        ruhesegmentOeffnen(dienst.dienstRef, dienst.tag, jetzt())
        return true
    }

    fun einsatzLaeuft(): Boolean = puffer.offenesPaket(Paketzeile.ART_EINSATZ) != null

    /**
     * Einen Einsatz beginnen: Ruhesegment schließen, Einsatz öffnen.
     *
     * DIE REIHENFOLGE IST ES, DIE DIE SPUR NAHTLOS HÄLT: Das Segment endet in
     * dem Augenblick, in dem der Einsatz beginnt — kein Loch dazwischen, kein
     * Überlappen.
     */
    private fun einsatzBeginnen(dienstRef: String, tag: String, zeitpunkt: Instant): Paketzeile {
        val iso = Zeit.iso(zeitpunkt)
        puffer.offenesPaket(Paketzeile.ART_RUHESEGMENT)?.let {
            puffer.paketSchliessen(it.id, iso, null, null)
        }
        /* Die Kennzahlen fangen mit dem Einsatz neu an, die AUSDÜNNUNG nicht:
         * Sonst entstünde direkt nach dem Schnitt ein zweiter Punkt am selben
         * Ort. Strecke und Anstieg gehören dem Einsatz, die Spur dem Dienst. */
        ausduenner.kennzahlenZuruecksetzen()
        val id = puffer.paketAnlegen(
            clientRef = kennungen.einsatz(),
            art = Paketzeile.ART_EINSATZ,
            tag = tag,
            dienstRef = dienstRef,
            begonnenAt = iso,
        )
        return checkNotNull(puffer.paket(id))
    }

    /** Strecke und Anstieg seit dem letzten Zurücksetzen — für die Anzeige. */
    fun streckeM(): Double = ausduenner.streckeM
    fun anstiegM(): Double = ausduenner.anstiegM

    private fun ruhesegmentOeffnen(dienstRef: String, tag: String, augenblick: Instant): Long =
        puffer.paketAnlegen(
            clientRef = kennungen.ruhesegment(),
            art = Paketzeile.ART_RUHESEGMENT,
            tag = tag,
            dienstRef = dienstRef,
            begonnenAt = Zeit.iso(augenblick),
        )

    data class Dienstbeginn(val dienst: Dienstzeile, val neu: Boolean)

    companion object {
        const val QUELLE_HANDY = "handy"
        const val QUELLE_UHR = "uhr"

        /**
         * Toleranz für die Phasen-Koordinate (E-S4-10). Bei 1-Hz-Abtastung und
         * einer Ausdünnung, die spätestens alle 10 s einen Punkt hält, ist der
         * nächste Punkt normalerweise Sekunden alt. 30 s decken den Fall ab,
         * dass die Uhr ein gepuffertes Ereignis nachliefert; darüber hinaus
         * wäre die Koordinate eine Behauptung.
         */
        const val KOORDINATEN_TOLERANZ_S = 30L
    }
}
