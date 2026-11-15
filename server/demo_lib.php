<?php
declare(strict_types=1);
if (PHP_SAPI !== 'cli' && realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(404); exit; }   // nur eingebunden (KA-05, F-KA-23)
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/backup_lib.php';
/* Fuer den Huellen-Riegel in `demo_fixture_laden()`. Die Datei liegt
 * unter `db.php` und greift nicht hierher zurueck — die Richtung stimmt. */
require_once __DIR__ . '/serverkrypto_lib.php';

/**
 * Demo-Konto.
 *
 * WOFUER ES DAS GIBT
 * Ein Konto, in dem sich die Anwendung ohne Anmeldehuerde ausprobieren
 * laesst: Zugangsdaten oeffentlich, Daten frei erfunden, Aenderungen
 * erwuenscht — und 30 Minuten nach der ersten Aenderung wieder auf den
 * Ausgangsstand (Abschnitt DIE AENDERUNGSMARKE unten).
 *
 * DIE AUSNAHME, DIE HIER GEMACHT WIRD, UND IHRE GRENZE
 * Das Projekt verspricht Ende-zu-Ende-Verschluesselung: Der Server sieht die
 * geschuetzten Angaben nie im Klartext, und das Schluesselmaterial haengt am
 * Passwort. Fuer DIESES eine Konto gilt das nicht — sein Schluesselmaterial
 * liegt in der Fixture auf dem Server, damit ein Reset die Chiffretexte
 * wieder lesbar macht.
 *
 * Das ist eine bewusste, eng gezogene Ausnahme und nur deshalb
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
 * folgenlos bleibt. Die Sperren des Demo-Kontos sind die erste Linie; dass der
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

/** Pflichtreset: so lange nach dem letzten Reset auch OHNE Aenderung.
 *  Das Netz fuer eine Aenderung, die an keiner Setzstelle
 *  vorbeikam, und fuer eine neue Fixture nach einem Deploy. */
const DEMO_PFLICHT_SEKUNDEN = 86400;

/** Schluessel in `app_state`. */
const DEMO_K_USER      = 'demo_user_id';
const DEMO_K_RESET     = 'demo_letzter_reset';
const DEMO_K_GEAENDERT = 'demo_geaendert';

/** Pfad der Fixture. Liegt unter server/, weil der Produktivserver sie
 *  braucht — alles Uebrige zum Demo-Konto liegt unter tools/.
 *  GEPACKT, weil sie roh rund 11 MB misst (im Wesentlichen Spurpunkte) und
 *  bei jedem Deploy ueber FTPS mitgeht. */
/* ANZEIGENAME DES DEMO-KONTOS. Er kommt NICHT aus der Fixture:
 * Dort steht der Name des Referenzkontos, aus dem sie erzeugt wurde, und in
 * der NutzerInnen-Liste las sich das wie ein gewoehnliches Konto. „Demo
 * NutzerIn" sagt beim Ueberfliegen, was es ist. Gesetzt wird er beim Anlegen
 * UND beim Zuruecksetzen — sonst holte der naechste Reset den Fixture-Namen
 * zurueck. */
const DEMO_NAME = 'Demo NutzerIn';

/* DAS PASSWORT DES DEMO-KONTOS. Es steht im Handbuch
 * (Abschnitt Demo-Konto) und ist kein Geheimnis. Der Server braucht es an
 * genau einer Stelle: beim Zuruecksetzen, um mit dem Inhaltsschluessel
 * des Demo-Kontos Transportziel und Ortshoehe in den `pat_blob` zu legen und
 * ein Schluesselpaar anzulegen, falls noch keines da ist. Fuer jedes andere
 * Konto kennt er weder Passwort noch Inhaltsschluessel, und keine Funktion
 * hier nimmt ein anderes Konto entgegen (`demo_id()`). Passt es nicht zur
 * Huelle der Fixture, bricht der Reset ab, statt still Klartext zu
 * hinterlassen. SEIT FIXTURE-FORMAT 4 (KA-13) ist es dieselbe eine Stelle
 * mit anderer Arbeit: Der Bestand kommt verschlossen, und der Reset legt
 * ihn unter einen frischen Inhaltsschluessel (`demo_frischer_schluessel()`). */
const DEMO_PASSWORT = 'nadokudemo0815';

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
    /* FORMAT 1 BIS 4. Format 3 (Konzept RD) traegt an spurtragenden
     * Eintraegen `stufe` und `n_original` und an Segmenten `loeschungen`; der
     * Reset legt daraus Stufe 2 und 3 an (`edbak_restore()`, Zweig 'server').
     * Format 2 und 1 tragen das nicht und laufen weiter — jede Spur dann auf
     * Stufe 1. FORMAT 4 (Konzept KA, KA-13, F-KA-104) traegt den Bestand
     * VERSCHLOSSEN, wie er nach dem Umlegen der Weiche in einem Konto liegt:
     * Geraetehuellen der Fassung 2 von zwei Demo-Geraeten mit festem
     * Schluessel, `edk2:`-Bloecke, Standbuch, Geraetebuch und Kontopaar —
     * der Reset reicht ihn durch (`demo_bestand_einspielen4()`). Eine hoehere
     * Nummer stammt aus einem Erzeuger, den diese Fassung nicht kennt:
     * abgewiesen, statt halb gelesen. */
    $fxVersion = (int)($fx['version'] ?? 1);
    if ($fxVersion < 1 || $fxVersion > 4) {
        throw new RuntimeException('Die Fixture hat Format ' . $fxVersion
            . '; diese Fassung liest Format 1 bis 4.');
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
    /* FORMAT 4 BRINGT MEHR MIT, UND ALLES DAVON IST PFLICHT: Ohne Kontopaar
     * ginge keine Geraetehuelle auf, ohne Geraetebuch (Blatt 16) keine der
     * Demo-Geraete (`absender`), und ein fehlendes Blatt liesse den
     * Anhebelauf nach jeder Anmeldung eines schreiben. */
    if ($fxVersion >= 4) {
        foreach (['kp_oeffentlich', 'kp_privat', 'pat_wrap_rc', 'rc_hex'] as $pflicht) {
            if (!is_string($fx['konto'][$pflicht] ?? null) || $fx['konto'][$pflicht] === '') {
                throw new RuntimeException("Fixture unvollstaendig: konto.$pflicht fehlt.");
            }
        }
        $wer = array_map(static fn($b) => is_array($b) ? (int)($b['wer'] ?? -1) : -1,
                         (array)($fx['standbuch'] ?? []));
        sort($wer);
        if ($wer !== range(0, 16) || !is_array($fx['spuren'] ?? null)) {
            throw new RuntimeException('Fixture unvollstaendig: Format 4 traegt 16 Blaetter, '
                . 'das Geraetebuch und die Spurstuecke.');
        }
    }
    /* DIE RUNDENZAHL MUSS BEDIENBAR SEIN.
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

    /* DIE HUELLEN TRAGEN KEINEN ANTEIL — `edk1:`, ab Format 4 `edk2:`
     * (zweiter Riegel).
     *
     * DERSELBE GEDANKE WIE EINE ZEILE HOEHER, mit einem anderen Geheimnis.
     * Die Rundenzahl entscheidet, ob sich das Demo-Konto anmelden kann; die
     * Huellenfassung entscheidet, ob es danach etwas sieht.
     *
     * Der Datenschluessel haengt am Server-Anteil aus `config.php`,
     * und der ist je Installation ein anderer (`install.php` wuerfelt ihn).
     * Eine Huelle mit Anteil (`edka1:<kennung>:`) laesst sich nur dort
     * oeffnen, wo dieser Anteil steht. Die Fixture reist aber: Sie entsteht
     * auf der Referenzmaschine und wird beim Deploy auf den Produktivserver
     * gelegt.
     *
     * Und das Demo-Konto bekommt BAUARTBEDINGT gar keinen Anteil
     * (`auth_guard.php`): `KONTO_ANTEILE` ist fuer es
     * `null`, `ANTEIL_STAND` ist `'demo'`. `EdCrypto.datenschluessel()`
     * wirft bei einer `edka1:`-Huelle ohne Anteil ausdruecklich, statt auf
     * die PBKDF2-Haelfte zurueckzufallen — der Rueckfall ergaebe einen
     * Schluessel, der nicht passt, und saehe aus wie ein falsches Passwort.
     *
     * OHNE DIESEN RIEGEL WAERE DER RESET STILL ERFOLGREICH. Das Konto kaeme
     * herein (der bcrypt-Hash stimmt ja), und erst das Entsperren scheiterte
     * — auf der oeffentlichen Demo, bei jedem Reset aufs Neue. Genau dieselbe
     * Begruendung wie beim Riegel auf die Rundenzahl:
     * „Ohne den zweiten Riegel waere ein Reset still erfolgreich und niemand
     * kaeme mehr herein."
     *
     * DER ERSTE RIEGEL STEHT IM ERZEUGER (`tools/referenzdatensatz/fixture/
     * erzeugen.php`) und verhindert, dass eine solche Fixture
     * ueberhaupt entsteht. Dieser hier verhindert, dass sie eingespielt wird.
     * Zwei Riegel, weil der erste nur greift, wo das Werkzeug laeuft — und
     * die Datei kommt auf dem Produktivserver an, nicht das Werkzeug.
     *
     * GEPRUEFT WIRD MIT DER GEMEINSAMEN PRUEFSCHICHT, nicht mit einem eigenen
     * Ausdruck: `huelle_pw_pruefen($wrap, istDemo: true)` setzt den erwarteten
     * Anteil auf `null` und weist damit jede Huelle ab, die eine Kennung
     * nennt; `huelle_rc_pruefen()` haelt den Wiederherstellungsschluessel vom
     * Anteil fern. Ein zweiter Ausdruck an dieser Stelle liesse
     * frueher oder spaeter die falsche Huelle durch. */
    $grundPw = huelle_pw_pruefen((string)$fx['konto']['pat_wrap_pw'], true);
    if ($grundPw !== null) {
        throw new RuntimeException('Die Schluesselhuelle der Fixture ist fuer '
            . 'das Demo-Konto unbrauchbar: ' . $grundPw
            . ' Das Demo-Konto bekommt keinen Server-Anteil; seine Huelle muss '
            . '`edk1:` oder `edk2:` tragen. Die Fixture neu erzeugen — '
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

/* ---- DIE AENDERUNGSMARKE --------
 *
 * WARUM. Setzte die erste Anfrage des Demo-Kontos nach 30 Minuten zurueck,
 * ob sich etwas geaendert hat oder nicht: Der Reset laeuft huckepack und kostet
 * rund sechseinhalb Sekunden (gemessen
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
 * AB DER ERSTEN AENDERUNG, NICHT AB DEM LETZTEN RESET.
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
 * POSTs des Demo-Kontos, die die Marke NICHT setzen (Backlog Nr. 459).
 *
 * Schluessel ist der Pfad des Skripts unter `server/`, Wert der Grund, warum
 * es fuer das Demo-Konto nichts aendert. Jeder Eintrag ist gemessen, keiner
 * vermutet: Beim blossen Ansehen von Startseite, Einstellungen und einem
 * Einsatz schickte der Browser am 04.11.2026 ausser der Anmeldung NUR
 * Anfragen an den Wartungslauf — eine `liste` und zwanzig `tauschen`, denn die
 * Fixture traegt faellige Spuren, und der Lauf verdichtete sie wirklich.
 * Ein Eintrag hier verlangt deshalb, dass der Endpunkt fuer das Demo-Konto
 * auch wirklich nichts schreibt; sonst bliebe eine Aenderung bis zum
 * Pflichtreset stehen.
 */
const DEMO_POST_OHNE_MARKE = [
    'api/wartungslauf.php' => 'antwortet dem Demo-Konto mit einer leeren Liste und tauscht nichts',
];

/**
 * Die lesenden Aktionen von `api/standbuch.php` (KA-13). Der Endpunkt nimmt
 * drei Aktionen in einem `POST`: `stand` und `holen` lesen, `ablegen`
 * schreibt. Mit umgelegter Weiche gleicht jede Seite ihr Standbuch ab —
 * gemessen am 15.11.2026 beim blossen Ansehen des Demo-Bestands: 3 × `stand`
 * und 17 × `holen`, und die Marke stand. Ein Eintrag in
 * `DEMO_POST_OHNE_MARKE` waere zu grob (er naehme `ablegen` mit); entschieden
 * wird deshalb an der Aktion im Rumpf.
 */
const DEMO_STANDBUCH_LIEST = ['stand', 'holen'];

/** Setzt diese POST die Marke? `$skript` ist der Pfad unter `server/`. */
function demo_post_aendert(string $skript): bool
{
    if ($skript === 'api/standbuch.php') {
        /* `php://input` laesst sich mehrfach lesen; der Endpunkt liest ihn
         * danach noch einmal (`api_rumpf()`). Ein Rumpf, der kein JSON ist
         * oder eine andere Aktion nennt, setzt die Marke — die strenge Seite. */
        $b = json_decode((string)file_get_contents('php://input'), true);
        return !(is_array($b) && in_array($b['aktion'] ?? null, DEMO_STANDBUCH_LIEST, true));
    }
    return !array_key_exists($skript, DEMO_POST_OHNE_MARKE);
}

/** Pfad des aufgerufenen Skripts unter `server/`, z. B. `api/wartungslauf.php`. */
function demo_skript_hier(): string
{
    $datei = realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) ?: '';
    $wurzel = realpath(__DIR__) . DIRECTORY_SEPARATOR;
    return str_starts_with($datei, $wurzel)
        ? str_replace(DIRECTORY_SEPARATOR, '/', substr($datei, strlen($wurzel))) : '';
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
 * Anfragegetriebener Reset — das Muster der vorhandenen Aufraeumjobs.
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
    $fx = demo_frischer_schluessel(demo_fixture_laden());
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
                /* NIEMALS eine Rolle mit Rechten — der Satz gilt fuer alle
                 * Rollen mit Rechten. Der Reset unten schreibt denselben Wert
                 * zurueck, damit ein waehrend der Sitzung erhoehtes Konto
                 * spaetestens nach dreissig Minuten wieder eine NutzerIn ist. */
                'user',
                $k['account_key'] ?? null,
            ]);
        $id = (int)$pdo->lastInsertId();
        /* `app_state_setzen()` nimmt `db()` — dieselbe statische Verbindung
         * wie `$pdo` hier, also DIESELBE offene Transaktion. */
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
 * `demo_zuruecksetzen()`.
 *
 * WARUM ES IHN GIBT: Einschalten ist im Demo-Konto gesperrt; steht trotzdem
 * einer da — ein Konto, das erst nachtraeglich zum Demo-Konto wurde, oder ein
 * handgebauter POST an der Sperre vorbei —, sperrte er die naechste
 * Besucherin aus. Vorabfrage auf die Spalte: Vor `update.php` gibt es sie
 * nicht, und der Reset laeuft auch dann.
 *
 * WARUM EINE EIGENE FUNKTION: Die Zweitfaktorprobe soll diesen
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
    /* DAS PAAR DES RÜCKWEGS GEHÖRT MIT DAZU:
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
    /* DIE GEMERKTEN GERAETE — dieselbe
     * Ueberlegung: Das Demo-Konto hat keinen Zweitfaktor, also nichts zu
     * merken; steht trotzdem etwas da, raeumt der Reset es ab. Eigene
     * Vorabfrage, weil die Tabelle mit einer eigenen Migration kommt. */
    if (db_hat_tabelle($pdo, 'vertraute_geraete')) {
        $pdo->prepare('DELETE FROM vertraute_geraete WHERE user_id = ?')->execute([$id]);
    }
    /* DIE PASSKEYS — ein Verfahren desselben Faktors, also
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
    $fx = demo_frischer_schluessel(demo_fixture_laden());
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
        /* DER ZWEITFAKTOR FAELLT MIT — siehe
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
 * day_crew, day_capabilities, day_refs, vehicle_roles, vehicle_capabilities.
 * Ausdruecklich geloescht werden muss, was KEIN Fremdschluessel traegt:
 *
 *   track_stuecke  polymorph (owner_type/owner_id), ohne Verweis
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

    // Spurstuecke zuerst: Danach sind ihre Eigentuemer weg und sie waeren
    // nicht mehr auffindbar.
    $pdo->prepare("DELETE ts FROM track_stuecke ts
                   JOIN missions m ON m.id = ts.owner_id
                   WHERE ts.owner_type = 'mission' AND m.user_id = ?")->execute([$id]);
    $pdo->prepare("DELETE ts FROM track_stuecke ts
                   JOIN rest_segments r ON r.id = ts.owner_id
                   WHERE ts.owner_type = 'rest' AND r.user_id = ?")->execute([$id]);

    /* UND DIE SPERRVERMERKE DES SCHNITTS.
     *
     * Sie fehlten hier, und das war folgenlos, solange es im Demo-Konto
     * keinen Schnitt gab. Es gibt einen: Die Fixture traegt ihn,
     * und der Reset spielt sie bei jedem Lauf neu ein. Ohne diese Zeile
     * bliebe bei JEDEM Reset ein Vermerk liegen — bis zu 48 am Tag, und keiner davon
     * je wieder auffindbar, weil seine Quelle mit dem Bestand verschwindet.
     * Auch der Waisenjob findet sie nicht: Er sucht Spuren ohne Eigentuemer,
     * und die Stuecke sind oben schon weg.
     *
     * UEBER spur_lib.php, wie alles an dieser Tabelle (CLAUDE.md 4). Der Weg
     * `konto` ist genau dafuer da — er ist derselbe, den die Kontoloeschung
     * geht. */
    schnitte_loeschen($pdo, 'konto', [$id]);

    // Sperrliste haengt an der Geraetekennung, nicht am Konto.
    $pdo->prepare('DELETE dr FROM deleted_refs dr
                   JOIN devices d ON d.id = dr.device_id
                   WHERE d.user_id = ?')->execute([$id]);

    /* DIE BLAETTER DES STANDBUCHS (KA-06b-2). Sie haengen am Konto, und das
     * Konto bleibt beim Reset stehen — ohne diese Zeile stuende nach dem
     * Reset ein Zaehlerstand fuer Datensaetze, die es nicht mehr gibt, und
     * die neu eingespielte Vorlage galte im Browser als zurueckgelegt.
     * Vor `update.php` gibt es die Tabelle nicht. */
    if (db_hat_tabelle($pdo, 'stand_blaetter')) {
        $pdo->prepare('DELETE FROM stand_blaetter WHERE user_id = ?')->execute([$id]);
    }

    foreach (['missions', 'rest_segments', 'days', 'devices', 'pair_sessions',
              'password_resets', 'crew_presets', 'bw_units', 'resources',
              'transport_dests', 'vehicles', 'user_defaults',
              'bases'] as $t) {
        $pdo->prepare("DELETE FROM `$t` WHERE user_id = ?")->execute([$id]);
    }
}

/* DIE GRENZE DER ABLEITUNG 2 — dieselbe Zahl wie `ABLEITUNG2_AB` in
 * assets/crypto.js (KA-06b-3, F-KA-29). Unter ihr Fassung 1: 64 Byte aus
 * PBKDF2, die erste Hälfte. Ab ihr Fassung 2: ein PBKDF2-Block als Wurzel,
 * die Hälfte per HKDF-SHA256 (Salz leer, Info „nadoku-ableitung-2|haelfte").
 * Byte-gleich zu `ableitung2()` in tools/proben/huelle/referenz_inhalt.py;
 * die Hüllenprobe des Servers hält es gegen `vektoren-inhalt.json`. Heute
 * rechnet hier noch kein Konto mit 1 200 000 Runden — das Ziel hebt sich erst
 * mit der Weiche. */
const KDF_ABLEITUNG2_AB = 1200000;

/** Die PBKDF2-Hälfte, aus der der Datenschlüssel entsteht (32 Byte). */
function kdf_haelfte(string $passwort, string $salz, int $runden): string
{
    if ($runden >= KDF_ABLEITUNG2_AB) {
        $wurzel = hash_pbkdf2('sha256', $passwort, $salz, $runden, 32, true);
        return hash_hkdf('sha256', $wurzel, 32, 'nadoku-ableitung-2|haelfte', '');
    }
    return substr(hash_pbkdf2('sha256', $passwort, $salz, $runden, 64, true), 0, 32);
}

/**
 * Der Inhaltsschluessel des Demo-Kontos (32 Byte), aus dem bekannten Passwort
 * — dieselbe Rechnung wie `EdCrypto.deriveKeys()` und `huelleOeffnen()` fuer
 * ein Konto ohne Server-Anteil: PBKDF2-SHA256 auf 512 Bit, die erste Haelfte
 * oeffnet `pat_wrap_pw` (`edk1:`, AES-GCM), darin steht der Schluessel als
 * Hex-Text.
 */
function demo_inhaltsschluessel(array $konto): string
{
    $haelfte = kdf_haelfte(DEMO_PASSWORT, (string)hex2bin((string)$konto['kdf_salt']),
                           (int)$konto['kdf_iter']);
    $ckHex = demo_edk1_oeffnen($haelfte, (string)$konto['pat_wrap_pw']);
    if ($ckHex === null || !preg_match('/^[0-9a-f]{64}\z/', $ckHex)) {
        throw new RuntimeException('Das Demo-Passwort öffnet die Schlüsselhülle der Fixture '
            . 'nicht. Passwort im Handbuch, DEMO_PASSWORT und Fixture gehören zusammen.');
    }
    return (string)hex2bin($ckHex);
}

/** `edk1:`-Chiffretext (AES-GCM, IV 12 Byte vorn, Tag 16 Byte hinten) oeffnen — oder null. */
function demo_edk1_oeffnen(string $schluessel, string $text): ?string
{
    if (str_starts_with($text, 'edk1:')) { $text = substr($text, 5); }
    $roh = base64_decode($text, true);
    if ($roh === false || strlen($roh) < 12 + 16) { return null; }
    $klar = openssl_decrypt(substr($roh, 12, -16), 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA,
                            substr($roh, 0, 12), substr($roh, -16));
    return $klar === false ? null : $klar;
}

/** Klartext zu `edk1:` — das Format von `EdCrypto.encrypt()`. */
function demo_edk1_bauen(string $schluessel, string $klar): string
{
    $iv = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($klar, 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA, $iv, $tag, '', 16);
    if ($ct === false) { throw new RuntimeException('AES-GCM gescheitert'); }
    return 'edk1:' . base64_encode($iv . $ct . $tag);
}

/**
 * Hat das Demo-Konto kein Schluesselpaar, legt der Server eines an
 * — wie `EdKontopaar.anlegen()`: P-256, der oeffentliche Teil als
 * SPKI, der private als PKCS8 unter dem Inhaltsschluessel. Ohne Paar liesse
 * sich der Bestand nicht verschliessen, und eine frische Anlage zeigte keine
 * Karte, bis sich jemand als Demo angemeldet hat.
 */
function demo_paar_sicherstellen(PDO $pdo, int $id, string $ck): void
{
    require_once __DIR__ . '/kontopaar_lib.php';
    if (!demo_ist_demo($id)) {
        throw new RuntimeException('demo_paar_sicherstellen() arbeitet nur auf dem Demo-Konto.');
    }
    /* DA IST NICHT GENUG: Der private Teil muss mit DIESEM Inhaltsschluessel
     * aufgehen (Konzept RD, F-RD-25). Der Reset schreibt Schluesselhuelle und
     * damit den Inhaltsschluessel aus der Fixture ins Konto — stammt die
     * Fixture aus einem anderen Referenzkonto als die vorige (jede neue
     * Fixture tut das), liegt `kp_privat` unter dem ALTEN Schluessel. Der
     * Browser konnte dann keine Spur, keinen Ort und keine Reanimation mehr
     * oeffnen, versuchte das Paar zu ersetzen und lief in die Mengenbremse
     * (gemessen: `kontopaar` nach 5 Versuchen gesperrt, 87 Konsolenfehler im
     * Bilderlauf). Ein Paar, das nicht aufgeht, wird verworfen und neu
     * angelegt; der Reset verschliesst danach alles mit dem neuen. */
    $z = kp_zustand($id, $pdo);
    if ($z['stand'] === 'da' && $z['privat'] !== null
        && demo_edk1_oeffnen($ck, (string)$z['privat']) !== null) { return; }
    if ($z['stand'] === 'da') {
        $pdo->prepare('UPDATE users SET kp_oeffentlich = NULL, kp_privat = NULL, kp_seit = NULL
                        WHERE id = ?')->execute([$id]);
    }
    $k = openssl_pkey_new(['private_key_type' => OPENSSL_KEYTYPE_EC, 'curve_name' => 'prime256v1']);
    if ($k === false || !openssl_pkey_export($k, $pem)) {
        throw new RuntimeException('Das Schlüsselpaar des Demo-Kontos ließ sich nicht erzeugen.');
    }
    $koerper = static fn(string $p): string => (string)preg_replace('/-----[^-]+-----|\s+/', '', $p);
    $spki = $koerper((string)openssl_pkey_get_details($k)['key']);
    $pkcs8 = $koerper((string)$pem);
    if (!kp_ablegen($pdo, $id, $spki, demo_edk1_bauen($ck, $pkcs8))) {
        throw new RuntimeException('Das Schlüsselpaar des Demo-Kontos ließ sich nicht ablegen.');
    }
}

/**
 * Geraete und Bestand einspielen. Erwartet eine offene Transaktion.
 *
 * VERSCHLOSSEN: Orte, Reanimation und Spur der
 * Fixture verschliesst `edbak_restore()` mit dem oeffentlichen Schluessel des
 * Demo-Kontos ('server'), Transportziel und Ortshoehe legt der Rueckruf mit
 * dem Inhaltsschluessel in den `pat_blob`. Der Bestand des Demo-Kontos traegt
 * danach keine Klartext-Ortsdaten mehr — sonst verweigerte die Aufraeum-
 * migration auf jeder Anlage mit Demo-Konto.
 */
function demo_bestand_einspielen(PDO $pdo, int $id, array $fx): array
{
    /* FORMAT 4 REICHT DURCH (KA-13): Kontopaar, Bloecke, Huellen und
     * Standbuch kommen verschlossen aus der Fixture; der Server oeffnet
     * davon nichts und verschliesst nichts. Bis Format 3 legt er das Paar an
     * und verschliesst den Klartext der Fixture selbst (Fassung 1). */
    $durchreichen = (int)($fx['version'] ?? 1) >= 4;
    $ck = null;
    if ($durchreichen) {
        demo_kontopaar_setzen($pdo, $id, $fx['konto']);
    } else {
        $ck = demo_inhaltsschluessel($fx['konto']);
        demo_paar_sicherstellen($pdo, $id, $ck);
    }

    /* Geraete VOR dem Bestand: `edbak_restore()` verknuepft die
     * Dienstkennungen (`day_refs`) ueber die oeffentliche Geraetekennung mit
     * einem Geraet DIESES Kontos. Fehlt es zu diesem Zeitpunkt, bleibt die
     * Verknuepfung leer — die Kennung stuende dann zwar noch da, aber ohne
     * Geraet, und ein Upload derselben Uhr legte den Diensttag erneut an. */
    /* ART UND MODELL KOMMEN MIT. Ohne sie zeigte die
     * Geraeteseite des Demo-Kontos „Gerät unbekannt", waehrend die Einsaetze
     * daneben ihre Momentaufnahme tragen — ein Widerspruch in derselben
     * Ansicht. Beide Felder sind in der Fixture optional (aeltere Fixtures
     * kennen sie nicht); fehlen sie, bleibt es bei NULL, und das heisst
     * genau das Richtige. */
    /* DER GERAETESCHLUESSEL KOMMT MIT (Format 4): `spk` und `herkunft` = 1
     * sagen, dass dieses Geraet im Geraetebuch des Kontos steht — die
     * Geraeteseite haelt beides gegeneinander und truege einen Bucheintrag
     * ohne Geraet sonst aus (`kopplung.js`, Abgleich). Vor `update.php` gibt
     * es die Spalten nicht; dann bleibt es bei der alten Form. */
    $mitSpk = $durchreichen && db_hat_spalte($pdo, 'devices', 'herkunft');
    $insDev = $pdo->prepare('INSERT INTO devices (user_id, device_id, api_key_hash,
                                                  label, active,
                                                  geraet_art, geraet_modell'
                            . ($mitSpk ? ', spk, herkunft' : '') . ')
                             VALUES (?,?,?,?,1,?,?' . ($mitSpk ? ',?,1' : '') . ')');
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
        $werte = [$id, (string)$g['device_id'], (string)$g['api_key_hash'],
                  $g['label'] ?? null,
                  $g['geraet_art'] ?? null, $g['geraet_modell'] ?? null];
        if ($mitSpk) { $werte[] = (string)($g['spk'] ?? ''); }
        $insDev->execute($werte);
        $geraete++;
    }
    /* DIE GERAETE DER FIXTURE SIND KEINE NEUEN. Der Reset legt sie mit
     * `created_at` = jetzt an, und die Startseite warnte deshalb nach jedem
     * Reset „2 neue Geräte wurden mit deinem Konto verbunden … Warst du das
     * nicht, entferne die Geräte" — eine Warnung vor einem Fremdzugriff, den
     * es nicht gibt (R91-Nachprüfung zu Schritt 24, Staging 05.11.2026).
     * Bestaetigt wird nach dem Anlegen, also mit einem Zeitpunkt, der keinen
     * Fixture-Eintrag mehr als neu gelten laesst; ein Geraet, das die
     * BesucherIn danach koppelt, warnt wie in jedem Konto. Aus demselben
     * Grund ist der Schritt „Uhr oder Handy koppeln" der Willkommenskarte
     * erledigt — das Konto hat Geraete. */
    if ($geraete > 0) {
        require_once __DIR__ . '/einstieg_lib.php';
        geraete_hinweis_bestaetigen($pdo, $id);
        erststart_erledigt($id, ERSTSTART_GERAET);
    }

    if ($durchreichen) {
        $stats = demo_bestand_durchreichen($pdo, $id, $fx);
        $stats['geraete'] = $geraete;
        return $stats;
    }

    $blobZiel = static function (?string $blob, array $zusatz) use ($ck): string {
        $pat = [];
        if ($blob !== null) {
            $klar = demo_edk1_oeffnen($ck, $blob);
            if ($klar === null) {
                throw new RuntimeException('Ein pat_blob der Fixture geht mit dem Inhaltsschlüssel '
                    . 'des Demo-Kontos nicht auf.');
            }
            $pat = (array)json_decode($klar, true);
        }
        $pat += $zusatz;        // was der Blob schon trägt, bleibt
        return demo_edk1_bauen($ck, (string)json_encode($pat, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    };
    /* DIE VERMERKE BEKOMMEN EINEN ZEITPUNKT VOR DIESEM RESET (Konzept RD,
     * E-RD-18, F-RD-14): Mit dem Wert aus der Fixture hielte jede spaeter
     * installierte Anlage die Demo-Schnitte fuer alt (`409 altschnitt`), und die
     * Ruecknahme liesse sich nirgends vorfuehren. EINE SEKUNDE VOR dem Beginn,
     * nicht die Einspielzeit selbst: Der Reset schreibt die Stuecke ueber
     * mehrere Sekunden, und ein Vermerk, der juenger waere als das gepackte
     * Stueck seines Segments, liesse die Ruecknahme dort zu — die Punkte
     * sind aber schon gefallen (F-RD-22, `spur2_seit_gepackt()`). */
    $vorher = gmdate('Y-m-d H:i:s', time() - 1);
    $stats = edbak_restore($id, $fx['daten'], null, 'server', $blobZiel);
    schnitte_stempeln($pdo, $id, $vorher);
    $stats['geraete'] = $geraete;
    return $stats;
}

/* ---- DER INHALTSSCHLÜSSEL WECHSELT MIT JEDEM RESET (Format 4, KA-13) -----
 *
 * WARUM. Das Standbuch hält im Browser fest, welchen Zählerstand er je
 * Datensatz schon gesehen hat (IndexedDB, übersteht das Abmelden), und
 * weist alles darunter als zurückgelegt ab — das ist sein Zweck. Ein
 * Demo-Reset IST ein Zurücklegen: Die Fixture bringt jeden Block und jedes
 * Blatt wieder auf den Stand 1. Wer im Demo-Konto etwas gespeichert hat und
 * nach dem Reset wiederkommt, sähe an genau diesen Einsätzen „älter als der
 * Stand, den dieser Browser kennt" (gemessen am 15.11.2026: ein Einsatz
 * gespeichert, Reset, derselbe Browser — `blatt-zurueckgelegt` am Blatt,
 * `zurueckgelegt` an Block und Einsatzhülle).
 *
 * WIE. Der Browser führt sein Buch je INHALTSSCHLÜSSEL (`kontoKennung()` in
 * standbuch.js): Wer einen anderen hat, hat ein anderes Buch. Der Reset
 * würfelt deshalb einen neuen und legt alles, was unter dem der Fixture
 * liegt, unter ihn — Patientenblöcke, Blätter, Gerätebuch, den privaten
 * Teil des Kontopaars und die zwei Schlüsselhüllen. Die Gerätehüllen
 * bleiben, wie sie sind: Sie hängen am Kontopaar, nicht am
 * Inhaltsschlüssel. Nach dem Reset findet jeder Browser kein Buch zu diesem
 * Schlüssel und glaubt der Abschrift des Servers — wie ein neuer.
 *
 * DAS GEHT NUR HIER. Der Server kennt den Inhaltsschlüssel dieses einen
 * Kontos, weil sein Passwort im Handbuch steht (Kopf der Datei); für jedes
 * andere Konto kann er nichts davon. Umgehüllt wird der INNENTEXT, wie er
 * ist — Zähler, Länge, Nutzlast, Polster —, unter denselben Zusatzdaten
 * (Zweck und Bezug). Das Format steht in
 * `tools/proben/huelle/referenz_inhalt.py`; ob die Texte danach aufgehen,
 * misst `tools/referenzdatensatz/browser/demo_fassung2.mjs` im Browser.
 */

/** Zusatzdaten eines `edk2:`-Texts: "edk2" ‖ Feld(Zweck) ‖ Feld(Bezug). */
function demo_edk2_zusatz(string $zweck, string $bezug): string
{
    return 'edk2' . pack('n', strlen($zweck)) . $zweck . pack('n', strlen($bezug)) . $bezug;
}

/** Den Innentext eines `edk2:`-Texts (Zähler ‖ Länge ‖ Nutzlast ‖ Polster) — oder null. */
function demo_edk2_innen(string $schluessel, string $text, string $zweck, string $bezug): ?string
{
    if (!str_starts_with($text, 'edk2:')) { return null; }
    $roh = base64_decode(substr($text, 5), true);
    if ($roh === false || strlen($roh) < 12 + 16 + 8) { return null; }
    $klar = openssl_decrypt(substr($roh, 12, -16), 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA,
                            substr($roh, 0, 12), substr($roh, -16), demo_edk2_zusatz($zweck, $bezug));
    return $klar === false ? null : $klar;
}

/** Einen Innentext zu `edk2:` verschließen. */
function demo_edk2_bauen(string $schluessel, string $innen, string $zweck, string $bezug): string
{
    $nonce = random_bytes(12);
    $tag = '';
    $ct = openssl_encrypt($innen, 'aes-256-gcm', $schluessel, OPENSSL_RAW_DATA, $nonce, $tag,
                          demo_edk2_zusatz($zweck, $bezug), 16);
    if ($ct === false) { throw new RuntimeException('AES-GCM gescheitert'); }
    return 'edk2:' . base64_encode($nonce . $ct . $tag);
}

/** Der Innentext einer Schlüsselhülle: Zähler 0, die Nutzlast auf 128 Byte gepolstert. */
function demo_edk2_huelle_innen(string $ckHex): string
{
    return str_pad(pack('NN', 0, strlen($ckHex)) . $ckHex, 128, "\0");
}

/**
 * Die Fixture (Format 4) unter einen frischen Inhaltsschlüssel legen —
 * rein, ohne Datenbank: Eingabe die geladene Fixture, Ausgabe dieselbe mit
 * neuen Texten. Geht ein Text mit dem Schlüssel der Fixture nicht auf,
 * wirft sie, und der Reset schreibt nichts.
 */
function demo_frischer_schluessel(array $fx): array
{
    if ((int)($fx['version'] ?? 1) < 4) { return $fx; }
    $k = $fx['konto'];
    $haelfte = kdf_haelfte(DEMO_PASSWORT, (string)hex2bin((string)$k['kdf_salt']), (int)$k['kdf_iter']);
    $innen = demo_edk2_innen($haelfte, (string)$k['pat_wrap_pw'], 'wrap', 'pw');
    $altHex = $innen === null ? '' : substr($innen, 8, 64);
    if ($innen === null || !preg_match('/^[0-9a-f]{64}\z/', $altHex)) {
        throw new RuntimeException('Das Demo-Passwort öffnet die Schlüsselhülle der Fixture '
            . 'nicht. Passwort im Handbuch, DEMO_PASSWORT und Fixture gehören zusammen.');
    }
    $alt = (string)hex2bin($altHex);
    $neu = random_bytes(32);
    $neuHex = bin2hex($neu);
    $um = static function (string $text, string $zweck, string $bezug, string $was) use ($alt, $neu): string {
        $innen = demo_edk2_innen($alt, $text, $zweck, $bezug);
        if ($innen === null) {
            throw new RuntimeException($was . ' der Fixture geht mit ihrem Inhaltsschlüssel nicht auf.');
        }
        return demo_edk2_bauen($neu, $innen, $zweck, $bezug);
    };

    $k['pat_wrap_pw'] = demo_edk2_bauen($haelfte, demo_edk2_huelle_innen($neuHex), 'wrap', 'pw');
    $k['pat_wrap_rc'] = demo_edk2_bauen((string)hex2bin((string)$k['rc_hex']),
                                        demo_edk2_huelle_innen($neuHex), 'wrap', 'rc');
    $k['pat_key_check'] = substr(hash('sha256', 'edk-ckchk:' . $neuHex), 0, 32);
    $k['kp_privat'] = $um((string)$k['kp_privat'], 'kp', '', 'Das Kontopaar');
    $fx['konto'] = $k;
    if (isset($fx['daten']['pat_key_check'])) { $fx['daten']['pat_key_check'] = $k['pat_key_check']; }
    foreach ($fx['daten']['missions'] as &$m) {
        if (isset($m['pat_blob']) && $m['pat_blob'] !== null) {
            $m['pat_blob'] = $um((string)$m['pat_blob'], 'pat', (string)$m['client_ref'],
                                 'Der Patientenblock von ' . (string)$m['client_ref']);
        }
    }
    unset($m);
    foreach ($fx['standbuch'] as &$b) {
        $wer = (int)$b['wer'];
        $b['text'] = $wer === 16 ? $um((string)$b['text'], 'geraete', '', 'Das Gerätebuch')
                                 : $um((string)$b['text'], 'stand', (string)$wer, 'Blatt ' . $wer);
    }
    unset($b);
    return $fx;
}

/**
 * Das Kontopaar der Fixture ablegen (Format 4) — der oeffentliche Teil als
 * SPKI, der private als `edk2:` unter dem Inhaltsschluessel der Fixture.
 *
 * ERSETZT, WAS DA IST. Die Geraetehuellen der Fixture sind fuer DIESES Paar
 * verschlossen; ein Paar, das die Anlage frueher selbst angelegt hat (Format
 * 3, `demo_paar_sicherstellen()`), oeffnete keine davon. `kp_ablegen()` legt
 * nur ab, wo keines steht — deshalb wird vorher geraeumt, in derselben
 * Transaktion.
 */
function demo_kontopaar_setzen(PDO $pdo, int $id, array $konto): void
{
    require_once __DIR__ . '/kontopaar_lib.php';
    if (!demo_ist_demo($id)) {
        throw new RuntimeException('demo_kontopaar_setzen() arbeitet nur auf dem Demo-Konto.');
    }
    $pdo->prepare('UPDATE users SET kp_oeffentlich = NULL, kp_privat = NULL, kp_seit = NULL
                    WHERE id = ?')->execute([$id]);
    if (!kp_ablegen($pdo, $id, (string)$konto['kp_oeffentlich'], (string)$konto['kp_privat'])
        || kp_zustand($id, $pdo)['stand'] !== 'da') {
        throw new RuntimeException('Das Schlüsselpaar der Fixture ließ sich nicht ablegen.');
    }
}

/**
 * Den Bestand einer Fixture im Format 4 einspielen: verschlossen, wie er
 * liegt. Erwartet eine offene Transaktion.
 *
 * DERSELBE WEG WIE EINE KONTOSICHERUNG, die die Verwaltung einspielt
 * (`adminbackup_lib.php`): `edbak_restore()` im Zweig 'browser' nimmt
 * Einsatzhuellen und `pat_blob` als Spalten und weist Klartext-Ortsdaten ab;
 * die Stuecke gehen ueber `edbak_spuren_schreiben()` — jedes durch die
 * Pruefschicht. Dazu, was eine Sicherung nicht traegt: die Blaetter des
 * Standbuchs und das Geraetebuch. Sie gehoeren zum Bestand wie die Bloecke,
 * deren Zaehler sie fuehren; ohne sie waere jeder Block dem Blatt voraus,
 * und die Demo-Geraete staenden in keinem Buch.
 *
 * NICHTS DARF FEHLEN. Ein abgewiesenes Stueck oder Blatt bricht den Reset
 * ab (die Transaktion rollt zurueck), statt ein Demo-Konto zu hinterlassen,
 * in dem eine Spur nicht aufgeht.
 */
function demo_bestand_durchreichen(PDO $pdo, int $id, array $fx): array
{
    require_once __DIR__ . '/standbuch_lib.php';
    if (!demo_ist_demo($id)) {
        throw new RuntimeException('demo_bestand_durchreichen() arbeitet nur auf dem Demo-Konto.');
    }
    if (!sb_bereit($pdo)) {
        throw new RuntimeException('Die Fixture im Format 4 braucht die Tabelle des Standbuchs — '
            . 'erst `update.php` aufrufen.');
    }
    foreach ((array)$fx['standbuch'] as $b) {
        $wer = (int)$b['wer'];
        $grund = sb_ablage_pruefen(0, $b['version'] ?? null, $b['text'] ?? null);
        if ($grund !== null || !sb_ablegen($pdo, $id, $wer, 0, (int)$b['version'], (string)$b['text'])['ok']) {
            throw new RuntimeException('Blatt ' . $wer . ' der Fixture ließ sich nicht ablegen'
                . ($grund !== null ? ' (' . $grund . ')' : '') . '.');
        }
    }

    /* Die Vermerke bekommen einen Zeitpunkt vor diesem Reset — wie im Weg bis
     * Format 3 (E-RD-18, F-RD-14, -22). */
    $vorher = gmdate('Y-m-d H:i:s', time() - 1);
    $stats = edbak_restore($id, $fx['daten'], null, 'browser');
    $karte = (array)($stats['spur_karte'] ?? []);
    unset($stats['spur_karte'], $stats['day_map']);
    $liste = [];
    foreach ((array)$fx['spuren'] as $e) {
        $ziel = $karte[(int)($e['spur_ref'] ?? -1)] ?? null;
        if ($ziel === null) {
            throw new RuntimeException('Die GPS-Daten zu ' . (string)($e['client_ref'] ?? '?')
                . ' in der Fixture haben keinen Eintrag im Bestand.');
        }
        $liste[] = ['owner_type' => $ziel['art'], 'owner_id' => $ziel['id'],
                    'stuecke' => (array)($e['stuecke'] ?? [])];
    }
    $spuren = edbak_spuren_schreiben($pdo, $id, $liste);
    if ($spuren['abgelehnt'] || $spuren['geschrieben'] !== count($liste)) {
        throw new RuntimeException('Von ' . count($liste) . ' GPS-Aufzeichnungen der Fixture wurden '
            . $spuren['geschrieben'] . ' geschrieben, ' . count($spuren['abgelehnt'])
            . ' abgelehnt' . ($spuren['abgelehnt'] ? ' (' . $spuren['abgelehnt'][0]['grund'] . ')' : '') . '.');
    }
    schnitte_stempeln($pdo, $id, $vorher);
    $stats['spuren'] = $spuren['geschrieben'];
    return $stats;
}

/* KEIN PAPIERKORB-NACHLAUF.
 *
 * Die Datei fuehrt den Papierkorb (ab Nutzlast 7), und `edbak_restore()`
 * bringt ihn als Papierkorb zurueck. Ein Nachlauf, der per Drehbuch
 * Einsaetze und Diensttage ueber die regulaeren Loeschwege (`trash_lib.php`)
 * in den Papierkorb legte, muesste NACH dem Commit laufen, weil
 * `trash_delete_*()` je eine eigene Transaktion oeffnen und PDO keine
 * verschachtelten kennt — der Reset zerfiele in zwei Schritte, von denen der
 * zweite fehlschlagen kann. So ist der Reset EIN Vorgang in EINER Transaktion, und die Zahlen fuer
 * den Bericht kommen aus den Zaehlern der Einspielroutine (`stats.papierkorb`).
 *
 * Die 90-Tage-Frist stempelt dabei jeder Reset frisch: Das
 * Demo-Konto haelt seinen Papierkorb also von selbst am Leben, ohne Sonderweg.
 */

/* ------------------------------------------------------------------ Loeschen */

/**
 * Demo-Konto vollstaendig entfernen (Kontozeile eingeschlossen).
 *
 * UEBER `konto_loeschen()`. Eine eigene Abschrift des Loeschens vergaesse
 * leicht etwas — die Konto-Backups (das Demo-Konto hat welche; sie blieben
 * als verwaiste Ordner liegen), den Protokolleintrag, `mengen:<id>` in
 * `app_state`. Deshalb nimmt sie den Weg jedes Kontos und raeumt danach nur,
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
