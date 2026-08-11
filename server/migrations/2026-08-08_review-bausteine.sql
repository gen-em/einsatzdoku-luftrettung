-- Einsatzdoku — Migration 2026_08_08_review_bausteine
--
-- Diese Datei ist die nachlesbare Fassung der Migration; ausgefuehrt wird sie
-- ueber update.php (Migrations-Runner, mit Buchfuehrung in schema_migrations).
-- Von Hand einspielen nur, wenn der Runner nicht erreichbar ist — und dann
-- anschliessend die ID in schema_migrations eintragen.
--
-- Sie legt Spalten und Tabellen AN. Bis auf den Ratenschutz liest und
-- schreibt sie zu diesem Zeitpunkt noch kein Code: Das Verhalten der
-- Anwendung aendert sich durch diese Migration nicht.

-- S1 --------------------------------------------------------------------
-- Rundenzahl der Schluesselableitung je Konto.
-- BESONDERE SORGFALT: Ein Fehler an der Schluesselableitung sperrt ALLE
-- KONTEN GLEICHZEITIG aus. Dieser Schritt legt die Spalte nur an und fuellt
-- sie mit dem heutigen Wert. Der Salt-Endpunkt bleibt unveraendert.
ALTER TABLE users
  ADD COLUMN kdf_iter INT UNSIGNED NOT NULL DEFAULT 310000 AFTER kdf_salt;
UPDATE users SET kdf_iter = 310000 WHERE kdf_iter = 0;

-- S2 --------------------------------------------------------------------
-- Pruefsumme des Inhaltsschluessels. Bleibt fuer Bestandskonten LEER —
-- der Server kann sie nicht berechnen, er kennt den Schluessel nicht.
ALTER TABLE users
  ADD COLUMN pat_key_check CHAR(32) NULL AFTER pat_wrap_rc;

-- S3 --------------------------------------------------------------------
-- Sitzungszaehler: wird beim Passwortwechsel erhoeht, bei jeder Anfrage
-- gegen die Sitzung geprueft.
ALTER TABLE users
  ADD COLUMN session_epoch INT UNSIGNED NOT NULL DEFAULT 0 AFTER role;

-- S4 --------------------------------------------------------------------
-- Ratenschutz je Kontokennung und je IP-Adresse, mit Zeitfenster.
CREATE TABLE rate_limits (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topf          VARCHAR(32)  NOT NULL,
  merkmal       VARCHAR(190) NOT NULL,
  versuche      INT UNSIGNED NOT NULL DEFAULT 0,
  fenster_start DATETIME     NOT NULL,
  gesperrt_bis  DATETIME     NULL,
  UNIQUE KEY uq_topf_merkmal (topf, merkmal),
  INDEX idx_fenster (fenster_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- S5 --------------------------------------------------------------------
-- Sperrliste auch fuer Ruhe-Segmente. Bestand besteht ausschliesslich aus
-- Einsaetzen, deshalb ist der Vorgabewert richtig. Erst den neuen Schluessel
-- anlegen, dann den alten entfernen.
ALTER TABLE deleted_refs
  ADD COLUMN owner_type ENUM('mission','rest') NOT NULL DEFAULT 'mission' AFTER device_id;
ALTER TABLE deleted_refs
  ADD UNIQUE KEY uq_dev_type_ref (device_id, owner_type, client_ref);
ALTER TABLE deleted_refs DROP INDEX uq_dev_ref;

-- S6 --------------------------------------------------------------------
-- Sortierregel der E-Mail-Spalte ausdruecklich festlegen. Bisher haengt die
-- Anmeldung an der Standardregel der jeweiligen Installation.
ALTER TABLE users
  MODIFY email VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL;
