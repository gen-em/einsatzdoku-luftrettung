<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';   // WRAP_RE, Formatkennung

$tab = $_GET['t'] ?? 'profil';
if (!in_array($tab, ['profil', 'geraete', 'stammdaten', 'backup'], true)) { $tab = 'profil'; }
$notice = null; $error = null; $pwGewechselt = false; $newKey = null; $pairCode = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

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

    /* ---- Stammdaten ----------------------------------------------------- */
    if ($action === 'base_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $bid = (int)($_POST['id'] ?? 0);
        if ($n !== '') {
            if (stammdaten_dup_global('bases', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($bid > 0) {
                db()->prepare('UPDATE bases SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $bid, $userId]);
                $notice = 'Standort gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO bases (user_id, name) VALUES (?,?)')
                    ->execute([$userId, $n]);
                $notice = 'Standort gespeichert.';
            }
        }
    }
    if ($action === 'base_default') {
        $bid = (int)($_POST['id'] ?? 0);
        // item_id muss ein fuer den Nutzer sichtbarer Eintrag sein (persoenlich oder global)
        $chk = db()->prepare('SELECT COUNT(*) FROM bases WHERE id = ? AND (user_id = ? OR user_id IS NULL)');
        $chk->execute([$bid, $userId]);
        if ($chk->fetchColumn()) {
            db()->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"base",?)
                           ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')
                ->execute([$userId, $bid]);
            $notice = 'Standard-Standort gesetzt.';
        }
    }
    if ($action === 'ac_default') {
        $aid = (int)($_POST['id'] ?? 0);
        $chk = db()->prepare('SELECT COUNT(*) FROM aircraft WHERE id = ? AND (user_id = ? OR user_id IS NULL)');
        $chk->execute([$aid, $userId]);
        if ($chk->fetchColumn()) {
            db()->prepare('INSERT INTO user_defaults (user_id, kind, item_id) VALUES (?,"aircraft",?)
                           ON DUPLICATE KEY UPDATE item_id = VALUES(item_id)')
                ->execute([$userId, $aid]);
            $notice = 'Standard-Maschine gesetzt.';
        }
    }
    if ($action === 'base_del') {
        // Standortnamen in den Flugtagen sichern (siehe ac_del)
        db()->prepare('UPDATE days d
                       JOIN bases b ON b.id = d.base_id
                          SET d.base = b.name
                        WHERE d.user_id = ? AND d.base_id = ?')
            ->execute([$userId, (int)($_POST['id'] ?? 0)]);
        db()->prepare('DELETE FROM user_defaults WHERE user_id = ? AND kind = "base" AND item_id = ?')
            ->execute([$userId, (int)($_POST['id'] ?? 0)]);
        db()->prepare('DELETE FROM bases WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Standort gelöscht.';
    }
    if ($action === 'ac_save') {
        $reg = mb_substr(trim($_POST['registration'] ?? ''), 0, 64);
        $acId = (int)($_POST['id'] ?? 0);
        if ($reg !== '') {
            if (stammdaten_dup_global('aircraft', 'registration', $reg)) {
                $error = '„' . $reg . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } else {
                $flags = [];
                foreach (['p1','p2','hems','fr','other'] as $r) { $flags[] = isset($_POST[$r]) ? 1 : 0; }
                if ($acId > 0) {
                    db()->prepare('UPDATE aircraft SET registration=?, p1=?, p2=?, hems=?, fr=?, other=?
                                   WHERE id = ? AND user_id = ?')
                        ->execute(array_merge([$reg], $flags, [$acId, $userId]));
                    $notice = 'Hubschrauber gespeichert.';
                } else {
                    try {
                        db()->prepare('INSERT INTO aircraft (user_id, registration, p1, p2, hems, fr, other)
                                       VALUES (?,?,?,?,?,?,?)')
                            ->execute(array_merge([$userId, $reg], $flags));
                        $notice = 'Hubschrauber angelegt.';
                    } catch (PDOException $ex) { $error = 'Diese Kennung existiert bereits.'; }
                }
            }
        }
    }
    if ($action === 'ac_del') {
        // Bevor die Maschine verschwindet: ihren Namen in den betroffenen
        // Flugtagen als Text sichern, sonst stuende dort nach dem Loeschen
        // nichts mehr (Fremdschluessel wird auf NULL gesetzt).
        db()->prepare('UPDATE days d
                       JOIN aircraft a ON a.id = d.aircraft_id
                          SET d.aircraft = a.registration
                        WHERE d.user_id = ? AND d.aircraft_id = ?')
            ->execute([$userId, (int)($_POST['id'] ?? 0)]);
        db()->prepare('DELETE FROM user_defaults WHERE user_id = ? AND kind = "aircraft" AND item_id = ?')
            ->execute([$userId, (int)($_POST['id'] ?? 0)]);
        db()->prepare('DELETE FROM aircraft WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Hubschrauber gelöscht.';
    }
    if ($action === 'crew_save') {
        $role = $_POST['role'] ?? '';
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $cid = (int)($_POST['id'] ?? 0);
        if ($n !== '' && in_array($role, ['p1','p2','hems','fr','other'], true)) {
            if (stammdaten_dup_global('crew_presets', 'name', $n, 'role', $role)) {
                $error = '„' . $n . '“ ' . 'ist für diese Rolle bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($cid > 0) {
                db()->prepare('UPDATE crew_presets SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $cid, $userId]);
                $notice = 'Eintrag gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO crew_presets (user_id, role, name) VALUES (?,?,?)')
                    ->execute([$userId, $role, $n]);
                $notice = 'Eintrag gespeichert.';
            }
        }
    }
    if ($action === 'crew_del') {
        $cid = (int)($_POST['id'] ?? 0);
        $rq = db()->prepare('SELECT role FROM crew_presets WHERE id = ? AND user_id = ?');
        $rq->execute([$cid, $userId]);
        $role = (string)($rq->fetchColumn() ?: '');
        db()->prepare('DELETE FROM crew_presets WHERE id = ? AND user_id = ?')
            ->execute([$cid, $userId]);
        $notice = 'Eintrag gelöscht.';
    }
    if ($action === 'res_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        if ($n !== '') {
            if (stammdaten_dup_global('resources', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($wid > 0) {
                db()->prepare('UPDATE resources SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $wid, $userId]);
                $notice = 'Rettungsmittel gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO resources (user_id, name) VALUES (?,?)')
                    ->execute([$userId, $n]);
                $notice = 'Rettungsmittel gespeichert.';
            }
        }
    }
    if ($action === 'res_del') {
        // Bereits dokumentierte Einsaetze behalten ihren Eintrag: Die
        // Zuordnung steht als eigener Datensatz und haengt nicht an dieser Liste.
        db()->prepare('DELETE FROM resources WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Rettungsmittel geloescht.';
    }
    if ($action === 'bw_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        if ($n !== '') {
            if (stammdaten_dup_global('bw_units', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($wid > 0) {
                db()->prepare('UPDATE bw_units SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $wid, $userId]);
                $notice = 'Bereitschaft gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO bw_units (user_id, name) VALUES (?,?)')
                    ->execute([$userId, $n]);
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
        if ($n !== '') {
            if (stammdaten_dup_global('transport_dests', 'name', $n)) {
                $error = '„' . $n . '“ ' . 'ist bereits systemweit hinterlegt und steht dir automatisch zur Verfügung.';
            } elseif ($tid > 0) {
                db()->prepare('UPDATE transport_dests SET name = ? WHERE id = ? AND user_id = ?')
                    ->execute([$n, $tid, $userId]);
                $notice = 'Transportziel gespeichert.';
            } else {
                db()->prepare('INSERT IGNORE INTO transport_dests (user_id, name) VALUES (?,?)')
                    ->execute([$userId, $n]);
                $notice = 'Transportziel gespeichert.';
            }
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['id'] ?? 0), $userId]);
        $notice = 'Transportziel gelöscht.';
    }

    // Nach dem Speichern zurueck zum passenden Abschnitt umleiten. Das oeffnet
    // ihn dank des Ankers automatisch wieder und verhindert nebenbei das
    // erneute Absenden beim Neuladen der Seite.
    $abschnitt = [
        'base_save' => 'standorte',   'base_del' => 'standorte',
        'ac_save'   => 'hubschrauber','ac_del'   => 'hubschrauber',
        'crew_save' => 'besatzung',   'crew_del' => 'besatzung',
        'res_save'  => 'rettungsmittel', 'res_del' => 'rettungsmittel',
        'bw_save'   => 'bergwacht',   'bw_del'   => 'bergwacht',
        'td_save'   => 'transportziele', 'td_del' => 'transportziele',
    ][$action] ?? null;
    // Besatzung: rollenspezifischen Anker anhaengen (besatzung-p1 usw.), damit
    // sich beim Wiederaufklappen gezielt das Namensfeld der richtigen Rolle
    // fokussieren laesst (siehe Hash-Skript unten).
    if ($abschnitt === 'besatzung' && in_array($action, ['crew_save', 'crew_del'], true)
        && in_array($role ?? '', ['p1','p2','hems','fr','other'], true)) {
        $abschnitt .= '-' . $role;
    }
    if ($abschnitt !== null && ($notice !== null || $error !== null)) {
        if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
        if ($error !== null) { $_SESSION['flash_error'] = $error; }
        header('Location: einstellungen.php?t=stammdaten#' . $abschnitt);
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

$ROLE_LABELS = ['p1' => 'Pilot 1', 'p2' => 'Pilot 2', 'hems' => 'HEMS',
                'fr' => 'Flugretter', 'other' => 'Sonstige'];

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
?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Einstellungen — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<div class="layout">
  <?php ui_settings_sidebar($tab); ?>

  <main class="page">
  <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

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
    <script src="<?= asset('assets/crypto.js') ?>"></script>
    <?php /* Passwortguete (Baustein B9) — dieselbe Regel wie bei Erstvergabe
             und Zuruecksetzen (M2-02). */ ?>
    <script src="<?= asset('assets/pwquality.js') ?>"></script>
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

    const KDF_SALT = <?= json_encode($kdfSalt) ?>;
    /* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
       gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
       bekommt einen anderen Schluessel. */
    const KDF_ITER      = <?= json_encode($kdfIter) ?>;
    const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;
    const WRAP_PW = <?= json_encode($patWrapPw) ?>;
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
        if (WRAP_PW) {
          let ck;
          try {
            ck = await EdCrypto.decrypt(oldDataKey, WRAP_PW);
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

  <?php elseif ($tab === 'stammdaten'): ?>
    <?php
      // Leseregel (Konzept Abschnitt 4): persoenliche UND zentrale (globale,
      // user_id IS NULL) Eintraege gemischt alphabetisch; user_id zusaetzlich
      // selektieren, um in der UI zwischen beiden zu unterscheiden.
      $bases = db()->prepare('SELECT id, name, user_id FROM bases WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
      $bases->execute([$userId]); $bases = $bases->fetchAll();
      $acs = db()->prepare('SELECT * FROM aircraft WHERE (user_id = ? OR user_id IS NULL) ORDER BY registration');
      $acs->execute([$userId]); $acs = $acs->fetchAll();
      $crew = db()->prepare('SELECT id, role, name, user_id FROM crew_presets WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
      $crew->execute([$userId]); $crew = $crew->fetchAll();
      $bw = db()->prepare('SELECT id, name, user_id FROM bw_units WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
      $bw->execute([$userId]); $bw = $bw->fetchAll();
      $res = db()->prepare('SELECT id, name, user_id FROM resources WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
      $res->execute([$userId]); $res = $res->fetchAll();
      $tds = db()->prepare('SELECT id, name, user_id FROM transport_dests WHERE (user_id = ? OR user_id IS NULL) ORDER BY name');
      $tds->execute([$userId]); $tds = $tds->fetchAll();

      // Standard-Vorbelegung (user_defaults ersetzt is_default, Abschnitt 7)
      $defs = db()->prepare("SELECT kind, item_id FROM user_defaults WHERE user_id = ?");
      $defs->execute([$userId]);
      $DEF_BASE_ID = 0; $DEF_AC_ID = 0;
      foreach ($defs->fetchAll() as $d) {
          if ($d['kind'] === 'base') { $DEF_BASE_ID = (int)$d['item_id']; }
          if ($d['kind'] === 'aircraft') { $DEF_AC_ID = (int)$d['item_id']; }
      }

      // Bearbeiten ist nur fuer eigene, persoenliche Eintraege moeglich —
      // globale Zeilen haben in der Nutzer-Ansicht keine Bearbeiten-/Loeschen-Buttons.
      $pick = function (array $rows, string $param) use ($userId) {
          foreach ($rows as $r) {
              if ((int)$r['id'] === (int)($_GET[$param] ?? 0) && (int)$r['user_id'] === $userId) { return $r; }
          }
          return null;
      };
      $editAc = $pick($acs, 'ac');    $editBase = $pick($bases, 'eb');
      $editBw = $pick($bw, 'ew');
      $editRes = $pick($res, 'er');
      $editTd = $pick($tds, 'et');
      $editCrew = null;
      foreach ($crew as $c) {
          if ((int)$c['id'] === (int)($_GET['ec'] ?? 0) && (int)$c['user_id'] === $userId) { $editCrew = $c; }
      }
    ?>
    <h1>Standortdaten</h1>
    <p class="muted">Vorbelegungen für die Flugtag- und Einsatzdokumentation, alphabetisch
       sortiert. Löschen entfernt nur den Listeneintrag — gespeicherte Flugtage bleiben
       unverändert. ★ markiert die Vorbelegung neuer Flugtage. Das Kennzeichen
       „systemweit“ markiert vom Admin gepflegte Einträge — diese stehen automatisch zur
       Verfügung und lassen sich hier nicht bearbeiten oder löschen.</p>

      <details class="stammblock" id="standorte">
    <summary>Standorte</summary>

    <table class="data data-centered">
      <thead><tr><th>Name</th><th>Standard</th><th class="th-act"></th></tr></thead>
      <tbody>
      <?php if (!$bases): ?><tr><td colspan="3" class="muted">Noch keine Standorte.</td></tr><?php endif; ?>
      <?php foreach ($bases as $b): $global = $b['user_id'] === null;
            $dup = !$global && stammdaten_dup_global('bases', 'name', $b['name']); ?>
        <tr>
          <td><?= e($b['name']) ?>
            <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
          </td>
          <td class="checkcol"><?= (int)$b['id'] === $DEF_BASE_ID ? '★' : '' ?></td>
          <td><div class="rowactions">
            <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
            <?php if ((int)$b['id'] !== $DEF_BASE_ID): ?>
              <form method="post" action="einstellungen.php?t=stammdaten#standorte">
                <?= csrf_field() ?><input type="hidden" name="action" value="base_default">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn-plain">Als Standard</button>
              </form>
            <?php endif; ?>
            <?php if (!$global): ?>
              <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;eb=<?= (int)$b['id'] ?>#standorte">Bearbeiten</a>
              <form method="post" action="einstellungen.php?t=stammdaten#standorte"
                    data-confirm="Standort löschen?">
                <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn-red">Löschen</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="einstellungen.php?t=stammdaten#standorte" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
      <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
      <input type="text" name="name" class="focus-target" maxlength="120" required
             placeholder="z. B. Kempten" value="<?= e($editBase['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editBase ? 'Änderung speichern' : 'Standort hinzufügen' ?></button>
      <?php if ($editBase): ?><a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
    </form>

    <hr class="sep">
      </details>

  <details class="stammblock" id="hubschrauber">
    <summary>Hubschrauber</summary>

    <p class="muted">Die angehakten Rollen bestimmen, welche Besatzungsfelder am Flugtag erscheinen.</p>
    <table class="data data-centered">
      <thead><tr><th>Kennung</th><th>Rollen</th><th>Standard</th><th class="th-act"></th></tr></thead>
      <tbody>
      <?php if (!$acs): ?><tr><td colspan="4" class="muted">Noch keine Hubschrauber.</td></tr><?php endif; ?>
      <?php foreach ($acs as $a): $global = $a['user_id'] === null;
            $dup = !$global && stammdaten_dup_global('aircraft', 'registration', $a['registration']); ?>
        <tr>
          <td><?= e($a['registration']) ?>
            <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
          </td>
          <td class="centercol"><?php $r = [];
            foreach ($ROLE_LABELS as $k => $lbl) { if ((int)$a[$k]) { $r[] = $lbl; } }
            echo e($r ? implode(' · ', $r) : '–'); ?></td>
          <td class="checkcol"><?= (int)$a['id'] === $DEF_AC_ID ? '★' : '' ?></td>
          <td><div class="rowactions">
            <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
            <?php if ((int)$a['id'] !== $DEF_AC_ID): ?>
              <form method="post" action="einstellungen.php?t=stammdaten#hubschrauber">
                <?= csrf_field() ?><input type="hidden" name="action" value="ac_default">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn-plain">Als Standard</button>
              </form>
            <?php endif; ?>
            <?php if (!$global): ?>
              <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;ac=<?= (int)$a['id'] ?>#hubschrauber">Bearbeiten</a>
              <form method="post" action="einstellungen.php?t=stammdaten#hubschrauber"
                    data-confirm="Hubschrauber löschen?">
                <?= csrf_field() ?><input type="hidden" name="action" value="ac_del">
                <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
                <button class="btn-red">Löschen</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="einstellungen.php?t=stammdaten#hubschrauber" class="ac-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="ac_save">
      <input type="hidden" name="id" value="<?= $editAc ? (int)$editAc['id'] : 0 ?>">
      <div class="inline-form">
        <input type="text" name="registration" class="focus-target" maxlength="64" required
               placeholder="Kennung, z. B. Christoph 17"
               value="<?= e($editAc['registration'] ?? '') ?>">
        <button class="btn-primary"><?= $editAc ? 'Änderungen speichern' : 'Hubschrauber anlegen' ?></button>
        <?php if ($editAc): ?><a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
      </div>
      <div class="rolechecks">
        <span class="rolechecks-hint">Rollen auf dem Hubschrauber:</span>
        <?php foreach ($ROLE_LABELS as $k => $lbl): ?>
          <label><input type="checkbox" name="<?= $k ?>"
            <?= ($editAc && (int)$editAc[$k]) ? 'checked' : '' ?>> <?= e($lbl) ?></label>
        <?php endforeach; ?>
      </div>
    </form>

    <hr class="sep">
      </details>

  <details class="stammblock" id="besatzung">
    <summary>Besatzung — Vorbelegungen</summary>

    <p class="muted">Diese Namen erscheinen am Flugtag als Auswahl im jeweiligen Rollen-Dropdown.</p>
    <?php foreach ($ROLE_LABELS as $rk => $lbl): ?>
      <h3 class="rolehead"><?= e($lbl) ?></h3>
      <table class="data">
        <tbody>
        <?php $any = false; foreach ($crew as $c): if ($c['role'] !== $rk) continue; $any = true;
              $global = $c['user_id'] === null;
              $dup = !$global && stammdaten_dup_global('crew_presets', 'name', $c['name'], 'role', $rk); ?>
          <tr>
            <td><?= e($c['name']) ?>
              <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
            </td>
            <td class="th-act"><div class="rowactions">
              <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
              <?php if (!$global): ?>
                <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;ec=<?= (int)$c['id'] ?>#besatzung-<?= $rk ?>">Bearbeiten</a>
                <form method="post" action="einstellungen.php?t=stammdaten#besatzung"
                      data-confirm="Eintrag löschen?">
                  <?= csrf_field() ?><input type="hidden" name="action" value="crew_del">
                  <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                  <button class="btn-red">Löschen</button>
                </form>
              <?php endif; ?>
            </div></td>
          </tr>
        <?php endforeach; ?>
        <?php if (!$any): ?><tr><td class="muted">Noch keine Einträge.</td><td></td></tr><?php endif; ?>
        </tbody>
      </table>
      <form method="post" action="einstellungen.php?t=stammdaten#besatzung" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="crew_save">
        <input type="hidden" name="role" value="<?= $rk ?>">
        <input type="hidden" name="id"
               value="<?= ($editCrew && $editCrew['role'] === $rk) ? (int)$editCrew['id'] : 0 ?>">
        <input type="text" name="name" class="focus-target" data-role="<?= $rk ?>" placeholder="Name" maxlength="120" required
               value="<?= ($editCrew && $editCrew['role'] === $rk) ? e($editCrew['name']) : '' ?>">
        <button class="btn-primary"><?= ($editCrew && $editCrew['role'] === $rk) ? 'Änderung speichern' : 'Hinzufügen' ?></button>
        <?php if ($editCrew && $editCrew['role'] === $rk): ?>
          <a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
      </form>
    <?php endforeach; ?>

    <hr class="sep">
      </details>

  <details class="stammblock" id="rettungsmittel">
    <summary>Andere Rettungsmittel</summary>

    <p class="muted">Vorbelegung f&uuml;r das Feld &bdquo;Weitere Rettungsmittel&ldquo; im Einsatz.
       Dort gen&uuml;gen zwei Zeichen, dann erscheinen die passenden Eintr&auml;ge zum Anklicken.</p>
    <table class="data">
      <tbody>
      <?php if (!$res): ?><tr><td class="muted">Noch keine Rettungsmittel.</td><td></td></tr><?php endif; ?>
      <?php foreach ($res as $r): $global = $r['user_id'] === null;
            $dup = !$global && stammdaten_dup_global('resources', 'name', $r['name']); ?>
        <tr>
          <td><?= e($r['name']) ?>
            <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
          </td>
          <td class="th-act"><div class="rowactions">
            <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
            <?php if (!$global): ?>
              <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;er=<?= (int)$r['id'] ?>#rettungsmittel">Bearbeiten</a>
              <form method="post" action="einstellungen.php?t=stammdaten#rettungsmittel"
                    data-confirm="Rettungsmittel aus der Vorbelegung l&ouml;schen? Bereits dokumentierte Eins&auml;tze behalten ihren Eintrag.">
                <?= csrf_field() ?><input type="hidden" name="action" value="res_del">
                <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                <button class="btn-red">L&ouml;schen</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="einstellungen.php?t=stammdaten#rettungsmittel" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="res_save">
      <input type="hidden" name="id" value="<?= $editRes ? (int)$editRes['id'] : 0 ?>">
      <input type="text" name="name" class="focus-target" maxlength="120" required
             placeholder="z. B. RTW Kempten 21/83" value="<?= e($editRes['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editRes ? '&Auml;nderung speichern' : 'Rettungsmittel hinzuf&uuml;gen' ?></button>
      <?php if ($editRes): ?><a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
    </form>

      </details>

  <details class="stammblock" id="bergwacht">
    <summary>Bergwacht-Bereitschaften</summary>

    <table class="data">
      <tbody>
      <?php if (!$bw): ?><tr><td class="muted">Noch keine Bereitschaften.</td><td></td></tr><?php endif; ?>
      <?php foreach ($bw as $b): $global = $b['user_id'] === null;
            $dup = !$global && stammdaten_dup_global('bw_units', 'name', $b['name']); ?>
        <tr>
          <td><?= e($b['name']) ?>
            <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
          </td>
          <td class="th-act"><div class="rowactions">
            <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
            <?php if (!$global): ?>
              <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;ew=<?= (int)$b['id'] ?>#bergwacht">Bearbeiten</a>
              <form method="post" action="einstellungen.php?t=stammdaten#bergwacht"
                    data-confirm="Bereitschaft löschen?">
                <?= csrf_field() ?><input type="hidden" name="action" value="bw_del">
                <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                <button class="btn-red">Löschen</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="einstellungen.php?t=stammdaten#bergwacht" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="bw_save">
      <input type="hidden" name="id" value="<?= $editBw ? (int)$editBw['id'] : 0 ?>">
      <input type="text" name="name" class="focus-target" maxlength="120" required
             placeholder="z. B. Bereitschaft Oberstdorf" value="<?= e($editBw['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editBw ? 'Änderung speichern' : 'Bereitschaft hinzufügen' ?></button>
      <?php if ($editBw): ?><a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
    </form>

    <hr class="sep">
      </details>

  <details class="stammblock" id="transportziele">
    <summary>Transportziele</summary>

    <p class="muted">Vorschläge für das Feld „Transportziel“ im Einsatz.</p>
    <table class="data">
      <tbody>
      <?php if (!$tds): ?><tr><td class="muted">Noch keine Transportziele.</td><td></td></tr><?php endif; ?>
      <?php foreach ($tds as $t): $global = $t['user_id'] === null;
            $dup = !$global && stammdaten_dup_global('transport_dests', 'name', $t['name']); ?>
        <tr>
          <td><?= e($t['name']) ?>
            <?php if ($dup): ?><br><span class="muted">⚠ identisch mit systemweitem Eintrag — kann gelöscht werden</span><?php endif; ?>
          </td>
          <td class="th-act"><div class="rowactions">
            <?php if ($global): ?><span class="badge-central">systemweit</span><?php endif; ?>
            <?php if (!$global): ?>
              <a class="btn-yellow" href="einstellungen.php?t=stammdaten&amp;et=<?= (int)$t['id'] ?>#transportziele">Bearbeiten</a>
              <form method="post" action="einstellungen.php?t=stammdaten#transportziele"
                    data-confirm="Transportziel löschen?">
                <?= csrf_field() ?><input type="hidden" name="action" value="td_del">
                <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                <button class="btn-red">Löschen</button>
              </form>
            <?php endif; ?>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="einstellungen.php?t=stammdaten#transportziele" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
      <input type="hidden" name="id" value="<?= $editTd ? (int)$editTd['id'] : 0 ?>">
      <input type="text" name="name" class="focus-target" maxlength="190" required
             placeholder="z. B. Klinikum Kempten" value="<?= e($editTd['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editTd ? 'Änderung speichern' : 'Transportziel hinzufügen' ?></button>
      <?php if ($editTd): ?><a class="btn-red" href="einstellungen.php?t=stammdaten">Abbrechen</a><?php endif; ?>
    </form>
  </details>


  <?php elseif ($tab === 'backup'): ?>
    <h1>Backup</h1>
    <p class="muted">Sichert <strong>alle</strong> deine Daten (Einsätze mit Phasen,
       Reanimationen und Tracks, Ruhesegmente, Flugtage, Stammdaten und die
       geschützten Angaben) in eine einzelne Datei (<code>.edbak</code>), verschlüsselt
       mit einem Passwort deiner Wahl (AES-256-GCM). Ver- und Entschlüsselung passieren
       vollständig <strong>in deinem Browser</strong> — der Server sieht die Inhalte nie.
       Dadurch lässt sich ein Backup auch in ein <strong>anderes Konto</strong>
       einspielen. Format-Beschreibung: <code>docs/Backup-Format.md</code>.</p>

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

    <script src="<?= asset('assets/crypto.js') ?>"></script>
    <script src="<?= asset('assets/keyguard.js') ?>"></script>
    <script src="<?= asset('assets/unlock.js') ?>"></script>
    <?php /* patient.js liefert die gemeinsame Entschluesselungsschleife
             (Baustein B8), die der Sicherungslauf seit Web 4.6.0 benutzt;
             pwquality.js die Guetepruefung des Backup-Passworts (B9, M2-03). */ ?>
    <script src="<?= asset('assets/patient.js') ?>"></script>
    <script src="<?= asset('assets/pwquality.js') ?>"></script>
    <script>
    const PAT_WRAP = <?= json_encode($patWrapPw) ?>;
    const PAT_KEY_CHECK = <?= json_encode($patKeyCheck) ?>;
    const KDF_SALT = <?= json_encode($kdfSalt) ?>;
    /* Rundenzahl dieses Kontos und Zielwert (M2-01). Salz und Rundenzahl
       gehoeren zusammen — wer mit dem einen rechnet und das andere raet,
       bekommt einen anderen Schluessel. */
    const KDF_ITER      = <?= json_encode($kdfIter) ?>;
    const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;
    // Eigenes Konto — nur fuer den Vergleich mit der Herkunft der Datei (M5-13).
    const KONTO_MAIL = <?= json_encode($userEmail) ?>;
    const KONTO_NAME = <?= json_encode($userName) ?>;
    const CSRF = <?= json_encode($_SESSION['csrf'] ?? '') ?>;
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
          + `${(data.days || []).length} Flugtage.`
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
          + `${s.days} Flugtage, ${s.stammdaten} Standortdaten-Einträge`
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
  /* Standortdaten (und ggf. andere Tabs): Abschnitt oeffnen, wenn er per
   * Anker angesprungen oder nach dem Speichern/Loeschen dorthin umgeleitet
   * wurde. Unabhaengig vom aktiven Tab eingebunden (nicht nur im jeweiligen
   * Tab-Zweig), da der Redirect-Anker tab-uebergreifend funktionieren muss. */
  (function(){
    function oeffne(hashId){
      // hashId kann z. B. "besatzung-p1" sein (rollenspezifischer Fokus);
      // das eigentliche <details>-Element traegt aber nur die Basis-ID.
      const teil = hashId.split('-');
      const baseId = teil[0];
      const rolle = teil.slice(1).join('-');
      const d = document.getElementById(baseId);
      if (d && d.tagName === 'DETAILS') {
        d.open = true;
        d.scrollIntoView({ block: 'start' });
        let f = rolle ? d.querySelector('.focus-target[data-role="' + rolle + '"]') : null;
        if (!f) { f = d.querySelector('.focus-target'); }
        if (f) { f.focus(); }
      }
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
</body>
</html>
