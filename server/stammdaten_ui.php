<?php
declare(strict_types=1);

/**
 * Die Bausteine der Stammdatenlisten (P3/O9c).
 *
 * WARUM DIESE DATEI. Sechs Listen folgen demselben Muster (Standorte,
 * Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel,
 * Bergwacht), und sie standen ZWEIMAL: einmal als persoenlicher Bestand
 * (`einstellungen.php`) und einmal systemweit (`admin_stammdaten.php`).
 *
 * Im Bestand war das Muster in jeder der beiden Dateien fuenfmal
 * ausgeschrieben — zehnmal insgesamt. O8b hat es in `einstellungen.php` zu
 * zwei Schliessungen zusammengezogen; gemessen am Vergleichsstand vor O8a
 * waren rund 70 Prozent des Rettungsmittel-Bereichs von
 * `admin_stammdaten.php` zeichengleich mit dem, was dort stand. Die
 * Schliessungen ein zweites Mal zu kopieren hiesse, denselben Fehler noch
 * einmal zu machen, nur eine Ebene hoeher.
 *
 * SEIT S9/AP5b GIBT ES NUR NOCH EINE SEITE. Die systemweite Stammdatenpflege
 * ist mit dem Modell der zentralen Stammdaten gestrichen (Rahmenplan R39);
 * `einstellungen.php` ist der einzige Aufrufer. Die Datei bleibt trotzdem:
 * Sie traegt die sechs Listen, die fuenf Dialoge und die Sprungliste, und
 * sie wieder in `einstellungen.php` aufzuloesen hiesse, 500 Zeilen in eine
 * Datei zurueckzuschieben, die schon ueber viertausend hat. Was aus der
 * Zweiseitigkeit stammt, steht unten als Option und ist als solche
 * gekennzeichnet.
 *
 * DER UNTERSCHIED ZWISCHEN DEN BEIDEN SEITEN steckte in genau drei Dingen,
 * und die stehen als Optionen darin:
 *
 *   `seite`     wohin ein Formular absendet und ein Anker zeigt
 *               (heisst NICHT `basis`: In dieser Anwendung ist eine Basis
 *                ein Standort, und der Schluessel meint eine URL — die
 *                Wortliste haette das Homonym zu Recht gemeldet)
 *               (seit S9/AP5 die Seite EINES Standorts: `sd_seite($bid)`
 *                — den Reiter `t=rettungsmittel` gibt es nicht mehr)
 *   `zentral`   ein systemweiter Eintrag in der Kontoansicht ist
 *               unveraenderlich. Bis S9/AP5b war er in der Adminansicht der
 *               Gegenstand; die gibt es nicht mehr, die Option bleibt fuer
 *               den Altbestand (R39, Backlog Nr. 168)
 *   `def_action` die Vorbelegung ist eine Eigenschaft des Kontos, nicht des
 *               Bestands
 */

/**
 * EINE STAMMDATENZEILE (E-P3-35/26).
 *
 * Gibt die versteckten POST-Formulare aus UND die Zeile. Beides gehört
 * zusammen: Die Knöpfe der Zeile zeigen über `form=` auf die Formulare
 * (ui_zeilenaktionen), also müssen sie im selben Atemzug entstehen.
 *
 * $o: seite, name, klein, anker, praefix (eindeutig je Liste), id, base_id,
 *     zentral (bool), stern (bool), del_action, del_frage,
 *     def_action (optional — nur wo es eine Vorbelegung gibt),
 *     bearbeiten_attr (Attribute am „Bearbeiten"-Eintrag — der Dialog-Öffner,
 *     S9/AP5-4), plaketten (zusätzliches Markup),
 *     vorn (Markup VOR dem Text — das Artzeichen, S9/AP5),
 *     zeilen_id (bool: die Zeile bekommt `id="<praefix>-<id>"` als Sprungziel)
 */
/**
 * AB WIE VIELEN EINTRAEGEN eine Liste ihr Hilfsmittel bekommt (S9/AP5).
 *
 * Sechs — die Zahl steht im Konzept (E-S9-14, Backlog Nr. 44) und im Mockup
 * M-S9-05. Darunter sieht man die ganze Liste, ohne zu rollen, und eine
 * Sprungliste waere eine zweite Aufzaehlung derselben Namen.
 *
 * SIE STEHT AN EINER STELLE, weil sie an sechs gebraucht wird: einmal je
 * Listenkarte der Standortseite. Welches Hilfsmittel eine Liste bekommt,
 * haengt daran, woran man ihre Eintraege erkennt: Die Rettungsmittel tragen
 * ein Artzeichen und bekommen die SPRUNGLISTE, alles Uebrige den FILTER.
 */
const SD_HILFE_AB = 6;

/**
 * Die Adresse der Seite EINES Standorts (S9/AP5, PS-12).
 *
 * Sie steht an einer Stelle, weil sie an einem Dutzend gebraucht wird: in
 * jedem Bearbeiten-Verweis, in jeder Formularadresse und in jeder Zeile der
 * Standortliste. Vorher stand dort `einstellungen.php?t=rettungsmittel`,
 * zwoelfmal ausgeschrieben — und der Reiter gibt es nicht mehr.
 *
 * SEIT S9/AP5b OHNE ZWEITEN PARAMETER. Bis dahin nahm sie `$basis` entgegen,
 * weil die Verwaltung ihre eigene Seite hatte (`admin_stammdaten.php`); die
 * ist mit dem Modell der zentralen Stammdaten gestrichen (R39). Ein
 * Vorgabewert, den nur noch ein Wert erreicht, ist keine Bequemlichkeit,
 * sondern eine offene Tuer: `sd_seite($id, 'admin_stammdaten.php')` haette
 * weiterhin eine Adresse geliefert, und die Linkprobe haette sie nicht
 * gesehen.
 */
function sd_seite(int $baseId): string
{
    /* DIE ADRESSE STEHT ALS GANZE IN EINER ZEICHENKETTE und nicht
       zusammengesetzt. Die Linkprobe erkennt einen Verweis am Muster
       `seite.php?…` INNERHALB einer Zeichenkette; zusammengesetzt sieht sie
       ihn gar nicht. Die zwoelf ausgeschriebenen Adressen, die diese
       Funktion abloest, hat sie geprueft — nach dem Umbau waren es null,
       und die Gesamtzahl fiel von 140 auf 126 geprueften Verweisen, ohne
       dass jemand etwas gemeldet haette. Ausgeschrieben prueft sie wieder,
       dass `einstellungen.php` `t` UND `s` liest. */
    return 'einstellungen.php?t=standort&s=' . $baseId;
}

function sd_zeile(array $o): void
{
    $id   = (int)$o['id'];
    $pre  = (string)$o['praefix'] . '-' . $id;
    /* Der Vorgabewert ist die STANDORTLISTE und nicht mehr der Reiter
       `t=rettungsmittel`: Den gibt es seit S9/AP5 nicht mehr, und die Weiche
       am Kopf von `einstellungen.php` haette jedes Formular, das den
       Schluessel vergisst, still auf die Liste geworfen — ohne Fehler, ohne
       Meldung, nur mit einem Anker, der dort nichts findet. Alle Aufrufe
       reichen ihn heute mit; der Wert steht als Netz, nicht als Weg. */
    $seite = (string)($o['seite'] ?? 'einstellungen.php?t=standorte');
    $ziel = $seite . '#' . (string)$o['anker'];
    $zentral = !empty($o['zentral']);

    /* Systemweite Einträge lassen sich in der Kontoansicht weder bearbeiten
       noch löschen — sie gehören der Administration. Die Vorbelegung dagegen
       schon: Sie ist eine Eigenschaft DIESES Kontos. */
    $eintraege = [];
    if (!empty($o['def_action']) && empty($o['stern'])) {
        echo '<form method="post" id="f-' . $pre . '-def" class="nur-vorlesen" action="'
           . ui_e($ziel) . '">' . csrf_field()
           . '<input type="hidden" name="action" value="' . ui_e((string)$o['def_action']) . '">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<input type="hidden" name="base_id" value="' . (int)$o['base_id'] . '">'
           . "</form>\n";
        $eintraege[] = ['text' => 'Als Vorbelegung', 'symbol' => 'stern',
                        'art' => 'leise-orange', 'form' => 'f-' . $pre . '-def'];
    }
    if (!$zentral) {
        /* „Bearbeiten" ist seit S9/AP5-4 kein Verweis mehr, sondern ein
           Dialog-Oeffner: `attr` traegt `data-dialog` und die `data-w-`-Kette
           (`sd_oeffner()`). Die Option `bearbeiten_href` stand daneben,
           solange die Verwaltung den Verweis-Weg noch benutzte; sie ist mit
           S9/AP5b entfallen. `href` BLEIBT `#` und wird nicht weggelassen:
           `ui_zeilenaktionen()` gibt nur bei nichtleerem `href` ein `<a>` aus
           — ohne wuerde aus dem Oeffner ein Absendeknopf, und die Seite luede
           neu, statt den Dialog zu oeffnen. */
        if (!empty($o['bearbeiten_attr'])) {
            $eintraege[] = ['text' => 'Bearbeiten', 'symbol' => 'stift',
                            'href' => '#',
                            'attr' => (string)$o['bearbeiten_attr']];
        }
        echo '<form method="post" id="f-' . $pre . '-del" class="nur-vorlesen" action="'
           . ui_e($ziel) . '" data-confirm="' . ui_e((string)$o['del_frage']) . '">' . csrf_field()
           . '<input type="hidden" name="action" value="' . ui_e((string)$o['del_action']) . '">'
           . '<input type="hidden" name="id" value="' . $id . '">'
           . '<input type="hidden" name="base_id" value="' . (int)$o['base_id'] . '">'
           . "</form>\n";
        $eintraege[] = ['text' => 'Löschen', 'symbol' => 'korb',
                        'art' => 'gefahr', 'form' => 'f-' . $pre . '-del'];
    }

    $plaketten = (string)($o['plaketten'] ?? '');
    if ($zentral) { $plaketten .= ui_plakette('systemweit'); }
    if (!empty($o['stern'])) {
        $plaketten .= ui_symbol('stern', 'zeile-stern', 'Vorbelegung neuer Diensttage');
    }

    /* DIE ZEILE ALS SPRUNGZIEL (S9/AP5). `<praefix>-<id>` — dieselben
       Praefixe, die die verborgenen Formulare schon tragen (`f-veh-7-del`).
       Das Konzept schrieb `#dest-<id>` fuer die Zielkliniken; die heissen in
       dieser Anwendung an jeder anderen Stelle `td`, und ein zweiter Name
       fuer dieselbe Sache im selben Modul ist kein Gewinn — berichtigt ist
       das Konzept, nicht der Code. Gesetzt wird die Kennung nur, wo sie
       gebraucht wird: Eine `id` an jeder Zeile jeder Liste waere Ballast,
       und `:target` faerbte dann auch Zeilen, die niemand angesprungen hat. */
    ui_zeile([
        'vorn'      => (string)($o['vorn'] ?? ''),
        'attr'      => !empty($o['zeilen_id']) ? ' id="' . $pre . '"' : '',
        'text'      => (string)$o['name'],
        'klein'     => (string)($o['klein'] ?? ''),
        'plaketten' => $plaketten,
        'aktionen'  => $eintraege
            ? ui_zeilenaktionen(['titel' => (string)$o['name'], 'eintraege' => $eintraege])
            : '',
    ]);
}

/* ===========================================================================
 * DIE DIALOGE DER STAMMDATEN                    S9/AP5-4, E-S9-19, M-S9-07
 * ===========================================================================
 *
 * WAS SIE ABLOESEN. Bis Web 16.3.0 stand unter jeder Liste ein Formular
 * (`sd_form()` und drei handgeschriebene). Das hatte drei Folgen, und alle
 * drei sind mit den Standortseiten schlimmer geworden:
 *
 *   1. Wer den zwoelften Eintrag anlegen wollte, rollte an elf vorbei — das
 *      Formular stand am ENDE der Liste, und die Liste war der Grund, warum
 *      man die Seite geoeffnet hatte.
 *   2. „Bearbeiten" war ein VERWEIS auf dieselbe Seite mit `?ec=7`. Die Seite
 *      lud neu, sprang zum Anker, und das Formular darunter trug ploetzlich
 *      andere Werte. Auf einem Handy sah man vom Wechsel nichts.
 *   3. In der Besatzungskarte stand es je ROLLE einmal — an einem Standort
 *      mit fuenf Rollen fuenfmal dasselbe Formular. Der Kartenfilter musste
 *      sie eigens verbergen (`kartenfilter.js`, Punkt 3).
 *
 * Jetzt oeffnet „Anlegen" im Kartenkopf und „Bearbeiten" im Zeilenmenue
 * denselben Dialog; die Liste bleibt, wo sie ist.
 *
 * WIE VIELE ES SIND. E-S9-19 nennt drei ARTEN — Rettungsmittel,
 * Besatzungsmitglied, Zielklinik —, und M-S9-07 zeichnet sie. Zwei Listen
 * zeichnet das Mockup nicht: „Weitere Rettungsmittel" und „Bergwacht". Sie
 * haben genau ein Feld, dasselbe wie das Besatzungsmitglied ohne die Rolle.
 * Deshalb entstehen aus DREI Funktionen FUENF Dialoge: `sd_dialog_eintrag()`
 * wird dreimal aufgerufen (Besatzung mit Rollenauswahl, weitere
 * Rettungsmittel und Bergwacht ohne).
 *
 * Der Gegenentwurf — EIN Dialog, der seine Beschriftungen vom Oeffner holt —
 * ist erwogen und verworfen: Die Feldbeschriftung steht in `<label>` neben
 * dem Pflichtstern, und `data-fuell` setzt `textContent`, wuerfe den Stern
 * also weg. Ein Dialog, dessen Beschriftungen erst im Browser entstehen,
 * liefe ausserdem an der Wortliste vorbei. Fuenf Dialoge im Markup kosten
 * Bytes; ein Text, den niemand nachlesen kann, kostet mehr.
 *
 * WAS DER OEFFNER TRAEGT. `dialog.js` fuellt aus `data-w-<schluessel>` in
 * `data-fuell="<schluessel>"`. Jeder Oeffner nennt JEDEN Schluessel des
 * Dialogs, auch den leeren (`data-w-name=""`): Ein Feld, zu dem der Oeffner
 * schweigt, bleibt unberuehrt — und nach einem Fehlversuch steht darin noch
 * die verworfene Eingabe, weil der Server sie ins Markup geschrieben hat.
 *
 * DER FEHLERWEG BLEIBT IM DIALOG (E-S9-19). Schlaegt das Speichern fehl,
 * wird NICHT umgeleitet: Die Seite ist die Antwort auf den POST, der Dialog
 * traegt die Eingabe und die Meldung, und das Seitenskript oeffnet ihn mit
 * `window.edDialog.auf()`. Deshalb nehmen alle drei Funktionen `werte` und
 * `fehler` — beides kommt aus `$_POST` bzw. der Pruefschicht.
 *
 * DER FUSSKNOPF TRAEGT KEIN SYMBOL. Sein Text wechselt zwischen „Anlegen"
 * und „Änderung speichern", und `data-fuell` setzt bei einem <button> den
 * `textContent` — ein Symbol darin waere nach dem ersten Oeffnen weg.
 */

/**
 * Der Dialog „Rettungsmittel" (M-S9-07, Feldfolge nach E-S9-09/E-S9-19).
 *
 * $o: seite       wohin das Formular absendet
 *     base_id     der Standort DIESER Seite; 0 auf der Standortliste, wo der
 *                 Dialog die Karte „Ohne Standort" bedient
 *     base_name   sein Name — er steht in der Zeile unter den Feldern, wo der
 *                 Typ Standard keine Auswahl bekommt
 *     unterzeile  was unter dem Titel steht („Standort Talwang")
 *     bases       [id => name] fuer die Standortauswahl
 *     werte       Vorbelegung im Fehlerfall (Schluessel wie im POST)
 *     fehler      Meldung im Dialog, leer wenn keiner
 */
function sd_dialog_rettungsmittel(array $o): void
{
    $heimat = (int)($o['base_id'] ?? 0);
    $w      = (array)($o['werte'] ?? []);
    $fehler = (string)($o['fehler'] ?? '');
    $rollen = array_map('strval', (array)($w['roles'] ?? []));
    $caps   = array_map('strval', (array)($w['caps'] ?? []));

    $typOptionen = [];
    foreach (VEHICLE_TYPEN as $tk => $tr) { $typOptionen[$tk] = $tr['label']; }
    /* „Ohne Standort" ist der erste Eintrag und nicht ein Haken daneben. Der
       Haken war bis Web 16.3.0 der einzige Weg und schlug eine verborgene
       Standortkennung; wer ihn setzte, sah nicht, WAS er damit ueberschrieb.
       Eine Auswahl zeigt beides in einer Zeile. */
    $baseOptionen = ['0' => 'Ohne Standort'];
    foreach ((array)($o['bases'] ?? []) as $bid => $bname) {
        $baseOptionen[(string)$bid] = (string)$bname;
    }
    ?>
<dialog class="dialog" id="dlg-veh" data-heimat="<?= $heimat ?>">
  <form method="post" action="<?= ui_e((string)$o['seite']) ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="veh_save">
    <input type="hidden" name="id" value="<?= (int)($w['id'] ?? 0) ?>" data-fuell="id">
    <div class="dialog-kopf">
      <h2 data-fuell="titel">Rettungsmittel anlegen</h2>
      <p class="unterzeile"><?= ui_e((string)($o['unterzeile'] ?? '')) ?></p>
    </div>
    <div class="dialog-inhalt">
      <?php if ($fehler !== ''): ?>
        <?= ui_meldung_markup('fehler', $fehler) ?>
      <?php endif; ?>
      <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'name', 'id' => 'dlgveh-name',
                     'pflicht' => true,
                     'platzhalter' => 'z. B. Alpenfalke 1 oder NEF Talwang 76/1',
                     'wert' => (string)($w['name'] ?? ''),
                     'attr' => ' maxlength="64" data-fuell="name"']); ?>
      <?php ui_feld(['label' => 'Kurzname', 'name' => 'kurz', 'id' => 'dlgveh-kurz',
                     'label_zusatz' => 'bis ' . RM_KURZ_MAX . ' Zeichen',
                     'klein' => 'Steht in der Leiste und auf den Kacheln. Ohne ihn '
                              . 'erscheint dort die volle Bezeichnung.',
                     'platzhalter' => 'z. B. AF 1',
                     'wert' => (string)($w['kurz'] ?? ''),
                     'attr' => ' maxlength="' . RM_KURZ_MAX . '" data-fuell="kurz"']); ?>
      <?php /* TYP VOR BETRIEBSART (E-S9-09). Er entscheidet ueber alles, was
               darunter steht: ob die Betriebsart waehlbar ist, ob es Rollen
               und Faehigkeiten gibt, ob der Standort Pflicht ist. Stuende er
               darunter, aenderte eine Wahl rueckwirkend das schon
               Ausgefuellte. */
            ui_feld(['label' => 'Typ', 'name' => 'typ', 'art' => 'select',
                     'id' => 'dlgveh-typ', 'optionen' => $typOptionen,
                     'wert' => (string)($w['typ'] ?? 'standard'),
                     'attr' => ' data-fuell="typ"']); ?>
      <div class="feld">
        <span class="feld-label">Betriebsart <span class="feld-pflicht" aria-hidden="true">*</span><?php
          /* „bei Veranstaltung fest" erscheint nur dann — der Text steht im
             Markup und nicht im Skript, damit die Wortliste ihn sieht. */
          ?> <span class="feld-klein-inline" data-veh-fest hidden>bei Veranstaltung fest</span></span>
        <span class="vehkind">
          <label><input type="radio" name="kind" value="air" class="vehkind-radio"
                 data-fuell="kind"<?= ($w['kind'] ?? '') === 'air' ? ' checked' : '' ?>>
            luftgebunden</label>
          <label><input type="radio" name="kind" value="ground" class="vehkind-radio"
                 data-fuell="kind"<?= ($w['kind'] ?? '') === 'ground' ? ' checked' : '' ?>>
            bodengebunden</label>
        </span>
      </div>
      <div class="feld rollen-zeile" hidden>
        <span class="feld-label">Besatzungsrollen <span class="feld-klein-inline">nach Betriebsart</span></span>
        <span class="acroles">
          <?php foreach (CREW_ROLES as $rc => $rr): ?>
            <label class="rollehaken" data-kind="<?= ui_e($rr['kind']) ?>">
              <input type="checkbox" name="roles[]" value="<?= ui_e($rc) ?>" data-fuell="rollen"
                     <?= in_array($rc, $rollen, true) ? 'checked' : '' ?>>
              <?= ui_e($rr['label']) ?></label>
          <?php endforeach; ?>
        </span>
      </div>
      <div class="feld vehcaps-zeile" hidden>
        <span class="feld-label">Fähigkeiten <span class="feld-klein-inline">nur luftgebunden</span></span>
        <span class="acroles vehcaps">
          <?php foreach (VEHICLE_CAPABILITIES as $ck => $cl): ?>
            <label><input type="checkbox" name="caps[]" value="<?= ui_e($ck) ?>" data-fuell="caps"
                   <?= in_array($ck, $caps, true) ? 'checked' : '' ?>>
              <?= ui_e($cl) ?></label>
          <?php endforeach; ?>
        </span>
      </div>
      <p class="feld-klein" data-veh-ohne-vorlagen hidden>Keine Besatzungsrollen und keine
         Fähigkeiten — nur der Typ Standard hat Vorlagen.</p>
      <?php /* DER STANDORT IST EIN FELD, ABER NICHT IMMER (E-S9-19, M-S9-07).
               Beim Typ Standard gehoert das Rettungsmittel zu der Seite, auf
               der man steht — dann sagt es die Zeile darunter, und es gibt
               nichts zu waehlen. Bei den drei uebrigen Typen ist der Standort
               freiwillig, also waehlbar. Auf der STANDORTLISTE (`heimat = 0`)
               bleibt die Auswahl immer sichtbar: Dort gibt es keine Seite, von
               der sich ein Standort ablesen liesse. */
            ui_feld(['label' => 'Standort', 'name' => 'base_id', 'art' => 'select',
                     'id' => 'dlgveh-base', 'optionen' => $baseOptionen,
                     'klein' => 'Bergwacht, Veranstaltung und Sonstiges brauchen keinen.',
                     'wert' => (string)($w['base_id'] ?? $heimat),
                     'attr' => ' data-fuell="base"']); ?>
      <p class="feld-klein" data-veh-heimat hidden>Standort:
         <strong><?= ui_e((string)($o['base_name'] ?? '')) ?></strong> — der Dialog
         gehört zu seiner Seite.</p>
    </div>
    <div class="dialog-fuss">
      <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                    'attr' => ' data-dialog-zu']) ?>
      <?= ui_knopf(['text' => 'Anlegen', 'art' => 'primaer', 'attr' => ' data-fuell="knopf"']) ?>
    </div>
  </form>
</dialog>
<?php
}

/**
 * Der Dialog einer einfachen Liste — ein Namensfeld, wahlweise eine Rolle
 * davor (M-S9-07 „Besatzungsmitglied"; dieselbe Form fuer „Weitere
 * Rettungsmittel" und „Bergwacht", die das Mockup nicht zeichnet).
 *
 * $o: id          Kennung des <dialog> (`dlg-crew`, `dlg-res`, `dlg-bw`)
 *     seite, base_id, unterzeile
 *     action      der Schreibweg (`crew_save` …)
 *     titel_neu   Titel beim Anlegen (der Oeffner setzt ihn beim Bearbeiten um)
 *     label, platzhalter, max, hinweis
 *     rollen      [code => Beschriftung] — leer heisst: kein Rollenfeld
 *     werte, fehler
 */
function sd_dialog_eintrag(array $o): void
{
    $w      = (array)($o['werte'] ?? []);
    $fehler = (string)($o['fehler'] ?? '');
    $rollen = (array)($o['rollen'] ?? []);
    $kurz   = str_replace('dlg-', '', (string)$o['id']);
    ?>
<dialog class="dialog" id="<?= ui_e((string)$o['id']) ?>">
  <form method="post" action="<?= ui_e((string)$o['seite']) ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="<?= ui_e((string)$o['action']) ?>">
    <input type="hidden" name="id" value="<?= (int)($w['id'] ?? 0) ?>" data-fuell="id">
    <input type="hidden" name="base_id" value="<?= (int)($o['base_id'] ?? 0) ?>">
    <div class="dialog-kopf">
      <h2 data-fuell="titel"><?= ui_e((string)$o['titel_neu']) ?></h2>
      <p class="unterzeile"><?= ui_e((string)($o['unterzeile'] ?? '')) ?></p>
    </div>
    <div class="dialog-inhalt">
      <?php if ($fehler !== ''): ?>
        <?= ui_meldung_markup('fehler', $fehler) ?>
      <?php endif; ?>
      <?php if ($rollen):
              /* DIE ROLLE IST EIN FELD GEWORDEN (M-S9-07). Vorher stand je
                 Rolle ein eigenes Formular unter ihrer Zwischenueberschrift,
                 und die Rolle war eine verborgene Kennung darin. Angeboten
                 werden nur die Rollen, die es an DIESEM Standort gibt — sie
                 kommen von den Rettungsmitteln, und ein Pilot an einem
                 reinen NEF-Standort waere eine Vorlage, die nie erscheint. */
              ui_feld(['label' => 'Rolle', 'name' => 'role', 'art' => 'select',
                       'id' => 'dlg' . $kurz . '-rolle', 'optionen' => $rollen,
                       'wert' => (string)($w['role'] ?? ''),
                       'attr' => ' data-fuell="rolle"']);
            endif; ?>
      <?php ui_feld(['label' => (string)$o['label'], 'name' => 'name',
                     'id' => 'dlg' . $kurz . '-name', 'pflicht' => true,
                     'platzhalter' => (string)($o['platzhalter'] ?? ''),
                     'wert' => (string)($w['name'] ?? ''),
                     'attr' => ' maxlength="' . (int)$o['max'] . '" data-fuell="name"']); ?>
      <?php if (!empty($o['hinweis'])): ?>
        <p class="feld-klein"><?= ui_e((string)$o['hinweis']) ?></p>
      <?php endif; ?>
    </div>
    <div class="dialog-fuss">
      <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                    'attr' => ' data-dialog-zu']) ?>
      <?= ui_knopf(['text' => 'Anlegen', 'art' => 'primaer', 'attr' => ' data-fuell="knopf"']) ?>
    </div>
  </form>
</dialog>
<?php
}

/**
 * Der Dialog „Zielklinik" — Bezeichnung und ein Nur-Lage-Ortsfeld (M-S9-07).
 *
 * Das Ortsfeld bringt Lupe, Vorschlagsliste, Pin-Knopf und Koordinaten-Chip
 * mit (E-S9-06/-07). Es braucht seinen Praefix in `$ORTSFELDER`; das tut die
 * aufrufende Seite, weil sie die Liste fuehrt.
 *
 * $o: seite, base_id, unterzeile, praefix, werte, fehler
 */
function sd_dialog_zielklinik(array $o): void
{
    $w      = (array)($o['werte'] ?? []);
    $fehler = (string)($o['fehler'] ?? '');
    $pre    = (string)$o['praefix'];
    ?>
<dialog class="dialog" id="dlg-td">
  <form method="post" action="<?= ui_e((string)$o['seite']) ?>">
    <?= csrf_field() ?><input type="hidden" name="action" value="td_save">
    <input type="hidden" name="id" value="<?= (int)($w['id'] ?? 0) ?>" data-fuell="id">
    <input type="hidden" name="base_id" value="<?= (int)($o['base_id'] ?? 0) ?>">
    <div class="dialog-kopf">
      <h2 data-fuell="titel">Zielklinik anlegen</h2>
      <p class="unterzeile"><?= ui_e((string)($o['unterzeile'] ?? '')) ?></p>
    </div>
    <div class="dialog-inhalt">
      <?php if ($fehler !== ''): ?>
        <?= ui_meldung_markup('fehler', $fehler) ?>
      <?php endif; ?>
      <?php ui_feld(['label' => 'Bezeichnung', 'name' => 'name',
                     'id' => $pre . '-name', 'pflicht' => true,
                     'platzhalter' => 'z. B. Klinikum Westried',
                     'wert' => (string)($w['name'] ?? ''),
                     'attr' => ' maxlength="' . SD_ZIEL_MAX . '" data-fuell="name"']); ?>
      <?php ui_ortsfeld([
              'praefix' => $pre, 'feld' => false, 'ortswahl' => true,
              'klasse' => 'loc-inline',
              'such_hinweis' => 'Lage (freiwillig)',
              'lat_name' => 'lat', 'lon_name' => 'lon',
              'lat' => (string)($w['lat'] ?? ''),
              'lon' => (string)($w['lon'] ?? ''),
          ]); ?>
      <p class="feld-klein">Vorschlag im Feld „Transportziel“. Mit Koordinate steht
         die Zielklinik auf der Karte und liefert die Entfernung.</p>
    </div>
    <div class="dialog-fuss">
      <?= ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'typ' => 'button',
                    'attr' => ' data-dialog-zu']) ?>
      <?= ui_knopf(['text' => 'Anlegen', 'art' => 'primaer', 'attr' => ' data-fuell="knopf"']) ?>
    </div>
  </form>
</dialog>
<?php
}

/**
 * Die `data-w-`-Kette eines Oeffners (S9/AP5-4).
 *
 * Jeder Oeffner nennt JEDEN Schluessel seines Dialogs — auch den leeren.
 * `dialog.js` laesst ein Feld unberuehrt, zu dem der Oeffner schweigt; nach
 * einem Fehlversuch steht darin aber noch die verworfene Eingabe, weil der
 * Server sie ins Markup geschrieben hat. Ein „Anlegen", das `name` nicht
 * nennt, oeffnete den Dialog mit dem Namen des letzten Fehlversuchs.
 *
 * Die REIHENFOLGE ist bedeutsam: `dialog.js` laeuft die Attribute in
 * Dokumentreihenfolge ab und loest je Auswahl und je Hakengruppe ein
 * `change` aus. Was die Anzeige steuert (`typ`, `kind`), gehoert deshalb nach
 * vorn und der letzte Schluessel sollte eine Auswahl oder Hakengruppe sein —
 * dann laeuft die Anpassung zuletzt ueber den fertigen Stand.
 */
function sd_oeffner(string $dialog, array $werte): string
{
    $s = ' data-dialog="' . ui_e($dialog) . '"';
    foreach ($werte as $k => $v) {
        $s .= ' data-w-' . ui_e((string)$k) . '="' . ui_e((string)$v) . '"';
    }
    return $s;
}
