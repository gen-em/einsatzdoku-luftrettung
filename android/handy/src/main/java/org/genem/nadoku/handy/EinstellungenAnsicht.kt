package org.genem.nadoku.handy

import androidx.compose.foundation.background
import androidx.compose.foundation.layout.Arrangement
import androidx.compose.foundation.layout.Column
import androidx.compose.foundation.layout.fillMaxSize
import androidx.compose.foundation.layout.padding
import androidx.compose.foundation.rememberScrollState
import androidx.compose.foundation.verticalScroll
import androidx.compose.material3.Text
import androidx.compose.runtime.Composable
import androidx.compose.ui.Modifier
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.compose.ui.tooling.preview.Preview
import androidx.compose.ui.unit.sp
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.kopplung.Trennergebnis

/**
 * Die App-Einstellungen (E-S4-22b, E-S4-21d, E-S4-12).
 *
 * DREI DINGE, UND MEHR SOLL ES NICHT WERDEN: die Logo-Wahl, die Sperre der
 * Uhr und das Trennen. Die Verwaltung bleibt im Browser (R45) — eine App, die
 * anfängt, Einsätze zu bearbeiten, ist der Anfang einer zweiten Anwendung.
 *
 * DIE SPERRE DER UHR STEHT HIER UND NICHT AUF DER UHR, weil die Uhr keine
 * eigenen Einstellungen hat: Sie übernimmt sie über den Nachrichtenweg (C2) —
 * wie die Garmin, ohne Abstimmungsbedarf zwischen den Geräten.
 */
@Composable
fun EinstellungenAnsicht(
    logoWahl: LogoWahl,
    uhrSperre: Boolean,
    dienstLaeuft: Boolean,
    trennmeldung: Trennergebnis?,
    aufLogoWahl: (LogoWahl) -> Unit,
    aufUhrSperre: (Boolean) -> Unit,
    aufTrennen: () -> Unit,
    aufZurueck: () -> Unit,
) {
    Column(modifier = Modifier.fillMaxSize().background(Farbe.rauch)) {
        Kopfleiste(titel = stringResource(R.string.einstellungen), logoWahl = logoWahl)

        Column(
            modifier = Modifier.verticalScroll(rememberScrollState()).padding(Abstand.vier),
            verticalArrangement = Arrangement.spacedBy(Abstand.drei),
        ) {
            Karte {
                Text(
                    text = stringResource(R.string.einstellungen_logo),
                    color = Farbe.dunkelblau, fontSize = 15.sp, fontWeight = FontWeight.SemiBold,
                )
                Wahlzeile(
                    beschriftung = stringResource(R.string.logo_wechselnd),
                    zustand = stringResource(R.string.logo_wechselnd_hinweis),
                    gewaehlt = logoWahl == LogoWahl.WECHSELND,
                ) { aufLogoWahl(LogoWahl.WECHSELND) }
                Wahlzeile(
                    beschriftung = stringResource(R.string.logo_luft),
                    gewaehlt = logoWahl == LogoWahl.LUFT,
                ) { aufLogoWahl(LogoWahl.LUFT) }
                Wahlzeile(
                    beschriftung = stringResource(R.string.logo_boden),
                    gewaehlt = logoWahl == LogoWahl.BODEN,
                ) { aufLogoWahl(LogoWahl.BODEN) }
            }

            Karte {
                Text(
                    text = stringResource(R.string.einstellungen_uhr),
                    color = Farbe.dunkelblau, fontSize = 15.sp, fontWeight = FontWeight.SemiBold,
                )
                Wahlzeile(
                    beschriftung = stringResource(R.string.uhr_sperre),
                    zustand = stringResource(if (uhrSperre) R.string.an else R.string.aus),
                    gewaehlt = uhrSperre,
                ) { aufUhrSperre(!uhrSperre) }
                Text(
                    text = stringResource(R.string.uhr_sperre_hinweis),
                    color = Farbe.gedaempft, fontSize = 13.sp,
                )
            }

            Karte {
                if (trennmeldung is Trennergebnis.Rueckstand) {
                    Meldungsblock(
                        titel = androidx.compose.ui.res.pluralStringResource(
                            R.plurals.trennen_rueckstand,
                            trennmeldung.pakete, trennmeldung.pakete,
                        ),
                        hinweis = stringResource(R.string.trennen_rueckstand_hinweis),
                        warnend = true,
                    )
                }
                /* WÄHREND EINES LAUFENDEN DIENSTES WIRD NICHT GETRENNT.
                 * Der Rückstand sperrt es ohnehin, sobald das erste Paket
                 * abgeschlossen ist — aber ein Trennen mitten in der
                 * Aufzeichnung risse den laufenden Dienst von seinem Konto ab,
                 * und der Rest des Tages läge nirgends. Erst Dienst beenden. */
                if (dienstLaeuft) {
                    Meldungsblock(
                        titel = stringResource(R.string.trennen),
                        hinweis = stringResource(R.string.trennen_erst_dienst_beenden),
                    )
                } else {
                    KnopfBeenden(stringResource(R.string.trennen)) { aufTrennen() }
                }
            }

            Text(
                text = stringResource(R.string.fassung, BuildConfig.VERSION_NAME),
                color = Farbe.gedaempft, fontSize = 12.sp,
            )
            KnopfNeutral(stringResource(R.string.zurueck)) { aufZurueck() }
        }
    }
}

@Preview(showBackground = true)
@Composable
private fun VorschauEinstellungen() = EinstellungenAnsicht(
    logoWahl = LogoWahl.WECHSELND, uhrSperre = true, dienstLaeuft = false,
    trennmeldung = null, aufLogoWahl = {}, aufUhrSperre = {}, aufTrennen = {}, aufZurueck = {},
)
