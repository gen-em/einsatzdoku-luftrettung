package org.genem.nadoku.handy

import android.Manifest
import android.content.Context
import android.content.Intent
import android.content.pm.PackageManager
import android.location.LocationManager
import android.os.Build
import android.os.SystemClock
import android.os.Bundle
import android.os.PowerManager
import android.provider.Settings
import android.widget.Toast
import androidx.activity.ComponentActivity
import androidx.activity.enableEdgeToEdge
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
import org.genem.nadoku.handy.kopplung.Serveradresse
import org.genem.nadoku.gemeinsam.Farbe
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.aufzeichnung.AufzeichnungsDienst
import org.genem.nadoku.handy.aufzeichnung.Ortungsstand
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.handy.dienst.Zeit
import org.genem.nadoku.handy.kopplung.Geraeteangabe
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.kopplung.Abweisung
import org.genem.nadoku.handy.kopplung.Bestaetigungsergebnis
import org.genem.nadoku.handy.kopplung.Kopplungsdienst
import org.genem.nadoku.handy.kopplung.Sitzungsergebnis
import org.genem.nadoku.handy.kopplung.Sitzungsstand
import org.genem.nadoku.handy.kopplung.Trennergebnis
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
        /* RANDLOS, UND ZWAR AUSDRUECKLICH (Backlog Nr. 86).
         *
         * Seit `targetSdk = 36` bekommt die App die volle Flaeche ohnehin --
         * Android 15+ fragt nicht mehr. Der Aufruf aendert daran nichts; er
         * aendert, ob wir es WISSEN. Zwei Dinge kommen mit ihm:
         *
         * 1. Die Systemleisten werden durchsichtig statt farbig. `themen.xml`
         *    setzte dafuer `statusBarColor` -- ein Attribut, das seit API 35
         *    wirkungslos ist und deshalb ausgetragen wurde.
         * 2. Die Symbole der Leisten (Uhrzeit, Akku, Navigation) werden hell
         *    oder dunkel nach dem Untergrund gewaehlt. Bei uns liegt oben die
         *    dunkelblaue Kopfleiste, unten die helle Flaeche -- ohne diese
         *    Wahl waere eines von beiden unlesbar.
         *
         * Das Freihalten der Flaeche selbst leisten die Bausteine
         * (`Kopfleiste` oben, `Bildschirm` unten), nicht dieser Aufruf. */
        enableEdgeToEdge()
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

    val tresor = app.tresor
    val dienst = remember {
        Kopplungsdienst(
            netzweg = HttpNetzweg(),
            tresor = tresor,
            /* DIE RÜCKSTANDSSPERRE IST JETZT ECHT (E-S4-12, Backlog Nr. 14):
             * Abgeschlossene, noch nicht bestätigte Pakete gehören dem
             * bisherigen Konto; nach einer Neukopplung gingen sie an das
             * neue. Das wäre kein Datenverlust, sondern schlimmer — fremde
             * Einsätze in einem fremden Konto. */
            rueckstand = { app.puffer.rueckstand() },
        )
    }
    val geraet = remember {
        val masse = kontext.resources.displayMetrics
        Geraeteangabe.vomGeraet(masse.widthPixels, masse.heightPixels, BuildConfig.VERSION_NAME)
    }

    var gekoppelt by remember { mutableStateOf(tresor.gekoppelt()) }
    var schritt by remember { mutableStateOf<Kopplungsschritt>(Kopplungsschritt.Bereit) }
    var trennmeldung by remember { mutableStateOf<Trennergebnis?>(null) }
    var trennfrageOffen by remember { mutableStateOf(false) }

    if (!gekoppelt) {
        /* DIE ABFRAGE HÄNGT AM ZUSTAND, NICHT AN EINEM EIGENEN FADEN.
         *
         * `LaunchedEffect` mit dem Code als Schlüssel: Sie läuft, solange
         * gewartet wird, und Compose beendet sie von selbst, sobald der
         * Schritt wechselt oder die Ansicht verschwindet. Ein selbstverwalteter
         * Faden müsste beides von Hand können — und der Fall, den man dabei
         * vergisst, ist immer derselbe: Die Ansicht ist weg, die Abfrage
         * läuft weiter und zählt gegen den Ratenschutz.
         *
         * FÜNF SEKUNDEN, UND ERST NACH DER ANTWORT (Vertrag 1a.2): Die Pause
         * steht VOR der nächsten Abfrage, nicht daneben — damit eine langsame
         * Antwort den Takt streckt statt Abfragen zu stapeln. */
        val laufenderCode = when (val s = schritt) {
            is Kopplungsschritt.Wartet -> s.code
            is Kopplungsschritt.Frage -> s.code
            else -> null
        }
        LaunchedEffect(laufenderCode) {
            if (laufenderCode == null) return@LaunchedEffect
            while (true) {
                delay(Kopplungsdienst.ABFRAGETAKT_MS)
                when (val stand = withContext(Dispatchers.IO) { dienst.nachfragen() }) {
                    is Sitzungsstand.Offen ->
                        schritt = Kopplungsschritt.Wartet(laufenderCode, stand.restSekunden)

                    is Sitzungsstand.Beansprucht ->
                        schritt = Kopplungsschritt.Frage(
                            laufenderCode, stand.konto, stand.restSekunden,
                        )

                    /* Der Fall, in dem die Antwort auf `ja` verlorenging: Der
                     * Server kennt das Gerät bereits. Dann ist die Kopplung
                     * fertig, und nur die App weiß es noch nicht. */
                    is Sitzungsstand.Gekoppelt -> {
                        tresor.bestaetigen()
                        gekoppelt = true
                        schritt = Kopplungsschritt.Bereit
                        return@LaunchedEffect
                    }

                    is Sitzungsstand.Abgewiesen -> {
                        /* KEIN NETZ IST KEIN ABBRUCH. Die Sitzung lebt auf dem
                         * Server weiter, bis die Frist abläuft (Vertrag 1a.2);
                         * wer hier abbräche, würfe eine gültige Kopplung weg,
                         * weil ein Funkloch drei Sekunden gedauert hat. */
                        if (stand.art != Abweisung.KEINE_VERBINDUNG) {
                            schritt = Kopplungsschritt.Abgewiesen(stand.art, stand.meldung)
                            return@LaunchedEffect
                        }
                    }
                }
            }
        }

        KopplungAnsicht(
            schritt = schritt,
            logoWahl = app.einstellungen.logoWahl,
            aufStarten = {
                schritt = Kopplungsschritt.Startet
                hilfsfaden.launch {
                    when (val e = withContext(Dispatchers.IO) { dienst.starten(geraet) }) {
                        is Sitzungsergebnis.Offen ->
                            schritt = Kopplungsschritt.Wartet(e.code, e.fristSekunden)

                        is Sitzungsergebnis.Abgewiesen ->
                            schritt = Kopplungsschritt.Abgewiesen(e.art, e.meldung)
                    }
                }
            },
            aufAntwort = { ja ->
                schritt = if (ja) Kopplungsschritt.Bestaetigt else Kopplungsschritt.Abgebrochen
                hilfsfaden.launch {
                    when (val e = withContext(Dispatchers.IO) { dienst.bestaetigen(ja) }) {
                        is Bestaetigungsergebnis.Gekoppelt -> {
                            gekoppelt = true
                            schritt = Kopplungsschritt.Bereit
                        }
                        is Bestaetigungsergebnis.Abgebrochen ->
                            schritt = Kopplungsschritt.Abgebrochen

                        is Bestaetigungsergebnis.Abgewiesen ->
                            schritt = Kopplungsschritt.Abgewiesen(e.art, e.meldung)
                    }
                }
            },
        )
        return
    }

    GekoppelteOberflaeche(
        app = app,
        trennmeldung = trennmeldung,
        trennfrageOffen = trennfrageOffen,
        aufTrennfrage = { trennfrageOffen = it },
        aufTrennen = {
            trennfrageOffen = false
            hilfsfaden.launch {
                val e = withContext(Dispatchers.IO) { dienst.trennen() }
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
    var abschlussFrageOffen by remember { mutableStateOf(false) }
    var akkuFrageOffen by remember { mutableStateOf(false) }
    var verbrauchFrageOffen by remember { mutableStateOf(false) }

    /* AUCH DIE FREIGABE WIRD ABGEFRAGT, NICHT GEHALTEN (B-S5Z-17).
     *
     * `remember { … }` wertet einmal aus. Bis 0.10.1 war das der einzige
     * Lesevorgang ausser dem Rückruf der Berechtigungsfrage — und damit sah
     * die Ansicht eine Freigabe nicht, die anderswo erteilt wurde. Der Weg,
     * auf dem das im Betrieb passiert, ist ausgerechnet der wichtigste: Wer
     * einmal „Nicht mehr fragen" gewählt hat, bekommt von `freigabeFrage`
     * keinen Dialog mehr; für sie oder ihn führt der einzige Weg über die
     * Android-Einstellungen. Zurück in der App stand dann weiter
     * „Ortung nicht freigegeben" — die App sagte jemandem, der das Problem
     * gerade behoben hatte, es bestehe fort. Erst ein vollständiger Neustart
     * räumte die Meldung weg.
     *
     * Gefunden am 03.09.2026 im Emulator, nicht durch Lesen: Der Abzug nach
     * `Home` und Rückkehr war byteweise gleich dem davor (md5 08c9a62a…),
     * der nach `force-stop` und Neustart war es nicht.
     *
     * Der Takt unten fragt ohnehin jede Sekunde den Puffer ab, mit genau
     * dieser Begründung. Die Freigabe hängt sich daran; `checkSelfPermission`
     * für das eigene Paket ist ein billiger Aufruf. Der Rückruf setzt den
     * Wert weiterhin selbst — er ist sofort da, statt bis zum nächsten Takt
     * zu warten, und er hängt die Akkufrage daran. */
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

    /* Siehe die Begründung oben bei `ortungFrei` (B-S5Z-17). */
    LaunchedEffect(takt) {
        ortungFrei =
            ContextCompat.checkSelfPermission(kontext, Manifest.permission.ACCESS_FINE_LOCATION) ==
                PackageManager.PERMISSION_GRANTED
    }

    val rueckstand = remember(takt) { app.puffer.rueckstand() }
    val abgewiesen = remember(takt) { app.puffer.abgewiesen() }
    val sendelaeuft = remember(takt) { app.sendelaufLaeuft }
    val sendeergebnis = remember(takt) { sendeergebnis(app.letzterSendebericht) }

    val stand = remember(takt, modus, ortungFrei) {
        val laufend = app.klammer.laufenderDienst()
        /* WER DEN ORTUNGSZUSTAND KENNT, HÄNGT DAVON AB, OB EIN DIENST LÄUFT
         * (E-S5Z-01). Läuft einer, ist der Vordergrunddienst die eine Quelle
         * — er misst, die Ansicht liest. Läuft keiner, gibt es nichts zu
         * lesen; dann leitet die Ansicht selbst ab, was sie für die beiden
         * Sperren braucht. Der Sekundentakt oben holt es von selbst nach,
         * sobald jemand aus den Systemeinstellungen zurückkommt. */
        val lage = app.ortung
        val offenesPaket = laufend?.let {
            app.puffer.offenesPaket(org.genem.nadoku.handy.puffer.Paketzeile.ART_EINSATZ)
                ?: app.puffer.offenesPaket(org.genem.nadoku.handy.puffer.Paketzeile.ART_RUHESEGMENT)
        }
        val einsatz = app.puffer.offenesPaket(org.genem.nadoku.handy.puffer.Paketzeile.ART_EINSATZ)
        val phasen = einsatz?.let { app.puffer.phasen(it.id) }.orEmpty()
        Dienststand(
            laeuft = laufend != null,
            begonnenHhmm = laufend?.let { Zeit.hhmm(Instant.parse(it.begonnenAt)) },
            modus = if (laufend != null) app.klammer.modus() else modus,
            punkte = offenesPaket?.let { app.puffer.punktzahl(it.id) } ?: 0L,
            streckeKm = "%.1f".format(app.klammer.streckeM() / 1000.0),
            ortungFreigegeben = ortungFrei,
            standortAn = standortAn(kontext),
            ortung = lage?.stand,
            ortungSeitMin = lage
                ?.let { ((SystemClock.elapsedRealtime() - it.seitMs) / 60_000L).toInt() }
                ?.coerceAtLeast(1) ?: 0,
            einsatzLaeuft = einsatz != null,
            laufendePhase = app.klammer.laufendePhase(),
            phaseSeit = phasen.lastOrNull()?.let { Zeit.hhmm(Instant.parse(it.at)) },
            naechstePhase = app.klammer.naechstePhase(),
            gesetztePhasen = phasen.map {
                Phasenzeile(it.nummer, Zeit.hhmm(Instant.parse(it.at)))
            },
        )
    }

    val freigabeFrage = rememberLauncherForActivityResult(
        ActivityResultContracts.RequestMultiplePermissions()
    ) { ergebnis ->
        ortungFrei = ergebnis[Manifest.permission.ACCESS_FINE_LOCATION] == true
        /* GEFRAGT WIRD NUR, WENN ES ETWAS ZU TUN GIBT. Steht die Freistellung
         * schon — etwa weil das Gerät sie von sich aus gewährt oder die
         * NutzerIn sie früher gesetzt hat —, wäre der Hinweis eine Frage nach
         * etwas Erledigtem. Das Lesen des Zustands braucht keine Berechtigung
         * (E-S4-52). */
        if (ortungFrei && !app.einstellungen.akkuHinweisGezeigt && !akkuFreigestellt(kontext)) {
            akkuFrageOffen = true
        }
    }

    if (verbrauchFrageOffen) {
        Verbrauchhinweis {
            verbrauchFrageOffen = false
            app.einstellungen.verbrauchHinweisGezeigt = true
        }
    }

    if (abschlussFrageOffen) {
        Rueckfrage(
            titel = stringResource(R.string.einsatz_abschliessen_frage),
            text = stringResource(R.string.einsatz_abschliessen_text),
            ja = stringResource(R.string.einsatz_abschliessen_ja),
            aufJa = {
                abschlussFrageOffen = false
                app.klammer.einsatzAbschliessen()
                // Einsatzabschluss ist ein Auslöser (E-S4-07).
                sendeImHintergrund(app)
                takt++
            },
            aufNein = { abschlussFrageOffen = false },
        )
    }
    if (beendenFrageOffen) {
        Rueckfrage(
            titel = stringResource(R.string.dienst_beenden_frage),
            text = stringResource(R.string.dienst_beenden_text),
            ja = stringResource(R.string.dienst_beenden_ja),
            aufJa = {
                beendenFrageOffen = false
                app.klammer.beenden()
                AufzeichnungsDienst.beenden(kontext)   // beendet und sendet
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
            aufDatenschutz = { rechtstextOeffnen(kontext, Serveradresse.datenschutz(Serveradresse.BASIS)) },
            aufImpressum = { rechtstextOeffnen(kontext, Serveradresse.impressum(Serveradresse.BASIS)) },
            aufZurueck = { ansicht = Ansicht.Dienst },
        )

        is Ansicht.Dienst -> DienstAnsicht(
            stand = stand,
            logoWahl = logoWahl,
            rueckstand = rueckstand,
            abgewiesen = abgewiesen,
            sendeergebnis = sendeergebnis,
            sendelaufLaeuft = sendelaeuft,
            aufJetztSenden = {
                sendeImHintergrund(app)
                takt++
            },
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
                    /* DER VERBRAUCHSHINWEIS, EINMAL JE INSTALLATION
                     * (Backlog Nr. 82).
                     *
                     * NACH dem Beginnen und nicht davor: Ein Dialog, der den
                     * Start aufhaelt, steht im Weg, wenn es losgeht. Der
                     * Dienst laeuft bereits, wenn der Hinweis erscheint — er
                     * ist eine Auskunft, keine Rueckfrage.
                     *
                     * NUR WENN DER AKKU-DIALOG NICHT OHNEHIN KOMMT: Zwei
                     * Kaesten uebereinander im selben Augenblick liest
                     * niemand, und der Akku-Dialog traegt denselben Satz seit
                     * diesem Paket in seinem zweiten Absatz. */
                    if (!akkuFrageOffen && !app.einstellungen.verbrauchHinweisGezeigt) {
                        verbrauchFrageOffen = true
                    }
                    takt++
                }
            },
            aufBeenden = { beendenFrageOffen = true },
            aufOrtungFreigeben = { freigabeFrage.launch(noetigeFreigaben()) },
            aufStandortEinschalten = { standortEinstellungOeffnen(kontext) },
            aufEinstellungen = { ansicht = Ansicht.Einstellungen },
            aufPhase = { nummer ->
                app.klammer.phaseSetzen(nummer)
                // Phasenwechsel ist ein Auslöser (E-S4-07).
                sendeImHintergrund(app)
                takt++
            },
            aufEinsatzAbschluss = { abschlussFrageOffen = true },
        )
    }
}

/**
 * Einen Sendelauf anstoßen, ohne auf ihn zu warten.
 *
 * Die Ereignisauslöser aus E-S4-07 — Phasenwechsel und Einsatzabschluss —
 * kommen aus der Oberfläche; der Takt selbst läuft im Vordergrunddienst.
 * Gewartet wird nicht: Ein Bedienschritt darf nicht am Netz hängen.
 */
private fun sendeImHintergrund(app: NAdokuApp) {
    /* ÜBER DEN SENDEAUSFÜHRER und nicht mehr über einen eigenen `Thread`
     * (E-S5Z-11): Ein Phasenwechsel während eines laufenden Takt-Laufs
     * erzeugte sonst zwei Läufe auf demselben Puffer (B-S5Z-11). */
    app.sendelauf()
}

/**
 * Den Bericht des letzten Laufs in das übersetzen, was die Ansicht zeigt
 * (E-S5Z-12).
 *
 * DIE REGEL STEHT HIER UND NICHT IN DER ANSICHT: Eine Compose-Funktion, die
 * entscheidet, was „Keine Verbindung" heißt, ist weder prüfbar noch
 * wiederfindbar. Die Reihenfolge ist die der Dringlichkeit — ein abgewiesener
 * Schlüssel wiegt schwerer als ein abgewiesenes Paket, und beides schwerer
 * als ein fehlendes Netz, das sich von selbst erledigt.
 */
private fun sendeergebnis(lauf: Sendelauf?): Sendeergebnis? {
    val l = lauf ?: return null
    val hhmm = Zeit.hhmm(l.am)
    return when {
        l.bericht.pausiert -> Sendeergebnis(Sendeausgang.SCHLUESSEL_ABGEWIESEN, hhmm)
        l.bericht.fehlerhaft > 0 ->
            Sendeergebnis(Sendeausgang.PAKET_ABGEWIESEN, hhmm, l.bericht.fehlerhaft)
        l.bericht.spaeterErneut -> Sendeergebnis(Sendeausgang.KEIN_NETZ, hhmm)
        else -> Sendeergebnis(Sendeausgang.GESENDET, hhmm)
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
 * Die Führung zur Akku-Freistellung (E-S4-05) — **auf dem Weg, den der Play
 * Store zulässt** (E-S4-52).
 *
 * SIE FÜHRT HIN UND ERZWINGT NICHTS. Geöffnet wird die **allgemeine Liste**
 * der Akku-Ausnahmen; dort sucht die NutzerIn NAdoku selbst heraus. Der
 * gezielte Dialog (`ACTION_REQUEST_IGNORE_BATTERY_OPTIMIZATIONS`) wäre ein
 * Schritt weniger, verlangt aber eine Berechtigung, die gegen die
 * Inhaltsrichtlinie des Store verstößt (Fund B-S4-04). Ein Schritt mehr ist
 * der kleinere Preis als eine App, die nicht verteilt werden darf.
 *
 * Ob die Freistellung hält, zeigt ohnehin nur das Gerät — namentlich Samsungs
 * „Apps im Tiefschlaf" ist ein zweiter Schalter an ganz anderer Stelle, den
 * keine App erreicht. Die App fragt deshalb **einmal**, merkt sich das und
 * drängt nicht wieder.
 */
/**
 * Ist der **GPS-Anbieter** eingeschaltet? (E-S5Z-03)
 *
 * ABSICHTLICH DER ANBIETER und nicht `isLocationEnabled()`: Im Modus
 * „Stromsparen" ist der Standort an und GPS aus — aufgezeichnet wird aber nur
 * mit GPS. Ein Dienst, der unter dieser Auskunft begänne, zeichnete nichts
 * auf und sagte es nicht.
 *
 * Das Lesen braucht keine Berechtigung; die Freigabe wird getrennt geprüft.
 */
private fun standortAn(kontext: Context): Boolean = try {
    (kontext.getSystemService(Context.LOCATION_SERVICE) as LocationManager)
        .isProviderEnabled(LocationManager.GPS_PROVIDER)
} catch (e: SecurityException) {
    false
} catch (e: IllegalArgumentException) {
    // Kein GPS-Anbieter auf diesem Gerät — dann gibt es nichts einzuschalten.
    false
}

/**
 * Die Systemeinstellung für den Standort öffnen (E-S5Z-03).
 *
 * Die App kann den Standort nicht selbst einschalten, und das ist richtig so:
 * Es ist eine Systemeinstellung mit Wirkung auf jede App des Geräts. Sie
 * führt deshalb dorthin und liest nach der Rückkehr im Sekundentakt neu — der
 * Meldungsblock verschwindet von selbst.
 */
private fun standortEinstellungOeffnen(kontext: Context) {
    try {
        kontext.startActivity(
            Intent(Settings.ACTION_LOCATION_SOURCE_SETTINGS)
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        )
    } catch (e: android.content.ActivityNotFoundException) {
        /* Diese Seite gibt es auf jedem Android; fehlt sie doch, bleibt die
         * allgemeine Einstellungsliste. Ohne diesen Fang stürzte die App an
         * einer Stelle ab, an der sie gerade erklärt, was zu tun ist. */
        kontext.startActivity(
            Intent(Settings.ACTION_SETTINGS).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        )
    }
}

/**
 * Einen Rechtstext im Browser oeffnen (seit 0.13.0).
 *
 * DIESELBE BAUART WIE DIE BEIDEN EINSTELLUNGSWEGE DARUNTER: Die App oeffnet
 * eine Absicht und faengt den Fall ab, dass niemand sie annimmt. Der
 * Unterschied ist der Rueckfall — bei den Systemeinstellungen gibt es einen
 * zweiten Weg, hier nicht: Ohne Browser gibt es keinen. Statt abzustuerzen
 * sagt die App es.
 *
 * KEIN WEBVIEW. Ein eingebauter Betrachter waere die einzige Stelle, an der
 * fremdes Markup im Prozess dieser App liefe — fuer zwei Seiten, die im
 * Browser genauso gut stehen.
 */
private fun rechtstextOeffnen(kontext: Context, adresse: String) {
    try {
        kontext.startActivity(
            Intent(Intent.ACTION_VIEW, adresse.toUri())
                .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        )
    } catch (e: android.content.ActivityNotFoundException) {
        Toast.makeText(kontext, R.string.recht_kein_browser, Toast.LENGTH_LONG).show()
    }
}

private fun akkuEinstellungOeffnen(kontext: Context) {
    /* EIN EINZIGER WEG, KEIN RÜCKFALL. Die allgemeine Liste gibt es auf jedem
     * Android ab 6.0; der Rückfall, den die frühere Fassung für fehlende
     * Hersteller-Dialoge brauchte, ist damit gegenstandslos. */
    val absicht = Intent(Settings.ACTION_IGNORE_BATTERY_OPTIMIZATION_SETTINGS)
        .addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
    try {
        kontext.startActivity(absicht)
    } catch (e: android.content.ActivityNotFoundException) {
        /* Gibt es auch die allgemeine Liste nicht, bleibt nur die App-Seite.
         * Von dort führt jedes Android zur Akkueinstellung. */
        kontext.startActivity(
            Intent(
                Settings.ACTION_APPLICATION_DETAILS_SETTINGS,
                "package:${kontext.packageName}".toUri(),
            ).addFlags(Intent.FLAG_ACTIVITY_NEW_TASK)
        )
    }
}

/**
 * Steht die Freistellung? **Lesen braucht keine Berechtigung** — nur das
 * Anfordern über den gezielten Dialog täte es, und den gibt es hier nicht.
 */
private fun akkuFreigestellt(kontext: Context): Boolean =
    (kontext.getSystemService(Context.POWER_SERVICE) as PowerManager)
        .isIgnoringBatteryOptimizations(kontext.packageName)

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
internal fun Verbrauchhinweis(aufVerstanden: () -> Unit) {
    /* EIN KNOPF, KEINE WAHL. Es gibt nichts zu entscheiden — die Aufzeichnung
     * laeuft bereits, und abschalten laesst sich der Stromverbrauch nicht,
     * ohne den Dienst zu beenden. Ein zweiter Knopf ("Spaeter") taeuschte eine
     * Wahl vor, die es nicht gibt.
     *
     * `onDismissRequest` setzt denselben Merker: Wer danebentippt, hat den
     * Text gesehen; ihn beim naechsten Dienst noch einmal zu zeigen, machte
     * aus dem einmaligen Hinweis eine Dauerwarnung (Backlog Nr. 82). */
    AlertDialog(
        onDismissRequest = aufVerstanden,
        title = { Text(stringResource(R.string.verbrauch_titel), color = Farbe.dunkelblau) },
        text = { Text(stringResource(R.string.verbrauch_hinweis), color = Farbe.asphalt) },
        confirmButton = {
            TextButton(onClick = aufVerstanden) {
                Text(
                    stringResource(R.string.verbrauch_verstanden),
                    color = Farbe.blauTief, fontWeight = FontWeight.SemiBold,
                )
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
