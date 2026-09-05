<?php
declare(strict_types=1);
/**
 * WARTUNG — Uebergangsstand (S8/AP2).
 *
 * WAS AUS DIESER SEITE GEWORDEN IST. Sie trug bis Web 15.0.0 neun Bloecke auf
 * einer Flaeche: Wartungsmodus, Schluesselableitung, Logo, Umgebung,
 * Hintergrundjobs, Job-Ausloeser, Einsaetze ohne Diensttag, Migrationsliste
 * und den Balken darueber. Das war der Ausloeser fuer Backlog Nr. 77 und
 * einer der Befunde der S8-Sichtung (B-S8-03: vier Anliegen auf einer Seite).
 *
 * S8 teilt sie nicht auf, sondern loest sie AUF (E-S8-05). Wohin was gegangen
 * ist:
 *
 *   Wartungsmodus, Migrationen, Fassung     -> betrieb_updates.php
 *   Hintergrundjobs, Ausloeser, Token       -> betrieb_jobs.php
 *   Speichergrenze, Schwellen, Ablage       -> betrieb_server.php
 *   Schluesselableitung, Umgebung           -> betrieb_status.php (AP4)
 *   Logo der Installation                   -> admin_installation.php (AP3)
 *   Einsaetze ohne Diensttag                -> ersatzlos entfallen (E-S8-17)
 *
 * WARUM SIE UEBERGANGSWEISE NOCH STEHT. Zwei Gruende, und beide enden bald:
 *
 *   1. Der Notausgang `php update.php` auf der Kommandozeile. Er laeuft ohne
 *      Sitzung — fuer den Fall, dass die Anmeldung selbst von einer Migration
 *      abhaengt. Er bleibt an dieser Adresse, weil er in Runbook, Handbuch und
 *      in jeder aelteren Anleitung so steht.
 *   2. Die Logo-Karte. Sie zieht in AP3 auf die neue Seite „Installation";
 *      bis dahin waere sie sonst nirgends erreichbar.
 *
 * Ab AP3 ist der Web-Teil dieser Datei eine Weiterleitung auf
 * `betrieb_updates.php` und bleibt es bis P6 (Nr. 77) — die Adresse steht in zu
 * vielen Lesezeichen, als dass ein 404 die richtige Antwort waere.
 */
// Notausgang: Aufruf per Kommandozeile (SSH) laeuft ohne Web-Session —
// fuer den Fall, dass der Login selbst von einer Migration abhaengt.
//   php update.php
if (php_sapi_name() === 'cli') {
    require_once __DIR__ . '/db.php';              // liefert auch e()
} else {
    require_once __DIR__ . '/auth_guard.php';
    require_admin();
}
require_once __DIR__ . '/migration_lib.php';

/* ---- Kommandozeile: einstufig, dann Schluss --------------------------------
 *
 * Wer "php update.php" eintippt, hat die bewusste Handlung bereits vollzogen —
 * anders als beim Aufruf einer Seite im Browser. Die zweite Stufe (Vorschau,
 * dann Knopf) gibt es hier deshalb nicht, und die FREIGABE einer blockierten
 * Migration ebenfalls nicht: Ein Argument "--force" waere zu leicht aus einer
 * Anleitung abgeschrieben. Der Weg dorthin steht in der Ausgabe.
 */
if (php_sapi_name() === 'cli') {
    $lauf = migrationen_lauf(db(), true);
    foreach ($lauf['results'] as [$id, $label, $status, $detail, $zerstoert, $blockId, $web]) {
        printf("%-6s %-9s %-46s %s\n", strtoupper($status),
               $web !== null ? ('Web ' . $web) : '', $id, $detail);
        if ($status === 'stopp') {
            printf("%-6s %-46s %s\n", '', '',
                   '-> Daten sichern, dann unter Betrieb → Updates einzeln freigeben.');
        }
    }
    exit(0);
}

/* ---- Logo-Standard der Installation (E-P3-19/20, seit Web 9.10.0) ---------
 *
 * Bis Web 9.9.0 war der Standard eine Konstante in session_lib.php. Er betrifft
 * die ganze Installation und nicht ein Konto — die Anmeldeseite zeigt ihn, und
 * jedes Konto ohne eigene Wahl folgt ihm.
 *
 * DASS ER AUF DER WARTUNGSSEITE LAG, WAR EIN BEFUND (B-S8-10): Der Logo-Standard
 * ist Gestaltung, keine Wartung — und die Logo-Wahl je Konto steht im Profil,
 * also am anderen Ende der Anwendung. Er zieht in AP3 auf „Installation" und
 * steht dort neben Impressum und Datenschutz.
 */
$pdo = db();
$logoMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'logo_standard') {
    csrf_check();
    $wahl = (string)($_POST['logo'] ?? '');
    /* „wechselnd" ist seit Web 9.14.0 auch fuer die INSTALLATION waehlbar
     * (F-N1-C). Es war nur im Profil da — und eine Installation, die beide
     * Rettungsmittel fuehrt, hat denselben Grund dafuer wie eine einzelne
     * Person. Die Aufloesung liegt in logo_standard_aufgeloest(). */
    $namen = ['hubschrauber' => 'Hubschrauber (RTH)',
              'fahrzeug'     => 'Fahrzeug (NEF)',
              'wechselnd'    => 'wechselnd'];
    if (!isset($namen[$wahl])) {
        $logoMeldung = ['fehler', 'Unbekannte Logo-Wahl — es wurde nichts geändert.'];
    } else {
        $pdo->prepare('INSERT INTO app_state (k, v) VALUES (?, ?)
                       ON DUPLICATE KEY UPDATE v = VALUES(v)')
            ->execute(['logo_standard', $wahl]);
        $logoMeldung = ['ok', 'Standard der Installation: ' . $namen[$wahl]
            . ($wahl === 'wechselnd'
               ? '. Je Anmeldung wird neu gewürfelt — innerhalb einer Sitzung '
                 . 'bleibt das Logo stehen.'
               : '.')
            . ' Wer im Profil keine eigene Wahl getroffen hat, sieht das ab sofort.'];
    }
}

/**
 * Liegt der NEF-Platzhalter noch (E-P3-19)?
 *
 * Gefragt wird die DATEI, nicht eine Zahl im Code: Der Platzhalter traegt das
 * Wort in seinem Kopfkommentar, und sobald die echte Datei an seiner Stelle
 * liegt, verschwindet der Hinweis von selbst. Gelesen werden nur die ersten
 * 400 Byte — der Kommentar steht ganz oben.
 *
 * DIE DATEINAMEN HABEN SICH GEAENDERT. Erwartet wurde ein Ersatz 1:1 unter
 * gleichem Namen; gekommen ist er als 'gen-em_logo_nef*'. Beide Namen stehen
 * deshalb in der Liste: Eine aeltere Installation, die noch den Platzhalter
 * unter dem alten Namen traegt, soll den Hinweis weiterhin bekommen.
 */
function logo_platzhalter_liegt(): array
{
    $liegt = [];
    foreach (['gen-em_logo_nef.svg', 'gen-em_logo_nef_weiss.svg',
              'gen-em_logo_fahrzeug.svg', 'gen-em_logo_fahrzeug_weiss.svg'] as $datei) {
        $pfad = __DIR__ . '/assets/images/' . $datei;
        if (!is_file($pfad)) { continue; }
        $f = @fopen($pfad, 'rb');
        if ($f === false) { continue; }
        $kopf = (string)fread($f, 400);
        fclose($f);
        if (str_contains($kopf, 'PLATZHALTER')) { $liegt[] = $datei; }
    }
    return $liegt;
}

require_once __DIR__ . '/wartung_lib.php';
ui_seite_start(['titel' => 'Wartung']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'wartung']); ?>

  <?php ui_titelzeile(['titel' => 'Wartung']); ?>

  <?php /* Der Balken bleibt: Diese Seite und `login.php` sind neben
           Betrieb → Updates die einzigen, auf denen ein stehengebliebener
           Wartungsmodus ueberhaupt auffallen kann — alle anderen antworten
           mit 503 (E-S5W-05). */ ?>
  <?= wartung_balken() ?>

  <p class="seiten-erklaerung">Diese Seite ist aufgeteilt. Was hier stand,
     liegt jetzt im Menüblock <strong>Betrieb</strong> — jede Seite ein
     Anliegen.</p>

  <?php ui_karte_start(['titel' => 'Wo es jetzt steht']); ?>
    <?php
      /* Die Ziele als Zeilen mit Link. Sie sind bis AP5 nur über die Adresse
         erreichbar — genau deshalb steht die Liste hier: Ohne sie wäre die
         Neuordnung für die Dauer von drei Paketen unauffindbar. */
      $ziele = [
        ['betrieb_updates.php', 'Updates',
         'Wartungsmodus, ausstehende Migrationen, Fassung'],
        ['betrieb_jobs.php', 'Hintergrundjobs',
         'Zustand je Job, die drei Auslöser, Token'],
        ['betrieb_server.php', 'Servereinstellungen',
         'Speichergrenze, Warnschwellen, Belegung, Ablage'],
      ];
      foreach ($ziele as [$href, $text, $klein]) {
          ui_zeile(['text' => $text, 'klein' => $klein, 'href' => $href]);
      }
    ?>
    <p class="feld-hinweis"><strong>Ersatzlos entfallen</strong> ist die Karte
       „Einsätze ohne Diensttag": Jede NutzerIn sieht ihre eigenen als
       <em>Zuordnung offen</em> in der Diensttage-Leiste und ordnet sie selbst
       zu. Die Karten <strong>Schlüsselableitung</strong> und
       <strong>Umgebung</strong> werden zu Zeilen der Statusseite; bis dahin
       melden sie sich nicht — beide waren ohnehin nur im Problemfall
       sichtbar.</p>
  <?php ui_karte_ende(); ?>

  <?php /* ---- Logo der Installation (E-P3-19/20) — zieht in AP3 um ------ */ ?>
  <?php ui_karte_start(['titel' => 'Logo']); ?>
    <?php if ($logoMeldung !== null): ?>
      <?= ui_meldung_markup($logoMeldung[0], $logoMeldung[1]) ?>
    <?php endif; ?>
    <?php $platzhalter = logo_platzhalter_liegt(); ?>
    <?php if ($platzhalter): ?>
      <?= ui_meldung_markup('warn',
          'Das Fahrzeug-Logo (NEF) ist ein Platzhalter — es steht hier, damit die '
        . 'Logo-Wahl vollständig gebaut und geprüft werden kann, bevor die echte '
        . 'Datei vorliegt. Sie ersetzt ihn 1:1: gleicher Name, gleiche Maße, kein '
        . 'Eingriff im Code. Betroffen: ' . implode(', ', $platzhalter) . '. '
        . 'Dieser Hinweis verschwindet von selbst, sobald die echten Dateien liegen.') ?>
    <?php endif; ?>
    <form method="post">
      <?= csrf_field() ?><input type="hidden" name="action" value="logo_standard">
      <?php ui_segment(['name' => 'logo', 'id' => 'logo-standard',
                        'wert' => logo_standard(),
                        'optionen' => ['hubschrauber' => 'Hubschrauber (RTH)',
                                       'fahrzeug'     => 'Fahrzeug (NEF)',
                                       'wechselnd'    => 'wechselnd']]); ?>
      <p class="feld-hinweis">Der <strong>Standard dieser Installation</strong>. Er gilt für
         die Anmeldeseite und für jedes Konto, das im Profil keine eigene Wahl getroffen
         hat — eine getroffene Wahl bleibt unberührt. Die Änderung wirkt sofort, auch für
         bereits angemeldete Konten. <strong>Wechselnd</strong> würfelt je Anmeldung neu;
         innerhalb einer Sitzung bleibt das Logo stehen, damit es beim Blättern nicht
         springt.</p>
      <div class="listen-form-fuss">
        <?= ui_knopf(['text' => 'Standard speichern', 'symbol' => 'haken', 'art' => 'primaer']) ?>
      </div>
    </form>
  <?php ui_karte_ende(); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
