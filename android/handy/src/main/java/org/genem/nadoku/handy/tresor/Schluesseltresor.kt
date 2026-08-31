package org.genem.nadoku.handy.tresor

import org.json.JSONObject
import java.io.File

/**
 * Die Ablage von `device_id` und `api_key` (E-S4-13).
 *
 * DREI SCHICHTEN, UND JEDE TRÄGT ETWAS:
 *
 * 1. **App-privates Verzeichnis.** Kein anderes Programm kommt heran; das ist
 *    Androids Grundzusage und der eigentliche Schutz im Normalbetrieb.
 * 2. **Verschlüsselt mit einem Schlüssel aus dem Android Keystore.** Das ist
 *    der Schutz für den Fall, dass Schicht 1 umgangen wird — ein entsperrter
 *    Bootloader, ein Abbild des Dateisystems.
 * 3. **Keine Gerätesicherung** (`allowBackup=false` plus leere
 *    Auszugsregeln). Der Keystore-Schlüssel ist nicht exportierbar, eine
 *    Sicherung wäre also ohnehin unbrauchbar — aber sie wäre vorhanden, und
 *    ein Geheimnis, das an einer Stelle mehr liegt als nötig, ist ein
 *    Geheimnis weniger.
 *
 * EINE DATEI, NICHT `SharedPreferences`. Preferences sind XML mit Schlüssel
 * und Wert; der Wert wäre der Geheimtext, der Schlüsselname stünde im
 * Klartext daneben und sagte, was dort liegt. Eine Datei ohne Struktur sagt
 * nichts — und `SharedPreferences` schreibt zusätzlich eine
 * `.bak`-Zwischendatei, also eine zweite Stelle, an der es steht.
 */
class Schluesseltresor(
    private val datei: File,
    private val schluessel: Tresorschluessel,
) {

    /** Liegt eine Kopplung vor? `null`, wenn keine oder eine unlesbare. */
    fun lesen(): Zugangsdaten? {
        if (!datei.exists()) return null
        return try {
            val klartext = schluessel.entschluesseln(datei.readBytes())
            val o = JSONObject(String(klartext, Charsets.UTF_8))
            val kennung = o.optString(FELD_KENNUNG)
            val geheim = o.optString(FELD_SCHLUESSEL)
            if (kennung.isEmpty() || geheim.isEmpty()) null
            else Zugangsdaten(kennung, geheim)
        } catch (e: Exception) {
            /* UNLESBAR IST WIE NICHT VORHANDEN, und mehr lässt sich hier auch
             * nicht tun: Ein beschädigtes oder mit einem anderen Schlüssel
             * geschriebenes Paket lässt sich nicht reparieren. Die App zeigt
             * dann "Nicht eingerichtet" und die NutzerIn koppelt neu — das ist
             * ein Klick. Die Ausnahme wird bewusst NICHT protokolliert: Ihr
             * Text könnte Teile des Inhalts tragen. */
            null
        }
    }

    fun speichern(zugang: Zugangsdaten) {
        val klartext = JSONObject()
            .put(FELD_KENNUNG, zugang.geraeteKennung)
            .put(FELD_SCHLUESSEL, zugang.schluessel)
            .toString()
            .toByteArray(Charsets.UTF_8)

        datei.parentFile?.mkdirs()
        /* ERST DANEBEN SCHREIBEN, DANN UMBENENNEN. Ein Stromausfall mitten im
         * Schreiben hinterließe sonst eine halbe Datei — und die ist nicht
         * "unlesbar und damit wie nicht vorhanden", sondern eine Kopplung, die
         * verloren ist, obwohl sie auf dem Server noch einen der fünf
         * Geräteplätze belegt. */
        val neben = File(datei.parentFile, datei.name + ".neu")
        neben.writeBytes(schluessel.verschluesseln(klartext))
        if (!neben.renameTo(datei)) {
            datei.writeBytes(neben.readBytes())
            neben.delete()
        }
    }

    /**
     * Löscht die Kopplung auf dem Gerät.
     *
     * LOKAL WIRD IMMER GETRENNT (E-S4-12, Backlog Nr. 14) — auch wenn der
     * Server nicht geantwortet hat. Andernfalls bliebe ein Handy ohne Netz
     * dauerhaft an ein Konto gebunden, das es nicht mehr benutzen soll.
     */
    fun loeschen() {
        datei.delete()
        File(datei.parentFile, datei.name + ".neu").delete()
    }

    fun gekoppelt(): Boolean = lesen() != null

    companion object {
        const val DATEINAME = "tresor.bin"
        private const val FELD_KENNUNG = "d"
        private const val FELD_SCHLUESSEL = "k"
    }
}
