<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_lib.php';
/* Fuer den Huellen-Riegel in `demo_fixture_laden()` (S10). Die Datei liegt
 * unter `db.php` und greift nicht hierher zurueck — die Richtung stimmt. */
require_once __DIR__ . '/serverkrypto_lib.php';

/**
 * Demo-Konto (Baustein B14, Phase P1).
 *
 * WOFUER ES DAS GIBT
 * Ein Konto, in dem sich die Anwendung ohne Anmeldehuerde ausprobieren
 * laesst: Zugangsdaten oeffentlich, Daten frei erfunden, Aenderungen
 * erwuenscht — und 30 Minuten nach der ersten Aenderung wieder auf den
 * Ausgangsstand (seit Web 21.2.0; bis dahin alle 30 Minuten, auch ohne
 * Aenderung — Abschnitt DIE AENDERUNGSMARKE unten).
 *
 * DIE AUSNAHME, DIE HIER GEMACHT WIRD, UND IHRE GRENZE
 * Das Projekt verspricht Ende-zu-Ende-Verschluesselung: Der Server sieht die
 * geschuetzten Angaben nie im Klartext, und das Schluesselmaterial haengt am
 * Passwort. Fuer DIESES eine Konto gilt das nicht — sein Schluesselmaterial
 * liegt in der Fixture auf dem Server, damit ein Reset die Chiffretexte
 * wieder lesbar macht.
 *
 * Das ist eine bewusste, eng gezogene Ausnahme (E-P1-09) und nur deshalb
 * vertretbar, weil:
 *   - das Konto ausschliesslich erfundene Daten traegt,
 *   - es die Rolle `user` hat und niemals `admin`,
 *   - seine Zugangsdaten ohnehin oeffentlich sind — es gibt nichts zu
 *     schuetzen, was nicht schon offen laege,
 *   - jede Funktion hier ausschliesslich auf dem in `app_state` vermerkten
 *     Konto arbeitet und auf keinem anderen.
 *
 * Der letzte Punkt ist der wichtigste und wird an jeder Stelle erzwungen,
 * nicht nur zugesichert: `demo_id()` ist die einzige Quelle der Kontokennung,
 * und keine Funktion nimmt eine von aussen entgegen.
 *
 * WARUM DER RESET DAS SCHLUESSELMATERIAL MITSCHREIBT
 * Damit selbst eine unerwartet gelungene Aenderung der Konto-Identitaet
 * folgenlos bleibt. Die Sperren in E-P1-19 sind die erste Linie; dass der
 * Reset Passwort, Salz und Schluesselhuellen ohnehin ueberschreibt, ist die
 * zweite. Zwei Linien, weil die erste an einem einzigen vergessenen Endpunkt
 * haengen koennte.
 *
 * WARUM KEIN ZWEITER EINSPIELWEG
 * Der Bestand wird ueber `edbak_restore()` eingespielt — dieselbe Routine wie
 * bei der Wiederherstellung eines Backups, mit derselben Pruefung. Ein
 * eigener Weg haette eigene Fehler, und ausgerechnet der Weg, der am
 * haeufigsten laeuft, waere der ungeprueftere. Der Chiffretext wandert dabei
 * UNVERAENDERT durch: `edbak_restore()` nimmt `pat_blob` als Spalte entgegen,
 * ein Browser ist nicht beteiligt, und weil das Schluesselmaterial aus
 * derselben Fixture kommt, passt beides zusammen.
 */

/** Frist nach der ersten Aenderung; danach setzt die naechste Anfrage des
 *  Demo-Kontos zurueck. */
const DEMO_RESET_SEKUNDEN = 1800;

/** Pflichtreset: so lange nach dem letzten Reset auch OHNE Aenderung
 *  (E-R4-10). Das Netz fuer eine Aenderung, die an keiner Setzstelle
 *  vorbeikam, und fuer eine neue Fixture nach einem Deploy. */
const DEMO_PFLICHT_SEKUNDEN = 86400;

/** Schluessel in `app_state`. */
const DEMO_K_USER      = 'demo_user_id';
const DEMO_K_RESET     = 'demo_letzter_reset';
const DEMO_K_GEAENDERT = 'demo_geaendert';

/** Pfad der Fixture. Liegt unter server/, weil der Produktivserver sie
 *  braucht — alles Uebrige der Phase P1 liegt unter tools/ (E-P1-07).
 *  GEPACKT, weil sie roh rund 11 MB misst (im Wesentlichen Spurpunkte) und
 *  bei jedem Deploy ueber FTPS mitgeht. */
/* ANZEIGENAME DES DEMO-KONTOS (S3/AP10). Er kommt NICHT aus der Fixture:
 * Dort steht der Name des Referenzkontos, aus dem sie erzeugt wurde, und in
 * der NutzerInnen-Liste las sich das wie ein gewoehnliches Konto. „Demo
 * NutzerIn" sagt beim Ueberfliegen, was es ist. Gesetzt wird er beim Anlegen
 * UND beim Zuruecksetzen — sonst holte der naechste Reset den Fixture-Namen
 * zurueck. */
const DEMO_NAME = 'Demo NutzerIn';

function demo_fixture_pfad(): string { return __DIR__ . '/demo/fixture.json.gz'; }

function demo_fixture_vorhanden(): bool { return is_file(demo_fixture_pfad()); }

/**
 * Fixture lesen und auf Brauchbarkeit pruefen.
 *
 * Die Pruefung ist knapp, aber nicht keine: Eine unvollstaendige Fixture
 * wuerde ein Konto ohne Schluesselmaterial anlegen — und das faellt erst auf,
 * wenn jemand sich anmeldet und nichts lesen kann.
 */
function demo_fixture_laden(): array
{
    $pfad = demo_fixture_pfad();
    if (!is_file($pfad)) {
        throw new RuntimeException('Keine Demo-Fixture unter server/demo/fixture.json.');
    }
    $roh = file_get_contents($pfad);
    if ($roh === false) {
        throw new RuntimeException('server/demo/fixture.json.gz ist nicht lesbar.');
    }
    /* Auch ungepackt annehmen: Wer die Datei zum Nachsehen entpackt und so
       ablegt, soll keinen Fehler bekommen, den er nicht versteht. */
    if (substr($roh, 0, 2) === "\x1f\x8b") {
        $entpackt = @gzdecode($roh);
        if ($entpackt === false) {
            throw new RuntimeException('server/demo/fixture.json.gz laesst sich nicht entpacken.');
        }
        $roh = $entpackt;
    }
    $fx = json_decode((string)$roh, true);
    if (!is_array($fx) || ($fx['format'] ?? '') !== 'einsatzdoku-demo-fixture') {
        throw new RuntimeException('server/demo/fixture.json hat nicht das erwartete Format.');
    }
    foreach (['konto', 'daten'] as $pflicht) {
        if (!is_array($fx[$pflicht] ?? null)) {
            throw new RuntimeException("Fixture unvollstaendig: '$pflicht' fehlt.");
        }
    }
    foreach (['email', 'password_hash', 'kdf_salt', 'kdf_iter', 'pat_wrap_pw'] as $pflicht) {
        if (($fx['konto'][$pflicht] ?? null) === null) {
            throw new RuntimeException("Fixture unvollstaendig: konto.$pflicht fehlt.");
        }
    }
    /* DIE RUNDENZAHL MUSS BEDIENBAR SEIN (Backlog Nr. 155).
     *
     * Die Fixture bringt `kdf_iter` mit, und der Reset schreibt den Wert
     * unveraendert ins Konto. Steht dort eine Zahl, die diese Fassung gar
     * nicht mehr anbietet, kann sich das Demo-Konto nach dem Reset nicht mehr
     * anmelden — und zwar still, denn der Reset selbst gelingt.
     *
     * Geprueft wird gegen KDF_ITER_LISTE und nicht gegen KDF_ITER_ZIEL: Eine
     * aeltere, aber noch bediente Fixture soll weiter laufen. Abgewiesen wird
     * nur der Fall, der niemanden mehr hereinlaesst — genau der entsteht,
     * wenn jemand den Altwert aus der Liste streicht, bevor die Fixture neu
     * gebaut ist. */
    if (!in_array((int)$fx['konto']['kdf_iter'], KDF_ITER_LISTE, true)) {
        throw new RuntimeException('Die Fixture traegt die Rundenzahl '
            . (int)$fx['konto']['kdf_iter'] . '; diese Fassung bietet nur '
            . implode(', ', array_map('strval', KDF_ITER_LISTE))
            . ' an — das Demo-Konto koennte sich nicht anmelden.');
    }

    /* DIE HUELLEN MUESSEN `edk1:` SEIN (S10, zweiter Riegel zu E-S10-15).
     *
     * DERSELBE GEDANKE WIE EINE ZEILE HOEHER, mit einem anderen Geheimnis.
     * Die Rundenzahl entscheidet, ob sich das Demo-Konto anmelden kann; die
     * Huellenfassung entscheidet, ob es danach etwas sieht.
     *
     * Seit S10 haengt der Datenschluessel am Server-Anteil aus `config.php`,
     * und der ist je Installation ein anderer (`install.php` wuerfelt ihn).
     * Eine Huelle mit Anteil (`edka1:<kennung>:`) laesst sich nur dort
     * oeffnen, wo dieser Anteil steht. Die Fixture reist aber: Sie entsteht
     * auf der Referenzmaschine und wird beim Deploy auf den Produktivserver
     * gelegt.
     *
     * Und das Demo-Konto bekommt BAUARTBEDINGT gar keinen Anteil
     * (`auth_guard.php`, E-P1-19/E-S10-06): `KONTO_ANTEILE` ist fuer es
     * `null`, `ANTEIL_STAND` ist `'demo'`. `EdCrypto.datenschluessel()`
     * wirft bei einer `edka1:`-Huelle ohne Anteil ausdruecklich, statt auf
     * die PBKDF2-Haelfte zurueckzufallen — der Rueckfall ergaebe einen
     * Schluessel, der nicht passt, und saehe aus wie ein falsches Passwort.
     *
     * OHNE DIESEN RIEGEL WAERE DER RESET STILL ERFOLGREICH. Das Konto kaeme
     * herein (der bcrypt-Hash stimmt ja), und erst das Entsperren scheiterte
     * — auf der oeffentlichen Demo, bei jedem Reset aufs Neue. Genau dieselbe
     * Begruendung wie beim Riegel auf die Rundenzahl (Backlog Nr. 155):
     * „Ohne den zweiten Riegel waere ein Reset still erfolgreich und niemand
     * kaeme mehr herein."
     *
     * DER ERSTE RIEGEL STEHT IM ERZEUGER (`tools/referenzdatensatz/fixture/
     * erzeugen.php`, S10/AP5) und verhindert, dass eine solche Fixture
     * ueberhaupt entsteht. Dieser hier verhindert, dass sie eingespielt wird.
     * Zwei Riegel, weil der erste nur greift, wo das Werkzeug laeuft — und
     * die Datei kommt auf dem Produktivserver an, nicht das Werkzeug.
     *
     * GEPRUEFT WIRD MIT DER GEMEINSAMEN PRUEFSCHICHT, nicht mit einem eigenen
     * Ausdruck: `huelle_pw_pruefen($wrap, istDemo: true)` setzt den erwarteten
     * Anteil auf `null` und weist damit jede Huelle ab, die eine Kennung
     * nennt; `huelle_rc_pruefen()` haelt den Wiederherstellungsschluessel vom
     * Anteil fern (E-S10-04). Ein zweiter Ausdruck an dieser Stelle liesse
     * frueher oder spaeter die falsche Huelle durch. */
    $grundPw = huelle_pw_pruefen((string)$fx['konto']['pat_wrap_pw'], true);
    if ($grundPw !== null) {
        throw new RuntimeException('Die Schluesselhuelle der Fixture ist fuer '
            . 'das Demo-Konto unbrauchbar: ' . $grundPw
            . ' Das Demo-Konto bekommt keinen Server-Anteil; seine Huelle muss '
            . '`edk1:` tragen. Die Fixture neu erzeugen — '
            . 'tools/referenzdatensatz/fixture/erzeugen.php haelt an, wenn sie '
            . 'es nicht tut.');
    }
    $rc = $fx['konto']['pat_wrap_rc'] ?? null;
    $grundRc = huelle_rc_pruefen($rc === null ? null : (string)$rc);
    if ($grundRc !== null) {
        throw new RuntimeException('Die Wiederherstellungs-Huelle der Fixture '
            . 'ist unbrauchbar: ' . $grundRc);
    }

    return $fx;
}

/** Kennung des Demo-Kontos, oder null. EINZIGE Quelle dieser Kennung. */
function demo_id(): ?int
{
    try {
        $v = app_state_lesen(DEMO_K_USER);
        if ($v === null || (int)$v <= 0) { return null; }
        // Gegenprobe: Steht das Konto ueberhaupt noch? Eine verwaiste Kennung
        // waere schlimmer als keine — sie zeigte auf eine spaeter neu
        // vergebene ID und damit auf ein fremdes Konto.
        $q = db()->prepare('SELECT id FROM users WHERE id = ?');
        $q->execute([(int)$v]);
        return $q->fetchColumn() === false ? null : (int)$v;
    } catch (Throwable $ex) {
        return null;   // app_state fehlt (Migration noch nicht gelaufen)
    }
}

/**
 * Gehoert diese E-Mail-Adresse dem Demo-Konto?
 *
 * Fuer die Anmeldeseite: Dort ist zum Zeitpunkt der Mengenbremse noch nicht
 * nachgeschlagen, WER sich anmeldet — und das soll so bleiben, damit der
 * Zweig „Adresse unbekannt" nicht schneller ist als der andere.
 *
 * Eine Abfrage statt eines Vergleichs mit der Fixture: Die Adresse steht in
 * der Kontozeile, und nur die zaehlt. Waere sie dort eine andere als in der
 * Fixture, griffe die Bremse sonst am falschen Konto.
 */
function demo_ist_demo_adresse(string $email): bool
{
    $id = demo_id();
    if ($id === null || $email === '') { return false; }
    $st = db()->prepare('SELECT 1 FROM users WHERE id = ? AND LOWER(email) = LOWER(?)');
    $st->execute([$id, $email]);
    return $st->fetchColumn() !== false;
}

/** Ist DIESES Konto das Demo-Konto? */
function demo_ist_demo(?int $userId): bool
{
    if ($userId === null || $userId <= 0) { return false; }
    return demo_id() === $userId;
}

/** Zeitpunkt des letzten Resets als Unix-Sekunden, oder 0. */
function demo_letzter_reset(): int
{
    try {
        return (int)(app_state_lesen(DEMO_K_RESET) ?? 0);
    } catch (Throwable $ex) {
        return 0;
    }
}

function demo_reset_marke_setzen(?int $wann = null): void
{
    app_state_setzen(DEMO_K_RESET, (string)($wann ?? time()));
}

/* ---- DIE AENDERUNGSMARKE (Web 21.2.0, Schritt 17, R4-14, Nr. 76) --------
 *
 * WARUM. Bis Web 21.1.x setzte die erste Anfrage des Demo-Kontos nach 30
 * Minuten zurueck, ob sich etwas geaendert hatte oder nicht. Der Reset
 * laeuft huckepack und kostet rund sechseinhalb Sekunden (gemessen
 * 15.09.2026); die trug jede Besucherin, die nach einer Pause nur NACHSEHEN
 * wollte — fuer einen Bestand, der ohnehin der Ausgangsstand war.
 *
 * WIE. `demo_geaendert` in `app_state` haelt den Zeitpunkt der ERSTEN
 * Aenderung seit dem letzten Reset. Gesetzt wird sie an zwei Stellen, je
 * hinter der Pruefung, die eine fremde Anfrage abweist: bei jedem POST des
 * Demo-Kontos (`auth_guard.php`, nach CSRF) und bei jedem angenommenen
 * Upload eines Demo-Geraets (`ingest.php`, nach dem Commit). Eine POST, die
 * nichts aendert, setzt sie auch — das kostet einen Reset, keinen Schaden;
 * umgekehrt waere eine Aenderung ohne Marke bis zum Pflichtreset sichtbar.
 *
 * AB DER ERSTEN AENDERUNG, NICHT AB DEM LETZTEN RESET (Q-R4-23, E-R4-42).
 * Ab dem letzten Reset gezaehlt, waere nach laengerer Ruhe schon die
 * Umleitung nach dem ersten Speichern faellig — die Aenderung waere weg,
 * bevor die Besucherin sie sieht. Ab der ersten gezaehlt lebt eine
 * Aenderung rund 30 Minuten, wie es der Hinweis verspricht.
 *
 * DER SPAETERE DER BEIDEN ZEITPUNKTE ZAEHLT. Werkzeuge halten den Reset auf,
 * indem sie `demo_letzter_reset` auf jetzt oder in die Zukunft schieben
 * (Pruefstand, `demo_kennzeichnen.php`, Klickprobe). Das bleibt so wirksam:
 * Liegt der letzte Reset nach der Marke, zaehlt er. */

/** Zeitpunkt der ersten Aenderung seit dem letzten Reset, oder 0. */
function demo_geaendert_seit(): int
{
    try {
        return (int)(app_state_lesen(DEMO_K_GEAENDERT) ?? 0);
    } catch (Throwable $ex) {
        return 0;
    }
}

/**
 * Eine Aenderung vermerken. Nur die ERSTE seit dem Reset setzt den
 * Zeitpunkt — `app_state_einmalig()` schreibt nur, wo nichts steht.
 *
 * Scheitert still gegenueber der Anfrage, aber nicht spurlos: Eine fehlende
 * Marke haelt die Aenderung bis zum Pflichtreset stehen, und das soll im
 * Reiter System stehen, nicht nur in der Anlage.
 */
function demo_aenderung_vermerken(): void
{
    try {
        app_state_einmalig(DEMO_K_GEAENDERT, static fn(): string => (string)time());
    } catch (Throwable $ex) {
        system_melden('demo', 'Änderungsmarke nicht gesetzt', $ex);
    }
}

/**
 * Die Marke nach einem Reset vergessen — aber nur, wenn sie nicht juenger
 * ist als sein Beginn. Eine Aenderung, die WAEHREND des Resets einging,
 * gehoert zum neuen Fenster; ihre Marke bleibt stehen.
 */
function demo_aenderung_vergessen(int $beginn): void
{
    $g = demo_geaendert_seit();
    if ($g > 0 && $g <= $beginn) { app_state_loeschen(DEMO_K_GEAENDERT); }
}

/**
 * Ab wann ist der naechste Reset faellig? Unix-Sekunden. REINE RECHNUNG,
 * damit die Probe sie ohne Anlage und ohne Uhr nachrechnen kann
 * (Zweitfaktorprobe, Teil 6b).
 *
 * MIT MARKE GILT NUR IHRE FRIST. Der Pflichtreset ist das Netz fuer den
 * Fall OHNE Marke; mit Marke ist der Reset ohnehin 30 Minuten entfernt.
 * Beides zu nehmen (`min`) hiesse, einer Aenderung kurz vor Ablauf des
 * Tages ihre halbe Stunde zu kuerzen.
 *
 * @param int $letzter   letzter Reset (0 = nie)
 * @param int $geaendert erste Aenderung seither (0 = keine)
 */
function demo_reset_faellig_ab(int $letzter, int $geaendert): int
{
    if ($geaendert <= 0) { return $letzter + DEMO_PFLICHT_SEKUNDEN; }
    return max($geaendert, $letzter) + DEMO_RESET_SEKUNDEN;
}

/** Sekunden bis zum naechsten faelligen Reset (0 = jetzt faellig). */
function demo_reset_in(): int
{
    $rest = demo_reset_faellig_ab(demo_letzter_reset(), demo_geaendert_seit()) - time();
    return $rest > 0 ? $rest : 0;
}

/**
 * Anfragegetriebener Reset — das Muster der vorhandenen Aufraeumjobs (B-13).
 *
 * ZUERST ZURUECKSETZEN, DANN ANTWORTEN. Wer nach laengerer Ruhe kommt, soll
 * den Ausgangsstand sehen und nicht die Hinterlassenschaft der letzten
 * Besucherin. Faellig ist der Reset 30 Minuten nach der ersten Aenderung,
 * ohne Aenderung einen Tag nach dem letzten (`demo_reset_faellig_ab()`);
 * ein Zeitdienst wird nicht vorausgesetzt.
 *
 * Aufzurufen an genau zwei Stellen: bei Web-Anfragen des Demo-Kontos
 * (auth_guard.php) und bei `ingest.php` von einem Demo-Geraet.
 *
 * Scheitert still gegenueber der Anfrage — eine Wartung darf keine Seite
 * kaputtmachen —, aber nicht spurlos: Der Grund landet im Fehlerprotokoll.
 */
function demo_reset_wenn_faellig(): bool
{
    $id = demo_id();
    if ($id === null || demo_reset_in() > 0) { return false; }
    try {
        // Marke ZUERST: verhindert, dass zwei gleichzeitige Anfragen beide
        // zuruecksetzen. Dasselbe Vorgehen wie in run_cleanup_if_due().
        demo_reset_marke_setzen();
        demo_zuruecksetzen();
        return true;
    } catch (Throwable $ex) {
        system_melden('demo', 'Zurücksetzen fehlgeschlagen', $ex);
        return false;
    }
}

/* ------------------------------------------------------------------ Anlegen */

/**
 * Demo-Konto anlegen. Verlangt, dass es noch keines gibt.
 *
 * Die E-Mail-Adresse kommt aus der Fixture — sie gehoert zum
 * Schluesselmaterial: `auth_salt.php` und die Ableitung des Anmeldetokens
 * haengen an ihr, ein anderer Wert machte den gespeicherten Hash wertlos.
 */
function demo_anlegen(): array
{
    $fx = demo_fixture_laden();
    $pdo = db();

    if (demo_id() !== null) {
        throw new RuntimeException('Es gibt bereits ein Demo-Konto. '
            . 'Zum Erneuern „Zurücksetzen" verwenden.');
    }
    $k = $fx['konto'];
    $st = $pdo->prepare('SELECT id FROM users WHERE email = ?');
    $st->execute([(string)$k['email']]);
    if ($st->fetchColumn() !== false) {
        throw new RuntimeException('Ein Konto mit der Adresse ' . (string)$k['email']
            . ' besteht bereits, ist aber nicht als Demo-Konto gekennzeichnet. '
            . 'Bitte zuerst im Adminbereich entfernen.');
    }

    /* Der Rumpf liefert Kennung und Zahlen zurueck; `db_transaktion()` reicht
     * sie unveraendert durch. */
    [$id, $stats] = db_transaktion($pdo, function (PDO $pdo) use ($k, $fx): array {
        $pdo->prepare('INSERT INTO users (email, name, password_hash, kdf_salt, kdf_iter,
                                          pat_wrap_pw, pat_wrap_rc, pat_key_check,
                                          role, account_key)
                       VALUES (?,?,?,?,?,?,?,?,?,?)')
            ->execute([
                (string)$k['email'], DEMO_NAME, (string)$k['password_hash'],
                $k['kdf_salt'] ?? null, (int)($k['kdf_iter'] ?? 0),
                $k['pat_wrap_pw'] ?? null, $k['pat_wrap_rc'] ?? null,
                $k['pat_key_check'] ?? null,
                /* NIEMALS eine Rolle mit Rechten (E-P1-09). Seit Web 15.0.0
                 * gibt es drei Rollen (R75), seit Web 20.41.0 vier (Support,
                 * R38) — der Satz gilt fuer alle mit Rechten unveraendert. Der Reset unten schreibt denselben Wert
                 * zurueck, damit ein waehrend der Sitzung erhoehtes Konto
                 * spaetestens nach dreissig Minuten wieder eine NutzerIn ist. */
                'user',
                $k['account_key'] ?? null,
            ]);
        $id = (int)$pdo->lastInsertId();
        /* `app_state_setzen()` nimmt `db()` — dieselbe statische Verbindung
         * wie `$pdo` hier, also DIESELBE offene Transaktion (E-ZE-17). */
        app_state_setzen(DEMO_K_USER, (string)$id);
        return [$id, demo_bestand_einspielen($pdo, $id, $fx)];
    });
    demo_reset_marke_setzen();
    /* Eine Marke aus einem frueheren Demo-Konto gilt nicht fuer dieses. */
    demo_aenderung_vergessen(time());
    return ['user_id' => $id] + $stats;
}

/* ------------------------------------------------------------- Zuruecksetzen */

/**
 * Den Zweitfaktor des Demo-Kontos leeren — ein Schritt von
 * `demo_zuruecksetzen()` (P5c/AP5, E-P5c-54).
 *
 * WARUM ES IHN GIBT: Einschalten ist im Demo-Konto gesperrt; steht trotzdem
 * einer da — ein Konto, das erst nachtraeglich zum Demo-Konto wurde, oder ein
 * handgebauter POST an der Sperre vorbei —, sperrte er die naechste
 * Besucherin aus. Vorabfrage auf die Spalte: Vor `update.php` gibt es sie
 * nicht, und der Reset laeuft auch dann.
 *
 * WARUM EINE EIGENE FUNKTION (F-P5c-117): Die Zweitfaktorprobe soll diesen
 * Schritt messen, ohne den ganzen Reset zu fahren. Der spielt den
 * Demo-Bestand neu ein, die Einsaetze bekommen neue Nummern — und die
 * GPX-Probe, die im Pruefstand danach laeuft, fand ihre Referenz nicht mehr
 * (204 von 204 ohne Gegenstueck).
 */
function demo_zweitfaktor_leeren(PDO $pdo, int $id): void
{
    if (db_hat_spalte($pdo, 'users', 'totp_seit')) {
        $pdo->prepare('UPDATE users SET totp_geheimnis = NULL, totp_seit = NULL,
                              totp_schritt = NULL WHERE id = ?')->execute([$id]);
        $pdo->prepare('DELETE FROM totp_codes WHERE user_id = ?')->execute([$id]);
    }
    /* DAS PAAR DES RÜCKWEGS GEHÖRT MIT DAZU (Konzept RW, RW-02, E-RW-15):
     * Das Demo-Konto bekommt keins — der Endpunkt weist es ab, und die Seite
     * fragt gar nicht erst. Steht trotzdem eins da (ein Konto, das erst
     * nachträglich zum Demo-Konto wurde), räumt es der Reset ab, an
     * derselben Stelle wie den Zweitfaktor. EIGENE VORABFRAGE: Die Spalten
     * kommen mit einer anderen Migration, und die eine kann fehlen, wo die
     * andere schon gelaufen ist. */
    if (db_hat_spalte($pdo, 'users', 'rw_seit')) {
        $pdo->prepare('UPDATE users SET rw_oeffentlich = NULL, rw_privat = NULL,
                              rw_seit = NULL WHERE id = ?')->execute([$id]);
    }
    /* DIE GEMERKTEN GERAETE (Schritt 18, SR-02, E-SR-07) — dieselbe
     * Ueberlegung: Das Demo-Konto hat keinen Zweitfaktor, also nichts zu
     * merken; steht trotzdem etwas da, raeumt der Reset es ab. Eigene
     * Vorabfrage, weil die Tabelle mit einer eigenen Migration kommt. */
    if (db_hat_tabelle($pdo, 'vertraute_geraete')) {
        $pdo->prepare('DELETE FROM vertraute_geraete WHERE user_id = ?')->execute([$id]);
    }
    /* DIE PASSKEYS (SR-09, E-SR-29) — ein Verfahren desselben Faktors, also
     * dieselbe Regel: Der Endpunkt nimmt vom Demo-Konto keinen an; steht
     * trotzdem einer da, raeumt der Reset ihn ab. */
    if (db_hat_tabelle($pdo, 'passkeys')) {
        $pdo->prepare('DELETE FROM passkeys WHERE user_id = ?')->execute([$id]);
    }
}

/**
 * Demo-Konto auf den Ausgangsstand bringen.
 *
 * Loescht ALLES, was am Konto haengt — auch, was Besucher angelegt haben:
 * Geraete, Kopplungssitzungen, Papierkorb, Sperrlisteneintraege. Und spielt
 * anschliessend die Fixture erneut ein, EINSCHLIESSLICH Konto- und
 * Schluesselmaterial.
 *
 * Wirkt ausschliesslich auf das in `app_state` vermerkte Konto. Die Kennung
 * kommt aus `demo_id()` und wird nicht von aussen entgegengenommen — die
 * Funktion kann kein anderes Konto treffen, auch nicht bei falschem Aufruf.
 */
function demo_zuruecksetzen(): array
{
    $id = demo_id();
    if ($id === null) {
        throw new RuntimeException('Kein Demo-Konto vermerkt — nichts zurückzusetzen.');
    }
    $fx = demo_fixture_laden();
    $pdo = db();
    $beginn = time();

    $stats = db_transaktion($pdo, function (PDO $pdo) use ($id, $fx): array {
        demo_bestand_loeschen($pdo, $id);

        /* Konto- und Schluesselmaterial ueberschreiben. `session_epoch` wird
         * hochgezaehlt: Offene Sitzungen im Demo-Konto enden damit, und wer
         * gerade mitten in einer Aenderung war, bekommt keinen halben
         * Zustand serviert. */
        $k = $fx['konto'];
        $pdo->prepare('UPDATE users SET email = ?, name = ?, password_hash = ?,
                              kdf_salt = ?, kdf_iter = ?, pat_wrap_pw = ?,
                              pat_wrap_rc = ?, pat_key_check = ?, account_key = ?,
                              role = \'user\', session_epoch = session_epoch + 1
                       WHERE id = ?')
            ->execute([
                (string)$k['email'], DEMO_NAME, (string)$k['password_hash'],
                $k['kdf_salt'] ?? null, (int)($k['kdf_iter'] ?? 0),
                $k['pat_wrap_pw'] ?? null, $k['pat_wrap_rc'] ?? null,
                $k['pat_key_check'] ?? null, $k['account_key'] ?? null, $id,
            ]);
        /* DER ZWEITFAKTOR FAELLT MIT (P5c/AP5, E-P5c-54) — siehe
         * `demo_zweitfaktor_leeren()`. */
        demo_zweitfaktor_leeren($pdo, $id);

        return demo_bestand_einspielen($pdo, $id, $fx);
    });
    demo_reset_marke_setzen();
    demo_aenderung_vergessen($beginn);
    return $stats;
}

/**
 * Alle Bestaende des Demo-Kontos entfernen.
 *
 * Die Reihenfolge folgt den Fremdschluesseln. Das Schema raeumt vieles selbst
 * ab (ON DELETE CASCADE): mission_crew, mission_phases, mission_resources,
 * resus_sessions/-events, day_crew, day_capabilities, day_refs,
 * vehicle_roles, vehicle_capabilities. Ausdruecklich geloescht werden muss,
 * was KEIN Fremdschluessel traegt:
 *
 *   track_points   polymorph (owner_type/owner_id), ohne Verweis
 *   deleted_refs   haengt an der Geraetekennung, ohne Verweis
 *
 * `user_defaults` faellt mit: Vorbelegungen sind Bestand, und ein Besucher
 * kann sie aendern.
 */
function demo_bestand_loeschen(PDO $pdo, int $id): void
{
    /* DER RIEGEL STEHT HIER, NICHT NUR BEI DEN AUFRUFERN.
     *
     * Die drei nach aussen gedachten Funktionen (anlegen, zuruecksetzen,
     * entfernen) nehmen gar keine Kennung entgegen — sie holen sie aus
     * `app_state`. Diese hier bekommt eine, weil sie in derselben Transaktion
     * laufen muss wie ihr Aufrufer, und PHP kennt keine paketprivaten
     * Funktionen: Sie ist damit von ueberall aufrufbar.
     *
     * Eine Funktion, die den gesamten Bestand eines Kontos loescht, darf sich
     * nicht darauf verlassen, dass ihre Aufrufer aufpassen. Der Vergleich
     * kostet eine Abfrage und macht aus einer Zusage eine Eigenschaft. */
    if (!demo_ist_demo($id)) {
        throw new RuntimeException(
            'demo_bestand_loeschen() arbeitet ausschliesslich auf dem in '
            . 'app_state vermerkten Demo-Konto.');
    }

    // Spurpunkte zuerst: Danach sind ihre Eigentuemer weg und sie waeren
    // nicht mehr auffindbar (verwaiste Punkte raeumt sonst erst der
    // Tagesjob ab).
    $pdo->prepare("DELETE tp FROM track_points tp
                   JOIN missions m ON m.id = tp.owner_id
                   WHERE tp.owner_type = 'mission' AND m.user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE tp FROM track_points tp
                   JOIN rest_segments r ON r.id = tp.owner_id
                   WHERE tp.owner_type = 'rest' AND r.user_id = ?")->execute([$id]);
    // Und die Blobs derselben Spuren (S2/AP1). Sie haengen an keinem
    // Fremdschluessel; ohne diese beiden Anweisungen bliebe der Bestand des
    // Demo-Kontos nach dem Zuruecksetzen als Waise liegen.
    $pdo->prepare("DELETE tb FROM track_blobs tb
                   JOIN missions m ON m.id = tb.owner_id
                   WHERE tb.owner_type = 'mission' AND m.user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE tb FROM track_blobs tb
                   JOIN rest_segments r ON r.id = tb.owner_id
                   WHERE tb.owner_type = 'rest' AND r.user_id = ?")->execute([$id]);

    /* UND DIE SPERRVERMERKE DES SCHNITTS (Web 14.2.0, R64).
     *
     * Sie fehlten hier, und das war folgenlos, solange es im Demo-Konto
     * keinen Schnitt gab. Seit E-R64-16 gibt es einen: Die Fixture traegt ihn,
     * und der Reset spielt sie bei jedem Lauf neu ein. Ohne diese Zeile
     * bliebe bei JEDEM Reset ein Vermerk liegen — bis zu 48 am Tag, und keiner davon
     * je wieder auffindbar, weil seine Quelle mit dem Bestand verschwindet.
     * Auch der Waisenjob findet sie nicht: Er sucht Spuren ohne Eigentuemer,
     * und die Spurzeilen sind oben schon weg.
     *
     * UEBER spur_lib.php, wie alles an dieser Tabelle (CLAUDE.md 4). Der Weg
     * `konto` ist genau dafuer da — er ist derselbe, den die Kontoloeschung
     * geht. */
    schnitte_loeschen($pdo, 'konto', [$id]);

    // Sperrliste haengt an der Geraetekennung, nicht am Konto.
    $pdo->prepare('DELETE dr FROM deleted_refs dr
                   JOIN devices d ON d.id = dr.device_id
                   WHERE d.user_id = ?')->execute([$id]);

    foreach (['missions', 'rest_segments', 'days', 'devices', 'pair_sessions',
              'password_resets', 'crew_presets', 'bw_units', 'resources',
              'transport_dests', 'vehicles', 'user_defaults',
              'bases'] as $t) {
        $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$id]);
    }
}

/**
 * Geraete und Bestand einspielen. Erwartet eine offene Transaktion.
 */
function demo_bestand_einspielen(PDO $pdo, int $id, array $fx): array
{
    /* Geraete VOR dem Bestand: `edbak_restore()` verknuepft die
     * Dienstkennungen (`day_refs`) ueber die oeffentliche Geraetekennung mit
     * einem Geraet DIESES Kontos. Fehlt es zu diesem Zeitpunkt, bleibt die
     * Verknuepfung leer — die Kennung stuende dann zwar noch da, aber ohne
     * Geraet, und ein Upload derselben Uhr legte den Diensttag erneut an. */
    /* ART UND MODELL KOMMEN MIT (Web 14.2.0, R64). Ohne sie zeigte die
     * Geraeteseite des Demo-Kontos „Gerät unbekannt", waehrend die Einsaetze
     * daneben ihre Momentaufnahme tragen — ein Widerspruch in derselben
     * Ansicht. Beide Felder sind in der Fixture optional (aeltere Fixtures
     * kennen sie nicht); fehlen sie, bleibt es bei NULL, und das heisst
     * genau das Richtige. */
    $insDev = $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash,
                                                  label, active,
                                                  geraet_art, geraet_modell)
                             VALUES (?,?,?,?,1,?,?)');
    $geraete = 0;
    foreach ((array)($fx['geraete'] ?? []) as $g) {
        if (!is_array($g) || empty($g['device_id']) || empty($g['api_key_hash'])) { continue; }
        /* DAS VIRTUELLE GERAET "Manuelle Einträge" WIRD UEBERSPRUNGEN.
         *
         * Es traegt die Kontonummer im Namen ('manual-<user_id>') und entsteht
         * im Normalbetrieb von selbst, sobald jemand einen Einsatz von Hand
         * anlegt oder importiert (einsatz_form.php, api/import_commit.php) —
         * mit der Nummer DIESES Kontos und dauerhaft deaktiviert.
         *
         * Aus der Fixture kaeme es mit der Nummer des Kontos, aus dem die
         * Fixture stammt. Das ist zweierlei Unfug: Fuer das Demo-Konto ist die
         * Kennung falsch (es legte sich bei der ersten Handeingabe ein zweites
         * an), und `devices.device_id` ist GLOBAL eindeutig (schema.sql 39) —
         * auf einer Installation, die auch den Bestand fuehrt, aus dem die
         * Fixture stammt, brach das Anlegen mit
         * "Duplicate entry 'manual-2' for key 'device_id'" ab.
         *
         * Verwiesen wird darauf nichts: `day_refs` nennen nur echte Geraete,
         * und `missions.device_id` steht gar nicht erst im Backup
         * (backup_lib.php, "Interne Verweise"). Gezaehlt: 0 Vorkommen von
         * 'manual-' in der Nutzlast der ausgelieferten Fixture. */
        if (geraet_virtuell((string)$g['device_id'])) { continue; }
        $insDev->execute([$id, (string)$g['device_id'], (string)$g['api_key_hash'],
                          $g['label'] ?? null,
                          $g['geraet_art'] ?? null, $g['geraet_modell'] ?? null]);
        $geraete++;
    }

    $stats = edbak_restore($id, $fx['daten']);
    $stats['geraete'] = $geraete;
    return $stats;
}

/* DER PAPIERKORB-NACHLAUF IST ENTFALLEN (E-S1-10).
 *
 * Bis Web 7.3.1 stand hier `demo_nachlauf()`: Nach dem Einspielen legte ein
 * Drehbuch benannte Einsaetze und Diensttage ueber die regulaeren Loeschwege
 * (`trash_lib.php`) wieder in den Papierkorb. Es musste NACH dem Commit
 * laufen, weil `trash_delete_*()` je eine eigene Transaktion oeffnen und PDO
 * keine verschachtelten kennt — der Reset zerfiel damit in zwei Schritte, von
 * denen der zweite fehlschlagen konnte.
 *
 * Der Grund dafuer war das Backup-Format: Es kannte keine geloeschten
 * Eintraege, und ein Papierkorb-Dauerzustand liess sich nur nachstellen. Seit
 * Nutzlast 7 fuehrt die Datei den Papierkorb, und `edbak_restore()` bringt ihn
 * als Papierkorb zurueck (E-S1-01/03/04). Damit ist das Drehbuch gegenstandslos
 * — der Reset ist wieder EIN Vorgang in EINER Transaktion, und die Zahlen fuer
 * den Bericht kommen aus den Zaehlern der Einspielroutine (`stats.papierkorb`).
 *
 * Die 90-Tage-Frist stempelt dabei jeder Reset frisch (E-S1-03): Das
 * Demo-Konto haelt seinen Papierkorb also von selbst am Leben, ohne Sonderweg.
 */

/* ------------------------------------------------------------------ Loeschen */

/**
 * Demo-Konto vollstaendig entfernen (Kontozeile eingeschlossen).
 *
 * UEBER `konto_loeschen()` SEIT WEB 21.1.8 (Backlog Nr. 299, F-R4-09). Bis
 * dahin loeschte diese Funktion selbst — Bestand, Kontozeile, Kennzeichnung,
 * in einer Transaktion —, und das war die dritte Abschrift des Loeschens:
 * ohne die Konto-Backups (das Demo-Konto hat welche; sie blieben als
 * verwaiste Ordner liegen), ohne Protokolleintrag und ohne `mengen:<id>` in
 * `app_state`. Jetzt nimmt sie den Weg jedes Kontos und raeumt danach nur,
 * was allein das Demo-Konto hat: seine Kennzeichnung.
 *
 * DIE KENNZEICHNUNG ZULETZT, und nur, wenn das Konto wirklich fort ist.
 * Scheitert `konto_loeschen()` (Backups nicht zu entfernen), bleibt das
 * Demo-Konto samt Kennzeichnung stehen, und nichts ist halb. Zwischen beiden
 * Schritten zeigt die Kennzeichnung einen Augenblick auf ein geloeschtes
 * Konto — `demo_id()` prueft das und antwortet dann `null`.
 *
 * @return array{ok:bool, grund:string} wie `konto_loeschen()`
 */
function demo_entfernen(): array
{
    $id = demo_id();
    if ($id === null) { return ['ok' => true, 'grund' => '']; }
    require_once __DIR__ . '/konto_lib.php';
    $r = konto_loeschen($id, true, 'demo');
    if ($r['ok']) {
        app_state_loeschen(DEMO_K_USER, DEMO_K_RESET, DEMO_K_GEAENDERT);
    }
    return $r;
}
