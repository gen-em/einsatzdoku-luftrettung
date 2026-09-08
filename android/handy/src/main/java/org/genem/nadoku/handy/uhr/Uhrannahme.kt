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
import java.time.Duration
import java.time.Instant

/**
 * Die Annahme der Uhr-Ereignisse am Handy (E-S4-10).
 *
 * VIER ZUSAGEN, UND JEDE HAT IHREN EIGENEN SCHUTZ:
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
 * 4. **Kein fremder Absender, keine unmögliche Zeit** (seit 0.14.0, Backlog
 *    Nr. 144, Krypto-Review AN-4). Der Absender muss unter den verbundenen
 *    Knoten stehen ([absenderBekannt]), sonst gibt es weder Wirkung noch
 *    Quittung; die Zeit der Uhr darf weder vor dem laufenden Dienst noch in
 *    der Zukunft liegen ([plausibel]), sonst wird quittiert, aber nicht
 *    gewirkt. Bis dahin ruhte das Vertrauen ganz auf der Bibliothek —
 *    gleiches Paket, gleiche Signatur. Das bleibt der erste Boden.
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
    /**
     * Der Ortungszustand als Kurzcode, oder `null` (E-S5Z-15).
     *
     * Als Funktion und nicht als Wert: Zwischen dem Bau dieser Annahme und
     * der Antwort liegt das Wirken des Ereignisses — ein Dienststart kann den
     * Zustand in genau dieser Spanne ändern.
     */
    private val ortung: () -> String? = { null },
    /** Die Uhr des Handys — einsetzbar, damit der Prüfstand die Zeit stellen kann. */
    private val jetzt: () -> Instant = Instant::now,
) {

    /**
     * Ist der Absender **unsere** Uhr? (Backlog Nr. 144, AN-4)
     *
     * Bis 0.13.0 ruhte das Vertrauen ganz auf der Bibliothek: Der Data Layer
     * stellt nur zwischen Apps gleichen Pakets und gleicher Signatur zu. Das
     * bleibt der erste Boden. Der zweite ist dieser Abgleich: Ein Knoten, der
     * nicht in der Liste der verbundenen steht, bekommt **weder Wirkung noch
     * Quittung** — er ist nicht die gekoppelte Uhr.
     *
     * IST DIE LISTE NICHT LESBAR (`null`), gilt das Ereignis als fremd. Das
     * kostet keine Daten: Ohne Quittung liefert die Uhr nach, und beim
     * nächsten Mal ist die Liste da. Andersherum — im Zweifel annehmen — wäre
     * der Abgleich genau dann außer Kraft, wenn etwas nicht stimmt.
     */
    fun absenderBekannt(a: Absender): Boolean =
        a.verbundene != null && a.knoten in a.verbundene

    /**
     * Ein Ereignis übernehmen und quittieren.
     *
     * @param empfangen der Augenblick des Empfangs am Handy — der Maßstab für
     *   die Zeitplausibilität (Nr. 144); im Betrieb jetzt.
     * @return die Quittung — **immer**, auch für eine Doppelzustellung. Genau
     *   dann ist sie am wichtigsten: Die Uhr liefert nach, weil die erste
     *   Quittung verlorenging, und ohne eine zweite täte sie es für immer.
     */
    fun uebernimm(m: Uhrmeldung, empfangen: Instant = jetzt()): Quittung = puffer.imVorgang {
        if (puffer.uhrEreignisBekannt(m.uhrId, m.nr)) {
            return@imVorgang Quittung(puffer.uhrStand(m.uhrId))
        }
        wirke(m, empfangen)
        Quittung(puffer.uhrEreignisMerken(m.uhrId, m.nr))
    }

    /**
     * @return `false`, wenn das Ereignis ins Leere lief (etwa eine Phase ohne
     *   laufenden Dienst). Es gilt trotzdem als übernommen: Die Uhr soll es
     *   nicht ewig nachliefern — es würde immer wieder ins Leere laufen.
     */
    private fun wirke(m: Uhrmeldung, empfangen: Instant): Boolean {
        val zeitpunkt = Instant.ofEpochMilli(m.zeitMs)
        if (!plausibel(zeitpunkt, empfangen)) return false
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
            /* DIE ANTWORT AUF EIN UHR-EREIGNIS TRÄGT DEN ORTUNGSZUSTAND MIT
             * (E-S5Z-15). Damit erfährt die Uhr von einem Dienststart bei
             * ausgeschaltetem Standort im selben Augenblick, in dem sie die
             * Quittung bekommt — ohne dass jemand das Handy aus der Tasche
             * holen muss. */
            ortung = ortung(),
        )
    }

    /**
     * Ist die Zeit der Uhr plausibel? (Backlog Nr. 144, AN-4)
     *
     * Zwei Grenzen, beide mit [ZEITTOLERANZ] Spiel, weil zwei Uhren nie ganz
     * gleich gehen:
     *  - **nicht in der Zukunft** — die Uhr war dabei, aber nicht voraus;
     *  - **nicht vor dem laufenden Dienst** — ein Ereignis, das älter ist als
     *    der Dienst, gehört zu keinem.
     *
     * Ein unplausibles Ereignis wird **nicht gewirkt, aber quittiert** —
     * dieselbe Regel wie für eine Phase ohne Dienst: Die Uhr soll es nicht
     * ewig nachliefern, es bliebe immer unplausibel. Die Aufzeichnung verliert
     * damit ein Ereignis mit falscher Zeit; sie behielte sonst eines mit
     * falscher Zeit, und das ist das schlechtere Dokument.
     */
    private fun plausibel(zeitpunkt: Instant, empfangen: Instant): Boolean {
        if (zeitpunkt.isAfter(empfangen.plus(ZEITTOLERANZ))) return false
        val dienst = klammer.laufenderDienst() ?: return true
        return !zeitpunkt.isBefore(Instant.parse(dienst.begonnenAt).minus(ZEITTOLERANZ))
    }

    companion object {
        /**
         * Fünf Minuten Spiel für die Zeitplausibilität. **Gewählt, nicht
         * gemessen:** Wear OS gleicht die Uhrzeit mit dem Handy ab, mehr als
         * Sekunden Abstand sind ein Fehler; fünf Minuten fangen den Fehler
         * ab, ohne ein Ereignis zu verwerfen, das zwei nahezu gleichzeitige
         * Handgriffe an Uhr und Handy hervorbringen.
         */
        val ZEITTOLERANZ: Duration = Duration.ofMinutes(5)
    }
}

/**
 * Woher ein Ereignis kam — was der Data Layer über den Absender sagt
 * (Backlog Nr. 144, Krypto-Review AN-4).
 *
 * @param knoten die Kennung des sendenden Knotens (`sourceNodeId`).
 * @param verbundene die zurzeit verbundenen Knoten, oder `null`, wenn die
 *   Liste nicht zu lesen war.
 */
data class Absender(val knoten: String, val verbundene: Set<String>?)
