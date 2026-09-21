<?php
declare(strict_types=1);

/* WRAP_PW_RE und WRAP_RC_RE — die beiden Hüllen-Ausdrücke der gemeinsamen
 * Prüfschicht (CLAUDE.md 4). Sie stehen dort und nicht hier, weil sie zu den
 * Formaten der Anwendung gehören; gebraucht werden sie in
 * `huelle_pw_pruefen()` weiter unten. */
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/konfig_lib.php';

/**
 * DIE GEHEIMNISSE DES SERVERS — seit S10 sind es zwei.
 *
 * Der **Serverschlüssel** (`server_key`, seit S2/AP7) versiegelt, was der
 * Server ohne Browser lesen können muss: die Zugangsdaten der Backup-Ziele
 * und das Komplettbackup. Er öffnet keine Patientendaten und kann es auch
 * nicht.
 *
 * Der **Server-Anteil** (`kdf_anteil`, seit S10 / Schritt 9b, R78) tut etwas
 * anderes: Er geht in die Ableitung des DATENSCHLÜSSELS ein, mit dem der
 * Browser die Schlüsselhülle des Kontos öffnet. Der Server kann damit
 * trotzdem nichts öffnen — er kennt den Anteil, nicht die PBKDF2-Hälfte aus
 * dem Passwort. Was sich ändert, ist die Rechnung des Angreifers: Wer nur
 * die Datenbank hat, hat seit S10 Salz, Rundenzahl und Hülle — aber nicht
 * mehr alles, was er zum Durchprobieren braucht (Krypto-Review K-3, Weg 1).
 *
 * BEIDE LIEGEN IN config.php UND NICHT IN DER DATENBANK, und beim Anteil
 * wiegt der Grund schwerer als beim Serverschlüssel: Der Zweck ist der Fall
 * „jemand hat die Datenbank". Läge der Anteil in einer Tabelle, wäre er im
 * Abzug und der ganze Schritt gegenstandslos. Er gehört deshalb auch NICHT
 * ins Komplettbackup (SP-3, „Was nicht ins Archiv gehört").
 *
 * WAS DAS FÜR DEN BETRIEB HEISST. `config.php` ist seit S10 Schlüsselträger
 * ALLER Konten. Das Wiederanlaufpaket hat vier Stücke — `config.php`,
 * Serverschlüssel, Server-Anteil, Zugang zum Ziel —, und das Schlüsselblatt
 * (Betrieb → Servereinstellungen) ist Pflicht, nicht Empfehlung.
 * **Der Rückweg bleibt serverunabhängig:** `pat_wrap_rc` hängt NICHT am
 * Anteil. Wer ihn verliert, sperrt niemanden dauerhaft aus — jede NutzerIn
 * kommt über den Wiederherstellungsschlüssel wieder herein (E-S10-04).
 *
 * ---------------------------------------------------------------------------
 *
 * DER SERVERSCHLÜSSEL — das eine Geheimnis, das der Server selbst hat
 * (E-S2-21, S2/AP7).
 *
 * WARUM ES IHN ÜBERHAUPT GIBT, OBWOHL DIESE ANWENDUNG SONST NICHTS WEISS
 * Diagnose, Alter und Einsatzort werden im Browser ver- und entschlüsselt;
 * der Server sieht sie nie. Das bleibt so. Ab AP7 gibt es aber zwei Dinge,
 * die der Server ohne jeden Browser lesen können MUSS, weil sie zu einem
 * Zeitpunkt gebraucht werden, an dem niemand angemeldet ist:
 *
 *   1. die Zugangsdaten der Backup-Ziele (Passwort, privater Schlüssel) —
 *      der Versandjob läuft nachts über den Job-Einstieg,
 *   2. das Komplettbackup der Installation (AP8), sobald es das Haus verlässt.
 *
 * Beides ist kein Patientendatum. Der Serverschlüssel ist deshalb KEINE
 * Aufweichung der Zusage aus CLAUDE.md Abschnitt 4 — er schützt Betriebs-
 * geheimnisse, nicht Behandlungsdaten, und er kann die verschlüsselten Felder
 * auch nicht öffnen.
 *
 * WARUM ER IN config.php LIEGT UND NICHT IN DER DATENBANK
 * Der Zweck ist der Fall „jemand hat die Datenbank". Ein Schlüssel, der neben
 * dem Chiffretext in derselben Tabelle steht, hilft dann niemandem. Für das
 * Komplettbackup wird es zwingend: Der Dump enthält jede Tabelle: läge der
 * Schlüssel in einer davon, läge er im Backup, und das Backup wäre nur
 * scheinbar versiegelt.
 *
 * Damit gehört er ins WIEDERANLAUFPAKET — config.php plus Serverschlüssel
 * plus Zugang zum Backup-Ziel, getrennt aufbewahrt. Ohne ihn sind die
 * Zugangsdaten der Ziele verloren (neu eintragen, mehr nicht) und ein
 * versiegeltes Komplettbackup ist Müll (das ist die schwere Folge). Das
 * Runbook in `docs/Technik.md` sagt es an der Stelle noch einmal.
 *
 * WAS PASSIERT, WENN ER SICH ÄNDERT
 * Nichts Stilles. `sk_oeffnen()` gibt `null` zurück, und jeder Aufrufer sagt
 * dann, was Sache ist: „mit einem anderen Serverschlüssel gespeichert".
 * Ein stillschweigend leeres Passwort wäre die schlechteste aller Antworten —
 * der Versand liefe in eine Anmeldung ohne Passwort und meldete „Zugang
 * verweigert", und niemand käme auf die Ursache.
 */

/* Kennung des Formats, wie `edk1:` im Browser (crypto.js). Sie steht vorn,
 * damit ein Feld in der Datenbank auf einen Blick als versiegelt zu erkennen
 * ist — und damit eine spätere zweite Fassung neben der ersten existieren
 * kann, ohne dass geraten werden muss. */
const SK_PRAEFIX = 'edsk1:';

/* AES-256-GCM, dieselbe Wahl wie im Browser. 12 Byte Nonce (der von GCM
 * vorgesehene Normalfall), 16 Byte Prüfsumme. */
const SK_NONCE_LEN = 12;
const SK_TAG_LEN   = 16;

/**
 * Der Schlüssel als 32 Rohbytes — oder null, wenn keiner eingetragen ist.
 *
 * Erwartet werden 64 Hexzeichen in `config.php`. Alles andere ist ein
 * Eintragsfehler und wird wie „nicht vorhanden" behandelt: Ein halber
 * Schlüssel darf keine halben Chiffren erzeugen.
 */
function serverschluessel(bool $frisch = false): ?string
{
    static $roh = false;                      // false = noch nicht gelesen
    if ($frisch) { $roh = false; }
    if ($roh !== false) { return $roh; }
    $hex = (string)konfig('server_key', '');
    if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) { return $roh = null; }
    $bin = hex2bin(strtolower($hex));
    return $roh = ($bin === false ? null : $bin);
}

/** Kurzform für die Oberfläche. */
function serverschluessel_da(): bool
{
    return serverschluessel() !== null;
}

/**
 * Die Kennung eines Geheimnisses — acht Hexzeichen, die man vorlesen kann.
 *
 * WOZU SIE DA IST. Ein 64-Zeichen-Geheimnis lässt sich nicht vergleichen,
 * ohne es zu zeigen, und zeigen darf man es nicht. Die Kennung ist der
 * Fingerabdruck: Sie steht auf dem Schlüsselblatt, auf der Karte, in
 * `app_state` und im Präfix jeder Hülle — und wer zwei davon nebeneinander
 * hält, sieht sofort, ob es dasselbe Geheimnis ist. Aus ihr zurückzurechnen
 * geht nicht; sie ist kein Schutzmerkmal, sondern ein Vergleichsmerkmal.
 *
 * ACHT ZEICHEN, WEIL SIE ABGESCHRIEBEN WERDEN. Vier wären zu wenig (bei
 * 65 536 Möglichkeiten ist eine zufällige Übereinstimmung denkbar), sechzehn
 * wären auf Papier eine zweite Fehlerquelle. Acht Hexzeichen sind 32 Bit —
 * genug, damit ein Vertippen auffällt, kurz genug, um sie am Telefon zu
 * nennen.
 *
 * ÜBER DIE KLEINGESCHRIEBENEN HEXZEICHEN, NICHT ÜBER DIE ROHBYTES. Das ist
 * eine Festlegung und keine Feinheit: Dieselbe Rechnung muss in PHP
 * (`hash('sha256', …)`) und auf dem Schlüsselblatt herauskommen, und auf dem
 * Blatt steht die Hexform. Wer vom Blatt abschreibt, darf gross schreiben —
 * deshalb wird hier kleingeschrieben, bevor gerechnet wird.
 *
 * Gibt `null` für alles, was keine 64 Hexzeichen sind — dieselbe Linie wie
 * `serverschluessel()`: ein halber Wert bekommt keine halbe Kennung.
 */
function schluessel_kennung(?string $hex): ?string
{
    if ($hex === null || !preg_match('/^[0-9a-fA-F]{64}$/', $hex)) { return null; }
    return substr(hash('sha256', strtolower($hex)), 0, 8);
}

/** Kennung des Serverschlüssels — für Anzeige und Schlüsselblatt, nie der Wert. */
function serverschluessel_kennung(): ?string
{
    return schluessel_kennung((string)konfig('server_key', ''));
}

/**
 * 64 Hexzeichen in Vierergruppen — die Form fürs Papier (E-S10-10).
 *
 * WOZU DIE GRUPPEN. Das Schlüsselblatt ist zum Abtippen da, und zwar im
 * Ernstfall: nach einem Wiederanlauf, unter Zeitdruck, von einem Ausdruck.
 * 64 Zeichen am Stück verliert man beim Lesen; sechzehn Gruppen zu vier hält
 * das Auge. Dieselbe Entscheidung wie beim Wiederherstellungsschlüssel, nur
 * dort mit Bindestrichen — hier mit Leerzeichen, weil ein Bindestrich in
 * einem Hexwert wie ein Zeichen aussieht, das dazugehört.
 *
 * BEIM EINLESEN WIRD DIE GRUPPIERUNG WIEDER ENTFERNT
 * (`schluessel_eingabe_normalisieren()`), samt Groß-/Kleinschreibung: Wer vom
 * Blatt abschreibt, soll nicht daran scheitern, wie er die Lesehilfe tippt.
 */
function schluessel_gruppen(?string $hex): string
{
    if ($hex === null) { return ''; }
    return hex_vierergruppen(strtolower($hex));
}

/**
 * Was von der Tastatur kommt, auf 64 Hexzeichen zurückführen — oder null.
 *
 * Entfernt jeden Leerraum und jeden Bindestrich und schreibt klein. Was
 * danach keine 64 Hexzeichen sind, ist keine Eingabe, sondern ein Vertipper —
 * und wird als `null` zurückgegeben, statt halb verarbeitet zu werden.
 */
function schluessel_eingabe_normalisieren(string $roh): ?string
{
    $h = strtolower(preg_replace('/[\s\-]+/', '', $roh) ?? '');
    return preg_match('/^[0-9a-f]{64}$/', $h) ? $h : null;
}

/**
 * Ein frischer Schlüssel als 64 Hexzeichen.
 *
 * `random_bytes()` und nichts anderes: Es ist die einzige Quelle in PHP, die
 * bei fehlender Entropie wirft, statt schwache Bytes zu liefern.
 */
function serverschluessel_neu(): string
{
    return bin2hex(random_bytes(32));
}

/** Die Zeile, die in `config.php` gehört — genau so, wie sie dort steht.
 *
 *  Seit S10 nur noch der Name über `config_eintrag_zeile()`: Zwei Funktionen,
 *  die dieselbe Zeile bauen, laufen früher oder später auseinander, und dann
 *  steht auf der Seite eine Zeile, die die schreibende Funktion nicht
 *  wiederfindet. */
function serverschluessel_zeile(string $hex): string
{
    return config_eintrag_zeile('server_key', $hex);
}

/**
 * Versiegeln. Gibt `edsk1:` + base64(nonce ‖ prüfsumme ‖ chiffre) zurück.
 *
 * DER ZWECK GEHT IN DIE ZUSATZDATEN und ist damit Teil der Prüfsumme. Er
 * verhindert das Umhängen: Ein versiegeltes FTP-Passwort aus Ziel 3 lässt
 * sich nicht als Passwort von Ziel 7 einsetzen, obwohl beide mit demselben
 * Schlüssel versiegelt sind — die Prüfsumme passt dann nicht mehr. Ohne
 * diesen Zusatz wäre der Chiffretext eine Münze, die überall gilt.
 *
 * @throws RuntimeException wenn kein Serverschlüssel eingetragen ist. Das ist
 *         Absicht: Wer versiegeln will und nicht kann, darf nicht im Klartext
 *         weitermachen.
 */
function sk_versiegeln(string $klartext, string $zweck): string
{
    $k = serverschluessel();
    if ($k === null) {
        throw new RuntimeException('Es ist kein Serverschlüssel eingetragen '
            . '(config.php, Eintrag server_key).');
    }
    $nonce = random_bytes(SK_NONCE_LEN);
    $tag   = '';
    $ct = openssl_encrypt($klartext, 'aes-256-gcm', $k, OPENSSL_RAW_DATA,
                          $nonce, $tag, 'edsk1|' . $zweck, SK_TAG_LEN);
    if ($ct === false) {
        throw new RuntimeException('Verschlüsseln fehlgeschlagen.');
    }
    return SK_PRAEFIX . base64_encode($nonce . $tag . $ct);
}

/**
 * Öffnen. Gibt den Klartext zurück — oder `null`.
 *
 * `null` heisst genau eines: Dieses Paket lässt sich mit DIESEM Schlüssel und
 * DIESEM Zweck nicht öffnen. Ob der Schlüssel fehlt, ein anderer ist oder der
 * Chiffretext beschädigt wurde, unterscheidet die Funktion bewusst nicht —
 * jede dieser Unterscheidungen wäre für einen Angreifer eine Auskunft und für
 * die Betreiberin keine Hilfe. Was die Betreiberin braucht, steht in der
 * Oberfläche: „mit einem anderen Serverschlüssel gespeichert".
 */
function sk_oeffnen(string $paket, string $zweck): ?string
{
    $k = serverschluessel();
    if ($k === null) { return null; }
    if (!str_starts_with($paket, SK_PRAEFIX)) { return null; }
    $roh = base64_decode(substr($paket, strlen(SK_PRAEFIX)), true);
    if ($roh === false || strlen($roh) < SK_NONCE_LEN + SK_TAG_LEN) { return null; }
    $nonce = substr($roh, 0, SK_NONCE_LEN);
    $tag   = substr($roh, SK_NONCE_LEN, SK_TAG_LEN);
    $ct    = substr($roh, SK_NONCE_LEN + SK_TAG_LEN);
    $klar = openssl_decrypt($ct, 'aes-256-gcm', $k, OPENSSL_RAW_DATA,
                            $nonce, $tag, 'edsk1|' . $zweck);
    return $klar === false ? null : $klar;
}

/**
 * Ist dieser Wert ein versiegeltes Paket?
 *
 * Gebraucht an den Stellen, die entscheiden müssen, ob ein Feld schon
 * versiegelt ist oder noch im Klartext ankommt (Formular).
 */
function sk_versiegelt(string $wert): bool
{
    return str_starts_with($wert, SK_PRAEFIX);
}

/* ===========================================================================
 * DER SERVER-ANTEIL AM DATENSCHLÜSSEL (S10, E-S10-02 bis E-S10-04)
 * ======================================================================== */

/**
 * Der Server-Anteil als 32 Rohbytes — oder null, wenn keiner eingetragen ist.
 *
 * Wortgleich gebaut wie `serverschluessel()`, und das mit Absicht: Beide
 * Werte stehen in derselben Datei, tragen dieselbe Form (64 Hexzeichen) und
 * scheitern auf dieselbe Weise. Ein halber Anteil darf keine halben
 * Datenschlüssel erzeugen — und ein halber Datenschlüssel öffnet keine Hülle
 * und sieht dabei aus wie ein falsches Passwort.
 */
function kdf_anteil(bool $frisch = false): ?string
{
    static $roh = false;
    if ($frisch) { $roh = false; }
    if ($roh !== false) { return $roh; }
    $hex = (string)konfig('kdf_anteil', '');
    if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) { return $roh = null; }
    $bin = hex2bin(strtolower($hex));
    return $roh = ($bin === false ? null : $bin);
}

/**
 * Der VORHERIGE Server-Anteil — gesetzt, solange eine Rotation läuft.
 *
 * Während einer Rotation stehen beide Werte in `config.php`, und beide werden
 * an die angemeldete Sitzung ausgeliefert: Der Browser muss eine Hülle noch
 * öffnen können, die mit dem alten Anteil gebaut wurde, um sie mit dem neuen
 * neu zu bauen. Steht niemand mehr auf dem alten (Statuszeile zählt es),
 * verschwindet der Eintrag wieder.
 */
function kdf_anteil_alt(bool $frisch = false): ?string
{
    static $roh = false;
    if ($frisch) { $roh = false; }
    if ($roh !== false) { return $roh; }
    $hex = (string)konfig('kdf_anteil_alt', '');
    if (!preg_match('/^[0-9a-fA-F]{64}$/', $hex)) { return $roh = null; }
    $bin = hex2bin(strtolower($hex));
    return $roh = ($bin === false ? null : $bin);
}

/** Ein frischer Anteil als 64 Hexzeichen — dieselbe Quelle wie beim Schlüssel. */
function kdf_anteil_neu(): string
{
    return bin2hex(random_bytes(32));
}

/**
 * Der Anteil DIESES Kontos — 64 Hexzeichen, abgeleitet aus der Kontonummer.
 *
 *     kontoAnteil = HMAC-SHA256(schlüssel = kdf_anteil, nachricht = "konto:<id>")
 *
 * WARUM AUS DER KONTONUMMER UND NICHT AUS DEM SALZ (F-S10-1, E-S10-03). Die
 * Vorbereitung (SP-3) schlug das Salz vor. Das geht nicht: Passwortwechsel
 * und Reset würfeln das NEUE Salz im Browser, und der Anteil dazu wäre dem
 * Browser in genau dem Augenblick unbekannt, in dem er die neue Hülle baut.
 * Es bräuchte einen zweiten Umlauf zum Server oder der Server müsste das Salz
 * wählen — beides teuer für nichts. Die Kontonummer ist unveränderlich, je
 * Installation eindeutig und schon da.
 *
 * DASS DIE NUMMER ERRATBAR IST, KOSTET NICHTS. Sie ist keine Zutat, die
 * geheim sein müsste — das Geheimnis ist `kdf_anteil`. Was die Ableitung
 * leistet, ist die Trennung: Wer den Anteil EINES Kontos in die Hände
 * bekommt (etwa aus einer offenen Sitzung), kann daraus keinen anderen
 * bilden. Das ist die Eigenschaft von HMAC, und dafür steht es hier und
 * nicht ein schlichtes Anhängen.
 */
function konto_anteil(int $userId, string $roh): string
{
    return hash_hmac('sha256', 'konto:' . $userId, $roh);
}

/**
 * Die Anteil-Kennung, die eine Schlüsselhülle im Präfix trägt — oder null.
 *
 * Eine Hülle mit Anteil lautet `edka1:<kennung>:<base64>` (E-S10-05); eine
 * ohne Anteil `edk1:<base64>` oder ganz ohne Präfix (Altbestand). An dieser
 * einen Stelle wird das gelesen, serverseitig — der Server öffnet dabei
 * nichts und kann es auch nicht.
 *
 * WOZU DER SERVER DAS ÜBERHAUPT BRAUCHT, obwohl er keine Hülle öffnet: Er
 * muss zwei Dinge entscheiden, für die die Kennung reicht. Erstens, ob eine
 * Hülle, die ihm zum Speichern gereicht wird, zum AKTUELLEN Anteil gehört
 * (`api/kdf_upgrade.php` — sonst könnte ein Fehler das Konto auf den alten
 * Anteil zurückstellen). Zweitens, wie viele Konten noch umzustellen sind
 * (Statuszeile, `SUBSTRING` in SQL, ohne dass je eine Hülle geöffnet wird).
 */
function huelle_anteil_kennung(?string $huelle): ?string
{
    if ($huelle === null) { return null; }
    return preg_match('/^edka1:([0-9a-f]{8}):/', $huelle, $m) ? $m[1] : null;
}

/* ---- Marken in app_state ------------------------------------------------
 *
 * Zwei Schlüssel, beide erst seit S10: `kdf_anteil_kennung` (mit welchem
 * Anteil die Hüllen gebaut werden) und `server_key_kennung` (mit welchem
 * Serverschlüssel versiegelt wurde).
 *
 * WARUM DIE KENNUNG IN DIE DATENBANK GEHÖRT UND NICHT NUR IN config.php.
 * Sie ist das Gegenstück: `config.php` sagt, welchen Wert diese Installation
 * HAT, `app_state` sagt, mit welchem sie GEARBEITET hat. Erst der Vergleich
 * beider ergibt eine Aussage — und zwar genau die, die sonst niemand
 * bekommt: „Der Anteil ist nicht der, mit dem die Hüllen gebaut wurden."
 * Ohne sie sähe dieselbe Lage aus wie ein falsches Passwort, und zwar für
 * jede NutzerIn gleichzeitig. Das ist die stille Aussperrung, gegen die
 * diese zwei Zeilen stehen (SP-3).
 *
 * Ein Abzug der Datenbank gewinnt damit nichts: Eine Kennung ist ein
 * Fingerabdruck, kein Wert.
 */

/** Eine Marke lesen. `null`, wenn sie fehlt — oder `app_state` noch nicht da ist. */
function schluessel_marke_lesen(string $k): ?string
{
    try {
        $st = db()->prepare('SELECT v FROM app_state WHERE k = ?');
        $st->execute([$k]);
        $v = $st->fetchColumn();
        return ($v === false || $v === null) ? null : (string)$v;
    } catch (Throwable $ex) {
        /* app_state fehlt (Migration noch nicht gelaufen) — dann verhält sich
         * die Installation wie vor S10, und das ist der richtige Zustand. */
        return null;
    }
}

/** Eine Marke setzen. Scheitert leise; sie ist eine Auskunft, kein Riegel. */
function schluessel_marke_setzen(string $k, string $v): void
{
    try {
        db()->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute([$k, $v]);
    } catch (Throwable $ex) {
        error_log('app_state: Marke ' . $k . ' liess sich nicht setzen — '
                . $ex->getMessage());
    }
}

/**
 * Der Zustand des Server-Anteils — die fünf Lagen aus E-S10-09.
 *
 * Gibt zurück:
 *   stand        'fehlt' | 'bereit' | 'rotation' | 'abweichend'
 *   kennung      Kennung des aktuellen Anteils aus config.php, oder null
 *   kennung_alt  Kennung des vorherigen Anteils (nur bei Rotation), oder null
 *   erwartet     Kennung aus app_state — die, mit der die Hüllen gebaut sind
 *
 * DIE VIER LAGEN UND WAS SIE BEDEUTEN
 *
 *   fehlt        Keine `kdf_anteil`-Zeile, keine Marke. Das ist der Zustand
 *                JEDER Installation unmittelbar nach dem Ausrollen von S10:
 *                Es wird nichts ausgeliefert, die Hüllen bleiben `edk1:`, und
 *                die Anwendung läuft Zeile für Zeile wie vorher. S10 tut
 *                nichts, bis jemand auf der Karte „Anlegen" drückt — das ist
 *                Absicht und steht als fällige Zuarbeit im Rahmenplan.
 *   bereit       Wert und Marke stimmen überein. Der Regelfall.
 *   rotation     Es gibt einen zweiten, vorherigen Anteil. Beide werden
 *                ausgeliefert; die Umstellung läuft je Konto beim nächsten
 *                Anmelden.
 *   abweichend   Der Wert ist ein anderer als der, mit dem gearbeitet wurde
 *                — oder er fehlt, obwohl gearbeitet wurde. DANN WIRD NICHTS
 *                AUSGELIEFERT. Eine `edka1:`-Hülle lässt sich nicht öffnen,
 *                und die Seite sagt das mit der erwarteten Kennung, statt
 *                „Passwort falsch" zu behaupten.
 *
 * DIE MARKE WIRD NACHGETRAGEN, WENN SIE FEHLT (E-S10-U-02). E-S10-09 sagt
 * „gesetzt bei der ersten Auslieferung"; die Abnahme von AP3 verlangt, dass
 * unmittelbar nach dem ANLEGEN Karte, Blatt und `app_state` dieselbe Kennung
 * zeigen — und beim Anlegen ist noch nichts ausgeliefert. Beides wird wahr,
 * indem diese Funktion die Marke nachträgt, sobald sie fehlt und ein
 * gültiger Wert dasteht. Sie wird sowohl von der Karte als auch von
 * `ui_krypto_bootstrap()` gerufen, also greift es in beiden Fällen.
 *
 * Was dabei in Kauf genommen wird: Auf einer Installation ohne Marke
 * übernimmt die Funktion, WAS DASTEHT — auch einen falsch abgeschriebenen
 * Wert. Das ist derselbe Fall, den E-S10-10 benennt („Ohne Eintrag in
 * `app_state` gibt es nichts zu prüfen"), und er ist harmlos: In diesem
 * Zustand existiert keine `edka1:`-Hülle, gegen die man prüfen könnte.
 *
 * Das Ergebnis wird gemerkt — die Funktion läuft auf jeder angemeldeten
 * Seite. `$frisch` setzt zurück, nachdem `config.php` geschrieben wurde.
 */
function anteil_zustand(bool $frisch = false): array
{
    static $merk = null;
    if ($frisch) { $merk = null; }
    if ($merk !== null) { return $merk; }

    $kennung    = schluessel_kennung((string)konfig('kdf_anteil', ''));
    $kennungAlt = schluessel_kennung((string)konfig('kdf_anteil_alt', ''));
    $erwartet   = schluessel_marke_lesen('kdf_anteil_kennung');

    if ($kennung === null) {
        /* Kein Wert. Ohne Marke ist das „noch nicht eingerichtet", MIT Marke
         * ist es der Ernstfall: Es wurde schon mit einem Anteil gearbeitet,
         * und der ist jetzt weg — etwa nach einem Wiederanlauf aus einer
         * Sicherung ohne `config.php`. */
        $stand = ($erwartet === null) ? 'fehlt' : 'abweichend';
        return $merk = ['stand' => $stand, 'kennung' => null,
                        'kennung_alt' => null, 'erwartet' => $erwartet];
    }

    if ($erwartet === null) {
        schluessel_marke_setzen('kdf_anteil_kennung', $kennung);
        $erwartet = $kennung;
    } elseif ($erwartet === $kennungAlt && $kennungAlt !== null) {
        /* Rotation soeben eingeleitet: Der neue Wert steht in `config.php`,
         * die Marke nennt noch den alten. Sie wandert jetzt mit — ab hier
         * werden neue Hüllen mit dem neuen Anteil gebaut, und der alte ist
         * nur noch zum Öffnen da. */
        schluessel_marke_setzen('kdf_anteil_kennung', $kennung);
        $erwartet = $kennung;
    }

    if ($erwartet !== $kennung) {
        return $merk = ['stand' => 'abweichend', 'kennung' => $kennung,
                        'kennung_alt' => $kennungAlt, 'erwartet' => $erwartet];
    }

    return $merk = ['stand' => $kennungAlt === null ? 'bereit' : 'rotation',
                    'kennung' => $kennung, 'kennung_alt' => $kennungAlt,
                    'erwartet' => $erwartet];
}

/**
 * Wird gerade ein Anteil ausgeliefert? Dann ist seine Kennung die Antwort.
 *
 * Die eine Frage, die Aufrufer wirklich stellen: `ui_krypto_bootstrap()`,
 * `pw_handling.php` und `api/kdf_upgrade.php`. `null` heisst „kein Anteil" —
 * und zwar gleichgültig, ob keiner eingetragen ist oder der eingetragene der
 * falsche ist. Dieselbe Linie wie `sk_oeffnen()`: Die Unterscheidung nützt
 * der Betreiberin (Karte, Status) und nicht dem Aufrufer.
 */
function anteil_ausgeliefert(): ?string
{
    $z = anteil_zustand();
    return in_array($z['stand'], ['bereit', 'rotation'], true) ? $z['kennung'] : null;
}

/**
 * Darf diese Passwort-Hülle so gespeichert werden? `null` = ja, sonst der Grund.
 *
 * DIE EINE PRÜFUNG FÜR ALLE VIER SCHREIBWEGE (S10, Fund F-3). `pat_wrap_pw`
 * wird an vier Stellen geschrieben — `pw_handling.php` bei Erstvergabe und
 * bei Reset, `einstellungen.php` beim Passwortwechsel und
 * `api/kdf_upgrade.php` bei der stillen Umstellung. Nach AP1 prüfte nur der
 * letzte, ob die Kennung im Präfix die aktuelle ist. Das genügt nicht: Ein
 * Passwortwechsel während einer Rotation könnte eine Hülle auf dem ALTEN
 * Anteil schreiben, und die wäre in dem Augenblick unbrauchbar, in dem
 * `kdf_anteil_alt` aus `config.php` verschwindet — also genau dann, wenn die
 * Statusseite meldet, es stehe niemand mehr auf dem alten.
 *
 * ZWEI RICHTUNGEN, EINE REGEL: Wird ein Anteil ausgeliefert, MUSS die Hülle
 * seine Kennung tragen; wird keiner ausgeliefert, darf sie KEINE tragen. Der
 * Server kann die Hülle nicht öffnen — was er prüfen kann, ist das Präfix,
 * und für diesen Fehler genügt das.
 *
 * DAS DEMO-KONTO IST AUSGENOMMEN und muss es sein (E-P1-19): Es bekommt
 * keinen Anteil, seine Hülle bleibt `edk1:`. Der Aufrufer sagt das mit
 * `$istDemo`, statt dass diese Funktion `demo_lib.php` nachlädt — sie liegt
 * unter `db.php` und soll dort nicht hinaufgreifen.
 */
function huelle_pw_pruefen(?string $wrap, bool $istDemo = false): ?string
{
    if ($wrap === null || $wrap === '') { return null; }
    if (!preg_match(WRAP_PW_RE, $wrap)) {
        return 'Die Schlüsselhülle hat ein unbrauchbares Format.';
    }
    $hat  = huelle_anteil_kennung($wrap);
    $soll = $istDemo ? null : anteil_ausgeliefert();

    if ($soll === null && $hat !== null) {
        return 'Diese Schlüsselhülle nennt einen Server-Anteil, den diese '
             . 'Installation nicht ausliefert. Es wurde nichts geändert.';
    }
    if ($soll !== null && $hat !== $soll) {
        return 'Diese Schlüsselhülle gehört nicht zum aktuellen Server-Anteil '
             . '(Kennung ' . $soll . ' erwartet). Es wurde nichts geändert.';
    }
    return null;
}

/**
 * Dasselbe für die Wiederherstellungs-Hülle — sie trägt NIE einen Anteil.
 *
 * Der eigene Ausdruck (`WRAP_RC_RE`) tut das schon; diese Funktion ist der
 * Ort, an dem die Begründung steht, und der Aufrufer, der beide Hüllen prüft,
 * ruft zwei Funktionen statt einer Regel zweimal. Wer `pat_wrap_rc` je an den
 * Anteil hängt, nimmt der Anwendung ihren Rückweg (E-S10-04).
 */
function huelle_rc_pruefen(?string $wrap): ?string
{
    if ($wrap === null || $wrap === '') { return null; }
    if (huelle_anteil_kennung($wrap) !== null) {
        return 'Die Wiederherstellungs-Hülle darf nicht am Server-Anteil '
             . 'hängen — sie ist der Rückweg, wenn er verloren geht.';
    }
    if (!preg_match(WRAP_RC_RE, $wrap)) {
        return 'Die Wiederherstellungs-Hülle hat ein unbrauchbares Format.';
    }
    return null;
}

/**
 * Die Anteile, die dieses Konto bekommt — `{ kennung: 64 hex }`.
 *
 * Im Regelfall einer, während einer Rotation zwei. Leer, wenn nichts
 * ausgeliefert wird. Das Demo-Konto fragt hier nicht an; darüber entscheidet
 * `auth_guard.php` (E-S10-06).
 */
function konto_anteile(int $userId): array
{
    $z = anteil_zustand();
    if (!in_array($z['stand'], ['bereit', 'rotation'], true)) { return []; }

    $aus = [];
    $roh = kdf_anteil();
    if ($roh !== null && $z['kennung'] !== null) {
        $aus[$z['kennung']] = konto_anteil($userId, $roh);
    }
    $rohAlt = kdf_anteil_alt();
    if ($rohAlt !== null && $z['kennung_alt'] !== null) {
        $aus[$z['kennung_alt']] = konto_anteil($userId, $rohAlt);
    }
    return $aus;
}

/* ===========================================================================
 * SCHREIBEN IN config.php (S10, E-S10-10)
 * ======================================================================== */

/** Die Einträge, die diese Anwendung in `config.php` schreiben darf.
 *
 *  EINE GESCHLOSSENE LISTE, WEIL DER SCHLÜSSELNAME VON AUSSEN KOMMT. Das
 *  Formular „Nachtragen vom Blatt" sagt, WELCHES Geheimnis gemeint ist. Ohne
 *  diese Liste wäre das eine Handhabe, einen beliebigen Eintrag in die
 *  Konfigurationsdatei zu schreiben — `db.dsn` etwa. Die Prüfung steht
 *  deshalb in der Funktion und nicht im Formular: Ein zweiter Aufrufer
 *  erbte sie sonst nicht. */
const CONFIG_SCHREIBBAR = ['server_key', 'kdf_anteil', 'kdf_anteil_alt'];

/** Die Zeile, die in `config.php` gehört — genau so, wie sie dort steht. */
function config_eintrag_zeile(string $schluessel, string $hex): string
{
    return "    '" . $schluessel . "' => '" . strtolower($hex) . "',";
}

/**
 * Die gemerkten Werte neu einlesen, nachdem `config.php` geschrieben wurde.
 *
 * Drei Funktionen halten ihr Ergebnis in einer `static`. Ohne diese Zeilen
 * gäbe die Seite, die gerade geschrieben hat, im selben Aufruf noch den
 * alten Stand zurück — und zeigte nach dem Anlegen weiter „kein Anteil".
 * Genau dieser Fehler ist in S2/AP7 beim Serverschlüssel aufgetreten.
 */
function config_gemerktes_verwerfen(): void
{
    /* ZUERST DIE KONFIGURATION SELBST (Schritt 15 AP2, E-ZE-14). Die vier
     * Zeilen darunter lesen ueber `konfig()` nach; stuende das Gemerkte noch,
     * holten sie sich genau den Stand zurueck, den sie wegwerfen sollen. */
    konfig_verwerfen();
    serverschluessel(true);
    kdf_anteil(true);
    kdf_anteil_alt(true);
    anteil_zustand(true);
}

/**
 * Einen Hexwert in `config.php` eintragen — wenn die Datei beschreibbar ist.
 *
 * Gibt `[true, hex]` zurück, wenn er drinsteht, sonst `[false, meldung]`.
 * `$hex === null` ENTFERNT den Eintrag (gebraucht für „alten Anteil
 * entfernen" nach einer Rotation, E-S10-11).
 *
 * WARUM DIESE FUNKTION ÜBERHAUPT SCHREIBT. Der Weg ohne sie hiesse: eine
 * Zeile abschreiben, per FTP in `config.php` einfügen, Datei hochladen. Das
 * geht — und ist genau die Art Handgriff, bei der ein Zeichen verlorengeht
 * und danach niemand weiss, warum der Versand nicht läuft. Klappt das
 * Schreiben nicht, bleibt der Weg von Hand; die Oberfläche zeigt dann die
 * fertige Zeile (`config_eintrag_zeile()`).
 *
 * ERSETZEN IST DIE AUSNAHME UND MUSS ANGESAGT WERDEN. Bis S10 konnte diese
 * Funktion nur ERGÄNZEN: Steht schon ein Wert da, bricht sie ab. Der Grund
 * war und ist gut — ein überschriebener Serverschlüssel macht jedes
 * versiegelte Feld unlesbar, ein überschriebener Anteil jede Hülle.
 *
 * S10 braucht das Ersetzen trotzdem, und zwar für genau einen Fall: Nach
 * einem Wiederanlauf steht ein FALSCHER Wert in der Datei — abgeschrieben,
 * vertippt, aus der falschen Sicherung. Ihn nicht ersetzen zu können hiesse,
 * die Datei doch von Hand anzufassen. Deshalb:
 *
 *   - Steht dort KEIN gültiger Wert (leer, halb, Unfug), wird ergänzt oder
 *     ersetzt, ohne zu fragen — kaputtgehen kann nichts, was schon kaputt ist.
 *   - Steht dort ein GÜLTIGER Wert, verlangt die Funktion `$ersetzen = true`.
 *     Das Formular macht daraus ein Ankreuzfeld, das man bewusst setzt.
 *
 * Die Kennungsprüfung — „ist das der Wert, mit dem die Hüllen gebaut
 * wurden?" — steht NICHT hier, sondern beim Aufrufer (E-S10-10): Sie gilt
 * fürs Nachtragen, nicht fürs Anlegen und nicht fürs Rotieren, und eine
 * Prüfung, die je nach Weg anders ausfällt, gehört nicht in die Funktion,
 * die schreibt.
 *
 * WIE HIER GESCHRIEBEN WIRD, DAMIT NICHTS KAPUTTGEHT
 *   1. Geschrieben wird in eine NEBENDATEI mit Endung `.php` und erst danach
 *      umbenannt. Die Endung ist kein Zufall: `server/` ist das Wurzel-
 *      verzeichnis des Webservers. Eine `config.php.tmp` läge dort als
 *      Textdatei mit dem Datenbankpasswort — abrufbar über den Browser.
 *   2. Die Nebendatei wird VOR dem Umbenennen eingelesen und geprüft: Sie
 *      muss ein Feld ergeben, das JEDEN anderen Abschnitt unverändert enthält
 *      und den neuen Wert dazu. Erst dann ersetzt sie das Original.
 */
function config_eintrag_schreiben(string $schluessel, ?string $hex,
                                  bool $ersetzen = false): array
{
    if (!in_array($schluessel, CONFIG_SCHREIBBAR, true)) {
        return [false, 'Dieser Eintrag darf nicht geschrieben werden.'];
    }
    if ($hex !== null && !preg_match('/^[0-9a-fA-F]{64}$/', $hex)) {
        return [false, 'Der Wert sind nicht 64 Hexzeichen. Es wurde nichts geändert.'];
    }
    $hex = $hex === null ? null : strtolower($hex);

    $pfad = __DIR__ . '/config.php';
    if (!is_file($pfad)) {
        return [false, 'config.php wurde nicht gefunden.'];
    }
    $inhalt = @file_get_contents($pfad);
    if ($inhalt === false) {
        return [false, 'config.php liess sich nicht lesen.'];
    }

    /* Eine ganze Zeile, die diesen Eintrag trägt. `var_export()` — und damit
     * jede vom Installer erzeugte Datei — schreibt jeden Eintrag der obersten
     * Ebene auf eine eigene Zeile; `config.example.php` ebenso. Dass hier
     * zeilenweise gearbeitet wird statt mit einem Parser, ist die
     * konservative Wahl: Was nicht auf eine Zeile passt, wird nicht angefasst,
     * und dann greift die Gegenprobe unten. */
    $zeilenMuster = '/^[ \t]*([\'"])' . preg_quote($schluessel, '/')
                  . '\1\s*=>.*\R?/m';
    $vorhanden = preg_match($zeilenMuster, $inhalt) === 1;
    $gueltig   = preg_match('/^[0-9a-fA-F]{64}$/',
                            (string)konfig($schluessel, '')) === 1;

    if ($hex === null && !$vorhanden) {
        return [true, ''];                      // schon fort — nichts zu tun
    }
    if ($hex !== null && $vorhanden && $gueltig && !$ersetzen) {
        return [false, 'In config.php steht bereits ein gültiger Eintrag '
            . $schluessel . '. Zum Überschreiben bitte „ersetzen" ankreuzen.'];
    }
    if (!is_writable($pfad) || !is_writable(__DIR__)) {
        return [false, 'config.php ist nicht beschreibbar.'];
    }

    if ($vorhanden) {
        $ersatz = $hex === null ? '' : config_eintrag_zeile($schluessel, $hex) . "\n";
        $treffer = 0;
        $neu = preg_replace($zeilenMuster, $ersatz, $inhalt, 1, $treffer);
        if ($neu === null || $treffer !== 1) {
            return [false, 'Der vorhandene Eintrag ' . $schluessel
                . ' in config.php steht nicht auf einer eigenen Zeile. '
                . 'Bitte von Hand berichtigen.'];
        }
    } else {
        /* Eingefügt wird direkt hinter dem Beginn des Feldes — der einzigen
         * Stelle, die in jeder Fassung dieser Datei vorkommt.
         *
         * ZWEI SCHREIBWEISEN, UND DIE HÄUFIGERE STAND ZUERST NICHT DA. Der
         * Installer schreibt die Datei mit `var_export()`, und das ergibt
         * `return array (` — nicht `return [`. Nur `config.example.php`
         * benutzt die kurze Form. Der erste Versuch traf deshalb ausgerechnet
         * jede echte Installation nicht (Browserprobe S2/AP7).
         *
         * Die Einrückung wird von der Zeile darunter ABGESCHRIEBEN:
         * `var_export` rückt zwei Zeichen ein, die Beispieldatei vier. Eine
         * feste Einrückung sähe in einer der beiden Fassungen schief aus. */
        $treffer = 0;
        $neu = preg_replace_callback(
            '/(return\s*(?:\[|array\s*\()\s*\R)([ \t]*)/',
            static fn(array $m): string =>
                $m[1] . $m[2] . "'" . $schluessel . "' => '" . $hex . "',\n" . $m[2],
            $inhalt, 1, $treffer);
        if ($neu === null || $treffer !== 1) {
            return [false, 'In config.php war weder „return [" noch '
                . '„return array (" zu finden.'];
        }
    }

    $tmp = __DIR__ . '/config.neu.php';
    if (@file_put_contents($tmp, $neu, LOCK_EX) === false) {
        return [false, 'Die neue config.php liess sich nicht schreiben.'];
    }
    @chmod($tmp, 0640);

    /* GEGENPROBE VOR DEM UMBENENNEN. Eine kaputte config.php legt die ganze
     * Anwendung still — jede Seite lädt sie. Deshalb wird die Nebendatei erst
     * ausgeführt und Abschnitt für Abschnitt mit dem verglichen, was gerade
     * gilt. Schlägt das fehl, bleibt das Original stehen.
     *
     * VERGLICHEN WIRD ÜBER ALLE SCHLÜSSEL, NICHT ÜBER DREI BENANNTE. Bis S10
     * standen `db`, `app` und `smtp` hier als Liste — und jeder vierte
     * Abschnitt, den eine spätere Fassung dazunimmt, wäre stillschweigend
     * ungeprüft geblieben. Dass die Liste heute noch stimmte, war Glück. */
    $probe = null;
    try {
        $probe = include $tmp;
    } catch (Throwable $e) {
        $probe = null;
    }
    $heil = is_array($probe);
    if ($heil) {
        $heil = $hex === null
            ? !array_key_exists($schluessel, $probe)
            : (($probe[$schluessel] ?? null) === $hex);
    }
    if ($heil) {
        foreach (konfig_alles() as $k => $v) {
            if ($k === $schluessel) { continue; }
            if (!array_key_exists($k, $probe) || $probe[$k] != $v) {
                $heil = false;
                break;
            }
        }
    }
    if (!$heil) {
        @unlink($tmp);
        return [false, 'Die geänderte config.php hat die Gegenprobe nicht '
            . 'bestanden. Es wurde nichts ersetzt.'];
    }
    if (!@rename($tmp, $pfad)) {
        @unlink($tmp);
        return [false, 'Die geänderte config.php liess sich nicht an ihren '
            . 'Platz schieben.'];
    }
    @chmod($pfad, 0640);
    /* DEN BYTECODE-ZWISCHENSPEICHER VERWERFEN. OPcache merkt sich die
     * übersetzte config.php und prüft ihren Zeitstempel SEKUNDENGENAU. Wird
     * sie in derselben Sekunde ersetzt, in der die alte Fassung übersetzt
     * wurde, gilt sie als unverändert — und die nächste Anfrage liest
     * weiterhin die Datei OHNE den neuen Eintrag. Gemessen in der
     * Browserprobe (S2/AP7): Die Seite meldete „steht jetzt in config.php",
     * und der unmittelbar folgende Aufruf zeigte wieder „Serverschlüssel
     * fehlt". */
    if (function_exists('opcache_invalidate')) { @opcache_invalidate($pfad, true); }
    /* Der gelesene Wert liegt in einer `static` — ohne diese Zeile gaeben
     * serverschluessel() und kdf_anteil() im selben Aufruf noch den alten
     * Stand zurueck, und die Seite zeigte nach dem Anlegen weiter den
     * Hinweis, dass nichts da ist.
     *
     * BIS WEB 20.26.3 WURDE HIER ZUSAETZLICH DIE GLOBALE `$CFG` VON HAND
     * NACHGEFUEHRT (`unset` bzw. Zuweisung). Das entfaellt mit Schritt 15
     * AP2: `konfig_verwerfen()` wirft das Gemerkte weg, und die naechste
     * Abfrage liest die Datei, die eben geschrieben wurde. Damit kann der
     * Speicher nicht mehr von der Datei abweichen — bisher haette eine
     * vergessene der beiden Zeilen genau das erzeugt, und zwar still. */
    config_gemerktes_verwerfen();
    return [true, (string)$hex];
}

/* ===========================================================================
 * DIE VIER HANDGRIFFE AM SERVER-ANTEIL (S10, E-S10-10 und E-S10-11)
 *
 * Sie stehen hier und nicht in `betrieb_server.php`, weil jeder von ihnen aus
 * zwei Schritten besteht, die zusammengehören: `config.php` schreiben UND die
 * Marke in `app_state` nachziehen. Getrennt geschrieben wären sie vier Stellen,
 * an denen der eine Schritt ohne den anderen laufen kann — und eine Marke, die
 * nicht zum Wert passt, ist genau der Zustand „abweichend", gegen den S10
 * gebaut ist.
 * ======================================================================== */

/**
 * Anlegen — einen frischen Anteil würfeln und eintragen.
 *
 * Nur, wenn noch keiner dasteht. Ein vorhandener Anteil wird über *Wechseln*
 * (Rotation) ersetzt, nicht über *Anlegen*: Der Unterschied ist, dass die
 * Rotation den alten Wert stehen lässt, damit die Hüllen noch zu öffnen sind.
 */
function anteil_anlegen(): array
{
    if (kdf_anteil() !== null) {
        return [false, 'Es steht bereits ein Server-Anteil in config.php.'];
    }
    [$ok, $was] = config_eintrag_schreiben('kdf_anteil', kdf_anteil_neu());
    if (!$ok) { return [false, $was]; }
    /* Die Marke setzt `anteil_zustand()` beim ersten Lesen nach (E-S10-U-02) —
     * hier wird sie nur ausgelöst, damit die Karte gleich den Endzustand
     * zeigt und nicht erst nach dem nächsten Aufruf. */
    anteil_zustand(true);
    return [true, (string)schluessel_kennung($was)];
}

/**
 * Rotation — neuen Anteil würfeln, den bisherigen als `kdf_anteil_alt` behalten.
 *
 * WARUM DER ALTE STEHEN BLEIBT. Die Hüllen aller Konten sind mit ihm gebaut.
 * Würde er beim Wechsel verschwinden, wäre jedes Konto ausgesperrt, bis es sich
 * einmal angemeldet hat — und anmelden kann es sich nur, wenn es die Hülle
 * öffnen kann. Der alte Wert wird deshalb weiter ausgeliefert, bis die
 * Statuszeile sagt, dass niemand mehr auf ihm steht (E-S10-11).
 *
 * DIE REIHENFOLGE IST NICHT BELIEBIG: erst den alten Wert sichern, dann den
 * neuen setzen. Andersherum wäre der alte für die Dauer eines Fehlschlags
 * verloren — und mit ihm jede Hülle.
 */
function anteil_wechseln(): array
{
    $jetzt = kdf_anteil();
    if ($jetzt === null) {
        return [false, 'Es steht kein Server-Anteil in config.php, der zu '
                     . 'wechseln wäre.'];
    }
    if (kdf_anteil_alt() !== null) {
        return [false, 'Es läuft bereits eine Rotation. Erst den alten Anteil '
                     . 'entfernen, wenn kein Konto mehr auf ihm steht.'];
    }
    $altHex = strtolower((string)konfig('kdf_anteil', ''));

    [$ok, $was] = config_eintrag_schreiben('kdf_anteil_alt', $altHex);
    if (!$ok) { return [false, $was]; }

    [$ok2, $was2] = config_eintrag_schreiben('kdf_anteil', kdf_anteil_neu(), true);
    if (!$ok2) {
        /* Zurücknehmen, damit kein halber Zustand stehen bleibt: ein
         * `kdf_anteil_alt` neben einem unveränderten `kdf_anteil` wäre eine
         * Rotation, die nie stattgefunden hat — und die Statuszeile zählte ab
         * sofort gegen eine Kennung, die niemand trägt. */
        config_eintrag_schreiben('kdf_anteil_alt', null);
        return [false, $was2];
    }
    anteil_zustand(true);     // schreibt die Marke auf die neue Kennung
    return [true, (string)schluessel_kennung($was2)];
}

/**
 * Den alten Anteil entfernen — erst, wenn niemand mehr auf ihm steht.
 *
 * Die Zählung macht `anteil_zaehlung()`; diese Funktion verlässt sich nicht
 * darauf, dass die Oberfläche den Knopf verbirgt. Ein Knopf, der nur versteckt
 * ist, wird irgendwann doch gedrückt — über die Zurück-Taste, ein zweites
 * Fenster oder ein Lesezeichen.
 */
function anteil_alt_entfernen(): array
{
    if (kdf_anteil_alt() === null) {
        return [false, 'Es steht kein alter Server-Anteil in config.php.'];
    }
    $z = anteil_zaehlung();
    if ($z['alt'] > 0) {
        return [false, $z['alt'] . ' Konto/Konten tragen noch eine Hülle auf dem '
                     . 'alten Anteil. Würde er jetzt entfernt, kämen sie nicht '
                     . 'mehr an ihre geschützten Angaben. Es wurde nichts geändert.'];
    }
    return config_eintrag_schreiben('kdf_anteil_alt', null);
}

/**
 * Neuanfang — ein frischer Anteil, obwohl der alte verloren ist.
 *
 * DER FALL, FÜR DEN ES DAS GIBT: `config.php` ist weg und beide Ausdrucke des
 * Schlüsselblatts auch. Dann lässt sich keine `edka1:`-Hülle mehr öffnen, und
 * es gibt nichts nachzutragen.
 *
 * WAS DAS KOSTET, UND WAS NICHT. Jedes Konto mit `edka1:`-Hülle muss sein
 * Passwort über den **Wiederherstellungsschlüssel** neu setzen — der hängt
 * nicht am Anteil (E-S10-04). Es ist also ein Vorgang für alle und **kein
 * Datenverlust**. Genau deshalb steht der Knopf überhaupt da: Ohne ihn bliebe
 * eine Installation im Zustand „abweichend" stehen, in dem niemand mehr
 * hereinkommt und niemand etwas tun kann.
 *
 * Ein etwaiger alter Anteil geht mit: Er öffnet nach einem Neuanfang nichts
 * mehr, was der neue nicht auch nicht öffnet, und stünde nur im Weg.
 */
function anteil_neuanfang(): array
{
    /* NUR AUS DER LAGE „abweichend" HERAUS. Der Knopf steht auch nur dort —
     * aber ein Knopf, der bloss verborgen ist, wird trotzdem gedrückt: über
     * die Zurück-Taste, ein zweites Fenster oder ein F5 nach dem Absenden.
     * Ohne diesen Riegel erzeugte jedes Neuladen einen WEITEREN Anteil, und
     * jeder davon wäre wieder der falsche für die Hüllen, die inzwischen
     * gebaut wurden. Der Riegel gehört in die Funktion, nicht in die Seite. */
    $stand = anteil_zustand()['stand'];
    if ($stand !== 'abweichend') {
        return [false, 'Ein Neuanfang ist nur nötig, wenn der Server-Anteil '
                     . 'nicht zu den vorhandenen Hüllen passt. Der Zustand ist '
                     . 'derzeit „' . $stand . '" — es wurde nichts geändert.'];
    }
    if (kdf_anteil_alt() !== null) {
        [$ok, $was] = config_eintrag_schreiben('kdf_anteil_alt', null);
        if (!$ok) { return [false, $was]; }
    }
    $neu = kdf_anteil_neu();
    [$ok, $was] = config_eintrag_schreiben('kdf_anteil', $neu,
                                           kdf_anteil() !== null);
    if (!$ok) { return [false, $was]; }
    /* DIE MARKE WIRD HIER AUSDRÜCKLICH ÜBERSCHRIEBEN und nicht nachgetragen.
     * Beim Neuanfang ist genau das der Vorgang: Die Installation erklärt, dass
     * ab jetzt mit diesem Wert gearbeitet wird — auch wenn die vorhandenen
     * Hüllen zu einem anderen gehören. Ohne diese Zeile bliebe der Zustand
     * „abweichend" bestehen, und der Neuanfang täte nichts. */
    schluessel_marke_setzen('kdf_anteil_kennung', (string)schluessel_kennung($neu));
    anteil_zustand(true);
    return [true, (string)schluessel_kennung($neu)];
}

/**
 * Wie viele Konten stehen auf welcher Hüllenfassung? (E-S10-11)
 *
 * GEZÄHLT WIRD AM PRÄFIX, IN SQL, OHNE EINE HÜLLE ZU ÖFFNEN — der Server
 * könnte es gar nicht. Genau dafür steht die Kennung vorn: Sie macht eine
 * Auskunft möglich, für die man sonst den Schlüssel bräuchte.
 *
 * Das Demo-Konto zählt **nicht mit** (E-P1-19): Es bleibt bauartbedingt auf
 * `edk1:`, und es als „noch offen" zu führen hieße, eine Zahl zu zeigen, die
 * nie auf null geht. Denselben Fehler hat die Zeile „Schlüsselableitung"
 * schon einmal gemacht (Backlog Nr. 155).
 *
 * Konten ohne Hülle (`pat_wrap_pw IS NULL` — eingeladen, aber nie
 * eingerichtet) zählen ebenfalls nicht: Sie haben nichts umzustellen.
 */
function anteil_zaehlung(): array
{
    $z = anteil_zustand();
    $aus = ['neu' => 0, 'alt' => 0, 'ohne' => 0, 'demo' => 0];
    try {
        require_once __DIR__ . '/demo_lib.php';
        $demoId = demo_id();
        $st = db()->prepare(
            'SELECT SUBSTRING(pat_wrap_pw, 1, 15) AS p, COUNT(*) AS n
               FROM users
              WHERE pat_wrap_pw IS NOT NULL AND (? IS NULL OR id <> ?)
              GROUP BY p');
        $st->execute([$demoId, $demoId ?? 0]);
        foreach ($st as $r) {
            $kennung = huelle_anteil_kennung((string)$r['p']);
            $n = (int)$r['n'];
            if ($kennung === null)                    { $aus['ohne'] += $n; }
            elseif ($kennung === $z['kennung'])       { $aus['neu']  += $n; }
            elseif ($kennung === $z['kennung_alt'])   { $aus['alt']  += $n; }
            else                                      { $aus['alt']  += $n; }
        }
        if ($demoId !== null) {
            $st2 = db()->prepare('SELECT COUNT(*) FROM users
                                   WHERE id = ? AND pat_wrap_pw IS NOT NULL');
            $st2->execute([$demoId]);
            $aus['demo'] = (int)$st2->fetchColumn();
        }
    } catch (Throwable $ex) {
        /* Eine Zählung, die nicht geht, ist keine Null — sie ist keine
         * Zählung. Der Aufrufer sieht das an `fehler`. */
        $aus['fehler'] = $ex->getMessage();
    }
    return $aus;
}

/**
 * Einen Wert vom Schlüsselblatt nachtragen (E-S10-10).
 *
 * DER UNTERSCHIED ZU „ANLEGEN": Hier ist der Wert vorgegeben, und er wird
 * **gegen die Kennung geprüft, bevor irgendetwas geschrieben wird**. Das ist
 * der ganze Zweck dieses Wegs — ein falsch abgeschriebener Anteil, der
 * stillschweigend landet, macht aus einer behebbaren Lage eine unbehebbare:
 * Danach steht in `config.php` ein Wert, der zu nichts passt, und der richtige
 * ist überschrieben.
 *
 * GEPRÜFT WIRD GEGEN `app_state`, nicht gegen den vorhandenen Eintrag. Der
 * vorhandene ist ja gerade der, von dem man annimmt, dass er falsch ist.
 */
function anteil_nachtragen(string $roh, bool $ersetzen): array
{
    $hex = schluessel_eingabe_normalisieren($roh);
    if ($hex === null) {
        return [false, 'Das sind keine 64 Hexzeichen. Leerzeichen und '
                     . 'Bindestriche dürfen drinstehen, andere Zeichen nicht.'];
    }
    $soll = schluessel_marke_lesen('kdf_anteil_kennung');
    if ($soll === null) {
        return [false, 'Diese Installation hat noch nie mit einem Server-Anteil '
                     . 'gearbeitet — es gibt keine Kennung, gegen die sich der '
                     . 'Wert prüfen ließe. Bitte „Server-Anteil anlegen" '
                     . 'benutzen.'];
    }
    $ist = (string)schluessel_kennung($hex);
    if ($ist !== $soll) {
        return [false, 'Die Kennung dieses Werts ist ' . $ist . ', erwartet ist '
                     . $soll . ' — das ist nicht der Wert, mit dem die Hüllen '
                     . 'gebaut wurden. Es wurde nichts geändert.'];
    }
    [$ok, $was] = config_eintrag_schreiben('kdf_anteil', $hex, $ersetzen);
    if (!$ok) { return [false, $was]; }
    anteil_zustand(true);
    return [true, $ist];
}

/**
 * Denselben Weg für den Serverschlüssel.
 *
 * Er hat keine Rotation (E-S10-11: sie hieße, jedes versiegelte Feld und jedes
 * Paket umzusiegeln) — aber denselben Ernstfall: Nach einem Wiederanlauf steht
 * der falsche Wert in `config.php`, und die Meldung „mit einem anderen
 * Serverschlüssel gespeichert" sagt nicht, welcher der richtige wäre.
 *
 * Die Marke `server_key_kennung` entsteht bei der ersten Anzeige nach S10 —
 * dieselbe Mechanik wie beim Anteil.
 */
function serverschluessel_nachtragen(string $roh, bool $ersetzen): array
{
    $hex = schluessel_eingabe_normalisieren($roh);
    if ($hex === null) {
        return [false, 'Das sind keine 64 Hexzeichen. Leerzeichen und '
                     . 'Bindestriche dürfen drinstehen, andere Zeichen nicht.'];
    }
    $soll = schluessel_marke_lesen('server_key_kennung');
    if ($soll === null) {
        return [false, 'Diese Installation hat noch nie mit einem '
                     . 'Serverschlüssel versiegelt — es gibt keine Kennung, '
                     . 'gegen die sich der Wert prüfen ließe.'];
    }
    $ist = (string)schluessel_kennung($hex);
    if ($ist !== $soll) {
        return [false, 'Die Kennung dieses Werts ist ' . $ist . ', erwartet ist '
                     . $soll . ' — das ist nicht der Schlüssel, mit dem '
                     . 'versiegelt wurde. Es wurde nichts geändert.'];
    }
    [$ok, $was] = config_eintrag_schreiben('server_key', $hex, $ersetzen);
    return $ok ? [true, $ist] : [false, $was];
}

/**
 * Der Zustand des Serverschlüssels — dieselben vier Lagen wie beim Anteil.
 *
 * Er kennt keine Rotation, also gibt es nur drei: `fehlt`, `bereit`,
 * `abweichend`. Die Marke wird nachgetragen, sobald ein gültiger Schlüssel
 * dasteht und noch keine Marke existiert (E-S10-U-02).
 */
function serverschluessel_zustand(bool $frisch = false): array
{
    static $merk = null;
    if ($frisch) { $merk = null; }
    if ($merk !== null) { return $merk; }

    $kennung  = serverschluessel_kennung();
    $erwartet = schluessel_marke_lesen('server_key_kennung');

    if ($kennung === null) {
        return $merk = ['stand' => $erwartet === null ? 'fehlt' : 'abweichend',
                        'kennung' => null, 'erwartet' => $erwartet];
    }
    if ($erwartet === null) {
        schluessel_marke_setzen('server_key_kennung', $kennung);
        $erwartet = $kennung;
    }
    return $merk = ['stand' => $erwartet === $kennung ? 'bereit' : 'abweichend',
                    'kennung' => $kennung, 'erwartet' => $erwartet];
}

/**
 * Einen frischen Serverschlüssel erzeugen und eintragen.
 *
 * Seit S10 nur noch die halbe Funktion: Das Würfeln bleibt hier, das
 * Schreiben macht `config_eintrag_schreiben()`. Ein zweiter, wortgleicher
 * Schreibweg daneben wäre die Stelle, an der der eine irgendwann eine
 * Gegenprobe bekommt und der andere nicht.
 *
 * ERSETZT NIE. Ein neuer Serverschlüssel macht jedes versiegelte Feld und
 * jedes versiegelte Paket unlesbar — das ist keine Sache eines Knopfes.
 * `$ersetzen` bleibt deshalb aus, und die Funktion bricht ab, wenn schon
 * einer dasteht.
 */
function serverschluessel_eintragen(): array
{
    if (serverschluessel_da()) {
        return [false, 'Es steht bereits ein Serverschlüssel in config.php.'];
    }
    return config_eintrag_schreiben('server_key', serverschluessel_neu());
}
