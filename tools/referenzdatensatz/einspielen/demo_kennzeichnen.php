<?php
declare(strict_types=1);

/**
 * Das Referenzkonto der Pruefinstallation als DEMO-KONTO kennzeichnen.
 *
 * WOFUER, UND WARUM ES DIESES SKRIPT SEIT S10 BRAUCHT.
 *
 * `fixture/erzeugen.php` bricht ab, wenn die Schluesselhuelle des Kontos nicht
 * `edk1:` traegt (Backlog Nr. 155 / S10/AP5). Der Riegel ist richtig: Eine
 * `edka1:`-Huelle haengt am Server-Anteil DIESER Installation, und die Fixture
 * reist auf eine andere -- das Demo-Konto kaeme dort herein und saehe nichts.
 *
 * Welche Fassung entsteht, entscheidet `pw_handling.php` in dem Augenblick, in
 * dem das Passwort gesetzt wird -- und zwar an einer einzigen Frage:
 * `demo_ist_demo($pwUserId)`. Zeigt `app_state.demo_user_id` auf das Konto,
 * baut der Browser die Huelle OHNE Anteil (E-P1-19); sonst MIT.
 *
 * Damit steht der Neubau des Referenzbestands seit S10 vor einem Ring:
 *
 *   - Der Einspiellauf legt das Konto ueber den regulaeren Einladungsweg an
 *     (`stufe_konto`, E-P1-10) und setzt das Passwort im Browser.
 *   - Zu diesem Zeitpunkt ist es ein KONTO WIE JEDES ANDERE, also bekommt es
 *     `edka1:`.
 *   - `demo_anlegen()` im Adminbereich kann die Kennzeichnung nicht
 *     nachtragen: Es legt ein Konto AUS DER FIXTURE an und lehnt ab, solange
 *     eines mit dieser Adresse besteht.
 *   - Und ohne Kennzeichnung gibt es keine Fixture.
 *
 * Dieses Skript setzt die Kennzeichnung -- mehr nicht. Es schreibt zwei Zeilen
 * in `app_state`, beide ueber die Konstanten aus `demo_lib.php`, und ruehrt
 * weder Bestand noch Schluesselmaterial an. R4 („kein roher SQL-Weg") meint
 * die EINSATZDATEN; hier steht der Schalter der Installation.
 *
 * DIE ZWEITE ZEILE IST KEIN BEIWERK. Sobald die Kennzeichnung steht, prueft
 * `auth_guard.php` bei JEDER Anfrage des Kontos, ob ein Reset faellig ist --
 * und ohne Resetmarke ist er es sofort. Der erste Seitenaufruf des
 * Einspiellaufs wuerde den halb aufgebauten Bestand durch die ALTE Fixture
 * ersetzen. Die Marke wird deshalb in die Zukunft gesetzt; `einspielen.py`
 * und die Browserlaeufe haben danach Ruhe.
 *
 * AUFRUF (aus dem Wurzelverzeichnis, NACH `--stufen konto`, VOR
 * `passwort_setzen.mjs`):
 *
 *     php tools/referenzdatensatz/einspielen/demo_kennzeichnen.php
 *     php tools/referenzdatensatz/einspielen/demo_kennzeichnen.php --frist 172800
 *
 * NUR AUF EINER PRUEFINSTALLATION. Auf dem Produktivserver entsteht das
 * Demo-Konto ueber `demo_anlegen()` aus der Fixture, und die Kennzeichnung
 * setzt diese Funktion selbst.
 */

$wurzel = dirname(__DIR__, 3);
require_once $wurzel . '/server/db.php';
require_once $wurzel . '/server/demo_lib.php';

$adresse = 'demo@gen-em.org';
$frist   = 86400;
foreach ($argv as $i => $a) {
    if ($a === '--konto' && isset($argv[$i + 1])) { $adresse = (string)$argv[$i + 1]; }
    if ($a === '--frist' && isset($argv[$i + 1])) { $frist   = (int)$argv[$i + 1]; }
}

$pdo = db();
$st = $pdo->prepare('SELECT id, LEFT(pat_wrap_pw, 6) AS huelle FROM users WHERE email = ?');
$st->execute([$adresse]);
$konto = $st->fetch();
if ($konto === false) {
    fwrite(STDERR, "Kein Konto {$adresse}. Erst `einspielen.py --stufen konto` fahren.\n");
    exit(1);
}
$id = (int)$konto['id'];

$vorher = demo_id();
if ($vorher !== null && $vorher !== $id) {
    fwrite(STDERR, "Es ist bereits ein ANDERES Konto (#{$vorher}) als Demo-Konto "
                 . "gekennzeichnet. Erst im Adminbereich entfernen.\n");
    exit(1);
}

$pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
               ON DUPLICATE KEY UPDATE v = VALUES(v)')
    ->execute([DEMO_K_USER, (string)$id]);
/* Resetmarke in die ZUKUNFT: `demo_reset_in()` rechnet
 * DEMO_RESET_SEKUNDEN - (jetzt - Marke); eine Marke von morgen ergibt eine
 * Restzeit, die den Reset waehrend des Aufbaus nicht faellig werden laesst. */
demo_reset_marke_setzen(time() + $frist);

printf("Konto #%d (%s) ist jetzt das Demo-Konto dieser Installation.\n", $id, $adresse);
printf("Huelle: %s…  (fuer die Fixture muss sie `edk1:` sein — die Fassung "
     . "entsteht beim SETZEN des Passworts)\n", (string)$konto['huelle']);
printf("Reset ausgesetzt fuer %d s (bis %s).\n", $frist,
       date('Y-m-d H:i:s', time() + $frist + DEMO_RESET_SEKUNDEN));
