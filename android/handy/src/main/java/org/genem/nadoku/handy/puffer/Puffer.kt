package org.genem.nadoku.handy.puffer

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt

/**
 * Der Puffer: alles, was noch nicht bestätigt beim Server liegt (E-S4-06).
 *
 * SQLITE ALS BORDMITTEL, ohne ORM (E-S4-04). Was hier gebraucht wird, sind
 * vier Tabellen, zwei Indizes und ein Dutzend Abfragen — dafür eine
 * Bibliothek mitzunehmen hieße, sie in `docs/Lizenzen.md` zu führen und bei
 * jeder Sicherheitsmeldung zu verfolgen.
 *
 * WARUM ÜBERHAUPT EINE DATENBANK und nicht eine Datei je Paket: Weil der
 * Puffer die eigentliche Zusicherung der App ist. Ein Dienst läuft zwölf
 * Stunden; in dieser Zeit stürzt die App ab, wird das Handy neu gestartet,
 * geht der Akku zur Neige. Was aufgezeichnet ist, muss das überleben — und
 * zwar **jeder einzelne Punkt**, nicht der Stand des letzten Speicherns.
 * SQLite schreibt jede Zeile sofort und dauerhaft; das ist genau die Zusage.
 *
 * DIE SPUR WÄCHST NUR. Punkte werden **angehängt**, nie ersetzt (Vertrag 2) —
 * und gelöscht wird ein Paket erst, wenn es `final` ist **und** der Server
 * alle Punkte bestätigt hat (`bestaetigt_seq >= Punktzahl`). Das ist dieselbe
 * Regel wie in `Uploader.mc`, und sie ist der Grund, warum ein Funkabriss
 * nichts kostet.
 */
class Puffer(kontext: Context, name: String = DATEINAME) :
    SQLiteOpenHelper(kontext, name, null, FASSUNG) {

    override fun onCreate(db: SQLiteDatabase) {
        db.execSQL(
            """
            CREATE TABLE dienst (
                id           INTEGER PRIMARY KEY AUTOINCREMENT,
                dienst_ref   TEXT    NOT NULL UNIQUE,
                tag          TEXT    NOT NULL,
                begonnen_at  TEXT    NOT NULL,
                beendet_at   TEXT,
                modus        TEXT    NOT NULL
            )
            """.trimIndent()
        )
        db.execSQL(
            """
            CREATE TABLE paket (
                id                   INTEGER PRIMARY KEY AUTOINCREMENT,
                client_ref           TEXT    NOT NULL UNIQUE,
                art                  TEXT    NOT NULL,
                tag                  TEXT    NOT NULL,
                dienst_ref           TEXT,
                begonnen_at          TEXT    NOT NULL,
                beendet_at           TEXT,
                final                INTEGER NOT NULL DEFAULT 0,
                strecke_m            INTEGER,
                anstieg_m            INTEGER,
                bestaetigt_seq       INTEGER NOT NULL DEFAULT 0,
                metadaten_bestaetigt INTEGER NOT NULL DEFAULT 0,
                fehlerhaft           INTEGER NOT NULL DEFAULT 0
            )
            """.trimIndent()
        )
        /* Die Spurpunkte. Der zusammengesetzte Primärschlüssel (Paket, seq)
         * ist die Idempotenz im Kleinen: Derselbe Punkt zweimal einzufügen
         * ist unmöglich, nicht bloß unwahrscheinlich. */
        db.execSQL(
            """
            CREATE TABLE punkt (
                paket_id INTEGER NOT NULL,
                seq      INTEGER NOT NULL,
                breite   REAL    NOT NULL,
                laenge   REAL    NOT NULL,
                hoehe    REAL,
                zeit     INTEGER NOT NULL,
                PRIMARY KEY (paket_id, seq)
            )
            """.trimIndent()
        )
        /* Phasen (B5). Sie stehen schon hier, weil eine Schemaänderung an
         * einem Puffer, in dem ein laufender Dienst liegt, teurer ist als
         * eine Tabelle, die eine Fassung lang leer bleibt.
         *
         * KEIN UNIQUE AUF (paket, nummer): Mehrfache Einträge derselben
         * Phase sind ausdrücklich erlaubt und bleiben erhalten (Vertrag 3) —
         * eine erneut gesetzte Phase ist eine Korrektur und damit eine
         * Information. Kein Client und kein Schreibweg darf sie entdoppeln. */
        db.execSQL(
            """
            CREATE TABLE phase (
                id       INTEGER PRIMARY KEY AUTOINCREMENT,
                paket_id INTEGER NOT NULL,
                nummer   INTEGER NOT NULL,
                at       TEXT    NOT NULL,
                breite   REAL,
                laenge   REAL,
                quelle   TEXT    NOT NULL
            )
            """.trimIndent()
        )
        db.execSQL("CREATE INDEX idx_paket_offen ON paket (final, fehlerhaft)")
        db.execSQL("CREATE INDEX idx_phase_paket ON phase (paket_id)")
    }

    override fun onUpgrade(db: SQLiteDatabase, alt: Int, neu: Int) {
        /* Es gibt noch keine zweite Fassung. Wenn es eine gibt, wird hier
         * migriert und NICHT gelöscht: Im Puffer liegt der einzige Ort, an
         * dem eine noch nicht gesendete Aufzeichnung existiert. */
        throw IllegalStateException(
            "Puffer-Schema $alt -> $neu: Es gibt noch keine Migration, und " +
                "Löschen ist keine. Hier liegt die einzige Kopie ungesendeter Daten."
        )
    }

    // ---- Dienst ------------------------------------------------------------

    fun dienstBeginnen(dienstRef: String, tag: String, begonnenAt: String, modus: String): Long =
        writableDatabase.insertOrThrow(
            "dienst", null,
            ContentValues().apply {
                put("dienst_ref", dienstRef)
                put("tag", tag)
                put("begonnen_at", begonnenAt)
                put("modus", modus)
            },
        )

    fun dienstBeenden(dienstRef: String, beendetAt: String) {
        writableDatabase.update(
            "dienst", ContentValues().apply { put("beendet_at", beendetAt) },
            "dienst_ref = ?", arrayOf(dienstRef),
        )
    }

    fun modusSetzen(dienstRef: String, modus: String) {
        writableDatabase.update(
            "dienst", ContentValues().apply { put("modus", modus) },
            "dienst_ref = ?", arrayOf(dienstRef),
        )
    }

    /**
     * Der laufende Dienst — der einzige ohne `beendet_at`.
     *
     * DAS IST DIE WIEDERAUFNAHME nach einem Absturz oder einem Neustart des
     * Handys: Der Zustand steht nicht im Arbeitsspeicher, sondern hier. Wer
     * die App neu startet, findet den Dienst vor, in dem er steckt.
     */
    fun laufenderDienst(): Dienstzeile? =
        readableDatabase.rawQuery(
            "SELECT id, dienst_ref, tag, begonnen_at, modus FROM dienst " +
                "WHERE beendet_at IS NULL ORDER BY id DESC LIMIT 1", null,
        ).use { c ->
            if (!c.moveToFirst()) null
            else Dienstzeile(c.getLong(0), c.getString(1), c.getString(2), c.getString(3), c.getString(4))
        }

    // ---- Pakete ------------------------------------------------------------

    fun paketAnlegen(
        clientRef: String, art: String, tag: String, dienstRef: String?, begonnenAt: String,
    ): Long = writableDatabase.insertOrThrow(
        "paket", null,
        ContentValues().apply {
            put("client_ref", clientRef)
            put("art", art)
            put("tag", tag)
            put("dienst_ref", dienstRef)
            put("begonnen_at", begonnenAt)
        },
    )

    fun paketSchliessen(paketId: Long, beendetAt: String, streckeM: Int?, anstiegM: Int?) {
        writableDatabase.update(
            "paket",
            ContentValues().apply {
                put("beendet_at", beendetAt)
                put("final", 1)
                if (streckeM != null) put("strecke_m", streckeM)
                if (anstiegM != null) put("anstieg_m", anstiegM)
            },
            "id = ?", arrayOf(paketId.toString()),
        )
    }

    fun paket(paketId: Long): Paketzeile? =
        readableDatabase.rawQuery(PAKET_SPALTEN + " WHERE id = ?", arrayOf(paketId.toString()))
            .use { c -> if (c.moveToFirst()) zuPaket(c) else null }

    fun paketNach(clientRef: String): Paketzeile? =
        readableDatabase.rawQuery(PAKET_SPALTEN + " WHERE client_ref = ?", arrayOf(clientRef))
            .use { c -> if (c.moveToFirst()) zuPaket(c) else null }

    /** Das offene (nicht `final`e) Paket einer Art im laufenden Dienst. */
    fun offenesPaket(art: String): Paketzeile? =
        readableDatabase.rawQuery(
            PAKET_SPALTEN + " WHERE art = ? AND final = 0 ORDER BY id DESC LIMIT 1", arrayOf(art),
        ).use { c -> if (c.moveToFirst()) zuPaket(c) else null }

    // ---- Punkte ------------------------------------------------------------

    /**
     * Hängt einen Punkt an. Die Sequenznummer vergibt der Puffer selbst —
     * sie ist die Stelle im Track und muss lückenlos sein (Vertrag 2).
     *
     * @return die vergebene Sequenznummer
     */
    fun punktAnhaengen(paketId: Long, p: Rohpunkt): Long {
        val db = writableDatabase
        val seq = punktzahl(paketId)
        db.insertOrThrow(
            "punkt", null,
            ContentValues().apply {
                put("paket_id", paketId)
                put("seq", seq)
                put("breite", p.breite)
                put("laenge", p.laenge)
                if (p.hoehe != null) put("hoehe", p.hoehe)
                put("zeit", p.zeit)
            },
        )
        return seq
    }

    fun punktzahl(paketId: Long): Long =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM punkt WHERE paket_id = ?", arrayOf(paketId.toString()),
        ).use { c -> if (c.moveToFirst()) c.getLong(0) else 0L }

    /** Punkte ab `vonSeq`, höchstens `hoechstens` Stück — für das Senden (B4). */
    fun punkte(paketId: Long, vonSeq: Long, hoechstens: Int): List<Rohpunkt> =
        readableDatabase.rawQuery(
            "SELECT breite, laenge, hoehe, zeit FROM punkt WHERE paket_id = ? AND seq >= ? " +
                "ORDER BY seq LIMIT ?",
            arrayOf(paketId.toString(), vonSeq.toString(), hoechstens.toString()),
        ).use { c ->
            buildList {
                while (c.moveToNext()) {
                    add(
                        Rohpunkt(
                            breite = c.getDouble(0),
                            laenge = c.getDouble(1),
                            hoehe = if (c.isNull(2)) null else c.getDouble(2),
                            zeit = c.getLong(3),
                        )
                    )
                }
            }
        }

    private companion object {
        const val DATEINAME = "puffer.db"
        const val FASSUNG = 1

        const val PAKET_SPALTEN =
            "SELECT id, client_ref, art, tag, dienst_ref, begonnen_at, beendet_at, final, " +
                "strecke_m, anstieg_m, bestaetigt_seq, metadaten_bestaetigt, fehlerhaft FROM paket"

        fun zuPaket(c: android.database.Cursor) = Paketzeile(
            id = c.getLong(0),
            clientRef = c.getString(1),
            art = c.getString(2),
            tag = c.getString(3),
            dienstRef = if (c.isNull(4)) null else c.getString(4),
            begonnenAt = c.getString(5),
            beendetAt = if (c.isNull(6)) null else c.getString(6),
            final = c.getInt(7) != 0,
            streckeM = if (c.isNull(8)) null else c.getInt(8),
            anstiegM = if (c.isNull(9)) null else c.getInt(9),
            bestaetigtSeq = c.getLong(10),
            metadatenBestaetigt = c.getInt(11) != 0,
            fehlerhaft = c.getInt(12) != 0,
        )
    }
}

data class Dienstzeile(
    val id: Long,
    val dienstRef: String,
    val tag: String,
    val begonnenAt: String,
    val modus: String,
)

data class Paketzeile(
    val id: Long,
    val clientRef: String,
    val art: String,
    val tag: String,
    val dienstRef: String?,
    val begonnenAt: String,
    val beendetAt: String?,
    val final: Boolean,
    val streckeM: Int?,
    val anstiegM: Int?,
    val bestaetigtSeq: Long,
    val metadatenBestaetigt: Boolean,
    val fehlerhaft: Boolean,
) {
    companion object {
        const val ART_EINSATZ = "mission"
        const val ART_RUHESEGMENT = "rest_segment"
    }
}
