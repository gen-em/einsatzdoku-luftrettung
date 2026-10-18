package org.genem.nadoku.handy.tresor

/**
 * Die Zugangsdaten einer Kopplung (JSON-Vertrag 1).
 *
 * `geraeteKennung` steht im Kopf jeder Anfrage und ist **kein** Geheimnis;
 * die Berechtigung hängt allein am `schluessel`. Trotzdem liegen beide
 * zusammen im Tresor: Getrennt zu speichern hieße, zwei Ablagen zu pflegen,
 * die immer gemeinsam gültig sein müssen.
 *
 * KEIN `toString()` MIT INHALT. Die Vorgabe von `data class` würde den
 * Schlüssel in jede Protokollzeile schreiben, in der ein Objekt dieser Art
 * beiläufig auftaucht — E-S4-13: kein Klartext in Logs. Deshalb ist
 * `toString()` überschrieben; das ist keine Kosmetik, sondern die einzige
 * Stelle, an der ein Geheimnis sonst *unbeabsichtigt* austritt.
 */
data class Zugangsdaten(
    val geraeteKennung: String,
    val schluessel: String,
    /**
     * Der öffentliche Schlüssel des Kontos, Base64 eines SEC1-Punkts (seit
     * 0.18.0, S11-09, JSON-Vertrag 1a.3a). Damit verschließt das Handy Spur
     * und Phasenorte. `null`: Die Kopplung stammt aus der Zeit davor, oder das
     * Konto hatte beim Koppeln noch kein Schlüsselpaar — der Sender holt ihn
     * dann über `status` nach (E-S11-80). Kein Geheimnis, aber er steht im
     * selben Tresor: eine Kopplung, eine Ablage.
     */
    val kontoschluessel: String? = null,
) {
    override fun toString(): String = "Zugangsdaten($geraeteKennung, Schlüssel verborgen)"
}
