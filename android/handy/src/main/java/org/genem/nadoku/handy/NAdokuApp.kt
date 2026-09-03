package org.genem.nadoku.handy

import android.app.Application
import android.content.Context
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Ortungslage
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.senden.Nachsenden
import org.genem.nadoku.handy.senden.Sendebericht
import org.genem.nadoku.handy.senden.Sender
import org.genem.nadoku.handy.tresor.KeystoreTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import java.io.File
import java.time.Instant
import java.util.concurrent.ExecutorService
import java.util.concurrent.Executors
import java.util.concurrent.atomic.AtomicInteger

/**
 * Die eine Stelle, an der die langlebigen Teile der App wohnen.
 *
 * WARUM ES SIE GIBT. Oberfläche und Vordergrunddienst arbeiten am **selben**
 * Dienst: Der Dienst zeichnet auf, während die Oberfläche längst beendet ist,
 * und die Oberfläche zeigt an, was der Dienst gerade tut. Zwei Exemplare von
 * [Dienstklammer] oder [Puffer] wären zwei Wahrheiten über einen Dienst — und
 * SQLite ließe beide gleichzeitig schreiben.
 *
 * Der Zustand selbst liegt trotzdem **nicht hier**, sondern im Puffer
 * (siehe [Dienstklammer]). Diese Klasse hält nur die Zugänge dorthin offen.
 */
class NAdokuApp : Application() {

    val puffer: Puffer by lazy { Puffer(this) }

    val einstellungen: Einstellungen by lazy { Einstellungen(this) }

    val ausduenner: Ausduenner by lazy { Ausduenner() }

    /**
     * Wie es um die Ortung steht — `null`, solange kein Dienst läuft
     * (E-S5Z-01, E-S5Z-06).
     *
     * WARUM HIER UND NICHT IM PUFFER. Das ist ein **Augenblickswert**, kein
     * Zustand des Dienstes: Er überlebt einen Neustart bewusst nicht. Eine
     * wiederhergestellte Aussage über den GPS-Empfang von vorhin wäre
     * schlimmer als keine — sie sähe aus wie eine Messung. Nach einem
     * Neustart misst der Wächter neu. Das widerspricht E-S4-31 nicht: Dort
     * geht es um den Zustand des **Dienstes**, der Neustarts überleben muss.
     *
     * `null` HEISST „ES LÄUFT KEIN DIENST", nicht „unbekannt". Vor dem Dienst
     * leitet die Oberfläche selbst ab, was sie braucht — Freigabe und
     * `isProviderEnabled`, mehr steht dort nicht zur Entscheidung an.
     *
     * GESCHRIEBEN wird ausschliesslich vom Vordergrunddienst (Wächter und
     * Zuhörer, beide auf dem Hauptfaden), GELESEN von der Dienstansicht im
     * Sekundentakt, von der Dauermeldung und von der Standmeldung an die Uhr.
     * `@Volatile`, weil Schreiber und Leser nicht in jedem Fall derselbe
     * Faden sind.
     */
    @Volatile
    var ortung: Ortungslage? = null

    val klammer: Dienstklammer by lazy {
        Dienstklammer(
            puffer = puffer,
            kennungen = Kennungen(einstellungen.kennungszaehler()),
            ausduenner = ausduenner,
        )
    }

    val tresor: Schluesseltresor by lazy {
        Schluesseltresor(File(filesDir, Schluesseltresor.DATEINAME), KeystoreTresorschluessel())
    }

    /**
     * Die Warteschlange. Sie lebt hier und nicht im Vordergrunddienst, weil
     * die halbierte Chunk-Größe (nach einem 413) einen Sendelauf überdauern
     * soll — sonst liefe die App bei jedem Lauf wieder in denselben 413.
     */
    val sender: Sender by lazy {
        Sender(
            puffer = puffer,
            netzweg = HttpNetzweg(),
            tresor = tresor,
            basis = { einstellungen.serverBasis },
            phasenLeser = { paketId -> puffer.phasen(paketId) },
        )
    }

    // ---- Senden: EIN Ausführer, nie zwei Läufe zugleich (E-S5Z-11) --------

    /**
     * Der eine Faden, auf dem gesendet wird.
     *
     * WARUM ER SEIN MUSS. Bis 0.8.1 startete **jeder** Anlass seinen eigenen
     * `Thread`: der 15-Minuten-Takt und das Dienstende aus dem
     * Vordergrunddienst, der Phasenwechsel und der Einsatzabschluss aus der
     * Oberfläche. Der Kommentar dazu lautete „die Läufe überlappen nicht (der
     * nächste kommt frühestens in 15 Minuten)" — und das galt nur für den
     * Takt. Zwei Läufe auf demselben Puffer waren möglich, `Sender.chunkPunkte`
     * ist ein ungeschütztes `var`, und harmlos war es nur, weil der Server
     * idempotent ist (B-S5Z-11). Mit E2 kommt ein dritter Anlass dazu (der
     * Nachsende-Job) — die Zusicherung wird jetzt gemacht statt kommentiert.
     *
     * Ein einzelner Faden und kein Vorrat: Es gibt nichts zu parallelisieren.
     * Was hier eingereiht wird, will nacheinander geschehen.
     */
    val sendeausfuehrer: ExecutorService by lazy {
        Executors.newSingleThreadExecutor { r -> Thread(r, "nadoku-senden") }
    }

    /** Eingereichte und laufende Sendeläufe — Grundlage von [sendelaufLaeuft]. */
    private val eingereicht = AtomicInteger(0)

    /**
     * Läuft oder wartet gerade ein Sendelauf?
     *
     * Gezählt wird ab dem **Einreichen** und nicht ab dem Start: Zwischen
     * beidem liegt die Warteschlange des Ausführers, und in dieser Spanne
     * wäre „läuft nicht" die falsche Auskunft — der Knopf „Jetzt senden"
     * stünde wieder da, obwohl der Lauf schon unterwegs ist.
     */
    val sendelaufLaeuft: Boolean get() = eingereicht.get() > 0

    /**
     * Was der letzte Lauf ergeben hat — für die Ergebniszeile der Ansicht
     * (E-S5Z-12). `null` = seit dem App-Start hat keiner stattgefunden.
     */
    @Volatile
    var letzterSendebericht: Sendelauf? = null
        private set

    /**
     * Einen Sendelauf einreihen.
     *
     * @param danach läuft **auf dem Sendefaden**, nicht auf dem Hauptfaden.
     *   Wer eine Oberfläche anfassen will, postet selbst.
     */
    fun sendelauf(danach: (Sendebericht) -> Unit = {}) {
        eingereicht.incrementAndGet()
        sendeausfuehrer.execute {
            try {
                val bericht = sender.sendeAlles()
                letzterSendebericht = Sendelauf(bericht, Instant.now())
                danach(bericht)
            } finally {
                eingereicht.decrementAndGet()
            }
        }
    }

    /**
     * Beim Start nachsehen, ob etwas liegen geblieben ist (E-S5Z-09).
     *
     * DER FALL, DEN DAS ABFÄNGT: Der Prozess ist gestorben, **bevor** der
     * Nachsende-Job geplant werden konnte — abgeräumt vom System, oder mit
     * der App weggewischt. Dann wartet die Nachlieferung sonst auf den
     * nächsten Dienst, und genau das war der Fehler, gegen den E2 gebaut ist.
     *
     * Es wird nur **geplant**, nicht gesendet: Ein Sendelauf beim App-Start
     * kostete Anlaufzeit für etwas, das ein paar Sekunden später ohnehin
     * geschieht — und ohne Netz brächte er gar nichts.
     */
    override fun onCreate() {
        super.onCreate()
        try {
            if (Nachsenden.planen(null, puffer.rueckstand(), klammer.laeuft())) {
                Nachsenden.einplanen(this)
            }
        } catch (e: Exception) {
            /* Ein Fehler HIER darf die App nicht am Starten hindern. Der
             * Puffer wird beim ersten Zugriff angelegt und migriert; ginge
             * das schief, wäre eine App, die sich gar nicht öffnen lässt,
             * die schlechtere Antwort — dann käme niemand mehr an die
             * Einstellungen, um es zu richten. */
            android.util.Log.w("NAdoku", "Nachsenden beim Start nicht planbar: ${e.message}")
        }
    }

    companion object {
        fun von(kontext: Context): NAdokuApp =
            kontext.applicationContext as NAdokuApp
    }
}

/** Ein abgeschlossener Sendelauf mit seinem Zeitpunkt. */
data class Sendelauf(val bericht: Sendebericht, val am: Instant)
