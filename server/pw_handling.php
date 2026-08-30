<?php
declare(strict_types=1);
require_once __DIR__ . '/db.php';
/* Fuer logo_src(): Die Seite hat keine Sitzung und zeigt den Standard der
 * Installation (E-P3-20). Ohne session_lib.php faende logo_src() logo_stamm()
 * nicht und fiele auf den Hubschrauber zurueck — F-P3-AN. */
require_once __DIR__ . '/session_lib.php';
require_once __DIR__ . '/validate_lib.php';   // WRAP_RE, Formatkennung

/**
 * Passwort setzen — die einzige Stelle, an der ein Passwort ueber einen
 * Einmal-Link vergeben wird. Loest reset_confirm.php und einrichtung.php ab.
 *
 * Zwei Betriebsarten, die der Server allein aus dem Kontostand bestimmt
 * (niemals aus dem, was der Browser mitschickt):
 *
 *  - ERSTVERGABE  (pat_wrap_rc IS NULL): Das Konto hat noch keinen
 *    Inhaltsschluessel. Der Browser erzeugt ihn zusammen mit dem
 *    Wiederherstellungsschluessel, zeigt letzteren EINMALIG an und laesst ihn
 *    bestaetigen. Erst danach wandern Passwort-Hash, Salt und beide Huellen
 *    gemeinsam in die Datenbank.
 *
 *  - RESET (pat_wrap_rc vorhanden): Ein neues Passwort macht die
 *    Passwort-Huelle des Inhaltsschluessels wertlos. Der
 *    Wiederherstellungsschluessel ist deshalb zwingend — der Browser entpackt
 *    damit den Inhaltsschluessel und verpackt ihn fuer das neue Passwort neu.
 *
 * Der Server sieht in beiden Faellen nur Chiffretext: nie das Passwort, nie
 * den Wiederherstellungsschluessel, nie den Inhaltsschluessel. Geschrieben
 * wird ausschliesslich in einer Transaktion — sonst entstuende ein Konto, das
 * sich zwar anmelden laesst, dessen Daten aber unlesbar waeren.
 */

/* WRAP_RE steht seit Web 5.1.0 in validate_lib.php — eine Fassung fuer die
 * drei Stellen, die eine Schluesselhuelle pruefen (M2-10). */

/* ---- Der Token gehoert nicht in die Adresszeile (M1-06) --------------------
 *
 * WAS DARAN FALSCH WAR
 * Der Token stand als Parameter in der Adresse. Damit landete er im Verlauf
 * des Browsers, im Zugriffsprotokoll des Webservers, in jedem Screenshot der
 * Seite — und ueber die Herkunftsadresse potenziell bei jeder Gegenstelle, die
 * von dieser Seite aus angefragt wird. Wer ihn hat, kann das Passwort setzen.
 *
 * STUFE 1 — Herkunftsadresse unterbinden.
 * Referrer-Policy: no-referrer nimmt die Vervielfachung durch Unterabrufe
 * heraus. Cache-Control: no-store haelt die Seite aus dem Zwischenspeicher.
 *
 * STUFE 2 — den Token gegen einen sitzungsgebundenen Wert tauschen.
 * Beim ersten Aufruf wandert er in eine Sitzung und die Seite leitet auf sich
 * selbst weiter, ohne Parameter. Ab da steht er in keiner Adresszeile mehr.
 *
 * WARUM EIN EIGENER SITZUNGSNAME UND SameSite=Lax
 * Der Klick kommt aus dem Mailprogramm, also von einer FREMDEN Seite. Ein
 * Cookie mit SameSite=Strict kaeme bei der Weiterleitung nicht zurueck — die
 * Seite waere danach eine Sackgasse. Lax wird bei Seitenaufrufen dieser Art
 * gesendet und haelt zugleich fremde POST-Anfragen ab.
 *
 * Der EIGENE Name (nicht der der Anwendung) ist dabei nicht Kosmetik: Wuerde
 * hier der Sitzungscookie der Anwendung mit Lax neu gesetzt, verloere eine
 * parallel offene, angemeldete Sitzung im selben Browser ihren Strict-Schutz.
 * Zwei Namen, zwei Sitzungen, keine Wechselwirkung.
 */
const PW_SESSION_NAME = 'EDPWSESS';

header('Referrer-Policy: no-referrer');
header('Cache-Control: no-store, no-cache, must-revalidate');

function pw_session_start(): void {
    if (session_status() !== PHP_SESSION_NONE) { return; }
    session_name(PW_SESSION_NAME);
    session_set_cookie_params([
        'httponly' => true, 'secure' => true, 'samesite' => 'Lax', 'path' => '/',
    ]);
    session_start();
}

$tokenAusAdresse = (string)($_GET['token'] ?? '');
$getauscht       = isset($_GET['w']);        // "weitergeleitet", zweiter Aufruf

if ($tokenAusAdresse !== '') {
    // Erster Aufruf: Token einlagern und ohne Parameter neu aufrufen. Ob er
    // gueltig ist, wird danach geprueft — die Weiterleitung erfolgt in jedem
    // Fall, sonst waere schon die Adresszeile die Auskunft, ob ein Token zieht.
    pw_session_start();
    session_regenerate_id(true);
    $_SESSION['pw_token'] = $tokenAusAdresse;
    header('Location: pw_handling.php?w=1', true, 302);
    exit;
}

pw_session_start();
$token = (string)($_SESSION['pw_token'] ?? '');

/* Kein Token in der Sitzung, obwohl die Weiterleitung gelaufen ist: Der
 * Browser hat den Cookie nicht angenommen. Das ist die einzige Sackgasse
 * dieses Weges — sie wird benannt, statt als "Link ungueltig" zu erscheinen
 * und die Person einen zweiten, ebenso wirkungslosen Link anfordern zu lassen. */
$keinCookie = ($token === '' && $getauscht);

$row = null;
if (preg_match('/^[a-f0-9]{64}$/', $token)) {
    $st = db()->prepare('SELECT r.id, r.user_id, u.pat_key_check, u.pat_wrap_rc
                         FROM password_resets r
                         JOIN users u ON u.id = r.user_id
                         WHERE r.token_hash = ? AND r.used_at IS NULL AND r.expires_at > NOW()');
    $st->execute([hash('sha256', $token)]);
    $row = $st->fetch();
}

// Betriebsart: ohne Wiederherstellungs-Huelle gibt es noch keinen
// Inhaltsschluessel — dann ist dies die Erstvergabe.
$erstvergabe = $row !== null && $row['pat_wrap_rc'] === null;

$error = null; $done = false;
if ($row && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $neuTok  = (string)($_POST['new_token'] ?? '');
    $neuSalt = (string)($_POST['new_salt'] ?? '');
    $wrapPw  = (string)($_POST['wrap_pw'] ?? '');
    $wrapRc  = (string)($_POST['wrap_rc'] ?? '');
    $keyChk  = (string)($_POST['key_check'] ?? '');
    $neuIter = (int)($_POST['new_iter'] ?? 0);
    $chkSoll = $row['pat_key_check'] ?? null;

    if (!preg_match('/^[0-9a-f]{64}$/', $neuTok) || !preg_match('/^[0-9a-f]{32}$/', $neuSalt)) {
        // Kommt nur vor, wenn JavaScript fehlt oder abbricht.
        $error = 'Speichern unvollständig — bitte JavaScript aktivieren und erneut versuchen.';
    } elseif ($neuIter !== KDF_ITER_ZIEL) {
        // Nur der Zielwert (M2-01): Diese Seite baut die Ableitung immer neu
        // auf und hat keinen Grund, auf einem Altwert zu landen.
        $error = 'Die Rundenzahl der Schlüsselableitung ist unbrauchbar. '
               . 'Es wurde nichts geändert.';
    } elseif (!preg_match(WRAP_RE, $wrapPw)) {
        $error = $erstvergabe
            ? 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.'
            : 'Der Wiederherstellungsschlüssel passt nicht. Es wurde nichts geändert.';
    } elseif ($erstvergabe && !preg_match(WRAP_RE, $wrapRc)) {
        $error = 'Die Schlüssel konnten nicht erzeugt werden. Es wurde nichts geändert.';
    } elseif ($keyChk !== '' && !preg_match('/^[0-9a-f]{32}$/', $keyChk)) {
        $error = 'Die Prüfsumme des Inhaltsschlüssels ist unbrauchbar. Es wurde nichts geändert.';
    } elseif (!$erstvergabe && $chkSoll !== null && $keyChk !== $chkSoll) {
        /* Beim Zuruecksetzen wird der Inhaltsschluessel aus der
         * Wiederherstellungs-Huelle entpackt und in eine neue Passwort-Huelle
         * gepackt. Der Server kann keine der beiden oeffnen. Passt die
         * Pruefsumme nicht, steckt ein ANDERER Schluessel in der neuen Huelle
         * — danach waere jeder vorhandene Datensatz endgueltig unlesbar.
         * Bestandskonten ohne gespeicherte Pruefsumme werden angenommen und
         * bekommen sie unten; der Server kann sie nicht selbst berechnen. */
        $error = 'Der Inhaltsschlüssel gehört nicht zu diesem Konto. '
               . 'Es wurde nichts geändert.';
    } else {
        $pdo = db();
        $pdo->beginTransaction();
        try {
            if ($erstvergabe) {
                // Passwort und BEIDE Huellen in einem Zug — ein Konto ohne
                // Wiederherstellungs-Huelle waere nach einem Reset verloren.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                kdf_iter = ?,
                                                pat_wrap_pw = ?, pat_wrap_rc = ?,
                                                pat_key_check = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $neuIter, $wrapPw, $wrapRc,
                               $keyChk !== '' ? $keyChk : null,
                               (int)$row['user_id']]);
            } else {
                // Reset: der Inhaltsschluessel bleibt derselbe, nur seine
                // Passwort-Huelle wird ersetzt. pat_wrap_rc bleibt unberuehrt,
                // damit der bekannte Wiederherstellungsschluessel gueltig bleibt.
                $pdo->prepare('UPDATE users SET password_hash = ?, kdf_salt = ?,
                                                kdf_iter = ?,
                                                pat_wrap_pw = ?, pat_key_check = ?
                               WHERE id = ?')
                    ->execute([password_hash($neuTok, PASSWORD_DEFAULT), $neuSalt,
                               $neuIter, $wrapPw,
                               $keyChk !== '' ? $keyChk : null,
                               (int)$row['user_id']]);
            }
            /* ALLE offenen Tokens dieses Kontos entwerten, nicht nur den
             * benutzten (M1-09). reset_request.php laesst seit Web 4.4.0 zwar
             * nur noch einen gueltigen zu — aber der Einladungslink aus der
             * Nutzerverwaltung ist 24 Stunden gueltig und entsteht auf einem
             * anderen Weg. Wer sein Passwort setzt, soll danach keinen
             * fremden Weg mehr offen haben. */
            $pdo->prepare('UPDATE password_resets SET used_at = NOW()
                           WHERE user_id = ? AND used_at IS NULL')
                ->execute([(int)$row['user_id']]);
            /* Sitzungszaehler erhoehen (M1-09/D6): beendet jede offene Sitzung
             * dieses Kontos. Hier ist keine davon die eigene — wer diese Seite
             * benutzt, ist nicht angemeldet. Genau der Fall, um dessentwillen
             * der Zaehler existiert: Passwort zuruecksetzen, weil jemand
             * anders drin ist. */
            $pdo->prepare('UPDATE users SET session_epoch = session_epoch + 1 WHERE id = ?')
                ->execute([(int)$row['user_id']]);
            $pdo->commit();
            $done = true;
            // Der Token hat seinen Zweck erfuellt — die Sitzung dieser Seite
            // wird nicht laenger gebraucht (M1-06).
            unset($_SESSION['pw_token']);
        } catch (Throwable $ex) {
            $pdo->rollBack();
            $error = 'Speichern fehlgeschlagen. Bitte erneut versuchen.';
        }
    }
}
require_once __DIR__ . '/ui.php';   // Seitenhuelle; laedt selbst nichts nach
ui_seite_start([
    'titel'  => $erstvergabe ? 'Passwort festlegen' : 'Neues Passwort',
    'klasse' => 'anmeldung-body',
]);
?>
<?php /* HIER STAND EIN div mit der Klasse `login-wrap` — ohne schliessendes Tag und
         ohne Regel im neuen Stylesheet. Entfernt (O10): Erst dadurch ist
         `<main class="anmeldung">` direktes Flex-Kind von `.anmeldung-body`,
         und `flex:1 1 auto` greift — die Fusszeile sitzt jetzt unten am Rand
         statt dicht unter der Karte. Sichtbare Aenderung, beabsichtigt. */ ?>
<main class="anmeldung">
 <?php /* DIESELBE BREITE WIE DIE ANMELDUNG (O10). Die Karte war 760 px
          breit (`.anmeldung-breit`), die Anmeldung daneben 400 — zwei
          Seiten derselben Familie, die man unmittelbar nacheinander
          sieht, sprangen dabei in der Breite. Der lange Erklaertext der
          Erstvergabe hat die breite Karte einmal gerechtfertigt; er ist
          aber Fliesstext, und bei 400 px liest er sich wie auf dem
          Handy — was er dort ohnehin tut. */ ?>
 <div class="anmeldung-karte">
  <img src="<?= e(logo_src()) ?>" alt="Einsatzdoku" class="anmeldung-logo">

  <?php if ($done): ?>
    <h1 class="anmeldung-titel">Fertig</h1>
    <?php if ($erstvergabe): ?>
      <?= ui_meldung_markup('ok',
          'Passwort gespeichert und die Verschlüsselung eingerichtet. Du kannst '
        . 'dich jetzt anmelden.') ?>
      <p class="feld-hinweis">Bewahre den Wiederherstellungsschlüssel sicher auf —
         nach einem Passwort-Reset ist er der einzige Weg zu deinen Daten.</p>
    <?php else: ?>
      <?= ui_meldung_markup('ok',
          'Passwort gespeichert und die verschlüsselten Angaben übernommen. Du '
        . 'kannst dich jetzt anmelden.') ?>
    <?php endif; ?>
    <p class="anmeldung-neben"><a href="login.php">Zur Anmeldung</a></p>

  <?php elseif ($keinCookie): ?>
    <h1 class="anmeldung-titel">Cookie nötig</h1>
    <?= ui_meldung_markup('fehler',
        'Dieser Browser hat den nötigen Cookie nicht angenommen. Der Link '
      . 'lässt sich deshalb nicht öffnen.') ?>
    <div class="text">
      <p>Der Link aus der E-Mail wird beim ersten Öffnen aus der Adresszeile
         genommen und in einem Cookie abgelegt — er soll weder im Verlauf noch
         in Protokollen stehen bleiben. Ohne Cookies geht dieser Weg nicht; die
         Anmeldung selbst braucht ebenfalls einen.</p>
      <p>Bitte Cookies für diese Seite erlauben (auch ein privates Fenster mit
         strengen Einstellungen kann die Ursache sein) und den Link aus der
         E-Mail erneut öffnen.</p>
    </div>
    <p class="anmeldung-neben"><a href="reset_request.php">Neuen Link anfordern</a></p>

  <?php elseif (!$row): ?>
    <h1 class="anmeldung-titel">Link ungültig</h1>
    <?= ui_meldung_markup('fehler', 'Dieser Link ist ungültig oder abgelaufen.') ?>
    <p class="anmeldung-neben"><a href="reset_request.php">Neuen Link anfordern</a></p>

  <?php elseif ($erstvergabe): ?>
    <h1 class="anmeldung-titel">Passwort festlegen</h1>
    <?php ui_meldung(null, $error); ?>
    <?php /* IN `.text`, weil `p{margin:0}` gilt (Grundregel, Abschnitt 3):
             Ohne die Hülle kleben die drei Absätze aneinander und lesen sich
             wie einer. `.text` bringt Absatzabstand, Lesegröße und Zeilenhöhe
             — die Lesespalte darin greift nicht, die Karte ist schmaler. */ ?>
    <div class="text">
      <p>Diagnose, Alter und Einsatzort werden <strong>Ende-zu-Ende-verschlüsselt</strong>
         gespeichert. Der Schlüssel entsteht aus deinem Passwort und verlässt deinen
         Browser nie — der Server kann die Angaben nicht lesen.</p>
      <p><strong>Deshalb ist die Stärke deines Passworts unmittelbar die Stärke der
         Verschlüsselung.</strong> Weil der Server das Passwort nie sieht, kann er
         seine Güte auch nicht prüfen und ein schwaches nicht ausgleichen — es gibt
         keine zweite Hürde dahinter. Wähle etwas Langes, das du dir merken kannst;
         vier zufällige Wörter sind besser als acht verdrehte Zeichen.</p>
      <p><strong>Wichtig:</strong> Nach dem Festlegen wird einmalig dein persönlicher
         <strong>Wiederherstellungsschlüssel</strong> angezeigt. Er ist nach einem
         Passwort-Reset der einzige Weg zu deinen Daten — ausdrucken oder sicher ablegen.</p>
    </div>

    <?php /* DER CODEBLOCK-BAUSTEIN statt dreier eigener Klassen (O10).
             `.codeblock` traegt Festbreitenschrift, Groesse und Sperrung schon
             — er ist genau dafuer da: Werte, die von einem Bildschirm
             abgeschrieben werden (Kopplungscode, Geraete-ID, jetzt auch der
             Wiederherstellungsschluessel). Das Inline-`style` fuer die
             Schriftgroesse entfaellt damit ebenfalls. */ ?>
    <div id="rcbox" class="codeblock" hidden>
      <p class="codeblock-titel">Dein Wiederherstellungsschlüssel</p>
      <p class="codeblock-wert" id="rccode"></p>
      <label><input type="checkbox" id="rcok">
        Ich habe den Schlüssel sicher notiert.</label>
    </div>

    <form method="post" id="pwform">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <?php /* Rundenzahl der Ableitung (M2-01). Sie muss mitkommen, sonst
               stuende in der Nutzerzeile eine andere als die, mit der das
               Token entstanden ist — und die naechste Anmeldung scheiterte. */ ?>
      <input type="hidden" name="new_iter"  id="new_iter">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
      <input type="hidden" name="wrap_rc"   id="wrap_rc">
      <input type="hidden" name="key_check" id="key_check">
      <?php ui_feld(['id' => 'pw1', 'label' => 'Passwort', 'art' => 'password',
                     'pflicht' => true, 'klein' => 'Mindestens 10 Zeichen.',
                     'attr' => ' minlength="10" autocomplete="new-password"']); ?>
      <span class="pwstaerke" id="pwq"></span>
      <?php ui_feld(['id' => 'pw2', 'label' => 'Wiederholen', 'art' => 'password',
                     'pflicht' => true,
                     'attr' => ' minlength="10" autocomplete="new-password"']); ?>
      <?= ui_knopf(['text' => 'Passwort festlegen', 'art' => 'primaer',
                    'breit' => true, 'attr' => ' id="gobtn"']) ?>
      <p class="zustandszeile" id="state"></p>
    </form>

  <?php else: ?>
    <h1 class="anmeldung-titel">Neues Passwort</h1>
    <?php ui_meldung(null, $error); ?>
    <p class="anmeldung-unter">Deine Einsatzdaten sind mit deinem Passwort verschlüsselt.
       Damit sie lesbar bleiben, brauchst du hier den
       <strong>Wiederherstellungsschlüssel</strong> aus der Einrichtung.</p>
    <form method="post" id="pwform">
      <input type="hidden" name="new_token" id="new_token">
      <input type="hidden" name="new_salt"  id="new_salt">
      <?php /* Rundenzahl der Ableitung (M2-01). Sie muss mitkommen, sonst
               stuende in der Nutzerzeile eine andere als die, mit der das
               Token entstanden ist — und die naechste Anmeldung scheiterte. */ ?>
      <input type="hidden" name="new_iter"  id="new_iter">
      <input type="hidden" name="wrap_pw"   id="wrap_pw">
      <input type="hidden" name="key_check" id="key_check">
      <?php ui_feld(['id' => 'rc', 'label' => 'Wiederherstellungsschlüssel',
                     'pflicht' => true, 'platzhalter' => 'ABCD-EFGH-JKMN-PQRS-TVWX',
                     'attr' => ' autocomplete="off" autocapitalize="characters"']); ?>
      <?php /* Sofortmeldung zur Eingabe (M2-06). Sie steht direkt am Feld,
               weil sie waehrend des Abtippens hilft — nicht erst, wenn alles
               eingegeben ist und der Knopf gedrueckt wurde. */ ?>
      <p class="zustandszeile" id="rcstate"></p>
      <?php ui_feld(['id' => 'pw1', 'label' => 'Neues Passwort', 'art' => 'password',
                     'pflicht' => true, 'klein' => 'Mindestens 10 Zeichen.',
                     'attr' => ' minlength="10" autocomplete="new-password"']); ?>
      <span class="pwstaerke" id="pwq"></span>
      <?php ui_feld(['id' => 'pw2', 'label' => 'Wiederholen', 'art' => 'password',
                     'pflicht' => true,
                     'attr' => ' minlength="10" autocomplete="new-password"']); ?>
      <?= ui_knopf(['text' => 'Passwort speichern', 'art' => 'primaer', 'breit' => true]) ?>
      <p class="zustandszeile" id="state"></p>
    </form>
  <?php endif; ?>
 </div>
</main>
<?php ui_fuss_seite(['dunkel' => true]); ?>

<?php if ($row && !$done): ?>
<script src="<?= asset('assets/crypto.js') ?>"></script>
<?php /* Passwortguete (Baustein B9). Er lag seit Web 4.0.0 fertig da und war
         an keiner Stelle eingebunden — die Mindestlaenge stand hier als
         HTML-Attribut und im Skript, die Staerkeanzeige gab es nicht (M2-02). */ ?>
<script src="<?= asset('assets/pwquality.js') ?>"></script>
<script>
const ERSTVERGABE = <?= $erstvergabe ? 'true' : 'false' ?>;
const WRAP_RC = <?= json_encode($erstvergabe ? null : $row['pat_wrap_rc']) ?>;
// Zielwert der Rundenzahl (M2-01). Diese Seite baut die Ableitung immer neu
// auf und nimmt deshalb nie einen Altwert.
const KDF_ITER_ZIEL = <?= json_encode(KDF_ITER_ZIEL) ?>;
const state = document.getElementById('state');
const form  = document.getElementById('pwform');

// Reste einer frueheren Sitzung in diesem Browser entfernen: Ein alter
// Inhaltsschluessel im sessionStorage wuerde nach der Anmeldung faelschlich
// wiederverwendet und die Entschluesselung scheitern lassen.
EdCrypto.clearSession();

/* Passwortguete: Anzeige waehrend der Eingabe, Pruefung beim Absenden (M2-02).
 *
 * Die Laengenpruefung stand bisher zweimal da — als HTML-Attribut minlength
 * und als Vergleich im Skript. Das Attribut haelt niemanden auf, der die
 * Entwicklerwerkzeuge oeffnet, und der Vergleich sagte nur "zu kurz". Beides
 * ersetzt jetzt EdPwQuality: dieselbe Regel wie an jeder anderen Stelle, mit
 * einer Begruendung statt einer Zahl.
 *
 * Die Anzeige haengt an pw1; die Wiederholung braucht keine — sie soll gleich
 * sein, nicht gut. */
EdPwQuality.beobachte(document.getElementById('pw1'),
                      document.getElementById('pwq'));

/* ---- Erstvergabe: Schluessel erzeugen, anzeigen, bestaetigen ---------- */
if (ERSTVERGABE) {
  let erzeugt = false;
  form.addEventListener('submit', async ev => {
    if (form.dataset.ready === '1') return;      // zweiter Durchlauf: senden
    ev.preventDefault();

    // Zweiter Klick: nur noch den Haken pruefen und absenden.
    if (erzeugt) {
      if (!document.getElementById('rcok').checked) {
        state.textContent = 'Bitte bestätigen, dass der Schlüssel notiert ist.';
        return;
      }
      form.dataset.ready = '1';
      form.submit();
      return;
    }

    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    const guete = EdPwQuality.pruefe(pw1);
    if (!guete.erlaubt) { state.textContent = guete.meldung; return; }
    if (pw1 !== pw2)    { state.textContent = 'Die Passwörter stimmen nicht überein.'; return; }

    try {
      state.textContent = 'Schlüssel werden erzeugt …';
      const salt = EdCrypto.randomHex(16);
      // Neues Konto: immer der Zielwert (M2-01). Es gibt keinen Altbestand,
      // der beruecksichtigt werden muesste.
      const k    = await EdCrypto.deriveKeys(pw1, salt, KDF_ITER_ZIEL);
      const ck   = EdCrypto.randomHex(32);          // Inhaltsschluessel
      const rc   = EdCrypto.newRecoveryCode();
      const rk   = await EdCrypto.recoveryKeyHex(rc);

      document.getElementById('new_salt').value  = salt;
      document.getElementById('new_iter').value  = KDF_ITER_ZIEL;
      document.getElementById('new_token').value = k.authToken;
      document.getElementById('wrap_pw').value   = await EdCrypto.encrypt(k.dataKeyHex, ck);
      document.getElementById('wrap_rc').value   = await EdCrypto.encrypt(rk, ck);
      // Pruefsumme des Inhaltsschluessels: Sie wird hier erstmals gesetzt und
      // ist ab jetzt der Massstab, an dem jedes spaetere Umpacken gemessen
      // wird. Ohne sie koennte ein Fehler beim Passwortwechsel eine Huelle
      // speichern, die einen ANDEREN Schluessel enthaelt — danach waere jeder
      // Datensatz endgueltig unlesbar.
      document.getElementById('key_check').value = await EdCrypto.contentKeyCheck(ck);

      // Ab hier darf das Passwort nicht mehr geaendert werden — die Huelle
      // ist bereits an den daraus abgeleiteten Schluessel gebunden.
      document.getElementById('pw1').readOnly = true;
      document.getElementById('pw2').readOnly = true;

      document.getElementById('rccode').textContent = rc;
      document.getElementById('rcbox').hidden = false;
      /* AUF DAS <span> ZIELEN, nicht auf den Knopf (O10). ui_knopf() legt
         den Text in ein <span> und stellt ihm ggf. ein Symbol voran;
         `textContent` am Knopf selbst wuerde beides ersetzen — der Text
         staende dann da, das Symbol waere fort. */
      document.querySelector('#gobtn span').textContent = 'Speichern und abschließen';
      state.textContent = 'Schlüssel notieren, Haken setzen, dann abschließen.';
      erzeugt = true;
    } catch (e) {
      state.textContent = 'Fehlgeschlagen: ' + e.message;
    }
  });

/* ---- Reset: Inhaltsschluessel umpacken -------------------------------- */
} else {
  /* SOFORTPRUEFUNG DER EINGABE (M2-06).
   *
   * Sie meldet, WAS an der Eingabe nicht stimmt — ein Zeichen, das es im
   * Alphabet nicht gibt, oder eine unvollstaendige Laenge. Ohne sie lautet
   * die einzige Rueckmeldung „passt nicht", und die bekommt man erst nach
   * dem Absenden und ohne Unterscheidung zwischen Tippfehler und falschem
   * Zettel. Der Knopf bleibt trotzdem bedienbar: Die Meldung ist eine Hilfe,
   * keine Sperre. */
  const rcFeld  = document.getElementById('rc');
  const rcState = document.getElementById('rcstate');
  function rcPruefen() {
    const wert = rcFeld.value.trim();
    if (wert === '') { rcState.textContent = ''; return null; }
    const p = EdCrypto.pruefeRecoveryCode(wert);
    rcState.textContent = p.ok
      ? 'Schlüssel vollständig.'
      : EdCrypto.recoveryCodeMeldung(p);
    return p;
  }
  rcFeld.addEventListener('input', rcPruefen);
  rcFeld.addEventListener('blur', rcPruefen);

  form.addEventListener('submit', async ev => {
    if (form.dataset.ready === '1') return;
    ev.preventDefault();

    const pw1 = document.getElementById('pw1').value;
    const pw2 = document.getElementById('pw2').value;
    const rc  = rcFeld.value.trim();
    // Erst der Schluessel: Ein Tippfehler darin ist der wahrscheinlichste
    // Grund fuer ein Scheitern, und er ist behebbar, ohne etwas zu verlieren.
    const rcPruef = EdCrypto.pruefeRecoveryCode(rc);
    if (!rcPruef.ok) {
      rcState.textContent = EdCrypto.recoveryCodeMeldung(rcPruef);
      state.textContent = 'Der Wiederherstellungsschlüssel ist noch nicht vollständig.';
      rcFeld.focus();
      return;
    }
    const guete = EdPwQuality.pruefe(pw1);
    if (!guete.erlaubt) { state.textContent = guete.meldung; return; }
    if (pw1 !== pw2)    { state.textContent = 'Die Passwörter stimmen nicht überein.'; return; }

    try {
      state.textContent = 'Wiederherstellungsschlüssel wird geprüft …';
      const rk = await EdCrypto.recoveryKeyHex(rc);
      let ck;
      try {
        ck = await EdCrypto.decrypt(rk, WRAP_RC);       // Inhaltsschluessel entpacken
      } catch (e) {
        /* Ab hier ist der Schluessel FORMAL in Ordnung — Laenge und Alphabet
         * stimmen. Dass er trotzdem nicht passt, heisst also: falscher
         * Zettel oder falsches Konto, kein Tippfehler. Genau das sagt die
         * Meldung jetzt auch (M2-06). */
        state.textContent = 'Der Schlüssel ist formal korrekt, passt aber nicht zu '
                          + 'diesem Konto. Kein Tippfehler — vermutlich der Schlüssel '
                          + 'eines anderen Kontos oder aus einer früheren Einrichtung.';
        return;                                        // nichts absenden
      }

      state.textContent = 'Neues Passwort wird eingerichtet …';
      const salt = EdCrypto.randomHex(16);
      // Zuruecksetzen baut die Ableitung vollstaendig neu auf — also gleich
      // mit dem Zielwert (M2-01).
      const k    = await EdCrypto.deriveKeys(pw1, salt, KDF_ITER_ZIEL);

      document.getElementById('new_salt').value  = salt;
      document.getElementById('new_iter').value  = KDF_ITER_ZIEL;
      document.getElementById('new_token').value = k.authToken;
      document.getElementById('wrap_pw').value   = await EdCrypto.encrypt(k.dataKeyHex, ck);
      document.getElementById('key_check').value = await EdCrypto.contentKeyCheck(ck);

      form.dataset.ready = '1';
      form.submit();
    } catch (e) {
      state.textContent = 'Fehlgeschlagen: ' + e.message;
    }
  });
}
</script>
<?php endif; ?>
<?php ui_seite_ende(); ?>
