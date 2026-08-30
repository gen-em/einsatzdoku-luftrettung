<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/diensttag_lib.php';
require_once __DIR__ . '/ui.php';   // auth_guard.php laedt sie bereits

/**
 * Einen anderen Diensttag in den geoeffneten aufnehmen (E10-E13, E25, A6-A8).
 *
 * DER GEOEFFNETE TAG IST IMMER DER ZIELTAG. Das ist keine Bequemlichkeit,
 * sondern der Grund, warum der Einstieg ueberhaupt hier liegt und nicht in der
 * Tagesliste (E25): Der Vorgang ist NICHT UMKEHRBAR, und bei einer Auswahl von
 * zwei Zeilen in einer Liste ist die Richtung eine Frage der Lesart. Hier ist
 * sie eine Tatsache — der Tag, der stehenbleibt, steht im Titel.
 *
 * ZWEI SCHRITTE, WEIL ES ZWEI FRAGEN SIND. Erst welcher Tag aufgenommen wird,
 * dann was bei Widerspruechen gilt. Beides in einem Formular haette entweder
 * die Widersprueche aller Kandidaten gleichzeitig zeigen muessen oder die
 * Vorschau erst nach dem Absenden — und eine Vorschau, die man nur mit dem
 * Absenden bekommt, ist keine.
 *
 * Der Papierkorb kommt hier nicht vor (E13). Was das bedeutet, steht in der
 * Rueckfrage; die Begruendung in dt_zusammenfuehren().
 */

$zielId = (int)($_POST['d'] ?? $_GET['d'] ?? 0);
$ziel   = $zielId > 0 ? dt_laden($userId, $zielId, true) : null;
if ($ziel === null) { ui_abbruch(404, 'Diensttag nicht gefunden.'); }
if ($ziel['deleted_at'] !== null) {
    ui_abbruch(409, 'Dieser Diensttag liegt im Papierkorb. Bitte ihn zuerst wiederherstellen.');
}

$quellId = (int)($_POST['q'] ?? $_GET['q'] ?? 0);
$fehler  = null;
$vorschau = null;
$quelle   = null;

/* Die Wahl bei Widerspruechen. Vorbelegt mit dem Zieltag: Er ist der, den die
 * Nutzerin vor Augen hat, und ein Vorschlag, der nichts aendert, ist der
 * einzige, der sich ohne Nachdenken bestaetigen laesst. */
$wahl = [];
foreach (['vehicle', 'base', 'crew'] as $feld) {
    $wahl[$feld] = (($_POST['w_' . $feld] ?? 'ziel') === 'quelle') ? 'quelle' : 'ziel';
}

if ($quellId > 0) {
    $p = dt_merge_pruefen($userId, $zielId, $quellId);
    if (!$p['ok']) {
        $fehler = $p['meldung'];
    } else {
        $quelle   = $p['quelle'];
        $vorschau = dt_merge_vorschau($userId, $p['ziel'], $quelle);
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['confirm'] ?? '') === 'ja'
    && $vorschau !== null) {
    csrf_check();
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $e = dt_zusammenfuehren($pdo, $userId, $zielId, $quellId, $wahl);
        if ($e['ok']) {
            $pdo->commit();
            header('Location: index.php?d=' . $zielId . '&aufgenommen=1');
            exit;
        }
        $pdo->rollBack();
        $fehler = $e['meldung'];
    } catch (Throwable $ex) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $fehler = 'Das Zusammenführen ist fehlgeschlagen — es wurde nichts geändert. '
                . 'Kennung: ' . fehler_kennung($ex, 'diensttag_zusammenfuehren');
    }
}

$kandidaten = $vorschau === null ? dt_merge_kandidaten($userId, $zielId) : [];

/* Ein- und Mehrzahl auseinanderhalten: „1 Einsätze" in einer Rückfrage, die zur
 * Vorsicht mahnt, liest sich wie ein Fehler. */
$anz = static fn(int $n, string $eins, string $viele): string
    => $n . ' ' . ($n === 1 ? $eins : $viele);

/** Rettungsmittel und Standort eines Tags in einer Zeile — aus den
 *  eingefrorenen Spalten (E8), nie aus den heutigen Stammdaten. */
$wer = static function (array $t): string {
    $s = [];
    if (($t['vehicle_name'] ?? null) !== null && $t['vehicle_name'] !== '') { $s[] = (string)$t['vehicle_name']; }
    if (($t['base_name']    ?? null) !== null && $t['base_name']    !== '') { $s[] = (string)$t['base_name']; }
    return $s ? implode(' · ', $s) : 'ohne Zuordnung';
};

/* Waehlbare und nicht waehlbare Kandidaten TRENNEN (P3/O11). Vorher standen
 * sie in EINER Tabelle, die nicht waehlbaren mit einem abgeschalteten Radio
 * und der Klasse `zeile-aus` — die es im neuen Stylesheet gar nicht mehr gibt,
 * die Zeilen sahen also aus wie die anderen. Ein abgeschaltetes Radio ist auf
 * einem Telefon ohnehin kaum von einem leeren zu unterscheiden. Jetzt stehen
 * die waehlbaren in der Wahlliste und die uebrigen darunter in einer eigenen,
 * zugeklappten Karte — mit dem Grund an jeder Zeile. */
$waehlbar = $nichtWaehlbar = [];
foreach ($kandidaten as $k) {
    if ($k['vereinbar']) { $waehlbar[] = $k; } else { $nichtWaehlbar[] = $k; }
}

$zielSym = dt_art_symbol($ziel['kind'] === null ? null : (string)$ziel['kind']);

/* Die Wahlliste erwartet Wert => ['text','zusatz']. Der Zusatz traegt alles,
 * was zwei Diensttage desselben Datums unterscheidet. */
$kandOpt = [];
foreach ($waehlbar as $k) {
    $kandOpt[(string)(int)$k['id']] = [
        'text'   => dt_lesbar($k, true),
        'zusatz' => $wer($k) . ' · ' . $anz((int)$k['einsaetze'], 'Einsatz', 'Einsätze')
                  . ' · ' . $anz((int)$k['segmente'], 'Ruhesegment', 'Ruhesegmente')
                  . ' · ' . $anz((int)$k['kennungen'], 'Uhr-Kennung', 'Uhr-Kennungen'),
    ];
}

ui_seite_start(['titel' => 'Diensttag aufnehmen']);
?>
<?php ui_geruest_start(['aktiv' => 'start', 'leiste' => 'diensttage', 'tag' => $zielId]); ?>

  <?php /* Der Zieltag steht im Rueckweg und im Kartentitel, nicht mehr in der
           Ueberschrift: „Anderen Diensttag in den 21.02.2026 07:30 aufnehmen"
           war bei 390 px vier Zeilen lang. */ ?>
  <?php ui_titelzeile([
      'titel'   => 'Diensttag aufnehmen',
      'zurueck' => ['text' => 'Zurück zum Diensttag', 'href' => 'index.php?d=' . (int)$zielId],
      'unter'   => 'Aufnehmender Diensttag: <strong>' . e(dt_lesbar($ziel, true))
                 . '</strong> · ' . e($wer($ziel)) . ' · ' . e($zielSym['text'])
                 . ' — dieser bleibt.',
  ]); ?>

  <?php ui_meldung(null, $fehler, 'info', '  '); ?>

<?php if ($vorschau === null): ?>

  <?php /* ---- Schritt 1: Welcher Tag wird aufgenommen? ------------------- */ ?>
  <p class="seiten-erklaerung">Wurde die App während eines Dienstes
     <strong>versehentlich mehrfach gestartet</strong>, entstehen mehrere
     Diensttage für einen einzigen tatsächlichen Dienst. Hier lassen sie sich
     wieder zusammenführen. Zur Wahl stehen die Diensttage der letzten
     <?= (int)DT_NACHBARSCHAFT_TAGE ?> Tage vor und nach diesem; liegt der
     gesuchte weiter entfernt, ist zuerst sein <strong>Datum zu
     berichtigen</strong>.</p>

  <?php if (!$waehlbar): ?>
    <?= ui_meldung_markup('info', $kandidaten
        ? 'In der zeitlichen Nachbarschaft liegt kein Diensttag, der sich mit '
          . 'diesem zusammenführen ließe — die Arten passen nicht zusammen.'
        : 'In der zeitlichen Nachbarschaft dieses Diensttags liegt kein weiterer. '
          . 'Es gibt nichts aufzunehmen.', '',
        ui_knopf(['text' => 'Zurück zum Diensttag', 'art' => 'neutral',
                  'href' => 'index.php?d=' . (int)$zielId])) ?>
  <?php else: ?>
    <?php ui_karte_start(['titel' => 'Aufzunehmender Diensttag',
                          'zahl' => count($waehlbar)]); ?>
      <?php /* Ein GET-Formular: Der erste Schritt aendert nichts und darf
               deshalb auch nach dem Zurückgehen im Browser wieder erscheinen.
               Geschrieben wird erst im zweiten Schritt, per POST. */ ?>
      <form method="get" action="diensttag_zusammenfuehren.php">
        <input type="hidden" name="d" value="<?= (int)$zielId ?>">
        <?php ui_wahlliste([
            'name' => 'q', 'label' => 'Aufzunehmender Diensttag',
            'optionen' => $kandOpt, 'wert' => $quellId > 0 ? (string)$quellId : '',
            'attr' => ' required',
        ]); ?>
        <div class="listen-form-fuss">
          <?php /* OHNE SYMBOL. Der Vorrat hat keinen Vorwaertspfeil; `winkel`
                   zeigt nach unten und haette „aufklappen" gesagt statt
                   „weiter". Ein Knopf mit klarem Text braucht keines. */ ?>
          <?= ui_knopf(['text' => 'Weiter zur Vorschau', 'art' => 'primaer']) ?>
          <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                        'href' => 'index.php?d=' . (int)$zielId]) ?>
        </div>
      </form>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php if ($nichtWaehlbar): ?>
    <?php ui_karte_start(['titel' => 'Nicht wählbar', 'zu' => true,
                          'vorschau' => count($nichtWaehlbar) . ' in der Nachbarschaft']); ?>
      <p class="feld-hinweis">Zusammenführen geht nur zwischen Diensttagen
         <strong>derselben Art</strong>: Art, Rollensatz und Fähigkeiten sind
         eingefroren (E8), und einen luftgebundenen mit einem bodengebundenen
         Dienst zu verschmelzen ergäbe einen Dienst, den es nie gab.</p>
      <?php foreach ($nichtWaehlbar as $k):
            $kSym = dt_art_symbol($k['kind'] === null ? null : (string)$k['kind']);
            ui_zeile([
                'text'  => dt_lesbar($k, true),
                'klein' => $wer($k) . ' — dieser Diensttag ist ' . $kSym['text']
                         . ', der geöffnete ist ' . $zielSym['text'] . '.',
                'plaketten' => ui_plakette($kSym['text'], ['ton' => 'rot']),
            ]);
      endforeach; ?>
    <?php ui_karte_ende(true); ?>
  <?php endif; ?>

<?php else: ?>

  <?php /* ---- Schritt 2: Vorschau und Rückfrage ------------------------- */
        $quellSym = dt_art_symbol($quelle['kind'] === null ? null : (string)$quelle['kind']);
        $ergSym   = dt_art_symbol($vorschau['kind']); ?>

  <?php ui_karte_start(['titel' => 'Aufgenommen wird ' . dt_lesbar($quelle, true),
                        'plakette' => ui_plakette('verschwindet danach', ['ton' => 'rot'])]); ?>

    <p class="feld-hinweis">
      <?= ui_artzeichen($quelle['kind'] === null ? null : (string)$quelle['kind']) ?>
      <?= e($wer($quelle)) ?> · <?= e($quellSym['text']) ?>
    </p>

    <p>Danach gibt es diesen Diensttag <strong>nicht mehr</strong>. Seine
       Einsätze, Ruhesegmente und Uhr-Kennungen hängen am Diensttag
       <?= e(dt_lesbar($ziel, true)) ?>. Es wandern:</p>

    <?php
      $mit = static fn(int $n): string => $n > 0
          ? ($n === 1 ? '1 weiterer im Papierkorb wandert mit'
                      : $n . ' weitere im Papierkorb wandern mit') : '';
      ui_zeile([
          'text'  => 'Einsätze',
          'klein' => $mit((int)$vorschau['einsaetze_papierkorb']),
          'plaketten' => ui_plakette((string)(int)$vorschau['einsaetze'], ['ton' => 'orange']),
      ]);
      ui_zeile([
          'text'  => 'Ruhesegmente',
          'klein' => $mit((int)$vorschau['segmente_papierkorb']),
          'plaketten' => ui_plakette((string)(int)$vorschau['segmente'], ['ton' => 'orange']),
      ]);
      ui_zeile([
          'text'  => 'Uhr-Kennungen',
          'klein' => $vorschau['kennungen']
              ? 'Spätere Uploads dieses Dienstes landen dadurch von selbst im '
              . 'aufnehmenden Diensttag.' : '',
          'plaketten' => ui_plakette((string)(int)$vorschau['kennungen'], ['ton' => 'orange']),
      ]);
    ?>
  <?php ui_karte_ende(); ?>

  <?php ui_karte_start(['titel' => 'Der Diensttag danach']); ?>
    <?php
      ui_zeile([
          'text' => 'Datum',
          'plaketten' => ui_plakette(dt_datum_lesbar((string)$vorschau['day']),
                                     ['ton' => 'blau']),
      ]);
      ui_zeile([
          'text'  => 'Dienstbeginn und -ende',
          'klein' => $vorschau['ende_offen']
              ? 'Kein Dienstende erfasst — beide Diensttage sind offen.'
              : ($vorschau['ende_geerbt']
                 ? 'Nur einer der beiden Diensttage hat ein erfasstes Ende; '
                 . 'es gilt für den zusammengeführten.' : ''),
          'plaketten' => ui_plakette(
              ($vorschau['started_at'] !== null ? fmt_local((string)$vorschau['started_at']) : '—')
              . ' – ' .
              ($vorschau['ended_at'] !== null ? fmt_local((string)$vorschau['ended_at']) : '—'),
              ['ton' => 'blau']),
      ]);
      /* NUR die Plakette, nicht zusaetzlich das Artzeichen: Das Symbol traegt
         denselben Text als <title>, und ein Screenreader laese „luftgebunden
         luftgebunden". Gemessen im Browser als genau diese Verdopplung. */
      ui_zeile([
          'text' => 'Art',
          'plaketten' => ui_plakette($ergSym['text'], ['ton' => 'blau']),
      ]);
      ui_zeile([
          'text'  => 'Notizen',
          'klein' => 'Die Notizen beider Diensttage werden aneinandergehängt; '
                   . 'nichts wird überschrieben.',
      ]);
    ?>
  <?php ui_karte_ende(); ?>

  <form method="post" action="diensttag_zusammenfuehren.php" id="mergeform"
        data-confirm="Die beiden Diensttage werden zusammengeführt. Das lässt sich nicht rückgängig machen. Fortfahren?"
        data-confirm-titel="Diensttag aufnehmen"
        data-confirm-ok="Zusammenführen" data-confirm-tone="danger">
    <?= csrf_field() ?>
    <input type="hidden" name="d" value="<?= (int)$zielId ?>">
    <input type="hidden" name="q" value="<?= (int)$quellId ?>">
    <input type="hidden" name="confirm" value="ja">

    <?php if ($vorschau['wahlen']): ?>
      <?php ui_karte_start(['titel' => 'Widersprüche',
                            'zahl' => count($vorschau['wahlen'])]); ?>
        <p class="feld-hinweis">Die beiden Diensttage widersprechen sich an diesen
           Stellen. Was soll gelten? Vorbelegt ist jeweils der Tag, der bleibt.</p>
        <?php /* Aus `fieldset`/`legend` mit blanken Radios wird die Wahlliste
                 (E-P3-20): 44 px hohe Zeilen, die gewählte hell orange, der
                 Fokusring an der Zeile statt am unsichtbaren Radio. */ ?>
        <?php foreach ($vorschau['wahlen'] as $feld => $w): ?>
          <div class="listen-form">
            <h3 class="listen-form-titel"><?= e($w['titel']) ?></h3>
            <?php ui_wahlliste([
                'name' => 'w_' . $feld, 'label' => $w['titel'],
                'wert' => $wahl[$feld],
                'optionen' => [
                    'ziel'   => ['text' => $w['ziel'],
                                 'zusatz' => dt_lesbar($ziel, true) . ', bleibt'],
                    'quelle' => ['text' => $w['quelle'],
                                 'zusatz' => dt_lesbar($quelle, true) . ', wird aufgenommen'],
                ],
            ]); ?>
          </div>
        <?php endforeach; ?>
        <?php if (isset($vorschau['wahlen']['crew'])): ?>
          <p class="feld-hinweis">Eine Rolle, die der gewählte Satz <em>nicht</em>
             besetzt, der andere aber schon, wird von dort übernommen — ein
             eingetragener Name geht nicht verloren.</p>
        <?php endif; ?>
      <?php ui_karte_ende(); ?>
    <?php endif; ?>

    <?php ui_karte_start(['titel' => 'Bestätigen']); ?>
      <?= ui_meldung_markup('fehler', 'Das Zusammenführen ist nicht umkehrbar und '
          . 'läuft nicht über den Papierkorb: Dort läge ein leerer Diensttag, '
          . 'dessen Wiederherstellung die Einsätze nicht zurückholen könnte — sie '
          . 'hängen dann am aufnehmenden Tag.') ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Diensttag aufnehmen', 'art' => 'gefahr',
                      'symbol' => 'tausch']) ?>
        <?= ui_knopf(['text' => 'Anderen Diensttag wählen', 'art' => 'neutral',
                      'href' => 'diensttag_zusammenfuehren.php?d=' . (int)$zielId]) ?>
        <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise',
                      'href' => 'index.php?d=' . (int)$zielId]) ?>
      </div>
    <?php ui_karte_ende(); ?>
  </form>

<?php endif; ?>
<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
