package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.tresor.Absender
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
     * @param fassung2 Absender und Zähler, wenn der Server `huelle: 2` gesagt
     *   hat (seit KA-06c); `null` heißt Fassung 1 wie bisher
     * @param letztesStueck trägt dieses Stück alle noch offenen Punkte? Nur
     *   dann bekommt es bei einem abgeschlossenen Paket den Merker SCHLUSS
     */
    fun baue(
        paket: Paketzeile,
        seqVon: Long,
        punkte: List<Rohpunkt>,
        phasen: List<Phaseneintrag> = emptyList(),
        kontoschluessel: ByteArray,
        fassung2: Fassung2? = null,
        letztesStueck: Boolean = true,
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
            put(
                "ort_huelle",
                if (fassung2 == null) {
                    Geraetehuelle.einsatz(paket.clientRef, paket.begonnenAt, phasen, kontoschluessel)
                } else {
                    /* JE ANFRAGE EIN NEUES `z` (E-KA-64): Die Hülle wird bei
                     * jeder Anfrage neu verschlossen, und der Zähler steigt
                     * mit — streng, auch in derselben Sekunde. SCHLUSS
                     * trägt sie, sobald das Paket abgeschlossen ist. */
                    Geraetehuelle.einsatz2(
                        paket.clientRef, paket.begonnenAt, phasen, kontoschluessel,
                        fassung2.absender, fassung2.zaehler(), schluss = paket.final,
                    )
                },
            )
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
        val art = if (paket.art == Paketzeile.ART_EINSATZ) "spur" else "ruhe"
        if (punkte.isNotEmpty()) {
            put(
                "track",
                if (fassung2 == null) {
                    Geraetehuelle.spurstueck(art, paket.clientRef, seqVon, punkte, kontoschluessel)
                } else {
                    /* SCHLUSS NUR AM LETZTEN STÜCK eines abgeschlossenen
                     * Pakets: Ein Paket kann final sein, während noch ein
                     * zweites Stück mit Punkten folgt (2000 je Stück). */
                    Geraetehuelle.spurstueck2(
                        art, paket.clientRef, seqVon, punkte, kontoschluessel, fassung2.absender,
                        schluss = paket.final && letztesStueck,
                    )
                },
            )
        } else if (fassung2 != null && paket.final) {
            /* DAS LEERE SCHLUSSSTÜCK (Vertrag 3.4): Ist beim Abschluss kein
             * Punkt mehr offen, sagt ein Stück ohne Punkte, dass die Spur
             * hier zu Ende ist. Ohne es liest der Browser eine Spur, deren
             * letztes Stück ohne SCHLUSS kam, als möglicherweise gekürzt. */
            val ende = paket.beendetAt?.let { runCatching { java.time.Instant.parse(it).epochSecond }.getOrNull() }
                ?: fassung2.jetzt()
            put(
                "track",
                Geraetehuelle.schlussstueck2(
                    art, paket.clientRef, seqVon, ende, kontoschluessel, fassung2.absender,
                ),
            )
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

/**
 * Was Fassung 2 zum Verschließen braucht (seit KA-06c): den Absender — den
 * Griff auf den Geräteschlüssel, nicht den Schlüssel (seit KA-06f) — und den
 * Zähler der Einsatzhülle. Die Uhr nur für das leere Schlussstück eines
 * Pakets ohne Endzeit.
 */
class Fassung2(
    val absender: Absender,
    val zaehler: () -> Long,
    val jetzt: () -> Long = { System.currentTimeMillis() / 1000 },
)

/** Ein Phasen-Zeitstempel, wie er in `phases[]` steht. */
data class Phaseneintrag(
    val nummer: Int,
    val at: String,
    val breite: Double?,
    val laenge: Double?,
)
