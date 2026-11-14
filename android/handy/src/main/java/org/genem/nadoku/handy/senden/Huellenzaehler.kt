package org.genem.nadoku.handy.senden

import java.io.File
import java.io.FileOutputStream

/**
 * Der Zähler `z` der Einsatzhülle, Fassung 2 (E-KA-64).
 *
 * WOFÜR. Der Browser des Kontos merkt sich je Einsatz den Zähler der zuletzt
 * abgenommenen Hülle und weist eine mit kleinerem ab (`zurueckgelegt`,
 * `einsatz_lesen()` in `tools/proben/huelle/referenz.py`). So kann, wer die
 * Datenbank beschreibt, keine ältere Hülle an die Stelle der neuen legen.
 *
 * DIE REGEL. `z` = Unix-Sekunde des Verschließens, lokal streng steigend:
 *
 *     z = max(jetzt, letztes z + 1)
 *
 * EIN Zähler für alle Einsätze dieses Handys. Die Referenz verlangt je
 * Einsatz `z ≥ o`, nicht `o + 1`; ein gemeinsamer Zähler erfüllt das und
 * braucht keinen Platz im Puffer. Die Sekunde statt `+1 ab 1`, weil ein
 * Einsatz das Gerät wechseln kann (Übergabe im laufenden Dienst, Q-KA-13):
 * Das Standbuch hält den Stand je EINSATZ, nicht je Gerät, und ein zweites
 * Gerät muss über dem ersten liegen. Mit der Uhrzeit tut es das, solange
 * die Uhren beider Geräte nicht weiter auseinandergehen als der Abstand
 * zweier Übergaben.
 *
 * WAS DIE REGEL LOKAL IMMER HÄLT: die eigene Folge. Springt die Zeit des
 * Handys zurück, trägt `letztes z + 1`, bis die Zeit den Zähler einholt.
 *
 * WAS SIE NICHT TRÄGT — und das gehört hierher, nicht in eine Fußnote:
 * 1. Ein Handy, dessen Zeit einmal weit VORAUS lag, behält den hohen Stand.
 *    Hat es damit eine Hülle gesendet, fällt jedes andere Gerät an diesem
 *    Einsatz durch, bis dessen Zeit den Stand überholt.
 * 2. Ein Handy, dessen Zeit NACHGEHT, fällt selbst durch, sobald ein anderes
 *    Gerät den Einsatz belegt hat (Vektor `wechsel-uhr-geht-nach`).
 * 3. `z` belegt, WANN verschlossen wurde, nicht wie frisch der Inhalt ist.
 * Alle drei stören erst den Gerätewechsel, nie die Folge dieses Handys.
 *
 * DER ZÄHLER ÜBERDAUERT DAS TRENNEN. Er hängt an keiner Kopplung: Ein Handy,
 * das neu koppelt, hat ein neues Gerätepaar, aber der Rückstand im Puffer
 * gehört zu Einsätzen, deren Stand der Browser schon kennt. Deshalb liegt er
 * in einer eigenen Datei und nicht im Tresor, den das Trennen löscht.
 *
 * ABGELEGT, BEVOR IHN JEMAND BENUTZT. Ein Absturz nach dem Ablegen kostet
 * eine Zahl; ein Absturz davor hätte eine Hülle mit einem Zähler
 * hinterlassen, den der nächste Lauf noch einmal vergibt. Scheitert das
 * Schreiben, wirft [naechster], und es entsteht keine Hülle.
 */
class Huellenzaehler(
    private val stand: Zaehlerstand,
    private val jetzt: () -> Long = { System.currentTimeMillis() / 1000 },
) {

    /** Der nächste Zählerstand — je Aufruf ein neuer, über alle Fäden und Instanzen dieses Prozesses. */
    fun naechster(): Long = synchronized(SPERRE) {
        val z = folge(stand.lesen(), jetzt())
        stand.schreiben(z)
        z
    }

    companion object {
        /**
         * Die obere Grenze: 2^53 − 1. Darüber liest der Browser eine
         * JSON-Zahl nicht mehr genau. Die Sekunde erreicht sie nie; ein
         * verdorbener Stand könnte es. An der Grenze BLEIBT der Zähler
         * stehen — gleiches `z` nimmt die Referenz an, ein übergelaufenes nie.
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
 * Uhrzeit des letzten Verschließens oder wenig mehr —, und was kein
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
