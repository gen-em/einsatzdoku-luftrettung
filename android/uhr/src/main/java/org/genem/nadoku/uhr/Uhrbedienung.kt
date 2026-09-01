package org.genem.nadoku.uhr

import org.genem.nadoku.gemeinsam.Phasen

/**
 * Das Bedienmodell der Uhr (E-S4-21) — **als Zustandsmaschine, ohne
 * Oberfläche**.
 *
 * WARUM SO. Die Uhr ist blind gebaut: kein Emulator (E-R45-8), keine Uhr
 * (E-R45-7). Wäre das Bedienmodell in Composables verteilt, wäre es
 * ungeprüft. Hier ist es eine reine Funktion — Zustand und Ereignis hinein,
 * Zustand und Wirkungen heraus —, und damit vollständig prüfbar. Was an der
 * Oberfläche bleibt, ist Zeichnen.
 *
 * DIE VIER TEILE VON E-S4-21:
 *
 * **(a) Keine verlässliche Hardwaretaste.** Die Ein/Aus-Taste ist für Apps
 * gesperrt; freie Zusatztasten meldet `WearableButtons`, auf der
 * Galaxy-Watch-Linie ist keine zu erwarten. Wo eine gemeldet wird, löst sie
 * „nächste Phase" aus — [Uhrereignis.FreieTaste] macht **genau dasselbe** wie
 * [Uhrereignis.GrosserKnopf]. Das Bedienbild hängt nicht daran.
 *
 * **(b) Der Durchlauf.** Ein großer Knopf: 2 → … → 9. Nach der letzten Phase
 * wird derselbe Knopf zu „Einsatz abschließen" **mit Rückfrage** — erst die
 * Bestätigung wirkt; ein versehentlicher letzter Tipp beendet nichts.
 *
 * **(c) Die Phasenliste.** Halten öffnet sie: jede Zeile eine Phase mit ihren
 * Zeiten (ansehen), Tippen setzt sie jetzt (direkt wählen). Eine erneut
 * gesetzte Phase ist ein zweiter Eintrag (E-R45-12).
 *
 * **(d) Die Sperre.** Nach [sperrfristMs] ohne Bedienung sperrt die Anzeige:
 * Phase und Zeit bleiben lesbar, ein Tippen tut nichts, entsperrt wird durch
 * **Halten** ([haltedauerMs]). Die Sperre gilt für Touch und die freie Taste
 * gleichermaßen.
 *
 * **Haltedauer, Sperrfrist und Berührziele sind blind gewählt** und am Gerät
 * nachzumessen (E-R45-7); sie gehören in den Wear-Teil von
 * `docs/Geraete-Eingabe.md`.
 */
class Uhrbedienung(
    /** Richtwert 10 s (E-S4-21d). In den App-Einstellungen abschaltbar. */
    private val sperrfristMs: Long = SPERRFRIST_MS,
    /** Richtwert 1 s. */
    private val haltedauerMs: Long = HALTEDAUER_MS,
    /** Ist die Sperre eingeschaltet? Kommt vom Handy (E-S4-21d). */
    private val sperreAn: Boolean = true,
) {

    data class Ergebnis(val zustand: Uhrzustand, val wirkungen: List<Uhrwirkung> = emptyList())

    fun verarbeite(zustand: Uhrzustand, ereignis: Uhrereignis): Ergebnis {
        // Der Zeitschlag ist das einzige Ereignis, das NICHT als Bedienung
        // zählt — sonst sperrte die Anzeige nie.
        if (ereignis is Uhrereignis.Zeitschlag) return zeitschlag(zustand, ereignis.jetztMs)

        // Halten ist der Weg AUS der Sperre — es wird vor ihr geprüft.
        if (ereignis is Uhrereignis.Halten) return halten(zustand, ereignis)

        /* GESPERRT TUT EIN TIPPEN NICHTS. Genau dafür ist die Sperre da: kein
         * versehentlicher Druck mit Handschuhen, kein Phasenstempel, den
         * niemand gesetzt hat. Die Berührung frischt auch die Frist NICHT auf
         * — sonst hielte eine Uhr am Handgelenk sich selbst wach. */
        if (zustand.gesperrt) return Ergebnis(zustand)

        val jetzt = zeitpunkt(ereignis)
        val wach = zustand.copy(letzteBedienungMs = jetzt)

        return when (ereignis) {
            is Uhrereignis.GrosserKnopf, is Uhrereignis.FreieTaste -> grosserKnopf(wach)
            is Uhrereignis.GrosserKnopfGehalten -> Ergebnis(
                if (wach.dienstLaeuft && wach.mitPhasen && wach.einsatzLaeuft) {
                    wach.copy(ansicht = Ansicht.PHASENLISTE)
                } else {
                    wach
                }
            )
            is Uhrereignis.ListenwahL -> listenwahl(wach, ereignis.phase)
            is Uhrereignis.Abschluss -> Ergebnis(wach.copy(ansicht = Ansicht.ABSCHLUSSFRAGE))
            is Uhrereignis.Dienstknopf -> dienstknopf(wach)
            is Uhrereignis.Bestaetigt -> bestaetigt(wach)
            is Uhrereignis.Verworfen -> Ergebnis(wach.copy(ansicht = grundansicht(wach)))
            else -> Ergebnis(wach)
        }
    }

    /**
     * Der Durchlauf (E-S4-21b).
     *
     * Ohne laufenden Dienst tut der große Knopf nichts — dort steht
     * „Dienst beginnen", und das ist ein eigenes Ereignis.
     */
    private fun grosserKnopf(z: Uhrzustand): Ergebnis {
        if (!z.dienstLaeuft || !z.mitPhasen) return Ergebnis(z)

        val naechste = z.naechstePhase
        return if (naechste == null) {
            // Nach Phase 9: derselbe Knopf, andere Bedeutung — MIT Rückfrage.
            Ergebnis(z.copy(ansicht = Ansicht.ABSCHLUSSFRAGE))
        } else {
            Ergebnis(
                z.copy(einsatzLaeuft = true, laufendePhase = naechste),
                listOf(Uhrwirkung.PhaseSetzen(naechste, eroeffnet = !z.einsatzLaeuft)),
            )
        }
    }

    /**
     * Direktwahl aus der Liste (E-S4-21c).
     *
     * Sie **setzt die gewählte Phase jetzt** — auch eine, die schon gesetzt
     * ist. Das ist eine Korrektur und damit ein zweiter Eintrag (E-R45-12),
     * kein Ersetzen.
     */
    private fun listenwahl(z: Uhrzustand, phase: Int): Ergebnis {
        if (!Phasen.uebertragbar(phase)) return Ergebnis(z)
        return Ergebnis(
            z.copy(einsatzLaeuft = true, laufendePhase = phase, ansicht = Ansicht.LAUFEND),
            listOf(Uhrwirkung.PhaseSetzen(phase, eroeffnet = !z.einsatzLaeuft)),
        )
    }

    private fun dienstknopf(z: Uhrzustand): Ergebnis =
        if (z.dienstLaeuft) {
            // Beenden ist eine beendende Handlung — mit Rückfrage.
            Ergebnis(z.copy(ansicht = Ansicht.DIENSTENDEFRAGE))
        } else {
            Ergebnis(z.copy(dienstLaeuft = true, ansicht = Ansicht.LAUFEND),
                listOf(Uhrwirkung.DienstBeginnen))
        }

    private fun bestaetigt(z: Uhrzustand): Ergebnis = when (z.ansicht) {
        Ansicht.ABSCHLUSSFRAGE -> Ergebnis(
            z.copy(
                einsatzLaeuft = false, laufendePhase = Phasen.FREI,
                phasen = emptyList(), ansicht = Ansicht.LAUFEND,
            ),
            listOf(Uhrwirkung.EinsatzAbschliessen),
        )

        Ansicht.DIENSTENDEFRAGE -> Ergebnis(
            z.copy(
                dienstLaeuft = false, einsatzLaeuft = false,
                laufendePhase = Phasen.FREI, phasen = emptyList(), ansicht = Ansicht.START,
            ),
            listOf(Uhrwirkung.DienstBeenden),
        )

        else -> Ergebnis(z)
    }

    /**
     * Die Sperre schließt nach der Frist (E-S4-21d).
     *
     * Sie greift nur im **laufenden Dienst**: Vor dem Dienst gibt es nichts zu
     * verstellen, und eine Startseite, die sich selbst sperrt, wäre eine Hürde
     * ohne Zweck.
     */
    private fun zeitschlag(z: Uhrzustand, jetztMs: Long): Ergebnis {
        if (!sperreAn || z.gesperrt || !z.dienstLaeuft) return Ergebnis(z)
        if (jetztMs - z.letzteBedienungMs < sperrfristMs) return Ergebnis(z)
        return Ergebnis(z.copy(gesperrt = true, ansicht = grundansicht(z)))
    }

    /**
     * Halten — der einzige Weg aus der Sperre.
     *
     * Ein zu kurzes Halten ist ein Tippen und tut gesperrt nichts. Im
     * **entsperrten** Zustand öffnet ein Halten die Phasenliste (E-S4-21c);
     * das ist derselbe Griff mit zwei Bedeutungen, und der Unterschied ist der
     * Zustand — nicht die Dauer.
     */
    private fun halten(z: Uhrzustand, e: Uhrereignis.Halten): Ergebnis {
        if (z.gesperrt) {
            return if (e.dauerMs >= haltedauerMs) {
                Ergebnis(z.copy(gesperrt = false, letzteBedienungMs = e.jetztMs))
            } else {
                Ergebnis(z)          // zu kurz: gesperrt bleibt gesperrt
            }
        }
        return verarbeite(z, Uhrereignis.GrosserKnopfGehalten(e.jetztMs))
    }

    private fun grundansicht(z: Uhrzustand): Ansicht =
        if (z.dienstLaeuft) Ansicht.LAUFEND else Ansicht.START

    private fun zeitpunkt(e: Uhrereignis): Long = when (e) {
        is Uhrereignis.GrosserKnopf -> e.jetztMs
        is Uhrereignis.GrosserKnopfGehalten -> e.jetztMs
        is Uhrereignis.ListenwahL -> e.jetztMs
        is Uhrereignis.Abschluss -> e.jetztMs
        is Uhrereignis.Dienstknopf -> e.jetztMs
        is Uhrereignis.Bestaetigt -> e.jetztMs
        is Uhrereignis.Verworfen -> e.jetztMs
        is Uhrereignis.FreieTaste -> e.jetztMs
        is Uhrereignis.Halten -> e.jetztMs
        is Uhrereignis.Zeitschlag -> e.jetztMs
    }

    companion object {
        /** Richtwert 10 s (E-S4-21d) — blind gewählt, am Gerät nachzumessen. */
        const val SPERRFRIST_MS = 10_000L

        /** Richtwert 1 s — dito. */
        const val HALTEDAUER_MS = 1_000L
    }
}
