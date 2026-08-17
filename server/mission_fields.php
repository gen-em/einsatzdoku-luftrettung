<?php
declare(strict_types=1);
/**
 * Zusatzfelder fuer Einsaetze — EINE zentrale Definition.
 *
 * Formular (einsatz_form.php), API (api/mission.php, api/day.php) und Anzeige
 * lesen alle diese Liste. Neues Feld = 1 Migration (update.php) + 1 Eintrag
 * hier. Alle Felder sind echte DB-Spalten (spaeter durchsuchbar).
 *
 * Typen:
 *   'text' | 'textarea' | 'number'  einfache Eingaben ('max' = Zeichen)
 *   'checkbox'                      Haken (0/1); optional 'children':
 *                                   Unterfelder, nur sichtbar/gespeichert,
 *                                   wenn der Haken gesetzt ist
 *   'select'                        Dropdown; 'options' = feste Werteliste
 *                                   ODER 'options_src':
 *                                     'bw_units'   Bergwacht-Bereitschaften
 *                                     'crew:<rolle>'  Besatzungs-Vorbelegungen
 *                                                  der Rolle (p1|p2|hems|fr|
 *                                                  other) aus crew_presets
 *                                   Beide liefern persoenliche UND zentrale
 *                                   Stammdaten; Freitext bleibt speicherbar
 *                                   (Stammdaten sind aenderbar), ein nicht
 *                                   mehr gelisteter Altwert wird beim Rendern
 *                                   ergaenzt statt stillschweigend verworfen
 *
 * Weitere Schluessel:
 *   'day_col'   => true|'check'     Spalte in der Tagestabelle (Text bzw. ✓).
 *                                   Seit Web 5.4.0 generisch ausgewertet:
 *                                   mf_tagesspalten() in mission_fields_lib.php
 *                                   ist die EINZIGE Stelle, die diesen
 *                                   Schluessel liest. api/day.php liefert die
 *                                   Spalte daraufhin von selbst mit, index.php
 *                                   zeigt und sortiert sie. Ein neues Feld
 *                                   braucht hier einen Eintrag und sonst
 *                                   nichts — hoechstens eine Spaltenbreite in
 *                                   style.css (Klasse `c-dc-<spalte>`).
 *                                   Gilt auch fuer Unterfelder.
 *
 *   'role_gate' => 'p1'|'p2'|'hems'|'fr'|'other'
 *                                   Feld nur zeigen, wenn der Hubschrauber des
 *                                   Flugtags diese Rolle vorsieht. Es wird
 *                                   trotzdem gerendert und nur versteckt —
 *                                   sonst sendet der Browser es nicht mit und
 *                                   die Speicherlogik wuerde einen vorhandenen
 *                                   Wert loeschen. Ein bereits belegtes Feld
 *                                   bleibt darum immer sichtbar.
 *   'day_label' => 'Winde'          Spaltentitel (sonst 'label'). Wird
 *                                   unmaskiert ausgegeben und darf deshalb
 *                                   Auszeichnung enthalten (`&shy;`, `<br>`)
 *   'placeholder'
 *   'suggest_src'                   nur bei 'text': Quelle der <datalist>-
 *                                   Vorschlaege; Freitext bleibt moeglich.
 *                                     'transport_dests'  Stammdaten-Tabelle
 *                                     'crew:<rolle>'     Besatzungs-Vorbelegungen
 *                                                        der Rolle (p1|p2|hems|
 *                                                        fr|other)
 *                                   Beide liefern persoenliche UND zentrale
 *                                   Eintraege. Unterschied zu 'options_src':
 *                                   Dort ist die Liste die Auswahl, hier nur
 *                                   ein Vorschlag — ein Wert ausserhalb der
 *                                   Liste bleibt erhalten, weil er gar nicht
 *                                   erst geprueft wird
 *
 * Der Einsatzort (Adresse + Koordinaten, Photon-Autocomplete) ist bewusst
 * KEIN Eintrag hier — er liegt Ende-zu-Ende-verschlüsselt im pat_blob.
 *
 * Die Einsatznummer ist bewusst KEIN Eintrag hier — sie liegt seit Web 2.9.0
 * Ende-zu-Ende-verschlüsselt im pat_blob, weil sich über sie bei der
 * Leitstelle die betroffene Person ermitteln lässt.
 */
return [
    'transport_dest' => [
        'label' => 'Transportziel', 'type' => 'text', 'max' => 190,
        'placeholder' => 'z. B. Klinikum Kempten',
        'suggest_src' => 'transport_dests',
        'children' => [
            'schockraum' => [ 'label' => 'Schockraum', 'type' => 'checkbox' ],
        ],
    ],
    'winch' => [
        'label' => 'Windeneinsatz', 'type' => 'checkbox',
        'day_col' => 'check', 'day_label' => 'Winde',
        'children' => [
            'winch_cycles' => [
                'label' => 'Cycles', 'type' => 'select',
                'options' => ['0','1','2','3','4','5','6','7','8'],
            ],
            'winch_cycles_pat' => [
                'label' => 'Cycles mit Patient', 'type' => 'select',
                'options' => ['0','1','2','3','4','5','6','7','8'],
            ],
            'winch_airload' => [ 'label' => 'Luftverladung', 'type' => 'checkbox' ],
        ],
    ],
    'bergwacht' => [
        'label' => 'Bergwacht', 'type' => 'checkbox',
        'day_col' => 'check', 'day_label' => 'Bergwacht',
        'children' => [
            'bw_unit' => [
                'label' => 'Bereitschaft', 'type' => 'select',
                'options_src' => 'bw_units',
            ],
            'bw_info' => [
                'label' => 'Namen / Infos', 'type' => 'text', 'max' => 190,
            ],
        ],
    ],

    'secondary' => [
        'label' => 'Sekundärtransport', 'type' => 'checkbox',
        // Harter Umbruch statt weichem Trennstrich: Die Zeitraum- und die
        // Suchtabelle (assets/missiontable.js) beschriften diese Spalte
        // ebenso. Solange 'day_col' wirkungslos war, fiel der Unterschied
        // nicht auf (Web 5.4.0).
        'day_col' => 'check', 'day_label' => 'Sekundär<br>Transport',
    ],
    'other_ema' => [
        'label' => 'Anderer Notarzt', 'type' => 'text', 'max' => 190,
    ],
    'other_resources' => [
        // Sonderfall: nicht als Spalte in missions, sondern als eigene Zeilen
        // in mission_resources (einzeln entfernbar). Siehe einsatz_form.php.
        'label' => 'Weitere Rettungsmittel', 'type' => 'resources',
    ],
    'crew_override' => [
        // Abweichende Besatzung fuer genau diesen Einsatz (fachlicher Anlass:
        // Pilotenwechsel waehrend eines Flugtags). Ohne Haken gilt die
        // Tagescrew aus days.crew_* — die Unterfelder bleiben dann NULL, es
        // wird also nichts doppelt gespeichert. Die effektive Besatzung
        // (COALESCE-Regel) liefert api/mission.php als 'crew_effektiv'.
        //
        // KEINE Spalte in der Tagestabelle (Web 5.10.0). Sie war seit Web 5.4.0
        // zu sehen — der Eintrag 'day_col' hier hatte davor keine Wirkung, weil
        // die Spalten hartkodiert waren (Backlog Nr. 10). Im taeglichen Gebrauch
        // trug sie nichts bei: Der Haken steht bei den allermeisten Taegen in
        // keiner einzigen Zeile und kostete trotzdem Breite in einer Tabelle,
        // die auf schmalen Geraeten ohnehin knapp ist. Wo die abweichende
        // Besatzung wirklich interessiert, steht sie vollstaendig — in der
        // Einsatzansicht unter „Besatzung", mit „(abw.)" an der betroffenen
        // Rolle. Das Feld selbst bleibt unveraendert erhalten, ebenso im Export.
        'label' => 'Abweichende Besatzung', 'type' => 'checkbox',
        'children' => [
            // Textfelder mit Vorschlagsliste, nicht Auswahlfelder (Web 5.5.0,
            // Entscheidung E8): Wer aushilft, steht oft nicht in den
            // Stammdaten — eine reine Auswahl liess genau diesen Fall nicht
            // dokumentieren. Die Vorbelegungen der Rolle bleiben als
            // Vorschlaege erhalten, Freitext ist zusaetzlich moeglich.
            'crew_p1'    => ['label' => 'Pilot 1',    'type' => 'text', 'suggest_src' => 'crew:p1',    'max' => 120, 'role_gate' => 'p1'],
            'crew_p2'    => ['label' => 'Pilot 2',    'type' => 'text', 'suggest_src' => 'crew:p2',    'max' => 120, 'role_gate' => 'p2'],
            'crew_hems'  => ['label' => 'HEMS-TC',    'type' => 'text', 'suggest_src' => 'crew:hems',  'max' => 120, 'role_gate' => 'hems'],
            'crew_fr'    => ['label' => 'Flugretter', 'type' => 'text', 'suggest_src' => 'crew:fr',    'max' => 120, 'role_gate' => 'fr'],
            'crew_other' => ['label' => 'Sonstige',   'type' => 'text', 'suggest_src' => 'crew:other', 'max' => 120, 'role_gate' => 'other'],
        ],
    ],
    'notes' => [
        'label' => 'Notizen', 'type' => 'textarea', 'max' => 2000,
        'placeholder' => 'Freitext (keine Patientendaten!)',
    ],
];
