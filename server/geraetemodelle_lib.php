<?php
declare(strict_types=1);

/**
 * NACHLOESEN DER GERAETEMODELLE — Bibliothek und Job (P5a/AP11, E-P5a-21).
 *
 * WOFUER. `pair.php` loest die Teilenummer einer Garmin-Uhr im Moment der
 * Kopplung auf, und nur dann. Trifft sie dabei auf eine leere oder aeltere
 * Modelltabelle, bleibt `geraet_modell` leer — und, was schwerer wiegt,
 * `geraet_art` steht auf der ungeprueften SELBSTAUSKUNFT des Geraets. Die
 * Uhr-App sendet dort fest `"uhr"`; ein Radcomputer waere damit dauerhaft als
 * Uhr gezaehlt, obwohl die Geraetedateien es besser wissen.
 *
 * Genau dafuer steht die Rohangabe in einer eigenen Spalte (E-S6-1): Sie
 * haelt den Schluessel bereit, mit dem sich jede Zeile spaeter erneut
 * aufloesen laesst.
 *
 * WARUM AUS DEM SKRIPT EINE BIBLIOTHEK WURDE. `tools/geraetemodelle/
 * nachaufloesen.php` kann es seit Web 12.9.1 — aber nur ueber die
 * Kommandozeile, und **auf einem Webspace ohne SSH gibt es diesen Weg
 * nicht**. Dort holten die betroffenen Geraete ihre Angabe erst bei der
 * naechsten Kopplung nach, also womoeglich nie. Die Zahl, die Backlog Nr. 80
 * auswerten will, haengt damit daran, ob jemand SSH hat.
 *
 * DREI REGELN, UNVERAENDERT AUS DEM SKRIPT:
 *
 *   1. ES AENDERT NUR, WAS DIE TABELLE WIRKLICH KENNT. Eine Zeile, deren
 *      Rohangabe unbekannt bleibt, wird nicht angefasst — kein Leeren, kein
 *      „unbekannt", kein Zuruecksetzen.
 *   2. DIE ROHANGABE SELBST WIRD NIE VERAENDERT. Sie ist die Auskunft des
 *      Geraets und muss die einzige Spalte bleiben, die man nicht nachrechnet.
 *   3. HANDY-ZEILEN BLEIBEN UNBERUEHRT. Ein Handy kennt seinen Modellnamen
 *      selbst; seine Rohangabe IST der Klarname, und die Modelltabelle
 *      enthaelt keine Handys. Erkannt wird das daran, dass die Tabelle die
 *      Rohangabe nicht fuehrt — dieselbe Regel wie fuer jede andere
 *      unbekannte Angabe, kein Sonderfall.
 *
 * WANN DER JOB LAEUFT: wenn sich die TABELLE geaendert hat. Der Vergleich
 * geht ueber `sha256(serialize(GERAETE_MODELLE))` gegen den Wert in
 * `app_state`. Das ist genauer als ein Datum: Ein Deploy fasst die
 * Aenderungszeit jeder Datei an, der Inhalt aber bleibt derselbe — ein Job,
 * der nach jedem Deploy 300 Zeilen durchgeht, ist ein Job, der nichts tut
 * und dafuer Zeit verbraucht.
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/geraete_lib.php';

/** Der Stand in `app_state`: Hash der Tabelle, Zeitpunkt, zwei Zahlen. */
const GM_STAND_SCHLUESSEL = 'geraetemodelle_stand';

/** Wie viele Zeilen ein Block ansieht (E-P5a-21). */
const GM_BLOCK = 200;

/** Zeitpolster, unter dem der Job keinen weiteren Block mehr beginnt. */
const GM_RESERVE_S = 0.5;

/**
 * Der Fingerabdruck der Modelltabelle.
 *
 * `serialize()` und nicht `json_encode()`: Die Tabelle ist ein PHP-Array mit
 * Zeichenketten, und `serialize()` bildet sie eindeutig ab — auch die
 * Reihenfolge, die `erzeugen.py` festlegt. Zwei Tabellen mit denselben
 * Eintraegen in anderer Reihenfolge bekommen damit verschiedene Hashes und
 * loesen einen Lauf aus, der nichts findet. Das ist der billigere der beiden
 * Fehler.
 */
function gm_tabellen_hash(?array $tabelle = null): string
{
    return hash('sha256', serialize($tabelle ?? GERAETE_MODELLE));
}

/**
 * Was zuletzt gelaufen ist.
 *
 * @return array{hash:?string,am:?string,nachgeloest:int,unbekannt:int}
 */
function gm_stand_lesen(): array
{
    $leer = ['hash' => null, 'am' => null, 'nachgeloest' => 0, 'unbekannt' => 0];
    $roh = function_exists('app_state_lesen') ? app_state_lesen(GM_STAND_SCHLUESSEL) : null;
    if ($roh === null || $roh === '') { return $leer; }
    $d = json_decode($roh, true);
    if (!is_array($d)) { return $leer; }
    return ['hash'        => isset($d['h']) && is_string($d['h']) ? $d['h'] : null,
            'am'          => isset($d['am']) && is_string($d['am']) ? $d['am'] : null,
            'nachgeloest' => (int)($d['n'] ?? 0),
            'unbekannt'   => (int)($d['u'] ?? 0)];
}

/**
 * Den Stand schreiben. `false` = ging nicht (steht dann im Fehlerprotokoll).
 *
 * VIER KURZE SCHLUESSEL, weil `app_state.v` 190 Zeichen fasst: Der Hash
 * allein ist 64, der Zeitpunkt 19. Mit ausgeschriebenen Namen waere es eng
 * genug, dass eine fuenfte Zahl spaeter still abgeschnitten wuerde.
 */
function gm_stand_merken(string $hash, int $nachgeloest, int $unbekannt): bool
{
    if (!function_exists('app_state_setzen')) { return false; }
    return app_state_setzen(GM_STAND_SCHLUESSEL, (string)json_encode([
        'h'  => $hash,
        'am' => gmdate('Y-m-d H:i:s'),
        'n'  => max(0, $nachgeloest),
        'u'  => max(0, $unbekannt),
    ]));
}

/**
 * Einen Block Geraetezeilen ansehen — und auf Wunsch eintragen.
 *
 * Das ist der Kern, den sich Skript und Job teilen. Er baut keine Verbindung
 * auf, oeffnet keine Transaktion ueber mehrere Bloecke und gibt IMMER
 * zurueck, was er vorhat — auch mit `$schreiben = false`. Genau das ist die
 * Vorschau des Skripts.
 *
 * @param int  $abId  nur Zeilen mit groesserer Kennung (Fortsetzungsmarke)
 * @return array{geprueft:int,kandidaten:list<array>,geschrieben:int,
 *                unbekannt:int,letzte_id:int,fertig:bool}
 */
function gm_nachaufloesen(PDO $pdo, int $block, bool $schreiben,
                          int $abId = 0, ?array $tabelle = null): array
{
    $block = max(1, $block);
    $st = $pdo->prepare('SELECT id, geraet_art, geraet_modell, geraet_teil
                           FROM devices
                          WHERE geraet_teil IS NOT NULL AND id > ?
                          ORDER BY id
                          LIMIT ' . $block);
    $st->execute([$abId]);
    $zeilen = $st->fetchAll();

    $raus = ['geprueft' => count($zeilen), 'kandidaten' => [], 'geschrieben' => 0,
             'unbekannt' => 0, 'letzte_id' => $abId,
             /* `fertig` heisst: Dieser Block war der letzte. Erkannt an der
              * Zahl der gelesenen Zeilen und nicht an einer zweiten Abfrage
              * „gibt es noch welche?" — die waere ein zweiter Weg zu
              * derselben Auskunft und koennte anders antworten. */
             'fertig' => count($zeilen) < $block];

    foreach ($zeilen as $z) {
        $raus['letzte_id'] = (int)$z['id'];
        $treffer = geraet_modell_aufloesen((string)$z['geraet_teil'], $tabelle);
        if ($treffer === null) { $raus['unbekannt']++; continue; }

        $neuModell = (string)$treffer['modell'];
        $neuArt    = $treffer['art'] ?? $z['geraet_art'];
        if ((string)$z['geraet_modell'] === $neuModell
            && (string)$z['geraet_art'] === (string)$neuArt) {
            continue;                        // steht schon richtig da
        }
        $raus['kandidaten'][] = [
            'id'         => (int)$z['id'],
            'teil'       => (string)$z['geraet_teil'],
            'alt_art'    => $z['geraet_art'],
            'alt_modell' => $z['geraet_modell'],
            'art'        => $neuArt,
            'modell'     => $neuModell,
        ];
    }

    if (!$schreiben || $raus['kandidaten'] === []) { return $raus; }

    /* EINE TRANSAKTION JE BLOCK, nicht eine ueber alle. Der Job laeuft in
     * Bloecken mit Zeitbudget; eine Transaktion ueber mehrere Bloecke haelt
     * Sperren ueber Sekunden und wird beim Zeitablauf zurueckgerollt — dann
     * waere die Arbeit des Blocks weg, den man gerade geschafft hat.
     *
     * `inTransaction()`, weil der Aufrufer schon eine offen haben kann. */
    $eigene = !$pdo->inTransaction();
    if ($eigene) { $pdo->beginTransaction(); }
    try {
        $up = $pdo->prepare('UPDATE devices SET geraet_art = ?, geraet_modell = ?
                              WHERE id = ?');
        foreach ($raus['kandidaten'] as $k) {
            $up->execute([$k['art'], $k['modell'], $k['id']]);
            $raus['geschrieben']++;
        }
        if ($eigene) { $pdo->commit(); }
    } catch (Throwable $ex) {
        if ($eigene && $pdo->inTransaction()) { $pdo->rollBack(); }
        throw $ex;
    }
    return $raus;
}

/**
 * Wie viele Geraetezeilen ueberhaupt eine Rohangabe tragen.
 *
 * Fuer den Rueckstand des Jobs: Steht der Hash, ist nichts zu tun (`null`);
 * steht er nicht, sind ALLE diese Zeilen anzusehen. Das ist eine Obergrenze
 * und keine Zahl der zu aendernden — welche das sind, weiss man erst beim
 * Ansehen, und dafuer ist der Job da.
 */
function gm_zeilen_mit_rohangabe(PDO $pdo): int
{
    try {
        return (int)$pdo->query('SELECT COUNT(*) FROM devices
                                  WHERE geraet_teil IS NOT NULL')->fetchColumn();
    } catch (Throwable $ex) { return 0; }
}
