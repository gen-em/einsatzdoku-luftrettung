package org.genem.nadoku.uhr.funk

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Standmeldung
import org.genem.nadoku.uhr.Ansicht
import org.genem.nadoku.uhr.Uhrbedienung
import org.genem.nadoku.uhr.Uhrereignis
import org.genem.nadoku.uhr.Uhrwirkung
import org.genem.nadoku.uhr.Uhrzustand

/**
 * Die Steuerung der Uhr: Bedienung, Funk und Anzeige an **einer** Stelle.
 *
 * SIE IST DIE NAHT ZWISCHEN ZWEI PRÜFBAREN TEILEN. [Uhrbedienung] entscheidet,
 * was eine Berührung bedeutet; [Uhrfunk] sorgt dafür, dass die Meldung ankommt.
 * Dazwischen liegt genau das, was C2 belegen muss: dass jede Wirkung der
 * Bedienung zu genau einer Meldung wird, dass die `wm-`-Kennung dort entsteht,
 * wo ein Einsatz eröffnet wird, und dass die Anzeige den Unterschied zwischen
 * „gemeldet" und „angekommen" nicht verschluckt.
 *
 * DIE OBERFLÄCHE HÄNGT DARAN UND NICHT UMGEKEHRT: `UhrActivity` liest
 * [zustand] und schickt Ereignisse herein. Deshalb ist von der Uhr alles
 * geprüft außer dem Zeichnen — auf einem Gerät, das es hier nicht gibt
 * (E-R45-7, E-R45-8).
 */
class Uhrsteuerung(
    private val funk: Uhrfunk,
    private val bedienung: Uhrbedienung = Uhrbedienung(),
    private val jetzt: () -> Long = System::currentTimeMillis,
    anfang: Uhrzustand = Uhrzustand(),
    /**
     * Wird bei **jeder** Änderung gerufen — auch der, die schon feststeht,
     * während die Nachricht noch unterwegs ist.
     *
     * WARUM ZWISCHENDURCH UND NICHT ERST AM ENDE: Der Sendeversuch wartet bis
     * zu fünf Sekunden auf den Data Layer. Käme die Anzeige erst danach, hinge
     * die Uhr nach jedem Tippen. Der Zustandswechsel steht aber sofort fest —
     * er hängt an nichts, was das Handy sagt.
     */
    private val beiAenderung: (Uhrzustand) -> Unit = {},
) {

    /** Die Nummer des gemeldeten Dienststarts, solange sie unquittiert ist. */
    private var dienstNr: Long? = null

    var zustand: Uhrzustand = anfang.copy(
        gepuffert = funk.offen(), handyErreichbar = funk.handyErreichbar,
    )
        private set(wert) {
            field = wert
            beiAenderung(wert)
        }

    /**
     * Ein Bedienereignis verarbeiten und melden.
     *
     * @return die Wirkungen, die dabei gemeldet wurden — für die Prüfung; die
     *   App braucht sie nicht, sie liest [zustand].
     */
    fun ereignis(e: Uhrereignis): List<Uhrwirkung> {
        val ergebnis = bedienung.verarbeite(zustand, e)
        zustand = ergebnis.zustand
        ergebnis.wirkungen.forEach { melde(it) }
        anzeigeNachziehen()
        return ergebnis.wirkungen
    }

    /** Das Handy hat quittiert (E-S4-10). */
    fun quittungEingegangen(q: Quittung) {
        funk.quittung(q)
        dienstNr?.let { if (funk.bestaetigt(it)) dienstNr = null }
        anzeigeNachziehen()
    }

    /**
     * Das Handy meldet seinen Stand — **er führt** (E-S4-10).
     *
     * DIE UHR BESITZT DEN ZUSTAND NICHT. Weicht ihre Anzeige vom Handy ab, hat
     * das Handy recht: Dort liegt der Dienst, dort die Phasen, dort der Modus.
     * Der Fall ist keine Theorie — nach einem Neustart der Uhr-App ist ihre
     * Anzeige leer und der Dienst läuft weiter.
     *
     * WAS NICHT ÜBERSCHRIEBEN WIRD, ist die Sperre und eine offene Rückfrage:
     * Eine Standmeldung, die mitten in der Abschlussfrage die Ansicht
     * umschaltet, beantwortete die Frage für den Menschen davor.
     */
    fun standEingegangen(s: Standmeldung) {
        zustand = zustand.copy(
            dienstLaeuft = s.dienstLaeuft,
            modus = s.modus,
            einsatzLaeuft = s.einsatzLaeuft,
            laufendePhase = s.laufendePhase,
            laufendeSeit = s.laufendeSeit,
            phasen = s.phasen,
            ortung = s.ortung,
            ansicht = if (zustand.ansicht == Ansicht.START || zustand.ansicht == Ansicht.LAUFEND) {
                if (s.dienstLaeuft) Ansicht.LAUFEND else Ansicht.START
            } else {
                zustand.ansicht
            },
        )
        if (s.dienstLaeuft) dienstNr = null
        anzeigeNachziehen()
    }

    /**
     * Nachliefern, was noch wartet — nach einem Funkabriss oder beim Start.
     *
     * @return wie viele Nachrichten zugestellt wurden
     */
    fun nachliefern(): Int {
        val zugestellt = funk.nachliefern()
        anzeigeNachziehen()
        return zugestellt
    }

    private fun melde(w: Uhrwirkung) {
        val zeit = jetzt()
        when (w) {
            is Uhrwirkung.PhaseSetzen -> funk.melde(
                Ereignisart.PHASE, zeit, phase = w.phase,
                /* DIE KENNUNG ENTSTEHT NUR BEIM ERÖFFNENDEN EREIGNIS (E-S4-09).
                 * Sie an jede Phase zu hängen wäre nicht bloß überflüssig: Bei
                 * der zweiten Phase wäre es eine ANDERE Kennung, und das Handy
                 * öffnete einen zweiten Einsatz, sobald der erste geschlossen
                 * ist. */
                einsatzRef = if (w.eroeffnet) funk.einsatzkennung() else null,
            )

            Uhrwirkung.EinsatzAbschliessen -> funk.melde(Ereignisart.EINSATZ_ABSCHLIESSEN, zeit)

            Uhrwirkung.DienstBeginnen -> {
                /* SCHWEBEND, BIS DAS HANDY QUITTIERT. Vorher läuft dort kein
                 * GPS (E-S4-10) — und eine Uhr, die „Dienst läuft" zeigt,
                 * während nichts aufgezeichnet wird, verschwiege genau die
                 * Lücke, die später niemand mehr erklären kann. */
                dienstNr = funk.melde(Ereignisart.DIENST_BEGINNEN, zeit).nr
            }

            Uhrwirkung.DienstBeenden -> {
                funk.melde(Ereignisart.DIENST_BEENDEN, zeit)
                dienstNr = null
            }
        }
    }

    /**
     * Anzeige und Funkstand angleichen.
     *
     * `dienstBestaetigt` ist wahr, sobald es nichts Schwebendes gibt — beim
     * Dienstende ebenso wie ohne Dienst. Was danach noch im Puffer liegt,
     * zeigt [Uhrzustand.gepuffert]; die Zahl steht auf der Uhr, damit ein
     * Rückstand sichtbar ist und nicht nur vermutet werden muss.
     */
    private fun anzeigeNachziehen() {
        zustand = zustand.copy(
            gepuffert = funk.offen(),
            handyErreichbar = funk.handyErreichbar,
            dienstBestaetigt = dienstNr == null,
        )
    }
}
