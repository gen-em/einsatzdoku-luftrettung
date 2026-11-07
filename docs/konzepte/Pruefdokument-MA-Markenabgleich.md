# Prüfdokument MA — Markenabgleich mit dem Branding-Repo

Zum Konzept `Konzept-MA-Markenabgleich.md` (Schritt 26, Web 23.9.0 bis
23.10.0, Android 0.21.1). Was maschinell gemessen wurde, steht mit Mittel und
Zahl im Prüfprotokoll des Konzepts (Abschnitt 9) und im Prüfbericht des
letzten Commits. Dieses Dokument sagt, was **noch zu tun** ist.

**Vor allem anderen:** Web 23.10.0 bringt eine Migration
(`2026_11_07_darstellung`). Nach dem Deploy auf Staging und nach dem Tag auf
Produktiv muss eine Administratorin `update.php` aufrufen; bis dahin gilt für
jedes Konto „wie das Gerät", und der Schalter im Profil speichert nichts.

## 1. Was nicht geprüft werden konnte, und warum

1. **Nichts auf Staging oder Produktiv.** Gemessen ist an der einen örtlichen
   Anlage. Die Kopfleiste der Testanlage (orange 800 statt rot, mit dem alten
   Wert `'farbe' => 'rot'` in der `config.php`) und die Migration zeigt erst
   Staging (P-MA-01, -02).
2. **Ein echter Safari** — WebKit ist nicht Safari (Grundsatz 8). Dazu
   gehören das SVG-Favicon, das Apple-Symbol auf dem Home-Bildschirm und
   `color-scheme: dark` an Formularelementen (P-MA-08, -11).
3. **Der Dark Mode im Bilderlauf.** Der Bilderlauf nimmt nur hell auf
   (F-MA-15, Backlog Nr. 521). Dunkel ist mit eigenen Aufnahmen in Chromium
   angesehen (Startseite, Profil, Anmeldung; 1440 und 390 px), nicht in
   Firefox und WebKit und nicht Seite für Seite (P-MA-05).
4. **Die Android-App auf einem Gerät.** Gebaut und mit den Prüffällen
   gemessen im Prüfstand; wie die neuen Bildmarken und die sechs geänderten
   Farbwerte auf Handy und Uhr aussehen, zeigt nur das Gerät (P-MA-12).
5. **Die Garmin-Uhr** zeigt noch die alten Zeichen; neu erzeugt wird erst
   mit einer Uhr-Fassung (Backlog Nr. 520).
6. **Zwei Entscheidungen der Umsetzung** brauchen die Betreiberin: die
   feste Kopfleiste „NAdoku" statt des Kurznamens (E-MA-21) und der
   Android-Nachzug nur der Werte (E-MA-24) (P-MA-13).

## 2. Grenzen der benutzten Prüfmittel

- **`kontrast.py`** rechnet 58 Paare in zwei Modi und leitet Paare nur aus
  **einer** Regel ab. Eine Schrift, deren Fläche eine Elternregel setzt,
  sieht es nicht — im Dark Mode ist das die häufigere Lage, weil die Karte
  die Fläche trägt und die Zeile die Schrift.
- **`vollstaendigkeit.py`** prüft, dass jeder übergebene Ton eine Regel hat,
  nicht, ob der Ton **passt** — ob eine Plakette einen Zustand (grün) oder
  eine Eigenschaft (blau) meint, ist eine Lesefrage (rund 80 Stellen
  umgestellt, P-MA-14).
- **`farbabgleich.py`** vergleicht Werte, keine Rollen: Dass die App
  weiter in Orange handelt, meldet es nicht (Backlog Nr. 522).
- **Der Bilderlauf** misst Überlauf und Knopfhöhen hell; eine Farbe sieht
  er nicht, und dunkel nimmt er nicht auf.

## 3. Handprüfliste

Wer: **Claude** prüft auf Staging nach dem Merge mit dem eigenen Konto bzw.
dem Demo-Konto (R91); **Betreiberin** auf Produktiv, in Safari, auf Geräten
und bei Entscheidungen.

| Punkt | Wer | Bedienweg | Erwartet | Gescheitert, wenn |
|---|---|---|---|---|
| P-MA-01 | Betreiberin | Nach dem Deploy auf Staging (und nach dem Tag auf Produktiv): Betrieb → Updates, `update.php` ausführen | Migration `2026_11_07_darstellung` „Erfolgreich angewendet", danach nichts offen | Migration bleibt offen; Fehlermeldung |
| P-MA-02 | Claude | Staging, als BetreiberIn: Startseite bei 1440 und 390 px | Kopfleiste orange 800 mit „NAdoku" und Etikett; Primärknopf dunkelblau; aktiver Diensttag hellblau | Kopfleiste rot oder dunkelblau; Primärknopf orange; Statusseite meldet „Farbe unbekannt" |
| P-MA-03 | Claude | Staging: Betrieb → Status | „in Ordnung" grün, Warnungen orange, Fehler rot; „Produktiv"/„Staging" blau | eine gute Zeile blau; eine Zeile ohne Fläche (Ton ohne Regel) |
| P-MA-04 | Claude | Staging: Zahnrad → Profil → Darstellung „Dunkel" → Speichern; abmelden, anmelden; dann „Wie das Gerät" | Sofort dunkel, nach der Anmeldung weiter dunkel; danach folgt die Seite dem Gerät | bleibt hell; springt nach der Anmeldung zurück; Seite blitzt hell auf |
| P-MA-05 | Claude | Staging, Darstellung „Dunkel", in Chromium, Firefox und WebKit: Tagesübersicht, Einsatzformular mit Pflichtfehler, Betrieb → Status, Dialog „Löschen", Schublade bei 390 px | Alle Texte lesbar, Flächen blau 900 / Dunkelblau / blau 700, Meldungen in dunklen Tönen | heller Kasten mitten im Dunkeln; unsichtbare Schrift; Rahmen fehlen an Eingabefeldern |
| P-MA-06 | Claude | Staging, dunkel: Diensttag mit vier oder mehr Einsätzen | Karte hell mit Rahmen; Farbstreifen der Tabelle mit Rand, der dunkelblaue sichtbar | Karte dunkel oder ohne Kante; ein Streifen unsichtbar |
| P-MA-07 | Claude | Staging, dunkel: Notfallblatt und Codeblatt öffnen, Druckvorschau einer Tagesübersicht | Blätter und Druck hell (Schnee, dunkle Schrift) | dunkle Flächen in der Vorschau |
| P-MA-08 | Betreiberin | Safari (Mac, iPhone) und Firefox: Tab-Symbol; iPhone „Zum Home-Bildschirm" | Zeichen farbig, Grundform Dunkelblau, auch bei 16 px erkennbar; Home-Symbol auf Schnee | altes Zeichen; leeres oder graues Symbol |
| P-MA-09 | Claude | Staging: Profil → Logo „Fahrzeug" → Speichern; abmelden; Wartungsseite (Betrieb → Wartung an, anderes Konto) | Kopfleiste und Tab zeigen das NEF; Anmeldeseite den Standard; Wartungsseite immer den Hubschrauber | Kopfleiste und Tab verschieden; Wartungsseite wechselt beim Neuladen |
| P-MA-10 | Claude | Gerät/Browser dunkel, abgemeldet: Anmeldeseite, „Passwort vergessen", Registrierung | Logo mit weißer Grundform auf dunkelblauer Karte | dunkelblaues Logo auf dunkelblauer Karte (unsichtbar) |
| P-MA-11 | Betreiberin | Safari dunkel, Konto „Wie das Gerät": Einsatzformular, Datumsfeld und Auswahlfelder öffnen | Auswahlen und Kalender dunkel und lesbar | weiße Auswahl mit weißer Schrift |
| P-MA-12 | Betreiberin | Android 0.21.1 auf Handy und Wear-OS-Uhr installieren, Startseite und Dienstansicht | Neue Bildmarke (dunkelblau bzw. Korpus weiß im Kopf); Farben wie bisher, nur Tief-/Grautöne leicht anders | alte Bildmarke; unlesbare Schrift; Absturz |
| P-MA-13 | Betreiberin | Konzept MA, E-MA-21 und E-MA-24 lesen | bestätigt oder Änderung beauftragt | — (Entscheidung) |
| P-MA-14 | Claude | Staging: Verwaltung → Sicherungen, Sicherungsziele, ein Konto; Einstellungen → Profil (Zweitfaktor) | „freigegeben", „aktiv", „an", „erledigt" grün; „verschlüsselt", „jüngster", Fassungen blau | ein Zustand blau oder eine Eigenschaft grün |
| P-MA-15 | Claude | Staging: Statistik und Speicherbalken, hell und dunkel | Säulen blau, Höchstwert orange, Balkenteile unterscheidbar | Säule unsichtbar im Dunkeln; Höchstwert nicht abgesetzt |
