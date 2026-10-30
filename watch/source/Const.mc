// NAdoku — zentrale Konstanten (Werte aus Anforderungen v1.0)
using Toybox.Lang;

module Const {

    /* Fassung der Uhr-App. Sie zaehlt GETRENNT von Web und Android, weil sie
     * getrennt ausgeliefert wird (CLAUDE.md 2); sie steht auf der Sync-Seite
     * und geht als "app" im geraet-Block an den Server.
     */
    const APP_VERSION = "4.1.1";

    /* Wie lange ein geparktes (dauerhaft abgewiesenes) Paket auf der Uhr
     * liegen bleibt, bevor ein Sendelauf es raeumt (wie auf dem Handy):
     * 30 Tage ab dem Parken. Zugleich die Frist fuer ALLE Pakete einer
     * abgemeldeten Uhr, ab der ersten Abmeldung
     * (Uploader.abmeldeFristPruefen). */
    const PARK_FRIST_S = 30 * 86400;

    /* Wie oft die Kopplungsansicht nachfragt, ob jemand den Code eingetragen
     * hat (Vertrag 1a.2: "hoechstens alle fuenf Sekunden").
     *
     * FEST IM CLIENT, kein Serverfeld. Bei zehn Minuten Frist sind das
     * hoechstens 120 Anfragen je Sitzung, und die Verzoegerung zwischen dem
     * Klick im Web und der Rueckfrage auf der Uhr betraegt hoechstens fuenf
     * Sekunden. Ein Feld, mit dem der Server den Takt drosseln koennte, waere
     * Vertrag fuer einen Fall, den es noch nicht gibt; er liesse sich
     * jederzeit nachtragen, ohne den Vertrag zu brechen (Clients ignorieren
     * unbekannte Felder).
     *
     * Es ist eine BEDIENZAHL, keine Lastzahl: Eine status-Abfrage kostet
     * den Server einen SHA-256-Vergleich, keine bcrypt-Pruefung. */
    const PAIR_TAKT_MS = 5000;

    // Werte der App-Einstellung "logoWahl" (properties.xml). Zahlen, weil
    // settingConfig type="list" nur Zahlen einliest.
    const LOGO_LUFT      = 0;
    const LOGO_BODEN     = 1;
    const LOGO_WECHSELND = 2;

    // Phasen 1..9 (Index 0 unbenutzt). Der Einsatz-Abschluss ist KEIN
    // Zeitstempel mehr, sondern eine bestaetigte Aktion (Phase 10 entfaellt).
    //
    // NEUTRALE BESCHRIFTUNGEN: Die Uhr laeuft auch an bodengebundenen
    // Diensten, und sie ERFAEHRT DIE ART NICHT — die Einordnung geschieht
    // ausschliesslich im Web. Eine Beschriftung, die eine der beiden Arten
    // benennt ("Abflug", "Landung KKH"), waere an der anderen schlicht falsch,
    // und die Uhr koennte sie nicht einmal umschalten.
    //
    // Uebertragen werden ohnehin NUMMERN, keine Beschriftungen (JSON-Vertrag
    // Abschnitt 7); die Beschriftung beruehrt den Vertrag also nicht.
    const PHASE_LABELS = [
        "", "Frei", "Alarmierung", "Ausrücken", "Ankunft Einsatzort",
        "Ankunft PatientIn", "Transportbeginn", "Ankunft Klinik",
        "Übergabe", "Einsatzende"
    ];

    // Reanimations-Ereignistypen (Server-Vertrag, Abschnitt 3)
    const R_ADRENALIN   = "adrenalin";
    const R_RHYTHMUS    = "rhythmuskontrolle";
    const R_DEFI        = "defibrillation";
    const R_INTUBATION  = "intubation";
    const R_AMIODARON   = "amiodaron";
    const R_SONO        = "sonographie";
    const R_ZUGANG      = "zugang";
    const R_ROSC        = "rosc";
    const R_TOD         = "tod";

    const RESUS_LABELS = {
        "adrenalin" => "Adrenalin", "rhythmuskontrolle" => "Rhythmuskontrolle",
        "defibrillation" => "Defibrillation", "intubation" => "Intubation",
        "amiodaron" => "Amiodaron", "sonographie" => "Sonographie",
        "zugang" => "Zugang",
        "rosc" => "ROSC", "tod" => "Tod"
    };

    // GPS-Ausduennung (Anforderungen 1.5) — spaeter justierbar
    const THIN_MIN_DIST_M = 15.0;   // neuer Punkt ab >= 15 m
    const THIN_MAX_GAP_S  = 10;     // spaetestens alle 10 s
    const THIN_MIN_GAP_S  = 1;      // nie oefter als 1/s

    // Anzeige-Polylinie (Anforderungen 1.3)
    const DISPLAY_MAX_POINTS = 1000; // bei Ueberlauf Dichte halbieren

    // Upload
    const UPLOAD_CHUNK_POINTS = 500; // JSON-Vertrag Abschnitt 6
    const REST_SYNC_INTERVAL_S = 3600;

    // Reanimation
    const CPR_CYCLE_S = 120;         // 2:00-Countdown
    const LONG_PRESS_MS = 1000;
    const END_SYNC_WAIT_S = 3;       // Dienstende: so lange senden, dann fragen

    // Storage-Schluessel
    const K_STATE = "state";         // Dienst-/Einsatz-/Rea-Zustand samt Dienstkennung
    const K_TRACK_META = "trk_meta"; // Zaehler & Upload-Marken
}
