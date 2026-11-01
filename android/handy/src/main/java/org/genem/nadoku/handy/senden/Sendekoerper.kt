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
 * false ist; dass `day_ref` nur mitgeht, wenn es eines gibt.
 *
 * VERSCHLOSSEN. `track` ist ein Spurstück mit Hülle statt einer Liste aus
 * `[lat, lon, ele_m, epoch_s]`, und die Koordinaten der Phasen stehen in der
 * Einsatzhülle (`ort_huelle`) statt in `phases[]` ([Geraetehuelle]). Klartext
 * weist der Server mit `400 klartext` ab.
 */
object Sendekoerper {

    /**
     * @param seqVon erste gesendete Sequenznummer (`track.seq_from`)
     * @param punkte die Punkte ab [seqVon], in Reihenfolge
     * @param phasen nur für Einsätze; die **vollständige** Liste (Vertrag 3)
     * @param kontoschluessel der öffentliche Schlüssel des Kontos, SEC1 (65 Byte)
     */
    fun baue(
        paket: Paketzeile,
        seqVon: Long,
        punkte: List<Rohpunkt>,
        phasen: List<Phaseneintrag> = emptyList(),
        kontoschluessel: ByteArray,
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
            /* DIE EINSATZHÜLLE GEHT MIT JEDER ANFRAGE, zusammen mit der
             * Phasenliste: Der Server ersetzt beide nach derselben Regel
             * (Vertrag 3.1), und eine Hülle, die zu einer anderen Liste
             * gehört, zeigte im Browser die falschen Orte. */
            put("ort_huelle", Geraetehuelle.einsatz(paket.clientRef, paket.begonnenAt, phasen, kontoschluessel))
            /* `resus_sessions` wird NICHT gesendet — und zwar gar nicht, statt
             * als leere Liste. Die Reanimation bleibt bei der Garmin, das
             * Handy dokumentiert sie nicht. Eine leere Liste
             * hieße „es gibt keine"; der Server ließe den vorhandenen Stand
             * dann zwar stehen (Vertrag 3.1) und meldete es als `kept_resus`
             * — aber gemeldet würde etwas, das nie eine Aussage war. Ein
             * fehlender Schlüssel heißt „dazu sage ich nichts", und genau das
             * ist die Wahrheit. */
        }

        /* OHNE PUNKTE KEIN `track`: Ein Stück hat 1 bis 2000 Punkte
         * (Vertrag 3.2). Der Server antwortet dann mit seiner eigenen Marke
         * (`next_seq`), und an der Warteschlange ändert sich nichts. */
        if (punkte.isNotEmpty()) {
            val art = if (paket.art == Paketzeile.ART_EINSATZ) "spur" else "ruhe"
            put("track", Geraetehuelle.spurstueck(art, paket.clientRef, seqVon, punkte, kontoschluessel))
        }
    }

    private fun phasenArray(phasen: List<Phaseneintrag>): JSONArray =
        JSONArray().apply {
            for (p in phasen) {
                put(
                    JSONObject().apply {
                        put("phase", p.nummer)
                        put("at", p.at)
                        // Die Koordinate steht in der Einsatzhülle,
                        // nicht hier (Vertrag 3).
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
