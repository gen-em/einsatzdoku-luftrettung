/**
 * Import-Pipeline (Teil 1: Einlesen, Uebersetzen, Pruefen, Gruppieren).
 *
 * WARUM IM BROWSER: Die Datei enthaelt Name, Geburtsdatum, Diagnose und
 * Einsatzort. Diese Angaben duerfen den Rechner nur verschluesselt verlassen
 * (Ende-zu-Ende-Verschluesselung). Ein Datei-Upload an den Server ist damit
 * ausgeschlossen — das komplette Lesen und Aufbereiten laeuft hier.
 *
 * Diese Datei enthaelt bewusst KEINE Oberflaeche und KEINEN Netzverkehr. Sie
 * ist reine Rechenlogik: gleiche Eingabe, gleiches Ergebnis. Die Bedienung
 * (Review-Tabelle, Konfliktloesung) und der Versand an den Server folgen
 * getrennt — so bleibt der hier steckende Teil pruefbar.
 *
 * Ablauf:
 *   leseArbeitsmappe(daten)          Datei -> Arbeitsmappe (SheetJS)
 *   erkenneProfil(mappe, profile)    passendes Profil anhand der Kopfzeile
 *   paramVorschlaege(...)            z. B. Jahr aus der Titelzeile
 *   verarbeite(mappe, profil, param) Zeilen -> geprueftes Zwischenergebnis
 *   gruppiere(zeilen)                Zwischenergebnis -> Flugtage + Einsaetze
 *
 * ZAHLEN STATT DATUMSOBJEKTEN: Die Arbeitsmappe wird ohne automatische
 * Datumsumwandlung gelesen. Excel speichert Datum und Uhrzeit als Zahl (Tage
 * seit 1899-12-30); wandelt man sie in ein JavaScript-Datum um, rechnet der
 * Browser die Zeitzone von 1899 ein — in Mitteleuropa waren das damals 53
 * Minuten Abweichung. Eine Alarmzeit 10:41 wuerde so zu 09:48. Die Zahl wird
 * deshalb selbst zerlegt (XLSX.SSF.parse_date_code), ohne Zeitzonenbezug.
 */
(function (global) {
    'use strict';

    function xlsx() {
        if (!global.XLSX) { throw new Error('SheetJS (xlsx.full.min.js) ist nicht geladen.'); }
        return global.XLSX;
    }

    // ---------------------------------------------------------------- Hilfen

    function istLeer(v) {
        return v === null || v === undefined || (typeof v === 'string' && v.trim() === '');
    }

    function normKopf(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/\s+/g, ' ').trim().toLowerCase();
    }

    function zahl2(n) { return (n < 10 ? '0' : '') + n; }

    /**
     * Zelle -> {y,m,d,H,M,S} oder null.
     * Nimmt Excel-Seriennummern, echte Datumsobjekte und ISO-Zeichenketten.
     */
    function zelleZuZeit(v) {
        if (v === null || v === undefined || v === '') { return null; }
        if (typeof v === 'number' && isFinite(v)) {
            var t = xlsx().SSF.parse_date_code(v);
            return t ? { y: t.y, m: t.m, d: t.d, H: t.H, M: t.M, S: t.S } : null;
        }
        if (v instanceof Date && !isNaN(v.getTime())) {
            return {
                y: v.getFullYear(), m: v.getMonth() + 1, d: v.getDate(),
                H: v.getHours(), M: v.getMinutes(), S: v.getSeconds()
            };
        }
        return null;
    }

    function istEchtesDatum(j, m, t) {
        if (!(j >= 1900 && j <= 2200) || !(m >= 1 && m <= 12) || !(t >= 1 && t <= 31)) { return false; }
        var d = new Date(Date.UTC(j, m - 1, t));
        return d.getUTCFullYear() === j && d.getUTCMonth() === m - 1 && d.getUTCDate() === t;
    }

    // ------------------------------------------------------- Parser-Registry
    //
    // Jeder Parser bekommt (wert, ctx) und liefert entweder den neuen Wert
    // oder {error:'...'} bzw. {warn:'...', wert:...}. Ein 'error' stoppt die
    // Kette fuer dieses Feld, ein 'warn' nicht.
    // ctx = { params, spalte, feld }

    var PARSERS = {

        trim: function (v) {
            if (v === null || v === undefined) { return null; }
            if (typeof v !== 'string') { return v; }
            var s = v.replace(/\s+/g, ' ').trim();
            return s === '' ? null : s;
        },

        /** "21.5." / "21.05." / "21.5.2026" / Excel-Datumszelle -> JJJJ-MM-TT */
        dateNoYear: function (v, ctx) {
            var z = zelleZuZeit(v);
            if (z && z.y > 1900) {
                if (!istEchtesDatum(z.y, z.m, z.d)) { return { error: 'Kein gueltiges Datum' }; }
                return z.y + '-' + zahl2(z.m) + '-' + zahl2(z.d);
            }
            if (typeof v !== 'string') { return { error: 'Datum nicht lesbar' }; }
            var t = /^(\d{1,2})\s*\.\s*(\d{1,2})\s*\.?\s*((?:19|20)\d{2})?\s*$/.exec(v);
            if (!t) { return { error: 'Datum nicht lesbar (erwartet "T.M." oder "T.M.JJJJ")' }; }
            var tag = parseInt(t[1], 10), mon = parseInt(t[2], 10);
            var jahr = t[3] ? parseInt(t[3], 10) : parseInt(ctx.params.jahr, 10);
            if (!jahr) { return { error: 'Jahr fehlt — im Kopf der Seite angeben' }; }
            if (!istEchtesDatum(jahr, mon, tag)) { return { error: 'Diesen Tag gibt es nicht' }; }
            return jahr + '-' + zahl2(mon) + '-' + zahl2(tag);
        },

        /** Excel-Zeitzelle, "10:41" oder "10:41:00" -> "HH:MM" */
        timeHHMM: function (v) {
            var z = zelleZuZeit(v);
            if (z) {
                if (z.H === 0 && z.M === 0 && z.S === 0 && typeof v === 'number' && v >= 1) {
                    return { error: 'Uhrzeit fehlt (Zelle enthaelt nur ein Datum)' };
                }
                return zahl2(z.H) + ':' + zahl2(z.M);
            }
            if (typeof v !== 'string') { return { error: 'Uhrzeit nicht lesbar' }; }
            var t = /^(\d{1,2})\s*[:.]\s*(\d{2})(?:\s*[:.]\s*\d{2})?\s*$/.exec(v.trim());
            if (!t) { return { error: 'Uhrzeit nicht lesbar (erwartet "HH:MM")' }; }
            var h = parseInt(t[1], 10), m = parseInt(t[2], 10);
            if (h > 23 || m > 59) { return { error: 'Uhrzeit ausserhalb des Tages' }; }
            return zahl2(h) + ':' + zahl2(m);
        },

        /** "Nachname, Vorname" -> {last, first}; ohne Komma alles als Nachname */
        splitComma: function (v) {
            if (istLeer(v)) { return null; }
            var s = String(v), i = s.indexOf(',');
            if (i < 0) { return { last: s.trim(), first: null }; }
            var last = s.slice(0, i).trim(), first = s.slice(i + 1).trim();
            return { last: last || null, first: first || null };
        },

        /** Vollstaendiges Datum -> JJJJ-MM-TT, mit Plausibilitaetspruefung */
        dateFull: function (v) {
            if (istLeer(v)) { return null; }
            var j, m, t, z = zelleZuZeit(v);
            if (z) {
                j = z.y; m = z.m; t = z.d;
            } else if (typeof v === 'string') {
                var a = /^(\d{1,2})\s*\.\s*(\d{1,2})\s*\.\s*((?:18|19|20)\d{2})\s*$/.exec(v.trim());
                var b = /^((?:18|19|20)\d{2})-(\d{1,2})-(\d{1,2})\s*$/.exec(v.trim());
                if (a) { j = +a[3]; m = +a[2]; t = +a[1]; }
                else if (b) { j = +b[1]; m = +b[2]; t = +b[3]; }
                else { return { error: 'Geburtsdatum nicht lesbar (erwartet "TT.MM.JJJJ")' }; }
            } else {
                return { error: 'Geburtsdatum nicht lesbar' };
            }
            if (!istEchtesDatum(j, m, t)) { return { error: 'Kein gueltiges Geburtsdatum' }; }
            var iso = j + '-' + zahl2(m) + '-' + zahl2(t);
            var heute = new Date();
            var hIso = heute.getFullYear() + '-' + zahl2(heute.getMonth() + 1) + '-' + zahl2(heute.getDate());
            if (j < 1900 || iso > hIso) {
                return { warn: 'Geburtsdatum unplausibel', wert: iso };
            }
            return iso;
        },

        /** j / ja / x / 1 / y -> 1, leer -> 0, alles andere -> 0 mit Hinweis */
        boolJN: function (v) {
            if (istLeer(v)) { return 0; }
            if (typeof v === 'number') { return v ? 1 : 0; }
            var s = String(v).trim().toLowerCase();
            if (s === 'j' || s === 'ja' || s === 'x' || s === '1' || s === 'y' || s === 'yes') { return 1; }
            if (s === 'n' || s === 'nein' || s === '0' || s === '-' || s === 'no') { return 0; }
            return { warn: 'Nicht als Ja/Nein erkannt — als "nein" uebernommen', wert: 0 };
        },

        /**
         * Mehrfachangabe in Liste zerlegen. Getrennt wird nur an Komma und
         * Semikolon — NICHT am Schraegstrich, sonst zerfaellt ein Funkrufname
         * wie "KE 71/1" in zwei Eintraege.
         */
        splitList: function (v) {
            if (istLeer(v)) { return null; }
            var teile = String(v).split(/[;,]/), out = [], i, s;
            for (i = 0; i < teile.length; i++) {
                s = teile[i].replace(/\s+/g, ' ').trim();
                if (s !== '') { out.push(s); }
            }
            return out.length ? out : null;
        }
    };

    /** max:N und maxEach:N werden mit Parameter gebaut. */
    function parserHolen(name) {
        if (PARSERS[name]) { return PARSERS[name]; }
        var t = /^max:(\d+)$/.exec(name);
        if (t) {
            var n = parseInt(t[1], 10);
            return function (v) {
                if (typeof v !== 'string' || v.length <= n) { return v; }
                return { warn: 'Auf ' + n + ' Zeichen gekuerzt', wert: v.slice(0, n) };
            };
        }
        var e = /^maxEach:(\d+)$/.exec(name);
        if (e) {
            var m = parseInt(e[1], 10), gekuerzt;
            return function (v) {
                if (!Array.isArray(v)) { return v; }
                gekuerzt = false;
                var out = v.map(function (s) {
                    if (s.length <= m) { return s; }
                    gekuerzt = true; return s.slice(0, m);
                });
                return gekuerzt ? { warn: 'Eintraege auf ' + m + ' Zeichen gekuerzt', wert: out } : out;
            };
        }
        throw new Error('Unbekannter Parser: ' + name);
    }

    /** Parserkette auf einen Zellwert anwenden. */
    function kette(wert, namen, ctx) {
        var warns = [], i, r;
        for (i = 0; i < namen.length; i++) {
            r = parserHolen(namen[i])(wert, ctx);
            if (r && typeof r === 'object' && !Array.isArray(r) && (r.error || r.warn)) {
                if (r.error) { return { error: r.error }; }
                warns.push(r.warn);
                wert = r.wert;
            } else {
                wert = r;
            }
            if (wert === null || wert === undefined) { break; }
        }
        return { wert: (wert === undefined ? null : wert), warns: warns };
    }

    // ------------------------------------------------- Datei und Profilwahl

    /** ArrayBuffer/Uint8Array -> Arbeitsmappe. Datumszellen bleiben Zahlen. */
    function leseArbeitsmappe(daten) {
        return xlsx().read(daten, { type: 'array', cellDates: false, cellNF: false });
    }

    function blatt(mappe, profil) {
        var name = typeof profil.sheet === 'number' ? mappe.SheetNames[profil.sheet] : profil.sheet;
        var ws = mappe.Sheets[name];
        if (!ws) { throw new Error('Tabellenblatt nicht gefunden: ' + profil.sheet); }
        return ws;
    }

    /** Blatt als Matrix (Array von Zeilen-Arrays), Rohwerte. */
    function matrix(mappe, profil) {
        return xlsx().utils.sheet_to_json(blatt(mappe, profil), {
            header: 1, raw: true, blankrows: true, defval: null
        });
    }

    /**
     * Kopfzeile suchen: die erste Zeile, in der mindestens minHeaderMatch der
     * erwarteten Ueberschriften vorkommen. Liefert {zeile, treffer} (zeile
     * 0-basiert) oder null.
     */
    function findeKopfzeile(mat, profil) {
        var erwartet = profil.expectedHeaders.map(normKopf);
        var grenze = Math.min(mat.length, 50), i, j, treffer, vorhanden;
        for (i = 0; i < grenze; i++) {
            vorhanden = (mat[i] || []).map(normKopf);
            treffer = 0;
            for (j = 0; j < erwartet.length; j++) {
                if (vorhanden.indexOf(erwartet[j]) >= 0) { treffer++; }
            }
            if (treffer >= profil.minHeaderMatch) { return { zeile: i, treffer: treffer }; }
        }
        return null;
    }

    /**
     * Passendstes Profil bestimmen. Liefert {profil, kopfzeile, treffer} oder
     * null, wenn keines greift.
     */
    function erkenneProfil(mappe, profile) {
        var beste = null;
        profile.forEach(function (p) {
            var mat, gefunden;
            try { mat = matrix(mappe, p); } catch (e) { return; }
            gefunden = p.headerRow === 'auto'
                ? findeKopfzeile(mat, p)
                : { zeile: p.headerRow - 1, treffer: p.minHeaderMatch };
            if (gefunden && (!beste || gefunden.treffer > beste.treffer)) {
                beste = { profil: p, kopfzeile: gefunden.zeile, treffer: gefunden.treffer };
            }
        });
        return beste;
    }

    /**
     * Vorschlagswerte fuer die Profil-Parameter (z. B. das Jahr). Die
     * Titelzeile ist alles oberhalb der Kopfzeile, zusammengezogen.
     */
    function paramVorschlaege(mappe, profil, kopfzeile, dateiname) {
        var mat = matrix(mappe, profil), i, j, teile = [];
        for (i = 0; i < kopfzeile && i < mat.length; i++) {
            for (j = 0; j < (mat[i] || []).length; j++) {
                if (!istLeer(mat[i][j])) { teile.push(String(mat[i][j])); }
            }
        }
        var ctx = { titleText: teile.join(' '), fileName: dateiname || '' };
        var out = {};
        (profil.params || []).forEach(function (p) {
            out[p.key] = p.suggest ? p.suggest(ctx) : null;
        });
        return out;
    }

    // ----------------------------------------------------- Zeilen aufbereiten

    function setzeZiel(zeile, target, wert) {
        switch (target) {
        case 'day': case 'alarm':
            zeile.mission[target] = wert; break;
        case 'mission_no': case 'transport_dest': case 'winch':
            zeile.mission[target] = wert; break;
        case 'resources':
            zeile.mission.resources = wert || []; break;
        case 'pat.last+first':
            if (wert) { zeile.pat.last = wert.last; zeile.pat.first = wert.first; }
            break;
        case 'pat.dob': zeile.pat.dob = wert; break;
        case 'pat.dx': zeile.pat.dx = wert; break;
        case 'pat.loc.addr':
            if (wert) { zeile.pat.loc = { addr: wert }; }
            break;
        default:
            if (target.indexOf('dayCrew.') === 0) {
                zeile.dayCrew[target.slice(8)] = wert;
            }
        }
    }

    /**
     * Kernschritt: Matrix -> geprueftes Zwischenergebnis.
     * Liefert { kopfzeile, spalten, zeilen, unbekannteSpalten, fehlendeSpalten }.
     *
     * Jede Zeile:
     *   { srcRow, status:'ok'|'warn'|'error', issues:[{spalte,level,text}],
     *     mission:{day, alarm, mission_no, transport_dest, winch, resources},
     *     pat:{last, first, dob, dx, loc:{addr}}, dayCrew:{p1, hems, ...} }
     */
    function verarbeite(mappe, profil, params, kopfzeile) {
        return verarbeiteMatrix(matrix(mappe, profil), profil, params, kopfzeile);
    }

    /**
     * Wie verarbeite(), aber auf einer bereits eingelesenen Matrix.
     *
     * Die Review-Tabelle nutzt das: Korrigiert jemand eine Zelle, wird der
     * Wert in die Matrix geschrieben und die Pruefung komplett neu gerechnet.
     * Das ist ein paar Millisekunden teurer als eine gezielte Nachpruefung
     * einzelner Felder — dafuer kann das Ergebnis nach einer Korrektur nicht
     * von dem abweichen, was ein frischer Durchlauf ergaebe.
     */
    function verarbeiteMatrix(mat, profil, params, kopfzeile) {
        if (kopfzeile === undefined || kopfzeile === null) {
            var g = profil.headerRow === 'auto'
                ? findeKopfzeile(mat, profil)
                : { zeile: profil.headerRow - 1 };
            if (!g) { throw new Error('Kopfzeile nicht gefunden — passt das Profil zur Datei?'); }
            kopfzeile = g.zeile;
        }

        // Spaltenindex je Quellueberschrift
        var kopf = (mat[kopfzeile] || []).map(normKopf);
        var spalten = {}, unbekannte = [], fehlende = [], name;
        for (name in profil.columns) {
            if (!Object.prototype.hasOwnProperty.call(profil.columns, name)) { continue; }
            var idx = kopf.indexOf(normKopf(name));
            if (idx < 0) { fehlende.push(name); } else { spalten[name] = idx; }
        }
        kopf.forEach(function (h, i) {
            if (h === '') { return; }
            var bekannt = Object.keys(profil.columns).some(function (n) { return normKopf(n) === h; });
            if (!bekannt) { unbekannte.push({ index: i, name: String(mat[kopfzeile][i]) }); }
        });

        var zeilen = [];
        for (var r = kopfzeile + 1; r < mat.length; r++) {
            var roh = mat[r] || [];
            // Vollstaendig leere Zeilen stillschweigend uebergehen — in
            // gewachsenen Listen stehen oft Leerzeilen zwischen Monaten.
            var belegt = false;
            for (var c = 0; c < roh.length; c++) { if (!istLeer(roh[c])) { belegt = true; break; } }
            if (!belegt) { continue; }

            var z = {
                srcRow: r + 1,                 // 1-basiert wie in Excel angezeigt
                status: 'ok', issues: [],
                mission: { day: null, alarm: null, mission_no: null,
                    transport_dest: null, winch: 0, resources: [] },
                pat: { last: null, first: null, dob: null, dx: null, loc: null },
                dayCrew: {}
            };

            for (name in profil.columns) {
                if (!Object.prototype.hasOwnProperty.call(profil.columns, name)) { continue; }
                var def = profil.columns[name];
                if (!def.target) { continue; }            // bewusst nicht importiert
                var wert = spalten[name] === undefined ? null : roh[spalten[name]];

                if (istLeer(wert)) {
                    if (def.required) {
                        z.issues.push({ spalte: name, level: 'error', text: 'Pflichtangabe fehlt' });
                    }
                    continue;
                }
                var erg = kette(wert, def.parse || [], { params: params, spalte: name, feld: def.target });
                if (erg.error) {
                    z.issues.push({ spalte: name, level: def.required ? 'error' : 'warn', text: erg.error });
                    continue;
                }
                erg.warns.forEach(function (w) {
                    z.issues.push({ spalte: name, level: 'warn', text: w });
                });
                setzeZiel(z, def.target, erg.wert);
            }

            z.status = z.issues.some(function (i) { return i.level === 'error'; })
                ? 'error'
                : (z.issues.length ? 'warn' : 'ok');
            zeilen.push(z);
        }

        return {
            kopfzeile: kopfzeile + 1,
            spalten: spalten,
            zeilen: zeilen,
            unbekannteSpalten: unbekannte,
            fehlendeSpalten: fehlende
        };
    }

    // ------------------------------------------------------ Tagesgruppierung

    var ROLLEN = ['p1', 'p2', 'hems', 'fr', 'other'];

    /**
     * Zeilen nach Flugtag buendeln.
     *
     * Die Tagesbesatzung ist die der FRUEHESTEN Zeile des Tages. Weicht eine
     * spaetere Zeile in einer Rolle ab, wird daraus eine abweichende
     * Besatzung am einzelnen Einsatz (crew_override) — genau der Fall
     * "Pilotenwechsel im laufenden Dienst". Ohne Abweichung bleiben die
     * Einsatzspalten leer, damit nichts doppelt gespeichert wird.
     *
     * Fehlerzeilen bleiben aussen vor: Sie haben kein verwertbares Datum und
     * duerfen die Tagescrew nicht verfaelschen.
     */
    function gruppiere(zeilen) {
        var proTag = {}, reihenfolge = [];
        zeilen.forEach(function (z) {
            if (z.status === 'error' || !z.mission.day) { return; }
            if (!proTag[z.mission.day]) { proTag[z.mission.day] = []; reihenfolge.push(z.mission.day); }
            proTag[z.mission.day].push(z);
        });
        reihenfolge.sort();

        return reihenfolge.map(function (tag) {
            var gruppe = proTag[tag].slice().sort(function (a, b) {
                if (a.mission.alarm === b.mission.alarm) { return a.srcRow - b.srcRow; }
                return a.mission.alarm < b.mission.alarm ? -1 : 1;
            });

            var crew = {};
            ROLLEN.forEach(function (r) {
                if (gruppe[0].dayCrew[r]) { crew[r] = gruppe[0].dayCrew[r]; }
            });

            var missionen = gruppe.map(function (z) {
                var m = {
                    srcRow: z.srcRow,
                    day: tag,
                    alarm: z.mission.alarm,
                    mission_no: z.mission.mission_no,
                    transport_dest: z.mission.transport_dest,
                    winch: z.mission.winch,
                    resources: z.mission.resources,
                    pat: z.pat,
                    crew_override: 0
                };
                ROLLEN.forEach(function (r) {
                    var w = z.dayCrew[r];
                    if (w && w !== (crew[r] || null)) {
                        m.crew_override = 1;
                        m['crew_' + r] = w;
                    }
                });
                return m;
            });

            return { day: tag, crew: crew, missionen: missionen };
        });
    }

    /** Kurzuebersicht fuer die Zusammenfassung ueber der Review-Tabelle. */
    function bilanz(zeilen, tage) {
        return {
            zeilen: zeilen.length,
            ok: zeilen.filter(function (z) { return z.status === 'ok'; }).length,
            warnungen: zeilen.filter(function (z) { return z.status === 'warn'; }).length,
            fehler: zeilen.filter(function (z) { return z.status === 'error'; }).length,
            tage: tage.length,
            einsaetze: tage.reduce(function (n, t) { return n + t.missionen.length; }, 0),
            abwCrew: tage.reduce(function (n, t) {
                return n + t.missionen.filter(function (m) { return m.crew_override === 1; }).length;
            }, 0)
        };
    }

    global.ImportCore = {
        PARSERS: PARSERS,
        leseArbeitsmappe: leseArbeitsmappe,
        matrix: matrix,
        findeKopfzeile: findeKopfzeile,
        erkenneProfil: erkenneProfil,
        paramVorschlaege: paramVorschlaege,
        verarbeite: verarbeite,
        verarbeiteMatrix: verarbeiteMatrix,
        gruppiere: gruppiere,
        bilanz: bilanz
    };
}(typeof window !== 'undefined' ? window : this));
