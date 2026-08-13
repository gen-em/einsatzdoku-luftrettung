<?php
declare(strict_types=1);
/**
 * Liefert das Schluesselableitungs-Salt zu einer E-Mail (fuer den Login).
 * POST JSON {"email": "..."} -> {"salt": hex}
 *
 * Unbekannte Adressen bekommen ein DETERMINISTISCHES Pseudo-Salt (HMAC mit
 * Server-Geheimnis) — Antworten sind damit nicht von echten unterscheidbar
 * und verraten nicht, welche Adressen existieren.
 *
 * HINWEIS FUER SPAETERE AENDERUNGEN AN DIESER DATEI
 * Sie ist einer der wenigen Endpunkte, die ohne Anmeldung erreichbar sind,
 * und jede Ungleichheit zwischen den beiden Antwortzweigen ist eine Auskunft
 * darueber, welche Konten es gibt. Gleich sein muessen: LAENGE,
 * ZEICHENVORRAT, AUFBAU und DAUER der Antwort — und, sobald weitere Angaben
 * hinzukommen, auch deren WERTE.
 *
 * ---- DIE RUNDENZAHL (seit Web 5.0.0, M2-01 Schritt 2) --------------------
 *
 * Genau dieser Fall ist eingetreten, und der Hinweis von damals ist eingeloest:
 * Der Endpunkt nennt die Rundenzahl NICHT je Konto, sondern als feste Liste
 * aus db.php — fuer jede Adresse dieselbe. Naehme er den Wert aus der
 * Nutzerzeile, waere waehrend einer Umstellung jede Adresse, die den alten
 * Wert zurueckliefert, nachweislich ein echtes, seither nicht benutztes Konto.
 *
 * Die Antwort ist damit fuer alle Adressen buchstaeblich identisch — nicht nur
 * ununterscheidbar, sondern gleich. Welche Rundenzahl fuer ein Konto gilt,
 * entscheidet der Server bei der Anmeldung (login.php), also erst NACH dem
 * Nachweis, dass jemand das Passwort kennt.
 */
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/ratelimit_lib.php';
header('Content-Type: application/json; charset=utf-8');

// Zeitpunkt fuer die gleiche Antwortdauer beider Zweige — vor jeder Arbeit.
$t0 = microtime(true);

/**
 * Beide Zweige auf dieselbe Mindestdauer bringen.
 *
 * Die Gleichheit von Laenge, Zeichenvorrat und Aufbau allein genuegt nicht:
 * Der unbekannte Zweig macht MEHR Arbeit als der bekannte (zweite Abfrage auf
 * app_state, dazu ein HMAC). Der Unterschied liegt im Bereich von
 * Bruchteilen einer Millisekunde und ginge im Netzrauschen unter — aber er
 * ist gerichtet, und wer oft genug misst, mittelt Rauschen weg. Eine
 * Mindestdauer von 50 ms liegt drei Groessenordnungen darueber und ist damit
 * das, was gemessen wird. Bewusst klein gehalten: Dieser Aufruf steht bei
 * jeder Anmeldung vor der Schluesselableitung, die ohnehin knapp eine Sekunde
 * dauert.
 */
const SALT_MINDESTDAUER = 0.05;

$b = json_decode(file_get_contents('php://input'), true);
// Gemeinsame Schreibweise (M1-13). Bisher stand die Normalisierung hier
// als einzige Stelle richtig — jetzt an allen acht Stellen dieselbe.
$email = email_normalisieren($b['email'] ?? '');

/* Sperre VOR jeder Arbeit pruefen. Der Endpunkt ist ohne Anmeldung erreichbar
 * und beantwortet je Aufruf die Frage "gibt es dieses Konto?" — nicht ueber
 * den Inhalt der Antwort, sondern ueber die blosse Moeglichkeit, ihn
 * millionenfach aufzurufen. Der Ratenschutz ist die Mengenbegrenzung, die
 * dabei bisher fehlte. */
if (!rate_erlaubt('salt', $email)) {
    rate_gleiche_dauer($t0, SALT_MINDESTDAUER);
    http_response_code(429);
    echo json_encode(['error'   => 'zu_viele_versuche',
                      'meldung' => 'Zu viele Versuche. Bitte später erneut.']);
    exit;
}
// Jede Anfrage zaehlt, nicht nur eine fehlgeschlagene: Dieser Endpunkt kennt
// kein Scheitern, er antwortet jeder Adresse. Begrenzt wird die Menge.
// Erfolgreiche Anmeldungen leeren den Zaehler wieder (login.php).
rate_zaehlen('salt', $email);

if ($email === '' || strlen($email) > 190) {
    rate_gleiche_dauer($t0, SALT_MINDESTDAUER);
    http_response_code(400); echo json_encode(['error' => 'email']); exit;
}

$pdo = db();
$st = $pdo->prepare('SELECT kdf_salt FROM users WHERE email = ?');
$st->execute([$email]);
$u = $st->fetch();

/* Die Rundenzahlen stehen in BEIDEN Zweigen gleich — sie sind kein Wert
 * dieses Kontos, sondern die Liste dessen, was diese Fassung unterstuetzt. */
$runden = KDF_ITER_LISTE;

if ($u && $u['kdf_salt'] !== null) {
    rate_gleiche_dauer($t0, SALT_MINDESTDAUER);
    echo json_encode(['salt' => $u['kdf_salt'], 'iter' => $runden]);
    exit;
}

// Server-Geheimnis fuer Pseudo-Salts (einmalig erzeugt, app_state)
$sec = $pdo->query("SELECT v FROM app_state WHERE k = 'salt_secret'")->fetchColumn();
if ($sec === false) {
    $sec = bin2hex(random_bytes(32));
    $pdo->prepare("INSERT IGNORE INTO app_state (k, v) VALUES ('salt_secret', ?)")
        ->execute([$sec]);
}

/* Unbekannte Adresse: Pseudo-Salt in derselben Form — die Antwort ist damit
 * nicht von einer echten unterscheidbar. Die Anmeldung scheitert anschliessend
 * am Token, ohne zu verraten, ob die Adresse existiert.
 *
 * ENTSCHEIDEND IST DIE LAENGE. Ein echtes Salt entsteht aus 16 Zufallsbytes
 * und ist damit 32 Hexzeichen lang (EdCrypto.randomHex(16) in
 * assets/crypto.js; die Pruefung in pw_handling.php verlangt genau 32). Der
 * volle HMAC liefert 64. Wer beide Antworten nebeneinanderlegt, musste sie
 * gar nicht ansehen — die blosse Laenge sagte, ob zu dieser Adresse ein
 * eingerichtetes Konto existiert. Damit war die gesamte Vorkehrung
 * wirkungslos.
 *
 * Die ersten 32 Zeichen des Hashwerts: Zeichenvorrat (Hex) und Verteilung
 * stimmen bereits ueberein, es fehlte nur der Zuschnitt. Ein gekuerzter HMAC
 * ist hier unbedenklich — er soll nicht faelschungssicher sein, sondern
 * gleich aussehen und fuer dieselbe Adresse immer denselben Wert liefern
 * (sonst waere die blosse Wiederholung der Anfrage die naechste Auskunft).
 */
$pseudo = substr(hash_hmac('sha256', $email, (string)$sec), 0, 32);
rate_gleiche_dauer($t0, SALT_MINDESTDAUER);
echo json_encode(['salt' => $pseudo, 'iter' => $runden]);
