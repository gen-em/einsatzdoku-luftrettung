package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Paketzeile
import org.json.JSONArray
import org.json.JSONObject

/**
 * Der Nachrichtenkörper für `ingest.php` (JSON-Vertrag 3 und 4).
 *
 * ER STEHT FÜR SICH, ohne Netz und ohne Datenbank — damit sich jede Regel des
 * Vertrags einzeln prüfen lässt: dass `ended_at` null ist, solange `final`
 * false ist; dass `day_ref` nur mitgeht, wenn es eines gibt; dass ein
 * Spurpunkt ein **Array** ist und kein Objekt.
 */
object Sendekoerper {

    /**
     * @param seqVon erste gesendete Sequenznummer (`track.seq_from`)
     * @param punkte die Punkte ab [seqVon], in Reihenfolge
     * @param phasen nur für Einsätze; die **vollständige** Liste (Vertrag 3)
     */
    fun baue(
        paket: Paketzeile,
        seqVon: Long,
        punkte: List<Rohpunkt>,
        phasen: List<Phaseneintrag> = emptyList(),
    ): JSONObject = JSONObject().apply {
        put("kind", paket.art)
        put("client_ref", paket.clientRef)
        put("day", paket.tag)

        /* NUR MITSCHICKEN, WENN SIE DA IST. Ein leeres Feld wäre dasselbe in
         * umständlich — und der Server hat für die fehlende Kennung eine
         * dauerhafte Rückfallebene über (Konto, Datum). Dieselbe Stelle in
         * `Uploader.mc`. */
        paket.dienstRef?.let { put("day_ref", it) }

        put("started_at", paket.begonnenAt)

        /* `ended_at` ist null, solange `final` false ist (Vertrag 3). Nicht
         * „weglassen", sondern ausdrücklich null: Der Server unterscheidet
         * zwischen „dazu sage ich nichts" und „es gibt keins". */
        put("ended_at", if (paket.final) (paket.beendetAt ?: JSONObject.NULL) else JSONObject.NULL)
        put("final", paket.final)

        if (paket.art == Paketzeile.ART_EINSATZ) {
            put("distance_m", paket.streckeM ?: 0)
            put("ascent_m", paket.anstiegM ?: 0)
            put("phases", phasenArray(phasen))
            /* `resus_sessions` wird NICHT gesendet — und zwar gar nicht, statt
             * als leere Liste. Die Reanimation bleibt bei der Garmin
             * (E-R45-1), das Handy dokumentiert sie nicht. Eine leere Liste
             * hieße „es gibt keine"; der Server ließe den vorhandenen Stand
             * dann zwar stehen (Vertrag 3.1) und meldete es als `kept_resus`
             * — aber gemeldet würde etwas, das nie eine Aussage war. Ein
             * fehlender Schlüssel heißt „dazu sage ich nichts", und genau das
             * ist die Wahrheit. */
        }

        put(
            "track",
            JSONObject().apply {
                put("seq_from", seqVon)
                put("points", punkteArray(punkte))
            },
        )
    }

    private fun phasenArray(phasen: List<Phaseneintrag>): JSONArray =
        JSONArray().apply {
            for (p in phasen) {
                put(
                    JSONObject().apply {
                        put("phase", p.nummer)
                        put("at", p.at)
                        // lat/lon dürfen null sein — der Vertrag prüft sie
                        // einzeln und nimmt null an (Vertrag 3).
                        put("lat", p.breite ?: JSONObject.NULL)
                        put("lon", p.laenge ?: JSONObject.NULL)
                    }
                )
            }
        }

    /**
     * `track.points` ist ein Array aus `[lat, lon, ele_m, epoch_s]`.
     *
     * **Muss eine Liste sein** — ein Objekt mit den Schlüsseln „0", „1" … wird
     * abgelehnt (Vertrag 3). `ele_m` darf null sein.
     */
    private fun punkteArray(punkte: List<Rohpunkt>): JSONArray =
        JSONArray().apply {
            for (p in punkte) {
                put(
                    JSONArray().apply {
                        put(p.breite)
                        put(p.laenge)
                        put(p.hoehe ?: JSONObject.NULL)
                        put(p.zeit)
                    }
                )
            }
        }
}

/** Ein Phasen-Zeitstempel, wie er in `phases[]` steht. */
data class Phaseneintrag(
    val nummer: Int,
    val at: String,
    val breite: Double?,
    val laenge: Double?,
)
