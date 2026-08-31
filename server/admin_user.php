<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/spur_lib.php';   // Spuren loeschen (F-S2-B)
// Eine Rollenpruefung fuer alle Seiten (M1-15). Hier stand als einziger Stelle
// eine handgeschriebene Fassung mit eigenem Wortlaut ("Nur fuer Admins.").
require_admin();
// Loeschen entscheidet seit Web 5.8.0 auch ueber die Admin-Sicherungen (E25);
// seit Web 9.8.0 liegen die Sicherungen des Kontos ganz hier (E-P3-41).
require_once __DIR__ . '/adminbackup_lib.php';
require_once __DIR__ . '/smtp.php';       // Passwort zuruecksetzen

/**
 * KONTOSEITE — die Drehscheibe eines Kontos (E-P3-41, P3/O9).
 *
 * WAS SICH GEAENDERT HAT. Bis Web 9.7.2 war diese Seite eine Reihe von
 * Einzelformularen (Rolle, E-Mail, Name — jedes mit eigenem Speichern) und
 * einer Geraetetabelle; die SICHERUNGEN eines Kontos standen woanders, auf
 * admin_sicherungen.php, in einer Tabelle ueber alle Konten. Wer zu einem
 * Konto etwas tun wollte, brauchte zwei Seiten und musste auf der zweiten
 * seine Zeile suchen.
 *
 * Jetzt liegt alles zu EINEM Konto hier: Kontodaten in einem Formular mit
 * einem Speichern, Geraete, Sicherungen, Abonnement (Platz fuer R33) und die
 * Loeschung als abgesetzte Gefahrenzone. admin_sicherungen.php behaelt nur
 * die REGELN (O9c).
 *
 * WARUM DAS BEI DREIHUNDERT KONTEN DER RICHTIGE SCHNITT IST. Die alte
 * Uebersicht las fuer JEDES Konto ein Verzeichnis und eine Begleitdatei —
 * eine Seite, deren Arbeit mit der Zahl der Konten waechst, obwohl man immer
 * nur eines davon ansieht. Hier wird genau ein Ordner gelesen
 * (edbak_konto_stand); die Liste in admin_users.php kommt ohne Dateizugriff
 * aus.
 *
 * DREI HANDLUNGEN BRAUCHEN MEHR ALS EINE RUECKFRAGE — Einspielen, Freigeben,
 * Loeschen. Sie stehen in Dialogen (assets/dialog.js) mit dem, was sie
 * brauchen: Zielkonto, abgetippte Adresse. Geprueft wird SERVERSEITIG; ein
 * Browser-Dialog liesse sich umgehen.
 */

$uid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$notice = null; $error = null; $bericht = null; $setzLink = null;

// Muss VOR der POST-Verarbeitung stehen: die Loeschbestaetigung vergleicht
// die Eingabe mit $u['email']. Nach dem Block wird erneut gelesen, damit die
// Anzeige die soeben geaenderten Werte zeigt.
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$uid]);
$u = $st->fetch();
if (!$u) { ui_abbruch(404, 'NutzerIn nicht gefunden.', ['zurueck' => 'admin_users.php', 'zurueck_text' => 'Zu den NutzerInnen']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');
    $kennung = (string)($u['account_key'] ?? '');
    $datei   = (string)($_POST['datei'] ?? '');

    /* ---- Kontodaten: EIN Formular, EIN Speichern (E-P3-41) --------------
     *
     * Vorher waren es drei. Drei Formulare heissen drei Absendevorgaenge fuer
     * eine Aenderung, die man als eine denkt („Name und Rolle richtigstellen")
     * — und jedes mit eigener Meldung, die die vorige ueberschreibt.
     *
     * Reihenfolge im Code: erst die beiden, die nicht scheitern koennen, dann
     * die E-Mail-Adresse. Bricht die Adresse ab (Dublette), sind Name und
     * Rolle trotzdem gespeichert; die Meldung sagt dann genau das. Andersherum
     * bliebe eine halbe Aenderung ohne Auskunft zurueck.
     */
    if ($action === 'konto') {
        /* „Rolle, Name und E-Mail-Adresse" — nicht „Rolle und Name und …".
         * Eine Aufzaehlung mit zwei „und" liest sich wie ein Fehler, und die
         * Meldung ist der einzige Beleg dafuer, was tatsaechlich geschrieben
         * wurde. */
        $aufzaehlung = static function (array $t): string {
            if (count($t) < 2) { return (string)($t[0] ?? ''); }
            $letzt = array_pop($t);
            return implode(', ', $t) . ' und ' . $letzt;
        };
        $name = trim((string)($_POST['name'] ?? ''));
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        $teile = [];

        if ($uid === $userId && $role !== 'admin') {
            $error = 'Du kannst dir nicht selbst die Admin-Rolle entziehen — '
                   . 'die Rolle wurde nicht geändert.';
        } elseif ($role !== (string)$u['role']) {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
            $teile[] = 'Rolle';
        }
        if ($name !== (string)($u['name'] ?? '')) {
            db()->prepare('UPDATE users SET name = ? WHERE id = ?')
                ->execute([$name !== '' ? $name : null, $uid]);
            $teile[] = 'Name';
        }

        $email = email_pruefen($_POST['email'] ?? '');
        if ($email === null) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben (höchstens 190 Zeichen).'
                   . ($teile ? ' ' . $aufzaehlung($teile) . ' wurde gespeichert.' : '');
        } elseif ($email !== (string)$u['email']) {
            try {
                db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $uid]);
                $teile[] = 'E-Mail-Adresse';
            } catch (PDOException $ex) {
                /* NUR der Schluesselkonflikt heisst "bereits verwendet" (M1-16).
                 * Vorher wurde JEDER Datenbankfehler so gemeldet — eine volle
                 * Platte, eine abgerissene Verbindung, ein Rechteproblem: alles
                 * erschien als Dublette und schickte die Fehlersuche
                 * zuverlaessig in die falsche Richtung. */
                if (ist_dublettenfehler($ex)) {
                    $error = 'Diese E-Mail-Adresse wird bereits verwendet.';
                } else {
                    error_log('admin_user email: ' . $ex->getMessage());
                    $error = 'Die E-Mail-Adresse konnte nicht gespeichert werden.';
                }
                if ($teile) {
                    $error .= ' ' . $aufzaehlung($teile) . ' wurde gespeichert.';
                }
            }
        }
        if ($error === null) {
            $notice = $teile
                ? $aufzaehlung($teile) . ' gespeichert.'
                : 'Es gab nichts zu ändern.';
        }
    }

    /* ---- Passwort zuruecksetzen (E-P3-41) -------------------------------
     *
     * Setzt KEIN Passwort — das kann diese Seite nicht, und das ist der Punkt:
     * Die Daten sind mit dem Passwort der Person Ende-zu-Ende-verschluesselt.
     * Verschickt wird derselbe Link, den „Passwort vergessen" verschickt
     * (reset_request.php), und mit derselben Regel: Der neue Token entwertet
     * alle offenen, es gibt zu jedem Zeitpunkt hoechstens einen.
     *
     * Kommt die Mail nicht weg, wird der Link ANGEZEIGT statt verschwiegen —
     * dasselbe Muster wie beim Anlegen eines Kontos (admin_users.php). Ein
     * gueltiger Token in der Datenbank, von dem niemand weiss, ist die
     * schlechteste aller Lagen.
     */
    if ($action === 'pw_reset') {
        require_once __DIR__ . '/demo_lib.php';
        if (demo_ist_demo($uid)) {
            $error = 'Das Demo-Konto bekommt keinen Setz-Link: Sein Passwort ist '
                   . 'öffentlich und steht im Handbuch (E-P1-19).';
        } else {
            db()->prepare('UPDATE password_resets SET used_at = NOW()
                           WHERE user_id = ? AND used_at IS NULL')->execute([$uid]);
            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([$uid, hash('sha256', $token)]);
            $link = $CFG['app']['base_url'] . '/pw_handling.php?token=' . $token;
            $ok = smtp_send((string)$u['email'],
                'Neues Passwort — Gen-EM Einsatzdokumentation Notarzt',
                "Hallo,\n\n"
                . "für deinen Zugang zur Gen-EM Einsatzdokumentation Notarzt wurde ein neues\n"
                . "Passwort angefordert. Über den folgenden Link kannst du es setzen — der Link ist\n"
                . "eine Stunde gültig:\n\n"
                . $link . "\n\n"
                . "Dafür brauchst du deinen Wiederherstellungsschlüssel, den du bei der Einrichtung\n"
                . "erhalten hast.\n\n"
                . "Ein zuvor angeforderter Link ist damit ungültig geworden — es gilt immer nur der\n"
                . "zuletzt verschickte.\n\n"
                . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
                . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n");
            if ($ok) {
                $notice = 'Setz-Link an ' . $u['email'] . ' verschickt — eine Stunde gültig.';
            } else {
                $notice = 'Der Setz-Link konnte NICHT verschickt werden.';
                $setzLink = $link;
            }
        }
    }

    /* ---- Sichern (A8.3) -------------------------------------------------- */
    if ($action === 'sichern') {
        [$ok, $grund, $erg] = edbak_sicherung_erzeugen($uid);
        if ($ok) {
            $notice = 'Sicherung erzeugt.'
                . (!empty($erg['verdraengt'])
                    ? ' ' . count($erg['verdraengt']) . ' ältere verdrängt.'
                    : '');
        } else {
            $error = 'Nicht gesichert: ' . $grund;
        }
    }

    /* ---- Einspielen (A8.6): Ziel ist DIESES Konto ------------------------
     *
     * Auf der Kontoseite gibt es nur ein sinnvolles Ziel — das Konto, dessen
     * Seite man aufhat. Ein Auswahlfeld mit allen Konten stuende hier fuer
     * einen Fall, den es nicht gibt: Wer eine Sicherung in ein FREMDES Konto
     * bringen will, gibt sie frei (unten) oder nimmt den Weg ueber die
     * verwaisten Sicherungen (admin_sicherungen.php) — dort hat das Paket
     * kein Konto mehr, dem es gehoert.
     *
     * edbak_weg() entscheidet trotzdem: Ein Paket aus einem ANDEREN Konto
     * (eingespielte Fremdsicherung) darf nicht unmittelbar hierher.
     */
    if ($action === 'einspielen') {
        $ziel  = edbak_ziel_konto($uid);
        $paket = edbak_paket_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Die Sicherung liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Kontos überein — es wurde nichts eingespielt.';
        } else {
            [$weg, $warum] = edbak_weg($paket, $ziel);
            if ($weg === 'gesperrt') {
                $error = 'Einspielen nicht möglich. ' . $warum;
            } elseif ($weg === 'freigabe') {
                $error = 'Unmittelbares Einspielen ist gesperrt. ' . $warum
                       . ' Bitte stattdessen die Sicherung für dieses Konto freigeben.';
            } else {
                try {
                    $bericht = edbak_restore($uid, $paket['daten']);
                    $notice = 'Sicherung eingespielt.';
                } catch (Throwable $ex) {
                    $error = 'Das Einspielen ist fehlgeschlagen (Kennung '
                           . fehler_kennung($ex, 'adminbackup') . ').';
                }
            }
        }
    }

    /* ---- Freigeben und widerrufen (A8.6) --------------------------------- */
    if ($action === 'freigeben') {
        $ziel  = edbak_ziel_konto((int)($_POST['ziel_user'] ?? 0));
        $paket = edbak_paket_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Die Sicherung liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts freigegeben.';
        } elseif (edbak_freigeben($kennung, $datei, (int)$ziel['id'])) {
            $notice = 'Freigegeben für ' . $ziel['email'] . '. Die NutzerIn sieht die '
                    . 'Sicherung jetzt im eigenen Backup-Bereich und spielt sie dort '
                    . 'mit ihrem Wiederherstellungsschlüssel ein.';
        } else {
            $error = 'Die Freigabe liess sich nicht speichern.';
        }
    }
    if ($action === 'widerrufen') {
        if (edbak_freigabe_widerrufen($kennung)) { $notice = 'Freigabe widerrufen.'; }
        else { $error = 'Die Freigabe liess sich nicht widerrufen.'; }
    }

    /* ---- Sicherung loeschen (A8.8) ---------------------------------------
     *
     * Die Haerte der Bestaetigung richtet sich danach, was verlorengeht (E24):
     * Bleibt danach mindestens eine weitere Sicherung dieses Kontos, genuegt
     * die uebliche Rueckfrage. Ist es die LETZTE, ist zusaetzlich die
     * E-Mail-Adresse abzutippen — der Dialog verlangt sie dann.
     */
    if ($action === 'paket_loeschen') {
        $hart = ($_POST['hart'] ?? '') === '1';
        $bestaetigt = !$hart
            || edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$u['email']);
        if (!$bestaetigt) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht überein — es wurde '
                   . 'nichts gelöscht.';
        } elseif (edbak_paket_loeschen($kennung, $datei)) {
            $notice = 'Sicherung gelöscht.';
        } else {
            $error = 'Die Sicherung liess sich nicht löschen.';
        }
    }

    if ($action === 'user_delete') {
        // Zweite Stufe: die E-Mail-Adresse muss abgetippt werden. Bewusst
        // SERVERSEITIG geprueft — ein Browser-Dialog liesse sich umgehen.
        $eingabe = trim((string)($_POST['confirm_email'] ?? ''));
        if ($uid === $userId) {
            $error = 'Das eigene Konto kann hier nicht gelöscht werden.';
        } elseif (!edbak_bestaetigung_passt($eingabe, (string)$u['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht überein — nichts wurde gelöscht.';
        } else {
            /* ÜBER DIE SICHERUNGEN WIRD AUSDRÜCKLICH ENTSCHIEDEN (E25).
             *
             * Bis Web 5.8.0 sagte der Warntext unbedingt zu, dass nach der
             * Löschung nichts mehr lesbar ist. Sobald Admin-Sicherungen
             * existieren, wäre das unwahr — die Sicherung überlebt die
             * Löschung und würde zur verwaisten Sicherung. Genau diese Zusage
             * ist aber der Grund, aus dem jemand eine Löschung verlangt.
             *
             * Umgekehrt ist das Überleben der Sicherung der Zweck der ganzen
             * Funktion. Beides verträgt sich nur, wenn die Entscheidung
             * sichtbar getroffen wird. Die Vorbelegung folgt der bisherigen
             * Zusage; das Abweichen ist eine bewusste Handlung.
             *
             * Die Sicherungen werden VOR dem Löschen der Zeile entfernt: Danach
             * wäre die Kontokennung fort, und der Ordner liesse sich nur noch
             * über die Übersicht der verwaisten Sicherungen finden. */
            $mitSicherungen = ($_POST['sicherungen_mit'] ?? '1') === '1';
            $sicherungenWeg = false;
            if ($mitSicherungen) {
                $sicherungenWeg = edbak_konto_ordner_loeschen(
                    $kennung !== '' ? $kennung : null);
            }
            if ($mitSicherungen && !$sicherungenWeg) {
                /* Nicht löschen, wenn die Zusage nicht gehalten werden kann.
                 * Ein Konto zu entfernen und die Sicherung stehen zu lassen,
                 * OBWOHL das Gegenteil gewählt wurde, wäre die schlechteste
                 * der drei möglichen Ausgänge. */
                $error = 'Die Sicherungen dieses Kontos liessen sich nicht entfernen — '
                       . 'das Konto wurde deshalb NICHT gelöscht. Bitte unter '
                       . '„Sicherungen" nachsehen.';
            } else {
                /* DIE SPUREN ZUERST, UND AUSDRUECKLICH (F-S2-B, S2/AP1).
                 *
                 * Hier stand: „FK-Kaskaden entfernen Einsätze, Segmente,
                 * Tracks, Geräte, Diensttage". Fuer „Tracks" war das FALSCH,
                 * und zwar seit jeher: `track_points` ist polymorph
                 * (owner_type/owner_id) und traegt deshalb KEINEN
                 * Fremdschluessel — die Kaskade nimmt die Punkte nicht mit.
                 * Sie blieben als Waisen liegen, bis der Tagesjob das naechste
                 * Mal lief: fruehestens am naechsten Kalendertag, und nur,
                 * wenn ueberhaupt jemand die Installation aufrief.
                 *
                 * Was dort liegen blieb, sind Positionsdaten — Wohnorte,
                 * Einsatzorte, Wege. Ein Konto zu loeschen ist die Handlung,
                 * mit der eine NutzerIn genau das aus der Welt schaffen will.
                 * Dass es bis zu einen Tag laenger dauerte, war vertretbar;
                 * dass es niemand wusste, nicht — und der Kommentar hier hat
                 * dafuer gesorgt, dass es niemand wusste.
                 *
                 * Der Messstand hat es vorgefuehrt: Zwei geloeschte Konten
                 * hinterliessen 6 202 931 verwaiste Spurpunkte, rund 380 MB.
                 *
                 * Jetzt gehen Zeilen UND Blobs mit, vor der Kaskade. Der
                 * Wartungsjob bleibt das Sicherheitsnetz (E-S2-18). */
                $pdoDel = db();
                foreach ([['mission', 'missions'], ['rest', 'rest_segments']] as [$typ, $tab]) {
                    $ids = $pdoDel->prepare("SELECT id FROM `$tab` WHERE user_id = ?");
                    $ids->execute([$uid]);
                    spur_loeschen($pdoDel, $typ, $ids->fetchAll(PDO::FETCH_COLUMN));
                }
                // Der Rest kaskadiert wie bisher.
                $pdoDel->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
                header('Location: admin_users.php');
                exit;
            }
        }
    }
    if ($action === 'device_toggle') {
        db()->prepare('UPDATE devices SET active = 1 - active WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['dev'] ?? 0), $uid]);
        $notice = 'Gerätestatus geändert.';
    }
    if ($action === 'device_delete') {
        // Daten bleiben erhalten: FK setzt device_id in Einsaetzen/Segmenten auf NULL
        db()->prepare('DELETE FROM devices WHERE id = ? AND user_id = ?')
            ->execute([(int)($_POST['dev'] ?? 0), $uid]);
        $notice = 'Gerät entkoppelt. Hochgeladene Daten bleiben erhalten.';
    }
}

// Auffrischen: zeigt Rolle, Name und E-Mail nach einer Aenderung aktuell an.
$st->execute([$uid]);
$u = $st->fetch();
if (!$u) { ui_abbruch(404, 'NutzerIn nicht gefunden.', ['zurueck' => 'admin_users.php', 'zurueck_text' => 'Zu den NutzerInnen']); }

$dv = db()->prepare('SELECT id, device_id, label, active, created_at, last_seen FROM devices
                     WHERE user_id = ? AND device_id NOT LIKE \'manual-%\' ORDER BY created_at');
$dv->execute([$uid]);
$devices = $dv->fetchAll();

$stand   = edbak_konto_stand($u);
$pakete  = $stand['pakete'];
$freigabe = $stand['freigabe'];
[$standText, $standTon] = edbak_stand_plakette($stand);
$istIch  = $uid === $userId;

/* Zielkonten der Freigabe: die uebrigen Konten. Eine Abfrage, kein
 * Dateizugriff — und ohne das eigene Konto, denn „an sich selbst freigeben"
 * ist der Fall, fuer den es das Einspielen gibt. */
$zielkonten = db()->prepare('SELECT id, email FROM users WHERE id <> ? ORDER BY email');
$zielkonten->execute([$uid]);
$zielkonten = $zielkonten->fetchAll();

/** Ist das Paket formal lesbar? Liest die Datei — deshalb nur hier, wo es
 *  hoechstens so viele sind, wie die Aufbewahrung zulaesst (Vorgabe drei). */
function paket_lesbar(string $kennung, string $datei): bool
{
    return edbak_paket_lesen($kennung, $datei) !== null;
}

$kennung = (string)($u['account_key'] ?? '');
$rolleText = $u['role'] === 'admin' ? 'Admin' : 'NutzerIn';
$unterTeile = [e((string)$u['email']), e($rolleText)];
if (!empty($u['created_at'])) {
    $unterTeile[] = 'seit ' . e(fmt_local($u['created_at'], 'd.m.Y'));
}
$unterTeile[] = 'zuletzt angemeldet '
    . (!empty($u['last_login']) ? e(fmt_local($u['last_login'], 'd.m.Y')) : '—');

ui_seite_start(['titel' => ($u['name'] ?: $u['email']) . ' — Konto']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin']); ?>

  <?php /* Die versteckten Formulare zuerst: Die Knöpfe in der Titelzeile, in
           den Zeilenaktionen und im Aktionsblatt verweisen über `form` auf
           sie. Ein <form> um einen Knopf im Blatt ginge nicht — das Blatt
           steht selbst in einem Block, und verschachtelte Formulare gibt es
           in HTML nicht. */ ?>
  <form method="post" id="f-sichern" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="sichern">
    <input type="hidden" name="id" value="<?= $uid ?>">
  </form>
  <form method="post" id="f-pwreset" hidden
        data-confirm="Setz-Link an <?= e((string)$u['email']) ?> schicken? Ein zuvor verschickter Link wird damit ungültig."
        data-confirm-ok="Link schicken" data-confirm-tone="normal">
    <?= csrf_field() ?><input type="hidden" name="action" value="pw_reset">
    <input type="hidden" name="id" value="<?= $uid ?>">
  </form>
  <?php if ($freigabe): ?>
    <form method="post" id="f-widerrufen" hidden
          data-confirm="Freigabe widerrufen? Die NutzerIn sieht die Sicherung danach nicht mehr."
          data-confirm-ok="Widerrufen">
      <?= csrf_field() ?><input type="hidden" name="action" value="widerrufen">
      <input type="hidden" name="id" value="<?= $uid ?>">
    </form>
  <?php endif; ?>
  <?php foreach ($devices as $d): ?>
    <form method="post" id="f-dev-t-<?= (int)$d['id'] ?>" hidden>
      <?= csrf_field() ?><input type="hidden" name="action" value="device_toggle">
      <input type="hidden" name="id" value="<?= $uid ?>">
      <input type="hidden" name="dev" value="<?= (int)$d['id'] ?>">
    </form>
    <form method="post" id="f-dev-d-<?= (int)$d['id'] ?>" hidden
          data-confirm="Gerät entkoppeln? Hochgeladene Daten bleiben erhalten."
          data-confirm-ok="Entkoppeln">
      <?= csrf_field() ?><input type="hidden" name="action" value="device_delete">
      <input type="hidden" name="id" value="<?= $uid ?>">
      <input type="hidden" name="dev" value="<?= (int)$d['id'] ?>">
    </form>
  <?php endforeach; ?>

  <?php
  $aktionen = ui_knopf(['text' => 'Jetzt sichern', 'symbol' => 'sicherung',
                        'art' => 'neutral', 'attr' => ' form="f-sichern"']);
  $eintraege = [];
  if ($pakete) {
      $eintraege[] = ['text' => 'Für Zielkonto freigeben', 'symbol' => 'tausch',
                      'href' => '#', 'attr' => 'data-dialog="dlg-freigeben"'];
  }
  if ($freigabe) {
      $eintraege[] = ['text' => 'Freigabe widerrufen', 'symbol' => 'schliessen',
                      'form' => 'f-widerrufen'];
  }
  $eintraege[] = ['text' => 'Passwort zurücksetzen', 'symbol' => 'schloss-offen',
                  'form' => 'f-pwreset'];
  if (!$istIch) {
      $eintraege[] = ['text' => 'Konto löschen', 'symbol' => 'korb',
                      'href' => '#karte-loeschen', 'gefahr' => true];
  }
  $aktionen .= ui_aktionen(['titel' => 'Konto', 'id' => 'konto-aktionen',
                            'eintraege' => $eintraege]);
  ui_titelzeile([
      'zurueck'  => ['text' => 'NutzerInnen', 'href' => 'admin_users.php'],
      'titel'    => (string)($u['name'] ?: $u['email']),
      'unter'    => implode(' · ', $unterTeile),
      'aktionen' => $aktionen,
  ]);
  ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if ($setzLink !== null): ?>
    <?php /* Muster aus admin_users.php: Ein gültiger Token, von dem niemand
             weiss, ist die schlechteste aller Lagen. */ ?>
    <?= ui_meldung_markup('warn',
        'Der Link konnte nicht per E-Mail zugestellt werden. Er ist eine Stunde '
        . 'gültig — bitte auf einem anderen Weg an die Person selbst weitergeben. '
        . 'Die Ursache des Fehlschlags steht im Fehlerprotokoll des Webspace.') ?>
    <p class="codeblock"><?= e($setzLink) ?></p>
  <?php endif; ?>

  <?php if ($bericht): ?>
    <?= ui_meldung_markup('ok', 'Eingespielt: '
        . (int)($bericht['days'] ?? 0) . ' Diensttage, '
        . (int)($bericht['missions'] ?? 0) . ' Einsätze, '
        . (int)($bericht['rest_segments'] ?? 0) . ' Ruhezeiten. '
        . 'Ergänzt, nicht ersetzt — Vorhandenes bleibt stehen.') ?>
  <?php endif; ?>

  <div class="form-raster">
  <div class="form-spalte">

    <?php /* ---- Konto: ein Formular, ein Speichern ------------------------ */ ?>
    <?php ui_karte_start(['titel' => 'Konto']); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="konto">
        <input type="hidden" name="id" value="<?= $uid ?>">
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'name', 'label' => 'Name', 'wert' => (string)($u['name'] ?? ''),
                         'attr' => 'maxlength="120" placeholder="z. B. Vorname Nachname"']); ?>
          <?php ui_feld(['name' => 'role', 'label' => 'Rolle', 'art' => 'select',
                         'wert' => (string)$u['role'],
                         'optionen' => ['user' => 'NutzerIn', 'admin' => 'Admin'],
                         'klein' => $istIch ? 'Das eigene Konto bleibt Admin.' : null]); ?>
        </div>
        <?php ui_feld(['name' => 'email', 'label' => 'E-Mail (Anmeldung)', 'art' => 'email',
                       'wert' => (string)$u['email'], 'pflicht' => true]); ?>
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </form>
      <p class="feld-hinweis">Ein Passwort lässt sich hier nicht setzen: Die Daten sind mit
         dem Passwort der Person Ende-zu-Ende-verschlüsselt. „Passwort zurücksetzen"
         im Aktionsmenü verschickt denselben Link wie „Passwort vergessen" auf der
         Anmeldeseite; entsperrt wird danach mit dem Wiederherstellungsschlüssel
         der Person.</p>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Geräte ---------------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Geräte', 'zahl' => (string)count($devices)]); ?>
      <?php if (!$devices): ?>
        <p class="feld-hinweis">Keine Geräte gekoppelt.</p>
      <?php endif; ?>
      <?php foreach ($devices as $d):
        $klein = [];
        if (!empty($d['created_at'])) { $klein[] = 'gekoppelt ' . fmt_local($d['created_at'], 'd.m.Y'); }
        $klein[] = 'zuletzt gesehen ' . (!empty($d['last_seen'])
            ? fmt_local($d['last_seen'], 'd.m.Y') : 'nie');
        /* DIE GERAETEKENNUNG BLEIBT SICHTBAR (Mockup 40: „Venu 3S ·
           4F2A…91"). Sie stand bis Web 9.7.2 als eigene Tabellenspalte da
           und ist das Einzige, woran sich eine Uhr in einer Rückfrage
           zweifelsfrei benennen lässt — zwei Geräte können dieselbe
           Bezeichnung tragen. In die Hauptzeile, nicht in die Kleinzeile:
           Sie gehört zum Namen des Geräts, nicht zu seiner Geschichte. */
        $kennungKurz = (string)$d['device_id'];
        if (mb_strlen($kennungKurz) > 12) {
            $kennungKurz = mb_substr($kennungKurz, 0, 8) . '…' . mb_substr($kennungKurz, -2);
        }
        ui_zeile([
          'text'  => (($d['label'] ?? '') !== '' ? (string)$d['label'] . ' · ' : '')
                   . $kennungKurz,
          'klein' => implode(' · ', $klein),
          'plaketten' => (int)$d['active']
              ? ui_plakette('aktiv', ['ton' => 'blau'])
              : ui_plakette('deaktiviert', ['ton' => 'neutral']),
          'aktionen' => ui_zeilenaktionen(['titel' => 'Gerät', 'eintraege' => [
              ['text' => (int)$d['active'] ? 'Deaktivieren' : 'Aktivieren',
               'form' => 'f-dev-t-' . (int)$d['id']],
              ['text' => 'Entkoppeln', 'art' => 'gefahr',
               'form' => 'f-dev-d-' . (int)$d['id']],
          ]]),
        ]);
      endforeach; ?>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">

    <?php /* ---- Sicherungen ----------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Sicherungen', 'zahl' => (string)count($pakete),
                          'plakette' => ui_plakette($standText, ['ton' => $standTon])]); ?>
      <?php if ($kennung === ''): ?>
        <?= ui_meldung_markup('warn', 'Diesem Konto fehlt die Kontokennung. Bitte zuerst '
            . 'die Wartung aufrufen und die Migration ausführen — ohne Kennung lässt '
            . 'sich das Konto nicht sichern.') ?>
      <?php elseif (!$pakete): ?>
        <p class="feld-hinweis">Für dieses Konto gibt es noch keine Sicherung.</p>
      <?php endif; ?>
      <?php foreach ($pakete as $i => $p):
        $istFreigabe = $freigabe && ($freigabe['datei'] ?? '') === $p['datei'];
        $lesbar = paket_lesbar($kennung, (string)$p['datei']);
        $zeit = edbak_zeitpunkt_text($p['erzeugt']);
        $plaketten = $istFreigabe ? ui_plakette('freigegeben', ['ton' => 'blau']) : '';
        $plaketten .= $lesbar
            ? ui_plakette('lesbar', ['ton' => 'neutral'])
            : ui_plakette('nicht lesbar', ['ton' => 'rot']);
        /* HART BESTÄTIGEN, WENN ES DIE LETZTE IST (E24). Bleibt eine weitere
           Sicherung dieses Kontos stehen, genügt die übliche Rückfrage. */
        $hart = count($pakete) === 1;
        ui_zeile([
          'text'  => $zeit,
          'klein' => edbak_umfang_text($p),
          'plaketten' => $plaketten,
          'aktionen' => ui_zeilenaktionen(['titel' => $zeit, 'eintraege' => [
              ['text' => 'Einspielen', 'href' => '#',
               'attr' => ' data-dialog="dlg-einspielen" data-w-datei="' . e((string)$p['datei'])
                       . '" data-w-zeit="' . e($zeit) . '"'],
              ['text' => 'Löschen', 'art' => 'gefahr', 'href' => '#',
               'attr' => ' data-dialog="dlg-paket-weg" data-w-datei="' . e((string)$p['datei'])
                       . '" data-w-zeit="' . e($zeit) . '" data-w-hart="' . ($hart ? '1' : '')
                       . '"'],
          ]]),
        ]);
      endforeach; ?>
      <p class="feld-hinweis">Aufbewahrung: die letzten <?= edbak_aufbewahrung() ?> Pakete je
         Konto (Einstellung unter <a href="admin_sicherungen.php">Sicherungen</a>). Einspielen
         ergänzt, ersetzt nicht; die Administration sieht keinen Klartext.</p>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Jetzt sichern', 'symbol' => 'sicherung',
                      'art' => 'primaer', 'attr' => ' form="f-sichern"']) ?>
        <?php if ($pakete): ?>
          <?= ui_knopf(['text' => 'Für Zielkonto freigeben', 'symbol' => 'tausch',
                        'art' => 'neutral', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-freigeben"']) ?>
        <?php endif; ?>
      </div>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Abonnement: reservierter Platz (R33) ---------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Abonnement', 'zahl' => 'ab P5']); ?>
      <p class="feld-hinweis">Tarif, Laufzeit, Zahlungsstand und Rechnungen dieses Kontos.
         Der Platz ist hier reserviert; der Inhalt kommt mit den Abomodellen
         (Rahmenplan R33).</p>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Gefahrenzone ---------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Konto löschen', 'klasse' => 'karte-gefahr',
                          'id' => 'karte-loeschen']); ?>
      <?php if ($istIch): ?>
        <p class="feld-hinweis">Das eigene Konto lässt sich hier nicht löschen.</p>
      <?php else: ?>
        <p class="feld-hinweis">Entfernt Konto, Diensttage, Einsätze, Tracks, Reanimationen
           und Geräte <strong>endgültig</strong> — ohne Papierkorb, nicht rückgängig zu
           machen. Ob danach nichts mehr lesbar ist, hängt von der Wahl unten ab:
           Bleiben die Sicherungen erhalten, überleben sie die Löschung und erscheinen
           unter „Sicherungen" als Sicherung ohne Konto.</p>
        <form method="post" data-confirm="Konto endgültig löschen?"
              data-confirm-ok="Endgültig löschen">
          <?= csrf_field() ?><input type="hidden" name="action" value="user_delete">
          <input type="hidden" name="id" value="<?= $uid ?>">
          <?php ui_feld(['name' => 'sicherungen_mit', 'label' => 'Sicherungen dieses Kontos',
                         'art' => 'select', 'wert' => '1', 'optionen' => [
                             '1' => 'mitlöschen (Vorgabe)',
                             '0' => 'erhalten — erscheinen als Sicherung ohne Konto']]); ?>
          <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse',
                         'pflicht' => true,
                         'attr' => 'autocomplete="off" placeholder="' . e((string)$u['email']) . '"',
                         'klein' => 'Zur Bestätigung die Adresse des Kontos abtippen.']); ?>
          <?= ui_knopf(['text' => 'Konto endgültig löschen', 'symbol' => 'korb',
                        'art' => 'gefahr']) ?>
        </form>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (rechts) */ ?>
  </div><?php /* .form-raster */ ?>

  <?php /* ---- Dialoge (assets/dialog.js) ---------------------------------- */ ?>
  <dialog class="dialog" id="dlg-einspielen">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="einspielen">
      <input type="hidden" name="id" value="<?= $uid ?>">
      <input type="hidden" name="datei" data-fuell="datei">
      <div class="dialog-kopf"><h2>Sicherung einspielen</h2></div>
      <div class="dialog-inhalt">
        <p>Paket <strong data-fuell="zeit"></strong> in
           <strong><?= e((string)$u['email']) ?></strong> einspielen. Vorhandenes bleibt
           stehen — eingespielt wird ergänzend, nicht ersetzend.</p>
        <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Kontos',
                       'pflicht' => true,
                       'attr' => 'autocomplete="off" placeholder="' . e((string)$u['email']) . '"',
                       'klein' => 'Zur Bestätigung abtippen.']); ?>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Einspielen', 'art' => 'primaer']) ?>
      </div>
    </form>
  </dialog>

  <dialog class="dialog" id="dlg-paket-weg">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="paket_loeschen">
      <input type="hidden" name="id" value="<?= $uid ?>">
      <input type="hidden" name="datei" data-fuell="datei">
      <input type="hidden" name="hart" data-fuell="hart">
      <div class="dialog-kopf"><h2>Sicherung löschen</h2></div>
      <div class="dialog-inhalt">
        <p>Paket <strong data-fuell="zeit"></strong> endgültig entfernen.</p>
        <?php if (count($pakete) === 1): ?>
          <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Kontos',
                         'pflicht' => true,
                         'attr' => 'autocomplete="off" placeholder="' . e((string)$u['email']) . '"',
                         'klein' => 'Es ist die letzte Sicherung dieses Kontos — '
                                  . 'zur Bestätigung die Adresse abtippen.']); ?>
        <?php endif; ?>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Löschen', 'art' => 'gefahr']) ?>
      </div>
    </form>
  </dialog>

  <?php if ($pakete): ?>
  <dialog class="dialog" id="dlg-freigeben">
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="freigeben">
      <input type="hidden" name="id" value="<?= $uid ?>">
      <div class="dialog-kopf"><h2>Für ein Zielkonto freigeben</h2></div>
      <div class="dialog-inhalt">
        <p>Die freigegebene Sicherung erscheint im Backup-Bereich des Zielkontos.
           Eingespielt wird sie dort von der NutzerIn selbst, mit ihrem
           Wiederherstellungsschlüssel — die Administration sieht keinen Klartext.</p>
        <?php
        $paketwahl = [];
        foreach ($pakete as $p) { $paketwahl[(string)$p['datei']] = edbak_zeitpunkt_text($p['erzeugt']); }
        ui_feld(['name' => 'datei', 'label' => 'Sicherung', 'art' => 'select',
                 'optionen' => $paketwahl]);
        $zielwahl = ['' => '— Konto wählen —'];
        foreach ($zielkonten as $z) { $zielwahl[(string)$z['id']] = (string)$z['email']; }
        ui_feld(['name' => 'ziel_user', 'label' => 'Zielkonto', 'art' => 'select',
                 'optionen' => $zielwahl, 'pflicht' => true]);
        ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Zielkontos',
                 'pflicht' => true, 'attr' => 'autocomplete="off"',
                 'klein' => 'Zur Bestätigung abtippen — geprüft wird das Ziel, '
                          . 'nicht die Herkunft.']);
        ?>
      </div>
      <div class="dialog-fuss">
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                      'attr' => ' data-dialog-zu']) ?>
        <?= ui_knopf(['text' => 'Freigeben', 'art' => 'primaer']) ?>
      </div>
    </form>
  </dialog>
  <?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/dialog.js']]); ?>
