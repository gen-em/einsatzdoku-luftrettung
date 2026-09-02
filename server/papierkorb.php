<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/trash_lib.php';
require_once __DIR__ . '/diensttag_lib.php';

/**
 * Aktionen des Papierkorbs. Wiederherstellen laeuft direkt (harmlos,
 * jederzeit umkehrbar); das endgueltige Loeschen zeigt vorher eine
 * Zwischenseite mit dem Umfang.
 *
 * SCHLUESSEL IST DIE KENNUNG DES DIENSTTAGS (Web 6.0.0), nicht mehr das Datum.
 * Die frueheren Formatpruefungen auf 'YYYY-MM-DD' sind damit zu Pruefungen auf
 * eine positive Kennung geworden — dieselbe Absicherung an derselben Stelle,
 * nur fuer den neuen Schluessel (M5-08).
 *
 * DIE RUECKFRAGE BLEIBT EINE SEITE (P3/O11). Sie waere als Dialog schneller,
 * und genau das ist der Einwand: Was hier steht, will gelesen werden — der
 * Umfang, das Mitgeloeschte, die Unumkehrbarkeit. Ein Dialog, der einen
 * halben Bildschirm Text traegt, ist keiner mehr; und der Weg dorthin hat
 * eine eigene Adresse, die man zurueckgehen kann.
 */

$action = (string)($_POST['action'] ?? $_GET['action'] ?? '');
$dayId  = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$id     = (int)($_POST['id'] ?? $_GET['id'] ?? 0);
$isPost = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($isPost) { csrf_check(); }

if ($isPost && $action === 'restore_day' && $dayId > 0) {
    trash_restore_day($userId, $dayId);
    header('Location: index.php?d=' . (int)$dayId); exit;
}
if ($isPost && $action === 'restore_mission' && $id > 0) {
    /* NICHT MEHR BEDINGUNGSLOS (Backlog Nr. 33). Liegt der Diensttag selbst im
     * Papierkorb, lehnt trash_restore_mission() ab — sonst entstuende ein
     * aktiver Einsatz an einem geloeschten Tag, also genau der halb sichtbare
     * Zustand, den das Einspielen eines Backups seit E-S1-19 verweigert.
     * Die Meldung geht ueber die Sitzung, weil danach umgeleitet wird. */
    $ergebnis = trash_restore_mission($userId, $id);
    if ($ergebnis === 'tag_im_papierkorb') {
        $_SESSION['flash_error'] =
            'Der Diensttag dieses Einsatzes liegt ebenfalls im Papierkorb. '
          . 'Stelle zuerst den Diensttag wieder her — der Einsatz bleibt so lange hier.';
        header('Location: papierkorb.php'); exit;
    }
    header('Location: index.php'); exit;
}
/* Dieselbe Pruefung wie beim Wiederherstellen (M5-08).
 *
 * Vorher stand hier nur die Rueckfrage. Das Wiederherstellen — die UMKEHRBARE
 * Handlung — pruefte den Schluessel, das endgueltige Loeschen nicht. Die
 * Zwischenseite weiter unten prueft zwar auch, aber erst NACH diesem Block:
 * Ein POST mit confirm=ja kam nie dort an.
 *
 * Praktisch ausgenutzt haette man das kaum — trash_purge_day() arbeitet mit
 * vorbereiteten Anweisungen, eine unsinnige Kennung trifft schlicht nichts. Aber
 * die schwaechere Pruefung ausgerechnet am unumkehrbaren Weg ist die falsche
 * Richtung, und beim naechsten Umbau von trash_purge_day() waere sie die
 * Stelle, an der es weh tut. */
if ($isPost && $action === 'purge_day' && ($_POST['confirm'] ?? '') === 'ja'
    && $dayId > 0) {
    trash_purge_day($userId, $dayId);
    header('Location: index.php'); exit;
}
if ($isPost && $action === 'purge_mission' && ($_POST['confirm'] ?? '') === 'ja' && $id > 0) {
    // Ebenso: Das Wiederherstellen verlangt $id > 0, das endgueltige Loeschen
    // verlangte gar nichts (M5-08).
    trash_purge_mission($userId, $id);
    header('Location: index.php'); exit;
}

/* ---- Zwischenseite fuer das endgueltige Loeschen ----------------------- */
$istTag = ($action === 'purge_day');
if ($istTag && $dayId <= 0) {
    ui_abbruch(400, 'Ungültiger Diensttag.',
               ['zurueck' => 'papierkorb.php', 'zurueck_text' => 'Zum Papierkorb']);
}
$zeigeListe = ($action !== 'purge_day' && $action !== 'purge_mission');

if (!$zeigeListe && $istTag) {
    $tag = dt_laden($userId, $dayId, true);
    if ($tag === null) { header('Location: papierkorb.php'); exit; }
    /* HIER STAND EIN AUFRUF INS LEERE (P3/O11). `$scope = trash_scope_day(...)`
     * wurde geholt und nie ausgegeben — die Zahl darunter kommt aus der
     * eigenen Abfrage, weil trash_scope_day() nur NICHT-geloeschte Zeilen
     * zaehlt und im Papierkorb alle markiert sind. Der Aufruf kostete je
     * Einsatz drei weitere Abfragen. Er ist ersatzlos weg; die Funktion
     * bleibt, `diensttag_loeschen.php` braucht sie wirklich. */
    $c = db()->prepare('SELECT COUNT(*) FROM missions
                        WHERE user_id = ? AND day_id = ? AND deleted_at IS NOT NULL');
    $c->execute([$userId, $dayId]);
    $anzahl = (int)$c->fetchColumn();
    /* WAS NOCH AKTIV AM TAG HAENGT (Backlog Nr. 33). In aller Regel nichts —
     * aber wenn doch, geht es mit, und dann muss es hier stehen. Die Zahl
     * oben zaehlt nur die geloeschten; sie war damit zu klein. */
    $aktiv = trash_aktiv_am_tag($userId, $dayId);
} elseif (!$zeigeListe) {
    $st = db()->prepare('SELECT * FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NOT NULL');
    $st->execute([$id, $userId]);
    $m = $st->fetch();
    if (!$m) { header('Location: papierkorb.php'); exit; }
}

$trashDays     = $zeigeListe ? trash_list_days($userId) : [];
$trashMissions = $zeigeListe ? trash_list_missions($userId) : [];

/* Meldung aus der Sitzung abholen — dieselbe Mechanik wie in
 * admin_stammdaten.php und einstellungen.php. Sie wird gebraucht, seit das
 * Zurueckholen eines Einsatzes abgelehnt werden kann (Backlog Nr. 33): Nach
 * einer Umleitung ist eine Variable weg, und eine Handlung, die nichts tut
 * und nichts sagt, ist die schlechteste von beidem. */
$fehler = null;
if (!empty($_SESSION['flash_error'])) {
    $fehler = (string)$_SESSION['flash_error'];
    unset($_SESSION['flash_error']);
}

require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits
ui_seite_start(['titel' => $zeigeListe ? 'Papierkorb' : 'Endgültig löschen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage']); ?>
  <?php if ($zeigeListe): ?>

    <?php ui_titelzeile(['titel' => 'Papierkorb']); ?>
    <?php ui_meldung(null, $fehler, 'info', '    '); ?>

    <p class="seiten-erklaerung">Gelöschtes bleibt <?= TRASH_DAYS ?> Tage hier und
       wird danach automatisch endgültig entfernt. Wiederherstellen ist jederzeit
       möglich; endgültig löschen ist es nicht.</p>

    <?php if (!$trashDays && !$trashMissions): ?>
      <?= ui_meldung_markup('info', 'Der Papierkorb ist leer.') ?>
    <?php endif; ?>

    <?php if ($trashDays): ?>
      <?php /* Datum UND Dienstbeginn: Seit E9 können mehrere Diensttage auf
               einem Kalendertag liegen, und im Papierkorb sind sie ohne die
               Uhrzeit nicht auseinanderzuhalten. Rettungsmittel, Zahl der
               Einsätze und Löschzeitpunkt stehen in der Kleinzeile — aus den
               eingefrorenen Spalten (E8). Die Tabelle davor hatte fünf
               Spalten und lief bei 360 px waagerecht aus dem Bild. */ ?>
      <?php ui_karte_start(['titel' => 'Diensttage', 'zahl' => count($trashDays)]); ?>
        <?php foreach ($trashDays as $t):
              $tid = (int)$t['id'];
              $klein = [];
              $klein[] = $t['vehicle_name'] !== null && $t['vehicle_name'] !== ''
                       ? (string)$t['vehicle_name'] : 'ohne Rettungsmittel';
              $klein[] = (int)$t['einsaetze'] === 1
                       ? '1 Einsatz' : (int)$t['einsaetze'] . ' Einsätze';
              $klein[] = 'gelöscht am ' . fmt_local((string)$t['deleted_at'], 'd.m.Y H:i');
        ?>
          <?php /* Das POST-Formular steht EINMAL und versteckt; der Knopf der
                   Zeile und der des Aktionsblatts zeigen beide über `form`
                   darauf (ui_zeilenaktionen). */ ?>
          <form method="post" action="papierkorb.php" id="f-tag-<?= $tid ?>" class="nur-vorlesen">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="restore_day">
            <input type="hidden" name="d" value="<?= $tid ?>">
          </form>
          <?php ui_zeile([
              'text'  => dt_lesbar($t, true),
              'klein' => implode(' · ', $klein),
              'plaketten' => ui_artzeichen($t['kind'] === null ? null : (string)$t['kind']),
              'aktionen' => ui_zeilenaktionen([
                  'titel' => dt_lesbar($t, true),
                  'eintraege' => [
                      ['text' => 'Wiederherstellen', 'symbol' => 'zurueck',
                       'form' => 'f-tag-' . $tid],
                      ['text' => 'Endgültig löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                       'href' => 'papierkorb.php?action=purge_day&d=' . $tid],
                  ],
              ]),
          ]); ?>
        <?php endforeach; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php if ($trashMissions): ?>
      <?php ui_karte_start(['titel' => 'Einsätze', 'zahl' => count($trashMissions)]); ?>
        <?php foreach ($trashMissions as $t):
              $mid = (int)$t['id'];
              /* Das ECHTE Einsatzdatum aus `started_at` (E14) — der Einsatz
                 trägt seit Web 6.0.0 kein eigenes Datum mehr, und das seines
                 Dienstes kann ein anderes sein. */
        ?>
          <form method="post" action="papierkorb.php" id="f-eins-<?= $mid ?>" class="nur-vorlesen">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="restore_mission">
            <input type="hidden" name="id" value="<?= $mid ?>">
          </form>
          <?php
            $bez = 'Einsatz vom ' . fmt_local((string)$t['started_at'], 'd.m.Y')
                 . ', ' . fmt_local((string)$t['started_at']) . ' Uhr';
            ui_zeile([
              'text'  => $bez,
              'klein' => 'gelöscht am ' . fmt_local((string)$t['deleted_at'], 'd.m.Y H:i'),
              'aktionen' => ui_zeilenaktionen([
                  'titel' => $bez,
                  'eintraege' => [
                      ['text' => 'Wiederherstellen', 'symbol' => 'zurueck',
                       'form' => 'f-eins-' . $mid],
                      ['text' => 'Endgültig löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                       'href' => 'papierkorb.php?action=purge_mission&id=' . $mid],
                  ],
              ]),
            ]);
          ?>
        <?php endforeach; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

  <?php else: ?>

    <?php ui_titelzeile([
        'titel'   => 'Endgültig löschen?',
        'zurueck' => ['text' => 'Zum Papierkorb', 'href' => 'papierkorb.php'],
    ]); ?>

    <?php ui_karte_start(['titel' => $istTag
        ? 'Diensttag ' . dt_lesbar($tag, true)
        : 'Einsatz vom ' . fmt_local((string)$m['started_at'], 'd.m.Y')
          . ', ' . fmt_local((string)$m['started_at']) . ' Uhr']); ?>

      <?php if ($istTag): ?>
        <p>Mitgelöscht werden <strong><?= $anzahl ?> <?= $anzahl === 1 ? 'Einsatz'
           : 'Einsätze' ?></strong>, alle Ruhesegmente und alle Tracks<?php
             if ($tag['vehicle_name'] !== null && $tag['vehicle_name'] !== '') {
                 echo ' — Rettungsmittel ' . e((string)$tag['vehicle_name']);
             } ?>.</p>
      <?php endif; ?>

      <?= ui_meldung_markup('fehler', 'Dieser Schritt lässt sich nicht rückgängig '
          . 'machen. Die Daten sind danach unwiederbringlich fort — auch die '
          . 'verschlüsselten Angaben.') ?>

      <p class="feld-hinweis">Anschließend werden die betroffenen Einsätze für die
         Uhr gesperrt, damit sie nicht durch Nachlieferungen erneut angelegt
         werden.</p>

      <?php /* Die Bestätigung steht IN der Karte, nicht in einer
               Speichern-Leiste: Die Leiste gehört zu einem Formular, das man
               ausfüllt und dessen Stand man verlieren kann. Hier gibt es
               nichts auszufüllen — nur eine Entscheidung, und die gehört
               unter den Text, der sie begründet. */ ?>
      <form method="post" action="papierkorb.php">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="<?= $istTag ? 'purge_day' : 'purge_mission' ?>">
        <input type="hidden" name="d" value="<?= (int)$dayId ?>">
        <input type="hidden" name="id" value="<?= (int)$id ?>">
        <input type="hidden" name="confirm" value="ja">
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Ja, endgültig löschen', 'art' => 'gefahr',
                        'symbol' => 'korb']) ?>
          <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                        'href' => 'papierkorb.php']) ?>
        </div>
      </form>

    <?php ui_karte_ende(); ?>

    <?php /* AKTIVE EINTRÄGE AM GELÖSCHTEN TAG (Backlog Nr. 33). Sie gehen
             seit Web 8.0.0 mit — vorher blieben sie ohne Diensttag zurück und
             waren danach halb sichtbar. Deshalb stehen sie hier EINZELN und
             als Zeilen mit ihren eigenen Wegen, nicht als Aufzählung: Wer
             einen behalten will, muss ihn erkennen und erreichen können.

             Die Karte steht UNTER der Bestätigung, obwohl sie eine Warnung
             ist. Das ist Absicht: Sie erscheint fast nie, und stünde sie
             oben, schöbe sie im Normalfall nichts und im Ausnahmefall die
             Entscheidung aus dem Bild. */ ?>
    <?php if ($istTag && ($aktiv['einsaetze'] || $aktiv['segmente'])): ?>
      <?php ui_karte_start(['titel' => 'Aktives an diesem Diensttag',
                            'plakette' => ui_plakette('wird mitgelöscht', ['ton' => 'rot'])]); ?>
        <?= ui_meldung_markup('warn', 'Diese Einträge sind nicht gelöscht. '
            . 'Wer einen davon behalten will, verschiebt ihn vorher an einen '
            . 'anderen Diensttag.') ?>
        <?php foreach ($aktiv['einsaetze'] as $a):
              $abez = 'Einsatz vom ' . fmt_local((string)$a['started_at'], 'd.m.Y')
                    . ', ' . fmt_local((string)$a['started_at']) . ' Uhr';
              ui_zeile([
                  'text' => $abez,
                  'aktionen' => ui_zeilenaktionen([
                      'titel' => $abez,
                      'eintraege' => [
                          ['text' => 'Ansehen', 'symbol' => 'lupe',
                           'href' => 'einsatz.php?id=' . (int)$a['id']],
                          ['text' => 'Verschieben', 'symbol' => 'tausch',
                           'href' => 'einsatz_verschieben.php?id=' . (int)$a['id']],
                      ],
                  ]),
              ]);
        endforeach; ?>
        <?php if ($aktiv['segmente'] > 0): ?>
          <?php ui_zeile([
              'text'  => (int)$aktiv['segmente'] === 1
                       ? '1 Ruhesegment' : (int)$aktiv['segmente'] . ' Ruhesegmente',
              'klein' => 'Ruhesegmente lassen sich nicht verschieben.',
          ]); ?>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

  <?php endif; ?>
<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
