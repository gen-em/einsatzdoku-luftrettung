<?php
declare(strict_types=1);
require_once __DIR__ . '/auth_guard.php';
require_once __DIR__ . '/validate_lib.php';
require_once __DIR__ . '/tageszuordnung_lib.php';

/**
 * Einen Einsatz einem anderen Flugtag zuordnen (A5.2, Auftragspunkt 13).
 *
 * Eigene Seite statt eines stillen Freischaltens des Datumsfeldes im Formular
 * (E4): Die Nebenwirkung — der Einsatz wechselt die Tageszugehoerigkeit — waere
 * an einem plötzlich beschreibbaren Feld nicht zu sehen. Eine bewusste,
 * benannte Handlung ist einem editierbaren Feld vorzuziehen.
 *
 * Die UHRZEITEN BLEIBEN. Wer die Uhrzeiten mitverschieben will, meint einen
 * anderen Fall — die falsch gestellte Uhr — und ist bei „Datum des Tages
 * ändern" richtig (flugtag_datum.php). Die Seite sagt das ausdruecklich.
 */

$mid = (int)($_POST['id'] ?? $_GET['id'] ?? 0);

$mq = db()->prepare('SELECT id, day, started_at, ended_at
                     FROM missions WHERE id = ? AND user_id = ? AND deleted_at IS NULL');
$mq->execute([$mid, $userId]);                                       // Datentrennung!
$mission = $mq->fetch();
if (!$mission) { http_response_code(404); exit('Einsatz nicht gefunden.'); }

$tag     = (string)$mission['day'];
$meldung = null;
$fehler  = null;
$ziel    = (string)($_POST['ziel'] ?? '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (pruef_kalendertag($ziel, 'Zieldatum') === null) {
        $fehler = 'Bitte ein gültiges Datum wählen.';
    } else {
        $e = tz_einsatz_verschieben($userId, $mid, $ziel);
        if ($e['ok']) {
            // Der Einsatz bleibt derselbe; die Bestaetigung gehoert an seine
            // Seite, wo sich das Ergebnis unmittelbar nachsehen laesst.
            header('Location: einsatz.php?id=' . $mid . '&verschoben=1');
            exit;
        }
        $fehler = $e['meldung'];
    }
}

/* Vorschlagsliste: die Tage, die es schon gibt — plus der Vor- und Folgetag des
   Einsatzes, weil das der haeufigste Fall ist (ein Dienst ueber Mitternacht).
   Die Liste ist ein Angebot, kein Zwang: Das Feld nimmt jedes Datum an, und
   ein fehlender Tag wird beim Verschieben angelegt (E14).

   MITGELIEFERT WIRD, WAS AM ZIELTAG STEHT (Web 5.10.0). Bis dahin nahm die
   Seite ein Datum entgegen und sagte erst nach dem Absenden, worauf es
   hinauslaeuft — angelegt, belegt oder im Papierkorb. Die Frage „welcher
   Flugtag ist das eigentlich?" liess sich hier nicht beantworten, obwohl der
   Server sie kennt.

   Nur EINEN Flugtag je Kalendertag kann es geben: `days` traegt
   `UNIQUE KEY uq_user_day (user_id, day)`. Eine Auswahl zwischen mehreren
   Tagen desselben Datums ist deshalb nicht moeglich — und dass es sie nicht
   gibt, sagt die Seite jetzt ausdruecklich, statt es offenzulassen.

   Tage im Papierkorb sind BEWUSST dabei, obwohl sie nicht vorgeschlagen
   werden: Sie belegen ihr Datum weiterhin, und tz_zieltag_sichern() lehnt sie
   ab. Wer das vorher weiss, waehlt gleich ein anderes Datum. */
$tq = db()->prepare('SELECT d.day, d.deleted_at, a.registration AS ac, b.name AS basis
                     FROM days d
                     LEFT JOIN aircraft a ON a.id = d.aircraft_id
                     LEFT JOIN bases    b ON b.id = d.base_id
                     WHERE d.user_id = ?
                     ORDER BY d.day DESC LIMIT 400');
$tq->execute([$userId]);
$tagZeilen = $tq->fetchAll();

$mq2 = db()->prepare('SELECT day, COUNT(*) AS n FROM missions
                      WHERE user_id = ? AND deleted_at IS NULL
                      GROUP BY day ORDER BY day DESC LIMIT 400');
$mq2->execute([$userId]);
$einsatzZahlen = [];
foreach ($mq2->fetchAll() as $z) { $einsatzZahlen[(string)$z['day']] = (int)$z['n']; }

$tagInfo = [];
foreach ($tagZeilen as $z) {
    $d = (string)$z['day'];
    $tagInfo[$d] = [
        'papierkorb' => $z['deleted_at'] !== null,
        'ac'         => $z['ac']    !== null ? (string)$z['ac']    : null,
        'basis'      => $z['basis'] !== null ? (string)$z['basis'] : null,
        'einsaetze'  => $einsatzZahlen[$d] ?? 0,
    ];
}
/* Einsaetze ohne Flugtag-Zeile gibt es: tz_tag_zustand() zaehlt beides
   getrennt, weil das eine ohne das andere vorkommen kann. Sie gehoeren in die
   Auskunft — sonst hiesse es „noch kein Flugtag angelegt" fuer ein Datum, an
   dem bereits Einsaetze liegen. */
foreach ($einsatzZahlen as $d => $n) {
    if (!isset($tagInfo[$d])) {
        $tagInfo[$d] = ['papierkorb' => false, 'ac' => null, 'basis' => null, 'einsaetze' => $n];
    }
}

/* Beide Abfragen sind gedeckelt. Wo der Deckel gegriffen hat, ist „nicht in
   der Liste" NICHT gleichbedeutend mit „gibt es nicht" — der Hinweis sagt
   dann nichts, statt etwas Falsches zu sagen. */
$vollAb = null;
if (count($tagZeilen) >= 400 || count($einsatzZahlen) >= 400) {
    $vollAb = $tagInfo ? min(array_keys($tagInfo)) : null;
}

$vorhandeneTage = [];
foreach ($tagZeilen as $z) {
    if ($z['deleted_at'] === null) { $vorhandeneTage[] = (string)$z['day']; }
}
$nachbarn = [date('Y-m-d', strtotime($tag . ' -1 day')),
             date('Y-m-d', strtotime($tag . ' +1 day'))];
$vorschlaege = array_values(array_unique(array_merge($nachbarn, $vorhandeneTage)));
sort($vorschlaege);
?>
<!doctype html>
<html lang="de">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Einsatz verschieben · Einsatzdoku</title>
  <link rel="stylesheet" href="<?= asset('assets/style.css') ?>">
  <?= favicon_tags() ?>
</head>
<body>
<?php ui_topbar('uebersicht'); ?>
<div class="layout">
  <?php ui_days_sidebar($tag); ?>
  <main class="page">
    <h1>Einsatz verschieben</h1>
    <?php if ($fehler): ?><p class="alert"><?= e($fehler) ?></p><?php endif; ?>

    <div class="card">
      <p>Der Einsatz vom <strong><?= e(tz_datum_lesbar($tag)) ?></strong>,
         <strong><?= e(fmt_local($mission['started_at'])) ?>
         bis <?= e(fmt_local($mission['ended_at'])) ?></strong> Uhr,
         wird einem anderen Flugtag zugeordnet.</p>
      <p class="muted">Die <strong>Uhrzeiten bleiben unverändert</strong> — es
         ändert sich allein, zu welchem Tag der Einsatz gehört. Sollen auch die
         Zeiten mitwandern, weil die Uhr falsch gestellt war, ist
         <a href="flugtag_datum.php?day=<?= e($tag) ?>">Datum des Flugtags
         ändern</a> der richtige Weg.</p>
      <p class="muted">Existiert am Zieldatum noch kein Flugtag, wird einer
         angelegt — mit deiner Standard-Vorbelegung für Standort und Maschine.
         Ein späterer Upload derselben Uhr zieht den Einsatz nicht zurück.</p>
      <p class="muted">Je Kalendertag gibt es <strong>genau einen
         Flugtag</strong>. Das Datum bestimmt den Zieltag deshalb eindeutig;
         eine Auswahl zwischen mehreren Tagen desselben Datums kann es nicht
         geben. Welcher Flugtag am gewählten Datum liegt, steht unter dem
         Feld.</p>
    </div>

    <form method="post" action="einsatz_verschieben.php" class="formcol"
          id="verschiebeform" data-dirty-track>
      <?= csrf_field() ?>
      <input type="hidden" name="id" value="<?= $mid ?>">
      <label>Neuer Flugtag
        <input type="date" name="ziel" id="zielfeld" required list="dl_tage"
               value="<?= e($ziel !== '' ? $ziel : $nachbarn[1]) ?>">
      </label>
      <datalist id="dl_tage">
        <?php foreach ($vorschlaege as $v): ?><option value="<?= e($v) ?>"><?php endforeach; ?>
      </datalist>
      <p id="zielinfo" class="muted zielinfo" hidden></p>
      <button type="submit" class="btn-primary">Einsatz verschieben</button>
      <p class="login-aux"><a href="einsatz.php?id=<?= $mid ?>"
         data-cancel-form="verschiebeform"
         data-cancel-confirm="Das gewählte Datum geht verloren. Trotzdem abbrechen?"
         >Abbrechen</a></p>
    </form>
    <?php ui_footer(); ?>
  </main>
</div>
<script src="<?= asset('assets/forms.js') ?>"></script>
<script>
/* AUSKUNFT ZUM ZIELTAG (Web 5.10.0).
 *
 * Rein anzeigend: Verhindert wird hier nichts. Was zulaessig ist, entscheidet
 * der Server in tz_einsatz_verschieben() — diese Zeilen sagen nur vorher, was
 * dort passieren wird. Der Text steht deshalb auch dann da, wenn er eine
 * Ablehnung ankuendigt; ein gesperrter Knopf haette die Begruendung
 * verschluckt.
 *
 * Namen von Maschine und Standort sind Eingaben. Sie werden ueber
 * textContent gesetzt, nicht ueber innerHTML.
 */
(function () {
  const TAGE     = <?= json_encode($tagInfo, JSON_UNESCAPED_UNICODE) ?>;
  const VOLL_AB  = <?= json_encode($vollAb) ?>;
  const HEUTETAG = <?= json_encode($tag) ?>;
  const feld = document.getElementById('zielfeld');
  const box  = document.getElementById('zielinfo');
  if (!feld || !box) { return; }

  function de(iso) {
    const t = /^(\d{4})-(\d{2})-(\d{2})$/.exec(iso);
    return t ? `${t[3]}.${t[2]}.${t[1]}` : iso;
  }
  function anzahl(n, eins, viele) { return n + ' ' + (n === 1 ? eins : viele); }

  function zeige() {
    const v = feld.value;
    box.className = 'muted zielinfo';
    if (!/^\d{4}-\d{2}-\d{2}$/.test(v)) { box.hidden = true; return; }
    box.hidden = false;

    if (v === HEUTETAG) {
      box.className = 'zielinfo alert alert-warn';
      box.textContent = 'Das ist der Tag, an dem der Einsatz bereits liegt — '
                      + 'es gäbe nichts zu verschieben.';
      return;
    }

    const t = TAGE[v];
    if (t && t.papierkorb) {
      box.className = 'zielinfo alert alert-warn';
      box.textContent = 'Am ' + de(v) + ' liegt ein Flugtag im Papierkorb. Er belegt '
                      + 'sein Datum weiterhin; das Verschieben wird abgelehnt, solange '
                      + 'er dort liegt. Bitte zuerst wiederherstellen '
                      + '(Einstellungen → Papierkorb) oder ein anderes Datum wählen.';
      return;
    }
    if (t) {
      const wer = [t.ac, t.basis].filter(Boolean).join(' · ');
      box.textContent = 'Am ' + de(v) + ' liegt der Flugtag'
                      + (wer ? ' ' + wer : ' (ohne Angaben zu Maschine und Standort)')
                      + ' mit ' + anzahl(t.einsaetze, 'Einsatz', 'Einsätzen')
                      + '. Diesem Tag wird der Einsatz zugeordnet.';
      return;
    }
    if (VOLL_AB !== null && v < VOLL_AB) {
      box.textContent = 'Für dieses Datum liegt hier keine Auskunft vor — die Liste '
                      + 'reicht nur bis zum ' + de(VOLL_AB) + ' zurück. Ist am Zieltag '
                      + 'bereits ein Flugtag angelegt, wird der Einsatz ihm zugeordnet, '
                      + 'sonst entsteht ein neuer.';
      return;
    }
    box.textContent = 'Am ' + de(v) + ' ist noch kein Flugtag angelegt. Er wird beim '
                    + 'Verschieben angelegt — mit deiner Standard-Vorbelegung für '
                    + 'Standort und Maschine.';
  }

  feld.addEventListener('input', zeige);
  feld.addEventListener('change', zeige);
  zeige();
})();
</script>
</body>
</html>
