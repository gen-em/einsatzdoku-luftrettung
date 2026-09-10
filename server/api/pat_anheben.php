<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../validate_lib.php';    // pruef_pat_blob()

/**
 * api/pat_anheben.php — stille Anhebung der Einsatz-Notizen (S9/AP7, E-S9-01)
 *
 * WAS HIER GESCHIEHT
 * Bis Web 19.0.0 lagen die Notizen des Einsatzes als Klartext in der Spalte
 * `missions.notes`. Sie gehoeren seither in den Ende-zu-Ende-verschluesselten
 * `pat_blob` — und dorthin kann sie nur der Browser bringen, denn nur er hat
 * den Schluessel. Dieser Endpunkt ist die beiden Haelften dieses Umzugs:
 *
 *   GET  liefert die Einsaetze DIESES Kontos, die noch Klartext in der Spalte
 *        haben: { id, notes, pat_blob }. Hoechstens GRENZE je Aufruf.
 *   POST nimmt je Einsatz den NEUEN Blob entgegen und setzt die Spalte auf
 *        NULL — in EINER Transaktion, mit einer Wache je Zeile.
 *
 * Antwort auf POST: { ok: true, angehoben: n, uebersprungen: m }
 *
 * ---- WARUM DER SERVER NICHTS SELBST VERSCHLUESSELN KANN -------------------
 *
 * Das ist der ganze Punkt der Zusage: Der Inhaltsschluessel liegt in der
 * Schluesselhuelle des Kontos und wird aus dem Passwort abgeleitet. Der Server
 * kennt ihn nicht und soll ihn nicht kennen. Ein Konto, das sich nie
 * entsperrt, behaelt deshalb seinen Klartext in der Spalte — derselbe Zustand
 * wie vor Web 19, nicht schlechter, und er verschwindet beim naechsten
 * Entsperren.
 *
 * ---- DIE WACHE JE ZEILE, UND WARUM SIE NOETIG IST -------------------------
 *
 * `missions` fuehrt KEIN `updated_at` und keine Fassungsspalte. Zwei offene
 * Fenster koennten denselben Einsatz gleichzeitig anheben — oder eines hebt
 * an, waehrend im anderen das Formular gespeichert wird. Der Blob wird immer
 * VOLLSTAENDIG ersetzt; die zweite Schreibung wuerde die erste stillschweigend
 * ueberschreiben, samt einer inzwischen geaenderten Diagnose.
 *
 * Deshalb traegt jedes UPDATE zwei Bedingungen: `notes IS NOT NULL` (es ist
 * noch nicht angehoben) und `pat_blob <=> ?` (der Blob ist noch genau der,
 * den dieser Browser gelesen und geoeffnet hat). `<=>` statt `=`, weil der
 * Blob NULL sein darf und `NULL = NULL` nicht wahr ist. Trifft die Bedingung
 * nicht zu, geschieht nichts und die Zeile zaehlt als uebersprungen — der
 * naechste Aufruf holt sie.
 *
 * ---- WAS DIESER ENDPUNKT AUSDRUECKLICH NICHT TUT -------------------------
 *
 * Er setzt NICHT `manual = 1` und NICHT `edited = 1`. Das Einsatzformular tut
 * das bei jedem Speichern, und `ingest.php` hoert bei `manual = 1` auf,
 * Metadaten, Phasen und Reanimationen der Uhr zu uebernehmen. Ein Anhebelauf,
 * der den Formularweg nachbaute, wuerde den GESAMTEN Altbestand eines Kontos
 * still gegen die Uhr einfrieren — beim naechsten Entsperren, ungefragt, ohne
 * dass irgendjemand einen Einsatz angefasst haette. Geschrieben werden genau
 * zwei Spalten: `pat_blob` und `notes`.
 *
 * ---- WARUM ES KEINEN MERKER GIBT -----------------------------------------
 *
 * „Einmal je Konto" ist die Wirkung, nicht der Mechanismus: Sobald kein
 * Einsatz mehr Klartext in der Spalte hat, liefert GET eine leere Liste und
 * der Browser hoert auf. Ein gespeicherter Merker haette dieselbe Wirkung,
 * kostete aber eine Spalte samt Migration — UND er waere falsch, sobald wieder
 * Klartext hereinkommt: Eine eingespielte Sicherung mit Nutzlast 10, ein
 * CSV-Import einer alten Datei oder das Zuruecksetzen des Demo-Kontos tun
 * genau das. Der abgeleitete Zustand kennt diesen Fall von selbst.
 *
 * ---- WAS BEI EINEM FEHLSCHLAG PASSIERT -----------------------------------
 *
 * Nichts. Die Transaktion faellt zurueck, der Klartext bleibt in der Spalte,
 * und der naechste Entsperrvorgang versucht es erneut. Der Browser meldet
 * einen Fehlschlag nicht: Die Anhebung laeuft ungefragt im Hintergrund, und
 * eine Stoerungsmeldung stuende dort, wo die NutzerIn nichts angefordert hat.
 */

/** Hoechstens so viele Einsaetze je Aufruf — der Browser ruft in Runden. */
const PAT_ANHEBEN_GRENZE = 200;

$pdo = db();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    /* Auch geloeschte Einsaetze (Papierkorb) werden angehoben: Ihr Klartext
     * liegt genauso in der Spalte, und wer sie wiederherstellt, bekaeme sonst
     * einen Einsatz mit Klartextnotiz zurueck. */
    $st = $pdo->prepare('SELECT id, notes, pat_blob
                           FROM missions
                          WHERE user_id = ? AND notes IS NOT NULL AND notes <> \'\'
                          ORDER BY id
                          LIMIT ' . PAT_ANHEBEN_GRENZE);
    $st->execute([$userId]);
    $liste = [];
    foreach ($st->fetchAll() as $m) {
        $liste[] = [
            'id'       => (int)$m['id'],
            'notes'    => (string)$m['notes'],
            'pat_blob' => $m['pat_blob'] !== null ? (string)$m['pat_blob'] : null,
        ];
    }
    /* `offen` sagt dem Browser, ob eine weitere Runde noetig ist — sonst
     * muesste er raten oder immer eine Leerrunde anhaengen. */
    $offenQ = $pdo->prepare('SELECT COUNT(*) FROM missions
                              WHERE user_id = ? AND notes IS NOT NULL AND notes <> \'\'');
    $offenQ->execute([$userId]);
    json_out(['missions' => $liste, 'offen' => (int)$offenQ->fetchColumn()]);
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_out(['error' => 'method'], 405); }

if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
    json_out(['error' => 'csrf'], 403);
}

$b = json_decode((string)file_get_contents('php://input'), true);
if (!is_array($b) || !isset($b['missions']) || !is_array($b['missions'])) {
    json_out(['error' => 'format'], 400);
}
if (count($b['missions']) > PAT_ANHEBEN_GRENZE) { json_out(['error' => 'zu_viele'], 400); }

/* Die Bloecke werden VOR der Transaktion geprueft — dieselbe Pruefschicht wie
 * im Formular (CLAUDE.md 4: alle Schreibwege, ohne Ausnahme). Ein unpassender
 * Wert bricht den ganzen Lauf ab, statt eine halbe Anhebung zu hinterlassen. */
$posten = [];
foreach ($b['missions'] as $m) {
    if (!is_array($m)) { json_out(['error' => 'format'], 400); }
    $id = (int)($m['id'] ?? 0);
    if ($id <= 0) { json_out(['error' => 'id'], 400); }
    $geprueft = new Pruefliste();
    $blob = pruef_pat_blob((string)($m['pat_blob'] ?? ''), 'Geschützte Angaben', $geprueft);
    if ($blob === null) { json_out(['error' => 'blob', 'feld' => $geprueft->text()], 422); }
    $posten[] = [
        'id'       => $id,
        'blob'     => $blob,
        'blob_alt' => (isset($m['blob_alt']) && $m['blob_alt'] !== null && $m['blob_alt'] !== '')
                      ? (string)$m['blob_alt'] : null,
    ];
}

$angehoben = 0; $uebersprungen = 0;
try {
    $pdo->beginTransaction();
    $upd = $pdo->prepare('UPDATE missions
                             SET pat_blob = ?, notes = NULL
                           WHERE id = ? AND user_id = ?
                             AND notes IS NOT NULL AND notes <> \'\'
                             AND pat_blob <=> ?');
    foreach ($posten as $p) {
        $upd->execute([$p['blob'], $p['id'], $userId, $p['blob_alt']]);
        if ($upd->rowCount() === 1) { $angehoben++; } else { $uebersprungen++; }
    }
    $pdo->commit();
} catch (Throwable $e) {
    if ($pdo->inTransaction()) { $pdo->rollBack(); }
    json_out(['error' => 'schreiben'], 500);
}

json_out(['ok' => true, 'angehoben' => $angehoben, 'uebersprungen' => $uebersprungen]);
