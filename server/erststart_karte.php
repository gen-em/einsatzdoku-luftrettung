<?php
declare(strict_types=1);

/**
 * DIE ERSTSTART-KARTE (P5b/AP9, E-P5b-09, Mockup M-P5b-02b).
 *
 * Drei Schritte ueber der Tagesuebersicht: Standort (optional),
 * Rettungsmittel, Uhr oder Handy koppeln. Sie steht dort, solange etwas
 * offen ist — und sie steht DARUEBER und nicht DAVOR.
 *
 * ---------------------------------------------------------------------------
 * KEINE DIALOG, UND DAS IST DER GANZE UNTERSCHIED
 * ---------------------------------------------------------------------------
 *
 * Das Mockup sagt es in einem Satz: **„Wer sie ignoriert, arbeitet
 * trotzdem."** Darunter steht die Tagesuebersicht, vollstaendig und bedienbar.
 * Ein Dialog haette den ersten Eindruck dieser Anwendung zu einer Huerde
 * gemacht, die man wegklickt, bevor man sie gelesen hat.
 *
 * ---------------------------------------------------------------------------
 * DIE SCHRITTE WERDEN NICHT HIER ERLEDIGT
 * ---------------------------------------------------------------------------
 *
 * Die Knoepfe fuehren in die Wege, die es schon gibt: die Anlegen-Dialoge der
 * Stammdaten (S9) und die Geraeteseite. Der Rueckweg landet wieder hier, und
 * der Schritt ist dann erledigt — vermerkt von der Stelle, die tatsaechlich
 * etwas angelegt hat, nicht von dieser Karte.
 *
 * WARUM NICHT EINFACH ZAEHLEN. Ein `SELECT COUNT(*) FROM bases` waere
 * einfacher und waere falsch: „Ich brauche keinen Standort" ist eine Antwort,
 * und wer den Schritt bewusst uebergeht, soll ihn nicht bei jedem Anmelden
 * wiedersehen. Der Stand steht deshalb am Konto (`erststart_stand`), nicht in
 * den Stammdaten.
 *
 * ---------------------------------------------------------------------------
 * ZWEI AUSWEGE, UND SIE BEDEUTEN VERSCHIEDENES
 * ---------------------------------------------------------------------------
 *
 *   „Spaeter"           blendet die Karte bis zur naechsten Anmeldung aus.
 *                       Der Stand bleibt; morgen steht sie wieder da.
 *   „nicht mehr zeigen" setzt `erststart_stand = -1`. Dauerhaft.
 *
 * Das erste ist eine Vertagung, das zweite eine Entscheidung. Beide gehoeren
 * hierhin, weil die Karte sonst genau das waere, was sie nicht sein soll:
 * etwas, das man nicht loswird.
 *
 * Erwartet: `$userId`, `$erststartOffen` (Liste aus `erststart_offen()`),
 * `$erststartStand`.
 */

require_once __DIR__ . '/einstieg_lib.php';

/* Was steht schon da? Nur fuer die Kleinzeile der erledigten Schritte —
 * „Hochkreuth — Wache Nord" statt der Erklaerung, die man nicht mehr braucht.
 * Eine Abfrage je Zeile und nur fuer die erledigten. */
$ersteZeile = static function (string $tabelle, int $uid): ?string {
    try {
        $st = db()->prepare("SELECT name FROM `$tabelle`
                              WHERE user_id = ? ORDER BY id LIMIT 1");
        $st->execute([$uid]);
        $n = $st->fetchColumn();
        return $n === false ? null : (string)$n;
    } catch (Throwable) { return null; }
};

$schritte = [
    [
        'bit'    => ERSTSTART_STANDORT,
        'schl'   => 'standort',
        'titel'  => 'Standort anlegen',
        'klein'  => 'Optional — ein Ort, dem deine Rettungsmittel zugeordnet '
                  . 'sind (Wache, Klinik).',
        'knopf'  => 'Anlegen',
        'href'   => 'einstellungen.php?t=standorte',
        /* OPTIONAL HEISST NEUTRALER KNOPF UND SANDFARBENE NUMMER (Mockup).
         * Drei Primaerknoepfe untereinander sagen „dreimal dasselbe Gewicht";
         * hier ist einer davon ausdruecklich uebergehbar, und das soll man
         * sehen, bevor man ihn liest. */
        'art'    => 'neutral',
        'wert'   => static fn(int $u) => $ersteZeile('bases', $u),
    ],
    [
        'bit'    => ERSTSTART_RETTUNGSMITTEL,
        'schl'   => 'rettungsmittel',
        'titel'  => 'Rettungsmittel anlegen',
        'klein'  => 'Womit du fährst oder fliegst: Name, Typ, Standort.',
        'knopf'  => 'Anlegen',
        /* DASSELBE ZIEL WIE SCHRITT 1, und das ist kein Versehen: S9/AP5
         * hat die beiden Reiter zusammengelegt (PS-12). „Rettungsmittel"
         * stand bis Web 7.0.0 daneben; der Schnitt nach Taetigkeit hat sich
         * nicht bewaehrt, weil beide DENSELBEN Bestand luden. Die Liste
         * fuehrt jetzt auf je eine Standortseite, die alles traegt, was dort
         * haengt.
         *
         * DIE ZWEI ZEILEN BLEIBEN TROTZDEM. Sie sind eine Merkliste, keine
         * Wegbeschreibung — und die Reihenfolge stimmt: Ein Rettungsmittel
         * vom Typ `standard` VERLANGT einen Standort (E-S9-09, nachgesehen in
         * `validate_lib.php`). „Optional" an Schritt 1 heisst deshalb
         * „nicht fuer jede Betriebsart noetig", nicht „ueberfluessig". */
        'href'   => 'einstellungen.php?t=standorte',
        'art'    => 'primaer',
        'wert'   => static fn(int $u) => $ersteZeile('vehicles', $u),
    ],
    [
        'bit'    => ERSTSTART_GERAET,
        'schl'   => 'geraet',
        'titel'  => 'Uhr oder Handy koppeln',
        'klein'  => 'Die App erfasst Einsätze unterwegs und gleicht sie hier ab. '
                  . 'Anleitung im Handbuch.',
        'knopf'  => 'Koppeln',
        'href'   => 'einstellungen.php?t=geraete',
        'art'    => 'primaer',
        'wert'   => static fn(int $u) => $ersteZeile('devices', $u),
    ],
];
?>
<?php ui_karte_start(['titel' => 'Willkommen — drei Schritte, dann geht es los',
                      'klasse' => 'erststart']); ?>

  <?php foreach ($schritte as $i => $s): ?>
    <?php
      $fertig = !in_array($s['schl'], $erststartOffen, true);
      $nr     = $i + 1;

      /* VORN: die Nummer, oder ein Haken, wenn erledigt. Der Haken ist das
       * vorhandene Symbol; die Nummer ist der eine neue Baustein dieses
       * Pakets und steht so im freigegebenen Mockup. */
      $vorn = $fertig
        ? '<span class="erststart-nr erststart-nr-fertig">'
          . ui_symbol('haken', 'symbol-klein') . '</span>'
        : '<span class="erststart-nr' . ($s['art'] === 'neutral'
              ? ' erststart-nr-kann' : '') . '">' . $nr . '</span>';

      /* ERLEDIGT: der WERT statt der Erklaerung. Wer einen Standort angelegt
       * hat, braucht nicht mehr zu lesen, was ein Standort ist — er will
       * sehen, welchen er angelegt hat. */
      $wert  = $fertig ? ($s['wert'])($userId) : null;
      $klein = $fertig ? ($wert ?? 'erledigt') : $s['klein'];
    ?>
    <?php ui_zeile([
        'klasse'    => 'erststart-zeile',
        'vorn'      => $vorn,
        'text'      => $s['titel'],
        'klein'     => $klein,
        'plaketten' => $fertig ? ui_plakette('erledigt', ['ton' => 'blau']) : '',
        'aktionen'  => $fertig ? '' : ui_knopf([
            'text' => $s['knopf'], 'art' => $s['art'], 'href' => $s['href'],
        ]),
    ]); ?>
  <?php endforeach; ?>

  <?php /* DER FUSS TRAEGT BEIDE AUSWEGE. Ein Formular und zwei Knoepfe mit
           verschiedenem `name` — kein JavaScript, damit die Karte auch dann
           wegzubekommen ist, wenn ein Skript nicht laedt. */ ?>
  <form method="post" class="erststart-fuss">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="erststart">
    <label class="erststart-nie">
      <input type="checkbox" name="nie" value="1">
      <span>nicht mehr zeigen</span>
    </label>
    <?= ui_knopf(['text' => 'Später', 'art' => 'leise', 'typ' => 'submit']) ?>
  </form>

<?php ui_karte_ende(); ?>
