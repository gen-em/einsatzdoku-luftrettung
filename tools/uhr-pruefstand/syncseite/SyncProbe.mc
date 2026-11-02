// Fuellung der Sync-Seite — NUR fuer die Prueffassung von syncseite.sh,
// KEIN Bestandteil der App. syncseite.sh kopiert watch/ nach
// $CIQ_BASIS/syncseite-app, legt diese Datei und Rundlauf.mc dazu und ruft
// beide aus NAdokuApp.onStart auf; die App startet dort direkt auf der
// Sync-Seite.
//
// Anlass: Nr. 402 — ab vier Zeilen im unteren Block lag der Mittelblock auf
// der obersten Meldung (Venu 3s, im Simulator gesehen).
//
// WIE DIE ZEILEN ENTSTEHEN. Rundlauf.fuellen() legt zwei offene Pakete an;
// ohne Kopplung steht darum der Einrichtungsschritt unten, dazu die
// Versionszeile: zwei Zeilen. "Gesperrt" bringt zwei (Grund, Weg heraus),
// ein Upload-Fehler eine, eine Kopplungsmeldung eine, ihr Hinweis eine:
// ZEILEN 4 bis 7. syncseite.sh setzt die Zahl in der Kopie.
using Toybox.Lang;

module SyncProbe {
    const ZEILEN = 4;

    function fuellen() as Void {
        Uploader.gesperrt = "wartet";
        if (ZEILEN >= 5) { Uploader.lastError = "Upload 500"; }
        if (ZEILEN >= 6) { Pair.status = "Keine Verbindung"; Pair.statusKind = :error; }
        if (ZEILEN >= 7) { Pair.statusHint = "Telefon in Reichweite?"; }
    }
}
