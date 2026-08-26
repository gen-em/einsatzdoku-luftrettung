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

ui_seite_start(['titel' => 'Demo-Konto']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin_demo']); ?>
  <h1>Demo-Konto</h1>

  <?php ui_meldung($notice, $error, 'info', '  '); ?>

  <p class="muted">Ein Konto zum Ausprobieren: erfundene Daten, öffentliche
     Zugangsdaten, Änderungen ausdrücklich erwünscht. Es setzt sich
     <strong>alle 30 Minuten</strong> selbst auf den Standardzustand zurück —
     ausgelöst von der nächsten Anfrage, nicht von einem Zeitdienst.</p>

  <p class="alert alert-warn">In diesem Konto liegt das Schlüsselmaterial
     <strong>auf dem Server</strong>. Das ist die einzige Stelle im Projekt,
     an der die Ende-zu-Ende-Verschlüsselung bewusst ausgesetzt ist, und sie
     ist nur vertretbar, weil dort ausschließlich erfundene Daten stehen.
     <strong>Niemals echte Daten in diesem Konto erfassen.</strong></p>

  <?php if (!$fixtureDa): ?>
    <p class="alert">Es liegt keine Fixture unter
       <code>server/demo/fixture.json.gz</code>. Ohne sie lässt sich weder
       anlegen noch zurücksetzen. Sie entsteht mit
       <code>php tools/referenzdatensatz/fixture/erzeugen.php</code> aus dem
       Referenzkonto.</p>
  <?php endif; ?>

  <h2>Zustand</h2>
  <?php if ($demoId === null): ?>
    <p>Es gibt derzeit <strong>kein</strong> Demo-Konto.</p>
  <?php else: ?>
    <?php /* table.data statt einer eigenen Klasse: Der Adminbereich zeigt
             Auskuenfte ueberall so, und eine neue Klasse fuer eine Handvoll
             Zeilen waere ein Sonderfall, den spaeter niemand pflegt. */ ?>
    <table class="data">
      <tbody>
        <tr><th>Konto</th><td><?= e((string)$email) ?></td></tr>
        <tr><th>Letzter Reset</th>
            <td><?= $letzter > 0 ? e(fmt_local(gmdate('Y-m-d H:i:s', $letzter))) : 'unbekannt' ?></td></tr>
        <tr><th>Nächster Reset</th>
            <td><?= $restSek > 0 ? 'in ' . (int)ceil($restSek / 60) . ' Minuten'
                                 : 'bei der nächsten Anfrage' ?></td></tr>
        <?php foreach (($zahlen ?? []) as $k => $v): ?>
          <tr><th><?= e((string)$k) ?></th><td><?= (int)$v ?></td></tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  <?php endif; ?>

  <?php if ($bericht !== null): ?>
    <h2>Bericht des letzten Laufs</h2>
    <pre class="mono"><?= e(json_encode($bericht,
        JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES)) ?></pre>
  <?php endif; ?>

  <h2>Handlungen</h2>
  <div class="rowactions">
    <?php if ($demoId === null): ?>
      <form method="post" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_anlegen">
        <button class="btn-primary" <?= $fixtureDa ? '' : 'disabled' ?>>Demo-Konto anlegen</button>
      </form>
    <?php else: ?>
      <?php /* KEINE Rueckfrage: Der Verlust ist der Zweck, und dasselbe
               passiert alle 30 Minuten von selbst. Eine Rueckfrage, die man
               dreissigmal am Tag wegklickt, entwertet die Rueckfragen, die
               etwas bedeuten. */ ?>
      <form method="post" class="inline-form">
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_reset">
        <button class="btn-primary" <?= $fixtureDa ? '' : 'disabled' ?>>Auf Standard zurücksetzen</button>
      </form>
      <form method="post" class="inline-form"
            data-confirm="Demo-Konto samt allen Daten endgültig entfernen?"
            data-confirm-ok="Entfernen">
        <?= csrf_field() ?><input type="hidden" name="action" value="demo_entfernen">
        <input type="hidden" name="confirm" value="ja">
        <button class="btn-red">Demo-Konto entfernen</button>
      </form>
    <?php endif; ?>
  </div>

  <h2>Was der Reset umfasst</h2>
  <ul class="muted">
    <li>Diensttage, Einsätze, Ruhesegmente, Spuren, Stammdaten — vollständig
        ersetzt.</li>
    <li>Geräte, Kopplungscodes, Papierkorb und Sperrliste — auch das, was
        Besucher angelegt haben.</li>
    <li>Konto- und Schlüsselmaterial: E-Mail, Passwort, Salz und beide
        Schlüsselhüllen werden aus der Fixture überschrieben. Selbst eine
        unerwartet gelungene Änderung der Konto-Identität bliebe damit
        folgenlos.</li>
    <li>Der Papierkorb kommt <strong>aus der Fixture</strong> zurück — als
        Papierkorb, mit frischer <?= TRASH_DAYS ?>-Tage-Frist. Der Reset ist
        damit <em>ein</em> Vorgang in <em>einer</em> Transaktion: Entweder er
        gelingt ganz, oder er ändert nichts.</li>
  </ul>
<?php ui_geruest_ende(); ?>
