-- Einsatzdokumentation Notarzt — Schema v2.0 (MySQL >= 5.7 / MariaDB >= 10.2)
--
-- REIHENFOLGE DER TABELLEN. Seit dem Umbau auf Diensttage (Web 6.0.0) ist
-- `days` der Anker des Datenmodells: `missions` und `rest_segments` verweisen
-- mit `day_id` darauf, `days` selbst auf `bases` und `vehicles`. Die Stammdaten
-- und `days` stehen deshalb VOR `missions` — ein Fremdschluessel laesst sich
-- nicht auf eine Tabelle legen, die es noch nicht gibt.
SET NAMES utf8mb4;

CREATE TABLE users (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  email         VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL UNIQUE,  -- Sortierregel ausdruecklich: sonst haengt die Anmeldung an der Standardregel der Installation
  name          VARCHAR(120) NULL,                   -- Anzeigename (Kopfleiste)
  password_hash VARCHAR(255) NULL,                   -- Hash des im Browser abgeleiteten Auth-Tokens
  kdf_salt      VARCHAR(64) NULL,                    -- Salt der Browser-Schluesselableitung
  kdf_iter      INT UNSIGNED NOT NULL DEFAULT 310000, -- Rundenzahl der Ableitung, je Konto aenderbar
  pat_wrap_pw   TEXT NULL,                           -- Inhaltsschluessel, passwortverpackt (Pflicht-Verschlüsselung)
  pat_wrap_rc   TEXT NULL,                           -- Inhaltsschluessel, mit Wiederherstellungsschluessel verpackt
  pat_key_check CHAR(32) NULL,                       -- Pruefsumme des Inhaltsschluessels (im Browser gerechnet); NULL = Altbestand
  role          ENUM('user','admin') NOT NULL DEFAULT 'user',
  session_epoch INT UNSIGNED NOT NULL DEFAULT 0,     -- wird beim Passwortwechsel erhoeht; beendet offene Sitzungen
  account_key   CHAR(16) NULL UNIQUE,                -- Ordnername der Admin-Sicherung; einmalig vergeben, danach unveraenderlich (E17)
  logo_wahl     VARCHAR(20) NOT NULL DEFAULT '',     -- '' = Standard der Installation, sonst 'hubschrauber' | 'fahrzeug' | 'wechselnd' (E-P3-20)
  last_login    DATETIME NULL,                       -- UTC, letzte erfolgreiche Anmeldung; NULL = noch nie (Kontoseite, NutzerInnen-Liste)
  created_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE password_resets (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  token_hash CHAR(64) NOT NULL,                      -- sha256 des Tokens
  expires_at DATETIME NOT NULL,
  used_at    DATETIME NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  INDEX (token_hash)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE devices (
  id           INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id      INT UNSIGNED NOT NULL,
  device_id    VARCHAR(64) NOT NULL UNIQUE,          -- oeffentlich, Header X-Device-Id
  api_key_hash VARCHAR(255) NOT NULL,                -- password_hash des Geraeteschluessels
  label        VARCHAR(64) NULL,
  active       TINYINT(1) NOT NULL DEFAULT 1,        -- deaktiviert = Upload gesperrt, Daten bleiben
  last_seen    TIMESTAMP NULL,
  created_at   TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================================
-- STAMMDATEN
--
-- Der Standort ist der Anker (Entscheidung E15): Jedes Rettungsmittel, jede
-- Zielklinik, jede Besatzungs-Vorbelegung, jedes weitere Rettungsmittel und
-- jede Bergwacht-Bereitschaft gehoert GENAU EINEM Standort. Eine zweite,
-- standortuebergreifende Ebene gibt es bewusst nicht — der Preis dafuer ist
-- Doppelpflege, der Gewinn ein Modell mit einer Regel statt mit zwei.
-- ===========================================================================

CREATE TABLE bases (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  name VARCHAR(120) NOT NULL,
  -- Optionale Koordinaten, Quelle des Abfahrtorts 'base' (Konzept 3.5.1).
  -- Freiwillig: ein Standort ohne Koordinaten steht als Abfahrtort schlicht
  -- nicht zur Auswahl. Beim Anlegen eines Diensttags werden sie nach
  -- days.base_lat/base_lon EINGEFROREN (E8) — eine spaetere Korrektur wirkt
  -- nur auf neue Diensttage, ein Wachenumzug ist ein eigener Standort.
  lat DECIMAL(9,6) NULL,
  lon DECIMAL(9,6) NULL,
  UNIQUE KEY uq_user_name (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Auswahl zentraler Standorte je NutzerIn (E16). Nur ausgewaehlte zentrale
-- Standorte erscheinen in den Auswahllisten. EIGENE Standorte brauchen hier
-- keinen Eintrag — sie gelten immer als ausgewaehlt.
CREATE TABLE user_bases (
  user_id INT UNSIGNED NOT NULL,
  base_id INT UNSIGNED NOT NULL,
  PRIMARY KEY (user_id, base_id),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Rettungsmittel (bis Web 5.10.0: `aircraft`). Die Art ist binaer (E3) und
-- entscheidet ueber Besatzungsrollen und sichtbare Einsatzfelder.
CREATE TABLE vehicles (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  base_id INT UNSIGNED NOT NULL,                   -- jedes Rettungsmittel gehoert einem Standort
  name VARCHAR(64) NOT NULL,                       -- bis Web 5.10.0: `registration`
  kind ENUM('air','ground') NOT NULL,
  UNIQUE KEY uq_user_name (user_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Besetzte Rollen je Rettungsmittel. Die Rollenkennungen stammen aus dem
-- festen Katalog CREW_ROLES in db.php (E4), NICHT aus der Datenbank — deshalb
-- VARCHAR und kein ENUM: eine neue Rolle braucht dann keine Schemaaenderung.
CREATE TABLE vehicle_roles (
  vehicle_id INT UNSIGNED NOT NULL,
  role_code  VARCHAR(16) NOT NULL,
  PRIMARY KEY (vehicle_id, role_code),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Faehigkeiten je Rettungsmittel: 'winch' | 'bergwacht' (E29). Zwei getrennte
-- Haken — ein Hubschrauber kann eine Winde fuehren, ohne in einer
-- Bergwachtkooperation zu stehen, und umgekehrt. Faehigkeiten kommen
-- AUSSCHLIESSLICH an Rettungsmitteln mit kind='air' vor; beim Speichern eines
-- bodengebundenen Rettungsmittels sind vorhandene Zeilen zu entfernen.
CREATE TABLE vehicle_capabilities (
  vehicle_id INT UNSIGNED NOT NULL,
  capability VARCHAR(16) NOT NULL,
  PRIMARY KEY (vehicle_id, capability),
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE crew_presets (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  base_id INT UNSIGNED NOT NULL,
  -- Bis Web 5.10.0 ein ENUM('p1','p2','hems','fr','other'). Jetzt VARCHAR,
  -- damit neue Rollen ohne Schemaaenderung moeglich sind (Katalog: db.php).
  role_code VARCHAR(16) NOT NULL,
  name VARCHAR(120) NOT NULL,
  UNIQUE KEY uq_user_base_role_name (user_id, base_id, role_code, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vorbelegung: weitere Rettungsmittel (RTW, NEF, weitere Hubschrauber ...)
CREATE TABLE resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  base_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  UNIQUE KEY uq_user_base_res (user_id, base_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bw_units (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  base_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  UNIQUE KEY uq_user_base_name (user_id, base_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Vorbelegung: Zielkliniken (Vorschlagsliste fuer missions.transport_dest,
-- dieses Feld selbst bleibt Freitext ohne FK-Referenz).
CREATE TABLE transport_dests (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NULL,                       -- NULL = zentral (Admin-Eintrag)
  base_id INT UNSIGNED NOT NULL,
  name VARCHAR(190) NOT NULL,
  -- Optionale Koordinaten (E37). Werden AM EINSATZ eingefroren
  -- (missions.dest_lat/dest_lon), nicht ueber den Namen aufgeloest: das Feld
  -- ist Freitext mit <datalist>, eine Aufloesung ueber Namensgleichheit waere
  -- bruechig — ein umbenannter Eintrag verloere seine Koordinate.
  lat DECIMAL(9,6) NULL,
  lon DECIMAL(9,6) NULL,
  UNIQUE KEY uq_user_base_name (user_id, base_id, name),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Nutzerbezogene Standard-Vorbelegung fuer Diensttage (Standort/Rettungsmittel);
-- funktioniert fuer persoenliche UND zentrale Eintraege (item_id verweist je
-- nach kind auf bases.id bzw. vehicles.id, kein FK moeglich wegen zwei
-- Zieltabellen). Beim Speichern ist zu pruefen, dass das Standard-Rettungsmittel
-- zum Standard-Standort gehoert.
CREATE TABLE user_defaults (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  kind ENUM('base','vehicle') NOT NULL,
  item_id INT UNSIGNED NOT NULL,
  UNIQUE KEY uq_user_kind (user_id, kind),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================================
-- DIENSTTAGE
--
-- Ein Diensttag beginnt mit "Einsatztag starten" und endet mit "Einsatztag
-- beenden"; er traegt echte Start- und Endzeiten. JEDER Start erzeugt einen
-- eigenen Diensttag (E9) — mehrere pro Kalendertag sind zulaessig, etwa ein
-- Hubschrauberdienst am Tag und ein NEF-Nachtdienst am Abend. Deshalb ist
-- `day` NICHT mehr Schluessel, sondern nur noch Sortier- und Anzeigedatum.
--
-- EINGEFRORENE ANGABEN (E8). Alles, was der Diensttag aus Standort und
-- Rettungsmittel ableitet — Art, Rollensatz, Faehigkeiten, Bezeichnungen,
-- Standortkoordinaten —, wird beim Anlegen kopiert. `base_id` und
-- `vehicle_id` bleiben als Fremdschluessel erhalten, dienen aber nur noch dem
-- Filtern und Auswerten, NIEMALS der Anzeige. Nebeneffekt: Das Loeschen eines
-- Standorts oder Rettungsmittels beschaedigt keine Historie mehr, die
-- Fremdschluessel duerfen daher gefahrlos ON DELETE SET NULL tragen.
-- ===========================================================================
CREATE TABLE days (
  id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id  INT UNSIGNED NOT NULL,
  day      DATE NOT NULL,                  -- Datum des Dienstbeginns, nur Sortierung/Anzeige
  started_at DATETIME NULL,                -- UTC, echter Dienstbeginn
  ended_at   DATETIME NULL,                -- UTC, echtes Dienstende
  vehicle_id INT UNSIGNED NULL,            -- Rettungsmittel (Stammdaten, nur Filter/Auswertung)
  base_id    INT UNSIGNED NULL,            -- Standort (Stammdaten, nur Filter/Auswertung)
  -- Snapshot: NULL = neutral, noch nicht zugeordnet (E26). Ein neutraler
  -- Diensttag hat keine Art, keine Rollen und keine artabhaengigen Felder;
  -- Zeiten, Phasen, Track und Reanimation werden trotzdem voll erfasst.
  kind ENUM('air','ground') NULL,
  base_name    VARCHAR(120) NULL,          -- eingefrorene Standortbezeichnung
  base_lat     DECIMAL(9,6) NULL,          -- eingefrorene Standortkoordinate
  base_lon     DECIMAL(9,6) NULL,
  vehicle_name VARCHAR(64) NULL,           -- eingefrorene Rettungsmittelbezeichnung
  notes    TEXT NULL,
  deleted_at DATETIME NULL,
  INDEX idx_user_day (user_id, day),
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
  FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE SET NULL,
  FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Uhr-Kennungen eines Diensttags.
--
-- BEWUSST EINE EIGENE TABELLE statt einer Spalte in `days`: Nach dem
-- Zusammenfuehren traegt ein Diensttag legitim MEHRERE Kennungen. Damit
-- entfaellt jede Umleitungslogik — ingest.php schlaegt (device_id, day_ref)
-- nach und findet den richtigen Tag, auch wenn dieser inzwischen aufgenommen
-- wurde. Von Hand angelegte Diensttage haben hier keine Zeile.
CREATE TABLE day_refs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  day_id    INT UNSIGNED NOT NULL,
  device_id INT UNSIGNED NULL,             -- NULL = Geraet geloescht
  day_ref   VARCHAR(64) NOT NULL,          -- von der Uhr erzeugt
  UNIQUE KEY uq_dev_dayref (device_id, day_ref),
  FOREIGN KEY (day_id)    REFERENCES days(id)    ON DELETE CASCADE,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Besatzung des Diensttags, je Rolle eine Zeile.
--
-- DIE ZEILENMENGE IST DER SNAPSHOT DES ROLLENSATZES. Beim Anlegen eines
-- Diensttags wird fuer JEDE Rolle des gewaehlten Rettungsmittels eine Zeile
-- erzeugt, auch leere mit name = NULL. Welche Rollen ein Diensttag anbietet,
-- ergibt sich damit aus DIESER Tabelle und nicht aus dem Rettungsmittel —
-- spaetere Aenderungen am Rettungsmittel wirken nur auf neue Diensttage. Eine
-- zusaetzliche Snapshot-Tabelle ist dadurch nicht noetig.
CREATE TABLE day_crew (
  day_id    INT UNSIGNED NOT NULL,
  role_code VARCHAR(16) NOT NULL,
  name      VARCHAR(120) NULL,
  PRIMARY KEY (day_id, role_code),
  FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Eingefrorener Faehigkeitssatz des Diensttags (E29). Anders als beim
-- Rollensatz laesst er sich nicht in eine bestehende Tabelle falten, weil zu
-- einer Faehigkeit kein Wert gehoert — nur ihr Vorhandensein.
CREATE TABLE day_capabilities (
  day_id     INT UNSIGNED NOT NULL,
  capability VARCHAR(16) NOT NULL,
  PRIMARY KEY (day_id, capability),
  FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ===========================================================================
-- EINSAETZE
-- ===========================================================================
CREATE TABLE missions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  device_id  INT UNSIGNED NULL,               -- NULL = Geraet geloescht (Daten bleiben)
  client_ref VARCHAR(64) NOT NULL,
  -- ON DELETE SET NULL ist bewusst gewaehlt: Der Papierkorb arbeitet mit
  -- deleted_at und deleted_with_day, nicht mit echtem Loeschen. Ein Kaskadieren
  -- wuerde beim endgueltigen Entfernen eines Diensttags die bestehende Logik
  -- in trash_lib.php umgehen.
  day_id     INT UNSIGNED NULL,
  started_at DATETIME NOT NULL,                      -- UTC
  ended_at   DATETIME NULL,                          -- UTC, NULL solange final=0
  distance_m INT UNSIGNED NULL,
  ascent_m   INT UNSIGNED NULL,
  site_ele_m INT NULL,                              -- Hoehe Einsatzort bei PatientInnenkontakt (berechnet, s. site_elevation_lib.php)
  final      TINYINT(1) NOT NULL DEFAULT 0,
  manual     TINYINT(1) NOT NULL DEFAULT 0,           -- ausschliesslich: Uhr ueberschreibt Metadaten/Phasen/Rea nicht mehr (NICHT "von Hand angelegt" -- dafuer siehe origin)
  origin     ENUM('watch','manual','import') NOT NULL DEFAULT 'watch', -- Herkunft: wird beim Anlegen gesetzt und nie wieder geaendert
  edited     TINYINT(1) NOT NULL DEFAULT 0,           -- wurde nach dem Anlegen veraendert
  -- Zusatzfelder (mission_fields.php):
  transport_mode ENUM('air','ground','ambulant') NULL, -- Luft | Boden | Ambulant (E17)
  na_escort  TINYINT(1) NOT NULL DEFAULT 0,        -- NA-Begleitung, nur bei Luft und Boden
  transport_dest VARCHAR(190) NULL,
  -- Zielklinik-Koordinate, KLARTEXT wie transport_dest (E40): Der Name steht
  -- ohnehin unverschluesselt am Einsatz, die Koordinate folgt derselben
  -- Einstufung. Ihr Pin ist damit ohne Freischalten sichtbar.
  dest_lat   DECIMAL(9,6) NULL,
  dest_lon   DECIMAL(9,6) NULL,
  schockraum TINYINT(1) NOT NULL DEFAULT 0,        -- Zielklinik: Schockraum
  false_alarm TINYINT(1) NOT NULL DEFAULT 0,       -- Fehleinsatz / Storno / Abbruch (E17)
  -- Abfahrtortregel (Konzept 3.5.1). Gespeichert wird die REGEL, nicht die
  -- Koordinate — der Klartextwert verraet damit keinen Ort. Bei 'manual' liegt
  -- die Koordinate verschluesselt im pat_blob unter dem Schluessel `start`.
  start_src  ENUM('base','prev_site','prev_dest','manual') NULL,
  winch      TINYINT(1) NOT NULL DEFAULT 0,
  winch_cycles TINYINT NULL,
  winch_cycles_pat TINYINT NULL,
  winch_airload TINYINT(1) NOT NULL DEFAULT 0,
  bergwacht  TINYINT(1) NOT NULL DEFAULT 0,
  secondary  TINYINT(1) NOT NULL DEFAULT 0,        -- Sekundaertransport
  bw_unit    VARCHAR(120) NULL,
  bw_info    VARCHAR(190) NULL,
  other_ema  VARCHAR(190) NULL,
  other_resources VARCHAR(190) NULL,
  crew_override TINYINT(1) NOT NULL DEFAULT 0,     -- abweichende Besatzung (z. B. Rollenwechsel im Dienst)
  pat_blob   TEXT NULL,                              -- E2E-verschluesselt: Diagnose, Alter, Einsatzort (Server: nur Chiffretext)
  notes      TEXT NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  deleted_at       DATETIME NULL,
  deleted_with_day TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_dev_ref (device_id, client_ref),
  INDEX idx_user_started (user_id, started_at),
  INDEX idx_day (day_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
  FOREIGN KEY (day_id)    REFERENCES days(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Abweichende Besatzung EINES Einsatzes. Wird nur befuellt, wenn
-- missions.crew_override = 1. Die effektive Besatzung bleibt die bestehende
-- COALESCE-Regel, jetzt ueber zwei Tabellen statt ueber zwei Spaltensaetze;
-- api/mission.php liefert sie weiterhin als `crew_effektiv`.
CREATE TABLE mission_crew (
  mission_id INT UNSIGNED NOT NULL,
  role_code  VARCHAR(16) NOT NULL,
  name       VARCHAR(120) NULL,
  PRIMARY KEY (mission_id, role_code),
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE mission_phases (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mission_id  INT UNSIGNED NOT NULL,
  phase       TINYINT UNSIGNED NOT NULL,             -- 2..9; eine Phase 10 gibt es nicht
  occurred_at DATETIME NOT NULL,                     -- UTC
  lat DOUBLE NULL, lon DOUBLE NULL,
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
  INDEX (mission_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE resus_sessions (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mission_id INT UNSIGNED NOT NULL,
  started_at DATETIME NOT NULL,                      -- UTC = "Reanimationsbeginn"
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE,
  INDEX idx_mission (mission_id, started_at)         -- mehrere Reas pro Einsatz
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE resus_events (
  id          INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  session_id  INT UNSIGNED NOT NULL,
  type        VARCHAR(24) NOT NULL,                  -- adrenalin, rhythmuskontrolle, ...
  occurred_at DATETIME NOT NULL,                     -- UTC
  FOREIGN KEY (session_id) REFERENCES resus_sessions(id) ON DELETE CASCADE,
  INDEX (session_id, occurred_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Je Einsatz beteiligte Rettungsmittel; einzeln entfernbar, daher eigene Zeilen
CREATE TABLE mission_resources (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  mission_id INT UNSIGNED NOT NULL,
  name VARCHAR(120) NOT NULL,
  KEY idx_mres_mission (mission_id),
  FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE rest_segments (
  id         INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id    INT UNSIGNED NOT NULL,
  device_id  INT UNSIGNED NULL,               -- NULL = Geraet geloescht (Daten bleiben)
  client_ref VARCHAR(64) NOT NULL,
  day_id     INT UNSIGNED NULL,
  started_at DATETIME NOT NULL,
  ended_at   DATETIME NULL,
  final      TINYINT(1) NOT NULL DEFAULT 0,
  deleted_at       DATETIME NULL,
  deleted_with_day TINYINT(1) NOT NULL DEFAULT 0,
  UNIQUE KEY uq_dev_ref (device_id, client_ref),
  INDEX idx_user_started (user_id, started_at),
  INDEX idx_day (day_id),
  FOREIGN KEY (user_id)   REFERENCES users(id)   ON DELETE CASCADE,
  FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL,
  FOREIGN KEY (day_id)    REFERENCES days(id)    ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kopplungscodes, mit denen sich eine Uhr selbst Zugangsdaten holt (pair.php)
-- — ohne Abtippen langer Schluessel. Laenge, Alphabet und Gueltigkeit stehen in
-- db.php (PAIR_LEN, PAIR_CHARS, PAIR_TTL_MIN), damit sie nicht an drei Stellen
-- auseinanderlaufen. Die Einmaligkeit ist DURCHGESETZT: pair.php entwertet den
-- Code, bevor es ihn als gueltig annimmt (used_at wechselt genau einmal von
-- NULL auf einen Wert), nicht nur zugesichert.
CREATE TABLE pair_codes (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id INT UNSIGNED NOT NULL,
  code VARCHAR(8) NOT NULL UNIQUE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  used_at TIMESTAMP NULL,
  FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sperrliste geloeschter Einsaetze: verhindert, dass eine Uhr mit noch
-- gepufferten Daten einen im Web geloeschten Einsatz wieder anlegt.
-- Eintraege verfallen nach 90 Tagen (Aufraeumjob).
--
-- GILT NICHT FUER DIENSTTAGE: Beim Zusammenfuehren wird die Sperrliste
-- ausdruecklich NICHT bedient — die Kennungen leben in `day_refs` weiter und
-- zeigen danach auf den Zieltag. Genau deshalb ist die Umleitung ueber eine
-- eigene Tabelle geloest.
CREATE TABLE deleted_refs (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  device_id INT UNSIGNED NOT NULL,
  owner_type ENUM('mission','rest') NOT NULL DEFAULT 'mission',  -- Sperrliste gilt fuer BEIDE Arten
  client_ref VARCHAR(64) NOT NULL,
  deleted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_dev_type_ref (device_id, owner_type, client_ref)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Ratenschutz: Zaehlung von Fehlversuchen je Kontokennung UND je IP-Adresse.
-- Liegt bewusst in der Datenbank und nicht in der Sitzung — eine Zaehlung, die
-- der Aufrufer durch Wegwerfen seines Cookies zuruecksetzen kann, ist keine.
-- Aufraeumjob entsorgt abgelaufene Zeilen.
CREATE TABLE rate_limits (
  id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  topf          VARCHAR(32)  NOT NULL,          -- login | salt | reset | pair
  merkmal       VARCHAR(190) NOT NULL,          -- 'ip:<adresse>' oder 'id:<kennung>'
  versuche      INT UNSIGNED NOT NULL DEFAULT 0,
  fenster_start DATETIME     NOT NULL,
  gesperrt_bis  DATETIME     NULL,
  UNIQUE KEY uq_topf_merkmal (topf, merkmal),
  INDEX idx_fenster (fenster_start)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Kleiner Schluessel/Wert-Speicher fuer App-interne Zustaende (z. B. Wartung)
CREATE TABLE app_state (
  k VARCHAR(64) NOT NULL PRIMARY KEY,
  v VARCHAR(190) NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Trackpunkte fuer Einsaetze UND Ruhe-Segmente (owner_type unterscheidet)
CREATE TABLE track_points (
  owner_type ENUM('mission','rest') NOT NULL,
  owner_id   INT UNSIGNED NOT NULL,
  seq        INT UNSIGNED NOT NULL,
  lat DOUBLE NOT NULL, lon DOUBLE NOT NULL,
  ele DOUBLE NULL,
  ts  INT UNSIGNED NOT NULL,                          -- Unix-Epoche (s, UTC)
  PRIMARY KEY (owner_type, owner_id, seq)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Spuren als Blob (S2, SPUR1). Seit dieser Fassung ist `track_points` nur
-- noch der EINGANGSPUFFER der Uhr (Stufe 1); sobald ein Paket abgeschlossen
-- ist, wandern seine Punkte in eine Zeile hier (Stufe 2), und sechs Monate
-- nach Einsatzende werden sie ausgeduennt (Stufe 3).
--
-- Der Grund ist die Menge: 62,4 Byte je Punkt als Zeile gegen 3,58 als Blob.
-- Gelesen und geschrieben wird ausschliesslich ueber `spur_lib.php`; das
-- Format steht dort und in docs/Backup-Format.md.
--
-- WIE `track_points` OHNE FREMDSCHLUESSEL, aus demselben Grund (polymorph
-- ueber owner_type/owner_id). Die Loeschwege raeumen deshalb ausdruecklich
-- mit; der Wartungsjob ist nur das Sicherheitsnetz (F-S2-B).
CREATE TABLE track_blobs (
  owner_type    ENUM('mission','rest') NOT NULL,
  owner_id      INT UNSIGNED NOT NULL,
  stufe         TINYINT UNSIGNED NOT NULL,     -- 2 = verlustfrei, 3 = ausgeduennt
  n_original    INT UNSIGNED NOT NULL,         -- Punktzahl vor der Ausduennung
  n_gespeichert INT UNSIGNED NOT NULL,
  blob_daten    MEDIUMBLOB NOT NULL,
  erstellt_am   DATETIME NOT NULL,
  geaendert_am  DATETIME NOT NULL,
  PRIMARY KEY (owner_type, owner_id),
  KEY stufe_alter (stufe, geaendert_am)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Zustand der Hintergrundjobs (S2, jobs.php). Eine Zeile je Job.
--
-- Mehr als ein Zeitstempel: Sobald Arbeit in HAEPPCHEN anfaellt, braucht
-- jeder Job eine Fortsetzungsmarke (`zustand`, JSON), einen Rueckstand fuer
-- die Wartungsseite und eine Sperre gegen zwei gleichzeitige Laeufe.
-- `laeuft_seit` ist ein Zeitstempel und kein Flag: Ein Lauf, der mitten im
-- Haeppchen abstuerzt, liesse ein Flag fuer immer stehen.
CREATE TABLE jobs (
  job               VARCHAR(32) NOT NULL PRIMARY KEY,
  zustand           TEXT NULL,
  rueckstand        INT UNSIGNED NULL,
  letzter_lauf      DATETIME NULL,
  letzter_erfolg    DATETIME NULL,
  letzter_ausloeser VARCHAR(16) NULL,       -- cli | token | anfrage
  letzter_fehler    TEXT NULL,
  erledigt_zuletzt  INT UNSIGNED NOT NULL DEFAULT 0,
  laeuft_seit       DATETIME NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ---------------------------------------------------------------------------
-- Migrations-Buchfuehrung. Eine frische Installation ist bereits auf dem
-- Stand aller bisherigen Migrationen, deshalb werden sie hier als erledigt
-- eingetragen — update.php findet dann nichts mehr zu tun.
--
-- WICHTIG bei neuen Migrationen: die neue ID zusaetzlich hier ergaenzen,
-- sonst laeuft sie bei jeder Neuinstallation unnoetig (und ggf. auf Daten,
-- die es noch gar nicht gibt).
-- ---------------------------------------------------------------------------
-- Impressum und Datenschutzerklaerung dieser Installation (R32, seit Web 9.11.0).
-- Kein Vorgabeinhalt: Was darin steht, ist Sache des Betreibers; die Anwendung
-- liefert keinen Rechtstext mit. Solange nichts hinterlegt ist, zeigen
-- impressum.php und datenschutz.php ihren Leerzustand.
-- MEDIUMTEXT statt TEXT, weil TEXT 64 KB in BYTES sind und deutsche
-- Rechtstexte in utf8mb4 Umlaute haben.
CREATE TABLE rechtstexte (
  schluessel VARCHAR(32) NOT NULL PRIMARY KEY,  -- 'impressum' | 'datenschutz'
  inhalt     MEDIUMTEXT NULL,                   -- Markdown-Quelle; NULL/leer = nichts hinterlegt
  stand_am   DATE NULL                          -- im Editor von Hand gesetzt; NULL = keine Standzeile
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS schema_migrations (
  id         VARCHAR(120) NOT NULL PRIMARY KEY,
  applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  status     VARCHAR(16) NOT NULL DEFAULT 'applied'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO schema_migrations (id, status) VALUES
  ('2026_07_16_mehrere_reanimationen', 'skipped'),
  ('2026_07_17_flugtage', 'skipped'),
  ('2026_07_17_wartung', 'skipped'),
  ('2026_07_18_geraete_status', 'skipped'),
  ('2026_07_18_manuelle_einsaetze', 'skipped'),
  ('2026_07_19_phase10_entfernen', 'skipped'),
  ('2026_07_19_profil_name', 'skipped'),
  ('2026_07_19_geraete_entkoppeln', 'skipped'),
  ('2026_07_19_stammdaten', 'skipped'),
  ('2026_07_20_einsatzfelder_ort', 'skipped'),
  ('2026_07_20_stammdaten_defaults', 'skipped'),
  ('2026_07_20_kopplung', 'skipped'),
  ('2026_07_20_patientinnendaten', 'skipped'),
  ('2026_07_21_pflicht_e2e', 'skipped'),
  ('2026_07_22_tag_zuordnung', 'skipped'),
  ('2026_07_22_papierkorb', 'skipped'),
  ('2026_07_23_sekundaer_schockraum', 'skipped'),
  ('2026_07_24_rettungsmittel', 'skipped'),
  ('2026_07_25_einsatzort_hoehe', 'skipped'),
  ('2026_07_26_zentrale_stammdaten', 'skipped'),
  ('2026_07_27_crew_override', 'skipped'),
  ('2026_07_28_kdf_ver_entfernt', 'skipped'),
  ('2026_07_29_einsatznummer_verschluesselt', 'skipped'),
  ('2026_07_30_herkunft_bearbeitungsstatus', 'skipped'),
  ('2026_08_05_site_desc_entfernt', 'skipped'),
  ('2026_08_08_review_bausteine', 'skipped'),
  -- Nachgetragen (Web 5.9.0): Beide Migrationen fehlten hier, obwohl der Kopf
  -- von update.php es ausdruecklich verlangt. Auf einer Neuinstallation haben
  -- sie nichts zu tun -- die Zeitzonen-Umstellung findet keine Zeilen, die
  -- Geraeteumbenennung keine Geraete --, sie liefen aber trotzdem an.
  ('2026_08_13_zeitzonen_umstellung', 'skipped'),
  ('2026_08_14_geraetename_ohne_datum', 'skipped'),
  -- account_key steht oben schon in der Tabelle: Eine Neuinstallation legt
  -- die Kennung bei der Kontoanlage an, der Nachtrag ist nur fuer Bestand da.
  ('2026_08_16_kontokennung', 'skipped'),
  -- Der Umbau auf Diensttage und bodengebundene Rettungsmittel (Web 6.0.0).
  -- Dieses Schema IST das Ergebnis der Migration.
  ('2026_08_17_notarzt_erweiterung', 'skipped'),
  -- Nachgetragen (Web 9.10.1): Beide Spalten stehen oben schon in der Tabelle,
  -- die Kennungen fehlten hier. Folge auf einer Neuinstallation: update.php
  -- haette beide Migrationen erneut angesetzt und waere an der bereits
  -- vorhandenen Spalte haengengeblieben -- oder, schlimmer, still
  -- durchgelaufen (update.php schluckt MySQL 1060 „Duplicate column").
  -- last_login fehlte zusaetzlich in der Tabelle selbst; eine frisch
  -- eingerichtete Anwendung hatte die Spalte gar nicht.
  ('2026_08_27_logo_wahl', 'skipped'),
  ('2026_08_28_last_login', 'skipped'),
  -- Die Tabelle rechtstexte steht oben schon im Schema (Web 9.11.0).
  ('2026_08_30_rechtstexte', 'skipped'),
  -- track_blobs und jobs stehen oben schon im Schema (Web 10.0.0/10.1.0).
  ('2026_08_31_spur_blobs', 'skipped'),
  ('2026_08_31_jobs', 'skipped');
