package org.genem.nadoku.uhr

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.wear.compose.material.MaterialTheme
import androidx.wear.compose.material.Text
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Bildmarke
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Motiv

/**
 * Der Einstieg der Uhr-App.
 *
 * SIE IST FERNBEDIENUNG, SONST NICHTS (E-S4-11): kein GPS, keine Kopplung,
 * keine Zugangsdaten, keine Reanimation. Was sie tut, ist Zeitstempel setzen
 * und ans Handy melden -- das Handy quittiert (E-S4-10).
 *
 * STAND B1: Das Modul baut im selben Gradle-Lauf und zeigt die Bildmarke in
 * der Dunkelgrund-Fassung. Das Bedienbild -- Durchlaufknopf, Phasenliste,
 * Sperre -- entsteht in C1.
 *
 * BLIND GEBAUT: Es gibt im Container keinen Emulator (E-R45-8) und bislang
 * keine Uhr (E-R45-7). Es gibt von dieser Ansicht kein Bildschirmfoto, und das
 * steht so im Pruefdokument, statt verschwiegen zu werden.
 */
class UhrActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { GeruestAnsicht() }
    }
}

/**
 * Kachelkante der Bildmarke auf den LAUFENDEN Ansichten: ein Sechstel der
 * Displayhoehe (E-S4-22a). Auf der Startseite steht sie groesser -- die
 * 27-%-Stufung der Garmin (tools/uhr-bilder: 70/260 des Bezugsgeraets).
 *
 * Beide Werte sind BLIND GEWAEHLT und am Geraet nachzumessen; sie gehoeren in
 * den Wear-Teil von docs/Geraete-Eingabe.md (E-S4-19).
 */
const val MARKE_ANTEIL_LAUFEND = 1f / 6f
const val MARKE_ANTEIL_START = 0.27f

@Composable
fun GeruestAnsicht(logoWahl: LogoWahl = LogoWahl.WECHSELND) {
    val motiv = Bildmarke.motiv(logoWahl)
    MaterialTheme {
        Column(
            modifier = Modifier
                .fillMaxSize()
                .background(Farbe.asphalt)
                .padding(horizontal = 16.dp, vertical = 24.dp),
            horizontalAlignment = Alignment.CenterHorizontally,
            verticalArrangement = Arrangement.spacedBy(8.dp, Alignment.CenterVertically),
        ) {
            // Die Startseite traegt die groessere Kachel. Sie wird hier an der
            // gesetzten Hoehe berechnet, nicht am Display -- das kommt in C1,
            // wenn die Ansicht ihre tatsaechliche Groesse kennt.
            Bildmarke(
                motiv = motiv,
                kachel = 62.dp,
                aufDunkel = true,
                beschreibung = stringResource(
                    if (motiv == Motiv.LUFT) R.string.marke_luft_beschreibung
                    else R.string.marke_boden_beschreibung
                ),
            )
            Text(
                text = stringResource(R.string.app_name),
                color = Farbe.aufDunkel,
                fontSize = 19.sp,
                textAlign = TextAlign.Center,
            )
            Text(
                text = stringResource(R.string.stand_geruest),
                color = Farbe.sand,
                fontSize = 13.sp,
                textAlign = TextAlign.Center,
            )
            Text(
                text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
                color = Farbe.sand,
                fontSize = 13.sp,
                textAlign = TextAlign.Center,
            )
        }
    }
}
