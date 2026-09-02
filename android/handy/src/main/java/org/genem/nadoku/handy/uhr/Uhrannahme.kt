package org.genem.nadoku.handy.uhr

import org.genem.nadoku.gemeinsam.Ereignisart
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Phasen
import org.genem.nadoku.gemeinsam.Phasenmarke
import org.genem.nadoku.gemeinsam.Quittung
import org.genem.nadoku.gemeinsam.Standmeldung
import org.genem.nadoku.gemeinsam.Uhrmeldung
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.handy.dienst.Zeit
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import java.time.Instant

/**
 * Die Annahme der Uhr-Ereignisse am Handy (E-S4-10).
 *
 * DREI ZUSAGEN, UND JEDE HAT IHREN EIGENEN SCHUTZ:
 *
 * 1. **Kein Ereignis wirkt zweimal.** Die Nummer je Uhr ist der Ausweis; eine
 *    bekannte Nummer wird quittiert und nicht noch einmal gewirkt.
 * 2. **Kein zweiter Einsatz nach verlorener Quittung.** Selbst wenn die
 *    Buchführung versagte, führt die `wm-`-Kennung auf denselben Einsatz
 *    zurück (E-S4-09) — sie entsteht auf der Uhr und reist mit der gepufferten
 *    Nachricht mit.
 * 3. **Kein halb gewirktes Ereignis.** Wirkung und Vermerk stehen in **einem**
 *    Schreibvorgang. Bräche es dazwischen ab, wäre das Ereignis entweder
 *    zweimal gewirkt oder gar nicht — beides unbemerkbar.
 *
 * WARUM ZWEI SCHUTZE FÜR DASSELBE (1 und 2): Weil sie verschiedene Fehler
 * abfangen. Die Nummer schützt gegen die Doppelzustellung; die Kennung auch
 * dann noch, wenn der Puffer des Handys gelöscht wurde, die App neu
 * eingerichtet ist oder ein Ereignis über einen anderen Weg ein zweites Mal
 * hereinkommt. Der Vertrag setzt beim Server dieselbe Art doppelten Bodens
 * (Idempotenz über `client_ref`), und aus demselben Grund.
 *
 * DIE ZEIT KOMMT VON DER UHR (E-R45-1) und nicht von der Ankunft: Die Uhr war
 * dabei. Zwischen Auslösung und Zustellung können im Funkloch Minuten liegen.
 */
class Uhrannahme(
    private val puffer: Puffer,
    private val klammer: Dienstklammer,
    /** Der Modus für einen an der Uhr ausgelösten Dienst (E-S4-20). */
    private val modus: () -> Modus = { Modus.MIT_PHASENKNOEPFEN },
) {

    /**
     * Ein Ereignis übernehmen und quittieren.
     *
     * @return die Quittung — **immer**, auch für eine Doppelzustellung. Genau
     *   dann ist sie am wichtigsten: Die Uhr liefert nach, weil die erste
     *   Quittung verlorenging, und ohne eine zweite täte sie es für immer.
     */
    fun uebernimm(m: Uhrmeldung): Quittung = puffer.imVorgang {
        if (puffer.uhrEreignisBekannt(m.uhrId, m.nr)) {
            return@imVorgang Quittung(puffer.uhrStand(m.uhrId))
        }
        wirke(m)
        Quittung(puffer.uhrEreignisMerken(m.uhrId, m.nr))
    }

    /**
     * @return `false`, wenn das Ereignis ins Leere lief (etwa eine Phase ohne
     *   laufenden Dienst). Es gilt trotzdem als übernommen: Die Uhr soll es
     *   nicht ewig nachliefern — es würde immer wieder ins Leere laufen.
     */
    private fun wirke(m: Uhrmeldung): Boolean {
        val zeitpunkt = Instant.ofEpochMilli(m.zeitMs)
        return when (m.art) {
            Ereignisart.DIENST_BEGINNEN -> klammer.beginnen(modus(), zeitpunkt).neu
            Ereignisart.DIENST_BEENDEN -> klammer.beenden(zeitpunkt)
            Ereignisart.EINSATZ_ABSCHLIESSEN -> klammer.einsatzAbschliessen(zeitpunkt)
            Ereignisart.PHASE -> {
                val nummer = m.phase ?: return false
                klammer.phaseSetzen(
                    nummer,
                    quelle = Dienstklammer.QUELLE_UHR,
                    zeitpunkt = zeitpunkt,
                    einsatzRef = m.einsatzRef,
                )
            }
        }
    }

    /**
     * Der Anzeigestand für die Uhr (E-S4-10).
     *
     * Er wird aus dem Puffer gelesen und nicht mitgeschrieben — dieselbe
     * Entscheidung wie in [Dienstklammer]: Ein Zustand im Arbeitsspeicher wäre
     * ein zweiter Zustand, und die Aufzeichnung läuft in einem Dienst weiter,
     * während die Oberfläche längst beendet ist.
     */
    fun stand(): Standmeldung {
        val einsatz = puffer.offenesPaket(Paketzeile.ART_EINSATZ)
        val phasen = einsatz?.let { puffer.phasen(it.id) }.orEmpty()
        return Standmeldung(
            dienstLaeuft = klammer.laeuft(),
            modus = klammer.modus(),
            einsatzLaeuft = einsatz != null,
            laufendePhase = phasen.lastOrNull()?.nummer ?: Phasen.FREI,
            laufendeSeit = phasen.lastOrNull()?.at?.let { Zeit.hhmm(Instant.parse(it)) },
            phasen = phasen.map { Phasenmarke(it.nummer, Zeit.hhmm(Instant.parse(it.at))) },
        )
    }
}
