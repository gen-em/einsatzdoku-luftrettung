/**
 * Import — Bedienung der Seite import.php.
 *
 * Zustaendig fuer: Datei einlesen, Profil waehlen, Ergebnis anzeigen,
 * Korrekturen entgegennehmen, Konflikte mit dem Bestand aufloesen und die
 * fertige (verschluesselte) Nutzlast bauen. Die eigentliche Rechenarbeit
 * steckt in import.js, die Formatkenntnis in import_profiles.js.
 *
 * Erwartet aus der Seite: PAT_WRAP, CSRF, EdCrypto, ImportCore, ImportProfile.
 */
(function () {
    'use strict';

    var $ = function (id) { return document.getElementById(id); };

    var S = {
        mappe: null, profil: null, kopfzeile: null, mat: null, params: {},
        erg: null, tage: [], bestand: null,
        wahlZeile: {},      // srcRow -> { skip:bool, dup:'skip'|'overwrite'|'insert' }
        wahlTag: {},        // day    -> 'keep' | 'update'
        filter: 'alle',
        nutzlast: null
    };

    var SPALTEN_ANZEIGE = ['Datum', 'Zeit', 'Name', 'Geb.dat', 'Einsatzort', 'RTW',
        'Diagnose', 'Transport', 'Winde', 'HEMS', 'Pilot', 'Einsatz-Nr'];

    // ------------------------------------------------------------- Anzeigen

    function fehler(text) {
        var el = $('fehler');
        el.textContent = text || '';
        el.hidden = !text;
    }

    function esc(s) {
        return String(s === null || s === undefined ? '' : s)
            .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

    // ------------------------------------------------------- Profil und Datei

    function profileFuellen() {
        var sel = $('profil'), liste = window.ImportProfile.liste();
        sel.innerHTML = liste.map(function (p) {
            return '<option value="' + esc(p.id) + '">' + esc(p.label) + '</option>';
        }).join('');
    }

    function paramsFuellen(vorschlag) {
        var box = $('params');
        box.innerHTML = (S.profil.params || []).map(function (p) {
            var v = vorschlag[p.key];
            return '<label>' + esc(p.label) +
                ' <input type="' + (p.type === 'number' ? 'number' : 'text') +
                '" class="imp-param" data-key="' + esc(p.key) + '" value="' + esc(v) + '"></label>';
        }).join('');
        S.params = {};
        Object.keys(vorschlag).forEach(function (k) { S.params[k] = vorschlag[k]; });
    }

    function paramsLesen() {
        Array.prototype.forEach.call(document.querySelectorAll('.imp-param'), function (i) {
            S.params[i.dataset.key] = i.value;
        });
    }

    async function dateiGewaehlt(datei) {
        fehler('');
        try {
            var puffer = await datei.arrayBuffer();
            S.mappe = ImportCore.leseArbeitsmappe(new Uint8Array(puffer));
        } catch (e) {
            fehler('Die Datei konnte nicht gelesen werden: ' + e.message);
            return;
        }

        var erkannt = ImportCore.erkenneProfil(S.mappe, window.ImportProfile.liste());
        if (!erkannt) {
            fehler('Zu dieser Datei passt keines der bekannten Formate. Stimmen die '
                 + 'Spaltenüberschriften?');
            return;
        }
        S.profil = erkannt.profil;
        S.kopfzeile = erkannt.kopfzeile;
        $('profil').value = S.profil.id;

        paramsFuellen(ImportCore.paramVorschlaege(S.mappe, S.profil, S.kopfzeile, datei.name));
        S.mat = ImportCore.matrix(S.mappe, S.profil);
        S.wahlZeile = {}; S.wahlTag = {};
        await neuRechnen();
    }

    // ------------------------------------------------------------ Rechnen

    async function neuRechnen() {
        if (!S.mat || !S.profil) { return; }
        paramsLesen();
        try {
            S.erg = ImportCore.verarbeiteMatrix(S.mat, S.profil, S.params, S.kopfzeile);
        } catch (e) {
            fehler(e.message);
            return;
        }
        S.tage = ImportCore.gruppiere(S.erg.zeilen);
        await bestandPruefen();
        zeichnen();
    }

    /**
     * Abgleich mit dem Bestand — dem Server gehen nur noch Datum und Uhrzeit
     * hinaus, die Einsatznummer sieht er seit Web 2.9.0 nicht mehr (sie liegt
     * verschluesselt im pat_blob). Fuer den Abgleich ueber die Nummer liefert
     * 'check' je vorhandenem Einsatz zusaetzlich seinen pat_blob mit; der
     * Browser entschluesselt diese hier lokal und baut daraus den Index
     * missionNoIndex fuer dublette().
     */
    async function bestandPruefen() {
        var tage = S.tage.map(function (t) { return t.day; });
        if (!tage.length) { S.bestand = { days: {}, missionNoIndex: {} }; return; }
        try {
            var res = await fetch('api/import_commit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
                body: JSON.stringify({ action: 'check', days: tage })
            });
            var d = await res.json();
            if (d.error) { throw new Error(d.meldung || d.error); }
            S.bestand = d;
            S.bestand.missionNoIndex = await bestandEinsatznummernIndex(d);
        } catch (e) {
            S.bestand = { days: {}, missionNoIndex: {} };
            fehler('Der Abgleich mit den vorhandenen Einsätzen ist fehlgeschlagen ('
                 + e.message + '). Dubletten werden deshalb nicht erkannt.');
        }
    }

    /**
     * Entschluesselt die pat_blobs der vorhandenen Einsaetze aus der
     * 'check'-Antwort und liefert einen Index Einsatznummer -> Einsatz-Id.
     * Ist die Verschluesselung gesperrt, bleibt der Index leer — die
     * Dublettenpruefung greift dann nur noch ueber Tag und Alarmzeit.
     *
     * Bewusst in Kauf genommen: Erkannt werden Nummerndubletten nur noch
     * innerhalb der Flugtage, die in der Importdatei vorkommen (siehe
     * docs/Technik.md).
     */
    async function bestandEinsatznummernIndex(d) {
        var index = {};
        var ck = await EdCrypto.getContentKey(PAT_WRAP);
        if (!ck) { return index; }
        for (var tag in (d.days || {})) {
            if (!Object.prototype.hasOwnProperty.call(d.days, tag)) { continue; }
            var missions = d.days[tag].missions || [];
            for (var i = 0; i < missions.length; i++) {
                var m = missions[i];
                if (!m.pat_blob) { continue; }
                try {
                    var o = JSON.parse(await EdCrypto.decrypt(ck, m.pat_blob)) || {};
                    if (o.mission_no) { index[o.mission_no] = m.id; }
                } catch (e) { /* Blob passt nicht zum Schluessel — ueberspringen */ }
            }
        }
        return index;
    }

    /** Liegt dieser Einsatz schon vor? Erst ueber die Nummer, dann ueber Tag+Zeit. */
    function dublette(m) {
        var b = S.bestand || { days: {}, missionNoIndex: {} };
        var nummer = m.pat && m.pat.mission_no;
        if (nummer && b.missionNoIndex && b.missionNoIndex[nummer]) {
            return { grund: 'Einsatznummer bereits vergeben', id: b.missionNoIndex[nummer] };
        }
        var tag = b.days[m.day];
        if (tag) {
            for (var i = 0; i < tag.missions.length; i++) {
                if (tag.missions[i].hhmm === m.alarm) {
                    return { grund: 'Tag und Alarmzeit bereits vorhanden', id: tag.missions[i].id };
                }
            }
        }
        return null;
    }

    /** Weicht die Tagescrew aus der Datei von der gespeicherten ab? */
    function tagKonflikt(t) {
        var b = (S.bestand && S.bestand.days[t.day]) || null;
        if (!b || !b.crew) { return null; }
        var abw = [];
        ['p1', 'p2', 'hems', 'fr', 'other'].forEach(function (r) {
            var neu = t.crew[r] || null, alt = b.crew[r] || null;
            if (neu && alt && neu !== alt) { abw.push(r + ': ' + alt + ' → ' + neu); }
        });
        return abw.length ? abw.join(', ') : null;
    }

    // ------------------------------------------------------------ Zeichnen

    function zeileSichtbar(z, dup) {
        if (S.filter === 'probleme') { return z.status !== 'ok'; }
        if (S.filter === 'dubletten') { return !!dup; }
        return true;
    }

    function zelle(z, spaltenName) {
        var idx = S.erg.spalten[spaltenName];
        if (idx === undefined) { return '<td class="muted">–</td>'; }
        var roh = (S.mat[z.srcRow - 1] || [])[idx];
        var problem = null;
        z.issues.forEach(function (i) { if (i.spalte === spaltenName) { problem = i; } });
        var klasse = problem ? ('imp-' + problem.level) : '';
        return '<td class="' + klasse + '"' + (problem ? ' title="' + esc(problem.text) + '"' : '') +
            '><input class="imp-cell" data-row="' + (z.srcRow - 1) + '" data-col="' + idx +
            '" value="' + esc(roh === null || roh === undefined ? '' : roh) + '"></td>';
    }

    function aktionZelle(z, dup) {
        var w = S.wahlZeile[z.srcRow] || {};
        if (z.status === 'error') {
            return '<td><label class="imp-skip"><input type="checkbox" class="imp-skipbox" ' +
                'data-row="' + z.srcRow + '"' + (w.skip ? ' checked' : '') +
                '> überspringen</label></td>';
        }
        if (dup) {
            var v = w.dup || 'skip';
            return '<td title="' + esc(dup.grund) + '"><select class="imp-dup" data-row="' + z.srcRow + '">' +
                '<option value="skip"' + (v === 'skip' ? ' selected' : '') + '>überspringen</option>' +
                '<option value="overwrite"' + (v === 'overwrite' ? ' selected' : '') + '>überschreiben</option>' +
                '<option value="insert"' + (v === 'insert' ? ' selected' : '') + '>trotzdem anlegen</option>' +
                '</select></td>';
        }
        return '<td class="muted">neu</td>';
    }

    function zeichnen() {
        var tab = $('tabelle');
        var kopf = '<thead><tr><th>Zeile</th>' +
            SPALTEN_ANZEIGE.map(function (s) { return '<th>' + esc(s) + '</th>'; }).join('') +
            '<th>Aktion</th></tr></thead>';

        // Zeilen den Tagen zuordnen; Fehlerzeilen ohne verwertbares Datum
        // kommen zuerst, damit sie nicht untergehen.
        var proZeile = {}, nachSrcRow = {};
        S.erg.zeilen.forEach(function (z) { nachSrcRow[z.srcRow] = z; });
        S.tage.forEach(function (t) {
            t.missionen.forEach(function (m) { proZeile[m.srcRow] = { tag: t, mission: m }; });
        });

        var ohneTag = S.erg.zeilen.filter(function (z) { return !proZeile[z.srcRow]; });
        var koerper = '';
        var spaltenZahl = SPALTEN_ANZEIGE.length + 2;

        if (ohneTag.length) {
            koerper += '<tr class="imp-daygroup"><td colspan="' + spaltenZahl + '">' +
                'Nicht zuordenbar (' + ohneTag.length + ')</td></tr>';
            ohneTag.forEach(function (z) {
                if (!zeileSichtbar(z, null)) { return; }
                koerper += zeileHtml(z, null);
            });
        }

        S.tage.forEach(function (t) {
            var b = (S.bestand && S.bestand.days[t.day]) || null;
            var konflikt = tagKonflikt(t);
            var crewText = ['p1', 'hems'].map(function (r) {
                return r.toUpperCase() + ' ' + (t.crew[r] || '–');
            }).join(', ');
            var kopfZelle = '<strong>' + esc(t.day) + '</strong> · ' + esc(crewText) +
                ' · ' + t.missionen.length + ' Einsätze · ' +
                (b ? 'Flugtag vorhanden' : 'Flugtag wird angelegt');
            if (konflikt) {
                var w = S.wahlTag[t.day] || 'keep';
                kopfZelle += ' · <span class="imp-warn">abweichende Crew (' + esc(konflikt) + ')</span> ' +
                    '<select class="imp-daymode" data-day="' + esc(t.day) + '">' +
                    '<option value="keep"' + (w === 'keep' ? ' selected' : '') + '>gespeicherte Crew behalten</option>' +
                    '<option value="update"' + (w === 'update' ? ' selected' : '') + '>Crew aus der Datei übernehmen</option>' +
                    '</select>';
            }
            koerper += '<tr class="imp-daygroup"><td colspan="' + spaltenZahl + '">' + kopfZelle + '</td></tr>';

            t.missionen.forEach(function (m) {
                var z = nachSrcRow[m.srcRow];
                if (!z) { return; }
                var dup = dublette(m);
                if (!zeileSichtbar(z, dup)) { return; }
                koerper += zeileHtml(z, dup, m);
            });
        });

        tab.innerHTML = kopf + '<tbody>' + koerper + '</tbody>';

        var b = ImportCore.bilanz(S.erg.zeilen, S.tage);
        var dubletten = zaehleDubletten();
        $('bilanz').textContent = b.tage + ' Flugtage, ' + b.einsaetze + ' Einsätze, '
            + b.warnungen + ' Hinweise, ' + b.fehler + ' Fehler, ' + dubletten + ' Dubletten'
            + (b.abwCrew ? ', ' + b.abwCrew + ' Einsätze mit abweichender Besatzung' : '');
        $('schritt2').hidden = false;
        $('schritt3').hidden = false;
        bereitschaft();
    }

    function zeileHtml(z, dup, m) {
        var klasse = 'imp-row imp-' + z.status;
        if (dup) { klasse += ' imp-dupe'; }
        if ((S.wahlZeile[z.srcRow] || {}).skip) { klasse += ' imp-skipped'; }
        var hinweise = z.issues.map(function (i) { return i.spalte + ': ' + i.text; }).join(' | ');
        return '<tr class="' + klasse + '"' + (hinweise ? ' title="' + esc(hinweise) + '"' : '') + '>' +
            '<td class="muted">' + z.srcRow + (m && m.crew_override ? ' <span title="abweichende Besatzung">*</span>' : '') + '</td>' +
            SPALTEN_ANZEIGE.map(function (s) { return zelle(z, s); }).join('') +
            aktionZelle(z, dup) + '</tr>';
    }

    function zaehleDubletten() {
        var n = 0;
        S.tage.forEach(function (t) {
            t.missionen.forEach(function (m) { if (dublette(m)) { n++; } });
        });
        return n;
    }

    // --------------------------------------------------- Nutzlast + Bereitschaft

    /** Offene Fehlerzeilen = Fehler, die weder korrigiert noch übersprungen sind. */
    function offeneFehler() {
        return S.erg.zeilen.filter(function (z) {
            return z.status === 'error' && !(S.wahlZeile[z.srcRow] || {}).skip;
        }).length;
    }

    /**
     * Baut die fertige Nutzlast — einschliesslich Verschluesselung der
     * geschuetzten Angaben. Absichtlich schon hier und nicht erst beim
     * Absenden: Scheitert die Verschluesselung (Krypto gesperrt), soll das
     * VOR dem Klick auffallen und nicht mittendrin.
     */
    async function baueNutzlast() {
        var ck = await EdCrypto.getContentKey(PAT_WRAP);
        if (!ck) { return null; }

        var acId = $('acsel').value ? parseInt($('acsel').value, 10) : null;
        var baseId = $('basesel').value ? parseInt($('basesel').value, 10) : null;

        var tage = [], missionen = [], i, t, m, w, dup, vorhanden, pat, keys, blob;

        for (i = 0; i < S.tage.length; i++) {
            t = S.tage[i];
            vorhanden = (S.bestand && S.bestand.days[t.day]) || null;
            tage.push({
                day: t.day,
                crew_p1: t.crew.p1 || null,
                crew_p2: t.crew.p2 || null,
                crew_hems: t.crew.hems || null,
                crew_fr: t.crew.fr || null,
                crew_other: t.crew.other || null,
                aircraft_id: vorhanden ? null : acId,
                base_id: vorhanden ? null : baseId,
                // 'insert' = Tag anlegen, 'keep' = vorhandenen Tag unangetastet
                // lassen, 'update' = Crew aus der Datei uebernehmen.
                mode: !vorhanden ? 'insert' : (S.wahlTag[t.day] === 'update' ? 'update' : 'keep')
            });

            for (var j = 0; j < t.missionen.length; j++) {
                m = t.missionen[j];
                w = S.wahlZeile[m.srcRow] || {};
                if (w.skip) { continue; }
                dup = dublette(m);
                if (dup && (w.dup || 'skip') === 'skip') { continue; }

                // Nur belegte Felder verschluesseln; ist nichts belegt, bleibt
                // pat_blob leer statt ein leeres Objekt zu verschluesseln.
                pat = {};
                if (m.pat.mission_no) { pat.mission_no = m.pat.mission_no; }
                if (m.pat.last) { pat.last = m.pat.last; }
                if (m.pat.first) { pat.first = m.pat.first; }
                if (m.pat.dob) { pat.dob = m.pat.dob; }
                if (m.pat.dx) { pat.dx = m.pat.dx; }
                if (m.pat.loc && m.pat.loc.addr) { pat.loc = { addr: m.pat.loc.addr }; }
                keys = Object.keys(pat);
                blob = keys.length ? await EdCrypto.encrypt(ck, JSON.stringify(pat)) : null;

                missionen.push({
                    day: m.day,
                    started_local: m.alarm,
                    transport_dest: m.transport_dest,
                    winch: m.winch ? 1 : 0,
                    resources: m.resources || [],
                    crew_override: m.crew_override ? 1 : 0,
                    crew_p1: m.crew_p1 || null,
                    crew_p2: m.crew_p2 || null,
                    crew_hems: m.crew_hems || null,
                    crew_fr: m.crew_fr || null,
                    crew_other: m.crew_other || null,
                    pat_blob: blob,
                    dup: dup ? (w.dup || 'skip') : 'insert',
                    overwrite_id: (dup && w.dup === 'overwrite') ? dup.id : null
                });
            }
        }
        return { action: 'commit', days: tage, missions: missionen };
    }

    async function bereitschaft() {
        var offen = offeneFehler();
        var knopf = $('commit');
        if (offen) {
            S.nutzlast = null;
            $('bereit').textContent = offen + ' Zeile(n) mit Fehler sind weder korrigiert '
                + 'noch übersprungen. Der Import bleibt so lange gesperrt.';
            knopf.disabled = true;
            return;
        }
        S.nutzlast = await baueNutzlast();
        if (!S.nutzlast) {
            $('bereit').textContent = 'Die geschützten Angaben lassen sich nicht '
                + 'verschlüsseln — bitte ab- und neu anmelden.';
            knopf.disabled = true;
            return;
        }
        var mitBlob = S.nutzlast.missions.filter(function (m) { return m.pat_blob; }).length;
        var neueTage = S.nutzlast.days.filter(function (d) { return d.mode === 'insert'; }).length;
        $('bereit').textContent = S.nutzlast.missions.length + ' Einsätze bereit ('
            + mitBlob + ' davon mit verschlüsselten Angaben), ' + neueTage + ' Flugtage werden '
            + 'neu angelegt.';
        knopf.disabled = S.nutzlast.missions.length === 0;
    }

    /** Uebernahme ausloesen. Der Server macht daraus eine einzige Transaktion. */
    async function uebernehmen() {
        var knopf = $('commit'), zustand = $('commitstate');
        if (!S.nutzlast) { return; }
        knopf.disabled = true;
        zustand.textContent = 'Übernahme läuft …';
        try {
            var res = await fetch('api/import_commit.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json', 'X-CSRF': CSRF },
                body: JSON.stringify(S.nutzlast)
            });
            var d = await res.json();
            if (!d.ok) { throw new Error(d.meldung || d.error || ('HTTP ' + res.status)); }

            zustand.innerHTML = 'Fertig: ' + d.missions_inserted + ' Einsätze angelegt, '
                + d.missions_overwritten + ' überschrieben, ' + d.missions_skipped + ' übersprungen; '
                + d.days_inserted + ' Flugtage angelegt, ' + d.days_updated + ' aktualisiert.'
                + (d.first_day ? ' <a href="index.php?day=' + esc(d.first_day) + '">Ersten Tag öffnen</a>' : '');

            // Ein zweiter Klick wuerde alles ein weiteres Mal anlegen. Der Weg
            // zurueck fuehrt bewusst ueber eine neu gewaehlte Datei.
            $('schritt2').hidden = true;
            S.nutzlast = null;
            $('bereit').textContent = 'Übernommen. Für einen weiteren Import bitte erneut '
                + 'eine Datei wählen.';
        } catch (e) {
            zustand.textContent = 'Die Übernahme ist fehlgeschlagen: ' + e.message
                + ' — es wurde nichts gespeichert.';
            knopf.disabled = false;
        }
    }

    // ------------------------------------------------------------- Ereignisse

    function verdrahten() {
        $('datei').addEventListener('change', function (ev) {
            if (ev.target.files && ev.target.files[0]) { dateiGewaehlt(ev.target.files[0]); }
        });

        $('profil').addEventListener('change', function () {
            var p = window.ImportProfile.profiles[$('profil').value];
            if (!p || !S.mappe) { return; }
            S.profil = p;
            var g = ImportCore.findeKopfzeile(ImportCore.matrix(S.mappe, p), p);
            S.kopfzeile = g ? g.zeile : 0;
            S.mat = ImportCore.matrix(S.mappe, p);
            paramsFuellen(ImportCore.paramVorschlaege(S.mappe, p, S.kopfzeile, ''));
            neuRechnen();
        });

        $('params').addEventListener('change', function () { neuRechnen(); });
        $('commit').addEventListener('click', uebernehmen);
        $('acsel').addEventListener('change', function () { bereitschaft(); });
        $('basesel').addEventListener('change', function () { bereitschaft(); });

        Array.prototype.forEach.call(document.querySelectorAll('[data-filter]'), function (b) {
            b.addEventListener('click', function () {
                S.filter = b.dataset.filter;
                zeichnen();
            });
        });

        // Aenderungen in der Tabelle: erst bei Verlassen des Feldes (change),
        // nicht bei jedem Tastendruck — sonst wird die Tabelle unter den
        // Fingern neu gezeichnet.
        $('tabelle').addEventListener('change', function (ev) {
            var el = ev.target;
            if (el.classList.contains('imp-cell')) {
                S.mat[parseInt(el.dataset.row, 10)][parseInt(el.dataset.col, 10)] = el.value;
                neuRechnen();
            } else if (el.classList.contains('imp-skipbox')) {
                var r = el.dataset.row;
                S.wahlZeile[r] = S.wahlZeile[r] || {};
                S.wahlZeile[r].skip = el.checked;
                zeichnen();
            } else if (el.classList.contains('imp-dup')) {
                var r2 = el.dataset.row;
                S.wahlZeile[r2] = S.wahlZeile[r2] || {};
                S.wahlZeile[r2].dup = el.value;
                zeichnen();
            } else if (el.classList.contains('imp-daymode')) {
                S.wahlTag[el.dataset.day] = el.value;
                zeichnen();
            }
        });
    }

    // ---------------------------------------------------------------- Start

    (async function () {
        profileFuellen();
        verdrahten();
        var ck = await EdCrypto.getContentKey(PAT_WRAP);
        $('lockwarn').hidden = !!ck;
        $('datei').disabled = !ck;
    }());
}());
