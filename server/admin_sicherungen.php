<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/adminbackup_lib.php';

/**
 * Admin-Sicherungen: Übersicht, Erzeugen, Einspielen, Freigeben, Löschen (A8).
 *
 * ZWEI ABSCHNITTE, ZWEI QUELLEN (E19)
 *   1. Bestehende Konten aus `users`, je Zeile mit ihren Sicherungen.
 *   2. Verwaiste Sicherungen — Ordner, deren Kennung in `users` nicht mehr
 *      vorkommt. Die Angaben stammen aus der Begleitdatei.
 *
 * Abschnitt 2 trägt den eigentlichen Anwendungsfall: das gelöschte und neu
 * aufgesetzte Konto. Es trägt eine neue Kennung, zum alten Ordner existiert
 * keine Datenbankzeile mehr — eine Liste allein aus `users` würde genau die
 * Sicherungen verschweigen, um derentwillen es diese Seite gibt.
 *
 * DIE KONTOKENNUNG ERSCHEINT NIRGENDS IN DER OBERFLÄCHE (A8.7). Sie ist ein
 * interner Ordnername, keine Angabe für Menschen — und die zweite Schranke
 * gegen den Abruf über den Browser. Sie steckt nur in den Formularfeldern, weil
 * die Seite ohne sie nicht sagen könnte, welcher Ordner gemeint ist.
 */

$notice = null; $error = null; $bericht = null;

/** Zielkonto samt Kennung lesen — für Rückspielung und Freigabe. */
function ziel_konto(int $id): ?array
{
    $st = db()->prepare('SELECT id, email, name, account_key FROM users WHERE id = ?');
    $st->execute([$id]);
    $r = $st->fetch();
    return $r ?: null;
}

/**
 * Harte Bestätigung: die E-Mail-Adresse des ZIELKONTOS muss abgetippt sein (E21).
 *
 * Abgetippt wird die Adresse des Ziels, nicht die der Herkunft. Das Risiko ist
 * nicht Datenverlust — edbak_restore() ergänzt und ersetzt nicht —, sondern das
 * Einspielen FREMDER Daten in ein falsches Konto. Abgesichert werden muss
 * deshalb das Ziel. Geprüft wird serverseitig, nach dem Muster von
 * admin_user.php: Ein Browser-Dialog liesse sich umgehen.
 */
function bestaetigung_passt(string $eingabe, string $soll): bool
{
    return strcasecmp(trim($eingabe), trim($soll)) === 0;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $action  = (string)($_POST['action'] ?? '');
    /* Aus der Oberfläche kommt der Handgriff, nicht die Kennung (Kriterium 49).
     * Lässt er sich nicht auflösen, gibt es den Ordner nicht — dann laufen die
     * Aktionen unten ins Leere und melden das, statt auf einem geratenen Pfad
     * zu arbeiten. */
    $kennung = edbak_kennung_aus_handgriff((string)($_POST['handgriff'] ?? '')) ?? '';
    $datei   = (string)($_POST['datei'] ?? '');

    if ($action === 'intervall') {
        $tage = (int)($_POST['tage'] ?? 0);
        if ($tage < 1 || $tage > 3650) {
            $error = 'Bitte ein Intervall zwischen 1 und 3650 Tagen angeben.';
        } else {
            edbak_marke_setzen('adminbackup_intervall', (string)$tage);
            $notice = 'Erinnerungsintervall gespeichert: alle ' . $tage . ' Tage.';
        }
    }

    /* ---- Sichern: einzeln, Auswahl, alle (A8.3) ------------------------- */
    if ($action === 'sichern' || $action === 'sichern_auswahl' || $action === 'sichern_alle') {
        if ($action === 'sichern') {
            $ids = [(int)($_POST['user_id'] ?? 0)];
        } elseif ($action === 'sichern_auswahl') {
            $ids = array_map('intval', (array)($_POST['auswahl'] ?? []));
        } else {
            $ids = array_map('intval', db()->query('SELECT id FROM users ORDER BY email')
                                            ->fetchAll(PDO::FETCH_COLUMN));
        }
        $ids = array_values(array_filter($ids, static fn($i) => $i > 0));
        if (!$ids) {
            $error = 'Es war kein Konto ausgewählt.';
        } else {
            $gut = 0; $schlecht = [];
            foreach ($ids as $id) {
                [$ok, $grund, ] = edbak_sicherung_erzeugen($id);
                if ($ok) { $gut++; } else { $schlecht[] = $grund; }
            }
            $notice = $gut . ' ' . ($gut === 1 ? 'Sicherung' : 'Sicherungen') . ' erzeugt.';
            if ($schlecht) {
                /* Gleichlautende Gründe zusammenfassen: Bei „alle Konten"
                 * stünde derselbe Satz sonst dutzendfach untereinander und
                 * verdeckte den einen, der anders lautet. */
                $error = 'Nicht erzeugt: ' . implode(' · ', array_unique($schlecht));
            }
        }
    }

    /* ---- Einspielen (A8.6) ---------------------------------------------- */
    if ($action === 'einspielen') {
        $ziel = ziel_konto((int)($_POST['ziel_user'] ?? 0));
        $paket = edbak_paket_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Die Sicherung liess sich nicht lesen.';
        } elseif (!bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts eingespielt.';
        } else {
            [$weg, $warum] = edbak_weg($paket, $ziel);
            if ($weg === 'gesperrt') {
                $error = 'Einspielen nicht möglich. ' . $warum;
            } elseif ($weg === 'freigabe') {
                $error = 'Unmittelbares Einspielen ist gesperrt. ' . $warum
                       . ' Bitte stattdessen die Sicherung für dieses Konto freigeben.';
            } else {
                try {
                    $stats = edbak_restore((int)$ziel['id'], $paket['daten']);
                    $bericht = $stats;
                    $notice = 'Sicherung eingespielt in ' . $ziel['email'] . '.';
                } catch (Throwable $ex) {
                    $error = 'Das Einspielen ist fehlgeschlagen (Kennung '
                           . fehler_kennung($ex, 'adminbackup') . ').';
                }
            }
        }
    }

    /* ---- Freigeben und widerrufen (A8.6) -------------------------------- */
    if ($action === 'freigeben') {
        $ziel = ziel_konto((int)($_POST['ziel_user'] ?? 0));
        $paket = edbak_paket_lesen($kennung, $datei);
        if (!$ziel) {
            $error = 'Zielkonto nicht gefunden.';
        } elseif (!$paket) {
            $error = 'Die Sicherung liess sich nicht lesen.';
        } elseif (!bestaetigung_passt((string)($_POST['confirm_email'] ?? ''), (string)$ziel['email'])) {
            $error = 'Die eingegebene E-Mail-Adresse stimmt nicht mit der des '
                   . 'Zielkontos überein — es wurde nichts freigegeben.';
        } elseif (edbak_freigeben($kennung, $datei, (int)$ziel['id'])) {
            $notice = 'Freigegeben für ' . $ziel['email'] . '. Die NutzerIn sieht die '
                    . 'Sicherung jetzt im eigenen Backup-Bereich und spielt sie dort '
                    . 'mit ihrem Wiederherstellungsschlüssel ein.';
        } else {
            $error = 'Die Freigabe liess sich nicht speichern.';
        }
    }
    if ($action === 'widerrufen') {
        $notice = edbak_freigabe_widerrufen($kennung)
            ? 'Freigabe widerrufen.'
            : null;
        if ($notice === null) { $error = 'Die Freigabe liess sich nicht widerrufen.'; }
    }

    /* ---- Löschen (A8.8) -------------------------------------------------
     *
     * Die Härte der Bestätigung richtet sich danach, was verloren geht (E24):
     * Bleibt danach mindestens eine weitere Sicherung desselben Kontos, genügt
     * die übliche Rückfrage. Ist es die letzte oder gehört sie zu einem
     * verwaisten Ordner, ist zusätzlich die E-Mail-Adresse abzutippen.
     */
    if ($action === 'paket_loeschen' || $action === 'ordner_loeschen') {
        $hart  = ($_POST['hart'] ?? '') === '1';
        $sollAdresse = (string)($_POST['soll_email'] ?? '');
        $eingabe = (string)($_POST['confirm_email'] ?? '');
        $unlesbar = ($_POST['unlesbar'] ?? '') === '1';

        $bestaetigt = true;
        if ($hart) {
            $bestaetigt = $unlesbar
                /* Ist die Begleitdatei unlesbar, gibt es keine Adresse zum
                 * Abtippen. An ihre Stelle tritt eine ausdrückliche Bestätigung,
                 * dass eine nicht mehr zuordenbare Sicherung endgültig entfernt
                 * wird (Akzeptanzkriterium 64). */
                ? (($_POST['confirm_unlesbar'] ?? '') === 'ja')
                : bestaetigung_passt($eingabe, $sollAdresse);
        }

        if (!$bestaetigt) {
            $error = $unlesbar
                ? 'Ohne die ausdrückliche Bestätigung wurde nichts gelöscht.'
                : 'Die eingegebene E-Mail-Adresse stimmt nicht überein — es wurde '
                . 'nichts gelöscht.';
        } elseif ($action === 'paket_loeschen') {
            $notice = edbak_paket_loeschen($kennung, $datei)
                ? 'Sicherung gelöscht.' : null;
            if ($notice === null) { $error = 'Die Sicherung liess sich nicht löschen.'; }
        } else {
            $notice = edbak_ordner_loeschen($kennung)
                ? 'Alle Sicherungen dieses Ordners wurden gelöscht.' : null;
            if ($notice === null) {
                $error = 'Der Ordner liess sich nicht vollständig löschen. Enthält er '
                       . 'Dateien, die nicht von dieser Anwendung stammen, bleibt er '
                       . 'bewusst stehen.';
            }
        }
    }
}

[$ablageBereit, $ablageGrund] = edbak_ablage_bereit();
$u = edbak_uebersicht();
$erinnerung = edbak_erinnerung();
$konten = db()->query('SELECT id, email FROM users ORDER BY email')->fetchAll();

/** Umfang einer Sicherung als eine Zeile — nur Zahlen, nie Inhalte (A8.7). */
function umfang_text(array $p): string
{
    $z = $p['umfang'] ?? null;
    $teile = [];
    if (is_array($z)) {
        $teile[] = (int)($z['einsaetze'] ?? 0) . ' Einsätze';
        $teile[] = (int)($z['diensttage'] ?? $z['flugtage'] ?? 0) . ' Diensttage';
        $teile[] = (int)($z['ruhezeiten'] ?? 0) . ' Ruhezeiten';
    }
    $teile[] = number_format($p['groesse'] / 1024, 0, ',', '.') . ' KB';
    return implode(', ', $teile);
}

function zeitpunkt_text(?string $iso): string
{
    if (!$iso) { return 'unbekannt'; }
    try { return fmt_local(str_replace(['T', 'Z'], [' ', ''], $iso), 'd.m.Y H:i'); }
    catch (Throwable) { return $iso; }
}

ui_seite_start(['titel' => 'Sicherungen']);
ui_topbar('einstellungen');
?>

<div class="layout">
  <?php ui_settings_sidebar('admin_sicherungen'); ?>

<main class="page">
  <h1>Sicherungen</h1>

  <?php if ($notice): ?><p class="alert alert-info"><?= e($notice) ?></p><?php endif; ?>
  <?php if ($error): ?><p class="alert"><?= e($error) ?></p><?php endif; ?>

  <?php if (!$ablageBereit): ?>
    <p class="alert"><?= e((string)$ablageGrund) ?></p>
  <?php endif; ?>

  <?php /* Rückmeldung nach der Rückspielung (E22). Die übersprungenen Einträge
           stehen NACH GRÜNDEN GETRENNT da: Wer eine Wiederherstellung beurteilen
           muss, braucht den Unterschied zwischen "war schon da" (gut) und
           "Aufbau unbrauchbar" (schlecht). Eine einzige Zahl beantwortet die
           Frage nicht, die man in diesem Moment hat. */ ?>
  <?php if ($bericht !== null): ?>
    <div class="keybox">
      <strong>Ergebnis der Rückspielung</strong>
      <p class="muted">Eine Rückspielung <strong>ergänzt</strong>, sie ersetzt nicht.
         Bereits vorhandene Einträge bleiben unverändert und werden übersprungen.</p>
      <pre class="small"><?= e(json_encode($bericht, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>
  <?php endif; ?>

  <p class="muted">Sicherungen entstehen <strong>ausschliesslich hier und von Hand</strong>.
     Es gibt keinen Zeitplan — auf diesem Webspace läuft kein Cron. Je Konto werden
     höchstens <?= EDBAK_MAX_JE_KONTO ?> Sicherungen aufbewahrt; die vierte verdrängt
     die älteste. Nach Alter wird <strong>nie</strong> etwas entfernt.</p>

  <p class="muted">Administration sieht zu keinem Zeitpunkt Klartext der geschützten
     Angaben. In der Sicherung stecken sie als Chiffretext, genau wie in der
     Datenbank — lesbar werden sie erst im Browser der NutzerIn.</p>

  <?php /* ---- Erinnerung (A8.4) ------------------------------------------
     * Muster wie die Wartungswarnung in update.php: erst sagen, was ist, dann
     * was daraus folgt. Ein Hinweis ohne Handlungsanweisung erzeugt nur
     * Unbehagen. */ ?>
  <h2>Erinnerung</h2>
  <?php if ($erinnerung['letzte'] === null): ?>
    <p class="alert alert-warn">Es wurde noch <strong>nie</strong> eine Sicherung
       erzeugt.</p>
  <?php elseif ($erinnerung['faellig']): ?>
    <p class="alert alert-warn">Letzte Sicherung vor
       <strong><?= (int)$erinnerung['tage'] ?> Tagen</strong>
       (<?= e((string)$erinnerung['letzte']) ?>) — das eingestellte Intervall von
       <?= (int)$erinnerung['intervall'] ?> Tagen ist überschritten.</p>
  <?php else: ?>
    <p class="muted">Letzte Sicherung: <strong><?= e((string)$erinnerung['letzte']) ?></strong>
       (vor <?= (int)$erinnerung['tage'] ?> Tagen). Intervall:
       <?= (int)$erinnerung['intervall'] ?> Tage.</p>
  <?php endif; ?>
  <form method="post" class="settings-form">
    <?= csrf_field() ?><input type="hidden" name="action" value="intervall">
    <label>Erinnern nach (Tage)
      <input type="number" name="tage" min="1" max="3650"
             value="<?= (int)$erinnerung['intervall'] ?>"></label>
    <button class="btn-primary">Intervall speichern</button>
  </form>

  <hr class="sep">
  <h2>Konten</h2>

  <form method="post">
    <?= csrf_field() ?>
    <table class="data">
      <thead><tr>
        <th></th><th>Konto</th><th>Sicherungen</th><th class="actions">Aktionen</th>
      </tr></thead>
      <tbody>
      <?php foreach ($u['konten'] as $k): ?>
        <tr>
          <td><input type="checkbox" name="auswahl[]" value="<?= (int)$k['user_id'] ?>"></td>
          <td>
            <strong><?= e($k['name'] ?: '—') ?></strong><br>
            <span class="muted small"><?= e($k['email']) ?></span>
            <?php if (!$k['kennung_ok']): ?>
              <br><span class="muted small">Ohne Kontokennung — bitte die Wartung
                 aufrufen und die Migration ausführen.</span>
            <?php endif; ?>
          </td>
          <td>
            <?php if (!$k['pakete']): ?>
              <span class="muted">keine</span>
            <?php else: ?>
              <ul class="small">
              <?php foreach ($k['pakete'] as $p): ?>
                <li><?= e(zeitpunkt_text($p['erzeugt'])) ?> — <?= e(umfang_text($p)) ?></li>
              <?php endforeach; ?>
              </ul>
            <?php endif; ?>
            <?php if ($k['freigabe']): ?>
              <p class="muted small">Freigegeben für ein Zielkonto, noch nicht eingelöst.</p>
            <?php endif; ?>
          </td>
          <td class="actions">
            <?php /* Der Knopf gehoert ueber form= zum eigenen Formular weiter
                     unten, obwohl er in der Auswahlliste steht: Innerhalb des
                     Auswahlformulars wuerde er dessen Kaestchen mitsenden, und
                     "Jetzt sichern" in Zeile 3 hiesse dann in Wahrheit
                     "Zeile 3 und alles Angekreuzte". */ ?>
            <button class="btn-primary" form="sichern-<?= (int)$k['user_id'] ?>">Jetzt sichern</button>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
    <p>
      <button class="btn-primary" name="action" value="sichern_auswahl">Auswahl sichern</button>
      <button class="btn-plain" name="action" value="sichern_alle"
              data-confirm="Für alle Konten eine Sicherung erzeugen?"
              data-confirm-ok="Alle sichern" data-confirm-tone="normal">Alle sichern</button>
    </p>
  </form>

  <?php foreach ($u['konten'] as $k): ?>
    <form method="post" id="sichern-<?= (int)$k['user_id'] ?>" hidden>
      <?= csrf_field() ?><input type="hidden" name="action" value="sichern">
      <input type="hidden" name="user_id" value="<?= (int)$k['user_id'] ?>">
    </form>
  <?php endforeach; ?>

  <hr class="sep">
  <h2>Sicherungen einspielen</h2>
  <p class="muted">Eine Rückspielung <strong>ergänzt</strong>, sie ersetzt nicht:
     Bereits vorhandene Einträge bleiben unverändert. Vor jedem Einspielen ist die
     E-Mail-Adresse des <strong>Zielkontos</strong> abzutippen — das Risiko ist nicht
     Datenverlust, sondern das Einspielen fremder Daten in ein falsches Konto.</p>

  <?php
  /* Alle Pakete beider Abschnitte in einer Liste: Die Entscheidung „direkt
     einspielen oder freigeben" hängt am Vergleich der Kennungen und nicht
     daran, in welchem Abschnitt die Sicherung steht. */
  $alle = [];
  foreach ($u['konten'] as $k) {
      foreach ($k['pakete'] as $p) {
          $alle[] = ['kennung' => $k['account_key'], 'paket' => $p,
                     'herkunft' => $k['email'], 'verwaist' => false,
                     'lesbar' => true, 'freigabe' => $k['freigabe']];
      }
  }
  foreach ($u['verwaist'] as $v) {
      foreach ($v['pakete'] as $p) {
          $alle[] = ['kennung' => $v['account_key'], 'paket' => $p,
                     'herkunft' => $v['email'], 'verwaist' => true,
                     'lesbar' => $v['lesbar'], 'freigabe' => $v['freigabe']];
      }
  }
  ?>

  <?php /* ---- EINE TABELLE STATT EINER KACHEL JE SICHERUNG (Web 7.0.0) -----
     *
     * Vorher stand hier je Sicherung ein Kasten mit Herkunftszeile, einem
     * vollständigen Einspiel-Formular (Zielkonto, Bestätigungsfeld, zwei
     * Schaltflächen, Erläuterung) und einem Lösch-Formular. Bei fünf Konten mit
     * je drei Sicherungen waren das fünfzehn solcher Kästen — mehrere
     * Bildschirmseiten, auf denen fünfzehnmal dasselbe stand und die eine
     * gesuchte Zeile nicht zu finden war.
     *
     * Jetzt: eine Zeile je Sicherung mit dem, was man zum Suchen braucht
     * (Zeitpunkt, Herkunft, Umfang, Zustand). Die Formulare stecken in einem
     * aufklappbaren Feld dahinter — sie erscheinen für die EINE Sicherung, mit
     * der man gerade etwas tun will.
     *
     * NICHTS AN DEN SICHERUNGEN SELBST ÄNDERT SICH: Dieselben Formulare,
     * dieselben Bestätigungen, dieselbe Abtippregel für die Zielkonto-Adresse.
     * Die Rückfragen sind der Schutz vor dem Einspielen in ein falsches Konto,
     * und der wird durch eine Umgestaltung nicht weicher. */ ?>
  <?php if (!$alle): ?>
    <p class="muted">Es liegt noch keine Sicherung vor.</p>
  <?php else: ?>
    <table class="data sictab">
      <thead><tr>
        <th>Zeitpunkt</th><th>Herkunft</th><th>Umfang</th><th>Zustand</th><th class="th-act">Aktionen</th>
      </tr></thead>
      <tbody>
      <?php foreach ($alle as $e):
            /* Härte der Löschbestätigung nach E24: Bleibt danach noch eine
               weitere Sicherung desselben Kontos, ist die Löschung folgenlos. */
            $weitere = count(array_filter($alle, static fn($x) => $x['kennung'] === $e['kennung'])) > 1;
            $hart = !$weitere || $e['verwaist'];
            $unlesbar = $hart && !$e['lesbar']; ?>
        <tr>
          <td class="mono"><?= e(zeitpunkt_text($e['paket']['erzeugt'])) ?></td>
          <td><?php if ($e['lesbar'] && $e['herkunft']): ?>
                <?= e((string)$e['herkunft']) ?>
              <?php else: ?>
                <em class="muted">unbekannt — Begleitdatei nicht lesbar</em>
              <?php endif; ?></td>
          <td class="muted small"><?= e(umfang_text($e['paket'])) ?></td>
          <td class="small"><?php
                $zustand = [];
                if ($e['verwaist']) { $zustand[] = '<strong>verwaist</strong>'; }
                if ($e['freigabe']) { $zustand[] = 'freigegeben'; }
                if ($hart)          { $zustand[] = 'letzte dieses Kontos'; }
                echo $zustand ? implode(' · ', $zustand) : '<span class="muted">—</span>'; ?></td>
          <td class="th-act">
            <details class="zeilenmenu">
              <summary class="btn-plain">Einspielen / Löschen</summary>
              <div class="zeilenmenu-inhalt">
                <?php if ($e['verwaist']): ?>
                  <p class="muted small">Zu dieser Sicherung existiert kein Konto
                     mehr (Fall „Konto gelöscht und neu aufgesetzt").</p>
                <?php endif; ?>
                <form method="post" class="settings-form">
                  <?= csrf_field() ?>
                  <input type="hidden" name="handgriff" value="<?= e(edbak_handgriff($e['kennung'])) ?>">
                  <input type="hidden" name="datei" value="<?= e($e['paket']['datei']) ?>">
                  <label>Zielkonto
                    <select name="ziel_user" required>
                      <option value="">— bitte wählen —</option>
                      <?php foreach ($konten as $z): ?>
                        <option value="<?= (int)$z['id'] ?>"><?= e($z['email']) ?></option>
                      <?php endforeach; ?>
                    </select></label>
                  <label>Zur Bestätigung die E-Mail-Adresse des Zielkontos abtippen
                    <input type="text" name="confirm_email" autocomplete="off" required></label>
                  <p>
                    <button class="btn-primary" name="action" value="einspielen"
                            data-confirm="Sicherung in das gewählte Konto einspielen? Vorhandene Einträge bleiben unverändert."
                            data-confirm-ok="Einspielen" data-confirm-tone="normal">Einspielen</button>
                    <button class="btn-plain" name="action" value="freigeben"
                            data-confirm="Sicherung für das gewählte Konto freigeben? Die NutzerIn spielt sie dann selbst mit ihrem Wiederherstellungsschlüssel ein."
                            data-confirm-ok="Freigeben" data-confirm-tone="normal">Für NutzerIn freigeben</button>
                  </p>
                  <p class="muted small">Stimmt die Kennung im Paket mit der des Zielkontos
                     überein, lässt sich unmittelbar einspielen. Weicht sie ab, ist das
                     gesperrt — dann ist die Freigabe der Weg: Die geschützten Angaben sind
                     mit einem Inhaltsschlüssel verschlüsselt, den nur der
                     Wiederherstellungsschlüssel öffnet, und der liegt ausschliesslich bei
                     der NutzerIn.</p>
                </form>

                <form method="post" class="settings-form"
                      data-confirm="Diese Sicherung endgültig löschen? Es gibt keinen Papierkorb."
                      data-confirm-ok="Löschen">
                  <?= csrf_field() ?><input type="hidden" name="action" value="paket_loeschen">
                  <input type="hidden" name="handgriff" value="<?= e(edbak_handgriff($e['kennung'])) ?>">
                  <input type="hidden" name="datei" value="<?= e($e['paket']['datei']) ?>">
                  <input type="hidden" name="hart" value="<?= $hart ? '1' : '0' ?>">
                  <input type="hidden" name="unlesbar" value="<?= $unlesbar ? '1' : '0' ?>">
                  <input type="hidden" name="soll_email" value="<?= e((string)($e['herkunft'] ?? '')) ?>">
                  <?php if ($hart && !$unlesbar): ?>
                    <label>Letzte Sicherung dieses Kontos — zur Bestätigung die
                           E-Mail-Adresse <strong><?= e((string)$e['herkunft']) ?></strong> abtippen
                      <input type="text" name="confirm_email" autocomplete="off" required></label>
                  <?php elseif ($unlesbar): ?>
                    <label class="check"><input type="checkbox" name="confirm_unlesbar" value="ja" required>
                      Ich bestätige, dass eine <strong>nicht mehr zuordenbare</strong> Sicherung
                      endgültig entfernt wird.</label>
                  <?php endif; ?>
                  <button class="btn-red">Sicherung löschen</button>
                </form>
              </div>
            </details>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <hr class="sep">
  <h2>Verwaiste Sicherungen</h2>
  <p class="muted">Ordner, zu deren Kennung kein Konto mehr existiert — der Fall
     „Konto gelöscht und neu aufgesetzt". Ein Ordner lässt sich hier vollständig
     entfernen; das ist der einzige Weg, ihn loszuwerden.</p>

  <?php if (!$u['verwaist']): ?>
    <p class="muted">Keine.</p>
  <?php else: ?>
    <?php foreach ($u['verwaist'] as $v): ?>
      <div class="keybox">
        <?php if ($v['lesbar'] && $v['email']): ?>
          <strong><?= e($v['name'] ?: '—') ?></strong>
          <span class="muted">(<?= e((string)$v['email']) ?>)</span>
        <?php else: ?>
          <strong>Ordner ohne lesbare Begleitdatei</strong>
          <p class="muted small">Name und Adresse sind nicht bekannt. Der Ordner wird
             hier trotzdem aufgeführt — stillschweigend zu übergehen, was man nicht
             zuordnen kann, ist die schlechtere Auskunft.</p>
        <?php endif; ?>
        <p class="muted small"><?= count($v['pakete']) ?>
           <?= count($v['pakete']) === 1 ? 'Sicherung' : 'Sicherungen' ?></p>
        <?php if ($v['freigabe']): ?>
          <form method="post" class="settings-form">
            <?= csrf_field() ?><input type="hidden" name="action" value="widerrufen">
            <input type="hidden" name="handgriff" value="<?= e(edbak_handgriff($v['account_key'])) ?>">
            <p class="muted small">Für ein Zielkonto freigegeben, noch nicht eingelöst.</p>
            <button class="btn-plain">Freigabe widerrufen</button>
          </form>
        <?php endif; ?>
        <form method="post" class="settings-form"
              data-confirm="Alle Sicherungen dieses Ordners endgültig löschen?"
              data-confirm-ok="Endgültig löschen">
          <?= csrf_field() ?><input type="hidden" name="action" value="ordner_loeschen">
          <input type="hidden" name="handgriff" value="<?= e(edbak_handgriff($v['account_key'])) ?>">
          <input type="hidden" name="hart" value="1">
          <input type="hidden" name="unlesbar" value="<?= $v['lesbar'] ? '0' : '1' ?>">
          <input type="hidden" name="soll_email" value="<?= e((string)($v['email'] ?? '')) ?>">
          <?php if ($v['lesbar'] && $v['email']): ?>
            <label>Zur Bestätigung die E-Mail-Adresse
                   <strong><?= e((string)$v['email']) ?></strong> abtippen
              <input type="text" name="confirm_email" autocomplete="off" required></label>
          <?php else: ?>
            <label class="check"><input type="checkbox" name="confirm_unlesbar" value="ja" required>
              Ich bestätige, dass eine <strong>nicht mehr zuordenbare</strong> Sicherung
              endgültig entfernt wird.</label>
          <?php endif; ?>
          <button class="btn-red">Ordner vollständig löschen</button>
        </form>
      </div>
    <?php endforeach; ?>
  <?php endif; ?>

  <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/confirm.js') ?>" defer></script>
<?php ui_seite_ende(); ?>
