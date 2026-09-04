package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.WindowInsets
import androidx.compose.foundation.layout.navigationBars
import androidx.compose.foundation.layout.windowInsetsPadding
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.text.style.TextAlign
import androidx.compose.ui.unit.sp
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.kopplung.Abweisung
import org.genem.nadoku.handy.kopplung.Kopplungscode

/**
 * Was der Kopplungsbildschirm gerade zeigt.
 *
 * DIE ZUSTÄNDE FOLGEN DEM VERTRAG, NICHT DEM BILDSCHIRM. Jeder von ihnen
 * entspricht einem Punkt im Ablauf von 1a: noch nichts ([Bereit]), Sitzung
 * wird geholt ([Startet]), Code steht und wir fragen nach ([Wartet]), ein
 * Konto hat ihn eingegeben ([Frage]), fertig ([Fertig]). So bleibt beim Lesen
 * nachvollziehbar, welcher Zustand welche Serverantwort abbildet.
 */
sealed interface Kopplungsschritt {
    /** Nichts läuft. Ein Knopf, und daneben steht, was er tut. */
    data object Bereit : Kopplungsschritt

    /** `start` ist unterwegs. */
    data object Startet : Kopplungsschritt

    /**
     * Der Code steht auf dem Bildschirm, `status` fragt im Takt nach.
     *
     * @param restSekunden Restgültigkeit. Sie kommt aus jeder `status`-Antwort
     *   und wird nicht selbst heruntergezählt: Der Server rechnet sie aus
     *   derselben Uhr wie die Fristprüfung (Vertrag 1a.1).
     */
    data class Wartet(val code: String, val restSekunden: Int) : Kopplungsschritt

    /**
     * DAS ZWEITE TOR. Ein Konto hat den Code eingegeben; jetzt entscheidet der
     * Mensch am Gerät.
     *
     * @param konto die maskierte Adresse — eine Zeichenkette für Menschen, die
     *   angezeigt und nirgends zerlegt oder gespeichert wird (Vertrag 1a.2).
     */
    data class Frage(val code: String, val konto: String, val restSekunden: Int) :
        Kopplungsschritt

    /** `bestaetigen` ist unterwegs. */
    data object Bestaetigt : Kopplungsschritt

    /** Der Server hat abgelehnt. */
    data class Abgewiesen(val art: Abweisung, val servermeldung: String? = null) :
        Kopplungsschritt

    /** Nach einem Nein — hier oder im Browser. */
    data object Abgebrochen : Kopplungsschritt

    /**
     * Nach dem Trennen. GESAGT WIRD ES IMMER (E-S4-12): Ohne Antwort des
     * Servers steht der Eintrag dort noch und belegt einen Geräteplatz — das
     * ist im Browser ein Klick, aber nur, wenn jemand davon weiß.
     */
    data class Getrennt(val nurLokal: Boolean) : Kopplungsschritt
}

/**
 * Der Kopplungsbildschirm (JSON-Vertrag 1a, R49/S5, R63).
 *
 * EIN WEG STATT ZWEI. Bis Android 0.10.1 standen hier zwei gleichwertige Wege
 * nebeneinander — Kameraschwenk und Abtippen — und darüber ein Adressfeld.
 * Alle drei sind fort: Der QR-Code trug die Serveradresse, und die ist jetzt
 * fest (R63); der eingetippte Code kommt nicht mehr von hier, sondern **geht**
 * von hier in den Browser.
 *
 * Was bleibt, ist ein Knopf und danach eine Zahl zum Ablesen. Das ist der
 * eigentliche Gewinn der Umkehr: Der Bildschirm, der vorher eine Eingabemaske
 * war, ist jetzt eine Anzeige.
 */
@Composable
fun KopplungAnsicht(
    schritt: Kopplungsschritt,
    logoWahl: LogoWahl,
    aufStarten: () -> Unit,
    aufAntwort: (ja: Boolean) -> Unit,
    modifier: Modifier = Modifier,
) {
    Column(
        modifier = modifier
            .fillMaxSize()
            .background(Farbe.rauch)
            /* Die Navigationsleiste unten -- und im Querformat die seitliche
             * Geste (Backlog Nr. 86). Wie oben gilt: `background` zuerst, damit
             * die Flaeche bis zum Rand faerbt, das Padding danach, damit der
             * Inhalt nicht darunter geraet. */
            .windowInsetsPadding(WindowInsets.navigationBars)
    ) {
        Kopfleiste(titel = stringResource(R.string.kopplung_titel), logoWahl = logoWahl)

        Column(
            modifier = Modifier.padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Zustandszeile(
                    text = stringResource(R.string.sync_nicht_eingerichtet),
                    punktfarbe = Farbe.rot,
                    schriftfarbe = Farbe.rotTief,
                )

                when (schritt) {
                    is Kopplungsschritt.Wartet -> {
                        Codeanzeige(schritt.code)
                        Hinweiskasten(stringResource(R.string.kopplung_code_wo))
                        Text(
                            text = stringResource(R.string.kopplung_wartet),
                            color = Farbe.blauTief, fontSize = 15.sp,
                        )
                        Restzeit(schritt.restSekunden)
                        KnopfNeutral(stringResource(R.string.kopplung_abbrechen)) {
                            aufAntwort(false)
                        }
                    }

                    is Kopplungsschritt.Frage -> {
                        /* DIE FRAGE STEHT ÜBER DEM CODE, nicht darunter: Ab
                         * jetzt ist der Code erledigt, und was zählt, ist die
                         * Adresse. Wer hier weiterliest, soll das Konto sehen
                         * und nicht noch einmal sechs Zeichen. */
                        Meldungsblock(
                            titel = stringResource(R.string.kopplung_frage_titel),
                            hinweis = stringResource(R.string.kopplung_frage_text),
                        )
                        Kontoanzeige(schritt.konto)
                        Restzeit(schritt.restSekunden)
                        KnopfPrimaer(stringResource(R.string.kopplung_ja)) { aufAntwort(true) }
                        KnopfNeutral(stringResource(R.string.kopplung_nein)) { aufAntwort(false) }
                    }

                    is Kopplungsschritt.Startet ->
                        Text(
                            text = stringResource(R.string.kopplung_laeuft),
                            color = Farbe.blauTief, fontSize = 15.sp,
                        )

                    is Kopplungsschritt.Bestaetigt ->
                        Text(
                            text = stringResource(R.string.kopplung_bestaetigt),
                            color = Farbe.blauTief, fontSize = 15.sp,
                        )

                    else -> {
                        Hinweiskasten(stringResource(R.string.kopplung_hinweis_start))

                        if (schritt is Kopplungsschritt.Getrennt) {
                            Meldungsblock(
                                titel = stringResource(
                                    if (schritt.nurLokal) R.string.trennen_nur_lokal
                                    else R.string.trennen_erfolg
                                ),
                                hinweis = if (schritt.nurLokal) {
                                    stringResource(R.string.trennen_nur_lokal_hinweis)
                                } else {
                                    null
                                },
                                warnend = schritt.nurLokal,
                            )
                        }

                        if (schritt is Kopplungsschritt.Abgebrochen) {
                            Meldungsblock(titel = stringResource(R.string.kopplung_abgebrochen))
                        }

                        if (schritt is Kopplungsschritt.Abgewiesen) {
                            Meldungsblock(
                                titel = stringResource(Meldungen.text(schritt.art)),
                                hinweis = stringResource(Meldungen.hinweis(schritt.art)),
                                servermeldung = schritt.servermeldung,
                                warnend = true,
                            )
                        }

                        /* NACH EINEM FEHLSCHLAG HEISST DER KNOPF ANDERS.
                         * „Kopplung starten" über einer roten Meldung liest
                         * sich, als sei nichts geschehen; „Neu beginnen" sagt,
                         * dass der vorige Versuch verfallen ist — und genau
                         * das ist er (jede Sitzung ist einmalig, 1a.1). */
                        val neuBeginnen = schritt is Kopplungsschritt.Abgewiesen ||
                            schritt is Kopplungsschritt.Abgebrochen
                        KnopfPrimaer(
                            stringResource(
                                if (neuBeginnen) R.string.kopplung_neu
                                else R.string.kopplung_start
                            )
                        ) { aufStarten() }
                    }
                }
            }
        }
    }
}

/**
 * Der Code, groß und in zwei Dreiergruppen.
 *
 * ER IST DAS GRÖSSTE AUF DEM BILDSCHIRM, und das ist der Zweck der Seite: Er
 * wird von hier auf einen anderen Bildschirm übertragen, oft mit dem Handy in
 * der einen und der Maus in der anderen Hand. `letterSpacing` gibt es dazu
 * nicht — die Gruppierung leistet dasselbe und überlebt jede Schriftgröße.
 */
@Composable
private fun Codeanzeige(code: String) {
    Column(
        modifier = Modifier.fillMaxWidth(),
        verticalArrangement = Arrangement.spacedBy(Abstand.eins),
    ) {
        Text(
            text = stringResource(R.string.kopplung_code_zeigen),
            color = Farbe.gedaempft, fontSize = 13.sp,
        )
        Text(
            text = Kopplungscode.gruppiert(code),
            color = Farbe.asphalt,
            fontSize = 34.sp,
            fontWeight = FontWeight.Bold,
            textAlign = TextAlign.Center,
            modifier = Modifier.fillMaxWidth(),
        )
    }
}

/**
 * Die maskierte Adresse des Kontos.
 *
 * SIE WIRD NICHT ZERLEGT UND NICHT GEPRÜFT — der Vertrag nennt sie
 * ausdrücklich eine Zeichenkette für Menschen (1a.2). Die App zeigt, was
 * ankommt.
 */
@Composable
private fun Kontoanzeige(konto: String) {
    Text(
        text = konto,
        color = Farbe.asphalt,
        fontSize = 20.sp,
        fontWeight = FontWeight.Bold,
        textAlign = TextAlign.Center,
        modifier = Modifier.fillMaxWidth(),
    )
}

/**
 * Die Restzeit — in Minuten, solange davon noch welche da sind.
 *
 * Eine sekundengenaue Anzeige lädt zum Zusehen ein; zehn Minuten sind
 * reichlich Zeit, um in Ruhe zum Browser zu gehen. Unter einer Minute wird es
 * genau, weil es dann tatsächlich darauf ankommt. Aufgerundet wird, damit
 * „Noch 1 Minuten" nicht neben einer Sitzung steht, die noch 90 Sekunden hat.
 */
@Composable
private fun Restzeit(sekunden: Int) {
    if (sekunden <= 0) return
    val text = if (sekunden >= 60) {
        stringResource(R.string.kopplung_rest_min, (sekunden + 59) / 60)
    } else {
        stringResource(R.string.kopplung_rest_sek, sekunden)
    }
    Text(text = text, color = Farbe.gedaempft, fontSize = 13.sp)
}

/**
 * Was ist los (erste Zeile), was hilft (zweite) — und, falls vorhanden, was
 * der Server dazu gesagt hat (dritte).
 *
 * Die Servermeldung steht UNTEN und ersetzt nie die erste Zeile: Die Texte von
 * `pair.php` sind für die Weboberfläche geschrieben, in ganzen Sätzen und ohne
 * Umlaute. Sie sollen sichtbar sein, aber nicht den eigenen Text verdrängen.
 */
@Composable
fun Meldungsblock(
    titel: String,
    hinweis: String? = null,
    servermeldung: String? = null,
    warnend: Boolean = false,
) {
    Column(verticalArrangement = Arrangement.spacedBy(Abstand.eins)) {
        Zustandszeile(
            text = titel,
            punktfarbe = if (warnend) Farbe.rot else Farbe.blau,
            schriftfarbe = if (warnend) Farbe.rotTief else Farbe.blauTief,
        )
        if (hinweis != null) {
            Text(text = hinweis, color = Farbe.gedaempft, fontSize = 13.sp)
        }
        if (!servermeldung.isNullOrBlank()) {
            Text(text = servermeldung, color = Farbe.gedaempft, fontSize = 12.sp)
        }
    }
}
