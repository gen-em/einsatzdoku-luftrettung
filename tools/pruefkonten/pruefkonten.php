<?php
declare(strict_types=1);

/**
 * Prüfkonten — ein Testbestand für die NutzerInnen-Liste (P3/O9b).
 *
 * WOFUER. Das Konzept verlangt die Abnahme der NutzerInnen-Liste „mit einem
 * Testbestand von 300 Konten" (E-P3-41, Abnahme O9). Ein Referenzdatensatz mit
 * vier Konten beantwortet die Fragen nicht, um derentwillen die Liste gebaut
 * wurde: Trägt der Seitenwechsel? Bleibt die Auswahl über Seiten hinweg? Wie
 * lange braucht ein Aufruf, wenn der Sicherungsstand aus dem Dateisystem kommt?
 *
 * WAS SIE ANFASST. Konten unterhalb von `@example.invalid` mit dem Präfix
 * `pruefkonto-` — sonst nichts. `entfernen` löscht genau diese wieder, samt
 * Geräten (Fremdschlüssel mit ON DELETE CASCADE) und samt ihrer
 * Sicherungsordner. Trotzdem gilt: gegen eine Testinstallation fahren, nicht
 * gegen den Produktivserver.
 *
 * WARUM DIE SICHERUNGEN ECHTE DATEIEN SIND. Der Stand eines Kontos („aktuell",
 * „überfällig · n Tage", „nie gesichert") kommt nicht aus der Datenbank,
 * sondern aus `server/sicherungen/<kennung>/konto.json`. Ein Testbestand, der
 * nur Datenbankzeilen anlegt, würde die Liste also mit lauter „nie gesichert"
 * füllen und genau die Verzweigung nicht prüfen, um die es geht. Die Pakete
 * hier sind formal gültig, aber winzig (ein leeres Datenpaket) — es geht um
 * die ZAHL der Ordner, nicht um ihren Inhalt.
 *
 * REPRODUZIERBAR. `mt_srand()` mit festem Startwert: Zweimal `anlegen 300`
 * ergibt zweimal denselben Bestand — Rollen, Gerätezahlen, Sicherungsstände
 * und Anmeldezeitpunkte inbegriffen. Nur so lässt sich eine gemessene Zahl
 * beim nächsten Lauf wiederfinden.
 *
 * AUFRUF
 *
 *     php tools/pruefkonten/pruefkonten.php anlegen [anzahl]   (Vorgabe 300)
 *     php tools/pruefkonten/pruefkonten.php zeigen
 *     php tools/pruefkonten/pruefkonten.php entfernen
 *
 * Optional als zweites Argument der Pfad zu `server/`.
 */

const PRAEFIX  = 'pruefkonto-';
const DOMAENE  = '@example.invalid';
const STARTWERT = 4711;

/* ARGUMENTE NACH IHRER GESTALT, NICHT NACH IHRER STELLE.
 *
 * Der Kopf sagt: „Optional als weiteres Argument der Pfad zu server/." Stand
 * die Anzahl fest auf $argv[2], hiess das beim Aufruf
 * `pruefkonten.php anlegen /pfad/zu/server` in Wahrheit „Anzahl = 0" — und
 * die Antwort war „Anzahl zwischen 1 und 5000 angeben", eine Meldung zu einer
 * Zahl, die gar niemand angegeben hatte. Jetzt ist die Anzahl das Argument,
 * das nur aus Ziffern besteht, und der Pfad das mit einem Schraegstrich. */
$befehl = (string)($argv[1] ?? '');
$anzahl = 300;
$serverArg = null;
foreach (array_slice($argv, 2) as $a) {
    if (preg_match('/^\d+$/', $a)) { $anzahl = (int)$a; }
    elseif (str_contains($a, '/'))  { $serverArg = $a; }
}
$server = realpath($serverArg ?? (__DIR__ . '/../../server'));
if ($server === false) { fwrite(STDERR, "server/ nicht gefunden\n"); exit(2); }
require_once $server . '/db.php';
require_once $server . '/adminbackup_lib.php';

/* Vor- und Nachnamen: genug für dreihundert verschiedene Konten, ohne dass
 * eine Namensliste zum eigenen Pflegefall wird. Namen sind erfunden. */
const VORNAMEN = ['Anna', 'Tobias', 'Selin', 'Mara', 'Jonas', 'Lea', 'Felix', 'Nora',
                  'David', 'Elif', 'Paul', 'Ida', 'Milan', 'Sofia', 'Jan', 'Ruth',
                  'Kai', 'Lena', 'Ömer', 'Britta', 'Sven', 'Hanna', 'Timo', 'Aylin'];
const NACHNAMEN = ['Berger', 'Brandl', 'Kaya', 'Lindner', 'Weiss', 'Roth', 'Hofer',
                   'Sommer', 'Krüger', 'Yildiz', 'Bauer', 'Neumann', 'Stark', 'Frei',
                   'Amann', 'Ostermann', 'Perez', 'Vogel', 'Lang', 'Schwarz'];

function mail_von(int $i): string
{
    return PRAEFIX . str_pad((string)$i, 4, '0', STR_PAD_LEFT) . DOMAENE;
}

/** Alle Prüfkonten mit Kennung — die eine Abfrage, auf der alles fusst. */
function pruefkonten(): array
{
    $st = db()->prepare('SELECT id, email, account_key FROM users
                         WHERE email LIKE ? AND email LIKE ? ORDER BY email');
    $st->execute([PRAEFIX . '%', '%' . DOMAENE]);
    return $st->fetchAll();
}

/** Ein winziges, formal gültiges Paket in den Ordner legen. */
function paket_schreiben(string $kennung, string $email, ?string $name, string $iso): string
{
    $datei = str_replace([':'], ['-'], substr($iso, 0, 19)) . 'Z_'
           . bin2hex(random_bytes(4)) . '.json';
    $paket = [
        'format'      => 'einsatzdoku-adminsicherung',
        'version'     => 1,
        'erzeugt'     => $iso,
        'web_version' => defined('WEB_VERSION') ? WEB_VERSION : '0',
        'konto'       => ['account_key' => $kennung, 'email' => $email, 'name' => $name],
        'schluessel'  => ['pat_wrap_rc' => null, 'pat_key_check' => null],
        'umfang'      => ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0,
                          'papierkorb' => ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0]],
        'daten'       => [],
    ];
    file_put_contents(edbak_ordner($kennung) . '/' . $datei,
        json_encode($paket, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    return $datei;
}

if ($befehl === 'anlegen') {
    if ($anzahl < 1 || $anzahl > 5000) {
        fwrite(STDERR, "Anzahl zwischen 1 und 5000 angeben.\n"); exit(2);
    }
    [$bereit, $grund] = edbak_ablage_bereit();
    if (!$bereit) { fwrite(STDERR, "Ablage nicht bereit: $grund\n"); exit(2); }

    mt_srand(STARTWERT);
    $pdo = db();
    $jetzt = time();
    $zahl = ['aktuell' => 0, 'ueberfaellig' => 0, 'nie' => 0, 'ohne_kennung' => 0,
             'admins' => 0, 'ohne_geraet' => 0, 'nie_angemeldet' => 0];

    $einfuegen = $pdo->prepare(
        'INSERT INTO users (email, name, role, account_key, kdf_iter, session_epoch, created_at, last_login)
         VALUES (?,?,?,?,?,0,?,?)');
    /* `api_key_hash` hat keinen Vorgabewert — ein Geraet ohne Schluessel gibt
     * es in dieser Anwendung nicht. Der Wert hier ist ein Zufallshash, mit dem
     * sich niemand anmelden kann: Er entsteht aus Zufall, und den Klartext
     * dazu gibt es nirgends. */
    $geraet = $pdo->prepare(
        'INSERT INTO devices (user_id, device_id, api_key_hash, label, active, created_at, last_seen)
         VALUES (?,?,?,?,?,?,?)');

    /* KEINE UMSPANNENDE TRANSAKTION.
     *
     * Die Ordner und Pakete entstehen im Dateisystem, waehrend die Kontozeilen
     * in der Datenbank stehen. Bricht ein Lauf mitten drin ab — Strg-C, ein
     * Fatal, ein Duplikat in devices.device_id —, rollte eine umspannende
     * Transaktion die Zeilen zurueck und liesse die bereits geschriebenen
     * Ordner stehen: verwaiste Sicherungen, die `entfernen` ueber die
     * Kontenliste nie wiederfaende. Ein Konto je Transaktion laesst im
     * schlimmsten Fall EIN halbes Konto zurueck, und `entfernen` raeumt
     * zusaetzlich nach der Begleitdatei auf (siehe unten). */
    for ($i = 1; $i <= $anzahl; $i++) {
        $pdo->beginTransaction();
        $mail = mail_von($i);
        $name = VORNAMEN[mt_rand(0, count(VORNAMEN) - 1)] . ' '
              . NACHNAMEN[mt_rand(0, count(NACHNAMEN) - 1)];
        $rolle = mt_rand(1, 100) <= 2 ? 'admin' : 'user';
        if ($rolle === 'admin') { $zahl['admins']++; }

        /* Etwa jedes fuenfzigste Konto ohne Kontokennung — der Altbestand vor
         * der Migration 2026_08_16_kontokennung. Die Liste muss ihn zeigen
         * koennen, ohne ihn als „nie gesichert" auszugeben. */
        $ohneKennung = mt_rand(1, 50) === 1;
        $kennung = $ohneKennung ? null : bin2hex(random_bytes(8));
        if ($ohneKennung) { $zahl['ohne_kennung']++; }

        $seit = gmdate('Y-m-d H:i:s', $jetzt - mt_rand(30, 900) * 86400);
        $nieAngemeldet = mt_rand(1, 10) === 1;
        $anmeldung = $nieAngemeldet ? null
            : gmdate('Y-m-d H:i:s', $jetzt - mt_rand(0, 200) * 86400);
        if ($nieAngemeldet) { $zahl['nie_angemeldet']++; }

        $einfuegen->execute([$mail, $name, $rolle, $kennung, 310000, $seit, $anmeldung]);
        $uid = (int)$pdo->lastInsertId();

        $geraete = mt_rand(1, 100) <= 20 ? 0 : mt_rand(1, 2);
        if ($geraete === 0) { $zahl['ohne_geraet']++; }
        for ($g = 1; $g <= $geraete; $g++) {
            $geraet->execute([$uid, 'dev-' . bin2hex(random_bytes(4)),
                hash('sha256', bin2hex(random_bytes(32))),
                'Uhr ' . $g, mt_rand(1, 10) === 1 ? 0 : 1,
                $seit, gmdate('Y-m-d H:i:s', $jetzt - mt_rand(0, 60) * 86400)]);
        }

        if ($kennung === null) { $pdo->commit(); continue; }

        /* Sicherungsstand: rund 60 % aktuell, 10 % ueberfaellig, 30 % nie.
         * „nie" bekommt gar keinen Ordner — genau wie in der Anwendung. */
        $wurf = mt_rand(1, 100);
        if ($wurf > 70) { $zahl['nie']++; $pdo->commit(); continue; }
        $alter = $wurf <= 60 ? mt_rand(0, 25) : mt_rand(31, 400);
        $zahl[$alter >= 30 ? 'ueberfaellig' : 'aktuell']++;

        $ordner = edbak_ordner($kennung);
        if (!is_dir($ordner)) { @mkdir($ordner, 0770, true); }
        $eintraege = [];
        $paketzahl = mt_rand(1, 3);
        for ($k = $paketzahl - 1; $k >= 0; $k--) {
            $iso = gmdate('Y-m-d\TH:i:s\Z', $jetzt - ($alter + $k * 30) * 86400);
            $eintraege[] = ['datei' => paket_schreiben($kennung, $mail, $name, $iso),
                            'erzeugt' => $iso,
                            'umfang' => ['einsaetze' => 0, 'diensttage' => 0, 'ruhezeiten' => 0]];
        }
        /* Die Eintraege stehen aufsteigend — edbak_verzeichnis_abgleichen()
         * nimmt das letzte als 'letzte_sicherung'. */
        usort($eintraege, static fn($a, $b) => strcmp($a['erzeugt'], $b['erzeugt']));
        edbak_begleit_schreiben($kennung, [
            'email' => $mail, 'name' => $name,
            'letzte_sicherung' => end($eintraege)['erzeugt'],
            'sicherungen' => $eintraege, 'freigabe' => null,
        ]);
        $pdo->commit();
    }

    printf("%d Prüfkonten angelegt (%s%s).\n", $anzahl, PRAEFIX, DOMAENE);
    foreach ($zahl as $k => $v) { printf("  %-16s %4d\n", $k, $v); }
    echo "  Entfernen mit: php tools/pruefkonten/pruefkonten.php entfernen\n";
    exit(0);
}

if ($befehl === 'entfernen') {
    $konten = pruefkonten();
    $ordner = 0;
    $weg = function (string $kennung) use (&$ordner): void {
        /* NUR ZAEHLEN, WAS ES WIRKLICH GAB. edbak_ordner_loeschen() liefert
         * auch dann true, wenn gar kein Ordner da war — die Zahl waere sonst
         * die der Konten mit Kennung und nicht die der geloeschten Ordner
         * (bei 300 Pruefkonten: 294 statt 208). */
        $gabEs = is_dir(edbak_ordner($kennung));
        if (edbak_ordner_loeschen($kennung) && $gabEs) { $ordner++; }
    };
    foreach ($konten as $k) {
        if (edbak_kennung_gueltig($k['account_key'])) { $weg((string)$k['account_key']); }
    }
    $st = db()->prepare('DELETE FROM users WHERE email LIKE ? AND email LIKE ?');
    $st->execute([PRAEFIX . '%', '%' . DOMAENE]);
    $entfernt = $st->rowCount();

    /* ZWEITER DURCHGANG UEBER DIE ABLAGE. Ein abgebrochener Lauf kann Ordner
     * hinterlassen, zu denen es keine Kontozeile (mehr) gibt — dann findet
     * die Schleife oben sie nicht. Die Begleitdatei traegt die Adresse, und
     * die verraet ein Pruefkonto. Ohne diesen Durchgang bliebe Muell liegen,
     * den ausgerechnet die Seite anzeigt, die hier geprueft werden soll. */
    $verwaist = 0;
    foreach (edbak_staende() as $kennung => $_) {
        $b = edbak_begleit_lesen($kennung);
        $mail = (string)($b['email'] ?? '');
        if (str_starts_with($mail, PRAEFIX) && str_ends_with($mail, DOMAENE)) {
            $weg($kennung); $verwaist++;
        }
    }
    printf("%d Prüfkonten entfernt, %d Sicherungsordner gelöscht%s.\n",
        $entfernt, $ordner,
        $verwaist ? sprintf(' (davon %d verwaiste)', $verwaist) : '');
    exit(0);
}

if ($befehl === 'zeigen') {
    $konten = pruefkonten();
    $karte  = edbak_staende();
    $zahl = [];
    foreach ($konten as $k) {
        $s = edbak_stand_aus_karte($k['account_key'], $karte)['stand'];
        $zahl[$s] = ($zahl[$s] ?? 0) + 1;
    }
    printf("%d Prüfkonten.\n", count($konten));
    foreach ($zahl as $s => $n) { printf("  %-14s %4d\n", $s, $n); }
    printf("  Ordner in der Ablage insgesamt: %d\n", count($karte));
    exit(0);
}

fwrite(STDERR, "Aufruf: php tools/pruefkonten/pruefkonten.php anlegen|zeigen|entfernen [anzahl]\n");
exit(2);
