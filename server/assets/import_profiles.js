/**
 * Importprofile — deklarative Beschreibung fremder Dateiformate.
 *
 * Ein Profil sagt der Pipeline in import.js, WO die Daten stehen (Blatt,
 * Kopfzeile) und WIE jede Quellspalte in ein Zielfeld uebersetzt wird. Ein
 * weiteres Format zu unterstuetzen heisst deshalb: hier einen Eintrag
 * ergaenzen — an der Pipeline selbst ist nichts zu aendern.
 *
 * Aufbau eines Profils:
 *   id, label        Kennung und Anzeigename
 *   sheet            Blattindex (0-basiert) oder Blattname
 *   headerRow        'auto' = erste Zeile, die mindestens minHeaderMatch der
 *                    erwarteten Ueberschriften enthaelt; sonst 1-basierte Nr.
 *   expectedHeaders  erwartete Spaltenueberschriften (Vergleich ohne
 *                    Gross-/Kleinschreibung und ohne Randleerzeichen)
 *   minHeaderMatch   ab wie vielen Treffern das Profil als passend gilt
 *   params           Angaben, die die Datei nicht enthaelt und die vor dem
 *                    Verarbeiten erfragt werden (Typ, Beschriftung,
 *                    Vorschlagsfunktion)
 *   columns          Quellueberschrift -> { target, parse, required,
 *                    sensitive }
 *   dedupeKey        Reihenfolge der Merkmale fuer die Duplikatpruefung
 *   explicitCrew     true = die Datei nennt Tages- und Einsatzbesatzung
 *                    getrennt; gruppiere() rechnet dann NICHT selbst aus,
 *                    welche Rolle abweicht (siehe import.js)
 *   warning          Text, der vor dem Import angezeigt wird
 *   archiveMember    Dateiname innerhalb eines ZIP-Archivs, den dieses Profil
 *                    liest
 *
 * target-Werte, die die Pipeline kennt:
 *   day, alarm, ended, transport_dest, winch, resources, notes
 *   site_ele_m, distance_m, ascent_m
 *   schockraum, secondary, winch_cycles, winch_cycles_pat, winch_airload
 *   bergwacht, bw_unit, bw_info, other_ema
 *   phase:N | phaseLat:N | phaseLon:N   (N = 2..9)
 *   rea
 *   crew_override, crew.<rolle> und dayCrew.<rolle>
 *     <rolle> ist eine Kennung aus CREW_ROLES (server/db.php): p1, p2, hems,
 *     fr, driver, trainee, other. Die zugehoerigen Spalten werden ERZEUGT und
 *     stehen nicht ausgeschrieben in den Profilen — siehe crewSpalten() unten.
 *   pat.last+first, pat.last, pat.first, pat.dob, pat.age, pat.dx, pat.loc.addr,
 *   pat.loc.lat, pat.loc.lon, pat.mission_no, pat.site_desc
 *   null = Spalte wird bewusst nicht uebernommen
 *
 * 'sensitive: true' markiert Felder, die im pat_blob landen und den Browser
 * nur verschluesselt verlassen duerfen (siehe import.js, Abschnitt
 * "Patientendaten").
 */
(function (global) {
    'use strict';

    // Reihenfolge und Beschriftung der Phasen — muss zu PHASE_LABELS in
    // db.php und zu den Spaltennamen in assets/export.js passen.
    /* Rollenkatalog aus CREW_ROLES (server/db.php), von import.php als
     * CREW_ROLLEN und CREW_LABELS gesetzt. Der Rueckfall ist der Stand vor
     * Web 6.0.0 — die fuenf Flugrollen —, damit diese Datei auch ohne die
     * Vorgabe laeuft. */
    var ROLLEN = (typeof CREW_ROLLEN !== 'undefined' && CREW_ROLLEN.length)
        ? CREW_ROLLEN : ['p1', 'p2', 'hems', 'fr', 'other'];
    var LABELS = (typeof CREW_LABELS !== 'undefined' && CREW_LABELS)
        ? CREW_LABELS
        : { p1: 'Pilot 1', p2: 'Pilot 2', hems: 'HEMS-TC', fr: 'Flugretter',
            other: 'Sonstige' };

    /**
     * Besatzungsspalten in ein Profil eintragen.
     *
     * Bis Web 5.10.0 standen sie ausgeschrieben da — zehn Zeilen im CSV-Profil,
     * fuenf im Excel-Profil. Mit sieben Rollen waeren es vierzehn und sieben
     * geworden, und eine achte Rolle haette man an einer der drei Stellen
     * vergessen. $ziel ist 'dayCrew' oder 'crew', $namen bildet die Rolle auf
     * die Spaltenueberschrift ab.
     */
    function crewSpalten(columns, prefixOderLabel, ziel, alsLabel) {
        ROLLEN.forEach(function (r) {
            var kopf = alsLabel
                ? ((r === 'other') ? 'Sonstige Besatzung' : (LABELS[r] || r))
                : (prefixOderLabel + r);
            columns[kopf] = {
                target: ziel + '.' + r,
                parse: alsLabel
                    ? ['dashLeer', 'trim', 'max:120']
                    : ['trim', 'max:120']
            };
        });
        return columns;
    }

    /* Muss mit assets/export.js uebereinstimmen — dort steht die Begruendung
       fuer die neutralen Namen der Phasen 3 und 7 (E20). */
    var PHASE_SLUGS = {
        2: 'alarmierung', 3: 'ausruecken', 4: 'ankunft_einsatzort',
        5: 'ankunft_patientin', 6: 'transportbeginn', 7: 'ankunft_klinik',
        8: 'uebergabezeit', 9: 'endzeit'
    };
    /* Die Namen bis Web 6.1.0. Nur diese beiden Phasen sind betroffen; die
       uebrigen sechs hiessen immer schon neutral. */
    var ALT_PHASE_SLUGS = { 3: 'abflug', 7: 'landung_krankenhaus' };

    // ----------------------------------------------------------------------
    // Jahresliste "Einsatzdokumentation Christoph 17"
    // Zeile 1 Titel (enthaelt das Jahr), Zeile 2 leer, Zeile 3 Kopfzeile,
    // ab Zeile 4 die Einsaetze. Die Datumsspalte fuehrt KEIN Jahr.
    // ----------------------------------------------------------------------
    var ch17 = {
        id: 'ch17_jahresliste',
        label: 'Excel (GuteSeele)',
        sheet: 0,
        headerRow: 'auto',
        minHeaderMatch: 8,
        expectedHeaders: ['Datum', 'Zeit', 'Name', 'Geb.dat', 'Vers.',
            'Einsatzort', 'RTW', 'Diagnose', 'Transport', 'Winde',
            'HEMS', 'Pilot', 'Einsatz-Nr'],

        params: [{
            key: 'jahr',
            label: 'Jahr der Liste',
            type: 'number',
            // Vorschlag aus der Titelzeile ("... - 2026"), sonst aus dem
            // Dateinamen, sonst das laufende Jahr. Immer nur ein Vorschlag —
            // der Wert bleibt im Dialog aenderbar, damit eine umformulierte
            // Titelzeile den Import nicht unbrauchbar macht.
            suggest: function (ctx) {
                var quellen = [ctx.titleText || '', ctx.fileName || ''];
                for (var i = 0; i < quellen.length; i++) {
                    var t = /(19|20)\d{2}/.exec(quellen[i]);
                    if (t) { return parseInt(t[0], 10); }
                }
                return new Date().getFullYear();
            }
        }],

        columns: {
            'Datum': { target: 'day', parse: ['trim', 'dateNoYear'], required: true },
            'Zeit': { target: 'alarm', parse: ['timeHHMM'], required: true },
            'Name': { target: 'pat.last+first', parse: ['trim', 'splitComma'], sensitive: true },
            'Geb.dat': { target: 'pat.dob', parse: ['dateFull'], sensitive: true },
            // "Vers." (P/S) wird bewusst nicht uebernommen — die Angabe ist im
            // System nicht abgebildet und soll nicht auf "Sekundaertransport"
            // umgedeutet werden.
            'Vers.': { target: null },
            'Einsatzort': { target: 'pat.loc.addr', parse: ['trim'], sensitive: true },
            // RTW geht auf "Weitere Rettungsmittel" (eigene Zeilen in
            // mission_resources, einzeln entfernbar), nicht auf "Anderer
            // Notarzt". Mehrere durch Komma getrennte Angaben werden zu
            // mehreren Eintraegen.
            'RTW': { target: 'resources', parse: ['trim', 'splitList', 'maxEach:120'] },
            'Diagnose': { target: 'pat.dx', parse: ['trim'], sensitive: true },
            'Transport': { target: 'transport_dest', parse: ['trim', 'max:190'] },
            'Winde': { target: 'winch', parse: ['boolJN'] },
            'HEMS': { target: 'dayCrew.hems', parse: ['trim', 'max:120'] },
            'Pilot': { target: 'dayCrew.p1', parse: ['trim', 'max:120'] },
            'Einsatz-Nr': { target: 'pat.mission_no', parse: ['trim', 'max:64'], sensitive: true }
        },

        // Seit Web 2.9.0 ist die Einsatznummer Teil des pat_blob und wird dem
        // Server beim Abgleich nicht mehr im Klartext uebergeben — er kennt
        // dafuer nur noch Tag + Alarmzeit. Der Abgleich ueber die Nummer
        // passiert clientseitig gegen die entschluesselten Bestandsdaten
        // (siehe import_ui.js, bestandPruefen/dublette).
        dedupeKey: ['day+alarm'],

        // Spaltenreihenfolge fuer den Export. Bewusst dieselbe Liste wie oben,
        // damit Import und Export nicht auseinanderlaufen.
        exportOrder: ['Datum', 'Zeit', 'Name', 'Geb.dat', 'Vers.', 'Einsatzort',
            'RTW', 'Diagnose', 'Transport', 'Winde', 'HEMS', 'Pilot', 'Einsatz-Nr'],
        exportTitle: 'Einsatzdokumentation Christoph 17 - {jahr}',
        exportHeaderRow: 3,

        // Spalten der Pruef-Tabelle in Schritt 2. Nicht alle Spalten eines
        // Formats sind zum Korrigieren interessant; die Liste haelt die
        // Tabelle auf einer Bildschirmbreite.
        reviewColumns: ['Datum', 'Zeit', 'Name', 'Geb.dat', 'Einsatzort', 'RTW',
            'Diagnose', 'Transport', 'Winde', 'HEMS', 'Pilot', 'Einsatz-Nr']
    };

    // ----------------------------------------------------------------------
    // Rueckimport des eigenen vollstaendigen CSV-Exports (einsaetze.csv).
    //
    // Verlustfrei bis auf drei bewusste Ausnahmen:
    //   - einsatz_id wird NICHT uebernommen (IDs sind kontospezifisch; ein
    //     Import in ein anderes Konto vergibt neue)
    //   - GPX-Dateien werden nicht eingelesen (siehe Handbuch: Tracks stammen
    //     von der Uhr und gehoeren ins Backup, nicht in den Export-Rundweg)
    //   - rettungsmittel/standort/art sind Klartextnamen; Rettungsmittel und
    //     Standort werden wie bei allen Profilen oben auf der Seite ausgewaehlt
    // ----------------------------------------------------------------------
    var csvColumns = {
        'einsatz_id': { target: null },
        /* 'diensttag' ist das Datum des Dienstes und bleibt der Gruppierungs-
           schluessel des Imports. 'datum' — das echte Einsatzdatum — wird NICHT
           uebernommen: Beide zusammen waeren zwei Quellen fuer dieselbe
           Zuordnung, und bei einem Dienst ueber Mitternacht widersprechen sie
           sich planmaessig. Die Uhrzeit rechnet die Mitternachtslogik ohnehin
           dem Folgetag zu. */
        'diensttag': { target: 'day', parse: ['trim', 'dateIso'], required: true },
        'diensttag_id': { target: null },                // kontospezifisch, s. einsatz_id
        'datum': { target: null },
        /* Kopfzeile bis Web 5.10.0. Zeigt auf dasselbe Ziel, damit frühere
           Exportdateien lesbar bleiben — dieselbe Regel wie bei 'site_desc'
           weiter unten. */
        'flugtag': { target: 'day', parse: ['trim', 'dateIso'] },
        'uhrzeit_ortszeit': { target: 'alarm', parse: ['timeHHMM'], required: true },
        // Herkunft und Bearbeitungsstatus beschreiben, wie ein Datensatz IN
        // DIESER Installation entstanden ist. Beim Einlesen entsteht er neu —
        // api/import_commit.php vergibt origin = 'import' und setzt edited beim
        // Aktualisieren selbst. Ein Wert aus der Datei waere dort eine Aussage
        // ueber ein fremdes Konto.
        'herkunft': { target: null },                    // wird beim Import neu gesetzt
        'final': { target: null },
        'manual': { target: null },
        'edited': { target: null },                      // wird beim Import neu gesetzt

        /* Rettungsmittel, Art und Standort sind Klartextnamen aus den
           eingefrorenen Spalten des Diensttags. Sie werden NICHT uebernommen:
           Eine Aufloesung ueber Namensgleichheit waere bruechig (umbenannt,
           gleichnamig an zwei Standorten), und der Standortbezug ist
           verbindlich (E15). Beides wird oben auf der Seite ausgewaehlt.
           'hubschrauber' ist die Kopfzeile bis Web 5.10.0. */
        'hubschrauber': { target: null },
        'rettungsmittel': { target: null },
        'art': { target: null },
        'standort': { target: null },

        'crew_abweichend': { target: 'crew_override', parse: ['boolJN'] },

        // 'beginn' wird nicht uebernommen: Der Startzeitpunkt ergibt sich aus
        // diensttag + uhrzeit_ortszeit, und zwei Quellen fuer dieselbe Angabe
        // waeren eine Widerspruchsquelle. 'dauer_min' ist gerechnet.
        'beginn': { target: null },
        'ende': { target: 'ended', parse: ['isoTs'] },
        'dauer_min': { target: null },

        'strecke_m': { target: 'distance_m', parse: ['ganzzahl'] },
        'hoehenmeter_m': { target: 'ascent_m', parse: ['ganzzahl'] },
        'hoehe_einsatzort_m': { target: 'site_ele_m', parse: ['ganzzahl'] },

        /* Felder der Etappe 2 (Web 6.1.0). `export_csv_v1` ist der verlustfreie
           Rückweg des eigenen Exports und muss mit assets/export.js synchron
           bleiben — jede dort ergänzte Spalte gehört hier ebenfalls hin, sonst
           verliert ein Export-Import-Umlauf sie stillschweigend. */
        'transport_art': { target: 'transport_mode', parse: ['trim', 'max:12'] },
        'na_begleitung': { target: 'na_escort', parse: ['boolJN'] },
        'fehleinsatz': { target: 'false_alarm', parse: ['boolJN'] },
        'ziel_lat': { target: 'dest_lat', parse: ['dezimal'] },
        'ziel_lon': { target: 'dest_lon', parse: ['dezimal'] },
        'abfahrt_regel': { target: 'start_src', parse: ['trim', 'max:12'] },
        'pat_start_adresse': { target: 'pat.start.addr', parse: ['trim'], sensitive: true },
        'pat_start_lat': { target: 'pat.start.lat', parse: ['dezimal'], sensitive: true },
        'pat_start_lon': { target: 'pat.start.lon', parse: ['dezimal'], sensitive: true },

        'transport_dest': { target: 'transport_dest', parse: ['trim', 'max:190'] },
        // Alte Kopfzeile aus Exporten bis Web 3.2.0. Zeigt auf dasselbe Ziel wie
        // 'pat_ort_beschreibung' (E10), damit frühere Dateien lesbar bleiben.
        'site_desc': { target: 'pat.site_desc', parse: ['trim', 'max:190'], sensitive: true },
        'schockraum': { target: 'schockraum', parse: ['boolJN'] },
        'secondary': { target: 'secondary', parse: ['boolJN'] },
        'winch': { target: 'winch', parse: ['boolJN'] },
        'winch_cycles': { target: 'winch_cycles', parse: ['ganzzahl'] },
        'winch_cycles_pat': { target: 'winch_cycles_pat', parse: ['ganzzahl'] },
        'winch_airload': { target: 'winch_airload', parse: ['boolJN'] },
        'bergwacht': { target: 'bergwacht', parse: ['boolJN'] },
        'bw_unit': { target: 'bw_unit', parse: ['trim', 'max:120'] },
        'bw_info': { target: 'bw_info', parse: ['trim', 'max:190'] },
        'other_ema': { target: 'other_ema', parse: ['trim', 'max:190'] },
        'weitere_rettungsmittel': { target: 'resources', parse: ['pipeList', 'maxEach:120'] },
        'notizen': { target: 'notes', parse: ['trim', 'max:2000'] },

        'pat_mission_no': { target: 'pat.mission_no', parse: ['trim', 'max:64'], sensitive: true },
        'pat_nachname': { target: 'pat.last', parse: ['trim'], sensitive: true },
        'pat_vorname': { target: 'pat.first', parse: ['trim'], sensitive: true },
        'pat_geburtsdatum': { target: 'pat.dob', parse: ['dateFull'], sensitive: true },
        'pat_alter': { target: 'pat.age', parse: ['alterJahre'], sensitive: true },
        'pat_diagnose': { target: 'pat.dx', parse: ['trim'], sensitive: true },
        'pat_ort_adresse': { target: 'pat.loc.addr', parse: ['trim'], sensitive: true },
        'pat_ort_lat': { target: 'pat.loc.lat', parse: ['dezimal'], sensitive: true },
        'pat_ort_lon': { target: 'pat.loc.lon', parse: ['dezimal'], sensitive: true },
        'pat_ort_beschreibung': { target: 'pat.site_desc', parse: ['trim', 'max:190'], sensitive: true },

        'rea_json': { target: 'rea', parse: ['jsonRea'] },
        'track_datei': { target: null },                 // GPX wird nicht eingelesen
        'track_punkte': { target: null }
    };

    // Phasenspalten ergaenzen — dieselbe Ableitung wie in assets/export.js,
    // damit Export und Rueckimport nicht auseinanderlaufen.
    Object.keys(PHASE_SLUGS).forEach(function (nStr) {
        var n = parseInt(nStr, 10);
        csvColumns['phase_0' + n + '_' + PHASE_SLUGS[n]] =
            { target: 'phase:' + n, parse: ['isoTs'] };
        csvColumns['phase_0' + n + '_lat'] = { target: 'phaseLat:' + n, parse: ['dezimal'] };
        csvColumns['phase_0' + n + '_lon'] = { target: 'phaseLon:' + n, parse: ['dezimal'] };
    });
    /* Die Spaltennamen der Phasen 3 und 7 aus Exporten BIS Web 6.1.0. Sie
       zeigen auf dasselbe Ziel wie ihre neutralen Nachfolger — dieselbe
       Loesung wie bei 'site_desc' weiter oben. Ohne sie verlöre der Rückweg
       einer alten Exportdatei stillschweigend zwei Zeitstempel, und zwar die
       Alarm- und die Klinikzeit. */
    Object.keys(ALT_PHASE_SLUGS).forEach(function (nStr) {
        var n = parseInt(nStr, 10);
        csvColumns['phase_0' + n + '_' + ALT_PHASE_SLUGS[n]] =
            { target: 'phase:' + n, parse: ['isoTs'] };
    });

    var exportCsv = {
        id: 'export_csv_v1',
        label: 'CSV (Standard)',
        sheet: 0,
        headerRow: 'auto',
        // Hoch angesetzt: Die Kopfzeile dieses Formats ist unverwechselbar,
        // und ein zu niedriger Wert wuerde fremde Tabellen faelschlich hier
        // einsortieren.
        minHeaderMatch: 20,
        expectedHeaders: Object.keys(csvColumns),
        params: [],
        columns: csvColumns,
        dedupeKey: ['mission_no', 'day+alarm'],
        explicitCrew: true,
        archiveMember: 'einsaetze.csv',
        reviewColumns: ['flugtag', 'uhrzeit_ortszeit', 'pat_nachname', 'pat_vorname',
            'pat_geburtsdatum', 'pat_ort_adresse', 'pat_diagnose', 'transport_dest',
            'winch', 'crew_hems', 'crew_p1', 'pat_mission_no']
    };

    // ----------------------------------------------------------------------
    // Rueckimport des eigenen Standard-Excel-Exports (Profil A).
    // Kopfzeile in Zeile 3, Daten ab Zeile 4. Leere Werte stehen dort als "-".
    // ----------------------------------------------------------------------
    var exportExcel = {
        id: 'export_excel_v1',
        label: 'Excel (Standard)',
        sheet: 0,
        headerRow: 'auto',
        minHeaderMatch: 10,
        /* Die Kopfzeilen BEIDER Fassungen stehen hier: die bis Web 5.10.0
           ('Hubschrauber', 'Flugkilometer') und die neutrale ab Web 6.0.0
           ('Rettungsmittel', 'Kilometer'). minHeaderMatch entscheidet ueber
           Treffer, nicht Vollstaendigkeit — damit erkennt das Profil beide
           Fassungen, ohne dass es zwei Profile braeuchte. */
        expectedHeaders: ['Hubschrauber', 'Rettungsmittel', 'Standort',
            'Einsatzdatum', 'Alarmzeit',
            'Endzeit', 'Dauer', 'Einsatznummer', 'Nachname', 'Vorname',
            'Geburtsdatum', 'Alter', 'Einsatzort', 'Diagnose',
            'Sekundärtransport', 'Transportziel', 'Schockraum', 'Windeneinsatz',
            'Windenzyklen gesamt', 'Bergwacht', 'Bergwacht-Einheit',
            'Weitere Rettungsmittel', 'Höhe Einsatzort (m)', 'Flugkilometer',
            'Kilometer', 'Notizen'].concat(ROLLEN.map(function (r) {
                return (r === 'other') ? 'Sonstige Besatzung' : (LABELS[r] || r);
            })),
        params: [],

        // Wortlaut aus SPEC_Export.md 7.2. Bewusst in der Aussagerichtung
        // "bleibt leer", nicht "geht verloren": Die Angaben stehen in dieser
        // Datei nie drin, es werden lediglich Felder nicht befuellt.
        warning: 'Diese Datei enthält nicht alle Felder, die das System kennt. '
            + 'Nach dem Import bleiben leer: die Phasen Abflug, Ankunft '
            + 'Einsatzort, Ankunft PatientIn, Transportbeginn, Landung '
            + 'Krankenhaus und Übergabezeit, sämtliche Koordinaten, die '
            + 'Reanimationsdokumentation, der Track (und damit auch die '
            + 'Flugkilometer) sowie ein von Hand eingetragenes Alter ohne '
            + 'Geburtsdatum. Für einen vollständigen Rückweg nutze den '
            + 'CSV-Export, für eine echte Wiederherstellung das Backup.',

        columns: {
            // Rettungsmittel und Standort sind Stammdaten und werden oben auf
            // der Seite ausgewaehlt — eine Bezeichnung aus der Datei wuerde sonst
            // stillschweigend neue Stammdaten anlegen.
            /* 'Hubschrauber' ist die Kopfzeile bis Web 5.10.0, 'Rettungsmittel'
               die neutrale ab Web 6.0.0. Beide bleiben eingetragen, damit
               frühere Exportdateien lesbar bleiben. */
            'Hubschrauber': { target: null },
            'Rettungsmittel': { target: null },
            'Standort': { target: null },
            'Einsatzdatum': { target: 'day', parse: ['dateFull'], required: true },
            'Alarmzeit': { target: 'alarm', parse: ['dashLeer', 'timeHHMM'], required: true },
            'Endzeit': { target: 'phaseLocal:9', parse: ['dashLeer', 'timeHHMM'] },
            // Gerechnet, nicht gespeichert — wuerde zu Widerspruechen fuehren,
            // sobald jemand eine Zeit korrigiert (SPEC_Export.md 7.2).
            'Dauer': { target: null },
            // 'Alter' ist in dieser Datei das ANGEZEIGTE Alter: bei gesetztem
            // Geburtsdatum daraus gerechnet, sonst der gespeicherte Wert. Ein
            // Rueckimport muesste beide Faelle auseinanderhalten und ein
            // gerechnetes Alter verwerfen, sonst stuende es dauerhaft im
            // pat_blob und liefe bei jeder Korrektur des Geburtsdatums
            // auseinander. Der verlustfreie Weg ist die CSV-Spalte
            // 'pat_alter', die den Rohwert fuehrt.
            'Alter': { target: null },

            'Einsatznummer': { target: 'pat.mission_no', parse: ['dashLeer', 'trim', 'max:64'], sensitive: true },
            'Nachname': { target: 'pat.last', parse: ['dashLeer', 'trim'], sensitive: true },
            'Vorname': { target: 'pat.first', parse: ['dashLeer', 'trim'], sensitive: true },
            'Geburtsdatum': { target: 'pat.dob', parse: ['dashLeer', 'dateFull'], sensitive: true },
            'Einsatzort': { target: 'pat.loc.addr', parse: ['dashLeer', 'trim'], sensitive: true },
            'Diagnose': { target: 'pat.dx', parse: ['dashLeer', 'trim'], sensitive: true },

            // Besatzungsspalten: erzeugt aus dem Rollenkatalog, siehe unten
            // (crewSpalten mit alsLabel = true).

            'Sekundärtransport': { target: 'secondary', parse: ['boolJN'] },
            'Transportziel': { target: 'transport_dest', parse: ['dashLeer', 'trim', 'max:190'] },
            'Schockraum': { target: 'schockraum', parse: ['boolJN'] },
            'Windeneinsatz': { target: 'winch', parse: ['boolJN'] },
            'Windenzyklen gesamt': { target: 'winch_cycles', parse: ['dashLeer', 'ganzzahl'] },
            'Bergwacht': { target: 'bergwacht', parse: ['boolJN'] },
            'Bergwacht-Einheit': { target: 'bw_unit', parse: ['dashLeer', 'trim', 'max:120'] },
            'Weitere Rettungsmittel': { target: 'resources', parse: ['dashLeer', 'trim', 'splitList', 'maxEach:120'] },
            'Höhe Einsatzort (m)': { target: 'site_ele_m', parse: ['dashLeer', 'ganzzahl'] },
            /* Kilometer sind aus dem Track gerechnet. Ohne Track waere ein
               uebernommener Wert nicht nachvollziehbar — deshalb verworfen.
               'Flugkilometer' ist die Kopfzeile bis Web 5.10.0 und bleibt
               eingetragen, damit frühere Dateien erkannt werden. */
            'Flugkilometer': { target: null },
            'Kilometer': { target: null },
            'Notizen': { target: 'notes', parse: ['dashLeer', 'trim', 'max:2000'] }
        },

        dedupeKey: ['mission_no', 'day+alarm'],

        reviewColumns: ['Einsatzdatum', 'Alarmzeit', 'Endzeit', 'Nachname', 'Vorname',
            'Geburtsdatum', 'Einsatzort', 'Diagnose', 'Transportziel',
            'Windeneinsatz', 'HEMS', 'Pilot 1', 'Einsatznummer'],

        // Profil A schreibt einen Diensttag ohne Einsatz als eine Zeile mit
        // Datum und lauter "-" (SPEC_Export.md 3.2). Die Pipeline muss das
        // als Diensttag ohne Einsatz lesen, nicht als fehlerhaften Einsatz.
        emptyDayRows: true
    };

    /* ---- Besatzungsspalten nachtragen -----------------------------------
     *
     * Erst hier, nachdem die Profile stehen: Ein Objektliteral kann sich nicht
     * selbst erweitern, und die Spalten sollen genau EINMAL erzeugt werden.
     *
     * Das CSV-Profil nennt Tages- UND Einsatzbesatzung getrennt
     * (tag_crew_<rolle> und crew_<rolle>) — deshalb explicitCrew. Das
     * Excel-Profil kennt nur eine Besatzungsangabe je Zeile; sie gilt als
     * Tagesbesatzung, und gruppiere() rechnet Abweichungen selbst aus. */
    crewSpalten(exportCsv.columns, 'tag_crew_', 'dayCrew', false);
    crewSpalten(exportCsv.columns, 'crew_', 'crew', false);
    crewSpalten(exportExcel.columns, null, 'dayCrew', true);

    global.ImportProfile = {
        // Reihenfolge = Reihenfolge im Auswahlfeld (liste() laeuft in
        // Einfuegereihenfolge). Bewusst das verlustfreie eigene Format zuerst.
        profiles: {
            export_csv_v1: exportCsv,
            export_excel_v1: exportExcel,
            ch17_jahresliste: ch17
        },
        liste: function () {
            var out = [], k;
            for (k in this.profiles) {
                if (Object.prototype.hasOwnProperty.call(this.profiles, k)) {
                    out.push(this.profiles[k]);
                }
            }
            return out;
        }
    };
}(typeof window !== 'undefined' ? window : this));
