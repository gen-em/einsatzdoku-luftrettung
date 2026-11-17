package org.genem.nadoku.handy.puffer

import android.content.ContentValues
import android.content.Context
import android.database.sqlite.SQLiteDatabase
import android.database.sqlite.SQLiteOpenHelper
import androidx.core.database.sqlite.transaction
import java.util.concurrent.atomic.AtomicLong
import java.io.File
import org.genem.nadoku.handy.aufzeichnung.Rohpunkt
import org.genem.nadoku.handy.senden.DateiZaehlerstand
import org.genem.nadoku.handy.senden.Huellenzaehler
import org.genem.nadoku.handy.senden.Phaseneintrag
import org.genem.nadoku.handy.senden.Zaehlerausfall

/**
 * Der Puffer: alles, was noch nicht bestätigt beim Server liegt.
 *
 * SQLITE ALS BORDMITTEL, ohne ORM. Was hier gebraucht wird, sind
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
 *
 * DER ZÄHLER DER EINSATZHÜLLE (E-KA-69, seit KA-06f, Schema 3). Die Hülle
 * der Fassung 2 trägt einen Zähler `z`, und er gehört zum INHALT des
 * Einsatzes, nicht zum Verschließen ([Huellenzaehler]). Deshalb liegt er
 * hier, in `paket.zaehler`, und wird an genau den Stellen vergeben, an denen
 * sich ändert, was in der Hülle innen steht:
 *
 *   - [paketAnlegen] eines Einsatzes   — `start` entsteht
 *   - [phaseAnhaengen]                 — die Phasenliste wächst (auch über
 *                                        die Uhr: `Uhrannahme` geht denselben Weg)
 *   - [paketSchliessen] eines Einsatzes, der noch offen war — `schluss` kippt
 *
 * Sonst nirgends: Punkte, Strecke, Anstieg, Endzeit und die Buchführung des
 * Sendens stehen nicht in der Einsatzhülle. EIN VERGLEICH DER BYTES VOR DEM
 * VERGEBEN ist deshalb nicht nötig, und er wäre hier nur Schein: Die ersten
 * beiden Wege ändern die Bytes IMMER (ein neuer Einsatz, eine längere
 * Liste), der dritte genau dann, wenn `final` von 0 auf 1 geht — und das
 * fragt er ab. Ein zweites Schließen kostet kein `z`. Wer einen vierten Weg
 * baut, der Beginn, Phasen oder Abschluss eines Einsatzes ändert, vergibt
 * dort ebenfalls ([zaehlerFuerAenderung]) — `EinsatzzaehlerTest` misst
 * „gleiches z ⇒ innen byte-gleich" an den drei, die es gibt.
 *
 * IN DERSELBEN SCHREIBHANDLUNG WIE DIE ÄNDERUNG. Zahl holen (der
 * Gerätezähler legt sie ab, bevor er sie herausgibt), dann Änderung und Zahl
 * in EINER Transaktion. Stirbt der Prozess dazwischen, ist eine Zahl
 * verbraucht und nichts geändert; nie steht ein neuer Inhalt unter einem
 * alten Zähler, und nie vergibt der nächste Lauf eine Zahl noch einmal. Die
 * Zahl wird INNERHALB der Transaktion geholt: So stehen zwei Änderungen
 * desselben Einsatzes in der Reihenfolge ihrer Zähler im Puffer.
 *
 * EIN PAKET OHNE ZÄHLER (`NULL`) heißt: „dieser Stand hat noch keinen".
 * Das gibt es in zwei Lagen — das Paket stammt aus Schema 2 (die Migration
 * vergibt nichts, siehe [onUpgrade]), oder der Gerätezähler ließ sich bei
 * der Änderung nicht ablegen ([Zaehlerausfall]; die Aufzeichnung geht vor,
 * eine Phase geht nicht verloren, weil eine Datei nicht schreibbar ist).
 * Dann vergibt [einsatzstand] beim ERSTEN Verschließen einmal und legt ab;
 * jedes weitere Verschließen liest. Ruhesegmente brauchen keinen.
 */
class Puffer(
    kontext: Context,
    name: String = DATEINAME,
    /**
     * Der eine Zähler dieses Geräts. Vorgabe: die Datei, die auch die App
     * nimmt — ein Puffer ohne Zähler wäre einer, der Einsätze ohne `z`
     * ablegt, und das soll sich nicht versehentlich bauen lassen.
     */
    private val huellenzaehler: Huellenzaehler =
        Huellenzaehler(DateiZaehlerstand(File(kontext.filesDir, DateiZaehlerstand.DATEINAME))),
) : SQLiteOpenHelper(kontext, name, null, FASSUNG) {

    /*
     * DER ÄNDERUNGSZÄHLER. Läse die Hauptansicht jede Sekunde drei Zahlen
     * über SQLite (`rueckstand()`, `abgewiesen()`, `verwerfbar()`) auf dem
     * Hauptfaden, wäre das auf einem alten Gerät mit vollem Puffer ein
     * Ruckeln je Sekunde, obwohl sich die Zahlen nur mit einem
     * Schreibvorgang ändern. Deshalb zählt der Puffer jeden Schreibvorgang, und die Ansicht liest nur nach, wenn
     * [aenderungen] sich bewegt hat.
     *
     * DER ZÄHLER STEIGT NACH DEM SCHREIBEN, nicht davor (`finally` in
     * [schreibe]). Stiege er vorher, könnte die Ansicht zwischen Zählen und
     * Festschreiben nachlesen, den alten Stand sehen und danach stehen
     * bleiben, weil sich der Zähler nicht mehr bewegt. Eine Transaktion
     * zählt deshalb an ihrem äußeren `schreibe` noch einmal — nach dem
     * Festschreiben.
     *
     * JEDER SCHREIBWEG GEHT ÜBER [schreibe]; `writableDatabase` steht nur
     * dort. Wer einen neuen Schreibweg daneben baut, bekommt eine Anzeige,
     * die erst beim nächsten anderen Schreiben nachzieht.
     *
     * EINE AUSNAHME, MIT GRUND: `punktAnhaengen()` zählt nicht
     * (`zaehlt = false`). Im Dienst kommt ein Punkt fast jede Sekunde; zählte
     * er, liefen die drei Abfragen wieder im Sekundentakt. Ein Punkt landet
     * aber immer in einem OFFENEN Paket, und keine der drei Zahlen sieht ein
     * offenes Paket an (`rueckstand()` zählt nur `final = 1`, die anderen
     * beiden nur abgewiesene). Wer [aenderungen] für etwas anderes liest als
     * diese drei, prüft zuerst, ob ihm Punkte fehlen dürfen.
     */
    private val zaehler = AtomicLong(0)

    /** Wie oft dieser Puffer seit dem Start geschrieben hat. */
    val aenderungen: Long get() = zaehler.get()

    private inline fun <T> schreibe(zaehlt: Boolean = true, block: (SQLiteDatabase) -> T): T =
        try {
            block(writableDatabase)
        } finally {
            if (zaehlt) zaehler.incrementAndGet()
        }

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
                fehlerhaft           INTEGER NOT NULL DEFAULT 0,
                zaehler              INTEGER,
                aenderung            INTEGER NOT NULL DEFAULT 0
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
        /* Phasen.
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
     * Die Buchführung über die Ereignisse der Uhr.
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
     * 1 → 2: die beiden Tabellen der Uhr-Buchführung kommen hinzu. Sie
     * sind leer und hängen an nichts — die Migration kann deshalb nichts
     * verlieren.
     *
     * 2 → 3 (KA-06f, E-KA-69): `paket.zaehler` kommt hinzu, leer. Eine neue
     * Spalte am Ende, keine Zeile wird angefasst — auch diese Migration kann
     * nichts verlieren. VERGEBEN WIRD HIER NICHTS, mit Absicht: Ein Zähler
     * entsteht aus der Datei des Gerätezählers, und eine Migration, die an
     * einer nicht schreibbaren Datei scheitern kann, ließe die App mit dem
     * einzigen Bestand ungesendeter Daten nicht mehr starten. Die
     * vorgefundenen Einsätze bekommen ihren Zähler beim ersten Verschließen
     * ([einsatzstand]) — einmal, abgelegt, danach immer denselben. Er liegt
     * über jedem `z`, das dieses Handy unter der alten Regel (E-KA-64, je
     * Anfrage neu) gesendet hat: Es ist derselbe Gerätezähler, und er steigt
     * streng. Der Browser nimmt den vorgefundenen Stand also an.
     *
     * 3 → 4 (Nr. 566): `paket.aenderung` kommt hinzu, mit 0 für jede
     * vorgefundene Zeile. Wieder eine Spalte am Ende, keine Zeile verliert
     * etwas. 0 ist für den Bestand richtig: Eine Anfrage, deren Antwort noch
     * aussteht, gibt es nach einem Neustart nicht — der Prozess, der sie
     * stellte, ist fort —, also kann keine Bestätigung mit einem älteren
     * Stand mehr eintreffen ([bestaetigungMerken]).
     */
    override fun onUpgrade(db: SQLiteDatabase, alt: Int, neu: Int) {
        var stand = alt
        if (stand == 1) {
            uhrTabellen(db)
            stand = 2
        }
        if (stand == 2) {
            db.execSQL("ALTER TABLE paket ADD COLUMN zaehler INTEGER")
            stand = 3
        }
        if (stand == 3) {
            db.execSQL("ALTER TABLE paket ADD COLUMN aenderung INTEGER NOT NULL DEFAULT 0")
            stand = 4
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
        schreibe { it.insertOrThrow(
            "dienst", null,
            ContentValues().apply {
                put("dienst_ref", dienstRef)
                put("tag", tag)
                put("begonnen_at", begonnenAt)
                put("modus", modus)
            },
        ) }

    fun dienstBeenden(dienstRef: String, beendetAt: String) {
        schreibe { it.update(
            "dienst", ContentValues().apply { put("beendet_at", beendetAt) },
            "dienst_ref = ?", arrayOf(dienstRef),
        ) }
    }

    fun modusSetzen(dienstRef: String, modus: String) {
        schreibe { it.update(
            "dienst", ContentValues().apply { put("modus", modus) },
            "dienst_ref = ?", arrayOf(dienstRef),
        ) }
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

    /**
     * Ein Paket anlegen. Ein EINSATZ bekommt dabei seinen ersten Zähler: Mit
     * ihm entsteht `start`, der erste Inhalt der Einsatzhülle.
     */
    fun paketAnlegen(
        clientRef: String, art: String, tag: String, dienstRef: String?, begonnenAt: String,
    ): Long = schreibe { it.transaction {
        insertOrThrow(
            "paket", null,
            ContentValues().apply {
                put("client_ref", clientRef)
                put("art", art)
                put("tag", tag)
                put("dienst_ref", dienstRef)
                put("begonnen_at", begonnenAt)
                if (art == Paketzeile.ART_EINSATZ) put("zaehler", zaehlerFuerAenderung())
            },
        )
    } }

    /**
     * Ein Paket abschließen. Ein Einsatz, der noch OFFEN war, bekommt einen
     * neuen Zähler: `schluss` kippt. War er schon abgeschlossen, ändert sich
     * an der Einsatzhülle nichts (Endzeit, Strecke und Anstieg stehen nicht
     * darin), und der Zähler bleibt.
     */
    fun paketSchliessen(paketId: Long, beendetAt: String, streckeM: Int?, anstiegM: Int?) {
        schreibe { it.transaction {
            val offenerEinsatz = rawQuery(
                "SELECT 1 FROM paket WHERE id = ? AND art = ? AND final = 0",
                arrayOf(paketId.toString(), Paketzeile.ART_EINSATZ),
            ).use { c -> c.moveToFirst() }
            update(
                "paket",
                ContentValues().apply {
                    put("beendet_at", beendetAt)
                    put("final", 1)
                    /* DER ABSCHLUSS IST SELBST ARBEIT (KA-06f, F-KA-117).
                     * `final`, Endzeit, Strecke und — in Fassung 2 — die
                     * Hülle mit SCHLUSS müssen hinaus, auch wenn danach kein
                     * Punkt mehr fällt. Bis 0.24.x blieb `metadaten_bestaetigt`
                     * hier stehen: War der Teil-Upload vollständig bestätigt,
                     * hatte das Paket keine Arbeit mehr (`hatArbeit()`), und
                     * der Abschluss erreichte den Server nie. */
                    put("metadaten_bestaetigt", 0)
                    if (streckeM != null) put("strecke_m", streckeM)
                    if (anstiegM != null) put("anstieg_m", anstiegM)
                    if (offenerEinsatz) put("zaehler", zaehlerFuerAenderung())
                },
                "id = ?", arrayOf(paketId.toString()),
            )
            aenderungZaehlen(this, paketId)
        } }
    }

    /**
     * Die Änderungsnummer eines Pakets weiterzählen — NUR INNERHALB der
     * Transaktion, die `metadaten_bestaetigt = 0` setzt (Nr. 566).
     *
     * WARUM ES SIE GIBT. Eine Bestätigung gilt dem Stand, den die Anfrage
     * TRUG, nicht dem, der beim Eintreffen der Antwort im Puffer steht.
     * Fiel dazwischen eine Phase oder der Abschluss, setzte die Antwort
     * `metadaten_bestaetigt` bis Schema 3 trotzdem auf 1, und die Änderung
     * hatte keine Arbeit mehr ([hatArbeit]) — sie ging erst mit dem
     * nächsten Punkt hinaus, beim stehenden Fahrzeug womöglich nie, und ein
     * abgeschlossener Einsatz wurde samt einer nachgereichten Phase
     * entsorgt. [bestaetigungMerken] vergleicht deshalb diese Nummer.
     *
     * WARUM NICHT DER ZÄHLER `z` DES EINSATZES. Er misst etwas anderes: den
     * Inhalt der Einsatzhülle, nicht den Stand der Anfrage. Ein zweites
     * Schließen ändert Endzeit und Strecke ohne neues `z`; ein Einsatz kann
     * ohne `z` dastehen ([zaehlerFuerAenderung] gibt bei [Zaehlerausfall]
     * `null`, und `null` gegen `null` sähe gleich aus, obwohl eine Phase
     * dazukam); Ruhesegmente haben keinen. Diese Nummer dagegen zählt in
     * SQL, kann nicht ausfallen und gilt für jede Art gleich — EIN Weg statt
     * zweier.
     *
     * DIE REGEL: Wer `metadaten_bestaetigt = 0` setzt, zählt hier. Heute
     * sind das [phaseAnhaengen] und [paketSchliessen].
     */
    private fun aenderungZaehlen(db: SQLiteDatabase, paketId: Long) {
        db.execSQL("UPDATE paket SET aenderung = aenderung + 1 WHERE id = ?", arrayOf<Any>(paketId))
    }

    /**
     * Der Zähler für eine Änderung am Inhalt eines Einsatzes — NUR INNERHALB
     * der Transaktion zu rufen, die die Änderung schreibt (Kopf der Klasse).
     *
     * `null`, wenn der Gerätezähler sich nicht ablegen ließ: Die Änderung
     * wird trotzdem geschrieben, das Paket steht dann OHNE Zähler da — nicht
     * mit dem alten, der zu einem anderen Inhalt gehörte —, und
     * [einsatzstand] holt es beim Verschließen nach.
     */
    private fun zaehlerFuerAenderung(): Long? = try {
        huellenzaehler.naechster()
    } catch (e: Zaehlerausfall) {
        null
    }

    /**
     * Ein Einsatz, wie er JETZT ist: Paket und Phasen aus EINEM Augenblick.
     *
     * WARUM NICHT [paket] UND [phasen] NACHEINANDER. Zwischen zwei Lesungen
     * kann der Aufzeichnungsfaden eine Phase anhängen. Der Sender verschlösse
     * dann die neue Liste unter dem alten Zähler — „gleiches z ⇒ innen
     * byte-gleich" wäre gebrochen, und zwar selten und ohne Meldung. Deshalb
     * eine Transaktion um beide.
     *
     * @param zaehlerSichern Wird für Fassung 2 verschlossen? Dann bekommt ein
     *   Einsatz ohne Zähler hier einen — einmal, abgelegt, bevor er
     *   zurückgegeben wird; die nächste Anfrage liest denselben. `false`
     *   lässt die Spalte, wie sie ist — das war der Weg der Fassung 1;
     *   seit 0.27.0 ruft der [Sender] nur noch mit `true` (KA-21).
     * @return `null`, wenn es das Paket nicht mehr gibt
     * @throws Zaehlerausfall wenn ein Zähler fällig ist und sich nicht
     *   ablegen lässt — dann darf keine Hülle entstehen
     */
    fun einsatzstand(paketId: Long, zaehlerSichern: Boolean): Einsatzstand? =
        schreibe(zaehlt = false) { it.transaction {
            val p = paket(paketId)
            when {
                p == null -> null
                p.art != Paketzeile.ART_EINSATZ -> Einsatzstand(p, emptyList())
                zaehlerSichern && p.zaehler == null -> {
                    val z = huellenzaehler.naechster()
                    update(
                        "paket", ContentValues().apply { put("zaehler", z) },
                        "id = ?", arrayOf(paketId.toString()),
                    )
                    Einsatzstand(p.copy(zaehler = z), phasen(paketId))
                }
                else -> Einsatzstand(p, phasen(paketId))
            }
        } }

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
    fun punktAnhaengen(paketId: Long, p: Rohpunkt): Long = schreibe(zaehlt = false) { db ->
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
        seq
    }

    fun punktzahl(paketId: Long): Long =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM punkt WHERE paket_id = ?", arrayOf(paketId.toString()),
        ).use { c -> if (c.moveToFirst()) c.getLong(0) else 0L }

    /** Punkte ab `vonSeq`, höchstens `hoechstens` Stück — für das Senden. */
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

    // ---- Phasen ------------------------------------------------------------

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
     * Phasen-Koordinate.
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
     * Nur-Aufzeichnen-Betrieb sogar bis zum Dienstende. Beim Phasenkonflikt
     * Uhr/Handy werden **beide Einträge gesendet** — gesendet, nicht nur
     * gespeichert.
     *
     * UND EIN NEUER ZÄHLER, in derselben Transaktion (E-KA-69): Die
     * Phasenliste steht in der Einsatzhülle, und sie ist jetzt eine andere.
     */
    fun phaseAnhaengen(paketId: Long, nummer: Int, at: String, breite: Double?, laenge: Double?, quelle: String) {
        schreibe { it.transaction {
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
                "paket",
                ContentValues().apply {
                    put("metadaten_bestaetigt", 0)
                    put("zaehler", zaehlerFuerAenderung())
                },
                "id = ?", arrayOf(paketId.toString()),
            )
            aenderungZaehlen(this, paketId)
        } }
    }

    /** Die Quelle einer Phase (`handy`/`uhr`) — nur für die Prüfung. */
    fun phasenquellen(paketId: Long): List<String> =
        readableDatabase.rawQuery(
            "SELECT quelle FROM phase WHERE paket_id = ? ORDER BY id", arrayOf(paketId.toString()),
        ).use { c -> buildList { while (c.moveToNext()) add(c.getString(0)) } }

    // ---- Buchführung des Sendens --------------------------------------------

    /**
     * Was der Server bestätigt hat.
     *
     * `next_seq` ist die **erste noch nicht gespeicherte** Sequenznummer
     * (Vertrag 5) — ab ihr sendet die App beim nächsten Mal weiter, und alles
     * davor darf sie lokal verwerfen. `metadatenBestaetigt` merkt, dass der
     * Datensatz überhaupt einmal angekommen ist: Ein Paket ohne Punkte
     * (ein Einsatz, der nur Phasen trägt) hätte sonst nie „Arbeit erledigt".
     *
     * NUR DER STAND, DEN DIE ANFRAGE TRUG (Nr. 566). `aenderung` ist die
     * Änderungsnummer aus der [Paketzeile], aus der die Anfrage gebaut
     * wurde. Steht im Puffer inzwischen eine andere — eine Phase, ein
     * Abschluss fiel, während die Anfrage unterwegs war —, bleibt
     * `metadaten_bestaetigt` stehen, und das Paket behält seine Arbeit. Die
     * Punkte gelten trotzdem als bestätigt: Sie hängen nur an, und was der
     * Server von ihnen hat, hat er. Vergleich und Schreiben stehen in EINER
     * Anweisung; dazwischen kann keine Änderung fallen.
     *
     * @return ob der Stand noch derselbe war — nur dann darf der Sender ein
     *   abgeschlossenes Paket entsorgen
     */
    fun bestaetigungMerken(paketId: Long, naechsteSeq: Long, aenderung: Long): Boolean =
        schreibe { it.transaction {
            execSQL(
                "UPDATE paket SET bestaetigt_seq = ?, " +
                    "metadaten_bestaetigt = CASE WHEN aenderung = ? THEN 1 ELSE metadaten_bestaetigt END " +
                    "WHERE id = ?",
                arrayOf<Any>(naechsteSeq, aenderung, paketId),
            )
            rawQuery(
                "SELECT 1 FROM paket WHERE id = ? AND aenderung = ?",
                arrayOf(paketId.toString(), aenderung.toString()),
            ).use { c -> c.moveToFirst() }
        } }

    /**
     * 400 vom Server: Die Nachricht ist fehlerhaft und wird **nicht
     * wiederholt** (Vertrag 5). Sie bleibt liegen — gelöscht wird sie nicht,
     * weil dann niemand mehr sähe, dass etwas nicht angekommen ist.
     */
    fun alsFehlerhaftMerken(paketId: Long) {
        schreibe { it.update(
            "paket", ContentValues().apply { put("fehlerhaft", 1) },
            "id = ?", arrayOf(paketId.toString()),
        ) }
    }

    /**
     * Ein vollständig bestätigtes, abgeschlossenes Paket entsorgen.
     *
     * ERST NACH `final` UND VOLLSTÄNDIGER BESTÄTIGUNG — dieselbe Regel wie in
     * `Uploader.mc`. Sie ist der Grund, warum ein Funkabriss nichts kostet:
     * Solange der Server nicht bestätigt hat, liegt alles noch hier.
     *
     * @param aenderung Wenn gesetzt: nur entsorgen, wenn das Paket noch
     *   diesen Stand hat (Nr. 566) — geprüft in derselben Transaktion wie
     *   das Löschen. Eine Phase, die die Uhr einem abgeschlossenen Einsatz
     *   nachreicht, während seine letzte Antwort eintrifft, fiele sonst mit
     *   ihm weg.
     * @return ob gelöscht wurde
     */
    fun paketEntsorgen(paketId: Long, aenderung: Long? = null): Boolean =
        /* IN EINER TRANSAKTION. Bräche der Vorgang zwischen den drei
         * Löschungen ab, bliebe ein Paket ohne Punkte stehen — oder, schlimmer,
         * Punkte ohne Paket, die nie wieder jemand sähe. */
        schreibe { it.transaction {
            val gleich = aenderung == null || rawQuery(
                "SELECT 1 FROM paket WHERE id = ? AND aenderung = ?",
                arrayOf(paketId.toString(), aenderung.toString()),
            ).use { c -> c.moveToFirst() }
            if (gleich) {
                delete("punkt", "paket_id = ?", arrayOf(paketId.toString()))
                delete("phase", "paket_id = ?", arrayOf(paketId.toString()))
                delete("paket", "id = ?", arrayOf(paketId.toString()))
            }
            gleich
        } }

    /**
     * Die Warteschlange in ihrer Reihenfolge (wie `Uploader._findJob`):
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
     * den sie hat.
     */
    fun rueckstand(): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket p WHERE p.final = 1 AND p.fehlerhaft = 0 AND (" +
                "p.metadaten_bestaetigt = 0 OR " +
                "p.bestaetigt_seq < (SELECT COUNT(*) FROM punkt WHERE paket_id = p.id))",
            null,
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /**
     * Pakete, die der Server mit **400** abgewiesen hat.
     *
     * WARUM SIE EINE EIGENE ZAHL BRAUCHEN. `alsFehlerhaftMerken` nimmt ein
     * Paket aus der Warteschlange **und** aus [rueckstand] — beides zu Recht:
     * Es wird nicht wiederholt (Vertrag 5), und ein Rückstand, der sich nie
     * abbaut, wäre eine Anzeige ohne Aussage. Ohne eigene Zahl tauchte es
     * aber **nirgends** mehr auf: Die App sagte „Alles gesendet", während
     * beim Server ein Segment offen bleibt.
     */
    fun abgewiesen(): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket WHERE fehlerhaft = 1", null,
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /**
     * Wie viele der [abgewiesen]en Pakete sich **jetzt** verwerfen lassen —
     * die abgeschlossenen.
     *
     * WARUM EINE ZWEITE ZAHL. [abgewieseneRaeumen] nimmt nur `final = 1`: Ein
     * laufendes Paket, dessen Teil-Upload eine 400 bekam, wird noch
     * beschrieben. Nennte die Rückfrage [abgewiesen], versprächen Titel und
     * Knopf das Verwerfen eines Pakets, das danach noch dasteht — und ein
     * zweiter Druck verwürfe nichts. Mit dieser Zahl steht der Knopf nur,
     * wenn er etwas tut, und der Titel nennt, was er tut.
     */
    fun verwerfbar(): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket WHERE fehlerhaft = 1 AND final = 1", null,
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /**
     * Abgewiesene Pakete **räumen**.
     *
     * WARUM: Sonst bliebe ein mit 400 abgewiesenes Paket samt Spur **für
     * immer** liegen — es überlebte Trennen und Neukopplung, und die
     * `dienst`-Zeilen würden nie gelöscht. [alsFehlerhaftMerken] sagt dazu:
     * „gelöscht wird sie nicht, weil dann niemand mehr sähe, dass etwas nicht
     * angekommen ist." Das bleibt richtig — für Tage, nicht für Jahre. Eine
     * Spur, die niemand mehr nachreichen wird, ist kein Beleg mehr, sondern
     * ein Ortsdatensatz auf einem Gerät, das jederzeit verlorengehen kann.
     *
     * ZWEI ANLÄSSE, EINE REGEL:
     *  - **nach der Frist** ([Raeumung.FRIST_TAGE], gemessen am Ende des
     *    Pakets) — jeder Sendelauf räumt, bevor er sendet;
     *  - **beim Trennen** ohne Frist (`vor = null`): Die Pakete gehören dem
     *    bisherigen Konto, und wer die Kopplung zurückgibt, gibt auch das ab.
     *
     * NUR ABGESCHLOSSENE (`final = 1`): Ein laufendes Paket, dessen
     * Teil-Upload eine 400 zurückbekam, wird noch beschrieben. Es fällt unter
     * die Regel, sobald das Dienstende es schließt.
     *
     * `dienst`-ZEILEN GEHEN MIT: Jede **beendete** Dienstzeile, an der kein
     * Paket mehr hängt und deren Ende vor dem Stichtag liegt, wird ebenfalls
     * gelöscht — nach dem Upload liest sie niemand mehr ([laufenderDienst]
     * fragt nur nach der offenen). Die laufende bleibt in jedem Fall.
     *
     * DER BEDIENWEG IST „VERWERFEN": ein Knopf
     * unter der roten Zeile der Dienstansicht, mit Rückfrage, ruft diese
     * Funktion ohne Frist. Ansehen und Ausleiten gibt es nicht: Der Server
     * hat das Paket mit 400 endgültig abgelehnt, und ein Nachreichen von
     * Hand wäre ein zweiter Weg an `validate_lib.php` vorbei.
     *
     * @param vor Zeitstempel im Format von `Zeit.iso` (UTC); geräumt wird,
     *   was vor ihm endete. `null` räumt ohne Frist.
     * @return was gelöscht wurde — für das Protokoll und den Prüfstand
     */
    fun abgewieseneRaeumen(vor: String?): Raeumung = schreibe { it.transaction {
        val frist = if (vor != null) arrayOf(vor) else null
        val ids = rawQuery(
            "SELECT id FROM paket WHERE fehlerhaft = 1 AND final = 1" +
                (if (vor != null) " AND COALESCE(beendet_at, begonnen_at) < ?" else ""),
            frist,
        ).use { c -> buildList { while (c.moveToNext()) add(c.getLong(0)) } }
        /* Dieselben drei Löschungen wie in `paketEntsorgen`, in derselben
         * Transaktion: kein Paket ohne Punkte, keine Punkte ohne Paket. */
        for (id in ids) {
            val a = arrayOf(id.toString())
            delete("punkt", "paket_id = ?", a)
            delete("phase", "paket_id = ?", a)
            delete("paket", "id = ?", a)
        }
        val dienste = delete(
            "dienst",
            "beendet_at IS NOT NULL" +
                (if (vor != null) " AND beendet_at < ?" else "") +
                " AND dienst_ref NOT IN (SELECT dienst_ref FROM paket WHERE dienst_ref IS NOT NULL)",
            frist,
        )
        Raeumung(pakete = ids.size, dienste = dienste)
    } }

    /**
     * Die höchste Paketnummer, die je vergeben wurde — die Grenze einer
     * Rückstandsbindung ([org.genem.nadoku.handy.kopplung.Rueckstandsbindung]).
     *
     * `AUTOINCREMENT` trägt das: Eine Nummer wird nie wiederverwendet, auch
     * nicht nach dem Löschen des höchsten Pakets. Was nach der Bindung
     * entsteht, liegt also sicher darüber.
     */
    fun hoechstePaketId(): Long =
        readableDatabase.rawQuery("SELECT COALESCE(MAX(id), 0) FROM paket", null)
            .use { if (it.moveToFirst()) it.getLong(0) else 0L }

    /**
     * Der ganze Bestand bis zu dieser Nummer: jedes Paket, das noch nicht
     * vollständig bestätigt ist — **offene eingeschlossen** (seit Android
     * 0.23.0, KA-04).
     *
     * WARUM NICHT [rueckstandBis]. Jener zählt nur abgeschlossene Pakete. Eine
     * Bindung, die ein offenes Paket deckt (vorgefundener Bestand bei einer
     * neuen Kopplung), zählte dort 0 — der Sendelauf hielte sie für leer,
     * löste sie, und das offene Paket ginge doch an das neue Konto.
     */
    fun bestandBis(bisId: Long): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket p WHERE p.id <= ? AND p.fehlerhaft = 0 AND (" +
                "p.final = 0 OR p.metadaten_bestaetigt = 0 OR " +
                "p.bestaetigt_seq < (SELECT COUNT(*) FROM punkt WHERE paket_id = p.id))",
            arrayOf(bisId.toString()),
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /** [bestandBis] über alle Pakete — was eine neue Kopplung vorfindet. */
    fun bestand(): Int = bestandBis(Long.MAX_VALUE)

    /** Wie [rueckstand], aber nur Pakete bis zu dieser Nummer. */
    fun rueckstandBis(bisId: Long): Int =
        readableDatabase.rawQuery(
            "SELECT COUNT(*) FROM paket p WHERE p.id <= ? AND p.final = 1 AND p.fehlerhaft = 0 AND (" +
                "p.metadaten_bestaetigt = 0 OR " +
                "p.bestaetigt_seq < (SELECT COUNT(*) FROM punkt WHERE paket_id = p.id))",
            arrayOf(bisId.toString()),
        ).use { if (it.moveToFirst()) it.getInt(0) else 0 }

    /**
     * Den gebundenen Rückstand **verwerfen** — alle abgeschlossenen Pakete
     * bis zu dieser Nummer, samt Punkten, Phasen und leeren Dienstzeilen.
     *
     * DER EINE FALL: Das Gerät wurde nach einer Abmeldung an ein **anderes**
     * Konto gekoppelt. Die Pakete gehören dem bisherigen; senden darf das
     * Gerät sie nicht (der Klartext ginge, mit dem Schlüssel des neuen Kontos
     * verschlossen, an das falsche), und zurück an das alte kommen sie nur
     * über eine Kopplung an das alte. Wer das nicht will, verwirft sie hier —
     * nach einer Rückfrage in der Oberfläche.
     *
     * NUR ABGESCHLOSSENE (`final = 1`): Ein laufendes Paket hat eine Nummer
     * über der Grenze, weil eine Bindung nur außerhalb eines Dienstes entsteht.
     */
    fun rueckstandVerwerfen(bisId: Long): Raeumung = schreibe { it.transaction {
        val ids = rawQuery(
            "SELECT id FROM paket WHERE id <= ? AND final = 1",
            arrayOf(bisId.toString()),
        ).use { c -> buildList { while (c.moveToNext()) add(c.getLong(0)) } }
        for (id in ids) {
            val a = arrayOf(id.toString())
            delete("punkt", "paket_id = ?", a)
            delete("phase", "paket_id = ?", a)
            delete("paket", "id = ?", a)
        }
        val dienste = delete(
            "dienst",
            "beendet_at IS NOT NULL" +
                " AND dienst_ref NOT IN (SELECT dienst_ref FROM paket WHERE dienst_ref IS NOT NULL)",
            null,
        )
        Raeumung(pakete = ids.size, dienste = dienste)
    } }

    /** Hat dieses Paket noch etwas zu senden? */
    fun hatArbeit(p: Paketzeile): Boolean =
        !p.metadatenBestaetigt || p.bestaetigtSeq < punktzahl(p.id)

    // ---- Buchführung über die Uhr --------------------------------------------

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
    fun <T> imVorgang(block: () -> T): T = schreibe { it.transaction { block() } }

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
    fun uhrEreignisMerken(uhrId: String, nr: Long): Long = schreibe { db ->
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
        stand
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
        const val FASSUNG = 4

        const val PAKET_SPALTEN =
            "SELECT id, client_ref, art, tag, dienst_ref, begonnen_at, beendet_at, final, " +
                "strecke_m, anstieg_m, bestaetigt_seq, metadaten_bestaetigt, fehlerhaft, zaehler, aenderung FROM paket"

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
            zaehler = if (c.isNull(13)) null else c.getLong(13),
            aenderung = c.getLong(14),
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

/** Was ein Räumlauf gelöscht hat. */
data class Raeumung(val pakete: Int, val dienste: Int) {
    companion object {
        /**
         * Nach so vielen Tagen ist ein abgewiesenes Paket kein Beleg mehr,
         * sondern ein liegengebliebener Ortsdatensatz. Die Zahl
         * ist **gewählt**, nicht gemessen: lang genug, dass die rote Zeile
         * gesehen und der Fehler am Server gemeldet werden kann, und kurz
         * genug, dass ein verlorenes Handy nicht die Spuren eines ganzen
         * Jahres trägt. Nachreichen gibt es nicht; verwerfen lässt es sich
         * jederzeit von Hand.
         */
        const val FRIST_TAGE = 30L
    }
}

/** Ein Einsatz aus einem Augenblick: das Paket samt Zähler und die Phasen, die zu ihm gehören. */
data class Einsatzstand(val paket: Paketzeile, val phasen: List<Phaseneintrag>)

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
    /**
     * Der Zähler `z` der Einsatzhülle zu DIESEM Stand des Einsatzes
     * (E-KA-69); `null` bei Ruhesegmenten und bei einem Einsatz, dessen
     * Stand noch keinen hat ([Puffer.einsatzstand] vergibt ihn dann).
     */
    val zaehler: Long? = null,
    /**
     * Die Änderungsnummer dieses Stands (Nr. 566, Schema 4): steigt mit
     * jeder Phase und jedem Schließen. Eine Bestätigung gilt nur, wenn sie
     * noch dieselbe ist ([Puffer.bestaetigungMerken]).
     */
    val aenderung: Long = 0,
) {
    companion object {
        const val ART_EINSATZ = "mission"
        const val ART_RUHESEGMENT = "rest_segment"
    }
}
