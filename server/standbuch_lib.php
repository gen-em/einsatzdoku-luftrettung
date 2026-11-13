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

/* ====================================================================
 * DATENSATZ UND BLATT IN EINER ANFRAGE (KA-06b-3)
 * ====================================================================
 *
 * Wer einen Patientenblock der Fassung 2 schreibt, schickt die Blätter mit,
 * deren Zähler er dabei hebt (`EdStandbuch.patVerschliessenViele()`):
 *
 *     "blaetter": [ { "wer": 0…15, "version_alt": n, "version": m,
 *                     "text": "edk2:…", "refs": ["<client_ref>", …] } ]
 *
 * Steht die Weiche im Browser aus, fehlt das Feld, und nichts hier läuft.
 *
 * ZWEI REGELN, beide in der Transaktion des Datensatzes:
 *
 *   (1) KEIN BLATT VOR SEINEM BLOCK. Jeder Einsatz in `refs` muss nach dem
 *       Schreiben einen der Blöcke dieser Anfrage tragen. Ein Blatt, das
 *       seinem Block vorausläuft, ließe den alten Block später als
 *       „zurückgelegt" erscheinen — den Schaden hätte, wer nichts falsch
 *       gemacht hat. Übersprungene Einsätze (schon da, nicht mehr da, ein
 *       anderes Fenster war schneller) nennt die Antwort; der Browser
 *       schickt dann ohne ihre Zähler noch einmal. Der umgekehrte Fall ist
 *       harmlos: Ein Block vor seinem Blatt „hinkt", und der Browser trägt
 *       nach.
 *   (2) DIE VERSIONSREGEL wie in `api/standbuch.php` (`sb_ablegen()`).
 *
 * Scheitert eine, wird NICHTS gespeichert: `SbKonflikt` rollt die
 * Transaktion zurück, und der Endpunkt antwortet 409 mit `$k->antwort`:
 *
 *     { "error": "blatt", "grund": "version", "wer", "version", "text" }
 *     { "error": "blatt", "grund": "uebersprungen", "refs": [ … ] }
 *     { "error": "migration", "meldung" }     vor `update.php`
 */

/** Ein Konflikt am Blatt — die Antwort steht in `$antwort` (409). */
final class SbKonflikt extends RuntimeException
{
    public function __construct(public readonly array $antwort)
    {
        parent::__construct('standbuch: ' . (string)($antwort['grund'] ?? $antwort['error'] ?? ''));
    }

    /** Ein Satz für eine Seite ohne JSON (das Einsatzformular). */
    public function satz(): string
    {
        return ($this->antwort['error'] ?? '') === 'migration'
            ? (string)$this->antwort['meldung']
            : 'Der Zählerstand dieses Einsatzes wurde inzwischen an anderer Stelle '
              . 'fortgeschrieben (ein zweites Fenster). Es wurde NICHTS gespeichert — '
              . 'bitte die Seite neu laden und erneut speichern.';
    }
}

/** Höchstens so viele Kennungen je Blatt — so viele Einsätze trägt eine
 *  Anfrage höchstens (Import). */
const SB_REFS_MAX = 5000;

/**
 * Die Blätter einer Anfrage, geprüft. Liefert `[]` (keine), die Liste oder
 * den Namen des Felds, das nicht passt.
 *
 * @return list<array{wer:int, version_alt:int, version:int, text:string, refs:list<string>}>|string
 */
function sb_blaetter_lesen(mixed $roh): array|string
{
    if ($roh === null) { return []; }
    if (!is_array($roh) || !array_is_list($roh) || !$roh || count($roh) > SB_BLAETTER) {
        return 'blaetter';
    }
    $aus = []; $gesehen = [];
    foreach ($roh as $b) {
        if (!is_array($b)) { return 'blaetter'; }
        $wer = $b['wer'] ?? null;
        if (!is_int($wer) || $wer < 0 || $wer >= SB_BLAETTER || isset($gesehen[$wer])) { return 'wer'; }
        $gesehen[$wer] = true;
        $grund = sb_ablage_pruefen($b['version_alt'] ?? null, $b['version'] ?? null, $b['text'] ?? null);
        if ($grund !== null) { return $grund; }
        $refs = $b['refs'] ?? null;
        if (!is_array($refs) || !array_is_list($refs) || !$refs || count($refs) > SB_REFS_MAX) {
            return 'refs';
        }
        foreach ($refs as $r) {
            if (!is_string($r) || $r === '' || strlen($r) > 64) { return 'refs'; }
        }
        $aus[] = ['wer' => $wer, 'version_alt' => $b['version_alt'], 'version' => $b['version'],
                  'text' => $b['text'], 'refs' => array_values(array_unique($refs))];
    }
    return $aus;
}

/**
 * Die Kennungen aus `$refs`, deren Einsatz nach dem Schreiben KEINEN der
 * Blöcke aus `$texte` trägt (Regel 1). Tragen zwei Zeilen dieselbe Kennung
 * (zwei Geräte), genügt eine.
 *
 * @param list<string> $refs
 * @param list<string> $texte  die Blöcke dieser Anfrage
 * @return list<string>
 */
function sb_nicht_geschrieben(PDO $pdo, int $userId, array $refs, array $texte): array
{
    $soll = array_flip(array_map('strval', $texte));
    $gut = [];
    foreach (array_chunk($refs, 500) as $stueck) {
        $st = $pdo->prepare('SELECT client_ref, pat_blob FROM missions WHERE user_id = ? AND client_ref IN ('
                            . implode(',', array_fill(0, count($stueck), '?')) . ')');
        $st->execute(array_merge([$userId], $stueck));
        foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $z) {
            if ($z['pat_blob'] !== null && isset($soll[(string)$z['pat_blob']])) {
                $gut[(string)$z['client_ref']] = true;
            }
        }
    }
    return array_values(array_filter($refs, static fn($r) => !isset($gut[$r])));
}

/**
 * Die Blätter ablegen — IN DER TRANSAKTION DES DATENSATZES, nach ihm.
 * Wirft `SbKonflikt`; der Rahmen rollt zurück, der Endpunkt antwortet 409.
 *
 * @param list<array>  $liste  aus `sb_blaetter_lesen()`
 * @param list<string> $texte  die Blöcke, die diese Anfrage schreibt
 */
function sb_blaetter_schreiben(PDO $pdo, int $userId, array $liste, array $texte): void
{
    if (!$liste) { return; }
    if (!sb_bereit($pdo)) {
        throw new SbKonflikt(['error' => 'migration',
            'meldung' => 'Das Standbuch ist auf dieser Anlage noch nicht eingerichtet — '
                       . 'eine AdministratorIn muss update.php aufrufen. Es wurde nichts gespeichert.']);
    }
    $refs = [];
    foreach ($liste as $b) { foreach ($b['refs'] as $r) { $refs[$r] = true; } }
    $fehlt = sb_nicht_geschrieben($pdo, $userId, array_map('strval', array_keys($refs)), $texte);
    if ($fehlt) {
        throw new SbKonflikt(['error' => 'blatt', 'grund' => 'uebersprungen', 'refs' => $fehlt]);
    }
    foreach ($liste as $b) {
        $e = sb_ablegen($pdo, $userId, $b['wer'], $b['version_alt'], $b['version'], $b['text']);
        if (!$e['ok']) {
            throw new SbKonflikt(['error' => 'blatt', 'grund' => 'version', 'wer' => $b['wer'],
                                  'version' => $e['version'], 'text' => $e['text']]);
        }
    }
}
