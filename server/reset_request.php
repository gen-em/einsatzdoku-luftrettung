<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/smtp.php';
require_once __DIR__ . '/ratelimit_lib.php';

/**
 * Zuruecksetzen anfordern.
 *
 * DREI DINGE MUSSTEN SICH AENDERN
 *
 *  1. MENGE (M1-08). Der Endpunkt war ohne Anmeldung erreichbar und ohne jede
 *     Begrenzung: Er taugte als Adressenpruefer und als Mailschleuder auf
 *     fremde Postfaecher. Jetzt zaehlt jede Anfrage (rate_zaehlen).
 *
 *  2. DAUER (M1-07). Der Antworttext war fuer beide Faelle derselbe, die
 *     Dauer nicht: Bei vorhandenem Konto lief ein vollstaendiges
 *     Mailgespraech, sonst kam die Antwort sofort. Eine einzige Anfrage je
 *     Adresse genuegte, um die Konten zu finden. Jetzt wird die Antwort
 *     ABGESCHLOSSEN, bevor der Versand beginnt (antwort_abschliessen), und
 *     beide Zweige liegen zusaetzlich auf derselben Mindestdauer.
 *
 *  3. MENGE GUELTIGER TOKENS. Jede Anfrage legte einen weiteren Token an;
 *     alle blieben eine Stunde lang gueltig. Jetzt entwertet ein neuer den
 *     alten — es gibt zu jedem Zeitpunkt hoechstens einen. Dasselbe Muster
 *     wie bei den Kopplungscodes (einstellungen.php).
 *
 *     Bewusst NICHT umgesetzt: den vorhandenen Token einfach stehen lassen
 *     und keine zweite Mail schicken. Das erreicht dieselbe Zahl gueltiger
 *     Tokens, macht aber die Aussage der Seite ("es wurde eine E-Mail
 *     verschickt") fuer eine Stunde unwahr — und wer die erste Mail im
 *     Spamordner hat, kaeme nicht weiter, ohne zu erfahren warum.
 */

/**
 * Mindestdauer beider Zweige.
 *
 * Deckt den messbaren Teil ab: eine zusaetzliche Abfrage, eine Entwertung und
 * eine Einfuegung — zusammen deutlich unter einer Millisekunde. Eine halbe
 * Sekunde liegt weit darueber und faellt auf einer Seite, die eine Person
 * einmal im Jahr aufruft, nicht ins Gewicht. Der Versand selbst liegt hinter
 * dem Abschluss der Antwort und wird nicht mitgemessen.
 */
const RESET_MINDESTDAUER = 0.5;

$t0 = microtime(true);
$done = false;
$mailAuftrag = null;      // erst NACH dem Abschluss der Antwort ausgefuehrt

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = email_normalisieren($_POST['email'] ?? '');
    $done  = true;        // die Antwort ist in jedem Fall dieselbe

    if (rate_erlaubt('reset', $email)) {
        // Kein Scheitern moeglich — begrenzt wird die Menge der Anfragen.
        rate_zaehlen('reset', $email);

        $st = db()->prepare('SELECT id FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch();

        /* Das Demo-Konto bekommt keinen Reset-Link (E-P1-19).
         *
         * Sein Passwort ist oeffentlich und steht im Handbuch; ein
         * Zuruecksetzen kann es nur unbrauchbar machen. Die Adresse ist
         * ebenfalls oeffentlich — ohne diese Abweisung koennte jeder eine
         * E-Mail an sie ausloesen, so oft die Mengenbremse es zulaesst.
         *
         * STILL abgewiesen, nicht mit eigener Meldung: Die Antwort dieser
         * Seite ist fuer jede Adresse dieselbe („falls es ein Konto gibt,
         * ist eine Mail unterwegs"). Eine Sondermeldung fuer das Demo-Konto
         * waere die einzige Stelle, an der die Seite verraet, welche
         * Adressen es gibt. */
        if ($u && demo_ist_demo((int)$u['id'])) { $u = false; }

        if ($u) {
            // Hoechstens EIN gueltiger Token je Konto: Der neue entwertet
            // alle offenen. Sonst sammelten sich mit jeder Anfrage weitere
            // gueltige Links an, und jeder einzelne davon reicht aus.
            db()->prepare('UPDATE password_resets SET used_at = NOW()
                           WHERE user_id = ? AND used_at IS NULL')
                ->execute([(int)$u['id']]);

            $token = bin2hex(random_bytes(32));
            db()->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                           VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 1 HOUR))')
                ->execute([(int)$u['id'], hash('sha256', $token)]);
            $link = $CFG['app']['base_url'] . '/pw_handling.php?token=' . $token;
            $mailAuftrag = [$email,
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
                . "Falls du das nicht angefordert hast, kannst du diese E-Mail einfach ignorieren —\n"
                . "es wurde nichts geändert.\n\n"
                . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
                . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n"];
        }
    }
    // Auch der gesperrte Fall bekommt dieselbe Antwort: Eine eigene Meldung
    // "zu viele Versuche" waere die naechste Auskunft — sie erschiene nur dort,
    // wo jemand oft genug dieselbe Adresse angefragt hat.
    rate_gleiche_dauer($t0, RESET_MINDESTDAUER);
}
/* Fuer logo_src(): Ohne session_lib.php faende es logo_stamm() nicht und
 * fiele still auf den Hubschrauber zurueck, gleich was die Installation
 * eingestellt hat — F-P3-AN, eine Seite weiter. */
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start(['titel' => 'Passwort zurücksetzen', 'klasse' => 'anmeldung-body']);
?>
<main class="anmeldung">
 <div class="anmeldung-karte">
  <?php /* DAS LOGO STEHT JETZT AUCH HIER (O10). Diese Seite ist die
           Nachbarin der Anmeldung — dieselbe dunkelblaue Fläche, dieselbe
           Karte —, und sie war die einzige der drei ohne Marke. logo_src()
           braucht dafür session_lib.php; die Datei lädt sie seit Web 9.10.1
           ohnehin (F-P3-AN). */ ?>
  <img src="<?= e(logo_src()) ?>" alt="" class="anmeldung-logo">
  <h1 class="anmeldung-titel">Passwort setzen</h1>
  <?php if ($done): ?>
    <?= ui_meldung_markup('ok',
        'Wenn die Adresse registriert ist, wurde eine E-Mail mit einem Link '
      . 'verschickt. Der Link ist eine Stunde gültig.') ?>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>
  <?php else: ?>
    <p class="anmeldung-unter">E-Mail-Adresse eingeben — du bekommst einen Link,
       um ein neues Passwort zu setzen.</p>
    <form method="post">
      <?php ui_feld(['name' => 'email', 'label' => 'E-Mail', 'art' => 'email',
                     'pflicht' => true, 'attr' => ' autofocus']); ?>
      <?= ui_knopf(['text' => 'Link anfordern', 'art' => 'primaer', 'breit' => true]) ?>
    </form>
    <p class="anmeldung-neben"><a href="login.php">Zurück zur Anmeldung</a></p>
  <?php endif; ?>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>
<?php
ui_seite_ende();

/* ---- Erst antworten, dann versenden ---------------------------------------
 * Ab hier laeuft nichts mehr, was die aufrufende Seite noch zu sehen bekommt.
 * Das Mailgespraech dauert je nach Mailserver Sekunden — es darf nicht in der
 * Antwortzeit stecken, sonst ist die Dauer die Auskunft, die der gleiche
 * Antworttext gerade verhindern soll (M1-07). */
if ($mailAuftrag !== null) {
    antwort_abschliessen();
    smtp_send($mailAuftrag[0], $mailAuftrag[1], $mailAuftrag[2]);
}
