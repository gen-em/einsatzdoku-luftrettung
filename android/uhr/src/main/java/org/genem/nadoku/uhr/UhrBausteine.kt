package org.genem.nadoku.uhr

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.text.font.FontFamily
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.wear.compose.material.Text
import org.genem.nadoku.gemeinsam.Farbe

/**
 * Die Bausteine der Uhr-Oberfläche.
 *
 * SIE SIND NICHT DIE DES HANDYS, und das ist Absicht: Ein rundes Display
 * schneidet an den Seiten ab, die Bedienung geschieht mit einem Finger und
 * oft mit Handschuhen, und der Grund ist Asphalt statt Rauch. Was beide
 * teilen, sind die **Farbrollen** (E-S4-22a) — Orange handelt, Blau erklärt
 * und bestätigt, Rot warnt und beendet.
 *
 * ALLE MASSE SIND BLIND GEWÄHLT (E-R45-7): Es gibt keinen Emulator und keine
 * Uhr. Berührziele, Schriftgrößen und die Randabstände gehören am Gerät
 * nachgemessen und danach in den Wear-Teil von `docs/Geraete-Eingabe.md`.
 */

/**
 * Bedienhöhe an der Uhr: **48 dp**, nicht die 44 des Web.
 *
 * Das ist kein Widerspruch zu `CLAUDE.md` 5, sondern dessen Zweck: Die 44 px
 * sind eine Untergrenze für Maus und Finger am Schreibtisch. Hier trifft ein
 * Finger im Einsatz ein rundes Display, oft mit Handschuh — Androids eigene
 * Empfehlung von 48 dp ist die kleinere Zumutung. (Am Handy bleibt es bei 44;
 * der Unterschied ist als Fund B-S4-02 vermerkt und dort zu entscheiden.)
 */
val UHR_BEDIENHOEHE: Dp = 48.dp


/** Der eine große Knopf: Orange handelt, dunkelblaue Schrift (E-P3-15). */
@Composable
fun UhrKnopf(
    beschriftung: String,
    modifier: Modifier = Modifier,
    flaeche: Color? = null,
    schrift: Color? = null,
    aufTippen: () -> Unit,
) {
    val f = flaeche ?: Farbe.orange
    val s = schrift ?: Farbe.dunkelblau
    Box(
        modifier = modifier
            .fillMaxWidth()
            .heightIn(min = UHR_BEDIENHOEHE)
            .background(f, RoundedCornerShape(percent = 50))
            .clickable(onClick = aufTippen)
            .padding(horizontal = 12.dp, vertical = 8.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = beschriftung, color = s, fontSize = 15.sp,
            fontWeight = FontWeight.SemiBold, textAlign = TextAlign.Center,
        )
    }
}

/** Die leise Handlung: nur Rand, keine Fläche. */
@Composable
fun UhrKnopfLeise(beschriftung: String, modifier: Modifier = Modifier, aufTippen: () -> Unit) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .heightIn(min = UHR_BEDIENHOEHE)
            .border(1.dp, Farbe.sand, RoundedCornerShape(percent = 50))
            .clickable(onClick = aufTippen)
            .padding(horizontal = 12.dp, vertical = 8.dp),
        contentAlignment = Alignment.Center,
    ) {
        Text(text = beschriftung, color = Farbe.aufDunkel, fontSize = 15.sp,
            textAlign = TextAlign.Center)
    }
}

/**
 * Eine Zeile der Phasenliste (E-S4-21c) — **Übersicht und Direktwahl in
 * einem**: Sie zeigt die gesetzten Zeiten und setzt beim Tippen.
 */
@Composable
fun Phasenreihe(
    nummer: Int,
    beschriftung: String,
    zeiten: List<String>,
    naechste: Boolean,
    aufTippen: () -> Unit,
) {
    Row(
        modifier = Modifier
            .fillMaxWidth()
            .heightIn(min = UHR_BEDIENHOEHE)
            .clickable(onClick = aufTippen)
            .padding(horizontal = 8.dp, vertical = 6.dp),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(6.dp),
    ) {
        Text(
            text = nummer.toString(),
            color = Farbe.sand, fontSize = 13.sp, fontFamily = FontFamily.Monospace,
        )
        Text(
            text = beschriftung,
            color = if (naechste) Farbe.orange else Farbe.aufDunkel,
            fontSize = 13.sp,
            fontWeight = if (naechste) FontWeight.SemiBold else FontWeight.Normal,
            modifier = Modifier.weight(1f),
        )
        /* ALLE Zeiten, nicht die letzte: Eine erneut gesetzte Phase ist eine
         * Korrektur und damit eine Information (E-R45-12). Blau bestätigt. */
        Text(
            text = zeiten.joinToString(" · ")
                .ifEmpty { androidx.compose.ui.res.stringResource(org.genem.nadoku.R.string.keine_zeit) },
            color = if (naechste) Farbe.orange else Farbe.blau,
            fontSize = 13.sp,
        )
    }
}

/**
 * Der rote Aufnahmepunkt — auf **jedem laufenden Bildschirm**, Handy wie Uhr
 * (E-S4-22a). Ein grafisches Objekt: gemessen gegen 3:1, nicht 4,5:1.
 */
@Composable
fun Aufnahmepunkt(modifier: Modifier = Modifier) {
    Box(modifier.size(8.dp).background(Farbe.rot, CircleShape))
}
