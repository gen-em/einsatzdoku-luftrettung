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
| P1 | Sofortmaßnahmen | Web 4.1.0 | **erledigt** |
| P7 | Dokumentation und Verträge | Web 4.1.1 | **erledigt** |
| P2 | Kette „unlesbarer Schlüssel" schließen | Web 4.1.2 | **erledigt** |
| P3 | Gemeinsame Prüfschicht anwenden | Web 4.2.0 | **erledigt** |
| P5 | Papierkorb und gelöschte Flugtage | Web 4.3.0 | **erledigt** |
| P4 | Ratenschutz und unangemeldete Endpunkte | Web 4.4.0 | **erledigt** |
| P6 | Sitzung, Rollen, Konten | — | offen |
| P8 | Aufräumen ohne Verhaltensänderung | — | offen |
| P9 | Größere Vorhaben | — | offen |

---

## P4 — Ratenschutz und unangemeldete Endpunkte (Web 4.4.0)

Fünf Endpunkte sind ohne Anmeldung erreichbar: Anmeldung, Salz-Abfrage,
Zurücksetzen-Anforderung, Kopplung und Upload der Uhr. An allen fünf ließ sich
etwas ablesen oder etwas beliebig oft wiederholen.

| Befund | Änderung |
|---|---|
| M1-02 | Anmeldung: Sitzungszähler ersetzt durch den Ratenschutz aus P0 |
| M1-08 | Salz-Abfrage und Zurücksetzen-Anforderung mit Ratenschutz; höchstens ein gültiger Token je Konto |
| M1-07 | Antwort wird abgeschlossen, bevor der Mailversand beginnt |
| M4-07 | Unbekannte Gerätekennung prüft gegen einen festen Vergleichswert |
| M4-10 | Fünf Geräte je Konto; E-Mail und Oberflächenhinweis bei neuen Geräten |
| M4-01 | bereits in P1 vollständig erledigt — hier nur nachgewiesen (A14) |

### Warum kein Sitzungszähler

Die alte Bremse zählte in `$_SESSION`. Wer das Cookie wegwarf, hatte wieder
fünf Versuche frei; ein Programm, das gar kein Cookie annimmt, verbrauchte nie
eines. Nachgewiesen: Zwölf Anmeldeversuche ohne Cookie werden jetzt ab dem
elften abgewiesen — vorher liefen alle zwölf durch.

Die Kontosperre als Nebenwirkung ist bewusst hingenommen: Wer eine Adresse
kennt, kann das Konto fünfzehn Minuten lang aussperren. Die Alternative — nur
nach IP zählen — ließe ein über viele Rechner verteiltes Durchprobieren einer
einzelnen Adresse völlig ungebremst.

### Warum keine Warteschlange für den Mailversand

Auf dieser Installation gibt es keinen Cronjob; die Wartung läuft huckepack auf
Anfragen, höchstens einmal täglich. Eine Warteschlange hätte den Link zum
Zurücksetzen genau so lange liegen lassen, bis zufällig jemand eine Seite
aufruft. Stattdessen wird die Antwort abgeschlossen, bevor der Versand beginnt
— über `fastcgi_finish_request()` bzw. `litespeed_finish_request()`, sonst über
Längenangabe und angekündigtes Verbindungsende.

**Der Preis:** Auf Hosts ohne FPM oder LiteSpeed bleibt der PHP-Arbeitsprozess
nach dem Abschluss der Antwort noch bis zum Zeitlimit des Mailversands belegt.
Bei fünf Anforderungen je Stunde und Konto ist das klein, aber nicht null.
Welcher Weg auf der eigenen Installation greift, steht auf der Wartungsseite
unter **Umgebung**.

### Nachweis — 116 Prüfungen gegen MariaDB und echten HTTP-Verkehr

**M1-07, der entscheidende Fall.** Gegen einen Mailserver, der die Verbindung
annimmt und nie antwortet (Zeitlimit 15 s):

| Zweig | vorher zu erwarten | gemessen |
|---|---|---|
| vorhandenes Konto | ~15 s | **0,51 s** |
| unbekannte Adresse | ~0,5 s | **0,51 s** |

Abweichung 0,0 %. Gemessen auf einem Webserver **ohne**
`fastcgi_finish_request` — also über den schwächeren der beiden Wege. Die
Kopplung der Uhr kam unter denselben Bedingungen in 0,07 s zurück.

**M4-07.** Bekannte Kennung mit falschem Schlüssel gegen unbekannte Kennung:
75 ms zu 76 ms, Abweichung 1,1 %. Eine einzelne Passwortprüfung kostet auf
derselben Maschine 113 ms — genau der Betrag, der vorher fehlte.

**M1-02.** Fehlerdauer bei bekannter und unbekannter Adresse: 351 ms zu 351 ms.
Auch das richtige Token wird während einer Sperre abgewiesen (die Sperre greift
vor der Prüfung). Eine erfolgreiche Anmeldung leert die Zähler beider Töpfe.

**M1-08.** Salz-Abfrage: Sperre greift beim 31. Aufruf, Antwortlängen beider
Zweige identisch (43 Zeichen), Dauer 51 ms zu 51 ms. Zurücksetzen: nach
beliebig vielen Anforderungen bleibt genau **ein** gültiger Token; die alte
Fassung hätte drei gehabt. Eine gesperrte Anforderung zeigt dieselbe
Antwortseite und legt nichts an.

**M4-10.** Obergrenze greift auf beiden Wegen. Das virtuelle Gerät
`manual-<konto>` zählt nicht mit. Fünf deaktivierte Geräte sperren das sechste
— Deaktivieren gibt keinen Platz frei, Löschen schon. An der Grenze wird gar
kein Kopplungscode erzeugt; wird trotzdem einer eingelöst, kommt 409
`device_limit` und der Code **bleibt verbraucht** (M4-03 bleibt gewahrt).

### Zwei Prüffälle, die erst falsch waren

Beim Fensterablauf hatte nur die Konto-Zeile zurückdatiert werden sollen —
`rate_erlaubt()` prüft aber beide Merkmale, und die IP trug die Fehlversuche
der vorherigen Fälle. Der Fehlschlag war korrektes Verhalten aus einem anderen
Grund als dem gemessenen.

Ein zweiter Prüffall behauptete „deaktivierte Geräte geben keinen Platz frei",
maß aber nur, dass ein viertes Gerät durchgeht. Neu gefasst: fünf deaktivierte
Geräte sperren das sechste, und die Meldung nennt den Grund.

### Offen aus diesem Paket

Die Uhr zeigt bei 409 „Kopplung fehlgeschlagen (409)", weil `Pair.mc` nur 200
und 404 unterscheidet. Eine eigene Meldung gehört nach P9, wo alle
Uhr-Änderungen liegen.

---

## P5 — Papierkorb und gelöschte Flugtage (Web 4.3.0)

Eine Entscheidung (D1: ablehnen und melden, nicht still wiederherstellen), vier
Stellen.

| Befund | Änderung |
|---|---|
| M3-01 | Tages-Schnittstelle lehnt ab und meldet; auch das Lesen nennt den Zustand |
| M3-16 | Import holt gelöschte Tage nicht mehr zurück, überspringt und zählt |
| M5-10 | Wiedereinspielen lehnt ab und benennt den Fall |
| M4-04 | Sperrliste und Papierkorbprüfung gelten für **beide** Arten |

### Nachweis gegen MariaDB

- Alte Fassung belegt: `ON DUPLICATE KEY UPDATE` ohne Bedingung auf den
  Löschzustand überschreibt den Tag und lässt ihn gelöscht — die Eingabe ist
  weg, die Antwort lautet „ok".
- Neue Vorabprüfung findet den Tag im Papierkorb (1 Treffer) und lehnt ab.
- Import-`UPDATE` mit `deleted_at IS NULL` ändert 0 Zeilen statt den Tag
  zurückzuholen.
- Sperrliste trennt sauber: `r-123` als Ruhesegment gesperrt (1 Treffer), als
  Einsatz nicht (0 Treffer).

### Umsetzungsdetail zu M4-04

Beide Prüfungen (Sperrliste, Papierkorb) sind aus dem Einsatz-Zweig **vor** die
Fallunterscheidung gezogen. Damit ist die Lücke nicht nur geschlossen, sondern
strukturell nicht wiederholbar: Ein künftiger dritter Datensatztyp bekäme sie
automatisch.

---

## P3 — Gemeinsame Prüfschicht anwenden (Web 4.2.0)

Die aufwendigste und wirkungsvollste strukturelle Änderung: Alle vier
Schreibwege rufen jetzt `validate_lib.php` auf.

| Befund | Änderung | Weg |
|---|---|---|
| M5-02 | Wiedereinspielen prüft überhaupt erst — vorher 0 von 9 Prüfungen | Sicherung |
| M4-05 | Koordinatenbereiche und Mengenbegrenzungen ergänzt | Uhr |
| M3-04 | Eine Grenze für den Patientenblock (40…60000) statt dreier | alle |
| M2-04 | Musterverletzung wird gemeldet statt übergangen | Formular |
| M3-02 | Kalendertagsprüfung angewendet | alle |
| M6-04 | Kalendertagsprüfung bei der Flugtag-Anlage | Formular |
| M7-02 | Entdoppelung der Phasen entfernt, Mengengrenze stattdessen | Import |
| M3-03 | Zugehörigkeit direkt abfragen statt aus der Zeilenzahl erschließen | Import |
| M5-14 | Übersprungene Datensätze nach Ursache aufschlüsseln | Import, Sicherung |

### Zwei Grundsätze, die die Umsetzung geprägt haben

**Ein schlechter Wert verwirft den Wert, nicht den Vorgang.** Auf dem Uhr-Weg,
weil die Uhr nichts nachliefern kann, was sie gelöscht hat — ein Abbruch wegen
einer krummen Koordinate könnte einen ganzen Einsatz kosten. Beim
Wiedereinspielen, weil wer eine Wiederherstellung startet, meist keinen zweiten
Versuch hat.

**Was verworfen wird, wird genannt.** Sonst wäre die Prüfung nur eine leisere
Art des Datenverlusts. Uhr: `rejected` in der Antwort. Import und
Wiedereinspielen: Aufschlüsselung nach Ursache in der Meldung.

### Ein Fehler aus P0, den erst dieser Einsatz gezeigt hat

Die Meldung zu Koordinaten lautete „außerhalb von ±9" statt „±90" — die
Formatierung schnitt nachlaufende Nullen ab. Aufgefallen an der Testausgabe,
nicht beim Lesen. Behoben.

### Nachweis

Eine bösartige Nutzlast mit je einem Fehler pro Kategorie gegen die Prüfschicht:
unmöglicher Kalendertag verworfen, gültiger Zeitstempel erhalten, zu kurzer
Chiffretext verworfen, `"viel"` wird NULL statt 0, Phase 10 verworfen,
**drei Einträge derselben Phase 5 bleiben erhalten**, Koordinate 91 verworfen
ohne die Phase zu verlieren, unbekannte Reanimationsart verworfen — und jede
Ursache einzeln benannt.

Zu M3-03 gegen echte Datenbank: Eine Aktualisierung mit unveränderten
Kopfdaten liefert nachweislich 0 geänderte Zeilen; die neue Abfrage auf
Kennung, Nutzerkennung und Löschzustand liefert dagegen korrekt 1.

Zusätzlich 9 Fälle für die neue Funktion `pruef_utc_oder_sql`, die beide
Zeitformate annimmt (Uhr mit `Z`, Sicherung in Datenbankschreibweise).

### Der Vertrag ist ehrlicher geworden

Drei Zeilen der Tabelle „Stand der Durchsetzung" im JSON-Vertrag stehen jetzt
auf „durchgesetzt": Kalendertag, Wertebereiche und Mengen, Antwortfeld
`rejected`. Offen bleiben das Verhalten bei leeren Listen samt `kept_*` (P8)
und der Zufallsanteil in der Client-Kennung (P9).

---

## P2 — Die Kette „unlesbarer Schlüssel" schließen (Web 4.1.2)

| Befund | Änderung | Baustein |
|---|---|---|
| M1-12 | Prüfsumme beim Neuverpacken prüfen — passt sie nicht, wird nichts geändert | B4, S2 |
| M2-05 | Zwischengespeicherter Schlüssel an seine Hülle gebunden, läuft mit der Sitzung ab | B5 |
| M6-02 | Unlesbare Datensätze mit ⚠ statt – , Hinweis über der Liste | B8 |
| M1-03 | Sitzungsablauf räumt die Schlüssel im Browser | B6 |
| M1-04 | Grund des Sitzungsendes wird angezeigt | B6 |
| M5-01 (2+3) | Sicherung trägt ihr Herkunftskonto, Einspielen entscheidet und fragt | B4 |

### Ein Fehler, den erst der Test gezeigt hat

Der in P0 angelegte Baustein B5 räumte beim Verwerfen eines nicht passenden
Schlüssels **auch den Datenschlüssel** mit weg. Der wird aber unmittelbar
danach gebraucht, um die Hülle neu zu entpacken. Die Folge wäre nicht ein
neuer Entsperrvorgang gewesen, sondern **gar kein Schlüssel** — also genau der
Zustand, den der Baustein verhindern soll, und noch dazu nur beim Kontowechsel,
dem seltensten Fall.

Aufgefallen ist das an zwei fehlgeschlagenen Prüfungen, nicht beim Lesen des
Codes. Behoben: `verwerfeInhalt()` räumt nur den Inhaltsschlüssel samt Bindung,
`beenden()` räumt beides.

### Entschieden: Der Sicherungslauf bricht NICHT ab, wenn alles unlesbar ist

Das Konzept stellt es zur Erwägung. Dagegen spricht seit Web 4.1.0 ein starkes
Argument: Die Sicherung **nimmt den Chiffretext jetzt mit**. Sie ist damit
genau das Richtige, was jemand mit einem nicht passenden Schlüssel tun sollte —
sie bewahrt die Daten, statt sie zu verlieren. Ein Abbruch nähme ihm das
einzige Mittel aus der Hand, das hilft.

Stattdessen: deutliche Meldung mit Zahl und Ursache (seit Web 4.1.0) und der
Hinweis, vor weiteren Schritten den Wiederherstellungsschlüssel bereitzuhalten.

### Nachweis

- **Kontowechsel im selben Tab:** Konto B bekommt seinen eigenen Schlüssel; die
  ungeprüfte Fassung lieferte im Gegentest nachweislich den fremden.
- **Ablauf:** Ein Schlüssel jenseits der 30-Minuten-Frist wird verworfen und
  neu entpackt.
- **M1-12 gegen echte Datenbank:** Bestandskonto ohne Prüfsumme wird angenommen
  und bekommt sie; richtige Prüfsumme angenommen; falsche, fehlende und
  unbrauchbare abgelehnt, ohne etwas zu ändern.
- **M6-02:** Die drei Zustände (`ok`, `leer`, `unlesbar`) werden getrennt
  gezählt, `_patFehler` steht nur am unlesbaren Datensatz.

---

## P7 — Dokumentation und Verträge (Web 4.1.1)

Das einzige Paket, das fast keinen Code berührt — und trotzdem nicht ans Ende
gehört: Eine falsche Zusicherung im Vertrag richtet Schaden an, solange sie
dort steht.

| Befund | Änderung |
|---|---|
| M7-01 | Phase 10 aus dem JSON-Vertrag entfernt (drei Stellen), Abschluss über `final` + `ended_at` beschrieben |
| M3-09 | Beschriftung für Phase 10 aus `PHASE_LABELS` entfernt |
| M7-05 | Reanimationsarten: der Vertrag ist die führende Liste |
| M7-06 | Format und Präfixe der Client-Kennung festgelegt, samt daran hängendem Verhalten |
| M4-02 | Fehlende gegen leere Liste ausdrücklich geregelt |
| M6-11 | Festgehalten, welche Felder im verschlüsselten Block liegen und welche nicht |
| M7-04 | Zwei Zusicherungen richtiggestellt (Kopplungscode-Kommentar in `schema.sql`, Rettungsseiten-Verweis in `update.php`) |
| M7-09 | Gerätebezug in `Uhr-Layout.md` ergänzt, Prüfgeräte und Konvention benannt |
| M5-17 | Aufbewahrungsfrist der Sperrliste (90 Tage) im Handbuch genannt |
| — | Grenzen und Mengen aus B1 in den Vertrag übernommen |

### Der neue Abschnitt „Stand der Durchsetzung"

Der Vertrag beschreibt jetzt Regeln, die der Server **noch nicht** durchsetzt —
Kalendertagsprüfung, Wertebereiche und Mengen auf dem Uhr-Weg (P3), das
Verhalten bei leeren Listen und die Antwortfelder `kept_*`/`rejected` (P8), der
Zufallsanteil in der Client-Kennung (Uhr-App).

Das ist genau der Fehler, den M7-04 rügt — außer man legt ihn offen. Deshalb
steht in Abschnitt 0 des Vertrags eine Tabelle, die je Regel sagt, ob sie
durchgesetzt ist. Sie verschwindet, wenn alle Zeilen „durchgesetzt" lauten.

Die Alternative wäre gewesen, den Vertrag erst nach P3 und P8 zu ergänzen.
Dagegen spricht die Reihenfolge des Konzepts: P7 steht bewusst vorn, weil die
falschen Angaben *jetzt* Schaden anrichten und nicht erst am Ende.

### Bewusst nicht geändert

- **Das Label der Migration `2026_07_20_kopplung`** („5 Zeichen, 60 Minuten
  gültig") beschreibt korrekt, was diese Migration damals tat. Ein
  Migrationslabel ist ein historischer Eintrag; es nachträglich umzuschreiben
  wäre dasselbe wie das Umschreiben eines Changelog-Eintrags.
- **`docs/archiv/Anforderungen_v1.2.md`** nennt weiterhin Phase 10 und den
  Bereich 2–10. Das Archiv gibt den Stand von damals wieder und wird nicht
  fortgeschrieben.

---

## P1 — Sofortmaßnahmen (Web 4.1.0)

Sieben Änderungen, die drei der vier Befundketten an je einer Stelle
unterbrechen. Wenn nur ein Paket umgesetzt würde, dann dieses.

| Befund | Änderung | Datei |
|---|---|---|
| M1-01 | Pseudo-Salt von 64 auf 32 Zeichen — die Antwortlänge verriet, ob ein Konto existiert | `auth_salt.php` |
| M4-01 | Kopplungscode 6 Zeichen, 10 Minuten, Ratenschutz, Prüfmuster auf das tatsächliche Alphabet, höchstens ein offener Code je Konto | `pair.php`, `einstellungen.php`, `db.php` |
| M4-03 | Entwerten vor dem Prüfen — der Code war nicht wirklich einmalig | `pair.php` |
| M5-01 (1) | Chiffretext bei fehlgeschlagener Entschlüsselung behalten (Formatänderung S8) | `einstellungen.php` |
| M5-03 | Antwortstatus prüfen, bevor eine Sicherungsdatei entsteht | `einstellungen.php` |
| M6-01 (1) | Wartungsseite zweistufig, Sicherungsrat vor den Lauf | `update.php` |
| M2-02 (3) | Passwortstärke = Verschlüsselungsstärke, in Worten | `pw_handling.php` |

### Zahlen zur Kopplung

| | vorher | jetzt |
|---|---|---|
| Coderaum | 5 Zeichen aus 32 = **25 Bit** (33,5 Mio.) | 6 Zeichen aus 32 = **30 Bit** (1,07 Mrd.) |
| Gültigkeit | 60 Minuten | 10 Minuten |
| Bremse | 0,3 s je Anfrage, **nicht parallelisierungsfest** | Ratenschutz je IP, greift vor jeder Arbeit |
| voller Durchlauf | **1,4 Stunden** bei 2000 parallelen Anfragen | praktisch unerreichbar |
| Prüfmuster | `[A-Z0-9]{4,8}` — ließ 0/O/1/I zu, die es nie gibt | genau das Alphabet, genau 6 Zeichen |

### Nachweis

Gegen MariaDB 10.11 geprüft: Zwei gleichzeitige Einlösungen desselben Codes —
die erste ändert eine Zeile und gewinnt, die zweite ändert null und wird
abgewiesen. Ein 30 Minuten alter Code wird abgelehnt (vorher wäre er noch
gültig gewesen). Ein neuer Code entwertet den alten.

Zur Wartungsseite: Bei ausstehender Migration ändert der Aufruf nachweislich
nichts — `schema_migrations` bleibt bei 25 Einträgen, die Spalte entsteht
nicht. Erst der Knopf legt sie an und verbucht die Migration.

Zur Sicherung: Ein Einsatz mit fremdem Schlüssel behält seinen Chiffretext und
ist nach dem Zurückspielen ins Ursprungskonto wieder lesbar; die alte
Reihenfolge (Entfernen hinter dem Fehlerblock) verlor ihn nachweislich.

### Was in diesem Paket bewusst offen bleibt

* **M5-01 Teile 2 und 3** (Meldung getrennt zählen, Chiffretext beim
  Einspielen über die Prüfsumme dem Konto zuordnen) gehören zu P2. Der
  mitgeführte Chiffretext wird beim Einspielen heute schon unverändert
  übernommen — in ein *fremdes* Konto gespielt bleibt er unlesbar, ohne
  Hinweis. Das ist gegenüber dem bisherigen Zustand (Daten weg) die bessere
  Richtung, aber noch nicht der Zielzustand.
* **M4-01 Ratenschutz beim Koppeln** ist hier vollständig; die übrigen
  Endpunkte (Anmeldung, Salt, Zurücksetzen) folgen in P4.
* **M6-01 Teil 2** (Inhaltsprüfung destruktiver Migrationen) gehört zu P9.
* **M2-02 Teile 1 und 2** (Prüfung im Skript, Stärkeanzeige) gehören zu P9;
  der Baustein dafür liegt seit P0 bereit.

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
