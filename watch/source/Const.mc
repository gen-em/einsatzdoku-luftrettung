// NAdoku — zentrale Konstanten (Werte aus Anforderungen v1.0)
using Toybox.Lang;

module Const {

    /* Fassung der Uhr-App. Sie zaehlt GETRENNT von Web und Android, weil sie
     * getrennt ausgeliefert wird (CLAUDE.md 2); sie steht auf der Sync-Seite
     * und geht als "app" im geraet-Block an den Server.
     *
     * Wofuer die Hauptnummern stehen:
     *   1.x  die urspruengliche App: Dienst, Einsatz, Phasen, Reanimation,
     *        GPS-Spur, Warteschlange. Innerhalb der Reihe kamen die zweite
     *        und dritte Geraeteklasse dazu (FR945, Venu 3s) und mit ihnen das
     *        Eingabemodell in Input.mc.
     *   2.0  neue Anwendungs-ID (manifest.xml). Fuer die Uhr ist das eine
     *        ANDERE App — eigene Einstellungen, eigener Speicher, also auch
     *        keine Kopplung mehr. Ein Umbau am Datenbestand des Geraets.
     *   3.0  die Kopplung laeuft andersherum (S5, E-R49-1): Die Uhr ZEIGT
     *        einen Code, statt einen einzutippen. Hauptnummer, weil es ein
     *        spuerbar anderer Weg durch die Anwendung ist UND weil keine
     *        aeltere Fassung mehr koppelt — der alte Weg setzte einen im Web
     *        erzeugten Code voraus, und den gibt es nicht mehr (E-R49-7).
     *        Bestehende Kopplungen sind davon nicht beruehrt: ingest.php und
     *        Vertragsabschnitt 1 aendern sich nicht.
     */
    const APP_VERSION = "3.0.1";

    /* Wie oft die Kopplungsansicht nachfragt, ob jemand den Code eingetragen
     * hat (E-S5-32, Vertrag 1a.2: "hoechstens alle fuenf Sekunden").
     *
     * FEST IM CLIENT, kein Serverfeld. Bei zehn Minuten Frist sind das
     * hoechstens 120 Anfragen je Sitzung, und die Verzoegerung zwischen dem
     * Klick im Web und der Rueckfrage auf der Uhr betraegt hoechstens fuenf
     * Sekunden. Ein Feld, mit dem der Server den Takt drosseln koennte, waere
     * Vertrag fuer einen Fall, den es noch nicht gibt; er liesse sich
     * jederzeit nachtragen, ohne den Vertrag zu brechen (Clients ignorieren
     * unbekannte Felder).
     *
     * Es ist eine BEDIENZAHL, keine Lastzahl: Seit Web 13.0.0 kostet eine
     * status-Abfrage den Server einen SHA-256-Vergleich (E-S5-41/-42), nicht
     * mehr eine bcrypt-Pruefung. */
    const PAIR_TAKT_MS = 5000;

    // Werte der App-Einstellung "logoWahl" (properties.xml). Zahlen, weil
    // settingConfig type="list" nur Zahlen einliest.
    const LOGO_LUFT      = 0;
    const LOGO_BODEN     = 1;
    const LOGO_WECHSELND = 2;

    // Phasen 1..9 (Index 0 unbenutzt). Der Einsatz-Abschluss ist KEIN
    // Zeitstempel mehr, sondern eine bestaetigte Aktion (Phase 10 entfaellt).
    //
    // NEUTRALE BESCHRIFTUNGEN seit 1.8.0 (E20): Phase 3 hiess "Abflug", Phase 7
    // "Landung KKH". Die Uhr laeuft seit Web 6.0.0 auch an bodengebundenen
    // Diensten, und sie ERFAEHRT DIE ART NICHT (E21) — die Einordnung geschieht
    // ausschliesslich im Web. Eine Beschriftung, die eine der beiden Arten
    // benennt, waere an der anderen schlicht falsch, und die Uhr koennte sie
    // nicht einmal umschalten.
    //
    // Uebertragen werden ohnehin NUMMERN, keine Beschriftungen (JSON-Vertrag
    // Abschnitt 7). Diese Aenderung beruehrt den Vertrag also nicht; sie holt
    // die Uhr nur dorthin, wo der Server seit Web 6.0.0 steht (Berichtigung B8).
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
