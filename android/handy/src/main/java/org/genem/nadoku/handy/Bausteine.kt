package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.border
import androidx.compose.foundation.clickable
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxHeight
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.width
import androidx.compose.foundation.layout.heightIn
import androidx.compose.foundation.layout.height
import androidx.compose.foundation.layout.IntrinsicSize
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.statusBars
import androidx.compose.foundation.layout.navigationBars
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.layout.size
import androidx.compose.foundation.shape.CircleShape
import androidx.compose.foundation.shape.RoundedCornerShape
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.draw.clip
import androidx.compose.ui.graphics.Color
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.Dp
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import androidx.compose.material3.Text
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Bildmarke
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Motiv

/**
 * Die Bausteine der Handy-Oberflaeche.
 *
 * WARUM ES SIE GIBT. Dieselbe Ueberlegung wie in `server/ui.php` (Design.md,
 * drei Ebenen): Eine neue Ansicht setzt vorhandene Bausteine zusammen und
 * definiert nichts Eigenes. Ohne diese Ebene entstehen sechs Knopfvarianten,
 * die sich um zwei Pixel unterscheiden -- genau der Zustand, aus dem das
 * Stylesheet der Weboberflaeche in P3 herausgeholt wurde.
 *
 * MASSE. Die Bedienhoehe ist 48 dp -- die Plattformvorgabe von Android fuer
 * Beruehrziele, NICHT die 44 px der Weboberflaeche. Der Unterschied stand als
 * Fund B-S4-02 im Konzept und ist am 02.09.2026 als R58 entschieden worden:
 * Die Web-Zahl gilt fuer den Browser, die Android-Module folgen ihrer
 * Plattform. Ausschlaggebend war der Einsatzfall -- diese App wird MIT
 * HANDSCHUHEN bedient, und genau dafuer sind die 48 dp gedacht. Die Uhr hielt
 * sie ohnehin schon (UHR_BEDIENHOEHE), die beiden Module waren an dieser
 * Stelle also uneins; jetzt nicht mehr.
 */

/** Hoehe jedes Bedienelements. Eine Zahl, keine Kompaktvariante. */
val BEDIENHOEHE: Dp = 48.dp

/** Abstaende -- die Fuenferstufung des Web (`--abstand-1` bis `--abstand-5`). */
object Abstand {
    val eins: Dp = 4.dp
    val zwei: Dp = 8.dp
    val drei: Dp = 12.dp
    val vier: Dp = 16.dp
    val fuenf: Dp = 24.dp
}

/** Radien -- `--radius-klein`, `--radius`, `--radius-gross`. */
object Radius {
    val klein: Dp = 6.dp
    val normal: Dp = 10.dp
    val gross: Dp = 12.dp
}

/**
 * Die Kopfleiste: dunkelblau, weisse Bildmarke, Titel.
 *
 * DIE BILDMARKE BLEIBT AUCH IM LAUFENDEN DIENST SICHTBAR (E-S4-22a) -- sie
 * steht im Kopf JEDER Ansicht, nicht nur auf der Startseite.
 */
@Composable
fun Kopfleiste(titel: String, logoWahl: LogoWahl, modifier: Modifier = Modifier) {
    val motiv = Bildmarke.motiv(logoWahl)
    Row(
        modifier = modifier
            .fillMaxWidth()
            .background(Farbe.dunkelblau)
            /* DIE STATUSLEISTE (Backlog Nr. 86).
             *
             * Seit `targetSdk = 36` zeichnet Android 15+ randlos, und zwar
             * ohne zu fragen: Die App bekommt die volle Flaeche, und die
             * Systemleisten liegen DARUEBER. Bis Android 0.10.1 hat niemand
             * das beruecksichtigt -- Uhrzeit und Akkusymbol standen auf der
             * Kopfleiste, teils auf dem Titel.
             *
             * DIE REIHENFOLGE DER DREI ZEILEN IST DIE LOESUNG, nicht ihre
             * Anwesenheit: `background` VOR `windowInsetsPadding` faerbt die
             * volle Hoehe einschliesslich des Streifens unter der
             * Statusleiste; das Padding danach schiebt nur den INHALT
             * herunter. Andersherum bliebe ein heller Streifen ueber der
             * dunklen Leiste. */
            .windowInsetsPadding(WindowInsets.statusBars)
            .padding(horizontal = Abstand.vier, vertical = Abstand.drei),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(Abstand.drei),
    ) {
        Bildmarke(
            motiv = motiv,
            kachel = 28.dp,
            aufDunkel = true,
            beschreibung = stringResource(
                if (motiv == Motiv.LUFT) R.string.marke_luft_beschreibung
                else R.string.marke_boden_beschreibung
            ),
        )
        Text(
            text = titel,
            color = Farbe.aufDunkel,
            fontSize = 19.sp,
            fontWeight = FontWeight.SemiBold,
        )
    }
}

/**
 * Eine Zustandszeile: farbiger Punkt, dann Text.
 *
 * Die drei Zustaende der Sync-Anzeige (Backlog Nr. 11) und die laufende
 * Aufzeichnung benutzen alle diese Zeile -- der Punkt traegt die Farbe, der
 * Text die Auskunft. Ein Punkt ist ein grafisches Objekt; sein Kontrast wird
 * gegen 3:1 gemessen, nicht gegen 4,5:1 (werkzeuge/kontraste.py).
 */
@Composable
fun Zustandszeile(text: String, punktfarbe: Color, schriftfarbe: Color, modifier: Modifier = Modifier) {
    Row(
        modifier = modifier.fillMaxWidth(),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.spacedBy(Abstand.zwei),
    ) {
        Box(Modifier.size(10.dp).background(punktfarbe, CircleShape))
        Text(text = text, color = schriftfarbe, fontSize = 13.sp)
    }
}

/**
 * Erklaerkasten auf Hellblau (E-S4-22a: Blau erklaert).
 * Bewusst anders als im Web, wo die Wahlliste hell-orange waehlt (E-P3-20).
 */
@Composable
fun Hinweiskasten(text: String, modifier: Modifier = Modifier) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .background(Farbe.blauHell, RoundedCornerShape(Radius.normal))
            .padding(horizontal = Abstand.drei, vertical = Abstand.zwei),
    ) {
        Text(text = text, color = Farbe.asphalt, fontSize = 13.sp)
    }
}

/** Grundform aller Knoepfe -- eine Hoehe, ein Radius, eine Schriftgroesse. */
@Composable
private fun Knopfflaeche(
    beschriftung: String,
    flaeche: Color,
    schrift: Color,
    randfarbe: Color?,
    modifier: Modifier,
    aufTippen: () -> Unit,
) {
    Box(
        modifier = modifier
            .fillMaxWidth()
            .heightIn(min = BEDIENHOEHE)
            .background(flaeche, RoundedCornerShape(Radius.normal))
            .then(
                if (randfarbe != null) {
                    Modifier.border(1.dp, randfarbe, RoundedCornerShape(Radius.normal))
                } else {
                    Modifier
                }
            )
            .clickable(onClick = aufTippen)
            .padding(horizontal = Abstand.vier, vertical = Abstand.drei),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = beschriftung,
            color = schrift,
            fontSize = 15.sp,
            fontWeight = FontWeight.SemiBold,
            textAlign = TextAlign.Center,
        )
    }
}

/** Die eine handelnde Handlung einer Ansicht: Orange, dunkelblaue Schrift. */
@Composable
fun KnopfPrimaer(beschriftung: String, modifier: Modifier = Modifier, aufTippen: () -> Unit) =
    Knopfflaeche(beschriftung, Farbe.knopfPrimaerFlaeche, Farbe.knopfPrimaerSchrift, null, modifier, aufTippen)

/** Alles Uebrige: Schneeflaeche mit Rand. */
@Composable
fun KnopfNeutral(beschriftung: String, modifier: Modifier = Modifier, aufTippen: () -> Unit) =
    Knopfflaeche(beschriftung, Farbe.schnee, Farbe.dunkelblau, Farbe.gedaempft, modifier, aufTippen)

/**
 * Beendende Handlung: VOLLFLAECHIG rot, weisse Schrift (E-S4-22a).
 * Einsatz abschliessen, Dienst beenden, Geraet trennen. Auf einem Geraet im
 * Einsatz muss sie ohne Lesen erkennbar sein; die Rueckfrage faengt den
 * Fehltipp ab (E-S4-21b).
 */
@Composable
fun KnopfBeenden(beschriftung: String, modifier: Modifier = Modifier, aufTippen: () -> Unit) =
    Knopfflaeche(beschriftung, Farbe.knopfBeendenFlaeche, Farbe.knopfBeendenSchrift, null, modifier, aufTippen)

/**
 * Ein Eingabefeld.
 *
 * Der Rand ist `--linie-stark` (= `--gedaempft`, 5,66:1) und nicht `--linie`:
 * An einem Bedienelement ist der Rand die einzige Auskunft darueber, wo es
 * anfaengt und aufhoert, und dafuer verlangt WCAG 1.4.11 3:1. Dieselbe
 * Unterscheidung wie im Web (Fund F-P3-K).
 */
@Composable
fun Eingabefeld(
    wert: String,
    beschriftung: String,
    beispiel: String,
    modifier: Modifier = Modifier,
    grossschreiben: Boolean = false,
    aufAenderung: (String) -> Unit,
) {
    Column(modifier = modifier.fillMaxWidth(), verticalArrangement = Arrangement.spacedBy(Abstand.eins)) {
        Text(text = beschriftung, color = Farbe.gedaempft, fontSize = 13.sp)
        androidx.compose.foundation.text.BasicTextField(
            value = wert,
            onValueChange = aufAenderung,
            singleLine = true,
            textStyle = androidx.compose.ui.text.TextStyle(
                color = Farbe.asphalt,
                fontSize = 15.sp,
                fontFamily = if (grossschreiben) androidx.compose.ui.text.font.FontFamily.Monospace else null,
            ),
            cursorBrush = androidx.compose.ui.graphics.SolidColor(Farbe.orange),
            keyboardOptions = androidx.compose.foundation.text.KeyboardOptions(
                capitalization = if (grossschreiben) {
                    androidx.compose.ui.text.input.KeyboardCapitalization.Characters
                } else {
                    androidx.compose.ui.text.input.KeyboardCapitalization.None
                },
                autoCorrectEnabled = false,
                keyboardType = androidx.compose.ui.text.input.KeyboardType.Uri,
            ),
            modifier = Modifier
                .fillMaxWidth()
                .heightIn(min = BEDIENHOEHE)
                .background(Farbe.schnee, RoundedCornerShape(Radius.klein))
                .border(1.dp, Farbe.gedaempft, RoundedCornerShape(Radius.klein))
                .padding(horizontal = Abstand.drei, vertical = Abstand.drei),
            decorationBox = { innen ->
                if (wert.isEmpty()) {
                    Text(text = beispiel, color = Farbe.gedaempft, fontSize = 15.sp)
                }
                innen()
            },
        )
    }
}

/**
 * Die Wahl aus zwei Moeglichkeiten, nebeneinander (E-S4-20).
 *
 * DIE GEWAEHLTE HAELFTE STEHT AUF HELLBLAU, nicht auf Hell-Orange wie die
 * Wahlliste im Web (E-P3-20). In der App waehlt Blau — damit die Rolle aus
 * Design.md 3.1 sichtbar wird. Der Unterschied ist mit der Freigabe vom
 * 31.08.2026 bestaetigt (E-S4-22a).
 *
 * DIE ZEILE HAT EINE FESTE HOEHE, UND DIE HAELFTEN FUELLEN SIE (Android
 * 0.13.1). Bis 0.13.0 stand an der Zeile nur `heightIn(min = BEDIENHOEHE)`,
 * und die Haelften trugen `fillMaxHeight()`. Das liest sich richtig und war
 * es nicht: Eine `Row` misst ihre Kinder mit Mindesthoehe 0 und der
 * Hoechsthoehe, die sie selbst bekommt — und in einer rollenden Spalte ist
 * die unendlich. Eine unendliche Hoehe kann `fillMaxHeight()` nicht fuellen;
 * es tut dann nichts. Jede Haelfte wurde so hoch wie ihr Text samt Polster
 * (rund 42 dp), die Zeile selbst aber 48 dp, und die Haelften standen oben
 * an. Die Folge, am 05.09.2026 vom S24 gemeldet: ein ungefaerbter Streifen
 * unter der blauen Haelfte, der Text um drei Punkte zu hoch — und der
 * Trennstrich dazwischen mit Hoehe 0, also unsichtbar. Der Bilderlauf hat es
 * nicht bemerkt: Er misst die Bedienhoehe an den FARBIGEN Knoepfen, nicht an
 * dieser Zeile. `ZweierwahlBildTest` misst sie jetzt hier.
 *
 * `height(IntrinsicSize.Min)` gibt der Zeile eine feste Hoehe — die groesste
 * Mindesthoehe ihrer Kinder, durch `heightIn` davor auf mindestens 48 dp
 * gehoben. Gegen eine FESTE Hoehe koennen die Kinder fuellen, und der
 * Trennstrich bekommt sie auch. Die Reihenfolge traegt: `heightIn` VOR
 * `height(IntrinsicSize.Min)`, sonst greift die Untergrenze nicht.
 */
@Composable
fun Zweierwahl(
    links: String,
    rechts: String,
    linksGewaehlt: Boolean,
    modifier: Modifier = Modifier,
    aufWahl: (linksGewaehlt: Boolean) -> Unit,
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .heightIn(min = BEDIENHOEHE)
            .height(IntrinsicSize.Min)
            .border(1.dp, Farbe.gedaempft, RoundedCornerShape(Radius.normal))
            .clip(RoundedCornerShape(Radius.normal)),
    ) {
        Haelfte(links, linksGewaehlt, Modifier.weight(1f)) { aufWahl(true) }
        Box(Modifier.fillMaxHeight().width(1.dp).background(Farbe.gedaempft))
        Haelfte(rechts, !linksGewaehlt, Modifier.weight(1f)) { aufWahl(false) }
    }
}

@Composable
private fun Haelfte(text: String, gewaehlt: Boolean, modifier: Modifier, aufTippen: () -> Unit) {
    Box(
        modifier = modifier
            .fillMaxHeight()
            .background(if (gewaehlt) Farbe.blauHell else Farbe.schnee)
            .clickable(onClick = aufTippen)
            .padding(horizontal = Abstand.zwei, vertical = Abstand.drei),
        contentAlignment = Alignment.Center,
    ) {
        Text(
            text = text,
            color = if (gewaehlt) Farbe.blauTief else Farbe.dunkelblau,
            fontSize = 13.sp,
            fontWeight = if (gewaehlt) FontWeight.SemiBold else FontWeight.Normal,
            textAlign = TextAlign.Center,
        )
    }
}

/** Eine Zeile mit Beschriftung links und Zustand rechts, als Ganzes tippbar. */
@Composable
fun Wahlzeile(
    beschriftung: String,
    modifier: Modifier = Modifier,
    zustand: String? = null,
    gewaehlt: Boolean = false,
    aufTippen: () -> Unit,
) {
    Row(
        modifier = modifier
            .fillMaxWidth()
            .heightIn(min = BEDIENHOEHE)
            .background(
                if (gewaehlt) Farbe.blauHell else Farbe.schnee,
                RoundedCornerShape(Radius.normal),
            )
            .border(1.dp, Farbe.linie, RoundedCornerShape(Radius.normal))
            .clickable(onClick = aufTippen)
            .padding(horizontal = Abstand.drei, vertical = Abstand.zwei),
        verticalAlignment = Alignment.CenterVertically,
        horizontalArrangement = Arrangement.SpaceBetween,
    ) {
        Text(
            text = beschriftung,
            color = if (gewaehlt) Farbe.blauTief else Farbe.dunkelblau,
            fontSize = 15.sp,
            fontWeight = if (gewaehlt) FontWeight.SemiBold else FontWeight.Normal,
        )
        if (zustand != null) {
            Text(text = zustand, color = Farbe.gedaempft, fontSize = 13.sp)
        }
    }
}

/**
 * Der rote Aufnahmepunkt (E-S4-22a).
 *
 * Er steht auf JEDEM laufenden Bildschirm — Handy wie Uhr. Ein grafisches
 * Objekt, gemessen gegen 3:1 und nicht gegen 4,5:1
 * (werkzeuge/kontraste.py).
 */
@Composable
fun Aufnahmepunkt(modifier: Modifier = Modifier) {
    Box(modifier.size(10.dp).background(Farbe.rot, CircleShape))
}

/** Eine Karte: Schneeflaeche, Rand aus `--linie`, Inhalt untereinander. */
@Composable
fun Karte(modifier: Modifier = Modifier, inhalt: @Composable androidx.compose.foundation.layout.ColumnScope.() -> Unit) {
    Column(
        modifier = modifier
            .fillMaxWidth()
            .background(Farbe.schnee, RoundedCornerShape(Radius.gross))
            .border(1.dp, Farbe.linie, RoundedCornerShape(Radius.gross))
            .padding(Abstand.vier),
        verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        content = inhalt,
    )
}
