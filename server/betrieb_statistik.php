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
 * und wird bei jedem Reset daraus neu hergestellt. Ihn in einer
 * Statistik mitzuzaehlen hiesse, gut hundert erfundene Einsaetze als Nutzung
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
 * DER ZEITRAUM IST WAEHLBAR (seit Web 21.5.0, R4-23, Nr. 122 a, Bild
 * M-R4-23). Vier Pillen bestimmen Kennzahl und Herkunft, Von/Bis einen
 * eigenen Zeitraum; dann hat jede Tabelle eine Spalte „im Zeitraum". Die
 * Regeln stehen in `statistik_zeitraum()`, die Reihe in `ui_zeitraumwahl()`.
 * „Aktiv", „angemeldet" und „gemeldet" gibt es dort nur bis heute
 * (F-R4-66, E-R4-55) — Begruendung bei `$bisHeute`.
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

/* DER ZEITRAUM (R4-23, Nr. 122 a, Bild M-R4-23). Ein festes Fenster (`t`)
 * bestimmt die Kennzahl „Einsätze in …" und die Herkunft; die Tabellen
 * zeigen weiter jedes Fenster. Ein EIGENER Zeitraum (`von`, `bis`) macht aus
 * jeder Tabelle eine Spalte „im Zeitraum". Die Regeln stehen in
 * `statistik_zeitraum()`. Reiter, Kennzahlen und Pillen tragen ihn weiter. */
$zeitraum = statistik_zeitraum($_GET);
$eigen    = $zeitraum['art'] === 'eigen';
$zAdresse = statistik_zeitraum_adresse($zeitraum);
/* „AKTIV", „ANGEMELDET" UND „GEMELDET" NUR BIS HEUTE (F-R4-66, E-R4-55).
 * Gespeichert ist je Konto nur die LETZTE Anmeldung, je Gerät nur die
 * letzte Meldung. Reicht der Zeitraum bis heute, ist „zuletzt nach dem
 * Beginn" dasselbe wie „im Zeitraum"; endet er früher, zählte dieselbe
 * Rechnung nur, wer sich danach NICHT mehr gemeldet hat — und die Zahl
 * sänke, je länger der Zeitraum zurückliegt. Dann steht „—". */
$bisHeute = $eigen && $zeitraum['bis'] === heute_lokal();

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
 * Liegt ein Zeitpunkt (UTC) im EIGENEN Zeitraum — Untergrenze einschließlich,
 * Obergrenze ausschließlich, und nicht nach jetzt (R4-23)?
 */
function stat_im_zeitraum(?string $wann, array $z, int $jetzt): bool
{
    if ($wann === null || $wann === '') { return false; }
    $t = strtotime($wann . ' UTC');
    return $t !== false && $t <= $jetzt
        && $t >= strtotime($z['unten'] . ' UTC') && $t < strtotime($z['oben'] . ' UTC');
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
 * daraus, deshalb laeuft sie auf jedem Reiter — bei einem eigenen Zeitraum
 * nicht: Dann braucht keine Karte die festen Fenster, und die Kennzahl
 * kommt aus der Abfrage des Zeitraums. */
$einsaetze = []; $mitEinsatz = [];
if ($eigen) {
    $st = $pdo->prepare(statistik_zeitraum_sql($zeitraum));
    $st->execute(array_merge([$demoId], statistik_bedingung_werte($zeitraum)));
    $r = $st->fetch() ?: [];
    $imZeitraum         = (int)($r['n'] ?? 0);
    $mitEinsatzZeitraum = (int)($r['k'] ?? 0);
    $zeitraumText       = 'im Zeitraum';
} else {
    $st = $pdo->prepare(statistik_einsaetze_sql());
    $st->execute([$demoId]);
    $r = $st->fetch() ?: [];
    foreach (array_keys(STAT_FENSTER_EINSAETZE) as $tage) {
        $einsaetze[$tage]  = (int)($r['n' . $tage] ?? 0);
        $mitEinsatz[$tage] = (int)($r['k' . $tage] ?? 0);
    }
    $imZeitraum         = $einsaetze[$zeitraum['tage']] ?? 0;
    $mitEinsatzZeitraum = $mitEinsatz[$zeitraum['tage']] ?? 0;
    $zeitraumText       = STAT_WAHL[$zeitraum['t']][1];
}

/* ======================================================================== */
/* ---- Die Diagramme (R4-24, Nr. 122 b, Bild M-R4-24) --------------------
 *
 * Eine Einteilung des gewählten Zeitraums in Tage, Wochen oder Monate
 * (`statistik_einteilung()`, E-R4-59), eine Säule je Fach. Die Einsätze je
 * Fach kommen aus EINER Abfrage je Stunde und Konto — dieselbe liefert die
 * Konten mit Einsatz je Fach für die kleinen Vielfachen unter NutzerInnen.
 * „Neu angelegt" und „gekoppelt" zählt PHP aus den Zeilen, die die Reiter
 * ohnehin lesen. */
$einteilung = statistik_einteilung($zeitraum);
$faecher    = $einteilung['faecher'];
$fachWort   = ['tag' => ['Tag', 'je Tag'], 'woche' => ['Woche', 'je Woche'],
               'monat' => ['Monat', 'je Monat']][$einteilung['art']];
$jeFach = static fn(): array => array_fill(0, count($faecher), 0);
/* Liegt ein Zeitpunkt im gewählten Zeitraum? Das erste Fach eines festen
 * Fensters beginnt um Mitternacht, das Fenster selbst jetzt minus N Tage —
 * gezählt wird nur, was im Fenster liegt, wie in der Abfrage der Einsätze. */
$imGewaehlten = static fn(?string $wann): bool => $eigen
    ? stat_im_zeitraum($wann, $zeitraum, $jetzt)
    : stat_im_fenster($wann, (int)$zeitraum['tage'], $jetzt);
$einsaetzeJeFach = $jeFach(); $kontenJeFach = $jeFach();
if ($reiter === 'einsaetze' || $reiter === 'nutzer') {
    $st = $pdo->prepare(statistik_stunden_sql($zeitraum));
    $st->execute(array_merge([$demoId], statistik_bedingung_werte($zeitraum)));
    $wer = [];
    foreach ($st->fetchAll() as $z) {
        $f = statistik_fach($einteilung, (string)$z['stunde']);
        if ($f === null) { continue; }
        $einsaetzeJeFach[$f] += (int)$z['n'];
        $wer[$f][(int)$z['user_id']] = true;
    }
    foreach ($wer as $f => $ids) { $kontenJeFach[$f] = count($ids); }
}

/** Die Werte eines Diagramms: je Fach [Beschriftung, Zahl]. */
function stat_saeulen(array $faecher, array $zahlen): array
{
    $werte = [];
    foreach ($faecher as $i => $f) { $werte[] = [$f['text'], (int)($zahlen[$i] ?? 0)]; }
    return $werte;
}

/**
 * Der Satz unter einem Diagramm: Einteilung, erster und letzter Tag des
 * Zeitraums mit Jahreszahl, und was angebrochen sein kann. Bis R4-24 nannte
 * er die Anfänge des ersten und letzten Fachs ohne Jahr — „22.09. bis
 * 28.09." über ein ganzes Jahr las sich wie eine Woche.
 */
function stat_achsensatz(array $einteilung): string
{
    $spanne = tage_spanne_text($einteilung['erster'], $einteilung['letzter']);
    $heute  = $einteilung['bis_heute'];
    return match ($einteilung['art']) {
        'tag'   => 'Je Tag, ' . $spanne . ($heute ? '; heute ist nicht vorbei.' : '.'),
        'woche' => 'Je Woche ab Montag, ' . $spanne . '; die erste und die letzte Woche '
                 . 'können angebrochen sein.',
        default => 'Je Monat, ' . $spanne . '; der erste und der letzte Monat '
                 . 'können angebrochen sein.',
    };
}

/** Die Zusammenfassung für `aria-label` — die Tabelle trägt die Einzelwerte. */
function stat_beschreibung(string $was, array $werte): string
{
    $h = null;
    foreach ($werte as [$t, $w]) { if ($h === null || $w > $h[1]) { $h = [$t, $w]; } }
    return $was . ', ' . count($werte) . ' Säulen'
         . ($h !== null && $h[1] > 0 ? ', höchstens ' . zahl_text($h[1]) . ' ab ' . $h[0] : ', alle 0');
}

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
    if ($eigen) {
        /* Eine Spalte „im Zeitraum" (R4-23). Zu „aktiv" und „angemeldet"
         * siehe `$bisHeute` oben. */
        $kontenZeit['aktiv']['z'] = $bisHeute ? count(array_filter($konten,
            static fn($k) => stat_im_zeitraum($k['last_login'] ?? null, $zeitraum, $jetzt)
                          || stat_im_zeitraum($k['geraet_zuletzt'] ?? null, $zeitraum, $jetzt))) : '—';
        $kontenZeit['angemeldet']['z'] = $bisHeute ? count(array_filter($konten,
            static fn($k) => stat_im_zeitraum($k['last_login'] ?? null, $zeitraum, $jetzt))) : '—';
        $kontenZeit['angelegt']['z'] = count(array_filter($konten,
            static fn($k) => stat_im_zeitraum($k['created_at'] ?? null, $zeitraum, $jetzt)));
    }
    $angelegtJeFach = $jeFach();
    foreach ($konten as $k) {
        $f = statistik_fach($einteilung, $k['created_at'] ?? null);
        if ($f !== null && $imGewaehlten($k['created_at'] ?? null)) { $angelegtJeFach[$f]++; }
    }
    foreach ($eigen ? [] : array_keys(STAT_FENSTER_KONTEN) as $tage) {
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
    /* HERKUNFT IM GEWÄHLTEN ZEITRAUM (E-P5c-45, Nr. 80; bis Web 21.4.1 fest
     * 30 Tage, R4-23). Summen einer vorhandenen
     * Spalte, nur fuer die BetreiberIn — keine Datenschutz-Vorbedingung,
     * anders als Nr. 80 fuer die Momentaufnahme am Einsatz verlangt. Alle
     * sechs Werte aus `HERKUNFT_WERTE`, auch mit 0: Eine fehlende Zeile saehe
     * aus wie eine Herkunft, die es nicht gibt. */
    $st = $pdo->prepare(statistik_herkunft_sql($zeitraum));
    $st->execute(array_merge([$demoId], statistik_bedingung_werte($zeitraum)));
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

    $gekoppeltJeFach = $jeFach();
    foreach ($geraete as $g) {
        $f = statistik_fach($einteilung, $g['created_at'] ?? null);
        if ($f !== null && $imGewaehlten($g['created_at'] ?? null)) { $gekoppeltJeFach[$f]++; }
    }
    $geraeteZeit = ['gemeldet' => [], 'gekoppelt' => []];
    if ($eigen) {
        $geraeteZeit['gemeldet']['z'] = $bisHeute ? count(array_filter($geraete,
            static fn($g) => stat_im_zeitraum($g['last_seen'] ?? null, $zeitraum, $jetzt))) : '—';
        $geraeteZeit['gekoppelt']['z'] = count(array_filter($geraete,
            static fn($g) => stat_im_zeitraum($g['created_at'] ?? null, $zeitraum, $jetzt)));
    }
    foreach ($eigen ? [] : array_keys(STAT_FENSTER_GERAETE) as $tage) {
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
 * @param array<int|string,string> $fenster  Tage => Spaltenkopf; bei einem
 *        eigenen Zeitraum (R4-23) Schlüssel wie 'z', 'w', 'd'
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
                     'href' => '?r=nutzer' . $zAdresse]) ?>
    <?= ui_kennzahl(['wert' => zahl_text($geraeteZahl), 'label' => 'Geräte',
                     'href' => '?r=geraete' . $zAdresse]) ?>
    <?= ui_kennzahl(['wert' => zahl_text($einsaetzeGesamt), 'label' => 'Einsätze gesamt',
                     'href' => '?r=einsaetze' . $zAdresse]) ?>
    <?= ui_kennzahl(['wert' => zahl_text($imZeitraum),
                     'label' => 'Einsätze ' . $zeitraumText, 'href' => '?r=einsaetze' . $zAdresse]) ?>
  </div>

  <?php
  /* DIE ZEITRAUMWAHL ZWISCHEN KENNZAHLEN UND REITERN (R4-23, Bild M-R4-23):
   * Sie gilt für alle drei Reiter, also steht sie über ihnen. */
  $pillen = [];
  foreach (STAT_WAHL as $t => [$pille]) {
      $pillen[] = ['text' => $pille,
                   'href' => '?r=' . $reiter . ($t === STAT_WAHL_VORGABE ? '' : '&t=' . $t),
                   'aktiv' => !$eigen && $zeitraum['t'] === $t];
  }
  ui_zeitraumwahl([
      'label'     => 'Zeitraum der Statistik',
      'versteckt' => ['r' => $reiter],
      'pillen'    => $pillen,
      'von'       => $eigen ? $zeitraum['von'] : '',
      'bis'       => $eigen ? $zeitraum['bis'] : '',
      'max'       => heute_lokal(),
      'eigen'     => $eigen ? ['text' => zeitraum_text($zeitraum['unten'], $zeitraum['bis_utc']),
                               'href' => '?r=' . $reiter] : null,
  ]);
  if (isset($zeitraum['fehler'])) {
      ui_meldung(null, $zeitraum['fehler'] . ' Gezeigt werden die ' . STAT_WAHL[STAT_WAHL_VORGABE][0] . '.');
  }

  $punkte = [];
  foreach (STAT_REITER as $schluessel => $text) {
      $punkte[] = ['text' => $text, 'href' => '?r=' . $schluessel . $zAdresse,
                   'aktiv' => $reiter === $schluessel];
  }
  ui_reiter(['label' => 'Bereiche der Statistik', 'punkte' => $punkte]);

  /* Die Zeile, die bei eigenem Zeitraum unter jeder Tabelle steht, die
   * „aktiv", „angemeldet" oder „gemeldet" führt (F-R4-66, E-R4-55). */
  $nurBisHeute = $eigen && !$bisHeute
      ? ' „—" heißt: nur zählbar, wenn der Zeitraum bis heute reicht — gespeichert ist '
      : '';
  ?>

  <div class="form-raster form-raster-links-breit">

<?php if ($reiter === 'nutzer'): ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Konten je Zeitraum', 'id' => 'k-konten-zeit',
                          'zahl' => 'von ' . zahl_text($kontenZahl)]); ?>
      <?php
      /* ZWEI KLEINE VIELFACHE (Bild M-R4-24, Zustand B; angepasst nach
       * E-R4-55): „mit Einsatz" und „neu angelegt" haben verschiedene
       * Größenordnungen, also je ein Diagramm mit eigener Skala. „Aktiv" und
       * „angemeldet" je Fach gibt es nicht — die Anlage kennt nur die letzte
       * Anmeldung (F-R4-66). */
      $werteM = stat_saeulen($faecher, $kontenJeFach);
      $werteN = stat_saeulen($faecher, $angelegtJeFach);
      ?>
      <div class="kleinvielfach">
        <div>
          <h3 class="kleinvielfach-titel">Mit Einsatz</h3>
          <p class="kleinvielfach-wert"><?= e(zahl_text($mitEinsatzZeitraum)) ?>
             <span><?= e($zeitraumText) ?></span></p>
          <?= ui_diagramm_saeulen(['werte' => $werteM, 'klein' => true,
                  'beschreibung' => stat_beschreibung('Konten mit Einsatz ' . $fachWort[1], $werteM)]) ?>
        </div>
        <div>
          <h3 class="kleinvielfach-titel">Neu angelegt</h3>
          <p class="kleinvielfach-wert"><?= e(zahl_text(array_sum($angelegtJeFach))) ?>
             <span><?= e($zeitraumText) ?></span></p>
          <?= ui_diagramm_saeulen(['werte' => $werteN, 'klein' => true,
                  'beschreibung' => stat_beschreibung('Neu angelegte Konten ' . $fachWort[1], $werteN)]) ?>
        </div>
      </div>
      <p class="feld-klein"><?= e(stat_achsensatz($einteilung) . ' Der Höchstwert ist beschriftet; '
          . '„mit Einsatz" zählt die Konten, die dort mindestens einen Einsatz begonnen haben.') ?></p>
      <?php if ($eigen): ?>
        <?php stat_zeitraumtabelle(['z' => 'im Zeitraum'], [
            ['Aktiv',        '',            $kontenZeit['aktiv'],      $kontenZahl],
            ['Angemeldet',   '',            $kontenZeit['angemeldet'], $kontenZahl],
            ['Neu angelegt', '',            $kontenZeit['angelegt'],   $kontenZahl],
            ['NutzerInnen',  'mit Einsatz', ['z' => $mitEinsatzZeitraum], $kontenZahl],
        ]); ?>
      <?php else: ?>
        <?php stat_zeitraumtabelle(STAT_FENSTER_KONTEN, [
            ['Aktiv',        '', $kontenZeit['aktiv'],      $kontenZahl],
            ['Angemeldet',   '', $kontenZeit['angemeldet'], $kontenZahl],
            ['Neu angelegt', '', $kontenZeit['angelegt'],   $kontenZahl],
        ]); ?>
      <?php endif; ?>
      <p class="feld-klein">Aktiv heißt: angemeldet <strong>oder</strong> eines der
         Geräte hat sich gemeldet.<?= $nurBisHeute !== ''
             ? e($nurBisHeute . 'je Konto nur die letzte Anmeldung.') : '' ?>
         <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
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
    <?php if ($eigen): ?>
    <?php ui_karte_start(['titel' => 'Einsätze im Zeitraum', 'id' => 'k-einsaetze-zeit',
                          'zahl' => zeitraum_text($zeitraum['unten'], $zeitraum['bis_utc'])
                                  . ' · ' . zahl_text($zeitraum['tage'])
                                  . ($zeitraum['tage'] === 1 ? ' Tag' : ' Tage')]); ?>
      <?php $werteE = stat_saeulen($faecher, $einsaetzeJeFach); ?>
      <?= ui_diagramm_saeulen([
          'werte' => $werteE,
          'beschreibung' => stat_beschreibung('Einsätze ' . $fachWort[1], $werteE),
          'unter' => stat_achsensatz($einteilung),
      ]) ?>
      <?php
      /* EINE SPALTE UND DER SCHNITT (Bild M-R4-23, Zustand B): Ein eigener
       * Zeitraum ist verschieden lang, erst Wochen- und Tagesschnitt machen
       * ihn mit einem anderen vergleichbar. Nur für die Einsätze — ein
       * Schnitt der NutzerInnen je Tag wäre keine Zahl, die jemand fragt. */
      $tage = $zeitraum['tage'];
      stat_zeitraumtabelle(['z' => 'im Zeitraum', 'w' => 'Ø je Woche', 'd' => 'Ø je Tag'], [
          ['Einsätze', '', ['z' => $imZeitraum,
                            'w' => zahl_text($imZeitraum * 7 / $tage, 1),
                            'd' => zahl_text($imZeitraum / $tage, 1)], null],
          ['NutzerInnen', 'mit Einsatz', ['z' => $mitEinsatzZeitraum, 'w' => '—', 'd' => '—'],
           $kontenZahl],
          ['Ø je NutzerIn', 'mit Einsatz',
           ['z' => $mitEinsatzZeitraum > 0 ? zahl_text($imZeitraum / $mitEinsatzZeitraum, 1) : '—',
            'w' => '—', 'd' => '—'], null],
      ]);
      ?>
      <p class="feld-klein">Gezählt ab dem Beginn des Einsatzes, Tagesgrenzen in
         <?= e((string)konfig('app.timezone', 'Europe/Berlin')) ?> — ohne Demo-Konto,
         ohne Papierkorb. <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
    <?php ui_karte_ende(); ?>
    <?php else: ?>
    <?php ui_karte_start(['titel' => 'Einsätze je Zeitraum', 'id' => 'k-einsaetze-zeit',
                          'zahl' => zahl_text($einsaetzeGesamt) . ' gesamt']); ?>
      <?php $werteE = stat_saeulen($faecher, $einsaetzeJeFach); ?>
      <?= ui_diagramm_saeulen([
          'werte' => $werteE,
          'beschreibung' => stat_beschreibung('Einsätze ' . $fachWort[1], $werteE),
          'unter' => stat_achsensatz($einteilung),
      ]) ?>
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
    <?php endif; ?>
  </div><?php /* .form-spalte (links) */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Herkunft der Einsätze', 'id' => 'k-herkunft',
                          'zahl' => zahl_text($imZeitraum) . ' ' . $zeitraumText]); ?>
      <?php
      /* ANTEILE ALS BALKEN (R4-24, Bild M-R4-24) statt Zeilen mit Plakette:
       * Nebeneinander an einer gemeinsamen Linie liest das Auge den Anteil
       * genauer als an einer Zahl allein. Alle sechs Herkünfte, auch mit 0. */
      $zeilen = [];
      foreach ($herkunft as $wert => $n) { $zeilen[] = [HERKUNFT_TEXTE[$wert]['lang'], $n]; }
      if ($herkunftAndere > 0) { $zeilen[] = ['Andere', $herkunftAndere]; }
      echo ui_diagramm_balken(['zeilen' => $zeilen, 'bezug' => $imZeitraum]);
      ?>
      <p class="feld-klein">Anteile am gewählten Zeitraum.<?= $herkunftAndere > 0
          ? ' „Andere" sind Werte, die diese Fassung nicht kennt.' : '' ?></p>
    <?php ui_karte_ende(); ?>
  </div><?php /* .form-spalte (rechts) */ ?>

<?php else: /* geraete */ ?>
  <div class="form-spalte">
    <?php ui_karte_start(['titel' => 'Geräte je Zeitraum', 'id' => 'k-geraete-zeit',
                          'zahl' => 'von ' . zahl_text($geraeteZahl)]); ?>
      <?php $werteG = stat_saeulen($faecher, $gekoppeltJeFach); ?>
      <?= ui_diagramm_saeulen([
          'werte' => $werteG,
          'beschreibung' => stat_beschreibung('Gekoppelte Geräte ' . $fachWort[1], $werteG),
          'unter' => 'Gekoppelt. ' . stat_achsensatz($einteilung)
                   . ' „Zuletzt gemeldet" gibt es nicht je ' . $fachWort[0]
                   . ': Gespeichert ist je Gerät nur die letzte Meldung.',
      ]) ?>
      <?php stat_zeitraumtabelle($eigen ? ['z' => 'im Zeitraum'] : STAT_FENSTER_GERAETE, [
          ['Zuletzt gemeldet', '', $geraeteZeit['gemeldet'],  $geraeteZahl],
          ['Gekoppelt',        '', $geraeteZeit['gekoppelt'], $geraeteZahl],
      ]); ?>
      <?php if ($nurBisHeute !== ''): ?>
        <p class="feld-klein"><?= e(ltrim($nurBisHeute) . 'je Gerät nur die letzte Meldung.') ?>
           <a href="hilfe.php#12-2-statistik">Handbuch: Statistik</a></p>
      <?php endif; ?>
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
