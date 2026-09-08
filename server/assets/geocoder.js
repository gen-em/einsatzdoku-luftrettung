/* Geocoder — die EINE Stelle, an der die Anwendung eine Adresse nachfragt
 * (S9/AP2, E-S9-05).
 * ===========================================================================
 *
 * WARUM ES DIESE DATEI GIBT. Bis Web 15.7.1 stand die Dienstadresse zweimal
 * fest im Code, und keine der beiden Stellen war abschaltbar:
 *
 *   assets/ortsfeld.js   die Vorwaertssuche beim Tippen (`…/api/?q=`). Die
 *                        Komponente kannte die Option `adresssuche`, aber
 *                        kein Aufrufer setzte sie je.
 *   assets/ortswahl.js   die Umkehrsuche nach jeder Kartenwahl
 *                        (`…/reverse?lat=&lon=`). Sie kannte gar keinen
 *                        Schalter.
 *
 * Dazu stand die Beschriftung eines Treffers in beiden Dateien wortgleich
 * (`photonLabel()` hier, `label()` dort) — zwei Fassungen derselben zehn
 * Zeilen, die auseinanderlaufen konnten und es beim naechsten Feld getan
 * haetten.
 *
 * DER KRYPTO-REVIEW HAT DEN ABFLUSS BENANNT (K-6): Jede Anfrage traegt die
 * eingetippten Buchstaben zu einem Dritten, und beim Einsatzort sind das
 * Ortsangaben zu einem laufenden Einsatz. Die Umkehrsuche traegt die
 * Koordinate. R78/F-SP-4 hat entschieden, wie damit umzugehen ist: nicht
 * abschaffen — die Adresssuche ist die bequemste Art, einen Ort zu setzen —,
 * sondern SAGEN und ABSCHALTBAR machen.
 *
 * ZWEI SCHALTER, EINE ADRESSE (E-S9-05):
 *
 *   je Installation   Betrieb -> Servereinstellungen, Karte „Adresssuche".
 *                     Sie ist die OBERGRENZE: Ist sie aus, hilft kein
 *                     Kontoschalter.
 *   je Konto          Einstellungen -> Profil, Karte „Datenschutz".
 *   die Adresse       dieselbe Karte, Feld „Dienst" — wer nach der
 *                     Hosting-Entscheidung (R36) einen eigenen Dienst
 *                     betreibt, traegt sie ein, und die Anfragen mit dem
 *                     Einsatzort verlassen das eigene Haus nicht. Kein Code,
 *                     keine Auslieferung.
 *
 * DIE VORGABEADRESSE STEHT NICHT IN DIESER DATEI, und das ist Absicht: Sie
 * steht genau einmal, in `server/geocoder_lib.php`, und kommt ueber den
 * Bootstrap hierher. Ein zweiter Wert im Skript waere eine zweite Wahrheit —
 * und schlimmer: Er wuerde eine Seite ohne Bootstrap stillschweigend an einen
 * Dritten sprechen lassen, obwohl niemand das eingestellt hat. Fehlt die
 * Adresse, ist die Suche AUS. Das ist die sichere Richtung, und es kann nur
 * passieren, wenn jemand `ui_ortsfeld()` umgeht.
 *
 * Beides kommt als Konstanten in die Seite (ui_geocoder_bootstrap() in
 * ui.php); dieses Modul liest sie und sonst nichts.
 *
 * AUS HEISST AUS. `suche()` und `umkehr()` senden dann NICHTS und liefern ein
 * leeres Ergebnis — nicht einen Fehler, denn es ist keiner. Was ohne den
 * Dienst weiterhin geht: Koordinaten, Plus Codes, „Meine Position" und die
 * Karte. Das Suchfeld im Kartendialog erscheint gar nicht erst.
 *
 * DIE DREI GRENZEN DER ABFRAGE stehen hier und nicht bei den Aufrufern —
 * Photon ist ein frei betriebener Gemeinschaftsdienst, und eine Anfrage je
 * Tastendruck waere Missbrauch seiner Gutmuetigkeit:
 *
 *   ENTPRELL_MS      400 ms Ruhe nach dem letzten Tastendruck (E-S3-06
 *                    erlaubt 300–600). Bei fluessigem Tippen eines
 *                    Ortsnamens entsteht damit genau EINE Anfrage. Die Lupe
 *                    umgeht sie mit `{sofort: true}`, nicht die Mindestlaenge.
 *   MINDESTZEICHEN   drei. Unter drei Zeichen sucht niemand ernsthaft.
 *   AbortController  hoechstens eine offene Anfrage. Ohne das ueberholen sich
 *                    zwei Antworten, und die Liste zeigt die zum vorletzten
 *                    Stand — der Fehler faellt nur im langsamen Netz auf.
 *
 * ABHAENGIGKEITEN: keine. Das Modul kennt weder DOM noch Leaflet; es nimmt
 * eine Zeichenkette und liefert ein Versprechen.
 */
(function (global) {
    'use strict';

    var ENTPRELL_MS = 400;
    var MINDESTZEICHEN = 3;
    var GRENZE = 6;                    // hoechstens sechs Treffer je Anfrage

    var timer = null;
    var laufend = null;                // AbortController der offenen Anfrage

    /** Die Dienstadresse ohne Schraegstrich am Ende, oder '' ohne Bootstrap. */
    function dienst() {
        var d = typeof global.GEO_DIENST === 'string' ? global.GEO_DIENST.trim() : '';
        return d.replace(/\/+$/, '');
    }

    /**
     * Ist die Adresssuche eingeschaltet? Installation UND Konto — UND eine
     * Adresse muss dastehen. Ohne Adresse gibt es nichts zu fragen.
     */
    function an() {
        return global.GEO_AN === true && dienst() !== '';
    }

    /** Der Rechnername des Dienstes — fuer den Hinweis am Feld. */
    function host() {
        try { return new URL(dienst()).host; }
        catch (e) { return dienst(); }
    }

    /* ---- Beschriftung eines Treffers -------------------------------------
     *
     * EINE ZEILE FUERS FELD, ZWEI FUER DIE LISTE (M-S9-03/M-S9-04). Ins Feld
     * wandert `voll` — dort steht eine Adresse, kein Absatz. In der Liste
     * steht `haupt` oben und `neben` gedaempft darunter: Auf einem
     * 390-px-Schirm schnitt die einzeilige Fassung genau dort ab, wo die
     * Unterscheidung zweier gleichnamiger Treffer beginnt. */
    function haupt(p) {
        var teile = [];
        if (p.name) { teile.push(p.name); }
        var strasse = [p.street, p.housenumber].filter(Boolean).join(' ');
        if (strasse && strasse !== p.name) { teile.push(strasse); }
        return teile.join(', ');
    }

    function neben(p) {
        return [p.postcode, p.city].filter(Boolean).join(' ');
    }

    function voll(p) {
        return [haupt(p), neben(p)].filter(Boolean).join(', ');
    }

    /** Ein Photon-Merkmal in die Form bringen, die die Aufrufer brauchen. */
    function treffer(ft) {
        var p = ft.properties || {};
        var c = (ft.geometry && ft.geometry.coordinates) || [];
        return {
            haupt: haupt(p), neben: neben(p), voll: voll(p),
            lat: c[1], lon: c[0],
            roh: ft
        };
    }

    /**
     * Adressen suchen.
     *
     * @param {string} q      der getippte Text
     * @param {object} [opt]  {sofort: true} umgeht die Entprellung (Lupe)
     * @returns {Promise} Liste von Treffern — LEER, wenn die Suche aus ist,
     *   der Text zu kurz ist oder der Dienst nichts liefert; **null**, wenn
     *   diese Anfrage von einer neueren ueberholt wurde. Wer `null` bekommt,
     *   zeichnet nichts: Die neuere Anfrage tut es.
     */
    function suche(q, opt) {
        opt = opt || {};
        clearTimeout(timer);
        var text = String(q == null ? '' : q).trim();
        if (!an() || text.length < MINDESTZEICHEN) {
            return Promise.resolve([]);
        }
        return new Promise(function (fertig) {
            timer = setTimeout(function () {
                if (laufend) { laufend.abort(); }
                laufend = (typeof AbortController === 'function')
                    ? new AbortController() : null;
                var dieser = laufend;
                var url = dienst() + '/api/?lang=de&limit=' + GRENZE
                        + '&q=' + encodeURIComponent(text);
                fetch(url, laufend ? { signal: laufend.signal } : undefined)
                    .then(function (r) { return r.json(); })
                    .then(function (d) {
                        if (dieser !== laufend) { fertig(null); return; }  // ueberholt
                        laufend = null;
                        fertig((d.features || []).map(treffer));
                    })
                    .catch(function (e) {
                        /* Ein ABBRUCH ist kein Fehlschlag: Er heisst, dass
                         * gerade eine neuere Anfrage laeuft. */
                        fertig(e && e.name === 'AbortError' ? null : []);
                    });
            }, opt.sofort ? 0 : ENTPRELL_MS);
        });
    }

    /**
     * Umkehrsuche: zu einer Koordinate eine Adresse.
     *
     * @returns {Promise} die Adresse als eine Zeile, oder '' — wenn die Suche
     *   aus ist, der Dienst nichts kennt oder die Anfrage scheitert. Die
     *   Adresse ist KOMFORT, nicht die Sache: Die Koordinate steht schon.
     */
    function umkehr(lat, lon) {
        if (!an()) { return Promise.resolve(''); }
        var url = dienst() + '/reverse?lang=de&lat=' + encodeURIComponent(lat)
                + '&lon=' + encodeURIComponent(lon);
        return fetch(url)
            .then(function (r) { return r.json(); })
            .then(function (d) {
                var ft = (d.features || [])[0];
                return ft ? voll(ft.properties || {}) : '';
            })
            .catch(function () { return ''; });
    }

    global.EdGeocoder = {
        an: an, dienst: dienst, host: host,
        suche: suche, umkehr: umkehr,
        MINDESTZEICHEN: MINDESTZEICHEN
    };
})(window);
