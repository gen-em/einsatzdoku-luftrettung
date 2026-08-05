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
 *   crew_override, crew.p1 | crew.p2 | crew.hems | crew.fr | crew.other
 *   dayCrew.p1 | dayCrew.p2 | dayCrew.hems | dayCrew.fr | dayCrew.other
 *   pat.last+first, pat.last, pat.first, pat.dob, pat.dx, pat.loc.addr,
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
    var PHASE_SLUGS = {
        2: 'alarmierung', 3: 'abflug', 4: 'ankunft_einsatzort',
        5: 'ankunft_patientin', 6: 'transportbeginn', 7: 'landung_krankenhaus',
        8: 'uebergabezeit', 9: 'endzeit'
    };

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
    //   - hubschrauber/standort sind Klartextnamen; Hubschrauber und Basis
    //     werden wie bei allen Profilen oben auf der Seite ausgewaehlt
    // ----------------------------------------------------------------------
    var csvColumns = {
        'einsatz_id': { target: null },
        'flugtag': { target: 'day', parse: ['trim', 'dateIso'], required: true },
        'datum': { target: null },                       // Dublette von flugtag
        'uhrzeit_ortszeit': { target: 'alarm', parse: ['timeHHMM'], required: true },
        'herkunft': { target: null },                    // wird beim Import neu gesetzt
        'final': { target: null },
        'manual': { target: null },

        'hubschrauber': { target: null },
        'standort': { target: null },
        'tag_crew_p1': { target: 'dayCrew.p1', parse: ['trim', 'max:120'] },
        'tag_crew_p2': { target: 'dayCrew.p2', parse: ['trim', 'max:120'] },
        'tag_crew_hems': { target: 'dayCrew.hems', parse: ['trim', 'max:120'] },
        'tag_crew_fr': { target: 'dayCrew.fr', parse: ['trim', 'max:120'] },
        'tag_crew_other': { target: 'dayCrew.other', parse: ['trim', 'max:120'] },

        'crew_abweichend': { target: 'crew_override', parse: ['boolJN'] },
        'crew_p1': { target: 'crew.p1', parse: ['trim', 'max:120'] },
        'crew_p2': { target: 'crew.p2', parse: ['trim', 'max:120'] },
        'crew_hems': { target: 'crew.hems', parse: ['trim', 'max:120'] },
        'crew_fr': { target: 'crew.fr', parse: ['trim', 'max:120'] },
        'crew_other': { target: 'crew.other', parse: ['trim', 'max:120'] },

        // 'beginn' wird nicht uebernommen: Der Startzeitpunkt ergibt sich aus
        // flugtag + uhrzeit_ortszeit, und zwei Quellen fuer dieselbe Angabe
        // waeren eine Widerspruchsquelle. 'dauer_min' ist gerechnet.
        'beginn': { target: null },
        'ende': { target: 'ended', parse: ['isoTs'] },
        'dauer_min': { target: null },

        'strecke_m': { target: 'distance_m', parse: ['ganzzahl'] },
        'hoehenmeter_m': { target: 'ascent_m', parse: ['ganzzahl'] },
        'hoehe_einsatzort_m': { target: 'site_ele_m', parse: ['ganzzahl'] },

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
        expectedHeaders: ['Hubschrauber', 'Standort', 'Einsatzdatum', 'Alarmzeit',
            'Endzeit', 'Dauer', 'Einsatznummer', 'Nachname', 'Vorname',
            'Geburtsdatum', 'Alter', 'Einsatzort', 'Diagnose', 'Pilot 1',
            'Pilot 2', 'HEMS', 'Flugretter', 'Sonstige Besatzung',
            'Sekundärtransport', 'Transportziel', 'Schockraum', 'Windeneinsatz',
            'Windenzyklen gesamt', 'Bergwacht', 'Bergwacht-Einheit',
            'Weitere Rettungsmittel', 'Höhe Einsatzort (m)', 'Flugkilometer',
            'Notizen'],
        params: [],

        // Wortlaut aus SPEC_Export.md 7.2. Bewusst in der Aussagerichtung
        // "bleibt leer", nicht "geht verloren": Die Angaben stehen in dieser
        // Datei nie drin, es werden lediglich Felder nicht befuellt.
        warning: 'Diese Datei enthält nicht alle Felder, die das System kennt. '
            + 'Nach dem Import bleiben leer: die Phasen Abflug, Ankunft '
            + 'Einsatzort, Ankunft PatientIn, Transportbeginn, Landung '
            + 'Krankenhaus und Übergabezeit, sämtliche Koordinaten, die '
            + 'Reanimationsdokumentation und der Track (und damit auch die '
            + 'Flugkilometer). Für einen vollständigen Rückweg nutze den '
            + 'CSV-Export, für eine echte Wiederherstellung das Backup.',

        columns: {
            // Hubschrauber und Basis sind Stammdaten und werden oben auf der
            // Seite ausgewaehlt — ein Kennzeichen aus der Datei wuerde sonst
            // stillschweigend neue Stammdaten anlegen.
            'Hubschrauber': { target: null },
            'Standort': { target: null },
            'Einsatzdatum': { target: 'day', parse: ['dateFull'], required: true },
            'Alarmzeit': { target: 'alarm', parse: ['dashLeer', 'timeHHMM'], required: true },
            'Endzeit': { target: 'phaseLocal:9', parse: ['dashLeer', 'timeHHMM'] },
            // Gerechnet, nicht gespeichert — wuerde zu Widerspruechen fuehren,
            // sobald jemand eine Zeit korrigiert (SPEC_Export.md 7.2).
            'Dauer': { target: null },
            'Alter': { target: null },

            'Einsatznummer': { target: 'pat.mission_no', parse: ['dashLeer', 'trim', 'max:64'], sensitive: true },
            'Nachname': { target: 'pat.last', parse: ['dashLeer', 'trim'], sensitive: true },
            'Vorname': { target: 'pat.first', parse: ['dashLeer', 'trim'], sensitive: true },
            'Geburtsdatum': { target: 'pat.dob', parse: ['dashLeer', 'dateFull'], sensitive: true },
            'Einsatzort': { target: 'pat.loc.addr', parse: ['dashLeer', 'trim'], sensitive: true },
            'Diagnose': { target: 'pat.dx', parse: ['dashLeer', 'trim'], sensitive: true },

            'Pilot 1': { target: 'dayCrew.p1', parse: ['dashLeer', 'trim', 'max:120'] },
            'Pilot 2': { target: 'dayCrew.p2', parse: ['dashLeer', 'trim', 'max:120'] },
            'HEMS': { target: 'dayCrew.hems', parse: ['dashLeer', 'trim', 'max:120'] },
            'Flugretter': { target: 'dayCrew.fr', parse: ['dashLeer', 'trim', 'max:120'] },
            'Sonstige Besatzung': { target: 'dayCrew.other', parse: ['dashLeer', 'trim', 'max:120'] },

            'Sekundärtransport': { target: 'secondary', parse: ['boolJN'] },
            'Transportziel': { target: 'transport_dest', parse: ['dashLeer', 'trim', 'max:190'] },
            'Schockraum': { target: 'schockraum', parse: ['boolJN'] },
            'Windeneinsatz': { target: 'winch', parse: ['boolJN'] },
            'Windenzyklen gesamt': { target: 'winch_cycles', parse: ['dashLeer', 'ganzzahl'] },
            'Bergwacht': { target: 'bergwacht', parse: ['boolJN'] },
            'Bergwacht-Einheit': { target: 'bw_unit', parse: ['dashLeer', 'trim', 'max:120'] },
            'Weitere Rettungsmittel': { target: 'resources', parse: ['dashLeer', 'trim', 'splitList', 'maxEach:120'] },
            'Höhe Einsatzort (m)': { target: 'site_ele_m', parse: ['dashLeer', 'ganzzahl'] },
            // Flugkilometer sind aus dem Track gerechnet. Ohne Track waere ein
            // uebernommener Wert nicht nachvollziehbar — deshalb verworfen.
            'Flugkilometer': { target: null },
            'Notizen': { target: 'notes', parse: ['dashLeer', 'trim', 'max:2000'] }
        },

        dedupeKey: ['mission_no', 'day+alarm'],

        reviewColumns: ['Einsatzdatum', 'Alarmzeit', 'Endzeit', 'Nachname', 'Vorname',
            'Geburtsdatum', 'Einsatzort', 'Diagnose', 'Transportziel',
            'Windeneinsatz', 'HEMS', 'Pilot 1', 'Einsatznummer'],

        // Profil A schreibt einen Flugtag ohne Einsatz als eine Zeile mit
        // Datum und lauter "-" (SPEC_Export.md 3.2). Die Pipeline muss das
        // als Flugtag ohne Einsatz lesen, nicht als fehlerhaften Einsatz.
        emptyDayRows: true
    };

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
