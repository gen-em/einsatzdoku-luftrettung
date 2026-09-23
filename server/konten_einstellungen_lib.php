<?php
declare(strict_types=1);
/**
 * DIE EINSTELLUNGEN RUND UM KONTEN (P5b/AP1, E-P5b-14).
 *
 * ---------------------------------------------------------------------------
 * WARUM EINE EIGENE DATEI UND NICHT DIE SEITE
 * ---------------------------------------------------------------------------
 *
 * R83: Zentralisiert wird beim zweiten echten Verbraucher — aber der ORT
 * steht vorher fest, „Bibliotheksdatei statt Seite, damit der zweite
 * Verbraucher ohne Umbau zugreifen kann". Hier sind die zweiten Verbraucher
 * schon namentlich bekannt und stehen im Konzept: `registrieren.php` (AP3)
 * liest die Betriebsart, `login.php` (AP7) die Demo-Anmeldung, `ingest.php`
 * (AP6) die Mengengrenzen, der Job `konto_verfall` (AP3) die Freischaltfrist.
 * Sie in `betrieb_server.php` zu schreiben und spaeter herauszuziehen waere
 * derselbe Umbau, nur spaeter und mit vier Aufrufern mehr.
 *
 * NICHT hier: die Protokollfrist. Sie steht in `protokoll_lib.php`, wo auch
 * die Bereinigung sie liest — eine Frist an zwei Stellen laeuft auseinander,
 * sobald jemand nur eine aendert. Die Karte in den Servereinstellungen
 * schreibt beide Gruppen in einem Formular; das ist die Oberflaeche und nicht
 * die Ablage.
 *
 * ---------------------------------------------------------------------------
 * DIE VORGABEN SIND DIE VORSICHTIGEN, NICHT DIE, DIE NADOKU FAEHRT
 * ---------------------------------------------------------------------------
 *
 * Betriebsart `einladung` (E-P5b-01): das heutige Verhalten. Eine
 * Selbsthosterin, die diese Seite nie aufschlaegt, bekommt keine offene
 * Registrierung durch Untaetigkeit. nadoku selbst schaltet zum Betriebsstart
 * auf `freischaltung` — von Hand, sichtbar, mit einem Protokolleintrag.
 *
 * Wegwerfadressen abweisen: an. Demo-Anmeldung: an (R25 — das Demo-Konto ist
 * Bestandteil, und es abzuschalten ist die Ausnahme).
 */

require_once __DIR__ . '/db.php';

/* ---- Die drei Betriebsarten (E-P5b-01) ----------------------------------- */

/**
 * Wert in `app_state` => sichtbarer Text.
 *
 * Die Reihenfolge ist die der Auswahl: von offen nach geschlossen. Die
 * Vorgabe steht damit unten, und das ist Absicht — wer die Liste von oben
 * liest, sieht zuerst, was die Seite kann, und nicht, was sie tut.
 */
const KONTEN_REG_ARTEN = [
    'offen'         => 'offen — wer die Seite findet, kann sich registrieren',
    'freischaltung' => 'offen mit Freischaltung — Registrierung wartet auf die Verwaltung',
    'einladung'     => 'nur auf Einladung — die Verwaltung legt Konten an',
];

const KONTEN_REG_ART_VORGABE = 'einladung';

/* ---- Schluessel in `app_state` ------------------------------------------- */

const KONTEN_K_REG_ART         = 'konten_reg_art';
const KONTEN_K_REG_FRIST       = 'konten_reg_frist';
const KONTEN_K_WEGWERF         = 'konten_wegwerf';
const KONTEN_K_WEGWERF_EIGENE  = 'konten_wegwerf_eigene';
const KONTEN_K_GRENZE_EINSAETZE = 'konten_grenze_einsaetze';
const KONTEN_K_GRENZE_MB       = 'konten_grenze_mb';
/* Backlog Nr. 48 wird NICHT als eigene Installationsvorgabe gefuehrt: Die
 * gibt es schon — `app_state.adminbackup_aufbewahrung`, gepflegt unter
 * Verwaltung -> Konto-Backups. Eine zweite Zahl daneben waere genau die Art
 * Doppelung, die R83 verhindern soll. Was P5b/AP6 ergaenzt, ist die
 * UEBERSCHREIBUNG je Konto (`users.backup_pakete`). */
const KONTEN_K_DEMO_ANMELDUNG  = 'konten_demo_anmeldung';

/* ---- Vorgaben und Grenzen ------------------------------------------------ */

/** Verfall wartender Registrierungen, in Tagen (E-P5b-02). */
const KONTEN_REG_FRIST_VORGABE = 30;
const KONTEN_REG_FRIST_MIN     = 1;
const KONTEN_REG_FRIST_MAX     = 365;

/**
 * Verfall UNBESTAETIGTER Registrierungen, in Stunden. FEST, keine Einstellung
 * (R37 (3), E-P5b-02).
 *
 * Zwei Fristen, zwei Zustaende, ein Job. Diese hier ist fest, weil sie eine
 * Sicherheitsfrist ist und keine Betriebsfrist: Ein unbestaetigtes Konto ist
 * eine Adresse, die jemand eingetippt hat, moeglicherweise nicht die eigene.
 * Sie laenger liegen zu lassen hat keinen Nutzen und einen Preis.
 */
const KONTEN_UNBESTAETIGT_H = 48;

/** Mengengrenze je Konto (E-P5b-04). */
const KONTEN_GRENZE_EINSAETZE_VORGABE = 5000;
const KONTEN_GRENZE_MB_VORGABE        = 250;

/** Ab diesem Anteil wird gewarnt — einmal per Mail, dauerhaft auf der Seite. */
const KONTEN_WARNSCHWELLE = 0.8;

/* ---- Lesen --------------------------------------------------------------- */

/**
 * Alle Konten-Einstellungen auf einmal.
 *
 * EIN AUFRUF UND NICHT ACHT: `app_state_lesen()` hat keinen Zwischenspeicher
 * je Anfrage und fragt bei jedem Aufruf neu (db.php, dort ausdruecklich
 * vermerkt). Acht Schluessel waeren acht Abfragen je Seitenaufbau. Diese
 * Funktion holt sie in einer.
 *
 * @return array<string,string> Schluessel => Wert, Vorgaben eingesetzt.
 */
function konten_einstellungen(bool $neuLesen = false): array
{
    static $speicher = null;
    if ($neuLesen) { $speicher = null; }
    if ($speicher !== null) { return $speicher; }

    $vorgaben = [
        KONTEN_K_REG_ART          => KONTEN_REG_ART_VORGABE,
        KONTEN_K_REG_FRIST        => (string)KONTEN_REG_FRIST_VORGABE,
        KONTEN_K_WEGWERF          => '1',
        KONTEN_K_WEGWERF_EIGENE   => '',
        KONTEN_K_GRENZE_EINSAETZE => (string)KONTEN_GRENZE_EINSAETZE_VORGABE,
        KONTEN_K_GRENZE_MB        => (string)KONTEN_GRENZE_MB_VORGABE,
        KONTEN_K_DEMO_ANMELDUNG   => '1',
    ];

    /* EIN LEERER WERT IST EIN WERT, ein fehlender nicht. Die Aufbewahrung
     * „unbegrenzt" ist der leere String — sie darf nicht auf die Vorgabe
     * zurueckfallen, sonst liesse sie sich nie einschalten. `app_state_mehrere()`
     * liefert nur die GEFUNDENEN Zeilen zurueck; genau das ist der Unterschied.
     * Fehlt die Tabelle (Migration noch nicht gelaufen), kommt ein leeres Feld
     * und die Vorgaben gelten — die richtige Antwort. */
    foreach (app_state_mehrere(array_keys($vorgaben)) as $k => $v) {
        $vorgaben[$k] = $v;
    }

    /* Eine Betriebsart, die es nicht gibt, faellt auf die Vorgabe zurueck
     * statt zu gelten: `offen` durch einen Tippfehler waere die teuerste
     * aller stillen Aenderungen. */
    if (!isset(KONTEN_REG_ARTEN[$vorgaben[KONTEN_K_REG_ART]])) {
        $vorgaben[KONTEN_K_REG_ART] = KONTEN_REG_ART_VORGABE;
    }

    return $speicher = $vorgaben;
}

/** Die Betriebsart der Registrierung: `offen` · `freischaltung` · `einladung`. */
function konten_reg_art(): string
{
    return konten_einstellungen()[KONTEN_K_REG_ART];
}

/** Darf sich überhaupt jemand selbst registrieren? */
function konten_reg_offen(): bool
{
    return konten_reg_art() !== 'einladung';
}

/** Wartet eine neue Registrierung auf die Verwaltung? */
function konten_reg_freischaltung(): bool
{
    return konten_reg_art() === 'freischaltung';
}

/** Verfall wartender Registrierungen in Tagen. */
function konten_reg_frist_tage(): int
{
    $n = (int)konten_einstellungen()[KONTEN_K_REG_FRIST];
    return ($n >= KONTEN_REG_FRIST_MIN && $n <= KONTEN_REG_FRIST_MAX)
        ? $n : KONTEN_REG_FRIST_VORGABE;
}

/** Werden Wegwerfadressen abgewiesen? */
function konten_wegwerf_an(): bool
{
    return konten_einstellungen()[KONTEN_K_WEGWERF] === '1';
}

/** Die zusätzlich eingetragenen Domains, klein geschrieben. */
function konten_wegwerf_eigene(): array
{
    $roh = trim(konten_einstellungen()[KONTEN_K_WEGWERF_EIGENE]);
    if ($roh === '') { return []; }
    return array_values(array_filter(array_map('trim', explode(',', $roh))));
}

/* ---- Wegwerfadressen (E-P5b-03, E-P5b-23) -------------------------------- */

/** Die mitgelieferte Liste; sie wird NIE zur Laufzeit geholt (R36). */
const WEGWERF_DATEI = __DIR__ . '/wegwerfdomains.txt';

/**
 * Ist das eine Wegwerfadresse?
 *
 * DIE DATEI WIRD JE AUFRUF EINMAL GELESEN und in einer `static` gehalten —
 * 126 KB und rund 8 900 Zeilen. Gelesen wird sie nur bei einer
 * Registrierung, also selten; ein Zwischenspeicher in `app_state` waere
 * teurer als die Datei.
 *
 * KEINE NORMALISIERUNG AUSSER KLEINSCHREIBUNG. Die Liste ist durchgehend
 * klein, ohne Kommentar-, Leer- und Doppelzeilen — `tools/erzeugen/wegwerfdomains.py`
 * prueft das bei jedem Nachziehen und schreibt nicht, wenn es nicht
 * stimmt. Was hier zusaetzlich abgefangen wuerde, verdeckte dort einen
 * Fehler.
 *
 * DIE SUBDOMAIN ZAEHLT MIT: `x.mailinator.com` trifft, wenn
 * `mailinator.com` auf der Liste steht. Wegwerfanbieter vergeben
 * Unterdomains freihaendig; eine Liste, die nur die genaue Domain traefe,
 * waere mit einem Punkt zu umgehen.
 *
 * FEHLT DIE DATEI, TRIFFT NICHTS. Eine Installation, die alle
 * Registrierungen abweist, WEIL eine Datei fehlt, waere das Gegenteil von
 * dem, was dieser Schalter soll — dieselbe Ueberlegung wie beim
 * Einwilligungstor ohne Tabelle.
 */
function wegwerf_trifft(string $email): bool
{
    if (!konten_wegwerf_an()) { return false; }

    $at = strrpos($email, '@');
    if ($at === false) { return false; }
    $domain = mb_strtolower(substr($email, $at + 1));
    if ($domain === '') { return false; }

    static $liste = null;
    if ($liste === null) {
        $liste = [];
        if (is_readable(WEGWERF_DATEI)) {
            foreach (file(WEGWERF_DATEI, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $z) {
                $liste[$z] = true;
            }
        } else {
            error_log('wegwerf_trifft: ' . WEGWERF_DATEI . ' fehlt oder ist nicht lesbar');
        }
    }

    /* Eigene Domains der Betreiberin kommen dazu — sie stehen in
     * `app_state` und nicht in der Datei, weil die Datei mit jeder
     * Auslieferung ersetzt wird. */
    $eigene = [];
    foreach (konten_wegwerf_eigene() as $d) { $eigene[mb_strtolower($d)] = true; }

    /* Von der vollen Domain nach oben: `a.b.example.com`, `b.example.com`,
     * `example.com`. Bei drei Punkten sind das vier Nachschlagevorgaenge in
     * einem Array — billiger als jede Schleife ueber die Liste. */
    $teil = $domain;
    while (true) {
        if (isset($liste[$teil]) || isset($eigene[$teil])) { return true; }
        $punkt = strpos($teil, '.');
        if ($punkt === false) { return false; }
        $teil = substr($teil, $punkt + 1);
        if ($teil === '' || strpos($teil, '.') === false) {
            /* Bei der letzten Stufe (`com`) wird nicht mehr nachgesehen: Eine
             * Liste, die eine ganze Endung sperrt, waere ein Fehler in der
             * Liste, und ihn hier wirksam werden zu lassen sperrte das halbe
             * Netz aus. */
            return false;
        }
    }
}

/** Mengengrenze je Konto: Einsätze. */
function konten_grenze_einsaetze(): int
{
    $n = (int)konten_einstellungen()[KONTEN_K_GRENZE_EINSAETZE];
    return $n > 0 ? $n : KONTEN_GRENZE_EINSAETZE_VORGABE;
}

/** Mengengrenze je Konto: Megabyte. */
function konten_grenze_mb(): int
{
    $n = (int)konten_einstellungen()[KONTEN_K_GRENZE_MB];
    return $n > 0 ? $n : KONTEN_GRENZE_MB_VORGABE;
}

/** Ist die Anmeldung am Demo-Konto zugelassen? (E-P5b-07) */
function konten_demo_anmeldung_an(): bool
{
    return konten_einstellungen()[KONTEN_K_DEMO_ANMELDUNG] === '1';
}

/* ---- Mengen je Konto (P5b/AP6, E-P5b-04, -18) ---------------------------- */

/**
 * Wie viel dieses Konto belegt — Einsaetze und Byte.
 *
 * ---------------------------------------------------------------------------
 * GECACHT, UND ZWAR AUS EINEM GEMESSENEN GRUND
 * ---------------------------------------------------------------------------
 *
 * Die Byte-Messung liest die Blob-Laengen aller GPS-Daten eines Kontos. Beim
 * Demo-Bestand (106 Einsaetze, 63 752 Punkte) sind das Millisekunden; bei der
 * Zielmenge aus E-S2-24 ist es das nicht mehr. Sie liefe sonst bei JEDEM
 * Upload, und `ingest.php` ist genau der Weg, der schnell sein muss.
 *
 * Deshalb: gecacht in `app_state` unter `mengen:<id>`, erneuert im Job
 * `aufraeumen` und nach jedem Upload-Schub FORTGESCHRIEBEN (geschaetzt, nicht
 * gemessen). Die Schaetzung darf danebenliegen — sie wird beim naechsten
 * Joblauf durch die echte Zahl ersetzt, und eine Grenze bei 250 MB nimmt
 * einen Schaetzfehler von ein paar Kilobyte nicht uebel.
 *
 * DIE 190-ZEICHEN-GRENZE VON `app_state` IST DER GRUND FUER DAS FORMAT:
 * `<einsaetze>|<bytes>|<zeitstempel>`, drei Zahlen mit Trennstrich. JSON
 * waere lesbarer und haette hier keinen Platz.
 *
 * @param bool $frisch `true` misst neu, statt den Cache zu nehmen
 * @return array{einsaetze:int, bytes:int, gemessen:?int}
 */
function konto_mengen(int $userId, bool $frisch = false): array
{
    $schluessel = 'mengen:' . $userId;

    if (!$frisch) {
        $roh = app_state_lesen($schluessel);
        if ($roh !== null && $roh !== '') {
            $t = explode('|', $roh);
            if (count($t) === 3) {
                return ['einsaetze' => (int)$t[0], 'bytes' => (int)$t[1],
                        'gemessen'  => (int)$t[2]];
            }
        }
    }

    $pdo = db();
    $einsaetze = 0; $bytes = 0;

    try {
        /* GEZAEHLT WIRD, WAS IM PAPIERKORB NICHT LIEGT. Ein geloeschter
         * Einsatz belegt zwar noch Platz (der Papierkorb raeumt nach 90
         * Tagen), aber er zaehlt nicht gegen die Grenze: Sonst koennte
         * jemand seine Grenze nicht durch Loeschen unterschreiten, und
         * genau das ist der Weg, den die Meldung bei 100 % vorschlaegt. */
        $st = $pdo->prepare('SELECT COUNT(*) FROM missions
                              WHERE user_id = ? AND deleted_at IS NULL');
        $st->execute([$userId]);
        $einsaetze = (int)$st->fetchColumn();

        /* Die Zeilenlaengen der Konto-Tabellen. `pat_blob` ist der grosse
         * Posten je Einsatz, die GPS-Daten sind der grosse Posten
         * ueberhaupt. */
        $st = $pdo->prepare('SELECT COALESCE(SUM(
                   LENGTH(COALESCE(pat_blob, "")) + 200), 0)
                 FROM missions WHERE user_id = ?');
        $st->execute([$userId]);
        $bytes += (int)$st->fetchColumn();

        /* DIE GPS-DATEN UEBER `spur_lib.php`, NICHT PER SQL (CLAUDE.md 4).
         * Sie liegen je nach Alter als Zeilen ODER als Blob — wer nur eine
         * der beiden Tabellen zaehlt, misst je nach Bestand die Haelfte,
         * und zwar ohne Fehlermeldung. */
        require_once __DIR__ . '/spur_lib.php';
        foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tab]) {
            $ids = $pdo->prepare("SELECT id FROM `$tab` WHERE user_id = ?");
            $ids->execute([$userId]);
            $liste = array_map('intval', $ids->fetchAll(PDO::FETCH_COLUMN));
            if ($liste) { $bytes += spur_bytes($pdo, $typ, $liste); }
        }
    } catch (Throwable $ex) {
        error_log('konto_mengen: ' . $ex->getMessage());
    }

    $jetzt = time();
    app_state_setzen($schluessel, $einsaetze . '|' . $bytes . '|' . $jetzt);

    return ['einsaetze' => $einsaetze, 'bytes' => $bytes, 'gemessen' => $jetzt];
}

/**
 * Den Zaehler nach einem Upload fortschreiben — geschaetzt, nicht gemessen.
 *
 * `ingest.php` ruft das nach jedem Schub. Die echte Zahl kommt beim naechsten
 * Joblauf; bis dahin genuegt eine Schaetzung, damit die Grenze nicht erst
 * einen Tag spaeter greift.
 */
function konto_mengen_fortschreiben(int $userId, int $einsaetzePlus, int $bytesPlus): void
{
    $roh = app_state_lesen('mengen:' . $userId);
    if ($roh === null || $roh === '') { return; }   // noch nie gemessen — der Job holt es
    $t = explode('|', $roh);
    if (count($t) !== 3) { return; }
    app_state_setzen('mengen:' . $userId,
        ((int)$t[0] + $einsaetzePlus) . '|' . ((int)$t[1] + $bytesPlus) . '|' . $t[2]);
}

/** Die Grenzen dieses Kontos — die eigene, sonst die Vorgabe der Installation. */
function konto_grenzen(int $userId): array
{
    $st = db()->prepare('SELECT grenze_einsaetze, grenze_mb, backup_pakete
                           FROM users WHERE id = ?');
    $st->execute([$userId]);
    $z = $st->fetch(PDO::FETCH_ASSOC) ?: [];

    return [
        'einsaetze'    => $z['grenze_einsaetze'] !== null
                        ? (int)$z['grenze_einsaetze'] : konten_grenze_einsaetze(),
        'mb'           => $z['grenze_mb'] !== null
                        ? (int)$z['grenze_mb'] : konten_grenze_mb(),
        /* Backlog Nr. 48: die Zahl der Konto-Backups. `null` heisst „die
         * Zahl der Installation gilt" (`edbak_aufbewahrung()`). */
        'backup_pakete' => $z['backup_pakete'] !== null
                        ? (int)$z['backup_pakete'] : null,
        'eigen'        => $z['grenze_einsaetze'] !== null || $z['grenze_mb'] !== null
                        || $z['backup_pakete'] !== null,
    ];
}

/**
 * Wie voll ist dieses Konto? Der groessere der beiden Anteile zaehlt.
 *
 * @return array{anteil:float, einsaetze:int, bytes:int, grenze_einsaetze:int,
 *               grenze_bytes:int, voll:bool, warnung:bool}
 *
 * DER GROESSERE ZAEHLT, nicht der Durchschnitt: Wer 5000 Einsaetze mit
 * wenigen GPS-Daten hat, ist genauso am Ende wie jemand mit 250 MB in
 * dreihundert Aufzeichnungen. Eine gemittelte Zahl liesse beide weiterladen,
 * bis eine der beiden Grenzen weit ueberschritten ist.
 */
function konto_fuellstand(int $userId): array
{
    $m = konto_mengen($userId);
    $g = konto_grenzen($userId);
    $grenzeBytes = $g['mb'] * 1024 * 1024;

    $aE = $g['einsaetze'] > 0 ? $m['einsaetze'] / $g['einsaetze'] : 0.0;
    $aB = $grenzeBytes > 0 ? $m['bytes'] / $grenzeBytes : 0.0;
    $anteil = max($aE, $aB);

    return [
        'anteil'           => $anteil,
        'einsaetze'        => $m['einsaetze'],
        'bytes'            => $m['bytes'],
        'grenze_einsaetze' => $g['einsaetze'],
        'grenze_bytes'     => $grenzeBytes,
        'voll'             => $anteil >= 1.0,
        'warnung'          => $anteil >= KONTEN_WARNSCHWELLE,
    ];
}

/**
 * Den Zwischenspeicher verwerfen und frisch lesen.
 *
 * Fuer die eine Stelle, die innerhalb EINER Anfrage erst schreibt und dann
 * wieder liest: die Karte in den Servereinstellungen, die nach dem Speichern
 * den Vorher/Nachher-Vergleich fuers Protokoll zieht. Ohne sie vergliche sie
 * den alten Stand mit sich selbst und meldete nie eine Aenderung.
 */
function konten_einstellungen_neu_lesen(): array
{
    return konten_einstellungen(true);
}
