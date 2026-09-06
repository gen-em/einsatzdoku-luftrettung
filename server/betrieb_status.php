<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/status_lib.php';

/**
 * BETRIEB -> STATUS (S8/AP4, E-S8-16, Mockup 03).
 *
 * EINE SEITE, DIE MAN ANSIEHT UND DANN WEISS, OB ETWAS ZU TUN IST.
 *
 * Der Befund dahinter (B-S8-03, B-S8-12): Die Auskunft ueber den Betrieb lag
 * verstreut — der Serverschluessel meldete sich als rote Karte auf der Seite
 * der Backup-Ziele, die Schluesselableitung als rote Karte auf der
 * Wartungsseite, der Speicherstand als Balken unter den Backups, die
 * Job-Fehler als Plakette in einer Liste. Jede fuer sich richtig; zusammen
 * ergaben sie kein Bild. Wer wissen wollte, ob diese Installation in Ordnung
 * ist, musste sechs Seiten aufrufen und auf jeder wissen, worauf zu achten
 * ist.
 *
 * DIESE DATEI ZEICHNET NUR NOCH. Was gemessen und wie es bewertet wird, steht
 * seit Web 15.4.0 in `status_lib.php` — weil der Menuezaehler dieselbe
 * Antwort braucht und ein Zaehler, der seine eigene Rechnung anstellt,
 * frueher oder spaeter etwas anderes sagt als die Seite, auf die er fuehrt.
 * Die Ampeltabelle und die Begruendung dazu stehen dort.
 *
 * REIN LESEND. Die Seite fasst zusammen und verweist; geaendert wird auf der
 * zustaendigen Seite. Auch der fehlende Serverschluessel ist deshalb ein Weg
 * und kein Knopf.
 *
 * WELCHE KARTE IN WELCHER SPALTE STEHT, entscheidet DIESE Datei: Das ist
 * Anordnung, keine Auskunft. Links Server und E-Mail, rechts Hintergrundjobs
 * und Backups.
 */

/* Der Baustein dieser Seite: eine Zeile mit Ampel. Sie ist `ui_zeile()` mit
 * einer Plakette und einem Link — kein neuer Baustein. */
function status_zeile(array $z): void
{
    ui_zeile([
        'text'      => $z['text'],
        'klein'     => $z['klein'],
        'href'      => $z['href'],
        'plaketten' => ui_plakette($z['plakette'], ['ton' => $z['ton']]),
    ]);
}

$karten = status_karten();
$nach   = static function (string $id) use ($karten): array {
    foreach ($karten as $k) { if ($k['id'] === $id) { return $k; } }
    return ['titel' => '', 'id' => $id, 'zeilen' => []];
};

/* Die frisch erhobene Ampel geht in den Zwischenspeicher des Menuezaehlers —
 * wer hier steht, hat gerade die vollstaendige Erhebung bezahlt, und der
 * Zaehler auf den naechsten Seiten kann sie mitbenutzen. */
$z = status_zaehlen($karten);
status_ampel_merken($z);

ui_seite_start(['titel' => 'Status']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_status']); ?>

  <?php ui_titelzeile([
      'titel' => 'Status',
      'unter' => 'Was diese Installation gerade meldet — rein lesend. '
               . 'Geändert wird auf der Seite, auf die die Zeile führt.',
      'aktionen' => ui_knopf(['text' => 'Aktualisieren', 'symbol' => 'sortieren',
                              'art' => 'neutral', 'href' => 'betrieb_status.php']),
  ]); ?>

  <?= wartung_balken() ?>

  <?php
  /* DIE MELDUNG OBEN ZAEHLT, WAS DIE KARTEN GEFUNDEN HABEN. Bis Web 15.3.3
     entstand sie zuletzt und wurde ueber einen Puffer nach vorn geschoben —
     die Zeilen kannten ihren Ton erst beim Zeichnen. Seit die Erhebung in
     `status_lib.php` steht, liegt die Zahl vor dem ersten Zeichen fest; der
     Puffer ist damit fort. */
  if ($z['rot'] > 0 || $z['orange'] > 0) {
      $teile = [];
      if ($z['rot'] > 0)    { $teile[] = $z['rot'] . ($z['rot'] === 1 ? ' Punkt arbeitet nicht' : ' Punkte arbeiten nicht'); }
      if ($z['orange'] > 0) { $teile[] = $z['orange'] . ($z['orange'] === 1 ? ' Punkt braucht Aufmerksamkeit' : ' Punkte brauchen Aufmerksamkeit'); }
      echo ui_meldung_markup($z['rot'] > 0 ? 'fehler' : 'warn',
          implode(' · ', $teile) . '. Sie stehen unten rot beziehungsweise orange; '
          . 'jede Zeile führt dorthin, wo sich etwas ändern lässt.');
  } else {
      echo ui_meldung_markup('info',
          'Wartungsmodus aus, keine ausstehende Migration, Jobs laufen, Backups '
          . 'aktuell, Speicher unter der Warnschwelle.', 'Alles läuft.');
  }
  ?>

  <div class="form-raster">
  <div class="form-spalte">
    <?php foreach (['k-server', 'k-mail'] as $id): $k = $nach($id); ?>
      <?php ui_karte_start(['titel' => $k['titel'], 'id' => $k['id']]); ?>
        <?php foreach ($k['zeilen'] as $zeile) { status_zeile($zeile); } ?>
      <?php ui_karte_ende(); ?>
    <?php endforeach; ?>
  </div><?php /* .form-spalte (links) */ ?>

  <div class="form-spalte">
    <?php foreach (['k-jobs', 'k-backups'] as $id): $k = $nach($id); ?>
      <?php ui_karte_start(['titel' => $k['titel'], 'id' => $k['id']]); ?>
        <?php foreach ($k['zeilen'] as $zeile) { status_zeile($zeile); } ?>
      <?php ui_karte_ende(); ?>
    <?php endforeach; ?>
  </div><?php /* .form-spalte (rechts) */ ?>
  </div><?php /* .form-raster */ ?>

  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt',
                        'vorschau' => 'Ampel · lesend · eine Ausnahme']); ?>
    <p class="feld-hinweis"><strong>Die Ampel hat vier Töne, und sie bedeuten
       auf dieser Seite überall dasselbe.</strong> <em>Blau</em>: es ist in
       Ordnung. <em>Orange</em>: es braucht Aufmerksamkeit, arbeitet aber.
       <em>Rot</em>: es arbeitet nicht — oder es geht dabei etwas verloren.
       <em>Neutral</em>: nicht eingerichtet, oder eine reine Zahl ohne
       Wertung.</p>
    <p class="feld-hinweis"><strong>Die Seite ändert nichts.</strong> Jede
       Zeile führt auf die Seite, die zuständig ist. Die einzige Ausnahme ist
       der fehlende Serverschlüssel — dort ist der Weg ein Knopf, und von der
       Seite, die das Problem meldet, auf eine andere zu schicken, wo derselbe
       Knopf steht, wäre ein Umweg ohne Zweck.</p>
    <p class="feld-hinweis"><strong>Die Zahlen sind nicht alle gleich alt.</strong>
       Wartungsmodus, Migrationen, Jobs, Konto-Backups und die Ablage werden
       bei jedem Aufruf gelesen. Die Größe von Datenbank und Dateien kommt aus
       der täglichen Messung im Aufräumjob — die Zeile „Datenbank" sagt, wann
       sie entstanden ist. Ein Zwischenspeicher über das Ganze gibt es
       bewusst nicht: Eine Statusseite, die einen Zustand zeigt, den es nicht
       mehr gibt, ist schlechter als keine.</p>
  <?php ui_karte_ende(true); ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
