<?php
// Nach config.php kopieren und ausfuellen. config.php NIE ins Git-Repo committen!
return [
    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=hems;charset=utf8mb4',
        'user' => 'hems',
        'pass' => 'CHANGE_ME',
    ],
    'app' => [
        'base_url'  => 'https://einsatz.example.invalid',  // ohne Slash am Ende
        'timezone'  => 'Europe/Berlin',               // Anzeige; Speicherung ist UTC
        'logo_path' => 'assets/images/gen-em_logo_helicopter.svg',  // Logo auf Login- und Einrichtungsseite
        'max_body_bytes' => 524288,                   // 512 KB Ingest-Limit
        // Die KONTAKTADRESSE und die BETREIBERMAIL stehen NICHT hier, sondern
        // unter Verwaltung -> Installation. Grund: config.php wird zur Laufzeit nicht geschrieben
        // — was hier steht, laesst sich nur ueber FTP aendern. Eine Adresse,
        // die in jeder Mail steht, muss eine BetreiberIn selbst umstellen
        // koennen.
        //
        // ---- Umgebungsetikett ---------------------------------------------
        // NUR AUF EINER ANLAGE, DIE NICHT DIE PRODUKTIVANLAGE IST. Leer oder
        // fehlend heisst: keine Kennzeichnung — die Anlage verhaelt sich wie
        // die Produktivanlage, und die Statusseite sagt „Produktiv". Gesetzt,
        // traegt jede Seite den Titelvorsatz „[Staging]", eine rote
        // Kopfleiste und die Zeile „Staging — Testdaten, kein Echtbetrieb".
        // Nie abgeleitet: Weder Domain noch Zweig setzen es, nur diese Zeile.
        // `farbe` ist eine geschlossene Liste, heute nur 'rot'.
        //
        //   'umgebung' => ['name' => 'Staging', 'farbe' => 'rot'],
    ],
    // ---- Betreff-Vorsatz der Mails ----------------------------------------
    // Auf Staging '[Staging]': Jede Mail dieser Anlage traegt ihn vorn im
    // Betreff. Gehoert mit dem Etikett oben zusammen — steht der Vorsatz und
    // das Etikett nicht, warnt Betrieb -> Status („Praefix ohne Etikett").
    //
    //   'mail' => ['betreff_praefix' => '[Staging]'],

    'smtp' => [                                       // z. B. eigener Stalwart-Server
        'host' => 'mail.example.invalid',
        'port' => 465,                                // implizites TLS (SMTPS)
        'user' => 'noreply@example.invalid',
        'pass' => 'CHANGE_ME',
        'from' => 'noreply@example.invalid',
        'from_name' => 'Gen-EM NAdoku',
    ],
    // ---- Netz: vertrauenswuerdige Proxys ---------------------------------
    //
    // LEER LASSEN, WENN DIE ANWENDUNG DIREKT AM NETZ HAENGT. Dann rechnet der
    // Ratenschutz mit REMOTE_ADDR.
    //
    // Steht sie hinter einem Reverse Proxy, einem Loadbalancer oder einem
    // DDoS-Schutz, ist REMOTE_ADDR die Adresse DES PROXYS. Der Ratenschutz
    // zaehlt dann ALLE Nutzerinnen als eine und sperrt sie gemeinsam aus.
    // Wer hier eintraegt, sagt: „Von diesen Adressen glaube ich der Kopfzeile
    // X-Forwarded-For." Das ist eine Aussage ueber die eigene Netztopologie,
    // und nur die Betreiberin kann sie treffen — deshalb gibt es keine
    // Vorgabe. Adressen oder CIDR-Bereiche, IPv4 und IPv6:
    //
    //   'vertrauenswuerdige_proxys' => ['10.0.0.8', '192.168.1.0/24', '2001:db8::/32'],
    //
    // Dieselbe Liste entscheidet ueber X-Forwarded-Proto (HTTPS-Zwang, HSTS):
    // Wer die Client-Adresse faelschen koennte, koennte sonst auch behaupten,
    // eine Anfrage sei ueber HTTPS gekommen.
    'netz' => [
        'vertrauenswuerdige_proxys' => [],
    ],
    // ---- Health-Endpunkt fuer das eigene Monitoring -----------------------
    //
    // DAS TOKEN STEHT NICHT HIER: Es wird unter
    // Betrieb -> Hintergrundjobs, Karte "Monitoring", erzeugt und liegt in
    // der Datenbank. Eine Zeile 'betrieb' => ['health_token' => '<Wert>'] in
    // einer bestehenden config.php wird beim ersten Abruf
    // uebernommen und danach nicht mehr gelesen; sie kann dann entfallen.
    // ---- Die zwei Geheimnisse des Servers -----------------------------
    // Beide 64 Hexzeichen. Der Installer wuerfelt sie; eine bestehende
    // Installation legt sie ueber Betrieb -> Servereinstellungen an, Karte
    // "Schluessel des Servers". Beide gehoeren ins Wiederanlaufpaket
    // (docs/Technik.md, Runbook) — das Schluesselblatt druckt sie.
    //
    // server_key versiegelt, was der Server ohne Browser lesen muss: die
    // Zugangsdaten der Backup-Ziele, die Geheimnisse des Zweitfaktors (TOTP),
    // die Konto-Backups der Verwaltung (Begleitdatei und Pakete), die
    // Archive des Protokolls und das Komplettbackup. Wer config.php UND die
    // Datenbank hat, oeffnet damit den zweiten Faktor aller Konten — keine
    // Patientendaten. Ohne ihn laeuft die Anwendung, aber nichts verlaesst
    // versiegelt das Haus.
    'server_key' => '',
    // server_key_alt steht nur waehrend eines Wechsels daneben (Betrieb ->
    // Servereinstellungen) und verschwindet, sobald alles umgehuellt ist.
    // Was auf dem Backup-Ziel liegt, oeffnet danach nur noch
    // der Wert vom bisherigen Schluesselblatt.
    // 'server_key_alt' => '',
    // kdf_anteil geht in den Datenschluessel JEDES Kontos und in den
    // Pfeffer des Anmelde-Hashes ein. Fehlt er oder steht ein ANDERER Wert
    // darin als der, mit dem Huellen und Hashes gebaut wurden, meldet sich
    // niemand an, auch die BetreiberIn nicht, bis er nachgetragen ist. Der
    // Wiederherstellungsschluessel oeffnet die Daten weiterhin ohne ihn.
    'kdf_anteil' => '',
    // kdf_anteil_alt steht nur waehrend einer Rotation daneben und
    // verschwindet, sobald kein Konto mehr auf dem alten Anteil steht.
    // 'kdf_anteil_alt' => '',
];
