package org.genem.nadoku.handy.tresor

/**
 * Die Ablage des Gerätepaars im Speicher — für Prüffälle.
 *
 * Dieselben Regeln wie auf dem Gerät ([AbgelegterGeraeteschluessel]), nur
 * ohne Datei und ohne Schlüssel aus dem Keystore, den Robolectric nicht
 * mitbringt. `inhalt` ist offen, damit ein Prüffall „Neustart" spielen kann:
 * eine zweite Instanz auf demselben Inhalt.
 */
class SpeicherGeraeteschluessel(var inhalt: ByteArray? = null) : AbgelegterGeraeteschluessel() {
    override fun laden(): ByteArray? = inhalt
    override fun ablegen(klartext: ByteArray?) {
        inhalt = klartext
    }
}
