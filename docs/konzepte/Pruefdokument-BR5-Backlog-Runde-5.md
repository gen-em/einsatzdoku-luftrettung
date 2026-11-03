# Prüfdokument BR5 — Backlog-Runde 5, Web (Schritt 22)

Was **du** noch prüfen musst. Was maschinell gemessen ist, steht mit Mittel
und Zahl im Prüfprotokoll des Konzepts (`Konzept-BR5-Backlog-Runde-5.md` 6),
nicht hier (CLAUDE.md 7, E-PU-08). Fortgeschrieben je Paket; Stand nach
BR5-01.

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

## 2. Grenzen der benutzten Prüfmittel

- **Die Deadlock-Probe war ein Einmalskript** (Prüfprotokoll BR5-01), kein
  eingechecktes Werkzeug. Sie belegt den Fall in `totp_abschalten()`, nicht
  jeden der vier Wege aus F-BR5-12; die übrigen drei laufen über dieselbe
  Stelle (`protokoll()` → `db_transaktion_verloren()`).
- **Z46 sieht nur feste Arten.** Eine Art aus einer Variablen
  (`protokoll_geraet()`, `wartung_an`/`_aus`) misst sie nicht.
- **Staging-Prüfungen nur in Chromium**, mit dem eigenen Konto der
  Arbeitsumgebung (R91).

## 3. Handprüfliste

*Für dich offen: nichts aus BR5-01.* P-BR5-01 bis -04 hat die
Arbeitsumgebung am 03.11.2026 auf Staging bestanden (Konzept 6) und hier
gestrichen.
