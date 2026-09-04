package org.genem.nadoku.handy.senden

import org.genem.nadoku.handy.kopplung.Netzantwort
import org.genem.nadoku.handy.kopplung.Netzweg
import org.genem.nadoku.handy.kopplung.Serveradresse
import org.genem.nadoku.handy.puffer.Paketzeile
import org.genem.nadoku.handy.puffer.Puffer
import org.genem.nadoku.handy.tresor.Schluesseltresor

/**
 * Was ein Sendelauf ergeben hat — für die Anzeige und für das Prüfprotokoll.
 */
data class Sendebericht(
    val anfragen: Int = 0,
    val gesendetePunkte: Int = 0,
    val fertigePakete: Int = 0,
    /** Verworfene Einzelwerte über alle Anfragen (Vertrag 5). */
    val verworfen: Map<String, Int> = emptyMap(),
    /** Übergangene Listen über alle Anfragen. */
    val uebergangen: Map<String, Int> = emptyMap(),
    /** 400-Pakete dieses Laufs — sie werden nicht wiederholt. */
    val fehlerhaft: Int = 0,
    /** 401: Der Schlüssel wurde abgewiesen; der Lauf ist gestoppt. */
    val pausiert: Boolean = false,
    /** Kein Netz oder 5xx — später unverändert erneut. */
    val spaeterErneut: Boolean = false,
) {
    /** Ein Lauf, an dem nichts zu beanstanden ist. */
    val sauber: Boolean
        get() = verworfen.isEmpty() && uebergangen.isEmpty() && fehlerhaft == 0 && !pausiert
}

/**
 * Die Warteschlange — **wortgleich der Uhr** (`Uploader.mc`, E-S4-06).
 *
 * ```
 * 1. abgeschlossene Einsätze        (sie tragen die Dokumentation)
 * 2. abgeschlossene Ruhesegmente
 * 3. das laufende Paket             (Teil-Upload)
 * ```
 *
 * JE PAKET WIRD IN TEILSTÜCKEN GESENDET, höchstens [CHUNK_PUNKTE] Punkte je
 * Anfrage (Vertrag 6). Nach jeder Antwort merkt sich die App die bestätigte
 * `next_seq` und sendet beim nächsten Mal ab dort weiter. **Gelöscht wird
 * erst nach `final` und vollständiger Bestätigung** — das ist der Grund,
 * warum ein Funkabriss nichts kostet.
 *
 * DIE FEHLERPFADE STEHEN WÖRTLICH IM VERTRAG (5), und jeder verlangt etwas
 * anderes:
 *
 * | | |
 * |---|---|
 * | 400 | Nachricht fehlerhaft — **nicht wiederholen**, lokal markieren |
 * | 401 | Schlüssel ungültig — **pausieren** und anzeigen |
 * | 413 | Chunk zu groß — **halbieren** und wiederholen |
 * | 5xx | später **unverändert** erneut |
 *
 * Der Unterschied ist nicht theoretisch: Ein 400, den man wiederholt, läuft
 * ewig; ein 401, über den man hinweggeht, sendet den Rest des Dienstes ins
 * Leere; ein 413, den man nicht halbiert, bleibt für immer zu groß.
 *
 * **Die Chunk-Größe bleibt halbiert**, wenn sie einmal halbiert wurde — wie
 * bei der Uhr. Sie wieder hochzusetzen hieße, denselben 413 noch einmal zu
 * provozieren; die Ersparnis wäre eine Anfrage, der Preis eine verlorene.
 */
class Sender(
    private val puffer: Puffer,
    private val netzweg: Netzweg,
    private val tresor: Schluesseltresor,
    /**
     * Die Basisadresse. Seit R63 eine Konstante des Baulaufs und deshalb kein
     * Lambda mehr: Vorher konnte sie sich zwischen zwei Sendeläufen ändern
     * (die NutzerIn tippte eine neue ein), heute nicht. Der Parameter bleibt,
     * damit der Prüfstand gegen seine örtliche Installation senden kann.
     */
    private val basis: String = Serveradresse.BASIS,
    private val phasenLeser: (Long) -> List<Phaseneintrag> = { emptyList() },
) {

    /** Aktuelle Chunk-Größe; sie halbiert sich bei 413 und bleibt es. */
    var chunkPunkte: Int = CHUNK_PUNKTE
        private set

    /**
     * Die Warteschlange abarbeiten, bis nichts mehr offen ist oder ein
     * Fehlerpfad den Lauf beendet.
     */
    fun sendeAlles(): Sendebericht {
        val adresse = Serveradresse.ingest(basis)
        val zugang = tresor.lesen()
            ?: return Sendebericht(spaeterErneut = true)

        val kopfzeilen = mapOf(
            "X-Device-Id" to zugang.geraeteKennung,
            "X-Api-Key" to zugang.schluessel,
        )

        var bericht = Sendebericht()
        /* Eine Obergrenze für die Anfragen eines Laufs. Sie ist kein Zaun
         * gegen den Normalfall — ein 12-h-Dienst braucht rund zwanzig —,
         * sondern gegen den Fall, in dem der Server dauerhaft `next_seq: 0`
         * antwortet: Dann käme die Schleife nie voran und liefe endlos. */
        var verbleibendeAnfragen = HOECHSTENS_ANFRAGEN

        while (verbleibendeAnfragen-- > 0) {
            val paket = naechstesPaket() ?: break
            val ergebnis = sendeTeilstueck(paket, adresse, kopfzeilen)
            bericht = vereine(bericht, ergebnis)
            if (ergebnis.pausiert || ergebnis.spaeterErneut) break
        }
        return bericht
    }

    private fun naechstesPaket(): Paketzeile? =
        puffer.warteschlange().firstOrNull { puffer.hatArbeit(it) }

    private fun sendeTeilstueck(
        paket: Paketzeile,
        adresse: String,
        kopfzeilen: Map<String, String>,
    ): Sendebericht {
        val seqVon = paket.bestaetigtSeq
        val punkte = puffer.punkte(paket.id, seqVon, chunkPunkte)
        val phasen = if (paket.art == Paketzeile.ART_EINSATZ) phasenLeser(paket.id) else emptyList()
        val koerper = Sendekoerper.baue(paket, seqVon, punkte, phasen).toString()

        val antwort = when (val a = netzweg.postJson(adresse, koerper, kopfzeilen)) {
            is Netzantwort.KeineVerbindung -> Sendeantwort.KeineVerbindung(a.ursache)
            is Netzantwort.Server -> Sendeantwort.lese(a.code, a.koerper)
        }

        return when (antwort) {
            is Sendeantwort.Angekommen -> {
                puffer.bestaetigungMerken(paket.id, antwort.naechsteSeq)
                var fertig = 0
                /* AUFRÄUMEN ERST NACH `final` UND VOLLSTÄNDIGER BESTÄTIGUNG.
                 * `next_seq` ist die erste NICHT gespeicherte Nummer — sie
                 * muss also die Punktzahl erreichen, nicht überschreiten. */
                if (paket.final && antwort.naechsteSeq >= puffer.punktzahl(paket.id)) {
                    puffer.paketEntsorgen(paket.id)
                    fertig = 1
                }
                Sendebericht(
                    anfragen = 1,
                    gesendetePunkte = punkte.size,
                    fertigePakete = fertig,
                    verworfen = antwort.verworfen,
                    uebergangen = antwort.uebergangen,
                )
            }

            is Sendeantwort.Fehlerhaft -> {
                puffer.alsFehlerhaftMerken(paket.id)
                Sendebericht(anfragen = 1, fehlerhaft = 1)
            }

            is Sendeantwort.SchluesselAbgewiesen ->
                Sendebericht(anfragen = 1, pausiert = true)

            is Sendeantwort.ZuGross -> {
                chunkPunkte = maxOf(1, chunkPunkte / 2)
                // Kein Abbruch: Der nächste Durchgang nimmt dasselbe Paket
                // mit der halbierten Größe noch einmal.
                Sendebericht(anfragen = 1)
            }

            is Sendeantwort.SpaeterErneut, is Sendeantwort.KeineVerbindung ->
                Sendebericht(anfragen = 1, spaeterErneut = true)
        }
    }

    private fun vereine(a: Sendebericht, b: Sendebericht) = Sendebericht(
        anfragen = a.anfragen + b.anfragen,
        gesendetePunkte = a.gesendetePunkte + b.gesendetePunkte,
        fertigePakete = a.fertigePakete + b.fertigePakete,
        verworfen = zaehleZusammen(a.verworfen, b.verworfen),
        uebergangen = zaehleZusammen(a.uebergangen, b.uebergangen),
        fehlerhaft = a.fehlerhaft + b.fehlerhaft,
        pausiert = a.pausiert || b.pausiert,
        spaeterErneut = a.spaeterErneut || b.spaeterErneut,
    )

    private fun zaehleZusammen(a: Map<String, Int>, b: Map<String, Int>): Map<String, Int> =
        buildMap {
            putAll(a)
            for ((k, v) in b) put(k, (get(k) ?: 0) + v)
        }

    companion object {
        /** Richtwert des Vertrags (6): höchstens 500 Punkte je Anfrage. */
        const val CHUNK_PUNKTE = 500

        /** Harte Grenze des Servers: 512 KB Körper (Vertrag 6). */
        const val HOECHSTE_KOERPERGROESSE = 512 * 1024

        private const val HOECHSTENS_ANFRAGEN = 500
    }
}
