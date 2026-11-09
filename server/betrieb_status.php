<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_betreiberin();
require_once __DIR__ . '/status_lib.php';
/* Der Ratenschutz fuer die Testmail. `status_lib.php` zieht
 * `smtp.php` nach, aber `ratelimit_lib.php` zieht niemand — ohne diese Zeile
 * gibt es einen Fatal Error beim ersten Klick, und zwar erst dann. */
require_once __DIR__ . '/ratelimit_lib.php';
require_once __DIR__ . '/mail_lib.php';

/**
 * BETRIEB -> STATUS.
 *
 * EINE SEITE, DIE MAN ANSIEHT UND DANN WEISS, OB ETWAS ZU TUN IST.
 *
 * Der Befund dahinter: Die Auskunft ueber den Betrieb lag
 * verstreut — der Serverschluessel meldete sich als rote Karte auf der Seite
 * der Backup-Ziele, die Schluesselableitung als rote Karte auf der
 * Wartungsseite, der Speicherstand als Balken unter den Backups, die
 * Job-Fehler als Plakette in einer Liste. Jede fuer sich richtig; zusammen
 * ergaben sie kein Bild. Wer wissen wollte, ob diese Installation in Ordnung
 * ist, musste sechs Seiten aufrufen und auf jeder wissen, worauf zu achten
 * ist.
 *
 * DIESE DATEI ZEICHNET NUR. Was gemessen und wie es bewertet wird, steht
 * in `status_lib.php` — weil der Menuezaehler dieselbe
 * Antwort braucht und ein Zaehler, der seine eigene Rechnung anstellt,
 * frueher oder spaeter etwas anderes sagt als die Seite, auf die er fuehrt.
 * Die Ampeltabelle und die Begruendung dazu stehen dort.
 *
 * SIE AENDERT NICHTS AM BESTAND. Die Seite fasst zusammen und verweist;
 * geaendert wird auf der zustaendigen Seite.
 *
 * EINE AUSNAHME fuehrt nicht weg, sondern prueft an Ort und Stelle:
 * „Testmail an mich" (freigegeben). Sie aendert keinen Bestand, sie prueft —
 * und es GIBT keine
 * zustaendige Seite, auf die man verweisen koennte: SMTP steht allein in der
 * `config.php` und hat keine Oberflaeche.
 * Der fehlende Serverschluessel ist KEINE zweite Ausnahme: Die Zeile
 * verweist auf die Karte „Schluessel des Servers"
 * (`betrieb_server.php#k-schluessel`, status_lib.php), erzeugt wird hier
 * nichts. Wer eine Ausnahme hinzufuegt oder wegnimmt, schreibt diesen Satz
 * mit; sonst beschreibt er eine Seite, die es nicht gibt.
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

/* DIE TESTMAIL.
 *
 * DIE TESTMAIL IST DIE AUSNAHME VON „REIN LESEND". Freigegeben am
 * 12.09.2026: Sie fuehrt nicht weg, sondern prueft an Ort und Stelle. Fuer
 * SMTP gibt es ausserdem gar keine Seite, auf die man verweisen koennte — es
 * steht nur in der `config.php` und hat keine Oberflaeche. Der Kopf dieser
 * Datei und das Handbuch (12.1) sagen es; eine Zusage, die die Seite nicht
 * mehr beschreibt, waere schlimmer als keine.
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
 *    gezeigt werden soll — `mail_einreihen()` versucht die Zeile sofort,
 *    mit `MAIL_BUDGET_S` = 5 s.
 *
 *    Bei 15 s je Protokollschritt koennte ein haengender Mailserver die
 *    Seite ueber zwei Minuten halten, weil jeder Schritt sein eigenes Limit
 *    hat. Das gehoert nicht hier behoben, sondern fuer alle zehn
 *    Aufrufstellen — darunter der Aufraeumjob, der HUCKEPACK auf der
 *    Anfrage einer Unbeteiligten laeuft.
 *
 *    `smtp_send()` rechnet mit einer FRIST statt einer Dauer.
 *    Gemessen gegen ein Relais mit 26 s Lesezeit: 5 s Limit
 *    ergeben 5,00 s statt 31,06 s mit einer Dauer; 15 s ergeben 15,00 s
 *    statt 41,07 s. Und das Limit steht NICHT HIER, sondern einmal in
 *    `mail_lib.php` — nicht verteilt auf zehn Aufrufstellen.
 * 3. KEIN `antwort_abschliessen()`. Die uebrigen Verwender entkoppeln den
 *    Versand oder verwerfen sein Ergebnis — hier ist das Ergebnis der Zweck.
 *
 * DREI AUSGAENGE STATT ZWEI, seit die Warteschlange dazwischensteht: Geht sie
 * sofort hinaus, ist es „hinausgegangen"; scheitert der erste Versuch, ist es
 * NICHT mehr endgueltig gescheitert, sondern eingereiht — der Mail-Job holt
 * sie nach, und zwar innerhalb der Stundenfrist des Katalogeintrags. Das ist
 * keine Beschoenigung, sondern der Zustand: Wer „gescheitert" liest und eine
 * Stunde spaeter doch eine Testmail im Postfach findet, misstraut der Seite.
 *
 * NICHT PROTOKOLLIERT WIRD DIE EMPFAENGERADRESSE: `smtp.php` fuehrt
 * ausdruecklich kein Protokoll ueber Mailempfaenger, und diese Zusage bleibt
 * — auch die Fehlermeldung haelt sie ein; sie nennt nicht
 * „Versand an <Adresse> fehlgeschlagen". Die Adresse steht in der
 * Warteschlange, solange die Zeile offen oder unzustellbar ist; das ist der
 * Ort dafuer, nicht das Protokoll (Reiter System).
 *
 * UMLEITEN NACH DEM POST: Ein Versand endet mit
 * `flash_setzen()` und einer Umleitung in die Karte „E-Mail" — sonst
 * verschickt Neuladen eine zweite Testmail und zaehlt sie mit. Das gilt fuer
 * alle drei Ausgaenge, weil der Zaehler schon davor steht. „Kein SMTP" und
 * „Zu viele" aendern nichts (`rate_erlaubt()` liest nur) und bleiben stehen. */
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
        $mailMeldung = match (mail_einreihen('testmail', (string)$userEmail)) {
            MAIL_ZUGESTELLT => ['ok', 'Die Testmail ist an ' . (string)$userEmail
                   . ' hinausgegangen. Ob sie ankommt, sagt erst das Postfach — '
                   . 'der Server hat sie angenommen.'],
            MAIL_WARTET     => ['warn', 'Der erste Versuch ist gescheitert. Die '
                   . 'Nachricht steht in der Warteschlange und wird innerhalb der '
                   . 'nächsten Stunde erneut versucht; die Zeile „Letzter Versand" '
                   . 'steht bis dahin rot. Der Grund steht in der Warteschlange.'],
            MAIL_UNZUSTELLBAR => ['fehler', 'Der Mailserver hat die Testmail beim '
                   . 'ersten Versuch endgültig abgelehnt; sie wird nicht erneut '
                   . 'versucht. Der Grund steht in der Warteschlange.'],
            default         => ['fehler', 'Die Testmail wurde nicht eingereiht. Das '
                   . 'passiert, wenn die eigene Adresse unbrauchbar ist oder die '
                   . 'Warteschlange nicht erreichbar war; die Ursache steht im '
                   . 'Protokoll unter System.'],
        };
        flash_setzen($mailMeldung[0], $mailMeldung[1], 'k-mail');
        header('Location: betrieb_status.php#k-mail');
        exit;
    }
}

// Meldung aus der Umleitung in die Karte, in der geklickt wurde
$flash = flash_holen();
if ($flash !== null && $flash['ort'] === 'k-mail') {
    $mailMeldung ??= [$flash['ton'], $flash['text']];
}

$erhebung = status_erhebung();
$karten   = $erhebung['karten'];
$nach   = static function (string $id) use ($karten): array {
    foreach ($karten as $k) { if ($k['id'] === $id) { return $k; } }
    return ['titel' => '', 'id' => $id, 'zeilen' => []];
};

$z = status_zaehlen($karten);
status_ampel_merken($z);

/* PROBLEME ZUERST (Konzept OF, OF-06, E-OF-20, Bild OF-P23-A). Oben stehen
 * genau die Zeilen, die nicht gruen sind — rot vor orange, je mit ihrem
 * Bereich als Vorsatz —, darunter jeder Bereich als Klappkarte mit Zahl und
 * Plakette. Bis Web 24.6 standen vier offene Karten mit rund siebzig Zeilen
 * da, und die sechs, die etwas brauchten, musste man suchen. */
$aufmerksam = [];
foreach (['rot', 'orange'] as $ton) {
    foreach ($karten as $k) {
        foreach ($k['zeilen'] as $zeile) {
            if ($zeile['ton'] === $ton) {
                $aufmerksam[] = ['klein' => $k['titel'] . ' · ' . $zeile['klein']] + $zeile;
            }
        }
    }
}

/** Plakette einer Bereichskarte: rot zaehlt vor orange, sonst „in Ordnung". */
$bereichPlakette = static function (array $k): string {
    $rot = 0; $orange = 0;
    foreach ($k['zeilen'] as $zeile) {
        if ($zeile['ton'] === 'rot')    { $rot++; }
        if ($zeile['ton'] === 'orange') { $orange++; }
    }
    if ($rot > 0)    { return ui_plakette($rot . ($rot === 1 ? ' Fehler' : ' Fehler'), ['ton' => 'rot']); }
    if ($orange > 0) { return ui_plakette($orange . ($orange === 1 ? ' Hinweis' : ' Hinweise'), ['ton' => 'orange']); }
    return ui_plakette('in Ordnung', ['ton' => 'gruen']);
};
$pruefungen = static fn(array $k): string
    => count($k['zeilen']) . (count($k['zeilen']) === 1 ? ' Prüfung' : ' Prüfungen');

ui_seite_start(['titel' => 'Status']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'betrieb_status']); ?>

  <?php ui_titelzeile([
      'titel' => 'Status',
      'unter' => 'Was diese Installation gerade meldet — geändert wird auf der '
               . 'Seite, auf die die Zeile führt.',
      /* ZWEI HANDLUNGEN NEBEN DEM TITEL, mit der Unterseite
         „Sicherheit". `ui_aktionen()` ist der Baustein fuer
         Handlungen DER SEITE; die Titelzeile reicht ihr Markup durch. Die
         Unterseite steht bewusst NICHT als achtzehnter Eintrag in der
         Leiste — sie haengt an Status, und hier ist der Weg
         dorthin. */
      /* OHNE SYMBOL, und das ist eine Entscheidung. Der Vorrat haette
         `schloss` — aber das Zeichen ist in dieser Anwendung schon zweifach
         belegt: Es traegt die Zusage „Ende-zu-Ende-verschluesselt"
         (`CLAUDE.md` 4) und auf dem Geraete-Reiter den Zustand „aktiv". Eine
         dritte Bedeutung waere genau die Verwechslungsgefahr, die neue
         Zeichen vermeiden sollen. Ein NEUES Zeichen braucht
         eine Freigabe; der Knopf braucht keins. */
      'aktionen' => ui_knopf(['text' => 'Sicherheit',
                              'art' => 'neutral', 'href' => 'betrieb_sicherheit.php'])
                  . ui_knopf(['text' => 'Aktualisieren', 'symbol' => 'sortieren',
                              'art' => 'neutral', 'href' => 'betrieb_status.php']),
  ]); ?>

  <?= wartung_balken() ?>

  <?php /* DAS FORMULAR STEHT EINMAL IM MARKUP, der Knopf in der Karte
           E-Mail zeigt per `form=` darauf. */ ?>
  <form method="post" action="betrieb_status.php" id="f-testmail" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="testmail">
  </form>

  <?php if ($aufmerksam === []): ?>
    <?= ui_meldung_markup('info',
        'Keine Prüfung meldet etwas; die Bereiche darunter zeigen, was gemessen wurde.',
        'Alles läuft.') ?>
  <?php else: ?>
    <?php ui_karte_start(['titel' => 'Braucht Aufmerksamkeit', 'id' => 'k-aufmerksam',
                          'zahl' => (string)count($aufmerksam)]); ?>
      <?php foreach ($aufmerksam as $zeile) { status_zeile($zeile); } ?>
    <?php ui_karte_ende(); ?>
  <?php endif; ?>

  <?php foreach (['k-server', 'k-konten', 'k-mail', 'k-jobs', 'k-backups'] as $id):
    $k = $nach($id);
    if ($k['zeilen'] === []) { continue; } ?>
    <?php /* DIE ANTWORT DER TESTMAIL STEHT IN DER KARTE, in der geklickt
             wurde; mit ihr ist die Karte offen. Die Kopfaktion „Testmail an
             mich" ist ein Knopf im Inhalt geworden — eine Klappkarte traegt
             keine Kopfaktion (Design.md 9.1). */ ?>
    <?php ui_karte_start(['titel' => $k['titel'], 'id' => $k['id'], 'zu' => true,
                          'stand' => $pruefungen($k), 'plakette' => $bereichPlakette($k),
                          'offen' => $id === 'k-mail' && $mailMeldung !== null]); ?>
      <?php if ($id === 'k-mail' && $mailMeldung): ?>
        <?= ui_meldung_markup($mailMeldung[0], $mailMeldung[1]) ?>
      <?php endif; ?>
      <?php foreach ($k['zeilen'] as $zeile) { status_zeile($zeile); } ?>
      <?php if ($id === 'k-mail'): ?>
        <div class="listen-form-fuss">
          <?= ui_knopf(['text' => 'Testmail an mich', 'symbol' => 'mail', 'art' => 'neutral',
                        'attr' => ' form="f-testmail"']) ?>
        </div>
      <?php endif; ?>
    <?php ui_karte_ende(true); ?>
  <?php endforeach; ?>

  <?php /* DIE PLATTFORM: dieselbe Liste, die `install.php` vor der
           Einrichtung prueft. Ein fehlendes Muss faerbt rot, ein knappes
           orange, eine Empfehlung gar nicht — ihre Zahl steht im Stand. */
        $kp = $nach('k-plattform');
        [$empfOk, $empfGesamt] = $erhebung['zahlen']['empfohlen']; ?>
  <?php ui_karte_start(['titel' => $kp['titel'], 'id' => $kp['id'], 'zu' => true,
                        'stand' => $pruefungen($kp) . ' · ' . $empfOk . '/' . $empfGesamt . ' empfohlen',
                        'plakette' => $bereichPlakette($kp)]); ?>
    <p class="feld-hinweis">Dieselbe Liste, die <code>install.php</code> vor der
       Einrichtung prüft; eine Empfehlung färbt die Ampel nicht.</p>
    <?php foreach ($kp['zeilen'] as $zeile) { status_zeile($zeile); } ?>
  <?php ui_karte_ende(true); ?>

  <?php /* DIE LESEKARTE DER config.php — zugeklappt, ohne Ampel; die Liste
           und ihre Begruendung stehen in `status_konfig_zeilen()`. */
        $konfig = status_konfig_zeilen(); ?>
  <?php ui_karte_start(['titel' => 'Konfiguration', 'id' => 'k-konfig', 'zu' => true,
                        'stand' => count($konfig) . ' Werte aus der config.php',
                        'plakette' => ui_plakette('nur lesend', ['ton' => 'neutral'])]); ?>
    <p class="feld-hinweis">Geändert wird die Datei per FTP; Passwörter und
       Schlüssel stehen nie im Klartext da.</p>
    <?php foreach ($konfig as $zeile) { status_zeile($zeile); } ?>
  <?php ui_karte_ende(true); ?>

  <?php /* KEINE KARTE „BETRIEBSPROTOKOLL" MEHR (E-OF-72): Die Zahlen stehen
           an den Reitern des Protokolls; dass Schreiben scheitert, meldet die
           Zeile „Betriebsprotokoll" im Bereich Server. */ ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
