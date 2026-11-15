package org.genem.nadoku.handy.puffer

import android.content.Context
import android.database.sqlite.SQLiteDatabase
import androidx.test.core.app.ApplicationProvider
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Assert.assertNull
import org.junit.Assert.assertTrue
import org.genem.nadoku.handy.senden.Huellenzaehler
import org.genem.nadoku.handy.senden.SpeicherZaehlerstand
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Die Schema-Migrationen des Puffers: **sie verlieren nichts.**
 *
 * WARUM DAS EINEN EIGENEN PRÜFFALL WERT IST. Im Puffer liegt der einzige Ort,
 * an dem eine noch nicht gesendete Aufzeichnung existiert. Der bequeme Weg —
 * beim Fassungswechsel alles wegwerfen und neu anlegen — wäre der stillste
 * denkbare Datenverlust: Die App startet, sieht gut aus, und ein Diensttag
 * ist weg. Deshalb steht in `onUpgrade` eine echte Migration, und deshalb
 * wird sie geprüft, statt gelesen.
 *
 * Nachgestellt wird eine Datenbank der **alten** Fassung mit einem laufenden
 * Dienst darin; dann öffnet der heutige [Puffer] sie.
 *
 * Anlass: Nr. 563 — Schema 3 (KA-06f, E-KA-69) legt den Zähler der
 * Einsatzhülle zum Paket. Der Fall von 2 auf 3 trägt, was ein Handy mitten
 * im Dienst im Puffer hat: einen offenen und einen abgeschlossenen Einsatz
 * mit Phasen und Punkten, ein Ruhesegment, die Buchführung der Uhr.
 */
@RunWith(RobolectricTestRunner::class)
class PufferMigrationTest {

    private lateinit var kontext: Context

    @Before fun aufbauen() {
        kontext = ApplicationProvider.getApplicationContext()
        kontext.deleteDatabase(DATEI)
    }

    @After fun abbauen() {
        kontext.deleteDatabase(DATEI)
    }

    /** Von 1 an: über 2 nach 3, in einem Zug. */
    @Test fun dieMigrationVonEinsAnVerliertNichts() {
        alteFassungAnlegen()

        val puffer = Puffer(kontext, DATEI)
        try {
            val dienst = puffer.laufenderDienst()
            assertNotNull("Der Dienst hat die Migration überlebt", dienst)
            assertEquals("ad-1-alt", dienst!!.dienstRef)
            assertEquals("2026-07-16T05:00:00Z", dienst.begonnenAt)

            val paket = puffer.paketNach("ar-1-alt")
            assertNotNull("Das Ruhesegment auch", paket)
            assertEquals("Und seine Punkte", 2, puffer.punktzahl(paket!!.id))

            // Und die neuen Tabellen sind da und benutzbar.
            assertEquals(0L, puffer.uhrStand("u-1"))
            assertEquals(1L, puffer.uhrEreignisMerken("u-1", 1))
            assertNull("ein Ruhesegment bekommt keinen Zähler", paket.zaehler)
        } finally {
            puffer.close()
        }
    }

    /**
     * 2 → 3: Kein Paket, kein Punkt, keine Phase und kein Vermerk geht
     * verloren oder ändert sich; die neue Spalte ist leer. Der Zähler der
     * vorgefundenen Einsätze entsteht beim ERSTEN Verschließen — einmal,
     * über dem Stand, den das Handy unter der alten Regel zuletzt vergeben
     * hat — und bleibt danach derselbe.
     */
    @Test fun dieMigrationVonZweiAufDreiVerliertNichts() {
        zweiteFassungAnlegen()
        val vorher = abzug()

        // Der Gerätezähler von vor dem Update: unter E-KA-64 zuletzt beim Senden vergeben.
        val alterStand = 1_784_300_000L
        val zaehlerstand = SpeicherZaehlerstand(alterStand)
        var jetzt = alterStand - 600                    // die Uhr liegt darunter
        val puffer = Puffer(kontext, DATEI, Huellenzaehler(zaehlerstand) { jetzt })
        try {
            puffer.laufenderDienst()                    // öffnet und migriert
            assertEquals("jede Zeile, jeder Wert wie vorher", vorher, abzug())
            assertEquals(3, version())

            val offen = puffer.paketNach("am-2-offen")!!
            val zu = puffer.paketNach("am-3-zu")!!
            val ruhe = puffer.paketNach("ar-1-ruhe")!!
            assertNull(offen.zaehler); assertNull(zu.zaehler); assertNull(ruhe.zaehler)
            assertEquals("die Migration selbst vergibt nichts", alterStand, zaehlerstand.wert)
            assertEquals(false, offen.final); assertEquals(true, zu.final)
            assertEquals(true, offen.metadatenBestaetigt); assertEquals(2L, offen.bestaetigtSeq)
            assertEquals(3L, puffer.punktzahl(offen.id)); assertEquals(2, puffer.phasen(offen.id).size)
            assertEquals(1, puffer.phasen(zu.id).size); assertEquals(2L, puffer.punktzahl(ruhe.id))
            assertEquals(listOf("handy", "uhr"), puffer.phasenquellen(offen.id))
            assertEquals(7L, puffer.uhrStand("u-1")); assertEquals(listOf(9L), puffer.uhrOffeneNummern("u-1"))
            assertEquals("der Rückstand zählt wie vorher", 1, puffer.rueckstand())

            // Erstes Verschließen: einmal vergeben, über dem alten Stand, abgelegt.
            val erstes = puffer.einsatzstand(offen.id, zaehlerSichern = true)!!
            assertEquals(alterStand + 1, erstes.paket.zaehler)
            assertEquals(2, erstes.phasen.size)
            assertEquals(alterStand + 1, zaehlerstand.wert)
            assertEquals(alterStand + 1, puffer.paketNach("am-2-offen")!!.zaehler)
            jetzt += 5
            assertEquals("zweites Verschließen: derselbe", alterStand + 1,
                puffer.einsatzstand(offen.id, zaehlerSichern = true)!!.paket.zaehler)
            assertEquals("der abgeschlossene Einsatz bekommt seinen eigenen",
                alterStand + 2, puffer.einsatzstand(zu.id, zaehlerSichern = true)!!.paket.zaehler)
            assertNull(puffer.einsatzstand(ruhe.id, zaehlerSichern = true)!!.paket.zaehler)

            // Und die migrierte Tabelle ist benutzbar wie eine frische: eine Änderung vergibt.
            puffer.phaseAnhaengen(offen.id, 5, "2026-07-16T06:20:00Z", null, null, "handy")
            assertEquals(alterStand + 3, puffer.paketNach("am-2-offen")!!.zaehler)
            assertEquals(3, puffer.phasen(offen.id).size)
        } finally {
            puffer.close()
        }
    }

    /** Ein migrierter Puffer hat dieselbe Tabelle `paket` wie ein frisch angelegter. */
    @Test fun migriertUndFrischHabenDasselbeSchema() {
        zweiteFassungAnlegen()
        Puffer(kontext, DATEI).use { it.laufenderDienst() }
        val migriert = spalten(DATEI)
        kontext.deleteDatabase(FRISCH)
        try {
            Puffer(kontext, FRISCH).use { it.laufenderDienst() }
            assertEquals(spalten(FRISCH), migriert)
            assertTrue(migriert.any { it.startsWith("zaehler|INTEGER|0|") })
        } finally {
            kontext.deleteDatabase(FRISCH)
        }
    }

    private fun spalten(datei: String): List<String> = roh(datei) { db ->
        db.rawQuery("PRAGMA table_info(paket)", null).use { c ->
            buildList { while (c.moveToNext()) add("${c.getString(1)}|${c.getString(2)}|${c.getInt(3)}|${c.getString(4)}") }
        }
    }

    private fun version(): Int = roh(DATEI) { it.version }

    private fun <T> roh(datei: String, block: (SQLiteDatabase) -> T): T {
        val db = SQLiteDatabase.openDatabase(kontext.getDatabasePath(datei).path, null, SQLiteDatabase.OPEN_READONLY)
        try { return block(db) } finally { db.close() }
    }

    /** Jede Zeile der Tabellen von Schema 2, Spalte für Spalte als Text — ohne die Spalte, die Schema 3 bringt. */
    private fun abzug(): List<String> = roh(DATEI) { db ->
        val spalten = mapOf(
            "dienst" to "id, dienst_ref, tag, begonnen_at, beendet_at, modus",
            "paket" to "id, client_ref, art, tag, dienst_ref, begonnen_at, beendet_at, final, strecke_m, " +
                "anstieg_m, bestaetigt_seq, metadaten_bestaetigt, fehlerhaft",
            "punkt" to "paket_id, seq, breite, laenge, hoehe, zeit",
            "phase" to "id, paket_id, nummer, at, breite, laenge, quelle",
            "uhr_ereignis" to "uhr_id, nr",
            "uhr_stand" to "uhr_id, bis_nr",
        )
        buildList {
            for ((tabelle, liste) in spalten) {
                db.rawQuery("SELECT $liste FROM $tabelle ORDER BY 1, 2", null).use { c ->
                    while (c.moveToNext()) {
                        add(tabelle + ": " + (0 until c.columnCount).joinToString("|") { if (c.isNull(it)) "∅" else c.getString(it) })
                    }
                }
            }
        }.also { assertEquals("der Abzug sieht alle Zeilen", 1 + 3 + 7 + 3 + 1 + 1, it.size) }
    }

    /**
     * Legt eine Datenbank der Fassung 2 an — von Hand, mit dem Wortlaut von
     * Android 0.2x bis KA-06f. Darin: ein laufender Dienst, ein
     * abgeschlossenes Ruhesegment (zwei Punkte, noch nicht gesendet), ein
     * OFFENER Einsatz (drei Punkte, zwei davon bestätigt, zwei Phasen von
     * Handy und Uhr), ein ABGESCHLOSSENER, vollständig bestätigter Einsatz,
     * den der Server mit 400 abgewiesen hat, und die Buchführung einer Uhr.
     */
    private fun zweiteFassungAnlegen() {
        alteFassungAnlegen(mitDaten = false)
        val db = SQLiteDatabase.openDatabase(kontext.getDatabasePath(DATEI).path, null, SQLiteDatabase.OPEN_READWRITE)
        db.execSQL("CREATE TABLE IF NOT EXISTS uhr_ereignis (uhr_id TEXT NOT NULL, nr INTEGER NOT NULL, PRIMARY KEY (uhr_id, nr))")
        db.execSQL("CREATE TABLE IF NOT EXISTS uhr_stand (uhr_id TEXT PRIMARY KEY, bis_nr INTEGER NOT NULL)")
        db.execSQL("CREATE INDEX idx_paket_offen ON paket (final, fehlerhaft)")
        db.execSQL("CREATE INDEX idx_phase_paket ON phase (paket_id)")
        db.execSQL(
            "INSERT INTO dienst (dienst_ref, tag, begonnen_at, modus) " +
                "VALUES ('ad-1-zwei', '2026-07-16', '2026-07-16T05:00:00Z', 'phasen')"
        )
        db.execSQL(
            "INSERT INTO paket (client_ref, art, tag, dienst_ref, begonnen_at, beendet_at, final) VALUES " +
                "('ar-1-ruhe', 'rest_segment', '2026-07-16', 'ad-1-zwei', '2026-07-16T05:00:00Z', '2026-07-16T06:00:00Z', 1)"
        )
        db.execSQL(
            "INSERT INTO paket (client_ref, art, tag, dienst_ref, begonnen_at, bestaetigt_seq, metadaten_bestaetigt) VALUES " +
                "('am-2-offen', 'mission', '2026-07-16', 'ad-1-zwei', '2026-07-16T06:00:00Z', 2, 1)"
        )
        db.execSQL(
            "INSERT INTO paket (client_ref, art, tag, dienst_ref, begonnen_at, beendet_at, final, strecke_m, anstieg_m, " +
                "bestaetigt_seq, metadaten_bestaetigt, fehlerhaft) VALUES " +
                "('am-3-zu', 'mission', '2026-07-15', NULL, '2026-07-15T08:00:00Z', '2026-07-15T09:00:00Z', 1, 4200, 35, 2, 1, 1)"
        )
        db.execSQL("INSERT INTO punkt VALUES (1, 0, 48.1372, 11.5756, 519.0, 1784178000)")
        db.execSQL("INSERT INTO punkt VALUES (1, 1, 48.1380, 11.5760, NULL, 1784178010)")
        db.execSQL("INSERT INTO punkt VALUES (2, 0, 48.1390, 11.5770, 521.0, 1784181600)")
        db.execSQL("INSERT INTO punkt VALUES (2, 1, 48.1400, 11.5780, 522.0, 1784181610)")
        db.execSQL("INSERT INTO punkt VALUES (2, 2, 48.1410, 11.5790, 523.0, 1784181620)")
        db.execSQL("INSERT INTO punkt VALUES (3, 0, 47.5601, 10.7002, 800.0, 1784102400)")
        db.execSQL("INSERT INTO punkt VALUES (3, 1, 47.5611, 10.7012, 801.0, 1784102410)")
        db.execSQL("INSERT INTO phase (paket_id, nummer, at, breite, laenge, quelle) VALUES (2, 3, '2026-07-16T06:00:00Z', 48.1390, 11.5770, 'handy')")
        db.execSQL("INSERT INTO phase (paket_id, nummer, at, breite, laenge, quelle) VALUES (2, 4, '2026-07-16T06:10:00Z', NULL, NULL, 'uhr')")
        db.execSQL("INSERT INTO phase (paket_id, nummer, at, breite, laenge, quelle) VALUES (3, 9, '2026-07-15T09:00:00Z', 47.5611, 10.7012, 'handy')")
        db.execSQL("INSERT INTO uhr_stand VALUES ('u-1', 7)")
        db.execSQL("INSERT INTO uhr_ereignis VALUES ('u-1', 9)")
        db.version = 2
        db.close()
    }

    /**
     * Legt eine Datenbank der Fassung 1 an — von Hand, mit dem Wortlaut von
     * damals. Das heutige `onCreate` aufzurufen prüfte nichts: Es schriebe das
     * neue Schema, und die Migration hätte nichts zu tun.
     */
    private fun alteFassungAnlegen(mitDaten: Boolean = true) {
        val db = SQLiteDatabase.openOrCreateDatabase(kontext.getDatabasePath(DATEI), null)
        db.execSQL(
            "CREATE TABLE dienst (id INTEGER PRIMARY KEY AUTOINCREMENT, dienst_ref TEXT NOT NULL " +
                "UNIQUE, tag TEXT NOT NULL, begonnen_at TEXT NOT NULL, beendet_at TEXT, " +
                "modus TEXT NOT NULL)"
        )
        db.execSQL(
            "CREATE TABLE paket (id INTEGER PRIMARY KEY AUTOINCREMENT, client_ref TEXT NOT NULL " +
                "UNIQUE, art TEXT NOT NULL, tag TEXT NOT NULL, dienst_ref TEXT, begonnen_at TEXT " +
                "NOT NULL, beendet_at TEXT, final INTEGER NOT NULL DEFAULT 0, strecke_m INTEGER, " +
                "anstieg_m INTEGER, bestaetigt_seq INTEGER NOT NULL DEFAULT 0, " +
                "metadaten_bestaetigt INTEGER NOT NULL DEFAULT 0, fehlerhaft INTEGER NOT NULL DEFAULT 0)"
        )
        db.execSQL(
            "CREATE TABLE punkt (paket_id INTEGER NOT NULL, seq INTEGER NOT NULL, breite REAL " +
                "NOT NULL, laenge REAL NOT NULL, hoehe REAL, zeit INTEGER NOT NULL, " +
                "PRIMARY KEY (paket_id, seq))"
        )
        db.execSQL(
            "CREATE TABLE phase (id INTEGER PRIMARY KEY AUTOINCREMENT, paket_id INTEGER NOT NULL, " +
                "nummer INTEGER NOT NULL, at TEXT NOT NULL, breite REAL, laenge REAL, " +
                "quelle TEXT NOT NULL)"
        )
        if (mitDaten) {
            db.execSQL(
                "INSERT INTO dienst (dienst_ref, tag, begonnen_at, modus) " +
                    "VALUES ('ad-1-alt', '2026-07-16', '2026-07-16T05:00:00Z', 'phasen')"
            )
            db.execSQL(
                "INSERT INTO paket (client_ref, art, tag, dienst_ref, begonnen_at) " +
                    "VALUES ('ar-1-alt', 'rest_segment', '2026-07-16', 'ad-1-alt', '2026-07-16T05:00:00Z')"
            )
            db.execSQL("INSERT INTO punkt VALUES (1, 0, 48.1372, 11.5756, 519.0, 1784178000)")
            db.execSQL("INSERT INTO punkt VALUES (1, 1, 48.1380, 11.5760, 520.0, 1784178010)")
        }
        db.version = 1
        db.close()
    }

    private companion object {
        const val DATEI = "pruef_migration.db"
        const val FRISCH = "pruef_migration_frisch.db"
    }
}
