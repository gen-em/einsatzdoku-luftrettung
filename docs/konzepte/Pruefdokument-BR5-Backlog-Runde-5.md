# Prüfdokument BR5 — Backlog-Runde 5, Web (Schritt 22)

Was **du** noch prüfen musst. Was maschinell gemessen ist, steht mit Mittel
und Zahl im Prüfprotokoll des Konzepts (`Konzept-BR5-Backlog-Runde-5.md` 6),
nicht hier (CLAUDE.md 7, E-PU-08). Fortgeschrieben je Paket; Stand nach
BR5-03.

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

## 2. Grenzen der benutzten Prüfmittel

- **Die Deadlock-Probe war ein Einmalskript** (Prüfprotokoll BR5-01), kein
  eingechecktes Werkzeug. Sie belegt den Fall in `totp_abschalten()`, nicht
  jeden der vier Wege aus F-BR5-12; die übrigen drei laufen über dieselbe
  Stelle (`protokoll()` → `db_transaktion_verloren()`).
- **Z46 sieht nur feste Arten.** Eine Art aus einer Variablen
  (`protokoll_geraet()`, `wartung_an`/`_aus`) misst sie nicht.
- **Staging-Prüfungen nur in Chromium**, mit dem eigenen Konto der
  Arbeitsumgebung (R91).

- **Die Proben senden an `probe.pruefung`** (E-BR5-20) — nur gegen die
  Gegenstelle bzw. 127.0.0.1. Wer eine dieser Proben gegen eine Anlage mit
  echtem Mailserver fährt, schickt Post an eine Domain, die es nicht gibt.

## 3. Handprüfliste

*Aus BR5-01: nichts offen* — P-BR5-01 bis -04 am 03.11.2026 auf Staging
bestanden (Konzept 6).

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an |
|---|---|---|---|
| P-BR5-05 | Staging, nach dem Merge und einem grünen Lauf von Stufe 2: Verwaltung → Protokoll, Reiter System, letzte Stunde | keine neue Störung „smtp: Versand fehlgeschlagen“; im Reiter E-Mail Einträge „nicht eingereiht“ für die Kettenkonten | weiter Mailfehler je Lauf, oder keine Einträge „nicht eingereiht“ |
| P-BR5-06 | Staging: Konto anlegen für eine Adresse der eigenen Domain, die es nicht gibt; danach Betrieb → Status, Zeile „Warteschlange“ | entweder sofort „unzustellbar“ (der Hoster lehnt beim Empfänger ab) oder „zugestellt“ (er nimmt an und schickt später zurück) — beides richtig, aber nicht „wartet“ mit 550 | „wartet“ mit einer 5xx-Absage im Grund |
| P-BR5-07 | Staging: Konto für `…@beispiel.invalid` anlegen | Meldung „… konnte NICHT verschickt werden. Die Adresse liegt unter der reservierten Domain .invalid …“ und der Link darunter | Meldung ohne den Satz, oder kein Link |
| P-BR5-08 | Staging, BetreiberIn: Betrieb → Jobs, nach einem gescheiterten Job (falls einer scheitert) | „zuletzt: „…““ ohne Klassennamen wie „RuntimeException:“ | ein Klassenname vor der Meldung |
| P-BR5-09 | Staging (Arbeitsumgebung): Anmeldung und „Passwort vergessen“ mit `email[]=x` statt `email=x` abschicken (Formular mit gültigem Token) | die Seite meldet „E-Mail fehlt“ o. ä.; keine Fehlerseite, keine neue Zeile im Reiter System | eine 500, oder „Array to string conversion“ im Reiter System |
