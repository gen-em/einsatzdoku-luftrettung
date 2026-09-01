<?php
declare(strict_types=1);
require_once __DIR__ . '/serverkrypto_lib.php';

/**
 * SICHERUNGSZIELE — wohin die Sicherungen gehen (E-S2-22, S2/AP7).
 *
 * WARUM „SICHERUNGSZIEL" UND NICHT „TRANSPORTZIEL". Das Konzept nennt es
 * Transportziel. Diesen Namen trägt in dieser Anwendung seit Web 4 aber schon
 * etwas anderes: `transport_dests`, die Zielklinik einer Patientin, gepflegt
 * unter Stammdaten. Zwei Dinge, zwei Klicks voneinander entfernt, unter einem
 * Wort — das wäre kein Detail, sondern die Art Verwechslung, die man in einer
 * Fehlermeldung nicht mehr auflösen kann. Also: Sicherungsziel. Festgehalten
 * in `docs/Konzept-S2-Mengen-Spuren-Sicherung.md` unter F-S2-G.
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

/** Vorgabeports je Protokoll. */
const SZ_PORTS = ['ftp' => 21, 'ftps' => 21, 'sftp' => 22];

/** Anzeigenamen der Protokolle, in der Reihenfolge der Empfehlung. */
const SZ_PROTOKOLLE = [
    'sftp' => 'SFTP (SSH) — empfohlen',
    'ftps' => 'FTPS (FTP über TLS)',
    'ftp'  => 'FTP (unverschlüsselt)',
];

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
 * Was ein Sicherungsziel können muss.
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
        '/Permission denied|550|553|Access is denied/i'
            => 'Das Ziel verweigert den Zugriff — Pfad und Rechte prüfen.',
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
                . 'FTPS geht damit nicht. FTP oder SFTP verwenden.');
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
 * sie bleibt unangetastet. Ein Sicherungsziel ist keine Einsatzdatei, kommt
 * nicht von der Uhr, nicht aus einer Sicherung und nicht über die API,
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
    if (!isset(SZ_PORTS[$prot])) { $f[] = 'Unbekanntes Protokoll.'; }
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
    return [[
        'name'      => $name,
        'protokoll' => $prot,
        'host'      => $host,
        'port'      => $port,
        'nutzer'    => $nutzer,
        'pfad'      => $pfad,
        'passiv'    => !empty($e['passiv']) ? 1 : 0,
        'aktiv'     => !empty($e['aktiv']) ? 1 : 0,
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
              (name, protokoll, host, port, nutzer, pfad, passiv, aktiv, erstellt_am)
              VALUES (?, ?, ?, ?, ?, ?, ?, ?, UTC_TIMESTAMP())');
        $st->execute([$s['name'], $s['protokoll'], $s['host'], $s['port'],
                      $s['nutzer'], $s['pfad'], $s['passiv'], $s['aktiv']]);
        $id = (int)db()->lastInsertId();
    } else {
        $st = db()->prepare('UPDATE backup_targets SET name = ?, protokoll = ?,
              host = ?, port = ?, nutzer = ?, pfad = ?, passiv = ?, aktiv = ?
              WHERE id = ?');
        $st->execute([$s['name'], $s['protokoll'], $s['host'], $s['port'],
                      $s['nutzer'], $s['pfad'], $s['passiv'], $s['aktiv'], $id]);
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
 * Art Lüge fällt erst auf, wenn man die Sicherung braucht.
 *
 * ES WIRD NUR HINZUGEFÜGT, NIE GELÖSCHT. Die Aufbewahrung („zwei je Konto")
 * gilt für die Ablage auf diesem Server; auf dem Ziel räumt niemand auf. Das
 * ist bewusst so: Der Zweck eines auswärtigen Ziels ist, den Ausfall DIESES
 * Servers zu überleben — samt eines Fehlers, der hier zu viel löscht. Wer dort
 * aufräumen will, tut es dort. (Backlog Nr. 49.)
 *
 * @param callable $zeitLinks gibt die verbleibenden Sekunden
 * @return array ['gesendet' => int, 'bytes' => int, 'ziele' => int,
 *                'fehler' => [text, ...], 'fertig' => bool]
 */
function sz_versand_schub(callable $zeitLinks, float $reserve = SZ_VERSAND_RESERVE_S): array
{
    require_once __DIR__ . '/adminbackup_lib.php';
    $ziele = sz_alle(true);
    $raus = ['gesendet' => 0, 'bytes' => 0, 'ziele' => count($ziele),
             'fehler' => [], 'fertig' => true];
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

    foreach ($ziele as $z) {
        if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break; }
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
        try {
            foreach ($ordner as $kennung) {
                /* Die Zeit wird JE KONTO geprüft, nicht je Ziel: Ein Schub,
                 * der mitten in einer Übertragung von der Zeit eingeholt
                 * wird, hinterlässt am Ziel eine halbe Datei. */
                if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break; }
                $pakete = edbak_pakete($kennung);
                if ($pakete === []) { continue; }
                $weg->ordner($kennung);
                $dort = $weg->liste($kennung);
                foreach ($pakete as $p) {
                    if ($zeitLinks() < $reserve) { $raus['fertig'] = false; break 2; }
                    $name = (string)$p['datei'];
                    if (!sz_name_gueltig($name)) { continue; }
                    /* SCHON DA HEISST: gleicher Name UND gleiche Grösse. Nur
                     * der Name wäre zu wenig — eine abgebrochene Übertragung
                     * hinterlässt eine Datei mit dem richtigen Namen und der
                     * falschen Länge, und die gälte für immer als erledigt. */
                    $bytes = (int)$p['groesse'];
                    if (isset($dort[$name]) && $dort[$name] === $bytes) { continue; }
                    $weg->senden(edbak_ordner($kennung) . '/' . $name, $kennung . '/' . $name);
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
    $ziele = sz_alle(true);
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
    return $n;
}
