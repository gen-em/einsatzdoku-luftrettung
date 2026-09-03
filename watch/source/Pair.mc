// NAdoku — Geraete-Kopplung: die Uhr ZEIGT einen Code
//
// Auf der Sync-Seite START halten (Venu 3s: Action halten oder Zurueck
// halten). Die Uhr holt sich bei pair.php eine Kopplungssitzung, zeigt den
// sechsstelligen Code gross an (PairView) und fragt im Takt nach, ob ihn
// jemand im Web eingetragen hat. Hat ein Konto ihn eingetragen, fragt die Uhr
// zurueck — "Mit ph***@… koppeln?" —, und erst dieses Ja legt das Geraet an.
// Voraussetzung: Server-Adresse in den App-Einstellungen (properties.xml);
// Vorgabe ist seit 3.0.0 die oeffentliche Installation (E-R49-8).
//
// WAS SICH MIT 3.0.0 GEDREHT HAT (S5, E-R49-1)
// Bis 2.0.0 erzeugte das Web den Code und die Traegerin tippte ihn auf der Uhr
// ein — fuenf, spaeter sechs Zeichen ueber einen TextPicker. Das setzte
// voraus, dass sie vorher am Rechner war, und die Texteingabe auf einem
// Uhrendisplay war der unangenehmste Weg der ganzen Anwendung. Jetzt zeigt die
// Uhr, und das Web nimmt entgegen. Der Kopf dieser Datei stand bis dahin auf
// "UP halten" und "5 Zeichen" — beides war schon vor S5 falsch (B-S5-05).
//
// DER CODE WEIST NICHTS AUS (E-S5-03). Er ist fuer den Menschen, der ihn
// abliest und eintippt; wer ihn ueber die Schulter sieht, kann an der Uhr
// nichts ausloesen. Was die Uhr ausweist, sind Kennung und Schluessel aus
// `start` — und die sind bis zum Ja SCHWEBEND: Der Server kennt sie,
// ingest.php weist sie aber mit 401 ab, weil es das Geraet noch nicht gibt.
//
// UND SIE LIEGEN BIS DAHIN NUR IM ARBEITSSPEICHER (E-S5-22). Erst
// `200 {"ok":true}` auf `bestaetigen ja` schreibt Storage "cred". Wer die App
// vorher verlaesst, hat keine halbe Kopplung auf der Uhr stehen; die Sitzung
// verfaellt serverseitig nach zehn Minuten. Das Kontolabel — die maskierte
// E-Mail — steht nur im Dialog und wird NIE gespeichert.
//
// ZWEI TORE, NICHT EINES (E-R49-5). Die Bestaetigungsseite im Web faengt das
// fremde Geraet im eigenen Konto ab; die Rueckfrage hier faengt das eigene
// Geraet im fremden Konto ab. Wer eines von beiden wegnimmt, laesst eine der
// beiden Richtungen offen.
using Toybox.Lang;
using Toybox.WatchUi;
using Toybox.Communications;
using Toybox.Application.Storage;
using Toybox.Application.Properties;
using Toybox.System;
using Toybox.Timer;

// Rueckruf-Traeger (method() existiert nur auf Objekten).
//
// VIER ANLIEGEN, VIER TRAEGER. Ein gemeinsamer Rueckruf muesste aus Code und
// Rumpf erraten, worauf er gerade antwortet — und `start`, `status` und
// `bestaetigen` liefern im Erfolgsfall alle drei eine 200 mit einem anderen
// Rumpf. Vier Klassen kosten vier Zeilen und keine Rateregel.
/* JEDE ANTWORT BRINGT IHRE SITZUNG MIT. Bis zur Gegenlesung waren die drei
 * Traeger wiederverwendete Singletons ohne Kennung, und die Rueckrufe
 * entschieden an Modulflaggen — die aber sagen "laeuft gerade IRGENDEINE
 * Sitzung", nicht "laeuft DIESE". Zwei Anfragen koennen hier erstmals im
 * Projekt gleichzeitig offen sein (`bestaetigen nein` wird bewusst nicht
 * abgewartet, E-S5-23, und daneben laeuft schon der naechste `start`).
 * Kommen sie vertauscht zurueck — Connect IQ sagt darueber nichts zu —, dann
 * beantwortet die Uhr die neue Sitzung mit dem Ergebnis der alten. Im
 * schlimmsten Fall schriebe ein `200 {"ok":true}` auf ein altes `nein` die
 * schwebenden Zugangsdaten der NEUEN Sitzung in Storage "cred": Die Uhr hielte
 * sich fuer gekoppelt, jeder Upload endete in 401, und ein Rueckstand
 * verhinderte sogar das Neukoppeln. Genau das schliesst E-S5-22 aus.
 *
 * Deshalb traegt jeder Rueckruf, worauf er antwortet. Fuer `start` ist das
 * eine Laufnummer (eine Kennung gibt es da noch nicht), fuer die anderen die
 * Geraetekennung der Sitzung — und `bestaetigen` zusaetzlich das Ja/Nein: Der
 * Server antwortet auf beides `200 {"ok":true}` (pair.php), ein Rueckruf ohne
 * dieses Wissen koennte sie nicht auseinanderhalten. */
class PairStartCb {
    var _lauf as Lang.Number;
    function initialize(lauf as Lang.Number) { _lauf = lauf; }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onStart(_lauf, code, data);
    }
}

class PairStatusCb {
    var _fuer as Lang.String;
    function initialize(fuer as Lang.String) { _fuer = fuer; }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onStatus(_fuer, code, data);
    }
}

class PairJaCb {
    var _fuer as Lang.String;
    var _ja as Lang.Boolean;
    function initialize(fuer as Lang.String, ja as Lang.Boolean) { _fuer = fuer; _ja = ja; }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onBestaetigt(_fuer, _ja, code, data);
    }
}

/* Traeger fuer den verzoegerten Ausgang. Warum es ihn braucht, steht bei
 * Pair.dialogWeggeklickt(). */
class PairSpaeterCb {
    function initialize() { }
    function fire() as Void { Pair.spaeterAblehnen(); }
}

class UnpairCb {
    function initialize() { }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onTrennen(code);
    }
}

/* Rueckfrage vor dem Trennen. Bewusst der vorhandene Baustein
 * WatchUi.Confirmation, wie beim Einsatzabschluss und beim Verlassen der App
 * (ClockView) — eine eigene Ansicht braucht es dafuer nicht. */
class TrennenDelegate extends WatchUi.ConfirmationDelegate {
    function initialize() { ConfirmationDelegate.initialize(); }
    function onResponse(response) as Lang.Boolean {
        if (response == WatchUi.CONFIRM_YES) { Pair.trennen(); }
        return true;
    }
}

module Pair {

    var status as Lang.String or Null = null;   // Anzeige auf der Sync-Seite
    /* Zweite Zeile fuer den Weg aus dem Fehler heraus.
     *
     * Die Meldungszeile wird mit drawText gezeichnet und NICHT umgebrochen —
     * was breiter ist als das Display, faellt weg, ohne dass man es merkt. In
     * der Hinweisschrift sind das rund 26 Zeichen. Eine Meldung wie "Zu viele
     * Geraete gekoppelt, erst eines im Web loeschen" waere damit genau um den
     * Teil gekuerzt, der sagt, was zu tun ist.
     *
     * Deshalb zwei kurze Zeilen statt einer langen: WAS ist los, und WAS hilft.
     * SyncView haengt beide als eigene Eintraege in seine Zeilenliste; deren
     * Hoehe geht in die Platzberechnung ein, der Block darueber weicht also
     * von selbst aus. */
    var statusHint as Lang.String or Null = null;
    // Art der Meldung, damit die Oberflaeche die Farbe waehlen kann, ohne den
    // Text auseinandernehmen zu muessen: :ok, :busy, :error
    var statusKind as Lang.Symbol = :busy;

    /* Wohin der Code im Web gehoert — die zweite Zeile unter dem Code.
     *
     * KOMMA STATT PFEIL, UND ZWAR GEMESSEN. Ob "→" in den Geraeteschriften
     * vorhanden ist, stand im Konzept als offener Punkt (Abschnitt 11).
     * Am 03.09.2026 im Simulator nachgesehen, fenix6pro, SDK 9.2.0, mit
     * "Einstellungen → Geräte" uebersetzt und fotografiert: Das Zeichen
     * erscheint als PLATZHALTER-RAUTE, nicht als Pfeil — der Fall aus
     * Uhr-Layout_Regeln 3.1, nur mit einer Textschrift statt einer
     * Ziffernschrift. Es gibt keine Warnung und keinen Fehler, nur ein Bild,
     * das niemand deuten kann.
     *
     * Das Komma sagt dasselbe und kann nicht fehlen. Die Weboberflaeche und
     * das Handbuch schreiben an dieser Stelle weiter "Einstellungen → Geräte"
     * — dort traegt die Schrift den Pfeil. Dass Uhr und Web hier
     * auseinanderlaufen, ist kein Versehen: Die Uhr kann es nicht anders. */
    const WEG_IM_WEB = "Im Web eingeben";

    // Laenge, die in der Hinweisschrift sicher aufs Display passt.
    const ZEILE_MAX = 26;

    function _kurz(t as Lang.String) as Lang.String {
        if (t.length() <= ZEILE_MAX) { return t; }
        // Lieber sichtbar gekuerzt als unsichtbar abgeschnitten.
        return t.substring(0, ZEILE_MAX - 1) + "…";
    }

    /* --- Die schwebende Sitzung ------------------------------------------
     *
     * ALLES HIER IST FLUECHTIG, und das ist die Zusage (E-S5-22). Keine dieser
     * Variablen wird gespeichert; ein App-Neustart laesst nichts zurueck.
     * Wer eine davon nach Storage schreibt, nimmt der Bauform ihre Aussage —
     * dann liegt auf der Uhr eine Kopplung, die es auf dem Server noch nicht
     * gibt. */
    var _code as Lang.String or Null = null;    // Anzeigecode, sechs Zeichen
    var _dev  as Lang.String or Null = null;    // kuenftige Geraetekennung
    var _key  as Lang.String or Null = null;    // kuenftiger Schluessel, Klartext
    var _endeMs as Lang.Number = 0;             // Systemzeit, zu der die Frist faellt

    /* Der Verbindungshinweis der Kopplungsansicht. Er steht dort UNTER dem
     * Code und ersetzt die Restzeit nicht: Ein Verbindungsfehler beendet die
     * Sitzung nicht (E-S5-25) — sie lebt auf dem Server weiter, und die
     * Abfrage laeuft bis zur Frist. */
    var netzHinweis as Lang.String or Null = null;

    /* Ist gerade ein `bestaetigen ja` unterwegs? Ohne diese Auskunft bliebe
     * die Kopplungsansicht nach dem Ja unveraendert stehen — mit dem Code und
     * der Aufforderung, ihn ins Web einzutragen, also genau der Handlung, die
     * die Traegerin soeben erledigt hat. Sie waere die einzige Stelle des
     * Weges ohne Rueckmeldung, und ausgerechnet die letzte. */
    var jaLaeuft as Lang.Boolean = false;

    var _pending as Lang.Boolean = false;       // laeuft gerade eine status-Abfrage?

    /* Laeuft gerade eine `start`-Anfrage? Ohne diese Sperre traegt ein zweiter
     * langer Druck waehrend "Hole Code…" eine zweite Sitzung ein und schiebt
     * eine ZWEITE Kopplungsansicht — die untere bliebe stehen, und `_viewOffen`
     * kennt nur einen Zustand, poppt also nur einmal. Auf der Venu liegt
     * SELECT_LONG sogar absichtlich auf zwei Tasten, der zweite Druck ist dort
     * einen Fehlgriff entfernt. */
    var _startLaeuft as Lang.Boolean = false;
    /* Laufnummer der juengsten `start`-Anfrage. Die Sperre oben verhindert den
     * zweiten Druck; diese Nummer faengt ab, was danach noch eintrudelt —
     * etwa die Antwort auf einen `start`, der beim Abbrechen unterwegs war. */
    var _lauf as Lang.Number = 0;

    /* Ist die Sync-Seite gerade auf dem Schirm? Zwischen dem Tastendruck und
     * dem Erscheinen der Kopplungsansicht liegt eine volle Funkrunde, und die
     * Traegerin kann in dieser Zeit weiterblaettern oder mit BACK heraus. Die
     * Ansicht draengte sich dann ueber eine Seite, die sie nicht aufgerufen
     * hat — im schlimmsten Fall ueber den Rea-Countdown, wo die Ereignistasten
     * tot waeren und BACK etwas anderes bedeutet. E-S5-24 sagt ausdruecklich
     * "ueber der Sync-Seite". */
    var _syncSichtbar as Lang.Boolean = false;

    /* Eine Warnung, die den unmittelbar folgenden Kopplungsweg ueberdauert.
     * `status`/`statusHint` taugen dafuer nicht — sie gehoeren dem laufenden
     * Schritt, und `sitzungStarten()` setzt sie im selben Rueckruf neu. */
    var _warnung as Lang.String or Null = null;
    var _letzteFrageMs as Lang.Number = 0;
    /* Darf abgefragt werden? Falsch, solange der Bestaetigungsdialog steht
     * oder die Sitzung beendet ist. Der Zeitgeber der Ansicht laeuft
     * unabhaengig davon weiter — er treibt die Restzeit. */
    var _abfragen as Lang.Boolean = false;
    var _viewOffen as Lang.Boolean = false;

    var _ucb as UnpairCb or Null = null;
    var _spaeterT as Timer.Timer or Null = null;
    var _spaeterCb as PairSpaeterCb or Null = null;

    /* Meldet die Sync-Seite beim Erscheinen und Verschwinden (B8).
     * Dass onHide auch feuert, wenn die Kopplungsansicht selbst darueber
     * geschoben wird, ist unschaedlich: onStart liest den Merker VOR dem
     * Schieben, und beim Zurueckkehren setzt onShow ihn wieder. */
    function seiteSichtbar(an as Lang.Boolean) as Void { _syncSichtbar = an; }

    /* Einstieg fuer „Gerät koppeln" (Sync-Seite, Auswahltaste halten).
     *
     * DER FALL IST DIE GETEILT GENUTZTE UHR (Backlog Nr. 14). Bis hierher
     * fuehrte der Weg direkt in die Kopplung. Schlug sie fehl — kein Telefon
     * in Reichweite, Geraetegrenze erreicht —, blieben die ALTEN Zugangsdaten
     * stehen und die Uhr dokumentierte stillschweigend weiter auf das
     * vorherige Konto. Niemand sah es ihr an, und die Person davor bekam
     * Einsaetze, die sie nicht gefahren ist.
     *
     * Die Reihenfolge ist deshalb ausdruecklich: abfragen -> trennen -> neu
     * koppeln. Scheitert das Koppeln danach, steht die Uhr SICHTBAR ohne
     * Kopplung da (die Sync-Seite sagt „Nicht eingerichtet") statt unsichtbar
     * mit der falschen.
     *
     * EIN RUECKSTAND VERHINDERT DAS TRENNEN. Abgeschlossene, noch nicht
     * gesendete Pakete gehoeren dem BISHERIGEN Konto; nach einer Neukopplung
     * wuerden sie an das neue gehen. Das waere kein Datenverlust, sondern
     * schlimmer — fremde Einsaetze in einem fremden Konto. Also erst senden.
     */
    function start() as Void {
        _warnung = null;              // ein neuer Anlauf erbt keine alte Vormerkung
        if (!Uploader.hasCredentials()) { sitzungStarten(); return; }

        var offen = Model.backlogCount();
        if (offen > 0) {
            status = "Erst " + offen.toString()
                   + (offen == 1 ? " Paket senden" : " Pakete senden");
            statusHint = "Sonst ans neue Konto";
            statusKind = :error;
            WatchUi.requestUpdate();
            return;
        }

        WatchUi.pushView(new WatchUi.Confirmation("Kopplung trennen und neu koppeln?"),
                         new TrennenDelegate(), WatchUi.SLIDE_LEFT);
    }

    /* Die Kopplung zurueckgeben. Der Server loescht das Geraet, damit es
     * keinen der MAX_GERAETE Plaetze mehr belegt — sonst liefe eine geteilte
     * Uhr genau in den Fehler „Zu viele Geräte", den sie vermeiden will. */
    function trennen() as Void {
        var cred = Uploader.credentials();
        var base = Uploader.serverBase();
        if (cred == null || base.length() == 0) { lokalTrennen(); sitzungStarten(); return; }

        status = "Trenne…";
        statusHint = null;
        statusKind = :busy;
        WatchUi.requestUpdate();

        var cb = _ucb;
        if (cb == null) { cb = new UnpairCb(); _ucb = cb; }
        Communications.makeWebRequest(
            base + "pair.php",
            { "aktion" => "trennen" },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => {
                    "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON,
                    "X-Device-Id"  => cred["d"],
                    "X-Api-Key"    => cred["k"]
                },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            cb.method(:onResponse));
    }

    /* Zugangsdaten auf der Uhr loeschen — beide Wege: die aus der Kopplung
     * (Storage) und die von Hand eingetragenen (Properties, Alt-Weg). */
    function lokalTrennen() as Void {
        Storage.deleteValue("cred");
        try {
            Properties.setValue("deviceId", "");
            Properties.setValue("apiKey", "");
        } catch (e) {
            // Alt-Weg nicht beschreibbar: Der Storage-Weg hat Vorrang, die
            // Kopplung ist damit trotzdem fort.
        }
        Uploader.lastError = null;
    }

    /* LOKAL WIRD IMMER GETRENNT, auch wenn der Server nicht geantwortet hat.
     *
     * Andernfalls waere eine Uhr ohne Telefon in Reichweite dauerhaft an ein
     * Konto gebunden, das sie nicht mehr benutzen soll — der Zustand, den
     * dieser ganze Weg beseitigt. Bleibt der Servereintrag dabei stehen,
     * belegt er einen Geraeteplatz; das steht in der zweiten Zeile, weil es
     * im Web mit einem Klick zu beheben ist.
     *
     * DANACH GEHT ES WEITER, auch nach einem Fehler: Wer „trennen und neu
     * koppeln" bestaetigt hat, will koppeln. Der belegte Platz laeuft
     * gegebenenfalls in ein 409 mit eigener, klarer Meldung — das ist die
     * bessere Auskunft als ein Abbruch an dieser Stelle. */
    function onTrennen(code as Lang.Number) as Void {
        lokalTrennen();
        /* HIER ZEIGEN GEHT NICHT: sitzungStarten() setzt status im selben
         * Rueckruf auf "Hole Code…", die Zeile waere nie zu sehen. Sie wird
         * deshalb vorgemerkt und am Ende des Weges ausgeliefert — dann steht
         * unter "Gekoppelt" der Hinweis auf den belegten Geraeteplatz. */
        if (code != 200) { _warnung = "Altes Gerät im Web löschen"; }
        sitzungStarten();
    }

    /* Was fuer ein Geraet koppelt sich hier? (Statistik, Web-Konzept)
     *
     * WOZU. Bis hierher wusste der Server nur, DASS ein Geraet gekoppelt ist,
     * nicht welches. Fuer die Frage "welche Uhren sollen wir kuenftig
     * unterstuetzen" gibt es keine brauchbare aeussere Quelle: Garmin
     * veroeffentlicht keine modellgenauen Zahlen, und der Connect-IQ-Store
     * schluesselt Installationen nicht nach Geraet auf. Wer es wissen will,
     * muss selbst zaehlen.
     *
     * WAS GESENDET WIRD. Die Teilenummer ist der Schluessel: Sie ist eindeutig
     * und laesst sich serverseitig gegen die Geraetedateien aufloesen (325
     * Teilenummern -> 173 Modelle), samt Geraeteart. Deshalb traegt die Uhr
     * KEINE Modelltabelle mit sich herum — auf einem Geraet mit 128 kB waere
     * das der falsche Platz dafuer.
     *
     * Die Art steht fest auf "uhr": Eine Connect-IQ-App laeuft nur auf einem
     * Garmin-Geraet. Unterscheiden kann diese App Uhr und Radcomputer nicht —
     * die Geraetedateien koennen es, deshalb loest der Server die Teilenummer
     * auf und seine Einstufung schlaegt diese Angabe (Web 12.9.0).
     *
     * SEIT 3.0.0 GEHT DER BLOCK AN `start` statt neben den Code (Vertrag
     * 1a.1). Er ist damit die einzige Auskunft, aus der die Bestaetigungsseite
     * im Web "Uhr · Venu 3S" bilden kann — ein Geraet, das nichts ueber sich
     * sagt, erscheint dort als "Gerät unbekannt". Eine Kopplung scheitert
     * daran trotzdem nie: Alle Felder sind fuer den Server freiwillig.
     *
     * WAS BEWUSST NICHT GESENDET WIRD: `uniqueIdentifier`. Das ist eine
     * dauerhafte, geraeteweite Kennung — fuer eine Stueckzahl-Statistik nicht
     * noetig, und in einer kleinen Gruppe ein Personenbezug mehr, als die
     * Frage rechtfertigt. Die Zuordnung leistet die device_id, die der Server
     * bei der Kopplung ohnehin vergibt. */
    function _geraeteInfo() as Lang.Dictionary {
        var d = System.getDeviceSettings();
        var ciq = null;
        var mv = d.monkeyVersion;
        if (mv != null && (mv as Lang.Array).size() >= 3) {
            var v = mv as Lang.Array<Lang.Number>;
            ciq = v[0].toString() + "." + v[1].toString() + "." + v[2].toString();
        }
        return {
            "art"   => "uhr",
            "teil"  => d.partNumber,          // z. B. "006-B4261-00"
            "br"    => d.screenWidth,
            "ho"    => d.screenHeight,
            "touch" => d.isTouchScreen,
            "fw"    => d.firmwareVersion,
            "ciq"   => ciq,
            "app"   => Const.APP_VERSION
        };
    }

    /* Schritt 1: eine Kopplungssitzung holen (`start`, ohne Kopfzeilen —
     * die Uhr hat noch keine). */
    function sitzungStarten() as Void {
        if (_startLaeuft) { return; }          // zweiter Druck waehrend "Hole Code…"
        var base = Uploader.serverBase();
        if (base.length() == 0) {
            /* "Adresse", nicht "Domain": So heisst die Einstellung in
             * settings.xml, im Handbuch, in SyncView und in StartView. Zwei
             * Woerter fuer dieselbe Sache koennen hier sogar gleichzeitig auf
             * dem Bildschirm stehen — die Sync-Seite zeigt in der Mitte
             * "Erst Server-Adresse setzen" und haette darunter die andere
             * Fassung gezeigt. */
            status = "Erst Server-Adresse setzen";
            statusHint = null;
            statusKind = :error;
            WatchUi.requestUpdate();
            return;
        }
        _sitzungVergessen();
        status = "Hole Code…";
        statusHint = null;
        statusKind = :busy;
        WatchUi.requestUpdate();
        _startLaeuft = true;
        _lauf += 1;
        var cb = new PairStartCb(_lauf);
        Communications.makeWebRequest(
            base + "pair.php",
            { "aktion" => "start", "geraet" => _geraeteInfo() },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => { "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            cb.method(:onResponse));
    }

    /* Antwort auf `start`. Bei Erfolg wird die Kopplungsansicht geschoben;
     * jeder Fehler bleibt auf der Sync-Seite und ist zweizeilig. */
    function onStart(lauf as Lang.Number, code as Lang.Number, data as Lang.Object or Null) as Void {
        // Eine ueberholte Antwort — die Sitzung, zu der sie gehoert, gibt es
        // nicht mehr. Sie darf weder anzeigen noch eine Ansicht schieben.
        if (lauf != _lauf) { return; }
        _startLaeuft = false;
        var dict = (data instanceof Lang.Dictionary) ? data : null;

        if (code == 200 && dict != null
            && dict["code"] instanceof Lang.String
            && dict["device_id"] instanceof Lang.String
            && dict["api_key"] instanceof Lang.String) {
            _code = dict["code"] as Lang.String;
            _dev  = dict["device_id"] as Lang.String;
            _key  = dict["api_key"] as Lang.String;
            // Frist ab JETZT, nicht ab der Serverzeit: Die Uhr hat keine
            // verlaessliche Uhrzeit des Servers, und die Laufzeit der Antwort
            // geht so zu unseren Lasten statt zu seinen.
            var frist = 600;
            if (dict["frist_s"] instanceof Lang.Number) {
                frist = dict["frist_s"] as Lang.Number;
            }
            _endeMs = System.getTimer() + frist * 1000;
            netzHinweis = null;
            _pending = false;
            /* Die erste Abfrage erst nach einem Takt: Zwischen dem Erscheinen
             * des Codes und dem Klick im Web liegen mindestens die Sekunden,
             * die jemand zum Ablesen und Tippen braucht. Eine Abfrage in
             * derselben Sekunde beantwortet sich garantiert mit "offen". */
            _letzteFrageMs = System.getTimer();
            _abfragen = true;

            /* Die Traegerin ist waehrend "Hole Code…" weitergeblaettert oder
             * mit BACK heraus. Dann draengt sich die Kopplungsansicht NICHT
             * ueber eine Seite, die sie nicht aufgerufen hat — im Zweifel
             * ueber den Rea-Countdown, wo die Ereignistasten tot waeren und
             * BACK etwas anderes bedeutet (E-S5-24: "ueber der Sync-Seite").
             * Die Sitzung wird stattdessen zurueckgegeben; `nein` ist in JEDEM
             * Zustand erlaubt, auch `offen`. Gespeichert wurde nichts
             * (E-S5-22), und die Meldung steht bereit, wenn die Sync-Seite das
             * naechste Mal aufgeschlagen wird. */
            if (!_syncSichtbar) { ablehnen("Abgebrochen"); return; }

            status = null;                 // die Sync-Seite tritt zurueck
            statusHint = null;
            statusKind = :busy;
            _viewOffen = true;
            WatchUi.pushView(new PairView(), new PairDelegate(), WatchUi.SLIDE_LEFT);
            return;
        }

        var fehler = (dict != null && dict["error"] instanceof Lang.String)
                     ? dict["error"] as Lang.String : null;
        var z1 = "Kopplung fehlgeschlagen (" + code.toString() + ")";
        var z2 = null;
        if (fehler != null && fehler.equals("zu_viele_versuche")) {
            // 429 aus dem Topf pair_start — Warten hilft, Wiederholen nicht.
            z1 = "Zu viele Versuche";
            z2 = "Später noch einmal";
        } else if (fehler != null && fehler.equals("zu_viele_sitzungen")) {
            /* 429 aus der Sitzungsobergrenze. Das ist KEIN Vorwurf an diese
             * Uhr — der Server haelt gerade so viele offene Sitzungen, wie er
             * hoechstens haelt. Der Zustand dauert Minuten, nicht Stunden
             * (die Frist ist zehn Minuten), deshalb dieselbe zweite Zeile. */
            z1 = "Server ausgelastet";
            z2 = "Später noch einmal";
        } else if (code < 0) {
            /* Negative Codes kommen nicht vom Server, sondern von der
             * Verbindung (kein Telefon in Reichweite, Bluetooth aus). Die
             * Zahl bleibt in der Meldung: Sie ist fuer eine Fehlersuche das
             * einzige Merkmal, und die Ursache liegt ausserhalb dieser App. */
            z1 = "Keine Verbindung";
            z2 = "Telefon in Reichweite?";
        } else {
            /* Unbekannter Fall. Der Zahlencode steht oben, weil er sicher
             * passt und fuer eine Fehlersuche taugt; die Servermeldung kommt
             * als zweite Zeile dazu, gekuerzt. Genau ueber diesen Weg erfaehrt
             * eine Uhr 2.0.0 am neuen Server, was zu tun ist: Sie sendet
             * `{"code": …}` ohne `aktion` und bekommt
             * `400 {"error":"aktion","meldung":"Uhr-App aktualisieren"}`
             * (E-S5-19). */
            if (dict != null && dict["meldung"] instanceof Lang.String) {
                z2 = _kurz(dict["meldung"] as Lang.String);
            }
        }
        // Die Vormerkung aus einem gescheiterten Trennen einloesen, wenn
        // dieser Ausgang keine eigene zweite Zeile mitbringt.
        if (z2 == null) { z2 = _warnung; }
        /* Ueber _sitzungBeenden, nicht ueber _sitzungVergessen: Steht bereits
         * eine Kopplungsansicht (weil ein frueherer Anlauf sie geschoben hat),
         * bliebe sie sonst mit einem toten Code stehen — die Meldung darunter
         * saehe niemand. */
        _sitzungBeenden(z1, z2, :error);
    }

    /* Schritt 2: nachfragen, ob jemand den Code eingetragen hat.
     *
     * HOECHSTENS ALLE PAIR_TAKT_MS UND NIE UEBERLAPPEND (E-S5-25). Der
     * Zeitgeber der Ansicht klopft alle zwei Sekunden an; hier wird
     * entschieden, ob daraus eine Anfrage wird. Zwei Bedingungen, beide
     * noetig: Die vorige Antwort muss da sein (sonst stapeln sich Anfragen,
     * wenn die Verbindung langsam ist), und der Takt muss verstrichen sein
     * (sonst waeren es 300 Anfragen je Sitzung statt 120). */
    function abfrageAnstossen() as Void {
        var dev = _dev;
        var key = _key;
        if (dev == null || key == null) { return; }

        /* Die Frist laeuft AUCH OHNE SERVER ab. Ein Verbindungsfehler beendet
         * die Sitzung nicht (E-S5-25) — aber "bis zur Frist" heisst eben auch:
         * an der Frist ist Schluss. Ohne diesen Zweig zeigte die Ansicht
         * "noch 0 s" und wartete auf ein 410, das nie kommt, wenn das Telefon
         * fort bleibt. */
        if (restSekunden() <= 0) {
            _sitzungBeenden("Code abgelaufen",
                            Input.lSelectHold() + ": neuer Code", :error);
            return;
        }

        if (!_abfragen || _pending) { return; }
        var base = Uploader.serverBase();
        if (base.length() == 0) { return; }

        var jetzt = System.getTimer();
        /* getTimer() zaehlt Millisekunden seit dem Einschalten und laeuft
         * irgendwann ueber. Faellt der Ueberlauf in diese zehn Minuten, waere
         * die Differenz stark negativ und es kaeme nie wieder eine Abfrage.
         * Einmal nachziehen kostet einen Takt und keinen Gedanken mehr. */
        if (jetzt < _letzteFrageMs) { _letzteFrageMs = jetzt; return; }
        if (jetzt - _letzteFrageMs < Const.PAIR_TAKT_MS) { return; }
        _letzteFrageMs = jetzt;
        _pending = true;

        var cb = new PairStatusCb(dev);
        Communications.makeWebRequest(
            base + "pair.php",
            { "aktion" => "status" },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => {
                    "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON,
                    "X-Device-Id"  => dev,
                    "X-Api-Key"    => key
                },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            cb.method(:onResponse));
    }

    function onStatus(fuer as Lang.String, code as Lang.Number,
                      data as Lang.Object or Null) as Void {
        /* GEHOERT DIE ANTWORT ZU DIESER SITZUNG? Die Pruefung steht VOR
         * `_pending = false` — sonst loeschte eine Fremdantwort die
         * Anfragebuchfuehrung der laufenden Sitzung mit, und die naechste
         * Abfrage liefe los, waehrend die vorige noch unterwegs ist. */
        var dev = _dev;
        if (dev == null || !fuer.equals(dev)) { return; }
        _pending = false;
        if (!_abfragen) { return; }

        var dict = (data instanceof Lang.Dictionary) ? data : null;
        var zustand = (dict != null && dict["zustand"] instanceof Lang.String)
                      ? dict["zustand"] as Lang.String : null;

        if (code == 200 && zustand != null) {
            netzHinweis = null;
            // Die Restzeit vom Server ist die massgebliche — sie zieht die
            // oertliche Rechnung bei jeder Antwort wieder gerade.
            if (dict != null && dict["rest_s"] instanceof Lang.Number) {
                _endeMs = System.getTimer() + (dict["rest_s"] as Lang.Number) * 1000;
            }
            if (zustand.equals("beansprucht")) {
                /* Ein Konto hat den Code eingetragen. Jetzt das zweite Tor:
                 * Die Abfrage haelt an, solange der Dialog steht — eine
                 * Antwort, die waehrenddessen eintraefe, haette keinen Platz,
                 * an dem sie etwas aendern duerfte. */
                _abfragen = false;
                var konto = (dict != null && dict["konto"] instanceof Lang.String)
                            ? dict["konto"] as Lang.String : "diesem Konto";
                WatchUi.pushView(new WatchUi.Confirmation("Mit " + konto + " koppeln?"),
                                 new KoppelnDelegate(), WatchUi.SLIDE_LEFT);
                return;
            }
            if (zustand.equals("gekoppelt")) {
                /* Die Antwort auf ein frueheres `bestaetigen ja` ist auf dem
                 * Rueckweg verlorengegangen — das Geraet gibt es bereits
                 * (E-S5-15). Ohne diesen Zweig haenge die Kopplung an einem
                 * einzigen Funkpaket: Der Server haette ein Geraet, von dem
                 * die Uhr nichts weiss. */
                _fertig();
                return;
            }
            // "offen": nichts zu tun. Der Code steht weiter, die Restzeit
            // laeuft, die naechste Frage kommt in einem Takt.
            WatchUi.requestUpdate();
            return;
        }

        var fehler = (dict != null && dict["error"] instanceof Lang.String)
                     ? dict["error"] as Lang.String : null;

        /* 429 IST KEIN FRISTABLAUF. Der Ratenschutz steht in pair.php VOR der
         * Aktionspruefung und trifft jedes Anliegen — die Sitzung lebt weiter,
         * und die naechste Abfrage in fuenf Sekunden kann schon wieder
         * durchgehen. Sie als "Code abgelaufen" zu melden warfe eine gueltige
         * Sitzung weg und schickte die Traegerin ohne Not von vorn los.
         * Deshalb: Zeile setzen, Code stehenlassen, weiterfragen. */
        if (fehler != null && fehler.equals("zu_viele_versuche")) {
            netzHinweis = "Zu viele Versuche";
            WatchUi.requestUpdate();
            return;
        }

        if (code < 0 || code >= 500) {
            /* Verbindungsfehler und Serverfehler beenden die Sitzung NICHT
             * (E-S5-25; Vertrag 1a.3: "es darf wiederholen"). Sie lebt auf dem
             * Server bis zur Frist; der Code bleibt gueltig, und wer das
             * Telefon wieder in Reichweite bringt, macht dort weiter. Die
             * Zeile steht deshalb UNTER dem Code und ersetzt ihn nicht. */
            netzHinweis = (code < 0)
                          ? "Keine Verbindung (" + code.toString() + ")"
                          : "Server antwortet nicht";
            WatchUi.requestUpdate();
            return;
        }

        /* 410 (verfallen oder verworfen) und 401 (Kennung oder Schluessel
         * unbekannt) sind fuer die Uhr dasselbe: Diese Sitzung traegt nicht
         * mehr, und der Weg heraus ist derselbe — von vorn. Deshalb eine
         * Meldung fuer beide (Vertrag 1a.2). */
        if (code == 410 || code == 401) {
            _sitzungBeenden("Code abgelaufen", Input.lSelectHold() + ": neuer Code",
                            :error);
            return;
        }
        /* Alles Uebrige bekommt den Zahlencode und, wenn der Server eine
         * schickt, seine Meldung — wie in onStart. "Code abgelaufen" hier
         * hinzuschreiben waere geraten: Es benennt eine Ursache, die gar nicht
         * vorliegen muss, und schickt die Traegerin auf einen Weg, der dann
         * nicht hilft (Tabelle 6.2, Zeile "alles Übrige"). */
        var hinweis = null;
        if (dict != null && dict["meldung"] instanceof Lang.String) {
            hinweis = _kurz(dict["meldung"] as Lang.String);
        }
        _sitzungBeenden("Kopplung fehlgeschlagen (" + code.toString() + ")",
                        hinweis, :error);
    }

    /* Schritt 3: Ja oder Nein zu dem Konto, das der Server genannt hat.
     *
     * JA WIRD ABGEWARTET: Erst die 200 macht die Zugangsdaten gueltig, und
     * erst dann duerfen sie in den Speicher (E-S5-22). Bis dahin steht die
     * Kopplungsansicht weiter — sie zeigt den Code und die Restzeit, was in
     * dieser Sekunde beides noch stimmt. */
    function bestaetigen(antwort as Lang.String) as Lang.Boolean {
        var dev = _dev;
        var key = _key;
        var base = Uploader.serverBase();
        if (dev == null || key == null || base.length() == 0) { return false; }

        var cb = new PairJaCb(dev, antwort.equals("ja"));
        Communications.makeWebRequest(
            base + "pair.php",
            { "aktion" => "bestaetigen", "antwort" => antwort },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => {
                    "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON,
                    "X-Device-Id"  => dev,
                    "X-Api-Key"    => key
                },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            cb.method(:onResponse));
        // Nur fuers Ja: Das Nein raeumt die Ansicht ohnehin sofort ab.
        if (antwort.equals("ja")) { jaLaeuft = true; WatchUi.requestUpdate(); }
        return true;
    }

    /* Nein — im Dialog abgelehnt oder mit BACK abgebrochen.
     *
     * WIRD NICHT ABGEWARTET (E-S5-23, Muster R47). Lokal ist die Sache sofort
     * entschieden; die Sitzung stirbt serverseitig spaetestens mit der Frist.
     * Auf eine Antwort zu warten hiesse, die Traegerin vor einem Bildschirm
     * warten zu lassen, dessen Inhalt sie gerade abgelehnt hat.
     *
     * ZWEI WORTE FUER DENSELBEN WEG, weil es zwei verschiedene Lagen sind:
     * Wer im Dialog „Nein" waehlt, hat ein fremdes Konto gesehen und es
     * zurueckgewiesen — „Nicht gekoppelt". Wer BACK drueckt, hat es sich
     * anders ueberlegt — „Abgebrochen". Beide Male steht darunter derselbe
     * Weg zurueck. */
    function ablehnen(text as Lang.String) as Void {
        bestaetigen("nein");
        /* Hellgrau, nicht rot: Es ist nichts schiefgegangen. Rot ist in
         * dieser Anwendung Warnung und Fehler (Uhr-Layout_Regeln 7), und eine
         * selbst getroffene Entscheidung ist keines von beidem. */
        _sitzungBeenden(text, Input.lSelectHold() + ": neuer Code", :busy);
    }

    function onBestaetigt(fuer as Lang.String, ja as Lang.Boolean,
                          code as Lang.Number, data as Lang.Object or Null) as Void {
        /* GEHOERT DIE ANTWORT ZU DIESER SITZUNG UND ZU EINEM JA? Der Server
         * antwortet auf `ja` und auf `nein` gleichermassen `200 {"ok":true}`.
         * Ohne diese beiden Pruefungen koennte die verspaetete Antwort auf ein
         * `nein` der VORIGEN Sitzung die schwebenden Zugangsdaten der neuen in
         * Storage "cred" schreiben — die Uhr hielte sich fuer gekoppelt, jeder
         * Upload endete in 401, und ein Rueckstand verhinderte sogar das
         * Neukoppeln. Genau das schliesst E-S5-22 aus. */
        if (!ja) { return; }
        var dev = _dev;
        if (dev == null || !fuer.equals(dev)) { return; }

        if (code == 200) {
            _fertig();
            return;
        }

        var dict = (data instanceof Lang.Dictionary) ? data : null;
        var fehler = (dict != null && dict["error"] instanceof Lang.String)
                     ? dict["error"] as Lang.String : null;

        if (fehler != null && fehler.equals("device_limit")) {
            /* 409 — zwischen dem Klick im Web und dem Ja hier kann von Hand
             * ein Geraet dazugekommen sein (E-S5-18). Behebbar, und zwar nur
             * im Web. Das gehoert in die Meldung; die Sitzung ist fort. */
            _sitzungBeenden("Zu viele Geräte", "Erst eines im Web löschen", :error);
            return;
        }

        if (fehler != null && fehler.equals("zu_viele_versuche")) {
            /* Ratenschutz, kein Fristablauf — die Sitzung steht noch. Zurueck
             * in die Abfrage; die Rueckfrage kommt beim naechsten
             * `beansprucht` erneut, und das Ja laesst sich wiederholen. */
            netzHinweis = "Zu viele Versuche";
            _abfragen = true;
            WatchUi.requestUpdate();
            return;
        }

        if (code < 0 || code >= 500) {
            /* 500: Der Server hat zurueckgerollt — die Sitzung STEHT NOCH, mit
             * Konto und Restfrist (pair.php legt Geraet und Loeschung in EINEN
             * Commit; scheitert einer, bleibt die Sitzung). Vertrag 1a.3 sagt
             * ausdruecklich "es darf wiederholen".
             * Negativ: Die Verbindung brach WAEHREND des Ja. Ob der Server es
             * ausgefuehrt hat, weiss die Uhr nicht — deshalb bleibt die
             * Sitzung ebenfalls stehen und die Abfrage laeuft weiter: Kam das
             * Ja an, antwortet das naechste `status` mit "gekoppelt" und die
             * Kopplung schliesst sich von selbst (E-S5-15). Kam es nicht an,
             * steht der Code weiter. */
            netzHinweis = (code < 0)
                          ? "Keine Verbindung (" + code.toString() + ")"
                          : "Server antwortet nicht";
            jaLaeuft = false;      // der Hinweis tritt an die Stelle von "Kopple…"
            _abfragen = true;
            WatchUi.requestUpdate();
            return;
        }

        // 410 abgelaufen, 409 nicht_beansprucht, 401 und alles Uebrige.
        _sitzungBeenden("Code abgelaufen", Input.lSelectHold() + ": neuer Code", :error);
    }

    /* DER BESTAETIGUNGSDIALOG IST OHNE ANTWORT VERSCHWUNDEN.
     *
     * GEMESSEN AM 03.09.2026 (Simulator, SDK 9.2.0, fenix6pro): Ein Druck auf
     * BACK bei stehender WatchUi.Confirmation ruft onResponse NICHT auf — der
     * Dialog wird weggeraeumt, und mehr geschieht nicht. Der Vertrag von
     * ConfirmationDelegate legt das nicht fest; das Verhalten musste gemessen
     * werden, und im Code war es nicht zu sehen.
     *
     * Ohne diesen Weg blieb die Kopplungsansicht danach TOT stehen: `_abfragen`
     * war beim Oeffnen des Dialogs abgeschaltet worden und wurde nie wieder
     * eingeschaltet — kein `status` mehr, keine Rueckfrage mehr, nur ein Code,
     * der still ablief. Erholbar nur ueber BACK, und ohne jeden Hinweis darauf.
     *
     * Die Lage ist eindeutig zu erkennen: Die Ansicht ist wieder oben, es gibt
     * eine Sitzung, die Abfrage steht still, und es ist kein Ja unterwegs. Das
     * kann nur der weggeklickte Dialog sein — beim ERSTEN Erscheinen der
     * Ansicht laeuft die Abfrage bereits.
     *
     * Behandelt wird er wie ein Nein: Wer die Frage wegdrueckt, hat nicht Ja
     * gesagt, und `nein` ist in jedem Zustand erlaubt. Die Sitzung
     * zurueckzugeben ist ehrlicher, als sie bis zur Frist offen zu lassen. */
    function dialogWeggeklickt() as Void {
        if (_dev == null || _abfragen || jaLaeuft) { return; }
        /* NICHT SOFORT ABLEHNEN. Dieser Aufruf steht in PairView.onShow — die
         * Ansicht wird gerade sichtbar, und ein popView aus diesem Aufruf
         * heraus greift NICHT ZUVERLAESSIG.
         *
         * GEMESSEN AM 03.09.2026, und zwar geraeteabhaengig: Auf der fenix6pro
         * verschwand die Ansicht wie gewollt, auf der fr945 blieb sie mit dem
         * Code stehen — gleicher Quelltext, gleiches SDK. Genau davor warnt der
         * letzte Punkt der Abgabeliste in Uhr-Layout_Regeln: auf ALLEN DREI
         * Geraeten ansehen.
         *
         * Das Projekt hat fuer diese Klasse Fehler schon eine Loesung: Der
         * Dienstende-Weg schiebt den Ansichtswechsel um 100 ms hinaus (Modul
         * EndDay), weil die sich schliessende Bestaetigung ihn sonst wieder
         * mitnimmt. Hier dasselbe Muster und keine zweite Erfindung. */
        var cb = _spaeterCb;
        if (cb == null) { cb = new PairSpaeterCb(); _spaeterCb = cb; }
        var tm = _spaeterT;
        if (tm == null) { tm = new Timer.Timer(); _spaeterT = tm; }
        tm.start(cb.method(:fire), 100, false);
    }

    function spaeterAblehnen() as Void {
        var tm = _spaeterT;
        if (tm != null) { tm.stop(); }
        // Zwischenzeitlich kann sich die Lage geaendert haben (eine Antwort
        // ist eingetroffen, die Sitzung ist beendet) — dann nichts tun.
        if (_dev == null || _abfragen || jaLaeuft) { return; }
        ablehnen("Nicht gekoppelt");
    }

    /* BACK in der Kopplungsansicht (E-S5-23). */
    function abbrechen() as Void {
        ablehnen("Abgebrochen");
    }

    /* Geschafft: Die Zugangsdaten sind ab jetzt gueltig und duerfen — erst
     * jetzt — in den Speicher (E-S5-22). */
    function _fertig() as Void {
        var dev = _dev;
        var key = _key;
        if (dev == null || key == null) { return; }
        // Cast wie in Model.save() — die strenge Pruefung erkennt das
        // Literal sonst nicht als Sonderfall des PolyType. Kostet 0 Byte.
        Storage.setValue("cred", { "d" => dev, "k" => key }
                         as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
        Uploader.lastError = null;
        // ohne Haken-Glyph (Geraeteschrift kennt es nicht)
        _sitzungBeenden("Gekoppelt", null, :ok);
    }

    /* Sitzung beenden, Meldung auf die Sync-Seite, Ansicht zu.
     *
     * Ein Weg fuer alle Ausgaenge — Erfolg, Ablehnung, Abbruch, Fehler. Wer
     * dafuer vier Wege pflegt, vergisst an einem davon das Vergessen; und
     * eine schwebende Sitzung, die im Speicher liegenbleibt, waere genau die
     * halbe Kopplung, die E-S5-22 ausschliesst. */
    function _sitzungBeenden(text as Lang.String, hinweis as Lang.String or Null,
                             art as Lang.Symbol) as Void {
        _sitzungVergessen();
        status = text;
        /* Die Vormerkung aus einem gescheiterten Trennen einloesen, wenn
         * dieser Ausgang keine eigene zweite Zeile mitbringt. Bringt er eine
         * mit (etwa "Erst eines im Web löschen"), gewinnt die speziellere —
         * sie sagt inhaltlich dasselbe. */
        if (hinweis == null && _warnung != null) { hinweis = _warnung; }
        _warnung = null;
        statusHint = hinweis;
        statusKind = art;
        if (_viewOffen) {
            _viewOffen = false;
            WatchUi.popView(WatchUi.SLIDE_RIGHT);
        }
        WatchUi.requestUpdate();
    }

    function _sitzungVergessen() as Void {
        jaLaeuft = false;
        _code = null;
        _dev = null;
        _key = null;
        _endeMs = 0;
        netzHinweis = null;
        _abfragen = false;
        _pending = false;
    }

    // --- Auskuenfte fuer die Kopplungsansicht ------------------------------

    /* Der Code in zwei Dreiergruppen — „AB3 K7Q" statt „AB3K7Q" (Vertrag
     * 1a.1). Sechs gleichfoermige Zeichen abzulesen und fehlerfrei
     * einzutippen ist die eigentliche Arbeit dieses Bildschirms; die Luecke
     * in der Mitte halbiert sie. Das Web nimmt den Code mit und ohne
     * Leerzeichen entgegen. Eine andere Laenge als sechs wird unveraendert
     * gezeigt — dann stimmt etwas anderes nicht, und Raten hilft nicht. */
    function codeAnzeige() as Lang.String {
        var c = _code;
        if (c == null) { return ""; }
        if (c.length() != 6) { return c; }
        return c.substring(0, 3) + " " + c.substring(3, 6);
    }

    function restSekunden() as Lang.Number {
        var r = (_endeMs - System.getTimer()) / 1000;
        return (r > 0) ? r : 0;
    }
}
