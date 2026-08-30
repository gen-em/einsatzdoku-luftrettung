<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/diensttag_lib.php';   // Rollenkatalog, Artsymbole
require_once __DIR__ . '/validate_lib.php';   // pruef_ortspaar()
/* Zeile und Formular der Stammdatenpflege — dieselben Bausteine wie in der
 * Kontoansicht (einstellungen.php). Bis Web 9.9.0 stand dieses Markup in
 * beiden Dateien; seit O9c steht es einmal (stammdaten_ui.php). */
require_once __DIR__ . '/stammdaten_ui.php';

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
ui_seite_start(['titel' => 'Stammdaten systemweit']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_stammdaten']); ?>

  <?php /* EIN MENUEPUNKT, ZWEI REITER (Mockup 41). Bis Web 9.9.0 standen
           „Standorte systemweit" und „Rettungsmittel systemweit" als zwei
           Eintraege in der Leiste — zwei Punkte fuer eine Sache, die man
           zusammen pflegt. Der Wechsel ist jetzt eine Segmentwahl in der
           Titelzeile, wie die Artwahl im Zeitraum (E-P3-37). */ ?>
  <?php ui_titelzeile([
      'titel' => 'Stammdaten systemweit',
      'aktionen' => '<form method="get" class="segment-art">'
          . ui_segment_markup([
              'name' => 't', 'id' => 'sdtab', 'wert' => $tab,
              'optionen' => ['standorte' => 'Standorte', 'rettungsmittel' => 'Rettungsmittel'],
            ]) . '</form>',
  ]); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <p class="seiten-erklaerung">Diese Einträge gelten für <strong>alle Konten</strong> —
     sichtbar werden sie einer NutzerIn aber erst, wenn sie den zugehörigen Standort
     in ihren Einstellungen auswählt; die Auswahl ist ihre Sache. Der Standort ist
     dabei der Anker: Rettungsmittel, Besatzung, Zielkliniken, weitere
     Rettungsmittel und Bergwacht gehören zu genau einem. Änderungen wirken nur auf
     <strong>neue</strong> Diensttage — dokumentierte haben ihre Angaben beim
     Anlegen eingefroren.</p>

<?php if ($tab === 'standorte'): ?>

  <?php ui_karte_start(['titel' => 'Standorte', 'zahl' => count($bases), 'id' => 'standorte']); ?>
    <p class="feld-hinweis">Was an einem Standort hängt, steht im Reiter
       <a href="admin_stammdaten.php?t=rettungsmittel">Rettungsmittel</a>.</p>
    <?php if (!$bases): ?>
      <p class="feld-hinweis">Noch kein systemweiter Standort.</p>
    <?php endif; ?>
    <?php foreach ($bases as $b):
      $bid = (int)$b['id'];
      $anz = $anzahlJeBase($bid);
      $ub  = $ubZahl[$bid] ?? 0;
      /* WEICHER HINWEIS AUF GLEICHNAMIGE EIGENE EINTRAEGE (F-P3-AO). Die
         fuenf uebrigen Listen zeigen ihn seit jeher, die Standorteliste nicht
         — ohne Begruendung im Code. Ein systemweiter Standort, den bereits ein
         Dutzend Konten unter demselben Namen selbst angelegt hat, entsteht
         damit ohne jeden Hinweis, und danach steht der Name zweimal in der
         Auswahlliste. */
      $dupP = stammdaten_dup_personal_count('bases', 'name', (string)$b['name']);
      $klein = [];
      $klein[] = ($b['lat'] !== null && $b['lon'] !== null)
          ? $b['lat'] . ', ' . $b['lon'] : 'ohne Lage';
      $klein[] = $anz === 1 ? '1 Eintrag daran' : $anz . ' Einträge daran';
      $klein[] = $ub === 1 ? '1 Konto hat ihn gewählt' : $ub . ' Konten haben ihn gewählt';
      if ($dupP > 0) {
          $klein[] = $dupP === 1
              ? '1 Konto führt einen gleichnamigen eigenen Eintrag'
              : $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag';
      }
      sd_zeile([
          'seite' => 'admin_stammdaten.php?t=standorte',
          'name'  => (string)$b['name'],
          'klein' => implode(' · ', $klein),
          'anker' => 'standorte', 'praefix' => 'adbase', 'id' => $bid, 'base_id' => $bid,
          'del_action' => 'base_del',
          'del_frage' => 'Standort „' . $b['name'] . '“ systemweit löschen? '
              . ($anz > 0
                  ? ($anz === 1 ? 'Ein systemweiter Stammdatensatz' : $anz . ' systemweite Stammdatensätze')
                    . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere '
                    . 'Rettungsmittel, Bergwacht) werden mitgelöscht. '
                  : 'Es hängen keine systemweiten Stammdaten daran. ')
              . ($ub > 0
                  ? 'Er verschwindet aus den Auswahllisten von '
                    . ($ub === 1 ? 'einem Konto' : $ub . ' Konten') . '. '
                  : '')
              . 'Bereits dokumentierte Diensttage bleiben unverändert.',
          'bearbeiten_href' => 'admin_stammdaten.php?t=standorte&eb=' . $bid . '#standorte',
          'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
      ]);
    endforeach; ?>

    <div class="listen-form">
      <h3 class="listen-form-titel"><?= $editBase ? 'Standort bearbeiten' : 'Standort hinzufügen' ?></h3>
      <form method="post" action="admin_stammdaten.php?t=standorte#standorte">
        <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
        <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
        <div class="listen-form-felder">
          <?php ui_feld(['label' => 'Name', 'name' => 'name', 'id' => 'adbase-name',
                         'klasse' => 'focus-target', 'pflicht' => true,
                         'platzhalter' => 'z. B. Standort Kempten',
                         'wert' => (string)($editBase['name'] ?? ''),
                         'attr' => ' maxlength="120"']); ?>
          <?php /* Dieselbe Ortsfeld-Komponente wie in der Kontoansicht und am
                   Einsatz (assets/ortsfeld.js). Die Kennung `<praefix>addr`
                   gehört dem LAGE-Suchfeld, nicht dem Namen (F-P3-AI). */
                $ORTSFELDER[] = 'adbase'; ?>
          <?php ui_ortsfeld([
                  'praefix' => 'adbase', 'feld' => false, 'such' => true,
                  'klasse' => 'loc-inline',
                  'such_hinweis' => 'Lage (optional)',
                  'lat_name' => 'lat', 'lon_name' => 'lon',
                  'lat' => (string)($editBase['lat'] ?? ''),
                  'lon' => (string)($editBase['lon'] ?? ''),
              ]); ?>
          <p class="feld-klein">Wird als Abfahrtsort neuer Diensttage übernommen.</p>
        </div>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => $editBase ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
          <?php if ($editBase): ?>
            <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                          'href' => 'admin_stammdaten.php?t=standorte']) ?>
          <?php endif; ?>
        </div>
      </form>
    </div>
  <?php ui_karte_ende(); ?>

<?php else: ?>

  <?php if (!$bases): ?>
    <?= ui_meldung_markup('warn', 'Es gibt noch keinen systemweiten Standort. '
        . 'Rettungsmittel, Besatzung und Zielkliniken hängen an einem Standort — '
        . 'ohne ihn gibt es nichts anzulegen.', '',
        ui_knopf(['text' => 'Zu den Standorten', 'art' => 'neutral',
                  'href' => 'admin_stammdaten.php?t=standorte'])) ?>
  <?php endif; ?>

  <?php foreach ($bases as $b):
    $bid = (int)$b['id'];
    $vehListe = $vehNach[$bid] ?? [];
    $hatLuft = false;
    foreach ($vehListe as $v) { if ($v['kind'] === 'air') { $hatLuft = true; break; } }
    $anker = 'sd-' . $bid;
    $rollenHier = $rollenAmStandort($bid);
    $seite = 'admin_stammdaten.php?t=rettungsmittel';
  ?>
    <?php ui_karte_start(['titel' => (string)$b['name'], 'id' => $anker, 'zu' => true,
                          'zahl' => count($vehListe) . ' Rettungsmittel']); ?>

      <?php /* ---- Rettungsmittel ------------------------------------------ */ ?>
      <section class="sd-liste" id="<?= e($anker) ?>-veh">
        <h3 class="sd-titel">Rettungsmittel <span class="sd-zahl"><?= count($vehListe) ?></span></h3>
        <p class="feld-hinweis">Die Art entscheidet über Besatzungsrollen und die im
           Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht) gibt es
           nur luftgebunden.</p>
        <?php if (!$vehListe): ?>
          <p class="feld-hinweis">Noch keine Rettungsmittel an diesem Standort.</p>
        <?php endif; ?>
        <?php foreach ($vehListe as $v):
          $vid = (int)$v['id'];
          $rollenTxt = array_map('crew_role_label', $vehRollen[$vid] ?? []);
          $capsTxt = array_map(static fn(string $c): string => VEHICLE_CAPABILITIES[$c] ?? $c,
                               $vehCaps[$vid] ?? []);
          $dupP = stammdaten_dup_personal_count('vehicles', 'name', (string)$v['name']);
          $klein = ($rollenTxt ? implode(', ', $rollenTxt) : 'keine Rollen')
                 . ($capsTxt ? ' · ' . implode(', ', $capsTxt) : '')
                 . ($dupP > 0 ? ' · ' . $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag' : '');
          sd_zeile([
              'seite' => $seite,
              'name'  => (string)$v['name'], 'klein' => $klein,
              'anker' => $anker . '-veh', 'praefix' => 'veh', 'id' => $vid, 'base_id' => $bid,
              'del_action' => 'veh_del',
              'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ systemweit löschen? '
                           . 'Dokumentierte Diensttage bleiben unverändert.',
              'bearbeiten_href' => $seite . '&ev=' . $vid . '#' . $anker . '-veh',
              'plaketten' => ui_artzeichen((string)$v['kind'])
                           . ($dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : ''),
          ]);
        endforeach; ?>
        <?php $evHier = ($editVeh && (int)$editVeh['base_id'] === $bid) ? $editVeh : null;
              $evRollen = $evHier ? ($vehRollen[(int)$evHier['id']] ?? []) : [];
              $evCaps   = $evHier ? ($vehCaps[(int)$evHier['id']] ?? []) : []; ?>
        <div class="listen-form">
          <h3 class="listen-form-titel"><?= $evHier ? 'Rettungsmittel bearbeiten' : 'Rettungsmittel hinzufügen' ?></h3>
          <form method="post" action="<?= e($seite . '#' . $anker . '-veh') ?>" class="ac-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="veh_save">
            <input type="hidden" name="id" value="<?= $evHier ? (int)$evHier['id'] : 0 ?>">
            <input type="hidden" name="base_id" value="<?= $bid ?>">
            <div class="listen-form-felder">
              <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'name',
                             'id' => 'advehname-' . $bid, 'pflicht' => true,
                             'platzhalter' => 'z. B. Christoph 17 oder NEF Kempten 1',
                             'wert' => (string)($evHier['name'] ?? ''),
                             'attr' => ' maxlength="64"']); ?>
              <?php /* DIE ART IST NICHT VORBELEGT (Web 7.0.0): „luftgebunden"
                       stand von selbst da, und an einem NEF-Standort war das
                       die falsche Vorgabe, die niemand bemerkt. */ ?>
              <div class="feld">
                <span class="feld-label">Art <span class="feld-pflicht" aria-hidden="true">*</span></span>
                <span class="vehkind">
                  <label><input type="radio" name="kind" value="air" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'air') ? 'checked' : '' ?>> luftgebunden</label>
                  <label><input type="radio" name="kind" value="ground" class="vehkind-radio"
                         <?= ($evHier && $evHier['kind'] === 'ground') ? 'checked' : '' ?>> bodengebunden</label>
                </span>
              </div>
            </div>
            <div class="feld rollen-zeile">
              <span class="feld-label">Besatzungsrollen <span class="feld-klein-inline">(optional)</span></span>
              <span class="acroles">
                <?php foreach (CREW_ROLES as $rc => $rr): ?>
                  <label class="rollehaken" data-kind="<?= e($rr['kind']) ?>">
                    <input type="checkbox" name="roles[]" value="<?= e($rc) ?>"
                           <?= in_array($rc, $evRollen, true) ? 'checked' : '' ?>>
                    <?= e($rr['label']) ?></label>
                <?php endforeach; ?>
              </span>
            </div>
            <div class="feld vehcaps-zeile">
              <span class="feld-label">Fähigkeiten <span class="feld-klein-inline">(nur luftgebunden)</span></span>
              <span class="acroles vehcaps">
                <?php foreach (VEHICLE_CAPABILITIES as $ck => $cl): ?>
                  <label><input type="checkbox" name="caps[]" value="<?= e($ck) ?>"
                         <?= in_array($ck, $evCaps, true) ? 'checked' : '' ?>>
                    <?= e($cl) ?></label>
                <?php endforeach; ?>
              </span>
            </div>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => $evHier ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
              <?php if ($evHier): ?>
                <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'href' => $seite]) ?>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </section>

      <?php /* ---- Besatzung: nur die Rollen, die es hier gibt -------------- */ ?>
      <section class="sd-liste" id="<?= e($anker) ?>-crew">
        <h3 class="sd-titel">Besatzung <span class="sd-zahl"><?= count($crewNach[$bid] ?? []) ?></span></h3>
        <p class="feld-hinweis">Vorschläge für die Besatzungsfelder, je Rolle. Freitext
           bleibt überall möglich — wer aushilft, muss nicht erst hier stehen.</p>
        <?php if (!$rollenHier): ?>
          <p class="feld-hinweis">Noch keine Rolle an diesem Standort. Rollen entstehen
             am Rettungsmittel: Trage oben eines ein und hake an, welche Rollen es
             führt.</p>
        <?php endif; ?>
        <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
          <h4 class="sd-rolle"><?= e($rr['label']) ?></h4>
          <?php $any = false;
                foreach (($crewNach[$bid] ?? []) as $c):
                    if ($c['role_code'] !== $rk) { continue; }
                    $any = true;
                    $dupP = stammdaten_dup_personal_count('crew_presets', 'name', (string)$c['name']);
                    sd_zeile([
                        'seite' => $seite,
                        'name'  => (string)$c['name'],
                        'klein' => $dupP > 0
                            ? $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag' : '',
                        'anker' => $anker . '-crew', 'praefix' => 'crew', 'id' => (int)$c['id'],
                        'base_id' => $bid,
                        'del_action' => 'crew_del',
                        'del_frage' => 'Eintrag „' . $c['name'] . '“ systemweit löschen?',
                        'bearbeiten_href' => $seite . '&ec=' . (int)$c['id'] . '#' . $anker . '-crew',
                        'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
                    ]);
                endforeach;
                if (!$any): ?>
            <p class="feld-hinweis">Noch keine Einträge.</p>
          <?php endif; ?>
          <?php $ecHier = ($editCrew && (int)$editCrew['base_id'] === $bid
                           && $editCrew['role_code'] === $rk) ? $editCrew : null;
                sd_form([
                    'seite' => $seite,
                    'anker' => $anker . '-crew', 'action' => 'crew_save', 'base_id' => $bid,
                    'bearbeitet' => $ecHier, 'label' => $rr['label'],
                    'platzhalter' => 'z. B. Nachname',
                    'felder_versteckt' => '<input type="hidden" name="role_code" value="'
                                        . e($rk) . '">',
                    'titel_neu' => 'Eintrag hinzufügen',
                    'titel_bearbeiten' => 'Eintrag bearbeiten',
                ]); ?>
        <?php endforeach; ?>
      </section>

      <?php /* ---- Zielkliniken --------------------------------------------- */ ?>
      <section class="sd-liste" id="<?= e($anker) ?>-td">
        <h3 class="sd-titel">Zielkliniken <span class="sd-zahl"><?= count($tdNach[$bid] ?? []) ?></span></h3>
        <p class="feld-hinweis">Vorschläge für das Transportziel. Mit Lage lässt sich
           die Luftlinie zum Einsatzort zeichnen.</p>
        <?php if (!($tdNach[$bid] ?? [])): ?>
          <p class="feld-hinweis">Noch keine Zielkliniken.</p>
        <?php endif; ?>
        <?php foreach (($tdNach[$bid] ?? []) as $t):
              $dupP = stammdaten_dup_personal_count('transport_dests', 'name', (string)$t['name']);
              $klein = ($t['lat'] !== null && $t['lon'] !== null)
                  ? $t['lat'] . ', ' . $t['lon'] : 'ohne Lage';
              if ($dupP > 0) {
                  $klein .= ' · ' . $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag';
              }
              sd_zeile([
                  'seite' => $seite,
                  'name'  => (string)$t['name'], 'klein' => $klein,
                  'anker' => $anker . '-td', 'praefix' => 'td', 'id' => (int)$t['id'],
                  'base_id' => $bid,
                  'del_action' => 'td_del',
                  'del_frage' => 'Zielklinik „' . $t['name'] . '“ systemweit löschen?',
                  'bearbeiten_href' => $seite . '&et=' . (int)$t['id'] . '#' . $anker . '-td',
                  'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
              ]);
        endforeach; ?>
        <?php $etHier = ($editTd && (int)$editTd['base_id'] === $bid) ? $editTd : null;
              $tdPraefix = 'adtd' . $bid; $ORTSFELDER[] = $tdPraefix; ?>
        <div class="listen-form">
          <h3 class="listen-form-titel"><?= $etHier ? 'Zielklinik bearbeiten' : 'Zielklinik hinzufügen' ?></h3>
          <form method="post" action="<?= e($seite . '#' . $anker . '-td') ?>">
            <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
            <input type="hidden" name="id" value="<?= $etHier ? (int)$etHier['id'] : 0 ?>">
            <input type="hidden" name="base_id" value="<?= $bid ?>">
            <div class="listen-form-felder">
              <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'name',
                             'id' => $tdPraefix . '-name', 'pflicht' => true,
                             'platzhalter' => 'z. B. Klinikum Kempten',
                             'wert' => (string)($etHier['name'] ?? ''),
                             'attr' => ' maxlength="120"']); ?>
              <?php ui_ortsfeld([
                      'praefix' => $tdPraefix, 'feld' => false, 'such' => true,
                      'klasse' => 'loc-inline',
                      'such_hinweis' => 'Lage (optional)',
                      'lat_name' => 'lat', 'lon_name' => 'lon',
                      'lat' => (string)($etHier['lat'] ?? ''),
                      'lon' => (string)($etHier['lon'] ?? ''),
                  ]); ?>
            </div>
            <div class="listen-form-fuss">
              <?= ui_knopf(['text' => $etHier ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer']) ?>
              <?php if ($etHier): ?>
                <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'href' => $seite]) ?>
              <?php endif; ?>
            </div>
          </form>
        </div>
      </section>

      <?php /* ---- Weitere Rettungsmittel ----------------------------------- */ ?>
      <section class="sd-liste" id="<?= e($anker) ?>-res">
        <h3 class="sd-titel">Weitere Rettungsmittel <span class="sd-zahl"><?= count($resNach[$bid] ?? []) ?></span></h3>
        <p class="feld-hinweis">Vorschläge für das Feld „Weitere Rettungsmittel" im
           Einsatz (RTW, NEF, RTH …).</p>
        <?php if (!($resNach[$bid] ?? [])): ?>
          <p class="feld-hinweis">Noch keine Einträge.</p>
        <?php endif; ?>
        <?php foreach (($resNach[$bid] ?? []) as $r):
              $dupP = stammdaten_dup_personal_count('resources', 'name', (string)$r['name']);
              sd_zeile([
                  'seite' => $seite,
                  'name'  => (string)$r['name'],
                  'klein' => $dupP > 0
                      ? $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag' : '',
                  'anker' => $anker . '-res', 'praefix' => 'res', 'id' => (int)$r['id'],
                  'base_id' => $bid,
                  'del_action' => 'res_del',
                  'del_frage' => 'Eintrag „' . $r['name'] . '“ systemweit löschen?',
                  'bearbeiten_href' => $seite . '&er=' . (int)$r['id'] . '#' . $anker . '-res',
                  'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
              ]);
        endforeach; ?>
        <?php $erHier = ($editRes && (int)$editRes['base_id'] === $bid) ? $editRes : null;
              sd_form([
                  'seite' => $seite,
                  'anker' => $anker . '-res', 'action' => 'res_save', 'base_id' => $bid,
                  'bearbeitet' => $erHier, 'label' => 'Bezeichnung',
                  'platzhalter' => 'z. B. RTW Kempten',
                  'titel_neu' => 'Rettungsmittel hinzufügen',
                  'titel_bearbeiten' => 'Eintrag bearbeiten',
              ]); ?>
      </section>

      <?php /* ---- Bergwacht: nur bei luftgebundenem Rettungsmittel ---------- */ ?>
      <?php if ($hatLuft): ?>
        <section class="sd-liste" id="<?= e($anker) ?>-bw">
          <h3 class="sd-titel">Bergwacht <span class="sd-zahl"><?= count($bwNach[$bid] ?? []) ?></span></h3>
          <p class="feld-hinweis">Bereitschaften für das Feld „Bergwacht" im Einsatz.
             Der Abschnitt erscheint, weil an diesem Standort ein luftgebundenes
             Rettungsmittel steht — die Fähigkeit kommt nur dort vor.</p>
          <?php if (!($bwNach[$bid] ?? [])): ?>
            <p class="feld-hinweis">Noch keine Bereitschaften.</p>
          <?php endif; ?>
          <?php foreach (($bwNach[$bid] ?? []) as $w):
                $dupP = stammdaten_dup_personal_count('bw_units', 'name', (string)$w['name']);
                sd_zeile([
                    'seite' => $seite,
                    'name'  => (string)$w['name'],
                    'klein' => $dupP > 0
                        ? $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag' : '',
                    'anker' => $anker . '-bw', 'praefix' => 'bw', 'id' => (int)$w['id'],
                    'base_id' => $bid,
                    'del_action' => 'bw_del',
                    'del_frage' => 'Bereitschaft „' . $w['name'] . '“ systemweit löschen?',
                    'bearbeiten_href' => $seite . '&ew=' . (int)$w['id'] . '#' . $anker . '-bw',
                    'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
                ]);
          endforeach; ?>
          <?php $ewHier = ($editBw && (int)$editBw['base_id'] === $bid) ? $editBw : null;
                sd_form([
                    'seite' => $seite,
                    'anker' => $anker . '-bw', 'action' => 'bw_save', 'base_id' => $bid,
                    'bearbeitet' => $ewHier, 'label' => 'Bereitschaft',
                    'platzhalter' => 'z. B. Bereitschaft Oberstdorf',
                    'titel_neu' => 'Bereitschaft hinzufügen',
                    'titel_bearbeiten' => 'Bereitschaft bearbeiten',
                ]); ?>
        </section>
      <?php endif; ?>
    <?php ui_karte_ende(true); ?>
  <?php endforeach; ?>

<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php /* confirm.js kommt aus ui_geruest_ende() (ui.php) — eine zweite Einbindung
         haette den Rueckfragedialog doppelt geoeffnet. */ ?>
<script src="<?= asset('assets/openlocationcode.js') ?>"></script>
<script src="<?= asset('assets/locparse.js') ?>"></script>
<script src="<?= asset('assets/ortsfeld.js') ?>"></script>
<script>
/* Ortsfelder der systemweiten Stammdatenpflege (E37/E38). Dieselbe Komponente
 * wie in der Kontoansicht — systemweit gepflegte Koordinaten gelten fuer alle,
 * die den Eintrag sehen. */
<?= 'const ORTSFELDER = ' . json_encode($ORTSFELDER) . ';' ?>
ORTSFELDER.forEach(p => EdOrtsfeld.init({ praefix: p, getrennteSuche: true }));
</script>
<script>
/* Die Reiterwahl schickt das Formular ab, sobald sie sich aendert — sonst
   braeuchte eine Segmentwahl, die eine Seite wechselt, einen zweiten Klick
   auf einen Knopf, den es im Mockup nicht gibt (dasselbe Muster wie die
   Artwahl im Zeitraum). */
document.querySelectorAll('.segment-art input[type=radio]').forEach(function (r) {
  r.addEventListener('change', function () { r.form.submit(); });
});

/* Den Abschnitt aus dem Anker wieder aufklappen — einschliesslich der
 * VORFAHREN: Die Bloecke sind zweistufig verschachtelt, und ein geoeffneter
 * Unterblock in einem geschlossenen Standort ist nicht zu sehen. */
(function () {
  var h = (location.hash || '').replace(/^#/, '');
  if (!h) { return; }
  var el = document.getElementById(h);
  if (!el) { return; }
  for (var p = el; p; p = p.parentElement) {
    if (p.tagName === 'DETAILS') { p.open = true; }
  }
  el.scrollIntoView({ block: 'start' });
  var f = el.querySelector('.focus-target') || el.querySelector('input[type=text]');
  if (f) { f.focus({ preventScroll: true }); }
})();

/* Rollen- und Faehigkeitshaken zur Art passend ein- und ausblenden (E3).
 * Rein anzeigend — was zulaessig ist, entscheidet der Server in 'veh_save'. */
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
<?php ui_seite_ende(); ?>
