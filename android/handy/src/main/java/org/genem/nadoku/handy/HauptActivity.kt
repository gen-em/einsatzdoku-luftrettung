package org.genem.nadoku.handy

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.os.Build
import android.os.Bundle
import android.os.PowerManager
import android.provider.Settings
import androidx.activity.ComponentActivity
import androidx.activity.compose.rememberLauncherForActivityResult
import androidx.activity.compose.setContent
import androidx.activity.result.contract.ActivityResultContracts
import androidx.compose.material3.AlertDialog
import androidx.compose.material3.Text
import androidx.compose.material3.TextButton
import androidx.compose.runtime.Composable
import androidx.compose.runtime.LaunchedEffect
import androidx.compose.runtime.getValue
import androidx.compose.runtime.mutableIntStateOf
import androidx.compose.runtime.mutableStateOf
import androidx.compose.runtime.remember
import androidx.compose.runtime.rememberCoroutineScope
import androidx.compose.runtime.setValue
import androidx.compose.ui.platform.LocalContext
import androidx.compose.ui.res.stringResource
import androidx.compose.ui.text.font.FontWeight
import androidx.core.content.ContextCompat
import androidx.core.net.toUri
import kotlinx.coroutines.Dispatchers
import kotlinx.coroutines.delay
import kotlinx.coroutines.launch
import kotlinx.coroutines.withContext
import org.genem.nadoku.BuildConfig
import org.genem.nadoku.R
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.aufzeichnung.AufzeichnungsDienst
import org.genem.nadoku.handy.dienst.Modus
import org.genem.nadoku.handy.dienst.Zeit
import org.genem.nadoku.handy.kopplung.Geraeteangabe
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.kopplung.Kopplungsdienst
import org.genem.nadoku.handy.kopplung.Kopplungsergebnis
import org.genem.nadoku.handy.kopplung.Trennergebnis
import org.genem.nadoku.handy.tresor.KeystoreTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import java.io.File
import java.time.Instant

/**
 * Der eine Bildschirm der Handy-App.
 *
 * EINE ACTIVITY, mehrere Ansichten darin. Der Grund ist der
 * Vordergrunddienst: Die Aufzeichnung läuft weiter, während die Oberfläche
 * beendet ist — die Activity ist ein **Fenster auf einen Zustand, den sie
 * nicht besitzt**. Mehrere Activities müssten sich diesen Zustand teilen und
 * wären nur mehr Stellen, an denen er auseinanderlaufen kann.
 *
 * DER ZUSTAND KOMMT AUS DEM PUFFER, nicht aus dem Arbeitsspeicher. Deshalb
 * überlebt er den Absturz der App und den Neustart des Handys, und deshalb
 * fragt die Oberfläche ihn regelmäßig ab, statt ihn zu halten.
 *
 * STAND B3: Aufzeichnung und Dienstklammer stehen. Gesendet wird noch nichts
 * (B4), Phasen gibt es noch keine (B5) — der Rückstand ist deshalb fest 0.
 */
class HauptActivity : ComponentActivity() {

    override fun onCreate(savedInstanceState: Bundle?) {
        super.onCreate(savedInstanceState)
        setContent { NAdokuOberflaeche(NAdokuApp.von(this)) }
    }
}

/** Welche Ansicht gerade offen ist. */
private sealed interface Ansicht {
    data object Dienst : Ansicht
    data object Einstellungen : Ansicht
}

@Composable
fun NAdokuOberflaeche(app: NAdokuApp) {
    val kontext = LocalContext.current
    val hilfsfaden = rememberCoroutineScope()

    val tresor = remember {
        Schluesseltresor(
            File(kontext.applicationContext.filesDir, Schluesseltresor.DATEINAME),
            KeystoreTresorschluessel(),
        )
    }
    val dienst = remember {
        Kopplungsdienst(
            netzweg = HttpNetzweg(),
            tresor = tresor,
            // B4 setzt hier die Warteschlange ein. Bis dahin gibt es nichts
            // zu senden — und eine erfundene Zahl wäre schlimmer als die Null.
            rueckstand = { 0 },
        )
    }
    val geraet = remember {
        val masse = kontext.resources.displayMetrics
        Geraeteangabe.vomGeraet(masse.widthPixels, masse.heightPixels, BuildConfig.VERSION_NAME)
    }

    var serverBasis by remember { mutableStateOf(app.einstellungen.serverBasis) }
    var gekoppelt by remember { mutableStateOf(tresor.gekoppelt()) }
    var schritt by remember { mutableStateOf<Kopplungsschritt>(Kopplungsschritt.Wahl) }
    var trennmeldung by remember { mutableStateOf<Trennergebnis?>(null) }
    var trennfrageOffen by remember { mutableStateOf(false) }

    if (!gekoppelt) {
        KopplungAnsicht(
            schritt = schritt,
            serverBasis = serverBasis,
            logoWahl = app.einstellungen.logoWahl,
            aufSchritt = { schritt = it },
            aufKoppeln = { basis, code ->
                schritt = Kopplungsschritt.Laeuft
                hilfsfaden.launch {
                    val e = withContext(Dispatchers.IO) { dienst.koppeln(basis, code, geraet) }
                    when (e) {
                        is Kopplungsergebnis.Gekoppelt -> {
                            app.einstellungen.serverBasis = basis
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

    GekoppelteOberflaeche(
        app = app,
        serverBasis = serverBasis,
        trennmeldung = trennmeldung,
        trennfrageOffen = trennfrageOffen,
        aufTrennfrage = { trennfrageOffen = it },
        aufTrennen = {
            trennfrageOffen = false
            hilfsfaden.launch {
                val e = withContext(Dispatchers.IO) { dienst.trennen(serverBasis.orEmpty()) }
                trennmeldung = e
                if (e !is Trennergebnis.Rueckstand) {
                    schritt = Kopplungsschritt.Getrennt(nurLokal = e is Trennergebnis.NurLokal)
                    gekoppelt = false
                }
            }
        },
    )
}

@Composable
private fun GekoppelteOberflaeche(
    app: NAdokuApp,
    serverBasis: String?,
    trennmeldung: Trennergebnis?,
    trennfrageOffen: Boolean,
    aufTrennfrage: (Boolean) -> Unit,
    aufTrennen: () -> Unit,
) {
    val kontext = LocalContext.current

    var ansicht by remember { mutableStateOf<Ansicht>(Ansicht.Dienst) }
    var logoWahl by remember { mutableStateOf(app.einstellungen.logoWahl) }
    var uhrSperre by remember { mutableStateOf(app.einstellungen.uhrSperre) }
    var modus by remember { mutableStateOf(app.einstellungen.letzterModus) }
    var beendenFrageOffen by remember { mutableStateOf(false) }
    var akkuFrageOffen by remember { mutableStateOf(false) }

    var ortungFrei by remember {
        mutableStateOf(
            ContextCompat.checkSelfPermission(kontext, Manifest.permission.ACCESS_FINE_LOCATION)
                == PackageManager.PERMISSION_GRANTED
        )
    }

    /* DER ZUSTAND WIRD ABGEFRAGT, NICHT GEHALTEN. Der Vordergrunddienst
     * schreibt in denselben Puffer; die Oberfläche muss also nachsehen, statt
     * sich auf einen Wert im Arbeitsspeicher zu verlassen. Eine Sekunde ist
     * für einen Punktzähler reichlich schnell und kostet nichts, solange die
     * Ansicht offen ist — sie läuft mit ihr an und mit ihr aus. */
    var takt by remember { mutableIntStateOf(0) }
    LaunchedEffect(Unit) {
        while (true) {
            delay(1000)
            takt++
        }
    }

    val stand = remember(takt, modus, ortungFrei) {
        val laufend = app.klammer.laufenderDienst()
        val offenesPaket = laufend?.let {
            app.puffer.offenesPaket(org.genem.nadoku.handy.puffer.Paketzeile.ART_EINSATZ)
                ?: app.puffer.offenesPaket(org.genem.nadoku.handy.puffer.Paketzeile.ART_RUHESEGMENT)
        }
        Dienststand(
            laeuft = laufend != null,
            begonnenHhmm = laufend?.let { Zeit.hhmm(Instant.parse(it.begonnenAt)) },
            modus = if (laufend != null) app.klammer.modus() else modus,
            punkte = offenesPaket?.let { app.puffer.punktzahl(it.id) } ?: 0L,
            streckeKm = "%.1f".format(app.klammer.streckeM() / 1000.0),
            ortungFreigegeben = ortungFrei,
        )
    }

    val freigabeFrage = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { ergebnis ->
        ortungFrei = ergebnis[Manifest.permission.ACCESS_FINE_LOCATION] == true
        if (ortungFrei && !app.einstellungen.akkuHinweisGezeigt) akkuFrageOffen = true
    }

    if (beendenFrageOffen) {
        Rueckfrage(
            titel = stringResource(R.string.dienst_beenden_frage),
            text = stringResource(R.string.dienst_beenden_text),
            ja = stringResource(R.string.dienst_beenden_ja),
            aufJa = {
                beendenFrageOffen = false
                app.klammer.beenden()
                AufzeichnungsDienst.beenden(kontext)
                takt++
            },
            aufNein = { beendenFrageOffen = false },
        )
    }
    if (trennfrageOffen) {
        Rueckfrage(
            titel = stringResource(R.string.trennen_frage),
            text = stringResource(R.string.trennen_frage_text),
            ja = stringResource(R.string.trennen_ja),
            aufJa = aufTrennen,
            aufNein = { aufTrennfrage(false) },
        )
    }
    if (akkuFrageOffen) {
        Akkufrage(
            aufOeffnen = {
                akkuFrageOffen = false
                app.einstellungen.akkuHinweisGezeigt = true
                akkuEinstellungOeffnen(kontext)
            },
            aufSpaeter = {
                akkuFrageOffen = false
                app.einstellungen.akkuHinweisGezeigt = true
            },
        )
    }

    when (ansicht) {
        is Ansicht.Einstellungen -> EinstellungenAnsicht(
            logoWahl = logoWahl,
            uhrSperre = uhrSperre,
            dienstLaeuft = stand.laeuft,
            trennmeldung = trennmeldung,
            aufLogoWahl = { app.einstellungen.logoWahl = it; logoWahl = it },
            aufUhrSperre = { app.einstellungen.uhrSperre = it; uhrSperre = it },
            aufTrennen = { aufTrennfrage(true) },
            aufZurueck = { ansicht = Ansicht.Dienst },
        )

        is Ansicht.Dienst -> DienstAnsicht(
            stand = stand,
            serverBasis = serverBasis,
            logoWahl = logoWahl,
            rueckstand = 0,
            aufModus = { gewaehlt ->
                modus = gewaehlt
                app.einstellungen.letzterModus = gewaehlt
                // Während des Dienstes: verlustfrei umschalten (E-S4-20).
                app.klammer.modusWechseln(gewaehlt)
                takt++
            },
            aufBeginnen = {
                if (!ortungFrei) {
                    freigabeFrage.launch(noetigeFreigaben())
                } else {
                    app.klammer.beginnen(modus)
                    AufzeichnungsDienst.starten(kontext)
                    if (!app.einstellungen.akkuHinweisGezeigt) akkuFrageOffen = true
                    takt++
                }
            },
            aufBeenden = { beendenFrageOffen = true },
            aufOrtungFreigeben = { freigabeFrage.launch(noetigeFreigaben()) },
            aufEinstellungen = { ansicht = Ansicht.Einstellungen },
        )
    }
}

/**
 * Ortung immer, Benachrichtigung erst ab Android 13 — davor gibt es die
 * Freigabe nicht, und sie anzufragen wirft eine Ausnahme.
 */
private fun noetigeFreigaben(): Array<String> =
    if (Build.VERSION.SDK_INT >= Build.VERSION_CODES.TIRAMISU) {
        arrayOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION,
            Manifest.permission.POST_NOTIFICATIONS,
        )
    } else {
        arrayOf(
            Manifest.permission.ACCESS_FINE_LOCATION,
            Manifest.permission.ACCESS_COARSE_LOCATION,
        )
    }

/**
 * Die Führung zur Akku-Freistellung (E-S4-05).
 *
 * SIE FÜHRT HIN UND ERZWINGT NICHTS. Ob die Freistellung hält, zeigt nur das
 * Gerät — und namentlich Samsungs „Apps im Tiefschlaf" ist ein zweiter
 * Schalter an ganz anderer Stelle, den keine App erreicht. Die App fragt
 * deshalb **einmal**, merkt sich das und drängt nicht wieder.
 */
private fun akkuEinstellungOeffnen(kontext: Context) {
    val strom = kontext.getSystemService(Context.POWER_SERVICE) as PowerManager
    val schonFrei = strom.isIgnoringBatteryOptimizations(kontext.packageName)
    val absicht = if (schonFrei) {
        Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)
    } else {
        /* Der gezielte Weg (ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS) zeigt
         * den Dialog direkt. Fehlt er auf dem Gerät — manche Hersteller haben
         * ihn nicht —, bleibt die allgemeine Liste. */
        Intent(
            Settings.ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS,
            "package:${kontext.packageName}".toUri(),
        )
    }
    try {
        kontext.startActivity(absicht.addFlags(Intent.FLAG_ACTIVITY_NEW_TASK))
    } catch (e: android.content.ActivityNotFoundException) {
        kontext.startActivity(
            Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        )
    }
}

/**
 * Die Rückfrage vor einer beendenden Handlung.
 *
 * `AlertDialog` aus Material 3, weil es der Systembaustein für genau diese
 * Frage ist — er bringt Fokusführung, Zurück-Taste und Vorlesbarkeit mit.
 * Die bestätigende Handlung trägt die beendende Farbe, die abbrechende nicht.
 */
@Composable
private fun Rueckfrage(
    titel: String,
    text: String,
    ja: String,
    aufJa: () -> Unit,
    aufNein: () -> Unit,
) {
    AlertDialog(
        onDismissRequest = aufNein,
        title = { Text(titel, color = Farbe.dunkelblau) },
        text = { Text(text, color = Farbe.asphalt) },
        confirmButton = {
            TextButton(onClick = aufJa) {
                Text(ja, color = Farbe.rotTief, fontWeight = FontWeight.SemiBold)
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

@Composable
private fun Akkufrage(aufOeffnen: () -> Unit, aufSpaeter: () -> Unit) {
    AlertDialog(
        onDismissRequest = aufSpaeter,
        title = { Text(stringResource(R.string.akku_titel), color = Farbe.dunkelblau) },
        text = { Text(stringResource(R.string.akku_hinweis), color = Farbe.asphalt) },
        confirmButton = {
            TextButton(onClick = aufOeffnen) {
                Text(
                    stringResource(R.string.akku_oeffnen),
                    color = Farbe.blauTief, fontWeight = FontWeight.SemiBold,
                )
            }
        },
        dismissButton = {
            TextButton(onClick = aufSpaeter) {
                Text(stringResource(R.string.akku_spaeter), color = Farbe.dunkelblau)
            }
        },
        containerColor = Farbe.schnee,
    )
}
