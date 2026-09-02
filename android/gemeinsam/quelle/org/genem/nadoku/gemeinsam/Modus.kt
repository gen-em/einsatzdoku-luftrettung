package org.genem.nadoku.gemeinsam

/**
 * Wie dieser Dienst dokumentiert wird (E-S4-20).
 *
 * DER MODUS IST KEIN SONDERWEG, sondern der benannte Grundzustand:
 * [NUR_AUFZEICHNEN] ist **exakt** das Verhalten eines Dienstes, in dem nie
 * eine Phase gesetzt wird. Am Vertrag, am Server und an der Sendelogik ändert
 * er **nichts** — nur an dem, was der Bildschirm anbietet.
 *
 * Das ist der Grund, warum er hier als Anzeigeentscheidung steht und nicht in
 * der Sendelogik: Wäre er dort, gäbe es zwei Wege durch dieselbe Klammer, und
 * einer von beiden wäre irgendwann der schlechter geprüfte.
 *
 * WOZU ER GUT IST: Wer im Einsatz keine Knöpfe drücken kann oder will — oder
 * keine Uhr hat —, zeichnet den ganzen Dienst als eine Ruhesegment-Kette auf
 * und schneidet die Einsätze später im Browser heraus. Kein versehentlicher
 * Druck mit Handschuhen, ein Bildschirm ohne Frage.
 *
 * DER WECHSEL WÄHREND DES DIENSTES IST VERLUSTFREI (E-S4-20): Er blendet die
 * Knöpfe ein oder aus, bereits Gesendetes bleibt unberührt. Ein Umstieg auf
 * „mit Knöpfen" schließt das laufende Segment erst, wenn tatsächlich eine
 * Phase gesetzt wird — wie bisher.
 */
enum class Modus(val gespeichert: String) {
    MIT_PHASENKNOEPFEN("phasen"),
    NUR_AUFZEICHNEN("nur_aufzeichnen");

    companion object {
        fun ausGespeichertem(wert: String?): Modus =
            entries.firstOrNull { it.gespeichert == wert } ?: MIT_PHASENKNOEPFEN
    }
}
