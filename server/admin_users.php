<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/smtp.php';
require_admin();
// Erinnerung an die Admin-Sicherungen (A8.4) — sie gehoert dorthin, wo
// Administration ohnehin hinsieht, nicht nur auf die Sicherungsseite selbst.
require_once __DIR__ . '/adminbackup_lib.php';

$notice = null; $error = null; $setzLink = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    /* ---- Nutzer anlegen (M1-10) ----------------------------------------
     *
     * DREI DINGE WAREN HIER FALSCH, UND ZWAR ALLE DREI AN DERSELBEN STELLE:
     *
     *  1. DUBLETTE. Bei vorhandener Adresse warf die Datenbank eine
     *     ungefangene Ausnahme — der Admin sah eine weisse Seite oder einen
     *     Servertfehler statt einer Auskunft. Dieselbe Situation ist in
     *     admin_user.php beim Aendern der Adresse laengst sauber behandelt.
     *
     *  2. HALBER ZUSTAND. Konto und Setz-Token entstanden in zwei getrennten
     *     Anweisungen. Scheiterte die zweite, blieb ein Konto ohne jeden Weg
     *     zu einem Passwort zurueck — anmelden unmoeglich, und "Passwort
     *     vergessen" haette es zwar geheilt, aber niemand wusste davon.
     *
     *  3. VERWORFENER RUECKGABEWERT. smtp_send() liefert true/false; hier
     *     wurde es ignoriert und in jedem Fall "Setz-Link per E-Mail
     *     verschickt" gemeldet. Bei einem Fehlschlag existierte das Konto,
     *     ein gueltiger Token lag in der Datenbank — nur hatte niemand den
     *     Link, und es sagte auch niemand etwas.
     *
     * Der Versand liegt bewusst NACH dem Commit und wird NICHT ueber
     * antwort_abschliessen() entkoppelt: Hier soll das Ergebnis ja gerade
     * angezeigt werden. Eine messbare Antwortzeit ist an dieser Stelle
     * unbedenklich — die Seite ist nur fuer Admins erreichbar und beantwortet
     * keine Frage, die man nicht ohnehin stellen darf.
     */
    if ($action === 'user_add') {
        $email = email_pruefen($_POST['email'] ?? '');
        $role  = ($_POST['role'] ?? 'user') === 'admin' ? 'admin' : 'user';

        if ($email === null) {
            $error = 'Ungültige E-Mail-Adresse (höchstens 190 Zeichen).';
        } else {
            // Vorab pruefen: Das ergibt die verstaendliche Meldung. Die
            // Ausnahme unten faengt trotzdem — zwischen Pruefung und Einfuegung
            // kann ein zweiter Vorgang dieselbe Adresse anlegen.
            $vorh = db()->prepare('SELECT id FROM users WHERE email = ?');
            $vorh->execute([$email]);
            if ($vorh->fetch()) {
                $error = 'Es gibt bereits ein Konto mit dieser E-Mail-Adresse.';
            } else {
                $token = bin2hex(random_bytes(32));
                $pdo = db();
                $pdo->beginTransaction();
                try {
                    /* Kontokennung bei der Anlage, nicht spaeter (E17).
                     * Sie ist ab hier unveraenderlich und der Ordnername der
                     * Admin-Sicherung; ein Konto ohne sie waere ein Konto, das
                     * sich nicht sichern laesst. */
                    $pdo->prepare('INSERT INTO users (email, role, account_key) VALUES (?, ?, ?)')
                        ->execute([$email, $role, bin2hex(random_bytes(8))]);
                    $uid = (int)$pdo->lastInsertId();
                    $pdo->prepare('INSERT INTO password_resets (user_id, token_hash, expires_at)
                                   VALUES (?, ?, DATE_ADD(NOW(), INTERVAL 24 HOUR))')
                        ->execute([$uid, hash('sha256', $token)]);
                    $pdo->commit();
                    $angelegt = true;
                } catch (PDOException $ex) {
                    $pdo->rollBack();
                    $angelegt = false;
                    if (ist_dublettenfehler($ex)) {
                        $error = 'Es gibt bereits ein Konto mit dieser E-Mail-Adresse.';
                    } else {
                        error_log('user_add: ' . $ex->getMessage());
                        $error = 'Der Zugang konnte nicht angelegt werden. '
                               . 'Es wurde nichts gespeichert.';
                    }
                }

                if ($angelegt) {
                    $link = $CFG['app']['base_url'] . '/pw_handling.php?token=' . $token;
                    $ok = smtp_send($email,
                        'Willkommen bei der Gen-EM Einsatzdokumentation Notarzt',
                        "Hallo,\n\n"
                        . "für dich wurde ein Zugang zur Gen-EM Einsatzdokumentation Notarzt angelegt.\n"
                        . "Über den folgenden Link legst du dein persönliches Passwort fest — der Link ist\n"
                        . "24 Stunden gültig:\n\n"
                        . $link . "\n\n"
                        . "Dabei wird auch dein Wiederherstellungsschlüssel angezeigt. Bitte notiere ihn dir\n"
                        . "sicher — ohne ihn lassen sich die verschlüsselten Angaben nach einem späteren\n"
                        . "Passwort-Reset nicht wiederherstellen.\n\n"
                        . "Bei Fragen oder Problemen wende dich gerne an philipp@gen-em.org.\n\n"
                        . "Viele Grüße\nGen-EM Einsatzdokumentation Notarzt\n");
                    if ($ok) {
                        $notice = 'Nutzer angelegt — Setz-Link per E-Mail verschickt.';
                    } else {
                        // Das Konto steht, nur die Mail kam nicht weg. Den Link
                        // hier zeigen ist der einzige Weg, der die Person noch
                        // erreicht; sonst bliebe ein unbrauchbares Konto stehen.
                        $notice = 'Nutzer angelegt — die E-Mail konnte NICHT verschickt werden.';
                        $setzLink = $link;
                    }
                }
            }
        }
    }

    if ($action === 'user_del') {
        $uid = (int)($_POST['id'] ?? 0);
        if ($uid !== $userId) { // sich selbst nicht loeschen
            db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
            $notice = 'Nutzer gelöscht (inkl. Geräte und Daten).';
        } else { $error = 'Du kannst dich nicht selbst löschen.'; }
    }

}

$users   = db()->query('SELECT id, email, name, role, created_at FROM users ORDER BY email')->fetchAll();
ui_seite_start(['titel' => 'Administration']);
ui_topbar('einstellungen');
?>

<div class="layout">
  <?php ui_settings_sidebar('admin'); ?>

<main class="page">
  <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

  <?php /* ---- Erinnerung an die Sicherungen (A8.4) ------------------------
     * Muster wie die Wartungswarnung in update.php: erst sagen, was ist, dann
     * was daraus folgt — und wohin man dafür geht. Ein Hinweis ohne den Weg
     * zur Handlung erzeugt nur Unbehagen.
     *
     * Sie steht HIER und nicht nur auf der Sicherungsseite: Ein Hinweis auf
     * einer Seite, die man erst öffnet, wenn man ohnehin sichern will, meldet
     * niemandem etwas — derselbe Fehler, der bei der Wartungsauskunft schon
     * einmal gemacht wurde (M3-05, Web 4.5.2). */
  $eSich = edbak_erinnerung(); ?>
  <?php if ($eSich['letzte'] === null): ?>
    <p class="alert alert-warn">Es wurde noch <strong>nie</strong> eine Sicherung der
       Konten erzeugt. <a href="admin_sicherungen.php">Zu den Sicherungen</a></p>
  <?php elseif ($eSich['faellig']): ?>
    <p class="alert alert-warn">Letzte Sicherung der Konten vor
       <strong><?= (int)$eSich['tage'] ?> Tagen</strong> — das eingestellte Intervall
       von <?= (int)$eSich['intervall'] ?> Tagen ist überschritten.
       <a href="admin_sicherungen.php">Zu den Sicherungen</a></p>
  <?php endif; ?>
  <?php if ($setzLink !== null): ?>
    <div class="keybox">
      <strong>Der Einladungslink konnte nicht per E-Mail zugestellt werden.</strong>
      <p>Das Konto ist angelegt und der Link 24 Stunden gültig. Bitte auf einem
         anderen Weg weitergeben — die Ursache des Fehlschlags steht im
         Fehlerprotokoll des Webspace.</p>
      <p class="codebig" style="word-break:break-all"><?= e($setzLink) ?></p>
      <p class="muted small">Wer diesen Link hat, kann das Passwort des neuen Kontos
         setzen. Nur an die Person selbst weitergeben.</p>
    </div>
  <?php endif; ?>

  <section>
    <h2>NutzerInnen</h2>
    <table class="data">
      <thead><tr><th>E-Mail</th><th>Name</th><th>Rolle</th><th>Seit</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($users as $u): ?>
        <tr class="rowlink" onclick="location.href='admin_user.php?id=<?= (int)$u['id'] ?>'">
          <td><a href="admin_user.php?id=<?= (int)$u['id'] ?>"><?= e($u['email']) ?></a></td>
          <td><?= e($u['name'] ?? '–') ?></td>
          <td><?= e($u['role']) ?></td>
          <td><?= e(fmt_local($u['created_at'], 'd.m.Y')) ?></td>
          <td>
            <?php if ((int)$u['id'] !== $userId): ?>
            <form method="post" onclick="event.stopPropagation()" data-confirm="Nutzer und alle zugehörigen Daten löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="user_del">
              <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
              <button class="btn-danger">Löschen</button>
            </form>
            <?php endif; ?>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="user_add">
      <input type="email" name="email" placeholder="neue@adresse.de" required>
      <select name="role"><option value="user">user</option><option value="admin">admin</option></select>
      <button class="btn-primary">Nutzer anlegen</button>
    </form>
  </section>
<?php ui_footer(); ?>
</main>
</div>
<?php ui_seite_ende(); ?>
