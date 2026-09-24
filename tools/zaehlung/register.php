<?php
declare(strict_types=1);

/**
 * REGISTER — je Muster eine Zeile: Kennung, Beschreibung, Regel, Sicht,
 * Decke, Ausnahmen. Gelesen von `zaehlen.php`.
 * ===========================================================================
 *
 * DIE DECKE IST DAS EIGENTLICHE. `start` sagt, wo der Bestand am 20.09.2026
 * stand; `decke` sagt, was nach dem genannten Arbeitspaket hoechstens noch
 * dastehen darf. Liegt der Ist-Wert darueber, schlaegt die Zaehlung an —
 * auch Jahre spaeter, wenn niemand mehr weiss, warum die Zeile da ist.
 * Deshalb traegt jede Zeile ihren `grund`.
 *
 * SOLANGE EIN PAKET NOCH NICHT GEBAUT IST, steht die Decke auf dem Startwert
 * (`decke_jetzt`), und `decke_ziel` sagt, worauf das Paket sie setzt. Erst
 * das Paket schreibt `decke_jetzt` herunter. Ohne diese Trennung waere die
 * Zaehlung von AP1 bis AP9 durchgehend rot und damit wertlos.
 *
 * EINE DECKE WIRD NICHT ANGEHOBEN, OHNE DASS ES IM KONZEPT STEHT (E-ZE-24).
 * Wer eine zweite Stelle braucht, begruendet sie dort — nicht hier.
 *
 * SICHTEN: `php_ohne_zeichenketten` (Aufrufe) · `php_mit_zeichenketten`
 * (SQL und Literale) · `js_und_inline` (JavaScript). Beschrieben im Kopf von
 * `zaehlen.php`.
 *
 * FELDER NEBEN DER REGEL: `bereich` — `php` (alle PHP-Dateien), `api` (nur
 * `server/api/`), `js_und_inline`, `php_und_tools` (dazu `tools/`); `ausser`
 * (Pfade, auch Verzeichnisse) und `nur` (genau eine Datei); `zaehlt` —
 * `treffer` (Vorgabe) oder `dateien`, wenn die Sache eine Datei ist.
 *
 * REGELARTEN: `aufruf` (echter Funktionsaufruf aus dem Tokenstrom) ·
 * `methode` (`$o->name(`) · `definition` · `muster` (PCRE ueber die Sicht) ·
 * `eigen` (benannte Funktion in `zaehlen.php`, fuer alles, was ein Muster
 * nicht trifft).
 */

/* Die fuenf Kindtabellen eines Einsatzes (E-ZE-21). */
const ZH_KINDTABELLEN = 'mission_phases|resus_sessions|resus_events|mission_resources|mission_crew';

/* Die vierzehn Formatierer-Definitionen aus Konzept 1.4, Zeile „Formatierer".
 * WARUM EINE NAMENSLISTE UND KEIN MUSTER: Die vierzehn heissen `fmtTag`,
 * `wertKmSumme`, `durationHHMM` — es gibt kein gemeinsames Muster, das sie
 * trifft und `wertLesen()` in `suche.php` (liest ein Formularfeld) oder
 * `fmtDe1()` in `zeitraum.php` (allgemeine Kommastelle) auslaesst. Ein
 * Muster, das beide mitnimmt, misst etwas anderes als gefragt war.
 * PREIS, ausdruecklich: Diese Zeile haelt die vierzehn auf null; einen NEU
 * erfundenen Formatierer unter neuem Namen sieht sie nicht. Dafuer ist die
 * Lesearbeit in AP8 da, nicht dieses Werkzeug. */
const ZH_FORMATIERER = [
    'server/assets/missiontable.js' => ['fmtTag', 'fmtDur', 'fmtKm', 'fmtKmZahl'],
    'server/assets/export.js'       => ['durationHHMM', 'durationMinutes'],
    'server/index.php'              => ['fmtDay'],
    'server/einsatz.php'            => ['fmtDay', 'fmtKm', 'fmtDauer'],
    'server/zeitraum.php'           => ['fmtKmDe', 'wertKm', 'wertKmSumme', 'fmtTagKurz'],
];

return [

/* ---- AP1: Gegenproben zu Paket 1 und 3 aus Nr. 202 ---------------------- */

['kennung' => 'Z30', 'paket' => 'AP1',
 'beschreibung' => "Gegenprobe P1: INSERT INTO password_resets ausserhalb konto_lib.php",
 'grund' => 'P5b AP2 hat die vier Stellen auf eine gezogen; die Zeile haelt das.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/konto_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~INSERT\s+INTO\s+password_resets~i'],
 'start' => 0, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z31', 'paket' => 'AP1',
 'beschreibung' => "Gegenprobe P1: 'base_url' ausserhalb instanz_lib/install/config.example",
 'grund' => 'P5a AP5 hat die Verkettung von Hand durch app_url() ersetzt.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/instanz_lib.php', 'server/install.php', 'server/config.example.php'],
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]base_url[\'"]~'],
 'start' => 0, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z32', 'paket' => 'AP1',
 'beschreibung' => 'Gegenprobe P1: mail_rahmen( ausserhalb mail_lib.php, instanz_lib.php',
 'grund' => 'P5a AP5: sieben Versandstellen bauten den Mailrahmen selbst.',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/mail_lib.php', 'server/instanz_lib.php'],
 'regel' => ['art' => 'aufruf', 'namen' => ['mail_rahmen']],
 'start' => 0, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z33', 'paket' => 'AP1',
 'beschreibung' => 'Gegenprobe P3: usleep( ausserhalb ratelimit_lib.php',
 'grund' => 'P5a: sieben inline verzoegerte Antworten wurden rate_gleiche_dauer().',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/ratelimit_lib.php'],
 'regel' => ['art' => 'aufruf', 'namen' => ['usleep']],
 'start' => 0, 'decke_jetzt' => 0, 'decke_ziel' => 0],

/* ---- AP2: Konfiguration und Sitzung ------------------------------------- */

['kennung' => 'Z01', 'paket' => 'AP2',
 'beschreibung' => 'session_start( Aufrufe',
 'grund' => 'Nach AP2 startet die Sitzung an genau einer Stelle: sitzung_starten() '
          . 'in sitzung_lib.php (E-ZE-12). Jeder weitere Aufruf ist ein Befund.',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'aufruf', 'namen' => ['session_start']],
 'start' => 9, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z02', 'paket' => 'AP2',
 'beschreibung' => 'sitzung_ablage( Aufrufe',
 'grund' => 'Schritt 16 ruft sie in db.php und install.php; mit AP2 ruft sie '
          . 'allein sitzung_starten() (E-ZE-06).',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'aufruf', 'namen' => ['sitzung_ablage']],
 'start' => 2, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z03', 'paket' => 'AP2',
 'beschreibung' => 'config.php lesend einbinden',
 'grund' => 'Nach AP2 liest konfig_lib.php die Datei, sonst niemand (E-ZE-02, -14).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'eigen', 'name' => 'konfig_lesestelle'],
 'start' => 7, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z04', 'paket' => 'AP2',
 'beschreibung' => "\$CFG / global \$CFG / \$GLOBALS['CFG']",
 'grund' => 'Die globale $CFG ist mit AP2 entfallen (E-ZE-14). '
          . 'BEREICH IST server/ UND tools/, und das ist der Kern dieser Zeile: '
          . 'Die Globale gibt es NIRGENDS mehr. Bis zum 21.09.2026 mass sie nur '
          . 'server/, meldete 46 -> 0 — und fuenf Pruefwerkzeuge lasen oder '
          . 'setzten dieselbe Globale weiter. 30 ihrer Erwartungen standen '
          . 'danach still auf „nicht erfuellt" (Backlog Nr. 257).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php_und_tools',
 'ausser' => ['tools/zaehlung/register.php', 'tools/sandbox/konfig_stellen.php'],
 'regel' => ['art' => 'muster', 'muster' => '~\$CFG\b|\$GLOBALS\[\s*[\'"]CFG[\'"]\s*\]~'],
 'start' => 46, 'decke_jetzt' => 0, 'decke_ziel' => 0],

/* ---- AP3: API-Eingang und Flash ----------------------------------------- */

['kennung' => 'Z05', 'paket' => 'AP3',
 'beschreibung' => 'php://input unter api/',
 'grund' => 'api_rumpf() liest den Rumpf (E-ZE-15, in AP3 auf zwei Funktionen '
          . 'aufgeteilt). Ausnahme: api/csp_bericht.php (anderer Inhaltstyp, kein '
          . 'eigener Aufrufer).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'api', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~php://input~'],
 'start' => 12, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z06', 'paket' => 'AP3',
 'beschreibung' => "'error' => 'method' unter api/",
 'grund' => 'Die Methodenpruefung wandert in api_methode(); ausserhalb bleibt keine. '
          . 'api/csp_bericht.php prueft weiter selbst, antwortet aber mit 204 statt '
          . 'mit dem Schluessel method und wird deshalb hier nicht gezaehlt.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'api', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]error[\'"]\s*=>\s*[\'"]method[\'"]~'],
 'start' => 17, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z07', 'paket' => 'AP3',
 'beschreibung' => "„Rumpf ist kein JSON-Objekt\" von Hand (payload|format) unter api/",
 'grund' => 'F-ZE-5: der Eingang antwortet einheitlich mit format; payload '
          . 'verschwindet aus api/. Die Geraete-Endpunkte behalten payload (JSON-Vertrag).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'api', 'ausser' => [],
 'regel' => ['art' => 'eigen', 'name' => 'rumpf_kein_objekt'],
 'start' => 11, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z08', 'paket' => 'AP3',
 'beschreibung' => 'post_max_size-Hinweis unter api/',
 'grund' => 'Drei Fassungen desselben Satzes; seit AP3 steht er einmal in '
          . 'api_rumpf() (db.php). Unter api/ bleibt damit KEINE — das Konzept '
          . 'hatte 1 erwartet, gemessen sind 0.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'api', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~post_max_size~'],
 'start' => 3, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z09', 'paket' => 'AP3',
 'beschreibung' => "\$_SESSION['flash…'] ausserhalb session_lib.php",
 'grund' => 'flash_setzen()/flash_holen() in session_lib.php (E-ZE-16). Der '
          . 'Sitzungsschluessel heisst seit AP3 einheitlich flash.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/session_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~\$_SESSION\[\s*[\'"]flash~'],
 'start' => 22, 'decke_jetzt' => 0, 'decke_ziel' => 0],

/* ---- AP4: Datenzugriff klein -------------------------------------------- */

['kennung' => 'Z10', 'paket' => 'AP4',
 'beschreibung' => 'app_state-SQL ausserhalb db.php und migration_lib.php',
 'grund' => 'Sechs Helfer in db.php (E-ZE-17). Drei Ausnahmen: migration_lib.php '
          . '(E-ZE-04), job_aufraeumen_schritte() in jobs_lib.php (DELETE mit Verbund '
          . 'auf users, kein Schluesselzugriff) und jobs.php — GERAETEVERTRAG: Der '
          . 'Endpunkt antwortet bei unerreichbarer app_state mit 500 "datenbank"; der '
          . 'Helfer faengt und liefert null, was daraus ein "Token falsch" machte '
          . '(AP4-c).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/db.php', 'server/migration_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~\b(FROM|INTO|UPDATE|JOIN)\s+app_state\b~i'],
 'start' => 27, 'decke_jetzt' => 2, 'decke_ziel' => 2],

['kennung' => 'Z11', 'paket' => 'AP4',
 'beschreibung' => "Literal 'manual-' ausserhalb db.php",
 'grund' => 'geraet_virtuell_kennung(), geraet_virtuell_sicherstellen(), '
          . 'geraete_echt_sql(), GERAET_VIRTUELL_MUSTER in db.php (E-ZE-18).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/db.php'],
 'regel' => ['art' => 'muster', 'muster' => '~manual-~'],
 'start' => 7, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z12', 'paket' => 'AP4',
 'beschreibung' => 'Einsatz per ID mit Besitzpruefung per SELECT ausserhalb einsatz_lib.php',
 'grund' => 'einsatz_laden() (E-ZE-19). Zwei Ausnahmen, namentlich: '
          . 'trash_restore_mission() in trash_lib.php (Verbund mit days) und die '
          . 'in der Import-Schleife wiederverwendete Anweisung in '
          . 'api/import_commit.php (bis 3000 Ausfuehrungen, AP4-b). '
          . 'DIE REGEL VERLANGT SEIT AP4 EIN SELECT: Der Startwert 13 aus AP1 '
          . 'enthielt eine DELETE-Anweisung (api/schneiden.php), also keinen '
          . 'Ladevorgang. Der berichtigte Startwert ist 12.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/einsatz_lib.php'],
 'regel' => ['art' => 'muster', 'muster' =>
    '~SELECT\s[^;]{0,300}?FROM\s+missions\b[^;]{0,400}?\bid\s*=\s*\?[^;]{0,200}?\buser_id\s*=\s*\?'
  . '|SELECT\s[^;]{0,300}?FROM\s+missions\b[^;]{0,400}?\buser_id\s*=\s*\?[^;]{0,200}?\bid\s*=\s*\?~is'],
 'start' => 12, 'decke_jetzt' => 2, 'decke_ziel' => 2],

['kennung' => 'Z13', 'paket' => 'AP4',
 'beschreibung' => 'Rollenvergleich von Hand ausserhalb db.php',
 'grund' => 'rolle_darf_verwalten()/rolle_ist_betreiberin() bestehen; 10c AP4 '
          . 'setzt rolle_darf_support() daneben. Kein Handvergleich mehr.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/db.php'],
 'regel' => ['art' => 'muster', 'muster' =>
    '~(===|!==|==|!=)\s*[\'"](admin|betreiberin|user)[\'"]'
  . '|[\'"](admin|betreiberin|user)[\'"]\s*(===|!==|==|!=)~'],
 'start' => 4, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z14', 'paket' => 'AP4',
 'beschreibung' => 'information_schema ausserhalb migration_lib.php und db.php',
 'grund' => 'db_hat_tabelle()/-spalte()/-index() sind seit AP4 oeffentlich (E-ZE-04). '
          . 'Was bleibt, fragt KEINE Existenz: komplett_lib.php 2 (Spaltenliste mit '
          . 'Typen, Fremdschluessel), speicher_lib.php 1 (Groesse in Bytes) und '
          . 'nachbearbeitung_lib.php 2 (is_nullable). Fuer is_nullable entsteht KEIN '
          . 'vierter Helfer: Beide Stellen liegen in EINER Datei, und nb_moeglich() '
          . 'fragt bewusst vier Tabellen in einer Abfrage (1,071 ms gegen 0,355 ms je '
          . 'Seitenaufbau) — ein Einzelhelfer naehme das wieder auseinander. '
          . 'Auftraggeber, 21.09.2026.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/migration_lib.php', 'server/db.php'],
 'regel' => ['art' => 'muster', 'muster' => '~information_schema~i'],
 'start' => 9, 'decke_jetzt' => 5, 'decke_ziel' => 5],

['kennung' => 'Z15', 'paket' => 'AP4',
 'beschreibung' => 'information_schema in migration_lib.php',
 'grund' => 'Gelaufene Migrationen werden nicht umgebaut (E-ZE-04) — die Zahl darf '
          . 'aber nicht steigen. Neue Migrationen fragen ueber db_hat_*(). Seit AP4 '
          . 'sind es 54 statt 57: Die drei privaten _hat_* reichen nur noch an db.php '
          . 'durch und nennen information_schema nicht mehr. Erledigt sich mit dem '
          . 'neuen Migrationsregister in P8 (R66).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'nur' => 'server/migration_lib.php',
 'regel' => ['art' => 'muster', 'muster' => '~information_schema~i'],
 'start' => 57, 'decke_jetzt' => 54, 'decke_ziel' => 54],

/* ---- AP5: Transaktion und Kindtabellen ---------------------------------- */

['kennung' => 'Z16', 'paket' => 'AP5',
 'beschreibung' => 'beginTransaction( ausserhalb transaktion_lib.php',
 'grund' => 'db_transaktion() in transaktion_lib.php (E-ZE-20; bis Web 20.37.2 in '
          . 'db.php, umgezogen wegen Nr. 288). NEUN namentliche Ausnahmen, '
          . 'freigegeben vom Auftraggeber am 22.09.2026 (H-ZE-4 ausgesetzt; das '
          . 'Konzept liess acht zu). DREI wegen GROESSE oder Vertrag: ingest.php '
          . '(Geraetevertrag, Deadlock-Behandlung Nr. 210 in Schritt 18; vorab '
          . 'gesetzt), backup_lib.php (Rumpf 1153 Zeilen, 145 Variablen) und '
          . 'api/import_commit.php (542 Zeilen, 78 Variablen) — eine use-Liste '
          . 'mit 145 Eintraegen ist kein Zentralisieren, sondern ein Rewrite. '
          . 'SECHS wegen BAUFORM: pair.php (Geraetevertrag; commit UND rollBack '
          . 'mehrfach im try, Antwort mitten im Rahmen), jobs_lib.php '
          . '(spur_ausduennen_eine: drei rollBack-return als REGULAERER Weg), '
          . 'diensttag_zusammenfuehren.php (ebenso) sowie api/day.php, '
          . 'api/kdf_upgrade.php und api/schneiden.php (rollBack + json_out im '
          . 'try). db_transaktion() setzt voraus, dass der Rumpf durchlaeuft '
          . 'ODER wirft; diese sechs rollen mittendrin zurueck und machen dann '
          . 'etwas anderes weiter.',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/transaktion_lib.php'],
 'regel' => ['art' => 'methode', 'namen' => ['beginTransaction']],
 'start' => 33, 'decke_jetzt' => 9, 'decke_ziel' => 9],

['kennung' => 'Z17', 'paket' => 'AP5',
 'beschreibung' => 'INSERT/DELETE auf Kindtabellen ausserhalb einsatz_lib.php, migration_lib.php',
 'grund' => 'Vier Datenzugriffsfunktionen einsatz_*_ersetzen() (E-ZE-21).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/migration_lib.php', 'server/einsatz_lib.php'],
 'regel' => ['art' => 'muster', 'muster' =>
    '~\b(INSERT\s+(?:IGNORE\s+)?INTO|DELETE\b[^;]{0,40}?FROM|REPLACE\s+INTO)\s+`?('
  . ZH_KINDTABELLEN . ')`?~i'],
 'start' => 30, 'decke_jetzt' => 30, 'decke_ziel' => 0],

/* ---- AP6: Spaltenregister missions -------------------------------------- */

['kennung' => 'Z18', 'paket' => 'AP6',
 'beschreibung' => 'Handlisten der missions-Spalten (>= 10 Spaltennamen dicht beieinander)',
 'grund' => 'mf_missions_register()/mf_spalten() erzeugen die neun SQL-Listen '
          . '(E-ZE-22). Drei Abbildungen bleiben — sie rechnen je Wert um und '
          . 'lassen sich nicht erzeugen; tools/spaltenregister/pruefen.php belegt, '
          . 'dass jede genau die Registerspalten ihres Zwecks fuehrt.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/migration_lib.php', 'server/mission_fields.php',
              'server/mission_fields_lib.php'],
 'regel' => ['art' => 'eigen', 'name' => 'missions_handliste'],
 'start' => 12, 'decke_jetzt' => 3, 'decke_ziel' => 3],

/* ---- AP7: Zeit und Zahl in PHP ------------------------------------------ */

['kennung' => 'Z19', 'paket' => 'AP7',
 'beschreibung' => 'edbak_groesse_text( Aufrufe',
 'grund' => 'Der R83-Beleg: neun fremde Dateien laden die Backup-Bibliothek nur '
          . 'dafuer. Heisst in format_lib.php groesse_text(); der alte Name entfaellt (E-ZE-23).',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'aufruf', 'namen' => ['edbak_groesse_text']],
 'start' => 42, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z20', 'paket' => 'AP7',
 'beschreibung' => "Bauten fuer relative Zeit von Hand ('vor …')",
 'grund' => 'status_alter() heisst zeit_relativ() und ist die Fassung, die gilt; '
          . 'die Kopie in betrieb_updates.php entfaellt (E-ZE-23, FF-3).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'zaehlt' => 'dateien',
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]vor ~'],
 'start' => 2, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z21', 'paket' => 'AP7',
 'beschreibung' => 'number_format( in deutscher Form ausserhalb format_lib.php',
 'grund' => 'zahl_text() (E-ZE-23). Die uebrigen number_format (andere Form, '
          . 'etwa Koordinaten und GPX) bleiben, wo sie sind.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => ['server/format_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~number_format\s*\([^;]{0,120}?,\s*[\'"],[\'"]\s*,\s*[\'"]\.[\'"]~'],
 'start' => 27, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z22', 'paket' => 'AP7',
 'beschreibung' => 'Byte-Division fuer die Anzeige',
 'grund' => 'Byte-ANZEIGEN laufen ueber groesse_text(); Byte-GRENZWERTE bleiben, '
          . 'wo sie sind (E-ZE-23). FUENF NAMENTLICH (AP7): betrieb_server.php 125 '
          . '(Vergleichswert, nie ausgegeben) und 937/950/957 (FORMULARWERTE mit Punkt '
          . 'als Dezimaltrenner, die derselbe POST-Zweig zurueckliest — eine Umstellung '
          . 'machte das Formular unabschickbar); gpx_lib.php 375 (sprintf %.1f mit PUNKT, '
          . 'groesse_text() schriebe ein Komma — sichtbare Textaenderung).',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => ['server/format_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~/\s*\(?\s*1024|/\s*1048576|/\s*1073741824~'],
 'start' => 18, 'decke_jetzt' => 5, 'decke_ziel' => 18],

['kennung' => 'Z23', 'paket' => 'AP7',
 'beschreibung' => 'Anteil von Hand (Teil * 100 / Ganzes)',
 'grund' => 'prozent_wert() und prozent_text() in format_lib.php (E-ZE-23). ZWEI '
          . 'NAMENTLICH (AP7): betrieb_server.php 572 und 585 rechnen eine CSS-LAENGE, '
          . 'keinen Prozenttext — sie runden bewusst gar nicht, weil der Strich sonst '
          . 'um bis zu einen Prozentpunkt wanderte.',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => ['server/format_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~\*\s*100\s*/~'],
 'start' => 10, 'decke_jetzt' => 2, 'decke_ziel' => 0],

['kennung' => 'Z24', 'paket' => 'AP7',
 'beschreibung' => "gmdate('Y-m-d\\TH:i:s\\Z' — ISO-UTC-Marke schreiben",
 'grund' => 'iso_utc() in format_lib.php (E-ZE-23). Die eine erlaubte Stelle ist die '
          . 'Funktion selbst; dazu EINE NAMENTLICH (AP7): wartung_lib.php 215. Deren '
          . 'Dateikopf sagt als Eigenschaft 2 zu, NICHTS zu laden — sie traegt den '
          . 'Wartungsmodus gerade dann, wenn der Rest ersetzt wird.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~gmdate\s*\(\s*[\'"]Y-m-d\\\\TH:i:s\\\\Z[\'"]~'],
 'start' => 20, 'decke_jetzt' => 2, 'decke_ziel' => 1],

['kennung' => 'Z25', 'paket' => 'AP7',
 'beschreibung' => "str_replace(['T','Z'] … — ISO-UTC-Marke lesen",
 'grund' => 'iso_utc_lesen() in format_lib.php (E-ZE-23). Die eine erlaubte Stelle ist '
          . 'die Funktion selbst; dazu EINE NAMENTLICH (AP7): wartung_lib.php 552, '
          . 'dieselbe Ladezusage wie bei Z24.',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~str_replace\s*\(\s*\[\s*[\'"]T[\'"]\s*,\s*[\'"]Z[\'"]~'],
 'start' => 9, 'decke_jetzt' => 2, 'decke_ziel' => 1],

['kennung' => 'Z26', 'paket' => 'AP7',
 'beschreibung' => 'Datumsformat-Literale mit d.m. ausserhalb format_lib.php',
 'grund' => 'datum_text() und datum_zeit_text($utc, $trenner) (F-ZE-3). Schritt 15 '
          . 'benennt die Varianten und aendert keinen Pixel; 10c AP9 entscheidet. '
          . 'DREI NAMENTLICH (AP7): admin_installation.php 363 und rechtstexte_lib.php '
          . '482 formatieren einen Unix-Zeitstempel bewusst OHNE Zonenumrechnung — eine '
          . 'Umstellung waere eine weitere sichtbare Ausnahme; wartung_lib.php 554 '
          . 'darf nichts laden (siehe Z24).',
 'sicht' => 'php_mit_zeichenketten', 'bereich' => 'php', 'ausser' => ['server/format_lib.php'],
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]d\.m\.[^\'"]*[\'"]~'],
 'start' => 67, 'decke_jetzt' => 3, 'decke_ziel' => 0],

['kennung' => 'Z27', 'paket' => 'AP7',
 'beschreibung' => "date('…') ohne Zeitstempel ausserhalb install.php",
 'grund' => 'FF-1: „heute\" haengt an der php.ini. heute_lokal() rechnet in '
          . 'app.timezone (F-ZE-1). install.php bleibt — dort gibt es keine config.php.',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php',
 'ausser' => ['server/install.php'],
 'regel' => ['art' => 'eigen', 'name' => 'date_ohne_zeitstempel'],
 'start' => 4, 'decke_jetzt' => 0, 'decke_ziel' => 0],

/* ---- AP8: JavaScript ---------------------------------------------------- */

['kennung' => 'Z28', 'paket' => 'AP8',
 'beschreibung' => "JS: Literal 'X-CSRF'",
 'grund' => 'EdApi.postJson() haengt das Token selbst an (assets/api.js). '
          . 'Die verbleibende 1 IST diese Zentrale — der Zaehler nimmt sie '
          . 'nicht aus, und das ist richtig so: Eine Stelle, an der das '
          . 'Literal steht, soll es geben, und sie soll gezaehlt werden.',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]X-CSRF[\'"]~'],
 'start' => 15, 'decke_jetzt' => 1, 'decke_ziel' => 1],

['kennung' => 'Z29', 'paket' => 'AP8',
 'beschreibung' => 'JS: Formularfeld csrf von Hand',
 'grund' => 'FF-4: 10b hat einen zweiten CSRF-Transport gebaut. EdApi.postForm() '
          . 'haengt ihn an. ZWEI BLEIBEN, nicht null — Entscheidung des '
          . 'Auftraggebers vom 22.09.2026 (ehrliche Zahl statt runder Null). '
          . 'Die eine ist die Zentrale selbst. Die andere ist rueckfrage.js: '
          . 'KEIN Formularfeld, sondern eine Parameterweitergabe an '
          . 'EdSchluessel.erneuern() — ein Fehlalarm des Musters. Sie faellt '
          . 'nur ueber eine Schnittstellenaenderung, und die bleibt.',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~[\'"]csrf[\'"]\s*[,:]|(?<![\w.$])csrf\s*:~'],
 'start' => 5, 'decke_jetzt' => 2, 'decke_ziel' => 0],

['kennung' => 'Z34', 'paket' => 'AP8',
 'beschreibung' => 'JS: Formatierer-Definitionen ausserhalb assets/format.js',
 'grund' => 'EdFormat in assets/format.js. FUENF BLEIBEN, nicht null, und '
          . 'keine davon ist ein zweiter Rechenweg: Es sind duenne '
          . 'Weiterleitungen, die je einen anderen LEERWERT binden — '
          . 'kein Ende, den Leerstring, einen Gedankenstrich als Markup, '
          . 'null. Der Leerwert ist bei EdFormat ein Parameter (das war '
          . 'die Lehre aus AP7), und diese fuenf sind seine benannten '
          . 'Vorgaben je Zusammenhang. Sie aufzuloesen hiesse, den '
          . 'Leerwert an fuenfzehn Aufrufstellen zu wiederholen statt an '
          . 'fuenf — und drei von ihnen stehen im Export-Objekt von '
          . 'EdMissionTable, das zeitraum.php als Alias nimmt. '
          . 'DIE ZEILE MISST UEBER EINE NAMENSLISTE und sieht deshalb '
          . 'keinen neu erfundenen Formatierer. Zwei hat erst eine '
          . 'Gegenprobe ueber das Muster der RECHNUNG gefunden: '
          . 'luftlinie.js (umgestellt, 57 143 Werte zeichengleich) und '
          . 'schneiden.js mit einer DRITTEN Dauer-Schreibweise '
          . '(Backlog Nr. 273, nicht umgestellt — sichtbare Aenderung).',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline',
 'ausser' => ['server/assets/format.js'],
 'regel' => ['art' => 'eigen', 'name' => 'js_formatierer'],
 'start' => 14, 'decke_jetzt' => 5, 'decke_ziel' => 0],

['kennung' => 'Z35', 'paket' => 'AP8',
 'beschreibung' => 'JS: Seiten mit eigener L.map(-Praeambel',
 'grund' => 'EdKarte.anlegen() in assets/map_layers.js — dort liegt L.tileLayer schon. '
          . 'assets/ortswahl.js ist ein Modul, keine Seite, und bleibt draussen.',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline',
 'ausser' => ['server/assets'], 'zaehlt' => 'dateien',
 'regel' => ['art' => 'muster', 'muster' => '~\bL\.map\s*\(~'],
 'start' => 4, 'decke_jetzt' => 0, 'decke_ziel' => 0],

['kennung' => 'Z36', 'paket' => 'AP8',
 'beschreibung' => 'JS: EdPat.entschluessleListe( in Seiten',
 'grund' => 'Ein Rahmen EdPat.listeLaden() fuer die drei ANZEIGESEITEN. Die '
          . 'beiden Module (export.js, import_ui.js) rufen weiter unmittelbar. '
          . 'EINE BLEIBT, nicht null — Entscheidung des Auftraggebers vom '
          . '22.09.2026. einstellungen.php teilt nur den AUFRUF, nicht den '
          . 'Rahmen: kein Banner, kein zeigeUnlesbar, der Schluessel kommt '
          . 'von aussen, und die Liste ist ein FENSTER zu 250 aus einem '
          . 'Bestand von tausenden. Daran haengt es: hinweisUnlesbar() wertet '
          . 'die GANZE Liste aus und sagt "Keiner der Eintraege liess sich '
          . 'oeffnen" — ueber ein Fenster gesagt waere das eine '
          . 'Falschaussage.',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline',
 'ausser' => ['server/assets'],
 'regel' => ['art' => 'muster', 'muster' => '~EdPat\.entschluessleListe\s*\(~'],
 'start' => 4, 'decke_jetzt' => 1, 'decke_ziel' => 0],

['kennung' => 'Z37', 'paket' => 'AP8',
 'beschreibung' => 'JS: Meldungs-Markup von Hand',
 'grund' => 'EdHtml.meldung(ton, text) in assets/html.js. Gemessen in der Sicht '
          . 'js_und_inline — eine breite Suche ueber den Quelltext zaehlt das '
          . 'PHP-Markup mit (35 Erwaehnungen in 15 Dateien) und misst etwas anderes. '
          . 'VIER BLEIBEN. Zwei davon sind die Zentrale selbst (html.js, zwei '
          . 'Treffer in einer Funktion: Tonklasse und Aktionszeile). Die dritte '
          . 'ist schneiden.js, eine LEERE Huelle mit data-vorher als Anker, die '
          . 'spaeter per textContent befuellt wird; sie traegt kein Symbol, und '
          . 'eines einzusetzen waere eine sichtbare Aenderung im Schnittblock '
          . '(Backlog Nr. 271). Die vierte ist unlock.js und gar keine Meldung: '
          . 'ein p-Element mit der Klasse meldung, ohne Tonklasse, ohne Symbol, '
          . 'ohne role — ein Fehlalarm des Musters und zugleich ein Missbrauch '
          . 'der Klasse (Backlog Nr. 272).',
 'sicht' => 'js_und_inline', 'bereich' => 'js_und_inline', 'ausser' => [],
 'regel' => ['art' => 'muster', 'muster' => '~class\s*=\s*[\'"\\\\]{0,3}meldung~'],
 'start' => 8, 'decke_jetzt' => 4, 'decke_ziel' => 0],

/* ---- Uebergabe an 10c --------------------------------------------------- */

['kennung' => 'Z38', 'paket' => '10c AP3',
 'beschreibung' => 'Uebergabe 10c: error_log( Aufrufe',
 'grund' => 'Schritt 15 stellt KEINEN error_log()-Aufruf um (E-ZE-05); wer Code '
          . 'verschiebt, verschiebt die Zeile unveraendert mit. Der Log-Helfer '
          . 'kommt in 10c AP3, das die Decke dann auf 2 setzt (Nr. 248).',
 'sicht' => 'php_ohne_zeichenketten', 'bereich' => 'php', 'ausser' => [],
 'regel' => ['art' => 'aufruf', 'namen' => ['error_log']],
 'start' => 77, 'decke_jetzt' => 77, 'decke_ziel' => 77],

];
