<?php
// Nach config.php kopieren und ausfuellen. config.php NIE ins Git-Repo committen!
return [
    'db' => [
        'dsn'  => 'mysql:host=localhost;dbname=hems;charset=utf8mb4',
        'user' => 'hems',
        'pass' => 'CHANGE_ME',
    ],
    'app' => [
        'base_url'  => 'https://einsatz.example.de',  // ohne Slash am Ende
        'timezone'  => 'Europe/Berlin',               // Anzeige; Speicherung ist UTC
        'logo_path' => 'assets/images/gen-em_logo_helicopter.svg',  // Logo auf Login- und Einrichtungsseite
        'max_body_bytes' => 524288,                   // 512 KB Ingest-Limit
        // Die KONTAKTADRESSE und die BETREIBERMAIL stehen NICHT hier, sondern
        // unter Betrieb -> Servereinstellungen (P5a/AP5, E-P5a-40). Grund:
        // config.php wird zur Laufzeit nicht geschrieben — was hier steht,
        // laesst sich nur ueber FTP aendern. Eine Adresse, die in jeder Mail
        // steht, muss eine BetreiberIn selbst umstellen koennen.
    ],
    'smtp' => [                                       // z. B. eigener Stalwart-Server
        'host' => 'mail.example.de',
        'port' => 465,                                // implizites TLS (SMTPS)
        'user' => 'noreply@example.de',
        'pass' => 'CHANGE_ME',
        'from' => 'noreply@example.de',
        'from_name' => 'Gen-EM NAdoku',
    ],
    // ---- Netz: vertrauenswuerdige Proxys (P5a/AP4, E-P5a-17) ----------
    //
    // LEER LASSEN, WENN DIE ANWENDUNG DIREKT AM NETZ HAENGT. Dann rechnet der
    // Ratenschutz mit REMOTE_ADDR — genau wie vor Web 20.7.0.
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
    // ---- Die zwei Geheimnisse des Servers -----------------------------
    // Beide 64 Hexzeichen. Der Installer wuerfelt sie; eine bestehende
    // Installation legt sie ueber Betrieb -> Servereinstellungen an, Karte
    // "Schluessel des Servers". Beide gehoeren ins Wiederanlaufpaket
    // (docs/Technik.md, Runbook) — das Schluesselblatt druckt sie.
    //
    // server_key versiegelt Zugangsdaten der Backup-Ziele, Komplettbackup
    // und Adminpakete. Ohne ihn laeuft die Anwendung, aber nichts verlaesst
    // versiegelt das Haus.
    'server_key' => '',
    // kdf_anteil geht in den Datenschluessel JEDES Kontos ein (S10). Ohne
    // ihn laeuft alles wie vor S10 — der Schutz gegen den Datenbankabzug
    // fehlt dann. Ein ANDERER Wert als der, mit dem die Huellen gebaut
    // wurden, sperrt alle aus, bis er nachgetragen ist; der
    // Wiederherstellungsschluessel oeffnet weiterhin ohne ihn.
    'kdf_anteil' => '',
    // kdf_anteil_alt steht nur waehrend einer Rotation daneben und
    // verschwindet, sobald kein Konto mehr auf dem alten Anteil steht.
    // 'kdf_anteil_alt' => '',
];
