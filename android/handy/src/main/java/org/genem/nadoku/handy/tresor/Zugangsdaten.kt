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
 * beiläufig auftaucht — und in Logs steht kein Klartext. Deshalb ist
 * `toString()` überschrieben; das ist keine Kosmetik, sondern die einzige
 * Stelle, an der ein Geheimnis sonst *unbeabsichtigt* austritt.
 */
data class Zugangsdaten(
    val geraeteKennung: String,
    val schluessel: String,
    /**
     * Der öffentliche Schlüssel des Kontos, Base64 eines SEC1-Punkts
     * (JSON-Vertrag 1a.3a). Damit verschließt das Handy Spur und Phasenorte.
     * `null`: Die Kopplung ist eine alte ohne Schlüssel, oder das Konto hatte
     * beim Koppeln noch kein Schlüsselpaar — der Sender holt ihn dann über
     * `status` nach. Kein Geheimnis, aber er steht im
     * selben Tresor: eine Kopplung, eine Ablage.
     */
    val kontoschluessel: String? = null,
    /**
     * Hat der Server `"huelle": 2` gesagt (JSON-Vertrag 1a.7)? Nur dann
     * verschließt das Handy in Fassung 2, mit seinem Geräteschlüssel als
     * Absender (seit KA-06c). `false` bei jeder alten Kopplung und bei einer
     * neuen, bei der die Weiche des Browsers aus stand — dann bleibt es bei
     * Fassung 1, bis der Server es anders sagt.
     */
    val huelle2: Boolean = false,
) {
    override fun toString(): String = "Zugangsdaten($geraeteKennung, Schlüssel verborgen)"
}
