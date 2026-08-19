<?php
declare(strict_types=1);
/**
 * Zusatzfelder fuer Einsaetze — EINE zentrale Definition.
 *
 * Formular (einsatz_form.php), API (api/mission.php, api/day.php) und Anzeige
 * lesen alle diese Liste. Neues Feld = 1 Migration (update.php) + 1 Eintrag
 * hier. Alle Felder sind echte DB-Spalten (spaeter durchsuchbar) — mit EINER
 * benannten Ausnahme, siehe 'store' weiter unten.
 *
 * Typen:
 *   'text' | 'textarea' | 'number'  einfache Eingaben ('max' = Zeichen)
 *   'checkbox'                      Haken (0/1); optional 'children':
 *                                   Unterfelder, nur sichtbar/gespeichert,
 *                                   wenn der Haken gesetzt ist
 *   'loc'                           ORTSFELD (Web 6.1.0): Bezeichnung wie
 *                                   'text' — samt 'max', 'placeholder' und
 *                                   'suggest_src' —, dazu ZWEI optionale
 *                                   Koordinatenspalten, benannt in 'lat_col'
 *                                   und 'lon_col'. Die Bedienung uebernimmt
 *                                   assets/ortsfeld.js (Chip, Adresssuche,
 *                                   Plus Code); die Koordinaten sind ueberall
 *                                   FREIWILLIG (E39) — ohne sie bleibt es ein
 *                                   reines Textfeld ohne Pin und ohne Linie.
 *                                   Umgekehrt gilt wie beim Einsatzort:
 *                                   Koordinaten ohne Bezeichnung werden
 *                                   abgewiesen.
 *   'select'                        Dropdown; 'options' = feste Werteliste
 *                                   ODER 'options_src':
 *                                     'bw_units'   Bergwacht-Bereitschaften
 *                                     'crew:<rolle>'  Besatzungs-Vorbelegungen
 *                                                  der Rolle aus crew_presets;
 *                                                  Rollenkennungen: CREW_ROLES
 *                                                  in db.php
 *                                   Beide liefern persoenliche UND zentrale
 *                                   Stammdaten DES STANDORTS, der am Diensttag
 *                                   hinterlegt ist (E15) — es gibt keine
 *                                   standortuebergreifenden Stammdaten mehr.
 *                                   Freitext bleibt speicherbar (Stammdaten
 *                                   sind aenderbar), ein nicht mehr gelisteter
 *                                   Altwert wird beim Rendern ergaenzt statt
 *                                   stillschweigend verworfen
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
 *   'role_gate' => Rollenkennung aus CREW_ROLES (db.php)
 *                                   Feld nur zeigen, wenn der DIENSTTAG diese
 *                                   Rolle anbietet — also eine Zeile in
 *                                   `day_crew` dafuer traegt (E8). Bis Web
 *                                   5.10.0 wurde stattdessen der Hubschrauber
 *                                   befragt; seit dem Einfrieren des
 *                                   Rollensatzes ist der Diensttag die Quelle,
 *                                   und eine spaetere Aenderung am
 *                                   Rettungsmittel aendert an ihm nichts (A4).
 *                                   Das Feld wird trotzdem gerendert und nur
 *                                   versteckt — sonst sendet der Browser es
 *                                   nicht mit und die Speicherlogik wuerde
 *                                   einen vorhandenen Wert loeschen. Ein
 *                                   bereits belegtes Feld bleibt darum immer
 *                                   sichtbar. Ein neutraler Diensttag hat keine
 *                                   Rollen (E26): Dann sind alle
 *                                   rollengebundenen Felder verborgen, ausser
 *                                   den belegten.
 *
 *   'kind_gate' => 'air' | 'ground' Feld nur zeigen, wenn der DIENSTTAG diese
 *                                   Art hat (`days.kind`, eingefroren beim
 *                                   Zuordnen). Verhalten exakt wie 'role_gate':
 *                                   rendern und verstecken, nie weglassen; ein
 *                                   bereits belegtes Feld bleibt sichtbar. Ist
 *                                   die Art NULL (neutraler Diensttag, E26),
 *                                   greift KEIN artgebundenes Feld — beide
 *                                   Artenbloecke bleiben verborgen.
 *
 *                                   DERZEIT NUTZT KEIN FELD DIESEN SCHLUESSEL,
 *                                   und das ist kein Versehen: Die einzigen
 *                                   artabhaengigen Einsatzfelder sind Winde und
 *                                   Bergwacht, und die haengen an der
 *                                   FAEHIGKEIT (siehe 'cap_gate'), nicht an der
 *                                   Art. Der Schluessel steht bereit, damit ein
 *                                   spaeteres artabhaengiges Feld nicht wieder
 *                                   eine eigene Sonderbehandlung im Formular
 *                                   braucht — die Auswertung liegt in
 *                                   mf_gates_erfuellt() neben den beiden
 *                                   anderen Filtern.
 *
 *   'cap_gate' => Faehigkeit        Feld nur zeigen, wenn der DIENSTTAG diese
 *                                   Faehigkeit traegt (`day_capabilities`,
 *                                   eingefroren beim Zuordnen, E29). Kennungen:
 *                                   VEHICLE_CAPABILITIES in db.php.
 *
 *                                   ERSETZT DIE ARTPRUEFUNG VOLLSTAENDIG (E29):
 *                                   Faehigkeiten kommen ausschliesslich an
 *                                   luftgebundenen Rettungsmitteln vor, ein
 *                                   zusaetzliches 'kind_gate' waere also nur
 *                                   eine zweite Formulierung derselben Aussage
 *                                   — und die erste, die beim naechsten Umbau
 *                                   vergessen wird.
 *
 *                                   Der Schluessel ist zugleich der Grund,
 *                                   warum ein bodengebundener Dienst keine
 *                                   Windenfelder zeigt (A3) und warum ein
 *                                   spaeter abgewaehlter Windenhaken alte
 *                                   Einsaetze nicht beschaedigt (A13e): Gefragt
 *                                   wird der Diensttag, nicht das heutige
 *                                   Rettungsmittel.
 *
 *   'show_if' => [                  WERTABHAENGIGES UNTERFELD unter einem
 *      'field'  => '<elternspalte>',  'select'. Nur zeigen und nur speichern,
 *      'not_in' => ['<wert>', …],     wenn das uebergeordnete Auswahlfeld
 *   ]                                KEINEN der genannten Werte hat.
 *
 *                                   Bis Web 6.0.0 gab es bedingte Unterfelder
 *                                   ausschliesslich unter Checkboxen; unter
 *                                   einem 'select' wurden Kinder immer
 *                                   gerendert und immer gespeichert. Anders als
 *                                   bei den Gates wird hier GELEERT statt nur
 *                                   versteckt: Ein Transportziel hinter
 *                                   „Ambulant" waere kein schuetzenswerter
 *                                   Bestand, sondern ein Widerspruch in den
 *                                   Daten (A5). Die Aenderung ist sichtbar —
 *                                   das Feld verschwindet vor dem Speichern.
 *
 *   'gruppe' => Kennung             FORMULARGRUPPE (Web 7.0.0). Nur an Feldern
 *                                   der obersten Ebene; Unterfelder folgen
 *                                   ihrem Elternfeld. Die Gruppen, ihre
 *                                   Ueberschriften und ihre Reihenfolge stehen
 *                                   in einsatz_form.php ($GRUPPEN) — hier steht
 *                                   nur, WOHIN das Feld gehoert. Ohne Angabe
 *                                   landet es in der letzten Gruppe, statt
 *                                   unsichtbar zu werden.
 *
 *   'nebeneinander' => true         Das Feld teilt sich eine Zeile mit seinen
 *                                   unmittelbaren Nachbarn derselben Gruppe,
 *                                   die dasselbe verlangen (Web 7.0.0). Gedacht
 *                                   fuer kurze Haken wie Sekundaertransport und
 *                                   Fehleinsatz — zwei Woerter, die
 *                                   untereinander zwei Zeilen kosten und
 *                                   nebeneinander eine.
 *
 *   'vorbelegt_bei' => [            VORBELEGUNG EINER CHECKBOX, abhaengig vom
 *      '<spalte>' => '<wert>',      Wert eines ANDEREN Feldes (Web 7.0.0). Der
 *   ]                               Haken wird gesetzt, sobald das genannte
 *                                   Feld den genannten Wert annimmt — aber NUR,
 *                                   solange niemand ihn von Hand angefasst hat.
 *                                   Eine ausdrueckliche Entscheidung schlaegt
 *                                   die Vorbelegung immer, und zwar dauerhaft:
 *                                   Sonst haette das Formular eine Meinung, die
 *                                   sich nicht abstellen laesst.
 *                                   Wirkt ausschliesslich im Browser und
 *                                   ausschliesslich auf ein `change`-Ereignis
 *                                   des genannten Feldes hin. Beim blossen
 *                                   Laden der Seite passiert nichts — ein
 *                                   gespeicherter Wert bleibt also unangetastet,
 *                                   auch beim Bearbeiten.
 *
 *   'such_label'                    nur bei 'loc': Beschriftung des
 *                                   Suchfeldes neben der Bezeichnung. Ohne
 *                                   Angabe „Koordinaten (optional)".
 *
 *   'store' => 'crew'               DIE EINE AUSNAHME von "alle Felder sind
 *                                   Spalten". Der Wert liegt nicht in
 *                                   `missions`, sondern als Zeile in
 *                                   `mission_crew (mission_id, role_code,
 *                                   name)`. Grund: Die Besatzung ist seit Web
 *                                   6.0.0 normalisiert (E7) — feste
 *                                   Rollenspalten tragen mit zwei
 *                                   Rettungsmittelarten nicht mehr. Wer den
 *                                   Katalog auswertet, MUSS diesen Schluessel
 *                                   beachten: Ein Feldname mit 'store' darf
 *                                   nicht in ein SELECT, INSERT oder UPDATE auf
 *                                   `missions` geraten. 'role_code' nennt die
 *                                   zugehoerige Rolle.
 *   'day_label' => 'Winde'          Spaltentitel (sonst 'label'). Wird
 *                                   unmaskiert ausgegeben und darf deshalb
 *                                   Auszeichnung enthalten (`&shy;`, `<br>`)
 *   'placeholder'
 *   'suggest_src'                   nur bei 'text': Quelle der <datalist>-
 *                                   Vorschlaege; Freitext bleibt moeglich.
 *                                     'transport_dests'  Stammdaten-Tabelle
 *                                     'crew:<rolle>'     Besatzungs-Vorbelegungen
 *                                                        der Rolle (CREW_ROLES)
 *                                   Beide liefern persoenliche UND zentrale
 *                                   Eintraege des Standorts. Unterschied zu 'options_src':
 *                                   Dort ist die Liste die Auswahl, hier nur
 *                                   ein Vorschlag — ein Wert ausserhalb der
 *                                   Liste bleibt erhalten, weil er gar nicht
 *                                   erst geprueft wird
 *
 * Der Einsatzort (Adresse + Koordinaten, Photon-Autocomplete) ist bewusst
 * KEIN Eintrag hier — er liegt Ende-zu-Ende-verschlüsselt im pat_blob.
 *
 * Der ABFAHRTORT (Web 6.1.0, E34) ebenso wenig, aus zwei Gruenden: Seine
 * Auswahl speichert eine REGEL (`missions.start_src` — 'base', 'prev_site',
 * 'prev_dest', 'manual'), deren Beschriftungen nicht ihre Datenbankwerte sind,
 * und der manuelle Ort liegt wie der Einsatzort verschluesselt im pat_blob
 * (Schluessel `start`). Beides kann der Katalog nicht — er kennt nur Felder,
 * deren Anzeigewert zugleich der gespeicherte Wert ist. Das Formular behandelt
 * ihn deshalb ausdruecklich, unmittelbar unter dem Einsatzort
 * (einsatz_form.php).
 *
 * Die Einsatznummer ist bewusst KEIN Eintrag hier — sie liegt seit Web 2.9.0
 * Ende-zu-Ende-verschlüsselt im pat_blob, weil sich über sie bei der
 * Leitstelle die betroffene Person ermitteln lässt.
 */

/* ---- Besatzungsfelder aus dem Rollenkatalog erzeugen (E4) -----------------
 *
 * Bis Web 5.10.0 standen hier fuenf Eintraege ausgeschrieben — je einer fuer
 * Pilot 1, Pilot 2, HEMS-TC, Flugretter und Sonstige. Mit den bodengebundenen
 * Rollen waeren es sieben geworden, und jede neue Rolle haette diese Liste,
 * das Formular, den Export und die Importprofile gleichzeitig anfassen muessen.
 * Die Quelle ist jetzt CREW_ROLES in db.php; wer eine Rolle ergaenzt, ergaenzt
 * sie DORT und nirgends sonst.
 *
 * Der Feldname bleibt `crew_<rolle>` — er ist der Name des Formularfeldes und
 * die Spaltenbezeichnung in Export und Import. Gespeichert wird der Wert
 * dagegen in `mission_crew` unter `role_code` (Schluessel 'store', siehe oben).
 *
 * Die Reihenfolge ist die des Katalogs, ueber alle Arten hinweg. Welche Felder
 * SICHTBAR sind, entscheidet 'role_gate' anhand des Diensttags — ein
 * bodengebundener Dienst zeigt Fahrer, Praktikant und Sonstige und sonst nichts
 * (A3). Eine Aufteilung des Katalogs nach Art waere hier also nicht nur
 * unnoetig, sondern falsch: Ein Einsatz kann eine belegte Rolle tragen, die die
 * Art des Tages nicht vorsieht, und die muss sichtbar bleiben.
 */
$mf_crew_kinder = [];
foreach (CREW_ROLES as $mf_code => $mf_rolle) {
    $mf_crew_kinder['crew_' . $mf_code] = [
        // Textfelder mit Vorschlagsliste, nicht Auswahlfelder (Web 5.5.0,
        // Entscheidung E8): Wer aushilft, steht oft nicht in den Stammdaten —
        // eine reine Auswahl liess genau diesen Fall nicht dokumentieren. Die
        // Vorbelegungen der Rolle bleiben als Vorschlaege erhalten, Freitext
        // ist zusaetzlich moeglich.
        'label'       => $mf_rolle['label'],
        'type'        => 'text',
        'max'         => 120,
        'suggest_src' => 'crew:' . $mf_code,
        'role_gate'   => $mf_code,
        'store'       => 'crew',
        'role_code'   => $mf_code,
    ];
}

/* ---- Reihenfolge = FORMULARREIHENFOLGE (Web 7.0.0) ------------------------
 *
 * Die Liste stand bis Web 6.3.0 in der Reihenfolge, in der die Felder
 * entstanden sind: Transport zuerst, weil er als letztes Grossfeld dazukam,
 * Notizen zuletzt, weil sie schon immer unten standen. Im Formular las sich
 * das quer zur Arbeit — die Transportart stand ueber dem Einsatzort, und
 * „Weitere Rettungsmittel" hing zwischen Bergwacht und Besatzung.
 *
 * Jetzt folgt die Liste dem Ablauf des Einsatzes, und JEDES FELD NENNT SEINE
 * GRUPPE ('gruppe'). Das Formular rendert Gruppe fuer Gruppe mit eigener
 * Ueberschrift; welche Felder in welche gehoert, entscheidet allein dieser
 * Schluessel — nicht eine zweite Liste im Formular, die beim naechsten Feld
 * vergessen wuerde.
 *
 * Die Gruppen und ihre Reihenfolge stehen in einsatz_form.php ($GRUPPEN). Ein
 * Feld ohne 'gruppe' landet in der letzten Gruppe („Weitere Angaben") — es
 * verschwindet also nicht, wenn jemand den Schluessel vergisst.
 *
 * WAS DIE REIHENFOLGE SONST NOCH STEUERT: die Spalten der Tagesuebersicht
 * (mf_tagesspalten) und die Spaltenfolge im Export. Beide ziehen von selbst
 * nach; die Tagesuebersicht zeigt seither Sekundaertransport, Bergwacht,
 * Winde in dieser Folge. Der Import ordnet ueber SPALTENNAMEN zu und ist
 * davon unberuehrt.
 */
return [
    /* ---- Gruppe „Einsatz" -------------------------------------------------
     * Zwei Haken, die den Einsatz als Ganzes kennzeichnen. Sie stehen im
     * Formular NEBENEINANDER ueber dem Einsatzort: Beides sind Aussagen ueber
     * die Art des Auftrags, und beide sind mit einem Blick zu erfassen. */
    'secondary' => [
        'label' => 'Sekundärtransport', 'type' => 'checkbox',
        'gruppe' => 'einsatz', 'nebeneinander' => true,
        // Harter Umbruch statt weichem Trennstrich: Die Zeitraum- und die
        // Suchtabelle (assets/missiontable.js) beschriften diese Spalte
        // ebenso. Solange 'day_col' wirkungslos war, fiel der Unterschied
        // nicht auf (Web 5.4.0).
        'day_col' => 'check', 'day_label' => 'Sekundär<br>Transport',
    ],
    /* FEHLEINSATZ (E17). EIN Haken ohne Unterauswahl — ausdruecklich so
     * entschieden (Abschnitt 7): Ob storniert, abgebrochen oder von vornherein
     * gegenstandslos, ist eine Unterscheidung, die im Nachhinein selten
     * verlaesslich zu treffen ist. */
    /* KEINE Spalte in der Tagestabelle. Der Haken steht im Einsatz selbst und
     * sonst nirgends: Er ist selten gesetzt, und eine Spalte voller leerer
     * Zellen kostet auf der Tagesuebersicht Breite, ohne etwas zu sagen.
     * Auswerten laesst er sich weiterhin — die Zeitraumuebersicht zaehlt ihn in
     * der Kachel "Fehleinsaetze", und die Suche filtert danach. Beide fuehren
     * ihre Spalte datengetrieben (missiontable.js, `nurWenn`), zeigen sie also
     * nur, wenn im Bestand tatsaechlich Fehleinsaetze liegen. */
    'false_alarm' => [
        'label' => 'Fehleinsatz / Storno / Abbruch', 'type' => 'checkbox',
        'gruppe' => 'einsatz', 'nebeneinander' => true,
    ],

    /* ---- Gruppe „Transport" -----------------------------------------------
     *
     * TRANSPORTART (E17). Sie ist das ordnende Feld der Einsatzdokumentation
     * geworden: Zielklinik, Schockraum und NA-Begleitung haengen daran und
     * entfallen bei „Ambulant" — also dann, wenn die Patientin nicht
     * transportiert wurde.
     *
     * SIE HEISST SEIT WEB 7.0.0 „TRANSPORTART" und nicht mehr „Transport".
     * „Transport" war zugleich der Name der Gruppe, in der sie steht, und der
     * Name eines Feldes darin — und im Altbestand ausserdem der Spaltentitel
     * fuer die ZIELKLINIK (siehe assets/import_profiles.js). Drei Bedeutungen
     * fuer ein Wort sind zwei zu viel.
     *
     * KEIN 'kind_gate'. „Luft" ist auch an einem bodengebundenen Dienst ein
     * gueltiger Wert: Das NEF uebergibt an den Hubschrauber, und wie
     * transportiert wurde, ist eine Aussage ueber den EINSATZ, nicht ueber das
     * eigene Rettungsmittel. */
    'transport_mode' => [
        'label' => 'Transportart', 'type' => 'select', 'gruppe' => 'transport',
        /* WERT LINKS, BESCHRIFTUNG RECHTS. Die Spalte ist ein
         * `ENUM('air','ground','ambulant')` — sie stammt aus der Migration der
         * Web 6.0.0 und ist englisch benannt wie alle uebrigen Spalten. Eine
         * Liste ['Luft','Boden','Ambulant'] haette die Beschriftung in die
         * Spalte geschrieben und dort still abgeschnitten. Beide Schreibweisen
         * loest mf_optionen() auf (mission_fields_lib.php). */
        'options' => ['air' => 'Luft', 'ground' => 'Boden', 'ambulant' => 'Ambulant'],
        'children' => [
            'na_escort' => [
                'label' => 'NA-Begleitung', 'type' => 'checkbox',
                // 'not_in' nennt den GESPEICHERTEN Wert, nicht die Beschriftung.
                'show_if' => ['field' => 'transport_mode', 'not_in' => ['ambulant']],
                /* VORBELEGT BEI LUFT (Web 7.0.0). Ein luftgebundener Transport
                 * ohne Notarzt an Bord ist die Ausnahme, nicht die Regel — der
                 * Haken war damit der am haeufigsten vergessene des Formulars.
                 * Gesetzt wird er NUR, solange niemand ihn von Hand angefasst
                 * hat (assets/forms.js kennt die Regel nicht; sie steht im
                 * Skript des Formulars). Eine ausdrueckliche Entscheidung
                 * schlaegt die Vorbelegung immer.
                 * Sie greift nur, wenn die Transportart GERADE UMGESTELLT wird
                 * — nicht beim Laden. Deshalb gilt sie auch beim Bearbeiten,
                 * ohne dort je einen gespeicherten Wert zu ueberschreiben. */
                'vorbelegt_bei' => ['transport_mode' => 'air'],
            ],
            /* ZIELKLINIK ALS ORTSFELD (E37/E38/E40). Freitext und
             * Vorschlagsliste bleiben unveraendert; neu ist die optionale
             * Koordinate. Sie liegt im KLARTEXT (`dest_lat`/`dest_lon`) wie der
             * Name selbst — ihr Pin ist damit ohne Freischalten sichtbar,
             * anders als der Einsatzort.
             *
             * EINGEFROREN AM EINSATZ, nicht ueber den Namen aufgeloest: Das
             * Feld ist Freitext, und eine Aufloesung ueber Namensgleichheit
             * verloere die Koordinate, sobald jemand den Stammdatensatz
             * umbenennt (A13p). */
            'transport_dest' => [
                'label' => 'Transportziel', 'type' => 'loc', 'max' => 190,
                'placeholder' => 'z. B. Klinikum Kempten',
                'suggest_src' => 'transport_dests',
                'lat_col' => 'dest_lat', 'lon_col' => 'dest_lon',
                /* Beschriftung des Suchfeldes daneben. Es hiess „Koordinaten
                 * (optional)" — was es einsammelt, sind aber laengst keine
                 * Zahlen mehr, sondern eine Adresse, ein Plus Code oder ein
                 * Stammdatentreffer. Die Koordinate ist das ERGEBNIS und steht
                 * danach als Merkfeld darunter (Web 7.0.0). */
                'such_label' => 'Lokalisation Transportziel (optional)',
                'show_if' => ['field' => 'transport_mode', 'not_in' => ['ambulant']],
                'children' => [
                    'schockraum' => [ 'label' => 'Schockraum', 'type' => 'checkbox' ],
                ],
            ],
        ],
    ],

    /* ---- Gruppe „Bergrettung" ---------------------------------------------
     * Bergwacht steht vor der Winde: Erst wer beteiligt war, dann womit. Beide
     * haengen an einer FAEHIGKEIT des Diensttags ('cap_gate') und sind an einem
     * bodengebundenen Dienst gar nicht zu sehen — die ganze Gruppe faellt dann
     * weg (einsatz_form.php). */
    'bergwacht' => [
        'label' => 'Bergwacht', 'type' => 'checkbox', 'gruppe' => 'bergrettung',
        'cap_gate' => 'bergwacht',
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
    'winch' => [
        'label' => 'Windeneinsatz', 'type' => 'checkbox', 'gruppe' => 'bergrettung',
        'cap_gate' => 'winch',
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

    /* ---- Gruppe „Weitere Rettungsmittel" ----------------------------------
     * Wer sonst noch am Einsatz war — Fahrzeuge und Personen. Beides gehoert
     * zusammen und stand bisher an zwei Stellen des Formulars. */
    'other_resources' => [
        // Sonderfall: nicht als Spalte in missions, sondern als eigene Zeilen
        // in mission_resources (einzeln entfernbar). Siehe einsatz_form.php.
        'label' => 'Weitere Rettungsmittel', 'type' => 'resources',
        'gruppe' => 'mittel',
    ],
    'other_ema' => [
        /* „Weiterer Notarzt" statt „Anderer Notarzt" (Web 7.0.0). „Anderer"
         * las sich, als sei der eigene ersetzt worden; gemeint ist ein
         * zusaetzlicher, der ebenfalls am Einsatz war — dieselbe Logik wie bei
         * „Weitere Rettungsmittel" direkt darueber. */
        'label' => 'Weiterer Notarzt', 'type' => 'text', 'max' => 190,
        'gruppe' => 'mittel',
    ],

    /* ---- Gruppe „Abweichende Besatzung" ----------------------------------- */
    'crew_override' => [
        // Abweichende Besatzung fuer genau diesen Einsatz (fachlicher Anlass:
        // Pilotenwechsel waehrend eines Diensttags). Ohne Haken gilt die
        // Tagesbesatzung aus `day_crew` — in `mission_crew` steht dann keine
        // Zeile, es wird also nichts doppelt gespeichert. Die effektive
        // Besatzung (COALESCE-Regel, jetzt ueber zwei TABELLEN statt zwei
        // Spaltensaetze) liefert api/mission.php weiterhin als 'crew_effektiv'.
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
        'gruppe' => 'besatzung',
        'children' => $mf_crew_kinder,
    ],

    /* ---- Gruppe „Notizen" ------------------------------------------------- */
    'notes' => [
        'label' => 'Notizen', 'type' => 'textarea', 'max' => 2000,
        'gruppe' => 'notizen',
        'placeholder' => 'Freitext (keine Patientendaten!)',
    ],
];
