package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.Row
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.navigationBars
import androidx.compose.foundation.layout.windowInsetsPadding
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
import org.genem.nadoku.handy.kopplung.Serveradresse
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Motiv
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.handy.aufzeichnung.Ortungsstand
import org.genem.nadoku.handy.puffer.Raeumung

/**
 * Wie der letzte Sendelauf ausgegangen ist.
 *
 * Die Ansicht bekommt das **fertig entschieden** und nicht als
 * `Sendebericht`: Sie soll anzeigen, nicht auswerten. Sonst stünde die Regel,
 * was „Keine Verbindung" heißt, in einer Compose-Funktion und wäre dort
 * weder prüfbar noch wiederfindbar.
 */
enum class Sendeausgang {
    /** Alles durch. */
    GESENDET,

    /** Kein Netz oder 5xx — der Nachsende-Job holt es nach. */
    KEIN_NETZ,

    /** 401. Wiederholen hilft nicht; es hilft eine neue Kopplung. */
    SCHLUESSEL_ABGEWIESEN,

    /** 400. Der Server hat den Inhalt abgelehnt — er wird nicht wiederholt. */
    PAKET_ABGEWIESEN,

    /**
     * Kein Kontoschlüssel, auch nach dem Nachholen nicht: Das Konto
     * hat noch keines. Nichts gesendet, nichts verloren; nach einer Anmeldung
     * im Web holt der nächste Lauf ihn von selbst.
     */
    KEIN_SCHLUESSEL,
}

/**
 * @param hhmm wann der Lauf war
 * @param anzahl nur bei [Sendeausgang.PAKET_ABGEWIESEN]: wie viele
 */
data class Sendeergebnis(val ausgang: Sendeausgang, val hhmm: String, val anzahl: Int = 0)

/** Was die Dienstansicht anzeigen soll — alles, was sie braucht, in einem Stück. */
data class Dienststand(
    val laeuft: Boolean,
    val begonnenSeit: String?,
    val modus: Modus,
    val punkte: Long,
    val streckeKm: String,
    val ortungFreigegeben: Boolean,
    /**
     * Ist der GPS-Anbieter eingeschaltet? Vor dem Dienst entscheidet er
     * zusammen mit [ortungFreigegeben] darüber, ob überhaupt begonnen werden
     * kann.
     */
    val standortAn: Boolean = true,
    /**
     * Der Ortungszustand des **laufenden** Dienstes; `null`, solange keiner
     * läuft oder der Wächter noch nichts gemessen hat.
     */
    val ortung: Ortungsstand? = null,
    /**
     * Wie lange [ortung] schon gilt, in vollen Minuten — für „seit 3 min".
     *
     * Die Ansicht rechnet das nicht selbst aus: Sie bekäme dafür eine
     * monotone Uhr in die Hand, und eine Vorschau hätte keine.
     */
    val ortungSeitMin: Int = 0,
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
 * Browser schneiden. Im Dienst ist die Anzeige das Wesentliche — dazu die
 * Phasenknöpfe; im Nur-Aufzeichnen-Modus gibt es gar keine.
 *
 * DER ROTE PUNKT UND DIE BILDMARKE STEHEN AUF JEDEM LAUFENDEN BILDSCHIRM:
 * der Punkt, weil man einer App im Einsatz ansehen muss, dass sie
 * aufzeichnet; die Marke, weil sie nicht nur auf der Startseite zur Marke
 * gehört.
 */
@Composable
fun DienstAnsicht(
    stand: Dienststand,
    logoWahl: LogoWahl,
    rueckstand: Int,
    abgewiesen: Int = 0,
    verwerfbar: Int = 0,
    verworfen: Int? = null,
    sendeergebnis: Sendeergebnis? = null,
    sendelaufLaeuft: Boolean = false,
    aufJetztSenden: () -> Unit = {},
    aufVerwerfen: () -> Unit = {},
    aufModus: (Modus) -> Unit,
    aufBeginnen: () -> Unit,
    aufBeenden: () -> Unit,
    aufOrtungFreigeben: () -> Unit,
    aufStandortEinschalten: () -> Unit,
    aufEinstellungen: () -> Unit,
    aufPhase: (Int) -> Unit = {},
    aufEinsatzAbschluss: () -> Unit = {},
) {
    Column(
        modifier = Modifier
            .fillMaxSize()
            .background(Farbe.rauch)
            /* Die Navigationsleiste unten -- und im Querformat die seitliche
             * Geste. Wie oben gilt: `background` zuerst, damit
             * die Flaeche bis zum Rand faerbt, das Padding danach, damit der
             * Inhalt nicht darunter geraet. */
            .windowInsetsPadding(WindowInsets.navigationBars),
    ) {
        Kopfleiste(titel = stringResource(R.string.app_name), logoWahl = logoWahl)

        Column(
            modifier = Modifier.verticalScroll(rememberScrollState()).padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Zustandsblock(
                    stand, rueckstand, abgewiesen, verwerfbar, verworfen,
                    sendeergebnis, sendelaufLaeuft, aufJetztSenden, aufVerwerfen,
                )

                /* ZWEI SPERREN, IN DIESER REIHENFOLGE. Erst die
                 * Freigabe, dann der Standort: Ohne Freigabe nützt der
                 * eingeschaltete Standort nichts, und zwei Meldungsblöcke
                 * übereinander wären eine Zumutung mit Handschuhen.
                 *
                 * Solange einer der beiden steht, gibt es KEINEN Knopf
                 * „Dienst beginnen" — ein Dienst, der nichts aufzeichnet,
                 * ist kein Dienst, sondern eine Lücke mit Uhrzeit. */
                if (!stand.ortungFreigegeben) {
                    Meldungsblock(
                        titel = stringResource(R.string.ortung_fehlt),
                        hinweis = stringResource(R.string.ortung_fehlt_hinweis),
                        warnend = true,
                    )
                    KnopfPrimaer(stringResource(R.string.ortung_freigeben)) { aufOrtungFreigeben() }
                } else if (!stand.standortAn) {
                    Meldungsblock(
                        titel = stringResource(R.string.standort_aus),
                        hinweis = stringResource(R.string.standort_aus_hinweis),
                        warnend = true,
                    )
                    KnopfPrimaer(stringResource(R.string.standort_einschalten)) {
                        aufStandortEinschalten()
                    }
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
private fun Zustandsblock(
    stand: Dienststand,
    rueckstand: Int,
    abgewiesen: Int,
    verwerfbar: Int,
    verworfen: Int?,
    sendeergebnis: Sendeergebnis?,
    sendelaufLaeuft: Boolean,
    aufJetztSenden: () -> Unit,
    aufVerwerfen: () -> Unit,
) {
    if (stand.laeuft) {
        Row(
            verticalAlignment = Alignment.CenterVertically,
            horizontalArrangement = Arrangement.spacedBy(Abstand.zwei),
        ) {
            /* DER AUFNAHMEPUNKT BLEIBT IN JEDEM ZUSTAND: Er
             * zeigt den DIENST, nicht das Signal. Die Zeile daneben sagt,
             * was der Punkt nicht sagen kann. */
            Aufnahmepunkt()
            Text(
                text = ortungstext(stand),
                color = ortungsfarbe(stand.ortung), fontSize = 13.sp,
            )
        }
    }
    /* DIE ADRESSE STEHT DA, OBWOHL SIE FEST IST. Sie ist keine
     * Einstellung, sondern eine Auskunft: Wohin sendet dieses Gerät? Bei
     * einem selbstgebauten APK (Selbsthoster, Pruefstand) ist das die einzige
     * Stelle, an der es sichtbar wird -- und dort ist die Frage echt. */
    Zustandszeile(
        text = stringResource(
            R.string.sync_gekoppelt_mit,
            Serveradresse.BASIS.removePrefix("https://").removePrefix("http://").trimEnd('/'),
        ),
        punktfarbe = Farbe.blau, schriftfarbe = Farbe.blauTief,
    )
    Zustandszeile(
        text = if (rueckstand > 0) {
            androidx.compose.ui.res.pluralStringResource(R.plurals.sync_rueckstand, rueckstand, rueckstand)
        } else {
            stringResource(R.string.sync_vollstaendig)
        },
        /* `orangeTief` UND NICHT `orange`. Der Punkt ist ein grafisches
         * Objekt und braucht nach WCAG 1.4.11 3,0 : 1 gegen die Fläche.
         * `marke_orange` auf `marke_schnee` erreicht 2,23 : 1,
         * `marke_orange_tief` 4,32 : 1; das Paar steht in der Liste von
         * `werkzeuge/kontraste.py`. */
        punktfarbe = if (rueckstand > 0) Farbe.orangeTief else Farbe.blau,
        schriftfarbe = Farbe.gedaempft,
    )

    /* WAS DER SERVER ABGEWIESEN HAT, STEHT HIER.
     *
     * Ein Paket mit 400 wird als `fehlerhaft` gemerkt und nicht wiederholt —
     * das ist richtig (Vertrag 5). Es fällt damit aber auch aus dem Rückstand,
     * und ohne diese Zeile sagte die App „Alles gesendet", während beim
     * Server ein Segment offen bleibt. Rot, weil hier tatsächlich etwas
     * verloren ist und niemand es von selbst merkt. */
    if (abgewiesen > 0) {
        Zustandszeile(
            text = androidx.compose.ui.res.pluralStringResource(
                R.plurals.sync_abgewiesen, abgewiesen, abgewiesen,
            ),
            punktfarbe = Farbe.rot, schriftfarbe = Farbe.rotTief,
        )
        /* VERWERFEN. Ohne Weg würde die rote Zeile zur Tapete: Sie stünde,
         * bis nach 30 Tagen die Frist räumt, und niemand könnte etwas tun.
         *
         * NEUTRAL, NICHT ROT: Verwerfen beendet die Ansicht nicht — Rot
         * bleibt „Dienst beenden" unten vorbehalten —, und den Fehltipp
         * fängt die Rückfrage. Der Knopf steht nur, wenn er etwas
         * verwirft ([Puffer.verwerfbar]): Ein laufendes Paket mit 400 wird
         * noch beschrieben und gehört erst nach dem Dienst dazu. Der Hinweis
         * darunter sagt, was die rote Zeile bedeutet und was ohne Zutun
         * geschieht. */
        if (verwerfbar > 0) {
            KnopfNeutral(stringResource(R.string.abgewiesene_verwerfen)) { aufVerwerfen() }
        }
        Hinweiskasten(
            androidx.compose.ui.res.pluralStringResource(
                R.plurals.abgewiesene_hinweis, abgewiesen, Raeumung.FRIST_TAGE.toInt(),
            ),
        )
    }

    /* Die Quittung nach „Verwerfen" — wie die Ergebniszeile darunter nur,
     * solange die Ansicht offen ist: Sie antwortet auf eine Handlung und ist
     * kein Zustand. Die Zahl ist die, die tatsächlich gelöscht wurde. */
    if (verworfen != null) {
        Text(
            text = androidx.compose.ui.res.pluralStringResource(
                R.plurals.sync_verworfen, verworfen, verworfen,
            ),
            color = Farbe.gedaempft, fontSize = 13.sp,
        )
    }

    /* Die Ergebniszeile lebt nur, solange die Ansicht offen ist — sie steht
     * in `NAdokuApp` und nicht im Puffer. Nach einem Neustart der App gibt es
     * sie nicht mehr, und das ist richtig: Sie ist die Quittung auf eine
     * Handlung, kein Zustand. */
    val ergebnis = sendeergebnis
    if (sendelaufLaeuft) {
        Text(
            text = stringResource(R.string.sync_sendet),
            color = Farbe.gedaempft, fontSize = 13.sp,
        )
    } else if (ergebnis != null) {
        Text(
            text = when (ergebnis.ausgang) {
                Sendeausgang.GESENDET ->
                    stringResource(R.string.sync_ergebnis_gesendet, ergebnis.hhmm)
                Sendeausgang.KEIN_NETZ -> stringResource(R.string.sync_ergebnis_kein_netz)
                Sendeausgang.SCHLUESSEL_ABGEWIESEN ->
                    stringResource(R.string.sync_ergebnis_schluessel)
                Sendeausgang.KEIN_SCHLUESSEL ->
                    stringResource(R.string.sync_ergebnis_kein_schluessel)
                Sendeausgang.PAKET_ABGEWIESEN ->
                    androidx.compose.ui.res.pluralStringResource(
                        R.plurals.sync_ergebnis_abgewiesen, ergebnis.anzahl, ergebnis.anzahl,
                    )
            },
            color = when (ergebnis.ausgang) {
                Sendeausgang.GESENDET -> Farbe.gedaempft
                else -> Farbe.rotTief
            },
            fontSize = 13.sp,
        )
    }

    /* „JETZT SENDEN" NUR, WENN ES ETWAS ZU SENDEN GIBT und gerade kein Lauf
     * unterwegs ist. Ein Knopf, der nichts tut, ist schlimmer als keiner: Wer
     * ihn drückt und nichts geschieht, hält die App für kaputt. */
    if (rueckstand > 0 && !sendelaufLaeuft) {
        KnopfNeutral(stringResource(R.string.sync_jetzt_senden)) { aufJetztSenden() }
    }
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
    // Die Vorgabe ist die zuletzt getroffene Wahl.
    Zweierwahl(
        links = stringResource(R.string.modus_phasen),
        rechts = stringResource(R.string.modus_nur_aufzeichnen),
        linksGewaehlt = stand.modus == Modus.MIT_PHASENKNOEPFEN,
    ) { links ->
        aufModus(if (links) Modus.MIT_PHASENKNOEPFEN else Modus.NUR_AUFZEICHNEN)
    }
    /* KEIN „Dienst beginnen", solange eine der beiden Sperren steht. Der
     * Knopf verschwindet, statt beim Druck abzulehnen: Eine
     * Ablehnung müsste erklärt werden, und über dem Knopf steht die
     * Erklärung schon. */
    if (stand.ortungFreigegeben && stand.standortAn) {
        KnopfPrimaer(stringResource(R.string.dienst_beginnen)) { aufBeginnen() }
    }
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
        // KEIN Phasenknopf — kein versehentlicher Druck mit Handschuhen.
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

    /* DER WECHSEL WÄHREND DES DIENSTES IST VERLUSTFREI. Er blendet
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

    // Beendende Handlung: vollflächig rot, mit Rückfrage.
    KnopfBeenden(stringResource(R.string.dienst_beenden)) { aufBeenden() }
}

/**
 * Der Wortlaut zum Ortungszustand.
 *
 * `null` faellt mit [Ortungsstand.SUCHT] zusammen, und das ist richtig:
 * Beides heisst, dass die App es noch nicht weiss. Sie darf dann nicht
 * „GPS ok" sagen.
 */
@Composable
private fun ortungstext(stand: Dienststand): String {
    val seit = stand.begonnenSeit.orEmpty()
    return when (stand.ortung) {
        Ortungsstand.OK -> stringResource(R.string.dienst_laeuft_ok, seit)
        Ortungsstand.KEIN_SIGNAL ->
            stringResource(R.string.dienst_laeuft_kein_signal, seit, stand.ortungSeitMin)
        Ortungsstand.UNGENAU -> stringResource(R.string.dienst_laeuft_ungenau, seit)
        Ortungsstand.STANDORT_AUS -> stringResource(R.string.dienst_laeuft_standort_aus, seit)
        Ortungsstand.FREIGABE_FEHLT ->
            stringResource(R.string.dienst_laeuft_freigabe_fehlt, seit)
        Ortungsstand.SUCHT, null -> stringResource(R.string.dienst_laeuft_sucht, seit)
    }
}

/**
 * Die Farbe dazu — **drei Stufen, nicht sechs**.
 *
 * Alle vier Zustaende, in denen nichts aufgezeichnet wird, sind rot und
 * unterscheiden sich am Wortlaut. Orange fuer `UNGENAU` geht nicht:
 * nachgerechnet traegt kein vorhandenes Orange Text auf Schnee
 * (`marke_orange` 2,23 : 1, `marke_orange_tief` 4,32 : 1 — beide unter AA).
 * `rotTief` erreicht 7,58 : 1. Die farbliche Abstufung „warnt" gegen „fehlt
 * ganz" geht dabei verloren; aufgezeichnet wird in beiden Faellen nichts, und
 * das ist die Aussage, auf die es ankommt.
 */
@Composable
private fun ortungsfarbe(stand: Ortungsstand?) = when (stand) {
    Ortungsstand.OK -> Farbe.asphalt
    Ortungsstand.SUCHT, null -> Farbe.gedaempft
    else -> Farbe.rotTief
}

@Preview(showBackground = true)
@Composable
private fun VorschauRuhend() = DienstAnsicht(
    stand = Dienststand(false, null, Modus.MIT_PHASENKNOEPFEN, 0, "0,0", true),
             logoWahl = LogoWahl.LUFT,
    rueckstand = 0, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufStandortEinschalten = {}, aufEinstellungen = {},
)

/** Die Sperre vor dem Dienst: Standort aus, kein Knopf „Dienst beginnen". */
@Preview(showBackground = true)
@Composable
private fun VorschauStandortAus() = DienstAnsicht(
    stand = Dienststand(
        false, null, Modus.MIT_PHASENKNOEPFEN, 0, "0,0",
        ortungFreigegeben = true, standortAn = false,
    ),
             logoWahl = LogoWahl.LUFT,
    rueckstand = 0, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufStandortEinschalten = {}, aufEinstellungen = {},
)

@Preview(showBackground = true)
@Composable
private fun VorschauLaufendNurAufzeichnen() = DienstAnsicht(
    stand = Dienststand(
        true, "07:02", Modus.NUR_AUFZEICHNEN, 1483, "126,4",
        ortungFreigegeben = true, ortung = Ortungsstand.OK,
    ),
             logoWahl = LogoWahl.BODEN,
    rueckstand = 2, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufStandortEinschalten = {}, aufEinstellungen = {},
)

/** Der Fall, der nicht verschwiegen werden darf: Dienst läuft, aufgezeichnet wird nichts. */
@Preview(showBackground = true)
@Composable
private fun VorschauLaufendOhneSignal() = DienstAnsicht(
    stand = Dienststand(
        true, "07:02", Modus.NUR_AUFZEICHNEN, 1483, "126,4",
        ortungFreigegeben = true, ortung = Ortungsstand.KEIN_SIGNAL, ortungSeitMin = 3,
    ),
             logoWahl = LogoWahl.BODEN,
    rueckstand = 0, aufModus = {}, aufBeginnen = {}, aufBeenden = {},
    aufOrtungFreigeben = {}, aufStandortEinschalten = {}, aufEinstellungen = {},
)
