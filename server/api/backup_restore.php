<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../backup_lib.php';

/**
 * POST api/backup_restore.php
 * Body: das im Browser entsiegelte Backup-JSON. Die verschluesselten Angaben
 * hat der Browser bereits mit dem Inhaltsschluessel DIESES Kontos neu
 * verschluesselt (`pat_blob`), deshalb laesst sich ein Backup in jedes Konto
 * einspielen. Header X-CSRF muss zum Session-Token passen.
 *
 * Antwort: { ok: true, stats: {...} }
 */

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }
if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
    json_out(['error' => 'csrf'], 403);
}

$raw = file_get_contents('php://input');
if ($raw === '' || $raw === false) {
    json_out(['error' => 'leer', 'hinweis' =>
        'Es kamen keine Daten an — evtl. begrenzt der Server die Upload-Größe (post_max_size).'], 400);
}

$data = json_decode($raw, true);
if (!is_array($data) || ($data['format'] ?? '') !== 'einsatzdoku-backup') {
    json_out(['error' => 'format'], 400);
}

/* ÄLTERE NUTZLASTVERSIONEN WERDEN ABGELEHNT (E23, A10).
 *
 * Mit dem Umbau auf Diensttage hat sich der Inhalt einer Sicherung so weit
 * geaendert, dass eine Umsetzung raten muesste: Eine Nutzlast der Version 5
 * kennt keine Kennung des Flugtags (der Kalendertag WAR sie), keine Art, keinen
 * Rollensatz, keine Standortzuordnung der Stammdaten und keine Uhr-Kennungen.
 * Jede dieser Luecken liesse sich nur mit einer Annahme fuellen, und eine
 * Wiederherstellung ist der falsche Ort fuer Annahmen — wer sie startet, hat
 * meist keinen zweiten Versuch.
 *
 * Die Ablehnung ist deshalb ausdrücklich und benannt, nicht ein Fehler beim
 * Einlesen. Wer eine alte Datei hat, spielt sie in einer Installation vor
 * Web 6.0.0 ein und sichert dort neu.
 *
 * Der Container (Signatur `EDBAK2`, Verschluesselung) ist unveraendert; die
 * Datei liess sich also entsiegeln. Genau darum kommt die Meldung hier an und
 * nicht schon im Browser.
 *
 * WARUM DIE SCHRANKE BEI 6 BLEIBT, OBWOHL DIE NUTZLAST AUF 7 GESTIEGEN IST
 * (E-S1-07). Nutzlast 7 fuehrt den Papierkorb (`deleted_at`,
 * `deleted_with_day` mit Inhalt). Eine Version-6-Datei fuehrt ihn nicht — ihr
 * fehlt nichts, was sich erraten muesste, sie beschreibt schlicht einen
 * Bestand ohne geloeschte Eintraege. Sie ist damit vollstaendig einspielbar,
 * und sie abzulehnen waere Schikane.
 *
 * Umgekehrt kennzeichnet der Sprung nur, er SPERRT NICHT: Ein bereits
 * ausgelieferter Stand mit derselben Schranke nimmt eine Version-7-Datei an
 * und braechte deren Papierkorb als aktiven Bestand zurueck, weil er
 * `deleted_at` nicht auswertet. Das ist nachtraeglich nicht zu reparieren und
 * steht als Warnung in docs/Backup-Format.md 4. */
$nutzlast = isset($data['version']) ? (int)$data['version'] : 0;
if ($nutzlast < 6) {
    json_out(['error' => 'version_alt',
              'meldung' => 'Diese Sicherung hat das Format ' . $nutzlast
                         . ' und stammt aus einer Fassung vor der Umstellung auf '
                         . 'Diensttage (Web 6.0.0). Sie lässt sich nicht mehr '
                         . 'einspielen: Ihr fehlen Angaben, die sich nicht '
                         . 'erraten lassen — die Kennung des Diensttags, die Art '
                         . 'des Rettungsmittels, der Rollensatz, der Standort '
                         . 'der Stammdaten und die Kennungen der Uhr. Es wurde '
                         . 'nichts geändert. Bitte in einer Installation vor '
                         . 'Web 6.0.0 einspielen und dort neu sichern.'], 409);
}

try {
    $stats = edbak_restore($userId, $data);
    json_out(['ok' => true, 'stats' => $stats]);
} catch (Throwable $ex) {
    json_fehler($ex, 'restore');
}
