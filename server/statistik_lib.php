<?php
declare(strict_types=1);

/**
 * STATISTIK — die Fenster und die eine Zaehlung der Einsaetze
 * (P5c/AP7, E-P5c-18, R38, Nr. 191).
 *
 * WARUM EINE EIGENE DATEI FUER EINE ABFRAGE. `betrieb_statistik.php` ist eine
 * Seite; sie laesst sich nicht einbinden, ohne sie zu zeichnen. Der
 * Messstand (Schritt `statistik`) soll aber GENAU die Abfrage der Seite
 * erklaeren lassen (`EXPLAIN`), nicht eine nachgeschriebene — zwei Fassungen
 * derselben Bedingung liefen auseinander, und der Messstand maesse dann eine
 * Abfrage, die niemand stellt. Deshalb steht sie hier, einmal, und beide
 * lesen sie.
 *
 * WAS SIE ZAEHLT (E-P5c-18, entschieden 20.09.2026): Einsaetze ab ihrem
 * BEGINN (`missions.started_at`, UTC), ohne Demo-Konto, ohne Papierkorb, in
 * Fenstern mit Unter- UND Obergrenze (F-P5c-39) — ein Beginn in der Zukunft
 * liegt in keinem Fenster. Kein Verbund mit `days`: Ein Einsatz im
 * Papierkorb seines Tages traegt sein eigenes `deleted_at`
 * (`deleted_with_day`), und `day_id` darf leer sein.
 */

/* Tage je Fenster => Spaltenkopf. Konten und Einsaetze fangen bei 24 h an
 * (R38); die Geraete bleiben bei den drei Fenstern aus S8 — eine Kopplung in
 * den letzten 24 Stunden ist keine Frage, die jemand stellt. „6 Monate" sind
 * 180 Tage, „1 Jahr" 365: Ein Monat ist keine feste Laenge. */
const STAT_FENSTER_KONTEN    = [1 => '24 h', 7 => '7 Tage', 30 => '30 Tage', 180 => '6 Monate'];
const STAT_FENSTER_EINSAETZE = [1 => '24 h', 7 => '7 Tage', 30 => '30 Tage', 180 => '6 Monate',
                                365 => '1 Jahr'];
const STAT_FENSTER_GERAETE   = [7 => '7 Tage', 30 => '30 Tage', 180 => '6 Monate'];

/**
 * Die Abfrage fuer alle Einsatzfenster — ein Platzhalter, die Kennung des
 * Demo-Kontos (0, wenn es keines gibt).
 *
 * EINE ABFRAGE FUER ALLE FUENF FENSTER. Sie liest das laengste Fenster ueber
 * den Index `idx_missions_started` und zaehlt die kuerzeren darin mit; fuenf
 * Abfragen laesen dieselben Zeilen fuenfmal. Liefert je Fenster `n<Tage>`
 * (Einsaetze) und `k<Tage>` (Konten mit Einsatz).
 *
 * Die Tage stehen als Zahlen aus der Konstante im Text, nicht als Parameter:
 * Sie kommen nie von aussen, und zehn Platzhalter fuer fuenf Zahlen waeren
 * nur schwerer zu lesen.
 */
function statistik_einsaetze_sql(): string
{
    $spalten = [];
    foreach (array_keys(STAT_FENSTER_EINSAETZE) as $tage) {
        $tage = (int)$tage;
        $bed  = "m.started_at >= UTC_TIMESTAMP() - INTERVAL $tage DAY";
        $spalten[] = "SUM($bed) AS n$tage, COUNT(DISTINCT CASE WHEN $bed THEN m.user_id END) AS k$tage";
    }
    $laengstes = (int)max(array_keys(STAT_FENSTER_EINSAETZE));
    return 'SELECT ' . implode(', ', $spalten) . '
            FROM missions m
            WHERE m.user_id <> ? AND m.deleted_at IS NULL
              AND m.started_at >= UTC_TIMESTAMP() - INTERVAL ' . $laengstes . ' DAY
              AND m.started_at <= UTC_TIMESTAMP()';
}

/**
 * Die Herkunft der Einsaetze der letzten 30 Tage — dieselbe Bedingung wie
 * oben, ein Fenster, gruppiert nach `origin` (E-P5c-45). Ein Platzhalter:
 * die Kennung des Demo-Kontos.
 */
function statistik_herkunft_sql(): string
{
    return 'SELECT m.origin, COUNT(*) AS n
            FROM missions m
            WHERE m.user_id <> ? AND m.deleted_at IS NULL
              AND m.started_at >= UTC_TIMESTAMP() - INTERVAL 30 DAY
              AND m.started_at <= UTC_TIMESTAMP()
            GROUP BY m.origin';
}
