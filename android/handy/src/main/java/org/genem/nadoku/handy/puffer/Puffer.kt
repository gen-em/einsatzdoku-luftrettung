package org.genem.nadoku.handy.puffer

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper
import androidx.core.database.sqlite.transaction
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.senden.Phaseneintrag

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
        uhrTabellen(db)
    }

    /**
     * Die Buchführung über die Ereignisse der Uhr (C2, E-S4-10).
     *
     * ZWEI TABELLEN UND NICHT EINE: `uhr_stand` führt je Uhr die höchste
     * **lückenlos** übernommene Nummer, `uhr_ereignis` die vereinzelten
     * darüber. Kommt Nr. 7, während Nr. 6 fehlt, wandert der Stand nicht
     * mit — sonst dürfte die Uhr Nr. 6 löschen, und niemand sähe je, dass
     * sie fehlt. Sobald 6 nachkommt, rückt der Stand auf 7 und beide Zeilen
     * verschwinden. Die Tabelle bleibt damit klein, ohne dass ein Vergessen
     * droht.
     *
     * `uhr_id` STEHT DABEI, WEIL DIE NUMMER ALLEIN NICHT REICHT: Wird die Uhr
     * zurückgesetzt, fängt ihr Zähler wieder bei 1 an. Ohne die Kennung hielte
     * das Handy jedes Ereignis der neu eingerichteten Uhr für eine
     * Doppelzustellung und verwürfe es **stillschweigend** — der schlimmste
     * aller Fehler, weil niemand ihn bemerkt.
     */
    private fun uhrTabellen(db: SQLiteDatabase) {
        db.execSQL(
            """
            CREATE TABLE IF NOT EXISTS uhr_ereignis (
                uhr_id TEXT    NOT NULL,
                nr     INTEGER NOT NULL,
                PRIMARY KEY (uhr_id, nr)
            )
            """.trimIndent()
        )
        db.execSQL(
            """
            CREATE TABLE IF NOT EXISTS uhr_stand (
                uhr_id TEXT    PRIMARY KEY,
                bis_nr INTEGER NOT NULL
            )
            """.trimIndent()
        )
    }

    /**
     * Migriert — und löscht **nie**. Im Puffer liegt der einzige Ort, an dem
     * eine noch nicht gesendete Aufzeichnung existiert; ein `DROP TABLE` beim
     * App-Update wäre der stillste denkbare Datenverlust.
     *
     * 1 → 2 (C2): die beiden Tabellen der Uhr-Buchführung kommen hinzu. Sie
     * sind leer und hängen an nichts — die Migration kann deshalb nichts
     * verlieren.
     */
    override fun onUpgrade(db: SQLiteDatabase, alt: Int, neu: Int) {
        var stand = alt
        if (stand == 1) {
            uhrTabellen(db)
            stand = 2
        }
        if (stand != neu) {
            throw IllegalStateException(
                "Puffer-Schema $alt -> $neu: Für $stand -> $neu gibt es keine " +
                    "Migration, und Löschen ist keine. Hier liegt die einzige " +
                    "Kopie ungesendeter Daten."
            )
        }
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

    // ---- Phasen (B5; die Tabelle steht seit B3) ----------------------------

    /**
     * Alle Phasen eines Einsatzes, **in der Reihenfolge ihres Entstehens**.
     *
     * MEHRFACHE EINTRÄGE DERSELBEN NUMMER BLEIBEN ERHALTEN (Vertrag 3): Eine
     * erneut gesetzte Phase ist eine Korrektur und damit eine Information.
     * Hier wird deshalb nicht gruppiert und nicht entdoppelt — der Vertrag
     * verbietet es ausdrücklich, und der Server ersetzt die Liste ohnehin
     * vollständig.
     */
    fun phasen(paketId: Long): List<Phaseneintrag> =
        readableDatabase.rawQuery(
            "SELECT nummer, at, breite, laenge FROM phase WHERE paket_id = ? ORDER BY id",
            arrayOf(paketId.toString()),
        ).use { c ->
            buildList {
                while (c.moveToNext()) {
                    add(
                        Phaseneintrag(
                            nummer = c.getInt(0),
                            at = c.getString(1),
                            breite = if (c.isNull(2)) null else c.getDouble(2),
                            laenge = if (c.isNull(3)) null else c.getDouble(3),
                        )
                    )
                }
            }
        }

    /**
     * Der Spurpunkt, der einem Zeitpunkt am nächsten liegt — für die
     * Phasen-Koordinate (E-S4-10).
     *
     * Gesucht wird über **alle Pakete dieses Dienstes**, nicht nur über das
     * eine: Eine Phase, die den Einsatz startet, trägt einen Zeitstempel aus
     * einem Augenblick, in dem der letzte Punkt noch im **Ruhesegment** lag.
     * Wer nur im Einsatz suchte, fände dort nichts und schriebe `null` — und
     * die Koordinate wäre für immer weg.
     *
     * `null`, wenn nichts innerhalb der Toleranz liegt. Der Vertrag erlaubt
     * das ausdrücklich (`lat`/`lon` dürfen null sein) — eine erfundene
     * Koordinate wäre schlimmer als keine.
     */
    fun punktNaheZeit(dienstRef: String?, zeit: Long, toleranzS: Long): Rohpunkt? =
        /* DIE GRENZEN STEHEN AN DER SPALTE, NICHT IN EINEM AUSDRUCK — und das
         * ist kein Stilfrage, sondern die Behebung eines Fehlers, den ein
         * Prüffall gefangen hat.
         *
         * `rawQuery` bindet jeden Wert als TEXT. Vergleicht SQLite eine SPALTE
         * mit Zahlen-Affinität gegen einen Text, wandelt es den Text vorher in
         * eine Zahl um — `p.zeit >= '1784279400'` tut also das Erwartete. Ein
         * AUSDRUCK wie `ABS(p.zeit - ?)` hat aber KEINE Affinität, und dann
         * gilt SQLites Typordnung: **Text ist immer größer als eine Zahl.**
         * `ABS(...) <= '30'` war damit IMMER wahr, und jede Phase bekam die
         * Koordinate des nächstbesten Punktes — auch die einer Stunde später.
         *
         * Deshalb: filtern an der Spalte, und die Zahl fürs Sortieren
         * ausdrücklich mit CAST. */
        readableDatabase.rawQuery(
            "SELECT p.breite, p.laenge, p.hoehe, p.zeit FROM punkt p " +
                "JOIN paket k ON k.id = p.paket_id " +
                "WHERE (k.dienst_ref = ? OR (? IS NULL AND k.dienst_ref IS NULL)) " +
                "AND p.zeit >= ? AND p.zeit <= ? " +
                "ORDER BY ABS(p.zeit - CAST(? AS INTEGER)) LIMIT 1",
            arrayOf(
                dienstRef, dienstRef,
                (zeit - toleranzS).toString(), (zeit + toleranzS).toString(),
                zeit.toString(),
            ),
        ).use { c ->
            if (!c.moveToFirst()) null
            else Rohpunkt(
                breite = c.getDouble(0), laenge = c.getDouble(1),
                hoehe = if (c.isNull(2)) null else c.getDouble(2),
                zeit = c.getLong(3),
            )
        }

    /**
     * Eine Phase anhängen — und den Datensatz damit **wieder sendepflichtig
     * machen**.
     *
     * DAS ZURÜCKNEHMEN DER BESTÄTIGUNG IST DER PUNKT, nicht ein Nebenzug. Ein
     * laufender Einsatz wird während des Dienstes in Teilen hochgeladen; danach
     * steht `metadaten_bestaetigt = 1`. Kommt jetzt eine Phase dazu, hat der
     * Server einen **veralteten** Stand — und [hatArbeit] sähe das nicht, weil
     * es nur Punkte zählt. Steht das Fahrzeug (kein neuer Punkt), bliebe die
     * Phase liegen, bis der Einsatz abgeschlossen wird; im
     * Nur-Aufzeichnen-Betrieb sogar bis zum Dienstende.
     *
     * Der Fehler stammt aus B5 und ist in C2 aufgefallen, als die Abnahme
     * verlangte, dass beim Phasenkonflikt Uhr/Handy **beide Einträge gesendet**
     * werden (Fund B-S4-05). Gesendet, nicht nur gespeichert.
     */
    fun phaseAnhaengen(paketId: Long, nummer: Int, at: String, breite: Double?, laenge: Double?, quelle: String) {
        writableDatabase.transaction {
            insertOrThrow(
                "phase", null,
                ContentValues().apply {
                    put("paket_id", paketId)
                    put("nummer", nummer)
                    put("at", at)
                    if (breite != null) put("breite", breite)
                    if (laenge != null) put("laenge", laenge)
                    put("quelle", quelle)
                },
            )
            update(
                "paket", ContentValues().apply { put("metadaten_bestaetigt", 0) },
                "id = ?", arrayOf(paketId.toString()),
            )
        }
    }

    /** Die Quelle einer Phase (`handy`/`uhr`) — nur für die Prüfung. */
    fun phasenquellen(paketId: Long): List<String> =
        readableDatabase.rawQuery(
            "SELECT quelle FROM phase WHERE paket_id = ? ORDER BY id", arrayOf(paketId.toString()),
        ).use { c -> buildList { while (c.moveToNext()) add(c.getString(0)) } }

    // ---- Buchführung des Sendens (B4) --------------------------------------

    /**
     * Was der Server bestätigt hat.
     *
     * `next_seq` ist die **erste noch nicht gespeicherte** Sequenznummer
     * (Vertrag 5) — ab ihr sendet die App beim nächsten Mal weiter, und alles
     * davor darf sie lokal verwerfen. `metadatenBestaetigt` merkt, dass der
     * Datensatz überhaupt einmal angekommen ist: Ein Paket ohne Punkte
     * (ein Einsatz, der nur Phasen trägt) hätte sonst nie „Arbeit erledigt".
     */
    fun bestaetigungMerken(paketId: Long, naechsteSeq: Long) {
        writableDatabase.update(
            "paket",
            ContentValues().apply {
                put("bestaetigt_seq", naechsteSeq)
                put("metadaten_bestaetigt", 1)
            },
            "id = ?", arrayOf(paketId.toString()),
        )
    }

    /**
     * 400 vom Server: Die Nachricht ist fehlerhaft und wird **nicht
     * wiederholt** (Vertrag 5). Sie bleibt liegen — gelöscht wird sie nicht,
     * weil dann niemand mehr sähe, dass etwas nicht angekommen ist.
     */
    fun alsFehlerhaftMerken(paketId: Long) {
        writableDatabase.update(
            "paket", ContentValues().apply { put("fehlerhaft", 1) },
            "id = ?", arrayOf(paketId.toString()),
        )
    }

    /**
     * Ein vollständig bestätigtes, abgeschlossenes Paket entsorgen.
     *
     * ERST NACH `final` UND VOLLSTÄNDIGER BESTÄTIGUNG — dieselbe Regel wie in
     * `Uploader.mc`. Sie ist der Grund, warum ein Funkabriss nichts kostet:
     * Solange der Server nicht bestätigt hat, liegt alles noch hier.
     */
    fun paketEntsorgen(paketId: Long) {
        /* IN EINER TRANSAKTION. Bräche der Vorgang zwischen den drei
         * Löschungen ab, bliebe ein Paket ohne Punkte stehen — oder, schlimmer,
         * Punkte ohne Paket, die nie wieder jemand sähe. */
        writableDatabase.transaction {
            delete("punkt", "paket_id = ?", arrayOf(paketId.toString()))
            delete("phase", "paket_id = ?", arrayOf(paketId.toString()))
            delete("paket", "id = ?", arrayOf(paketId.toString()))
        }
    }

    /**
     * Die Warteschlange in ihrer Reihenfolge (E-S4-06, wie `Uploader._findJob`):
     *
     *   1. abgeschlossene **Einsätze**
     *   2. abgeschlossene **Ruhesegmente**
     *   3. das laufende Paket (Teil-Upload)
     *
     * WARUM DIE EINSÄTZE ZUERST: Sie tragen die Dokumentation. Ein Segment
     * ist eine Spur; ein Einsatz ist ein Einsatz. Geht die Verbindung nach
     * drei Anfragen wieder verloren, sollen diese drei die wichtigen gewesen
     * sein.
     *
     * Fehlerhafte Pakete (400) sind nicht dabei — sie werden nicht wiederholt.
     */
    fun warteschlange(): List<Paketzeile> =
        readableDatabase.rawQuery(
            PAKET_SPALTEN + " WHERE fehlerhaft = 0 ORDER BY " +
                "final DESC, CASE art WHEN 'mission' THEN 0 ELSE 1 END, id",
            null,
        ).use { c -> buildList { while (c.moveToNext()) add(zuPaket(c)) } }

    /**
     * Der Sende-Rückstand: **nur abgeschlossene**, noch nicht vollständig
     * bestätigte Pakete.
     *
     * Das laufende Segment zählt bewusst nicht mit — sonst stünde während des
     * ganzen Dienstes „Rückstand 1", und die Anzeige verlöre den einen Zweck,
     * den sie hat (Backlog Nr. 11).
     */
    fun rueckstand(): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket p WHERE p.final = 1 AND p.fehlerhaft = 0 AND (" +
                "p.metadaten_bestaetigt = 0 OR " +
                "p.bestaetigt_seq < (SELECT COUNT(*) FROM punkt WHERE paket_id = p.id))",
            null,
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /**
     * Pakete, die der Server mit **400** abgewiesen hat (B-S5Z-06).
     *
     * WARUM SIE EINE EIGENE ZAHL BRAUCHEN. `alsFehlerhaftMerken` nimmt ein
     * Paket aus der Warteschlange **und** aus [rueckstand] — beides zu Recht:
     * Es wird nicht wiederholt (Vertrag 5), und ein Rückstand, der sich nie
     * abbaut, wäre eine Anzeige ohne Aussage. Die Folge war aber, dass es
     * **nirgends** mehr auftauchte: Die App sagte „Alles gesendet", während
     * beim Server ein Segment offen blieb. E-S4-36 hatte die Anzeige dem
     * Paket D1 zugewiesen; dort ist sie nicht entstanden.
     */
    fun abgewiesen(): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket WHERE fehlerhaft = 1", null,
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /** Hat dieses Paket noch etwas zu senden? */
    fun hatArbeit(p: Paketzeile): Boolean =
        !p.metadatenBestaetigt || p.bestaetigtSeq < punktzahl(p.id)

    // ---- Buchführung über die Uhr (C2, E-S4-10) ----------------------------

    /**
     * Führt [block] als **einen** Schreibvorgang aus.
     *
     * Gebraucht wird das genau einmal, und dort ist es entscheidend: Ein
     * Uhr-Ereignis zu wirken **und** es als übernommen zu vermerken, muss
     * ungeteilt geschehen. Bräche der Vorgang dazwischen ab, wäre das
     * Ereignis entweder zweimal gewirkt (Vermerk fehlt, die Uhr liefert nach)
     * oder gar nicht (Vermerk steht, gewirkt wurde nichts) — beides
     * unbemerkbar.
     */
    fun <T> imVorgang(block: () -> T): T = writableDatabase.transaction { block() }

    /**
     * Kennt das Handy dieses Ereignis schon? Dann ist es eine
     * **Doppelzustellung** nach verlorener Quittung — sie wird quittiert und
     * nicht noch einmal gewirkt.
     */
    fun uhrEreignisBekannt(uhrId: String, nr: Long): Boolean {
        if (nr <= uhrStand(uhrId)) return true
        return readableDatabase.rawQuery(
            "SELECT 1 FROM uhr_ereignis WHERE uhr_id = ? AND nr = ?",
            arrayOf(uhrId, nr.toString()),
        ).use { it.moveToFirst() }
    }

    /**
     * Ein Ereignis als übernommen vermerken und den Stand nachziehen.
     *
     * Der Stand rückt nur so weit, wie die Reihe **lückenlos** ist: Nach 5, 7
     * steht er auf 5; kommt 6, springt er auf 7. Was er überholt hat, wird
     * gelöscht — die Einzeltabelle bleibt so klein, ohne dass etwas vergessen
     * wird.
     *
     * @return der neue Stand — genau die Zahl, die in die Quittung gehört.
     */
    fun uhrEreignisMerken(uhrId: String, nr: Long): Long {
        val db = writableDatabase
        db.insertWithOnConflict(
            "uhr_ereignis", null,
            ContentValues().apply {
                put("uhr_id", uhrId)
                put("nr", nr)
            },
            SQLiteDatabase.CONFLICT_IGNORE,
        )
        var stand = uhrStand(uhrId)
        while (db.rawQuery(
                "SELECT 1 FROM uhr_ereignis WHERE uhr_id = ? AND nr = ?",
                arrayOf(uhrId, (stand + 1).toString()),
            ).use { it.moveToFirst() }
        ) {
            stand += 1
        }
        db.insertWithOnConflict(
            "uhr_stand", null,
            ContentValues().apply {
                put("uhr_id", uhrId)
                put("bis_nr", stand)
            },
            SQLiteDatabase.CONFLICT_REPLACE,
        )
        db.delete("uhr_ereignis", "uhr_id = ? AND nr <= ?", arrayOf(uhrId, stand.toString()))
        return stand
    }

    /** Die höchste lückenlos übernommene Nummer dieser Uhr; 0, wenn keine. */
    fun uhrStand(uhrId: String): Long =
        readableDatabase.rawQuery(
            "SELECT bis_nr FROM uhr_stand WHERE uhr_id = ?", arrayOf(uhrId),
        ).use { if (it.moveToFirst()) it.getLong(0) else 0L }

    /** Die vereinzelt übernommenen Nummern oberhalb des Standes — für die Prüfung. */
    fun uhrOffeneNummern(uhrId: String): List<Long> =
        readableDatabase.rawQuery(
            "SELECT nr FROM uhr_ereignis WHERE uhr_id = ? ORDER BY nr", arrayOf(uhrId),
        ).use { c -> buildList { while (c.moveToNext()) add(c.getLong(0)) } }

    private companion object {
        const val DATEINAME = "puffer.db"
        const val FASSUNG = 2

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
