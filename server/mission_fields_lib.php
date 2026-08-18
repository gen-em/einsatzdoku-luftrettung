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
 *   'klasse' CSS-Klasse der Spalte, `c-dc-<spalte>`. Die Breite steht in
 *            style.css; ohne eigenen Eintrag greift die Vorgabe von `.c-dc`.
 *
 * @return list<array{col:string,art:string,label:string,klasse:string}>
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
 * Ist dieses Feld eine Spalte in `missions`?
 *
 * Zwei Feldarten sind es nicht: 'resources' (eigene Zeilen in
 * `mission_resources`) und alles mit 'store' (seit Web 6.0.0 die Besatzung in
 * `mission_crew`). Beide duerfen nicht in ein SELECT, INSERT oder UPDATE auf
 * `missions` geraten.
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
