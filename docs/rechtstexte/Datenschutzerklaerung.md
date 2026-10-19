# Datenschutzerklärung — Entwurf

> **Entwurf vom 19.10.2026, nicht anwaltlich geprüft.** Beschreibt den
> Stand **nach S11** (Ortsdaten verschlüsselt, Web 22 · Uhr 4.0.0 ·
> Android 0.18.0) — einzuspielen erst, wenn S11 auf Produktiv läuft und
> der Ortsumzug des Altbestands durch ist (Entscheidung der BetreiberIn
> vom 19.10.2026). Er ersetzt die Bausteine B1 bis B13 aus P5b
> (`Datenschutz-Ergaenzung-P5.md`, gelöscht mit diesem Entwurf; die
> Git-Historie behält sie) und die drei Absätze in Handbuch 11.5a, die er
> aufnimmt.
>
> **Einspielen:** Den Teil zwischen den beiden Linien unten in den Editor
> kopieren (Verwaltung → Rechtstexte → Datenschutzerklärung), Stellen in
> eckigen Klammern füllen, Standdatum setzen. Der Editor kennt nur
> Überschriften, Absätze, Listen und Links — kein Fett, keine Tabellen;
> der Text ist darauf geschrieben. Das Standdatum setzt der Editor unter
> den Text, es steht deshalb nicht darin.
>
> **Grundlage:** jede Angabe ist am Code gelesen (Stand `main` bei
> `88cb6bc`, 19.10.2026); Verschlüsselung und ihre Grenzen nach
> `docs/Bedrohungsmodell.md` 1 bis 3 und `CLAUDE.md` 4. Die
> Prüfhinweise am Ende sagen, was vor dem Einspielen zu klären ist.

---

## Das Wichtigste in Kürze

- Patientendaten, Einsatzorte, GPS-Daten, die Orte der Einsatzphasen, Reanimationsverläufe und Transportziele werden auf deiner Uhr, deinem Handy oder in deinem Browser verschlüsselt. Öffnen kann sie nur dein Browser. Wir, unser Hoster und alle Stellen auf dem Übertragungsweg sehen davon nur verschlüsselte Daten.
- Für uns lesbar bleiben Zeiten, Besatzung, Rettungsmittel, Standort und deine Kontodaten (Abschnitt 3).
- Kein Tracking, keine Werbung, keine Analyse-Cookies. Wir geben keine Daten für eigene Zwecke weiter.
- Für die Einsätze, die du dokumentierst, bist du selbst verantwortlich; wir verarbeiten sie in deinem Auftrag.

## 1. Wer verantwortlich ist

Gen-EM GbR
[Anschrift]
vertreten durch [Name(n)]
E-Mail: [Adresse]
Datenschutzbeauftragte/r: [Name und Kontakt, oder: nicht benannt]

Für dein Konto und den Betrieb des Dienstes sind wir verantwortlich. Für die Einsatzdaten, die du einträgst oder deine Geräte aufzeichnen, bist du verantwortlich; wir verarbeiten sie nach der [Vereinbarung zur Auftragsverarbeitung](avv.php), die du bei der Registrierung annimmst. NAdoku ist für die private Dokumentation eigener Einsätze bestimmt ([Nutzungsbedingungen](nutzungsbedingungen.php), Abschnitt 2).

## 2. Verschlüsselung

### Was verschlüsselt ist

- Name, Geburtsdatum, Alter und Diagnose der PatientIn
- Einsatznummer
- Adresse, Koordinate und Beschreibung des Einsatzorts, ein von Hand gesetzter Abfahrtort
- Notizen des Einsatzes
- Transportziel mit Koordinate, Höhe des Einsatzorts
- GPS-Daten
- Ort jeder Einsatzphase
- Verlauf einer Reanimation

### Wie

Uhr und Handy verschlüsseln ihre Aufzeichnung, bevor sie sie senden, mit dem öffentlichen Schlüssel deines Kontos. Was du im Browser einträgst, verschlüsselt dein Browser, bevor er es sendet. Öffnen kann beides nur dein Browser: Der Schlüssel dazu entsteht aus deinem Passwort (und einem Anteil unseres Servers, der allein nichts öffnet) und verlässt deinen Browser nicht. Auf dem ganzen Weg — Begleit-App der Uhr, Internet, unser Server, unsere Sicherungen — liegen diese Angaben nur verschlüsselt; unser Server nimmt sie unverschlüsselt gar nicht an.

Bei der Einrichtung erhältst du einen Wiederherstellungsschlüssel. Ohne dein Passwort und ohne diesen Schlüssel kann niemand deine verschlüsselten Daten wiederherstellen, auch wir nicht.

### Wo die Verschlüsselung endet

- Auf deinen Geräten: Uhr und Handy halten eine Aufzeichnung bis zum Senden unverschlüsselt im Gerätespeicher. Pakete, die sich nicht zustellen lassen, löschen sie nach 30 Tagen.
- Bei Adresssuche und Karte: Abschnitt 5.
- Bei Exporten: Was du ohne Passwort exportierst (etwa als CSV), ist unverschlüsselt.
- In älteren Sicherungen: Sicherungen von vor dem [Datum der Umstellung] enthalten Orte, GPS-Daten und Reanimationsverläufe noch unverschlüsselt, bis sie [gelöscht sind / Aufbewahrungsregel].
- Gegen Eingriffe in den Server: Die Verschlüsselung setzt voraus, dass unser Server die Software unverändert ausliefert. Wer ihn übernähme, könnte künftig Eingegebenes abgreifen, bereits Gespeichertes nicht.
- Gegen ein fremdes Passwort: Wer dein Passwort kennt und sich anmelden kann, kann deine Daten lesen. Ein zweiter Faktor verhindert die Anmeldung mit dem Passwort allein.

## 3. Welche Daten wir verarbeiten

### Konto

- Was: E-Mail-Adresse, Name, Passwort (nur als nicht umkehrbarer Prüfwert), Rolle, Einstellungen, Zeitpunkt der Registrierung und der letzten Anmeldung; welche Fassung der Nutzungsbedingungen, der Auftragsverarbeitung und dieser Erklärung du wann angenommen oder zur Kenntnis genommen hast; deine Antworten auf die Rückfrage zum Wiederherstellungsschlüssel (nicht der Schlüssel selbst); Zahl deiner Einsätze und belegter Speicher.
- Mit zweitem Faktor zusätzlich: das Geheimnis der Authenticator-App (versiegelt), die Notfallcodes (als Prüfwert), bei Passkeys deren öffentlicher Schlüssel, Kennung, deine Bezeichnung und die Zeitpunkte von Anlage und letzter Nutzung. Der geheime Teil eines Passkeys verlässt dein Gerät nicht.
- Wofür: Konto führen, Anmeldung, Nachweis deiner Zustimmungen. Rechtsgrundlage: Art. 6 Abs. 1 lit. b und c DSGVO.
- Wie lange: bis zur Löschung des Kontos. Unbestätigte Registrierungen löschen wir nach 48 Stunden, nicht freigeschaltete nach [30 Tagen]. Löschst du dein Konto, ist es sofort gesperrt und nach 30 Tagen endgültig gelöscht; bis dahin kannst du die Löschung durch Anmelden widerrufen.

### Dienste und Einsätze

- Für uns lesbar: Datum, Beginn und Ende von Dienst und Einsatz; Zeitpunkte und Nummern der Einsatzphasen; Strecke und Anstieg; Rettungsmittel und Standort samt Koordinate; Namen der Besatzung; Einsatzmerkmale (etwa Transportart, Fehleinsatz); Angaben zu weiteren beteiligten Kräften; Notizen des Diensttags; Zeitraum und Punktzahl einer GPS-Aufzeichnung, nicht die Punkte selbst. Felder, die so gespeichert werden, sind in der Oberfläche mit „Klartext — keine Patientendaten" gekennzeichnet.
- Verschlüsselt: alles aus Abschnitt 2.
- Was daraus folgt: Wer weiß, wann ein Einsatz stattfand, kann ihn über die Zeiten finden; Ort, GPS-Daten, Klinik und Reanimation sieht er nicht.
- Wofür: Bereitstellen des Dienstes — Anzeige, Auswertung und Statistik deiner eigenen Einsätze. Grundlage: die Vereinbarung zur Auftragsverarbeitung (Art. 28 DSGVO).
- Wie lange: bis du sie löschst. Gelöschtes liegt 90 Tage im Papierkorb.

### Gekoppelte Geräte und Standort

- Was: Gerätekennung, Typ und Modell, Zeitpunkt der letzten Verbindung, Geräteschlüssel (als Prüfwert). Keine IMEI, keine Seriennummer, keine Werbe-ID.
- Standort: Handy und Uhr zeichnen den Standort nur zwischen „Dienst beginnen" und „Dienst beenden" auf. Die Android-App braucht keine Berechtigung für den Standort im Hintergrund; eine Benachrichtigung zeigt die laufende Aufzeichnung. Ohne gekoppeltes Gerät entstehen keine GPS-Daten.
- Bei jeder Kopplung und Trennung schicken wir dir eine E-Mail, damit dir eine fremde Kopplung auffällt.
- Wie lange: bis du das Gerät trennst oder dein Konto löschst.

### Schutz vor Missbrauch

- Was: deine IP-Adresse bei Anmeldung, Registrierung, Passwort-Zurücksetzung und beim Empfang von Gerätedaten.
- Wofür: Fehlversuche zählen und bei Häufung zeitweise sperren (Art. 6 Abs. 1 lit. f DSGVO, Sicherheit des Dienstes).
- Wie lange: Zähler einen Tag; Sperren und Sperrversuche mit IP- und E-Mail-Adresse 30 Tage. Sicherungen enthalten diese Angaben nicht. An keiner anderen Stelle speichert die Anwendung IP-Adressen.
- Unser Hoster führt Zugriffsprotokolle des Webservers: [Inhalt und Aufbewahrungsdauer laut Hoster].

### Betriebsprotokoll

- Was: Verwaltungshandlungen (etwa Konto angelegt, gesperrt, Rolle geändert), versendete Systemmitteilungen, Wartung, Sicherungen, Fehler. Einzelne Einträge enthalten E-Mail-Adressen, keiner eine IP-Adresse.
- Nicht: Wir protokollieren nicht, wer wann welchen Einsatz geöffnet, gelesen oder exportiert hat.
- Wofür: Nachvollziehbarkeit und Sicherheit des Betriebs (Art. 6 Abs. 1 lit. f DSGVO).
- Wie lange: Verwaltungshandlungen [365] Tage, alles andere 30 Tage; ein versiegeltes Wochenarchiv 365 Tage.

### E-Mails

- Wir senden nur Mitteilungen, die dein Konto betreffen: Bestätigungen, Sicherheitshinweise, Kopplungen, Hinweise ab 80 % deines Kontingents, Rückfragen zum Wiederherstellungsschlüssel. Keine Werbung.
- Der Inhalt wird nach dem Versand gelöscht; bei nicht zustellbaren Mitteilungen bleiben Empfänger und Betreff 30 Tage.

### Sicherheitsberichte des Browsers

- Dein Browser kann Verstöße gegen unsere Sicherheitsrichtlinie (CSP) melden: betroffene Regel, Quelle und Seite, keine IP-Adresse, keine Inhalte. Wir bewahren sie 30 Tage auf.

### Sicherungen

- Wir sichern die ganze Installation (zwei versiegelte Stände) und jedes Konto (zwei Stände, alle 30 Tage) [und übertragen die Sicherungen an: Anbieter, Ort]. Verschlüsselte Daten bleiben darin verschlüsselt.
- Nach der Löschung deines Kontos bleiben deine Daten in Sicherungen, bis diese überschrieben sind: [Aufbewahrungsregel].

### Betriebszahlen

- Wir zählen nur zusammengefasst aus vorhandenen Daten: Zahl der Konten, der Einsätze und der Gerätetypen. Kein Analysewerkzeug, keine Telemetrie.

## 4. Cookies

Wir setzen nur Cookies, die für die Anmeldung nötig sind. Das Sitzungscookie hält die Anmeldung, ein zweites bindet sie an deinen Browser; beide enden, wenn du dich abmeldest oder den Browser schließt. Wählst du beim zweiten Faktor „Dieses Gerät merken", kommt ein drittes Cookie dazu, das [90] Tage gilt; bei uns liegt davon nur ein Prüfwert, kein Gerätename und kein Browsertyp. Beim Setzen eines Passworts und auf der Registrierungsseite kommt für diesen einen Vorgang ein eigenes Sitzungscookie dazu.

## 5. Empfänger

- Hosting: [dataforest, Firmierung, Anschrift], Deutschland. Vertrag zur Auftragsverarbeitung.
- E-Mail-Versand: [lima-city, Firmierung, Anschrift], Deutschland. Vertrag zur Auftragsverarbeitung.
- Auswärtige Sicherung: [Anbieter, Ort — oder Zeile streichen].
- Adresssuche: Tippst du eine Adresse in das Feld Einsatzort oder Abfahrtort, fragt dein Browser direkt den Adressdienst [Photon der komoot GmbH, Potsdam]. Er erhält die getippte Adresse oder Koordinate und deine IP-Adresse — damit verlässt eine geschützte Angabe unverschlüsselt deinen Browser. Unser Server sieht diese Anfrage nicht. Du kannst die Adresssuche in deinen Einstellungen abschalten.
- Karten: Zeigt eine Seite eine Karte, lädt dein Browser die Kartenausschnitte direkt vom Kartendienst — OpenStreetMap, auf Wunsch OpenHikingMap (openmaps.fr), OpenTopoMap oder Luftbilder von Esri (USA). Der Dienst sieht deine IP-Adresse und den Kartenausschnitt, also die Gegend des Einsatzorts.
- Garmin und Google: Die Garmin-Uhr sendet über die Garmin-Connect-App auf deinem Handy; die Wear-OS-Uhr gibt ihre Ereignisse über Google-Dienste an dein Handy. Dabei sichtbar sind Zeiten und Phasennummern, bei Garmin auch Strecke und Anstieg — keine Orte, keine Spuren, keine Patientendaten. Es gelten die Datenschutzbestimmungen von Garmin und Google.

Darüber hinaus geben wir keine Daten weiter.

## 6. Deine Rechte

Du hast das Recht auf Auskunft, Berichtigung, Löschung, Einschränkung der Verarbeitung und Datenübertragbarkeit (Art. 15 bis 18 und 20 DSGVO) sowie auf Widerspruch gegen Verarbeitungen nach Art. 6 Abs. 1 lit. f DSGVO (Art. 21). Vieles davon kannst du selbst in der Anwendung tun: Daten ansehen, ändern, exportieren und dein Konto löschen. Deine verschlüsselten Daten können wir selbst nicht einsehen; Auskunft darüber gibt dir dein Konto.

Für Einsatzdaten bist du verantwortlich: Wenden sich PatientInnen oder KollegInnen mit einem Anliegen zu ihren Daten an dich, unterstützen wir dich dabei.

Du kannst dich bei einer Datenschutz-Aufsichtsbehörde beschweren, etwa bei der für uns zuständigen: [Behörde, Anschrift, Link].

## 7. Änderungen

Ändert sich diese Erklärung, weisen wir dich bei der nächsten Anmeldung darauf hin.

---

*Prüfhinweise (nicht Teil des Textes):*

1. **Platzhalter:** Anschrift und Vertretung der GbR, Kontakt,
   Datenschutzbeauftragte/r, Firmierungen von dataforest und lima-city,
   auswärtiges Sicherungsziel und seine Aufbewahrungsregel, Datum der
   S11-Umstellung, Frist für nicht freigeschaltete Registrierungen
   (Vorgabe 30 Tage, `konto_lib.php`), Aufbewahrung des
   Verwaltungsprotokolls (Vorgabe 365, einstellbar 90 bis 1095),
   Gültigkeit „Gerät merken" (Vorgabe bis 90 Tage, `totp_lib.php`),
   Zugriffsprotokolle des Hosters, Aufsichtsbehörde. Wer eine Vorgabe
   umgestellt hat, trägt den eingestellten Wert ein.
2. **Adressdienst:** Trägt die Installation einen anderen Photon-Dienst
   ein (Betrieb → Servereinstellungen), gehört dessen BetreiberIn in die
   Klammer; ist die Adresssuche für alle aus, entfällt der Punkt. Die
   Textbaustein-Karte im Editor nennt den eingestellten Dienst.
3. **Esri sitzt in den USA.** Luftbilder lädt der Browser nur, wenn man
   die Ebene „Luftbild" wählt; dann geht die IP-Adresse dorthin. Ob das
   eine Drittlandübermittlung ist, die eine eigene Angabe (Art. 44 ff.
   DSGVO) braucht, ist rechtlich zu prüfen. Dasselbe gilt für die
   Einordnung von Photon und den Kachelservern (AVV Anlage IV Nr. 4).
4. **Rechtsgrundlagen sind Vorschläge.** Insbesondere ob die
   Einsatzdaten ausschließlich über Art. 28 laufen und die Gerätedaten
   (Typ, Modell) unter lit. b fallen.
5. **Die „Grenzen" in Abschnitt 2 sind die vier aus `CLAUDE.md` 4** —
   Altbestand (entfällt nach dem Ortsumzug, deshalb nur die Sicherungen),
   alte Sicherungen, Gerätepuffer, Schlüssel ohne zweiten Kanal (als
   „Eingriffe in den Server") — dazu Adresssuche, Karten, Exporte und das
   Passwort. Wer den Text kürzt, kürzt diese Liste nicht.
6. **Was der Text bewusst nicht nennt:** Die Begleit-App der Uhr sieht
   auch den Geräteschlüssel, mit dem die Uhr sich anmeldet
   (Bedrohungsmodell, Lage M); das Konto-Paket kann die Verwaltung in ein
   anderes Konto freigeben und dabei den Klartextteil lesen (Lage I). Ob
   beides in die Erklärung gehört, entscheidet die BetreiberIn mit der
   Rechtsprüfung.
7. **Zu ändern, wenn:** sich die Liste der verschlüsselten Felder ändert
   (Feldkatalog, `'store' => 'pat'`), ein Fremddienst dazukommt oder
   wegfällt, eine Frist sich ändert. Die AVV (Anlage II Nr. 2) und die
   Nutzungsbedingungen (4.1, Hinweis d) beschreiben noch den Stand vor
   S11 und brauchen dieselbe Runde.
