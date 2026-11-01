package org.genem.nadoku.handy.uhr

/**
 * Was ein Uhr-Ereignis mit dem Vordergrunddienst macht.
 *
 * WOZU EINE EIGENE KLASSE FÜR DREI ZEILEN. Eine einzige Bedingung im
 * `HandyHorcher` — `if (app.klammer.laeuft()) AufzeichnungsDienst.starten(this)` —
 * startet und **beendet nie**. Ein Dienstende von der Uhr schlösse den Dienst
 * dann im Puffer, während der Vordergrunddienst weiterliefe: Ortung an, Akku
 * leer, Dauermeldung nach dem nächsten Auffrischen „Kein Dienst", und
 * **kein** DIENSTENDE-Sendelauf. Gesendet würde erst beim nächsten
 * 15-Minuten-Takt — sofern der Dienst bis dahin überhaupt noch liefe.
 *
 * Die Entscheidung ist reine Rechnung und liegt deshalb hier: Sie ist auf der
 * JVM prüfbar, der `WearableListenerService` ist es nicht.
 */
enum class Dienstfolge {
    /** Es läuft jetzt ein Dienst und der Vordergrunddienst fehlt. */
    STARTEN,

    /** Der Dienst ist zu Ende und der Vordergrunddienst läuft noch. */
    BEENDEN,

    /** Nichts zu tun. */
    NICHTS,
    ;

    companion object {
        /**
         * @param liefVorher lief die Dienstklammer **vor** dem Ereignis?
         *   Wird vor `uebernimm()` gelesen — danach ist es zu spät.
         * @param laeuftNachher läuft sie danach?
         * @param dienstSteht läuft der Vordergrunddienst gerade?
         *
         *   **Ohne diese Marke darf gar nichts geschehen.** `startService()`
         *   aus dem Hintergrund wirft ab Android 8 `IllegalStateException`,
         *   und ein Stopp-Befehl an einen Dienst, den es nicht gibt, startet
         *   ihn erst — um ihn dann sofort wieder zu beenden. Beides ist
         *   unnötig, und das erste ist ein Absturz.
         */
        fun aus(liefVorher: Boolean, laeuftNachher: Boolean, dienstSteht: Boolean): Dienstfolge =
            when {
                !liefVorher && laeuftNachher -> STARTEN
                liefVorher && !laeuftNachher && dienstSteht -> BEENDEN
                /* Lief vorher, läuft nicht mehr, und der Vordergrunddienst
                 * ist schon weg: Dann hat ihn das System abgeräumt. Was noch
                 * liegt, holt der Nachsende-Job beim nächsten App-Start ab
                 * — hier ist nichts mehr zu retten. */
                else -> NICHTS
            }
    }
}
