# Umsetzung des Code-Reviews — Stand

Dieses Dokument hält fest, welche Befunde des Code-Reviews bereits behoben
sind. Es wird bei **jeder** Auslieferung fortgeschrieben und ist die Antwort
auf die Frage „ist das schon erledigt?", ohne dass jemand den Changelog
rückwärts lesen muss.

Grundlage: `Konzept-Behebung-Review-Befunde.md` (nicht im Repository — es ist
das Arbeitsdokument der Umsetzung). Ausgangsstand `e457b2d`, 117 Befunde,
davon 94 zu beheben und 23 als bewusst richtig bestätigt.

## Fortschritt

| Paket | Inhalt | Version | Stand |
|---|---|---|---|
| P0 | Gemeinsame Bausteine und Migration | Web 4.0.0 | **erledigt** |
| P1 | Sofortmaßnahmen | — | offen |
| P7 | Dokumentation und Verträge | — | offen |
| P2 | Kette „unlesbarer Schlüssel" schließen | — | offen |
| P3 | Gemeinsame Prüfschicht anwenden | — | offen |
| P5 | Papierkorb und gelöschte Flugtage | — | offen |
| P4 | Ratenschutz und unangemeldete Endpunkte | — | offen |
| P6 | Sitzung, Rollen, Konten | — | offen |
| P8 | Aufräumen ohne Verhaltensänderung | — | offen |
| P9 | Größere Vorhaben | — | offen |

---

## P0 — Gemeinsame Bausteine und Migration (Web 4.0.0)

**Grundsatz dieser Auslieferung: anlegen, noch nicht benutzen.** Die Bausteine
existieren und sind einsatzbereit; das Verhalten der Anwendung ändert sich
nicht. Einzige Ausnahme ist der Ratenschutz, der ab P1 gebraucht wird.

### Bausteine

| | Datei | Behandelt |
|---|---|---|
| B1 | `server/validate_lib.php` | M2-04, M3-04, M4-05, M5-02, M3-02, D11 |
| B2 | `server/validate_lib.php` (`pruef_kalendertag`) | M3-02, M6-04 |
| B3 | `server/ratelimit_lib.php` | M1-02, M1-08, M4-01, M4-10 |
| B4 | `server/assets/crypto.js` (`contentKeyCheck`) | M1-12, M2-16, M2-05, M5-01 |
| B5 | `server/assets/keyguard.js` | M2-05, M1-03 |
| B6 | `server/session_lib.php` | M1-03, M1-04 |
| B7 | `server/assets/missiontable.js` (`escape`) | M6-03, M6-05 |
| B8 | `server/assets/patient.js` (`entschluessleListe`) | M6-02, M6-06 |
| B9 | `server/assets/pwquality.js` | M2-02, M2-03 |

### Schema

| | Änderung | Benutzt ab |
|---|---|---|
| S1 | `users.kdf_iter`, Bestand auf 310000 | P9 (M2-01) |
| S2 | `users.pat_key_check`, Bestand bleibt leer | P2 (M1-12) |
| S3 | `users.session_epoch`, Vorgabe 0 | P6 (M1-09) |
| S4 | Tabelle `rate_limits` | **P1** (M4-01) |
| S5 | `deleted_refs.owner_type`, Schlüssel erweitert | P5 (M4-04) |
| S6 | Sortierregel `users.email` festgelegt | P6 (M1-13) |
| S9 | Ratenschutz-Tabelle im Aufräumjob (Teil) | sofort |

S7 und S8 (Sicherungsformat) sind Formatänderungen ohne Schemaanteil; sie
entstehen dort, wo sie gebraucht werden — S8 in P1/P2, S7 in P9.

### Zur Rundenzahl (S1)

Die heikelste Änderung der gesamten Umsetzung: Ein Fehler an der
Schlüsselableitung sperrt nicht ein Konto aus, sondern **alle gleichzeitig**.
Sie ist deshalb auf zwei Auslieferungen verteilt.

```
P0   Schritt 1  Spalte anlegen, Bestand auf den heutigen Wert setzen.
                Kein Code liest sie. Der Salt-Endpunkt bleibt unverändert.
P9   Schritt 2  Salt-Endpunkt liefert die Rundenzahl mit.
     Schritt 3  Browser rechnet mit dem gelieferten Wert.
     Schritt 4  Stille Anhebung bei der nächsten Anmeldung.
```

Ab Schritt 2 gilt: Für unbekannte Adressen muss **dieselbe** Rundenzahl
genannt werden wie für echte Konten — sonst wird die in P1 geschlossene
Auskunftslücke (M1-01) an neuer Stelle wieder geöffnet. In P1 besteht diese
Wechselwirkung noch nicht; dort ist M1-01 eine reine Längenkorrektur.

Der Salt-Endpunkt wird insgesamt in drei Paketen angefasst: P1 (Länge des
Pseudo-Salts), P4 (Ratenschutz), P9 (Rundenzahl).

### Prüfung

Nachgewiesen gegen eine echte MariaDB 10.11 mit Altbestand:

* Migration läuft fehlerfrei; `kdf_iter` = 310000, `pat_key_check` = NULL,
  `session_epoch` = 0, vorhandene Sperrlisteneinträge erhalten
  `owner_type = 'mission'` ohne Datenverlust.
* Neuinstallation über `schema.sql` legt alles an und verbucht die Migration
  als „nicht nötig".
* Ratenschutz: nach 10 Fehlversuchen gesperrt; nach Ablauf des Zeitfensters
  Zähler zurück auf 1 und Sperre aufgehoben.
* Aufräumjob entfernt abgelaufene Zähler und lässt aktive Sperren stehen.

Prüfschicht und Browser-Bausteine über 40 Einzelfälle, darunter: 30. Februar
wird abgelehnt statt auf den 2. März verschoben, 29.02.2024 bleibt gültig,
Patientenblock unter 40 Zeichen abgelehnt, Phase 10 abgelehnt, „1234567890"
und „Passwort123!" als Passwort abgelehnt, unlesbare Datensätze getrennt von
leeren gezählt.

### Noch nicht Teil dieser Auslieferung

Der Aufräumjob wurde nur um die neue Tabelle ergänzt. Der eigentliche Umbau
(Schritte gegeneinander abschotten, Fehler protokollieren, zweiter
Zustandsschlüssel für den letzten erfolgreichen Lauf) gehört zu M3-05 und
folgt in P8.

Die Migration läuft noch über den bisherigen Ablauf der Wartungsseite, deren
Umbau (M6-01) erst in P1 erfolgt. Das ist vertretbar, weil der Aufruf hier
eine bewusste Handlung des Betreibers ist.
