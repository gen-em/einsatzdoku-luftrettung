/* Luftlinie — Abfahrtort → Einsatzort → Zielklinik (Web 6.1.0, E34–E36).
 *
 * WOFUER. Faellt die Uhr aus oder wird ohne Uhr gearbeitet, fehlt der Track und
 * die Karte bleibt leer, obwohl der Einsatzort bekannt ist. Dieses Modul
 * zeichnet dann eine gerade Verbindung zwischen den bekannten Punkten — und
 * benennt sie ausdruecklich als Luftlinie.
 *
 * DER TRACK HAT IMMER VORRANG (E35). Liegt er vor, unterbleibt die Linie; die
 * Abfahrtortangabe bleibt gespeichert und wird lediglich nicht dargestellt.
 * Faellt der Track spaeter weg, erscheint sie wieder.
 *
 * OHNE EINSATZORT KEINE LINIE (A13n). Auch dann nicht, wenn Abfahrtort und
 * Zielklinik beide Koordinaten haben: Eine direkte Verbindung zwischen beiden
 * hat nie stattgefunden und waere eine Falschaussage.
 *
 * KEIN AUSWEICHEN (A13i). Fehlt die Koordinate der GEWAEHLTEN Quelle — kein
 * vorheriger Einsatz, Standort ohne Koordinaten, Zielklinik ohne Koordinaten —,
 * entsteht keine Linie. Es wird nicht stillschweigend eine andere Quelle
 * genommen, weil eine falsche Linie schlechter ist als keine.
 *
 * KEINE STATISTIKWIRKUNG (E36). Die Laenge steht an der Linie und sonst
 * nirgends: nicht in einer Kachel, nicht in einem Filter. Eine Luftlinie und
 * eine gefahrene Strecke sind nicht dieselbe Groesse, und der Einsatzort liegt
 * ohnehin verschluesselt im pat_blob — serverseitig ist er nicht lesbar.
 *
 * Zwei Seiten benutzen dieses Modul (einsatz.php, index.php). Sie unterscheiden
 * sich darin, WOHER die Punkte kommen; was mit ihnen geschieht, steht hier.
 */
(function (global) {
    'use strict';

    /* Max Blau. Bewusst weit weg vom Track-Orange #FF8F1F: Eine gerechnete
     * Verbindung darf nicht wie eine gemessene Spur aussehen. Zusaetzlich
     * gestrichelt — Farbe allein traegt die Unterscheidung nicht. */
    var FARBE = '#4280E5';
    var STRICHEL = '8 7';

    function zahl(v) {
        if (v === null || v === undefined || v === '') { return null; }
        var n = typeof v === 'number' ? v : parseFloat(v);
        return isFinite(n) ? n : null;
    }

    /** {lat, lon, …} -> {lat, lon, text} oder null, wenn keine Koordinate. */
    function punkt(o, text) {
        if (!o) { return null; }
        var la = zahl(o.lat), lo = zahl(o.lon);
        if (la === null || lo === null) { return null; }
        return { lat: la, lon: lo, text: text || o.text || '' };
    }

    /**
     * Abfahrtort aus der REGEL aufloesen (Konzept 4.6.1).
     *
     * Gespeichert ist in `missions.start_src` nur die Regel; wo die Koordinate
     * herkommt, haengt daran — und mit ihr, ob sie im Klartext steht oder
     * verschluesselt ist:
     *
     *   base       days.base_lat/base_lon, eingefroren beim Anlegen  Klartext
     *   prev_site  Einsatzort des vorherigen Einsatzes des Tages     verschlüsselt
     *   prev_dest  dest_lat/dest_lon des vorherigen Einsatzes        Klartext
     *   manual     pat_blob.start                                    verschlüsselt
     *
     * @param {?string} regel   Wert aus missions.start_src
     * @param {object}  quellen {base, prevSite, prevDest, manual} — je
     *                          {lat, lon} oder null/undefined
     */
    function abfahrt(regel, quellen) {
        quellen = quellen || {};
        if (regel === 'base')      { return punkt(quellen.base, 'Standort'); }
        if (regel === 'prev_site') { return punkt(quellen.prevSite, 'Letzter Einsatzort'); }
        if (regel === 'prev_dest') { return punkt(quellen.prevDest, 'Letzte Zielklinik'); }
        if (regel === 'manual') {
            var m = punkt(quellen.manual, 'Abfahrtort');
            if (m && quellen.manual && quellen.manual.addr) { m.text = quellen.manual.addr; }
            return m;
        }
        return null;                       // nichts gewählt: keine Linie
    }

    /**
     * Stuetzpunkte der Linie in der Reihenfolge Abfahrt → Einsatzort → Ziel.
     *
     * Liefert eine LEERE Liste, wenn keine Linie entstehen darf. Die drei
     * Bedingungen stehen unten ausgeschrieben, weil sie fachlich sind und nicht
     * technisch.
     */
    function punkte(ctx) {
        ctx = ctx || {};
        if (ctx.hatTrack) { return []; }                 // E35: Track hat Vorrang
        var ort = punkt(ctx.ort, ctx.ort && ctx.ort.addr ? ctx.ort.addr : 'Einsatzort');
        if (!ort) { return []; }                         // A13n: ohne Einsatzort keine Linie
        var start = ctx.abfahrt || null;
        var ziel = punkt(ctx.ziel, ctx.ziel && ctx.ziel.name ? ctx.ziel.name : 'Zielklinik');
        if (!start && !ziel) { return []; }              // ein einzelner Punkt ist keine Linie

        var liste = [];
        if (start) { liste.push(start); }
        liste.push(ort);
        if (ziel) { liste.push(ziel); }
        return liste;
    }

    /**
     * Summe der Grosskreisdistanzen in Metern.
     *
     * Kein Umwegfaktor: Eine gerechnete Fahrstrecke taeuschte eine Genauigkeit
     * vor, die es nicht gibt (Konzept 4.6.1). Bei drei Punkten ist es die Summe
     * beider Abschnitte.
     */
    function meter(liste) {
        var R = 6371000, s = 0;
        for (var i = 1; i < liste.length; i++) {
            var a = liste[i - 1], b = liste[i];
            var p1 = a.lat * Math.PI / 180, p2 = b.lat * Math.PI / 180;
            var dp = (b.lat - a.lat) * Math.PI / 180;
            var dl = (b.lon - a.lon) * Math.PI / 180;
            var h = Math.sin(dp / 2) * Math.sin(dp / 2)
                  + Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) * Math.sin(dl / 2);
            s += 2 * R * Math.atan2(Math.sqrt(h), Math.sqrt(1 - h));
        }
        return s;
    }

    /** „12,3 km Luftlinie" — die Benennung ist Teil der Aussage (E36). */
    function text(liste) {
        return (meter(liste) / 1000).toFixed(1).replace('.', ',') + ' km Luftlinie';
    }

    /**
     * Linie zeichnen und die Ebenen zurueckgeben.
     *
     * @param map        Leaflet-Karte
     * @param liste      Ergebnis von punkte()
     * @param opt.ziel   Ebenengruppe statt der Karte (Tagesuebersicht)
     * @param opt.farbe  abweichende Farbe (Tagesuebersicht faerbt je Einsatz)
     * @param opt.titel  Zusatz im Tooltip, z. B. „Einsatz 3"
     */
    function zeichne(map, liste, opt) {
        opt = opt || {};
        if (!liste || liste.length < 2) { return []; }
        var ziel = opt.ziel || map;
        var farbe = opt.farbe || FARBE;
        var linie = L.polyline(liste.map(function (p) { return [p.lat, p.lon]; }), {
            color: farbe, weight: 3, opacity: 0.9,
            dashArray: STRICHEL, smoothFactor: 0
        });
        linie.bindPopup((opt.titel ? opt.titel + '<br>' : '') + text(liste)
            + '<br><span class="muted">gerade Verbindung, kein aufgezeichneter Weg</span>');
        ziel.addLayer ? ziel.addLayer(linie) : linie.addTo(map);
        return [linie];
    }

    global.EdLuftlinie = {
        abfahrt: abfahrt,
        punkte: punkte,
        meter: meter,
        text: text,
        zeichne: zeichne,
        FARBE: FARBE
    };
})(window);
