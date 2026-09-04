package org.genem.nadoku.handy.aufzeichnung

/**
 * Wie es um den Akku steht, während aufgezeichnet wird.
 *
 * DREI STUFEN UND NICHT EINE, weil sie Verschiedenes bedeuten: Bei 25 % ist
 * noch alles offen, bei 15 % wird es knapp, bei 10 % geht es zu Ende. Ein
 * einziger Schwellwert müsste sich zwischen „zu früh, wird überlesen" und „zu
 * spät, hilft nicht mehr" entscheiden.
 *
 * Die Reihenfolge ist die der Schwere, wie bei [Ortungsstand].
 */
enum class Akkustufe(
    /** Bei welchem Ladestand (in Prozent) diese Stufe beginnt. */
    val schwelleProzent: Int,
) {
    /** Über 25 % — es gibt nichts zu sagen. */
    OK(101),

    /** 25 % und darunter: Ein Hinweis, mehr nicht. */
    KNAPP(25),

    /** 15 % und darunter: Hinweis **mit** Angebot, den Dienst zu beenden. */
    NIEDRIG(15),

    /** 10 % und darunter: dasselbe, dringlicher. */
    KRITISCH(10),
    ;

    /** Wird bei dieser Stufe gewarnt? */
    val warnt: Boolean get() = this != OK

    /**
     * Bekommt die Warnung einen Knopf „Dienst beenden"?
     *
     * ERST AB [NIEDRIG], und das ist der Unterschied zwischen den Stufen. Bei
     * 25 % ist ein Dienstende die falsche Empfehlung — da lädt man nach. Ein
     * Knopf, der das Naheliegende anbietet, bevor es naheliegt, wird beim
     * dritten Mal gedrückt, ohne gelesen zu werden.
     */
    val bietetDienstende: Boolean get() = this == NIEDRIG || this == KRITISCH

    companion object {
        /**
         * Die Stufe zu einem Ladestand. Die Reihenfolge der Prüfung geht von
         * der schwersten zur leichtesten — bei 8 % gilt [KRITISCH] und nicht
         * [KNAPP], obwohl beide Schwellen unterschritten sind.
         */
        fun zu(prozent: Int): Akkustufe = when {
            prozent <= KRITISCH.schwelleProzent -> KRITISCH
            prozent <= NIEDRIG.schwelleProzent -> NIEDRIG
            prozent <= KNAPP.schwelleProzent -> KNAPP
            else -> OK
        }
    }
}

/** Was aus einer Akkumessung folgt. */
enum class Akkubefehl {
    /** Nichts tun — die Lage hat sich nicht warnungsrelevant geändert. */
    NICHTS,

    /** Warnung stellen: Es ist eine neue, tiefere Stufe erreicht. */
    POSTEN,

    /** Warnung zurückziehen — es wird geladen oder der Stand ist wieder hoch. */
    LOESCHEN,
}

/**
 * Der Akkuwächter (Backlog Nr. 82, Erweiterung vom 04.09.2026) — **die
 * Zustandsmaschine ohne Android**.
 *
 * WOZU ER DA IST. Die Aufzeichnung braucht durchgehend GPS und ist damit über
 * einen langen Dienst der größte Stromverbraucher des Geräts. Bis hierher
 * sagte die App das einmal beim ersten Dienstbeginn und danach nie wieder —
 * ein Satz, den man im Januar liest und im Juli gebraucht hätte.
 *
 * ER SCHALTET NICHTS AB, und das ist die eigentliche Entscheidung. Eine
 * automatische Abschaltung bei X % wäre die bequeme Lösung und die falsche:
 * Sie beendete die Aufzeichnung **still**, mitten im Dienst, genau in dem
 * Augenblick, in dem niemand auf das Handy sieht. Was in der Dokumentation
 * fehlt, fehlt hinterher unwiederbringlich — der Dienst lässt sich nicht
 * nachfahren. Das ganze Paket E (Android 0.8.0) ist dagegen gebaut worden,
 * dass die App etwas anderes tut, als sie sagt; hier dieselbe Linie. Der
 * Mensch entscheidet, die App sagt ihm nur rechtzeitig Bescheid.
 *
 * ER HÄLT KEINE UHR UND KEINEN FADEN. Jede Messung wird ihm übergeben; damit
 * ist er ohne Warten prüfbar, wie [Ausduenner] und [Ortungswaechter].
 *
 * ER WARNT NUR BEIM ABSTIEG. Eine Warnung je erreichter Stufe, nicht je
 * Messung — sonst stünde bei 24 % zwölf Stunden lang dieselbe Meldung, und
 * die letzte bei 10 % ginge in ihr unter. Steigt der Stand wieder (jemand hat
 * nachgeladen), setzt sich die Stufe zurück und dieselbe Schwelle warnt
 * erneut; das ist gewollt: Ein zweites Absacken ist ein zweites Ereignis.
 */
class Akkuwaechter {

    /** Die tiefste Stufe, zu der bereits gewarnt wurde. */
    var gewarntBis: Akkustufe = Akkustufe.OK
        private set

    /** Die Stufe der letzten Messung — auch ohne Warnung. */
    var stand: Akkustufe = Akkustufe.OK
        private set

    /** Lädt das Gerät gerade? */
    var laedt: Boolean = false
        private set

    /**
     * Eine Messung verarbeiten.
     *
     * @param prozent Ladestand 0–100.
     * @param laedt Hängt das Gerät am Strom?
     */
    fun messen(prozent: Int, laedt: Boolean): Akkubefehl {
        this.laedt = laedt
        val neu = Akkustufe.zu(prozent.coerceIn(0, 100))
        val vorher = stand
        stand = neu

        /* AM STROM WIRD NICHT GEWARNT — und die Stufe wird zurückgesetzt.
         *
         * Wer nachlädt, hat gehandelt; eine Warnung, die dann stehen bleibt,
         * ist eine über einen Zustand, den es nicht mehr gibt. Das
         * Zurücksetzen ist dabei der wichtigere Teil: Wird das Kabel später
         * gezogen und der Stand fällt wieder, warnt dieselbe Schwelle erneut.
         * Ohne das bliebe der zweite Abstieg stumm. */
        if (laedt) {
            val hatte = gewarntBis.warnt
            gewarntBis = Akkustufe.OK
            return if (hatte) Akkubefehl.LOESCHEN else Akkubefehl.NICHTS
        }

        /* WIEDER ÜBER DER SCHWELLE, OHNE KABEL. Kommt vor: Der Ladestand
         * springt beim Abkühlen oder nach einer Neuberechnung des Systems.
         * Behandelt wie das Laden — Warnung weg, Stufe zurück. */
        if (!neu.warnt) {
            val hatte = gewarntBis.warnt
            gewarntBis = Akkustufe.OK
            return if (hatte) Akkubefehl.LOESCHEN else Akkubefehl.NICHTS
        }

        /* GEWARNT WIRD NUR BEIM ERREICHEN EINER TIEFEREN STUFE.
         *
         * `ordinal` trägt hier die Ordnung, weil die Aufzählung nach Schwere
         * sortiert ist (dieselbe Zusage wie bei `Ortungsstand`). Bei 24 % und
         * dann 23 % steht `neu` zweimal auf KNAPP — die zweite Messung ergibt
         * NICHTS, und die Meldung von vorhin bleibt stehen. */
        if (neu.ordinal > gewarntBis.ordinal) {
            gewarntBis = neu
            return Akkubefehl.POSTEN
        }

        // Auch das Steigen INNERHALB der Warnstufen zieht die Warnung nicht
        // zurück: von 9 % auf 12 % ist keine Entwarnung, es ist Rauschen.
        if (vorher != neu) return Akkubefehl.NICHTS
        return Akkubefehl.NICHTS
    }

    /** Für den Neubeginn eines Dienstes. */
    fun zuruecksetzen() {
        gewarntBis = Akkustufe.OK
        stand = Akkustufe.OK
        laedt = false
    }

    companion object {
        /**
         * Wie oft gemessen wird, in Millisekunden.
         *
         * ZWEI MINUTEN, nicht Sekunden: Ein Akku fällt über Stunden, nicht
         * über Augenblicke. Die Messung selbst kostet nichts (der Stand liegt
         * als Sticky-Intent bereit, es wird kein Sensor geweckt) — aber jeder
         * Weckruf des Handlers kostet, und ein Wächter, der gegen den
         * Stromverbrauch warnt, sollte nicht selbst welchen erzeugen.
         *
         * Der gröbere Takt kostet nichts an Genauigkeit: Zwischen 15 % und
         * 10 % liegen bei laufender Aufzeichnung Dutzende Minuten.
         */
        const val TAKT_MS = 120_000L
    }
}
