package org.genem.nadoku.handy

import android.os.Bundle
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.pluralStringResource
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.sp
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.kopplung.Abweisung
import org.genem.nadoku.handy.kopplung.Geraeteangabe
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.kopplung.Kopplungsdienst
import org.genem.nadoku.handy.kopplung.Kopplungsergebnis
import org.genem.nadoku.handy.kopplung.Syncstand
import org.genem.nadoku.handy.kopplung.Trennergebnis
import org.genem.nadoku.handy.tresor.KeystoreTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import java.io.File

/**
 * Der eine Bildschirm der Handy-App.
 *
 * EINE ACTIVITY, mehrere Ansichten darin. Der Grund ist der Vordergrunddienst
 * (ab B3): Die Aufzeichnung läuft weiter, während die Oberfläche beendet ist —
 * die Activity ist ein Fenster auf einen Zustand, den sie nicht besitzt.
 * Mehrere Activities müssten sich diesen Zustand teilen und wären nur mehr
 * Stellen, an denen er auseinanderlaufen kann.
 *
 * STAND B2: Kopplung, Trennen und Schlüsselablage stehen. Aufzeichnung (B3)
 * und Senden (B4) folgen; der Rückstand ist deshalb noch fest 0 — die
 * Warteschlange, die ihn zählt, gibt es erst mit B4.
 */
class HauptActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)

        val einstellungen = Einstellungen(this)
        val tresor = Schluesseltresor(
            File(filesDir, Schluesseltresor.DATEINAME),
            KeystoreTresorschluessel(),
        )
        val dienst = Kopplungsdienst(
            netzweg = HttpNetzweg(),
            tresor = tresor,
            // B4 setzt hier die Warteschlange ein. Bis dahin gibt es nichts zu
            // senden -- und eine erfundene Zahl waere schlimmer als die Null.
            rueckstand = { 0 },
        )

        val masse = resources.displayMetrics
        val geraet = Geraeteangabe.vomGeraet(
            breite = masse.widthPixels,
            hoehe = masse.heightPixels,
            appFassung = BuildConfig.VERSION_NAME,
        )

        setContent {
            NAdokuOberflaeche(einstellungen, tresor, dienst, geraet)
        }
    }
}

@Composable
fun NAdokuOberflaeche(
    einstellungen: Einstellungen,
    tresor: Schluesseltresor,
    dienst: Kopplungsdienst,
    geraet: Geraeteangabe,
) {
    val hilfsfaden = rememberCoroutineScope()

    // „Wechselnd“ wird EINMAL JE APP-START gewürfelt und bleibt stehen
    // (E-S4-22b). Die feste Wahl kommt mit den App-Einstellungen in B3.
    val logoWahl = remember { LogoWahl.WECHSELND }

    var serverBasis by remember { mutableStateOf(einstellungen.serverBasis) }
    var gekoppelt by remember { mutableStateOf(tresor.gekoppelt()) }
    var schritt by remember { mutableStateOf<Kopplungsschritt>(Kopplungsschritt.Wahl) }
    var trennmeldung by remember { mutableStateOf<Trennergebnis?>(null) }
    var trennfrageOffen by remember { mutableStateOf(false) }

    if (!gekoppelt) {
        KopplungAnsicht(
            schritt = schritt,
            serverBasis = serverBasis,
            logoWahl = logoWahl,
            aufSchritt = { schritt = it },
            aufKoppeln = { basis, code ->
                schritt = Kopplungsschritt.Laeuft
                hilfsfaden.launch {
                    // Netz gehört nicht auf den Anzeigefaden: Android bricht
                    // die App dafür mit NetworkOnMainThreadException ab.
                    val e = withContext(Dispatchers.IO) { dienst.koppeln(basis, code, geraet) }
                    when (e) {
                        is Kopplungsergebnis.Gekoppelt -> {
                            einstellungen.serverBasis = basis
                            serverBasis = basis
                            gekoppelt = true
                            schritt = Kopplungsschritt.Wahl
                        }
                        is Kopplungsergebnis.Abgewiesen ->
                            schritt = Kopplungsschritt.Abgewiesen(e.art, e.meldung)
                    }
                }
            },
        )
        return
    }

    GekoppeltAnsicht(
        serverBasis = serverBasis,
        logoWahl = logoWahl,
        trennmeldung = trennmeldung,
        trennfrageOffen = trennfrageOffen,
        aufTrennfrage = { trennfrageOffen = it },
        aufTrennen = {
            trennfrageOffen = false
            hilfsfaden.launch {
                val e = withContext(Dispatchers.IO) { dienst.trennen(serverBasis.orEmpty()) }
                trennmeldung = e
                // Nur ein Rückstand lässt die Kopplung stehen — in jedem
                // anderen Fall ist sie lokal fort (E-S4-12). Was geschehen ist,
                // wird auf dem Kopplungsbildschirm GESAGT und nicht
                // verschwiegen: Ein Servereintrag, der noch steht, belegt einen
                // der fünf Geräteplätze.
                if (e !is Trennergebnis.Rueckstand) {
                    schritt = Kopplungsschritt.Getrennt(nurLokal = e is Trennergebnis.NurLokal)
                    gekoppelt = false
                }
            }
        },
    )
}

@Composable
fun GekoppeltAnsicht(
    serverBasis: String?,
    logoWahl: LogoWahl,
    trennmeldung: Trennergebnis?,
    trennfrageOffen: Boolean = false,
    aufTrennfrage: (Boolean) -> Unit = {},
    aufTrennen: () -> Unit,
) {
    // Der Rückstand kommt mit B4; bis dahin gibt es keine Warteschlange.
    val stand = Syncstand.ermittle(serverBasis, gekoppelt = true, rueckstand = 0)

    if (trennfrageOffen) {
        Trennfrage(aufJa = aufTrennen, aufNein = { aufTrennfrage(false) })
    }

    Column(modifier = Modifier.fillMaxSize().background(Farbe.rauch)) {
        Kopfleiste(titel = stringResource(R.string.app_name), logoWahl = logoWahl)

        Column(
            modifier = Modifier.padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Zustandszeile(
                    text = stringResource(
                        R.string.sync_gekoppelt_mit,
                        serverBasis?.removePrefix("https://")?.trimEnd('/').orEmpty(),
                    ),
                    punktfarbe = Farbe.blau,
                    schriftfarbe = Farbe.blauTief,
                )
                Zustandszeile(
                    text = when (stand) {
                        is Syncstand.Vollstaendig -> stringResource(R.string.sync_vollstaendig)
                        is Syncstand.Rueckstand -> pluralStringResource(
                            R.plurals.sync_rueckstand, stand.pakete, stand.pakete,
                        )
                        is Syncstand.NichtEingerichtet ->
                            stringResource(R.string.sync_nicht_eingerichtet)
                    },
                    punktfarbe = when (stand) {
                        is Syncstand.Vollstaendig -> Farbe.blau
                        is Syncstand.Rueckstand -> Farbe.orange
                        is Syncstand.NichtEingerichtet -> Farbe.rot
                    },
                    schriftfarbe = Farbe.gedaempft,
                )

                Text(
                    text = stringResource(R.string.stand_geruest),
                    color = Farbe.dunkelblau, fontSize = 19.sp, fontWeight = FontWeight.SemiBold,
                )
                Hinweiskasten(stringResource(R.string.stand_geruest_hinweis))
                Text(
                    text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
                    color = Farbe.gedaempft, fontSize = 12.sp,
                )
            }

            Karte {
                Text(
                    text = stringResource(R.string.einstellungen),
                    color = Farbe.dunkelblau, fontSize = 19.sp, fontWeight = FontWeight.SemiBold,
                )
                if (trennmeldung is Trennergebnis.Rueckstand) {
                    Meldungsblock(
                        titel = pluralStringResource(
                            R.plurals.trennen_rueckstand,
                            trennmeldung.pakete, trennmeldung.pakete,
                        ),
                        hinweis = stringResource(R.string.trennen_rueckstand_hinweis),
                        warnend = true,
                    )
                }

                /* BEENDENDE HANDLUNG: vollflächig rot (E-S4-22a) UND MIT
                 * RÜCKFRAGE. Das eine ohne das andere wäre schlimmer als
                 * keines von beidem: Ein großer roter Knopf zieht den Blick
                 * an, und ein Fehltipp löschte die Kopplung samt
                 * Geräteschlüssel. Die Rückfrage fängt ihn ab — dieselbe
                 * Bauform wie beim Einsatzabschluss (E-S4-21b) und wie an der
                 * Garmin-Uhr (Pair.TrennenDelegate). */
                KnopfBeenden(stringResource(R.string.trennen)) { aufTrennfrage(true) }
            }
        }
    }
}

/**
 * Die Rückfrage vor dem Trennen.
 *
 * `AlertDialog` aus Material 3, weil es der Systembaustein für genau diese
 * Frage ist — er bringt Fokusführung, Zurück-Taste und Vorlesbarkeit mit.
 * Die bestätigende Handlung trägt die beendende Farbe, die abbrechende nicht.
 */
@Composable
private fun Trennfrage(aufJa: () -> Unit, aufNein: () -> Unit) {
    AlertDialog(
        onDismissRequest = aufNein,
        title = { Text(stringResource(R.string.trennen_frage), color = Farbe.dunkelblau) },
        text = { Text(stringResource(R.string.trennen_frage_text), color = Farbe.asphalt) },
        confirmButton = {
            TextButton(onClick = aufJa) {
                Text(stringResource(R.string.trennen_ja), color = Farbe.rotTief,
                    fontWeight = FontWeight.SemiBold)
            }
        },
        dismissButton = {
            TextButton(onClick = aufNein) {
                Text(stringResource(R.string.trennen_nein), color = Farbe.dunkelblau)
            }
        },
        containerColor = Farbe.schnee,
    )
}

@Preview(showBackground = true)
@Composable
private fun VorschauGekoppelt() =
    GekoppeltAnsicht("https://einsatz.beispieldomain.de/", LogoWahl.LUFT, null) {}

@Preview(showBackground = true)
@Composable
private fun VorschauKopplung() = KopplungAnsicht(
    schritt = Kopplungsschritt.Abgewiesen(Abweisung.ZU_VIELE_GERAETE, null),
    serverBasis = null, logoWahl = LogoWahl.BODEN, aufSchritt = {}, aufKoppeln = { _, _ -> },
)
