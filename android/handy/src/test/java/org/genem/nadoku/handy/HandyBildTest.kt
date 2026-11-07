package org.genem.nadoku.handy

import android.graphics.Bitmap
import android.graphics.Canvas
import android.os.Looper
import android.view.View
import androidx.activity.ComponentActivity
import androidx.compose.runtime.Composable
import androidx.compose.ui.platform.ComposeView
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.kopplung.Abweisung
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
 * WARUM ES DIESEN FALL GIBT. Ohne Bilderlauf wären Oberflächenänderungen am
 * Telefon **gelesen und nicht gesehen** — etwa die Sperre „Standort
 * ausgeschaltet" oder die Zustandszeile ohne Signal.
 *
 * DER WEG IST DERSELBE WIE BEI DER UHR (`UhrBildTest`) und aus demselben
 * Grund:
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
 * | Höhe der farbigen Knopfflächen | **48 dp** | `BEDIENHOEHE` — die App wird mit Handschuhen bedient |
 * | Ein Knopf liegt **im sichtbaren Bereich** | ohne Bildlauf bedienbar | ein Bildschirm, dessen Handlung unter dem Rand liegt, ist keiner |
 * | Kein Knopf **berührt den Bildrand** | er ist nicht beschnitten | die Karte hat Rand; wer ihn erreicht, ist zu breit |
 * | Bilder sind **paarweise verschieden** | jedes zeigt etwas anderes | eine grüne Zahl über gleiche Bilder belegt nichts |
 *
 * **WAS DIESER FALL AUSDRÜCKLICH NICHT MISST: waagerechten Überlauf im Sinn
 * des Web-Bilderlaufs.** Die Zahl wäre wertlos: Jeder Bildschirm der App
 * ruft `fillMaxSize()`, also gewinnt die Einschränkung immer, und ein zu
 * breites Kind wird von Compose **beschnitten statt gemeldet**. „Verlangte
 * Breite = Gerätebreite" stünde deshalb in jeder Zeile, gleich was darin
 * steht. Was an ihre Stelle tritt, sind die zwei Zeilen darüber: Sie messen die Folge des Beschnitts, wo sie
 * jemanden trifft — an den Bedienelementen.
 *
 * **DER GRUND FÜR DIE DRITTE ZEILE.** Ein Bilderlauf kann „248 Bilder,
 * 0 Überlauf" melden, während 176 davon die Anmeldeseite zeigen. Eine grüne
 * Zahl über 248 Bilder, von denen zwei Drittel
 * dasselbe zeigen, ist kein Beleg, sondern eine Beruhigung. Dieser Fall
 * vergleicht deshalb die Prüfsummen aller erzeugten PNG und besteht darauf,
 * dass keine zweimal vorkommt.
 *
 * GRENZEN, und sie sind erheblich:
 *
 * - Gemessen wird die **Anordnung**, nicht das Aussehen auf einem Gerät.
 *   Schriftrasterung, Herstelleraufsätze und die Systemleisten fehlen.
 * - Die Bedienhöhe wird an den **farbigen** Knöpfen gemessen (Aktion,
 *   Hauptaktion, Gefahr — hell und dunkel).
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
     * Bildschirme, auf denen mit Absicht KEIN farbiger Knopf steht.
     *
     * Die Regel unten fragt „ist hier eine Handlung erreichbar?" und sucht
     * dafür die Primärfarbe — bislang war der farbige Knopf immer die
     * Haupthandlung. Es gibt einen Bildschirm, auf dem es keine gibt, und das
     * ist kein Versäumnis:
     *
     * **`kopplung-code`** zeigt den Code und wartet darauf, dass ein Mensch
     * ihn im Browser einträgt. Die App ist an dieser Stelle nicht am Zug; sie
     * hat nichts anzubieten außer „Abbrechen", und den in die Primärfarbe zu
     * setzen hieße, den Abbruch zur empfohlenen Handlung zu machen. Der
     * Bildschirm ist trotzdem bedienbar — die Prüfung darunter zählt nach,
     * dass ein Knopf sichtbar ist.
     *
     * Ein Eintrag hier ist eine Aussage über die Gestaltung, keine
     * Stummschaltung: Er wird mitgeprüft (ungenutzte Ausnahmen fallen auf),
     * und er verlangt weiterhin einen Ausweg auf dem Bildschirm.
     */
    private val OHNE_HAUPTHANDLUNG = listOf("kopplung-code")


    /**
     * Die Flächenfarben, an denen ein Bedienelement im Bild zu erkennen ist:
     * Aktion, Hauptaktion, Gefahr — je Modus aus den Ressourcen gelesen
     * (`rolle_aktion`, `rolle_aktion_haupt`, `rolle_gefahr`), nicht als Hexwert
     * hier. Bis Android 0.21.1 standen hier Orange und Rot fest.
     */
    private var knopffarben: Set<Int> = emptySet()

    /**
     * Die Kopfleiste (Leitwert Dunkelblau, schaltet nicht um). Hell trägt die
     * Aktion DIESELBE Farbe; ohne Ausschluss zählte die Kopfleiste als
     * bildbreiter Knopf am Rand. Ausgenommen werden die Zeilen von oben, die
     * zur Hälfte in dieser Farbe stehen.
     */
    private var kopffarbe: Int = 0

    /**
     * Ein Band unter dieser Höhe ist eine LINIE, kein Knopf: Im Dark Mode
     * trägt der Rand des Nebenknopfs (`linie_stark`, blau 300) dieselbe Farbe
     * wie die Aktion. 8 dp liegt weit unter jedem Knopf (48 dp) und weit über
     * jedem Rand (1 dp).
     */
    private val linieBisDp = 8.0

    /** Die zugesagte Bedienhöhe. Toleranz 1 dp für die Rasterung. */
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
        verwerfbar: Int = abgewiesen,
        verworfen: Int? = null,
        sendeergebnis: Sendeergebnis? = null,
        sendelaufLaeuft: Boolean = false,
        einsatzLaeuft: Boolean = false,
        laufendePhase: Int = Phasen.FREI,
        gesetztePhasen: List<Phasenzeile> = emptyList(),
        fristBis: String? = null,
    ): @Composable () -> Unit = {
        DienstAnsicht(
            stand = Dienststand(
                laeuft = laeuft,
                begonnenSeit = if (laeuft) "07:02" else null,
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
            logoWahl = LogoWahl.LUFT,
            rueckstand = rueckstand,
            abgewiesen = abgewiesen,
            verwerfbar = verwerfbar,
            verworfen = verworfen,
            sendeergebnis = sendeergebnis,
            sendelaufLaeuft = sendelaufLaeuft,
            fristBis = fristBis,
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

        // -- Im Dienst, je Ortungszustand --
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

        /* SPÄT IM EINSATZ — der engste Fall. Mit allen acht
         * gesetzten Phasen ist die Liste am längsten; wenn „Einsatz
         * abschliessen" irgendwo unter die Faltkante rutscht, dann hier. */
        "laufend-einsatz-spaet" to dienst(
            laeuft = true, ortung = Ortungsstand.OK, einsatzLaeuft = true,
            laufendePhase = Phasen.LETZTE,
            gesetztePhasen = Phasen.UEBERTRAGEN.mapIndexed { i, n ->
                Phasenzeile(n, "09:%02d".format(12 + i * 7))
            },
        ),

        // -- Was das Senden anzeigt --
        /* VERWERFEN: mit Knopf und Hinweis; ohne Knopf,
         * wenn das abgewiesene Paket noch läuft; danach die Quittung. Die
         * Rückfrage selbst ist ein Dialog und hier nicht zu sehen (unten). */
        "sende-abgewiesen" to dienst(rueckstand = 2, abgewiesen = 3),
        "sende-abgewiesen-laufend" to dienst(
            laeuft = true, ortung = Ortungsstand.OK, abgewiesen = 1, verwerfbar = 0,
        ),
        "sende-verworfen" to dienst(rueckstand = 2, verworfen = 3),
        "sende-laeuft" to dienst(rueckstand = 2, sendelaufLaeuft = true),
        "sende-kein-netz" to dienst(
            rueckstand = 2,
            sendeergebnis = Sendeergebnis(Sendeausgang.KEIN_NETZ, "12:41"),
        ),
        "sende-schluessel-weg" to dienst(
            rueckstand = 2,
            sendeergebnis = Sendeergebnis(Sendeausgang.SCHLUESSEL_ABGEWIESEN, "12:41"),
        ),
        /* DIE FRIST NACH EINER ABMELDUNG (Nr. 471): Ergebniszeile und
         * darunter das Datum, bis zu dem die Pakete bleiben. */
        "sende-frist" to dienst(
            rueckstand = 2,
            sendeergebnis = Sendeergebnis(Sendeausgang.SCHLUESSEL_ABGEWIESEN, "12:41"),
            fristBis = "04.12.",
        ),
        "sende-gesendet" to dienst(
            sendeergebnis = Sendeergebnis(Sendeausgang.GESENDET, "12:41"),
        ),

        // -- Nebenbildschirme --
        /* VIER KOPPLUNGSBILDER. Zu sehen sein muessen die vier Punkte des
         * Ablaufs aus Vertrag 1a -- besonders die beiden mittleren, denn dort steht je
         * eine Zahl bzw. eine Adresse, die von diesem Bildschirm auf einen
         * anderen wandert. Genau dort faellt ein Umbruch oder ein
         * abgeschnittener Text auf. */
        /* KEIN VERBRAUCHSHINWEIS, und das ist eine Grenze dieses
         * Prüfmittels: **Der Bilderlauf kann keine Dialoge.** Gemessen:
         * 1 dp Inhalt bei allen drei Breiten, 0 Knöpfe. Ein Compose-
         * `AlertDialog` rendert in einem EIGENEN Fenster, und `captureToImage`
         * über die Wurzel-Composable sieht davon nichts.
         *
         * Ein Bild, das leer ist, belegt nichts und zählte trotzdem in der
         * Gesamtzahl mit. Was der Hinweis
         * tut, steht stattdessen in `VerbrauchhinweisTest`; wie er AUSSIEHT,
         * ist am Emulator zu sehen — dort ist der Akku-Dialog derselben
         * Bauform belegt (docs/bilder/s4-rest/07-akku-hinweis.png).
         *
         * Das gilt für JEDEN Dialog dieser App: Akkufrage, Rückfrage,
         * Trennfrage. Keiner von ihnen ist im Bilderlauf abgebildet, und das
         * ist keine Lücke im Fallkatalog, sondern eine des Mittels. */
        "kopplung-bereit" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.Bereit,
                logoWahl = LogoWahl.LUFT,
                aufStarten = {}, aufAntwort = {},
            )
        },
        "kopplung-code" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.Wartet("AB3K7Q", restSekunden = 540),
                logoWahl = LogoWahl.LUFT,
                aufStarten = {}, aufAntwort = {},
            )
        },
        "kopplung-frage" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.Frage(
                    "AB3K7Q", konto = "ph***@gen-em.org", restSekunden = 300,
                ),
                logoWahl = LogoWahl.LUFT,
                aufStarten = {}, aufAntwort = {},
            )
        },
        "kopplung-abgewiesen" to {
            KopplungAnsicht(
                schritt = Kopplungsschritt.Abgewiesen(
                    Abweisung.ZU_VIELE_GERAETE,
                    servermeldung = "Es sind bereits 5 Geraete gekoppelt.",
                ),
                logoWahl = LogoWahl.LUFT,
                aufStarten = {}, aufAntwort = {},
            )
        },
        "einstellungen" to {
            EinstellungenAnsicht(
                logoWahl = LogoWahl.BODEN,
                uhrSperre = true,
                dienstLaeuft = false,
                trennmeldung = null,
                aufLogoWahl = {}, aufUhrSperre = {}, aufTrennen = {},
                aufDatenschutz = {}, aufImpressum = {}, aufZurueck = {},
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

        /* HELL UND DUNKEL (seit Android 0.22.0). Die Rollen schalten über
         * `values-night/` um; Robolectric folgt dem Qualifier `night`. Die
         * hellen Bilder behalten ihren Namen, die dunklen tragen `-dunkel`. */
        for ((modus, qualifier) in listOf("" to "notnight", "-dunkel" to "night")) {
            for (breiteDp in breiten) {
                RuntimeEnvironment.setQualifiers("w${breiteDp}dp-h800dp-$qualifier-xhdpi")
                val kontext = RuntimeEnvironment.getApplication()
                knopffarben = setOf(
                    kontext.getColor(org.genem.nadoku.R.color.rolle_aktion),
                    kontext.getColor(org.genem.nadoku.R.color.rolle_aktion_haupt),
                    kontext.getColor(org.genem.nadoku.R.color.rolle_gefahr),
                )
                kopffarbe = kontext.getColor(org.genem.nadoku.R.color.marke_dunkelblau)
                for ((name, inhalt) in bildschirme()) {
                    messungen += male("$name-${breiteDp}dp$modus", breiteDp, inhalt)
                }
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
        val ohneKnopf = messungen
            .filter { it.knopfDp == 0.0 }
            .filterNot { m -> OHNE_HAUPTHANDLUNG.any { m.name.startsWith(it) } }
        check(ohneKnopf.isEmpty()) {
            "Kein farbiger Knopf im sichtbaren Bereich (${hoeheDp} dp): " +
                ohneKnopf.joinToString { it.name }
        }

        /* DIE AUSNAHMEN MUESSEN GREIFEN. Eine Ausnahme, die nichts erklaert,
         * beschreibt entweder einen Bildschirm, den es nicht mehr gibt, oder
         * einen, der inzwischen doch einen Knopf hat -- in beiden Faellen
         * prueft die Liste weniger, als sie vorgibt. Dieselbe Vorschrift wie
         * bei der Wortliste (tools/wortliste/, "ungenutzte Ausnahmen"). */
        val ungenutzt = OHNE_HAUPTHANDLUNG.filterNot { a ->
            messungen.any { it.name.startsWith(a) && it.knopfDp == 0.0 }
        }
        check(ungenutzt.isEmpty()) {
            "Ungenutzte Ausnahmen in OHNE_HAUPTHANDLUNG: ${ungenutzt.joinToString()}"
        }

        /* KEINE PRUEFUNG `knoepfeSichtbar > 0` -- sie maesse nichts.
         *
         * Der Gedanke: Wo keine Haupthandlung steht, steht wenigstens ein
         * Ausweg. Das geht nicht: `kopplung-code` traegt einen
         * Abbrechen-Knopf und meldet trotzdem 0/0.
         *
         * DER GRUND IST EINE GRENZE DIESES PRUEFMITTELS: `knopfbaender()`
         * sucht die PRIMAER- und die BEENDEN-Farbe. Ein neutraler Knopf ist
         * weiss mit duennem Rand -- er hat keine Flaeche, die sich vom
         * Untergrund abhebt, und faellt damit durch jede Farbmessung.
         * `knoepfeSichtbar` heisst also nicht „sichtbare Knoepfe", sondern
         * „sichtbare FARBIGE Knoepfe"; die Zahl 0/0 ist richtig gemessen und
         * beantwortet nur eine andere Frage als die gestellte.
         *
         * Eine Pruefung, die das nicht kann, wird nicht so hingeschrieben,
         * als koennte sie es. Dass der Ausweg da ist, steht im Quelltext
         * (KopplungAnsicht, Zweig `Wartet`) und ist am Emulator zu sehen --
         * nicht hier. Der Befund gehoert unter die Grenzen des
         * Pruefmittels. */

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
        /* `knopfDp > 0.0` STAND BIS ZU DIESEM PAKET NUR IM KOMMENTAR.
         *
         * Der Absatz darueber sagt seit jeher „nur dort geprueft, wo ein
         * farbiger Knopf im Bild ist" -- die Bedingung dafuer fehlte im Code.
         * Aufgefallen ist es erst, als mit `kopplung-code` der erste
         * Bildschirm OHNE farbigen Knopf dazukam: Er meldete 0,0 dp und damit
         * „Bedienhoehe unter 48 dp", obwohl er gar keinen Knopf hat, dessen
         * Hoehe zu klein sein koennte. Eine Regel, die einen fehlenden Knopf
         * als zu flachen meldet, misst nicht, was sie sagt. */
        val zuFlach = messungen.filter {
            it.knopfDp > 0.0 && !it.unterDerFaltkante && it.knopfDp < sollDp - 1.0
        }
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

        // ---- 6. keine zwei Bilder sind gleich ------------------------------
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

        /* JE BAUART EIN ORDNER. Schrieben `testDebugUnitTest` und
         * `testReleaseUnitTest` beide nach build/bilder/, bliebe liegen, was
         * zuletzt fertig war -- einmal die Fassungszeile ohne, einmal die mit
         * "-pruef", je nach Reihenfolge der parallelen Aufgaben. Ein
         * Bildvergleich braucht einen festen Stand. */
        val ordner = File("build/bilder/${org.genem.nadoku.BuildConfig.BUILD_TYPE}")
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
        val linie = (linieBisDp * dichte).toInt()
        val alleBaender = knopfbaender(hochPunkte, breitePx, hochPx).filter { it.count() > linie }

        val band = knopfbaender(punkte, breitePx, hoehePx).lastOrNull { it.count() > linie }
        return Messung(
            name = name,
            breiteDp = breiteDp,
            knopfDp = if (band == null) 0.0 else (band.last - band.first + 1) / dichte.toDouble(),
            knopfUnterkanteDp =
                if (band == null) 0 else Math.round((band.last + 1) / dichte),
            amRand = knopfAmRand(punkte, breitePx, hoehePx),
            unterDerFaltkante = band != null && band.last == hoehePx - 1,
            inhaltDp = Math.round((letzteZeile + 1) / dichte),
            knoepfeGesamt = alleBaender.size,
            knoepfeSichtbar = alleBaender.count { it.last < hoehePx },
            farben = punkte.toHashSet().size,
            pruefsumme = pruefsumme(ziel),
        )
    }

    /**
     * Wie viele Zeilen von oben die Kopfleiste belegt — zur Hälfte in
     * [kopffarbe]. Sie werden bei der Suche nach Knöpfen übersprungen.
     */
    private fun kopfEnde(punkte: IntArray, breite: Int, hoehe: Int): Int {
        var y = 0
        while (y < hoehe && (0 until breite).count { punkte[y * breite + it] == kopffarbe } >= breite / 2) y++
        return y
    }

    /**
     * **Alle** zusammenhaengenden Knopfbaender unter der Kopfleiste, von oben
     * nach unten. Gezaehlt werden nur Zeilen, in denen die Farbe
     * **breitflaechig** vorkommt — mindestens die halbe Bildbreite. Ohne
     * diese Bedingung zaehlte der rote Aufnahmepunkt als Knopfzeile. Das
     * unterste Band ist die Handlung des Bildschirms: Die steht unten.
     */
    private fun knopfbaender(punkte: IntArray, breite: Int, hoehe: Int): List<IntRange> {
        val schwelle = breite / 2
        val baender = mutableListOf<IntRange>()
        var y = kopfEnde(punkte, breite, hoehe)
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
        for (y in kopfEnde(punkte, breite, hoehe) until hoehe) {
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
