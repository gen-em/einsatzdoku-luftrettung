<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/geraete_lib.php';
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/statistik_lib.php';   // Fenster, Zaehlung der Einsaetze (P5c/AP7)
require_once __DIR__ . '/format_lib.php';   // zahl_text(), prozent_text(), prozent_wert(),
                                            // datum_zeit_text(), heute_lokal() (Schritt 15/AP7)

/**
 * BETRIEB -> STATISTIK (S8/AP4; seit Web 20.47.0 mit drei Reitern, P5c/AP7,
 * E-P5c-18, Bild M-P5c-01b).
 *
 * WOZU. „Wie viele Konten hat diese Installation, was fuer Geraete koppeln
 * sich, wird sie ueberhaupt benutzt?" — Fragen, die eine BetreiberIn einmal
 * im Quartal stellt. Die NutzerInnen-Liste zaehlt Konten, die Geraeteseite
 * zaehlt je Konto; die Summe ueber alles zieht nur diese Seite.
 *
 * DREI REITER, EIN MENUEEINTRAG (E-P5c-18, -25). NutzerInnen · Einsaetze ·
 * Geraete, als Verweise `?r=nutzer|einsaetze|geraete`; Betrieb behaelt seine
 * sieben Eintraege, und die Seite heisst weiter „Statistik" — kein Name
 * „Betriebslage". Ueber den Reitern stehen die vier Kennzahlen, jede fuehrt in
 * ihren Reiter. JEDER REITER HAT DIESELBE FORM: links die Tabelle „… je
 * Zeitraum", rechts die Karte „was es gibt" (`.form-raster-links-breit`,
 * 3 : 2 ab 1200 px); gestapelt steht die Tabelle zuerst. Jede Sicht rechnet
 * nur ihre eigenen Abfragen — die Kennzahlen oben rechnen alle.
 *
 * REIN LESEND, KEINE AMPEL. Der Status (`betrieb_status.php`) bewertet;
 * diese Seite zaehlt. Eine Zahl, die hier orange waere, gehoerte dorthin.
 *
 * OHNE DEMO-KONTO — durchgaengig und ohne Ausnahme (Rueckmeldung
 * 05.09.2026). Sein Bestand ist erfunden, liegt als Fixture im Repositorium
 * und wird alle dreissig Minuten daraus neu hergestellt. Ihn in einer
 * Statistik mitzuzaehlen hiesse, 106 erfundene Einsaetze als Nutzung
 * auszugeben. Die Bezugsgroesse steht deshalb an jeder Karte: „von 11"
 * meint elf ECHTE Konten.
 *
 * EINE ZAEHLUNG DER EINSAETZE: AB IHREM BEGINN (E-P5c-18, entschieden
 * 20.09.2026). Gezaehlt wird nach `missions.started_at` — Pflichtfeld, UTC,
 * mit eigenem Index (`idx_missions_started`, Nr. 191) —, ohne Demo-Konto und
 * ohne Papierkorb. Bis Web 20.46.0 zaehlte diese Seite nach Diensttag, wie
 * die Statistik der NutzerIn; das ist entfallen, und mit ihm der Streit, ob
 * „Bestand" das eine oder das andere meint (Nr. 192). PREIS: Ein Einsatz, der
 * nach Mitternacht beginnt, gehoert hier zum Tag seines Beginns, in der
 * Statistik der NutzerIn zum Dienst des Vortags — die Summe hier kann um
 * einzelne Einsaetze von der Summe der NutzerInnen-Statistiken abweichen.
 * Das Handbuch (12.2) sagt es; der Satz unter der Karte sagt, wie gezaehlt
 * wird.
 *
 * JEDES FENSTER HAT EINE OBERGRENZE (F-P5c-39, ergaenzt 23.09.2026): „7 Tage"
 * heisst zwischen jetzt minus sieben Tagen und JETZT. Ein Einsatz mit einem
 * Beginn in der Zukunft (vorausgeplant, oder eine Uhr mit falscher Zeit)
 * erscheint nur unter „gesamt". Vorher hatten die Fenster nur eine
 * Untergrenze und zaehlten ihn in jedem mit.
 *
 * „AKTIV" NACH R38: angemeldet ODER eines der echten Geraete hat sich im
 * Fenster gemeldet (`devices.last_seen`, ohne das virtuelle Geraet der
 * Handeintraege — `geraete_echt_sql()`). Wer nur mit der Uhr arbeitet und
 * sich nie im Browser anmeldet, ist aktiv; die Zeile „Angemeldet" allein
 * saehe ihn nicht.
 *
 * WEAR OS ERSCHEINT NICHT, UND DAS IST KEIN VERSEHEN (Z-02, geklaert am
 * 05.09.2026). Die Wear-OS-App hat weder Serveradresse noch Schluessel
 * (E-S4-11, `CLAUDE.md` 4) — sie koppelt nicht, sie schickt ihre Ereignisse
 * an das Handy, und das Handy ist das Geraet. Eine Zeile „Wear-OS-Uhren"
 * waere dauerhaft null und verschwiege, dass es sie hier nicht zu zaehlen
 * gibt. Statt der Zeile steht deshalb ein Satz — unter dem Reiter Geraete.
 * (Die HERKUNFT „Wear-OS-Uhr" unter Einsaetze ist etwas anderes: an der Uhr
 * begonnen, vom Handy gesendet. Sie zaehlt Einsaetze, keine Geraete.)
 *
 * DIE ZEITRAEUME SIND TABELLEN, kein neuer Baustein: eine Zeile je Kennzahl,
 * eine Spalte je Fenster. „6 Monate" heisst 180 Tage, „1 Jahr" 365 — ein
 * Monat ist keine feste Laenge, und verschieden lange Monate in einer Spalte
 * waeren eine stille Ungenauigkeit.
 */

/* ---- Reiter: eine Stelle. Die Fenster stehen in `statistik_lib.php`. ---- */
const STAT_REITER = ['nutzer' => 'NutzerInnen', 'einsaetze' => 'Einsätze', 'geraete' => 'Geräte'];

/* Spalten der Modelltabelle — Schluessel der Sortierung => Kopf. Oben, nicht
 * bei der Tabelle: Eine Konstante auf oberster Ebene gibt es erst, wenn ihre
 * Zeile gelaufen ist, und der Reiter Geraete sortiert weiter oben. */
const STAT_SPALTEN = ['modell' => 'Gerät', 'hersteller' => 'Hersteller', 'art' => 'Art',
                      'geraete' => 'Geräte', 'nutzer' => 'NutzerInnen'];

$reiter = isset(STAT_REITER[(string)($_GET['r'] ?? '')]) ? (string)$_GET['r'] : 'nutzer';

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

/**
 * Liegt ein Zeitpunkt (UTC, wie ihn die Datenbank fuehrt) im Fenster der
 * letzten `$tage` Tage — MIT Obergrenze? Ein Zeitpunkt in der Zukunft liegt
 * in keinem Fenster (F-P5c-39).
 */
function stat_im_fenster(?string $wann, int $tage, int $jetzt): bool
{
    if ($wann === null || $wann === '') { return false; }
    $t = strtotime($wann . ' UTC');
    return $t !== false && $t <= $jetzt && ($jetzt - $t) <= $tage * 86400;
}

$pdo   = db();
$jetzt = time();

/* Das Demo-Konto fliegt aus JEDER Abfrage. Die Kennung steht in `app_state`;
 * gibt es kein Demo-Konto, ist sie 0 und die Bedingung `id <> 0` wahr fuer
 * alle — dieselbe Abfrage, ein Sonderfall weniger. */
$demoId = (int)(demo_id() ?? 0);

/* ---- Die vier Kennzahlen — auf jedem Reiter ----------------------------- */
$st = $pdo->prepare('SELECT COUNT(*) FROM users WHERE id <> ?');
$st->execute([$demoId]);
$kontenZahl = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM devices WHERE user_id <> ? AND ' . geraete_echt_sql());
$st->execute([$demoId]);
$geraeteZahl = (int)$st->fetchColumn();

$st = $pdo->prepare('SELECT COUNT(*) FROM missions WHERE user_id <> ? AND deleted_at IS NULL');
$st->execute([$demoId]);
$einsaetzeGesamt = (int)$st->fetchColumn();

/* Die Fenster der Einsaetze — EINE Abfrage, und dieselbe, die der Messstand
 * erklaeren laesst (`statistik_lib.php`). Die Kennzahl „in 30 Tagen" kommt
 * daraus, deshalb laeuft sie auf jedem Reiter. */
$st = $pdo->prepare(statistik_einsaetze_sql());
$st->execute([$demoId]);
$r = $st->fetch() ?: [];
$einsaetze = []; $mitEinsatz = [];
foreach (array_keys(STAT_FENSTER_EINSAETZE) as $tage) {
    $einsaetze[$tage]  = (int)($r['n' . $tage] ?? 0);
    $mitEinsatz[$tage] = (int)($r['k' . $tage] ?? 0);
}

/* ======================================================================== */
/* ---- Reiter NutzerInnen ------------------------------------------------- */
if ($reiter === 'nutzer') {
    /* Je Konto: Rolle, Anmeldung, Anlage — und die juengste Meldung eines
     * ECHTEN Geraets. Eine Zeile je Konto; bei dreihundert Konten dreihundert
     * Zeilen, gezaehlt wird in PHP. */
    $st = $pdo->prepare('SELECT u.role, u.last_login, u.created_at,
                                (SELECT MAX(d.last_seen) FROM devices d
                                  WHERE d.user_id = u.id AND ' . geraete_echt_sql('d') . ') AS geraet_zuletzt,
                                EXISTS (SELECT 1 FROM devices d
                                  WHERE d.user_id = u.id AND ' . geraete_echt_sql('d') . ') AS hat_geraet
                         FROM users u WHERE u.id <> ?');
    $st->execute([$demoId]);
    $konten = $st->fetchAll();

    /* AUS DEM KATALOG, NICHT VON HAND (P5c/AP4, F-P5c-36). */
    $nachRolle = array_fill_keys(array_keys(ROLLEN), 0);
    /* „OHNE GERAET" ZAEHLT NUR ECHTE GERAETE (Nr. 190). Das virtuelle Geraet
     * der Handeintraege entsteht beim ersten Formular, beim Import, beim
     * Schneiden und beim GPX-Import — wer ausschliesslich von Hand
     * dokumentiert, hatte damit eine Geraetezeile und fiel bis Web 20.46.0
     * aus genau der Gruppe heraus, die die Kleinzeile meint. */
    $ohneGeraet = 0;
    foreach ($konten as $k) {
        $nachRolle[rolle_normieren($k['role'])]++;
        if (!(int)$k['hat_geraet']) { $ohneGeraet++; }
    }

    $kontenZeit = ['aktiv' => [], 'angemeldet' => [], 'angelegt' => []];
    foreach (array_keys(STAT_FENSTER_KONTEN) as $tage) {
        $kontenZeit['aktiv'][$tage] = count(array_filter($konten,
            static fn($k) => stat_im_fenster($k['last_login'] ?? null, $tage, $jetzt)
                          || stat_im_fenster($k['geraet_zuletzt'] ?? null, $tage, $jetzt)));
        $kontenZeit['angemeldet'][$tage] = count(array_filter($konten,
            static fn($k) => stat_im_fenster($k['last_login'] ?? null, $tage, $jetzt)));
        $kontenZeit['angelegt'][$tage] = count(array_filter($konten,
            static fn($k) => stat_im_fenster($k['created_at'] ?? null, $tage, $jetzt)));
    }
}

/* ---- Reiter Einsätze ---------------------------------------------------- */
if ($reiter === 'einsaetze') {
    /* HERKUNFT, LETZTE 30 TAGE (E-P5c-45, Nr. 80). Summen einer vorhandenen
     * Spalte, nur fuer die BetreiberIn — keine Datenschutz-Vorbedingung,
     * anders als Nr. 80 fuer die Momentaufnahme am Einsatz verlangt. Alle
     * sechs Werte aus `HERKUNFT_WERTE`, auch mit 0: Eine fehlende Zeile saehe
     * aus wie eine Herkunft, die es nicht gibt. */
    $st = $pdo->prepare(statistik_herkunft_sql());
    $st->execute([$demoId]);
    $herkunft = array_fill_keys(HERKUNFT_WERTE, 0);
    $herkunftAndere = 0;
    foreach ($st->fetchAll() as $h) {
        $o = (string)($h['origin'] ?? '');
        if (isset($herkunft[$o])) { $herkunft[$o] = (int)$h['n']; }
        else                      { $herkunftAndere += (int)$h['n']; }
    }
}

/* ---- Reiter Geräte ------------------------------------------------------ */
if ($reiter === 'geraete') {
    $st = $pdo->prepare('SELECT geraet_art, geraet_modell, geraet_teil, active, last_seen,
                                created_at, user_id
                         FROM devices WHERE user_id <> ? AND ' . geraete_echt_sql());
    $st->execute([$demoId]);
    $geraete = $st->fetchAll();

    $nachArt = ['uhr' => 0, 'handy' => 0, 'sonstiges' => 0, 'unbekannt' => 0];
    $deaktiviert = 0;
    foreach ($geraete as $g) {
        $a = (string)($g['geraet_art'] ?? '');
        $nachArt[isset($nachArt[$a]) && $a !== 'unbekannt' ? $a : 'unbekannt']++;
        if (!$g['active']) { $deaktiviert++; }
    }

    $geraeteZeit = ['gemeldet' => [], 'gekoppelt' => []];
    foreach (array_keys(STAT_FENSTER_GERAETE) as $tage) {
        $geraeteZeit['gemeldet'][$tage] = count(array_filter($geraete,
            static fn($g) => stat_im_fenster($g['last_seen'] ?? null, $tage, $jetzt)));
        $geraeteZeit['gekoppelt'][$tage] = count(array_filter($geraete,
            static fn($g) => stat_im_fenster($g['created_at'] ?? null, $tage, $jetzt)));
    }

    /* ---- Gerätemodelle --------------------------------------------------
     *
     * Gruppiert nach `geraet_modell`; fehlt es, tritt die Rohangabe
     * `geraet_teil` an seine Stelle. Der HERSTELLER wird abgeleitet und nicht
     * gespeichert — eine Herstellerspalte waere eine Angabe, die kein Geraet
     * macht (`geraete_lib.php`: Hersteller und Modell werden ausdruecklich
     * zusammengezogen, weil `Build.MANUFACTURER` ohne `Build.MODEL` wertlos
     * ist).
     *
     * ABGELEITET WIRD UEBER DIE ART, NICHT UEBER DIE TEILENUMMER (Abweichung
     * vom S8-Konzept, begruendet). `geraet_teil` bleibt leer, wenn eine
     * aeltere Uhr-Fassung nichts ueber sich meldet — im Referenzbestand steht
     * bei der `fēnix 7` genau das, und die Regel „Teilenummer vorhanden ->
     * Garmin" machte daraus den Hersteller „fēnix". Ueber die ART geht es
     * immer: Eine Uhr, die koppelt, ist eine Garmin-Uhr (die Wear-OS-App
     * koppelt nicht); bei einem Handy ist das erste Wort des Modellnamens
     * der Hersteller, denn genau so hat `geraete_lib.php` ihn zusammengezogen.
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

    /* Sortierung ueber die Adresse, ohne Skript — dasselbe Muster wie die
     * NutzerInnen-Liste. Vorgabe: Anteil (also Geraetezahl) absteigend. Der
     * Reiter `r` reist in jedem Verweis mit (F-P5c-39): Ohne ihn fuehrte ein
     * Klick auf einen Spaltenkopf zurueck auf den Reiter NutzerInnen. */
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

    /* ---- CSV ------------------------------------------------------------
     *
     * SEMIKOLON UND BOM, und beides aus demselben Grund: Excel in deutscher
     * Einstellung liest Komma-CSV als eine Spalte und UTF-8 ohne BOM als
     * Latin-1 — aus „fēnix" wird „fÄ“nix". Wer die Datei danach speichert,
     * hat den Fehler in seinen Daten. Der Export ist fuer Excel gemacht,
     * nicht fuer ein Werkzeug, das UTF-8 erkennt.
     */
    if (($_GET['export'] ?? '') === 'csv') {
        $name = 'geraetemodelle-' . heute_lokal() . '.csv';
        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $name . '"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF");
        fputcsv($out, ['Gerät', 'Hersteller', 'Art', 'Geräte', 'Anteil Geräte %',
                       'NutzerInnen', 'Anteil NutzerInnen %'], ';', '"', '');
        foreach ($modelle as $m) {
            fputcsv($out, [$m['name'], $m['hersteller'], $m['art'],
                           $m['geraete'], prozent_wert($m['geraete'], $geraeteZahl, 'kauf'),
                           $m['nutzer'],  prozent_wert($m['nutzer'],  $kontenZahl,  'kauf')],
                     ';', '"', '');
        }
        fclose($out);
        exit;
    }
}

/** Ein Spaltenkopf der Modelltabelle — Link mit Richtungsumkehr, im Reiter. */
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
    return '<a href="?r=geraete&amp;sort=' . ui_e($key) . '&amp;richtung=' . ui_e($neu) . '">'
         . ui_e($text) . $pfeil . '</a>';
}

/**
 * Eine Zeitraumtabelle: eine Zeile je Kennzahl, eine Spalte je Fenster, der
 * Anteil als Kleinzeile unter der Zahl.
 *
 * @param array<int,string> $fenster  Tage => Spaltenkopf
 * @param list<array{0:string,1:string,2:array<int,int|string>,3:?int}> $zeilen
 *        [Titel, Unterzeile, Werte je Fenster, Bezug fuer den Anteil oder null].
 *        Ein Wert als Text (der Schnitt, „—") steht so da und traegt keinen
 *        Anteil.
 */
function stat_zeitraumtabelle(array $fenster, array $zeilen): void
{
    echo '<div class="tabelle-scroll"><table class="tabelle"><thead><tr><th>&nbsp;</th>';
    foreach ($fenster as $t) { echo '<th class="zahl-spalte">' . e($t) . '</th>'; }
    echo '</tr></thead><tbody>';
    foreach ($zeilen as [$titel, $unter, $werte, $bezug]) {
        echo '<tr><th scope="row">' . e($titel)
           . ($unter !== '' ? ' <span class="zeile-klein">' . e($unter) . '</span>' : '') . '</th>';
        foreach (array_keys($fenster) as $tage) {
            $w = $werte[$tage] ?? 0;
            echo '<td class="zahl-spalte">'
               . (is_string($w) ? e($w) : zahl_text($w))
               /* Das Leerzeichen vor „%" ist geschuetzt: In einer schmalen
                * Spalte (vier und fuenf Fenster bei 360 px) stand sonst „100"
                * ueber „%". Im Markup und nicht als `white-space` im Stylesheet —
                * die Eigenschaft erbt, und der Stilvergleich sah sie an 116
                * fremden Elementen seiner Probe (F-P5c-126, Nr. 321). */
               . ($bezug !== null && !is_string($w)
                   ? '<span class="zeile-klein">'
                     . str_replace(' ', '&nbsp;', e(prozent_text($w, $bezug))) . '</span>' : '')
               . '</td>';
        }
        echo '</tr>';
    }
    echo '</tbody></table></div>';
}

ui_seite_start(['titel' => 'Statistik']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_statistik']); ?>

  <?php ui_titelzeile([
      'titel' => 'Statistik',
      'unter' => 'Stand ' . e(datum_zeit_text(gmdate('Y-m-d H:i:s'))) . ' Uhr · '
               . '<strong>ohne Demo-Konto</strong> · rein lesend',
  ]); ?>

  <?php /* DER WARTUNGSBALKEN GEHOERT AUF JEDE SEITE, DIE IM WARTUNGSMODUS
           NOCH ANTWORTET (S8/AP8). Diese Seite steht in
           `WARTUNG_AUSNAHMEN`; der Balken ist die einzige Stelle, an der ein
           stehengebliebener Wartungsmodus auffaellt (E-S5W-05). */ ?>
  <?= wartung_balken() ?>

  <div class="kennzahl-raster kennzahl-raster-4">
    <?= ui_kennzahl(['wert' => zahl_text($kontenZahl), 'label' => 'Konten',
                     'href' => '?r=nutzer']) ?>
    <?= ui_kennzahl(['wert' => zahl_text($geraeteZahl), 'label' => 'Geräte',
                     'href' => '?r=geraete']) ?>
    <?= ui_kennzahl(['wert' => zahl_text($einsaetzeGesamt), 'label' => 'Einsätze gesamt',
                     'href' => '?r=einsaetze']) ?>
    <?= ui_kennzahl(['wert' => zahl_text($einsaetze[30] ?? 0),
                     'label' => 'Einsätze in 30 Tagen', 'href' => '?r=einsaetze']) ?>
  </div>

  <?php
  $punkte = [];
  foreach (STAT_REITER as $schluessel => $text) {
      $punkte[] = ['text' => $text, 'href' => '?r=' . $schluessel, 'aktiv' => $reiter === $schluessel];
  }
  ui_reiter(['label' => 'Bereiche der Statistik', 'punkte' => $punkte]);
  ?>

  <div class="form-raster form-raster-links-breit">

<?php if ($reiter === 'nutzer'): ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Konten je Zeitraum', 'id' => 'k-konten-zeit',
                          'zahl' => 'von ' . zahl_text($kontenZahl)]); ?>
      <?php stat_zeitraumtabelle(STAT_FENSTER_KONTEN, [
          ['Aktiv',        '', $kontenZeit['aktiv'],      $kontenZahl],
          ['Angemeldet',   '', $kontenZeit['angemeldet'], $kontenZahl],
          ['Neu angelegt', '', $kontenZeit['angelegt'],   $kontenZahl],
      ]); ?>
      <p class="feld-klein">Aktiv heißt: angemeldet <strong>oder</strong> eines der
         Geräte hat sich gemeldet. <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Konten', 'id' => 'k-konten',
                          'zahl' => zahl_text($kontenZahl),
                          'aktion' => ['text' => 'NutzerInnen', 'href' => 'admin_users.php',
                                       'symbol' => 'gruppe']]); ?>
      <?php /* Oben, wer am meisten darf — die Reihenfolge von `ROLLEN`
               rueckwaerts. */ ?>
      <?php foreach (array_reverse(ROLLEN_MEHRZAHL, true) as $rolle => $t): ?>
        <?php ui_zeile(['text' => $t,
                        'klein' => prozent_text($nachRolle[$rolle], $kontenZahl),
                        'plaketten' => ui_plakette((string)$nachRolle[$rolle])]); ?>
      <?php endforeach; ?>
      <?php ui_zeile(['text' => 'Ohne Gerät',
                      'klein' => stat_klein(prozent_text($ohneGeraet, $kontenZahl),
                                            'sie tragen von Hand nach'),
                      'plaketten' => ui_plakette((string)$ohneGeraet)]); ?>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (rechts) */ ?>

<?php elseif ($reiter === 'einsaetze'): ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Einsätze je Zeitraum', 'id' => 'k-einsaetze-zeit',
                          'zahl' => zahl_text($einsaetzeGesamt) . ' gesamt']); ?>
      <?php
      $schnitt = [];
      foreach (array_keys(STAT_FENSTER_EINSAETZE) as $tage) {
          $schnitt[$tage] = $mitEinsatz[$tage] > 0
              ? zahl_text($einsaetze[$tage] / $mitEinsatz[$tage], 1) : '—';
      }
      stat_zeitraumtabelle(STAT_FENSTER_EINSAETZE, [
          ['Einsätze',     '',            $einsaetze,  null],
          ['NutzerInnen',  'mit Einsatz', $mitEinsatz, $kontenZahl],
          ['Ø je NutzerIn', 'mit Einsatz', $schnitt,   null],
      ]);
      ?>
      <p class="feld-klein">Gezählt ab dem Beginn des Einsatzes — ohne Demo-Konto,
         ohne Papierkorb. <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Herkunft der Einsätze', 'id' => 'k-herkunft',
                          'zahl' => zahl_text($einsaetze[30] ?? 0) . ' in 30 Tagen']); ?>
      <?php foreach ($herkunft as $wert => $n): ?>
        <?php ui_zeile(['text' => HERKUNFT_TEXTE[$wert]['lang'],
                        'klein' => prozent_text($n, $einsaetze[30] ?? 0),
                        'plaketten' => ui_plakette((string)$n)]); ?>
      <?php endforeach; ?>
      <?php if ($herkunftAndere > 0): ?>
        <?php ui_zeile(['text' => 'Andere',
                        'klein' => stat_klein(prozent_text($herkunftAndere, $einsaetze[30] ?? 0),
                                              'Werte, die diese Fassung nicht kennt'),
                        'plaketten' => ui_plakette((string)$herkunftAndere)]); ?>
      <?php endif; ?>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (rechts) */ ?>

<?php else: /* geraete */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Geräte je Zeitraum', 'id' => 'k-geraete-zeit',
                          'zahl' => 'von ' . zahl_text($geraeteZahl)]); ?>
      <?php stat_zeitraumtabelle(STAT_FENSTER_GERAETE, [
          ['Zuletzt gemeldet', '', $geraeteZeit['gemeldet'],  $geraeteZahl],
          ['Gekoppelt',        '', $geraeteZeit['gekoppelt'], $geraeteZahl],
      ]); ?>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Geräte', 'id' => 'k-geraete',
                          'zahl' => zahl_text($geraeteZahl)]); ?>
      <?php ui_zeile(['text' => 'Garmin-Uhren',
                      'klein' => prozent_text($nachArt['uhr'], $geraeteZahl),
                      'plaketten' => ui_plakette((string)$nachArt['uhr'])]); ?>
      <?php ui_zeile(['text' => 'Android-Handys',
                      'klein' => prozent_text($nachArt['handy'], $geraeteZahl),
                      'plaketten' => ui_plakette((string)$nachArt['handy'])]); ?>
      <?php if ($nachArt['sonstiges'] > 0): ?>
        <?php ui_zeile(['text' => 'Sonstige',
                        'klein' => prozent_text($nachArt['sonstiges'], $geraeteZahl),
                        'plaketten' => ui_plakette((string)$nachArt['sonstiges'])]); ?>
      <?php endif; ?>
      <?php if ($nachArt['unbekannt'] > 0): ?>
        <?php ui_zeile(['text' => 'Ohne Angabe',
                        'klein' => stat_klein(prozent_text($nachArt['unbekannt'], $geraeteZahl),
                                              'ältere Fassungen melden nichts über sich'),
                        'plaketten' => ui_plakette((string)$nachArt['unbekannt'])]); ?>
      <?php endif; ?>
      <?php ui_zeile(['text' => 'Deaktiviert',
                      'klein' => stat_klein(prozent_text($deaktiviert, $geraeteZahl),
                                            'Ingest gesperrt, Daten bleiben'),
                      'plaketten' => ui_plakette((string)$deaktiviert,
                          ['ton' => $deaktiviert > 0 ? 'orange' : 'neutral'])]); ?>

      <?php /* DER SATZ STATT EINER ZEILE (Z-02). Siehe Kopfkommentar. */ ?>
      <p class="feld-klein"><strong>Wear-OS-Uhren erscheinen hier nicht</strong>, weil
         sie über das gekoppelte Handy senden.
         <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (rechts) */ ?>
<?php endif; ?>

  </div><?php /* .form-raster */ ?>

<?php if ($reiter === 'geraete'): ?>
  <?php ui_karte_start(['titel' => 'Gerätemodelle', 'id' => 'k-modelle',
      'zahl' => count($modelle) . (count($modelle) === 1 ? ' Modell' : ' Modelle'),
      'aktion' => ['text' => 'Als CSV', 'symbol' => 'tausch',
                   'href' => '?r=geraete&export=csv&sort=' . e($sort) . '&richtung=' . e($richtung)]]); ?>
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
                <td class="zahl-spalte"><?= zahl_text($m['geraete']) ?></td>
                <td class="zahl-spalte"><?= e(prozent_text($m['geraete'], $geraeteZahl)) ?></td>
                <td class="zahl-spalte"><?= zahl_text($m['nutzer']) ?></td>
                <td class="zahl-spalte"><?= e(prozent_text($m['nutzer'], $kontenZahl)) ?></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
      <p class="feld-klein">Anteile bezogen auf alle <?= e(zahl_text($geraeteZahl)) ?> Geräte
         bzw. <?= e(zahl_text($kontenZahl)) ?> Konten, der Hersteller abgeleitet und nicht
         gespeichert. <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>
<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
