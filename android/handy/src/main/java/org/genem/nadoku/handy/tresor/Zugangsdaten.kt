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
data class Zugangsdaten(val geraeteKennung: String, val schluessel: String) {
    override fun toString(): String = "Zugangsdaten($geraeteKennung, Schlüssel verborgen)"
}
