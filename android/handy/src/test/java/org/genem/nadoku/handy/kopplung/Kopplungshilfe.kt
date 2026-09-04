package org.genem.nadoku.handy.kopplung

import org.genem.nadoku.handy.tresor.Schluesseltresor

/**
 * Was in den Rundlauffällen den **Menschen im Browser** ersetzt.
 *
 * DAS PROBLEM, DAS DIESE DATEI LÖST. Seit der Umkehr der Kopplung (R49, S5)
 * gibt es keinen Kopplungscode mehr, den man vorab in eine Tabelle legen kann:
 * Er entsteht bei `pair.php` selbst, zusammen mit den Zugangsdaten, und lebt
 * zehn Minuten. Die drei Rundlaufklassen konnten sich bis Android 0.10.1
 * ihre Kopplung aus einer vorbereiteten `pair_codes`-Zeile holen — diese
 * Tabelle gibt es nicht mehr.
 *
 * Der Ablauf hat jetzt einen Schritt, den **kein Gerät tut**: Zwischen `start`
 * und `bestaetigen` trägt ein Mensch den Code im Browser ein. Ein Prüffall
 * muss diesen Schritt nachstellen, sonst bleibt die Sitzung ewig `offen` und
 * `bestaetigen ja` antwortet `409 nicht_beansprucht`.
 *
 * WARUM ÜBER `mariadb` UND NICHT ÜBER DIE WEBOBERFLÄCHE. Der Browserweg wäre
 * echter, kostete aber Anmeldung, Sitzungskeks und CSRF-Marke — dreihundert
 * Zeilen Prüfcode für einen Schritt, der nicht der Prüfling ist. Geprüft wird
 * *App → `pair.php` → `devices`-Zeile*; wie ein Konto den Code entgegennimmt,
 * ist Gegenstand der Weboberfläche und dort belegt (`tools/kopplungsprobe/`).
 *
 * WARUM DAS VERTRETBAR IST: Diese Fälle laufen ausschließlich gegen eine
 * **örtliche** Installation, auf derselben Maschine, und nur wenn
 * `-Pnadoku.rundlauf` gesetzt ist. Ohne sie überspringen sie sich. Ein
 * Prüfendpunkt im Server wäre die Alternative gewesen — also Prüfcode im
 * ausgelieferten `server/`, und das ist der schlechtere Tausch.
 *
 * DER WEG STEHT WÖRTLICH IN `android/LIESMICH.md` („So bekommt ein Prüfling
 * heute eine Kopplung"); diese Datei ist seine ausführbare Fassung.
 */
object Kopplungshilfe {

    /** Das Konto der örtlichen Installation — das erste, das sie anlegt. */
    const val KONTO_ID = 1

    /**
     * Eine vollständige Kopplung herstellen, wie ein Gerät sie erlebt:
     * `start` → (Mensch im Browser) → `bestaetigen ja`.
     *
     * @return `null`, wenn es geklappt hat, sonst eine Beschreibung dessen,
     *   was schiefging. **Kein Werfen**: Der Aufrufer entscheidet, ob das ein
     *   Fehlschlag ist oder ein erwarteter Fall.
     */
    fun koppeln(dienst: Kopplungsdienst, geraet: Geraeteangabe): String? {
        val start = dienst.starten(geraet)
        if (start !is Sitzungsergebnis.Offen) {
            return "start ergab $start"
        }

        val zugeordnet = codeZuordnen(start.code)
        if (zugeordnet != null) return zugeordnet

        return when (val ja = dienst.bestaetigen(ja = true)) {
            is Bestaetigungsergebnis.Gekoppelt -> null
            else -> "bestaetigen ergab $ja"
        }
    }

    /**
     * Den Schritt tun, den sonst ein Mensch im Browser tut: den Code einem
     * Konto zuordnen.
     *
     * DIE FRIST LÄUFT WEITER. Zwischen `start` und diesem Aufruf vergehen
     * Millisekunden, nicht Minuten — die zehn Minuten aus 1a.1 sind hier kein
     * Thema, solange niemand zwischen den beiden Aufrufen anhält.
     */
    fun codeZuordnen(code: String, kontoId: Int = KONTO_ID): String? {
        val sql = "UPDATE pair_sessions SET user_id = $kontoId WHERE code = '$code';"
        return sqlAusfuehren(sql)
    }

    /**
     * Geräte, Sitzungen **und den Ratenschutz** des Prüfkontos abräumen
     * (Backlog Nr. 95).
     *
     * DER RATENSCHUTZ IST DER GRUND, WARUM DIESE FUNKTION MEHR TUT ALS
     * AUFRÄUMEN. `pair.php` lässt zwanzig `start`-Aufrufe je zehn Minuten und
     * Absenderadresse zu (E-S5-33). Diese Klasse macht in einem Lauf mehr als
     * zwanzig — allein der Grenzfall bis zu acht —, und alle kommen von
     * `127.0.0.1`. Ohne das Leeren sperrt sich der Prüflauf **selbst** aus:
     * Der erste Durchgang läuft grün, der zweite meldet für jeden Fall
     * `429 zu_viele_versuche`, und wer das sieht, sucht den Fehler in der App.
     * Genau das ist beim ersten Lauf dieses Pakets passiert.
     *
     * Der Ratenschutz ist hier **nicht der Prüfling** — er wird in
     * `tools/kopplungsprobe/` gegen den Endpunkt gemessen, wo er hingehört.
     * Ihn in einer Klasse mitzuprüfen, die die App prüft, hieße, zwei Dinge an
     * einer Zahl zu messen.
     *
     * WAS DIESE FUNKTION AUSDRÜCKLICH NICHT TUT: die **hochgeladenen Daten**
     * abräumen — Diensttage, Einsätze, Ruhesegmente. Das ist Backlog Nr. 95,
     * und es bleibt dort offen. Der naheliegende Weg wäre ein `DELETE` auf
     * `missions` und `rest_segments` in derselben SQL-Zeile; er scheidet aus,
     * und zwar nicht aus Vorsicht, sondern nach einer festen Zusage des
     * Projekts (`CLAUDE.md` 4): GPS-Punkte liegen je nach Alter als Zeilen in
     * `track_points` **oder** als Blob in `track_blobs`, und beides fasst
     * ausschließlich `spur_lib.php` an. Ein SQL-Löschen des Einsatzes ließe
     * seine Spur als Waise zurück — ohne Fehlermeldung, und messbar erst,
     * wenn ein Waisen-Vollscan sie findet.
     *
     * Was Nr. 95 wirklich braucht, ist der Weg, den der Backlog selbst nennt:
     * ein **eigenes Prüfkonto**, das man als Ganzes verwerfen kann (Muster:
     * `tools/pruefkonten/`). Das ist ein eigener Punkt und kein Nachklapp
     * dieses Pakets. Gemessen nach einem vollen Rundlauf: 18 Diensttage,
     * 10 Einsätze, 28 Ruhesegmente im Konto 1.
     */
    fun aufraeumen(kontoId: Int = KONTO_ID): String? = sqlAusfuehren(
        "DELETE FROM pair_sessions WHERE user_id = $kontoId OR user_id IS NULL; " +
            "DELETE FROM devices WHERE user_id = $kontoId; " +
            "DELETE FROM rate_limits;"
    )

    /**
     * `mariadb` aufrufen. Der Rückgabewert ist `null` bei Erfolg und sonst
     * die Fehlerausgabe — sie sagt regelmäßig genauer, was fehlt, als eine
     * geworfene Ausnahme es täte (kein Client, keine Datenbank, keine
     * Tabelle).
     */
    private fun sqlAusfuehren(sql: String): String? = try {
        val lauf = ProcessBuilder("mariadb", "-e", sql, DATENBANK)
            .redirectErrorStream(true)
            .start()
        val ausgabe = lauf.inputStream.bufferedReader().readText().trim()
        if (lauf.waitFor() == 0) null else "mariadb: $ausgabe"
    } catch (e: Exception) {
        "mariadb nicht aufrufbar: ${e.message}"
    }

    private const val DATENBANK = "nadoku"
}
