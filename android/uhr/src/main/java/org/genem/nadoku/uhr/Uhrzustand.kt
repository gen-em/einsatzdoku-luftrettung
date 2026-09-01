package org.genem.nadoku.uhr

import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Phasen

/** Eine gesetzte Phase, wie die Uhr sie anzeigt. */
data class Phasenzeit(val nummer: Int, val hhmm: String)

/** Welche Ansicht die Uhr gerade zeigt. */
enum class Ansicht {
    /** Vor dem Dienst: Bildmarke, Name, „Dienst beginnen". */
    START,

    /** Im Dienst: der eine große Knopf (oder, ohne Phasen, nur „beenden"). */
    LAUFEND,

    /** Die Phasenliste — Übersicht und Direktwahl in einem (E-S4-21c). */
    PHASENLISTE,

    /** Die Rückfrage vor dem Einsatzabschluss (E-S4-21b). */
    ABSCHLUSSFRAGE,

    /** Die Rückfrage vor dem Dienstende. */
    DIENSTENDEFRAGE,
}

/**
 * Alles, was die Uhr anzeigt — in einem Stück.
 *
 * DIE UHR BESITZT DIESEN ZUSTAND NICHT, sie spiegelt ihn: Der Dienst läuft am
 * Handy, die Phasen liegen dort. Was hier steht, ist der zuletzt übernommene
 * Stand plus das, was die Uhr selbst weiß — ob sie gesperrt ist und wie viele
 * Ereignisse sie gepuffert hat.
 */
data class Uhrzustand(
    val dienstLaeuft: Boolean = false,
    val modus: Modus = Modus.MIT_PHASENKNOEPFEN,
    val einsatzLaeuft: Boolean = false,
    val laufendePhase: Int = Phasen.FREI,
    val laufendeSeit: String? = null,
    val phasen: List<Phasenzeit> = emptyList(),
    val ansicht: Ansicht = Ansicht.START,

    /** Die Anzeige ist gesperrt (E-S4-21d). */
    val gesperrt: Boolean = false,

    /** Wann zuletzt bedient wurde — Grundlage der Sperrfrist. */
    val letzteBedienungMs: Long = 0,

    /** Ist das Handy erreichbar? (E-S4-10) */
    val handyErreichbar: Boolean = true,

    /** Wie viele Ereignisse warten auf ihre Quittung? (C2) */
    val gepuffert: Int = 0,
) {
    /**
     * Trägt der große Knopf eine nächste Phase — oder den Abschluss?
     *
     * Nach der letzten Phase wird derselbe Knopf zu „Einsatz abschließen"
     * (E-S4-21b). Ohne laufenden Einsatz trägt er Phase 2: Sie startet ihn.
     */
    val naechstePhase: Int?
        get() = when {
            !einsatzLaeuft -> Phasen.ERSTE
            else -> Phasen.naechste(laufendePhase)
        }

    /** Zeigt die Uhr in diesem Modus überhaupt Phasenknöpfe? (E-S4-20) */
    val mitPhasen: Boolean get() = modus == Modus.MIT_PHASENKNOEPFEN
}

/** Was an der Uhr geschehen ist. */
sealed interface Uhrereignis {
    /** Der große Knopf wurde getippt. */
    data class GrosserKnopf(val jetztMs: Long) : Uhrereignis

    /** Der große Knopf wurde gehalten — er öffnet die Phasenliste. */
    data class GrosserKnopfGehalten(val jetztMs: Long) : Uhrereignis

    /** Eine Zeile der Phasenliste wurde getippt (Direktwahl). */
    data class ListenwahL(val phase: Int, val jetztMs: Long) : Uhrereignis

    /** „Einsatz abschließen" — aus der Liste oder vom Durchlaufknopf. */
    data class Abschluss(val jetztMs: Long) : Uhrereignis

    /** „Dienst beginnen" bzw. „Dienst beenden". */
    data class Dienstknopf(val jetztMs: Long) : Uhrereignis

    /** Eine Rückfrage wurde bestätigt. */
    data class Bestaetigt(val jetztMs: Long) : Uhrereignis

    /** Eine Rückfrage wurde verworfen, oder zurück aus der Liste. */
    data class Verworfen(val jetztMs: Long) : Uhrereignis

    /** Die freie Zusatztaste, falls das Gerät eine meldet (E-S4-21a). */
    data class FreieTaste(val jetztMs: Long) : Uhrereignis

    /** Ein Halten wurde beendet — der Weg aus der Sperre. */
    data class Halten(val dauerMs: Long, val jetztMs: Long) : Uhrereignis

    /** Die Zeit läuft — prüft die Sperrfrist. */
    data class Zeitschlag(val jetztMs: Long) : Uhrereignis
}

/** Was das Handy tun soll. Die Uhr tut selbst nichts (E-S4-11). */
sealed interface Uhrwirkung {
    data class PhaseSetzen(val phase: Int) : Uhrwirkung
    data object EinsatzAbschliessen : Uhrwirkung
    data object DienstBeginnen : Uhrwirkung
    data object DienstBeenden : Uhrwirkung
}
