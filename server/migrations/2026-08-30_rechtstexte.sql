-- Einsatzdoku — Migration 2026_08_30_rechtstexte
--
-- Diese Datei ist die nachlesbare Fassung der Migration; ausgefuehrt wird sie
-- ueber update.php (Migrations-Runner, mit Buchfuehrung in schema_migrations).
-- Von Hand einspielen nur, wenn der Runner nicht erreichbar ist — und dann
-- anschliessend die ID in schema_migrations eintragen.
--
-- Sie legt eine Tabelle AN und aendert nichts Bestehendes. Ohne sie zeigen
-- Impressum und Datenschutz ihren Leerzustand; die Anwendung laeuft weiter
-- (rechtstexte_lib.php faengt die fehlende Tabelle ab).

-- Impressum und Datenschutzerklaerung dieser Installation (R32, P3/O10).
--
-- WARUM EINE EIGENE TABELLE UND NICHT app_state. Dort ist der Wert
-- VARCHAR(190) — ein Impressum hat 400 bis 1500 Zeichen, eine
-- Datenschutzerklaerung 8000 bis 20000. Der Speicher waere um den Faktor 50
-- bis 100 zu klein, und ohne strict mode kuerzt MySQL still: Ein Rechtstext,
-- der ab Zeichen 191 verschwindet, sieht in der Vorschau vollstaendig aus,
-- solange niemand ans Ende scrollt. Bei einem Dokument, das rechtlich
-- vollstaendig sein muss, ist das der schlechteste denkbare Ausgang.
--
-- MEDIUMTEXT, nicht TEXT: TEXT sind 64 KB in BYTES, und deutsche Rechtstexte
-- in utf8mb4 haben Umlaute. Der Unterschied kostet nichts.
--
-- KEIN VORGABEINHALT. Die Anwendung liefert keinen Rechtstext mit; was darin
-- steht, ist Sache des Betreibers. Der Leerzustand ist die Auslieferung.
CREATE TABLE rechtstexte (
  schluessel VARCHAR(32) NOT NULL PRIMARY KEY,  -- 'impressum' | 'datenschutz'
  inhalt     MEDIUMTEXT NULL,                   -- Markdown-Quelle; NULL oder leer = nichts hinterlegt
  stand_am   DATE NULL                          -- das im Editor VON HAND gesetzte Standdatum; NULL = keine Standzeile
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
