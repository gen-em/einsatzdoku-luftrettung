<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/status_lib.php';
/* Der Ratenschutz fuer die Testmail (Backlog Nr. 120). `status_lib.php` zieht
 * `smtp.php` nach, aber `ratelimit_lib.php` zieht niemand — ohne diese Zeile
 * gibt es einen Fatal Error beim ersten Klick, und zwar erst dann. */
require_once __DIR__ . '/ratelimit_lib.php';

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
 * SIE AENDERT NICHTS AM BESTAND. Die Seite fasst zusammen und verweist;
 * geaendert wird auf der zustaendigen Seite.
 *
 * ZWEI AUSNAHMEN, und beide fuehren nicht weg, sondern pruefen an Ort und
 * Stelle:
 *   - Der fehlende Serverschluessel. Ihn dort zu erzeugen, wo das Problem
 *     gemeldet wird, ist kuerzer als ein Umweg auf eine Seite mit demselben
 *     Knopf.
 *   - „Testmail an mich" (Backlog Nr. 120, Web 19.3.0, freigegeben am
 *     12.09.2026). Sie aendert keinen Bestand, sie prueft — und es GIBT
 *     keine zustaendige Seite, auf die man verweisen koennte: SMTP steht
 *     allein in der `config.php` und hat keine Oberflaeche. Bis dahin
 *     stand hier „genau eine Ausnahme"; wer eine zweite hinzufuegt, ohne
 *     den Satz mitzuschreiben, hinterlaesst eine Beschreibung, die die
 *     Seite nicht mehr trifft.
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

/* DIE TESTMAIL (Backlog Nr. 120).
 *
 * DIE EINZIGE AUSNAHME VON „REIN LESEND" IST JETZT DIE ZWEITE. Freigegeben
 * am 12.09.2026: Beide Ausnahmen fuehren nicht weg, sondern pruefen an Ort
 * und Stelle. Fuer SMTP gibt es ausserdem gar keine Seite, auf die man
 * verweisen koennte — es steht nur in der `config.php` und hat keine
 * Oberflaeche. Der Satz in der Karte „Was hier gilt", der Kopf dieser Datei,
 * die Unterzeile und das Handbuch sind mitgeschrieben; eine Zusage, die die
 * Seite nicht mehr beschreibt, waere schlimmer als keine.
 *
 * DER ZWEIG STEHT VOR `status_karten()`. Sonst zeigt die Zeile „Letzter
 * Versand" den Stand von VOR dem Versand: `smtp_send()` vermerkt ihn selbst
 * (`smtp_versand_vermerken()`), und die Erhebung liest ihn.
 *
 * DREI DINGE SIND NICHT VERHANDELBAR:
 *
 * 1. ERST `smtp_eingerichtet()`, DANN ERST VERSUCHEN. `smtp_send()` prueft
 *    das NICHT selbst: Es baut die Verbindung auf, scheitert und vermerkt
 *    einen Fehlschlag. Auf einer Installation ohne Mailserver machte ein
 *    Klick damit aus „nicht eingerichtet" (neutral, keine Aufforderung) ein
 *    „fehlgeschlagen" (rot, zaehlt in der Meldung oben mit) — eine
 *    Statusseite, die ein Problem behauptet, das es nicht gibt. Vorbild:
 *    `adminbackup_lib.php`, das vor dem Erinnerungsversand ebenso prueft.
 * 2. EIN KURZES ZEITLIMIT. Der Versand laeuft synchron, weil sein Ergebnis
 *    gezeigt werden soll. `smtp_send()` nimmt das Limit als vierten Wert
 *    (Vorgabe 15 s, und KEINER der acht Aufrufer im Bestand setzt ihn); bei
 *    15 s koennte ein haengender Mailserver die Seite ueber zwei Minuten
 *    halten, weil jeder Protokollschritt sein eigenes Limit hat. 5 s.
 * 3. KEIN `antwort_abschliessen()`. Die uebrigen Verwender entkoppeln den
 *    Versand oder verwerfen sein Ergebnis — hier ist das Ergebnis der Zweck.
 *
 * NICHT PROTOKOLLIERT WIRD DIE EMPFAENGERADRESSE: `smtp.php` fuehrt
 * ausdruecklich kein Protokoll ueber Mailempfaenger, und diese Zusage bleibt.
 * Sie steht nur in der Meldung dieser einen Antwort. */
$mailMeldung = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'testmail') {
    csrf_check();
    if (!smtp_eingerichtet()) {
        $mailMeldung = ['warn', 'Es ist kein SMTP eingerichtet — es wurde nichts '
                              . 'versucht. Der Zugang gehört in die config.php.'];
    } elseif (!rate_erlaubt('testmail', (string)$userId)) {
        $bis = rate_gesperrt_bis('testmail', (string)$userId);
        $mailMeldung = ['warn', 'Zu viele Testmails in kurzer Zeit.'
            . ($bis !== null ? ' Bis ' . fmt_local($bis, 'H:i') . ' Uhr geht keine mehr hinaus.'
                             : ' Bitte später erneut versuchen.')];
    } else {
        rate_zaehlen('testmail', (string)$userId);
        $ok = smtp_send((string)$userEmail,
            'Testmail — Einsatzdokumentation Notarzt',
            "Diese Nachricht wurde auf der Seite Betrieb → Status ausgelöst.\n"
          . "Kommt sie an, funktioniert der Versand dieser Installation.\n",
            5);
        $mailMeldung = $ok
            ? ['ok', 'Die Testmail ist an ' . (string)$userEmail . ' hinausgegangen. '
                   . 'Ob sie ankommt, sagt erst das Postfach — der Server hat sie '
                   . 'angenommen.']
            : ['fehler', 'Der Versand ist gescheitert. Die Zeile „Letzter Versand" '
                       . 'steht jetzt rot; die Ursache steht im Fehlerprotokoll des '
                       . 'Webspace.'];
    }
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
      'unter' => 'Was diese Installation gerade meldet. Geändert wird auf der '
               . 'Seite, auf die die Zeile führt — hier wird nur geprüft.',
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

  <?php /* DAS FORMULAR STEHT EINMAL IM MARKUP, der Knopf im Kartenkopf zeigt
           per `form=` darauf — ein <form> laesst sich dort nicht unterbringen,
           und zwei ineinander erst recht nicht. Dasselbe Muster wie
           „Alle sichern" in admin_sicherungen.php. */ ?>
  <form method="post" action="betrieb_status.php" id="f-testmail" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="testmail">
  </form>

  <div class="form-raster">
  <div class="form-spalte">
    <?php foreach (['k-server', 'k-mail'] as $id): $k = $nach($id); ?>
      <?php /* DIE KOPFAKTION HAENGT AN DER KARTE, NICHT AN EINER ZEILE
               (Backlog Nr. 120, freigegeben 12.09.2026). Sie gehoert der
               Karte E-Mail als Ganzes: Geprueft wird der Versand, und den
               beschreiben beide Zeilen zusammen. Damit bleibt `status_lib.php`
               unberuehrt — die Datei sagt in ihrem Kopf, dass sie misst und
               nichts ueber Darstellung entscheidet, und DIESE Datei
               entscheidet ueber Anordnung.

               Art `blau`: Die Kopfaktion kennt nur `blau` und `orange`
               (`.karte-aktion-blau` / `-orange` im Stylesheet). `neutral`
               gaebe eine Klasse ohne Regel — einen ungestalteten Knopf ohne
               jede Fehlermeldung. Orange waere eine Haupthandlung; die hat
               diese Seite nicht.

               Symbol `mail`: das 53. Zeichen des Vorrats, Tabler „mail",
               neu aufgenommen fuer diesen Knopf. Alle elf uebrigen
               Kopfaktionen tragen eines; eine textnackte waere die erste
               gewesen und damit eine neue Darstellung. */ ?>
      <?php ui_karte_start(['titel' => $k['titel'], 'id' => $k['id']]
          + ($id === 'k-mail'
              ? ['aktion' => ['text' => 'Testmail an mich', 'symbol' => 'mail',
                              'art' => 'blau', 'form' => 'f-testmail']]
              : [])); ?>
        <?php /* DIE ANTWORT STEHT IN DER KARTE, IN DER GEKLICKT WURDE.
                 Oben auf der Seite haette sie neben der Zusammenfassung
                 („5 Punkte brauchen Aufmerksamkeit") gestanden — zwei
                 Meldungen uebereinander, von denen die obere nichts mit dem
                 Klick zu tun hat. Dasselbe Muster wie auf
                 `betrieb_jobs.php`. */ ?>
        <?php if ($id === 'k-mail' && $mailMeldung): ?>
          <?= ui_meldung_markup($mailMeldung[0], $mailMeldung[1]) ?>
        <?php endif; ?>
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
                        'vorschau' => 'Ampel · prüfen · zwei Ausnahmen']); ?>
    <p class="feld-hinweis"><strong>Die Ampel hat vier Töne, und sie bedeuten
       auf dieser Seite überall dasselbe.</strong> <em>Blau</em>: es ist in
       Ordnung. <em>Orange</em>: es braucht Aufmerksamkeit, arbeitet aber.
       <em>Rot</em>: es arbeitet nicht — oder es geht dabei etwas verloren.
       <em>Neutral</em>: nicht eingerichtet, oder eine reine Zahl ohne
       Wertung.</p>
    <p class="feld-hinweis"><strong>Die Seite ändert nichts am Bestand.</strong>
       Jede Zeile führt auf die Seite, die zuständig ist. <strong>Zwei
       Ausnahmen</strong> führen nicht weg, sondern prüfen an Ort und Stelle:
       der <em>fehlende Serverschlüssel</em> — von der Seite, die das Problem
       meldet, auf eine andere zu schicken, wo derselbe Knopf steht, wäre ein
       Umweg ohne Zweck — und die <em>Testmail</em> im Kopf der Karte
       „E-Mail". Für SMTP gibt es gar keine zuständige Seite: Der Zugang steht
       allein in der <code>config.php</code>. Die Testmail geht an die eigene
       Adresse, höchstens dreimal je Stunde, und danach sagt die Zeile
       „Letzter Versand", ob sie hinausgegangen ist.</p>
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
