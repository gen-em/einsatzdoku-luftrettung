/**
 * Export — Profil A (Standard-Excel) und Profil C (GuteSeele-Layout).
 *
 * Profil B (Vollständiges CSV inkl. GPX, ZIP mit optionaler AES-256-
 * Verschlüsselung via zip.js) ist seit Paket E3 mit umgesetzt.
 *
 * Erwartet aus der Seite: PAT_WRAP, KDF_SALT, CSRF, APP_TZ, WEB_VERSION,
 * EdCrypto, EdUnlock, EdPat,
 * ImportProfile, XLSX (vendor/xlsx.full.min.js), zip (vendor/zipjs.min.js),
 * edConfirm (confirm.js).
 *
 * WICHTIG — Grenzen der vendorierten SheetJS Community Edition (0.18.5):
 * Fette Schrift (Titel-/Kopfzeile) und Fensterfixierung lassen sich mit dieser
 * kostenlosen Ausgabe NICHT schreiben (geprüft: !freeze wird beim Schreiben
 * stillschweigend ignoriert, cell.s-Stile werden nicht in die styles.xml
 * übernommen — beides sind kostenpflichtige Pro-Funktionen von SheetJS).
 * Spaltenbreiten, Autofilter und echte Datumszellen funktionieren dagegen.
 * SPEC_Export.md 3.1 verlangt "fett" und "Fensterfixierung" — das wird hier
 * bewusst ausgelassen statt einer kaputten Datei. Bitte bei der Abnahme
 * gegenprüfen, ob das so tragbar ist oder eine andere Lösung nötig wird.
 */
(function () {
    'use strict';

    var $ = function (id) { return document.getElementById(id); };

    var DIALOG_PATIENT =
        'Die Datei enthält Patientendaten im Klartext. Ab dem Speichern schützt ' +
        'die Verschlüsselung dieser Anwendung die Daten nicht mehr — Name, ' +
        'Geburtsdatum, Diagnose und Einsatzort stehen lesbar in der Datei. ' +
        'Bewahre sie entsprechend auf und gib sie nicht unverschlüsselt weiter.';

    var DIALOG_PASSWORT =
        'Merke dir das Passwort. Es wird nirgends gespeichert und lässt sich ' +
        'nicht zurücksetzen — geht es verloren, lässt sich die Datei nicht ' +
        'mehr öffnen, und die Daten darin sind endgültig nicht mehr lesbar. ' +
        'Zum Öffnen wird ausserdem ein Zusatzprogramm gebraucht: Der ' +
        'Windows-Explorer und das Archivprogramm von macOS können ' +
        'AES-verschlüsselte Archive nicht öffnen. Unter Windows geht 7-Zip ' +
        '(7-zip.org), unter macOS Keka (keka.io) oder The Unarchiver — beide ' +
        'kostenlos. Ohne Passwort entsteht ein normales Archiv, das überall aufgeht.';

    var DIALOG_KEIN_BACKUP =
        'Dies ist kein Backup. Ein Export ist zum Weiterverarbeiten in anderen ' +
        'Programmen gedacht. Für eine vollständige Sicherung inklusive ' +
        'Verschlüsselung nutze Einstellungen → Backup.';

    /* ---------------------------------------------------------- Helfer --- */

    function pad2(n) { return String(n).padStart(2, '0'); }

    /** 'YYYY-MM-DD' -> lokales Date-Objekt (Mitternacht), fuer echte Datumszellen. */
    function dateOnly(dayStr) {
        var p = dayStr.split('-').map(Number);
        return new Date(p[0], p[1] - 1, p[2]);
    }

    /** 'YYYY-MM-DD' -> 'TT.MM.JJJJ' */
    function dmyDE(dayStr) {
        if (!dayStr) return '';
        var p = dayStr.split('-');
        return p[2] + '.' + p[1] + '.' + p[0];
    }

    /** ISO-8601-UTC ('...Z') -> {hour, minute, ...} in der App-Zeitzone. */
    function localParts(iso, tz) {
        if (!iso) return null;
        var d = new Date(iso);
        if (isNaN(d.getTime())) return null;
        var fmt = new Intl.DateTimeFormat('de-DE', {
            timeZone: tz, hour: '2-digit', minute: '2-digit', hour12: false
        });
        var out = {};
        fmt.formatToParts(d).forEach(function (p) { out[p.type] = p.value; });
        return out;
    }

    function hhmmLocal(iso, tz) {
        var p = localParts(iso, tz);
        if (!p) return null;
        var hh = p.hour === '24' ? '00' : p.hour;
        return hh + ':' + p.minute;
    }

    function phaseAt(mission, phaseNo) {
        var ph = (mission.phases || []).find(function (x) { return x.phase === phaseNo; });
        return ph ? ph.at : null;
    }

    /** Dauer zwischen zwei UTC-Zeitstempeln als 'HH:MM', oder null. */
    function durationHHMM(startIso, endIso) {
        if (!startIso || !endIso) return null;
        var ms = new Date(endIso).getTime() - new Date(startIso).getTime();
        if (!(ms >= 0)) return null;
        var mins = Math.round(ms / 60000);
        return pad2(Math.floor(mins / 60)) + ':' + pad2(mins % 60);
    }

    function jaOrDash(v) { return Number(v) === 1 ? 'Ja' : '-'; }
    function numOrDash(v) { return (v === null || v === undefined) ? '-' : v; }
    function txtOrDash(v) {
        v = (v === null || v === undefined) ? '' : String(v).trim();
        return v === '' ? '-' : v;
    }
    function orEmpty(v) { return (v === null || v === undefined) ? '' : String(v); }

    /** Effektive Besatzung nach 3.4: bei crew_override und belegtem Einsatzfeld
     *  der Einsatzwert, sonst der Wert des Flugtags. */
    function effectiveCrew(mission, dayRow) {
        var roles = ['p1', 'p2', 'hems', 'fr', 'other'];
        var out = {};
        roles.forEach(function (r) {
            var col = 'crew_' + r;
            var mVal = (mission[col] || '').toString().trim();
            var dVal = dayRow ? (dayRow[col] || '') : '';
            out[r] = (mission.crew_override === 1 && mVal !== '') ? mVal : dVal;
        });
        return out;
    }

    var MIME_XLSX = 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet';
    var MIME_ZIP = 'application/zip';

    function triggerDownload(bytes, filename, mime) {
        var blob = new Blob([bytes], { type: mime || MIME_XLSX });
        var url = URL.createObjectURL(blob);
        var a = document.createElement('a');
        a.href = url; a.download = filename;
        a.click();
        URL.revokeObjectURL(url);
    }

    function autoCols(headers) {
        return headers.map(function (h) {
            return { wch: Math.max(10, String(h).length + 2) };
        });
    }

    /* ------------------------------------------------ Daten laden/holen --- */

    async function fetchMeta(from, to, patient) {
        var res = await fetch('api/export_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
            body: JSON.stringify({ action: 'meta', from: from, to: to, patient: patient })
        });
        var data = await res.json();
        if (!res.ok || data.error) {
            throw new Error(data.meldung || ('Serverfehler (' + (data.error || res.status) + ')'));
        }
        return data;
    }

    /** Entschlüsselt pat_blob je Einsatz zu m.pat (oder null bei Fehler/Fehlen). */
    async function decryptPatients(missions, key) {
        for (var i = 0; i < missions.length; i++) {
            var m = missions[i];
            m.pat = null;
            if (!m.pat_blob) continue;
            try {
                m.pat = JSON.parse(await EdCrypto.decrypt(key, m.pat_blob));
            } catch (e) {
                m.pat = null;
            }
        }
    }

    /* ------------------------------------------------------- Profil A --- */

    var SPALTEN_A = [
        { label: 'Hubschrauber', star: false },
        { label: 'Standort', star: false },
        { label: 'Einsatzdatum', star: false },
        { label: 'Alarmzeit', star: false },
        { label: 'Endzeit', star: false },
        { label: 'Dauer', star: false },
        { label: 'Einsatznummer', star: true },
        { label: 'Nachname', star: true },
        { label: 'Vorname', star: true },
        { label: 'Geburtsdatum', star: true },
        { label: 'Alter', star: true },
        { label: 'Einsatzort', star: true },
        { label: 'Diagnose', star: true },
        { label: 'Pilot 1', star: false },
        { label: 'Pilot 2', star: false },
        { label: 'HEMS', star: false },
        { label: 'Flugretter', star: false },
        { label: 'Sonstige Besatzung', star: false },
        // 'Sekundärtransport' ist der Wortlaut aus mission_fields.php — die
        // Tabelle soll dieselben Begriffe verwenden wie das Formular.
        { label: 'Sekundärtransport', star: false },
        { label: 'Transportziel', star: false },
        { label: 'Schockraum', star: false },
        { label: 'Windeneinsatz', star: false },
        { label: 'Windenzyklen gesamt', star: false },
        { label: 'Bergwacht', star: false },
        { label: 'Bergwacht-Einheit', star: false },
        { label: 'Weitere Rettungsmittel', star: false },
        { label: 'Höhe Einsatzort (m)', star: false },
        { label: 'Flugkilometer', star: false },
        { label: 'Notizen', star: false }
    ];

    var DATE_COL_A = 2;   // 0-basiert, immer Spalte C (Einsatznummer* aendert daran nichts)

    function rowValuesA(ctx, cols) {
        return cols.map(function (c) {
            if (c.label === 'Hubschrauber') return txtOrDash(ctx.day && ctx.day.aircraft);
            if (c.label === 'Standort') return txtOrDash(ctx.day && ctx.day.base);
            if (c.label === 'Einsatzdatum') return dateOnly(ctx.tag);
            if (ctx.row === 'empty') return '-';   // alle übrigen Zellen '-'

            var m = ctx.m, pat = ctx.pat, eff = ctx.eff;
            switch (c.label) {
                case 'Alarmzeit': {
                    var hz = hhmmLocal(phaseAt(m, 2), APP_TZ);
                    return hz || '-';
                }
                case 'Endzeit': {
                    var he = hhmmLocal(phaseAt(m, 9), APP_TZ);
                    return he || '-';
                }
                case 'Dauer': {
                    var d = durationHHMM(phaseAt(m, 2), phaseAt(m, 9));
                    return d || '-';
                }
                case 'Einsatznummer': return pat ? txtOrDash(pat.mission_no) : '-';
                case 'Nachname': return pat ? txtOrDash(pat.last) : '-';
                case 'Vorname': return pat ? txtOrDash(pat.first) : '-';
                case 'Geburtsdatum': return pat && pat.dob ? EdPat.datumDe(pat.dob) : '-';
                case 'Alter': {
                    if (!pat || !pat.dob) return '-';
                    var alter = EdPat.alterAm(pat.dob, m.day);
                    return alter === null ? '-' : alter;
                }
                case 'Einsatzort': return pat && pat.loc ? txtOrDash(pat.loc.addr) : '-';
                case 'Diagnose': return pat ? txtOrDash(pat.dx) : '-';
                case 'Pilot 1': return txtOrDash(eff.p1);
                case 'Pilot 2': return txtOrDash(eff.p2);
                case 'HEMS': return txtOrDash(eff.hems);
                case 'Flugretter': return txtOrDash(eff.fr);
                case 'Sonstige Besatzung': return txtOrDash(eff.other);
                case 'Sekundärtransport': return jaOrDash(m.secondary);
                case 'Transportziel': return txtOrDash(m.transport_dest);
                case 'Schockraum': return jaOrDash(m.schockraum);
                case 'Windeneinsatz': return jaOrDash(m.winch);
                case 'Windenzyklen gesamt': return numOrDash(m.winch_cycles);
                case 'Bergwacht': return jaOrDash(m.bergwacht);
                case 'Bergwacht-Einheit': return txtOrDash(m.bw_unit);
                case 'Weitere Rettungsmittel':
                    return (m.resources && m.resources.length) ? m.resources.join(', ') : '-';
                case 'Höhe Einsatzort (m)': return numOrDash(m.site_ele_m);
                case 'Flugkilometer':
                    return (m.distance_m === null || m.distance_m === undefined)
                        ? '-' : Number((m.distance_m / 1000).toFixed(1));
                case 'Notizen': {
                    var n = (m.notes || '').replace(/\r\n|\r|\n/g, '; ').trim();
                    return n === '' ? '-' : n;
                }
                default: return '-';
            }
        });
    }

    function buildProfilA(data, opts) {
        var cols = SPALTEN_A.filter(function (c) { return !c.star || opts.patient; });
        var daysByDate = {};
        (data.days || []).forEach(function (d) { daysByDate[d.day] = d; });

        var missionDays = {};
        (data.missions || []).forEach(function (m) { missionDays[m.day] = true; });

        var rows = [];   // { tag, hhmm, row:'mission'|'empty', m, day, eff, pat }
        (data.missions || []).forEach(function (m) {
            rows.push({
                tag: m.day,
                hhmm: hhmmLocal(phaseAt(m, 2), APP_TZ) || '',
                row: 'mission', m: m,
                day: daysByDate[m.day],
                eff: effectiveCrew(m, daysByDate[m.day]),
                pat: opts.patient ? m.pat : null
            });
        });
        (data.days || []).forEach(function (d) {
            if (missionDays[d.day]) return;
            rows.push({ tag: d.day, hhmm: '', row: 'empty', day: d });
        });
        rows.sort(function (a, b) {
            return (a.tag + 'T' + a.hhmm).localeCompare(b.tag + 'T' + b.hhmm);
        });

        var aoa = [];
        aoa.push([opts.titel]);
        aoa.push([]);
        aoa.push(cols.map(function (c) { return c.label; }));
        rows.forEach(function (ctx) { aoa.push(rowValuesA(ctx, cols)); });

        var ws = XLSX.utils.aoa_to_sheet(aoa);
        // Datumszellen kommen von aoa_to_sheet als Typ 'n' (Zahl) mit einem
        // automatisch gesetzten .z ('m/d/yy') zurueck, NICHT als Typ 'd' —
        // deshalb hier ohne Typ-Pruefung ueberschreiben (siehe Testprotokoll).
        for (var r = 3; r < aoa.length; r++) {
            var ref = XLSX.utils.encode_cell({ r: r, c: DATE_COL_A });
            if (ws[ref]) { ws[ref].z = 'DD.MM.YYYY'; }
        }
        if (aoa.length > 3) {
            ws['!autofilter'] = {
                ref: XLSX.utils.encode_range(
                    { s: { r: 2, c: 0 }, e: { r: aoa.length - 1, c: cols.length - 1 } })
            };
        }
        ws['!cols'] = autoCols(cols.map(function (c) { return c.label; }));

        var wb = XLSX.utils.book_new();
        XLSX.utils.book_append_sheet(wb, ws, 'Einsätze');
        return { wb: wb, count: rows.length };
    }

    /* ------------------------------------------------------- Profil C --- */

    function buildProfilC(data, opts) {
        var profile = ImportProfile.profiles.ch17_jahresliste;
        var order = profile.exportOrder;
        var daysByDate = {};
        (data.days || []).forEach(function (d) { daysByDate[d.day] = d; });

        var byYear = {};
        (data.missions || []).forEach(function (m) {
            var y = m.day.slice(0, 4);
            (byYear[y] = byYear[y] || []).push(m);
        });
        var years = Object.keys(byYear).sort();
        if (!years.length) {
            years = [(opts.von || opts.bis || String(new Date().getFullYear())).slice(0, 4)];
            byYear[years[0]] = [];
        }

        var wb = XLSX.utils.book_new();
        var total = 0;

        years.forEach(function (year) {
            var missions = byYear[year].slice().sort(function (a, b) {
                return a.started_at.localeCompare(b.started_at);
            });
            total += missions.length;

            var aoa = [];
            var titleRow = new Array(6).fill(null);
            titleRow[5] = profile.exportTitle.replace('{jahr}', year);   // Spalte F
            aoa.push(titleRow);
            aoa.push([]);
            aoa.push(order.slice());

            missions.forEach(function (m) {
                var pat = opts.patient ? m.pat : null;
                var eff = effectiveCrew(m, daysByDate[m.day]);
                var p = m.day.split('-');
                var datum = String(parseInt(p[2], 10)) + '.' + String(parseInt(p[1], 10));
                var zeit = hhmmLocal(phaseAt(m, 2), APP_TZ) || '';

                var byLabel = {
                    'Datum': datum,
                    'Zeit': zeit,
                    'Name': pat ? EdPat.name(pat) : '',
                    'Geb.dat': (pat && pat.dob) ? EdPat.datumDe(pat.dob) : '',
                    'Vers.': '',
                    'Einsatzort': (pat && pat.loc) ? orEmpty(pat.loc.addr) : '',
                    'RTW': (m.resources && m.resources.length) ? m.resources.join(', ') : '',
                    'Diagnose': pat ? orEmpty(pat.dx) : '',
                    'Transport': orEmpty(m.transport_dest),
                    'Winde': Number(m.winch) === 1 ? 'j' : '',
                    'HEMS': orEmpty(eff.hems),
                    'Pilot': orEmpty(eff.p1),
                    'Einsatz-Nr': pat ? orEmpty(pat.mission_no) : ''
                };
                aoa.push(order.map(function (h) { return byLabel[h]; }));
            });

            var ws = XLSX.utils.aoa_to_sheet(aoa);
            ws['!cols'] = autoCols(order);
            XLSX.utils.book_append_sheet(wb, ws, year);
        });

        return { wb: wb, count: total };
    }

    /* ------------------------------------------------------- Profil B --- */
    /* Vollständiges CSV (einsaetze.csv, flugtage.csv, ruhezeiten.csv,
     * felder.csv, LIESMICH.txt, tracks/*.gpx), gepackt mit zip.js.
     * Erwartet zusätzlich: zip (vendor/zipjs.min.js). */

    // Muss mit db.php PHASE_LABELS/RESUS_LABELS uebereinstimmen. Eigene
    // Kopie, weil der Server hierfuer keinen Endpunkt anbietet (I2: keine
    // zusaetzlichen Aufrufe) — bei Aenderung an den Server-Konstanten bitte
    // hier mitziehen.
    var PHASE_SLUGS = {
        2: 'alarmierung', 3: 'abflug', 4: 'ankunft_einsatzort',
        5: 'ankunft_patientin', 6: 'transportbeginn', 7: 'landung_krankenhaus',
        8: 'uebergabezeit', 9: 'endzeit'
    };
    var RESUS_LABELS = {
        zugang: 'Zugang', beginn: 'Reanimationsbeginn', adrenalin: 'Adrenalingabe',
        rhythmuskontrolle: 'Rhythmuskontrolle', defibrillation: 'Defibrillation',
        intubation: 'Intubation', amiodaron: 'Amiodaron', sonographie: 'Sonographie',
        rosc: 'ROSC', tod: 'Tod'
    };

    function padId(id) { return String(id).padStart(6, '0'); }

    function chunkArr(arr, size) {
        var out = [];
        for (var i = 0; i < arr.length; i += size) { out.push(arr.slice(i, i + size)); }
        return out;
    }

    /** IANA-Zonenversatz einer konkreten UTC-Instanz plus lokale Kalenderteile
     *  ('en-US'-Trick: als UTC formatieren und die Differenz zum echten UTC-
     *  Zeitpunkt bilden — damit stimmt der Versatz auch bei Sommerzeit). */
    function tzOffsetParts(date, tz) {
        var dtf = new Intl.DateTimeFormat('en-US', {
            timeZone: tz, hour12: false, year: 'numeric', month: '2-digit', day: '2-digit',
            hour: '2-digit', minute: '2-digit', second: '2-digit'
        });
        var p = {};
        dtf.formatToParts(date).forEach(function (x) { if (x.type !== 'literal') p[x.type] = x.value; });
        var hh = p.hour === '24' ? '00' : p.hour;
        var asUTC = Date.UTC(Number(p.year), Number(p.month) - 1, Number(p.day),
            Number(hh), Number(p.minute), Number(p.second));
        return {
            y: p.year, mo: p.month, d: p.day, h: hh, mi: p.minute, s: p.second,
            offMin: Math.round((asUTC - date.getTime()) / 60000)
        };
    }

    /** ISO-8601-UTC -> ISO 8601 mit Zonenversatz in der App-Zeitzone,
     *  z. B. '2026-03-14T11:50:00+01:00'. */
    function isoOffset(iso, tz) {
        if (!iso) return '';
        var d = new Date(iso);
        if (isNaN(d.getTime())) return '';
        var p = tzOffsetParts(d, tz);
        var sign = p.offMin >= 0 ? '+' : '-';
        var abs = Math.abs(p.offMin);
        return p.y + '-' + p.mo + '-' + p.d + 'T' + p.h + ':' + p.mi + ':' + p.s
            + sign + pad2(Math.floor(abs / 60)) + ':' + pad2(abs % 60);
    }

    function durationMinutes(startIso, endIso) {
        if (!startIso || !endIso) return '';
        var ms = new Date(endIso).getTime() - new Date(startIso).getTime();
        return (ms >= 0) ? Math.round(ms / 60000) : '';
    }

    function csvEscape(v) {
        if (v === null || v === undefined) return '';
        var s = String(v);
        if (/[;"\r\n]/.test(s)) { s = '"' + s.replace(/"/g, '""') + '"'; }
        return s;
    }
    function csvRow(vals) { return vals.map(csvEscape).join(';') + '\r\n'; }
    function pipeList(arr) { return (arr && arr.length) ? arr.join('|') : ''; }

    /** Baut CSV-Text (mit UTF-8-BOM) aus einer Felddefinitions-Liste — dieselbe
     *  Liste liefert auch die Zeilen fuer felder.csv (buildFelderCsv).
     *
     *  SCHEMA IST STABIL: Die Spaltenliste haengt NICHT am Patientendaten-Haken.
     *  Ohne Haken bleiben die pat_-Spalten vorhanden und leer. Anders als Profil A
     *  (dort liest ein Mensch, und eine leere Spalte waere nur Ballast) liest hier
     *  eine Maschine — ein je nach Haken wechselnder Spaltensatz zwingt jeden
     *  Importer, zwei Faelle zu unterscheiden. Weicht bewusst von SPEC_Export.md
     *  4.3 ("Patientendaten entfallen ohne den Haken") ab; nach Ruecksprache
     *  entschieden. */
    function buildCsvText(fieldDefs, rows) {
        var out = '\uFEFF' + csvRow(fieldDefs.map(function (f) { return f.feld; }));
        rows.forEach(function (ctx) { out += csvRow(fieldDefs.map(function (f) { return f.get(ctx); })); });
        return out;
    }

    function buildReaJson(m, tz) {
        if (!m.resus || !m.resus.length) return '';
        return JSON.stringify(m.resus.map(function (s) {
            return {
                beginn: isoOffset(s.started_at, tz),
                ereignisse: (s.events || []).map(function (e) {
                    return { typ: e.type, bezeichnung: RESUS_LABELS[e.type] || e.type, zeit: isoOffset(e.at, tz) };
                })
            };
        }));
    }

    // ---- Feldtabellen (Reihenfolge = Spaltenreihenfolge in der CSV) -------

    var FIELD_DEFS_EINSAETZE = [
        { feld: 'einsatz_id', typ: 'int', einheit: '', beschreibung: 'interne ID, Bezugsschlüssel für tracks/', get: function (c) { return c.m.id; } },
        { feld: 'flugtag', typ: 'date', einheit: '', beschreibung: 'missions.day', get: function (c) { return c.m.day; } },
        { feld: 'datum', typ: 'date', einheit: '', beschreibung: 'identisch zu flugtag, für Tabellenprogramme', get: function (c) { return c.m.day; } },
        { feld: 'uhrzeit_ortszeit', typ: 'time', einheit: '', beschreibung: 'Alarmzeit HH:MM, für Tabellenprogramme', get: function (c) { return hhmmLocal(phaseAt(c.m, 2), APP_TZ) || ''; } },
        { feld: 'herkunft', typ: 'text', einheit: '', beschreibung: 'uhr | manuell | import', get: function (c) { return c.m.source; } },
        { feld: 'final', typ: '0/1', einheit: '', beschreibung: 'abgeschlossen', get: function (c) { return c.m.final; } },
        { feld: 'manual', typ: '0/1', einheit: '', beschreibung: 'Schutz: Uhr überschreibt Metadaten/Phasen/Rea nicht mehr (Herkunft siehe Spalte herkunft)', get: function (c) { return c.m.manual; } },

        { feld: 'hubschrauber', typ: 'text', einheit: '', beschreibung: 'Kennzeichen (Flugtag)', get: function (c) { return c.day ? orEmpty(c.day.aircraft) : ''; } },
        { feld: 'standort', typ: 'text', einheit: '', beschreibung: 'Basis (Flugtag)', get: function (c) { return c.day ? orEmpty(c.day.base) : ''; } },
        { feld: 'tag_crew_p1', typ: 'text', einheit: '', beschreibung: 'Besatzung des Flugtags: Pilot 1', get: function (c) { return c.day ? orEmpty(c.day.crew_p1) : ''; } },
        { feld: 'tag_crew_p2', typ: 'text', einheit: '', beschreibung: 'Besatzung des Flugtags: Pilot 2', get: function (c) { return c.day ? orEmpty(c.day.crew_p2) : ''; } },
        { feld: 'tag_crew_hems', typ: 'text', einheit: '', beschreibung: 'Besatzung des Flugtags: HEMS', get: function (c) { return c.day ? orEmpty(c.day.crew_hems) : ''; } },
        { feld: 'tag_crew_fr', typ: 'text', einheit: '', beschreibung: 'Besatzung des Flugtags: Flugretter', get: function (c) { return c.day ? orEmpty(c.day.crew_fr) : ''; } },
        { feld: 'tag_crew_other', typ: 'text', einheit: '', beschreibung: 'Besatzung des Flugtags: Sonstige', get: function (c) { return c.day ? orEmpty(c.day.crew_other) : ''; } },

        { feld: 'crew_abweichend', typ: '0/1', einheit: '', beschreibung: 'missions.crew_override', get: function (c) { return c.m.crew_override; } },
        { feld: 'crew_p1', typ: 'text', einheit: '', beschreibung: 'tatsächliche Besatzung: Pilot 1 (effektiv, siehe 3.4)', get: function (c) { return orEmpty(c.eff.p1); } },
        { feld: 'crew_p2', typ: 'text', einheit: '', beschreibung: 'tatsächliche Besatzung: Pilot 2', get: function (c) { return orEmpty(c.eff.p2); } },
        { feld: 'crew_hems', typ: 'text', einheit: '', beschreibung: 'tatsächliche Besatzung: HEMS', get: function (c) { return orEmpty(c.eff.hems); } },
        { feld: 'crew_fr', typ: 'text', einheit: '', beschreibung: 'tatsächliche Besatzung: Flugretter', get: function (c) { return orEmpty(c.eff.fr); } },
        { feld: 'crew_other', typ: 'text', einheit: '', beschreibung: 'tatsächliche Besatzung: Sonstige', get: function (c) { return orEmpty(c.eff.other); } },

        { feld: 'beginn', typ: 'ts', einheit: '', beschreibung: 'started_at', get: function (c) { return isoOffset(c.m.started_at, APP_TZ); } },
        { feld: 'ende', typ: 'ts', einheit: '', beschreibung: 'ended_at', get: function (c) { return isoOffset(c.m.ended_at, APP_TZ); } },
        { feld: 'dauer_min', typ: 'int', einheit: 'min', beschreibung: 'Phase 2 → Phase 9, leer wenn unvollständig', get: function (c) { return durationMinutes(phaseAt(c.m, 2), phaseAt(c.m, 9)); } },
    ]
        .concat([2, 3, 4, 5, 6, 7, 8, 9].map(function (n) {
            return {
                feld: 'phase_0' + n + '_' + PHASE_SLUGS[n], typ: 'ts', einheit: '',
                beschreibung: 'Zeitpunkt Phase ' + n + ' (' + PHASE_SLUGS[n] + ')',
                get: function (c) { return isoOffset(phaseAt(c.m, n), APP_TZ); }
            };
        }))
        .concat([2, 3, 4, 5, 6, 7, 8, 9].reduce(function (acc, n) {
            acc.push({
                feld: 'phase_0' + n + '_lat', typ: 'dec', einheit: '',
                beschreibung: 'Breitengrad Phase ' + n,
                get: function (c) { var p = (c.m.phases || []).find(function (x) { return x.phase === n; }); return p && p.lat !== null ? p.lat : ''; }
            });
            acc.push({
                feld: 'phase_0' + n + '_lon', typ: 'dec', einheit: '',
                beschreibung: 'Längengrad Phase ' + n,
                get: function (c) { var p = (c.m.phases || []).find(function (x) { return x.phase === n; }); return p && p.lon !== null ? p.lon : ''; }
            });
            return acc;
        }, []))
        .concat([
            { feld: 'strecke_m', typ: 'int', einheit: 'm', beschreibung: 'Flugstrecke (distance_m)', get: function (c) { return numOrEmpty(c.m.distance_m); } },
            { feld: 'hoehenmeter_m', typ: 'int', einheit: 'm', beschreibung: 'Höhenmeter (ascent_m)', get: function (c) { return numOrEmpty(c.m.ascent_m); } },
            { feld: 'hoehe_einsatzort_m', typ: 'int', einheit: 'm', beschreibung: 'Höhe des Einsatzorts', get: function (c) { return numOrEmpty(c.m.site_ele_m); } },

            { feld: 'transport_dest', typ: 'text', einheit: '', beschreibung: 'Transportziel', get: function (c) { return orEmpty(c.m.transport_dest); } },
            { feld: 'site_desc', typ: 'text', einheit: '', beschreibung: 'Beschreibung Einsatzort', get: function (c) { return orEmpty(c.m.site_desc); } },
            { feld: 'schockraum', typ: '0/1', einheit: '', beschreibung: 'Schockraum alarmiert', get: function (c) { return c.m.schockraum; } },
            { feld: 'secondary', typ: '0/1', einheit: '', beschreibung: 'Sekundärtransport', get: function (c) { return c.m.secondary; } },
            { feld: 'winch', typ: '0/1', einheit: '', beschreibung: 'Windeneinsatz', get: function (c) { return c.m.winch; } },
            { feld: 'winch_cycles', typ: 'int', einheit: '', beschreibung: 'Windenzyklen gesamt (Formular: „Cycles")', get: function (c) { return numOrEmpty(c.m.winch_cycles); } },
            { feld: 'winch_cycles_pat', typ: 'int', einheit: '', beschreibung: 'Windenzyklen mit PatientIn (Formular: „Cycles mit Patient")', get: function (c) { return numOrEmpty(c.m.winch_cycles_pat); } },
            { feld: 'winch_airload', typ: '0/1', einheit: '', beschreibung: 'Luftverladung', get: function (c) { return c.m.winch_airload; } },
            { feld: 'bergwacht', typ: '0/1', einheit: '', beschreibung: 'Bergwacht beteiligt', get: function (c) { return c.m.bergwacht; } },
            { feld: 'bw_unit', typ: 'text', einheit: '', beschreibung: 'Bergwacht-Einheit', get: function (c) { return orEmpty(c.m.bw_unit); } },
            { feld: 'bw_info', typ: 'text', einheit: '', beschreibung: 'Bergwacht: Namen / Infos', get: function (c) { return orEmpty(c.m.bw_info); } },
            { feld: 'other_ema', typ: 'text', einheit: '', beschreibung: 'Anderer Notarzt', get: function (c) { return orEmpty(c.m.other_ema); } },
            { feld: 'weitere_rettungsmittel', typ: 'text', einheit: '', beschreibung: 'mission_resources.name, mit | verkettet', get: function (c) { return pipeList(c.m.resources); } },
            { feld: 'notizen', typ: 'text', einheit: '', beschreibung: 'missions.notes', get: function (c) { return orEmpty(c.m.notes); } },

            { feld: 'pat_mission_no', typ: 'text', einheit: '', beschreibung: 'Einsatznummer (pat_blob.mission_no)', patient: true, get: function (c) { return c.pat ? orEmpty(c.pat.mission_no) : ''; } },
            { feld: 'pat_nachname', typ: 'text', einheit: '', beschreibung: 'pat_blob.last', patient: true, get: function (c) { return c.pat ? orEmpty(c.pat.last) : ''; } },
            { feld: 'pat_vorname', typ: 'text', einheit: '', beschreibung: 'pat_blob.first', patient: true, get: function (c) { return c.pat ? orEmpty(c.pat.first) : ''; } },
            { feld: 'pat_geburtsdatum', typ: 'date', einheit: '', beschreibung: 'pat_blob.dob', patient: true, get: function (c) { return c.pat ? orEmpty(c.pat.dob) : ''; } },
            { feld: 'pat_diagnose', typ: 'text', einheit: '', beschreibung: 'pat_blob.dx', patient: true, get: function (c) { return c.pat ? orEmpty(c.pat.dx) : ''; } },
            { feld: 'pat_ort_adresse', typ: 'text', einheit: '', beschreibung: 'pat_blob.loc.addr', patient: true, get: function (c) { return (c.pat && c.pat.loc) ? orEmpty(c.pat.loc.addr) : ''; } },
            { feld: 'pat_ort_lat', typ: 'dec', einheit: '', beschreibung: 'pat_blob.loc.lat', patient: true, get: function (c) { return (c.pat && c.pat.loc && c.pat.loc.lat != null) ? c.pat.loc.lat : ''; } },
            { feld: 'pat_ort_lon', typ: 'dec', einheit: '', beschreibung: 'pat_blob.loc.lon', patient: true, get: function (c) { return (c.pat && c.pat.loc && c.pat.loc.lon != null) ? c.pat.loc.lon : ''; } },

            { feld: 'rea_json', typ: 'json', einheit: '', beschreibung: 'Reanimationssitzungen mit Ereignissen, siehe 4.4; leer wenn keine Reanimation', get: function (c) { return buildReaJson(c.m, APP_TZ); } },
            { feld: 'track_datei', typ: 'text', einheit: '', beschreibung: 'relativer Pfad unter tracks/, oder leer', get: function (c) { return c.trackFile || ''; } },
            { feld: 'track_punkte', typ: 'int', einheit: '', beschreibung: 'Anzahl Trackpunkte', get: function (c) { return c.m.track_points; } }
        ]);

    var FIELD_DEFS_FLUGTAGE = [
        { feld: 'flugtag', typ: 'date', einheit: '', beschreibung: 'days.day', get: function (c) { return c.d.day; } },
        { feld: 'hubschrauber', typ: 'text', einheit: '', beschreibung: 'Kennzeichen', get: function (c) { return orEmpty(c.d.aircraft); } },
        { feld: 'standort', typ: 'text', einheit: '', beschreibung: 'Basis', get: function (c) { return orEmpty(c.d.base); } },
        { feld: 'crew_p1', typ: 'text', einheit: '', beschreibung: 'Pilot 1', get: function (c) { return orEmpty(c.d.crew_p1); } },
        { feld: 'crew_p2', typ: 'text', einheit: '', beschreibung: 'Pilot 2', get: function (c) { return orEmpty(c.d.crew_p2); } },
        { feld: 'crew_hems', typ: 'text', einheit: '', beschreibung: 'HEMS', get: function (c) { return orEmpty(c.d.crew_hems); } },
        { feld: 'crew_fr', typ: 'text', einheit: '', beschreibung: 'Flugretter', get: function (c) { return orEmpty(c.d.crew_fr); } },
        { feld: 'crew_other', typ: 'text', einheit: '', beschreibung: 'Sonstige', get: function (c) { return orEmpty(c.d.crew_other); } },
        { feld: 'notizen', typ: 'text', einheit: '', beschreibung: 'days.notes', get: function (c) { return orEmpty(c.d.notes); } },
        { feld: 'anzahl_einsaetze', typ: 'int', einheit: '', beschreibung: 'Anzahl Einsätze an diesem Flugtag im Export', get: function (c) { return c.count; } }
    ];

    var FIELD_DEFS_RUHEZEITEN = [
        { feld: 'ruhezeit_id', typ: 'int', einheit: '', beschreibung: 'interne ID, Bezugsschlüssel für tracks/', get: function (c) { return c.r.id; } },
        { feld: 'flugtag', typ: 'date', einheit: '', beschreibung: 'rest_segments.day', get: function (c) { return c.r.day; } },
        { feld: 'beginn', typ: 'ts', einheit: '', beschreibung: 'started_at', get: function (c) { return isoOffset(c.r.started_at, APP_TZ); } },
        { feld: 'ende', typ: 'ts', einheit: '', beschreibung: 'ended_at', get: function (c) { return isoOffset(c.r.ended_at, APP_TZ); } },
        { feld: 'dauer_min', typ: 'int', einheit: 'min', beschreibung: 'ende − beginn', get: function (c) { return durationMinutes(c.r.started_at, c.r.ended_at); } },
        { feld: 'final', typ: '0/1', einheit: '', beschreibung: 'abgeschlossen', get: function (c) { return c.r.final; } },
        { feld: 'track_datei', typ: 'text', einheit: '', beschreibung: 'relativer Pfad unter tracks/, oder leer', get: function (c) { return c.trackFile || ''; } },
        { feld: 'track_punkte', typ: 'int', einheit: '', beschreibung: 'Anzahl Trackpunkte', get: function (c) { return c.r.track_points; } }
    ];

    function numOrEmpty(v) { return (v === null || v === undefined) ? '' : v; }

    /** felder.csv beschreibt IMMER den vollen Formatumfang — auch die
     *  pat_-Spalten, die ohne Haken leer bleiben. Die Datei ist die
     *  Formatbeschreibung, nicht ein Inhaltsverzeichnis dieses einen Laufs. */
    function buildFelderCsv() {
        var tables = [
            ['einsaetze.csv', FIELD_DEFS_EINSAETZE],
            ['flugtage.csv', FIELD_DEFS_FLUGTAGE],
            ['ruhezeiten.csv', FIELD_DEFS_RUHEZEITEN]
        ];
        var out = '\uFEFF' + csvRow(['datei', 'feld', 'typ', 'einheit', 'beschreibung']);
        tables.forEach(function (t) {
            var datei = t[0];
            t[1].forEach(function (f) {
                out += csvRow([datei, f.feld, f.typ, f.einheit || '', f.beschreibung]);
            });
        });
        return out;
    }

    function buildLiesmich(opts) {
        var now = new Date();
        var erzeugtDatum = tzOffsetParts(now, APP_TZ);
        var erzeugt = erzeugtDatum.d + '.' + erzeugtDatum.mo + '.' + erzeugtDatum.y
            + ' ' + erzeugtDatum.h + ':' + erzeugtDatum.mi + ' (' + APP_TZ + ')';
        var zeitraum = opts.von ? (opts.von + ' bis ' + opts.bis) : 'gesamter Zeitraum';
        return [
            'Einsatzdoku — Export (vollständiges CSV)',
            '========================================',
            '',
            'Erzeugt am: ' + erzeugt,
            'App-Version: Web ' + WEB_VERSION,
            'Zeitraum: ' + zeitraum,
            'Patientendaten enthalten: ' + (opts.patient ? 'ja' : 'nein'),
            '',
            'Der Spaltensatz ist in jedem Export gleich. Sind keine Patientendaten',
            'enthalten, bleiben die pat_-Spalten vorhanden und leer — ein Programm,',
            'das diese Dateien einliest, muss deshalb nicht zwei Fälle unterscheiden.',
            'felder.csv beschreibt immer den vollen Formatumfang.',
            '',
            'Dateien in diesem Archiv:',
            '  felder.csv       jedes Feld jeder Tabelle: datei;feld;typ;einheit;beschreibung',
            '  einsaetze.csv    eine Zeile je Einsatz — vollständig',
            '  flugtage.csv     eine Zeile je Flugtag, auch ohne Einsatz',
            '  ruhezeiten.csv   eine Zeile je Ruhesegment',
            '  tracks/          GPX-Dateien, nur bei aktiviertem Haken; Namen enthalten',
            '                   keinen Patientenbezug (nur Datum, Uhrzeit, interne ID)',
            '',
            'CSV-Konventionen: Trennzeichen Semikolon, Zeichensatz UTF-8 mit BOM,',
            'Zeilenende CRLF, Quoting nach RFC 4180. Zeitstempel als ISO 8601 mit',
            'Zonenversatz (z. B. 2026-03-14T11:50:00+01:00). Datum ohne Zeit als',
            'JJJJ-MM-TT. Wahrheitswerte als 1/0. Leere Werte als leeres Feld.',
            'Dezimaltrennzeichen ist der Punkt — das schließt beim Semikolon als',
            'Feldtrenner Missverständnisse mit anderen Ländern aus, die das Komma',
            'als Dezimaltrennzeichen verwenden. Mehrfachwerte in einer Zelle mit |',
            'getrennt, ohne Leerzeichen.',
            '',
            'hubschrauber, standort und die Tagesbesatzung stehen sowohl in',
            'einsaetze.csv als auch in flugtage.csv — beabsichtigt, damit die',
            'Einsatztabelle allein ein vollständiges Bild ergibt. Bei Abweichungen',
            'gilt einsaetze.csv; flugtage.csv wird nur für Tage ohne Einsatz und',
            'für Tagesnotizen gebraucht.',
            ''
        ].join('\r\n');
    }

    function xmlEscape(s) {
        return String(s).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;').replace(/'/g, '&apos;');
    }

    /** GPX 1.1, ein <trk> mit einem <trkseg>. Punkte kommen sortiert vom Server. */
    function buildGpx(points, name, genTimeIso) {
        var trkpts = points.map(function (p) {
            var lat = p[0], lon = p[1], ele = p[2], ts = p[3];
            var timeIso = new Date(ts * 1000).toISOString();
            var eleTag = (ele === null || ele === undefined) ? '' : ('<ele>' + ele + '</ele>');
            return '<trkpt lat="' + lat + '" lon="' + lon + '">' + eleTag + '<time>' + timeIso + '</time></trkpt>';
        }).join('');
        return '<?xml version="1.0" encoding="UTF-8"?>\n'
            + '<gpx version="1.1" creator="Einsatzdoku" xmlns="http://www.topografix.com/GPX/1/1">\n'
            + '<metadata><time>' + genTimeIso + '</time></metadata>\n'
            + '<trk><name>' + xmlEscape(name) + '</name><trkseg>' + trkpts + '</trkseg></trk>\n'
            + '</gpx>';
    }

    async function fetchTrack(ownerType, ids) {
        var res = await fetch('api/export_data.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
            body: JSON.stringify({ action: 'track', owner_type: ownerType, ids: ids })
        });
        if (!res.ok) { throw new Error('Serverfehler beim Laden der Tracks (' + res.status + ').'); }
        return res.json();
    }

    /** Holt GPX-relevante Tracks blockweise (höchstens 25 IDs je Anfrage, siehe
     *  api/export_data.php) und baut die GPX-Dateien. Liefert außerdem, welche
     *  mission_id/rest_id welche Datei bekommen hat (für track_datei in den CSVs). */
    async function buildTracks(data, onProgress) {
        var genTime = new Date().toISOString();
        var files = [];
        var fileByMission = {}, fileByRest = {};

        var missionIds = (data.missions || []).filter(function (m) { return m.track_points > 0; }).map(function (m) { return m.id; });
        var restIds = (data.rests || []).filter(function (r) { return r.track_points > 0; }).map(function (r) { return r.id; });
        var total = missionIds.length + restIds.length;
        var done = 0;
        if (onProgress) { onProgress('Tracks 0 / ' + total); }

        for (var b1 = 0; b1 < missionIds.length; b1 += 25) {
            var batch1 = missionIds.slice(b1, b1 + 25);
            var res1 = await fetchTrack('mission', batch1);
            batch1.forEach(function (id) {
                var pts = res1[String(id)] || [];
                if (!pts.length) return;
                var m = data.missions.find(function (x) { return x.id === id; });
                var hhmm = (hhmmLocal(phaseAt(m, 2), APP_TZ) || '00:00').replace(':', '');
                var relPath = 'tracks/mission_' + padId(id) + '_' + m.day + '_' + hhmm + '.gpx';
                fileByMission[id] = relPath;   // relativer Pfad, so wie einsaetze.csv ihn braucht
                var gpxName = 'Einsatz ' + id + ' — ' + dmyDE(m.day) + ' ' + (hhmmLocal(phaseAt(m, 2), APP_TZ) || '');
                files.push({ name: relPath, content: buildGpx(pts, gpxName, genTime) });
            });
            done += batch1.length;
            if (onProgress) { onProgress('Tracks ' + done + ' / ' + total); }
        }

        for (var b2 = 0; b2 < restIds.length; b2 += 25) {
            var batch2 = restIds.slice(b2, b2 + 25);
            var res2 = await fetchTrack('rest', batch2);
            batch2.forEach(function (id) {
                var pts = res2[String(id)] || [];
                if (!pts.length) return;
                var r = data.rests.find(function (x) { return x.id === id; });
                var hhmm = (hhmmLocal(r.started_at, APP_TZ) || '00:00').replace(':', '');
                var relPath = 'tracks/rest_' + padId(id) + '_' + r.day + '_' + hhmm + '.gpx';
                fileByRest[id] = relPath;
                var gpxName = 'Ruhezeit ' + id + ' — ' + dmyDE(r.day) + ' ' + (hhmmLocal(r.started_at, APP_TZ) || '');
                files.push({ name: relPath, content: buildGpx(pts, gpxName, genTime) });
            });
            done += batch2.length;
            if (onProgress) { onProgress('Tracks ' + done + ' / ' + total); }
        }

        return { files: files, fileByMission: fileByMission, fileByRest: fileByRest };
    }

    async function buildProfilB(data, opts) {
        var onProgress = opts.onProgress || function () {};
        var daysByDate = {};
        (data.days || []).forEach(function (d) { daysByDate[d.day] = d; });
        var countByDay = {};
        (data.missions || []).forEach(function (m) { countByDay[m.day] = (countByDay[m.day] || 0) + 1; });

        var tracks = opts.gpx
            ? await buildTracks(data, onProgress)
            : { files: [], fileByMission: {}, fileByRest: {} };

        onProgress('Einsätze werden aufbereitet…');
        var einsRows = (data.missions || []).map(function (m) {
            return {
                m: m, day: daysByDate[m.day], eff: effectiveCrew(m, daysByDate[m.day]),
                pat: opts.patient ? m.pat : null, trackFile: tracks.fileByMission[m.id] || ''
            };
        });
        var einsaetzeCsv = buildCsvText(FIELD_DEFS_EINSAETZE, einsRows);

        var tageRows = (data.days || []).map(function (d) {
            return { d: d, count: countByDay[d.day] || 0 };
        });
        var flugtageCsv = buildCsvText(FIELD_DEFS_FLUGTAGE, tageRows);

        var restRows = (data.rests || []).map(function (r) {
            return { r: r, trackFile: tracks.fileByRest[r.id] || '' };
        });
        var ruhezeitenCsv = buildCsvText(FIELD_DEFS_RUHEZEITEN, restRows);

        var files = [
            { name: 'LIESMICH.txt', content: buildLiesmich(opts) },
            { name: 'felder.csv', content: buildFelderCsv() },
            { name: 'einsaetze.csv', content: einsaetzeCsv },
            { name: 'flugtage.csv', content: flugtageCsv },
            { name: 'ruhezeiten.csv', content: ruhezeitenCsv }
        ].concat(tracks.files);

        return { files: files, count: einsRows.length, tracks: tracks.files.length };
    }

    /** Packt Dateien (Text oder Uint8Array) mit zip.js — mit Passwort
     *  AES-256/WinZip (encryptionStrength 3), sonst unverschlüsselt (6.). */
    async function zipAndDownload(files, password, filename) {
        var writer = new zip.Uint8ArrayWriter();
        var zwOpts = password ? { password: password, encryptionStrength: 3 } : {};
        var zw = new zip.ZipWriter(writer, zwOpts);
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            var reader = (typeof f.content === 'string')
                ? new zip.TextReader(f.content)
                : new zip.Uint8ArrayReader(f.content);
            await zw.add(f.name, reader);
        }
        await zw.close();
        var bytes = await writer.getData();
        triggerDownload(bytes, filename, MIME_ZIP);
    }

    /* --------------------------------------------------------- Steuerung --- */

    var state = null;

    function syncZeitraum() {
        var alles = document.querySelector('input[name="exp_zr"]:checked').value === 'all';
        $('exp_von').disabled = alles;
        $('exp_bis').disabled = alles;
    }

    function gewaehltesFormat() { return $('exp_fmt').value; }

    function syncFormat() {
        $('exp_gpx_row').hidden = (gewaehltesFormat() !== 'b');
    }

    /** Dateiname nach dem Muster
     *  luftrettungsdokumentation_export_TT-MM-JJJJ_<profil>.<endung>
     *  Das Datum ist der Tag der Erstellung, nicht der Zeitraum — der steht in
     *  der Datei selbst (Titelzeile bzw. LIESMICH.txt). */
    var PROFIL_KUERZEL = { a: 'standard', c: 'guteseele', b: 'csv' };

    function dateiName(fmt, endung) {
        var j = new Date();
        var datum = pad2(j.getDate()) + '-' + pad2(j.getMonth() + 1) + '-' + j.getFullYear();
        return 'luftrettungsdokumentation_export_' + datum + '_'
            + (PROFIL_KUERZEL[fmt] || 'export') + '.' + endung;
    }

    /* Prueft den Inhaltsschluessel und bietet bei Bedarf den Entsperrdialog
     * an. Laeuft parallel zum gleichlautenden Aufruf in import_ui.js — der
     * Sperrmechanismus in unlock.js sorgt dafuer, dass trotzdem nur ein
     * einziger Dialog erscheint. */
    async function syncPatientLock() {
        var key = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT);
        var cb = $('exp_pat');
        var hint = $('exp_pat_hint');
        if (!key) {
            cb.checked = false; cb.disabled = true; hint.hidden = false;
        } else {
            cb.disabled = false; hint.hidden = true;
        }
        return key;
    }

    /** Passwortfelder müssen übereinstimmen, Mindestlänge 8 — sonst bleibt
     *  der Knopf gesperrt mit Begründung (1.1). */
    function syncPasswordGate() {
        var on = $('exp_pw').checked;
        $('exp_pw_fields').hidden = !on;
        var reason = '';
        if (on) {
            var p1 = $('exp_pw1').value, p2 = $('exp_pw2').value;
            if (p1.length < 8) { reason = 'Passwort muss mindestens 8 Zeichen haben.'; }
            else if (p1 !== p2) { reason = 'Passwörter stimmen nicht überein.'; }
        }
        $('exp_go').disabled = !!reason;
        // Nur die eigene Begruendung setzen bzw. wieder wegnehmen. Ein
        // pauschales setState('') wuerde sonst jede Erfolgs- oder
        // Fehlermeldung des letzten Exports loeschen, sobald hier etwas
        // umgeschaltet wird.
        if (reason) { setState(reason); }
        else if (/^Passw/.test($('exp_state').textContent)) { setState(''); }
    }

    function setState(text) { $('exp_state').textContent = text || ''; }

    async function runExport() {
        var fmt = gewaehltesFormat();

        var alles = document.querySelector('input[name="exp_zr"]:checked').value === 'all';
        var von = alles ? null : $('exp_von').value;
        var bis = alles ? null : $('exp_bis').value;
        if (!alles && (!von || !bis)) {
            setState('Bitte Von- und Bis-Datum angeben (oder „Alles" wählen).');
            return;
        }
        if (!alles && von > bis) {
            setState('„Von" liegt nach „Bis".');
            return;
        }

        var patient = $('exp_pat').checked;
        var pwOn = $('exp_pw').checked;
        var password = pwOn ? $('exp_pw1').value : null;
        if (pwOn && (password.length < 8 || password !== $('exp_pw2').value)) {
            syncPasswordGate();
            return;
        }
        var gpx = (fmt === 'b') ? $('exp_gpx').checked : false;

        var goBtn = $('exp_go');
        goBtn.disabled = true;
        try {
            // Die Rueckfragen liegen bewusst INNERHALB von try: Scheitert hier
            // etwas, soll es als Meldung sichtbar werden. Vorher standen sie
            // davor — ein Fehler im Dialog brach den Export dann still ab,
            // ohne dass irgendetwas unter dem Knopf stand.
            if (patient) {
                if (!await window.edConfirm(DIALOG_PATIENT, 'Verstanden, fortfahren', 'normal')) { return; }
            }
            if (pwOn) {
                if (!await window.edConfirm(DIALOG_PASSWORT, 'Verstanden, fortfahren', 'normal')) { return; }
            }
            if (!await window.edConfirm(DIALOG_KEIN_BACKUP, 'Export erstellen', 'normal')) { return; }

            setState('Daten werden geladen…');
            var data = await fetchMeta(von, bis, patient);

            if (patient) {
                var key = await syncPatientLock();
                if (!key) { setState('Entschlüsselung gesperrt — siehe Hinweis oben.'); return; }
                setState('Geschützte Angaben werden entschlüsselt…');
                await decryptPatients(data.missions || [], key);
            }

            var titel = alles
                ? 'Einsatzdokumentation – gesamter Zeitraum'
                : 'Einsatzdokumentation ' + dmyDE(von) + ' – ' + dmyDE(bis);

            var built;
            if (fmt === 'a' || fmt === 'c') {
                setState('Datei wird erstellt…');
                built = (fmt === 'a')
                    ? buildProfilA(data, { patient: patient, titel: titel })
                    : buildProfilC(data, { patient: patient, von: von, bis: bis });
                var bytesXlsx = XLSX.write(built.wb, { type: 'array', bookType: 'xlsx' });
                if (pwOn) {
                    setState('Datei wird verschlüsselt…');
                    await zipAndDownload(
                        [{ name: dateiName(fmt, 'xlsx'), content: new Uint8Array(bytesXlsx) }],
                        password, dateiName(fmt, 'zip'));
                } else {
                    triggerDownload(bytesXlsx, dateiName(fmt, 'xlsx'), MIME_XLSX);
                }
            } else {
                built = await buildProfilB(data, {
                    patient: patient, gpx: gpx, von: von, bis: bis, onProgress: setState
                });
                setState('Archiv wird ' + (pwOn ? 'verschlüsselt und ' : '') + 'gepackt…');
                await zipAndDownload(built.files, pwOn ? password : null, dateiName(fmt, 'zip'));
            }

            // Die Trackzahl steht bewusst mit in der Meldung: Ob ein Archiv
            // GPX-Dateien enthaelt, sieht man ihm sonst erst nach dem Entpacken
            // an — und "keine Tracks vorhanden" ist etwas anderes als
            // "Tracks vergessen".
            var schluss = 'Fertig: ' + built.count + ' Einsätze exportiert.';
            if (fmt === 'b') {
                if (!gpx) {
                    schluss += ' GPX-Tracks waren abgewählt.';
                } else if (built.tracks) {
                    schluss += ' ' + built.tracks + ' GPX-Tracks enthalten.';
                } else {
                    schluss += ' Keine GPX-Tracks — im gewählten Zeitraum sind '
                             + 'zu keinem Einsatz Trackpunkte gespeichert.';
                }
            }
            setState(schluss);
        } catch (e) {
            setState('Export fehlgeschlagen: ' + e.message);
        } finally {
            goBtn.disabled = false;
        }
    }

    document.addEventListener('DOMContentLoaded', function () {
        if (!$('exp_go')) return;   // Export-Block nicht auf der Seite

        document.querySelectorAll('input[name="exp_zr"]').forEach(function (r) {
            r.addEventListener('change', syncZeitraum);
        });
        $('exp_fmt').addEventListener('change', syncFormat);
        $('exp_pw').addEventListener('change', syncPasswordGate);
        $('exp_pw1').addEventListener('input', syncPasswordGate);
        $('exp_pw2').addEventListener('input', syncPasswordGate);

        var unlockBtn = $('exp_pat_unlock');
        if (unlockBtn) { unlockBtn.addEventListener('click', function () { syncPatientLock(); }); }

        syncZeitraum();
        syncFormat();
        syncPatientLock();
        syncPasswordGate();

        $('exp_go').addEventListener('click', runExport);
    });
})();
