package org.genem.nadoku.handy

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.sp
import androidx.compose.material3.Text
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl

/**
 * Der eine Bildschirm der Handy-App.
 *
 * EINE ACTIVITY, mehrere Ansichten darin. Der Grund ist der Vordergrunddienst
 * (ab B3): Die Aufzeichnung laeuft im Dienst weiter, waehrend die Oberflaeche
 * beendet ist -- die Activity ist ein Fenster auf einen Zustand, den sie nicht
 * besitzt. Mehrere Activities muessten sich diesen Zustand teilen und waeren
 * nur mehr Stellen, an denen er auseinanderlaufen kann.
 *
 * STAND B1: Das Geruest steht. Was hier zu sehen ist, ist die Kopfleiste mit
 * der Bildmarke, die Farbrollen und die Fassungsnummer -- und die ehrliche
 * Auskunft, dass die App noch nichts kann. Kopplung folgt mit B2.
 */
class HauptActivity : ComponentActivity() {
    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { GeruestAnsicht() }
    }
}

@Composable
fun GeruestAnsicht(logoWahl: LogoWahl = LogoWahl.WECHSELND) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Farbe.rauch),
    ) {
        Kopfleiste(titel = stringResource(R.string.app_name), logoWahl = logoWahl)

        Column(
            modifier = Modifier.padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Text(
                    text = stringResource(R.string.stand_geruest),
                    color = Farbe.dunkelblau,
                    fontSize = 19.sp,
                    fontWeight = FontWeight.SemiBold,
                )
                Hinweiskasten(stringResource(R.string.stand_geruest_hinweis))
                Zustandszeile(
                    text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
                    punktfarbe = Farbe.blau,
                    schriftfarbe = Farbe.gedaempft,
                )
            }
        }
    }
}

@Preview(showBackground = true)
@Composable
private fun VorschauGeruest() = GeruestAnsicht(LogoWahl.LUFT)
