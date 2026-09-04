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
 *
 * SEIT DER UMGEKEHRTEN KOPPLUNG (R49, S5) HAT DER TRESOR ZWEI ZUSTÄNDE.
 * Zugangsdaten entstehen jetzt schon beim ersten Schritt (`start`), sind aber
 * bis zum Ja des Menschen **schwebend**: `ingest.php` weist sie mit `401` ab,
 * weil es das Gerät noch nicht gibt (Vertrag 1a.1). Sie liegen trotzdem in der
 * Datei und nicht im Arbeitsspeicher — die beiden folgenden Anliegen brauchen
 * sie als Kopfzeilen, und ein Prozesstod zwischen zwei Abfragen dürfte die
 * Sitzung nicht wertlos machen.
 *
 * Der Unterschied steht als drittes Feld in der Datei. Er wird gebraucht, weil
 * sonst [gekoppelt] schon nach `start` wahr wäre: Die App zeigte die
 * Dienstansicht, der Aufzeichnungsdienst liefe an, und jedes Paket ginge gegen
 * eine `401`. **Eine alte Datei ohne das Feld gilt als gültig** — sie stammt
 * aus der Zeit, als jede gespeicherte Kopplung eine fertige war.
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

    /**
     * Zugangsdaten einer fertigen Kopplung ablegen.
     *
     * Der Weg über `pair.php` benutzt ihn nicht mehr — dort entstehen die
     * Daten schwebend ([speichernSchwebend]) und werden mit [bestaetigen]
     * gültig. Er bleibt für den von Hand angelegten Zugang: Wer im Web „Gerät
     * anlegen" wählt, bekommt Kennung und Schlüssel fertig in die Hand, und
     * für sie gibt es keine Sitzung, die zu bestätigen wäre.
     */
    fun speichern(zugang: Zugangsdaten) = schreiben(zugang, schwebend = false)

    /**
     * Zugangsdaten ablegen, die es serverseitig noch nicht gibt (Vertrag 1a.1).
     *
     * Bis zum Ja weist `ingest.php` sie ab. [gekoppelt] bleibt deshalb
     * `false`, und die App zeigt weiter den Kopplungsbildschirm.
     */
    fun speichernSchwebend(zugang: Zugangsdaten) = schreiben(zugang, schwebend = true)

    /**
     * Aus schwebend wird gültig — der eine Schritt nach `bestaetigen ja`.
     *
     * Er liest und schreibt neu, statt ein Feld zu ändern: Die Datei ist ein
     * verschlüsseltes Paket, kein Datensatz mit Feldern, die sich einzeln
     * anfassen ließen. Ohne lesbaren Inhalt tut er nichts — dann gibt es keine
     * Sitzung, die gültig werden könnte.
     */
    fun bestaetigen() {
        val zugang = lesen() ?: return
        schreiben(zugang, schwebend = false)
    }

    private fun schreiben(zugang: Zugangsdaten, schwebend: Boolean) {
        val klartext = JSONObject()
            .put(FELD_KENNUNG, zugang.geraeteKennung)
            .put(FELD_SCHLUESSEL, zugang.schluessel)
            .put(FELD_SCHWEBEND, schwebend)
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

    /**
     * Ist das Gerät gekoppelt — also **fertig**, nicht bloß in einer laufenden
     * Sitzung? Schwebende Zugangsdaten zählen ausdrücklich nicht.
     */
    fun gekoppelt(): Boolean = !schwebend() && lesen() != null

    /**
     * Liegen schwebende Zugangsdaten? `false` auch dann, wenn gar keine
     * Datei da ist — „schwebend" ist eine Aussage über eine vorhandene
     * Sitzung, nicht über deren Fehlen.
     */
    fun schwebend(): Boolean {
        if (!datei.exists()) return false
        return try {
            val o = JSONObject(String(schluessel.entschluesseln(datei.readBytes()), Charsets.UTF_8))
            // Vorgabe false: Eine Datei aus der Zeit vor der umgekehrten
            // Kopplung kennt das Feld nicht und war immer eine fertige.
            o.optBoolean(FELD_SCHWEBEND, false)
        } catch (e: Exception) {
            false
        }
    }

    companion object {
        const val DATEINAME = "tresor.bin"
        private const val FELD_KENNUNG = "d"
        private const val FELD_SCHLUESSEL = "k"

        /** Einbuchstabig wie die beiden anderen: Die Datei soll nichts erzählen. */
        private const val FELD_SCHWEBEND = "s"
    }
}
