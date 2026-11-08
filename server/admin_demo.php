<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/demo_lib.php';

/**
 * Demo-Konto — die Kontrollzahlen des Resets.
 *
 * SEIT WEB 24.6 NUR NOCH LESEND (Konzept OF, OF-05, E-OF-71). Anlegen,
 * Zuruecksetzen, Entfernen und der Schalter stehen in der Karte „Demo-Konto"
 * unter Installation (`admin_installation.php#k-demo`, E-OF-08, -46), der
 * Menuepunkt ist fort. Was hier bleibt, sind die Zahlen, die
 * `tools/referenzdatensatz/browser/demo_pruefen.mjs` liest; sie werden in
 * OF-06 Zeilen der Statusseite, und diese Seite leitet dann um.
 */

$demoId = demo_id();

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
    /* DREI PAPIERKORBZAHLEN, NICHT EINE. Der Reset meldet drei
     * (`stats.papierkorb`), und eine Ansicht, die nur eine davon zeigt,
     * laesst zwei Fehlerbilder unsichtbar.
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

$letzter   = demo_letzter_reset();
$geaendert = demo_geaendert_seit();
$restSek   = demo_reset_in();

/* ---- Anzeige --------------------------------------------------------------
 *
 * DIE ZAHLEN STEHEN OBEN. Was man vor dem Klicken wissen will, ist
 * „steht da ueberhaupt etwas?" — vier Kacheln beantworten das in einem Blick,
 * wo sonst sieben Tabellenzeilen zu lesen waeren. Die Papierkorbzahlen bleiben
 * Zeilen: Sie sind die Kontrollzahlen des Resets, nicht sein Ergebnis.
 *
 * DIE HANDLUNGEN STEHEN IN DER TITELZEILE, wie auf der Kontoseite: der
 * haeufige Fall als Knopf, der endgueltige im Aktionsmenue. Die Formulare
 * dazu liegen versteckt im Seitenkopf und werden ueber `form="…"` bedient —
 * dasselbe Verfahren wie dort.
 */
ui_seite_start(['titel' => 'Demo-Konto']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen', 'menue' => 'admin_installation']); ?>

  <?php ui_titelzeile(['titel' => 'Demo-Konto', 'unter' => 'Kontrollzahlen des Resets']); ?>

  <p class="seiten-erklaerung">Anlegen, zurücksetzen und abschalten:
     <a href="admin_installation.php#k-demo">Installation → Demo-Konto</a>.</p>

<?php if ($demoId === null): ?>

  <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand']); ?>
    <p class="feld-hinweis">Es gibt derzeit <strong>kein</strong> Demo-Konto.</p>
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

  <?php /* KEIN ZWEISPALTEN-RASTER. Drei Karten sind eine Spalte; zwei
           Spalten erst ab mehr als vier Karten. */ ?>
    <?php ui_karte_start(['titel' => 'Zustand', 'id' => 'k-zustand']); ?>
      <?php
      ui_zeile(['text' => 'Konto', 'klein' => (string)$email]);
      ui_zeile(['text' => 'Letzter Reset',
                'klein' => $letzter > 0 ? fmt_local(gmdate('Y-m-d H:i:s', $letzter)) : 'unbekannt']);
      /* ZWEI FRISTEN: 30 Minuten nach der
       * ersten Aenderung, ohne Aenderung ein Tag nach dem letzten Reset.
       * Welche gilt, sagt die Zeile „Geändert" darueber. */
      ui_zeile(['text' => 'Geändert',
                'klein' => $geaendert > 0
                    ? 'seit ' . fmt_local(gmdate('Y-m-d H:i:s', $geaendert))
                    : 'nein — kein Reset außer dem täglichen']);
      ui_zeile(['text' => 'Nächster Reset',
                'klein' => $restSek <= 0 ? 'bei der nächsten Anfrage'
                    : ($restSek < 3600 ? 'in ' . (int)ceil($restSek / 60) . ' Minuten'
                                       : 'ab ' . datum_zeit_text(gmdate('Y-m-d H:i:s', time() + $restSek)))]);
      ?>
    <?php ui_karte_ende(); ?>

    <?php /* DREI PAPIERKORBZAHLEN, NICHT EINE. Der Reset meldet drei
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
      <p class="feld-hinweis">Die drei Zahlen sind die Kontrolle des Resets —
         stehen sie auf null, hat das Einspielen etwas übersprungen.</p>
    <?php ui_karte_ende(); ?>

<?php endif; ?>

<?php ui_geruest_ende(); ?>
<?php ui_seite_ende(); ?>
