/* Ortswahl — „Meine Position übernehmen" und „Auf der Karte wählen".
 * ===========================================================================
 *
 * WOFUER (P3/O5, E-P3-34). Wer am Einsatzort steht oder ihn auf der Karte
 * kennt, soll ihn nicht abtippen muessen. Der Pin-Knopf am Ortsfeld
 * (ui_ortsfeld mit 'ortswahl') oeffnet ein Blatt mit zwei Wegen:
 *
 *   Meine Position uebernehmen   navigator.geolocation (nur ueber HTTPS) —
 *                                die Koordinate des Geraets.
 *   Auf der Karte waehlen        Leaflet-Dialog mit FADENKREUZ in der Mitte:
 *                                Karte verschieben, bis das Kreuz auf dem Ort
 *                                steht, „Uebernehmen". Kein Klick-Marker —
 *                                auf dem Handy verdeckt der eigene Finger
 *                                sonst genau die Stelle, um die es geht.
 *
 * WAS S9/AP2 DAZUGEBAUT HAT (E-S9-06, Mockup M-S9-04):
 *
 *   SUCHFELD IM DIALOGKOPF (PS-1). Bisher zeigte der Dialog Karte, Kreuz und
 *   „Uebernehmen" — wer den Ort nicht auf dem sichtbaren Ausschnitt fand,
 *   musste die Karte von Hand dorthin schieben. Jetzt steht oben ein Feld mit
 *   Lupe. Ein Treffer SETZT NUR DAS KREUZ (`setView`, Zoom 15) und traegt
 *   seinen Namen ins Suchfeld — uebernommen wird erst mit „Uebernehmen"
 *   (F1). Das Kreuz laesst sich danach noch verschieben; ein Treffer ist ein
 *   Vorschlag, wohin man sehen soll, keine Entscheidung.
 *
 *   DIE AUFGEZEICHNETE SPUR (PS-11, Backlog 147). Wer den Einsatzort
 *   nachtraegt, hat die Spur — und der Ort liegt fast immer auf ihr. Sie wird
 *   als Linie in der ersten Spurfarbe gezeichnet, mit einem blauen Ringpunkt
 *   am Anfang und einem roten am Ende. KEINE PFEILE, KEINE LUFTLINIE, KEINE
 *   SCHILDER: Standort und Zielklinik gehoeren nicht in einen Auswahldialog,
 *   sie wuerden das Kreuz verdecken. Ist das Bezeichnungsfeld leer, oeffnet
 *   die Karte AUF DER SPUR (`fitBounds`); steht schon eine Koordinate, gilt
 *   sie wie bisher.
 *
 *   OHNE ADRESSSUCHE (E-S9-05) fehlt das Suchfeld, und nach „Uebernehmen"
 *   bleibt das Bezeichnungsfeld leer statt mit einer Adresse gefuellt — die
 *   Umkehrsuche folgt demselben Schalter. Alles Uebrige ist gleich. Der
 *   Dialog sagt NICHT, warum das Feld fehlt: Das sagt die Karte „Datenschutz"
 *   im Profil, und ein Dialog, der seine eigene Einstellung erklaert, ist
 *   eine Einstellungsseite an der falschen Stelle.
 *
 * WAS HIER NICHT PASSIERT: Speicherlogik und Felder sind unveraendert —
 * dieses Skript setzt dieselben Koordinaten, die auch die Suche setzen
 * wuerde. Die Verschluesselung des Einsatzorts bleibt unberuehrt: Die
 * Anfrage an den Adressdienst traegt NUR die Koordinate, nie Namen, Diagnose
 * oder sonst einen Inhalt (dasselbe Datenschutzprinzip wie bei den
 * Kartenkacheln).
 *
 * Erwartet: Leaflet (L) fuer den Kartendialog, assets/blatt.js fuer das
 * Blatt, assets/geocoder.js fuer Suche und Umkehrsuche,
 * assets/vorschlagsliste.js fuer die Trefferliste, assets/geo.js fuer die
 * Ringpunkte und die Spurfarbe; je Verwendung eine Registrierung ueber
 * EdOrtswahl.registriere().
 */
(function (global) {
    'use strict';

    var RUECKFALL = [47.7, 10.3];      // derselbe Ausgangspunkt wie auf den Karten
    var ZOOM_KOORD = 14;               // gesetzte Koordinate — wie seit O5
    var ZOOM_TREFFER = 15;             // Adresstreffer: eine Stufe naeher (M-S9-04)
    var RAND_PX = 24;                  // Rand beim Einpassen der Spur (M-S9-04)
    var felder = {};                   // praefix -> {steuer, opt}

    /**
     * Eine Verwendung anmelden.
     *
     * @param {string} praefix
     * @param {object} steuer  Steuerobjekt aus EdOrtsfeld.init()
     * @param {object} [opt]
     *   spur   Die aufgezeichnete Spur als [[lat, lon], …] ODER eine Funktion,
     *          die ein Versprechen darauf liefert. DIE FUNKTION IST DER
     *          REGELFALL: Das Einsatzformular holt die Spur ueber
     *          `api/mission.php`, und das soll erst geschehen, wenn jemand
     *          den Dialog tatsaechlich oeffnet — die meisten Formularaufrufe
     *          oeffnen ihn nie, und eine Spur sind einige hundert Punkte.
     *          Geholt wird EINMAL; das Versprechen merkt sich der Aufrufer.
     */
    function registriere(praefix, steuer, opt) {
        if (steuer) { felder[praefix] = { steuer: steuer, opt: opt || {} }; }
    }

    function spurHolen(opt) {
        if (!opt || !opt.spur) { return Promise.resolve(null); }
        try {
            var s = (typeof opt.spur === 'function') ? opt.spur() : opt.spur;
            return Promise.resolve(s).catch(function () { return null; });
        } catch (e) { return Promise.resolve(null); }
    }

    /* Koordinate uebernehmen und die Adresse nachschlagen. Die Uebernahme
     * wartet NICHT auf den Dienst: Die Koordinate ist die Sache, die Adresse
     * ist Komfort — faellt die Umkehrsuche aus oder ist sie abgeschaltet,
     * fehlt nur der Text. */
    function uebernehmen(steuer, lat, lon) {
        lat = Math.round(lat * 1e6) / 1e6;
        lon = Math.round(lon * 1e6) / 1e6;
        steuer.uebernehmen(lat, lon, '');
        EdGeocoder.umkehr(lat, lon).then(function (adresse) {
            if (adresse) { steuer.uebernehmen(lat, lon, adresse); }
        });
    }

    /* ---- Weg 1: Geolocation ---------------------------------------------- */
    function meinePosition(steuer) {
        if (!navigator.geolocation) {
            steuer.melde('Standortbestimmung wird von diesem Browser nicht unterstützt.', true);
            return;
        }
        steuer.melde('Position wird bestimmt …', false);
        navigator.geolocation.getCurrentPosition(
            function (pos) {
                uebernehmen(steuer, pos.coords.latitude, pos.coords.longitude);
            },
            function () {
                steuer.melde('Position nicht verfügbar — Freigabe verweigert oder kein Empfang. '
                    + 'Alternativ „Auf der Karte wählen".', true);
            },
            { enableHighAccuracy: true, timeout: 15000, maximumAge: 60000 }
        );
    }

    /* ---- Weg 2: Kartendialog mit Fadenkreuz ------------------------------- */
    function kartendialog(steuer, opt) {
        if (typeof L === 'undefined') {
            steuer.melde('Kartenbaustein nicht geladen.', true);
            return;
        }
        var mitSuche = (typeof EdGeocoder !== 'undefined') && EdGeocoder.an();
        var werte = steuer.werte();
        var leer = werte.addr === '' && werte.lat === null;

        var dlg = document.createElement('dialog');
        dlg.className = 'dialog dialog-karte';
        /* DAS SUCHFELD STEHT IM KOPF, nicht im Inhalt (M-S9-04, E-S9-06 a).
         * Der Unterschied ist keine Formsache: Im Inhalt schoebe es die Karte
         * nach unten, sobald die Trefferliste aufklappt; im Kopf legt sich die
         * Liste UEBER die Karte, und das Kreuz bleibt, wo es war. Und die
         * Ueberschrift ist jetzt ein `h2` wie in jedem anderen Dialog dieser
         * Anwendung — dieser eine trug seinen Titel seit Web 9.4.0 als nackten
         * Text. */
        dlg.innerHTML =
            '<div class="dialog-kopf"><h2>Auf der Karte wählen</h2>' +
            (mitSuche
                ? '  <div class="dialog-suche">' +
                  '    <div class="ortsfeld-zeile">' +
                  '      <input type="text" data-suche autocomplete="off"' +
                  '             placeholder="Adresse oder Ort suchen">' +
                  '      <button type="button" class="knopf knopf-symbol" data-act="lupe"' +
                  '              title="Suchen">' + edSymbol('lupe', 'symbol-gross') +
                  '        <span class="nur-vorlesen">Suchen</span></button>' +
                  '    </div>' +
                  '    <ul class="vorschlaege" data-liste hidden></ul>' +
                  '  </div>'
                : '') +
            '</div>' +
            '<div class="dialog-inhalt">' +
            '  <div class="ortswahl-karte"><div class="geo" data-karte></div>' +
            '    <span class="ortswahl-kreuz" aria-hidden="true"></span></div>' +
            '  <p class="feld-hinweis">Karte verschieben, bis das Kreuz auf dem Ort steht.</p>' +
            /* Reihenfolge nach M-S9-04: erst der Hinweis, dann die Legende.
             * Die Legende erklaert die Zeichnung darueber und gehoert an den
             * Fuss des Inhalts; der Hinweis sagt, was zu tun ist. */
            '  <div class="legende" data-legende hidden>' +
            '    <span><span class="legende-linie"></span> Aufzeichnung</span>' +
            '    <span><span class="geo-ringpunkt"></span> Start</span>' +
            '    <span><span class="geo-ringpunkt geo-ringpunkt-ende"></span> Ende</span>' +
            '  </div>' +
            '</div>' +
            '<div class="dialog-fuss">' +
            '  <button type="button" class="knopf knopf-leise" data-act="zu">Abbrechen</button>' +
            '  <button type="button" class="knopf knopf-primaer" data-act="ok">Übernehmen</button>' +
            '</div>';
        document.body.appendChild(dlg);

        var mitte = (werte.lat !== null) ? [werte.lat, werte.lon] : RUECKFALL;
        var zoom = (werte.lat !== null) ? ZOOM_KOORD : 9;

        dlg.addEventListener('close', function () { dlg.remove(); });
        dlg.querySelector('[data-act="zu"]').addEventListener('click', function () { dlg.close(); });
        dlg.showModal();

        /* Erst NACH showModal(): Leaflet misst sein Element beim Anlegen —
         * in einem noch unsichtbaren Dialog misst es null. */
        var karte = L.map(dlg.querySelector('[data-karte]'),
            { attributionControl: true, zoomControl: true });
        karte.setView(mitte, zoom);
        if (typeof attachBaseLayers === 'function') { attachBaseLayers(karte); }
        else {
            L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png',
                { maxZoom: 19, attribution: '&copy; OpenStreetMap' }).addTo(karte);
        }

        /* ---- Die Spur, sobald sie da ist ---------------------------------
         *
         * Der Dialog wartet nicht auf sie: Er steht sofort, und die Spur
         * kommt nach. Wer in der Zwischenzeit schon geschoben hat, hat
         * entschieden — `fitBounds` faellt dann aus, sonst risse es ihm die
         * Karte unter dem Kreuz weg. */
        var geschoben = false;
        karte.on('dragstart zoomstart', function () { geschoben = true; });

        spurHolen(opt).then(function (spur) {
            if (!spur || spur.length < 2 || !karte.getContainer()) { return; }
            L.polyline(spur, {
                color: EdGeo.spurFarbe(0), weight: 4, smoothFactor: 0,
                interactive: false
            }).addTo(karte);
            EdGeo.markerRing(spur[0], 'start').addTo(karte);
            EdGeo.markerRing(spur[spur.length - 1], 'ende').addTo(karte);
            var legende = dlg.querySelector('[data-legende]');
            if (legende) { legende.hidden = false; }
            /* NUR BEI LEEREM FELD auf die Spur einpassen (E-S9-06 b): Steht
             * schon eine Koordinate, ist sie die Aussage — die Spur ist dann
             * Zusatz und darf den Ausschnitt nicht bestimmen. */
            if (leer && !geschoben) {
                karte.fitBounds(L.latLngBounds(spur),
                    { padding: [RAND_PX, RAND_PX] });
            }
        });

        /* ---- Das Suchfeld ------------------------------------------------ */
        if (mitSuche) {
            var feld = dlg.querySelector('[data-suche]');
            var liste = dlg.querySelector('[data-liste]');
            var steuerListe = EdVorschlaege.init({
                feld: feld, behaelter: feld.closest('.dialog-suche'), liste: liste,
                beiWahl: function (e) {
                    /* SETZT NUR DAS KREUZ (F1). Der Name wandert ins
                     * Suchfeld, damit man sieht, wonach die Karte steht —
                     * nicht in das Feld des Formulars. */
                    feld.value = e.voll;
                    geschoben = true;
                    karte.setView([e.wert.lat, e.wert.lon], ZOOM_TREFFER);
                }
            });
            var zeige = function (sofort) {
                var q = feld.value.trim();
                if (q === '') { steuerListe.verstecke(); return; }
                EdGeocoder.suche(q, { sofort: !!sofort }).then(function (treffer) {
                    if (treffer === null) { return; }          // ueberholt
                    steuerListe.zeige([{ titel: null, eintraege: treffer.map(function (t) {
                        return { haupt: t.haupt, neben: t.neben, symbol: 'standort',
                                 art: 'adresse', wert: t, voll: t.voll };
                    }) }], q);
                });
            };
            feld.addEventListener('input', function () { zeige(false); });
            dlg.querySelector('[data-act="lupe"]').addEventListener('click', function () {
                zeige(true);
                feld.focus();
            });
        }

        dlg.querySelector('[data-act="ok"]').addEventListener('click', function () {
            var c = karte.getCenter();
            dlg.close();
            uebernehmen(steuer, c.lat, c.lng);
        });
    }

    /* ---- Blatt-Eintraege (ui_ortsfeld, data-ortswahl) --------------------- */
    document.addEventListener('click', function (ev) {
        var knopf = ev.target.closest ? ev.target.closest('[data-ortswahl]') : null;
        if (!knopf) { return; }
        var eintrag = felder[knopf.dataset.praefix];
        if (!eintrag) { return; }
        if (global.edBlatt) { global.edBlatt.zu(); }
        if (knopf.dataset.ortswahl === 'position') { meinePosition(eintrag.steuer); }
        else { kartendialog(eintrag.steuer, eintrag.opt); }
    });

    global.EdOrtswahl = { registriere: registriere };
})(window);
