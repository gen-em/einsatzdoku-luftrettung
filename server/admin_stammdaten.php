<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();

/**
 * Zentrale (globale) Stammdaten: vom Admin gepflegte Eintraege mit
 * user_id = NULL, die allen NutzerInnen in Formularen/Vorbelegungen zur
 * Verfuegung stehen. UI-Muster identisch zu einstellungen.php?t=stammdaten,
 * schreibt aber mit user_id = NULL statt user_id = $userId und prueft
 * Duplikate gegen die bestehenden globalen Eintraege (siehe Konzept
 * Abschnitt 3.1 / 5.1 — UNIQUE-Keys greifen bei user_id NULL nicht).
 */

$notice = null; $error = null;
// Duplikat-Helfer stammdaten_dup_global()/stammdaten_dup_personal_count() -> db.php

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';

    if ($action === 'base_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $bid = (int)($_POST['id'] ?? 0);
        if ($n === '') {
            $error = 'Bitte einen Namen angeben.';
        } elseif (stammdaten_dup_global('bases', 'name', $n, null, null, $bid)) {
            $error = "„$n“ ist bereits zentral hinterlegt.";
        } elseif ($bid > 0) {
            db()->prepare('UPDATE bases SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $bid]);
            $notice = 'Standort gespeichert.';
        } else {
            db()->prepare('INSERT INTO bases (user_id, name) VALUES (NULL, ?)')->execute([$n]);
            $notice = 'Standort zentral angelegt.';
        }
    }
    if ($action === 'base_del') {
        $bid = (int)($_POST['id'] ?? 0);
        // Standortnamen in den Flugtagen sichern, bevor die Zeile verschwindet
        // (dasselbe Muster wie beim persoenlichen Loeschen, siehe einstellungen.php)
        db()->prepare('UPDATE days d
                       JOIN bases b ON b.id = d.base_id
                          SET d.base = b.name
                        WHERE d.base_id = ?')->execute([$bid]);
        db()->prepare('DELETE FROM user_defaults WHERE kind = "base" AND item_id = ?')->execute([$bid]);
        db()->prepare('DELETE FROM bases WHERE id = ? AND user_id IS NULL')->execute([$bid]);
        $notice = 'Standort gelöscht.';
    }

    if ($action === 'ac_save') {
        $reg = mb_substr(trim($_POST['registration'] ?? ''), 0, 64);
        $acId = (int)($_POST['id'] ?? 0);
        if ($reg === '') {
            $error = 'Bitte eine Kennung angeben.';
        } elseif (stammdaten_dup_global('aircraft', 'registration', $reg, null, null, $acId)) {
            $error = "„$reg“ ist bereits zentral hinterlegt.";
        } else {
            $flags = [];
            foreach (['p1','p2','hems','fr','other'] as $r) { $flags[] = isset($_POST[$r]) ? 1 : 0; }
            if ($acId > 0) {
                db()->prepare('UPDATE aircraft SET registration=?, p1=?, p2=?, hems=?, fr=?, other=?
                               WHERE id = ? AND user_id IS NULL')
                    ->execute(array_merge([$reg], $flags, [$acId]));
                $notice = 'Hubschrauber gespeichert.';
            } else {
                db()->prepare('INSERT INTO aircraft (user_id, registration, p1, p2, hems, fr, other)
                               VALUES (NULL,?,?,?,?,?,?)')
                    ->execute(array_merge([$reg], $flags));
                $notice = 'Hubschrauber zentral angelegt.';
            }
        }
    }
    if ($action === 'ac_del') {
        $acId = (int)($_POST['id'] ?? 0);
        db()->prepare('UPDATE days d
                       JOIN aircraft a ON a.id = d.aircraft_id
                          SET d.aircraft = a.registration
                        WHERE d.aircraft_id = ?')->execute([$acId]);
        db()->prepare('DELETE FROM user_defaults WHERE kind = "aircraft" AND item_id = ?')->execute([$acId]);
        db()->prepare('DELETE FROM aircraft WHERE id = ? AND user_id IS NULL')->execute([$acId]);
        $notice = 'Hubschrauber gelöscht.';
    }

    if ($action === 'crew_save') {
        $role = $_POST['role'] ?? '';
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $cid = (int)($_POST['id'] ?? 0);
        if ($n === '' || !in_array($role, ['p1','p2','hems','fr','other'], true)) {
            $error = 'Bitte Name und Rolle angeben.';
        } elseif (stammdaten_dup_global('crew_presets', 'name', $n, 'role', $role, $cid)) {
            $error = "„$n“ ist für diese Rolle bereits zentral hinterlegt.";
        } elseif ($cid > 0) {
            db()->prepare('UPDATE crew_presets SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $cid]);
            $notice = 'Eintrag gespeichert.';
        } else {
            db()->prepare('INSERT INTO crew_presets (user_id, role, name) VALUES (NULL,?,?)')
                ->execute([$role, $n]);
            $notice = 'Eintrag zentral angelegt.';
        }
    }
    if ($action === 'crew_del') {
        db()->prepare('DELETE FROM crew_presets WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Eintrag gelöscht.';
    }

    if ($action === 'res_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        if ($n === '') {
            $error = 'Bitte einen Namen angeben.';
        } elseif (stammdaten_dup_global('resources', 'name', $n, null, null, $wid)) {
            $error = "„$n“ ist bereits zentral hinterlegt.";
        } elseif ($wid > 0) {
            db()->prepare('UPDATE resources SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $wid]);
            $notice = 'Rettungsmittel gespeichert.';
        } else {
            db()->prepare('INSERT INTO resources (user_id, name) VALUES (NULL, ?)')->execute([$n]);
            $notice = 'Rettungsmittel zentral angelegt.';
        }
    }
    if ($action === 'res_del') {
        db()->prepare('DELETE FROM resources WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Rettungsmittel gelöscht.';
    }

    if ($action === 'bw_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        if ($n === '') {
            $error = 'Bitte einen Namen angeben.';
        } elseif (stammdaten_dup_global('bw_units', 'name', $n, null, null, $wid)) {
            $error = "„$n“ ist bereits zentral hinterlegt.";
        } elseif ($wid > 0) {
            db()->prepare('UPDATE bw_units SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $wid]);
            $notice = 'Bereitschaft gespeichert.';
        } else {
            db()->prepare('INSERT INTO bw_units (user_id, name) VALUES (NULL, ?)')->execute([$n]);
            $notice = 'Bereitschaft zentral angelegt.';
        }
    }
    if ($action === 'bw_del') {
        db()->prepare('DELETE FROM bw_units WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Bereitschaft gelöscht.';
    }

    if ($action === 'td_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 190);
        $tid = (int)($_POST['id'] ?? 0);
        if ($n === '') {
            $error = 'Bitte einen Namen angeben.';
        } elseif (stammdaten_dup_global('transport_dests', 'name', $n, null, null, $tid)) {
            $error = "„$n“ ist bereits zentral hinterlegt.";
        } elseif ($tid > 0) {
            db()->prepare('UPDATE transport_dests SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $tid]);
            $notice = 'Transportziel gespeichert.';
        } else {
            db()->prepare('INSERT INTO transport_dests (user_id, name) VALUES (NULL, ?)')->execute([$n]);
            $notice = 'Transportziel zentral angelegt.';
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Transportziel gelöscht.';
    }

    // Nach dem Speichern zurueck zum passenden Abschnitt umleiten (verhindert
    // erneutes Absenden beim Neuladen; Fehlermeldung bleibt ohne Umleitung
    // stehen, damit die Eingabe im Formular erhalten bleibt).
    $abschnitt = [
        'base_save' => 'standorte',      'base_del' => 'standorte',
        'ac_save'   => 'hubschrauber',   'ac_del'   => 'hubschrauber',
        'crew_save' => 'besatzung',      'crew_del' => 'besatzung',
        'res_save'  => 'rettungsmittel', 'res_del'  => 'rettungsmittel',
        'bw_save'   => 'bergwacht',      'bw_del'   => 'bergwacht',
        'td_save'   => 'transportziele', 'td_del'   => 'transportziele',
    ][$action] ?? null;
    if ($abschnitt !== null && $notice !== null) {
        $_SESSION['flash_notice'] = $notice;
        header('Location: admin_stammdaten.php#' . $abschnitt);
        exit;
    }
}

if (!empty($_SESSION['flash_notice'])) {
    $notice = $_SESSION['flash_notice'];
    unset($_SESSION['flash_notice']);
}

$ROLE_LABELS = ['p1' => 'Pilot 1', 'p2' => 'Pilot 2', 'hems' => 'HEMS',
                'fr' => 'Flugretter', 'other' => 'Sonstige'];

$bases = db()->query('SELECT id, name FROM bases WHERE user_id IS NULL ORDER BY name')->fetchAll();
$acs   = db()->query('SELECT * FROM aircraft WHERE user_id IS NULL ORDER BY registration')->fetchAll();
$crew  = db()->query('SELECT id, role, name FROM crew_presets WHERE user_id IS NULL ORDER BY name')->fetchAll();
$res   = db()->query('SELECT id, name FROM resources WHERE user_id IS NULL ORDER BY name')->fetchAll();
$bw    = db()->query('SELECT id, name FROM bw_units WHERE user_id IS NULL ORDER BY name')->fetchAll();
$tds   = db()->query('SELECT id, name FROM transport_dests WHERE user_id IS NULL ORDER BY name')->fetchAll();

$pick = function (array $rows, string $param) {
    foreach ($rows as $r) { if ((int)$r['id'] === (int)($_GET[$param] ?? 0)) { return $r; } }
    return null;
};
$editBase = $pick($bases, 'eb');
$editAc   = $pick($acs, 'ac');
$editRes  = $pick($res, 'er');
$editBw   = $pick($bw, 'ew');
$editTd   = $pick($tds, 'et');
$editCrew = null;
foreach ($crew as $c) { if ((int)$c['id'] === (int)($_GET['ec'] ?? 0)) { $editCrew = $c; } }

?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Zentrale Stammdaten — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<div class="layout">
  <?php ui_settings_sidebar('admin_stammdaten'); ?>

  <main class="page">
  <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

  <h1>Zentrale Stammdaten</h1>
  <p class="muted">Diese Einträge stehen automatisch allen NutzerInnen als Vorbelegung zur
     Verfügung (Kennzeichnung „zentral“ in der persönlichen Übersicht) und können nur hier
     vom Admin gepflegt werden.</p>

  <details class="stammblock" id="standorte">
    <summary>Standorte</summary>
    <table class="data">
      <thead><tr><th>Name</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$bases): ?><tr><td colspan="2" class="muted">Noch keine zentralen Standorte.</td></tr><?php endif; ?>
      <?php foreach ($bases as $b): $n = stammdaten_dup_personal_count('bases', 'name', $b['name']); ?>
        <tr>
          <td><?= e($b['name']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben einen gleichnamigen persönlichen Eintrag</span><?php endif; ?>
          </td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?eb=<?= (int)$b['id'] ?>#standorte">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#standorte" data-confirm="Zentralen Standort löschen? Er verschwindet dann bei allen NutzerInnen.">
              <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#standorte" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
      <input type="hidden" name="id" value="<?= (int)($editBase['id'] ?? 0) ?>">
      <input type="text" name="name" maxlength="120" placeholder="Standortname"
             value="<?= e($editBase['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editBase ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editBase): ?><a class="btn-red" href="admin_stammdaten.php#standorte">Abbrechen</a><?php endif; ?>
    </form>
  </details>

  <details class="stammblock" id="hubschrauber">
    <summary>Hubschrauber</summary>
    <table class="data">
      <thead><tr><th>Kennung</th><th>P1</th><th>P2</th><th>HEMS</th><th>FR</th><th>Sonst.</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$acs): ?><tr><td colspan="7" class="muted">Noch keine zentralen Hubschrauber.</td></tr><?php endif; ?>
      <?php foreach ($acs as $a): $n = stammdaten_dup_personal_count('aircraft', 'registration', $a['registration']); ?>
        <tr>
          <td><?= e($a['registration']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben eine gleichnamige persönliche Maschine</span><?php endif; ?>
          </td>
          <td class="checkcol"><?= (int)$a['p1'] ? '✓' : '' ?></td>
          <td class="checkcol"><?= (int)$a['p2'] ? '✓' : '' ?></td>
          <td class="checkcol"><?= (int)$a['hems'] ? '✓' : '' ?></td>
          <td class="checkcol"><?= (int)$a['fr'] ? '✓' : '' ?></td>
          <td class="checkcol"><?= (int)$a['other'] ? '✓' : '' ?></td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?ac=<?= (int)$a['id'] ?>#hubschrauber">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#hubschrauber" data-confirm="Zentralen Hubschrauber löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="ac_del">
              <input type="hidden" name="id" value="<?= (int)$a['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#hubschrauber" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="ac_save">
      <input type="hidden" name="id" value="<?= (int)($editAc['id'] ?? 0) ?>">
      <input type="text" name="registration" maxlength="64" placeholder="Kennung"
             value="<?= e($editAc['registration'] ?? '') ?>">
      <button class="btn-primary"><?= $editAc ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editAc): ?><a class="btn-red" href="admin_stammdaten.php#hubschrauber">Abbrechen</a><?php endif; ?>
      <div class="rolechecks">
        <span class="rolechecks-hint">Rollen auf dem Hubschrauber:</span>
        <?php foreach ($ROLE_LABELS as $k => $lbl): ?>
          <label><input type="checkbox" name="<?= $k ?>"
            <?= !empty($editAc[$k]) ? 'checked' : '' ?>> <?= e($lbl) ?></label>
        <?php endforeach; ?>
      </div>
    </form>
  </details>

  <details class="stammblock" id="besatzung">
    <summary>Besatzung</summary>
    <table class="data">
      <thead><tr><th>Rolle</th><th>Name</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$crew): ?><tr><td colspan="3" class="muted">Noch keine zentralen Besatzungs-Vorbelegungen.</td></tr><?php endif; ?>
      <?php foreach ($crew as $c): $n = stammdaten_dup_personal_count('crew_presets', 'name', $c['name'], 'role', $c['role']); ?>
        <tr>
          <td><?= e($ROLE_LABELS[$c['role']] ?? $c['role']) ?></td>
          <td><?= e($c['name']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben einen gleichnamigen persönlichen Eintrag</span><?php endif; ?>
          </td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?ec=<?= (int)$c['id'] ?>#besatzung">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#besatzung" data-confirm="Zentralen Eintrag löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="crew_del">
              <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#besatzung" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="crew_save">
      <input type="hidden" name="id" value="<?= (int)($editCrew['id'] ?? 0) ?>">
      <select name="role">
        <?php foreach ($ROLE_LABELS as $k => $lbl): ?>
          <option value="<?= $k ?>" <?= ($editCrew['role'] ?? '') === $k ? 'selected' : '' ?>><?= $lbl ?></option>
        <?php endforeach; ?>
      </select>
      <input type="text" name="name" maxlength="120" placeholder="Name"
             value="<?= e($editCrew['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editCrew ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editCrew): ?><a class="btn-red" href="admin_stammdaten.php#besatzung">Abbrechen</a><?php endif; ?>
    </form>
  </details>

  <details class="stammblock" id="rettungsmittel">
    <summary>Andere Rettungsmittel</summary>
    <table class="data">
      <thead><tr><th>Name</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$res): ?><tr><td colspan="2" class="muted">Noch keine zentralen Rettungsmittel.</td></tr><?php endif; ?>
      <?php foreach ($res as $r): $n = stammdaten_dup_personal_count('resources', 'name', $r['name']); ?>
        <tr>
          <td><?= e($r['name']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben einen gleichnamigen persönlichen Eintrag</span><?php endif; ?>
          </td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?er=<?= (int)$r['id'] ?>#rettungsmittel">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#rettungsmittel" data-confirm="Zentrales Rettungsmittel löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="res_del">
              <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#rettungsmittel" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="res_save">
      <input type="hidden" name="id" value="<?= (int)($editRes['id'] ?? 0) ?>">
      <input type="text" name="name" maxlength="120" placeholder="Rettungsmittel"
             value="<?= e($editRes['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editRes ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editRes): ?><a class="btn-red" href="admin_stammdaten.php#rettungsmittel">Abbrechen</a><?php endif; ?>
    </form>
  </details>

  <details class="stammblock" id="bergwacht">
    <summary>Bergwacht-Bereitschaften</summary>
    <table class="data">
      <thead><tr><th>Name</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$bw): ?><tr><td colspan="2" class="muted">Noch keine zentralen Bergwacht-Bereitschaften.</td></tr><?php endif; ?>
      <?php foreach ($bw as $w): $n = stammdaten_dup_personal_count('bw_units', 'name', $w['name']); ?>
        <tr>
          <td><?= e($w['name']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben einen gleichnamigen persönlichen Eintrag</span><?php endif; ?>
          </td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?ew=<?= (int)$w['id'] ?>#bergwacht">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#bergwacht" data-confirm="Zentrale Bereitschaft löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="bw_del">
              <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#bergwacht" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="bw_save">
      <input type="hidden" name="id" value="<?= (int)($editBw['id'] ?? 0) ?>">
      <input type="text" name="name" maxlength="120" placeholder="Bereitschaft"
             value="<?= e($editBw['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editBw ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editBw): ?><a class="btn-red" href="admin_stammdaten.php#bergwacht">Abbrechen</a><?php endif; ?>
    </form>
  </details>

  <details class="stammblock" id="transportziele">
    <summary>Transportziele</summary>
    <p class="muted">Vorschläge für das Feld „Transportziel“ im Einsatz.</p>
    <table class="data">
      <thead><tr><th>Name</th><th class="th-act">Aktionen</th></tr></thead>
      <tbody>
      <?php if (!$tds): ?><tr><td colspan="2" class="muted">Noch keine zentralen Transportziele.</td></tr><?php endif; ?>
      <?php foreach ($tds as $t): $n = stammdaten_dup_personal_count('transport_dests', 'name', $t['name']); ?>
        <tr>
          <td><?= e($t['name']) ?>
            <?php if ($n > 0): ?><br><span class="muted">⚠ <?= $n ?> Nutzer haben einen gleichnamigen persönlichen Eintrag</span><?php endif; ?>
          </td>
          <td><div class="rowactions">
            <a class="btn-yellow" href="admin_stammdaten.php?et=<?= (int)$t['id'] ?>#transportziele">Bearbeiten</a>
            <form method="post" action="admin_stammdaten.php#transportziele" data-confirm="Zentrales Transportziel löschen?">
              <?= csrf_field() ?><input type="hidden" name="action" value="td_del">
              <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
              <button class="btn-red">Löschen</button>
            </form>
          </div></td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <form method="post" action="admin_stammdaten.php#transportziele" class="inline-form">
      <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
      <input type="hidden" name="id" value="<?= (int)($editTd['id'] ?? 0) ?>">
      <input type="text" name="name" maxlength="190" placeholder="z. B. Klinikum Kempten"
             value="<?= e($editTd['name'] ?? '') ?>">
      <button class="btn-primary"><?= $editTd ? 'Speichern' : 'Anlegen' ?></button>
      <?php if ($editTd): ?><a class="btn-red" href="admin_stammdaten.php#transportziele">Abbrechen</a><?php endif; ?>
    </form>
  </details>

<script>
(function(){
  function oeffne(id){
    const d = document.getElementById(id);
    if (d && d.tagName === 'DETAILS') { d.open = true; d.scrollIntoView({ block: 'start' }); }
  }
  if (location.hash.length > 1) { oeffne(location.hash.slice(1)); }
  window.addEventListener('hashchange', () => {
    if (location.hash.length > 1) { oeffne(location.hash.slice(1)); }
  });
})();
</script>

  <?php ui_footer(); ?>
  </main>
</div>
</body>
</html>
