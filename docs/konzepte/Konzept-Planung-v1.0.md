# Konzept — Planung v1.0 (Schritt 10, vorgezogen)

**Zielpfad im Repositorium:** `docs/konzepte/Konzept-Planung-v1.0.md`
**Arbeitskennung:** PV (Planung v1.0). Die endgültige Kennung vergibt die
Rahmenplan-Fassung, die dieses Konzept einträgt (Rahmenplan Abschnitt 9).
**Grundlage:** `docs/Rahmenplan.md` Fassung 24 (03.09.2026, morgens) — beim
Einpflegen lag Fassung 25 vor (S5 gemergt, Web 13.2.0, Uhr 3.0.0, Android
0.7.7; Backlog bis 97), daher **Fassung 26** und Backlog **98–113** statt der
im Text zunächst genannten 25 und 90–105; Rahmenplan-Archiv (R1–R50 im
Volltext); `CLAUDE.md`; `.github/workflows/deploy.yml`;
`docs/konzepte/Konzept-S4-Handy-Uhr-Client.md`; Rechercheergebnisse zur
Store-Verteilung vom 01.09.2026.
**Modell:** Fable (R14) — das Konzept entsteht mit Fable 5.1.
**Erstellt:** 03.09.2026.

---

## 0. Status

| Was | Stand |
|---|---|
| **Stand** | **Eingepflegt am 03.09.2026** als Rahmenplan **Fassung 26** — alle Blöcke aus Abschnitt 6 angewendet (Reihenfolge 6.4), Backlog 98–113, Konzept S4 Nachträge, Vorbereitung S9; Gegenproben 6.3 ausgeführt (Abschnitt 8, letzte Zeile). Die Schrittnummern im Rahmenplan sind nach R73 verschoben; **dieses Dokument nennt die Schritte noch mit den Nummern vor R73** (P5 = 9, Planung = 10, P6 = 11 …) — die Abschnitte 3 und 6 des Rahmenplans sind maßgeblich |
| Erledigt | **F-PV-7** — E-PV-1, 6.2.7 · **F-PV-1** — E-PV-2, 6.2.1 · **F-PV-3** — E-PV-3, 6.2.3 · **F-PV-2** — E-PV-4, 6.2.2 · **F-PV-4** — E-PV-5, 6.2.4 · **F-PV-5** — **E-PV-6** (03.09.2026), 6.2.5 · **F-PV-8 Phasenschnitt** — E-PV-7, 6.2.8 · **F-PV-6** — E-PV-8, 6.2.6 · **F-PV-9 Problemsammlung → S9** — **E-PV-9** (03.09.2026), 6.2.9, Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` · **Nachtrag Wartungsmodus** — Zuschnitt freigegeben 03.09.2026, Zusatz-Konzept `Konzept-S5-Zusatz-Wartungsmodus.md` und Prompt für die Server-Instanz liegen vor (Abschnitt 7.4) |
| Bleibt | die Paketschnitte je Phase — im jeweiligen Konzept (P6 nach der Freigaberunde des Reviews); dieses Dokument bleibt bis zum Abschluss von P8 als Referenz für R65–R72 liegen, dann R62 |
| Sofort einpflegbar | 6.1 · 6.2.7 (R65) · 6.2.1 (R66) · 6.2.3 (R67) · 6.2.2 (R68) · 6.2.4 (R69) · 6.2.5 (R70) · **6.2.8 (R71) — zuletzt anwenden, es ordnet alle P6-Nennungen zu** |
| Hakt | zwei kleine Bestätigungen: (1) „(e) entfällt mit Veröffentlichung im Play Store" ist als **Produktionsfreigabe (Welle 1)** eingetragen (E-PV-1, Punkt 6); (2) **Abnahmemaß** für das Handbuch „höchstens ein Drittel des heutigen Umfangs" (E-PV-8, Punkt 4) ist ein Vorschlag |

---

## 1. Auftrag und Abgrenzung

**Anlass (03.09.2026).** Frage des Auftraggebers, ob Punkte des Rahmenplans,
die vor v1.0 zur Diskussion stehen, jetzt geklärt werden können — auch wenn
andere Schritte vorher umgesetzt werden. Der Rahmenplan bündelt diese Punkte
in **Schritt 10 „Planung v1.0"** (R59, R60), hinter P5 einsortiert. Seine
Gegenstände sind Festlegungen, keine Umsetzung; sie brauchen den P5-Stand
nicht, und zwei davon werden vor P5 gebraucht (3.0).

**Auftrag (bestätigt 03.09.2026):**

1. Schritt 10 **komplett** vorziehen und Punkt für Punkt abarbeiten.
2. Ergebnisse so dokumentieren, dass **eine andere Instanz mit Zugriff auf
   das Repositorium** sie in `docs/Rahmenplan.md` (und wo nötig in andere
   Dokumente) schreiben kann — Abschnitt 6 dieses Konzepts.
3. Die **Store-Verteilung der Clients** formal in den Rahmenplan aufnehmen
   (Befund A in 2.2) — als F-PV-7.

**Was dieses Konzept liefert:** je Frage Befund, Optionen mit Preis,
Empfehlung; nach der Entscheidung ein E-Eintrag (Abschnitt 5) und ein
wörtlicher Einfügeblock für den Rahmenplan (Abschnitt 6). Dazu die
Zuarbeiten, die aus den Entscheidungen erwachsen.

**Was es nicht liefert:**

- **Den P6-Paketschnitt.** Der Rahmenplan nennt als Ergebnis von Schritt 10
  „das P6-Konzept nach K1 mit Paketschnitt". Der Schnitt braucht den
  P5-Stand (Registrierung, Rollen, Dashboard fließen in Review und Doku).
  Vorschlag: Schritt 10 bleibt im Fahrplan, **verkürzt auf den Paketschnitt**
  aus den E-PV-Entscheidungen; die Festlegungen selbst sind dann getroffen
  (Einfügeblock 6.1.2).
- **Punkte außerhalb von Schritt 10**, die ebenfalls vor v1.0 klärbar wären
  und am 03.09.2026 genannt wurden: die drei Fragen aus
  `Konzept-V1-Ortsdaten.md` (Nr. 43), die Grundsatzfrage der Mengenbremse
  (R19), die P5-Vorentscheidungen (R9, R33, R37), die Bedienhöhe am
  Schreibtisch (Nr. 74). Auf Wunsch als eigenes Konzept.
- **Die Hosting-Entscheidung (R36)** — Zuarbeit; F-PV-3 hängt an ihr und
  nennt, was von ihr gebraucht wird.
- **Ein Prüfdokument nach K9.** Es entsteht kein Code; die Prüfung dieses
  Konzepts ist die Konsistenz der Rahmenplan-Änderungen, und die
  Gegenproben dafür stehen in 6.3. Wer das anders sieht, sagt es.

**Kein Versionssprung, kein Deploy.** Alle Änderungen liegen unter `docs/`;
nach `CLAUDE.md` 2 stuft das keine der drei Zählungen hoch, und
`deploy.yml` reagiert nur auf `server/**`. Die Wortliste (R28) läuft, weil
normative Dokumentation berührt wird.

---

## 2. Befund

### 2.1 Was der Rahmenplan zu Schritt 10 festlegt

Fahrplan-Zeile 10: „Konzeptgespräch vor dem Schnitt: Umfang des
Code-Reviews, Aufteilung in mehrere Repositorien, Auslieferungskette,
Update-Weg (R59, R60); Ergebnis ist das P6-Konzept" — Voraussetzung
Schritt 9, Modell Fable. Der Schritt-10-Block nennt **fünf Gegenstände**:
Code-Review (R17), Aufteilung in Repositorien, Auslieferungskette (R40),
Update-Weg ab v1.0 (R60), Web-App auf Android (Nr. 87) — „dazu die
Doku-Anforderungen nach R16, wenn das Gespräch dazu noch aussteht".
Fest steht bereits (R60): ab v1.0 keine Rückwärtskompatibilität, auch
nicht bei Updates; v1.0 beginnt mit dem Neuaufsetzen (R40); eine ältere
Sicherung wird genau einmal über ein Wegwerf-Formular eingespielt.

### 2.2 Zwei Berichtigungen am Rahmenplan

**A — Die Store-Verteilung fehlt.** Abschnitt 1 nennt „Store-Verteilung der
Clients vor v1.0 (Betriebsübergang, R41)" als Nicht-Ziel; der Betriebsübergang
führt „Verteilung der Clients über Connect-IQ-Store und Play Store (setzt
Mengenbremse und Mengengrenze aus P5 voraus, E-R45-6)". Die Befunde vom
01.09.2026 (4.1) sind in keiner Fassung eingetragen. → F-PV-7.

**B — Nr. 83 steht zweimal, widersprüchlich.** Der Schritt-10-Block trägt
einen Absatz „Dazu ein Punkt, der genau hierhin gehört … die Haltbarkeit
der Gerätestatistik (Backlog Nr. 83) … nennt drei Wege und ihre Kosten; er
ist vor dem Schnitt zu entscheiden". Abschnitt 5 und R64 führen Nr. 83 als
**entschieden am 02.09.2026** (Weg b, Umsetzung im S4-Rest). Der Absatz
ist ein Rest aus Fassung 21. → Einfügeblock 6.1.1, keine Entscheidung nötig.

### 2.3 Wie heute ausgeliefert wird (Ist-Stand, für F-PV-1, F-PV-3, F-PV-7)

- **Web:** `deploy.yml` synchronisiert bei jedem Push auf `main` mit
  Änderungen unter `server/**` das Verzeichnis per FTPS auf den
  Produktivserver (Aktion `SamKirkland/FTP-Deploy-Action`, Ziel
  `./httpdocs/`); Ausnahmen `config.php`, `install.lock`, `sicherungen/`,
  `apk/`. Kein Staging, kein Prüftor, kein Release-Tag, kein Rollback-Weg.
  Nach einer Schemaänderung ruft eine Administratorin `update.php` von Hand
  auf; die Migrationsliste steht auf dieser Wartungsseite (Nr. 77). Ohne
  erhöhte `WEB_VERSION` sieht der Browser alte Dateien.
- **Garmin-Uhr:** `watch/`, Zählung in `Const.mc`, Auslieferung als `.prg`
  per Seitenladung auf eine Uhr; Connect-IQ-Store nach R41 Betriebsübergang.
- **Android:** `android/`, **eine** Zählung für Handy- und Uhr-Modul
  (E-S4-02, `version.properties`), Versionscode gerechnet
  `Haupt·10000 + Neben·100 + Korrektur` — **für beide Module derselbe**;
  Anwendungs-ID `org.genem.nadoku`, `minSdk 26`, `targetSdk 36`; beide APK
  mit demselben RSA-4096-Schlüssel signiert (Zertifikat `078c…ad64`, gültig
  bis 2056, E-S4-01/E-S4-27), Schlüssel am 02.09.2026 an den Auftraggeber
  übergeben. Verteilung: signiertes APK per FTPS nach `server/apk/`,
  Download-Seite nur angemeldet, SHA-256 (E-S4-16, Web 12.8.0). Die App ist
  seit 0.7.4 **store-fähig gebaut** (E-S4-52; B-S4-04: Akku-Freistellung
  ohne `REQUEST_IGNORE_BATTERY_OPTIMIZATIONS`). CI-Prüftor nach R40.4 soll
  ab P5 den unsignierten Baulauf enthalten; signiert wird außerhalb der CI
  (E-S4-16).
- **Prüfmittel** sind skriptbar (Kreisläufe R24, Wortliste R28, Messstand
  R35, Vollständigkeit, Bilderlauf, Android `gradlew build`), laufen aber
  heute nirgends automatisch.
- **Drei Zählungen, drei Auslieferungen, ein Repositorium** (`CLAUDE.md` 2).

---

## 3. Die Fragen

### 3.0 Reihenfolge und Begründung

| Reihe | Frage | Warum an dieser Stelle |
|---|---|---|
| 1 | **F-PV-7** Store-Verteilung | einzige Frage mit **externem Vorlauf** (D-U-N-S bis zu vier Wochen, Kontoprüfung, Wear-OS-Prüfrunde); ändert Abschnitt 1; F-PV-3 baut darauf auf (APK-Bau im Prüftor, E-R45-9) |
| 2 | **F-PV-1** Update-Weg (R60) | **blockiert S8** (Schritt 7): Nr. 77 „Ort der Migrationsliste hängt an R60" |
| 3 | **F-PV-3** Auslieferungskette (R40) | braucht die Hosting-Entscheidung (Staging-Ziel) — die ist vor Schritt 9 fällig; die Kette selbst wird in P5 aufgesetzt |
| 4 | **F-PV-2** Repositorien | bestimmt Vertrag, Versionszählung, Backlog-Übernahme und die Kette aus F-PV-3 endgültig |
| 5 | **F-PV-4** Code-Review (R17) | Umfang wächst mit P5; Form und Reihenfolge lassen sich jetzt festlegen |
| 6 | **F-PV-5** Web-App auf Android (Nr. 87) | Grundsatzfrage ohne Abhängigkeit; ein Nein erspart die Erhebung |
| 7 | **F-PV-6** Doku-Anforderungen (R16) | Anforderungsgespräch; Screenshots setzen die finale Oberfläche voraus (P6), die Anforderungen nicht |

### F-PV-1 — Update-Weg der Installation ab v1.0 (R60)

**Zu entscheiden (aus R60 wörtlich):** (a) Prüft die Installation selbst
gegen das Repositorium und meldet eine neue Fassung? (b) Spielt sie das
Update selbst ein, oder bleibt es beim Hochladen per FTP? (c) Muss die
Migrationsliste auf der Wartungsseite sichtbar bleiben?
**Schon fest:** keine Rückwärtskompatibilität ab v1.0; Neuaufsetzen am
Schnitt; Wartungsmodus-Torwächter (R40.4, P5). **Berührt:** Nr. 77 (S8),
Zusage „keine fremde Quelle zur Laufzeit" (`CLAUDE.md` 4 — eine
Selbstprüfung gegen GitHub ist ein Netzaufruf des Servers, nicht des
Browsers; ist zu bewerten), Selbsthoster (R63 setzt sie voraus).
**Ausarbeitung in Abschnitt 4b** (in Klärung).

### F-PV-2 — Ein Repositorium oder mehrere

**Zu entscheiden:** ob Web/Server, Garmin-Uhr, Android und Werkzeuge nach
dem Schnitt getrennt oder gemeinsam weiterleben; Folgen für
`docs/JSON-Vertrag.md` (wo liegt der Vertrag, wenn die Clients eigene
Repositorien haben), für die drei Zählungen, für die Auslieferung je
Repositorium und für die Übernahme des Backlogs (dauerhafte Nummern über
mehrere Repositorien). **Schon fest:** „frische Repositorien" (Abschnitt 1),
neuer Name (R29/R48, Umbenennung P6). **Ausarbeitung in Abschnitt 4d** (in Klärung).

### F-PV-3 — Auslieferungskette nach R40

**Zu entscheiden:** Staging-Ziel und -Zugang; `main` → Staging,
Release-Tag → Produktion; CI-Prüftor (welche Prüfmittel, in welcher
Reihenfolge, was bricht den Lauf); Rollback-Weg bei nicht rückwärtsfähigen
Migrationen; Ort des unsignierten APK-Baulaufs (E-S4-16) und des
Uhr-Baus; Umgang mit `update.php` in der Kette. **Schon fest:** R40 (1)–(4),
Torwächter in P5. **Zuarbeit:** Hosting-Entscheidung (R36), Staging-Installation
(Abschnitt 6 des Rahmenplans). **Ausarbeitung in Abschnitt 4c** (in Klärung).

### F-PV-4 — Bug- und Sicherheitsreview (R17)

**Zu entscheiden:** Umfang (R17 nennt Verschlüsselungsverfahren; Schritt 11
ergänzt Containerfassung 4, SPUR1, Komplettbackup und Serverschlüssel,
Demo-Konstruktion, Schlüsselablage auf dem Handy, S5-Kopplungsweg, Dumps
und Klartext-Koordinaten — Nr. 43 Weg B), Reihenfolge, Form (Befundliste
mit Schweregrad? Sofortbehebung oder Sammlung nach K4?), was vor und was
nach dem Neuaufsetzen läuft, wie Funde entschieden werden. **Abhängigkeit:**
die drei Fragen aus `Konzept-V1-Ortsdaten.md` (Nr. 43) sind laut Abschnitt 6
**vor** dem Review zu beantworten — nicht Umfang hier, aber Vorbedingung.
**Ausarbeitung in Abschnitt 4e** (in Klärung).

### F-PV-5 — Weboberfläche als installierbare Web-App auf Android (Nr. 87)

**Zu entscheiden:** ob überhaupt (es gibt seit S4 eine native App; die
Weboberfläche ist die Verwaltung, nicht die Erfassung — E-R45-1), und wenn
ja, mit welchem Umfang (Manifest und Symbole nur; Service Worker ja/nein —
ein Service Worker ist ein Cache und berührt „ohne erhöhte `WEB_VERSION`
sieht der Browser alte Dateien"). **Stand:** kein Manifest, kein Service
Worker. **Ausarbeitung in Abschnitt 4f** (in Klärung).

### F-PV-6 — Anforderungen an die Doku-Neufassung (R16)

**Zu entscheiden:** Umfang (Handbuch, README, Technik; Betreiberhandbuch
und Notfallplan aus R41), Form (Screenshots, klickbare Kapitel, ein
Dokument oder mehrere), Zielgruppen (NutzerIn, Admin, Betreiberin,
Selbsthoster), Erzeugungsweg (Screenshots aus `tools/screenshots/`?),
Sprache und Wortliste. **Schon fest:** R16 verortet die Screenshots in P6.
**Ausarbeitung in Abschnitt 4h** (in Klärung).

### F-PV-7 — Store-Verteilung der Clients

**Ausarbeitung in Abschnitt 4** (in Klärung).

---

## 4. Ausarbeitung F-PV-7 — Store-Verteilung der Clients

### 4.1 Befund

**Was gilt (Rahmenplan und Archiv):**

- **E-R45-6 (31.08.2026):** Verteilung ohne Store — signiertes APK aus der
  Web-App, für einen bekannten Kreis. Play Store ist Betriebsübergang nach
  v1.0, aus **drei** Gründen: (1) wie die Connect-IQ-Verteilung (R41) eine
  Frage des Betriebsübergangs; (2) für neue Konten 12 Tester über 14 Tage vor
  der Freigabe; (3) **Sicherheitsargument:** ein öffentlich verteilter Client
  mit Geräteschlüssel ohne Mengenbremse (R19) und Mengengrenze je Konto
  (R37.10) ist die Flutungsgefahr aus P5. Grund (3) ist der tragende.
- **R41:** MDR-Abgrenzung „einmal prüfen und festhalten" (reine
  Dokumentation, vermutlich unkritisch) — Zuarbeit, abschließende Prüfung in
  P6, vor der Öffnung. Recht und Betreiberunterlagen vor der Öffnung.
- **Konzept S4, Abschnitt 8:** Store-Verteilung ausdrücklich nicht Umfang;
  **E-S4-52:** App trotzdem store-fähig gebaut.

**Was seit dem 01.09.2026 bekannt ist und im Rahmenplan fehlt:**

1. **Wear OS hat ohne Store keinen zumutbaren Installationsweg.** ADB über
   WLAN ist ein Entwicklerweg (E-R45-7), kein NutzerInnen-Weg. E-R45-6 hat
   Handy und Uhr nicht unterschieden.
2. **Entwicklerverifizierung.** Ab 30.09.2026 müssen in Brasilien,
   Indonesien, Singapur und Thailand Apps auf zertifizierten Android-Geräten
   von verifizierten Entwicklern stammen — **auch bei Seitenladung**; ab
   **2027 weltweit**, damit im Zielmarkt (R36, deutschsprachig). Registriert
   werden Paketname und Signaturzertifikat. Kontotypen außerhalb von Play:
   „Limited Distribution" (kostenlos, ohne Ausweisprüfung, **20 Geräte**) —
   für bis zu 1 000 Konten unbrauchbar; „Full Distribution" (25 USD). Ein
   Play-Console-Konto deckt beides ab. Folge: **Die Seitenladung braucht ab
   2027 ohnehin ein Konto** — die Frage ist nicht ob, sondern welches und wann.
3. **Die 12-Tester-Pflicht trifft nur persönliche Konten** (erstellt nach dem
   13.11.2023). Organisationskonten sind ausgenommen; sie brauchen eine
   **D-U-N-S-Nummer** (kostenlos, Beschaffung bis zu vier Wochen).
4. **Interner Test-Track:** bis 100 Tester per Einladung, sofort verfügbar,
   ohne Produktionsfreigabe, nicht im Store sichtbar — das ist „ein bekannter
   Kreis" im Sinn von E-R45-6, ohne die Flutungsgefahr aus Grund (3).
5. **Uhr und Handy** unter demselben Paketnamen und Eintrag (so ist die App
   gebaut, E-S4-01), Wear OS als eigener Formfaktor und eigener Track;
   Google prüft den Wear-OS-Teil gegen seine Qualitätsrichtlinien — mit einer
   Ablehnungsrunde ist zu rechnen. **Der Versionscode muss je APK eindeutig
   sein** — die eine gerechnete Zahl für beide Module (E-S4-02, 2.3)
   **kollidiert damit**.
6. **Vor dem ersten Release fällig:** Deklaration des Vordergrunddienstes
   (Standort) mit Demo-Video; Datensicherheitsformular (Standortdaten, ggf.
   Gesundheitsbezug); Play App Signing — der Signaturweg muss zum
   Seitenladungs-APK passen; MDR-Frage vor einem **öffentlichen** Eintrag.

### 4.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026** (Antwort des Auftraggebers je Buchstabe):
> (a) Organisation, Träger ist die **Gen-EM GbR** · (b) wie empfohlen,
> interner Track zuerst · (c) Versatz · (d) hochladen · (e) **entfällt mit
> der Veröffentlichung im Play Store** — eingetragen als Produktionsfreigabe,
> Welle 1 (nachentschieden nach Rückfrage) · (f) ja ·
> (g) ja. → **E-PV-1** (Abschnitt 5), Einfügeblöcke 6.2.7.

**(a) Kontoart und Träger.**
Optionen: **Organisation** (D-U-N-S nötig; keine 12-Tester-Pflicht; das
Konto trägt eine Rechtsperson) · **Person** (sofort; vor der
Produktionsfreigabe 12 Tester über 14 Tage; für den internen Track ohne
Folge; späterer Wechsel = App-Übertragung zwischen Konten).
*Empfehlung:* Organisation, D-U-N-S **sofort** anstoßen — die
Produktionsfreigabe kommt spätestens mit Welle 1, und das Konto ist die
Identität der App gegenüber Google.
*Offen, vom Auftraggeber:* **Welche Rechtsperson trägt das Konto** (Gen-EM in
welcher Rechtsform; Sitz; wer ist Kontoinhaber)? Ohne diese Angabe ist kein
D-U-N-S-Antrag möglich. Ich nehme dazu nichts an.

**(b) Stufe und Zeitpunkt.**
- *Option 1 — Plan bleibt.* Store nach v1.0, bis dahin Seitenladung. Nur die
  Verifizierungsregistrierung wird vorgezogen (Konto ohnehin nötig, Punkt 2).
  Preis: Wear OS bleibt bis nach v1.0 ohne NutzerInnen-Weg; die Uhr-App wird
  vor der Öffnung von niemandem außer dem Auftraggeber benutzt.
- *Option 2 — interner Track ab Schritt 6.* Mit Android 1.0.0 (S4-Rest) wird
  der interne Test-Track der Regelweg für den bekannten Kreis, Handy und Uhr;
  die Seitenladung bleibt als Rückfall (siehe (e)). Produktionsfreigabe
  bleibt Betriebsübergang, **Welle 1** (R41). Grund (3) aus E-R45-6 bleibt
  gewahrt: Der interne Track ist nicht öffentlich.
  Preis: Play-Console-Pflichten fallen früher an (Konto, Deklarationen,
  Datensicherheitsformular; welche davon der interne Track schon verlangt,
  ist **beim Einrichten zu prüfen**, nicht hier festzulegen); die
  Wear-OS-Prüfrunde kommt vor den Gerätetest, oder der Gerätetest auf einer
  echten Uhr kommt vorher (E-R45-7: keine Uhr vorhanden).
- *Option 3 — geschlossener Test oder Produktion vor v1.0.* Verworfen: Ein
  Store-Eintrag ist eine Öffnung; R41 stellt Recht und Unterlagen davor, und
  die Mengenbremse kommt erst mit P5.
*Empfehlung:* Option 2.

**(c) Versionscode-Schema.**
E-S4-02 (eine Zählung, ein Versionsname) bleibt; **der gerechnete Code
bekommt je Modul einen Versatz**, damit Handy- und Uhr-APK unter einem
Paketnamen eindeutig sind — etwa Uhr `+ 1 000 000` oder eine führende
Formfaktor-Ziffer; das Schema legt die Umsetzung in Schritt 6 fest
(`android/build.gradle.kts`, `version.properties`-Kopf, `LIESMICH.md`).
Preis: ein einmaliger Versionscode-Sprung; auf der einen gekoppelten
Uhr eine Neuinstallation. Ohne diese Entscheidung ist kein Wear-OS-Release
möglich.
*Empfehlung:* Versatz, jetzt beschließen, in Schritt 6 umsetzen.

**(d) Signaturweg.**
Play App Signing ist für neue Apps Pflicht (App Bundle). Optionen:
**vorhandenen Schlüssel als App-Signaturschlüssel hochladen** (Zertifikat
`078c…ad64` bleibt für Play-Installation und Seitenladung dasselbe; die
Verifizierungsregistrierung nennt dasselbe Zertifikat; dazu ein getrennter
Upload-Schlüssel) · **Google erzeugt den Schlüssel** (dann sind Play-APK und
Seitenladungs-APK für Android zwei verschiedene Apps; ein Wechsel zwischen
den Wegen kostet die Neuinstallation, und der Data Layer verlangt gleiche
Signatur beider Module — ein Mischbetrieb bräche).
*Empfehlung:* vorhandenen Schlüssel hochladen. Folge für das
Bedrohungsmodell (S2/AP10) und den R17-Review: **Der Signaturschlüssel liegt
danach auch bei Google** — festhalten, nicht verschweigen.

**(e) Seitenladung und Download-Seite (Web 12.8.0, `apk.php`).**
*Was gemeint ist (Rückfrage vom 03.09.2026):* die Karte **„NAdoku für
Android"** unter der Geräteliste in *Einstellungen → Geräte*
(Handbuch 10.1), seit Web 12.8.0. Sie zeigt Dateiname, Größe, Fassung, Stand
und SHA-256 der APK-Datei, die die Betreiberin per FTPS nach `server/apk/`
legt; `apk.php` liefert die Datei aus (E-S4-16). Das ist heute der einzige
Weg, die Handy-App zu bekommen — Android fragt beim Öffnen nach der
Freigabe „aus dieser Quelle" (Seitenladung). Sobald der interne Track der
Regelweg ist (b), ist diese Karte ein zweiter Weg zur selben App; die Frage
ist, ob und wie lange er bleibt.
Optionen: bleibt dauerhaft als Rückfall · entfällt mit der
Produktionsfreigabe · entfällt mit v1.0.
*Empfehlung:* bleibt **bis zur Produktionsfreigabe** (Rückfall für den
Übergang, Weg für Geräte ohne Play-Zugang); ob sie danach bleibt,
entscheidet die Welle-1-Planung. Ab 2027 ist sie durch (d) verifiziert.

**(f) Vorbedingungen und Zuarbeiten** — als Zeilen für Rahmenplan Abschnitt 6:
D-U-N-S-Nummer beantragen (**sofort**) · Play-Console-Organisationskonto
(25 USD, nach D-U-N-S) · Signaturschlüssel hochladen und Upload-Schlüssel
erzeugen (Schritt 6, mit dem ersten Track-Release) · Deklaration
Vordergrunddienst/Standort mit **Demo-Video auf echtem Gerät** (Schritt 6;
wer dreht es?) · Datensicherheitsformular — setzt die Datenschutzerklärung
voraus, die Abschnitt 6 schon als Zuarbeit führt (Gerätekennung) ·
**MDR-Abgrenzung (R41) vor der Produktionsfreigabe**, nicht erst in P6 —
für den internen Track nach heutiger Einschätzung nicht nötig, zu prüfen ·
Wear-OS-Uhr für Gerätetest und Prüfrunde.

**(g) Garmin / Connect IQ.** Unverändert nach R41: Betriebsübergang nach
v1.0. Es gibt dort keinen Verifizierungszwang und keinen internen Track;
der Bestand ist eine Uhr. *Nur zu bestätigen.*

### 4.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.7)

- Abschnitt 1, Nicht-Ziele: „Store-Verteilung vor v1.0" wird zu
  „**Produktionsfreigabe** in den Stores vor v1.0"; interner Track vor v1.0
  ist Ziel.
- Fahrplan Schritt 6 (S4-Rest): Play-Console-Einrichtung, Versionscode-Versatz,
  Signaturweg, erstes Track-Release für Handy und Uhr.
- Betriebsübergang-Zeile: „Produktionsfreigabe im Play Store (Welle 1) und
  Connect-IQ-Store".
- Abschnitt 6: die Zuarbeiten aus (f).
- Abschnitt 7: **R65** (nächste freie Nummer nach Fassung 24), Kern und
  Status; Volltext mit Begründung in diesem Konzept (Abschnitt 5), nach R62
  nach Abschluss in Abschnitt 8 verwiesen.
- Abschnitt 10: Zeile Fassung 26.
- `docs/Backlog.md`: ein Punkt „Versionscode-Versatz Uhr-Modul" (nächste
  freie Nummer laut Backlog-Kopf; Stand Fassung 24 ist 89 vergeben),
  Zuordnung S4-Rest.
- `docs/konzepte/Konzept-S4-Handy-Uhr-Client.md`: Abschnitt 8 (Nicht Umfang)
  und Abschnitt 13 (Schritt-6-Inhalt) Nachtrag; B-S4-04 verweist auf die
  Entscheidung.
- **Nicht** anfassen: Rahmenplan-Archiv (wird nicht fortgeschrieben, R51);
  E-R45-6 bleibt, wie sie war — R65 ersetzt sie, das steht im Register.

---

## 4b. Ausarbeitung F-PV-1 — Update-Weg der Installation ab v1.0 (R60)

### 4b.1 Befund

- **Heute:** Push auf `main` → FTPS-Sync (2.3) → Administratorin ruft
  `update.php` auf; die Seite trägt Migrationsliste, Job-Einstieg (Cron,
  Token), Speichergrenze (Nr. 77, S8). `update.php` führt jede Kennung genau
  einmal aus; das Register steht in `schema.sql` **und** `update.php`
  (Gegenzählen beim Merge, Abschnitt 2.2). Stand: 40 Migrationen.
- **Beschlossen:** R40 (4): ab dem neuen Repositorium `main` → Staging,
  Release-Tag → Produktion, CI-Prüftor, Rollback-Weg; **Torwächter** (P5):
  Anwendung erkennt ausstehende Migration und zeigt eine Wartungsseite statt
  Fehler. R60: keine Rückwärtskompatibilität ab v1.0, Neuaufsetzen am
  Schnitt, Alt-Backup einmal über Wegwerf-Formular.
- **Wer ist „die Installation"?** Nach R36 **eine** öffentliche Installation,
  betrieben von Gen-EM — Betreiberin und Entwicklung sind dieselben Personen.
  Dazu **Selbsthoster** (R63 setzt sie voraus: eigenes APK; die Garmin-Uhr
  behält die Adresseinstellung). Für die eigene Installation ist eine
  Selbstprüfung „gibt es eine neue Fassung?" wertlos — wer deployt, weiß es;
  sie hätte nur für Selbsthoster Sinn.
- **Zusagen, die berührt werden:** „keine fremde Quelle zur Laufzeit"
  (`CLAUDE.md` 4 — gemeint ist der Browser; ein Serveraufruf an GitHub ist
  formal etwas anderes, verrät aber die Existenz und Adresse jeder
  Installation an einen Dritten) und „keine Telemetrie" (R36 — in der
  Gegenrichtung: die Installation meldet sich nirgends). Ein
  **Selbst-Update** (PHP lädt ein Archiv und entpackt es über sich selbst)
  braucht Schreibrecht auf den eigenen Code, ist auf geteiltem Webspace (Z2)
  nicht atomar und wäre ein webseitig auslösbarer Code-Download — eine
  Angriffsfläche, die der R17-Review sonst nicht hat.
- **Bei v1.0 beginnt das Migrationsregister neu:** Die frische Installation
  entsteht aus `schema.sql` (R40 (3)); die 40 Alt-Migrationen gehören dort in
  die Grundfassung, nicht in die Liste. Das ist eine Folge von R60, die P6
  ausführt — hier nur festgehalten.

### 4b.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) **A1** · (b) **B1, aber nicht
> automatisch** — Produktion nur auf Auslösung der Betreiberin, damit vorher
> Zeit bleibt, Backups zu prüfen oder anzustoßen (nach Erläuterung des
> Unterschieds B1/B2) · (c) **C3** (nach Erläuterung). → **E-PV-2**
> (Abschnitt 5), Einfügeblöcke 6.2.1. Die Mechanik der Handauslösung ist
> Gegenstand von F-PV-3.

**(a) Selbstprüfung gegen das Repositorium mit Meldung?**
- *A1 — keine.* Die Wartungsseite zeigt laufende Fassung und Datum des
  letzten Einspielens; neue Fassungen stehen als GitHub-Release mit
  Changelog; Selbsthoster abonnieren das Repositorium („Watch → Releases").
  Kein Aufruf nach außen, nichts zu betreiben. Preis: nichts Automatisches.
- *A2 — nur auf Klick.* Knopf „Auf neue Fassung prüfen" auf der
  Wartungsseite; fragt die GitHub-Releases-Schnittstelle **einmal, wenn die
  Administratorin klickt**; kein Hintergrundlauf, kein Banner. Preis: eine
  Netzabhängigkeit im Serverbetrieb, ein Endpunkt mehr im Review; für die
  eigene Installation ohne Nutzen.
- *A3 — automatisch.* Prüfung über den S2-Job-Einstieg, Banner im
  Adminbereich. Preis: regelmäßiger Aufruf eines Dritten aus jeder
  Installation — gegen den Geist von R36; Ausfall von GitHub wird zur
  Fehlerzeile der Wartung.
*Empfehlung:* **A1** für v1.0; A2 als Backlog-Punkt „nach v1.0, wenn
Selbsthoster es verlangen".

**(b) Einspielen: selbst oder per FTP?**
- *B1 — die Auslieferungskette ist der Update-Weg.* Für die eigene
  Installation: Release-Tag → CI → Deploy (R40 (4), Einzelheiten F-PV-3);
  FTP von Hand nur als Rückfall und Rollback. Für Selbsthoster:
  Release-Archiv herunterladen, per FTP/FTPS hochladen, `update.php`
  aufrufen — dokumentiert im Betreiberhandbuch (R16/R41). Die Installation
  **verändert ihren eigenen Code nie**.
- *B2 — Selbst-Update aus der Anwendung.* Verworfen aus den Gründen in 4b.1
  (Schreibrecht, nicht atomar, Angriffsfläche, doppelt zur Kette).
- *B3 — `git pull` auf dem Server.* Kein eigener Weg, sondern eine Variante
  der Kette, falls das Hosting SSH bietet (R36-Zuarbeit); entscheidet
  F-PV-3.
*Empfehlung:* **B1**.

**(c) Migrationsliste sichtbar?**
- *C1 — ja, auf einer eigenen Unterseite „Migrationen".* Register mit
  Zustand je Kennung (ausgeführt am / ausstehend) und der Handlung
  „Ausstehende ausführen"; der Torwächter (R40.4, P5) liest **dasselbe
  Register** und zeigt „ausstehend" den NutzerInnen als Wartungsseite. Das
  beantwortet Nr. 77 für S8: Die Wartungsseite wird in Serverbetrieb/Jobs,
  Backup und Migrationen geteilt, und der Ort der Migrationsliste ist die
  dritte Unterseite. Preis: keiner über S8 hinaus.
- *C2 — unsichtbar, automatisch beim ersten Aufruf nach dem Deploy.*
  Migration läuft in einer NutzerInnen-Anfrage unter dem Zeitbudget des
  Hosters (Z2), ohne Einsicht, ohne Entscheidung, ohne Rollback-Punkt.
  Verworfen für geteilten Webspace; mit SSH und Cron später denkbar, dann
  als Schritt der Kette (F-PV-3), nicht als Web-Aufruf.
- *C3 — nur Ausstehende sichtbar, Historie ins Audit-Protokoll (R38, P5).*
  Die Historie ist nach dem Neuaufsetzen kurz (4b.1, letzter Punkt); ob sie
  im Register oder im Audit steht, ist eine S8/P5-Detailfrage.
*Empfehlung:* **C1**; ob die ausgeführten Kennungen dauerhaft im Register
oder im Audit-Protokoll stehen, entscheidet das S8-Konzept (Nr. 77) — der
Torwächter braucht nur die ausstehenden.

### 4b.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.1)

R66 im Register (Kern: kein Selbstcheck, kein Selbst-Update, die Kette ist
der Update-Weg, Migrationsliste als eigene Unterseite, Register beginnt bei
v1.0 neu) · R60 im Register: „Update-Weg … wird in der Planung v1.0
entschieden" → „entschieden als R66" · Abschnitt 5, Nr. 77: „Ort der
Migrationsliste hängt an R60" → „eigene Unterseite (R66)" · Schritt 7 (S8),
Inhalt (2): „Ort der Migrationsliste" → „Unterseite Migrationen (R66)" ·
Schritt 11 (P6): Ergänzung „Migrationsregister beginnt neu; Alt-Migrationen
in `schema.sql`" · Backlog: bei A1 ein Punkt „Fassungsprüfung auf Klick
(A2), nach v1.0" · Schritt 10, Update-Weg-Absatz: kürzen auf den Verweis.

---

## 4c. Ausarbeitung F-PV-3 — Auslieferungskette nach R40

### 4c.1 Befund

- **Heute** (2.3): Push auf `main` → FTPS-Sync von `server/` auf Produktiv;
  kein Staging, kein Prüftor, kein Tag, kein Rollback-Weg; `update.php` von
  Hand. Die Prüfmittel laufen nur in der Arbeitssitzung der umsetzenden
  Instanz.
- **Beschlossen:** R40 (2) Autodeploy auf Produktiv endet mit P5-Beginn,
  `main` deployt dann nur auf Staging; R40 (4) neues Repositorium mit
  `main` → Staging, Release-Tag → Produktion, CI-Prüftor (Kreisläufe R24,
  Wortliste R28, Messstand R35 vor jedem Produktiv-Deploy), dokumentierter
  Rollback-Weg; Torwächter (P5). E-S4-16: unsignierter APK-Baulauf im
  Prüftor, Signatur außerhalb der CI. **E-PV-2:** Produktion **nur auf
  Auslösung der Betreiberin**, Migrationen sichtbar und von Hand (C3).
- **Rahmenbedingungen:** Das Repositorium ist **öffentlich** — GitHub
  bietet dafür kostenlos Umgebungen mit Pflichtfreigabe („required
  reviewers") und Wartezeit. Hosting: geteilter Webspace, FTPS; SSH und
  Cron offen (R36-Zuarbeit). Der **Komplett-Backup-Job** hat einen
  Token-Einstieg (S2/AP8, `jobs.php`), lief bis 12.9.2 nie (Nr. 89, seit
  12.9.4 behoben; Betriebsnachweis in Abschnitt 6 noch offen);
  Wiederherstellung über `wiederherstellen.php` (S2).
- **Was die Prüfmittel brauchen:**

| Prüfmittel | Braucht | In der CI |
|---|---|---|
| Wortliste, Vollständigkeit, Kontraste, `php -l`, Backlog-Doppelungen, Migrationsregister gegenzählen | nur den Quellstand | statisch, überall |
| Android `gradlew build` (JUnit/Robolectric, Lint) | JDK, Android-SDK | ja, der Standard-Runner hat das SDK |
| Uhr-Prüfstand Stufe I | Connect-IQ-SDK über `CIQ_GERAETE_URL` (Secret) | zu prüfen, ob der Abruf ohne Anmeldung geht |
| Kreisläufe (csv, edbak), Bilderlauf, Messstand | eine **laufende Installation** mit Demo-Konto, Referenzdatensatz, 5 000-Einsätze-Konto; Bilderlauf dazu Chromium | **gegen Staging**, nicht im Runner — und die Prüfung gegen den echten Hoster (PHP-Grenzen, Zeitbudgets) ist genau der Wert, den R40 (1) am Autodeploy festhielt |

### 4c.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) ja · (b) **T1** — nach Klärung, dass
> die Freigabe per Push-Nachricht in der GitHub-App vom Handy geht; ganz
> ohne GitHub-Anmeldung nur mit einem Token auf dem Webspace, verworfen ·
> (c) **K2 „auf jeden Fall"** plus Checkliste im Betreiberhandbuch (= K3;
> „Runbook" erläutert) · (d) so, nach Erläuterung · (e) so; festgehalten in
> R67, `.github/workflows/`, Betreiberhandbuch und `docs/Technik.md` ·
> (f) **G1**; G2 nach v1.0 als Backlog-Punkt, weil der Upload-Schlüssel
> nach Play App Signing der zurücksetzbare ist. → **E-PV-3** (Abschnitt 5),
> Einfügeblöcke 6.2.3. Dazu die Frage nach einem **Wartungsmodus**
> (Abschnitt 7).

**(a) Staging.** Wie R40 (2): jeder Push auf `main` deployt **automatisch**
auf Staging — zweite Installation mit eigener `config.php` und Datenbank
(Zuarbeit, Abschnitt 6), ohne Echtbestand; dort liegen dauerhaft Demo-Konto,
Referenzdatensatz und Messstand-Konto. Staging ist damit **die
Prüfumgebung** für alles, was eine laufende Installation braucht.
*Nur zu bestätigen* — Staging trägt keine Echtdaten, ein Automatismus
schadet dort nichts.

**(b) Auslösung des Produktiv-Deploys.**
- *T1 — Freigabe-Tor.* Ein Release-Tag startet den Lauf; der wartet in der
  GitHub-Umgebung „produktion" auf den Freigabeklick der Betreiberin (bis zu
  30 Tage); optional eine Mindestwartezeit. Vorher passiert nichts auf dem
  Server.
- *T2 — Start von Hand.* Ein Tag allein deployt nichts; die Betreiberin
  startet den Lauf „Produktiv-Deploy" selbst (Aktionen-Tab oder GitHub-App
  auf dem Handy) und gibt den Tag an. Der Lauf verweigert, wenn der Tag
  keinen grünen Staging-Lauf hat.
*Empfehlung:* **T2** — das klarste Modell: „Ohne meinen Start passiert
nichts." T1 leistet dasselbe, kehrt aber die Vorgabe um (der Lauf wartet
auf dich).

**(c) Backup-Tor vor dem Deploy.**
- *K1 — Runbook.* Vor dem Start prüft die Betreiberin auf der Wartungsseite:
  letztes Komplett-Backup mit Zeitpunkt, Backup-Ziel erreichbar, ggf. „Jetzt
  sichern". Nichts automatisch.
- *K2 — die Kette sichert selbst.* Erster Schritt des Produktiv-Laufs ruft
  den Komplett-Backup-Job über den Token-Einstieg auf und **bricht ab, wenn
  der Job keinen Erfolg meldet**; erst dann FTPS. Das Backup landet wie
  immer auf dem Backup-Ziel (S2). Zu prüfen in der Umsetzung: ob der
  Job-Einstieg den Erfolg synchron meldet oder die Kette auf den
  Zeitstempel warten muss (Huckepack-Zeitbudget).
- *K3 — beides.* K2 im Lauf, K1 als Pflichtschritt davor im Runbook.
*Empfehlung:* **K3** — die Handauslösung gibt die Zeit zum Prüfen, K2
sorgt dafür, dass kein Deploy ohne frisches Backup durchgeht, auch wenn die
Prüfung vergessen wird.

**(d) Rollback.** Code: Produktiv-Lauf mit dem **vorigen Tag** (T2 kann
jeden Tag deployen). Datenbank: liefen Migrationen, Wiederherstellung des
Komplett-Backups aus (c) über `wiederherstellen.php`; der Torwächter zeigt
währenddessen Wartung. Geprobt einmal beim Neuaufsetzen (R40 (3)) und
danach mit der halbjährlichen Probe-Wiederherstellung (Betriebsübergang).
Steht im Betreiberhandbuch (R16/R41). *Empfehlung so.*

**(e) Prüftor in drei Stufen.**
- *Stufe 1 — jeder Push, jeder Zweig:* Wortliste 0/0/0, Vollständigkeit,
  Kontraste, `php -l`, Backlog-Doppelungen leer, Migrationsregister
  `schema.sql` = `update.php`, Android `gradlew build` (0 Lint-Fehler,
  0 Fehlschläge), Uhr Stufe I, wenn das SDK beschaffbar ist. **Rot = kein
  Merge auf `main`** (Zweigschutz).
- *Stufe 2 — nach dem Staging-Deploy, nur `main`:* Kreisläufe csv und edbak
  (0 unerklärt), Bilderlauf (Überlauf, Konsole, Knopfhöhen 0), Messstand
  **nur bei Tag-Läufen** (Dauer). **Rot = der Tag ist nicht freigabefähig.**
- *Stufe 3 — Produktion, Handauslösung (T2):* Tag grün? → Backup (K2) →
  FTPS → Betreiberin führt ausstehende Migrationen aus (C3) → Nachprüfung:
  Startseite antwortet, `WEB_VERSION` entspricht dem Tag.
„Prüfmittel laufen zuletzt" (`CLAUDE.md` 6) bleibt gewahrt: Die CI prüft
den Commit, wie er ist. *Empfehlung so.*

**(f) Android und Uhr in der Kette.** CI baut unsigniert und prüft
(E-S4-16). Signieren und Hochladen:
- *G1 — auf dem Rechner der Betreiberin.* Upload-Schlüssel dort, Anleitung
  in `android/LIESMICH.md`; das signierte App Bundle wird in der Play
  Console von Hand auf den internen Track gelegt. E-S4-16 bleibt unberührt.
- *G2 — Upload-Schlüssel als GitHub-Secret* plus Dienstkonto der Play-API;
  die Kette lädt selbst auf den internen Track. Preis: zwei Geheimnisse in
  der CI, E-S4-16 wäre zu ändern.
*Empfehlung:* **G1** bis v1.0 — passt zur Handauslösung, ändert keine
gefallene Entscheidung; G2 als Backlog-Punkt nach v1.0. Uhr: `.prg` wie
bisher von Hand; die CI baut nur (Stufe I).

### 4c.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.3)

R67 als Nachtrag zu R40 im Register (Staging automatisch und Prüfumgebung;
Produktion nur von Hand, mit Backup-Tor; Rollback; Prüftor in drei Stufen;
Android-Signatur außerhalb der CI) · R40 Status: „(4) präzisiert durch
R67" · Abschnitt 6, Staging-Zeile: „samt Demo-Konto, Referenzdatensatz und
Messstand-Konto" · Schritt 11: „Release-getriggerte Auslieferung mit
CI-Prüftor" → „Auslieferungskette nach R67" · Backlog: „Play-API-Upload aus
der Kette (G2), nach v1.0" · Zuarbeit: SSH/Cron-Frage aus R36 bleibt;
`CIQ_GERAETE_URL` als CI-Secret prüfen.

---

## 4d. Ausarbeitung F-PV-2 — Ein Repositorium oder mehrere

### 4d.1 Befund

- **Heute:** ein öffentliches Repositorium `gen-em/einsatzdoku-luftrettung`
  unter **AGPL-3.0** mit `server/`, `watch/`, `android/`, `tools/`, `docs/`,
  `.github/`. Drei Zählungen, drei Auslieferungen (`CLAUDE.md` 2); ein
  Changelog nach Keep a Changelog mit getrennten Web-/Uhr-Einträgen (R15:
  neu ab v1.0); **ein** Backlog mit dauerhaften Nummern über alle drei
  Produkte; **ein** JSON-Vertrag zwischen Server und beiden Clients (R12:
  Festschreibung als v1 in P6); Konzepte, die über Produkte hinweggehen
  (S5: fünf Pakete auf Server, Web, Uhr, Android — parallele Instanzen auf
  Zweigen **desselben** Repositoriums); Prüfwerkzeuge in `tools/`, die den
  Server über HTTP prüfen und dabei Geräte nachstellen (Kreisläufe,
  Geräteprobe, Kopplungsprobe, Bilderlauf).
- **Beschlossen:** Abschnitt 1 „frische Repositorien (ob eines oder
  mehrere, entscheidet die Planung vor v1.0, R59)"; Schritt 11 „neue
  Repositorien nach dem Schnitt"; neuer Produktname **Gen-EM NAdoku**
  (R48, R63); Selbsthoster bauen ein eigenes APK (R63) — sie brauchen
  den Quelltext; **E-PV-3:** Pflichtfreigaben gibt es im kostenlosen Plan
  nur für **öffentliche** Repositorien, und nur dort sind Actions-Minuten
  unbegrenzt (Stufe 2 mit Bilderlauf und Kreisläufen kostet Minuten).
- **Was „frisch" leistet:** einen ersten Commit ohne den alten Namen, ohne
  vierzig Migrationen (R66: Register beginnt neu), ohne die
  Sicherungs-Altformate (Nr. 46), ohne Konzeptreste — und den R17-Review
  als Eingang statt als Bruch in der Mitte einer Historie.

### 4d.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) **eines** · (b) öffentlich · (c)
> **`gen-em/nadoku`** · (d) frisch, ohne Historie · (e) passt — **dazu ein
> eigener P6-Punkt „Repo-Umzug und Inventur (Cleaning)"**, der bei v1.0
> prüft, was an Werkzeugen, Dokumenten und Workflows wirklich mitwandern
> muss · (f) ja · (g) ok. → **E-PV-4** (Abschnitt 5), Einfügeblöcke 6.2.2.

**(a) Anzahl.**
- *R1 — eines* (Aufbau wie heute, neuer Name). Vertrag, Backlog,
  Rahmenplan, Konzepte, Prüfwerkzeuge und Kette bleiben an einem Ort; eine
  Vertragsänderung ist **ein** Zweig mit Server- und Client-Paketen, wie
  S5 es vormacht; die Kette (R67) prüft Stufe 2 mit Werkzeugen aus
  demselben Stand, den sie deployt. Preis: Tags mit Präfix je Zählung
  (`web-v…`, `uhr-v…`, `android-v…`), Pfadfilter in den Workflows
  (`server/**` → Staging und Stufe 2; `android/**` → Gradle; `watch/**` →
  Stufe I), GitHub-Releases je Tag.
- *R2 — vier* (Web/Server, Uhr, Android, Werkzeuge). Saubere Zählung und
  Releases je Produkt. Preis: der Vertrag braucht einen Ort (Server-Repo
  oder ein fünftes), jede Vertragsänderung wird zu drei abgestimmten
  Pull-Requests; Backlog und Rahmenplan müssen in **einem** Repositorium
  liegen und die anderen darauf verweisen; ein Konzept wie S5 verteilt sich
  auf vier Zweige in vier Repositorien; die Werkzeuge prüfen den Server —
  getrennt von ihm sind sie schwerer zu halten als bei ihm.
- *R3 — zwei* (Server + Doku + Werkzeuge; Clients). Halbiert die Nachteile
  von R2, behält den geteilten Vertrag zwischen zwei Orten.
*Empfehlung:* **R1.** Die Gründe, die Abschnitt 1 für die Frage nannte
(Vertrag, Zählung, Auslieferung, Backlog), sprechen nach der
S5-Erfahrung und nach E-PV-3 alle für einen Ort.

**(b) Sichtbarkeit.** Öffentlich, AGPL-3.0 wie heute — Bedingung aus
E-PV-3, Voraussetzung für Selbsthoster (R63), unbegrenzte Actions-Minuten.
Geheimnisse bleiben, wo sie sind: nie im Repositorium (`config.php`,
Schlüssel, `wartung.lock`). *Nur zu bestätigen.*

**(c) Name.** Vorschlag **`gen-em/nadoku`** (Organisation `gen-em` besteht;
Produktname „Gen-EM NAdoku", R63). Alternativen: `gen-em/genem-nadoku`,
`gen-em/nadoku-luftrettung` (aber v1.0 ist „Land und Luft gleichrangig",
Abschnitt 1). *Empfehlung:* `gen-em/nadoku`.

**(d) Frisch — mit oder ohne Git-Historie?**
- *F1 — ohne.* Neues Repositorium, erster Commit ist der v1.0-Stand nach
  Review und Umbenennung; das alte Repositorium wird auf GitHub
  **archiviert** (schreibgeschützt, bleibt lesbar, README verweist auf das
  neue). Historie geht nicht verloren, sie bleibt dort. Preis: `git blame`
  und `git bisect` im neuen Repositorium beginnen bei v1.0.
- *F2 — mit.* Umbenennen oder spiegeln; dann steht der alte Name in jeder
  Commit-Nachricht und jedem Zweignamen — kein frisches Repositorium,
  Abschnitt 1 wäre zu ändern.
*Empfehlung:* **F1** — so steht es in Abschnitt 1, und die Entscheidungen
tragen Rahmenplan-Archiv und Changelog, nicht die Commit-Historie.

**(e) Was wandert.** `server/`, `watch/`, `android/`, `tools/`, `.github/`
(Kette nach R67), `CLAUDE.md`, `LICENSE`, `README.md`; `docs/`:
Rahmenplan, Rahmenplan-Archiv, Backlog (dauerhafte Nummern, R60/P6),
JSON-Vertrag v1 (R12), Handbuch und Technik in der Neufassung (R16),
Changelog neu ab v1.0 mit einem Verweis auf das archivierte Repositorium
(R15), laufende Konzepte. **Nicht:** `docs/konzepte/erledigt/` — die
Beschlüsse stehen im Archiv und in Abschnitt 8 des Rahmenplans, die
Konzepte bleiben im archivierten Repositorium lesbar. *Empfehlung so; (e)
ist die Stelle, an der du widersprechen kannst, wenn etwas mit soll.*

**(f) Zweigschutz und Arbeitsweise.** `main` geschützt: nur über
Pull-Request, Stufe 1 grün, keine direkten Pushes; Arbeitszweige wie heute
(K7); Tags nur auf `main`; Umgebungen `staging` und `produktion` (R67).
*Empfehlung so.*

**(g) Zeitpunkt.** Der Umzug ist der **letzte** Schritt von P6 — nach
Review, Umbenennung und Doku-Neufassung, zusammen mit dem Neuaufsetzen
(R40 (3)); die Kette entsteht in P5 im alten Repositorium (R40 (2)) und
zieht als `.github/` mit. Bis dahin alles hier. *Empfehlung so.*

### 4d.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.2)

R68 im Register · R59 Status: „Repositorien entschieden (R68)" ·
Abschnitt 1: „frischen Repositorien (ob eines oder mehrere, entscheidet
die Planung vor v1.0, R59)" → „einem frischen Repositorium (R68)" ·
Schritt 11: „neue Repositorien" → „neues Repositorium `gen-em/nadoku`,
Altrepositorium archiviert" · Abschnitt 6: Zuarbeit „Repositorium anlegen,
Umgebungen und Zweigschutz einrichten, Altrepositorium archivieren" (P6) ·
Schritt 10 Text: „Aufteilung in mehrere Repositorien" → „entschieden als
R68".

---

## 4e. Ausarbeitung F-PV-4 — Bug- und Sicherheitsreview (R17)

### 4e.1 Befund

- **R17 (Archiv):** vor dem Schnitt ein Bug- und Sicherheitsreview durch
  **Fable, hohe Denktiefe**, ausdrücklich einschließlich des
  Verschlüsselungsverfahrens (Verfahren, Schlüsselableitung, Umsetzung im
  Code); **Eingangsschritt von P6**, damit die P5-Ergebnisse (Registrierung,
  Rollen) mitgeprüft sind.
- **Was der Rahmenplan dem Review schon zuweist** (Schritt 11, R25, R41,
  E-R45-10, R49, Nr. 43, E-PV-1): Verschlüsselungsverfahren ·
  Containerfassung 4 · SPUR1 · Komplettbackup und Serverschlüssel ·
  Demo-Konstruktion (R25: darf in keiner Betriebsart entstehen, verschwinden
  oder Rechte erben) · Schlüsselablage auf dem Handy (Android Keystore) ·
  S5-Kopplungsweg und Adress-QR · Umgang mit Dumps, `sicherungen/` und
  Klartext-Koordinaten (Nr. 43: **Weg B — Schlüssel auf das Gerät —
  entscheidet der Review**) · neu aus E-PV-1: der Signaturschlüssel liegt
  nach Play App Signing auch bei Google · neu aus E-PV-3: Geheimnisse der
  Kette (Umgebungsgeheimnisse, Token-Einstieg des Backups).
- **Vorbedingung laut Abschnitt 6:** die drei Fragen aus
  `Konzept-V1-Ortsdaten.md` (Schutzbedarf der Spur; Passwortwechsel bei
  nicht synchronisierten Uhr-Daten; Stichtag oder rückwirkend) — **vor**
  dem Review. Nicht Umfang dieses Konzepts (Abschnitt 1), aber der Review
  kann ohne sie Weg B nicht entscheiden.
- **Es gibt kein Bedrohungsmodell als Dokument.** S5 hat es festgestellt
  (B-S5-02): Was R41 und R49 als „Bedrohungsmodell (S2/AP10)" fortschreiben
  wollen, liegt verteilt in `docs/Technik.md` 4.99 („Sicherheit", „Die
  Antwortzeit als Auskunft"), 4.97c/d und in den Konzepten; S5 legt einen
  ersten eigenen Unterabschnitt an. Ein Review prüft **gegen** Schutzziele
  und Angreifer — die müssen vorher an einem Ort stehen.
- **Bestand, den der Review liest:** `server/` mit ~70 PHP-Dateien
  (`update.php` allein 2 900 Zeilen), `watch/` (Monkey C), `android/`
  (Kotlin, zwei Module), `docs/Technik.md` (5 000 Zeilen), 27
  Prüfwerkzeuge unter `tools/` (Angriffsproben für Markdown-Renderer,
  Container, Wiederherstellung, Kopplung, Ingest …), die Fehlerfund-Listen
  B-P1 … B-S5 in den Konzepten, das offene Backlog. Ein Review dieser
  Größe muss geschnitten werden, sonst liest es nichts zu Ende.
- **Wer liest:** R18 verortet Konzepte im Projektraum, Umsetzung in Claude
  Code. Der Review liest Code in großer Menge — das geht nur mit
  Repositoriumszugriff (Claude Code, Modell **Fable** nach R17).

### 4e.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) **U1 — alles, in Stücken**; Ziel
> sind Bugs, ungebrauchte Zeilen, Codeschnipsel und Karteileichen,
> Probleme jeder Art — **und ein Kommentardurchgang: keine Verweise mehr
> auf alte Konzepte, Beschlüsse oder Fassungen, der Code startet sauber
> als v1.0** · (b) **Z1** — alles auf einmal, so nah wie möglich an der
> Erklärung v1.0 (Eingang von P6, wie R17) · (c) ja · (d) **zwei Wege:
> kritisch → Sofortpaket, alles andere → Pflichtpaket in P6**, keine
> weiteren Kategorien · (e) ja · (f) ja. → **E-PV-5** (Abschnitt 5),
> Einfügeblöcke 6.2.4.

**(a) Umfang.**
- *U1 — alles.* Jede Datei der drei Codebasen und die Doku, einmal mit
  Fable gelesen. Preis: bei ~100 Quelldateien und 10 000 Zeilen Doku
  mehrere Sitzungen, und die Aufmerksamkeit ist am Ende dort dünn, wo sie
  am dichtesten sein müsste.
- *U2 — Themenreview.* Nur die zugewiesene Themenliste (Befund) in der
  Tiefe. Preis: alles, was nicht auf der Liste steht, bleibt ungelesen —
  ein Endpunkt ohne CSRF-Prüfung fiele durch.
- *U3 — Themenreview plus Endpunktdurchgang.* Die Themenliste in der Tiefe
  **und** jede über HTTP erreichbare Datei (rund 45 Einstiege) einmal
  gegen eine feste Checkliste: Anmeldung/Rolle, CSRF, Eingabeprüfung,
  Ausgabekodierung, Fehlerweg (was verrät eine Ausnahme), Ratenschutz,
  Antwortgleichheit, Datenbank nur mit gebundenen Parametern. Clients: die
  sicherheitsrelevanten Module vollständig (Uhr: `Storage "cred"`, Pair,
  Uploader; Android: Keystore, Data Layer, SQLite-Puffer mit Klartext-
  Koordinaten, Vordergrunddienst, Kopplung), der Rest nach Checkliste.
  **Bug-Anteil:** die offenen B-Funde aller Konzepte, das Backlog und die
  Prüfwerkzeuge — je Werkzeug die Frage „prüft es noch das, was der Code
  tut?"
*Empfehlung:* **U3.**

**(b) Zeitpunkt und Teilung.**
- *Z1 — ein Review als Eingang von P6* (R17 wörtlich).
- *Z2 — zwei Phasen.* **Phase 1 „Bestand und Kryptographie"** nach dem
  letzten S-Zwischenpaket, **vor P5**: alles, was heute fertig und stabil
  ist (Verfahren, Container 4, SPUR1, Komplettbackup, Serverschlüssel,
  Demo, Handy-Schlüssel, S5-Weg, Klartext-Koordinaten mit Weg B). **Phase
  2 „P5 und Delta"** als Eingang von P6: Registrierung, Rollen,
  Mengenbremse, Konto-Lebenszyklus, Audit, Kette, plus alles, was sich
  seit Phase 1 geändert hat (Diff). Gewinn: Was Phase 1 findet, kann das
  P5-Konzept noch berücksichtigen (Weg B verändert den Vertrag; ein
  Befund am Passwortwechsel trifft R37); der P6-Eingang wird kürzer; zwei
  Fable-Sitzungen statt einer sehr langen. Preis: Delta-Disziplin in
  Phase 2 (der Diff seit Phase 1 ist Pflichtlektüre).
*Empfehlung:* **Z2** — dieselbe Logik wie das Vorziehen von Schritt 10.
R17 bleibt erfüllt: Der P6-Eingang prüft die P5-Ergebnisse.

**(c) Bedrohungsmodell zuerst.** Erstes Ergebnis von Phase 1 ist ein
**eigener Abschnitt „Bedrohungsmodell"** in `docs/Technik.md` (oder eine
eigene Datei, entscheidet der Review): Schutzziele; Angreifer (Zugriff
auf Hoster/Datenbank/Protokolle; Netz; fremde NutzerIn; Finder eines
Geräts; die Betreiberin selbst); Zusagen und ihre benannten Grenzen (E2E;
Ausnahmen Demo, Klartext-Koordinaten, Signaturschlüssel bei Google;
Speicherdauern). Er sammelt die verteilten Stellen (4.99, 4.97c/d,
S5-Nachtrag, Vorstudie Nr. 43) ein und ist danach die Referenz für den
Review — und für VVT und TOMs aus R41. *Empfehlung ja.*

**(d) Form der Funde.** Ein Review-Dokument `docs/konzepte/Review-R17.md`
(Phase 1 und 2 darin): je Fund Nummer, **Schweregrad** (kritisch / hoch /
mittel / niedrig / Hinweis), Fundstelle, Wirkung, Empfehlung, geschätzter
Aufwand. **Der Review behebt nichts** (K4). Wege der Funde: *kritisch* →
Sofortpaket vor allem anderen (Muster `Pruefung-Sofortpaket-22.md`);
*hoch* → Pflichtpaket in P6 vor dem Schnitt; *mittel* → Backlog mit
Zuordnung P6 oder nach v1.0, entscheidet die Freigaberunde; *niedrig* und
*Hinweis* → Backlog nach v1.0. Kryptographische Änderungen sind
Fable-Schritte, alles andere Opus (K8). *Empfehlung so.*

**(e) Wer entscheidet.** Der Review empfiehlt, der Auftraggeber entscheidet
je Fund in **einer Freigaberunde je Phase** (kurz: Liste mit Empfehlung,
Antwort je Nummer wie hier); Entscheidungen als E-R17-n im
Review-Dokument; der Rahmenplan bekommt je Phase eine Zeile in Abschnitt
8 und eine R-Nummer für Weg B. *Empfehlung so.*

**(f) Vorbedingungen und Zuarbeiten.** Für Phase 1: die drei Fragen aus
Nr. 43 beantwortet (Abschnitt 6 — als eigenes kurzes Gespräch außerhalb
dieses Konzepts); S5 abgeschlossen und gemergt (der S5-Weg ist Prüfgegen-
stand); Repositoriumszugriff für eine Fable-Instanz. Für Phase 2: P5
gemergt; Kette steht (R67). *Empfehlung so.*

### 4e.3 Wirkung auf den Rahmenplan (entschieden → Einfügeblock 6.2.4)

Nachtrag zu R17 als **R69** (alles, in zwölf Stücken, Eingang von P6,
Bedrohungsmodell zuerst, Kommentardurchgang, zwei Wege für Funde,
Paketschnitt nach der Freigaberunde) · R17, R13, R31 Status · Schritt 11
Eingangsschritt und Reihenfolge · Schritt 10 Text und Statuszeile ·
Abschnitt 5, Nr. 43 · Abschnitt 6 (Nr. 43-Fragen spätestens mit P5;
Fable-Instanz) · `CLAUDE.md` Kommentarregel im neuen Repositorium. *Die
Zeile „Review Phase 1" aus dem Vorschlag Z2 entfällt.*

---

## 4f. Ausarbeitung F-PV-5 — Weboberfläche als installierbare Web-App auf Android (Nr. 87)

### 4f.1 Befund

- **Backlog Nr. 87** (02.09.2026, auf Anweisung des Auftraggebers): „vor
  v1.0 prüfen, was es braucht, damit Android die Seite aus dem Browser
  heraus als App auf dem Startbildschirm ablegt". Am Code: kein Manifest,
  kein Service Worker, kein `theme-color`; nur ein `apple-touch-icon` je
  Logo-Wahl (`db.php`). **„Beides nebeneinander ist gewollt"** — die
  Web-App zeigt die Oberfläche, die S4-App zeichnet auf (E-R45-1, E-R45-5).
  Entscheidung über **Umfang und Zeitpunkt** in Schritt 10. *Die
  Grundsatzfrage „ob überhaupt" aus 3.0 ist damit schon beantwortet — die
  Vorlage vom 03.09.2026 hatte das übersehen.*
- **Chrome auf Android verlangt seit Version 108 keinen Service Worker mehr
  für die Installation aus dem Menü** — geprüft in der aktuellen
  Chrome-Dokumentation, nicht aus dem Gedächtnis (Nr. 87 hatte genau das
  verlangt). Nötig bleibt ein Manifest über HTTPS mit `name`/`short_name`,
  `start_url`, `display: standalone`, Symbolen 192 und 512 px (maskierbares
  Symbol empfohlen). Chrome liefert für installierte Apps ohne eigenen
  Service Worker eine Standard-Offline-Seite.
- **Was ein Service Worker kostete:** einen Cache zwischen Server und
  Browser — genau die Stelle, an der „ohne erhöhte `WEB_VERSION` sieht der
  Browser alte Dateien" (2.3) zum Dauerzustand würde; dazu eine zweite
  Update-Logik im Client. Nutzen: Offline-Seite und Vorladen. Beides
  braucht die Verwaltung nicht — offline gibt es nichts zu verwalten.
- **Was sich ändert, wenn die Seite als Fenster läuft:** ein eigener Tab →
  eigener `sessionStorage` → einmal anmelden im Fenster (R44: ein Tab, ein
  Schlüssel — gilt unverändert, kein Sonderfall). Die CSP (Nr. 8) bleibt:
  Manifest und Symbole sind eigene Quelle. Das Startbildschirm-Symbol ist
  fest (wie das Launcher-Symbol der Uhr, R47), nicht die Logo-Wahl je
  Profil — Vorgabe der Installation.
- **Verwechslung:** Auf dem Handy stünden dann zwei Symbole — die Handy-App
  „Gen-EM NAdoku" (R63) und das Web-Fenster. Gleiches Bild, ähnlicher Name
  → Fehlgriff. Name und Symbol der Web-App müssen den Unterschied tragen.
- **Umbenennung:** Name und Symbole im Manifest sollen den v1.0-Namen
  tragen; Web und Handbuch heißen bis P6 „Einsatzdoku" (Schritt 11). Ein
  Manifest vor P6 hieße „Einsatzdoku" und würde beim Umbenennen ein
  zweites Mal angefasst — installierte Startbildschirm-Einträge behalten
  den alten Namen, bis NutzerInnen neu installieren.

### 4f.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) **M1** — nach Erläuterung von
> Manifest und Service Worker; Firefox für Android, Samsung Internet und
> Safari (iPhone) als Nachweisziele aufgenommen · (b) **T2 — in P7**
> (nach dem Phasenschnitt aus F-PV-8) · (c) Symbolrichtung: gleicher
> Hubschrauber, verschiedene Hintergrundfarbe, kleine Marke rechts unten
> (GPS-Nadel beim Tracker, Browser-Marke bei der Web-App); **Entwurf und
> Wahl im P7-Konzept**, nicht hier. → **E-PV-6** (Abschnitt 5),
> Einfügeblöcke 6.2.5.

**(a) Umfang.**
- *M1 — Manifest allein.* `manifest.webmanifest` (Name, Kurzname,
  Startadresse, `standalone`, Farben, Symbole 192/512 px, maskierbar),
  `<link rel="manifest">` und `theme-color` in `ui.php`, Symbolsatz aus den
  vorhandenen Logos nach dem Rezept `tools/uhr-bilder/`; CSP-Freigabe für
  das Manifest; Handbuch-Abschnitt „Auf dem Startbildschirm ablegen".
  Kein Service Worker. Ein halber Tag.
- *M2 — Manifest plus Service Worker mit Offline-Seite.* Dazu eine eigene
  Offline-Seite („Keine Verbindung — die Verwaltung braucht das Netz") und
  ein Service Worker **ohne Cache** für Anwendungsdateien. Preis: eine
  Datei mehr im Review, die Versionierung muss den Worker bei jedem Deploy
  erneuern; Gewinn: eine eigene Offline-Seite statt der Standardseite von
  Chrome.
- *M3 — Manifest plus Service Worker mit Cache.* Verworfen: Cache gegen
  „keine alten Dateien".
*Empfehlung:* **M1.** M2 bringt nur die eigene Offline-Seite.

**(b) Zeitpunkt.**
- *T1 — Kleinpaket jetzt* (vor P6, etwa mit S8, das die Oberfläche ohnehin
  anfasst). Preis: heißt „Einsatzdoku", wird in P6 umbenannt, alte Einträge
  auf Startbildschirmen bleiben stehen.
- *T2 — in P6, mit der Umbenennung.* Manifest entsteht gleich mit Name und
  Symbolen von v1.0; ein Durchgang statt zwei. Preis: bis dahin nur der
  Browser-Tab — heute der Zustand, den alle kennen.
*Empfehlung:* **T2**, als kleines P6-Paket neben der Umbenennung.

**(c) Name und Symbol.** Vorschlag: Name **„NAdoku Web"**, Kurzname
„NAdoku Web"; Symbol das Standardlogo der Installation, **nicht** das
Launcher-Symbol der Handy-App (das trägt den Hubschrauber, R47/R63) —
damit die beiden Einträge auf dem Startbildschirm auseinanderzuhalten
sind. Alternativen: „NAdoku Verwaltung" (sagt, wofür), oder dasselbe
Symbol mit anderem Namen (spart die Symbolarbeit, riskiert den Fehlgriff).
*Empfehlung:* „NAdoku Web" mit eigenem Symbol — **Zuarbeit:** welches Bild
(Gen-EM-Vogel, Wortmarke, Hubschrauber in anderer Farbe?) entscheidet der
Auftraggeber; die Gestaltungsvorgaben liegen im Projekt.

### 4f.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.5)

Abschnitt 5, Nr. 87: „Backlog-Runde (Erhebung), Entscheidung in Schritt
10" → „P6 (Kleinpaket neben der Umbenennung, R70)"; die Erhebung entfällt
(hier erledigt) · Schritt 11: Paket „Web-App-Manifest (Nr. 87)" · Schritt
10 Text: „Web-App auf Android — entschieden als R70" · Abschnitt 6:
Zuarbeit „Symbol der Web-App" · Abschnitt 7: R70 · Abschnitt 10.

---

## 4g. F-PV-8 — Phasenschnitt vor v1.0 (nachgetragen 03.09.2026)

### 4g.1 Befund

Frage des Auftraggebers bei F-PV-5 (b): „Wird P6 nicht zu groß? Müssen wir
P7/P8 aufmachen?" Nach den Entscheidungen E-PV-1 bis E-PV-5 läge in P6:
Bedrohungsmodell und Review in zwölf Stücken, Freigaberunde, Sofortpaket,
alle Pflicht- und Aufräumpakete, eventuell Weg B mit Vertragsänderung,
Umbenennung überall, Vertrag v1, Doku-Neufassung mit Screenshots, Manifest,
Changelog, Backlog-Übernahme, Altformat abschaffen, Neuaufsetzen,
Repo-Umzug mit Inventur, Rechtsunterlagen. Das ist kein Konzept mehr, das
eine Instanz zu Ende liest, und kein Prüfdokument, das jemand abnimmt.

### 4g.2 Entscheidung (03.09.2026: „finde ich gut so")

Drei Phasen statt einer; der Paketschnitt je Phase entsteht im jeweiligen
Konzept nach K1:

| Phase | Name | Inhalt | Modell |
|---|---|---|---|
| **P6** | **Review und Bereinigung** | Bedrohungsmodell; Bug- und Sicherheitsreview in zwölf Stücken (R17, R69); Freigaberunde; Sofortpaket; Pflicht- und Aufräumpakete; Kommentardurchgang (R13, R31 gehen darin auf); Weg B (Nr. 43), falls beschlossen; R5-Ausnahmeliste. Ergebnis: sauberer Code, Verhalten unverändert außer bei Funden | Fable für Review und Kryptographie, sonst Opus |
| **P7** | **Gesicht v1.0** | Umbenennung überall (Web, Handbuch; neues Demo-Passwort mit dem Produktnamen in der Schwachwortliste, R25); Vertrag v1 festschreiben (R12, Nr. 23); Doku-Neufassung (R16, Anforderungen aus F-PV-6); Web-App-Manifest (Nr. 87, R70); Changelog neu ab v1.0 (R15); Backlog mit dauerhaften Nummern übernehmen; Altformat der Sicherung abschaffen (Nr. 46); Kommentarregel in `CLAUDE.md` (R69) | Opus |
| **P8** | **Schnitt** | Neuaufsetzen (R40 (3)); Migrationsregister neu (R66); Repo-Umzug und Inventur (R68); Kette im neuen Repositorium (R67, R40 (4)); Rechts- und Betreiberunterlagen (R41); Abnahme nach R11; Erklärung v1.0 | Opus |

Danach der Betriebsübergang (Welle 1, R65). → **E-PV-7**, Einfügeblöcke
6.2.8 — dort auch die Zuordnung aller bisherigen „P6"-Nennungen.

---

## 4h. Ausarbeitung F-PV-6 — Anforderungen an die Doku-Neufassung (R16)

### 4h.1 Befund

- **R16:** Doku-Neufassung zu v1.0 mit Screenshots und klickbaren Kapiteln;
  Anforderungsgespräch vorher — das ist dieses. **R41** verlangt dazu
  Betreiberhandbuch und Notfallplan (Zugänge zu Hoster, Domain, Mail,
  Repositorium; zweiter Admin; Wiederanlaufpaket aus S2), FAQ
  (Todesfall-/Erben-Grenze), `security.txt`. **R63:** Selbsthoster bauen ein
  eigenes APK — sie brauchen eine Installationsanleitung. **R67/R66:**
  Runbooks (Update mit Wartungsmodus, Rollback, Backup-Probe).
- **Bestand:** `Handbuch.md` (2 700 Zeilen, zwölf Kapitel: Uhr,
  Weboberfläche, Einsätze, Verschlüsselung, Backup, Import/Export,
  Papierkorb, Stammdaten, Geräte, Administration, Uhr einrichten) ·
  `Technik.md` (5 000 Zeilen: Architektur, Verzeichnisse, Datenmodell,
  Abläufe, Uhr, Android, Deployment, **Betrieb (Runbook)**, Admin-Backups) ·
  `JSON-Vertrag.md` (clientneutral) · `Geraete-Eingabe.md` (gerätefrei,
  Garmin und Wear OS als Zusätze, E-P2-02) · `Design.md`,
  `Uhr-Layout_Regeln.md`, `Backup-Format.md`, `Export-Format.md`,
  `Lizenzen.md` · `README.md` · `android/LIESMICH.md`, `tools/*/LIESMICH.md`
  · Rahmenplan, Archiv, Backlog, Changelog. Alles Markdown im
  Repositorium, von GitHub gerendert; Kapitelnummern als Text, keine
  Sprungmarken. Ob die Weboberfläche irgendwo auf das Handbuch verweist,
  ist zu prüfen (in `db.php`/`ui.php` kein „Hilfe"-Link gefunden).
- **Werkzeuge, die die Doku tragen können:** `tools/screenshots/`
  (Bilderlauf über `seiten.json`, acht Breiten, Demo-Konto) — Screenshots
  lassen sich **erzeugen statt pflegen**; `tools/wortliste/` (R28) prüft
  Begriffe; ein Ankerprüfer für Sprungmarken fehlt.
- **Was P2 festgelegt hat:** gerätefreie Texte mit Geräte-Zusätzen
  (E-P2-02), Begriffe nach Wortliste, Anrede Du im Handbuch.

### 4h.2 Teilfragen, Optionen, Empfehlung

> **Entschieden am 03.09.2026:** (a) **D2** · (b) **O2 für das Handbuch**
> — Rückfrage: „dynamisch von GitHub ins Tool einbinden?" → Antwort in
> 4h.4, Mechanik zu bestätigen · (c) Screenshots **1920×1080 und 414×896**;
> Uhr **drei repräsentative Darstellungen** aus dem Simulator; Handy aus
> dem Gerätetest · (d) ja — **wirklich kurz und prägnant** · (e) **keine
> Zugänge**; Betreiberhandbuch **generisch für selbst hostende Admins**,
> nicht auf die Gen-EM-Installation zugeschnitten; Rückfrage: „Was kommt
> in den Notfallplan?" → Antwort in 4h.5; **bestätigt: die Fälle kommen als
> FAQ ins Betreiberhandbuch** (kein eigenes Dokument), Betriebsakte-Vorlage
> dazu. Mechanik 4h.4 **bestätigt** („perfekt"). → **E-PV-8** (Abschnitt 5),
> Einfügeblöcke 6.2.6.

**(a) Dokumentenschnitt nach Zielgruppe.**
- *D1 — Dateien bleiben, Inhalt neu.* Handbuch und Technik werden
  umgeschrieben, sonst bleibt alles, wo es ist. Preis: Betrieb bleibt in
  Technik vergraben (Kapitel 7 von 9), Selbsthoster finden nichts, R41
  hat keinen Ort.
- *D2 — Schnitt nach Zielgruppe:* **Handbuch** (NutzerIn: Web, Uhr, Handy —
  Geräte-Eingabe geht darin auf) · **Betreiberhandbuch** (Administration,
  Betrieb, Runbooks, Notfallplan, Breach-Prozess, Statusseite — heute
  Handbuch 11 + Technik 7 + R41) · **Installation und Selbsthosting**
  (Server, eigenes APK, Uhr-Adresse, Wartungsmodus) · **Technik**
  (Architektur, Datenmodell, Abläufe, Sicherheit mit Bedrohungsmodell,
  Kette — für Entwicklung und Review) · **Vertrag** (unverändert eigenes
  Dokument, R12) · Anhänge: Backup-Format, Export-Format, Lizenzen;
  Design und Uhr-Layout-Regeln in Technik.
*Empfehlung:* **D2.**

**(b) Form und Ort.**
- *O1 — Markdown im Repositorium bleibt die Quelle,* GitHub rendert;
  „klickbare Kapitel" = Inhaltsverzeichnis mit Sprungmarken am Kopf jeder
  Datei, Querverweise als Links; in der Weboberfläche ein Link „Hilfe" auf
  die gerenderte Datei (das ist kein Laden fremder Quellen zur Laufzeit,
  nur ein Link).
- *O2 — dazu eine gerenderte HTML-Ausgabe* (Generator, eigene Seite oder
  im Web ausgeliefert). Preis: ein Werkzeug mehr in der Kette, eine
  Ablage mehr.
*Empfehlung:* **O1**; O2 nur, wenn die Öffnung zeigt, dass NutzerInnen
GitHub nicht finden.

**(c) Screenshots.** Automatisch aus `tools/screenshots/` erzeugt, mit dem
Demo-Konto (keine echten Daten), in **zwei Breiten** (360 mobil, 1440
Desktop), abgelegt unter `docs/bilder/` mit festen Dateinamen, die das
Handbuch einbindet; erneuert mit jedem Release in Stufe 2 der Kette
(R67), Abweichungen sichtbar im Diff. Uhr- und Handy-Bilder: Uhr aus dem
Simulator (`tools/uhr-bilder/`), Handy aus dem Gerätetest — von Hand, mit
Demo-Konto. *Empfehlung so.*

**(d) Stil und Umfang.** Aufgabenorientiert („Einen Einsatz nachbearbeiten")
mit kurzen Schritten, je Aufgabe ein Bild; Referenzteile (Felder, Formate,
Grenzen) als Anhang statt im Fließtext; Anrede Du im Handbuch, Sie
nirgends; Wortliste 0/0/0; **keine Fassungsgeschichte** im Text („seit Web
9.7.0") — die steht im Changelog (Muster wie der Kommentardurchgang, R69).
*Empfehlung so.*

**(e) Betreiberhandbuch und Notfallplan — Inhalt (R41):** Zugänge
(Hoster, Domain, Mail, Repositorium, Play Console, D-U-N-S), zweiter
Admin, Wiederanlaufpaket, Runbooks (Update mit Wartungsmodus, Rollback,
Backup-Probe halbjährlich, Freigabe der Kette), Breach-Prozess,
Statusseite, Support-Adresse, Speicherdauern; die Geheimnisse selbst
**nicht** im Dokument, nur wo sie liegen. *Empfehlung so — eigenes
Dokument, nicht Teil von Technik.*

**Nicht hier zu entscheiden:** was mit `Design.md`, `Uhr-Layout_Regeln.md`
und den Formatbeschreibungen geschieht (Inventur, R68/P8); ein Ankerprüfer
für Sprungmarken (Backlog-Kandidat für Stufe 1).

### 4h.3 Wirkung auf den Rahmenplan (nach Entscheidung → Einfügeblock 6.2.6)

Nachtrag zu R16 als **R72** (Zielgruppen-Schnitt, Markdown mit
Sprungmarken, erzeugte Screenshots, Stil) · R16 Status: „Anforderungen
R72; Umsetzung P7" · Abschnitt 6: Zeile „Anforderungsgespräch
Doku-Neufassung" → erledigt (R72); Zuarbeit „Inhalte für Betreiberhandbuch:
Zugänge, zweiter Admin" · Schritt 12 (P7): „Doku-Neufassung nach R72" ·
Schritt 10 Text: „Doku-Anforderungen — entschieden als R72".

### 4h.4 Antwort auf (b): Handbuch im Tool — von GitHub oder mit dem Release?

**Nicht von GitHub zur Laufzeit.** Drei Gründe, der erste ist der
entscheidende:

1. **Es zeigte die falsche Fassung.** `main` ist dem Produktivstand voraus
   (Staging automatisch, Produktion nach Freigabe, R67). Ein Handbuch, das
   live von GitHub kommt, beschreibt Funktionen, die die Installation noch
   nicht hat — oder, bei Selbsthostern mit älterer Fassung, längst andere.
   Das Handbuch muss zur **installierten** Fassung passen.
2. **Die Hilfe hinge an GitHub.** Ist GitHub nicht erreichbar, ist die
   Hilfe weg; jede Installation ruft bei jedem Hilfeaufruf einen Dritten
   an — gegen R36 und den Geist von `CLAUDE.md` 4.
3. **Der Renderer im Server** (`rechtstexte_lib.php`) kann absichtlich nur
   Überschriften, Absätze, Listen und Links (E-P3-38) — keine Bilder, keine
   Tabellen. Für ein Handbuch mit Screenshots reicht er nicht, und er
   sollte dafür auch nicht wachsen.

**Was „Änderungen kommen an" richtig erfüllt: das Handbuch reist mit dem
Release.** Die Kette (R67) bekommt vor dem Deploy einen Bauschritt, der
`docs/Handbuch.md` samt `docs/bilder/` in **statisches HTML** unter
`server/hilfe/` wandelt (Inhaltsverzeichnis mit Sprungmarken, Bilder, das
Stylesheet der Anwendung, keine Skripte, CSP-konform). Die Weboberfläche
verlinkt es unter **„Hilfe"** (Fußzeile und Anmeldeseite — auch, wer sich
nicht anmelden kann, braucht die Hilfe; das Handbuch enthält keine Daten).
Jede Änderung am Handbuch landet mit dem nächsten Release in jeder
Installation — **genau mit der Fassung, die sie beschreibt**; Selbsthoster
bekommen sie mit ihrem Update. Das Rendering ist ein Werkzeug unter
`tools/` (Python-Markdown oder Pandoc, in Stufe 1 gebaut), das erzeugte
HTML liegt **nicht** im Repositorium. Betreiberhandbuch, Installation und
Technik bleiben auf GitHub (O1) — sie brauchen keinen Platz im Tool.
*Zu bestätigen.*

### 4h.5 Antwort auf (e): Was in den Notfallplan gehört

Ohne Zugänge, für jede Installation gültig. Je Fall vier Zeilen:
**Erkennen — Sofort — Beheben — Danach.** Die Fälle:

| Fall | Kern der Anweisung |
|---|---|
| Server nicht erreichbar (Hoster, Domain, TLS abgelaufen) | Geräte puffern von selbst (Vertrag 5xx), nichts löschen; Hoster/Domain/Zertifikat prüfen; NutzerInnen informieren |
| Update fehlgeschlagen, Seite zeigt Fehler | Wartungsmodus an; Rollback nach R67: voriger Tag freigeben, Komplett-Backup vom Backup-Tor wiederherstellen; Wartungsmodus aus |
| Migration hängt oder bricht ab | Wartungsmodus bleibt; `update.php`/CLI-Notausgang; sonst Rollback |
| Versehentlich gelöscht | Papierkorb und seine Fristen; danach Konto-Backup (edbak) oder Komplett-Backup — Grenze: Backups sind nur mit dem Passwort lesbar |
| Passwort einer NutzerIn verloren | Zurücksetzen über die Anwendung; **Grenze der E2E:** ohne altes Passwort sind die verschlüsselten Daten weg, ein altes Backup bleibt mit dem alten Passwort lesbar (Handbuch, Kapitel Verschlüsselung) |
| Einziger Admin fällt aus | Vorsorge: **zweiter Admin ist Pflicht**; ohne ihn Zugang über die Datenbank nach Runbook |
| Uhr oder Handy verloren | Gerät in der Weboberfläche entkoppeln (Geräteschlüssel sperren); was auf dem Gerät liegt (Puffer, Koordinaten im Klartext — Bedrohungsmodell); Passwort ändern |
| Verdacht auf Datenpanne | Wartungsmodus; Protokolle sichern; Betroffene und Umfang ermitteln; **Meldung an die Aufsichtsbehörde binnen 72 Stunden** (Art. 33 DSGVO), Betroffene informieren; Serverschlüssel und Geräteschlüssel erneuern |
| Speicher voll, Jobs stehen | Speichergrenze auf der Wartungsseite; Aufräumjobs; Backup-Ziel prüfen |
| E-Mail-Versand kaputt | SMTP-Prüfung auf der Wartungsseite; Reset-Mails kommen nicht an — NutzerInnen anders erreichen |
| Betreiberin fällt dauerhaft aus (Todesfall, Aufgabe) | Was ohne Passwörter **nicht** geht (E2E); Übergabe an den zweiten Admin; Stilllegung nach Handbuch |
| Wiederanlaufpaket | Was hineingehört (Serverschlüssel, `config.php`, Backup-Ziele, letzter Stand) und dass ohne Serverschlüssel jedes Komplett-Backup wertlos ist — **wo** es liegt, steht nicht im Dokument |

**Instanz-Spezifisches** (Hoster, Domain, Mail, Aufsichtsbehörde, zweiter
Admin, Ablageort des Wiederanlaufpakets, Play Console) kommt in eine
**„Betriebsakte"** — eine Vorlage im Betreiberhandbuch (leere Tabelle),
die jede Betreiberin **außerhalb des Repositoriums** ausfüllt. So erfüllt
R41 („Zugänge dokumentiert") ohne ein Geheimnis und ohne einen Namen in
der öffentlichen Doku. *Zu bestätigen.*

---

## 4i. F-PV-9 — Einordnung der Problemsammlung als S9 (nachgetragen 03.09.2026)

### 4i.1 Befund

Der Auftraggeber legt am 03.09.2026 die **Problemsammlung NAdoku** vor: zehn
Punkte zur Einsatzbearbeitung und den Rettungsmitteln (vier Bugs, drei
Gestaltungspunkte, drei Erweiterungen, davon zwei mit Datenmodelländerung),
neunzehn Entscheidungen bereits gefallen, vier Fragen offen (F3–F6). Sie
ist ein Schritt mit eigenem Konzept, kein Einzelpunkt der Backlog-Runde.
Zwei Verbindungen in den Bestand: **PS-3** (kompaktere Buttons) ist dieselbe
Frage wie **Nr. 74** (Bedienhöhe, Entscheidung im S8-Konzept); **PS-8.2**
(Notizfeld verschlüsseln, Suche bleibt) berührt **Nr. 43 und das
Bedrohungsmodell** des Reviews (R69). Dazu die Kollision der Bezeichner:
„P3" und „P5" sind Phasen — die Punkte heißen deshalb **PS-1 bis PS-10**.

### 4i.2 Entscheidung (03.09.2026: „Schrittnummern verschieben; Reihenfolge, wie du es für richtig hältst")

1. **Neuer Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel"**, Konzept
   nach K1 mit **Fable** (R14; Mockups PS-3 und PS-5 sowie der Zielkonflikt
   PS-8.2 sind Fable-Schritte, K2), Umsetzung Opus. Vorbereitung:
   `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` (die Sammlung mit
   Backlog-Zuordnung und den Verbindungen).
2. **Reihenfolge:** das S9-Konzept **nach dem S8-Konzept** (Nr. 74 fällt
   dort; zwei Fable-Konzepte zur Oberfläche nacheinander sind für den
   Freigeber übersichtlicher). Die **Umsetzung** darf parallel zur
   S8-Umsetzung laufen, wenn beide Konzepte ihre Berührungen benennen
   (Stylesheet; Stammdaten- und Einstellungsseiten). **P5 setzt S9 nicht
   voraus; P6 schon** — der Review liest den S9-Code mit, und die Antwort
   auf den Zielkonflikt PS-8.2 geht in das Bedrohungsmodell ein.
3. **Backlog Nr. 101–113**, alle S9 (Tabelle in der Vorbereitung).
4. **F3–F6** als Zuarbeit in Abschnitt 6, vor dem S9-Konzept.
5. **Schrittnummern verschieben:** Backlog-Runde 8 → 9, P5 9 → 10, Planung
   v1.0 10 → 11, P6 11 → 12, P7 12 → 13, P8 13 → 14. Alle Verweise im
   Rahmenplan und in den Einfügeblöcken dieses Konzepts werden beim
   Einpflegen nachgezogen (Tabelle 6.2.9.3).
6. **Erste Prüffrage der S9-Analyse:** die Geocoding-Quelle für PS-1
   (Zusage `CLAUDE.md` 4) — dieselbe Quelle wie die heutigen
   Adressvorschläge oder keine.

→ **E-PV-9**, Einfügeblöcke 6.2.9 (R73).

---

## 5. Entscheidungen (E-PV-…)

### E-PV-1 — Store-Verteilung in zwei Stufen (03.09.2026); ersetzt E-R45-6

**Kern (für Abschnitt 7 als R65):** Die Android-Clients werden über die Play
Console verteilt — **interner Test-Track ab Schritt 6** als Regelweg für den
bekannten Kreis, **Produktionsfreigabe erst als Welle 1** des
Betriebsübergangs (R41). Organisationskonto der Gen-EM GbR (D-U-N-S).
Versionscode je Modul mit Versatz. Vorhandener Signaturschlüssel wird
App-Signaturschlüssel bei Play App Signing. MDR-Abgrenzung (R41) vor der
Produktionsfreigabe. Connect IQ unverändert (R41). Seitenladung bleibt bis
zur Produktionsfreigabe und entfällt mit Welle 1.

**Im Einzelnen:**

1. **Konto.** Play-Console-**Organisationskonto**, Träger die **Gen-EM GbR**.
   D-U-N-S-Nummer **sofort** beantragen (Dun & Bradstreet, kostenlos, bis zu
   vier Wochen). Beim Antrag zu klären, nicht hier angenommen: ob die GbR im
   Gesellschaftsregister eingetragen ist (eGbR) — D&B und Google gleichen
   Name und Anschrift ab; ohne Registereintrag mit Gesellschaftsvertrag
   oder Gewerbeanmeldung belegen. Kontoinhaber ist ein **Google-Konto der
   GbR** (keine private Adresse); Identitätsprüfung des Kontoinhabers;
   25 USD einmalig. Entwicklername und Kontaktadresse erscheinen später
   öffentlich im Store-Eintrag.
2. **Stufe 1 — interner Test-Track**, ab Schritt 6 mit Android 1.0.0, für
   Handy und Uhr unter einem Eintrag (`org.genem.nadoku`, E-S4-01), Wear OS
   als eigener Formfaktor und Track. Bis 100 Tester per Einladung, nicht
   öffentlich — Grund (3) aus E-R45-6 (Flutungsgefahr ohne Mengenbremse)
   bleibt gewahrt. **Welche Deklarationen der interne Track schon verlangt,
   wird beim Einrichten geprüft**, nicht hier festgelegt.
3. **Stufe 2 — Produktionsfreigabe = Welle 1** (R41): nach P5 (R19, R37.10),
   nach MDR-Abgrenzung und Rechtsunterlagen (R41), nach Wear-OS-Prüfrunde.
4. **Versionscode.** E-S4-02 bleibt (eine Zählung, ein Versionsname); der
   gerechnete Code erhält für das Uhr-Modul einen **Versatz**; Schema legt
   die Umsetzung in Schritt 6 fest (`android/build.gradle.kts`,
   Kopf von `version.properties`, `android/LIESMICH.md`). Preis: ein
   einmaliger Sprung, Neuinstallation auf der vorhandenen Uhr. Backlog
   Nr. 98.
5. **Signatur.** Der vorhandene Schlüssel (RSA 4096, Zertifikat
   `078c…ad64`) wird bei Play App Signing als **App-Signaturschlüssel
   hochgeladen**; ein getrennter **Upload-Schlüssel** wird erzeugt und wie
   der erste außerhalb des Repositoriums verwahrt (E-S4-16); die
   Verifizierungsregistrierung nennt dasselbe Zertifikat, damit
   Seitenladung und Play-Installation dieselbe App bleiben. **Folge:** Der
   Signaturschlüssel liegt danach auch bei Google — Bedrohungsmodell
   (S2/AP10) und R17-Review (F-PV-4) nehmen es auf.
6. **Seitenladung.** Die Karte „NAdoku für Android" (`apk.php`,
   Handbuch 10.1) **bleibt bis zur Produktionsfreigabe** als zweiter Weg zur
   selben App und **entfällt mit Welle 1**: `apk.php`, `apk_lib.php`, die
   Karte in `einstellungen.php`, Handbuch 10.1, der Technik-Abschnitt, die
   Ausnahmen `apk/` in `.gitignore` und `deploy.yml`, das Verzeichnis
   `server/apk/` auf dem Server. Bis dahin nennt Handbuch 10.1 den internen
   Track als Regelweg und die Karte als Rückfall. *(Lesart der Antwort
   „entfällt mit Veröffentlichung im Play Store"; falls der interne Track
   gemeint war, rückt der Abbau nach Schritt 6.)*
7. **Zuarbeiten** → Rahmenplan Abschnitt 6 (Einfügeblock 6.2.7.5).
8. **Connect IQ** unverändert nach R41: Betriebsübergang nach v1.0.

**Begründung, kurz:** Wear OS hat ohne Store keinen NutzerInnen-Weg; die
Seitenladung braucht ab 2027 im Zielmarkt ohnehin ein Konto und ein
registriertes Zertifikat; das Sicherheitsargument von E-R45-6 richtet sich
gegen **öffentliche** Verteilung, und der interne Track ist keine. Was an
E-R45-6 stehen bleibt: Produktion nach P5 und R41, Schlüssel außerhalb des
Repositoriums.

**Wirkung:** Rahmenplan Abschnitt 1, Schritt 6, Betriebsübergang,
Abschnitte 5, 6, 7, 10; `docs/Backlog.md` Nr. 98; Konzept S4 Abschnitte 8
und 13 sowie B-S4-04 — alles in 6.2.7.

### E-PV-2 — Update-Weg der Installation ab v1.0 (03.09.2026); beantwortet R60

**Kern (für Abschnitt 7 als R66):** Die Installation prüft sich nicht selbst
auf neue Fassungen und ändert ihren Code nie selbst. Neuer Code kommt
ausschließlich über die Auslieferungskette (R40, R67); **auf Produktion nur
auf ausdrückliche Auslösung der Betreiberin, nie automatisch** — damit vor
jedem Update Zeit bleibt, die Backups zu prüfen oder anzustoßen. Die
Wartungsseite zeigt **nur ausstehende** Migrationen mit der Handlung
„Ausstehende ausführen"; der Torwächter liest dasselbe Register; die
ausgeführten Kennungen wandern ins Audit-Protokoll (P5). Das
Migrationsregister beginnt bei v1.0 neu.

**Im Einzelnen:**

1. **Keine Selbstprüfung (A1).** Die Wartungsseite zeigt die laufende
   Fassung und das Datum des letzten Einspielens; neue Fassungen erscheinen
   als GitHub-Release mit Changelog; Selbsthoster abonnieren das
   Repositorium. Kein Aufruf der Installation nach außen — im Einklang mit
   R36 („keine Telemetrie") und dem Geist von `CLAUDE.md` 4. Eine Prüfung
   **auf Klick** (A2) bleibt als Backlog-Punkt nach v1.0 (Nr. 99).
2. **Einspielen von außen, nur von Hand ausgelöst (B1, präzisiert).** Die
   Installation verändert ihren eigenen Code nie (kein WordPress-Weg, B2):
   kein Schreibrecht auf den Code, kein webseitig auslösbarer
   Code-Download, keine halb ersetzten Dateien auf geteiltem Webspace.
   Für die eigene Installation ist die Kette der Weg — Staging automatisch,
   **Produktion erst, wenn die Betreiberin sie auslöst**; wie die
   Auslösung technisch aussieht und ob die Kette vorher selbst ein
   Komplett-Backup anstößt, entscheidet F-PV-3. R40 (4) „Release-Tag →
   Produktion" liest sich damit „Release-Tag → Auslösung durch die
   Betreiberin → Produktion". Für Selbsthoster: Release-Archiv, FTP,
   Migrationen ausführen — Betreiberhandbuch (R16/R41).
3. **Migrationen sichtbar, aber nur die ausstehenden (C3).** S8 (Nr. 77)
   teilt die Wartungsseite; die Unterseite „Migrationen" zeigt die
   ausstehenden Kennungen und die Handlung „Ausstehende ausführen" — die
   Betreiberin löst Migrationen wissentlich aus, weil sie selten
   rückwärtsfähig sind. Der Torwächter (R40.4, P5) liest **dasselbe
   Register**. Ausgeführte Kennungen bleiben gespeichert (das Register
   braucht sie, um „genau einmal" zu garantieren), werden aber nicht mehr
   als Liste geführt: ab P5 stehen sie im Audit-Protokoll (R38) als
   „Migration … ausgeführt am … durch …". **Zwischen S8 und P5** bleibt die
   ausgeführte Liste eingeklappt erreichbar, damit nichts unsichtbar wird —
   die Darstellung entscheidet das S8-Konzept.
4. **Bei v1.0 beginnt das Register neu** (Folge von R60, ausgeführt in P6):
   Die frische Installation entsteht aus `schema.sql`; die bis dahin
   gelaufenen Migrationen gehören in die Grundfassung, nicht in die Liste.

**Begründung, kurz:** Betreiberin und Entwicklung sind dieselben Personen;
ein Selbstcheck wäre wertlos und meldete Selbsthoster an einen Dritten. Ein
Selbst-Update ist auf geteiltem Webspace die größte Angriffsfläche, die die
Anwendung sich geben könnte. Sichtbare, von Hand ausgelöste Migrationen
kosten einen Klick und ersparen den unbemerkten Abbruch unter dem
Zeitbudget des Hosters.

**Wirkung:** Rahmenplan Abschnitt 3 (Schritte 7, 10, 11), Abschnitt 5
(Nr. 77, neu 91), Abschnitt 7 (R60, R66), Abschnitt 10; `docs/Backlog.md`
Nr. 99 — alles in 6.2.1.

### E-PV-3 — Auslieferungskette (03.09.2026); präzisiert R40 (4)

**Kern (für Abschnitt 7 als R67):** `main` deployt automatisch auf
**Staging**, das zugleich die Prüfumgebung ist. Ein **Release-Tag startet
den Produktiv-Lauf, der in der GitHub-Umgebung „produktion" auf die
Freigabe der Betreiberin wartet** (Push-Nachricht, ein Tipp in der
GitHub-App); die Produktiv-Zugangsdaten liegen nur in dieser Umgebung. Der
freigegebene Lauf **stößt zuerst das Komplett-Backup an und bricht ohne
Erfolgsmeldung ab**, dann Deploy; Migrationen führt die Betreiberin aus
(R66). **Rollback** = voriger Tag plus Wiederherstellung des Backups.
**Prüftor in drei Stufen.** Android wird in der CI unsigniert gebaut und
geprüft; signiert und hochgeladen wird auf dem Rechner der Betreiberin.

**Im Einzelnen:**

1. **Staging** (R40 (2) unverändert): jeder Push auf `main` → Staging;
   zweite Installation mit eigener `config.php` und Datenbank, ohne
   Echtdaten; dort dauerhaft Demo-Konto, Referenzdatensatz,
   Messstand-Konto. Staging ist die Umgebung für Kreisläufe, Bilderlauf und
   Messstand.
2. **Freigabe-Tor (T1).** Der Produktiv-Job referenziert die
   GitHub-Umgebung „produktion" mit Pflichtfreigabe („required reviewers":
   die Betreiberin); optional Mindestwartezeit. Die FTPS-Zugangsdaten der
   Produktion sind **Umgebungsgeheimnisse** dieser Umgebung — kein Lauf
   erreicht sie ohne Freigabe. Freigabe per Push-Nachricht in der
   GitHub-App oder im Browser. **Voraussetzung:** Pflichtfreigaben sind im
   kostenlosen Plan nur für **öffentliche** Repositorien verfügbar — das
   ist eine Bedingung für **F-PV-2** (Repositorien bleiben öffentlich, oder
   der Plan ändert sich).
3. **Backup-Tor (K2 + Checkliste).** Erster Schritt des freigegebenen
   Laufs: Aufruf des Komplett-Backup-Jobs über den Token-Einstieg
   (`jobs.php`); **ohne Erfolgsmeldung bricht der Lauf ab**, nichts wird
   hochgeladen. Zu klären in der Umsetzung: ob der Job-Einstieg den Erfolg
   synchron meldet oder der Lauf auf den Zeitstempel warten muss. Dazu die
   Checkliste im Betreiberhandbuch (R41): vor der Freigabe Wartungsseite
   prüfen — letztes Komplett-Backup, Backup-Ziel erreichbar.
4. **Rollback.** Code: Freigabe eines Laufs mit dem vorigen Tag.
   Datenbank: liefen Migrationen, Wiederherstellung des Backups aus (3)
   über `wiederherstellen.php`; Eingaben seit dem Update gehen dabei
   verloren, deshalb zügig entscheiden; der Torwächter zeigt Wartung.
   Geprobt beim Neuaufsetzen (R40 (3)) und halbjährlich mit der
   Probe-Wiederherstellung. Steht im Betreiberhandbuch.
5. **Prüftor in drei Stufen** — festgehalten in R67 (Kern), in
   `.github/workflows/` (Stufe 1 ein Lauf je Push; Stufe 2 nach dem
   Staging-Deploy; Stufe 3 der Job in „produktion") und im
   Betreiberhandbuch/`docs/Technik.md`; gebaut in P5, das P5-Konzept
   übernimmt die Stufen von hier:
   - *Stufe 1, jeder Push, jeder Zweig:* Wortliste 0/0/0, Vollständigkeit,
     Kontraste, `php -l`, Backlog-Doppelungen leer, Migrationsregister
     `schema.sql` = `update.php`, Android `gradlew build` (0 Lint-Fehler,
     0 Fehlschläge), Uhr Stufe I, wenn das SDK in der CI beschaffbar ist.
     **Rot = kein Merge auf `main`.**
   - *Stufe 2, nach dem Staging-Deploy, nur `main`:* Kreisläufe csv und
     edbak (0 unerklärt), Bilderlauf (Überlauf, Konsole, Knopfhöhen 0),
     Messstand nur bei Tag-Läufen. **Rot = Tag nicht freigabefähig.**
   - *Stufe 3, Produktion nach Freigabe:* Tag grün? → Backup (3) → FTPS →
     Betreiberin führt ausstehende Migrationen aus (R66) → Nachprüfung
     (Startseite antwortet, `WEB_VERSION` = Tag). **Sobald ein
     Wartungsmodus-Schalter existiert (Abschnitt 7), wird Stufe 3 zu:
     Wartung an → Backup → Deploy → Migrationen → Wartung aus** — das
     Backup ist dann ohne laufende Schreibzugriffe konsistent, und die
     Endgeräte liefern nach.
6. **Android und Uhr (G1).** CI baut unsigniert und prüft (E-S4-16 bleibt).
   Upload-Schlüssel auf dem Rechner der Betreiberin, Anleitung in
   `android/LIESMICH.md`; signiertes App Bundle von Hand in die Play
   Console. **Nach v1.0** (Backlog Nr. 100): Upload-Schlüssel als
   GitHub-Secret plus Play-API-Dienstkonto, die Kette lädt auf den
   internen Track — vertretbar, weil der Upload-Schlüssel der zurücksetzbare
   ist; E-S4-16 ist dann um den Unterschied App-Signaturschlüssel /
   Upload-Schlüssel zu ergänzen. Uhr: `.prg` von Hand; CI nur Stufe I.

**Begründung, kurz:** Die Betreiberin will vor jedem Produktiv-Update Zeit,
Backups zu prüfen — das Freigabe-Tor gibt sie, das Backup-Tor sichert sie ab,
auch wenn die Prüfung einmal ausfällt. Staging als Prüfumgebung nutzt den
echten Hoster, was R40 (1) am Autodeploy festgehalten hatte.

**Wirkung:** Rahmenplan Abschnitt 3 (Schritte 9, 11), Abschnitt 6
(Staging-Zeile, CI-Secret Uhr), Abschnitt 7 (R40, R67), Abschnitt 10;
`docs/Backlog.md` Nr. 100; **Bedingung an F-PV-2** (öffentlich) — alles in
6.2.3.

### E-PV-4 — Ein Repositorium, frisch, öffentlich (03.09.2026); beantwortet R59 (Repositorien)

**Kern (für Abschnitt 7 als R68):** v1.0 lebt in **einem** frischen,
öffentlichen Repositorium **`gen-em/nadoku`** (AGPL-3.0), ohne Git-Historie;
das alte Repositorium wird archiviert und verweist weiter. Drei Zählungen
bleiben, mit Tag-Präfix je Zählung und Pfadfiltern in der Kette. Der Umzug
ist das **letzte P6-Paket „Repo-Umzug und Inventur"** und prüft, was an
Werkzeugen, Dokumenten und Workflows wirklich mitwandert.

**Im Einzelnen:**

1. **Eines.** Vertrag, Backlog, Rahmenplan, Konzepte, Prüfwerkzeuge und
   Kette an einem Ort; eine Vertragsänderung bleibt ein Zweig mit Server-
   und Client-Paketen (Muster S5). Tags `web-v…`, `uhr-v…`, `android-v…`;
   Workflows mit Pfadfiltern (`server/**` → Staging und Stufe 2;
   `android/**` → Gradle; `watch/**` → Stufe I); GitHub-Releases je Tag.
2. **Öffentlich, AGPL-3.0** wie heute — Bedingung aus E-PV-3
   (Pflichtfreigabe), Voraussetzung für Selbsthoster (R63), unbegrenzte
   Actions-Minuten. Geheimnisse nie im Repositorium.
3. **Name `gen-em/nadoku`.**
4. **Frisch, ohne Historie.** Erster Commit ist der v1.0-Stand nach Review
   und Umbenennung. `gen-em/einsatzdoku-luftrettung` wird auf GitHub
   **archiviert** (schreibgeschützt, lesbar), README verweist auf das neue.
5. **Was wandert:** `server/`, `watch/`, `android/`, `tools/`, `.github/`
   (Kette R67), `CLAUDE.md`, `LICENSE`, `README.md`; aus `docs/`
   Rahmenplan, Rahmenplan-Archiv, Backlog (dauerhafte Nummern),
   JSON-Vertrag v1 (R12), Handbuch und Technik in der Neufassung (R16),
   Changelog neu ab v1.0 mit Verweis auf das Archiv (R15), laufende
   Konzepte. **Nicht** `docs/konzepte/erledigt/`. **Dazu, auf Wunsch des
   Auftraggebers: ein eigenes letztes P6-Paket „Repo-Umzug und
   Inventur"** — Durchsicht von `tools/` (27 Werkzeuge: welche prüfen noch
   etwas, welche gehören zu abgeschafften Formaten wie Nr. 46), `docs/`
   (Design.md, Uhr-Layout_Regeln.md, Backup-Format.md, Export-Format.md,
   Geraete-Eingabe.md — bleibt, wandert, geht in die Neufassung auf?),
   `.github/` und `CLAUDE.md`; Ergebnis ist die Liste dessen, was in den
   ersten Commit kommt, mit Begründung je Weglassung.
6. **Zweigschutz:** `main` nur über Pull-Request mit grüner Stufe 1; keine
   direkten Pushes; Arbeitszweige wie heute (K7); Tags nur auf `main`;
   Umgebungen `staging` und `produktion` (R67).
7. **Zeitpunkt:** letzter Schritt von P6, zusammen mit dem Neuaufsetzen
   (R40 (3)); die Kette entsteht in P5 im alten Repositorium und zieht als
   `.github/` mit.

**Begründung, kurz:** Alles, was die Aufteilung hätte lösen sollen
(Zählung, Auslieferung), lösen Tag-Präfixe und Pfadfilter; alles, was sie
gekostet hätte (Vertrag, Backlog, Konzepte über Produkte hinweg), fällt
weg. Frisch, weil Abschnitt 1 es so sagt und die Beschlüsse in Archiv und
Changelog stehen, nicht in der Commit-Historie.

**Wirkung:** Rahmenplan Abschnitt 1, Schritte 10 und 11, Abschnitt 6,
Abschnitt 7 (R59, R68), Abschnitt 10 — alles in 6.2.2.

### E-PV-5 — Bug- und Sicherheitsreview: Umfang, Form, Wege der Funde (03.09.2026); präzisiert R17

**Kern (für Abschnitt 7 als R69):** Der R17-Review liest **alles** — drei
Codebasen, Werkzeuge, Doku — **in Stücken**, als Eingang von P6, mit Fable.
Erstes Ergebnis ist ein **Bedrohungsmodell** als eigener Abschnitt. Gesucht
werden Bugs, Sicherheitslücken, ungebrauchter Code, Karteileichen und
Probleme jeder Art; dazu ein **Kommentardurchgang**: keine Verweise mehr auf
alte Konzepte, Beschlüsse oder Fassungen — der Code startet sauber als v1.0.
Funde in einem Review-Dokument mit zwei Wegen: **kritisch → Sofortpaket,
alles andere → Pflichtpaket in P6.** Der Auftraggeber entscheidet je Fund in
einer Freigaberunde; der P6-Paketschnitt folgt dem Review.

**Im Einzelnen:**

1. **Umfang (U1).** Jede Datei unter `server/`, `watch/`, `android/`,
   `tools/`, `.github/` und die Doku unter `docs/` (Handbuch, Technik,
   Vertrag, Geräte-Eingabe, Design, Backup- und Export-Format); die
   Themenliste aus 4e.1 in der Tiefe; jeder HTTP-Einstieg gegen die
   Checkliste aus U3; die offenen B-Funde und das Backlog; je Prüfwerkzeug
   die Frage, ob es noch prüft, was der Code tut.
2. **In Stücken.** Der Review läuft als Folge von Sitzungen mit festem
   Schnitt, jede schreibt in dasselbe Review-Dokument mit fortlaufender
   Fund-Nummer: **(1)** Bedrohungsmodell · **(2)** Kryptographie und
   Schlüssel (`serverkrypto_lib`, `auth_salt`, `pw_handling`, Container 4,
   SPUR1, `komplett_lib`, `backup_lib`, `wiederherstellen`, Demo) ·
   **(3)** Zugang und Sitzung (`login`, `logout`, `session_lib`,
   `auth_guard`, `ratelimit_lib`, `reset_request`, `pair`, `jobs`, CSRF) ·
   **(4)** Einsatz, Diensttag, Spur, Ingest (`ingest`, `spur_lib`,
   `diensttag_*`, `einsatz*`, `nachbearbeitung*`, `gpx*`, `import`) ·
   **(5)** Verwaltung und Admin (`einstellungen`, `admin_*`, `stammdaten*`,
   `rechtstexte*`, `mission_fields*`, `papierkorb`, `suche`, `zeitraum`,
   `apk*`) · **(6)** Betrieb (`update`, `wartung_lib`, `jobs_lib`,
   `sicherungsziel_lib`, `smtp`, `email_lib`, `install`, `db`, `ui`,
   `version`, `.htaccess`) · **(7)** Browser-Skripte und Stylesheet ·
   **(8)** Uhr (`watch/`) · **(9)** Android (beide Module) · **(10)**
   Werkzeuge (`tools/`, 27 Verzeichnisse) und Workflows · **(11)** Doku
   gegen Code · **(12)** Zusammenführung: Fundliste sortiert, Sofortpaket
   ausgewiesen, Vorschlag für den P6-Paketschnitt. Maschinelle
   Vorsortierung ist erlaubt und erwünscht (PHPStan/Psalm für toten Code,
   Android Lint für ungenutzte Ressourcen, Compiler-Warnungen der Uhr,
   `vulture` für Python-Werkzeuge) — gelesen wird trotzdem alles.
3. **Kommentardurchgang.** Kommentare erklären das **Warum** in Worten;
   Nummern von Beschlüssen (R…, E-…), Backlog-Nummern, Fassungen („seit Web
   9.7.0"), Konzept- und Paketnamen verschwinden aus dem Code. Wo ein Grund
   ohne den Verweis nicht mehr verständlich wäre, wird er ausgeschrieben;
   wo er nur Geschichte war, entfällt er. Damit gehen **R13 (Kommentare
   normalisieren, Liste in Konzept P2 10.3)** und der Namensdurchgang
   **R31** im Review auf — beide werden zu Funden und Aufräumpaketen.
   **Folge für `CLAUDE.md`** im neuen Repositorium: Kommentarregel
   entsprechend („Grund ja, Nummer nein"); die Rückverfolgung leisten
   Changelog und Rahmenplan-Archiv.
4. **Zeitpunkt (Z1).** Ein Review, als Eingangsschritt von P6, nach P5 und
   nach dem Wartungsmodus-Zusatz — so nah wie möglich an v1.0; danach nur
   noch die P6-Pakete, die aus ihm folgen. Zwei Phasen verworfen.
5. **Bedrohungsmodell zuerst** (Stück 1): eigener Abschnitt in
   `docs/Technik.md` oder eigene Datei — Schutzziele; Angreifer; Zusagen
   und benannte Grenzen (E2E; Demo; Klartext-Koordinaten;
   Signaturschlüssel bei Google; Speicherdauern; Geheimnisse der Kette);
   Referenz für den Review und für VVT/TOMs (R41).
6. **Form.** `docs/konzepte/Review-R17.md`: je Fund Nummer, **Einstufung
   kritisch oder nicht**, Fundstelle, Wirkung, Empfehlung, Aufwand; je
   Datei die Karteileichen-Liste. Der Review behebt nichts (K4).
7. **Wege der Funde.** **Kritisch** (ausnutzbar, Datenverlust, Bruch einer
   Zusage) → **Sofortpaket** vor allem anderen, Muster
   `Pruefung-Sofortpaket-22.md`. **Alles andere → Pflichtpaket in P6**;
   v1.0 wird nicht erklärt, solange ein Fund offen ist. Keine Kategorie
   „nach v1.0". Die Umsetzung bündelt Aufräumfunde je Codebasis
   (Aufräumpaket Server / Uhr / Android / Werkzeuge) statt je Fund; die
   Kryptographie-Änderungen sind Fable-Schritte, alles andere Opus (K8).
8. **Freigaberunde.** Der Review empfiehlt, der Auftraggeber entscheidet
   je Fund (Liste mit Empfehlung, Antwort je Nummer); Ergebnis als E-R17-n
   im Review-Dokument; Weg B (Nr. 43) bekommt eine eigene R-Nummer.
   **Der P6-Paketschnitt (Rest von Schritt 10) folgt der Freigaberunde**,
   nicht umgekehrt.
9. **Vorbedingungen.** Die drei Fragen aus Nr. 43 beantwortet; P5 gemergt;
   S5 samt Zusatz W gemergt; Repositoriumszugriff für eine Fable-Instanz
   in Claude Code (R18).

**Begründung, kurz:** Der Auftraggeber will v1.0 so sauber wie möglich —
also alles lesen, alles beheben, und die Historie aus dem Code in die Doku
verlagern, wo sie hingehört. Die Stückelung macht das lesbar; die zwei Wege
machen es entscheidbar.

**Wirkung:** Rahmenplan Abschnitt 3 (Schritte 10, 11), Abschnitt 5 (Nr. 43),
Abschnitt 6, Abschnitt 7 (R13, R17, R31, R69), Abschnitt 10; `CLAUDE.md`
(Kommentarregel, im neuen Repositorium) — alles in 6.2.4.

### E-PV-6 — Web-App-Manifest in P7 (03.09.2026); erledigt die Erhebung zu Nr. 87

**Kern (für Abschnitt 7 als R70):** Die Weboberfläche wird als
installierbare Web-App ausgeliefert — **Manifest allein, kein Service
Worker** (Chrome auf Android verlangt seit Version 108 keinen mehr; kein
Cache, keine alten Dateien) — **in P7 mit der Umbenennung**, unter dem Namen
**„NAdoku Web"** mit eigenem Symbol: gleicher Hubschrauber wie die
Handy-App, andere Hintergrundfarbe, kleine Marke rechts unten (Browser-Marke;
der Tracker bekommt zum Gegenstück eine GPS-Nadel). Entwurf und Wahl der
Symbole im P7-Konzept.

**Im Einzelnen:**

1. **Manifest** (`manifest.webmanifest`): `name`/`short_name` „NAdoku Web",
   `start_url`, `display: standalone`, `theme_color`/`background_color` aus
   der Farbpalette, Symbole 192 und 512 px, davon eines maskierbar (Marke
   innerhalb der Schutzzone, sonst wird sie rund abgeschnitten);
   `<link rel="manifest">` und `theme-color` in `ui.php`; CSP-Freigabe für
   das Manifest (Nr. 8); `apple-touch-icon` bleibt (Safari). Kein Service
   Worker; die Offline-Seite ist die des Browsers.
2. **Nachweis am Gerät** (Nr. 87): Installierbarkeits-Diagnose in Chrome;
   am S24 mit **Chrome, Samsung Internet und Firefox für Android**; auf
   einem iPhone Safari „Zum Home-Bildschirm" — für iPhone-NutzerInnen ist
   das die einzige App-Form (E-R45-5).
3. **Kein Sonderfall für Anmeldung und Schlüssel:** eigenes Fenster =
   eigener Tab = eigener `sessionStorage` (R44 gilt unverändert).
4. **Symbole:** Tracker-App und Web-App teilen den Hubschrauber und
   unterscheiden sich in Hintergrundfarbe und Marke; auf ein
   Browser-Abzeichen des Systems ist kein Verlass (Chrome-WebAPKs tragen
   keines). Farben aus den Gen-EM-Vorgaben (Projektablage); Entwurf beider
   Symbole nebeneinander im P7-Konzept, Zuarbeit des Auftraggebers: Wahl.
   Das Launcher-Symbol der Handy-App wird dabei ebenfalls angepasst (vor
   v1.0 unkritisch).
5. **Handbuch:** Abschnitt „Auf dem Startbildschirm ablegen" (Android,
   iPhone).

**Begründung, kurz:** Nr. 87 wollte es; die Chrome-Bedingung ist geprüft;
ein Manifest kostet einen halben Tag und kein Risiko; ein Service Worker
kostete den Grundsatz „keine alten Dateien" — für eine Verwaltung, die
ohne Netz nichts zu tun hat.

**Wirkung:** Rahmenplan Abschnitt 5 (Nr. 87), Schritt 10, Schritt 12 (P7),
Abschnitt 6, Abschnitt 7 (R70), Abschnitt 10 — in 6.2.5.

### E-PV-7 — Drei Phasen vor v1.0: P6, P7, P8 (03.09.2026)

**Kern (für Abschnitt 7 als R71):** Der v1.0-Schnitt wird in drei Phasen
mit je eigenem Konzept nach K1 geteilt: **P6 Review und Bereinigung**
(Fable-Eingang), **P7 Gesicht v1.0** (Umbenennung, Vertrag v1, Doku,
Manifest, Changelog, Backlog, Altformat, Kommentarregel), **P8 Schnitt**
(Neuaufsetzen, Register neu, Repo-Umzug mit Inventur, Kette im neuen
Repositorium, Rechtsunterlagen, Abnahme R11, Erklärung v1.0). Der
Paketschnitt je Phase entsteht im jeweiligen Konzept; P6 nach der
Freigaberunde des Reviews (R69). Alle bisherigen Nennungen „P6" werden
nach der Tabelle in 6.2.8 zugeordnet.

**Reihenfolge und Modell:** P6 → P7 → P8, nichts parallel (jede Phase
setzt die vorige voraus: P7 benennt sauberen Code um, P8 setzt das
Umbenannte neu auf). Fable in P6 (Review, Kryptographie), sonst Opus (K8);
Fable-Schritte je Konzept markiert (K2).

**Begründung, kurz:** Ein Konzept muss von einer Instanz zu Ende gelesen
und von einem Prüfdokument abgenommen werden können; nach E-PV-1 bis E-PV-5
konnte P6 das nicht mehr. Der Schnitt trennt drei Fragen, die nichts
gemeinsam haben: Ist der Code sauber? Sieht v1.0 aus wie v1.0? Steht die
Installation neu?

**Wirkung:** Rahmenplan Abschnitt 2 (Deploy-Satz), Abschnitt 3 (Schritt 10,
Zeilen und Blöcke 11–13), Abschnitte 5, 6, 7 (R5, R11, R12, R15, R16, R25,
R40, R41, R59, R66, R68, R71), Abschnitt 10; die Einfügeblöcke dieses
Konzepts, die auf „Schritt 11 (P6)" zielten, gehen in 6.2.8 auf — in 6.2.8.

### E-PV-8 — Anforderungen an die Doku-Neufassung (03.09.2026); beantwortet R16 („Anforderungsgespräch vorher")

*Punkte 2 und 5 tragen die Mechanik aus 4h.4 und 4h.5 — bestätigt am
03.09.2026; der Notfallplan wird eine FAQ im Betreiberhandbuch.*

**Kern (für Abschnitt 7 als R72):** Vier Dokumente nach Zielgruppe —
**Handbuch** (NutzerIn), **Betreiberhandbuch mit Notfall-FAQ** (jede
Betreiberin, auch Selbsthoster; ohne Zugänge, mit Betriebsakte-Vorlage),
**Installation und Selbsthosting**, **Technik** (mit Bedrohungsmodell) —
dazu der Vertrag wie bisher. Markdown im Repositorium mit Sprungmarken; das
**Handbuch reist als statisches HTML mit jedem Release** in die Installation
(„Hilfe"), nicht von GitHub zur Laufzeit. Screenshots erzeugt (1920×1080,
414×896), Uhr drei Simulatorbilder, Handy aus dem Gerätetest. **Kurz und
prägnant:** je Aufgabe ein Bild und wenige Schritte, Referenz im Anhang,
keine Fassungsgeschichte.

**Im Einzelnen:**

1. **Dokumentenschnitt (D2):** `docs/Handbuch.md` (Web, Uhr, Handy;
   Geräte-Eingabe geht auf) · `docs/Betreiberhandbuch.md` (Administration,
   Betrieb, Runbooks nach R66/R67 — Update mit Wartungsmodus, Freigabe der
   Kette, Rollback, Backup-Probe —, **Notfallplan** nach 4h.5,
   **Betriebsakte-Vorlage**) · `docs/Installation.md` (Server, eigenes
   APK nach R63, Uhr-Adresse, Wartungsmodus, Kette für Selbsthoster) ·
   `docs/Technik.md` (Architektur, Datenmodell, Abläufe, **Bedrohungsmodell**
   aus P6, Kette; Design und Uhr-Layout-Regeln als Anhang) ·
   `docs/JSON-Vertrag.md` unverändert eigenes Dokument · Anhänge:
   Backup-Format, Export-Format, Lizenzen. Was mit den heutigen
   Einzeldateien geschieht, entscheidet die Inventur (R68/P8).
2. **Form und Ort (O1 + O2 für das Handbuch):** Markdown bleibt die Quelle,
   GitHub rendert, Inhaltsverzeichnis mit Sprungmarken je Datei. Das
   **Handbuch** wird zusätzlich in der Kette (Stufe 1) zu statischem HTML
   nach `server/hilfe/` gerendert (eigenes Werkzeug unter `tools/`; kein
   Skript, Stylesheet der Anwendung, CSP-konform; erzeugtes HTML nicht im
   Repositorium) und mit dem Release ausgeliefert — Link **„Hilfe"** in
   Fußzeile und Anmeldeseite. Damit passt die Hilfe immer zur installierten
   Fassung, auch bei Selbsthostern, und hängt an keinem Dritten. Nicht
   von GitHub zur Laufzeit (Gründe 4h.4).
3. **Screenshots:** erzeugt aus `tools/screenshots/` mit dem Demo-Konto in
   **1920×1080 und 414×896**, abgelegt unter `docs/bilder/` mit festen
   Namen, erneuert mit jedem Release (Stufe 2, R67); Uhr **drei
   repräsentative Darstellungen** aus dem Simulator (`tools/uhr-bilder/`);
   Handy aus dem Gerätetest, Demo-Konto.
4. **Stil (kurz und prägnant):** aufgabenorientiert, je Aufgabe ein Bild
   und höchstens fünf Schritte; kein Absatz ohne Handlung; Referenzteile
   (Felder, Formate, Grenzen, Fristen) im Anhang; Anrede Du im Handbuch;
   Wortliste 0/0/0; keine Fassungsgeschichte („seit Web 9.7.0") — die steht
   im Changelog. **Abnahmemaß (Vorschlag):** das neue Handbuch hat höchstens
   ein Drittel der heutigen 2 700 Zeilen.
5. **Betreiberhandbuch mit Notfall-FAQ:** generisch für jede Installation,
   **ohne Zugänge, Namen oder Adressen**; die zwölf Fälle aus 4h.5 als
   **FAQ-Abschnitt „Was tun, wenn …"** im Betreiberhandbuch, je Fall
   Erkennen — Sofort — Beheben — Danach;
   Instanz-Spezifisches in der **Betriebsakte** (Vorlage im
   Betreiberhandbuch, ausgefüllt außerhalb des Repositoriums) — so erfüllt
   R41 „Zugänge dokumentiert, zweiter Admin, Wiederanlaufpaket" ohne ein
   Geheimnis in der öffentlichen Doku.
6. **Prüfmittel:** Wortliste; Ankerprüfer für Sprungmarken (Backlog-Kandidat,
   Stufe 1); Screenshot-Diff in Stufe 2.

**Begründung, kurz:** Vier Leser, vier Dokumente. Das Handbuch dort, wo
NutzerInnen sind, in der Fassung, die sie benutzen. Kurz, weil ein
Handbuch, das niemand liest, keine Doku ist.

**Wirkung:** Rahmenplan Schritt 10, Schritt 12 (P7), Abschnitt 6,
Abschnitt 7 (R16, R41, R72), Abschnitt 10 — in 6.2.6.

### E-PV-9 — Problemsammlung als Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel" (03.09.2026)

**Kern (für Abschnitt 7 als R73):** Die Problemsammlung vom 03.09.2026 wird
**Schritt 8, S9 — Einsatzbearbeitung und Rettungsmittel**, mit eigenem
Konzept nach K1 (Fable; Mockups PS-3/PS-5 und Zielkonflikt PS-8.2 als
Fable-Schritte), Backlog Nr. 101–113, Vorbereitung in
`docs/konzepte/Vorbereitung-S9-Problemsammlung.md`. S9-Konzept nach dem
S8-Konzept (Nr. 74), Umsetzung parallel zulässig; P5 setzt S9 nicht
voraus, P6 schon. Die Schritte 8–13 rücken auf 9–14. Erste Prüffrage:
Geocoding-Quelle (PS-1). Der Zielkonflikt PS-8.2 geht in das
Bedrohungsmodell ein (Nr. 43, R69).

**Begründung, kurz:** zehn Punkte, zwei Datenmodelländerungen, zwei
Mockup-Runden — das ist ein Schritt mit Konzept, und er muss vor dem Review
liegen, sonst prüft der Review Code, der sich danach noch ändert.

**Wirkung:** Rahmenplan Abschnitt 3 (neue Zeile und neuer Block Schritt 8,
Nummern 9–14), Abschnitt 4 (Berührungen S8/S9), Abschnitt 5 (Nr. 101–113),
Abschnitt 6 (F3–F6), Abschnitt 7 (R73), Abschnitt 10; `docs/Backlog.md`
101–113 — in 6.2.9.

---

## 6. Einfügeblöcke für den Rahmenplan

Für eine Instanz mit Zugriff auf das Repositorium. Jede Änderung nennt
Datei, Stelle, alten Text (wo er ersetzt wird) und neuen Text. Alle
Änderungen einer Runde bilden **eine** Rahmenplan-Fassung und **einen**
Commit (Nachricht deutsch, ohne Versionsangabe — es gibt keine). Vor dem
Push die Gegenproben aus 6.3.

### 6.1 Sofort einpflegbar (unabhängig von Entscheidungen)

**6.1.1 Berichtigung B — Nr. 83 im Schritt-10-Block.**
Datei `docs/Rahmenplan.md`, Abschnitt 3, Block „Schritt 10 — Planung v1.0",
Absatz beginnend mit „**Dazu ein Punkt, der genau hierhin gehört und
nirgendwo sonst: die Haltbarkeit der Gerätestatistik (Backlog Nr. 83).**"
bis „… er ist vor dem Schnitt zu entscheiden, nicht danach." — **ersetzen
durch:**

> Die Haltbarkeit der Gerätestatistik (Nr. 83), mit Fassung 21 hierher
> gelegt, ist mit Fassung 22 als **R64** entschieden (Momentaufnahme am
> Einsatz, Umsetzung im S4-Rest) und steht hier nicht mehr an.

**6.1.2 Statuszeile Schritt 10 im Fahrplan.**
Datei `docs/Rahmenplan.md`, Abschnitt 3, Tabellenzeile `| 10 | **Planung
v1.0** | …`, Spalten „Voraussetzung", „Konzept" und „Status" — **ersetzen
durch:**

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 10 | **Planung v1.0** | Festlegungen vor dem Schnitt: Store-Verteilung (R65), Update-Weg (R66), Auslieferungskette (R67), Repositorium (R68), Code-Review (R69), Web-App auf Android (R70), Phasenschnitt (R71), Doku-Anforderungen (R72); Ergebnis sind die Konzepte der Phasen P6–P8 mit je eigenem Paketschnitt | Festlegungen: keine (vorgezogen); Paketschnitte: die jeweilige Vorphase, P6 nach der Freigaberunde des Reviews | `docs/konzepte/Konzept-Planung-v1.0.md` | Fable (R14) | **Festlegungen entschieden** 03.09.2026 (R65–R72); offen nur die Paketschnitte je Phasenkonzept |

**6.1.3 Verweis in Abschnitt 6.**
Datei `docs/Rahmenplan.md`, Abschnitt 6, Zeile „Planungsgespräch v1.0:
Code-Review-Umfang, Aufteilung in Repositorien, Auslieferungskette,
Update-Weg (R59, R60) | Schritt 10 | nach P5, vor jedem P6-Paket" —
**ersetzen durch:**

| Was | Wofür | Wann |
|---|---|---|
| Festlegungen der Planung v1.0: entschieden als R65–R72 (`docs/konzepte/Konzept-Planung-v1.0.md`); Paketschnitte je Phase (R71) | Schritt 10 | erledigt 03.09.2026 |

**6.1.4 Änderungsverlauf.** Datei `docs/Rahmenplan.md`, Abschnitt 10,
neue letzte Zeile (die Anhänge aus 6.2.x.„Abschnitt 10" sind hier bereits
zusammengeführt — **nur diese Zeile einfügen**, die Einzelanhänge
entfallen):

| Fassung | Datum | Was |
|---|---|---|
| **26** | *03.09.2026* | **Schritt 10 vorgezogen und entschieden** (03.09.2026, Konzept `docs/konzepte/Konzept-Planung-v1.0.md`): **R65** Store-Verteilung in zwei Stufen — interner Play-Test-Track ab Schritt 6, Produktion mit Welle 1; Organisationskonto der Gen-EM GbR, Versionscode-Versatz (Nr. 98), Signaturschlüssel zu Play App Signing, Seitenladung bis zur Produktionsfreigabe; sieben Zuarbeiten, D-U-N-S sofort; E-R45-6 ersetzt; Abschnitt 1 und Betriebsübergang angepasst · **R66** Update-Weg: keine Selbstprüfung, kein Selbst-Update, Produktion nur auf Handauslösung, nur ausstehende Migrationen sichtbar (Nr. 77 damit für S8 beantwortet), Register beginnt bei v1.0 neu (Nr. 99) · **R67** Auslieferungskette: Staging automatisch und Prüfumgebung, Freigabe- und Backup-Tor, Rollback, Prüftor in drei Stufen, Android-Signatur außerhalb der CI (Nr. 100) · **R68** Repositorium: eines, frisch, öffentlich, `gen-em/nadoku`; P8-Paket „Repo-Umzug und Inventur" · **R69** Review-Umfang: alles in zwölf Stücken, Bedrohungsmodell zuerst, Kommentardurchgang ohne Beschluss- und Fassungsverweise, zwei Wege für Funde (Sofortpaket / Pflichtpaket P6), Paketschnitt nach der Freigaberunde; R13 und R31 gehen darin auf · **R70** Web-App-Manifest: Manifest allein, in P7, „NAdoku Web" mit eigenem Symbol; Nr. 87 als Erhebung erledigt · **R71** Phasenschnitt: P6 Review und Bereinigung, P7 Gesicht v1.0, P8 Schnitt — Schritt 11 in die Schritte 11–13 geteilt, alle P6-Nennungen zugeordnet · **R72** Doku-Anforderungen: vier Dokumente nach Zielgruppe, Handbuch reist mit dem Release als HTML, erzeugte Screenshots, kurz und prägnant; Betreiberhandbuch generisch mit Notfall-FAQ und Betriebsakte. **R73** Problemsammlung als Schritt 8 S9 — Einsatzbearbeitung und Rettungsmittel: dreizehn Punkte Nr. 101–113, Konzept mit Fable nach dem S8-Konzept, Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`; Schrittnummern 8–13 → 9–14. Zusatz-Konzept `Konzept-S5-Zusatz-Wartungsmodus.md` (Paket W) angelegt, der Torwächter in P5 hängt daran. **Berichtigt:** der Schritt-10-Absatz zu Nr. 83 nannte den Punkt als offen, obwohl Fassung 22 ihn als R64 entschieden hatte. Abschnitte 1, 2, 3, 4, 5, 6, 7 entsprechend; Backlog 98–105 angelegt. |

**Kopfzeile:** „Fassung 24 (03.09.2026)" auf die neue Fassung und das
Datum setzen; die Standzeile („`main` trägt …") bleibt, wenn sich am Code
nichts geändert hat.

### 6.2 Je Entscheidung (Übersicht; die Blöcke folgen in Entstehungsreihenfolge, die Anwendungsreihenfolge steht in 6.4)

- **6.2.1 F-PV-1** → **R66** — **fertig, siehe unten.**
- **6.2.2 F-PV-2** → **R68** — **fertig, siehe unten.**
- **6.2.3 F-PV-3** → **R67** als Nachtrag zu R40; Abschnitt 6 Staging-Zeile; Schritt 11; Backlog (Play-API-Upload nach v1.0).
- **6.2.4 F-PV-4** → **R69** — **fertig, siehe unten.**
- **6.2.5 F-PV-5** → **R70** — **fertig, siehe unten.**
- **6.2.6 F-PV-6** → **R72** — **fertig, siehe unten.**
- **6.2.8 F-PV-8 Phasenschnitt** → **R71** — **fertig, siehe unten;** ersetzt die Schritt-11-Teile von 6.2.1, 6.2.2, 6.2.3, 6.2.4.
- **6.2.9 F-PV-9 Problemsammlung** → **R73** — **fertig, siehe unten;** neuer Schritt 8 (S9), Schrittnummern 8–13 → 9–14, Backlog 101–113.
- **6.2.7 F-PV-7** → **R65** — **fertig, siehe unten** (einschließlich (e)).

*Die R-Nummern sind Vorschläge in Reihenfolge der Entscheidung; die
einpflegende Instanz vergibt die nächste freie Nummer nach Abschnitt 7 und
nummeriert nie um.*

#### 6.2.1 Einfügeblöcke zu E-PV-2 (R66)

**6.2.1.1 Abschnitt 7, Register.** Neue Zeile hinter R65:

| Nr. | Kern | Status |
|---|---|---|
| R66 | **Update-Weg ab v1.0** (Beschluss 03.09.2026, E-PV-2; beantwortet R60): keine Selbstprüfung auf neue Fassungen, kein Selbst-Update — die Installation ändert ihren Code nie selbst, neuer Code kommt nur über die Auslieferungskette (R40, R67); **Produktion nur auf ausdrückliche Auslösung der Betreiberin, nie automatisch**, damit vorher Backups geprüft werden können; Wartungsseite zeigt nur ausstehende Migrationen mit „Ausstehende ausführen", der Torwächter liest dasselbe Register, ausgeführte Kennungen ab P5 im Audit-Protokoll; Migrationsregister beginnt bei v1.0 neu. Selbsthoster: Release-Archiv, FTP, Migrationen von Hand (Betreiberhandbuch). Fassungsprüfung auf Klick als Nr. 99 nach v1.0. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-2 | gilt; Unterseite in S8 (Nr. 77), Audit in P5, Neubeginn des Registers in P6 |

*Zeile R60, Spalte „Kern", letzter Satz* „Der Update-Weg der Installation
(Selbstprüfung gegen das Repositorium, Benachrichtigung, Einspielen selbst
oder per FTP, Sichtbarkeit der Migrationsliste) wird in der Planung v1.0
entschieden" → „Der Update-Weg ist mit **R66** entschieden". *Spalte
„Status":* „gilt, Schritt 10" → „gilt; Update-Weg entschieden (R66)".

**6.2.1.2 Abschnitt 5, Nr. 77.** Spalte „Bemerkung": „Schnitt im Konzept;
Ort der Migrationsliste hängt an R60" → „Schnitt im Konzept; **Unterseite
„Migrationen" zeigt nur Ausstehende mit „Ausstehende ausführen", der
Torwächter liest dasselbe Register (R66)**; ausgeführte Kennungen bis P5
eingeklappt, danach im Audit-Protokoll". Neue Zeile:

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 91 | Fassungsprüfung auf Klick der Administratorin (GitHub-Releases) | nach v1.0 | R66, Option A2; nur wenn Selbsthoster es verlangen; kein Hintergrundlauf |

**6.2.1.3 Schritt 7 (S8), Block, Inhalt (2).** „… Aufteilung der
Wartungsseite (etwa Serverbetrieb und Jobs, Sicherung, Migrationen), Ort
der Migrationsliste" → „… Aufteilung der Wartungsseite (Serverbetrieb und
Jobs, Backup, **Migrationen — nur Ausstehende mit Handlung, R66**)".

**6.2.1.4 Schritt 10, Block.** Der Teilsatz „**Update-Weg der Installation
ab v1.0** (R60): Prüft die Installation selbst gegen das Repositorium und
meldet eine neue Fassung? Spielt sie das Update selbst ein, oder bleibt es
beim Hochladen per FTP? Muss die Migrationsliste auf der Wartungsseite
sichtbar bleiben?" — **ersetzen durch:** „**Update-Weg der Installation ab
v1.0** (R60) — entschieden als **R66** (E-PV-2): keine Selbstprüfung, kein
Selbst-Update, Produktion nur auf Handauslösung, nur ausstehende
Migrationen sichtbar." Der Rest des Satzes („Fest steht: …") bleibt.

**6.2.1.5 Schritt 11 (P6), Block.** Nach „**Neuaufsetzen** (R40): frische
Installation," einfügen: „**Migrationsregister beginnt neu** — die bis
dahin gelaufenen Migrationen gehen in die Grundfassung von `schema.sql`
(R66),".

**6.2.1.6 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R66** Update-Weg ab v1.0 (E-PV-2): keine Selbstprüfung, kein
> Selbst-Update, Produktion nur auf Handauslösung der Betreiberin, nur
> ausstehende Migrationen sichtbar (Nr. 77 damit für S8 beantwortet),
> Register beginnt bei v1.0 neu; Backlog 99 angelegt.

**6.2.1.7 `docs/Backlog.md`.** Neuer Punkt hinten (Nummer 99):

> 99. **Fassungsprüfung auf Klick.** Ein Knopf „Auf neue Fassung prüfen"
> auf der Wartungsseite, der einmalig die GitHub-Releases-Schnittstelle
> fragt — kein Hintergrundlauf, kein Banner (Rahmenplan R66, Option A2).
> Nur, wenn Selbsthoster es verlangen; die eigene Installation braucht es
> nicht, weil Betreiberin und Entwicklung dieselben sind. Nach v1.0.

#### 6.2.2 Einfügeblöcke zu E-PV-4 (R68)

**6.2.2.1 Abschnitt 7, Register.** Neue Zeile hinter R67:

| Nr. | Kern | Status |
|---|---|---|
| R68 | **Ein Repositorium, frisch, öffentlich** (Beschluss 03.09.2026, E-PV-4; beantwortet den Repositorien-Teil von R59): v1.0 lebt in **`gen-em/nadoku`** (öffentlich, AGPL-3.0) ohne Git-Historie; `gen-em/einsatzdoku-luftrettung` wird archiviert und verweist weiter. Drei Zählungen bleiben, Tags mit Präfix je Zählung, Pfadfilter in der Kette (R67). `main` nur über Pull-Request mit grüner Stufe 1. Der Umzug ist das letzte P6-Paket **„Repo-Umzug und Inventur"**: Durchsicht von `tools/`, `docs/`, `.github/`, `CLAUDE.md` — was wandert, mit Begründung je Weglassung; `docs/konzepte/erledigt/` bleibt im Archiv. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-4 | gilt; Umzug in P6, zusammen mit dem Neuaufsetzen |

*Zeile R59, Spalte „Status":* „gilt, Schritt 10" → „gilt; Repositorien
entschieden (R68), Update-Weg (R66), Auslieferungskette (R67)".

**6.2.2.2 Abschnitt 1.** „und frischen Repositorien (ob eines oder mehrere,
entscheidet die Planung vor v1.0, R59)" → „und einem frischen Repositorium
`gen-em/nadoku` (R68)".

**6.2.2.3 Fahrplan, Schritt 11.** *Tabellenzeile, Spalte „Inhalt":*
„Review, Umbenennung, Doku, Neuaufsetzen, Repositorien" → „Review,
Umbenennung, Doku, Neuaufsetzen, Repo-Umzug und Inventur (R68)". *Block
„Schritt 11 — P6", Ziel:* „Neuer Name, neue Repositorien nach dem Schnitt
aus Schritt 10, Version 1.0" → „Neuer Name, neues Repositorium
`gen-em/nadoku` (R68), Version 1.0". *Block, am Ende des Inhalts vor
„Abnahme nach R11" einfügen:*

> · **Repo-Umzug und Inventur** als letztes Paket (R68): Durchsicht von
> `tools/`, `docs/`, `.github/`, `CLAUDE.md` — was in den ersten Commit
> von `gen-em/nadoku` kommt, mit Begründung je Weglassung; Umgebungen und
> Zweigschutz einrichten; Altrepositorium archivieren mit Verweis

**6.2.2.4 Schritt 10, Block.** „**Aufteilung in Repositorien** — ob
Web/Server, Garmin-Uhr, Android und Werkzeuge getrennt …" bis zum Ende
dieses Punkts — **ersetzen durch:** „**Aufteilung in Repositorien** —
entschieden als **R68** (E-PV-4): eines, frisch, öffentlich,
`gen-em/nadoku`."

**6.2.2.5 Abschnitt 6, Zuarbeiten.** Neue Zeile:

| Was | Wofür | Wann |
|---|---|---|
| GitHub: `gen-em/nadoku` anlegen (öffentlich, AGPL-3.0), Umgebungen `staging` und `produktion` mit Pflichtfreigabe, Zweigschutz für `main`; nach dem Umzug `gen-em/einsatzdoku-luftrettung` archivieren | R68, P6 | mit dem letzten P6-Paket |

**6.2.2.6 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R68** Repositorium (E-PV-4): eines, frisch, öffentlich,
> `gen-em/nadoku`; letztes P6-Paket „Repo-Umzug und Inventur"; Abschnitt 1
> und Schritt 11 angepasst.

#### 6.2.4 Einfügeblöcke zu E-PV-5 (R69)

**6.2.4.1 Abschnitt 7, Register.** Neue Zeile hinter R68:

| Nr. | Kern | Status |
|---|---|---|
| R69 | **Umfang und Form des R17-Reviews** (Beschluss 03.09.2026, E-PV-5): der Review liest **alles** — `server/`, `watch/`, `android/`, `tools/`, `.github/`, Doku — **in zwölf Stücken** mit Fable, als Eingang von P6; Stück 1 ist ein **Bedrohungsmodell** als eigener Abschnitt; gesucht werden Bugs, Sicherheitslücken, ungebrauchter Code, Karteileichen, Probleme; dazu der **Kommentardurchgang** — keine Verweise auf Beschlüsse, Backlog-Nummern, Fassungen oder Konzepte mehr im Code (R13 und R31 gehen darin auf). Funde in `docs/konzepte/Review-R17.md`, zwei Wege: **kritisch → Sofortpaket, alles andere → Pflichtpaket in P6**; der Auftraggeber entscheidet je Fund in einer Freigaberunde; der P6-Paketschnitt folgt ihr. Vorbedingungen: Nr. 43-Fragen beantwortet, P5 und S5 gemergt. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-5 | gilt; Eingang von P6 |

*Zeile R17, Spalte „Status":* „gilt, Eingang von P6" → „gilt, Eingang von
P6; Umfang und Form nach **R69**". *Zeile R13, Spalte „Status":* Zusatz
„; geht im Kommentardurchgang des R17-Reviews auf (R69)". *Zeile R31,
Spalte „Status":* Zusatz „; Namensdurchgang im R17-Review (R69)".

**6.2.4.2 Fahrplan, Schritt 11.** *Block „Schritt 11 — P6", Eingangsschritt:*
„Eingangsschritt **Bug- und Sicherheitsreview mit Fable (R17)** —
einschließlich …" → „Eingangsschritt **Bug- und Sicherheitsreview mit Fable
(R17, Umfang und Form nach R69)** — alles, in zwölf Stücken, Bedrohungsmodell
zuerst, Kommentardurchgang; danach die Freigaberunde und **der
P6-Paketschnitt** (Rest von Schritt 10); die Pflichtpakete aus dem Review
kommen vor Umbenennung und Doku; einschließlich …" (der Rest des Satzes
bleibt). *Ebenda, „Kommentare normalisieren (R13, Liste in Konzept P2
10.3)":* → „Kommentare normalisieren (R13) — im Review-Durchgang (R69)".

**6.2.4.3 Schritt 10, Block.** „**Code-Review** (R17): Umfang, Reihenfolge
und Form — …" bis zum Ende dieses Punkts — **ersetzen durch:**
„**Code-Review** (R17) — entschieden als **R69** (E-PV-5): alles, in
Stücken, Eingang von P6, zwei Wege für Funde." *Statuszeile Schritt 10
(6.1.2) ergänzen:* „P6-Paketschnitt nach der Freigaberunde des Reviews".

**6.2.4.4 Abschnitt 5, Nr. 43.** Spalte „Bemerkung": „Weg B (Schlüssel auf
das Gerät) entscheidet der R17-Review" bleibt; Zusatz „(R69, Stück 2;
eigene R-Nummer nach der Freigaberunde)".

**6.2.4.5 Abschnitt 6.** *Zeile „Drei Fragen aus `Konzept-V1-Ortsdaten.md`":*
Spalte „Wann": „vor dem R17-Review" → „vor dem R17-Review — spätestens mit
dem Abschluss von P5 (R69)". *Neue Zeile:*

| Was | Wofür | Wann |
|---|---|---|
| Fable-Instanz mit Repositoriumszugriff (Claude Code) für den Review in zwölf Sitzungen; `docs/konzepte/Review-R17.md` als Sammelstelle | R17, R69 | Eingang von P6 |

**6.2.4.6 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R69** Review-Umfang (E-PV-5): alles in zwölf Stücken, Bedrohungsmodell
> zuerst, Kommentardurchgang ohne Beschluss- und Fassungsverweise, zwei
> Wege für Funde (Sofortpaket / Pflichtpaket P6), Paketschnitt nach der
> Freigaberunde; R13 und R31 gehen darin auf.

**6.2.4.7 `CLAUDE.md` (erst im neuen Repositorium, P6).** Kommentarregel:
„Kommentare nennen den Grund in Worten. Keine Nummern von Beschlüssen,
Backlog-Punkten oder Fassungen und keine Konzeptnamen im Code — die
Rückverfolgung leisten Changelog und Rahmenplan-Archiv." Nicht jetzt
ändern: Bis zum Review gilt die heutige Regel, sonst entstehen zwei
Kommentarstile nebeneinander.

#### 6.2.5 Einfügeblöcke zu E-PV-6 (R70)

**6.2.5.1 Abschnitt 7, Register.** Neue Zeile hinter R69:

| Nr. | Kern | Status |
|---|---|---|
| R70 | **Web-App-Manifest** (Beschluss 03.09.2026, E-PV-6; erledigt die Erhebung zu Nr. 87): die Weboberfläche wird als installierbare Web-App ausgeliefert — **Manifest allein, kein Service Worker** (Chrome auf Android verlangt seit Version 108 keinen; kein Cache, keine alten Dateien), **in P7 mit der Umbenennung**, Name „NAdoku Web", eigenes Symbol (gleicher Hubschrauber wie die Handy-App, andere Hintergrundfarbe, Browser-Marke; der Tracker bekommt eine GPS-Nadel); Entwurf im P7-Konzept. Nachweis am S24 mit Chrome, Samsung Internet und Firefox sowie auf einem iPhone (Safari) — für iPhone-NutzerInnen die einzige App-Form. R44 gilt unverändert (eigenes Fenster = eigener Tab). Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-6 | gilt; P7 |

**6.2.5.2 Abschnitt 5, Nr. 87.** Spalte „gehört zu": „Backlog-Runde
(Erhebung), Entscheidung in Schritt 10" → „**P7** (R70)"; Spalte
„Bemerkung": → „Manifest allein, kein Service Worker; Name „NAdoku Web",
eigenes Symbol; Erhebung erledigt (R70)".

**6.2.5.3 Schritt 10, Block.** „**Web-App auf Android** (Nr. 87): …" bis zum
Ende dieses Punkts — **ersetzen durch:** „**Web-App auf Android** (Nr. 87)
— entschieden als **R70** (E-PV-6): Manifest allein, in P7."

**6.2.5.4 Abschnitt 6, Zuarbeiten.** Neue Zeile:

| Was | Wofür | Wann |
|---|---|---|
| Wahl der Symbole für Handy-App und Web-App (gleicher Hubschrauber, zwei Hintergrundfarben, GPS-Nadel / Browser-Marke) aus dem Entwurf im P7-Konzept; iPhone für den Safari-Nachweis | R70, P7 | mit dem P7-Konzept |

**6.2.5.5 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R70** Web-App-Manifest (E-PV-6): Manifest allein, in P7, „NAdoku Web"
> mit eigenem Symbol; Nr. 87 erledigt als Erhebung.

#### 6.2.6 Einfügeblöcke zu E-PV-8 (R72)

*Mechanik 4h.4 und Notfall-FAQ mit Betriebsakte (4h.5) am 03.09.2026
bestätigt — anwendbar.*

**6.2.6.1 Abschnitt 7, Register.** Neue Zeile hinter R71:

| Nr. | Kern | Status |
|---|---|---|
| R72 | **Anforderungen an die Doku-Neufassung** (Beschluss 03.09.2026, E-PV-8; beantwortet das Anforderungsgespräch aus R16): vier Dokumente nach Zielgruppe — Handbuch (NutzerIn), Betreiberhandbuch mit Notfall-FAQ und Betriebsakte-Vorlage (generisch, ohne Zugänge), Installation und Selbsthosting, Technik mit Bedrohungsmodell — dazu der Vertrag; Markdown mit Sprungmarken; **das Handbuch reist als statisches HTML mit jedem Release** in die Installation (Link „Hilfe"), nicht von GitHub zur Laufzeit; Screenshots erzeugt (1920×1080, 414×896), Uhr drei Simulatorbilder, Handy aus dem Gerätetest; kurz und prägnant — je Aufgabe ein Bild, Referenz im Anhang, keine Fassungsgeschichte; Abnahmemaß höchstens ein Drittel des heutigen Umfangs. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-8 | gilt; Umsetzung P7 |

*Zeile R16, Spalte „Status":* „gilt, P6" → „gilt; Anforderungen R72,
Umsetzung P7". *Zeile R41, Spalte „Status" (nach 6.2.8.5):* Zusatz
„; Betreiberhandbuch generisch mit Notfall-FAQ, Zugänge in der Betriebsakte
außerhalb des Repositoriums (R72)".

**6.2.6.2 Schritt 10, Block.** „Dazu die Doku-Anforderungen nach R16, wenn
das Gespräch dazu noch aussteht." → „Die Doku-Anforderungen nach R16 sind
entschieden als **R72** (E-PV-8)."

**6.2.6.3 Abschnitt 6, Zuarbeiten.** *Zeile „Anforderungsgespräch
Doku-Neufassung":* durchstreichen, Zusatz „erledigt 03.09.2026 (R72)".
*Neue Zeilen:*

| Was | Wofür | Wann |
|---|---|---|
| Drei repräsentative Uhr-Darstellungen benennen (welche Bildschirme) und Handy-Screenshots aus dem Gerätetest mit dem Demo-Konto | R72, P7 | mit dem P7-Konzept |
| Betriebsakte der eigenen Installation ausfüllen (Hoster, Domain, Mail, Aufsichtsbehörde, zweiter Admin, Ablageort des Wiederanlaufpakets, Play Console) — außerhalb des Repositoriums | R41, R72 | vor der Öffnung |

**6.2.6.4 Schritt 12 (P7), Block (aus 6.2.8.3).** Der Satz
„**Doku-Neufassung nach R72** (Handbuch, Betreiberhandbuch, Installation
und Selbsthosting, Technik; Screenshots erzeugt, Sprungmarken)" steht dort
bereits; ergänzen: „; das Handbuch als statisches HTML in der Kette nach
`server/hilfe/` gerendert und mit dem Release ausgeliefert, Link „Hilfe"
in Fußzeile und Anmeldeseite".

**6.2.6.5 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R72** Doku-Anforderungen (E-PV-8): vier Dokumente nach Zielgruppe,
> Handbuch reist mit dem Release als HTML, erzeugte Screenshots, kurz und
> prägnant; Betreiberhandbuch generisch mit Notfall-FAQ und Betriebsakte.

#### 6.2.8 Einfügeblöcke zu E-PV-7 (R71) — Phasenschnitt

**Diese Blöcke ersetzen alle früheren Blöcke dieses Konzepts, die auf
„Schritt 11 (P6)" zielten:** 6.2.1.5, 6.2.2.3 (Block-Teile), 6.2.3.4,
6.2.4.2 sind **hier aufgegangen** und werden nicht mehr gesondert
angewendet. Die Register-Zeilen (6.2.1.1, 6.2.2.1, 6.2.3.1, 6.2.4.1)
bleiben, mit den Status-Korrekturen aus 6.2.8.5.

**6.2.8.1 Fahrplan, Tabelle.** *Zeile 10, Spalte „Inhalt":* „… Ergebnis ist
das P6-Konzept" → „… Ergebnis sind die Konzepte der Phasen P6–P8 (R71)".
*Zeile 11 ersetzen durch drei Zeilen:*

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 11 | **P6 — Review und Bereinigung** | Bedrohungsmodell; Bug- und Sicherheitsreview in zwölf Stücken (R17, R69); Freigaberunde; Sofortpaket; Pflicht- und Aufräumpakete; Kommentardurchgang (R13, R31); Weg B (Nr. 43); R5-Ausnahmeliste | Schritt 9; Nr. 43-Fragen beantwortet; S5 samt Zusatz W gemergt | `docs/konzepte/Review-R17.md`, Paketschnitt nach der Freigaberunde | Fable (Review, Kryptographie), sonst Opus | offen |
| 12 | **P7 — Gesicht v1.0** | Umbenennung überall, neues Demo-Passwort (R25); Vertrag v1 (R12, Nr. 23); Doku-Neufassung (R16, R72); Web-App-Manifest (Nr. 87, R70); Changelog neu (R15); Backlog-Übernahme; Altformat der Sicherung abschaffen (Nr. 46); Kommentarregel `CLAUDE.md` (R69) | Schritt 11 | eigenes Konzept nach K1 | Opus | offen |
| 13 | **P8 — Schnitt** | Neuaufsetzen (R40 (3)); Migrationsregister neu (R66); Repo-Umzug und Inventur (R68); Kette im neuen Repositorium (R67, R40 (4)); Rechts- und Betreiberunterlagen (R41); Abnahme nach R11; Erklärung v1.0 | Schritt 12 | eigenes Konzept nach K1 | Opus | offen |

*Zeile „Betriebsübergang" bleibt dahinter.*

**6.2.8.2 Fahrplan, Block Schritt 10.** „**Ergebnis:** das P6-Konzept nach
K1 mit Paketschnitt und Abnahmekriterien; bis dahin beginnt kein
P6-Paket." → „**Ergebnis:** je Phase ein Konzept nach K1 mit Paketschnitt
und Abnahmekriterien (P6 nach der Freigaberunde des Reviews, R69); bis
dahin beginnt kein Paket der Phase. Die Festlegungen dieses Schritts sind
seit dem 03.09.2026 vorgezogen und in R65–R72 entschieden."

**6.2.8.3 Fahrplan, Block Schritt 11 — vollständig ersetzen durch drei
Blöcke:**

> ### Schritt 11 — P6 Review und Bereinigung
>
> **Ziel:** sauberer Code, Verhalten unverändert außer bei Funden.
> **Inhalt:** Eingangsschritt **Bug- und Sicherheitsreview mit Fable (R17,
> Umfang und Form nach R69)** — alles, in zwölf Stücken; Stück 1 ist das
> **Bedrohungsmodell** als eigener Abschnitt; gesucht werden Bugs,
> Sicherheitslücken, ungebrauchter Code, Karteileichen und Probleme,
> einschließlich Verschlüsselungsverfahren, Containerfassung 4, SPUR1,
> Komplettbackup und Serverschlüssel, Demo-Konstruktion (R25),
> Schlüsselablage auf dem Handy, S5-Kopplungsweg und Adress-QR, Umgang mit
> Dumps und Klartext-Koordinaten (R41, Nr. 43 — **Weg B entscheidet der
> Review**), Signaturschlüssel bei Google (R65), Geheimnisse der Kette
> (R67) · **Kommentardurchgang:** keine Verweise auf Beschlüsse,
> Backlog-Nummern, Fassungen oder Konzepte mehr im Code — Kommentare
> normalisieren (R13) und Namensdurchgang (R31) gehen darin auf ·
> **Freigaberunde:** der Auftraggeber entscheidet je Fund; danach der
> **Paketschnitt** · **Sofortpaket** für Kritisches vor allem anderen ·
> **Pflicht- und Aufräumpakete** (je Codebasis gebündelt) für alles
> andere — v1.0 wird nicht erklärt, solange ein Fund offen ist ·
> R5-Ausnahmeliste beschließen (zugeliefert: leer). **Voraussetzung:** die
> drei Fragen aus `Konzept-V1-Ortsdaten.md` beantwortet; P5 und S5 samt
> Zusatz W gemergt. **Abnahme:** Review-Dokument vollständig (zwölf Stücke,
> jeder Fund entschieden), Sofort- und Pflichtpakete abgenommen,
> Prüfmittel unverändert grün, Wortliste 0/0/0.
>
> ### Schritt 12 — P7 Gesicht v1.0
>
> **Ziel:** v1.0 sieht aus wie v1.0. **Inhalt:** Umbenennung überall (Web
> und Handbuch heißen noch „Einsatzdoku", die Uhr seit 2.0.0 „NAdoku"),
> neues Demo-Passwort mit dem Produktnamen in der Schwachwortliste (R25) ·
> Vertragsreview und Festschreibung als v1 (R12; Nr. 23) ·
> **Doku-Neufassung nach R72** (Handbuch, Betreiberhandbuch, Installation
> und Selbsthosting, Technik; Screenshots erzeugt, Sprungmarken) ·
> **Web-App-Manifest** (Nr. 87, R70: Manifest allein, „NAdoku Web",
> Symbole für Web-App und Handy-App aus dem Entwurf) · Changelog neu ab
> v1.0 (R15) · Backlog mit dauerhaften Nummern übernehmen · Altformat der
> Sicherung abschaffen (Nr. 46) · Kommentarregel in `CLAUDE.md` („Grund ja,
> Nummer nein", R69). **Konzept** nach K1, Paketschnitt dort. **Abnahme:**
> kein „Einsatzdoku" mehr außer im Archiv und Changelog; Vertrag v1
> festgeschrieben; Handbuch-Screenshots aus dem Bilderlauf; Manifest am
> S24 (Chrome, Samsung Internet, Firefox) und am iPhone nachgewiesen;
> Wortliste 0/0/0.
>
> ### Schritt 13 — P8 Schnitt
>
> **Ziel:** die frische Installation, das frische Repositorium, Version
> 1.0. **Inhalt:** **Neuaufsetzen** (R40 (3)): frische Installation,
> Übernahme des Bestandskontos per edbak über das Wegwerf-Formular (R60),
> Demo-Konto nach Runbook, Probe des Komplettbackup-Zyklus und des
> Rollbacks auf Produktiv (R67) · **Migrationsregister beginnt neu** — die
> bis dahin gelaufenen Migrationen gehen in die Grundfassung von
> `schema.sql` (R66) · **Repo-Umzug und Inventur** (R68): Durchsicht von
> `tools/`, `docs/`, `.github/`, `CLAUDE.md` — was in den ersten Commit von
> `gen-em/nadoku` kommt, mit Begründung je Weglassung; Umgebungen und
> Zweigschutz; Altrepositorium archivieren mit Verweis · **Auslieferungs-
> kette nach R67 im neuen Repositorium** (R40 (4)) · Rechts- und
> Betreiberunterlagen zur Öffnung (R41; MDR-Abgrenzung bereits vor Welle 1,
> R65) · **Abnahme nach R11:** die frische Installation liest die
> Referenz-edbak aus `tools/referenzdatensatz/referenz/` · Erklärung
> v1.0: Tags `web-v1.0.0`, `uhr-v…`, `android-v1.0.0`; danach der
> Betriebsübergang, Welle 1 (R65).

**6.2.8.4 Abschnitt 2, Deploy-Satz.** „… am P6-Schnitt einmaliges …" →
„… am P8-Schnitt einmaliges …".

**6.2.8.5 Zuordnung aller bisherigen „P6"-Nennungen** — die einpflegende
Instanz wendet diese Tabelle an; Nennungen im Rahmenplan-Archiv bleiben
unverändert (R51):

| Stelle | heute | neu |
|---|---|---|
| Abschnitt 5, Nr. 23 (Vertrag `beginn`) | P6 | **P7** |
| Abschnitt 5, Nr. 43 (Weg B) | P6 | **P6** — unverändert; Bemerkung „(R69, Stück 2)" |
| Abschnitt 5, Nr. 46 (Altformat) | P6 | **P7** |
| Abschnitt 5, Nr. 87 | Backlog-Runde / Schritt 10 | **P7** (R70, 6.2.5.2) |
| Abschnitt 6, „Neues NEF-Logo und -Favicon" | vor P6 | **vor P7** |
| Abschnitt 6, „Impressums- und Datenschutztext" | vor P6 | **vor P7** — für das Datensicherheitsformular schon früher (R65) |
| Abschnitt 6, „Drei Fragen aus `Konzept-V1-Ortsdaten.md`" | Nr. 43, P6 · vor dem R17-Review | **P6** — „vor dem R17-Review, spätestens mit dem Abschluss von P5 (R69)" |
| Abschnitt 6, „Anforderungsgespräch Doku-Neufassung" | P6 · vor dem P6-Konzept | **erledigt (R72)** — Zeile durchstreichen mit Datum |
| Abschnitt 7, R5 Status | Ausnahmeliste in P6 | **P7** (mit der Umbenennung) |
| Abschnitt 7, R11 Status | Abnahme in P6 | **P8** |
| Abschnitt 7, R12 Kern | Vertragsreview in P6 | **P7** |
| Abschnitt 7, R13 Status | gilt, P6 (Liste Konzept P2, 10.3) | **gilt, P6 — im Kommentardurchgang des Reviews (R69)** |
| Abschnitt 7, R15 Status | gilt, P6 | **gilt, P7** |
| Abschnitt 7, R16 Status | gilt, P6 | **gilt; Anforderungen R72, Umsetzung P7** |
| Abschnitt 7, R17 Status | gilt, Eingang von P6 | **unverändert** (plus „Umfang nach R69") |
| Abschnitt 7, R25 Status | P5 und P6 führen es mit | **P5, P6 (Review prüft die Konstruktion) und P7 (neues Demo-Passwort mit der Umbenennung) führen es mit** |
| Abschnitt 7, R31 Kern | Namensbeispiele raus (P6) | **P6 — im Review (R69)** |
| Abschnitt 7, R40 Kern | Neuaufsetzen am P6-Schnitt | **am P8-Schnitt** |
| Abschnitt 7, R41 Status | (Archiv: abschließende Prüfung in P6) | Status ergänzen: **„Prüfung in P8 (R71); MDR-Abgrenzung vor Welle 1 (R65)"** |
| Abschnitt 7, R59 Status | gilt, Schritt 10 | **„vorgezogen und entschieden: R65–R72 (Fassung 26); Ergebnis sind die Konzepte P6–P8 (R71)"** |
| Abschnitt 7, R66 Status (6.2.1.1) | Neubeginn des Registers in P6 | **in P8** |
| Abschnitt 7, R68 Status (6.2.2.1) | Umzug in P6 | **Umzug in P8** |
| Abschnitt 6, Zeile aus 6.2.2.5 (GitHub anlegen) | R68, P6 · mit dem letzten P6-Paket | **R68, P8 · mit dem Umzug in P8** |
| Abschnitt 6, Zeile aus 6.2.7.5 (MDR vorziehen) | nicht erst in P6 | **nicht erst in P8** |
| 6.2.4.7 `CLAUDE.md` Kommentarregel | erst im neuen Repositorium, P6 | **in P7** (nach dem Kommentardurchgang, vor dem Umzug) |
| 6.1.2 Statuszeile Schritt 10 | „der P6-Paketschnitt bleibt nach Schritt 9" | **„die Paketschnitte entstehen je Phasenkonzept (P6 nach der Freigaberunde des Reviews, R69/R71)"** |
| 6.1.3 Verweis Abschnitt 6 | „P6-Paketschnitt nach P5" | **„Paketschnitte je Phase (R71)"** |

**6.2.8.6 Abschnitt 7, Register.** Neue Zeile hinter R70:

| Nr. | Kern | Status |
|---|---|---|
| R71 | **Drei Phasen vor v1.0** (Beschluss 03.09.2026, E-PV-7): **P6 Review und Bereinigung** (Fable-Eingang; Sofort-, Pflicht- und Aufräumpakete, Kommentardurchgang, Weg B) · **P7 Gesicht v1.0** (Umbenennung, Vertrag v1, Doku-Neufassung, Manifest, Changelog, Backlog, Altformat, Kommentarregel) · **P8 Schnitt** (Neuaufsetzen, Register neu, Repo-Umzug mit Inventur, Kette im neuen Repositorium, Rechtsunterlagen, Abnahme R11, Erklärung v1.0). Je Phase ein Konzept nach K1 mit eigenem Paketschnitt; P6 → P7 → P8, nichts parallel. Alle früheren „P6"-Nennungen sind nach der Tabelle in `docs/konzepte/Konzept-Planung-v1.0.md` 6.2.8.5 zugeordnet | gilt; Schritte 11–13 |

**6.2.8.7 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R71** Phasenschnitt (E-PV-7): P6 Review und Bereinigung, P7 Gesicht
> v1.0, P8 Schnitt — Schritt 11 in die Schritte 11–13 geteilt; alle
> P6-Nennungen zugeordnet.

#### 6.2.9 Einfügeblöcke zu E-PV-9 (R73) — S9 und Schrittnummern

**6.2.9.1 Fahrplan, Tabelle.** Neue Zeile **vor** der Backlog-Runde; alle
folgenden Zeilen bekommen die Nummer + 1:

| Schritt | Kennung | Inhalt | Voraussetzung | Konzept | Modell | Status |
|---|---|---|---|---|---|---|
| 8 | **S9 — Einsatzbearbeitung und Rettungsmittel** | Problemsammlung vom 03.09.2026 (Nr. 101–113): Adresssuche und gemeinsamer Kartendialog, Rettungsmittel-Übernahme, kompaktere Buttons, Windenkacheln, Hubschrauber-Icon, Vorschlagsliste, Zielklinik ad hoc, Schloss-Kennzeichnung, Notizfeld, Kachel „GPS-Daten", neue Rettungsmittel-Arten, Tageszuordnung, Rollen | Schritt 7 (S8-Konzept, Nr. 74); F3–F6 beantwortet | neu; Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` | Fable (Konzept; Mockups PS-3/PS-5, Zielkonflikt PS-8.2), Umsetzung Opus | offen |

**6.2.9.2 Fahrplan, neuer Block vor „Schritt 9 — Backlog-Runde":**

> ### Schritt 8 — S9 Einsatzbearbeitung und Rettungsmittel
>
> **Ziel:** Die Problemsammlung vom 03.09.2026 wird in einem Zug
> analysiert, konzipiert und umgesetzt — dreizehn Punkte (Nr. 101–113) zur
> Einsatzbearbeitung, zur Ortsauswahl und zu den Rettungsmitteln; die
> Sammlung mit ihren neunzehn Entscheidungen liegt in
> `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`. **Inhalt** (Konzept
> nach K1 mit Fable, R14): (1) **zuerst der Zielkonflikt** PS-8.2 —
> Notizfeld verschlüsselt und trotzdem durchsuchbar wie die übrigen
> Felder; Optionen mit Preis, bevor entschieden wird; die Antwort geht in
> das Bedrohungsmodell des Reviews ein (Nr. 43, R69) · (2) der
> **gemeinsame Kartendialog** mit Adresssuche als eine Komponente (PS-1,
> Voraussetzung für PS-7); erste Prüffrage die Geocoding-Quelle — dieselbe
> wie die heutigen Adressvorschläge oder keine (`CLAUDE.md` 4) · (3) die
> Bugs PS-2, PS-4, PS-6, PS-10.3 · (4) die Erweiterungen PS-7 und PS-10
> mit ihren Migrationen und der Prüfung des Vertrags (R12) · (5) die
> Gestaltung PS-3, PS-5, PS-8.1, PS-9 — **Mockups zu PS-3 und PS-5 sind
> Fable-Schritte** (K2), Freigabe je Darstellung (`CLAUDE.md` 5); PS-3
> setzt die Entscheidung zu Nr. 74 aus dem S8-Konzept voraus.
> **Reihenfolge:** Konzept nach dem S8-Konzept; Umsetzung parallel zur
> S8-Umsetzung zulässig, wenn beide Konzepte ihre Berührungen benennen
> (Stylesheet, Stammdaten- und Einstellungsseiten). P5 setzt S9 nicht
> voraus, **P6 schon**. **Abnahme:** Bilderlauf in acht Breiten,
> Kreisläufe csv und edbak (Datenmodell), Wortliste, Bedienprüfung je Punkt
> auf dem Auftraggeber-Client, Handbuch nachgezogen, Register gegengezählt.

**6.2.9.3 Schrittnummern — beim Einpflegen nachziehen (alt → neu):**
Backlog-Runde 8 → **9** · P5 9 → **10** · Planung v1.0 10 → **11** · P6 11 →
**12** · P7 12 → **13** · P8 13 → **14**. Betroffen: die Fahrplan-Tabelle
(Spalten „Schritt" und „Voraussetzung"), die Blocküberschriften „Schritt
N", Abschnitt 6 („Schritt 10", „Schritt 6" bleibt), Abschnitt 7 (R59
„Schritt 10", R66/R67-Status, R69 „Eingang von P6" bleibt), die
Statuszeile aus 6.1.2, die Blöcke 6.2.8 (Schritte 11–13 → 12–14) und alle
„Schritt 9/10/11"-Nennungen in den Blöcken dieses Konzepts. Gegenprobe:
`grep -n "Schritt [0-9]" docs/Rahmenplan.md` — jede Nennung gegen die
Tabelle.

**6.2.9.4 Abschnitt 4, Berührungen.** Neue Zeile: „S8 und S9 (Umsetzung):
Stylesheet, Stammdaten- und Einstellungsseiten — beide Konzepte benennen
die Dateien; wer zuerst mergt, der andere zieht nach (K7)."

**6.2.9.5 Abschnitt 5, Backlog-Zuordnung.** Dreizehn neue Zeilen:

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 93 | Adresssuche im Kartendialog (PS-1) | S9 | Treffer setzt den Pin, Übernahme bleibt eigener Schritt (F1); Geocoding-Quelle erste Prüffrage |
| 94 | Weitere Rettungsmittel: Auswahl wird nicht übernommen (PS-2) | S9 | Bug, nur Desktop/Web (F2) |
| 95 | Kompaktere Buttons Einsatzort/Standort/Zielklinik (PS-3) | S9 | Mockups im Konzept (Fable); hängt an Nr. 74; F3–F6 offen |
| 96 | Windenkacheln fehlen bei Nullwert (PS-4) | S9 | maßgeblich ist die Auswahl als Einsatzmittel (F7) |
| 97 | Hubschrauber-Icon in der linken Leiste (PS-5) | S9 | Varianten im Konzept (Fable, F8) |
| 98 | Klinik- und Adressvorschläge in einer Liste (PS-6) | S9 | Kliniken nur im Zielklinik-Kontext, höchstens zwei (F9, F10) |
| 99 | Zielklinik per Koordinaten und Karte, ad hoc (PS-7) | S9 | wie übrige Felder (F11), kein Stammdateneintrag (F12); Migration |
| 100 | Schloss-Icon und Legende für verschlüsselte Felder (PS-8.1) | S9 | F13 |
| 101 | Notizfeld verschlüsseln, Suche bleibt (PS-8.2) | S9 | **Zielkonflikt zuerst** (F14/F18); Fable; Bedrohungsmodell, Nr. 43 |
| 102 | Kachel „Spur" → „GPS-Daten" ohne Punktzahl (PS-9) | S9 | F15; Wortliste |
| 103 | Neue Rettungsmittel-Arten mit eigenem Icon, ohne Rollen-Vorlagen (PS-10.1) | S9 | F16; Migration |
| 104 | Rettungsmittel ohne Stammdateneintrag in der Tageszuordnung (PS-10.2) | S9 | gilt nur für den Tag (F17); Suche und Filter müssen greifen |
| 105 | Rollen unmittelbar nach Auswahl bearbeitbar, Vorlagen nachladen (PS-10.3) | S9 | entfällt für Arten ohne Vorlagen (F19) |

**6.2.9.6 Abschnitt 6, Zuarbeiten.** Neue Zeile:

| Was | Wofür | Wann |
|---|---|---|
| F3–F6 zu PS-3: Screenshot des Ist-Zustands in realer Nutzungsbreite; Client und Zielbreite (Desktop / Tablet quer / hoch); ob die Beginn/Ende-Anzeige Uhrzeiten zeigt; Zusatzmerkmal neben der Farbe (Sonnenlicht, Farbfehlsichtigkeit) | S9, Nr. 95 | vor dem S9-Konzept |

**6.2.9.7 Abschnitt 7, Register.** Neue Zeile hinter R72:

| Nr. | Kern | Status |
|---|---|---|
| R73 | **Problemsammlung als S9** (Beschluss 03.09.2026, E-PV-9): Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel", Konzept nach K1 mit Fable (Mockups PS-3/PS-5, Zielkonflikt PS-8.2 als Fable-Schritte), Backlog 101–113, Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`; Konzept nach dem S8-Konzept (Nr. 74), Umsetzung parallel zulässig; P5 setzt S9 nicht voraus, P6 schon; Zielkonflikt PS-8.2 geht in das Bedrohungsmodell ein (Nr. 43, R69); erste Prüffrage Geocoding-Quelle (PS-1). Schritte 8–13 → 9–14 | gilt; Konzept nach Go des Auftraggebers |

**6.2.9.8 Abschnitt 10.** An die Fassung-26-Zeile anhängen (in 6.1.4
bereits ergänzt): „**R73** Problemsammlung als Schritt 8 S9 (E-PV-9):
dreizehn Punkte Nr. 101–113, Konzept mit Fable nach S8; Schrittnummern
8–13 → 9–14."

**6.2.9.9 `docs/Backlog.md`.** Dreizehn neue Punkte 101–113 mit dem Text
der jeweiligen Ist/Soll-Absätze aus der Vorbereitung (Kurzfassung, je
drei bis fünf Sätze, mit den gefallenen F-Entscheidungen) und dem Verweis
„Rahmenplan Schritt 8 (S9), R73; Vorbereitung
`docs/konzepte/Vorbereitung-S9-Problemsammlung.md`". Backlog-Kopf:
Reservierung 101–113 für S9.

#### 6.2.3 Einfügeblöcke zu E-PV-3 (R67)

**6.2.3.1 Abschnitt 7, Register.** Neue Zeile hinter R66:

| Nr. | Kern | Status |
|---|---|---|
| R67 | **Auslieferungskette** (Beschluss 03.09.2026, E-PV-3; präzisiert R40 (4)): `main` deployt automatisch auf Staging, das zugleich Prüfumgebung ist (Demo-Konto, Referenzdatensatz, Messstand-Konto); ein Release-Tag startet den Produktiv-Lauf, der in der GitHub-Umgebung „produktion" auf die **Freigabe der Betreiberin** wartet — die Produktiv-Zugangsdaten liegen nur dort; der freigegebene Lauf **stößt zuerst das Komplett-Backup an und bricht ohne Erfolg ab**, dann Deploy, Migrationen von Hand (R66); **Rollback** = voriger Tag plus Wiederherstellung; **Prüftor in drei Stufen** (je Push statisch und Android-Build, rot = kein Merge; nach Staging-Deploy Kreisläufe, Bilderlauf, Messstand bei Tags, rot = nicht freigabefähig; Produktion nach Freigabe); Android in der CI unsigniert, Signatur und Play-Upload auf dem Rechner der Betreiberin (E-S4-16 bleibt), Play-API-Upload nach v1.0 (Nr. 100). Sobald ein Wartungsmodus existiert, umschließt er Backup, Deploy und Migrationen. Pflichtfreigaben setzen ein **öffentliches** Repositorium voraus (Bedingung an die Repositorien-Entscheidung). Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-3 | gilt; gebaut in P5 (R40 (2)), vollständig ab dem neuen Repositorium (R40 (4)) |

*Zeile R40, Spalte „Status":* „gilt; (1) läuft, (2) ab P5-Beginn, (3) und
(4) in P6" → „gilt; (1) läuft, (2) ab P5-Beginn, (3) und (4) in P6 —
**(4) präzisiert durch R67**".

**6.2.3.2 Abschnitt 6, Zuarbeiten.** *Zeile „Staging-Installation (zweites
FTP-Ziel und Datenbank …)":* am Ende ergänzen „; **samt Demo-Konto,
Referenzdatensatz und Messstand-Konto — Staging ist die Prüfumgebung
(R67)**". *Zeile „Konfigurationsgeheimnis für Mengenpfade" (Uhr-Prüfstand):*
am Ende ergänzen „; **prüfen, ob `CIQ_GERAETE_URL` als CI-Secret taugt**
(R67, Stufe 1)". *Neue Zeile:*

| Was | Wofür | Wann |
|---|---|---|
| GitHub-Umgebung „produktion" mit Pflichtfreigabe (Betreiberin) und den FTPS-Zugangsdaten der Produktion als Umgebungsgeheimnisse; GitHub-App auf dem Handy mit Push-Nachrichten | R67, Freigabe-Tor | mit dem Aufbau der Kette in P5 |

**6.2.3.3 Fahrplan, Schritt 9 (P5), Block.** Im Satz „Davor: Staging (R40
(2))" ergänzen: „Davor: Staging (R40 (2)) — **Aufbau der
Auslieferungskette nach R67**: Staging automatisch, Prüftor Stufen 1 und 2,
Umgebung „produktion" mit Freigabe- und Backup-Tor". Im Satz zum
Torwächter ergänzen: „… zeigt die Anwendung Wartung statt Fehler — **über
denselben Schalter wie ein Wartungsmodus, falls der bis dahin existiert**
(Konzept Planung v1.0, Abschnitt 7)".

**6.2.3.4 Fahrplan, Schritt 11 (P6), Block.** „Release-getriggerte
Auslieferung mit CI-Prüftor (R40 (4))" → „Auslieferungskette nach **R67**
im neuen Repositorium (R40 (4))".

**6.2.3.5 Abschnitt 10.** An die Fassung-26-Zeile anhängen:

> **R67** Auslieferungskette (E-PV-3): Staging automatisch und
> Prüfumgebung, Produktion nur nach Freigabe der Betreiberin mit
> Backup-Tor, Rollback, Prüftor in drei Stufen, Android-Signatur außerhalb
> der CI; Backlog 100 angelegt.

**6.2.3.6 `docs/Backlog.md`.** Neuer Punkt hinten (Nummer 100):

> 100. **Play-API-Upload aus der Kette.** Upload-Schlüssel als
> GitHub-Secret plus Dienstkonto der Play-API; jeder grüne Tag landet von
> selbst auf dem internen Test-Track, die Produktionsfreigabe bleibt ein
> Klick in der Play Console. Vertretbar, weil nach Play App Signing der
> Upload-Schlüssel der zurücksetzbare ist; E-S4-16 dann um den Unterschied
> App-Signaturschlüssel / Upload-Schlüssel ergänzen. Rahmenplan R67. Nach
> v1.0, wenn die Releases häufiger werden.

#### 6.2.7 Einfügeblöcke zu E-PV-1 (R65)

**6.2.7.1 Abschnitt 1, Nicht-Ziele.** Datei `docs/Rahmenplan.md`. Alter
Text: „Store-Verteilung der Clients vor v1.0 (Betriebsübergang, R41)" —
**ersetzen durch:**

> **Produktionsfreigabe** der Clients in den Stores vor v1.0
> (Betriebsübergang, R41, R65 — der interne Play-Test-Track ab Schritt 6
> ist Ziel, nicht Nicht-Ziel)

**6.2.7.2 Fahrplan, Schritt 6.** Datei `docs/Rahmenplan.md`, Abschnitt 3.
*Tabellenzeile 6, Spalte „Inhalt":* nach „Gerätetest," einfügen:
„**Play Console nach R65** (interner Test-Track für Handy und Uhr,
Versionscode-Versatz, Signaturweg),". *Block „Schritt 6 — S4 Rest",* nach
dem Satz „… Signaturschlüssel erzeugen und übergeben, erstes signiertes
APK ·" einfügen:

> **Play Console nach R65:** Organisationskonto der Gen-EM GbR ist
> eingerichtet (Zuarbeit, Abschnitt 6) · Versionscode-Versatz für das
> Uhr-Modul (Backlog 98; E-S4-02 bleibt eine Zählung) · vorhandener
> Signaturschlüssel als App-Signaturschlüssel bei Play App Signing,
> Upload-Schlüssel erzeugt und übergeben · Deklarationen, soweit der interne
> Track sie verlangt (Vordergrunddienst/Standort mit Demo-Video,
> Datensicherheitsformular) · **erstes Release auf dem internen
> Test-Track** für Handy und Uhr, Testerliste = der bekannte Kreis ·
> `android/LIESMICH.md` und Handbuch 10.1 nachgezogen — **die Karte „NAdoku
> für Android" bleibt als Rückfall bis zur Produktionsfreigabe** (R65),
> Handbuch 10.1 nennt den Track als Regelweg. **Abnahme,
> ergänzt:** Installation von Handy- und Uhr-App aus dem internen Track auf
> dem S24 und einer Wear-OS-Uhr; Update von der Seitenladungs-Fassung auf
> die Track-Fassung **ohne Neuinstallation** (gleiche Signatur).

**6.2.7.3 Betriebsübergang.** Datei `docs/Rahmenplan.md`. *Fahrplan, letzte
Zeile, Spalte „Inhalt":* „Öffnung in Wellen, Stores" → „Öffnung in Wellen;
Produktionsfreigabe in den Stores (R65)". *Block „Betriebsübergang (nach
v1.0, keine Phase)":* Satz „Verteilung der Clients über Connect-IQ-Store und
Play Store (setzt Mengenbremse und Mengengrenze aus P5 voraus, E-R45-6)" —
**ersetzen durch:**

> **Produktionsfreigabe im Play Store mit Welle 1** (R65; setzt Mengenbremse
> und Mengengrenze aus P5, die MDR-Abgrenzung und die Rechtsunterlagen nach
> R41 voraus — der interne Test-Track läuft seit Schritt 6) · **mit Welle 1
> entfällt die Seitenladung**: Karte „NAdoku für Android", `apk.php`,
> Handbuch 10.1 und die Deploy-Ausnahme `apk/` (R65) · Verteilung der
> Garmin-Uhr über den Connect-IQ-Store (R41).

**6.2.7.4 Abschnitt 5, Backlog-Zuordnung.** Neue Zeile hinter Nr. 89 (bzw.
am Ende der Tabelle):

| Nr. | Punkt (kurz) | gehört zu | Bemerkung |
|---|---|---|---|
| 90 | Versionscode-Versatz für das Uhr-Modul | S4-Rest | R65; Play verlangt je APK einen eindeutigen Code, E-S4-02 rechnet für beide Module denselben; eine Zählung bleibt |

**6.2.7.5 Abschnitt 6, Zuarbeiten.** Neue Zeilen; die bestehende Zeile
„Signaturschlüssel des APK verwahren" bleibt und bekommt am Ende den
Zusatz „; **wird nach R65 als App-Signaturschlüssel bei Play App Signing
hochgeladen** — dazu kommt ein Upload-Schlüssel, gleich verwahrt":

| Was | Wofür | Wann |
|---|---|---|
| **D-U-N-S-Nummer für die Gen-EM GbR** bei Dun & Bradstreet beantragen (kostenlos, bis zu vier Wochen); dabei klären, ob die GbR als eGbR im Gesellschaftsregister steht — sonst Gesellschaftsvertrag oder Gewerbeanmeldung bereithalten | R65, Play-Console-Organisationskonto | **sofort** — längster Vorlauf im Programm |
| Google-Konto der GbR als Kontoinhaber (keine private Adresse), Play-Console-Organisationskonto anlegen (25 USD), Identitätsprüfung; Entwicklername und öffentliche Kontaktadresse festlegen | R65 | nach D-U-N-S, vor Schritt 6 |
| Vorhandenen Signaturschlüssel bei Play App Signing hochladen, Upload-Schlüssel erzeugen und außerhalb des Repositoriums verwahren | R65, Schritt 6 | mit dem ersten Track-Release |
| Demo-Video des Vordergrunddienstes (Dauer-GPS) **auf echtem Gerät** für die Standort-Deklaration; wer es dreht, ist zu klären | R65, Schritt 6 | vor dem ersten Track-Release, falls der interne Track die Deklaration verlangt (beim Einrichten prüfen) |
| Datensicherheitsformular der Play Console — setzt die Datenschutzerklärung voraus (Zeile „Datenschutzerklärung um die Gerätekennung ergänzen") | R65 | vor dem ersten Release, das es verlangt |
| **MDR-Abgrenzung nach R41 vorziehen:** vor der Produktionsfreigabe (Welle 1), nicht erst in P6; für den internen Track nach heutiger Einschätzung nicht nötig — beim Einrichten prüfen | R41, R65 | vor Welle 1 |
| Wear-OS-Uhr — jetzt auch für die Wear-OS-Prüfrunde und den Installationstest aus dem Track | Schritt 6, R65 | vor dem ersten Uhr-Release |

**6.2.7.6 Abschnitt 7, Register.** Neue Zeile am Ende:

| Nr. | Kern | Status |
|---|---|---|
| R65 | **Store-Verteilung in zwei Stufen** (Beschluss 03.09.2026, E-PV-1; ersetzt E-R45-6): Play-Console-Organisationskonto der Gen-EM GbR (D-U-N-S); **interner Test-Track ab Schritt 6** als Regelweg für den bekannten Kreis, Handy und Uhr unter einem Eintrag; **Produktionsfreigabe erst als Welle 1** des Betriebsübergangs (R41), nach P5 und MDR-Abgrenzung; Versionscode je Modul mit Versatz (E-S4-02 bleibt eine Zählung, Nr. 98); vorhandener Signaturschlüssel wird App-Signaturschlüssel bei Play App Signing, getrennter Upload-Schlüssel — der Schlüssel liegt danach auch bei Google (R17); Seitenladung bleibt bis zur Produktionsfreigabe und entfällt mit Welle 1; Connect IQ unverändert. Begründung in `docs/konzepte/Konzept-Planung-v1.0.md`, E-PV-1 | gilt; Konto sofort, Track in Schritt 6, Produktion Betriebsübergang |

*Und in der Zeile R45, Spalte „Status":* „in Arbeit (Schritte 1 und 6)" →
„in Arbeit (Schritte 1 und 6); **E-R45-6 ersetzt durch R65**".

**6.2.7.7 Abschnitt 10.** An die Fassung-26-Zeile aus 6.1.4 anhängen:

> **R65** Store-Verteilung in zwei Stufen (E-PV-1): interner Play-Test-Track
> ab Schritt 6, Produktion erst mit Welle 1; Organisationskonto der GbR,
> Versionscode-Versatz (Backlog 98), Signaturschlüssel zu Play App Signing;
> sieben Zuarbeiten in Abschnitt 6, die erste (D-U-N-S) sofort. Abschnitt 1
> und Betriebsübergang angepasst; E-R45-6 ersetzt.

**6.2.7.8 `docs/Backlog.md`.** Neuer Punkt hinten (Nummer 98, sofern der
Backlog-Kopf keine Reservierung nennt — Gegenprobe 6.3.1):

> 98. **Versionscode-Versatz für das Uhr-Modul.** `version.properties`
> rechnet für Handy- und Uhr-Modul denselben Versionscode
> (`Haupt·10000 + Neben·100 + Korrektur`, E-S4-02). Die Play Console
> verlangt unter einem Paketnamen je APK einen eindeutigen Code — ohne
> Versatz ist kein Wear-OS-Release möglich. Das Uhr-Modul bekommt einen
> Versatz (Schema in der Umsetzung: etwa `+ 1 000 000` oder eine führende
> Formfaktor-Ziffer); Versionsname und Zählung bleiben eins. Preis: ein
> einmaliger Sprung, Neuinstallation auf der vorhandenen Uhr. Rahmenplan
> R65, Schritt 6 (S4-Rest).

**6.2.7.9 `docs/konzepte/Konzept-S4-Handy-Uhr-Client.md`.**
*Abschnitt 8 „Nicht Umfang", Punkt „Store-Verteilung …":* Zusatz
„**Nachtrag 03.09.2026 (R65):** Der interne Play-Test-Track kommt mit
Schritt 6 in den S4-Rest; die Produktionsfreigabe bleibt Betriebsübergang."
*Abschnitt 13 (Schritt-6-Inhalt):* die Punkte aus 6.2.7.2 als Arbeitspaket
mit Abnahme aufnehmen. *B-S4-04:* Zusatz „Die Entscheidung, an die die
Warnung erinnerte, ist mit R65 gefallen; sie bleibt gezählt."

### 6.3 Gegenproben vor dem Push

1. `grep -oE '^[0-9]+\.' docs/Backlog.md | tr -d '.' | sort -n | uniq -d`
   muss leer sein (nur, wenn 6.2.7 einen Backlog-Punkt anlegt).
2. Abschnitt 7 des Rahmenplans: neue R-Nummer ist die nächste freie; keine
   Lücke, keine Doppelung.
3. Kopfzeile, Abschnitt 3 und Abschnitt 10 nennen dieselbe Fassungsnummer
   (die Berichtigung von Fassung 24 zeigt, wie leicht das auseinanderläuft).
4. `tools/wortliste/` über `docs/` — Soll 0/0/0.
5. Kein Versionssprung, kein Changelog-Eintrag: Es wird nur `docs/`
   berührt (`CLAUDE.md` 2).
6. Push auf einen Arbeitszweig (K7); `main` erst nach Freigabe — dieser
   Push deployt nichts, weil `server/` unberührt bleibt, aber die Regel gilt.
7. Nach 6.2.8: `grep -n "P6" docs/Rahmenplan.md` — jede verbleibende
   Nennung muss in der Tabelle 6.2.8.5 als „unverändert" stehen oder den
   Review (R17/R69) meinen.
8. Abschnitt 10 hat **eine** neue Zeile (6.1.4); die Einzelanhänge aus den
   6.2.x-Blöcken „Abschnitt 10" sind dort schon zusammengeführt und werden
   nicht zusätzlich eingefügt.

### 6.4 Übergabe an die einpflegende Instanz · **erledigt — eingepflegt von dieser Instanz am 03.09.2026 (Fassung 26)**

**Reihenfolge der Blöcke:** 6.1 → 6.2.7 (R65) → 6.2.1 (R66) → 6.2.3 (R67)
→ 6.2.2 (R68) → 6.2.4 (R69) → 6.2.5 (R70) → 6.2.6 (R72) → 6.2.8 (R71),
weil es Schritt 11 komplett ersetzt und alle P6-Nennungen zuordnet →
**ganz zuletzt 6.2.9 (R73)**, weil es die Schrittnummern verschiebt; dabei die Schritt-11-Teile von 6.2.1.5, 6.2.2.3, 6.2.3.4 und
6.2.4.2 **nicht** gesondert anwenden (in 6.2.8.3 enthalten). Register-Zeilen
in der Reihenfolge R65 … R72.

**Dateien:** `docs/Rahmenplan.md` (Fassung 26), `docs/Backlog.md` (98–100),
`docs/konzepte/Konzept-S4-Handy-Uhr-Client.md` (6.2.7.9), dieses Konzept
nach `docs/konzepte/Konzept-Planung-v1.0.md`. **Nicht** anfassen:
`docs/Rahmenplan-Archiv.md` (R51), `CLAUDE.md` (Kommentarregel erst in P7),
`server/**`. Der Wartungsmodus-Zusatz liegt bei der Server-Instanz auf
ihrem Zweig; er wird hier nicht angelegt.

**Zweig und Merge:** Arbeitszweig von `main`; ein Commit; deutsche
Nachricht ohne Versionsangabe („Rahmenplan Fassung 26: Planung v1.0
vorgezogen, R65–R72"). Die S5-Zweige ändern beim Merge ebenfalls
`Rahmenplan.md` (Erledigt-Zeile, Abschnitt 10) — Konflikte dort sind
zeilenweise lösbar, beide Änderungen bleiben.

**Prompt für die Instanz (zum Kopieren, Konzept anhängen):**

> Bitte pflege die Ergebnisse der vorgezogenen Planung v1.0 in das
> Repositorium ein. Grundlage ist `Konzept-Planung-v1.0.md` (anhängend);
> lege es unter `docs/konzepte/` ab. Arbeite auf einem Arbeitszweig von
> `main`, nicht auf `main`. 1. Wende die Einfügeblöcke aus Abschnitt 6 in
> der Reihenfolge aus 6.4 an — 6.1, dann 6.2.7, 6.2.1, 6.2.3, 6.2.2, 6.2.4,
> 6.2.5, 6.2.6, zuletzt 6.2.8; die dort genannten Schritt-11-Teile älterer
> Blöcke nicht doppelt. 2. Kopfzeile auf Fassung 26 mit heutigem Datum;
> Abschnitt 10 bekommt genau die eine Zeile aus 6.1.4. 3. Backlog 98–92,
> Konzept S4 nach 6.2.7.9. 4. Gegenproben aus 6.3, alle acht. 5. Kein
> Versionssprung, kein Changelog, kein Deploy — nur `docs/`. 6. Push auf
> den Zweig, zeig mir den Diff der Abschnitte 1, 3 und 7 zur Bestätigung;
> `main` erst danach.

**Danach — außerhalb dieses Konzepts, in dieser Reihenfolge:**

1. **Zuarbeiten mit Vorlauf, sofort:** D-U-N-S für die Gen-EM GbR (R65);
   DNS und TLS für `nadoku.gen-em.org` (blockiert Schritt 5);
   Hosting-Entscheidung (R36, vor Schritt 9); Wear-OS-Uhr; iPhone für den
   Safari-Nachweis (R70).
2. **Server-Instanz:** Prompt aus `Konzept-S5-Zusatz-Wartungsmodus.md`,
   Abschnitt 0; F-S5W-01 bis -04 entscheiden.
3. **Offen vor v1.0, nicht Umfang hier** (Abschnitt 1): die drei Fragen aus
   Nr. 43 (Pflicht vor P6, spätestens mit dem Abschluss von P5); die
   Grundsatzfrage der Mengenbremse (R19), die P5-Vorentscheidungen (R9,
   R33, R37) — sinnvoll als kurzes eigenes Gespräch vor dem P5-Konzept;
   die Bedienhöhe am Schreibtisch (Nr. 74) mit S8.
4. **Die Paketschnitte** je Phase entstehen in den Konzepten P6, P7, P8.

---

## 7. Nachtrag — Wartungsmodus (Frage vom 03.09.2026) · **umgesetzt als Paket W, Web 13.2.0**

*Stand beim Einpflegen (Rahmenplan Fassung 25): Die Server-Instanz hat das
Zusatz-Konzept umgesetzt und mit S5 gemergt — Wartungsprobe 40/40, Backlog
96 und 97 aus E-S5W-08/-10. Stufe 3 der Kette (E-PV-3) gilt damit
unbedingt; der Torwächter in P5 ist im Rahmenplan als „hängt am
Wartungsmodus aus Paket W" eingetragen.*

**Frage des Auftraggebers:** Lässt sich die Installation für ein Update
vorübergehend in einen Wartungsmodus setzen — nicht erreichbar, nimmt keine
Daten von Endgeräten an, die liefern nach, sobald die Wartung vorbei ist?
Und: jetzt in S5 einbauen, wo drei Instanzen parallel laufen (Server;
Paket C Uhr; Paket E Android) — mit je einem Prompt?

### 7.1 Befund (am Bestand geprüft)

- **Die Endgeräte können das bereits.** `docs/JSON-Vertrag.md` Abschnitt 5,
  Fehlertabelle: **5xx → „Später unverändert erneut versuchen (Backoff)"**.
  Die Uhr leert ihren Puffer erst nach bestätigtem `next_seq`
  (Vertrag 4, „Nachzügler"). Android: E-S4-06 „Puffer und Warteschlange
  wie die Uhr", Fehlerpfad 5xx = Backoff, im S4-Prüfprotokoll geprüft
  („5xx / 503 → später erneut, nichts markiert, nichts bestätigt").
  **Ein Server, der während der Wartung mit 503 antwortet, verliert keine
  Gerätedaten — ohne dass Uhr oder Handy angefasst werden.**
- **Was fehlt, ist allein der Schalter auf dem Server.** Heute gibt es
  keinen Wartungsmodus; `install.lock` sperrt nur die Installation. Der
  Torwächter aus R40 (4) (P5) ist der **automatische** Sonderfall
  desselben Mechanismus: Wartung bei ausstehender Migration.
- **Web-NutzerInnen** bekämen eine Wartungsseite. Ein Formular, das genau
  in dem Moment abgeschickt wird, läuft auf die 503 — die Wartungsseite
  muss sagen: zurück, Eingaben bleiben im Formular, später erneut absenden.
  Wartungen dauern Minuten.

### 7.2 Urteil

1. **S5C (Uhr) und S5E (Android): kein Prompt.** Es gibt dort nichts zu
   tun. Was man später haben *könnte*: eine eigene Meldung „Server in
   Wartung" statt des allgemeinen Sendefehlers und Auswertung von
   `Retry-After` — Bequemlichkeit, kein Datenschutz. Backlog-Kandidat nach
   v1.0.
2. **Server: ja, klein und sinnvoll — aber als eigenes, abgegrenztes
   Paket, nicht als Nachtrag in die laufenden S5-Pakete.** Gründe: K4 (was
   nicht im Paket steht, wird nicht gebaut) und drei parallele Zweige. Das
   Muster gibt es: `Konzept-S5-Zusatz-Android-Ortung-Dienstende.md` —
   eigenes Konzept, eigenes Prüfdokument. Ausführen kann es die
   Server-Instanz **nach** ihrem laufenden Paket auf demselben Zweig; die
   Alternative ist S8 (Schritt 7), das die Wartungsseite ohnehin aufteilt
   (Nr. 77).
   *Empfehlung:* **Zusatz jetzt, Server-Instanz, nach Paket B.** Jeder
   S5-Merge mit Migration (`2026_09_03_kopplungssitzungen` war der erste)
   hat das Fenster, das der Schalter schließt; und er ist sofort ohne Kette
   nutzbar: Schalter an → Push → `update.php` → Schalter aus.

### 7.3 Vorschlag für den Zuschnitt (zu bestätigen)

- **Schalter:** auf der Wartungsseite `update.php`, nur Admin: „Wartungsmodus
  einschalten" / „ausschalten", mit Zeitpunkt und Konto. Zustand als Datei
  `wartung.lock` neben `install.lock` — **kein Datenbankzugriff**, damit der
  Schalter auch bei laufender oder gescheiterter Migration greift; vom
  Deploy ausgenommen wie `install.lock`.
- **Wirkung:** jede Anfrage außer den Ausnahmen → **HTTP 503** mit
  `Retry-After: 300`. Endpunkte der Geräte (`ingest.php`, `pair.php`, …):
  `{"error":"maintenance"}`. Browser: eine schlichte Wartungsseite (Logo,
  „Wartung — bitte in einigen Minuten erneut", der Formularhinweis aus
  7.1). Kein Aufruf nach außen, keine Zeitsteuerung.
- **Ausnahmen:** `update.php` samt der dafür nötigen Anmeldung (damit
  Migrationen und Ausschalten gehen); `jobs.php` mit Token (damit das
  Backup-Tor der Kette **während** der Wartung läuft — genau dann ist das
  Backup konsistent); statische Assets.
- **Kein automatisches Ausschalten.** Ein vergessener Wartungsmodus ist
  auf der Wartungsseite unübersehbar (Balken mit Einschaltzeit). Der
  Torwächter (P5) setzt später denselben Zustand automatisch bei
  ausstehender Migration — **zwei Auslöser, ein Mechanismus**.
- **Prüfung:** Kopplungs- oder Geräteprobe gegen eingeschaltete Wartung
  (503 und Body für jeden Endpunkt), Bilderlauf der Wartungsseite,
  Kreisläufe unberührt, Handbuch (Admin-Kapitel), `docs/Technik.md`
  (Betriebsablauf „Update mit Wartungsmodus"), Changelog; Register
  unverändert (keine Migration).
- **Nicht Umfang:** Ankündigung eines Wartungsfensters an NutzerInnen,
  Zeitsteuerung, Meldungen auf den Geräten (Backlog-Kandidat).
- **Wirkung auf den Rahmenplan:** Schritt 9 (P5), Torwächter „hängt am
  Wartungsmodus" (6.2.3.3 nennt es schon bedingt); E-PV-3, Stufe 3, wird
  unbedingt; Abschnitt 8 Erledigt-Zeile nach Abschluss des Zusatzes.

### 7.4 Ergebnis (Freigabe 03.09.2026: „Passt so")

Zusatz-Konzept **`docs/konzepte/Konzept-S5-Zusatz-Wartungsmodus.md`**
geschrieben (Muster des Android-Zusatzes; Nummernkreis S5W; acht
Entscheidungen E-S5W-01 bis -08 aus dem freigegebenen Zuschnitt, vier
Restfragen F-S5W-01 bis -04 mit Empfehlung; ein Paket W nach B, vor D;
Wartungsprobe mit 15 Erwartungen). Der Prompt für die Server-Instanz steht
dort in Abschnitt 0. **Kein Prompt für S5C und S5E** (7.2, Punkt 1).
Folge für dieses Konzept: E-PV-3, Stufe 3, gilt ab dem Merge von W
unbedingt (Wartung an → Backup → Deploy → Migrationen → Wartung aus); der
Einfügeblock 6.2.3.3 nennt den Torwächter bereits als „hängt am
Wartungsmodus".

---

## 8. Protokoll

| Datum | Was |
|---|---|
| 03.09.2026 | Rahmenplan Fassung 24, Archiv, `CLAUDE.md`, `deploy.yml`, Konzept S4, Android-Bauangaben gelesen. Befunde A und B. Auftrag bestätigt (Schritt 10 komplett, Einfügeblöcke für eine andere Instanz, Store formal hinein). Konzept angelegt; F-PV-7 ausgearbeitet und mit Teilfragen (a)–(g) vorgelegt. Berichtigung: Am 03.09.2026 war gesagt worden, die Medizinprodukt-Frage stehe in keinem Dokument — sie steht in R41 (Archiv) als „MDR-Abgrenzung einmal prüfen"; und die Verifizierungsfrist 30.09.2026 trifft nicht den Zielmarkt, dort gilt 2027. |
| 03.09.2026 | **F-PV-7 entschieden** (a–d, f, g) → E-PV-1, Einfügeblöcke 6.2.7 geschrieben (Rahmenplan Abschnitte 1, 3, 5, 6, 7, 10; Backlog 98; Konzept S4). (e) offen: Rückfrage „welche Download-Seite?" in 4.2 (e) beantwortet. **F-PV-1 ausgearbeitet** (4b) und mit Teilfragen (a)–(c) vorgelegt. |
| 03.09.2026 | **(e) nachentschieden:** Seitenladung entfällt mit der Produktionsfreigabe (Lesart „Veröffentlichung im Play Store" = Welle 1, zu bestätigen); 6.2.7 vervollständigt. **F-PV-1 entschieden** (A1; B1 mit Handauslösung — nach Erläuterung des WordPress-Vergleichs; C3 — nach Erläuterung) → E-PV-2, Einfügeblöcke 6.2.1 (R66, R60-Status, Nr. 77, Schritte 7/10/11, Backlog 99). **F-PV-3 ausgearbeitet** (4c) und mit Teilfragen (a)–(f) vorgelegt. |
| 03.09.2026 | **F-PV-3 entschieden** (T1 nach Klärung der Freigabe vom Handy; K2 + Checkliste; Rollback nach Erläuterung; Stufen festgehalten in R67/Workflows/Betreiberhandbuch; G1, G2 nach v1.0) → E-PV-3, Einfügeblöcke 6.2.3 (R67, R40-Status, Abschnitt 6, Schritte 9/11, Backlog 100). Bedingung an F-PV-2 notiert (öffentliches Repositorium). **Frage Wartungsmodus** geprüft: Geräte brauchen nichts (Vertrag 5xx-Regel, E-S4-06), Server-Schalter als Zusatz-Konzept vorgeschlagen (Abschnitt 7), Freigabe offen. |
| 03.09.2026 | **Zuschnitt Wartungsmodus freigegeben.** `Konzept-S5-Zusatz-Wartungsmodus.md` geschrieben (E-S5W-01–08, F-S5W-01–04, Paket W, Wartungsprobe 6.1); Prompt für die Server-Instanz in dessen Abschnitt 0. Kein Prompt für Uhr und Android. Nächste Frage: F-PV-2. |
| 03.09.2026 | **F-PV-2 ausgearbeitet** (4d: Anzahl, Sichtbarkeit, Name, Historie, Umfang des Umzugs, Zweigschutz, Zeitpunkt) und mit Teilfragen (a)–(g) vorgelegt. Befund dabei: Lizenz ist AGPL-3.0, Repositorium öffentlich — passt zu E-PV-3 und R63. |
| 03.09.2026 | **F-PV-2 entschieden** (eines, öffentlich, `gen-em/nadoku`, frisch; dazu P6-Paket „Repo-Umzug und Inventur" auf Wunsch des Auftraggebers) → E-PV-4, Einfügeblöcke 6.2.2 (R68, R59-Status, Abschnitt 1, Schritte 10/11, Abschnitt 6). **F-PV-4 ausgearbeitet** (4e: Umfang, zwei Phasen, Bedrohungsmodell zuerst, Form und Wege der Funde, Freigaberunde, Vorbedingungen); Befund: kein Bedrohungsmodell als Dokument (B-S5-02). |
| 03.09.2026 | **F-PV-4 entschieden** (U1 alles in Stücken mit Karteileichen- und Kommentardurchgang; Z1 ein Review als P6-Eingang; Bedrohungsmodell zuerst; zwei Wege kritisch/Pflichtpaket; Freigaberunde; Vorbedingungen) → E-PV-5, Einfügeblöcke 6.2.4 (R69; R13/R17/R31-Status; Schritte 10/11; Nr. 43; Abschnitt 6; `CLAUDE.md`-Kommentarregel für das neue Repositorium). **F-PV-5 ausgearbeitet** (4f); Berichtigung: Nr. 87 sagt „beides nebeneinander ist gewollt" — die Grundsatzfrage war schon beantwortet; Chrome verlangt seit Version 108 keinen Service Worker mehr (Chrome-Doku geprüft). |
| 03.09.2026 | **F-PV-5 entschieden** (M1; in P7; Symbolrichtung, Entwurf im P7-Konzept) → E-PV-6, Einfügeblöcke 6.2.5 (R70). Rückfragen des Auftraggebers beantwortet: Manifest/Service Worker erläutert; Firefox für Android, Samsung Internet und Safari als Nachweisziele; Browser-Abzeichen nicht verlässlich. **F-PV-8 nachgetragen und entschieden:** Phasenschnitt P6/P7/P8 → E-PV-7, Einfügeblöcke 6.2.8 mit vollständigem Text der Schritte 11–13 und Zuordnungstabelle aller P6-Nennungen. **F-PV-6 ausgearbeitet** (4h) und mit Teilfragen (a)–(e) vorgelegt. |
| 03.09.2026 | **F-PV-6 entschieden** (D2; O2 für das Handbuch; Screenshots 1920×1080 und 414×896, Uhr drei Simulatorbilder, Handy Gerätetest; kurz und prägnant; Betreiberhandbuch generisch ohne Zugänge) → E-PV-8, Einfügeblöcke 6.2.6 (R72). Rückfragen beantwortet: Handbuch nicht von GitHub zur Laufzeit, sondern als HTML mit dem Release (4h.4); Notfallplan mit zwölf Fällen und Betriebsakte-Vorlage (4h.5) — beides zu bestätigen. |
| 03.09.2026 | **Bestätigt:** Handbuch-Mechanik (4h.4); Notfallfälle als **FAQ im Betreiberhandbuch** mit Betriebsakte-Vorlage (4h.5) — E-PV-8 und 6.2.6 angepasst. **Abschluss:** Statuszeile Schritt 10, Verweis in Abschnitt 6 und die zusammengeführte Fassung-26-Zeile (6.1.4) final; Übergabe mit Reihenfolge, Prompt und Folgeliste in 6.4. Offen nur zwei Kleinbestätigungen (Status, „Hakt"). |
| 03.09.2026 | **Problemsammlung NAdoku** (Upload) eingeordnet: Schritt 8 „S9 — Einsatzbearbeitung und Rettungsmittel", Backlog 101–113, Bezeichner P→PS (Kollision mit Phasen), Vorbereitung `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` erzeugt; Schrittnummern verschieben (Auftraggeber), Reihenfolge S9-Konzept nach S8-Konzept (wegen Nr. 74), P6 setzt S9 voraus → E-PV-9, Einfügeblöcke 6.2.9 (R73). **Neuer Auftrag:** diese Instanz pflegt alles selbst in die aktuelle Fassung von Rahmenplan und Backlog ein, sobald sie vorliegt. |
| 03.09.2026 | **Eingepflegt.** Beim Abruf lag `main` mit Rahmenplan Fassung 25 (S5 gemergt, Paket W umgesetzt, Backlog bis 97) vor — daher Fassung 26, Backlog 98–113. Angewendet: 6.1, 6.2.7, 6.2.1, 6.2.3, 6.2.2, 6.2.4, 6.2.5, 6.2.6, 6.2.8, 6.2.9 in dieser Reihenfolge; Schrittnummern 8–11 → 9–12 und neue Schritte 13/14 im Rahmenplan (Abschnitte 8 und 10 historisch unverändert); Backlog 98–113 mit Kopfnotiz; Konzept S4 Abschnitt 8, B-S4-04 und neuer Abschnitt 13.x; `docs/konzepte/Vorbereitung-S9-Problemsammlung.md`. Gegenproben: Backlog-Doppelungen leer; Register R65–R73 lückenlos; Kopfzeile und Abschnitt 10 auf 26; P6-Nennungen geprüft; Schritt-Verweise geprüft; kein Versionssprung; Wortliste über `docs/` — siehe Chat-Protokoll. |
