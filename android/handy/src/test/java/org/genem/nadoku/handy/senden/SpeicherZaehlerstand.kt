package org.genem.nadoku.handy.senden

/** Der Zählerstand im Speicher — für Prüffälle; `wert` ist offen, damit sich ein alter Stand vorgeben lässt. */
class SpeicherZaehlerstand(var wert: Long? = null) : Zaehlerstand {
    override fun lesen(): Long? = wert
    override fun schreiben(z: Long) {
        wert = z
    }
}
