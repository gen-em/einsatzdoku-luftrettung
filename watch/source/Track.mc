// Einsatzdoku — GPS-Aufzeichnung mit Ausduennung und Chunk-Persistenz.
//
// Punktformat im Puffer (flach, speicherschonend):
//   [lat0, lon0, ele0, ts0, lat1, lon1, ele1, ts1, ...]
// Persistenz: Chunks a 200 Punkte unter Schluesseln "<ref>_<n>", damit die
// 8-KB-Grenze pro Storage-Wert eingehalten wird. Meta unter K_TRACK_META.
using Toybox.Position;
using Toybox.Application;
using Toybox.Application.Storage;
using Toybox.Lang;
using Toybox.Math;
using Toybox.WatchUi;

// Traeger fuer den Positions-Rueckruf (method() gibt es nur auf Objekten).
class TrackCb {
    function initialize() {}
    function onPosition(info as Position.Info) as Void { Track.onPosition(info); }
}

module Track {

    const CHUNK_POINTS = 200;

    var _cb as TrackCb or Null = null;

    // aktiver Puffer (Einsatz ODER Ruhe-Segment — nie beides)
    var _ref as Lang.String or Null = null;   // client_ref des aktiven Tracks
    var _isMission as Lang.Boolean = false;
    var _buf as Lang.Array<Lang.Numeric or Null> = [];              // flacher Punktpuffer (nur Tail seit letztem Flush)
    var _count as Lang.Number = 0;            // Gesamtpunkte des aktiven Tracks

    var _lastLat as Lang.Double or Null = null;
    var _lastLon as Lang.Double or Null = null;
    var _lastEle as Lang.Float or Null = null;
    var _lastTs as Lang.Number = 0;

    var distanceM as Lang.Float = 0.0;        // aktueller Einsatz
    var ascentM as Lang.Float = 0.0;
    var speedMs as Lang.Float = 0.0;          // aktuelle Geschwindigkeit (m/s)

    // Anzeige-Polylinie (nur aktueller Einsatz), flach [lat,lon,...]
    var display as Lang.Array<Lang.Double> = [];
    var _displayStride as Lang.Number = 1;    // jeder n-te Punkt kommt in die Anzeige
    var _sinceDisplay as Lang.Number = 0;

    var _lastRestSync as Lang.Number = 0;

    // ---- Lebenszyklus -------------------------------------------------------

    function startPositioning() as Void {
        if (_cb == null) { _cb = new TrackCb(); }
        Position.enableLocationEvents(Position.LOCATION_CONTINUOUS, _cb.method(:onPosition));
    }

    function stopPositioning() as Void {
        if (_cb == null) { _cb = new TrackCb(); }
        Position.enableLocationEvents(Position.LOCATION_DISABLE, _cb.method(:onPosition));
    }

    function beginMissionTrack(ref as Lang.String) as Void {
        _flush();
        _ref = ref; _isMission = true;
        _buf = []; _count = 0;
        distanceM = 0.0; ascentM = 0.0;
        display = []; _displayStride = 1; _sinceDisplay = 0;
        _saveMeta();
    }

    function endMissionTrack() as Void { _flush(); _ref = null; }

    function beginRestTrack(ref as Lang.String) as Void {
        _flush();
        _ref = ref; _isMission = false;
        _buf = []; _count = 0;
        _saveMeta();
    }

    function endRestTrack() as Void { _flush(); _ref = null; }

    // ---- Positions-Callback -------------------------------------------------

    function onPosition(info as Position.Info) as Void {
        // Ueber lokale Variablen statt ueber info.xxx: Die Typpruefung verfolgt
        // eine Null-Pruefung nur bei lokalen Variablen weiter, nicht ueber den
        // Feldzugriff hinweg.
        var pos = info.position;
        if (_ref == null || pos == null) { return; }
        var acc = info.accuracy;
        if (acc != null && acc < Position.QUALITY_POOR) { return; }

        var deg = pos.toDegrees();
        var lat = deg[0]; var lon = deg[1];
        var ele = info.altitude;
        var now = Util.epochNow();

        // Geschwindigkeit immer uebernehmen (auch wenn der Punkt ausgeduennt wird)
        var spd = info.speed;
        if (spd != null) { speedMs = spd; }

        // Ausduennung: nie oefter als 1/s; dann >= 15 m ODER >= 10 s
        if (now - _lastTs < Const.THIN_MIN_GAP_S) { return; }
        var pLat = _lastLat; var pLon = _lastLon;   // lokal: s. Hinweis oben
        if (pLat != null && pLon != null) {
            var d = _haversine(pLat, pLon, lat, lon);
            if (d < Const.THIN_MIN_DIST_M && (now - _lastTs) < Const.THIN_MAX_GAP_S) {
                return;
            }
            if (_isMission) {
                distanceM += d;
                if (ele != null && _lastEle != null && ele > _lastEle) {
                    ascentM += (ele - _lastEle);
                }
            }
        }

        _buf.add(lat); _buf.add(lon); _buf.add(ele); _buf.add(now);
        _count += 1;
        _lastLat = lat; _lastLon = lon; _lastEle = ele; _lastTs = now;

        if (_isMission) { _addDisplayPoint(lat, lon); }

        // Flash-Persistenz: gebuendelt, sobald ein Chunk voll ist
        if (_buf.size() >= CHUNK_POINTS * 4) { _flush(); }

        // Periodischer Ruhe-Sync + Nachzuegler (Anforderungen 2)
        if (now - _lastRestSync > Const.REST_SYNC_INTERVAL_S) {
            _lastRestSync = now;
            Uploader.syncAll();
        }
        WatchUi.requestUpdate();
    }

    function lastLatLon() as Lang.Array {
        return [_lastLat, _lastLon];
    }

    // ---- Anzeige-Polylinie (Cap 1000 mit Dichte-Halbierung) -----------------

    function _addDisplayPoint(lat as Lang.Double, lon as Lang.Double) as Void {
        _sinceDisplay += 1;
        if (_sinceDisplay < _displayStride) { return; }
        _sinceDisplay = 0;
        display.add(lat); display.add(lon);

        if (display.size() >= Const.DISPLAY_MAX_POINTS * 2) {
            // jeden zweiten Punkt entfernen -> gesamter Track bleibt sichtbar
            var halved = [] as Lang.Array<Lang.Double>;
            for (var i = 0; i < display.size(); i += 4) {
                halved.add(display[i]); halved.add(display[i + 1]);
            }
            display = halved;
            _displayStride *= 2;
        }
    }

    // ---- Persistenz + Upload-Zugriff ---------------------------------------

    function _flush() as Void {
        var ref = _ref;                             // lokal: s. Hinweis oben
        if (ref == null || _buf.size() == 0) { return; }
        var chunkIdx = (_count - (_buf.size() / 4)) / CHUNK_POINTS;
        Storage.setValue(ref + "_" + chunkIdx.toString(),
                         _buf as Lang.Array<Application.PropertyValueType>);
        // Achtung: Chunks sind nur dann sauber ausgerichtet, wenn immer bei
        // vollem Chunk geflusht wird; Rest-Flush (App-Ende) erzeugt Teilchunk,
        // der beim naechsten Punkt ueberschrieben wuerde -> deshalb nach einem
        // Teil-Flush Puffer NICHT leeren, sondern weiterfuehren.
        if (_buf.size() >= CHUNK_POINTS * 4) { _buf = []; }
        _saveMeta();
    }

    function flushForShutdown() as Void { _flush(); }

    function _saveMeta() as Void {
        Storage.setValue(Const.K_TRACK_META, {
            "ref" => _ref, "isMission" => _isMission, "count" => _count,
            "dist" => distanceM, "asc" => ascentM
        });
    }

    function restore() as Void {
        var m = Storage.getValue(Const.K_TRACK_META);
        if (m instanceof Lang.Dictionary && m["ref"] != null) {
            // Storage.getValue() liefert einen Sammeltyp, der von BitmapResource
            // bis ScanResult alles einschliesst. Was hier herauskommt, hat
            // _saveMeta() geschrieben — die Zusicherungen halten den Typprüfer
            // an derselben Stelle fest, an der die Struktur ohnehin feststeht.
            var ref = m["ref"] as Lang.String;
            _ref = ref; _isMission = true.equals(m["isMission"]);
            _count = m["count"] != null ? m["count"] as Lang.Number : 0;
            distanceM = m["dist"] != null ? m["dist"] as Lang.Float : 0.0;
            ascentM = m["asc"] != null ? m["asc"] as Lang.Float : 0.0;

            // WICHTIG: Ein beim Herunterfahren gesicherter TEIL-Chunk muss
            // zurueck in den RAM-Puffer, damit die Chunk-Ausrichtung stimmt
            // (Invariante: _count - _buf/4 ist immer ein Vielfaches von
            // CHUNK_POINTS). Sonst wuerde der naechste volle Chunk den
            // Teil-Chunk ueberschreiben -> Datenverlust.
            _buf = [];
            if (_count % CHUNK_POINTS != 0) {
                var tail = Storage.getValue(ref + "_" + (_count / CHUNK_POINTS).toString());
                if (tail instanceof Lang.Array) {
                    _buf = tail as Lang.Array<Lang.Numeric or Null>;
                }
            }

            // Anzeige-Polylinie aus Chunks grob rekonstruieren
            display = []; _displayStride = 1; _sinceDisplay = 0;
            if (_isMission) {
                var pts = readPoints(ref, 0, _count);
                for (var i = 0; i < pts.size(); i += 4) {
                    _addDisplayPoint(pts[i] as Lang.Double, pts[i + 1] as Lang.Double);
                }
            }
        }
    }

    // Punkte [seqFrom, seqFrom+n) eines Tracks lesen (fuer Upload).
    // Aktiver Track: alles ab Tail-Beginn kommt aus dem RAM-Puffer (der auch
    // einen evtl. Teil-Chunk aus dem Flash enthaelt, siehe restore()).
    function readPoints(ref as Lang.String, seqFrom as Lang.Number, n as Lang.Number) as Lang.Array {
        var out = [];
        var seq = seqFrom;
        var isActive = ref.equals(_ref);
        var tailStart = isActive ? (_count - (_buf.size() / 4)) : -1;
        while (out.size() / 4 < n) {
            var chunk;
            var offs; var end;
            if (isActive && seq >= tailStart) {
                chunk = _buf;
                offs = (seq - tailStart) * 4;
                end = chunk.size();
            } else {
                chunk = Storage.getValue(ref + "_" + (seq / CHUNK_POINTS).toString())
                        as Lang.Array<Lang.Numeric or Null> or Null;
                if (chunk == null) { break; }
                offs = (seq % CHUNK_POINTS) * 4;
                end = chunk.size();
                if (isActive) {
                    // Flash nie ueber den Tail-Beginn hinaus lesen (Teil-Chunk
                    // dort ist nur eine Kopie des Puffer-Anfangs)
                    var chunkStart = (seq / CHUNK_POINTS) * CHUNK_POINTS;
                    var maxInChunk = (tailStart - chunkStart) * 4;
                    if (maxInChunk < end) { end = maxInChunk; }
                }
            }
            if (offs < 0 || offs >= end) { break; }
            while (offs < end && out.size() / 4 < n) {
                out.add(chunk[offs]); out.add(chunk[offs + 1]);
                out.add(chunk[offs + 2]); out.add(chunk[offs + 3]);
                offs += 4; seq += 1;
            }
        }
        return out;
    }

    function pointCount(ref as Lang.String) as Lang.Number {
        if (ref.equals(_ref)) { return _count; }
        // abgeschlossene Tracks: Anzahl aus Chunks bestimmen
        var n = 0; var i = 0;
        while (true) {
            var chunk = Storage.getValue(ref + "_" + i.toString());
            if (chunk == null) { break; }
            n += (chunk as Lang.Array).size() / 4; i += 1;
        }
        return n;
    }

    // Chunks eines fertig hochgeladenen Tracks loeschen
    function purge(ref as Lang.String) as Void {
        var i = 0;
        while (Storage.getValue(ref + "_" + i.toString()) != null) {
            Storage.deleteValue(ref + "_" + i.toString());
            i += 1;
        }
    }

    // ---- Geometrie ----------------------------------------------------------

    function _haversine(lat1 as Lang.Double, lon1 as Lang.Double,
                        lat2 as Lang.Double, lon2 as Lang.Double) as Lang.Float {
        var r = 6371000.0;
        var p1 = Math.toRadians(lat1); var p2 = Math.toRadians(lat2);
        var dp = Math.toRadians(lat2 - lat1);
        var dl = Math.toRadians(lon2 - lon1);
        var a = Math.sin(dp / 2) * Math.sin(dp / 2)
              + Math.cos(p1) * Math.cos(p2) * Math.sin(dl / 2) * Math.sin(dl / 2);
        return (2.0 * r * Math.asin(Math.sqrt(a))).toFloat();
    }
}
