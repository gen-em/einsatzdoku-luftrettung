package org.genem.nadoku.handy.kopplung

import org.json.JSONObject

/**
 * Inhalt des Kopplungs-QR-Codes (E-S4-15).
 *
 * Das Format ist kompaktes JSON:
 *
 * ```json
 * {"server":"https://einsatz.beispieldomain.de/","code":"AB3K7Q"}
 * ```
 *
 * KEIN EIGENES URL-SCHEMA. Gescannt wird **in** der App, nicht mit der
 * Systemkamera — ein `nadoku://`-Schema bräuchte eine Registrierung im
 * Manifest, einen zweiten Einstiegsweg in die App und eine Antwort auf die
 * Frage, was passiert, wenn eine fremde Seite den Link auslöst. Für einen
 * Kameraschwenk innerhalb der eigenen App leistet es nichts.
 *
 * DIE KAMERA SIEHT ALLES MÖGLICHE. Ein Paketaufkleber, ein WLAN-Code, eine
 * Speisekarte — jeder QR-Code im Blickfeld landet hier. Deshalb gibt diese
 * Klasse bei allem, was nicht genau passt, `null` zurück und keine Ausnahme:
 * Ein fremder Code ist kein Fehler, sondern der Normalfall beim Zielen.
 */
data class QrInhalt(val basis: String, val code: String) {

    companion object {
        fun lese(rohtext: String?): QrInhalt? {
            val text = rohtext?.trim() ?: return null
            if (!text.startsWith("{")) return null      // spart das Ausnahmewerfen

            val o = try {
                JSONObject(text)
            } catch (e: org.json.JSONException) {
                return null
            }

            val basis = Serveradresse.normalisiere(o.optString("server", null)) ?: return null
            val code = Kopplungscode.normalisiere(o.optString("code", null))
            if (!Kopplungscode.gueltig(code)) return null

            return QrInhalt(basis, code)
        }
    }
}
