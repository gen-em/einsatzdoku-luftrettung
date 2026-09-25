<?php
declare(strict_types=1);

/**
 * RATENPROBE — die Sperrleiter, die Verlangsamung und die Sammelmail
 * ===========================================================================
 *
 * Anlass: Nr. 305 — die Stufe der Sperrleiter waere nie zurueckgefallen
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
 * SEIT P5c/AP6 AUCH DER HEALTH-ENDPUNKT, UND DER UEBER HTTP (Abschnitt 11,
 * Abnahme AP6: „in den vorhandenen Proben statt einer neuen"). Dort zaehlt der
 * Topf `health` die MENGE, und die zeigt sich nur an echten Anfragen: 60
 * gehen durch, die 61. bekommt 429. Dazu die drei 403 mit gleicher Dauer, die
 * Felder der 200 und die 503 bei ausstehender Migration. Den Token stellt die
 * Probe in `config.php` (`tools/sandbox/konfig_stellen.php`) und legt den
 * vorigen Stand zurueck.
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
    $pdo->exec("DELETE FROM rate_limits WHERE merkmal LIKE '%probe-%' OR topf IN ('global', 'health')");
    $pdo->exec("DELETE FROM sicherheit_ereignisse
                 WHERE merkmal LIKE '%probe-%' OR merkmal = 'alle' OR topf = 'health'");
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
abschnitt('11  Health ueber HTTP — Token, Felder, Migration, Menge (P5c/AP6)');

/* UEBER HTTP, NICHT UEBER DIE BIBLIOTHEK: Was hier zaehlt, ist, was ein
 * Monitoring sieht — Code, Rumpf und Dauer. Gegen die oertliche Anlage
 * (Vorgabe http://127.0.0.1:8080, sonst das erste Argument). */
require_once $wurzel . '/tools/sandbox/konfig_stellen.php';
require_once $wurzel . '/server/migration_lib.php';
require_once $wurzel . '/server/speicher_lib.php';
$basis = rtrim($argv[1] ?? 'http://127.0.0.1:8080', '/');
$health = static function (?string $token) use ($basis): array {
    $url = $basis . '/api/health.php' . ($token === null ? '' : '?token=' . rawurlencode($token));
    $ch = curl_init($url);
    curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 30, CURLOPT_PROXY => '']);
    $t = microtime(true);
    $rumpf = (string)curl_exec($ch);
    $dauer = microtime(true) - $t;
    $code = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    return ['code' => $code, 'json' => json_decode($rumpf, true), 'dauer' => $dauer];
};
/* Der OPcache des PHP-Servers prueft den Zeitstempel von `config.php`
 * hoechstens alle ZWEI Sekunden (`opcache.revalidate_freq = 2`, F-P5c-69):
 * nach dem Stellen drei Sekunden warten, wie der Umschalter aus AP1. Die
 * erste Fassung wartete 1,2 s und sah beim leeren Eintrag noch den Token
 * des Falls davor (F-P5c-123). */
$stellen = static function (array $werte): callable { $z = konfig_stellen($werte); sleep(3); return $z; };
$pdo->exec("DELETE FROM rate_limits WHERE topf = 'health'");

$TOKEN = bin2hex(random_bytes(16));
$zurueckKonfig = $stellen(['betrieb' => ['health_token' => $TOKEN]]);
try {
    $ohne   = $health(null);
    $falsch = $health(bin2hex(random_bytes(16)));
    $gut    = $health($TOKEN);
    pruef('ohne Token → 403 `token`', $ohne['code'] === 403 && ($ohne['json']['error'] ?? '') === 'token',
          'HTTP ' . $ohne['code']);
    pruef('falscher Token → 403 `token`', $falsch['code'] === 403 && ($falsch['json']['error'] ?? '') === 'token',
          'HTTP ' . $falsch['code']);
    $FELDER = ['ok', 'web_version', 'db', 'migration_ausstehend', 'jobs_alter_s', 'system_24h',
               'protokoll_fehler', 'speicher_pct'];
    $j = is_array($gut['json']) ? $gut['json'] : [];
    pruef('richtiger Token → 200 mit genau den Feldern aus E-P5c-52',
          $gut['code'] === 200 && array_keys($j) === $FELDER,
          'HTTP ' . $gut['code'] . ' · ' . implode(',', array_keys($j)));
    pruef('… ok, Datenbank da, keine Migration, Fassung stimmt',
          ($j['ok'] ?? null) === true && ($j['db'] ?? null) === true
          && ($j['migration_ausstehend'] ?? null) === false && ($j['web_version'] ?? '') === WEB_VERSION,
          json_encode(array_intersect_key($j, array_flip(['ok', 'db', 'migration_ausstehend', 'web_version']))));
    pruef('… und keine Konten-, Mengen- oder Hosterangaben (nur Zahlen, Wahrheitswerte, die Fassung)',
          $j !== [] && !array_filter($j, static fn($v, $k): bool
              => !(is_bool($v) || is_int($v) || $v === null || $k === 'web_version'), ARRAY_FILTER_USE_BOTH),
          'jobs_alter_s=' . var_export($j['jobs_alter_s'] ?? null, true)
          . ' system_24h=' . var_export($j['system_24h'] ?? null, true)
          . ' speicher_pct=' . var_export($j['speicher_pct'] ?? null, true));

    /* MIGRATION AUSSTEHEND: der gemerkte Stand des Torwaechters, gestellt —
     * Hash des Katalogs und „offen". `api/health.php` laedt kein
     * `auth_guard.php`, schaltet also die Wartung nicht ein; es meldet. */
    $vorTor = [MIGRATION_TOR_HASH => _tor_lesen($pdo, MIGRATION_TOR_HASH),
               MIGRATION_TOR_OFFEN => _tor_lesen($pdo, MIGRATION_TOR_OFFEN)];
    migrationen_tor_merken($pdo, true);
    try {
        $mig = $health($TOKEN);
    } finally {
        foreach ($vorTor as $k => $v) {
            if ($v === null) { $pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute([$k]); }
            else { app_state_setzen($k, $v); }
        }
    }
    pruef('Migration ausstehend → 503 mit ok:false und migration_ausstehend:true',
          $mig['code'] === 503 && ($mig['json']['ok'] ?? null) === false
          && ($mig['json']['migration_ausstehend'] ?? null) === true,
          'HTTP ' . $mig['code']);

    /* SPEICHER_PCT (E-P5c-117): Der Endpunkt liest, was `speicher_messen()`
     * gemerkt hat, und nimmt den hoechsten Anteil. Erst gestellt (ein Wert
     * fehlt, der hoechste ist nicht der erste; dann gar keine Marke), dann
     * einmal gemessen und gegen die Balken von `speicher_uebersicht()`
     * gehalten — dieselbe Rechnung, an anderer Stelle. Die Marken der
     * Messung legt die Probe danach zurueck. */
    $SPK = [SPEICHER_K_PROZENT, SPEICHER_K_DB, SPEICHER_K_DATEIEN, SPEICHER_K_STAND];
    $vorSp = [];
    foreach ($SPK as $k) { $vorSp[$k] = _tor_lesen($pdo, $k); }
    try {
        app_state_setzen(SPEICHER_K_PROZENT, '{"datenbank":12,"backups":null,"gesamt":47}');
        $sp1 = $health($TOKEN)['json']['speicher_pct'] ?? 'fehlt';
        $pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute([SPEICHER_K_PROZENT]);
        $sp2 = $health($TOKEN)['json'] ?? [];
        speicher_messen($pdo);
        $gemerkt = json_decode((string)app_state_lesen(SPEICHER_K_PROZENT), true);
        $u = speicher_uebersicht();
        $sp3 = $health($TOKEN)['json']['speicher_pct'] ?? 'fehlt';
    } finally {
        foreach ($vorSp as $k => $v) {
            if ($v === null) { $pdo->prepare('DELETE FROM app_state WHERE k = ?')->execute([$k]); }
            else { app_state_setzen($k, $v); }
        }
    }
    pruef('speicher_pct ist der hoechste gemerkte Anteil (12 / – / 47 → 47)', $sp1 === 47, var_export($sp1, true));
    pruef('… ohne Messung null, und die Antwort bleibt 200',
          array_key_exists('speicher_pct', $sp2) && $sp2['speicher_pct'] === null && ($sp2['ok'] ?? null) === true,
          array_key_exists('speicher_pct', $sp2) ? var_export($sp2['speicher_pct'], true) : 'Feld fehlt');
    $erwB = $u['backups']['bezug'] > 0 ? $u['backups']['prozent'] : null;
    $erwG = $u['gesamt']['bezug'] > 0 ? $u['gesamt']['prozent'] : null;
    $werte = is_array($gemerkt) ? array_filter($gemerkt, 'is_int') : [];
    pruef('Nach speicher_messen(): Backups und Gesamt wie die Balken der Karte Speicher, der Endpunkt nennt den hoechsten',
          is_array($gemerkt) && array_keys($gemerkt) === ['datenbank', 'backups', 'gesamt']
          && $gemerkt['backups'] === $erwB && $gemerkt['gesamt'] === $erwG
          && $sp3 === ($werte === [] ? null : max($werte)),
          json_encode($gemerkt) . ' · Balken ' . var_export($erwB, true) . '/' . var_export($erwG, true)
          . ' · Endpunkt ' . var_export($sp3, true));
} finally {
    $zurueckKonfig();
}

/* ENDPUNKT AUS: leerer Eintrag. Dieselbe 403 wie ohne und mit falschem
 * Token — die Antwort verraet nicht, ob einer eingerichtet ist. */
$zurueckKonfig = $stellen(['betrieb' => ['health_token' => '']]);
try { $aus = $health($TOKEN); } finally { $zurueckKonfig(); }
pruef('Endpunkt aus (leerer Eintrag) → 403 `token`', $aus['code'] === 403 && ($aus['json']['error'] ?? '') === 'token',
      'HTTP ' . $aus['code']);
$dauern = [$ohne['dauer'], $falsch['dauer'], $aus['dauer']];
pruef('… alle drei 403 mit angeglichener Dauer (je mindestens 0,35 s, Spanne unter 0,15 s)',
      min($dauern) >= 0.35 && max($dauern) - min($dauern) < 0.15,
      implode(' / ', array_map(static fn(float $d): string => number_format($d, 3) . ' s', $dauern)));

/* DIE MENGE: 60 je Minute. Der Zaehler steht nach den Faellen oben auf
 * einigen Anfragen — geleert, dann genau 60 richtige, dann die 61. */
$pdo->exec("DELETE FROM rate_limits WHERE topf = 'health'");
$zurueckKonfig = $stellen(['betrieb' => ['health_token' => $TOKEN]]);
try {
    $codes = [];
    for ($i = 1; $i <= 61; $i++) { $codes[$i] = $health($TOKEN)['code']; }
} finally { $zurueckKonfig(); }
$bis60 = array_count_values(array_slice($codes, 0, 60));
pruef('60 Anfragen in einer Minute gehen durch, die 61. bekommt 429',
      ($bis60[200] ?? 0) === 60 && $codes[61] === 429,
      '1–60: ' . json_encode($bis60) . ' · 61: ' . $codes[61]);
pruef('Der Topf `health` hat keine Leiter (60 je Minute, eine Minute Sperre)',
      empty(RATE_GRENZEN['health']['leiter']) && RATE_GRENZEN['health']['max'] === 60
      && RATE_GRENZEN['health']['fenster'] === 60,
      json_encode(RATE_GRENZEN['health']));

/* ======================================================================== */
aufraeumen();
$nachher  = (int)$pdo->query('SELECT COUNT(*) FROM rate_limits')->fetchColumn();
$nachherE = (int)$pdo->query('SELECT COUNT(*) FROM sicherheit_ereignisse')->fetchColumn();
$nachherM = (int)$pdo->query('SELECT COUNT(*) FROM mail_warteschlange')->fetchColumn();

abschnitt('12  Die Probe hinterlaesst nichts');
pruef('rate_limits unveraendert', $nachher <= $vorher, $vorher . ' -> ' . $nachher);
pruef('sicherheit_ereignisse unveraendert', $nachherE <= $vorherE, $vorherE . ' -> ' . $nachherE);
pruef('mail_warteschlange unveraendert', $nachherM <= $vorherM, $vorherM . ' -> ' . $nachherM);

echo "\n" . str_repeat('=', 72) . "\n";
printf("Ratenprobe: %d Pruefungen, %d Befunde\n", $GEPRUEFT, count($BEFUNDE));
foreach ($BEFUNDE as $b) { echo "  - " . $b . "\n"; }
exit($BEFUNDE === [] ? 0 : 1);
