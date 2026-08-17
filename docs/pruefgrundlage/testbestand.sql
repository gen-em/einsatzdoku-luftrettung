-- Repraesentativer Altbestand fuer die Migrationspruefung (Etappe 1).
-- Deckt die Faelle ab, die die Migration unterscheiden muss.
SET NAMES utf8mb4;

-- ---------------------------------------------------------------- Konten ---
INSERT INTO users (id, email, name, role) VALUES
  (1, 'eine-basis@example.org',   'Nutzerin mit einem Standort', 'user'),
  (2, 'zwei-basen@example.org',   'Nutzerin mit zwei Standorten', 'user'),
  (3, 'ohne-basis@example.org',   'Nutzerin ohne Standort',      'user'),
  (4, 'admin@example.org',        'Administratorin',             'admin');

INSERT INTO devices (id, user_id, device_id, api_key_hash, label) VALUES
  (1, 1, 'dev-u1', 'x', 'Uhr 1'),
  (2, 2, 'dev-u2', 'x', 'Uhr 2'),
  (3, 3, 'dev-u3', 'x', 'Uhr 3');

-- ------------------------------------------------------------ Stammdaten ---
-- u1: genau EIN Standort  -> Migration ordnet ohne Nachfrage zu (Schritt 8a)
-- u2: ZWEI Standorte      -> bleibt offen, Nachbearbeitungsseite (Schritt 8b)
-- u3: KEIN Standort       -> bleibt offen
-- NULL: zentrale Stammdaten der Admins
INSERT INTO bases (id, user_id, name) VALUES
  (1, 1,    'Standort Eins'),
  (2, 2,    'Standort Nord'),
  (3, 2,    'Standort Sued'),
  (10, NULL,'Zentraler Standort A'),
  (11, NULL,'Zentraler Standort B');

INSERT INTO aircraft (id, user_id, registration, p1, p2, hems, fr, other) VALUES
  (1, 1,    'D-HXAA', 1, 0, 1, 1, 0),   -- Pilot 1, HEMS-TC, Flugretter
  (2, 2,    'D-HXBB', 1, 1, 1, 0, 1),
  (3, NULL, 'D-HZEN', 1, 0, 1, 0, 0);   -- zentral

INSERT INTO crew_presets (id, user_id, role, name) VALUES
  (1, 1, 'p1',   'Anna Pilotin'),
  (2, 1, 'hems', 'Bert Technik'),
  (3, 2, 'p1',   'Clara Pilotin'),
  (4, NULL, 'p1','Zentral Pilot');

INSERT INTO transport_dests (id, user_id, name) VALUES
  (1, 1, 'Klinikum Kempten'),
  (2, 2, 'Klinikum Nord'),
  (3, NULL, 'Zentralklinik');

INSERT INTO resources (id, user_id, name) VALUES
  (1, 1, 'RTW 1'), (2, 2, 'NEF 2'), (3, NULL, 'Zentral-RTW');

INSERT INTO bw_units (id, user_id, name) VALUES
  (1, 1, 'Bergwacht Oberstdorf'), (2, 2, 'Bergwacht Nord'), (3, NULL, 'Bergwacht Zentral');

INSERT INTO user_defaults (user_id, kind, item_id) VALUES
  (1, 'base', 1), (1, 'aircraft', 1);

-- -------------------------------------------------------------- Diensttage ---
-- 1  u1: Rettungsmittel + Standort + Besatzung  -> kind='air', volle day_crew
-- 2  u1: OHNE Rettungsmittel, aber Besatzung    -> kind='air', nur belegte Rollen
-- 3  u1: OHNE alles                             -> kind=NULL (neutral)
-- 4  u1: im Papierkorb
-- 5  u2: Standort Nord + Rettungsmittel
-- 6  u3: ohne alles
INSERT INTO days (id, user_id, day, aircraft_id, base_id, crew_p1, crew_p2, crew_hems, crew_fr, crew_other, notes, deleted_at) VALUES
  (1, 1, '2026-01-10', 1,    1,    'Anna Pilotin', NULL, 'Bert Technik', 'Cara Retter', NULL, 'Tag mit allem',      NULL),
  (2, 1, '2026-01-12', NULL, NULL, 'Anna Pilotin', NULL, NULL,           NULL,          NULL, 'Nur Besatzung',      NULL),
  (3, 1, '2026-01-14', NULL, NULL, NULL,           NULL, NULL,           NULL,          NULL, 'Neutral',            NULL),
  (4, 1, '2026-01-16', 1,    1,    'Anna Pilotin', NULL, NULL,           NULL,          NULL, 'Papierkorb',         '2026-01-17 10:00:00'),
  (5, 2, '2026-01-10', 2,    2,    'Clara Pilotin',NULL, NULL,           NULL,          NULL, 'Tag u2',             NULL),
  (6, 3, '2026-01-11', NULL, NULL, NULL,           NULL, NULL,           NULL,          NULL, 'Tag u3 ohne Basis',  NULL);

-- ---------------------------------------------------------------- Einsaetze ---
-- 1  normal
-- 2  UEBER MITTERNACHT: day = 10.01., started_at 23:30 UTC, ended 11.01. 00:40
-- 3  crew_override = 1  -> wandert nach mission_crew
-- 4  crew_* belegt OHNE override -> darf NICHT wandern (Schritt 6)
-- 5  im Papierkorb, mit dem Tag geloescht
-- 6  u2
-- 7  u3, Tag 6
INSERT INTO missions (id, user_id, device_id, client_ref, day, started_at, ended_at, final, origin,
                      distance_m, winch, winch_cycles, bergwacht, secondary, transport_dest,
                      crew_override, crew_p1, crew_hems, notes, deleted_at, deleted_with_day) VALUES
  (1, 1, 1, 'ref-1', '2026-01-10', '2026-01-10 08:00:00', '2026-01-10 09:10:00', 1, 'watch',
      42000, 0, NULL, 0, 0, 'Klinikum Kempten', 0, NULL, NULL, 'Einsatz eins', NULL, 0),
  (2, 1, 1, 'ref-2', '2026-01-10', '2026-01-10 23:30:00', '2026-01-11 00:40:00', 1, 'watch',
      18000, 1, 2,    1, 0, 'Klinikum Kempten', 0, NULL, NULL, 'Ueber Mitternacht', NULL, 0),
  (3, 1, 1, 'ref-3', '2026-01-12', '2026-01-12 10:00:00', '2026-01-12 11:00:00', 1, 'watch',
      9000,  0, NULL, 0, 1, NULL,               1, 'Ersatz Pilot', 'Ersatz HEMS', 'Mit Override', NULL, 0),
  (4, 1, 1, 'ref-4', '2026-01-12', '2026-01-12 14:00:00', '2026-01-12 15:00:00', 1, 'watch',
      5000,  0, NULL, 0, 0, NULL,               0, 'Geist Pilot',  NULL,          'Ohne Override, Spalten belegt', NULL, 0),
  (5, 1, 1, 'ref-5', '2026-01-16', '2026-01-16 09:00:00', '2026-01-16 10:00:00', 1, 'watch',
      7000,  0, NULL, 0, 0, NULL,               0, NULL, NULL, 'Papierkorb', '2026-01-17 10:00:00', 1),
  (6, 2, 2, 'ref-6', '2026-01-10', '2026-01-10 07:00:00', '2026-01-10 08:00:00', 1, 'watch',
      3000,  0, NULL, 0, 0, 'Klinikum Nord',    0, NULL, NULL, 'Einsatz u2', NULL, 0),
  (7, 3, 3, 'ref-7', '2026-01-11', '2026-01-11 12:00:00', '2026-01-11 13:00:00', 1, 'watch',
      1000,  0, NULL, 0, 0, NULL,               0, NULL, NULL, 'Einsatz u3', NULL, 0);

INSERT INTO mission_phases (mission_id, phase, occurred_at) VALUES
  (1, 2, '2026-01-10 08:00:00'), (1, 3, '2026-01-10 08:05:00'), (1, 9, '2026-01-10 09:10:00'),
  (2, 2, '2026-01-10 23:30:00'), (2, 9, '2026-01-11 00:40:00'),
  (3, 2, '2026-01-12 10:00:00'), (3, 9, '2026-01-12 11:00:00');

-- ------------------------------------------------------------ Ruhesegmente ---
INSERT INTO rest_segments (id, user_id, device_id, client_ref, day, started_at, ended_at, final) VALUES
  (1, 1, 1, 'rest-1', '2026-01-10', '2026-01-10 12:00:00', '2026-01-10 13:00:00', 1),
  (2, 1, 1, 'rest-2', '2026-01-10', '2026-01-10 20:00:00', '2026-01-10 21:00:00', 1),
  (3, 2, 2, 'rest-3', '2026-01-10', '2026-01-10 09:00:00', '2026-01-10 09:30:00', 1);

-- Ein Einsatz OHNE zugehoerigen Diensttag: darf nach der Migration nicht
-- verwaisen (A11). Datum 2026-02-01 hat in `days` keine Zeile.
INSERT INTO missions (id, user_id, device_id, client_ref, day, started_at, ended_at, final, origin, notes) VALUES
  (8, 1, 1, 'ref-8', '2026-02-01', '2026-02-01 06:00:00', '2026-02-01 07:00:00', 1, 'watch', 'Tag fehlt in days');
