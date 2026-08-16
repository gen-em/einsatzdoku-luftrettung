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
