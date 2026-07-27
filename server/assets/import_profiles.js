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
 *
 * target-Werte, die die Pipeline kennt:
 *   day, alarm, transport_dest, winch, resources
 *   dayCrew.p1 | dayCrew.p2 | dayCrew.hems | dayCrew.fr | dayCrew.other
 *   pat.last+first, pat.dob, pat.dx, pat.loc.addr, pat.mission_no
 *   null = Spalte wird bewusst nicht uebernommen
 *
 * 'sensitive: true' markiert Felder, die im pat_blob landen und den Browser
 * nur verschluesselt verlassen duerfen (siehe import.js, Abschnitt
 * "Patientendaten").
 */
(function (global) {
    'use strict';

    var profile = {
        // ------------------------------------------------------------------
        // Jahresliste "Einsatzdokumentation Christoph 17"
        // Zeile 1 Titel (enthaelt das Jahr), Zeile 2 leer, Zeile 3 Kopfzeile,
        // ab Zeile 4 die Einsaetze. Die Datumsspalte fuehrt KEIN Jahr.
        // ------------------------------------------------------------------
        id: 'ch17_jahresliste',
        label: 'Einsatzdoku Christoph 17 (Jahresliste)',
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

        // Spaltenreihenfolge fuer den Export (Paket 3). Bewusst dieselbe
        // Liste wie oben, damit Import und Export nicht auseinanderlaufen.
        exportOrder: ['Datum', 'Zeit', 'Name', 'Geb.dat', 'Vers.', 'Einsatzort',
            'RTW', 'Diagnose', 'Transport', 'Winde', 'HEMS', 'Pilot', 'Einsatz-Nr'],
        exportTitle: 'Einsatzdokumentation Christoph 17 - {jahr}',
        exportHeaderRow: 3
    };

    global.ImportProfile = {
        profiles: { ch17_jahresliste: profile },
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
