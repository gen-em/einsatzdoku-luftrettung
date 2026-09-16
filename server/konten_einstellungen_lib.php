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
const KONTEN_K_AUFBEWAHRUNG    = 'konten_aufbewahrung';
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
        KONTEN_K_AUFBEWAHRUNG     => '',
        KONTEN_K_DEMO_ANMELDUNG   => '1',
    ];

    try {
        $marken = array_keys($vorgaben);
        $platz  = implode(',', array_fill(0, count($marken), '?'));
        $st = db()->prepare('SELECT k, v FROM app_state WHERE k IN (' . $platz . ')');
        $st->execute($marken);
        foreach ($st->fetchAll(PDO::FETCH_KEY_PAIR) as $k => $v) {
            /* EIN LEERER WERT IST EIN WERT, ein fehlender nicht. Die
             * Aufbewahrung „unbegrenzt" ist der leere String — sie darf nicht
             * auf die Vorgabe zurueckfallen, sonst liesse sie sich nie
             * einschalten. Deshalb `!== null` und nicht `!== ''`. */
            if ($v !== null) { $vorgaben[$k] = (string)$v; }
        }
    } catch (Throwable $ex) {
        /* Tabelle fehlt (Migration noch nicht gelaufen) — die Vorgaben
         * gelten, und das ist die richtige Antwort. */
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

/** Aufbewahrung je Konto in Tagen, `null` = unbegrenzt (Backlog Nr. 48). */
function konten_aufbewahrung_tage(): ?int
{
    $roh = konten_einstellungen()[KONTEN_K_AUFBEWAHRUNG];
    if ($roh === '' || !ctype_digit($roh)) { return null; }
    return (int)$roh;
}

/** Ist die Anmeldung am Demo-Konto zugelassen? (E-P5b-07) */
function konten_demo_anmeldung_an(): bool
{
    return konten_einstellungen()[KONTEN_K_DEMO_ANMELDUNG] === '1';
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
