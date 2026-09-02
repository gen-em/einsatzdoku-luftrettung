package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Bildmarke
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Motiv
import org.genem.nadoku.gemeinsam.Modus

/** Was die Dienstansicht anzeigen soll — alles, was sie braucht, in einem Stück. */
data class Dienststand(
    val laeuft: Boolean,
    val begonnenHhmm: String?,
    val modus: Modus,
    val punkte: Long,
    val streckeKm: String,
    val ortungFreigegeben: Boolean,
    val einsatzLaeuft: Boolean = false,
    val laufendePhase: Int = org.genem.nadoku.gemeinsam.Phasen.FREI,
    val phaseSeit: String? = null,
    val naechstePhase: Int? = null,
    val gesetztePhasen: List<Phasenzeile> = emptyList(),
)

/**
 * Der Bildschirm des laufenden (oder nicht laufenden) Dienstes.
 *
 * **Ein Bildschirm, eine Frage.** Vor dem Dienst gibt es einen Knopf und eine
 * Wahl: mit Phasenknöpfen dokumentieren oder nur aufzeichnen und später im
 * Browser schneiden (E-S4-20). Im Dienst ist die Anzeige das Wesentliche —
 * die Phasenknöpfe kommen mit B5, im Nur-Aufzeichnen-Modus gibt es gar keine.
 *
 * DER ROTE PUNKT UND DIE BILDMARKE STEHEN AUF JEDEM LAUFENDEN BILDSCHIRM
 * (E-S4-22a): der Punkt, weil man einer App im Einsatz ansehen muss, dass sie
 * aufzeichnet; die Marke, weil sie nicht nur auf der Startseite zur Marke
 * gehört.
 */
@Composable
fun DienstAnsicht(
    stand: Dienststand,
    serverBasis: String?,
    logoWahl: LogoWahl,
    rueckstand: Int,
    aufModus: (Modus) -> Unit,
    aufBeginnen: () -> Unit,
    aufBeenden: () -> Unit,
    aufOrtungFreigeben: () -> Unit,
    aufEinstellungen: () -> Unit,
    aufPhase: (Int) -> Unit = {},
    aufEinsatzAbschluss: () -> Unit = {},
) {
    Column(
        modifier = Modifier.fillMaxSize().background(Farbe.rauch),
    ) {
        Kopfleiste(titel = stringResource(R.string.app_name), logoWahl = logoWahl)

        Column(
            modifier = Modifier.verticalScroll(rememberScrollState()).padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Zustandsblock(stand, serverBasis, rueckstand)

                if (!stand.ortungFreigegeben) {
                    Meldungsblock(
                        titel = stringResource(R.string.ortung_fehlt),
                        hinweis = stringResource(R.string.ortung_fehlt_hinweis),
                        warnend = true,
                    )
                    KnopfPrimaer(stringResource(R.string.ortung_freigeben)) { aufOrtungFreigeben() }
                }

                if (stand.laeuft) {
                    LaufenderDienst(stand, logoWahl, aufModus, aufBeenden, aufPhase, aufEinsatzAbschluss)
                } else {
                    RuhenderDienst(stand, logoWahl, aufModus, aufBeginnen)
                }
            }

            KnopfNeutral(stringResource(R.string.einstellungen)) { aufEinstellungen() }
        }
    }
}

@Composable
private fun Zustandsblock(stand: Dienststand, serverBasis: String?, rueckstand: Int) {
    if (stand.laeuft) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Abstand.zwei),
        ) {
            Aufnahmepunkt()
            Text(
                text = stringResource(
                    if (stand.ortungFreigegeben) R.string.dienst_laeuft_seit
                    else R.string.dienst_laeuft_seit_ohne_gps,
                    stand.begonnenHhmm.orEmpty(),
                ),
                color = Farbe.asphalt, fontSize = 13.sp,
            )
        }
    }
    Zustandszeile(
        text = stringResource(
            R.string.sync_gekoppelt_mit,
            serverBasis?.removePrefix("https://")?.trimEnd('/').orEmpty(),
        ),
        punktfarbe = Farbe.blau, schriftfarbe = Farbe.blauTief,
    )
    Zustandszeile(
        text = if (rueckstand > 0) {
            androidx.compose.ui.res.pluralStringResource(R.plurals.sync_rueckstand, rueckstand, rueckstand)
        } else {
            stringResource(R.string.sync_vollstaendig)
        },
        punktfarbe = if (rueckstand > 0) Farbe.orange else Farbe.blau,
        schriftfarbe = Farbe.gedaempft,
    )
}

@Composable
private fun androidx.compose.foundation.layout.ColumnScope.RuhenderDienst(
    stand: Dienststand,
    logoWahl: LogoWahl,
    aufModus: (Modus) -> Unit,
    aufBeginnen: () -> Unit,
) {
    val motiv = Bildmarke.motiv(logoWahl)
    Bildmarke(
        motiv = motiv,
        kachel = 96.dp,
        aufDunkel = false,
        modifier = Modifier.align(Alignment.CenterHorizontally),
        beschreibung = stringResource(
            if (motiv == Motiv.LUFT) R.string.marke_luft_beschreibung
            else R.string.marke_boden_beschreibung
        ),
    )
    Text(
        text = stringResource(R.string.dienst_keiner),
        color = Farbe.dunkelblau, fontSize = 24.sp, fontWeight = FontWeight.SemiBold,
        modifier = Modifier.align(Alignment.CenterHorizontally),
    )
    // Die Vorgabe ist die zuletzt getroffene Wahl (E-S4-20).
    Zweierwahl(
        links = stringResource(R.string.modus_phasen),
        rechts = stringResource(R.string.modus_nur_aufzeichnen),
        linksGewaehlt = stand.modus == Modus.MIT_PHASENKNOEPFEN,
    ) { links ->
        aufModus(if (links) Modus.MIT_PHASENKNOEPFEN else Modus.NUR_AUFZEICHNEN)
    }
    KnopfPrimaer(stringResource(R.string.dienst_beginnen)) { aufBeginnen() }
}

@Composable
private fun androidx.compose.foundation.layout.ColumnScope.LaufenderDienst(
    stand: Dienststand,
    logoWahl: LogoWahl,
    aufModus: (Modus) -> Unit,
    aufBeenden: () -> Unit,
    aufPhase: (Int) -> Unit,
    aufEinsatzAbschluss: () -> Unit,
) {
    Text(
        text = stringResource(
            if (stand.modus == Modus.NUR_AUFZEICHNEN) R.string.modus_nur_aufzeichnen
            else R.string.modus_phasen
        ),
        color = Farbe.dunkelblau, fontSize = 19.sp, fontWeight = FontWeight.SemiBold,
    )
    Text(
        text = androidx.compose.ui.res.pluralStringResource(
            R.plurals.dienst_punkte, stand.punkte.toInt(), stand.punkte, stand.streckeKm,
        ),
        color = Farbe.gedaempft, fontSize = 13.sp,
    )

    if (stand.modus == Modus.NUR_AUFZEICHNEN) {
        // KEIN Phasenknopf — kein versehentlicher Druck mit Handschuhen
        // (E-S4-20).
        Hinweiskasten(stringResource(R.string.modus_nur_aufzeichnen_hinweis))
    } else {
        Phasenteil(
            einsatzLaeuft = stand.einsatzLaeuft,
            laufendePhase = stand.laufendePhase,
            laufendeSeit = stand.phaseSeit,
            naechstePhase = stand.naechstePhase,
            gesetzte = stand.gesetztePhasen,
            aufPhase = aufPhase,
            aufAbschluss = aufEinsatzAbschluss,
        )
    }

    /* DER WECHSEL WÄHREND DES DIENSTES IST VERLUSTFREI (E-S4-20). Er blendet
     * die Knöpfe ein oder aus, bereits Gesendetes bleibt unberührt — deshalb
     * ein neutraler Knopf und keine Rückfrage: Es geht nichts verloren. */
    KnopfNeutral(
        stringResource(
            if (stand.modus == Modus.NUR_AUFZEICHNEN) R.string.modus_doch_phasen
            else R.string.modus_doch_nur_aufzeichnen
        )
    ) {
        aufModus(
            if (stand.modus == Modus.NUR_AUFZEICHNEN) Modus.MIT_PHASENKNOEPFEN
            else Modus.NUR_AUFZEICHNEN
        )
    }

    // Beendende Handlung: vollflächig rot, mit Rückfrage (E-S4-22a).
    KnopfBeenden(stringResource(R.string.dienst_beenden)) { aufBeenden() }
}

@Preview(showBackground = true)
@Composable
private fun VorschauRuhend() = DienstAnsicht(
    stand = Dienststand(false, null, Modus.MIT_PHASENKNOEPFEN, 0, "0,0", true),
    serverBasis = "https://einsatz.beispieldomain.de/", logoWahl = LogoWahl.LUFT,
    rueckstand = 0, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufEinstellungen = {},
)

@Preview(showBackground = true)
@Composable
private fun VorschauLaufendNurAufzeichnen() = DienstAnsicht(
    stand = Dienststand(true, "07:02", Modus.NUR_AUFZEICHNEN, 1483, "126,4", true),
    serverBasis = "https://einsatz.beispieldomain.de/", logoWahl = LogoWahl.BODEN,
    rueckstand = 2, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufEinstellungen = {},
)
