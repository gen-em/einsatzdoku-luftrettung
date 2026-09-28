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

/* DIE WAHL ÜBER DEN REITERN (R4-23, Nr. 122 a, Bild M-R4-23): vier feste
 * Fenster als Pillen, Tage => [Pille, Wortlaut in Kennzahl und Karte]. Sie
 * bestimmen die Kennzahl „Einsätze in …" und die Herkunft; die Tabellen
 * zeigen weiter jedes Fenster. Die Namen sind die der Spaltenköpfe
 * (`STAT_FENSTER_EINSAETZE`), nicht „180 Tage" wie im Mockup: Eine Pille
 * „180 Tage" über einer Spalte „6 Monate" wären zwei Namen für eine Sache
 * (E-R4-57). 30 Tage sind die Vorgabe — die Seite zeigte bis Web 21.4.1 nur
 * sie. */
const STAT_WAHL = [7   => ['7 Tage',   'in 7 Tagen'],
                   30  => ['30 Tage',  'in 30 Tagen'],
                   180 => ['6 Monate', 'in 6 Monaten'],
                   365 => ['1 Jahr',   'in einem Jahr']];
const STAT_WAHL_VORGABE = 30;

/**
 * Der Zeitraum der Seite aus der Adresse (R4-23).
 *
 * Zwei Arten: ein festes Fenster (`t` aus `STAT_WAHL`, sonst die Vorgabe)
 * oder ein EIGENER Zeitraum (`von` und `bis`, beide `JJJJ-MM-TT`).
 *
 * TAGESGRENZEN IN ORTSZEIT (`app.timezone`, Rückfall Europe/Berlin): „bis
 * 31.05." meint den ganzen 31. Mai in Deutschland, nicht bis 02:00 Uhr MESZ.
 * Die Untergrenze ist der Beginn des ersten Tages, die Obergrenze der Beginn
 * des Tages NACH dem letzten — beide in UTC, weil `missions.started_at` in
 * UTC steht. Die Abfragen halten zusätzlich die Obergrenze JETZT
 * (F-P5c-39): Ein Beginn in der Zukunft liegt in keinem Zeitraum.
 *
 * EIN BIS IN DER ZUKUNFT WIRD AUF HEUTE GEKÜRZT, sonst verdünnte er den
 * Schnitt je Tag mit Tagen, an denen noch niemand arbeiten konnte. Ein Von
 * nach heute oder nach dem Bis ist ein Fehler; ebenso ein Feld allein. Bei
 * einem Fehler gilt die Vorgabe, und `fehler` sagt, warum.
 *
 * @return array{art:string, tage:int, t?:int, von?:string, bis?:string,
 *               unten?:string, oben?:string, fehler?:string}
 */
function statistik_zeitraum(array $q): array
{
    $vorgabe = ['art' => 'fenster', 't' => STAT_WAHL_VORGABE, 'tage' => STAT_WAHL_VORGABE];
    $von = trim((string)($q['von'] ?? ''));
    $bis = trim((string)($q['bis'] ?? ''));
    if ($von === '' && $bis === '') {
        $t = (int)($q['t'] ?? STAT_WAHL_VORGABE);
        return isset(STAT_WAHL[$t]) ? ['art' => 'fenster', 't' => $t, 'tage' => $t] : $vorgabe;
    }
    if ($von === '' || $bis === '') {
        return $vorgabe + ['fehler' => 'Für einen eigenen Zeitraum braucht es Von und Bis.'];
    }
    $zone = new DateTimeZone((string)konfig('app.timezone', 'Europe/Berlin'));
    $lies = static function (string $d) use ($zone): ?DateTimeImmutable {
        $x = DateTimeImmutable::createFromFormat('!Y-m-d', $d, $zone);
        return ($x !== false && $x->format('Y-m-d') === $d) ? $x : null;
    };
    $a = $lies($von);
    $b = $lies($bis);
    if ($a === null || $b === null) {
        return $vorgabe + ['fehler' => 'Ein Datum des Zeitraums ist ungültig.'];
    }
    $heute = $lies(heute_lokal());
    if ($a > $heute) {
        return $vorgabe + ['fehler' => 'Der Zeitraum beginnt nach heute.'];
    }
    if ($a > $b) {
        return $vorgabe + ['fehler' => 'Von liegt nach Bis.'];
    }
    if ($b > $heute) { $b = $heute; }
    $utc = new DateTimeZone('UTC');
    return [
        'art'   => 'eigen',
        'von'   => $a->format('Y-m-d'),
        'bis'   => $b->format('Y-m-d'),
        /* Kalendertage, in UTC gezählt: Über die Zeitumstellung hinweg hat
         * ein Ortstag 23 oder 25 Stunden, und `diff()` zweier Mitternächte
         * könnte einen Tag verlieren. */
        'tage'  => (int)(new DateTimeImmutable($a->format('Y-m-d'), $utc))
                       ->diff(new DateTimeImmutable($b->format('Y-m-d'), $utc))->days + 1,
        'unten' => $a->setTimezone($utc)->format('Y-m-d H:i:s'),
        'oben'  => $b->modify('+1 day')->setTimezone($utc)->format('Y-m-d H:i:s'),
        /* Für die Anzeige: der letzte Tag, nicht der Beginn des nächsten. */
        'bis_utc' => $b->setTimezone($utc)->format('Y-m-d H:i:s'),
    ];
}

/**
 * Die Adressteile, die den Zeitraum tragen — für Reiter, Kennzahlen und
 * Pillen, damit ein Wechsel des Reiters den Zeitraum nicht verliert. Die
 * Vorgabe trägt nichts: `?r=einsaetze` bleibt `?r=einsaetze`.
 */
function statistik_zeitraum_adresse(array $z): string
{
    if ($z['art'] === 'eigen') {
        return '&von=' . rawurlencode($z['von']) . '&bis=' . rawurlencode($z['bis']);
    }
    return ($z['t'] ?? STAT_WAHL_VORGABE) === STAT_WAHL_VORGABE ? '' : '&t=' . (int)$z['t'];
}

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
 * Die Bedingung EINES Zeitraums auf `m.started_at` — Unter- und Obergrenze,
 * und immer auch „nicht nach jetzt" (F-P5c-39). Ein festes Fenster rechnet
 * mit `UTC_TIMESTAMP()` und hat keinen Platzhalter; ein eigener Zeitraum
 * hat zwei (Unter- und Obergrenze, UTC, die obere ausschließlich). Beide
 * lesen über `idx_missions_started`.
 */
function statistik_bedingung(array $z): string
{
    if ($z['art'] === 'eigen') {
        return 'm.started_at >= ? AND m.started_at < ? AND m.started_at <= UTC_TIMESTAMP()';
    }
    return 'm.started_at >= UTC_TIMESTAMP() - INTERVAL ' . (int)$z['tage'] . ' DAY
              AND m.started_at <= UTC_TIMESTAMP()';
}

/** Die Werte zu den Platzhaltern der Bedingung — für ein festes Fenster keine. */
function statistik_bedingung_werte(array $z): array
{
    return $z['art'] === 'eigen' ? [$z['unten'], $z['oben']] : [];
}

/**
 * Die Herkunft der Einsaetze im gewählten Zeitraum — dieselbe Bedingung wie
 * oben, gruppiert nach `origin` (E-P5c-45). Platzhalter: die Kennung des
 * Demo-Kontos, dann die der Bedingung. Bis Web 21.4.1 immer 30 Tage (R4-23).
 */
function statistik_herkunft_sql(array $z): string
{
    return 'SELECT m.origin, COUNT(*) AS n
            FROM missions m
            WHERE m.user_id <> ? AND m.deleted_at IS NULL
              AND ' . statistik_bedingung($z) . '
            GROUP BY m.origin';
}

/**
 * Einsätze und Konten mit Einsatz in EINEM Zeitraum (R4-23) — für den
 * eigenen Zeitraum; die festen Fenster rechnet `statistik_einsaetze_sql()`
 * in einer Abfrage. Platzhalter wie bei der Herkunft.
 */
function statistik_zeitraum_sql(array $z): string
{
    return 'SELECT COUNT(*) AS n, COUNT(DISTINCT m.user_id) AS k
            FROM missions m
            WHERE m.user_id <> ? AND m.deleted_at IS NULL
              AND ' . statistik_bedingung($z);
}
