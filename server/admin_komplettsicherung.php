<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/komplett_lib.php';
require_once __DIR__ . '/jobs_lib.php';

/**
 * KOMPLETTSICHERUNG — die ganze Installation als versiegelter SQL-Dump
 * (E-S2-19 bis E-S2-21, S2/AP8).
 *
 * WARUM EINE EIGENE SEITE UND KEINE KARTE AUF „SICHERUNGEN". Dieselbe
 * Begründung wie bei den Sicherungszielen: „Sicherungen" ist seit P3/O9c die
 * Seite der REGELN — Aufbewahrung, Grenze, Schwellen. Hier steht eine Liste
 * von Ständen mit Handgriffen daran, dazu ein Lauf, der Minuten dauert und
 * einen Fortschritt hat. Das ist der Zuschnitt einer Seite.
 *
 * DREI SACHEN, DIE HIER ZUSAMMENKOMMEN
 *   1. Der ZEITPLAN — wie alt der jüngste Stand höchstens sein darf.
 *   2. Der LAUF selbst, mit Fortschritt und Wiederaufnahme.
 *   3. Die STÄNDE, jeder mit zwei Wegen heraus: unverschlüsselt für `mysql`
 *      und phpMyAdmin, oder unter einer Passphrase zum Weitergeben.
 *
 * DER DOWNLOAD IST EIN POST UND KEIN LINK. Für die unverschlüsselte Fassung
 * wäre ein Link bequem; für die Fassung mit Passphrase wäre er falsch, weil
 * die Passphrase dann in der Adresszeile stünde — und damit im Verlauf des
 * Browsers und im Zugriffsprotokoll des Servers. Zwei verschiedene Wege für
 * dieselbe Handlung wären ein Weg zu viel.
 */

/** Zeitbudget eines Durchgangs „Jetzt sichern" in Sekunden. */
const KOMP_BUDGET_SEITE = 20.0;

$notice = null; $error = null;

/* ---- Download: läuft VOR jeder Ausgabe und endet mit exit ---------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && (string)($_POST['action'] ?? '') === 'herunterladen') {
    csrf_check();
    $datei = (string)($_POST['datei'] ?? '');
    $art   = (string)($_POST['art'] ?? 'klar');
    $pw    = (string)($_POST['passphrase'] ?? '');
    $pfad  = komp_wurzel() . '/' . $datei;
    if (!komp_name_gueltig($datei) || !is_file($pfad)) {
        $error = 'Diesen Stand gibt es nicht (mehr).';
    } elseif ($art === 'pw' && strlen($pw) < 8) {
        $error = 'Die Passphrase muss mindestens 8 Zeichen haben. '
               . 'Es wurde nichts heruntergeladen.';
    } else {
        /* KEINE ZEITSCHRANKE FÜR DIE ÜBERTRAGUNG, und das ist die eine
         * begründete Ausnahme vom Z3-Budget „Serveranfrage <= 30 s".
         *
         * Das Budget gilt der ARBEIT: Kein Häppchen darf länger rechnen, als
         * ein Webserver wartet. Ein Download rechnet nicht — er schiebt
         * Bytes, und wie lange das dauert, entscheidet die Leitung der
         * Herunterladenden. Ein Abbruch nach 30 s wäre kein Schutz, sondern
         * eine Sicherung, die sich bei langsamer Verbindung nicht abholen
         * lässt.
         *
         * Der Speicher bleibt trotzdem bei einem halben Megabyte: Es wird
         * Block für Block entsiegelt und Block für Block ausgegeben. */
        @set_time_limit(0);
        while (ob_get_level() > 0) { ob_end_clean(); }
        $name = $art === 'pw'
            ? 'einsatzdoku-komplett-' . substr($datei, 0, 19) . '.edk'
            : 'einsatzdoku-komplett-' . substr($datei, 0, 19) . '.sql.gz';
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        header('Cache-Control: no-store');
        header('X-Content-Type-Options: nosniff');
        $hinaus = static function (string $s): void { echo $s; flush(); };
        try {
            if ($art === 'pw') { komp_ausgeben_passphrase($datei, $pw, $hinaus); }
            else               { komp_ausgeben_klar($datei, $hinaus); }
        } catch (Throwable $ex) {
            /* Die Kopfzeilen sind längst heraus; eine Fehlerseite geht nicht
             * mehr. Was bleibt, ist ein ABGEBROCHENER Download — besser als
             * eine halbe Datei, die aussieht wie eine ganze. Nachlesbar ist
             * er im Fehlerprotokoll. */
            error_log('komplett: Download „' . $datei . '" abgebrochen: ' . $ex->getMessage());
        }
        exit;
    }
}

/* ---- Die übrigen Handgriffe ---------------------------------------------- */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $error === null) {
    csrf_check();
    $aktion = (string)($_POST['action'] ?? '');

    if ($aktion === 'jetzt_sichern' || $aktion === 'fortsetzen') {
        if ($aktion === 'jetzt_sichern') {
            $r = komp_auftrag_starten();
            if (!$r['ok']) { $error = (string)$r['meldung']; }
        }
        if ($error === null) {
            /* DERSELBE WEG WIE DER JOB, nur mit dem Budget dieser Seite.
             * `jobs_lauf('token', ['komplett'])` wäre der Umweg über den
             * Katalog samt Sperre und Mindestabstand — beides steht hier im
             * Weg: Wer klickt, will jetzt und nicht in fünf Minuten. */
            $anfang = microtime(true);
            $z = komp_zustand();
            try {
                $e = komp_schub(db(), $z,
                                static fn(): float => KOMP_BUDGET_SEITE - (microtime(true) - $anfang),
                                5.0);
                komp_zustand_setzen($z);
                if (($z['stand'] ?? '') === 'fertig') {
                    $notice = 'Die Komplettsicherung ist fertig: '
                            . number_format((int)$z['zeilen'], 0, ',', '.') . ' Zeilen aus '
                            . (int)$z['tabellen'] . ' Tabellen, '
                            . edbak_groesse_text((int)$z['bytes']) . '.';
                    if (($z['verdraengt'] ?? []) !== []) {
                        $notice .= ' Verdrängt wurde: ' . implode(', ', (array)$z['verdraengt']) . '.';
                    }
                } else {
                    $notice = 'Der Durchgang ist zu Ende, der Lauf noch nicht. '
                            . 'Er macht mit dem nächsten Wartungslauf weiter — oder '
                            . 'gleich hier mit „Fortsetzen".';
                }
            } catch (Throwable $ex) {
                komp_zustand_setzen($z);
                $error = 'Der Lauf ist gescheitert: ' . $ex->getMessage()
                       . ' Der Stand bleibt stehen; „Fortsetzen" nimmt ihn wieder auf.';
            }
        }
    } elseif ($aktion === 'abbrechen') {
        $r = komp_auftrag_abbrechen();
        if ($r['ok']) { $notice = (string)$r['meldung']; } else { $error = (string)$r['meldung']; }
    } elseif ($aktion === 'regeln') {
        $plan = (string)($_POST['plan'] ?? 'aus');
        $auf  = (int)($_POST['aufbewahrung'] ?? 0);
        $ok = komp_plan_setzen($plan);
        $ok = komp_aufbewahrung_setzen($auf) && $ok;
        $notice = $ok ? 'Die Regeln wurden gespeichert.' : null;
        $error  = $ok ? null : 'Die Regeln liessen sich nicht vollständig speichern. '
                             . 'Aufbewahrung: 1 bis 20.';
    } elseif ($aktion === 'stand_loeschen') {
        $datei = (string)($_POST['datei'] ?? '');
        $notice = komp_loeschen($datei)
            ? 'Der Stand wurde gelöscht. Was auf einem Sicherungsziel liegt, '
            . 'bleibt dort — gelöscht wird auf dem Ziel nichts.'
            : null;
        if ($notice === null) { $error = 'Diesen Stand gibt es nicht (mehr).'; }
    }
}

$schluesselDa = serverschluessel_da();
$z            = komp_zustand();
$stand        = (string)($z['stand'] ?? '');
$laeuft       = in_array($stand, ['dump', 'siegel'], true);
$staende      = komp_staende();
$zahlen       = edbak_ablage_zahlen(true);
$plan         = komp_plan();

ui_seite_start(['titel' => 'Komplettsicherung']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_komplettsicherung']); ?>

  <form method="post" id="f-sichern" hidden
        data-confirm="Jetzt eine Komplettsicherung der ganzen Installation erzeugen? Sie umfasst alle Konten, Stammdaten und Spuren; das dauert und belegt Platz. Was in einem Durchgang nicht fertig wird, läuft mit der Wartung weiter."
        data-confirm-ok="Sichern" data-confirm-tone="normal">
    <?= csrf_field() ?><input type="hidden" name="action" value="jetzt_sichern">
  </form>
  <form method="post" id="f-fort" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="fortsetzen">
  </form>
  <form method="post" id="f-ab" hidden
        data-confirm="Den laufenden Lauf abbrechen? Der halbfertige Stand wird verworfen; die bereits abgelegten Stände bleiben."
        data-confirm-ok="Abbrechen" data-confirm-tone="gefahr">
    <?= csrf_field() ?><input type="hidden" name="action" value="abbrechen">
  </form>

  <?php ui_titelzeile([
      'titel' => 'Komplettsicherung',
      'unter' => 'Die ganze Installation als versiegelter SQL-Dump: alle Konten, '
               . 'Stammdaten, Geräte, Schlüsselhüllen und Spuren. Nicht enthalten '
               . 'ist <code>config.php</code> — sie gehört ins Wiederanlaufpaket.',
      'aktionen' => $schluesselDa
          ? ($laeuft
              ? ui_knopf(['text' => 'Fortsetzen', 'symbol' => 'sicherung',
                          'art' => 'primaer', 'attr' => ' form="f-fort"'])
                . ui_knopf(['text' => 'Abbrechen', 'symbol' => 'schliessen',
                            'art' => 'gefahr', 'attr' => ' form="f-ab"'])
              : ui_knopf(['text' => 'Jetzt sichern', 'symbol' => 'sicherung',
                          'art' => 'primaer', 'attr' => ' form="f-sichern"']))
          : '',
  ]); ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php if (!$schluesselDa): ?>
    <?php ui_karte_start(['titel' => 'Serverschlüssel fehlt']); ?>
      <p class="feld-hinweis">Eine Komplettsicherung enthält jede Tabelle dieser
      Datenbank. Sie wird deshalb <strong>immer versiegelt</strong> abgelegt —
      und dafür braucht es den Serverschlüssel aus <code>config.php</code>.
      Ohne ihn wird hier nichts erzeugt; unversiegelt wird eine solche Datei
      nicht abgelegt.</p>
      <p class="feld-hinweis">Der Schlüssel wird auf der Seite
      <a href="admin_sicherungsziele.php">Sicherungsziele</a> erzeugt und
      eingetragen. Er gehört danach ins Wiederanlaufpaket: Geht er verloren,
      lässt sich keine versiegelte Sicherung mehr öffnen.</p>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php if ($laeuft): ?>
    <?php ui_karte_start(['titel' => 'Läuft gerade']); ?>
      <?php
      $tabellen = count($z['folge'] ?? []);
      ui_zeile(['text' => 'Stand',
                'klein' => $stand === 'dump'
                    ? 'Der SQL-Text entsteht — Tabelle für Tabelle.'
                    : 'Der Text ist vollständig; er wird versiegelt.',
                'plaketten' => ui_plakette($stand === 'dump' ? 'Dump' : 'Versiegelung',
                                           ['ton' => 'blau'])]);
      if ($stand === 'dump') {
          ui_zeile(['text' => 'Tabellen',
                    'klein' => 'In einspielbarer Reihenfolge, Fremdschlüssel zuerst.',
                    'plaketten' => ui_plakette((int)($z['i'] ?? 0) . ' von ' . $tabellen,
                                               ['ton' => 'neutral'])]);
      }
      ui_zeile(['text' => 'Zeilen geschrieben',
                'plaketten' => ui_plakette(number_format((int)($z['zeilen'] ?? 0), 0, ',', '.'),
                                           ['ton' => 'neutral'])]);
      ui_zeile(['text' => 'Bisherige Größe (unversiegelt, gepackt)',
                'plaketten' => ui_plakette(edbak_groesse_text((int)($z['roh_bytes'] ?? 0)),
                                           ['ton' => 'neutral'])]);
      ui_zeile(['text' => 'Begonnen',
                'klein' => 'Ein Lauf darf über mehrere Wartungsläufe gehen.',
                'plaketten' => ui_plakette(edbak_zeitpunkt_text((string)($z['begonnen'] ?? '')),
                                           ['ton' => 'neutral'])]);
      ?>
    <?php ui_karte_ende(); ?>
  <?php elseif ($stand === 'fertig'): ?>
    <?php ui_karte_start(['titel' => 'Letzter Lauf']); ?>
      <?php
      ui_zeile(['text' => 'Fertig geworden',
                'klein' => (string)($z['name'] ?? ''),
                'plaketten' => ui_plakette(edbak_zeitpunkt_text((string)($z['beendet'] ?? '')),
                                           ['ton' => 'blau'])]);
      ui_zeile(['text' => 'Umfang',
                'plaketten' => ui_plakette(
                    number_format((int)($z['zeilen'] ?? 0), 0, ',', '.') . ' Zeilen',
                    ['ton' => 'neutral'])
                  . ui_plakette((int)($z['tabellen'] ?? 0) . ' Tabellen', ['ton' => 'neutral'])
                  . ui_plakette(edbak_groesse_text((int)($z['bytes'] ?? 0)), ['ton' => 'neutral'])]);
      if (($z['verdraengt'] ?? []) !== []) {
          ui_zeile(['text' => 'Verdrängt',
                    'klein' => implode(', ', (array)$z['verdraengt']),
                    'plaketten' => ui_plakette((string)count((array)$z['verdraengt']),
                                               ['ton' => 'orange'])]);
      }
      foreach ((array)($z['warnung'] ?? []) as $w) {
          ui_zeile(['text' => 'Hinweis', 'klein' => (string)$w,
                    'plaketten' => ui_plakette('beachten', ['ton' => 'orange'])]);
      }
      ?>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php ui_karte_start(['titel' => 'Regeln']); ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="regeln">
      <div class="fld-reihe">
        <?php ui_feld(['name' => 'plan', 'label' => 'Von selbst sichern',
                       'art' => 'select', 'optionen' => KOMP_PLAENE, 'wert' => $plan,
                       'klein' => 'Der Plan sagt nicht WANN, sondern OB: Er legt fest, '
                                . 'wie alt der jüngste Stand höchstens sein darf. Wann '
                                . 'tatsächlich gearbeitet wird, entscheidet der '
                                . 'eingerichtete Auslöser — nachzusehen auf der '
                                . 'Wartungsseite.']); ?>
        <?php ui_feld(['name' => 'aufbewahrung', 'label' => 'Stände aufbewahren',
                       'art' => 'number', 'attr' => 'min="1" max="20"',
                       'wert' => (string)komp_aufbewahrung(),
                       'klein' => 'Ältere werden nach einem erfolgreichen Lauf gelöscht — '
                                . 'hier, nicht auf dem Sicherungsziel.']); ?>
      </div>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
    <?php
    $rueck = komp_rueckstand();
    ui_zeile(['text' => 'Jüngster Stand',
              'klein' => $staende === [] ? 'Es gibt noch keinen.' : (string)$staende[0]['datei'],
              'plaketten' => $staende === []
                  ? ui_plakette('keiner', ['ton' => 'orange'])
                  : ui_plakette(edbak_zeitpunkt_text((string)$staende[0]['zeit']),
                                ['ton' => 'blau'])]);
    ui_zeile(['text' => 'Belegt von Komplettsicherungen',
              'klein' => 'Zählt auf die Speichergrenze mit — sie steht unter '
                       . '„Sicherungen".',
              'plaketten' => ui_plakette(edbak_groesse_text((int)$zahlen['komplett_bytes']),
                                         ['ton' => 'neutral'])]);
    ui_zeile(['text' => 'Wartet auf den nächsten Lauf',
              'klein' => $plan === 'aus'
                  ? 'Der Plan steht auf „Nur von Hand".'
                  : 'Fällig heisst: Der jüngste Stand ist älter als der Plan erlaubt.',
              'plaketten' => $rueck === null
                  ? ui_plakette('nichts', ['ton' => 'neutral'])
                  : ui_plakette((string)$rueck, ['ton' => 'orange'])]);
    ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Stände', 'zahl' => count($staende)]); ?>
    <?php if ($staende === []): ?>
      <p class="feld-hinweis">Es liegt noch keine Komplettsicherung vor.</p>
    <?php else: ?>
      <?php foreach ($staende as $nr => $s):
          $kopf = komp_kopf_lesen(komp_wurzel() . '/' . $s['datei']);
          $k = $kopf['kopf'] ?? [];
          $id = 'st' . $nr;
      ?>
        <form method="post" id="f-klar-<?= $id ?>" hidden>
          <?= csrf_field() ?><input type="hidden" name="action" value="herunterladen">
          <input type="hidden" name="art" value="klar">
          <input type="hidden" name="datei" value="<?= ui_e($s['datei']) ?>">
        </form>
        <form method="post" id="f-weg-<?= $id ?>" hidden
              data-confirm="Diesen Stand endgültig löschen? Was bereits auf einem Sicherungsziel liegt, bleibt dort."
              data-confirm-ok="Löschen" data-confirm-tone="gefahr">
          <?= csrf_field() ?><input type="hidden" name="action" value="stand_loeschen">
          <input type="hidden" name="datei" value="<?= ui_e($s['datei']) ?>">
        </form>
        <?php
        ui_zeile([
            'text'  => edbak_zeitpunkt_text((string)$s['zeit']),
            'klein' => $s['datei'] . ' · '
                     . (isset($k['zeilen'])
                        ? number_format((int)$k['zeilen'], 0, ',', '.') . ' Zeilen aus '
                          . (int)($k['tabellen'] ?? 0) . ' Tabellen · Web '
                          . (string)($k['web'] ?? '?') . ' · Migrationsstand '
                          . ((string)($k['migration'] ?? '') !== ''
                             ? (string)$k['migration'] : 'keiner')
                        : 'Der Dateikopf ist nicht lesbar.'),
            'plaketten' => ui_plakette(edbak_groesse_text((int)$s['groesse']), ['ton' => 'neutral'])
                         . ($nr === 0 ? ui_plakette('jüngster', ['ton' => 'blau']) : ''),
            'aktionen' => ui_zeilenaktionen(['eintraege' => [
                ['text' => 'Herunterladen', 'symbol' => 'tausch',
                 'form' => 'f-klar-' . $id],
                ['text' => 'Löschen', 'symbol' => 'korb', 'art' => 'gefahr',
                 'form' => 'f-weg-' . $id],
            ]]),
        ]);
        ?>
        <form method="post" class="listen-form">
          <?= csrf_field() ?><input type="hidden" name="action" value="herunterladen">
          <input type="hidden" name="art" value="pw">
          <input type="hidden" name="datei" value="<?= ui_e($s['datei']) ?>">
          <div class="fld-reihe">
            <?php ui_feld(['name' => 'passphrase', 'label' => 'Mit Passphrase herunterladen',
                           'art' => 'password', 'wert' => '',
                           'klein' => 'Mindestens 8 Zeichen. Die Datei wird dann unter '
                                    . 'dieser Passphrase versiegelt statt unter dem '
                                    . 'Serverschlüssel — zum Weitergeben. Sie wird '
                                    . 'nirgends gespeichert; wer sie verliert, hat die '
                                    . 'Datei verloren.']); ?>
          </div>
          <?php /* DER KNOPF STEHT NEBEN DER REIHE UND NICHT DARIN.
                 * In `.fld-reihe` liegen FELDER nebeneinander; ein Knopf
                 * darin bekommt keine Umbruchstelle und schiebt die Seite
                 * auf. Gemessen im Bilderlauf: 434 px breiter Knopf in einem
                 * 360er Fenster, Überlauf +74 px bei 360, +59 bei 390, +44
                 * bei 420. Der Fussbereich (`.listen-form-fuss`) ist der
                 * vorgesehene Ort — so machen es die übrigen Formulare auch. */ ?>
          <div class="listen-form-fuss">
            <?= ui_knopf(['text' => 'Versiegelt herunterladen', 'symbol' => 'schloss',
                          'art' => 'neutral']) ?>
          </div>
        </form>
      <?php endforeach; ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'vorschau' => 'Wiederanlauf']); ?>
    <p class="feld-hinweis"><strong>Zwei Wege heraus.</strong> „Herunterladen"
    liefert den Dump <em>unverschlüsselt</em> als <code>.sql.gz</code> — genau
    das, was <code>mysql</code> und phpMyAdmin einspielen können. „Versiegelt
    herunterladen" liefert dieselbe Datei unter einer Passphrase; die braucht
    es, wenn die Datei aus dem Haus geht. Was von selbst auf ein
    <a href="admin_sicherungsziele.php">Sicherungsziel</a> geschoben wird, ist
    immer die versiegelte Fassung.</p>

    <p class="feld-hinweis"><strong>Das Wiederanlaufpaket.</strong> Diese Datei
    allein reicht nicht. Wer nach einem Totalausfall neu aufsetzt, braucht
    <em>drei</em> Dinge, und zwei davon stehen nicht hier drin:
    <code>config.php</code> (Datenbankzugang und <strong>Serverschlüssel</strong>),
    den Zugang zum Sicherungsziel und diese Datei. Ohne den Serverschlüssel
    lässt sich die versiegelte Fassung nicht öffnen — er gehört an einen Ort,
    der den Server überlebt. Das Vorgehen steht im Runbook
    (<code>docs/Technik.md</code>, Abschnitt 7).</p>

    <p class="feld-hinweis"><strong>Der Schnappschuss ist nicht scharf.</strong>
    Der Dump entsteht über mehrere Läufe hinweg; eine Zeile, die währenddessen
    entsteht, kann enthalten sein oder nicht. Übersprungen wird nichts, was
    schon dastand. Wer es genauer braucht, lässt nachts sichern.</p>

    <p class="feld-hinweis"><strong>Ein Konto einzeln</strong> holt man sich
    nicht hier, sondern unter <a href="admin_sicherungen.php">Sicherungen</a>.
    Diese Seite ist für den Fall „der Server ist weg", nicht für „jemand hat
    sich vertan".</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
