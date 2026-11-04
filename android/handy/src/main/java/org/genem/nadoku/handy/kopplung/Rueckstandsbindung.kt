package org.genem.nadoku.handy.kopplung

import android.content.Context
import android.content.SharedPreferences
import androidx.core.content.edit
import java.time.Duration
import java.time.Instant
import org.genem.nadoku.handy.puffer.Raeumung

/** Wie sich ein gebundener Rückstand zum Schlüssel der jetzigen Kopplung verhält. */
enum class Bindungsstand {
    /** Es gibt keine Bindung — der ganze Puffer gehört der jetzigen Kopplung. */
    KEINE,

    /** Dasselbe Konto. Die Bindung ist damit aufgelöst, der Rückstand darf hinaus. */
    GLEICH,

    /**
     * Ein anderes Konto, oder die alte Kopplung kannte keinen Schlüssel.
     * Der gebundene Rückstand darf **nicht** hinaus.
     */
    ANDERS,
}

/**
 * Der Abmeldevermerk und die Bindung des Rückstands an ein Konto.
 *
 * DER FALL. Ein Gerät wird im Web gelöscht oder gesperrt, während noch
 * abgeschlossene Pakete auf ihm liegen. Senden geht nicht mehr (401/403), und
 * das Trennen war bis dahin gesperrt, solange ein Rückstand bestand — damit
 * fremde Einsätze nicht in ein fremdes Konto gehen. Neu koppeln setzt Trennen
 * voraus: eine Sackgasse, aus der nur das Löschen der App-Daten führte.
 *
 * DER AUSWEG. Ist das Gerät **abgemeldet**, darf es trotz Rückstand trennen.
 * Der Rückstand bleibt dann liegen, gebunden an den Kontoschlüssel der
 * bisherigen Kopplung und an die höchste Paketnummer zu diesem Zeitpunkt.
 * Bringt die neue Kopplung denselben Schlüssel, ist es dasselbe Konto — ein
 * Konto hat genau ein Schlüsselpaar, das nie ersetzt wird —, und der Rückstand
 * geht hinaus. Bringt sie einen anderen, geht er nicht hinaus.
 *
 * WARUM DER VERGLEICH NÖTIG IST. Der Puffer liegt im Klartext und wird erst
 * beim Senden verschlossen. Ginge der Rückstand an ein anderes Konto, wäre er
 * mit dessen Schlüssel verschlossen und dort lesbar: Patientendaten im
 * falschen Konto.
 *
 * KEIN GEHEIMNIS. Der Kontoschlüssel ist der öffentliche Teil; deshalb liegt
 * die Bindung in einer einfachen Ablage und nicht im Tresor. Sie muss das
 * Trennen überleben — der Tresor wird dabei geleert.
 *
 * DIE FRIST (Backlog Nr. 471, wie die Garmin-Uhr, E-R17-22). Ein im Web
 * gelöschtes, verlorenes Handy hielt seinen Klartextpuffer sonst ohne Ende.
 * Der Vermerk trägt deshalb den Zeitpunkt der **ersten** 401/403; 30 Tage
 * danach räumt [Abmeldefrist] alle abgeschlossenen Pakete. Die Bindung trägt
 * ihre eigene Frist, übernommen aus der Abmeldung: Das Trennen vergisst die
 * Abmeldung, und ohne eigene Frist läge der gebundene Rückstand danach
 * wieder unbegrenzt.
 */
class Rueckstandsbindung(private val ablage: SharedPreferences) {

    constructor(kontext: Context) :
        this(kontext.applicationContext.getSharedPreferences(ABLAGE, Context.MODE_PRIVATE))

    /**
     * Hat der Server das Gerät zuletzt mit 401 oder 403 abgewiesen?
     *
     * In der Ablage und nicht im Speicher: Die Frage stellt sich beim Trennen,
     * und das kommt oft erst nach einem Neustart der App.
     */
    val abgemeldet: Boolean get() = ablage.getBoolean(ABGEMELDET, false)

    /** Die erste 401/403 seit der letzten Annahme; `null` = nicht abgemeldet. */
    val abgemeldetSeit: Instant?
        get() = if (ablage.contains(SEIT)) Instant.ofEpochMilli(ablage.getLong(SEIT, 0L)) else null

    /** Bis wann das Handy die Pakete nach der Abmeldung noch hält. */
    val abmeldeFristBis: Instant? get() = abgemeldetSeit?.plus(FRIST)

    /** Nur die ERSTE Abmeldung setzt den Zeitpunkt — sonst liefe die Frist nie ab. */
    fun abmeldungVermerken(jetzt: Instant = Instant.now()) {
        ablage.edit {
            putBoolean(ABGEMELDET, true)
            if (!ablage.contains(SEIT)) putLong(SEIT, jetzt.toEpochMilli())
        }
    }

    fun abmeldungVergessen() {
        if (abgemeldet || ablage.contains(SEIT)) ablage.edit { remove(ABGEMELDET); remove(SEIT) }
    }

    /**
     * Ein Vermerk aus Android 0.20.0 hat keinen Zeitpunkt. Seine Frist beginnt
     * mit der ersten Prüfung in dieser Fassung, nicht rückwirkend — das Datum
     * der Abmeldung kennt niemand mehr. Ebenso eine Bindung ohne Frist.
     */
    fun fristenNachholen(jetzt: Instant) {
        if (abgemeldet && !ablage.contains(SEIT)) ablage.edit { putLong(SEIT, jetzt.toEpochMilli()) }
        if (ablage.contains(BIS) && !ablage.contains(BIS_FRIST)) {
            ablage.edit { putLong(BIS_FRIST, jetzt.plus(FRIST).toEpochMilli()) }
        }
    }

    /** Bis zu welcher Paketnummer der Rückstand gebunden ist; `null` = keine Bindung. */
    val gebundenBis: Long?
        get() = if (ablage.contains(BIS)) ablage.getLong(BIS, 0L) else null

    /** Bis wann der gebundene Rückstand liegen darf; `null` = keine Bindung. */
    val gebundenFristBis: Instant?
        get() = if (ablage.contains(BIS_FRIST)) Instant.ofEpochMilli(ablage.getLong(BIS_FRIST, 0L)) else null

    /**
     * Den Rückstand an den Schlüssel der bisherigen Kopplung binden.
     *
     * @param kontoschluessel `null`, wenn die alte Kopplung keinen kannte —
     *   dann ist die Bindung „unbekannt" und gilt bei jedem Schlüssel als
     *   [Bindungsstand.ANDERS]: lieber eine Rückfrage zu viel als ein Abfluss.
     */
    fun binden(kontoschluessel: String?, bisPaketId: Long, jetzt: Instant = Instant.now()) {
        /* Die Frist der Abmeldung läuft für den gebundenen Rückstand weiter;
         * ohne Abmeldung (kommt nicht vor, das Trennen verlangt eine) 30 Tage
         * ab jetzt. */
        val frist = abmeldeFristBis ?: jetzt.plus(FRIST)
        ablage.edit {
            putLong(BIS, bisPaketId)
            putLong(BIS_FRIST, frist.toEpochMilli())
            if (kontoschluessel != null) putString(SCHLUESSEL, kontoschluessel) else remove(SCHLUESSEL)
        }
    }

    /** Wie steht es — ohne etwas zu ändern. Für die Anzeige. */
    fun stand(aktuell: String?): Bindungsstand {
        if (gebundenBis == null) return Bindungsstand.KEINE
        val gebunden = ablage.getString(SCHLUESSEL, null)
        return if (gebunden != null && gebunden == aktuell) Bindungsstand.GLEICH else Bindungsstand.ANDERS
    }

    /**
     * Wie [stand], aber eine Bindung an dasselbe Konto wird dabei gelöst —
     * der Sendelauf ruft das vor dem ersten Paket.
     */
    fun pruefen(aktuell: String): Bindungsstand {
        val s = stand(aktuell)
        if (s == Bindungsstand.GLEICH) loesen()
        return s
    }

    fun loesen() {
        ablage.edit { remove(BIS); remove(SCHLUESSEL); remove(BIS_FRIST) }
    }

    private companion object {
        const val ABLAGE = "rueckstand"
        const val ABGEMELDET = "abgemeldet"
        const val SEIT = "abgemeldet_seit"
        const val BIS = "gebunden_bis"
        const val BIS_FRIST = "gebunden_frist"
        const val SCHLUESSEL = "gebunden_schluessel"
        val FRIST: Duration = Duration.ofDays(Raeumung.FRIST_TAGE)
    }
}
