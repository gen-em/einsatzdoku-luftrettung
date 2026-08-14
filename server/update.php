<?php
declare(strict_types=1);
/**
 * Datenbank-Updates (Migrationen).
 * - Nur fuer eingeloggte Admins.
 * - NEUE MIGRATION? Die ID zusaetzlich am Ende von schema.sql eintragen,
 *   damit Neuinstallationen sie nicht unnoetig ausfuehren.
 * - Fuehrt Buch in der Tabelle schema_migrations: jede Migration laeuft genau
 *   einmal. Mehrfaches Aufrufen dieser Seite ist ungefaehrlich.
 * - Neue Migrationen werden unten in $MIGRATIONS ergaenzt.
 */
// Notausgang: Aufruf per Kommandozeile (SSH) laeuft ohne Web-Session —
// fuer den Fall, dass der Login selbst von einer Migration abhaengt.
//   php update.php
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/db.php';              // liefert auch e()
} else {
    require_once __DIR__ . '/auth_guard.php';
    require_admin();
}

/**
 * IDs der Geraete, deren Name exakt dem automatisch vergebenen Muster
 * "Uhr (gekoppelt TT.MM.JJJJ)" entspricht (Migration vom 14.08.2026).
 *
 * Bewusst eng: Ein selbst vergebener Name — "Uhr Philipp", "Christoph 17",
 * auch "Uhr (gekoppelt, alt)" — passt nicht und bleibt unberuehrt.
 */
function _geraete_mit_datumsname(PDO $pdo): array
{
    $ids = [];
    foreach ($pdo->query('SELECT id, label FROM devices')->fetchAll() as $z) {
        if (preg_match('/^Uhr \(gekoppelt \d{2}\.\d{2}\.\d{4}\)$/', (string)$z['label'])) {
            $ids[] = (int)$z['id'];
        }
    }
    return $ids;
}

/* ---- Migrationsliste ------------------------------------------------------
 * 'id'    : eindeutiger, aufsteigender Name (Datum_stichwort)
 * 'label' : Beschreibung fuer die Anzeige
 * 'skip'  : optionale Pruefung; liefert true, wenn die Aenderung in dieser
 *           Datenbank nicht noetig ist (z. B. frisch mit aktuellem Schema
 *           installiert) -> wird als "uebersprungen" verbucht
 * 'sql'   : Liste der auszufuehrenden Statements
 * 'run'   : alternativ eine Funktion statt einer Anweisungsliste
 *
 * ---- Zwei Angaben fuer destruktive Migrationen (M6-01) ---------------------
 *
 * 'zerstoert' : Klartext, WAS unwiderruflich verlorengeht. Allein diese
 *               Angabe hebt die Migration in der Vorschau hervor.
 * 'inhalt'    : Liste [Tabelle, Spalte, Beschreibung] der Spalten, deren
 *               INHALT diese Migration vernichten wuerde. Steht dort etwas
 *               drin, wird die Migration NICHT ausgefuehrt, sondern gemeldet.
 *
 * WARUM BEIDE UND NICHT EINE
 * Nicht jede destruktive Migration darf am Inhalt scheitern. Bei
 * 2026_07_19_phase10_entfernen IST das Loeschen der Zweck, und die Werte sind
 * bedeutungslos geworden — eine Inhaltspruefung wuerde die Migration dauerhaft
 * blockieren und damit genau das Gegenteil bewirken. Die Inhaltspruefung gilt
 * deshalb nur dort, wo eine Spalte VON HAND EINGEGEBENE Daten enthielt und
 * die Migration davon ausgeht, dass sie anderswo gerettet wurden.
 *
 * WARUM DAS NOETIG IST
 * Fuer die Betreiberinstallation ist jeder dieser Faelle dokumentiert
 * erledigt. Das Projekt liegt aber offen: Eine zweite Station verliert die
 * betroffenen Spalten in dem Moment, in dem jemand die Wartungsseite oeffnet
 * und den Knopf drueckt — ohne je gelesen zu haben, dass sie vorher etwas
 * haette retten muessen.
 *
 * Die STRUKTURPRUEFUNG in 'skip' bleibt daneben bestehen: Sie beantwortet die
 * andere Frage, naemlich ob die Aenderung ueberhaupt noch aussteht. Eine
 * bereits geloeschte Spalte hat keinen Inhalt mehr — ohne 'skip' waere sie
 * damit von einer vollen nicht zu unterscheiden.
 */
$MIGRATIONS = [
    [
        'id'    => '2026_07_16_mehrere_reanimationen',
        'label' => 'Mehrere Reanimationen pro Einsatz erlauben',
        'skip'  => function (PDO $pdo): bool {
            // Nur noetig, wenn der alte UNIQUE-Index uq_mission existiert
            $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics
                                WHERE table_schema = DATABASE()
                                  AND table_name = 'resus_sessions'
                                  AND index_name = 'uq_mission'");
            $q->execute();
            return (int)$q->fetchColumn() === 0;
        },
        'sql'   => [
            // Reihenfolge wichtig: Der Fremdschluessel braucht durchgehend
            // einen Index auf mission_id. Erst den Ersatz anlegen (mission_id
            // ist dort die fuehrende Spalte), dann den UNIQUE entfernen —
            // sonst MySQL-Fehler 1553.
            'ALTER TABLE resus_sessions ADD INDEX idx_mission (mission_id, started_at)',
            'ALTER TABLE resus_sessions DROP INDEX uq_mission',
        ],
    ],
    [
        'id'    => '2026_07_17_flugtage',
        'label' => 'Flugtage mit editierbaren Feldern (Maschine, Basis, Besatzung, Notizen)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'days'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            'CREATE TABLE days (
               id       INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id  INT UNSIGNED NOT NULL,
               day      DATE NOT NULL,
               aircraft VARCHAR(64) NULL,
               base     VARCHAR(64) NULL,
               crew     VARCHAR(190) NULL,
               notes    TEXT NULL,
               UNIQUE KEY uq_user_day (user_id, day),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ],
    ],
    [
        'id'    => '2026_07_17_wartung',
        'label' => 'Zustandsspeicher für automatische Wartung (Aufräumjob)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'app_state'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            'CREATE TABLE app_state (
               k VARCHAR(64) NOT NULL PRIMARY KEY,
               v VARCHAR(190) NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ],
    ],
    [
        'id'    => '2026_07_18_geraete_status',
        'label' => 'Geräte deaktivieren statt löschen (active-Flag)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'devices' AND column_name = 'active'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE devices ADD COLUMN active TINYINT(1) NOT NULL DEFAULT 1 AFTER label",
        ],
    ],
    [
        'id'    => '2026_07_18_manuelle_einsaetze',
        'label' => 'Manuelle Einsätze: Schutzmarker + Zusatzfelder (Einsatznummer, Notizen)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'manual'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE missions ADD COLUMN manual TINYINT(1) NOT NULL DEFAULT 0 AFTER final",
            "ALTER TABLE missions ADD COLUMN mission_no VARCHAR(64) NULL AFTER manual",
            "ALTER TABLE missions ADD COLUMN notes TEXT NULL AFTER mission_no",
        ],
    ],
    [
        'id'    => '2026_07_19_phase10_entfernen',
        'label' => 'Phase 10 abgeschafft: alte Zeitstempel löschen, Einsatzende = Phase 9',
        'zerstoert' => 'Alle Zeitstempel der Phase 10 werden gelöscht.',
        // BEWUSST OHNE Inhaltspruefung: Das Loeschen IST der Zweck. Die Phase
        // gibt es nicht mehr (JSON-Vertrag, PHASE_LABELS), ihre Zeitstempel
        // sind bedeutungslos, und der Einsatzschluss wird eine Zeile darueber
        // aus Phase 9 nachgetragen. Eine Inhaltspruefung wuerde die Migration
        // genau auf den Installationen blockieren, auf denen sie gebraucht wird.
        'sql'   => [
            "UPDATE missions m
               JOIN (SELECT mission_id, MAX(occurred_at) AS t FROM mission_phases
                     WHERE phase = 9 GROUP BY mission_id) x ON x.mission_id = m.id
               SET m.ended_at = x.t",
            "DELETE FROM mission_phases WHERE phase = 10",
        ],
    ],
    [
        'id'    => '2026_07_19_profil_name',
        'label' => 'Profil: Anzeigename für NutzerInnen',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'name'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE users ADD COLUMN name VARCHAR(120) NULL AFTER email",
        ],
    ],
    [
        'id'    => '2026_07_19_geraete_entkoppeln',
        'label' => 'Geräte löschbar ohne Datenverlust (Einsätze/Segmente bleiben, Verweis wird geleert)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT IS_NULLABLE FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'device_id'");
            return $q->fetchColumn() === 'YES';
        },
        'run'   => function (PDO $pdo): void {
            foreach ([['missions', 'device_id'], ['rest_segments', 'device_id']] as $t) {
                [$tbl, $col] = $t;
                // Bestehende FK-Namen sind auto-generiert -> dynamisch ermitteln
                $q = $pdo->prepare("SELECT CONSTRAINT_NAME FROM information_schema.KEY_COLUMN_USAGE
                                    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ?
                                      AND COLUMN_NAME = ? AND REFERENCED_TABLE_NAME = 'devices'");
                $q->execute([$tbl, $col]);
                foreach ($q->fetchAll(PDO::FETCH_COLUMN) as $fk) {
                    $pdo->exec("ALTER TABLE `$tbl` DROP FOREIGN KEY `$fk`");
                }
                $pdo->exec("ALTER TABLE `$tbl` MODIFY `$col` INT UNSIGNED NULL");
                $pdo->exec("ALTER TABLE `$tbl` ADD CONSTRAINT fk_{$tbl}_device
                            FOREIGN KEY (`$col`) REFERENCES devices(id) ON DELETE SET NULL");
            }
        },
    ],
    [
        'id'    => '2026_07_19_stammdaten',
        'label' => 'Stammdaten (Standorte, Hubschrauber mit Rollen, Besatzungs-Vorbelegungen, Bergwacht) + Flugtag-Dropdowns',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'bases'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "CREATE TABLE bases (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               name VARCHAR(120) NOT NULL,
               UNIQUE KEY uq_user_name (user_id, name),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE aircraft (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               registration VARCHAR(64) NOT NULL,
               p1 TINYINT(1) NOT NULL DEFAULT 0, p2 TINYINT(1) NOT NULL DEFAULT 0,
               hems TINYINT(1) NOT NULL DEFAULT 0, fr TINYINT(1) NOT NULL DEFAULT 0,
               other TINYINT(1) NOT NULL DEFAULT 0,
               UNIQUE KEY uq_user_reg (user_id, registration),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE crew_presets (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               role ENUM('p1','p2','hems','fr','other') NOT NULL,
               name VARCHAR(120) NOT NULL,
               UNIQUE KEY uq_user_role_name (user_id, role, name),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE bw_units (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               name VARCHAR(120) NOT NULL,
               UNIQUE KEY uq_user_name (user_id, name),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "ALTER TABLE days ADD COLUMN aircraft_id INT UNSIGNED NULL AFTER day",
            "ALTER TABLE days ADD COLUMN base_id INT UNSIGNED NULL AFTER aircraft_id",
            "ALTER TABLE days ADD COLUMN crew_p1 VARCHAR(120) NULL AFTER base_id",
            "ALTER TABLE days ADD COLUMN crew_p2 VARCHAR(120) NULL AFTER crew_p1",
            "ALTER TABLE days ADD COLUMN crew_hems VARCHAR(120) NULL AFTER crew_p2",
            "ALTER TABLE days ADD COLUMN crew_fr VARCHAR(120) NULL AFTER crew_hems",
            "ALTER TABLE days ADD COLUMN crew_other VARCHAR(120) NULL AFTER crew_fr",
            "ALTER TABLE days ADD CONSTRAINT fk_days_aircraft
               FOREIGN KEY (aircraft_id) REFERENCES aircraft(id) ON DELETE SET NULL",
            "ALTER TABLE days ADD CONSTRAINT fk_days_base
               FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE SET NULL",
        ],
    ],
    [
        'id'    => '2026_07_20_einsatzfelder_ort',
        'label' => 'Einsatzfelder-Ausbau (Winde, Bergwacht, Transportziel …), Einsatzort mit Koordinaten, Lösch-Sperrliste',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'winch'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE missions ADD COLUMN transport_dest VARCHAR(190) NULL AFTER mission_no",
            "ALTER TABLE missions ADD COLUMN site_desc VARCHAR(190) NULL AFTER transport_dest",
            "ALTER TABLE missions ADD COLUMN winch TINYINT(1) NOT NULL DEFAULT 0 AFTER site_desc",
            "ALTER TABLE missions ADD COLUMN winch_cycles TINYINT NULL AFTER winch",
            "ALTER TABLE missions ADD COLUMN winch_cycles_pat TINYINT NULL AFTER winch_cycles",
            "ALTER TABLE missions ADD COLUMN winch_airload TINYINT(1) NOT NULL DEFAULT 0 AFTER winch_cycles_pat",
            "ALTER TABLE missions ADD COLUMN bergwacht TINYINT(1) NOT NULL DEFAULT 0 AFTER winch_airload",
            "ALTER TABLE missions ADD COLUMN bw_unit VARCHAR(120) NULL AFTER bergwacht",
            "ALTER TABLE missions ADD COLUMN bw_info VARCHAR(190) NULL AFTER bw_unit",
            "ALTER TABLE missions ADD COLUMN other_ema VARCHAR(190) NULL AFTER bw_info",
            "ALTER TABLE missions ADD COLUMN other_resources VARCHAR(190) NULL AFTER other_ema",
            "ALTER TABLE missions ADD COLUMN loc_addr VARCHAR(255) NULL AFTER other_resources",
            "ALTER TABLE missions ADD COLUMN loc_lat DOUBLE NULL AFTER loc_addr",
            "ALTER TABLE missions ADD COLUMN loc_lon DOUBLE NULL AFTER loc_lat",
            "CREATE TABLE deleted_refs (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               device_id INT UNSIGNED NOT NULL,
               client_ref VARCHAR(64) NOT NULL,
               deleted_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               UNIQUE KEY uq_dev_ref (device_id, client_ref)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
    ],
    [
        'id'    => '2026_07_20_stammdaten_defaults',
        'label' => 'Standard-Maschine und Standard-Standort (Flugtag-Vorbelegung)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'aircraft' AND column_name = 'is_default'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE aircraft ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE bases ADD COLUMN is_default TINYINT(1) NOT NULL DEFAULT 0",
        ],
    ],
    [
        'id'    => '2026_07_20_kopplung',
        'label' => 'Geräte-Kopplung per Kurzcode (5 Zeichen, 60 Minuten gültig)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'pair_codes'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "CREATE TABLE pair_codes (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               code VARCHAR(8) NOT NULL UNIQUE,
               created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
               used_at TIMESTAMP NULL,
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
        ],
    ],
    [
        'id'    => '2026_07_20_patientinnendaten',
        'label' => 'PatientInnendaten-Modul: Ende-zu-Ende-Verschlüsselung (Schlüsselableitung, Modul-Einstellungen, Datenblob)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'kdf_salt'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE users ADD COLUMN kdf_salt VARCHAR(64) NULL",
            "ALTER TABLE users ADD COLUMN kdf_ver TINYINT NOT NULL DEFAULT 0",
            "ALTER TABLE users ADD COLUMN pat_enabled TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE users ADD COLUMN pat_fields VARCHAR(190) NULL",
            "ALTER TABLE users ADD COLUMN pat_wrap_pw TEXT NULL",
            "ALTER TABLE users ADD COLUMN pat_wrap_rc TEXT NULL",
            "ALTER TABLE missions ADD COLUMN pat_blob TEXT NULL",
        ],
    ],
    [
        'id'    => '2026_07_21_pflicht_e2e',
        'label' => 'Pflicht-Verschlüsselung: Einsatzort wandert in den verschlüsselten Block (Klartext-Altdaten entfallen), Felder Diagnose/Alter, Modul-Schalter entfallen',
        'zerstoert' => 'Die Klartext-Spalten des Einsatzorts (Adresse, Koordinaten) '
                     . 'werden gelöscht. Ein automatischer Umzug in den verschlüsselten '
                     . 'Block ist nicht möglich — pat_blob entsteht ausschließlich im '
                     . 'Browser, der Server hat den Schlüssel nach Bauart nicht.',
        'inhalt' => [
            ['missions', 'loc_addr', 'Einsatzort im Klartext'],
            ['missions', 'loc_lat',  'Einsatzort-Koordinate'],
            ['missions', 'loc_lon',  'Einsatzort-Koordinate'],
        ],
        // users.pat_enabled/pat_fields stehen bewusst NICHT in der Liste: Das
        // sind Modulschalter, keine eingegebenen Daten.
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'loc_addr'");
            return (int)$q->fetchColumn() === 0;
        },
        'sql'   => [
            "ALTER TABLE missions DROP COLUMN loc_addr",
            "ALTER TABLE missions DROP COLUMN loc_lat",
            "ALTER TABLE missions DROP COLUMN loc_lon",
            "ALTER TABLE users DROP COLUMN pat_enabled",
            "ALTER TABLE users DROP COLUMN pat_fields",
        ],
    ],
    [
        'id'    => '2026_07_22_tag_zuordnung',
        'label' => 'Tageszuordnung: Tag = lokales Datum des Einsatz-/Segmentbeginns (Wechsel 0:00); Bestand wird neu zugeordnet',
        'run'   => function (PDO $pdo): void {
            global $CFG;
            $tz  = new DateTimeZone($CFG['app']['timezone'] ?? 'Europe/Berlin');
            $utc = new DateTimeZone('UTC');
            foreach (['missions', 'rest_segments'] as $tab) {
                $rows = $pdo->query("SELECT id, day, started_at FROM `$tab`")->fetchAll(PDO::FETCH_ASSOC);
                $upd = $pdo->prepare("UPDATE `$tab` SET day = ? WHERE id = ?");
                foreach ($rows as $r) {
                    $d = new DateTime((string)$r['started_at'], $utc);
                    $d->setTimezone($tz);
                    $local = $d->format('Y-m-d');
                    if ($local !== (string)$r['day']) { $upd->execute([$local, (int)$r['id']]); }
                }
            }
        },
    ],
    [
        'id'    => '2026_07_22_papierkorb',
        'label' => 'Papierkorb: Einsätze, Ruhesegmente und Flugtage werden erst als gelöscht markiert',
        'sql'   => [
            "ALTER TABLE missions ADD COLUMN deleted_at DATETIME NULL",
            "ALTER TABLE missions ADD COLUMN deleted_with_day TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE missions ADD INDEX idx_missions_deleted (user_id, deleted_at)",
            "ALTER TABLE rest_segments ADD COLUMN deleted_at DATETIME NULL",
            "ALTER TABLE rest_segments ADD COLUMN deleted_with_day TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE days ADD COLUMN deleted_at DATETIME NULL",
        ],
    ],
    [
        'id'    => '2026_07_23_sekundaer_schockraum',
        'label' => 'Neue Felder: Sekundärtransport und Schockraum',
        'sql'   => [
            "ALTER TABLE missions ADD COLUMN secondary TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE missions ADD COLUMN schockraum TINYINT(1) NOT NULL DEFAULT 0",
        ],
    ],
    [
        'id'    => '2026_07_24_rettungsmittel',
        'label' => 'Andere Rettungsmittel: Vorbelegungen und Zuordnung je Einsatz',
        'run'   => function (PDO $pdo): void {
            $pdo->exec("CREATE TABLE IF NOT EXISTS resources (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                user_id INT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                UNIQUE KEY uq_user_res (user_id, name),
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");
            $pdo->exec("CREATE TABLE IF NOT EXISTS mission_resources (
                id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                mission_id INT UNSIGNED NOT NULL,
                name VARCHAR(120) NOT NULL,
                KEY idx_mres_mission (mission_id),
                FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

            // Bisherige Freitexte uebernehmen: an Komma/Semikolon trennen,
            // damit jeder Eintrag einzeln entfernbar wird.
            $alt = $pdo->query("SELECT id, other_resources FROM missions
                                WHERE other_resources IS NOT NULL AND other_resources <> ''");
            $ins = $pdo->prepare('INSERT INTO mission_resources (mission_id, name) VALUES (?, ?)');
            foreach ($alt->fetchAll() as $m) {
                foreach (preg_split('/[,;]/u', (string)$m['other_resources']) as $teil) {
                    $teil = trim($teil);
                    if ($teil !== '') { $ins->execute([(int)$m['id'], mb_substr($teil, 0, 120)]); }
                }
            }
        },
    ],
    [
        'id'    => '2026_07_25_einsatzort_hoehe',
        'label' => 'Einsatzort-Höhe (site_ele_m): neues Feld + Backfill aus Track',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'site_ele_m'");
            return (int)$q->fetchColumn() > 0;
        },
        'run'   => function (PDO $pdo): void {
            $pdo->exec('ALTER TABLE missions ADD COLUMN site_ele_m INT NULL AFTER ascent_m');

            // Backfill: alle Einsaetze mit Phase 5 oder 6 und vorhandenem Track.
            // Dieselbe Logik wie bei Uhr-Upload/manuellem Speichern — eine
            // einzige Implementierung in site_elevation_lib.php.
            require_once __DIR__ . '/site_elevation_lib.php';
            $ids = $pdo->query("SELECT DISTINCT m.id FROM missions m
                                JOIN mission_phases p ON p.mission_id = m.id AND p.phase IN (5, 6)
                                WHERE EXISTS (SELECT 1 FROM track_points t
                                              WHERE t.owner_type = 'mission' AND t.owner_id = m.id)")
                        ->fetchAll(PDO::FETCH_COLUMN);
            foreach ($ids as $mid) {
                compute_site_elevation($pdo, (int)$mid);
            }
        },
    ],
    [
        'id'    => '2026_07_26_zentrale_stammdaten',
        'label' => 'Zentrale (globale) Stammdaten durch Admin, Transportziele als Stammdaten, '
                 . 'nutzerbezogene Standard-Vorbelegung (user_defaults)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'transport_dests'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE bases MODIFY user_id INT UNSIGNED NULL",
            "ALTER TABLE aircraft MODIFY user_id INT UNSIGNED NULL",
            "ALTER TABLE crew_presets MODIFY user_id INT UNSIGNED NULL",
            "ALTER TABLE resources MODIFY user_id INT UNSIGNED NULL",
            "ALTER TABLE bw_units MODIFY user_id INT UNSIGNED NULL",
            "CREATE TABLE transport_dests (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NULL,
               name VARCHAR(190) NOT NULL,
               UNIQUE KEY uq_user_name (user_id, name),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "CREATE TABLE user_defaults (
               id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               user_id INT UNSIGNED NOT NULL,
               kind ENUM('base','aircraft') NOT NULL,
               item_id INT UNSIGNED NOT NULL,
               UNIQUE KEY uq_user_kind (user_id, kind),
               FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",
            "INSERT INTO user_defaults (user_id, kind, item_id)
               SELECT user_id, 'base', id FROM bases WHERE is_default = 1 AND user_id IS NOT NULL",
            "INSERT INTO user_defaults (user_id, kind, item_id)
               SELECT user_id, 'aircraft', id FROM aircraft WHERE is_default = 1 AND user_id IS NOT NULL",
        ],
    ],
    [
        'id'    => '2026_07_27_crew_override',
        'label' => 'Abweichende Besatzung je Einsatz (Crew-Override)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'crew_override'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            // Ein einziges ALTER: entweder alle sechs Spalten oder keine.
            // Die Spalten bleiben NULL, solange keine Abweichung vorliegt —
            // die Tagescrew (days.crew_*) bleibt die einzige Wahrheit.
            "ALTER TABLE missions
               ADD COLUMN crew_override TINYINT(1) NOT NULL DEFAULT 0 AFTER other_resources,
               ADD COLUMN crew_p1    VARCHAR(120) NULL AFTER crew_override,
               ADD COLUMN crew_p2    VARCHAR(120) NULL AFTER crew_p1,
               ADD COLUMN crew_hems  VARCHAR(120) NULL AFTER crew_p2,
               ADD COLUMN crew_fr    VARCHAR(120) NULL AFTER crew_hems,
               ADD COLUMN crew_other VARCHAR(120) NULL AFTER crew_fr",
        ],
    ],
    [
        'id'    => '2026_07_28_kdf_ver_entfernt',
        'label' => 'Spalte users.kdf_ver entfernt (wurde geschrieben, aber nie gelesen)',
        'zerstoert' => 'Die Spalte users.kdf_ver wird gelöscht.',
        // OHNE Inhaltspruefung: Die Spalte trug eine Versionskennung, die nie
        // ein Codepfad gelesen hat. Sie ist per Definition gefuellt und per
        // Definition bedeutungslos — eine Inhaltspruefung wuerde hier jede
        // Installation blockieren, ohne dass irgendetwas zu retten waere.
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'kdf_ver'");
            return (int)$q->fetchColumn() === 0;
        },
        'sql'   => [
            // Seit der Umstellung auf Browser-Schluesselableitung gibt es nur
            // noch einen Login-Weg; eine Versionskennung wird nicht gebraucht.
            "ALTER TABLE users DROP COLUMN kdf_ver",
        ],
    ],
    [
        'id'    => '2026_07_29_einsatznummer_verschluesselt',
        'label' => 'Einsatznummer wandert in den verschlüsselten pat_blob',
        'zerstoert' => 'Die Spalte missions.mission_no wird gelöscht.',
        'inhalt' => [['missions', 'mission_no', 'Einsatznummer im Klartext']],
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'mission_no'");
            return (int)$q->fetchColumn() === 0;
        },
        // Vom Betreiber bestaetigt: In der Produktivinstanz ist keine einzige
        // Einsatznummer belegt — die Spalte kann deshalb verlustfrei entfernt
        // werden, ohne vorherige Ueberfuehrung in den pat_blob (dazu braeuchte
        // der Server ohnehin den Schluessel, den er nach Bauart nicht hat).
        'sql'   => [
            "ALTER TABLE missions DROP COLUMN mission_no",
        ],
    ],
    [
        'id'    => '2026_07_30_herkunft_bearbeitungsstatus',
        'label' => 'Herkunft (origin) und Bearbeitungsstatus (edited) getrennt von manual',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'origin'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            "ALTER TABLE missions
               ADD COLUMN origin ENUM('watch','manual','import') NOT NULL DEFAULT 'watch' AFTER manual,
               ADD COLUMN edited TINYINT(1) NOT NULL DEFAULT 0 AFTER origin",
            // Herkunft laesst sich fuer Bestandsdaten zuverlaessig aus client_ref
            // rekonstruieren, weil jede anlegende Stelle ein eigenes Praefix
            // vergibt: einsatz_form.php -> 'man-', api/import_commit.php ->
            // 'imp-'. Uhr-Uploads liefern das client_ref der Uhr (kein Praefix).
            "UPDATE missions SET origin = 'manual' WHERE client_ref LIKE 'man-%'",
            "UPDATE missions SET origin = 'import' WHERE client_ref LIKE 'imp-%'",
            // Ein Einsatz mit manual = 1, der weder von Hand angelegt noch
            // importiert wurde, kann diesen Marker nur durch eine Bearbeitung
            // bekommen haben. Fuer Hand- und Importeintraege laesst sich eine
            // spaetere Bearbeitung rueckwirkend nicht mehr feststellen; sie
            // starten deshalb bewusst mit edited = 0 (nicht im Changelog oder
            // Handbuch zu erwaehnen — der betroffene Bestand ist ueberschaubar
            // und dem Betreiber bekannt. Diese Begruendung bleibt hier stehen,
            // damit sie spaeter nicht versehentlich als Fehler "korrigiert" wird).
            "UPDATE missions SET edited = 1
               WHERE manual = 1 AND client_ref NOT LIKE 'man-%' AND client_ref NOT LIKE 'imp-%'",
        ],
    ],
    [
        'id'    => '2026_08_05_site_desc_entfernt',
        'label' => 'Beschreibung Einsatzort: Klartextspalte entfernen (liegt seit Web 3.3.0 im verschlüsselten Block)',
        'zerstoert' => 'Die Spalte missions.site_desc wird gelöscht. Auf einer '
                     . 'Installation, die den Klartextbestand nicht vorher gerettet '
                     . 'hat, sind die Beschreibungen des Einsatzorts danach weg.',
        'inhalt' => [['missions', 'site_desc', 'Beschreibung des Einsatzorts im Klartext']],
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'missions' AND column_name = 'site_desc'");
            return (int)$q->fetchColumn() === 0;
        },
        'sql'   => [
            // Der Klartextbestand wurde vor dieser Auslieferung ueber eine
            // einmalige Rettungsseite gesichert und von Hand in den
            // verschluesselten Block nachgetragen; die Seite ist seit Web 3.4.0
            // entfernt (Changelog). Ein automatischer Umzug war nie moeglich:
            // pat_blob entsteht ausschliesslich im Browser.
            //
            // ACHTUNG, DIE SPALTE WIRD GELOESCHT: Auf einer Installation, die
            // den Klartextbestand NICHT vorher gerettet hat, gingen die
            // Beschreibungen des Einsatzorts hier verloren. Seit Web 4.7.0
            // fangen 'zerstoert' und 'inhalt' oben genau diesen Fall ab: Steht
            // in der Spalte noch etwas, wird sie nicht geloescht, sondern
            // gemeldet (M6-01).
            "ALTER TABLE missions DROP COLUMN site_desc",
        ],
    ],
    [
        'id'    => '2026_08_08_review_bausteine',
        'label' => 'Ableitungsrunden, Schlüssel-Prüfsumme, Sitzungszähler, Ratenschutz, '
                 . 'Sperrliste für Ruhe-Segmente, festgelegte Sortierregel',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'kdf_iter'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            /* --- S1: Rundenzahl der Schluesselableitung je Konto -----------
             * BESONDERE SORGFALT. Nicht wegen der Datenmenge, sondern weil
             * die Aenderung die Schluesselableitung beruehrt und ein Fehler
             * dort ALLE KONTEN GLEICHZEITIG AUSSPERRT.
             *
             * Dieser Schritt legt die Spalte NUR AN und fuellt sie mit dem
             * heutigen Wert (ITER in assets/crypto.js). Kein Code liest sie;
             * der Salt-Endpunkt bleibt unveraendert. Das Verhalten aendert
             * sich durch diese Migration nicht.
             *
             * Die drei Folgeschritte (Salt-Endpunkt liefert die Zahl mit;
             * Browser rechnet mit dem gelieferten Wert; stille Anhebung bei
             * der naechsten Anmeldung) folgen in einer eigenen Auslieferung,
             * jeder Schritt fuer sich rueckwaertsvertraeglich.
             *
             * Der Vorgabewert ist wesentlich: Zwischen dieser Migration und
             * dem Zeitpunkt, an dem der Code die Spalte kennt, koennen neue
             * Konten entstehen, deren Einfuegeanweisung sie noch nicht nennt.
             */
            'ALTER TABLE users
               ADD COLUMN kdf_iter INT UNSIGNED NOT NULL DEFAULT 310000 AFTER kdf_salt',
            'UPDATE users SET kdf_iter = 310000 WHERE kdf_iter = 0',

            /* --- S2: Pruefsumme des Inhaltsschluessels ---------------------
             * Bleibt fuer Bestandskonten LEER. Eine fehlende Pruefsumme ist
             * ein gueltiger Zustand: Konten ohne sie werden weiter
             * angenommen und bekommen sie beim naechsten Setzen des
             * Passworts. Alles andere wuerde bestehende Konten aussperren,
             * weil der Server die Pruefsumme nicht selbst berechnen kann —
             * er kennt den Inhaltsschluessel nicht.
             */
            'ALTER TABLE users
               ADD COLUMN pat_key_check CHAR(32) NULL AFTER pat_wrap_rc',

            /* --- S3: Sitzungszaehler ---------------------------------------
             * Wird beim Passwortwechsel erhoeht und bei jeder Anfrage gegen
             * die Sitzung geprueft. Damit endet eine offene Sitzung, wenn das
             * Passwort gewechselt wird — heute laeuft sie weiter, was genau
             * den Zweck des Wechsels verfehlt, wenn er wegen eines Verdachts
             * erfolgt.
             */
            'ALTER TABLE users
               ADD COLUMN session_epoch INT UNSIGNED NOT NULL DEFAULT 0 AFTER role',

            /* --- S4: Ratenschutz -------------------------------------------
             * Zaehlung je Kontokennung UND je IP-Adresse, mit Zeitfenster.
             * Der Aufraeumjob entsorgt die Tabelle mit.
             * Einzige Schemaaenderung dieser Auslieferung, die bereits
             * benutzt wird — der Ratenschutz ist ab der naechsten
             * Auslieferung Pflicht (Kopplung).
             */
            "CREATE TABLE rate_limits (
               id            INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
               topf          VARCHAR(32)  NOT NULL,
               merkmal       VARCHAR(190) NOT NULL,
               versuche      INT UNSIGNED NOT NULL DEFAULT 0,
               fenster_start DATETIME     NOT NULL,
               gesperrt_bis  DATETIME     NULL,
               UNIQUE KEY uq_topf_merkmal (topf, merkmal),
               INDEX idx_fenster (fenster_start)
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4",

            /* --- S5: Sperrliste auch fuer Ruhe-Segmente ---------------------
             * Die Sperrliste war an beiden Enden nur fuer Einsaetze
             * umgesetzt: Sie wurde nur fuer Einsaetze befuellt und nur im
             * Einsatz-Zweig abgefragt. Ein endgueltig geloeschtes
             * Ruhe-Segment wird deshalb von der naechsten Nachlieferung
             * wieder angelegt — und beim erneuten Loeschen wieder.
             *
             * Der Bestand besteht ausschliesslich aus Einsaetzen, deshalb ist
             * der Vorgabewert 'mission' fuer vorhandene Zeilen richtig.
             * Reihenfolge: erst den neuen Schluessel anlegen, dann den alten
             * entfernen.
             */
            "ALTER TABLE deleted_refs
               ADD COLUMN owner_type ENUM('mission','rest') NOT NULL DEFAULT 'mission' AFTER device_id",
            'ALTER TABLE deleted_refs
               ADD UNIQUE KEY uq_dev_type_ref (device_id, owner_type, client_ref)',
            'ALTER TABLE deleted_refs DROP INDEX uq_dev_ref',

            /* --- S6: Sortierregel der E-Mail-Spalte ausdruecklich festlegen -
             * Dass die Anmeldung heute trotz uneinheitlicher Normalisierung
             * der Adresse funktioniert, liegt ALLEIN an der
             * Standardsortierregel der Datenbank. Auf einer Installation mit
             * unterscheidender Sortierregel schluege sie fuer jede Adresse
             * fehl, die nicht exakt wie beim Anlegen eingetippt wird — mit
             * der Meldung "Anmeldung fehlgeschlagen", ohne Hinweis auf die
             * Ursache. Das Projekt liegt offen; diese Annahme darf nicht
             * ungeschrieben bleiben.
             *
             * utf8mb4_unicode_ci ist wie die bisherigen Standardregeln
             * unterscheidungsfrei bei Gross-/Kleinschreibung — an bestehenden
             * Installationen aendert sich nichts.
             */
            'ALTER TABLE users
               MODIFY email VARCHAR(190) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL',
        ],
    ],
    [
        'id'    => '2026_08_13_zeitzonen_umstellung',
        'label' => 'Zeitzonen-Umstellung: Ratenschutz-Zähler aus der Zeit davor entfernen',
        'zerstoert' => 'Ratenschutz-Zähler mit einem Fenster in der Zukunft werden '
                     . 'gelöscht (laufende Anmeldesperren enden dadurch sofort).',
        // OHNE Inhaltspruefung: Das Loeschen IST der Zweck, und die Zeilen sind
        // kurzlebige Zaehler, keine Daten. Eine Pruefung wuerde die Migration
        // ausgerechnet dort blockieren, wo Zeilen aus der alten Zeitrechnung
        // liegen — also genau im Anwendungsfall.
        /* WARUM ES DIESE MIGRATION GIBT
         *
         * Web 4.5.2 hat die Zeitzone der Datenbankverbindung ausdruecklich auf
         * UTC gesetzt (M5-09). Vorher kam sie aus der Einstellung des
         * Datenbankservers — bei einer Ortszeit schrieb NOW() also um den
         * Zonenversatz vor der Weltzeit.
         *
         * BETROFFEN IST NUR EIN SPALTENTYP, UND DAS IST DER SPRINGENDE PUNKT.
         * MySQL behandelt die beiden Zeittypen grundverschieden:
         *
         *   TIMESTAMP  wird beim Schreiben in UTC umgerechnet und beim Lesen
         *              zurueck. Der gespeicherte Wert war IMMER richtig, egal
         *              welche Zone die Sitzung hatte. Betroffen war nur die
         *              ANZEIGE (fmt_local rechnete ein zweites Mal um) — und
         *              die stimmt seit 4.5.2 von selbst.
         *              Das sind: pair_codes, devices.last_seen/created_at,
         *              users.created_at, missions.created_at, deleted_refs.
         *
         *   DATETIME   speichert, was dasteht, ohne jede Umrechnung. Wurde es
         *              mit NOW() aus einer Ortszeit-Sitzung gefuellt, steht
         *              dort Ortszeit — und die wird jetzt gegen UTC
         *              verglichen.
         *              Mit NOW() gefuellt werden: rate_limits.fenster_start
         *              und .gesperrt_bis sowie password_resets.expires_at.
         *              Die Einsatzzeiten kommen aus local_to_utc() und die
         *              Papierkorb-Zeiten aus UTC_TIMESTAMP() — beide waren
         *              schon immer UTC und sind unberuehrt.
         *
         * Bleiben also zwei Stellen, und nur eine davon wird angefasst:
         *
         *   rate_limits          Eine Anmeldesperre haelt laenger als die
         *                        vorgesehenen 15 Minuten. Genau das wurde im
         *                        Betrieb beobachtet: Die Meldung nannte
         *                        2 Stunden 15 Minuten. -> wird geraeumt.
         *
         *   password_resets      Ein offener Link lebt ein bis zwei Stunden
         *                        laenger als vorgesehen. BEWUSST NICHT
         *                        angefasst: Dort koennte jemand auf einen
         *                        Einladungslink warten, und ein Link, der
         *                        unter den Haenden ungueltig wird, waere der
         *                        groessere Schaden als einer, der etwas zu
         *                        lange lebt.
         *
         * WARUM "fenster_start > NOW()" GENAU DAS RICHTIGE TRIFFT
         * Ein Beobachtungszeitraum kann nicht in der Zukunft beginnen. Wo das
         * doch so aussieht, stammt die Zeile aus der Zeit vor der Umstellung —
         * und nur dann. Eine laufende, korrekt geschriebene Sperre gegen einen
         * tatsaechlichen Angriff bleibt damit bestehen.
         *
         * DAS HEILT SICH AUCH VON SELBST, sobald der Zonenversatz verstrichen
         * ist. Diese Migration nimmt nur vorweg, was sonst Stunden dauert —
         * wer sie spaeter aufspielt, findet nichts mehr vor. Das ist kein
         * Fehler, sondern der Normalfall.
         */
        'sql'   => [
            'DELETE FROM rate_limits WHERE fenster_start > NOW()',
        ],
    ],
    [
        'id'    => '2026_08_14_geraetename_ohne_datum',
        'label' => 'Gerätenamen: automatisch vergebenes Kopplungsdatum aus dem Namen entfernen',
        /* WARUM
         *
         * pair.php vergab beim Koppeln den Namen "Uhr (gekoppelt 11.08.2026)".
         * Dieselbe Angabe steht in devices.created_at, und beide werden
         * angezeigt — im Hinweis auf der Startseite stand das Datum deshalb
         * zweimal hintereinander. Seit Web 5.0.1 heisst ein neu gekoppeltes
         * Geraet nur noch "Uhr"; der Altbestand traegt das Datum aber weiter.
         *
         * WARUM DAS HIER GEFAHRLOS IST
         * Geaendert wird NUR, was exakt dem automatisch vergebenen Muster
         * entspricht. Ein selbst vergebener Name — "Uhr Philipp", "Christoph
         * 17", auch "Uhr (gekoppelt, alt)" — passt nicht auf das Muster und
         * bleibt unberuehrt. Es geht keine Angabe verloren: Das Datum steht in
         * created_at und wird in der Geraeteliste als "seit …" angezeigt.
         *
         * Deshalb KEINE Kennzeichnung als destruktiv: Hier wird nichts
         * vernichtet, sondern eine doppelt gefuehrte Angabe auf ihre eine
         * Quelle zurueckgefuehrt. Die rote Kennzeichnung ist den Faellen
         * vorbehalten, in denen wirklich etwas verlorengeht — sonst gewoehnt
         * man sich an sie.
         */
        /* Als Funktion statt als SQL: Das Muster steht damit EINMAL da, als
         * gewoehnlicher regulaerer Ausdruck. Derselbe Ausdruck in einer
         * SQL-Zeichenkette braeuchte Klammern und Punkte doppelt maskiert —
         * einmal fuer PHP, einmal fuer die Datenbank —, und was am Ende
         * wirklich bei MariaDB ankommt, sieht man dem Quelltext nicht mehr an.
         * Genau daran ist der erste Entwurf gescheitert. */
        'skip'  => function (PDO $pdo): bool {
            return count(_geraete_mit_datumsname($pdo)) === 0;
        },
        'run'   => function (PDO $pdo): void {
            $up = $pdo->prepare('UPDATE devices SET label = ? WHERE id = ?');
            foreach (_geraete_mit_datumsname($pdo) as $id) {
                $up->execute(['Uhr', $id]);
            }
        },
    ],
    // Naechste Migration hier anhaengen.
];

/* ---- Zweistufiger Ablauf ---------------------------------------------------
 *
 * FRUEHER WAR DER AUFRUF DIESER SEITE BEREITS DIE AUSFUEHRUNG. Wer sie
 * versehentlich oeffnete, oder aus dem Verlauf, oder weil ein Vorschau-Abruf
 * des Browsers ihr folgte, hatte die Migrationen laufen lassen — darunter
 * solche, die Spalten LOESCHEN. Eine unwiderrufliche Handlung auf einen GET
 * hin ist immer falsch; hier war sie es besonders, weil die Seite kein
 * Formular-Token brauchte und der Rat, vorher eine Sicherung zu erstellen,
 * erst DANACH zu lesen war.
 *
 * Jetzt gilt:
 *   Aufruf (GET)   zeigt an, WAS anstuende. Aendert nichts.
 *   Knopf (POST)   fuehrt aus, mit Formular-Token.
 *
 * Der Notausgang ueber die Kommandozeile bleibt einstufig: Wer "php
 * update.php" eintippt, hat die bewusste Handlung bereits vollzogen.
 */
$pdo = db();
$pdo->exec('CREATE TABLE IF NOT EXISTS schema_migrations (
              id         VARCHAR(120) NOT NULL PRIMARY KEY,
              applied_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              status     VARCHAR(16) NOT NULL DEFAULT "applied"
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

$istCli = php_sapi_name() === 'cli';
$ausfuehren = $istCli
    || ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'run');
if ($ausfuehren && !$istCli) { csrf_check(); }

/* ---- Inhaltspruefung vor destruktiven Migrationen (M6-01) ------------------
 *
 * Liefert je Spalte aus 'inhalt', wie viele Zeilen dort etwas stehen haben.
 * Eine leere Rueckgabe heisst: Es ist nichts zu verlieren.
 *
 * Gezaehlt wird NICHT NULL und nicht leer — eine Spalte voller NULL-Werte ist
 * dasselbe wie eine leere Spalte, und ein leerer Text ist keine Angabe.
 *
 * Fehlt die Spalte bereits, wird sie uebergangen: Dann hat die Migration ihre
 * Arbeit getan, und die Frage stellt sich nicht mehr. Das ist auch der Grund,
 * warum 'skip' danebenstehen bleibt — ohne die Strukturpruefung waere eine
 * bereits geloeschte Spalte von einer leeren nicht zu unterscheiden.
 */
function inhalt_zaehlen(PDO $pdo, array $spalten): array
{
    $gefunden = [];
    foreach ($spalten as [$tabelle, $spalte, $was]) {
        $q = $pdo->prepare('SELECT COUNT(*) FROM information_schema.columns
                            WHERE table_schema = DATABASE()
                              AND table_name = ? AND column_name = ?');
        $q->execute([$tabelle, $spalte]);
        if ((int)$q->fetchColumn() === 0) { continue; }   // Spalte gibt es nicht mehr

        /* Tabellen- und Spaltenname stehen fest im Code dieser Datei und
         * kommen von nirgendwo sonst her; sie lassen sich in einer
         * vorbereiteten Anweisung nicht als Parameter uebergeben. Die
         * Ruecksicherung ist die Abfrage oben: Was nicht in
         * information_schema steht, kommt hier nicht an. */
        $c = $pdo->query("SELECT COUNT(*) FROM `$tabelle`
                          WHERE `$spalte` IS NOT NULL AND `$spalte` <> ''")->fetchColumn();
        if ((int)$c > 0) { $gefunden[] = [$tabelle . '.' . $spalte, (int)$c, $was]; }
    }
    return $gefunden;
}

/** Kurztext fuer die Anzeige einer Inhaltspruefung. */
function inhalt_text(array $gefunden): string
{
    $teile = [];
    foreach ($gefunden as [$spalte, $zahl, $was]) {
        $teile[] = $spalte . ': ' . $zahl . ' Zeile' . ($zahl === 1 ? '' : 'n')
                 . ' (' . $was . ')';
    }
    return implode(', ', $teile);
}

/* Welche destruktiven Migrationen sollen trotz Inhalt laufen?
 *
 * Der gewoehnliche Knopf fuehrt sie NICHT aus. Wer die Daten anderswo
 * gesichert hat, hakt die betroffene Migration einzeln an — das ist die
 * zweite Stufe aus D10, und sie ist bewusst kein globales "trotzdem":
 * Angehakt wird genau die eine Migration, deren Meldung man gerade gelesen
 * hat.
 *
 * Auf der Kommandozeile gibt es die Stufe nicht. Dort blockiert eine
 * Migration mit Inhalt immer und nennt den Weg ueber die Wartungsseite —
 * ein Argument "--force" waere zu leicht aus einer Anleitung abgeschrieben.
 */
$forcieren = [];
if (!$istCli && $ausfuehren && isset($_POST['forcieren']) && is_array($_POST['forcieren'])) {
    foreach ($_POST['forcieren'] as $fid) { $forcieren[(string)$fid] = true; }
}

$applied = $pdo->query('SELECT id FROM schema_migrations')->fetchAll(PDO::FETCH_COLUMN);
$results = [];   // [id, label, status, detail, zerstoert, blockiertId]
$ranSomething = false;
$offen = 0;      // Zahl der Migrationen, die tatsaechlich etwas taeten
$blockiert = 0;  // destruktive Migrationen, die wegen Inhalt nicht laufen (M6-01)

if (!$ausfuehren) {
    /* ---- VORSCHAU: nur lesen, nichts schreiben ------------------------
     * Auch die skip-Pruefungen laufen hier — sie fragen ausschliesslich
     * information_schema ab und aendern nichts. Ihr Ergebnis wird aber NICHT
     * verbucht: Selbst dieser harmlose Eintrag waere eine Schreiboperation
     * auf einen Seitenaufruf hin. */
    foreach ($MIGRATIONS as $m) {
        if (in_array($m['id'], $applied, true)) {
            $results[] = [$m['id'], $m['label'], 'ok', 'Bereits angewendet.'];
            continue;
        }
        $nichtNoetig = false;
        try {
            $nichtNoetig = isset($m['skip']) && ($m['skip'])($pdo);
        } catch (Throwable $ex) {
            $results[] = [$m['id'], $m['label'], 'warn',
                          'Zustand nicht feststellbar: ' . $ex->getMessage(),
                          $m['zerstoert'] ?? null, null];
            $offen++;
            continue;
        }
        if ($nichtNoetig) {
            $results[] = [$m['id'], $m['label'], 'ok',
                          'Nicht nötig (Schema bereits aktuell) — wird beim Ausführen als erledigt vermerkt.',
                          null, null];
            $offen++;
            continue;
        }
        // Destruktive Migration mit Inhalt: in der Vorschau BENENNEN, was
        // verlorenginge — genau dafuer ist die Vorschau da (M6-01).
        $gefunden = isset($m['inhalt']) ? inhalt_zaehlen($pdo, $m['inhalt']) : [];
        if ($gefunden) {
            $results[] = [$m['id'], $m['label'], 'stopp',
                          'WIRD NICHT AUSGEFÜHRT — dort stehen noch Daten: '
                          . inhalt_text($gefunden) . '.',
                          $m['zerstoert'] ?? null, $m['id']];
            $blockiert++;
            continue;
        }
        $results[] = [$m['id'], $m['label'], 'todo',
                      'STEHT AN — wird beim Ausführen angewendet.',
                      $m['zerstoert'] ?? null, null];
        $offen++;
    }
} else {
    /* ---- AUSFUEHRUNG ---------------------------------------------------- */
    foreach ($MIGRATIONS as $m) {
        if (in_array($m['id'], $applied, true)) {
            $results[] = [$m['id'], $m['label'], 'ok', 'Bereits angewendet.', null, null];
            continue;
        }

        if (isset($m['skip']) && ($m['skip'])($pdo)) {
            $pdo->prepare('INSERT INTO schema_migrations (id, status) VALUES (?, "skipped")')
                ->execute([$m['id']]);
            $results[] = [$m['id'], $m['label'], 'ok',
                          'Nicht nötig (Schema bereits aktuell) — als erledigt vermerkt.', null, null];
            continue;
        }

        /* ---- Destruktive Migration mit Inhalt: NICHT ausfuehren (M6-01) ----
         *
         * Und zwar OHNE die Schleife zu verlassen. Das ist der Unterschied zu
         * einem FEHLER weiter unten: Eine blockierte Migration hat NICHTS
         * getan — die Datenbank steht exakt so da wie zuvor, als gaebe es sie
         * nicht. Ein Fehler dagegen kann auf halbem Weg stehengeblieben sein,
         * und dann ist jede nachfolgende Migration eine Wette.
         *
         * Der Unterschied ist wichtig: Wuerde eine blockierte Migration die
         * Kette anhalten, kaeme auf einer Installation mit Altbestand in
         * site_desc keine spaetere Migration mehr durch — darunter die
         * Sicherheitsbausteine aus 2026_08_08. Ein Datenschutz, der die
         * Sicherheitsupdates blockiert, waere ein schlechter Tausch.
         */
        $gefunden = isset($m['inhalt']) ? inhalt_zaehlen($pdo, $m['inhalt']) : [];
        if ($gefunden && !isset($forcieren[$m['id']])) {
            $results[] = [$m['id'], $m['label'], 'stopp',
                          'NICHT AUSGEFÜHRT — dort stehen noch Daten: '
                          . inhalt_text($gefunden) . '. Es wurde nichts geändert.',
                          $m['zerstoert'] ?? null, $m['id']];
            $blockiert++;
            continue;
        }
        $freigegeben = $gefunden && isset($forcieren[$m['id']]);

        try {
            if (isset($m['run'])) { ($m['run'])($pdo); }
            /* Teilschritte zaehlen (M6-08).
             *
             * Vorher meldete jede Migration "Erfolgreich angewendet." — auch
             * eine, bei der ALLE Teilschritte uebersprungen wurden, weil sie
             * laengst erledigt waren. Wer nach einem abgebrochenen Lauf
             * nachsehen wollte, was tatsaechlich passiert ist, bekam dieselbe
             * Zeile wie bei einer frisch durchgelaufenen Migration.
             *
             * Das ist genau die Auskunft, die man an dieser Stelle braucht:
             * Migrationen koennen Spalten loeschen, und "es war schon so" ist
             * eine andere Aussage als "ich habe es gerade getan". */
            $gemacht = 0; $uebersprungen = 0;
            foreach (($m['sql'] ?? []) as $stmt) {
                try {
                    $pdo->exec($stmt);
                    $gemacht++;
                } catch (PDOException $inner) {
                    // Nach einem Teil-Lauf koennen einzelne Schritte schon
                    // erledigt sein: 1060 Spalte existiert, 1061 Index existiert,
                    // 1091 zu loeschendes Objekt fehlt, 1050 Tabelle existiert.
                    // Diese Faelle sind harmlos -> weitermachen, aber zaehlen.
                    $code = (int)($inner->errorInfo[1] ?? 0);
                    if (!in_array($code, [1050, 1060, 1061, 1091], true)) { throw $inner; }
                    $uebersprungen++;
                }
            }
            $pdo->prepare('INSERT INTO schema_migrations (id, status) VALUES (?, "applied")')
                ->execute([$m['id']]);
            $gesamt = $gemacht + $uebersprungen;
            if ($uebersprungen === 0) {
                $detail = 'Erfolgreich angewendet.';
            } elseif ($gemacht === 0) {
                $detail = 'Als erledigt vermerkt — alle ' . $gesamt . ' Teilschritte waren '
                        . 'bereits vorhanden, es wurde nichts geändert.';
            } else {
                $detail = 'Angewendet: ' . $gemacht . ' von ' . $gesamt . ' Teilschritten '
                        . 'ausgeführt, ' . $uebersprungen . ' waren bereits erledigt.';
            }
            if ($freigegeben) {
                // Ausdruecklich benennen, was gerade passiert ist — diese Zeile
                // ist spaeter der einzige Beleg dafuer, dass jemand die
                // Freigabe bewusst gesetzt hat.
                $detail = 'AUF AUSDRÜCKLICHE FREIGABE ausgeführt, obwohl Daten '
                        . 'betroffen waren (' . inhalt_text($gefunden) . '). ' . $detail;
            }
            $results[] = [$m['id'], $m['label'], 'ok', $detail, $m['zerstoert'] ?? null, null];
            $ranSomething = true;
        } catch (Throwable $ex) {
            // Nicht verbuchen -> naechster Aufruf versucht es erneut
            $results[] = [$m['id'], $m['label'], 'fail',
                          'Fehler: ' . $ex->getMessage() . ' — Migration wurde NICHT als erledigt vermerkt.',
                          $m['zerstoert'] ?? null, null];
            break;   // Reihenfolge wahren: nachfolgende Migrationen nicht ausfuehren
        }
    }
}

// Kommandozeile: Ergebnis als Text ausgeben und beenden — ohne HTML-Geruest.
if ($istCli) {
    foreach ($results as [$id, $label, $status, $detail, $zerstoert, $blockId]) {
        printf("%-6s %-46s %s\n", strtoupper($status), $id, $detail);
        // Auf der Kommandozeile gibt es die Freigabe nicht (siehe oben) —
        // dafuer den Weg dorthin.
        if ($status === 'stopp') {
            printf("%-6s %-46s %s\n", '', '',
                   '-> Daten sichern, dann auf der Wartungsseite einzeln freigeben.');
        }
    }
    exit($ranSomething ? 0 : 0);
}

?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Datenbank-Update — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<?php /* Seitenleiste wie auf den uebrigen Verwaltungsseiten.
   Bis Web 4.5.2 stand diese Seite ohne sie da — sie war ja nur ueber die
   direkte Adresse erreichbar und damit ohnehin eine Sackgasse: Wer hier
   landete, kam nur ueber den Zurueck-Knopf wieder heraus. */ ?>
<div class="layout">
  <?php ui_settings_sidebar('wartung'); ?>

<main class="page">
  <h1>Wartung &amp; Datenbank-Update</h1>

  <?php if (!$results): ?>
    <p class="alert alert-info">Keine Migrationen definiert.</p>

  <?php elseif (!$ausfuehren): ?>
    <?php /* ---- Vorschau: es wurde noch NICHTS geändert ---- */ ?>
    <?php if ($offen === 0): ?>
      <p class="alert alert-info">Die Datenbank ist auf dem aktuellen Stand.
         Es steht nichts an.</p>
    <?php else: ?>
      <p class="alert alert-warn"><strong>Es wurde noch nichts geändert.</strong>
         <?= (int)$offen ?> Eintrag/Einträge stehen aus. Unten steht, was
         passieren würde.</p>
      <p class="alert alert-warn"><strong>Vorher eine Sicherung erstellen.</strong>
         Migrationen können Spalten und Daten unwiderruflich entfernen. Die
         Sicherung liegt unter
         <a href="einstellungen.php?t=backup">Einstellungen → Backup</a> und
         dauert eine Minute — eine verlorene Spalte dagegen ist verloren.</p>
    <?php endif; ?>
    <?php if ($blockiert > 0): ?>
      <p class="alert"><strong><?= (int)$blockiert ?> Migration(en) werden
         NICHT ausgeführt</strong>, weil sie Spalten löschen würden, in denen
         noch Daten stehen. Unten ist je Eintrag genannt, um welche Spalte und
         wie viele Zeilen es geht.<br>
         Diese Daten lassen sich <strong>nicht automatisch</strong> in den
         verschlüsselten Block überführen — er entsteht ausschließlich im
         Browser. Wer sie behalten will, trägt sie vorher von Hand in den
         jeweiligen Einsatz ein (oder sichert sie außerhalb) und gibt die
         Migration danach einzeln frei.</p>
    <?php endif; ?>
  <?php elseif ($ranSomething): ?>
    <p class="alert alert-info">Updates wurden angewendet — Details unten.</p>
  <?php else: ?>
    <p class="alert alert-info">Es war nichts anzuwenden.</p>
  <?php endif; ?>

  <?php if ($results): ?>
    <table class="data">
      <thead><tr><th>Update</th><th>Status</th><th>Details</th></tr></thead>
      <tbody>
      <?php foreach ($results as [$id, $label, $status, $detail, $zerstoert, $blockId]): ?>
        <tr<?= $status === 'stopp' ? ' class="warnzeile"' : '' ?>>
          <td><?= e($label) ?><br><span class="muted"><code><?= e($id) ?></code></span></td>
          <td><?= match ($status) {
                    'ok'    => '✔',
                    'todo'  => '●',
                    'warn'  => '!',
                    'stopp' => '⚠',
                    default => '✖',
                  } ?></td>
          <td>
            <?= e($detail) ?>
            <?php /* Destruktive Migration: benennen, WAS verlorenginge — und
                     zwar an der Zeile, nicht in einem allgemeinen Hinweis
                     ueber der Tabelle (M6-01). */ ?>
            <?php if ($zerstoert !== null): ?>
              <br><strong class="loeschhinweis">Löscht Daten:</strong>
              <span class="muted"><?= e($zerstoert) ?></span>
            <?php endif; ?>
            <?php if ($blockId !== null && !$ausfuehren): ?>
              <br><label class="check">
                <input type="checkbox" name="forcieren[]" form="migform"
                       value="<?= e($blockId) ?>">
                Daten sind gesichert — diese eine Migration trotzdem ausführen
              </label>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <?php if (!$ausfuehren && ($offen > 0 || $blockiert > 0)): ?>
      <form method="post" action="update.php" id="migform" style="margin-top:1rem">
        <?= csrf_field() ?><input type="hidden" name="action" value="run">
        <button type="submit" class="btn-primary" style="width:auto">
          Updates jetzt anwenden</button>
      </form>
      <p class="muted">Der Aufruf dieser Seite ändert nichts. Erst dieser Knopf
         führt die Updates aus.<?php if ($blockiert > 0): ?> Die mit ⚠
         gekennzeichneten Einträge bleiben dabei unangetastet, solange ihr
         Häkchen nicht gesetzt ist.<?php endif; ?></p>
    <?php elseif ($ausfuehren): ?>
      <p class="muted">Bereits erledigte Updates werden übersprungen —
         ein erneuter Lauf ist ungefährlich.</p>
    <?php endif; ?>
  <?php endif; ?>

  <?php /* ---- Umgebung ------------------------------------------------------
     * Ob der Mailversand aus der messbaren Antwortzeit herausgehalten werden
     * kann, haengt an der PHP-Anbindung des Webspace und laesst sich sonst
     * nirgends ablesen. Bei „Passwort vergessen“ ist genau das die
     * Eigenschaft, an der die Gleichheit beider Antwortzweige haengt — sie
     * gehoert deshalb sichtbar gemacht und nicht nur in die Doku. */ ?>
  <?php /* ---- Rundenzahl der Schluesselableitung (M2-01) -------------------
     *
     * Der Browser leitet nur fuer die Werte in KDF_ITER_LISTE ab. Traegt ein
     * Konto eine Zahl, die dort NICHT steht, entsteht bei ihm nie das richtige
     * Token — es kann sich nicht mehr anmelden, und die Meldung lautet
     * schlicht "Anmeldung fehlgeschlagen".
     *
     * Das passiert, wenn jemand KDF_ITER_ZIEL anhebt und vergisst, den
     * bisherigen Wert in der Liste stehen zu lassen. Der Fehler ist an der
     * Anmeldemaske nicht zu erkennen und trifft alle Bestandskonten
     * gleichzeitig — deshalb steht die Pruefung hier, wo jemand nach einem
     * Update ohnehin vorbeikommt. */
  $kdfListe = KDF_ITER_LISTE;
  $platz    = implode(',', array_fill(0, count($kdfListe), '?'));
  $stk = $pdo->prepare("SELECT kdf_iter, COUNT(*) AS n FROM users
                        WHERE password_hash IS NOT NULL
                          AND kdf_iter NOT IN ($platz)
                        GROUP BY kdf_iter ORDER BY kdf_iter");
  $stk->execute($kdfListe);
  $kdfVerwaist = $stk->fetchAll();
  ?>
  <?php /* NUR IM PROBLEMFALL ANZEIGEN.
     *
     * Zuerst stand hier auch eine Entwarnung ("Alle Konten rechnen mit einer
     * Rundenzahl, die diese Fassung anbietet"). Sie ist wieder entfallen: Eine
     * Wartungsseite, die Nicht-Probleme aufzaehlt, macht die echten Meldungen
     * schwerer zu finden — und wer sie liest, ueberfliegt beim naechsten Mal
     * auch die Zeile, die zaehlt.
     *
     * Die Pruefung selbst bleibt. Sie kostet eine Abfrage und faengt den
     * Fehler ab, den jemand macht, der KDF_ITER_ZIEL anhebt und vergisst, den
     * bisherigen Wert in KDF_ITER_LISTE stehen zu lassen: Dann kann sich kein
     * Bestandskonto mehr anmelden, und an der Anmeldemaske ist die Ursache
     * nicht zu erkennen. */ ?>
  <?php if ($kdfVerwaist): ?>
    <h2>Schlüsselableitung</h2>
    <p class="alert"><strong>Achtung: <?php
        $summe = array_sum(array_column($kdfVerwaist, 'n'));
        echo (int)$summe; ?> Konto/Konten können sich nicht anmelden.</strong>
       Sie tragen eine Rundenzahl, die diese Fassung nicht mehr anbietet:
       <?php $t = [];
             foreach ($kdfVerwaist as $z) { $t[] = $z['kdf_iter'] . ' (' . $z['n'] . '×)'; }
             echo e(implode(', ', $t)); ?>.
       Angeboten wird nur <?= e(implode(', ', array_map('strval', $kdfListe))) ?>.<br>
       <strong>Behebung:</strong> Den fehlenden Wert in <code>KDF_ITER_LISTE</code>
       (<code>server/db.php</code>) wieder aufnehmen. Danach melden sich die
       Konten wie gewohnt an und werden beim nächsten Mal still angehoben.</p>
  <?php endif; ?>

  <h2>Umgebung</h2>
  <?php require_once __DIR__ . '/smtp.php'; ?>
  <?php if (antwort_entkoppelbar()): ?>
    <p class="muted">Mailversand: <strong>entkoppelt</strong> — die Antwort wird
       abgeschlossen, bevor der Versand beginnt. Die Anforderung „Passwort
       vergessen“ dauert damit für vorhandene und unbekannte Adressen gleich lang.</p>
  <?php else: ?>
    <p class="alert alert-warn">Mailversand: <strong>nicht sicher entkoppelbar</strong>
       — diese PHP-Anbindung kennt weder <code>fastcgi_finish_request</code> noch
       <code>litespeed_finish_request</code>. Die Antwort wird zwar mit
       Längenangabe abgeschlossen, was üblicherweise reicht; verbindlich ist es
       nicht. Im ungünstigen Fall bleibt die Dauer der Anforderung „Passwort
       vergessen“ ein Hinweis darauf, ob es zu einer Adresse ein Konto gibt.</p>
  <?php endif; ?>

  <?php /* ---- Wartung (M3-05) ------------------------------------------------
     * Der Aufraeumjob laeuft huckepack auf Anfragen und ist gegenueber der
     * Anfrage still. Genau deshalb ist ein dauerhaft scheiternder Job von
     * einem laufenden sonst nicht zu unterscheiden — bis irgendwann auffaellt,
     * dass der Papierkorb seit Monaten nicht mehr geleert wird.
     *
     * Zwei Marken: der letzte VERSUCH und der letzte VOLLSTAENDIGE Lauf.
     * Klaffen sie auseinander, scheitert mindestens ein Schritt. Die Ursache
     * steht im Fehlerprotokoll des Webspace. */
  $wartung = [];
  foreach (['last_cleanup', 'last_cleanup_ok'] as $k) {
      $stw = $pdo->prepare('SELECT v FROM app_state WHERE k = ?');
      $stw->execute([$k]);
      $wartung[$k] = $stw->fetchColumn();
  }
  $wVersuch = $wartung['last_cleanup']    ?: null;
  $wErfolg  = $wartung['last_cleanup_ok'] ?: null;
  ?>
  <h2>Wartung</h2>
  <?php if ($wVersuch === null): ?>
    <p class="muted">Der Aufräumjob ist noch nie gelaufen. Das ist auf einer
       frischen Installation normal — er startet bei der ersten Anfrage des
       nächsten Tages.</p>
  <?php elseif ($wErfolg === null): ?>
    <p class="alert alert-warn">Letzter Versuch: <strong><?= e((string)$wVersuch) ?></strong> —
       aber <strong>noch kein einziger vollständiger Lauf</strong>. Mindestens ein
       Schritt scheitert dauerhaft; die Ursache steht im Fehlerprotokoll des
       Webspace (Suchwort <code>cleanup:</code>). Solange das so bleibt, wird
       unter anderem der Papierkorb nicht geleert.</p>
  <?php elseif ($wErfolg !== $wVersuch): ?>
    <p class="alert alert-warn">Letzter Versuch: <strong><?= e((string)$wVersuch) ?></strong>,
       letzter <strong>vollständiger</strong> Lauf: <strong><?= e((string)$wErfolg) ?></strong>.
       Es scheitert mindestens ein Schritt — Ursache im Fehlerprotokoll des
       Webspace (Suchwort <code>cleanup:</code>).</p>
  <?php else: ?>
    <p class="muted">Aufräumjob zuletzt vollständig durchgelaufen:
       <strong><?= e((string)$wErfolg) ?></strong>.</p>
  <?php endif; ?>
<?php ui_footer(); ?>
</main>
</div>
</body>
</html>
