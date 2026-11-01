package org.genem.nadoku.handy

import android.content.Context
import androidx.core.content.edit
import org.genem.nadoku.gemeinsam.LogoWahl
import org.genem.nadoku.gemeinsam.Kennungen
import org.genem.nadoku.gemeinsam.Modus

/**
 * Die Einstellungen der App — alles, was **kein Geheimnis** ist.
 *
 * DIE TRENNUNG VOM TRESOR IST ABSICHT. `device_id` und `api_key` liegen
 * verschlüsselt in einer eigenen Datei; hier steht, was ohne
 * Schaden im Klartext stehen darf. Beides in eine Ablage zu werfen hieße, den
 * Schutz der strengeren an der schwächeren auszurichten — oder umgekehrt
 * Einstellungen zu verschlüsseln, die auf jedem Werbebanner stehen könnten.
 *
 * Die Server-Adresse steht hier nicht — sie ist eine Konstante des Baulaufs
 * (`Serveradresse.BASIS`).
 */
class Einstellungen(kontext: Context) {

    private val ablage =
        kontext.applicationContext.getSharedPreferences(ABLAGE, Context.MODE_PRIVATE)

    /* KEINE SERVERADRESSE. Sie ist eine Konstante des Baulaufs
     * (`Serveradresse.BASIS`) und keine Einstellung -- eine Angabe, die fuer
     * alle dieselbe ist, gehoert nicht in eine Ablage je Geraet.
     *
     * EIN ALTBESTAND-EINTRAG `server_basis` GEHT BEIM ERSTEN ZUGRIFF: Eine
     * Adresse, die niemand mehr liest, soll auch niemand mehr finden. */
    init {
        if (ablage.contains(ALT_SERVER_BASIS)) ablage.edit { remove(ALT_SERVER_BASIS) }
    }

    /**
     * Die Logo-Wahl. Vorgabe **„wechselnd"** — sie wird einmal je
     * App-Start gewürfelt und bleibt dann stehen; das Würfeln selbst macht
     * [org.genem.nadoku.gemeinsam.Bildmarke].
     */
    var logoWahl: LogoWahl
        get() = LogoWahl.ausZahl(ablage.getInt(LOGO, LogoWahl.WECHSELND.zahl))
        set(wert) = ablage.edit { putInt(LOGO, wert.zahl) }

    /**
     * Die Sperre der Uhr, abschaltbar.
     *
     * Sie steht hier und nicht auf der Uhr, weil die Uhr **keine eigenen
     * Einstellungen** hat: Sie übernimmt sie über den Nachrichtenweg —
     * wie die Garmin, ohne Abstimmungsbedarf zwischen den Geräten.
     */
    var uhrSperre: Boolean
        get() = ablage.getBoolean(UHR_SPERRE, true)
        set(wert) = ablage.edit { putBoolean(UHR_SPERRE, wert) }

    /**
     * Die zuletzt getroffene Moduswahl — sie ist die Vorgabe beim nächsten
     * Dienstbeginn. Wer immer ohne Knöpfe arbeitet, soll das nicht
     * jeden Morgen neu einstellen müssen.
     */
    var letzterModus: Modus
        get() = Modus.ausGespeichertem(ablage.getString(MODUS, null))
        set(wert) = ablage.edit { putString(MODUS, wert.gespeichert) }

    /**
     * Wurde durch die Akku-Freistellung geführt?
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
     * Wurde der Hinweis auf den Stromverbrauch gezeigt?
     *
     * EIN ZWEITER MERKER NEBEN [akkuHinweisGezeigt], und das ist kein
     * Versehen: Die beiden Hinweise beantworten verschiedene Fragen und
     * erreichen verschiedene Menschen. Der Akku-Dialog erklaert, warum die App
     * Strom ziehen DARF, und erscheint nur, wenn die Freistellung noch nicht
     * steht. Dieser hier sagt, dass sie es in erheblichem Mass TUT — und er
     * erreicht gerade die, die den ersten nie sehen, weil ihre Freistellung
     * schon stand.
     *
     * Sie an EINEN Merker zu haengen hiesse: Wer den Akku-Dialog gesehen hat,
     * bekommt den Verbrauchshinweis nie — und das sind zwei verschiedene
     * Auskuenfte.
     */
    var verbrauchHinweisGezeigt: Boolean
        get() = ablage.getBoolean(VERBRAUCH, false)
        set(wert) = ablage.edit { putBoolean(VERBRAUCH, wert) }

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
        const val LOGO = "logo_wahl"
        const val UHR_SPERRE = "uhr_sperre"
        const val MODUS = "letzter_modus"
        const val AKKU = "akku_hinweis"
        const val VERBRAUCH = "verbrauch_hinweis"
        const val ZAEHLER = "kennungszaehler"
        /** Der Schlüssel einer früher gespeicherten Serveradresse; nur noch zum Entfernen. */
        const val ALT_SERVER_BASIS = "server_basis"
    }
}
