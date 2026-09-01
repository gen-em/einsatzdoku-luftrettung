<?php
declare(strict_types=1);

/**
 * VERSANDPROBE — die Adapter aus `server/sicherungsziel_lib.php` gegen echte
 * Server (S2/AP7).
 *
 *   python3 tools/versandprobe/gegenstellen.py /tmp/vp     # in einer Schale
 *   php    tools/versandprobe/probe.php        /tmp/vp     # in der zweiten
 *
 * Was hier NICHT geprüft wird, steht in der LIESMICH.md — an erster Stelle,
 * nicht in einer Fussnote.
 */

$wurzel = rtrim((string)($argv[1] ?? ''), '/');
if ($wurzel === '' || !is_dir($wurzel)) {
    fwrite(STDERR, "Aufruf: php probe.php <wurzel der gegenstellen>\n");
    exit(2);
}

/* ZWEI SAETZE GEGENSTELLEN, EIN SATZ ERWARTUNGEN.
 *
 * Ohne Schalter laeuft die Probe gegen `gegenstellen.py` — pyftpdlib und
 * paramiko, portabel und ohne Rechte. Mit `--echt` gegen
 * `echte_gegenstellen.sh` — vsftpd und OpenSSH, also die Server, die auf
 * einem Webspace tatsaechlich stehen.
 *
 * DER UNTERSCHIED IST NICHT KOSMETISCH. Nachbauten koennen nur, was ihr Autor
 * bedacht hat; vsftpd kennt kein MLSD, OpenSSH sperrt niemanden in ein
 * Heimverzeichnis, und beide antworten mit anderen Texten. Genau daran
 * scheitert ein FTP-Adapter, nicht am Uebertragen.
 *
 * DER GRUNDPFAD IST DESHALB JE PROTOKOLL EIN ANDERER: vsftpd sperrt den
 * Nutzer in sein Heimverzeichnis (chroot), dort ist `/` die Wurzel. OpenSSH
 * tut das NICHT — dort ist `/` die Wurzel des Dateisystems, und der Grundpfad
 * muss der echte Pfad sein. Wer das verwechselt, schreibt seine Sicherungen
 * nach `/`.
 */
$echt = in_array('--echt', $argv, true);
if ($echt) {
    define('P_FTP', 2131);  define('P_FTPS', 2132);  define('P_SFTP', 2232);
    define('NUTZER', 'edprobe');
    define('PFAD_FTP', '/');                       // chroot: Heim ist die Wurzel
    define('PFAD_SFTP', $wurzel . '/heim');        // kein chroot: echter Pfad
    define('GEGEN', 'vsftpd und OpenSSH');
} else {
    define('P_FTP', 2121);  define('P_FTPS', 2122);  define('P_SFTP', 2222);
    define('NUTZER', 'probe');
    define('PFAD_FTP', '/');
    define('PFAD_SFTP', '/');
    define('GEGEN', 'pyftpdlib und paramiko');
}
const HOST     = '127.0.0.1';
const PASSWORT = 'geheim-probe-2026';

/* Ein $CFG, bevor irgendetwas aus server/ geladen wird: serverkrypto_lib.php
 * liest den Serverschlüssel daraus.
 *
 * Liegt eine örtliche `config.php`, wird sie GELESEN (für den Zugang zur
 * Datenbank in Teil 10), aber NICHT GESCHRIEBEN: Der Serverschlüssel für
 * diesen Lauf entsteht hier und ist nach dem Lauf wieder fort. Eine Probe,
 * die eine Konfiguration verändert, ist keine Probe mehr, sondern ein
 * Eingriff.
 *
 * Ohne config.php laufen die Teile 1 bis 9 trotzdem — die Adapter brauchen
 * keine Installation. Teil 10 sagt dann, dass er ausfällt, statt zu schweigen. */
$konfig = __DIR__ . '/../../server/config.php';
$CFG = is_file($konfig) ? (array)(require $konfig) : ['db' => [], 'app' => [], 'smtp' => []];
$CFG['server_key'] = bin2hex(random_bytes(32));
require_once __DIR__ . '/../../server/sicherungsziel_lib.php';

echo "Gegenstellen: " . GEGEN . " (FTP " . P_FTP . ", FTPS " . P_FTPS
     . ", SFTP " . P_SFTP . ", Nutzer " . NUTZER . ")\n";

$n = 0; $offen = 0; $teil = '';
function kopf(string $t): void { global $teil; $teil = $t; echo "\n$t\n"; }
function pruef(string $was, bool $ok, string $dazu = ''): void {
    global $n, $offen;
    $n++;
    if (!$ok) { $offen++; }
    printf("  [%s] %-58s %s\n", $ok ? 'ok ' : 'OFFEN', $was, $dazu);
}
/** Läuft der Rumpf durch, ohne zu werfen? Gibt die Fehlermeldung zurück. */
function faengt(callable $fn): ?string {
    try { $fn(); return null; } catch (Throwable $e) { return $e->getMessage(); }
}

/* ======================================================================
 * Teil 1 — Der Serverschlüssel
 * ==================================================================== */
kopf('Teil 1 — Serverschlüssel und Versiegelung (serverkrypto_lib.php)');

pruef('Der Schlüssel wird als 32 Rohbytes gelesen',
      serverschluessel() !== null && strlen((string)serverschluessel()) === 32);
$p = sk_versiegeln('geheim123', 'ziel:7:pass');
pruef('Die Chiffre trägt das Präfix edsk1:', str_starts_with($p, SK_PRAEFIX));
pruef('Der Klartext steht nicht in der Chiffre', !str_contains($p, 'geheim123'));
pruef('Öffnet mit demselben Zweck', sk_oeffnen($p, 'ziel:7:pass') === 'geheim123');
pruef('Öffnet NICHT mit einem anderen Zweck', sk_oeffnen($p, 'ziel:3:pass') === null,
      'Umhängen zwischen Zielen scheitert');
$p2 = sk_versiegeln('geheim123', 'ziel:7:pass');
pruef('Zweimal versiegelt sind zwei verschiedene Chiffren', $p !== $p2);
pruef('...und beide öffnen gleich', sk_oeffnen($p2, 'ziel:7:pass') === 'geheim123');
$roh = base64_decode(substr($p, strlen(SK_PRAEFIX)), true);
$kaputt = SK_PRAEFIX . base64_encode(substr((string)$roh, 0, -1) . 'X');
pruef('Eine veränderte Chiffre wird abgewiesen', sk_oeffnen($kaputt, 'ziel:7:pass') === null);
pruef('Müll wird abgewiesen', sk_oeffnen('nichts', 'z') === null);
$alt = $CFG['server_key'];
$CFG['server_key'] = bin2hex(random_bytes(32)); serverschluessel(true);
pruef('Ein anderer Serverschlüssel öffnet nicht', sk_oeffnen($p, 'ziel:7:pass') === null);
$CFG['server_key'] = 'zu-kurz'; serverschluessel(true);
pruef('Ein unbrauchbarer Eintrag gilt als „kein Schlüssel"', !serverschluessel_da());
pruef('Ohne Schlüssel wirft das Versiegeln, statt Klartext zu speichern',
      faengt(fn() => sk_versiegeln('x', 'z')) !== null);
$CFG['server_key'] = $alt; serverschluessel(true);
$lang = str_repeat('A', 100000);
pruef('100 000 Zeichen gehen unverändert durch',
      sk_oeffnen(sk_versiegeln($lang, 'z'), 'z') === $lang);

/* ======================================================================
 * Teil 2 — Die Hilfsfunktionen
 * ==================================================================== */
kopf('Teil 2 — Namen, Pfade, Fehlertexte');

pruef('Ein gewöhnlicher Paketname ist gültig',
      sz_name_gueltig('2026-08-16T18-22-31Z_a1b2c3d4.zip'));
pruef('„.." im Namen wird abgewiesen', !sz_name_gueltig('../../etc/passwd'));
pruef('Rückschrägstrich wird abgewiesen', !sz_name_gueltig('a\\b'));
pruef('Der leere Name wird abgewiesen', !sz_name_gueltig(''));
pruef('Ein Schrägstrich im Namen wird abgewiesen', !sz_name_gueltig('a/b'));
pruef('sz_pfad setzt Grundpfad und Rest zusammen',
      sz_pfad('/sicherungen/', '/konto/paket.zip') === '/sicherungen/konto/paket.zip');
pruef('sz_pfad ohne Rest gibt den Grundpfad', sz_pfad('/ablage', '') === '/ablage');
pruef('Ein leerer Grundpfad wird zu „."', sz_pfad('', 'x') === './x');
pruef('„Login incorrect" wird ein deutscher Satz',
      str_contains(sz_klartext('ftp_login(): Login incorrect.'), 'Passwort stimmt nicht'));
pruef('...und das Original bleibt in Klammern stehen',
      str_contains(sz_klartext('ftp_login(): Login incorrect.'), 'Login incorrect'));
pruef('„getaddrinfo" wird zum Rechnernamen-Hinweis',
      str_contains(sz_klartext('php_network_getaddresses: getaddrinfo failed'), 'auflösen'));

/* ======================================================================
 * Teile 3 bis 5 — Die drei Adapter im Rundlauf
 * ==================================================================== */

/** Ein Adapter, ein voller Rundlauf. Gibt die Zahl der Erwartungen zurück. */
function rundlauf(string $wie, Zielweg $weg, string $ordner): void
{
    $datei = tempnam(sys_get_temp_dir(), 'vp');
    $inhalt = "Rundlauf $wie\n" . str_repeat('x', 5000) . "\n";
    file_put_contents($datei, $inhalt);
    $zurueck = $datei . '.zurueck';
    $name = 'paket-' . $wie . '.zip';

    pruef("$wie: verbinden und anmelden", faengt(fn() => $weg->verbinden()) === null);
    pruef("$wie: Ordner anlegen", faengt(fn() => $weg->ordner($ordner)) === null);
    pruef("$wie: derselbe Ordner ein zweites Mal ist kein Fehler",
          faengt(fn() => $weg->ordner($ordner)) === null);
    $bytes = 0;
    $f = faengt(function () use ($weg, $datei, $ordner, $name, &$bytes) {
        $bytes = $weg->senden($datei, $ordner . '/' . $name);
    });
    pruef("$wie: Datei senden", $f === null, $f ?? ($bytes . ' Byte'));
    $liste = [];
    $f = faengt(function () use ($weg, $ordner, &$liste) { $liste = $weg->liste($ordner); });
    pruef("$wie: Verzeichnis auflisten", $f === null && isset($liste[$name]),
          $f ?? (count($liste) . ' Eintrag, ' . ($liste[$name] ?? '?') . ' Byte'));
    pruef("$wie: die gemeldete Grösse stimmt", ($liste[$name] ?? -1) === strlen($inhalt),
          strlen($inhalt) . ' Byte erwartet');
    $f = faengt(fn() => $weg->holen($ordner . '/' . $name, $zurueck));
    pruef("$wie: Datei zurückholen", $f === null, $f ?? '');
    pruef("$wie: zurückgeholt ist Byte für Byte dasselbe",
          (string)@file_get_contents($zurueck) === $inhalt,
          strlen($inhalt) . ' Byte verglichen');
    pruef("$wie: Datei löschen", faengt(fn() => $weg->loeschen($ordner . '/' . $name)) === null);
    $f = faengt(function () use ($weg, $ordner, &$liste) { $liste = $weg->liste($ordner); });
    pruef("$wie: danach ist sie weg", $f === null && !isset($liste[$name]));
    $weg->trennen();
    @unlink($datei); @unlink($zurueck);
}

kopf('Teil 3 — FTP (ext/ftp, unverschlüsselt) gegen 127.0.0.1:' . P_FTP);
rundlauf('ftp', new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, PFAD_FTP, false, true), 'kontoA');

kopf('Teil 4 — FTPS (ext/ftp mit TLS) gegen 127.0.0.1:' . P_FTPS);
rundlauf('ftps', new ZielFtp(HOST, P_FTPS, NUTZER, PASSWORT, PFAD_FTP, true, true), 'kontoB');
/* DER BEFUND, DER IN DIE DOKUMENTATION GEHÖRT. Die Gegenstelle zeigt ein
 * selbst ausgestelltes Zertifikat mit dem Namen „versandprobe-selbst-
 * ausgestellt" und ohne jede Vertrauenskette. Dass der Rundlauf oben
 * durchläuft, IST die Messung: `ext/ftp` prüft es nicht. */
pruef('FTPS nimmt ein selbst ausgestelltes Zertifikat an', true,
      'BEFUND: ext/ftp prüft kein Zertifikat — kein Schutz gegen Unterschieben');

kopf('Teil 5 — SFTP (phpseclib) gegen 127.0.0.1:' . P_SFTP);
$sollAbdruck = trim((string)@file_get_contents($wurzel . '/fingerabdruck.txt'));
$sftp = new ZielSftp(HOST, P_SFTP, NUTZER, PASSWORT, null, PFAD_SFTP, null);
rundlauf('sftp', $sftp, 'kontoC');
pruef('Der errechnete Fingerabdruck ist der des Servers',
      $sftp->fingerabdruck() === $sollAbdruck && $sollAbdruck !== '',
      (string)$sftp->fingerabdruck());
pruef('Er ist in OpenSSH-Schreibweise', str_starts_with((string)$sftp->fingerabdruck(), 'SHA256:'));

/* ======================================================================
 * Teil 6 — Der Fingerabdruck ist ein Riegel, kein Schmuck
 * ==================================================================== */
kopf('Teil 6 — Unerwarteter Hostschlüssel (SFTP)');

/* DIE MESSGROESSE IST DIE GEGENSTELLE, NICHT DIE FEHLERMELDUNG. „Der Adapter
 * bricht ab" liesse sich behaupten; „der Server hat keinen Anmeldeversuch
 * gesehen" ist gezaehlt. Bei den Nachbauten schreibt paramiko jede Anmeldung
 * mit, bei den echten Servern das Protokoll von OpenSSH (`sshd -E`). */
$protokoll = $echt ? $wurzel . '/sshd-auth.log' : $wurzel . '/anmeldungen.log';
$zaehle = static function (string $datei, bool $echt): int {
    $zeilen = file($datei, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    if (!$echt) { return count($zeilen); }
    /* OpenSSH schreibt viel; gezaehlt wird nur, was eine Anmeldung IST. */
    return count(array_filter($zeilen, static fn($z) =>
        preg_match('/(Accepted|Failed|Postponed|Connection closed by authenticating)/i', $z) === 1));
};
$vorher = $zaehle($protokoll, $echt);
$falsch = 'SHA256:' . rtrim(base64_encode(hash('sha256', 'ein anderer Server', true)), '=');
$sftpF = new ZielSftp(HOST, P_SFTP, NUTZER, PASSWORT, null, PFAD_SFTP, $falsch);
$fehler = faengt(fn() => $sftpF->verbinden());
pruef('Ein anderer Hostschlüssel bricht die Verbindung ab', $fehler !== null);
pruef('...und die Meldung nennt beide Fingerabdrücke',
      $fehler !== null && str_contains($fehler, $falsch)
      && str_contains($fehler, (string)$sollAbdruck));
$nachher = $zaehle($protokoll, $echt);
pruef('...und die Gegenstelle hat KEINEN Anmeldeversuch gesehen', $nachher === $vorher,
      "Anmeldezeilen vorher $vorher, nachher $nachher — kein Passwort gesendet");
$sftpF->trennen();

$sftpR = new ZielSftp(HOST, P_SFTP, NUTZER, PASSWORT, null, PFAD_SFTP, $sollAbdruck);
pruef('Der richtige Fingerabdruck lässt durch',
      faengt(fn() => $sftpR->verbinden()) === null);
$sftpR->trennen();

/* ======================================================================
 * Teil 7 — Anmeldung mit privatem Schlüssel
 * ==================================================================== */
kopf('Teil 7 — SFTP mit privatem Schlüssel');

$key      = (string)@file_get_contents($wurzel . '/nutzerschluessel');
$keyPass  = (string)@file_get_contents($wurzel . '/nutzerschluessel-mit-passwort');
pruef('Der Probeschlüssel liegt bereit', $key !== '' && $keyPass !== '');

$sk = new ZielSftp(HOST, P_SFTP, NUTZER, '', $key, PFAD_SFTP, $sollAbdruck);
$f = faengt(fn() => $sk->verbinden());
pruef('Anmeldung mit Schlüssel ohne Passphrase', $f === null, $f ?? '');
$sk->trennen();

$sk2 = new ZielSftp(HOST, P_SFTP, NUTZER, 'passphrase-der-probe', $keyPass, PFAD_SFTP, $sollAbdruck);
$f = faengt(fn() => $sk2->verbinden());
pruef('Anmeldung mit Schlüssel UND Passphrase', $f === null, $f ?? '');
$sk2->trennen();

$sk3 = new ZielSftp(HOST, P_SFTP, NUTZER, 'falsche-passphrase', $keyPass, PFAD_SFTP, $sollAbdruck);
$f = faengt(fn() => $sk3->verbinden());
pruef('Eine falsche Passphrase wird verständlich abgewiesen',
      $f !== null && str_contains($f, 'Passphrase'), (string)$f);

$sk4 = new ZielSftp(HOST, P_SFTP, NUTZER, '', 'kein Schlüssel, nur Text', PFAD_SFTP, $sollAbdruck);
$f = faengt(fn() => $sk4->verbinden());
pruef('Ein kaputter Schlüssel wird verständlich abgewiesen',
      $f !== null && str_contains($f, 'BEGIN'), (string)$f);

/* ======================================================================
 * Teil 8 — Die Fehlerfälle, die eine Betreiberin tatsächlich trifft
 * ==================================================================== */
kopf('Teil 8 — Fehlerfälle mit verständlicher Meldung (Abnahme AP7)');

$faelle = [
    ['Falsches Passwort (FTP)',
     new ZielFtp(HOST, P_FTP, NUTZER, 'falsch', PFAD_FTP, false, true),
     'Passwort'],
    ['Falsches Passwort (SFTP)',
     new ZielSftp(HOST, P_SFTP, NUTZER, 'falsch', null, PFAD_SFTP, $sollAbdruck),
     'Passwort'],
    ['Falscher Port (FTP)',
     new ZielFtp(HOST, 2199, NUTZER, PASSWORT, PFAD_FTP, false, true),
     'Port'],
    ['Falscher Port (SFTP)',
     new ZielSftp(HOST, 2199, NUTZER, PASSWORT, null, PFAD_SFTP, null),
     'Verbindung'],
    ['Pfad gibt es nicht (FTP)',
     new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, '/gibt-es-nicht', false, true),
     'Pfad'],
    ['Pfad gibt es nicht (SFTP)',
     new ZielSftp(HOST, P_SFTP, NUTZER, PASSWORT, null, '/gibt-es-nicht', $sollAbdruck),
     'Pfad'],
];
foreach ($faelle as [$was, $weg, $wort]) {
    $f = faengt(fn() => $weg->verbinden());
    pruef($was . ' wird abgewiesen', $f !== null);
    pruef($was . ': die Meldung nennt „' . $wort . '"',
          $f !== null && str_contains($f, $wort),
          mb_substr((string)$f, 0, 78));
    try { $weg->trennen(); } catch (Throwable $e) {}
}

$ohne = new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, PFAD_FTP, false, true);
$f = faengt(fn() => $ohne->senden('/tmp/gibt-es-nicht.zip', 'x.zip'));
pruef('Senden ohne Verbindung wird abgewiesen, statt still zu scheitern',
      $f !== null && str_contains($f, 'keine Verbindung'), (string)$f);
$ohne->verbinden();
$f = faengt(fn() => $ohne->senden('/tmp/gibt-es-nicht-wirklich.zip', 'x.zip'));
pruef('Eine fehlende örtliche Datei wird benannt',
      $f !== null && str_contains($f, 'gibt es hier nicht'), (string)$f);
$ohne->trennen();

/* ======================================================================
 * Teil 9 — „Verbindung prüfen" als Ganzes
 * ==================================================================== */
kopf('Teil 9 — sz_verbindung_pruefen(): schreiben, lesen, vergleichen, löschen');

/** Ein Ziel bauen, wie es aus der Datenbank käme — ohne Datenbank. */
function zielAttrappe(int $id, string $prot, int $port, ?string $abdruck,
                      string $passwort = PASSWORT, ?string $schluessel = null): array
{
    return [
        'id' => $id, 'name' => 'Probe ' . $prot, 'protokoll' => $prot,
        'host' => HOST, 'port' => $port, 'nutzer' => NUTZER,
        'geheim' => sk_versiegeln($passwort, sz_zweck($id, 'geheim')),
        'schluessel' => $schluessel === null ? null
                        : sk_versiegeln($schluessel, sz_zweck($id, 'schluessel')),
        'pfad' => $prot === 'sftp' ? PFAD_SFTP : PFAD_FTP,
        'passiv' => 1, 'aktiv' => 1,
        'fingerabdruck' => $abdruck,
    ];
}

foreach ([['ftp', P_FTP, null], ['ftps', P_FTPS, null],
          ['sftp', P_SFTP, $sollAbdruck]] as $i => [$prot, $port, $abdruck]) {
    $e = sz_verbindung_pruefen(zielAttrappe(100 + $i, $prot, $port, $abdruck));
    pruef(strtoupper($prot) . ': die Prüfung läuft durch', $e['ok'] === true,
          $e['ok'] ? count($e['schritte']) . ' Schritte' : $e['meldung']);
    pruef(strtoupper($prot) . ': sie hat wirklich geschrieben und verglichen',
          in_array('Zurückgelesen und Byte für Byte verglichen.', $e['schritte'], true));
    pruef(strtoupper($prot) . ': die Probedatei ist wieder weg',
          in_array('Probedatei wieder gelöscht.', $e['schritte'], true));
}
$dreck = array_merge(glob($wurzel . '/*/edverbindungsprobe-*') ?: [],
                     glob($wurzel . '/heim/edverbindungsprobe-*') ?: []);
pruef('Keine Probedatei ist auf einer der drei Gegenstellen liegengeblieben',
      $dreck === [], count($dreck) . ' Rückstände');

$e = sz_verbindung_pruefen(zielAttrappe(200, 'ftp', P_FTP, null, 'falsches-passwort'));
pruef('Ein falsches Passwort meldet sich als solches', $e['ok'] === false
      && str_contains($e['meldung'], 'Passwort'), mb_substr($e['meldung'], 0, 60));

/* Ein Ziel, dessen Geheimnis mit einem ANDEREN Serverschlüssel versiegelt
 * wurde — der Fall „config.php neu aufgesetzt, Schlüssel vergessen". */
$fremd = zielAttrappe(300, 'ftp', P_FTP, null);
$alt = $CFG['server_key'];
$CFG['server_key'] = bin2hex(random_bytes(32)); serverschluessel(true);
$e = sz_verbindung_pruefen($fremd);
pruef('Ein fremder Serverschlüssel wird benannt, nicht verschwiegen',
      $e['ok'] === false && str_contains($e['meldung'], 'ANDEREN Serverschlüssel'),
      mb_substr($e['meldung'], 0, 60));
$CFG['server_key'] = $alt; serverschluessel(true);

/* ======================================================================
 * Teil 10 — Die Ziele in der Datenbank
 * ==================================================================== */
kopf('Teil 10 — Anlegen, Ändern, Löschen und die Versiegelung in der Tabelle');

$db = null;
try {
    require_once __DIR__ . '/../../server/db.php';
    $db = sz_tabelle_da() ? db() : null;
} catch (Throwable $e) { $db = null; }

if ($db === null) {
    pruef('Teil 10 läuft (Datenbank mit Tabelle backup_targets erreichbar)', false,
          'AUSGEFALLEN — keine Datenbank oder Migration 2026_09_01_sicherungsziele fehlt');
} else {
    $db->exec("DELETE FROM backup_targets WHERE name LIKE 'Versandprobe%'");
    [$ok, $id] = sz_speichern(null, [
        'name' => 'Versandprobe SFTP', 'protokoll' => 'sftp', 'host' => HOST,
        'port' => P_SFTP, 'nutzer' => NUTZER, 'pfad' => PFAD_SFTP,
        'passiv' => 1, 'aktiv' => 1,
    ], PASSWORT, null);
    pruef('Ein Ziel lässt sich anlegen', $ok === true, is_int($id) ? "Kennung $id" : '');
    $ziel = $ok ? sz_lesen((int)$id) : null;
    pruef('...und wieder lesen', $ziel !== null);

    $roh = (string)($ziel['geheim'] ?? '');
    pruef('Das Passwort steht NICHT im Klartext in der Spalte',
          !str_contains($roh, PASSWORT) && str_starts_with($roh, SK_PRAEFIX),
          mb_substr($roh, 0, 30) . '…');
    pruef('...und lässt sich mit dem Serverschlüssel öffnen',
          sz_geheim((array)$ziel, 'geheim') === PASSWORT);

    /* Umhängen: dieselbe Chiffre unter einer anderen Kennung. */
    $verhaengt = (array)$ziel; $verhaengt['id'] = ((int)$ziel['id']) + 1;
    pruef('Die Chiffre eines Ziels öffnet nicht unter einer anderen Kennung',
          sz_geheim($verhaengt, 'geheim') === null);

    [$ok2, $f2] = sz_speichern(null, [
        'name' => 'Versandprobe SFTP', 'protokoll' => 'ftp', 'host' => HOST,
        'port' => 21, 'nutzer' => 'x', 'pfad' => '/', 'passiv' => 1, 'aktiv' => 1,
    ], 'x', null);
    pruef('Ein zweites Ziel mit demselben Namen wird abgewiesen', $ok2 === false);

    [$ok3, $f3] = sz_speichern(null, [
        'name' => 'Versandprobe Murks', 'protokoll' => 'gopher', 'host' => '',
        'port' => 99999, 'nutzer' => '', 'pfad' => '/', 'passiv' => 1, 'aktiv' => 1,
    ], 'x', null);
    pruef('Unsinnige Eingaben werden einzeln benannt',
          $ok3 === false && is_array($f3) && count($f3) >= 4, count((array)$f3) . ' Sätze');

    /* Ändern OHNE neues Passwort — das Geheimnis muss stehenbleiben. */
    sz_speichern((int)$id, [
        'name' => 'Versandprobe SFTP', 'protokoll' => 'sftp', 'host' => HOST,
        'port' => P_SFTP, 'nutzer' => NUTZER, 'pfad' => PFAD_SFTP . '/geaendert',
        'passiv' => 1, 'aktiv' => 0,
    ], null, null);
    $ziel2 = sz_lesen((int)$id);
    pruef('Ändern ohne Passworteingabe lässt das Passwort stehen',
          sz_geheim((array)$ziel2, 'geheim') === PASSWORT);
    pruef('...und übernimmt die geänderten Felder',
          ($ziel2['pfad'] ?? '') === PFAD_SFTP . '/geaendert'
          && (int)($ziel2['aktiv'] ?? 1) === 0);

    sz_fingerabdruck_merken((int)$id, $sollAbdruck);
    $ziel3 = sz_lesen((int)$id);
    pruef('Der Fingerabdruck lässt sich übernehmen',
          ($ziel3['fingerabdruck'] ?? '') === $sollAbdruck);

    sz_lauf_merken((int)$id, false, 'Ein Fehler zum Merken');
    $ziel4 = sz_lesen((int)$id);
    pruef('Ein Fehlschlag bleibt am Ziel stehen',
          ($ziel4['letzter_fehler'] ?? '') === 'Ein Fehler zum Merken'
          && ($ziel4['letzter_erfolg'] ?? null) === null);
    sz_lauf_merken((int)$id, true);
    $ziel5 = sz_lesen((int)$id);
    pruef('Ein Erfolg räumt den Fehler weg',
          ($ziel5['letzter_fehler'] ?? null) === null
          && ($ziel5['letzter_erfolg'] ?? null) !== null);

    /* Nur AKTIVE Ziele für den Versandjob. */
    $aktive = array_column(sz_alle(true), 'name');
    pruef('Ein abgeschaltetes Ziel steht nicht in der Liste der aktiven',
          !in_array('Versandprobe SFTP', $aktive, true));

    pruef('Löschen entfernt die Zeile', sz_loeschen((int)$id) && sz_lesen((int)$id) === null);
    $db->exec("DELETE FROM backup_targets WHERE name LIKE 'Versandprobe%'");
}


/* ======================================================================
 * Teil 11 — Die Wege, die bis hierher niemand gegangen ist
 *
 * Die Teile 3 bis 10 fahren den HAEUFIGEN Fall: passives FTP, Grundpfad `/`,
 * ein Server mit MLSD, ein Verzeichnis, in das man schreiben darf. Der
 * Adapter kann mehr — und was nie gefahren wurde, ist nicht geprueft,
 * sondern nur geschrieben.
 * ==================================================================== */
kopf('Teil 11 — Nebenwege des Adapters');

/* --- 11a: Welchen Listenbefehl kann diese Gegenstelle ueberhaupt? -------
 * `ZielFtp::liste()` nimmt `ftp_mlsd` und faellt auf `ftp_nlist` + `ftp_size`
 * zurueck. Welcher Weg genommen wird, entscheidet der SERVER — und deshalb
 * wird hier gemessen und nicht vermutet. vsftpd kennt kein MLSD; pyftpdlib
 * kennt es. Erst beide Laeufe zusammen belegen beide Zweige. */
$roh = ftp_connect(HOST, P_FTP, 10);
@ftp_login($roh, NUTZER, PASSWORT);
ftp_pasv($roh, true);
$kannMlsd = is_array(@ftp_mlsd($roh, PFAD_FTP === '/' ? '.' : PFAD_FTP));
@ftp_close($roh);
pruef('Der benutzte Listenweg ist gemessen, nicht vermutet', true,
      $kannMlsd ? 'MLSD — der Hauptweg' : 'kein MLSD — der RUECKFALL auf NLST+SIZE');

$wegL = new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, PFAD_FTP, false, true);
$datei = tempnam(sys_get_temp_dir(), 'vp');
file_put_contents($datei, str_repeat('L', 777));
$f = faengt(function () use ($wegL, $datei) {
    $wegL->verbinden(); $wegL->ordner('liste'); $wegL->senden($datei, 'liste/l.zip');
});
$liste = $f === null ? $wegL->liste('liste') : [];
pruef(($kannMlsd ? 'Mit MLSD' : 'Ohne MLSD (Rueckfall)') . ': die Liste nennt die Datei',
      isset($liste['l.zip']), $f ?? (count($liste) . ' Eintrag'));
pruef(($kannMlsd ? 'Mit MLSD' : 'Ohne MLSD (Rueckfall)') . ': und ihre Groesse stimmt',
      ($liste['l.zip'] ?? -1) === 777, ($liste['l.zip'] ?? '?') . ' statt 777');
if ($f === null) { $wegL->loeschen('liste/l.zip'); }
$wegL->trennen();

/* --- 11b: Aktives FTP ---------------------------------------------------
 * Der Schalter „passiver Modus" steht in der Oberflaeche und war bisher nur
 * in EINER Stellung gefahren. Aktives FTP heisst: Der SERVER baut die
 * Datenverbindung zum Klienten auf. Auf einem Webspace hinter NAT geht das
 * meist nicht — hier auf 127.0.0.1 geht es, und genau deshalb laesst es sich
 * hier pruefen. */
$wegA = new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, PFAD_FTP, false, false);
$zurueck = $datei . '.zurueck';
$f = faengt(function () use ($wegA, $datei, $zurueck) {
    $wegA->verbinden();
    $wegA->ordner('aktiv');
    $wegA->senden($datei, 'aktiv/a.zip');
    $wegA->holen('aktiv/a.zip', $zurueck);
});
pruef('Aktives FTP (passiv = aus) uebertraegt', $f === null, $f ?? '777 Byte');
pruef('...und zurueckgeholt ist es dasselbe',
      $f === null && (string)@file_get_contents($zurueck) === (string)file_get_contents($datei));
if ($f === null) { $wegA->loeschen('aktiv/a.zip'); }
$wegA->trennen();
@unlink($zurueck);

/* --- 11c: Ein Grundpfad, der nicht die Wurzel ist -----------------------
 * Im Betrieb heisst der Pfad `/backups/einsatzdoku` und nicht `/`. Damit
 * laeuft jeder Aufruf durch `sz_pfad()` mit zwei Bestandteilen statt einem —
 * und ein Fehler dort faellt bei `/` nicht auf. */
$tiefFtp = rtrim(PFAD_FTP, '/') . '/tief/darunter';
$wegT = new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, $tiefFtp, false, true);
$f = faengt(function () use ($wegT, $datei) {
    $wegT->verbinden(); $wegT->ordner('kontoX'); $wegT->senden($datei, 'kontoX/t.zip');
});
$listeT = $f === null ? $wegT->liste('kontoX') : [];
pruef('Ein Grundpfad mit Unterordnern traegt (FTP)', $f === null && isset($listeT['t.zip']),
      $f ?? $tiefFtp);
if ($f === null) { $wegT->loeschen('kontoX/t.zip'); }
$wegT->trennen();

$tiefSftp = rtrim(PFAD_SFTP, '/') . '/tief/darunter';
$wegTs = new ZielSftp(HOST, P_SFTP, NUTZER, PASSWORT, null, $tiefSftp, $sollAbdruck);
$f = faengt(function () use ($wegTs, $datei) {
    $wegTs->verbinden(); $wegTs->ordner('kontoY'); $wegTs->senden($datei, 'kontoY/t.zip');
});
$listeTs = $f === null ? $wegTs->liste('kontoY') : [];
pruef('Ein Grundpfad mit Unterordnern traegt (SFTP)', $f === null && isset($listeTs['t.zip']),
      $f ?? $tiefSftp);
if ($f === null) { $wegTs->loeschen('kontoY/t.zip'); }
$wegTs->trennen();

/* --- 11d: Kein Schreibrecht --------------------------------------------
 * Die Abnahme von AP7 nennt „Platz voll" ausdruecklich als Fehlerfall mit
 * verstaendlicher Meldung. Eine volle Platte laesst sich hier nicht
 * herstellen; ein gesperrtes Verzeichnis ist derselbe Weg durch den Adapter
 * (der Server antwortet mit 550 bzw. „Permission denied") und laesst sich
 * herstellen. Das steht so auch in der LIESMICH — es ist der NAECHSTLIEGENDE
 * Fall, nicht derselbe. */
$sperre = null;
if ($echt) {
    /* Bei den echten Servern laeuft vsftpd als der angemeldete Nutzer — ein
     * Verzeichnis auf 0555 genuegt, und der Server antwortet mit dem
     * Wortlaut, den ein Hoster auch schickt. */
    $sperre = $wurzel . '/heim/gesperrt';
    @mkdir($sperre, 0755, true);
    @chmod($sperre, 0555);
    $wegS = new ZielFtp(HOST, P_FTP, NUTZER, PASSWORT, PFAD_FTP, false, true);
    $ziel11d = 'gesperrt/s.zip';
} else {
    /* Bei den Nachbauten laeuft der Server als root, und root ignoriert
     * Rechtebits — ein gesperrtes Verzeichnis waere hier keine Sperre.
     * Stattdessen ein Konto, das NUR LESEN darf: derselbe Weg durch den
     * Adapter, abgelehnt vom Server statt vom Dateisystem. */
    $wegS = new ZielFtp(HOST, P_FTP, 'nurlesen', PASSWORT, PFAD_FTP, false, true);
    $ziel11d = 'nurlesen.zip';
}
$f = faengt(function () use ($wegS, $datei, $ziel11d) {
    $wegS->verbinden(); $wegS->senden($datei, $ziel11d);
});
pruef('Ein verweigertes Schreiben wird abgewiesen (FTP)', $f !== null,
      $echt ? 'gesperrtes Verzeichnis' : 'Konto ohne Schreibrecht');
pruef('...und die Meldung sagt auf DEUTSCH, woran es liegt',
      $f !== null && str_contains($f, 'verweigert den Zugriff'),
      mb_substr((string)$f, 0, 100));
$wegS->trennen();
if ($sperre !== null) { @chmod($sperre, 0755); @rmdir($sperre); }
@unlink($datei);
printf("\n-> %d Erwartungen, %d nicht erfuellt\n", $n, $offen);
exit($offen === 0 ? 0 : 1);
