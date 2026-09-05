<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/migration_lib.php';
require_once __DIR__ . '/wartung_lib.php';
require_once __DIR__ . '/adminbackup_lib.php';   // edbak_groesse_text()
require_once __DIR__ . '/komplett_lib.php';      // juengster Komplett-Stand

/**
 * BETRIEB → UPDATES (S8/AP2, E-S8-05, R66).
 *
 * WARUM WARTUNGSMODUS UND MIGRATIONEN AUF EINER SEITE STEHEN. Weil sie
 * derselbe Vorgang sind. Der Ablauf eines Updates ist fuenfstufig — Backup
 * pruefen, Wartung an, Deploy, Migrationen, Wartung aus —, und drei dieser
 * Stufen finden hier statt. Sie auf zwei Seiten zu verteilen hiesse, mitten
 * im Vorgang das Menue zu benutzen.
 *
 * WAS R66 AENDERT. Die alte Liste zeigte ALLE 43 Migrationen, davon 41 mit
 * „Bereits angewendet" — eine Seite, auf der man scrollen muss, um die zwei
 * Zeilen zu finden, um die es geht. Hier steht nur, was aussteht; die
 * Ausgefuehrten liegen zugeklappt darunter und entfallen mit dem
 * Audit-Protokoll in P5.
 *
 * DER ZWEISTUFIGE ABLAUF BLEIBT: Der Aufruf zeigt an, der Knopf fuehrt aus.
 * Migrationen koennen Spalten loeschen; eine unwiderrufliche Handlung auf
 * einen GET hin ist immer falsch. Die Mechanik dazu steht in
 * `migration_lib.php` und wird von der Kommandozeile mitbenutzt.
 */

$pdo = db();

/* ---- Wartungsmodus ein- und ausschalten (S5 Paket W, E-S5W-01) -----------
 *
 * NICHT AUF DER KOMMANDOZEILE: Der Notausgang laeuft ohne Sitzung und ohne
 * CSRF; ein Schalter dort waere ein Schalter ohne Nachweis, wer ihn betaetigt
 * hat. Wer per SSH schalten muss, legt `wartung.lock` von Hand an — die Datei
 * IST der Schalter (E-S5W-02), und das steht im Runbook.
 */
$wartungMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && in_array($_POST['action'] ?? '', ['wartung_an', 'wartung_aus'], true)) {
    csrf_check();
    /* Wer geschaltet hat, kommt in die Datei — Name, sonst Adresse. Der
     * Balken zeigt es, und ohne diese Angabe waere bei mehreren Betreibenden
     * nicht zu erkennen, wessen Wartung gerade steht. */
    $wer = trim((string)($userName ?? '')) !== '' ? (string)$userName : $userEmail;
    if (($_POST['action'] ?? '') === 'wartung_an') {
        $wartungMeldung = wartung_einschalten($wer)
            ? ['ok', 'Wartungsmodus eingeschaltet — alle anderen Anfragen bekommen '
                   . '503. Geräte liefern nach. Diese Seite, die Anmeldung und der '
                   . 'Job-Abruf bleiben erreichbar.']
            : ['fehler', 'Die Datei ' . WARTUNG_DATEI . ' ließ sich nicht anlegen. '
                       . 'Der Wartungsmodus steht damit NICHT — bitte die '
                       . 'Schreibrechte des Verzeichnisses prüfen.'];
    } else {
        $wartungMeldung = wartung_ausschalten()
            ? ['ok', 'Wartungsmodus ausgeschaltet.']
            : ['fehler', 'Die Datei ' . WARTUNG_DATEI . ' ließ sich nicht löschen. '
                       . 'Der Wartungsmodus steht damit WEITERHIN — bitte die '
                       . 'Rechte prüfen oder die Datei von Hand entfernen.'];
    }
}

/* ---- Migrationen: Vorschau oder Lauf --------------------------------------
 *
 * Die Freigabe einer blockierten Migration ist bewusst KEIN globales
 * „trotzdem": Angehakt wird genau die eine Migration, deren Meldung man
 * gerade gelesen hat (D10, zweite Stufe).
 */
$ausfuehren = $_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'migrate';
if ($ausfuehren) { csrf_check(); }
$forcieren = [];
if ($ausfuehren && isset($_POST['forcieren']) && is_array($_POST['forcieren'])) {
    foreach ($_POST['forcieren'] as $fid) { $forcieren[(string)$fid] = true; }
}
$lauf      = migrationen_lauf($pdo, $ausfuehren, $forcieren);
$results   = $lauf['results'];
$blockiert = $lauf['blockiert'];

/* Zwei Listen aus einem Ergebnis: was ansteht und was erledigt ist. Die
 * Trennung ist die ganze Aenderung an R66 — sie geschieht hier und nicht im
 * Katalog, damit die Kommandozeile weiterhin alles sieht. */
$ausstehend = array_values(array_filter($results,
    static fn(array $r): bool => in_array($r[2], ['todo', 'stopp', 'warn', 'fail'], true)));
$erledigt   = array_values(array_filter($results,
    static fn(array $r): bool => $r[2] === 'ok'));

/* ---- Jüngstes Komplett-Backup: die Meldung statt des Knopfs (Mockup 05) --
 *
 * Bis Web 15.0.0 stand hier „Vorher ein Backup erstellen" mit einem Knopf auf
 * die Backup-Seite der NutzerIn — also auf das FALSCHE Backup: Vor einer
 * Migration schuetzt das Komplett-Backup der Installation, nicht die
 * `.edbak`-Datei eines Kontos. Jetzt nennt die Meldung den juengsten Stand
 * mit Alter und verweist dorthin. */
$kompStaende = komp_staende();
$kompJuengst = $kompStaende[0] ?? null;
$kompAlter   = null;
if ($kompJuengst !== null && !empty($kompJuengst['zeit'])) {
    $sek = time() - strtotime((string)$kompJuengst['zeit']);
    $kompAlter = $sek < 3600
        ? 'vor ' . max(1, (int)round($sek / 60)) . ' Minuten'
        : ($sek < 172800
           ? 'vor ' . (int)round($sek / 3600) . ' Stunden'
           : 'vor ' . (int)round($sek / 86400) . ' Tagen');
}

$stand = migrationen_stand($pdo);

ui_seite_start(['titel' => 'Updates']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_updates']); ?>

  <?php ui_titelzeile(['titel' => 'Updates']); ?>

  <?= wartung_balken() ?>

  <?php /* ---- Wartungsmodus (S5 Paket W, zieht mit E-S8-05 hierher) ------
     *
     * Zwei Zustaende, ein Knopf. Im Normalzustand ist „einschalten" die
     * Haupthandlung dieser Karte — aber NICHT die der Seite: Die ist das
     * Update selbst. Deshalb `neutral` und nicht `primaer` (Design.md 9.16).
     * Im Wartungsmodus ist „ausschalten" der Weg zurueck in den Betrieb und
     * damit das, was jemand hier sucht. */ ?>
  <?php ui_karte_start(['titel' => 'Wartungsmodus', 'id' => 'k-wartungsmodus',
      'plakette' => wartung_aktiv()
          ? ui_plakette('Wartung', ['ton' => 'orange'])
          : ui_plakette('aus · im Betrieb', ['ton' => 'neutral'])]); ?>
    <?php if ($wartungMeldung !== null): ?>
      <?= ui_meldung_markup($wartungMeldung[0], $wartungMeldung[1]) ?>
    <?php endif; ?>
    <p class="feld-hinweis">Schließt die Installation vorübergehend für alle außer
       Verwaltung und Betrieb: Jede andere Anfrage bekommt <strong>503</strong> mit
       <code>Retry-After: <?= WARTUNG_RETRY_S ?></code> statt eines Fehlers aus einer
       halb umgebauten Datenbank. Uhr und Handy puffern und liefern nach — es geht
       nichts verloren. Wer sich anmeldet und nicht verwaltet, wird sofort wieder
       abgemeldet und sieht die Wartungsseite.</p>
    <?php /* DER ABLAUF STEHT IN DER KARTE (Mockup 05) und nicht nur im Runbook:
             Er ist der Grund, warum der Schalter hierher gezogen ist. Fünf
             Zeilen, und die Reihenfolge IST die Sache — deshalb eine
             nummerierte Liste im Baustein `.text`. Ein blankes <ol> bekäme
             keine Nummern: Das Stylesheet setzt `list-style:none` auf alle
             Listen (sie sind sonst überall Aufzählungen im Markup, nicht im
             Bild), und `.text` nimmt das für Fließtext zurück. */ ?>
    <div class="text">
      <ol>
        <li>Komplett-Backup prüfen oder anstoßen</li>
        <li>Wartungsmodus einschalten</li>
        <li>Dateien einspielen (Deploy)</li>
        <li>Hier „Ausstehende ausführen"</li>
        <li>Wartungsmodus ausschalten</li>
      </ol>
    </div>
    <form method="post" action="betrieb_updates.php">
      <?= csrf_field() ?>
      <input type="hidden" name="action"
             value="<?= wartung_aktiv() ? 'wartung_aus' : 'wartung_an' ?>">
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => wartung_aktiv()
                        ? 'Wartungsmodus ausschalten' : 'Wartungsmodus einschalten',
                      'art' => wartung_aktiv() ? 'primaer' : 'neutral']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Ausstehende Updates (R66) ---------------------------------- */ ?>
  <?php ui_karte_start(['titel' => 'Ausstehende Updates', 'id' => 'k-ausstehend',
      'zahl' => $ausstehend
          ? count($ausstehend) . (count($ausstehend) === 1 ? ' Update' : ' Updates')
          : null,
      'plakette' => $ausstehend
          ? ui_plakette($blockiert > 0 ? $blockiert . ' blockiert' : 'steht aus',
                        ['ton' => $blockiert > 0 ? 'rot' : 'orange'])
          : ui_plakette('alles aktuell', ['ton' => 'blau'])]); ?>

    <?php if ($ausfuehren): ?>
      <?= $lauf['gelaufen']
            ? ui_meldung_markup('ok', 'Die ausstehenden Updates wurden angewendet — '
                . 'unten steht je Eintrag, was geschehen ist.')
            : ui_meldung_markup('info', 'Es war nichts anzuwenden.') ?>
    <?php endif; ?>

    <?php if (!$ausstehend && !$ausfuehren): ?>
      <?= ui_meldung_markup('info',
          'Alles aktuell · ' . ($stand['letzte'] !== null
              ? e((string)$stand['letzte']) . ($stand['wann'] !== null
                  ? ' am ' . e(fmt_local((string)$stand['wann'], 'd.m.Y'))
                  : '')
              : 'noch keine Migration verbucht')
          . '. Es steht nichts an.') ?>
    <?php endif; ?>

    <?php if ($ausstehend && !$ausfuehren): ?>
      <?php /* Die Backup-Meldung: der jüngste Komplett-Stand mit Alter und
               Link. Ohne Stand ist sie ein Fehler und kein Hinweis — dann gibt
               es nichts, worauf man nach einer verlorenen Spalte zurückgriffe. */ ?>
      <?= $kompJuengst !== null
          ? ui_meldung_markup('info', 'Jüngstes Komplett-Backup: '
              . e(fmt_local((string)$kompJuengst['zeit'], 'd.m.Y H:i'))
              . ($kompAlter !== null ? ', ' . e($kompAlter) : '')
              . ' · ' . edbak_groesse_text((int)$kompJuengst['groesse']) . '.',
              '', ui_knopf(['text' => 'Komplett-Backup', 'art' => 'neutral',
                            'symbol' => 'datenbank',
                            'href' => 'admin_komplettsicherung.php']))
          : ui_meldung_markup('warn', 'Es gibt noch KEIN Komplett-Backup dieser '
              . 'Installation. Migrationen können Spalten und Daten unwiderruflich '
              . 'entfernen — ein Backup dauert eine Minute, eine verlorene Spalte '
              . 'ist verloren.', 'Vorher sichern.',
              ui_knopf(['text' => 'Komplett-Backup', 'art' => 'neutral',
                        'symbol' => 'datenbank',
                        'href' => 'admin_komplettsicherung.php'])) ?>
      <?php if ($blockiert > 0): ?>
        <?= ui_meldung_markup('fehler', $blockiert . ' Migration(en) werden NICHT '
            . 'ausgeführt, weil sie Spalten löschen würden, in denen noch Daten '
            . 'stehen. Unten ist je Eintrag genannt, um welche Spalte und wie '
            . 'viele Zeilen es geht. Wer sie behalten will, trägt sie vorher von '
            . 'Hand ein (oder sichert sie außerhalb) und gibt die Migration '
            . 'danach einzeln frei.') ?>
      <?php endif; ?>
      <form method="post" action="betrieb_updates.php" id="migform">
        <?= csrf_field() ?><input type="hidden" name="action" value="migrate">
      </form>
    <?php endif; ?>

    <?php foreach ($ausstehend as [$id, $label, $status, $detail, $zerstoert, $blockId, $web]): ?>
      <?php
        [$statusText, $statusTon] = match ($status) {
            'todo'  => ['steht aus', 'orange'],
            'warn'  => ['Hinweis',   'orange'],
            'stopp' => ['blockiert', 'rot'],
            default => ['Fehler',    'rot'],
        };
        $klein = [$detail];
        if ($zerstoert !== null) { $klein[] = 'Löscht Daten: ' . $zerstoert; }
        $klein[] = $id;
        $plaketten = ui_plakette($statusText, ['ton' => $statusTon]);
        if ($web !== null) { $plaketten .= ui_plakette('Web ' . $web, ['ton' => 'neutral']); }
        /* Das Freigabe-Häkchen steht VORN in der Zeile — dort, wo der Baustein
           Auswahlkästchen erwartet. Es gehört über `form=` zum Formular oben. */
        $vorn = ($blockId !== null && !$ausfuehren)
            ? '<input type="checkbox" name="forcieren[]" form="migform" value="'
              . e($blockId) . '" aria-label="' . e($label)
              . ' trotzdem ausführen — die Daten sind gesichert">'
            : '';
        ui_zeile(['vorn' => $vorn, 'text' => $label,
                  'klein' => implode(' · ', $klein), 'plaketten' => $plaketten]);
      ?>
    <?php endforeach; ?>

    <?php if ($ausstehend && !$ausfuehren): ?>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Ausstehende ausführen', 'art' => 'primaer',
                      'symbol' => 'datenbank', 'attr' => ' form="migform"']) ?>
      </div>
      <p class="feld-klein">Führt der Reihe nach aus, was oben steht; blockierte
         Einträge nur mit gesetztem Häkchen. Der Aufruf dieser Seite ändert
         nichts — erst dieser Knopf.</p>
    <?php endif; ?>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Ausgeführt: zugeklappt, bis P5 das Audit-Protokoll bringt -- */ ?>
  <?php ui_karte_start(['titel' => 'Ausgeführt', 'id' => 'k-ausgefuehrt',
      'zahl' => (string)count($erledigt),
      'vorschau' => $stand['letzte'] !== null
          ? 'zuletzt ' . (string)$stand['letzte']
            . ($stand['wann'] !== null
               ? ' am ' . fmt_local((string)$stand['wann'], 'd.m.Y H:i') : '')
          : 'noch nichts verbucht']); ?>
    <?php foreach (array_reverse($erledigt) as [$id, $label, , $detail, , , $web]): ?>
      <?php
        $plaketten = ui_plakette('erledigt', ['ton' => 'blau']);
        if ($web !== null) { $plaketten .= ui_plakette('Web ' . $web, ['ton' => 'neutral']); }
        ui_zeile(['text' => $label, 'klein' => $detail . ' · ' . $id,
                  'plaketten' => $plaketten]);
      ?>
    <?php endforeach; ?>
    <p class="feld-hinweis">Diese Liste steht hier bis P5. Danach führt das
       Audit-Protokoll die ausgeführten Kennungen (R66), und die Karte
       entfällt.</p>
  <?php ui_karte_ende(true); ?>

  <?php /* ---- Fassung ------------------------------------------------------
     *
     * Ersetzt den Kopf der alten Wartungsseite. Es ist die Stelle, an der der
     * Torwaechter (P5, R40.4) spaeter anzeigt, dass er gesperrt hat. */ ?>
  <?php ui_karte_start(['titel' => 'Fassung', 'id' => 'k-fassung']); ?>
    <?php
      ui_zeile(['text' => 'Web', 'klein' => 'server/version.php',
                'plaketten' => ui_plakette(WEB_VERSION, ['ton' => 'blau'])]);
      ui_zeile(['text' => 'Datenbankstand',
                'klein' => 'höchste ausgeführte Migration · ' . $stand['zahl']
                         . ' verbucht',
                'plaketten' => ui_plakette($stand['letzte'] ?? '—',
                                           ['ton' => 'neutral'])]);
      /* DIE UHR-FASSUNG STEHT NICHT AUF DEM SERVER (gemessen in AP2).
         Das Konzept nannte als Quelle „eine Konstante in `ingest.php`/`api`,
         prüfen" — es gibt keine: `ingest.php` kennt weder eine Fassungsangabe
         der Uhr noch eine Untergrenze, und der einzige Treffer auf „Fassung"
         in der Datei ist ein Kommentar über Uhr-Fassungen OHNE `day_ref`, also
         gerade der Rückfall für ALTE Uhren. Der JSON-Vertrag ist bewusst
         abwärtskompatibel (R12). Eine Zahl hier zu erfinden wäre schlimmer als
         keine — sie sähe aus wie eine Zusage, die niemand einlöst. */
      ui_zeile(['text' => 'Uhr-App',
                'klein' => 'Der Server verlangt keinen Mindeststand — der '
                         . 'JSON-Vertrag ist abwärtskompatibel (R12). Welche '
                         . 'Fassung ein Gerät fährt, steht auf der Sync-Seite '
                         . 'der Uhr.',
                'plaketten' => ui_plakette('ohne Mindeststand', ['ton' => 'neutral'])]);
      require_once __DIR__ . '/apk_lib.php';
      $apk  = apk_liste();
      $apkV = $apk ? ($apk[0]['version'] ?? null) : null;
      ui_zeile(['text' => 'Android-App',
                'klein' => $apk
                    ? 'APK auf dem Server, Stand '
                      . fmt_local(gmdate('Y-m-d H:i:s', (int)$apk[0]['stand']), 'd.m.Y')
                    : 'kein APK auf dem Server — die App kommt über den Store (R65)',
                'plaketten' => ui_plakette($apkV ?? '—', ['ton' => 'neutral'])]);
    ?>
  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
