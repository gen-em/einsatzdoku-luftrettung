package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Box
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.fillMaxWidth
import androidx.compose.foundation.layout.padding
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.setValue
import androidx.compose.ui.Alignment
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.unit.dp
import androidx.compose.ui.unit.sp
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.kopplung.Abweisung
import org.genem.nadoku.handy.kopplung.Kopplungscode
import org.genem.nadoku.handy.kopplung.QrInhalt
import org.genem.nadoku.handy.kopplung.Serveradresse

/**
 * Was der Kopplungsbildschirm gerade zeigt.
 *
 * DIE ABLEHNUNGSGRÜNDE SIND GETRENNT GEFÜHRT, und das ist keine Feinheit: Ein
 * fremder QR-Code und eine unbrauchbare Adresse sind Zustände, in denen
 * **nichts hinausgegangen ist**. Sie unter [Abweisung] zu führen hieße, dafür
 * „Der Server hat einen Fehler gemeldet" anzuzeigen — eine Auskunft über einen
 * Server, der gar nicht gefragt wurde.
 */
sealed interface Kopplungsschritt {
    data object Wahl : Kopplungsschritt
    data object VonHand : Kopplungsschritt
    data object Scannen : Kopplungsschritt
    data object Laeuft : Kopplungsschritt

    /** Der Server hat abgelehnt (oder die Vorprüfung des Codes). */
    data class Abgewiesen(val art: Abweisung, val servermeldung: String? = null) : Kopplungsschritt

    /** Im Bild war ein QR-Code, aber keiner von NAdoku. */
    data object FremderQr : Kopplungsschritt

    /** Die eingetippte Server-Adresse ist keine. */
    data object AdresseUnbrauchbar : Kopplungsschritt

    /**
     * Nach dem Trennen. GESAGT WIRD ES IMMER (E-S4-12): Ohne Antwort des
     * Servers steht der Eintrag dort noch und belegt einen Geräteplatz — das
     * ist im Browser ein Klick, aber nur, wenn jemand davon weiß.
     */
    data class Getrennt(val nurLokal: Boolean) : Kopplungsschritt
}

/**
 * Der Kopplungsbildschirm (E-S4-12, E-S4-15).
 *
 * ZWEI WEGE, EIN ZIEL: der Kameraschwenk und das Abtippen. Der QR-Code trägt
 * **beides** — Server-Adresse und Code —, deshalb ist er der Primärknopf; das
 * Abtippen bleibt gleichwertig erreichbar, weil eine Kamera fehlen oder eine
 * Freigabe abgelehnt sein kann und die App dann trotzdem vollständig sein muss.
 */
@Composable
fun KopplungAnsicht(
    schritt: Kopplungsschritt,
    serverBasis: String?,
    logoWahl: LogoWahl,
    aufSchritt: (Kopplungsschritt) -> Unit,
    aufKoppeln: (basis: String, code: String) -> Unit,
    modifier: Modifier = Modifier,
) {
    var adresse by remember(serverBasis) { mutableStateOf(serverBasis.orEmpty()) }
    var code by remember { mutableStateOf("") }

    if (schritt is Kopplungsschritt.Scannen) {
        QrScanBildschirm(
            aufInhalt = { roh ->
                val i = QrInhalt.lese(roh)
                if (i == null) {
                    aufSchritt(Kopplungsschritt.FremderQr)
                } else {
                    adresse = i.basis
                    code = i.code
                    aufKoppeln(i.basis, i.code)
                }
            },
            aufAbbruch = { aufSchritt(Kopplungsschritt.Wahl) },
            modifier = modifier,
        )
        return
    }

    Column(modifier = modifier.fillMaxSize().background(Farbe.rauch)) {
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
                // Der Hinweis nennt den NÄCHSTEN Schritt, und der ist ein
                // anderer, je nachdem was fehlt (Backlog Nr. 11).
                Hinweiskasten(
                    if (adresse.isBlank()) stringResource(R.string.sync_fehlt_server)
                    else stringResource(R.string.sync_fehlt_kopplung)
                )

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

                Eingabefeld(
                    wert = adresse,
                    beschriftung = stringResource(R.string.kopplung_server),
                    beispiel = stringResource(R.string.kopplung_server_beispiel),
                ) { adresse = it }

                when (schritt) {
                    is Kopplungsschritt.Laeuft ->
                        Text(
                            text = stringResource(R.string.kopplung_laeuft),
                            color = Farbe.blauTief, fontSize = 15.sp,
                        )

                    is Kopplungsschritt.VonHand -> {
                        Eingabefeld(
                            wert = code,
                            beschriftung = stringResource(R.string.kopplung_code),
                            beispiel = stringResource(R.string.kopplung_code_beispiel),
                            grossschreiben = true,
                        ) { code = Kopplungscode.normalisiere(it).take(Kopplungscode.LAENGE) }

                        KnopfPrimaer(stringResource(R.string.kopplung_koppeln)) {
                            koppelnWennMoeglich(adresse, code, aufSchritt, aufKoppeln)
                        }
                        KnopfNeutral(stringResource(R.string.kopplung_qr)) {
                            aufSchritt(Kopplungsschritt.Scannen)
                        }
                    }

                    else -> {
                        KnopfPrimaer(stringResource(R.string.kopplung_qr)) {
                            aufSchritt(Kopplungsschritt.Scannen)
                        }
                        KnopfNeutral(stringResource(R.string.kopplung_von_hand)) {
                            aufSchritt(Kopplungsschritt.VonHand)
                        }
                    }
                }

                when (schritt) {
                    is Kopplungsschritt.Abgewiesen -> Meldungsblock(
                        titel = stringResource(Meldungen.text(schritt.art)),
                        hinweis = stringResource(Meldungen.hinweis(schritt.art)),
                        servermeldung = schritt.servermeldung,
                        warnend = true,
                    )

                    is Kopplungsschritt.FremderQr -> Meldungsblock(
                        titel = stringResource(R.string.kopplung_qr_fremd),
                        hinweis = stringResource(R.string.kopplung_von_hand),
                        warnend = true,
                    )

                    is Kopplungsschritt.AdresseUnbrauchbar -> Meldungsblock(
                        titel = stringResource(R.string.fehler_server_adresse),
                        hinweis = stringResource(R.string.fehler_server_adresse_hinweis),
                        warnend = true,
                    )

                    else -> Unit
                }
            }
        }
    }
}

/**
 * Die Adresse wird VOR dem Senden geprüft. Sonst ginge eine Anfrage an eine
 * Adresse hinaus, die keine ist — und der Fehler käme als „keine Verbindung"
 * zurück, was die Ursache verschweigt.
 */
private fun koppelnWennMoeglich(
    adresse: String,
    code: String,
    aufSchritt: (Kopplungsschritt) -> Unit,
    aufKoppeln: (String, String) -> Unit,
) {
    val basis = Serveradresse.normalisiere(adresse)
    when {
        basis == null -> aufSchritt(Kopplungsschritt.AdresseUnbrauchbar)
        !Kopplungscode.gueltig(code) ->
            aufSchritt(Kopplungsschritt.Abgewiesen(Abweisung.CODE_UNBRAUCHBAR, null))
        else -> aufKoppeln(basis, code)
    }
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

@Composable
private fun QrScanBildschirm(
    aufInhalt: (String) -> Unit,
    aufAbbruch: () -> Unit,
    modifier: Modifier = Modifier,
) {
    var kameraFehlt by remember { mutableStateOf(false) }

    Column(modifier = modifier.fillMaxSize().background(Farbe.asphalt)) {
        Box(modifier = Modifier.fillMaxWidth().weight(1f), contentAlignment = Alignment.Center) {
            if (!kameraFehlt) {
                QrKamera(
                    aufFund = aufInhalt,
                    aufFehlenderFreigabe = { kameraFehlt = true },
                )
            }
            if (kameraFehlt) {
                Text(
                    text = stringResource(R.string.kopplung_kamera_fehlt),
                    color = Farbe.aufDunkel, fontSize = 15.sp,
                    modifier = Modifier.padding(Abstand.fuenf),
                )
            }
        }
        Column(
            modifier = Modifier.padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Text(
                text = stringResource(R.string.kopplung_qr_zielen),
                color = Farbe.sand, fontSize = 13.sp, fontWeight = FontWeight.Normal,
            )
            KnopfNeutral(stringResource(R.string.kopplung_abbrechen)) { aufAbbruch() }
        }
    }
}
