package org.genem.nadoku.uhr

import android.os.Bundle
import android.view.KeyEvent
import androidx.activity.ComponentActivity
import androidx.activity.compose.setContent
import androidx.compose.foundation.background
import androidx.compose.foundation.gestures.detectTapGestures
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.input.pointer.pointerInput
import androidx.compose.ui.platform.LocalConfiguration
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.wear.compose.material.MaterialTheme
import androidx.wear.compose.material.Text
import kotlinx.coroutines.delay
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Bildmarke
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Motiv
import org.genem.nadoku.gemeinsam.Phasen

/**
 * Der Einstieg der Uhr-App.
 *
 * SIE IST FERNBEDIENUNG, SONST NICHTS (E-S4-11): kein GPS, keine Kopplung,
 * keine Zugangsdaten, keine Reanimation. Was sie tut, ist Zeitstempel setzen
 * und ans Handy melden — das Handy quittiert (E-S4-10, C2).
 *
 * **STAND C1:** Das Bedienbild steht, der Nachrichtenweg noch nicht. Die
 * Wirkungen aus [Uhrbedienung] laufen deshalb noch ins Leere; C2 hängt den
 * Data Layer daran.
 *
 * **BLIND GEBAUT.** Es gibt keinen Emulator (E-R45-8) und keine Uhr
 * (E-R45-7). Von dieser Ansicht existiert **kein Bildschirmfoto**. Geprüft
 * ist, was dahinterliegt: [Uhrbedienung], vollständig und ohne Oberfläche.
 */
class UhrActivity : ComponentActivity() {

    private var freieTaste: Int? = null

    /** Was der Bildschirm meldet — in C2 kommt das Handy dazu. */
    private var aufFreieTaste: (() -> Unit)? = null

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        freieTaste = Tastenabfrage.freieTaste(this)
        setContent {
            UhrOberflaeche(
                freieTasteVorhanden = freieTaste != null,
                tasteAnmelden = { aufFreieTaste = it },
            )
        }
    }

    /**
     * Die freie Zusatztaste löst „nächste Phase" aus (E-S4-21a).
     *
     * Nur die Kennung, die [Tastenabfrage] gemeldet hat — alles andere geht
     * ans System zurück. Wer Home oder Zurück belegte, nähme der NutzerIn den
     * Weg aus der App heraus.
     */
    override fun onKeyDown(keyCode: Int, event: KeyEvent?): Boolean {
        if (keyCode == freieTaste) {
            aufFreieTaste?.invoke()
            return true
        }
        return super.onKeyDown(keyCode, event)
    }
}

@Composable
fun UhrOberflaeche(
    logoWahl: LogoWahl = LogoWahl.WECHSELND,
    freieTasteVorhanden: Boolean = false,
    tasteAnmelden: (() -> Unit) -> Unit = {},
    sperreAn: Boolean = true,
) {
    val bedienung = remember(sperreAn) { Uhrbedienung(sperreAn = sperreAn) }
    var zustand by remember { mutableStateOf(Uhrzustand()) }

    fun melde(ereignis: Uhrereignis) {
        // Die Wirkungen gehen in C2 an das Handy; bis dahin bleibt es beim
        // Zustandswechsel — die Uhr tut selbst nichts (E-S4-11).
        zustand = bedienung.verarbeite(zustand, ereignis).zustand
    }

    // Die freie Taste tut dasselbe wie der große Knopf (E-S4-21a).
    LaunchedEffect(freieTasteVorhanden) {
        tasteAnmelden { melde(Uhrereignis.FreieTaste(System.currentTimeMillis())) }
    }

    /* DER ZEITSCHLAG DER SPERRE. Eine Sekunde ist reichlich genau für eine
     * Frist von zehn — und die Schleife läuft mit der Ansicht an und mit ihr
     * aus, kostet also nichts, wenn niemand hinsieht. */
    LaunchedEffect(Unit) {
        while (true) {
            delay(1000)
            melde(Uhrereignis.Zeitschlag(System.currentTimeMillis()))
        }
    }

    MaterialTheme {
        Box(
            modifier = Modifier
                .fillMaxSize()
                .background(Farbe.asphalt)
                .pointerInput(zustand.gesperrt) {
                    detectTapGestures(
                        onLongPress = {
                            melde(
                                Uhrereignis.Halten(
                                    dauerMs = Uhrbedienung.HALTEDAUER_MS,
                                    jetztMs = System.currentTimeMillis(),
                                )
                            )
                        },
                    )
                },
            contentAlignment = Alignment.Center,
        ) {
            if (zustand.gesperrt) {
                GesperrteAnsicht(zustand, logoWahl)
            } else {
                when (zustand.ansicht) {
                    Ansicht.START -> Startseite(zustand, logoWahl) { melde(it) }
                    Ansicht.LAUFEND -> LaufendeAnsicht(zustand, logoWahl) { melde(it) }
                    Ansicht.PHASENLISTE -> Phasenliste(zustand) { melde(it) }
                    Ansicht.ABSCHLUSSFRAGE -> Rueckfrage(
                        stringResource(R.string.einsatz_abschliessen_frage),
                        stringResource(R.string.abschliessen),
                    ) { melde(it) }
                    Ansicht.DIENSTENDEFRAGE -> Rueckfrage(
                        stringResource(R.string.dienst_beenden_frage),
                        stringResource(R.string.beenden),
                    ) { melde(it) }
                }
            }
        }
    }
}

/** Kachelkante der Bildmarke — Anteil der Displayhöhe (E-S4-22a). */
@Composable
private fun markenKachel(anteil: Float) =
    (LocalConfiguration.current.screenHeightDp * anteil).dp

@Composable
private fun Marke(logoWahl: LogoWahl, anteil: Float) {
    val motiv = Bildmarke.motiv(logoWahl)
    Bildmarke(
        motiv = motiv,
        kachel = markenKachel(anteil),
        aufDunkel = true,          // Dunkelgrund-Fassung (Design.md 2.3)
        beschreibung = stringResource(
            if (motiv == Motiv.LUFT) R.string.marke_luft_beschreibung
            else R.string.marke_boden_beschreibung
        ),
    )
}

@Composable
private fun Startseite(z: Uhrzustand, logoWahl: LogoWahl, melde: (Uhrereignis) -> Unit) {
    Spalte {
        // Die Startseite trägt die Marke GRÖSSER — die 27-%-Stufung der
        // Garmin (E-S4-22a).
        Marke(logoWahl, MARKE_START)
        Text(
            text = stringResource(R.string.app_name),
            color = Farbe.aufDunkel, fontSize = 19.sp, textAlign = TextAlign.Center,
        )
        // Die Fassung steht auf der Startseite — beim Gerätetest ist die erste
        // Frage immer, welche gerade draufliegt.
        Text(
            text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
            color = Farbe.sand, fontSize = 11.sp, textAlign = TextAlign.Center,
        )
        Verbindungszeile(z)
        UhrKnopf(stringResource(R.string.dienst_beginnen)) {
            melde(Uhrereignis.Dienstknopf(System.currentTimeMillis()))
        }
        Text(
            text = stringResource(R.string.loest_am_handy_aus),
            color = Farbe.sand, fontSize = 12.sp, textAlign = TextAlign.Center,
        )
    }
}

@Composable
private fun LaufendeAnsicht(z: Uhrzustand, logoWahl: LogoWahl, melde: (Uhrereignis) -> Unit) {
    Spalte {
        // Auf laufenden Ansichten ein Sechstel der Displayhöhe (E-S4-22a).
        Marke(logoWahl, MARKE_LAUFEND)
        Zustandszeile(z)

        if (!z.mitPhasen) {
            // Nur-Aufzeichnen: nur Dienst beginnen/beenden (E-S4-20).
            Text(
                text = stringResource(R.string.nur_aufzeichnen),
                color = Farbe.sand, fontSize = 13.sp, textAlign = TextAlign.Center,
            )
            UhrKnopf(
                stringResource(R.string.dienst_beenden),
                flaeche = Farbe.rot, schrift = Farbe.aufDunkel,
            ) { melde(Uhrereignis.Dienstknopf(System.currentTimeMillis())) }
            return@Spalte
        }

        val naechste = z.naechstePhase
        if (naechste != null) {
            UhrKnopf(
                stringResource(R.string.phase_knopf, naechste, Phasen.kurz(naechste))
            ) { melde(Uhrereignis.GrosserKnopf(System.currentTimeMillis())) }
            Text(
                text = stringResource(R.string.halten_phasenliste),
                color = Farbe.sand, fontSize = 12.sp, textAlign = TextAlign.Center,
            )
        } else {
            // Nach Phase 9 wird derselbe Knopf zum Abschluss (E-S4-21b).
            UhrKnopf(
                stringResource(R.string.einsatz_abschliessen),
                flaeche = Farbe.rot, schrift = Farbe.aufDunkel,
            ) { melde(Uhrereignis.GrosserKnopf(System.currentTimeMillis())) }
        }

        UhrKnopfLeise(stringResource(R.string.dienst_beenden)) {
            melde(Uhrereignis.Dienstknopf(System.currentTimeMillis()))
        }
    }
}

@Composable
private fun Phasenliste(z: Uhrzustand, melde: (Uhrereignis) -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 14.dp, vertical = 24.dp),
        verticalArrangement = Arrangement.spacedBy(2.dp),
    ) {
        for (nummer in Phasen.UEBERTRAGEN) {
            Phasenreihe(
                nummer = nummer,
                beschriftung = Phasen.kurz(nummer),
                zeiten = z.phasen.filter { it.nummer == nummer }.map { it.hhmm },
                naechste = nummer == z.naechstePhase,
            ) { melde(Uhrereignis.ListenwahL(nummer, System.currentTimeMillis())) }
        }
        // Der vorzeitige Abschluss steht in der Liste (E-S4-21c).
        UhrKnopf(
            stringResource(R.string.einsatz_abschliessen),
            flaeche = Farbe.rot, schrift = Farbe.aufDunkel,
        ) { melde(Uhrereignis.Abschluss(System.currentTimeMillis())) }
        UhrKnopfLeise(stringResource(R.string.zurueck)) {
            melde(Uhrereignis.Verworfen(System.currentTimeMillis()))
        }
    }
}

@Composable
private fun Rueckfrage(frage: String, ja: String, melde: (Uhrereignis) -> Unit) {
    Spalte {
        Text(text = frage, color = Farbe.aufDunkel, fontSize = 16.sp, textAlign = TextAlign.Center)
        // Die beendende Handlung ist vollflächig rot (E-S4-22a).
        UhrKnopf(ja, flaeche = Farbe.rot, schrift = Farbe.aufDunkel) {
            melde(Uhrereignis.Bestaetigt(System.currentTimeMillis()))
        }
        UhrKnopfLeise(stringResource(R.string.zurueck)) {
            melde(Uhrereignis.Verworfen(System.currentTimeMillis()))
        }
    }
}

/**
 * Gesperrt (E-S4-21d): **Phase und Zeit bleiben lesbar**, unten steht der
 * Hinweis zum Entsperren. Ein Tippen tut nichts; entsperrt wird durch Halten.
 */
@Composable
private fun GesperrteAnsicht(z: Uhrzustand, logoWahl: LogoWahl) {
    Spalte {
        androidx.compose.foundation.Image(
            painter = androidx.compose.ui.res.painterResource(R.drawable.symbol_schloss),
            contentDescription = stringResource(R.string.gesperrt_beschreibung),
            modifier = Modifier.size(22.dp),
        )
        Marke(logoWahl, MARKE_LAUFEND)
        Zustandszeile(z)
        Text(
            text = stringResource(R.string.gesperrt),
            color = Farbe.sand, fontSize = 12.sp, textAlign = TextAlign.Center,
        )
    }
}

@Composable
private fun Zustandszeile(z: Uhrzustand) {
    Box(contentAlignment = Alignment.Center) {
        Text(
            text = if (z.einsatzLaeuft) {
                stringResource(R.string.phase_seit, z.laufendePhase, z.laufendeSeit.orEmpty())
            } else {
                stringResource(R.string.dienst_beginnen)
            },
            color = Farbe.sand, fontSize = 13.sp, textAlign = TextAlign.Center,
        )
    }
    Verbindungszeile(z)
}

@Composable
private fun Verbindungszeile(z: Uhrzustand) {
    Text(
        text = when {
            !z.handyErreichbar -> stringResource(R.string.handy_nicht_erreichbar)
            z.gepuffert > 0 -> androidx.compose.ui.res.pluralStringResource(
                R.plurals.gepuffert, z.gepuffert, z.gepuffert,
            )
            else -> stringResource(R.string.handy_verbunden)
        },
        // Rot warnt, Blau bestätigt (E-S4-22a).
        color = if (z.handyErreichbar) Farbe.blau else Farbe.rot,
        fontSize = 12.sp,
        textAlign = TextAlign.Center,
    )
}

@Composable
private fun Spalte(inhalt: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxWidth()
            .padding(horizontal = 18.dp, vertical = 16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(6.dp, Alignment.CenterVertically),
        content = inhalt,
    )
}

/**
 * Die Startseite trägt die Marke nach der **27-%-Stufung der Garmin**
 * (`tools/uhr-bilder`: 70/260 des Bezugsgeräts), die laufenden Ansichten
 * **ein Sechstel** der Displayhöhe (E-S4-22a).
 *
 * Beides ist **blind gewählt** und am Gerät nachzumessen (E-R45-7); es gehört
 * danach in den Wear-Teil von `docs/Geraete-Eingabe.md`.
 */
const val MARKE_START = 0.27f
const val MARKE_LAUFEND = 1f / 6f

@Preview(device = "id:wearos_small_round", showBackground = true)
@Composable
private fun VorschauStart() = UhrOberflaeche(logoWahl = LogoWahl.BODEN)
