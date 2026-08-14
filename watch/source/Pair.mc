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

// Rueckruf-Traeger (method() existiert nur auf Objekten)
class PairCb {
    function initialize() { }
    function onResponse(code as Lang.Number, data as Lang.Dictionary or Lang.String or Null) as Void {
        Pair.onResponse(code, data);
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

    function openInput() as Void {
        var tp = new WatchUi.TextPicker("");
        WatchUi.pushView(tp, new PairTextDelegate(), WatchUi.SLIDE_LEFT);
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
        if (_cb == null) { _cb = new PairCb(); }
        Communications.makeWebRequest(
            base + "pair.php",
            { "code" => code.toUpper() },
            {
                :method => Communications.HTTP_REQUEST_METHOD_POST,
                :headers => { "Content-Type" => Communications.REQUEST_CONTENT_TYPE_JSON },
                :responseType => Communications.HTTP_RESPONSE_CONTENT_TYPE_JSON
            },
            _cb.method(:onResponse));
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
    function onResponse(code as Lang.Number, data) as Void {
        var dict = (data instanceof Lang.Dictionary) ? data : null;
        var fehler = (dict != null && dict["error"] instanceof Lang.String)
                     ? dict["error"] : null;

        statusHint = null;
        statusKind = :error;

        if (code == 200 && dict != null && dict["device_id"] != null) {
            Storage.setValue("cred", {
                "d" => dict["device_id"], "k" => dict["api_key"]
            });
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
