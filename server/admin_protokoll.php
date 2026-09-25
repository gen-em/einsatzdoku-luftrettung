<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
/* SEIT P5c/AP4 AUCH DER SUPPORT — mit zwei Reitern, lesend
 * (`protokoll_reiter_sichtbar()`). Die eine Handlung der Seite, das Laden
 * eines Archivs, fragt weiterhin die BetreiberIn. */
require_support();
require_once __DIR__ . '/protokoll_archiv_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_zeit_text(), datum_text(), zahl_text(), groesse_text()

/**
 * VERWALTUNG → PROTOKOLL (P5c/AP2, E-P5c-02, -10, -11, -26, -38; Bild M-P5c-01a)
 * ===========================================================================
 *
 * EINE SEITE, EIN ORT. Was die Rolle nicht darf, zeigt die Seite nicht:
 * Oben die Reiter, die sie sehen darf (`protokoll_reiter_sichtbar()`) —
 * BetreiberIn sieben, Admin vier —, darunter die Liste mit einem Suchfeld,
 * dem Zeitraum als Pillen und der Art als Auswahl, 50 Zeilen je Seite. Nur
 * die BetreiberIn sieht den abgesetzten Reiter „Archiv" am rechten Rand.
 *
 * EIN REITER, DEN DIE ROLLE NICHT SIEHT, IST 403 — auch über die Adresse.
 * Die Rollenprobe (`tools/proben/rollen/`) fragt jeden Reiter mit jeder Rolle.
 *
 * KEIN ZUGRIFFSPROTOKOLL. Der Satz im Kopf ist die Zusage aus E-P5c-01 und
 * V1: Wer wann welchen Einsatz geöffnet hat, steht hier nicht.
 *
 * DIE FEHLERKENNUNG (E-P5c-26): Acht Hexzeichen im Suchfeld wechseln — nur
 * für die BetreiberIn — von jedem Reiter auf „System". Die Kennung nennt die
 * Fehlerseite; wer sie gemeldet bekommt, soll nicht raten müssen, wo sie
 * steht.
 */

const PROTOKOLL_JE_SEITE = 50;

$sichtbar = protokoll_reiter_sichtbar();
$betreiberin = ist_betreiberin();

/* ---- Der Download eines Archivs (nur BetreiberIn, POST mit Token) -------- */
$fehler = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    /* ERST DIE ROLLE, DANN DAS TOKEN — wie überall, wo `require_*()` oben
     * steht: Wer die Handlung nicht darf, erfährt das, und nicht, dass sein
     * Formular abgelaufen ist. Die Rollenprobe unterscheidet genau daran. */
    if (!$betreiberin) { ui_abbruch(403, 'Das Archiv des Protokolls ist der BetreiberIn vorbehalten.'); }
    csrf_check();
    if (($_POST['action'] ?? '') === 'archiv_laden') {
        $datei = (string)($_POST['datei'] ?? '');
        /* ERST DER EINTRAG, DANN DIE DATEI. Nach `protokoll_archiv_ausliefern()`
         * ist die Anfrage zu Ende; ein Eintrag danach stünde nie da. Scheitert
         * das Ausliefern, sagt der Eintrag es mit. */
        $ok = protokoll_archiv_name_gueltig($datei)
           && protokoll_archiv_kennung($datei) === serverschluessel_kennung();
        if ($ok) {
            protokoll('verwaltung', 'archiv_heruntergeladen',
                'Protokollarchiv ' . $datei . ' heruntergeladen (entsiegelt)',
                ['datei' => $datei]);
        }
        $fehler = protokoll_archiv_ausliefern($datei);   // endet bei Erfolg
    }
}

/* ---- Welcher Reiter, welche Filter ---------------------------------------- */
$reiter = (string)($_GET['r'] ?? ($sichtbar[0] ?? 'verwaltung'));
$istArchiv = $reiter === 'archiv';
if ($istArchiv ? !$betreiberin : !in_array($reiter, $sichtbar, true)) {
    /* Jobs und Sicherung sieht auch der Admin — dem Support stuende dort
     * sonst „der BetreiberIn vorbehalten", und das stimmte nicht (F-P5c-100). */
    ui_abbruch(isset(PROTOKOLL_SEITE_REITER[$reiter]) || $istArchiv ? 403 : 404,
        isset(PROTOKOLL_SEITE_REITER[$reiter]) || $istArchiv
            ? 'Dieser Reiter des Protokolls ist der '
              . (in_array($reiter, PROTOKOLL_REITER_VERWALTUNG, true) ? 'Verwaltung' : 'BetreiberIn')
              . ' vorbehalten.'
            : 'Diesen Reiter gibt es nicht.');
}

$q     = trim((string)($_GET['q'] ?? ''));
$tage  = (int)($_GET['z'] ?? 0);
$art   = (string)($_GET['art'] ?? '');
$konto = (int)($_GET['konto'] ?? 0);
$seite = max(1, (int)($_GET['s'] ?? 1));
$zeitraeume = [1 => '24 h', 7 => '7 Tage', 30 => '30 Tage'];
if ($reiter === 'verwaltung') { $zeitraeume[365] = '365 Tage'; }
if (!isset($zeitraeume[$tage])) { $tage = 0; }
$arten = $istArchiv ? [] : protokoll_arten_des_reiters($reiter);
if ($art !== '' && !isset($arten[$art])) { $art = ''; }

/* Eine Fehlerkennung wechselt auf „System" — nur für die BetreiberIn. */
if (!$istArchiv && $betreiberin && $reiter !== 'system' && protokoll_ist_kennung($q)) {
    header('Location: admin_protokoll.php?' . http_build_query(['r' => 'system', 'q' => $q]), true, 303);
    exit;
}

/** Die Adresse dieser Seite mit geänderten Parametern. */
function protokoll_weg(array $neu = []): string
{
    global $reiter, $q, $tage, $art, $konto;
    $p = array_merge(['r' => $reiter, 'q' => $q, 'z' => $tage ?: '', 'art' => $art,
                      'konto' => $konto ?: '', 's' => ''], $neu);
    $p = array_filter($p, static fn($v) => (string)$v !== '');
    return 'admin_protokoll.php' . ($p ? '?' . http_build_query($p) : '');
}

ui_seite_start(['titel' => 'Protokoll']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin_protokoll']); ?>

  <?php ui_titelzeile(['titel' => 'Protokoll']); ?>
  <p class="seiten-erklaerung">Hier stehen Betriebsereignisse dieser
     Installation — wer wann welchen Einsatz geöffnet hat, wird
     <strong>nicht</strong> protokolliert.
     <a href="hilfe.php#11-7-protokoll">Handbuch: Protokoll</a></p>

  <?php if ($fehler !== null) { ui_meldung(null, $fehler); } ?>

  <?php
  $punkte = [];
  foreach ($sichtbar as $r) {
      $punkte[] = ['text' => PROTOKOLL_SEITE_REITER[$r], 'aktiv' => $r === $reiter,
                   'href' => 'admin_protokoll.php?r=' . $r];
  }
  if ($betreiberin) {
      $punkte[] = ['text' => 'Archiv', 'aktiv' => $istArchiv, 'abgesetzt' => true,
                   'href' => 'admin_protokoll.php?r=archiv'];
  }
  ui_reiter(['label' => 'Bereiche des Protokolls', 'punkte' => $punkte]);
  ?>

<?php if ($istArchiv):
    /* ---- Das Archiv (nur BetreiberIn) ---------------------------------- */
    $archive = protokoll_archive(true);
    $aufZiel = protokoll_archiv_auf_ziel();
    $fremd = array_values(array_filter($archive, static fn(array $a): bool => !$a['passt']));
    ui_karte_start(['titel' => 'Archiv', 'id' => 'k-archiv', 'zahl' => (string)count($archive),
                    'aktion' => ['text' => 'Fristen', 'href' => 'betrieb_server.php#k-protokoll']]); ?>
    <p class="feld-hinweis"><?= protokoll_archiv_tage() === 1 ? 'Jeden Tag'
         : 'Alle ' . protokoll_archiv_tage() . ' Tage' ?> versiegelt der
       Server die Einträge des Zeitraums als ZIP — ohne IP-Adressen,
       aufbewahrt <?= protokoll_archiv_behalten() ?> Tage.
       <a href="hilfe.php#das-archiv-nur-betreiberin">Handbuch: Archiv des Protokolls</a></p>
    <?php if ($fremd): ?>
      <?php ui_meldung('Öffnen lässt es sich nur mit dem Schlüssel von damals — er steht '
          . 'im Wiederanlaufpaket und auf dem Schlüsselblatt mit der Kennung '
          . (string)$fremd[0]['kennung'] . '.', null, 'warn', '',
          ['auftakt' => count($fremd) === 1
              ? 'Ein Archiv trägt einen anderen Serverschlüssel.'
              : count($fremd) . ' Archive tragen einen anderen Serverschlüssel.']); ?>
    <?php endif; ?>
    <?php if (!$archive): ?>
      <p class="feld-hinweis">Noch kein Archiv — das erste entsteht, sobald ein
         Zeitraum von <?= protokoll_archiv_tage() ?> Tagen abgelaufen und der
         Hintergrundjob gelaufen ist.</p>
    <?php else: ?>
      <div class="archiv-liste">
      <?php foreach ($archive as $i => $a):
          $von = (string)$a['von'];
          $m = $a['manifest'];
          /* DAS ENDE AUS DEM MANIFEST, nicht aus der heutigen Einstellung —
           * wer das Intervall ändert, ändert nicht die alten Archive. Ohne
           * Manifest (anderer Schlüssel) bleibt nur die Einstellung. */
          $bisRoh = is_array($m) && !empty($m['bis'])
              ? (string)$m['bis']
              : protokoll_archiv_ende($von, protokoll_archiv_tage());
          $bis = gmdate('Y-m-d H:i:s', (int)strtotime($bisRoh . ' UTC') - 1);
          $zeilen = is_array($m) ? array_sum(array_map('intval', (array)($m['zeilen'] ?? []))) : null;
          /* DIE KENNUNG WIE AUF DEM SCHLÜSSELBLATT — acht Zeichen am Stück,
           * nicht in Vierergruppen wie im Bild M-P5c-01a: Wer vergleicht, legt
           * das Blatt daneben. */
          $klein = 'Schlüssel ' . (string)$a['kennung']
                 . ($zeilen !== null ? ' · ' . zahl_text($zeilen) . ' Einträge' : '')
                 . ' · ' . groesse_text($a['bytes']);
          $formId = 'f-archiv-' . $i;
          $plakette = !$a['passt']
              ? ui_plakette('anderer Schlüssel', ['ton' => 'orange'])
              : (isset($aufZiel[$a['datei']])
                  ? ui_plakette('auf dem Ziel', ['ton' => 'blau'])
                  : ui_plakette('nur lokal', ['ton' => 'orange']));
          ?>
          <form method="post" id="<?= e($formId) ?>" hidden>
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="archiv_laden">
            <input type="hidden" name="datei" value="<?= e($a['datei']) ?>">
          </form>
          <?php ui_zeile([
              'text' => zeitraum_text($von, $bis),
              'klein' => $klein,
              'plaketten' => $plakette,
              /* OHNE SYMBOL (E-P5c-26). Bei anderem Schlüssel gibt es nichts
               * herunterzuladen, was sich öffnen ließe — dann kein Knopf. */
              'aktionen' => $a['passt'] ? ui_zeilenaktionen([
                  'titel' => 'Archiv ' . zeitraum_text($von, $bis),
                  'eintraege' => [['text' => 'Herunterladen', 'form' => $formId]],
              ]) : '',
              'aktionsspalte' => true,
          ]); ?>
      <?php endforeach; ?>
      </div>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

<?php else:
    /* ---- Ein Reiter mit Ereignissen ------------------------------------- */
    $filter = ['q' => $q, 'tage' => $tage, 'art' => $art, 'konto' => $konto];
    $liste = protokoll_liste($reiter, $filter, $seite, PROTOKOLL_JE_SEITE);
    $seiten = max(1, (int)ceil($liste['gesamt'] / PROTOKOLL_JE_SEITE));
    $aktion = $reiter === 'sicherheit'
        ? ['text' => 'Aktive Sperren', 'href' => 'betrieb_sicherheit.php'] : null;
    ?>
  <div class="protokoll-liste">
  <?php ui_karte_start(['titel' => 'Ereignisse', 'id' => 'k-ereignisse',
                        'zahl' => zahl_text($liste['gesamt'])] + ($aktion ? ['aktion' => $aktion] : [])); ?>
    <?php
    $pillen = [];
    foreach ($zeitraeume as $t => $text) {
        /* Die aktive Pille nimmt ihren Filter zurück — ein zweiter Klick
         * auf „7 Tage" heißt „wieder alles", wie bei den Kontenfiltern. */
        $pillen[] = ['text' => $text, 'aktiv' => $t === $tage,
                     'href' => protokoll_weg(['z' => $t === $tage ? '' : $t])];
    }
    /* DER KONTOFILTER AUS DER ADRESSE (`?konto=…`, der Verweis von der
     * Kontoseite) steht als aktive Pille mit Kreuz in der Filterreihe — ein
     * Verweis, der ihn zurücknimmt, wie jede andere Pille. */
    if ($konto > 0) {
        $st = db()->prepare('SELECT email FROM users WHERE id = ?');
        $st->execute([$konto]);
        $mail = (string)($st->fetchColumn() ?: '');
        $pillen[] = ['text' => $mail !== '' ? $mail : 'Konto ' . $konto, 'aktiv' => true,
                     'kreuz' => true, 'href' => protokoll_weg(['konto' => ''])];
    }
    ui_listenkopf([
        'form_id' => 'f-protokollsuche',
        'suche' => ['name' => 'q', 'wert' => $q,
                    'label' => $betreiberin ? 'Text, Konto oder Fehlerkennung' : 'Text oder Konto',
                    'platzhalter' => $betreiberin ? 'Text, Konto oder Fehlerkennung' : 'Text oder Konto'],
        'versteckt' => ['r' => $reiter, 'z' => $tage ?: '', 'konto' => $konto ?: ''],
        'filter' => $pillen,
        'auswahl' => $arten ? ['name' => 'art', 'label' => 'Art des Ereignisses', 'wert' => $art,
                               'optionen' => ['' => 'Alle Arten'] + $arten] : null,
    ]);
    ?>
    <?php if ($liste['fehler'] !== null): ?>
      <?php ui_meldung(null, 'Eine Quelle dieses Reiters ließ sich nicht lesen — '
          . 'steht ein Update aus? Betrieb → Updates.', 'warn', '',
          ['auftakt_fehler' => 'Unvollständig.']); ?>
    <?php endif; ?>
    <?php if (!$liste['zeilen']): ?>
      <p class="feld-hinweis"><?= $q !== '' || $tage || $art !== '' || $konto
          ? 'Kein Eintrag passt zu Suche und Filter.'
          : 'In diesem Reiter steht noch nichts.' ?></p>
    <?php else: ?>
      <div class="zeilen">
      <?php foreach ($liste['zeilen'] as $z) {
          $klein = array_filter([datum_zeit_text($z['zeit']), $z['wer'],
                                 $z['betrifft'] !== '' ? 'betrifft ' . $z['betrifft'] : '']);
          ui_zeile([
              'text' => $z['text'],
              'klein' => implode(' · ', $klein),
              'plaketten' => ui_plakette($z['beschriftung'], ['ton' => $z['ton']]),
              'daten' => $z['daten'],
              'aktionsspalte' => true,
          ]);
      } ?>
      </div>
      <?php $von = ($seite - 1) * PROTOKOLL_JE_SEITE + 1;
            $bis = min($liste['gesamt'], $seite * PROTOKOLL_JE_SEITE);
            ui_listenfuss(['zahl' => 'Ereignisse ' . $von . '–' . $bis . ' von '
                                   . zahl_text($liste['gesamt']),
                           'seite' => $seite, 'seiten' => $seiten,
                           'weg' => static fn(int $n): string
                               => protokoll_weg(['s' => $n === 1 ? '' : (string)$n])]); ?>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>
  </div>
<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
