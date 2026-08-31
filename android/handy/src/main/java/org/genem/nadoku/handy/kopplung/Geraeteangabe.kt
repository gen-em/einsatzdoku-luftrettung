package org.genem.nadoku.handy.kopplung

import android.os.Build
import org.json.JSONObject

/**
 * Der `geraet`-Block der Kopplung (JSON-Vertrag 1a, F-S4-B → E-S4-28).
 *
 * WOZU ES IHN GIBT. Der Server soll wissen, welche Geräte tatsächlich koppeln
 * — es gibt dafür keine brauchbare äußere Quelle, und ohne die Angabe weiß er
 * nur, *dass* ein Gerät gekoppelt ist. Für die Uhr ist der Schlüssel die
 * Teilenummer (325 Teilenummern → 173 Modelle, serverseitig auflösbar). Ein
 * Handy hat keine; es kennt seinen Modellnamen dafür selbst.
 *
 * DIE FELDFORM (E-S4-28, entschieden am 31.08.2026). Das R42-Kleinstpaket lag
 * beim Bau von B2 nicht auf `main` — `pair.php` liest den Block dort an keiner
 * Stelle aus. Genommen wird deshalb der in F-S4-B vorgeschlagene Rückfall: die
 * Felder der Uhr, soweit sie an einem Handy dieselbe Bedeutung haben, dazu
 * `hersteller`/`modell` an der Stelle der Teilenummer.
 *
 * | Feld | Uhr | Handy |
 * |---|---|---|
 * | `art` | `"uhr"` | **`"handy"`** |
 * | `teil` | Teilenummer | **`null`** — ein Handy hat keine |
 * | `hersteller` | — | **`Build.MANUFACTURER`** |
 * | `modell` | — | **`Build.MODEL`** |
 * | `br`, `ho` | Display in px | dito |
 * | `touch` | vorhanden? | immer `true` |
 * | `fw` | Firmware-Stand | **Android-Fassung** (`Build.VERSION.RELEASE`) |
 * | `ciq` | Uhr-Plattform | **entfällt**, dafür `sdk` (API-Stufe) |
 * | `app` | App-Fassung | dito |
 *
 * `ciq` wird **weggelassen und nicht auf `null` gesetzt**: Ein Feld, das es
 * für diese Geräteart gar nicht gibt, ist etwas anderes als eines, das das
 * Gerät nicht beantworten kann. Der Vertrag stellt beides frei.
 *
 * WAS BEWUSST NICHT MITGEHT: alles, was ein Gerät wiedererkennbar macht —
 * `ANDROID_ID`, IMEI, Seriennummer. Dieselbe Überlegung wie beim
 * `uniqueIdentifier` der Uhr: Für eine Stückzahl-Statistik nicht nötig, und in
 * einer kleinen Gruppe ein Personenbezug mehr, als die Frage rechtfertigt. Die
 * Zuordnung leistet die `device_id`, die der Server ohnehin vergibt.
 *
 * EINE KOPPLUNG DARF AN EINER STATISTIKANGABE NIE SCHEITERN (Vertrag 1a).
 * Deshalb wird jedes Feld einzeln und gekapselt gelesen: Wirft eines — auf
 * einem Gerät mit eigenwilliger Firmware kommt das vor —, steht dort `null`,
 * und die Kopplung läuft weiter.
 */
data class Geraeteangabe(
    val art: String,
    val teil: String?,
    val hersteller: String?,
    val modell: String?,
    val br: Int?,
    val ho: Int?,
    val touch: Boolean?,
    val fw: String?,
    val sdk: Int?,
    val app: String?,
) {

    fun alsJson(): JSONObject = JSONObject().apply {
        put("art", art)
        put("teil", teil ?: JSONObject.NULL)
        put("hersteller", hersteller ?: JSONObject.NULL)
        put("modell", modell ?: JSONObject.NULL)
        put("br", br ?: JSONObject.NULL)
        put("ho", ho ?: JSONObject.NULL)
        put("touch", touch ?: JSONObject.NULL)
        put("fw", fw ?: JSONObject.NULL)
        put("sdk", sdk ?: JSONObject.NULL)
        put("app", app ?: JSONObject.NULL)
    }

    companion object {
        const val ART_HANDY = "handy"

        /**
         * @param breite  Displaybreite in Pixeln, vom Aufrufer gemessen —
         *   die Fenstermaße hängen an einem `Context`, und diese Klasse soll
         *   keinen brauchen (sie ist damit ohne Android-Umgebung prüfbar).
         */
        fun vomGeraet(breite: Int?, hoehe: Int?, appFassung: String?): Geraeteangabe =
            Geraeteangabe(
                art = ART_HANDY,
                teil = null,
                hersteller = ohneAusnahme { Build.MANUFACTURER },
                modell = ohneAusnahme { Build.MODEL },
                br = breite,
                ho = hoehe,
                touch = true,
                fw = ohneAusnahme { Build.VERSION.RELEASE },
                sdk = ohneAusnahme { Build.VERSION.SDK_INT },
                app = appFassung,
            )

        /** Vertrag 1a: an einer Statistikangabe scheitert keine Kopplung. */
        private fun <T> ohneAusnahme(lies: () -> T): T? =
            try {
                lies()
            } catch (e: Throwable) {
                null
            }
    }
}
