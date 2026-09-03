<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/stammdaten_ui.php';   // sd_zeile(), sd_form()
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/validate_lib.php';   // WRAP_RE, Formatkennung
require_once __DIR__ . '/diensttag_lib.php';  // dt_bases(), dt_base_erlaubt(), Rollenkatalog
/* TRASH_DAYS fuer die Rueckmeldung der Wiederherstellung (E-S1-08). Kommt
 * ueber demo_lib.php ohnehin mit — aber eine Frist, die auf der Seite steht,
 * darf nicht an einem zufaelligen Umweg haengen. */
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/apk_lib.php';    // APK-Karte des Geraete-Reiters (S4/A1)
require_once __DIR__ . '/geraete_lib.php'; // Art und Modell in der Geraeteliste (S6)

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
if (!in_array($tab, ['profil', 'geraete', 'standorte', 'rettungsmittel', 'backup'], true)) {
    $tab = 'profil';
}
$notice = null; $error = null; $pwGewechselt = false; $newKey = null; $pairCode = null;

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
        if ($email === null) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben (höchstens 190 Zeichen).';
        } else {
            try {
                db()->prepare('UPDATE users SET name = ?, email = ?, logo_wahl = ? WHERE id = ?')
                    ->execute([$name !== '' ? $name : null, $email, $logo, $userId]);
                $userName = $name !== '' ? $name : null;
                $userEmail = $email;
                $logoWahl  = $logo;
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
            $devId = 'dev-' . bin2hex(random_bytes(4));
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
    if ($action === 'pair_code' && geraete_grenze_erreicht(db(), $userId)) {
        // Schon hier abfangen statt erst beim Einloesen: Der Code waere sonst
        // verbraucht, ohne dass ein Geraet entstanden ist (pair.php entwertet
        // vor der Pruefung, und das ist dort richtig so).
        $error = 'Es sind bereits ' . MAX_GERAETE . ' Geräte mit diesem Konto verbunden. '
               . 'Bitte zuerst ein nicht mehr genutztes Gerät löschen — dann lässt sich '
               . 'wieder ein Kopplungscode erzeugen.';
    } elseif ($action === 'pair_code') {
        // Hoechstens EIN offener Code je Konto: Ein neuer entwertet den alten.
        // Sonst haetten mehrere gleichzeitig gueltige Codes den Raum, den ein
        // Angreifer treffen kann, mit jedem Klick vergroessert — und liegen
        // gebliebene Codes aus abgebrochenen Kopplungsversuchen waeren bis zum
        // Ablauf weiter einloesbar.
        db()->prepare('UPDATE pair_codes SET used_at = NOW()
                       WHERE user_id = ? AND used_at IS NULL')
            ->execute([$userId]);

        // Laenge, Alphabet und Gueltigkeit stehen in db.php — dieselbe Quelle,
        // aus der auch pair.php sein Pruefmuster bildet.
        for ($try = 0; $try < 5; $try++) {
            $code = '';
            for ($i = 0; $i < PAIR_LEN; $i++) {
                $code .= PAIR_CHARS[random_int(0, strlen(PAIR_CHARS) - 1)];
            }
            try {
                db()->prepare('INSERT INTO pair_codes (user_id, code) VALUES (?,?)')
                    ->execute([$userId, $code]);
                $pairCode = $code;
                break;
            } catch (PDOException $ex) {
                /* NUR die Kollision rechtfertigt einen neuen Versuch (M4-09).
                 *
                 * Vorher galt jeder Datenbankfehler als Kollision. Fehlte die
                 * Tabelle oder war die Verbindung weg, versuchte es die Schleife
                 * fuenfmal mit fuenf frischen Zufallscodes und meldete danach
                 * "Bitte erneut versuchen." — eine Aufforderung, die nie zum
                 * Ziel fuehren konnte, weil die Ursache eine ganz andere war.
                 *
                 * Ein Zusammentreffen zweier Codes ist ohnehin so selten, dass
                 * die fuenf Versuche eher Formsache sind. Ein echter Fehler
                 * dagegen soll durchschlagen. */
                if (!ist_dublettenfehler($ex)) { throw $ex; }
            }
        }
        if ($pairCode === null) {
            // Fuenf Kollisionen hintereinander sind bei 32^6 Moeglichkeiten
            // praktisch ausgeschlossen — dann liegt ein anderer Fehler vor,
            // und die NutzerIn soll ihn sehen statt vor einer leeren Kachel
            // zu stehen.
            $error = 'Es konnte kein Kopplungscode erzeugt werden. Bitte erneut versuchen.';
        }
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
    if ($action === 'base_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $bid = (int)($_POST['id'] ?? 0);
        /* Optionale Koordinate (E37/E39). Die Regeln — nur zusammen, ausserhalb
         * des Bereichs leer, Komma zulaessig — stehen seit Web 6.1.0 an EINER
         * Stelle (pruef_ortspaar in validate_lib.php). Vorher gab es dieselbe
         * kleine Umrechnung dreimal: hier, in admin_stammdaten.php und, mit dem
         * Ortsfeld am Einsatz, waere sie ein viertes Mal entstanden. */
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        if ($n !== '') {
            if (stammdaten_dup_global('bases', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($bid > 0) {
                db()->prepare('UPDATE bases SET name = ?, lat = ?, lon = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $lat, $lon, $bid, $userId]);
                $notice = 'Standort gespeichert. Bereits dokumentierte Diensttage bleiben unverändert.';
            } else {
                db()->prepare('INSERT IGNORE INTO bases (user_id, name, lat, lon) VALUES (?,?,?,?)')
                    ->execute([$userId, $n, $lat, $lon]);
                $notice = 'Standort gespeichert.';
            }
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
        $n     = mb_substr(trim($_POST['name'] ?? ''), 0, 64);
        $vid   = (int)($_POST['id'] ?? 0);
        $bid   = $sdBase();
        /* DIE ART IST PFLICHT (Web 7.0.0). Bis Web 6.3.0 stand hier ein
         * stillschweigendes „im Zweifel luftgebunden", und das Formular hatte
         * den Knopf entsprechend vorbelegt. An einem Standort mit NEF war das
         * die falsche Vorgabe — und weil sie nie eine Entscheidung verlangte,
         * fiel sie erst auf, wenn im Einsatzformular Windenfelder erschienen.
         * Ohne Angabe wird jetzt nicht gespeichert. */
        $kindRoh = (string)($_POST['kind'] ?? '');
        $kind = in_array($kindRoh, ['air', 'ground'], true) ? $kindRoh : null;
        if ($n === '') {
            $error = 'Bitte eine Bezeichnung für das Rettungsmittel eintragen.';
        } elseif ($kind === null) {
            $error = 'Bitte die Art wählen: luftgebunden oder bodengebunden. '
                   . 'Sie entscheidet über Besatzungsrollen und die im '
                   . 'Einsatzformular sichtbaren Felder.';
        } elseif ($bid === null) {
            $error = 'Bitte einen Standort wählen. Jedes Rettungsmittel gehört zu '
                   . 'genau einem Standort.';
        } elseif (stammdaten_dup_global('vehicles', 'name', $n)) {
            $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
        } else {
            /* Rollen aus dem Katalog, gefiltert auf die Art (E5/E6): Ein
             * bodengebundenes Rettungsmittel kann keinen Flugretter fuehren.
             * Die Filterung geschieht hier und nicht nur im Formular — ein
             * Haken, den die Oberflaeche nicht anbietet, darf auch ueber eine
             * gesendete Anfrage nicht hereinkommen. */
            $erlaubteRollen = array_keys(crew_roles_fuer_art($kind));
            $rollen = [];
            foreach ((array)($_POST['roles'] ?? []) as $rc) {
                if (in_array((string)$rc, $erlaubteRollen, true)) { $rollen[] = (string)$rc; }
            }
            /* Faehigkeiten kommen AUSSCHLIESSLICH an luftgebundenen
             * Rettungsmitteln vor (E29). Bei einem bodengebundenen werden
             * vorhandene Zeilen entfernt — so steht es im Schema, und ein
             * Zustand, den die Oberflaeche nicht herstellen kann, soll auch
             * nicht in der Datenbank stehen. */
            $caps = [];
            if ($kind === 'air') {
                foreach ((array)($_POST['caps'] ?? []) as $c) {
                    if (array_key_exists((string)$c, VEHICLE_CAPABILITIES)) { $caps[] = (string)$c; }
                }
            }

            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($vid > 0) {
                    $pdo->prepare('UPDATE vehicles SET name = ?, kind = ?, base_id = ?
                                   WHERE id = ? AND user_id = ?')
                        ->execute([$n, $kind, $bid, $vid, $userId]);
                } else {
                    $pdo->prepare('INSERT INTO vehicles (user_id, base_id, name, kind)
                                   VALUES (?,?,?,?)')
                        ->execute([$userId, $bid, $n, $kind]);
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
                $notice = 'Rettungsmittel gespeichert. Bereits dokumentierte Diensttage '
                        . 'behalten Art, Rollen und Fähigkeiten unverändert.';
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
        $role = (string)($_POST['role'] ?? '');
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $cid = (int)($_POST['id'] ?? 0);
        $bid = $sdBase();
        if ($n !== '' && array_key_exists($role, CREW_ROLES)) {
            if ($bid === null) {
                $error = 'Bitte einen Standort wählen.';
            } elseif (stammdaten_dup_global('crew_presets', 'name', $n, 'role_code', $role)) {
                $error = '„' . $n . '“ ' . 'ist für diese Rolle bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($cid > 0) {
                db()->prepare('UPDATE crew_presets SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $cid, $userId]);
                $notice = 'Eintrag gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO crew_presets (user_id, base_id, role_code, name)
                               VALUES (?,?,?,?)')
                    ->execute([$userId, $bid, $role, $n]);
                $notice = 'Eintrag gespeichert.';
            }
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
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        $bid = $sdBase();
        if ($n !== '') {
            if ($bid === null) {
                $error = 'Bitte einen Standort wählen.';
            } elseif (stammdaten_dup_global('resources', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($wid > 0) {
                db()->prepare('UPDATE resources SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $wid, $userId]);
                $notice = 'Rettungsmittel gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO resources (user_id, base_id, name) VALUES (?,?,?)')
                    ->execute([$userId, $bid, $n]);
                $notice = 'Rettungsmittel gespeichert.';
            }
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
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        $bid = $sdBase();
        if ($n !== '') {
            if ($bid === null) {
                $error = 'Bitte einen Standort wählen.';
            } elseif (stammdaten_dup_global('bw_units', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($wid > 0) {
                db()->prepare('UPDATE bw_units SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $wid, $userId]);
                $notice = 'Bereitschaft gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO bw_units (user_id, base_id, name) VALUES (?,?,?)')
                    ->execute([$userId, $bid, $n]);
                $notice = 'Bereitschaft gespeichert.';
            }
        }
    }
    if ($action === 'bw_del') {
        db()->prepare('DELETE FROM bw_units WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Bereitschaft gelöscht.';
    }

    if ($action === 'td_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 190);
        $tid = (int)($_POST['id'] ?? 0);
        $bid = $sdBase();
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        if ($n !== '') {
            if ($bid === null) {
                $error = 'Bitte einen Standort wählen.';
            } elseif (stammdaten_dup_global('transport_dests', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($tid > 0) {
                db()->prepare('UPDATE transport_dests SET name = ?, lat = ?, lon = ?
                               WHERE id = ? AND user_id = ?')
                    ->execute([$n, $lat, $lon, $tid, $userId]);
                $notice = 'Zielklinik gespeichert. Bereits dokumentierte Einsätze bleiben '
                        . 'unverändert.';
            } else {
                db()->prepare('INSERT IGNORE INTO transport_dests (user_id, base_id, name, lat, lon)
                               VALUES (?,?,?,?,?)')
                    ->execute([$userId, $bid, $n, $lat, $lon]);
                $notice = 'Zielklinik gespeichert.';
            }
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Zielklinik gelöscht.';
    }

    /* Nach dem Speichern zurueck zum passenden Abschnitt umleiten. Das oeffnet
     * ihn dank des Ankers automatisch wieder und verhindert nebenbei das
     * erneute Absenden beim Neuladen der Seite.
     *
     * Die Anker sind seit der Gliederung nach Standort (Konzept 3.8)
     * STANDORTBEZOGEN: `sd-<Standortkennung>` oeffnet den Block dieses
     * Standorts. Nur die Standortliste selbst und die Auswahl der zentralen
     * Standorte haben feste Anker. */
    /* ZWEI REITER, ZWEI ZIELE (Web 7.0.0). Die Standortaktionen fuehren in den
     * Reiter „Standorte" zurueck, alles Uebrige in „Rettungsmittel". */
    $zurueckTab = in_array($action, ['base_save', 'base_del', 'base_default', 'ub_toggle'], true)
        ? 'standorte' : 'rettungsmittel';
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
    if ($abschnitt === null && $unterblock !== null) {
        $zurueckBase = (int)($_POST['base_id'] ?? 0);
        $abschnitt = $zurueckBase > 0
            ? ('sd-' . $zurueckBase . '-' . $unterblock)
            : 'standorte';
    }
    if ($abschnitt !== null && ($notice !== null || $error !== null)) {
        if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
        if ($error !== null) { $_SESSION['flash_error'] = $error; }
        header('Location: einstellungen.php?t=' . $zurueckTab . '#' . $abschnitt);
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
ui_seite_start(['titel' => 'Einstellungen']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => $tab]); ?>
  <?php ui_meldung($notice, $error, 'info', '  '); ?>

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

    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">

      <?php ui_karte_start(['titel' => 'Angaben']); ?>
        <?php ui_feld(['label' => 'Name', 'name' => 'name', 'wert' => (string)($userName ?? ''),
                       'platzhalter' => 'wird in der Kopfleiste angezeigt',
                       'attr' => ' maxlength="120"']); ?>
        <?php ui_feld(['label' => 'E-Mail-Adresse (Anmeldung)', 'name' => 'email',
                       'art' => 'email', 'wert' => $userEmail, 'pflicht' => true]); ?>
      <?php ui_karte_ende(); ?>

      <?php /* LOGO-WAHL (E-P3-20, Mockup 13). Sie gilt für Kopfleiste UND
               Browser-Symbol; die Anmeldeseite zeigt immer den Standard, weil
               dort noch niemand angemeldet ist und die Wahl am Konto hängt. */ ?>
      <?php ui_karte_start(['titel' => 'Logo']); ?>
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

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Profil speichern', 'art' => 'primaer']) ?>
      </div>
    </form>

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
      <?php ui_karte_start(['titel' => 'Passwort ändern']); ?>
        <?php ui_feld(['label' => 'Aktuelles Passwort', 'name' => 'old', 'id' => 'pw_old',
                       'art' => 'password', 'pflicht' => true,
                       'attr' => ' autocomplete="current-password"']); ?>
        <?php ui_feld(['label' => 'Neues Passwort', 'name' => 'new1', 'id' => 'pw_new1',
                       'art' => 'password', 'pflicht' => true,
                       'klein' => 'Mindestens 10 Zeichen. Die Stärke des Passworts ist '
                                . 'unmittelbar die Stärke der Verschlüsselung.',
                       'attr' => ' minlength="10" autocomplete="new-password"']); ?>
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

  <?php elseif ($tab === 'standorte' || $tab === 'rettungsmittel'): ?>
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
      $sdVeh  = $sdLade('vehicles', 'id, name, kind');
      $sdCrew = $sdLade('crew_presets', 'id, name, role_code');
      $sdTd   = $sdLade('transport_dests', 'id, name, lat, lon');
      $sdRes  = $sdLade('resources', 'id, name');
      $sdBw   = $sdLade('bw_units', 'id, name');

      // Rollen und Faehigkeiten je Rettungsmittel, ebenfalls gebuendelt.
      $vehIds = [];
      foreach ($sdVeh as $liste) { foreach ($liste as $v) { $vehIds[] = (int)$v['id']; } }
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

      /* Bearbeiten ist nur fuer EIGENE Eintraege moeglich — zentrale Zeilen
       * haben in der Nutzeransicht keine Bearbeiten-/Loeschen-Schaltflaechen. */
      $pickIn = function (array $nachBase, string $param) use ($userId) {
          $ges = (int)($_GET[$param] ?? 0);
          if ($ges <= 0) { return null; }
          foreach ($nachBase as $liste) {
              foreach ($liste as $z) {
                  if ((int)$z['id'] === $ges && (int)$z['user_id'] === $userId) { return $z; }
              }
          }
          return null;
      };
      $editVeh  = $pickIn($sdVeh, 'ev');
      $editCrew = $pickIn($sdCrew, 'ec');
      $editTd   = $pickIn($sdTd, 'et');
      $editRes  = $pickIn($sdRes, 'er');
      $editBw   = $pickIn($sdBw, 'ew');
      $editBase = null;
      foreach ($eigene as $b) { if ((int)$b['id'] === (int)($_GET['eb'] ?? 0)) { $editBase = $b; } }

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
       die Vorbelegung, den Abfahrtsort und die
       <a href="einstellungen.php?t=rettungsmittel">Rettungsmittel</a>. Der Stern
       markiert die Vorbelegung neuer Diensttage. Löschen entfernt nur den
       Listeneintrag — dokumentierte Diensttage bleiben unverändert.</p>

    <?php ui_karte_start(['titel' => 'Eigene Standorte', 'zahl' => count($eigene), 'id' => 'standorte']); ?>
      <?php if (!$eigene): ?>
        <p class="feld-hinweis">Noch keine eigenen Standorte.</p>
      <?php endif; ?>
      <?php foreach ($eigene as $b):
            $bid = (int)$b['id'];
            $dup = stammdaten_dup_global('bases', 'name', $b['name']);
            $anz = $sdAnzahl($bid);
            $istDef = $bid === $DEF_BASE_ID;
            /* Die POST-Formulare stehen EINMAL und versteckt; die Knöpfe der
               Zeile und die des Aktionsblatts zeigen beide über `form` darauf
               (ui_zeilenaktionen). */ ?>
        <form method="post" id="f-bdef-<?= $bid ?>" class="nur-vorlesen"
              action="einstellungen.php?t=standorte#standorte">
          <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
          <input type="hidden" name="id" value="<?= $bid ?>">
        </form>
        <form method="post" id="f-bdel-<?= $bid ?>" class="nur-vorlesen"
              action="einstellungen.php?t=standorte#standorte"
              data-confirm="Standort „<?= e($b['name']) ?>“ löschen? <?= $anz > 0
                  ? ($anz === 1 ? 'Ein eigener Stammdatensatz' : $anz . ' eigene Stammdatensätze')
                    . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht) werden mitgelöscht.'
                  : 'Es hängen keine eigenen Stammdaten daran.' ?> Bereits dokumentierte Diensttage bleiben unverändert.">
          <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
          <input type="hidden" name="id" value="<?= $bid ?>">
        </form>
        <?php
        $klein = [];
        if ($b['lat'] !== null && $b['lon'] !== null) {
            $klein[] = $b['lat'] . ', ' . $b['lon'];
        } else {
            $klein[] = 'ohne Lage';
        }
        if ($dup) { $klein[] = 'identisch mit einem systemweiten Eintrag'; }
        $eintraege = [];
        if (!$istDef) {
            $eintraege[] = ['text' => 'Als Vorbelegung', 'symbol' => 'stern',
                            'art' => 'leise-orange', 'form' => 'f-bdef-' . $bid];
        }
        $eintraege[] = ['text' => 'Bearbeiten', 'symbol' => 'stift',
                        'href' => 'einstellungen.php?t=standorte&eb=' . $bid . '#standorte'];
        $eintraege[] = ['text' => 'Löschen', 'symbol' => 'korb',
                        'art' => 'gefahr', 'form' => 'f-bdel-' . $bid];
        ui_zeile([
            'text'  => (string)$b['name'],
            'klein' => implode(' · ', $klein),
            'plaketten' => $istDef ? ui_symbol('stern', 'zeile-stern', 'Vorbelegung neuer Diensttage') : '',
            'aktionen' => ui_zeilenaktionen(['titel' => (string)$b['name'], 'eintraege' => $eintraege]),
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
                    'praefix' => 'sdbase', 'feld' => false, 'such' => true,
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

  <?php else: ?>
    <?php /* ---- Reiter „Rettungsmittel" ------------------------------------
             Alles, was an einem ausgewählten Standort hängt. Ein Block je
             Standort, darin je Datenart ein eigener. */ ?>
    <?php ui_titelzeile(['titel' => 'Rettungsmittel']); ?>
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
      <?php /* Ein Standort ist eine zugeklappte Karte; die Listen darin sind
               Abschnitte mit Überschrift. Verschachtelte Karten wären zwei
               Rahmen um dieselbe Sache — die zweite Ebene trägt hier keine
               eigene Bedeutung, sie ordnet nur. */
             ui_karte_start(['titel' => (string)$b['name'], 'id' => $anker, 'zu' => true,
                             'zahl' => count($vehListe) . ' Rettungsmittel']); ?>
        <?php if (!empty($b['zentral'])): ?>
          <p class="feld-hinweis"><?= ui_plakette('systemweit') ?> Dieser Standort wird
             von der Administration gepflegt.</p>
        <?php endif; ?>

        <section class="sd-liste" id="<?= e($anker) ?>-veh">
          <h3 class="sd-titel">Rettungsmittel <span class="sd-zahl"><?= count($vehListe) ?></span></h3>
          <p class="feld-hinweis">Die Art entscheidet über Besatzungsrollen und die
             im Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht)
             gibt es nur luftgebunden.</p>
          <?php if (!$vehListe): ?>
            <p class="feld-hinweis">Noch keine Rettungsmittel an diesem Standort.</p>
          <?php endif; ?>
          <?php foreach ($vehListe as $v):
                $vid = (int)$v['id'];
                $sym = dt_art_symbol((string)$v['kind']);
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
                    'anker' => $anker . '-veh', 'praefix' => 'veh', 'id' => $vid,
                    'base_id' => $bid, 'zentral' => $istZentral($v),
                    'stern' => $vid === $DEF_VEH_ID,
                    'def_action' => 'veh_default', 'del_action' => 'veh_del',
                    'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ löschen? '
                                 . 'Bereits dokumentierte Diensttage bleiben unverändert.',
                    'bearbeiten_href' => 'einstellungen.php?t=rettungsmittel&ev=' . $vid
                                       . '#' . $anker . '-veh',
                ]);
          endforeach; ?>
          <?php $evHier = ($editVeh && (int)$editVeh['base_id'] === $bid) ? $editVeh : null;
                $evRollen = $evHier ? ($vehRollen[(int)$evHier['id']] ?? []) : [];
                $evCaps   = $evHier ? ($vehCaps[(int)$evHier['id']] ?? []) : []; ?>
          <?php /* ---- EINGABE (Web 7.0.0 neu gefasst) -----------------------
                   Vorher klebte alles am oberen Rand: Bezeichnung, Art,
                   Rollenhaken und Fähigkeiten standen ohne Abstand unter der
                   Tabelle, und es war nicht zu sehen, dass die Haken zur
                   Eingabezeile darüber gehören und nicht zum letzten
                   Tabelleneintrag.
                   Jetzt umschliesst ein eigener Rahmen (`.neu-form`) die ganze
                   Eingabe, mit Überschrift. Die Schaltfläche steht in der
                   Eingabezeile — die Haken darunter sind Zubehör, nicht der
                   Abschluss.
                   UND DIE ART IST NICHT MEHR VORBELEGT: „luftgebunden" stand
                   von selbst da, und an einem NEF-Standort war das die falsche
                   Vorgabe, die niemand bemerkt. Ohne Auswahl weist der Server
                   die Eingabe jetzt ab und sagt, was fehlt. */ ?>
          <div class="listen-form">
            <h3 class="listen-form-titel"><?= $evHier ? 'Rettungsmittel bearbeiten' : 'Rettungsmittel hinzufügen' ?></h3>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-veh" class="ac-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="veh_save">
              <input type="hidden" name="id" value="<?= $evHier ? (int)$evHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <div class="listen-form-felder">
                <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'name',
                               'id' => 'vehname-' . $bid, 'pflicht' => true,
                               'platzhalter' => 'z. B. Alpenfalke 1 oder NEF Talwang 76/1',
                               'wert' => (string)($evHier['name'] ?? ''),
                               'attr' => ' maxlength="64"']); ?>
                <?php /* DIE ART IST NICHT VORBELEGT (Web 7.0.0): „luftgebunden"
                         stand von selbst da, und an einem NEF-Standort war das
                         die falsche Vorgabe, die niemand bemerkt. Ohne Auswahl
                         weist der Server die Eingabe ab und sagt, was fehlt.
                         Sie steuert zugleich, welche Rollen und Fähigkeiten
                         angehakt werden können — im Browser sichtbar, auf dem
                         Server noch einmal gefiltert. */ ?>
                <div class="feld">
                  <span class="feld-label">Art <span class="feld-pflicht" aria-hidden="true">*</span></span>
                  <span class="vehkind">
                    <label><input type="radio" name="kind" value="air" class="vehkind-radio"
                           <?= ($evHier && $evHier['kind'] === 'air') ? 'checked' : '' ?>> luftgebunden</label>
                    <label><input type="radio" name="kind" value="ground" class="vehkind-radio"
                           <?= ($evHier && $evHier['kind'] === 'ground') ? 'checked' : '' ?>> bodengebunden</label>
                  </span>
                </div>
              </div>
              <div class="feld rollen-zeile">
                <span class="feld-label">Besatzungsrollen <span class="feld-klein-inline">(optional)</span></span>
                <span class="acroles">
                  <?php foreach (CREW_ROLES as $rc => $rr): ?>
                    <label class="rollehaken" data-kind="<?= e($rr['kind']) ?>">
                      <input type="checkbox" name="roles[]" value="<?= e($rc) ?>"
                             <?= in_array($rc, $evRollen, true) ? 'checked' : '' ?>>
                      <?= e($rr['label']) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
              <div class="feld vehcaps-zeile">
                <span class="feld-label">Fähigkeiten <span class="feld-klein-inline">(nur luftgebunden)</span></span>
                <span class="acroles vehcaps">
                  <?php foreach (VEHICLE_CAPABILITIES as $ck => $cl): ?>
                    <label><input type="checkbox" name="caps[]" value="<?= e($ck) ?>"
                           <?= in_array($ck, $evCaps, true) ? 'checked' : '' ?>>
                      <?= e($cl) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
              <div class="listen-form-fuss">
                <?= ui_knopf(['text' => $evHier ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
                <?php if ($evHier): ?>
                  <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                                'href' => 'einstellungen.php?t=rettungsmittel']) ?>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </section>

        <?php /* BESATZUNG — nur die Rollen, die es an diesem Standort gibt. */ ?>
        <section class="sd-liste" id="<?= e($anker) ?>-crew">
          <h3 class="sd-titel">Besatzung <span class="sd-zahl"><?= count($sdCrew[$bid] ?? []) ?></span></h3>
          <p class="feld-hinweis">Vorschläge für die Besatzungsfelder, je Rolle.
             Freitext bleibt überall möglich — wer aushilft, muss nicht erst hier
             eingetragen werden.</p>
          <?php if (!$rollenHier): ?>
            <p class="feld-hinweis">Noch keine Rolle an diesem Standort. Rollen
               entstehen am Rettungsmittel: Trage oben eines ein und hake an,
               welche Rollen es führt.</p>
          <?php endif; ?>
          <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
            <h4 class="sd-rolle"><?= e($rr['label']) ?></h4>
            <?php $any = false;
                  foreach (($sdCrew[$bid] ?? []) as $c):
                      if ($c['role_code'] !== $rk) { continue; }
                      $any = true; $cz = $istZentral($c);
                      $dup = !$cz && stammdaten_dup_global('crew_presets', 'name', $c['name'], 'role_code', $rk);
                      sd_zeile([
                          'name' => (string)$c['name'],
                          'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                          'anker' => $anker . '-crew', 'praefix' => 'crew', 'id' => (int)$c['id'],
                          'base_id' => $bid, 'zentral' => $cz,
                          'del_action' => 'crew_del',
                          'del_frage' => 'Eintrag „' . $c['name'] . '“ löschen?',
                          'bearbeiten_href' => 'einstellungen.php?t=rettungsmittel&ec=' . (int)$c['id']
                                             . '#' . $anker . '-crew',
                      ]);
                  endforeach;
                  if (!$any): ?>
              <p class="feld-hinweis">Noch keine Einträge.</p>
            <?php endif; ?>
            <?php $ecHier = ($editCrew && (int)$editCrew['base_id'] === $bid
                             && $editCrew['role_code'] === $rk) ? $editCrew : null; ?>
            <?php sd_form([
                'anker' => $anker . '-crew', 'action' => 'crew_save', 'base_id' => $bid,
                'bearbeitet' => $ecHier, 'label' => 'Name',
                'platzhalter' => 'Name der Person',
                'titel_neu' => $rr['label'] . ' hinzufügen',
                'titel_bearbeiten' => $rr['label'] . ' bearbeiten',
                'felder_versteckt' => '<input type="hidden" name="role" value="' . ui_e($rk) . '">',
            ]); ?>
          <?php endforeach; ?>
        </section>

        <section class="sd-liste" id="<?= e($anker) ?>-td">
          <h3 class="sd-titel">Zielkliniken <span class="sd-zahl"><?= count($sdTd[$bid] ?? []) ?></span></h3>
          <p class="feld-hinweis">Vorschläge für das Feld „Transportziel" im
             Einsatz. Koordinaten sind freiwillig; ohne sie entsteht lediglich
             kein Pin auf der Karte.</p>
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
                    'anker' => $anker . '-td', 'praefix' => 'td', 'id' => (int)$t['id'],
                    'base_id' => $bid, 'zentral' => $tz,
                    'del_action' => 'td_del',
                    'del_frage' => 'Zielklinik „' . $t['name'] . '“ löschen?',
                    'bearbeiten_href' => 'einstellungen.php?t=rettungsmittel&et=' . (int)$t['id']
                                       . '#' . $anker . '-td',
                ]);
          endforeach; ?>
          <?php $etHier = ($editTd && (int)$editTd['base_id'] === $bid) ? $editTd : null; ?>
          <div class="listen-form">
            <h3 class="listen-form-titel"><?= $etHier ? 'Zielklinik bearbeiten' : 'Zielklinik hinzufügen' ?></h3>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-td">
              <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
              <input type="hidden" name="id" value="<?= $etHier ? (int)$etHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <?php /* Das Präfix trägt die Standortkennung: Dieses Formular steht
                       EINMAL JE STANDORT auf der Seite, und zwei Ortsfelder mit
                       denselben Element-Kennungen fänden beide dasselbe Feld. */
                    $tdPraefix = 'sdtd' . $bid; $ORTSFELDER[] = $tdPraefix; ?>
              <div class="listen-form-felder">
                <?php ui_feld(['label' => 'Name', 'name' => 'name',
                               'id' => $tdPraefix . '-name', 'pflicht' => true,
                               'platzhalter' => 'z. B. Klinikum Westried',
                               'wert' => (string)($etHier['name'] ?? ''),
                               'attr' => ' maxlength="190"']); ?>
                <?php ui_ortsfeld([
                        'praefix' => $tdPraefix, 'feld' => false, 'such' => true,
                        'klasse' => 'loc-inline',
                        'such_hinweis' => 'Lage (optional)',
                        'lat_name' => 'lat', 'lon_name' => 'lon',
                        'lat' => (string)($etHier['lat'] ?? ''),
                        'lon' => (string)($etHier['lon'] ?? ''),
                    ]); ?>
              </div>
              <div class="listen-form-fuss">
                <?= ui_knopf(['text' => $etHier ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
                <?php if ($etHier): ?>
                  <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                                'href' => 'einstellungen.php?t=rettungsmittel']) ?>
                <?php endif; ?>
              </div>
            </form>
          </div>
        </section>

        <section class="sd-liste" id="<?= e($anker) ?>-res">
          <h3 class="sd-titel">Weitere Rettungsmittel <span class="sd-zahl"><?= count($sdRes[$bid] ?? []) ?></span></h3>
          <p class="feld-hinweis">Vorschläge für das Feld „Weitere Rettungsmittel"
             im Einsatz (RTW, NEF, RTH …).</p>
          <?php if (!($sdRes[$bid] ?? [])): ?>
            <p class="feld-hinweis">Noch keine Einträge.</p>
          <?php endif; ?>
          <?php foreach (($sdRes[$bid] ?? []) as $r):
                $rz = $istZentral($r);
                $dup = !$rz && stammdaten_dup_global('resources', 'name', $r['name']);
                sd_zeile([
                    'name' => (string)$r['name'],
                    'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                    'anker' => $anker . '-res', 'praefix' => 'res', 'id' => (int)$r['id'],
                    'base_id' => $bid, 'zentral' => $rz,
                    'del_action' => 'res_del',
                    'del_frage' => 'Eintrag „' . $r['name'] . '“ löschen?',
                    'bearbeiten_href' => 'einstellungen.php?t=rettungsmittel&er=' . (int)$r['id']
                                       . '#' . $anker . '-res',
                ]);
          endforeach; ?>
          <?php $erHier = ($editRes && (int)$editRes['base_id'] === $bid) ? $editRes : null;
                sd_form([
                    'anker' => $anker . '-res', 'action' => 'res_save', 'base_id' => $bid,
                    'bearbeitet' => $erHier, 'label' => 'Bezeichnung',
                    'platzhalter' => 'z. B. RTW Talwang 76/85',
                    'titel_neu' => 'Rettungsmittel hinzufügen',
                    'titel_bearbeiten' => 'Eintrag bearbeiten',
                ]); ?>
        </section>

        <?php if ($hatLuft): ?>
          <section class="sd-liste" id="<?= e($anker) ?>-bw">
            <h3 class="sd-titel">Bergwacht <span class="sd-zahl"><?= count($sdBw[$bid] ?? []) ?></span></h3>
            <p class="feld-hinweis">Bereitschaften für das Feld „Bergwacht" im
               Einsatz. Der Abschnitt erscheint, weil an diesem Standort ein
               luftgebundenes Rettungsmittel steht — die Fähigkeit kommt nur
               dort vor.</p>
            <?php if (!($sdBw[$bid] ?? [])): ?>
              <p class="feld-hinweis">Noch keine Bereitschaften.</p>
            <?php endif; ?>
            <?php foreach (($sdBw[$bid] ?? []) as $w):
                  $wz = $istZentral($w);
                  $dup = !$wz && stammdaten_dup_global('bw_units', 'name', $w['name']);
                  sd_zeile([
                      'name' => (string)$w['name'],
                      'klein' => $dup ? 'identisch mit einem systemweiten Eintrag' : '',
                      'anker' => $anker . '-bw', 'praefix' => 'bw', 'id' => (int)$w['id'],
                      'base_id' => $bid, 'zentral' => $wz,
                      'del_action' => 'bw_del',
                      'del_frage' => 'Bereitschaft „' . $w['name'] . '“ löschen?',
                      'bearbeiten_href' => 'einstellungen.php?t=rettungsmittel&ew=' . (int)$w['id']
                                         . '#' . $anker . '-bw',
                  ]);
            endforeach; ?>
            <?php $ewHier = ($editBw && (int)$editBw['base_id'] === $bid) ? $editBw : null;
                  sd_form([
                      'anker' => $anker . '-bw', 'action' => 'bw_save', 'base_id' => $bid,
                      'bearbeitet' => $ewHier, 'label' => 'Bereitschaft',
                      'platzhalter' => 'z. B. Bergwacht Sonnenau',
                      'titel_neu' => 'Bereitschaft hinzufügen',
                      'titel_bearbeiten' => 'Bereitschaft bearbeiten',
                  ]); ?>
          </section>
        <?php endif; ?>
      <?php ui_karte_ende(true); ?>
    <?php endforeach; ?>
  <?php endif; ?>

    <script src="<?= asset('assets/openlocationcode.js') ?>"></script>
    <script src="<?= asset('assets/locparse.js') ?>"></script>
    <script src="<?= asset('assets/ortsfeld.js') ?>"></script>
    <script>
    /* Ortsfelder der Stammdatenpflege beleben (E37). Dieselbe Komponente wie
     * am Einsatz — mit getrennter Suche, weil das Namensfeld hier den NAMEN
     * trägt und nicht die Adresse. Ohne Vorschlagsliste: Was hier entsteht,
     * IST die Vorschlagsliste. */
    <?= 'const ORTSFELDER = ' . json_encode($ORTSFELDER) . ';' ?>
    ORTSFELDER.forEach(p => EdOrtsfeld.init({ praefix: p, getrennteSuche: true }));
    </script>

    <script>
    /* Rollen- und Fähigkeitshaken zur Art passend ein- und ausblenden (E3).
     *
     * Rein anzeigend: Was zulässig ist, entscheidet der Server in 'veh_save'.
     * Diese Zeilen nehmen der Ablehnung nur die Überraschung — und verhindern,
     * dass jemand einen Flugretter an einem NEF anhakt und sich danach fragt,
     * wo der Haken geblieben ist.
     *
     * OHNE GEWÄHLTE ART sind BEIDE Bereiche verborgen (Web 7.0.0). Die Art ist
     * nicht mehr vorbelegt; Rollenhaken zu zeigen, bevor feststeht, welche
     * überhaupt in Frage kommen, hiesse Auswahl anzubieten und sie gleich
     * wieder wegzunehmen. */
    document.querySelectorAll('form.ac-form').forEach(function (f) {
      function anpassen() {
        var gewaehlt = f.querySelector('.vehkind-radio:checked');
        var kind = gewaehlt ? gewaehlt.value : null;
        f.querySelectorAll('.rollehaken').forEach(function (lab) {
          var k = lab.dataset.kind;
          var passt = kind !== null && (k === 'both' || k === kind);
          lab.hidden = !passt;
          if (!passt) { lab.querySelector('input').checked = false; }
        });
        var caps = f.querySelector('.vehcaps-zeile');
        if (caps) {
          caps.hidden = (kind !== 'air');
          if (kind !== 'air') {
            caps.querySelectorAll('input').forEach(function (i) { i.checked = false; });
          }
        }
        var rollen = f.querySelector('.rollen-zeile');
        if (rollen) { rollen.hidden = (kind === null); }
      }
      f.querySelectorAll('.vehkind-radio').forEach(function (r) {
        r.addEventListener('change', anpassen);
      });
      anpassen();
    });
    </script>
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

    <?php ui_karte_start(['titel' => 'Backup erstellen']); ?>
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

      <?php ui_feld(['label' => 'Passwort für das Backup', 'id' => 'bpw1',
                     'art' => 'password', 'klasse' => 'bpw1-feld',
                     'klein' => 'Mindestens 10 Zeichen.',
                     'attr' => ' minlength="10" autocomplete="new-password"']); ?>
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

    <?php ui_karte_start(['titel' => 'Backup einspielen']); ?>
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
      <?php ui_karte_start(['titel' => 'Für dich freigegebenes Backup']); ?>
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
                                . 'sich die geschützten Angaben dieses Backups von niemandem '
                                . 'mehr öffnen.',
                       'attr' => ' autocomplete="off"']); ?>
        </div>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Backup einspielen', 'art' => 'primaer',
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
    const KONTO_MAIL = <?= json_encode($userEmail) ?>;
    const KONTO_NAME = <?= json_encode($userName) ?>;
    /* Die Fassung der Anwendung wandert ins Manifest des Backups: Wer eine
       Datei in zwei Jahren wiederfindet, soll ihr ansehen, womit sie
       entstanden ist. */
    const WEB_VERSION = <?= json_encode(WEB_VERSION) ?>;

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
          expState.textContent = `Spuren werden geholt (Teil ${i + 1} von ${teileplan.length})…`;
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
                  throw new Error('Die Spuren konnten nicht geladen werden (' + grund
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
                throw new Error('Der Server kam mit ' + rest.length + ' Spuren auch nach '
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
          + `${spurenGesamt} Spuren mit ${punkteGesamt.toLocaleString('de-DE')} Punkten `
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
                + `${fehlerhaft.length === 1 ? 'Spur konnte' : 'Spuren konnten'} nicht `
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
        + '.' + uebersprungen + papierkorb + (zusatz || '')
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
            impState.textContent = `Spuren werden übertragen `
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
                throw new Error('Die Spuren konnten nicht übertragen werden ('
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
          ? ` ${spurenGeschrieben} Spuren übernommen`
            + (spurenUebersprungen ? `, ${spurenUebersprungen} waren schon da` : '')
            + (ohneZiel ? `, ${ohneZiel} ohne zugehörigen Einsatz (übersprungen)` : '')
            + (spurenAbgelehnt.length
                ? `. ACHTUNG: ${spurenAbgelehnt.length} Spuren abgelehnt: `
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
          `Die Administration hat ein Backup vom `
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
            fgState.textContent = `Spuren werden übertragen (Teil ${i} von ${nS})…`;
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
                          ? ` ${s.spuren_uebernommen} Spuren übernommen.` : '')
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
    <p class="seiten-erklaerung">Jedes Gerät — Uhr oder Handy — bekommt eigene
       Zugangsdaten für den Upload. Deaktivieren sperrt den Schlüssel — hochgeladene Daten
       bleiben. Je Konto sind <?= MAX_GERAETE ?> Geräte möglich, belegt sind
       <?= count($devices) ?>; deaktivierte zählen mit, erst Löschen gibt einen
       Platz frei.</p>

    <?php if ($devNeu > 0): ?>
      <?php /* Zweite Spur neben der E-Mail beim Koppeln: Wer die Post nicht
               liest, sieht ein neu hinzugekommenes Gerät wenigstens hier. */ ?>
      <?php ui_meldung(
          ($devNeu === 1 ? 'Ein Gerät ist' : $devNeu . ' Geräte sind')
          . ' in den letzten ' . GERAETE_NEU_TAGE . ' Tagen hinzugekommen — unten mit '
          . '„neu" gekennzeichnet. Kommt dir davon etwas unbekannt vor, lösche es hier; '
          . 'danach kann es nichts mehr hochladen.', null, 'warn', '      '); ?>
    <?php endif; ?>

    <?php /* „Gerät koppeln", nicht „Uhr koppeln" (S6): Seit S4 koppelt die
             Handy-App ueber denselben Weg und denselben Code — und die Liste
             darunter sagt seit Web 12.9.0 „Handy". Eine Ueberschrift, die
             „Uhr" sagt, widerspricht der Zeile, die sie ankuendigt. */ ?>
    <?php ui_karte_start(['titel' => 'Gerät koppeln', 'id' => 'koppeln']); ?>
      <p class="feld-hinweis">Erzeuge einen Code und gib ihn auf dem Gerät ein:
         <strong>Sync-Seite → Gerät koppeln → Code eintippen</strong>. Das Gerät
         holt sich seine Zugangsdaten dann selbst — kein Abtippen langer
         Schlüssel. Der Code ist <?= PAIR_TTL_MIN ?> Minuten gültig und genau
         einmal verwendbar; ein neuer macht einen vorher erzeugten ungültig.</p>
      <?php /* Der Tastenweg steht als Zusatz mit genannter Plattform: Er gilt
               nur für Fenix und Forerunner. Auf der Venu 3s gibt es weder START
               noch DOWN — der frühere Satz war für sie schon falsch, als sie
               dazukam. Die Tabelle je Uhr steht im Handbuch, Abschnitt 2.0. */ ?>
      <p class="feld-klein">Auf Garmin-Uhren: die Sync-Seite erreichst du vom
         Startbildschirm mit DOWN, das Koppeln startet mit gedrückt gehaltener
         START-Taste. Die Tastenwege der einzelnen Uhren stehen im Handbuch,
         Abschnitt 2.0.</p>
      <?php if ($pairCode): ?>
        <?php /* Der Code GROSS und für sich (E-P3-35): Er wird von einem
                 Bildschirm auf eine Uhr abgetippt, und zwar unter Zeitdruck. */ ?>
        <div class="codeblock">
          <p class="codeblock-titel">Kopplungscode</p>
          <p class="codeblock-wert"><?= e($pairCode) ?></p>
          <p class="feld-klein">Gültig bis
             <?= e(fmt_local(gmdate('Y-m-d H:i:s', time() + PAIR_TTL_MIN * 60), 'H:i')) ?> Uhr.
             Das Gerät erscheint nach der Kopplung unten in der Liste.</p>
        </div>
      <?php endif; ?>
      <?php /* Der Knopf steht im `.listen-form-fuss` wie jeder andere Knopf am
               Ende eines Formulars (Design.md 9.0). Vorher stand er blank im
               <form> und klebte am Absatz darüber (F-N1-O). */ ?>
      <form method="post" action="einstellungen.php?t=geraete">
        <?= csrf_field() ?><input type="hidden" name="action" value="pair_code">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Kopplungscode erzeugen', 'art' => 'primaer']) ?>
        </div>
      </form>
    <?php ui_karte_ende(); ?>

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
            $klein = geraet_bezeichnung($d['geraet_art'], $d['geraet_modell'], $d['geraet_teil'])
                   . ' · ' . ($aktiv ? 'aktiv' : 'deaktiviert')
                   . ' · zuletzt gesehen: '
                   . ($d['last_seen'] ? fmt_local($d['last_seen'], 'd.m.Y H:i') : 'nie');
            ?>
        <form method="post" id="f-dev-<?= $did ?>" class="nur-vorlesen"
              action="einstellungen.php?t=geraete">
          <?= csrf_field() ?><input type="hidden" name="action" value="toggle">
          <input type="hidden" name="id" value="<?= $did ?>">
        </form>
        <form method="post" id="f-devdel-<?= $did ?>" class="nur-vorlesen"
              action="einstellungen.php?t=geraete"
              data-confirm="Gerät „<?= e($d['label'] ?? $d['device_id']) ?>“ wirklich löschen? Bereits hochgeladene Daten bleiben erhalten.">
          <?= csrf_field() ?><input type="hidden" name="action" value="delete">
          <input type="hidden" name="id" value="<?= $did ?>">
        </form>
        <?php ui_zeile([
            'text'  => (string)($d['label'] ?? '') !== '' ? (string)$d['label'] : (string)$d['device_id'],
            'klein' => $klein,
            'plaketten' => ((int)$d['ist_neu']
                ? ui_plakette('neu seit ' . fmt_local($d['created_at'], 'd.m.Y'), ['ton' => 'orange'])
                : '')
                /* GEKUERZT (S6): Die volle 36-Zeichen-Kennung hat keine
                   Umbruchstelle und drueckte den Text daneben auf ein Wort je
                   Zeile zusammen — bei jedem frisch gekoppelten Geraet, dessen
                   Bezeichnung kurz ist. Begruendung und Form stehen an einer
                   Stelle: geraet_kennung_kurz(). */
                . ui_plakette(geraet_kennung_kurz((string)$d['device_id']), ['ton' => 'neutral']),
            'aktionen' => ui_zeilenaktionen([
                'titel' => (string)($d['label'] ?? $d['device_id']),
                'eintraege' => [
                    ['text' => 'Bearbeiten', 'symbol' => 'stift',
                     'href' => 'einstellungen.php?t=geraete&ed=' . $did],
                    ['text' => $aktiv ? 'Deaktivieren' : 'Aktivieren',
                     'symbol' => $aktiv ? 'schloss' : 'schloss-offen',
                     'art' => 'leise', 'form' => 'f-dev-' . $did],
                    ['text' => 'Löschen', 'symbol' => 'korb',
                     'art' => 'gefahr', 'form' => 'f-devdel-' . $did],
                ],
            ]),
        ]); ?>
      <?php endforeach; ?>

      <div class="listen-form">
        <h3 class="listen-form-titel"><?= $editDev ? 'Bezeichnung ändern' : 'Gerät von Hand anlegen' ?></h3>
        <?php if (!$editDev): ?>
          <p class="feld-hinweis">Die Alternative zum Koppeln: Geräte-ID und
             Schlüssel werden angezeigt und in der App des Geräts eingetragen.</p>
        <?php endif; ?>
        <form method="post" action="einstellungen.php?t=geraete">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="<?= $editDev ? 'rename' : 'add' ?>">
          <?php if ($editDev): ?>
            <input type="hidden" name="id" value="<?= (int)$editDev['id'] ?>">
          <?php endif; ?>
          <div class="listen-form-felder">
            <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'label', 'id' => 'devlabel',
                           'platzhalter' => 'z. B. Dienstuhr',
                           'wert' => (string)($editDev['label'] ?? ''),
                           'attr' => ' maxlength="120"']); ?>
          </div>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => $editDev ? 'Bezeichnung speichern' : 'Gerät anlegen',
                          'art' => 'primaer']) ?>
            <?php if ($editDev): ?>
              <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                            'href' => 'einstellungen.php?t=geraete']) ?>
            <?php endif; ?>
          </div>
        </form>
      </div>
    <?php ui_karte_ende(); ?>

    <?php /* ---- NAdoku für Android (S4/A1, E-S4-16) ---------------------
             Die App wird hier verteilt und nicht über einen App-Store. Die
             Karte zeigt, was auf dem Server LIEGT — Name, Größe, Datum und
             der gerechnete SHA-256; von Hand gepflegt wird nichts. Liegt
             nichts, erscheint die Karte gar nicht: Ein Leerzustand „noch
             keine App" wäre auf jeder Installation zu sehen, die keine
             Android-App verteilt, und sagte dort etwas Falsches.

             DER DOWNLOAD IST EINE NEUTRALE HANDLUNG, kein Primärknopf
             (Mockup A0, freigegeben): Die eine Haupthandlung dieses Reiters
             bleibt „Kopplungscode erzeugen". */ ?>
    <?php $apks = apk_liste(); if ($apks): ?>
      <?php ui_karte_start(['titel' => 'NAdoku für Android']); ?>
        <p class="feld-hinweis">Die Handy-App zeichnet die Spur des Dienstes
           auf und dokumentiert die Einsatzphasen — am Handy oder an einer
           verbundenen Wear-OS-Uhr. Sie wird hier verteilt, nicht über einen
           App-Store.</p>
        <?php foreach ($apks as $apk): ?>
          <?php ui_zeile([
              'text'  => $apk['datei'],
              'klein' => apk_groesse($apk['groesse'])
                       . ($apk['version'] !== null ? ' · Fassung ' . $apk['version'] : '')
                       . ' · Stand ' . fmt_local(gmdate('Y-m-d H:i:s', $apk['stand']), 'd.m.Y'),
              'aktionen' => '<div class="zeile-knoepfe">'
                  . '<a class="knopf knopf-neutral" href="apk.php?d='
                  . e(rawurlencode($apk['datei'])) . '"><span>Herunterladen</span></a>'
                  . '</div>',
          ]); ?>
          <p class="feld-klein">SHA-256:
             <code><?= e(apk_sha_lesbar($apk['sha256'])) ?></code> — wer der
             Seite nicht traut, rechnet die Prüfsumme der heruntergeladenen
             Datei nach.</p>
        <?php endforeach; ?>
        <p class="feld-klein">Beim ersten Öffnen fragt Android nach, ob
           Installationen aus dieser Quelle erlaubt sind — das ist bei einer
           Verteilung ohne App-Store der vorgesehene Weg.</p>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php if ($newKey): ?>
      <?php ui_karte_start(['titel' => 'Zugangsdaten des neuen Geräts']); ?>
        <p class="feld-hinweis">Beide Werte in den Einstellungen der App
           eintragen; als Server genügt die Domain.</p>
        <p class="feld-klein">Bei Garmin stehen diese Einstellungen in Garmin Connect.</p>
        <div class="codeblock">
          <p class="codeblock-titel">Geräte-ID</p>
          <p class="codeblock-wert"><?= e($newKey['device_id']) ?></p>
          <p class="codeblock-titel">API-Schlüssel</p>
          <p class="codeblock-wert"><?= e($newKey['api_key']) ?></p>
        </div>
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
<?php ui_seite_ende(); ?>
