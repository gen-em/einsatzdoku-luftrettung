<?php
declare(strict_types=1);

/**
 * DAS SCHLÜSSELPAAR DES KONTOS (S11-03; Konzept S11 3.1, E-S11-12, -14, -17)
 * ===========================================================================
 *
 * WAS ES IST. Uhr und Handy verschließen Spur, Phasenkoordinaten und
 * Reanimation vor dem Upload in eine Gerätehülle (Format: Kopf von
 * `tools/proben/huelle/referenz.py`). Dafür brauchen sie einen ÖFFENTLICHEN
 * Schlüssel des Kontos; den PRIVATEN hat nur der Browser nach dem Entsperren.
 * Ein ECDH-Paar auf P-256, erzeugt im Browser (`assets/kontopaar.js`):
 *
 *   kp_oeffentlich  der öffentliche Teil (SPKI, Base64), VERSIEGELT mit dem
 *                   Serverschlüssel, Zweck `kontopaar|<Kontonummer>`
 *   kp_privat       der private Teil (PKCS8) unter dem INHALTSSCHLÜSSEL,
 *                   `edk1:` — nie `edka1:` (`KP_PRIVAT_RE`)
 *   kp_seit         wann es entstand
 *
 * WARUM VERSIEGELT (E-S11-14, F-S11-04). Wer nur die Datenbank beschreiben
 * kann — ein Abzug mit Schreibrecht, eine Einspielung —, könnte sonst seinen
 * eigenen öffentlichen Schlüssel eintragen. Jedes Gerät, das danach koppelt,
 * verschlösse für ihn, und er läse die Ortsdaten aus derselben Datenbank. Das
 * Siegel braucht den Serverschlüssel aus `config.php`; der Zweck bindet es an
 * das Konto, sodass sich ein gültiges Siegel nicht in eine andere Zeile
 * umhängen lässt. Ausgegeben wird der Schlüssel NUR mit gültigem Siegel
 * (`kp_oeffentlich_lesen()`). Gegen jemanden, der den Server übernimmt (C1),
 * hilft das nicht — er hat den Serverschlüssel und tauscht `crypto.js`
 * gleich mit; diese Grenze nennt das Bedrohungsmodell. Der zweite Riegel ist
 * der Abgleich beim Entsperren: Der Browser leitet aus dem privaten Teil den
 * öffentlichen ab und meldet eine Abweichung (`kontopaar.js`).
 *
 * WARUM AM INHALTSSCHLÜSSEL UND NICHT AM SERVER-ANTEIL — wie `rw_privat`
 * (`docs/Technik.md` 4.99q): Der Wiederherstellungsschlüssel muss das Paar
 * allein öffnen. Ginge der Anteil verloren, wären sonst mit dem Paar alle
 * Ortsdaten des Kontos verloren.
 *
 * WER ES ANLEGT. Nur, wer das Passwort nachweist (`api/kontopaar.php`, mit dem
 * Token aus dem Vormerkfach der Anmeldung — wie beim Rückweg). Sonst könnte
 * eine übernommene Sitzung ihr eigenes Paar ablegen.
 *
 * WENN DER SERVERSCHLÜSSEL WECHSELT, hüllt der Job `schluesselwechsel` das
 * Siegel um (Zweck `kontopaar`, `schluesselwechsel_lib.php`).
 */

require_once __DIR__ . '/db.php';
require_once __DIR__ . '/serverkrypto_lib.php';

/** Die Kennung des Siegels — an das Konto gebunden. */
function kp_zweck(int $userId): string
{
    return 'kontopaar|' . $userId;
}

/**
 * Ein öffentlicher Teil, den das Konto annimmt? Null heißt ja; sonst der
 * Grund in einem Satz. Form (`KP_OEFFENTLICH_RE`) und Kurve P-256 — über
 * denselben Lader wie der Rückweg (phpseclib, `rw_oeffentlich_laden()`):
 * ein Prüfweg, nicht zwei.
 */
function kp_oeffentlich_pruefen(string $spkiB64): ?string
{
    require_once __DIR__ . '/validate_lib.php';
    require_once __DIR__ . '/rueckweg_lib.php';
    if (!preg_match(KP_OEFFENTLICH_RE, $spkiB64)) {
        return 'Der öffentliche Schlüssel hat ein unbrauchbares Format.';
    }
    if (rw_oeffentlich_laden($spkiB64) === null) {
        return 'Der öffentliche Schlüssel ist keiner auf der Kurve P-256.';
    }
    return null;
}

/**
 * Der öffentliche Teil als 65 Byte SEC1 (0x04 ‖ X ‖ Y) in Base64 — so, wie
 * Uhr und Handy ihn für die Hülle brauchen. SPKI für P-256 endet immer mit
 * genau diesem Punkt; geprüft wird trotzdem, statt die Länge anzunehmen.
 */
function kp_sec1(string $spkiB64): ?string
{
    $der = base64_decode($spkiB64, true);
    if ($der === false || strlen($der) < 65) { return null; }
    $punkt = substr($der, -65);
    return $punkt[0] === "\x04" ? base64_encode($punkt) : null;
}

/**
 * Stand des Paars für die Seite: 'da', 'fehlt', 'spalten' (Migration steht
 * aus) oder 'siegel' (ein Paar ist da, aber das Siegel geht nicht auf — mit
 * diesem Serverschlüssel nicht versiegelt, oder von Hand eingetragen).
 * Dazu der geöffnete öffentliche Teil und der private Chiffretext.
 *
 * Ein SELECT mit Rückfall wie `rw_zustand()`: Nur die fehlende Spalte ist
 * 'spalten', jeder andere Fehler der Datenbank bricht ab.
 *
 * @return array{stand: string, oeffentlich: ?string, privat: ?string}
 */
function kp_zustand(int $userId, ?PDO $pdo = null): array
{
    try {
        $st = ($pdo ?? db())->prepare('SELECT kp_oeffentlich, kp_privat FROM users WHERE id = ?');
        $st->execute([$userId]);
        $z = $st->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $ex) {
        if ((string)$ex->getCode() !== '42S22') { throw $ex; }
        return ['stand' => 'spalten', 'oeffentlich' => null, 'privat' => null];
    }
    if (!$z || $z['kp_oeffentlich'] === null || $z['kp_oeffentlich'] === '') {
        return ['stand' => 'fehlt', 'oeffentlich' => null, 'privat' => null];
    }
    $offen = kp_oeffentlich_lesen($userId, (string)$z['kp_oeffentlich']);
    return ['stand' => $offen === null ? 'siegel' : 'da', 'oeffentlich' => $offen,
            'privat' => $offen === null ? null : (string)$z['kp_privat']];
}

/**
 * Das Siegel öffnen und den öffentlichen Teil zurückgeben — oder null.
 * Geht das Siegel nicht auf oder ist der Inhalt kein P-256-Schlüssel, wird
 * es gemeldet (Reiter System): Das ist entweder ein Schlüsselwechsel, der
 * dieses Stück nicht umgehüllt hat, oder ein Eingriff in die Datenbank —
 * beides will die Betreiberin wissen. Ausgegeben wird dann NICHTS.
 */
function kp_oeffentlich_lesen(int $userId, string $versiegelt): ?string
{
    $klar = sk_oeffnen($versiegelt, kp_zweck($userId));
    if ($klar === null || kp_oeffentlich_pruefen($klar) !== null) {
        require_once __DIR__ . '/systemmeldung_lib.php';
        system_melden('kontopaar', 'Das Siegel des öffentlichen Kontoschlüssels geht nicht auf — '
            . 'der Schlüssel wird nicht ausgegeben.', null, ['konto' => $userId]);
        return null;
    }
    return $klar;
}

/** Der öffentliche Teil für ein Gerät (SEC1, Base64) — oder null. */
function kp_fuer_geraet(PDO $pdo, int $userId): ?string
{
    $z = kp_zustand($userId, $pdo);
    return $z['stand'] === 'da' ? kp_sec1((string)$z['oeffentlich']) : null;
}

/**
 * Das Paar ablegen — nur, wenn noch keins da ist. Die Bedingung steht im
 * UPDATE selbst: Zwei Fenster, die gleichzeitig anlegen, legen genau eines
 * ab. Ersetzt wird nicht: Ein neues Paar machte alle Hüllen unter dem alten
 * unlesbar; einen Weg dafür gibt es erst, wenn ein Anlass ihn verlangt.
 *
 * @return bool true, wenn genau dieses Paar jetzt abgelegt ist
 */
function kp_ablegen(PDO $pdo, int $userId, string $spkiB64, string $privat): bool
{
    $up = $pdo->prepare('UPDATE users SET kp_oeffentlich = ?, kp_privat = ?, kp_seit = UTC_TIMESTAMP()
                          WHERE id = ? AND kp_oeffentlich IS NULL');
    $up->execute([sk_versiegeln($spkiB64, kp_zweck($userId)), $privat, $userId]);
    return $up->rowCount() === 1;
}
