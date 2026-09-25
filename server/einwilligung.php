<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/einwilligung_lib.php';
require_once __DIR__ . '/protokoll_lib.php';
require_once __DIR__ . '/format_lib.php';   // datum_text() fuer die Fassungsdaten

/**
 * DAS EINWILLIGUNGSTOR (P5b/AP4, E-P5b-05, -15).
 *
 * WER HIER LANDET, KOMMT NUR MIT DEN HAEKCHEN WEITER — oder gar nicht, und
 * dann muss er trotzdem an seine Daten kommen. Deshalb stehen unten drei
 * Wege: Abmelden, Export, Konto loeschen. Ein Tor, das auch den Ausgang
 * versperrt, waere Noetigung; das ist keine Feinheit, sondern der Grund,
 * warum die Ausnahmeliste in `auth_guard.php` genau diese drei Seiten kennt.
 *
 * KEINE EIGENE SEITENFORM. Das Geruest ist das der Anwendung, nur ohne
 * Leiste: Die Bereichsnavigation fuehrte auf Seiten, die gerade nicht
 * erreichbar sind, und ein Menue, dessen Eintraege alle auf dieselbe Seite
 * zurueckwerfen, ist schlimmer als keines.
 *
 * DER TEXT STEHT NICHT HIER, SONDERN DAHINTER. Die Seite zeigt die
 * Ueberschrift, den Stand und einen Verweis — nicht den vollstaendigen
 * Vertrag. Wer zustimmt, ohne zu lesen, tut das auch bei eingeblendetem
 * Text; wer lesen will, bekommt ihn in der Fassung, die auch ohne Anmeldung
 * gilt. Und die Alternative — 60 000 Zeichen AVV in einem Kasten mit einem
 * Haken darunter — ist die Form, die niemand liest.
 */

$notice = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'einwilligen') {
    csrf_check();

    /* ALLE ODER KEINE. Die Seite zeigt, was offen ist; wer absendet, ohne
     * alles anzuhaken, bekommt sie wieder — mit einer Meldung, die sagt,
     * was fehlt. Eine Teilannahme zu speichern waere schlimmer als sie
     * abzulehnen: Sie saehe im Nachweis aus wie eine vollstaendige. */
    $verlangt = array_merge($einwilligungOffen['sperrt'], $einwilligungOffen['hinweis']);
    $fehlt = [];
    foreach ($verlangt as $schluessel) {
        if (empty($_POST['ew'][$schluessel])) { $fehlt[] = RT_TEXTE[$schluessel]; }
    }

    if ($fehlt) {
        $error = 'Es fehlt noch: ' . implode(', ', $fehlt) . '. Ohne alle drei geht '
               . 'es nicht weiter — du kannst dich aber abmelden, deine Daten '
               . 'ausleiten oder dein Konto löschen.';
    } else {
        $gesetzt = [];
        foreach ($verlangt as $schluessel) {
            if (einwilligung_setzen($userId, $schluessel)) { $gesetzt[] = $schluessel; }
        }
        /* DAS PROTOKOLL HAELT DEN NACHWEIS, nicht die Tabelle. In
         * `konto_einwilligungen` steht nur der aktuelle Stand — eine neue
         * Annahme ueberschreibt die alte. Wer wann welche Fassung
         * angenommen hat, steht hier, und es ueberlebt auch die
         * Kontoloeschung. */
        if ($gesetzt) {
            protokoll('verwaltung', 'einwilligung',
                      'Einwilligung erteilt: ' . implode(', ', array_map(
                          static fn(string $k): string => RT_TEXTE[$k] . ' ('
                              . RT_EINWILLIGUNG[$k]['wort'] . ')', $gesetzt)),
                      ['dokumente' => $gesetzt], $userId);
        }
        header('Location: index.php');
        exit;
    }
}

/* Nach dem POST neu lesen — sonst zeigte die Seite bei einem Fehlschlag den
 * Stand von vor dem Klick. */
$einwilligungOffen = einwilligung_offen($userId);
$verlangt = array_merge($einwilligungOffen['sperrt'], $einwilligungOffen['hinweis']);

if (!$verlangt) {
    /* Nichts offen — wer hierher kommt, ist entweder schon durch oder die
     * Betreiberin hat die Texte inzwischen zurueckgezogen. */
    header('Location: index.php');
    exit;
}

$stand = einwilligung_stand($userId);

ui_seite_start(['titel' => 'Zustimmung nötig']);
?>
<div class="rahmen rahmen-lesespalte">
  <main class="inhalt">
  <?php /* Ohne den Datenschutz-Streifen: Diese Seite IST der Weg, auf den er
           zeigt, und er stuende ueber seinem eigenen Ziel. */ ?>
  <?php ui_hinweise(false); ?>
    <h1>Bevor es weitergeht</h1>
    <?php ui_meldung($notice, $error); ?>

    <?php if ($einwilligungOffen['sperrt'] && $einwilligungOffen['hinweis']): ?>
      <p class="text">Es gibt <strong>neue Fassungen</strong> der Dokumente
         dieser Installation. Zwei davon musst du annehmen, eine zur Kenntnis
         nehmen — danach geht es weiter, wo du warst.</p>
    <?php elseif ($einwilligungOffen['sperrt']): ?>
      <p class="text">Es gibt eine <strong>neue Fassung</strong> — bitte
         annehmen, dann geht es weiter, wo du warst.</p>
    <?php else: ?>
      <p class="text">Es gibt eine <strong>neue Fassung</strong> zur
         Kenntnisnahme.</p>
    <?php endif; ?>

    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="einwilligen">
      <?php foreach ($verlangt as $schluessel): ?>
        <?php
          $art  = RT_EINWILLIGUNG[$schluessel];
          $s    = $stand[$schluessel]['stand'] ?? null;
          $alt  = $stand[$schluessel]['angenommen'] ?? null;
        ?>
        <?php ui_karte_start(['titel' => RT_TEXTE[$schluessel],
            'plakette' => $alt !== null
                ? ui_plakette('neue Fassung', ['ton' => 'orange'])
                : ui_plakette('neu', ['ton' => 'blau'])]); ?>
          <?php /* DER STAND DER NEUEN FASSUNG STEHT NICHT MEHR HIER, sondern
                   im Satz am Haekchen (`rt_haken_satz()`). Er stand bis Web
                   20.22.1 an beiden Stellen — zwei Zeilen auseinander
                   dieselbe Zahl, einmal als Auskunft und einmal als Teil der
                   Erklaerung. Geblieben ist, was NUR hier steht: welche
                   Fassung bisher angenommen war. */ ?>
          <p class="feld-hinweis">
            <?php if ($alt !== null): ?>
              Bisher angenommen: Fassung vom
              <?= e(datum_text((string)$alt)) ?>.
            <?php endif; ?>
            <a href="<?= e(RT_SEITEN[$schluessel]) ?>" target="_blank" rel="noopener">Text
               lesen</a> — er öffnet sich in einem neuen Fenster, damit du
               diese Seite nicht verlierst.
          </p>
          <?php /* DER WORTLAUT KOMMT AUS DEM KATALOG und nicht aus dem
                   Markup: „angenommen" und „zur Kenntnis genommen" tragen
                   den rechtlichen Unterschied (E-P5b-05), und ein Text, der
                   an zwei Stellen steht, laeuft auseinander. */ ?>
          <?php /* EIN HAEKCHEN UND KEIN SCHALTER (Mockup M-P5b-02a, Tor).
                   Ein Schiebeschalter steht fuer eine Einstellung, die man
                   an- und ausmacht; hier wird eine Erklaerung abgegeben, und
                   die kennt nur eine Richtung. Der Satz kommt aus dem
                   Katalog — dieselbe Quelle wie auf der
                   Registrierungsseite, damit die beiden nicht
                   auseinanderlaufen (`rt_haken_satz()`). */ ?>
          <label>
            <input type="checkbox" name="ew[<?= e($schluessel) ?>]" value="1"
                   <?= !empty($_POST['ew'][$schluessel]) ? 'checked' : '' ?>>
            <span><?= rt_haken_satz($schluessel,
                      $s !== null ? datum_text((string)$s) : null) ?></span>
          </label>
          <p class="feld-hinweis"><?= $art['art'] === 'annahme'
              ? 'Ohne diese Annahme geht es nicht weiter.'
              : 'Eine Kenntnisnahme ist keine Zustimmung — sie hält nur fest, '
              . 'dass du die Fassung gesehen hast.' ?></p>
        <?php ui_karte_ende(); ?>
      <?php endforeach; ?>

      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Weiter', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>

    <?php ui_karte_start(['titel' => 'Wenn du nicht zustimmen willst']); ?>
      <p class="feld-hinweis">Dann geht es hier nicht weiter — aber deine Daten
         bleiben deine. <strong>Diese drei Wege sind offen:</strong></p>
      <?php ui_zeile(['text' => 'Daten ausleiten',
          'klein' => 'Alles als CSV oder als versiegeltes Paket — dasselbe wie sonst',
          'href'  => 'import.php']); ?>
      <?php ui_zeile(['text' => 'Konto löschen',
          'klein' => 'Unter Einstellungen → Profil. 30 Tage Karenz; in der Zeit '
                   . 'nimmt eine Anmeldung die Löschung zurück',
          'href'  => 'einstellungen.php#karte-konto']); ?>
      <?php ui_zeile(['text' => 'Abmelden',
          'klein' => 'Die Frage kommt beim nächsten Anmelden wieder',
          'href'  => 'logout.php']); ?>
    <?php ui_karte_ende(); ?>
  </main>
</div>
<?php ui_seite_ende(); ?>
