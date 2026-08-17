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
 * Die Pruefung steht hier und nicht in jedem Aufrufer, weil sie sonst beim
 * naechsten Sonderfall an fuenf Stellen nachgezogen werden muesste.
 */
function mf_ist_spalte(array $f): bool
{
    return !isset($f['store']) && ($f['type'] ?? 'text') !== 'resources';
}
