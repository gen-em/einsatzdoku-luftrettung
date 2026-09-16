<?php
declare(strict_types=1);
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/instanz_lib.php';
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/demo_lib.php';

/* HTTPS ZUERST (P5a/AP4, E-P5a-16). Diese Seite ist die, auf der es
 * auffaellt: Ihr Sitzungscookie traegt `secure`, und ueber HTTP sendet der
 * Browser es nicht — das Formular kaeme zurueck, als waere das Passwort
 * falsch. `https_tor()` sagt stattdessen, was los ist. Vor `session_start()`,
 * damit gar nicht erst eine Sitzung ohne Cookie entsteht. */
https_tor();

// Zeitpunkt fuer die konstante Antwortdauer im Fehlerzweig — muss VOR jeder
// Arbeit stehen, sonst misst er nicht die ganze Anfrage.
$t0 = microtime(true);
session_set_cookie_params(['httponly' => true, 'secure' => true, 'samesite' => 'Strict', 'path' => '/']);
/* `use_strict_mode` VOR `session_start()` (P5a/AP4a, E-P5a-38, Backlog
 * Nr. 205). Ohne das uebernimmt PHP eine Sitzungskennung, die der Browser
 * mitbringt, auch wenn es sie nie vergeben hat — wer eine Kennung setzen
 * kann (ueber einen Link, eine fremde Seite auf derselben Domain, ein
 * gesetztes Cookie), kennt damit die Sitzung, in der sich gleich jemand
 * anmeldet. Das ist Session-Fixation, und der Schutz dagegen hing bis
 * Web 20.9.1 an der `php.ini` des Hosters.
 *
 * `install.php` und `wiederherstellen.php` setzten die Zeile seit jeher —
 * ausgerechnet die beiden Wege, die KEINE Anmeldesitzung tragen. */
ini_set('session.use_strict_mode', '1');
session_start();

if (!empty($_SESSION['user_id'])) { header('Location: index.php'); exit; }

/* Grund des Sitzungsendes anzeigen.
 *
 * Wer nach Ablauf der Frist weiterarbeiten wollte, landete bisher OHNE JEDE
 * ERKLAERUNG auf dieser Seite: Der Ablaufpfad haengte ?timeout=1 an, und diese
 * Seite wertete den Parameter nicht aus. Aus Sicht der NutzerIn verschwand die
 * Anwendung einfach. Der alte Parametername wird weiter erkannt, damit ein
 * offener Tab mit alter Adresse nicht ins Leere laeuft. */
$hinweis = session_ende_text($_GET['ende'] ?? null);
if ($hinweis === '' && isset($_GET['timeout'])) { $hinweis = session_ende_text('abgelaufen'); }

/* DIE VERLANGSAMUNG SAGT SICH AN (E-P5a-06, P5a/AP6).
 *
 * Wer die Seite oeffnet, waehrend die Bremse laeuft, soll wissen, warum das
 * Anmelden gleich lange dauert — sonst haelt er die Anwendung fuer kaputt und
 * klickt noch einmal, was die Lage genau nicht verbessert.
 *
 * RUHIG UND OHNE DAS WORT ANGRIFF. Das Konzept sagt es ausdruecklich, und es
 * hat recht: Auf dieser Seite steht jemand, der zum Dienst will. „Wird ganz
 * normal geprueft" ist der Satz, auf den es ankommt.
 *
 * Steht schon ein Fehler an, tritt der Hinweis zurueck (ui_meldung unten) —
 * die Fehlermeldung ist dann die naehere Auskunft.
 *
 * $sperreRest wird weiter unten im gesperrten Zweig gesetzt und steuert den
 * Countdown; hier steht die Vorbelegung, damit die Ansicht ihn immer kennt. */
$sperreRest = 0;
$bremse = rate_verlangsamung();
if ($hinweis === '' && $bremse['stufe'] > 0) {
    $hinweis = 'Die Anmeldung antwortet derzeit verzögert, etwa '
             . rtrim(rtrim(number_format($bremse['sekunden'], 1, ',', ''), '0'), ',')
             . ' Sekunden. Das ist eine Schutzmaßnahme; dein Passwort wird '
             . 'ganz normal geprüft.';
}

/* ---- Anmeldung ------------------------------------------------------------
 *
 * DIE BREMSE LAG FRUEHER IN DER SITZUNG DES AUFRUFERS. Fuenf Fehlversuche,
 * dann dreissig Sekunden Pause — gezaehlt in $_SESSION. Wer das Cookie
 * wegwarf, hatte wieder fuenf Versuche frei; ein Skript, das gar kein Cookie
 * annimmt, hatte nie eines verbraucht. Das war keine Bremse, sondern eine
 * Bequemlichkeit gegen Vertippen. Gezaehlt wird jetzt in der Datenbank, je
 * Kontokennung UND je IP-Adresse (ratelimit_lib.php).
 *
 * ZWEI EIGENSCHAFTEN, DIE HIER DIE ARBEIT MACHEN
 *   1. Die Sperre greift VOR der Abfrage und vor bcrypt. Sonst kann ein
 *      Gesperrter den Server weiter rechnen lassen.
 *   2. Der Fehlerzweig dauert immer gleich lang. Bei unbekannter Adresse lief
 *      frueher gar keine Passwortpruefung, bei bekannter eine bcrypt-Pruefung
 *      — der Unterschied sagte, welche Adressen es gibt. Deshalb der
 *      Vergleich gegen einen festen Wert plus rate_gleiche_dauer().
 *
 * BEWUSST IN KAUF GENOMMEN: Wer eine Adresse kennt, kann das Konto durch
 * Fehlversuche zeitweise sperren. Die Sperre ist kurz und die Meldung nennt
 * ihr Ende; die Alternative — nur nach IP zaehlen — liesse ein verteiltes
 * Durchprobieren einer einzelnen Adresse voellig ungebremst.
 */
$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !csrf_ok()) {
    /* ---- Login-CSRF (Backlog Nr. 127, K-8) ------------------------------
     *
     * DAS ANMELDEFORMULAR WAR DAS EINZIGE OHNE TOKEN. Eine fremde Seite konnte
     * einen abgemeldeten Browser per Top-Level-POST in ein ANGREIFERKONTO
     * anmelden — Adresse und Token des Angreifers im Formular, Absenden per
     * Skript. Die Patientenfelder sind davon nicht betroffen (ohne `edk` oeffnet
     * sich keine fremde Huelle), aber was danach eingegeben wird, landet im
     * fremden Konto und ist dort lesbar.
     *
     * Die Sitzung besteht schon beim GET (Zeile 13), das Token liegt also vor.
     *
     * KEINE RATENSTRAFE. Der Zaehler (`rate_erlaubt`) bleibt unberuehrt: Ein
     * abgelaufenes Formular ist kein Fehlversuch, und wer sich nach einer
     * Mittagspause anmeldet, darf dafuer nicht gesperrt werden. Aus demselben
     * Grund steht diese Pruefung VOR allen Zaehlern.
     *
     * KEINE 403-SEITE, sondern die Anmeldeseite mit einer Meldung — auf einer
     * Seite, die man gerade ausgefuellt hat, ist eine Fehlerseite die falsche
     * Antwort. Ein neues Token liefert dieselbe Seite gleich mit. */
    $error = 'Das Formular ist abgelaufen. Bitte versuche es erneut.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* Eine Schreibweise fuer alle Stellen (M1-13, email_lib.php). Hier stand
     * bisher nur trim(): Dass die Anmeldung trotzdem funktionierte, lag allein
     * an der Sortierregel der Datenbank. Nebenbei behoben: rate_erfolg('salt',
     * ...) unten meldete den Erfolg mit der Adresse WIE GETIPPT, waehrend
     * auth_salt.php unter der kleingeschriebenen zaehlt — wer "Max@..." tippte,
     * leerte seinen Salz-Zaehler nie. */
    $email = email_normalisieren($_POST['email'] ?? '');

    /* Mengenbremse des Demo-Kontos (E-P1-20).
     *
     * VOR der teuren Pruefung, wie jede Bremse hier — sonst bliebe der
     * Rechenaufwand als Angriffsflaeche offen.
     *
     * Sie haengt an der ADRESSE, nicht am Konto: Zu diesem Zeitpunkt ist noch
     * nicht nachgeschlagen, wer sich anmeldet, und das soll auch so bleiben
     * (der Zweig „Adresse unbekannt" darf nicht schneller sein als der
     * andere). Die Adresse des Demo-Kontos ist ohnehin oeffentlich — hier
     * verraet der Vergleich also nichts, was nicht im Handbuch steht.
     *
     * Die Meldung nennt den Grund: „zu viele Anmeldeversuche" waere hier
     * schlicht falsch — es hat niemand etwas falsch gemacht. */
    $istDemoAdresse = demo_ist_demo_adresse($email);
    if ($istDemoAdresse && !rate_demo_erlaubt()) {
        $bis = rate_demo_gesperrt_bis();
        $error = 'Das Demo-Konto wird gerade sehr häufig genutzt und ist '
               . 'vorübergehend gesperrt'
               . ($bis !== null ? ' — wieder ab ' . fmt_local($bis, 'H:i') . ' Uhr.' : '.')
               . ' Ein eigenes Konto ist davon nicht betroffen.';
        rate_gleiche_dauer($t0);
    } elseif (!rate_erlaubt('login', $email) || !rate_erlaubt('login_ip', null)) {
        /* ZWEI TOEPFE, EINE MELDUNG — aber die Meldung sagt, WELCHER greift
         * (P5a/AP6). „Für diesen Namen gesperrt" waere schlicht falsch, wenn
         * in Wahrheit die Adresssperre haelt, und wer den Unterschied nicht
         * liest, wartet auf das Falsche.
         *
         * SIE ERSCHEINT FUER ERFUNDENE ADRESSEN GENAUSO WIE FUER ECHTE. Das
         * Merkmal ist der EINGETIPPTE Name (`rate_merkmale()`), nicht die
         * Kontozeile — deshalb gibt die Sperre keine Kontoauskunft, und
         * deshalb darf sie ihn nennen. */
        $sperre = rate_sperre('login', $email) ?? rate_sperre('login_ip', null);
        $bis = $sperre['bis'] ?? null;
        $error = ($sperre !== null && $sperre['art'] === 'adresse'
                    ? 'Zu viele Anmeldeversuche von diesem Anschluss.'
                    : 'Zu viele Anmeldeversuche für diesen Namen.')
               . ($bis !== null
                    ? ' Wieder ab ' . fmt_local($bis, 'H:i') . ' Uhr.'
                    : ' Bitte später erneut versuchen.')
               . ' „Passwort vergessen?" geht weiterhin.';
        /* Der Countdown braucht SEKUNDEN, keinen Zeitstempel — siehe
         * `rate_sperre()`. Er geht unten in ein data-Attribut. */
        $sperreRest = $sperre['rest'] ?? 0;

        /* KEINE VERLANGSAMUNG IM GESPERRTEN ZWEIG, und das ist Selbstschutz:
         * Jede wartende Anfrage haelt einen PHP-Arbeitsprozess. Wer gesperrt
         * ist, wird sofort abgewiesen — damit haengt die Zahl der Wartenden
         * an der Sperrrate und nicht an der Flutrate (Kopf von
         * ratelimit_lib.php). */
        rate_gleiche_dauer($t0);
    } else {
        // Der Browser sendet nie das Passwort, sondern das daraus
        // abgeleitete Token (siehe assets/crypto.js).
        /* `role` seit S5 Paket W: Im Wartungsmodus entscheidet die Rolle,
         * ob die Anmeldung Bestand hat (E-S5W-09). Sie wandert NICHT in die
         * Sitzung — das war M1-05, und daran aendert sich nichts; sie wird
         * hier einmal gelesen und danach vergessen. auth_guard.php liest sie
         * weiterhin bei jeder Anfrage neu. */
        $st = db()->prepare('SELECT id, password_hash, session_epoch, kdf_iter, logo_wahl, role,
                                    status, gesperrt_grund
                             FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        /* ---- Ein Token je Rundenzahl (M2-01, Schritt 3) -------------------
         *
         * Der Salz-Endpunkt nennt jeder Adresse dieselbe Liste von
         * Rundenzahlen, damit er nicht verraet, welche Konten es gibt. Der
         * Browser kann daher nicht wissen, welche fuer dieses Konto gilt — er
         * leitet fuer JEDE ab und schickt alle Token mit.
         *
         * DER SERVER WEISS ES und sucht sich das passende heraus. Das ist der
         * Grund, warum hier kein Durchprobieren stattfindet: Es gibt genau
         * EINE bcrypt-Pruefung, wie zuvor auch.
         *
         * Format: {"<runden>": "<64 Hexzeichen>", ...}. Streng geprueft, weil
         * es unangemeldet hereinkommt. Das alte Feld 'token' wird weiterhin
         * angenommen — ein Browser mit zwischengespeichertem alten Skript
         * schickt es noch, und die Anmeldung soll daran nicht scheitern.
         */
        $tokenNach = [];
        $roh = json_decode((string)($_POST['tokens'] ?? ''), true);
        if (is_array($roh)) {
            foreach ($roh as $runde => $tk) {
                $r = (int)$runde;
                /* Keine Pruefung gegen eine Liste erlaubter Werte: Der Server
                 * greift gleich unten NUR den Eintrag heraus, der zur
                 * gespeicherten Rundenzahl des Kontos gehoert. Ein Token unter
                 * einem beliebigen anderen Schluessel wird nie angesehen. */
                if ($r > 0 && is_string($tk) && preg_match('/^[0-9a-f]{64}$/', $tk)) {
                    $tokenNach[$r] = $tk;
                }
            }
        }
        if (isset($_POST['token']) && preg_match('/^[0-9a-f]{64}$/', (string)$_POST['token'])) {
            // Altes Feld: es galt immer die frueher fest verdrahtete Zahl.
            $tokenNach[310000] = $tokenNach[310000] ?? (string)$_POST['token'];
        }

        if ($u && $u['password_hash'] !== null) {
            $konto = (int)($u['kdf_iter'] ?? 0) ?: 310000;
            $token = $tokenNach[$konto] ?? '';
            $ok = $token !== '' && password_verify($token, $u['password_hash']);
        } else {
            /* Unbekannte Adresse oder Konto ohne gesetztes Passwort: trotzdem
             * eine bcrypt-Rechnung, damit dieser Zweig nicht schneller ist.
             *
             * DER VERGLEICHSWERT WIRD GEPRUEFT, NICHT GEGLAUBT (Backlog
             * Nr. 93). Er traegt eine feste Rundenzahl (db.php), und die
             * Vorgabe von PASSWORD_DEFAULT waechst mit den PHP-Fassungen —
             * von Web 5 bis 19.1.1 stand er auf 10, waehrend PHP 8.4 laengst
             * 12 anlegte, und der blinde Zweig war damit viermal schneller
             * als der bekannte. password_needs_rehash() kostet nichts
             * (0,0000 ms ueber 2000 Laeufe) und faengt genau das ab: Passt
             * die Konstante, bleibt es beim billigen Vergleich; passt sie
             * nicht, kostet ein password_hash() mit der Vorgabe genau so
             * viel wie die Pruefung im Gegenzweig.
             *
             * NICHT BEIDES. Hashen UND danach pruefen macht diesen Zweig um
             * 234 ms LANGSAMER als den anderen und sprengt die Mindestdauer
             * — das Leck waere dann umgedreht. */
            if (password_needs_rehash(AUTH_VERGLEICHSWERT, PASSWORD_DEFAULT)) {
                password_hash(reset($tokenNach) ?: '', PASSWORD_DEFAULT);
            } else {
                password_verify(reset($tokenNach) ?: '', AUTH_VERGLEICHSWERT);
            }
            $ok = false;
        }

        if ($ok) {
            /* ---- Zaehler leeren, BEVOR ueber den Zugang entschieden wird ----
             *
             * Beide Aufrufe standen bisher weiter unten. Sie stehen jetzt hier
             * oben, weil der Wartungsmodus gleich darunter abbrechen kann und
             * das Passwort trotzdem RICHTIG war: Wer waehrend einer Wartung
             * dreimal richtig tippt, darf sich danach nicht ausgesperrt finden
             * (E-S5W-09 b). Der Demo-Zaehler bleibt unten — er zaehlt die
             * NUTZUNG des Demo-Kontos, und wer sofort wieder abgemeldet wird,
             * hat es nicht benutzt. */
            rate_erfolg('login', $email);
            // Auch den Zaehler des Salz-Endpunkts leeren — jede Anmeldung
            // verbraucht dort einen Versuch, und wer sich erfolgreich
            // anmeldet, soll sich nicht selbst aussperren.
            rate_erfolg('salt', $email);
            /* UND DEN ADRESSTOPF (P5a/AP6). `login_ip` zaehlt 50 Fehlversuche
             * je Adresse — hinter einem Klinik-NAT teilen sich viele eine.
             * Ohne diese Zeile liefe eine Praxis ueber den Tag in ihre 50
             * hinein, ohne dass irgendjemand etwas falsch gemacht haette:
             * Jede gelungene Anmeldung ist der Beweis, dass die Adresse kein
             * Angreifer ist, und genau dafuer gibt es `rate_erfolg()` seit
             * jeher auch fuer die IP. */
            rate_erfolg('login_ip', null);

            /* ---- WARTUNGSMODUS: nur die Verwaltung kommt hinein (E-S5W-09) --
             *
             * Die Entscheidung des Auftraggebers vom 03.09.2026, abweichend
             * von der Empfehlung des Konzepts: Ein Nicht-Admin-Konto bekommt
             * KEINE Sitzung, die die Wartung ueberdauert. Damit liegt waehrend
             * des Umbaus kein entsperrter Inhaltsschluessel herum, und keine
             * Anmeldung schreibt in `users` (`last_login`, gleich darunter),
             * waehrend `update.php` das Schema aendert.
             *
             * DIE STELLE IST WICHTIG. Der Zweig haengt am ERFOLG des
             * Passwortvergleichs, nicht am Vergleich selbst — die
             * Antwortgleichheit des Fehlerzweigs (rate_gleiche_dauer, ganz
             * unten) bleibt unberuehrt, und ein Angreifer erfaehrt hier
             * nichts, was er nicht schon wuesste: Er hat das Passwort.
             *
             * UND ES IST NICHT DAS ANMELDEFORMULAR, das danach erscheint.
             * Wer hier landete und wieder die Maske saehe, laese das als
             * „Passwort falsch" und tippte weiter — bis der Ratenschutz
             * zuschlaegt. Es ist die Wartungsseite, und die sagt, was los
             * ist. */
            /* ---- KONTOSTATUS (P5b/AP2, E-P5b-12, E-P5b-16) ----------------
             *
             * DIE STELLE IST DIESELBE ABWAEGUNG WIE BEIM WARTUNGSMODUS
             * darunter: Der Zweig haengt am ERFOLG des Passwortvergleichs
             * und nicht am Vergleich selbst. Die Antwortgleichheit des
             * Fehlerzweigs bleibt damit unberuehrt, und ein Angreifer
             * erfaehrt hier nichts, was er nicht schon wuesste — er hat das
             * Passwort.
             *
             * DIE SELBSTLOESCHUNG IST DER SONDERFALL, UND ZWAR DER WICHTIGE:
             * Waehrend der Karenz steht das Konto auf `gesperrt`, aber
             * **die Anmeldung IST der Rueckzug** (E-P5b-16). Wer sich
             * anmeldet, will sein Konto behalten. Es hier abzuweisen hiesse,
             * den einen Weg zu versperren, der aus der Loeschung
             * herausfuehrt — und danach loescht der Job.
             *
             * `konto_status_setzen()` schreibt den Protokolleintrag und
             * raeumt `loeschung_am` mit weg; beides steht dort, damit es
             * nicht an zwei Stellen steht. */
            $kStatus = (string)($u['status'] ?? 'aktiv');
            if ($kStatus === 'gesperrt'
                && ($u['gesperrt_grund'] ?? null) === 'selbstloeschung') {
                require_once __DIR__ . '/konto_lib.php';
                konto_status_setzen((int)$u['id'], 'aktiv');
                $kStatus = 'aktiv';
                $rueckzug = true;
            }
            if ($kStatus !== 'aktiv') {
                require_once __DIR__ . '/konto_lib.php';
                session_verwerfen();
                /* KEINE SITZUNG, und eine eigene Seite statt des Formulars —
                 * derselbe Weg, den der Wartungsmodus eine Zeile darunter
                 * geht, und aus demselben Grund (Backlog Nr. 126): Wer hier
                 * landete und wieder die Anmeldemaske saehe, laese das als
                 * „Passwort falsch" und tippte weiter, bis der Ratenschutz
                 * zuschlaegt. Das Passwort war richtig.
                 *
                 * `stoerung_seite_html()` ist dasselbe Geruest, das Wartung
                 * und Ausgelastet benutzen (P5a/AP9) — kein neuer Baustein
                 * (Design.md 9), nur ein dritter Aufrufer.
                 *
                 * DIE ANTWORTDAUER WIRD TROTZDEM ANGEGLICHEN. Sonst waere
                 * ein gesperrtes Konto an der Antwortzeit zu erkennen — und
                 * zwar von jemandem, der das Passwort hat, also genau von
                 * dem, vor dem die Sperre schuetzen soll. */
                rate_gleiche_dauer($t0);
                require_once __DIR__ . '/wartung_lib.php';
                header('Content-Type: text/html; charset=utf-8');
                echo stoerung_seite_html(
                    'Kein Zugang — ' . instanz_kurz(),
                    '<h1>' . htmlspecialchars(KONTO_STATUS[$kStatus], ENT_QUOTES)
                  . '</h1><p class="text">'
                  . htmlspecialchars(konto_status_text($kStatus,
                        $u['gesperrt_grund'] ?? null), ENT_QUOTES)
                  . '</p><p class="text"><a href="login.php">Zurück zur Anmeldung</a></p>');
                exit;
            }

            if (wartung_aktiv() && !rolle_darf_verwalten($u['role'] ?? null)) {
                session_verwerfen();
                /* OHNE RUECKWEG (Backlog Nr. 126). Das ist die einzige Stelle,
                 * an der wir die Rolle KENNEN — und sie reicht nicht: Hinter
                 * `betrieb_updates.php` steht `require_betreiberin()`. Ein
                 * Knopf, der hier steht, fuehrt garantiert auf ein 403. */
                wartung_antwort_seite(false);
            }

            session_regenerate_id(true);
            /* Auch das Formular-Token wird neu gezogen (Backlog Nr. 127). Die
               Sitzungskennung wechselt eine Zeile darueber gegen die
               Sitzungsuebernahme; ein Token, das der Angreifer vor der
               Anmeldung gesetzt hat, ueberlebte diesen Wechsel sonst. */
            unset($_SESSION['csrf']);
            $_SESSION['user_id'] = (int)$u['id'];
            /* Stand des Sitzungszaehlers mitfuehren (M1-09). Jede Anfrage
             * vergleicht ihn in auth_guard.php gegen die Zeile; ein
             * Passwortwechsel erhoeht ihn und beendet damit alle Sitzungen,
             * die noch den alten Stand tragen. */
            $_SESSION['epoch']   = (int)($u['session_epoch'] ?? 0);
            /* Die Rolle wird NICHT mehr hier abgelegt (M1-05). Sie kam
             * frueher aus dieser einen Zeile und wurde nie wieder geprueft —
             * ein Rollenentzug wirkte erst nach dem naechsten Anmelden.
             * auth_guard.php liest sie jetzt bei jeder Anfrage aus der
             * Nutzerzeile, die dort ohnehin gelesen wird. */
            // Alte Sitzungsbremse aufraeumen: Auf Rechnern, die vor dieser
            // Fassung angemeldet waren, liegen die beiden Werte noch herum.
            unset($_SESSION['login_fails'], $_SESSION['login_last'], $_SESSION['role']);
            /* LOGO-WAHL EINMAL AUFLOESEN (E-P3-20). Bei „wechselnd" faellt
               hier der Wuerfel — je Anmeldung, nicht je Seitenaufruf; sonst
               spraenge das Logo beim Blaettern. */
            logo_sitzung_setzen($u['logo_wahl'] ?? '');
            /* ZULETZT ANGEMELDET (E-P3-41). Die einzige Stelle, an der der
               Wert geschrieben wird — nicht bei jedem Seitenaufruf. Ein
               Fehlschlag darf die Anmeldung nicht aufhalten: Der Wert ist
               eine Auskunft fuer die Administration, kein Teil des Zugangs.
               Solange die Migration nicht gelaufen ist, gibt es die Spalte
               nicht; dann bleibt es beim Fangen. */
            try {
                db()->prepare('UPDATE users SET last_login = NOW() WHERE id = ?')
                    ->execute([(int)$u['id']]);
            } catch (Throwable) {
                // Spalte fehlt (Migration steht aus) — ohne Folgen.
            }
            /* Die Demo-Bremse zaehlt GELUNGENE Anmeldungen. Kein Widerspruch
             * zu den beiden rate_erfolg()-Aufrufen oben: Jene betreffen den
             * Fehlversuchsschutz des Kontos, dieser die Nutzungsmenge des
             * Demo-Kontos. */
            if ($istDemoAdresse) { rate_demo_zaehlen(); }
            header('Location: index.php'); exit;
        }
        /* DREI ZAEHLUNGEN AN EINEM FEHLVERSUCH (P5a/AP6):
         *   `login`     das eingetippte Konto — 10 je 15 min
         *   `login_ip`  der Anschluss        — 50 je 15 min
         *   `global`    die Installation     — Grundlage der Verlangsamung
         *
         * `rate_misserfolg('login', $email)` zaehlt seit jeher BEIDE Merkmale
         * dieses Topfes (Konto und IP). Das bleibt so — der zweite Topf ist
         * nicht sein Ersatz, sondern die ZWEITE SCHWELLE: 10 je Konto ist
         * richtig, 10 je Adresse waere es nicht. */
        rate_misserfolg('login', $email);
        rate_misserfolg('login_ip', null);
        rate_global_misserfolg();

        $error = 'Anmeldung fehlgeschlagen. E-Mail oder Passwort prüfen.';

        /* HIER GREIFT DIE VERLANGSAMUNG, und nur hier: Es ist der einzige
         * Zweig, in dem tatsaechlich gerechnet wurde (PBKDF2 im Browser,
         * bcrypt hier). */
        $bremseStufe = rate_gleiche_dauer_gebremst($t0);
        if ($bremseStufe > 0) {
            sicherheit_melden_pruefen();
        }
    }
}
require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start(['titel' => 'Anmelden', 'klasse' => 'anmeldung-body']);
?>
<main class="anmeldung">
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel"><?= e(instanz_kurz()) ?></h1>
  <p class="anmeldung-unter">Einsatzdokumentation Notarzt</p>
  <?php /* Der Wartungsbalken (S5 Paket W, Konzept 4.5). Er steht UEBER der
           Meldung und nicht darunter: Wer hier ankommt, waehrend die Wartung
           laeuft, soll wissen, warum die Anwendung sonst nicht antwortet,
           BEVOR er sein Passwort eintippt. Anmelden kann er sich trotzdem —
           was danach geschieht, entscheidet die Rolle (E-S5W-09). */ ?>
  <?= wartung_balken() ?>
  <?php /* Beide schliessen einander aus: Steht ein Fehler an, tritt der
           Hinweis zurueck. Die Reihenfolge in ui_meldung() ist deshalb
           ohne Wirkung. */ ?>
  <?php ui_meldung($error ? null : $hinweis, $error); ?>
  <form method="post" autocomplete="on" id="loginform"
        data-sperre-rest="<?= (int)$sperreRest ?>">
    <?php /* Ein Token je Rundenzahl (M2-01). Das alte Feld 'token' entfaellt —
             der Server nimmt es weiterhin an, aber diese Seite fuellt es nicht
             mehr, weil sie nicht weiss, welche Rundenzahl fuer das Konto gilt. */ ?>
    <?= csrf_field() ?>
    <input type="hidden" name="tokens" id="toks">
    <label>E-Mail
      <input type="email" name="email" required autofocus autocomplete="username">
    </label>
    <label>Passwort
      <input type="password" name="password" required autocomplete="current-password">
    </label>
    <div class="listen-form-fuss">
      <?= ui_knopf(['text' => 'Anmelden', 'art' => 'primaer', 'breit' => true]) ?>
    </div>
  </form>
  <p class="anmeldung-neben"><a href="reset_request.php">Passwort vergessen?</a></p>
  <?php /* Zustandszeile der Anmeldung (Schluesselableitung laeuft …).
           `.zustandszeile` haelt ihre Hoehe frei, damit die Karte beim
           Erscheinen der Meldung nicht springt — `.muted` tat das nicht
           und ist mit der Uebergangsschicht in O11 gefallen. */ ?>
  <p class="zustandszeile" id="loginstate"></p>
 </div>
</main>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<script<?= kopf_nonce_attr() ?>>
// Der Browser leitet aus dem Passwort zwei Schluessel ab: das Auth-Token
// (geht zum Server) und den Daten-Schluessel (bleibt hier, entsperrt das
// PatientInnendaten-Modul). Das Passwort selbst verlaesst den Browser nie.
document.getElementById('loginform').addEventListener('submit', async ev => {
  const f = ev.target;
  if (f.dataset.ready === '1') return;               // zweiter Durchlauf: senden
  ev.preventDefault();
  const state = document.getElementById('loginstate');
  try {
    state.textContent = 'Schlüssel wird abgeleitet…';
    // Schluessel einer frueheren Sitzung verwerfen — sonst wuerde ein alter
    // Inhaltsschluessel weiterverwendet (etwa nach Kontowechsel im selben Tab).
    EdCrypto.clearSession();
    const email = f.elements['email'].value.trim().toLowerCase();
    const pw = f.elements['password'].value;
    const r = await fetch('auth_salt.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    // Der Salz-Endpunkt hat seit Web 4.4.0 einen Ratenschutz. Ohne diese
    // Abfrage lief eine Sperre in den catch-Zweig und meldete "Dieser Browser
    // unterstuetzt die noetige Verschluesselung nicht" — eine Auskunft, die
    // nicht nur unbrauchbar, sondern falsch ist.
    if (r.status === 429) {
      const d429 = await r.json().catch(() => ({}));
      state.textContent = d429.meldung || 'Zu viele Versuche. Bitte später erneut.';
      return;
    }
    if (!r.ok) {
      state.textContent = 'Anmeldung derzeit nicht möglich. Bitte später erneut.';
      return;
    }
    const d = await r.json();

    /* ---- Für jede genannte Rundenzahl ableiten (M2-01, Schritt 3) --------
     *
     * Der Salz-Endpunkt nennt jeder Adresse dieselbe Liste — er darf nicht
     * verraten, welche Zahl für dieses Konto gilt, sonst wäre die Antwort für
     * echte und erfundene Adressen wieder unterscheidbar. Der Browser rechnet
     * deshalb für alle und schickt alle Token; der Server nimmt das passende.
     *
     * WAS DAS KOSTET: Solange die Liste zwei Einträge hat, dauert die
     * Anmeldung doppelt so lange. Das ist der Übergangszustand während einer
     * Anhebung, nicht der Dauerzustand — steht nur ein Wert in der Liste, ist
     * alles wie vorher.
     *
     * Deshalb die Zwischenmeldung: Ohne sie wirkt die Seite in dieser Zeit
     * eingefroren, und zwar doppelt so lange wie gewohnt. */
    const runden = Array.isArray(d.iter) && d.iter.length ? d.iter : [310000];
    state.textContent = 'Schlüssel werden abgeleitet …';
    const tokens = {}, haelften = {};
    for (const it of runden) {
      const k = await EdCrypto.deriveKeys(pw, d.salt, it);
      tokens[it] = k.authToken;
      haelften[it] = k.haelfteHex;
    }
    document.getElementById('toks').value = JSON.stringify(tokens);

    /* ---- IMMER ins Vormerkfach, seit S10 (E-S10-07) ---------------------
     *
     * Diese Seite setzt den Datenschlüssel NICHT MEHR SELBST, und zwar auch
     * dann nicht, wenn nur eine Rundenzahl in Frage kommt.
     *
     * WARUM SIE ES NICHT MEHR KANN. Bis Web 19.7.0 fehlte ihr nur EINE
     * Angabe: welche Rundenzahl für dieses Konto gilt. Bei einer einzigen Zahl
     * gab es nichts zu entscheiden, und sie setzte den Schlüssel sofort. Seit
     * S10 fehlt ihr eine zweite: Ob die Hülle dieses Kontos den Server-Anteil
     * braucht, steht im PRÄFIX der Hülle — und die kennt erst die angemeldete
     * Seite (`PAT_WRAP` aus auth_guard.php). Eine Anmeldeseite, die den
     * Datenschlüssel setzt, ohne die Hülle gesehen zu haben, rät.
     *
     * WAS DAS KOSTET: Das Fach liegt jetzt nach jeder Anmeldung einen
     * Seitenwechsel lang im sessionStorage statt gar nicht. Geräumt wird es
     * unverändert von der ersten Seite, die den Inhaltsschlüssel braucht
     * (unlock.js), beim Abmelden und bei jedem Anmeldeversuch.
     *
     * Die Ablage im Vormerkfach ist dasselbe Verfahren, das der
     * Passwortwechsel seit Web 4.5.0 benutzt (M2-07): Der neue Stand liegt
     * bereit, wird aber erst übernommen, wenn der Server ihn bestätigt hat. */
    EdCrypto.merkeAbleitungen(haelften, tokens);
    f.elements['password'].value = '';               // verlaesst den Browser nie
    f.dataset.ready = '1';
    state.textContent = '';
    f.submit();
  } catch (e2) {
    // Ohne Web-Krypto ist keine Anmeldung moeglich: Das Passwort duerfte den
    // Browser nicht verlassen, und ohne abgeleitetes Token gibt es keinen Weg.
    state.textContent = 'Dieser Browser unterstützt die nötige Verschlüsselung nicht.';
  }
});
</script>
<?php /* DER COUNTDOWN DER SPERRE (E-P5a-06, P5a/AP6).
 *
 * EIGENER BLOCK UND NICHT `forms.js` — das ist eine benannte Abweichung vom
 * Konzept (E-P5a-45). Das Konzept nennt „login.php, forms.js"; login.php
 * LAEDT forms.js aber gar nicht, und forms.js ist Aenderungsverfolgung,
 * Strg-Enter und Abbrechen-Rueckfrage. Es hier nachzutragen schaltete
 * nebenbei eine beforeunload-Warnung auf einer Seite frei, auf der jemand
 * ein Passwort tippt — und ein Zeitgeber steht darin ohnehin nicht.
 *
 * KEINE EIGENE DATEI: Die Seite hat schon einen genoncten Block, und zehn
 * Zeilen rechtfertigen keine Auslieferung mehr.
 *
 * DIE SEKUNDEN KOMMEN ALS GANZE ZAHL aus `rate_sperre()`, nicht als
 * Zeitstempel. Ein roher DATETIME steht in UTC (db.php setzt die Verbindung
 * auf +00:00); der Browser laese ihn als Ortszeit, und der Countdown stuende
 * je nach Zone ein bis zwei Stunden daneben.
 *
 * ER STEHT NICHT IN DER MELDUNG. Jene traegt `role="alert"`, und ein Text,
 * der sich jede Sekunde aendert, wird von einem Screenreader jede Sekunde neu
 * vorgelesen. Er steht in der Zustandszeile darunter — und auch dort erst,
 * nachdem der Krypto-Block sie geleert hat. */ ?>
<script<?= kopf_nonce_attr() ?>>
(function () {
  var f = document.getElementById('loginform');
  if (!f) { return; }
  var rest = parseInt(f.dataset.sperreRest || '0', 10);
  if (!(rest > 0)) { return; }

  var knopf = f.querySelector('button[type=submit], input[type=submit]');
  var zeile = document.getElementById('loginstate');
  var text  = knopf ? (knopf.textContent || 'Anmelden') : '';

  // Das Formular bleibt gesperrt, solange die Sperre laeuft — ein Klick
  // waere ein weiterer Fehlversuch und verlaengerte im ungluecklichen Fall
  // die Stufe.
  Array.prototype.forEach.call(f.elements, function (el) { el.disabled = true; });

  function zeig() {
    var m = Math.floor(rest / 60), sek = rest % 60;
    var wie = m > 0 ? (m + ' Minute' + (m === 1 ? '' : 'n')) : (sek + ' Sekunden');
    if (zeile) { zeile.textContent = 'Noch ' + wie + '.'; }
    if (knopf) { knopf.textContent = text + ' (' + wie + ')'; }
  }
  zeig();

  var uhr = setInterval(function () {
    rest -= 1;
    if (rest <= 0) {
      clearInterval(uhr);
      Array.prototype.forEach.call(f.elements, function (el) { el.disabled = false; });
      if (knopf) { knopf.textContent = text; }
      if (zeile) { zeile.textContent = 'Du kannst es wieder versuchen.'; }
      return;
    }
    zeig();
  }, 1000);
})();
</script>
<?php /* Fusszeile auf JEDER Seite, auch vor der Anmeldung (R32, E-P3-14) —
         dunkel, weil sie hier auf der dunkelblauen Flaeche liegt. */ ?>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php ui_seite_ende(); ?>
