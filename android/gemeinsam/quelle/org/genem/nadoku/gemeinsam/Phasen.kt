package org.genem.nadoku.gemeinsam

/**
 * Die Einsatzphasen (JSON-Vertrag Abschnitt 7).
 *
 * UEBERTRAGEN WERDEN AUSSCHLIESSLICH DIE NUMMERN 2 BIS 9. Phase 1 ("Frei") ist
 * ein Anzeigezustand und erzeugt keinen Eintrag; eine Phase 10 gibt es nicht --
 * der Abschluss eines Einsatzes laeuft ueber `final: true` zusammen mit
 * `ended_at` (Vertrag 3). Wer nach der alten Fassung des Vertrags eine Phase 10
 * sendete, bekam keine Fehlermeldung, sondern einen Eintrag weniger.
 *
 * DIE BESCHRIFTUNGEN SIND ANZEIGE, NICHT VERTRAG. Der Server bekommt Zahlen zu
 * sehen. Sie stehen hier trotzdem an EINER Stelle -- zwei Geraete, die
 * dieselbe Phase verschieden benennen, sind ein Fehlerbericht mit Ansage.
 *
 * NEUTRAL SEIT VERTRAG 1.3: Phase 3 hiess "Abflug", Phase 7 "Landung
 * Krankenhaus". Die Anwendung dokumentiert auch bodengebundene
 * Notarzteinsaetze; eine Beschriftung, die eine der beiden Arten benennt,
 * waere an der anderen schlicht falsch. Der Wortlaut ist der der Garmin-Uhr
 * (Const.PHASE_LABELS) -- der Vertrag fuehrt fuer 8 und 9 die laengeren
 * Formen "Uebergabezeit" und "Endzeit des Einsatzes"; auf einem Uhrendisplay
 * ist die kurze die einzige, die ankommt, und der Vertrag stellt es
 * ausdruecklich frei.
 */
object Phasen {

    /** Kleinste uebertragene Phasennummer. */
    const val ERSTE = 2

    /** Groesste uebertragene Phasennummer. */
    const val LETZTE = 9

    /** Anzeigezustand ohne Eintrag -- "kein Einsatz laeuft". */
    const val FREI = 1

    /** Die uebertragbaren Nummern, in ihrer Reihenfolge. */
    val UEBERTRAGEN: IntRange = ERSTE..LETZTE

    private val BESCHRIFTUNG = mapOf(
        1 to "Frei",
        2 to "Alarmierung",
        3 to "Ausrücken",
        4 to "Ankunft Einsatzort",
        5 to "Ankunft PatientIn",
        6 to "Transportbeginn",
        7 to "Ankunft Klinik",
        8 to "Übergabe",
        9 to "Einsatzende",
    )

    /**
     * Kurzform fuer die Phasenliste der Uhr (E-S4-21c). Nur dort, wo die lange
     * Form auf einem runden Display umbricht -- alles andere bleibt gleich,
     * damit nicht zwei Wortlaeufe nebeneinander entstehen.
     */
    private val KURZ = mapOf(
        4 to "Ank. Einsatzort",
        5 to "Ank. PatientIn",
    )

    fun beschriftung(phase: Int): String =
        BESCHRIFTUNG[phase] ?: error("Phase $phase gibt es nicht (gueltig: 1 bis $LETZTE).")

    fun kurz(phase: Int): String = KURZ[phase] ?: beschriftung(phase)

    /** Darf diese Nummer an den Server? (Vertrag 3.2: ganze Zahl 2 bis 9.) */
    fun uebertragbar(phase: Int): Boolean = phase in UEBERTRAGEN

    /**
     * Die naechste Phase im Durchlauf (E-S4-21b: 2 -> ... -> 9).
     *
     * Nach der letzten gibt es KEINE naechste: Der Durchlaufknopf wird dort zu
     * "Einsatz abschliessen" mit Rueckfrage. `null` ist genau diese Auskunft --
     * und nicht etwa ein Rueckfall auf Phase 2, der einen Einsatz stillschweigend
     * von vorn begaenne.
     */
    fun naechste(aktuell: Int): Int? = when {
        aktuell < ERSTE -> ERSTE
        aktuell >= LETZTE -> null
        else -> aktuell + 1
    }
}
