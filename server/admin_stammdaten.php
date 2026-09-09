<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/diensttag_lib.php';   // Rollenkatalog, Artsymbole
require_once __DIR__ . '/validate_lib.php';   // pruef_ortspaar(), pruef_rettungsmittel()
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
 * DER STANDORTBEZUG IST VERBINDLICH (E15) — MIT EINER AUSNAHME SEIT WEB 16.0.0.
 * Jede Zielklinik, jede Besatzungs-Vorbelegung und jede Bergwacht-Bereitschaft
 * gehoert genau einem Standort; ohne ihn erschiene der Eintrag in keiner
 * Auswahlliste. Bei den RETTUNGSMITTELN gilt das nur noch fuer den Typ
 * „Standard": `vehicles.base_id` ist NULL-faehig, und die drei anderen Typen
 * (Bergwacht, Veranstaltung, Sonstiges) duerfen ohne Standort bestehen
 * (E-S9-09). Sie haben dann keine Vorschlagslisten — die haengen am Standort —
 * und stehen in der Standortliste unter „Ohne Standort". Die Regel steht in
 * `pruef_rettungsmittel()` (validate_lib.php), nicht im Schema: Die Datenbank
 * kann eine Pflicht nicht von einer zweiten Spalte abhaengig machen, ohne den
 * Fehler an der Pruefschicht vorbei zu melden.
 */

/** Zentralen Standort pruefen: Er muss existieren UND zentral sein. */
function admin_base_id(?int $id): ?int {
    if ($id === null || $id <= 0) { return null; }
    $q = db()->prepare('SELECT id FROM bases WHERE id = ? AND user_id IS NULL');
    $q->execute([$id]);
    return $q->fetchColumn() !== false ? $id : null;
}
// Duplikat-Helfer stammdaten_dup_global()/stammdaten_dup_personal_count() -> db.php

/* EINE LISTE UND EINE SEITE JE STANDORT (S9/AP5-4, E-S9-18).
 *
 * Bis Web 16.3.0 hatte diese Seite ZWEI REITER — „Standorte systemweit" und
 * „Rettungsmittel systemweit" —, und der zweite zeigte ALLE Standorte
 * untereinander als zugeklappte Karten. Die Kontoansicht hat dieselbe Form am
 * 08.09.2026 abgelegt (S9/AP5-1 und -2); zwei Ansichten desselben Bestands,
 * die sich verschieden bedienen lassen, sind genau das, was `stammdaten_ui.php`
 * seit O9c verhindern soll. Also: dieselbe Gliederung hier.
 *
 *   `t=standorte`            die Liste; ein Klick fuehrt auf
 *   `t=standort&s=<id>`      die Seite EINES Standorts, sechs Karten
 *
 * Der alte Name `rettungsmittel` bleibt als WEICHE stehen — er steht in
 * Lesezeichen und in aelteren Fassungen der Dokumentation; wer ihn aufruft,
 * landet auf der Liste und ist einen Klick von dem entfernt, was er suchte.
 * Dieselbe Weiche wie in `einstellungen.php`.
 */
$tab = $_GET['t'] ?? 'standorte';
if ($tab === 'rettungsmittel') { $tab = 'standorte'; }
if (!in_array($tab, ['standorte', 'standort'], true)) { $tab = 'standorte'; }

/* GEPRUEFT WIRD HIER OBEN, NICHT IM MARKUP: Eine Umleitung braucht
 * Kopfzeilen, und die sind fort, sobald das Geruest die erste Zeile
 * geschrieben hat. Derselbe Grund und dasselbe Vorgehen wie in
 * `einstellungen.php`. */
$seiteBase = 0;
if ($tab === 'standort') {
    $seiteBase = (int)admin_base_id((int)($_GET['s'] ?? 0));
    if ($seiteBase === 0) {
        header('Location: admin_stammdaten.php?t=standorte');
        exit;
    }
}

$notice = null; $error = null;
/* Ein Fehler aus einem der fuenf Dialog-Schreibwege bleibt im Dialog und wird
 * nicht umgeleitet (E-S9-19). Begruendung in `einstellungen.php` bei
 * `$dlgFehler`; hier steht dieselbe Sache, weil hier dieselben Dialoge
 * stehen. */
$dlgFehler = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action = $_POST['action'] ?? '';
    $postBase = admin_base_id(isset($_POST['base_id']) ? (int)$_POST['base_id'] : null);
    /* DIE KENNUNG DER GESCHRIEBENEN ZEILE (S9/AP5-4, E-S9-19). Sie wird
     * gebraucht, damit die Umleitung auf `#veh-7` zeigt und `:target` die
     * neue Zeile faerbt. Anders als im Konto braucht es hier keinen
     * Dublettenwaechter um `lastInsertId()`: Diese Seite schreibt mit
     * `INSERT` und nicht mit `INSERT IGNORE`, und die Dublette ist eine
     * Zeile darueber schon abgefangen (`stammdaten_dup_global()`) — die
     * UNIQUE-Schluessel greifen bei `user_id IS NULL` gar nicht. */
    $zielId = null;
    /* Die Kennung eines NEU angelegten Standorts — die Umleitung fuehrt auf
     * seine Seite und nicht auf die Liste, aus der er entstanden ist. */
    $baseNeu = null;

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
            /* „Standort anlegen" landet auf der neuen Seite (E-S9-19) — sie ist
               die Bestaetigung und zugleich der Ort, an dem als Naechstes etwas
               zu tun ist. */
            $baseNeu = (int)db()->lastInsertId();
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
        $vid = (int)($_POST['id'] ?? 0);
        /* DIESELBE PRUEFUNG WIE IM KONTO (Web 16.0.0, E-S9-09). Bis Web 15.9.0
         * standen die Regeln hier als Kopie der Kontoansicht — dieselben Saetze,
         * nur kuerzer formuliert, was allein schon ein Fehler war: Zwei
         * Meldungen fuer dieselbe Lage. Mit `pruef_rettungsmittel()` gibt es
         * eine Fassung; abweichend bleibt nur die Dublettenpruefung, denn
         * „zentral hinterlegt" heisst hier etwas anderes als im Konto. */
        $geprueft = pruef_rettungsmittel([
            'name'    => $_POST['name'] ?? null,
            'kurz'    => $_POST['kurz'] ?? null,
            'typ'     => $_POST['typ']  ?? null,
            'kind'    => $_POST['kind'] ?? null,
            /* Der Standort kommt aus dem Auswahlfeld des Dialogs (S9/AP5-4);
             * „Ohne Standort" ist dessen erster Eintrag mit dem Wert 0, und
             * `admin_base_id()` macht daraus null. Ob das zum Typ passt,
             * entscheidet die Pruefschicht. Bis Web 16.3.0 stand hier ein
             * Haken, der die verborgene Kennung der Standortkarte schlug. */
            'base_id' => $postBase,
            'roles'   => $_POST['roles'] ?? [],
            'caps'    => $_POST['caps']  ?? [],
        ]);
        $rm = $geprueft['daten'];
        if ($rm === null) {
            $error = reset($geprueft['fehler']) ?: 'Das Rettungsmittel konnte nicht gespeichert werden.';
        } elseif (stammdaten_dup_global('vehicles', 'name', $rm['name'], null, null, $vid)) {
            $error = '„' . $rm['name'] . '“ ist bereits zentral hinterlegt.';
        } else {
            $rollen = $rm['roles'];
            $caps   = $rm['caps'];
            $pdo = db();
            $pdo->beginTransaction();
            try {
                if ($vid > 0) {
                    $pdo->prepare('UPDATE vehicles SET name = ?, kurz = ?, kind = ?, typ = ?, base_id = ?
                                   WHERE id = ? AND user_id IS NULL')
                        ->execute([$rm['name'], $rm['kurz'], $rm['kind'], $rm['typ'], $rm['base_id'], $vid]);
                } else {
                    $pdo->prepare('INSERT INTO vehicles (user_id, base_id, name, kurz, kind, typ)
                                   VALUES (NULL,?,?,?,?,?)')
                        ->execute([$rm['base_id'], $rm['name'], $rm['kurz'], $rm['kind'], $rm['typ']]);
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
                $zielId = $vid;
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
        /* DER SCHLUESSEL HEISST `role` — UND HIESS ES HIER SEIT WEB 9.10.0
         * NICHT (Backlog Nr. 163, behoben S9/AP5-4). Das Formular schickte
         * `role_code`, dieser Zweig las `role`: `$role` war immer leer, die
         * Bedingung darunter schlug jedes Mal an, und die Verwaltung meldete
         * „Bitte Rolle und Namen angeben." — bei ausgefuellter Rolle und
         * ausgefuelltem Namen. Anlegen und Aendern einer systemweiten
         * Besatzungs-Vorbelegung waren damit zwei Jahre lang unmoeglich.
         *
         * Gefunden beim Umbau auf die Dialoge, nicht von einem Pruefmittel:
         * Kein Bild zeigt eine Fehlermeldung, die nur nach einem Klick
         * erscheint, und der Bilderlauf klickt nicht. Die Klickprobe fuhr
         * diesen Weg bis dahin nicht. Sie tut es jetzt (`wege/ap5.mjs`).
         *
         * Der Dialog schickt `role`, wie die Kontoansicht seit jeher — der
         * gemeinsame Baustein hat damit EINEN Namen fuer diese Sache. */
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
            /* Die Rolle wird mitgeschrieben: Sie ist im Dialog ein Feld
               (S9/AP5-4), und ohne diese Spalte taete eine Rollenaenderung
               wortlos nichts. */
            db()->prepare('UPDATE crew_presets SET name = ?, role_code = ?
                           WHERE id = ? AND user_id IS NULL')
                ->execute([$n, $role, $cid]);
            $zielId = $cid;
        } else {
            db()->prepare('INSERT INTO crew_presets (user_id, base_id, role_code, name)
                           VALUES (NULL,?,?,?)')->execute([$postBase, $role, $n]);
            $zielId = (int)db()->lastInsertId();
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
            $zielId = $wid;
        } else {
            db()->prepare('INSERT INTO resources (user_id, base_id, name) VALUES (NULL,?,?)')
                ->execute([$postBase, $n]);
            $zielId = (int)db()->lastInsertId();
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
            $zielId = $wid;
        } else {
            db()->prepare('INSERT INTO bw_units (user_id, base_id, name) VALUES (NULL,?,?)')
                ->execute([$postBase, $n]);
            $zielId = (int)db()->lastInsertId();
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
            $zielId = $tid;
        } else {
            db()->prepare('INSERT INTO transport_dests (user_id, base_id, name, lat, lon)
                           VALUES (NULL,?,?,?,?)')->execute([$postBase, $n, $lat, $lon]);
            $zielId = (int)db()->lastInsertId();
        }
    }
    if ($action === 'td_del') {
        db()->prepare('DELETE FROM transport_dests WHERE id = ? AND user_id IS NULL')
            ->execute([(int)($_POST['id'] ?? 0)]);
        $notice = 'Zielklinik gelöscht.';
    }

    /* Ein Fehler aus einem der fuenf Dialog-Schreibwege bleibt im Dialog
     * (E-S9-19) und verlaesst dafuer `$error` — sonst leitete die Weiche
     * unten doch um. Dieselbe Stelle und dieselbe Begruendung wie in
     * `einstellungen.php`. */
    $dlgVon = [
        'veh_save' => 'dlg-veh', 'crew_save' => 'dlg-crew', 'res_save' => 'dlg-res',
        'bw_save'  => 'dlg-bw',  'td_save'   => 'dlg-td',
    ][$action] ?? null;
    if ($error !== null && $dlgVon !== null) {
        $dlgFehler = ['dialog' => $dlgVon, 'meldung' => $error, 'werte' => $_POST];
        $error = null;
    }

    /* ZWEI ZIELE (S9/AP5-4). Die Standortaktionen fuehren auf die LISTE
     * zurueck, alles Uebrige auf die SEITE DES STANDORTS, an dem es haengt —
     * und dort auf die geschriebene ZEILE, nicht auf den Abschnitt: Wer etwas
     * angelegt hat, will es sehen (E-S9-19). `:target` faerbt sie.
     *
     * Bis Web 16.3.0 stand hier `t=rettungsmittel` — der Reiter, der alles auf
     * einmal zeigte. Den gibt es nicht mehr. */
    $unterblock = [
        'veh_save' => 'veh', 'veh_del' => 'veh',
        'crew_save' => 'crew', 'crew_del' => 'crew',
        'td_save'  => 'td',  'td_del'  => 'td',
        'res_save' => 'res', 'res_del' => 'res',
        'bw_save'  => 'bw',  'bw_del'  => 'bw',
    ][$action] ?? null;
    $zurueckZiel = 'admin_stammdaten.php?t=standorte';
    $abschnitt = null;
    if ($baseNeu !== null) {
        $zurueckZiel = sd_seite($baseNeu, 'admin_stammdaten.php');
        $abschnitt = 'k-standort';
    } elseif (in_array($action, ['base_save', 'base_del'], true)) {
        $abschnitt = 'standorte';
    } elseif ($unterblock !== null) {
        /* OHNE STANDORT GIBT ES KEINE SEITE, auf die man zurueckkehren
           koennte: Ein Rettungsmittel mit `base_id = null` haengt an keinem
           Standort, und seine Karte steht auf der Liste. */
        $abschnitt = $postBase !== null
            ? ('sd-' . $postBase . '-' . $unterblock)
            : 'adsd-ohne-veh';
        if ($postBase !== null) { $zurueckZiel = sd_seite($postBase, 'admin_stammdaten.php'); }
        if ($zielId !== null) { $abschnitt = $unterblock . '-' . $zielId; }
    }
    /* UMGELEITET WIRD, WENN ES EIN ZIEL GIBT — nicht, wenn es eine Meldung
     * gibt: Der Erfolgsfall der Dialoge hat keine mehr (E-S9-19), und ohne
     * diese Bedingung bliebe die Anwendung auf dem POST-Ergebnis stehen. */
    if ($abschnitt !== null
        && ($zielId !== null || $baseNeu !== null || $notice !== null || $error !== null)) {
        if ($notice !== null) { $_SESSION['flash_notice'] = $notice; }
        if ($error !== null) { $_SESSION['flash_error'] = $error; }
        header('Location: ' . $zurueckZiel . '#' . $abschnitt);
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
$vehNach  = $ladeNachBase('vehicles', 'id, name, kurz, kind, typ');
/* Zentrale Rettungsmittel OHNE Standort — dieselbe Luecke wie in der
 * Kontoansicht: `base_id IN (...)` laesst sie heraus, und sie waeren dann
 * nirgends zu aendern (E-S9-09, Web 16.0.0). */
$vehOhne = [];
foreach (db()->query('SELECT id, name, kurz, kind, typ, base_id, user_id FROM vehicles
                       WHERE base_id IS NULL AND user_id IS NULL ORDER BY name') as $z) {
    $vehOhne[] = $z;
}
$crewNach = $ladeNachBase('crew_presets', 'id, name, role_code');
$tdNach   = $ladeNachBase('transport_dests', 'id, name, lat, lon');
$resNach  = $ladeNachBase('resources', 'id, name');
$bwNach   = $ladeNachBase('bw_units', 'id, name');

/* DIE STANDORTLOSEN ZAEHLEN MIT (S9/AP5-4). Bis Web 16.3.0 sammelte diese
 * Schleife nur `$vehNach`; Rollen und Faehigkeiten eines Rettungsmittels ohne
 * Standort waren damit nie geladen. Das fiel nicht auf, solange die Karte
 * „Ohne Standort" nur Name und Typ zeigte — der Dialog fuellt jetzt auch die
 * Haken, und ohne diese Zeile stuenden sie beim Bearbeiten leer und waeren
 * nach dem Speichern weg. */
$vehIds = [];
foreach ($vehNach as $liste) { foreach ($liste as $v) { $vehIds[] = (int)$v['id']; } }
foreach ($vehOhne as $v) { $vehIds[] = (int)$v['id']; }
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
$editBase = $pick($bases, 'eb');
/* FUENF GET-PARAMETER SIND MIT S9/AP5-4 ENTFALLEN — `ev`, `ec`, `et`, `er`,
 * `ew`. Sie waren der Bearbeiten-Weg: ein Verweis auf dieselbe Seite, der ein
 * Formular unter der Liste mit anderen Werten fuellte. Diese Formulare gibt es
 * nicht mehr; „Bearbeiten" oeffnet einen Dialog. `eb` bleibt — der Standort
 * wird weiterhin in einem Formular unter der Liste bearbeitet. */

/* [Kennung => Name] fuer die Standortauswahl im Rettungsmittel-Dialog. */
$adBaseNamen = [];
foreach ($bases as $b) { $adBaseNamen[(int)$b['id']] = (string)$b['name']; }

/* Die drei Zahlen eines Standorts fuer Kleinzeile und Titelunterzeile —
 * dieselbe Form wie im Konto (`$sdZahlen`). */
$adZahlen = function (int $bid) use ($vehNach, $crewNach, $tdNach): string {
    $n = static fn(array $art): int => count($art[$bid] ?? []);
    $eins = static fn(int $z, string $ein, string $viele): string
        => $z . ' ' . ($z === 1 ? $ein : $viele);
    return $eins($n($vehNach), 'Rettungsmittel', 'Rettungsmittel') . ' · '
         . $eins($n($crewNach), 'Besatzung', 'Besatzung') . ' · '
         . $eins($n($tdNach), 'Zielklinik', 'Zielkliniken');
};

/* DIE `data-w-`-KETTE EINES RETTUNGSMITTELS (S9/AP5-4) — wortgleich mit der
 * in `einstellungen.php`, samt der Begruendung fuer die Reihenfolge: Was die
 * Anzeige steuert (`typ`, `kind`), gehoert nach vorn, und der letzte
 * Schluessel soll eine Auswahl sein, damit die Anpassung zuletzt ueber den
 * fertigen Stand laeuft. */
$vehKette = static function (?array $v, int $heimat): array {
    if ($v === null) {
        return ['titel' => 'Rettungsmittel anlegen', 'knopf' => 'Anlegen',
                'id' => '0', 'name' => '', 'kurz' => '',
                'typ' => 'standard', 'kind' => '',
                'rollen' => '', 'caps' => '', 'base' => (string)$heimat];
    }
    return ['titel' => 'Rettungsmittel bearbeiten', 'knopf' => 'Änderung speichern',
            'id' => (string)(int)$v['id'], 'name' => (string)$v['name'],
            'kurz' => (string)($v['kurz'] ?? ''),
            'typ' => (string)($v['typ'] ?? 'standard'),
            'kind' => (string)$v['kind'],
            'rollen' => implode(',', $v['rollen'] ?? []),
            'caps' => implode(',', $v['caps'] ?? []),
            'base' => (string)(int)($v['base_id'] ?? 0)];
};

/* Vorbelegung und Meldung bekommt GENAU DER Dialog, dessen Schreibweg
 * abgelehnt hat; die uebrigen stehen leer da. */
$dlgWerte = fn(string $id): array => ($dlgFehler['dialog'] ?? '') === $id
    ? (array)($dlgFehler['werte'] ?? []) : [];
$dlgMeldung = fn(string $id): string => ($dlgFehler['dialog'] ?? '') === $id
    ? (string)($dlgFehler['meldung'] ?? '') : '';

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
/* Leaflet-Stylesheet: Beide Seiten tragen seit S9/AP2 den Pin-Knopf am
   Ortsfeld und damit den Kartendialog (E-S9-06 c). */
ui_seite_start(['titel' => 'Stammdaten systemweit', 'karte' => true]);
?>

<?php /* DIE STANDORTSEITE IST KEIN MENUEPUNKT, sondern eine Seite UNTER
         einem — dieselbe Ueberlegung wie in `einstellungen.php`. Der
         Schluessel `admin_stammdaten` ist der, den
         `ui_leiste_einstellungen()` fuer beide kennt. */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_stammdaten']); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

<?php if ($tab === 'standorte'): ?>

  <?php ui_titelzeile(['titel' => 'Stammdaten systemweit']); ?>
  <p class="seiten-erklaerung">Diese Einträge gelten für <strong>alle Konten</strong> —
     sichtbar werden sie einer NutzerIn aber erst, wenn sie den zugehörigen Standort
     in ihren Einstellungen auswählt; die Auswahl ist ihre Sache. Der Standort ist
     dabei der Anker: Ein Klick auf einen Standort führt auf seine Seite, und dort
     stehen Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel und
     Bergwacht. Änderungen wirken nur auf <strong>neue</strong> Diensttage —
     dokumentierte haben ihre Angaben beim Anlegen eingefroren.</p>

  <?php ui_karte_start(['titel' => 'Standorte', 'zahl' => count($bases), 'id' => 'standorte']); ?>
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
      $klein[] = $adZahlen($bid);
      $klein[] = $ub === 1 ? '1 Konto hat ihn gewählt' : $ub . ' Konten haben ihn gewählt';
      if ($dupP > 0) {
          $klein[] = $dupP === 1
              ? '1 Konto führt einen gleichnamigen eigenen Eintrag'
              : $dupP . ' Konten führen einen gleichnamigen eigenen Eintrag';
      }
      /* DIE ZEILE IST EIN VERWEIS AUF DIE SEITE DES STANDORTS (S9/AP5-4).
         Bearbeiten und Loeschen stehen dort — ein Knopf in einem Link ist
         kein gueltiges Markup, und wer einen Standort loescht, soll vorher
         gesehen haben, was daran haengt. Dieselbe Form wie im Konto.
         Die Kleinzeile nennt die drei Zahlen und, was nur die Verwaltung
         angeht: wie viele Konten den Standort gewaehlt haben. */
      ui_zeile([
          'href_ganz' => sd_seite($bid, 'admin_stammdaten.php'),
          'vorn'  => ui_symbol('standort'),
          'text'  => (string)$b['name'],
          'klein' => implode(' · ', $klein),
          'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
      ]);
    endforeach; ?>

    <?php /* Das Standortformular bleibt ein Formular unter der Liste
             (E-P3-35): Die drei Dialoge des Konzepts sind die der
             LISTENKARTEN einer Standortseite, und die Standortkarte ist
             keine Liste. */ ?>
    <div class="listen-form">
      <h3 class="listen-form-titel"><?= $editBase ? 'Standort bearbeiten' : 'Standort hinzufügen' ?></h3>
      <form method="post" action="admin_stammdaten.php?t=standorte#standorte">
        <?= csrf_field() ?><input type="hidden" name="action" value="base_save">
        <input type="hidden" name="id" value="<?= $editBase ? (int)$editBase['id'] : 0 ?>">
        <div class="listen-form-felder">
          <?php ui_feld(['label' => 'Name', 'name' => 'name', 'id' => 'adbase-name',
                         'klasse' => 'focus-target', 'pflicht' => true,
                         'platzhalter' => 'z. B. Standort Talwang',
                         'wert' => (string)($editBase['name'] ?? ''),
                         'attr' => ' maxlength="120"']); ?>
          <?php /* Dieselbe Ortsfeld-Komponente wie in der Kontoansicht und am
                   Einsatz (assets/ortsfeld.js). Die Kennung `<praefix>addr`
                   gehört dem LAGE-Suchfeld, nicht dem Namen (F-P3-AI). */
                $ORTSFELDER[] = 'adbase'; ?>
          <?php ui_ortsfeld([
                  'praefix' => 'adbase', 'feld' => false, 'ortswahl' => true,
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

  <?php /* OHNE STANDORT (E-S9-09/E-S9-18) — dieselbe Karte wie im Konto, aus
           demselben Grund: Bergwacht, Veranstaltung und Sonstiges brauchen
           keinen Standort, haengen also an keiner Standortseite und stehen
           deshalb auf der Liste. */ ?>
  <?php if ($vehOhne): ?>
    <?php ui_karte_start(['titel' => 'Ohne Standort', 'id' => 'adsd-ohne', 'zu' => true,
                          'zahl' => count($vehOhne) . ' Rettungsmittel',
                          'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                       'art' => 'orange', 'href' => '#',
                                       'attr' => sd_oeffner('dlg-veh', $vehKette(null, 0))]]); ?>
      <p class="feld-hinweis">Bergwacht, Veranstaltung und Sonstiges brauchen keinen
         Standort. Sie haben dafür keine Vorschlagslisten — die hängen am Standort.</p>
      <section class="sd-liste" id="adsd-ohne-veh">
        <?php foreach ($vehOhne as $v):
              $vid = (int)$v['id'];
              $klein = VEHICLE_TYPEN[(string)$v['typ']]['label'] ?? (string)$v['typ'];
              if ((string)($v['kurz'] ?? '') !== '') { $klein .= ' · ' . (string)$v['kurz']; }
              sd_zeile([
                  'seite' => 'admin_stammdaten.php?t=standorte',
                  'name'  => (string)$v['name'], 'klein' => $klein,
                  'anker' => 'adsd-ohne-veh', 'praefix' => 'veh', 'id' => $vid,
                  'zeilen_id' => true, 'base_id' => 0,
                  'bearbeiten_attr' => sd_oeffner('dlg-veh', $vehKette(
                      $v + ['rollen' => $vehRollen[$vid] ?? [],
                            'caps'   => $vehCaps[$vid] ?? []], 0)),
                  'del_action' => 'veh_del',
                  'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ systemweit löschen? '
                               . 'Dokumentierte Diensttage bleiben unverändert.',
                  'plaketten' => ui_artzeichen((string)$v['kind'], '', (string)$v['typ']),
              ]);
        endforeach; ?>
      </section>
    <?php ui_karte_ende(true); ?>
    <?php sd_dialog_rettungsmittel([
              'seite' => 'admin_stammdaten.php?t=standorte', 'base_id' => 0,
              'unterzeile' => 'Ohne Standort', 'bases' => $adBaseNamen,
              'werte' => $dlgWerte('dlg-veh'), 'fehler' => $dlgMeldung('dlg-veh'),
          ]); ?>
  <?php endif; ?>

<?php else: ?>

  <?php
    /* DIE SEITE EINES STANDORTS. Die Kennung ist oben geprueft; findet sie
       sich hier trotzdem nicht, hat sich der Bestand zwischen den beiden
       Abfragen geaendert — dann ist die Liste der richtige Ort, und
       `ui_abbruch()` kann das noch, wenn `header()` es nicht mehr kann. */
    $seiteB = null;
    foreach ($bases as $b) { if ((int)$b['id'] === $seiteBase) { $seiteB = $b; break; } }
    if ($seiteB === null) { ui_abbruch(404, 'Diesen Standort gibt es nicht (mehr).'); }
    $bid = (int)$seiteB['id'];
    $seite = sd_seite($bid, 'admin_stammdaten.php');
    $anker = 'sd-' . $bid;
    $vehListe = $vehNach[$bid] ?? [];
    $hatLuft = false;
    foreach ($vehListe as $v) { if ($v['kind'] === 'air') { $hatLuft = true; break; } }
    $rollenHier = $rollenAmStandort($bid);
    $anz = $anzahlJeBase($bid);
    $ub  = $ubZahl[$bid] ?? 0;
    $sdUnter = 'Standort ' . (string)$seiteB['name'];
  ?>
  <?php /* LOESCHEN STEHT HIER, nicht in der Liste: Die Zeile dort ist der
           Verweis auf diese Seite, und ein Knopf in einem Link ist kein
           gueltiges Markup. Zugleich der bessere Ort — wer einen Standort
           loescht, hat vorher gesehen, was daran haengt. */ ?>
  <form method="post" id="f-adbdel-<?= $bid ?>" class="nur-vorlesen"
        action="admin_stammdaten.php?t=standorte#standorte"
        data-confirm="Standort „<?= e($seiteB['name']) ?>“ systemweit löschen? <?= $anz > 0
            ? ($anz === 1 ? 'Ein systemweiter Stammdatensatz' : $anz . ' systemweite Stammdatensätze')
              . ' dieses Standorts (Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel, Bergwacht) werden mitgelöscht.'
            : 'Es hängen keine systemweiten Stammdaten daran.' ?><?= $ub > 0
            ? ' Er verschwindet aus den Auswahllisten von '
              . ($ub === 1 ? 'einem Konto' : $ub . ' Konten') . '.'
            : '' ?> Bereits dokumentierte Diensttage bleiben unverändert.">
    <?= csrf_field() ?><input type="hidden" name="action" value="base_del">
    <input type="hidden" name="id" value="<?= $bid ?>">
  </form>
  <?php ui_titelzeile([
      'zurueck'  => ['href' => 'admin_stammdaten.php?t=standorte', 'text' => 'Stammdaten systemweit'],
      'titel'    => (string)$seiteB['name'],
      'unter'    => e($adZahlen($bid)),
      'aktionen' => ui_zeilenaktionen(['titel' => (string)$seiteB['name'], 'eintraege' => [
          ['text' => 'Bearbeiten', 'symbol' => 'stift',
           'href' => 'admin_stammdaten.php?t=standorte&eb=' . $bid . '#standorte'],
          ['text' => 'Löschen', 'symbol' => 'korb', 'art' => 'gefahr',
           'form' => 'f-adbdel-' . $bid],
      ]]),
  ]); ?>
  <p class="seiten-erklaerung">Diese Einträge gelten für <strong>alle Konten</strong>,
     die diesen Standort in ihren Einstellungen ausgewählt haben —
     <?= $ub === 1 ? 'derzeit eines' : 'derzeit ' . $ub ?>. Änderungen wirken nur auf
     <strong>neue</strong> Diensttage; dokumentierte haben ihre Angaben eingefroren.</p>

  <?php /* DAS INHALTSVERZEICHNIS ALS KENNZAHLEN (M-S9-07) — dieselben drei
           Kacheln wie im Konto, dieselben Kartenkennungen. */ ?>
  <div class="kennzahl-raster kennzahl-raster-3">
    <?= ui_kennzahl(['wert' => (string)count($vehListe), 'label' => 'Rettungsmittel',
                     'href' => '#k-rettungsmittel']) ?>
    <?= ui_kennzahl(['wert' => (string)count($crewNach[$bid] ?? []), 'label' => 'Besatzung',
                     'href' => '#k-besatzung']) ?>
    <?= ui_kennzahl(['wert' => (string)count($tdNach[$bid] ?? []), 'label' => 'Zielkliniken',
                     'href' => '#k-zielkliniken']) ?>
  </div>

  <?php ui_karte_start(['titel' => 'Standort', 'id' => 'k-standort']); ?>
    <?php ui_zeile([
        'text'  => (string)$seiteB['name'],
        'klein' => ($seiteB['lat'] !== null && $seiteB['lon'] !== null)
                 ? $seiteB['lat'] . ', ' . $seiteB['lon'] . ' — Abfahrtsort neuer Diensttage'
                 : 'ohne Lage — ohne sie gibt es keinen Abfahrtsort',
        'aktionen' => ui_knopf([
            'text' => 'Bearbeiten', 'symbol' => 'stift', 'art' => 'leise',
            'href' => 'admin_stammdaten.php?t=standorte&eb=' . $bid . '#standorte']),
    ]); ?>
  <?php ui_nach_oben(); ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Rettungsmittel', 'id' => 'k-rettungsmittel',
                        'zahl' => count($vehListe),
                        'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                     'art' => 'orange', 'href' => '#',
                                     'attr' => sd_oeffner('dlg-veh', $vehKette(null, $bid))]]); ?>
    <section class="sd-liste" id="<?= e($anker) ?>-veh">
      <p class="feld-hinweis">Die Art entscheidet über Besatzungsrollen und die im
         Einsatzformular sichtbaren Felder. Fähigkeiten (Winde, Bergwacht) gibt es
         nur luftgebunden.</p>
      <?php if (!$vehListe): ?>
        <p class="feld-hinweis">Noch keine Rettungsmittel an diesem Standort.</p>
      <?php endif; ?>
      <?php if (count($vehListe) >= SD_HILFE_AB):
            ui_sprungliste([
                'label' => 'Zu einem Rettungsmittel springen',
                'eintraege' => array_map(static fn(array $v): array => [
                    'text' => (string)$v['name'],
                    'href' => '#veh-' . (int)$v['id'],
                    'vorn' => ui_artzeichen((string)$v['kind'], '', (string)($v['typ'] ?? null)),
                ], $vehListe),
            ]);
      endif; ?>
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
            'vorn'  => ui_artzeichen((string)$v['kind'], '', (string)($v['typ'] ?? null)),
            'zeilen_id' => true,
            'anker' => $anker . '-veh', 'praefix' => 'veh', 'id' => $vid, 'base_id' => $bid,
            'del_action' => 'veh_del',
            'del_frage' => 'Rettungsmittel „' . $v['name'] . '“ systemweit löschen? '
                         . 'Dokumentierte Diensttage bleiben unverändert.',
            'bearbeiten_attr' => sd_oeffner('dlg-veh', $vehKette(
                $v + ['rollen' => $vehRollen[$vid] ?? [], 'caps' => $vehCaps[$vid] ?? []], $bid)),
            'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
        ]);
      endforeach; ?>
    </section>
  <?php ui_nach_oben(); ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Besatzung', 'id' => 'k-besatzung',
                        'zahl' => count($crewNach[$bid] ?? []),
                        'aktion' => $rollenHier ? ['text' => 'Anlegen', 'symbol' => 'plus',
                                     'art' => 'orange', 'href' => '#',
                                     'attr' => sd_oeffner('dlg-crew', [
                                         'titel' => 'Besatzungsmitglied anlegen',
                                         'knopf' => 'Anlegen', 'id' => '0', 'name' => '',
                                         'rolle' => (string)$rollenHier[0]])] : null]); ?>
    <p class="feld-hinweis">Vorschläge für die Besatzungsfelder, je Rolle. Freitext
       bleibt überall möglich — wer aushilft, muss nicht erst hier stehen.</p>
    <?php if (count($crewNach[$bid] ?? []) >= SD_HILFE_AB) {
               ui_kartenfilter(['id' => 'filt-crew-' . $bid, 'ziel' => $anker . '-crew',
                                'label' => 'Besatzung filtern', 'platzhalter' => 'Namen filtern']);
           } ?>
    <section class="sd-liste" id="<?= e($anker) ?>-crew">
      <?php if (!$rollenHier): ?>
        <p class="feld-hinweis">Noch keine Rolle an diesem Standort. Rollen entstehen
           am Rettungsmittel: Trage oben eines ein und hake an, welche Rollen es
           führt.</p>
      <?php endif; ?>
      <?php foreach ($rollenHier as $rk): $rr = CREW_ROLES[$rk]; ?>
        <h3 class="sd-rolle"><?= e($rr['label']) ?></h3>
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
                      'zeilen_id' => true,
                      'anker' => $anker . '-crew', 'praefix' => 'crew', 'id' => (int)$c['id'],
                      'base_id' => $bid,
                      'del_action' => 'crew_del',
                      'del_frage' => 'Eintrag „' . $c['name'] . '“ systemweit löschen?',
                      'bearbeiten_attr' => sd_oeffner('dlg-crew', [
                          'titel' => 'Besatzungsmitglied bearbeiten',
                          'knopf' => 'Änderung speichern', 'id' => (string)(int)$c['id'],
                          'name' => (string)$c['name'], 'rolle' => $rk]),
                      'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
                  ]);
              endforeach;
              if (!$any): ?>
          <p class="feld-hinweis">Noch keine Einträge.</p>
        <?php endif; ?>
      <?php endforeach; ?>
    </section>
  <?php ui_nach_oben(); ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Zielkliniken', 'id' => 'k-zielkliniken',
                        'zahl' => count($tdNach[$bid] ?? []),
                        'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                     'art' => 'orange', 'href' => '#',
                                     'attr' => sd_oeffner('dlg-td', [
                                         'titel' => 'Zielklinik anlegen', 'knopf' => 'Anlegen',
                                         'id' => '0', 'name' => '', 'lat' => '', 'lon' => ''])]]); ?>
    <p class="feld-hinweis">Vorschläge für das Transportziel. Mit Lage lässt sich
       die Luftlinie zum Einsatzort zeichnen.</p>
    <?php if (count($tdNach[$bid] ?? []) >= SD_HILFE_AB) {
               ui_kartenfilter(['id' => 'filt-td-' . $bid, 'ziel' => $anker . '-td',
                                'label' => 'Zielkliniken filtern', 'platzhalter' => 'Zielklinik filtern']);
           } ?>
    <section class="sd-liste" id="<?= e($anker) ?>-td">
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
                'zeilen_id' => true,
                'anker' => $anker . '-td', 'praefix' => 'td', 'id' => (int)$t['id'],
                'base_id' => $bid,
                'del_action' => 'td_del',
                'del_frage' => 'Zielklinik „' . $t['name'] . '“ systemweit löschen?',
                'bearbeiten_attr' => sd_oeffner('dlg-td', [
                    'titel' => 'Zielklinik bearbeiten', 'knopf' => 'Änderung speichern',
                    'id' => (string)(int)$t['id'], 'name' => (string)$t['name'],
                    'lat' => (string)($t['lat'] ?? ''), 'lon' => (string)($t['lon'] ?? '')]),
                'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
            ]);
      endforeach; ?>
    </section>
  <?php ui_nach_oben(); ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Weitere Rettungsmittel', 'id' => 'k-weitere',
                        'zahl' => count($resNach[$bid] ?? []),
                        'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                     'art' => 'orange', 'href' => '#',
                                     'attr' => sd_oeffner('dlg-res', [
                                         'titel' => 'Weiteres Rettungsmittel anlegen',
                                         'knopf' => 'Anlegen', 'id' => '0', 'name' => ''])]]); ?>
    <p class="feld-hinweis">Vorschläge für das Feld „Weitere Rettungsmittel" im
       Einsatz (RTW, NEF, RTH …).</p>
    <?php if (count($resNach[$bid] ?? []) >= SD_HILFE_AB) {
               ui_kartenfilter(['id' => 'filt-res-' . $bid, 'ziel' => $anker . '-res',
                                'label' => 'Weitere Rettungsmittel filtern',
                                'platzhalter' => 'Bezeichnung filtern']);
           } ?>
    <section class="sd-liste" id="<?= e($anker) ?>-res">
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
                'zeilen_id' => true,
                'anker' => $anker . '-res', 'praefix' => 'res', 'id' => (int)$r['id'],
                'base_id' => $bid,
                'del_action' => 'res_del',
                'del_frage' => 'Eintrag „' . $r['name'] . '“ systemweit löschen?',
                'bearbeiten_attr' => sd_oeffner('dlg-res', [
                    'titel' => 'Weiteres Rettungsmittel bearbeiten', 'knopf' => 'Änderung speichern',
                    'id' => (string)(int)$r['id'], 'name' => (string)$r['name']]),
                'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
            ]);
      endforeach; ?>
    </section>
  <?php ui_nach_oben(); ui_karte_ende(); ?>

  <?php /* DIE BERGWACHT-KARTE ERSCHEINT NUR MIT EINEM LUFTGEBUNDENEN
           RETTUNGSMITTEL (E29). */ ?>
  <?php if ($hatLuft): ?>
    <?php ui_karte_start(['titel' => 'Bergwacht', 'id' => 'k-bergwacht',
                          'zahl' => count($bwNach[$bid] ?? []),
                          'aktion' => ['text' => 'Anlegen', 'symbol' => 'plus',
                                       'art' => 'orange', 'href' => '#',
                                       'attr' => sd_oeffner('dlg-bw', [
                                           'titel' => 'Bereitschaft anlegen',
                                           'knopf' => 'Anlegen', 'id' => '0', 'name' => ''])]]); ?>
      <p class="feld-hinweis">Bereitschaften für das Feld „Bergwacht" im Einsatz.
         Der Abschnitt erscheint, weil an diesem Standort ein luftgebundenes
         Rettungsmittel steht — die Fähigkeit kommt nur dort vor.</p>
      <?php if (count($bwNach[$bid] ?? []) >= SD_HILFE_AB) {
                 ui_kartenfilter(['id' => 'filt-bw-' . $bid, 'ziel' => $anker . '-bw',
                                  'label' => 'Bereitschaften filtern',
                                  'platzhalter' => 'Bereitschaft filtern']);
             } ?>
      <section class="sd-liste" id="<?= e($anker) ?>-bw">
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
                  'zeilen_id' => true,
                  'anker' => $anker . '-bw', 'praefix' => 'bw', 'id' => (int)$w['id'],
                  'base_id' => $bid,
                  'del_action' => 'bw_del',
                  'del_frage' => 'Bereitschaft „' . $w['name'] . '“ systemweit löschen?',
                  'bearbeiten_attr' => sd_oeffner('dlg-bw', [
                      'titel' => 'Bereitschaft bearbeiten', 'knopf' => 'Änderung speichern',
                      'id' => (string)(int)$w['id'], 'name' => (string)$w['name']]),
                  'plaketten' => $dupP > 0 ? ui_plakette('Namensdublette', ['ton' => 'orange']) : '',
              ]);
        endforeach; ?>
      </section>
    <?php ui_nach_oben(); ui_karte_ende(); ?>
  <?php endif; ?>

  <?php /* ---- DIE DIALOGE DER SEITE (E-S9-19) — dieselben fuenf wie im
           Konto, aus derselben Datei. Sie stehen einmal, am Ende. */
        sd_dialog_rettungsmittel([
            'seite' => $seite, 'base_id' => $bid, 'unterzeile' => $sdUnter,
            'base_name' => (string)$seiteB['name'], 'bases' => $adBaseNamen,
            'werte' => $dlgWerte('dlg-veh'), 'fehler' => $dlgMeldung('dlg-veh'),
        ]);
        if ($rollenHier) {
            $rollenWahl = [];
            foreach ($rollenHier as $rk) { $rollenWahl[$rk] = CREW_ROLES[$rk]['label']; }
            sd_dialog_eintrag([
                'id' => 'dlg-crew', 'seite' => $seite, 'base_id' => $bid,
                'unterzeile' => $sdUnter, 'action' => 'crew_save',
                'titel_neu' => 'Besatzungsmitglied anlegen',
                'label' => 'Name', 'platzhalter' => 'z. B. Nachname',
                'max' => SD_NAME_MAX, 'rollen' => $rollenWahl,
                'hinweis' => 'Vorlage für Diensttage an diesem Standort — in jedem Konto, '
                           . 'das ihn ausgewählt hat.',
                'werte' => $dlgWerte('dlg-crew'), 'fehler' => $dlgMeldung('dlg-crew'),
            ]);
        }
        sd_dialog_zielklinik([
            'seite' => $seite, 'base_id' => $bid, 'unterzeile' => $sdUnter,
            'praefix' => 'adtd' . $bid,
            'werte' => $dlgWerte('dlg-td'), 'fehler' => $dlgMeldung('dlg-td'),
        ]);
        $ORTSFELDER[] = 'adtd' . $bid;
        sd_dialog_eintrag([
            'id' => 'dlg-res', 'seite' => $seite, 'base_id' => $bid,
            'unterzeile' => $sdUnter, 'action' => 'res_save',
            'titel_neu' => 'Weiteres Rettungsmittel anlegen',
            'label' => 'Bezeichnung', 'platzhalter' => 'z. B. RTW Talwang 76/85',
            'max' => SD_NAME_MAX,
            'hinweis' => 'Vorschlag im Feld „Weitere Rettungsmittel“ am Einsatz.',
            'werte' => $dlgWerte('dlg-res'), 'fehler' => $dlgMeldung('dlg-res'),
        ]);
        if ($hatLuft) {
            sd_dialog_eintrag([
                'id' => 'dlg-bw', 'seite' => $seite, 'base_id' => $bid,
                'unterzeile' => $sdUnter, 'action' => 'bw_save',
                'titel_neu' => 'Bereitschaft anlegen',
                'label' => 'Bereitschaft', 'platzhalter' => 'z. B. Bergwacht Sonnenau',
                'max' => SD_NAME_MAX,
                'hinweis' => 'Vorschlag im Feld „Bergwacht“ am Einsatz.',
                'werte' => $dlgWerte('dlg-bw'), 'fehler' => $dlgMeldung('dlg-bw'),
            ]);
        }
  ?>

<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php /* confirm.js kommt aus ui_geruest_ende() (ui.php) — eine zweite Einbindung
         haette den Rueckfragedialog doppelt geoeffnet. */ ?>
<script src="<?= asset('assets/openlocationcode.js') ?>"></script>
<script src="<?= asset('assets/locparse.js') ?>"></script>
<?php /* html.js (EdHtml.escape) und vorschlagsliste.js (EdVorschlaege)
         gehoeren zur Ortsfeld-Komponente, seit die Trefferliste ein eigener
         Baustein ist (S9/AP1, E-S9-07). Reihenfolge = Abhaengigkeit. */ ?>
<script src="<?= asset('assets/html.js') ?>"></script>
<script src="<?= asset('assets/vorschlagsliste.js') ?>"></script>
<script src="<?= asset('assets/geocoder.js') ?>"></script>
<script src="<?= asset('assets/ortsfeld.js') ?>"></script>
<?php /* Die Karte kommt mit S9/AP2 auch hierher (E-S9-06 c, Backlog Nr. 70) —
         dieselben vier Bausteine wie im Einsatzformular. */ ?>
<script src="<?= asset('assets/vendor/leaflet/leaflet.js') ?>"></script>
<script src="<?= asset('assets/map_layers.js') ?>"></script>
<script src="<?= asset('assets/geo.js') ?>"></script>
<script src="<?= asset('assets/ortswahl.js') ?>"></script>
<?php /* DER KARTENFILTER (S9/AP5) — dieselben langen Listen wie im Konto,
         dasselbe Hilfsmittel ab `SD_HILFE_AB` Eintraegen. */ ?>
<script src="<?= asset('assets/kartenfilter.js') ?>"></script>
<script src="<?= asset('assets/dialog.js') ?>"></script>
<script>
/* Ortsfelder der systemweiten Stammdatenpflege (E37/E38). Dieselbe Komponente
 * wie in der Kontoansicht — systemweit gepflegte Koordinaten gelten fuer alle,
 * die den Eintrag sehen. Ohne Spur: Hier gibt es keinen Einsatz. */
<?= 'const ORTSFELDER = ' . json_js($ORTSFELDER) . ';' ?>
ORTSFELDER.forEach(p => {
  const steuer = EdOrtsfeld.init({ praefix: p, getrennteSuche: true });
  if (steuer) { EdOrtswahl.registriere(p, steuer); }
});
</script>
<script>
/* Den Abschnitt aus dem Anker wieder aufklappen — einschliesslich der
 * VORFAHREN: Die Karte „Ohne Standort" ist ein <details>, und eine
 * angesprungene Zeile darin waere in einer geschlossenen Karte nicht zu
 * sehen. */
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

/* DER RETTUNGSMITTEL-DIALOG RICHTET SICH NACH DEM TYP (E-S9-09/E-S9-19) —
 * dieselben vier Schritte wie in `einstellungen.php`, dieselbe Quelle der
 * Regeln (`VEHICLE_TYPEN`). Rein anzeigend: Was zulaessig ist, entscheidet
 * `pruef_rettungsmittel()` im Schreibweg. */
(function () {
  var dlg = document.getElementById('dlg-veh');
  if (!dlg) { return; }
  var f = dlg.querySelector('form');
  var TYPEN  = <?= json_js(VEHICLE_TYPEN) ?>;
  var heimat = dlg.dataset.heimat || '0';
  var typ    = f.querySelector('[name=typ]');
  var base   = f.querySelector('#dlgveh-base');
  var baseFeld = base.closest('.feld');

  function anpassen() {
    var regel = TYPEN[typ.value] || TYPEN.standard;
    var fest = regel.betriebsart;
    f.querySelectorAll('.vehkind-radio').forEach(function (r) {
      r.disabled = (fest !== null && r.value !== fest);
      if (fest !== null) { r.checked = (r.value === fest); }
    });
    f.querySelector('[data-veh-fest]').hidden = (fest === null);

    var gewaehlt = f.querySelector('.vehkind-radio:checked');
    var kind = gewaehlt ? gewaehlt.value : null;

    f.querySelectorAll('.rollehaken').forEach(function (lab) {
      var k = lab.dataset.kind;
      var passt = regel.rollen && kind !== null && (k === 'both' || k === kind);
      lab.hidden = !passt;
      if (!passt) { lab.querySelector('input').checked = false; }
    });
    f.querySelector('.rollen-zeile').hidden = !regel.rollen || kind === null;

    var caps = f.querySelector('.vehcaps-zeile');
    var capsAn = regel.rollen && kind === 'air';
    caps.hidden = !capsAn;
    if (!capsAn) {
      caps.querySelectorAll('input').forEach(function (i) { i.checked = false; });
    }
    f.querySelector('[data-veh-ohne-vorlagen]').hidden = regel.rollen;

    var wahl = !regel.standort || heimat === '0';
    baseFeld.hidden = !wahl;
    f.querySelector('[data-veh-heimat]').hidden = wahl;
    if (!wahl) { base.value = heimat; }
  }

  typ.addEventListener('change', anpassen);
  f.querySelectorAll('.vehkind-radio').forEach(function (r) {
    r.addEventListener('change', anpassen);
  });
  anpassen();
})();
</script>
<?php if ($dlgFehler !== null): ?>
<script>
/* Nach einem abgelehnten Speichern geht der Dialog wieder auf (E-S9-19). */
if (window.edDialog) { window.edDialog.auf(<?= json_js($dlgFehler['dialog']) ?>); }
</script>
<?php endif; ?>

<?php ui_seite_ende(); ?>
