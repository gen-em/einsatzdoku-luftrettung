<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/demo_lib.php';
require_once __DIR__ . '/trash_lib.php';   // TRASH_DAYS fuer den Erklaertext

/**
 * Demo-Konto verwalten (Phase P1, E-P1-08).
 *
 * DREI HANDLUNGEN, EINE DAVON HARMLOS:
 *   Anlegen        legt das Konto an und spielt die Fixture ein
 *   Zuruecksetzen  verwirft alles und spielt die Fixture erneut ein
 *   Entfernen      loescht das Konto samt Kennzeichnung
 *
 * „Zuruecksetzen" sieht gefaehrlich aus und ist es nicht: Der Verlust ist der
 * Zweck. Was verlorengeht, sind Besuchereingaben in einem Konto mit rein
 * erfundenen Daten — und dasselbe passiert ohnehin alle 30 Minuten von
 * selbst. „Entfernen" dagegen ist endgueltig und hat deshalb eine Rueckfrage.
 *
 * WARUM EINE EIGENE SEITE. Die Nutzerverwaltung listet Konten; hier geht es
 * um EIN Konto mit besonderen Regeln, dessen Zustand (wann war der letzte
 * Reset? wie viele Einsaetze stehen gerade darin?) man sehen will, bevor man
 * etwas tut. Als Abschnitt zwischen dreissig Kontozeilen waere das ein
 * Fremdkoerper.
 */

$notice = null; $error = null; $bericht = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $aktion = (string)($_POST['action'] ?? '');
    try {
        if ($aktion === 'demo_anlegen') {
            $bericht = demo_anlegen();
            $notice = 'Demo-Konto angelegt und Standardzustand eingespielt.';
        } elseif ($aktion === 'demo_reset') {
            $bericht = demo_zuruecksetzen();
            $notice = 'Demo-Konto auf den Standardzustand zurückgesetzt.';
        } elseif ($aktion === 'demo_entfernen' && ($_POST['confirm'] ?? '') === 'ja') {
            demo_entfernen();
            $notice = 'Demo-Konto entfernt.';
        }
    } catch (Throwable $ex) {
        /* DREI FAELLE, UND NUR EINER DAVON DARF WOERTLICH DURCH.
         *
         * demo_lib.php wirft an mehreren Stellen RuntimeException mit einem
         * Satz, der FUER die Administration geschrieben ist („Es gibt bereits
         * ein Demo-Konto …"). Der ist die Auskunft und bleibt.
         *
         * Alles andere kam bis Web 8.0.0 genauso heraus — auch
         * `SQLSTATE[23000] … Duplicate entry 'manual-2' for key 'device_id'`.
         * Das ist keine Meldung, sondern ein Fund fuer jemanden, der den Code
         * kennt. Die technische Ursache gehoert ins Fehlerprotokoll, in die
         * Seite gehoert, WAS los ist und dass nichts geaendert wurde — alle
         * drei Handlungen laufen in einer Transaktion und rollen zurueck.
         *
         * Die Kennung verbindet beides: Sie steht in der Meldung und im
         * Protokoll (fehler_kennung(), db.php).
         *
         * DIE REIHENFOLGE DER ZWEIGE IST NICHT BELIEBIG: PDOException ERBT
         * von RuntimeException. Steht die Abfrage auf RuntimeException zuerst,
         * faengt sie jeden Datenbankfehler mit ab und gibt ihn im Wortlaut
         * aus — also genau das, was hier abgestellt werden soll. Die erste
         * Fassung dieser Aenderung hatte den Fehler; aufgefallen ist er im
         * Browserlauf, nicht beim Lesen. */
        if ($ex instanceof PDOException) {
            $kennung = fehler_kennung($ex, 'admin_demo');
            $error = ist_dublettenfehler($ex)
                ? 'Der Vorgang ist fehlgeschlagen, weil ein Wert aus der Fixture '
                  . 'auf diesem Server bereits vergeben ist — am ehesten eine '
                  . 'Gerätekennung, wenn hier auch der Bestand liegt, aus dem die '
                  . 'Fixture stammt. Es wurde nichts geändert. Einzelheiten stehen '
                  . 'im Fehlerprotokoll unter der Kennung ' . $kennung . '.'
                : 'Der Vorgang ist an der Datenbank gescheitert; es wurde nichts '
                  . 'geändert. Einzelheiten stehen im Fehlerprotokoll unter der '
                  . 'Kennung ' . $kennung . '.';
        } elseif ($ex instanceof RuntimeException) {
            $error = $ex->getMessage();
        } else {
            $kennung = fehler_kennung($ex, 'admin_demo');
            $error = 'Der Vorgang ist fehlgeschlagen; es wurde nichts geändert. '
                   . 'Einzelheiten stehen im Fehlerprotokoll unter der Kennung '
                   . $kennung . '.';
        }
    }
}

$demoId = demo_id();
$fixtureDa = demo_fixture_vorhanden();

/* Kennzahlen des Kontos — sie beantworten die Frage, die man VOR dem Klicken
 * hat: Steht da ueberhaupt etwas, und seit wann? */
$zahlen = null; $email = null;
if ($demoId !== null) {
    $st = db()->prepare('SELECT email FROM users WHERE id = ?');
    $st->execute([$demoId]);
    $email = (string)$st->fetchColumn();
    $eine = function (string $sql) use ($demoId): int {
        $st = db()->prepare($sql);
        $st->execute([$demoId]);
        return (int)$st->fetchColumn();
    };
    /* DREI PAPIERKORBZAHLEN, NICHT EINE. Bis Web 8.0.0 stand hier nur die
     * der Einsaetze — solange der Papierkorb aus einem Nachlauf-Drehbuch kam,
     * war das die einzige, die etwas aussagte. Seit er aus der Fixture kommt,
     * meldet der Reset drei (`stats.papierkorb`), und eine Ansicht, die nur
     * eine davon zeigt, laesst zwei Fehlerbilder unsichtbar.
     *
     * Der Schluessel „im Papierkorb" bleibt unveraendert bei den Einsaetzen:
     * `browser/demo_pruefen.mjs` liest ihn. */
    $zahlen = [
        'Diensttage'   => $eine('SELECT COUNT(*) FROM days WHERE user_id = ? AND deleted_at IS NULL'),
        'Einsätze'     => $eine('SELECT COUNT(*) FROM missions WHERE user_id = ? AND deleted_at IS NULL'),
        'Ruhesegmente' => $eine('SELECT COUNT(*) FROM rest_segments WHERE user_id = ? AND deleted_at IS NULL'),
        'im Papierkorb' => $eine('SELECT COUNT(*) FROM missions WHERE user_id = ? AND deleted_at IS NOT NULL'),
        'im Papierkorb, Diensttage'
                       => $eine('SELECT COUNT(*) FROM days WHERE user_id = ? AND deleted_at IS NOT NULL'),
        'im Papierkorb, Ruhesegmente'
                       => $eine('SELECT COUNT(*) FROM rest_segments WHERE user_id = ? AND deleted_at IS NOT NULL'),
        /* OHNE das virtuelle Geraet (GERAETE_ECHT_SQL) — dieselbe Zahl, die
         * die Geraeteliste zeigt und gegen die MAX_GERAETE zaehlt. Bis Web
         * 8.0.0 zaehlte diese Stelle als einzige mit; sie meldete drei, wo
         * NutzerIn und Grenze zwei sahen. */
        'Geräte'       => $eine('SELECT COUNT(*) FROM devices
                                 WHERE user_id = ? AND ' . GERAETE_ECHT_SQL),
    ];
}

$letzter = demo_letzter_reset();
$restSek = demo_reset_in();

/* ---- Anzeige --------------------------------------------------------------
 *
 * UMGEBAUT IN O9C (Web 9.10.0). Bis Web 9.9.0 war diese Seite eine Folge von
 * <h2>-Ueberschriften mit einer `table.data`, einem `pre.mono` und drei
 * `button.btn-primary` in einem `div.rowactions` — Klassen, die es im neuen
 * Stylesheet nicht mehr gibt, weil ihre Regeln in den Bausteinen aufgegangen
 * sind. Sie stand deshalb seit O2 ohne Gestaltung da.
 *
 * DIE ZAHLEN STEHEN JETZT OBEN. Was man vor dem Klicken wissen will, ist
 * „steht da ueberhaupt etwas?" — vier Kacheln beantworten das in einem Blick,
 * wo vorher sieben Tabellenzeilen zu lesen waren. Die Papierkorbzahlen bleiben
 * Zeilen: Sie sind die Kontrollzahlen des Resets, nicht sein Ergebnis.
 *
 * DIE HANDLUNGEN STEHEN IN DER TITELZEILE, wie auf der Kontoseite (O9a): der
 * haeufige Fall als Knopf, der endgueltige im Aktionsmenue. Die Formulare
 * dazu liegen versteckt im Seitenkopf und werden ueber `form="…"` bedient —
 * dasselbe Verfahren wie dort.
 */
ui_seite_start(['titel' => 'Demo-Konto']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin_demo']); ?>

  <?php /* Die Formulare der Titelzeile. Versteckt, weil ihr Knopf woanders
           steht; `data-confirm` haengt am Formular, nicht am Knopf — es gibt
           je Formular nur eine Handlung. */ ?>
  <form method="post" id="f-demo-anlegen" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="demo_anlegen">
  </form>
  <form method="post" id="f-demo-reset" hidden>
    <?= csrf_field() ?><input type="hidden" name="action" value="demo_reset">
  </form>
  <form method="post" id="f-demo-weg" hidden
        data-confirm="Demo-Konto samt allen Daten endgültig entfernen?"
        data-confirm-ok="Entfernen">
    <?= csrf_field() ?><input type="hidden" name="action" value="demo_entfernen">
    <input type="hidden" name="confirm" value="ja">
  </form>

  <?php
  /* KEINE RUECKFRAGE VOR DEM ZURUECKSETZEN: Der Verlust ist der Zweck, und
     dasselbe passiert alle 30 Minuten von selbst. Eine Rueckfrage, die man
     dreissigmal am Tag wegklickt, entwertet die Rueckfragen, die etwas
     bedeuten. „Entfernen" dagegen ist endgueltig und steht deshalb im
     Aktionsmenue, hinter einer Rueckfrage. */
  $gesperrt = $fixtureDa ? '' : ' disabled';
  if ($demoId === null) {
      $aktionen = ui_knopf(['text' => 'Demo-Konto anlegen', 'art' => 'primaer',
                            'symbol' => 'plus', 'attr' => ' form="f-demo-anlegen"' . $gesperrt]);
  } else {
      $aktionen = ui_knopf(['text' => 'Zurücksetzen', 'art' => 'primaer', 'symbol' => 'tausch',
                            'attr' => ' form="f-demo-reset"' . $gesperrt])
        . ui_aktionen(['titel' => 'Demo-Konto', 'eintraege' => [
              ['text' => 'Demo-Konto entfernen', 'symbol' => 'korb',
               'gefahr' => true, 'form' => 'f-demo-weg'],
          ]]);
  }
  ?>
  <?php ui_titelzeile(['titel' => 'Demo-Konto', 'aktionen' => $aktionen]); ?>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <?php /* DIE WARNUNG STEHT VOR ALLEM ANDEREN und bleibt stehen — sie ist der
           einzige Ort im Projekt, an dem die Ende-zu-Ende-Verschluesselung
           bewusst ausgesetzt ist. */ ?>
  <?= ui_meldung_markup('warn',
      'In diesem Konto liegt das Schlüsselmaterial auf dem Server. Das ist die '
    . 'einzige Stelle im Projekt, an der die Ende-zu-Ende-Verschlüsselung bewusst '
    . 'ausgesetzt ist, und sie ist nur vertretbar, weil dort ausschließlich '
    . 'erfundene Daten stehen. Niemals echte Daten in diesem Konto erfassen.',
      'Schlüsselmaterial auf dem Server') ?>

  <?php if (!$fixtureDa): ?>
    <?= ui_meldung_markup('fehler',
        'Es liegt keine Fixture unter server/demo/fixture.json.gz. Ohne sie lässt '
      . 'sich weder anlegen noch zurücksetzen. Sie entsteht mit '
      . '„php tools/referenzdatensatz/fixture/erzeugen.php" aus dem Referenzkonto.',
        'Keine Fixture') ?>
  <?php endif; ?>

  <p class="seiten-erklaerung">Ein Konto zum Ausprobieren: erfundene Daten,
     öffentliche Zugangsdaten, Änderungen ausdrücklich erwünscht. Es setzt sich
     <strong>alle 30 Minuten</strong> selbst auf den Standardzustand zurück —
     ausgelöst von der nächsten Anfrage, nicht von einem Zeitdienst.</p>

<?php if ($demoId === null): ?>

  <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand']); ?>
    <p class="feld-hinweis">Es gibt derzeit <strong>kein</strong> Demo-Konto.
       „Demo-Konto anlegen" legt es an und spielt die Fixture ein.</p>
  <?php ui_karte_ende(); ?>

<?php else: ?>

  <?php /* Die vier Zahlen des laufenden Bestands. Der Papierkorb steht nicht
           dabei: Er ist eine Kontrollzahl des Resets, keine Bestandszahl —
           und eine Kachel „5 im Papierkorb" neben „82 Einsätze" liest sich
           wie ein Problem, wo keines ist. */ ?>
  <div class="kennzahl-raster kennzahl-raster-4">
    <?= ui_kennzahl(['wert' => (string)$zahlen['Diensttage'],   'label' => 'Diensttage']) ?>
    <?= ui_kennzahl(['wert' => (string)$zahlen['Einsätze'],     'label' => 'Einsätze']) ?>
    <?= ui_kennzahl(['wert' => (string)$zahlen['Ruhesegmente'], 'label' => 'Ruhesegmente']) ?>
    <?= ui_kennzahl(['wert' => (string)$zahlen['Geräte'],       'label' => 'Geräte']) ?>
  </div>

  <?php /* KEIN ZWEISPALTEN-RASTER MEHR. Es stand hier mit einer einzigen
           Spalte darin — die rechte Haelfte blieb ab 1200 px leer, und mit
           dem Zusammenlegen der beiden Erklaerkarten waere sie es endgueltig
           geblieben. Drei Karten sind eine Spalte (Konzept AP5 (8): zwei
           Spalten erst ab mehr als vier Karten). */ ?>
    <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand']); ?>
      <?php
      ui_zeile(['text' => 'Konto', 'klein' => (string)$email]);
      ui_zeile(['text' => 'Letzter Reset',
                'klein' => $letzter > 0 ? fmt_local(gmdate('Y-m-d H:i:s', $letzter)) : 'unbekannt']);
      ui_zeile(['text' => 'Nächster Reset',
                'klein' => $restSek > 0 ? 'in ' . (int)ceil($restSek / 60) . ' Minuten'
                                        : 'bei der nächsten Anfrage']);
      ?>
    <?php ui_karte_ende(); ?>

    <?php /* DREI PAPIERKORBZAHLEN, NICHT EINE. Bis Web 8.0.0 stand hier nur
             die der Einsaetze — solange der Papierkorb aus einem
             Nachlauf-Drehbuch kam, war das die einzige, die etwas aussagte.
             Seit er aus der Fixture kommt, meldet der Reset drei
             (`stats.papierkorb`), und eine Ansicht, die nur eine davon zeigt,
             laesst zwei Fehlerbilder unsichtbar. */ ?>
    <?php ui_karte_start(['titel' => 'Papierkorb', 'id' => 'k-papierkorb']); ?>
      <?php
      /* DIE BESCHRIFTUNG TRAEGT DIE ART, DER WERT NUR DIE ZAHL. Bis Web
         9.9.0 hiessen die drei Zeilen „im Papierkorb", „im Papierkorb,
         Diensttage" und „im Papierkorb, Ruhesegmente" — Beschriftungen aus
         einer Tabelle, in der „im Papierkorb" ohne Zusatz die Einsaetze
         meinte. In einer Karte namens „Papierkorb" ist das doppelt gesagt
         und einmal zu ungenau. `demo_pruefen.mjs` liest diese Zeilen und
         ist mitgezogen. */
      ui_zeile(['text' => 'Einsätze im Papierkorb',     'klein' => (string)$zahlen['im Papierkorb']]);
      ui_zeile(['text' => 'Diensttage im Papierkorb',   'klein' => (string)$zahlen['im Papierkorb, Diensttage']]);
      ui_zeile(['text' => 'Ruhesegmente im Papierkorb', 'klein' => (string)$zahlen['im Papierkorb, Ruhesegmente']]);
      ?>
      <p class="feld-hinweis">Diese drei Zahlen sind die Kontrolle des Resets: Der
         Papierkorb kommt <strong>aus der Fixture</strong> zurück, nicht aus einem
         Nachlauf. Stehen sie auf null, ist beim Einspielen etwas übersprungen
         worden.</p>
    <?php ui_karte_ende(); ?>

  <?php /* EINE ERKLAERKARTE JE SEITE, ZUGEKLAPPT, AM ENDE (E-S8-01, Regel 5).
           Bis Web 15.3.3 standen hier ZWEI: „Was der Reset umfasst" und
           „Bericht des letzten Laufs" — dieselbe Frage („was tut der Reset
           und was hat er getan") in zwei Kaesten, und der Bericht erschien
           nur manchmal, so dass die Seite mal drei und mal vier Karten
           hatte. Jetzt eine Karte am Ende ueber die volle Breite, mit dem
           Bericht als letztem Abschnitt darin. */ ?>
  <?php ui_karte_start(['titel' => 'Was hier gilt', 'id' => 'k-gilt', 'zu' => true,
                        'vorschau' => 'Umfang des Resets'
                                    . ($bericht !== null ? ' · letzter Lauf' : '')]); ?>
      <?php /* `.text` ist der Lesetext-Baustein: nur darin haben ul/li
               Punkte und Einzug (Stylesheet, Abschnitt 13). Eine eigene
               Klasse fuer eine vierzeilige Liste waere ein Sonderfall. */ ?>
      <p class="feld-hinweis"><strong>Was der Reset umfasst.</strong></p>
      <div class="text">
      <ul>
        <li>Diensttage, Einsätze, Ruhesegmente, Spuren, Stammdaten — vollständig
            ersetzt.</li>
        <li>Geräte, offene Kopplungssitzungen, Papierkorb und Sperrliste — auch
            das, was Besucher angelegt haben.</li>
        <li>Konto- und Schlüsselmaterial: E-Mail, Passwort, Salz und beide
            Schlüsselhüllen werden aus der Fixture überschrieben. Selbst eine
            unerwartet gelungene Änderung der Konto-Identität bliebe damit
            folgenlos.</li>
        <li>Der Papierkorb kommt aus der Fixture zurück — als Papierkorb, mit
            frischer <?= TRASH_DAYS ?>-Tage-Frist. Der Reset ist damit
            <em>ein</em> Vorgang in <em>einer</em> Transaktion: Entweder er
            gelingt ganz, oder er ändert nichts.</li>
      </ul>
      </div>
      <?php if ($bericht !== null): ?>
        <p class="feld-hinweis"><strong>Bericht des letzten Laufs.</strong></p>
        <pre><?= e(json_encode($bericht,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
      <?php endif; ?>
  <?php ui_karte_ende(true); ?>

<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
