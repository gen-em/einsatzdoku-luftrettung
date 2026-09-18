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
 * DREI BREMSEN, KEINE DAVON SICHTBAR
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
 *
 * ALLE DREI SCHEITERN STILL. Wer gefangen wird, bekommt dieselbe Antwort wie
 * jeder andere. Eine Meldung „Honeypot gefuellt" waere eine Bauanleitung.
 */

/** Wie `RESET_MINDESTDAUER` — deckt Abfrage, Anlage und Tokenausgabe ab. */
const REG_MINDESTDAUER = 0.5;

/** Mindestens so lange muss das Formular offen gewesen sein (R37 (4)). */
const REG_MINDESTDAUER_FORMULAR = 4;

/** Danach ist der Zeitstempel verbraucht — ein Formular, das zwei Stunden
 *  offen lag, ist kein Mensch mehr, sondern ein Wiederholungslauf. */
const REG_FORMULAR_GILT_S = 7200;

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
    $pdo = db();
    $v = $pdo->query("SELECT v FROM app_state WHERE k = 'reg_secret'")->fetchColumn();
    if ($v === false) {
        $v = bin2hex(random_bytes(32));
        $pdo->prepare("INSERT IGNORE INTO app_state (k, v) VALUES ('reg_secret', ?)")
            ->execute([$v]);
    }
    return $sec = (string)$v;
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

$art        = konten_reg_art();
$offen      = konten_reg_offen();
$mitFrei    = konten_reg_freischaltung();
$fristTage  = konten_reg_frist_tage();

/* WELCHE TEXTE GELTEN GERADE (Backlog Nr. 231). Einmal gelesen und sowohl
 * fuer die Pruefung als auch fuer das Markup benutzt: Wuerden beide Seiten
 * getrennt fragen, koennte die Betreiberin zwischen Anzeige und Absenden
 * einen Text in Kraft setzen, und das Formular verlangte einen Haken, den es
 * nie gezeigt hat. */
$ewInKraft = einwilligung_in_kraft();

$t0 = microtime(true);
$done = false;
$error = null;
$mailAuftrag = null;      // erst NACH dem Abschluss der Antwort ausgefuehrt
$sammelPruefen = false;   // die Verwaltung benachrichtigen? (erst bei `wartet`)

if ($offen && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = email_normalisieren($_POST['email'] ?? '');
    $name  = trim((string)($_POST['name'] ?? ''));
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

        $honig    = trim((string)($_POST['website'] ?? ''));
        $stempel  = (string)($_POST['zeit'] ?? '');
        $menschlich = $honig === '' && reg_stempel_ok($stempel);

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
                     * geschlossen wird. Die Nutzerin erklaert mit dem Absenden
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
                     * Betreiberin einen Text inzwischen zurueckgezogen, ist
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
                    error_log('registrieren: Anlage gescheitert — ' . $ex->getMessage());
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
      . 'Wende dich an die Betreiberin — sie kann dir ein Konto anlegen.') ?>
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
        ? 'Nach der Bestätigung deiner Adresse prüft der Betreiber die '
          . 'Registrierung — in der Regel innerhalb von ' . $fristTage . ' Tagen. '
          . 'Wegwerfadressen werden nicht angenommen.'
        : 'Nach der Bestätigung deiner Adresse legst du dein Passwort fest und '
          . 'kannst sofort loslegen. Wegwerfadressen werden nicht angenommen.') ?>

    <form method="post">
      <?php ui_feld(['name' => 'email', 'label' => 'E-Mail', 'art' => 'email',
                     'pflicht' => true, 'wert' => (string)($_POST['email'] ?? ''),
                     'platzhalter' => 'name@klinik.example',
                     'attr' => ' autofocus autocomplete="email"']); ?>
      <?php ui_feld(['name' => 'name', 'label' => 'Name',
                     'wert' => (string)($_POST['name'] ?? ''),
                     'platzhalter' => 'Wie Kolleginnen dich kennen',
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
        <?= ui_knopf(['text' => 'Konto anlegen', 'art' => 'primaer', 'breit' => true]) ?>
      </div>
    </form>
    <p class="anmeldung-neben"><a href="login.php">Schon ein Konto? Anmelden</a></p>
  <?php endif; ?>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php
ui_seite_ende();

/* ---- Erst antworten, dann versenden ---------------------------------------
 * Ab hier laeuft nichts mehr, was die aufrufende Seite zu sehen bekommt. Das
 * Mailgespraech dauert je nach Mailserver Sekunden; stuende es in der
 * Antwortzeit, waere die DAUER die Auskunft, die der gleiche Antworttext
 * gerade verhindert (M1-07). */
if ($mailAuftrag !== null) {
    antwort_abschliessen();
    mail_einreihen($mailAuftrag[0], $mailAuftrag[1], $mailAuftrag[2]);
}
