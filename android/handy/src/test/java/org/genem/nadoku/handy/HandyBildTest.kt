package org.genem.nadoku.handy

import android.graphics.Bitmap
import android.graphics.Canvas
import android.os.Looper
import android.view.View
import androidx.activity.ComponentActivity
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.ComposeView
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.gemeinsam.Phasen
import org.genem.nadoku.handy.aufzeichnung.Ortungsstand
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.Robolectric
import org.robolectric.RobolectricTestRunner
import org.robolectric.RuntimeEnvironment
import org.robolectric.Shadows.shadowOf
import org.robolectric.annotation.Config
import org.robolectric.annotation.GraphicsMode
import java.io.File
import java.security.MessageDigest

/**
 * Bilder der **Handy**-Oberfläche, ohne Emulator und ohne Gerät.
 *
 * WARUM ES DIESEN FALL GIBT. Für die Uhr gab es seit C1 einen Bilderlauf
 * (`UhrBildTest`), für das Handy nicht — Oberflächenänderungen am Telefon
 * waren damit **gelesen und nicht gesehen**. Das fiel in E1 auf, als zwei
 * neue Bildschirmzustände entstanden (Sperre „Standort ausgeschaltet",
 * Zustandszeile ohne Signal) und es keinen Weg gab, sie anzusehen.
 *
 * DER WEG IST DERSELBE WIE BEI DER UHR (E-S4-49) und aus demselben Grund:
 * `captureToImage()` aus `compose-ui-test` hängt sich unter Robolectric auf —
 * es wartet in einer `Thread.sleep`-Schleife auf genau den Faden, der
 * zeichnen müsste, und Robolectric hat nur einen. Gemessen, angeordnet und
 * gezeichnet wird deshalb selbst; `ComposeView` steckt in
 * `androidx.compose.ui`, das die App ohnehin einbindet. **Null neue
 * Abhängigkeiten.**
 *
 * WAS DIESER FALL MISST — und das ist der Punkt, nicht das Bild:
 *
 * | Gemessen | Zusage | Woher sie kommt |
 * |---|---|---|
 * | Höhe der farbigen Knopfflächen | **48 dp** | R58 / `BEDIENHOEHE` — die App wird mit Handschuhen bedient |
 * | Ein Knopf liegt **im sichtbaren Bereich** | ohne Bildlauf bedienbar | ein Bildschirm, dessen Handlung unter dem Rand liegt, ist keiner |
 * | Kein Knopf **berührt den Bildrand** | er ist nicht beschnitten | die Karte hat Rand; wer ihn erreicht, ist zu breit |
 * | Bilder sind **paarweise verschieden** | jedes zeigt etwas anderes | F-P3-AQ |
 *
 * **WAS DIESER FALL AUSDRÜCKLICH NICHT MISST: waagerechten Überlauf im Sinn
 * des Web-Bilderlaufs.** Die erste Fassung meldete ihn — und die Zahl war
 * wertlos: Jeder Bildschirm der App ruft `fillMaxSize()`, also gewinnt die
 * Einschränkung immer, und ein zu breites Kind wird von Compose **beschnitten
 * statt gemeldet**. „Verlangte Breite = Gerätebreite" stand deshalb in jeder
 * der 48 Zeilen, gleich was darin stand. Was an ihre Stelle getreten ist,
 * sind die zwei Zeilen darüber: Sie messen die Folge des Beschnitts, wo sie
 * jemanden trifft — an den Bedienelementen.
 *
 * **F-P3-AQ IST DER GRUND FÜR DIE DRITTE ZEILE.** Der Bilderlauf des Web
 * meldete nach O9c „248 Bilder, 0 Überlauf" — 176 davon zeigten die
 * Anmeldeseite. Eine grüne Zahl über 248 Bilder, von denen zwei Drittel
 * dasselbe zeigen, ist kein Beleg, sondern eine Beruhigung. Dieser Fall
 * vergleicht deshalb die Prüfsummen aller erzeugten PNG und besteht darauf,
 * dass keine zweimal vorkommt.
 *
 * GRENZEN, und sie sind erheblich:
 *
 * - Gemessen wird die **Anordnung**, nicht das Aussehen auf einem Gerät.
 *   Schriftrasterung, Herstelleraufsätze und die Systemleisten fehlen.
 * - Die Bedienhöhe wird an den **farbigen** Knöpfen gemessen (Orange, Rot).
 *   `KnopfNeutral` trägt Schneefläche mit Rand und ist im Bild nicht sicher
 *   von der Karte zu trennen; er benutzt dieselbe `Knopfflaeche` und
 *   dieselbe `BEDIENHOEHE`, ist hier aber **nicht belegt**.
 * - Kein Bedienzustand: kein Tippen, kein Bildlauf, keine Tastatur.
 */
@RunWith(RobolectricTestRunner::class)
@GraphicsMode(GraphicsMode.Mode.NATIVE)
@Config(qualifiers = "w360dp-h800dp-xhdpi")
class HandyBildTest {

    /**
     * Die Flächenfarben, an denen ein Bedienelement im Bild zu erkennen ist:
     * Orange handelt, Rot beendet (E-S4-22a).
     */
    private val knopffarben = setOf(0xFFFF8F1F.toInt(), 0xFFD63338.toInt())

    /** Die zugesagte Bedienhöhe (R58). Toleranz 1 dp für die Rasterung. */
    private val sollDp = 48.0

    /**
     * Die geprüften Breiten in dp.
     *
     * 360 ist das schmalste verbreitete Telefon und der Fall, in dem etwas
     * bricht; 411 ist das S24 des Auftraggebers; 600 ist die Schwelle, ab der
     * Android von „Telefon" auf „Tablet" umschaltet — die App hat dafür kein
     * eigenes Bild, und genau deshalb gehört die Breite gemessen.
     */
    private val breiten = listOf(360, 411, 600)

    /** Die Bildschirmhöhe, auf der gezeichnet wird — was das Telefon zeigt. */
    private val hoeheDp = 800

    private data class Messung(
        val name: String,
        val breiteDp: Int,
        val knopfDp: Double,
        /** Unterkante des untersten farbigen Knopfes, in dp ab oben. */
        val knopfUnterkanteDp: Int,
        /** Berührt ein Knopf die linke oder rechte Bildkante? */
        val amRand: Boolean,
        /**
         * Wird der unterste Knopf von der **Faltkante** abgeschnitten?
         *
         * Dann ist seine gemessene Höhe kleiner als seine wirkliche, und die
         * Bedienhöhe wäre an ihm nicht zu prüfen — das ist eine Aussage über
         * die Bildschirmlänge, nicht über den Knopf.
         */
        val unterDerFaltkante: Boolean,
        /**
         * Wie hoch der **ganze** Inhalt ist, gemessen ohne Faltkante.
         *
         * DIESE ZAHL FEHLTE, und ihr Fehlen war ein Loch im Prüfmittel:
         * [unterDerFaltkante] findet nur Knöpfe, die **angeschnitten** sind.
         * Einer, der vollständig unter dem Rand liegt, war unsichtbar — und
         * der Lauf meldete „kein Knopf unter der Faltkante", während einer
         * 80 dp darunter lag. Genau die grüne Zahl, die nichts misst.
         */
        val inhaltDp: Int,
        /** Farbige Knöpfe im ganzen Inhalt … */
        val knoepfeGesamt: Int,
        /** … und davon im sichtbaren Bereich vollständig. */
        val knoepfeSichtbar: Int,
        val farben: Int,
        val pruefsumme: String,
    )

    // ---- Die Bildschirme ---------------------------------------------------

    private fun dienst(
        laeuft: Boolean = false,
        ortungFreigegeben: Boolean = true,
        standortAn: Boolean = true,
        ortung: Ortungsstand? = null,
        ortungSeitMin: Int = 0,
        modus: Modus = Modus.MIT_PHASENKNOEPFEN,
        rueckstand: Int = 0,
        abgewiesen: Int = 0,
        sendeergebnis: Sendeergebnis? = null,
        sendelaufLaeuft: Boolean = false,
        einsatzLaeuft: Boolean = false,
        laufendePhase: Int = Phasen.FREI,
        gesetztePhasen: List<Phasenzeile> = emptyList(),
    ): @Composable () -> Unit = {
        DienstAnsicht(
            stand = Dienststand(
                laeuft = laeuft,
                begonnenHhmm = if (laeuft) "07:02" else null,
                modus = modus,
                punkte = if (laeuft) 1483 else 0,
                streckeKm = if (laeuft) "126,4" else "0,0",
                ortungFreigegeben = ortungFreigegeben,
                standortAn = standortAn,
                ortung = ortung,
                ortungSeitMin = ortungSeitMin,
                einsatzLaeuft = einsatzLaeuft,
                laufendePhase = laufendePhase,
                phaseSeit = if (einsatzLaeuft) "09:12" else null,
                /* Abgeleitet wie in der App (`klammer.naechstePhase()`) und
                 * nicht als `laufendePhase + 1`: Nach der letzten Phase gibt
                 * es keine nächste, und Phase 10 wirft. */
                naechstePhase = when {
                    !einsatzLaeuft -> Phasen.ERSTE
                    gesetztePhasen.isNotEmpty() ->
                        (gesetztePhasen.maxOf { it.nummer } + 1).takeIf { it <= Phasen.LETZTE }
                    else -> laufendePhase + 1
                },
                gesetztePhasen = gesetztePhasen,
            ),
            serverBasis = "https://einsatz.beispieldomain.de/",
            logoWahl = LogoWahl.LUFT,
            rueckstand = rueckstand,
            abgewiesen = abgewiesen,
            sendeergebnis = sendeergebnis,
            sendelaufLaeuft = sendelaufLaeuft,
            aufJetztSenden = {},
            aufModus = {}, aufBeginnen = {}, aufBeenden = {},
            aufOrtungFreigeben = {}, aufStandortEinschalten = {}, aufEinstellungen = {},
        )
    }

    /**
     * Die Liste ist die Prüfung: Was hier nicht steht, wird nicht gesehen.
     *
     * Sie deckt jeden Zustand ab, der die Oberfläche sichtbar ändert — die
     * beiden Sperren vor dem Dienst, jeden der sechs Ortungszustände im
     * Dienst, beide Betriebsarten und die Nebenbildschirme.
     */
    private fun bildschirme(): List<Pair<String, @Composable () -> Unit>> = listOf(
        // -- Vor dem Dienst --
        "dienst-ruhend" to dienst(),
        "dienst-ruhend-nur-aufzeichnen" to dienst(modus = Modus.NUR_AUFZEICHNEN),
        "sperre-freigabe-fehlt" to dienst(ortungFreigegeben = false),
        "sperre-standort-aus" to dienst(standortAn = false),
        "dienst-ruhend-rueckstand" to dienst(rueckstand = 2),

        // -- Im Dienst, je Ortungszustand (E-S5Z-01) --
        "laufend-ok" to dienst(laeuft = true, ortung = Ortungsstand.OK),
        "laufend-sucht" to dienst(laeuft = true, ortung = Ortungsstand.SUCHT),
        "laufend-kein-signal" to
            dienst(laeuft = true, ortung = Ortungsstand.KEIN_SIGNAL, ortungSeitMin = 3),
        "laufend-ungenau" to dienst(laeuft = true, ortung = Ortungsstand.UNGENAU),
        "laufend-standort-aus" to
            dienst(laeuft = true, standortAn = false, ortung = Ortungsstand.STANDORT_AUS),
        "laufend-freigabe-fehlt" to dienst(
            laeuft = true, ortungFreigegeben = false, ortung = Ortungsstand.FREIGABE_FEHLT,
        ),

        // -- Im Dienst, mit Einsatz und ohne Phasenknoepfe --
        "laufend-einsatz-phase" to dienst(
            laeuft = true, ortung = Ortungsstand.OK,
            einsatzLaeuft = true, laufendePhase = Phasen.ERSTE,
        ),
        "laufend-nur-aufzeichnen" to dienst(
            laeuft = true, ortung = Ortungsstand.OK, modus = Modus.NUR_AUFZEICHNEN,
        ),

        /* SPÄT IM EINSATZ — der Fall, an dem B-S5Z-16 hing. Mit allen acht
         * gesetzten Phasen ist die Liste am längsten; wenn „Einsatz
         * abschliessen" irgendwo unter die Faltkante rutscht, dann hier. */
        "laufend-einsatz-spaet" to dienst(
            laeuft = true, ortung = Ortungsstand.OK, einsatzLaeuft = true,
            laufendePhase = Phasen.LETZTE,
            gesetztePhasen = Phasen.UEBERTRAGEN.mapIndexed { i, n ->
                Phasenzeile(n, "09:%02d".format(12 + i * 7))
            },
        ),

        // -- Was das Senden anzeigt (E2, E-S5Z-12) --
        "sende-abgewiesen" to dienst(abgewiesen = 1),
        "sende-laeuft" to dienst(rueckstand = 2, sendelaufLaeuft = true),
        "sende-kein-netz" to dienst(
            rueckstand = 2,
            sendeergebnis = Sendeergebnis(Sendeausgang.KEIN_NETZ, "12:41"),
        ),
        "sende-schluessel-weg" to dienst(
            rueckstand = 2,
            sendeergebnis = Sendeergebnis(Sendeausgang.SCHLUESSEL_ABGEWIESEN, "12:41"),
        ),
        "sende-gesendet" to dienst(
            sendeergebnis = Sendeergebnis(Sendeausgang.GESENDET, "12:41"),
        ),

        // -- Nebenbildschirme --
        "kopplung-wahl" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.Wahl,
                serverBasis = null,
                logoWahl = LogoWahl.LUFT,
                aufSchritt = {}, aufKoppeln = { _, _ -> },
            )
        },
        "kopplung-von-hand" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.VonHand,
                serverBasis = "https://einsatz.beispieldomain.de/",
                logoWahl = LogoWahl.LUFT,
                aufSchritt = {}, aufKoppeln = { _, _ -> },
            )
        },
        "einstellungen" to {
            EinstellungenAnsicht(
                logoWahl = LogoWahl.BODEN,
                uhrSperre = true,
                dienstLaeuft = false,
                trennmeldung = null,
                aufLogoWahl = {}, aufUhrSperre = {}, aufTrennen = {}, aufZurueck = {},
            )
        },
    )

    // ---- Der Lauf ----------------------------------------------------------

    /**
     * **Der Bilderlauf selbst.**
     *
     * Ein Prüffall und nicht sechzehn, weil die Zusicherung, die am meisten
     * wert ist, über *alle* Bilder geht: dass keines einem anderen gleicht.
     * Die Zahlen je Bild stehen trotzdem einzeln in der Ausgabe — sie sind
     * der Beleg, und ein Beleg ohne Zahl ist keiner.
     */
    @Test
    fun alleBildschirmeInDreiBreiten() {
        val messungen = mutableListOf<Messung>()

        for (breiteDp in breiten) {
            RuntimeEnvironment.setQualifiers("w${breiteDp}dp-h800dp-xhdpi")
            for ((name, inhalt) in bildschirme()) {
                messungen += male("$name-${breiteDp}dp", breiteDp, inhalt)
            }
        }

        println("BILDERLAUF HANDY — ${messungen.size} Bilder, je ${hoeheDp} dp sichtbarer Bereich")
        println(
            "%-38s %7s %9s %11s %8s %9s %6s".format(
                "Bild", "Breite", "Knopf", "Unterkante", "Inhalt", "Knöpfe", "Rand",
            )
        )
        for (m in messungen) {
            println(
                "%-38s %5d dp %6.1f dp %8d dp %5d dp %4d/%-4d %6s".format(
                    m.name, m.breiteDp, m.knopfDp, m.knopfUnterkanteDp, m.inhaltDp,
                    m.knoepfeSichtbar, m.knoepfeGesamt, if (m.amRand) "JA" else "-",
                )
            )
        }

        // ---- 1. Jedes Bild zeigt etwas -------------------------------------
        val leer = messungen.filter { it.farben <= 1 }
        check(leer.isEmpty()) {
            "Einfarbig, also nichts gezeichnet: ${leer.joinToString { it.name }}"
        }

        // ---- 2. Auf jedem Bildschirm ist eine Handlung erreichbar ----------
        /* Ohne Bildlauf. Ein Bildschirm, dessen Knopf unter dem Rand liegt,
         * ist auf einem 800-dp-Telefon nicht bedienbar — und das faellt beim
         * Lesen des Quelltexts niemandem auf. */
        val ohneKnopf = messungen.filter { it.knopfDp == 0.0 }
        check(ohneKnopf.isEmpty()) {
            "Kein farbiger Knopf im sichtbaren Bereich (${hoeheDp} dp): " +
                ohneKnopf.joinToString { it.name }
        }

        // ---- 3. Kein Knopf beruehrt den Bildrand ---------------------------
        val randberuehrer = messungen.filter { it.amRand }
        check(randberuehrer.isEmpty()) {
            "Knopffarbe an der Bildkante — zu breit oder beschnitten: " +
                randberuehrer.joinToString { it.name }
        }

        // ---- 4. Die Bedienhöhe hält ----------------------------------------
        /* Nur dort geprüft, wo ein farbiger Knopf im Bild ist. Ein Bildschirm
         * ohne solchen (die Sperre zeigt nur den Meldungsblock, bis der Knopf
         * darunter kommt) hat hier nichts auszusagen — er bekäme sonst eine
         * Zusicherung über etwas, das er nicht enthält. */
        /* ABGESCHNITTENE KNÖPFE ZÄHLEN HIER NICHT MIT, und das ist keine
         * Nachsicht: Ihre gemessene Höhe ist die des sichtbaren Rests, nicht
         * die des Knopfes. Sie an der Bedienhöhe zu messen hiesse, eine
         * Aussage über die Bildschirmlänge als Aussage über den Knopf zu
         * verkaufen. Sie stehen stattdessen in ihrer eigenen Zeile unten. */
        val zuFlach = messungen.filter { !it.unterDerFaltkante && it.knopfDp < sollDp - 1.0 }
        check(zuFlach.isEmpty()) {
            "Bedienhöhe unter ${sollDp.toInt()} dp (R58): " + zuFlach.joinToString {
                "%s %.1f dp".format(it.name, it.knopfDp)
            }
        }
        val ganz = messungen.filter { !it.unterDerFaltkante }
        println(
            "Bedienhöhe %.1f dp bis %.1f dp an %d von %d Bildern (Soll %d dp)."
                .format(ganz.minOf { it.knopfDp }, ganz.maxOf { it.knopfDp },
                        ganz.size, messungen.size, sollDp.toInt())
        )
        println(
            "Unterste Knopfkante: %d dp bis %d dp von %d dp sichtbar."
                .format(ganz.minOf { it.knopfUnterkanteDp }, ganz.maxOf { it.knopfUnterkanteDp },
                        hoeheDp)
        )

        // ---- 5. Was unter der Faltkante liegt, wird BENANNT ----------------
        /* Nicht als Fehler: Der Bildschirm rollt (`verticalScroll`), der Knopf
         * ist erreichbar. Aber er ist ohne Schieben NICHT DA, und das gehört
         * gesagt statt verschwiegen — auf einem Gerät im Einsatz, mit
         * Handschuhen, ist Schieben ein Bedienschritt mehr. */
        /* GEZÄHLT WIRD JETZT ÜBER DEN GANZEN INHALT, nicht nur über das Bild.
         * Ein Knopf, der VOLLSTÄNDIG unter dem Rand liegt, war für die erste
         * Fassung unsichtbar — sie fand nur angeschnittene, und der Lauf
         * meldete „kein Knopf unter der Faltkante", während einer 80 dp
         * darunter lag. */
        val unerreichbar = messungen.filter { it.knoepfeSichtbar < it.knoepfeGesamt }
        if (unerreichbar.isEmpty()) {
            println("Alle Knöpfe liegen im sichtbaren Bereich (${hoeheDp} dp).")
        } else {
            println(
                "NUR MIT BILDLAUF erreichbar (${hoeheDp} dp sichtbar): " +
                    unerreichbar.joinToString(" · ") {
                        "%s: %d von %d Knöpfen, Inhalt %d dp".format(
                            it.name, it.knoepfeSichtbar, it.knoepfeGesamt, it.inhaltDp,
                        )
                    }
            )
        }

        // ---- 6. F-P3-AQ: keine zwei Bilder sind gleich ---------------------
        val nachPruefsumme = messungen.groupBy { it.pruefsumme }.filter { it.value.size > 1 }
        check(nachPruefsumme.isEmpty()) {
            "Gleiche Bilder unter verschiedenem Namen (F-P3-AQ): " +
                nachPruefsumme.values.joinToString(" · ") { gruppe ->
                    gruppe.joinToString(" = ") { it.name }
                }
        }
        println("Alle ${messungen.size} Bilder paarweise verschieden (F-P3-AQ).")

        /* Und die Gegenprobe zur Gegenprobe: Dass die Prüfsummen sich
         * unterscheiden, könnte auch daran liegen, dass jede Breite alles
         * verschiebt. Deshalb wird JE BREITE noch einmal gezählt — dort
         * unterscheiden sich nur die Inhalte. */
        for (breiteDp in breiten) {
            val dieser = messungen.filter { it.breiteDp == breiteDp }
            val doppelt = dieser.groupBy { it.pruefsumme }.filter { it.value.size > 1 }
            check(doppelt.isEmpty()) {
                "Bei $breiteDp dp zeigen zwei Bildschirme dasselbe: " +
                    doppelt.values.joinToString { g -> g.joinToString(" = ") { it.name } }
            }
        }
        println("Auch je Breite verschieden — ${breiten.size} × ${bildschirme().size} Bildschirme.")
    }

    // ---- Zeichnen und messen -----------------------------------------------

    private fun male(
        name: String,
        breiteDp: Int,
        inhalt: @Composable () -> Unit,
    ): Messung {
        val steuerung = Robolectric.buildActivity(ComponentActivity::class.java).setup()
        val activity = steuerung.get()
        val dichte = activity.resources.displayMetrics.density

        val ansicht = ComposeView(activity)
        activity.setContentView(ansicht)
        ansicht.setContent(inhalt)
        shadowOf(Looper.getMainLooper()).idle()

        /* GEZEICHNET WIRD DER SICHTBARE BEREICH, nicht der Inhalt.
         *
         * Die erste Fassung mass mit einem grosszuegigen Deckel und bekam
         * 2400 dp zurueck — jeder Bildschirm ruft `fillMaxSize()`, also
         * fuellt er, was man ihm gibt. Das Bild zeigte dann 1600 dp leeren
         * Grund, und die Frage, auf die es ankommt, blieb offen: Was steht
         * auf dem Telefon, ohne zu schieben? */
        val breitePx = (breiteDp * dichte).toInt()
        val hoehePx = (hoeheDp * dichte).toInt()
        val fest = { n: Int -> View.MeasureSpec.makeMeasureSpec(n, View.MeasureSpec.EXACTLY) }
        ansicht.measure(fest(breitePx), fest(hoehePx))
        ansicht.layout(0, 0, breitePx, hoehePx)
        shadowOf(Looper.getMainLooper()).idle()

        val bild = Bitmap.createBitmap(breitePx, hoehePx, Bitmap.Config.ARGB_8888)
        ansicht.draw(Canvas(bild))

        val ordner = File("build/bilder")
        ordner.mkdirs()
        val ziel = File(ordner, "$name.png")
        ziel.outputStream().use { bild.compress(Bitmap.CompressFormat.PNG, 100, it) }
        check(ziel.length() > 0L) { "$name: PNG ist leer" }

        val punkte = IntArray(breitePx * hoehePx)
        bild.getPixels(punkte, 0, breitePx, 0, 0, breitePx, hoehePx)

        /* ZWEITE MESSUNG OHNE FALTKANTE. Sie beantwortet die Frage, die die
         * erste nicht kann: Wie viel steht unterhalb, und wie viele Knöpfe
         * sind das? Gezeichnet wird davon nichts — das Bild soll zeigen, was
         * das Telefon zeigt. */
        val hochPx = (2400 * dichte).toInt()
        ansicht.measure(fest(breitePx), fest(hochPx))
        ansicht.layout(0, 0, breitePx, hochPx)
        shadowOf(Looper.getMainLooper()).idle()
        val hoch = Bitmap.createBitmap(breitePx, hochPx, Bitmap.Config.ARGB_8888)
        ansicht.draw(Canvas(hoch))
        val hochPunkte = IntArray(breitePx * hochPx)
        hoch.getPixels(hochPunkte, 0, breitePx, 0, 0, breitePx, hochPx)
        val grund = hochPunkte.toList().groupingBy { it }.eachCount().maxBy { it.value }.key
        var letzteZeile = 0
        for (y in 0 until hochPx) {
            if ((0 until breitePx).any { hochPunkte[y * breitePx + it] != grund }) letzteZeile = y
        }
        val alleBaender = knopfbaender(hochPunkte, breitePx, hochPx)

        val band = knopfband(punkte, breitePx, hoehePx)
        return Messung(
            name = name,
            breiteDp = breiteDp,
            knopfDp = if (band == null) 0.0 else (band.last - band.first + 1) / dichte.toDouble(),
            knopfUnterkanteDp =
                if (band == null) 0 else Math.round((band.last + 1) / dichte).toInt(),
            amRand = knopfAmRand(punkte, breitePx, hoehePx),
            unterDerFaltkante = band != null && band.last == hoehePx - 1,
            inhaltDp = Math.round((letzteZeile + 1) / dichte).toInt(),
            knoepfeGesamt = alleBaender.size,
            knoepfeSichtbar = alleBaender.count { it.last < hoehePx },
            farben = punkte.toHashSet().size,
            pruefsumme = pruefsumme(ziel),
        )
    }

    /**
     * Das **unterste** zusammenhaengende Band aus Knopffarbe.
     *
     * Warum das unterste und nicht das hoechste: Die Frage ist, ob die
     * Handlung des Bildschirms erreichbar ist, und die steht unten. Gezaehlt
     * werden nur Zeilen, in denen die Farbe **breitflaechig** vorkommt —
     * mindestens die halbe Bildbreite. Ohne diese Bedingung zaehlte der rote
     * Aufnahmepunkt als Knopfzeile: Er traegt dieselbe Farbe wie der
     * Beenden-Knopf, und die Messung meldete dann 3 dp statt 48.
     *
     * @return der Zeilenbereich, oder `null`, wenn kein farbiger Knopf im
     *   sichtbaren Bereich liegt
     */
    private fun knopfband(punkte: IntArray, breite: Int, hoehe: Int): IntRange? {
        val schwelle = breite / 2
        val istKnopfzeile = BooleanArray(hoehe) { y ->
            var treffer = 0
            val zeile = y * breite
            for (x in 0 until breite) if (punkte[zeile + x] in knopffarben) treffer++
            treffer >= schwelle
        }
        var ende = -1
        for (y in hoehe - 1 downTo 0) if (istKnopfzeile[y]) { ende = y; break }
        if (ende < 0) return null
        var anfang = ende
        while (anfang > 0 && istKnopfzeile[anfang - 1]) anfang--
        return anfang..ende
    }

    /** **Alle** zusammenhaengenden Knopfbaender, von oben nach unten. */
    private fun knopfbaender(punkte: IntArray, breite: Int, hoehe: Int): List<IntRange> {
        val schwelle = breite / 2
        val baender = mutableListOf<IntRange>()
        var y = 0
        while (y < hoehe) {
            val voll = { z: Int ->
                (0 until breite).count { punkte[z * breite + it] in knopffarben } >= schwelle
            }
            if (voll(y)) {
                val start = y
                while (y < hoehe && voll(y)) y++
                baender += start until y
            } else y++
        }
        return baender
    }

    /**
     * Beruehrt Knopffarbe die linke oder rechte Bildkante?
     *
     * Jeder Knopf sitzt in der Karte, und die hat Rand (`Abstand.vier`). Wer
     * die aeusserste Spalte erreicht, ist entweder breiter als der Platz oder
     * schon beschnitten — beides will man wissen. Das ist die Frage, die von
     * der urspruenglich gemessenen „verlangten Breite" uebrig bleibt, nachdem
     * sich herausgestellt hat, dass die nichts messen kann.
     */
    private fun knopfAmRand(punkte: IntArray, breite: Int, hoehe: Int): Boolean {
        for (y in 0 until hoehe) {
            val zeile = y * breite
            if (punkte[zeile] in knopffarben) return true
            if (punkte[zeile + breite - 1] in knopffarben) return true
        }
        return false
    }

    private fun pruefsumme(datei: File): String =
        MessageDigest.getInstance("SHA-256")
            .digest(datei.readBytes())
            .joinToString("") { "%02x".format(it) }
}
