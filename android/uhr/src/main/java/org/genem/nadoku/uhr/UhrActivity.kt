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
import androidx.compose.runtime.DisposableEffect
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
import org.genem.nadoku.gemeinsam.Ortungscode
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
 * **STAND C2:** Bedienbild und Nachrichtenweg stehen. Die Ansicht entscheidet
 * nichts mehr selbst — sie reicht Ereignisse an [UhrApp] weiter und zeichnet,
 * was von dort zurückkommt. Der Zustand liegt in [org.genem.nadoku.uhr.funk.Uhrsteuerung],
 * nicht hier: Eine Quittung kann eintreffen, während gar keine Ansicht läuft.
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
        val app = UhrApp.von(this)
        setContent {
            UhrOberflaeche(
                app = app,
                freieTasteVorhanden = freieTaste != null,
                tasteAnmelden = { aufFreieTaste = it },
            )
        }
    }

    /**
     * Beim Öffnen wird nachgeliefert.
     *
     * Wer die App aufmacht, hat meistens einen Grund — ein voller Puffer ist
     * einer davon. Ein eigener Wecker dafür wäre ein Dienst auf der Uhr, also
     * Akku für einen Fall, den die Nähe zum Handy von selbst auflöst.
     */
    override fun onResume() {
        super.onResume()
        UhrApp.von(this).nachliefern()
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
    app: UhrApp? = null,
    logoWahl: LogoWahl = LogoWahl.WECHSELND,
    freieTasteVorhanden: Boolean = false,
    tasteAnmelden: (() -> Unit) -> Unit = {},
    sperreAn: Boolean = true,
    /**
     * Startzustand **ohne** [app] — für Vorschau und Bildprüfstand.
     *
     * Er existiert, weil sich sonst nur die Startseite abbilden ließe: Ohne
     * Handy kommt die Ansicht nie in den laufenden Dienst, und gerade dort
     * steht der längste Text. Mit [app] wird er ignoriert; dann führt die
     * Steuerung (E-S4-48).
     */
    anfang: Uhrzustand = Uhrzustand(),
) {
    /* OHNE [app] BLEIBT DIE ANSICHT FÜR SICH — das ist der Fall der
     * @Preview-Vorschau und nur der. In der App liegt der Zustand in der
     * Steuerung, weil eine Quittung eintreffen kann, während keine Ansicht
     * läuft (E-S4-10). */
    val bedienung = remember(sperreAn) { Uhrbedienung(sperreAn = sperreAn) }
    var zustand by remember { mutableStateOf(app?.zustand ?: anfang) }

    DisposableEffect(app) {
        app?.beobachter = { z -> zustand = z }
        onDispose { app?.beobachter = null }
    }

    fun melde(ereignis: Uhrereignis) {
        if (app != null) app.ereignis(ereignis)
        else zustand = bedienung.verarbeite(zustand, ereignis).zustand
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
        // Die Startseite trägt die Marke GRÖSSER als die laufenden Ansichten
        // (E-S4-22a) — seit 0.7.3 mit 22 % statt 27 %, siehe MARKE_START.
        Marke(logoWahl, MARKE_START)
        Text(
            text = stringResource(R.string.app_name),
            color = Farbe.aufDunkel, fontSize = 19.sp, textAlign = TextAlign.Center,
        )

        /* DER KNOPF STEHT ÜBER DER VERBINDUNGSZEILE, und das ist Geometrie,
         * keine Gewichtung: Der Kreis ist in der Mitte am breitesten. Ein
         * Bedienelement gehört dorthin, eine Statusanzeige nicht — sie wird
         * gelesen, nicht getroffen. Vorher stand der Knopf ganz unten, wo der
         * Kreis auf 55 dp zusammenläuft, und wurde an beiden Seiten gekappt
         * (Fund B-S4-08b, gemessen: 13,55 % des Inhalts außerhalb des Glases). */
        UhrKnopf(stringResource(R.string.dienst_beginnen)) {
            melde(Uhrereignis.Dienstknopf(System.currentTimeMillis()))
        }
        Verbindungszeile(z)

        /* Die Fassung steht zuletzt — beim Gerätetest ist sie die erste Frage,
         * im Betrieb interessiert sie niemanden. Auf der kleinen Uhr ist sie
         * damit nur noch über den Bildlauf erreichbar; das ist die richtige
         * Reihenfolge, wenn der Platz nicht für alles reicht.
         *
         * „löst am Handy aus" ist ersatzlos gestrichen: Die Verbindungszeile
         * sagt bereits, dass ein Handy im Spiel ist, und der Satz lag auf der
         * 192-dp-Uhr ohnehin außerhalb des Glases — er war nie zu sehen. */
        Text(
            text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
            color = Farbe.sand, fontSize = 11.sp, textAlign = TextAlign.Center,
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
            Verbindungszeile(z)
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

        /* DIE VERBINDUNGSZEILE STEHT ZULETZT — dieselbe Regel wie auf der
         * Startseite (E-S4-51): Bedienelemente in die Mitte, wo der Kreis
         * breit ist, Statusanzeigen an den Rand. Sie stand vorher zwischen
         * Zustandszeile und Knopf und schob beide Knöpfe nach unten; gemessen
         * blieben davon 1,66 % des Inhalts außerhalb des Glases. */
        Verbindungszeile(z)
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
        Verbindungszeile(z)
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
}

/**
 * Was der Funk gerade tut — und **was er noch nicht getan hat**.
 *
 * ROSA UND NICHT ROT, und das ist eine Berichtigung (B-S5Z-15). `marke_rot`
 * als **Schrift** auf `marke_asphalt` erreicht 4,12 : 1 und bleibt damit unter
 * AA; als Fläche mit weisser Schrift (die beendenden Knöpfe) trägt dasselbe
 * Rot 4,78 : 1 und ist richtig. `marke_rosa` ist der helle Vertreter
 * derselben Familie und erreicht 15,94 : 1. Der Fehler stand seit C1 da und
 * fiel nicht auf, weil `werkzeuge/kontraste.py` eine feste Paarliste führt
 * und dieses Paar nicht enthielt — dieselbe Lücke wie bei B-S5Z-13. Beide
 * Paare stehen jetzt darin.
 *
 * DIE SCHWEBENDE ZEILE IST DER KERN VON E-S4-10 an der Oberfläche: Ein an der
 * Uhr ausgelöster Dienststart wirkt erst mit der Zustellung; vorher läuft am
 * Handy kein GPS. Eine Uhr, die in diesem Augenblick nur „Dienst läuft"
 * zeigte, verschwiege genau die Aufzeichnungslücke, die hinterher niemand
 * mehr erklären kann. Deshalb steht sie **über** allem anderen, in Rot.
 */
@Composable
private fun Verbindungszeile(z: Uhrzustand) {
    if (z.dienstSchwebt) {
        Text(
            text = stringResource(R.string.dienst_schwebt),
            color = Farbe.rosa, fontSize = 12.sp, textAlign = TextAlign.Center,
        )
        return
    }
    /* DIE ORTUNG DES HANDYS HAT VORFAHRT VOR DEM FUNKSTAND (E-S5Z-15).
     *
     * Beide sagen dasselbe Wesentliche — „gerade entsteht keine Spur" —, aber
     * die fehlende Ortung ist der Fall, den nur diese Zeile verraten kann: Der
     * Funk steht, das Handy hat quittiert, der Dienst läuft, und aufgezeichnet
     * wird trotzdem nichts. Ohne sie stünde hier „verbunden", und das wäre
     * wahr und irreführend zugleich.
     *
     * EIN WORTLAUT FÜR VIER URSACHEN: Die Uhr kann keine davon beheben. Das
     * tut das Handy, und das vibriert (E-S5Z-04).
     *
     * SIE STEHT HIER UNTEN UND NICHT ÜBER DEN KNÖPFEN (E-S5Z-25, E-S4-51):
     * Bedienelemente in die Mitte, wo der Kreis breit ist, Statusanzeigen an
     * den Rand. Der Entwurf sah sie vor den Knöpfen vor; dort schob schon die
     * Verbindungszeile beide Knöpfe so weit nach unten, dass 1,66 % des
     * Inhalts aus dem Glas liefen — gemessen, nicht vermutet. */
    if (z.ortung in Ortungscode.OHNE_AUFZEICHNUNG) {
        Text(
            text = stringResource(R.string.ortung_keine),
            color = Farbe.rosa, fontSize = 12.sp, textAlign = TextAlign.Center,
        )
        return
    }
    if (z.ortung == Ortungscode.SUCHT) {
        Text(
            text = stringResource(R.string.ortung_sucht),
            color = Farbe.sand, fontSize = 12.sp, textAlign = TextAlign.Center,
        )
        return
    }
    Text(
        text = when {
            /* NOCH NICHTS VERSUCHT ist ein eigener Fall und nicht „verbunden".
             * Beim Start der App weiß die Uhr über das Handy genau nichts —
             * das zu sagen ist die einzige zutreffende Auskunft (B-S4-09). */
            z.handyErreichbar == null -> stringResource(R.string.handy_unbekannt)
            z.handyErreichbar == false -> stringResource(R.string.handy_nicht_erreichbar)
            z.gepuffert > 0 -> androidx.compose.ui.res.pluralStringResource(
                R.plurals.gepuffert, z.gepuffert, z.gepuffert,
            )
            else -> stringResource(R.string.handy_verbunden)
        },
        // Rot warnt, Blau bestätigt, Sand sagt nichts zu (E-S4-22a).
        color = when (z.handyErreichbar) {
            null -> Farbe.sand
            true -> Farbe.blau
            false -> Farbe.rot
        },
        fontSize = 12.sp,
        textAlign = TextAlign.Center,
    )
}

/**
 * Die Grundspalte aller Uhr-Ansichten — **mit Bildlauf**.
 *
 * DER BILDLAUF IST DIE BEHEBUNG EINES FEHLERS, keine Bequemlichkeit
 * (Fund B-S4-08). Ohne ihn bekommt die Spalte die Displayhöhe als feste
 * Obergrenze und **staucht ihre Kinder**, wenn der Inhalt nicht passt. Das
 * traf ausgerechnet den großen Knopf: `heightIn(min = 48.dp)` in
 * [UhrKnopf] beugt sich einer kleineren Elternbeschränkung, und auf der
 * 192-dp-Uhr blieben davon **35,5 dp** übrig — 26 % unter der Zusage aus
 * E-S4-41, und zwar genau auf den kleinen Uhren, für die die 48 dp gedacht
 * sind. Auf der 227-dp-Uhr stimmte es, weil dort der Platz reichte.
 *
 * Mit Bildlauf misst die Spalte ihre Kinder **unbeschränkt**: Der Knopf
 * bekommt seine 48 dp, und was nicht auf einmal aufs Glas passt, ist durch
 * Wischen erreichbar statt still abgeschnitten. Auf Wear OS ist der Bildlauf
 * die übliche Bedienung; [Phasenliste] benutzt ihn seit C1.
 *
 * Gefunden hat das kein Auge, sondern die Messung im Bild
 * (`UhrBildTest.pruefeBedienhoehe`) — das erste Ergebnis des neuen
 * Bildmittels (E-S4-49).
 */
@Composable
private fun Spalte(inhalt: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .verticalScroll(rememberScrollState())
            .padding(horizontal = 18.dp, vertical = 16.dp),
        horizontalAlignment = Alignment.CenterHorizontally,
        verticalArrangement = Arrangement.spacedBy(6.dp, Alignment.CenterVertically),
        content = inhalt,
    )
}

/**
 * Die Startseite trug die Marke nach der 27-%-Stufung der Garmin; seit 0.7.3
 * sind es **22 %** — die 27 % stammen von einem Gerät mit anderen
 * Proportionen und kosteten auf der 192-dp-Uhr genau die Höhe, die der große
 * Knopf braucht, um im Glas zu bleiben. Die alte Herleitung:
 * (`tools/uhr-bilder`: 70/260 des Bezugsgeräts), die laufenden Ansichten
 * **ein Sechstel** der Displayhöhe (E-S4-22a).
 *
 * Beides ist **blind gewählt** und am Gerät nachzumessen (E-R45-7); es gehört
 * danach in den Wear-Teil von `docs/Geraete-Eingabe.md`.
 */
const val MARKE_START = 0.22f
const val MARKE_LAUFEND = 1f / 6f

@Preview(device = "id:wearos_small_round", showBackground = true)
@Composable
private fun VorschauStart() = UhrOberflaeche(logoWahl = LogoWahl.BODEN)
