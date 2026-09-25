<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/speicher_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/geocoder_lib.php';
require_once __DIR__ . '/serverkrypto_lib.php';   // Karte „Schlüssel des Servers" (S10)
require_once __DIR__ . '/format_lib.php';         // groesse_text(), datum_zeit_text()

/**
 * BETRIEB → SERVEREINSTELLUNGEN (S8/AP2, E-S8-05, E-S8-18; Mockup 07 Fassung 2).
 *
 * WAS HIER STEHT UND WARUM. Die Speichergrenze und die Warnschwellen lagen
 * unter „Backups", zwischen der Erinnerung und der Aufbewahrung. Sie gehoeren
 * nicht dorthin: Sie wirken auf die Konto-Backups UND auf die Komplett-Staende
 * (B-S8-06), und die Komplett-Seite verwies bisher mit einem Satz auf sie. Das
 * war einer der Gruende fuer Nr. 79.
 *
 * Jetzt liegen Grenze, Schwellen und Belegung an EINER Stelle — und zwar im
 * Betrieb, wo die uebrigen Einstellungen der Installation stehen. Was je Konto
 * gilt (Erinnerung, Aufbewahrung, Admin-Mail), bleibt bei den Konto-Backups.
 *
 * DIE SEITE IST EINSPALTIG AUF LESEBREITE, auch am Schreibtisch.
 *
 * DIESER ABSATZ HAT SEINE EIGENE BEGRUENDUNG UEBERLEBT, und das wird hier
 * vermerkt statt stillschweigend berichtigt. Er lautete: „Sie traegt zwei
 * Karten, und ein zweispaltiges Raster fuer zwei Karten waere Raster um des
 * Rasters willen (E-S8-18 — Zweispaltigkeit gilt ab mehr als vier Karten)."
 * Seit S8 sind fuenf Karten dazugekommen (Kopfzeilen, Ratenschutz,
 * CSP-Berichte, Schluessel des Servers, Konten), dazu Ankuendigung und
 * Protokoll; es sind ACHT (Stand Web 21.1.0), und die Zahl, die die
 * Einspaltigkeit begruendete, ist ueberschritten.
 *
 * EINSPALTIG BLEIBT SIE TROTZDEM, und zwar bis jemand das Gegenteil mit
 * einem Mockup freigibt (`CLAUDE.md` 5): Die Karten hier sind keine Kacheln,
 * sondern Formulare mit langen Erklaerungstexten — sie leben von der
 * Lesebreite. Zwei Spalten haetten zur Folge, dass ein Hinweissatz, der
 * erklaert, warum eine Zahl nicht unter 15 gesetzt werden sollte, in einer
 * 40-Zeichen-Spalte steht. Der Befund gehoert ins Backlog, nicht in eine
 * stille Aenderung.
 */

$pdo = db();
$notice = null; $error = null;
/* Die Rueckfrage zur Demo-Anmeldung (P5b/AP7) — sie entsteht im
 * Konten-Zweig und wird unten in der Karte gezeigt. */
$demoFrage = false;

/* ---- Ankuendigung und Rundmail (P5c/AP1, E-P5c-13, -55) ------------------
 *
 * EIN FORMULAR, DREI KNOEPFE: Speichern, Rundmail, Entfernen. Die Rundmail
 * SPEICHERT ZUERST, und das ist der Punkt: Was im Feld steht, ist das, was
 * hinausgeht. Wer den Text aendert und gleich „Als Rundmail senden" drueckt,
 * soll nicht die alte Fassung verschickt haben.
 *
 * `$ankForm` haelt bei einem Fehler, was eingegeben war — ein abgewiesenes
 * Formular, das die Eingabe verwirft, laesst sie ein zweites Mal tippen. */
require_once __DIR__ . '/ankuendigung_lib.php';
$ankForm = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['action'] ?? '', ['ankuendigung', 'rundmail', 'ankuendigung_weg'], true)) {
    csrf_check();
    if ($_POST['action'] === 'ankuendigung_weg') {
        ankuendigung_entfernen();
        $notice = 'Ankündigung entfernt — der Streifen erscheint auf keiner Seite mehr.';
    } else {
        $ankForm = ['text' => (string)($_POST['ank_text'] ?? ''),
                    'ton'  => (string)($_POST['ank_ton'] ?? 'info'),
                    'tag'  => trim((string)($_POST['ank_bis'] ?? '')),
                    'zeit' => trim((string)($_POST['ank_bis_zeit'] ?? ''))];
        require_once __DIR__ . '/validate_lib.php';
        $bisUtc = pruef_ortszeit_zu_utc($ankForm['tag'], $ankForm['zeit'], 0, 'Sichtbar bis');
        if ($bisUtc === null) {
            $error = 'Bitte bei „Sichtbar bis" ein Datum und eine Uhrzeit (HH:MM) angeben.';
        } else {
            $error = ankuendigung_setzen($ankForm['text'], $ankForm['ton'],
                                         (int)iso_utc_lesen($bisUtc));
        }
        if ($error === null) {
            $ankForm = null;
            if ($_POST['action'] === 'rundmail') {
                $r = rundmail_senden();
                if ($r['ok']) { $notice = $r['meldung']; } else { $error = $r['meldung']; }
            } else {
                $a = ankuendigung();
                $notice = 'Ankündigung gespeichert — sie steht bis '
                        . datum_zeit_text(iso_utc($a['bis'] ?? time()))
                        . ' über jeder Seite.';
            }
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'speicher') {
    csrf_check();
    $teile = [];

    /* ---- Speichergrenze (E-S2-15, zieht mit S8 hierher) ---------------- */
    $gb = str_replace(',', '.', trim((string)($_POST['grenze'] ?? '')));
    if (!is_numeric($gb) || (float)$gb <= 0 || (float)$gb > 10000) {
        $error = 'Bitte eine Speichergrenze zwischen 0,1 und 10000 GB angeben.';
    } elseif (abs((float)$gb * 1024 * 1024 * 1024 - edbak_grenze_bytes()) > 1) {
        edbak_marke_setzen('adminbackup_grenze_gb', (string)(float)$gb);
        /* DIE GEMELDETEN SCHWELLEN VERGESSEN. Eine neue Grenze macht aus
         * denselben Bytes einen anderen Prozentsatz — was bei der alten Grenze
         * gemeldet war, ist bei der neuen eine andere Aussage. Ohne dieses
         * Zuruecksetzen bliebe eine Warnung aus, die nach der Änderung fällig
         * wäre. */
        edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
        edbak_marke_setzen('adminbackup_schwellen_offen', '');
        $teile[] = 'Speichergrenze ' . $gb . ' GB';
    }

    /* ---- Warnschwellen ------------------------------------------------- */
    if ($error === null) {
        $roh = trim((string)($_POST['schwellen'] ?? ''));
        $neu = [];
        foreach (explode(',', $roh) as $t) {
            $t = trim($t);
            if ($t === '') { continue; }
            if (!ctype_digit($t) || (int)$t < 1 || (int)$t > 100) {
                $error = 'Warnschwellen sind ganze Zahlen zwischen 1 und 100, '
                       . 'durch Komma getrennt (z. B. „70, 90").';
                break;
            }
            $neu[(int)$t] = true;
        }
        if ($error === null) {
            $neu = array_keys($neu); sort($neu);
            if ($neu !== edbak_schwellen()) {
                edbak_marke_setzen('adminbackup_schwellen', implode(',', $neu));
                edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
                edbak_marke_setzen('adminbackup_schwellen_offen', '');
                $teile[] = $neu ? 'Warnschwellen ' . implode(' / ', $neu) . ' %'
                                : 'Warnschwellen aus';
            }
        }
    }

    /* ---- Webspace laut Hosting (neu, E-S8-18) --------------------------- */
    if ($error === null) {
        $roh = str_replace(',', '.', trim((string)($_POST['webspace'] ?? '')));
        if ($roh === '') {
            if (speicher_webspace_bytes() > 0) {
                speicher_webspace_setzen(0);
                $teile[] = 'Webspace-Angabe entfernt';
            }
        } elseif (!is_numeric($roh) || (float)$roh <= 0 || (float)$roh > 100000) {
            $error = 'Der Webspace ist eine Zahl in GB (oder leer, wenn die Angabe fehlt).';
        } elseif (abs((float)$roh * 1024 * 1024 * 1024 - speicher_webspace_bytes()) > 1) {
            speicher_webspace_setzen((float)$roh);
            $teile[] = 'Webspace ' . $roh . ' GB';
        }
    }

    /* ---- Kontingent der Datenbank (P5a/AP2, E-P5a-11) -------------------
     *
     * ANDERS ALS DER WEBSPACE HAT ES EINE VORGABE (10 GB): Das ist die
     * Untergrenze Z2, die diese Anwendung tragen muss — eine Zusage des
     * Projekts, keine Vermutung ueber den Hoster. Leer setzt sie zurueck.
     *
     * Es gehoert in DIESES Formular und nicht in ein eigenes: Es steht neben
     * dem Webspace, wird von denselben Schwellen gewarnt und hat dieselbe
     * Frage im Ruecken. */
    if ($error === null) {
        $roh = str_replace(',', '.', trim((string)($_POST['db_gb'] ?? '')));
        /* BLEIBT BYTE-DIVISION (Z22, AP7): keine Anzeige, sondern ein
         * Vergleichswert — er geht unten in `abs((float)$roh - $istGb)`
         * und wird nie ausgegeben. */
        $istGb = speicher_db_kontingent_bytes() / (1024 * 1024 * 1024);
        if ($roh === '') {
            if (edbak_marke_lesen(SPEICHER_K_DB_GB) !== null
                && (string)edbak_marke_lesen(SPEICHER_K_DB_GB) !== '') {
                speicher_db_kontingent_setzen(0);
                $teile[] = 'DB-Kontingent auf die Vorgabe zurückgesetzt';
            }
        } elseif (!is_numeric($roh) || (float)$roh < 0.01 || (float)$roh > 100000) {
            $error = 'Das DB-Kontingent ist eine Zahl in GB, mindestens 0,01 (oder '
                   . 'leer für die Vorgabe ' . SPEICHER_DB_GB_VORGABE . ' GB).';
        } elseif (abs((float)$roh - $istGb) > 0.001) {
            speicher_db_kontingent_setzen((float)$roh);
            $teile[] = 'DB-Kontingent ' . $roh . ' GB';
        }
    }

    if ($error === null) {
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

/* ---- Sicherheitskopfzeilen (P5a/AP4, E-P5a-15) ---------------------------
 *
 * EIGENES FORMULAR, wie die Adresssuche darunter und aus demselben Grund: Ein
 * Tippfehler in der Speichergrenze soll die CSP nicht mit abweisen.
 *
 * ZWEI EINSTELLUNGEN, ZWEI GESCHICHTEN. `csp_scharf` ist der Schritt von
 * „melden" auf „blockieren" — er gehoert ans Ende einer Report-Only-Phase und
 * nicht an ihren Anfang. `hsts_tage` ist die Dauer, fuer die ein Browser sich
 * merkt, dass diese Domain nur ueber HTTPS zu haben ist; sie stand bis Web
 * 20.6.0 fest in der `.htaccess` auf einem Jahr.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'kopfzeilen') {
    csrf_check();
    $teile = [];
    $scharf = !empty($_POST['csp_scharf']);
    if ($scharf !== kopf_csp_scharf()) {
        kopf_csp_scharf_setzen($scharf);
        $teile[] = $scharf ? 'CSP scharf' : 'CSP auf „nur melden"';
    }
    $tage = (int)($_POST['hsts_tage'] ?? -1);
    if ($tage !== kopf_hsts_tage()) {
        if (!kopf_hsts_tage_setzen($tage)) {
            $error = 'Für HSTS sind nur „aus", 1, 7 oder 365 Tage vorgesehen.';
        } else {
            $teile[] = 'HSTS ' . ($tage === 0 ? 'aus' : $tage . ' Tage');
        }
    }
    if ($error === null) {
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

/* ---- Adresssuche (S9/AP2, E-S9-05, R79; Backlog Nr. 137) ------------------
 *
 * EIGENE HANDLUNG, EIGENES FORMULAR. Sie hat mit dem Speicher nichts zu tun,
 * und ein gemeinsames „Speichern" ueber zwei Karten hinweg hiesse, dass ein
 * Tippfehler in der Speichergrenze die Dienstadresse mit abweist.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'ratenschutz') {
    csrf_check();
    require_once __DIR__ . '/ratelimit_lib.php';

    /* ERST ALLES PRUEFEN, DANN ALLES SPEICHERN (P5a/AP6).
     *
     * Die Nachbarkarten auf dieser Seite schreiben Wert fuer Wert und brechen
     * beim ersten Mangel ab — dann steht die Haelfte in der Datenbank und die
     * Meldung „nicht gespeichert" ist zur Haelfte falsch. `admin_installation.php`
     * macht es seit S8 richtig und schreibt den Grund dazu; hier steht er
     * wieder, weil es die naechste Karte sonst wieder falsch macht. */
    $neuLeiter = null; $neuLogin = null; $neuLoginIp = null; $neuBremse = null;

    $pruefListe = static function (string $roh, int $anzahl, int $minWert,
                                   int $maxWert, string $name) use (&$error): ?array {
        $teile = array_map('trim', explode(',', $roh));
        if (count($teile) !== $anzahl) {
            $error = $name . ': genau ' . $anzahl . ' Werte, durch Komma getrennt.';
            return null;
        }
        $zahlen = []; $vorher = 0;
        foreach ($teile as $t) {
            if (!ctype_digit($t) || (int)$t < $minWert || (int)$t > $maxWert) {
                $error = $name . ': ganze Zahlen zwischen ' . $minWert . ' und '
                       . $maxWert . '.';
                return null;
            }
            if ((int)$t <= $vorher) {
                $error = $name . ': die Werte müssen aufsteigen — eine Leiter, die '
                       . 'rückwärts läuft, sperrt beim zweiten Mal kürzer.';
                return null;
            }
            $zahlen[] = (int)$t; $vorher = (int)$t;
        }
        return $zahlen;
    };

    $roh = trim((string)($_POST['leiter'] ?? ''));
    $neuLeiter = $pruefListe($roh, 4, 1, 1440, 'Sperrleiter');

    if ($error === null) {
        $roh = trim((string)($_POST['bremse'] ?? ''));
        $neuBremse = $pruefListe($roh, 4, 10, 1000000, 'Schwellen der Verlangsamung');
    }
    foreach ([['login', 'Fehlversuche je Konto', &$neuLogin],
              ['login_ip', 'Fehlversuche je Anschluss', &$neuLoginIp]] as [$feld, $name, &$ziel]) {
        if ($error !== null) { break; }
        $roh = trim((string)($_POST[$feld] ?? ''));
        if (!ctype_digit($roh) || (int)$roh < 3 || (int)$roh > 10000) {
            $error = $name . ': eine ganze Zahl zwischen 3 und 10000.';
        } else {
            $ziel = (int)$roh;
        }
    }
    unset($ziel);

    if ($error === null) {
        /* Die Leiter steht in SEKUNDEN in `app_state`, eingegeben wird sie in
         * MINUTEN — Sekunden in ein Formular zu schreiben, das von Menschen
         * ausgefuellt wird, waere eine Einladung zum Vertippen um den Faktor
         * sechzig. */
        app_state_setzen(RATE_K_LEITER,
                         implode(',', array_map(static fn(int $m): int => $m * 60, $neuLeiter)));
        app_state_setzen(RATE_K_BREMSE, implode(',', $neuBremse));
        app_state_setzen(RATE_K_LOGIN, (string)$neuLogin);
        app_state_setzen(RATE_K_LOGIN_IP, (string)$neuLoginIp);
        app_state_setzen(RATE_K_MAIL, empty($_POST['mail']) ? '0' : '');
        $notice = 'Ratenschutz gespeichert: Sperrleiter '
                . implode(' / ', $neuLeiter) . ' min · ' . $neuLogin
                . ' Fehlversuche je Konto, ' . $neuLoginIp . ' je Anschluss · '
                . 'Verlangsamung ab ' . $neuBremse[0] . ' Fehlversuchen je 15 Minuten.';
    }
}

/* ---- Protokoll: Frist und Archiv (P5c/AP2, E-P5c-02, -03, -24) ----------
 *
 * ALLES ODER NICHTS, wie bei den Konten darunter: vier Werte, geprüft, dann
 * geschrieben. Was sich geändert hat, steht als EIN Eintrag im Protokoll —
 * Fristen ändern heißt, das Audit kürzer oder länger zu machen, und das
 * gehört selbst ins Audit (E-P5c-02: „Fristen ändern: BetreiberIn,
 * protokolliert"). */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'protokoll') {
    csrf_check();
    require_once __DIR__ . '/protokoll_archiv_lib.php';
    $felder = [
        'protokoll_frist'    => ['Verwaltungseinträge aufbewahren', PROTOKOLL_K_FRIST_VERWALTUNG,
                                 PROTOKOLL_FRIST_MIN, PROTOKOLL_FRIST_MAX, protokoll_frist_verwaltung()],
        'archiv_tage'        => ['Archiv alle', PROTOKOLL_K_ARCHIV_TAGE,
                                 PROTOKOLL_ARCHIV_TAGE_MIN, PROTOKOLL_ARCHIV_TAGE_MAX, protokoll_archiv_tage()],
        'archiv_behalten'    => ['Archive aufbewahren', PROTOKOLL_K_ARCHIV_BEHALTEN,
                                 PROTOKOLL_ARCHIV_BEHALTEN_MIN, PROTOKOLL_ARCHIV_BEHALTEN_MAX,
                                 protokoll_archiv_behalten()],
    ];
    $neu = []; $geaendert = [];
    foreach ($felder as $feld => [$name, $schluessel, $min, $max, $alt]) {
        $roh = trim((string)($_POST[$feld] ?? ''));
        if (!ctype_digit($roh) || (int)$roh < $min || (int)$roh > $max) {
            $error = $name . ': eine ganze Zahl zwischen ' . $min . ' und ' . $max . ' Tagen.';
            break;
        }
        $neu[$schluessel] = $roh;
        if ((int)$roh !== $alt) { $geaendert[] = $name . ' ' . $alt . ' → ' . $roh . ' Tage'; }
    }
    $versand = !empty($_POST['archiv_versand']);
    if ($error === null) {
        foreach ($neu as $k => $v) { app_state_setzen($k, $v); }
        if ($versand !== protokoll_archiv_versand()) {
            $geaendert[] = 'Archive auf das Backup-Ziel ' . ($versand ? 'an' : 'aus');
        }
        app_state_setzen(PROTOKOLL_K_ARCHIV_VERSAND, $versand ? '1' : '0');
        if ($geaendert) {
            protokoll('verwaltung', 'frist_geaendert',
                      'Fristen des Protokolls geändert: ' . implode(' · ', $geaendert),
                      ['geaendert' => $geaendert]);
        }
        $notice = $geaendert ? 'Protokoll: ' . implode(' · ', $geaendert) . '.'
                             : 'Es gab nichts zu ändern.';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'geocoder') {
    csrf_check();
    $adresse = geocoder_adresse_pruefen((string)($_POST['dienst'] ?? ''));
    if ($adresse === null) {
        $error = 'Die Dienstadresse muss mit „https://" beginnen und einen '
               . 'Rechnernamen nennen — ohne Abfrageteil, höchstens 180 Zeichen '
               . '(z. B. „https://photon.komoot.io").';
    } else {
        $teile = geocoder_installation_setzen(!empty($_POST['adresssuche']), $adresse);
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

/* Der eine Handgriff der Rueckfrage (P5b/AP7): Demo-Anmeldung abschalten,
 * ohne das ganze Formular noch einmal zu schicken. Eigener `action`, weil er
 * eine eigene Handlung ist — dieselbe Ordnung wie bei den Schluesseln. */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'demo_aus') {
    csrf_check();
    require_once __DIR__ . '/konten_einstellungen_lib.php';
    require_once __DIR__ . '/protokoll_lib.php';
    app_state_setzen(KONTEN_K_DEMO_ANMELDUNG, '0');
    konten_einstellungen_neu_lesen();
    protokoll('verwaltung', 'einstellungen_konten',
              'Demo-Anmeldung abgeschaltet (mit dem Wechsel auf „nur auf Einladung")',
              ['geaendert' => [KONTEN_K_DEMO_ANMELDUNG]]);
    $notice = 'Die Demo-Anmeldung ist abgeschaltet. Der Bestand des Demo-Kontos '
            . 'bleibt; ein Umlegen des Schalters macht es sofort wieder zugänglich.';
}

/* ---- Konten: Registrierung, Fristen, Grenzen (P5b/AP1, E-P5b-14) --------
 *
 * EINE KARTE, EIN FORMULAR, ALLES ODER NICHTS. Der Ratenschutz darueber hat
 * es vorgemacht und den Grund mitgeschrieben: Wer Wert fuer Wert schreibt und
 * beim ersten Mangel abbricht, hinterlaesst die Haelfte in der Datenbank und
 * eine Meldung „nicht gespeichert", die zur Haelfte falsch ist. Hier steht es
 * wieder so — erst alles pruefen, dann alles schreiben.
 *
 * WAS IN DIESEM PAKET NOCH KEINEN VERBRAUCHER HAT, steht trotzdem schon hier:
 * Betriebsart, Freischaltfrist, Wegwerfadressen, Mengengrenzen und die
 * Aufbewahrung je Konto wirken erst mit AP3 bzw. AP6. Das Konzept sagt es so
 * (AP1: „alle Felder, noch ohne Verbraucher ausser Demo-Anmeldung"), und der
 * Grund ist die Reihenfolge: Die Registrierung liest ihre Betriebsart aus
 * `app_state`, und eine Einstellung, die es beim Bauen der Registrierung noch
 * nicht gibt, wird beim Bauen erfunden — an einer zweiten Stelle, mit einer
 * zweiten Vorgabe.
 *
 * DIE VORGABE DER BETRIEBSART IST „nur auf Einladung" (E-P5b-01), und das ist
 * nicht die Vorgabe, die nadoku selbst fahren wird. Sie ist die sicherste
 * Grundstellung fuer eine Selbsthosterin, die die Seite nie aufschlaegt —
 * heutiges Verhalten, keine offene Tuer durch Untaetigkeit.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'konten') {
    csrf_check();
    require_once __DIR__ . '/konten_einstellungen_lib.php';

    $neu = [];   // Schluessel => Wert, erst am Ende geschrieben

    /* ---- Betriebsart ---------------------------------------------------- */
    $art = (string)($_POST['reg_art'] ?? '');
    if (!isset(KONTEN_REG_ARTEN[$art])) {
        $error = 'Betriebsart: bitte eine der drei Möglichkeiten wählen.';
    } else {
        $neu[KONTEN_K_REG_ART] = $art;
    }

    /* ---- Freischaltfrist in Tagen --------------------------------------- */
    if ($error === null) {
        $roh = trim((string)($_POST['reg_frist'] ?? ''));
        if (!ctype_digit($roh) || (int)$roh < KONTEN_REG_FRIST_MIN
                               || (int)$roh > KONTEN_REG_FRIST_MAX) {
            $error = 'Verfall wartender Registrierungen: eine ganze Zahl zwischen '
                   . KONTEN_REG_FRIST_MIN . ' und ' . KONTEN_REG_FRIST_MAX . ' Tagen.';
        } else {
            $neu[KONTEN_K_REG_FRIST] = $roh;
        }
    }

    /* ---- Wegwerfadressen ------------------------------------------------ */
    if ($error === null) {
        $neu[KONTEN_K_WEGWERF] = empty($_POST['wegwerf']) ? '0' : '1';

        /* EIGENE DOMAINS: eine je Zeile im Formular, mit Komma getrennt in
         * `app_state` — dort passen 190 Zeichen, und mehr als eine Handvoll
         * eigener Domains ist kein Anwendungsfall (die mitgelieferte Liste
         * hat 8870). Wer mehr braucht, ergaenzt die Datei. */
        $roh = trim((string)($_POST['wegwerf_eigene'] ?? ''));
        $domains = [];
        foreach (preg_split('/[\s,;]+/', $roh) ?: [] as $d) {
            $d = mb_strtolower(trim($d, " \t\n\r.@"));
            if ($d === '') { continue; }
            if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $d)) {
                $error = 'Eigene Wegwerfdomains: „' . $d . '" ist kein Domainname. '
                       . 'Erwartet wird die reine Domain, ohne „@" und ohne „https://".';
                break;
            }
            $domains[] = $d;
        }
        if ($error === null) {
            $domains = array_values(array_unique($domains));
            $wert = implode(',', $domains);
            if (strlen($wert) > APP_STATE_MAX) {
                $error = 'Eigene Wegwerfdomains: die Liste ist zu lang (höchstens '
                       . APP_STATE_MAX . ' Zeichen). Für längere Listen ist '
                       . '`server/wegwerfdomains.txt` der Ort.';
            } else {
                $neu[KONTEN_K_WEGWERF_EIGENE] = $wert;
            }
        }
    }

    /* ---- Mengengrenzen je Konto ----------------------------------------- */
    foreach ([['grenze_einsaetze', 'Mengengrenze Einsätze', KONTEN_K_GRENZE_EINSAETZE,
               1, 1000000],
              ['grenze_mb', 'Mengengrenze Speicher (MB)', KONTEN_K_GRENZE_MB,
               1, 1000000]] as [$feld, $name, $schluessel, $min, $max]) {
        if ($error !== null) { break; }
        $roh = trim((string)($_POST[$feld] ?? ''));
        if (!ctype_digit($roh) || (int)$roh < $min || (int)$roh > $max) {
            $error = $name . ': eine ganze Zahl zwischen ' . $min . ' und ' . $max . '.';
        } else {
            $neu[$schluessel] = $roh;
        }
    }

    /* ---- Demo-Anmeldung (der einzige Wert mit Verbraucher in AP1) ------- */
    if ($error === null) {
        $neu[KONTEN_K_DEMO_ANMELDUNG] = empty($_POST['demo_anmeldung']) ? '0' : '1';
    }

    /* Die Protokollfrist stand bis Web 20.38.0 hier. Seit P5c/AP2 hat sie
     * eine eigene Karte, zusammen mit dem Archiv — und wird protokolliert,
     * was sie hier nie wurde (`konten_einstellungen()` kannte sie nicht). */

    if ($error === null) {
        $vorher = konten_einstellungen();
        foreach ($neu as $k => $v) { app_state_setzen($k, $v); }

        /* WAS SICH GEAENDERT HAT, STEHT IM PROTOKOLL — nicht der ganze
         * Formularinhalt. Ein Audit, das bei jedem „Speichern" zwoelf
         * unveraenderte Werte auffuehrt, macht die eine Aenderung
         * unauffindbar. */
        $nachher  = konten_einstellungen_neu_lesen();
        $geaendert = [];
        foreach ($nachher as $k => $v) {
            if ((string)($vorher[$k] ?? '') !== (string)$v) { $geaendert[] = $k; }
        }
        if ($geaendert) {
            protokoll('verwaltung', 'einstellungen_konten',
                      'Konten-Einstellungen geändert: ' . implode(', ', $geaendert),
                      ['geaendert' => $geaendert]);
        }

        $notice = 'Konten-Einstellungen gespeichert: Registrierung '
                . KONTEN_REG_ARTEN[$art] . ' · Wartende verfallen nach '
                . $neu[KONTEN_K_REG_FRIST] . ' Tagen · Grenzen '
                . $neu[KONTEN_K_GRENZE_EINSAETZE] . ' Einsätze / '
                . $neu[KONTEN_K_GRENZE_MB] . ' MB je Konto.';

        /* DIE RUECKFRAGE ZUR DEMO-ANMELDUNG (P5b/AP7, E-P5b-07).
         *
         * Nur beim WECHSEL auf „nur auf Einladung" und nur, solange die
         * Demo-Anmeldung noch an ist. Wer die Tuer schliesst, hat meist auch
         * das Demo-Konto im Sinn — aber nicht immer: Eine Installation kann
         * geschlossen sein und trotzdem ein Demo-Konto zum Vorzeigen haben.
         * Deshalb gefragt und nicht getan.
         *
         * ALS ANGEBOT UND NICHT ALS BESTAETIGUNGSDIALOG. Das Konzept sagt
         * „fragt die Seite einmal"; `data-confirm` (der vorhandene
         * Rueckfrage-Baustein) kann aber nur ja/nein ZUM ABSENDEN, nicht
         * „und schalte dabei noch etwas anderes ab". Ein Dialog, der das
         * koennte, waere ein NEUER Baustein und braeuchte eine Freigabe mit
         * Mockup (Design.md 9). Die Meldung mit Knopf ist aus vorhandenen
         * Teilen gebaut und hat denselben Zweck — sie fragt einmal, sie
         * blockiert nichts, und sie verschwindet beim naechsten Speichern. */
        if ($art === 'einladung'
            && ($vorher[KONTEN_K_REG_ART] ?? '') !== 'einladung'
            && $neu[KONTEN_K_DEMO_ANMELDUNG] === '1') {
            $demoFrage = true;
        }
    }
}

/* ---- Die Schlüssel des Servers (S10, E-S10-10 bis E-S10-12) -------------
 *
 * SECHS HANDGRIFFE, EIN FORMULARZWEIG. Sie stehen hier und nicht auf
 * „Backup-Ziele", weil sie die INSTALLATION betreffen und nicht die
 * Sicherung: Der Server-Anteil geht in den Datenschlüssel jedes Kontos ein,
 * mit Backups hat er nichts zu tun. Das Ordnungsprinzip ist R74/E-S8-12 —
 * was alle trifft, steht im Betrieb.
 *
 * JEDER GRIFF IST EINE EIGENE HANDLUNG mit eigenem `action`, so wie Speicher
 * und Adresssuche getrennte Formulare haben. Ein gemeinsames „Speichern" über
 * sechs Handlungen hinweg wäre die Stelle, an der ein Klick auf „Wechseln"
 * nebenbei etwas anderes tut.
 *
 * DIE ARBEIT STECKT IN `serverkrypto_lib.php`, nicht hier. Jeder Griff
 * besteht aus zwei Schritten, die zusammengehören — `config.php` schreiben
 * und die Marke nachziehen —, und diese Seite soll keine Krypto-Logik tragen.
 */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && str_starts_with((string)($_POST['action'] ?? ''), 'schluessel_')) {
    csrf_check();
    $aktion   = (string)$_POST['action'];
    $ersetzen = !empty($_POST['ersetzen']);

    if ($aktion === 'schluessel_sk_anlegen') {
        [$ok, $was] = serverschluessel_eintragen();
        $notice = $ok ? 'Der Serverschlüssel steht jetzt in config.php (Kennung '
                      . schluessel_kennung($was) . '). Bitte das Schlüsselblatt '
                      . 'drucken — zwei Ausdrucke, zwei Orte.' : null;
        $error  = $ok ? null : $was;
        serverschluessel_zustand(true);

    } elseif ($aktion === 'schluessel_anteil_anlegen') {
        [$ok, $was] = anteil_anlegen();
        $notice = $ok ? 'Der Server-Anteil steht jetzt in config.php (Kennung '
                      . $was . '). Die Konten stellen beim nächsten Anmelden '
                      . 'still um. Bitte das Schlüsselblatt drucken — zwei '
                      . 'Ausdrucke, zwei Orte.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_sk_nachtragen') {
        [$ok, $was] = serverschluessel_nachtragen(
            (string)($_POST['wert'] ?? ''), $ersetzen);
        $notice = $ok ? 'Der Serverschlüssel ist nachgetragen (Kennung ' . $was
                      . ') — er stimmt mit dem überein, mit dem versiegelt '
                      . 'wurde.' : null;
        $error  = $ok ? null : $was;
        serverschluessel_zustand(true);

    } elseif ($aktion === 'schluessel_anteil_nachtragen') {
        [$ok, $was] = anteil_nachtragen((string)($_POST['wert'] ?? ''), $ersetzen);
        $notice = $ok ? 'Der Server-Anteil ist nachgetragen (Kennung ' . $was
                      . ') — er stimmt mit dem überein, mit dem die Hüllen '
                      . 'gebaut wurden. Die Anmeldung geht wieder.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_wechseln') {
        [$ok, $was] = anteil_wechseln();
        $notice = $ok ? 'Der Server-Anteil ist gewechselt (neue Kennung ' . $was
                      . '). Der bisherige bleibt als kdf_anteil_alt stehen, bis '
                      . 'kein Konto mehr auf ihm steht. Jetzt ein neues '
                      . 'Schlüsselblatt drucken.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_alt_entfernen') {
        [$ok, $was] = anteil_alt_entfernen();
        $notice = $ok ? 'Der alte Server-Anteil ist aus config.php entfernt. '
                      . 'Die Rotation ist abgeschlossen.' : null;
        $error  = $ok ? null : $was;

    } elseif ($aktion === 'schluessel_anteil_neuanfang') {
        [$ok, $was] = anteil_neuanfang();
        $notice = $ok ? 'Ein neuer Server-Anteil ist eingetragen (Kennung ' . $was
                      . '). Alle Konten mit umgestellter Hülle müssen ihr '
                      . 'Passwort jetzt über den Wiederherstellungsschlüssel neu '
                      . 'setzen — die Daten selbst sind davon nicht betroffen. '
                      . 'Bitte das Schlüsselblatt neu drucken.' : null;
        $error  = $ok ? null : $was;
    }
}

$sp = speicher_uebersicht();
$skZustand  = serverschluessel_zustand();
$anZustand  = anteil_zustand();
$anZaehlung = anteil_zaehlung();

/**
 * Ein Balken mit Segmenten und Legende (Baustein `.speicher-balken`).
 *
 * DIE BREITE KOMMT AUS `data-breite`, DIE FARBE AUS EINER KLASSE. Die Breite
 * ist ein gerechneter Wert und kann nicht in eine Klasse; die Farbe kommt aus
 * einer, damit kein Hexwert und kein Token in das Markup wandert
 * (`CLAUDE.md` 5).
 *
 * BIS WEB 20.6.0 STAND DIE BREITE ALS STILATTRIBUT AM ELEMENT. Ein
 * Stilattribut im Markup faellt unter `style-src` der CSP (P5a/AP4,
 * E-P5a-15); `el.style.width = …` faellt als CSSOM gar nicht darunter. Die
 * drei Zeilen am Ende dieser Seite setzen die Breite deshalb nach dem
 * Aufbau. Ohne Skript bleibt der Balken leer — die Zahlen darunter in der
 * Legende stehen trotzdem, und das ist die Auskunft, auf die es ankommt.
 *
 * OHNE BEZUG KEINE ANTEILE. Fehlt die Webspace-Angabe, werden die Segmente
 * anteilig ZUEINANDER gezeichnet und die Legende nennt nur die Summe — der
 * Balken zeigt dann die Zusammensetzung, nicht die Fuellung. Alles andere
 * hiesse, eine Bezugsgroesse zu erfinden.
 *
 * @param list<array{klasse:string,bytes:int,text:string}> $teile
 */
function speicher_balken(array $teile, int $bezug, array $schwellen): string
{
    $summe = 0;
    foreach ($teile as $t) { $summe += (int)$t['bytes']; }
    $nenner = $bezug > 0 ? $bezug : max(1, $summe);
    $frei   = $bezug > 0 ? max(0, $bezug - $summe) : 0;

    $h = '<div class="speicher-balken">';
    foreach ($teile as $t) {
        /* BLEIBT HANDRECHNUNG (Z23, AP7): Das ist eine CSS-LAENGE, kein
         * Prozenttext. Sie rundet bewusst gar nicht — `data-breite` traegt
         * unten drei Nachkommastellen, `prozent_wert()` gaebe eine ganze
         * Zahl und liesse den Balken sichtbar springen. */
        $p = $nenner > 0 ? (float)$t['bytes'] * 100 / $nenner : 0.0;
        if ($p <= 0) { continue; }
        $h .= '<span class="' . ui_e($t['klasse']) . '" data-breite="'
            . number_format(min(100, $p), 3, '.', '') . '"></span>';
    }
    /* Der Schwellenstrich sitzt als leeres Segment an seiner Stelle — ohne
     * absolute Positionierung und ohne zweite Ebene: Er ist ein Punkt AUF dem
     * Balken, und der Balken ist ein Flex-Behälter. */
    if ($bezug > 0 && $schwellen) {
        $erste  = (int)min($schwellen);
        /* BLEIBT HANDRECHNUNG (Z23, AP7): CSS-Laenge wie oben — die
         * Differenz `$erste - $bisher` wird ungerundet zur Breite der
         * Luecke vor dem Schwellenstrich. */
        $bisher = $nenner > 0 ? $summe * 100 / $nenner : 0;
        if ($erste > $bisher) {
            $h .= '<span class="speicher-luecke" data-breite="'
                . number_format(max(0, $erste - $bisher), 3, '.', '') . '"></span>';
            $h .= '<span class="speicher-marke"></span>';
        }
    }
    $h .= '</div>';

    $h .= '<div class="speicher-legende">';
    foreach ($teile as $t) {
        if ((int)$t['bytes'] <= 0) { continue; }
        $h .= '<span><i class="' . ui_e($t['klasse']) . '"></i>'
            . ui_e($t['text']) . ' ' . groesse_text((int)$t['bytes']) . '</span>';
    }
    if ($bezug > 0) {
        $h .= '<span><i class="sb-frei"></i>frei ' . groesse_text($frei) . '</span>';
        if ($schwellen) {
            $h .= '<span>Warnschwelle ' . (int)min($schwellen) . ' %</span>';
        }
    }
    return $h . '</div>';
}

ui_seite_start(['titel' => 'Servereinstellungen']);
?>

<?php /* Lesespalte (E-S8-18, Mockup 07) — damals zwei Karten mit viel
         Erklärtext; heute acht mit je einem Satz, einspaltig aus dem Grund
         im Kopf dieser Datei. */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_server', 'lesespalte' => true]); ?>

  <?php ui_titelzeile(['titel' => 'Servereinstellungen']); ?>
  <?= wartung_balken() ?>

  <?php if ($notice !== null): ?><?= ui_meldung_markup('ok', $notice) ?><?php endif; ?>
  <?php if ($error !== null): ?><?= ui_meldung_markup('fehler', $error) ?><?php endif; ?>


  <?php /* ---- Ankündigung (P5c/AP1, E-P5c-13, -55; Bild M-P5c-02 a) ---------
       ZUOBERST, wie im freigegebenen Bild: Sie ist die Karte, für die eine
       BetreiberIn diese Seite am häufigsten aufschlägt, und sie ist kurz.
       Die Schlüssel folgen unmittelbar darunter — ihre rote Lage steht
       zusätzlich auf der Statusseite und am Menüzähler.

       DREI KNÖPFE IN EINEM FORMULAR, jeder mit eigenem `action`. Die
       Rückfrage hängt deshalb am Rundmail-KNOPF und nicht am Formular
       (confirm.js: „Wenn EIN Formular mehrere Absendeknöpfe hat …").

       DER BYTEZÄHLER zählt Byte, nicht Zeichen — `app_state` fasst 190
       (E-P5c-55). Den ersten Stand rechnet der Server, das Mitzählen
       `assets/ankuendigung.js`; ohne Skript steht der Stand beim Laden. */ ?>
  <?php
    $ankGesp = ankuendigung_gespeichert();
    $ankWert = $ankForm ?? ($ankGesp !== null
        ? ['text' => $ankGesp['text'], 'ton' => $ankGesp['ton'],
           'tag'  => fmt_local(iso_utc($ankGesp['bis']), 'Y-m-d'),
           'zeit' => fmt_local(iso_utc($ankGesp['bis']), 'H:i')]
        : ['text' => '', 'ton' => 'info', 'tag' => '', 'zeit' => '']);
    $ankRest = APP_STATE_MAX - strlen(trim((string)preg_replace('/\s+/u', ' ', $ankWert['text'])));
    $rundZuletzt = app_state_lesen(RUNDMAIL_K_ZULETZT);
    $rundHeute   = rundmail_heute_gesendet() !== null;
    $rundZahl    = count(rundmail_empfaenger());
  ?>
  <?php ui_karte_start(['titel' => 'Ankündigung', 'id' => 'k-ankuendigung',
      'plakette' => $ankGesp === null
          ? ui_plakette('keine', ['ton' => 'neutral'])
          : ($ankGesp['abgelaufen']
              ? ui_plakette('abgelaufen', ['ton' => 'neutral'])
              : ui_plakette('sichtbar bis ' . datum_zeit_text(iso_utc($ankGesp['bis'])),
                            ['ton' => $ankGesp['ton'] === 'warn' ? 'orange' : 'blau']))]); ?>
    <p class="feld-hinweis">Ein Streifen über jeder Seite, bis er abläuft —
       als Rundmail höchstens einmal je Tag.
       <a href="hilfe.php#12-8-ankuendigung-und-rundmail">Wie er wirkt</a></p>
    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?>
      <?php /* VON HAND, NICHT DURCH ui_feld: Der Zähler darunter braucht eine
               Kennung (`aria-describedby`, `data-bytezaehler`), und ui_feld
               gibt seiner Kleinzeile keine. */ ?>
      <div class="feld">
        <label class="feld-label" for="f-ank_text">Text</label>
        <textarea class="feld-eingabe feld-mehrzeilig" id="f-ank_text" name="ank_text" rows="3"
                  data-bytegrenze="<?= APP_STATE_MAX ?>" data-bytezaehler="f-ank_rest"
                  aria-describedby="f-ank_rest"><?= e($ankWert['text']) ?></textarea>
        <p class="feld-klein" id="f-ank_rest"><?= $ankRest >= 0
            ? 'Noch ' . $ankRest . ' von ' . APP_STATE_MAX . ' Byte — Umlaute zählen doppelt.'
            : (-$ankRest) . ' Byte zu viel — erlaubt sind ' . APP_STATE_MAX
              . ', Umlaute zählen doppelt.' ?></p>
      </div>
      <?php ui_feld(['name' => 'ank_ton', 'label' => 'Ton', 'art' => 'select',
          'optionen' => ANKUENDIGUNG_TOENE, 'wert' => $ankWert['ton']]); ?>
      <?php ui_feld(['name' => 'ank_bis', 'label' => 'Sichtbar bis', 'art' => 'date',
          'wert' => $ankWert['tag']]); ?>
      <?php /* Textfeld mit der Klasse `zeitfeld` statt type="time" — native
               Zeitfelder zeigen je nach Systemsprache AM/PM (E1); die Maske
               haengt an der Klasse (assets/zeitfeld.js). */ ?>
      <div class="feld">
        <label class="feld-label" for="f-ank_bis_zeit">Uhrzeit
          <span class="feld-klein-inline">HH:MM</span></label>
        <input class="feld-eingabe zeitfeld" type="text" id="f-ank_bis_zeit" name="ank_bis_zeit"
               value="<?= e($ankWert['zeit']) ?>" placeholder="z. B. 21:00" autocomplete="off">
        <p class="feld-klein">Danach verschwindet der Streifen von selbst.</p>
      </div>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer',
                      'name' => 'action', 'wert' => 'ankuendigung']) ?>
        <?= ui_knopf(['text' => 'Als Rundmail senden …', 'symbol' => 'mail', 'art' => 'neutral',
                      'name' => 'action', 'wert' => 'rundmail',
                      'attr' => $rundHeute ? ' disabled' : ' data-confirm-titel="Rundmail senden?"'
                          . ' data-confirm="' . e('Der Ankündigungstext geht an ' . $rundZahl
                              . ' erreichbare Konten — alle mit gesetztem Passwort, ohne das '
                              . 'Demo-Konto. Die Mails laufen über die Warteschlange; das kann '
                              . 'einige Minuten dauern. Heute ist danach keine zweite Rundmail '
                              . 'mehr möglich.') . '"'
                          . ' data-confirm-ok="' . e('An ' . $rundZahl . ' Konten senden') . '"'
                          . ' data-confirm-tone="normal"']) ?>
        <?php if ($ankGesp !== null): ?>
          <?= ui_knopf(['text' => 'Entfernen', 'art' => 'leise',
                        'name' => 'action', 'wert' => 'ankuendigung_weg']) ?>
        <?php endif; ?>
      </div>
      <?php /* EINE WERTANZEIGE, KEIN SATZ (P5c/AP9): Die Regel „höchstens eine
               je Tag" steht im einen Satz der Karte oben. */ ?>
      <p class="feld-klein"><?= $rundZuletzt !== null && $rundZuletzt !== ''
          ? 'Letzte Rundmail: ' . e(datum_zeit_text($rundZuletzt))
          : 'noch keine Rundmail' ?><?= $rundHeute ? ' · heute schon gesendet' : '' ?></p>
    </form>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Die Schlüssel des Servers (S10, E-S10-12) -------------------
       SIE STEHT VOR DEM SPEICHER (seit P5c unter der Ankündigung). Die
       übrigen Karten dieser Seite melden Einstellungen; diese hier kann
       melden, dass sich niemand mehr anmelden kann. Eine rote Zeile unter einem Speicherbalken wird später
       gesehen als eine darüber.

       GEZEIGT WIRD NIE DER WERT, immer nur die KENNUNG (acht Hexzeichen aus
       SHA-256). Sie ist ein Vergleichsmerkmal, kein Geheimnis: Damit lässt
       sich prüfen, ob Blatt, Karte und Datenbank dasselbe meinen, ohne dass
       der Wert je auf dem Bildschirm steht. Wer ihn braucht, druckt das
       Blatt. */ ?>
  <?php
    /* VIER TOENE, UND „ok" IST KEINER DAVON. Das Stylesheet kennt
     * `.plakette-blau`, `-neutral`, `-orange` und `-rot` — nachgezaehlt am
     * 14.09.2026. Ein `'ton' => 'ok'` ergibt eine Klasse ohne Regel, also
     * eine ungestaltete Plakette, und zwar ohne jede Fehlermeldung. Zwei
     * Stellen im Bestand tun das schon (F-15). */
    $skTon = ['bereit' => 'blau', 'fehlt' => 'rot',
              'abweichend' => 'rot'][$skZustand['stand']] ?? 'neutral';
    /* „fehlt" ist NEUTRAL, nicht rot (Design.md 9.23): Ohne Anteil arbeitet
     * alles wie vorher, es geht nichts verloren. Rot bleibt der Lage
     * vorbehalten, in der niemand mehr an seine Angaben kommt. */
    $anTon = ['bereit' => 'blau', 'rotation' => 'blau', 'fehlt' => 'neutral',
              'abweichend' => 'rot'][$anZustand['stand']] ?? 'neutral';
    $anOffen = $anZaehlung['ohne'] + $anZaehlung['alt'];
  ?>
  <?php ui_karte_start(['titel' => 'Schlüssel des Servers', 'id' => 'k-schluessel',
      'plakette' => ($skZustand['stand'] === 'bereit' && $anZustand['stand'] === 'bereit')
          ? ui_plakette('in Ordnung', ['ton' => 'blau'])
          : (($skZustand['stand'] === 'abweichend' || $anZustand['stand'] === 'abweichend')
              ? ui_plakette('abweichend', ['ton' => 'rot'])
              : ($skZustand['stand'] === 'fehlt'
                  ? ui_plakette('unvollständig', ['ton' => 'rot'])
                  : ($anZustand['stand'] === 'fehlt'
                      ? ui_plakette('nicht eingerichtet', ['ton' => 'neutral'])
                      : ui_plakette('Übergang läuft', ['ton' => 'blau']))))]); ?>

    <p class="feld-hinweis">Zwei Geheimnisse in <code>config.php</code>, nicht
       in der Datenbank — gedruckt gehören beide auf das Schlüsselblatt.
       <a href="hilfe.php#karte-schluessel-des-servers-seit-web-20-1-0">Handbuch: was sie schützen und was tun, wenn einer fehlt</a></p>

    <?php
      ui_zeile([
        'text'  => 'Serverschlüssel',
        'klein' => $skZustand['stand'] === 'bereit'
            ? 'Kennung ' . $skZustand['kennung'] . ' — versiegelt Backup-Ziele, '
              . 'Komplett-Backup und Konto-Backups'
            : ($skZustand['stand'] === 'fehlt'
                /* EIN SATZ (E-P5c-06): „Fehlt. Ohne ihn …" waren zwei
                   (Endzählung AP9, die einzige Kleinzeile über dem Soll). */
                ? 'Fehlt — ohne ihn entsteht kein Komplett-Backup, kein '
                  . 'Konto-Backup und kein Versand auf ein Backup-Ziel'
                : 'In config.php steht Kennung '
                  . ($skZustand['kennung'] ?? '—') . ', versiegelt wurde mit '
                  . $skZustand['erwartet'] . ' — bis der richtige Wert '
                  . 'nachgetragen ist, lässt sich Versiegeltes nicht öffnen'),
        'plaketten' => ui_plakette(
            ['bereit' => 'vorhanden', 'fehlt' => 'fehlt',
             'abweichend' => 'abweichend'][$skZustand['stand']] ?? '?',
            ['ton' => $skTon]),
      ]);

      ui_zeile([
        'text'  => 'Server-Anteil',
        'klein' => $anZustand['stand'] === 'bereit'
            ? 'Kennung ' . $anZustand['kennung'] . ' — geht in den '
              . 'Datenschlüssel jedes Kontos ein'
            : ($anZustand['stand'] === 'rotation'
                ? 'Rotation läuft: neu ' . $anZustand['kennung'] . ', alt '
                  . $anZustand['kennung_alt'] . ' — beide werden ausgeliefert, '
                  . 'bis kein Konto mehr auf dem alten steht'
                : ($anZustand['stand'] === 'fehlt'
                    /* Kein Konzeptname im sichtbaren Text (C-4): „wie vor
                       S10" hieß für niemanden etwas; die Statusseite sagt
                       dasselbe mit der Fassung. */
                    ? 'Nicht eingerichtet — alles läuft wie vor Web 20.0.0, nur '
                      . 'der Schutz gegen einen Datenbankabzug fehlt'
                    : 'In config.php steht Kennung '
                      . ($anZustand['kennung'] ?? '—') . ', gebaut wurden die '
                      . 'Hüllen mit ' . $anZustand['erwartet'] . ' — bis das '
                      . 'behoben ist, kommt niemand mit umgestellter Hülle an '
                      . 'seine geschützten Angaben')),
        'plaketten' => ui_plakette(
            ['bereit' => 'bereit', 'rotation' => 'Rotation',
             'fehlt' => 'nicht eingerichtet',
             'abweichend' => 'abweichend'][$anZustand['stand']] ?? '?',
            ['ton' => $anTon]),
      ]);

      /* DIE ZÄHLUNG STEHT ALS EIGENE ZEILE, nicht als drei Zahlen in einer
         Plakette: Sie beantwortet eine andere Frage als der Zustand — nicht
         „ist der Anteil richtig", sondern „wie weit ist die Umstellung". */
      if ($anZustand['stand'] !== 'fehlt') {
          $teile = [$anZaehlung['neu'] . ' auf dem aktuellen Anteil'];
          if ($anZustand['kennung_alt'] !== null) {
              $teile[] = $anZaehlung['alt'] . ' auf dem alten';
          } elseif ($anZaehlung['alt'] > 0) {
              $teile[] = $anZaehlung['alt'] . ' auf einem unbekannten';
          }
          if ($anZaehlung['ohne'] > 0) { $teile[] = $anZaehlung['ohne'] . ' noch ohne'; }
          if ($anZaehlung['demo'] > 0) {
              $teile[] = 'das Demo-Konto bleibt ohne Anteil und zählt nicht mit';
          }
          /* EIN SATZ JE KLEINZEILE (P5c/AP9): Dass der alte Anteil erst weg
             darf, wenn kein Konto mehr auf ihm steht, stand bis Web 21.0.0 als
             eigener Absatz unter den Knöpfen und steht jetzt hier. */
          /* DREI LAGEN, NICHT ZWEI (C-5): Ist nichts mehr offen, stellt auch
             niemand mehr um — bis Web 21.1.0 stand dann trotzdem „jedes Konto
             stellt beim nächsten Anmelden von selbst um". */
          $umstellungRest = ($anZustand['kennung_alt'] !== null && $anZaehlung['alt'] > 0)
              ? ' — der alte Anteil bleibt, bis kein Konto mehr auf ihm steht'
              : ($anOffen > 0
                  ? ' — jedes Konto stellt beim nächsten Anmelden von selbst um'
                  : '');
          ui_zeile([
            'text'  => 'Umstellung der Konten',
            'klein' => implode(' · ', $teile) . $umstellungRest,
            'plaketten' => ui_plakette($anOffen === 0 ? 'vollständig'
                                                      : $anOffen . ' offen',
                ['ton' => 'blau']),
          ]);
      }
    ?>

    <?php /* ---- GENAU EIN PRIMAERER KNOPF, UND ZWAR DER, DER DRAN IST ----
             `Design.md` fuehrt „zwei primaere Knoepfe auf einer Seite" als
             Anti-Muster: Keiner ist dann mehr die Haupthandlung. Welche das
             ist, haengt hier an der Lage — fehlt ein Wert, ist es das
             Anlegen; weicht einer ab, das Nachtragen; sonst das Blatt. Die
             uebrigen Knoepfe sind `neutral`. */ ?>
    <?php
      $hauptAnlegenSk = $skZustand['stand'] === 'fehlt';
      $hauptAnlegenAn = !$hauptAnlegenSk && $anZustand['stand'] === 'fehlt';
      $abweichend = $skZustand['stand'] === 'abweichend'
                 || $anZustand['stand'] === 'abweichend';
      $hauptBlatt = !$hauptAnlegenSk && !$hauptAnlegenAn && !$abweichend;
    ?>
    <?php if ($skZustand['stand'] === 'fehlt' || $anZustand['stand'] === 'fehlt'): ?>
      <div class="listen-form-fuss">
        <?php if ($skZustand['stand'] === 'fehlt'): ?>
          <form method="post" action="betrieb_server.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_sk_anlegen">
            <?= ui_knopf(['text' => 'Serverschlüssel anlegen', 'symbol' => 'schloss',
                          'art' => $hauptAnlegenSk ? 'primaer' : 'neutral']) ?>
          </form>
        <?php endif; ?>
        <?php if ($anZustand['stand'] === 'fehlt'): ?>
          <form method="post" action="betrieb_server.php">
            <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_anlegen">
            <?= ui_knopf(['text' => 'Server-Anteil anlegen', 'symbol' => 'schloss',
                          'art' => $hauptAnlegenAn ? 'primaer' : 'neutral']) ?>
          </form>
        <?php endif; ?>
      </div>
    <?php endif; ?>

    <?php /* ---- Blatt, Wechseln, Entfernen ------------------------------ */ ?>
    <div class="listen-form-fuss">
      <?php if ($skZustand['kennung'] !== null || $anZustand['kennung'] !== null): ?>
        <?php /* KEIN `target="_blank"`. Der Bestand oeffnet nur FREMDE Adressen
                 in einem neuen Tab (Play-Store, Connect IQ, Lizenztext), und
                 `rechtstexte_lib.php` begruendet ausdruecklich, warum eine
                 eigene Seite es nicht tut: Sie nimmt dem Browser den
                 Zurueck-Weg. Das Blatt hat einen eigenen. */ ?>
        <?= ui_knopf(['text' => 'Schlüsselblatt drucken', 'symbol' => 'schloss',
                      'art' => $hauptBlatt ? 'primaer' : 'neutral',
                      'href' => 'betrieb_schluesselblatt.php']) ?>
      <?php endif; ?>
      <?php if (in_array($anZustand['stand'], ['bereit', 'rotation'], true)
                && $anZustand['kennung_alt'] === null): ?>
        <form method="post" action="betrieb_server.php"
              data-confirm="Den Server-Anteil wechseln? Der bisherige bleibt stehen, bis kein Konto mehr auf ihm steht — die Umstellung läuft je Konto beim nächsten Anmelden. Danach ist ein NEUES Schlüsselblatt zu drucken; das alte gilt nicht mehr."
              data-confirm-ok="Wechseln" data-confirm-tone="normal">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_wechseln">
          <?= ui_knopf(['text' => 'Server-Anteil wechseln', 'symbol' => 'tausch']) ?>
        </form>
      <?php endif; ?>
      <?php if ($anZustand['kennung_alt'] !== null && $anZaehlung['alt'] === 0): ?>
        <form method="post" action="betrieb_server.php"
              data-confirm="Den alten Server-Anteil aus config.php entfernen? Kein Konto steht mehr auf ihm — danach ist die Rotation abgeschlossen und das alte Schlüsselblatt gegenstandslos."
              data-confirm-ok="Entfernen" data-confirm-tone="normal">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_alt_entfernen">
          <?= ui_knopf(['text' => 'Alten Anteil entfernen', 'symbol' => 'korb']) ?>
        </form>
      <?php endif; ?>
    </div>


    <?php /* ---- Nachtragen vom Blatt (E-S10-10) -------------------------- */ ?>
    <?php if ($skZustand['stand'] === 'abweichend' || $anZustand['stand'] === 'abweichend'): ?>
      <h3 class="listen-form-titel">Nachtragen vom Blatt</h3>
      <?php foreach ([
            ['anteil', 'Server-Anteil', $anZustand, 'schluessel_anteil_nachtragen'],
            ['sk', 'Serverschlüssel', $skZustand, 'schluessel_sk_nachtragen'],
          ] as [$kurz, $name, $z, $aktion]): ?>
        <?php if ($z['stand'] !== 'abweichend') { continue; } ?>
        <form method="post" action="betrieb_server.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="<?= e($aktion) ?>">
          <?php /* `.feld-fest` — die Schreibmaschinenschrift, die es dafür
                   gibt (Stylesheet, `.feld-fest .feld-eingabe`). 64 Hexzeichen
                   in einer Proportionalschrift sind beim Vergleichen mit dem
                   Blatt genau die Zeile, in der man verrutscht. */ ?>
          <?php ui_feld(['name' => 'wert', 'label' => $name . ' vom Blatt',
              'klasse' => 'feld-fest',
              'attr' => ' autocomplete="off" spellcheck="false" inputmode="latin"',
              'klein' => 'Erwartet wird der Wert mit Kennung '
                       . ($z['erwartet'] ?? '—') . ' — geschrieben wird nur, wenn '
                       . 'die Kennung übereinstimmt.']); ?>
          <?php if ($z['kennung'] !== null): ?>
            <?php ui_schalter(['name' => 'ersetzen',
                'label' => 'Den vorhandenen Eintrag ersetzen',
                'an' => false,
                'klein' => 'In config.php steht bereits ein gültiger Wert '
                         . '(Kennung ' . $z['kennung'] . ') — ohne diesen Haken '
                         . 'wird nichts überschrieben.']); ?>
          <?php endif; ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => $name . ' nachtragen', 'symbol' => 'haken',
                          'art' => 'primaer']) ?><?php /* die Haupthandlung
                          dieser Lage — siehe oben */ ?>
          </div>
        </form>
      <?php endforeach; ?>
    <?php endif; ?>

    <?php /* ---- Neuanfang (E-S10-09, Zustand „Neuanfang") ---------------- */ ?>
    <?php if ($anZustand['stand'] === 'abweichend'): ?>
      <h3 class="listen-form-titel">Neuanfang <span class="feld-klein-inline">nur, wenn der Wert unwiederbringlich weg ist</span></h3>
      <div class="listen-form-fuss">
        <form method="post" action="betrieb_server.php"
              data-confirm="Einen NEUEN Server-Anteil erzeugen? Jede NutzerIn mit umgestellter Hülle muss danach ihr Passwort über den Wiederherstellungsschlüssel neu setzen. Die Daten selbst bleiben unversehrt. Dieser Schritt lässt sich nicht zurücknehmen — der bisherige Wert ist danach gegenstandslos."
              data-confirm-ok="Neu erzeugen" data-confirm-tone="gefahr">
          <?= csrf_field() ?><input type="hidden" name="action" value="schluessel_anteil_neuanfang">
          <?= ui_knopf(['text' => 'Server-Anteil neu erzeugen', 'symbol' => 'warnung',
                        'art' => 'gefahr']) ?>
        </form>
      </div>
    <?php endif; ?>

    <?php if (isset($anZaehlung['fehler'])): ?>
      <?= ui_meldung_markup('fehler', 'Die Konten ließen sich nicht zählen: '
          . $anZaehlung['fehler']) ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Speicher', 'id' => 'k-speicher',
      'zahl' => $sp['stand'] !== null
          ? 'Stand ' . datum_zeit_text((string)$sp['stand'])
          : 'noch nicht gemessen',
      'plakette' => $sp['backups']['bezug'] > 0
          ? ui_plakette($sp['backups']['prozent'] . ' %',
                        ['ton' => speicher_ton($sp['backups']['prozent'], $sp['schwellen'])])
          : '']); ?>

    <?php if ($sp['stand'] === null): ?>
      <?= ui_meldung_markup('info', 'Datenbank und Dateien sind noch nicht gemessen. '
          . 'Die Messung läuft einmal täglich im Aufräumjob mit; bis dahin zeigt '
          . '„Installation gesamt" nur die Backups.') ?>
    <?php endif; ?>

    <h3 class="listen-form-titel">Backups
      <span class="feld-klein-inline"><?= groesse_text($sp['backups']['summe']) ?>
        von <?= groesse_text($sp['backups']['bezug']) ?> Grenze ·
        <?= (int)$sp['backups']['prozent'] ?> %</span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-konto',    'bytes' => $sp['backups']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['backups']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['backups']['bezug'], $sp['schwellen']) ?>

    <h3 class="listen-form-titel">Installation gesamt
      <span class="feld-klein-inline"><?= groesse_text($sp['gesamt']['summe']) ?><?php
        if ($sp['gesamt']['bezug'] > 0): ?> von
        <?= groesse_text($sp['gesamt']['bezug']) ?> Webspace ·
        <?= (int)$sp['gesamt']['prozent'] ?> %<?php
        else: ?> — ohne Webspace-Angabe kein Anteil<?php endif; ?></span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-db',       'bytes' => $sp['gesamt']['datenbank'],
           'text' => 'Datenbank'],
          ['klasse' => 'sb-dateien',  'bytes' => $sp['gesamt']['dateien'],
           'text' => 'Dateien'],
          ['klasse' => 'sb-konto',    'bytes' => $sp['gesamt']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['gesamt']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['gesamt']['bezug'], $sp['schwellen']) ?>

    <p class="feld-hinweis">Der freie Webspace wird nicht gemessen, nur was
       diese Installation belegt.
       <a href="hilfe.php#speicher">Handbuch: was gezählt wird</a></p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="speicher">
      <div class="fld-reihe">
        <?php /* DIE DREI FORMULARWERTE BLEIBEN BYTE-DIVISION (Z22, AP7) — und
                 zwar mit PUNKT als Dezimaltrenner. Derselbe POST-Zweig oben liest
                 sie mit `is_numeric()` und `(float)` wieder ein (Zeilen 54/55,
                 97/103, 121/132); `groesse_text()` schriebe „2,00 GB", und das
                 Formular waere nicht mehr abzuschicken — zu merken erst beim
                 Speichern. Der Kommentar steht IM Tag, damit er kein Leerzeichen
                 in die Ausgabe schreibt. */ ui_feld(['name' => 'grenze', 'label' => 'Speichergrenze Backups',
            'wert' => rtrim(rtrim(number_format(
                          edbak_grenze_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.'),
            'klein' => 'GB für Konto- und Komplett-Backups zusammen — ist sie '
                     . 'erreicht, wird nicht mehr gesichert, aber nichts gelöscht.']); ?>
        <?php ui_feld(['name' => 'schwellen', 'label' => 'Warnschwellen',
            'wert' => implode(', ', edbak_schwellen()),
            'klein' => 'Prozent, durch Komma getrennt, für beide Balken — je '
                     . 'Schwelle einmal eine Meldung.']); ?>
      </div>
      <div class="fld-reihe">
        <?php ui_feld(['name' => 'webspace', 'label' => 'Webspace laut Hosting',
            'label_zusatz' => 'optional',
            'wert' => speicher_webspace_bytes() > 0
                ? rtrim(rtrim(number_format(
                      speicher_webspace_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.')
                : '',
            'klein' => 'GB, aus dem Hosting-Tarif abgelesen — ohne Angabe zeigt '
                     . '„Installation gesamt" nur die Summe.']); ?>
        <?php ui_feld(['name' => 'db_gb', 'label' => 'Kontingent der Datenbank',
            'wert' => rtrim(rtrim(number_format(
                          speicher_db_kontingent_bytes() / (1024 * 1024 * 1024),
                          2, '.', ''), '0'), '.'),
            'klein' => 'GB, aus dem Hosting-Tarif abgelesen — leer setzt auf die '
                     . 'Vorgabe ' . SPEICHER_DB_GB_VORGABE . ' GB zurück.']); ?>
      </div>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php
      /* ---- Ablage und Reste: rein lesend (AB-08, zieht mit S8 hierher) --- */
      ui_zeile([
        'text'  => 'Ablage',
        'klein' => (string)$sp['ablage']['pfad'],
        'plaketten' => $sp['ablage']['ok']
            ? ui_plakette('beschreibbar', ['ton' => 'blau'])
            : ui_plakette('nicht beschreibbar', ['ton' => 'rot']),
      ]);
      if (!$sp['ablage']['ok'] && $sp['ablage']['grund'] !== null) {
          echo ui_meldung_markup('fehler', (string)$sp['ablage']['grund']);
      }
      ui_zeile([
        'text'  => 'Reste abgebrochener Läufe',
        'klein' => 'Bauordner und .tmp-Dateien — werden vom Aufräumjob entfernt',
        'plaketten' => ui_plakette((string)$sp['reste'],
            ['ton' => $sp['reste'] > 0 ? 'orange' : 'neutral']),
      ]);
      ui_zeile([
        'text'  => 'Pakete in der Ablage',
        'klein' => $sp['ordner'] . ' Konten mit mindestens einem Konto-Backup',
        'plaketten' => ui_plakette((string)$sp['pakete'], ['ton' => 'neutral']),
      ]);
    ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Sicherheitskopfzeilen (P5a/AP4, E-P5a-15, Backlog Nr. 8) --
           Sie steht zwischen Speicher und Adresssuche: naeher an „was der
           Server tut" als an „was er speichert". */ ?>
  <?php
    $cspBerichte = [];
    try {
        $cspBerichte = db()->query(
            'SELECT richtlinie, quelle, seite, anzahl, zuletzt
               FROM csp_berichte ORDER BY zuletzt DESC LIMIT 20')->fetchAll();
    } catch (Throwable $ex) { /* Tabelle fehlt — Migration steht noch aus */ }
  ?>
  <?php ui_karte_start(['titel' => 'Sicherheitskopfzeilen', 'id' => 'k-kopfzeilen',
      'plakette' => kopf_csp_scharf()
          ? ui_plakette('CSP scharf', ['ton' => 'blau'])
          : ui_plakette('CSP meldet nur', ['ton' => 'orange'])]); ?>
    <p class="feld-hinweis">Die Content-Security-Policy sagt dem Browser, woher
       er etwas laden darf — erst beobachten, dann scharf schalten.
       <a href="hilfe.php#karte-sicherheitskopfzeilen-seit-web-20-7-0">Handbuch: Sicherheitskopfzeilen</a></p>
    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="kopfzeilen">
      <?php ui_schalter(['name' => 'csp_scharf', 'label' => 'Richtlinie scharf schalten',
          'an' => kopf_csp_scharf(),
          'klein' => 'Aus heißt „nur melden" — scharf schalten, wenn nach zwei '
                   . 'Wochen unten keine unerklärten Berichte stehen.']); ?>
      <h3 class="listen-form-titel">Dauer der HTTPS-Bindung (HSTS) <span class="feld-klein-inline">klein anfangen</span></h3>
      <?php ui_segment(['name' => 'hsts_tage', 'wert' => (string)kopf_hsts_tage(),
          'label' => 'Dauer der HTTPS-Bindung',
          'optionen' => ['0' => 'aus', '1' => '1 Tag', '7' => '7 Tage',
                         '365' => '1 Jahr']]); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php if ($cspBerichte): ?>
      <h3 class="listen-form-titel">Berichte der letzten 30 Tage</h3>
      <?php foreach ($cspBerichte as $b): ?>
        <?php ui_zeile([
          'text'  => (string)$b['richtlinie'] . ' · ' . (string)$b['quelle'],
          'klein' => 'auf ' . (string)$b['seite'] . ' · zuletzt '
                   . datum_zeit_text((string)$b['zuletzt']) . ' Uhr',
          'plaketten' => ui_plakette((string)$b['anzahl'] . ' Meldungen', ['ton' => 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php else: ?>
      <?php ui_zeile(['text' => 'Berichte der letzten 30 Tage',
          'klein' => 'Keine — die Richtlinie ist vollständig, oder niemand war auf '
                   . 'einer Seite, die etwas nachlädt (Karten, Import, Export).',
          'plaketten' => ui_plakette('0', ['ton' => 'blau'])]); ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Konten (P5b/AP1, E-P5b-14) ---------------------------------
     *
     * VOR dem Ratenschutz und nach den Kopfzeilen: Was diese Installation
     * mit Konten TUT, steht vor dem, womit sie sich WEHRT. Beides gehoert
     * auf diese Seite (R74/E-S8-12 — was alle trifft, steht im Betrieb),
     * aber die Betriebsart ist die Frage, die eine BetreiberIn zuerst
     * stellt.
     * ------------------------------------------------------------------- */ ?>
  <?php require_once __DIR__ . '/konten_einstellungen_lib.php';
        require_once __DIR__ . '/protokoll_lib.php';
        $kE = konten_einstellungen(); ?>
  <?php ui_karte_start(['titel' => 'Konten', 'id' => 'k-konten',
      'plakette' => ui_plakette(
          ['offen' => 'Registrierung offen',
           'freischaltung' => 'mit Freischaltung',
           'einladung' => 'nur auf Einladung'][konten_reg_art()],
          ['ton' => konten_reg_art() === 'offen' ? 'orange' : 'blau'])]); ?>
    <?php if ($demoFrage): ?>
      <?php /* Kein neuer Baustein: `.meldung` mit einem Knopf darin, wie ihn
               die Anwendung an mehreren Stellen fuehrt (`index.php`,
               `betrieb_schluesselblatt.php`). Von Hand und nicht ueber
               `ui_meldung_markup()`, weil der Knopf hier in einem eigenen
               FORMULAR steckt; der Baustein nimmt nur fertiges Markup fuer
               den Knopf, kein Formular darum.

               DER TON HEISST `info` UND NICHT `blau` (Backlog Nr. 226).
               Genau dieser Fehler stand hier: Die Toene sind
               `fehler, warn, ok, info, schutz`, und `meldung-blau` hat keine
               Regel im Stylesheet — der Kasten stand ungestaltet da, ohne
               jede Fehlermeldung. `ui_meldung_markup()` wirft bei einem
               unbekannten Ton; wer von Hand baut, hat diesen Schutz nicht.
               Gefunden hat es `tools/quelltext/vollstaendigkeit.py`. */ ?>
      <div class="meldung meldung-info" role="status">
        <?= ui_symbol('hinweis', 'symbol-gross') ?>
        <?php /* EIN ABSATZ, NICHT ZWEI. `.meldung` ist eine Flexzeile —
                 ein zweiter `<p>` stellt sich NEBEN den ersten und schiebt
                 den Knopf in die naechste Zeile. Gemessen am 17.09.2026;
                 `ui_meldung_markup()` gibt aus demselben Grund immer genau
                 einen Absatz aus. */ ?>
        <p><strong>Die Registrierung ist jetzt geschlossen.</strong> Die
           <strong>Demo-Anmeldung</strong> ist weiterhin zugelassen — wer die
           Adresse aus dem Handbuch kennt, kommt also weiter herein. Soll sie
           mit abgeschaltet werden? Der Bestand des Demo-Kontos bleibt in
           jedem Fall erhalten; abgeschaltet wird nur die Anmeldung daran.</p>
        <form method="post" action="betrieb_server.php">
          <?= csrf_field() ?><input type="hidden" name="action" value="demo_aus">
          <?= ui_knopf(['text' => 'Demo-Anmeldung auch abschalten',
                        'symbol' => 'schloss', 'art' => 'neutral']) ?>
        </form>
      </div>
    <?php endif; ?>
    <p class="feld-hinweis">Die Vorgabe ist „nur auf Einladung", damit niemand
       durch Untätigkeit eine offene Registrierung bekommt.
       <a href="hilfe.php#karte-konten-seit-web-20-16-5">Handbuch: Konten</a></p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="konten">

      <?php ui_feld(['name' => 'reg_art', 'label' => 'Registrierung', 'art' => 'select',
          'optionen' => KONTEN_REG_ARTEN, 'wert' => konten_reg_art()]); ?>

      <?php ui_feld(['name' => 'reg_frist', 'label' => 'Wartende Registrierungen verfallen nach',
          'art' => 'number', 'label_zusatz' => 'Tagen',
          'wert' => (string)konten_reg_frist_tage(),
          'klein' => 'Gilt nur bei „mit Freischaltung"; wer seine Adresse gar nicht '
                   . 'bestätigt, verfällt davon getrennt nach '
                   . KONTEN_UNBESTAETIGT_H . ' Stunden.']); ?>

      <h3 class="listen-form-titel">Wegwerfadressen</h3>
      <?php ui_schalter(['name' => 'wegwerf', 'label' => 'Wegwerfadressen abweisen',
          'an' => konten_wegwerf_an(),
          'klein' => 'Gegen die mitgelieferte Liste, ohne Anfrage bei Dritten und '
                   . 'ohne Wirkung bei „nur auf Einladung".']); ?>
      <?php ui_feld(['name' => 'wegwerf_eigene', 'label' => 'Zusätzlich abweisen',
          'art' => 'textarea', 'zeilen' => 2,
          'wert' => implode(', ', konten_wegwerf_eigene()),
          'platzhalter' => 'beispiel.invalid, noch-eine.test',
          'klein' => 'Reine Domains ohne „@", durch Komma oder Zeilenumbruch '
                   . 'getrennt.']); ?>

      <h3 class="listen-form-titel">Was ein Konto halten darf
        <span class="feld-klein-inline">— je Konto überschreibbar</span></h3>
      <?php ui_feld(['name' => 'grenze_einsaetze', 'label' => 'Einsätze je Konto',
          'art' => 'number', 'wert' => (string)konten_grenze_einsaetze(),
          'klein' => 'Ab ' . (int)(KONTEN_WARNSCHWELLE * 100) . ' % geht eine '
                   . 'Nachricht heraus, bei 100 % weder Gerätedaten noch Import — '
                   . 'Bearbeiten und Löschen bleiben frei.']); ?>
      <?php ui_feld(['name' => 'grenze_mb', 'label' => 'Speicher je Konto',
          'art' => 'number', 'label_zusatz' => 'MB',
          'wert' => (string)konten_grenze_mb(),
          'klein' => 'Einsätze samt GPS-Daten und Ruhesegmenten, gemessen wie die '
                   . 'Statistik zählt.']); ?>
      <h3 class="listen-form-titel">Demo</h3>
      <?php ui_schalter(['name' => 'demo_anmeldung', 'label' => 'Demo-Anmeldung zulassen',
          'an' => konten_demo_anmeldung_an(),
          'klein' => 'Aus heißt: Die Demo-Adresse gilt bei der Anmeldung als '
                   . 'unbekannt, ihr Bestand bleibt.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Protokoll: Frist und Archiv (P5c/AP2, E-P5c-02, -03, -24) ----
           Die Frist der Verwaltungseinträge stand bis Web 20.38.0 in der
           Karte „Konten". Sie gehört zu dem, was sie begrenzt: dem
           Protokoll und seinem Archiv. Gelesen wird das Protokoll unter
           Verwaltung → Protokoll; hier steht, wie lange es liegt. */
        require_once __DIR__ . '/protokoll_archiv_lib.php';
        $archive = protokoll_archive(); ?>
  <?php ui_karte_start(['titel' => 'Protokoll', 'id' => 'k-protokoll',
      'plakette' => ui_plakette(count($archive) . (count($archive) === 1 ? ' Archiv' : ' Archive'),
                                ['ton' => $archive ? 'blau' : 'neutral']),
      'aktion' => ['text' => 'Protokoll lesen', 'href' => 'admin_protokoll.php']]); ?>
    <p class="feld-hinweis">Was hier steht, begrenzt, wie lange Betriebsereignisse
       liegen — in der Datenbank und im versiegelten Archiv.
       <a href="hilfe.php#11-7-protokoll">Wie das Archiv arbeitet</a></p>
    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="protokoll">
      <?php ui_feld(['name' => 'protokoll_frist',
          'label' => 'Verwaltungseinträge aufbewahren',
          'art' => 'number', 'label_zusatz' => 'Tage',
          'wert' => (string)protokoll_frist_verwaltung(),
          'klein' => 'Zwischen ' . PROTOKOLL_FRIST_MIN . ' und ' . PROTOKOLL_FRIST_MAX
                   . ' Tagen; alle übrigen Einträge verfallen fest nach '
                   . PROTOKOLL_FRIST_UEBRIGE . ' Tagen.']); ?>
      <?php ui_feld(['name' => 'archiv_tage', 'label' => 'Archiv alle',
          'art' => 'number', 'label_zusatz' => 'Tage',
          'wert' => (string)protokoll_archiv_tage(),
          'klein' => 'Zwischen ' . PROTOKOLL_ARCHIV_TAGE_MIN . ' und '
                   . PROTOKOLL_ARCHIV_TAGE_MAX . ' Tagen.']); ?>
      <?php ui_feld(['name' => 'archiv_behalten', 'label' => 'Archive aufbewahren',
          'art' => 'number', 'label_zusatz' => 'Tage',
          'wert' => (string)protokoll_archiv_behalten(),
          'klein' => 'Zwischen ' . PROTOKOLL_ARCHIV_BEHALTEN_MIN . ' und '
                   . PROTOKOLL_ARCHIV_BEHALTEN_MAX . ' Tagen, danach löscht der Job '
                   . 'sie hier — auf dem Backup-Ziel bleiben sie.']); ?>
      <?php ui_schalter(['name' => 'archiv_versand',
          'label' => 'Archive auf das Backup-Ziel schicken',
          'an' => protokoll_archiv_versand(),
          'klein' => 'Mit dem Versandjob, wie Konto-Backups und Komplett-Stände.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Ratenschutz (P5a/AP6, E-P5a-04 bis -07) --------------------
           Sie steht NEBEN den Sicherheitskopfzeilen, weil beide dieselbe Frage
           beantworten: Was haelt jemanden auf, der es von aussen versucht?
           Die LISTE der laufenden Sperren steht bewusst NICHT hier, sondern
           kommt nach Status -> Sicherheit (E-P5a-08, Mockup M-P5a-01) — wer
           sie jetzt hier baut, baut sie zweimal. */ ?>
  <?php require_once __DIR__ . '/ratelimit_lib.php'; ?>
  <?php $vBr = rate_verlangsamung(true); ?>
  <?php ui_karte_start(['titel' => 'Ratenschutz', 'id' => 'k-ratenschutz',
      'plakette' => $vBr['stufe'] > 0
          ? ui_plakette('Verlangsamung Stufe ' . $vBr['stufe'], ['ton' => 'orange'])
          : ui_plakette('ruhig', ['ton' => 'blau'])]); ?>
    <p class="feld-hinweis">Eine Sperre dauert beim zweiten Mal länger und
       zählt am eingetippten Namen, nicht am Konto.
       <a href="hilfe.php#11-4a-ratenschutz-was-jemanden-aufhaelt-der-es-von-aussen-versucht">Handbuch: Ratenschutz</a></p>
    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="ratenschutz">

      <?php ui_feld(['name' => 'leiter', 'label' => 'Sperrleiter',
          'label_zusatz' => 'vier Dauern in Minuten, aufsteigend',
          'wert' => implode(', ', array_map(
                        static fn(int $s): int => (int)($s / 60), rate_leiter())),
          'platzhalter' => '15, 20, 30, 60',
          'klein' => 'Die vierte Sprosse gilt für jede weitere Sperre; die erste '
                   . 'nicht unter 15 setzen, ohne es zu wollen.']); ?>

      <?php ui_feld(['name' => 'login', 'label' => 'Fehlversuche je Konto',
          'art' => 'number',
          'label_zusatz' => 'bis zur Sperre, je 15 Minuten',
          'wert' => (string)rate_grenze('login')['max']]); ?>
      <?php ui_feld(['name' => 'login_ip', 'label' => 'Fehlversuche je Anschluss',
          'art' => 'number',
          'label_zusatz' => 'bis zur Sperre, je 15 Minuten',
          'wert' => (string)rate_grenze('login_ip')['max'],
          'klein' => 'Die größere Zahl, weil sich hinter einem Klinik-Anschluss '
                   . 'viele eine Adresse teilen.']); ?>

      <h3 class="listen-form-titel">Verlangsamung statt globaler Sperre</h3>
      <?php ui_feld(['name' => 'bremse', 'label' => 'Schwellen der Verlangsamung',
          'label_zusatz' => 'vier Zahlen, Fehlversuche je 15 Minuten',
          'wert' => implode(', ', rate_bremse_schwellen()),
          'platzhalter' => '200, 400, 800, 1600',
          'klein' => 'Darüber antwortet jede fehlgeschlagene Anmeldung langsamer '
                   . '(1, 2, 4, 8 Sekunden), die gelungene ohne Verzögerung.']); ?>

      <?php ui_schalter(['name' => 'mail', 'label' => 'Bei der höchsten Stufe melden',
          'an' => rate_mail_an(),
          'klein' => 'Eine Sammelmeldung an die Betreiberadresse aus Verwaltung → '
                   . 'Installation, höchstens eine je Stunde.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php $sperren = rate_sperren_aktiv(8); ?>
    <?php if ($sperren): ?>
      <h3 class="listen-form-titel">Läuft gerade
        <span class="feld-klein-inline">— <a href="betrieb_sicherheit.php">alle
        samt „Sperre aufheben" unter Status → Sicherheit</a></span></h3>
      <?php foreach ($sperren as $sp): ?>
        <?php ui_zeile([
          'text'  => (string)$sp['merkmal'],
          'klein' => 'Topf ' . (string)$sp['topf'] . ' · noch '
                   . max(1, (int)round($sp['rest'] / 60)) . ' Minuten',
          'plaketten' => ui_plakette('Stufe ' . (int)$sp['stufe'], ['ton' => 'orange']),
        ]); ?>
      <?php endforeach; ?>
    <?php else: ?>
      <?php ui_zeile(['text' => 'Laufende Sperren',
          'klein' => 'Keine — das ist der Normalfall.',
          'plaketten' => ui_plakette('0', ['ton' => 'blau'])]); ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Adresssuche (S9/AP2, E-S9-05, R79) ------------------------
           Sie steht im Betrieb und nicht in den Kontoeinstellungen, weil sie
           die Installation betrifft: Wer sie hier abschaltet, schaltet sie
           fuer alle ab (R74 (1) — BetreiberIn, trifft alle). Der Kontoschalter
           daneben steht im Profil und kann nur noch einschraenken. */ ?>
  <?php ui_karte_start(['titel' => 'Adresssuche', 'id' => 'k-adresssuche',
      'plakette' => geocoder_installation_an()
          ? ui_plakette('an', ['ton' => 'blau'])
          : ui_plakette('aus', ['ton' => 'neutral'])]); ?>
    <p class="feld-hinweis">Beim Tippen in einem Ortsfeld und nach jeder Wahl auf
       der Karte gehen der getippte Text und die Koordinate an einen Adressdienst.
       <a href="hilfe.php#karte-adresssuche-seit-web-15-8-0">Handbuch: Adresssuche</a></p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="geocoder">
      <?php ui_schalter(['name' => 'adresssuche', 'label' => 'Adresssuche im Internet',
          'an' => geocoder_installation_an(),
          'klein' => 'Aus heißt: keine Vorschläge, keine Umkehrsuche, kein '
                   . 'Suchfeld im Kartendialog — für alle Konten dieser '
                   . 'Installation.']); ?>
      <?php ui_feld(['name' => 'dienst', 'label' => 'Dienst',
          'wert' => geocoder_dienst(),
          'attr' => ' maxlength="180" inputmode="url"',
          'klein' => 'Ein Photon-Dienst mit „https://", Vorgabe '
                   . GEOCODER_VORGABE . ' — ein eigener hält die Anfragen im '
                   . 'Haus.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

<?php /* DIE BALKENBREITEN — nach dem Aufbau, nicht im Markup (P5a/AP4).
         Siehe `speicher_balken()` oben: Ein `style`-Attribut faellt unter
         `style-src` der CSP, `el.style.width` nicht. Der Block traegt den
         Nonce der Anfrage. */ ?>
<script<?= kopf_nonce_attr() ?>>
document.querySelectorAll('.speicher-balken [data-breite]').forEach(function (el) {
  el.style.width = el.getAttribute('data-breite') + '%';
});
</script>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/zeitfeld.js', 'assets/ankuendigung.js']]); ?>
