<?php
declare(strict_types=1);
/**
 * NOTZUGANG DER EINZIGEN BETREIBERIN — der Zweitfaktor ohne Gerät, Codes und
 * Notfallblatt (Schritt 18, SR-04; Backlog Nr. 249; E-SR-13, E-SR-24,
 * E-SR-81 bis -87).
 *
 * WOFUER ES DIESE SEITE GIBT. Wer den Zweitfaktor verliert, hat drei Wege
 * zurueck: die Wiederherstellungscodes, den Rueckweg mit dem Notfallblatt
 * (Konzept RW) und eine zweite BetreiberIn, die zuruecksetzt. Hat die
 * EINZIGE BetreiberIn alle drei nicht, blieb bis Web 21.12.2 nur SQL im
 * Datenbankwerkzeug des Hosters — an der Anwendung vorbei, ohne Protokoll,
 * ohne Mail. Diese Seite ist der vierte Weg, und sie verlangt DREI Dinge:
 *
 *   1. EINE DATEI IM ANWENDUNGSVERZEICHNIS (Schreibnachweis, E-SR-81). Die
 *      Seite nennt einen Namen, die BetreiberIn legt eine Datei dieses
 *      Namens per FTP an. Wer das kann, hat den Webspace — dasselbe
 *      Vertrauen wie bei dem, der heute SQL absetzt. Der Name haengt an der
 *      SITZUNG (E-SR-82): Eine liegengebliebene Datei aus einem
 *      abgebrochenen Versuch gilt nicht mehr. Die Anwendung SCHREIBT hier
 *      nie eine Datei — eine Schreiboperation auf einen unangemeldeten
 *      Aufruf ist die falsche Antwort auf jede Frage (`wiederherstellen.php`).
 *   2. DEN WERT AUS DER DATENBANK, `app_state.notzugang_geheim` (E-SR-24):
 *      64 Hexzeichen, die nirgends in der Oberflaeche stehen, auch nicht auf
 *      dem Schluesselblatt. Er schuetzt gegen den, der Dateien nur LESEN
 *      kann (ein Webspace-Backup), und gegen den, der nur die Datenbank hat.
 *      Nach jedem gelungenen Notzugang wird er neu gewuerfelt.
 *   3. DAS PASSWORT DES KONTOS — als Token, wie bei der Anmeldung (E-SR-84):
 *      Das Passwort verlaesst den Browser nicht.
 *
 * EHRLICH DAZU (E-SR-24): Wer auf dem Webspace SCHREIBEN kann, kann auch eine
 * PHP-Datei hochladen und hat damit die Datenbank. Gegen ihn hilft kein Wert
 * auf dem Server — das gilt fuer jeden Weg, den die Anwendung selbst anbietet.
 *
 * DIE TUER IST ENG (E-SR-13, E-SR-85). Sie oeffnet nur fuer ein Konto der
 * Rolle BetreiberIn, aktiv, mit eingeschaltetem Zweitfaktor, und nur, wenn es
 * GENAU EINE BetreiberIn gibt (`betreiberinnen_zahl()` zaehlt jedes Konto der
 * Rolle, auch ein gesperrtes oder eingeladenes). Bei zweien setzt die andere
 * zurueck; eine Tuer, die dann offen bliebe, waere ein zweiter Weg ohne Not.
 *
 * SIE GIBT KEINE AUSKUNFT (K-11). Jede Lage — keine Datei, falscher Wert,
 * falsches Passwort, zwei BetreiberInnen, ein Admin-Konto, eine erfundene
 * Adresse — antwortet mit demselben Satz und derselben Dauer. Deshalb laufen
 * IMMER alle Pruefungen, ohne fruehe Rueckkehr, und erst danach wird
 * entschieden. Die Seite selbst sieht in jeder Lage gleich aus.
 *
 * SIE MELDET NIEMANDEN AN (E-SR-86, E-SR-87). Danach geht es ueber die
 * Anmeldung mit dem Passwort ins Einrichtungstor. Der Protokolleintrag traegt
 * deshalb den Urheber `job` — wie beim Passwort-Reset ueber den Mail-Link;
 * Text und `daten.weg = notweg` sagen, was es war.
 */
if (!file_exists(__DIR__ . '/config.php')) { header('Location: install.php'); exit; }
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/instanz_lib.php';
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/email_lib.php';
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/totp_lib.php';
require_once __DIR__ . '/serverkrypto_lib.php';
require_once __DIR__ . '/nachweis_lib.php';

/* HTTPS ZUERST, wie in `login.php`: Das Sitzungscookie traegt `secure`, und
 * ueber HTTP kaeme das Formular zurueck, als waere alles falsch. */
https_tor();

/* Zeitpunkt fuer die gleiche Dauer — VOR jeder Arbeit. */
$t0 = microtime(true);
sitzung_starten('app');

/** Das Muster der Nachweisdatei. */
const NOTWEG_MUSTER = 'zweitfaktor-notweg-';

/** Die eine Antwort auf jede Lage, in der die Tuer zu bleibt. */
const NOTWEG_NICHT_BEREIT = 'Der Notzugang steht für dieses Konto nicht bereit.';

/* DER NAME HAENGT AN DER SITZUNG (E-SR-82). Gewuerfelt beim ersten Aufruf,
 * danach derselbe, bis die Sitzung endet oder der Notzugang gelang. */
if (!isset($_SESSION['notweg']['datei'])
    || !nachweis_kennung_gueltig((string)$_SESSION['notweg']['datei'])) {
    $_SESSION['notweg'] = ['datei' => nachweis_kennung_neu()];
}
$kennung = (string)$_SESSION['notweg']['datei'];
$dateiname = NOTWEG_MUSTER . $kennung . '.txt';

/* DER WERT ENTSTEHT BEIM ERSTEN AUFRUF, wenn er fehlt (E-SR-24). Die
 * Migration von SR-02 legt ihn auf einer bestehenden Anlage an; auf einer
 * frischen steht er hier zum ersten Mal. Ein Fehler der Datenbank aendert
 * an der Seite nichts — sie sieht gleich aus, und der Notzugang bleibt zu,
 * weil der Wert dann nicht passt. */
try {
    if (app_state_lesen('notzugang_geheim') === null) {
        app_state_einmalig('notzugang_geheim', static fn(): string => bin2hex(random_bytes(32)));
    }
} catch (Throwable $ex) {
    system_melden('notweg', 'der Wert des Notzugangs ließ sich nicht anlegen', $ex);
}

$fehler = null;

if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST') {
    /* NUR ZEICHENKETTEN. `email[]=…` liefe sonst in einen Typfehler, `wert[]=…`
     * in eine Warnung im Reiter System — beides auf einer Seite, die jeder
     * aufrufen kann. Ein Feld, das keine Zeichenkette ist, ist leer. */
    $feld = static fn(string $k): string => is_string($_POST[$k] ?? null) ? $_POST[$k] : '';
    $email = email_normalisieren($feld('email'));
    $merkmale = [rate_merkmal_kennung($email)];

    if (!csrf_ok()) {
        /* Wie in `login.php`: Ein abgelaufenes Formular zaehlt nicht als
         * Versuch — es ist meistens eine Sitzung, die inzwischen abgelaufen
         * ist. Dann gilt auch ein neuer Dateiname (E-SR-82). */
        $fehler = 'Das Formular ist abgelaufen. Bitte die Seite neu laden — '
                . 'und den Namen der Datei mit dem vergleichen, der dann oben steht.';
    } elseif (!rate_erlaubt('notweg', null, $merkmale)) {
        /* DIE SPERRE HAENGT AN DER EINGETIPPTEN ADRESSE, nicht an einem
         * Konto — sie erscheint fuer erfundene Adressen genauso und gibt
         * deshalb keine Auskunft. */
        $sperre = rate_sperre('notweg', null, $merkmale);
        $bis = $sperre['bis'] ?? null;
        $fehler = 'Zu viele Versuche für diese Adresse.'
                . ($bis !== null ? ' Wieder ab ' . fmt_local($bis, 'H:i') . ' Uhr.'
                                 : ' Bitte später erneut versuchen.');
        rate_gleiche_dauer($t0);
    } else {
        $u = notweg_konto($email);
        $passwortPasst = notweg_passwort_passt($u, $feld('tokens'));
        $wertPasst     = notweg_wert_passt($feld('wert'));
        $dateiLiegt    = nachweis_steht(NOTWEG_MUSTER, $kennung);
        $tuerOffen     = notweg_tuer_offen($u);

        if ($u !== null && $passwortPasst && $wertPasst && $dateiLiegt && $tuerOffen
            && notweg_zuruecksetzen((int)$u['id'])) {
            /* NACH DEM COMMIT: Datei(en) weg, Topf leer, Mail, Sitzungsteil
             * weg. Die Dateien des Musters gehen ALLE — auch die aus
             * abgebrochenen Versuchen; sie galten ohnehin nicht mehr. */
            nachweis_entfernen(NOTWEG_MUSTER);
            rate_erfolg('notweg', null, $merkmale);
            require_once __DIR__ . '/mail_lib.php';
            mail_einreihen('totp_zurueckgesetzt', (string)$u['email'],
                           ['link' => app_url('/login.php'), 'weg' => 'notweg']);
            unset($_SESSION['notweg']);
            header('Location: login.php?ende=notweg', true, 303);
            exit;
        }

        rate_misserfolg('notweg', null, $merkmale);
        $fehler = NOTWEG_NICHT_BEREIT;
        rate_gleiche_dauer($t0);
    }
}

/**
 * Das Konto zur Adresse — oder `null`. Ein Fehler der Datenbank ist hier
 * dasselbe wie „kein Konto": Die Tuer bleibt zu, und die Antwort ist die
 * eine (die Ursache geht in den Reiter System).
 */
function notweg_konto(string $email): ?array
{
    if ($email === '') { return null; }
    try {
        $st = db()->prepare('SELECT id, email, password_hash, kdf_iter, role, status
                               FROM users WHERE email = ?');
        $st->execute([$email]);
        $u = $st->fetch(PDO::FETCH_ASSOC);
        return $u === false ? null : $u;
    } catch (Throwable $ex) {
        system_melden('notweg', 'das Konto ließ sich nicht lesen', $ex);
        return null;
    }
}

/**
 * Stimmt das Passwort? DASSELBE VERFAHREN WIE `login.php` (E-SR-84): Der
 * Browser schickt je Rundenzahl ein Token, der Server nimmt das zur
 * `kdf_iter` des Kontos passende. Ohne Konto oder ohne Passwort trotzdem
 * eine bcrypt-Rechnung gegen `AUTH_VERGLEICHSWERT` — sonst waere dieser
 * Zweig schneller, und die Dauer verriete, ob es die Adresse gibt.
 */
function notweg_passwort_passt(?array $u, string $tokensRoh): bool
{
    $tokenNach = [];
    $roh = json_decode($tokensRoh, true);
    if (is_array($roh)) {
        foreach ($roh as $runde => $tk) {
            $r = (int)$runde;
            if ($r > 0 && is_string($tk) && preg_match('/^[0-9a-f]{64}$/', $tk)) {
                $tokenNach[$r] = $tk;
            }
        }
    }
    if ($u !== null && $u['password_hash'] !== null) {
        $token = $tokenNach[(int)($u['kdf_iter'] ?? 0) ?: 310000] ?? '';
        return $token !== '' && password_verify($token, (string)$u['password_hash']);
    }
    /* Derselbe Blindvergleich wie in `login.php` (Backlog Nr. 93): passt die
     * Konstante zur Vorgabe, genuegt die Pruefung; sonst kostet ein Hash mit
     * der Vorgabe genauso viel. Nicht beides. */
    if (password_needs_rehash(AUTH_VERGLEICHSWERT, PASSWORD_DEFAULT)) {
        password_hash(reset($tokenNach) ?: '', PASSWORD_DEFAULT);
    } else {
        password_verify(reset($tokenNach) ?: '', AUTH_VERGLEICHSWERT);
    }
    return false;
}

/**
 * Stimmt der Wert? In Vierergruppen abtippbar wie die Schluessel —
 * Leerzeichen und Bindestriche zaehlen nicht. Verglichen mit `hash_equals()`;
 * fehlt der Wert in der Datenbank, wird gegen einen frischen Zufall
 * verglichen, damit auch dieser Zweig dieselbe Arbeit tut.
 */
function notweg_wert_passt(string $eingabe): bool
{
    try {
        $soll = app_state_lesen('notzugang_geheim');
    } catch (Throwable $ex) {
        $soll = null;
    }
    $ist = schluessel_eingabe_normalisieren($eingabe) ?? '';
    $gueltig = $soll !== null && preg_match('/^[0-9a-f]{64}$/', $soll) === 1;
    $vergleich = hash_equals($gueltig ? $soll : bin2hex(random_bytes(32)), $ist);
    return $gueltig && $vergleich;
}

/**
 * Steht die Tuer fuer dieses Konto offen? (E-SR-13, E-SR-85.) Fragt IMMER
 * beide Zahlen ab, auch ohne Konto — dieselbe Arbeit in jeder Lage.
 */
function notweg_tuer_offen(?array $u): bool
{
    try {
        $einzige = betreiberinnen_zahl(db()) === 1;
        $an = totp_an($u !== null ? (int)$u['id'] : 0);
    } catch (Throwable $ex) {
        system_melden('notweg', 'die Lage des Kontos ließ sich nicht lesen', $ex);
        return false;
    }
    return $u !== null
        && rolle_ist_betreiberin($u['role'] ?? null)
        && ($u['status'] ?? '') === 'aktiv'
        && $einzige && $an;
}

/**
 * Zuruecksetzen — in EINER Transaktion: der Wert neu, der Zweitfaktor aus
 * (`totp_abschalten()` haengt sich an, samt Geraeten, Passkeys und
 * Protokoll). Alles oder nichts: Nach einem gelungenen Notzugang steht in
 * `app_state` ein anderer Wert, und ein gescheiterter verbraucht ihn nicht.
 */
function notweg_zuruecksetzen(int $userId): bool
{
    try {
        db_transaktion(db(), static function () use ($userId): void {
            if (!app_state_setzen('notzugang_geheim', bin2hex(random_bytes(32)))) {
                throw new RuntimeException('der Wert ließ sich nicht neu setzen');
            }
            if (!totp_abschalten($userId, 'notweg')) {
                throw new RuntimeException('der Zweitfaktor ließ sich nicht abschalten');
            }
        });
        return true;
    } catch (Throwable $ex) {
        system_melden('notweg', 'der Notzugang ließ sich nicht abschließen', $ex);
        return false;
    }
}

require_once __DIR__ . '/ui.php';
ui_seite_start(['titel' => 'Notzugang zum Zweitfaktor']);
ui_kopf(['menue' => false]);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
  <?php ui_hinweise(); ?>

  <?php ui_titelzeile([
      'titel' => 'Notzugang zum Zweitfaktor',
      'unter' => 'Für die <strong>einzige</strong> BetreiberIn, die Zweitgerät, '
               . 'Wiederherstellungscodes und Notfallblatt verloren hat. Die Reihenfolge '
               . 'steht im <a href="hilfe.php#wenn-die-einzige-betreiberin-den-zweitfaktor-verliert-seit-web-21-13-0">Handbuch</a>.',
  ]); ?>

  <?php ui_meldung(null, $fehler); ?>

  <?php ui_karte_start(['titel' => 'Was es braucht']); ?>
    <p class="feld-hinweis"><strong>1. Eine Datei im Anwendungsverzeichnis</strong> —
    dort, wo <code>config.php</code> liegt — mit genau diesem Namen. Was darin
    steht, spielt keine Rolle; eine leere Datei genügt:</p>
    <p class="feld-hinweis"><code><?= ui_e($dateiname) ?></code></p>
    <p class="feld-hinweis">Der Name gehört zu diesem Browserfenster. Zeigt die Seite
    später einen anderen, die Datei umbenennen.</p>
    <p class="feld-hinweis"><strong>2. Den Wert aus der Datenbank</strong> — im
    Datenbankwerkzeug des Hosters mit<br>
    <code>SELECT v FROM app_state WHERE k = 'notzugang_geheim';</code><br>
    64 Zeichen; Leerzeichen und Bindestriche zählen nicht.</p>
    <p class="feld-hinweis"><strong>3. Adresse und Passwort</strong> des Kontos.</p>
    <p class="feld-hinweis">Danach ist der Zweitfaktor aus, die Datei und der Wert sind
    verbraucht, und eine Mail geht an die Adresse des Kontos. Du meldest dich mit
    dem Passwort an und richtest ihn sofort neu ein.</p>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Notzugang']); ?>
    <form method="post" autocomplete="off" id="notwegform">
      <?= csrf_field() ?>
      <input type="hidden" name="tokens" id="notwegtoks">
      <?php ui_feld(['name' => 'email', 'label' => 'E-Mail des Kontos', 'art' => 'email',
                     'pflicht' => true, 'attr' => ' autocomplete="username"']); ?>
      <?php ui_feld(['name' => 'password', 'label' => 'Passwort', 'art' => 'password',
                     'pflicht' => true, 'attr' => ' autocomplete="current-password"']); ?>
      <?php ui_feld(['name' => 'wert', 'label' => 'Wert aus der Datenbank',
                     'pflicht' => true,
                     'attr' => ' autocomplete="off" spellcheck="false" inputmode="text"']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Zweitfaktor zurücksetzen', 'art' => 'primaer']) ?>
      </div>
    </form>
    <p class="zustandszeile" id="notwegstate"></p>
  <?php ui_karte_ende(); ?>
  </main>
</div>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<?php /* DASSELBE VERFAHREN WIE DIE ANMELDUNG (E-SR-84). Das Passwort
         verlaesst den Browser nicht: Salz holen, je Rundenzahl das Token
         ableiten, nur die Token schicken. Anders als die Anmeldung legt diese
         Seite KEINE Ableitung ins Vormerkfach — sie meldet niemanden an.
         Die zweite Kopie dieser Zeilen ist Backlog Nr. 358: `login.php` ist
         fuer Schritt 18 zu (E-SR-14). */ ?>
<script<?= kopf_nonce_attr() ?>>
document.getElementById('notwegform').addEventListener('submit', async ev => {
  const f = ev.target;
  if (f.dataset.ready === '1') return;
  ev.preventDefault();
  const state = document.getElementById('notwegstate');
  try {
    state.textContent = 'Schlüssel wird abgeleitet …';
    const email = f.elements['email'].value.trim().toLowerCase();
    const pw = f.elements['password'].value;
    const r = await fetch('auth_salt.php', {
      method: 'POST', headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ email })
    });
    if (r.status === 429) {
      const d429 = await r.json().catch(() => ({}));
      state.textContent = d429.meldung || 'Zu viele Versuche. Bitte später erneut.';
      return;
    }
    if (!r.ok) {
      state.textContent = 'Der Notzugang ist gerade nicht erreichbar. Bitte später erneut.';
      return;
    }
    const d = await r.json();
    const runden = Array.isArray(d.iter) && d.iter.length ? d.iter : [310000];
    const tokens = {};
    for (const it of runden) {
      tokens[it] = (await EdCrypto.deriveKeys(pw, d.salt, it)).authToken;
    }
    document.getElementById('notwegtoks').value = JSON.stringify(tokens);
    f.elements['password'].value = '';
    f.dataset.ready = '1';
    state.textContent = '';
    f.submit();
  } catch (e) {
    state.textContent = 'Dieser Browser unterstützt die nötige Verschlüsselung nicht.';
  }
});
</script>
<?php ui_fuss_seite(); ?>
<?php ui_seite_ende(); ?>
