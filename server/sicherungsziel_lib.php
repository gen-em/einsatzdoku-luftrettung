<?php
declare(strict_types=1);
require_once __DIR__ . '/serverkrypto_lib.php';

/**
 * BACKUP-ZIELE — wohin die Backups gehen (E-S2-22, S2/AP7).
 *
 * WARUM „BACKUP-ZIEL" UND NICHT „TRANSPORTZIEL". Das Konzept nennt es
 * Transportziel. Diesen Namen trägt in dieser Anwendung seit Web 4 aber schon
 * etwas anderes: `transport_dests`, die Zielklinik einer Patientin, gepflegt
 * unter Stammdaten. Zwei Dinge, zwei Klicks voneinander entfernt, unter einem
 * Wort — das wäre kein Detail, sondern die Art Verwechslung, die man in einer
 * Fehlermeldung nicht mehr auflösen kann. Also: Backup-Ziel. Festgehalten
 * in `docs/konzepte/erledigt/Konzept-S2-Mengen-Spuren-Sicherung.md` unter F-S2-G.
 *
 * DREI PROTOKOLLE, EINE SCHNITTSTELLE
 * `Zielweg` beschreibt, was ein Ziel können muss — verbinden, Ordner anlegen,
 * senden, holen, auflisten, löschen. Wer eine Datei wegschiebt, sieht nicht,
 * ob dahinter FTP oder SFTP steckt. Das ist nicht Ästhetik: Der Versandjob
 * (AP7/5) und das Komplettbackup (AP8) sollen beide nichts über Protokolle
 * wissen müssen, und ein vierter Adapter (WebDAV steht im Backlog) soll sie
 * nicht anfassen.
 *
 * WAS DIE DREI TATSÄCHLICH TAUGEN — der Reihe nach, und ohne Beschönigung:
 *
 *   FTP    Zugangsdaten und Inhalt gehen im Klartext über die Leitung. Es ist
 *          hier, weil es auf einfachem Webspace oft das Einzige ist, was es
 *          gibt. Für ein versiegeltes Komplettbackup (AP8) ist das vertretbar,
 *          für die Zugangsdaten selbst nie — sie sind im Klartext mitzulesen.
 *          Die Oberfläche sagt das an der Stelle, an der man FTP auswählt.
 *   FTPS   verschlüsselt die Leitung, aber `ext/ftp` PRÜFT DAS ZERTIFIKAT
 *          NICHT. Nachgewiesen in `tools/versandprobe/` gegen einen Server mit
 *          selbst ausgestelltem Zertifikat ohne jede Vertrauenskette: Die
 *          Verbindung kommt zustande. Schutz gegen Mitlesen also ja, Schutz
 *          gegen einen untergeschobenen Server nein.
 *   SFTP   verschlüsselt UND erkennt den Server wieder: Der Fingerabdruck des
 *          Hostschlüssels wird beim ersten Mal übernommen und danach bei jeder
 *          Verbindung verglichen. Ändert er sich, bricht die Verbindung ab.
 *          Das ist das einzige der drei Protokolle mit dieser Eigenschaft und
 *          deshalb die Empfehlung.
 *
 * DIE GEHEIMNISSE LIEGEN VERSIEGELT IN DER DATENBANK
 * `geheim` (Passwort oder Passphrase) und `schluessel` (privater SSH-
 * Schlüssel) tragen `edsk1:`-Chiffren aus `serverkrypto_lib.php`. Der
 * Schlüssel dazu steht in `config.php` und damit NICHT im Datenbankdump. Ohne
 * Serverschlüssel lässt sich kein Ziel anlegen — die Oberfläche verlangt ihn
 * vorher, statt hinterher Klartext zu speichern.
 */

/**
 * Zeitlimit für Verbindungsaufbau und einzelne Übertragungen, in Sekunden.
 *
 * ZWANZIG UND NICHT SECHZIG: Diese Bibliothek läuft im Versandjob, und der
 * hat sein eigenes Budget (JOB_BUDGET_ANFRAGE = 3 s am Huckepack-Weg,
 * JOB_BUDGET_CLI = 300 s). Ein Ziel, das nicht antwortet, darf nicht die
 * Anfrage aufbrauchen, an der es huckepack hängt. Und für „Verbindung prüfen"
 * gilt dasselbe von der anderen Seite: Wer auf den Knopf drückt, wartet keine
 * Minute auf ein Ergebnis, das ohnehin „nicht erreichbar" lautet.
 */
const SZ_ZEITLIMIT = 20;

/**
 * Vorgabeports je Protokoll.
 *
 * `ftp` STEHT HIER NICHT MEHR (S10/AP4, E-S10-14). Diese Liste ist nicht bloss
 * eine Bequemlichkeit: `sz_pruefen_eingabe()` prüfte gegen SIE, nicht gegen
 * `SZ_PROTOKOLLE` (Fund F-6). Wer das Protokoll nur aus dem Anzeigekatalog
 * gestrichen hätte, hätte gar nichts abgeschafft — es wäre weiter
 * speicherbar gewesen, nur nicht mehr wählbar. Geprüft wird ab jetzt gegen
 * `SZ_PROTOKOLLE`, und beide Listen tragen dieselben Schlüssel; eine
 * Gegenprobe darunter zählt das nach, damit sie es auch morgen tun.
 */
const SZ_PORTS = ['ftps' => 21, 'sftp' => 22];

/** Anzeigenamen der Protokolle, in der Reihenfolge der Empfehlung. */
const SZ_PROTOKOLLE = [
    'sftp' => 'SFTP (SSH) — empfohlen',
    'ftps' => 'FTPS (FTP über TLS)',
];

/**
 * Ist dieses Protokoll erlaubt?
 *
 * DIE EINE STELLE, DIE DAS ENTSCHEIDET. Es gab zwei Listen und zwei
 * Meinungen; jetzt gibt es eine Frage. Wer ein Protokoll hinzunimmt, trägt
 * es in `SZ_PROTOKOLLE` und `SZ_PORTS` ein — und `sz_weg()` braucht einen
 * Zweig, sonst wirft es.
 */
function sz_protokoll_erlaubt(string $prot): bool
{
    return isset(SZ_PROTOKOLLE[$prot]) && isset(SZ_PORTS[$prot]);
}

/**
 * Ein Fehler auf dem Weg zum Ziel — mit einem Satz, der einer Betreiberin
 * etwas sagt.
 *
 * Kein `RuntimeException` von der Stange: Jeder Aufrufer will diese eine Art
 * Fehler fangen und anzeigen, und nicht zugleich einen Programmierfehler
 * verschlucken.
 */
class ZielFehler extends RuntimeException {}

/**
 * Was ein Backup-Ziel können muss.
 *
 * Alle Pfade sind RELATIV zum Grundpfad des Ziels. Kein Adapter nimmt einen
 * absoluten Pfad entgegen — sonst wäre der Grundpfad eine Empfehlung und
 * keine Grenze.
 */
interface Zielweg
{
    /** Verbindet und meldet an. Wirft ZielFehler. */
    public function verbinden(): void;

    /** Trennt. Darf mehrfach aufgerufen werden und wirft nie. */
    public function trennen(): void;

    /** Legt einen Unterordner an, wenn er fehlt. Ist er da, passiert nichts. */
    public function ordner(string $unter): void;

    /** Schiebt eine örtliche Datei hin. Gibt die Bytezahl zurück. */
    public function senden(string $ortsdatei, string $fern): int;

    /** Holt eine Datei her. Gibt die Bytezahl zurück. */
    public function holen(string $fern, string $ortsdatei): int;

    /** Listet einen Unterordner: Dateiname => Bytes. Fehlt er, ist es leer. */
    public function liste(string $unter = ''): array;

    /** Löscht eine Datei. */
    public function loeschen(string $fern): void;

    /** SHA-256 des Hostschlüssels (nur SFTP), sonst null. */
    public function fingerabdruck(): ?string;
}

/* ==========================================================================
 * Gemeinsames
 * ======================================================================== */

/**
 * Aus einer PHP-Warnung einen Satz machen, den man beantworten kann.
 *
 * Die Originalmeldung bleibt in Klammern stehen. Das ist Absicht: Der
 * verständliche Satz ist für die Betreiberin, der englische Rest für den Fall,
 * dass der verständliche Satz danebenliegt. Eine Meldung, die das Original
 * wegwirft, ist bei einem seltenen Fehler wertlos.
 */
function sz_klartext(string $roh): string
{
    /* Das `ftp_put(): ` am Anfang sagt nur, welche PHP-Funktion gesprochen
     * hat — für die Ursache ist es nie die Antwort. */
    $kern = preg_replace('/^[a-z_0-9]+\(\):\s*/', '', trim($roh)) ?? $roh;

    $muster = [
        '/getaddrinfo|Name or service not known|Temporary failure in name/i'
            => 'Der Rechnername lässt sich nicht auflösen — Schreibweise prüfen.',
        '/Connection refused/i'
            => 'Der Server nimmt auf diesem Port keine Verbindung an — Port prüfen.',
        '/timed out|Timeout|timeout/i'
            => 'Der Server hat innerhalb von ' . SZ_ZEITLIMIT . ' Sekunden nicht '
             . 'geantwortet — Rechnername, Port und Firewall prüfen.',
        /* „Authentication failed" ist das, was pyftpdlib und viele echte
         * Server sagen; „Login incorrect" sagt vsftpd. Beide meinen
         * dasselbe, und die Versandprobe ist über den ersten Wortlaut
         * gestolpert — die Meldung nannte das Passwort nicht. */
        '/Login incorrect|Authentication failed|Not logged in|530/i'
            => 'Nutzername oder Passwort stimmt nicht.',
        '/No space left|552|Quota|quota/i'
            => 'Auf dem Ziel ist kein Platz mehr.',
        /* Die letzten drei Wortlaute stammen von vsftpd und sind in der
         * Versandprobe gegen den ECHTEN Server aufgeschlagen: Es sagt weder
         * „Permission denied" noch nennt es einen Zahlencode, sondern
         * „Could not create file." — und die Meldung blieb dadurch halb
         * englisch. Genau dafuer gibt es den zweiten Satz Gegenstellen. */
        '/Permission denied|550|553|Access is denied|Could not create file'
        . '|Could not open file|Failed to open file|Create directory operation failed'
        . '|Not enough privileges|Operation not permitted/i'
            => 'Das Ziel verweigert den Zugriff — Pfad und Rechte prüfen. Ist '
             . 'das Verzeichnis beschreibbar, und ist noch Platz?',
        '/Passive mode|PASV|EPSV/i'
            => 'Der Wechsel in den passiven Modus ist gescheitert — der Schalter '
             . '„passiver Modus" steht vielleicht falsch.',
        '/SSL|TLS|certificate/i'
            => 'Die verschlüsselte Verbindung kam nicht zustande — spricht der '
             . 'Server überhaupt FTPS?',
    ];
    foreach ($muster as $re => $satz) {
        if (preg_match($re, $kern)) { return $satz . ' (' . $kern . ')'; }
    }
    return $kern;
}

/**
 * Ist dieser Name als ferner Datei- oder Ordnername brauchbar?
 *
 * Die Namen kommen aus dieser Anwendung (Kontokennung, Paketname) und nicht
 * von aussen. Geprüft wird trotzdem, und zwar hier an EINER Stelle: Ein
 * `..` im Namen wäre auf dem Ziel ein Schreibzugriff ausserhalb des
 * Grundpfads, und der Grundpfad ist das Einzige, was diese Anwendung dort
 * überhaupt eingrenzt.
 */
function sz_name_gueltig(string $name): bool
{
    if ($name === '' || strlen($name) > 190) { return false; }
    if (str_contains($name, '..') || str_contains($name, '\\')) { return false; }
    return (bool)preg_match('/^[A-Za-z0-9._-]+$/', $name);
}

/**
 * Grundpfad und Rest zu einem fernen Pfad zusammensetzen.
 *
 * Er heisst `grundpfad` und nicht `basis`: Die Wortliste (`tools/wortliste/`)
 * führt „Basis" als Luftbegriff — gemeint ist dort die Luftrettungsstation.
 * Hier wäre es dasselbe Wort in einer völlig anderen Bedeutung, und eine
 * Ausnahme dafuer einzutragen hiesse, die Liste um einen Fall zu erweitern,
 * den ein anderes Wort einfach vermeidet.
 */
function sz_pfad(string $grundpfad, string $rest): string
{
    $grundpfad = rtrim($grundpfad, '/');
    $rest  = ltrim($rest, '/');
    if ($grundpfad === '') { $grundpfad = '.'; }
    return $rest === '' ? $grundpfad : $grundpfad . '/' . $rest;
}

/* ==========================================================================
 * Adapter FTP und FTPS (PHP-Erweiterung ftp)
 * ======================================================================== */

/**
 * FTP und FTPS über `ext/ftp`.
 *
 * EIN ADAPTER FÜR BEIDE, weil sich genau eine Zeile unterscheidet
 * (`ftp_ssl_connect` statt `ftp_connect`). Zwei Klassen dafür wären zwei
 * Stellen, an denen dieselbe Fehlerbehandlung zu pflegen wäre.
 */
final class ZielFtp implements Zielweg
{
    /** @var resource|\FTP\Connection|null */
    private $verb = null;

    public function __construct(
        private string $host,
        private int    $port,
        private string $nutzer,
        private string $passwort,
        private string $grundpfad,
        private bool   $tls,
        private bool   $passiv,
    ) {}

    public function verbinden(): void
    {
        if (!function_exists('ftp_connect')) {
            throw new ZielFehler('Die PHP-Erweiterung „ftp" fehlt auf diesem '
                . 'Server. Ohne sie geht FTP und FTPS nicht — beim Hoster '
                . 'freischalten lassen oder SFTP verwenden.');
        }
        if ($this->tls && !function_exists('ftp_ssl_connect')) {
            throw new ZielFehler('Dieses PHP hat „ftp" ohne TLS-Unterstützung; '
                . 'FTPS geht damit nicht. SFTP verwenden.');
        }
        $verb = $this->ruf(
            fn() => $this->tls
                ? ftp_ssl_connect($this->host, $this->port, SZ_ZEITLIMIT)
                : ftp_connect($this->host, $this->port, SZ_ZEITLIMIT),
            'Die Verbindung zu ' . $this->host . ':' . $this->port
            . ' kam nicht zustande.',
            'Rechnername, Port und Firewall prüfen — der Server hat innerhalb '
            . 'von ' . SZ_ZEITLIMIT . ' Sekunden nicht geantwortet oder die '
            . 'Verbindung auf diesem Port abgelehnt.');
        $this->verb = $verb;
        $this->ruf(fn() => ftp_login($verb, $this->nutzer, $this->passwort),
                   'Die Anmeldung wurde abgelehnt.');
        /* NACH der Anmeldung, nicht davor: Vorher kennt der Server die
         * Sitzung noch nicht, und manche Server antworten dann mit einem
         * Fehler, der wie ein Netzproblem aussieht. */
        $this->ruf(fn() => ftp_pasv($verb, $this->passiv),
                   'Der Modus (passiv/aktiv) liess sich nicht setzen.');
        $this->ruf(fn() => ftp_chdir($verb, $this->grundpfad === '' ? '.' : $this->grundpfad),
                   'Der Pfad „' . $this->grundpfad . '" ist auf dem Ziel nicht zu erreichen.');
    }

    public function trennen(): void
    {
        if ($this->verb !== null) { @ftp_close($this->verb); $this->verb = null; }
    }

    public function ordner(string $unter): void
    {
        if ($unter === '') { return; }
        $verb = $this->wach();
        $ziel = sz_pfad($this->grundpfad, $unter);
        /* ERST HINEINGEHEN, DANN ANLEGEN. `ftp_mkdir` auf einem vorhandenen
         * Ordner ist ein Fehler, kein Erfolg — ein Adapter, der das nicht
         * unterscheidet, meldet bei jedem zweiten Lauf einen Fehlschlag. */
        if (@ftp_chdir($verb, $ziel)) { @ftp_chdir($verb, $this->grundpfad ?: '.'); return; }
        $this->ruf(fn() => ftp_mkdir($verb, $ziel),
                   'Der Ordner „' . $unter . '" liess sich auf dem Ziel nicht anlegen.');
    }

    public function senden(string $ortsdatei, string $fern): int
    {
        $verb = $this->wach();
        if (!is_file($ortsdatei)) {
            throw new ZielFehler('Die Datei „' . basename($ortsdatei) . '" gibt es hier nicht.');
        }
        $this->ruf(fn() => ftp_put($verb, sz_pfad($this->grundpfad, $fern),
                                   $ortsdatei, FTP_BINARY),
                   'Die Datei „' . basename($fern) . '" liess sich nicht übertragen.');
        return (int)filesize($ortsdatei);
    }

    public function holen(string $fern, string $ortsdatei): int
    {
        $verb = $this->wach();
        $this->ruf(fn() => ftp_get($verb, $ortsdatei, sz_pfad($this->grundpfad, $fern), FTP_BINARY),
                   'Die Datei „' . basename($fern) . '" liess sich nicht holen.');
        return (int)@filesize($ortsdatei);
    }

    public function liste(string $unter = ''): array
    {
        $verb = $this->wach();
        $ziel = sz_pfad($this->grundpfad, $unter);
        /* `ftp_mlsd` liefert Grösse und Art strukturiert und ist damit die
         * einzige der drei Listenfunktionen, deren Ergebnis nicht geraten
         * werden muss. Sie ist aber nicht überall da — deshalb der Rückfall
         * auf `ftp_nlist` plus `ftp_size`. */
        $raus = [];
        $mlsd = @ftp_mlsd($verb, $ziel);
        if (is_array($mlsd)) {
            foreach ($mlsd as $e) {
                if (($e['type'] ?? '') !== 'file') { continue; }
                $name = (string)($e['name'] ?? '');
                if ($name === '' || $name === '.' || $name === '..') { continue; }
                $raus[$name] = (int)($e['size'] ?? -1);
            }
            return $raus;
        }
        $namen = @ftp_nlist($verb, $ziel);
        if (!is_array($namen)) { return []; }
        foreach ($namen as $roh) {
            $name = basename((string)$roh);
            if ($name === '' || $name === '.' || $name === '..') { continue; }
            $groesse = @ftp_size($verb, sz_pfad($ziel, $name));
            /* -1 heisst bei ftp_size „konnte ich nicht sagen" — und genau das
             * heisst es hier auch, statt als 0 durchzugehen. Ordner geben
             * ebenfalls -1; eine Datei mit unbekannter Grösse und ein Ordner
             * sind über nlist nicht zu unterscheiden. */
            $raus[$name] = (int)$groesse;
        }
        return $raus;
    }

    public function loeschen(string $fern): void
    {
        $verb = $this->wach();
        $this->ruf(fn() => ftp_delete($verb, sz_pfad($this->grundpfad, $fern)),
                   'Die Datei „' . basename($fern) . '" liess sich nicht löschen.');
    }

    public function fingerabdruck(): ?string
    {
        /* FTP und FTPS haben nichts, womit sich ein Server wiedererkennen
         * liesse: FTP ist im Klartext, und `ext/ftp` prüft bei FTPS kein
         * Zertifikat (nachgewiesen in tools/versandprobe/). Hier `null`
         * zurückzugeben ist die ehrliche Antwort — eine erfundene Prüfsumme
         * wäre schlimmer als keine. */
        return null;
    }

    /** @return resource|\FTP\Connection */
    private function wach()
    {
        if ($this->verb === null) {
            throw new ZielFehler('Es besteht keine Verbindung zum Ziel.');
        }
        return $this->verb;
    }

    /**
     * Einen ftp_*-Aufruf machen und aus `false` einen brauchbaren Fehler.
     *
     * Die ftp-Funktionen melden über eine PHP-Warnung, was schiefging, und
     * geben `false` zurück. Wer sie mit `@` unterdrückt, verliert die einzige
     * Auskunft; wer sie stehen lässt, schreibt sie in die Seitenausgabe. Also
     * eingefangen und in die Ausnahme gepackt.
     */
    private function ruf(callable $fn, string $was, string $rat = '')
    {
        $warnung = null;
        set_error_handler(static function (int $no, string $text) use (&$warnung): bool {
            $warnung = $text;
            return true;
        });
        try {
            $r = $fn();
        } finally {
            restore_error_handler();
        }
        if ($r === false) {
            /* `$rat` springt nur ein, wenn PHP GAR NICHTS gesagt hat. Genau
             * das tut `ftp_connect()` auf einem geschlossenen Port: Es gibt
             * `false` zurück und schweigt. Die Meldung hiess dann „kam nicht
             * zustande." und liess die Betreiberin ohne den nächsten Schritt
             * stehen (Versandprobe, Teil 8). */
            throw new ZielFehler($was . ($warnung !== null
                ? ' ' . sz_klartext($warnung)
                : ($rat !== '' ? ' ' . $rat : '')));
        }
        return $r;
    }
}

/* ==========================================================================
 * Adapter SFTP (phpseclib, vendoriert unter server/vendor/)
 * ======================================================================== */

/**
 * SFTP über phpseclib 3.
 *
 * WARUM PHPSECLIB UND NICHT `ext/ssh2`. Die Erweiterung `ssh2` ist auf
 * geteiltem Webspace praktisch nie da und lässt sich dort auch nicht
 * nachinstallieren. phpseclib ist reines PHP und läuft überall, wo diese
 * Anwendung läuft — das ist der ganze Grund. Es kostet Rechenzeit
 * (Verschlüsselung in PHP statt in C); bei einer Handvoll Dateien je Nacht
 * fällt das nicht ins Gewicht.
 *
 * DER FINGERABDRUCK IST HIER KEIN BEIWERK, sondern der Grund, warum SFTP
 * empfohlen wird. Er wird VOR der Anmeldung geprüft: Wer sich bei einem
 * untergeschobenen Server anmeldet, hat sein Passwort schon abgegeben, auch
 * wenn er danach abbricht.
 */
final class ZielSftp implements Zielweg
{
    private ?\phpseclib3\Net\SFTP $sftp = null;
    private ?string $gesehen = null;

    public function __construct(
        private string  $host,
        private int     $port,
        private string  $nutzer,
        private string  $passwort,      // Passwort ODER Passphrase des Schlüssels
        private ?string $privatschluessel,
        private string  $grundpfad,
        private ?string $erwarteterFingerabdruck,
    ) {}

    public function verbinden(): void
    {
        require_once __DIR__ . '/vendor/laden.php';
        try {
            $sftp = new \phpseclib3\Net\SFTP($this->host, $this->port, SZ_ZEITLIMIT);
            /* Der Hostschlüssel steht fest, sobald der Schlüsselaustausch
             * durch ist — also vor jeder Anmeldung. Wirft phpseclib hier
             * schon, war der Server nicht zu erreichen. */
            $roh = $sftp->getServerPublicHostKey();
        } catch (\Throwable $e) {
            throw new ZielFehler('Die Verbindung zu ' . $this->host . ':' . $this->port
                . ' kam nicht zustande. ' . sz_klartext($e->getMessage()));
        }
        if ($roh === false) {
            throw new ZielFehler('Der Server hat keinen Hostschlüssel geliefert.');
        }
        $this->gesehen = sz_fingerabdruck($roh);
        $soll = $this->erwarteterFingerabdruck;
        if ($soll !== null && $soll !== '' && !hash_equals($soll, (string)$this->gesehen)) {
            throw new ZielFehler('Der Server meldet sich mit einem ANDEREN '
                . 'Hostschlüssel als beim letzten Mal. Erwartet war ' . $soll
                . ', gekommen ist ' . $this->gesehen . '. Es wurde nichts '
                . 'übertragen und kein Passwort gesendet. Entweder hat die '
                . 'Gegenstelle ihren Schlüssel gewechselt — dann den '
                . 'Fingerabdruck im Ziel löschen und neu übernehmen — oder es '
                . 'ist nicht dieselbe Gegenstelle.');
        }

        $anmeldung = $this->privatschluessel !== null && $this->privatschluessel !== ''
            ? $this->schluesselLaden()
            : $this->passwort;
        try {
            $ok = $sftp->login($this->nutzer, $anmeldung);
        } catch (\Throwable $e) {
            throw new ZielFehler('Die Anmeldung ist gescheitert. '
                . sz_klartext($e->getMessage()));
        }
        if (!$ok) {
            throw new ZielFehler($this->privatschluessel
                ? 'Die Anmeldung mit dem privaten Schlüssel wurde abgelehnt — '
                . 'passt der öffentliche Teil in der `authorized_keys` des Ziels?'
                : 'Nutzername oder Passwort stimmt nicht.');
        }
        $this->sftp = $sftp;
        if ($this->grundpfad !== '' && $this->grundpfad !== '.' && !$sftp->chdir($this->grundpfad)) {
            throw new ZielFehler('Der Pfad „' . $this->grundpfad . '" ist auf dem Ziel '
                . 'nicht zu erreichen.');
        }
    }

    public function trennen(): void
    {
        if ($this->sftp !== null) { $this->sftp->disconnect(); $this->sftp = null; }
    }

    public function ordner(string $unter): void
    {
        if ($unter === '') { return; }
        $sftp = $this->wach();
        $ziel = sz_pfad($this->grundpfad, $unter);
        if ($sftp->is_dir($ziel)) { return; }
        if (!$sftp->mkdir($ziel, -1, true)) {
            throw new ZielFehler('Der Ordner „' . $unter . '" liess sich auf dem '
                . 'Ziel nicht anlegen. ' . $this->letzter($sftp));
        }
    }

    public function senden(string $ortsdatei, string $fern): int
    {
        $sftp = $this->wach();
        if (!is_file($ortsdatei)) {
            throw new ZielFehler('Die Datei „' . basename($ortsdatei) . '" gibt es hier nicht.');
        }
        /* SOURCE_LOCAL_FILE liest die Datei häppchenweise. Ohne diese Angabe
         * würde phpseclib den ersten Parameter als INHALT nehmen — und ein
         * 25-MB-Paket läge als Zeichenkette im Speicher, gegen ein Budget von
         * 64 MB (Z3). */
        if (!$sftp->put(sz_pfad($this->grundpfad, $fern), $ortsdatei,
                        \phpseclib3\Net\SFTP::SOURCE_LOCAL_FILE)) {
            throw new ZielFehler('Die Datei „' . basename($fern) . '" liess sich '
                . 'nicht übertragen. ' . $this->letzter($sftp));
        }
        return (int)filesize($ortsdatei);
    }

    public function holen(string $fern, string $ortsdatei): int
    {
        $sftp = $this->wach();
        if ($sftp->get(sz_pfad($this->grundpfad, $fern), $ortsdatei) === false) {
            throw new ZielFehler('Die Datei „' . basename($fern) . '" liess sich '
                . 'nicht holen. ' . $this->letzter($sftp));
        }
        return (int)@filesize($ortsdatei);
    }

    public function liste(string $unter = ''): array
    {
        $sftp = $this->wach();
        $roh = $sftp->rawlist(sz_pfad($this->grundpfad, $unter));
        if (!is_array($roh)) { return []; }
        $raus = [];
        /* NET_SFTP_TYPE_REGULAR ist bei phpseclib eine GLOBALE Konstante, die
         * beim ersten Erzeugen eines SFTP-Objekts entsteht — keine
         * Klassenkonstante. `SFTP::TYPE_REGULAR` gibt es nicht; der erste
         * Versuch damit ist in der Versandprobe aufgeschlagen („Undefined
         * constant"), und zwar erst beim Auflisten, nicht beim Übertragen.
         * Der Rückfall auf die 1 ist der Wert aus dem Protokoll (RFC-Entwurf
         * filexfer-04, Abschnitt 5.2) und steht hier, damit ein Aufruf ohne
         * vorher erzeugtes Objekt nicht dasselbe noch einmal auslöst. */
        $regulaer = defined('NET_SFTP_TYPE_REGULAR') ? NET_SFTP_TYPE_REGULAR : 1;
        foreach ($roh as $name => $e) {
            if ($name === '.' || $name === '..' || !is_array($e)) { continue; }
            if ((int)($e['type'] ?? 0) !== $regulaer) { continue; }
            $raus[(string)$name] = (int)($e['size'] ?? -1);
        }
        return $raus;
    }

    public function loeschen(string $fern): void
    {
        $sftp = $this->wach();
        if (!$sftp->delete(sz_pfad($this->grundpfad, $fern), false)) {
            throw new ZielFehler('Die Datei „' . basename($fern) . '" liess sich '
                . 'nicht löschen. ' . $this->letzter($sftp));
        }
    }

    public function fingerabdruck(): ?string
    {
        return $this->gesehen;
    }

    private function schluesselLaden(): object
    {
        try {
            return \phpseclib3\Crypt\PublicKeyLoader::load(
                (string)$this->privatschluessel,
                $this->passwort === '' ? false : $this->passwort);
        } catch (\Throwable $e) {
            throw new ZielFehler('Der private Schlüssel liess sich nicht lesen. '
                . 'Ist er vollständig eingefügt (mit den BEGIN- und END-Zeilen), '
                . 'und stimmt die Passphrase? (' . $e->getMessage() . ')');
        }
    }

    private function wach(): \phpseclib3\Net\SFTP
    {
        if ($this->sftp === null) {
            throw new ZielFehler('Es besteht keine Verbindung zum Ziel.');
        }
        return $this->sftp;
    }

    /** Die letzte Auskunft des Servers, wenn es eine gibt. */
    private function letzter(\phpseclib3\Net\SFTP $sftp): string
    {
        $s = trim((string)$sftp->getLastSFTPError());
        return $s === '' ? '' : sz_klartext($s);
    }
}

/**
 * Fingerabdruck eines SSH-Hostschlüssels, geschrieben wie OpenSSH ihn zeigt.
 *
 * `SHA256:` plus base64 ohne Füllzeichen — dasselbe, was
 * `ssh-keyscan host | ssh-keygen -lf -` ausgibt. Das ist der Punkt: Wer den
 * Fingerabdruck in der Oberfläche vergleichen soll, muss ihn irgendwo her
 * haben, und diese eine Zeile hat jede Administratorin schon einmal gesehen.
 */
function sz_fingerabdruck(string $hostschluessel): string
{
    /* phpseclib liefert „ssh-rsa AAAAB3Nza..." — der zweite Teil ist der
     * base64-kodierte Rohschlüssel, und genau über den rechnet OpenSSH. */
    $teile = explode(' ', trim($hostschluessel));
    $roh = count($teile) >= 2 ? base64_decode($teile[1], true) : false;
    if ($roh === false) { $roh = $hostschluessel; }
    return 'SHA256:' . rtrim(base64_encode(hash('sha256', $roh, true)), '=');
}

/* ==========================================================================
 * Die Ziele in der Datenbank
 * ======================================================================== */

/** Alle Ziele, in Namensfolge. `$nurAktive` blendet abgeschaltete aus. */
function sz_alle(bool $nurAktive = false): array
{
    require_once __DIR__ . '/db.php';
    $sql = 'SELECT * FROM backup_targets';
    if ($nurAktive) { $sql .= ' WHERE aktiv = 1'; }
    $sql .= ' ORDER BY name';
    try {
        return db()->query($sql)->fetchAll();
    } catch (Throwable $e) {
        /* Die Tabelle fehlt, solange update.php nicht gelaufen ist. Eine leere
         * Liste ist hier die richtige Antwort — die Oberfläche sagt an ihrer
         * Stelle, dass die Migration aussteht, und tut es verständlicher, als
         * es eine SQL-Ausnahme könnte. */
        return [];
    }
}

/** Gibt es die Tabelle schon? (Migration 2026_09_01_sicherungsziele) */
function sz_tabelle_da(): bool
{
    require_once __DIR__ . '/db.php';
    try {
        $q = db()->query("SELECT COUNT(*) FROM information_schema.tables
                          WHERE table_schema = DATABASE()
                            AND table_name = 'backup_targets'");
        return (int)$q->fetchColumn() > 0;
    } catch (Throwable $e) {
        return false;
    }
}

/** Ein Ziel, oder null. */
function sz_lesen(int $id): ?array
{
    require_once __DIR__ . '/db.php';
    $st = db()->prepare('SELECT * FROM backup_targets WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r === false ? null : $r;
}

/**
 * Eingaben prüfen. Gibt `[$sauber, $fehler]` zurück; `$fehler` ist eine Liste
 * von Sätzen und leer, wenn alles passt.
 *
 * WARUM DAS NICHT ÜBER `validate_lib.php` LÄUFT. Die gemeinsame Prüfschicht
 * gilt für EINSATZDATEN — das ist die Zusage aus CLAUDE.md Abschnitt 4, und
 * sie bleibt unangetastet. Ein Backup-Ziel ist keine Einsatzdatei, kommt
 * nicht von der Uhr, nicht aus einem Backup und nicht über die API,
 * sondern aus genau einem Adminformular.
 */
function sz_pruefen_eingabe(array $e): array
{
    $f = [];
    $name = trim((string)($e['name'] ?? ''));
    if ($name === '' || mb_strlen($name) > 190) {
        $f[] = 'Der Name fehlt oder ist zu lang (höchstens 190 Zeichen).';
    }
    $prot = (string)($e['protokoll'] ?? '');
    if (!sz_protokoll_erlaubt($prot)) {
        /* DER SATZ NENNT DEN GRUND, nicht bloss „unbekannt". Wer ein
         * bestehendes `ftp`-Ziel bearbeitet, soll nicht rätseln, warum das
         * Protokoll, das gestern noch dastand, heute abgewiesen wird. */
        $f[] = $prot === 'ftp'
            ? 'FTP überträgt alles im Klartext, auch Nutzername und Passwort. '
              . 'Es wird seit Web 20.2.0 nicht mehr angeboten — bitte SFTP oder '
              . 'FTPS wählen und Port und Zugangsdaten dazu neu setzen.'
            : 'Unbekanntes Protokoll.';
    }
    $host = trim((string)($e['host'] ?? ''));
    if ($host === '' || mb_strlen($host) > 190
        || !preg_match('/^[A-Za-z0-9._:\[\]-]+$/', $host)) {
        $f[] = 'Der Rechnername fehlt oder enthält Zeichen, die dort nicht '
             . 'hingehören (erlaubt: Buchstaben, Ziffern, Punkt, Bindestrich, '
             . 'Doppelpunkt für IPv6).';
    }
    $port = (int)($e['port'] ?? 0);
    if ($port < 1 || $port > 65535) { $f[] = 'Der Port muss zwischen 1 und 65535 liegen.'; }
    $nutzer = trim((string)($e['nutzer'] ?? ''));
    if ($nutzer === '' || mb_strlen($nutzer) > 190) { $f[] = 'Der Nutzername fehlt.'; }
    $pfad = trim((string)($e['pfad'] ?? ''));
    if ($pfad === '') { $pfad = '/'; }
    if (mb_strlen($pfad) > 255 || str_contains($pfad, '..')) {
        $f[] = 'Der Pfad ist zu lang oder enthält „..".';
    }
    /* ---- Die Loeschregel (P5a/AP10, E-P5a-03) ----------------------------
     *
     * SIE IST AUS, WENN DER HAKEN AUS IST — und dann stehen die beiden
     * Zahlen auf `NULL`, nicht auf `0`. Der Unterschied ist der zwischen
     * „hier wird nicht aufgeraeumt" und „hier wird alles weggeraeumt". Eine
     * `0`, die als „Option aus" gemeint war und als „nichts behalten"
     * gelesen wird, loescht das Ziel leer; das ist genau der Fehler, gegen
     * den Backlog Nr. 49 die Option ueberhaupt erst zur Option macht.
     *
     * MINDESTENS 1. Wer aufraeumen laesst, behaelt mindestens einen Stand.
     * Ein Ziel, auf dem nichts mehr liegt, ist kein Sicherungsziel. */
    $auf = !empty($e['aufraeumen']);
    $bk = $bm = null;
    if ($auf) {
        $bk = (int)($e['behalten_konto'] ?? 0);
        $bm = (int)($e['behalten_komplett'] ?? 0);
        if ($bk < 1 || $bk > 999) {
            $f[] = 'Wie viele Konto-Sicherungen dort bleiben sollen, muss '
                 . 'zwischen 1 und 999 liegen.';
            $bk = null;
        }
        if ($bm < 1 || $bm > 999) {
            $f[] = 'Wie viele Komplett-Stände dort bleiben sollen, muss '
                 . 'zwischen 1 und 999 liegen.';
            $bm = null;
        }
    }

    return [[
        'name'      => $name,
        'protokoll' => $prot,
        'host'      => $host,
        'port'      => $port,
        'nutzer'    => $nutzer,
        'pfad'      => $pfad,
        'passiv'    => !empty($e['passiv']) ? 1 : 0,
        'aktiv'     => !empty($e['aktiv']) ? 1 : 0,
        'behalten_konto'    => $bk,
        'behalten_komplett' => $bm,
    ], $f];
}

/**
 * Anlegen oder ändern. Gibt `[true, id]` oder `[false, [fehler, ...]]`.
 *
 * `$geheim` und `$schluessel` sind KLARTEXT und dürfen `null` sein — dann
 * bleibt beim Ändern stehen, was gespeichert ist. Genau dafür ist das
 * Passwortfeld im Formular leer: Ein Formular, das das Passwort im Klartext
 * zurückschickt, damit es beim Speichern nicht verlorengeht, hat es einmal
 * mehr über die Leitung geschickt, als nötig war.
 */
function sz_speichern(?int $id, array $eingabe, ?string $geheim, ?string $schluessel): array
{
    require_once __DIR__ . '/db.php';
    [$s, $f] = sz_pruefen_eingabe($eingabe);
    if (!serverschluessel_da()) {
        $f[] = 'Ohne Serverschlüssel lassen sich keine Zugangsdaten speichern.';
    }
    if ($id === null && $geheim === null && $schluessel === null) {
        $f[] = 'Ein neues Ziel braucht ein Passwort oder einen privaten Schlüssel.';
    }
    if ($f) { return [false, $f]; }

    $st = db()->prepare('SELECT id FROM backup_targets WHERE name = ? AND id <> ?');
    $st->execute([$s['name'], $id ?? 0]);
    if ($st->fetchColumn() !== false) {
        return [false, ['Es gibt schon ein Ziel mit diesem Namen.']];
    }

    /* Der Zweck in den Zusatzdaten bindet die Chiffre an DIESES Ziel und
     * DIESES Feld (serverkrypto_lib.php). Beim Anlegen ist die Kennung noch
     * nicht bekannt — deshalb wird zuerst die Zeile geschrieben und das
     * Geheimnis danach nachgetragen. */
    if ($id === null) {
        $st = db()->prepare('INSERT INTO backup_targets
              (name, protokoll, host, port, nutzer, pfad, passiv, aktiv,
               behalten_konto, behalten_komplett, erstellt_am)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $st->execute([$s['name'], $s['protokoll'], $s['host'], $s['port'],
                      $s['nutzer'], $s['pfad'], $s['passiv'], $s['aktiv'],
                      $s['behalten_konto'], $s['behalten_komplett']]);
        $id = (int)db()->lastInsertId();
    } else {
        $st = db()->prepare('UPDATE backup_targets SET name = ?, protokoll = ?,
              host = ?, port = ?, nutzer = ?, pfad = ?, passiv = ?, aktiv = ?,
              behalten_konto = ?, behalten_komplett = ?
              WHERE id = ?');
        $st->execute([$s['name'], $s['protokoll'], $s['host'], $s['port'],
                      $s['nutzer'], $s['pfad'], $s['passiv'], $s['aktiv'],
                      $s['behalten_konto'], $s['behalten_komplett'], $id]);
    }

    if ($geheim !== null) {
        db()->prepare('UPDATE backup_targets SET geheim = ? WHERE id = ?')
            ->execute([$geheim === '' ? null : sk_versiegeln($geheim, sz_zweck($id, 'geheim')), $id]);
    }
    if ($schluessel !== null) {
        db()->prepare('UPDATE backup_targets SET schluessel = ? WHERE id = ?')
            ->execute([$schluessel === '' ? null
                       : sk_versiegeln($schluessel, sz_zweck($id, 'schluessel')), $id]);
    }
    return [true, $id];
}

/** Der Zweck einer Chiffre — Kennung des Ziels plus Feldname. */
function sz_zweck(int $id, string $feld): string
{
    return 'sicherungsziel:' . $id . ':' . $feld;
}

/** Löschen. Die Datei auf dem Ziel bleibt, wo sie ist — das ist Absicht. */
function sz_loeschen(int $id): bool
{
    require_once __DIR__ . '/db.php';
    $st = db()->prepare('DELETE FROM backup_targets WHERE id = ?');
    $st->execute([$id]);
    return $st->rowCount() > 0;
}

/** Den Fingerabdruck übernehmen (erste Verbindung oder nach einem Wechsel). */
function sz_fingerabdruck_merken(int $id, ?string $abdruck): void
{
    require_once __DIR__ . '/db.php';
    db()->prepare('UPDATE backup_targets SET fingerabdruck = ? WHERE id = ?')
        ->execute([$abdruck, $id]);
}

/** Ergebnis eines Laufs festhalten — Zeitpunkt und, wenn er schieflief, warum. */
function sz_lauf_merken(int $id, bool $gut, ?string $fehler = null): void
{
    require_once __DIR__ . '/db.php';
    if ($gut) {
        db()->prepare('UPDATE backup_targets SET letzter_lauf = UTC_TIMESTAMP(),
                       letzter_erfolg = UTC_TIMESTAMP(), letzter_fehler = NULL
                       WHERE id = ?')->execute([$id]);
    } else {
        db()->prepare('UPDATE backup_targets SET letzter_lauf = UTC_TIMESTAMP(),
                       letzter_fehler = ? WHERE id = ?')
            ->execute([mb_substr((string)$fehler, 0, 2000), $id]);
    }
}

/**
 * Ein Geheimnis öffnen. `null` heisst „nicht zu öffnen", und der Aufrufer
 * muss das sagen — nicht mit einem leeren Passwort weitermachen.
 */
function sz_geheim(array $ziel, string $feld): ?string
{
    $roh = (string)($ziel[$feld] ?? '');
    if ($roh === '') { return ''; }
    return sk_oeffnen($roh, sz_zweck((int)$ziel['id'], $feld));
}

/**
 * Den passenden Adapter für ein Ziel bauen.
 *
 * Hier — und nur hier — werden die Geheimnisse geöffnet. Wer diese Funktion
 * nicht benutzt, kommt an sie nicht heran.
 */
function sz_weg(array $ziel): Zielweg
{
    $geheim = sz_geheim($ziel, 'geheim');
    $schluessel = sz_geheim($ziel, 'schluessel');
    if ($geheim === null || $schluessel === null) {
        throw new ZielFehler('Die Zugangsdaten dieses Ziels lassen sich nicht '
            . 'entschlüsseln. Sie wurden mit einem ANDEREN Serverschlüssel '
            . 'gespeichert als dem, der jetzt in config.php steht. Entweder den '
            . 'alten Schlüssel wieder eintragen oder die Zugangsdaten hier neu '
            . 'erfassen.');
    }
    $prot = (string)$ziel['protokoll'];
    /* DER ENGPASS, UND ER PRÜFT POSITIV (S10/AP4, E-S10-U-10).
     *
     * Hier stand ein einziger benannter Zweig (`sftp`), und alles andere fiel
     * in `ZielFtp`, wo `$prot === 'ftps'` über TLS entscheidet. FTPS war
     * damit geschützt — ein UNBEKANNTES oder LEERES Protokoll aber fiel
     * still auf Klartext-FTP zurück, und dann gehen Nutzername und Passwort
     * offen über Port 21.
     *
     * Geprüft wird deshalb gegen den Katalog und nicht auf `!== 'ftp'`: Ein
     * `ENUM`, das je nach `sql_mode` zum Leerstring wird, ist im Projekt
     * belegt (`backup_lib.php`, „die ENUM-Falle"), und `db.php` setzt kein
     * `sql_mode`. Ein Wert, den diese Anwendung nicht kennt, bekommt keine
     * Verbindung — weder im Versand noch bei „Verbindung prüfen". */
    if (!sz_protokoll_erlaubt($prot)) {
        throw new ZielFehler('Dieses Ziel trägt das Protokoll „'
            . ($prot === '' ? '(leer)' : $prot) . '", das diese Fassung nicht '
            . 'mehr kennt. Es wird nichts gesendet. Bitte das Ziel auf SFTP '
            . 'oder FTPS umstellen (Protokoll, Port und Zugangsdaten).');
    }
    if ($prot === 'sftp') {
        return new ZielSftp((string)$ziel['host'], (int)$ziel['port'],
            (string)$ziel['nutzer'], $geheim,
            $schluessel === '' ? null : $schluessel,
            (string)$ziel['pfad'],
            ($ziel['fingerabdruck'] ?? null) === null ? null : (string)$ziel['fingerabdruck']);
    }
    return new ZielFtp((string)$ziel['host'], (int)$ziel['port'],
        (string)$ziel['nutzer'], $geheim, (string)$ziel['pfad'],
        $prot === 'ftps', (bool)(int)$ziel['passiv']);
}

/**
 * „Verbindung prüfen": verbinden, eine Probedatei schreiben, wieder lesen,
 * löschen, trennen.
 *
 * WARUM NICHT NUR VERBINDEN. Eine Anmeldung, die klappt, sagt nichts darüber,
 * ob dort auch geschrieben werden darf — und genau daran scheitert ein
 * Versand später, nachts, ohne Zuschauer. Die Probe schreibt deshalb
 * tatsächlich; sie ist ein paar Dutzend Byte gross und wird danach wieder
 * entfernt. Bleibt sie liegen (weil das Löschen scheitert), sagt die Meldung
 * ihren Namen — dann liegt dort eine Datei, die von Hand wegzuräumen ist.
 *
 * Gibt `['ok' => bool, 'meldung' => string, 'fingerabdruck' => ?string,
 *        'schritte' => [text, ...]]` zurück.
 */
function sz_verbindung_pruefen(array $ziel): array
{
    $schritte = [];
    $name = 'edverbindungsprobe-' . bin2hex(random_bytes(6)) . '.txt';
    $inhalt = "Verbindungsprobe der Einsatzdokumentation.\n"
            . "Diese Datei darf gelöscht werden.\n";
    $tmp = tempnam(sys_get_temp_dir(), 'sz');
    if ($tmp === false) {
        return ['ok' => false, 'meldung' => 'Es liess sich keine örtliche '
            . 'Probedatei anlegen.', 'fingerabdruck' => null, 'schritte' => []];
    }
    file_put_contents($tmp, $inhalt);
    $zurueck = $tmp . '.zurueck';
    $weg = null;
    try {
        $weg = sz_weg($ziel);
        $weg->verbinden();
        $schritte[] = 'Verbunden und angemeldet.';
        $abdruck = $weg->fingerabdruck();
        if ($abdruck !== null) { $schritte[] = 'Hostschlüssel ' . $abdruck; }

        $weg->senden($tmp, $name);
        $schritte[] = 'Probedatei geschrieben (' . strlen($inhalt) . ' Byte).';

        $liste = $weg->liste();
        $schritte[] = 'Verzeichnis gelesen: ' . count($liste)
                    . (count($liste) === 1 ? ' Datei' : ' Dateien')
                    . ($liste === [] ? '' : ', darunter die Probe');

        $weg->holen($name, $zurueck);
        $gelesen = (string)@file_get_contents($zurueck);
        if ($gelesen !== $inhalt) {
            throw new ZielFehler('Die zurückgelesene Probedatei ist eine andere '
                . 'als die geschriebene (' . strlen($gelesen) . ' statt '
                . strlen($inhalt) . ' Byte).');
        }
        $schritte[] = 'Zurückgelesen und Byte für Byte verglichen.';

        $weg->loeschen($name);
        $schritte[] = 'Probedatei wieder gelöscht.';
        $weg->trennen();
        return ['ok' => true, 'meldung' => 'Die Verbindung steht, und es lässt '
            . 'sich dort schreiben, lesen und löschen.',
            'fingerabdruck' => $abdruck, 'schritte' => $schritte];
    } catch (ZielFehler $e) {
        if ($weg !== null) { try { $weg->trennen(); } catch (Throwable $x) {} }
        return ['ok' => false, 'meldung' => $e->getMessage(),
                'fingerabdruck' => $weg?->fingerabdruck(), 'schritte' => $schritte];
    } catch (Throwable $e) {
        if ($weg !== null) { try { $weg->trennen(); } catch (Throwable $x) {} }
        return ['ok' => false, 'meldung' => 'Unerwarteter Fehler: ' . $e->getMessage(),
                'fingerabdruck' => null, 'schritte' => $schritte];
    } finally {
        @unlink($tmp);
        @unlink($zurueck);
    }
}

/* ==========================================================================
 * Der Versand
 * ======================================================================== */

/**
 * Schlüssel in `app_state`: Versendet die Anwendung von selbst?
 *
 * EIN SCHALTER UND KEIN ZEITPLAN. E-S2-22 spricht von einem „Zeitplan für den
 * Push im Admin-Bereich"; einen eigenen Zeitplan gibt es hier trotzdem nicht,
 * und zwar aus demselben Grund wie bei allen anderen Jobs: Wann etwas läuft,
 * entscheidet der eingerichtete Auslöser (Cron, Token-Aufruf oder huckepack an
 * einer Anfrage — Wartungsseite), nicht eine zweite Uhr in der Datenbank. Zwei
 * Zeitpläne nebeneinander wären zwei Wahrheiten. Der Schalter sagt also nicht
 * WANN, sondern OB.
 */
const SZ_AUTO_SCHLUESSEL = 'versand_auto';

/** Reserve für einen Versandschub, in Sekunden. */
const SZ_VERSAND_RESERVE_S = 25.0;

/** Läuft der Versand von selbst mit dem Wartungsjob? */
function sz_auto_an(): bool
{
    require_once __DIR__ . '/adminbackup_lib.php';
    return edbak_marke_lesen(SZ_AUTO_SCHLUESSEL) === '1';
}

/** Den Schalter setzen. */
function sz_auto_setzen(bool $an): bool
{
    require_once __DIR__ . '/adminbackup_lib.php';
    return edbak_marke_setzen(SZ_AUTO_SCHLUESSEL, $an ? '1' : '0');
}

/**
 * Ein Schub Versand: neue Pakete auf die aktiven Ziele schieben.
 *
 * WAS „NEU" HEISST, WIRD AM ZIEL ABGELESEN und nicht in einer eigenen Tabelle
 * geführt. Der Job listet den Zielordner und schickt, was dort fehlt. Das ist
 * eine Anfrage mehr je Konto und dafür immer richtig: Eine Merkliste in der
 * Datenbank behauptet „schon versandt" auch dann noch, wenn die Datei am Ziel
 * längst gelöscht, das Ziel neu aufgesetzt oder der Pfad geändert wurde. Diese
 * Art Lüge fällt erst auf, wenn man das Backup braucht.
 *
 * ES WIRD NUR HINZUGEFÜGT, NIE GELÖSCHT. Die Aufbewahrung („zwei je Konto")
 * gilt für die Ablage auf diesem Server; auf dem Ziel räumt niemand auf. Das
 * ist bewusst so: Der Zweck eines auswärtigen Ziels ist, den Ausfall DIESES
 * Servers zu überleben — samt eines Fehlers, der hier zu viel löscht. Wer dort
 * aufräumen will, tut es dort. (Backlog Nr. 49.)
 *
 * @param callable $zeitLinks gibt die verbleibenden Sekunden
 * @return array ['gesendet' => int, 'bytes' => int, 'ziele' => int,
 *                'fehler' => [text, ...], 'fertig' => bool,
 *                'uebersprungen' => int, 'uebersprungen_namen' => [text, ...]]
 *
 * WARUM „uebersprungen" NICHT IN „fehler" GEHÖRT (S10/AP4, E-S10-U-09).
 * `jobs_lib.php` wirft, sobald `fehler` nicht leer ist — und das mit gutem
 * Grund: Sonst meldete die Wartungsseite „grün", während seit drei Wochen
 * nichts hinausgeht. Ein Ziel, das planmäßig übergangen wird, ist aber keine
 * Störung; stünde sein Vermerk dort, wäre der Versandjob dauerhaft rot und
 * das Signal für echte Störungen verbrannt. Deshalb zwei getrennte Zahlen.
 */
function sz_versand_schub(callable $zeitLinks, float $reserve = SZ_VERSAND_RESERVE_S): array
{
    require_once __DIR__ . '/adminbackup_lib.php';
    $ziele = sz_alle(true);
    $raus = ['gesendet' => 0, 'bytes' => 0, 'ziele' => count($ziele),
             'fehler' => [], 'fertig' => true,
             'uebersprungen' => 0, 'uebersprungen_namen' => [],
             /* P5a/AP10: was die Aufbewahrungsregel dort entfernt hat — und
              * was deshalb nicht noch einmal hinuebergeschickt wurde. */
             'geloescht' => 0, 'geloescht_bytes' => 0, 'nicht_wieder' => 0];
    if ($ziele === []) { return $raus; }

    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return $raus; }
    /* Die Kontoordner EINMAL lesen, nicht je Ziel. */
    $ordner = [];
    foreach (scandir($wurzel) ?: [] as $n) {
        if ($n === '.' || $n === '..') { continue; }
        if (!edbak_kennung_gueltig($n)) { continue; }
        if (is_dir($wurzel . '/' . $n)) { $ordner[] = $n; }
    }
    sort($ordner);

    /* DIE KOMPLETT-BACKUP GEHT DENSELBEN WEG (S2/AP8, E-S2-21).
     *
     * Sie liegt in `sicherungen/komplett/` und nicht in einem Kontoordner —
     * `edbak_kennung_gueltig()` weist den Namen ab, die Schleife oben
     * uebergeht ihn also von selbst. Angehaengt wird er HIER und
     * ausdruecklich, damit niemand spaeter raetselt, warum die eine Datei,
     * auf die es beim Ausfall des ganzen Servers ankommt, als einzige liegen
     * bleibt.
     *
     * SIE STEHT VORN. Wenn die Zeit eines Schubes nicht fuer alles reicht,
     * soll das Uebriggebliebene ein Kontopaket sein und nicht das
     * Komplett-Backup: Aus ihm laesst sich jedes Konto wiederherstellen,
     * umgekehrt nicht. */
    require_once __DIR__ . '/komplett_lib.php';
    if (is_dir(komp_wurzel())) { array_unshift($ordner, KOMP_ORDNER); }

    foreach ($ziele as $z) {
        if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break; }

        /* ÜBERGANGEN STATT BESCHICKT (S10/AP4, E-S10-14).
         *
         * Ein Ziel mit einem Protokoll, das diese Fassung nicht mehr kennt,
         * wird NICHT im Klartext beliefert und NICHT als Störung gezählt. Es
         * bekommt einen Vermerk an sich selbst (rote Plakette auf der
         * Zielseite) und eine Zahl im Lauf. `continue` VOR `sz_weg()`: Jene
         * würfe sonst, und der Wurf landete in `fehler`. */
        if (!sz_protokoll_erlaubt((string)$z['protokoll'])) {
            $raus['uebersprungen']++;
            $raus['uebersprungen_namen'][] = (string)$z['name'];
            sz_lauf_merken((int)$z['id'], false,
                'Übergangen: Das Protokoll „' . (string)$z['protokoll']
                . '" wird nicht mehr beschickt, weil es unverschlüsselt '
                . 'überträgt. Bitte das Ziel auf SFTP oder FTPS umstellen.');
            continue;
        }

        $weg = null;
        try {
            $weg = sz_weg($z);
            $weg->verbinden();
        } catch (ZielFehler $e) {
            $raus['fehler'][] = (string)$z['name'] . ': ' . $e->getMessage();
            sz_lauf_merken((int)$z['id'], false, $e->getMessage());
            if ($weg !== null) { try { $weg->trennen(); } catch (Throwable $x) {} }
            continue;
        }

        $fehlerHier = null;
        $gesendetHier = 0;
        /* WAS DIE REGEL DORT ENTFERNT HAT, GEHT NICHT WIEDER HINUEBER
         * (E-P5a-57). Ohne diese Zeile senden und loeschen sich Versand und
         * Aufbewahrung gegenseitig im Kreis — die Begruendung steht bei
         * `sz_dort_entfernt()`. Einmal je Ziel gelesen, nicht je Datei. */
        $nichtWieder = sz_dort_entfernt((int)$z['id']);
        try {
            foreach ($ordner as $kennung) {
                /* Die Zeit wird JE KONTO geprüft, nicht je Ziel: Ein Schub,
                 * der mitten in einer Übertragung von der Zeit eingeholt
                 * wird, hinterlässt am Ziel eine halbe Datei. */
                if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break; }
                $pakete = $kennung === KOMP_ORDNER
                    ? array_map(fn(array $s): array => ['datei' => $s['datei'],
                                                        'groesse' => $s['groesse']],
                                komp_staende())
                    : edbak_pakete($kennung);
                if ($pakete === []) { continue; }
                $weg->ordner($kennung);
                $dort = $weg->liste($kennung);
                foreach ($pakete as $p) {
                    if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break 2; }
                    $name = (string)$p['datei'];
                    if (!sz_name_gueltig($name)) { continue; }
                    if (isset($nichtWieder[$kennung . '/' . $name])) {
                        $raus['nicht_wieder']++;
                        continue;
                    }
                    /* SCHON DA HEISST: gleicher Name UND gleiche Grösse. Nur
                     * der Name wäre zu wenig — eine abgebrochene Übertragung
                     * hinterlässt eine Datei mit dem richtigen Namen und der
                     * falschen Länge, und die gälte für immer als erledigt. */
                    $bytes = (int)$p['groesse'];
                    if (isset($dort[$name]) && $dort[$name] === $bytes) {
                        /* SCHON DA — UND TROTZDEM EIN VERMERK (P5a/AP10).
                         *
                         * Ohne diese Zeile faenge das Versandprotokoll erst
                         * mit dem naechsten NEUEN Paket an, und alles, was
                         * heute schon drueben liegt, gaelte fuer immer als
                         * fremd. Die Aufbewahrungsregel hiesse dann „raeumt
                         * irgendwann in einem Jahr das erste Mal auf".
                         *
                         * Der Beleg ist gut genug: gleicher Name, gleiche
                         * Groesse, und die Datei liegt HIER im eigenen
                         * Sicherungsordner. Ein fremdes Paket muesste dafuer
                         * Zeitstempel, Zufallskennung und Bytezahl einer
                         * unserer Dateien treffen.
                         *
                         * WAS DAMIT NICHT ERFASST WIRD: Sicherungen, die
                         * drueben liegen und hier schon weggeraeumt sind
                         * (die oertliche Regel behaelt zwei je Konto). Die
                         * bleiben unbekannt und werden nie angefasst — die
                         * sichere Richtung, und sie steht so im Handbuch. */
                        sz_versand_vermerken((int)$z['id'], $kennung, $name, $bytes);
                        continue;
                    }
                    $hier = $kennung === KOMP_ORDNER
                        ? komp_wurzel() . '/' . $name
                        : edbak_ordner($kennung) . '/' . $name;
                    $weg->senden($hier, $kennung . '/' . $name);
                    /* DAS PROTOKOLL STEHT DIREKT HINTER DEM VERSAND
                     * (P5a/AP10). Es ist die zweite der drei Sicherungen der
                     * Loeschregel: Ohne diese Zeile gilt die Datei drueben
                     * spaeter als fremd und wird nie angefasst — die sichere
                     * Richtung, aber die falsche Auskunft. */
                    sz_versand_vermerken((int)$z['id'], $kennung, $name, $bytes);
                    $raus['gesendet']++;
                    $raus['bytes'] += $bytes;
                    $gesendetHier++;
                }
            }
        } catch (ZielFehler $e) {
            $fehlerHier = $e->getMessage();
            $raus['fehler'][] = (string)$z['name'] . ': ' . $fehlerHier;
            $raus['fertig'] = false;
        }
        /* ---- DIE DRITTE SICHERUNG DER LOESCHREGEL (P5a/AP10, E-P5a-03) ---
         *
         * Aufgeraeumt wird NUR in einem Lauf, dessen eigener Versand
         * durchgelaufen ist. Wer nicht sicher weiss, dass der neue Stand
         * drueben angekommen ist, raeumt den alten nicht weg — sonst
         * entfernte ausgerechnet ein halb gescheiterter Lauf die
         * Sicherungen, die er nicht ersetzen konnte.
         *
         * `fertig === false` heisst: Die Zeit des Schubes ist ausgegangen,
         * es liegt also noch etwas an. Auch dann nicht — der naechste Schub
         * sendet zuerst zu Ende und raeumt dann auf. */
        if ($fehlerHier === null && $raus['fertig']) {
            $auf = sz_aufraeumen($z, $weg, $zeitLinks, $reserve);
            $raus['geloescht']       += $auf['geloescht'];
            $raus['geloescht_bytes'] += $auf['bytes'];
            if (!$auf['fertig']) { $raus['fertig'] = false; }
            foreach ($auf['fehler'] as $f) {
                $raus['fehler'][] = (string)$z['name'] . ' (Aufbewahrung): ' . $f;
            }
        }
        try { $weg->trennen(); } catch (Throwable $x) {}
        sz_lauf_merken((int)$z['id'], $fehlerHier === null, $fehlerHier);
    }
    return $raus;
}

/**
 * Wie viele Dateien warten noch? Für die Rückstandsanzeige der Wartungsseite.
 *
 * SIE FRAGT DIE ZIELE NICHT. Eine Rückstandszahl wird bei jedem Aufruf der
 * Wartungsseite gebildet; dafür drei FTP-Verbindungen aufzubauen wäre eine
 * Seite, die zehn Sekunden lädt. Gezählt wird stattdessen, was HIER liegt und
 * seit dem letzten erfolgreichen Versand dieses Ziels dazugekommen ist — eine
 * Schätzung, und sie ist als solche benannt.
 */
function sz_versand_rueckstand(): ?int
{
    require_once __DIR__ . '/adminbackup_lib.php';
    /* ÜBERGANGENE ZIELE ZÄHLEN NICHT MIT (S10/AP4, E-S10-U-15).
     *
     * Sie werden nie beschickt, also steht ihr `letzter_erfolg` für immer auf
     * `null` — und die Zeile darunter machte daraus „keine Aussage" für die
     * GANZE Installation. Die Jobzeile stünde dann dauerhaft blau „in
     * Ordnung", obwohl Pakete liegenbleiben; oder, mit einem alten Erfolg,
     * dauerhaft orange, obwohl jedes erreichbare Ziel beliefert ist. Beides
     * ist eine Dauermeldung, die nichts mehr sagt.
     *
     * Sichtbar bleibt das Ziel an SEINER Zeile: rote Plakette, Vermerk aus
     * dem Lauf. Das ist der Ort, an dem etwas zu tun ist. */
    $ziele = array_values(array_filter(sz_alle(true),
        static fn(array $z): bool => sz_protokoll_erlaubt((string)$z['protokoll'])));
    if ($ziele === []) { return null; }
    $aeltester = null;
    foreach ($ziele as $z) {
        $e = $z['letzter_erfolg'] ?? null;
        if ($e === null) { return null; }        // nie gelaufen: keine Aussage
        if ($aeltester === null || $e < $aeltester) { $aeltester = $e; }
    }
    $wurzel = edbak_wurzel();
    if (!is_dir($wurzel)) { return 0; }
    $grenze = strtotime((string)$aeltester . ' UTC');
    $n = 0;
    foreach (scandir($wurzel) ?: [] as $k) {
        if (!edbak_kennung_gueltig($k) || !is_dir($wurzel . '/' . $k)) { continue; }
        foreach (scandir($wurzel . '/' . $k) ?: [] as $d) {
            if (!edbak_paketname_gueltig($d)) { continue; }
            if ((int)@filemtime($wurzel . '/' . $k . '/' . $d) > $grenze) { $n++; }
        }
    }
    /* Die Komplett-Backups zaehlen mit — sie gehen denselben Weg. */
    require_once __DIR__ . '/komplett_lib.php';
    foreach (komp_staende() as $st) {
        if ((int)@filemtime(komp_wurzel() . '/' . $st['datei']) > $grenze) { $n++; }
    }
    return $n;
}

/* ==========================================================================
 * AUFBEWAHRUNG AUF DEM ZIEL           P5a/AP10, E-P5a-03/-56, Backlog Nr. 49
 * ==========================================================================
 *
 * WOGEGEN. Der Versand ERGAENZT nur; auf der Gegenstelle loescht diese
 * Anwendung nie. Das ist Absicht und keine Luecke — der Zweck eines
 * auswaertigen Ziels ist, den Ausfall dieses Servers zu ueberleben, SAMT
 * eines Fehlers, der HIER zu viel loescht. Ein Versand, der drueben
 * aufraeumt, traegt genau diesen Fehler mit hinueber.
 *
 * Bei zwei Sicherungen je Konto und Monat laeuft ein Ziel trotzdem ueber
 * kurz oder lang voll, und niemand merkt es hier. Deshalb zwei Stufen:
 *
 *   ANZEIGE ist die Grundlage. Sie loescht nichts. Die Zielseite zeigt je
 *   Ziel, was dort liegt — Anzahl, Groesse, aeltester und juengster Stand,
 *   und wie viel davon NICHT von dieser Installation ist.
 *
 *   LOESCHREGEL ist die Option. Je Ziel, ausdruecklich einzuschalten, nie
 *   Vorgabe, und mit DREI SICHERUNGEN:
 *
 *     1. HERKUNFT. Geloescht wird nur, was (a) dem strengen Namensmuster
 *        einer Sicherung entspricht UND (b) in `sicherungsziel_dateien` als
 *        von DIESER Installation dorthin geschickt verzeichnet ist. Eine
 *        fremde Datei besteht schon die erste Probe nicht.
 *     2. MENGE. Nie unter N je Konto beziehungsweise M Komplett-Staende.
 *        Gezaehlt werden dabei nur die EIGENEN Dateien — fremde sind nicht
 *        unsere, sie zu zaehlen hiesse, sich an ihnen gutzuschreiben.
 *     3. LAUF. Nie in einem Lauf, dessen eigener Versand fehlgeschlagen ist.
 *        Wer nicht sicher weiss, dass der neue Stand drueben angekommen ist,
 *        raeumt den alten nicht weg.
 *
 * WARUM DAS PROTOKOLL EINE TABELLE IST UND NICHT `app_state` (E-P5a-56):
 * siehe die Migration `2026_09_16_sicherungsziel_aufbewahrung`. Kurz:
 * `app_state.v` ist `VARCHAR(190)`, und die Frage lautet „hat DIESE
 * Installation die Datei X auf Ziel Y geschickt?" — eine Zeile je Datei und
 * Ziel.
 */

/** Wie viele Tage die Loeschliste zurueckblickt (Statusseite, Sicherheit). */
const SZ_PROTOKOLL_TAGE = 30;

/** Ab wann ein Ziel als „waechst, ohne dass etwas entfernt wurde" gilt. */
const SZ_WACHSTUM_TAGE = 30;

/** Steht die Tabelle schon? (Migration noch nicht gelaufen: alles still.) */
function sz_dateien_tabelle_da(): bool
{
    require_once __DIR__ . '/db.php';
    try {
        $q = db()->query("SELECT COUNT(*) FROM information_schema.tables
                           WHERE table_schema = DATABASE()
                             AND table_name = 'sicherungsziel_dateien'");
        return (int)$q->fetchColumn() > 0;
    } catch (Throwable $e) { return false; }
}

/**
 * Einen gelungenen Versand verzeichnen.
 *
 * `ON DUPLICATE KEY`: Dieselbe Datei kann ein zweites Mal hinuebergehen —
 * etwa, wenn die erste Uebertragung abgebrochen ist und die Groesse drueben
 * nicht stimmte. Dann gilt der zweite Zeitpunkt, und `geloescht_am` faellt
 * zurueck auf `NULL`: Die Datei liegt wieder dort.
 *
 * SIE WIRFT NIE. Ein Versand, der geglueckt ist, darf nicht daran scheitern,
 * dass die Buchfuehrung darueber klemmt — dann steht die Datei drueben und
 * die Anwendung meldete einen Fehler. Was klemmt, steht im Fehlerprotokoll.
 */
function sz_versand_vermerken(int $zielId, string $ordner, string $datei, int $bytes): void
{
    require_once __DIR__ . '/db.php';
    try {
        db()->prepare('INSERT INTO sicherungsziel_dateien
                         (ziel_id, ordner, datei, bytes, gesendet_am)
                       VALUES (?, ?, ?, ?, UTC_TIMESTAMP())
                       ON DUPLICATE KEY UPDATE
                         bytes = VALUES(bytes), gesendet_am = VALUES(gesendet_am),
                         geloescht_am = NULL, grund = NULL')
            ->execute([$zielId, $ordner, $datei, max(0, $bytes)]);
    } catch (Throwable $e) {
        error_log('Sicherungsziel: Versandvermerk fuer ' . $ordner . '/' . $datei
                . ' misslang: ' . $e->getMessage());
    }
}

/**
 * Was diese Installation auf dieses Ziel geschickt hat und dort noch liegt.
 *
 * @return array<string,bool> Schluessel „ordner/datei"
 */
function sz_geschickt(int $zielId): array
{
    require_once __DIR__ . '/db.php';
    try {
        $st = db()->prepare('SELECT ordner, datei FROM sicherungsziel_dateien
                              WHERE ziel_id = ? AND geloescht_am IS NULL');
        $st->execute([$zielId]);
        $raus = [];
        foreach ($st as $z) { $raus[$z['ordner'] . '/' . $z['datei']] = true; }
        return $raus;
    } catch (Throwable $e) { return []; }
}

/**
 * Was diese Installation auf diesem Ziel SELBST entfernt hat (P5a/AP10,
 * E-P5a-57).
 *
 * WOGEGEN — und das ist beim Bauen der Probe herausgekommen, nicht beim
 * Nachdenken: Ohne diese Liste raeumt die Regel im zweiten Lauf drei alte
 * Sicherungen weg, der DRITTE Lauf schickt dieselben drei wieder hinueber
 * (sie liegen hier ja noch), und der vierte raeumt sie erneut weg. Ein
 * Kreislauf, der bei jedem Job Bandbreite kostet, das Protokoll vollschreibt
 * und nie zur Ruhe kommt — und zwar still, denn beide Seiten tun genau das,
 * wofuer sie gebaut sind.
 *
 * Gemessen: dritter Lauf **3 geloescht** statt 0 (Versandprobe Teil 12).
 *
 * DIE ENTSCHEIDUNG: Was die Regel dort entfernt hat, geht nicht wieder
 * hinueber. Der Preis, benannt — wer die Zahl spaeter ANHEBT, bekommt die
 * alten Staende nicht zurueck; sie sind dort weg und bleiben es. Das ist die
 * richtige Richtung: Der umgekehrte Preis waere ein Versand, der jede Nacht
 * dieselben Dateien hin- und herschiebt.
 *
 * @return array<string,bool> Schluessel „ordner/datei"
 */
function sz_dort_entfernt(int $zielId): array
{
    require_once __DIR__ . '/db.php';
    try {
        $st = db()->prepare('SELECT ordner, datei FROM sicherungsziel_dateien
                              WHERE ziel_id = ? AND geloescht_am IS NOT NULL');
        $st->execute([$zielId]);
        $raus = [];
        foreach ($st as $z) { $raus[$z['ordner'] . '/' . $z['datei']] = true; }
        return $raus;
    } catch (Throwable $e) { return []; }
}

/** Eine Loeschung verzeichnen. Wirft nie (siehe `sz_versand_vermerken()`). */
function sz_loeschung_vermerken(int $zielId, string $ordner, string $datei,
                                string $grund): void
{
    require_once __DIR__ . '/db.php';
    try {
        db()->prepare('UPDATE sicherungsziel_dateien
                          SET geloescht_am = UTC_TIMESTAMP(), grund = ?
                        WHERE ziel_id = ? AND ordner = ? AND datei = ?')
            ->execute([mb_substr($grund, 0, 190), $zielId, $ordner, $datei]);
    } catch (Throwable $e) {
        error_log('Sicherungsziel: Loeschvermerk fuer ' . $ordner . '/' . $datei
                . ' misslang: ' . $e->getMessage());
    }
}

/**
 * Der Zeitpunkt aus dem Namen einer Sicherung — beide Muster in einer Hand.
 *
 * Kontopakete heissen `<ISO>_<8 hex>.json|.zip`, Komplett-Staende
 * `<ISO>_<8 hex>.edk`. Beide beginnen mit demselben Zeitstempel, und genau
 * deshalb sortiert der NAME schon zeitlich — die Funktion gibt es trotzdem,
 * weil „sortiert zufaellig richtig" eine Falle ist, sobald jemand das Muster
 * aendert.
 */
function sz_zeit_aus_dateiname(string $name): ?string
{
    if (!preg_match('/^(\d{4}-\d{2}-\d{2})T(\d{2})-(\d{2})-(\d{2})Z_/', $name, $t)) {
        return null;
    }
    return $t[1] . ' ' . $t[2] . ':' . $t[3] . ':' . $t[4];
}

/** Gehoert dieser Dateiname zu einer Sicherung dieser Anwendung? */
function sz_ist_sicherungsname(string $ordner, string $name): bool
{
    require_once __DIR__ . '/komplett_lib.php';
    require_once __DIR__ . '/adminbackup_lib.php';
    return $ordner === KOMP_ORDNER
        ? komp_name_gueltig($name)
        : edbak_paketname_gueltig($name);
}

/**
 * Was auf einem Ziel liegt — gelesen mit `liste()`, ueber alle Ordner.
 *
 * SIE BAUT DIE VERBINDUNG NICHT SELBST AUF. Der Aufrufer uebergibt einen
 * verbundenen Weg; so laesst sich dieselbe Auskunft im Versandlauf (wo die
 * Verbindung ohnehin steht) und auf Knopfdruck holen, ohne zwei Fassungen.
 *
 * **Sie laeuft NICHT bei jedem Seitenaufruf.** Drei FTP-Verbindungen je
 * Aufruf der Zielseite waeren eine Seite, die zehn Sekunden laedt — dieselbe
 * Ueberlegung wie bei `sz_versand_rueckstand()`.
 *
 * @return array{ordner:int,dateien:int,bytes:int,fremd:int,fremd_bytes:int,
 *                aeltester:?string,juengster:?string}
 */
function sz_bestand(Zielweg $weg, int $zielId): array
{
    $raus = ['ordner' => 0, 'dateien' => 0, 'bytes' => 0,
             'fremd' => 0, 'fremd_bytes' => 0,
             'aeltester' => null, 'juengster' => null];
    $wurzel = $weg->liste('');
    /* `liste()` gibt Dateiname => Bytes. Unterordner erscheinen je nach
     * Adapter gar nicht oder mit Groesse 0; deshalb wird NICHT aus der
     * Wurzelliste geraten, welche Ordner es gibt, sondern gefragt, was diese
     * Installation angelegt haben KANN: die Kontokennungen und `komplett`.
     * Ein Ordner, den es drueben nicht gibt, liefert eine leere Liste. */
    require_once __DIR__ . '/adminbackup_lib.php';
    require_once __DIR__ . '/komplett_lib.php';
    $ordner = [KOMP_ORDNER];
    $wurzelPfad = edbak_wurzel();
    if (is_dir($wurzelPfad)) {
        foreach (scandir($wurzelPfad) ?: [] as $n) {
            if ($n === '.' || $n === '..' || !edbak_kennung_gueltig($n)) { continue; }
            if (is_dir($wurzelPfad . '/' . $n)) { $ordner[] = $n; }
        }
    }
    /* Kennungen, die es HIER nicht mehr gibt, drueben aber noch: Sie stehen
     * im Versandprotokoll. Ohne sie zaehlte die Anzeige ein geloeschtes Konto
     * als „nichts dort" — und genau dessen Sicherungen sind die, auf die es
     * ankommt. */
    require_once __DIR__ . '/db.php';
    try {
        $st = db()->prepare('SELECT DISTINCT ordner FROM sicherungsziel_dateien
                              WHERE ziel_id = ?');
        $st->execute([$zielId]);
        foreach ($st as $z) { $ordner[] = (string)$z['ordner']; }
    } catch (Throwable $e) { /* Tabelle fehlt — dann eben ohne */ }
    $ordner = array_values(array_unique($ordner));

    foreach ($ordner as $o) {
        $dort = $weg->liste($o);
        if ($dort === []) { continue; }
        $raus['ordner']++;
        foreach ($dort as $name => $bytes) {
            if (sz_ist_sicherungsname($o, (string)$name)) {
                $raus['dateien']++;
                $raus['bytes'] += (int)$bytes;
                $zeit = sz_zeit_aus_dateiname((string)$name);
                if ($zeit !== null) {
                    if ($raus['aeltester'] === null || $zeit < $raus['aeltester']) {
                        $raus['aeltester'] = $zeit;
                    }
                    if ($raus['juengster'] === null || $zeit > $raus['juengster']) {
                        $raus['juengster'] = $zeit;
                    }
                }
            } else {
                $raus['fremd']++;
                $raus['fremd_bytes'] += (int)$bytes;
            }
        }
    }
    /* WAS IN DER WURZEL LIEGT, ist nie eine Sicherung dieser Anwendung — sie
     * legt alles in Ordner. Es zaehlt deshalb als fremd und wird nie
     * angefasst.
     *
     * AUSSER: die Ordner selbst. Manche Adapter fuehren Unterordner in der
     * Liste mit (Groesse 0), andere nicht. Wuerden sie als fremde Dateien
     * gezaehlt, meldete dieselbe Gegenstelle ueber FTPS und ueber SFTP
     * verschiedene Zahlen — und die Anzeige sagte „drei fremde Dateien", wo
     * drei eigene Ordner stehen. */
    $eigen = array_flip($ordner);
    foreach ($wurzel as $name => $bytes) {
        if (isset($eigen[(string)$name])) { continue; }
        $raus['fremd']++;
        $raus['fremd_bytes'] += (int)$bytes;
    }
    return $raus;
}

/**
 * Aufraeumen auf einem Ziel — die Loeschregel, mit ihren drei Sicherungen.
 *
 * SIE BEKOMMT EINEN VERBUNDENEN WEG und baut keine zweite Verbindung auf:
 * Sie laeuft am Ende desselben Schubes, in dem gesendet wurde. Das ist nicht
 * nur billiger, es ist die dritte Sicherung — wer hier ankommt, hat gerade
 * erfolgreich gesendet.
 *
 * @param callable():float $zeitLinks  wie viel Zeit der Schub noch hat
 * @return array{geloescht:int,bytes:int,fertig:bool,fehler:list<string>}
 */
function sz_aufraeumen(array $ziel, Zielweg $weg, callable $zeitLinks,
                       float $reserve = SZ_VERSAND_RESERVE_S): array
{
    require_once __DIR__ . '/adminbackup_lib.php';
    require_once __DIR__ . '/komplett_lib.php';

    $raus = ['geloescht' => 0, 'bytes' => 0, 'fertig' => true, 'fehler' => []];
    $bk = $ziel['behalten_konto'] === null ? null : (int)$ziel['behalten_konto'];
    $bm = $ziel['behalten_komplett'] === null ? null : (int)$ziel['behalten_komplett'];
    /* OPTION AUS HEISST: gar nichts tun — auch nicht nachsehen. Ein Ziel ohne
     * Regel soll keine einzige zusaetzliche Anfrage kosten. */
    if ($bk === null && $bm === null) { return $raus; }
    if (!sz_dateien_tabelle_da()) {
        /* Ohne Versandprotokoll fehlt die zweite Sicherung. Dann wird NICHT
         * geloescht — und es wird gesagt, warum. Der Fall tritt zwischen
         * Deploy und Migration auf. */
        $raus['fehler'][] = 'Die Aufbewahrungsregel ist eingeschaltet, aber das '
                          . 'Versandprotokoll fehlt (Migration steht aus). Es '
                          . 'wurde nichts gelöscht.';
        return $raus;
    }

    $zielId    = (int)$ziel['id'];
    $geschickt = sz_geschickt($zielId);

    /* Welche Ordner infrage kommen: was HIER liegt plus, was das Protokoll
     * fuer dieses Ziel kennt. Der zweite Teil ist der wichtige — ein geloeschtes
     * Konto hat hier keinen Ordner mehr, drueben aber noch Sicherungen, und
     * genau die wachsen sonst ewig weiter. */
    $ordner = [KOMP_ORDNER];
    $wurzelPfad = edbak_wurzel();
    if (is_dir($wurzelPfad)) {
        foreach (scandir($wurzelPfad) ?: [] as $n) {
            if ($n === '.' || $n === '..' || !edbak_kennung_gueltig($n)) { continue; }
            if (is_dir($wurzelPfad . '/' . $n)) { $ordner[] = $n; }
        }
    }
    foreach (array_keys($geschickt) as $schluessel) {
        $ordner[] = (string)substr($schluessel, 0, (int)strrpos($schluessel, '/'));
    }
    $ordner = array_values(array_unique(array_filter($ordner, static fn($o) => $o !== '')));
    sort($ordner);

    foreach ($ordner as $o) {
        if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break; }
        $behalten = $o === KOMP_ORDNER ? $bm : $bk;
        if ($behalten === null) { continue; }   // nur eine der beiden Zahlen gesetzt

        try {
            $dort = $weg->liste($o);
        } catch (ZielFehler $e) {
            $raus['fehler'][] = $o . ': ' . $e->getMessage();
            continue;
        }
        if ($dort === []) { continue; }

        /* ---- Sicherung 1: HERKUNFT ---------------------------------------
         * Zwei Proben, und beide muessen bestehen. Das Namensmuster allein
         * genuegte nicht: Eine zweite Installation, die dasselbe Ziel
         * beschickt, schriebe Dateien mit demselben Muster — und die sind
         * genauso fremd wie ein Urlaubsfoto. */
        $eigene = [];
        foreach ($dort as $name => $bytes) {
            $name = (string)$name;
            if (!sz_ist_sicherungsname($o, $name)) { continue; }
            if (!isset($geschickt[$o . '/' . $name])) { continue; }
            $eigene[$name] = (int)$bytes;
        }
        if ($eigene === []) { continue; }

        /* Neueste zuerst. Der Name beginnt mit dem Zeitstempel, sortiert also
         * zeitlich — `sz_zeit_aus_dateiname()` steht daneben und wuerde es
         * merken, wenn das einmal nicht mehr stimmt. */
        krsort($eigene, SORT_STRING);

        /* ---- Sicherung 2: MENGE ----------------------------------------- */
        $ueber = array_slice($eigene, $behalten, null, true);
        if ($ueber === []) { continue; }

        foreach ($ueber as $name => $bytes) {
            if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break 2; }
            try {
                $weg->loeschen($o . '/' . $name);
            } catch (ZielFehler $e) {
                $raus['fehler'][] = $o . '/' . $name . ': ' . $e->getMessage();
                continue;
            }
            sz_loeschung_vermerken($zielId, $o, $name,
                'Aufbewahrung dieses Ziels: höchstens ' . $behalten
                . ($o === KOMP_ORDNER ? ' Komplett-Stände' : ' je Konto'));
            $raus['geloescht']++;
            $raus['bytes'] += $bytes;
        }
    }
    return $raus;
}

/**
 * Die Loeschungen der letzten Tage — fuer die Sicherheitsseite (E-P5a-08).
 *
 * @return array{zeilen:list<array>,gesamt:int}
 */
function sz_loeschungen(int $tage = SZ_PROTOKOLL_TAGE, int $hoechstens = 50): array
{
    require_once __DIR__ . '/db.php';
    $leer = ['zeilen' => [], 'gesamt' => 0];
    if (!sz_dateien_tabelle_da()) { return $leer; }
    try {
        $st = db()->prepare('SELECT COUNT(*) FROM sicherungsziel_dateien
                              WHERE geloescht_am IS NOT NULL
                                AND geloescht_am > DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)');
        $st->execute([$tage]);
        $gesamt = (int)$st->fetchColumn();

        $st = db()->prepare('SELECT d.ordner, d.datei, d.bytes, d.geloescht_am,
                                    d.grund, t.name AS ziel
                               FROM sicherungsziel_dateien d
                               LEFT JOIN backup_targets t ON t.id = d.ziel_id
                              WHERE d.geloescht_am IS NOT NULL
                                AND d.geloescht_am > DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)
                              ORDER BY d.geloescht_am DESC
                              LIMIT ' . max(1, $hoechstens));
        $st->execute([$tage]);
        return ['zeilen' => $st->fetchAll(), 'gesamt' => $gesamt];
    } catch (Throwable $e) { return $leer; }
}

/**
 * Ziele, die seit ueber einem Monat wachsen, ohne dass dort etwas entfernt
 * wurde (E-P5a-03, Statusseite).
 *
 * SIE FRAGT DIE ZIELE NICHT — sie liest das Protokoll. Eine Statusseite, die
 * drei FTP-Verbindungen aufbaut, laedt zehn Sekunden; dieselbe Ueberlegung
 * wie bei `sz_versand_rueckstand()`. Was sie damit NICHT sieht: eine
 * Betreiberin, die von Hand aufgeraeumt hat. Der Hinweis sagt deshalb
 * „es ist nie etwas entfernt worden" und nicht „dort liegt zu viel".
 *
 * @return list<array{name:string,seit:string,dateien:int,bytes:int}>
 */
function sz_waechst(): array
{
    require_once __DIR__ . '/db.php';
    if (!sz_dateien_tabelle_da()) { return []; }
    try {
        $st = db()->prepare(
            'SELECT t.name,
                    MIN(d.gesendet_am)                        AS seit,
                    SUM(d.geloescht_am IS NULL)               AS dateien,
                    SUM(CASE WHEN d.geloescht_am IS NULL THEN d.bytes ELSE 0 END) AS bytes,
                    SUM(d.geloescht_am IS NOT NULL)           AS entfernt
               FROM sicherungsziel_dateien d
               JOIN backup_targets t ON t.id = d.ziel_id
              WHERE t.behalten_konto IS NULL AND t.behalten_komplett IS NULL
              GROUP BY t.id, t.name
             HAVING entfernt = 0
                AND seit < DATE_SUB(UTC_TIMESTAMP(), INTERVAL ? DAY)
                AND dateien > 0
              ORDER BY bytes DESC');
        $st->execute([SZ_WACHSTUM_TAGE]);
        $raus = [];
        foreach ($st as $z) {
            $raus[] = ['name' => (string)$z['name'], 'seit' => (string)$z['seit'],
                       'dateien' => (int)$z['dateien'], 'bytes' => (int)$z['bytes']];
        }
        return $raus;
    } catch (Throwable $e) { return []; }
}
