<?php
declare(strict_types=1);
require_once __DIR__ . '/../auth_guard.php';      // liefert $userId
require_once __DIR__ . '/../adminbackup_lib.php';

/**
 * Die von der Administration freigegebene Sicherung — für die NutzerIn selbst.
 *
 *   GET  api/adminbackup_freigabe.php            -> Paket oder { freigabe: null }
 *   POST api/adminbackup_freigabe.php  { eingeloest: true }
 *
 * WARUM ES DIESEN WEG ÜBERHAUPT GIBT (E5, E20)
 * Wurde ein Konto gelöscht und neu aufgesetzt, trägt es einen NEUEN
 * Inhaltsschlüssel. Die geschützten Angaben der alten Sicherung sind mit dem
 * ALTEN verschlüsselt, und der steckt in `pat_wrap_rc` — geöffnet allein vom
 * Wiederherstellungsschlüssel, den ausschliesslich die NutzerIn hat.
 * Administration kann ein solches Paket deshalb nicht einspielen: Es entstünden
 * Einträge, die niemand mehr lesen kann. Sie gibt es stattdessen frei, und der
 * Browser der NutzerIn schlüsselt um.
 *
 * DER SERVER SIEHT DABEI KEINEN KLARTEXT. Er reicht `pat_wrap_rc` und die
 * `pat_blob` unverändert als Chiffretext heraus — beides steht ohnehin schon so
 * in der Datenbank. Entpackt, entschlüsselt und neu verschlüsselt wird
 * ausschliesslich im Browser (assets/crypto.js).
 *
 * Das eigentliche Zurückschreiben läuft anschliessend über den vorhandenen Weg
 * api/backup_restore.php: Das Feld `daten` IST ein Backup der Formatversion 5,
 * und ein zweiter Rückspielpfad wäre eine zweite Stelle, an der dieselben
 * Fehler zu machen sind.
 */

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $f = edbak_freigabe_fuer($userId);
    if ($f === null) { json_out(['freigabe' => null]); }

    /* ---- Ein Teil, roh (S2/AP6) ----------------------------------------
     *
     * Ein Fassung-2-Paket geht nicht mehr in EINER Antwort heraus. Beim
     * 5000er-Konto waeren das 94 MB gewesen — und auf dem Rueckweg ein POST
     * derselben Groesse gegen ein Limit, das niemand kennt. Der Browser holt
     * Kopf, Eintragsfenster und Spurteile einzeln, genau wie beim Einspielen
     * einer eigenen Sicherung.
     *
     * Der Name wird in `edbak_paket_teil_lesen()` gegen die Teileliste des
     * Manifests geprueft; was dort nicht steht, gibt es hier nicht. */
    $teil = (string)($_GET['teil'] ?? '');
    if ($teil !== '') {
        $roh = edbak_paket_teil_lesen($f['account_key'], $f['datei'], $teil);
        if ($roh === null) {
            json_out(['error' => 'teil',
                      'meldung' => 'Dieser Teil gehört nicht zur freigegebenen '
                                 . 'Sicherung.'], 404);
        }
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: no-store');
        echo $roh;
        exit;
    }

    $paket = edbak_paket_kopf_lesen($f['account_key'], $f['datei']);
    if ($paket === null) {
        json_out(['error' => 'unlesbar',
                  'meldung' => 'Die freigegebene Sicherung lässt sich nicht lesen. '
                             . 'Bitte die Administration verständigen.'], 500);
    }
    $fassung = (int)($paket['version'] ?? 1);

    header('Cache-Control: no-store');
    $antwort = [
        'freigabe' => [
            'erstellt'       => $f['erstellt'],
            'erzeugt'        => $paket['erzeugt'] ?? null,
            'umfang'         => $paket['umfang'] ?? null,
            'herkunft_email' => $f['herkunft_email'],
            'herkunft_name'  => $f['herkunft_name'],
            /* Sagt dem Browser, ob überhaupt umgeschlüsselt werden muss.
             * Ein Paket ohne geschützte Angaben braucht keinen
             * Wiederherstellungsschlüssel — dann nach einem zu fragen wäre
             * eine Hürde ohne Zweck. */
            'braucht_schluessel' => edbak_paket_hat_geschuetzte($paket),
        ],
        'fassung'       => $fassung,
        'pat_wrap_rc'   => $paket['schluessel']['pat_wrap_rc'] ?? null,
        'pat_key_check' => $paket['schluessel']['pat_key_check'] ?? null,
    ];
    if ($fassung >= 2) {
        $antwort['eintragsteile'] = (int)($paket['eintragsteile'] ?? 0);
        $antwort['spurteile']     = (int)($paket['spurteile'] ?? 0);
        $antwort['eintraege']     = (int)($paket['eintraege'] ?? 0);
    } else {
        /* Fassung 1 geht weiterhin am Stueck heraus — sie liegt ohnehin als
         * eine Datei da, und es kommen keine neuen dazu. */
        $ganz = edbak_paket_lesen($f['account_key'], $f['datei']);
        $antwort['daten'] = $ganz['daten'] ?? null;
    }
    json_out($antwort);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_SERVER['HTTP_X_CSRF'] ?? '')) {
        json_out(['error' => 'csrf'], 403);
    }
    $f = edbak_freigabe_fuer($userId);
    if ($f === null) { json_out(['error' => 'keine_freigabe'], 404); }
    /* Als eingelöst vermerken, nicht löschen: Die Sicherung selbst bleibt
     * liegen. Sie ist die Rückfallebene, und die verliert ihren Sinn, wenn sie
     * beim ersten Gebrauch verschwindet. Widerrufbar ist ab hier nichts mehr —
     * eingelöst ist eingelöst (Akzeptanzkriterium 53 gilt für die Zeit davor). */
    edbak_freigabe_eingeloest($f['account_key']);
    json_out(['ok' => true]);
}

json_out(['error' => 'method'], 405);
