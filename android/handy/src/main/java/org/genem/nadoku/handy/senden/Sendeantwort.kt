package org.genem.nadoku.handy.senden

import org.json.JSONObject

/**
 * Die Antwort von `ingest.php`, ausgewertet (JSON-Vertrag 5).
 *
 * **EIN `ok: true` MIT `rejected` ODER `kept_*` IST KEIN REINER ERFOLG**
 * (E-S4-06): Der Upload ist angekommen, aber nicht vollständig übernommen.
 * Die beiden Fälle unterscheiden sich — `rejected` nennt einzelne verworfene
 * Werte, ein `kept_*` sagt, dass eine ganze Liste übergangen wurde und der
 * Serverstand unverändert blieb. Beides muss die App **anzeigen**; still
 * darüber hinwegzugehen wäre die schlechteste Art, Daten zu verlieren.
 */
sealed interface Sendeantwort {

    /** 200 mit `ok: true`. */
    data class Angekommen(
        val id: Long?,
        val gespeichertePunkte: Int,
        val naechsteSeq: Long,
        /** Verworfene Einzelwerte, nach Ursache gezählt. Leer = alles übernommen. */
        val verworfen: Map<String, Int>,
        /** Übergangene Listen (`kept_phases`, `kept_resus`) mit der behaltenen Anzahl. */
        val uebergangen: Map<String, Int>,
    ) : Sendeantwort {
        val vollstaendig: Boolean get() = verworfen.isEmpty() && uebergangen.isEmpty()
    }

    /** 400: Nachricht fehlerhaft — **nicht wiederholen**, lokal markieren. */
    data object Fehlerhaft : Sendeantwort

    /** 401: Schlüssel ungültig — Upload **pausieren** und anzeigen. */
    data object SchluesselAbgewiesen : Sendeantwort

    /** 413: Chunk zu groß — **halbieren** und wiederholen. */
    data object ZuGross : Sendeantwort

    /** 5xx oder unverständliche Antwort — später **unverändert** erneut. */
    data class SpaeterErneut(val code: Int) : Sendeantwort

    /** Kein Netz — später unverändert erneut. */
    data class KeineVerbindung(val ursache: String) : Sendeantwort

    companion object {
        fun lese(code: Int, koerper: String?): Sendeantwort {
            if (code == 400) return Fehlerhaft
            if (code == 401) return SchluesselAbgewiesen
            if (code == 413) return ZuGross
            if (code != 200) return SpaeterErneut(code)

            val o = try {
                if (koerper.isNullOrBlank()) null else JSONObject(koerper)
            } catch (e: org.json.JSONException) {
                null
            } ?: return SpaeterErneut(code)

            if (!o.optBoolean("ok")) return SpaeterErneut(code)

            return Angekommen(
                id = if (o.has("id")) o.optLong("id") else null,
                gespeichertePunkte = o.optInt("stored_points"),
                naechsteSeq = o.optLong("next_seq"),
                verworfen = zaehlwerk(o.optJSONObject("rejected")),
                uebergangen = buildMap {
                    for (schluessel in listOf("kept_phases", "kept_resus")) {
                        if (o.has(schluessel)) put(schluessel, o.optInt(schluessel))
                    }
                },
            )
        }

        private fun zaehlwerk(o: JSONObject?): Map<String, Int> {
            if (o == null) return emptyMap()
            return buildMap {
                for (name in o.keys()) put(name, o.optInt(name))
            }
        }
    }
}
