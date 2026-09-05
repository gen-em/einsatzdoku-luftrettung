<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/speicher_lib.php';
require_once __DIR__ . '/wartung_lib.php';

/**
 * BETRIEB → SERVEREINSTELLUNGEN (S8/AP2, E-S8-05, E-S8-18; Mockup 07 Fassung 2).
 *
 * WAS HIER STEHT UND WARUM. Die Speichergrenze und die Warnschwellen lagen
 * unter „Backups", zwischen der Erinnerung und der Aufbewahrung. Sie gehoeren
 * nicht dorthin: Sie wirken auf die Konto-Backups UND auf die Komplett-Staende
 * (B-S8-06), und die Komplett-Seite verwies bisher mit einem Satz auf sie. Das
 * war einer der Gruende fuer Nr. 79.
 *
 * Jetzt liegen Grenze, Schwellen und Belegung an EINER Stelle — und zwar im
 * Betrieb, wo die uebrigen Einstellungen der Installation stehen. Was je Konto
 * gilt (Erinnerung, Aufbewahrung, Admin-Mail), bleibt bei den Konto-Backups.
 *
 * DIE SEITE IST EINSPALTIG AUF LESEBREITE, auch am Schreibtisch: Sie traegt
 * zwei Karten, und ein zweispaltiges Raster fuer zwei Karten waere Raster um
 * des Rasters willen (E-S8-18 — Zweispaltigkeit gilt ab mehr als vier Karten).
 */

$pdo = db();
$notice = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'speicher') {
    csrf_check();
    $teile = [];

    /* ---- Speichergrenze (E-S2-15, zieht mit S8 hierher) ---------------- */
    $gb = str_replace(',', '.', trim((string)($_POST['grenze'] ?? '')));
    if (!is_numeric($gb) || (float)$gb <= 0 || (float)$gb > 10000) {
        $error = 'Bitte eine Speichergrenze zwischen 0,1 und 10000 GB angeben.';
    } elseif (abs((float)$gb * 1024 * 1024 * 1024 - edbak_grenze_bytes()) > 1) {
        edbak_marke_setzen('adminbackup_grenze_gb', (string)(float)$gb);
        /* DIE GEMELDETEN SCHWELLEN VERGESSEN. Eine neue Grenze macht aus
         * denselben Bytes einen anderen Prozentsatz — was bei der alten Grenze
         * gemeldet war, ist bei der neuen eine andere Aussage. Ohne dieses
         * Zuruecksetzen bliebe eine Warnung aus, die nach der Änderung fällig
         * wäre. */
        edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
        edbak_marke_setzen('adminbackup_schwellen_offen', '');
        $teile[] = 'Speichergrenze ' . $gb . ' GB';
    }

    /* ---- Warnschwellen ------------------------------------------------- */
    if ($error === null) {
        $roh = trim((string)($_POST['schwellen'] ?? ''));
        $neu = [];
        foreach (explode(',', $roh) as $t) {
            $t = trim($t);
            if ($t === '') { continue; }
            if (!ctype_digit($t) || (int)$t < 1 || (int)$t > 100) {
                $error = 'Warnschwellen sind ganze Zahlen zwischen 1 und 100, '
                       . 'durch Komma getrennt (z. B. „70, 90").';
                break;
            }
            $neu[(int)$t] = true;
        }
        if ($error === null) {
            $neu = array_keys($neu); sort($neu);
            if ($neu !== edbak_schwellen()) {
                edbak_marke_setzen('adminbackup_schwellen', implode(',', $neu));
                edbak_marke_setzen('adminbackup_schwellen_gemeldet', '');
                edbak_marke_setzen('adminbackup_schwellen_offen', '');
                $teile[] = $neu ? 'Warnschwellen ' . implode(' / ', $neu) . ' %'
                                : 'Warnschwellen aus';
            }
        }
    }

    /* ---- Webspace laut Hosting (neu, E-S8-18) --------------------------- */
    if ($error === null) {
        $roh = str_replace(',', '.', trim((string)($_POST['webspace'] ?? '')));
        if ($roh === '') {
            if (speicher_webspace_bytes() > 0) {
                speicher_webspace_setzen(0);
                $teile[] = 'Webspace-Angabe entfernt';
            }
        } elseif (!is_numeric($roh) || (float)$roh <= 0 || (float)$roh > 100000) {
            $error = 'Der Webspace ist eine Zahl in GB (oder leer, wenn die Angabe fehlt).';
        } elseif (abs((float)$roh * 1024 * 1024 * 1024 - speicher_webspace_bytes()) > 1) {
            speicher_webspace_setzen((float)$roh);
            $teile[] = 'Webspace ' . $roh . ' GB';
        }
    }

    if ($error === null) {
        $notice = $teile ? implode(', ', $teile) . ' gespeichert.'
                         : 'Es gab nichts zu ändern.';
    }
}

$sp = speicher_uebersicht();

/**
 * Ein Balken mit Segmenten und Legende (Baustein `.speicher-balken`).
 *
 * DIE BREITE STEHT INLINE, DIE FARBE NICHT. Die Breite ist ein gerechneter
 * Wert und kann gar nicht anders als am Element stehen; die Farbe kommt aus
 * einer Klasse, damit kein Hexwert und kein Token in das Markup wandert
 * (`CLAUDE.md` 5).
 *
 * OHNE BEZUG KEINE ANTEILE. Fehlt die Webspace-Angabe, werden die Segmente
 * anteilig ZUEINANDER gezeichnet und die Legende nennt nur die Summe — der
 * Balken zeigt dann die Zusammensetzung, nicht die Fuellung. Alles andere
 * hiesse, eine Bezugsgroesse zu erfinden.
 *
 * @param list<array{klasse:string,bytes:int,text:string}> $teile
 */
function speicher_balken(array $teile, int $bezug, array $schwellen): string
{
    $summe = 0;
    foreach ($teile as $t) { $summe += (int)$t['bytes']; }
    $nenner = $bezug > 0 ? $bezug : max(1, $summe);
    $frei   = $bezug > 0 ? max(0, $bezug - $summe) : 0;

    $h = '<div class="speicher-balken">';
    foreach ($teile as $t) {
        $p = $nenner > 0 ? (float)$t['bytes'] * 100 / $nenner : 0.0;
        if ($p <= 0) { continue; }
        $h .= '<span class="' . ui_e($t['klasse']) . '" style="width:'
            . number_format(min(100, $p), 3, '.', '') . '%"></span>';
    }
    /* Der Schwellenstrich sitzt als leeres Segment an seiner Stelle — ohne
     * absolute Positionierung und ohne zweite Ebene: Er ist ein Punkt AUF dem
     * Balken, und der Balken ist ein Flex-Behälter. */
    if ($bezug > 0 && $schwellen) {
        $erste  = (int)min($schwellen);
        $bisher = $nenner > 0 ? $summe * 100 / $nenner : 0;
        if ($erste > $bisher) {
            $h .= '<span class="speicher-luecke" style="width:'
                . number_format(max(0, $erste - $bisher), 3, '.', '') . '%"></span>';
            $h .= '<span class="speicher-marke"></span>';
        }
    }
    $h .= '</div>';

    $h .= '<div class="speicher-legende">';
    foreach ($teile as $t) {
        if ((int)$t['bytes'] <= 0) { continue; }
        $h .= '<span><i class="' . ui_e($t['klasse']) . '"></i>'
            . ui_e($t['text']) . ' ' . edbak_groesse_text((int)$t['bytes']) . '</span>';
    }
    if ($bezug > 0) {
        $h .= '<span><i class="sb-frei"></i>frei ' . edbak_groesse_text($frei) . '</span>';
        if ($schwellen) {
            $h .= '<span>Warnschwelle ' . (int)min($schwellen) . ' %</span>';
        }
    }
    return $h . '</div>';
}

ui_seite_start(['titel' => 'Servereinstellungen']);
?>

<?php /* Lesespalte: zwei Karten, viel Erklärtext (E-S8-18, Mockup 07). */ ?>
<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_server', 'lesespalte' => true]); ?>

  <?php ui_titelzeile(['titel' => 'Servereinstellungen']); ?>
  <?= wartung_balken() ?>

  <?php if ($notice !== null): ?><?= ui_meldung_markup('ok', $notice) ?><?php endif; ?>
  <?php if ($error !== null): ?><?= ui_meldung_markup('fehler', $error) ?><?php endif; ?>

  <?php ui_karte_start(['titel' => 'Speicher', 'id' => 'k-speicher',
      'zahl' => $sp['stand'] !== null
          ? 'Stand ' . fmt_local((string)$sp['stand'], 'd.m.Y H:i')
          : 'noch nicht gemessen',
      'plakette' => $sp['backups']['bezug'] > 0
          ? ui_plakette($sp['backups']['prozent'] . ' %',
                        ['ton' => speicher_ton($sp['backups']['prozent'], $sp['schwellen'])])
          : '']); ?>

    <?php if ($sp['stand'] === null): ?>
      <?= ui_meldung_markup('info', 'Datenbank und Dateien sind noch nicht gemessen. '
          . 'Die Messung läuft einmal täglich im Aufräumjob mit; bis dahin zeigt '
          . '„Installation gesamt" nur die Backups.') ?>
    <?php endif; ?>

    <h3 class="listen-form-titel">Backups
      <span class="feld-klein-inline"><?= edbak_groesse_text($sp['backups']['summe']) ?>
        von <?= edbak_groesse_text($sp['backups']['bezug']) ?> Grenze ·
        <?= (int)$sp['backups']['prozent'] ?> %</span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-konto',    'bytes' => $sp['backups']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['backups']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['backups']['bezug'], $sp['schwellen']) ?>

    <h3 class="listen-form-titel">Installation gesamt
      <span class="feld-klein-inline"><?= edbak_groesse_text($sp['gesamt']['summe']) ?><?php
        if ($sp['gesamt']['bezug'] > 0): ?> von
        <?= edbak_groesse_text($sp['gesamt']['bezug']) ?> Webspace ·
        <?= (int)$sp['gesamt']['prozent'] ?> %<?php
        else: ?> — ohne Webspace-Angabe kein Anteil<?php endif; ?></span></h3>
    <?= speicher_balken([
          ['klasse' => 'sb-db',       'bytes' => $sp['gesamt']['datenbank'],
           'text' => 'Datenbank'],
          ['klasse' => 'sb-dateien',  'bytes' => $sp['gesamt']['dateien'],
           'text' => 'Dateien'],
          ['klasse' => 'sb-konto',    'bytes' => $sp['gesamt']['konto'],
           'text' => 'Konto-Backups'],
          ['klasse' => 'sb-komplett', 'bytes' => $sp['gesamt']['komplett'],
           'text' => 'Komplett-Backups'],
        ], $sp['gesamt']['bezug'], $sp['schwellen']) ?>

    <p class="feld-klein">Datenbank aus <code>information_schema</code> (Daten und
       Indizes), Dateien aus dem Verzeichnislauf über das Anwendungsverzeichnis
       (Code, Symbole, Logos, APK) — ohne <code>sicherungen/</code>, die zählen
       bei den Backups. Der freie Webspace wird <strong>nicht gemessen</strong>:
       <code>disk_free_space()</code> zeigt auf geteiltem Hosting den Datenträger
       des Hosts, nicht die Quota. Versendete Pakete auf Backup-Zielen zählen
       nirgends mit — sie liegen außerhalb dieses Webspace.</p>

    <form method="post" action="betrieb_server.php">
      <?= csrf_field() ?><input type="hidden" name="action" value="speicher">
      <div class="fld-reihe">
        <?php ui_feld(['name' => 'grenze', 'label' => 'Speichergrenze Backups',
            'wert' => rtrim(rtrim(number_format(
                          edbak_grenze_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.'),
            'klein' => 'GB für Konto-Backups und Komplett-Backups zusammen. Ist sie '
                     . 'erreicht, wird nicht mehr gesichert; gelöscht wird nichts.']); ?>
        <?php ui_feld(['name' => 'schwellen', 'label' => 'Warnschwellen',
            'wert' => implode(', ', edbak_schwellen()),
            'klein' => 'Prozent, durch Komma getrennt — gelten für beide Balken. '
                     . 'Je Schwelle einmal eine Meldung.']); ?>
      </div>
      <?php ui_feld(['name' => 'webspace', 'label' => 'Webspace laut Hosting',
          'label_zusatz' => 'optional',
          'wert' => speicher_webspace_bytes() > 0
              ? rtrim(rtrim(number_format(
                    speicher_webspace_bytes() / (1024 * 1024 * 1024), 2, '.', ''), '0'), '.')
              : '',
          'klein' => 'GB, aus dem Hosting-Tarif abgelesen. Ohne Angabe zeigt '
                   . '„Installation gesamt" nur die Summe — ohne Anteil und ohne '
                   . 'Warnung.']); ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php
      /* ---- Ablage und Reste: rein lesend (AB-08, zieht mit S8 hierher) --- */
      ui_zeile([
        'text'  => 'Ablage',
        'klein' => (string)$sp['ablage']['pfad'],
        'plaketten' => $sp['ablage']['ok']
            ? ui_plakette('beschreibbar', ['ton' => 'blau'])
            : ui_plakette('nicht beschreibbar', ['ton' => 'rot']),
      ]);
      if (!$sp['ablage']['ok'] && $sp['ablage']['grund'] !== null) {
          echo ui_meldung_markup('fehler', (string)$sp['ablage']['grund']);
      }
      ui_zeile([
        'text'  => 'Reste abgebrochener Läufe',
        'klein' => 'Bauordner und .tmp-Dateien — werden vom Aufräumjob entfernt',
        'plaketten' => ui_plakette((string)$sp['reste'],
            ['ton' => $sp['reste'] > 0 ? 'orange' : 'neutral']),
      ]);
      ui_zeile([
        'text'  => 'Pakete in der Ablage',
        'klein' => $sp['ordner'] . ' Konten mit mindestens einem Konto-Backup',
        'plaketten' => ui_plakette((string)$sp['pakete'], ['ton' => 'neutral']),
      ]);
    ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                        'vorschau' => 'Grenze · Schwellen · Webspace']); ?>
    <p class="feld-hinweis"><strong>Die Grenze gilt nur für Backups.</strong> Die
       Datenbank wächst mit jedem Einsatz und wird nie angehalten — eine Grenze
       darauf hieße, die Anwendung anzuhalten. Ist die Grenze erreicht, wird
       <em>nicht mehr gesichert</em>; gelöscht wird nie von selbst.</p>
    <p class="feld-hinweis"><strong>Warnschwellen</strong> melden einmal je
       Schwelle, für beide Balken. Mit eingerichtetem SMTP geht die Meldung
       zusätzlich an alle mit Verwaltungsrechten. Wer Grenze oder Schwellen
       ändert, setzt die Meldungen zurück: Dieselben Bytes sind bei einer
       anderen Grenze eine andere Aussage.</p>
    <p class="feld-hinweis"><strong>Der Webspace ist eine Angabe, keine
       Messung.</strong> Er steht im Hosting-Tarif und lässt sich von hier aus
       nicht ermitteln. Ohne ihn bleibt der zweite Balken eine Zusammensetzung
       ohne Füllstand — das ist ehrlicher als eine erfundene Bezugsgröße.</p>
    <p class="feld-hinweis"><strong>Gemessen wird einmal täglich</strong>, im
       Aufräumjob. Der Stand steht im Kartenkopf. Die Backups werden dagegen bei
       jedem Aufruf gewogen — ihr Verzeichnis ist klein genug dafür, und ihre
       Zahl entscheidet, ob noch gesichert werden darf.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
