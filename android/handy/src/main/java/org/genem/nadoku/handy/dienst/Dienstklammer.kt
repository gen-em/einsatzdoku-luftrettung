package org.genem.nadoku.handy.dienst

import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Dienstzeile
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
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
}
