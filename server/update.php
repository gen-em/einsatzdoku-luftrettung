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

/* ---- Schema-Auskuenfte fuer die Notarzt-Migration -------------------------
 *
 * Die Migration 2026_08_17_notarzt_erweiterung baut in einem Zug um und muss
 * dabei mehrfach fragen, ob ein Schritt schon geschehen ist — sie laeuft auf
 * Installationen, die an unterschiedlichen Punkten stehen koennen. Die vier
 * Auskuenfte stehen deshalb hier und nicht als wiederholtes SQL im Ablauf.
 */
function _hat_tabelle(PDO $pdo, string $tabelle): bool
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.tables
                        WHERE table_schema = DATABASE() AND table_name = ?");
    $q->execute([$tabelle]);
    return (int)$q->fetchColumn() > 0;
}

function _hat_spalte(PDO $pdo, string $tabelle, string $spalte): bool
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.columns
                        WHERE table_schema = DATABASE()
                          AND table_name = ? AND column_name = ?");
    $q->execute([$tabelle, $spalte]);
    return (int)$q->fetchColumn() > 0;
}

function _hat_index(PDO $pdo, string $tabelle, string $index): bool
{
    $q = $pdo->prepare("SELECT COUNT(*) FROM information_schema.statistics
                        WHERE table_schema = DATABASE()
                          AND table_name = ? AND index_name = ?");
    $q->execute([$tabelle, $index]);
    return (int)$q->fetchColumn() > 0;
}

/**
 * Name des Fremdschluessels auf einer Spalte, oder null.
 *
 * Die Namen sind nicht vergeben worden, sondern von MySQL erzeugt
 * (`days_ibfk_2` und aehnlich). Sie lassen sich deshalb nicht fest
 * hinschreiben — eine Installation, die eine Tabelle einmal neu aufgebaut
 * hat, traegt andere. Vor dem Umbenennen einer Spalte mit Fremdschluessel
 * muss dieser fallen und danach neu gesetzt werden.
 */
function _fk_name(PDO $pdo, string $tabelle, string $spalte): ?string
{
    $q = $pdo->prepare("SELECT constraint_name FROM information_schema.key_column_usage
                        WHERE table_schema = DATABASE() AND table_name = ?
                          AND column_name = ? AND referenced_table_name IS NOT NULL
                        LIMIT 1");
    $q->execute([$tabelle, $spalte]);
    $n = $q->fetchColumn();
    return $n === false ? null : (string)$n;
}

/* ---- Migrationsliste ------------------------------------------------------
 * 'id'    : eindeutiger, aufsteigender Name (Datum_stichwort)
 * 'label' : Beschreibung fuer die Anzeige
 * 'skip'  : optionale Pruefung; liefert true, wenn die Aenderung in dieser
 *           Datenbank nicht noetig ist (z. B. frisch mit aktuellem Schema
 *           installiert) -> wird als "uebersprungen" verbucht
 * 'sql'   : Liste der auszufuehrenden Statements
 * 'run'   : alternativ eine Funktion statt einer Anweisungsliste
 * 'web'   : Web-Fassung, mit der diese Migration ausgeliefert wurde (Web 7.0.0)
 *
 *           REINE AUSKUNFT — sie steuert nichts. Die Wartungsseite zeigt sie
 *           als eigene Spalte, und sie beantwortet die Frage, die man vor
 *           jedem Update hat: „Von welchem Stand komme ich, und was kommt
 *           dazu?" Die Kennung allein sagte das nicht; sie traegt ein Datum,
 *           und Datum und Fassung fallen auseinander, sobald an einem Tag
 *           zwei Fassungen erscheinen (an dreien ist das geschehen).
 *
 *           Die Zuordnung stammt aus dem Changelog. Wo eine Migration dort
 *           nicht ausdruecklich genannt ist, steht die Fassung, mit der ihr
 *           Gegenstand erschienen ist. Fehlt der Schluessel ganz, bleibt die
 *           Zelle leer — kein Fehler, nur keine Angabe.
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
        'web'   => '1.1',
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
        'web'   => '1.1',
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
        'web'   => '1.1',
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
        'web'   => '1.2',
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
        'web'   => '1.2',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
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
        'web'   => '2.0.0',
        'label' => 'Neue Felder: Sekundärtransport und Schockraum',
        'sql'   => [
            "ALTER TABLE missions ADD COLUMN secondary TINYINT(1) NOT NULL DEFAULT 0",
            "ALTER TABLE missions ADD COLUMN schockraum TINYINT(1) NOT NULL DEFAULT 0",
        ],
    ],
    [
        'id'    => '2026_07_24_rettungsmittel',
        'web'   => '2.0.0',
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
        'web'   => '2.3.0',
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
        'web'   => '2.4.0',
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
        'web'   => '2.6.0',
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
        'web'   => '2.7.0',
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
        'web'   => '2.9.0',
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
        'web'   => '2.11.0',
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
        'web'   => '3.3.1',
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
        'web'   => '4.0.0',
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
        'web'   => '4.5.3',
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
        'web'   => '5.1.1',
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
    [
        'id'    => '2026_08_16_kontokennung',
        'web'   => '5.9.0',
        'label' => 'Kontokennung für die Admin-Sicherungen (users.account_key)',
        /* WARUM EINE EIGENE KENNUNG UND NICHT users.id ODER DIE ADRESSE (E17)
         *
         * Die Kennung ist der Ordnername der Admin-Sicherung. Sie muss
         * unveraenderlich sein und darf NIE ein zweites Mal vergeben werden.
         *
         * Die E-Mail-Adresse scheidet aus: Sie aendert sich (der Ordner
         * muesste mitwandern, ein Fehlschlag bliebe unbemerkt), sie ist eine
         * personenbezogene Angabe im Klartext auf dem Dateisystem, sie bringt
         * Zeichen- und Gross-/Kleinschreibungsprobleme mit — und bei Loeschung
         * plus Neuanlage derselben Adresse traefen Sicherungen mit
         * VERSCHIEDENEN Inhaltsschluesseln in einem Ordner aufeinander.
         *
         * users.id scheidet aus, weil der AUTO_INCREMENT-Zaehler in MariaDB
         * und aelteren MySQL-Fassungen nach einem Serverneustart auf den
         * hoechsten vorhandenen Wert zurueckfallen kann. Ein neu angelegtes
         * Konto koennte dann den Ordner eines geloeschten erben — und dessen
         * Sicherungen unter seinem Namen fuehren.
         *
         * Zwei Zugaben der Zufallskennung: Sie ist nicht erratbar und damit
         * selbst die zweite Schranke, falls die .htaccess einmal nicht greift;
         * und sie verraet weder die Person noch die Zahl der Konten.
         */
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'account_key'");
            if ((int)$q->fetchColumn() === 0) { return false; }
            // Spalte da, aber noch Zeilen ohne Kennung? Dann nicht ueberspringen —
            // der Nachtrag unten laeuft und fuellt genau diese.
            return (int)$pdo->query('SELECT COUNT(*) FROM users WHERE account_key IS NULL')
                            ->fetchColumn() === 0;
        },
        /* EIN ZWEITER LAUF IST FOLGENLOS (P9).
         *
         * Die Spalte wird nur angelegt, wenn sie fehlt; gefuellt wird nur, wo
         * NULL steht. Ein erneuter Lauf findet dann nichts mehr zu tun und
         * vergibt insbesondere KEINE neuen Kennungen an Konten, die schon eine
         * haben — das waere der eine Fehler, der die Zuordnung zu den bereits
         * abgelegten Ordnern zerreisst.
         */
        'run'   => function (PDO $pdo): void {
            $hat = (int)$pdo->query("SELECT COUNT(*) FROM information_schema.columns
                                     WHERE table_schema = DATABASE()
                                       AND table_name = 'users'
                                       AND column_name = 'account_key'")->fetchColumn();
            if ($hat === 0) {
                $pdo->exec('ALTER TABLE users ADD COLUMN account_key CHAR(16) NULL');
            }
            $ids = $pdo->query('SELECT id FROM users WHERE account_key IS NULL')
                       ->fetchAll(PDO::FETCH_COLUMN);
            $up = $pdo->prepare('UPDATE users SET account_key = ? WHERE id = ? AND account_key IS NULL');
            foreach ($ids as $id) {
                /* Der eindeutige Index steht erst nach dem Nachtrag; bis dahin
                 * schuetzt die Wiederholung. Bei 8 Zufallsbytes ist eine
                 * Kollision nicht zu erwarten — aber "nicht zu erwarten" ist
                 * bei einem Ordnernamen, an dem fremde Daten haengen, kein
                 * Grund, es nicht zu pruefen. */
                for ($v = 0; $v < 5; $v++) {
                    $kennung = bin2hex(random_bytes(8));
                    $frei = $pdo->prepare('SELECT 1 FROM users WHERE account_key = ?');
                    $frei->execute([$kennung]);
                    if ($frei->fetchColumn() === false) { break; }
                }
                $up->execute([$kennung, (int)$id]);
            }
            $idx = $pdo->query("SELECT COUNT(*) FROM information_schema.statistics
                                WHERE table_schema = DATABASE() AND table_name = 'users'
                                  AND index_name = 'uq_users_account_key'")->fetchColumn();
            if ((int)$idx === 0) {
                $pdo->exec('ALTER TABLE users ADD UNIQUE KEY uq_users_account_key (account_key)');
            }
        },
    ],
    [
        'id'    => '2026_08_17_notarzt_erweiterung',
        'web'   => '6.0.0',
        'label' => 'Bodengebundene Notarzteinsätze: Diensttage, Standort als Anker, '
                 . 'normalisierte Besatzung',
        'zerstoert' => 'Die Besatzungsspalten crew_p1…crew_other in `days` und `missions` '
                     . 'entfallen; ihr Inhalt wandert vorher nach `day_crew` bzw. '
                     . '`mission_crew`. Ebenso entfallen die Altspalten days.aircraft, '
                     . 'days.base und days.crew sowie bases.is_default.',
        /* INHALTSPRUEFUNG NUR AUF days.crew — und warum genau dort.
         *
         * days.aircraft und days.base gehen NICHT verloren: Sie wandern unten
         * in die eingefrorenen Snapshot-Spalten vehicle_name/base_name, wenn
         * die Stammdaten-Verknuepfung fehlt. Das ist zugleich der Ersatz fuer
         * den Rueckfall, den api/suchindex.php bisher auf diese Altspalten
         * hatte — Diensttage von vor der Stammdaten-Umstellung bleiben nach
         * Standort und Rettungsmittel auffindbar.
         *
         * days.crew hat dagegen kein Ziel. Es ist das Freitextfeld aus der
         * Zeit VOR den Rollenspalten und enthaelt die ganze Besatzung in einer
         * Zeile. Sie auf eine Rolle abzubilden waere geraten — "Sonstige"
         * traege dann eine Aufzaehlung statt eines Namens. Steht dort noch
         * etwas, meldet sich die Migration deshalb, statt zu entscheiden.
         *
         * missions.crew_* mit crew_override = 0 wird bewusst NICHT geprueft:
         * Diese Werte sind schon heute unerreichbar. Die COALESCE-Regel liest
         * sie ausschliesslich bei crew_override = 1 (siehe Technik.md und
         * api/mission.php), ein Wert ohne Haken ist also bereits jetzt ohne
         * Wirkung. Was nie gelesen wird, geht beim Entfernen nicht verloren.
         */
        'inhalt' => [
            ['days', 'crew', 'Besatzung als Freitext (Altfeld vor den Rollenspalten)'],
        ],
        /* DIE PRUEFUNG FRAGT NACH DEM LETZTEN SCHRITT, NICHT NACH DEM ERSTEN.
         *
         * Naheliegend waere "gibt es `vehicles`?" — und genau das ist falsch.
         * Diese Migration baut in vielen Schritten um; bricht ein Lauf in der
         * Mitte ab, existiert `vehicles` bereits, waehrend Standortbezug,
         * Rollenkennung und Aufraeumen noch fehlen. Eine Pruefung auf den
         * ersten Schritt haette den halbfertigen Stand als erledigt verbucht
         * und den Rest nie nachgeholt.
         *
         * `days.aircraft` faellt im allerletzten Schritt. Ist die Spalte weg
         * und `vehicles` da, ist der ganze Weg gegangen — auf einer
         * Neuinstallation aus schema.sql ebenso wie nach einer Migration.
         */
        'skip'  => function (PDO $pdo): bool {
            return _hat_tabelle($pdo, 'vehicles')
                && !_hat_spalte($pdo, 'days', 'aircraft')
                && _hat_spalte($pdo, 'crew_presets', 'base_id');
        },
        /* ---- Ablauf in elf Schritten (Konzept 4.9) -------------------------
         *
         * REIHENFOLGE IST HIER KEIN GESCHMACK. `days` verweist auf `vehicles`,
         * `missions` auf `days` — jeder Fremdschluessel braucht sein Ziel
         * vorher. Deshalb erst die Stammdaten, dann die Diensttage, dann die
         * Einsaetze.
         *
         * JEDER SCHRITT PRUEFT SICH SELBST. Bricht ein Lauf in der Mitte ab,
         * setzt der naechste dort auf, statt an einer bereits angelegten
         * Spalte zu scheitern.
         */
        'run'   => function (PDO $pdo): void {

            /* -- 1. bases: Koordinaten fuer den Abfahrtort (Konzept 3.5.1) -- */
            if (!_hat_spalte($pdo, 'bases', 'lat')) {
                $pdo->exec('ALTER TABLE bases ADD COLUMN lat DECIMAL(9,6) NULL,
                                              ADD COLUMN lon DECIMAL(9,6) NULL');
            }

            /* -- 2. aircraft -> vehicles ---------------------------------- */
            if (!_hat_tabelle($pdo, 'vehicles')) {
                $pdo->exec('RENAME TABLE aircraft TO vehicles');
            }
            if (_hat_spalte($pdo, 'vehicles', 'registration')) {
                $pdo->exec('ALTER TABLE vehicles CHANGE registration name VARCHAR(64) NOT NULL');
            }
            if (!_hat_spalte($pdo, 'vehicles', 'kind')) {
                // Alles Bestehende ist luftgebunden — die Anwendung konnte
                // bisher nichts anderes abbilden.
                $pdo->exec("ALTER TABLE vehicles
                            ADD COLUMN kind ENUM('air','ground') NOT NULL DEFAULT 'air' AFTER name");
                $pdo->exec("ALTER TABLE vehicles ALTER COLUMN kind DROP DEFAULT");
            }
            if (!_hat_spalte($pdo, 'vehicles', 'base_id')) {
                /* NULLABLE, nicht NOT NULL. Bestandsdaten haben keinen
                 * Standortbezug; die Nachbearbeitungsseite traegt ihn nach und
                 * zieht die Bedingung erst danach an (E15, A12). */
                $pdo->exec('ALTER TABLE vehicles ADD COLUMN base_id INT UNSIGNED NULL AFTER user_id');
                $pdo->exec('ALTER TABLE vehicles ADD FOREIGN KEY (base_id)
                            REFERENCES bases(id) ON DELETE CASCADE');
            }

            /* -- 3. Rollen und Faehigkeiten je Rettungsmittel -------------- */
            $pdo->exec('CREATE TABLE IF NOT EXISTS vehicle_roles (
                          vehicle_id INT UNSIGNED NOT NULL,
                          role_code  VARCHAR(16) NOT NULL,
                          PRIMARY KEY (vehicle_id, role_code),
                          FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $pdo->exec('CREATE TABLE IF NOT EXISTS vehicle_capabilities (
                          vehicle_id INT UNSIGNED NOT NULL,
                          capability VARCHAR(16) NOT NULL,
                          PRIMARY KEY (vehicle_id, capability),
                          FOREIGN KEY (vehicle_id) REFERENCES vehicles(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            if (_hat_spalte($pdo, 'vehicles', 'p1')) {
                $insR = $pdo->prepare('INSERT IGNORE INTO vehicle_roles (vehicle_id, role_code)
                                       VALUES (?, ?)');
                $rows = $pdo->query('SELECT id, p1, p2, hems, fr, other FROM vehicles')->fetchAll();
                foreach ($rows as $v) {
                    foreach (['p1', 'p2', 'hems', 'fr', 'other'] as $rolle) {
                        if ((int)$v[$rolle] === 1) { $insR->execute([(int)$v['id'], $rolle]); }
                    }
                }
                $pdo->exec('ALTER TABLE vehicles DROP COLUMN p1, DROP COLUMN p2,
                            DROP COLUMN hems, DROP COLUMN fr, DROP COLUMN other');
            }
            if (_hat_spalte($pdo, 'vehicles', 'is_default')) {
                $pdo->exec('ALTER TABLE vehicles DROP COLUMN is_default');
            }

            /* Schritt 2a des Konzepts: BEIDE Faehigkeiten fuer den Bestand.
             * Bisher standen Winden- und Bergwachtfelder an JEDEM Hubschrauber
             * zur Verfuegung. Ohne diesen Schritt verschwaende vorhandene
             * Dokumentation aus der Anzeige, sobald cap_gate greift. Das
             * Ausduennen auf die tatsaechlich zutreffenden Faehigkeiten ist
             * bewusste Nachpflege und betrifft dann nur neue Diensttage. */
            $pdo->exec("INSERT IGNORE INTO vehicle_capabilities (vehicle_id, capability)
                        SELECT id, 'winch' FROM vehicles WHERE kind = 'air'");
            $pdo->exec("INSERT IGNORE INTO vehicle_capabilities (vehicle_id, capability)
                        SELECT id, 'bergwacht' FROM vehicles WHERE kind = 'air'");

            /* -- 4. Auswahl zentraler Standorte --------------------------- */
            $pdo->exec('CREATE TABLE IF NOT EXISTS user_bases (
                          user_id INT UNSIGNED NOT NULL,
                          base_id INT UNSIGNED NOT NULL,
                          PRIMARY KEY (user_id, base_id),
                          FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
                          FOREIGN KEY (base_id) REFERENCES bases(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            /* -- 5. days: Spalten, Verweis auf vehicles, Schluessel -------- */
            if (_hat_spalte($pdo, 'days', 'aircraft_id')) {
                $fk = _fk_name($pdo, 'days', 'aircraft_id');
                if ($fk !== null) { $pdo->exec("ALTER TABLE days DROP FOREIGN KEY `$fk`"); }
                $pdo->exec('ALTER TABLE days CHANGE aircraft_id vehicle_id INT UNSIGNED NULL');
                $pdo->exec('ALTER TABLE days ADD FOREIGN KEY (vehicle_id)
                            REFERENCES vehicles(id) ON DELETE SET NULL');
            }
            foreach ([
                'started_at'   => 'DATETIME NULL',
                'ended_at'     => 'DATETIME NULL',
                'kind'         => "ENUM('air','ground') NULL",
                'base_name'    => 'VARCHAR(120) NULL',
                'base_lat'     => 'DECIMAL(9,6) NULL',
                'base_lon'     => 'DECIMAL(9,6) NULL',
                'vehicle_name' => 'VARCHAR(64) NULL',
            ] as $spalte => $typ) {
                if (!_hat_spalte($pdo, 'days', $spalte)) {
                    $pdo->exec("ALTER TABLE days ADD COLUMN `$spalte` $typ");
                }
            }

            /* FEHLENDE DIENSTTAGE NACHZIEHEN (A11: kein verwaister Einsatz).
             * Es gibt Einsaetze und Ruhe-Segmente, zu deren Datum keine
             * `days`-Zeile existiert — bis hierher war das folgenlos, weil die
             * Verknuepfung ueber (user_id, day) gerechnet und nicht
             * gespeichert wurde. Ab jetzt traegt `day_id` sie, und ein Einsatz
             * ohne Diensttag verloere seinen Platz in der Uebersicht. */
            $pdo->exec('INSERT INTO days (user_id, day)
                        SELECT DISTINCT m.user_id, m.day FROM missions m
                        WHERE NOT EXISTS (SELECT 1 FROM days d
                                          WHERE d.user_id = m.user_id AND d.day = m.day)');
            $pdo->exec('INSERT INTO days (user_id, day)
                        SELECT DISTINCT r.user_id, r.day FROM rest_segments r
                        WHERE NOT EXISTS (SELECT 1 FROM days d
                                          WHERE d.user_id = r.user_id AND d.day = r.day)');

            /* Zeiten aus dem tatsaechlichen Bestand, ersatzweise 00:00 UTC. */
            $pdo->exec('UPDATE days d SET d.started_at = COALESCE((
                            SELECT LEAST(
                              COALESCE(MIN(m.started_at), MIN(r.started_at)),
                              COALESCE(MIN(r.started_at), MIN(m.started_at)))
                            FROM (SELECT 1) x
                            LEFT JOIN missions m
                              ON m.user_id = d.user_id AND m.day = d.day
                            LEFT JOIN rest_segments r
                              ON r.user_id = d.user_id AND r.day = d.day
                        ), TIMESTAMP(d.day, "00:00:00"))
                        WHERE d.started_at IS NULL');
            $pdo->exec('UPDATE days d SET d.ended_at = (
                            SELECT GREATEST(
                              COALESCE(MAX(m.ended_at), MAX(r.ended_at)),
                              COALESCE(MAX(r.ended_at), MAX(m.ended_at)))
                            FROM (SELECT 1) x
                            LEFT JOIN missions m
                              ON m.user_id = d.user_id AND m.day = d.day
                            LEFT JOIN rest_segments r
                              ON r.user_id = d.user_id AND r.day = d.day
                        ) WHERE d.ended_at IS NULL');

            /* Schritt 3a: Snapshot-Spalten (E8).
             * COALESCE auf die Altspalten ist die Zugabe gegenueber dem
             * Konzept: Wo die Stammdaten-Verknuepfung fehlt, rettet sie den
             * frueheren Freitext in den Snapshot, statt ihn beim Aufraeumen in
             * Schritt 11 zu verlieren. */
            $pdo->exec('UPDATE days d LEFT JOIN bases b ON b.id = d.base_id
                        SET d.base_name = COALESCE(b.name, d.base),
                            d.base_lat  = b.lat,
                            d.base_lon  = b.lon
                        WHERE d.base_name IS NULL');
            $pdo->exec('UPDATE days d LEFT JOIN vehicles v ON v.id = d.vehicle_id
                        SET d.vehicle_name = COALESCE(v.name, d.aircraft)
                        WHERE d.vehicle_name IS NULL');

            /* REIHENFOLGE WICHTIG (MySQL-Fehler 1553), wie schon bei
             * 2026_07_16_mehrere_reanimationen: `uq_user_day (user_id, day)`
             * bedient zugleich den Fremdschluessel auf user_id. Erst den
             * Ersatz anlegen, dann den eindeutigen Schluessel entfernen —
             * sonst stuende der Fremdschluessel kurzzeitig ohne Index da und
             * MySQL verweigert das Loeschen. */
            if (!_hat_index($pdo, 'days', 'idx_user_day')) {
                $pdo->exec('ALTER TABLE days ADD INDEX idx_user_day (user_id, day)');
            }
            if (_hat_index($pdo, 'days', 'uq_user_day')) {
                $pdo->exec('ALTER TABLE days DROP INDEX uq_user_day');
            }

            /* -- 6. day_crew, day_capabilities, day_refs ------------------- */
            $pdo->exec('CREATE TABLE IF NOT EXISTS day_crew (
                          day_id    INT UNSIGNED NOT NULL,
                          role_code VARCHAR(16) NOT NULL,
                          name      VARCHAR(120) NULL,
                          PRIMARY KEY (day_id, role_code),
                          FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $pdo->exec('CREATE TABLE IF NOT EXISTS day_capabilities (
                          day_id     INT UNSIGNED NOT NULL,
                          capability VARCHAR(16) NOT NULL,
                          PRIMARY KEY (day_id, capability),
                          FOREIGN KEY (day_id) REFERENCES days(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            $pdo->exec('CREATE TABLE IF NOT EXISTS day_refs (
                          id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                          day_id    INT UNSIGNED NOT NULL,
                          device_id INT UNSIGNED NULL,
                          day_ref   VARCHAR(64) NOT NULL,
                          UNIQUE KEY uq_dev_dayref (device_id, day_ref),
                          FOREIGN KEY (day_id)    REFERENCES days(id)    ON DELETE CASCADE,
                          FOREIGN KEY (device_id) REFERENCES devices(id) ON DELETE SET NULL
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');

            /* Schritt 4: kind und day_crew, DREISTUFIG.
             *
             * Der neutrale Zustand aus E26 gilt fuer NEUE Diensttage, nicht
             * rueckwirkend — Bestandsdaten duerfen keine Besatzung verlieren.
             *   a) Rettungsmittel vorhanden  -> 'air', Zeilen fuer ALLE Rollen
             *      des Rettungsmittels, auch leere. Der Rollensatz ist damit
             *      eingefroren.
             *   b) kein Rettungsmittel, aber Besatzung -> 'air', Zeilen nur
             *      fuer die belegten Rollen.
             *   c) weder noch -> kind bleibt NULL, keine Zeilen. Der Diensttag
             *      ist neutral und erscheint in der Nachbearbeitung.
             */
            if (_hat_spalte($pdo, 'days', 'crew_p1')) {
                $alt   = ['p1', 'p2', 'hems', 'fr', 'other'];
                $insC  = $pdo->prepare('INSERT IGNORE INTO day_crew (day_id, role_code, name)
                                        VALUES (?, ?, ?)');
                $setK  = $pdo->prepare('UPDATE days SET kind = ? WHERE id = ?');
                $rollQ = $pdo->prepare('SELECT role_code FROM vehicle_roles WHERE vehicle_id = ?');

                $tage = $pdo->query('SELECT id, vehicle_id, crew_p1, crew_p2, crew_hems,
                                            crew_fr, crew_other FROM days')->fetchAll();
                foreach ($tage as $t) {
                    $tagId = (int)$t['id'];
                    $belegt = [];
                    foreach ($alt as $r) {
                        $w = trim((string)($t['crew_' . $r] ?? ''));
                        if ($w !== '') { $belegt[$r] = $w; }
                    }

                    if ($t['vehicle_id'] !== null) {
                        $rollQ->execute([(int)$t['vehicle_id']]);
                        $rollen = $rollQ->fetchAll(PDO::FETCH_COLUMN);
                        foreach ($rollen as $r) {
                            $insC->execute([$tagId, $r, $belegt[$r] ?? null]);
                        }
                        /* Eine belegte Rolle, die das Rettungsmittel gar nicht
                         * vorsieht, bekommt trotzdem ihre Zeile — sonst waere
                         * der Name weg. Genau dafuer ist der Rollensatz eine
                         * Zeilenmenge und keine Ableitung. */
                        foreach ($belegt as $r => $w) {
                            if (!in_array($r, $rollen, true)) { $insC->execute([$tagId, $r, $w]); }
                        }
                        $setK->execute(['air', $tagId]);
                    } elseif ($belegt) {
                        foreach ($belegt as $r => $w) { $insC->execute([$tagId, $r, $w]); }
                        $setK->execute(['air', $tagId]);
                    }
                    // sonst: neutral, kind bleibt NULL
                }
                $pdo->exec('ALTER TABLE days DROP COLUMN crew_p1, DROP COLUMN crew_p2,
                            DROP COLUMN crew_hems, DROP COLUMN crew_fr, DROP COLUMN crew_other');
            }

            /* Schritt 2a, zweite Haelfte: BEIDE Faehigkeiten fuer jeden
             * bestehenden Diensttag — aus demselben Grund wie oben. */
            $pdo->exec("INSERT IGNORE INTO day_capabilities (day_id, capability)
                        SELECT id, 'winch' FROM days");
            $pdo->exec("INSERT IGNORE INTO day_capabilities (day_id, capability)
                        SELECT id, 'bergwacht' FROM days");

            /* -- 7. missions und rest_segments an den Diensttag haengen ---- */
            foreach (['missions', 'rest_segments'] as $tab) {
                if (!_hat_spalte($pdo, $tab, 'day_id')) {
                    $pdo->exec("ALTER TABLE `$tab` ADD COLUMN day_id INT UNSIGNED NULL AFTER client_ref");
                    $pdo->exec("ALTER TABLE `$tab` ADD INDEX idx_day (day_id)");
                    $pdo->exec("ALTER TABLE `$tab` ADD FOREIGN KEY (day_id)
                                REFERENCES days(id) ON DELETE SET NULL");
                }
                if (_hat_spalte($pdo, $tab, 'day')) {
                    $pdo->exec("UPDATE `$tab` t JOIN days d
                                  ON d.user_id = t.user_id AND d.day = t.day
                                SET t.day_id = d.id WHERE t.day_id IS NULL");
                    if (!_hat_index($pdo, $tab, 'idx_user_started')) {
                        $pdo->exec("ALTER TABLE `$tab` ADD INDEX idx_user_started (user_id, started_at)");
                    }
                    if (_hat_index($pdo, $tab, 'user_id')) {
                        $pdo->exec("ALTER TABLE `$tab` DROP INDEX user_id");
                    }
                    $pdo->exec("ALTER TABLE `$tab` DROP COLUMN day");
                }
            }

            /* -- 8. mission_crew: NUR bei crew_override = 1 ---------------- */
            $pdo->exec('CREATE TABLE IF NOT EXISTS mission_crew (
                          mission_id INT UNSIGNED NOT NULL,
                          role_code  VARCHAR(16) NOT NULL,
                          name       VARCHAR(120) NULL,
                          PRIMARY KEY (mission_id, role_code),
                          FOREIGN KEY (mission_id) REFERENCES missions(id) ON DELETE CASCADE
                        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4');
            if (_hat_spalte($pdo, 'missions', 'crew_p1')) {
                foreach (['p1', 'p2', 'hems', 'fr', 'other'] as $r) {
                    $pdo->exec("INSERT IGNORE INTO mission_crew (mission_id, role_code, name)
                                SELECT id, '$r', crew_$r FROM missions
                                WHERE crew_override = 1 AND crew_$r IS NOT NULL AND crew_$r <> ''");
                }
                $pdo->exec('ALTER TABLE missions DROP COLUMN crew_p1, DROP COLUMN crew_p2,
                            DROP COLUMN crew_hems, DROP COLUMN crew_fr, DROP COLUMN crew_other');
            }

            /* -- 9. Neue Einsatzspalten ----------------------------------- *
             *
             * Sie werden HIER angelegt, obwohl sie erst spaeter im
             * Feldkatalog auftauchen. Eine Schemaaenderung in einem Zug ist
             * einer in dreien vorzuziehen: Wer einmal migriert hat, muss es
             * fuer die naechsten Ausbaustufen nicht erneut.
             *
             * transport_mode bleibt NULL — es wird BEWUSST NICHT aus
             * transport_dest erraten. Ein Transportziel sagt nichts darueber,
             * ob per Luft, per Boden oder gar nicht transportiert wurde.
             * start_src, dest_lat und dest_lon bleiben ebenfalls NULL;
             * Koordinaten lassen sich fuer Altdaten nicht ableiten und werden
             * nicht ueber eine Adressanfrage nachgeschlagen.
             */
            foreach ([
                'transport_mode' => "ENUM('air','ground','ambulant') NULL",
                'na_escort'      => 'TINYINT(1) NOT NULL DEFAULT 0',
                'false_alarm'    => 'TINYINT(1) NOT NULL DEFAULT 0',
                'start_src'      => "ENUM('base','prev_site','prev_dest','manual') NULL",
                'dest_lat'       => 'DECIMAL(9,6) NULL',
                'dest_lon'       => 'DECIMAL(9,6) NULL',
            ] as $spalte => $typ) {
                if (!_hat_spalte($pdo, 'missions', $spalte)) {
                    $pdo->exec("ALTER TABLE missions ADD COLUMN `$spalte` $typ");
                }
            }

            /* -- 10. Standortbezug der Stammdaten (E15) -------------------- *
             *
             * ZWEISTUFIG, weil Bestandsdaten keinen Standortbezug haben:
             *   - genau EIN Standort im Zustaendigkeitsbereich -> zuordnen,
             *     ohne Nachfrage. Das ist der Regelfall.
             *   - mehrere oder keiner -> Spalte bleibt leer, die
             *     Nachbearbeitungsseite erledigt es. Erst danach NOT NULL.
             *
             * "Zustaendigkeitsbereich" heisst: fuer persoenliche Stammdaten
             * die eigenen Standorte, fuer zentrale (user_id IS NULL) die
             * zentralen. Gibt es keinen einzigen Standort, legt die Migration
             * KEINEN an — ein erfundener Sammelstandort waere genau die zweite
             * Ebene, die E15 vermeiden soll.
             */
            if (!_hat_spalte($pdo, 'transport_dests', 'lat')) {
                $pdo->exec('ALTER TABLE transport_dests ADD COLUMN lat DECIMAL(9,6) NULL,
                                                        ADD COLUMN lon DECIMAL(9,6) NULL');
            }
            if (_hat_spalte($pdo, 'crew_presets', 'role')) {
                /* Wieder Fehler 1553: uq_user_role_name (user_id, role, name)
                 * bedient den Fremdschluessel auf user_id. Der endgueltige
                 * Ersatz uq_user_base_role_name kann hier noch nicht stehen —
                 * base_id ist noch leer und role_code gibt es noch nicht.
                 * Also ein Behelfsindex, der weiter unten wieder faellt. */
                if (!_hat_index($pdo, 'crew_presets', 'idx_cp_user')) {
                    $pdo->exec('ALTER TABLE crew_presets ADD INDEX idx_cp_user (user_id)');
                }
                $pdo->exec('ALTER TABLE crew_presets DROP INDEX uq_user_role_name');
                $pdo->exec('ALTER TABLE crew_presets CHANGE role role_code VARCHAR(16) NOT NULL');
            }
            foreach (['crew_presets', 'bw_units', 'resources', 'transport_dests'] as $tab) {
                if (!_hat_spalte($pdo, $tab, 'base_id')) {
                    $pdo->exec("ALTER TABLE `$tab` ADD COLUMN base_id INT UNSIGNED NULL AFTER user_id");
                    $pdo->exec("ALTER TABLE `$tab` ADD FOREIGN KEY (base_id)
                                REFERENCES bases(id) ON DELETE CASCADE");
                }
            }

            /* DER EINDEUTIGE SCHLUESSEL MUSS DEN STANDORT ENTHALTEN.
             *
             * Ohne diesen Schritt bliebe (user_id, name) eindeutig — und
             * dieselbe Zielklinik liesse sich nicht an zwei Standorten
             * anlegen. Genau diese Doppelpflege ist aber der bewusst
             * hingenommene Preis von E15; sie zu verhindern kehrte die
             * Entscheidung um.
             *
             * REIHENFOLGE: erst den neuen Schluessel ANLEGEN, dann den alten
             * entfernen. Beide fuehren user_id an erster Stelle, der
             * Fremdschluessel ist also durchgehend mit einem Index versorgt
             * und Fehler 1553 tritt gar nicht erst auf.
             */
            foreach ([
                ['bw_units',        'uq_user_name', 'uq_user_base_name', '(user_id, base_id, name)'],
                ['resources',       'uq_user_res',  'uq_user_base_res',  '(user_id, base_id, name)'],
                ['transport_dests', 'uq_user_name', 'uq_user_base_name', '(user_id, base_id, name)'],
                ['vehicles',        'uq_user_reg',  'uq_user_name',      '(user_id, name)'],
            ] as [$tab, $alt, $neu, $spalten]) {
                if (!_hat_index($pdo, $tab, $neu)) {
                    $pdo->exec("ALTER TABLE `$tab` ADD UNIQUE KEY `$neu` $spalten");
                }
                if (_hat_index($pdo, $tab, $alt)) {
                    $pdo->exec("ALTER TABLE `$tab` DROP INDEX `$alt`");
                }
            }

            /* Je Zustaendigkeitsbereich genau einen Standort? Dann zuordnen. */
            $einzel = [];   // user_id (oder 0 fuer zentral) => base_id
            $bq = $pdo->query('SELECT COALESCE(user_id, 0) AS uid, COUNT(*) AS n, MIN(id) AS bid
                               FROM bases GROUP BY COALESCE(user_id, 0)');
            foreach ($bq->fetchAll() as $z) {
                if ((int)$z['n'] === 1) { $einzel[(int)$z['uid']] = (int)$z['bid']; }
            }
            foreach (['crew_presets', 'bw_units', 'resources', 'transport_dests', 'vehicles'] as $tab) {
                $up = $pdo->prepare("UPDATE `$tab` SET base_id = ?
                                     WHERE base_id IS NULL AND COALESCE(user_id, 0) = ?");
                foreach ($einzel as $uid => $bid) { $up->execute([$bid, $uid]); }
            }
            /* Eindeutiger Schluessel erst jetzt, wenn base_id gefuellt ist.
             * Danach bedient er den Fremdschluessel auf user_id selbst und der
             * Behelfsindex von oben wird ueberfluessig. */
            if (!_hat_index($pdo, 'crew_presets', 'uq_user_base_role_name')) {
                $pdo->exec('ALTER TABLE crew_presets
                            ADD UNIQUE KEY uq_user_base_role_name (user_id, base_id, role_code, name)');
            }
            if (_hat_index($pdo, 'crew_presets', 'idx_cp_user')) {
                $pdo->exec('ALTER TABLE crew_presets DROP INDEX idx_cp_user');
            }

            /* -- 11. user_defaults und Aufraeumen ------------------------- */
            $q = $pdo->query("SELECT COLUMN_TYPE FROM information_schema.columns
                              WHERE table_schema = DATABASE() AND table_name = 'user_defaults'
                                AND column_name = 'kind'");
            $typ = (string)$q->fetchColumn();
            if (str_contains($typ, 'aircraft')) {
                $pdo->exec("ALTER TABLE user_defaults
                            MODIFY kind ENUM('base','aircraft','vehicle') NOT NULL");
                $pdo->exec("UPDATE user_defaults SET kind = 'vehicle' WHERE kind = 'aircraft'");
                $pdo->exec("ALTER TABLE user_defaults MODIFY kind ENUM('base','vehicle') NOT NULL");
            }

            /* Zentrale Standorte, die in einem bestehenden Diensttag benutzt
             * wurden, ausdruecklich auswaehlen — sonst verschwinden sie aus
             * den Auswahllisten (E16). */
            $pdo->exec('INSERT IGNORE INTO user_bases (user_id, base_id)
                        SELECT DISTINCT d.user_id, d.base_id
                        FROM days d JOIN bases b ON b.id = d.base_id
                        WHERE d.base_id IS NOT NULL AND b.user_id IS NULL');

            foreach ([['days', 'aircraft'], ['days', 'base'], ['days', 'crew'],
                      ['bases', 'is_default']] as [$tab, $spalte]) {
                if (_hat_spalte($pdo, $tab, $spalte)) {
                    $pdo->exec("ALTER TABLE `$tab` DROP COLUMN `$spalte`");
                }
            }
        },
    ],
    [
        'id'    => '2026_08_27_logo_wahl',
        'web'   => '9.7',
        'label' => 'Logo-Wahl je Profil (Standard / Hubschrauber / Fahrzeug / wechselnd)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'logo_wahl'");
            return (int)$q->fetchColumn() > 0;
        },
        /* LEERSTRING IST DER STANDARD, kein 'hubschrauber'. Wer nichts
         * gewaehlt hat, folgt dem Standard der Installation — und der kann
         * sich aendern (E-P3-20; die Wahl dafuer entsteht in O9). Stuende
         * hier 'hubschrauber' als Vorgabe, haetten alle bestehenden Konten
         * eine ausdrueckliche Wahl getroffen, die sie nie getroffen haben,
         * und ein spaeterer Wechsel des Installationsstandards ginge an
         * ihnen vorbei. */
        'sql'   => [
            "ALTER TABLE users ADD COLUMN logo_wahl VARCHAR(20) NOT NULL DEFAULT '' AFTER account_key",
        ],
    ],
    [
        'id'    => '2026_08_28_last_login',
        'web'   => '9.8',
        'label' => 'Zeitpunkt der letzten Anmeldung je Konto (Kontoseite, NutzerInnen-Liste)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.columns
                              WHERE table_schema = DATABASE()
                                AND table_name = 'users' AND column_name = 'last_login'");
            return (int)$q->fetchColumn() > 0;
        },
        /* NULL FUER DEN BESTAND, kein NOW(). Die Kontoseite und die
         * NutzerInnen-Liste zeigen „zuletzt angemeldet" (E-P3-41). Wuerde die
         * Migration den Zeitpunkt der Migration eintragen, saehe der ganze
         * Bestand aus, als haette sich jedes Konto heute angemeldet — eine
         * erfundene Angabe genau in der Spalte, die man liest, um ungenutzte
         * Konten zu finden. NULL heisst hier „nicht bekannt" und wird als
         * „—" gezeigt; nach der ersten Anmeldung steht der wahre Wert da.
         *
         * KEIN Zugriffszaehler und keine zweite Zeitangabe: Gefragt ist, ob
         * ein Konto benutzt wird, nicht wie oft. Ein Feld, das bei jedem
         * Seitenaufruf geschrieben wuerde, waere ausserdem eine Schreiblast
         * ohne Nutzen — geschrieben wird nur bei der Anmeldung. */
        'sql'   => [
            'ALTER TABLE users ADD COLUMN last_login DATETIME NULL AFTER logo_wahl',
        ],
    ],
    [
        'id'    => '2026_08_30_rechtstexte',
        'web'   => '9.11',
        'label' => 'Impressum und Datenschutzerklärung dieser Installation (R32)',
        'skip'  => function (PDO $pdo): bool {
            $q = $pdo->query("SELECT COUNT(*) FROM information_schema.tables
                              WHERE table_schema = DATABASE() AND table_name = 'rechtstexte'");
            return (int)$q->fetchColumn() > 0;
        },
        'sql'   => [
            /* MEDIUMTEXT, nicht TEXT: TEXT sind 64 KB in BYTES, und deutsche
             * Rechtstexte in utf8mb4 haben Umlaute. Der Unterschied kostet
             * nichts. Kein Vorgabeinhalt — die Anwendung liefert keinen
             * Rechtstext mit; der Leerzustand IST die Auslieferung. */
            'CREATE TABLE rechtstexte (
               schluessel VARCHAR(32) NOT NULL PRIMARY KEY,
               inhalt     MEDIUMTEXT NULL,
               stand_am   DATE NULL
             ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4',
        ],
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

/* ---- Logo-Standard der Installation (E-P3-19/20, seit Web 9.10.0) ---------
 *
 * Bis Web 9.9.0 war der Standard eine Konstante in session_lib.php. Er gehoert
 * hierher und nicht ins Profil: Er betrifft die ganze Installation und nicht
 * ein Konto — die Anmeldeseite zeigt ihn, und jedes Konto ohne eigene Wahl
 * folgt ihm.
 *
 * Warum die WARTUNG und nicht eine eigene Seite: Es ist eine Einstellung, die
 * einmal im Leben einer Installation gesetzt wird, zusammen mit dem, was hier
 * sonst steht (Umgebung, Aufraeumjob, Schluesselableitung). Eine eigene Seite
 * fuer eine Einstellung ist ein Menuepunkt, den man einmal braucht und
 * dreihundertmal ueberliest.
 */
$logoMeldung = null;
if (!$istCli && $_SERVER['REQUEST_METHOD'] === 'POST'
    && ($_POST['action'] ?? '') === 'logo_standard') {
    csrf_check();
    $wahl = (string)($_POST['logo'] ?? '');
    if ($wahl !== 'hubschrauber' && $wahl !== 'fahrzeug') {
        $logoMeldung = ['fehler', 'Unbekannte Logo-Wahl — es wurde nichts geändert.'];
    } else {
        $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute(['logo_standard', $wahl]);
        $logoMeldung = ['ok', 'Standard der Installation: '
            . ($wahl === 'fahrzeug' ? 'Fahrzeug (NEF)' : 'Hubschrauber (RTH)')
            . '. Wer im Profil keine eigene Wahl getroffen hat, sieht das ab sofort.'];
    }
}

/**
 * Liegt der NEF-Platzhalter noch (E-P3-19)?
 *
 * Die echte Datei ersetzt ihn 1:1 — gleicher Name, gleicher viewBox, kein
 * Eingriff im Code. Damit der Hinweis dann VON SELBST verschwindet, wird nicht
 * eine Zahl im Code gepflegt, sondern die DATEI gefragt: Der Platzhalter
 * traegt das Wort in seinem Kopfkommentar. Gelesen werden nur die ersten 400
 * Byte — der Kommentar steht ganz oben, und die ganze Datei zu lesen waere
 * fuer eine Ja/Nein-Frage zu viel.
 */
function logo_platzhalter_liegt(): array
{
    $liegt = [];
    foreach (['gen-em_logo_fahrzeug.svg', 'gen-em_logo_fahrzeug_weiss.svg'] as $datei) {
        $pfad = __DIR__ . '/assets/images/' . $datei;
        if (!is_file($pfad)) { continue; }
        $f = @fopen($pfad, 'rb');
        if ($f === false) { continue; }
        $kopf = (string)fread($f, 400);
        fclose($f);
        if (str_contains($kopf, 'PLATZHALTER')) { $liegt[] = $datei; }
    }
    return $liegt;
}

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
$results = [];   // [id, label, status, detail, zerstoert, blockiertId, web]
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
            /* SECHS ELEMENTE, wie jede andere Zeile hier. Diese eine trug nur
             * vier — und die Auswertung unten zerlegt sie in sechs Variablen
             * (`foreach ($results as [$id, $label, $status, $detail,
             * $zerstoert, $blockId])`). Ergebnis: zwei PHP-Warnungen JE
             * BEREITS ANGEWENDETER MIGRATION, bei jedem Aufruf dieser Seite.
             * Auf einer Installation mit 30 verbuchten Migrationen sind das
             * sechzig Zeilen Fehlerprotokoll für nichts — ausgerechnet auf der
             * Seite, die den Zustand der Datenbank berichten soll. Angezeigt
             * wurde trotzdem das Richtige, weil die fehlenden Werte als NULL
             * ankamen; deshalb ist es nie aufgefallen.
             *
             * Gefunden beim Prüfen der Etappe 1b im Serverprotokoll; der Fehler
             * ist älter als diese Etappe. */
            $results[] = [$m['id'], $m['label'], 'ok', 'Bereits angewendet.', null, null,
                          $m['web'] ?? null];
            continue;
        }
        $nichtNoetig = false;
        try {
            $nichtNoetig = isset($m['skip']) && ($m['skip'])($pdo);
        } catch (Throwable $ex) {
            $results[] = [$m['id'], $m['label'], 'warn',
                          'Zustand nicht feststellbar: ' . $ex->getMessage(),
                          $m['zerstoert'] ?? null, null, $m['web'] ?? null];
            $offen++;
            continue;
        }
        if ($nichtNoetig) {
            $results[] = [$m['id'], $m['label'], 'ok',
                          'Nicht nötig (Schema bereits aktuell) — wird beim Ausführen als erledigt vermerkt.',
                          null, null, $m['web'] ?? null];
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
                          $m['zerstoert'] ?? null, $m['id'], $m['web'] ?? null];
            $blockiert++;
            continue;
        }
        $results[] = [$m['id'], $m['label'], 'todo',
                      'STEHT AN — wird beim Ausführen angewendet.',
                      $m['zerstoert'] ?? null, null, $m['web'] ?? null];
        $offen++;
    }
} else {
    /* ---- AUSFUEHRUNG ---------------------------------------------------- */
    foreach ($MIGRATIONS as $m) {
        if (in_array($m['id'], $applied, true)) {
            $results[] = [$m['id'], $m['label'], 'ok', 'Bereits angewendet.', null, null,
                          $m['web'] ?? null];
            continue;
        }

        if (isset($m['skip']) && ($m['skip'])($pdo)) {
            $pdo->prepare('INSERT INTO schema_migrations (id, status) VALUES (?, "skipped")')
                ->execute([$m['id']]);
            $results[] = [$m['id'], $m['label'], 'ok',
                          'Nicht nötig (Schema bereits aktuell) — als erledigt vermerkt.', null, null,
                          $m['web'] ?? null];
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
                          $m['zerstoert'] ?? null, $m['id'], $m['web'] ?? null];
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
            $results[] = [$m['id'], $m['label'], 'ok', $detail, $m['zerstoert'] ?? null, null,
                          $m['web'] ?? null];
            $ranSomething = true;
        } catch (Throwable $ex) {
            // Nicht verbuchen -> naechster Aufruf versucht es erneut
            $results[] = [$m['id'], $m['label'], 'fail',
                          'Fehler: ' . $ex->getMessage() . ' — Migration wurde NICHT als erledigt vermerkt.',
                          $m['zerstoert'] ?? null, null, $m['web'] ?? null];
            break;   // Reihenfolge wahren: nachfolgende Migrationen nicht ausfuehren
        }
    }
}

// Kommandozeile: Ergebnis als Text ausgeben und beenden — ohne HTML-Geruest.
if ($istCli) {
    foreach ($results as [$id, $label, $status, $detail, $zerstoert, $blockId, $web]) {
        printf("%-6s %-9s %-46s %s\n", strtoupper($status),
               $web !== null ? ('Web ' . $web) : '', $id, $detail);
        // Auf der Kommandozeile gibt es die Freigabe nicht (siehe oben) —
        // dafuer den Weg dorthin.
        if ($status === 'stopp') {
            printf("%-6s %-46s %s\n", '', '',
                   '-> Daten sichern, dann auf der Wartungsseite einzeln freigeben.');
        }
    }
    exit($ranSomething ? 0 : 0);
}

ui_seite_start(['titel' => 'Datenbank-Update']);
?>

<?php /* Seitenleiste wie auf den uebrigen Verwaltungsseiten.
   Bis Web 4.5.2 stand diese Seite ohne sie da — sie war ja nur ueber die
   direkte Adresse erreichbar und damit ohnehin eine Sackgasse: Wer hier
   landete, kam nur ueber den Zurueck-Knopf wieder heraus. */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'wartung']); ?>

  <?php ui_titelzeile(['titel' => 'Wartung & Datenbank-Update']); ?>

  <?php /* ---- ZUSTAND ZUERST, TABELLE DANACH (Web 7.0.0) --------------------
     *
     * Bis Web 6.3.0 stand die Updatetabelle ganz oben und darunter, nach
     * dreissig Zeilen „Bereits angewendet", die Auskunft über Umgebung und
     * Aufräumjob. Genau die ist aber der Grund, aus dem man diese Seite im
     * Betrieb öffnet — die Tabelle liest man einmal vor einem Update.
     *
     * Also andersherum: erst was JETZT gilt (Schlüsselableitung, Mailversand,
     * Aufräumjob), dann was anstünde. Der Knopf für das Update steht seither
     * ÜBER seiner Tabelle: Er gehört zu ihr, und wer die Liste gelesen hat,
     * scrollt nicht wieder ans Ende zurück, um sie auszuführen.
     *
     * P3/O11: Jeder dieser Abschnitte ist jetzt eine KARTE. Vorher waren es
     * <h2>-Überschriften über losem Fließtext, getrennt durch waagerechte
     * Linien mit einer Klasse, die es im neuen Stylesheet nicht mehr gibt —
     * die Trenner waren also seit Web 9.0.0 nur noch die des Browsers. */ ?>

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
    <?php ui_karte_start(['titel' => 'Schlüsselableitung',
                          'plakette' => ui_plakette('Anmeldung blockiert', ['ton' => 'rot'])]); ?>
      <?php $summe = array_sum(array_column($kdfVerwaist, 'n'));
            $t = [];
            foreach ($kdfVerwaist as $z) { $t[] = $z['kdf_iter'] . ' (' . $z['n'] . '×)'; } ?>
      <?= ui_meldung_markup('fehler',
          $summe . ' Konto/Konten können sich nicht anmelden. Sie tragen eine '
        . 'Rundenzahl, die diese Fassung nicht mehr anbietet: '
        . implode(', ', $t) . '. Angeboten wird nur '
        . implode(', ', array_map('strval', $kdfListe)) . '.', 'Achtung:') ?>
      <p class="feld-hinweis"><strong>Behebung:</strong> Den fehlenden Wert in
         <code>KDF_ITER_LISTE</code> (<code>server/db.php</code>) wieder
         aufnehmen. Danach melden sich die Konten wie gewohnt an und werden beim
         nächsten Mal still angehoben.</p>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- Logo der Installation (E-P3-19/20) ----------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Logo']); ?>
    <?php if ($logoMeldung !== null): ?>
      <?= ui_meldung_markup($logoMeldung[0], $logoMeldung[1]) ?>
    <?php endif; ?>
    <?php $platzhalter = logo_platzhalter_liegt(); ?>
    <?php if ($platzhalter): ?>
      <?= ui_meldung_markup('warn',
          'Das Fahrzeug-Logo (NEF) ist ein Platzhalter — es steht hier, damit die '
        . 'Logo-Wahl vollständig gebaut und geprüft werden kann, bevor die echte '
        . 'Datei vorliegt. Sie ersetzt ihn 1:1: gleicher Name, gleiche Maße, kein '
        . 'Eingriff im Code. Betroffen: ' . implode(', ', $platzhalter) . '. '
        . 'Dieser Hinweis verschwindet von selbst, sobald die echten Dateien liegen.') ?>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="logo_standard">
      <?php ui_segment(['name' => 'logo', 'id' => 'logo-standard',
                        'wert' => logo_standard(),
                        'optionen' => ['hubschrauber' => 'Hubschrauber (RTH)',
                                       'fahrzeug'     => 'Fahrzeug (NEF)']]); ?>
      <p class="feld-hinweis">Der <strong>Standard dieser Installation</strong>. Er gilt für
         die Anmeldeseite und für jedes Konto, das im Profil keine eigene Wahl getroffen
         hat — eine getroffene Wahl bleibt unberührt. Die Änderung wirkt sofort, auch für
         bereits angemeldete Konten.</p>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Standard speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

  <?php require_once __DIR__ . '/smtp.php'; ?>
  <?php ui_karte_start(['titel' => 'Umgebung']); ?>
    <?php if (antwort_entkoppelbar()): ?>
      <?php ui_zeile([
          'text'  => 'Mailversand',
          'klein' => 'Die Antwort wird abgeschlossen, bevor der Versand beginnt. '
                   . 'Die Anforderung „Passwort vergessen" dauert damit für '
                   . 'vorhandene und unbekannte Adressen gleich lang.',
          'plaketten' => ui_plakette('entkoppelt', ['ton' => 'blau']),
      ]); ?>
    <?php else: ?>
      <?php ui_zeile([
          'text' => 'Mailversand',
          'plaketten' => ui_plakette('nicht sicher entkoppelbar', ['ton' => 'orange']),
      ]); ?>
      <?= ui_meldung_markup('warn', 'Diese PHP-Anbindung kennt weder '
          . 'fastcgi_finish_request noch litespeed_finish_request. Die Antwort '
          . 'wird zwar mit Längenangabe abgeschlossen, was üblicherweise reicht; '
          . 'verbindlich ist es nicht. Im ungünstigen Fall bleibt die Dauer der '
          . 'Anforderung „Passwort vergessen" ein Hinweis darauf, ob es zu einer '
          . 'Adresse ein Konto gibt.') ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

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
  $wOk      = $wVersuch !== null && $wErfolg === $wVersuch;
  ?>
  <?php ui_karte_start(['titel' => 'Aufräumjob',
      'plakette' => $wVersuch === null
          ? ui_plakette('noch nie gelaufen', ['ton' => 'neutral'])
          : ($wOk ? ui_plakette('läuft', ['ton' => 'blau'])
                  : ui_plakette('scheitert', ['ton' => 'rot']))]); ?>
    <?php
      ui_zeile(['text' => 'Letzter Versuch',
                'plaketten' => ui_plakette($wVersuch ?? 'nie',
                    ['ton' => $wVersuch === null ? 'neutral' : 'blau'])]);
      ui_zeile(['text' => 'Letzter vollständiger Lauf',
                'plaketten' => ui_plakette($wErfolg ?? 'nie',
                    ['ton' => $wErfolg === null ? 'rot' : 'blau'])]);
    ?>
    <?php if ($wVersuch === null): ?>
      <p class="feld-hinweis">Auf einer frischen Installation ist das normal — er
         startet bei der ersten Anfrage des nächsten Tages.</p>
    <?php elseif (!$wOk): ?>
      <?= ui_meldung_markup('warn', $wErfolg === null
          ? 'Noch kein einziger vollständiger Lauf. Mindestens ein Schritt '
          . 'scheitert dauerhaft; die Ursache steht im Fehlerprotokoll des '
          . 'Webspace (Suchwort cleanup:). Solange das so bleibt, wird unter '
          . 'anderem der Papierkorb nicht geleert.'
          : 'Es scheitert mindestens ein Schritt — Ursache im Fehlerprotokoll '
          . 'des Webspace (Suchwort cleanup:).') ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php
  /* ---- Bestandsaufnahme: Einsätze ohne Diensttag (Backlog Nr. 33) --------
   *
   * KEINE MIGRATION, SONDERN EIN BERICHT — und zwar mit Absicht. Eine
   * Migration gilt nach einem Durchlauf als erledigt und meldet sich nie
   * wieder; dieser Zustand soll aber so lange sichtbar bleiben, wie es ihn
   * gibt. Er wird auch nicht selbsttätig behoben: Welchem Diensttag ein
   * verwaister Einsatz zugeschlagen wird, ist eine fachliche Entscheidung,
   * und die trifft keine Wartungsseite.
   *
   * WIE DER ZUSTAND ENTSTAND. Bis Web 8.0.0 entfernte trash_purge_day() nur
   * die GELÖSCHTEN Einsätze eines Diensttags und danach den Tag selbst; ein
   * aktiver Einsatz daran überlebte den ersten Schritt und verlor im zweiten
   * seinen Diensttag (`ON DELETE SET NULL`). Aktiv an einem gelöschten Tag
   * konnte er über zwei Wege werden — das Zurückholen eines einzeln
   * gelöschten Einsatzes im Papierkorb und eine Nachlieferung der Uhr über
   * eine Kennung in `day_refs`. Beide Wege sind seit Web 8.0.0 zu, und
   * trash_purge_day() nimmt jetzt alles mit. Was VORHER entstanden ist,
   * liegt aber noch da, und sichtbar ist es fast nirgends: in der Suche und
   * auf der Einsatzseite ja — in Tagesübersicht, Zeitraum, Export und
   * Nachbearbeitung nicht, und in einer Sicherung ist es zwar enthalten,
   * kommt beim Einspielen aber nicht zurück.
   */
  $waisen = $pdo->query(
      'SELECT m.id, m.user_id, u.email, m.started_at
         FROM missions m JOIN users u ON u.id = m.user_id
        WHERE m.day_id IS NULL AND m.deleted_at IS NULL
        ORDER BY u.email, m.started_at')->fetchAll(PDO::FETCH_ASSOC);
  ?>
  <?php ui_karte_start(['titel' => 'Einsätze ohne Diensttag',
      'zahl' => count($waisen),
      'plakette' => $waisen ? ui_plakette('unvollständig sichtbar', ['ton' => 'rot']) : '']); ?>
    <?php if (!$waisen): ?>
      <p class="feld-hinweis">Keine. Jeder aktive Einsatz hängt an einem
         Diensttag — so soll es sein.</p>
    <?php else: ?>
      <?= ui_meldung_markup('warn', count($waisen) . ' aktive Einsätze haben '
          . 'keinen Diensttag. Sie stammen aus einem Stand vor Web 8.0.0 (siehe '
          . 'Changelog, Backlog Nr. 33). Sie sind in der Suche zu finden, fehlen '
          . 'aber in Tagesübersicht, Zeitraum, Export und Nachbearbeitung — und '
          . 'eine Sicherung führt sie zwar mit, spielt sie aber nicht zurück.') ?>
      <p class="feld-hinweis"><strong>Zu tun:</strong> Jeden Einsatz öffnen und über
         <strong>Verschieben</strong> an einen Diensttag hängen (oder löschen,
         wenn er nicht gebraucht wird). Diese Seite ändert von sich aus nichts —
         welcher Diensttag der richtige ist, steht hier nicht.</p>
      <?php foreach ($waisen as $w): ?>
        <?php ui_zeile([
            'text'  => (string)$w['email'],
            'klein' => 'Einsatzbeginn ' . fmt_local((string)$w['started_at'], 'd.m.Y H:i'),
            'plaketten' => ui_plakette('Nr. ' . (int)$w['id'], ['ton' => 'neutral']),
            'aktionen' => ui_zeilenaktionen([
                'titel' => 'Einsatz Nr. ' . (int)$w['id'],
                'eintraege' => [
                    ['text' => 'Ansehen', 'symbol' => 'lupe',
                     'href' => 'einsatz.php?id=' . (int)$w['id']],
                    ['text' => 'Verschieben', 'symbol' => 'tausch',
                     'href' => 'einsatz_verschieben.php?id=' . (int)$w['id']],
                ],
            ]),
        ]); ?>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Datenbank-Update ---------------------------------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Datenbank-Update',
      'zahl' => 'Web ' . WEB_VERSION,
      'plakette' => $results
          ? ($offen > 0 || $blockiert > 0
             ? ui_plakette($offen + $blockiert === 1 ? '1 offen'
                         : ($offen + $blockiert) . ' offen', ['ton' => 'orange'])
             : ui_plakette('aktuell', ['ton' => 'blau']))
          : '']); ?>

    <?php if (!$results): ?>
      <?= ui_meldung_markup('info', 'Keine Migrationen definiert.') ?>

    <?php elseif (!$ausfuehren): ?>
      <?php /* ---- Vorschau: es wurde noch NICHTS geändert ---- */ ?>
      <?php if ($offen === 0): ?>
        <?= ui_meldung_markup('info', 'Die Datenbank ist auf dem aktuellen Stand. '
            . 'Es steht nichts an.') ?>
      <?php else: ?>
        <?= ui_meldung_markup('warn', $offen . ' Eintrag/Einträge stehen aus. '
            . 'Unten steht, was passieren würde.', 'Es wurde noch nichts geändert.') ?>
        <?= ui_meldung_markup('warn', 'Migrationen können Spalten und Daten '
            . 'unwiderruflich entfernen. Die Sicherung dauert eine Minute — eine '
            . 'verlorene Spalte dagegen ist verloren.', 'Vorher eine Sicherung erstellen.',
            ui_knopf(['text' => 'Zur Sicherung', 'art' => 'neutral', 'symbol' => 'sicherung',
                      'href' => 'einstellungen.php?t=backup'])) ?>
      <?php endif; ?>
      <?php if ($blockiert > 0): ?>
        <?= ui_meldung_markup('fehler', $blockiert . ' Migration(en) werden NICHT '
            . 'ausgeführt, weil sie Spalten löschen würden, in denen noch Daten '
            . 'stehen. Unten ist je Eintrag genannt, um welche Spalte und wie '
            . 'viele Zeilen es geht. Diese Daten lassen sich nicht automatisch in '
            . 'den verschlüsselten Block überführen — er entsteht ausschließlich '
            . 'im Browser. Wer sie behalten will, trägt sie vorher von Hand in den '
            . 'jeweiligen Einsatz ein (oder sichert sie außerhalb) und gibt die '
            . 'Migration danach einzeln frei.') ?>
      <?php endif; ?>
    <?php elseif ($ranSomething): ?>
      <?= ui_meldung_markup('ok', 'Updates wurden angewendet — Details unten.') ?>
    <?php else: ?>
      <?= ui_meldung_markup('info', 'Es war nichts anzuwenden.') ?>
    <?php endif; ?>

    <?php if ($results): ?>
      <?php /* DER KNOPF STEHT ÜBER DER LISTE (Web 7.0.0). Er stand darunter,
               hinter dreissig Zeilen — man las die Vorschau und scrollte dann
               zurück zum Anfang, weil man dort die Schaltfläche vermutete.
               Das Formular trägt weiterhin die Kennung `migform`: Die
               Freigabe-Häkchen in den Zeilen darunter gehören über `form=` zu
               ihm, und das funktioniert unabhängig von der Reihenfolge im
               Dokument. */ ?>
      <?php if (!$ausfuehren && ($offen > 0 || $blockiert > 0)): ?>
        <form method="post" action="update.php" id="migform">
          <?= csrf_field() ?><input type="hidden" name="action" value="run">
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Updates jetzt anwenden', 'art' => 'primaer',
                          'symbol' => 'datenbank']) ?>
          </div>
          <p class="feld-klein">Der Aufruf dieser Seite ändert nichts. Erst dieser
             Knopf führt die Updates aus.<?php if ($blockiert > 0): ?> Die
             blockierten Einträge bleiben dabei unangetastet, solange ihr Häkchen
             nicht gesetzt ist.<?php endif; ?></p>
        </form>
      <?php elseif ($ausfuehren): ?>
        <p class="feld-hinweis">Bereits erledigte Updates werden übersprungen —
           ein erneuter Lauf ist ungefährlich.</p>
      <?php endif; ?>

      <?php /* UMGEKEHRTE REIHENFOLGE (Web 7.0.0). Die Liste wächst hinten an,
               und die Einträge, die interessieren, sind die neuen: Was ansteht,
               steht am Ende der Datei. Wer die Seite öffnet, sah zuerst die
               Migration von 2026-07-16 und musste zum Antwortgeben scrollen.
               Umgekehrt steht die Antwort oben.
               Die AUSFÜHRUNG bleibt in Katalogreihenfolge — sie muss es, weil
               Migrationen aufeinander aufbauen. Gedreht wird allein die
               Anzeige.

               P3/O11: Aus der vierspaltigen Tabelle sind Zeilen geworden. Der
               Anlass ist die Statusspalte: Sie trug ✔ ● ! ✖ ⚠ — Schriftzeichen
               als Symbol, und genau das schliesst E-P3-18 aus. Ein Häkchen und
               ein Ausrufezeichen sagen ohnehin nichts, was ein Wort nicht
               besser sagte. Jetzt steht der Status als Plakette mit Ton. */ ?>
      <?php foreach (array_reverse($results) as [$id, $label, $status, $detail, $zerstoert, $blockId, $web]): ?>
        <?php
          [$statusText, $statusTon] = match ($status) {
              'ok'    => ['erledigt',  'blau'],
              'todo'  => ['steht aus', 'orange'],
              'warn'  => ['Hinweis',   'orange'],
              'stopp' => ['blockiert', 'rot'],
              default => ['Fehler',    'rot'],
          };
          $klein = [$detail];
          if ($zerstoert !== null) { $klein[] = 'Löscht Daten: ' . $zerstoert; }
          $klein[] = $id;
          $plaketten = ui_plakette($statusText, ['ton' => $statusTon]);
          if ($web !== null) { $plaketten .= ui_plakette('Web ' . $web, ['ton' => 'neutral']); }
          /* Das Freigabe-Häkchen steht VORN in der Zeile — dort, wo der
             Baustein Auswahlkästchen erwartet (ui_zeile, Schlüssel `vorn`).
             Es gehört über `form=` zum Formular oben, nicht zu einem eigenen. */
          $vorn = ($blockId !== null && !$ausfuehren)
              ? '<input type="checkbox" name="forcieren[]" form="migform" value="'
                . e($blockId) . '" aria-label="' . e($label)
                . ' trotzdem ausführen — Daten sind gesichert">'
              : '';
          ui_zeile([
              'vorn'  => $vorn,
              'text'  => $label,
              'klein' => implode(' · ', $klein),
              'plaketten' => $plaketten,
          ]);
        ?>
      <?php endforeach; ?>
      <?php if (!$ausfuehren && $blockiert > 0): ?>
        <p class="feld-hinweis">Ein gesetztes Häkchen vor einer blockierten
           Migration heißt: <strong>Die Daten sind gesichert, diese eine trotzdem
           ausführen.</strong></p>
      <?php endif; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
