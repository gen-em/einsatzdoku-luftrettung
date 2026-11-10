// NAdoku — Upload gemaess JSON-Vertrag v1.0.
// Sendet Einsaetze und Ruhe-Segmente inkrementell (max. 500 Punkte/Request),
// merkt sich pro Track die bestaetigte next_seq und raeumt nach final+komplett auf.
//
// VERSCHLOSSEN. Spur, Phasenkoordinaten und Reanimation gehen nur als
// Geraetehuelle hinaus (Huelle.mc): je Anfrage ein Spurstueck (`track` mit
// `huelle`) und bei einem Einsatz die Einsatzhuelle (`ort_huelle`). Ohne
// Kontoschluessel aus der Kopplung sendet die Uhr nicht — der Server nimmt
// Klartext ohnehin nicht an, und eine Uhr, die es trotzdem versuchte,
// bekaeme fuer jedes Paket `400 klartext` und parkte ihren ganzen Bestand.
using Toybox.Communications;
using Toybox.Application.Properties;
using Toybox.Application.Storage;
using Toybox.Lang;

// Traeger fuer den Web-Antwort-Rueckruf (method() gibt es nur auf Objekten).
// Die Signatur muss exakt der entsprechen, die Communications fuer den
// Rueckruf einer Webanfrage erwartet. (Bewusst ohne den Namen des Aufrufs:
// Wer die Netzstellen zaehlt, soll sechs finden, nicht sieben.)
class UploaderCb {
    function initialize() {}
    function onResponse(code as Lang.Number,
                        data as Null or Lang.Dictionary or Lang.String
                                or Toybox.PersistedContent.Iterator) as Void {
        Uploader.onResponse(code, data);
    }
}

module Uploader {

    var _busy as Lang.Boolean = false;
    var lastError as Lang.String or Null = null;

    /* IST DAS GERAET BEIM SERVER ABGEMELDET?
     *
     * 401 und 403 sagen nichts ueber das Paket, sondern ueber das GERAET: Es
     * wurde im Web geloescht, sein Schluessel passt nicht mehr, oder es steht
     * auf inaktiv. Dann kommt KEIN Paket mehr durch, gleich welches -- und
     * Pakete einzeln zu parken waere falsch, weil mit ihnen nichts verkehrt
     * ist. Stattdessen haelt das Senden an, bis jemand neu koppelt.
     *
     * Der Zustand liegt im Speicher und nicht im Storage: Nach einem Neustart
     * darf die Uhr es ruhig noch einmal versuchen. Sie erfaehrt binnen einer
     * Anfrage wieder, woran sie ist, und ein falsch stehen gebliebenes
     * "abgemeldet" waere teurer als ein Versuch zuviel. */
    var abgemeldet as Lang.Boolean = false;

    /* IST DAS GERAET GESPERRT? (403)
     *
     * Etwas anderes als abgemeldet: Das Geraet ist bekannt, aber im Web
     * deaktiviert, oder das Konto darf gerade nicht (gesperrt, wartet auf
     * Freigabe, Adresse unbestaetigt). Neu koppeln hilft dann nicht -- der
     * Weg heraus ist das Web. Beides in einen Zweig zu legen hiesse, einen Rat
     * zu geben, der im Kreis fuehrt. Der Wert ist der Grund:
     * "geraet", "gesperrt", "wartet", "unbestaetigt" oder "unbekannt" (wenn
     * der Koerper der Antwort nicht durchkommt). Im Speicher wie `abgemeldet`. */
    var gesperrt as Lang.String or Null = null;

    /* NICHT VOR DIESEM ZEITPUNKT (Epoche in Sekunden) -- nach 429, 503 und
     * 507. Ohne ihn klopfte jeder Anlass an, und die Sync-Seite tut es im
     * Zwei-Sekunden-Takt. Im Speicher: Nach einem Neustart darf es einen
     * Versuch frueher geben. `_wartegrund` ist :bremse oder :voll. */
    var _nichtVor as Lang.Number or Null = null;
    var _wartegrund as Lang.Symbol or Null = null;

    /* EIN VERSUCH TROTZ DER MARKE. Die Marke liegt nur im Speicher, und eine
     * angenommene Anfrage ist das Einzige, was sie widerlegt -- ohne diesen
     * Versuch kaeme sie nie zustande: Wer das Geraet im Web wieder
     * aktiviert, muesste die App neu starten. Gesetzt vom Oeffnen der
     * Sync-Seite und vom Dienstende, verbraucht vom naechsten Lauf. */
    var _einmalTrotz as Lang.Boolean = false;

    var _cb as UploaderCb or Null = null;
    var _inflight as Lang.Dictionary or Null = null;  // Kontext der laufenden Anfrage

    // Von ueberall aufrufbar: arbeitet die Warteschlange sequenziell ab.
    // Alles beim Server bestaetigt? (Nach Dienstende liegt alles Unbestaetigte
    // in den Pending-Listen; vollstaendig bestaetigte Eintraege werden entfernt.)
    function allSynced() as Lang.Boolean {
        return _offeneOhneGeparkte(Model.pendingMissions)
            && _offeneOhneGeparkte(Model.pendingRest);
    }

    /* GEPARKTE ZAEHLEN HIER NICHT MIT -- sonst kaeme die Rueckfrage
     * "Sync unvollstaendig, trotzdem beenden?" nach einem abgewiesenen Paket
     * bei JEDEM Dienstende wieder, fuer immer, ohne dass irgendetwas daran
     * noch zu tun waere. */
    function _offeneOhneGeparkte(liste as Lang.Array<Lang.Dictionary>) as Lang.Boolean {
        for (var i = 0; i < liste.size(); i++) {
            if (!istGeparkt(liste[i]["ref"] as Lang.String)) { return false; }
        }
        return true;
    }

    /* IST DIESES PAKET DAUERHAFT ABGEWIESEN?
     *
     * Die Marke haelt im Storage, weil ein Neustart die Lage nicht aendert:
     * Was der Server als unbrauchbar zurueckgewiesen hat, bleibt unbrauchbar.
     * Sie wird nur beim Verwerfen des Pakets geloescht -- und dann mit der
     * Spur zusammen, nie fuer sich. */
    function istGeparkt(ref as Lang.String) as Lang.Boolean {
        return Storage.getValue("bad_" + ref) != null;
    }

    /* DIE FRIST DER GEPARKTEN (wie auf dem Handy).
     *
     * Ohne Frist laege ein geparktes Paket, bis jemand es verwirft -- unter
     * Umstaenden fuer immer, mit seiner Spur. Eine Spur, die niemand mehr
     * nachreichen wird, ist kein Beleg mehr, sondern ein Ortsdatensatz auf
     * einem Geraet, das verlorengehen kann (Lage H im Bedrohungsmodell).
     * Deshalb traegt die Marke den Zeitpunkt des Parkens, und nach
     * Const.PARK_FRIST_S raeumt jeder Sendelauf das Paket, bevor er sendet.
     *
     * ALTE MARKEN (ein blosses `true`, ohne Zeitpunkt) bekommen beim ersten Lauf den Zeitpunkt JETZT --
     * wann sie gesetzt wurden, weiss die Uhr nicht, und lieber dreissig Tage
     * zu spaet raeumen als ein Paket, das gestern abgewiesen wurde, sofort. */
    function parkFristPruefen() as Void {
        var jetzt = Util.epochNow();
        var weg = [] as Lang.Array<Lang.String>;
        var listen = [Model.pendingMissions, Model.pendingRest];
        for (var l = 0; l < 2; l++) {
            var liste = listen[l] as Lang.Array<Lang.Dictionary>;
            for (var i = 0; i < liste.size(); i++) {
                var ref = liste[i]["ref"] as Lang.String;
                var v = Storage.getValue("bad_" + ref);
                if (v == null) { continue; }
                if (!(v instanceof Lang.Number)) {
                    Storage.setValue("bad_" + ref, jetzt);
                } else if (jetzt - (v as Lang.Number) > Const.PARK_FRIST_S) {
                    weg.add(ref);
                }
            }
        }
        for (var k = 0; k < weg.size(); k++) { verwerfen(weg[k]); }
    }

    /* DIE FRIST DER ABMELDUNG.
     *
     * `abgemeldet` liegt im Speicher (siehe oben). Ohne Frist hielte eine
     * verlorene, im Web geloeschte Uhr ihren Klartextpuffer -- Spur, Phasen,
     * Reanimation -- unbegrenzt, waehrend geparkte Pakete nach dreissig
     * Tagen gehen (Lage H im Bedrohungsmodell). Deshalb merkt sich die Uhr
     * im Storage, WANN sie zum
     * ersten Mal 401 oder 403 bekam; das uebersteht Neustarts, an denen
     * `abgemeldet` vergessen wird. Eine angenommene Anfrage und jedes
     * Koppeln oder Trennen loeschen den Vermerk. Nach Const.PARK_FRIST_S
     * verwirft der naechste Sendelauf ALLE wartenden Pakete und den Vermerk
     * mit -- der naechste Dienst bekommt bei der naechsten Abmeldung eine
     * neue Frist, statt sofort mitzugehen.
     *
     * DER PREIS: Ein Diensttag, der dreissig Tage nach der
     * Abmeldung noch nicht gesendet ist, geht auf der Uhr verloren. Wer die
     * Uhr in der Frist neu koppelt, verliert nichts.
     *
     * WAS ES NICHT ABDECKT: Eine Uhr, die nie wieder ein Telefon erreicht,
     * erfaehrt nie, dass sie abgemeldet ist -- die Frist beginnt dann nicht. */
    const ABLAGE_ABGEMELDET = "abgem_seit";

    function abmeldungVermerken() as Void {
        if (!(Storage.getValue(ABLAGE_ABGEMELDET) instanceof Lang.Number)) {
            Storage.setValue(ABLAGE_ABGEMELDET, Util.epochNow());
        }
    }

    function abmeldungVergessen() as Void {
        Storage.deleteValue(ABLAGE_ABGEMELDET);
    }

    /* Bis wann darf die Uhr die Pakete noch halten? null = keine Frist laeuft.
     * Fuer die Sync-Seite. */
    function abmeldeFristBis() as Lang.Number or Null {
        var v = Storage.getValue(ABLAGE_ABGEMELDET);
        if (!(v instanceof Lang.Number)) { return null; }
        return (v as Lang.Number) + Const.PARK_FRIST_S;
    }

    function abmeldeFristPruefen() as Void {
        var bis = abmeldeFristBis();
        if (bis == null || Util.epochNow() <= bis) { return; }
        var refs = [] as Lang.Array<Lang.String>;
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            refs.add(Model.pendingMissions[i]["ref"] as Lang.String);
        }
        for (var j = 0; j < Model.pendingRest.size(); j++) {
            refs.add(Model.pendingRest[j]["ref"] as Lang.String);
        }
        for (var k = 0; k < refs.size(); k++) { verwerfen(refs[k]); }
        abmeldungVergessen();
    }

    /* DIE BINDUNG DES RUECKSTANDS.
     *
     * Ist die Uhr abgemeldet oder gesperrt, darf sie trotz Rueckstand neu
     * koppeln (Pair.start) -- sonst ginge weder Senden noch Trennen. Der
     * Rueckstand bleibt dann liegen, gebunden an den Kontoschluessel der
     * bisherigen Kopplung: Bringt die neue Kopplung DENSELBEN Schluessel, ist
     * es dasselbe Konto -- ein Konto hat genau ein Schluesselpaar, und es wird
     * nie ersetzt --, und er geht hinaus. Bringt sie einen anderen, geht er
     * NICHT hinaus.
     *
     * WARUM. Der Puffer liegt im Klartext und wird erst beim Senden
     * verschlossen. An ein anderes Konto gesendet, waere er mit dessen
     * Schluessel verschlossen und dort lesbar.
     *
     * Die Ablage haelt den alten Schluessel ("k", null = die alte Kopplung
     * kannte keinen; dann gilt jeder neue als anders), die Referenzen der
     * gebundenen Pakete ("refs") und das Ende der Frist ("bis"): Die Frist der
     * Abmeldung laeuft fuer sie weiter, auch nach der neuen Kopplung. */
    const ABLAGE_BINDUNG = "rueck_bind";

    /* Anfang und Groesse des zuletzt gesendeten, noch unquittierten
     * Stuecks, je Paket: "stk_" + ref -> { "s", "n" } (F-KA-07, `_send`). */
    const ABLAGE_STUECK = "stk_";

    function gebunden() as Lang.Boolean {
        return Storage.getValue(ABLAGE_BINDUNG) instanceof Lang.Dictionary;
    }

    /* Den jetzigen Rueckstand an den jetzigen Schluessel binden. Vor dem
     * Loeschen des Schluessels zu rufen (Pair.lokalTrennen).
     *
     * AUCH DIE LAUFENDEN PAKETE (F-KA-05). Der laufende Einsatz und das
     * laufende Ruhestueck stehen in keiner Liste; bis Uhr 4.3.3 band die
     * Bindung sie nicht, und `_findJob()` schickte sie ohne Pruefung. Im
     * Dienst wird zwar nicht mehr neu gekoppelt (Pair.start), aber wer die
     * Zugangsdaten auf anderem Weg verliert, soll den laufenden Dienst nicht
     * an die naechste Kopplung verschenken. Ihre Referenz bleibt dieselbe,
     * wenn sie in die Liste wandern -- die Bindung haelt also weiter. */
    function binden() as Void {
        var refs = [] as Lang.Array<Lang.String>;
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            refs.add(Model.pendingMissions[i]["ref"] as Lang.String);
        }
        for (var j = 0; j < Model.pendingRest.size(); j++) {
            refs.add(Model.pendingRest[j]["ref"] as Lang.String);
        }
        var lauf = laufendeRefs();
        for (var l = 0; l < lauf.size(); l++) { refs.add(lauf[l]); }
        var bis = abmeldeFristBis();
        if (bis == null) { bis = Util.epochNow() + Const.PARK_FRIST_S; }
        var k = Storage.getValue(Huelle.ABLAGE_SCHLUESSEL);
        Storage.setValue(ABLAGE_BINDUNG, {
            "k" => (k instanceof Lang.String) ? k : null,
            "refs" => refs, "bis" => bis
        } as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
    }

    /* Die Referenzen des laufenden Einsatzes und Ruhestuecks, soweit es sie
     * gibt. */
    function laufendeRefs() as Lang.Array<Lang.String> {
        var refs = [] as Lang.Array<Lang.String>;
        var mis = Model.mission;            // lokal: Null-Pruefung greift nur so
        if (mis != null) { refs.add(mis["ref"] as Lang.String); }
        var rest = Model.restSegment;
        if (rest != null) { refs.add(rest["ref"] as Lang.String); }
        return refs;
    }

    /* Hat ein laufendes Paket etwas, das noch nicht beim Server ist? */
    function laufendOffen() as Lang.Boolean {
        var lauf = laufendeRefs();
        for (var i = 0; i < lauf.size(); i++) {
            if (hasWork(lauf[i])) { return true; }
        }
        return false;
    }

    function _bindung() as Lang.Dictionary or Null {
        var b = Storage.getValue(ABLAGE_BINDUNG);
        return (b instanceof Lang.Dictionary) ? b : null;
    }

    /* Gehoert dieses Paket einem ANDEREN Konto als der jetzigen Kopplung? */
    function istFremd(ref as Lang.String) as Lang.Boolean {
        var b = _bindung();
        if (b == null || !_bindungAnders(b)) { return false; }
        var refs = b["refs"] as Lang.Array<Lang.String>;
        for (var i = 0; i < refs.size(); i++) {
            if (ref.equals(refs[i])) { return true; }
        }
        return false;
    }

    function _bindungAnders(b as Lang.Dictionary) as Lang.Boolean {
        var alt = b["k"];
        var jetzt = Storage.getValue(Huelle.ABLAGE_SCHLUESSEL);
        return !(alt instanceof Lang.String) || !(jetzt instanceof Lang.String)
            || !(alt as Lang.String).equals(jetzt as Lang.String);
    }

    /* Vor dem Senden: Bindung an dasselbe Konto loesen, abgelaufene
     * verwerfen. Gibt nichts zurueck -- `istFremd` sagt danach je Paket,
     * ob es liegen bleibt. */
    function bindungPruefen() as Void {
        var b = _bindung();
        if (b == null) { return; }
        var bis = b["bis"];
        if (bis instanceof Lang.Number && Util.epochNow() > (bis as Lang.Number)) {
            fremdeVerwerfen();
            return;
        }
        if (Huelle.hatSchluessel() && !_bindungAnders(b)) {
            Storage.deleteValue(ABLAGE_BINDUNG);
        }
    }

    /* Wie viele Pakete gehoeren einem anderen Konto? Fuer die Sync-Seite. */
    function fremdeZahl() as Lang.Number {
        var b = _bindung();
        if (b == null || !Huelle.hatSchluessel() || !_bindungAnders(b)) { return 0; }
        var n = 0;
        var refs = b["refs"] as Lang.Array<Lang.String>;
        for (var i = 0; i < refs.size(); i++) {
            if (_inListe(refs[i])) { n += 1; }
        }
        return n;
    }

    function _inListe(ref as Lang.String) as Lang.Boolean {
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            if (ref.equals(Model.pendingMissions[i]["ref"] as Lang.String)) { return true; }
        }
        for (var j = 0; j < Model.pendingRest.size(); j++) {
            if (ref.equals(Model.pendingRest[j]["ref"] as Lang.String)) { return true; }
        }
        return false;
    }

    /* Die gebundenen Pakete verwerfen und die Bindung loesen -- nach der
     * Rueckfrage auf der Sync-Seite oder nach Ablauf der Frist. */
    function fremdeVerwerfen() as Lang.Number {
        var b = _bindung();
        if (b == null) { return 0; }
        var refs = b["refs"] as Lang.Array<Lang.String>;
        var n = 0;
        /* EIN LAUFENDES PAKET WIRD NIE VERWORFEN -- es zeichnet noch auf.
         * Seine Bindung bleibt aber stehen, sonst ginge es, sobald es
         * abgeschlossen ist, an die jetzige Kopplung. */
        var bleiben = [] as Lang.Array<Lang.String>;
        for (var i = 0; i < refs.size(); i++) {
            if (_inListe(refs[i])) { verwerfen(refs[i]); n += 1; }
            else if (laufendeRefs().indexOf(refs[i]) >= 0) { bleiben.add(refs[i]); }
        }
        if (bleiben.size() == 0) {
            Storage.deleteValue(ABLAGE_BINDUNG);
        } else {
            b["refs"] = bleiben;
            Storage.setValue(ABLAGE_BINDUNG, b as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
        }
        return n;
    }

    /* WIE VIELE PAKETE STEHEN GEPARKT? Fuer die Anzeige (SyncView). */
    function geparkteZahl() as Lang.Number {
        var n = 0;
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            if (istGeparkt(Model.pendingMissions[i]["ref"] as Lang.String)) { n += 1; }
        }
        for (var j = 0; j < Model.pendingRest.size(); j++) {
            if (istGeparkt(Model.pendingRest[j]["ref"] as Lang.String)) { n += 1; }
        }
        return n;
    }

    /* Haelt eine Antwort des Servers das Senden an? (401 oder 403) */
    function angehalten() as Lang.Boolean {
        return abgemeldet || gesperrt != null;
    }

    /* Bis wann wartet die Uhr? null = sie wartet nicht. Fuer die Sync-Seite. */
    function wartetBis() as Lang.Number or Null {
        var nv = _nichtVor;
        if (nv == null || Util.epochNow() >= nv) { return null; }
        return nv;
    }

    function wartetWegenKontoVoll() as Lang.Boolean {
        return wartetBis() != null && _wartegrund == :voll;
    }

    /* Einmal senden, auch wenn die Marke steht (siehe `_einmalTrotz`). */
    function syncAllEinmalTrotz() as Void {
        if (angehalten()) { _einmalTrotz = true; }
        syncAll();
    }

    function syncAll() as Void {
        if (_busy) { return; }
        /* DIE FRIST VOR DEM RIEGEL: Auch eine Uhr, die gerade weiss, dass sie
         * abgemeldet ist, raeumt nach Ablauf. */
        abmeldeFristPruefen();
        /* ABGEMELDET ODER GESPERRT HEISST: gar nicht erst versuchen -- ausser
         * einmal auf ausdruecklichen Anlass. Jede Anfrage kostet Funk und
         * Akku und bekommt dieselbe Antwort, bis sich im Web etwas aendert. */
        if (angehalten()) {
            if (!_einmalTrotz) { return; }
            _einmalTrotz = false;
        }
        /* DIE WARTEZEIT gilt fuer jeden Anlass, auch den ausdruecklichen: Der
         * Server hat gesagt, wann er wieder will. */
        if (wartetBis() != null) { return; }
        /* OHNE SCHLUESSEL ODER OHNE VERSCHLUESSELUNG: ebenso nicht.
         * Die Sync-Seite sagt, was fehlt. */
        if (!Huelle.hatSchluessel() || !Huelle.bereit()) { return; }
        bindungPruefen();
        parkFristPruefen();
        _next();
    }

    function _next() as Void {
        // 1) abgeschlossene Einsaetze, 2) aktives + abgeschlossene Ruhe-Segmente,
        // 3) aktiver Einsatz (Teil-Upload) — jeweils erster mit offenen Punkten
        var job = _findJob();
        if (job == null) { _busy = false; return; }
        _busy = true;
        _send(job);
    }

    // Hat dieses Paket noch echte Arbeit? (offene Punkte oder unbestaetigte
    // Metadaten) — Grundlage fuer die Backlog-Anzeige.
    function hasWork(ref as Lang.String) as Lang.Boolean {
        return _openPoints(ref) > 0 || !_isAcked(ref);
    }

    function _findJob() as Lang.Dictionary or Null {
        /* GEPARKTE WERDEN UEBERSPRUNGEN, NICHT ENTFERNT.
         *
         * Sonst stuende ein dauerhaft abgewiesenes Paket vorn in der
         * Schlange und bliebe dort: Es wuerde endlos wiederholt, und alles
         * dahinter kaeme nie an. Ueberspringen loest die Blockade, ohne etwas
         * wegzuwerfen -- die Spur bleibt, bis jemand sie ausdruecklich
         * verwirft. */
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            var m = Model.pendingMissions[i];
            var mref = m["ref"] as Lang.String;
            if (istGeparkt(mref) || istFremd(mref)) { continue; }
            if (_openPoints(mref) > 0 || !_isAcked(mref)) {
                return { "kind" => "mission", "data" => m };
            }
        }
        for (var i = 0; i < Model.pendingRest.size(); i++) {
            var r = Model.pendingRest[i];
            var rref = r["ref"] as Lang.String;
            if (istGeparkt(rref) || istFremd(rref)) { continue; }
            if (_openPoints(rref) > 0 || !_isAcked(rref)) {
                return { "kind" => "rest_segment", "data" => r };
            }
        }
        /* Die laufenden Pakete gehen durch dieselbe Pruefung wie die in der
         * Liste: geparkt oder fremd -> liegen lassen (F-KA-05; bis Uhr 4.3.3
         * gingen sie ohne `istFremd` hinaus). */
        var rest = Model.restSegment;        // lokal: Null-Pruefung greift nur so
        if (rest != null) {
            var lr = rest["ref"] as Lang.String;
            if (!istGeparkt(lr) && !istFremd(lr) && _openPoints(lr) > 0) {
                return { "kind" => "rest_segment", "data" => rest };
            }
        }
        var mis = Model.mission;
        if (mis != null) {
            var lm = mis["ref"] as Lang.String;
            if (!istGeparkt(lm) && !istFremd(lm) && _openPoints(lm) > 0) {
                return { "kind" => "mission", "data" => mis };
            }
        }
        return null;
    }

    function _send(job as Lang.Dictionary) as Void {
        var url = _serverUrl();
        if (url.length() == 0) {                 // Einstellungen noch leer
            lastError = "Keine Server-URL";
            _busy = false;
            return;
        }
        var cred = credentials();
        if (cred == null) {
            lastError = "Nicht gekoppelt";
            _busy = false;
            return;
        }
        // Kein Schluessel: nichts hinaus. Die Sync-Seite sagt es.
        if (!Huelle.hatSchluessel()) { _busy = false; return; }
        var d = job["data"] as Lang.Dictionary;
        var ref = d["ref"] as Lang.String;
        var seqFrom = _ackedSeq(ref);
        var n = _openPoints(ref);
        if (n > Const.UPLOAD_CHUNK_POINTS) { n = Const.UPLOAD_CHUNK_POINTS; }
        /* EIN UNBESTAETIGTES STUECK GEHT GENAU SO NOCH EINMAL (F-KA-07).
         *
         * Kam auf `0:120` keine Antwort, kann der Server es trotzdem
         * abgelegt haben. Wuchs das Stueck bis zum naechsten Versuch auf
         * `0:300`, sah der Server dieselbe Stelle mit anderem Inhalt, legte
         * nichts ab -- und quittierte bis Uhr 4.3.3 trotzdem 300: Die Punkte
         * 120 bis 299 lagen nirgends, und bei einem abgeschlossenen Einsatz
         * loeschte die Uhr sie. Deshalb merkt sie sich Anfang und Groesse
         * des gesendeten Stuecks bis zur Quittung und wiederholt es
         * identisch. Danach waechst es wieder. */
        var stk = Storage.getValue(ABLAGE_STUECK + ref);
        if (stk instanceof Lang.Dictionary) {
            var sn = stk["n"];
            if (seqFrom == stk["s"] && sn instanceof Lang.Number && sn <= n) {
                n = sn;
            } else {
                Storage.deleteValue(ABLAGE_STUECK + ref);   // veraltet
            }
        }

        var flat = Track.readPoints(ref, seqFrom, n);
        var istEinsatz = "mission".equals(job["kind"] as Lang.String);

        var body = {
            "kind" => job["kind"], "client_ref" => ref,
            "day" => (d["day"] != null ? d["day"] : Model.day),
            "started_at" => d["startedAt"], "ended_at" => d["endedAt"],
            "final" => d["final"] == true
        };

        /* VERSCHLIESSEN. Scheitert es, geht nichts hinaus: Eine
         * Anfrage ohne Huelle quittierte der Server, und die Uhr raeumte
         * Punkte, die nie angekommen sind. Ohne Punkte faellt `track` weg --
         * der Server antwortet dann mit seiner eigenen Marke (Vertrag 3.1). */
        try {
            if (flat.size() > 0) {
                body["track"] = Huelle.spurstueck(istEinsatz ? "spur" : "ruhe", ref, seqFrom, flat);
            }
            if (istEinsatz) {
                body["ort_huelle"] = Huelle.einsatz(ref, d);
            }
        } catch (e) {
            lastError = "Verschlüsseln gescheitert";
            _busy = false;
            return;
        }

        // Dienstkennung (JSON-Vertrag 1.3). Sie stammt aus DEM PAKET, nicht aus
        // Model.dayRef: Ein Einsatz aus einem laengst beendeten Dienst kann noch
        // in der Warteschlange liegen, waehrend bereits der naechste laeuft —
        // mit der aktuellen Kennung landete er an dessen Diensttag.
        //
        // NUR MITSCHICKEN, WENN SIE DA IST. Aeltere Pakete fuehren
        // keine; fuer sie greift die Rueckfallebene ueber (Konto,
        // Datum), die der Server dauerhaft behaelt. Ein leeres Feld waere
        // dasselbe in umstaendlich.
        if (d["dref"] != null) { body["day_ref"] = d["dref"]; }

        if (istEinsatz) {
            body["distance_m"] = (d["dist"] != null) ? d["dist"] : Track.distanceM.toNumber();
            body["ascent_m"]   = (d["asc"]  != null) ? d["asc"]  : Track.ascentM.toNumber();
            /* Die Phasen tragen nur Nummer und Zeit; die Koordinate steht
             * in der Einsatzhuelle, ebenso die Reanimation (Vertrag 3). */
            var phases = [];
            var raw = d["phases"] as Lang.Array<Lang.Array>;
            for (var i = 0; i < raw.size(); i++) {
                var p = raw[i];
                phases.add({ "phase" => p[0], "at" => p[1] });
            }
            body["phases"] = phases;
        }

        var opts = {
            :method => Communications.HTTP_REQUEST_METHOD_POST,
            :headers => {
                "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON,
                "X-Device-Id" => cred["d"],
                "X-Api-Key" => cred["k"]
            },
            :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
        };

        // Kontext der laufenden Anfrage merken (Uploads laufen sequenziell,
        // daher genuegt eine einzelne Variable). "seq" und "n" sind die
        // Grenze, die eine Quittung nicht ueberschreiten darf.
        var gesendet = flat.size() / 4;
        _inflight = { "ref" => ref, "kind" => job["kind"],
                      "final" => d["final"] == true,
                      "seq" => seqFrom, "n" => gesendet };
        if (gesendet > 0) {
            Storage.setValue(ABLAGE_STUECK + ref, { "s" => seqFrom, "n" => gesendet }
                             as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
        }

        // Lokal statt ueber _cb: Die Typpruefung verfolgt eine Null-Pruefung
        // nur ueber lokale Variablen. Anlegen und Merken in einem Zug, sonst
        // entsteht eine Pruefung, die der Compiler als unerreichbar meldet.
        var cb = _cb;
        if (cb == null) { cb = new UploaderCb(); _cb = cb; }
        // Cast wie in Model.save(); hier ist der PolyType
        // Dictionary<Object, Object>.
        Communications.makeWebRequest(url,
            body as Lang.Dictionary<Lang.Object, Lang.Object>,
            opts, cb.method(:onResponse));
    }

    /* Zugangsdaten aus der Kopplung (Storage "cred"). null = nicht gekoppelt.
     *
     * NUR DIESER WEG. Geraete-ID und API-Schluessel als App-Einstellungen
     * waeren in Garmin Connect sichtbar und von Hand eintragbar, und sie
     * truegen Zugangsdaten durch eine fremde App. */
    function credentials() as Lang.Dictionary or Null {
        var c = Storage.getValue("cred");
        if (c instanceof Lang.Dictionary && c["d"] != null && c["k"] != null) {
            return { "d" => c["d"], "k" => c["k"] };
        }
        return null;
    }

    function hasCredentials() as Lang.Boolean {
        return credentials() != null;
    }

    // Ist in den App-Einstellungen (Garmin Connect) eine Server-Adresse
    // hinterlegt? Ohne sie kann weder gekoppelt noch gesendet werden.
    function hasServer() as Lang.Boolean {
        return _serverUrl().length() > 0;
    }

    // Server-Basis (https://domain/…/) fuer beliebige Endpunkte (pair.php)
    function serverBase() as Lang.String {
        var u = _serverUrl();
        if (u.length() == 0) { return ""; }
        // ".../xyz.php" -> ".../"
        var cut = u.length();
        for (var i = u.length() - 1; i >= 8; i--) {
            if ("/".equals(u.substring(i, i + 1))) { cut = i + 1; break; }
        }
        return u.substring(0, cut) as Lang.String;
    }

    // Toleranz bei der Server-URL: die blosse Domain genuegt in den
    // Einstellungen (Vorgabe "nadoku.gen-em.org") — Schema und /ingest.php
    // werden ergaenzt. Eine vollstaendige URL bleibt ebenso gueltig; sie
    // endet dann auf ".php" und wird nicht angefasst.
    //
    // NUR HTTPS (wie auf dem Handy). Bliebe ein eingetragenes "http://"
    // stehen, gingen Geraeteschluessel und Pakete ab dem Telefon
    // unverschluesselt. Deshalb wird es auf "https://" gehoben, ohne
    // Ausnahmeschalter; die Prueffassung spricht ohnehin TLS (8443).
    function _serverUrl() as Lang.String {
        var u = Properties.getValue("serverUrl");
        if (!(u instanceof Lang.String) || u.length() == 0) { return ""; }
        if (u.find("http://") == 0) { u = "https://" + (u.substring(7, u.length()) as Lang.String); }
        else if (u.find("https://") != 0) { u = "https://" + u; }
        var len = u.length();
        var endsPhp = (len >= 4) && (u.substring(len - 4, len) as Lang.String).equals(".php");
        if (!endsPhp) {
            if (!(u.substring(len - 1, len) as Lang.String).equals("/")) { u = u + "/"; }
            u = u + "ingest.php";
        }
        return u;
    }

    function onResponse(code as Lang.Number,
                        data as Null or Lang.Dictionary or Lang.String
                                or Toybox.PersistedContent.Iterator) as Void {
        var ctx = _inflight;
        if (ctx == null) { _busy = false; return; }
        var ref = ctx["ref"] as Lang.String;
        if (code == 200 && data instanceof Lang.Dictionary && data["ok"] == true) {
            lastError = null;
            abgemeldet = false;        // eine angenommene Anfrage widerlegt es
            gesperrt = null;
            _nichtVor = null;
            abmeldungVergessen();
            /* HOECHSTENS, WAS GESENDET WURDE (F-KA-18). Die Uhr loescht,
             * was quittiert ist. Bis Uhr 4.3.3 nahm sie `next_seq`
             * ungeprueft -- eine Antwort mit einer zu grossen Zahl, etwa
             * von einer falschen Serveradresse in den Einstellungen, liess
             * sie Punkte loeschen, die nie angekommen waren. Eine Antwort
             * ohne Zahl quittiert nichts. */
            var roh = data["next_seq"];
            if (!(roh instanceof Lang.Number)) {
                lastError = "Antwort ungültig";
                _busy = false;
                return;
            }
            var nextSeq = roh as Lang.Number;
            var grenze = (ctx["seq"] as Lang.Number) + (ctx["n"] as Lang.Number);
            if (nextSeq > grenze) { nextSeq = grenze; }
            Storage.deleteValue(ABLAGE_STUECK + ref);
            _setAcked(ref, nextSeq, true);

            // final + alle Punkte bestaetigt -> lokal aufraeumen
            if (ctx["final"] == true && nextSeq >= Track.pointCount(ref)) {
                Track.purge(ref);
                Storage.deleteValue("ack_" + ref);    // Marken mit entsorgen
                Storage.deleteValue("meta_" + ref);
                /* UEBER DIE REFERENZ, NICHT UEBER DEN INDEX.
                 *
                 * Der Listenplatz, den `_findJob()` beim Absenden misst,
                 * kann veralten: Dazwischen liegt eine Netzanfrage, und in
                 * der Zeit darf die Liste schrumpfen. "Abgewiesene
                 * verwerfen" auf der Sync-Seite entfernt jedes geparkte
                 * Paket -- und geparkte stehen gerade VOR dem laufenden,
                 * weil `_findJob()` sie ueberspringt. Rueckte die Liste um
                 * eins, traefe das Entfernen ein ANDERES Paket: Es
                 * verschwaende, seine Spur bliebe als Waise im Speicher, und
                 * der Einsatz kaeme nie an. Laege der Platz danach hinter dem
                 * Ende, braeche die App im Rueckruf ab. Die Referenz kann nicht
                 * veralten; steht sie nicht (mehr) in der Liste -- das
                 * laufende Paket des Dienstes steht nie dort --, wird nichts
                 * entfernt. Dieselbe Bauart wie `verwerfen()`. */
                var liste = Model.pendingRest;
                if ("mission".equals(ctx["kind"] as Lang.String)) { liste = Model.pendingMissions; }
                var weg = false;
                for (var i = liste.size() - 1; i >= 0; i--) {
                    if (ref.equals(liste[i]["ref"] as Lang.String)) {
                        liste.remove(liste[i]);
                        weg = true;
                    }
                }
                if (weg) { Model.save(); }
            }
            _next();   // weitere offene Chunks/Jobs
        } else if (code == 401 || code == 403) {
            /* DAS GERAET IST ABGEMELDET ODER GESPERRT, NICHT DAS PAKET
             * ABGEWIESEN.
             *
             * 401: im Web geloescht oder der Schluessel passt nicht mehr.
             * 403: dort deaktiviert, oder das Konto darf nicht. In beiden
             * Faellen kommt kein Paket mehr durch -- deshalb wird auch keines
             * geparkt: Mit den Paketen ist nichts verkehrt, und sie werden
             * gebraucht, sobald sich die Lage aendert.
             *
             * Fiele das in denselben Zweig wie eine Stoerung, wuerde es
             * endlos wiederholt. */
            abweisungMerken(code, data);
            lastError = null;
            _busy = false;
        } else if (code == 400 && _dauerhaftAbgewiesen(data)) {
            /* DIESES EINE PAKET IST UNBRAUCHBAR (Vertrag: "nicht wiederholen,
             * lokal als fehlerhaft markieren").
             *
             * NUR MIT ERKENNBARER ANTWORT DES SERVERS: Ein blankes 400 ohne
             * Fehlerschluessel kann von jedem Zwischenstueck kommen -- einem
             * Reverse Proxy, einer Firewall, einer vertippten Adresse in den
             * Einstellungen. Ein gesundes Paket dafuer zu parken waere
             * schlimmer als ein Versuch zuviel; ohne Kennzeichen wird deshalb
             * weiter wiederholt. */
            // Mit dem Zeitpunkt des Parkens: daran haengt die Frist.
            Storage.setValue("bad_" + ref, Util.epochNow());
            lastError = null;
            _busy = false;
            _next();          // die Schlange laeuft weiter
        } else if (code == 429 || code == 503 || code == 507) {
            /* DER SERVER SAGT, WANN ER WIEDER WILL -- in einer Kopfzeile, die
             * die Uhr nicht sieht. Sie wartet deshalb eine feste Zeit
             * (Const), bevor ein Anlass wieder sendet. Nichts wird geparkt
             * oder verworfen. */
            var s = Const.WARTEN_503_S;
            _wartegrund = :bremse;
            if (code == 429) { s = Const.WARTEN_429_S; }
            else if (code == 507) { s = Const.WARTEN_507_S; _wartegrund = :voll; }
            _nichtVor = Util.epochNow() + s;
            lastError = null;
            _busy = false;
        } else {
            lastError = "Upload " + code.toString();
            _busy = false;   // spaeter erneut (naechster syncAll-Ausloeser)
        }
    }

    /* 401 oder 403 merken -- vom Upload und vom Nachholen des Schluessels
     * (Pair.onSchluessel), damit beide dieselbe Lage zeigen. */
    function abweisungMerken(code as Lang.Number, data as Lang.Object or Null) as Void {
        if (code == 401) {
            abgemeldet = true;
            gesperrt = null;
        } else {
            gesperrt = _sperrgrund(data);
            abgemeldet = false;
        }
        abmeldungVermerken();          // die Frist beginnt beim ersten Mal
    }

    /* Der Grund einer 403 aus dem Koerper (JSON-Vertrag 5). Ob Connect IQ
     * den Koerper einer 403 durchreicht, ist geraeteabhaengig; ohne ihn
     * heisst es "unbekannt". */
    function _sperrgrund(data as Lang.Object or Null) as Lang.String {
        if (!(data instanceof Lang.Dictionary)) { return "unbekannt"; }
        var e = data["error"];
        if ("device_disabled".equals(e)) { return "geraet"; }
        if ("konto".equals(e)) {
            var g = data["grund"];
            if ("gesperrt".equals(g) || "wartet".equals(g) || "unbestaetigt".equals(g)) {
                return g as Lang.String;
            }
        }
        return "unbekannt";
    }

    /* Traegt die Antwort den Fehlerschluessel des Servers? (JSON-Vertrag 5.)
     *
     * `ingest.php` antwortet auf eine unbrauchbare Nachricht mit
     * `{"error":"payload", ...}`. Ob Connect IQ den Rumpf einer
     * 400-Antwort ueberhaupt durchreicht, ist geraeteabhaengig und im
     * Simulator gemessen; kommt nichts an, bleibt es beim Wiederholen. */
    function _dauerhaftAbgewiesen(data as Null or Lang.Dictionary or Lang.String
                                          or Toybox.PersistedContent.Iterator) as Lang.Boolean {
        if (!(data instanceof Lang.Dictionary)) { return false; }
        var kennung = data["error"];
        if (!(kennung instanceof Lang.String)) { return false; }
        return "payload".equals(kennung as Lang.String);
    }

    /* EIN GEPARKTES PAKET ENDGUELTIG VERWERFEN.
     *
     * Die Reihenfolge ist wesentlich: erst die Spur, dann die Marken, dann
     * der Eintrag aus der Liste. Wer die Marke `bad_` vorher loeschte, haette
     * ein Paket, das sofort wieder gesendet wird; wer den Eintrag vorher
     * entfernte, liesse die Spur als Waise im Speicher zurueck -- rund
     * 100 kB, die nichts mehr freigibt. */
    function verwerfen(ref as Lang.String) as Void {
        Track.purge(ref);
        Storage.deleteValue("ack_" + ref);
        Storage.deleteValue("meta_" + ref);
        Storage.deleteValue("bad_" + ref);
        Storage.deleteValue(ABLAGE_STUECK + ref);
        for (var i = Model.pendingMissions.size() - 1; i >= 0; i--) {
            if (ref.equals(Model.pendingMissions[i]["ref"] as Lang.String)) {
                Model.pendingMissions.remove(Model.pendingMissions[i]);
            }
        }
        for (var j = Model.pendingRest.size() - 1; j >= 0; j--) {
            if (ref.equals(Model.pendingRest[j]["ref"] as Lang.String)) {
                Model.pendingRest.remove(Model.pendingRest[j]);
            }
        }
        Model.save();
    }

    /* Alle geparkten Pakete verwerfen -- der Weg von der Sync-Seite und vom
     * Trennen der Kopplung aus. Gibt zurueck, wie viele es waren. */
    function alleGeparktenVerwerfen() as Lang.Number {
        var refs = [] as Lang.Array<Lang.String>;
        for (var i = 0; i < Model.pendingMissions.size(); i++) {
            var mr = Model.pendingMissions[i]["ref"] as Lang.String;
            if (istGeparkt(mr)) { refs.add(mr); }
        }
        for (var j = 0; j < Model.pendingRest.size(); j++) {
            var rr = Model.pendingRest[j]["ref"] as Lang.String;
            if (istGeparkt(rr)) { refs.add(rr); }
        }
        for (var k = 0; k < refs.size(); k++) { verwerfen(refs[k]); }
        return refs.size();
    }

    // ---- Upload-Marken pro Track --------------------------------------------

    function _ackedSeq(ref as Lang.String) as Lang.Number {
        var v = Storage.getValue("ack_" + ref);
        return v != null ? v as Lang.Number : 0;
    }
    function _isAcked(ref as Lang.String) as Lang.Boolean {
        return true.equals(Storage.getValue("meta_" + ref));   // Metadaten mind. 1x bestaetigt
    }
    function _setAcked(ref as Lang.String, seq as Lang.Number, meta as Lang.Boolean) as Void {
        Storage.setValue("ack_" + ref, seq);
        if (meta) { Storage.setValue("meta_" + ref, true); }
    }
    function _openPoints(ref as Lang.String) as Lang.Number {
        var open = Track.pointCount(ref) - _ackedSeq(ref);
        return open > 0 ? open : 0;
    }
}
