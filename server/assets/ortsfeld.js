/* Ortsfeld — Bezeichnung + optionale Koordinaten, als wiederverwendbare
 * Komponente (Web 6.1.0).
 *
 * WARUM ES DIESE DATEI GIBT. Das Einsatzort-Widget war bis Web 6.0.0 keine
 * Komponente, sondern ueber feste Element-Kennungen verdrahtet: `locaddr`,
 * `loclat`, `loclon`, `locstate` erschienen in rund 25 getElementById-Aufrufen
 * in eingebettetem JavaScript von einsatz_form.php — Photon-Abfrage,
 * Plus-Code-Erkennung, Chip, Zustandszeile und die Pruefung „Koordinaten ohne
 * Bezeichnung" hingen alle daran (Vorpruefung V8). Mit Etappe 2 sind SECHS
 * Verwendungen gefordert: Einsatzort, Abfahrtort, Zielklinik am Einsatz sowie
 * Standort- und Zielklinikpflege je auf Konto- und Adminebene. Sechs Kopien
 * derselben 250 Zeilen waeren sechs Fassungen, die auseinanderlaufen.
 *
 * Die Kennungen bildet die Komponente jetzt aus einem PRAEFIX. Das Markup dazu
 * erzeugt ui_ortsfeld() (server/ui.php) — eine Verwendung besteht damit aus
 * einem PHP-Aufruf und einem EdOrtsfeld.init().
 *
 * ZWEI BEDIENFORMEN, eine Umsetzung:
 *
 *   getrennteSuche: false   Das Textfeld IST das Suchfeld (Einsatzort,
 *                           manueller Abfahrtort). Wer eine Adresse waehlt,
 *                           uebernimmt sie als Bezeichnung — dort ist die
 *                           Adresse die Bezeichnung. Unveraendertes Verhalten
 *                           seit Web 3.x.
 *
 *   getrennteSuche: true    Textfeld = NAME, daneben ein eigenes Suchfeld
 *                           (Zielklinik, Standort). „Standort Kempten" ist
 *                           keine Adresse; wuerde die Suche das Textfeld
 *                           ueberschreiben, waere der Name weg. Der Treffer
 *                           setzt deshalb nur die Koordinaten — und faellt nur
 *                           dann in das Namensfeld, wenn es leer ist.
 *
 * WAS IN BEIDEN FORMEN GLEICH BLEIBT und der eigentliche Grund fuer die
 * Zusammenfassung ist:
 *
 *   - Koordinaten stehen als CHIP unter dem Feld, nie im Textfeld. Sonst
 *     vernichtet die erste getippte Bezeichnung sie.
 *   - Formaterkennung (Dezimalgrad, Grad/Minuten, Plus Code) laeuft LOKAL und
 *     hat Vorrang vor jeder Netzanfrage (assets/locparse.js).
 *   - Ein Treffer wird zur BESTAETIGUNG angeboten, nie sofort uebernommen.
 *   - Stehen Koordinaten, ruht die Adresssuche: Ein Vorschlag wuerde sie
 *     stillschweigend ueberschreiben. Der Weg zurueck fuehrt ueber das Kreuz
 *     am Chip.
 *   - Koordinaten ohne Bezeichnung werden beim Absenden abgewiesen, eine
 *     Bezeichnung ohne Koordinaten ist zulaessig (E39).
 *
 * KEINE Abhaengigkeit ausser assets/locparse.js (EdLoc) — und selbst die ist
 * optional: Fehlt sie, entfaellt die Formaterkennung, das Feld bleibt
 * bedienbar.
 */
(function (global) {
    'use strict';

    /* Photon (OSM-Daten, kostenlos, kein Schluessel). Dieselbe Adresse wie
     * bisher im Einsatzformular. */
    var PHOTON = 'https://photon.komoot.io/api/?lang=de&limit=6&q=';

    var MELDUNGEN = {
        'plus-kurz': 'Plus-Code-Kurzform erkannt — bitte Vollcode eingeben ' +
            '(in der Karten-App ohne Ortsangabe kopieren).',
        'ungueltig': 'Koordinaten unvollständig oder außerhalb des gültigen Bereichs.'
    };

    var UEBERNAHME = {
        dezimal: 'Koordinaten übernehmen (Dezimalgrad)',
        gdm: 'Koordinaten übernehmen (Grad/Dezimalminuten)',
        dms: 'Koordinaten übernehmen (Grad/Minuten/Sekunden)',
        plus: 'Plus Code übernehmen'
    };

    function el(id) { return document.getElementById(id); }

    /** Beschriftung eines Photon-Treffers: Name, Strasse, PLZ/Ort. */
    function photonLabel(p) {
        var teile = [];
        if (p.name) { teile.push(p.name); }
        var strasse = [p.street, p.housenumber].filter(Boolean).join(' ');
        if (strasse && strasse !== p.name) { teile.push(strasse); }
        var ort = [p.postcode, p.city].filter(Boolean).join(' ');
        if (ort) { teile.push(ort); }
        return teile.join(', ');
    }

    /**
     * Eine Verwendung aufbauen.
     *
     * @param {object} opt
     *   praefix                Kennungs-Praefix; erwartet werden die Elemente
     *                          <p>addr, <p>lat, <p>lon, <p>suggest, <p>state,
     *                          <p>chips und — bei getrennteSuche — <p>such.
     *   getrennteSuche         siehe Kopfkommentar (Vorgabe: false)
     *   adresssuche            Photon-Abfrage zulassen (Vorgabe: true)
     *   formate                Koordinaten-/Plus-Code-Erkennung (Vorgabe: true)
     *   bezeichnungPlatzhalter Platzhalter, sobald Koordinaten stehen
     *   vorschlaege            [{name, lat, lon}, …] — Stammdaten hinter einer
     *                          <datalist>. Trifft die Eingabe einen Namen
     *                          GENAU, werden dessen Koordinaten uebernommen
     *                          (E38: „Wird eine Zielklinik aus der
     *                          Vorschlagsliste übernommen, füllen sich ihre
     *                          Koordinaten mit").
     *   beiAenderung           Rueckruf nach jeder Wertaenderung
     * @returns {object} Steuerobjekt (siehe unten)
     */
    function init(opt) {
        opt = opt || {};
        var p = opt.praefix;
        var feld = el(p + 'addr');
        if (!feld) { return null; }        // Verwendung nicht auf dieser Seite

        var latF = el(p + 'lat');
        var lonF = el(p + 'lon');
        var liste = el(p + 'suggest');
        var zeile = el(p + 'state');
        var chips = el(p + 'chips');
        var suchF = opt.getrennteSuche ? el(p + 'such') : feld;

        var adresssuche = opt.adresssuche !== false;
        var formate = opt.formate !== false;
        var vorschlaege = opt.vorschlaege || [];
        var beiAenderung = typeof opt.beiAenderung === 'function' ? opt.beiAenderung : null;

        var platzhalterFrei = feld.getAttribute('placeholder') || '';
        var platzhalterBez = opt.bezeichnungPlatzhalter || platzhalterFrei;

        var timer = null;
        var erkennung = { typ: null };
        /* Zuletzt aus der Vorschlagsliste uebernommener Name. Er verhindert,
         * dass eine VON HAND gesetzte Koordinate beim erneuten Tippen desselben
         * Namens wieder verworfen wird: Uebernommen wird nur, wenn sich der
         * Treffer aendert. */
        var letzterTreffer = null;

        function hatKoordinaten() { return latF.value !== '' && lonF.value !== ''; }

        function melde(text, fehler) {
            if (!zeile) { return; }
            zeile.textContent = text || '';
            zeile.classList.toggle('locstate-fehler', !!fehler);
        }

        function zustand() {
            if (erkennung.typ && MELDUNGEN[erkennung.typ]) {
                melde(MELDUNGEN[erkennung.typ], false);
                return;
            }
            if (hatKoordinaten()) {
                /* Die Koordinaten selbst zeigt der Chip. Der Hinweis erklaert,
                 * warum hier keine Vorschlaege mehr erscheinen — sonst wirkt
                 * das Feld defekt. */
                melde(adresssuche
                    ? 'Koordinaten gesetzt — dieses Feld ist die Bezeichnung. '
                      + 'Für eine Suche zuerst die Koordinaten entfernen (✕).'
                    : 'Koordinaten gesetzt — dieses Feld ist die Bezeichnung.', false);
                return;
            }
            melde(feld.value ? 'Nur Text (keine Koordinaten) — kein Karten-Pin.' : '', false);
        }

        /* Chip: eigene, sichtbare Darstellung ausserhalb des Textfeldes.
         * Gleiche Klassen wie die Rettungsmittel-Chips — kein zweites Aussehen
         * fuer dieselbe Sache. Der Chip ist reine ANZEIGE; Werttraeger bleiben
         * die versteckten Felder. */
        function zeichne() {
            feld.placeholder = hatKoordinaten() ? platzhalterBez : platzhalterFrei;
            if (!chips) { return; }
            chips.innerHTML = '';
            if (!hatKoordinaten()) { return; }
            var chip = document.createElement('span');
            chip.className = 'rmchip';
            chip.appendChild(document.createTextNode(
                parseFloat(latF.value).toFixed(5) + ', ' + parseFloat(lonF.value).toFixed(5)));
            var x = document.createElement('button');
            x.type = 'button';
            x.className = 'rmx';
            x.textContent = '×';
            x.title = 'Koordinaten entfernen';
            x.addEventListener('click', function () {
                latF.value = '';
                lonF.value = '';
                letzterTreffer = null;
                zeichne();                 // Textfeld bleibt unangetastet
                zustand();                 // ab jetzt sucht das Feld wieder
                if (beiAenderung) { beiAenderung(); }
            });
            chip.appendChild(x);
            chips.appendChild(chip);
        }

        function setzeKoordinaten(lat, lon) {
            latF.value = (lat === null || lat === undefined) ? '' : lat;
            lonF.value = (lon === null || lon === undefined) ? '' : lon;
            zeichne();
            zustand();
            if (beiAenderung) { beiAenderung(); }
        }

        function versteckeListe() {
            if (!liste) { return; }
            liste.innerHTML = '';
            liste.hidden = true;
        }

        function zeigeEintrag(text, uebernehmen) {
            if (!liste) { return; }
            var li = document.createElement('li');
            li.textContent = text;
            li.addEventListener('mousedown', function (ev) {   // mousedown: vor blur
                ev.preventDefault();
                uebernehmen();
            });
            liste.appendChild(li);
        }

        /* Trifft die Eingabe genau einen Stammdatensatz, dessen Koordinaten
         * uebernehmen — und zwar auch dann, wenn er KEINE hat: Wer die
         * Zielklinik wechselt, soll nicht die Koordinate der vorherigen
         * behalten. Ein unveraenderter Name aendert nichts (siehe
         * letzterTreffer). */
        function pruefeVorschlag() {
            if (!vorschlaege.length) { return; }
            var wert = feld.value.trim().toLowerCase();
            if (wert === '') { letzterTreffer = null; return; }
            var treffer = null;
            for (var i = 0; i < vorschlaege.length; i++) {
                if (String(vorschlaege[i].name).trim().toLowerCase() === wert) {
                    treffer = vorschlaege[i];
                    break;
                }
            }
            if (!treffer) { return; }
            if (letzterTreffer !== null && letzterTreffer === wert) { return; }
            letzterTreffer = wert;
            setzeKoordinaten(
                treffer.lat === null || treffer.lat === undefined ? '' : treffer.lat,
                treffer.lon === null || treffer.lon === undefined ? '' : treffer.lon);
        }

        /* ---- Eingabe im Bezeichnungsfeld ---------------------------------- */
        feld.addEventListener('input', function () {
            pruefeVorschlag();
            if (suchF === feld) { sucheTippen(); } else { zustand(); }
        });

        /* ---- Eingabe im Such-/Koordinatenfeld ----------------------------- */
        function sucheTippen() {
            clearTimeout(timer);

            /* Stehen bereits Koordinaten, ist hier Schluss. Weder
             * Formaterkennung noch Adresssuche laufen weiter — beide wuerden
             * beim Uebernehmen eines Vorschlags die bestaetigten Koordinaten
             * ueberschreiben. */
            if (hatKoordinaten()) {
                erkennung = { typ: null };
                versteckeListe();
                zustand();
                return;
            }

            erkennung = (formate && typeof EdLoc !== 'undefined')
                ? EdLoc.erkenneEinsatzort(suchF.value) : { typ: null };

            if (UEBERNAHME[erkennung.typ]) {
                var erg = erkennung;
                if (liste) { liste.innerHTML = ''; }
                zeigeEintrag(UEBERNAHME[erg.typ] + ': ' + erg.anzeige, function () {
                    /* Das Suchfeld LEEREN statt mit der Zahlendarstellung zu
                     * ueberschreiben — es gehoert ab hier der Bezeichnung
                     * (bzw. der naechsten Suche). Die Koordinaten stehen im
                     * Chip. */
                    suchF.value = '';
                    versteckeListe();
                    erkennung = { typ: null };
                    setzeKoordinaten(erg.lat, erg.lon);
                    feld.focus();
                });
                if (liste) { liste.hidden = false; }
                zustand();
                return;
            }
            if (erkennung.typ === 'plus-kurz' || erkennung.typ === 'ungueltig') {
                versteckeListe();
                zustand();
                return;
            }

            zustand();
            if (!adresssuche) { versteckeListe(); return; }

            var q = suchF.value.trim();
            if (q.length < 3) { versteckeListe(); return; }
            timer = setTimeout(function () {
                fetch(PHOTON + encodeURIComponent(q)).then(function (r) {
                    return r.json();
                }).then(function (d) {
                    if (!liste) { return; }
                    liste.innerHTML = '';
                    (d.features || []).forEach(function (ft) {
                        var text = photonLabel(ft.properties);
                        zeigeEintrag(text, function () {
                            /* Getrennte Suche: NUR die Koordinaten. Das
                             * Namensfeld gehoert der Nutzerin — es wird
                             * hoechstens gefuellt, wenn es leer ist. */
                            if (suchF !== feld) {
                                suchF.value = '';
                                if (feld.value.trim() === '') { feld.value = text; }
                            } else {
                                feld.value = text;
                            }
                            versteckeListe();
                            letzterTreffer = feld.value.trim().toLowerCase();
                            setzeKoordinaten(ft.geometry.coordinates[1],
                                             ft.geometry.coordinates[0]);
                        });
                    });
                    liste.hidden = liste.children.length === 0;
                }).catch(function () { versteckeListe(); });
            }, 300);
        }

        if (suchF !== feld) { suchF.addEventListener('input', sucheTippen); }
        suchF.addEventListener('blur', function () {
            setTimeout(versteckeListe, 150);
        });

        zeichne();
        zustand();

        return {
            /** Sind Koordinaten gesetzt? */
            hatKoordinaten: hatKoordinaten,

            /** Werte lesen: {addr, lat, lon} — lat/lon null ohne Koordinaten. */
            werte: function () {
                return {
                    addr: feld.value.trim(),
                    lat: hatKoordinaten() ? parseFloat(latF.value) : null,
                    lon: hatKoordinaten() ? parseFloat(lonF.value) : null
                };
            },

            /** Werte setzen (Vorbelegung nach dem Entschluesseln). */
            setzen: function (o) {
                o = o || {};
                feld.value = (o.addr === null || o.addr === undefined) ? '' : o.addr;
                latF.value = (o.lat === null || o.lat === undefined) ? '' : o.lat;
                lonF.value = (o.lon === null || o.lon === undefined) ? '' : o.lon;
                letzterTreffer = feld.value.trim().toLowerCase() || null;
                zeichne();
                zustand();
            },

            /**
             * Pruefung vor dem Absenden (E39, A13j): Koordinaten ohne
             * Bezeichnung werden abgewiesen — sonst stuende in den Listen
             * wieder ein Zahlenfragment. Eine Bezeichnung ohne Koordinaten ist
             * ausdruecklich zulaessig.
             *
             * Meldet und fokussiert selbst; der Aufrufer bricht nur ab.
             */
            pruefe: function (meldung) {
                if (feld.value.trim() === '' && hatKoordinaten()) {
                    melde(meldung || 'Bezeichnung fehlt — bitte zu den Koordinaten '
                        + 'einen Namen eintragen.', true);
                    feld.focus();
                    return false;
                }
                melde('', false);
                zustand();
                return true;
            },

            /** Bezeichnungsfeld — fuer Aufrufer, die es sperren muessen. */
            feld: feld
        };
    }

    global.EdOrtsfeld = { init: init };
})(window);
