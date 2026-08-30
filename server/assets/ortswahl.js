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
 * Zur Koordinate holt die PHOTON-UMKEHRSUCHE eine Adresse (dieselbe Quelle
 * wie die Adresssuche des Ortsfelds; OSM-Daten, kein Schluessel). Die
 * Adresse fuellt das Feld nur, wenn es LEER ist — ein eingetragener Name
 * wird nie ueberschrieben (ortsfeld.js, uebernehmen()).
 *
 * WAS HIER NICHT PASSIERT: Speicherlogik und Felder sind unveraendert —
 * dieses Skript setzt dieselben Koordinaten, die auch die Suche setzen
 * wuerde. Die Verschluesselung des Einsatzorts bleibt unberuehrt: Die
 * Anfrage an Photon traegt NUR die Koordinate, nie Namen, Diagnose oder
 * sonst einen Inhalt (dasselbe Datenschutzprinzip wie bei den
 * Kartenkacheln).
 *
 * Erwartet: Leaflet (L) fuer den Kartendialog, assets/blatt.js fuer das
 * Blatt, je Verwendung eine Registrierung ueber EdOrtswahl.registriere().
 */
(function (global) {
    'use strict';

    var REVERSE = 'https://photon.komoot.io/reverse?lang=de&';
    var RUECKFALL = [47.7, 10.3];      // derselbe Ausgangspunkt wie auf den Karten
    var felder = {};                   // praefix -> Steuerobjekt aus EdOrtsfeld

    function registriere(praefix, steuer) {
        if (steuer) { felder[praefix] = steuer; }
    }

    /** Beschriftung eines Photon-Treffers — dieselbe Form wie im Ortsfeld. */
    function label(p) {
        var teile = [];
        if (p.name) { teile.push(p.name); }
        var strasse = [p.street, p.housenumber].filter(Boolean).join(' ');
        if (strasse && strasse !== p.name) { teile.push(strasse); }
        var ort = [p.postcode, p.city].filter(Boolean).join(' ');
        if (ort) { teile.push(ort); }
        return teile.join(', ');
    }

    /* Koordinate uebernehmen und die Adresse nachschlagen. Die Uebernahme
     * wartet NICHT auf Photon: Die Koordinate ist die Sache, die Adresse ist
     * Komfort — faellt die Umkehrsuche aus, fehlt nur der Text. */
    function uebernehmen(steuer, lat, lon) {
        lat = Math.round(lat * 1e6) / 1e6;
        lon = Math.round(lon * 1e6) / 1e6;
        steuer.uebernehmen(lat, lon, '');
        fetch(REVERSE + 'lat=' + lat + '&lon=' + lon)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var ft = (d.features || [])[0];
                if (ft) { steuer.uebernehmen(lat, lon, label(ft.properties)); }
            })
            .catch(function () { /* Adresse ist Komfort, kein Muss */ });
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
    function kartendialog(steuer) {
        if (typeof L === 'undefined') {
            steuer.melde('Kartenbaustein nicht geladen.', true);
            return;
        }
        var dlg = document.createElement('dialog');
        dlg.className = 'dialog dialog-karte';
        dlg.innerHTML =
            '<div class="dialog-kopf">Auf der Karte wählen</div>' +
            '<div class="dialog-inhalt">' +
            '  <div class="ortswahl-karte"><div class="geo" data-karte></div>' +
            '    <span class="ortswahl-kreuz" aria-hidden="true"></span></div>' +
            '  <p class="feld-hinweis">Karte verschieben, bis das Kreuz auf dem Ort steht.</p>' +
            '</div>' +
            '<div class="dialog-fuss">' +
            '  <button type="button" class="knopf knopf-leise" data-act="zu">Abbrechen</button>' +
            '  <button type="button" class="knopf knopf-primaer" data-act="ok">Übernehmen</button>' +
            '</div>';
        document.body.appendChild(dlg);

        var werte = steuer.werte();
        var mitte = (werte.lat !== null) ? [werte.lat, werte.lon] : RUECKFALL;
        var zoom = (werte.lat !== null) ? 14 : 9;

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
        var steuer = felder[knopf.dataset.praefix];
        if (!steuer) { return; }
        if (global.edBlatt) { global.edBlatt.zu(); }
        if (knopf.dataset.ortswahl === 'position') { meinePosition(steuer); }
        else { kartendialog(steuer); }
    });

    global.EdOrtswahl = { registriere: registriere };
})(window);
