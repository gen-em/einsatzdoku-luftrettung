<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/stammdaten_ui.php';   // sd_zeile(), sd_form()
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/validate_lib.php';   // WRAP_RE, Formatkennung, pruef_rettungsmittel()
require_once __DIR__ . '/diensttag_lib.php';  // dt_bases(), dt_base_erlaubt(), Rollenkatalog
/* TRASH_DAYS fuer die Rueckmeldung der Wiederherstellung (E-S1-08). Kommt
 * ueber demo_lib.php ohnehin mit — aber eine Frist, die auf der Seite steht,
 * darf nicht an einem zufaelligen Umweg haengen. */
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/apk_lib.php';    // APK-Karte des Geraete-Reiters (S4/A1)
require_once __DIR__ . '/geraete_lib.php'; // Art und Modell in der Geraeteliste (S6)
require_once __DIR__ . '/kopplung_lib.php';  // Kopplungssitzungen: Code suchen, beanspruchen (S5)
require_once __DIR__ . '/ratelimit_lib.php'; // Topf `pair_code` an der Code-Eingabe (S5, E-S5-16)
require_once __DIR__ . '/geocoder_lib.php'; // Adresssuche: beide Schalter (S9/AP2, E-S9-05)

/* OHNE `t` DIE ÜBERSICHT (E-P3-11, P3/O2).
 *
 * Das Zahnrad in der Kopfleiste führt seit P3 nicht mehr direkt auf den
 * Reiter „Profil", sondern auf diese Seite ohne Parameter. Auf dem Handy gibt
 * es keine sichtbare Leiste — ohne eine Übersicht käme man dort nur über die
 * Schublade an die übrigen Punkte, und ein Menüpunkt, der ungefragt auf einem
 * beliebigen Unterpunkt landet, sagt nichts darüber, was es sonst noch gibt.
 *
 * Am Desktop steht die Leiste daneben; die Übersicht ist dort die
 * Eingangsseite des Bereichs. */
if (!isset($_GET['t'])) {
    ui_einstellungen_uebersicht();
    exit;
}

$tab = $_GET['t'] ?? 'profil';
/* „stammdaten" war bis Web 6.3.0 der Reiter, der alles trug. Er ist in zwei
 * zerlegt (siehe ui_leiste_einstellungen) — der alte Name bleibt als WEICHE
 * stehen: Er steht in Lesezeichen, in verschickten Links und in älteren
 * Fassungen der Dokumentation. Ein „Seite nicht gefunden" dafür wäre der
 * schlechteste Umgang mit einer Umbenennung. */
if ($tab === 'stammdaten') { $tab = 'standorte'; }
/* „rettungsmittel" ist seit S9/AP5 kein eigener Reiter mehr: Was an einem
 * Standort haengt, steht auf SEINER Seite (`t=standort&s=<id>`). Der alte
 * Name bleibt als Weiche stehen, aus demselben Grund wie „stammdaten" darueber
 * — er steht in Lesezeichen und in aelteren Fassungen der Dokumentation. Wer
 * ihn aufruft, landet auf der Liste und ist einen Klick von dem entfernt, was
 * er suchte. */
if ($tab === 'rettungsmittel') { $tab = 'standorte'; }
if (!in_array($tab, ['profil', 'geraete', 'standorte', 'standort', 'backup'], true)) {
    $tab = 'profil';
}
/* Die Standortseite braucht einen Standort. Ohne `s` — oder mit einem, den
 * diese NutzerIn nicht sieht — waere die Seite leer; dann ist die Liste der
 * richtige Ort, und nicht eine Fehlermeldung ueber eine Kennung.
 *
 * GEPRUEFT WIRD HIER OBEN, NICHT IM MARKUP. Eine Umleitung braucht Kopfzeilen,
 * und die sind fort, sobald das Geruest die erste Zeile geschrieben hat; ein
 * `header()` weiter unten scheiterte still, und die Seite stuende halb da.
 * `dt_base_erlaubt()` ist dieselbe Pruefung, die auch der Diensttag benutzt —
 * eigener Standort oder ausgewaehlter zentraler. */
$seiteBase = 0;
if ($tab === 'standort') {
    $seiteBase = (int)dt_base_erlaubt(db(), $userId, (int)($_GET['s'] ?? 0));
    if ($seiteBase === 0) {
        header('Location: einstellungen.php?t=standorte');
        exit;
    }
}
$notice = null; $error = null; $pwGewechselt = false; $newKey = null;

/* DER TON DER HINWEISZEILE (S5 Paket B). Bis hierher war er fest 'info'. Die
 * abgeschlossene Kopplung ist aber ein Vollzug und kein Hinweis — sie bekommt
 * 'ok' mit dem Haken. Vorgabe bleibt 'info', jede vorhandene Meldung sieht aus
 * wie bisher. */
$noticeTon = 'info';

/* ---- Die beiden Zustaende der Karte „Gerät koppeln" (S5, E-S5-01) ---------
 *
 * $koppelSitzung  Zustand 2: Der eingegebene Code passt zu einer offenen
 *                 Sitzung. Die Karte zeigt Art, Modell und Kennung und fragt
 *                 nach — beansprucht ist noch nichts.
 * $koppelWarten   Zustand 3: Die Sitzung gehoert jetzt diesem Konto, das
 *                 Geraet ist am Zug. Die Karte wartet und laedt nach
 *                 (E-S5-53). Der Zustand haengt an $_SESSION['pair_warten']
 *                 und ueberlebt damit ein Neuladen, ohne im Adressfeld zu
 *                 stehen.
 *
 * Beide null: die Karte zeigt ihren Regelfall, das Eingabefeld. */
$koppelSitzung = null; $koppelWarten = null;

/* EIN FEHLER AUS EINEM DIALOG WIRD NICHT UMGELEITET (E-S9-19, S9/AP5-4).
 *
 * Bis Web 16.3.0 ging jeder Fehler denselben Weg wie jede Meldung: in die
 * Sitzung, Umleitung, Kasten am Seitenkopf. Fuer ein Formular unter der Liste
 * war das richtig — es stand nach dem Neuladen wieder da, mit seinen Werten.
 * Ein Dialog steht nach dem Neuladen NICHT wieder da: Er waere zu, die
 * Eingabe waere weg, und oben stuende „Bezeichnung fehlt." ueber einer Liste,
 * in der man gerade nichts eingegeben hat.
 *
 * Deshalb ist der Fehlerweg ein anderer: keine Umleitung, die Seite IST die
 * Antwort auf den POST, der Dialog traegt Eingabe und Meldung, und das
 * Seitenskript oeffnet ihn beim Laden (`window.edDialog.auf`). Der Preis ist
 * die Neuladen-Warnung des Browsers — sie trifft genau den Fall, in dem
 * ohnehin niemand neu laedt, sondern die Eingabe berichtigt.
 *
 * `$_POST` ist die Vorbelegung: die verworfenen Werte, nicht der Bestand.
 * Ungeprueft ins Markup geht davon nichts — jeder Wert laeuft durch `ui_e()`.
 */
$dlgFehler = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---- Demo-Konto: die Identitaet ist gesperrt (E-P1-19) -------------
     *
     * GESPERRT IST AUSSCHLIESSLICH DIE IDENTITAET. Alles andere bleibt
     * offen — Stammdaten, Geraete, Kopplung, Einsaetze: Die Anwendung soll
     * ausprobierbar sein, das ist der Zweck des Kontos.
     *
     * Warum ueberhaupt sperren, wenn der Reset ohnehin alles zurueckholt?
     * Weil zwischen zwei Ruecksetzungen bis zu dreissig Minuten liegen. Wer
     * in dieser Zeit E-Mail oder Passwort aendert, sperrt die naechste
     * Besucherin aus — und die findet ein Konto vor, dessen oeffentliche
     * Zugangsdaten nicht mehr stimmen, ohne zu erfahren warum.
     *
     * Der Hinweis nennt den Grund und ist freundlich: Es ist kein
     * Fehlverhalten, das auszuprobieren. */
    if (in_array($action, ['profile', 'password'], true) && demo_ist_demo($userId)) {
        $error = 'Im Demo-Konto lassen sich E-Mail-Adresse und Passwort nicht '
               . 'ändern — sie sind öffentlich und müssen es bleiben, damit '
               . 'die nächste Besucherin hereinkommt. Alles andere darfst du '
               . 'gern ausprobieren; spätestens nach 30 Minuten ist ohnehin '
               . 'wieder der Ausgangszustand hergestellt.';
        $action = '';
    }

    /* ---- Datenschutz: der Kontoschalter der Adresssuche ----------------
     *
     * EIGENE HANDLUNG, EIGENES FORMULAR — und das ist keine Formfrage,
     * sondern eine Notwendigkeit: Der Wächter darüber sperrt das Profil des
     * Demo-Kontos, weil dessen E-Mail-Adresse und Passwort öffentlich bleiben
     * müssen. Stünde der Schalter in demselben Formular, könnte ausgerechnet
     * das Konto, an dem alle die Anwendung ausprobieren, seine eigene
     * Adresssuche nicht abschalten — gemessen am 07.09.2026 mit der
     * Klickprobe: Häkchen gesetzt, „Profil speichern" gedrückt, Spalte
     * unverändert 1, dazu die Fehlermeldung des Demo-Wächters. Der Satz
     * „Alles andere darfst du gern ausprobieren" hätte dann nicht mehr
     * gestimmt.
     *
     * Dieselbe Trennung wie in `betrieb_server.php` (Karte „Adresssuche"):
     * Ein Tippfehler in der E-Mail-Adresse soll den Schalter nicht mit
     * abweisen, und umgekehrt.
     *
     * DIE MARKE `adresssuche_da` BLEIBT. Ein ausgegrautes Kästchen sendet
     * nichts, und „nichts gesendet" heißt bei einer Checkbox „aus" — ohne die
     * Marke schaltete ein Klick auf „Speichern" bei abgeschalteter
     * Installation den Kontowert still ab, der beim nächsten Einschalten dann
     * aus gewesen wäre. Sie steht nur dort im Markup, wo der Schalter auch
     * bedienbar ist. */
    if ($action === 'datenschutz') {
        if (!empty($_POST['adresssuche_da'])) {
            $notice = geocoder_konto_setzen($userId, !empty($_POST['adresssuche']))
                ? 'Datenschutz gespeichert.'
                : 'Der Schalter konnte nicht gespeichert werden — die Spalte '
                . 'fehlt noch. Eine Administratorin muss update.php aufrufen.';
        } else {
            $notice = 'Es gab nichts zu ändern.';
        }
    }

    /* ---- Profil: Name & E-Mail ---------------------------------------- */
    if ($action === 'profile') {
        $name  = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $email = email_pruefen($_POST['email'] ?? '');
        /* Ein unbekannter Wert wird zum Leerstring, nicht zum Fehler: Er
           bedeutet „Standard der Installation", und das ist der harmlose
           Ausgang. Die Liste steht in session_lib.php, damit Prüfung und
           Auflösung dieselbe Quelle haben (E-P3-20). */
        $logo = (string)($_POST['logo_wahl'] ?? '');
        if (!in_array($logo, LOGO_WAHLEN, true)) { $logo = ''; }
        /* ---- Adresswechsel nur mit Passwortnachweis (Backlog Nr. 128, K-7)
         *
         * Bis Web 15.5.2 schrieb dieses Formular die Anmeldeadresse allein mit
         * dem CSRF-Token um. Wer eine offene Sitzung uebernahm — geliehener
         * Rechner, gestohlenes Cookie —, konnte die Adresse auf seine eigene
         * setzen, sich den Setz-Link schicken lassen und das Konto uebernehmen.
         * Die geschuetzten Angaben blieben zwar zu (der Reset-Weg verlangt den
         * Wiederherstellungsschluessel), aber die Klartextfelder nicht, und die
         * rechtmaessige Besitzerin war ausgesperrt.
         *
         * Derselbe Nachweis wie beim Passwortwechsel: das aus dem aktuellen
         * Passwort abgeleitete Token. Der Server sieht das Passwort auch hier
         * nicht.
         *
         * NUR BEIM WECHSEL. Name und Logo sind harmlos; wer sie aendert, soll
         * dafuer nicht sein Passwort tippen. Der Vergleich laeuft gegen die
         * normalisierte Adresse, damit "Max@..." gegen "max@..." kein Wechsel
         * ist (email_pruefen normalisiert bereits).
         *
         * `session_epoch` bleibt UNVERAENDERT: Es hat sich kein Passwort
         * geaendert, und wer seine Adresse berichtigt, will nicht ueberall
         * abgemeldet werden. */
        $adressWechsel = ($email !== null && $email !== email_normalisieren($userEmail));
        $nachweisOk = true;
        if ($adressWechsel) {
            $stp = db()->prepare('SELECT password_hash FROM users WHERE id = ?');
            $stp->execute([$userId]);
            $hash = (string)($stp->fetchColumn() ?: '');
            $nachweisOk = $hash !== ''
                && password_verify((string)($_POST['old_token'] ?? ''), $hash);
        }

        if ($email === null) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben (höchstens 190 Zeichen).';
        } elseif (!$nachweisOk) {
            $error = 'Zum Ändern der E-Mail-Adresse ist das aktuelle Passwort nötig — '
                   . 'es war leer oder falsch. Es wurde nichts geändert.';
        } else {
            $altAdresse = (string)$userEmail;
            try {
                db()->prepare('UPDATE users SET name = ?, email = ?, logo_wahl = ? WHERE id = ?')
                    ->execute([$name !== '' ? $name : null, $email, $logo, $userId]);
                $userName = $name !== '' ? $name : null;
                $userEmail = $email;
                $logoWahl  = $logo;
                if ($adressWechsel) { profil_adresswechsel_melden($altAdresse, $email); }
                /* Sofort wirksam, ohne Neuanmeldung: Wer die Wahl ändert,
                   soll das Ergebnis auf derselben Seite sehen. Bei
                   „wechselnd" fällt hier ein neuer Würfel — das ist richtig,
                   denn eine Wahl IST eine Gelegenheit zu würfeln. */
                logo_sitzung_setzen($logo);
                $notice = 'Profil gespeichert.';
            } catch (PDOException $ex) {
                /* NUR der Schluesselkonflikt heisst "bereits verwendet" (M1-16).
                 * Jeder andere Datenbankfehler bekommt eine ehrliche Meldung —
                 * "diese Adresse wird bereits verwendet" bei einer vollen
                 * Platte kostet mehr Zeit als gar keine Meldung. */
                if (ist_dublettenfehler($ex)) {
                    $error = 'Diese E-Mail-Adresse wird bereits verwendet.';
                } else {
                    error_log('profil speichern: ' . $ex->getMessage());
                    $error = 'Das Profil konnte nicht gespeichert werden. '
                           . 'Es wurde nichts geändert.';
                }
            }
        }
    }

    /* ---- Profil: Passwort (nur mit korrektem alten Passwort) ----------- */
    if ($action === 'password') {
        // Browser-Krypto: alt wird per Token (oder Alt-Passwort) belegt,
        // neu kommt als Token+Salt; bei aktivem Modul zusaetzlich der neu
        // verpackte Inhaltsschluessel (Server sieht weiterhin nichts).
        $st = db()->prepare('SELECT password_hash, pat_key_check FROM users WHERE id = ?');
        $st->execute([$userId]);
        $u = $st->fetch();
        $oldOk = password_verify((string)($_POST['old_token'] ?? ''),
                                 (string)$u['password_hash']);
        $newTok = (string)($_POST['new_token'] ?? '');
        $newSalt = (string)($_POST['new_salt'] ?? '');
        $wrapPw = (string)($_POST['wrap_pw'] ?? '');
        $keyChk = (string)($_POST['key_check'] ?? '');
        $newIter = (int)($_POST['new_iter'] ?? 0);
        // Gespeicherte Pruefsumme des Inhaltsschluessels (NULL bei Altbestand)
        $chkSoll = $u['pat_key_check'] ?? null;
        if (!$oldOk) {
            $error = 'Das aktuelle Passwort ist nicht korrekt.';
        } elseif (!preg_match('/^[0-9a-f]{64}$/', $newTok)
                  || !preg_match('/^[0-9a-f]{32}$/', $newSalt)) {
            $error = 'Passwortwechsel unvollständig (JavaScript nötig).';
        } elseif ($newIter !== KDF_ITER_ZIEL) {
            /* Nur der Zielwert (M2-01). Ein Passwortwechsel baut die Ableitung
             * vollstaendig neu auf; es gibt keinen Grund, dabei auf einem
             * Altwert zu landen. Eine frei waehlbare Rundenzahl waere ein Weg,
             * das eigene Konto auf einen absurd niedrigen Wert zu setzen — die
             * Anmeldung liefe weiter, und niemand saehe es. */
            $error = 'Die Rundenzahl der Schlüsselableitung ist unbrauchbar — '
                   . 'das Passwort wurde NICHT geändert.';
        } elseif ($patReady && !preg_match(WRAP_RE, $wrapPw)) {
            // Frueher wurde die Huelle hier stillschweigend uebersprungen —
            // das Passwort galt dann, die Daten waren aber nicht mehr lesbar.
            // Jetzt wird gar nichts geaendert.
            $error = 'Der Inhaltsschlüssel konnte nicht umgepackt werden — '
                   . 'das Passwort wurde NICHT geändert. Bitte Seite neu laden '
                   . 'und erneut versuchen.';
        } elseif ($patReady && $keyChk !== '' && !preg_match('/^[0-9a-f]{32}$/', $keyChk)) {
            $error = 'Die Prüfsumme des Inhaltsschlüssels ist unbrauchbar — '
                   . 'das Passwort wurde NICHT geändert.';
        } elseif ($patReady && $chkSoll !== null && $keyChk !== $chkSoll) {
            /* DIE ENTSCHEIDENDE PRUEFUNG (M1-12).
             *
             * Der Server kann die neue Huelle nicht oeffnen — er kennt den
             * Schluessel nicht. Er kann bisher also NICHT erkennen, ob darin
             * wirklich derselbe Inhaltsschluessel steckt. Enthielte sie einen
             * anderen, waere anschliessend JEDER vorhandene Datensatz
             * unlesbar, und zwar endgueltig: Die alte Huelle ist dann
             * ueberschrieben.
             *
             * Mit der Pruefsumme (im Browser gerechnet, siehe
             * EdCrypto.contentKeyCheck) laesst sich genau dieser eine Fehler
             * erkennen, ohne dass der Server etwas ueber den Schluessel lernt:
             * Er vergleicht zwei Hashwerte.
             *
             * Bestandskonten haben keine gespeicherte Pruefsumme
             * (pat_key_check IS NULL). Sie werden weiter angenommen und
             * bekommen sie unten beim Speichern — sonst waeren sie ausgesperrt,
             * denn der Server kann sie nicht nachtraeglich berechnen.
             */
            $error = 'Der umgepackte Inhaltsschlüssel gehört nicht zu diesem Konto — '
                   . 'das Passwort wurde NICHT geändert. Bitte die Seite neu laden '
                   . 'und erneut versuchen. Sollte das wiederholt auftreten, bitte '
                   . 'nichts weiter unternehmen, bevor ein Backup erstellt ist.';
        } else {
            // Passwort und Huelle gemeinsam — sonst entstuende ein Konto, das
            // sich zwar anmelden laesst, dessen Angaben aber unlesbar waeren.
            $pdo = db();
            $pdo->beginTransaction();
            try {
                /* Sitzungszaehler mit erhoehen (M1-09/D6).
                 *
                 * Wer sein Passwort wechselt, weil er Missbrauch vermutet,
                 * will genau eines erreichen: dass der andere draussen ist.
                 * Das Passwort allein erreicht das nicht — eine offene
                 * Sitzung haengt am Sitzungscookie. Der erhoehte Zaehler
                 * beendet jede Sitzung dieses Kontos, die noch den alten
                 * Stand traegt (auth_guard.php).
                 *
                 * IN DERSELBEN TRANSAKTION wie das Passwort: Ein erhoehter
                 * Zaehler ohne geaendertes Passwort spuelte alle Sitzungen
                 * hinaus, ohne dass etwas geschehen waere; ein geaendertes
                 * Passwort ohne erhoehten Zaehler ist genau der Zustand, den
                 * dieser Befund beschreibt. */
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                kdf_iter = ?,
                                                session_epoch = session_epoch + 1
                               WHERE id = ?')
                    ->execute([password_hash($newTok, PASSWORD_DEFAULT), $newSalt,
                               $newIter, $userId]);
                if ($patReady) {
                    // Pruefsumme mitschreiben: Bestandskonten bekommen sie
                    // hier erstmals, alle anderen bestaetigen den alten Wert.
                    /* Kein Abschneiden mehr (M2-08).
                     *
                     * mb_substr(..., 0, 4000) konnte nie greifen: WRAP_RE
                     * laesst hoechstens 4000 Zeichen durch, laengere Eingaben
                     * sind vorher abgewiesen. Toter Code — aber gefaehrlicher
                     * toter Code. Wuerde die Obergrenze der Pruefung je
                     * angehoben, ohne dass jemand an diese Zeile denkt,
                     * schnitte sie die Schluesselhuelle stillschweigend ab.
                     * Eine abgeschnittene Huelle laesst sich nicht mehr
                     * oeffnen, und auffallen wuerde es erst beim naechsten
                     * Anmelden — dann sind die Patientenangaben verloren.
                     *
                     * Die Laenge gehoert in die Pruefung, nicht ins
                     * Speichern. Dort steht sie. */
                    $pdo->prepare('UPDATE users SET pat_wrap_pw = ?, pat_key_check = ? WHERE id = ?')
                        ->execute([$wrapPw, $keyChk !== '' ? $keyChk : null, $userId]);
                }
                /* Offene Links zum Zuruecksetzen entwerten. Sie sind bis zu
                 * einer Stunde gueltig und haetten den soeben gewaehlten
                 * Zustand wieder ueberschrieben — mit einem Passwort, das
                 * jemand anders kennt. */
                $pdo->prepare('UPDATE password_resets SET used_at = NOW()
                               WHERE user_id = ? AND used_at IS NULL')
                    ->execute([$userId]);
                $pdo->commit();

                /* Die EIGENE Sitzung zieht den neuen Stand mit und bleibt
                 * bestehen (Abnahmekriterium A5: "alle ANDEREN Sitzungen").
                 * Der Browser hat den neuen Datenschluessel in diesem Moment
                 * bereits gesetzt; die handelnde Person hier abzumelden waere
                 * kein Sicherheitsgewinn, sondern nur laestig. */
                $st2 = $pdo->prepare('SELECT session_epoch FROM users WHERE id = ?');
                $st2->execute([$userId]);
                $_SESSION['epoch'] = (int)$st2->fetchColumn();

                $notice = 'Passwort geändert. Alle anderen offenen Sitzungen dieses '
                        . 'Kontos sind damit beendet; noch offene Links zum '
                        . 'Zurücksetzen sind ungültig.';
                /* Signal fuer das Browser-Skript (M2-07): Erst JETZT darf es
                 * den neuen Datenschluessel uebernehmen. */
                $pwGewechselt = true;
            } catch (Throwable $ex) {
                $pdo->rollBack();
                $error = 'Passwortwechsel fehlgeschlagen. Es wurde nichts geändert.';
            }
        }
    }

    /* ---- Geräte (Selbstverwaltung) ------------------------------------- */
    if ($action === 'add') {
        // Dieselbe Obergrenze wie beim Koppeln (MAX_GERAETE, db.php). Sie an
        // nur einem der beiden Wege zu pruefen hiesse, sie gar nicht zu haben.
        if (geraete_grenze_erreicht(db(), $userId)) {
            $error = 'Es sind bereits ' . MAX_GERAETE . ' Geräte mit diesem Konto verbunden. '
                   . 'Bitte zuerst ein nicht mehr genutztes Gerät löschen — Deaktivieren '
                   . 'genügt nicht, die Zugangsdaten bleiben dabei bestehen.';
        } else {
            $label = trim($_POST['label'] ?? '');
            /* SECHZEHN ZUFALLSBYTES WIE BEI DER KOPPLUNG (B-S5-01, S5 Paket B).
             *
             * Hier standen vier. Zwei Wege fuehrten damit zu derselben Spalte,
             * der eine mit 32, der andere mit 128 Bit — und der schwaechere war
             * ausgerechnet der, den niemand pruefte: Bei der Kopplung faengt
             * der eindeutige Schluessel eine Dublette ab und die Uhr versucht
             * es erneut; hier bekaeme die NutzerIn einen Datenbankfehler. Die
             * Begruendung fuer 16 steht in pair.php (M4-08, Geburtstags-
             * problem). Bestandsgeraete behalten ihre kurze Kennung — die
             * Spalte ist VARCHAR(64), es ist keine Migration noetig, und
             * geraet_kennung_kurz() kommt mit beiden Laengen zurecht. */
            $devId = 'dev-' . bin2hex(random_bytes(16));
            $key   = bin2hex(random_bytes(24));
            db()->prepare('INSERT INTO devices (user_id, device_id, api_key_hash, label) VALUES (?,?,?,?)')
                ->execute([$userId, $devId, geraet_schluessel_hash($key), $label ?: null]);
            $newKey = ['device_id' => $devId, 'api_key' => $key];
            $notice = 'Gerät angelegt. Schlüssel unten JETZT notieren — er wird nur einmal angezeigt.';
        }
    }
    if ($action === 'toggle') {
        db()->prepare('UPDATE devices SET active = 1 - active WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Status geändert.';
    }
    /* ---- Kopplung: den Code vom Gerät entgegennehmen (S5, E-S5-01) -------
     *
     * DER ABLAUF HAT SICH UMGEDREHT. Bis Web 12.9.4 erzeugte diese Seite einen
     * Code, und die Uhr tippte ihn. Jetzt zeigt das GERAET den Code, ein Mensch
     * tippt ihn hier, und das Geraet hat das letzte Wort: Es sieht die
     * maskierte Adresse des Kontos und sagt Ja oder Nein.
     *
     * ZWEI SCHRITTE, ZWEI AKTIONEN, und das ist kein Umweg, sondern das erste
     * der beiden Tore aus E-S5-05: `koppeln_pruefen` SUCHT die Sitzung und
     * zeigt, was da koppeln will (Art, Modell, Kennung); `koppeln_bestaetigen`
     * BINDET sie ans Konto. Wer nur den Code hat, hat damit noch nichts
     * ausgeloest — er sieht ein Geraet und kann abbrechen. Das zweite Tor steht
     * am Geraet.
     *
     * WAS ZAEHLT UND WAS NICHT (E-S5-17). Gezaehlt wird im Topf `pair_code`,
     * je Konto UND je Adresse:
     *   - Code passt nicht zum Muster  -> zaehlt NICHT. Die Datenbank wurde
     *     nicht gefragt; es ist nichts zu erraten, nur ein Vertipper.
     *   - Code nicht gefunden          -> zaehlt. Das ist der Rateversuch.
     *   - Sitzung dazwischen weg       -> zaehlt. Von aussen nicht von einem
     *     Rateversuch zu unterscheiden, und selten genug.
     *
     * GELEERT WIRD DER TOPF ERST BEIM BEANSPRUCHEN, nicht schon beim Finden —
     * und das weicht bewusst vom Wortlaut in Z-08 ab (E-S5-54). Ein Treffer im
     * Suchschritt verbraucht die Sitzung naemlich nicht: Wer sich ueber
     * `pair.php?aktion=start` selbst eine Sitzung holt, kennt einen gueltigen
     * Code, den er beliebig oft eingeben kann — und haette damit alle zehn
     * Versuche zurueckgesetzt, so oft er will. Das Beanspruchen dagegen
     * verbraucht die Sitzung; jedes Zuruecksetzen kostet dann ein neues
     * `start`, und das begrenzt der Topf `pair_start`.
     */
    if ($action === 'koppeln_pruefen' || $action === 'koppeln_bestaetigen') {
        /* Das Merkmal ist die KONTOKENNUNG, nicht die E-Mail-Adresse: Sie ist
         * ebenso eindeutig und legt keine Adresse in einer Tabelle ab, die zum
         * Zaehlen da ist. rate_merkmale() haengt die IP-Adresse selbst an. */
        $koppelKonto = (string)$userId;

        if (geraete_grenze_erreicht(db(), $userId)) {
            // Schon hier abfangen: Die Sitzung waere sonst beansprucht, und
            // das Geraet liefe beim Ja in ein 409 (pair.php prueft erneut).
            $error = 'Es sind bereits ' . MAX_GERAETE . ' Geräte mit diesem Konto verbunden. '
                   . 'Bitte zuerst ein nicht mehr genutztes Gerät löschen — dann lässt sich '
                   . 'wieder ein Gerät koppeln.';
        } elseif (!rate_erlaubt('pair_code', $koppelKonto)) {
            $bis = rate_gesperrt_bis('pair_code', $koppelKonto);
            $error = 'Zu viele falsche Codes.'
                   . ($bis !== null
                      ? ' Bis ' . fmt_local($bis, 'H:i') . ' Uhr nimmt der Server keine Eingabe von dir an.'
                      : ' Bitte später erneut versuchen.');
        } else {
            $koppelCode = pair_code_normalisieren((string)($_POST['code'] ?? ''));
            if (!preg_match(PAIR_RE, $koppelCode)) {
                $error = 'Ein Code hat ' . PAIR_LEN . ' Zeichen; 0, O, 1 und I kommen darin '
                       . 'nicht vor. Bitte vergleiche mit der Anzeige auf dem Gerät.';
            } else {
                $sitzung = pair_sitzung_nach_code(db(), $koppelCode);
                if ($sitzung === null) {
                    rate_misserfolg('pair_code', $koppelKonto);
                    $error = 'Diesen Code kennt der Server nicht — er ist falsch, abgelaufen '
                           . 'oder schon verwendet. Auf dem Gerät einen neuen Code holen: '
                           . 'Sync-Seite → Gerät koppeln.';
                } elseif ($action === 'koppeln_pruefen') {
                    $koppelSitzung = $sitzung;
                } elseif (!pair_sitzung_beanspruchen(db(), $koppelCode, $userId)) {
                    /* Zwischen Suchen und Beanspruchen ist die Sitzung weg —
                     * abgelaufen, am Gerät verworfen oder von einem zweiten
                     * Browserfenster genommen. Die Datenbank hat entschieden
                     * (E-S5-13), und dieselbe Meldung wie oben genuegt: Der
                     * Unterschied ginge nur einen Angreifer etwas an. */
                    rate_misserfolg('pair_code', $koppelKonto);
                    $error = 'Diesen Code kennt der Server nicht — er ist falsch, abgelaufen '
                           . 'oder schon verwendet. Auf dem Gerät einen neuen Code holen: '
                           . 'Sync-Seite → Gerät koppeln.';
                } else {
                    /* ---- UMLEITUNG STATT DIREKTER ANZEIGE (E-S5-53) --------
                     *
                     * Der Wartezustand ist der einzige auf dieser Seite, der
                     * MINUTEN dauert und sich von selbst aendert: Das Geraet
                     * sagt Ja, und die Seite soll es merken. Ein Neuladen — vom
                     * Skript oder von Hand — darf deshalb nicht die Rueckfrage
                     * „Formular erneut senden?" ausloesen und schon gar nicht
                     * ein zweites Mal beanspruchen. Nach dem POST also eine
                     * Umleitung, und der Zustand liegt in der Sitzung, nicht im
                     * Adressfeld: Die Gerätekennung ist kein Geheimnis, aber
                     * sie hat auch nichts im Verlauf des Browsers zu suchen. */
                    rate_erfolg('pair_code', $koppelKonto);
                    $_SESSION['pair_warten'] = (string)$sitzung['device_id'];
                    $_SESSION['flash_notice'] = 'Der Code ist deinem Konto zugeordnet. '
                                              . 'Bestätige jetzt am Gerät mit Ja.';
                    header('Location: einstellungen.php?t=geraete#koppeln');
                    exit;
                }
            }
        }
    }
    if ($action === 'koppeln_abbrechen') {
        /* Der Rueckweg aus dem Wartezustand. Ohne ihn saehe eine Person, die
         * es sich anders ueberlegt, bis zum Ablauf der Frist eine Karte, die
         * auf ein Geraet wartet, das nie kommt — und die Sitzung bliebe
         * beansprucht. */
        $kw = (string)($_SESSION['pair_warten'] ?? '');
        if ($kw !== '') { pair_sitzung_verwerfen(db(), $kw, $userId); }
        unset($_SESSION['pair_warten']);
        $_SESSION['flash_notice'] = 'Die Kopplung ist abgebrochen. Wenn du das Gerät doch '
                                  . 'verbinden willst, hol dir dort einen neuen Code.';
        header('Location: einstellungen.php?t=geraete#koppeln');
        exit;
    }
    if ($action === 'rename') {
        $lbl = mb_substr(trim($_POST['label'] ?? ''), 0, 120);
        db()->prepare('UPDATE devices SET label = ? WHERE id = ? AND user_id = ?')
            ->execute([$lbl !== '' ? $lbl : null, (int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Bezeichnung gespeichert.';
    }
    if ($action === 'delete') {
        // FK setzt device_id in Einsaetzen/Segmenten auf NULL -> Daten bleiben
        db()->prepare('DELETE FROM devices WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Gerät gelöscht. Bereits hochgeladene Daten bleiben erhalten.';
    }

    /* ---- Standorte und ihre Stammdaten ------------------------------------
     *
     * DER STANDORT IST DER ANKER (E15). Jedes Rettungsmittel, jede Zielklinik,
     * jede Besatzungs-Vorbelegung, jedes weitere Rettungsmittel und jede
     * Bergwacht-Bereitschaft gehoert GENAU EINEM Standort. Eine zweite,
     * standortuebergreifende Ebene gibt es bewusst nicht — der Preis ist
     * Doppelpflege, der Gewinn ein Modell mit einer Regel statt mit zwei.
     *
     * Jede der Speicheraktionen unten prueft deshalb zuerst den Standort, und
     * zwar mit dt_base_erlaubt(): Zulaessig sind die eigenen und die
     * AUSGEWAEHLTEN zentralen (E16). Dieselbe Pruefung entscheidet in
     * api/day.php, welche Zuordnung ein Diensttag annehmen darf — zwei
     * verschiedene Fassungen davon waeren die Stelle, an der beide
     * auseinanderlaufen.
     */
    $sdBase = static function (): ?int {
        global $userId;
        return dt_base_erlaubt(db(), $userId, isset($_POST['base_id']) ? (int)$_POST['base_id'] : null);
    };
    /* DIE KENNUNG DER GESCHRIEBENEN ZEILE (S9/AP5-4). Sie wird gebraucht,
     * damit die Umleitung auf `#veh-7` zeigt und `:target` die neue Zeile
     * faerbt — ohne sie gaebe es nach dem Anlegen keine Rueckmeldung ausser
     * einem Satz am Seitenkopf, und genau den nimmt E-S9-19 weg.
     *
     * DER FALL `lastInsertId() === 0` IST KEIN ERFOLG. Die vier einfachen
     * Listen schreiben mit `INSERT IGNORE`; greift der Eindeutigkeitsschluessel
     * (`uq_user_base_role_name` und Geschwister), fuegt MySQL nichts ein und
     * meldet trotzdem keinen Fehler. Bis Web 16.3.0 sagte die Anwendung dann
     * „Eintrag gespeichert." und es war keiner gespeichert. Jetzt sagt sie,
     * dass es ihn schon gibt. */
    $zielId = null;
    /* Die Kennung eines NEU angelegten Standorts — die Umleitung fuehrt auf
     * seine Seite (E-S9-19), nicht auf die Liste, aus der er entstanden ist. */
    $baseNeu = null;
    $sdNeuId = static function (string $dublettenmeldung) use (&$error): ?int {
        $id = (int)db()->lastInsertId();
        if ($id > 0) { return $id; }
        $error = $dublettenmeldung;
        return null;
    };
    /* DAS GEGENSTUECK ZUM ANLEGEN. Beim Anlegen faengt `INSERT IGNORE` die
     * Dublette ab (oben); beim AENDERN gibt es kein `UPDATE IGNORE`, das
     * etwas Vernuenftiges taete — es wuerfe die Zeile still weg. Also die
     * Ausnahme fangen und dieselbe Meldung geben. Ohne diesen Zweig endete
     * ein Umbenennen auf einen vorhandenen Namen in einer nicht gefangenen
     * PDOException. */
    $sdAendern = static function (string $tabelle, string $satz, array $werte,
                                  int $id, string $dublettenmeldung) use (&$error, $userId): ?int {
        try {
            db()->prepare('UPDATE ' . $tabelle . ' SET ' . $satz . ' WHERE id = ? AND user_id = ?')
                ->execute([...$werte, $id, $userId]);
            return $id;
        } catch (PDOException $ex) {
            $error = ist_dublettenfehler($ex) ? $dublettenmeldung
                   : 'Der Eintrag konnte nicht gespeichert werden.';
            return null;
        }
    };
    if ($action === 'base_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $bid = (int)($_POST['id'] ?? 0);
        /* Optionale Koordinate (E37/E39). Die Regeln — nur zusammen, ausserhalb
         * des Bereichs leer, Komma zulaessig — stehen seit Web 6.1.0 an EINER
         * Stelle (pruef_ortspaar in validate_lib.php). Vorher gab es dieselbe
         * kleine Umrechnung dreimal: hier, in admin_stammdaten.php und, mit dem
         * Ortsfeld am Einsatz, waere sie ein viertes Mal entstanden. */
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        /* EIN LEERER NAME BEKAM BIS WEB 16.3.0 KEINE ANTWORT (F-S9-U-28).
         * Die Bedingung lautete `if ($n !== '')` — ohne `else`. Wer das Feld
         * leer liess und absendete, sah die Seite neu geladen, keinen neuen
         * Standort und keine Meldung. Das `required` im Markup faengt den
         * Regelfall ab; es ist keine Pruefung, sondern eine Bequemlichkeit. */
        if ($n === '') {
            $error = 'Bitte einen Namen eintragen.';
        } elseif (stammdaten_dup_global('bases', 'name', $n)) {
            $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
        } elseif ($bid > 0) {
            db()->prepare('UPDATE bases SET name = ?, lat = ?, lon = ? WHERE id = ? AND user_id = ?')
                ->execute([$n, $lat, $lon, $bid, $userId]);
            $notice = 'Standort gespeichert. Bereits dokumentierte Diensttage bleiben unverändert.';
        } else {
            db()->prepare('INSERT IGNORE INTO bases (user_id, name, lat, lon) VALUES (?,?,?,?)')
                ->execute([$userId, $n, $lat, $lon]);
            /* „STANDORT ANLEGEN" LANDET AUF DER NEUEN SEITE (E-S9-19). Sie ist
             * die Bestaetigung — und zugleich der Ort, an dem als Naechstes
             * etwas zu tun ist: Ein Standort ohne Rettungsmittel ist ein leeres
             * Fach. Eine Meldung „Standort gespeichert." ueber der Liste sagte
             * dasselbe und liess einen dort stehen.
             * `INSERT IGNORE` schweigt bei einer Dublette (eigener Bestand,
             * `uq_user_base_name`); `$sdNeuId()` macht daraus eine Meldung. */
            $baseNeu = $sdNeuId('Einen eigenen Standort dieses Namens gibt es schon.');
        }
    }
    if ($action === 'base_default') {
        $bid = dt_base_erlaubt(db(), $userId, (int)($_POST['id'] ?? 0));
        if ($bid !== null) {
            db()->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"base",?)
                           ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')
                ->execute([$userId, $bid]);
            $notice = 'Standard-Standort gesetzt.';
        }
    }
    if ($action === 'veh_default') {
        $vid = dt_vehicle_erlaubt(db(), $userId, (int)($_POST['id'] ?? 0));
        if ($vid !== null) {
            db()->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"vehicle",?)
                           ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')
                ->execute([$userId, $vid]);
            $notice = 'Standard-Rettungsmittel gesetzt.';
        }
    }
    /* Zentralen Standort aus- oder abwaehlen (E16). Nur ausgewaehlte erscheinen
     * in den Auswahllisten; EIGENE Standorte brauchen keinen Eintrag und gelten
     * immer als ausgewaehlt. */
    if ($action === 'ub_toggle') {
        $bid = (int)($_POST['id'] ?? 0);
        $chk = db()->prepare('SELECT COUNT(*) FROM bases WHERE id = ? AND user_id IS NULL');
        $chk->execute([$bid]);
        if ($chk->fetchColumn()) {
            if (($_POST['an'] ?? '') === '1') {
                db()->prepare('INSERT IGNORE INTO user_bases (user_id, base_id) VALUES (?,?)')
                    ->execute([$userId, $bid]);
                $notice = 'Zentraler Standort ausgewählt.';
            } else {
                db()->prepare('DELETE FROM user_bases WHERE user_id = ? AND base_id = ?')
                    ->execute([$userId, $bid]);
                $notice = 'Zentraler Standort abgewählt. Bereits dokumentierte '
                        . 'Diensttage bleiben unverändert.';
            }
        }
    }
    if ($action === 'base_del') {
        /* DAS LOESCHEN NIMMT DIE STAMMDATEN DES STANDORTS MIT (E15,
         * ON DELETE CASCADE). Diensttage bleiben davon unberuehrt, weil sie ihre
         * Angaben eingefroren haben (E8) — der frueher noetige Umweg, den Namen
         * vorher in `days.base` zu retten, ist damit entfallen.
         *
         * Vor dem Loeschen ist die Zahl der betroffenen Stammdatensaetze
         * anzuzeigen und bestaetigen zu lassen (Konzept 4.2). Die Zahl steht in
         * der Rueckfrage der Oberflaeche; hier wird nur noch geloescht. */
        $bid = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM user_defaults WHERE user_id = ? AND kind = "base" AND item_id = ?')
            ->execute([$userId, $bid]);
        db()->prepare('DELETE FROM bases WHERE id = ? AND user_id = ?')
            ->execute([$bid, $userId]);
        $notice = 'Standort samt seiner Stammdaten gelöscht. Bereits dokumentierte '
                . 'Diensttage bleiben unverändert.';
    }
    if ($action === 'veh_save') {
        $vid = (int)($_POST['id'] ?? 0);
        /* ALLE REGELN STEHEN IN DER PRUEFSCHICHT (Web 16.0.0, E-S9-09).
         * Bis Web 15.9.0 standen sie hier ausgeschrieben — und ein zweites Mal
         * in admin_stammdaten.php, ein drittes Mal (kuerzer) beim Einspielen
         * einer Sicherung. Mit dem Typ waeren daraus drei Fassungen von sieben
         * Regeln geworden. `pruef_rettungsmittel()` liefert den fertigen
         * Datensatz oder je Feld eine Meldung; die Dublettenpruefung bleibt
         * hier, weil sie den Bestand fragt und nicht die Eingabe.
         *
         * DIE ART IST WEITER PFLICHT (Web 7.0.0) — die Begruendung steht jetzt
         * an der Meldung in validate_lib.php: Bis Web 6.3.0 galt ein
         * stillschweigendes „im Zweifel luftgebunden", das an einem Standort
         * mit NEF erst auffiel, wenn im Einsatzformular Windenfelder erschienen. */
        $geprueft = pruef_rettungsmittel([
            'name'    => $_POST['name'] ?? null,
            'kurz'    => $_POST['kurz'] ?? null,
            'typ'     => $_POST['typ']  ?? null,
            'kind'    => $_POST['kind'] ?? null,
            /* DER STANDORT IST EIN AUSWAHLFELD (S9/AP5-4, E-S9-19). Bis
             * Web 16.3.0 stand hier ein Haken „Ohne Standort", der die
             * verborgene Kennung der Standortkarte schlug — man sah beim
             * Setzen nicht, WAS man damit überschrieb, und ein Rettungsmittel
             * von einem Standort auf einen anderen zu verschieben ging gar
             * nicht. Jetzt kommt die Kennung aus dem Feld; „Ohne Standort"
             * ist dessen erster Eintrag mit dem Wert 0, und
             * `dt_base_erlaubt()` macht daraus null. Ob das zum Typ passt,
             * entscheidet weiterhin `pruef_rettungsmittel()`. */
            'base_id' => $sdBase(),
            'roles'   => $_POST['roles'] ?? [],
            'caps'    => $_POST['caps']  ?? [],
        ]);
        $rm = $geprueft['daten'];
        if ($rm === null) {
            $error = reset($geprueft['fehler']) ?: 'Das Rettungsmittel konnte nicht gespeichert werden.';
        } elseif (stammdaten_dup_global('vehicles', 'name', $rm['name'])) {
            $error = '„' . $rm['name'] . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
        } else {
            $rollen = $rm['roles'];
            $caps   = $rm['caps'];

            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($vid > 0) {
                    $pdo->prepare('UPDATE vehicles SET name = ?, kurz = ?, kind = ?, typ = ?, base_id = ?
                                   WHERE id = ? AND user_id = ?')
                        ->execute([$rm['name'], $rm['kurz'], $rm['kind'], $rm['typ'], $rm['base_id'], $vid, $userId]);
                } else {
                    $pdo->prepare('INSERT INTO vehicles (user_id, base_id, name, kurz, kind, typ)
                                   VALUES (?,?,?,?,?,?)')
                        ->execute([$userId, $rm['base_id'], $rm['name'], $rm['kurz'], $rm['kind'], $rm['typ']]);
                    $vid = (int)$pdo->lastInsertId();
                }
                /* Rollen und Faehigkeiten vollstaendig ersetzen. Auf BEREITS
                 * DOKUMENTIERTE Diensttage wirkt das nicht: Ihr Rollensatz steht
                 * eingefroren in `day_crew`, ihr Faehigkeitssatz in
                 * `day_capabilities` (E8). Das Abwaehlen der Winde kostet also
                 * keine vorhandene Windendokumentation (A13e) — es aendert nur,
                 * was NEUE Diensttage dieses Rettungsmittels anbieten. */
                $pdo->prepare('DELETE FROM vehicle_roles WHERE vehicle_id = ?')->execute([$vid]);
                $insR = $pdo->prepare('INSERT IGNORE INTO vehicle_roles (vehicle_id, role_code) VALUES (?,?)');
                foreach ($rollen as $rc) { $insR->execute([$vid, $rc]); }
                $pdo->prepare('DELETE FROM vehicle_capabilities WHERE vehicle_id = ?')->execute([$vid]);
                $insC = $pdo->prepare('INSERT IGNORE INTO vehicle_capabilities (vehicle_id, capability) VALUES (?,?)');
                foreach ($caps as $c) { $insC->execute([$vid, $c]); }
                $pdo->commit();
                $zielId = $vid;
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = ist_dublettenfehler($ex)
                    ? 'Diese Bezeichnung existiert bereits.'
                    : 'Das Rettungsmittel konnte nicht gespeichert werden.';
            }
        }
    }
    if ($action === 'veh_del') {
        /* Kein Retten von Bezeichnungen mehr: Der Diensttag hat sie eingefroren
         * (E8), und der Fremdschluessel steht auf ON DELETE SET NULL. Ein
         * geloeschtes Rettungsmittel beschaedigt damit keine Historie (A4). */
        $vid = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM user_defaults WHERE user_id = ? AND kind = "vehicle" AND item_id = ?')
            ->execute([$userId, $vid]);
        db()->prepare('DELETE FROM vehicles WHERE id = ? AND user_id = ?')
            ->execute([$vid, $userId]);
        $notice = 'Rettungsmittel gelöscht. Bereits dokumentierte Diensttage bleiben '
                . 'unverändert.';
    }
    if ($action === 'crew_save') {
        $cid = (int)($_POST['id'] ?? 0);
        $g = pruef_stammdaten('crew_presets', [
            'name' => $_POST['name'] ?? null, 'rolle' => $_POST['role'] ?? null,
            'base_id' => $sdBase(),
        ]);
        if ($g['daten'] === null) {
            $error = reset($g['fehler']);
        } elseif (stammdaten_dup_global('crew_presets', 'name', $g['daten']['name'],
                                        'role_code', $g['daten']['rolle'])) {
            $error = '„' . $g['daten']['name'] . '“ ist für diese Rolle bereits systemweit '
                   . 'hinterlegt und steht dir automatisch zur Verfügung.';
        } elseif ($cid > 0) {
            /* DIE ROLLE WIRD MITGESCHRIEBEN (S9/AP5-4). Bis Web 16.3.0 stand
             * hier nur `SET name = ?` — richtig, solange die Rolle eine
             * verborgene Kennung im Formular je Rolle war und sich gar nicht
             * ändern konnte. Der Dialog bietet sie als Feld an; ohne diese
             * Spalte hätte eine Rollenänderung wortlos nichts getan.
             *
             * UND SIE KANN JETZT AUF EINE DUBLETTE LAUFEN: Der eindeutige
             * Schlüssel `uq_user_base_role_name` deckt Standort, Rolle und
             * Name; wer „Sabine Ortner" von Pilot 1 auf HEMS-TC schiebt, wo es
             * sie schon gibt, bekam bisher eine Ausnahme bis zur weißen Seite.
             * Dasselbe galt für res, bw und td beim Umbenennen — dort seit
             * jeher, nur seltener getroffen. */
            $zielId = $sdAendern('crew_presets', 'name = ?, role_code = ?',
                [$g['daten']['name'], $g['daten']['rolle']], $cid,
                'Diesen Eintrag gibt es an diesem Standort schon.');
        } else {
            db()->prepare('INSERT IGNORE INTO crew_presets (user_id, base_id, role_code, name)
                           VALUES (?,?,?,?)')
                ->execute([$userId, $g['daten']['base_id'], $g['daten']['rolle'],
                           $g['daten']['name']]);
            $zielId = $sdNeuId('Diesen Eintrag gibt es an diesem Standort schon.');
        }
    }
    if ($action === 'crew_del') {
        $cid = (int)($_POST['id'] ?? 0);
        $rq = db()->prepare('SELECT role_code FROM crew_presets WHERE id = ? AND user_id = ?');
        $rq->execute([$cid, $userId]);
        $role = (string)($rq->fetchColumn() ?: '');
        db()->prepare('DELETE FROM crew_presets WHERE id = ? AND user_id = ?')
            ->execute([$cid, $userId]);
        $notice = 'Eintrag gelöscht.';
    }
    if ($action === 'res_save') {
        $wid = (int)($_POST['id'] ?? 0);
        $g = pruef_stammdaten('resources',
                              ['name' => $_POST['name'] ?? null, 'base_id' => $sdBase()]);
        if ($g['daten'] === null) {
            $error = reset($g['fehler']);
        } elseif (stammdaten_dup_global('resources', 'name', $g['daten']['name'])) {
            $error = '„' . $g['daten']['name'] . '“ ist bereits systemweit hinterlegt und '
                   . 'steht dir automatisch zur Verfügung.';
        } elseif ($wid > 0) {
            $zielId = $sdAendern('resources', 'name = ?', [$g['daten']['name']], $wid,
                'Dieses Rettungsmittel gibt es an diesem Standort schon.');
        } else {
            db()->prepare('INSERT IGNORE INTO resources (user_id, base_id, name) VALUES (?,?,?)')
                ->execute([$userId, $g['daten']['base_id'], $g['daten']['name']]);
            $zielId = $sdNeuId('Dieses Rettungsmittel gibt es an diesem Standort schon.');
        }
    }
    if ($action === 'res_del') {
        // Bereits dokumentierte Einsaetze behalten ihren Eintrag: Die
        // Zuordnung steht als eigener Datensatz und haengt nicht an dieser Liste.
        db()->prepare('DELETE FROM resources WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Rettungsmittel gelöscht.';
    }
    if ($action === 'bw_save') {
        $wid = (int)($_POST['id'] ?? 0);
        $g = pruef_stammdaten('bw_units',
                              ['name' => $_POST['name'] ?? null, 'base_id' => $sdBase()]);
        if ($g['daten'] === null) {
            $error = reset($g['fehler']);
        } elseif (stammdaten_dup_global('bw_units', 'name', $g['daten']['name'])) {
            $error = '„' . $g['daten']['name'] . '“ ist bereits systemweit hinterlegt und '
                   . 'steht dir automatisch zur Verfügung.';
        } elseif ($wid > 0) {
            $zielId = $sdAendern('bw_units', 'name = ?', [$g['daten']['name']], $wid,
                'Diese Bereitschaft gibt es an diesem Standort schon.');
        } else {
            db()->prepare('INSERT IGNORE INTO bw_units (user_id, base_id, name) VALUES (?,?,?)')
                ->execute([$userId, $g['daten']['base_id'], $g['daten']['name']]);
            $zielId = $sdNeuId('Diese Bereitschaft gibt es an diesem Standort schon.');
        }
    }
    if ($action === 'bw_del') {
        db()->prepare('DELETE FROM bw_units WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Bereitschaft gelöscht.';
    }

    if ($action === 'td_save') {
        $tid = (int)($_POST['id'] ?? 0);
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        $g = pruef_stammdaten('transport_dests',
                              ['name' => $_POST['name'] ?? null, 'base_id' => $sdBase()]);
        if ($g['daten'] === null) {
            $error = reset($g['fehler']);
        } elseif (stammdaten_dup_global('transport_dests', 'name', $g['daten']['name'])) {
            $error = '„' . $g['daten']['name'] . '“ ist bereits systemweit hinterlegt und '
                   . 'steht dir automatisch zur Verfügung.';
        } elseif ($tid > 0) {
            $zielId = $sdAendern('transport_dests', 'name = ?, lat = ?, lon = ?',
                [$g['daten']['name'], $lat, $lon], $tid,
                'Diese Zielklinik gibt es an diesem Standort schon.');
        } else {
            db()->prepare('INSERT IGNORE INTO transport_dests (user_id, base_id, name, lat, lon)
                           VALUES (?,?,?,?,?)')
                ->execute([$userId, $g['daten']['base_id'], $g['daten']['name'], $lat, $lon]);
            $zielId = $sdNeuId('Diese Zielklinik gibt es an diesem Standort schon.');
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Zielklinik gelöscht.';
    }

    /* Ein Fehler aus einem der fuenf Dialog-Schreibwege bleibt im Dialog
     * (Begruendung am Kopf der Datei, bei `$dlgFehler`). Er verlaesst damit
     * `$error` — sonst leitete die Weiche unten doch um. */
    $dlgVon = [
        'veh_save' => 'dlg-veh', 'crew_save' => 'dlg-crew', 'res_save' => 'dlg-res',
        'bw_save'  => 'dlg-bw',  'td_save'   => 'dlg-td',
    ][$action] ?? null;
    if ($error !== null && $dlgVon !== null) {
        $dlgFehler = ['dialog' => $dlgVon, 'meldung' => $error, 'werte' => $_POST];
        $error = null;
    }

    /* Nach dem Speichern zurueck zum passenden Abschnitt umleiten. Das oeffnet
     * ihn dank des Ankers automatisch wieder und verhindert nebenbei das
     * erneute Absenden beim Neuladen der Seite.
     *
     * Die Anker sind seit der Gliederung nach Standort (Konzept 3.8)
     * STANDORTBEZOGEN: `sd-<Standortkennung>` oeffnet den Block dieses
     * Standorts. Nur die Standortliste selbst und die Auswahl der zentralen
     * Standorte haben feste Anker. */
    /* ZWEI ZIELE (Web 7.0.0, neu gefasst S9/AP5). Die Standortaktionen fuehren
     * auf die LISTE zurueck, alles Uebrige auf die SEITE DES STANDORTS, an dem
     * es haengt.
     *
     * Bis Web 16.2.1 stand hier `t=rettungsmittel` — der Reiter, der alles auf
     * einmal zeigte. Den gibt es nicht mehr; die Weiche am Seitenkopf haette
     * jede Aenderung an einem Rettungsmittel, einer Rolle, einer Zielklinik
     * oder einer Bereitschaft auf die Liste geworfen. Der Anker haette dort
     * nichts gefunden, und wer zehn Zielkliniken nacheinander eintraegt, waere
     * zehnmal zurueckgeklickt. Das Ziel steht deshalb unten, wo auch der
     * Standort feststeht — als ganze Adresse und nicht als Reitername. */
    $abschnitt = [
        'base_save'  => 'standorte', 'base_del' => 'standorte',
        'base_default' => 'standorte', 'ub_toggle' => 'zentrale',
        'veh_save'   => null, 'veh_del'  => null, 'veh_default' => null,
        'crew_save'  => null, 'crew_del' => null,
        'res_save'   => null, 'res_del'  => null,
        'bw_save'    => null, 'bw_del'   => null,
        'td_save'    => null, 'td_del'   => null,
    ][$action] ?? null;
    /* Aktionen, die zu einem Standort gehoeren, springen in dessen Block
     * zurueck — und zwar in den UNTERBLOCK der jeweiligen Datenart
     * (`sd-<Standort>-<Art>`, Web 7.0.0). Vorher genuegte `sd-<Standort>`, weil
     * der Block alles auf einmal zeigte; jetzt liegt jede Datenart in einem
     * eigenen aufklappbaren Abschnitt, und ohne die Art landete man wieder ganz
     * oben. Das Skript am Seitenende oeffnet alle Ebenen bis dorthin.
     *
     * Der Standort steht im Formular; beim Loeschen liefert es ihn
     * ausdruecklich mit, weil die Zeile danach nicht mehr da ist, um befragt zu
     * werden. */
    $unterblock = [
        'veh_save' => 'veh', 'veh_del' => 'veh', 'veh_default' => 'veh',
        'crew_save' => 'crew', 'crew_del' => 'crew',
        'td_save'  => 'td',  'td_del'  => 'td',
        'res_save' => 'res', 'res_del' => 'res',
        'bw_save'  => 'bw',  'bw_del'  => 'bw',
    ][$action] ?? null;
    $zurueckZiel = 'einstellungen.php?t=standorte';
    /* Der neu angelegte Standort zieht die Umleitung auf seine eigene Seite
     * (E-S9-19). `#k-standort` ist die erste Karte darauf. */
    if ($baseNeu !== null) {
        $zurueckZiel = sd_seite($baseNeu);
        $abschnitt = 'k-standort';
    }
    if ($abschnitt === null && $unterblock !== null) {
        $zurueckBase = (int)($_POST['base_id'] ?? 0);
        /* OHNE STANDORT GIBT ES KEINE SEITE, auf die man zurueckkehren
           koennte: Ein Rettungsmittel mit `base_id = null` haengt an keinem
           Standort, und seine Karte steht auf der Liste (S9/AP5). Dorthin
           also, und in ihren Abschnitt. */
        $abschnitt = $zurueckBase > 0
            ? ('sd-' . $zurueckBase . '-' . $unterblock)
            : 'sd-ohne-veh';
        if ($zurueckBase > 0) { $zurueckZiel = sd_seite($zurueckBase); }
        /* AUF DIE GESCHRIEBENE ZEILE STATT AUF DEN ABSCHNITT (E-S9-19,
           S9/AP5-4). Wer etwas angelegt hat, will es sehen — nicht den
           Anfang der Liste, in der es irgendwo steht. `:target` faerbt sie,
           `scroll-padding-top` setzt sie unter die Kopfleiste. Der
           Abschnittsanker bleibt der Rueckfall fuer das Loeschen: Die Zeile,
           auf die er zeigte, gibt es dann nicht mehr. */
        if ($zielId !== null && $unterblock !== null) {
            $abschnitt = $unterblock . '-' . $zielId;
        }
    }
    /* UMGELEITET WIRD, WENN ES EIN ZIEL GIBT — nicht, wenn es eine Meldung
     * gibt. Bis Web 16.3.0 hing die Umleitung an `notice || error`; nimmt man
     * dem Erfolgsfall seine Meldung weg (E-S9-19: „keine zusaetzliche
     * Erfolgsmeldung"), waere beides null, es wuerde nicht umgeleitet, und
     * die Anwendung bliebe auf dem POST-Ergebnis stehen — ohne Anker, ohne
     * `:target` und mit der Neuladen-Warnung des Browsers. */
    if ($abschnitt !== null
        && ($zielId !== null || $baseNeu !== null || $notice !== null || $error !== null)) {
        if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
        if ($error !== null) { $_SESSION['flash_error'] = $error; }
        header('Location: ' . $zurueckZiel . '#' . $abschnitt);
        exit;
    }
}

// Meldung/Fehler aus der Umleitung uebernehmen
if (!empty($_SESSION['flash_notice'])) {
    $notice = $_SESSION['flash_notice'];
    unset($_SESSION['flash_notice']);
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

/* ---- Wartet dieses Konto gerade auf ein Gerät? (S5, E-S5-53) --------------
 *
 * Der Zustand steht in der Sitzung und wird bei JEDEM Aufruf des Geräte-
 * Reiters gegen die Wirklichkeit gehalten — er ist eine Erinnerung, keine
 * Wahrheit. Drei Ausgaenge:
 *
 *   Sitzung da und unverfallen  -> Zustand 3, die Karte wartet und laedt nach.
 *   Sitzung weg, Geraet da      -> das Geraet hat Ja gesagt. Vollzugsmeldung,
 *                                  Erinnerung loeschen.
 *   Sitzung weg, kein Geraet    -> Nein am Geraet oder Frist abgelaufen.
 *                                  Erinnerung STILL loeschen: Wer Tage spaeter
 *                                  auf die Seite kommt, soll nicht an einen
 *                                  abgebrochenen Versuch erinnert werden. Wer
 *                                  gerade zusieht, hat es vom Skript erfahren.
 *
 * Die Kontokennung steht in beiden Abfragen mit in der Bedingung: Eine
 * Erinnerung aus einer fremden Sitzung — moeglich nach einem Kontowechsel im
 * selben Browser — zeigt damit nichts an. */
if ($tab === 'geraete' && !empty($_SESSION['pair_warten'])) {
    $kw = (string)$_SESSION['pair_warten'];
    $sitzung = pair_sitzung_nach_kennung(db(), $kw);
    if ($sitzung !== null && (int)($sitzung['user_id'] ?? 0) === $userId
        && (int)$sitzung['rest_s'] > 0) {
        $koppelWarten = $sitzung;
    } else {
        unset($_SESSION['pair_warten']);
        if ($sitzung === null && pair_geraet_da(db(), $kw, $userId)) {
            $notice = 'Das Gerät ist jetzt mit deinem Konto verbunden — du findest es '
                    . 'unten in der Liste. Eine E-Mail dazu ist unterwegs.';
            $noticeTon = 'ok';
        }
    }
}

/* Die Rollenbeschriftungen kommen aus CREW_ROLES (db.php, E4). Bis Web 5.10.0
 * stand hier eine zweite Liste mit fuenf Flugrollen; sie waere mit dem Katalog
 * auseinandergelaufen, sobald eine Rolle dazukommt. */
$ROLE_LABELS = array_map(static fn(array $r): string => $r['label'], CREW_ROLES);

$devices = []; $editDev = null; $devNeu = 0;
if ($tab === 'geraete') {
    /* Das Kennzeichen "neu" rechnet die Datenbank, nicht PHP: created_at ist
     * ein TIMESTAMP und kommt in der Zeitrechnung der Datenbank an. Ein
     * Vergleich gegen eine in PHP gebildete Grenze haette stillschweigend
     * angenommen, dass beide dieselbe Zeitzone benutzen. */
    $st = db()->prepare('SELECT id, device_id, label, active, last_seen, created_at,
                                geraet_art, geraet_modell, geraet_teil,
                                (created_at > DATE_SUB(NOW(), INTERVAL ? DAY)) AS ist_neu
                         FROM devices
                         WHERE user_id = ? AND ' . GERAETE_ECHT_SQL . ' ORDER BY created_at');
    $st->execute([GERAETE_NEU_TAGE, $userId]);
    $devices = $st->fetchAll();
    foreach ($devices as $d) {
        if ((int)$d['id'] === (int)($_GET['ed'] ?? 0)) { $editDev = $d; }
        if ((int)$d['ist_neu']) { $devNeu++; }
    }
}
/* Das Leaflet-Stylesheet nur, wo eine Karte entstehen kann: Die
   Stammdatenreiter tragen seit S9/AP2 den Pin-Knopf am Ortsfeld und damit den
   Kartendialog (E-S9-06 c). Auf den uebrigen Reitern waere es eine Datei fuer
   nichts. */
/* DAS LEAFLET-STYLESHEET BRAUCHT JETZT DIE STANDORTSEITE. Bis Web 16.1.1
 * stand hier `['standorte','rettungsmittel']`; „rettungsmittel" gibt es nicht
 * mehr, und der Pin am Nur-Lage-Ortsfeld sitzt auf `t=standort`. Ohne diese
 * Zeile faellt die Karte dort unformatiert zusammen — und zwar ohne
 * Fehlermeldung, was das Aergerliche daran ist. */
ui_seite_start(['titel' => 'Einstellungen',
                'karte' => in_array($tab, ['standorte', 'standort'], true)]);
?>

<?php /* DIE STANDORTSEITE IST KEIN MENUEPUNKT, sondern eine Seite UNTER
         einem. Sie muss deshalb „standorte" als aktiv melden und nicht ihren
         eigenen Reiternamen: `ui_leiste_einstellungen()` vergleicht auf
         Gleichheit, ein unbekannter Schluessel liesse jeden Eintrag blass —
         und ohne aktiven Eintrag haengt `menue.js` seine Unterpunkte an
         nichts. */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => $tab === 'standort' ? 'standorte' : $tab]); ?>
  <?php ui_meldung($notice, $error, $noticeTon, '  '); ?>

  <?php if ($tab === 'profil'): ?>
    <?php
    /* Die gespeicherte Wahl steht NICHT in der Sitzung — dort liegt ihr
       Ergebnis ('hubschrauber' oder 'fahrzeug'). Für das gesetzte Radio
       braucht es die Wahl selbst, und die wird genau hier gebraucht:
       auth_guard.php liest bewusst nur, was jede Seite braucht (M1-20). */
    if (!isset($logoWahl)) {
        $lw = db()->prepare('SELECT logo_wahl FROM users WHERE id = ?');
        $lw->execute([$userId]);
        $logoWahl = (string)$lw->fetchColumn();
    }
    $standardName = logo_standard() === 'fahrzeug' ? 'Fahrzeug (NEF)' : 'Hubschrauber (RTH)';
    ?>
    <?php ui_titelzeile(['titel' => 'Profil']); ?>

    <form method="post" id="pfform">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <input type="hidden" name="old_token" id="pf_oldtok">

      <?php ui_karte_start(['titel' => 'Angaben', 'id' => 'k-angaben']); ?>
        <?php ui_feld(['label' => 'Name', 'name' => 'name', 'wert' => (string)($userName ?? ''),
                       'platzhalter' => 'wird in der Kopfleiste angezeigt',
                       'attr' => ' maxlength="120"']); ?>
        <?php ui_feld(['label' => 'E-Mail-Adresse (Anmeldung)', 'name' => 'email',
                       'id' => 'pf_email',
                       'art' => 'email', 'wert' => $userEmail, 'pflicht' => true]); ?>
        <?php /* NACHWEIS FUER DEN ADRESSWECHSEL (Backlog Nr. 128, K-7).
                 Immer sichtbar und nie Pflicht: Ein Feld, das erst beim Tippen
                 erscheint, wird uebersehen, und wer nur den Namen aendert, soll
                 sein Passwort nicht suchen muessen. Der Hinweis sagt, wann es
                 gebraucht wird; der Server verlangt es genau dann. */ ?>
        <?php ui_feld(['label' => 'Aktuelles Passwort', 'name' => 'old', 'id' => 'pf_old',
                       'art' => 'password',
                       'klein' => 'Nur nötig, wenn du die E-Mail-Adresse änderst — sie '
                                . 'ist die Anmeldung zu diesem Konto. Name und Logo '
                                . 'gehen ohne.',
                       'attr' => ' autocomplete="current-password"']); ?>
        <?php /* DIE EIGENE ROLLE, NUR ZU LESEN (R75, Web 15.0.0).
                 Sie steht hier, weil sie erklaert, warum zwei Konten
                 verschiedene Menues sehen — und weil es der einzige Ort ist,
                 an dem eine NutzerIn ihre eigene Rolle nachsehen kann.
                 Geaendert wird sie ausschliesslich in der Verwaltung; ein
                 Feld waere hier eine Einladung, die ins Leere fuehrt. */ ?>
        <p class="feld-hinweis">Rolle: <strong><?= e(rolle_text(eigene_rolle())) ?></strong><?php
          if (ist_betreiberin()): ?> — du siehst zusätzlich den Bereich
          <em>Betrieb</em>.<?php elseif (ist_admin()): ?> — du siehst zusätzlich den
          Bereich <em>Verwaltung</em>.<?php endif; ?> Geändert wird sie in der
          Verwaltung, nicht hier.</p>
      <?php ui_karte_ende(); ?>

      <?php /* LOGO-WAHL (E-P3-20, Mockup 13). Sie gilt für Kopfleiste UND
               Browser-Symbol; die Anmeldeseite zeigt immer den Standard, weil
               dort noch niemand angemeldet ist und die Wahl am Konto hängt. */ ?>
      <?php ui_karte_start(['titel' => 'Logo', 'id' => 'k-logo']); ?>
        <p class="feld-hinweis">Gilt für Kopfleiste und Browser-Symbol. Die Wahl
          übersteuert den Standard der Installation.</p>
        <?php ui_wahlliste([
            'name' => 'logo_wahl', 'wert' => $logoWahl, 'label' => 'Logo',
            'optionen' => [
                ''             => ['text' => 'Standard der Installation',
                                   'zusatz' => 'zurzeit ' . $standardName],
                'hubschrauber' => ['text' => 'Hubschrauber (RTH)'],
                'fahrzeug'     => ['text' => 'Fahrzeug (NEF)'],
                'wechselnd'    => ['text' => 'Wechselnd', 'zusatz' => 'neu je Anmeldung'],
            ],
        ]); ?>
      <?php ui_karte_ende(); ?>

      <?php ui_karte_ende(); ?>

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Profil speichern', 'art' => 'primaer']) ?>
      </div>
    </form>

      <?php /* DATENSCHUTZ: die Adresssuche je Konto (S9/AP2, E-S9-05, R79).
               Sie steht im Profil und nicht bei den Standorten, weil sie eine
               Entscheidung UEBER MICH ist und nicht ueber Daten: Was mein
               Browser einen Dritten fragt, entscheide ich (R74 (1) —
               NutzerIn). Die Installation ist die Obergrenze; ist sie aus,
               steht der Schalter ausgegraut da und sagt, warum.

               EIGENES FORMULAR, NICHT DAS DES PROFILS: Der Demo-Waechter
               oben sperrt `action=profile` ganz — mit dem Schalter darin
               koennte ausgerechnet das Konto, an dem alle die Anwendung
               ausprobieren, seine Adresssuche nicht abschalten. Dieselbe
               Trennung wie in `betrieb_server.php`. */ ?>
      <?php $geoInst = geocoder_installation_an();
            $geoKonto = geocoder_konto_an($userId); ?>
      <?php ui_karte_start(['titel' => 'Datenschutz', 'id' => 'k-datenschutz',
          'plakette' => ($geoInst && $geoKonto)
              ? ui_plakette('Adresssuche an', ['ton' => 'ok'])
              : ui_plakette('Adresssuche aus', ['ton' => 'neutral'])]); ?>
        <p class="feld-hinweis">Beim Tippen in einem Ortsfeld schickt der Browser
          den getippten Text an <strong><?= e(geocoder_host()) ?></strong> und
          bekommt Adressvorschläge zurück; nach einer Wahl auf der Karte geht die
          Koordinate denselben Weg, um die Adresse dazu zu holen. <strong>Nichts
          anderes verlässt dabei das Gerät</strong> — kein Name, keine Diagnose,
          keine Einsatznummer. Ohne die Suche bleiben Koordinaten, Plus Codes,
          „Meine Position" und die Karte selbst; nur die Vorschläge und die
          Umkehrsuche entfallen.</p>
        <?php if ($geoInst): ?>
          <form method="post" action="einstellungen.php?t=profil#k-datenschutz">
            <?= csrf_field() ?><input type="hidden" name="action" value="datenschutz">
            <input type="hidden" name="adresssuche_da" value="1">
            <?php ui_schalter(['name' => 'adresssuche',
                'label' => 'Adressvorschläge aus dem Internet',
                'an' => $geoKonto,
                'klein' => 'Gilt für dieses Konto, auf jedem Gerät.']); ?>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken',
                            'art' => 'primaer']) ?>
            </div>
          </form>
        <?php else: ?>
          <?php ui_schalter(['name' => 'adresssuche_gesperrt',
              'label' => 'Adressvorschläge aus dem Internet',
              'an' => false, 'attr' => ' disabled',
              'klein' => 'Für diese Installation abgeschaltet.']); ?>
          <p class="feld-hinweis">Die <strong>Installation</strong> hat die
            Adresssuche abgeschaltet (Betrieb → Servereinstellungen, Karte
            „Adresssuche"). Solange das so ist, ändert dieser Schalter nichts —
            deshalb steht er ausgegraut. Wer ihn braucht, wendet sich an die
            BetreiberIn.</p>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>

    <form method="post" id="pwform">
      <?= csrf_field() ?><input type="hidden" name="action" value="password">
      <input type="hidden" name="old_token" id="pw_oldtok">
      <input type="hidden" name="new_token" id="pw_newtok">
      <input type="hidden" name="new_salt" id="pw_newsalt">
      <?php /* Rundenzahl, mit der das NEUE Passwort abgeleitet wurde (M2-01).
               Ohne sie stuende in der Nutzerzeile weiter die alte, und die
               naechste Anmeldung rechnete mit der falschen Zahl. */ ?>
      <input type="hidden" name="new_iter" id="pw_newiter">
      <input type="hidden" name="wrap_pw" id="pw_wrap">
      <input type="hidden" name="key_check" id="pw_keychk">
      <?php ui_karte_start(['titel' => 'Passwort ändern', 'id' => 'k-passwort']); ?>
        <?php ui_feld(['label' => 'Aktuelles Passwort', 'name' => 'old', 'id' => 'pw_old',
                       'art' => 'password', 'pflicht' => true,
                       'attr' => ' autocomplete="current-password"']); ?>
        <?php ui_feld(['label' => 'Neues Passwort', 'name' => 'new1', 'id' => 'pw_new1',
                       'art' => 'password', 'pflicht' => true,
                       'klein' => 'Mindestens ' . PW_MIN_LAENGE . ' Zeichen. Die Stärke des Passworts ist '
                                . 'unmittelbar die Stärke der Verschlüsselung.',
                       'attr' => ' minlength="' . PW_MIN_LAENGE . '" autocomplete="new-password"']); ?>
        <span class="pwstaerke" id="pw_guete"></span>
        <?php ui_feld(['label' => 'Neues Passwort wiederholen', 'name' => 'new2', 'id' => 'pw_new2',
                       'art' => 'password', 'pflicht' => true,
                       'attr' => ' autocomplete="new-password"']); ?>
      <?php ui_karte_ende(); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Passwort ändern', 'art' => 'primaer']) ?>
      </div>
      <span class="feld-hinweis" id="pwstate"></span>
    </form>
    <?php /* Ruestzeug der Verschluesselung (Baustein ui_krypto_bootstrap()),
             dazu pwquality.js: Passwortguete nach derselben Regel wie bei
             Erstvergabe und Zuruecksetzen (B9, M2-02).
             OHNE keyguard.js/unlock.js — dieser Reiter entsperrt nichts, er
             wechselt das Passwort. */ ?>
    <?php ui_krypto_bootstrap(['skripte' => ['assets/crypto.js'],
                               'guete' => true, 'einzug' => '    ']); ?>
    <script>
    /* Zweiter Teil des Passwortwechsels (M2-07): Das Vormerkfach aus dem
     * vorigen Seitenaufruf aufloesen, bevor irgendetwas anderes geschieht. */
    (() => {
      const neu = sessionStorage.getItem('edk_neu');
      if (neu === null) { return; }
      sessionStorage.removeItem('edk_neu');
      if (<?= $pwGewechselt ? 'true' : 'false' ?>) {
        EdCrypto.clearSession();          // alten Inhaltsschluessel verwerfen
        EdCrypto.setDataKey(neu);         // neuer Datenschluessel gilt ab jetzt
      }
      // Sonst: nichts tun. Der Wechsel ist nicht zustande gekommen, der alte
      // Schluessel im Tab passt weiterhin zur gespeicherten Huelle.
    })();

    /* ---- Adresswechsel: Nachweis ableiten (Backlog Nr. 128, K-7) --------
     *
     * Derselbe Weg wie beim Passwortwechsel eine Karte tiefer: Aus dem
     * eingegebenen Passwort entsteht im Browser das Anmeldetoken; nur das geht
     * an den Server. Abgeleitet wird NUR, wenn die Adresse sich tatsaechlich
     * geaendert hat und etwas im Feld steht -- sonst ist es ein Namenswechsel,
     * und der kostet keine halbe Sekunde Rechnung.
     *
     * Die Meldung bei leerem Feld kommt hier und nicht erst vom Server: Ein
     * Formular, das man abschickt und unveraendert zurueckbekommt, sieht aus,
     * als sei nichts passiert. */
    (() => {
      const f = document.getElementById('pfform');
      if (!f) { return; }
      const feldMail = document.getElementById('pf_email');
      const feldPw   = document.getElementById('pf_old');
      const startMail = (feldMail.value || '').trim().toLowerCase();
      f.addEventListener('submit', async ev => {
        if (f.dataset.ready === '1') { return; }
        const jetzt = (feldMail.value || '').trim().toLowerCase();
        if (jetzt === startMail) { return; }          // kein Wechsel, kein Nachweis
        ev.preventDefault();
        if (!feldPw.value) {
          feldPw.setCustomValidity('Zum Ändern der E-Mail-Adresse ist das aktuelle '
                                 + 'Passwort nötig.');
          feldPw.reportValidity();
          return;
        }
        feldPw.setCustomValidity('');
        try {
          const k = await EdCrypto.deriveKeys(feldPw.value, KDF_SALT, KDF_ITER);
          document.getElementById('pf_oldtok').value = k.authToken;
          feldPw.value = '';                          // verlaesst den Browser nie
          f.dataset.ready = '1';
          f.submit();
        } catch (e) {
          feldPw.setCustomValidity('Dieser Browser unterstützt die nötige '
                                 + 'Verschlüsselung nicht.');
          feldPw.reportValidity();
        }
      });
      feldPw.addEventListener('input', () => feldPw.setCustomValidity(''));
    })();

    EdPwQuality.beobachte(document.getElementById('pw_new1'),
                          document.getElementById('pw_guete'));
    document.getElementById('pwform').addEventListener('submit', async ev => {
      const f = ev.target;
      if (f.dataset.ready === '1') return;
      ev.preventDefault();
      const st = document.getElementById('pwstate');
      const oldPw = f.elements['old'].value, n1 = f.elements['new1'].value;
      // Guete im SKRIPT pruefen, nicht nur als HTML-Attribut (M2-02).
      const guete = EdPwQuality.pruefe(n1);
      if (!guete.erlaubt) { st.textContent = guete.meldung; return; }
      if (n1 !== f.elements['new2'].value) { st.textContent = 'Neue Passwörter ungleich.'; return; }
      st.textContent = 'Schlüssel werden neu abgeleitet…';
      try {
        let oldDataKey = null;
        /* Das ALTE Passwort mit der Rundenzahl dieses Kontos ableiten, das
         * NEUE mit dem Zielwert (M2-01). Ein Passwortwechsel ist ohnehin ein
         * vollstaendiger Neuaufbau der Ableitung — er ist damit die zweite
         * Gelegenheit, bei der ein Konto die Anhebung mitnimmt, neben der
         * stillen Anhebung beim Anmelden. */
        const ok = await EdCrypto.deriveKeys(oldPw, KDF_SALT, KDF_ITER);
        document.getElementById('pw_oldtok').value = ok.authToken;
        oldDataKey = ok.dataKeyHex;
        const salt = EdCrypto.randomHex(16);
        const nk = await EdCrypto.deriveKeys(n1, salt, KDF_ITER_ZIEL);
        document.getElementById('pw_newtok').value = nk.authToken;
        document.getElementById('pw_newsalt').value = salt;
        document.getElementById('pw_newiter').value = KDF_ITER_ZIEL;
        // Inhaltsschluessel des Moduls in die neue Passwort-Huelle umpacken.
        // Klappt das nicht, wird NICHT abgeschickt: ein geaendertes Passwort
        // ohne passende Huelle machte die geschuetzten Angaben unlesbar.
        if (PAT_WRAP) {
          let ck;
          try {
            ck = await EdCrypto.decrypt(oldDataKey, PAT_WRAP);
          } catch (e) {
            st.textContent = 'Die geschützten Angaben lassen sich mit dem aktuellen '
                           + 'Passwort nicht entschlüsseln. Es wurde nichts geändert.';
            return;
          }
          document.getElementById('pw_wrap').value = await EdCrypto.encrypt(nk.dataKeyHex, ck);
          // Pruefsumme des Inhaltsschluessels mitsenden. Der Server kann die
          // Huelle nicht oeffnen und darum bisher nicht erkennen, ob darin
          // derselbe Schluessel steckt. Er lernt dadurch nichts ueber den
          // Schluessel — er vergleicht zwei Hashwerte.
          document.getElementById('pw_keychk').value = await EdCrypto.contentKeyCheck(ck);
        }
        /* SCHLUESSEL ERST NACH BESTAETIGTEM ERFOLG TAUSCHEN (M2-07).
         *
         * Hier stand clearSession() + setDataKey() VOR dem Absenden. Damit
         * war der alte Inhaltsschluessel verworfen und der neue
         * Datenschluessel gesetzt, BEVOR der Server ueberhaupt gefragt
         * worden war.
         *
         * Lehnt der Server ab — falsches aktuelles Passwort, abgelaufenes
         * Formular-Token, Ratenschutz, ein Fehler beim Speichern —, dann
         * liegt in diesem Tab jetzt ein Datenschluessel, zu dem die
         * gespeicherte Huelle nicht passt. Die geschuetzten Angaben sind
         * damit unlesbar, und zwar so, wie es aussieht, wenn es sie nicht
         * gaebe: "keine Angaben vorhanden". Ein FEHLGESCHLAGENER Vorgang
         * hinterliess also einen kaputten Zustand.
         *
         * Jetzt wandert der neue Schluessel in ein VORMERKFACH. Nach dem
         * Neuladen entscheidet die Antwort des Servers:
         *   Erfolg    -> uebernehmen und Fach leeren
         *   Fehlschlag-> Fach leeren, der alte Schluessel bleibt unberuehrt
         *
         * Das Vormerkfach liegt im sessionStorage, also im selben Tab und nur
         * bis zu dessen Ende — dieselbe Lebensdauer wie der Schluessel, den
         * es ersetzen soll. */
        sessionStorage.setItem('edk_neu', nk.dataKeyHex);
        f.dataset.ready = '1';
        f.submit();
      } catch (e) { st.textContent = 'Fehler bei der Schlüsselableitung.'; }
    });
    </script>

  <?php elseif ($tab === 'standorte' || $tab === 'standort'): ?>
    <?php
      /* ---- Standorte und ihre Stammdaten — ZWEI REITER (Web 7.0.0) --------
       *
       * Bis Web 6.3.0 stand alles unter einem Punkt „Standortdaten": die Liste
       * der Standorte, die Auswahl der zentralen, und darunter je Standort ein
       * Block mit fünf Datenarten. Das war eine Seite, auf der man scrollte, um
       * einen Standort anzulegen, und nochmal scrollte, um ein Rettungsmittel
       * einzutragen — und der Name passte auf keines von beidem.
       *
       * Jetzt trennt der Schnitt nach der Tätigkeit:
       *   „Standorte"       Standorte anlegen, bearbeiten, auswählen. Sonst nichts.
       *   „Rettungsmittel"  alles, was an einem ausgewählten Standort hängt.
       *
       * DIE DATENHALTUNG IST UNVERÄNDERT: Der Standort bleibt der Anker (E15),
       * jeder Eintrag gehört genau einem. Beide Reiter laden deshalb denselben
       * Bestand — der Block hier läuft für beide, gerendert wird danach je
       * Reiter.
       *
       * ZENTRALE EINTRÄGE bleiben sichtbar und unveränderlich: Sie werden von
       * einer Administratorin gepflegt (admin_stammdaten.php) und tragen hier
       * das Kennzeichen „systemweit".
       */
      /* Präfixe der Ortsfelder dieses Reiters. Sie entstehen beim Rendern — je
       * Standort eines für die Zielklinik —, und die Belebung im Browser
       * läuft am Ende über genau diese Liste. Eine zweite, von Hand gepflegte
       * Aufzählung im Skript liefe beim nächsten Standort auseinander. */
      $ORTSFELDER = [];

      $sdBases = dt_bases($userId);           // eigene + ausgewaehlte zentrale
      $sdBaseIds = array_map(static fn($b) => (int)$b['id'], $sdBases);
      /* [Kennung => Name] fuer die Standortauswahl im Rettungsmittel-Dialog
         (S9/AP5-4). Sie entsteht HIER und nicht auf der Standortseite: Dort
         ist `$sdBases` gleich auf den einen Standort der Seite verkuerzt, und
         eine Auswahl mit einem Eintrag waere keine. Zulaessig ist genau, was
         `dt_bases()` liefert — dieselbe Menge, die `dt_base_erlaubt()` beim
         Speichern durchlaesst. */
      $sdBaseNamen = [];
      foreach ($sdBases as $b) { $sdBaseNamen[(int)$b['id']] = (string)$b['name']; }

      // Zentrale Standorte zum Auswaehlen (E16) samt aktuellem Zustand.
      $zentral = db()->prepare('SELECT b.id, b.name, b.lat, b.lon,
                                       ub.base_id IS NOT NULL AS gewaehlt
                                  FROM bases b
                                  LEFT JOIN user_bases ub
                                         ON ub.base_id = b.id AND ub.user_id = ?
                                 WHERE b.user_id IS NULL ORDER BY b.name');
      $zentral->execute([$userId]);
      $zentral = $zentral->fetchAll();

      // Eigene Standorte getrennt: nur sie sind hier bearbeitbar.
      $eigene = db()->prepare('SELECT id, name, lat, lon FROM bases
                               WHERE user_id = ? ORDER BY name');
      $eigene->execute([$userId]);
      $eigene = $eigene->fetchAll();

      /* Die Stammdaten aller verfuegbaren Standorte in EINER Abfrage je Art,
       * danach nach Standort gebuendelt. Je Standort einzeln zu fragen ergaebe
       * bei zehn Standorten fuenfzig Abfragen fuer eine Seite. */
      $sdLade = function (string $tabelle, string $spalten) use ($userId, $sdBaseIds): array {
          if (!$sdBaseIds) { return []; }
          $nach = [];
          /* Die Nutzerbedingung steht VOR der IN-Liste: sql_in_bloecken()
           * setzt die Kennungen fuer {IDS} ein und haengt sie hinter die
           * uebergebenen Vorlaufparameter. Ein Platzhalter dahinter bekaeme den
           * falschen Wert. */
          foreach (sql_in_bloecken(db(),
                  "SELECT $spalten, base_id, user_id FROM `$tabelle`
                   WHERE (user_id = ? OR user_id IS NULL) AND base_id IN ({IDS})
                   ORDER BY name", $sdBaseIds, [$userId]) as $z) {
              $nach[(int)$z['base_id']][] = $z;
          }
          return $nach;
      };
      $sdVeh  = $sdLade('vehicles', 'id, name, kurz, kind, typ');
      /* RETTUNGSMITTEL OHNE STANDORT — GEBUENDELT AM ENDE (E-S9-09, Web 16.0.0).
       * `$sdLade()` fragt `base_id IN (...)`, und daran faellt ein Rettungsmittel
       * ohne Standort heraus: Es stuende in der Auswahlliste eines Diensttags,
       * waere auf dieser Seite aber weder zu aendern noch zu loeschen. Die
       * eigene Karte am Ende ist die kleinste Fassung dessen, was E-S9-18 als
       * letzten Eintrag der Standortliste vorsieht; ihre Form bekommt sie in AP5. */
      $sdVehOhne = [];
      foreach (db()->query('SELECT id, name, kurz, kind, typ, base_id, user_id
                              FROM vehicles
                             WHERE base_id IS NULL AND (user_id = ' . (int)$userId
                          . ' OR user_id IS NULL) ORDER BY name') as $z) {
          $sdVehOhne[] = $z;
      }
      $sdCrew = $sdLade('crew_presets', 'id, name, role_code');
      $sdTd   = $sdLade('transport_dests', 'id, name, lat, lon');
      $sdRes  = $sdLade('resources', 'id, name');
      $sdBw   = $sdLade('bw_units', 'id, name');

      // Rollen und Faehigkeiten je Rettungsmittel, ebenfalls gebuendelt.
      $vehIds = [];
      foreach ($sdVeh as $liste) { foreach ($liste as $v) { $vehIds[] = (int)$v['id']; } }
      foreach ($sdVehOhne as $v) { $vehIds[] = (int)$v['id']; }
      $vehRollen = $vehCaps = [];
      if ($vehIds) {
          foreach (sql_in_bloecken(db(),
                  'SELECT vehicle_id, role_code FROM vehicle_roles
                   WHERE vehicle_id IN ({IDS})', $vehIds) as $r) {
              $vehRollen[(int)$r['vehicle_id']][] = (string)$r['role_code'];
          }
          foreach (sql_in_bloecken(db(),
                  'SELECT vehicle_id, capability FROM vehicle_capabilities
                   WHERE vehicle_id IN ({IDS})', $vehIds) as $c) {
              $vehCaps[(int)$c['vehicle_id']][] = (string)$c['capability'];
          }
      }

      // Standard-Vorbelegung (user_defaults ersetzt is_default, Abschnitt 7)
      $SD_DEF = dt_standardwerte($userId);
      $DEF_BASE_ID = (int)($SD_DEF['base_id'] ?? 0);
      $DEF_VEH_ID  = (int)($SD_DEF['vehicle_id'] ?? 0);

      /* FUENF GET-PARAMETER SIND MIT S9/AP5-4 ENTFALLEN: `ev`, `ec`, `et`,
       * `er`, `ew`. Sie waren der Bearbeiten-Weg — ein Verweis auf dieselbe
       * Seite, der ein Formular unter der Liste mit anderen Werten fuellte.
       * Diese Formulare gibt es nicht mehr; „Bearbeiten" oeffnet einen
       * Dialog, und der bekommt seine Werte aus dem Oeffner, ohne dass die
       * Seite neu laedt (E-S9-19). Mit ihnen ist `$pickIn()` gegangen, das
       * nur sie bediente.
       *
       * `eb` BLEIBT: Der Standort wird weiterhin in einem Formular unter der
       * Liste bearbeitet — die drei Dialoge des Konzepts sind die der
       * LISTENKARTEN, und die Standortkarte ist keine Liste. */
      $editBase = null;
      foreach ($eigene as $b) { if ((int)$b['id'] === (int)($_GET['eb'] ?? 0)) { $editBase = $b; } }

      /* DIE `data-w-`-KETTE EINES RETTUNGSMITTELS (S9/AP5-4).
       *
       * Sie steht an einer Stelle, weil sie an vier gebraucht wird: „Anlegen"
       * im Kartenkopf der Standortseite, „Bearbeiten" in jeder Zeile dort,
       * und dasselbe Paar auf der Standortliste fuer die Karte „Ohne
       * Standort".
       *
       * DIE REIHENFOLGE IST TEIL DER SACHE. `dialog.js` laeuft die Attribute
       * in Dokumentreihenfolge ab und loest je Auswahl und je Hakengruppe ein
       * `change` aus; das Seitenskript haengt daran und richtet Betriebsart,
       * Rollen, Faehigkeiten und Standortauswahl nach dem Typ aus. Steht
       * `typ` vorn und `base` hinten, laeuft die Anpassung zuletzt ueber den
       * fertigen Stand — sonst richtet sie sich nach einem halb gefuellten
       * Formular. */
      $vehKette = static function (?array $v, int $heimat): array {
          if ($v === null) {
              return ['titel' => 'Rettungsmittel anlegen', 'knopf' => 'Anlegen',
                      'id' => '0', 'name' => '', 'kurz' => '',
                      'typ' => 'standard', 'kind' => '',
                      'rollen' => '', 'caps' => '', 'base' => (string)$heimat];
          }
          return ['titel' => 'Rettungsmittel bearbeiten', 'knopf' => 'Änderung speichern',
                  'id' => (string)(int)$v['id'], 'name' => (string)$v['name'],
                  'kurz' => (string)($v['kurz'] ?? ''),
                  'typ' => (string)($v['typ'] ?? 'standard'),
                  'kind' => (string)$v['kind'],
                  'rollen' => implode(',', $v['rollen'] ?? []),
                  'caps' => implode(',', $v['caps'] ?? []),
                  'base' => (string)(int)($v['base_id'] ?? 0)];
      };

      /* Zahl der Stammdatensaetze eines Standorts — fuer die Rueckfrage vor dem
       * Loeschen (Konzept 4.2): Das Loeschen nimmt sie mit. */
      $sdAnzahl = function (int $bid) use ($sdVeh, $sdCrew, $sdTd, $sdRes, $sdBw, $userId): int {
          $n = 0;
          foreach ([$sdVeh, $sdCrew, $sdTd, $sdRes, $sdBw] as $art) {
              foreach (($art[$bid] ?? []) as $z) {
                  if ((int)$z['user_id'] === $userId) { $n++; }
              }
          }
          return $n;
      };
      /* DIE DREI ZAHLEN EINER STANDORTZEILE (M-S9-06). Sie zaehlen, was
       * jemand an diesem Standort sucht — Rettungsmittel, Besatzung,
       * Zielkliniken —, und zwar EIGENE UND SYSTEMWEITE zusammen: Wer die
       * Liste liest, will wissen, wie viel dort steht, nicht wem es gehoert.
       * `$sdAnzahl()` daneben zaehlt etwas anderes und wird weiter gebraucht:
       * nur die EIGENEN, ueber alle fuenf Arten, fuer die Loeschrueckfrage. */
      $sdZahlen = function (int $bid) use ($sdVeh, $sdCrew, $sdTd): string {
          $n = static fn(array $art): int => count($art[$bid] ?? []);
          $eins = static fn(int $z, string $ein, string $viele): string
              => $z . ' ' . ($z === 1 ? $ein : $viele);
          return $eins($n($sdVeh), 'Rettungsmittel', 'Rettungsmittel') . ' · '
               . $eins($n($sdCrew), 'Besatzung', 'Besatzung') . ' · '
               . $eins($n($sdTd), 'Zielklinik', 'Zielkliniken');
      };

      // Kennzeichen einer Zeile: eigen oder systemweit?
      $istZentral = static fn(array $z): bool => $z['user_id'] === null;

      /* ---- WELCHE ROLLEN GIBT ES AN DIESEM STANDORT? (Web 7.0.0) ----------
       *
       * Die Besatzungspflege zeigte bis Web 6.3.0 IMMER alle Rollen des
       * Katalogs — an einem reinen NEF-Standort also auch Pilot 1, Pilot 2,
       * HEMS-TC und Flugretter. Vier Überschriften mit vier leeren Tabellen und
       * vier Eingabezeilen, für die es nie einen Eintrag geben wird.
       *
       * Gefragt wird jetzt der Bestand: Eine Rolle erscheint, wenn mindestens
       * EIN Rettungsmittel dieses Standorts sie führt. Damit richtet sich die
       * Pflege nach dem, was am Standort tatsächlich fliegt und fährt.
       *
       * EINE BEREITS BELEGTE ROLLE BLEIBT — dieselbe Regel wie im
       * Einsatzformular (A13e): Wer Vorbelegungen für eine Rolle hinterlegt hat
       * und später das zugehörige Rettungsmittel löscht, käme sonst an seine
       * eigenen Einträge nicht mehr heran, auch nicht zum Löschen. */
      $rollenAmStandort = function (int $bid) use ($sdVeh, $vehRollen, $sdCrew): array {
          $rollen = [];
          foreach (($sdVeh[$bid] ?? []) as $v) {
              foreach (($vehRollen[(int)$v['id']] ?? []) as $rc) { $rollen[$rc] = true; }
          }
          foreach (($sdCrew[$bid] ?? []) as $c) { $rollen[(string)$c['role_code']] = true; }
          // Reihenfolge des Katalogs, nicht die des Zufalls.
          return array_values(array_filter(array_keys(CREW_ROLES),
              static fn(string $rc): bool => isset($rollen[$rc])));
      };
    ?>

  <?php
  /* Die beiden Bausteine der Stammdatenlisten stehen seit Web 9.10.0 in
     `stammdaten_ui.php`: Dieselben Listen gibt es systemweit noch einmal
     (`admin_stammdaten.php`), und ein Muster, das an zwei Stellen steht,
     laeuft auseinander — genau das war der Befund, aus dem in O8b die
     Schliessungen entstanden sind. */
  ?>

  <?php if ($tab === 'standorte'): ?>
    <?php ui_titelzeile(['titel' => 'Standorte']); ?>
    <?php /* DREI ZEILEN ERKLÄRUNG, nicht zwei Absätze (E-P3-35). Der Bestand
             hatte hier zwei Blöcke à fünf Zeilen; wer die Seite zum zehnten
             Mal öffnet, liest sie nicht mehr und muss trotzdem daran vorbei.
             Was wegfällt, steht an der Handlung selbst: Die Rückfrage beim
             Löschen beziffert, was mitgeht. */ ?>
    <p class="seiten-erklaerung">Der Standort ist der Anker aller Diensttage: Er trägt
       die Vorbelegung, den Abfahrtsort, die Rettungsmittel, die Besatzung und
       die Zielkliniken. Ein Klick auf einen Standort führt auf seine Seite;
       der Stern markiert die Vorbelegung neuer Diensttage.</p>

    <?php ui_karte_start(['titel' => 'Eigene Standorte', 'zahl' => count($eigene), 'id' => 'standorte']); ?>
      <?php if (!$eigene): ?>
        <p class="feld-hinweis">Noch keine eigenen Standorte.</p>
      <?php endif; ?>
      <?php foreach ($eigene as $b):
            $bid = (int)$b['id'];
            $dup = stammdaten_dup_global('bases', 'name', $b['name']);
            $istDef = $bid === $DEF_BASE_ID;
            /* Die POST-Formulare stehen EINMAL und versteckt; die Knöpfe der
               Zeile und die des Aktionsblatts zeigen beide über `form` darauf
               (ui_zeilenaktionen). */ ?>
        <?php /* DIE BEIDEN FORMULARE STEHEN JETZT AUF DER STANDORTSEITE
                 (S9/AP5). Solange die Zeile Knoepfe trug, mussten sie hier
                 liegen; jetzt ist die Zeile selbst der Verweis, und wer
                 loeschen will, sieht den Standort vorher an. */ ?>
        <?php
        $klein = [];
        if ($b['lat'] !== null && $b['lon'] !== null) {
            $klein[] = $b['lat'] . ', ' . $b['lon'];
        } else {
            $klein[] = 'ohne Lage';
        }
        if ($dup) { $klein[] = 'identisch mit einem systemweiten Eintrag'; }
        /* DIE GANZE ZEILE FUEHRT AUF DIE SEITE DES STANDORTS (M-S9-06).
           Damit fallen die Zeilenaktionen hier weg: Sie waeren Knoepfe IN
           einem Link, und das ist kein gueltiges Markup. Loeschen und „Als
           Vorbelegung" stehen im Aktionsmenue der Standortseite — dort, wo
           man den Standort ohnehin ansieht, bevor man ihn loescht.
           Die Kleinzeile nennt die drei Zahlen statt der Lage: Wer sucht,
           sucht ein Rettungsmittel, keine Koordinate. Die Lage steht auf der
           Seite selbst, im ersten Abschnitt. */
        ui_zeile([
            'href_ganz' => sd_seite($bid),
            'vorn'  => ui_symbol('standort'),
            'text'  => (string)$b['name'],
            'klein' => $sdZahlen($bid)
                     . ($dup ? ' · identisch mit einem systemweiten Eintrag' : ''),
            'plaketten' => $istDef ? ui_symbol('stern', 'zeile-stern', 'Vorbelegung neuer Diensttage') : '',
        ]);
      endforeach; ?>

      <?php /* Das Formular bleibt IN der Karte unter der Liste (E-P3-35):
               „Hinzufügen" gehört zu dem, was darüber steht, und eine eigene
               Karte dafür trennte, was zusammengehört. „Bearbeiten" füllt
               dasselbe Formular und macht daraus „Standort bearbeiten". */ ?>
      <div class="listen-form">
        <h3 class="listen-form-titel"><?= $editBase ? 'Standort bearbeiten' : 'Standort hinzufügen' ?></h3>
        <form method="post" action="einstellungen.php?t=standorte#standorte">
          <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
          <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
          <div class="listen-form-felder">
            <?php /* Die Kennung `<praefix>addr` gehört dem LAGE-Suchfeld, nicht
                     dem Namen: ortsfeld.js sucht in `el(p + 'addr')`. Bis
                     Web 9.7.0 trug das Namensfeld sie — damals gab es kein
                     zweites Feld, und die Suche lief bewusst im Namensfeld
                     („getrennte Suche" übernahm nur die Koordinaten). Mit dem
                     wiederhergestellten Lage-Feld (F-P3-AI) stünde die Kennung
                     zweimal im Markup, und getElementById fände das erste —
                     das Lage-Feld wäre Zierde. */ ?>
            <?php ui_feld(['label' => 'Name', 'name' => 'name', 'id' => 'sdbase-name',
                           'klasse' => 'focus-target', 'pflicht' => true,
                           'platzhalter' => 'z. B. Standort Talwang',
                           'wert' => (string)($editBase['name'] ?? ''),
                           'attr' => ' maxlength="120"']); ?>
            <?php /* Koordinaten optional (E37/E39). Sie sind die Quelle des
                     Abfahrtorts „Standort" und werden beim Anlegen eines
                     Diensttags eingefroren (E8). Mit GETRENNTEM Suchfeld:
                     „Standort Kempten" ist keine Adresse, und eine Suche im
                     Namensfeld schriebe den Namen weg. */
                  $ORTSFELDER[] = 'sdbase'; ?>
            <?php ui_ortsfeld([
                    'praefix' => 'sdbase', 'feld' => false, 'ortswahl' => true,
                    'klasse' => 'loc-inline',
                    'such_hinweis' => 'Lage (optional)',
                    'lat_name' => 'lat', 'lon_name' => 'lon',
                    'lat' => (string)($editBase['lat'] ?? ''),
                    'lon' => (string)($editBase['lon'] ?? ''),
                ]); ?>
            <p class="feld-klein">Wird als Abfahrtsort neuer Diensttage übernommen.</p>
          </div>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => $editBase ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
            <?php if ($editBase): ?>
              <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                            'href' => 'einstellungen.php?t=standorte']) ?>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php ui_karte_ende(); ?>

    <?php /* „Vordefinierte Standorte" statt „Zentrale Standorte auswählen"
             (Web 7.0.0). „Zentral" beschrieb die Verwaltung, nicht den Nutzen.
             ZUGEKLAPPT (E-P3-35): Wer eigene Standorte gepflegt hat, braucht
             sie selten — und die Zahl im Kopf sagt schon, was drinsteht. */ ?>
    <?php
    $gewaehlt = count(array_filter($zentral, static fn($z) => !empty($z['gewaehlt'])));
    ui_karte_start(['titel' => 'Vordefinierte Standorte', 'id' => 'zentrale', 'zu' => true,
                    'zahl' => count($zentral) . ' · ' . $gewaehlt . ' ausgewählt']);
    ?>
      <p class="feld-hinweis">Vordefinierte Standorte legt eine Administratorin an.
         Sie erscheinen erst dann in den Auswahllisten, wenn du sie hier auswählst.
         Abwählen entfernt keine Daten.</p>
      <?php if (!$zentral): ?>
        <p class="feld-hinweis">Keine vordefinierten Standorte hinterlegt.</p>
      <?php endif; ?>
      <?php foreach ($zentral as $z):
            $zid = (int)$z['id']; $an = !empty($z['gewaehlt']);
            $istDef = $zid === $DEF_BASE_ID; ?>
        <?php /* ★ AUCH FÜR SYSTEMWEITE STANDORTE (Web 7.0.0): Ein Konto, das
                 ausschließlich mit vordefinierten Standorten arbeitet — der
                 Regelfall an einer Station —, konnte sonst gar keine
                 Vorbelegung setzen. Voraussetzung bleibt die Auswahl. */ ?>
        <?php if ($an && !$istDef): ?>
          <form method="post" id="f-zdef-<?= $zid ?>" class="nur-vorlesen"
                action="einstellungen.php?t=standorte#zentrale">
            <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
            <input type="hidden" name="id" value="<?= $zid ?>">
          </form>
        <?php endif; ?>
        <form method="post" id="f-zsel-<?= $zid ?>" class="nur-vorlesen"
              action="einstellungen.php?t=standorte#zentrale">
          <?= csrf_field() ?><input type="hidden" name="action" value="ub_toggle">
          <input type="hidden" name="id" value="<?= $zid ?>">
          <input type="hidden" name="an" value="<?= $an ? '0' : '1' ?>">
        </form>
        <?php
        $klein = ($z['lat'] !== null && $z['lon'] !== null)
            ? $z['lat'] . ', ' . $z['lon'] : 'ohne Lage';
        $eintraege = [];
        if ($an && !$istDef) {
            $eintraege[] = ['text' => 'Als Vorbelegung', 'symbol' => 'stern',
                            'art' => 'leise-orange', 'form' => 'f-zdef-' . $zid];
        }
        $eintraege[] = $an
            ? ['text' => 'Abwählen', 'symbol' => 'schliessen', 'art' => 'leise', 'form' => 'f-zsel-' . $zid]
            : ['text' => 'Auswählen', 'symbol' => 'plus', 'form' => 'f-zsel-' . $zid];
        ui_zeile([
            'text'  => (string)$z['name'],
            'klein' => $klein,
            'plaketten' => ui_plakette('systemweit')
                         . ($istDef ? ui_symbol('stern', 'zeile-stern', 'Vorbelegung neuer Diensttage') : ''),
            'aktionen' => ui_zeilenaktionen(['titel' => (string)$z['name'], 'eintraege' => $eintraege]),
        ]);
      endforeach; ?>
    <?php ui_karte_ende(true); ?>

    <?php if (!$sdBases): ?>
      <?= ui_meldung_markup('info', 'Noch kein Standort verfügbar. Lege oben '
          . 'einen eigenen an oder wähle einen vordefinierten aus — ohne Standort '
          . 'gibt es keine Rettungsmittel, keine Besatzungs-Vorbelegungen und '
          . 'keine Zielkliniken.') ?>
    <?php endif; ?>

    <?php /* OHNE STANDORT (E-S9-09/E-S9-18).
             Bergwacht, Veranstaltung und Sonstiges brauchen keinen Standort
             (AP4). Diese Rettungsmittel haengen an keinem — also stehen sie
             auf der LISTE und nicht auf der Seite eines Standorts, an dem sie
             nichts zu suchen haben. Bis Web 16.2.1 standen sie unter der
             letzten Standortkarte und erschienen damit auf JEDER
             Standortseite; die Leiste zaehlte sie als siebten Unterpunkt mit.

             SEIT S9/AP5-4 HAT DIE KARTE IHR EIGENES „ANLEGEN". Vorher fuehrte
             „Bearbeiten" auf die Seite des ERSTEN Standorts, weil dort das
             einzige Rettungsmittel-Formular stand — eine fremde Seite fuer
             einen Datensatz, der zu keiner gehoert, mit einem Haken „Ohne
             Standort" darin, der die verborgene Standortkennung schlug. Der
             Dialog braucht keine fremde Seite: Er steht hier, und der
             Standort ist ein Feld darin. */ ?>
    <?php if ($sdVehOhne): ?>
      <?php ui_karte_start(['titel' => 'Ohne Standort', 'id' => 'sd-ohne', 'zu' => true,
                            'zahl' => count($sdVehOhne) . ' Rettungsmittel',
                            'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-veh', $vehKette(null, 0))]]); ?>
        <p class="feld-hinweis">Bergwacht, Veranstaltung und Sonstiges brauchen keinen
           Standort. Sie haben dafür keine Vorschlagslisten — die hängen am Standort.</p>
        <section class="sd-liste" id="sd-ohne-veh">
          <?php foreach ($sdVehOhne as $v):
                $vid = (int)$v['id'];
                $capsTxt = array_map(static fn(string $c): string => VEHICLE_CAPABILITIES[$c] ?? $c,
                                     $vehCaps[$vid] ?? []);
                $klein = VEHICLE_TYPEN[(string)$v['typ']]['label'] ?? (string)$v['typ'];
                if ((string)($v['kurz'] ?? '') !== '') { $klein .= ' · ' . (string)$v['kurz']; }
                if ($capsTxt) { $klein .= ' · ' . implode(', ', $capsTxt); }
                sd_zeile([
                    'name' => (string)$v['name'], 'klein' => $klein,
                    'anker' => 'sd-ohne-veh', 'praefix' => 'veh', 'id' => $vid,
                    'zeilen_id' => true,
                    'base_id' => 0, 'zentral' => $istZentral($v),
                    'seite' => 'einstellungen.php?t=standorte',
                    'plaketten' => ui_artzeichen((string)$v['kind'], '', (string)$v['typ']),
                    'bearbeiten_attr' => sd_oeffner('dlg-veh', $vehKette(
                        $v + ['rollen' => $vehRollen[$vid] ?? [],
                              'caps'   => $vehCaps[$vid] ?? []], 0)),
                    'del_action' => 'veh_del',
                    'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ löschen? '
                                 . 'Bereits dokumentierte Diensttage bleiben unverändert.',
                ]);
          endforeach; ?>
        </section>
      <?php ui_karte_ende(true); ?>
      <?php /* Der Dialog steht auch hier — einmal, am Ende der Karte. Sein
               „Heimatstandort" ist 0: Auf der Liste gibt es keinen, von dem
               sich einer ablesen liesse, also bleibt die Standortauswahl in
               jedem Typ sichtbar (siehe `sd_dialog_rettungsmittel()`). */
            sd_dialog_rettungsmittel([
                'seite' => 'einstellungen.php?t=standorte', 'base_id' => 0,
                'unterzeile' => 'Ohne Standort', 'bases' => $sdBaseNamen,
                'werte' => ($dlgFehler['dialog'] ?? '') === 'dlg-veh'
                    ? (array)($dlgFehler['werte'] ?? []) : [],
                'fehler' => ($dlgFehler['dialog'] ?? '') === 'dlg-veh'
                    ? (string)($dlgFehler['meldung'] ?? '') : '',
            ]); ?>
    <?php endif; ?>

  <?php else: ?>
    <?php /* ---- Reiter „Rettungsmittel" ------------------------------------
             Alles, was an einem ausgewählten Standort hängt. Ein Block je
             Standort, darin je Datenart ein eigener. */ ?>
    <?php
      /* DIE SEITE EINES STANDORTS (S9/AP5, PS-12). Bis Web 16.1.1 stand hier
         der Reiter „Rettungsmittel" und zeigte ALLE Standorte untereinander,
         jeder als zugeklappte Karte. Jetzt fuehrt die Liste auf je eine
         Seite, und die zeigt genau einen — aufgeklappt, weil es nichts mehr
         gibt, wovon man ihn unterscheiden muesste.
         Wer eine Kennung aufruft, die er nicht sieht, landet auf der Liste:
         Eine Fehlermeldung ueber eine Zahl hilft niemandem weiter. */
      $seiteB = null;
      foreach ($sdBases as $b) {
          if ((int)$b['id'] === $seiteBase) { $seiteB = $b; break; }
      }
      /* Die Kennung ist oben schon geprueft; findet sie sich hier trotzdem
         nicht, hat sich der Bestand zwischen den beiden Abfragen geaendert.
         Dann ist die Liste der richtige Ort — und ui_abbruch() kann das noch,
         wenn header() es nicht mehr kann. */
      if ($seiteB === null) { ui_abbruch(404, 'Diesen Standort gibt es nicht (mehr).'); }
      $sdBases = [$seiteB];
    ?>
    <?php
      /* LOESCHEN UND „ALS VORBELEGUNG" STEHEN HIER, nicht mehr in der Liste
         (S9/AP5): Die Zeile dort ist der Verweis auf diese Seite, und ein
         Knopf in einem Link ist kein gueltiges Markup. Zugleich ist es der
         bessere Ort — wer einen Standort loescht, hat vorher gesehen, was
         daran haengt. Die Formulare stehen EINMAL und versteckt, das
         Aktionsmenue zeigt ueber `form` darauf (ui_zeilenaktionen). */
      /* `dt_bases()` liefert `zentral`, nicht `user_id` — ein zentraler
         Standort wird von einer Administratorin gepflegt und traegt hier
         die Plakette „systemweit" statt eines Aktionsmenues. */
      $sBid     = (int)$seiteB['id'];
      $sZentral = (bool)($seiteB['zentral'] ?? false);
      $sAnz     = $sdAnzahl($sBid);
      $sDef   = $sBid === $DEF_BASE_ID;
      $sAkt   = [];
      if (!$sDef) {
          $sAkt[] = ['text' => 'Als Vorbelegung', 'symbol' => 'stern',
                     'art' => 'leise-orange', 'form' => 'f-bdef-' . $sBid];
      }
      $sAkt[] = ['text' => 'Bearbeiten', 'symbol' => 'stift',
                 'href' => 'einstellungen.php?t=standorte&eb=' . $sBid . '#standorte'];
      $sAkt[] = ['text' => 'Löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                 'form' => 'f-bdel-' . $sBid];
    ?>
    <form method="post" id="f-bdef-<?= $sBid ?>" class="nur-vorlesen"
          action="einstellungen.php?t=standorte#standorte">
      <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
      <input type="hidden" name="id" value="<?= $sBid ?>">
    </form>
    <form method="post" id="f-bdel-<?= $sBid ?>" class="nur-vorlesen"
          action="einstellungen.php?t=standorte#standorte"
          data-confirm="Standort „<?= e($seiteB['name']) ?>“ löschen? <?= $sAnz > 0
              ? ($sAnz === 1 ? 'Ein eigener Stammdatensatz' : $sAnz . ' eigene Stammdatensätze')
                . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht) werden mitgelöscht.'
              : 'Es hängen keine eigenen Stammdaten daran.' ?> Bereits dokumentierte Diensttage bleiben unverändert.">
      <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
      <input type="hidden" name="id" value="<?= $sBid ?>">
    </form>
    <?php ui_titelzeile([
        'zurueck'  => ['href' => 'einstellungen.php?t=standorte', 'text' => 'Standorte'],
        'titel'    => (string)$seiteB['name'],
        'unter'    => e($sdZahlen($sBid)),
        'aktionen' => $sZentral ? ui_plakette('systemweit')
                    : ui_zeilenaktionen(['titel' => (string)$seiteB['name'], 'eintraege' => $sAkt]),
    ]); ?>
    <?php /* DREI ZEILEN (E-P3-35). Der Bestand hatte hier zwei Absätze zu je
             sechs Zeilen; was wegfällt, steht an der Handlung selbst — die
             Löschrückfrage sagt, dass dokumentierte Diensttage bleiben, und
             die Plakette „systemweit" sagt, warum eine Zeile keine Knöpfe
             hat. */ ?>
    <p class="seiten-erklaerung">Was an den ausgewählten
       <a href="einstellungen.php?t=standorte">Standorten</a> hängt:
       Rettungsmittel und ihre Rollen, Besatzungs-Vorbelegungen, Zielkliniken,
       weitere Rettungsmittel und Bergwacht. Änderungen wirken nur auf neue
       Diensttage — dokumentierte haben ihre Angaben eingefroren.</p>

    <?php if (!$sdBases): ?>
      <?php ui_meldung('Noch kein Standort verfügbar. Ohne Standort gibt es keine '
          . 'Rettungsmittel, keine Besatzungs-Vorbelegungen und keine Zielkliniken.',
          null, 'info', '      ',
          ['knopf' => ui_knopf(['text' => 'Zu den Standorten', 'art' => 'neutral',
                                'href' => 'einstellungen.php?t=standorte'])]); ?>
    <?php endif; ?>

    <?php foreach ($sdBases as $b): $bid = (int)$b['id']; ?>
      <?php /* Ein Block je Standort, darin je Datenart ein eigener. Die zweite
               Ebene ist neu (Web 7.0.0): Ein Standort mit vier Rettungsmitteln,
               sieben Rollen und einem Dutzend Zielkliniken war aufgeklappt eine
               Bildschirmseite, durch die man zum Suchen scrollte.
               Die Bergwacht erscheint nur, wenn an diesem Standort ein
               luftgebundenes Rettungsmittel steht: Die Fähigkeit kommt
               ausschließlich dort vor (E29), und ein leerer Block für einen
               reinen NEF-Standort wäre ein Angebot ohne Sinn. */ ?>
      <?php
        $vehListe = $sdVeh[$bid] ?? [];
        $hatLuft = false;
        foreach ($vehListe as $v) { if ($v['kind'] === 'air') { $hatLuft = true; break; } }
        $anker = 'sd-' . $bid;
        $rollenHier = $rollenAmStandort($bid);
      ?>
      <?php /* DAS INHALTSVERZEICHNIS ALS KENNZAHLEN (M-S9-07, das M-S9-06 an
               dieser Stelle ueberholt: dort waren es Pillen mit Zahl, und die
               Anmerkung 1 von M-S9-07 zieht diese Variante ausdruecklich
               zurueck). Drei Kacheln, keine fuer „Standort" — der steht
               darueber im Titel. Sie sind Verweise auf die Kartenkennungen;
               dieselben Kennungen holt sich `menue.js` fuer die Unterpunkte
               der Leiste.

               DER VORSATZ `k-` IST HAUSREGEL, nicht Geschmack: `Design.md`
               9.25 schreibt ihn fuer jede Karte vor, die Sprungziel sein
               soll, und der uebrige Bestand haelt sich an dreissig Stellen
               daran (`k-zustand`, `k-konten`, `k-angaben`, `k-app`). Die
               Mockups zeichnen `#standort`, `#rettungsmittel` — ein Bild ist
               aber keine Namensregel, und eine Regel, die man fuer die
               sechs neuesten Karten aufweicht, ist ab dann keine. */ ?>
      <div class="kennzahl-raster kennzahl-raster-3">
        <?= ui_kennzahl(['wert' => (string)count($vehListe), 'label' => 'Rettungsmittel',
                         'href' => '#k-rettungsmittel']) ?>
        <?= ui_kennzahl(['wert' => (string)count($sdCrew[$bid] ?? []), 'label' => 'Besatzung',
                         'href' => '#k-besatzung']) ?>
        <?= ui_kennzahl(['wert' => (string)count($sdTd[$bid] ?? []), 'label' => 'Zielkliniken',
                         'href' => '#k-zielkliniken']) ?>
      </div>

      <?php /* EINE KARTE JE ABSCHNITT, MIT KENNUNG (PS-12). Bis Web 16.2.1
               war der ganze Standort EINE zugeklappte Karte, darin fuenf
               Abschnitte. Jetzt traegt die Seite genau einen Standort, es
               gibt nichts mehr, wovon man ihn unterscheiden muesste — und
               jede Karte bekommt eine Kennung, an der die Kennzahlen und die
               Unterpunkte der Leiste haengen. */
             ui_karte_start(['titel' => 'Standort', 'id' => 'k-standort']); ?>
        <?php if (!empty($b['zentral'])): ?>
          <p class="feld-hinweis"><?= ui_plakette('systemweit') ?> Dieser Standort wird
             von der Verwaltung gepflegt.</p>
        <?php endif; ?>
        <?php ui_zeile([
            'text'  => (string)$b['name'],
            'klein' => ($b['lat'] !== null && $b['lon'] !== null)
                     ? $b['lat'] . ', ' . $b['lon'] . ' — Abfahrtsort neuer Diensttage'
                     : 'ohne Lage — ohne sie gibt es keinen Abfahrtsort',
            'aktionen' => empty($b['zentral']) ? ui_knopf([
                'text' => 'Bearbeiten', 'symbol' => 'stift', 'art' => 'leise',
                'href' => 'einstellungen.php?t=standorte&eb=' . $bid . '#standorte']) : '',
        ]); ?>
      <?php ui_nach_oben(); ui_karte_ende(); ?>

      <?php /* „ANLEGEN" STEHT IM KARTENKOPF (E-S9-19, M-S9-07) und nicht mehr
               als Formular unter der Liste. Wer den zwoelften Eintrag anlegen
               wollte, rollte vorher an elf vorbei. */
            ui_karte_start(['titel' => 'Rettungsmittel', 'id' => 'k-rettungsmittel',
                            'zahl' => count($vehListe),
                            'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-veh', $vehKette(null, $bid))]]); ?>
        <section class="sd-liste" id="<?= e($anker) ?>-veh">
          <p class="feld-hinweis">Die Art entscheidet über Besatzungsrollen und die
             im Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht)
             gibt es nur luftgebunden.</p>
          <?php if (!$vehListe): ?>
            <p class="feld-hinweis">Noch keine Rettungsmittel an diesem Standort.</p>
          <?php endif; ?>
          <?php /* DIE SPRUNGLISTE AB SECHS (E-S9-14, Nr. 44, M-S9-05). Sie
                   bekommen die Rettungsmittel und keine der anderen Listen:
                   Ihre Eintraege tragen ein Artzeichen, an dem man sie in
                   einer Pillenreihe wiedererkennt — ein Name allein taete das
                   nicht, und dann waere die Sprungliste dieselbe Liste ein
                   zweites Mal. Die uebrigen Karten bekommen den Filter. */ ?>
          <?php if (count($vehListe) >= SD_HILFE_AB):
                ui_sprungliste([
                    'label' => 'Zu einem Rettungsmittel springen',
                    'eintraege' => array_map(static fn(array $v): array => [
                        'text' => (string)$v['name'],
                        'href' => '#veh-' . (int)$v['id'],
                        'vorn' => ui_artzeichen((string)$v['kind'], '',
                                                (string)($v['typ'] ?? null)),
                    ], $vehListe),
                ]);
          endif; ?>
          <?php foreach ($vehListe as $v):
                $vid = (int)$v['id'];
                $rollenTxt = array_map('crew_role_label', $vehRollen[$vid] ?? []);
                $capsTxt = array_map(static fn(string $c): string => VEHICLE_CAPABILITIES[$c] ?? $c,
                                     $vehCaps[$vid] ?? []);
                /* DIE ART STEHT NICHT AUSGESCHRIEBEN DARUNTER (Web 7.0.0): Das
                   Symbol vor dem Namen sagt sie, und es trägt seine
                   Textalternative — die Auskunft hängt nicht an der Grafik.
                   Übrig bleibt, was man dem Symbol nicht ansieht. */
                $klein = ($rollenTxt ? implode(', ', $rollenTxt) : 'keine Rollen')
                       . ($capsTxt ? ' · ' . implode(', ', $capsTxt) : '');
                sd_zeile([
                    'name' => (string)$v['name'], 'klein' => $klein,
                    /* DAS ARTZEICHEN STEHT JETZT WIRKLICH DA (F-S9-K-04).
                       Der Kommentar darueber behauptete es seit Web 7.0.0,
                       und `$sym = dt_art_symbol(...)` wurde dafuer sogar
                       berechnet — benutzt hat es niemand, die Zeile zeigte
                       nur Namen und Rollen. Jetzt steht es links wie in den
                       Mockups, MIT Typ: `ui_artzeichen()` zeigt sonst fuer
                       eine Bergwacht dasselbe Zeichen wie fuer ein NEF. */
                    'vorn' => ui_artzeichen((string)$v['kind'], '',
                                            (string)($v['typ'] ?? null)),
                    'zeilen_id' => true,
                    'seite' => sd_seite($bid),
                    'anker' => $anker . '-veh', 'praefix' => 'veh', 'id' => $vid,
                    'base_id' => $bid, 'zentral' => $istZentral($v),
                    'stern' => $vid === $DEF_VEH_ID,
                    'def_action' => 'veh_default', 'del_action' => 'veh_del',
                    'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ löschen? '
                                 . 'Bereits dokumentierte Diensttage bleiben unverändert.',
                    'bearbeiten_attr' => sd_oeffner('dlg-veh', $vehKette(
                        $v + ['rollen' => $vehRollen[$vid] ?? [],
                              'caps'   => $vehCaps[$vid] ?? []], $bid)),
                ]);
          endforeach; ?>
        </section>

        <?php /* BESATZUNG — nur die Rollen, die es an diesem Standort gibt. */ ?>
      <?php ui_nach_oben(); ui_karte_ende(); ?>

      <?php /* OHNE ROLLE KEIN „ANLEGEN". Die Rollen kommen von den
               Rettungsmitteln des Standorts; steht dort keines, hat der Dialog
               nichts anzubieten und wuerde eine leere Auswahl zeigen. Der
               Hinweis in der Karte sagt stattdessen, woher Rollen kommen. */
            ui_karte_start(['titel' => 'Besatzung', 'id' => 'k-besatzung',
                            'zahl' => count($sdCrew[$bid] ?? []),
                            'aktion' => $rollenHier ? ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-crew', [
                                             'titel' => 'Besatzungsmitglied anlegen',
                                             'knopf' => 'Anlegen', 'id' => '0', 'name' => '',
                                             'rolle' => (string)$rollenHier[0]])] : null]); ?>
        <p class="feld-hinweis">Vorschläge für die Besatzungsfelder, je Rolle.
           Freitext bleibt überall möglich — wer aushilft, muss nicht erst hier
           eingetragen werden.</p>
        <?php /* DER FILTER STEHT VOR DER SEKTION, nicht darin: Das Skript
                 filtert die KINDER des Listenbehälters, und das Feld selbst
                 gehört nicht dazu. Der Hinweis darüber ist mit demselben
                 Schnitt aus der Sektion gerückt — er beschreibt die Karte,
                 nicht die Liste. */
               if (count($sdCrew[$bid] ?? []) >= SD_HILFE_AB) {
                   ui_kartenfilter(['id' => 'filt-crew-' . $bid,
                                    'ziel' => $anker . '-crew',
                                    'label' => 'Besatzung filtern',
                                    'platzhalter' => 'Namen filtern']);
               } ?>
        <section class="sd-liste" id="<?= e($anker) ?>-crew">
          <?php if (!$rollenHier): ?>
            <p class="feld-hinweis">Noch keine Rolle an diesem Standort. Rollen
               entstehen am Rettungsmittel: Trage oben eines ein und hake an,
               welche Rollen es führt.</p>
          <?php endif; ?>
          <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
            <?php /* h3 UND NICHT h4 (S9/AP5): Der doppelte Titel `h3.sd-titel`
                     ist mit diesem Paket entfallen — er wiederholte Kartentitel
                     und Kartenzahl aus dem Kartenkopf Wort fuer Wort, und die
                     Mockups kennen ihn nicht. Bliebe die Rolle ein h4, klaffte
                     zwischen dem h2 der Karte und ihr eine Ebene. Die Regel in
                     `style.css` haengt an der KLASSE, nicht am Element. */ ?>
            <h3 class="sd-rolle"><?= e($rr['label']) ?></h3>
            <?php $any = false;
                  foreach (($sdCrew[$bid] ?? []) as $c):
                      if ($c['role_code'] !== $rk) { continue; }
                      $any = true; $cz = $istZentral($c);
                      $dup = !$cz && stammdaten_dup_global('crew_presets', 'name', $c['name'], 'role_code', $rk);
                      sd_zeile([
                          'name' => (string)$c['name'],
                          'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                          'seite' => sd_seite($bid),
                          'zeilen_id' => true,
                    'anker' => $anker . '-crew', 'praefix' => 'crew', 'id' => (int)$c['id'],
                          'base_id' => $bid, 'zentral' => $cz,
                          'del_action' => 'crew_del',
                          'del_frage' => 'Eintrag „' . $c['name'] . '“ löschen?',
                          'bearbeiten_attr' => sd_oeffner('dlg-crew', [
                              'titel' => 'Besatzungsmitglied bearbeiten',
                              'knopf' => 'Änderung speichern', 'id' => (string)(int)$c['id'],
                              'name' => (string)$c['name'], 'rolle' => $rk]),
                      ]);
                  endforeach;
                  if (!$any): ?>
              <p class="feld-hinweis">Noch keine Einträge.</p>
            <?php endif; ?>
          <?php endforeach; ?>
        </section>

      <?php ui_nach_oben(); ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'Zielkliniken', 'id' => 'k-zielkliniken',
                            'zahl' => count($sdTd[$bid] ?? []),
                            'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-td', [
                                             'titel' => 'Zielklinik anlegen', 'knopf' => 'Anlegen',
                                             'id' => '0', 'name' => '',
                                             'lat' => '', 'lon' => ''])]]); ?>
        <p class="feld-hinweis">Vorschläge für das Feld „Transportziel" im
           Einsatz. Koordinaten sind freiwillig; ohne sie entsteht lediglich
           kein Pin auf der Karte.</p>
        <?php if (count($sdTd[$bid] ?? []) >= SD_HILFE_AB) {
                   ui_kartenfilter(['id' => 'filt-td-' . $bid,
                                    'ziel' => $anker . '-td',
                                    'label' => 'Zielkliniken filtern',
                                    'platzhalter' => 'Zielklinik filtern']);
               } ?>
        <section class="sd-liste" id="<?= e($anker) ?>-td">
          <?php if (!($sdTd[$bid] ?? [])): ?>
            <p class="feld-hinweis">Noch keine Zielkliniken.</p>
          <?php endif; ?>
          <?php foreach (($sdTd[$bid] ?? []) as $t):
                $tz = $istZentral($t);
                $dup = !$tz && stammdaten_dup_global('transport_dests', 'name', $t['name']);
                $klein = ($t['lat'] !== null && $t['lon'] !== null)
                    ? $t['lat'] . ', ' . $t['lon'] : 'ohne Lage';
                if ($dup) { $klein .= ' · identisch mit einem systemweiten Eintrag'; }
                sd_zeile([
                    'name' => (string)$t['name'], 'klein' => $klein,
                    'seite' => sd_seite($bid),
                    'zeilen_id' => true,
                    'anker' => $anker . '-td', 'praefix' => 'td', 'id' => (int)$t['id'],
                    'base_id' => $bid, 'zentral' => $tz,
                    'del_action' => 'td_del',
                    'del_frage' => 'Zielklinik „' . $t['name'] . '“ löschen?',
                    'bearbeiten_attr' => sd_oeffner('dlg-td', [
                        'titel' => 'Zielklinik bearbeiten', 'knopf' => 'Änderung speichern',
                        'id' => (string)(int)$t['id'], 'name' => (string)$t['name'],
                        'lat' => (string)($t['lat'] ?? ''), 'lon' => (string)($t['lon'] ?? '')]),
                ]);
          endforeach; ?>
        </section>

      <?php ui_nach_oben(); ui_karte_ende(); ?>

      <?php ui_karte_start(['titel' => 'Weitere Rettungsmittel', 'id' => 'k-weitere',
                            'zahl' => count($sdRes[$bid] ?? []),
                            'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-res', [
                                             'titel' => 'Weiteres Rettungsmittel anlegen',
                                             'knopf' => 'Anlegen', 'id' => '0', 'name' => ''])]]); ?>
        <p class="feld-hinweis">Vorschläge für das Feld „Weitere Rettungsmittel"
           im Einsatz (RTW, NEF, RTH …).</p>
        <?php /* AUCH HIER DER FILTER, obwohl Konzept und Mockup nur Besatzung
                 und Zielkliniken nennen: Es ist dieselbe Listenform mit
                 demselben Problem, und zwei Sorten Liste auf einer Seite —
                 die eine filterbar, die andere nicht — wären schwerer zu
                 erklären als eine Regel. Die Regel lautet: ab
                 `SD_HILFE_AB` Einträgen bekommt jede Liste ihr Hilfsmittel;
                 die Rettungsmittel die Sprungliste, alle übrigen den Filter. */
               if (count($sdRes[$bid] ?? []) >= SD_HILFE_AB) {
                   ui_kartenfilter(['id' => 'filt-res-' . $bid,
                                    'ziel' => $anker . '-res',
                                    'label' => 'Weitere Rettungsmittel filtern',
                                    'platzhalter' => 'Bezeichnung filtern']);
               } ?>
        <section class="sd-liste" id="<?= e($anker) ?>-res">
          <?php if (!($sdRes[$bid] ?? [])): ?>
            <p class="feld-hinweis">Noch keine Einträge.</p>
          <?php endif; ?>
          <?php foreach (($sdRes[$bid] ?? []) as $r):
                $rz = $istZentral($r);
                $dup = !$rz && stammdaten_dup_global('resources', 'name', $r['name']);
                sd_zeile([
                    'name' => (string)$r['name'],
                    'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                    'seite' => sd_seite($bid),
                    'zeilen_id' => true,
                    'anker' => $anker . '-res', 'praefix' => 'res', 'id' => (int)$r['id'],
                    'base_id' => $bid, 'zentral' => $rz,
                    'del_action' => 'res_del',
                    'del_frage' => 'Eintrag „' . $r['name'] . '“ löschen?',
                    'bearbeiten_attr' => sd_oeffner('dlg-res', [
                        'titel' => 'Weiteres Rettungsmittel bearbeiten', 'knopf' => 'Änderung speichern',
                        'id' => (string)(int)$r['id'], 'name' => (string)$r['name']]),
                ]);
          endforeach; ?>
        </section>

      <?php ui_nach_oben(); ui_karte_ende(); ?>

      <?php /* DIE BERGWACHT-KARTE ERSCHEINT NUR MIT EINEM LUFTGEBUNDENEN
               RETTUNGSMITTEL (E29). Die Karte davor schliesst deshalb VOR der
               Bedingung — sonst bliebe sie an einem reinen NEF-Standort offen,
               und das Markup zerfiele ab dort. */ ?>
        <?php if ($hatLuft): ?>
      <?php ui_karte_start(['titel' => 'Bergwacht', 'id' => 'k-bergwacht',
                            'zahl' => count($sdBw[$bid] ?? []),
                            'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                         'art' => 'orange', 'href' => '#',
                                         'attr' => sd_oeffner('dlg-bw', [
                                             'titel' => 'Bereitschaft anlegen',
                                             'knopf' => 'Anlegen', 'id' => '0', 'name' => ''])]]); ?>
          <p class="feld-hinweis">Bereitschaften für das Feld „Bergwacht" im
             Einsatz. Der Abschnitt erscheint, weil an diesem Standort ein
             luftgebundenes Rettungsmittel steht — die Fähigkeit kommt nur
             dort vor.</p>
          <?php if (count($sdBw[$bid] ?? []) >= SD_HILFE_AB) {
                     ui_kartenfilter(['id' => 'filt-bw-' . $bid,
                                      'ziel' => $anker . '-bw',
                                      'label' => 'Bereitschaften filtern',
                                      'platzhalter' => 'Bereitschaft filtern']);
                 } ?>
          <section class="sd-liste" id="<?= e($anker) ?>-bw">
            <?php if (!($sdBw[$bid] ?? [])): ?>
              <p class="feld-hinweis">Noch keine Bereitschaften.</p>
            <?php endif; ?>
            <?php foreach (($sdBw[$bid] ?? []) as $w):
                  $wz = $istZentral($w);
                  $dup = !$wz && stammdaten_dup_global('bw_units', 'name', $w['name']);
                  sd_zeile([
                      'name' => (string)$w['name'],
                      'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                      'seite' => sd_seite($bid),
                      'zeilen_id' => true,
                    'anker' => $anker . '-bw', 'praefix' => 'bw', 'id' => (int)$w['id'],
                      'base_id' => $bid, 'zentral' => $wz,
                      'del_action' => 'bw_del',
                      'del_frage' => 'Bereitschaft „' . $w['name'] . '“ löschen?',
                      'bearbeiten_attr' => sd_oeffner('dlg-bw', [
                          'titel' => 'Bereitschaft bearbeiten', 'knopf' => 'Änderung speichern',
                          'id' => (string)(int)$w['id'], 'name' => (string)$w['name']]),
                  ]);
            endforeach; ?>
          </section>
      <?php ui_nach_oben(); ui_karte_ende(); ?>
        <?php endif; ?>
      <?php /* ---- DIE DIALOGE DER SEITE (E-S9-19, M-S9-07) ------------------
               Sie stehen EINMAL, am Ende, nicht je Liste und schon gar nicht
               je Rolle: Ein Dialog ist ein Ort, kein Bestandteil einer Zeile.
               Die Oeffner oben tragen, was den Fall ausmacht.

               Sie stehen INNERHALB der Standortschleife, weil sie den Standort
               kennen muessen — auf einer Standortseite ist das genau einer
               (die Schleife laeuft einmal). Ausserhalb stuenden sie vor einem
               `$bid`, das es dort nicht mehr gibt. */
            $sdUnter = 'Standort ' . (string)$b['name'];
            /* Vorbelegung und Meldung bekommt GENAU DER Dialog, dessen
               Schreibweg abgelehnt hat — die uebrigen stehen leer da. */
            $dlgWerte = fn(string $id): array => ($dlgFehler['dialog'] ?? '') === $id
                ? (array)($dlgFehler['werte'] ?? []) : [];
            $dlgMeldung = fn(string $id): string => ($dlgFehler['dialog'] ?? '') === $id
                ? (string)($dlgFehler['meldung'] ?? '') : '';

            sd_dialog_rettungsmittel([
                'seite' => sd_seite($bid), 'base_id' => $bid, 'unterzeile' => $sdUnter,
                'base_name' => (string)$b['name'], 'bases' => $sdBaseNamen,
                'werte' => $dlgWerte('dlg-veh'), 'fehler' => $dlgMeldung('dlg-veh'),
            ]);
            if ($rollenHier) {
                $rollenWahl = [];
                foreach ($rollenHier as $rk) { $rollenWahl[$rk] = CREW_ROLES[$rk]['label']; }
                sd_dialog_eintrag([
                    'id' => 'dlg-crew', 'seite' => sd_seite($bid), 'base_id' => $bid,
                    'unterzeile' => $sdUnter, 'action' => 'crew_save',
                    'titel_neu' => 'Besatzungsmitglied anlegen',
                    'label' => 'Name', 'platzhalter' => 'Name der Person',
                    'max' => SD_NAME_MAX, 'rollen' => $rollenWahl,
                    'hinweis' => 'Vorlage für Diensttage an diesem Standort; sie erscheint in '
                               . 'der Vorschlagsliste des Rollenfeldes — im Diensttag und im Einsatz.',
                    'werte' => $dlgWerte('dlg-crew'), 'fehler' => $dlgMeldung('dlg-crew'),
                ]);
            }
            sd_dialog_zielklinik([
                'seite' => sd_seite($bid), 'base_id' => $bid, 'unterzeile' => $sdUnter,
                'praefix' => 'sdtd' . $bid,
                'werte' => $dlgWerte('dlg-td'), 'fehler' => $dlgMeldung('dlg-td'),
            ]);
            $ORTSFELDER[] = 'sdtd' . $bid;
            sd_dialog_eintrag([
                'id' => 'dlg-res', 'seite' => sd_seite($bid), 'base_id' => $bid,
                'unterzeile' => $sdUnter, 'action' => 'res_save',
                'titel_neu' => 'Weiteres Rettungsmittel anlegen',
                'label' => 'Bezeichnung', 'platzhalter' => 'z. B. RTW Talwang 76/85',
                'max' => SD_NAME_MAX,
                'hinweis' => 'Vorschlag im Feld „Weitere Rettungsmittel“ am Einsatz.',
                'werte' => $dlgWerte('dlg-res'), 'fehler' => $dlgMeldung('dlg-res'),
            ]);
            if ($hatLuft) {
                sd_dialog_eintrag([
                    'id' => 'dlg-bw', 'seite' => sd_seite($bid), 'base_id' => $bid,
                    'unterzeile' => $sdUnter, 'action' => 'bw_save',
                    'titel_neu' => 'Bereitschaft anlegen',
                    'label' => 'Bereitschaft', 'platzhalter' => 'z. B. Bergwacht Sonnenau',
                    'max' => SD_NAME_MAX,
                    'hinweis' => 'Vorschlag im Feld „Bergwacht“ am Einsatz.',
                    'werte' => $dlgWerte('dlg-bw'), 'fehler' => $dlgMeldung('dlg-bw'),
                ]);
            }
      ?>
    <?php endforeach; ?>

  <?php endif; ?>

    <script src="<?= asset('assets/openlocationcode.js') ?>"></script>
    <script src="<?= asset('assets/locparse.js') ?>"></script>
    <?php /* html.js (EdHtml.escape) und vorschlagsliste.js (EdVorschlaege)
             gehoeren zur Ortsfeld-Komponente, seit die Trefferliste ein
             eigener Baustein ist (S9/AP1, E-S9-07). Reihenfolge = Abhaengigkeit.

             html.js STEHT IN DIESER DATEI ZWEIMAL — hier und im Reiter
             „Backup". Das geht, weil die Reiter einander ausschliessen
             (`elseif`), und es geht NUR deshalb: Die Datei deklariert auf
             oberster Ebene ein `const`, und eine zweite Deklaration im selben
             Dokument ist ein SyntaxError, der das ganze zweite Skript
             verwirft. Genau diese Falle hat F-12 schon einmal gekostet. Wer
             die Reiterstruktur aendert, prueft beide Stellen. */ ?>
    <script src="<?= asset('assets/html.js') ?>"></script>
    <script src="<?= asset('assets/vorschlagsliste.js') ?>"></script>
    <script src="<?= asset('assets/geocoder.js') ?>"></script>
    <script src="<?= asset('assets/ortsfeld.js') ?>"></script>
    <?php /* DIE KARTE KOMMT MIT S9/AP2 HIERHER (E-S9-06 c, Backlog Nr. 70).
             Bis Web 15.7.1 hatte die Nur-Lage-Fassung des Ortsfelds keinen
             Pin-Knopf — die Lage eines Standorts liess sich suchen oder
             tippen, aber nicht auf der Karte zeigen. Dafuer braucht diese
             Seite jetzt dieselben vier Bausteine wie das Einsatzformular. */ ?>
    <script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
    <script src="<?= asset('assets/map_layers.js') ?>"></script>
    <script src="<?= asset('assets/geo.js') ?>"></script>
    <script src="<?= asset('assets/ortswahl.js') ?>"></script>
    <?php /* DER KARTENFILTER (S9/AP5). Er steht bei den Skripten dieses
             Zweiges und nicht im Geruest: Ihn gibt es bisher nur auf der
             Standortseite, und ein Skript, das auf jeder Seite laedt und auf
             fuenf von sechs nichts findet, ist Ballast. Er braucht keine
             Reihenfolge — er haengt an nichts. */ ?>
    <script src="<?= asset('assets/kartenfilter.js') ?>"></script>
    <script>
    /* Ortsfelder der Stammdatenpflege beleben (E37). Dieselbe Komponente wie
     * am Einsatz — mit getrennter Suche, weil das Namensfeld hier den NAMEN
     * trägt und nicht die Adresse. Ohne Vorschlagsliste: Was hier entsteht,
     * IST die Vorschlagsliste.
     *
     * OHNE SPUR: Hier gibt es keinen Einsatz, also nichts aufzuzeichnen
     * (M-S9-04, Anmerkung 6). Sonst ist es derselbe Dialog. */
    <?= 'const ORTSFELDER = ' . json_js($ORTSFELDER) . ';' ?>
    ORTSFELDER.forEach(p => {
      const steuer = EdOrtsfeld.init({ praefix: p, getrennteSuche: true });
      if (steuer) { EdOrtswahl.registriere(p, steuer); }
    });
    </script>

    <script src="<?= asset('assets/dialog.js') ?>"></script>
    <script>
    /* DER RETTUNGSMITTEL-DIALOG RICHTET SICH NACH DEM TYP (E-S9-09/E-S9-19).
     *
     * Rein anzeigend: Was zulässig ist, entscheidet der Server in 'veh_save'
     * über `pruef_rettungsmittel()`. Diese Zeilen nehmen der Ablehnung nur die
     * Überraschung — und verhindern, dass jemand einen Flugretter an einem NEF
     * anhakt und sich danach fragt, wo der Haken geblieben ist.
     *
     * DIE REGELN KOMMEN AUS `VEHICLE_TYPEN` und nicht aus einer zweiten,
     * abgetippten Aufzählung hier: `betriebsart` (fest oder frei), `rollen`
     * (hat der Typ Vorlagen?), `standort` (Pflicht?). Eine Kopie liefe beim
     * nächsten Typ auseinander, und zwar still.
     *
     * OHNE GEWÄHLTE BETRIEBSART sind Rollen und Fähigkeiten verborgen
     * (Web 7.0.0). Die Betriebsart ist nicht vorbelegt; Rollenhaken zu zeigen,
     * bevor feststeht, welche überhaupt in Frage kommen, hiesse Auswahl
     * anzubieten und sie gleich wieder wegzunehmen.
     *
     * DER STANDORT wird zum Feld, sobald der Typ keinen verlangt. Beim Typ
     * Standard gehört das Rettungsmittel zu der Seite, auf der man steht —
     * dann steht die Kennung fest und darunter der Satz, warum. Auf der
     * STANDORTLISTE (`data-heimat="0"`) gibt es keine solche Seite: Dort
     * bleibt die Auswahl in jedem Typ sichtbar. */
    (function () {
      var dlg = document.getElementById('dlg-veh');
      if (!dlg) { return; }
      var f = dlg.querySelector('form');
      var TYPEN  = <?= json_js(VEHICLE_TYPEN) ?>;
      var heimat = dlg.dataset.heimat || '0';
      var typ    = f.querySelector('[name=typ]');
      var base   = f.querySelector('#dlgveh-base');
      var baseFeld = base.closest('.feld');

      function anpassen() {
        var regel = TYPEN[typ.value] || TYPEN.standard;

        /* 1. Betriebsart — bei Veranstaltung fest auf Boden. Die andere
              Wahl wird gesperrt statt versteckt: Man soll sehen, dass es sie
              gibt und warum sie hier nicht offensteht. */
        var fest = regel.betriebsart;
        f.querySelectorAll('.vehkind-radio').forEach(function (r) {
          r.disabled = (fest !== null && r.value !== fest);
          if (fest !== null) { r.checked = (r.value === fest); }
        });
        f.querySelector('[data-veh-fest]').hidden = (fest === null);

        var gewaehlt = f.querySelector('.vehkind-radio:checked');
        var kind = gewaehlt ? gewaehlt.value : null;

        /* 2. Rollen — nur beim Typ Standard, und darin nur die zur
              Betriebsart passenden. */
        f.querySelectorAll('.rollehaken').forEach(function (lab) {
          var k = lab.dataset.kind;
          var passt = regel.rollen && kind !== null && (k === 'both' || k === kind);
          lab.hidden = !passt;
          if (!passt) { lab.querySelector('input').checked = false; }
        });
        f.querySelector('.rollen-zeile').hidden = !regel.rollen || kind === null;

        /* 3. Fähigkeiten — nur luftgebunden, und nur beim Typ Standard. */
        var caps = f.querySelector('.vehcaps-zeile');
        var capsAn = regel.rollen && kind === 'air';
        caps.hidden = !capsAn;
        if (!capsAn) {
          caps.querySelectorAll('input').forEach(function (i) { i.checked = false; });
        }
        f.querySelector('[data-veh-ohne-vorlagen]').hidden = regel.rollen;

        /* 4. Standort — Feld oder Satz. */
        var wahl = !regel.standort || heimat === '0';
        baseFeld.hidden = !wahl;
        f.querySelector('[data-veh-heimat]').hidden = wahl;
        if (!wahl) { base.value = heimat; }
      }

      typ.addEventListener('change', anpassen);
      f.querySelectorAll('.vehkind-radio').forEach(function (r) {
        r.addEventListener('change', anpassen);
      });
      anpassen();
    })();
    </script>
<?php if ($dlgFehler !== null): ?>
    <script>
    /* NACH EINEM ABGELEHNTEN SPEICHERN GEHT DER DIALOG WIEDER AUF (E-S9-19).
       Er trägt dann die verworfene Eingabe und die Meldung; `auf()` füllt
       bewusst NICHTS nach — im Markup steht schon das Richtige. */
    if (window.edDialog) { window.edDialog.auf(<?= json_js($dlgFehler['dialog']) ?>); }
    </script>
<?php endif; ?>
  <?php elseif ($tab === 'backup'): ?>
    <?php ui_titelzeile(['titel' => 'Backup']); ?>
    <?php /* DREI ZEILEN (E-P3-35). Was in der Datei steht und warum das
             Passwort zählt, gehört an die Handlung — es steht in der Karte
             „Backup erstellen", direkt über der Passwortwahl. */ ?>
    <p class="seiten-erklaerung">Sichert <strong>alle</strong> deine Daten in eine
       einzelne Datei (<code>.edbak</code>), verschlüsselt mit einem Passwort
       deiner Wahl. Ver- und Entschlüsselung passieren vollständig in deinem
       Browser — der Server sieht die Inhalte nie. Dadurch lässt sich ein
       Backup auch in ein anderes Konto einspielen.</p>

    <div id="lockwarn" hidden>
      <?php ui_meldung(
          'Die geschützten Angaben lassen sich gerade nicht entschlüsseln — die '
        . 'Verschlüsselung ist in dieser Sitzung gesperrt.', null, 'warn', '      ',
          ['knopf' => ui_knopf(['text' => 'Entsperren', 'art' => 'neutral',
                                'typ' => 'button', 'attr' => ' id="lockwarn_unlock"'])]); ?>
    </div>

    <?php ui_karte_start(['titel' => 'Backup erstellen', 'id' => 'k-erstellen']); ?>
      <?php /* WAS IN DER DATEI STEHT, GEHÖRT VOR DIE PASSWORTWAHL (M2-03).
               Vorher stand hier „ohne dieses Passwort ist die Datei wertlos" —
               richtig, aber es beantwortet die falsche Frage. Wer ein Passwort
               wählt, muss wissen, WAS er damit schützt. */ ?>
      <?php ui_meldung(
          'In dieser Datei stehen alle geschützten Angaben im Klartext — Namen, '
        . 'Geburtsdaten, Diagnosen, Einsatzorte. Zwischen ihnen und jedem, der die '
        . 'Datei in die Hand bekommt, steht nur dieses Passwort. Es wird nirgends '
        . 'gespeichert und lässt sich nicht zurücksetzen.', null, 'warn', '      '); ?>

      <?php ui_schalter(['name' => 'bpwkonto', 'id' => 'bpwkonto',
                         'label' => 'Mein Kontopasswort verwenden']); ?>
      <p class="feld-klein" id="bpwkontohinweis" hidden>Das Kontopasswort schützt
         dieselben Angaben bereits in der Datenbank — die Datei wird dadurch nicht
         schwächer geschützt, und es ist ein Passwort weniger zu verwahren.
         <strong>Nicht</strong> geeignet, wenn die Datei an jemand anderen gehen soll.</p>

      <?php /* `name="password"` UND `autocomplete` WIE BEI DER ANMELDUNG,
               sobald der Schalter oben an ist (gemeldet 05.09.2026). Ein
               Passwortverwalter entscheidet an genau diesen beiden Angaben,
               ob er ein bekanntes Passwort anbietet oder ein neues vorschlägt:
               `new-password` heisst „hier entsteht etwas Neues", und dann
               bietet er nichts an — auch nicht, wenn das Feld in diesem
               Augenblick nach dem Kontopasswort fragt. Das Umschalten
               übernimmt der Schalter-Handler weiter unten. Der Ausgangswert
               ist `new-password`, weil der Schalter aus ist. */ ?>
      <?php ui_feld(['label' => 'Passwort für das Backup', 'id' => 'bpw1',
                     'name' => 'password',
                     'art' => 'password', 'klasse' => 'bpw1-feld',
                     'klein' => 'Mindestens ' . PW_MIN_LAENGE . ' Zeichen.',
                     'attr' => ' minlength="' . PW_MIN_LAENGE . '" autocomplete="new-password"']); ?>
      <span class="pwstaerke" id="bpwguete"></span>
      <div id="bpw2label">
        <?php ui_feld(['label' => 'Passwort wiederholen', 'id' => 'bpw2',
                       'art' => 'password', 'attr' => ' autocomplete="new-password"']); ?>
      </div>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Backup erstellen', 'art' => 'primaer',
                      'typ' => 'button', 'attr' => ' id="expbtn"']) ?>
      </div>
      <div id="expstate" class="zustandszeile"></div>
    <?php ui_karte_ende(); ?>

    <?php ui_karte_start(['titel' => 'Backup einspielen', 'id' => 'k-einspielen']); ?>
      <p class="feld-hinweis">Spielt ein Backup in <strong>dieses</strong> Konto
         zurück. Vorhandene Einsätze, Tage und Stammdaten bleiben unangetastet —
         das Einspielen ergänzt nur Fehlendes und ist gefahrlos wiederholbar.</p>
      <?php ui_feld(['label' => 'Datei (.edbak)', 'id' => 'bfile', 'name' => 'bfile',
                     'art' => 'file', 'pflicht' => true, 'attr' => ' accept=".edbak"']); ?>
      <?php ui_feld(['label' => 'Passwort des Backups', 'id' => 'ipw',
                     'art' => 'password', 'attr' => ' autocomplete="off"']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Backup einspielen', 'art' => 'primaer',
                      'typ' => 'button', 'attr' => ' id="impbtn"']) ?>
      </div>
      <?php /* Herkunft der geöffneten Datei (M5-13). Steht ÜBER der
               Statuszeile, weil es die Frage beantwortet, die man VOR dem
               Einspielen hat: Ist das die richtige Datei? */ ?>
      <div id="impherkunft" hidden></div>
      <div id="impstate" class="zustandszeile"></div>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Von der Administration freigegebenes Backup (A8.6) -------
             Erscheint NUR, wenn tatsächlich eine Freigabe vorliegt. Ein
             dauerhaft sichtbarer, meist leerer Block wäre eine Frage, die man
             sich bei jedem Besuch neu stellt.

             Der Fall dahinter: Das Konto wurde gelöscht und neu aufgesetzt.
             Die geschützten Angaben des alten Backups hängen am ALTEN
             Inhaltsschlüssel; nur der Wiederherstellungsschlüssel öffnet ihn,
             und der liegt ausschliesslich hier. Deshalb kann die Administration
             ein solches Paket nicht einspielen — sie gibt es frei, und das
             Umschlüsseln passiert in diesem Browser. */ ?>
    <div id="freigabebox" hidden>
      <?php /* DER TITEL BLEIBT „BACKUP" (S8/AP3, E-S8-06). Aus SICHT der
               NutzerIn ist es genau das: ein Backup ihres Kontos, das sie
               einspielt. Dass es die Verwaltung angelegt hat, sagt der
               Hinweistext darunter — „Konto-Backup" ist der Name aus dem
               Blickwinkel der Verwaltung, und ihn hier zu benutzen hiesse,
               eine NutzerIn eine Unterscheidung lernen zu lassen, die sie
               nichts angeht. */ ?>
      <?php ui_karte_start(['titel' => 'Für dich freigegebenes Backup', 'id' => 'k-freigegeben']); ?>
        <p class="feld-hinweis" id="freigabeinfo"></p>
        <?php /* DIE HÜLLE TRÄGT DIE KENNUNG, NICHT DAS FELD (F-S2-F).
                 `freigabeLaden()` blendet die Frage nach dem
                 Wiederherstellungsschlüssel aus, wenn das Paket keine
                 geschützten Angaben enthält — dafür braucht es ein Element,
                 das Beschriftung, Feld und Erklärung zusammen umfasst.
                 `ui_feld()` vergibt eine Kennung nur am Eingabefeld selbst.

                 Bis Web 12.0.0 sprach das Skript trotzdem `freigabecodelabel`
                 an. Die Kennung gab es nirgends, der Zugriff warf, und der
                 Fehler landete im stillen `catch` von `freigabeLaden()` —
                 zusammen mit der Zeile, die den Kasten sichtbar macht. Die
                 Freigabe war damit für NIEMANDEN zu sehen. */ ?>
        <div id="freigabecodelabel">
        <?php ui_feld(['label' => 'Wiederherstellungsschlüssel', 'id' => 'freigabecode',
                       'platzhalter' => 'XXXX-XXXX-XXXX-XXXX',
                       'klein' => 'Der Schlüssel, der bei der Ersteinrichtung einmalig '
                                . 'angezeigt wurde — nicht das Kontopasswort. Ohne ihn lassen '
                                . 'sich die geschützten Angaben dieses Pakets von niemandem '
                                . 'mehr öffnen.',
                       'attr' => ' autocomplete="off"']); ?>
        </div>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Einspielen', 'art' => 'primaer',
                        'typ' => 'button', 'attr' => ' id="freigabebtn"']) ?>
        </div>
        <div id="freigabestate" class="zustandszeile"></div>
        <p class="feld-klein">Das Einspielen <strong>ergänzt</strong>: Vorhandene
           Einträge bleiben unverändert, es kommt nur hinzu, was fehlt.</p>
      <?php ui_karte_ende(); ?>
    </div>

    <?php /* Ruestzeug der Verschluesselung (Baustein ui_krypto_bootstrap()),
             dazu pwquality.js fuer die Guetepruefung des Backup-Passworts
             (B9, M2-03). */ ?>
    <?php ui_krypto_bootstrap(['keycheck' => true, 'csrf' => true,
                               'guete' => true, 'einzug' => '    ']); ?>
    <?php /* patient.js liefert die gemeinsame Entschluesselungsschleife
             (Baustein B8), die der Backup-Lauf seit Web 4.6.0 benutzt. */ ?>
    <?php /* html.js liefert EdHtml.escape() — melde() setzt fremden Text in
             eine Meldung, und der muss maskiert sein: In „Import
             fehlgeschlagen: …" steckt eine Fehlermeldung, die aus einer
             fremden Datei stammen kann. */ ?>
    <script src="<?= asset('assets/html.js') ?>"></script>
    <script src="<?= asset('assets/patient.js') ?>"></script>
    <?php /* zip.js: Seit Containerfassung 4 (S2/AP5) ist ein Backup ein
             ZIP mit versiegelten Teilen — geschrieben beim Sichern, gelesen
             beim Einspielen. Dieselbe vendorierte Bibliothek, die der Export
             und der Import schon benutzen (assets/vendor/zipjs.min.js,
             docs/Lizenzen.md). Sie steht als eigene Zeile und NICHT in der
             Skriptliste von ui_krypto_bootstrap(): Der Baustein ersetzt dort
             seine Vorgabeliste, und crypto.js fiele weg. */ ?>
    <script src="<?= asset('assets/vendor/zipjs.min.js') ?>"></script>
    <script>
    // Eigenes Konto — nur fuer den Vergleich mit der Herkunft der Datei (M5-13).
    const KONTO_MAIL = <?= json_js($userEmail) ?>;
    const KONTO_NAME = <?= json_js($userName) ?>;
    /* Die Fassung der Anwendung wandert ins Manifest des Backups: Wer eine
       Datei in zwei Jahren wiederfindet, soll ihr ansehen, womit sie
       entstanden ist. */
    const WEB_VERSION = <?= json_js(WEB_VERSION) ?>;

    /* EINE WACHE, wie sie import_ui.js seit je hat: Ein vergessener
       Skriptverweis ergibt sonst „zip is not defined" genau in dem Augenblick,
       in dem jemand seine Daten sichern will. */
    if (typeof zip === 'undefined') {
      throw new Error('Die Bibliothek zum Schreiben von Archiven ist nicht geladen.');
    }
    const expState = document.getElementById('expstate');
    const impState = document.getElementById('impstate');

    /* ---- Zustandszeilen als Meldungen (E-P3-16) --------------------------
     *
     * Das Backup meldet viel: Fortschritt („Daten werden geladen …"),
     * Fehlschläge („Das ist nicht dein Kontopasswort") und Erfolge („Fertig:
     * 82 Einsätze"). Bis Web 9.7.1 stand alles in derselben grauen Zeile —
     * ein misslungener Export sah aus wie ein Zwischenstand.
     *
     * `melde()` trägt den Ton: 'fehler' rot, 'ok' blau mit Haken, sonst eine
     * schlichte Zeile für den laufenden Fortschritt. Ein Fortschrittstext
     * bekommt bewusst KEIN Symbol — er ist kein Ergebnis, und ein Haken
     * daneben behauptete eines.
     *
     * Die Zuweisung `el.textContent = …` funktioniert weiterhin (die
     * Zustandszeile ist ein gewöhnliches Element); sie ergibt dann den
     * schlichten Ton. So bleiben die Stellen richtig, die nur Fortschritt
     * melden. */
    function melde(el, text, ton) {
      if (!el) { return; }
      if (!text) { el.innerHTML = ''; return; }
      if (!ton) { el.textContent = text; return; }
      /* SYMBOLE WIE IM BAUSTEIN, nicht ungefähr wie im Baustein.
       * ui_meldung_markup() (ui.php) führt die Tabelle
       * ['fehler'=>'warnung','warn'=>'warnung','ok'=>'haken','info'=>'hinweis'],
       * Design.md 9.5 schreibt sie vor. Dieser Nachbau ließ `warn` in den
       * Sonst-Zweig fallen und zeigte das Hinweiszeichen — bei genau den
       * Meldungen, die auffallen sollen. Erreichbar ist der Ton auf dieser
       * Seite an drei Stellen: unlesbare geschützte Angaben oder eine nicht
       * mitgesicherte Spur beim Sichern, abgelehnte Spuren beim Einspielen,
       * dasselbe auf dem Freigabeweg. (Zwei davon kamen mit S2 dazu; als der
       * Fehler gefunden wurde, war es noch eine.) */
      const symbole = { fehler: 'warnung', warn: 'warnung', ok: 'haken', info: 'hinweis' };
      const sym = symbole[ton] || 'hinweis';
      el.innerHTML = '<div class="meldung meldung-' + ton + '" role="'
        + (ton === 'fehler' ? 'alert' : 'status') + '">'
        + edSymbol(sym, 'symbol-gross')
        + '<p>' + EdHtml.escape(text) + '</p></div>';
    }

    /* Liefert den Inhaltsschluessel; ist er gesperrt, bietet EdUnlock den
     * Entsperrdialog an. Wird er abgebrochen, bleibt der Hinweis stehen —
     * sein Knopf ruft dieselbe Funktion erneut auf. Export und Import des
     * Backups brauchen den Schluessel beide. */
    async function ck() {
      const k = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
      document.getElementById('lockwarn').hidden = !!k;
      return k;
    }
    document.getElementById('lockwarn_unlock').addEventListener('click', () => ck());
    ck();

    /* ---- Kontopasswort als Backup-Passwort anbieten (M2-03, D4) ----------
     *
     * WARUM DAS SICHER GEHT, OHNE DEN SERVER ZU FRAGEN
     * Das Kontopasswort liegt hier nicht vor — die Sitzung führt nur die
     * abgeleiteten Schlüssel. Wer es benutzen will, tippt es also erneut ein.
     * Ob es stimmt, lässt sich im Browser selbst feststellen: Aus Passwort und
     * Salz entsteht der Datenschlüssel, und mit dem muss sich die gespeicherte
     * Hülle öffnen lassen. Passt es nicht, ist es das falsche Passwort.
     *
     * WARUM ES NUR HIER ANGEBOTEN WIRD UND NICHT BEIM EXPORT
     * Ein Backup ist für einen selbst. Eine Exportdatei ist ausdrücklich
     * zum Weitergeben gedacht — wer sie mit seinem Kontopasswort verschlüsselt,
     * gibt es dem Empfänger mit. */
    const bpw1 = document.getElementById('bpw1');
    const bpw2 = document.getElementById('bpw2');
    const bpwKonto = document.getElementById('bpwkonto');
    const bpwGuete = document.getElementById('bpwguete');
    EdPwQuality.beobachte(bpw1, bpwGuete);

    bpwKonto.addEventListener('change', () => {
      const an = bpwKonto.checked;
      document.getElementById('bpwkontohinweis').hidden = !an;
      // Die Wiederholung entfällt: Ein falsch getipptes Kontopasswort fällt
      // unten beim Öffnen der Hülle auf, nicht erst beim Öffnen der Datei.
      document.getElementById('bpw2label').hidden = an;
      /* Die Beschriftung liegt seit O8c als eigenes <label class="feld-label">
         neben dem Feld, nicht mehr als Textknoten davor — `parentElement
         .firstChild` traf damit den Zeilenumbruch statt der Beschriftung. */
      document.querySelector('label[for="bpw1"]').textContent = an
        ? 'Kontopasswort'
        : 'Passwort für das Backup';
      document.querySelector('.bpw1-feld .feld-klein').hidden = an;
      bpwGuete.hidden = an;
      /* Das Feld wechselt seine BEDEUTUNG, also auch seine Ankuendigung an
         den Passwortverwalter: an = das Kontopasswort, das er kennt
         (`current-password`); aus = ein frisch zu waehlendes Backup-Passwort
         (`new-password`). Mit dem festen `new-password` sah jeder Verwalter
         hier ein neues Feld und bot nichts an. */
      bpw1.autocomplete = an ? 'current-password' : 'new-password';
      bpw1.value = '';
      expState.textContent = '';
    });

    /** Prüft das eingegebene Passwort und liefert es zurück — oder null. */
    async function backupPasswort() {
      const pw = bpw1.value;
      if (bpwKonto.checked) {
        if (pw === '') { melde(expState, 'Bitte das Kontopasswort eingeben.', 'fehler'); return null; }
        if (!PAT_WRAP) {
          melde(expState, 'Für dieses Konto liegt keine Schlüsselhülle vor — '
                               + 'bitte ein eigenes Backup-Passwort wählen.', 'fehler');
          return null;
        }
        expState.textContent = 'Kontopasswort wird geprüft…';
        try {
          const k = await EdCrypto.deriveKeys(pw, KDF_SALT, KDF_ITER);
          await EdCrypto.decrypt(k.dataKeyHex, PAT_WRAP);
        } catch (e) {
          melde(expState, 'Das ist nicht dein Kontopasswort. Es wurde keine '
                               + 'Datei erzeugt.', 'fehler');
          return null;
        }
        return pw;
      }
      const guete = EdPwQuality.pruefe(pw);
      if (!guete.erlaubt) { melde(expState, guete.meldung, 'fehler'); return null; }
      if (pw !== bpw2.value) { melde(expState, 'Die Passwörter stimmen nicht überein.', 'fehler'); return null; }
      return pw;
    }

    // ---- Export: Daten holen, entschlüsseln, versiegeln, herunterladen ----
    document.getElementById('expbtn').addEventListener('click', async () => {
      const pw = await backupPasswort();
      if (pw === null) { return; }
      const key = await ck();
      if (!key) { melde(expState, 'Entschlüsselung gesperrt — siehe Hinweis oben.', 'fehler'); return; }
      try {
        expState.textContent = 'Daten werden geladen…';

        /* ANTWORTSTATUS PRÜFEN, BEVOR IRGENDETWAS ENTSTEHT.
         *
         * Der Server sieht einen Fehlerfall ausdrücklich vor und antwortet
         * dann mit {error, meldung} und einem 4xx/5xx-Status. Ohne diese
         * Prüfung liefen alle Schleifen unten über nichts — und es entstand
         * eine echte .edbak-Datei mit korrektem Kopf und richtigem Passwort,
         * die ausschließlich die Fehlermeldung enthielt. Sie ließe sich
         * öffnen und wäre erst beim Einspielen als leer zu erkennen,
         * möglicherweise Monate später. */
        /* ---- Der Kopf: Stammdaten, Diensttage, die Zahl der Einträge ---- */
        async function holeTeil(adresse) {
          const a = await fetch(adresse);
          if (!a.ok) {
            let grund = 'HTTP ' + a.status;
            try { const j = await a.json(); grund = j.meldung || j.error || grund; } catch (e2) {}
            throw new Error('Die Daten konnten nicht geladen werden (' + grund + '). '
                          + 'Es wurde KEINE Datei erzeugt.');
          }
          return a.json();
        }

        const kopf = await holeTeil('api/backup_data.php?teil=kopf');
        /* Arbeitsfelder gehören nicht in die Datei — hier steht keines, aber
           die Regel gilt für jeden Teil und nicht nur für die, bei denen man
           gerade daran denkt. */
        for (const k of Object.keys(kopf)) { if (k.startsWith('_')) { delete kopf[k]; } }
        if (!Array.isArray(kopf.days) || typeof kopf.eintraege_gesamt !== 'number') {
          throw new Error('Die Antwort des Servers ist unvollständig. Es wurde KEINE Datei erzeugt.');
        }
        if (!kopf.eintraege_gesamt && !kopf.days.length) {
          melde(expState, 'Es sind keine Daten vorhanden, die gesichert werden könnten. '
                               + 'Es wurde keine Datei erzeugt.', 'fehler');
          return;
        }

        /* ---- Die Einträge in Fenstern ------------------------------------
         *
         * WARUM NICHT AM STÜCK. Der Kern eines 5000er-Bestands ist 10,5 MB.
         * Auf dem Rückweg wäre das ein POST von 9,4 MB gegen ein Serverlimit,
         * das niemand kennt — nginx deckelt in der Vorgabe bei 1 MB. Und im
         * Server kostet der Bau am Stück 39,5 MB von 64 (Z3), wachsend mit
         * dem Bestand; in Fenstern sind es 10,0 MB. Beides gemessen am
         * 31.08.2026, die Zahlen stehen in `api/backup_data.php`.
         *
         * WARUM ERST ALLE, DANN VERSIEGELN. Die Zusatzdaten jedes Teils
         * tragen `<nr>/<gesamt>`; die Gesamtzahl steht erst fest, wenn auch
         * die Zahl der Spurteile bekannt ist — und die hängt an den
         * Punktzahlen, die in den Fenstern stehen. Die Fenster liegen dabei
         * als getrennte Zeichenketten vor, keine davon groß: 44 Stück zu
         * höchstens 0,44 MB statt einer zu 10,5 MB.
         */
        /* 250 EINTRÄGE JE FENSTER — die Zahl kommt von der strengsten
           verbreiteten Servergrenze, nicht aus dem Gefühl.
           `client_max_body_size` steht bei nginx in der Vorgabe auf **1 MB**,
           und der Rückweg schickt genau diese Fenster als POST zurück.
           Gemessen am 5000er-Bestand: 500 Einträge ergeben ein größtes
           Fenster von 0,87 MB — unter der Grenze, aber ohne Reserve. Bei 250
           sind es 0,44 MB in 44 Anfragen. */
        const FENSTER = 250;
        const eintragsteile = [];
        const index = [];
        let n = 0, unlesbar = 0;

        for (let ab = 0; ab < kopf.eintraege_gesamt; ab += FENSTER) {
          expState.textContent = `Einträge werden geladen (${ab} von ${kopf.eintraege_gesamt})…`;
          const f = await holeTeil('api/backup_data.php?teil=eintraege'
                                 + '&ab=' + ab + '&anzahl=' + FENSTER);
          if (!Array.isArray(f.missions) || !Array.isArray(f.rest_segments)) {
            throw new Error('Die Antwort des Servers ist unvollständig. '
                          + 'Es wurde KEINE Datei erzeugt.');
          }
          /* NACHZÄHLEN, WAS ANGEKOMMEN IST (S2/AP5b).
             Die Schleife rückt um FENSTER weiter, gleichgültig wie viel
             zurückkam. Lieferte ein Fenster weniger — aus welchem Grund auch
             immer —, fehlten diese Einträge im Backup, und die
             Meldung am Ende lautete trotzdem „Fertig". Der Endpunkt weist
             eine zu große `anzahl` heute mit 400 ab, statt still zu kürzen;
             diese Zeile ist die zweite Schranke, die nicht davon abhängt,
             dass die erste bleibt. */
          const bekommen = f.missions.length + f.rest_segments.length;
          const soll = Math.min(FENSTER, kopf.eintraege_gesamt - ab);
          if (bekommen !== soll) {
            throw new Error(`Der Server lieferte für das Fenster ab ${ab} `
              + `${bekommen} statt ${soll} Einträgen. Es wurde KEINE Datei erzeugt.`);
          }

          /* Entschlüsseln je Fenster — dieselbe Schleife wie bisher (Baustein
             B8), nur eben stückweise. Damit ist auch dieser Schritt
             beschränkt und nicht mehr so groß wie der Bestand. */
          const zahl = await EdPat.entschluessleListe(f.missions, key);
          n += zahl.ok; unlesbar += zahl.unlesbar;
          for (const m of f.missions) {
            if (m._patState === 'ok') {
              m.pat = m._pat;
              /* Das Entfernen des Chiffretexts gehört in DIESEN Zweig: Ein
                 Einsatz, dessen Angaben sich gerade NICHT entschlüsseln
                 ließen, verlöre beim Sichern sonst seinen Chiffretext — und
                 die Meldung lautete „Fertig". */
              delete m.pat_blob;
            } else if (m._patState === 'unlesbar') {
              /* Nicht lesbar: Chiffretext MITNEHMEN statt verwerfen. */
              m.pat_unreadable = true;
            }
            delete m._pat; delete m._patState; delete m._patFehler;
          }

          for (const e of (f._spur_index || [])) { index.push(e); }
          delete f._spur_index;      // Arbeitsfeld, gehört nicht in die Datei
          eintragsteile.push(f);
        }

        /* DIE TEILE WERDEN VORHER GEPLANT, nicht unterwegs gebildet.
         *
         * Grund: Die Zusatzdaten jedes Teils tragen `<nr>/<gesamt>` — die
         * Gesamtzahl muss also feststehen, BEVOR das erste Teil versiegelt
         * wird. Die Punktzahl je Spur steht in den Einträgen; damit lässt
         * sich die Einteilung ausrechnen, ohne einen Blob geholt zu haben.
         *
         * Geschnitten wird an SPURGRENZEN: Eine Spur liegt ganz in einem
         * Teil. Eine über die Grenze gestückelte wäre nur mit beiden Teilen
         * brauchbar, und dann hätte die Teilung nichts gebracht.
         *
         * 250 000 Punkte je Teil: gemessen kostet ein Punkt 3,56 Byte als
         * SPUR1 (S2/AP1), Base64 macht 4,77 daraus — also rund 1,2 MB je
         * Teil im Regelfall. */
        const TEIL_PUNKTE = 250000;
        const teileplan = [];
        let laufend = [], laufendePunkte = 0;
        for (const e of index) {
          if (laufend.length && laufendePunkte + (e.n || 0) > TEIL_PUNKTE) {
            teileplan.push(laufend); laufend = []; laufendePunkte = 0;
          }
          laufend.push(e); laufendePunkte += (e.n || 0);
        }
        if (laufend.length) { teileplan.push(laufend); }

        const gesamt = 1 + eintragsteile.length + teileplan.length;
        const kennung = EdCrypto.randomHex(16);

        /* EINE PBKDF2 FÜR ALLE TEILE (E-S2-10). Bei zwanzig Teilen wären es
         * sonst zwanzig Ableitungen zu je KDF_ITER Runden — auf einem
         * gedrosselten Telefon Minuten reines Warten. */
        expState.textContent = 'Schlüssel wird abgeleitet…';
        const vorgang = await EdCrypto.backupSchluessel(pw, KDF_ITER);

        const schreiber = new zip.BlobWriter('application/octet-stream');
        const zw = new zip.ZipWriter(schreiber, { level: 0 });
        const teileliste = [];
        let nr = 1;

        async function teilAnhaengen(name, art, inhalt) {
          const bytes = await EdCrypto.sealTeilJson(vorgang, inhalt,
            EdCrypto.aadTeil(kennung, name, nr, gesamt));
          teileliste.push({ name, art, sha256: await EdCrypto.sha256Hex(bytes) });
          /* `level: 0` — gespeichert, nicht gepackt. Die Teile sind bereits
             gzip UND verschlüsselt; ein zweiter Packlauf kostet Zeit und
             bringt nichts. */
          await zw.add(name, new zip.Uint8ArrayReader(bytes), { level: 0 });
          nr++;
          return bytes.length;
        }

        expState.textContent = 'Kopf wird verschlüsselt…';
        await teilAnhaengen('kopf.edbak', 'kopf', kopf);
        for (const [i, teil] of eintragsteile.entries()) {
          expState.textContent = `Einträge werden verschlüsselt `
            + `(Teil ${i + 1} von ${eintragsteile.length})…`;
          await teilAnhaengen('eintraege/' + String(i + 1).padStart(4, '0') + '.edbak',
                              'eintraege', teil);
          eintragsteile[i] = null;     // versiegelt — die Rohform wird nicht mehr gebraucht
        }

        /* Die Blobs holt der Server in Blöcken; 25 Kennungen je Anfrage,
           dieselbe Zahl wie im Export. */
        const BLOCK = 25;
        const fehlerhaft = [];
        let punkteGesamt = 0, spurenGesamt = 0;

        for (const [i, teil] of teileplan.entries()) {
          expState.textContent = `GPS-Daten werden geholt (Teil ${i + 1} von ${teileplan.length})…`;
          const eintraege = [];
          for (const art of ['mission', 'rest']) {
            const dieser = teil.filter(e => e.art === art);
            for (let k = 0; k < dieser.length; k += BLOCK) {
              let rest = dieser.slice(k, k + BLOCK).map(e => e.id);
              const refNach = new Map(dieser.slice(k, k + BLOCK).map(e => [e.id, e.spur_ref]));
              /* `offen` heißt: Dem Server ist die Zeit ausgegangen, bevor er
                 alle Spuren des Blocks kodiert hatte. Dann wird derselbe Rest
                 noch einmal geholt — nicht abgebrochen, denn es ist kein
                 Fehler, sondern eine Grenze. */
              for (let versuch = 0; rest.length && versuch < 10; versuch++) {
                const a = await fetch('api/backup_spuren.php', {
                  method: 'POST',
                  headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
                  body: JSON.stringify({ owner_type: art, ids: rest }),
                });
                if (!a.ok) {
                  let grund = 'HTTP ' + a.status;
                  try { const j = await a.json(); grund = j.meldung || j.error || grund; } catch (e2) {}
                  throw new Error('Die GPS-Daten konnten nicht geladen werden (' + grund
                                + '). Es wurde KEINE Datei erzeugt.');
                }
                const spuren = (await a.json()).spuren || {};
                const nochOffen = [];
                for (const [idText, s] of Object.entries(spuren)) {
                  const id = Number(idText);
                  if (s.offen) { nochOffen.push(id); continue; }
                  if (s.leer) { continue; }
                  if (s.fehler) {
                    fehlerhaft.push(`${art} ${id}: ${s.grund || s.fehler}`);
                    continue;
                  }
                  eintraege.push({ spur_ref: refNach.get(id), blob: s.blob,
                                   stufe: s.stufe, n_original: s.n_original, n: s.n });
                  punkteGesamt += s.n; spurenGesamt++;
                }
                rest = nochOffen;
              }
              if (rest.length) {
                throw new Error('Der Server kam mit ' + rest.length + ' Aufzeichnungen auch nach '
                              + 'zehn Anläufen nicht durch. Es wurde KEINE Datei erzeugt.');
              }
            }
          }
          const name = 'spuren/' + String(i + 1).padStart(4, '0') + '.edbak';
          expState.textContent = `Teil ${i + 1} von ${teileplan.length} wird verschlüsselt…`;
          await teilAnhaengen(name, 'spuren', { spuren: eintraege });
        }

        /* DAS MANIFEST ZULETZT — es kennt dann alle Prüfsummen. */
        expState.textContent = 'Manifest wird geschrieben…';
        const manifest = {
          format: 'einsatzdoku-backup-manifest',
          fassung: 4,
          kennung: kennung,
          erzeugt_am: new Date().toISOString(),
          web_version: WEB_VERSION,
          nutzlast: kopf.version,
          teile: teileliste,
          eintragsteile: eintragsteile.length,
          eintraege: kopf.eintraege_gesamt,
          spurteile: teileplan.length,
          spuren: spurenGesamt,
          punkte: punkteGesamt,
          pat_key_check: kopf.pat_key_check || null,
          /* WIE VIELE EINSAETZE IHRE ANGABEN VERSCHLUESSELT MITBRINGEN
             (S2/AP5b). Der Erzeuger weiss es — er hat es eben gezaehlt.
             Der Einspielweg kann es bei Fassung 4 NICHT mehr feststellen,
             ohne alle Eintragsteile zu oeffnen; ohne diese Zahl muesste er
             raten und fragte dann auch dann, wenn es nichts zu fragen gibt
             (F-S2-D). */
          unlesbar: unlesbar,
        };
        const manifestBytes = await EdCrypto.sealTeilJson(vorgang, manifest,
          EdCrypto.aadManifest());
        await zw.add('manifest.edbak', new zip.Uint8ArrayReader(manifestBytes), { level: 0 });
        await zw.close();

        const blob = await schreiber.getData();
        const url = URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        /* DER DATEINAME STEHT IN EINER VARIABLEN, weil ihn zwei Stellen
         * brauchen: der Download und die Meldung darunter. Zwei Ausdrücke
         * nebeneinander liefen mit dem nächsten Tageswechsel auseinander. */
        const dateiname = 'einsatzdoku-backup-'
          + new Date().toISOString().slice(0, 10) + '.edbak';
        a.download = dateiname;
        a.click();
        URL.revokeObjectURL(url);

        const mb = (blob.size / 1048576).toFixed(1).replace('.', ',');
        melde(expState, `Fertig: ${kopf.eintraege_gesamt} Einträge `
          + `(davon ${n} mit geschützten Angaben), `
          + `${(kopf.days || []).length} Diensttage, `
          + `${spurenGesamt} Aufzeichnungen mit ${punkteGesamt.toLocaleString('de-DE')} Punkten `
          + `in ${gesamt} ${gesamt === 1 ? 'Teil' : 'Teilen'} `
          + `— ${mb} MB.`
          /* DASS DIE DATEI DA IST, MUSS DASTEHEN (Rückmeldung nach P3).
           *
           * Der Download läuft ohne Dialog und ohne Ton durch; wer nicht
           * gerade auf die Download-Leiste des Browsers sieht, merkt nichts
           * davon und sucht anschließend eine Datei, deren Namen er nicht
           * kennt. Der Name ist deshalb der eigentliche Inhalt dieses Satzes
           * — „wurde heruntergeladen" allein hilft beim Suchen nicht.
           *
           * WO sie liegt, sagt der Satz bewusst NICHT: Das entscheidet die
           * Einstellung des Browsers, nicht diese Anwendung. Eine Zusage
           * „in deinem Download-Ordner" wäre für jeden falsch, der sein
           * Ziel selbst wählt.
           *
           * Er steht VOR den ACHTUNG-Blöcken, damit eine Warnung das letzte
           * bleibt, was gelesen wird. */
          + ` Die Datei „${dateiname}" wurde heruntergeladen.`
          + (unlesbar
              ? ` ACHTUNG: ${unlesbar} Einsätze ließen sich nicht entschlüsseln. `
                + 'Ihre Angaben sind verschlüsselt in der Datei enthalten und bleiben '
                + 'lesbar, wenn das Backup in DIESES Konto zurückgespielt wird. '
                + 'Bitte klären, warum der Schlüssel nicht passt, bevor weitere '
                + 'Schritte unternommen werden.'
              : '')
          + (fehlerhaft.length
              /* EINE ABGELEHNTE SPUR WIRD GENANNT, nicht verschwiegen. Die
                 Datei ist im Übrigen vollständig; das Fehlen einer Spur
                 fiele sonst erst beim Einspielen auf — und da ist die Quelle
                 vielleicht schon weg. */
              ? ` ACHTUNG: ${fehlerhaft.length} `
                + `${fehlerhaft.length === 1 ? 'Aufzeichnung konnte' : 'Aufzeichnungen konnten'} nicht `
                + 'mitgesichert werden: ' + fehlerhaft.slice(0, 3).join(' · ')
                + (fehlerhaft.length > 3 ? ' · …' : '')
              : ''),
          /* Ein Export mit unlesbaren Blobs oder fehlenden Spuren ist kein
             reiner Erfolg: Die Datei ist vollständig bis auf das Genannte. */
          (unlesbar || fehlerhaft.length) ? 'warn' : 'ok');
      } catch (e) {
        melde(expState, 'Export fehlgeschlagen: ' + e.message, 'fehler');
      }
    });

    /* ---- Rückmeldung einer Wiederherstellung, an EINER Stelle (E-S1-08) --
     *
     * Zwei Wege spielen ein — die eigene Datei und das freigegebene Backup
     * der Administration —, und beide melden dasselbe. Bis Web 7.3.1 hatten
     * sie zwei getrennte Textbausteine, und die liefen auseinander: Der zweite
     * fasste alle Überspringgründe zu „bereits vorhanden oder unbrauchbar"
     * zusammen und nannte weder Standortdaten noch die Höhenfehler. Jetzt gibt
     * es einen Baustein.
     *
     * WAS ER SAGEN MUSS, und warum jedes Stück davon:
     *  - Was angekommen ist (die vier Zahlen).
     *  - Was NICHT angekommen ist, mit GRUND. „40 übersprungen" ist nicht
     *    deutbar: Es kann „war alles schon da" heißen oder „war alles kaputt".
     *  - Was in den PAPIERKORB gegangen ist. Diese Einträge stecken in den
     *    Zahlen oben mit drin; ohne den Satz wären sie unauffindbar — sie
     *    stehen ja gerade nicht in der Tagesliste.
     *  - Dass die Frist NEU beginnt (E-S1-03). Wer ein altes Backup
     *    einspielt, würde sonst annehmen, seine Einträge verfielen morgen.
     */
    const GRUND_TEXT = {
      bereits_vorhanden: 'bereits vorhanden',
      datum_oder_zeit:   'unbrauchbares Datum oder Zeit',
      aufbau:            'unbrauchbarer Aufbau',
      tag_im_papierkorb: 'Diensttag liegt hier im Papierkorb',
      tag_unbrauchbar:   'unbrauchbares Datum des Diensttags',
      tag_uebersprungen: 'Diensttag wurde übersprungen',
      tag_mehrdeutig:    'Diensttag nicht eindeutig zuzuordnen',
      /* Die vier Gründe, aus denen ein Sperrvermerk des Schnitts verworfen
         wird (Nutzlast 9). Ohne Eintrag hier greift der Rückfall
         `GRUND_TEXT[k] || k` und zeigt den Rohwert — also „schnitt_ohne_quelle
         3" statt eines Satzes. */
      schnitt_ohne_ziel:   'Sperrvermerk ohne Einsatz',
      schnitt_ohne_quelle: 'Sperrvermerk ohne Quelle',
      schnitt_werte:       'Sperrvermerk mit unbrauchbaren Werten',
      schnitt_aufbau:      'Sperrvermerk mit unbrauchbarem Aufbau',
    };

    /* „1 Diensttage" ist ein kleiner Fehler mit großer Wirkung: Er lässt den
       ganzen Satz nach Maschine aussehen — und diese Sätze nennen Zahlen, die
       oft genug auf 1 stehen. */
    const zahlwort = (n, ein, viele) => (n === 1 ? '1 ' + ein : (n || 0) + ' ' + viele);

    function restoreBericht(s, zusatz) {
      const gruende = s.skipped_reasons && Object.keys(s.skipped_reasons).length
        ? ' — ' + Object.entries(s.skipped_reasons)
            .map(([k, v]) => (GRUND_TEXT[k] || k) + ' ' + v).join(', ')
        : '';
      const uebersprungen = (s.missions_skipped || s.rests_skipped || gruende)
        ? ` Übersprungen: ${zahlwort(s.missions_skipped, 'Einsatz', 'Einsätze')}, `
          + `${zahlwort(s.rests_skipped, 'Ruhesegment', 'Ruhesegmente')}${gruende}.`
        : '';
      /* SPERRVERMERKE DES SCHNITTS (Nutzlast 9, Backlog Nr. 63).
         Die Zeile erscheint NUR, wenn die Datei überhaupt welche trug — eine
         Zeile „0 übernommen, 0 verworfen" wäre eine Antwort auf eine Frage,
         die niemand gestellt hat. „Verworfen" wird nur genannt, wenn es
         welche gab: Es ist die einzige der drei Zahlen, die einen Verlust
         bedeutet, und sie soll auffallen statt in einer Aufzählung zu
         verschwinden. Die Gründe stehen daneben in `skipped_reasons`. */
      const sc = s.schnitte || {};
      const scSumme = (sc.uebernommen || 0) + (sc.uebersprungen || 0) + (sc.verworfen || 0);
      const schnitte = scSumme
        ? ` Sperrvermerke des Schneidens: `
          + `${sc.uebernommen || 0} übernommen`
          + (sc.uebersprungen ? `, ${sc.uebersprungen} übersprungen (Einsatz war schon da)` : '')
          + (sc.verworfen ? `, ${sc.verworfen} verworfen` : '')
          + '.'
        : '';
      const pk = s.papierkorb || {};
      const pkSumme = (pk.einsaetze || 0) + (pk.diensttage || 0) + (pk.ruhezeiten || 0);
      const papierkorb = pkSumme
        ? ` In den Papierkorb übernommen: `
          + `${zahlwort(pk.einsaetze, 'Einsatz', 'Einsätze')}, `
          + `${zahlwort(pk.ruhezeiten, 'Ruhesegment', 'Ruhesegmente')}, `
          + `${zahlwort(pk.diensttage, 'Diensttag', 'Diensttage')} — `
          + `die <?= TRASH_DAYS ?>-Tage-Frist beginnt für sie neu.`
        : '';
      return `${zahlwort(s.missions, 'Einsatz', 'Einsätze')} übernommen, `
        + `${zahlwort(s.rests, 'Ruhesegment', 'Ruhesegmente')}, `
        + `${zahlwort(s.days, 'Diensttag', 'Diensttage')}, `
        + `${zahlwort(s.stammdaten, 'Standortdaten-Eintrag', 'Standortdaten-Einträge')}`
        + (s.stammdaten_skipped
            ? ` (${s.stammdaten_skipped} übersprungen, bereits systemweit vorhanden)` : '')
        + '.' + uebersprungen + schnitte + papierkorb + (zusatz || '')
        /* Die Höhenberechnung läuft seit Web 4.6.0 NACH dem Einspielen und
         * kann einzeln scheitern, ohne die Wiederherstellung zu gefährden
         * (M5-05). Wenn das passiert, gehört es gesagt — sonst fehlt später
         * eine Höhenangabe ohne erkennbaren Grund. */
        + (s.hoehe_fehler
            ? ` Bei ${s.hoehe_fehler} Einsätzen ließ sich die Einsatzort-Höhe nicht `
              + `berechnen; die Einsätze selbst sind vollständig übernommen.`
            : '');
    }

    // ---- Import: läuft vollständig im Browser ----
    /* ---- Ein mehrteiliges Backup öffnen (S2/AP5, Containerfassung 4) --
     *
     * Reihenfolge, und jeder Schritt hat einen Grund:
     *
     *   1. Manifest holen und seinen KOPF lesen — dort stehen Salz und
     *      Rundenzahl. Ohne sie lässt sich der Schlüssel nicht ableiten, und
     *      abgeleitet wird EINMAL für alle Teile (E-S2-10).
     *   2. Manifest entsiegeln. Geht das nicht, ist entweder das Passwort
     *      falsch oder die Datei beschädigt — und das ist der einzige Punkt,
     *      an dem diese beiden noch zusammenfallen dürfen.
     *   3. VOLLSTÄNDIGKEIT prüfen, bevor irgendetwas eingespielt wird. Ein
     *      fehlendes Teil soll auffallen, solange noch nichts geschehen ist —
     *      nicht auf halbem Weg, wenn der Bestand schon halb angelegt ist.
     *   4. Erst dann Teil für Teil, jedes gegen seine Prüfsumme und mit
     *      seinen Zusatzdaten.
     *
     * Der Archivleser bleibt offen; die Teile werden einzeln geholt, statt
     * die ganze Datei ein zweites Mal in den Speicher zu legen. */
    async function fassung4Oeffnen(pw, bytes) {
      const leser = new zip.ZipReader(new zip.Uint8ArrayReader(bytes));
      const eintraege = await leser.getEntries();
      const nach = new Map(eintraege.map(e => [e.filename, e]));
      const holen = async (name) => nach.get(name).getData(new zip.Uint8ArrayWriter());

      if (!nach.has('manifest.edbak')) {
        await leser.close();
        throw new Error('Diese Datei ist ein Archiv, aber kein Backup dieser '
          + 'Anwendung: Das Manifest fehlt. Womöglich ist es ein Export (CSV/Excel) '
          + 'statt eines Backups.');
      }
      const mBytes = await holen('manifest.edbak');
      const kopf = EdCrypto.teilKopf(mBytes);
      const vorgang = await EdCrypto.backupSchluessel(pw, kopf.iter, kopf.salt);
      const manifest = await EdCrypto.openTeilJson(vorgang, mBytes,
        EdCrypto.aadManifest(), 'Das Manifest des Backups');

      const teile = manifest.teile || [];
      if (!teile.length || teile[0].art !== 'kopf') {
        await leser.close();
        throw new Error('Das Manifest nennt keinen Kopf — das Backup ist unvollständig.');
      }
      const fehlend = teile.filter(t => !nach.has(t.name)).map(t => t.name);
      if (fehlend.length) {
        await leser.close();
        throw new Error(`Dem Backup fehlen ${fehlend.length} von ${teile.length} `
          + `Teilen: ${fehlend.slice(0, 3).join(', ')}`
          + (fehlend.length > 3 ? ' …' : '')
          + '. Es wurde nichts geändert.');
      }

      const teilOeffnen = async (index) => {
        const t = teile[index];
        const roh = await holen(t.name);
        /* DIE PRÜFSUMME ZUERST. Sie sagt deutlicher, was los ist, als die
           Zusatzdaten: „dieses Teil ist nicht das, das hier stehen soll"
           gegen „ließ sich nicht öffnen". Beide fangen dieselben Fälle; für
           wen ein Backup nicht aufgeht, ist der Unterschied der zwischen
           zehnmal Passwort tippen und die richtige Datei suchen. */
        if (t.sha256 && await EdCrypto.sha256Hex(roh) !== t.sha256) {
          throw new Error(`Das Teil ${t.name} ist nicht das, das laut Manifest hier `
            + 'stehen soll. Es ist verändert, vertauscht oder stammt aus einer '
            + 'anderen Backup. Es wurde nichts geändert.');
        }
        return EdCrypto.openTeilJson(vorgang, roh,
          EdCrypto.aadTeil(manifest.kennung, t.name, index + 1, teile.length),
          `Das Teil ${t.name}`);
      };

      return {
        manifest, teile, teilOeffnen,
        eintragsteile: teile.map((t, i) => (t.art === 'eintraege' ? i : -1)).filter(i => i >= 0),
        spurteile: teile.map((t, i) => (t.art === 'spuren' ? i : -1)).filter(i => i >= 0),
        schliessen: () => leser.close(),
      };
    }

    document.getElementById('impbtn').addEventListener('click', async () => {
      const f = document.getElementById('bfile').files[0];
      if (!f) { melde(impState, 'Bitte eine Backup-Datei auswählen.', 'fehler'); return; }
      const pw = document.getElementById('ipw').value;
      if (!pw) { melde(impState, 'Bitte das Backup-Passwort eingeben.', 'fehler'); return; }

      const key = await ck();
      if (!key) { melde(impState, 'Entschlüsselung gesperrt — siehe Hinweis oben.', 'fehler'); return; }
      try {
        impState.textContent = 'Datei wird gelesen…';
        const bytes = new Uint8Array(await f.arrayBuffer());
        /* DREI ANTWORTEN STATT EINER (S2/AP5). `isBackupFile()` sagt seit
           Fassung 4 auch zu einem ZIP ja — das mehrteilige Backup IST
           eins. Damit ein versehentlich gewaehltes CSV-Archiv trotzdem eine
           brauchbare Auskunft bekommt, entscheidet hier `dateiArt()`:
           'zip' = mehrteilig, 'edbak' = einteilig, 'teil' = ein Stueck
           daraus, null = etwas anderes. */
        const art = EdCrypto.dateiArt(bytes);
        if (art === null) {
          melde(impState, 'Das ist keine Backup-Datei dieses Programms.', 'fehler');
          return;
        }
        if (art === 'teil') {
          melde(impState, 'Das ist ein einzelnes Teil eines mehrteiligen Backups, '
                        + 'nicht das Backup selbst. Bitte die vollständige '
                        + '.edbak-Datei auswählen.', 'fehler');
          return;
        }
        impState.textContent = 'Datei wird geöffnet…';
        /* ZWEI WEGE AB HIER. Die einteilige Datei geht auf wie immer; die
           mehrteilige wird zuerst als Archiv geöffnet, ihr Manifest gelesen
           und gegen die Teileliste gehalten — erst dann der Kern. */
        let fassung4 = null;
        let data;
        if (art === 'zip') {
          fassung4 = await fassung4Oeffnen(pw, bytes);
          impState.textContent = `Backup vom ${(fassung4.manifest.erzeugt_am || '')
            .slice(0, 10)} mit ${fassung4.eintragsteile.length} Eintrags- und `
            + `${fassung4.spurteile.length} Spurteilen — Kopf wird geöffnet…`;
          data = await fassung4.teilOeffnen(0);
        } else {
          data = await EdCrypto.openBackup(pw, bytes);
        }

        /* HERKUNFT DER DATEI NENNEN (M5-13) — sie steht im Kopf, bei beiden
           Fassungen an derselben Stelle. */
        const herkunftEl = document.getElementById('impherkunft');
        if (herkunftEl) {
          if (data.user && (data.user.email || data.user.name)) {
            const wer = data.user.name
              ? `${data.user.name} (${data.user.email || 'ohne Adresse'})`
              : data.user.email;
            const wann = data.created_at ? new Date(data.created_at) : null;
            const zeit = wann && !isNaN(wann)
              ? ` vom ${wann.toLocaleDateString('de-DE')}, ${wann.toLocaleTimeString('de-DE',
                  { hour: '2-digit', minute: '2-digit' })} Uhr` : '';
            const fremd = data.user.email && data.user.email !== KONTO_MAIL;
            herkunftEl.textContent = `Backup${zeit} aus dem Konto ${wer}.`
              + (fremd ? ` Das ist NICHT das angemeldete Konto (${KONTO_MAIL}) — die `
                       + 'geschützten Angaben werden dabei für dieses Konto neu '
                       + 'verschlüsselt.' : '');
            herkunftEl.hidden = false;
          } else {
            herkunftEl.hidden = true;
          }
        }

        const gleichesKonto = PAT_KEY_CHECK != null && data.pat_key_check != null
                              && PAT_KEY_CHECK === data.pat_key_check;
        let uebernommen = 0, uebernommenFremd = 0;

        /* Geschützte Angaben für DIESES Konto neu verschlüsseln. Bei Fassung 4
           geschieht das je Eintragsteil (unten); beim Altformat hier, weil
           dort alles in einer Nutzlast steht. */
        async function patUmschluesseln(liste) {
          for (const m of (liste || [])) {
            if (m.pat && Object.keys(m.pat).length) {
              m.pat_blob = await EdCrypto.encrypt(key, JSON.stringify(m.pat));
            } else if (m.pat_blob) {
              if (gleichesKonto) { uebernommen++; } else { uebernommenFremd++; }
            }
            delete m.pat;
            delete m.pat_unreadable;
          }
        }

        /* DIE RÜCKFRAGE STEHT VOR DEM ERSTEN SCHREIBEN. Bei Fassung 4 sind die
           Einsätze noch nicht geöffnet; gefragt wird deshalb anhand der
           Prüfsumme im Kopf, die genau dafür da ist. Beim Altformat bleibt es
           bei der gezählten Zahl. */
        if (!fassung4) { await patUmschluesseln(data.missions); }
        /* NUR FRAGEN, WENN ES ETWAS ZU FRAGEN GIBT (S2/AP5b, F-S2-D).
         *
         * Betroffen sind allein Einsätze, deren geschützte Angaben beim
         * SICHERN nicht zu entschlüsseln waren und deshalb als Chiffretext in
         * der Datei liegen — nur die kommen hier unlesbar an. Alle anderen
         * tragen Klartext und werden gleich für dieses Konto neu
         * verschlüsselt; bei ihnen geht nichts verloren.
         *
         * Beim Altformat wird gezählt, die Einsätze liegen ja vor. Bei
         * Fassung 4 sind sie zum Zeitpunkt der Frage noch versiegelt — die
         * Frage steht aber vor dem ersten Schreiben und muss dort bleiben.
         * Die Zahl kommt deshalb aus dem Manifest.
         *
         * WAS DIESE ZEILEN GEKOSTET HABEN: Bis hierher stand hier nur „aus
         * einem anderen Konto". Das ist bei Fassung 4 der REGELFALL des
         * Einspielens, und die Rückfrage kam damit bei jedem fremden
         * Backup — auch bei einem, in dem jeder Einsatz seine Angaben im
         * Klartext mitbringt. Der Kreislauftest lief 300 Sekunden ins Leere,
         * weil sein Browser die Frage verneinte; ein Mensch hätte eine
         * Warnung vor einem Verlust gelesen, der nicht stattfindet.
         *
         * Fehlt die Zahl (Fassung-4-Datei aus einem Stand vor AP5b), wird
         * gefragt: „nicht erhoben" ist etwas anderes als „keine".
         */
        const unlesbarLaut = fassung4 ? fassung4.manifest.unlesbar : undefined;
        const fremdeAngaben = fassung4
          ? (!gleichesKonto && (unlesbarLaut === undefined || unlesbarLaut > 0))
          : uebernommenFremd > 0;
        if (fremdeAngaben) {
          // Die Prüfsumme sagt, OB die Angaben hier lesbar wären; der
          // user-Block sagt, WOHER sie kommen. Beides gehört in dieselbe
          // Rückfrage, sonst muss man es sich zusammensuchen (M5-13).
          const woher = (data.user && data.user.email)
            ? ` Sie stammt aus dem Konto ${data.user.email}.` : '';
          const w = data.pat_key_check == null
            ? 'Die Datei nennt keine Schlüssel-Prüfsumme (vor Web 4.1.1 erstellt), '
              + 'die Zuordnung ist daher unbekannt.'
            : 'Die Datei stammt aus einem anderen Konto.';
          /* `window.edConfirm` statt `confirm` (S2/AP5b). Der native Dialog
             lässt sich im Browser dauerhaft abschalten („keine weiteren
             Dialoge dieser Seite anzeigen") — genau das war der Grund, aus
             dem es confirm.js überhaupt gibt. Diese beiden Stellen im
             Backup-Bereich waren die letzten, die daran vorbeigingen. */
          if (!await window.edConfirm(
              `Einsätze dieses Backups können geschützte Angaben enthalten, `
              + `die beim Erstellen nicht entschlüsselt werden konnten. `
              + `${w}${woher} Solche Angaben werden übernommen, sind hier aber `
              + `voraussichtlich NICHT lesbar. Trotzdem fortfahren?`,
              'Trotzdem einspielen', 'normal', 'Geschützte Angaben')) {
            /* ALS MELDUNG, NICHT ALS ZWISCHENSTAND (S2/AP5b). Ein Abbruch
               ist ein Ergebnis. Solange er wie ein Fortschrittstext aussah,
               konnte kein Prüfmittel ihn vom Weiterlaufen unterscheiden. */
            melde(impState, 'Abgebrochen — es wurde nichts übernommen.', 'warn');
            return;
          }
        }

        impState.textContent = fassung4 ? 'Kopf wird übertragen…' : 'Daten werden übertragen…';
        const res = await fetch('api/backup_restore.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify(data)
        });
        const out = await res.json();
        if (!out.ok) { throw new Error(out.meldung || out.hinweis || out.error || 'unbekannt'); }
        const s = out.stats;

        /* ---- Die Einträge in Fenstern (S2/AP5b) -------------------------
         *
         * Der Kopf hat die Diensttage angelegt und sagt, unter welcher
         * Kennung. Die Zuordnung geht mit jedem Fenster zurück an den Server;
         * der prüft sie gegen das Konto, statt sie zu glauben.
         *
         * DIE ZAHLEN WERDEN AUFADDIERT. `restoreBericht()` bekommt am Ende
         * eine Summe über alle Fenster — sonst meldete die Anwendung die
         * Zahlen des letzten Fensters als Ergebnis des Ganzen. */
        const spurKarte = Object.assign({}, out.spur_karte || {});
        if (fassung4 && fassung4.eintragsteile.length) {
          const dayMap = out.day_map || {};
          for (const [i, ti] of fassung4.eintragsteile.entries()) {
            impState.textContent = `Einträge werden übertragen `
              + `(Teil ${i + 1} von ${fassung4.eintragsteile.length})…`;
            const teil = await fassung4.teilOeffnen(ti);
            await patUmschluesseln(teil.missions);
            const a = await fetch('api/backup_eintraege_restore.php', {
              method: 'POST',
              headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
              body: JSON.stringify({ eintraege: teil, day_map: dayMap }),
            });
            const o = await a.json();
            if (!o.ok) {
              throw new Error('Die Einträge konnten nicht übertragen werden ('
                + (o.meldung || o.hinweis || o.error || 'HTTP ' + a.status) + '). '
                + 'Was bis hierher übertragen wurde, ist eingespielt.');
            }
            /* Summieren, nicht überschreiben. */
            for (const k of ['missions', 'missions_skipped', 'rests', 'rests_skipped',
                             'stammdaten', 'stammdaten_skipped', 'days']) {
              s[k] = (s[k] || 0) + (o.stats[k] || 0);
            }
            for (const k of Object.keys(o.stats.papierkorb || {})) {
              s.papierkorb[k] = (s.papierkorb[k] || 0) + o.stats.papierkorb[k];
            }
            /* Die Sperrvermerke (Nutzlast 9). EIGENE SCHLEIFE, weil sie wie
               `papierkorb` ein verschachtelter Block sind — die feste Liste
               oben summiert nur flache Zahlen und ließe diese fallen. Genau
               das ist der Fehler, gegen den der Kommentar über der Schleife
               geschrieben ist: Sonst meldete die Anwendung die Zahlen des
               letzten Fensters als Ergebnis des Ganzen. */
            for (const k of Object.keys(o.stats.schnitte || {})) {
              s.schnitte = s.schnitte || {};
              s.schnitte[k] = (s.schnitte[k] || 0) + o.stats.schnitte[k];
            }
            for (const [g, z] of Object.entries(o.stats.skipped_reasons || {})) {
              s.skipped_reasons = s.skipped_reasons || {};
              s.skipped_reasons[g] = (s.skipped_reasons[g] || 0) + z;
            }
            Object.assign(spurKarte, o.spur_karte || {});
          }
        }

        /* ---- Die Spuren hinterher (Konzept 3.2.4) ------------------------
         *
         * WAS SCHIEFGEHEN KANN UND GEMELDET WIRD: eine `spur_ref`, zu der es
         * keinen Datensatz gibt, und eine Spur, die der Server ablehnt.
         * Beides ist kein Abbruch, aber beides gehört in die Rückmeldung:
         * Eine Wiederherstellung, die eine Spur still verliert, ist genau
         * das, wovor ein Backup schützen soll. */
        let spurenGeschrieben = 0, spurenUebersprungen = 0;
        const spurenAbgelehnt = [];
        let ohneZiel = 0;
        if (fassung4 && fassung4.spurteile.length) {
          /* ZWEI GRENZEN, NICHT EINE. Die Größe deckelt der POST (Z3: 2 MB);
             die ANZAHL deckelt der Endpunkt (BACKUP_SPUREN_RESTORE_MAX in
             api/backup_spuren_restore.php), weil je Spur Arbeit anfällt.
             Der erste Entwurf kannte nur die Größe — und scheiterte bei der
             Abnahme am 5000er-Bestand: Kurze Ruhespuren sind so klein, dass
             in einem Häppchen weit mehr als 500 passen. Die Größe liegt bei
             800 kB, also unter nginx' Vorgabe von 1 MB.

             DIE ZAHL STEHT AN ZWEI ORTEN, und das ist bekannt: hier und im
             Endpunkt. `tools/wiederherstellungs-probe/` hält sie zusammen. */
          const HAPPEN = 800 * 1024;      // unter nginx' Vorgabe von 1 MB
          const HAPPEN_ZAHL = 500;
          for (const [i, teilIndex] of fassung4.spurteile.entries()) {
            impState.textContent = `GPS-Daten werden übertragen `
              + `(Teil ${i + 1} von ${fassung4.spurteile.length})…`;
            const teil = await fassung4.teilOeffnen(teilIndex);
            let happen = [], groesse = 0;
            const senden = async () => {
              if (!happen.length) { return; }
              const a = await fetch('api/backup_spuren_restore.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
                body: JSON.stringify({ spuren: happen }),
              });
              const o = await a.json();
              if (!o.ok) {
                throw new Error('Die GPS-Daten konnten nicht übertragen werden ('
                  + (o.meldung || o.hinweis || o.error || 'HTTP ' + a.status) + '). '
                  + 'Der übrige Bestand ist bereits eingespielt.');
              }
              spurenGeschrieben += o.geschrieben || 0;
              spurenUebersprungen += o.uebersprungen || 0;
              for (const x of (o.abgelehnt || [])) {
                spurenAbgelehnt.push(`${x.owner_type} ${x.owner_id}: ${x.grund}`);
              }
              happen = []; groesse = 0;
            };
            for (const e of (teil.spuren || [])) {
              const ziel = spurKarte[String(e.spur_ref)];
              if (!ziel) { ohneZiel++; continue; }
              const blob = String(e.blob || '');
              if (groesse + blob.length > HAPPEN || happen.length >= HAPPEN_ZAHL) {
                await senden();
              }
              happen.push({ owner_type: ziel.art, owner_id: ziel.id,
                            blob: blob, n: e.n });
              groesse += blob.length;
            }
            await senden();
          }
        }
        if (fassung4) { await fassung4.schliessen(); }

        const spurText = fassung4
          ? ` ${spurenGeschrieben} Aufzeichnungen übernommen`
            + (spurenUebersprungen ? `, ${spurenUebersprungen} waren schon da` : '')
            + (ohneZiel ? `, ${ohneZiel} ohne zugehörigen Einsatz (übersprungen)` : '')
            + (spurenAbgelehnt.length
                ? `. ACHTUNG: ${spurenAbgelehnt.length} Aufzeichnungen abgelehnt: `
                  + spurenAbgelehnt.slice(0, 3).join(' · ')
                  + (spurenAbgelehnt.length > 3 ? ' · …' : '')
                : '.')
          : '';
        const zusatz = uebernommen
          ? ` ${uebernommen} Einsätze brachten ihre geschützten Angaben verschlüsselt `
            + `mit und sind wieder lesbar.`
          : (uebernommenFremd
              ? ` ${uebernommenFremd} Einsätze brachten verschlüsselte Angaben mit, die `
                + `in diesem Konto nicht lesbar sind.`
              : '');
        melde(impState, 'Import fertig: ' + restoreBericht(s, zusatz) + spurText,
              spurenAbgelehnt.length || ohneZiel ? 'warn' : 'ok');
      } catch (e) {
        melde(impState, 'Import fehlgeschlagen: ' + e.message, 'fehler');
      }
    });

    /* ---- Freigegebenes Backup einspielen (A8.6) ----------------------
     *
     * Ablauf, vollständig im Browser:
     *   1. Wiederherstellungsschlüssel -> Schlüssel-Hex (EdCrypto.recoveryKeyHex)
     *   2. damit `pat_wrap_rc` aus dem Paket öffnen -> ALTER Inhaltsschlüssel
     *   3. je Einsatz `pat_blob` mit dem alten öffnen und mit dem EIGENEN
     *      neu verschlüsseln
     *   4. das so umgeschlüsselte Paket über den vorhandenen Weg
     *      api/backup_restore.php zurückspielen
     *
     * Schritt 4 benutzt bewusst denselben Endpunkt wie der Datei-Import: Das
     * Feld `daten` IST ein Backup der Formatversion 5. Ein zweiter Rückspielpfad
     * wäre eine zweite Stelle, an der dieselben Fehler zu machen sind.
     */
    const fgBox   = document.getElementById('freigabebox');
    const fgState = document.getElementById('freigabestate');
    let fgPaket = null;

    async function freigabeLaden() {
      try {
        const res = await fetch('api/adminbackup_freigabe.php');
        const d = await res.json();
        if (!res.ok || !d.freigabe) { return; }
        fgPaket = d;
        const u = d.freigabe.umfang || {};
        const woher = d.freigabe.herkunft_email
          ? ` Sie stammt aus dem Konto ${d.freigabe.herkunft_email}.` : '';
        /* „davon im Papierkorb" (E-S1-02). Die drei Zahlen davor zählen den
           Papierkorb MIT — seit Nutzlast 7 steht er in jedem Backup. Fehlt
           der Block (Backup von vor S1), bleibt der Zusatz weg: „nicht
           erhoben" ist etwas anderes als „nichts drin". */
        const pk = u.papierkorb;
        const pkText = pk
          ? ` Davon im Papierkorb: ${pk.einsaetze || 0} Einsätze, `
            + `${pk.diensttage || 0} Diensttage, ${pk.ruhezeiten || 0} Ruhezeiten — `
            + `sie kommen als Papierkorbeinträge zurück, und die 90-Tage-Frist `
            + `beginnt dabei neu.`
          : '';
        document.getElementById('freigabeinfo').textContent =
          `Die Verwaltung hat ein Konto-Backup vom `
          + `${(d.freigabe.erzeugt || '').replace('T', ' ').replace('Z', ' UTC')} `
          + `für dich freigegeben: ${u.einsaetze || 0} Einsätze, `
          + `${u.diensttage || u.flugtage || 0} Diensttage, ${u.ruhezeiten || 0} Ruhezeiten.`
          + pkText + woher;
        // Ohne geschützte Angaben gibt es nichts umzuschlüsseln — dann nach dem
        // Wiederherstellungsschlüssel zu fragen wäre eine Hürde ohne Zweck.
        document.getElementById('freigabecodelabel').hidden = !d.freigabe.braucht_schluessel;
        fgBox.hidden = false;
      } catch (e) {
        /* STILL, ABER NICHT STUMM (F-S2-F).
         *
         * Der Gedanke war richtig: Wer keine Freigabe hat, soll auf dieser
         * Seite keinen Fehler über eine Funktion lesen, die ihn nichts angeht.
         * Nur hat dieser Block danach JEDEN Fehler geschluckt — auch den
         * TypeError einer Kennung, die es im Markup nicht gab, und mit ihm die
         * Zeile, die den Kasten sichtbar macht. Die Freigabe war für niemanden
         * zu sehen, und nichts hat es gesagt.
         *
         * Die Ausgabe bleibt still; die Konsole bekommt es. Damit fällt es
         * dem Bilderlauf und jeder Browserprüfung auf, ohne dass eine
         * NutzerIn ohne Freigabe je etwas merkt. */
        console.error('Freigabe konnte nicht geladen werden:', e);
      }
    }
    freigabeLaden();

    document.getElementById('freigabebtn').addEventListener('click', async () => {
      if (!fgPaket) { return; }
      const daten = fgPaket.daten;
      const braucht = fgPaket.freigabe.braucht_schluessel;
      /* FASSUNG 1 KOMMT AM STÜCK, FASSUNG 2 IN TEILEN (S2/AP6). Ein
         Adminpaket ist seit Web 12.0.0 ein mehrteiliges ZIP; beim 5000er-Konto
         wären es sonst 94 MB in einer Antwort und derselbe Rumpf als POST. */
      const fassung = Number(fgPaket.fassung || 1);
      try {
        let altCk = null, eigenerCk = null;
        if (braucht) {
          const code = document.getElementById('freigabecode').value;
          const pruef = EdCrypto.pruefeRecoveryCode(code);
          if (!pruef.ok) {
            melde(fgState, EdCrypto.recoveryCodeMeldung(pruef), 'fehler');
            return;
          }
          if (!fgPaket.pat_wrap_rc) {
            fgState.textContent = 'Dem Backup fehlt die Wiederherstellungs-Hülle — '
              + 'die geschützten Angaben lassen sich nicht mehr öffnen.';
            return;
          }
          fgState.textContent = 'Schlüssel wird geprüft…';
          const rcKey = await EdCrypto.recoveryKeyHex(code);
          try { altCk = await EdCrypto.decrypt(rcKey, fgPaket.pat_wrap_rc); }
          catch (e) { altCk = null; }
          if (!altCk) {
            fgState.textContent = 'Der Wiederherstellungsschlüssel passt nicht zu diesem '
              + 'Backup. Es wurde nichts eingespielt.';
            return;
          }
          eigenerCk = await ck();
          if (!eigenerCk) {
            fgState.textContent = 'Die Verschlüsselung ist in dieser Sitzung gesperrt — '
              + 'bitte oben entsperren.';
            return;
          }
        }

        let um = 0, unlesbar = 0;
        /* Die Umschlüsselung an EINER Stelle, für beide Fassungen. */
        const umschluesseln = async (liste) => {
          if (!braucht) { return; }
          for (const m of (liste || [])) {
            if (!m.pat_blob) { continue; }
            try {
              const klar = await EdCrypto.decrypt(altCk, m.pat_blob);
              m.pat_blob = await EdCrypto.encrypt(eigenerCk, klar);
              um++;
            } catch (e) {
              /* NICHT stillschweigend weglassen: Ein Eintrag, dessen
                 geschützte Angaben hier nicht lesbar werden, ist eine Auskunft
                 — die Datei sähe sonst vollständig aus, wäre es aber nicht. */
              unlesbar++;
            }
          }
        };

        const holeTeil = async (name) => {
          const a = await fetch('api/adminbackup_freigabe.php?teil='
                              + encodeURIComponent(name));
          if (!a.ok) {
            let grund = 'HTTP ' + a.status;
            try { const j = await a.json(); grund = j.meldung || j.error || grund; } catch (e2) {}
            throw new Error('Der Teil ' + name + ' liess sich nicht laden (' + grund + ').');
          }
          return a.json();
        };
        const senden = async (adresse, rumpf) => {
          const a = await fetch(adresse, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
            body: JSON.stringify(rumpf),
          });
          const o = await a.json();
          if (!o.ok) { throw new Error(o.meldung || o.hinweis || o.error || 'unbekannt'); }
          return o;
        };

        let s = null;

        if (fassung >= 2) {
          /* WARUM HIER KEINE RÜCKFRAGE VOR DEM SCHREIBEN STEHT.
           *
           * Bei Fassung 1 liegt alles im Speicher: Es lässt sich zählen, wie
           * viele Einsätze sich mit diesem Schlüssel NICHT öffnen lassen, und
           * dann fragen — vor dem ersten Schreiben. Bei Fassung 2 liegen die
           * Einträge in Fenstern, die einzeln geholt werden; die Zahl stünde
           * erst fest, wenn alle geöffnet sind, also nach dem ersten
           * Schreiben. Ein zweiter Durchgang nur zum Zählen hiesse, jedes
           * Fenster zweimal zu holen und jede Angabe zweimal zu entschlüsseln.
           *
           * Die eigentliche Schranke steht ohnehin davor und ist schärfer: Der
           * Wiederherstellungsschlüssel muss die Hülle `pat_wrap_rc` öffnen.
           * Tut er das, ist es der richtige Inhaltsschlüssel; einzelne
           * Fehlschläge danach sind beschädigte Einträge, nicht der falsche
           * Schlüssel. Sie werden am Ende GENANNT, mit Zahl und in Orange. */
          fgState.textContent = 'Kopf wird übertragen…';
          const kopf = await holeTeil('kopf.json');
          const out0 = await senden('api/backup_restore.php', kopf);
          s = out0.stats;
          const dayMap = out0.day_map || {};
          let karte = Object.assign({}, out0.spur_karte || {});

          const nT = Number(fgPaket.eintragsteile || 0);
          for (let i = 1; i <= nT; i++) {
            fgState.textContent = `Einträge werden übertragen (Teil ${i} von ${nT})…`;
            const name = 'eintraege/' + String(i).padStart(4, '0') + '.json';
            const teil = await holeTeil(name);
            await umschluesseln(teil.missions);
            const o = await senden('api/backup_eintraege_restore.php',
                                   { eintraege: teil, day_map: dayMap });
            Object.assign(karte, o.spur_karte || {});
            for (const [k, v] of Object.entries(o.stats || {})) {
              if (typeof v === 'number') { s[k] = (s[k] || 0) + v; }
            }
          }

          let spurenGeschrieben = 0, ohneZiel = 0;
          const nS = Number(fgPaket.spurteile || 0);
          for (let i = 1; i <= nS; i++) {
            fgState.textContent = `GPS-Daten werden übertragen (Teil ${i} von ${nS})…`;
            const name = 'spuren/' + String(i).padStart(4, '0') + '.json';
            const teil = await holeTeil(name);
            let happen = [], groesse = 0;
            const schicken = async () => {
              if (!happen.length) { return; }
              const o = await senden('api/backup_spuren_restore.php', { spuren: happen });
              spurenGeschrieben += o.geschrieben || 0;
              happen = []; groesse = 0;
            };
            for (const e of (teil.spuren || [])) {
              const ziel = karte[String(e.spur_ref)];
              if (!ziel) { ohneZiel++; continue; }
              const blob = String(e.blob || '');
              if (groesse + blob.length > 800 * 1024 || happen.length >= 500) {
                await schicken();
              }
              happen.push({ owner_type: ziel.art, owner_id: ziel.id, blob: blob, n: e.n });
              groesse += blob.length;
            }
            await schicken();
          }
          s.spuren_uebernommen = spurenGeschrieben;
          if (ohneZiel) { s.spuren_ohne_ziel = ohneZiel; }
        } else {
          await umschluesseln(daten.missions);
          if (unlesbar && !await window.edConfirm(
              `${unlesbar} Einsätze lassen sich mit diesem Schlüssel `
              + `nicht öffnen. Ihre geschützten Angaben bleiben hier unlesbar. `
              + `Trotzdem einspielen?`,
              'Trotzdem einspielen', 'normal', 'Geschützte Angaben')) {
            melde(fgState, 'Abgebrochen — es wurde nichts eingespielt.', 'warn');
            return;
          }
          fgState.textContent = braucht
            ? `${um} Einsätze umgeschlüsselt. Daten werden übertragen…`
            : 'Daten werden übertragen…';
          const out = await senden('api/backup_restore.php', daten);
          s = out.stats;
        }

        await fetch('api/adminbackup_freigabe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify({ eingeloest: true })
        });
        const zusatz = (s.spuren_uebernommen !== undefined
                          ? ` ${s.spuren_uebernommen} Aufzeichnungen übernommen.` : '')
                     + (unlesbar
                          ? ` ACHTUNG: ${unlesbar} Einsätze liessen sich mit diesem `
                          + `Schlüssel nicht öffnen; ihre geschützten Angaben bleiben `
                          + `hier unlesbar.` : '');
        melde(fgState, 'Fertig: ' + restoreBericht(s, zusatz),
              unlesbar || s.spuren_ohne_ziel ? 'warn' : 'ok');
        document.getElementById('freigabebtn').disabled = true;
      } catch (e) {
        melde(fgState, 'Einspielen fehlgeschlagen: ' + e.message, 'fehler');
      }
    });
    </script>

  <?php else: ?>
    <?php ui_titelzeile(['titel' => 'Geräte']); ?>
    <?php /* EIN SATZ MIT BELEGUNG UND GRENZE (Mockup 10). Vorher standen hier
             drei Sätze, und die Belegung kam zuletzt — die Zahl, wegen der man
             die Erklärung überhaupt liest. */ ?>
    <p class="seiten-erklaerung">Uhr oder Handy zeichnet den Dienst auf und
       liefert ihn hierher. <strong><?= count($devices) ?> von <?= MAX_GERAETE ?></strong>
       Plätzen belegt — deaktivierte zählen mit, erst Entkoppeln gibt einen Platz
       frei.</p>

    <?php if ($devNeu > 0): ?>
      <?php /* Zweite Spur neben der E-Mail beim Koppeln: Wer die Post nicht
               liest, sieht ein neu hinzugekommenes Gerät wenigstens hier. */ ?>
      <?php ui_meldung(
          ($devNeu === 1 ? 'Ein Gerät ist' : $devNeu . ' Geräte sind')
          . ' in den letzten ' . GERAETE_NEU_TAGE . ' Tagen hinzugekommen — unten mit '
          . '„neu" gekennzeichnet. Kommt dir davon etwas unbekannt vor, entkopple es '
          . 'hier; danach kann es nichts mehr hochladen.', null, 'warn', '      '); ?>
    <?php endif; ?>

    <?php /* ---- Die Karte „Gerät koppeln" in drei Zuständen (S5, E-S5-26) ----
             „Gerät koppeln", nicht „Uhr koppeln" (S6): Seit S4 koppelt die
             Handy-App über denselben Weg — und die Liste darunter sagt seit
             Web 12.9.0 „Handy". Eine Überschrift, die „Uhr" sagt,
             widerspricht der Zeile, die sie ankündigt.

             DREI ZUSTÄNDE, EINE KARTE, KEIN NEUER BAUSTEIN: erst die Eingabe,
             dann die Rückfrage, dann das Warten. Alles aus dem Vorrat
             (Design.md 9): die Karte
             selbst, `ui_feld`, `ui_zeile`, `ui_meldung_markup` und Knöpfe im
             `.listen-form-fuss`. Eine eigene Darstellung bräuchte Freigabe
             mit Mockup (Design.md 1.2) — sie ist nicht nötig. */ ?>
    <?php if ($koppelWarten !== null): ?>
      <?php /* ---- Zustand 3: beansprucht, das Gerät ist am Zug -------------
               Diese Karte wartet, und sie wartet SICHTBAR: Sie sagt, worauf,
               auf welches Gerät und wie lange noch. Das Nachladen besorgt
               `assets/kopplung.js` (E-S5-53); ohne JavaScript bleibt der Weg
               vollständig — dann steht hier dieselbe Auskunft, und die Person
               lädt die Seite selbst neu, sobald sie am Gerät bestätigt hat. */ ?>
      <?php $kwRest = (int)$koppelWarten['rest_s']; ?>
      <?php ui_karte_start(['titel' => 'Am Gerät bestätigen', 'id' => 'koppeln']); ?>
        <?php ui_zeile([
            'text'  => geraet_bezeichnung($koppelWarten['geraet_art'],
                                          $koppelWarten['geraet_modell'],
                                          $koppelWarten['geraet_teil']),
            'klein' => 'Kennung ' . geraet_kennung_kurz((string)$koppelWarten['device_id'])]); ?>
        <p class="feld-hinweis">Das Gerät fragt jetzt, ob es sich mit deinem Konto
           verbinden soll — <strong>bestätige dort mit Ja</strong>. Es zeigt dabei
           deine E-Mail-Adresse, teilweise verdeckt. Danach erscheint es unten in
           der Liste.</p>
        <p class="feld-klein" id="kopplung-warten"
           data-rest="<?= $kwRest ?>"
           data-quelle="api/kopplung_stand.php"
           data-ziel="einstellungen.php?t=geraete">Noch
           <span id="kopplung-restzeit"><?= e(pair_restzeit_text($kwRest)) ?></span>
           gültig. Sagst du am Gerät Nein oder läuft die Zeit ab, geschieht
           nichts — dann holst du dir dort einen neuen Code.</p>
        <form method="post" action="einstellungen.php?t=geraete">
          <?= csrf_field() ?><input type="hidden" name="action" value="koppeln_abbrechen">
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>
      <script src="<?= asset('assets/kopplung.js') ?>" defer></script>

    <?php elseif ($koppelSitzung !== null): ?>
      <?php /* ---- Zustand 2: das erste der beiden Tore (E-S5-05a) ----------
               Hier steht, WAS koppeln will — Art, Modell, gekürzte Kennung,
               Restzeit. Das ist der ganze Zweck dieses Zwischenschritts: Wer
               einen fremden Code eingetippt bekommt („gib mal AB3 K7Q ein"),
               sieht spätestens jetzt ein Gerät, das nicht seines ist. */ ?>
      <?php $ksRest = (int)$koppelSitzung['rest_s']; ?>
      <?php ui_karte_start(['titel' => 'Dieses Gerät koppeln?', 'id' => 'koppeln']); ?>
        <?php ui_zeile([
            'text'  => geraet_bezeichnung($koppelSitzung['geraet_art'],
                                          $koppelSitzung['geraet_modell'],
                                          $koppelSitzung['geraet_teil']),
            'klein' => 'Code ' . pair_code_anzeigen((string)$koppelSitzung['code'])
                     . ' · gültig bis '
                     . fmt_local(gmdate('Y-m-d H:i:s', time() + $ksRest), 'H:i') . ' Uhr'
                     . ' · Kennung ' . geraet_kennung_kurz((string)$koppelSitzung['device_id'])]); ?>
        <p class="feld-hinweis">Wenn das dein Gerät ist und der Code stimmt, verbinde
           es. Das Gerät fragt dich danach noch einmal — erst dein Ja dort schließt
           die Kopplung ab. Kommt dir das Gerät unbekannt vor: abbrechen. Dann
           geschieht nichts.</p>
        <?php if ($koppelSitzung['geraet_art'] === null && $koppelSitzung['geraet_teil'] === null): ?>
          <?php /* Ohne Selbstauskunft steht hier „Gerät unbekannt" — und dann
                   trägt die Rückfrage nichts mehr, woran sich ein fremdes
                   Gerät erkennen ließe. Das gehört gesagt, nicht verschwiegen. */ ?>
          <?= ui_meldung_markup('warn', 'Das Gerät hat keine Angaben über sich gemacht. '
              . 'Das ist bei älteren Uhr-Apps so; sei sicher, dass es deines ist.') ?>
        <?php endif; ?>
        <form method="post" action="einstellungen.php?t=geraete">
          <?= csrf_field() ?><input type="hidden" name="action" value="koppeln_bestaetigen">
          <input type="hidden" name="code" value="<?= e((string)$koppelSitzung['code']) ?>">
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Mit meinem Konto verbinden', 'art' => 'primaer']) ?>
            <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                          'href' => 'einstellungen.php?t=geraete#koppeln']) ?>
          </div>
        </form>
      <?php ui_karte_ende(); ?>

    <?php else: ?>
      <?php ui_karte_start(['titel' => 'Gerät koppeln', 'id' => 'koppeln']); ?>
        <?php if (geraete_grenze_erreicht(db(), $userId)): ?>
          <?php /* Kein Feld, wo die Eingabe ohnehin abgewiesen würde: Erst ein
                   Platz, dann ein Code. Denselben Wortlaut nennt der
                   Fehlerzweig der Aktion. */ ?>
          <p class="feld-hinweis">Es sind bereits <?= MAX_GERAETE ?> Geräte mit diesem
             Konto verbunden. Bitte zuerst ein nicht mehr genutztes Gerät löschen —
             dann lässt sich wieder ein Gerät koppeln.</p>
        <?php else: ?>
          <p class="feld-hinweis">Starte die Kopplung auf dem Gerät:
             <strong>Sync-Seite → Gerät koppeln</strong>. Das Gerät zeigt einen Code
             aus <?= PAIR_LEN ?> Zeichen. Gib ihn hier ein; danach fragt das Gerät, ob
             es sich mit deinem Konto verbinden soll — bestätige dort mit Ja. Der Code
             ist <?= PAIR_TTL_MIN ?> Minuten gültig.</p>
          <?php /* Der Tastenweg steht als Zusatz mit genannter Plattform: Er gilt
                   nur für Fenix und Forerunner. Auf der Venu 3s gibt es weder START
                   noch DOWN — der frühere Satz war für sie schon falsch, als sie
                   dazukam. Die Tabelle je Uhr steht im Handbuch, Abschnitt 2.0. */ ?>
          <p class="feld-klein">Auf Garmin-Uhren: die Sync-Seite erreichst du vom
             Startbildschirm mit DOWN, das Koppeln startet mit gedrückt gehaltener
             START-Taste. Die Tastenwege der einzelnen Uhren stehen im Handbuch,
             Abschnitt 2.0.</p>
          <form method="post" action="einstellungen.php?t=geraete">
            <?= csrf_field() ?><input type="hidden" name="action" value="koppeln_pruefen">
            <?php /* `feld-fest` setzt die Schreibmaschinenschrift (Design.md 2.2):
                     Der Code wird abgelesen und abgetippt, und in Festbreite steht
                     jedes Zeichen für sich. Die Eingabe nimmt ihn mit und ohne
                     Leerzeichen und in jeder Schreibung — pair_code_normalisieren()
                     räumt das auf, bevor gesucht wird; deshalb maxlength 8 und nicht
                     6. Der Platzhalter ist ein Phantasiecode (E-S3-13). */ ?>
            <?php ui_feld([
                'name'        => 'code',
                'label'       => 'Code vom Gerät',
                'klasse'      => 'feld-fest',
                'platzhalter' => 'AB3 K7Q',
                'attr'        => ' maxlength="8" autocomplete="off" '
                               . 'autocapitalize="characters" spellcheck="false"']); ?>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Weiter', 'art' => 'primaer']) ?>
            </div>
          </form>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php ui_karte_start(['titel' => 'Geräte', 'zahl' => count($devices), 'id' => 'geraeteliste']); ?>
      <?php if (!$devices): ?>
        <p class="feld-hinweis">Noch keine Geräte angelegt.</p>
      <?php endif; ?>
      <?php foreach ($devices as $d):
            $did = (int)$d['id'];
            $aktiv = (int)$d['active'] === 1;
            /* WAS FUER EIN GERAET DAS IST, steht vorn (S6/R42): Wer drei
               Geraete gekoppelt hat, unterscheidet sie sonst nur an einer
               selbst vergebenen Bezeichnung — und die fehlt beim frisch
               gekoppelten Geraet gerade. Vorhandener Baustein, keine neue
               Darstellung: dieselbe Kleinzeile, die schon Zustand und letzten
               Kontakt traegt. */
            /* DIE KLEINZEILE SAGT, WAS DAS GERAET IST UND SEIT WANN
               (Mockup 10): Modell und Art, gekoppelt seit, zuletzt gemeldet.
               „aktiv" steht nicht mehr darin — es war der Normalfall und
               damit die haeufigste Auskunft ohne Aussage; „deaktiviert" sagt
               jetzt die Plakette. */
            $klein = geraet_bezeichnung($d['geraet_art'], $d['geraet_modell'], $d['geraet_teil'])
                   . ' · gekoppelt ' . fmt_local($d['created_at'], 'd.m.Y')
                   . ' · zuletzt gemeldet '
                   . ($d['last_seen'] ? fmt_local($d['last_seen'], 'd.m.Y H:i') : 'nie');
            ?>
        <form method="post" id="f-dev-<?= $did ?>" class="nur-vorlesen"
              action="einstellungen.php?t=geraete">
          <?= csrf_field() ?><input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $did ?>">
        </form>
        <form method="post" id="f-devdel-<?= $did ?>" class="nur-vorlesen"
              action="einstellungen.php?t=geraete"
              data-confirm="Gerät „<?= e($d['label'] ?? $d['device_id']) ?>“ wirklich entkoppeln? Es kann danach nichts mehr hochladen; bereits hochgeladene Daten bleiben erhalten."
              data-confirm-ok="Entkoppeln">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $did ?>">
        </form>
        <?php ui_zeile([
            'text'  => (string)($d['label'] ?? '') !== '' ? (string)$d['label'] : (string)$d['device_id'],
            'klein' => $klein,
            /* ZWEI PLAKETTEN, BEIDE NUR IM AUSNAHMEFALL (Mockup 10): „neu"
               orange (sieben Tage lang) und „deaktiviert" neutral. Das Datum
               steht nicht mehr in der Plakette — es steht seit AP6 in der
               Kleinzeile („gekoppelt 03.09.2026") und war zweimal dasselbe.

               DIE GEKUERZTE KENNUNG IST FORT. Sie stand hier seit S6, um zwei
               gleich benannte Geraete auseinanderzuhalten; das leistet die
               Kleinzeile mit Modell, Art und Kopplungsdatum besser, und die
               Zeile hat vier Angaben statt fuenf. */
            'plaketten' => ((int)$d['ist_neu'] ? ui_plakette('neu', ['ton' => 'orange']) : '')
                . ($aktiv ? '' : ui_plakette('deaktiviert', ['ton' => 'neutral'])),
            'aktionen' => ui_zeilenaktionen([
                'titel' => (string)($d['label'] ?? $d['device_id']),
                /* ALLE HANDLUNGEN IM PUNKTE-MENUE, auch am Schreibtisch
                   (Mockup 10). Als Knopfreihe stand „Entkoppeln" in Rot
                   unmittelbar neben „Deaktivieren", und zwar in jeder Zeile. */
                'blatt_immer' => true,
                'eintraege' => [
                    ['text' => 'Bezeichnung ändern', 'symbol' => 'stift',
                     'href' => 'einstellungen.php?t=geraete&ed=' . $did],
                    ['text' => $aktiv ? 'Deaktivieren' : 'Aktivieren',
                     'symbol' => $aktiv ? 'schloss' : 'schloss-offen',
                     'art' => 'leise', 'form' => 'f-dev-' . $did],
                    /* ENTKOPPELN STATT LOESCHEN (B-S8-21). Dasselbe Wort wie
                       auf der Kontoseite und dasselbe, was tatsaechlich
                       geschieht: Der Schluessel wird ungueltig, die
                       hochgeladenen Daten bleiben. „Loeschen" las sich, als
                       gingen sie mit. Die Handlung dahinter ist unveraendert. */
                    ['text' => 'Entkoppeln', 'symbol' => 'geraet-entkoppeln',
                     'art' => 'gefahr', 'form' => 'f-devdel-' . $did],
                ],
            ]),
        ]); ?>
      <?php endforeach; ?>

      <?php /* NUR NOCH DAS UMBENENNEN STEHT IN DIESER KARTE (S8/AP6). Das
               Anlegen von Hand ist die Ausnahme und hat seit AP6 eine eigene,
               zugeklappte Karte am Ende der Seite — die Reihenfolge der Seite
               folgt der Haeufigkeit: koppeln, ansehen, App holen, Ausnahme.
               Das Umbenennen dagegen gehoert zur Zeile, auf deren Punkte-Menue man
               gerade geklickt hat, und bleibt hier. */ ?>
      <?php if ($editDev): ?>
        <div class="listen-form">
          <h3 class="listen-form-titel">Bezeichnung ändern</h3>
          <form method="post" action="einstellungen.php?t=geraete">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="rename">
            <input type="hidden" name="id" value="<?= (int)$editDev['id'] ?>">
            <div class="listen-form-felder">
              <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'label', 'id' => 'devlabel',
                             'platzhalter' => 'z. B. Dienstuhr',
                             'wert' => (string)($editDev['label'] ?? ''),
                             'attr' => ' maxlength="120"']); ?>
            </div>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Bezeichnung speichern', 'art' => 'neutral']) ?>
              <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                            'href' => 'einstellungen.php?t=geraete']) ?>
            </div>
          </form>
        </div>
      <?php else: ?>
        <p class="feld-klein">Im Menü der Zeile: Bezeichnung ändern ·
           Deaktivieren / Aktivieren · Entkoppeln.</p>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php /* ---- App installieren (S8/AP6, Mockup 10, E-S8-11) -------------
             ZWEI WEGE, EIN RUECKFALL. Die Uhr-App kommt aus dem
             Connect-IQ-Store, die Handy- und Wear-OS-App aus dem Play Store;
             das APK auf diesem Server ist der Weg fuer den Fall, dass der
             Store nicht geht.

             DIE ADRESSEN STEHEN IN ZWEI KONSTANTEN, und beide sind LEER.
             Weder der Beitrittslink des internen Play-Tests noch die Adresse
             der Uhr-App im Connect-IQ-Store liegen vor (Rahmenplan
             Abschnitt 6, Stand 06.09.2026). Solange eine leer ist, steht ihre
             Zeile ohne Knopf da — mit dem Weg als Text, denn „im Store nach
             NAdoku suchen" ist auch ohne Link eine Anleitung. Ein Knopf ins
             Leere waere schlechter als keiner. Nachzutragen ist danach je
             eine Zeile in `db.php`.

             DIE KARTE STEHT IMMER, auch ohne APK auf dem Server: Sie
             beantwortet die Frage „wie bekomme ich die App", und die stellt
             sich auf jeder Installation. Frueher hiess sie „NAdoku fuer
             Android" und erschien nur, wenn ein APK dalag — die Uhr kam
             darin gar nicht vor. */ ?>
    <?php ui_karte_start(['titel' => 'App installieren', 'id' => 'k-app']); ?>
      <?php
      ui_zeile([
          'text'  => 'Garmin-Uhr',
          'klein' => CONNECT_IQ_URL !== ''
              ? 'Im Connect-IQ-Store auf die Uhr laden. Danach auf der Sync-Seite '
                . '„Gerät koppeln" starten.'
              : 'Im Connect-IQ-Store auf dem Handy nach „NAdoku" suchen und auf die Uhr '
                . 'laden. Danach auf der Sync-Seite „Gerät koppeln" starten.',
          'aktionen' => CONNECT_IQ_URL !== ''
              ? ui_knopf(['text' => 'Connect IQ', 'art' => 'neutral',
                          'href' => CONNECT_IQ_URL, 'attr' => ' target="_blank" rel="noopener"'])
              : '',
      ]);
      ui_zeile([
          'text'  => 'Android-Handy oder Wear-OS-Uhr',
          'klein' => PLAY_TEST_URL !== ''
              ? 'Über den Play Store — im internen Test bis zur Freigabe. Mit dem Link '
                . 'trittst du dem Test bei und installierst wie jede andere App.'
              : 'Über den Play Store, sobald der interne Test offen ist. Bis dahin führt '
                . 'der Weg über das APK weiter unten.',
          'aktionen' => PLAY_TEST_URL !== ''
              ? ui_knopf(['text' => 'Play Store', 'art' => 'neutral',
                          'href' => PLAY_TEST_URL, 'attr' => ' target="_blank" rel="noopener"'])
              : '',
      ]);
      ?>

      <?php /* DAS APK KLAPPT AUF (Mockup 10). Es ist der Rueckfall, nicht der
               Weg — zugeklappt sagt die Zeile, dass es ihn gibt, ohne ihn
               anzubieten. Die Karte zeigt, was auf dem Server LIEGT: Name,
               Groesse, Datum und der gerechnete SHA-256; von Hand gepflegt
               wird nichts.

               DER DOWNLOAD IST EINE LEISE HANDLUNG (Mockup 10; vorher
               neutral): Die eine Haupthandlung dieses Reiters bleibt „Weiter"
               am Feld „Code vom Geraet" (S5, B-S5-07), und der Store steht
               darueber mit einem neutralen Knopf. */ ?>
      <?php $apks = apk_liste(); if ($apks): ?>
        <details class="apk-fach">
          <summary class="akkordeon-zeile">
            <?= ui_symbol('winkel', 'akkordeon-winkel') ?>
            <span class="akkordeon-text">Ohne Play Store: APK von Hand</span>
          </summary>
          <?php foreach ($apks as $apk): ?>
            <?php ui_zeile([
                'text'  => 'NAdoku' . ($apk['version'] !== null ? ' ' . $apk['version'] : ''),
                'klein' => 'APK · ' . apk_groesse($apk['groesse'])
                         . ' · Stand ' . fmt_local(gmdate('Y-m-d H:i:s', $apk['stand']), 'd.m.Y'),
                'aktionen' => ui_knopf(['text' => 'Herunterladen', 'art' => 'leise',
                    'href' => 'apk.php?d=' . rawurlencode($apk['datei'])]),
            ]); ?>
            <?php /* DIE PRUEFSUMME STEHT IM WERTEKASTEN, nicht in der
                     Kleinzeile. Mockup 10 zeigt sie dort gekuerzt
                     (nur Anfang und Ende) — gekuerzt taugt sie aber fuer nichts:
                     Wer nachrechnet, braucht alle 64 Zeichen, und wer nicht
                     nachrechnet, braucht sie gar nicht. Im Wertekasten steht
                     sie vollstaendig und mit einem Knopf, der sie in die
                     Zwischenablage legt — genau das, was jemand tut, der sie
                     mit `sha256sum` vergleichen will. */ ?>
            <?= ui_codeblock_lang(apk_sha_lesbar($apk['sha256']), 'SHA-256') ?>
          <?php endforeach; ?>
          <p class="feld-klein">Nur, wenn der Play Store nicht geht — Updates
             kommen dann nicht von selbst. Beim ersten Öffnen fragt Android
             nach, ob Installationen aus dieser Quelle erlaubt sind; das ist
             bei einer Verteilung ohne App-Store der vorgesehene Weg. Wer der
             Seite nicht traut, rechnet die Prüfsumme der heruntergeladenen
             Datei nach.</p>
        </details>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Gerät ohne Code anlegen (S8/AP6, Mockup 10) ----------------
             DIE AUSNAHME STEHT ZUGEKLAPPT AM ENDE. Sie war bis Web 15.4.0 ein
             Formular MITTEN in der Geräteliste, unter den Zeilen — an der
             Stelle also, an der man nach dem Umbenennen sucht. Der Weg dahin
             ist das Koppeln; von Hand angelegt wird nur, was keinen Code
             zeigen kann.

             NEUTRAL, NICHT PRIMÄR (B-S5-09): Die eine Haupthandlung dieses
             Reiters ist „Weiter" an der Kopplungskarte. */ ?>
    <?php if (!$editDev): ?>
      <?php ui_karte_start(['titel' => 'Gerät ohne Code anlegen', 'id' => 'k-ohne-code',
                            'zu' => true, 'vorschau' => 'Ausnahme · Zugangsdaten von Hand']); ?>
        <p class="feld-hinweis">Für Geräte, die keinen Code anzeigen können. Du
           bekommst Geräte-ID und API-Schlüssel und trägst sie in der App
           ein — bei Garmin in Garmin Connect unter den App-Einstellungen.</p>
        <form method="post" action="einstellungen.php?t=geraete">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="add">
          <div class="listen-form-felder">
            <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'label', 'id' => 'devlabel',
                           'platzhalter' => 'z. B. Ersatzuhr',
                           'attr' => ' maxlength="120"']); ?>
          </div>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Gerät anlegen', 'art' => 'neutral']) ?>
          </div>
        </form>
      <?php ui_karte_ende(true); ?>
    <?php endif; ?>

    <?php if ($newKey): ?>
      <?php ui_karte_start(['titel' => 'Zugangsdaten des neuen Geräts', 'id' => 'k-zugangsdaten']); ?>
        <p class="feld-hinweis">Beide Werte in den Einstellungen der App
           eintragen; als Server genügt die Domain.</p>
        <p class="feld-klein">Bei Garmin stehen diese Einstellungen in Garmin Connect.</p>
        <?php /* KLEINE STUFE MIT „KOPIEREN" (E-S8-10, Backlog Nr. 78). Die
                 grosse Stufe ist fuer sechs Zeichen gemacht; Geraete-ID und
                 API-Schluessel sind 36 beziehungsweise 64 Zeichen lang und
                 standen darin in Plakatgroesse ueber drei Zeilen — und ohne
                 Knopf, obwohl sie zum Abtippen gedacht sind. */ ?>
        <?= ui_codeblock_lang((string)$newKey['device_id'], 'Geräte-ID') ?>
        <?= ui_codeblock_lang((string)$newKey['api_key'], 'API-Schlüssel') ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>
  <?php endif; ?>

  <script>
  /* ---- Abschnitt aus dem Anker wieder aufklappen -------------------------
   *
   * Nach jedem Speichern und Löschen leitet der Server auf einen Anker um; wer
   * dort ankommt, soll genau an der Stelle stehen, an der er getippt hat.
   *
   * ALLE EBENEN, NICHT NUR EINE (Web 7.0.0). Die Stammdatenpflege ist seit dem
   * Aufteilen in zwei Reiter zweistufig verschachtelt: ein <details> je
   * Standort, darin eines je Datenart. Bis Web 6.3.0 öffnete dieses Skript
   * genau ein Element — der äussere Block blieb zu, und der innere lag darin
   * unsichtbar. Man landete auf einer Seite, auf der alles geschlossen war,
   * und musste sich zurückklicken.
   *
   * Deshalb läuft es jetzt von innen nach aussen über die Vorfahren: Jedes
   * <details> auf dem Weg wird geöffnet, danach wird gescrollt (erst dann steht
   * die endgültige Position fest) und in das erste Eingabefeld gesprungen.
   *
   * Der frühere Sonderfall „besatzung-p1" (ID plus Rolle) bleibt erhalten: Er
   * greift nur, wenn es zur vollen Kennung KEIN Element gibt — sonst hätte
   * `sd-12-veh` als „Element sd mit Rolle 12-veh" gelesen werden können. */
  (function(){
    function oeffneVorfahren(el){
      for (let p = el; p; p = p.parentElement) {
        if (p.tagName === 'DETAILS') { p.open = true; }
      }
    }
    function fokus(d, rolle){
      let f = rolle ? d.querySelector('.focus-target[data-role="' + rolle + '"]') : null;
      if (!f) { f = d.querySelector('.focus-target'); }
      if (!f) { f = d.querySelector('input[type=text], input[type=number], select, textarea'); }
      if (f) { f.focus({ preventScroll: true }); }
    }
    function oeffne(hashId){
      let d = document.getElementById(hashId);
      let rolle = '';
      if (!d) {
        // Rückfall: „<id>-<rolle>" — nur wenn die volle Kennung nichts trifft.
        const teil = hashId.split('-');
        d = document.getElementById(teil[0]);
        rolle = teil.slice(1).join('-');
      }
      if (!d) { return; }
      oeffneVorfahren(d);
      if (d.tagName === 'DETAILS') { d.open = true; }
      d.scrollIntoView({ block: 'start' });
      fokus(d, rolle);
    }
    if (location.hash.length > 1) { oeffne(location.hash.slice(1)); }
    window.addEventListener('hashchange', () => {
      if (location.hash.length > 1) { oeffne(location.hash.slice(1)); }
    });
  })();
  </script>

<?php ui_geruest_ende(); ?>
<?php /* `assets/kopieren.js` gehört zum Wertekasten der kleinen Stufe
         (`ui_codeblock_lang()`, Geräte-Reiter): Er blendet den Knopf ein und
         kopiert. Ohne das Skript bleibt der Wert lesbar und der Knopf
         verborgen — ein Knopf, der nichts tut, wäre schlechter als keiner. */ ?>
<?php ui_seite_ende(['skripte' => ['assets/kopieren.js']]); ?>
