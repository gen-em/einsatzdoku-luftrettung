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
     * Wie es um die Ortung steht — `null`, solange kein Dienst läuft.
     *
     * WARUM HIER UND NICHT IM PUFFER. Das ist ein **Augenblickswert**, kein
     * Zustand des Dienstes: Er überlebt einen Neustart bewusst nicht. Eine
     * wiederhergestellte Aussage über den GPS-Empfang von vorhin wäre
     * schlimmer als keine — sie sähe aus wie eine Messung. Nach einem
     * Neustart misst der Wächter neu. Anders der Zustand des **Dienstes**:
     * Der muss Neustarts überleben.
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
            phasenLeser = { paketId -> puffer.phasen(paketId) },
            // Fehlt der Kontoschlüssel, holt der Sendelauf ihn über `status`
            // nach — auf demselben Faden, ohne Bildschirm.
            schluesselHolen = {
                org.genem.nadoku.handy.kopplung.Kopplungsdienst(HttpNetzweg(), tresor).schluesselHolen()
            },
        )
    }

    // ---- Senden: EIN Ausführer, nie zwei Läufe zugleich -------------------

    /**
     * Der eine Faden, auf dem gesendet wird.
     *
     * WARUM ER SEIN MUSS. Senden wollen viele Anlässe: der 15-Minuten-Takt
     * und das Dienstende aus dem Vordergrunddienst, der Phasenwechsel und
     * der Einsatzabschluss aus der Oberfläche, der Nachsende-Job. Startete
     * jeder seinen eigenen `Thread`, wären zwei Läufe auf demselben Puffer
     * möglich — `Sender.chunkPunkte` ist ein ungeschütztes `var`, und harmlos
     * wäre es nur, weil der Server idempotent ist. Die Zusicherung wird hier
     * gemacht statt kommentiert.
     *
     * Ein einzelner Faden und kein Vorrat: Es gibt nichts zu parallelisieren.
     * Was hier eingereiht wird, will nacheinander geschehen.
     */
    val sendeausfuehrer: ExecutorService by lazy {
        Executors.newSingleThreadExecutor { r -> Thread(r, "nadoku-senden") }
    }

    /**
     * Der Faden für Nachrichten **an die Uhr** — ein zweiter, nicht der
     * Sendeausführer.
     *
     * Die Meldung darf nicht auf den Hauptfaden, weil
     * `WearNachrichtenweg.sende()` bis zu fünf Sekunden je `Tasks.await`
     * blockiert. Auf den Sendeausführer darf sie aber auch nicht: Beide
     * Warteschlangen haben **verschiedene Fristen und verschiedene
     * Fehlerbilder**.
     *
     * Ein Upload eines Zwölfstundendienstes braucht zwanzig Anfragen und kann
     * eine Minute dauern. Läge die Uhrnachricht dahinter, erschiene die
     * Warnung „keine Ortung" am Handgelenk eine Minute zu spät — und genau
     * ihre Rechtzeitigkeit ist der Grund, warum die Uhr es erfährt. Umgekehrt darf
     * eine Uhr im Funkloch (fünf Sekunden Wartezeit, oft mehrfach) keinen
     * Diensttag aufhalten.
     *
     * WAS DER SENDEAUSFÜHRER ZUSICHERT, BLEIBT UNBERÜHRT: Dort geht es um
     * **zwei Läufe auf demselben Puffer**. Eine Uhrnachricht fasst den Puffer nur lesend
     * an (den Stand), schreibt nichts und kann deshalb nichts verschränken.
     */
    val uhrausfuehrer: ExecutorService by lazy {
        Executors.newSingleThreadExecutor { r -> Thread(r, "nadoku-uhr") }
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
     * Was der letzte Lauf ergeben hat — für die Ergebniszeile der Ansicht.
     * `null` = seit dem App-Start hat keiner stattgefunden.
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
     * Beim Start nachsehen, ob etwas liegen geblieben ist.
     *
     * DER FALL, DEN DAS ABFÄNGT: Der Prozess ist gestorben, **bevor** der
     * Nachsende-Job geplant werden konnte — abgeräumt vom System, oder mit
     * der App weggewischt. Dann wartet die Nachlieferung sonst auf den
     * nächsten Dienst, und genau dagegen ist der Nachsende-Job gebaut.
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
