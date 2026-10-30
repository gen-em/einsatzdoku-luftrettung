package org.genem.nadoku.handy.senden

import java.time.Duration
import java.time.Instant

/**
 * **Wann** gesendet wird: ereignisgetrieben, nicht dauernd.
 *
 * Gesendet wird bei **Phasenwechsel, Einsatzabschluss, Dienstende und
 * Wiederverbindung**, dazwischen alle 15 Minuten.
 *
 * WARUM NICHT ÖFTER. Zwei Messungen hängen daran. Die Messung des
 * Garmin-Verhaltens ergab einen **Median von 1 020 s** zwischen zwei Anfragen
 * und eine Spitze von 14 Anfragen an einem Auslöser; auf diesen Zahlen ruhen
 * die Mengenbremse und die Mengengrenze je Konto. Ein zweiter Client, der
 * alle dreißig Sekunden sendet, machte beide Zahlen wertlos — und die Bremse
 * träfe die beiden Clients verschieden.
 *
 * 15 Minuten (900 s) liegen dicht am gemessenen Median und sind zugleich der
 * längste Abstand, nach dem ein Funkloch noch als „gerade eben" durchgeht.
 *
 * WARUM NICHT SELTENER. Was nicht gesendet ist, liegt nur auf dem Handy. Geht
 * das Handy verloren, ist der Dienst verloren — und das Ziel dieser App ist,
 * dass er es nicht ist.
 *
 * DIESE KLASSE HÄLT KEINE UHR UND KEINEN FADEN. Sie beantwortet eine Frage,
 * und deshalb ist sie ohne Zeitverzug prüfbar.
 */
class Sendetakt(
    private val abstand: Duration = Duration.ofSeconds(ABSTAND_S),
    private val wiederverbindungsAbstand: Duration = Duration.ofSeconds(WIEDERVERBINDUNG_ABSTAND_S),
) {

    /** Was den Sendelauf ausgelöst hat. */
    enum class Ausloeser {
        /** Eine Phase wurde gesetzt. */
        PHASENWECHSEL,

        /** Ein Einsatz wurde abgeschlossen. */
        EINSATZABSCHLUSS,

        /** Der Dienst wurde beendet. */
        DIENSTENDE,

        /**
         * Das Netz ist wieder da. Dafür steht die Berechtigung
         * `ACCESS_NETWORK_STATE` im Manifest.
         */
        WIEDERVERBINDUNG,

        /** Der Takt läuft ab — der einzige Auslöser, der wartet. */
        TAKT,
    }

    /**
     * @param letzterVersuch wann zuletzt gesendet wurde; `null` = noch nie
     */
    fun faellig(ausloeser: Ausloeser, jetzt: Instant, letzterVersuch: Instant?): Boolean {
        /* DIE WIEDERVERBINDUNG IST EIN EREIGNIS MIT BREMSE.
         *
         * Sie kommt nicht von einem Menschen, sondern vom
         * `ConnectivityManager` — und ein flatterndes Mobilfunknetz meldet
         * `onAvailable` im Sekundentakt. Jeder Lauf kostet mindestens eine
         * Anfrage mit bcrypt-Prüfung am Server; ohne Mindestabstand wäre der
         * Auslöser eine Last, kein Dienst.
         *
         * DIE REGEL STEHT HIER UND NICHT IM RÜCKRUF, weil sie hier prüfbar
         * ist: Diese Klasse hält keine Uhr und keinen Faden. */
        if (ausloeser == Ausloeser.WIEDERVERBINDUNG) {
            if (letzterVersuch == null) return true
            return Duration.between(letzterVersuch, jetzt) >= wiederverbindungsAbstand
        }

        /* JEDES ANDERE EREIGNIS SENDET SOFORT. Es sind genau die Augenblicke,
         * in denen etwas Abgeschlossenes entstanden ist — darauf zu warten,
         * bis der Takt abläuft, hieße, es fünfzehn Minuten lang nur auf dem
         * Handy liegen zu haben. */
        if (ausloeser != Ausloeser.TAKT) return true
        if (letzterVersuch == null) return true
        return Duration.between(letzterVersuch, jetzt) >= abstand
    }

    companion object {
        /** 15 Minuten — dicht am gemessenen Median (1 020 s). */
        const val ABSTAND_S = 900L

        /**
         * Mindestabstand zweier Läufe aus **Wiederverbindung**.
         *
         * Eine Minute. Kürzer, und ein Funkloch am Straßenrand erzeugte einen
         * Lauf je Laternenmast; länger, und die Wiederverbindung verlöre
         * ihren Zweck — sie soll das Netz nutzen, solange es da ist.
         */
        const val WIEDERVERBINDUNG_ABSTAND_S = 60L
    }
}
