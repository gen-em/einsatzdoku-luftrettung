<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/diensttag_lib.php';   // Rollenkatalog, Artsymbole
require_once __DIR__ . '/validate_lib.php';   // pruef_ortspaar()

/**
 * Zentrale (globale) Stammdaten: vom Admin gepflegte Eintraege mit
 * user_id = NULL, die allen NutzerInnen zur Verfuegung stehen — sobald sie den
 * zugehoerigen Standort ausgewaehlt haben (E16).
 *
 * UI-Muster identisch zur Kontoansicht (einstellungen.php, Reiter „Standorte"
 * und „Rettungsmittel"), schreibt aber mit
 * user_id = NULL statt user_id = $userId und prueft Duplikate gegen die
 * bestehenden globalen Eintraege (siehe Konzept Abschnitt 3.1 / 5.1 —
 * UNIQUE-Keys greifen bei user_id NULL nicht).
 *
 * GEGLIEDERT NACH STANDORT (Konzept 3.8), wie die Nutzeransicht — ohne den
 * Block „zentrale Standorte auswaehlen": Die Auswahl ist Sache der NutzerIn,
 * nicht der Administration.
 *
 * DER STANDORTBEZUG IST VERBINDLICH (E15): Jedes Rettungsmittel, jede
 * Zielklinik, jede Besatzungs-Vorbelegung, jedes weitere Rettungsmittel und
 * jede Bergwacht-Bereitschaft gehoert genau einem Standort. Ohne Standort wird
 * nichts angelegt — die Spalte traegt nach der Nachbearbeitung NOT NULL (A12),
 * und ein Eintrag ohne Standort erschiene in keiner Auswahlliste.
 */

/* ZWEI REITER wie in der Kontoansicht (Web 7.0.0): „Standorte systemweit" und
 * „Rettungsmittel systemweit". Bis Web 6.3.0 war das EINE Seite namens
 * „Zentrale Stammdaten", auf der sechs Datenarten untereinander standen.
 * Der Bestand ist derselbe — nur die Anzeige teilt sich. */
$tab = $_GET['t'] ?? 'standorte';
if (!in_array($tab, ['standorte', 'rettungsmittel'], true)) { $tab = 'standorte'; }

$notice = null; $error = null;
// Duplikat-Helfer stammdaten_dup_global()/stammdaten_dup_personal_count() -> db.php

/** Zentralen Standort pruefen: Er muss existieren UND zentral sein. */
function admin_base_id(?int $id): ?int {
    if ($id === null || $id <= 0) { return null; }
    $q = db()->prepare('SELECT id FROM bases WHERE id = ? AND user_id IS NULL');
    $q->execute([$id]);
    return $q->fetchColumn() !== false ? $id : null;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $postBase = admin_base_id(isset($_POST['base_id']) ? (int)$_POST['base_id'] : null);

    if ($action === 'base_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $bid = (int)($_POST['id'] ?? 0);
        // Optionale Koordinate (E37/E39) — Regeln in pruef_ortspaar()
        // (validate_lib.php): nur zusammen, ausserhalb des Bereichs ist leer.
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        if ($n === '') {
            $error = 'Bitte einen Namen eintragen.';
        } elseif (stammdaten_dup_global('bases', 'name', $n, null, null, $bid)) {
            $error = '„' . $n . '“ ist bereits zentral hinterlegt.';
        } elseif ($bid > 0) {
            db()->prepare('UPDATE bases SET name = ?, lat = ?, lon = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $lat, $lon, $bid]);
            $notice = 'Standort gespeichert. Bereits dokumentierte Diensttage bleiben unverändert.';
        } else {
            db()->prepare('INSERT INTO bases (user_id, name, lat, lon) VALUES (NULL,?,?,?)')
                ->execute([$n, $lat, $lon]);
            $notice = 'Standort angelegt.';
        }
    }
    if ($action === 'base_del') {
        /* DAS LOESCHEN NIMMT DIE STAMMDATEN DES STANDORTS MIT (E15,
         * ON DELETE CASCADE) — und die Auswahl der NutzerInnen (`user_bases`)
         * ebenso. Diensttage bleiben unberuehrt: Sie haben Bezeichnung,
         * Koordinate, Art, Rollen und Faehigkeiten eingefroren (E8). Der
         * frueher noetige Umweg, den Namen vorher nach `days.base` zu retten,
         * ist damit entfallen. */
        $bid = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM user_defaults WHERE kind = "base" AND item_id = ?')->execute([$bid]);
        db()->prepare('DELETE FROM bases WHERE id = ? AND user_id IS NULL')->execute([$bid]);
        $notice = 'Standort samt seiner zentralen Stammdaten gelöscht. Bereits '
                . 'dokumentierte Diensttage bleiben unverändert.';
    }

    if ($action === 'veh_save') {
        $n    = mb_substr(trim($_POST['name'] ?? ''), 0, 64);
        $vid  = (int)($_POST['id'] ?? 0);
        /* Die Art ist Pflicht (Web 7.0.0) — dieselbe Aenderung wie in der
         * Kontoansicht und aus demselben Grund: „im Zweifel luftgebunden" war
         * eine Entscheidung, die niemand traf und niemand bemerkte. */
        $kindRoh = (string)($_POST['kind'] ?? '');
        $kind = in_array($kindRoh, ['air', 'ground'], true) ? $kindRoh : null;
        if ($n === '') {
            $error = 'Bitte eine Bezeichnung eintragen.';
        } elseif ($kind === null) {
            $error = 'Bitte die Art wählen: luftgebunden oder bodengebunden.';
        } elseif ($postBase === null) {
            $error = 'Bitte einen zentralen Standort wählen.';
        } elseif (stammdaten_dup_global('vehicles', 'name', $n, null, null, $vid)) {
            $error = '„' . $n . '“ ist bereits zentral hinterlegt.';
        } else {
            // Rollen auf die Art filtern (E5/E6) — serverseitig, unabhaengig
            // davon, was das Formular angeboten hat.
            $erlaubt = array_keys(crew_roles_fuer_art($kind));
            $rollen = [];
            foreach ((array)($_POST['roles'] ?? []) as $rc) {
                if (in_array((string)$rc, $erlaubt, true)) { $rollen[] = (string)$rc; }
            }
            // Faehigkeiten kommen nur luftgebunden vor (E29).
            $caps = [];
            if ($kind === 'air') {
                foreach ((array)($_POST['caps'] ?? []) as $c) {
                    if (array_key_exists((string)$c, VEHICLE_CAPABILITIES)) { $caps[] = (string)$c; }
                }
            }
            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($vid > 0) {
                    $pdo->prepare('UPDATE vehicles SET name = ?, kind = ?, base_id = ?
                                   WHERE id = ? AND user_id IS NULL')
                        ->execute([$n, $kind, $postBase, $vid]);
                } else {
                    $pdo->prepare('INSERT INTO vehicles (user_id, base_id, name, kind)
                                   VALUES (NULL,?,?,?)')->execute([$postBase, $n, $kind]);
                    $vid = (int)$pdo->lastInsertId();
                }
                /* Vollstaendig ersetzen. Auf bereits dokumentierte Diensttage
                 * wirkt das nicht — ihr Rollen- und Faehigkeitssatz steht
                 * eingefroren in `day_crew` und `day_capabilities` (E8, A13e). */
                $pdo->prepare('DELETE FROM vehicle_roles WHERE vehicle_id = ?')->execute([$vid]);
                $insR = $pdo->prepare('INSERT IGNORE INTO vehicle_roles (vehicle_id, role_code) VALUES (?,?)');
                foreach ($rollen as $rc) { $insR->execute([$vid, $rc]); }
                $pdo->prepare('DELETE FROM vehicle_capabilities WHERE vehicle_id = ?')->execute([$vid]);
                $insC = $pdo->prepare('INSERT IGNORE INTO vehicle_capabilities (vehicle_id, capability) VALUES (?,?)');
                foreach ($caps as $c) { $insC->execute([$vid, $c]); }
                $pdo->commit();
                $notice = 'Rettungsmittel gespeichert. Bereits dokumentierte Diensttage '
                        . 'behalten Art, Rollen und Fähigkeiten unverändert.';
            } catch (PDOException $ex) {
                if ($pdo->inTransaction()) { $pdo->rollBack(); }
                $error = ist_dublettenfehler($ex)
                    ? 'Diese Bezeichnung existiert bereits.'
                    : 'Das Rettungsmittel konnte nicht gespeichert werden.';
            }
        }
    }
    if ($action === 'veh_del') {
        $vid = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM user_defaults WHERE kind = "vehicle" AND item_id = ?')->execute([$vid]);
        db()->prepare('DELETE FROM vehicles WHERE id = ? AND user_id IS NULL')->execute([$vid]);
        $notice = 'Rettungsmittel gelöscht. Bereits dokumentierte Diensttage bleiben '
                . 'unverändert.';
    }

    if ($action === 'crew_save') {
        $role = (string)($_POST['role'] ?? '');
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $cid = (int)($_POST['id'] ?? 0);
        if ($n === '' || !array_key_exists($role, CREW_ROLES)) {
            $error = 'Bitte Rolle und Namen angeben.';
        } elseif ($postBase === null) {
            $error = 'Bitte einen zentralen Standort wählen.';
        } elseif (stammdaten_dup_global('crew_presets', 'name', $n, 'role_code', $role, $cid)) {
            $error = '„' . $n . '“ ist für diese Rolle bereits zentral hinterlegt.';
        } elseif ($cid > 0) {
            db()->prepare('UPDATE crew_presets SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $cid]);
            $notice = 'Eintrag gespeichert.';
        } else {
            db()->prepare('INSERT INTO crew_presets (user_id, base_id, role_code, name)
                           VALUES (NULL,?,?,?)')->execute([$postBase, $role, $n]);
            $notice = 'Eintrag angelegt.';
        }
    }
    if ($action === 'crew_del') {
        $cid = (int)($_POST['id'] ?? 0);
        db()->prepare('DELETE FROM crew_presets WHERE id = ? AND user_id IS NULL')->execute([$cid]);
        $notice = 'Eintrag gelöscht.';
    }

    if ($action === 'res_save') {
        $n = mb_substr(trim($_POST['name'] ?? ''), 0, 120);
        $wid = (int)($_POST['id'] ?? 0);
        if ($n === '') {
            $error = 'Bitte einen Namen eintragen.';
        } elseif ($postBase === null) {
            $error = 'Bitte einen zentralen Standort wählen.';
        } elseif (stammdaten_dup_global('resources', 'name', $n, null, null, $wid)) {
            $error = '„' . $n . '“ ist bereits zentral hinterlegt.';
        } elseif ($wid > 0) {
            db()->prepare('UPDATE resources SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $wid]);
            $notice = 'Rettungsmittel gespeichert.';
        } else {
            db()->prepare('INSERT INTO resources (user_id, base_id, name) VALUES (NULL,?,?)')
                ->execute([$postBase, $n]);
            $notice = 'Rettungsmittel angelegt.';
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
            $error = 'Bitte einen Namen eintragen.';
        } elseif ($postBase === null) {
            $error = 'Bitte einen zentralen Standort wählen.';
        } elseif (stammdaten_dup_global('bw_units', 'name', $n, null, null, $wid)) {
            $error = '„' . $n . '“ ist bereits zentral hinterlegt.';
        } elseif ($wid > 0) {
            db()->prepare('UPDATE bw_units SET name = ? WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $wid]);
            $notice = 'Bereitschaft gespeichert.';
        } else {
            db()->prepare('INSERT INTO bw_units (user_id, base_id, name) VALUES (NULL,?,?)')
                ->execute([$postBase, $n]);
            $notice = 'Bereitschaft angelegt.';
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
        [$lat, $lon] = pruef_ortspaar($_POST['lat'] ?? null, $_POST['lon'] ?? null);
        if ($n === '') {
            $error = 'Bitte einen Namen eintragen.';
        } elseif ($postBase === null) {
            $error = 'Bitte einen zentralen Standort wählen.';
        } elseif (stammdaten_dup_global('transport_dests', 'name', $n, null, null, $tid)) {
            $error = '„' . $n . '“ ist bereits zentral hinterlegt.';
        } elseif ($tid > 0) {
            db()->prepare('UPDATE transport_dests SET name = ?, lat = ?, lon = ?
                           WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $lat, $lon, $tid]);
            $notice = 'Zielklinik gespeichert. Bereits dokumentierte Einsätze bleiben '
                    . 'unverändert.';
        } else {
            db()->prepare('INSERT INTO transport_dests (user_id, base_id, name, lat, lon)
                           VALUES (NULL,?,?,?,?)')->execute([$postBase, $n, $lat, $lon]);
            $notice = 'Zielklinik angelegt.';
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Zielklinik gelöscht.';
    }

    /* Nach dem Speichern zurueck zum passenden Abschnitt umleiten (verhindert
     * erneutes Absenden beim Neuladen) und den Abschnitt dort wieder aufklappen
     * (siehe Hash-Skript unten). Gilt fuer Erfolg UND Fehlermeldung.
     *
     * Die Anker sind seit der Gliederung nach Standort STANDORTBEZOGEN:
     * `sd-<Standortkennung>`. Nur die Standortliste selbst hat einen festen. */
    $unterblock = [
        'veh_save' => 'veh', 'veh_del' => 'veh',
        'crew_save' => 'crew', 'crew_del' => 'crew',
        'td_save'  => 'td',  'td_del'  => 'td',
        'res_save' => 'res', 'res_del' => 'res',
        'bw_save'  => 'bw',  'bw_del'  => 'bw',
    ][$action] ?? null;
    $zurueckTab = 'standorte';
    $abschnitt = null;
    if (in_array($action, ['base_save', 'base_del'], true)) {
        $abschnitt = 'standorte';
    } elseif ($action !== '') {
        $zurueckTab = 'rettungsmittel';
        /* Bis in den UNTERBLOCK der Datenart zurueck (Web 7.0.0). Die Bloecke
         * sind zweistufig verschachtelt; ohne die Art landete man im richtigen
         * Standort, aber wieder ganz oben. */
        $abschnitt = ($postBase !== null && $unterblock !== null)
            ? ('sd-' . $postBase . '-' . $unterblock)
            : ($postBase !== null ? ('sd-' . $postBase) : 'standorte');
    }
    if ($abschnitt !== null && ($notice !== null || $error !== null)) {
        if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
        if ($error !== null) { $_SESSION['flash_error'] = $error; }
        header('Location: admin_stammdaten.php?t=' . $zurueckTab . '#' . $abschnitt);
        exit;
    }
}

if (!empty($_SESSION['flash_notice'])) {
    $notice = $_SESSION['flash_notice'];
    unset($_SESSION['flash_notice']);
}
if (!empty($_SESSION['flash_error'])) {
    $error = $_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

/* ---- Bestand laden -------------------------------------------------------- */
/* Praefixe der Ortsfelder dieser Seite. Sie entstehen beim Rendern — je
 * Standort eines fuer die Zielklinik —, und die Belebung im Browser laeuft am
 * Ende ueber genau diese Liste (siehe einstellungen.php, gleiches Muster). */
$ORTSFELDER = [];

$bases = db()->query('SELECT id, name, lat, lon FROM bases WHERE user_id IS NULL ORDER BY name')->fetchAll();
$baseIds = array_map(static fn($b) => (int)$b['id'], $bases);

/* Je Datenart EINE Abfrage, danach nach Standort gebuendelt: Je Standort
 * einzeln zu fragen ergaebe bei zehn Standorten fuenfzig Abfragen. */
$ladeNachBase = function (string $tabelle, string $spalten) use ($baseIds): array {
    if (!$baseIds) { return []; }
    $nach = [];
    foreach (sql_in_bloecken(db(),
            "SELECT $spalten, base_id FROM `$tabelle`
             WHERE user_id IS NULL AND base_id IN ({IDS}) ORDER BY name",
            $baseIds) as $z) {
        $nach[(int)$z['base_id']][] = $z;
    }
    return $nach;
};
$vehNach  = $ladeNachBase('vehicles', 'id, name, kind');
$crewNach = $ladeNachBase('crew_presets', 'id, name, role_code');
$tdNach   = $ladeNachBase('transport_dests', 'id, name, lat, lon');
$resNach  = $ladeNachBase('resources', 'id, name');
$bwNach   = $ladeNachBase('bw_units', 'id, name');

$vehIds = [];
foreach ($vehNach as $liste) { foreach ($liste as $v) { $vehIds[] = (int)$v['id']; } }
$vehRollen = $vehCaps = [];
if ($vehIds) {
    foreach (sql_in_bloecken(db(),
            'SELECT vehicle_id, role_code FROM vehicle_roles
             WHERE vehicle_id IN ({IDS})', $vehIds) as $r) {
        $vehRollen[(int)$r['vehicle_id']][] = (string)$r['role_code'];
    }
    foreach (sql_in_bloecken(db(),
            'SELECT vehicle_id, capability FROM vehicle_capabilities
             WHERE vehicle_id IN ({IDS})', $vehIds) as $c) {
        $vehCaps[(int)$c['vehicle_id']][] = (string)$c['capability'];
    }
}

/* Wie viele NutzerInnen haben diesen Standort ausgewaehlt (E16)? Die Zahl
 * gehoert in die Rueckfrage vor dem Loeschen: Sie sagt, wen es trifft. */
$ubZahl = [];
if ($baseIds) {
    foreach (sql_in_bloecken(db(),
            'SELECT base_id, COUNT(*) AS n FROM user_bases
             WHERE base_id IN ({IDS}) GROUP BY base_id', $baseIds) as $z) {
        $ubZahl[(int)$z['base_id']] = (int)$z['n'];
    }
}

$pick = function (array $rows, string $param) {
    foreach ($rows as $r) { if ((int)$r['id'] === (int)($_GET[$param] ?? 0)) { return $r; } }
    return null;
};
$pickNach = function (array $nachBase, string $param) {
    $ges = (int)($_GET[$param] ?? 0);
    if ($ges <= 0) { return null; }
    foreach ($nachBase as $liste) {
        foreach ($liste as $z) { if ((int)$z['id'] === $ges) { return $z; } }
    }
    return null;
};
$editBase = $pick($bases, 'eb');
$editVeh  = $pickNach($vehNach, 'ev');
$editCrew = $pickNach($crewNach, 'ec');
$editTd   = $pickNach($tdNach, 'et');
$editRes  = $pickNach($resNach, 'er');
$editBw   = $pickNach($bwNach, 'ew');

/* Zahl der zentralen Stammdatensaetze eines Standorts — fuer die Rueckfrage
 * vor dem Loeschen (Konzept 4.2). */
$anzahlJeBase = function (int $bid) use ($vehNach, $crewNach, $tdNach, $resNach, $bwNach): int {
    $n = 0;
    foreach ([$vehNach, $crewNach, $tdNach, $resNach, $bwNach] as $art) {
        $n += count($art[$bid] ?? []);
    }
    return $n;
};

/* Welche Rollen gibt es an diesem Standort? Dieselbe Regel wie in der
 * Kontoansicht (Web 7.0.0): Eine Rolle erscheint in der Besatzungspflege, wenn
 * mindestens ein Rettungsmittel dieses Standorts sie fuehrt — oder wenn bereits
 * ein Eintrag dafuer besteht. Sonst stuenden an einem reinen NEF-Standort vier
 * leere Flugrollen mit vier Eingabezeilen. */
$rollenAmStandort = function (int $bid) use ($vehNach, $vehRollen, $crewNach): array {
    $rollen = [];
    foreach (($vehNach[$bid] ?? []) as $v) {
        foreach (($vehRollen[(int)$v['id']] ?? []) as $rc) { $rollen[$rc] = true; }
    }
    foreach (($crewNach[$bid] ?? []) as $c) { $rollen[(string)$c['role_code']] = true; }
    return array_values(array_filter(array_keys(CREW_ROLES),
        static fn(string $rc): bool => isset($rollen[$rc])));
};
?><!doctype html>
<html lang="de">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= $tab === 'standorte' ? 'Standorte systemweit' : 'Rettungsmittel systemweit' ?> — Einsatzdoku</title>
<link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
<?= favicon_tags() ?></head>
<body>
<?php ui_topbar('einstellungen'); ?>

<div class="layout">
  <?php ui_settings_sidebar($tab === 'standorte' ? 'admin_standorte' : 'admin_rettungsmittel'); ?>

  <main class="page">
    <h1><?= $tab === 'standorte' ? 'Standorte systemweit' : 'Rettungsmittel systemweit' ?></h1>
    <?php if ($notice): ?><p class="alert alert-ok"><?= e($notice) ?></p><?php endif; ?>
    <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

    <p class="muted">Diese Einträge gelten für <strong>alle Konten</strong>. Sie
       erscheinen in den Auswahllisten einer NutzerIn erst, wenn sie den
       zugehörigen Standort in ihren Einstellungen ausgewählt hat — die Auswahl
       ist ihre Sache, nicht die der Administration.</p>
    <p class="muted"><strong>Der Standort ist der Anker</strong> (E15): Jedes
       Rettungsmittel, jede Zielklinik, jede Besatzungs-Vorbelegung, jedes
       weitere Rettungsmittel und jede Bergwacht-Bereitschaft gehört zu genau
       einem Standort. Eine standortübergreifende Ebene gibt es nicht.</p>
    <p class="muted">Änderungen wirken <strong>nur auf neue Diensttage</strong>.
       Bereits dokumentierte haben Art, Rollen, Fähigkeiten und Bezeichnungen
       beim Anlegen eingefroren und bleiben unverändert — auch beim Löschen.</p>

  <?php if ($tab === 'standorte'): ?>
    <p class="muted">Hier stehen die Standorte selbst. Was an ihnen hängt —
       Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel,
       Bergwacht — steht unter
       <a href="admin_stammdaten.php?t=rettungsmittel">Rettungsmittel
       systemweit</a>.</p>

    <details class="stammblock" id="standorte" open>
      <summary>Standorte</summary>
      <table class="data">
        <tbody>
        <?php if (!$bases): ?><tr><td colspan="2" class="muted">Noch keine zentralen Standorte.</td></tr><?php endif; ?>
        <?php foreach ($bases as $b): $bid = (int)$b['id'];
              $anz = $anzahlJeBase($bid); $nutzer = $ubZahl[$bid] ?? 0; ?>
          <tr>
            <td><?= e($b['name']) ?>
              <?php if ($b['lat'] !== null && $b['lon'] !== null): ?>
                <br><span class="muted small"><?= e((string)$b['lat']) ?>, <?= e((string)$b['lon']) ?></span>
              <?php endif; ?>
              <br><span class="muted small"><?= $anz ?> Stammdatensätze ·
                <?= $nutzer === 1 ? '1 Konto hat ihn ausgewählt'
                                  : $nutzer . ' Konten haben ihn ausgewählt' ?></span>
            </td>
            <td class="th-act"><div class="rowactions">
              <a class="btn-yellow" href="admin_stammdaten.php?t=standorte&amp;eb=<?= $bid ?>#standorte">Bearbeiten</a>
              <?php /* Die Rückfrage BEZIFFERT, was mitgeht (Konzept 4.2): die
                       zentralen Stammdaten dieses Standorts und die Auswahl in
                       jedem Konto, das ihn führt. */ ?>
              <form method="post" action="admin_stammdaten.php?t=standorte#standorte"
                    data-confirm="Zentralen Standort „<?= e($b['name']) ?>“ löschen? <?= $anz > 0
                        ? ($anz === 1 ? 'Ein zentraler Stammdatensatz' : $anz . ' zentrale Stammdatensätze')
                          . ' dieses Standorts werden mitgelöscht.'
                        : 'Es hängen keine zentralen Stammdaten daran.' ?> <?= $nutzer > 0
                        ? 'Er verschwindet aus den Auswahllisten von ' . $nutzer . ' Konten.'
                        : '' ?> Bereits dokumentierte Diensttage bleiben unverändert.">
                <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
                <input type="hidden" name="id" value="<?= $bid ?>">
                <button class="btn-red">Löschen</button>
              </form>
            </div></td>
          </tr>
        <?php endforeach; ?>
        </tbody>
      </table>
      <?php /* Eingabe im eigenen Rahmen, Name und Schaltfläche in einer Zeile,
               die freiwillige Ortsangabe darunter — gleiche Form wie in der
               Kontoansicht und beim Rettungsmittel (Web 7.0.0). */ ?>
      <div class="neu-form">
        <h4><?= $editBase ? 'Standort bearbeiten' : 'Standort hinzufügen' ?></h4>
        <form method="post" action="admin_stammdaten.php?t=standorte#standorte">
          <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
          <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
          <div class="neu-zeile">
            <input type="text" name="name" id="adbaseaddr" class="focus-target" maxlength="120" required
                   placeholder="z. B. Standort Kempten" value="<?= e($editBase['name'] ?? '') ?>">
            <button class="btn-primary"><?= $editBase ? 'Änderung speichern' : 'Hinzufügen' ?></button>
            <?php if ($editBase): ?><a class="btn-red" href="admin_stammdaten.php?t=standorte">Abbrechen</a><?php endif; ?>
          </div>
          <?php /* Koordinaten optional (E37/E39). Quelle des Abfahrtorts
                   „Standort", beim Anlegen eines Diensttags eingefroren (E8). Seit
                   Web 6.1.0 mit Adresssuche — dieselbe Komponente wie in der
                   Kontoansicht und am Einsatz (assets/ortsfeld.js). */
                $ORTSFELDER[] = 'adbase'; ?>
          <div class="neu-feld">
            <?php ui_ortsfeld([
                    'praefix' => 'adbase', 'feld' => false, 'such' => true,
                    'klasse' => 'loc-inline',
                    'such_hinweis' => 'Lage des Standorts (optional)',
                    'lat_name' => 'lat', 'lon_name' => 'lon',
                    'lat' => (string)($editBase['lat'] ?? ''),
                    'lon' => (string)($editBase['lon'] ?? ''),
                ]); ?>
          </div>
        </form>
      </div>
    </details>

  <?php else: ?>
    <?php if (!$bases): ?>
      <p class="alert alert-info">Ohne zentralen Standort lassen sich keine
         zentralen Rettungsmittel, Besatzungs-Vorbelegungen oder Zielkliniken
         anlegen. Bitte zuerst unter
         <a href="admin_stammdaten.php?t=standorte">Standorte systemweit</a>
         einen Standort hinzufügen.</p>
    <?php endif; ?>

    <?php foreach ($bases as $b): $bid = (int)$b['id']; $anker = 'sd-' . $bid;
          $vehListe = $vehNach[$bid] ?? [];
          $hatLuft = false;
          foreach ($vehListe as $v) { if ($v['kind'] === 'air') { $hatLuft = true; break; } }
          $rollenHier = $rollenAmStandort($bid); ?>
      <details class="stammblock" id="<?= e($anker) ?>">
        <summary><?= e($b['name']) ?></summary>

        <details class="stammunter" id="<?= e($anker) ?>-veh">
          <summary>Rettungsmittel<span class="stammzahl"><?= count($vehListe) ?></span></summary>
          <p class="muted">Die Art entscheidet über Besatzungsrollen und die im
             Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht) gibt
             es nur luftgebunden.</p>
          <table class="data">
            <tbody>
            <?php if (!$vehListe): ?><tr><td colspan="2" class="muted">Noch keine Rettungsmittel an diesem Standort.</td></tr><?php endif; ?>
            <?php foreach ($vehListe as $v): $vid = (int)$v['id'];
                  $sym = dt_art_symbol((string)$v['kind']);
                  $rollenTxt = array_map('crew_role_label', $vehRollen[$vid] ?? []);
                  $capsTxt = array_map(static fn(string $c): string => VEHICLE_CAPABILITIES[$c] ?? $c,
                                       $vehCaps[$vid] ?? []);
                  $dupP = stammdaten_dup_personal_count('vehicles', 'name', (string)$v['name']); ?>
              <tr>
                <td><span class="artzeichen" title="<?= e($sym['text']) ?>"
                          aria-label="<?= e($sym['text']) ?>"><?= e($sym['zeichen']) ?></span>
                  <?= e($v['name']) ?>
                  <?php /* Die Art steht nicht mehr ausgeschrieben darunter — das
                           Symbol davor sagt sie, mit Textalternative in
                           title/aria-label (Web 7.0.0, wie in der
                           Kontoansicht). */ ?>
                  <br><span class="muted small"><?php
                    echo $rollenTxt ? e(implode(', ', $rollenTxt)) : 'keine Rollen';
                    echo $capsTxt ? ' · ' . e(implode(', ', $capsTxt)) : ''; ?></span>
                  <?php if ($dupP > 0): ?>
                    <br><span class="muted small">⚠ <?= $dupP ?> Konten führen einen
                      gleichnamigen eigenen Eintrag</span>
                  <?php endif; ?>
                </td>
                <td class="th-act"><div class="rowactions">
                  <a class="btn-yellow" href="admin_stammdaten.php?t=rettungsmittel&amp;ev=<?= $vid ?>#<?= e($anker) ?>-veh">Bearbeiten</a>
                  <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-veh"
                        data-confirm="Rettungsmittel löschen? Bereits dokumentierte Diensttage bleiben unverändert.">
                    <?= csrf_field() ?><input type="hidden" name="action" value="veh_del">
                    <input type="hidden" name="id" value="<?= $vid ?>">
                    <input type="hidden" name="base_id" value="<?= $bid ?>">
                    <button class="btn-red">Löschen</button>
                  </form>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php $evHier = ($editVeh && (int)$editVeh['base_id'] === $bid) ? $editVeh : null;
                $evRollen = $evHier ? ($vehRollen[(int)$evHier['id']] ?? []) : [];
                $evCaps   = $evHier ? ($vehCaps[(int)$evHier['id']] ?? []) : []; ?>
          <div class="neu-form">
            <h4><?= $evHier ? 'Rettungsmittel bearbeiten' : 'Rettungsmittel hinzufügen' ?></h4>
            <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-veh" class="ac-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="veh_save">
              <input type="hidden" name="id" value="<?= $evHier ? (int)$evHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <div class="neu-zeile">
                <input type="text" name="name" maxlength="64" required placeholder="z. B. Christoph 17"
                       value="<?= e($evHier['name'] ?? '') ?>">
                <button class="btn-primary"><?= $evHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
                <?php if ($evHier): ?><a class="btn-red" href="admin_stammdaten.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
              </div>
              <div class="neu-feld">
                <span class="neu-titel">Art</span>
                <span class="vehkind">
                  <label><input type="radio" name="kind" value="air" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'air') ? 'checked' : '' ?>> luftgebunden</label>
                  <label><input type="radio" name="kind" value="ground" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'ground') ? 'checked' : '' ?>> bodengebunden</label>
                </span>
              </div>
              <div class="neu-feld rollen-zeile">
                <span class="neu-titel">Besatzungsrollen <span class="muted small">(optional)</span></span>
                <span class="acroles">
                  <?php foreach (CREW_ROLES as $rc => $rr): ?>
                    <label class="rollehaken" data-kind="<?= e($rr['kind']) ?>">
                      <input type="checkbox" name="roles[]" value="<?= e($rc) ?>"
                             <?= in_array($rc, $evRollen, true) ? 'checked' : '' ?>>
                      <?= e($rr['label']) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
              <div class="neu-feld vehcaps-zeile">
                <span class="neu-titel">Fähigkeiten <span class="muted small">(nur luftgebunden)</span></span>
                <span class="acroles vehcaps">
                  <?php foreach (VEHICLE_CAPABILITIES as $ck => $cl): ?>
                    <label><input type="checkbox" name="caps[]" value="<?= e($ck) ?>"
                           <?= in_array($ck, $evCaps, true) ? 'checked' : '' ?>>
                      <?= e($cl) ?></label>
                  <?php endforeach; ?>
                </span>
              </div>
            </form>
          </div>
        </details>

        <details class="stammunter" id="<?= e($anker) ?>-crew">
          <summary>Besatzung<span class="stammzahl"><?= count($crewNach[$bid] ?? []) ?></span></summary>
          <?php if (!$rollenHier): ?>
            <p class="muted">Noch keine Rolle an diesem Standort. Rollen entstehen
               am Rettungsmittel: Trage oben eines ein und hake an, welche Rollen
               es führt.</p>
          <?php endif; ?>
          <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
            <h4><?= e($rr['label']) ?></h4>
            <table class="data">
              <tbody>
              <?php $any = false;
                    foreach (($crewNach[$bid] ?? []) as $c):
                        if ($c['role_code'] !== $rk) { continue; }
                        $any = true;
                        $dupP = stammdaten_dup_personal_count('crew_presets', 'name', (string)$c['name'], 'role_code', $rk); ?>
                <tr>
                  <td><?= e($c['name']) ?>
                    <?php if ($dupP > 0): ?>
                      <br><span class="muted small">⚠ <?= $dupP ?> Konten führen einen
                        gleichnamigen eigenen Eintrag</span>
                    <?php endif; ?>
                  </td>
                  <td class="th-act"><div class="rowactions">
                    <a class="btn-yellow" href="admin_stammdaten.php?t=rettungsmittel&amp;ec=<?= (int)$c['id'] ?>#<?= e($anker) ?>-crew">Bearbeiten</a>
                    <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-crew"
                          data-confirm="Eintrag löschen?">
                      <?= csrf_field() ?><input type="hidden" name="action" value="crew_del">
                      <input type="hidden" name="id" value="<?= (int)$c['id'] ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-red">Löschen</button>
                    </form>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              <?php if (!$any): ?><tr><td class="muted">Noch keine Einträge.</td><td></td></tr><?php endif; ?>
              </tbody>
            </table>
            <?php $ecHier = ($editCrew && (int)$editCrew['base_id'] === $bid
                             && $editCrew['role_code'] === $rk) ? $editCrew : null; ?>
            <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-crew" class="inline-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="crew_save">
              <input type="hidden" name="role" value="<?= e($rk) ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <input type="hidden" name="id" value="<?= $ecHier ? (int)$ecHier['id'] : 0 ?>">
              <input type="text" name="name" placeholder="Name" maxlength="120" required
                     value="<?= e($ecHier['name'] ?? '') ?>">
              <button class="btn-primary"><?= $ecHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
              <?php if ($ecHier): ?><a class="btn-red" href="admin_stammdaten.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
            </form>
          <?php endforeach; ?>
        </details>

        <details class="stammunter" id="<?= e($anker) ?>-td">
          <summary>Zielkliniken<span class="stammzahl"><?= count($tdNach[$bid] ?? []) ?></span></summary>
          <p class="muted">Koordinaten sind freiwillig; ohne sie entsteht lediglich
             kein Pin auf der Karte. Sie werden am Einsatz eingefroren — eine
             späte Korrektur verändert bereits erfasste Einsätze nicht.</p>
          <table class="data">
            <tbody>
            <?php if (!($tdNach[$bid] ?? [])): ?><tr><td class="muted">Noch keine Zielkliniken.</td><td></td></tr><?php endif; ?>
            <?php foreach (($tdNach[$bid] ?? []) as $t):
                  $dupP = stammdaten_dup_personal_count('transport_dests', 'name', (string)$t['name']); ?>
              <tr>
                <td><?= e($t['name']) ?>
                  <?php if ($t['lat'] !== null && $t['lon'] !== null): ?>
                    <br><span class="muted small"><?= e((string)$t['lat']) ?>, <?= e((string)$t['lon']) ?></span>
                  <?php endif; ?>
                  <?php if ($dupP > 0): ?>
                    <br><span class="muted small">⚠ <?= $dupP ?> Konten führen einen
                      gleichnamigen eigenen Eintrag</span>
                  <?php endif; ?>
                </td>
                <td class="th-act"><div class="rowactions">
                  <a class="btn-yellow" href="admin_stammdaten.php?t=rettungsmittel&amp;et=<?= (int)$t['id'] ?>#<?= e($anker) ?>-td">Bearbeiten</a>
                  <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-td"
                        data-confirm="Zielklinik löschen?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="td_del">
                    <input type="hidden" name="id" value="<?= (int)$t['id'] ?>">
                    <input type="hidden" name="base_id" value="<?= $bid ?>">
                    <button class="btn-red">Löschen</button>
                  </form>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php $etHier = ($editTd && (int)$editTd['base_id'] === $bid) ? $editTd : null; ?>
          <div class="neu-form">
            <h4><?= $etHier ? 'Zielklinik bearbeiten' : 'Zielklinik hinzufügen' ?></h4>
            <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-td">
              <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
              <input type="hidden" name="id" value="<?= $etHier ? (int)$etHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <?php /* Praefix mit Standortkennung: Dieses Formular steht einmal je
                       Standort auf der Seite (siehe einstellungen.php). */
                    $tdPraefix = 'adtd' . $bid; $ORTSFELDER[] = $tdPraefix; ?>
              <div class="neu-zeile">
                <input type="text" name="name" id="<?= e($tdPraefix) ?>addr" maxlength="190" required
                       placeholder="z. B. Klinikum Kempten" value="<?= e($etHier['name'] ?? '') ?>">
                <button class="btn-primary"><?= $etHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
                <?php if ($etHier): ?><a class="btn-red" href="admin_stammdaten.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
              </div>
              <div class="neu-feld">
                <?php ui_ortsfeld([
                        'praefix' => $tdPraefix, 'feld' => false, 'such' => true,
                        'klasse' => 'loc-inline',
                        'such_hinweis' => 'Lage der Zielklinik (optional)',
                        'lat_name' => 'lat', 'lon_name' => 'lon',
                        'lat' => (string)($etHier['lat'] ?? ''),
                        'lon' => (string)($etHier['lon'] ?? ''),
                    ]); ?>
              </div>
            </form>
          </div>
        </details>

        <details class="stammunter" id="<?= e($anker) ?>-res">
          <summary>Weitere Rettungsmittel<span class="stammzahl"><?= count($resNach[$bid] ?? []) ?></span></summary>
          <table class="data">
            <tbody>
            <?php if (!($resNach[$bid] ?? [])): ?><tr><td class="muted">Noch keine Einträge.</td><td></td></tr><?php endif; ?>
            <?php foreach (($resNach[$bid] ?? []) as $r):
                  $dupP = stammdaten_dup_personal_count('resources', 'name', (string)$r['name']); ?>
              <tr>
                <td><?= e($r['name']) ?>
                  <?php if ($dupP > 0): ?>
                    <br><span class="muted small">⚠ <?= $dupP ?> Konten führen einen
                      gleichnamigen eigenen Eintrag</span>
                  <?php endif; ?>
                </td>
                <td class="th-act"><div class="rowactions">
                  <a class="btn-yellow" href="admin_stammdaten.php?t=rettungsmittel&amp;er=<?= (int)$r['id'] ?>#<?= e($anker) ?>-res">Bearbeiten</a>
                  <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-res"
                        data-confirm="Eintrag löschen?">
                    <?= csrf_field() ?><input type="hidden" name="action" value="res_del">
                    <input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                    <input type="hidden" name="base_id" value="<?= $bid ?>">
                    <button class="btn-red">Löschen</button>
                  </form>
                </div></td>
              </tr>
            <?php endforeach; ?>
            </tbody>
          </table>
          <?php $erHier = ($editRes && (int)$editRes['base_id'] === $bid) ? $editRes : null; ?>
          <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-res" class="inline-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="res_save">
            <input type="hidden" name="id" value="<?= $erHier ? (int)$erHier['id'] : 0 ?>">
            <input type="hidden" name="base_id" value="<?= $bid ?>">
            <input type="text" name="name" maxlength="120" required
                   placeholder="z. B. RTW Kempten" value="<?= e($erHier['name'] ?? '') ?>">
            <button class="btn-primary"><?= $erHier ? 'Änderung speichern' : 'Hinzufügen' ?></button>
            <?php if ($erHier): ?><a class="btn-red" href="admin_stammdaten.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
          </form>
        </details>

        <?php if ($hatLuft): ?>
          <details class="stammunter" id="<?= e($anker) ?>-bw">
            <summary>Bergwacht<span class="stammzahl"><?= count($bwNach[$bid] ?? []) ?></span></summary>
            <p class="muted">Der Block erscheint, weil an diesem Standort ein
               luftgebundenes Rettungsmittel steht — die Fähigkeit kommt nur dort
               vor.</p>
            <table class="data">
              <tbody>
              <?php if (!($bwNach[$bid] ?? [])): ?><tr><td class="muted">Noch keine Bereitschaften.</td><td></td></tr><?php endif; ?>
              <?php foreach (($bwNach[$bid] ?? []) as $w):
                    $dupP = stammdaten_dup_personal_count('bw_units', 'name', (string)$w['name']); ?>
                <tr>
                  <td><?= e($w['name']) ?>
                    <?php if ($dupP > 0): ?>
                      <br><span class="muted small">⚠ <?= $dupP ?> Konten führen einen
                        gleichnamigen eigenen Eintrag</span>
                    <?php endif; ?>
                  </td>
                  <td class="th-act"><div class="rowactions">
                    <a class="btn-yellow" href="admin_stammdaten.php?t=rettungsmittel&amp;ew=<?= (int)$w['id'] ?>#<?= e($anker) ?>-bw">Bearbeiten</a>
                    <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-bw"
                          data-confirm="Bereitschaft löschen?">
                      <?= csrf_field() ?><input type="hidden" name="action" value="bw_del">
                      <input type="hidden" name="id" value="<?= (int)$w['id'] ?>">
                      <input type="hidden" name="base_id" value="<?= $bid ?>">
                      <button class="btn-red">Löschen</button>
                    </form>
                  </div></td>
                </tr>
              <?php endforeach; ?>
              </tbody>
            </table>
            <?php $ewHier = ($editBw && (int)$editBw['base_id'] === $bid) ? $editBw : null; ?>
            <form method="post" action="admin_stammdaten.php?t=rettungsmittel#<?= e($anker) ?>-bw" class="inline-form">
              <?= csrf_field() ?><input type="hidden" name="action" value="bw_save">
              <input type="hidden" name="id" value="<?= $ewHier ? (int)$ewHier['id'] : 0 ?>">
              <input type="hidden" name="base_id" value="<?= $bid ?>">
              <input type="text" name="name" maxlength="120" required
                     placeholder="z. B. Bereitschaft Oberstdorf" value="<?= e($ewHier['name'] ?? '') ?>">
              <button class="btn-primary"><?= $ewHier ? 'Änderung speichern' : 'Bereitschaft hinzufügen' ?></button>
              <?php if ($ewHier): ?><a class="btn-red" href="admin_stammdaten.php?t=rettungsmittel">Abbrechen</a><?php endif; ?>
            </form>
          </details>
        <?php endif; ?>
      </details>
    <?php endforeach; ?>
  <?php endif; ?>

    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/confirm.js') ?>"></script>
<script src="<?= asset('assets/openlocationcode.js') ?>"></script>
<script src="<?= asset('assets/locparse.js') ?>"></script>
<script src="<?= asset('assets/ortsfeld.js') ?>"></script>
<script>
/* Ortsfelder der zentralen Stammdatenpflege (E37/E38). Dieselbe Komponente wie
 * in der Kontoansicht — zentral gepflegte Koordinaten gelten fuer alle, die den
 * Eintrag sehen. */
<?= 'const ORTSFELDER = ' . json_encode($ORTSFELDER) . ';' ?>
ORTSFELDER.forEach(p => EdOrtsfeld.init({ praefix: p, getrennteSuche: true }));
</script>
<script>
/* Den Abschnitt aus dem Anker wieder aufklappen — dieselbe Mechanik wie in
 * einstellungen.php, einschliesslich der VORFAHREN: Die Bloecke sind seit
 * Web 7.0.0 zweistufig verschachtelt, und ein geoeffneter Unterblock in einem
 * geschlossenen Standort ist nicht zu sehen. */
(function () {
  var h = (location.hash || '').replace(/^#/, '');
  if (!h) { return; }
  var el = document.getElementById(h);
  if (!el) { return; }
  for (var p = el; p; p = p.parentElement) {
    if (p.tagName === 'DETAILS') { p.open = true; }
  }
  el.scrollIntoView({ block: 'start' });
  var f = el.querySelector('.focus-target')
       || el.querySelector('input[type=text]');
  if (f) { f.focus({ preventScroll: true }); }
})();

/* Rollen- und Fähigkeitshaken zur Art passend ein- und ausblenden (E3).
 * Rein anzeigend — was zulässig ist, entscheidet der Server in 'veh_save'.
 * Ohne gewählte Art bleiben beide Bereiche verborgen (Web 7.0.0): Die Art ist
 * nicht mehr vorbelegt. */
document.querySelectorAll('form.ac-form').forEach(function (f) {
  function anpassen() {
    var gewaehlt = f.querySelector('.vehkind-radio:checked');
    var kind = gewaehlt ? gewaehlt.value : null;
    f.querySelectorAll('.rollehaken').forEach(function (lab) {
      var k = lab.dataset.kind;
      var passt = kind !== null && (k === 'both' || k === kind);
      lab.hidden = !passt;
      if (!passt) { lab.querySelector('input').checked = false; }
    });
    var caps = f.querySelector('.vehcaps-zeile');
    if (caps) {
      caps.hidden = (kind !== 'air');
      if (kind !== 'air') {
        caps.querySelectorAll('input').forEach(function (i) { i.checked = false; });
      }
    }
    var rollen = f.querySelector('.rollen-zeile');
    if (rollen) { rollen.hidden = (kind === null); }
  }
  f.querySelectorAll('.vehkind-radio').forEach(function (r) {
    r.addEventListener('change', anpassen);
  });
  anpassen();
});
</script>
</body>
</html>
