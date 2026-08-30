<?php
declare(strict_types=1);

/**
 * Die Bausteine der Stammdatenlisten — eine Fassung für zwei Seiten (P3/O9c).
 *
 * WARUM DIESE DATEI. Sechs Listen folgen demselben Muster (Standorte,
 * Rettungsmittel, Besatzung, Zielkliniken, weitere Rettungsmittel,
 * Bergwacht), und sie stehen ZWEIMAL: einmal als persoenlicher Bestand
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
 * DER UNTERSCHIED ZWISCHEN DEN BEIDEN SEITEN steckt in genau drei Dingen,
 * und die stehen als Optionen darin:
 *
 *   `seite`     wohin ein Formular absendet und ein Anker zeigt
 *               (heisst NICHT `basis`: In dieser Anwendung ist eine Basis
 *                ein Standort, und der Schluessel meint eine URL — die
 *                Wortliste haette das Homonym zu Recht gemeldet)
 *               (`einstellungen.php?t=rettungsmittel` bzw.
 *                `admin_stammdaten.php?t=rettungsmittel`)
 *   `zentral`   ein systemweiter Eintrag in der KONTOANSICHT ist
 *               unveraenderlich — in der ADMINANSICHT ist er der Gegenstand
 *   `def_action` die Vorbelegung gibt es nur im Konto: Sie ist eine
 *               Eigenschaft dieses Kontos, nicht des Bestands
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
 *     bearbeiten_href, plaketten (zusätzliches Markup)
 */
function sd_zeile(array $o): void
{
    $id   = (int)$o['id'];
    $pre  = (string)$o['praefix'] . '-' . $id;
    $seite = (string)($o['seite'] ?? 'einstellungen.php?t=rettungsmittel');
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
        if (!empty($o['bearbeiten_href'])) {
            $eintraege[] = ['text' => 'Bearbeiten', 'symbol' => 'stift',
                            'href' => (string)$o['bearbeiten_href']];
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

    ui_zeile([
        'text'      => (string)$o['name'],
        'klein'     => (string)($o['klein'] ?? ''),
        'plaketten' => $plaketten,
        'aktionen'  => $eintraege
            ? ui_zeilenaktionen(['titel' => (string)$o['name'], 'eintraege' => $eintraege])
            : '',
    ]);
}

/**
 * Das Anlegen-Formular einer Stammdatenliste.
 *
 * Ein Namensfeld, ein Knopf, bei Bearbeitung ein Abbrechen daneben. Die
 * Listen mit mehr Feldern (Rettungsmittel, Zielkliniken) bauen ihr Formular
 * selbst.
 *
 * $o: seite, anker, action, base_id, titel_neu, titel_bearbeiten, bearbeitet,
 *     label, platzhalter, felder_versteckt
 */
function sd_form(array $o): void
{
    $bearb = $o['bearbeitet'] ?? null;
    $seite = (string)($o['seite'] ?? 'einstellungen.php?t=rettungsmittel');
    echo '<div class="listen-form">' . "\n";
    echo '  <h3 class="listen-form-titel">'
       . ui_e($bearb ? (string)$o['titel_bearbeiten'] : (string)$o['titel_neu']) . "</h3>\n";
    echo '  <form method="post" action="' . ui_e($seite . '#' . (string)$o['anker']) . '">' . "\n";
    echo '    ' . csrf_field()
       . '<input type="hidden" name="action" value="' . ui_e((string)$o['action']) . '">'
       . '<input type="hidden" name="id" value="' . ($bearb ? (int)$bearb['id'] : 0) . '">'
       . '<input type="hidden" name="base_id" value="' . (int)$o['base_id'] . '">'
       . (string)($o['felder_versteckt'] ?? '') . "\n";
    echo '    <div class="listen-form-felder">' . "\n";
    /* Die Kennung trägt eine laufende Nummer: Das Besatzungsformular steht
       je ROLLE einmal, alle mit demselben Anker — ohne sie hätten vier
       Rollen vier Felder gleicher Kennung, und das Label zeigte auf das
       erste (F-P3-AK). */
    static $lfdForm = 0;
    $lfdForm++;
    ui_feld(['label' => (string)($o['label'] ?? 'Name'), 'name' => 'name',
             'id' => 'sdf-' . preg_replace('/[^\w-]/', '-', (string)$o['anker']) . '-' . $lfdForm,
             'pflicht' => true, 'platzhalter' => (string)($o['platzhalter'] ?? ''),
             'wert' => (string)($bearb['name'] ?? ''), 'attr' => ' maxlength="120"']);
    echo "    </div>\n";
    echo '    <div class="listen-form-fuss">'
       . ui_knopf(['text' => $bearb ? 'Änderung speichern' : 'Hinzufügen', 'art' => 'primaer'])
       . ($bearb ? ui_knopf(['text' => 'Abbrechen', 'art' => 'leise', 'href' => $seite]) : '')
       . "</div>\n";
    echo "  </form>\n</div>\n";
}
