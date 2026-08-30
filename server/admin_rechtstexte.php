<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_admin();
require_once __DIR__ . '/rechtstexte_lib.php';

/**
 * Rechtstexte pflegen — Impressum und Datenschutzerklärung (R32, P3/O10).
 *
 * DIE ANWENDUNG LIEFERT KEINEN TEXT MIT. Was hier steht, ist Sache des
 * Betreibers; wir stellen das Feld, die Vorschau und die beiden öffentlichen
 * Seiten. Deshalb gibt es auch keine Vorlage und keinen Vorgabeinhalt — eine
 * mitgelieferte Datenschutzerklärung wäre eine Rechtsauskunft, die dieses
 * Projekt nicht geben kann.
 *
 * EIN FORMULAR FÜR BEIDE TEXTE, ein Speichern. Mockup 35 zeigt zwei Karten
 * nebeneinander und unten EINE Speichern-Leiste; das ist auch inhaltlich
 * richtig, weil man die beiden Texte in einem Zug pflegt.
 *
 * DIE VORSCHAU KOMMT VOM SERVER, nicht aus JavaScript. Ein zweiter Renderer
 * im Browser wäre genau die Stelle, an der die Regeln auseinanderlaufen: Er
 * müsste dieselbe Positivliste für Linkziele, dieselbe Maskierreihenfolge und
 * dieselben Zeichenfilter führen, und beim nächsten Fund würde einer von
 * beiden vergessen. Sie zeigt also den zuletzt GESPEICHERTEN Stand — das
 * Konzept lässt das ausdrücklich zu (E-P3-38).
 */

$notice = null; $error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    /* ERST ALLES PRÜFEN, DANN ALLES SPEICHERN. Sonst stünde nach einem
     * abgelehnten zweiten Text der erste schon in der Datenbank, und die
     * Meldung „nicht gespeichert" wäre zur Hälfte falsch. */
    $eingaben = [];
    foreach (RT_TEXTE as $k => $name) {
        $eingaben[$k] = [
            'text'  => (string)($_POST['text_' . $k] ?? ''),
            'stand' => trim((string)($_POST['stand_' . $k] ?? '')),
        ];
        $mangel = rt_pruefen($eingaben[$k]['text'], $eingaben[$k]['stand']);
        if ($mangel !== null) {
            $error = $name . ': ' . $mangel;
            break;
        }
    }

    if ($error === null) {
        $geaendert = [];
        foreach (RT_TEXTE as $k => $name) {
            $vorher = rt_lesen($k);
            /* Zeilenenden vergleichbar machen: Der Browser schickt CRLF aus
             * einem <textarea>, gespeichert wurde LF. Ohne das meldete jedes
             * Speichern eine Änderung. */
            $neu = str_replace(["\r\n", "\r"], "\n", $eingaben[$k]['text']);
            $standNeu = $eingaben[$k]['stand'] === '' ? null : $eingaben[$k]['stand'];
            if ($neu !== $vorher['inhalt'] || $standNeu !== $vorher['stand']) {
                rt_speichern($k, $neu, $standNeu);
                $geaendert[] = $name;
            }
        }
        $notice = $geaendert
            ? implode(' und ', $geaendert) . ' gespeichert.'
            : 'Es gab nichts zu ändern.';
    }
}

$texte = rt_alle();

ui_seite_start(['titel' => 'Rechtstexte']);
?>

<?php ui_geruest_start(['aktiv' => 'einstellungen', 'leiste' => 'einstellungen',
                        'menue' => 'admin_rechtstexte']); ?>

  <?php ui_titelzeile(['titel' => 'Rechtstexte']); ?>

  <?php ui_meldung($notice, $error, 'ok', '  '); ?>

  <p class="seiten-erklaerung">Impressum und Datenschutzerklärung dieser
     Installation. Beide Seiten sind <strong>ohne Anmeldung</strong> erreichbar
     und in jeder Fußzeile verlinkt. Der Inhalt ist Sache des Betreibers; die
     Anwendung liefert keinen Text mit.</p>

  <?php /* data-dirty-track hängt die Speichern-Leiste an das Formular
           (assets/forms.js) — ohne das Attribut erschiene sie nie, und zwar
           lautlos. data-submit-on-ctrl-enter, weil der Hinweistext der Leiste
           es zusagt. */ ?>
  <form method="post" data-dirty-track data-submit-on-ctrl-enter>
    <?= csrf_field() ?>

    <div class="zweispalter">
      <?php foreach (RT_TEXTE as $k => $name):
        $t = $texte[$k];
        $leer = rt_leer($t['inhalt']);
        /* Der Kartenkopf trägt den Stand — dieselbe Auskunft, die die
           öffentliche Seite unten zeigt. */
        $kopfzahl = $t['stand'] !== null && strtotime($t['stand']) !== false
            ? 'Stand ' . date('d.m.Y', (int)strtotime($t['stand']))
            : 'ohne Standdatum';
      ?>
        <?php ui_karte_start(['titel' => $name, 'zahl' => $kopfzahl]); ?>

          <?php ui_feld([
              'name'         => 'text_' . $k,
              'id'           => 'rt-' . $k,
              'label'        => 'Text',
              'label_zusatz' => '(Markdown: Überschriften, Absätze, Listen, Links)',
              'art'          => 'textarea',
              'zeilen'       => 18,
              'klasse'       => 'feld-fest',
              'wert'         => $t['inhalt'],
              'klein'        => 'Erlaubt: ## Überschrift, ### Unterüberschrift, '
                              . 'Absätze, - Listen, 1. Nummerierung, '
                              . '[Text](https://…). Kein HTML — Tags erscheinen '
                              . 'als Text.',
          ]); ?>

          <?php /* DAS STANDDATUM WIRD VON HAND GESETZT. Automatisch wäre es
                   bequemer und an einem Rechtstext falsch: Das Datum ist eine
                   Aussage darüber, auf welchem Stand der Text INHALTLICH ist —
                   eine Kommakorrektur soll ihn nicht neu datieren. Leer heißt:
                   keine Standzeile auf der öffentlichen Seite. */ ?>
          <?php ui_feld([
              'name'  => 'stand_' . $k,
              'id'    => 'rt-stand-' . $k,
              'label' => 'Stand',
              'art'   => 'date',
              'wert'  => (string)($t['stand'] ?? ''),
              'klein' => 'Erscheint als „Stand: …" am Ende der Seite. Leer '
                       . 'lassen heißt: kein Datum. Wird nicht automatisch '
                       . 'gesetzt — bei einem Rechtstext ist das Datum eine '
                       . 'Aussage.',
          ]); ?>

          <?php if (!$leer): ?>
            <div class="vorschau">
              <h4>Vorschau</h4>
              <div class="text">
                <?= rt_html($t['inhalt']) ?>
                <?= rt_stand_markup($t['stand']) ?>
              </div>
            </div>
          <?php endif; ?>

          <div class="rechtstext-fuss">
            <?= $leer
                ? ui_plakette('leer', ['ton' => 'rot'])
                : ui_plakette('öffentlich', ['ton' => 'blau']) ?>
            <span class="feld-klein">Seite:
              <a href="<?= e(RT_SEITEN[$k]) ?>"><?= e(RT_SEITEN[$k]) ?></a></span>
          </div>

        <?php ui_karte_ende(); ?>
      <?php endforeach; ?>
    </div>

    <?php /* Die Leiste steht INNERHALB des Formulars — forms.js sucht sie mit
             f.querySelector('[data-speichern]'). Außerhalb erschiene sie nie,
             ohne Fehlermeldung. */ ?>
    <?php ui_speichern_leiste(['text' => 'Änderungen speichern',
                               'hinweis' => 'Es gibt ungespeicherte Änderungen']); ?>
  </form>

  <?php /* Die Vorschau zeigt den GESPEICHERTEN Stand. Das ist keine
           Einschränkung, die man verschweigt — wer gerade getippt hat und
           unten nichts davon sieht, hält den Editor für kaputt. */ ?>
  <p class="feld-hinweis">Die Vorschau zeigt den zuletzt <strong>gespeicherten</strong>
     Stand. Sie entsteht auf dem Server, mit demselben Renderer wie die
     öffentliche Seite — ein zweiter im Browser wäre die Stelle, an der die
     Regeln auseinanderlaufen.</p>

<?php ui_geruest_ende(); ?>
<?php /* forms.js bringt ui_geruest_ende() NICHT mit (nur symbol, schublade,
         blatt, confirm) — ohne diese Zeile bliebe die Speichern-Leiste
         unsichtbar, und zwar ohne jede Fehlermeldung. */ ?>
<?php ui_seite_ende(['skripte' => ['assets/forms.js']]); ?>
