<?php
declare(strict_types=1);
/**
 * Abgeleitete Sichten auf den zentralen Feldkatalog (mission_fields.php).
 *
 * Der Katalog selbst bleibt eine reine Datendatei: Er wird an mehreren Stellen
 * mit `require` eingelesen (einsatz_form.php, api/mission.php, backup_lib.php)
 * und darf deshalb keine Funktion definieren — beim zweiten Einlesen waere sie
 * doppelt deklariert. Alles, was aus dem Katalog ABGELEITET wird, steht hier.
 *
 * Diese Datei wird mit `require_once` eingebunden.
 */

/**
 * Spalten der Tagestabelle (index.php), abgeleitet aus dem Schluessel
 * 'day_col' des Feldkatalogs.
 *
 * Bis Web 5.4.0 waren diese Spalten an drei Stellen hartkodiert — im SELECT
 * und im JSON-Aufbau von api/day.php sowie im Tabellenkopf und im
 * Zeilenaufbau von index.php. 'day_col' war dadurch reine Dokumentation: Die
 * Spalte „abw. Crew" stand seit Web 2.6.0 im Katalog und erschien trotzdem
 * nicht. Seither ist DIESE Funktion die einzige Stelle, die den Katalog fuer
 * die Tagestabelle auswertet; ein neuer Eintrag mit 'day_col' erscheint ohne
 * weitere Codeaenderung (Backlog Nr. 10).
 *
 * Die Probe aufs Exempel lief in Web 5.10.0 rueckwaerts: „abw. Crew" wurde
 * wieder abbestellt, und dafuer genuegte es, zwei Schluessel im Katalog zu
 * streichen — Tabellenkopf, Zeilenaufbau, Sortierung und der SELECT in
 * api/day.php zogen von selbst nach.
 *
 * Unterfelder werden mit durchsucht: Ein Haken unter einem Haken darf
 * ebenfalls eine Spalte bekommen. Die Reihenfolge folgt dem Katalog.
 *
 * Rueckgabe je Spalte:
 *   'col'    Spaltenname in `missions`. Zugleich der Schluessel, unter dem
 *            api/day.php den Wert ausliefert.
 *   'art'    'check'  Haken   -> Wahrheitswert im JSON, Anzeige als ✓
 *            'text'   Textart -> Zeichenkette oder null
 *   'label'  Spaltentitel: 'day_label', ersatzweise 'label'. Darf Auszeichnung
 *            enthalten (z. B. `<br>`) und wird deshalb UNMASKIERT ausgegeben.
 *            Der Wert stammt aus dem Katalog, nie aus einer Eingabe.
 *   'klasse' CSS-Klasse der Spalte, `c-dc-<spalte>` — der Anker fuer eine
 *            Breiten- oder Sonderregel in style.css, wenn eine Spalte eine
 *            braucht. Die Ausrichtung kommt nicht von hier: Hakenspalten
 *            (`art` = 'check') erhalten im Markup zusaetzlich `haken-spalte`.
 *   'cap'    Faehigkeit aus 'cap_gate', sonst ''. Seit Schritt 15 AP9.
 *
 * WOZU 'cap' DA IST -- und wozu nicht. Diese Funktion FILTERT NICHT. Sie
 * kennt keinen Diensttag (sie nimmt keinen Parameter und cacht statisch),
 * und sie soll auch keinen kennen: Die Tagesuebersicht wechselt den Tag
 * OHNE Seitenwechsel, die Entscheidung faellt also ohnehin im Browser.
 * Geliefert wird die Bedingung, angewendet wird sie dort.
 *
 * Bis Schritt 15 AP9 fiel die Bedingung unterwegs heraus: 'cap_gate' stand
 * im Katalog, `mf_gates_erfuellt()` wertete es aus -- aber nur fuer das
 * Einsatzformular. Die Tagestabelle bekam es nie zu sehen, und so trugen
 * ALLE 69 Diensttage des Referenzbestands die Windenspalte, auch die ohne
 * Winde. Die Regel, die jetzt gilt, steht in E-ZE-31: Anzeige nach
 * Betriebsart UND Faehigkeit, Bearbeitung nach Faehigkeit allein.
 *
 * @return list<array{col:string,art:string,label:string,klasse:string,cap:string}>
 */
function mf_tagesspalten(): array
{
    static $spalten = null;
    if ($spalten !== null) { return $spalten; }

    $gefunden = [];
    $sammle = static function (array $felder) use (&$sammle, &$gefunden): void {
        foreach ($felder as $col => $f) {
            $dc = $f['day_col'] ?? null;
            if ($dc !== null && $dc !== false) {
                /* Der Name landet unmaskiert in einem SELECT und in einer
                 * CSS-Klasse. Er stammt aus einer Datei des Projekts, nicht
                 * aus einer Eingabe — die Pruefung faengt darum keinen
                 * Angriff ab, sondern einen Tippfehler, der sonst als
                 * SQL-Fehler ohne erkennbaren Bezug auftauchen wuerde. */
                if (!preg_match('/^[a-z][a-z0-9_]*$/', (string)$col)) {
                    throw new RuntimeException(
                        "mission_fields.php: '$col' hat 'day_col', ist aber kein "
                        . 'zulaessiger Spaltenname ([a-z][a-z0-9_]*).');
                }
                /* Ein Feld mit 'store' ist KEINE Spalte in `missions` (siehe
                 * mission_fields.php). Eine Tagesspalte daraus liefe in einen
                 * SQL-Fehler ohne erkennbaren Bezug — dieselbe Ueberlegung wie
                 * bei der Namenspruefung darueber, nur fuer den Fehler, der
                 * seit der Normalisierung der Besatzung moeglich ist. */
                if (isset($f['store'])) {
                    throw new RuntimeException(
                        "mission_fields.php: '$col' hat 'day_col' und 'store'. "
                        . 'Ein Feld, das nicht in `missions` liegt, kann keine '
                        . 'Spalte der Tagestabelle sein.');
                }
                $gefunden[] = [
                    'col'    => (string)$col,
                    'art'    => $dc === 'check' ? 'check' : 'text',
                    'label'  => (string)($f['day_label'] ?? $f['label'] ?? $col),
                    'klasse' => 'c-dc-' . $col,
                    'cap'    => (string)($f['cap_gate'] ?? ''),
                ];
            }
            if (!empty($f['children']) && is_array($f['children'])) {
                $sammle($f['children']);
            }
        }
    };
    $sammle(require __DIR__ . '/mission_fields.php');

    return $spalten = $gefunden;
}

/**
 * Besatzungsfelder des Katalogs: Feldname => Rollenkennung.
 *
 * Also `['crew_p1' => 'p1', …]`, in Katalogreihenfolge. Abgeleitet aus dem
 * Schluessel 'store' => 'crew' — nicht aus einer zweiten Liste, die mit
 * CREW_ROLES auseinanderlaufen koennte.
 *
 * Gebraucht an vier Stellen, die alle dasselbe wissen muessen: Formular
 * (Lesen und Schreiben von `mission_crew`), api/mission.php (effektive
 * Besatzung), Export und Backup.
 *
 * @return array<string,string>
 */
function mf_crew_felder(): array
{
    static $felder = null;
    if ($felder !== null) { return $felder; }

    $gefunden = [];
    $sammle = static function (array $felder) use (&$sammle, &$gefunden): void {
        foreach ($felder as $col => $f) {
            if (($f['store'] ?? null) === 'crew') {
                $gefunden[(string)$col] = (string)($f['role_code'] ?? substr((string)$col, 5));
            }
            if (!empty($f['children']) && is_array($f['children'])) { $sammle($f['children']); }
        }
    };
    $sammle(require __DIR__ . '/mission_fields.php');

    return $felder = $gefunden;
}

/**
 * Verschluesselte Felder des Katalogs: Feldname => ['blob' => Schluessel im
 * `pat_blob`, 'label' => Beschriftung].
 *
 * Abgeleitet aus 'store' => 'pat' (S9/AP7), genau wie mf_crew_felder() aus
 * 'store' => 'crew'. Der Blobschluessel ist der Feldname, sofern nicht
 * 'blob_key' etwas anderes sagt — die aelteren Blobschluessel des Formulars
 * (`last`, `first`, `dob`, `dx`, `loc`, …) stehen NICHT im Katalog: Sie
 * gehoeren zu handgeschriebenem Markup, das keinen Katalogeintrag hat. Diese
 * Funktion nennt nur die Felder, die BEIDES sind — Katalogfeld und Blobinhalt.
 *
 * DIE BESCHRIFTUNG STEHT MIT DABEI, weil die Anzeige sie sonst nirgends mehr
 * bekaeme: `api/mission.php` liefert nur Spalten, und ein Blobfeld ist keine.
 * Zwei Funktionen fuer dieselbe Feldmenge waeren der Anfang davon, dass sie
 * auseinanderlaufen.
 *
 * Gebraucht, wo eine Liste der Blobfelder gebraucht wird, ohne sie ein zweites
 * Mal zu fuehren: Formular (Rendern und Fuellen), Anzeige, Export, Sicherung,
 * Import.
 *
 * @return array<string,array{blob:string,label:string}>
 */
function mf_pat_felder(): array
{
    static $felder = null;
    if ($felder !== null) { return $felder; }

    $gefunden = [];
    $sammle = static function (array $felder) use (&$sammle, &$gefunden): void {
        foreach ($felder as $col => $f) {
            if (($f['store'] ?? null) === 'pat') {
                $gefunden[(string)$col] = [
                    'blob'  => (string)($f['blob_key'] ?? $col),
                    'label' => (string)($f['label'] ?? $col),
                ];
            }
            if (!empty($f['children']) && is_array($f['children'])) { $sammle($f['children']); }
        }
    };
    $sammle(require __DIR__ . '/mission_fields.php');

    return $felder = $gefunden;
}

/**
 * Ist dieses Feld eine Spalte in `missions`?
 *
 * Zwei Feldarten sind es nicht: 'resources' (eigene Zeilen in
 * `mission_resources`) und alles mit 'store' — seit Web 6.0.0 die Besatzung in
 * `mission_crew` ('crew'), seit S9/AP7 die Notizen im verschluesselten
 * `pat_blob` ('pat'). Beide duerfen nicht in ein SELECT, INSERT oder UPDATE
 * auf `missions` geraten; bei 'pat' waere es zusaetzlich ein Bruch der
 * Verschluesselungszusage (CLAUDE.md 4).
 *
 * Ein Ortsfeld ('loc', seit Web 6.1.0) IST eine Spalte — es traegt die
 * Bezeichnung. Seine beiden Koordinatenspalten stehen daneben und kommen aus
 * mf_ort_spalten(); wer alle Spalten eines Feldes braucht, fragt BEIDE
 * Funktionen.
 *
 * Die Pruefung steht hier und nicht in jedem Aufrufer, weil sie sonst beim
 * naechsten Sonderfall an fuenf Stellen nachgezogen werden muesste.
 */
function mf_ist_spalte(array $f): bool
{
    return !isset($f['store']) && ($f['type'] ?? 'text') !== 'resources';
}

/**
 * Koordinatenspalten eines Ortsfeldes: ['lat' => …, 'lon' => …].
 *
 * Leer bei jedem anderen Feldtyp. Die Namen stehen im Katalog ('lat_col',
 * 'lon_col') statt aus dem Feldnamen abgeleitet zu werden: `transport_dest`
 * liegt in `dest_lat`/`dest_lon`, und eine Ableitung ueber ein Namensmuster
 * waere geraten. Sie landen unmaskiert in einem UPDATE, deshalb dieselbe
 * Namenspruefung wie bei den Tagesspalten — sie faengt einen Tippfehler ab,
 * der sonst als SQL-Fehler ohne erkennbaren Bezug auftauchen wuerde.
 *
 * @return array{lat?:string,lon?:string}
 */
function mf_ort_spalten(array $f): array
{
    if (($f['type'] ?? 'text') !== 'loc') { return []; }
    $raus = [];
    foreach (['lat' => 'lat_col', 'lon' => 'lon_col'] as $k => $schluessel) {
        $col = (string)($f[$schluessel] ?? '');
        if ($col === '') { continue; }
        if (!preg_match('/^[a-z][a-z0-9_]*$/', $col)) {
            throw new RuntimeException(
                "mission_fields.php: '$col' ist kein zulaessiger Spaltenname "
                . '([a-z][a-z0-9_]*).');
        }
        $raus[$k] = $col;
    }
    return $raus;
}

/**
 * Optionen eines Auswahlfeldes als `wert => beschriftung`.
 *
 * DER GESPEICHERTE WERT IST NICHT IMMER DIE BESCHRIFTUNG (Web 6.1.0). Bis dahin
 * war er es immer: 'options' war eine einfache Liste, und was dort stand, ging
 * genau so in die Spalte. Mit der Transportart geht das nicht mehr auf — die
 * Spalte ist ein `ENUM('air','ground','ambulant')`, angezeigt gehoert „Luft",
 * „Boden", „Ambulant". Ein Katalog, der die Beschriftung speichert, haette dort
 * eine abgeschnittene Zelle erzeugt (MySQL-Warnung 1265), und zwar still.
 *
 * Beide Schreibweisen sind deshalb zulaessig, und diese Funktion ist die
 * einzige Stelle, die sie unterscheidet:
 *
 *   ['0','1','2']                         Wert = Beschriftung (wie bisher)
 *   ['air' => 'Luft', 'ground' => 'Boden'] Wert links, Beschriftung rechts
 *
 * Auch die Listen aus 'options_src' (Stammdaten) laufen hier durch — sie sind
 * Listen, und fuer sie aendert sich nichts.
 *
 * @param list<string>|array<string,string> $opts
 * @return array<string,string>
 */
function mf_optionen(array $opts): array
{
    if (array_is_list($opts)) {
        $raus = [];
        foreach ($opts as $o) { $raus[(string)$o] = (string)$o; }
        return $raus;
    }
    return array_map('strval', $opts);
}

/**
 * Wertabhaengiges Unterfeld: Soll es bei diesem Elternwert erscheinen? (E17)
 *
 * `'show_if' => ['field' => '<elternspalte>', 'not_in' => ['<wert>', …]]`
 *
 * Bis Web 6.0.0 gab es bedingte Unterfelder ausschliesslich unter Checkboxen —
 * unter einem `select` wurden Kinder immer gerendert und immer gespeichert
 * (Vorpruefung V4). `show_if` schliesst diese Luecke fuer den einen Fall, um den
 * es fachlich geht: Transport „Ambulant" hat weder NA-Begleitung noch
 * Zielklinik.
 *
 * KEIN Ausdruck, keine Verknuepfung, nur eine Ausschlussliste. Wer mehr braucht,
 * baut es dann — eine kleine Regel, die man liest, ist besser als eine
 * allgemeine, die man auswerten muss.
 *
 * 'field' ist die Selbstauskunft des Katalogs und wird GEPRUEFT: Steht dort
 * etwas anderes als die Spalte des Elternfeldes, ist der Katalog widerspruechlich
 * — und die Regel griffe still am falschen Feld.
 *
 * @param array  $f          das UNTERFELD
 * @param mixed  $elternwert aktueller Wert des uebergeordneten Feldes
 * @param string $elternCol  Spaltenname des uebergeordneten Feldes
 */
function mf_show_if(array $f, $elternwert, string $elternCol = ''): bool
{
    $regel = $f['show_if'] ?? null;
    if (!is_array($regel)) { return true; }

    $benannt = (string)($regel['field'] ?? '');
    if ($benannt !== '' && $elternCol !== '' && $benannt !== $elternCol) {
        throw new RuntimeException(
            "mission_fields.php: 'show_if' nennt '$benannt', haengt aber unter "
            . "'$elternCol'.");
    }

    $wert = $elternwert === null ? '' : (string)$elternwert;
    $nicht = $regel['not_in'] ?? [];
    if (!is_array($nicht)) { return true; }
    foreach ($nicht as $n) {
        if ($wert === (string)$n) { return false; }
    }
    return true;
}

/**
 * Erlauben die Filter dieses Feldes seine Anzeige am gegebenen Diensttag?
 *
 * Drei Filter, alle nach demselben Muster (siehe mission_fields.php):
 *   'role_gate' => Rollenkennung   — Rolle im eingefrorenen Satz (`day_crew`)
 *   'kind_gate' => 'air'|'ground'  — Art des Diensttags (`days.kind`)
 *   'cap_gate'  => 'winch'|…       — Faehigkeit (`day_capabilities`)
 *
 * Sie sind UND-verknuepft und arbeiten alle mit den EINGEFRORENEN Angaben des
 * Diensttags (E8), nie mit den heutigen Stammdaten: Wird der Windenhaken Jahre
 * spaeter am Hubschrauber entfernt, aendert das an dokumentierten Einsaetzen
 * nichts (A13e).
 *
 * Ein NEUTRALER Diensttag (kind === null, E26) erfuellt weder `kind_gate` noch
 * `cap_gate` und hat keine Rollen — dort bleiben alle gefilterten Felder
 * verborgen, ausser den bereits belegten. Diese Ausnahme entscheidet der
 * Aufrufer, nicht diese Funktion: Ob ein Feld BELEGT ist, weiss nur er.
 *
 * @param array<string,mixed> $rollen        Rollensatz des Tages (Schluessel = Kennung)
 * @param list<string>        $faehigkeiten  eingefrorene Faehigkeiten
 */
function mf_gates_erfuellt(array $f, array $rollen, ?string $kind, array $faehigkeiten): bool
{
    $rolle = (string)($f['role_gate'] ?? '');
    if ($rolle !== '' && !array_key_exists($rolle, $rollen)) { return false; }

    $art = (string)($f['kind_gate'] ?? '');
    if ($art !== '' && $art !== $kind) { return false; }

    $cap = (string)($f['cap_gate'] ?? '');
    if ($cap !== '' && !in_array($cap, $faehigkeiten, true)) { return false; }

    return true;
}

/* ---- DAS SPALTENREGISTER VON `missions` (Schritt 15/AP6, E-ZE-22) --------
 *
 * `missions` hat 41 Spalten, und sieben Stellen fuehrten eine eigene Liste
 * davon: der Export, das Backup, die beiden Import-Anweisungen, der
 * Suchindex, die Zeitraumansicht und die Einsatzansicht (letztere mit
 * `SELECT *`). Elf Handlisten, jede in ihrer eigenen Reihenfolge, und keine
 * sagte, warum eine Spalte fehlt.
 *
 * WAS DAS KOSTET, steht in `backup_lib.php`: Die tote Altspalte
 * `other_resources` ging jahrelang in jedes Backup, weil dort `SELECT *`
 * stand — ein Feld, das seit Monaten niemand mehr fuellte und das beim
 * Einspielen verworfen wurde.
 *
 * UND `site_ele_m` STEHT IM BACKUP, ABER IN KEINER EINSPIELLISTE — weder im
 * Zweck `backup_restore` noch unter den `$extraCols` aus dem Feldkatalog.
 * Der Wert kommt trotzdem wieder, weil `edbak_restore()` ihn nach dem
 * Bestaetigen aus den Phasenkoordinaten NEU RECHNET
 * (`compute_site_elevation()`). Das ist ein Unterschied, den man sehen
 * koennen muss: Ein Backup TRAEGT die Hoehe nicht zurueck, es stellt sie
 * wieder her. Steht in der Datei eine Hoehe, die zu den Koordinaten nicht
 * passt, gewinnt die Rechnung. Begruendung in `backup_lib.php`, Kopf.
 *
 * JETZT FUEHRT DAS REGISTER JEDE SPALTE GENAU EINMAL und sagt je Zweck, ob
 * sie dabei ist — und an welcher Stelle. Die Zahl ist die Position in der
 * erzeugten Liste; `mf_spalten()` sortiert danach. Ein Alias steht als
 * `[Position, 'name']` daneben (`uhr_gesperrt AS manual`).
 *
 * WARUM DIE POSITIONEN MITGEFUEHRT WERDEN und nicht einfach die
 * Registerreihenfolge gilt: Die sieben Listen sind in Menge UND Reihenfolge
 * eingefroren (`tools/spaltenregister/`). Eine geaenderte Reihenfolge
 * aendert die Spaltenfolge im CSV-Export — also eine Datei, die Menschen
 * aufheben. `mf_spalten()` prueft, dass die Positionen je Zweck eine
 * lueckenlose Folge ab 0 sind; eine doppelte oder fehlende faellt sofort auf.
 *
 * `api_mission` fehlt als Zweck: `api/mission.php` liest `SELECT *` und
 * gibt die Zeile weiter, wie sie ist. Ein Zweck waere dort eine Liste, die
 * niemand braucht — und die beim naechsten Spaltenzuwachs vergessen wuerde.
 */

/**
 * Jede Spalte von `missions` mit ihren Zwecken.
 *
 * @return array<string, array<string, int|array{0:int,1:string}>>
 */
function mf_missions_register(): array
{
    return [
    'id' => ['export' => 0, 'suchindex' => 0, 'range' => 0],
    'user_id' => ['import_neu' => 0, 'ingest_neu' => 0, 'schnitt_neu' => 0,
                  'backup_restore' => 0],
    'device_id' => ['import_neu' => 1, 'ingest_neu' => 1, 'schnitt_neu' => 1],
    'client_ref' => ['backup' => 0, 'import_neu' => 2, 'ingest_neu' => 2, 'schnitt_neu' => 2,
                     'backup_restore' => 1],
    'day_id' => ['export' => 1, 'backup' => 1, 'import_neu' => 3, 'import_aendern' => 0,
                 'suchindex' => 1, 'range' => 1, 'ingest_neu' => 3, 'schnitt_neu' => 3,
                 'backup_restore' => 2],
    'started_at' => ['export' => 2, 'backup' => 2, 'import_neu' => 4, 'import_aendern' => 1,
                     'suchindex' => 2, 'range' => 2, 'ingest_neu' => 4, 'schnitt_neu' => 4,
                     'backup_restore' => 3],
    'ended_at' => ['export' => 3, 'backup' => 3, 'import_neu' => 5, 'import_aendern' => 2,
                   'suchindex' => 20, 'range' => 3, 'ingest_neu' => 5, 'schnitt_neu' => 5,
                   'backup_restore' => 4],
    'distance_m' => ['export' => 4, 'backup' => 4, 'import_neu' => 14, 'import_aendern' => 9,
                     'suchindex' => 3, 'range' => 4, 'ingest_neu' => 6, 'backup_restore' => 9],
    'ascent_m' => ['export' => 5, 'backup' => 5, 'import_neu' => 15, 'import_aendern' => 10,
                   'ingest_neu' => 7, 'backup_restore' => 10],
    'site_ele_m' => ['export' => 28, 'backup' => 6, 'import_neu' => 13, 'import_aendern' => 8,
                     'range' => 10],
    'final' => ['export' => 6, 'backup' => 7, 'import_neu' => 6, 'import_aendern' => 3,
                'ingest_neu' => 8, 'schnitt_neu' => 6, 'backup_restore' => 8],
    'letzter_punkt_am' => [],
    'uhr_gesperrt' => ['export' => [7, 'manual'], 'backup' => [8, 'manual'], 'import_neu' => 7,
                       'import_aendern' => 26, 'schnitt_neu' => 7, 'backup_restore' => 5],
    'origin' => ['export' => 8, 'backup' => 9, 'import_neu' => 8, 'ingest_neu' => 9,
                 'schnitt_neu' => 8, 'backup_restore' => 6],
    'edited' => ['export' => 9, 'backup' => 10, 'import_aendern' => 27, 'backup_restore' => 7],
    'geraet_art' => ['export' => 10, 'backup' => 11, 'ingest_neu' => 10, 'schnitt_neu' => 9,
                     'backup_restore' => 11],
    'geraet_modell' => ['export' => 11, 'backup' => 12, 'ingest_neu' => 11, 'schnitt_neu' => 10,
                        'backup_restore' => 12],
    'transport_mode' => ['export' => 14, 'backup' => 14, 'import_neu' => 25,
                         'import_aendern' => 20, 'suchindex' => 4],
    'na_escort' => ['export' => 15, 'backup' => 15, 'import_neu' => 26, 'import_aendern' => 21,
                    'suchindex' => 5],
    'transport_dest' => ['export' => 12, 'backup' => 13, 'import_neu' => 9,
                         'import_aendern' => 4, 'suchindex' => 6],
    'dest_lat' => ['export' => 18, 'backup' => 18, 'import_neu' => 28, 'import_aendern' => 23],
    'dest_lon' => ['export' => 19, 'backup' => 19, 'import_neu' => 29, 'import_aendern' => 24],
    'schockraum' => ['export' => 26, 'backup' => 26, 'import_neu' => 16, 'import_aendern' => 11,
                     'suchindex' => 7],
    'false_alarm' => ['export' => 16, 'backup' => 16, 'import_neu' => 27,
                      'import_aendern' => 22, 'suchindex' => 8, 'range' => 9],
    'start_src' => ['export' => 17, 'backup' => 17, 'import_neu' => 30, 'import_aendern' => 25],
    'winch' => ['export' => 13, 'backup' => 20, 'import_neu' => 10, 'import_aendern' => 5,
                'suchindex' => 9, 'range' => 5],
    'winch_cycles' => ['export' => 20, 'backup' => 21, 'import_neu' => 18,
                       'import_aendern' => 13, 'suchindex' => 10, 'range' => 8],
    'winch_cycles_pat' => ['export' => 21, 'backup' => 22, 'import_neu' => 19,
                           'import_aendern' => 14, 'suchindex' => 11],
    'winch_airload' => ['export' => 22, 'backup' => 23, 'import_neu' => 20,
                        'import_aendern' => 15, 'suchindex' => 12],
    'bergwacht' => ['export' => 23, 'backup' => 24, 'import_neu' => 21, 'import_aendern' => 16,
                    'suchindex' => 13, 'range' => 6],
    'secondary' => ['export' => 25, 'backup' => 25, 'import_neu' => 17, 'import_aendern' => 12,
                    'suchindex' => 16, 'range' => 7],
    'bw_unit' => ['export' => 24, 'backup' => 27, 'import_neu' => 22, 'import_aendern' => 17,
                  'suchindex' => 14],
    'bw_info' => ['export' => 29, 'backup' => 28, 'import_neu' => 23, 'import_aendern' => 18,
                  'suchindex' => 15],
    'other_ema' => ['export' => 30, 'backup' => 29, 'import_neu' => 24, 'import_aendern' => 19,
                    'suchindex' => 17],
    'other_resources' => [],
    'crew_override' => ['export' => 27, 'backup' => 30, 'import_neu' => 11,
                        'import_aendern' => 6, 'suchindex' => 18],
    'pat_blob' => ['export' => 31, 'backup' => 31, 'import_neu' => 12, 'import_aendern' => 7,
                   'suchindex' => 19, 'range' => 11],
    'notes' => [],
    'created_at' => ['backup' => 32],
    'deleted_at' => ['backup' => 33, 'backup_restore' => 13],
    'deleted_with_day' => ['backup' => 34, 'backup_restore' => 14],
    ];
}

/** Grund, warum eine Spalte in einem Zweck fehlt — oder ueberhaupt nirgends steht. */
function mf_missions_gruende(): array
{
    return [
        'id' =>
            'Interner Verweis. Er gilt nur in DIESER Datenbank; ein Backup soll sich auch in eine andere einspielen lassen.',
        'user_id' =>
            'Interner Verweis (wie id). Beim Einspielen setzt ihn das Zielkonto.',
        'device_id' =>
            'Interner Verweis (wie id). Der Import haengt seine Einsaetze an das virtuelle Geraet des Zielkontos.',
        'letzter_punkt_am' =>
            'Fortsetzungsmarke der GPS-Daten, ein Betriebswert. Er gehoert zum Stand der Uebertragung, nicht zum Einsatz — ein Export oder Backup traegt ihn nie.',
        'other_resources' =>
            'TOTE ALTSPALTE. Seit der Migration 2026_07 liegen die weiteren Rettungsmittel als Zeilen in mission_resources und werden als \'resources\' gesichert. Die Spalte wurde damals nur nicht geloescht.',
        'notes' =>
            'Seit Web 19.0.0 liegt die Einsatznotiz im pat_blob und faellt mit ihm unter die Export-Schranke (S9/AP7). Die Spalte traegt nur noch Altbestand, den api/pat_anheben.php abraeumt.',
        'created_at' =>
            'Im Backup enthalten (die Datei soll den Bestand vollstaendig abbilden), im Export nicht: Dort steht der Anlegezeitpunkt der DATEI im Kopf, und zwei Zeitpunkte gleichen Namens nebeneinander wurden verwechselt.',
        'deleted_at' =>
            'Nur im Backup. Ein Export ist eine Auswertung des BESTANDES; der Papierkorb gehoert nicht hinein.',
        'deleted_with_day' =>
            'Wie deleted_at.',
        'site_ele_m' =>
            'Im Export nur mit personenbezogenen Angaben (A9): Die Hoehe des Einsatzortes ist eine Ortsangabe. Im suchindex nicht, weil die Suche sie nicht anbietet.',
        'bw_info' =>
            'Im Export nur mit personenbezogenen Angaben (A9).',
        'other_ema' =>
            'Im Export nur mit personenbezogenen Angaben (A9).',
        'pat_blob' =>
            'Im Export nur mit personenbezogenen Angaben (A9). Chiffretext — der Server sieht ihn nie im Klartext.',
        'origin' =>
            'Beim Aendern nicht: Ein Import einer Jahresliste darf die Herkunft eines bestehenden Einsatzes nicht ueberschreiben.',
        'edited' =>
            'Beim Anlegen nicht (ein neuer Einsatz ist nicht bearbeitet), beim Aendern fest auf 1.',
        'geraet_art' =>
            'Nur Export und Backup: Die Geraetekennung entsteht beim Koppeln und wird nicht importiert.',
        'geraet_modell' =>
            'Wie geraet_art.',
        'client_ref' =>
            'Im Export nicht: Er ist die Kennung, unter der die Uhr denselben Einsatz wiedererkennt, und damit ein Betriebswert.',
        'ascent_m' =>
            'Im suchindex und range nicht: Beide zeigen den Steigungsmeter nicht an.',
    ];
}

/**
 * Die Spaltenliste eines Zwecks — fertig fuer ein `SELECT`, `INSERT` oder `UPDATE`.
 *
 * @param string $zweck `export` · `backup` · `import_neu` · `import_aendern` ·
 *                      `suchindex` · `range`
 * @param string $praefix Tabellenalias mit Punkt (`'x.'`, `'m.'`) oder leer
 * @param bool   $alias   Alias mitschreiben (`uhr_gesperrt AS manual`) — fuer
 *                        `INSERT`/`UPDATE` ist er falsch und muss weg
 * @return list<string>
 */
function mf_spalten(string $zweck, string $praefix = '', bool $alias = true): array
{
    $reihen = [];
    foreach (mf_missions_register() as $spalte => $zwecke) {
        if (!isset($zwecke[$zweck])) { continue; }
        $e = $zwecke[$zweck];
        $nr = is_array($e) ? $e[0] : $e;
        /* DER ALIAS STEHT IN BACKTICKS, IMMER (Web 20.26.3, hierher gezogen
         * beim Merge von Schritt 15). `manual` ist auf MySQL 8.4.0 bis
         * 8.4.10 auch als ALIAS ein reserviertes Wort; ohne die Backticks
         * antwortete der Export dort mit 1064, und die Sicherung kam nie an
         * (Staging, MySQL 8.4.10, Kennung 097D7622, Nr. 267).
         *
         * Die Backticks stehen HIER und nicht im Register, und das ist
         * Absicht: Sie sind eine Eigenschaft des SQL-Texts, nicht des
         * Spaltennamens — `mf_spalten()` ist die einzige Stelle, die SQL
         * daraus macht. Wer sie ins Register schriebe, muesste sie bei jedem
         * neuen Alias von Hand mitschreiben und wuerde es beim naechsten
         * reservierten Wort wieder vergessen. Ein Backtick um einen
         * gewoehnlichen Bezeichner kostet nichts. */
        $as = (is_array($e) && $alias) ? ' AS `' . $e[1] . '`' : '';
        if (isset($reihen[$nr])) {
            throw new RuntimeException("mf_spalten($zweck): Position $nr ist doppelt vergeben — "
                . "'{$reihen[$nr]}' und '$spalte'.");
        }
        $reihen[$nr] = $praefix . $spalte . $as;
    }
    if ($reihen === []) {
        throw new InvalidArgumentException("mf_spalten: unbekannter Zweck '$zweck'.");
    }
    ksort($reihen);
    /* LUECKENLOS AB 0. Eine fehlende Position hiesse, dass jemand eine Spalte
     * aus einem Zweck genommen hat, ohne die uebrigen nachzuziehen — die
     * Liste waere dann kuerzer, als das Register behauptet, und das faellt
     * sonst erst an der Spaltenzahl einer Exportdatei auf. */
    if (array_keys($reihen) !== range(0, count($reihen) - 1)) {
        throw new RuntimeException("mf_spalten($zweck): Die Positionen sind nicht "
            . 'lueckenlos ab 0: ' . implode(', ', array_keys($reihen)) . '.');
    }
    return array_values($reihen);
}

/** Die Spaltenliste eines Zwecks als SQL-Text. */
function mf_spalten_sql(string $zweck, string $praefix = '', bool $alias = true): string
{
    return implode(', ', mf_spalten($zweck, $praefix, $alias));
}
