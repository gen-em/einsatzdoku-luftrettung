package org.genem.nadoku.handy

import android.app.Application
import android.content.Context
import org.genem.nadoku.handy.aufzeichnung.Ausduenner
import org.genem.nadoku.handy.aufzeichnung.Ortungslage
import org.genem.nadoku.handy.dienst.Dienstklammer
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.handy.kopplung.HttpNetzweg
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.senden.Sender
import org.genem.nadoku.handy.tresor.KeystoreTresorschluessel
import org.genem.nadoku.handy.tresor.Schluesseltresor
import java.io.File

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

    companion object {
        fun von(kontext: Context): NAdokuApp =
            kontext.applicationContext as NAdokuApp
    }
}
