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
 *   getrennteSuche: true    Textfeld = NAME (Zielklinik, Standort). „Standort
 *                           Talwang" ist keine Adresse; wuerde die Suche das
 *                           Textfeld ueberschreiben, waere der Name weg. Ein
 *                           Treffer setzt deshalb nur die Koordinaten — und
 *                           faellt nur dann in das Namensfeld, wenn es leer
 *                           ist. SEIT O5 (E-P3-34) sucht hier kein zweites
 *                           Feld mehr, sondern das Namensfeld selbst.
 *                           SEIT S3/AP8 (E-S3-06) auch BEIM TIPPEN, nicht
 *                           mehr nur auf Klick — entprellt, ab drei Zeichen,
 *                           mit hoechstens einer offenen Anfrage. Die Lupe
 *                           bleibt als ausdruecklicher Ausloeser.
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
 * ZWEI ABHAENGIGKEITEN, und nur zwei:
 *
 *   assets/locparse.js (EdLoc) ist OPTIONAL — fehlt sie, entfaellt die
 *   Formaterkennung, das Feld bleibt bedienbar.
 *
 *   assets/vorschlagsliste.js (EdVorschlaege) ist PFLICHT (S9/AP1, E-S9-07).
 *   Die Trefferliste war bis Web 15.5.2 hier eingebaut — dreissig Zeilen, die
 *   `mousedown` richtig machten und Pfeiltasten gar nicht kannten; daneben
 *   stand am Transportziel zusaetzlich eine native `<datalist>` mit den
 *   Stammdaten, die der Browser UEBER dem Feld zeichnete und mobil oft gar
 *   nicht (PS-6, Backlog 68). Beide sind fort: Es gibt EINE Liste, sie kommt
 *   aus dem Baustein, und sie zeigt Stammdaten und Adressen in GRUPPEN. Was
 *   davon erscheint, entscheidet weiterhin diese Datei — der Baustein weiss
 *   nicht, woher ein Eintrag stammt.
 */
(function (global) {
    'use strict';

    /* ENTPRELLUNG UND EINE OFFENE ANFRAGE (E-S3-06).
     *
     * Photon ist ein frei betriebener Gemeinschaftsdienst. Eine Anfrage je
     * Tastendruck waere Missbrauch seiner Gutmuetigkeit — und jede Anfrage
     * traegt die eingetippten Buchstaben zu einem Dritten. Deshalb drei
     * Grenzen, und alle drei stehen hier und nicht verstreut:
     *
     *   ENTPRELL_MS      Ruhe nach dem letzten Tastendruck, bevor gesucht
     *                    wird. E-S3-06 gibt 400 ms vor und erlaubt 300–600;
     *                    400 ist geblieben — bei fluessigem Tippen eines
     *                    Ortsnamens entsteht damit genau EINE Anfrage.
     *   MINDESTZEICHEN   Unter drei Zeichen sucht niemand ernsthaft.
     *   AbortController  Eine laufende Anfrage wird abgebrochen, bevor die
     *                    naechste startet. Ohne das ueberholen sich zwei
     *                    Antworten, und die Liste zeigt die zum vorletzten
     *                    Stand — der Fehler faellt nur im langsamen Netz auf.
     *
     * Die Lupe umgeht die Entprellung (sofort), nicht die Mindestlaenge. */
    var ENTPRELL_MS = 400;
    var MINDESTZEICHEN = 3;

    /* Photon (OSM-Daten, kostenlos, kein Schluessel). Dieselbe Adresse wie
     * bisher im Einsatzformular. */
    var PHOTON = 'https://photon.komoot.io/api/?lang=de&limit=6&q=';

    /* HOECHSTENS ZWEI STAMMDATENTREFFER ueber den Adressen (F10, E-S9-07).
     * Die Zahl ist eine Entscheidung, keine Schaetzung: Die Stammdatengruppe
     * steht OBEN und schiebt die Adressen nach unten; drei Zielkliniken auf
     * einem 390-px-Schirm liessen von den Adressen nichts mehr uebrig. Wer
     * mehr sehen will, tippt weiter — der Filter wird schaerfer. */
    var STAMM_MAX = 2;

    /* Beschriftungen der beiden Gruppen. Sie stehen hier und nicht im
     * Baustein: Der weiss nicht, dass es Zielkliniken sind. */
    var GRUPPE_STAMM = 'Zielkliniken';
    var GRUPPE_ADRESSEN = 'Adressen';

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

    /** Beschriftung eines Photon-Treffers: Name, Strasse, PLZ/Ort. Das ist
     *  der WERT, der ins Feld wandert — eine Zeile, wie bisher. */
    function photonLabel(p) {
        var teile = [];
        if (p.name) { teile.push(p.name); }
        var strasse = [p.street, p.housenumber].filter(Boolean).join(' ');
        if (strasse && strasse !== p.name) { teile.push(strasse); }
        var ort = [p.postcode, p.city].filter(Boolean).join(' ');
        if (ort) { teile.push(ort); }
        return teile.join(', ');
    }

    /* ZWEI ZEILEN IN DER LISTE, EINE IM FELD (M-S9-03). Die Postleitzahl mit
     * Ort steht gedaempft darunter statt hinten in derselben Zeile: Auf einem
     * 390-px-Schirm schnitt die einzeilige Fassung genau dort ab, wo die
     * Unterscheidung zweier gleichnamiger Treffer beginnt. Uebernommen wird
     * trotzdem die eine Zeile aus photonLabel() — im Feld steht die Adresse,
     * nicht ein Absatz. */
    function photonHaupt(p) {
        var teile = [];
        if (p.name) { teile.push(p.name); }
        var strasse = [p.street, p.housenumber].filter(Boolean).join(' ');
        if (strasse && strasse !== p.name) { teile.push(strasse); }
        return teile.length ? teile.join(', ') : photonLabel(p);
    }

    function photonNeben(p) {
        return [p.postcode, p.city].filter(Boolean).join(' ');
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
     *   vorschlaege            [{name, lat, lon}, …] — Stammdaten. Sie
     *                          erscheinen als eigene GRUPPE oben in der
     *                          Trefferliste, hoechstens zwei, schon bei
     *                          Teiluebereinstimmung (E-S9-07/F10); ein
     *                          Treffer setzt Name und Koordinaten (E38).
     *                          Zusaetzlich fuellt eine GENAUE Namensgleichheit
     *                          die Koordinaten weiterhin ohne Klick — wer den
     *                          Namen abtippt oder einfuegt, bekommt sie mit.
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
        var lupe = el(p + 'lupe');
        /* Das Textfeld ist immer auch die Suchquelle; bei getrennter Suche
         * unterscheidet sich nur, WANN gesucht wird (Lupe statt Tippen) und
         * WAS ein Treffer uebernimmt (nur Koordinaten). */
        var suchF = feld;
        var nurKoordinaten = !!opt.getrennteSuche;

        var adresssuche = opt.adresssuche !== false;
        var formate = opt.formate !== false;
        var vorschlaege = opt.vorschlaege || [];
        var beiAenderung = typeof opt.beiAenderung === 'function' ? opt.beiAenderung : null;

        var platzhalterFrei = feld.getAttribute('placeholder') || '';
        var platzhalterBez = opt.bezeichnungPlatzhalter || platzhalterFrei;

        var timer = null;
        var laufend = null;          // AbortController der offenen Anfrage
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
                /* KURZ GENUG FUER EINE ZEILE (F-N1-I). Der Satz stand in der
                 * Formularspalte auf zwei Zeilen und schob die Felder
                 * darunter weg. Die Auskunft bleibt dieselbe; nur der Verweis
                 * auf das Kreuz ist fort — es steht sichtbar daneben, und
                 * seine Beschreibung im Text kostete mehr Platz, als sie
                 * erklaerte (nebenbei ein Unicode-Zeichen weniger, Backlog
                 * Nr. 42). */
                melde(adresssuche
                    ? 'Koordinaten gesetzt — dieses Feld ist die Bezeichnung. '
                      + 'Zum Suchen erst entfernen.'
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
            /* SUCHFELD WEG, SOBALD DIE KOORDINATE STEHT (Web 7.0.0).
             *
             * Bei getrennter Suche standen bisher drei Zeilen untereinander:
             * die Bezeichnung, ein Suchfeld, das nichts mehr zu suchen hatte,
             * und darunter der Chip mit dem Ergebnis. Das Suchfeld war ab
             * diesem Moment funktionslos — die Zustandszeile sagte das auch,
             * musste es aber sagen, weil es dastand.
             *
             * Es kommt zurueck, sobald der Chip ueber sein ✕ entfernt wird:
             * Genau dann gibt es wieder etwas zu suchen. Ohne getrennte Suche
             * (Einsatzort) aendert sich nichts — dort IST das Bezeichnungsfeld
             * das Suchfeld, und es zu verstecken hiesse, den Ort zu
             * verstecken. */
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

        /* ---- Die Trefferliste (assets/vorschlagsliste.js) ------------------
         *
         * Drei Sorten Eintrag, und nur diese Datei weiss, was ein Klick auf
         * welche tut:
         *
         *   'koordinate'  eine lokal erkannte Koordinate oder ein Plus Code
         *                 (EdLoc). Sie hat Vorrang vor allem anderen und
         *                 steht dann ALLEIN in der Liste — wer Zahlen tippt,
         *                 sucht keine Adresse.
         *   'stamm'       ein Stammdatensatz (nur da, wo `vorschlaege`
         *                 uebergeben werden — heute allein am Transportziel,
         *                 F9). Ein Treffer setzt NAME UND KOORDINATE (E38).
         *   'adresse'     ein Treffer der Adresssuche. Er setzt die
         *                 Koordinate, und den Namen nur, wenn das Feld die
         *                 Adresse selbst als Bezeichnung fuehrt.
         */
        var adressTreffer = [];        // letzte Antwort der Adresssuche
        var koordTreffer = null;       // lokal erkannte Koordinate
        var letzteAnfrage = '';        // getippter Text (fuer die Hervorhebung)

        var vorschlagsListe = liste ? EdVorschlaege.init({
            feld: feld,
            behaelter: liste.parentNode,
            liste: liste,
            beiWahl: uebernimm
        }) : null;

        function versteckeListe() {
            if (vorschlagsListe) { vorschlagsListe.verstecke(); }
        }

        /* Stammdatentreffer bei TEILUEBEREINSTIMMUNG, nicht erst bei
         * Namensgleichheit (F-S9-K-01). Bis Web 15.5.2 kamen die Stammdaten
         * ueber eine native `<datalist>` in die Seite, und die Komponente
         * verglich daneben auf genaue Gleichheit — wer „Klin" tippte, sah die
         * Vorschlaege nur, solange der Browser sie zeichnete, und mobil gar
         * nicht. Jetzt filtert diese Funktion, und die Liste zeigt das
         * Ergebnis. */
        function stammTreffer(q) {
            var raus = [];
            if (!vorschlaege.length) { return raus; }
            var s = String(q || '').trim().toLowerCase();
            if (s === '') { return raus; }
            for (var i = 0; i < vorschlaege.length && raus.length < STAMM_MAX; i++) {
                if (String(vorschlaege[i].name).toLowerCase().indexOf(s) !== -1) {
                    raus.push(vorschlaege[i]);
                }
            }
            return raus;
        }

        function zeichneListe() {
            if (!vorschlagsListe) { return; }
            if (koordTreffer) {
                vorschlagsListe.zeige([{ titel: null, eintraege: [{
                    haupt: UEBERNAHME[koordTreffer.typ] + ': ' + koordTreffer.anzeige,
                    symbol: 'position', art: 'koordinate', wert: koordTreffer
                }] }], '');
                return;
            }
            var gruppen = [];
            var stamm = stammTreffer(letzteAnfrage);
            /* EINE GRUPPE IST KEINE GRUPPE (M-S9-03, Anmerkung 4). Am
             * Einsatzort und am Abfahrtort gibt es keine Stammdaten; dort
             * stuende „Adressen" als Ueberschrift ueber allem, was da ist.
             * Die Zeilen tragen ihre Herkunft ohnehin im Symbol. */
            var mitTiteln = stamm.length > 0;
            if (stamm.length) {
                gruppen.push({ titel: GRUPPE_STAMM, eintraege: stamm.map(function (v) {
                    var hatOrt = v.lat !== null && v.lat !== undefined
                              && v.lon !== null && v.lon !== undefined;
                    return {
                        haupt: v.name,
                        neben: 'Stammdaten · ' + (hatOrt ? 'mit Koordinate' : 'ohne Koordinate'),
                        symbol: 'klinik', art: 'stamm', wert: v
                    };
                }) });
            }
            if (adressTreffer.length) {
                gruppen.push({ titel: mitTiteln ? GRUPPE_ADRESSEN : null,
                               eintraege: adressTreffer.map(function (ft) {
                    return {
                        haupt: photonHaupt(ft.properties),
                        neben: photonNeben(ft.properties),
                        symbol: 'standort', art: 'adresse', wert: ft,
                        voll: photonLabel(ft.properties)
                    };
                }) });
            }
            vorschlagsListe.zeige(gruppen, letzteAnfrage);
        }

        function uebernimm(e) {
            if (e.art === 'koordinate') {
                var erg = e.wert;
                /* Die Zahlendarstellung raeumen — das Feld gehoert ab hier
                 * der Bezeichnung; die Koordinaten stehen im Chip. Bei
                 * getrennter Suche steht im Feld womoeglich schon ein NAME
                 * (und die Koordinate kam per Lupe daneben): Ein Name wird
                 * nie geleert — geleert wird nur, was die Erkennung selbst
                 * gerade als Koordinate gelesen hat. */
                if (!nurKoordinaten || erg.anzeige === feld.value.trim()) { feld.value = ''; }
                koordTreffer = null;
                erkennung = { typ: null };
                setzeKoordinaten(erg.lat, erg.lon);
                feld.focus();
                return;
            }
            if (e.art === 'stamm') {
                var v = e.wert;
                /* AUCH IN DER GETRENNTEN SUCHE der Name: Ein Stammdatensatz
                 * IST die Bezeichnung („Klinik Talwang"), nicht eine Adresse,
                 * die sie ueberschriebe. Ein Satz OHNE Koordinate leert sie —
                 * wer die Zielklinik wechselt, soll nicht die Koordinate der
                 * vorherigen behalten (A13l). */
                feld.value = v.name;
                letzterTreffer = feld.value.trim().toLowerCase();
                setzeKoordinaten(
                    v.lat === null || v.lat === undefined ? '' : v.lat,
                    v.lon === null || v.lon === undefined ? '' : v.lon);
                feld.focus();
                return;
            }
            /* Adresse. Getrennte Suche: NUR die Koordinaten. Das Namensfeld
             * gehoert der Nutzerin — es wird hoechstens gefuellt, wenn es
             * leer ist. */
            var ft = e.wert;
            if (nurKoordinaten) {
                if (feld.value.trim() === '') { feld.value = e.voll; }
            } else {
                feld.value = e.voll;
            }
            letzterTreffer = feld.value.trim().toLowerCase();
            setzeKoordinaten(ft.geometry.coordinates[1], ft.geometry.coordinates[0]);
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
        /* AUCH BEI GETRENNTER SUCHE (S3/AP8, E-S3-06). Bis Web 12.3.2 stand
         * hier `if (nurKoordinaten) { zustand(); }` — bei Standort und
         * Zielklinik lief die Suche also nur auf Klick. Jetzt sucht das Feld
         * in beiden Bedienformen beim Tippen; was ein Treffer uebernimmt,
         * bleibt unterschieden (bei getrennter Suche nur die Koordinaten). */
        feld.addEventListener('input', function () {
            pruefeVorschlag();
            sucheTippen();
        });

        /* ---- Suche (beim Tippen bzw. per Lupe) ---------------------------- */
        function sucheJetzt() { sucheTippen(true); }
        function sucheTippen(sofort) {
            clearTimeout(timer);
            letzteAnfrage = suchF.value.trim();

            /* Stehen bereits Koordinaten, ist hier Schluss. Weder
             * Formaterkennung noch Adresssuche laufen weiter — beide wuerden
             * beim Uebernehmen eines Vorschlags die bestaetigten Koordinaten
             * ueberschreiben. */
            if (hatKoordinaten()) {
                erkennung = { typ: null };
                koordTreffer = null;
                adressTreffer = [];
                versteckeListe();
                zustand();
                return;
            }

            erkennung = (formate && typeof EdLoc !== 'undefined')
                ? EdLoc.erkenneEinsatzort(suchF.value) : { typ: null };

            if (UEBERNAHME[erkennung.typ]) {
                koordTreffer = erkennung;
                adressTreffer = [];
                zeichneListe();
                zustand();
                return;
            }
            koordTreffer = null;
            if (erkennung.typ === 'plus-kurz' || erkennung.typ === 'ungueltig') {
                adressTreffer = [];
                versteckeListe();
                zustand();
                return;
            }

            zustand();

            /* STAMMDATEN SOFORT, ADRESSEN ENTPRELLT. Die Stammdaten liegen im
             * Browser — auf sie zu warten waere eine Wartezeit ohne Grund; sie
             * erscheinen ab dem ersten Zeichen. Die Adresssuche geht an einen
             * Dritten und bleibt bei ihren drei Grenzen (400 ms, drei Zeichen,
             * eine offene Anfrage). Die Liste wird deshalb zweimal gezeichnet:
             * jetzt mit dem, was da ist, und noch einmal, wenn die Antwort
             * kommt. */
            adressTreffer = [];
            zeichneListe();

            /* IST DIE ADRESSSUCHE AUS, bleibt die Stammdatengruppe stehen —
             * sie kommt aus dem eigenen Bestand und hat mit dem Dienst nichts
             * zu tun. Bis Web 15.5.2 verschwand hier die ganze Liste, weil es
             * nur eine gab. */
            if (!adresssuche) { return; }

            var q = letzteAnfrage;
            if (q.length < MINDESTZEICHEN) { return; }
            timer = setTimeout(function () {
                /* HOECHSTENS EINE OFFENE ANFRAGE. Eine noch laufende wird
                 * abgebrochen, bevor die naechste startet — sonst ueberholen
                 * sich zwei Antworten und die Liste zeigt die zum vorletzten
                 * Stand. */
                if (laufend) { laufend.abort(); }
                laufend = (typeof AbortController === 'function')
                    ? new AbortController() : null;
                var dieser = laufend;
                fetch(PHOTON + encodeURIComponent(q),
                      laufend ? { signal: laufend.signal } : undefined).then(function (r) {
                    return r.json();
                }).then(function (d) {
                    if (dieser !== laufend) { return; }   // ueberholt
                    laufend = null;
                    adressTreffer = d.features || [];
                    zeichneListe();
                }).catch(function (e) {
                    /* Ein ABBRUCH ist kein Fehlschlag: Er heisst, dass gerade
                     * eine neuere Anfrage laeuft. Die Liste zu leeren liesse
                     * sie beim fluessigen Tippen flackern. */
                    if (e && e.name === 'AbortError') { return; }
                    /* Scheitert die Abfrage, bleibt die STAMMDATENGRUPPE
                     * stehen: Sie hat mit dem Dienst nichts zu tun, und eine
                     * leere Liste waere die falsche Auskunft. */
                    adressTreffer = [];
                    zeichneListe();
                });
            }, sofort ? 0 : ENTPRELL_MS);   // Lupe: sofort, Tippen: entprellt
        }

        /* Der Lupen-Knopf (ui_ortsfeld) stoesst die Suche ausdruecklich an —
         * fuer getrennte Suche der EINZIGE Weg, beim Einsatzort ein zweiter
         * neben dem Tippen. */
        if (lupe) {
            lupe.addEventListener('click', function () {
                clearTimeout(timer);
                sucheJetzt();
                feld.focus();
            });
        }
        /* DER BLUR-AUFSCHUB STEHT JETZT IM BAUSTEIN (assets/vorschlagsliste.js,
         * S9/AP1). Dort ist auch der Fund aufgeschrieben, der ihn eingefasst
         * hat: Er darf nicht zuschlagen, wenn der Lupen-Knopf den Fokus nimmt
         * und gleich zurueckgibt (F-P3-AJ). Eine zweite Fassung hier haette
         * die Liste doppelt versteckt. */

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

            /**
             * Uebernahme aus der Ortswahl (Geolocation, Kartendialog —
             * assets/ortswahl.js): Koordinaten setzen; die Adresse aus der
             * Umkehrsuche fuellt das Feld nur, wenn es LEER ist — ein
             * eingetragener Name (oder eine Adresse) wird nie ueberschrieben.
             */
            uebernehmen: function (lat, lon, addr) {
                if (addr && feld.value.trim() === '') {
                    feld.value = addr;
                    letzterTreffer = feld.value.trim().toLowerCase();
                }
                setzeKoordinaten(lat, lon);
            },

            /** Meldungszeile — fuer die Fehlertexte der Ortswahl. */
            melde: melde,

            /** Bezeichnungsfeld — fuer Aufrufer, die es sperren muessen. */
            feld: feld
        };
    }

    global.EdOrtsfeld = { init: init };
})(window);
