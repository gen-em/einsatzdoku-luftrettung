# Prüfdokument BR5 — Backlog-Runde 5, Web (Schritt 22)

Was **du** noch prüfen musst. Was maschinell gemessen ist, steht mit Mittel
und Zahl im Prüfprotokoll des Konzepts (`Konzept-BR5-Backlog-Runde-5.md` 6),
nicht hier (CLAUDE.md 7, E-PU-08). Fortgeschrieben je Paket; Stand nach
BR5-01.

## 1. Was nicht geprüft werden konnte, und warum

- **Logo-Standard bei scheiterndem Schreiben (Nr. 345, BR5-01)** — der Zweig
  in `admin_installation.php` ist gelesen und die Funktion darunter
  gemessen, die Seite aber nicht im Browser bedient: Ein gescheitertes
  `app_state_setzen()` lässt sich über die Oberfläche nicht herbeiführen,
  ohne die Tabelle zu sperren, und der Weg ist drei Zeilen lang. → P-BR5-01.
- **Der Deadlock auf Produktiv-MySQL (Nr. 354)** — gemessen ist MariaDB
  10.11 örtlich. Die Fehlercodes 1213, 2006, 2013 sind bei MySQL dieselben;
  einen Deadlock gegen den Hoster stellt niemand her.

## 2. Grenzen der benutzten Prüfmittel

- **Die Deadlock-Probe war ein Einmalskript** (Prüfprotokoll BR5-01), kein
  eingechecktes Werkzeug. Sie belegt den Fall in `totp_abschalten()`, nicht
  jeden der vier Wege aus F-BR5-12; die übrigen drei laufen über dieselbe
  Stelle (`protokoll()` → `db_transaktion_verloren()`).
- **Z46 sieht nur feste Arten.** Eine Art aus einer Variablen
  (`protokoll_geraet()`, `wartung_an`/`_aus`) misst sie nicht.

## 3. Handprüfliste

| Nr. | Bedienweg | Erwartung | Scheitern erkennbar an |
|---|---|---|---|
| P-BR5-01 | Staging, nach dem Merge: Verwaltung → Installation, Logo-Standard wechseln, speichern | Meldung „Standard der Installation: …“, das Logo ist gewechselt | Meldung ohne Wechsel, oder eine Fehlerseite |
| P-BR5-02 | Staging: Verwaltung → Installation, Name und Kurzname ändern, speichern; dann Kontakt- und Betreiberadresse | „gespeichert“, beide Werte stehen nach dem Neuladen | nur einer der beiden Werte geändert |
| P-BR5-03 | Staging: einen Zeitraum aus einer GPS-Spur löschen (Einsatz → Spur → Zeitraum löschen), dann Verwaltung → Protokoll, Reiter Verwaltung | Eintrag mit Plakette „GPS-Daten gelöscht“; die Art steht im Filter „Art des Ereignisses“ | Plakette „spur stueck geloescht“ oder Art fehlt im Filter |
| P-BR5-04 | Staging: Zweitfaktor eines Prüfkontos ausschalten (Profil → Anmeldung) | Faktor aus, Passkeys und gemerkte Geräte weg, drei Protokolleinträge | Fehlerseite ohne Deadlock, oder Passkeys bleiben |
