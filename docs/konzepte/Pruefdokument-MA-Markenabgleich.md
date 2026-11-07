# Prüfdokument MA — Markenabgleich mit dem Branding-Repo

Zum Konzept `Konzept-MA-Markenabgleich.md` (Schritt 26, Web 23.9.0 bis
23.11.2, Android 0.21.1 und 0.22.0, Uhr 4.3.3). Was maschinell gemessen
wurde, steht mit Mittel und Zahl im Prüfprotokoll des Konzepts (Abschnitt 9)
und im Prüfbericht des letzten Commits. Dieses Dokument sagt, was **noch zu
tun** ist.

**Vor allem anderen:** Web 23.10.0 bringt eine Migration
(`2026_11_07_darstellung`). Nach dem Deploy auf Staging und nach dem Tag auf
Produktiv muss eine Administratorin `update.php` aufrufen; bis dahin gilt für
jedes Konto „wie das Gerät", und der Schalter im Profil speichert nichts.

## 1. Was nicht geprüft werden konnte, und warum

1. **Nichts auf Staging oder Produktiv.** Gemessen ist an der einen örtlichen
   Anlage. Kopfleiste mit dem Kurznamen der Testanlage und die Migration
   zeigt erst Staging (P-MA-01, -02).
2. **Ein echter Safari** — WebKit ist nicht Safari (Grundsatz 8): Favicon,
   Apple-Symbol, `color-scheme: dark` an Formularelementen (P-MA-08, -11).
3. **Ein Screenreader.** axe-core misst Regeln, nicht ob VoiceOver oder
   TalkBack die Seite verständlich vorliest (P-MA-16).
4. **Der Dark Mode im Web-Bilderlauf** (Backlog Nr. 521). Dunkel misst ihn
   die Barrierefreiheitsprobe auf 28 Seiten (Regeln, auch Kontrast), und
   angesehen ist er in Chromium; Seite für Seite im Bild nicht.
5. **Android und Garmin auf einem Gerät.** Gebaut und mit Prüffällen,
   Bilderlauf und Kontrastrechnung gemessen; wie es auf dem Telefon, der
   Wear-OS-Uhr und der Garmin aussieht, zeigt nur das Gerät (P-MA-12, -17).
6. **Eine Entscheidung braucht die Betreiberin:** die beendenden Knöpfe der
   App bleiben vollflächig rot (E-MA-36, P-MA-13).

## 2. Grenzen der benutzten Prüfmittel

- **`kontrast.py`** leitet Paare aus **einer** Regel ab; eine Schrift, deren
  Fläche eine Elternregel setzt, sieht es nicht — dunkel häufiger als hell.
- **axe-core** prüft rund ein Drittel der WCAG-Kriterien und misst die Seite,
  wie sie nach dem Laden steht: keine offenen Dialoge, Blätter, Menüs.
- **`kontraste.py` (Android)** kennt die Rolle einer Farbe, nicht ihren
  Grund in einer anderen Funktion; den Grund nennt die Paarliste.
- **Der Android-Bilderlauf** zeichnet unter Robolectric: ohne Systemleisten,
  Herstellerschrift und Systemdialoge.
- **Die Bedienprobe** und der Bilderlauf sehen keine Farbe; ob eine Plakette
  einen Zustand (grün) oder eine Eigenschaft (blau) meint, ist eine
  Lesefrage (P-MA-14).

## 3. Handprüfliste

Wer: **Claude** prüft auf Staging nach dem Merge mit dem eigenen Konto bzw.
dem Demo-Konto (R91); **Betreiberin** auf Produktiv, in Safari, auf Geräten
und bei Entscheidungen.

| Punkt | Wer | Bedienweg | Erwartet | Gescheitert, wenn |
|---|---|---|---|---|
| P-MA-01 | Betreiberin | Nach dem Deploy auf Staging (und nach dem Tag auf Produktiv): Betrieb → Updates, `update.php` ausführen | Migration `2026_11_07_darstellung` „Erfolgreich angewendet", danach nichts offen | Migration bleibt offen; Fehlermeldung |
| P-MA-02 | Claude | Staging, als BetreiberIn: Startseite bei 1440 und 390 px | Kopfleiste orange 800 mit Zeichen, **Kurzname der Anlage** und Etikett; Knöpfe dunkelblau; „Einsatz nachtragen" orange | „NAdoku" statt Kurzname; Kopfleiste rot; Statusseite meldet „Farbe unbekannt" |
| P-MA-03 | Claude | Staging: Betrieb → Status | „in Ordnung" grün, Warnungen orange mit Symbol, Fehler rot; „Produktiv"/„Staging" blau | eine gute Zeile blau; eine Warnung ohne Symbol |
| P-MA-04 | Claude | Staging: Zahnrad → Profil → Darstellung „Dunkel" → Speichern; abmelden, anmelden; dann „Wie das Gerät" | sofort dunkel, nach der Anmeldung weiter dunkel; danach folgt die Seite dem Gerät | bleibt hell; springt zurück; Seite blitzt hell auf |
| P-MA-05 | Claude | Staging, dunkel, in Chromium, Firefox und WebKit: Tagesübersicht, Einsatzformular mit Pflichtfehler, Dialog „Löschen", Schublade bei 390 px | alles lesbar; Löschknopf rot umrandet, nicht gefüllt | heller Kasten im Dunkeln; unsichtbare Schrift; Löschknopf rot gefüllt |
| P-MA-06 | Claude | Staging: je einmal Tagesübersicht, Einsatz, Einsatzformular, Diensttag anlegen, Anmeldung, Registrierung | genau ein oranger Knopf je Seite; auf Suche, Einstellungen, Verwaltung keiner | zwei orange Knöpfe; Orange auf einer Seite ohne Hauptaktion |
| P-MA-07 | Claude | Staging, dunkel: Notfallblatt, Codeblatt, Druckvorschau einer Tagesübersicht | Blätter und Druck hell | dunkle Flächen in der Vorschau |
| P-MA-08 | Betreiberin | Safari (Mac, iPhone) und Firefox: Tab-Symbol; iPhone „Zum Home-Bildschirm" | Zeichen farbig, auch bei 16 px erkennbar; Home-Symbol auf Schnee | altes Zeichen; leeres oder graues Symbol |
| P-MA-09 | Claude | Staging: Profil → Logo „Fahrzeug"; abmelden; Wartungsseite (anderes Konto) | Kopfleiste und Tab zeigen das NEF; Anmeldeseite den Standard; Wartung den Hubschrauber, Warnung mit Symbol | Kopfleiste und Tab verschieden; Wartungsmeldung ohne Symbol |
| P-MA-10 | Claude | Staging: Seitenleiste, Monat zuklappen; mit Tab-Taste durch Jahr und Monat; Klick auf das Balkensymbol | Balkensymbol an derselben Stelle rechts in der Zeile; Tab erreicht es getrennt vom Aufklappen; Klick öffnet die Übersicht, ohne aufzuklappen | Symbol verrutscht oder fehlt an zugeklappten Zeilen; Klick klappt mit |
| P-MA-11 | Betreiberin | Safari dunkel, Konto „Wie das Gerät": Einsatzformular, Datums- und Auswahlfelder öffnen | Auswahlen und Kalender dunkel und lesbar | weiße Auswahl mit weißer Schrift |
| P-MA-12 | Betreiberin | Android 0.22.0 auf Handy (hell und dunkel im System umschalten) und Wear-OS-Uhr installieren; Start, Dienst, Kopplung, Einstellungen | wie `konzept-ma/android/MA-16-*.png`: „Dienst beginnen" orange, übrige Knöpfe dunkelblau (dunkel hellblau), Bildmarke dunkel mit weißem Korpus | Orange auf anderen Knöpfen; unlesbare Schrift; Bildmarke ohne Korpus; Absturz |
| P-MA-13 | Betreiberin | Konzept MA, E-MA-36 lesen (beendende Knöpfe der App vollflächig rot) | bestätigt oder Umriss wie im Web beauftragt | — (Entscheidung) |
| P-MA-14 | Claude | Staging: Verwaltung → Sicherungen, Sicherungsziele, ein Konto; Profil (Zweitfaktor) | Zustände grün, Eigenschaften blau | ein Zustand blau oder eine Eigenschaft grün |
| P-MA-15 | Claude | Staging: Statistik und Speicherbalken, hell und dunkel | Säulen blau, Höchstwert orange, Teile unterscheidbar | Säule unsichtbar im Dunkeln |
| P-MA-16 | Betreiberin | VoiceOver (iPhone) oder TalkBack: Tagesübersicht bei Handybreite — „Aktionen", Seitenleiste, Tagesfelder | „Aktionen, Taste"; Monat und Monatsübersicht getrennt angesagt; Feldname und Wert als Paar | „Taste" ohne Namen; Monat und Übersicht als ein Element |
| P-MA-17 | Betreiberin | Garmin Uhr 4.3.3 (Simulator oder Uhr): Launcher und Startseite | neue Bildmarke: Korpus weiß, Federn rot, orange, blau | alte Zeichnung; Zeichen abgeschnitten |
| P-MA-18 | Claude | Staging, WebKit oder Safari: von der Startseite schnell auf einen Diensttag und zurück | keine Fehlermeldung, die Seite lädt sauber | Meldung „Die Verbindung zum Server ist abgebrochen" beim Wechsel |
