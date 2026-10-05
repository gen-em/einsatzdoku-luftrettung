<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/mail_lib.php';
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/konten_einstellungen_lib.php';
require_once __DIR__ . '/konto_lib.php';
require_once __DIR__ . '/rechtstexte_lib.php';
require_once __DIR__ . '/einwilligung_lib.php';

/**
 * SELBSTREGISTRIERUNG (P5b/AP3, E-P5b-01, -02, -03, -13).
 *
 * ---------------------------------------------------------------------------
 * DIE SEITE GIBT KEINE AUSKUNFT UEBER KONTEN
 * ---------------------------------------------------------------------------
 *
 * Freie Adresse, belegte Adresse, Wegwerfadresse, Demo-Adresse, gesperrter
 * Ratenschutz — **fuenf Faelle, eine Antwort**. Unterschieden wird
 * ausschliesslich in der Mail, und die landet im Postfach des Besitzers und
 * nicht auf dem Bildschirm dessen, der das Formular abgeschickt hat.
 *
 * Das Muster ist `reset_request.php` (M1-07): Antwort abschliessen, DANN
 * versenden, und beide Zweige auf derselben Mindestdauer. Ohne das waere die
 * DAUER die Auskunft, die der gleiche Text gerade verhindert — ein
 * vollstaendiges Mailgespraech dauert Sekunden, ein `return` nicht.
 *
 * ---------------------------------------------------------------------------
 * DAS PASSWORT WIRD HIER NICHT GESETZT, UND DAS HAT EINEN ZWINGENDEN GRUND
 * ---------------------------------------------------------------------------
 *
 * Der Datenschluessel haengt seit S10 am Server-Anteil, und der wird per
 * `HMAC(kdf_anteil, 'konto:<id>')` aus der **Kontonummer** abgeleitet
 * (`serverkrypto_lib.php`, E-S10-17). Eine Registrierungsseite hat die
 * Kontonummer nicht — das Konto entsteht ja erst mit ihr. Der Browser kann
 * `pat_wrap_pw` also nicht bilden, und eine Huelle ohne Anteil weist
 * `huelle_pw_pruefen()` ab, sobald die Installation einen ausliefert.
 *
 * Deshalb: **Konto zuerst, Schluessel danach** — genau wie beim
 * Einladungsweg, den E-P5b-13 als Vorbild nennt. Diese Seite legt das Konto
 * `unbestaetigt` an und verschickt einen Link auf `pw_handling.php`; dort
 * entstehen Passwort, Datenschluessel und Wiederherstellungsschluessel auf
 * dem einen gepruefteren Weg, den es dafuer gibt, und dort ist die
 * Kontonummer da. Danach fuehrt `pw_handling.php` auf `bestaetigen.php`.
 *
 * Das freigegebene Mockup M-P5b-02a zeichnet die Passwortfelder auf dieser
 * Seite. Die Abweichung ist am 17.09.2026 mit dem Auftraggeber geklaert:
 * lieber zwei Felder weniger als ein zweiter Weg, auf dem ein Anteil das
 * Haus verlaesst.
 *
 * ---------------------------------------------------------------------------
 * VIER BREMSEN, KEINE DAVON SICHTBAR
 * ---------------------------------------------------------------------------
 *
 *  1. HONEYPOT `website` — ein Feld ausserhalb des Sichtbereichs, das leer
 *     bleiben muss. **Nicht `display:none`**: Ein Feld, das der Browser gar
 *     nicht darstellt, fuellt auch kein Bot, und der Fang ginge ins Leere.
 *  2. MINDESTAUSFUELLDAUER 4 s, ueber einen **signierten** Zeitstempel im
 *     Formular. Ohne Signatur waere er eine Zahl, die der Absender selbst
 *     bestimmt.
 *  3. Die drei Toepfe `reg` (IP), `regg` (global) und `regz` (Zieladresse) —
 *     Begruendung in `ratelimit_lib.php`.
 *  4. DIE RECHENAUFGABE (seit Web 21.14.0, Schritt 18, SR-08, E-SR-26) —
 *     ein Proof-of-Work: Der Server stellt beim Zeichnen des Formulars eine
 *     Aufgabe, `assets/pow-worker.js` sucht im Browser eine Zahl, mit der
 *     SHA-256 ueber Aufgabe und Zahl mit `POW_BITS` Nullbits beginnt, und
 *     der Server prueft das mit EINER Rechnung. Sie laeuft, waehrend die
 *     Person tippt; spuerbar wird sie nur fuer ein Skript, das das Formular
 *     sofort und tausendfach abschickt. R37 (4) nannte sie „notfalls"; seit
 *     dem 27.09.2026 ist sie per Entscheidung der Betreiberin gebaut, nicht
 *     auf Anlass (Backlog Nr. 228). GEPRUEFT WIRD SIE ZUERST: Sie eroeffnet
 *     den stillen Teil (E-SR-88), vor Honeypot, Stempel und Toepfen.
 *
 * ALLE VIER SCHEITERN STILL. Wer gefangen wird, bekommt dieselbe Antwort wie
 * jeder andere. Eine Meldung „Honeypot gefuellt" waere eine Bauanleitung.
 *
 * ---------------------------------------------------------------------------
 * SEIT SR-08 HAT DIE SEITE EINE SITZUNG — ABER NUR, WENN SIE OFFEN IST
 * ---------------------------------------------------------------------------
 *
 * Die Aufgabe muss EINMAL gelten, und das weiss nur, wer sie sich gemerkt
 * hat (E-SR-92, Q-SR-19). Ein signierter Wert wie der Zeitstempel liesse
 * sich innerhalb seiner Frist beliebig oft vorzeigen. Deshalb startet die
 * Seite die Sitzung der Anwendung wie `login.php` — aber nur bei offener
 * Registrierung: Eine Anlage „nur auf Einladung" setzt hier weiter kein
 * Cookie und legt keine Sitzungsdatei an. Wer die Seite einer offenen
 * Registrierung aufruft, bekommt dagegen beides, auch ein Bot; das ist der
 * Preis, und er steht im Cookie-Baustein des Handbuchs (11.5a).
 *
 * OHNE JAVASCRIPT GEHT ES NICHT, und das war vorher nur fuer den ganzen Weg
 * wahr (F-SR-92): `pw_handling.php` brauchte es schon immer, diese Seite
 * nicht. Seit der Rechenaufgabe scheiterte eine Absendung ohne Skript still
 * — deshalb steht unter dem Knopf ein Satz, den `pow.js` wegnimmt, sobald es
 * laeuft.
 */

/** Wie `RESET_MINDESTDAUER` — deckt Abfrage, Anlage und Tokenausgabe ab. */
const REG_MINDESTDAUER = 0.5;

/** Mindestens so lange muss das Formular offen gewesen sein (R37 (4)). */
const REG_MINDESTDAUER_FORMULAR = 4;

/** Danach ist der Zeitstempel verbraucht — ein Formular, das zwei Stunden
 *  offen lag, ist kein Mensch mehr, sondern ein Wiederholungslauf.
 *  SEIT SR-08 AUCH DIE FRIST DER RECHENAUFGABE (E-SR-93, Q-SR-20): Das
 *  Konzept sah zehn Minuten vor; wer so lange die Nutzungsbedingungen liest,
 *  bekaeme die Danke-Karte und keine Mail. Eine Zahl fuer beides, weil beide
 *  dasselbe fragen — wie lange darf ein Formular offen liegen. */
const REG_FORMULAR_GILT_S = 7200;

/**
 * Die Schwierigkeit der Rechenaufgabe: so viele Nullbits muss
 * SHA-256(aufgabe . loesung) vorn haben. Im Mittel 2^16 Versuche im Browser,
 * eine Rechnung auf dem Server (SR-08, E-SR-26).
 *
 * HERKUNFT — gemessen am 05.10.2026 in Chromium 141 (Arbeitsumgebung, vier
 * Kerne), mit dem Worker aus `assets/pow-worker.js`, je 30 Laeufe:
 *   ungedrosselt          158 343 Versuche/s · Median 0,47 s · hoechstens 1,34 s
 *   ein Viertel der CPU    34 875 Versuche/s · Median 1,71 s · hoechstens 6,02 s
 * Ziel war der Median unter einer Sekunde ungedrosselt und unter drei auf
 * dem alten Diensthandy, dafuer steht die gedrosselte Zeile. Gedrosselt
 * wurde NICHT ueber die Entwicklerwerkzeuge des Browsers: Deren Drosselung
 * erreicht keinen Worker (F-SR-95, gemessen), sondern ueber eine CPU-Quote
 * des Betriebssystems auf ein Viertel dessen, was der Lauf ungedrosselt
 * verbraucht. 17 Bit haetten den gedrosselten Median auf rund 2,6 s gehoben
 * — unter drei, aber ohne Spielraum fuer ein Ersatzmodell, das ein echtes
 * Handy nur naehert. Die Zahlen stehen im Pruefdokument von Konzept SR.
 *
 * WER SIE AENDERT: Eine Stufe weniger halbiert die Zeit. Ist das alte
 * Diensthandy spuerbar langsamer als drei Sekunden, ist es hier eins weniger
 * (P-SR-14) — und eine neue Messung gehoert dann in den Kommentar.
 */
const POW_BITS = 16;

/** So viele offene Aufgaben haelt eine Sitzung (E-SR-89). Zwei Reiter oder
 *  ein nach einem sichtbaren Fehler neu gezeichnetes Formular sollen die
 *  erste Aufgabe nicht still entwerten; mehr als fuenf braucht kein Mensch. */
const POW_OFFEN_HOECHSTENS = 5;

/**
 * Das Geheimnis, mit dem der Zeitstempel signiert wird.
 *
 * Muster und Begruendung wie `salt_secret` in `auth_salt.php`: Es entsteht
 * beim ersten Bedarf und bleibt danach stehen. In `config.php` gehoert es
 * nicht — es schuetzt kein Geheimnis, es macht nur eine Zahl faelschungsfest,
 * und ein verlorenes Geheimnis kostet hier nichts ausser den gerade offenen
 * Formularen.
 */
function reg_geheimnis(): string
{
    static $sec = null;
    if ($sec !== null) { return $sec; }
    return $sec = app_state_einmalig('reg_secret', fn (): string => bin2hex(random_bytes(32)));
}

/** Der signierte Zeitstempel, wie er ins Formular geht: `<zeit>.<hmac>`. */
function reg_stempel(): string
{
    $t = (string)time();
    return $t . '.' . hash_hmac('sha256', $t, reg_geheimnis());
}

/**
 * Hat das Formular lange genug offen gelegen?
 *
 * `false` bei jedem Zweifel — fehlender Stempel, falsche Signatur, zu jung,
 * zu alt. Der Aufrufer macht daraus keine Meldung, sondern dieselbe Antwort
 * wie sonst.
 */
function reg_stempel_ok(string $roh): bool
{
    $teile = explode('.', $roh, 2);
    if (count($teile) !== 2 || !ctype_digit($teile[0])) { return false; }
    if (!hash_equals(hash_hmac('sha256', $teile[0], reg_geheimnis()), $teile[1])) {
        return false;
    }
    $alter = time() - (int)$teile[0];
    return $alter >= REG_MINDESTDAUER_FORMULAR && $alter <= REG_FORMULAR_GILT_S;
}

/**
 * Eine neue Rechenaufgabe fuer das Formular, das gerade gezeichnet wird.
 *
 * 32 Zufallsbyte als Hex — und in der Sitzung mit ihrem Verfall als GANZE
 * ZAHL (`<aufgabe> => <bis>`): Abgelaufene fallen beim naechsten Aufruf weg,
 * und es bleiben hoechstens `POW_OFFEN_HOECHSTENS`, die juengsten.
 */
function pow_aufgabe_neu(): string
{
    $jetzt = time();
    $liste = [];
    foreach ((array)($_SESSION['pow'] ?? []) as $a => $bis) {
        if (is_string($a) && is_int($bis) && $bis >= $jetzt) { $liste[$a] = $bis; }
    }
    $neu = bin2hex(random_bytes(32));
    $liste[$neu] = $jetzt + REG_FORMULAR_GILT_S;
    $_SESSION['pow'] = array_slice($liste, -POW_OFFEN_HOECHSTENS, null, true);
    return $neu;
}

/**
 * Ist die Rechenaufgabe geloest? Eine SHA-256, unter einer Millisekunde.
 *
 * DIE AUFGABE IST DANACH VERBRAUCHT, OB SIE GELOEST WAR ODER NICHT — ein
 * Absenden, eine Aufgabe (E-SR-89). Sonst liesse sich eine geloeste Aufgabe
 * so oft vorzeigen, wie die Toepfe es zulassen, und die Rechnung waere
 * einmal bezahlt statt je Versuch.
 *
 * `false` bei jedem Zweifel — fremde, abgelaufene oder schon benutzte
 * Aufgabe, eine Loesung, die keine Dezimalzahl mit hoechstens zwoelf Stellen
 * ist (E-SR-91), zu wenige Nullbits. Wie beim Stempel macht der Aufrufer
 * daraus keine Meldung.
 */
function pow_ok(string $aufgabe, string $loesung): bool
{
    if (!preg_match('/^[0-9a-f]{64}$/', $aufgabe)) { return false; }
    $liste = (array)($_SESSION['pow'] ?? []);
    $bis = $liste[$aufgabe] ?? null;
    unset($liste[$aufgabe]);
    $_SESSION['pow'] = $liste;
    if (!is_int($bis) || $bis < time()) { return false; }
    if (!preg_match('/^[0-9]{1,12}$/', $loesung)) { return false; }

    $h = hash('sha256', $aufgabe . $loesung, true);
    $voll = intdiv(POW_BITS, 8);
    for ($i = 0; $i < $voll; $i++) {
        if ($h[$i] !== "\0") { return false; }
    }
    $rest = POW_BITS % 8;
    return $rest === 0 || (ord($h[$voll]) >> (8 - $rest)) === 0;
}

$art        = konten_reg_art();
$offen      = konten_reg_offen();
$mitFrei    = konten_reg_freischaltung();
$fristTage  = konten_reg_frist_tage();

/* WELCHE TEXTE GELTEN GERADE (Backlog Nr. 231). Einmal gelesen und sowohl
 * fuer die Pruefung als auch fuer das Markup benutzt: Wuerden beide Seiten
 * getrennt fragen, koennte die BetreiberIn zwischen Anzeige und Absenden
 * einen Text in Kraft setzen, und das Formular verlangte einen Haken, den es
 * nie gezeigt hat. */
$ewInKraft = einwilligung_in_kraft();

/* DIE SITZUNG NUR BEI OFFENER REGISTRIERUNG (E-SR-92). HTTPS davor wie in
 * `login.php`: Das Sitzungscookie traegt `secure`, und ueber HTTP kaeme es
 * nie zurueck — jede Absendung scheiterte dann still an der Aufgabe. */
if ($offen) {
    https_tor();
    sitzung_starten('app');
}

$t0 = microtime(true);

/* FELDER NUR ALS ZEICHENKETTE (seit Web 21.14.0, F-SR-96). Bis dahin endete
 * `email[]=…` in einem TypeError und einer 500, `name[]`, `website[]` und
 * `zeit[]` in je einer PHP-Warnung im Reiter System — beides ohne Auskunft,
 * aber eine Einladung, den Reiter mit Zeilen zu fuellen. Eine Liste ist hier
 * nie gemeint; sie gilt als leer. Die Haekchen `ew[…]` SIND eine Liste und
 * bleiben, wie sie sind. */
$feld = static fn (string $k): string => is_string($_POST[$k] ?? null) ? $_POST[$k] : '';

$done = false;
$error = null;
$mailAuftrag = null;      // erst NACH dem Abschluss der Antwort ausgefuehrt
$sammelPruefen = false;   // die Verwaltung benachrichtigen? (erst bei `wartet`)

if ($offen && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = email_normalisieren($feld('email'));
    $name  = trim($feld('name'));
    $done  = true;        // die Antwort ist in jedem Fall dieselbe

    /* DIE HAEKCHEN SIND DAS EINZIGE, WAS EINE MELDUNG BEKOMMT (E-P5b-05).
     * Sie sind keine Sicherheitsvorkehrung, sondern eine Willenserklaerung:
     * Wer sie vergisst, hat sich vertippt und nicht angegriffen — und eine
     * stille Ablehnung liesse ihn raten, warum keine Mail kommt. */
    /* NUR WAS IN KRAFT IST. Bis Web 20.22.1 lief die Schleife ueber
     * `RT_EINWILLIGUNG` und verlangte damit auch die Annahme von Texten, die
     * gar nicht hinterlegt sind — siehe `einwilligung_in_kraft()`. */
    $fehlt = [];
    foreach (array_keys($ewInKraft) as $schluessel) {
        if (empty($_POST['ew'][$schluessel])) { $fehlt[] = RT_TEXTE[$schluessel]; }
    }

    if ($fehlt) {
        /* DIE MELDUNG ZAEHLT MIT. Sie sagte bis Web 20.22.2 „Ohne alle drei",
         * und das stimmte nur, solange es immer drei waren. Seit die Seite
         * zeigt, was in Kraft ist, kann auch eines fehlen — dann stand dort
         * „Es fehlt noch: Nutzungsbedingungen. Ohne alle drei". Gefunden im
         * Prueffall mit einem einzigen geltenden Text. */
        $done  = false;
        $error = (count($fehlt) === 1
                    ? 'Es fehlt noch: ' . $fehlt[0] . '. Ohne diese Zustimmung '
                    : 'Es fehlen noch: ' . implode(', ', $fehlt) . '. Ohne diese Zustimmungen ')
               . 'lässt sich kein Konto anlegen.';
    } elseif (!email_pruefen($email)) {
        $done  = false;
        $error = 'Diese E-Mail-Adresse sieht nicht wie eine Adresse aus.';
    } else {
        /* AB HIER WIRD NICHTS MEHR GEMELDET. Jeder der folgenden Zweige
         * endet in derselben Antwortkarte; unterschieden wird nur, WELCHE
         * Mail hinausgeht — und ob ueberhaupt eine. */
        $mailGeht = false;

        /* DIE RECHENAUFGABE ZUERST (SR-08, E-SR-88). Sie eroeffnet den
         * stillen Teil und verbraucht ihre Aufgabe auch dann, wenn danach
         * eine andere Bremse greift. Vor den beiden sichtbaren Pruefungen
         * oben steht sie NICHT: Die sind ein Vertipper und keine Bremse
         * (E-P5b-05), und das Formular wird danach ohnehin mit einer neuen
         * Aufgabe gezeichnet. */
        $gerechnet = pow_ok($feld('pow_aufgabe'), $feld('pow_loesung'));

        $honig    = trim($feld('website'));
        $stempel  = $feld('zeit');
        $menschlich = $gerechnet && $honig === '' && reg_stempel_ok($stempel);

        if ($menschlich
            && rate_reg_erlaubt()
            && rate_reg_ziel_erlaubt($email)
            && !wegwerf_trifft($email)
            && !demo_ist_demo_adresse($email)) {

            $st = db()->prepare('SELECT id FROM users WHERE email = ?');
            $st->execute([$email]);
            $vorhanden = $st->fetch();

            if ($vorhanden) {
                /* BELEGT: kein Konto, keine Aenderung — aber eine Mail an
                 * den Besitzer. Er erfaehrt damit, dass jemand seine Adresse
                 * eingetippt hat, und das ist die einzige Stelle, an der er
                 * es erfahren kann. */
                $mailAuftrag = ['registrierung_bekannt', $email, []];
                $mailGeht = true;
            } else {
                try {
                    $neu = konto_anlegen($email, $name, 'user', 'registrierung',
                                         'unbestaetigt', null, TOKEN_REGISTRIERUNG_S);

                    /* DIE HAEKCHEN FESTHALTEN (Backlog Nr. 231, E-P5b-05:
                     * „Gespeichert je Konto mit Fassungskennung und Zeit").
                     * Bis Web 20.22.1 geschah das NICHT: Die Seite verlangte
                     * die Haken und vergass sie im selben Atemzug — der
                     * einzige Schreibweg lag am Tor beim Login.
                     *
                     * HIER UND NICHT ERST AM TOR, weil hier der Vertrag
                     * geschlossen wird. Die NutzerIn erklaert mit dem Absenden
                     * ihren Willen; das Tor ist dafuer da, eine NEUE Fassung
                     * nachzuholen, nicht die erste zu erheben. Stuende hier
                     * nichts, beantwortete sie dieselben Fragen zweimal.
                     *
                     * DASS DAS KONTO NOCH `unbestaetigt` IST, steht dem nicht
                     * entgegen. Festgehalten wird, was an diesem Formular
                     * erklaert wurde — nicht, dass die Adresse jemandem
                     * gehoert. Wird die Registrierung nie bestaetigt, raeumt
                     * `job_konto_verfall()` das Konto weg und die Zeilen mit
                     * ihm; es bleibt kein Beleg fuer eine Erklaerung stehen,
                     * die niemand abgegeben hat.
                     *
                     * `einwilligung_setzen()` liest die Fassung selbst und
                     * gibt `false` zurueck, wenn der Text nicht in Kraft ist.
                     * Der Rueckgabewert wird bewusst nicht geprueft: Die
                     * Schleife laeuft ohnehin nur ueber das, was in Kraft
                     * war, als das Formular gebaut wurde — und hat die
                     * BetreiberIn einen Text inzwischen zurueckgezogen, ist
                     * das kein Fall fuer eine Fehlermeldung an die
                     * Registrierende, sondern einer fuer das Tor beim ersten
                     * Login. */
                    foreach (array_keys($ewInKraft) as $schluessel) {
                        einwilligung_setzen((int)$neu['id'], $schluessel);
                    }

                    $link = app_url('/pw_handling.php?token=' . $neu['token']);
                    $mailAuftrag = ['registrierung', $email, ['link' => $link]];
                    $mailGeht = true;
                } catch (Throwable $ex) {
                    /* EIN WETTLAUF, KEIN FEHLER: Zwischen der Abfrage oben
                     * und dem INSERT kann dieselbe Adresse angelegt worden
                     * sein. `ist_dublettenfehler()` prueft nur SQLSTATE
                     * 23000 — `users` hat zwei eindeutige Schluessel, und
                     * eine Kollision der Kontokennung saehe genauso aus.
                     * Beide Faelle enden hier gleich: still, mit derselben
                     * Antwort. Protokolliert wird trotzdem, sonst bliebe
                     * ein echter Datenbankfehler unsichtbar. */
                    system_melden('registrieren', 'Anlage gescheitert', $ex);
                }
            }
        }

        rate_reg_zaehlen($email, $mailGeht);
    }

    rate_gleiche_dauer($t0, REG_MINDESTDAUER);
}

/* Fuer logo_src(): ohne session_lib.php faende es logo_stamm() nicht und
 * fiele still auf den Hubschrauber zurueck (F-P3-AN). */
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start(['titel' => 'Konto anlegen', 'klasse' => 'anmeldung-body']);

$unterzeile = match ($art) {
    'offen'         => instanz_kurz() . ' · Registrierung offen',
    'freischaltung' => instanz_kurz() . ' · Registrierung offen, mit Freischaltung',
    default         => instanz_kurz(),
};
?>
<main class="anmeldung">
 <?php ui_hinweise(); ?>
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel">Konto anlegen</h1>
  <p class="anmeldung-unter"><?= e($unterzeile) ?></p>

  <?php if (!$offen): ?>
    <?php /* NUR AUF EINLADUNG: Die Seite bleibt erreichbar und sagt, woran
             es liegt. Ein 404 waere unhoeflich und nicht einmal geheimer —
             die Betriebsart steht ohnehin auf jeder Registrierungsseite, die
             es NICHT gibt. */ ?>
    <?= ui_meldung_markup('info',
        'Diese Installation nimmt Registrierungen nur auf Einladung an. '
      . 'Wende dich an die BetreiberIn — sie kann dir ein Konto anlegen.') ?>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>

  <?php elseif ($done): ?>
    <?= ui_meldung_markup('ok', 'Wenn die Adresse frei ist, ist eine Mail mit dem '
      . 'Bestätigungslink unterwegs — er gilt 48 Stunden. Kommt nichts an, prüfe '
      . 'den Spam-Ordner oder die Schreibweise.', 'Danke.') ?>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>

  <?php else: ?>
    <?php if ($error !== null): ?>
      <?= ui_meldung_markup('fehler', $error) ?>
    <?php endif; ?>

    <?= ui_meldung_markup('info', $mitFrei
        ? 'Nach der Bestätigung deiner Adresse prüft die BetreiberIn die '
          . 'Registrierung — in der Regel innerhalb von ' . $fristTage . ' Tagen. '
          . 'Wegwerfadressen werden nicht angenommen.'
        : 'Nach der Bestätigung deiner Adresse legst du dein Passwort fest und '
          . 'kannst sofort loslegen. Wegwerfadressen werden nicht angenommen.') ?>

    <?php /* DIE RECHENAUFGABE (SR-08): Bitzahl und Adresse des Workers kommen
             als Attribute aus derselben Konstante und aus `asset()` — mit
             Erkennungswert, damit ein geaenderter Worker nicht aus dem
             Zwischenspeicher kommt, und ohne zweite Zahl im Skript
             (E-SR-90). */ ?>
    <form method="post" data-pow data-pow-bits="<?= POW_BITS ?>"
          data-pow-worker="<?= e(asset('assets/pow-worker.js')) ?>">
      <?php ui_feld(['name' => 'email', 'label' => 'E-Mail', 'art' => 'email',
                     'pflicht' => true, 'wert' => $feld('email'),
                     'platzhalter' => 'name@klinik.example',
                     'attr' => ' autofocus autocomplete="email"']); ?>
      <?php ui_feld(['name' => 'name', 'label' => 'Name',
                     'wert' => $feld('name'),
                     'platzhalter' => 'Wie KollegInnen dich kennen',
                     'klein' => 'Steht in der Kopfleiste und auf deinen Einsätzen. '
                              . 'Lässt sich später ändern.',
                     'attr' => ' maxlength="120" autocomplete="name"']); ?>

      <?php /* DER HONEYPOT (R37 (4)).
               `.nur-vorlesen` statt `display:none`, weil ein Feld, das der
               Browser nicht darstellt, auch kein Bot ausfuellt — dann finge
               die Falle nichts.
               DAZU `aria-hidden` UND `tabindex="-1"`: Ohne beides waere es
               eine Falle fuer genau die Leute, die einen Bildschirmleser
               benutzen — sie hoeren „Website" und tippen etwas ein. Ein
               Honeypot, der Blinde aussperrt, ist keiner. */ ?>
      <label class="nur-vorlesen" aria-hidden="true">Website
        <input type="text" name="website" value="" tabindex="-1"
               autocomplete="off"></label>
      <input type="hidden" name="zeit" value="<?= e(reg_stempel()) ?>">
      <?php /* `autocomplete="off"`, weil Firefox versteckte Felder beim Neuladen
               sonst mit dem alten Wert fuellt — die Aufgabe des neuen
               Formulars stuende dann neben der Loesung des alten. */ ?>
      <input type="hidden" name="pow_aufgabe" value="<?= e(pow_aufgabe_neu()) ?>" autocomplete="off">
      <input type="hidden" name="pow_loesung" value="" autocomplete="off">

      <?php /* DIE HAEKCHEN — Wortlaut aus dem Katalog und nicht aus dem
               Markup: „angenommen" und „zur Kenntnis genommen" tragen den
               rechtlichen Unterschied (E-P5b-05), und ein Text, der an zwei
               Stellen steht, laeuft auseinander.

               ES SIND NICHT IMMER DREI. Gezeigt wird, was in Kraft ist
               (Backlog Nr. 231) — solange die geprueften Texte fehlen, also
               keines. Das ist kein Fehler, sondern dieselbe Regel, nach der
               das Tor beim Login arbeitet: Ein Text ohne Standdatum verlangt
               nichts. Wer sich in diesem Zustand registriert, wird beim
               ersten Login nach dem Einspielen am Tor gefasst; die Annahme
               geht also nicht verloren, sie faellt spaeter. */ ?>
      <?php foreach (array_keys($ewInKraft) as $schluessel): ?>
        <label>
          <input type="checkbox" name="ew[<?= e($schluessel) ?>]" value="1"
                 <?= !empty($_POST['ew'][$schluessel]) ? 'checked' : '' ?>>
          <span><?= rt_haken_satz($schluessel) ?></span>
        </label>
      <?php endforeach; ?>

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Konto anlegen', 'art' => 'primaer', 'breit' => true,
                      'attr' => ' data-pow-knopf']) ?>
      </div>
      <?php /* OHNE SKRIPT STEHT HIER EIN SATZ, MIT SKRIPT ZUNAECHST NICHTS
               (F-SR-92): `pow.js` nimmt ihn weg, sobald es laeuft, und
               schreibt „Sicherheitspruefung laeuft …" hinein, wenn jemand
               schneller abschickt, als der Worker rechnet. Der Knopf steht
               frei im Markup, wie in `zweitfaktor.js` — gesperrt wird er nur
               vom Skript. */ ?>
      <p class="zustandszeile" data-pow-zustand>Ohne JavaScript lässt sich hier kein Konto anlegen.</p>
    </form>
    <p class="anmeldung-neben"><a href="login.php">Schon ein Konto? Anmelden</a></p>
  <?php endif; ?>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php
ui_seite_ende(['skripte' => $offen && !$done ? ['assets/pow.js'] : []]);

/* ---- Erst antworten, dann versenden ---------------------------------------
 * Ab hier laeuft nichts mehr, was die aufrufende Seite zu sehen bekommt. Das
 * Mailgespraech dauert je nach Mailserver Sekunden; stuende es in der
 * Antwortzeit, waere die DAUER die Auskunft, die der gleiche Antworttext
 * gerade verhindert (M1-07). */
if ($mailAuftrag !== null) {
    antwort_abschliessen();
    mail_einreihen($mailAuftrag[0], $mailAuftrag[1], $mailAuftrag[2]);
}
