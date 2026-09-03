package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.ColumnScope
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.setValue
import androidx.compose.runtime.remember
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.getValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.Phasen

/** Eine gesetzte Phase, wie die Anzeige sie braucht. */
data class Phasenzeile(val nummer: Int, val hhmm: String)

/**
 * Der Phasenteil der Dienstansicht (E-S4-08, E-S4-21b).
 *
 * **Ein großer Knopf für die nächste Phase**, darunter die Liste. Jede Zeile
 * der Liste setzt ihre Phase direkt — eine Korrektur erzeugt dabei einen
 * **zweiten Eintrag** und überschreibt den ersten nicht (E-R45-12); die Liste
 * zeigt deshalb alle Zeiten, die eine Phase hat, und nicht die letzte.
 *
 * Nach der letzten Phase wird der große Knopf zu „Einsatz abschließen" —
 * **mit Rückfrage**: Ein versehentlicher letzter Tipp beendet nichts.
 *
 * IM NUR-AUFZEICHNEN-MODUS wird dieser ganze Teil nicht gezeichnet (E-S4-20):
 * kein Knopf, den man mit Handschuhen versehentlich trifft.
 */
@Composable
fun ColumnScope.Phasenteil(
    einsatzLaeuft: Boolean,
    laufendePhase: Int,
    laufendeSeit: String?,
    naechstePhase: Int?,
    gesetzte: List<Phasenzeile>,
    aufPhase: (Int) -> Unit,
    aufAbschluss: () -> Unit,
) {
    Text(
        text = if (einsatzLaeuft) {
            stringResource(
                R.string.einsatz_laeuft,
                Phasen.beschriftung(laufendePhase),
                laufendeSeit.orEmpty(),
            )
        } else {
            stringResource(R.string.einsatz_kein)
        },
        color = Farbe.dunkelblau, fontSize = 19.sp, fontWeight = FontWeight.SemiBold,
    )

    /* DER EINE GROSSE KNOPF. Ohne laufenden Einsatz trägt er Phase 2 — sie
     * startet den Einsatz (E-S4-08); es gibt kein separates
     * „Einsatz beginnen", weil es im Einsatz auch keine Zeit dafür gibt. */
    val naechste = naechstePhase ?: if (!einsatzLaeuft) Phasen.ERSTE else null
    if (naechste != null) {
        KnopfPrimaer(
            stringResource(R.string.phase_knopf, naechste, Phasen.beschriftung(naechste))
        ) { aufPhase(naechste) }
    }

    if (einsatzLaeuft) {
        /* DIE LISTE ZEIGT NICHT MEHR ALLE ACHT PHASEN (B-S5Z-16, E-S5Z-33).
         *
         * DER GRUND IST GEMESSEN: Acht Reihen zu 48 dp sind rund 412 dp und
         * damit der größte Block der laufenden Ansicht (1015 dp gesamt). Auf
         * einem 800-dp-Telefon lag „Einsatz abschließen" dadurch bei
         * 771–819 dp — halb unter der Faltkante, also die Handlung, die den
         * Einsatz beendet, nur mit Schieben erreichbar. Der Bilderlauf hat
         * es gefunden; gelesen hätte es niemand.
         *
         * SICHTBAR SIND: alle **gesetzten** Phasen und die **nächste**. Die
         * gesetzten bleiben, weil sie die Dokumentation sind und weil ein
         * erneutes Tippen die Korrektur ist (E-R45-12); die nächste, weil sie
         * der nächste Schritt ist. Alles andere liegt einen Druck entfernt.
         *
         * DIE DIREKTWAHL GEHT NICHT VERLOREN, sie wird einen Schritt tiefer
         * (E-S4-21c hatte die Liste als „Übersicht und Direktwahl in einem"
         * gebaut). Das ist der Preis, und er ist bewusst bezahlt: Ein Knopf
         * unter dem Rand ist teurer als ein Knopf hinter einem Knopf.
         *
         * WAS DAS NICHT LÖST: Gegen Ende eines Einsatzes sind fast alle
         * Phasen gesetzt, und dann ist die Liste wieder lang. Die Zahlen
         * dazu stehen im Prüfdokument. */
        var alleZeigen by remember(einsatzLaeuft) { mutableStateOf(false) }
        val sichtbar = if (alleZeigen) {
            Phasen.UEBERTRAGEN.toList()
        } else {
            Phasen.UEBERTRAGEN.filter { nummer ->
                nummer == naechstePhase || gesetzte.any { it.nummer == nummer }
            }
        }

        Text(
            text = stringResource(R.string.phasenliste),
            color = Farbe.gedaempft, fontSize = 13.sp,
        )
        Column(verticalArrangement = Arrangement.spacedBy(Abstand.eins)) {
            for (nummer in sichtbar) {
                val zeiten = gesetzte.filter { it.nummer == nummer }
                Phasenreihe(nummer, zeiten) { aufPhase(nummer) }
            }
        }
        /* Der Knopf verschwindet, wenn es nichts mehr aufzuklappen gibt — ein
         * Knopf, der nichts tut, ist schlimmer als keiner. */
        if (sichtbar.size < Phasen.UEBERTRAGEN.count()) {
            KnopfNeutral(stringResource(R.string.phasen_alle_zeigen)) { alleZeigen = true }
        } else if (alleZeigen) {
            KnopfNeutral(stringResource(R.string.phasen_weniger_zeigen)) { alleZeigen = false }
        }

        // Beendende Handlung: vollflächig rot, mit Rückfrage (E-S4-22a).
        KnopfBeenden(stringResource(R.string.einsatz_abschliessen)) { aufAbschluss() }
    }
}

@Composable
private fun Phasenreihe(nummer: Int, zeiten: List<Phasenzeile>, aufTippen: () -> Unit) {
    val gesetzt = zeiten.isNotEmpty()
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(min = BEDIENHOEHE)
            .background(Farbe.schnee, RoundedCornerShape(Radius.normal))
            .border(1.dp, Farbe.linie, RoundedCornerShape(Radius.normal))
            .clickable(onClick = aufTippen)
            .padding(horizontal = Abstand.drei, vertical = Abstand.zwei),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(Abstand.zwei),
    ) {
        Text(
            text = nummer.toString(),
            color = Farbe.gedaempft, fontSize = 13.sp, fontFamily = FontFamily.Monospace,
        )
        Text(
            text = Phasen.beschriftung(nummer),
            color = if (gesetzt) Farbe.gedaempft else Farbe.dunkelblau,
            fontSize = 15.sp,
            modifier = Modifier.weight(1f),
        )
        /* ALLE Zeiten einer Phase, nicht die letzte: Eine erneut gesetzte
         * Phase ist eine Korrektur und damit eine Information (E-R45-12).
         * Wer nur die letzte zeigte, verschwiege genau das. */
        Text(
            text = zeiten.joinToString(" · ") { it.hhmm },
            color = Farbe.blauTief, fontSize = 13.sp, fontWeight = FontWeight.SemiBold,
        )
    }
}
