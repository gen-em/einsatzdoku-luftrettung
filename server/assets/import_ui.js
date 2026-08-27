/**
 * Import — Bedienung der Seite import.php.
 *
 * Zustaendig fuer: Datei einlesen, Profil waehlen, Ergebnis anzeigen,
 * Korrekturen entgegennehmen, Konflikte mit dem Bestand aufloesen und die
 * fertige (verschluesselte) Nutzlast bauen. Die eigentliche Rechenarbeit
 * steckt in import.js, die Formatkenntnis in import_profiles.js.
 *
 * Erwartet aus der Seite: PAT_WRAP, KDF_SALT, KDF_ITER, CSRF, EdCrypto, EdUnlock,
 * ImportCore, ImportProfile.
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

    /**
     * Spalten der Pruef-Tabelle. Jedes Profil bringt seine eigene Liste mit
     * (reviewColumns) — das vollstaendige CSV hat 75 Spalten, die als Tabelle
     * niemand mehr lesen koennte. Ohne Angabe werden alle uebernommenen
     * Spalten des Profils gezeigt.
     */
    function anzeigeSpalten() {
        if (!S.profil) { return []; }
        if (S.profil.reviewColumns) { return S.profil.reviewColumns; }
        return Object.keys(S.profil.columns).filter(function (n) {
            return !!S.profil.columns[n].target;
        });
    }

    // ------------------------------------------------------------- Anzeigen

    /* Fehler und Warnungen als MELDUNGS-BAUSTEIN (E-P3-16, ab Web 9.7.2).
     * Vorher waren beides graue `.alert`-Kästen — ein Fehler, der den Import
     * verhindert, sah aus wie ein Hinweis zum Dateiformat. Der Text stammt
     * teils aus der gelesenen Datei und wird deshalb maskiert. */
    function meldungMarkup(ton, text) {
        var sym = ton === 'fehler' ? 'warnung' : (ton === 'warn' ? 'warnung' : 'hinweis');
        return '<div class="meldung meldung-' + ton + '" role="'
             + (ton === 'fehler' ? 'alert' : 'status') + '">'
             + edSymbol(sym, 'symbol-gross')
             + '<p>' + esc(text) + '</p></div>';
    }

    function fehler(text) {
        var el = $('fehler');
        el.innerHTML = text ? meldungMarkup('fehler', text) : '';
        el.hidden = !text;
    }

    /* Maskierung: eine Fassung fuer das ganze Projekt (B7, assets/html.js).
     * Hier stand eine eigene Kopie, die das einfache Anfuehrungszeichen
     * ausliess — folgenlos, solange jedes Attribut mit doppelten
     * Anfuehrungszeichen geschrieben wird, und genau deshalb eine Falle fuer
     * die naechste Aenderung (M6-03, M6-05). */
    var esc = EdHtml.escape;

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

    /**
     * Passwortabfrage fuer ein verschluesseltes Archiv.
     *
     * Baut denselben Dialogtyp wie confirm.js (gleiche CSS-Klassen), aber mit
     * einem Eingabefeld. Das Passwort bleibt in dieser Funktion und wird
     * nirgends gespeichert oder gesendet.
     */
    function passwortAbfragen(text) {
        return new Promise(function (aufloesen) {
            var d = document.createElement('dialog');
            /* Dialog-Baustein aus P3/O2 — dieselben Klassen wie in confirm.js
             * und ui.php (Stylesheet-Abschnitt 15). Das style-Attribut am
             * Feld ist mit dem Baustein .feld-eingabe entfallen. */
            d.className = 'dialog';
            d.innerHTML =
                '<div class="dialog-inhalt"><p data-text></p>' +
                '<div class="feld"><input type="password" class="feld-eingabe" ' +
                'autocomplete="off"></div></div>' +
                '<div class="dialog-fuss">' +
                '<button type="button" data-w="ab" class="knopf knopf-leise">Abbrechen</button>' +
                '<button type="button" data-w="ok" class="knopf knopf-primaer">Öffnen</button>' +
                '</div>';
            d.querySelector('[data-text]').textContent = text;
            document.body.appendChild(d);

            var feld = d.querySelector('input');
            function fertig(wert) {
                d.close();
                d.remove();
                aufloesen(wert);
            }
            d.querySelector('[data-w="ok"]').addEventListener('click', function () {
                fertig(feld.value || null);
            });
            d.querySelector('[data-w="ab"]').addEventListener('click', function () { fertig(null); });
            feld.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter') { ev.preventDefault(); fertig(feld.value || null); }
            });
            d.addEventListener('cancel', function (ev) { ev.preventDefault(); fertig(null); });
            d.showModal();
            feld.focus();
        });
    }

    /** Ein .zip erkennen. Ein .xlsx ist technisch auch ein ZIP — deshalb wird
     *  ausschliesslich nach der Endung entschieden, nicht nach dem Inhalt. */
    function istArchiv(name) {
        return /\.zip$/i.test(String(name || ''));
    }

    /**
     * Aus einem Exportarchiv die Tabellendatei holen.
     *
     * Gesucht werden die 'archiveMember' aller bekannten Profile — heute nur
     * einsaetze.csv. Ist das Archiv verschluesselt, wird einmal nach dem
     * Passwort gefragt; ein falsches Passwort meldet zip.js als Fehler.
     */
    async function ausArchiv(bytes) {
        if (!window.zip) {
            throw new Error('Die Bibliothek zum Öffnen von Archiven ist nicht geladen.');
        }
        var gesucht = window.ImportProfile.liste()
            .map(function (p) { return p.archiveMember; })
            .filter(Boolean);

        async function lies(passwort) {
            var leser = new window.zip.ZipReader(new window.zip.Uint8ArrayReader(bytes),
                passwort ? { password: passwort } : {});
            try {
                var eintraege = await leser.getEntries();
                var treffer = eintraege.filter(function (e) {
                    return gesucht.indexOf(e.filename) >= 0;
                })[0];
                if (!treffer) {
                    throw new Error('Im Archiv steckt keine der bekannten Tabellen ('
                        + gesucht.join(', ') + ').');
                }
                if (treffer.encrypted && !passwort) { return { braucht: true }; }
                return { daten: await treffer.getData(new window.zip.Uint8ArrayWriter()) };
            } finally {
                await leser.close();
            }
        }

        var erg = await lies(null);
        if (erg.braucht) {
            var pw = await passwortAbfragen('Dieses Archiv ist mit einem Passwort '
                + 'geschützt. Bitte das Passwort eingeben, mit dem es erstellt wurde.');
            if (!pw) { return null; }
            erg = await lies(pw);
        }
        return erg.daten || null;
    }

    function warnungZeigen(profil) {
        var el = $('profilwarnung');
        if (!el) { return; }
        var text = (profil && profil.warning) || '';
        el.innerHTML = text ? meldungMarkup('warn', text) : '';
        el.hidden = !text;
    }

    async function dateiGewaehlt(datei) {
        fehler('');
        warnungZeigen(null);
        try {
            var daten = new Uint8Array(await datei.arrayBuffer());
            if (istArchiv(datei.name)) {
                daten = await ausArchiv(daten);
                if (!daten) { return; }          // abgebrochen oder nichts gefunden
            }
            S.mappe = ImportCore.leseArbeitsmappe(daten);
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
        warnungZeigen(S.profil);

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
        S.tage = ImportCore.gruppiere(S.erg.zeilen, S.profil);
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
     * innerhalb der Diensttage, die in der Importdatei vorkommen (siehe
     * docs/Technik.md).
     */
    async function bestandEinsatznummernIndex(d) {
        var index = {};
        var ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
        if (!ck) { return index; }
        for (var tag in (d.days || {})) {
            if (!Object.prototype.hasOwnProperty.call(d.days, tag)) { continue; }
            var missions = d.days[tag].missions || [];
            // Entschluesseln samt Fehlerbehandlung an einer Stelle (M6-06,
            // Baustein B8). Ein Datensatz, der nicht zum Schluessel passt,
            // liefert hier keine Einsatznummer — er wird uebersprungen, wie
            // zuvor auch. Dass er unlesbar ist, faellt auf den Anzeigeseiten
            // auf; die Dublettenpruefung ist dafuer der falsche Ort.
            await EdPat.entschluessleListe(missions, ck);
            for (var i = 0; i < missions.length; i++) {
                var o = missions[i]._pat;
                if (o && o.mission_no) { index[o.mission_no] = missions[i].id; }
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

    /* Rollenkatalog aus CREW_ROLES (server/db.php), von import.php gesetzt.
     * Rueckfall: der Stand vor Web 6.0.0. */
    var ROLLEN = (typeof CREW_ROLLEN !== 'undefined' && CREW_ROLLEN.length)
        ? CREW_ROLLEN : ['p1', 'p2', 'hems', 'fr', 'other'];
    var LABELS = (typeof CREW_LABELS !== 'undefined' && CREW_LABELS)
        ? CREW_LABELS
        : { p1: 'Pilot 1', p2: 'Pilot 2', hems: 'HEMS-TC', fr: 'Flugretter',
            other: 'Sonstige' };

    /** Weicht die Besatzung des Diensttags aus der Datei von der gespeicherten ab? */
    function tagKonflikt(t) {
        var b = (S.bestand && S.bestand.days[t.day]) || null;
        if (!b || !b.crew) { return null; }
        var abw = [];
        ROLLEN.forEach(function (r) {
            var neu = t.crew[r] || null, alt = b.crew[r] || null;
            if (neu && alt && neu !== alt) {
                abw.push((LABELS[r] || r) + ': ' + alt + ' → ' + neu);
            }
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
            anzeigeSpalten().map(function (s) { return '<th>' + esc(s) + '</th>'; }).join('') +
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
        var spaltenZahl = anzeigeSpalten().length + 2;

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
            /* Kopfzeile der Tagesgruppe: die BELEGTEN Rollen, hoechstens drei.
               Bis Web 5.10.0 standen hier fest „P1" und „HEMS" — mit sieben
               Rollen waere das die falsche Auswahl, weil ein bodengebundener
               Dienst beide gar nicht kennt. */
            var belegt = ROLLEN.filter(function (r) { return t.crew[r]; }).slice(0, 3);
            var crewText = belegt.length
                ? belegt.map(function (r) { return (LABELS[r] || r) + ' ' + t.crew[r]; }).join(', ')
                : 'keine Besatzung in der Datei';
            var kopfZelle = '<strong>' + esc(t.day) + '</strong> · ' + esc(crewText) +
                ' · ' + t.missionen.length + ' Einsätze · ' +
                (b ? 'Diensttag vorhanden' : 'Diensttag wird angelegt');
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
        $('bilanz').textContent = b.tage + ' Diensttage, ' + b.einsaetze + ' Einsätze, '
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
            anzeigeSpalten().map(function (s) { return zelle(z, s); }).join('') +
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

    function nummerOderNull(v) {
        return (typeof v === 'number' && isFinite(v)) ? v : null;
    }

    /**
     * Phasen der Zeile in die Form bringen, die api/import_commit.php erwartet.
     *
     * Zwei Quellen, weil die beiden Exportformate unterschiedlich genau sind:
     * das vollstaendige CSV liefert ganze Zeitstempel ('at', schon in UTC), das
     * Standard-Excel nur eine Uhrzeit ohne Zone ('local'). Der Server rechnet
     * 'local' genauso um wie die Alarmzeit.
     */
    function phasenListe(m) {
        var out = [], n;
        for (n = 2; n <= 9; n++) {
            var p = (m.phases || {})[n];
            var lokal = (m.phasesLocal || {})[n] || null;
            if (!p && !lokal) { continue; }
            if (p && !p.at && !lokal && p.lat === null && p.lon === null) { continue; }
            out.push({
                phase: n,
                at: (p && p.at) || null,
                local: lokal,
                lat: (p && typeof p.lat === 'number') ? p.lat : null,
                lon: (p && typeof p.lon === 'number') ? p.lon : null
            });
        }
        return out.length ? out : null;
    }

    /**
     * Baut die fertige Nutzlast — einschliesslich Verschluesselung der
     * geschuetzten Angaben. Absichtlich schon hier und nicht erst beim
     * Absenden: Scheitert die Verschluesselung (Krypto gesperrt), soll das
     * VOR dem Klick auffallen und nicht mittendrin.
     */
    async function baueNutzlast() {
        var ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
        if (!ck) { return null; }

        /* Fuehrt die eingelesene Datei ueberhaupt eine Spalte fuer dieses
         * Zielfeld? Kommt aus verarbeiteMatrix(), das die Kopfzeile kennt. */
        var fuehrt = function (ziel) {
            return !!(S.erg && S.erg.zielspalten && S.erg.zielspalten[ziel]);
        };

        var vehId = $('vehsel').value ? parseInt($('vehsel').value, 10) : null;
        var baseId = $('basesel').value ? parseInt($('basesel').value, 10) : null;

        var tage = [], missionen = [], i, t, m, w, dup, vorhanden, pat, keys, blob;

        for (i = 0; i < S.tage.length; i++) {
            t = S.tage[i];
            vorhanden = (S.bestand && S.bestand.days[t.day]) || null;
            // Besatzung als Objekt role_code => name (E7), nicht als
            // Spaltensatz. Leere Rollen bleiben draussen: `day_crew` bekommt
            // seine Zeilenmenge aus dem Rettungsmittel, nicht aus der Datei.
            var crewObj = {};
            ROLLEN.forEach(function (r) { if (t.crew[r]) { crewObj[r] = t.crew[r]; } });
            tage.push({
                day: t.day,
                crew: crewObj,
                vehicle_id: vorhanden ? null : vehId,
                base_id: vorhanden ? null : baseId,
                // 'insert' = Diensttag anlegen, 'keep' = vorhandenen unangetastet
                // lassen, 'update' = Besatzung aus der Datei uebernehmen.
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
                // Alter nur speichern, wenn es NICHT aus dem Geburtsdatum
                // folgt — dieselbe Regel wie im Formular (einsatz_form.php).
                // Der eigene CSV-Export schreibt ohnehin nur diesen Fall; die
                // Pruefung faengt von Hand nachbearbeitete Dateien ab, in
                // denen beides steht.
                if (m.pat.age != null && EdPat.alterAm(m.pat.dob, m.day) === null) {
                    pat.age = m.pat.age;
                }
                if (m.pat.dx) { pat.dx = m.pat.dx; }
                if (m.pat.site_desc) { pat.site_desc = m.pat.site_desc; }
                if (m.pat.loc && (m.pat.loc.addr || m.pat.loc.lat !== undefined)) {
                    pat.loc = {};
                    if (m.pat.loc.addr) { pat.loc.addr = m.pat.loc.addr; }
                    if (typeof m.pat.loc.lat === 'number') { pat.loc.lat = m.pat.loc.lat; }
                    if (typeof m.pat.loc.lon === 'number') { pat.loc.lon = m.pat.loc.lon; }
                    if (!Object.keys(pat.loc).length) { delete pat.loc; }
                }
                // Manueller Abfahrtort (Web 6.1.0) — gleicher Aufbau wie `loc`,
                // eigener Schluessel `start`. Nur er liegt im Blob; die REGEL
                // daneben (start_src) ist Klartext.
                if (m.pat.start && (m.pat.start.addr || m.pat.start.lat !== undefined)) {
                    pat.start = {};
                    if (m.pat.start.addr) { pat.start.addr = m.pat.start.addr; }
                    if (typeof m.pat.start.lat === 'number') { pat.start.lat = m.pat.start.lat; }
                    if (typeof m.pat.start.lon === 'number') { pat.start.lon = m.pat.start.lon; }
                    if (!Object.keys(pat.start).length) { delete pat.start; }
                }
                keys = Object.keys(pat);
                blob = keys.length ? await EdCrypto.encrypt(ck, JSON.stringify(pat)) : null;

                missionen.push({
                    day: m.day,
                    /* Das ECHTE Einsatzdatum, wenn die Datei es fuehrt. Der
                       Server nimmt es als Bezugstag der Alarmzeit; ohne es
                       rechnet er auf `day` — und ein Einsatz nach Mitternacht
                       laege dann 24 Stunden zu frueh (F-P1-K). */
                    date_local: m.einsatzdatum || null,
                    started_local: m.alarm,
                    transport_dest: m.transport_dest,
                    winch: m.winch ? 1 : 0,
                    resources: m.resources || [],
                    crew_override: m.crew_override ? 1 : 0,
                    // Abweichende Besatzung als Objekt role_code => name.
                    // `mission_crew` fuehrt nur Abweichungen, also nur belegte
                    // Rollen — eine Leerzeile hat dort keine Bedeutung.
                    crew: (function () {
                        var o = {};
                        ROLLEN.forEach(function (r) {
                            if (m['crew_' + r]) { o[r] = m['crew_' + r]; }
                        });
                        return o;
                    }()),
                    pat_blob: blob,

                    // Ab Web 2.10.0: Felder, die nur der Rueckimport der
                    // eigenen Exportformate liefert. Profile, die sie nicht
                    // kennen, senden hier ueberall null/0 — der Server setzt
                    // dann dieselben Werte wie vor dieser Version.
                    site_ele_m: nummerOderNull(m.site_ele_m),
                    distance_m: nummerOderNull(m.distance_m),
                    ascent_m: nummerOderNull(m.ascent_m),
                    schockraum: m.schockraum ? 1 : 0,
                    secondary: m.secondary ? 1 : 0,
                    winch_cycles: nummerOderNull(m.winch_cycles),
                    winch_cycles_pat: nummerOderNull(m.winch_cycles_pat),
                    winch_airload: m.winch_airload ? 1 : 0,
                    bergwacht: m.bergwacht ? 1 : 0,
                    bw_unit: m.bw_unit || null,
                    bw_info: m.bw_info || null,
                    other_ema: m.other_ema || null,
                    notes: m.notes || null,

                    // Ab Web 6.1.0: Transportart, Fehleinsatz,
                    // Zielklinik-Koordinate und Abfahrtortregel (E17/E34/E37).
                    transport_mode: m.transport_mode || null,
                    na_escort: m.na_escort ? 1 : 0,
                    false_alarm: m.false_alarm ? 1 : 0,
                    dest_lat: nummerOderNull(m.dest_lat),
                    dest_lon: nummerOderNull(m.dest_lon),
                    start_src: m.start_src || null,

                    phases: phasenListe(m),
                    rea: m.rea || null,

                    dup: dup ? (w.dup || 'skip') : 'insert',
                    overwrite_id: (dup && w.dup === 'overwrite') ? dup.id : null
                });

                /* `ended_utc` und `final`: NUR, WENN DIE DATEI DIE SPALTE
                 * FUEHRT (Backlog Nr. 28).
                 *
                 * Der Unterschied ist der ganze Punkt. Ein WEGGELASSENES Feld
                 * heisst „die Datei sagt dazu nichts" — der Server bleibt beim
                 * bisherigen Verhalten (Ende = Beginn, final = 1) und laesst
                 * beim Ueberschreiben stehen, was dasteht. Ein Feld mit `null`
                 * heisst „die Zelle ist leer" — und ein leeres Ende ist eine
                 * Aussage: Der Einsatz ist nicht abgeschlossen.
                 *
                 * Bis Web 7.3.1 ging `ended_utc: m.ended || null` immer
                 * hinaus, und beide Faelle sahen serverseitig gleich aus. Ein
                 * nicht abgeschlossener Einsatz kam deshalb mit Ende = Beginn
                 * und final = 1 zurueck — im Ueberschreiben-Modus auch dann,
                 * wenn er im Bestand richtig stand (Fund F-P1-M). */
                var letzte = missionen[missionen.length - 1];
                if (fuehrt('ended')) { letzte.ended_utc = m.ended || null; }
                if (fuehrt('final')) { letzte.final = m.final ? 1 : 0; }
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
            $('bereit').innerHTML = meldungMarkup('fehler',
                'Die geschützten Angaben lassen sich nicht verschlüsseln — '
                + 'bitte ab- und neu anmelden.');
            knopf.disabled = true;
            return;
        }
        var mitBlob = S.nutzlast.missions.filter(function (m) { return m.pat_blob; }).length;
        var neueTage = S.nutzlast.days.filter(function (d) { return d.mode === 'insert'; }).length;
        $('bereit').textContent = S.nutzlast.missions.length + ' Einsätze bereit ('
            + mitBlob + ' davon mit verschlüsselten Angaben), ' + neueTage + ' Diensttage werden '
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

            /* Übersprungene und verworfene Werte AUFSCHLÜSSELN.
             *
             * „40 übersprungen" ist nicht deutbar: Es kann „alles war schon
             * da" heißen (gut) oder „alles war kaputt" (schlecht). Vorher
             * fielen vier verschiedene Ursachen in diese eine Zahl. */
            var ursachen = {
                bereits_vorhanden:    'bereits vorhanden',
                auswahl:              'von dir übersprungen',
                datum:                'unbrauchbares Datum',
                uhrzeit:              'unbrauchbare Uhrzeit',
                fremd_oder_geloescht: 'nicht mehr vorhanden oder fremd'
            };
            var teile = [];
            for (var k in (d.skipped_reasons || {})) {
                teile.push((ursachen[k] || k) + ': ' + d.skipped_reasons[k]);
            }
            var verworfen = [];
            for (var u in (d.rejected || {})) {
                verworfen.push(esc(u) + ' (' + d.rejected[u] + '×)');
            }

            /* Das Ergebnis als Meldung mit Haken (E-P3-16). `innerHTML`
                bleibt: Der Bericht trägt einen Link auf den ersten Tag, und
                die verworfenen Werte sind bereits maskiert. */
            zustand.innerHTML = '<div class="meldung meldung-ok" role="status">'
                + edSymbol('haken', 'symbol-gross') + '<p>'
                + 'Fertig: ' + d.missions_inserted + ' Einsätze angelegt, '
                + d.missions_overwritten + ' überschrieben, ' + d.missions_skipped + ' übersprungen'
                + (teile.length ? ' (' + esc(teile.join(', ')) + ')' : '') + '; '
                + d.days_inserted + ' Diensttage angelegt, ' + d.days_updated + ' aktualisiert.'
                + (verworfen.length
                   ? '<br><span class="muted">Einzelne Werte verworfen: '
                     + verworfen.join(', ') + '. Die Einsätze wurden trotzdem angelegt.</span>'
                   : '')
                + (d.first_day ? ' <a href="index.php?day=' + esc(d.first_day) + '">Ersten Tag öffnen</a>' : '')
                + '</p></div>';

            // Ein zweiter Klick wuerde alles ein weiteres Mal anlegen. Der Weg
            // zurueck fuehrt bewusst ueber eine neu gewaehlte Datei.
            $('schritt2').hidden = true;
            S.nutzlast = null;
            $('bereit').textContent = 'Übernommen. Für einen weiteren Import bitte erneut '
                + 'eine Datei wählen.';
        } catch (e) {
            zustand.innerHTML = meldungMarkup('fehler',
                'Die Übernahme ist fehlgeschlagen: ' + e.message
                + ' — es wurde nichts gespeichert.');
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
            warnungZeigen(p);
            var g = ImportCore.findeKopfzeile(ImportCore.matrix(S.mappe, p), p);
            S.kopfzeile = g ? g.zeile : 0;
            S.mat = ImportCore.matrix(S.mappe, p);
            paramsFuellen(ImportCore.paramVorschlaege(S.mappe, p, S.kopfzeile, ''));
            neuRechnen();
        });

        $('params').addEventListener('change', function () { neuRechnen(); });
        $('commit').addEventListener('click', uebernehmen);
        $('vehsel').addEventListener('change', function () { bereitschaft(); });
        $('basesel').addEventListener('change', function () { bereitschaft(); });

        /* Die Zeilenwahl ist seit Web 9.7.2 eine Segmentwahl (E-P3-35): drei
         * Zustände, von denen genau einer gilt. Gehorcht wird `change` an der
         * Gruppe, nicht `click` an je einem Knopf — sonst löste die
         * Tastaturbedienung nichts aus, die der Browser bei Radios von selbst
         * mitbringt. */
        var filterWahl = $('impfilter');
        if (filterWahl) {
            filterWahl.addEventListener('change', function (ev) {
                if (ev.target.name !== 'impfilter') { return; }
                S.filter = ev.target.value;
                zeichnen();
            });
        }

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

    /* Sperrstatus pruefen und bei Bedarf entsperren lassen. Bricht die Person
     * den Dialog ab, bleibt der Hinweis stehen — sein Knopf stoesst denselben
     * Versuch erneut an. */
    async function sperrstatus() {
        var ck = await EdUnlock.ensureContentKey(PAT_WRAP, KDF_SALT, KDF_ITER);
        $('lockwarn').hidden = !!ck;
        $('datei').disabled = !ck;
        return ck;
    }

    (async function () {
        profileFuellen();
        verdrahten();
        var btn = $('lockwarn_unlock');
        if (btn) { btn.addEventListener('click', function () { sperrstatus(); }); }
        await sperrstatus();
    }());
}());
