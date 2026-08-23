<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
// Eine Rollenpruefung fuer alle Seiten (M1-15). Hier stand als einziger Stelle
// eine handgeschriebene Fassung mit eigenem Wortlaut ("Nur fuer Admins.").
require_admin();
// Loeschen entscheidet seit Web 5.8.0 auch ueber die Admin-Sicherungen (E25).
require_once __DIR__ . '/adminbackup_lib.php';

$uid = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
$notice = null; $error = null;

// Muss VOR der POST-Verarbeitung stehen: die Loeschbestaetigung vergleicht
// die Eingabe mit $u['email']. Nach dem Block wird erneut gelesen, damit die
// Anzeige die soeben geaenderten Werte zeigt.
$st = db()->prepare('SELECT * FROM users WHERE id = ?');
$st->execute([$uid]);
$u = $st->fetch();
if (!$u) { ui_abbruch(404, 'NutzerIn nicht gefunden.', ['zurueck' => 'admin_users.php', 'zurueck_text' => 'Zur Nutzerverwaltung']); }

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'role') {
        $role = ($_POST['role'] ?? '') === 'admin' ? 'admin' : 'user';
        if ($uid === $userId && $role !== 'admin') {
            $error = 'Du kannst dir nicht selbst die Admin-Rolle entziehen.';
        } else {
            db()->prepare('UPDATE users SET role = ? WHERE id = ?')->execute([$role, $uid]);
            $notice = 'Rolle geändert.';
        }
    }
    if ($action === 'name') {
        $name = trim($_POST['name'] ?? '');
        db()->prepare('UPDATE users SET name = ? WHERE id = ?')
            ->execute([$name !== '' ? $name : null, $uid]);
        $notice = 'Name geändert.';
    }
    if ($action === 'email') {
        $email = email_pruefen($_POST['email'] ?? '');
        if ($email === null) {
            $error = 'Bitte eine gültige E-Mail-Adresse angeben (höchstens 190 Zeichen).';
        } else {
            try {
                db()->prepare('UPDATE users SET email = ? WHERE id = ?')->execute([$email, $uid]);
                $notice = 'E-Mail-Adresse geändert.';
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
                    $error = 'Die E-Mail-Adresse konnte nicht gespeichert werden. '
                           . 'Es wurde nichts geändert.';
                }
            }
        }
    }
    if ($action === 'user_delete') {
        // Zweite Stufe: die E-Mail-Adresse muss abgetippt werden. Bewusst
        // SERVERSEITIG geprueft — ein Browser-Dialog liesse sich umgehen.
        $eingabe = trim((string)($_POST['confirm_email'] ?? ''));
        if ($uid === $userId) {
            $error = 'Das eigene Konto kann hier nicht gelöscht werden.';
        } elseif (strcasecmp($eingabe, (string)$u['email']) !== 0) {
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
            $kennung = $u['account_key'] ?? null;
            $sicherungenWeg = false;
            if ($mitSicherungen) {
                $sicherungenWeg = edbak_konto_ordner_loeschen(
                    is_string($kennung) ? $kennung : null);
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
                // FK-Kaskaden entfernen Einsätze, Segmente, Tracks, Geräte, Diensttage
                db()->prepare('DELETE FROM users WHERE id = ?')->execute([$uid]);
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
        $notice = 'Gerät gelöscht. Hochgeladene Daten bleiben erhalten.';
    }
}

// Auffrischen: zeigt Rolle, Name und E-Mail nach einer Aenderung aktuell an.
$st->execute([$uid]);
$u = $st->fetch();
if (!$u) { ui_abbruch(404, 'NutzerIn nicht gefunden.', ['zurueck' => 'admin_users.php', 'zurueck_text' => 'Zur Nutzerverwaltung']); }

$dv = db()->prepare('SELECT id, device_id, label, active, last_seen FROM devices
                     WHERE user_id = ? AND device_id NOT LIKE \'manual-%\' ORDER BY created_at');
$dv->execute([$uid]);
$devices = $dv->fetchAll();
ui_seite_start(['titel' => 'NutzerIn bearbeiten']);
ui_topbar('einstellungen');
?>

<div class="layout">
  <?php ui_settings_sidebar('admin'); ?>

  <main class="page">
  <p><a href="admin_users.php" class="add-link">← zurück zur Nutzerverwaltung</a></p>
  <h1><?= e($u['name'] ?: $u['email']) ?></h1>
  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <h2>Rolle</h2>
  <form method="post" class="inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="role">
    <input type="hidden" name="id" value="<?= $uid ?>">
    <select name="role">
      <option value="user" <?= $u['role'] === 'user' ? 'selected' : '' ?>>user</option>
      <option value="admin" <?= $u['role'] === 'admin' ? 'selected' : '' ?>>admin</option>
    </select>
    <button class="btn-primary">Rolle speichern</button>
  </form>

  <h2>E-Mail-Adresse (Login)</h2>
  <form method="post" class="inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="email">
    <input type="hidden" name="id" value="<?= $uid ?>">
    <input type="email" name="email" required value="<?= e($u['email']) ?>">
    <button class="btn-primary">E-Mail speichern</button>
  </form>

  <h2>Name</h2>
  <form method="post" class="inline-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="name">
    <input type="hidden" name="id" value="<?= $uid ?>">
    <input type="text" name="name" maxlength="120" placeholder="z. B. Vorname Nachname"
           value="<?= e((string)($u['name'] ?? '')) ?>">
    <button class="btn-primary">Name speichern</button>
  </form>

  <p class="muted">Ein Passwort kann hier nicht gesetzt werden: Die Daten sind mit dem
     Passwort der Person Ende-zu-Ende-verschlüsselt. Bei vergessenem Passwort den Weg
     „Passwort vergessen" auf der Login-Seite nutzen — der Zugriff auf verschlüsselte
     Angaben wird danach mit dem Wiederherstellungsschlüssel der Person entsperrt.</p>

  <h2>Verbundene Geräte</h2>
  <table class="data">
    <thead><tr><th>Geräte-ID</th><th>Bezeichnung</th><th>Status</th><th>Zuletzt gesehen</th><th></th></tr></thead>
    <tbody>
    <?php if (!$devices): ?><tr><td colspan="5" class="muted">Keine Geräte.</td></tr><?php endif; ?>
    <?php foreach ($devices as $d): ?>
      <tr>
        <td><code><?= e($d['device_id']) ?></code></td>
        <td><?= e($d['label'] ?? '–') ?></td>
        <td><?= (int)$d['active'] ? 'aktiv' : '<span class="muted">deaktiviert</span>' ?></td>
        <td><?= e($d['last_seen'] ? fmt_local($d['last_seen'], 'd.m.Y H:i') : 'nie') ?></td>
        <td class="actions">
          <form method="post">
            <?= csrf_field() ?><input type="hidden" name="action" value="device_toggle">
            <input type="hidden" name="id" value="<?= $uid ?>"><input type="hidden" name="dev" value="<?= (int)$d['id'] ?>">
            <button class="btn-danger"><?= (int)$d['active'] ? 'Deaktivieren' : 'Aktivieren' ?></button>
          </form>
          <form method="post" data-confirm="Gerät wirklich löschen? Hochgeladene Daten bleiben erhalten.">
            <?= csrf_field() ?><input type="hidden" name="action" value="device_delete">
            <input type="hidden" name="id" value="<?= $uid ?>"><input type="hidden" name="dev" value="<?= (int)$d['id'] ?>">
            <button class="btn-danger">Löschen</button>
          </form>
        </td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>

  <hr class="sep">
  <h2>Nutzer löschen</h2>
  <?php /* Der Warntext sagt seit Web 5.8.0 nicht mehr unbedingt zu, dass danach
           nichts mehr lesbar ist, sondern bindet diese Aussage an die
           getroffene Wahl (E25). Vorher wäre sie unwahr geworden, sobald eine
           Admin-Sicherung existiert. */ ?>
  <p class="muted">Entfernt das Konto <strong><?= e($u['email']) ?></strong> mit
     <strong>allen</strong> Daten: Einsätze, Diensttage, Tracks, Reanimationen und Geräte.
     Dieser Schritt lässt sich nicht rückgängig machen und geht nicht über den
     Papierkorb.</p>
  <p class="muted"><strong>Ob danach nichts mehr lesbar ist, hängt von der Wahl
     unten ab.</strong> Werden die Sicherungen mitgelöscht, bleibt nichts zurück.
     Bleiben sie erhalten, überleben sie die Löschung und erscheinen unter
     „Sicherungen" als verwaiste Sicherung — geschützte Angaben darin sind
     weiterhin nur mit dem Wiederherstellungsschlüssel der Person zu öffnen.</p>
  <form method="post" class="settings-form"
        data-confirm="Nutzer endgültig löschen?" data-confirm-ok="Endgültig löschen">
    <?= csrf_field() ?><input type="hidden" name="action" value="user_delete">
    <input type="hidden" name="id" value="<?= $uid ?>">
    <label>Sicherungen dieses Kontos
      <select name="sicherungen_mit">
        <option value="1" selected>mitlöschen (Vorgabe)</option>
        <option value="0">erhalten — erscheinen danach als verwaiste Sicherung</option>
      </select></label>
    <label>Zur Bestätigung die E-Mail-Adresse abtippen
      <input type="text" name="confirm_email" autocomplete="off" required
             placeholder="<?= e($u['email']) ?>"></label>
    <button class="btn-red">! Nutzer endgültig löschen</button>
  </form>

  <?php ui_footer(); ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
