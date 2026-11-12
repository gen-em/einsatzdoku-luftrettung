<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)

/*
 * Gen-EM NAdoku — die Abschrift des Standbuchs auf dem Server (KA-06b-2).
 *
 * WOZU. Der Browser hält je Konto einen Zählerstand für jeden verschlossenen
 * Datensatz (`assets/standbuch.js`, Technik 4.42): Mit ihm erkennt er, ob
 * jemand einen alten Block zurückgelegt hat. Damit ein zweiter Browser
 * dasselbe weiß, liegt eine Abschrift hier — in 16 Blättern und einem
 * Gerätebuch je Konto, jedes verschlossen unter dem Inhaltsschlüssel
 * (`edk2:`, Zweck `stand` bzw. `geraete`). Der Server kann sie nicht öffnen
 * und nicht fälschen; ein Blatt mit falschem Inhalt geht im Browser nicht
 * auf, ein zurückgelegtes erkennt der Browser an seinem Anker.
 *
 * WAS DER SERVER PRÜFT: die Form (`edk2_innenlaenge()` in validate_lib.php)
 * und die VERSIONSREGEL — ein Blatt wird nur ersetzt, wenn der Absender die
 * Version nennt, die hier liegt, und eine höhere schickt. Das geschieht in
 * EINER Anweisung, sonst gewinnen zwei Tabs, die gleichzeitig schreiben,
 * beide (`standbuch.js`, „DIE SCHNITTSTELLE").
 *
 * `wer` ist 0…15 für ein Blatt und 16 für das Gerätebuch (im Endpunkt
 * `'buch'`).
 */

require_once __DIR__ . '/validate_lib.php';

/** 16 Blätter und das Gerätebuch. */
const SB_BLAETTER = 16;
const SB_BUCH     = 16;

/* Die größte Innenlänge eines Blatts: 256 KB. Ein Blatt trägt je Datensatz
 * eine Zeile von rund 60 Byte; bei 5 000 Einsätzen sind es rund 320 je
 * Blatt, rund 20 KB. Die Decke ist das Zwölffache davon und hält ein Konto
 * davon ab, die Datenbank über diesen Weg zu füllen. */
const SB_MAX_INNEN = 262144;

/** Gibt es die Tabelle schon (vor `update.php` nicht)? */
function sb_bereit(PDO $pdo): bool
{
    static $bereit = null;
    return $bereit ??= db_hat_tabelle($pdo, 'stand_blaetter');
}

/**
 * `wer` aus der Anfrage: 0…15 oder 'buch'. Liefert 0…16 oder null.
 */
function sb_wer(mixed $wer): ?int
{
    if ($wer === 'buch') { return SB_BUCH; }
    if (is_int($wer) && $wer >= 0 && $wer < SB_BLAETTER) { return $wer; }
    return null;
}

/** Die Version je Blatt (16 Zahlen) und die des Gerätebuchs; 0 = keins. */
function sb_stand(PDO $pdo, int $userId): array
{
    $blaetter = array_fill(0, SB_BLAETTER, 0);
    $buch = 0;
    $st = $pdo->prepare('SELECT wer, version FROM stand_blaetter WHERE user_id = ?');
    $st->execute([$userId]);
    foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
        $w = (int)$z['wer'];
        if ($w === SB_BUCH) { $buch = (int)$z['version']; }
        elseif ($w >= 0 && $w < SB_BLAETTER) { $blaetter[$w] = (int)$z['version']; }
    }
    return ['blaetter' => $blaetter, 'buch' => $buch];
}

/** Ein Blatt: `['version' => n, 'text' => …]`, oder `['version' => 0, 'text' => null]`. */
function sb_holen(PDO $pdo, int $userId, int $wer): array
{
    $st = $pdo->prepare('SELECT version, text FROM stand_blaetter WHERE user_id = ? AND wer = ?');
    $st->execute([$userId, $wer]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    return $z ? ['version' => (int)$z['version'], 'text' => (string)$z['text']]
              : ['version' => 0, 'text' => null];
}

/**
 * Ein Blatt ablegen — nur, wenn hier `$alt` liegt (0 = noch keins) und
 * `$neu` > `$alt` ist.
 *
 * Liefert `['ok' => true]` oder, bei einem Konflikt, `['ok' => false]` mit
 * dem Blatt, das hier liegt. Die Form prüft der Aufrufer
 * (`sb_ablage_pruefen()`).
 *
 * EINE ANWEISUNG JE FALL. Für ein neues Blatt `INSERT IGNORE`: Liegt schon
 * eins da, fügt sie nichts ein (Schlüssel `user_id, wer`). Für ein
 * vorhandenes `UPDATE … WHERE version = :alt`: Hat ein anderer Tab
 * dazwischen geschrieben, trifft sie keine Zeile. In beiden Fällen sagt die
 * Zahl der getroffenen Zeilen, wer gewonnen hat.
 */
function sb_ablegen(PDO $pdo, int $userId, int $wer, int $alt, int $neu, string $text): array
{
    if ($alt === 0) {
        $st = $pdo->prepare('INSERT IGNORE INTO stand_blaetter (user_id, wer, version, text, geaendert_am)
                             VALUES (?, ?, ?, ?, UTC_TIMESTAMP())');
        $st->execute([$userId, $wer, $neu, $text]);
    } else {
        $st = $pdo->prepare('UPDATE stand_blaetter SET version = ?, text = ?, geaendert_am = UTC_TIMESTAMP()
                             WHERE user_id = ? AND wer = ? AND version = ?');
        $st->execute([$neu, $text, $userId, $wer, $alt]);
    }
    if ($st->rowCount() === 1) { return ['ok' => true]; }
    return ['ok' => false] + sb_holen($pdo, $userId, $wer);
}

/**
 * Form einer Ablage: Zahlen ganz, `$neu` > `$alt` ≥ 0, Text ein `edk2:` in
 * einer Größenklasse bis zur Decke. Liefert null oder den Grund.
 */
function sb_ablage_pruefen(mixed $alt, mixed $neu, mixed $text): ?string
{
    if (!is_int($alt) || !is_int($neu) || $alt < 0 || $neu <= $alt || $neu > 4294967295) {
        return 'version';
    }
    if (!is_string($text) || edk2_innenlaenge($text, SB_MAX_INNEN) === null) {
        return 'text';
    }
    return null;
}
