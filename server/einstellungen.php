<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/validate_lib.php';   // WRAP_RE, Formatkennung
require_once __DIR__ . '/diensttag_lib.php';  // dt_bases(), dt_base_erlaubt(), Rollenkatalog

$tab = $_GET['t'] ?? 'profil';
/* „stammdaten" war bis Web 6.3.0 der Reiter, der alles trug. Er ist in zwei
 * zerlegt (siehe ui_settings_sidebar) — der alte Name bleibt als WEICHE
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
        if ($email === null) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben (höchstens 190 Zeichen).';
        } else {
            try {
                db()->prepare('UPDATE users SET name = ?, email = ? WHERE id = ?')
                    ->execute([$name !== '' ? $name : null, $email, $userId]);
                $userName = $name !== '' ? $name : null;
                $userEmail = $email;
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
                   . 'nichts weiter unternehmen, bevor eine Sicherung erstellt ist.';
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
                ->execute([$userId, $devId, password_hash($key, PASSWORD_DEFAULT), $label ?: null]);
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
ui_topbar('einstellungen');
?>

<div class="layout">
  <?php ui_settings_sidebar($tab); ?>

  <main class="page">
  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if ($tab === 'profil'): ?>
    <h1>Profil</h1>

    <form method="post" class="settings-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="profile">
      <label>Name <input type="text" name="name" maxlength="120"
        value="<?= e($userName ?? '') ?>" placeholder="wird in der Kopfleiste angezeigt"></label>
      <label>E-Mail-Adresse (Login) <input type="email" name="email" required
        value="<?= e($userEmail) ?>"></label>
      <button class="btn-primary">Profil speichern</button>
    </form>

    <h2>Passwort ändern</h2>
    <form method="post" class="settings-form" id="pwform">
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
      <label>Aktuelles Passwort <input type="password" name="old" id="pw_old" required autocomplete="current-password"></label>
      <label>Neues Passwort (mind. 10 Zeichen) <input type="password" name="new1" id="pw_new1" required minlength="10" autocomplete="new-password"></label>
      <span class="pwquality" id="pw_guete"></span>
      <label>Neues Passwort wiederholen <input type="password" name="new2" id="pw_new2" required autocomplete="new-password"></label>
      <button class="btn-primary">Passwort ändern</button>
      <span class="muted" id="pwstate"></span>
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

  <?php if ($tab === 'standorte'): ?>
    <h1>Standorte</h1>
    <p class="muted">Der Standort ist der <strong>Anker</strong> aller
       Vorbelegungen: Jedes Rettungsmittel, jede Zielklinik, jede
       Besatzungs-Vorbelegung und jede Bergwacht-Bereitschaft gehört zu genau
       einem Standort. Angelegt und ausgewählt werden sie hier — gepflegt wird
       ihr Inhalt unter
       <a href="einstellungen.php?t=rettungsmittel">Rettungsmittel</a>.</p>
    <p class="muted">★ markiert die Vorbelegung neuer Diensttage. Löschen
       entfernt nur den Listeneintrag — <strong>bereits dokumentierte Diensttage
       bleiben unverändert</strong>: Sie haben Art, Rollen, Fähigkeiten und
       Bezeichnungen beim Anlegen eingefroren.</p>

    <details class="stammblock" id="standorte" open>
      <summary>Eigene Standorte</summary>
      <table class="data">
        <tbody>
        <?php if (!$eigene): ?><tr><td colspan="3" class="muted">Noch keine eigenen Standorte.</td></tr><?php endif; ?>
        <?php foreach ($eigene as $b):
              $dup = stammdaten_dup_global('bases', 'name', $b['name']);
              $anz = $sdAnzahl((int)$b['id']); ?>
          <tr>
            <td><?= e($b['name']) ?>
              <?php if ($b['lat'] !== null && $b['lon'] !== null): ?>
                <br><span class="muted small"><?= e((string)$b['lat']) ?>, <?= e((string)$b['lon']) ?></span>
              <?php endif; ?>
              <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
            </td>
            <td class="c-stern"><?= (int)$b['id'] === $DEF_BASE_ID
                ? '<span class="sternmarke" title="Vorbelegung neuer Diensttage"'
                  . ' aria-label="Vorbelegung neuer Diensttage">★</span>' : '' ?></td>
            <td class="th-act"><div class="rowactions">
              <?php if ((int)$b['id'] !== $DEF_BASE_ID): ?>
                <form method="post" action="einstellungen.php?t=standorte#standorte">
                  <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
                  <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                  <button class="btn-plain btn-stern" title="Als Vorbelegung neuer Diensttage setzen">★ Standard</button>
                </form>
              <?php endif; ?>
              <a class="btn-yellow" href="einstellungen.php?t=standorte&amp;eb=<?= (int)$b['id'] ?>#standorte">Bearbeiten</a>
              <?php /* Die Rückfrage BEZIFFERT, was mitgeht (Konzept 4.2). Ein
                       „Standort löschen?" allein verschwieg, dass Rettungsmittel,
                       Zielkliniken und Besatzungen daran hängen. */ ?>
              <form method="post" action="einstellungen.php?t=standorte#standorte"
                    data-confirm="Standort „<?= e($b['name']) ?>“ löschen? <?= $anz > 0
                        ? ($anz === 1 ? 'Ein eigener Stammdatensatz' : $anz . ' eigene Stammdatensätze')
                          . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht) werden mitgelöscht.'
                        : 'Es hängen keine eigenen Stammdaten daran.' ?> Bereits dokumentierte Diensttage bleiben unverändert.">
                <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn-red">Löschen</button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php /* EINGABE IM EIGENEN RAHMEN (Web 7.0.0, zweiter Anlauf).
               Erster Anlauf war `.inline-form` mit `align-items:flex-start`.
               Damit war die Überhöhe weg — die Ausrichtung aber immer noch
               falsch: Das Namensfeld stand ohne Beschriftung ganz oben, das
               Suchfeld daneben trägt eine („Lage des Standorts") und rutschte
               dadurch eine Zeile tiefer. Zwei Eingabefelder derselben Zeile auf
               zwei Höhen — das sah nach Fehler aus, weil es einer war.

               Jetzt dieselbe Form wie beim Rettungsmittel: Name und
               Schaltfläche in EINER Zeile, die freiwillige Ortsangabe als
               eigener Block darunter. Damit stellt sich die Frage nach der
               Ausrichtung gar nicht mehr, und die Zusammengehörigkeit ist zu
               sehen. */ ?>
      <div class="neu-form">
        <h4><?= $editBase ? 'Standort bearbeiten' : 'Standort hinzufügen' ?></h4>
        <form method="post" action="einstellungen.php?t=standorte#standorte">
          <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
          <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
          <div class="neu-zeile">
            <input type="text" name="name" id="sdbaseaddr" class="focus-target" maxlength="120" required
                   placeholder="z. B. Standort Kempten" value="<?= e($editBase['name'] ?? '') ?>">
            <button class="btn-primary"><?= $editBase ? 'Änderung speichern' : 'Hinzufügen' ?></button>
            <?php if ($editBase): ?><a class="btn-red" href="einstellungen.php?t=standorte">Abbrechen</a><?php endif; ?>
          </div>
          <?php /* Koordinaten optional (E37/E39). Sie sind die Quelle des
                   Abfahrtorts „Standort" und werden beim Anlegen eines Diensttags
                   eingefroren (E8). Seit Web 6.1.0 mit Adresssuche — dieselbe
                   Komponente wie am Einsatzort (assets/ortsfeld.js), hier aber mit
                   GETRENNTEM Suchfeld: „Standort Kempten" ist keine Adresse, und
                   eine Suche im Namensfeld schriebe den Namen weg. */
                $ORTSFELDER[] = 'sdbase'; ?>
          <div class="neu-feld">
            <?php ui_ortsfeld([
                    'praefix' => 'sdbase', 'feld' => false, 'such' => true,
                    'klasse' => 'loc-inline',
                    'such_hinweis' => 'Lage des Standorts (optional)',
                    'lat_name' => 'lat', 'lon_name' => 'lon',
                    'lat' => (string)($editBase['lat'] ?? ''),
                    'lon' => (string)($editBase['lon'] ?? ''),
                ]); ?>
          </div>
        </form>
      </div>
    </details>

    <?php /* „Vordefinierte Standorte" statt „Zentrale Standorte auswählen"
             (Web 7.0.0). „Zentral" beschrieb die Verwaltung, nicht den Nutzen;
             was man hier vor sich hat, sind fertige Standorte, die man
             übernehmen kann. */ ?>
    <details class="stammblock" id="zentrale">
      <summary>Vordefinierte Standorte</summary>
      <p class="muted">Vordefinierte Standorte legt eine Administratorin an. Sie
         stehen allen zur Verfügung, erscheinen aber erst dann in den
         Auswahllisten, wenn du sie hier auswählst (E16). Abwählen entfernt keine
         Daten — bereits dokumentierte Diensttage bleiben unverändert.</p>
      <table class="data">
        <tbody>
        <?php if (!$zentral): ?><tr><td colspan="3" class="muted">Keine vordefinierten Standorte hinterlegt.</td></tr><?php endif; ?>
        <?php foreach ($zentral as $z): $an = !empty($z['gewaehlt']); ?>
          <tr>
            <td><?= e($z['name']) ?> <span class="badge-central">systemweit</span>
              <?php if ($z['lat'] !== null && $z['lon'] !== null): ?>
                <br><span class="muted small"><?= e((string)$z['lat']) ?>, <?= e((string)$z['lon']) ?></span>
              <?php endif; ?>
            </td>
            <td class="c-stern"><?= (int)$z['id'] === $DEF_BASE_ID
                ? '<span class="sternmarke" title="Vorbelegung neuer Diensttage"'
                  . ' aria-label="Vorbelegung neuer Diensttage">★</span>' : '' ?></td>
            <td class="th-act"><div class="rowactions">
              <?php /* ★ AUCH FÜR SYSTEMWEITE STANDORTE (Web 7.0.0). Die
                       Schaltfläche stand nur bei den eigenen — ein Konto, das
                       ausschließlich mit vordefinierten Standorten arbeitet
                       (der Regelfall an einer Station), konnte damit gar keine
                       Vorbelegung setzen. Die Serverseite ließ es längst zu:
                       `base_default` prüft mit dt_base_erlaubt(), und das
                       erlaubt eigene UND ausgewählte zentrale. Es fehlte allein
                       der Knopf.
                       Voraussetzung bleibt die Auswahl: Was nicht in den
                       Auswahllisten steht, kann auch keine Vorbelegung sein. */ ?>
              <?php if ($an && (int)$z['id'] !== $DEF_BASE_ID): ?>
                <form method="post" action="einstellungen.php?t=standorte#zentrale">
                  <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
                  <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                  <button class="btn-plain btn-stern" title="Als Vorbelegung neuer Diensttage setzen">★ Standard</button>
                </form>
              <?php endif; ?>
              <form method="post" action="einstellungen.php?t=standorte#zentrale">
                <?= csrf_field() ?><input type="hidden" name="action" value="ub_toggle">
                <input type="hidden" name="id" value="<?= (int)$z['id'] ?>">
                <input type="hidden" name="an" value="<?= $an ? '0' : '1' ?>">
                <button class="<?= $an ? 'btn-red' : 'btn-primary' ?>"><?= $an ? 'Abwählen' : 'Auswählen' ?></button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
    </details>

    <?php if (!$sdBases): ?>
      <p class="alert alert-info">Noch kein Standort verfügbar. Lege oben einen
         eigenen an oder wähle einen vordefinierten aus — ohne Standort gibt es
         keine Rettungsmittel, keine Besatzungs-Vorbelegungen und keine
         Zielkliniken.</p>
    <?php endif; ?>

  <?php else: ?>
    <?php /* ---- Reiter „Rettungsmittel" ------------------------------------
             Alles, was an einem ausgewählten Standort hängt. Ein Block je
             Standort, darin je Datenart ein eigener. */ ?>
    <h1>Rettungsmittel</h1>
    <p class="muted">Was an den ausgewählten Standorten hängt: Rettungsmittel und
       ihre Besatzungsrollen, Besatzungs-Vorbelegungen, Zielkliniken, weitere
       Rettungsmittel und Bergwacht-Bereitschaften. Die Standorte selbst stehen
       unter <a href="einstellungen.php?t=standorte">Standorte</a>.</p>
    <p class="muted">Löschen entfernt nur den Listeneintrag — <strong>bereits
       dokumentierte Diensttage bleiben unverändert</strong>. Sie haben Art,
       Rollen, Fähigkeiten und Bezeichnungen beim Anlegen eingefroren; Änderungen
       hier wirken ausschließlich auf neue Diensttage. ★ markiert die
       Vorbelegung neuer Diensttage. „systemweit“ markiert vom Admin gepflegte
       Einträge — diese stehen automatisch zur Verfügung und lassen sich hier
       nicht bearbeiten oder löschen.</p>

    <?php if (!$sdBases): ?>
      <p class="alert alert-info">Noch kein Standort verfügbar. Bitte zuerst unter
         <a href="einstellungen.php?t=standorte">Standorte</a> einen eigenen
         anlegen oder einen vordefinierten auswählen — ohne Standort gibt es
         keine Rettungsmittel, keine Besatzungs-Vorbelegungen und keine
         Zielkliniken.</p>
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
      <details class="stammblock" id="<?= e($anker) ?>">
        <summary><?= e($b['name']) ?><?= !empty($b['zentral']) ? ' <span class="badge-central">systemweit</span>' : '' ?></summary>

        <details class="stammunter" id="<?= e($anker) ?>-veh">
          <summary>Rettungsmittel<span class="stammzahl"><?= count($vehListe) ?></span></summary>
          <p class="muted">Die Art entscheidet über Besatzungsrollen und die im
             Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht) gibt
             es nur luftgebunden.</p>
          <table class="data">
            <tbody>
            <?php if (!$vehListe): ?><tr><td colspan="3" class="muted">Noch keine Rettungsmittel an diesem Standort.</td></tr><?php endif; ?>
            <?php foreach ($vehListe as $v):
                  $vz = $istZentral($v); $vid = (int)$v['id'];
                  $sym = dt_art_symbol((string)$v['kind']);
                  $rollenTxt = array_map('crew_role_label', $vehRollen[$vid] ?? []);
                  $capsTxt = array_map(static fn(string $c): string => VEHICLE_CAPABILITIES[$c] ?? $c,
                                       $vehCaps[$vid] ?? []); ?>
              <tr>
                <td><span class="artzeichen" title="<?= e($sym['text']) ?>"
                          aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
                  <?= e($v['name']) ?>
                  <?php /* DIE ART STEHT NICHT MEHR AUSGESCHRIEBEN DARUNTER
                           (Web 7.0.0). Das Symbol davor sagt sie bereits, und es
                           trägt seine Textalternative in title/aria-label — die
                           Auskunft hängt also nicht an der Grafik. Übrig bleibt,
                           was man dem Symbol nicht ansieht: die Rollen und die
                           Fähigkeiten. */ ?>
                  <br><span class="muted small"><?php
                    echo $rollenTxt ? e(implode(', ', $rollenTxt)) : 'keine Rollen';
                    echo $capsTxt ? ' · ' . e(implode(', ', $capsTxt)) : ''; ?></span>
                </td>
                <td class="c-stern"><?= $vid === $DEF_VEH_ID
                    ? '<span class="sternmarke" title="Vorbelegung neuer Diensttage"'
                      . ' aria-label="Vorbelegung neuer Diensttage">★</span>' : '' ?></td>
                <td class="th-act"><div class="rowactions">
                  <?php if ($vz): ?><span class="badge-central">systemweit</span><?php endif; ?>
                  <?php if ($vid !== $DEF_VEH_ID): ?>
                    <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-veh">
                      <?= csrf_field() ?><input type="hidden" name="action" value="veh_default">
                      <input type="hidden" name="id" value="<?= $vid ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-plain btn-stern" title="Als Vorbelegung neuer Diensttage setzen">★ Standard</button>
                    </form>
                  <?php endif; ?>
                  <?php if (!$vz): ?>
                    <a class="btn-yellow" href="einstellungen.php?t=rettungsmittel&amp;ev=<?= $vid ?>#<?= e($anker) ?>-veh">Bearbeiten</a>
                    <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-veh"
                          data-confirm="Rettungsmittel löschen? Bereits dokumentierte Diensttage bleiben unverändert.">
                      <?= csrf_field() ?><input type="hidden" name="action" value="veh_del">
                      <input type="hidden" name="id" value="<?= $vid ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-red">Löschen</button>
                    </form>
                  <?php endif; ?>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
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
          <div class="neu-form">
            <h4><?= $evHier ? 'Rettungsmittel bearbeiten' : 'Rettungsmittel hinzufügen' ?></h4>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-veh" class="ac-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="veh_save">
              <input type="hidden" name="id" value="<?= $evHier ? (int)$evHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <div class="neu-zeile">
                <input type="text" name="name" maxlength="64" required placeholder="z. B. NEF Kempten 1"
                       value="<?= e($evHier['name'] ?? '') ?>">
                <button class="btn-primary"><?= $evHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
                <?php if ($evHier): ?><a class="btn-red" href="einstellungen.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
              </div>
              <?php /* Die Art steuert, welche Rollen und Fähigkeiten überhaupt
                       angehakt werden können. Das Umschalten passiert im Browser
                       (Skript unten); der Server filtert unabhängig davon noch
                       einmal — ein Haken, den die Oberfläche nicht anbietet, darf
                       auch über eine gesendete Anfrage nicht hereinkommen. */ ?>
              <div class="neu-feld">
                <span class="neu-titel">Art</span>
                <span class="vehkind">
                  <label><input type="radio" name="kind" value="air" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'air') ? 'checked' : '' ?>> luftgebunden</label>
                  <label><input type="radio" name="kind" value="ground" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'ground') ? 'checked' : '' ?>> bodengebunden</label>
                </span>
              </div>
              <div class="neu-feld rollen-zeile">
                <span class="neu-titel">Besatzungsrollen <span class="muted small">(optional)</span></span>
                <span class="acroles">
                  <?php foreach (CREW_ROLES as $rc => $rr): ?>
                    <label class="rollehaken" data-kind="<?= e($rr['kind']) ?>">
                      <input type="checkbox" name="roles[]" value="<?= e($rc) ?>"
                             <?= in_array($rc, $evRollen, true) ? 'checked' : '' ?>>
                      <?= e($rr['label']) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
              <div class="neu-feld vehcaps-zeile">
                <span class="neu-titel">Fähigkeiten <span class="muted small">(nur luftgebunden)</span></span>
                <span class="acroles vehcaps">
                  <?php foreach (VEHICLE_CAPABILITIES as $ck => $cl): ?>
                    <label><input type="checkbox" name="caps[]" value="<?= e($ck) ?>"
                           <?= in_array($ck, $evCaps, true) ? 'checked' : '' ?>>
                      <?= e($cl) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
            </form>
          </div>
        </details>

        <?php /* BESATZUNG — nur die Rollen, die es an diesem Standort gibt. */ ?>
        <details class="stammunter" id="<?= e($anker) ?>-crew">
          <summary>Besatzung<span class="stammzahl"><?= count($sdCrew[$bid] ?? []) ?></span></summary>
          <p class="muted">Vorschläge für die Besatzungsfelder, je Rolle. Freitext
             bleibt überall möglich — wer aushilft, muss nicht erst hier eingetragen
             werden.</p>
          <?php if (!$rollenHier): ?>
            <p class="muted">Noch keine Rolle an diesem Standort. Rollen entstehen
               am Rettungsmittel: Trage oben eines ein und hake an, welche Rollen
               es führt.</p>
          <?php endif; ?>
          <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
            <h4><?= e($rr['label']) ?></h4>
            <table class="data">
              <tbody>
              <?php $any = false;
                    foreach (($sdCrew[$bid] ?? []) as $c):
                        if ($c['role_code'] !== $rk) { continue; }
                        $any = true; $cz = $istZentral($c);
                        $dup = !$cz && stammdaten_dup_global('crew_presets', 'name', $c['name'], 'role_code', $rk); ?>
                <tr>
                  <td><?= e($c['name']) ?>
                    <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
                  </td>
                  <td class="th-act"><div class="rowactions">
                    <?php if ($cz): ?><span class="badge-central">systemweit</span><?php endif; ?>
                    <?php if (!$cz): ?>
                      <a class="btn-yellow" href="einstellungen.php?t=rettungsmittel&amp;ec=<?= (int)$c['id'] ?>#<?= e($anker) ?>-crew">Bearbeiten</a>
                      <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-crew"
                            data-confirm="Eintrag löschen?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="crew_del">
                        <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                        <input type="hidden" name="base_id" value="<?= $bid ?>">
                        <button class="btn-red">Löschen</button>
                      </form>
                    <?php endif; ?>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$any): ?><tr><td class="muted">Noch keine Einträge.</td><td></td></tr><?php endif; ?>
              </tbody>
            </table>
            <?php $ecHier = ($editCrew && (int)$editCrew['base_id'] === $bid
                             && $editCrew['role_code'] === $rk) ? $editCrew : null; ?>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-crew" class="inline-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="crew_save">
              <input type="hidden" name="role" value="<?= e($rk) ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <input type="hidden" name="id" value="<?= $ecHier ? (int)$ecHier['id'] : 0 ?>">
              <input type="text" name="name" placeholder="Name" maxlength="120" required
                     value="<?= e($ecHier['name'] ?? '') ?>">
              <button class="btn-primary"><?= $ecHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
              <?php if ($ecHier): ?><a class="btn-red" href="einstellungen.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
            </form>
          <?php endforeach; ?>
        </details>

        <details class="stammunter" id="<?= e($anker) ?>-td">
          <summary>Zielkliniken<span class="stammzahl"><?= count($sdTd[$bid] ?? []) ?></span></summary>
          <p class="muted">Vorschläge für das Feld „Transportziel“ im Einsatz.
             Koordinaten sind freiwillig; ohne sie entsteht lediglich kein Pin auf
             der Karte.</p>
          <table class="data">
            <tbody>
            <?php if (!($sdTd[$bid] ?? [])): ?><tr><td class="muted">Noch keine Zielkliniken.</td><td></td></tr><?php endif; ?>
            <?php foreach (($sdTd[$bid] ?? []) as $t): $tz = $istZentral($t);
                  $dup = !$tz && stammdaten_dup_global('transport_dests', 'name', $t['name']); ?>
              <tr>
                <td><?= e($t['name']) ?>
                  <?php if ($t['lat'] !== null && $t['lon'] !== null): ?>
                    <br><span class="muted small"><?= e((string)$t['lat']) ?>, <?= e((string)$t['lon']) ?></span>
                  <?php endif; ?>
                  <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
                </td>
                <td class="th-act"><div class="rowactions">
                  <?php if ($tz): ?><span class="badge-central">systemweit</span><?php endif; ?>
                  <?php if (!$tz): ?>
                    <a class="btn-yellow" href="einstellungen.php?t=rettungsmittel&amp;et=<?= (int)$t['id'] ?>#<?= e($anker) ?>-td">Bearbeiten</a>
                    <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-td"
                          data-confirm="Zielklinik löschen?">
                      <?= csrf_field() ?><input type="hidden" name="action" value="td_del">
                      <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-red">Löschen</button>
                    </form>
                  <?php endif; ?>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php $etHier = ($editTd && (int)$editTd['base_id'] === $bid) ? $editTd : null; ?>
          <?php /* Gleiche Form wie beim Standort: Name und Schaltfläche in einer
                   Zeile, die freiwillige Ortsangabe darunter. */ ?>
          <div class="neu-form">
            <h4><?= $etHier ? 'Zielklinik bearbeiten' : 'Zielklinik hinzufügen' ?></h4>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-td">
              <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
              <input type="hidden" name="id" value="<?= $etHier ? (int)$etHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <?php /* Das Präfix trägt die Standortkennung: Dieses Formular steht
                       EINMAL JE STANDORT auf der Seite, und zwei Ortsfelder mit
                       denselben Element-Kennungen fänden beide dasselbe Feld. */
                    $tdPraefix = 'sdtd' . $bid; $ORTSFELDER[] = $tdPraefix; ?>
              <div class="neu-zeile">
                <input type="text" name="name" id="<?= e($tdPraefix) ?>addr" maxlength="190" required
                       placeholder="z. B. Klinikum Kempten" value="<?= e($etHier['name'] ?? '') ?>">
                <button class="btn-primary"><?= $etHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
                <?php if ($etHier): ?><a class="btn-red" href="einstellungen.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
              </div>
              <div class="neu-feld">
                <?php ui_ortsfeld([
                        'praefix' => $tdPraefix, 'feld' => false, 'such' => true,
                        'klasse' => 'loc-inline',
                        'such_hinweis' => 'Lage der Zielklinik (optional)',
                        'lat_name' => 'lat', 'lon_name' => 'lon',
                        'lat' => (string)($etHier['lat'] ?? ''),
                        'lon' => (string)($etHier['lon'] ?? ''),
                    ]); ?>
              </div>
            </form>
          </div>
        </details>

        <details class="stammunter" id="<?= e($anker) ?>-res">
          <summary>Weitere Rettungsmittel<span class="stammzahl"><?= count($sdRes[$bid] ?? []) ?></span></summary>
          <p class="muted">Vorschläge für das Feld „Weitere Rettungsmittel“ im
             Einsatz (RTW, NEF, weitere Hubschrauber …).</p>
          <table class="data">
            <tbody>
            <?php if (!($sdRes[$bid] ?? [])): ?><tr><td class="muted">Noch keine Einträge.</td><td></td></tr><?php endif; ?>
            <?php foreach (($sdRes[$bid] ?? []) as $r): $rz = $istZentral($r);
                  $dup = !$rz && stammdaten_dup_global('resources', 'name', $r['name']); ?>
              <tr>
                <td><?= e($r['name']) ?>
                  <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
                </td>
                <td class="th-act"><div class="rowactions">
                  <?php if ($rz): ?><span class="badge-central">systemweit</span><?php endif; ?>
                  <?php if (!$rz): ?>
                    <a class="btn-yellow" href="einstellungen.php?t=rettungsmittel&amp;er=<?= (int)$r['id'] ?>#<?= e($anker) ?>-res">Bearbeiten</a>
                    <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-res"
                          data-confirm="Eintrag löschen?">
                      <?= csrf_field() ?><input type="hidden" name="action" value="res_del">
                      <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-red">Löschen</button>
                    </form>
                  <?php endif; ?>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php $erHier = ($editRes && (int)$editRes['base_id'] === $bid) ? $editRes : null; ?>
          <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-res" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="res_save">
            <input type="hidden" name="id" value="<?= $erHier ? (int)$erHier['id'] : 0 ?>">
            <input type="hidden" name="base_id" value="<?= $bid ?>">
            <input type="text" name="name" maxlength="120" required
                   placeholder="z. B. RTW Kempten" value="<?= e($erHier['name'] ?? '') ?>">
            <button class="btn-primary"><?= $erHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
            <?php if ($erHier): ?><a class="btn-red" href="einstellungen.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
          </form>
        </details>

        <?php if ($hatLuft): ?>
          <details class="stammunter" id="<?= e($anker) ?>-bw">
            <summary>Bergwacht<span class="stammzahl"><?= count($sdBw[$bid] ?? []) ?></span></summary>
            <p class="muted">Bereitschaften für das Feld „Bergwacht“ im Einsatz.
               Der Block erscheint, weil an diesem Standort ein luftgebundenes
               Rettungsmittel steht — die Fähigkeit kommt nur dort vor.</p>
            <table class="data">
              <tbody>
              <?php if (!($sdBw[$bid] ?? [])): ?><tr><td class="muted">Noch keine Bereitschaften.</td><td></td></tr><?php endif; ?>
              <?php foreach (($sdBw[$bid] ?? []) as $w): $wz = $istZentral($w);
                    $dup = !$wz && stammdaten_dup_global('bw_units', 'name', $w['name']); ?>
                <tr>
                  <td><?= e($w['name']) ?>
                    <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
                  </td>
                  <td class="th-act"><div class="rowactions">
                    <?php if ($wz): ?><span class="badge-central">systemweit</span><?php endif; ?>
                    <?php if (!$wz): ?>
                      <a class="btn-yellow" href="einstellungen.php?t=rettungsmittel&amp;ew=<?= (int)$w['id'] ?>#<?= e($anker) ?>-bw">Bearbeiten</a>
                      <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-bw"
                            data-confirm="Bereitschaft löschen?">
                        <?= csrf_field() ?><input type="hidden" name="action" value="bw_del">
                        <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                        <input type="hidden" name="base_id" value="<?= $bid ?>">
                        <button class="btn-red">Löschen</button>
                      </form>
                    <?php endif; ?>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php $ewHier = ($editBw && (int)$editBw['base_id'] === $bid) ? $editBw : null; ?>
            <form method="post" action="einstellungen.php?t=rettungsmittel#<?= e($anker) ?>-bw" class="inline-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="bw_save">
              <input type="hidden" name="id" value="<?= $ewHier ? (int)$ewHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <input type="text" name="name" maxlength="120" required
                     placeholder="z. B. Bereitschaft Oberstdorf" value="<?= e($ewHier['name'] ?? '') ?>">
              <button class="btn-primary"><?= $ewHier ? 'Änderung speichern' : 'Bereitschaft hinzufügen' ?></button>
              <?php if ($ewHier): ?><a class="btn-red" href="einstellungen.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
            </form>
          </details>
        <?php endif; ?>
      </details>
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
    <h1>Backup</h1>
    <p class="muted">Sichert <strong>alle</strong> deine Daten (Einsätze mit Phasen,
       Reanimationen und Tracks, Ruhesegmente, Diensttage, Standortdaten und die
       geschützten Angaben) in eine einzelne Datei (<code>.edbak</code>), verschlüsselt
       mit einem Passwort deiner Wahl (AES-256-GCM). Ver- und Entschlüsselung passieren
       vollständig <strong>in deinem Browser</strong> — der Server sieht die Inhalte nie.
       <?php /* Der Satz „Format-Beschreibung: docs/Backup-Format.md" stand hier
                bis Web 5.7.0 (A6.1). Der Pfad ist für Nutzende nicht
                erreichbar — er zeigt in das Quellverzeichnis, nicht auf den
                Server. Die Datei bleibt bestehen, die Verweise darauf im Code
                (backup_lib.php) ebenfalls; sie richten sich an
                Entwicklerinnen. */ ?>
       Dadurch lässt sich ein Backup auch in ein <strong>anderes Konto</strong>
       einspielen.</p>

    <div id="lockwarn" class="alert" hidden>Die geschützten Angaben lassen sich gerade
      nicht entschlüsseln — die Verschlüsselung ist in dieser Sitzung gesperrt.
      <button type="button" class="btn-plain unlockbtn" id="lockwarn_unlock">Entsperren</button></div>

    <h2>Exportieren</h2>
    <div class="settings-form">
      <?php /* WAS IN DER DATEI STEHT, GEHOERT VOR DIE PASSWORTWAHL (M2-03).
               Vorher stand hier "ohne dieses Passwort ist die Datei wertlos" —
               richtig, aber es beantwortet die falsche Frage. Wer ein Passwort
               waehlt, muss wissen, WAS er damit schuetzt. */ ?>
      <p class="alert alert-warn">In dieser Datei stehen <strong>alle geschützten
         Angaben im Klartext</strong> — Namen, Geburtsdaten, Diagnosen,
         Einsatzorte. Zwischen ihnen und jedem, der die Datei in die Hand
         bekommt, steht <strong>nur dieses Passwort</strong>. Es wird nirgends
         gespeichert und lässt sich nicht zurücksetzen.</p>
      <label class="check"><input type="checkbox" id="bpwkonto">
        Mein Kontopasswort verwenden</label>
      <p class="muted small" id="bpwkontohinweis" hidden>Das Kontopasswort schützt
         dieselben Angaben bereits in der Datenbank — die Datei wird dadurch
         nicht schwächer geschützt, und es ist ein Passwort weniger zu
         verwahren. <strong>Nicht</strong> geeignet, wenn die Datei an jemand
         anderen gehen soll.</p>
      <label>Backup-Passwort (mind. 10 Zeichen)
        <input type="password" id="bpw1" minlength="10" autocomplete="new-password"></label>
      <span class="pwquality" id="bpwguete"></span>
      <label id="bpw2label">Passwort wiederholen
        <input type="password" id="bpw2" autocomplete="new-password"></label>
      <button class="btn-primary" id="expbtn">Backup erstellen</button>
      <p class="muted" id="expstate" style="min-height:1.3em"></p>
    </div>

    <h2>Importieren</h2>
    <p class="muted">Spielt ein Backup in <strong>dieses</strong> Konto zurück. Bereits
       vorhandene Einsätze, Tage und Stammdaten bleiben unangetastet (Erkennung über
       interne Referenzen) — der Import ergänzt nur Fehlendes und ist gefahrlos
       wiederholbar.</p>
    <div class="settings-form" id="impform">
      <label>Backup-Datei (.edbak)
        <input type="file" name="bfile" id="bfile" accept=".edbak" required></label>
      <label>Backup-Passwort
        <input type="password" id="ipw" autocomplete="off"></label>
      <button class="btn-primary" id="impbtn">Backup importieren</button>
      <?php /* Herkunft der geoeffneten Datei (M5-13). Steht ueber der
               Statuszeile, weil es die Frage beantwortet, die man VOR dem
               Einspielen hat: Ist das die richtige Datei? */ ?>
      <p class="muted" id="impherkunft" hidden></p>
      <p class="muted" id="impstate" style="min-height:1.3em"></p>
    </div>

    <?php /* ---- Von der Administration freigegebene Sicherung (A8.6) -------
             Erscheint NUR, wenn tatsächlich eine Freigabe vorliegt. Ein
             dauerhaft sichtbarer, meist leerer Block wäre eine Frage, die man
             sich bei jedem Besuch neu stellt.

             Der Fall dahinter: Das Konto wurde gelöscht und neu aufgesetzt.
             Die geschützten Angaben der alten Sicherung hängen am ALTEN
             Inhaltsschlüssel; nur der Wiederherstellungsschlüssel öffnet ihn,
             und der liegt ausschliesslich hier. Deshalb kann Administration
             ein solches Paket nicht einspielen — sie gibt es frei, und das
             Umschlüsseln passiert in diesem Browser. */ ?>
    <div id="freigabebox" hidden>
      <hr class="sep">
      <h2>Für dich freigegebene Sicherung</h2>
      <p class="muted" id="freigabeinfo"></p>
      <div class="settings-form">
        <label id="freigabecodelabel">Wiederherstellungsschlüssel
          <input type="text" id="freigabecode" autocomplete="off"
                 placeholder="XXXX-XXXX-XXXX-XXXX"></label>
        <p class="muted small">Das ist der Schlüssel, der bei der Ersteinrichtung
           einmalig angezeigt wurde — nicht das Kontopasswort. Ohne ihn lassen sich
           die geschützten Angaben dieser Sicherung von niemandem mehr öffnen.</p>
        <button class="btn-primary" id="freigabebtn">Sicherung einspielen</button>
        <p class="muted" id="freigabestate" style="min-height:1.3em"></p>
      </div>
      <p class="muted small">Das Einspielen <strong>ergänzt</strong>: Vorhandene
         Einträge bleiben unverändert, es kommt nur hinzu, was fehlt.</p>
    </div>

    <?php /* Ruestzeug der Verschluesselung (Baustein ui_krypto_bootstrap()),
             dazu pwquality.js fuer die Guetepruefung des Backup-Passworts
             (B9, M2-03). */ ?>
    <?php ui_krypto_bootstrap(['keycheck' => true, 'csrf' => true,
                               'guete' => true, 'einzug' => '    ']); ?>
    <?php /* patient.js liefert die gemeinsame Entschluesselungsschleife
             (Baustein B8), die der Sicherungslauf seit Web 4.6.0 benutzt. */ ?>
    <script src="<?= asset('assets/patient.js') ?>"></script>
    <script>
    // Eigenes Konto — nur fuer den Vergleich mit der Herkunft der Datei (M5-13).
    const KONTO_MAIL = <?= json_encode($userEmail) ?>;
    const KONTO_NAME = <?= json_encode($userName) ?>;
    const expState = document.getElementById('expstate');
    const impState = document.getElementById('impstate');

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
     * Eine Sicherung ist für einen selbst. Eine Exportdatei ist ausdrücklich
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
      bpw1.parentElement.firstChild.textContent = an
        ? 'Kontopasswort'
        : 'Backup-Passwort (mind. 10 Zeichen)';
      bpwGuete.hidden = an;
      bpw1.value = '';
      expState.textContent = '';
    });

    /** Prüft das eingegebene Passwort und liefert es zurück — oder null. */
    async function backupPasswort() {
      const pw = bpw1.value;
      if (bpwKonto.checked) {
        if (pw === '') { expState.textContent = 'Bitte das Kontopasswort eingeben.'; return null; }
        if (!PAT_WRAP) {
          expState.textContent = 'Für dieses Konto liegt keine Schlüsselhülle vor — '
                               + 'bitte ein eigenes Backup-Passwort wählen.';
          return null;
        }
        expState.textContent = 'Kontopasswort wird geprüft…';
        try {
          const k = await EdCrypto.deriveKeys(pw, KDF_SALT, KDF_ITER);
          await EdCrypto.decrypt(k.dataKeyHex, PAT_WRAP);
        } catch (e) {
          expState.textContent = 'Das ist nicht dein Kontopasswort. Es wurde keine '
                               + 'Datei erzeugt.';
          return null;
        }
        return pw;
      }
      const guete = EdPwQuality.pruefe(pw);
      if (!guete.erlaubt) { expState.textContent = guete.meldung; return null; }
      if (pw !== bpw2.value) { expState.textContent = 'Die Passwörter stimmen nicht überein.'; return null; }
      return pw;
    }

    // ---- Export: Daten holen, entschlüsseln, versiegeln, herunterladen ----
    document.getElementById('expbtn').addEventListener('click', async () => {
      const pw = await backupPasswort();
      if (pw === null) { return; }
      const key = await ck();
      if (!key) { expState.textContent = 'Entschlüsselung gesperrt — siehe Hinweis oben.'; return; }
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
        const res = await fetch('api/backup_data.php');
        if (!res.ok) {
          let grund = 'HTTP ' + res.status;
          try { const j = await res.json(); grund = j.meldung || j.error || grund; } catch (e2) {}
          throw new Error('Die Daten konnten nicht geladen werden (' + grund + '). '
                        + 'Es wurde KEINE Datei erzeugt.');
        }
        const data = await res.json();

        /* Eine FEHLENDE Einsatzliste ist kein leerer Bestand, sondern ein
         * Fehler: Der Server liefert das Feld immer, notfalls als leere
         * Liste. Fehlt es, stimmt etwas mit der Antwort nicht. */
        if (!Array.isArray(data.missions)) {
          throw new Error('Die Antwort des Servers ist unvollständig. Es wurde KEINE Datei erzeugt.');
        }
        if (!data.missions.length && !(data.rest_segments || []).length
            && !(data.days || []).length) {
          expState.textContent = 'Es sind keine Daten vorhanden, die gesichert werden könnten. '
                               + 'Es wurde keine Datei erzeugt.';
          return;
        }

        expState.textContent = 'Geschützte Angaben werden entschlüsselt…';
        /* Entschlüsseln und zählen an EINER Stelle (M6-06, Baustein B8) — was
         * mit dem Ergebnis geschieht, bleibt Sache dieser Seite, denn hier ist
         * es etwas anderes als auf einer Anzeigeseite. */
        const zahl = await EdPat.entschluessleListe(data.missions || [], key);
        const n = zahl.ok, unlesbar = zahl.unlesbar;
        for (const m of (data.missions || [])) {
          if (m._patState === 'ok') {
            m.pat = m._pat;
            /* Das Entfernen des Chiffretexts gehört in DIESEN Zweig.
             *
             * Vorher stand es hinter dem Fehlerblock und lief deshalb auch im
             * Fehlerfall: Ein Einsatz, dessen Angaben sich gerade NICHT
             * entschlüsseln ließen, verlor beim Sichern seinen Chiffretext —
             * und die Meldung lautete „Fertig". In der Datenbank lägen die
             * Daten noch und wären mit dem richtigen Schlüssel lesbar; in der
             * Datei waren sie weg. Wer den Verdacht hat, dass etwas nicht
             * stimmt, erstellt als Erstes eine Sicherung — genau die Handlung
             * vollendete den Verlust. */
            delete m.pat_blob;
          } else if (m._patState === 'unlesbar') {
            /* Nicht lesbar: Chiffretext MITNEHMEN statt verwerfen. Die Datei
             * trägt die Angaben damit weiterhin, nur eben verschlüsselt.
             * Zurück in dasselbe Konto gespielt, sind sie wieder lesbar. */
            m.pat_unreadable = true;
          }
          // Arbeitsfelder gehören nicht in die Datei — das Format zählt seine
          // Spalten auf (M5-07), und das gilt auch hier.
          delete m._pat; delete m._patState; delete m._patFehler;
        }

        expState.textContent = 'Datei wird verschlüsselt…';
        /* Die Rundenzahl der Datei ist die des Kontos — nicht der Zielwert.
         *
         * Beides waere vertretbar; entscheidend ist, dass sie IN DER DATEI
         * steht (S7) und beim Oeffnen von dort gelesen wird. Der Wert des
         * Kontos ist der ehrlichere: Er sagt, unter welchen Bedingungen diese
         * Sicherung entstanden ist. */
        const bytes = await EdCrypto.sealBackup(pw, JSON.stringify(data), KDF_ITER);
        const url = URL.createObjectURL(new Blob([bytes], { type: 'application/octet-stream' }));
        const a = document.createElement('a');
        a.href = url;
        a.download = 'einsatzdoku-backup-' + new Date().toISOString().slice(0, 10) + '.edbak';
        a.click();
        URL.revokeObjectURL(url);
        expState.textContent = `Fertig: ${(data.missions || []).length} Einsätze `
          + `(davon ${n} mit geschützten Angaben), `
          + `${(data.rest_segments || []).length} Ruhesegmente, `
          + `${(data.days || []).length} Diensttage.`
          + (unlesbar
              ? ` ACHTUNG: ${unlesbar} Einsätze ließen sich nicht entschlüsseln. `
                + 'Ihre Angaben sind verschlüsselt in der Datei enthalten und bleiben '
                + 'lesbar, wenn die Sicherung in DIESES Konto zurückgespielt wird. '
                + 'Bitte klären, warum der Schlüssel nicht passt, bevor weitere '
                + 'Schritte unternommen werden.'
              : '');
      } catch (e) {
        expState.textContent = 'Export fehlgeschlagen: ' + e.message;
      }
    });

    // ---- Import: läuft vollständig im Browser ----
    document.getElementById('impbtn').addEventListener('click', async () => {
      const f = document.getElementById('bfile').files[0];
      if (!f) { impState.textContent = 'Bitte eine Backup-Datei auswählen.'; return; }
      const pw = document.getElementById('ipw').value;
      if (!pw) { impState.textContent = 'Bitte das Backup-Passwort eingeben.'; return; }

      const key = await ck();
      if (!key) { impState.textContent = 'Entschlüsselung gesperrt — siehe Hinweis oben.'; return; }
      try {
        impState.textContent = 'Datei wird gelesen…';
        const bytes = new Uint8Array(await f.arrayBuffer());
        if (!EdCrypto.isBackupFile(bytes)) {
          impState.textContent = 'Das ist keine Backup-Datei dieses Programms.';
          return;
        }
        impState.textContent = 'Datei wird geöffnet…';
        const data = await EdCrypto.openBackup(pw, bytes);

        /* HERKUNFT DER DATEI NENNEN (M5-13).
         *
         * Der Block `user` steht seit dem ersten Dateiformat in jeder
         * Sicherung und wurde beim Einspielen nie angesehen. Wer zwei Konten
         * betreut oder eine Datei aus einer Übergabe bekommt, hatte damit
         * keine Möglichkeit zu prüfen, ob es die richtige ist — es blieb der
         * Dateiname, und der sagt nur das Datum.
         *
         * Die Angabe wird ANGEZEIGT, nicht abgefragt: Eine Sicherung in ein
         * fremdes Konto einzuspielen ist ein vorgesehener Vorgang (deshalb
         * verschlüsselt der Browser die Angaben neu). Eine Rückfrage an
         * dieser Stelle wäre eine Warnung vor etwas Erlaubtem — und würde
         * nach dem dritten Mal weggeklickt. Die Rückfrage bleibt dem Fall
         * vorbehalten, in dem tatsächlich etwas unlesbar bliebe (unten). */
        const herkunftEl = document.getElementById('impherkunft');
        const fremdesKonto = data.user && data.user.email
                             && data.user.email !== KONTO_MAIL;
        herkunftEl.hidden = false;
        if (data.user && (data.user.email || data.user.name)) {
          const wer = data.user.name
            ? `${data.user.name} (${data.user.email || 'ohne Adresse'})`
            : data.user.email;
          const wann = data.created_at ? new Date(data.created_at) : null;
          const wannText = (wann && !isNaN(wann.getTime()))
            ? ` vom ${wann.toLocaleDateString('de-DE')}, ${wann.toLocaleTimeString('de-DE',
                  { hour: '2-digit', minute: '2-digit' })} Uhr`
            : '';
          herkunftEl.textContent = `Sicherung${wannText} aus dem Konto ${wer}.`
            + (fremdesKonto
                ? ` Das ist NICHT das angemeldete Konto (${KONTO_MAIL}) — `
                  + `die geschützten Angaben werden dabei für dieses Konto neu verschlüsselt.`
                : '');
        } else {
          herkunftEl.textContent = 'Die Datei nennt kein Herkunftskonto.';
        }

        impState.textContent = 'Angaben werden für dieses Konto verschlüsselt…';

        /* DREI FÄLLE, und sie müssen auseinandergehalten werden:
         *
         *  1. `pat` vorhanden  → beim Sichern lesbar gewesen; für DIESES Konto
         *     neu verschlüsseln. Deshalb lässt sich eine Sicherung überhaupt
         *     in ein fremdes Konto einspielen.
         *  2. `pat_blob` vorhanden, `pat` nicht → beim Sichern NICHT lesbar
         *     gewesen. Der Chiffretext ist unverändert mitgeführt (seit Web
         *     4.1.0). Er bleibt, wie er ist — umschlüsseln geht nicht, wir
         *     haben den Klartext nie gesehen.
         *  3. weder noch → keine geschützten Angaben.
         *
         * Für Fall 2 entscheidet die Prüfsumme, ob die Angaben hier lesbar
         * sein werden: Stammt die Datei aus DIESEM Konto, sind sie es. Sonst
         * werden sie übernommen und bleiben unlesbar — das ist immer noch
         * besser, als sie wegzuwerfen, aber es muss dabeistehen. */
        let uebernommen = 0, uebernommenFremd = 0;
        const gleichesKonto = PAT_KEY_CHECK != null && data.pat_key_check != null
                              && PAT_KEY_CHECK === data.pat_key_check;
        for (const m of (data.missions || [])) {
          if (m.pat && Object.keys(m.pat).length) {
            m.pat_blob = await EdCrypto.encrypt(key, JSON.stringify(m.pat));
          } else if (m.pat_blob) {
            if (gleichesKonto) { uebernommen++; } else { uebernommenFremd++; }
          }
          delete m.pat;
          delete m.pat_unreadable;
        }
        if (uebernommenFremd) {
          // Die Prüfsumme sagt, OB die Angaben hier lesbar wären; der
          // user-Block sagt, WOHER sie kommen. Beides gehört in dieselbe
          // Rückfrage, sonst muss man es sich zusammensuchen (M5-13).
          const woher = (data.user && data.user.email)
            ? ` Sie stammt aus dem Konto ${data.user.email}.` : '';
          const w = data.pat_key_check == null
            ? 'Die Datei nennt keine Schlüssel-Prüfsumme (vor Web 4.1.1 erstellt), '
              + 'die Zuordnung ist daher unbekannt.'
            : 'Die Datei stammt aus einem anderen Konto.';
          if (!confirm(`${uebernommenFremd} Einsätze enthalten geschützte Angaben, die `
              + `beim Erstellen der Sicherung nicht entschlüsselt werden konnten. `
              + `${w}${woher} Diese Angaben werden übernommen, sind hier aber `
              + `voraussichtlich NICHT lesbar. Trotzdem fortfahren?`)) {
            impState.textContent = 'Abgebrochen — es wurde nichts übernommen.';
            return;
          }
        }

        impState.textContent = 'Daten werden übertragen…';
        const res = await fetch('api/backup_restore.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify(data)
        });
        const out = await res.json();
        if (!out.ok) { throw new Error(out.meldung || out.hinweis || out.error || 'unbekannt'); }
        const s = out.stats;
        const zusatz = uebernommen
          ? ` ${uebernommen} Einsätze brachten ihre geschützten Angaben verschlüsselt `
            + `mit und sind wieder lesbar.`
          : (uebernommenFremd
              ? ` ${uebernommenFremd} Einsätze brachten verschlüsselte Angaben mit, die `
                + `in diesem Konto nicht lesbar sind.`
              : '');
        impState.textContent = `Import fertig: ${s.missions} Einsätze übernommen `
          + `(${s.missions_skipped} übersprungen${
                s.skipped_reasons && Object.keys(s.skipped_reasons).length
                  ? ': ' + Object.entries(s.skipped_reasons)
                      .map(([k, v]) => ({bereits_vorhanden:'bereits vorhanden',
                                         datum_oder_zeit:'unbrauchbares Datum oder Zeit',
                                         aufbau:'unbrauchbarer Aufbau'}[k] || k) + ' ' + v)
                      .join(', ')
                  : ''}), ${s.rests} Ruhesegmente, `
          + `${s.days} Diensttage, ${s.stammdaten} Standortdaten-Einträge`
          + (s.stammdaten_skipped ? ` (${s.stammdaten_skipped} übersprungen, bereits systemweit vorhanden)` : '') + `.` + zusatz
          /* Die Höhenberechnung läuft seit Web 4.6.0 NACH dem Einspielen und
           * kann einzeln scheitern, ohne die Wiederherstellung zu gefährden
           * (M5-05). Wenn das passiert, gehört es gesagt — sonst fehlt später
           * eine Höhenangabe ohne erkennbaren Grund. */
          + (s.hoehe_fehler
              ? ` Bei ${s.hoehe_fehler} Einsätzen ließ sich die Einsatzort-Höhe nicht `
                + `berechnen; die Einsätze selbst sind vollständig übernommen.`
              : '');
      } catch (e) {
        impState.textContent = 'Import fehlgeschlagen: ' + e.message;
      }
    });

    /* ---- Freigegebene Sicherung einspielen (A8.6) ----------------------
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
        document.getElementById('freigabeinfo').textContent =
          `Die Administration hat eine Sicherung vom `
          + `${(d.freigabe.erzeugt || '').replace('T', ' ').replace('Z', ' UTC')} `
          + `für dich freigegeben: ${u.einsaetze || 0} Einsätze, `
          + `${u.diensttage || u.flugtage || 0} Diensttage, ${u.ruhezeiten || 0} Ruhezeiten.` + woher;
        // Ohne geschützte Angaben gibt es nichts umzuschlüsseln — dann nach dem
        // Wiederherstellungsschlüssel zu fragen wäre eine Hürde ohne Zweck.
        document.getElementById('freigabecodelabel').hidden = !d.freigabe.braucht_schluessel;
        fgBox.hidden = false;
      } catch (e) {
        /* Still bleiben: Wer keine Freigabe hat, soll auf dieser Seite auch
           keinen Fehler über eine Funktion lesen, die ihn nichts angeht. */
      }
    }
    freigabeLaden();

    document.getElementById('freigabebtn').addEventListener('click', async () => {
      if (!fgPaket) { return; }
      const daten = fgPaket.daten;
      const braucht = fgPaket.freigabe.braucht_schluessel;
      try {
        if (braucht) {
          const code = document.getElementById('freigabecode').value;
          const pruef = EdCrypto.pruefeRecoveryCode(code);
          if (!pruef.ok) {
            fgState.textContent = EdCrypto.recoveryCodeMeldung(pruef);
            return;
          }
          if (!fgPaket.pat_wrap_rc) {
            fgState.textContent = 'Der Sicherung fehlt die Wiederherstellungs-Hülle — '
              + 'die geschützten Angaben lassen sich nicht mehr öffnen.';
            return;
          }
          fgState.textContent = 'Schlüssel wird geprüft…';
          const rcKey = await EdCrypto.recoveryKeyHex(code);
          let altCk = null;
          try {
            altCk = await EdCrypto.decrypt(rcKey, fgPaket.pat_wrap_rc);
          } catch (e) { altCk = null; }
          if (!altCk) {
            fgState.textContent = 'Der Wiederherstellungsschlüssel passt nicht zu dieser '
              + 'Sicherung. Es wurde nichts eingespielt.';
            return;
          }

          const eigenerCk = await ck();
          if (!eigenerCk) {
            fgState.textContent = 'Die Verschlüsselung ist in dieser Sitzung gesperrt — '
              + 'bitte oben entsperren.';
            return;
          }

          fgState.textContent = 'Geschützte Angaben werden umgeschlüsselt…';
          let um = 0, unlesbar = 0;
          for (const m of (daten.missions || [])) {
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
          if (unlesbar && !confirm(`${unlesbar} Einsätze lassen sich mit diesem Schlüssel `
              + `nicht öffnen. Ihre geschützten Angaben bleiben hier unlesbar. `
              + `Trotzdem einspielen?`)) {
            fgState.textContent = 'Abgebrochen — es wurde nichts eingespielt.';
            return;
          }
          fgState.textContent = `${um} Einsätze umgeschlüsselt. Daten werden übertragen…`;
        } else {
          fgState.textContent = 'Daten werden übertragen…';
        }

        const res = await fetch('api/backup_restore.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify(daten)
        });
        const out = await res.json();
        if (!out.ok) { throw new Error(out.meldung || out.hinweis || out.error || 'unbekannt'); }
        await fetch('api/adminbackup_freigabe.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
          body: JSON.stringify({ eingeloest: true })
        });
        const s = out.stats;
        fgState.textContent = `Fertig: ${s.missions} Einsätze übernommen `
          + `(${s.missions_skipped} übersprungen, weil bereits vorhanden oder unbrauchbar), `
          + `${s.rests} Ruhesegmente, ${s.days} Diensttage.`;
        document.getElementById('freigabebtn').disabled = true;
      } catch (e) {
        fgState.textContent = 'Einspielen fehlgeschlagen: ' + e.message;
      }
    });
    </script>

  <?php else: ?>
    <h1>Geräte</h1>
    <p class="muted">Jedes Gerät (Uhr) bekommt eigene Zugangsdaten für den Upload.
       Deaktivieren sperrt den Schlüssel — bereits hochgeladene Daten bleiben erhalten.
       Je Konto sind <strong><?= MAX_GERAETE ?> Geräte</strong> möglich
       (belegt: <?= count($devices) ?>). Deaktivierte zählen mit, weil ihre
       Zugangsdaten bestehen bleiben; erst Löschen gibt einen Platz frei.</p>

    <?php if ($devNeu > 0): ?>
      <?php /* Zweite Spur neben der E-Mail beim Koppeln: Wer die Post nicht
               liest, sieht ein neu hinzugekommenes Gerät wenigstens hier. */ ?>
      <p class="alert alert-warn">
        <?= $devNeu === 1 ? 'Ein Gerät ist' : $devNeu . ' Geräte sind' ?> in den
        letzten <?= GERAETE_NEU_TAGE ?> Tagen hinzugekommen — unten mit
        <strong>neu</strong> gekennzeichnet. Kommt dir davon etwas unbekannt vor,
        lösche es hier; danach kann es nichts mehr hochladen.</p>
    <?php endif; ?>

    <h2>Uhr koppeln (empfohlen)</h2>
    <p class="muted">Erzeuge einen Code und gib ihn auf der Uhr ein
       (Sync-Seite der Uhr → <strong>START gedrückt halten</strong> → Code eintippen;
       die Sync-Seite erreichst du vom Startbildschirm mit DOWN).
       Die Uhr holt sich ihre Zugangsdaten dann selbst — kein Abtippen langer
       Schlüssel. Der Code ist <strong><?= PAIR_TTL_MIN ?> Minuten</strong> gültig und
       <strong>genau einmal</strong> verwendbar. Ein neuer Code macht einen
       vorher erzeugten ungültig.</p>
    <?php if ($pairCode): ?>
      <div class="keybox paircode">
        <strong>Kopplungscode</strong>
        <p class="codebig"><?= e($pairCode) ?></p>
        <p class="muted">Gültig bis <?= e(fmt_local(gmdate('Y-m-d H:i:s', time() + PAIR_TTL_MIN * 60), 'H:i')) ?> Uhr.
           Das Gerät erscheint nach der Kopplung unten in der Liste.</p>
      </div>
    <?php endif; ?>
    <form method="post" action="einstellungen.php?t=geraete">
      <?= csrf_field() ?><input type="hidden" name="action" value="pair_code">
      <button class="btn-primary" style="width:auto">Kopplungscode erzeugen</button>
    </form>

    <?php if ($editDev): ?>
      <form method="post" action="einstellungen.php?t=geraete" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="rename">
        <input type="hidden" name="id" value="<?= (int)$editDev['id'] ?>">
        <input type="text" name="label" maxlength="120" placeholder="Bezeichnung"
               value="<?= e($editDev['label'] ?? '') ?>">
        <button class="btn-primary">Bezeichnung speichern</button>
        <a class="btn-red" href="einstellungen.php?t=geraete">Abbrechen</a>
      </form>
    <?php endif; ?>

    <h2>Manuell anlegen (Alternative)</h2>

    <?php if ($newKey): ?>
      <div class="keybox">
        <strong>Neues Gerät</strong>
        <p>Geräte-ID: <code><?= e($newKey['device_id']) ?></code><br>
           API-Schlüssel: <code><?= e($newKey['api_key']) ?></code></p>
        <p>Beide Werte in den Connect-IQ-Einstellungen der Uhr-App eintragen
           (als Server genügt die Domain, z. B. <code>luftrettung.net</code>).</p>
      </div>
    <?php endif; ?>

    <table class="data">
      <thead><tr><th>Geräte-ID</th><th>Bezeichnung</th><th>Status</th><th>Zuletzt gesehen</th><th></th></tr></thead>
      <tbody>
      <?php if (!$devices): ?>
        <tr><td colspan="5" class="muted">Noch keine Geräte angelegt.</td></tr>
      <?php endif; ?>
      <?php foreach ($devices as $d): ?>
        <tr>
          <td><code><?= e($d['device_id']) ?></code>
              <?php if ((int)$d['ist_neu']): ?>
                <br><strong>neu</strong>
                <span class="muted">seit <?= e(fmt_local($d['created_at'], 'd.m.Y H:i')) ?></span>
              <?php endif; ?></td>
          <td><?= e($d['label'] ?? '–') ?></td>
          <td><?= (int)$d['active'] ? 'aktiv' : '<span class="muted">deaktiviert</span>' ?></td>
          <td><?= e($d['last_seen'] ? fmt_local($d['last_seen'], 'd.m.Y H:i') : 'nie') ?></td>
          <td class="actions">
            <a class="btn-yellow" href="einstellungen.php?t=geraete&amp;ed=<?= (int)$d['id'] ?>">Bearbeiten</a>
            <form method="post" action="einstellungen.php?t=geraete">
              <?= csrf_field() ?><input type="hidden" name="action" value="toggle">
              <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
              <button class="btn-danger"><?= (int)$d['active'] ? 'Deaktivieren' : 'Aktivieren' ?></button>
            </form>
            <form method="post" action="einstellungen.php?t=geraete"
                  data-confirm="Gerät wirklich löschen? Bereits hochgeladene Daten bleiben erhalten.">
              <?= csrf_field() ?><input type="hidden" name="action" value="delete">
              <input type="hidden" name="id" value="<?= (int)$d['id'] ?>">
              <button class="btn-danger">Löschen</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>

    <form method="post" action="einstellungen.php?t=geraete" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="add">
      <input type="text" name="label" placeholder="Bezeichnung, z. B. Fenix 6 Pro">
      <button class="btn-primary">Gerät anlegen</button>
    </form>
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

  <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
