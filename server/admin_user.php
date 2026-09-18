<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/spur_lib.php';   // Spuren loeschen (F-S2-B)
// Eine Rollenpruefung fuer alle Seiten (M1-15). Hier stand als einziger Stelle
// eine handgeschriebene Fassung mit eigenem Wortlaut ("Nur fuer Admins.").
require_admin();
// Loeschen entscheidet seit Web 5.8.0 auch ueber die Admin-Backups (E25);
// seit Web 9.8.0 liegen die Backups des Kontos ganz hier (E-P3-41).
require_once __DIR__ . '/adminbackup_lib.php';
require_once __DIR__ . '/smtp.php';       // Passwort zuruecksetzen
require_once __DIR__ . '/demo_lib.php';   // Demo-Konto erkennen (S3/AP10)
require_once __DIR__ . '/geraete_lib.php'; // Art und Modell in der Geraeteliste (S6)

/**
 * KONTOSEITE — die Drehscheibe eines Kontos (E-P3-41, P3/O9).
 *
 * WAS SICH GEAENDERT HAT. Bis Web 9.7.2 war diese Seite eine Reihe von
 * Einzelformularen (Rolle, E-Mail, Name — jedes mit eigenem Speichern) und
 * einer Geraetetabelle; die BACKUPS eines Kontos standen woanders, auf
 * admin_sicherungen.php, in einer Tabelle ueber alle Konten. Wer zu einem
 * Konto etwas tun wollte, brauchte zwei Seiten und musste auf der zweiten
 * seine Zeile suchen.
 *
 * Jetzt liegt alles zu EINEM Konto hier: Kontodaten in einem Formular mit
 * einem Speichern, Geraete, Konto-Backups und die
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

/* DAS DEMO-KONTO WIRD ZENTRAL VERWALTET (S3/AP10, E-S3-07).
 *
 * Es entsteht, setzt sich zurueck und verschwindet ueber den Reiter
 * „Demo-Konto“ — und nur dort. Was hier haengenbliebe, waere spaetestens
 * nach dreissig Minuten wieder weg: Der Reset ueberschreibt Konto- und
 * Schluesselmaterial und loescht den ganzen Bestand. Eine Aenderung, die
 * lautlos verfaellt, ist schlimmer als eine, die gar nicht erst geht.
 * Gesichert wird das Konto ebenfalls nicht — der Bestand ist erfunden und
 * liegt als Fixture im Repositorium.
 *
 * DIE SPERRE SITZT HIER, IM SCHREIBWEG, und nicht nur als `disabled` im
 * Markup. Ein `disabled` ist Kulisse: Ein direkt abgesetzter POST geht daran
 * vorbei. Die Anzeige weiter unten graut zusaetzlich aus — damit man es
 * sieht, bevor man es versucht.
 *
 * NICHT gesperrt sind die GERAETE-Aktionen: Das Demo-Konto laedt
 * ausdruecklich zum Koppeln einer Uhr ein, und was dabei entsteht, raeumt
 * der Reset selbst wieder ab. */
const DEMO_GESPERRT = ['konto', 'sichern', 'einspielen', 'freigeben',
                       'widerrufen', 'paket_loeschen', 'user_delete'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');
    if (demo_ist_demo($uid) && in_array($action, DEMO_GESPERRT, true)) {
        $error = 'Das Demo-Konto wird über den Reiter „Demo-Konto“ verwaltet — '
               . 'Anlegen, Zurücksetzen und Entfernen. Hier lässt es sich weder '
               . 'ändern noch sichern: Der Reset überschreibt alle dreißig Minuten '
               . 'Konto und Bestand, und eine Änderung wäre spätestens dann wieder '
               . 'weg. Gesichert wird es nicht — der Bestand ist erfunden.';
        $action = '';
    }
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
        /* DREI ROLLEN, DREI SCHRANKEN (R75, S8/AP1).
         *
         * Bis Web 14.2.2 stand hier ein Zweiwegvergleich und genau eine
         * Schranke: „nicht sich selbst die Admin-Rolle entziehen". Mit der
         * dritten Rolle kommen zwei dazu, und alle drei sitzen SERVERSEITIG —
         * das Auswahlfeld unten blendet aus, was nicht angeboten wird, aber
         * ein POST von Hand kennt das Feld trotzdem.
         *
         *  (1) NUR EINE BETREIBERIN VERGIBT ODER ENTZIEHT DIE ROLLE. Ein
         *      Admin, der 'betreiberin' schickt, koennte sich sonst selbst
         *      hochstufen — und die Rolle waere keine Grenze, sondern eine
         *      Beschriftung. Auch das Entziehen gehoert dazu: Ein Admin darf
         *      eine BetreiberIn nicht zurueckstufen.
         *  (2) NIEMAND ENTZIEHT SICH SELBST DIE VERWALTUNGSRECHTE. Wer das
         *      taete, saehe die Seite, auf der er steht, nach dem Absenden
         *      nicht mehr — und muesste jemand anderen bitten.
         *  (3) DAS LETZTE BETREIBERIN-KONTO BLEIBT EINE BETREIBERIN. Ohne
         *      diese Schranke koennte sich eine Installation aus ihrem
         *      eigenen Betriebsbereich aussperren; der Rueckweg fuehrte ueber
         *      die Datenbank, und den hat auf geteiltem Hosting niemand.
         */
        $role  = rolle_normieren($_POST['role'] ?? '');
        $rolleAlt = rolle_normieren($u['role'] ?? null);
        $teile = [];

        $rollenwechsel = ($role !== $rolleAlt);
        if ($rollenwechsel && !ist_betreiberin()
            && ($role === 'betreiberin' || $rolleAlt === 'betreiberin')) {
            $error = 'Die Rolle „BetreiberIn" vergibt und entzieht nur eine BetreiberIn — '
                   . 'die Rolle wurde nicht geändert.';
        } elseif ($rollenwechsel && $uid === $userId && !rolle_darf_verwalten($role)) {
            $error = 'Du kannst dir nicht selbst die Verwaltungsrechte entziehen — '
                   . 'die Rolle wurde nicht geändert.';
        } elseif ($rollenwechsel && $role !== 'betreiberin'
                  && ist_letzte_betreiberin(db(), $uid, $rolleAlt)) {
            $error = 'Das ist das letzte Konto mit der Rolle „BetreiberIn". '
                   . 'Es lässt sich nicht zurückstufen — lege zuerst eine zweite '
                   . 'BetreiberIn an. Die Rolle wurde nicht geändert.';
        } elseif ($rollenwechsel) {
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
                /* Hinweismail an die ALTE Adresse (Backlog Nr. 128, K-7). Die
                   Verwaltung darf die Anmeldeadresse eines fremden Kontos
                   aendern -- und danach den Setz-Link an die neue schicken.
                   Das ist ein legitimer Weg (jemand hat die Firma gewechselt)
                   und zugleich der kuerzeste Weg zur Kontouebernahme, wenn eine
                   Adminsitzung uebernommen wurde. Die Mail an die alte Adresse
                   ist die einzige Stelle, an der die Besitzerin davon erfaehrt;
                   sie geht deshalb dorthin und nicht an die neue. */
                profil_adresswechsel_melden((string)$u['email'], $email, 'verwaltung');
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
    /* ---- Status: sperren, entsperren, freischalten (P5b/AP2, E-P5b-12) --
     *
     * DREI HANDGRIFFE, EIN ZWEIG. Sie sind dasselbe — ein Statuswechsel —,
     * und `konto_status_setzen()` entscheidet, ob er erlaubt ist; ein
     * Freischalten aus `aktiv` heraus laeuft dort ins Leere statt hier in
     * einen vergessenen `if`-Zweig.
     *
     * DAS EIGENE KONTO NICHT. Wer sich selbst sperrt, sperrt sich aus, und
     * die Sperre laesst sich nur von innen wieder loesen. Dieselbe Schranke
     * wie beim Loeschen eine Karte tiefer.
     *
     * UND NICHT DIE LETZTE BETREIBERIN: Eine Installation ohne zugaengliches
     * Betreiberinnenkonto hat keinen Weg mehr zu Serverschluessel,
     * Migrationen und Wartungsmodus. Derselbe Grund wie beim Loeschen. */
    /* ---- Grenzen und Aufbewahrung je Konto (P5b/AP6, Nr. 37, 48) -------
     *
     * LEER HEISST „die Vorgabe der Installation gilt" — an allen drei
     * Feldern. Nicht 0 und nicht die Vorgabe als Zahl: Traegt die Spalte den
     * Wert, aendert eine spaetere Anhebung der Installationsvorgabe an
     * diesem Konto nichts, und niemand saehe, warum. */
    if ($action === 'konto_grenzen') {
        require_once __DIR__ . '/konten_einstellungen_lib.php';
        $werte = [];
        foreach ([['grenze_einsaetze', 'Einsätze', 1, 1000000],
                  ['grenze_mb',        'Speicher (MB)', 1, 1000000],
                  ['backup_pakete',    'Konto-Backups aufheben', 1, 99]] as [$f, $name, $min, $max]) {
            $roh = trim((string)($_POST[$f] ?? ''));
            if ($roh === '') { $werte[$f] = null; continue; }
            if (!ctype_digit($roh) || (int)$roh < $min || (int)$roh > $max) {
                $error = $name . ': leer lassen für die Vorgabe der Installation, '
                       . 'sonst eine ganze Zahl zwischen ' . $min . ' und ' . $max . '.';
                break;
            }
            $werte[$f] = (int)$roh;
        }
        if ($error === null) {
            db()->prepare('UPDATE users SET grenze_einsaetze = ?, grenze_mb = ?,
                                  backup_pakete = ? WHERE id = ?')
                ->execute([$werte['grenze_einsaetze'], $werte['grenze_mb'],
                           $werte['backup_pakete'], $uid]);
            /* DIE MARKE DER WARNUNG LEEREN. Eine hoehere Grenze macht aus
             * denselben Daten einen anderen Prozentsatz — was bei der alten
             * gemeldet war, ist bei der neuen eine andere Aussage. Dieselbe
             * Ueberlegung wie bei den Speicherschwellen in
             * `betrieb_server.php`. */
            app_state_setzen('mengen_gemeldet:' . $uid, '');
            protokoll('verwaltung', 'konto_grenzen',
                      'Grenzen geändert für ' . (string)$u['email'] . ': '
                    . ($werte['grenze_einsaetze'] ?? 'Vorgabe') . ' Einsätze, '
                    . ($werte['grenze_mb'] ?? 'Vorgabe') . ' MB, Konto-Backups '
                    . ($werte['backup_pakete'] ?? 'Vorgabe'),
                      $werte, $uid);
            $notice = 'Grenzen gespeichert.';
            $st = db()->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([$uid]);
            $u = $st->fetch() ?: $u;
        }
    }

    if ($action === 'konto_status') {
        require_once __DIR__ . '/konto_lib.php';
        $ziel  = (string)($_POST['status'] ?? '');
        $grund = trim((string)($_POST['grund'] ?? ''));
        /* VOR dem Wechsel merken: Danach steht in der Tabelle der neue Wert,
         * und ob dies eine Freischaltung war (`wartet` -> `aktiv`) oder ein
         * Entsperren (`gesperrt` -> `aktiv`), liesse sich nicht mehr
         * unterscheiden. Die Mail geht nur im ersten Fall. */
        $statusVorher = (string)($u['status'] ?? '');

        if ($uid === $userId) {
            $error = 'Das eigene Konto lässt sich hier nicht sperren.';
        } elseif ($ziel === 'gesperrt'
                  && ist_letzte_betreiberin(db(), $uid, $u['role'] ?? null)) {
            $error = 'Das ist das letzte Konto mit der Rolle „BetreiberIn" — es lässt '
                   . 'sich nicht sperren. Lege zuerst eine zweite BetreiberIn an.';
        } elseif (!konto_status_setzen($uid, $ziel, $ziel === 'gesperrt'
                                       ? ($grund !== '' ? $grund : 'von der Verwaltung')
                                       : null)) {
            $error = 'Dieser Wechsel des Kontostatus ist nicht vorgesehen — es wurde '
                   . 'nichts geändert.';
        } else {
            $notice = match ($ziel) {
                'gesperrt' => 'Das Konto ist gesperrt. Laufende Sitzungen enden beim '
                            . 'nächsten Seitenaufruf; Geräte bekommen ab sofort eine '
                            . 'Absage und puffern.',
                'aktiv'    => 'Das Konto ist wieder offen. Gepufferte Gerätedaten kommen '
                            . 'beim nächsten Versuch an.',
                default    => 'Der Kontostatus wurde geändert.',
            };

            /* ---- FREISCHALTUNG: die Nutzerin erfaehrt es (P5b/AP3, E-P5b-02)
             *
             * NUR `wartet` -> `aktiv`. Beim Entsperren (`gesperrt` -> `aktiv`)
             * geht keine Mail: Wer gesperrt war, weiss in aller Regel warum,
             * und eine automatische Nachricht „dein Zugang ist frei" waere
             * dort das falsche Wort. Beim Wartenden ist sie das einzige
             * Zeichen — er hat sich registriert und seither nichts gehoert.
             *
             * OHNE `$notice` ZU AENDERN: Die Verwaltung sieht, dass
             * freigeschaltet ist; ob die Mail durchkommt, entscheidet die
             * Warteschlange und nicht dieser Seitenaufruf. */
            if ($ziel === 'aktiv' && $statusVorher === 'wartet') {
                require_once __DIR__ . '/mail_lib.php';
                mail_einreihen('freigeschaltet', (string)($u['email'] ?? ''),
                               ['link' => app_url('/login.php')]);
                $notice = 'Das Konto ist freigeschaltet. Die Nutzerin bekommt eine '
                        . 'Mail und kann sich ab sofort anmelden.';
            }
            /* Die Zeile neu lesen — die Karte darunter zeigt sonst den
             * Stand von vor dem Klick. */
            $st = db()->prepare('SELECT * FROM users WHERE id = ?');
            $st->execute([$uid]);
            $u = $st->fetch() ?: $u;
        }
    }

    if ($action === 'pw_reset') {
        if (demo_ist_demo($uid)) {
            $error = 'Das Demo-Konto bekommt keinen Setz-Link: Sein Passwort ist '
                   . 'öffentlich und steht im Handbuch (E-P1-19).';
        } else {
            /* Entwerten und Ausstellen in einer Funktion (P5b/AP2,
             * Backlog Nr. 202 Paket 1). Die Regel „hoechstens ein gueltiger
             * Token je Konto" galt hier schon; jetzt gilt sie an allen vier
             * Stellen, und die Stunde steht als `TOKEN_RESET_S` statt als
             * SQL-Literal. */
            require_once __DIR__ . '/konto_lib.php';
            $token = reset_token_ausstellen($uid, TOKEN_RESET_S);
            $link = app_url('/pw_handling.php?token=' . $token);
            /* `passwort_neu`, nicht `passwort_reset`: Der Text der
             * Selbstbedienung endet mit „Falls du das nicht angefordert
             * hast…" — wer die Verwaltung darum gebeten hat, hat es
             * angefordert. Der Unterschied steht im Katalog. */
            $zustellung = mail_einreihen('passwort_neu', (string)$u['email'], ['link' => $link]);
            /* `wartet` zeigt den Link MIT — die Begruendung steht in
             * `admin_users.php` bei derselben Stelle (E-P5a-54). Hier waere
             * sie sogar noch dringender: Dies ist die Seite, auf der jemand
             * landet, WEIL die Einladung nicht angekommen ist. */
            if ($zustellung === MAIL_ZUGESTELLT) {
                $notice = 'Setz-Link an ' . $u['email'] . ' verschickt — eine Stunde gültig.';
            } elseif ($zustellung === MAIL_WARTET) {
                $notice = 'Der Setz-Link ist beim ersten Versuch NICHT hinausgegangen '
                        . 'und steht in der Warteschlange. Er gilt eine Stunde — '
                        . 'so lange nützt auch der Link unten.';
                $setzLink = $link;
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
            $notice = 'Konto-Backup erzeugt.'
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
     * einen Fall, den es nicht gibt: Wer ein Backup in ein FREMDES Konto
     * bringen will, gibt sie frei (unten) oder nimmt den Weg ueber die
     * verwaisten Backups (admin_sicherungen.php) — dort hat das Paket
     * kein Konto mehr, dem es gehoert.
     *
     * edbak_weg() entscheidet trotzdem: Ein Paket aus einem ANDEREN Konto
     * (eingespieltes Fremd-Backup) darf nicht unmittelbar hierher.
     */
    if ($action === 'einspielen') {
        $ziel  = edbak_ziel_konto($uid);
        /* Nur der Kopf: edbak_weg() entscheidet aus dem Manifest (S2/AP6). */
        $paket = edbak_paket_kopf_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Das Paket liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Kontos überein — es wurde nichts eingespielt.';
        } else {
            [$weg, $warum] = edbak_weg($paket, $ziel);
            if ($weg === 'gesperrt') {
                $error = 'Einspielen nicht möglich. ' . $warum;
            } elseif ($weg === 'freigabe') {
                $error = 'Unmittelbares Einspielen ist gesperrt. ' . $warum
                       . ' Bitte stattdessen das Paket für dieses Konto freigeben.';
            } else {
                try {
                    [$okE, $grundE, $bericht] =
                        edbak_paket_zurueckspielen($kennung, $datei, $uid);
                    if ($okE) { $notice = 'Konto-Backup eingespielt.'; }
                    else { $error = (string)$grundE; }
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
        $paket = edbak_paket_kopf_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Das Paket liess sich nicht lesen.';
        } elseif (!edbak_bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts freigegeben.';
        } elseif (edbak_freigeben($kennung, $datei, (int)$ziel['id'])) {
            $notice = 'Freigegeben für ' . $ziel['email'] . '. Die NutzerIn sieht das '
                    . 'Paket jetzt im eigenen Backup-Bereich und spielt es dort '
                    . 'mit ihrem Wiederherstellungsschlüssel ein.';
        } else {
            $error = 'Die Freigabe liess sich nicht speichern.';
        }
    }
    if ($action === 'widerrufen') {
        if (edbak_freigabe_widerrufen($kennung)) { $notice = 'Freigabe widerrufen.'; }
        else { $error = 'Die Freigabe liess sich nicht widerrufen.'; }
    }

    /* ---- Backup loeschen (A8.8) ---------------------------------------
     *
     * Die Haerte der Bestaetigung richtet sich danach, was verlorengeht (E24):
     * Bleibt danach mindestens ein weiteres Backup dieses Kontos, genuegt
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
            $notice = 'Paket gelöscht.';
        } else {
            $error = 'Das Paket liess sich nicht löschen.';
        }
    }

    if ($action === 'user_delete') {
        // Zweite Stufe: die E-Mail-Adresse muss abgetippt werden. Bewusst
        // SERVERSEITIG geprueft — ein Browser-Dialog liesse sich umgehen.
        $eingabe = trim((string)($_POST['confirm_email'] ?? ''));
        if ($uid === $userId) {
            $error = 'Das eigene Konto kann hier nicht gelöscht werden.';
        } elseif (ist_letzte_betreiberin(db(), $uid, $u['role'] ?? null)) {
            /* Dieselbe Zusage wie beim Zurueckstufen (R75), nur der andere
             * Weg dorthin: Eine Installation ohne BetreiberIn hat keinen
             * Zugang mehr zu ihrem Betriebsbereich. */
            $error = 'Das ist das letzte Konto mit der Rolle „BetreiberIn" — es lässt '
                   . 'sich nicht löschen. Lege zuerst eine zweite BetreiberIn an. '
                   . 'Es wurde nichts gelöscht.';
        } elseif (!edbak_bestaetigung_passt($eingabe, (string)$u['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht überein — nichts wurde gelöscht.';
        } else {
            /* ÜBER DIE BACKUPS WIRD AUSDRÜCKLICH ENTSCHIEDEN (E25).
             *
             * Bis Web 5.8.0 sagte der Warntext unbedingt zu, dass nach der
             * Löschung nichts mehr lesbar ist. Sobald Admin-Backups
             * existieren, wäre das unwahr — das Backup überlebt die
             * Löschung und würde zum verwaisten Backup. Genau diese Zusage
             * ist aber der Grund, aus dem jemand eine Löschung verlangt.
             *
             * Umgekehrt ist das Überleben des Backups der Zweck der ganzen
             * Funktion. Beides verträgt sich nur, wenn die Entscheidung
             * sichtbar getroffen wird. Die Vorbelegung folgt der bisherigen
             * Zusage; das Abweichen ist eine bewusste Handlung.
             *
             * Die Backups werden VOR dem Löschen der Zeile entfernt: Danach
             * wäre die Kontokennung fort, und der Ordner liesse sich nur noch
             * über die Übersicht der verwaisten Backups finden. */
            $mitSicherungen = ($_POST['sicherungen_mit'] ?? '1') === '1';
            $sicherungenWeg = false;
            if ($mitSicherungen) {
                $sicherungenWeg = edbak_konto_ordner_loeschen(
                    $kennung !== '' ? $kennung : null);
            }
            if ($mitSicherungen && !$sicherungenWeg) {
                /* Nicht löschen, wenn die Zusage nicht gehalten werden kann.
                 * Ein Konto zu entfernen und das Backup stehen zu lassen,
                 * OBWOHL das Gegenteil gewählt wurde, wäre die schlechteste
                 * der drei möglichen Ausgänge. */
                $error = 'Die Konto-Backups dieses Kontos liessen sich nicht '
                       . 'entfernen — das Konto wurde deshalb NICHT gelöscht. Bitte '
                       . 'unter „Konto-Backups" nachsehen.';
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
                /* Und die Sperrvermerke des Kontos (S4/A2). Sie haengen an
                 * keinem Fremdschluessel — wie die Spuren, aus demselben
                 * Grund und mit demselben Preis. Ein Vermerk nennt einen
                 * Zeitraum, in dem sich jemand aufgehalten hat; das ist ein
                 * Ortsdatum und faellt unter genau die Handlung, die hier
                 * gerade vollzogen wird. */
                schnitte_loeschen($pdoDel, 'konto', [$uid]);
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

$dv = db()->prepare('SELECT id, device_id, label, active, created_at, last_seen,
                            geraet_art, geraet_modell, geraet_teil
                     FROM devices
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

/* WER BEKOMMT DIE FREIGABE? (S8/AP3, B-S8-09) In der Begleitdatei steht nur
 * die Kennung des Zielkontos. Fuer die Zustandszeile braucht es die Adresse —
 * eine Abfrage, und nur dann, wenn ueberhaupt etwas freigegeben ist. Ist das
 * Zielkonto inzwischen geloescht, bleibt der Platz leer statt einer erfundenen
 * Adresse; die Freigabe selbst ist damit wirkungslos und laesst sich
 * widerrufen. */
$freigabeZiel = null;
if ($freigabe && (int)($freigabe['ziel_user'] ?? 0) > 0) {
    $fz = db()->prepare('SELECT email FROM users WHERE id = ?');
    $fz->execute([(int)$freigabe['ziel_user']]);
    $freigabeZiel = $fz->fetchColumn() ?: null;
}

/** Ist das Paket formal lesbar?
 *
 *  Seit S2/AP6 nur noch der KOPF: Bei Fassung 2 ist das das Manifest im ZIP,
 *  ein paar Kilobyte. Vorher wurde je Paket die ganze Datei gelesen und
 *  dekodiert — bei einem grossen Konto also, bei jedem Aufruf dieser Seite,
 *  zweimal 94 MB, nur um zwei Plaketten zu setzen.
 *
 *  Fassung 1 muss weiterhin ganz gelesen werden; ihr Format hat keinen
 *  getrennten Kopf. Solche Pakete verschwinden mit dem ersten neuen Lauf. */
function paket_lesbar(string $kennung, string $datei): bool
{
    return edbak_paket_kopf_lesen($kennung, $datei) !== null;
}

$kennung = (string)($u['account_key'] ?? '');
$rolleText = rolle_text($u['role'] ?? null);
/* Fuer die Anzeige unten: Darf die Angemeldete die Rolle dieses Kontos
 * ueberhaupt aendern, und ist es die letzte BetreiberIn? Beides wird im
 * Schreibweg oben noch einmal geprueft — hier entscheidet es nur, was das
 * Auswahlfeld anbietet und was der Kleintext sagt. */
$istLetzteBetreiberin = ist_letzte_betreiberin(db(), $uid, $u['role'] ?? null);
$rolleGesperrt = $istLetzteBetreiberin
    || (!ist_betreiberin() && rolle_ist_betreiberin($u['role'] ?? null));
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
          data-confirm="Freigabe widerrufen? Die NutzerIn sieht das Paket danach nicht mehr."
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
  /* BEIM DEMO-KONTO BLEIBT NUR DER WEG ZUM REITER (S3/AP10). Ein Aktionsmenü
     voller Einträge, die alle abgewiesen würden, wäre eine Einladung ins
     Leere — und „Passwort zurücksetzen" hat dort seit E-P1-19 ohnehin keinen
     Sinn: Das Passwort des Demo-Kontos ist öffentlich und steht im Handbuch. */
  $istDemoKopf = demo_ist_demo($uid);
  $aktionen = $istDemoKopf
      ? ui_knopf(['text' => 'Zum Demo-Konto', 'symbol' => 'kolben',
                  'art' => 'neutral', 'href' => 'admin_demo.php'])
      : ui_knopf(['text' => 'Jetzt sichern', 'symbol' => 'sicherung',
                  'art' => 'neutral', 'attr' => ' form="f-sichern"']);
  $eintraege = [];
  if (!$istDemoKopf) {
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
  }
  if ($eintraege) {
      $aktionen .= ui_aktionen(['titel' => 'Konto', 'id' => 'konto-aktionen',
                                'eintraege' => $eintraege]);
  }
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
    <?php /* KLEINE STUFE MIT „KOPIEREN" (E-S8-10, Backlog Nr. 78). Der Link
             ist über hundert Zeichen lang; in der grossen Stufe stand er
             gesperrt in Plakatgrösse über drei Zeilen — und ohne Knopf,
             obwohl er zum Weitergeben da ist. */ ?>
    <?= ui_codeblock_lang((string)$setzLink, 'Setz-Link') ?>
  <?php endif; ?>

  <?php if ($bericht): ?>
    <?= ui_meldung_markup('ok', 'Eingespielt: '
        . (int)($bericht['days'] ?? 0) . ' Diensttage, '
        . (int)($bericht['missions'] ?? 0) . ' Einsätze, '
        . (int)($bericht['rest_segments'] ?? 0) . ' Ruhezeiten. '
        . 'Ergänzt, nicht ersetzt — Vorhandenes bleibt stehen.') ?>
  <?php endif; ?>

  <?php $istDemo = demo_ist_demo($uid); ?>
  <?php if ($istDemo): ?>
    <?= ui_meldung_markup('info',
        'Dieses Konto wird über den Reiter „Demo-Konto“ verwaltet: dort wird es '
      . 'angelegt, zurückgesetzt und entfernt. Ändern und Sichern sind hier '
      . 'gesperrt — der Reset überschreibt alle dreißig Minuten Konto und '
      . 'Bestand, und der Bestand ist erfunden.',
        'Demo-Konto.',
        ui_knopf(['text' => 'Zum Demo-Konto', 'art' => 'neutral',
                  'href' => 'admin_demo.php'])) ?>
  <?php endif; ?>

  <div class="form-raster">
  <div class="form-spalte">

    <?php /* ---- Konto: ein Formular, ein Speichern ------------------------ */ ?>
    <?php ui_karte_start(['titel' => 'Konto']); ?>
      <?php /* AUSGEGRAUT BEIM DEMO-KONTO (S3/AP10). Das `disabled` ist die
               ANZEIGE der Sperre, nicht die Sperre selbst — die sitzt oben im
               Schreibweg. Beides zusammen: Man sieht es, bevor man es
               versucht, und ein direkter POST kommt trotzdem nicht durch. */ ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="konto">
        <input type="hidden" name="id" value="<?= $uid ?>">
        <fieldset class="feldsatz-gesperrt" <?= $istDemo ? 'disabled' : '' ?>>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'name', 'label' => 'Name', 'wert' => (string)($u['name'] ?? ''),
                         'attr' => 'maxlength="120" placeholder="z. B. Vorname Nachname"']); ?>
          <?php
            /* ROLLENFELD (R75). Drei Zustaende:
             *
             *   offen      — die Angemeldete darf die Rolle dieses Kontos
             *                aendern; angeboten wird, was rollen_auswahl()
             *                hergibt (BetreiberIn nur fuer eine BetreiberIn).
             *   gesperrt   — das Feld steht in einem eigenen
             *                `.feldsatz-gesperrt`-Feldsatz mit `disabled`, und
             *                ein verstecktes Feld DAHINTER traegt den
             *                unveraenderten Wert mit. Der Feldsatz ist der
             *                vorhandene Baustein fuer genau diesen Zweck
             *                (S3/AP10) — ein blosses `disabled` am Auswahlfeld
             *                waere UNSICHTBAR: `.feld-eingabe` setzt Farbe und
             *                Hintergrund selbst und ueberschreibt damit das,
             *                was der Browser sonst graut. Und OHNE das
             *                versteckte Feld schickte der Browser gar nichts,
             *                der Schreibweg lese daraus „NutzerIn" und
             *                antwortete auf jedes Speichern von Name oder
             *                Adresse mit einer Rollen-Fehlermeldung.
             *   eigenes    — Kleintext wie bisher, aber ohne Sperre: Man darf
             *                sich hochstufen, nur nicht herabstufen (das
             *                faengt der Schreibweg).
             *
             * Das `disabled` ist die ANZEIGE der Regel; die Regel selbst
             * sitzt oben im Schreibweg und faengt auch einen POST von Hand. */
            $rolleKlein = null;
            if ($istLetzteBetreiberin) {
                $rolleKlein = 'Letztes Konto mit dieser Rolle — es lässt sich weder '
                            . 'zurückstufen noch löschen.';
            } elseif ($rolleGesperrt) {
                $rolleKlein = 'Die Rolle „BetreiberIn" ändert nur eine BetreiberIn.';
            } elseif ($istIch) {
                $rolleKlein = 'Die eigenen Verwaltungsrechte lassen sich hier nicht abgeben.';
            }
            $rolleOptionen = rollen_auswahl();
            /* Traegt das Konto eine Rolle, die die Angemeldete gar nicht
             * vergeben darf, steht sie trotzdem im Feld — sonst zeigte das
             * Auswahlfeld eine falsche Rolle an. */
            $rolleWert = rolle_normieren($u['role'] ?? null);
            if (!isset($rolleOptionen[$rolleWert])) {
                $rolleOptionen[$rolleWert] = ROLLEN[$rolleWert];
            }
            $rolleFeld = ['name' => 'role', 'label' => 'Rolle', 'art' => 'select',
                          'wert' => $rolleWert, 'optionen' => $rolleOptionen,
                          'klein' => $rolleKlein];
            if ($rolleGesperrt): ?>
            <fieldset class="feldsatz-gesperrt" disabled><?php ui_feld($rolleFeld); ?></fieldset>
          <?php else:
            ui_feld($rolleFeld);
          endif; ?>
        </div>
        <?php if ($rolleGesperrt): ?>
          <?php /* AUSSERHALB des gesperrten Feldsatzes — ein `disabled`
                   fieldset schickt auch versteckte Felder darin nicht mit. */ ?>
          <input type="hidden" name="role" value="<?= e($rolleWert) ?>">
        <?php endif; ?>
        <?php ui_feld(['name' => 'email', 'label' => 'E-Mail (Anmeldung)', 'art' => 'email',
                       'wert' => (string)$u['email'], 'pflicht' => true]); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        </div>
        </fieldset>
      </form>
      <p class="feld-hinweis">Ein Passwort lässt sich hier nicht setzen: Die Daten sind mit
         dem Passwort der Person Ende-zu-Ende-verschlüsselt. „Passwort zurücksetzen"
         im Aktionsmenü verschickt denselben Link wie „Passwort vergessen" auf der
         Anmeldeseite; entsperrt wird danach mit dem Wiederherstellungsschlüssel
         der Person.</p>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Status (P5b/AP2, E-P5b-12) ---------------------------------
       *
       * EIGENE KARTE UND NICHT EIN FELD IM FORMULAR DARUEBER. Name, Rolle
       * und Adresse sind Angaben ueber ein Konto; der Status ist eine
       * Handlung an ihm, und sie wirkt sofort auf laufende Sitzungen und auf
       * die Geraete. Ein Auswahlfeld neben „Name" liesse sich versehentlich
       * mitspeichern.
       * ------------------------------------------------------------------ */ ?>
    <?php require_once __DIR__ . '/konto_lib.php';
          $kStatus = (string)($u['status'] ?? 'aktiv'); ?>
    <?php ui_karte_start(['titel' => 'Status', 'id' => 'karte-status',
        'plakette' => ui_plakette(KONTO_STATUS[$kStatus] ?? $kStatus,
            ['ton' => match ($kStatus) {
                'aktiv'    => 'blau',
                'gesperrt' => 'rot',
                default    => 'orange',
            }])]); ?>
      <?php if ($kStatus === 'gesperrt' && ($u['gesperrt_grund'] ?? '') !== ''): ?>
        <?php ui_zeile([
            'text'  => ($u['gesperrt_grund'] === 'selbstloeschung')
                     ? 'Löschung beantragt'
                     : 'Gesperrt: ' . (string)$u['gesperrt_grund'],
            'klein' => ($u['gesperrt_seit'] ?? null)
                     ? 'seit ' . fmt_local((string)$u['gesperrt_seit'], 'd.m.Y · H:i') . ' Uhr'
                     : '',
            'plaketten' => ($u['loeschung_am'] ?? null)
                ? ui_plakette('löscht sich am '
                    . fmt_local((string)$u['loeschung_am'], 'd.m.Y'), ['ton' => 'rot'])
                : '']); ?>
      <?php endif; ?>

      <?php if ($istDemo): ?>
        <p class="feld-hinweis">Das Demo-Konto lässt sich hier nicht sperren. Ob die
           Anmeldung daran zugelassen ist, steht unter Betrieb →
           Servereinstellungen → Konten.</p>
      <?php elseif ($uid === $userId): ?>
        <p class="feld-hinweis">Das eigene Konto lässt sich hier nicht sperren — die
           Sperre ließe sich danach nur von einem anderen Konto aus lösen.</p>
      <?php else: ?>
        <?php if ($kStatus === 'wartet' || $kStatus === 'unbestaetigt'): ?>
          <p class="feld-hinweis"><?= e(konto_status_text($kStatus)) ?></p>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="konto_status">
            <input type="hidden" name="id" value="<?= $uid ?>">
            <input type="hidden" name="status" value="aktiv">
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Freischalten', 'symbol' => 'haken', 'art' => 'primaer']) ?>
            </div>
          </form>
        <?php elseif ($kStatus === 'gesperrt'): ?>
          <p class="feld-hinweis">Beim Entsperren verschwindet auch ein
             <strong>beantragter Löschtermin</strong> — das Konto bleibt dann
             bestehen. Gepufferte Gerätedaten kommen beim nächsten Versuch
             vollständig an; es ist nichts verlorengegangen.</p>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="konto_status">
            <input type="hidden" name="id" value="<?= $uid ?>">
            <input type="hidden" name="status" value="aktiv">
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Entsperren', 'symbol' => 'haken', 'art' => 'primaer']) ?>
            </div>
          </form>
        <?php else: ?>
          <p class="feld-hinweis">Eine Sperre beendet laufende Sitzungen beim nächsten
             Seitenaufruf. <strong>Geräte verlieren nichts:</strong> Sie bekommen eine
             Absage, behalten ihre Warteschlange und senden nach dem Entsperren
             alles nach. Der Bestand bleibt unberührt — gelöscht wird nichts.</p>
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="konto_status">
            <input type="hidden" name="id" value="<?= $uid ?>">
            <input type="hidden" name="status" value="gesperrt">
            <?php ui_feld(['name' => 'grund', 'label' => 'Grund',
                'label_zusatz' => 'erscheint im Protokoll, nicht bei der Nutzerin',
                'attr' => 'maxlength="64" placeholder="z. B. auf eigenen Wunsch"',
                'klein' => 'Die Nutzerin sieht nur, dass das Konto gesperrt ist, und den '
                         . 'Hinweis, sich an die Verwaltung zu wenden. Den Grund '
                         . 'hier liest die Verwaltung.']); ?>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => 'Sperren', 'symbol' => 'schloss', 'art' => 'neutral']) ?>
            </div>
          </form>
        <?php endif; ?>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Was das Konto halten darf (P5b/AP6, Nr. 37, 48) ---------- */ ?>
    <?php require_once __DIR__ . '/konten_einstellungen_lib.php';
          $fuell = konto_fuellstand($uid);
          $gr    = konto_grenzen($uid);
          $proz  = (int)round($fuell['anteil'] * 100); ?>
    <?php ui_karte_start(['titel' => 'Mengen und Grenzen', 'id' => 'karte-mengen',
        'plakette' => ui_plakette($proz . ' %', ['ton' => $fuell['voll'] ? 'rot'
                                : ($fuell['warnung'] ? 'orange' : 'blau')])]); ?>
      <?php ui_zeile(['text' => 'Einsätze',
          'klein' => 'ohne die im Papierkorb',
          'plaketten' => ui_plakette($fuell['einsaetze'] . ' von '
                       . $fuell['grenze_einsaetze'], ['ton' => 'neutral'])]); ?>
      <?php ui_zeile(['text' => 'Speicher',
          'klein' => 'Einsätze samt GPS-Daten und Ruhesegmenten, geschätzt',
          'plaketten' => ui_plakette((int)round($fuell['bytes'] / 1048576) . ' von '
                       . (int)round($fuell['grenze_bytes'] / 1048576) . ' MB',
                       ['ton' => 'neutral'])]); ?>
      <form method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="konto_grenzen">
        <input type="hidden" name="id" value="<?= $uid ?>">
        <p class="feld-hinweis"><strong>Leer heißt: die Vorgabe der Installation
           gilt</strong> (<?= (int)konten_grenze_einsaetze() ?> Einsätze,
           <?= (int)konten_grenze_mb() ?> MB). Trägt hier eine Zahl, gilt sie
           <em>statt</em> der Vorgabe — auch wenn die Vorgabe später steigt.</p>
        <div class="fld-reihe">
          <?php ui_feld(['name' => 'grenze_einsaetze', 'label' => 'Einsätze',
              'art' => 'number',
              'wert' => $u['grenze_einsaetze'] !== null ? (string)$u['grenze_einsaetze'] : '',
              'platzhalter' => 'Vorgabe: ' . (int)konten_grenze_einsaetze()]); ?>
          <?php ui_feld(['name' => 'grenze_mb', 'label' => 'Speicher',
              'art' => 'number', 'label_zusatz' => 'MB',
              'wert' => $u['grenze_mb'] !== null ? (string)$u['grenze_mb'] : '',
              'platzhalter' => 'Vorgabe: ' . (int)konten_grenze_mb()]); ?>
        </div>
        <?php require_once __DIR__ . '/adminbackup_lib.php'; ?>
        <?php ui_feld(['name' => 'backup_pakete', 'label' => 'Konto-Backups aufheben',
            'art' => 'number', 'label_zusatz' => 'Pakete',
            'wert' => $u['backup_pakete'] !== null ? (string)$u['backup_pakete'] : '',
            'platzhalter' => 'Vorgabe: ' . edbak_aufbewahrung(),
            'klein' => 'Wie viele Sicherungsstände dieses Kontos aufgehoben werden, '
                     . 'bevor der älteste verdrängt wird. Leer lassen für die Zahl der '
                     . 'Installation. Für ein Konto, dessen Bestand besonders wertvoll '
                     . 'ist, ohne die Zahl für alle anzuheben.']); ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
        </div>
      </form>
    <?php ui_karte_ende(); ?>

    <?php /* ---- Geräte ---------------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Geräte', 'zahl' => (string)count($devices)]); ?>
      <?php if (!$devices): ?>
        <p class="feld-hinweis">Keine Geräte gekoppelt.</p>
      <?php endif; ?>
      <?php foreach ($devices as $d):
        $klein = [];
        /* WAS FUER EIN GERAET (S6/R42) — an erster Stelle, weil es die Frage
           beantwortet, mit der jemand in den Adminbereich kommt: Womit
           arbeitet diese NutzerIn? Der Werdegang (gekoppelt, zuletzt gesehen)
           steht dahinter. */
        $klein[] = geraet_bezeichnung($d['geraet_art'], $d['geraet_modell'], $d['geraet_teil']);
        if (!empty($d['created_at'])) { $klein[] = 'gekoppelt ' . fmt_local($d['created_at'], 'd.m.Y'); }
        $klein[] = 'zuletzt gesehen ' . (!empty($d['last_seen'])
            ? fmt_local($d['last_seen'], 'd.m.Y') : 'nie');
        /* DIE GERAETEKENNUNG BLEIBT SICHTBAR (Mockup 40: „Venu 3S ·
           4F2A…91"). Sie stand bis Web 9.7.2 als eigene Tabellenspalte da
           und ist das Einzige, woran sich eine Uhr in einer Rückfrage
           zweifelsfrei benennen lässt — zwei Geräte können dieselbe
           Bezeichnung tragen. In die Hauptzeile, nicht in die Kleinzeile:
           Sie gehört zum Namen des Geräts, nicht zu seiner Geschichte. */
        /* Seit S6 steht die Rechnung in geraete_lib.php und nicht mehr hier:
           Der Geraete-Reiter braucht dieselbe Kuerzung, und zwei Fassungen
           derselben Kennung liefen frueher oder spaeter auseinander. */
        $kennungKurz = geraet_kennung_kurz((string)$d['device_id']);
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

    <?php /* ---- Backups -----------------------------------------------
             ENTFAELLT BEIM DEMO-KONTO (S3/AP10, E-S3-07): Es wird nicht
             gesichert. Sein Bestand ist erfunden, liegt als Fixture im
             Repositorium und wird alle dreissig Minuten daraus neu
             hergestellt — ein Backup davon waere eine Kopie einer Datei,
             die ohnehin im Git liegt. */ ?>
    <?php if (!$istDemo): ?>
    <?php /* „JETZT SICHERN" IST DIE KARTENAKTION (Mockup 08). Sie stand
             bisher nur als Knopf am Kartenfuss und ein zweites Mal in der
             Titelzeile — am Kartenkopf steht sie dort, wo die Karte sagt,
             worum es geht, und der Fuss bleibt den seltenen Wegen. */ ?>
    <?php ui_karte_start(['titel' => 'Konto-Backups', 'zahl' => (string)count($pakete),
                          'id' => 'k-konto-backups',
                          'plakette' => ui_plakette($standText, ['ton' => $standTon]),
                          'aktion' => ['text' => 'Jetzt sichern', 'symbol' => 'sicherung',
                                       'art' => 'blau', 'form' => 'f-sichern']]); ?>
      <?php if ($kennung === ''): ?>
        <?= ui_meldung_markup('warn', 'Diesem Konto fehlt die Kontokennung. Bitte zuerst '
            . 'unter Betrieb → Updates die Migration ausführen — ohne Kennung lässt '
            . 'sich das Konto nicht sichern.') ?>
      <?php elseif (!$pakete): ?>
        <p class="feld-hinweis">Für dieses Konto gibt es noch kein Konto-Backup.</p>
      <?php endif; ?>

      <?php /* ---- Zustandszeile der Freigabe (B-S8-09) --------------------
           *
           * DIE FREIGABE WAR EIN ZUSTAND OHNE ANZEIGE. Sichtbar war sie nur
           * als Plakette „freigegeben" an einer Paketzeile und als Eintrag
           * „Freigabe widerrufen" im Aktionsmenü — wer nicht danach suchte,
           * sah nicht, dass ein Paket dieses Kontos gerade fuer jemand
           * anderen offensteht. Sie sagt jetzt in einem Satz: fuer wen, seit
           * wann, welches Paket, und was die andere Seite noch tun muss. */ ?>
      <?php if ($freigabe): ?>
        <?php
          $fDatei = (string)($freigabe['datei'] ?? '');
          $fZeit  = null;
          foreach ($pakete as $pk) {
              if ((string)$pk['datei'] === $fDatei) { $fZeit = edbak_zeitpunkt_text($pk['erzeugt']); break; }
          }
          $fSeit = !empty($freigabe['erstellt'])
              ? fmt_local(str_replace(['T', 'Z'], [' ', ''], (string)$freigabe['erstellt']), 'd.m.Y')
              : null;
          /* ui_meldung_markup() maskiert seinen Text — Fettdruck geht nur
             ueber den Auftakt, und der ist genau die eine Angabe, die man
             sucht: fuer WEN. */
          $fAuftakt = 'Freigegeben für '
                    . ($freigabeZiel !== null ? (string)$freigabeZiel : 'ein gelöschtes Konto')
                    . ($fSeit !== null ? ', seit ' . $fSeit : '') . '.';
          $fText = ($fZeit !== null ? 'Das Paket vom ' . $fZeit . '. ' : '')
                 . 'Die NutzerIn spielt es in ihrem eigenen Backup-Bereich mit ihrem '
                 . 'Wiederherstellungsschlüssel ein; die Verwaltung kann das nicht '
                 . 'für sie tun.'
                 . ($freigabeZiel === null
                     ? ' Das Zielkonto gibt es nicht mehr — die Freigabe läuft ins '
                       . 'Leere und kann widerrufen werden.'
                     : '');
        ?>
        <?= ui_meldung_markup('info', $fText, $fAuftakt,
              ui_knopf(['text' => 'Widerrufen', 'art' => 'neutral',
                        'attr' => ' form="f-widerrufen"'])) ?>
      <?php endif; ?>
      <?php foreach ($pakete as $i => $p):
        $istFreigabe = $freigabe && ($freigabe['datei'] ?? '') === $p['datei'];
        $lesbar = paket_lesbar($kennung, (string)$p['datei']);
        $zeit = edbak_zeitpunkt_text($p['erzeugt']);
        /* NUR DER BEFUND TRAEGT EINE PLAKETTE (S8/AP3). „lesbar" stand
           bisher an JEDER Zeile — bei drei Paketen dreimal dasselbe Wort,
           das nie etwas anderes sagt. Dieselbe Ueberlegung wie bei den
           Statuskacheln der NutzerInnen-Liste: Null ist kein Befund. */
        $plaketten = $istFreigabe ? ui_plakette('freigegeben', ['ton' => 'blau']) : '';
        if (!$lesbar) { $plaketten .= ui_plakette('nicht lesbar', ['ton' => 'rot']); }
        /* HART BESTÄTIGEN, WENN ES DIE LETZTE IST (E24). Bleibt ein weiteres
           Paket dieses Kontos stehen, genügt die übliche Rückfrage. */
        $hart = count($pakete) === 1;
        $wPaket = ' data-w-datei="' . e((string)$p['datei'])
                . '" data-w-zeit="' . e($zeit) . '"';
        ui_zeile([
          'text'  => $zeit,
          'klein' => edbak_umfang_text($p),
          'plaketten' => $plaketten,
          /* „Einspielen" als leiser Knopf, „Löschen" eine Ebene tiefer
             (Mockup 08 C, Regel 3). `ui_zeilenaktionen()` zeigte beide ab
             720 px als Knopfreihe — die unumkehrbare Handlung stand damit
             gleichrangig neben der harmlosen. */
          'aktionen' =>
              ui_knopf(['text' => 'Einspielen', 'art' => 'leise', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-einspielen"' . $wPaket])
            . ui_aktionen(['titel' => $zeit, 'id' => 'pa-' . $i, 'eintraege' => [
                  ['text' => 'Paket löschen', 'gefahr' => true, 'href' => '#',
                   'symbol' => 'korb',
                   'attr' => ' data-dialog="dlg-paket-weg"' . $wPaket
                           . ' data-w-hart="' . ($hart ? '1' : '') . '"'],
              ]]),
        ]);
      endforeach; ?>
      <p class="feld-hinweis">Aufbewahrung: die letzten <?= edbak_aufbewahrung() ?> Pakete je
         Konto (Einstellung unter <a href="admin_sicherungen.php">Konto-Backups</a>).
         Das jüngste und ein freigegebenes bleiben immer. Einspielen ergänzt,
         ersetzt nicht; die Verwaltung sieht keinen Klartext.</p>
      <?php if ($pakete): ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Für Zielkonto freigeben', 'symbol' => 'tausch',
                        'art' => 'neutral', 'typ' => 'button',
                        'attr' => ' data-dialog="dlg-freigeben"']) ?>
        </div>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>
    <?php endif; /* !$istDemo */ ?>

    <?php /* DIE KARTE „ABONNEMENT · AB P5" IST FORT (S8/AP3, B-S8-11). Sie
             stand seit Web 9.9.0 als reservierter Platz auf jeder Kontoseite
             und sagte: Tarif, Laufzeit, Zahlungsstand — „kommt mit den
             Abomodellen". Ein Kasten, der auf jeder Kontoseite eine Zusage
             wiederholt, die niemand terminiert hat, ist kein Platzhalter,
             sondern ein Versprechen. R33 steht im Rahmenplan; dort gehoert
             es hin, und die Karte entsteht mit ihrem Inhalt. */ ?>

    <?php /* ---- Gefahrenzone ---------------------------------------------- */ ?>
    <?php ui_karte_start(['titel' => 'Konto löschen', 'klasse' => 'karte-gefahr',
                          'id' => 'karte-loeschen']); ?>
      <?php if ($istDemo): ?>
        <p class="feld-hinweis">Das Demo-Konto wird über den Reiter
           <a href="admin_demo.php">Demo-Konto</a> entfernt — dort steht
           „Demo-Konto entfernen“. Hier ginge derselbe Weg an der Buchführung
           vorbei, die sich merkt, welches Konto das Demo-Konto ist.</p>
      <?php elseif ($istIch): ?>
        <p class="feld-hinweis">Das eigene Konto lässt sich hier nicht löschen.</p>
      <?php elseif ($istLetzteBetreiberin): ?>
        <?php /* Dieselbe Zusage wie am Rollenfeld (R75) — hier gesagt, wo man
                 die Handlung versucht, und nicht erst als Fehlermeldung
                 danach. Der Schreibweg faengt es trotzdem noch einmal. */ ?>
        <p class="feld-hinweis">Das ist das letzte Konto mit der Rolle
           <strong>BetreiberIn</strong>. Es lässt sich nicht löschen — sonst hätte
           diese Installation niemanden mehr, der ihren Bereich <em>Betrieb</em>
           öffnen kann: Serverbetrieb, Updates, Hintergrundjobs, Speicher,
           Komplett-Backup und Backup-Ziele. Lege zuerst eine zweite BetreiberIn
           an; danach lässt sich dieses Konto löschen.</p>
      <?php else: ?>
        <p class="feld-hinweis">Entfernt Konto, Diensttage, Einsätze, Tracks, Reanimationen
           und Geräte <strong>endgültig</strong> — ohne Papierkorb, nicht rückgängig zu
           machen. Ob danach nichts mehr lesbar ist, hängt von der Wahl unten ab:
           Bleiben die Pakete erhalten, überleben sie die Löschung und erscheinen
           unter <a href="admin_sicherungen.php">Konto-Backups</a> als „Backup ohne
           Konto".</p>
        <form method="post" data-confirm="Konto endgültig löschen?"
              data-confirm-ok="Endgültig löschen">
          <?= csrf_field() ?><input type="hidden" name="action" value="user_delete">
          <input type="hidden" name="id" value="<?= $uid ?>">
          <?php ui_feld(['name' => 'sicherungen_mit', 'label' => 'Konto-Backups dieses Kontos',
                         'art' => 'select', 'wert' => '1', 'optionen' => [
                             '1' => 'mitlöschen (Vorgabe)',
                             '0' => 'erhalten — erscheinen als „Backup ohne Konto"']]); ?>
          <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse',
                         'pflicht' => true,
                         'attr' => 'autocomplete="off" placeholder="' . e((string)$u['email']) . '"',
                         'klein' => 'Zur Bestätigung die Adresse des Kontos abtippen.']); ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Konto endgültig löschen', 'symbol' => 'korb',
                          'art' => 'gefahr']) ?>
          </div>
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
      <div class="dialog-kopf"><h2>Konto-Backup einspielen</h2></div>
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
      <div class="dialog-kopf"><h2>Paket löschen</h2></div>
      <div class="dialog-inhalt">
        <p>Paket <strong data-fuell="zeit"></strong> endgültig entfernen.</p>
        <?php if (count($pakete) === 1): ?>
          <?php ui_feld(['name' => 'confirm_email', 'label' => 'E-Mail-Adresse des Kontos',
                         'pflicht' => true,
                         'attr' => 'autocomplete="off" placeholder="' . e((string)$u['email']) . '"',
                         'klein' => 'Es ist das letzte Paket dieses Kontos — '
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
        <p>Das freigegebene Paket erscheint im Backup-Bereich des Zielkontos.
           Eingespielt wird es dort von der NutzerIn selbst, mit ihrem
           Wiederherstellungsschlüssel — die Verwaltung sieht keinen Klartext.</p>
        <?php
        $paketwahl = [];
        foreach ($pakete as $p) { $paketwahl[(string)$p['datei']] = edbak_zeitpunkt_text($p['erzeugt']); }
        ui_feld(['name' => 'datei', 'label' => 'Paket', 'art' => 'select',
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
<?php ui_seite_ende(['skripte' => ['assets/dialog.js', 'assets/kopieren.js']]); ?>
