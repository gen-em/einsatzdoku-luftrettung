<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/geraete_lib.php';
require_once __DIR__ . '/demo_lib.php';

/**
 * BETRIEB -> STATISTIK (S8/AP4, Mockup 04 Fassung 2).
 *
 * WOZU. „Wie viele Konten hat diese Installation, was fuer Geraete koppeln
 * sich, wird sie ueberhaupt benutzt?" — Fragen, die eine Betreiberin einmal
 * im Quartal stellt und auf die es bisher keine Antwort gab. Die
 * NutzerInnen-Liste zaehlt Konten, die Geraeteseite zaehlt je Konto; die
 * Summe ueber alles zog niemand.
 *
 * REIN LESEND, KEINE AMPEL. Der Status (`betrieb_status.php`) bewertet;
 * diese Seite zaehlt. Eine Zahl, die hier orange waere, gehoerte dorthin.
 *
 * OHNE DEMO-KONTO — durchgaengig und ohne Ausnahme (Rueckmeldung
 * 05.09.2026). Sein Bestand ist erfunden, liegt als Fixture im Repositorium
 * und wird alle dreissig Minuten daraus neu hergestellt. Er in einer
 * Statistik mitzuzaehlen hiesse, 88 erfundene Einsaetze als Nutzung
 * auszugeben. Die Bezugsgroesse steht deshalb an jeder Karte: „von 11
 * Konten" meint elf ECHTE.
 *
 * WEAR OS ERSCHEINT NICHT, UND DAS IST KEIN VERSEHEN (Z-02, geklaert am
 * 05.09.2026). Mockup 04 fuehrt eine Zeile „Wear-OS-Uhren" und eine Art
 * „Uhr (Wear OS)". Beide waeren dauerhaft null: Die Wear-OS-App hat weder
 * Serveradresse noch Schluessel (E-S4-11, `CLAUDE.md` 4) — sie koppelt
 * nicht, sie schickt ihre Ereignisse an das Handy, und das Handy ist das
 * Geraet. In `devices` steht deshalb `art = 'handy'` (Rohangabe
 * `Build.MODEL`) oder `art = 'uhr'` (Garmin, mit Teilenummer). Eine Zeile,
 * die bauartbedingt nie etwas zaehlt, sagt nicht „null Wear-OS-Uhren",
 * sondern verschweigt, dass es sie hier nicht zu zaehlen gibt. Statt der
 * Zeile steht deshalb ein Satz.
 *
 * DIE ZEITRAEUME SIND EINE TABELLE, kein neuer Baustein: eine Zeile je
 * Kennzahl, Spalten 7 Tage / 30 Tage / 6 Monate. „6 Monate" heisst 180 Tage
 * — ein Monat ist keine feste Laenge, und drei verschieden lange Monate in
 * einer Spalte waeren eine stille Ungenauigkeit.
 *
 * EINSAETZE WERDEN NACH DIENSTTAG GEZAEHLT, wie die Statistik der NutzerIn
 * (`days.day`), nicht nach `missions.started_at`. Sonst faellt ein Einsatz,
 * der um 23:50 beginnt und um 00:20 endet, in einen anderen Zeitraum als der
 * Dienst, zu dem er gehoert.
 */

/* ---- Zeitraeume: eine Stelle, drei Spalten ------------------------------ */
const STAT_ZEITRAEUME = [7 => '7 Tage', 30 => '30 Tage', 180 => '6 Monate'];

/** Anteil als Prozentzeichenkette — oder leer, wenn es keine Bezugsgröße gibt. */
function stat_anteil(int $teil, int $ganz): string
{
    if ($ganz <= 0) { return ''; }
    return (string)(int)round($teil * 100 / $ganz) . ' %';
}

/**
 * Anteil und Erklärung zu EINER Kleinzeile — ohne führenden Gedankenstrich,
 * wenn es keinen Anteil gibt. „— Ingest gesperrt" liest sich wie ein
 * abgeschnittener Satz; bei null Geräten gibt es schlicht keinen Anteil.
 */
function stat_klein(string $anteil, string $text = ''): string
{
    $teile = array_values(array_filter([$anteil, $text], static fn($x) => $x !== ''));
    return implode(' — ', $teile);
}

/** Zahl mit Tausenderpunkt. */
function stat_zahl(int|float $n, int $stellen = 0): string
{
    return number_format((float)$n, $stellen, ',', '.');
}

$pdo = db();

/* Das Demo-Konto fliegt aus JEDER Abfrage. Die Kennung steht in `app_state`;
 * gibt es kein Demo-Konto, ist sie 0 und die Bedingung `id <> 0` wahr fuer
 * alle — dieselbe Abfrage, ein Sonderfall weniger. */
$demoId = (int)(demo_id() ?? 0);

/* ---- Konten ------------------------------------------------------------- */
$st = $pdo->prepare('SELECT role, last_login, created_at, id FROM users WHERE id <> ?');
$st->execute([$demoId]);
$konten = $st->fetchAll();
$kontenZahl = count($konten);

$nachRolle = ['betreiberin' => 0, 'admin' => 0, 'user' => 0];
foreach ($konten as $k) { $nachRolle[rolle_normieren($k['role'])]++; }

$st = $pdo->prepare('SELECT COUNT(*) FROM users u WHERE u.id <> ?
                     AND NOT EXISTS (SELECT 1 FROM devices d WHERE d.user_id = u.id)');
$st->execute([$demoId]);
$ohneGeraet = (int)$st->fetchColumn();

/* ---- Geräte ------------------------------------------------------------- */
$st = $pdo->prepare('SELECT geraet_art, geraet_modell, geraet_teil, active, last_seen,
                            created_at, user_id
                     FROM devices WHERE user_id <> ? AND device_id NOT LIKE ?');
$st->execute([$demoId, 'manual-%']);
$geraete = $st->fetchAll();
$geraeteZahl = count($geraete);

$nachArt = ['uhr' => 0, 'handy' => 0, 'sonstiges' => 0, 'unbekannt' => 0];
$deaktiviert = 0;
foreach ($geraete as $g) {
    $a = (string)($g['geraet_art'] ?? '');
    $nachArt[isset($nachArt[$a]) && $a !== 'unbekannt' ? $a : 'unbekannt']++;
    if (!$g['active']) { $deaktiviert++; }
}

/* ---- Zeitraumtabellen ---------------------------------------------------
 *
 * Eine Abfrage je Zelle waere sauber und teuer. Stattdessen wird EINMAL
 * gelesen und in PHP gezaehlt: Die Mengen sind Konten und Geraete — bei
 * dreihundert Konten dreihundert Zeilen, und die stehen ohnehin schon da.
 * Fuer die Einsaetze geht das nicht (Millionen Zeilen); dort zaehlt SQL.
 */
function stat_juenger(?string $wann, int $tage): bool
{
    if ($wann === null || $wann === '') { return false; }
    $t = strtotime($wann . ' UTC');
    return $t !== false && (time() - $t) <= $tage * 86400;
}

$kontenZeit = ['angemeldet' => [], 'angelegt' => []];
$geraeteZeit = ['gemeldet' => [], 'gekoppelt' => []];
foreach (array_keys(STAT_ZEITRAEUME) as $tage) {
    $kontenZeit['angemeldet'][$tage] = count(array_filter($konten,
        static fn($k) => stat_juenger($k['last_login'] ?? null, $tage)));
    $kontenZeit['angelegt'][$tage] = count(array_filter($konten,
        static fn($k) => stat_juenger($k['created_at'] ?? null, $tage)));
    $geraeteZeit['gemeldet'][$tage] = count(array_filter($geraete,
        static fn($g) => stat_juenger($g['last_seen'] ?? null, $tage)));
    $geraeteZeit['gekoppelt'][$tage] = count(array_filter($geraete,
        static fn($g) => stat_juenger($g['created_at'] ?? null, $tage)));
}

/* ---- Einsätze -----------------------------------------------------------
 *
 * Gezaehlt wird ueber den Diensttag (`days.day`) und OHNE Papierkorb: Ein
 * geloeschter Einsatz ist keine Nutzung, und er kaeme beim Wiederherstellen
 * zurueck. `deleted_at IS NULL` an beiden Tabellen — ein Einsatz kann fuer
 * sich geloescht sein oder mit seinem Diensttag.
 */
$st = $pdo->prepare('SELECT COUNT(*) FROM missions m
                     JOIN days d ON d.id = m.day_id
                     WHERE m.user_id <> ? AND m.deleted_at IS NULL
                       AND d.deleted_at IS NULL');
$st->execute([$demoId]);
$einsaetzeGesamt = (int)$st->fetchColumn();

$einsaetze = []; $aktive = [];
foreach (array_keys(STAT_ZEITRAEUME) as $tage) {
    $st = $pdo->prepare('SELECT COUNT(*) AS n, COUNT(DISTINCT m.user_id) AS k
                         FROM missions m JOIN days d ON d.id = m.day_id
                         WHERE m.user_id <> ? AND m.deleted_at IS NULL
                           AND d.deleted_at IS NULL
                           AND d.day >= DATE_SUB(UTC_DATE(), INTERVAL ? DAY)');
    $st->execute([$demoId, $tage]);
    $r = $st->fetch();
    $einsaetze[$tage] = (int)$r['n'];
    $aktive[$tage]    = (int)$r['k'];
}

/* ---- Gerätemodelle ------------------------------------------------------
 *
 * Gruppiert nach `geraet_modell`; fehlt es, tritt die Rohangabe `geraet_teil`
 * an seine Stelle. Der HERSTELLER wird abgeleitet und nicht gespeichert — eine
 * Herstellerspalte in der Datenbank waere eine Angabe, die kein Geraet macht
 * (`geraete_lib.php`: Hersteller und Modell werden ausdruecklich
 * zusammengezogen, weil `Build.MANUFACTURER` ohne `Build.MODEL` wertlos ist).
 *
 * ABGELEITET WIRD UEBER DIE ART, NICHT UEBER DIE TEILENUMMER (Abweichung vom
 * Konzept, begruendet). Das Konzept sagte „Teilenummer vorhanden -> Garmin".
 * Das trifft zu, ist aber nicht vollstaendig: `geraet_teil` bleibt leer, wenn
 * eine aeltere Uhr-Fassung nichts ueber sich meldet oder ein Bestand ohne
 * Kopplung entstanden ist — im Referenzbestand steht bei der `fēnix 7` genau
 * das, und die Regel machte daraus den Hersteller „fēnix". Ueber die ART geht
 * es immer: Eine Uhr, die koppelt, ist eine Garmin-Uhr (die Wear-OS-App
 * koppelt nicht, siehe oben); bei einem Handy ist das erste Wort des
 * Modellnamens der Hersteller, denn genau so hat `geraete_lib.php` ihn
 * zusammengezogen.
 */
$modelle = [];
foreach ($geraete as $g) {
    $name = trim((string)($g['geraet_modell'] ?? ''));
    if ($name === '') { $name = trim((string)($g['geraet_teil'] ?? '')); }
    if ($name === '') { $name = 'ohne Angabe'; }
    $hersteller = match ((string)($g['geraet_art'] ?? '')) {
        'uhr'   => 'Garmin',
        'handy' => (explode(' ', $name)[0] !== $name ? explode(' ', $name)[0] : '—'),
        default => '—',
    };
    $art = geraet_art_text($g['geraet_art'] ?? null) ?? 'unbekannt';
    $k = $name . "\0" . $art;
    if (!isset($modelle[$k])) {
        $modelle[$k] = ['name' => $name, 'hersteller' => $hersteller, 'art' => $art,
                        'geraete' => 0, 'nutzer' => []];
    }
    $modelle[$k]['geraete']++;
    $modelle[$k]['nutzer'][(int)$g['user_id']] = true;
}
$modelle = array_values(array_map(static function (array $m): array {
    $m['nutzer'] = count($m['nutzer']);
    return $m;
}, $modelle));

/* Sortierung über die Adresse, ohne Skript — dasselbe Muster wie die
 * NutzerInnen-Liste. Vorgabe: Anteil (also Gerätezahl) absteigend. */
const STAT_SPALTEN = ['modell' => 'Gerät', 'hersteller' => 'Hersteller', 'art' => 'Art',
                      'geraete' => 'Geräte', 'nutzer' => 'NutzerInnen'];
$sort     = isset(STAT_SPALTEN[(string)($_GET['sort'] ?? '')]) ? (string)$_GET['sort'] : 'geraete';
$richtung = ($_GET['richtung'] ?? '') === 'auf' ? 'auf' : 'ab';
usort($modelle, static function (array $a, array $b) use ($sort, $richtung): int {
    $r = match ($sort) {
        'geraete' => $a['geraete'] <=> $b['geraete'],
        'nutzer'  => $a['nutzer']  <=> $b['nutzer'],
        'hersteller' => strcasecmp($a['hersteller'], $b['hersteller']),
        'art'     => strcasecmp($a['art'], $b['art']),
        default   => strcasecmp($a['name'], $b['name']),
    };
    if ($r === 0) { $r = strcasecmp($a['name'], $b['name']); }
    return $richtung === 'ab' ? -$r : $r;
});

/* ---- CSV ----------------------------------------------------------------
 *
 * SEMIKOLON UND BOM, und beides aus demselben Grund: Excel in deutscher
 * Einstellung liest Komma-CSV als eine Spalte und UTF-8 ohne BOM als
 * Latin-1 — aus „fēnix" wird „fÄ“nix". Wer die Datei danach speichert, hat
 * den Fehler in seinen Daten. Der Export ist fuer Excel gemacht, nicht fuer
 * ein Werkzeug, das UTF-8 erkennt.
 */
if (($_GET['export'] ?? '') === 'csv') {
    $name = 'geraetemodelle-' . date('Y-m-d') . '.csv';
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $name . '"');
    $out = fopen('php://output', 'w');
    fwrite($out, "\xEF\xBB\xBF");
    fputcsv($out, ['Gerät', 'Hersteller', 'Art', 'Geräte', 'Anteil Geräte %',
                   'NutzerInnen', 'Anteil NutzerInnen %'], ';', '"', '');
    foreach ($modelle as $m) {
        fputcsv($out, [$m['name'], $m['hersteller'], $m['art'],
                       $m['geraete'], $geraeteZahl > 0 ? (int)round($m['geraete'] * 100 / $geraeteZahl) : 0,
                       $m['nutzer'],  $kontenZahl  > 0 ? (int)round($m['nutzer']  * 100 / $kontenZahl)  : 0],
                 ';', '"', '');
    }
    fclose($out);
    exit;
}

/** Ein Spaltenkopf der Modelltabelle — Link mit Richtungsumkehr. */
function stat_kopf(string $key, string $text, string $sort, string $richtung): string
{
    /* Dasselbe Muster wie die NutzerInnen-Liste: ein Link im <th class="sortable">,
       der Pfeil in <span class="arrow"> und `symbol-oben` fuer absteigend. Kein
       Skript — die Sortierung steht in der Adresse. */
    $aktiv = $sort === $key;
    $neu   = ($aktiv && $richtung === 'ab') ? 'auf' : 'ab';
    $pfeil = $aktiv
        ? '<span class="arrow">' . ui_symbol('pfeil-hoch',
              $richtung === 'ab' ? 'symbol-oben' : '',
              $richtung === 'ab' ? 'absteigend' : 'aufsteigend') . '</span>'
        : '';
    return '<a href="?sort=' . ui_e($key) . '&amp;richtung=' . ui_e($neu) . '">'
         . ui_e($text) . $pfeil . '</a>';
}

ui_seite_start(['titel' => 'Statistik']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_statistik']); ?>

  <?php ui_titelzeile([
      'titel' => 'Statistik',
      'unter' => 'Stand ' . e(fmt_local(gmdate('Y-m-d H:i:s'), 'd.m.Y · H:i')) . ' Uhr · '
               . '<strong>ohne Demo-Konto</strong> · rein lesend',
  ]); ?>

  <?php /* DER WARTUNGSBALKEN GEHOERT AUF JEDE SEITE, DIE IM WARTUNGSMODUS
           NOCH ANTWORTET (S8/AP8). Diese Seite steht in
           `WARTUNG_AUSNAHMEN`, trug den Balken aber als einzige der fuenf
           nicht — eine BetreiberIn konnte hier arbeiten, waehrend die Anlage
           fuer alle anderen geschlossen war, ohne es zu sehen. Der Balken ist
           die einzige Stelle, an der ein stehengebliebener Wartungsmodus
           auffaellt (E-S5W-05); eine Luecke darin ist keine Kleinigkeit. */ ?>
  <?= wartung_balken() ?>

  <div class="kennzahl-raster kennzahl-raster-4">
    <?= ui_kennzahl(['wert' => stat_zahl($kontenZahl), 'label' => 'Konten',
                     'href' => 'admin_users.php']) ?>
    <?= ui_kennzahl(['wert' => stat_zahl($geraeteZahl), 'label' => 'Geräte']) ?>
    <?= ui_kennzahl(['wert' => stat_zahl($einsaetzeGesamt), 'label' => 'Einsätze gesamt']) ?>
    <?= ui_kennzahl(['wert' => stat_zahl($einsaetze[30] ?? 0),
                     'label' => 'Einsätze in 30 Tagen']) ?>
  </div>

  <div class="form-raster">
  <div class="form-spalte">

    <?php ui_karte_start(['titel' => 'Konten', 'id' => 'k-konten',
                          'zahl' => 'von ' . $kontenZahl,
                          'aktion' => ['text' => 'NutzerInnen', 'href' => 'admin_users.php',
                                       'symbol' => 'gruppe']]); ?>
      <?php foreach (['betreiberin' => 'BetreiberInnen', 'admin' => 'Admins',
                      'user' => 'NutzerInnen'] as $r => $t): ?>
        <?php ui_zeile(['text' => $t,
                        'klein' => stat_anteil($nachRolle[$r], $kontenZahl),
                        'plaketten' => ui_plakette((string)$nachRolle[$r])]); ?>
      <?php endforeach; ?>
      <?php ui_zeile(['text' => 'Ohne Gerät',
                      'klein' => stat_klein(stat_anteil($ohneGeraet, $kontenZahl),
                                            'sie tragen von Hand nach'),
                      'plaketten' => ui_plakette((string)$ohneGeraet)]); ?>

      <div class="tabelle-scroll">
        <table class="tabelle">
          <thead><tr><th>&nbsp;</th>
            <?php foreach (STAT_ZEITRAEUME as $t): ?><th class="zahl-spalte"><?= e($t) ?></th><?php endforeach; ?>
          </tr></thead>
          <tbody>
            <?php foreach (['angemeldet' => 'Zuletzt angemeldet',
                            'angelegt' => 'Neu angelegt'] as $k => $t): ?>
              <tr><th scope="row"><?= e($t) ?></th>
                <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                  <td class="zahl-spalte"><?= stat_zahl($kontenZeit[$k][$tage]) ?>
                    <span class="zeile-klein"><?= e(stat_anteil($kontenZeit[$k][$tage], $kontenZahl)) ?></span></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php ui_karte_ende(); ?>

    <?php ui_karte_start(['titel' => 'Einsätze', 'id' => 'k-einsaetze',
                          'zahl' => stat_zahl($einsaetzeGesamt) . ' gesamt']); ?>
      <div class="tabelle-scroll">
        <table class="tabelle">
          <thead><tr><th>&nbsp;</th>
            <?php foreach (STAT_ZEITRAEUME as $t): ?><th class="zahl-spalte"><?= e($t) ?></th><?php endforeach; ?>
          </tr></thead>
          <tbody>
            <tr><th scope="row">Einsätze</th>
              <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                <td class="zahl-spalte"><?= stat_zahl($einsaetze[$tage]) ?></td>
              <?php endforeach; ?>
            </tr>
            <tr><th scope="row">NutzerInnen mit Einsatz</th>
              <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                <td class="zahl-spalte"><?= stat_zahl($aktive[$tage]) ?>
                  <span class="zeile-klein"><?= e(stat_anteil($aktive[$tage], $kontenZahl)) ?></span></td>
              <?php endforeach; ?>
            </tr>
            <tr><th scope="row">Ø je aktiver NutzerIn
                <span class="zeile-klein">mit Einsatz im Zeitraum</span></th>
              <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                <td class="zahl-spalte"><?= $aktive[$tage] > 0
                    ? stat_zahl($einsaetze[$tage] / $aktive[$tage], 1) : '—' ?></td>
              <?php endforeach; ?>
            </tr>
            <tr><th scope="row">Ø je NutzerIn gesamt
                <span class="zeile-klein">alle <?= $kontenZahl ?> Konten</span></th>
              <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                <td class="zahl-spalte"><?= $kontenZahl > 0
                    ? stat_zahl($einsaetze[$tage] / $kontenZahl, 1) : '—' ?></td>
              <?php endforeach; ?>
            </tr>
          </tbody>
        </table>
      </div>
      <p class="feld-klein">Gezählt nach <strong>Diensttag</strong>, wie die Statistik
         der NutzerIn — nicht nach dem Beginn des Einsatzes. Ohne Papierkorb, ohne
         Demo-Konto. „6 Monate" sind 180 Tage.</p>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">

    <?php ui_karte_start(['titel' => 'Geräte', 'id' => 'k-geraete',
                          'zahl' => 'von ' . $geraeteZahl]); ?>
      <?php ui_zeile(['text' => 'Garmin-Uhren',
                      'klein' => stat_anteil($nachArt['uhr'], $geraeteZahl),
                      'plaketten' => ui_plakette((string)$nachArt['uhr'])]); ?>
      <?php ui_zeile(['text' => 'Android-Handys',
                      'klein' => stat_anteil($nachArt['handy'], $geraeteZahl),
                      'plaketten' => ui_plakette((string)$nachArt['handy'])]); ?>
      <?php if ($nachArt['sonstiges'] > 0): ?>
        <?php ui_zeile(['text' => 'Sonstige',
                        'klein' => stat_anteil($nachArt['sonstiges'], $geraeteZahl),
                        'plaketten' => ui_plakette((string)$nachArt['sonstiges'])]); ?>
      <?php endif; ?>
      <?php if ($nachArt['unbekannt'] > 0): ?>
        <?php ui_zeile(['text' => 'Ohne Angabe',
                        'klein' => stat_klein(stat_anteil($nachArt['unbekannt'], $geraeteZahl),
                                              'ältere Fassungen melden nichts über sich'),
                        'plaketten' => ui_plakette((string)$nachArt['unbekannt'])]); ?>
      <?php endif; ?>
      <?php ui_zeile(['text' => 'Deaktiviert',
                      'klein' => stat_klein(stat_anteil($deaktiviert, $geraeteZahl),
                                            'Ingest gesperrt, Daten bleiben'),
                      'plaketten' => ui_plakette((string)$deaktiviert,
                          ['ton' => $deaktiviert > 0 ? 'orange' : 'neutral'])]); ?>

      <div class="tabelle-scroll">
        <table class="tabelle">
          <thead><tr><th>&nbsp;</th>
            <?php foreach (STAT_ZEITRAEUME as $t): ?><th class="zahl-spalte"><?= e($t) ?></th><?php endforeach; ?>
          </tr></thead>
          <tbody>
            <?php foreach (['gemeldet' => 'Zuletzt gemeldet',
                            'gekoppelt' => 'Gekoppelt'] as $k => $t): ?>
              <tr><th scope="row"><?= e($t) ?></th>
                <?php foreach (array_keys(STAT_ZEITRAEUME) as $tage): ?>
                  <td class="zahl-spalte"><?= stat_zahl($geraeteZeit[$k][$tage]) ?>
                    <span class="zeile-klein"><?= e(stat_anteil($geraeteZeit[$k][$tage], $geraeteZahl)) ?></span></td>
                <?php endforeach; ?>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php /* DER SATZ STATT EINER ZEILE (Z-02). Siehe Kopfkommentar. */ ?>
      <p class="feld-klein"><strong>Wear-OS-Uhren erscheinen hier nicht</strong>, und
         zwar bauartbedingt: Die Uhr-App kennt weder Serveradresse noch Schlüssel.
         Sie schickt ihre Ereignisse an das Handy, und das Handy sendet — gekoppelt
         ist also das Handy. Eine verlorene Uhr gibt keinen Zugang preis; das ist der
         Zweck dieser Bauform.</p>
    <?php ui_karte_ende(); ?>

  </div><?php /* .form-spalte (rechts) */ ?>
  </div><?php /* .form-raster */ ?>

  <?php ui_karte_start(['titel' => 'Gerätemodelle', 'id' => 'k-modelle',
      'zahl' => count($modelle) . (count($modelle) === 1 ? ' Modell' : ' Modelle'),
      'aktion' => ['text' => 'Als CSV', 'symbol' => 'tausch',
                   'href' => '?export=csv&sort=' . e($sort) . '&richtung=' . e($richtung)]]); ?>
    <?php if ($modelle === []): ?>
      <p class="feld-hinweis">Noch kein Gerät gekoppelt.</p>
    <?php else: ?>
      <div class="tabelle-scroll">
        <table class="tabelle">
          <thead><tr>
            <th class="sortable"><?= stat_kopf('modell', 'Gerät', $sort, $richtung) ?></th>
            <th class="sortable"><?= stat_kopf('hersteller', 'Hersteller', $sort, $richtung) ?></th>
            <th class="sortable"><?= stat_kopf('art', 'Art', $sort, $richtung) ?></th>
            <th class="sortable"><?= stat_kopf('geraete', 'Geräte', $sort, $richtung) ?></th>
            <th>Anteil</th>
            <th class="sortable"><?= stat_kopf('nutzer', 'NutzerInnen', $sort, $richtung) ?></th>
            <th>Anteil</th>
          </tr></thead>
          <tbody>
            <?php foreach ($modelle as $m): ?>
              <tr>
                <td><?= e($m['name']) ?></td>
                <td><?= e($m['hersteller']) ?></td>
                <td><?= e($m['art']) ?></td>
                <td class="zahl-spalte"><?= stat_zahl($m['geraete']) ?></td>
                <td class="zahl-spalte"><?= e(stat_anteil($m['geraete'], $geraeteZahl)) ?></td>
                <td class="zahl-spalte"><?= stat_zahl($m['nutzer']) ?></td>
                <td class="zahl-spalte"><?= e(stat_anteil($m['nutzer'], $kontenZahl)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="feld-klein">Anteil <em>Geräte</em> bezogen auf alle <?= $geraeteZahl ?>
         Geräte, Anteil <em>NutzerInnen</em> auf alle <?= $kontenZahl ?> Konten — ein
         Modell kann bei mehreren Konten stehen. <strong>Der Hersteller ist
         abgeleitet</strong>, nicht gespeichert: Eine Uhr, die koppelt, ist eine
         Garmin-Uhr; bei einem Handy gilt das erste Wort des Modellnamens, weil
         der Name aus Hersteller und Modell zusammengezogen ist. Das ist eine
         Faustregel und keine Zusage.</p>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
