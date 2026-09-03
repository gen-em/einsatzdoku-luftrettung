# Vorbereitung S9 — Einsatzbearbeitung und Rettungsmittel (Problemsammlung)

**Zielpfad:** `docs/konzepte/Vorbereitung-S9-Problemsammlung.md` — dasselbe
Muster wie `Vorbereitung-S5-Kopplung-umgekehrt.md`: die Sammlung mit den
bereits gefallenen Entscheidungen, aus der das S9-Konzept nach K1 entsteht.
**Rahmenplan:** Schritt 8 (Fassung 26), Backlog Nr. 101–113.
**Status:** Sammlung abgeschlossen, Rückfragen weitgehend geklärt — Analyse
und Konzept stehen noch aus. **Stand:** 03.09.2026.
**Vorgehen:** Repo-Analyse und Konzepterstellung erfolgen gebündelt nach
ausdrücklichem Go des Auftraggebers; Konzept mit Fable (R14), Umsetzung
Opus, Fable-Schritte im Konzept markiert (K2).
**Noch offen:** F3–F6 zu PS-3 (Screenshot Ist-Zustand, Client/Zielbreite,
Zeitstempel-Darstellung, Zusatzmerkmal neben Farbe) — Zuarbeit in
Rahmenplan Abschnitt 6, vor dem S9-Konzept.

**Bezeichner:** Die Punkte heißen hier **PS-1 bis PS-10** (in der
Ursprungsfassung P1–P10 — umbenannt, weil „P3" und „P5" im Rahmenplan
Phasen sind). Die Entscheidungen F1–F19 dieser Sammlung übernimmt das
S9-Konzept als E-S9-…; die offenen F3–F6 werden dort F-S9-….

**Zuordnung zum Backlog (Fassung 26):**

| Backlog | Punkt | Kurz | Typ |
|---|---|---|---|
| 101 | PS-1 | Adresssuche im Kartendialog | Erweiterung |
| 102 | PS-2 | Weitere Rettungsmittel: Auswahl wird nicht übernommen (Desktop/Web) | Bug |
| 103 | PS-3 | Kompaktere Buttons Einsatzort / Standort / Zielklinik; Umrandung als Beginn/Ende-Anzeige | Gestaltung (Mockups, Fable) |
| 104 | PS-4 | Windenkacheln fehlen bei Nullwert | Bug / Anzeigelogik |
| 105 | PS-5 | Hubschrauber-Icon in der linken Leiste | Gestaltung (Varianten, Fable) |
| 106 | PS-6 | Klinik- und Adressvorschläge in einer Liste | Bug / UI |
| 107 | PS-7 | Zielklinik per Koordinaten und Karte, ad hoc | Erweiterung |
| 108 | PS-8.1 | Schloss-Icon und Legende für verschlüsselte Felder | UI |
| 109 | PS-8.2 | Notizfeld verschlüsseln — Zielkonflikt Suche zuerst | Funktionale Änderung (Datenmodell, Fable) |
| 110 | PS-9 | Kachel „Spur" → „GPS-Daten", ohne Punktzahl | Wording / UI |
| 111 | PS-10.1 | Neue Rettungsmittel-Arten: Bergwachtnotarzt, Veranstaltungsnotarzt, Sonstiges | Erweiterung |
| 112 | PS-10.2 | Rettungsmittel ohne Stammdateneintrag in der Tageszuordnung | Erweiterung |
| 113 | PS-10.3 | Rollen unmittelbar nach Auswahl bearbeitbar, Vorlagen nachladen | Bug / Workflow |

**Verbindungen in den Rahmenplan (beim Einordnen festgehalten):**

- **PS-3 hängt an Nr. 74** (Bedienhöhe am Schreibtisch, Entscheidung im
  S8-Konzept): kleinere Buttons sind dieselbe Frage. Deshalb entsteht das
  S9-Konzept **nach** dem S8-Konzept.
- **PS-8.2 berührt Nr. 43 und den R17-Review (R69):** Was verschlüsselt ist
  und was durchsuchbar bleibt, ist eine Frage an das Bedrohungsmodell.
  Der Zielkonflikt wird im S9-Konzept zuerst geprüft; die Antwort geht in
  das Bedrohungsmodell (P6, Stück 1) ein. S9 liegt deshalb **vor P6**.
- **PS-1: Geocoding-Quelle.** Ein Aufruf an einen Dienst außerhalb der
  Installation berührt die Zusage „keine fremde Quelle zur Laufzeit"
  (`CLAUDE.md` 4). Erste Prüffrage der Analyse: dieselbe Quelle wie die
  heutigen Adressvorschläge (PS-6) oder keine.
- **PS-7 und PS-10** ändern das Datenmodell (Ad-hoc-Zielkliniken,
  Rettungsmittel je Tag) — Migrationen; der Vertrag (R12) ist zu prüfen,
  falls Uhr oder Handy Rettungsmittel oder Ziele übertragen.

---

## PS-1 — Adresssuche im Kartenauswahl-Dialog

**Bereich:** Ortsauswahl über Karte (Transportziel, Einsatzort etc.)

**Ist:** Im aufploppenden Kartendialog kann kein Ort per Adresse gesucht werden.

**Soll:** Adress-/Ortssuche im Kartendialog verfügbar.

**Entscheidung (F1):** Ein Klick auf einen Suchtreffer **setzt den Pin**, übernimmt den
Ort aber **nicht** direkt. Die Übernahme bleibt ein eigener, bestätigender Schritt.

**Bei Analyse zu prüfen:** Geocoding-Quelle (Datenschutz, Offline-Fähigkeit im
Einsatzbetrieb).

---

## PS-2 — Weitere Rettungsmittel: Auswahl wird nicht übernommen

**Bereich:** Eingabe „weitere Rettungsmittel"

**Ist:** Die Suche im hinterlegten Stand liefert korrekte Treffer. Ein Klick auf ein
angebotenes Rettungsmittel schließt den Dialog, das Rettungsmittel wird jedoch **nicht
übernommen**.

**Soll:** Klick auf einen Treffer übernimmt das Rettungsmittel in den Einsatz.

**Entscheidung (F2):** Betrifft **nur Desktop/Web**.

**Typ:** Bug

---

## PS-3 — Kompaktere Buttons Einsatzort / Standort / Zielklinik

**Bereich:** Buttonleiste Einsatzort, Standort, Zielklinik

**Soll:**
- Buttons „Einsatzort", „Standort" und „Zielklinik" sollen kleiner werden.
- Prüfidee: Die farbige Umrandung der Icons von Standort & Zielklinik zur Anzeige
  **Einsatzbeginn / Einsatzende** verwenden — anstelle der bisherigen dunkelblauen
  Umrandung. Ziel: Platzersparnis durch Wegfall einer separaten Anzeige.
- Offen, ob das gestalterisch trägt.
- Icon-Größe soll bei der Ausarbeitung separat justiert werden können.

**Liefergegenstand:** Mockups / Screenshots mehrerer Optionen, inkl. Varianten der
Icon-Größe, zur Auswahl vor der Umsetzung.

**Entscheidung (F8, gilt auch hier):** Mockups werden **erst bei der Konzepterstellung**
erzeugt, nicht vorab.

**Noch offen:**
- **F3** Screenshot Ist-Zustand in realer Nutzungsbreite
- **F4** Client und Zielbreite (Desktop / Tablet quer / Tablet hoch)
- **F5** Zeigt die Einsatzbeginn/Einsatzende-Anzeige aktuell nur den Zustand oder auch
  Uhrzeiten? Bei sichtbaren Uhrzeiten kann eine Umrandung sie nicht ersetzen.
- **F6** Zusätzliches nicht-farbliches Merkmal (Randstärke, gefüllt/ungefüllt) neben der
  Farbe — wegen Sonnenlicht im Cockpit und Farbfehlsichtigkeit

---

## PS-4 — Windenkacheln fehlen bei Nullwert

**Bereich:** Einsatzübersicht, Monats- und Jahresansicht

**Ist:** Windenkacheln werden nicht angezeigt, wenn im dargestellten Zeitraum keine
Windeneinsätze geflogen wurden.

**Soll:** Sobald im angezeigten Zeitraum ein Hubschrauber **mit Winde** als Einsatzmittel
ausgewählt war, werden die Windenkacheln angezeigt — auch mit Anzeigewert „0".

**Entscheidung (F7):** Maßgeblich ist die **Auswahl als Einsatzmittel**, unabhängig davon,
ob damit Einsätze geflogen wurden. Auch bei 0 Einsätzen erscheinen die Kacheln.

**Typ:** Bug / Anzeigelogik

---

## PS-5 — Helikopter-Icon in der linken Leiste

**Bereich:** Linke Leiste, neben den Tagesdaten

**Ist:** Das aktuelle Helikopter-Icon überzeugt nicht.

**Soll:** Alternative suchen bzw. gestalten.

**Entscheidung (F8):** Icon-Varianten und Mockups werden **erst im Rahmen der
Konzepterstellung** gerendert, nicht jetzt.

**Typ:** Gestaltung

---

## PS-6 — Klinik- und Adressvorschläge überlagern sich

**Bereich:** Adressfeld mit Klinik-Suche

**Ist:** Die Klinikvorschläge überlagern die Adressvorschläge.

**Soll:** Beide Vorschlagsarten in **einer gemeinsamen Vorschlagsliste**:
- Kliniken oben zuerst
- visuell abgesetzt (Gruppierung)
- darunter die Adressvorschläge

**Entscheidungen:**
- **F9:** Klinikvorschläge erscheinen **nur im Zielklinik-Kontext**, nicht bei Einsatzort
  oder Standort.
- **F10:** Maximal **2 Kliniken** werden oben angezeigt, danach folgen die
  Adressvorschläge.

**Typ:** Bug / UI

---

## PS-7 — Zielklinik: Koordinateneingabe und Kartenauswahl ergänzen

**Bereich:** Eingabe von Zielkliniken, an **beiden** Stellen:
- Vorbelegung bei den Rettungsmitteln
- Einsatzbearbeitung

**Soll:** Zusätzlich zur bisherigen Eingabe möglich:
- Koordinateneingabe
- Auswahl über die Karte

Die Kartenauswahl soll dem **standardisierten Kartendialog** entsprechen — inklusive der
unter PS-1 geforderten Adresssuche.

**Entscheidungen:**
- **F11:** Koordinateneingabe **einheitlich wie in den übrigen Feldern**. Keine neuen oder
  abweichenden Formate; die bestehende Funktion wird wiederverwendet.
- **F12:** Über Karte oder Koordinaten gewählte Zielkliniken sind **Ad-hoc-Einträge** für
  den jeweiligen Einsatz. Es wird kein Stammdateneintrag angelegt.

**Abhängigkeiten:** PS-1 (gemeinsamer Kartendialog), PS-6 (Vorschlagsliste im Klinikfeld)

---

## PS-8 — Verschlüsselungs-Kennzeichnung und Notizfeld

**Bereich:** Einsatzbearbeitung (mindestens PC/Web)

### PS-8.1 — Kennzeichnung fehlt
**Ist:** Es ist nicht ersichtlich, welche Felder verschlüsselt gespeichert werden und
welche nicht.

**Soll (F13):** **Schloss-Icon** am jeweiligen Feld, ergänzt um eine **Legende**, die die
Bedeutung erklärt.

**Typ:** UI

### PS-8.2 — Notizfeld verschlüsseln
**Soll:** Das Notizfeld soll verschlüsselt werden.

**Entscheidung (F14/F18):** Das Notizfeld muss **durchsuchbar** bleiben. Die Suche soll
sich **verhalten wie in allen anderen Feldern**. Filtern über das Notizfeld ist nicht
erforderlich.

**Offener Zielkonflikt — bei Analyse zuerst zu klären:** Ob Verschlüsselung und
gleichwertige Suche zusammen erreichbar sind, hängt davon ab, wo die übrigen
durchsuchbaren Felder verschlüsselt werden. Werden sie im Klartext gehalten und
serverseitig durchsucht, ist beides beim Notizfeld nicht ohne Kompromiss zu haben. In dem
Fall werden Optionen mit Vor- und Nachteilen vorgelegt, bevor entschieden wird.

**Typ:** Funktionale Änderung — betrifft Datenmodell / Verschlüsselung, nicht nur UI.
Vermutliche Folgewirkungen: Migration bestehender Daten, Suchbarkeit. Wird getrennt von
PS-8.1 behandelt.

---

## PS-9 — Kachel „Spur" umbenennen

**Bereich:** Einsatzbearbeitung, obere Leiste neben „editiert"

**Ist:** Kachel zeigt z. B. „Spur · 852 Punkte". Der Begriff „Spur" ist schwer
verständlich.

**Soll (F15):** Umbenennung in **„GPS-Daten"**. Die **Punktzahl entfällt** — die Kachel
zeigt nur noch, dass GPS-Daten vorhanden sind.

**Typ:** Wording / UI

---

## PS-10 — Rettungsmittel: neue Arten, freie Definition, Rollen-Nachladen

### PS-10.1 — Neue Arten von Rettungsmitteln
**Soll:** Zusätzlich zu bestehenden Arten etablieren:
- Bergwachtnotarzt
- Veranstaltungsnotarzt
- Sonstiges

**Entscheidung (F16):** Die neuen Arten erhalten ein **eigenes Icon**. **Keine
Rollen-Vorlagen.** Ein **Standort** kann eingegeben werden.

### PS-10.2 — Rettungsmittel ohne zentralen Eintrag
**Soll:** Ein Rettungsmittel kann in der **Tageszuordnung** manuell definiert werden und
benötigt keinen zentralen Stammdateneintrag.

**Bedingung:** Suche und Filter müssen für solche manuell definierten Rettungsmittel
dennoch funktionieren.

**Entscheidung (F17):** Manuell definierte Rettungsmittel gelten **nur für den jeweiligen
Tag**. Eine dauerhafte Aufnahme in den Stamm erfolgt weiterhin **manuell über die
Einstellungen**.

### PS-10.3 — Rollen erst nach Speichern und erneutem Bearbeiten editierbar
**Ist:** Bei der Zuordnung eines Rettungsmittels muss erst gespeichert und anschließend
erneut auf „bearbeiten" geklickt werden, bevor die Rollen bearbeitet werden können.

**Soll:**
- Rollen unmittelbar nach Auswahl des Rettungsmittels bearbeitbar, ohne Speichern-und-
  erneut-Öffnen-Umweg
- Falls für das gewählte Rettungsmittel Rollen vordefiniert sind, werden diese nach der
  Auswahl **automatisch nachgeladen**

**Entscheidung (F19):** Bei **manuell definierten** Rettungsmitteln (PS-10.2) und bei den
neuen Arten ohne Rollen-Vorlagen (PS-10.1) **entfällt die Rollenbearbeitung vollständig**.
Sie gilt nur für Rettungsmittel mit hinterlegten Rollen.

**Typ:** Bug / Workflow + Funktionserweiterung

---

## Querbezüge

| Punkt | hängt zusammen mit | Grund |
|-------|--------------------|-------|
| PS-7 | PS-1 | gemeinsamer standardisierter Kartendialog inkl. Adresssuche |
| PS-7 | PS-6 | Vorschlagsliste im Klinik-/Adressfeld |
| PS-6 | PS-1 | Adressvorschläge stammen aus derselben Suchquelle |
| PS-2 | PS-10.2 | Übernahme aus Rettungsmittel-Suchdialog, freie Einträge |
| PS-3 | PS-5 | Icon-Gestaltung und Icon-Größen in der Oberfläche |
| PS-10.1 | PS-10.3 | Arten ohne Rollen-Vorlagen bestimmen, wo Rollenbearbeitung entfällt |

---

## Hinweise für die Konzepterstellung

- **Fable-Schritte:** Die Mockups und Icon-Varianten zu **PS-3** und **PS-5** sind als
  Fable-Schritte zu markieren. Die ausführende Instanz muss davor explizit pausieren.
- **Reihenfolge:** Der Zielkonflikt unter PS-8.2 ist als Erstes zu prüfen, da er über die
  Machbarkeit des gesamten Punktes entscheidet.
- **Standardisierter Kartendialog:** PS-1 ist Voraussetzung für PS-7 und sollte als
  gemeinsame Komponente konzipiert werden.

---

## Offene Punkte vor Konzeptstart

| Nr. | Punkt | Frage |
|-----|-------|-------|
| F3 | PS-3 | Screenshot Ist-Zustand in realer Nutzungsbreite |
| F4 | PS-3 | Client und Zielbreite |
| F5 | PS-3 | Einsatzbeginn/Einsatzende: nur Zustand oder mit Uhrzeiten? |
| F6 | PS-3 | Zusatzmerkmal neben Farbe erforderlich? |
