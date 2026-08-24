# Browserschritte des Referenzdatensatzes (Arbeitspaket B4)

Was es **nur** im Browser gibt. Die beiden Schritte des Aufbaus (oben in der
Tabelle) sind zusätzlich als nummerierte Klickstrecke beschrieben — das Skript
ist die wiederholbare Fassung, die Klickstrecke die prüfbare.

| Skript | Wofür |
|---|---|
| `csv_import.mjs` | CSV-Import der vier nachträglich erfassten Einsätze (Aufbau) |
| `angriffswerte.mjs` | P-07 — die Angriffswerte stehen inert in allen Einsatztabellen |
| `referenz_export.mjs` | zieht die eingecheckten Referenzdateien aus dem Referenzkonto |
| `kreislauf_edbak.mjs` | Umlauf der Sicherung: einspielen, erneut sichern (von `kreislauf.py` gerufen) |
| `kreislauf_csv.mjs` | dasselbe für den CSV-Weg |
| `papierkorb_misch.mjs` | E-S1-04: ein Diensttag mit **einzeln** und **mit dem Tag** gelöschten Einsätzen übersteht den Umlauf, und die Papierkorbseite zeigt den Unterschied |
| `demo_pruefen.mjs` | Abnahme der Demo-Funktion (E-P1-08) — **verändert das Konto, gegen das es läuft** |
| `demo_bremse.mjs` | die Mengenbremse greift auch für das Demo-Konto |

Zwei davon fassen Daten an und haben deshalb einen Riegel:
`demo_pruefen.mjs` bricht ab, wenn unter der Demo-Adresse ein Konto liegt, das
nicht als Demo-Konto gekennzeichnet ist; `papierkorb_misch.mjs` arbeitet
ausschließlich auf Konten, deren Adresse mit `umlauf-` beginnt. Beide Riegel
brechen **hart** ab (Rückgabe 2) — sie melden nicht bloß.

Aufruf jeweils:

```
PLAYWRIGHT_MODUL=/opt/node22/lib/node_modules/playwright/index.mjs \
  node csv_import.mjs [basis] [email] [passwort] [csv] [ausgabeordner]
```

---

## Warum der Import nicht im Einspielskript steht

`import.php` enthält **keine** Verarbeitungslogik (so steht es im
Kopfkommentar der Datei, und so ist es auch). Die Datei wird nicht
hochgeladen: Der Browser liest sie, prüft sie, verschlüsselt die
geschützten Angaben und schickt erst das Ergebnis an
`api/import_commit.php`. Der Server bekommt Diagnose, Alter und Einsatzort
ausschließlich als Chiffretext zu sehen.

Ein Skript, das diesen Weg serverseitig nachbaute, prüfte deshalb gar nicht
den Weg, um den es geht. Es prüfte einen zweiten, den es nicht geben soll.

---

## Klickstrecke 1 — CSV-Import

Voraussetzung: Der Einspiellauf (B3) ist durch, die Datei
`generator/ausgabe/import/einsaetze.csv` liegt vor.

1. **Anmelden** unter `login.php` mit `demo@gen-em.org` / `nadokudemo0815`.
   Der Inhaltsschlüssel wird bei der Anmeldung entsperrt und liegt für die
   Dauer des Tabs bereit — ohne ihn kann der Import nicht verschlüsseln.
2. **Einstellungen → Import / Export** öffnen (`import.php`).
   Steht dort die Warnung „Verschlüsselung gesperrt", ist die Sitzung zwar
   gültig, der Schlüssel aber nicht im Tab: ab- und neu anmelden.
3. **Datei wählen**: `einsaetze.csv`.
   Die Seite erkennt das Format selbst. Erwartet: **CSV (Standard)** — das
   ist das Profil `export_csv_v1`, der verlustfreie Rückweg des eigenen
   Exports.
4. **Prüftabelle lesen.** Erwartet beim ersten Lauf in ein leeres Konto:
   `6 Zeilen — 2 Diensttage, 4 Einsätze, 0 Hinweise, 0 Fehler, 0 Dubletten`.
   **Woran ein Scheitern zu erkennen ist:** Steht dort eine Zahl bei
   „Fehler", ist die Schaltfläche unten gesperrt und nennt den Grund.
   Steht dort eine Zahl bei „Hinweise", auf die Zeilen klappen und den Text
   lesen — „Zeitstempel nicht lesbar" heißt, der Zonenversatz in der Datei
   stimmt nicht (siehe unten). Ein Hinweis sperrt den Import **nicht**: Die
   betroffenen Werte fallen still weg, und die Bilanz meldet trotzdem
   „0 Fehler".
5. **„Import ausführen"** klicken. Erwartet:
   `4 Einsätze angelegt, 0 überschrieben, 0 übersprungen`.
   Ein zweiter Lauf derselben Datei meldet **4 Dubletten** und legt nichts
   an — das ist richtig so und keine Fehlfunktion.
6. **Ersten Tag öffnen** über den Verweis in der Rückmeldung. Die vier
   Einsätze tragen in der Datenbank `origin = 'import'` und einen
   `client_ref` mit dem Präfix `imp-`.

### Zwei Fallen, beide erlebt

**Der Zonenversatz braucht einen Doppelpunkt.** `PARSERS.isoTs` in
`assets/import.js` prüft gegen `[+-]\d{2}:\d{2}`. Pythons `%z` liefert
`+0200` ohne Doppelpunkt — damit fallen die Endzeit und **alle acht
Phasenzeiten** durch, und zwar als *Hinweis*, nicht als Fehler. Der Import
meldet trotzdem „0 Fehler" und legt die Einsätze ohne Zeiten an. Der
Generator schreibt den Versatz deshalb von Hand zusammen
(`erzeugen.iso_offset`), und `generator/pruefen.py` hält die ganze Datei
gegen die Parser der Anwendung (Prüfung 5).

**Eine Zelle mit `=` am Anfang kommt leer an.** SheetJS liest sie als
Formel; der Wert ist danach weg. Der Exportweg schützt solche Zellen mit
einem vorangestellten `'` — der Importweg entfernt ihn nicht wieder. Die
Formel-Anfangszeichen des Referenzdatensatzes stehen deshalb auf dem
Formularweg, nicht auf dem CSV-Weg (Fund F-P1-G).

---

## Klickstrecke 2 — P-07, Angriffswerte

Fünf geschützte Felder des Referenzdatensatzes tragen absichtlich Markup,
das ein Browser ausführen würde (R20). Alle liegen am **Diensttag
21.11.2026**.

| Feld | Wert |
|---|---|
| Diagnose | `<img src=x onerror="alert('R20-dx')">Thoraxtrauma …` |
| Ortsbeschreibung | `"><script>alert('R20-ort')</script>Baustelle …` |
| Einsatznummer | `<svg/onload=alert('R20-nr')>2026-0335` |
| Einsatzort-Adresse | `<b onmouseover="alert('R20-adr')">Talstraße 7</b>, …` |
| Alter | `<img src=x onerror="alert('R20-alter')">` |

1. **Anmelden** wie oben.
2. **Tagesübersicht** des 21.11.2026 öffnen (`index.php?d=<Kennung>`).
   Erwartet: In der Spalte *Alter* steht der Text `<img src=x onerror=…>`
   sichtbar da, in *Diagnose* der Text mit dem `<img …>` davor.
   **Scheitern:** ein Dialogfenster, ein leeres Bild-Symbol in der Zelle,
   oder eine Zelle, die den Wert gar nicht zeigt.
3. **Einsatzsuche** (`suche.php`) öffnen, unten **„Alle N anzeigen"**
   klicken. Ohne diesen Klick steht nur die erste Seite der Trefferliste da
   und die betroffenen Einsätze sind gar nicht gerendert — dann sagt
   „nichts passiert" nichts aus.
4. **Zeitraum-Übersicht** (`zeitraum.php?y=2026&m=11`) öffnen, ebenfalls
   „Alle N anzeigen".
5. **Einsatzseite** des Einsatzes mit der Nummer `2026-0335` öffnen.
   Erwartet: Einsatznummer und Adresse stehen als Text da, mit Markup.
6. **Einsatzformular** desselben Einsatzes öffnen. Erwartet: dieselben
   Werte in den Eingabefeldern, unverändert.

In allen sechs Schritten gilt: **kein Dialogfenster, keine Konsolenmeldung,
kein Element aus der Nutzlast im Dokument.**

Das Skript prüft genau das und ersetzt dafür `window.alert`, `confirm` und
`prompt` **vor** dem ersten Seitenskript — ein `alert()` aus einem
`onerror`-Attribut liefe sonst gegen Playwrights stillen Dialog-Handler und
bliebe unbemerkt. Die Gegenprobe läuft mit: Mindestens eine Seite muss den
Wert tatsächlich anzeigen, sonst wäre die Prüfung gegenstandslos.

**Hier wurde ein echter Fehler gefunden** (F-P1-I, ausgeliefert als Web 7.2.1):
Die Spalte *Alter* gab ihren Wert unmaskiert aus. Gegen den Stand vor der
Korrektur meldet das Skript sechs Befunde über drei Seiten.
