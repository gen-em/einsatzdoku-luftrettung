// Einsatzdoku — Geraete-Kopplung per Kurzcode
//
// Auf dem Startbildschirm UP halten -> Code eintippen (5 Zeichen, aus dem
// Web unter Einstellungen -> Geraete). Die Uhr tauscht den Code bei pair.php
// gegen frische Zugangsdaten und speichert sie dauerhaft im Uhr-Speicher —
// deviceId/apiKey muessen nie mehr von Hand eingetragen werden.
// Voraussetzung: Server-Domain in den App-Einstellungen (properties.xml).
using Toybox.Lang;
using Toybox.WatchUi;
using Toybox.Communications;
using Toybox.Application.Storage;
using Toybox.Application.Properties;
using Toybox.System;

// Rueckruf-Traeger (method() existiert nur auf Objekten)
class PairCb {
    function initialize() { }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onResponse(code, data);
    }
}

// Eigener Traeger fuers Trennen. Zwei Anliegen an denselben Endpunkt brauchen
// zwei Rueckrufe — sonst muesste die Auswertung raten, worauf sie antwortet.
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

    var status as Lang.String or Null = null;   // Anzeige auf dem Startbildschirm
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

    // Laenge, die in der Hinweisschrift sicher aufs Display passt.
    const ZEILE_MAX = 26;

    function _kurz(t as Lang.String) as Lang.String {
        if (t.length() <= ZEILE_MAX) { return t; }
        // Lieber sichtbar gekuerzt als unsichtbar abgeschnitten.
        return t.substring(0, ZEILE_MAX - 1) + "…";
    }
    var _cb as PairCb or Null = null;
    var _ucb as UnpairCb or Null = null;

    /* Einstieg fuer „Gerät koppeln" (Sync-Seite, Auswahltaste halten).
     *
     * DER FALL IST DIE GETEILT GENUTZTE UHR (Backlog Nr. 14). Bis hierher
     * fuehrte der Weg direkt in die Code-Eingabe. Schlug das Koppeln fehl —
     * falscher Code, kein Telefon in Reichweite, Geraetegrenze erreicht —,
     * blieben die ALTEN Zugangsdaten stehen und die Uhr dokumentierte
     * stillschweigend weiter auf das vorherige Konto. Niemand sah es ihr an,
     * und die Person davor bekam Einsaetze, die sie nicht gefahren ist.
     *
     * Die Reihenfolge ist deshalb jetzt ausdruecklich: abfragen -> trennen ->
     * neu koppeln. Scheitert das Koppeln danach, steht die Uhr SICHTBAR ohne
     * Kopplung da (die Sync-Seite sagt „Nicht eingerichtet") statt unsichtbar
     * mit der falschen.
     *
     * EIN RUECKSTAND VERHINDERT DAS TRENNEN. Abgeschlossene, noch nicht
     * gesendete Pakete gehoeren dem BISHERIGEN Konto; nach einer Neukopplung
     * wuerden sie an das neue gehen. Das waere kein Datenverlust, sondern
     * schlimmer — fremde Einsaetze in einem fremden Konto. Also erst senden.
     */
    function start() as Void {
        if (!Uploader.hasCredentials()) { openInput(); return; }

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
        if (cred == null || base.length() == 0) { lokalTrennen(); openInput(); return; }

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
     * im Web mit einem Klick zu beheben ist. */
    function onTrennen(code as Lang.Number) as Void {
        lokalTrennen();
        if (code == 200) {
            status = "Getrennt";
            statusHint = null;
            statusKind = :ok;
        } else {
            status = "Nur auf der Uhr getrennt";
            statusHint = "Gerät im Web löschen";
            statusKind = :error;
        }
        WatchUi.requestUpdate();
        openInput();
    }

    function openInput() as Void {
        var tp = new WatchUi.TextPicker("");
        WatchUi.pushView(tp, new PairTextDelegate(), WatchUi.SLIDE_LEFT);
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
     * Garmin-Geraet. Handy und Rechner tauchen in der Statistik ueber die
     * Web-Zugriffe auf, nicht hier.
     *
     * WAS BEWUSST NICHT GESENDET WIRD: `uniqueIdentifier`. Das ist eine
     * dauerhafte, geraeteweite Kennung — fuer eine Stueckzahl-Statistik nicht
     * noetig, und in einer kleinen Gruppe ein Personenbezug mehr, als die
     * Frage rechtfertigt. Die Zuordnung leistet die device_id, die der Server
     * bei der Kopplung ohnehin vergibt.
     *
     * Eine fehlende oder unbekannte Angabe darf die Kopplung NIE verhindern:
     * Alle Felder sind fuer den Server freiwillig, und ein Geraet, das eines
     * davon nicht kennt, sendet dort null. */
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

    function request(code as Lang.String) as Void {
        var base = Uploader.serverBase();
        if (base.length() == 0) {
            status = "Erst Server-Domain setzen";
            statusHint = null;
            statusKind = :error;
            WatchUi.requestUpdate();
            return;
        }
        status = "Kopple…";
        statusHint = null;
        statusKind = :busy;
        WatchUi.requestUpdate();
        // Lokal halten und in einem Zug anlegen — s. Uploader._send():
        // die Typpruefung verfolgt eine Null-Pruefung nur ueber lokale
        // Variablen, und ein nachgestelltes if waere unerreichbar.
        var cb = _cb;
        if (cb == null) { cb = new PairCb(); _cb = cb; }
        Communications.makeWebRequest(
            base + "pair.php",
            { "code" => code.toUpper(), "geraet" => _geraeteInfo() },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => { "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            cb.method(:onResponse));
    }

    /* Antwort auswerten.
     *
     * WAS VORHER FEHLTE
     * Es wurden nur 200 und 404 unterschieden. Alles andere endete in
     * "Kopplung fehlgeschlagen (409)" — einer Meldung, die den Zahlencode
     * nennt und sonst nichts. Ausgerechnet die 409 ist aber der Fall, den
     * jemand selbst beheben kann: Es sind bereits fuenf Geraete verbunden,
     * eines muss weg. Wer stattdessen eine Zahl liest, tippt den Code
     * mehrmals neu ein und laeuft am Ende noch in die Sperre.
     *
     * WARUM DIE MELDUNG NICHT EINFACH VOM SERVER UEBERNOMMEN WIRD
     * pair.php schickt zu jedem Fehler ein Feld "meldung". Es ist fuer die
     * Weboberflaeche geschrieben: ganze Saetze, ohne Umlaute (der Server
     * schreibt "Geraete"), zu lang fuer ein Uhrendisplay. Fuer die Faelle, die
     * diese App kennt, steht der Text deshalb hier — kurz und mit Umlauten.
     *
     * Fuer alles UEBRIGE wird die Servermeldung genommen, sofern sie kommt:
     * Ein kuenftiger Fehlerfall soll nicht wieder als nackte Zahl erscheinen,
     * nur weil die Uhr ihn noch nicht kennt.
     *
     * Entschieden wird am Feld "error", nicht am Zahlencode: Der Schluessel
     * benennt die Ursache, der Code nur ihre Klasse.
     */
    function onResponse(code as Lang.Number, data as Lang.Object or Null) as Void {
        var dict = (data instanceof Lang.Dictionary) ? data : null;
        var fehler = (dict != null && dict["error"] instanceof Lang.String)
                     ? dict["error"] as Lang.String : null;

        statusHint = null;
        statusKind = :error;

        if (code == 200 && dict != null && dict["device_id"] != null) {
            // Cast wie in Model.save() — die strenge Pruefung erkennt das
            // Literal sonst nicht als Sonderfall des PolyType. Kostet 0 Byte.
            Storage.setValue("cred", {
                "d" => dict["device_id"], "k" => dict["api_key"]
            } as Lang.Dictionary<Storage.KeyType, Storage.ValueType>);
            Uploader.lastError = null;
            status = "Gekoppelt";   // ohne Haken-Glyph (Geraeteschrift kennt es nicht)
            statusKind = :ok;
        } else if (fehler != null && fehler.equals("device_limit")) {
            // 409 — behebbar, und zwar nur im Web. Das gehoert in die Meldung.
            status = "Zu viele Geräte";
            statusHint = "Erst eines im Web löschen";
        } else if (fehler != null && fehler.equals("zu_viele_versuche")) {
            // 429 — Warten hilft, weiteres Tippen nicht.
            status = "Zu viele Versuche";
            statusHint = "Später noch einmal";
        } else if (code == 404 || (fehler != null && fehler.equals("invalid"))) {
            status = "Code ungültig/abgelaufen";
            statusHint = "Im Web neuen Code holen";
        } else if (code < 0) {
            /* Negative Codes kommen nicht vom Server, sondern von der
             * Verbindung (kein Telefon in Reichweite, Bluetooth aus). Die
             * Zahl bleibt in der Meldung: Sie ist fuer eine Fehlersuche das
             * einzige Merkmal, und die Ursache liegt ausserhalb dieser App. */
            status = "Keine Verbindung";
            statusHint = "Telefon in Reichweite? (" + code.toString() + ")";
        } else {
            /* Unbekannter Fall. Der Zahlencode steht oben, weil er sicher
             * passt und fuer eine Fehlersuche taugt; die Servermeldung kommt
             * als zweite Zeile dazu, gekuerzt. So erscheint ein kuenftiger
             * Fehler nicht wieder als nackte Zahl, nur weil die Uhr ihn noch
             * nicht kennt. */
            status = "Kopplung fehlgeschlagen (" + code.toString() + ")";
            if (dict != null && dict["meldung"] instanceof Lang.String) {
                statusHint = _kurz(dict["meldung"] as Lang.String);
            }
        }
        WatchUi.requestUpdate();
    }
}

class PairTextDelegate extends WatchUi.TextPickerDelegate {
    function initialize() { TextPickerDelegate.initialize(); }
    function onTextEntered(text as Lang.String, changed as Lang.Boolean) as Lang.Boolean {
        if (text.length() > 0) { Pair.request(text); }
        return true;
    }
    function onCancel() as Lang.Boolean { return true; }
}
