package org.genem.nadoku.handy.senden

import java.io.File
import java.io.FileOutputStream

/**
 * Der Zähler `z` der Einsatzhülle, Fassung 2 (E-KA-69, seit KA-06f).
 *
 * WOFÜR. Der Browser des Kontos merkt sich je Einsatz den Zähler der zuletzt
 * abgenommenen Hülle und weist eine mit kleinerem ab (`zurueckgelegt`,
 * `einsatz_lesen()` in `tools/proben/huelle/referenz.py`). So kann, wer die
 * Datenbank beschreibt, keine ältere Hülle an die Stelle der neuen legen.
 *
 * DIE REGEL. `z` GEHÖRT ZUM INHALT, NICHT ZUM VERSCHLIESSEN. Vergeben wird
 * es, wenn sich der Inhalt eines Einsatzes ändert — am Handy: der Einsatz
 * entsteht (`start`), eine Phase kommt hinzu, der Einsatz wird abgeschlossen
 * (`schluss`); eine Reanimation dokumentiert das Handy nicht —, und es liegt
 * MIT DEM PAKET im Puffer (Spalte `paket.zaehler`). Jede Hülle, die danach
 * aus diesem Paket entsteht, trägt dasselbe `z`, wie oft und wann auch
 * gesendet wird. Der Wert ist die Unix-Sekunde der Änderung, lokal streng
 * steigend:
 *
 *     z = max(jetzt, letztes z + 1)
 *
 * EIN Zähler für alle Einsätze dieses Handys — diese Klasse. Sie vergibt
 * nur; WANN vergeben wird und wo der Wert liegt, steht im
 * [org.genem.nadoku.handy.puffer.Puffer] („DER ZÄHLER DER EINSATZHÜLLE").
 * Zwei Sätze, an denen die Umsetzung gemessen wird (Vektor `zaehler` in
 * `vektoren-2.json`, `EinsatzzaehlerTest`):
 *
 *     gleiches z     ⇒ innen byte-gleich
 *     anderer Inhalt ⇒ größeres z, über alle Einsätze dieses Handys
 *
 * BIS KA-06f (E-KA-64) war `z` die Unix-Sekunde des VERSCHLIESSENS, je
 * Anfrage neu. Jeder Sendeversuch hob damit den Zähler, und ein Handy, das
 * nach einer Übergabe seinen alten, unveränderten Stand nachsandte, gewann
 * gegen den Abschluss des anderen Geräts. Jetzt trägt der alte Stand den
 * alten Zähler und fällt als `zurueckgelegt` durch.
 *
 * WAS DIE REGEL LOKAL IMMER HÄLT: die eigene Folge. Springt die Zeit des
 * Handys zurück, trägt `letztes z + 1`, bis die Zeit den Zähler einholt.
 *
 * WAS SIE NICHT TRÄGT — und das gehört hierher, nicht in eine Fußnote:
 * 1. VORAUSLAUFENDE UHR. Ein Handy, dessen Zeit einmal weit voraus lag,
 *    behält den hohen Stand (er überdauert alles). Hat es damit eine Hülle
 *    gesendet, fällt jedes andere Gerät an diesem Einsatz durch, bis dessen
 *    Zeit den Stand überholt. Nichts am Handy senkt ihn.
 * 2. NACHGEHENDE UHR. Ein Handy, dessen Zeit nachgeht, vergibt bei einer
 *    echten Änderung ein `z` unter dem Stand des anderen Geräts und fällt
 *    selbst durch, obwohl niemand etwas zurückgelegt hat (Vektor
 *    `wechsel-uhr-geht-nach`).
 * 3. GLEICHSTAND (F-KA-112). Der Browser nimmt `z ≥ o` an — er muss:
 *    Derselbe Stand, neu verschlossen, trägt dasselbe `z`. Über Geräte
 *    hinweg sagt Gleichstand nichts; zwei Geräte, die in derselben Sekunde
 *    ändern, sind am Zähler nicht zu unterscheiden.
 * 4. UHRZEIT, NICHT REIHENFOLGE. `z` belegt „später geändert", nicht
 *    „vollständiger": Wer nach einer Übergabe wirklich etwas ändert,
 *    gewinnt auch gegen einen Abschluss, von dem er nichts weiß.
 * Alle vier stören erst den Gerätewechsel, nie die Folge dieses Handys.
 *
 * DER ZÄHLER ÜBERDAUERT DAS TRENNEN. Er hängt an keiner Kopplung: Ein Handy,
 * das neu koppelt, hat ein neues Gerätepaar, aber der Rückstand im Puffer
 * gehört zu Einsätzen, deren Stand der Browser schon kennt. Deshalb liegt er
 * in einer eigenen Datei und nicht im Tresor, den das Trennen löscht.
 *
 * ABGELEGT, BEVOR IHN JEMAND BENUTZT. Ein Absturz nach dem Ablegen kostet
 * eine Zahl; ein Absturz davor hätte ein Paket mit einem Zähler
 * hinterlassen, den der nächste Lauf für anderen Inhalt noch einmal
 * vergibt. Scheitert das Schreiben, wirft [naechster] einen
 * [Zaehlerausfall], und niemand bekommt eine Zahl.
 */
class Huellenzaehler(
    private val stand: Zaehlerstand,
    private val jetzt: () -> Long = { System.currentTimeMillis() / 1000 },
) {

    /**
     * Der Zähler für einen GEÄNDERTEN Inhalt — je Aufruf ein neuer, über alle
     * Fäden und Instanzen dieses Prozesses. Wer verschließt, ruft das NICHT:
     * Er liest den Zähler des Pakets.
     *
     * @throws Zaehlerausfall wenn der Stand sich nicht ablegen lässt
     */
    fun naechster(): Long = synchronized(SPERRE) {
        val z = folge(stand.lesen(), jetzt())
        try {
            stand.schreiben(z)
        } catch (e: Exception) {
            throw Zaehlerausfall(e)
        }
        z
    }

    companion object {
        /**
         * Die obere Grenze: 2^53 − 1. Darüber liest der Browser eine
         * JSON-Zahl nicht mehr genau. Die Sekunde erreicht sie nie; ein
         * verdorbener Stand könnte es. An der Grenze BLEIBT der Zähler
         * stehen — gleiches `z` nimmt die Referenz an, ein übergelaufenes nie.
         * Dort (und nur dort) gilt „anderer Inhalt ⇒ größeres z" nicht mehr.
         */
        const val Z_MAX = 9_007_199_254_740_991L

        /** Eine Sperre für alle Instanzen: Sender und Bildschirm teilen sich eine Datei. */
        private val SPERRE = Any()

        /**
         * Die Regel ohne Uhr und ohne Speicher. Was kein Stand ≥ 1 ist, zählt
         * als „kein Vorgänger"; ohne Vorgänger und ohne brauchbare Zeit ist
         * `z` 1 — nie kleiner, die Referenz weist `z < 1` als `innen` ab.
         */
        fun folge(alt: Long?, jetzt: Long): Long {
            val mindestens = when {
                alt == null || alt < 1 -> 1L
                alt >= Z_MAX -> Z_MAX
                else -> alt + 1
            }
            return maxOf(mindestens, minOf(jetzt, Z_MAX))
        }
    }
}

/**
 * Der Zählerstand ließ sich nicht ablegen (Platte voll, Datei nicht
 * schreibbar). Eine eigene Ausnahme, damit die zwei Stellen, die sie
 * erwarten, nichts anderes mitfangen: Der Puffer legt die Änderung dann
 * OHNE Zähler ab (die Aufzeichnung geht vor), der Sender sendet später.
 */
class Zaehlerausfall(ursache: Throwable) : IllegalStateException("Hüllenzähler nicht ablegbar", ursache)

/** Wo der letzte Zählerstand liegt — getrennt von der Regel, damit sie sich ohne Platte prüfen lässt. */
interface Zaehlerstand {
    /** Der zuletzt vergebene Stand, oder `null`, wenn es keinen lesbaren gibt. */
    fun lesen(): Long?

    /** Legt den Stand dauerhaft ab; wirft, wenn das nicht gelingt. */
    fun schreiben(z: Long)
}

/**
 * Der Zählerstand in einer Datei des app-privaten Verzeichnisses.
 *
 * KLARTEXT, eine Dezimalzahl. Der Stand ist kein Geheimnis — er ist die
 * Uhrzeit der letzten Änderung an einem Einsatz oder wenig mehr —, und was kein
 * Geheimnis ist, braucht keinen Schlüssel aus dem Keystore, der fehlen kann.
 *
 * ERST DANEBEN, DANN UMBENENNEN, und vorher auf die Platte gezwungen: Eine
 * halbe Datei läse sich als „kein Vorgänger", und der Zähler finge bei der
 * Uhrzeit an — richtig, solange die Uhr stimmt, und genau dann falsch, wenn
 * sie es nicht tut.
 *
 * UNLESBAR IST WIE NICHT VORHANDEN. Mehr lässt sich nicht tun: Der Zähler
 * beginnt dann bei der Uhrzeit, und die liegt über jedem Stand, den dieses
 * Handy mit richtiger Uhr je vergeben hat.
 */
class DateiZaehlerstand(private val datei: File) : Zaehlerstand {

    override fun lesen(): Long? = try {
        if (datei.exists()) datei.readText(Charsets.US_ASCII).trim().toLongOrNull() else null
    } catch (e: Exception) {
        null
    }

    override fun schreiben(z: Long) {
        datei.parentFile?.mkdirs()
        val neben = File(datei.parentFile, datei.name + ".neu")
        FileOutputStream(neben).use {
            it.write(z.toString().toByteArray(Charsets.US_ASCII))
            it.fd.sync()
        }
        if (!neben.renameTo(datei)) {
            FileOutputStream(datei).use {
                it.write(z.toString().toByteArray(Charsets.US_ASCII))
                it.fd.sync()
            }
            neben.delete()
        }
    }

    companion object {
        const val DATEINAME = "huellenzaehler"
    }
}
