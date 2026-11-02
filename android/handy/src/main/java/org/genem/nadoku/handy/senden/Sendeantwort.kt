package org.genem.nadoku.handy.senden

import org.json.JSONObject

/**
 * Warum der Server das Gerät nicht senden lässt (403, JSON-Vertrag 5).
 *
 * WARUM EINE EIGENE AUSKUNFT NEBEN 401. Ein 401 heißt: Dieses Gerät gibt es
 * nicht mehr, oder sein Schlüssel passt nicht — der Weg heraus ist eine neue
 * Kopplung. Ein 403 heißt das Gegenteil: Das Gerät ist bekannt, aber im Web
 * deaktiviert, oder das Konto darf gerade nicht. Neu zu koppeln hilft dann
 * nicht; es hilft das Web. Beides in einen Topf zu werfen hieße, einen Rat zu
 * geben, der im Kreis führt.
 */
enum class Sperrgrund {
    /** `device_disabled` — das Gerät steht im Web auf inaktiv. */
    GERAET_DEAKTIVIERT,

    /** `konto` / `gesperrt`. */
    KONTO_GESPERRT,

    /** `konto` / `wartet` — die Freigabe durch eine AdministratorIn steht aus. */
    KONTO_WARTET,

    /** `konto` / `unbestaetigt` — die Adresse ist noch nicht bestätigt. */
    KONTO_UNBESTAETIGT,

    /** 403 ohne lesbaren Körper — etwa von einem Zwischenstück. */
    UNBEKANNT,
}

/**
 * Die Antwort von `ingest.php`, ausgewertet (JSON-Vertrag 5).
 *
 * **EIN `ok: true` MIT `rejected` ODER `kept_*` IST KEIN REINER ERFOLG**:
 * Der Upload ist angekommen, aber nicht vollständig übernommen.
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
        /**
         * Übergangene Listen und Felder mit ihrer Zahl: `kept_phases`,
         * `kept_resus`, `kept_points` (Punkte nicht angehängt, Ersetzfenster
         * zu) und `kept_meta` (Metadaten übergangen, Wert immer 1).
         */
        val uebergangen: Map<String, Int>,
    ) : Sendeantwort {
        val vollstaendig: Boolean get() = verworfen.isEmpty() && uebergangen.isEmpty()
    }

    /** 400: Nachricht fehlerhaft — **nicht wiederholen**, lokal markieren. */
    data object Fehlerhaft : Sendeantwort

    /** 401: Schlüssel ungültig — Upload **pausieren** und anzeigen. */
    data object SchluesselAbgewiesen : Sendeantwort

    /**
     * 403: Gerät deaktiviert oder Konto nicht aktiv — Upload **pausieren**,
     * nichts verwerfen, den Grund nennen. Nach dem Entsperren im Web kommt der
     * Rückstand idempotent an.
     */
    data class Gesperrt(val grund: Sperrgrund) : Sendeantwort

    /** 413: Chunk zu groß — **halbieren** und wiederholen. */
    data object ZuGross : Sendeantwort

    /**
     * 507: Das Konto ist voll. Löst sich nur durch eine Handlung im Web;
     * die App wartet [WARTEN_KONTO_VOLL_S] bis zum nächsten Versuch.
     */
    data object KontoVoll : Sendeantwort

    /**
     * 5xx, 429 oder eine unverständliche Antwort — später **unverändert**
     * erneut.
     *
     * @param wartenS wie lange bis zum nächsten Versuch, oder `null` für
     *   „beim nächsten Anlass". Gesetzt bei 429 und 503: aus `Retry-After`,
     *   sonst eine feste Zeit.
     */
    data class SpaeterErneut(val code: Int, val wartenS: Long? = null) : Sendeantwort

    /** Kein Netz — später unverändert erneut. */
    data class KeineVerbindung(val ursache: String) : Sendeantwort

    companion object {
        /** 429 ohne Kopfzeile: die erste Sprosse der Sperrleiter (Vertrag 5). */
        const val WARTEN_429_S = 900L

        /** 503 ohne Kopfzeile: die Zeit, die der Wartungsmodus nennt. */
        const val WARTEN_503_S = 300L

        /** 507: eine Stunde. */
        const val WARTEN_KONTO_VOLL_S = 3600L

        /**
         * Obergrenze für eine Wartezeit aus der Kopfzeile. Ein Zwischenstück,
         * das einen unsinnigen Wert schickt, soll das Senden nicht auf Tage
         * anhalten: Sechs Stunden decken jede Sprosse der Mengenbremse, die
         * ein Gerät realistisch erreicht.
         */
        const val WARTEN_HOECHSTENS_S = 6 * 3600L

        fun lese(code: Int, koerper: String?, retryAfterS: Long? = null): Sendeantwort {
            if (code == 400) return Fehlerhaft
            if (code == 401) return SchluesselAbgewiesen
            if (code == 403) return Gesperrt(sperrgrund(alsJson(koerper)))
            if (code == 413) return ZuGross
            if (code == 507) return KontoVoll
            if (code == 429) return SpaeterErneut(code, warten(retryAfterS, WARTEN_429_S))
            if (code == 503) return SpaeterErneut(code, warten(retryAfterS, WARTEN_503_S))
            if (code != 200) return SpaeterErneut(code)

            val o = alsJson(koerper) ?: return SpaeterErneut(code)

            if (!o.optBoolean("ok")) return SpaeterErneut(code)

            return Angekommen(
                id = if (o.has("id")) o.optLong("id") else null,
                gespeichertePunkte = o.optInt("stored_points"),
                naechsteSeq = o.optLong("next_seq"),
                verworfen = zaehlwerk(o.optJSONObject("rejected")),
                uebergangen = buildMap {
                    for (schluessel in UEBERGANGEN) {
                        if (o.has(schluessel)) put(schluessel, o.optInt(schluessel))
                    }
                },
            )
        }

        /**
         * Die vier `kept_*` des Vertrags (5). Fehlte eine davon, sähe ein
         * Paket, dessen Punkte oder Metadaten der Server wegen des
         * Ersetzfensters übergangen hat, wie ein Erfolg aus.
         */
        val UEBERGANGEN = listOf("kept_phases", "kept_resus", "kept_points", "kept_meta")

        private fun warten(kopfzeile: Long?, vorgabe: Long): Long =
            (kopfzeile ?: vorgabe).coerceIn(1L, WARTEN_HOECHSTENS_S)

        private fun sperrgrund(o: JSONObject?): Sperrgrund = when (o?.optString("error")) {
            "device_disabled" -> Sperrgrund.GERAET_DEAKTIVIERT
            "konto" -> when (o.optString("grund")) {
                "gesperrt" -> Sperrgrund.KONTO_GESPERRT
                "wartet" -> Sperrgrund.KONTO_WARTET
                "unbestaetigt" -> Sperrgrund.KONTO_UNBESTAETIGT
                else -> Sperrgrund.UNBEKANNT
            }
            else -> Sperrgrund.UNBEKANNT
        }

        private fun alsJson(koerper: String?): JSONObject? = try {
            if (koerper.isNullOrBlank()) null else JSONObject(koerper)
        } catch (e: org.json.JSONException) {
            null
        }

        private fun zaehlwerk(o: JSONObject?): Map<String, Int> {
            if (o == null) return emptyMap()
            return buildMap {
                for (name in o.keys()) put(name, o.optInt(name))
            }
        }
    }
}
