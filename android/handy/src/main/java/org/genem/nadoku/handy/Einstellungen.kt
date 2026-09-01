package org.genem.nadoku.handy

import android.content.Context
import androidx.core.content.edit
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.handy.dienst.Kennungen
import org.genem.nadoku.gemeinsam.Modus
import org.genem.nadoku.handy.kopplung.Serveradresse

/**
 * Die Einstellungen der App — alles, was **kein Geheimnis** ist.
 *
 * DIE TRENNUNG VOM TRESOR IST ABSICHT. `device_id` und `api_key` liegen
 * verschlüsselt in einer eigenen Datei (E-S4-13); hier steht, was ohne
 * Schaden im Klartext stehen darf. Beides in eine Ablage zu werfen hieße, den
 * Schutz der strengeren an der schwächeren auszurichten — oder umgekehrt
 * Einstellungen zu verschlüsseln, die auf jedem Werbebanner stehen könnten.
 *
 * Die Server-Adresse wird **normalisiert gespeichert** (Basis mit
 * abschließendem `/`), nicht so, wie sie getippt wurde: Sonst müsste jede
 * lesende Stelle die Toleranzregeln kennen.
 */
class Einstellungen(kontext: Context) {

    private val ablage =
        kontext.applicationContext.getSharedPreferences(ABLAGE, Context.MODE_PRIVATE)

    /** Basisadresse des Servers, `null` wenn keine gesetzt ist. */
    var serverBasis: String?
        get() = ablage.getString(SERVER, null)
        set(wert) {
            val sauber = Serveradresse.normalisiere(wert)
            ablage.edit {
                if (sauber == null) remove(SERVER) else putString(SERVER, sauber)
            }
        }

    /**
     * Die Logo-Wahl (E-S4-22b). Vorgabe **„wechselnd"** — sie wird einmal je
     * App-Start gewürfelt und bleibt dann stehen; das Würfeln selbst macht
     * [org.genem.nadoku.gemeinsam.Bildmarke].
     */
    var logoWahl: LogoWahl
        get() = LogoWahl.ausZahl(ablage.getInt(LOGO, LogoWahl.WECHSELND.zahl))
        set(wert) = ablage.edit { putInt(LOGO, wert.zahl) }

    /**
     * Die Sperre der Uhr (E-S4-21d), abschaltbar.
     *
     * Sie steht hier und nicht auf der Uhr, weil die Uhr **keine eigenen
     * Einstellungen** hat: Sie übernimmt sie über den Nachrichtenweg (C2) —
     * wie die Garmin, ohne Abstimmungsbedarf zwischen den Geräten.
     */
    var uhrSperre: Boolean
        get() = ablage.getBoolean(UHR_SPERRE, true)
        set(wert) = ablage.edit { putBoolean(UHR_SPERRE, wert) }

    /**
     * Die zuletzt getroffene Moduswahl — sie ist die Vorgabe beim nächsten
     * Dienstbeginn (E-S4-20). Wer immer ohne Knöpfe arbeitet, soll das nicht
     * jeden Morgen neu einstellen müssen.
     */
    var letzterModus: Modus
        get() = Modus.ausGespeichertem(ablage.getString(MODUS, null))
        set(wert) = ablage.edit { putString(MODUS, wert.gespeichert) }

    /**
     * Wurde durch die Akku-Freistellung geführt? (E-S4-05)
     *
     * Nur ein Merker dafür, dass **gefragt** wurde — nicht dafür, dass die
     * Freistellung gilt. Ob sie hält, weiß nur das Gerät, und Samsungs „Apps
     * im Tiefschlaf" ist ein zweiter Schalter an anderer Stelle. Die App
     * fragt deshalb einmal und drängt nicht.
     */
    var akkuHinweisGezeigt: Boolean
        get() = ablage.getBoolean(AKKU, false)
        set(wert) = ablage.edit { putBoolean(AKKU, wert) }

    /**
     * Der fortlaufende Zähler der Client-Kennungen (Vertrag 8).
     *
     * Er liegt hier, weil er **Neustarts überleben muss** und weil er kein
     * Geheimnis ist — er ist die Zusicherung, dass sich keine Kennung
     * wiederholt.
     */
    fun kennungszaehler(): Kennungen.Zaehlerspeicher = object : Kennungen.Zaehlerspeicher {
        override fun lies(): Long = ablage.getLong(ZAEHLER, 0L)
        override fun schreib(wert: Long) = ablage.edit { putLong(ZAEHLER, wert) }
    }

    private companion object {
        const val ABLAGE = "nadoku"
        const val SERVER = "server_basis"
        const val LOGO = "logo_wahl"
        const val UHR_SPERRE = "uhr_sperre"
        const val MODUS = "letzter_modus"
        const val AKKU = "akku_hinweis"
        const val ZAEHLER = "kennungszaehler"
    }
}
