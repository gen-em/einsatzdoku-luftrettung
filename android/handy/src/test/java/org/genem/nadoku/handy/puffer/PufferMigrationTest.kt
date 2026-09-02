package org.genem.nadoku.handy.puffer

import android.content.Context
import android.database.sqlite.SQLiteDatabase
import androidx.test.core.app.ApplicationProvider
import org.junit.After
import org.junit.Assert.assertEquals
import org.junit.Assert.assertNotNull
import org.junit.Before
import org.junit.Test
import org.junit.runner.RunWith
import org.robolectric.RobolectricTestRunner

/**
 * Die erste Schema-Migration des Puffers (C2): **sie verliert nichts.**
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

    @Test fun dieMigrationVonEinsAufZweiVerliertNichts() {
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
        } finally {
            puffer.close()
        }
    }

    /**
     * Legt eine Datenbank der Fassung 1 an — von Hand, mit dem Wortlaut von
     * damals. Das heutige `onCreate` aufzurufen prüfte nichts: Es schriebe das
     * neue Schema, und die Migration hätte nichts zu tun.
     */
    private fun alteFassungAnlegen() {
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
        db.version = 1
        db.close()
    }

    private companion object {
        const val DATEI = "pruef_migration.db"
    }
}
