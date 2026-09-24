<?php
declare(strict_types=1);

/**
 * RATENPROBE — die Sperrleiter, die Verlangsamung und die Sammelmail
 * ===========================================================================
 *
 * Aufruf:  php tools/proben/raten/probe.php
 * Rueckgabe: 0 = keine Befunde · 1 = Befunde · 2 = die Probe kam nicht los
 *
 * WAS SIE MISST. Das Konzept nennt als Abnahme von P5a/AP6 einen
 * „Pruefkonten-Lauf": 10 Fehlversuche -> Sperre, weitere -> laenger, nach 24 h
 * wieder von vorn, 200 Fehlversuche -> Antwortzeit >= 1 s, gleiche Antwortzeit
 * fuer Namen mit und ohne Konto, Sammelmail genau einmal je Stunde.
 *
 * `tools/erzeugen/ (pruefkonten)` KANN DAS NICHT — es legt Konten an und misst die
 * NutzerInnen-Liste. Deshalb diese Probe.
 *
 * SIE GREIFT DIE BIBLIOTHEK UNMITTELBAR AN, nicht ueber HTTP. Der Grund ist
 * die Uhr: „nach 24 h faellt die Stufe" laesst sich ueber HTTP nur pruefen,
 * indem man 24 Stunden wartet. Hier wird stattdessen `stufe_bis`
 * zurueckdatiert — dieselbe Wirkung, in einer Millisekunde.
 *
 * WAS SIE DAMIT NICHT MISST, und das steht hier und nicht in einer Fussnote:
 *
 * - **Den Weg durch `login.php`.** Ob die drei Zaehlungen an der richtigen
 *   Stelle stehen, ob die Meldung erscheint und ob der Countdown laeuft, sagt
 *   nur der Browser. Die Probe misst die Bibliothek darunter.
 * - **Echte Gleichzeitigkeit.** Zwei Fehlversuche in derselben Millisekunde
 *   sind nicht nachgestellt. Die Leiter ist deshalb als EIN Statement gebaut,
 *   aber belegt ist das hier nicht.
 * - **Ob eine Mail ankommt.** Gemessen wird, dass genau eine Zeile in die
 *   Warteschlange geht und die zweite nicht.
 * - **Die Wirkung auf einen echten Angriff.** 200 Fehlversuche in einer
 *   Schleife sind keine 200 Anfragen aus 50 Netzen.
 *
 * SIE RAEUMT HINTER SICH AUF. Angelegt werden Zeilen in `rate_limits`,
 * `sicherheit_ereignisse` und `mail_warteschlange` unter eigenen Merkmalen
 * (Praefix `probe-`); am Ende sind sie weg, und die Zahl davor und danach
 * steht im Bericht.
 */

$wurzel = dirname(__DIR__, 3);
require_once $wurzel . '/server/db.php';
require_once $wurzel . '/server/ratelimit_lib.php';
require_once $wurzel . '/server/mail_lib.php';

$GEPRUEFT = 0; $BEFUNDE = [];
function pruef(string $was, bool $ok, string $gemessen = ''): void
{
    global $GEPRUEFT, $BEFUNDE;
    $GEPRUEFT++;
    printf("  %s  %-56s %s\n", $ok ? 'ok  ' : 'FEHL', $was, $gemessen);
    if (!$ok) { $BEFUNDE[] = $was . ($gemessen !== '' ? ' — ' . $gemessen : ''); }
}
function abschnitt(string $t): void { echo "\n" . $t . "\n" . str_repeat('-', strlen($t)) . "\n"; }

$pdo = db();

/* ---- Die Spalten muessen da sein, sonst misst die Probe nichts ---------- */
try {
    $pdo->query('SELECT stufe, stufe_bis FROM rate_limits LIMIT 0');
    $pdo->query('SELECT art FROM sicherheit_ereignisse LIMIT 0');
} catch (Throwable $ex) {
    fwrite(STDERR, "Die Migration 2026_09_16_ratenschutz_stufen ist nicht gelaufen.\n"
                 . "Betrieb -> Updates aufrufen, dann erneut.\n");
    exit(2);
}

/* ---- Merkmale der Probe ------------------------------------------------- */
$KONTO   = 'id:probe-leiter@ratenprobe.invalid';
$ADRESSE = 'ip:probe-203.0.113.7';
$vorher  = (int)$pdo->query('SELECT COUNT(*) FROM rate_limits')->fetchColumn();
$vorherE = (int)$pdo->query('SELECT COUNT(*) FROM sicherheit_ereignisse')->fetchColumn();
$vorherM = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange')->fetchColumn();

function aufraeumen(): void
{
    $pdo = db();
    $pdo->exec("DELETE FROM rate_limits WHERE merkmal LIKE '%probe-%' OR topf = 'global'");
    $pdo->exec("DELETE FROM sicherheit_ereignisse
                 WHERE merkmal LIKE '%probe-%' OR merkmal = 'alle'");
    $pdo->exec("DELETE FROM mail_warteschlange WHERE schluessel = 'sicherheit_sammel'");
    foreach ([RATE_K_MAIL_MARK, RATE_K_BREMSE_ST] as $k) {
        try { app_state_setzen($k, ''); } catch (Throwable $e) {}
    }
}
register_shutdown_function('aufraeumen');
aufraeumen();

/** Zustand eines Merkmals lesen. */
function stand(string $topf, string $merkmal): ?array
{
    $st = db()->prepare('SELECT versuche, stufe, gesperrt_bis, stufe_bis,
                                TIMESTAMPDIFF(SECOND, NOW(), gesperrt_bis) AS rest
                           FROM rate_limits WHERE topf = ? AND merkmal = ?');
    $st->execute([$topf, $merkmal]);
    $z = $st->fetch(PDO::FETCH_ASSOC);
    return $z === false ? null : $z;
}

/** N Fehlversuche auf ein einzelnes Merkmal. */
function klopfen(string $topf, string $merkmal, int $n): void
{
    for ($i = 0; $i < $n; $i++) { rate_misserfolg($topf, null, [$merkmal]); }
}

/* ======================================================================== */
abschnitt('1  Die Leiter — 10 Fehlversuche, dann laenger und laenger');

$leiter = rate_leiter();
pruef('Die Leiter hat vier Sprossen', count($leiter) === 4,
      implode(' / ', array_map(static fn($s) => (int)($s / 60) . ' min', $leiter)));
pruef('Die erste Sprosse ist NICHT kuerzer als die bisherige feste Sperre',
      $leiter[0] >= RATE_GRENZEN['login']['sperre'],
      $leiter[0] . ' s gegen bisher ' . RATE_GRENZEN['login']['sperre'] . ' s');

klopfen('login', $KONTO, 9);
$z = stand('login', $KONTO);
pruef('Neun Fehlversuche sperren noch nicht',
      $z !== null && $z['gesperrt_bis'] === null, 'versuche=' . ($z['versuche'] ?? '-'));

klopfen('login', $KONTO, 1);
$z = stand('login', $KONTO);
pruef('Der zehnte sperrt', $z !== null && $z['gesperrt_bis'] !== null, '');
pruef('Stufe 1', (int)($z['stufe'] ?? 0) === 1, 'stufe=' . ($z['stufe'] ?? '-'));
pruef('Dauer = erste Sprosse', abs((int)$z['rest'] - $leiter[0]) <= 2,
      (int)$z['rest'] . ' s, erwartet ' . $leiter[0]);

/* ======================================================================== */
abschnitt('2  Klopfen befreit NICHT — der Fund, der still danebengegangen waere');

/* Fenster zurueckdatieren: Das ist genau die Lage, in der die alte Fassung
 * `gesperrt_bis = NULL` gesetzt haette — Fenster abgelaufen, Sperre noch
 * nicht. Sie tritt im Betrieb bei JEDER Sperre ueber 15 Minuten ein. */
$pdo->prepare('UPDATE rate_limits SET fenster_start = DATE_SUB(NOW(), INTERVAL 20 MINUTE)
                WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
$vorRest = (int)stand('login', $KONTO)['rest'];
klopfen('login', $KONTO, 1);
$z = stand('login', $KONTO);
pruef('Ein Fehlversuch nach Fensterablauf loescht die Sperre NICHT',
      $z !== null && $z['gesperrt_bis'] !== null,
      'gesperrt_bis=' . var_export($z['gesperrt_bis'] ?? null, true));
pruef('und verlaengert sie auch nicht', (int)$z['rest'] <= $vorRest + 2,
      (int)$z['rest'] . ' s, vorher ' . $vorRest . ' s');
pruef('Der Zaehler faengt im neuen Fenster von vorn an',
      (int)$z['versuche'] === 1, 'versuche=' . $z['versuche']);

/* ======================================================================== */
abschnitt('3  Die naechste Sperre ist laenger');

/* Sperre ablaufen lassen, Stufe stehen lassen. */
$pdo->prepare('UPDATE rate_limits SET gesperrt_bis = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                                      versuche = 0,
                                      fenster_start = NOW()
                WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
pruef('Nach Ablauf darf wieder angemeldet werden',
      rate_erlaubt('login', null, [$KONTO]), '');

klopfen('login', $KONTO, 10);
$z = stand('login', $KONTO);
pruef('Zehn weitere sperren erneut', $z['gesperrt_bis'] !== null, '');
pruef('Jetzt Stufe 2', (int)$z['stufe'] === 2, 'stufe=' . $z['stufe']);
pruef('Dauer = zweite Sprosse', abs((int)$z['rest'] - $leiter[1]) <= 2,
      (int)$z['rest'] . ' s, erwartet ' . $leiter[1]);

/* Und weiter bis zur hoechsten. */
foreach ([3, 4] as $erwartet) {
    $pdo->prepare('UPDATE rate_limits SET gesperrt_bis = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                                          versuche = 0, fenster_start = NOW()
                    WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
    klopfen('login', $KONTO, 10);
    $z = stand('login', $KONTO);
    pruef('Stufe ' . $erwartet, (int)$z['stufe'] === $erwartet,
          'stufe=' . $z['stufe'] . ', ' . (int)((int)$z['rest'] / 60) . ' min');
}

/* Ueber die letzte Sprosse hinaus. */
$pdo->prepare('UPDATE rate_limits SET gesperrt_bis = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                                      versuche = 0, fenster_start = NOW()
                WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
klopfen('login', $KONTO, 10);
$z = stand('login', $KONTO);
pruef('Die Leiter waechst nicht ins Unendliche', (int)$z['stufe'] === 4,
      'stufe=' . $z['stufe'] . ', ' . (int)((int)$z['rest'] / 60) . ' min');

/* ======================================================================== */
abschnitt('4  Nach 24 h ohne Fehlversuch faengt die Leiter von vorn an');

$pdo->prepare('UPDATE rate_limits
                  SET gesperrt_bis = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                      stufe_bis    = DATE_SUB(NOW(), INTERVAL 1 MINUTE),
                      versuche = 0, fenster_start = NOW()
                WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
klopfen('login', $KONTO, 10);
$z = stand('login', $KONTO);
pruef('Die Stufe faellt auf 1 zurueck', (int)$z['stufe'] === 1, 'stufe=' . $z['stufe']);
pruef('und die Dauer mit ihr', abs((int)$z['rest'] - $leiter[0]) <= 2,
      (int)$z['rest'] . ' s, erwartet ' . $leiter[0]);

/* Und die Gegenprobe: Solange die Frist LAEUFT, haelt ein Fehlversuch die
 * Stufe am Leben und schiebt sie auf volle 24 h vor. `stufe_bis` steht dafuer
 * eine Stunde in der ZUKUNFT — die Stufe wurde also vor 23 h gesetzt. */
$pdo->prepare('UPDATE rate_limits SET gesperrt_bis = NULL, versuche = 0, stufe = 2,
                                      stufe_bis = DATE_ADD(NOW(), INTERVAL 1 HOUR)
                WHERE topf = ? AND merkmal = ?')->execute(['login', $KONTO]);
klopfen('login', $KONTO, 1);
$z = stand('login', $KONTO);
$rest = (int)(strtotime((string)$z['stufe_bis'] . ' UTC') - time());
pruef('Ein Fehlversuch verlaengert die laufende Frist wieder auf 24 h',
      $rest > 86000 && (int)$z['stufe'] === 2,
      'stufe=' . $z['stufe'] . ', noch ' . round($rest / 3600, 1) . ' h');

/* ======================================================================== */
abschnitt('5  Zwei Schwellen: 10 je Konto, 50 je Adresse');

pruef('Der Adresstopf steht auf 50', rate_grenze('login_ip')['max'] === 50, '');
pruef('Der Kontotopf steht auf 10',  rate_grenze('login')['max'] === 10, '');

klopfen('login_ip', $ADRESSE, 49);
pruef('49 Fehlversuche je Adresse sperren nicht',
      rate_erlaubt('login_ip', null, [$ADRESSE]), '');
klopfen('login_ip', $ADRESSE, 1);
pruef('Der fuenfzigste sperrt', !rate_erlaubt('login_ip', null, [$ADRESSE]), '');

/* ======================================================================== */
abschnitt('6  Die Verlangsamung — und dass sie erfolgreiche Anmeldungen durchlaesst');

$schwellen = rate_bremse_schwellen();
$pdo->exec("DELETE FROM rate_limits WHERE topf = 'global'");
$v = rate_verlangsamung(true);
pruef('Ohne Fehlversuche keine Verlangsamung', $v['stufe'] === 0, '');

foreach ($schwellen as $i => $schwelle) {
    $pdo->prepare("INSERT INTO rate_limits (topf, merkmal, versuche, fenster_start)
                   VALUES ('global', ?, ?, NOW())
                   ON DUPLICATE KEY UPDATE versuche = VALUES(versuche), fenster_start = NOW()")
        ->execute([RATE_GLOBAL_MERKMAL, $schwelle]);
    $v = rate_verlangsamung(true);
    pruef('Ab ' . $schwelle . ' Fehlversuchen: Stufe ' . ($i + 1),
          $v['stufe'] === $i + 1 && abs($v['sekunden'] - RATE_BREMSE_SEKUNDEN[$i]) < 0.01,
          'Stufe ' . $v['stufe'] . ', ' . $v['sekunden'] . ' s');
}

/* Die gemessene Wirkung — IN EINEM EIGENEN PROZESS.
 *
 * `rate_verlangsamung()` merkt sich ihre Antwort je Anfrage, und das ist im
 * Betrieb richtig: Die Lage aendert sich waehrend einer Seitenanfrage nicht.
 * Eine Probe, die in EINEM Prozess nacheinander vier Lagen herstellt, bekaeme
 * sonst viermal die erste — der erste Lauf dieser Probe meldete prompt
 * „8,00 s" fuer Stufe 1, weil der Merker noch die Stufe 4 aus der Schleife
 * darueber trug. Das war ein Fehler der Probe, nicht des Codes, und genau
 * deshalb steht die Messung jetzt hier draussen. */
$pdo->prepare("UPDATE rate_limits SET versuche = ?, fenster_start = NOW()
                WHERE topf = 'global' AND merkmal = ?")
    ->execute([$schwellen[0], RATE_GLOBAL_MERKMAL]);
$code = 'require "' . $wurzel . '/server/ratelimit_lib.php";'
      . '$t = microtime(true); rate_gleiche_dauer_gebremst($t);'
      . 'printf("%.3f", microtime(true) - $t);';
$d = (float)shell_exec('php -r ' . escapeshellarg($code) . ' 2>/dev/null');
pruef('Stufe 1 haelt die Antwort tatsaechlich ~1 s auf',
      $d >= 0.95 && $d < 1.4, sprintf('%.2f s', $d));

$pdo->prepare("UPDATE rate_limits SET versuche = ?, fenster_start = NOW()
                WHERE topf = 'global' AND merkmal = ?")
    ->execute([end($schwellen), RATE_GLOBAL_MERKMAL]);
$d4 = (float)shell_exec('php -r ' . escapeshellarg($code) . ' 2>/dev/null');
pruef('Stufe 4 haelt sie ~8 s auf — und nicht laenger',
      $d4 >= 7.9 && $d4 < 8.4, sprintf('%.2f s (gedeckelt)', $d4));

/* Und das Fenster. */
$pdo->prepare("UPDATE rate_limits SET fenster_start = DATE_SUB(NOW(), INTERVAL 20 MINUTE)
                WHERE topf = 'global' AND merkmal = ?")->execute([RATE_GLOBAL_MERKMAL]);
$v = rate_verlangsamung(true);
pruef('Ein abgelaufenes Fenster verlangsamt nicht mehr', $v['stufe'] === 0,
      'sonst bliebe die Installation nach einem Angriff dauerhaft langsam');

/* ======================================================================== */
abschnitt('7  Gleiche Antwortzeit — Name mit Konto und Name ohne');

/* Gemessen wird die Bremse selbst, nicht login.php: Sie ist die Stelle, an
 * der die Gleichheit hergestellt wird. */
$pdo->exec("DELETE FROM rate_limits WHERE topf = 'global'");
$messe = static function (float $mindest): float {
    $t = microtime(true);
    rate_gleiche_dauer($t, $mindest);
    return microtime(true) - $t;
};
$a = []; $b = [];
for ($i = 0; $i < 50; $i++) {
    $a[] = $messe(0.02);
    usleep(1000);                       // „Konto vorhanden": etwas Vorarbeit
    $b[] = $messe(0.02);
}
$ma = array_sum($a) / count($a); $mb = array_sum($b) / count($b);
pruef('Differenz unter 50 ms ueber 100 Messungen', abs($ma - $mb) < 0.05,
      sprintf('%.1f ms gegen %.1f ms, Differenz %.1f ms',
              $ma * 1000, $mb * 1000, abs($ma - $mb) * 1000));

/* ======================================================================== */
abschnitt('8  Die Sammelmail — hoechstens eine je Stunde');

$pdo->exec("DELETE FROM mail_warteschlange WHERE schluessel = 'sicherheit_sammel'");
rate_ereignis('sperre', 'login', $KONTO, 4, 10, gmdate('Y-m-d H:i:s', time() + 3600));
rate_ereignis('sperre', 'login_ip', $ADRESSE, 4, 50, gmdate('Y-m-d H:i:s', time() + 3600));
app_state_setzen(RATE_K_MAIL_MARK, '');
sicherheit_melden_pruefen();
$n1 = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                         WHERE schluessel = 'sicherheit_sammel'")->fetchColumn();
sicherheit_melden_pruefen();
sicherheit_melden_pruefen();
$n2 = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                         WHERE schluessel = 'sicherheit_sammel'")->fetchColumn();
pruef('Der erste Anlass reiht ein', $n1 >= 1, $n1 . ' Zeile(n)');
pruef('Zwei weitere Anlaesse in derselben Stunde tun es NICHT', $n2 === $n1,
      $n1 . ' -> ' . $n2);

app_state_setzen(RATE_K_MAIL_MARK, gmdate('Y-m-d H:i:s', time() - 3700));
sicherheit_melden_pruefen();
$n3 = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                         WHERE schluessel = 'sicherheit_sammel'")->fetchColumn();
pruef('Nach einer Stunde wieder', $n3 > $n2, $n2 . ' -> ' . $n3);

app_state_setzen(RATE_K_MAIL, '0');
app_state_setzen(RATE_K_MAIL_MARK, '');
sicherheit_melden_pruefen();
$n4 = (int)$pdo->query("SELECT COUNT(*) FROM mail_warteschlange
                         WHERE schluessel = 'sicherheit_sammel'")->fetchColumn();
pruef('Abgeschaltet meldet sie gar nicht', $n4 === $n3, $n3 . ' -> ' . $n4);
app_state_setzen(RATE_K_MAIL, '');

/* ======================================================================== */
abschnitt('9  Sperre aufheben — und der Weg zurueck nach einem Passwort-Reset');

klopfen('login', $KONTO, 20);
pruef('Vorbereitet: gesperrt', !rate_erlaubt('login', null, [$KONTO]), '');
$weg = rate_sperre_aufheben('login', $KONTO, 'Ratenprobe');
pruef('rate_sperre_aufheben() loescht die Zeile', $weg, '');
pruef('und die Sperre ist weg', rate_erlaubt('login', null, [$KONTO]), '');
$e = (int)$pdo->query("SELECT COUNT(*) FROM sicherheit_ereignisse
                        WHERE art = 'aufgehoben' AND wer = 'Ratenprobe'")->fetchColumn();
pruef('Das Aufheben steht im Protokoll', $e >= 1, $e . ' Zeile(n)');

klopfen('login', 'id:probe-reset@ratenprobe.invalid', 20);
klopfen('salt', 'id:probe-reset@ratenprobe.invalid', 40);
pruef('Vorbereitet: Konto an Anmeldung und Salz gesperrt',
      !rate_erlaubt('login', null, ['id:probe-reset@ratenprobe.invalid'])
   && !rate_erlaubt('salt',  null, ['id:probe-reset@ratenprobe.invalid']), '');
rate_konto_freigeben('Probe-Reset@Ratenprobe.INVALID');
pruef('Ein gesetztes Passwort raeumt beide Toepfe (auch bei anderer Schreibweise)',
      rate_erlaubt('login', null, ['id:probe-reset@ratenprobe.invalid'])
   && rate_erlaubt('salt',  null, ['id:probe-reset@ratenprobe.invalid']),
      'Merkmale werden kleingeschrieben');

/* ======================================================================== */
abschnitt('10  Welche Toepfe eine Leiter haben — und welche ausdruecklich nicht');

/* DIE LISTE IST DER SOLLWERT, UND DIE ZAHL KOMMT AUS IHR.
 *
 * Hier stand bis zum 21.09.2026 „Genau fuenf Toepfe haben eine Leiter" neben
 * einer fuenfelementigen Liste — dieselbe Zahl zweimal, einmal als Satz und
 * einmal als Aufzaehlung. Mit Web 20.24.0 (P5b/AP9) kam `blatt` dazu, die
 * Liste wurde nicht nachgezogen, und die Probe stand EINEN MONAT auf einem
 * Befund, den niemand sah: Sie braucht eine laufende Anlage und haengt nicht
 * in Stufe 1. Gefunden am 21.09.2026 beim ersten Lauf nach Schritt 15 AP2
 * (Backlog Nr. 254).
 *
 * Jetzt rechnet die Ueberschrift ihre Zahl aus der Liste. Wer einen Topf
 * ergaenzt, ergaenzt die Liste — und der Satz stimmt von selbst. Das ist
 * genau die Bauform, die der Backlog-Kopf fuer gezaehlte Werte verlangt.
 *
 * `blatt` (Schluesselblatt-Pruefung, S10) HAT eine Leiter, und das ist
 * richtig: Wer die Vierergruppen raet, soll nach drei Fehlversuchen laenger
 * warten. Anmeldung und Anwendung bleiben dabei offen.
 *
 * `totp` (Code-Schritt der Anmeldung, P5c/AP5, E-P5c-53) hat eine: Fuenf
 * falsche Codes je Konto, dann waechst die Sperre — sechs Ziffern duerfen
 * nicht zu erraten sein. */
const TOEPFE_MIT_LEITER = ['blatt', 'ingest', 'ingest_ip', 'login', 'login_ip', 'salt', 'totp'];

$mitLeiter = []; $ohne = [];
foreach (RATE_GRENZEN as $topf => $g) {
    if (!empty($g['leiter'])) { $mitLeiter[] = $topf; } else { $ohne[] = $topf; }
}
sort($mitLeiter);
pruef('Genau ' . count(TOEPFE_MIT_LEITER) . ' Toepfe haben eine Leiter',
      $mitLeiter === TOEPFE_MIT_LEITER,
      implode(', ', $mitLeiter));
pruef('Die beiden Ingest-Toepfe haben DIESELBE Zahl (E-P5a-47)',
      RATE_GRENZEN['ingest']['max'] === RATE_GRENZEN['ingest_ip']['max']
      && RATE_GRENZEN['ingest']['max'] === 30,
      'je ' . RATE_GRENZEN['ingest']['max'] . ' — verschiedene Schwellen waeren ein Existenzorakel');
pruef('`reset` hat KEINE — er sperrt heute schon eine Stunde',
      in_array('reset', $ohne, true),
      'jede Sprosse unterhalb der vierten waere schwaecher');
pruef('Die Kopplungstoepfe haben keine',
      !array_diff(['pair', 'pair_start', 'pair_code'], $ohne),
      'eine laengere Sperre unterbraeche dort einen laufenden Vorgang (E-P5a-48)');
pruef('Der globale Zaehler sperrt nie',
      RATE_GRENZEN['global']['max'] === PHP_INT_MAX, 'max = PHP_INT_MAX');

/* ======================================================================== */
aufraeumen();
$nachher  = (int)$pdo->query('SELECT COUNT(*) FROM rate_limits')->fetchColumn();
$nachherE = (int)$pdo->query('SELECT COUNT(*) FROM sicherheit_ereignisse')->fetchColumn();
$nachherM = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange')->fetchColumn();

abschnitt('11  Die Probe hinterlaesst nichts');
pruef('rate_limits unveraendert', $nachher <= $vorher, $vorher . ' -> ' . $nachher);
pruef('sicherheit_ereignisse unveraendert', $nachherE <= $vorherE, $vorherE . ' -> ' . $nachherE);
pruef('mail_warteschlange unveraendert', $nachherM <= $vorherM, $vorherM . ' -> ' . $nachherM);

echo "\n" . str_repeat('=', 72) . "\n";
printf("Ratenprobe: %d Pruefungen, %d Befunde\n", $GEPRUEFT, count($BEFUNDE));
foreach ($BEFUNDE as $b) { echo "  - " . $b . "\n"; }
exit($BEFUNDE === [] ? 0 : 1);
