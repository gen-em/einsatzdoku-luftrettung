# Prüfdokument BR5 — Backlog-Runde 5, Web (Schritt 22)

Was **du** noch prüfen musst. Was maschinell gemessen ist, steht mit Mittel
und Zahl im Prüfprotokoll des Konzepts (`Konzept-BR5-Backlog-Runde-5.md` 6),
nicht hier (CLAUDE.md 7, E-PU-08). Fortgeschrieben je Paket; Stand nach
der Prüfung auf Staging am 04.11.2026 (PR #148).

## 1. Was nicht geprüft werden konnte, und warum

- **Logo-Standard bei scheiterndem Schreiben (Nr. 345, BR5-01)** — der
  Fehlerzweig ist gelesen und die Funktion darunter örtlich gemessen; über die
  Oberfläche lässt sich ein gescheitertes `app_state_setzen()` nicht
  herbeiführen. Der Erfolgsweg ist auf Staging bedient (Konzept 6).
- **Der Deadlock auf Produktiv (Nr. 354)** — gemessen ist MariaDB 10.11
  örtlich. Die Fehlercodes 1213, 2006, 2013 sind bei MySQL dieselben;
  einen Deadlock gegen den Hoster stellt niemand her.
- **Passkeys beim Zurücksetzen des Zweitfaktors** — auf Staging hatte das
  Wegwerfkonto keinen; ein Browser ohne Bildschirm legt keinen an. Örtlich
  belegt (Deadlock-Probe, Passkey bleibt bzw. geht mit).

- **Eine echte 550 eines echten Mailservers (Nr. 454)** — gemessen gegen die
  Gegenstelle der Mailprobe. Ob der Mailserver des Hosters eine unbekannte
  Adresse schon beim `RCPT TO` abweist oder erst annimmt und später
  zurückschickt, zeigt nur Staging (P-BR5-06).

- **Bitwarden am Feld „Kontopasswort“ (Nr. 430)** — die Sandbox hat kein
  Bitwarden; gemessen sind Backup in drei Engines und ein pixelgleiches Bild
  (Konzept 6). → P-BR5-13.

- **Jobfehler ohne Klassennamen auf Staging (Nr. 357, P-BR5-08)** — am
  04.11.2026 gab es keinen gescheiterten Job; örtlich belegt (Jobprobe).

## 2. Grenzen der benutzten Prüfmittel

- **Die Deadlock-Probe war ein Einmalskript** (Prüfprotokoll BR5-01), kein
  eingechecktes Werkzeug. Sie belegt den Fall in `totp_abschalten()`, nicht
  jeden der vier Wege aus F-BR5-12; die übrigen drei laufen über dieselbe
  Stelle (`protokoll()` → `db_transaktion_verloren()`).
- **Z46 sieht nur feste Arten.** Eine Art aus einer Variablen
  (`protokoll_geraet()`, `wartung_an`/`_aus`) misst sie nicht.
- **PHP 8.3.6 nur örtlich (Nr. 383, BR5-09)** — gemessen mit den
  Proben unter dem PHP der Sandbox. Eine Anlage auf 8.3.6 gibt es bei keiner
  der beiden Umgebungen (Produktiv 8.3.33, Staging 8.4.27).
- **Staging-Prüfungen nur in Chromium**, mit dem eigenen Konto der
  Arbeitsumgebung (R91).

- **Die Proben senden an `probe.pruefung`** (E-BR5-20) — nur gegen die
  Gegenstelle bzw. 127.0.0.1. Wer eine dieser Proben gegen eine Anlage mit
  echtem Mailserver fährt, schickt Post an eine Domain, die es nicht gibt.

## 3. Handprüfliste

*Aus BR5-01: nichts offen* — P-BR5-01 bis -04 am 03.11.2026 auf Staging
bestanden (Konzept 6).
P-BR5-05, -07, -09, -10 und -12 am 04.11.2026 auf Staging bestanden;
P-BR5-06 halb — die Warteschlange richtig, die Meldung beim Anlegen nicht
(Nr. 481, nicht mehr Teil von BR5, E-BR5-21). Konzept 6.

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an |
|---|---|---|---|
| P-BR5-08 | Staging, BetreiberIn: Betrieb → Jobs, nach einem gescheiterten Job (falls einer scheitert) | „zuletzt: „…““ ohne Klassennamen wie „RuntimeException:“ | ein Klassenname vor der Meldung |
| P-BR5-11 | Du, mit einem echten Safari (iPhone oder Mac) auf Staging: frisches Konto anlegen, anmelden und sofort auf eine zweite Seite wechseln (ohne Warten) | danach unter Einstellungen → Geräte keine Karte „Schlüsselpaar des Kontos“; im Protokoll (Verwaltung, Konto) „Kontoschlüssel angelegt“ | die Karte steht da, oder kein Protokolleintrag — WebKit ist nicht Safari (CLAUDE.md 6) |
| P-BR5-13 | **Du**, Firefox mit Bitwarden, Staging nach dem Merge: Einstellungen → Backup, „Mein Kontopasswort verwenden“, ins Feld „Kontopasswort“ klicken | das Bitwarden-Menü am Feld bietet den Eintrag der Anlage an; Einfügen und Enter erstellen das Backup | „No items to show“ am Feld — dann bleibt Nr. 430 offen |
| P-BR5-14 | **Du**, auf Produktiv nach dem nächsten Tag `android-v…`: Einstellungen → Geräte, „Ohne Play Store“ | zwei Zeilen „NAdoku für Android-Handy“ (oben) und „NAdoku für Wear-OS-Uhr“, je mit Fassung | zwei Zeilen „NAdoku“ oder die Uhr oben |
