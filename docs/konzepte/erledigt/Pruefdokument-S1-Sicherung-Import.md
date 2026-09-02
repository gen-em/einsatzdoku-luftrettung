# Prüfdokument S1 — was **du** noch prüfen musst

Das Prüfprotokoll im Konzept (`docs/Konzept-S1-Sicherung-Import.md`,
Abschnitt 6 und 10) beantwortet „ist es belegt?". Dieses Dokument beantwortet
die andere Frage: **was steht noch aus, und auf welchem Weg?**

Ausgeliefert wird **Web 8.0.0**. Eine Migration gibt es **nicht** — die
Spalten `deleted_at` und `deleted_with_day` liegen seit jeher, sie standen nur
bisher leer in der Sicherungsdatei. `update.php` muss nach diesem Deploy also
**nicht** aufgerufen werden.

---

## 1. Was NICHT geprüft werden konnte — und warum

Das steht hier oben und nicht in einer Fußnote.

### 1.1 Alles Gemessene stammt aus einer lokalen Installation

Kein Zugang zum Produktivserver. Lokal: MariaDB 10.11, PHP 8.4, Schema regulär
über `install.php`, Browserschritte in Chromium (Playwright, headless).

Was das offenlässt:

- **Der FTPS-Deploy.** Ob die **neue** `server/demo/fixture.json.gz` (744 KB)
  sauber hochgeht. Sie hat sich in Inhalt und Format geändert (Format 2, ohne
  `nachlauf`), nicht nur in der Größe.
- **Die PHP-Fassung des Hosters.** Lokal 8.4.
- **`max_execution_time`.** Der Demo-Reset dauert lokal **5,8 s** und läuft
  **innerhalb** einer Web-Anfrage. Er ist durch S1 nicht langsamer geworden
  (Ausgangswert rund 6 s), aber er bleibt eng, wenn der Hoster bei 30 s
  abschneidet.
- **Sicherungen echter Konten.** Die Admin-Sicherung wurde lokal für zwei
  Konten erzeugt. Wie sie sich bei vielen Konten und großen Beständen verhält,
  ist unverändert und hier nicht neu gemessen.

### 1.2 Ältere ausgelieferte Stände nehmen v7-Dateien an — ungeprüft und unprüfbar

`api/backup_restore.php` weist erst unterhalb von Nutzlast **6** ab. Ein
bereits ausgelieferter Stand (Web 7.3.1 und älter) nimmt deshalb eine
Version-7-Datei **an**, wertet `deleted_at` aber nicht aus und brächte den
Papierkorb als **aktiven Bestand** zurück.

Das ist **nicht** geprüft, und es lässt sich auch nicht mehr beheben: Eine
Sperre hätte in jenen Ständen stehen müssen. Es steht als Warnung in
`docs/Backup-Format.md` 4 und im Handbuch 6 — als Warnung, nicht als Zusage.
Wer eine Sicherung aus dieser Fassung in eine ältere Installation einspielt,
sieht dort anschließend im Papierkorb nach.

### 1.3 Der Papierkorb-Ablauf nach 90 Tagen

Dass eingespielte Papierkorbeinträge nach `TRASH_DAYS` vom Aufräumjob
endgültig entfernt werden, folgt aus dem gesetzten `deleted_at` und ist
**nicht** durch Zeitablauf beobachtet. Geprüft ist der Zeitstempel selbst:
Alle Einträge eines Einspielvorgangs tragen denselben, und zwar den des
Vorgangs.

### 1.4 Der Uhr-Weg zum gelöschten Diensttag ist nicht am Gerät geprüft

Seit dieser Fassung löst eine Nachlieferung, deren Dienstkennung auf einen
**gelöschten** Diensttag zeigt, einen **neuen** Tag aus statt am
Papierkorb-Eintrag zu landen (Backlog Nr. 33). Geprüft ist das an
`dt_zu_dayref()` unmittelbar — neuer Tag, Dienstkennung umgebogen, beides
gemessen — und die entsprechende Bedingung in `ingest.php` durch Lesen.

**Nicht geprüft ist eine echte Nachlieferung einer Garmin-Uhr auf einen
gelöschten Diensttag.** Es gibt in dieser Umgebung kein Gerät. Wer eines hat:
Punkt 4.12 der Prüfliste.

### 1.5 Kein echtes Gerät, kein SMTP

Unverändert gegenüber P1: Der Bestand ist über `ingest.php` eingespielt, aber
nicht von einer echten Uhr; E-Mail-Versand ist lokal nicht eingerichtet.

### 1.6 Die Referenzinstallation wurde mitten in der Phase neu aufgebaut

Ein Prüfmittel (`browser/demo_pruefen.mjs`) lief versehentlich gegen die
Referenzinstallation und hat dort das Referenzkonto verändert — Einzelheiten in
Konzept Abschnitt 10. Der Referenzstand wurde vollständig neu aufgebaut und
danach nachgezählt (87/5, 100/5, 16/1, 55 861 Spurpunkte, identisch zum
dokumentierten Stand). **Alle Zahlen unten stammen aus dem Stand nach dem
Wiederaufbau**, mit einer Ausnahme, die als solche gekennzeichnet ist (die
C1-Messungen lagen davor und sind unberührt).

Das Prüfmittel hat einen Riegel bekommen; er ist in beide Richtungen geprüft.

---

## 2. Was maschinell geprüft wurde — mit Mittel und Zahl

| Was | Mittel | Zahl |
|---|---|---|
| Quelldaten: Schema, Sachlogik, Abdeckung | `quelldaten/pruefen.py` | **5 680** Einzelprüfungen, 78 Matrixzeilen, 0 offen |
| Erzeugte Nutzlasten gegen den JSON-Vertrag | `generator/pruefen.py` | **283 985** Einzelprüfungen, keine Befunde |
| Einspiellauf über die regulären Wege | `einspielen/einspielen.py` | **526** Ingest-Anfragen, 0 Fehler; Sperrlisten-Prüfschritt bestanden |
| **Kreislauf Sicherung** (P-S1-01) | `vergleich/kreislauf.py --art edbak` | **286 739** Einzelvergleiche, **0 unerklärt**, 16 erwartet |
| **Kreislauf CSV** (P-S1-02) | `vergleich/kreislauf.py --art csv` | **8 797** Einzelvergleiche, **0 unerklärt**, 859 erwartet, 0 ungenutzte Regeln |
| Probe aufs Exempel, Sicherung (P-S1-12) | `vergleichen.py --testabweichung`, gleiche Datei beidseitig, ohne `--ausnahmen` | **12/12** bestanden (vier davon neu) |
| Probe aufs Exempel, CSV (P-S1-12) | dieselbe | **10/10** bestanden |
| **Wiederherstellungsprobe**, heutiger Stand | `tools/wiederherstellungs-probe/probe.php` | **30 Erwartungen, 0 nicht erfüllt** (vier Teile) |
| **Wiederherstellungsprobe**, Stand vor C9 (`5e68024`) | dieselbe gegen eine Kopie von `server/` aus diesem Stand | **11 von 30 nicht erfüllt** — genau in Teil 3 und 4 |
| **Wiederherstellungsprobe**, Stand vor C8 (`d078494`) | dieselbe, mit den damaligen 16 Erwartungen | **12 von 16 nicht erfüllt** — Teil 2 endet mit `SQLSTATE[23000] … Duplicate entry` |
| Invariante `deleted_with_day` (P-S1-05) | SQL über **alle** Konten der Prüfinstallation | `1` an aktivem Tag: **0**; `1` ohne `deleted_at`: **0** |
| `created_at` wörtlich (P-S1-07) | SQL, paarweise über `client_ref` | **87 von 87 gleich**, 0 abweichend |
| Angriffswerte-Regression (P-S1-13) | `browser/angriffswerte.mjs` | **42** Einzelprüfungen, 0 Befunde, 0 Konsolenfehler |
| Syntax aller berührten PHP-Dateien | `php -l` | fehlerfrei |
| Syntax aller berührten JS-Dateien | `node --check` | fehlerfrei |

**Zum Vergleich, was sich geändert hat:** Der CSV-Kreislauf stand vor S1 auf
**6 unerklärten** Abweichungen (Backlog Nr. 27 und 28). Der Sicherungs-Kreislauf
stand auf 0, verglich aber **weniger**: 269 439 statt 286 739 Einzelvergleiche —
der Papierkorb war gar nicht in der Datei.

---

## 3. Was im Browser geprüft wurde

Alles über Playwright/Chromium gegen die lokale Installation, jeder Schritt
über die reguläre Oberfläche.

| Was | Ergebnis |
|---|---|
| Sicherung erstellen (Einstellungen → Backup) | Datei entsiegelt: `version` 7, 87/100/16, davon **5/5/1 im Papierkorb**; 0 Konsolenfehler |
| Admin-Sicherung, Übersicht | „87 Einsätze, 16 Diensttage, 100 Ruhezeiten, **davon im Papierkorb: 5 Einsätze, 1 Diensttag, 5 Ruhezeiten**, 2.483 KB"; leere Konten: „nichts im Papierkorb" |
| Version-6-Datei einspielen (P-S1-10) | angenommen, 82/95/15 korrekt eingespielt; einzige Abweichung im Vergleich: `kopf/version 6 → 7` |
| D1, Fall Zielkonto (P-S1-03) | „Diensttag liegt hier im Papierkorb 2, Diensttag wurde übersprungen 13"; kein roher Schlüssel in der Meldung |
| D1, Fall Datei (P-S1-04) | Papierkorb zeigt 1 Diensttag mit 4 mitgelöschten Einsätzen und 1 einzeln gelöschten; Wiederherstellen holt genau die 4, der einzelne bleibt liegen |
| Zombie-Gegenprobe (P-S1-05) | Einsatz kommt **einzeln** gelöscht zurück, Zieltag bleibt aktiv |
| Frist (P-S1-06) | „In den Papierkorb übernommen: 5 Einsätze, 5 Ruhesegmente, 1 Diensttag — die 90-Tage-Frist beginnt für sie neu." |
| Nr. 27 (P-S1-08) | 4 mehrzeilige Notizen, **164/253/119/150** Zeichen, je 1 Umbruch, nach dem Umlauf wörtlich gleich |
| Nr. 28 (P-S1-09) | Referenzfall bleibt `final = 0` und Ende leer — auch nach dem Überschreiben aller 82 Zeilen |
| Excel-Rückweg (ohne `ende`/`final`) | 82× `final = 1`, 82× `ende = Beginn` — unverändertes Verhalten |
| Demo-Abnahme (P-S1-11) | `demo_pruefen.mjs`: **24** Einzelprüfungen, 0 Befunde, 0 Konsolenfehler; Papierkorb **5/1/5** vor und nach dem Reset |
| Wächter in `demo_pruefen.mjs` | falsches Adminkennwort → **Abbruch, Rückgabe 2**; Lauf gegen die Referenzinstallation → **Abbruch, Rückgabe 2**, Referenzkonto unangetastet |
| Mengenbremse Demo | `demo_bremse.mjs`: erste Abweisung bei Anmeldung 21 (Grenze 20), Gegenprobe kommt herein |
| CSV-Import der vier Referenzzeilen | `browser/csv_import.mjs`: 4 Einsätze, 0 Hinweise, 0 Fehler |
| **Mischfall im Papierkorb** (F-S1-E, F-S1-G) | `browser/papierkorb_misch.mjs`: **14 Einzelprüfungen, 0 Befunde, 0 Konsolenfehler**. Ein Diensttag mit fünf mitgelöschten und einem einzeln gelöschten Einsatz übersteht den vollen Umlauf; „Wiederherstellen" beim einzelnen wird mit Begründung abgelehnt, solange der Tag im Papierkorb liegt; nach dem Wiederherstellen des Tages geht es |
| dieselbe Prüfung gegen den Stand vor C9 | **4 Befunde**: Das Zurückholen ging ohne Meldung durch, der Einsatz wurde mit dem Tag aktiv, und die Gegenprobe konnte deshalb nicht greifen |
| dieselbe gegen den Stand vor C8 | **2 Befunde**: Ziel zeigt 1 statt 3 einzeln gelöschte Einsätze, und der Diensttag nennt 6 statt 5 — der einzeln gelöschte war im Tag verschwunden |
| **Bericht „Einsätze ohne Diensttag"** (`update.php`) | ohne Waisen: „Keine. Jeder aktive Einsatz hängt an einem Diensttag"; mit zwei künstlich erzeugten: **2**, je mit Konto, Datum/Uhrzeit und Kennung; 0 Konsolenfehler |

---

## 4. Prüfliste — was du selbst tun musst

Abhaken. Je Punkt: **Weg**, **Erwartung**, **woran ein Scheitern zu erkennen
ist**.

### 4.1 Nach dem Deploy: die Sicherung enthält den Papierkorb

- [ ] **Weg:** Anmelden → **⚙ Einstellungen → Backup** → Passwort zweimal
      eingeben → *Backup erstellen*. Vorher unter **Papierkorb** nachsehen, wie
      viel darin liegt.
- [ ] **Erwartung:** Die Statuszeile nennt **mehr** Einsätze als die
      Tagesübersicht zeigt — genau um die Zahl im Papierkorb.
- [ ] **Scheitern erkennbar an:** Die Zahl entspricht dem aktiven Bestand.
      Dann greift der alte Filter noch, und die Datei ist unvollständig
      (Deploy nicht angekommen, oder `WEB_VERSION` nicht erhöht → Browser hat
      alte Dateien).

### 4.2 Der Rückweg bringt den Papierkorb als Papierkorb

**Nicht in dein Produktivkonto einspielen.** Nimm ein zweites, leeres Konto
(Adminbereich → Konto anlegen) oder das Demo-Konto.

- [ ] **Weg:** Im leeren Konto **Einstellungen → Backup → Importieren**, die
      eben erzeugte Datei und ihr Passwort.
- [ ] **Erwartung:** Die Rückmeldung endet mit „In den Papierkorb übernommen:
      … — die 90-Tage-Frist beginnt für sie neu." Unter **Papierkorb** stehen
      danach dieselben Einträge wie im Quellkonto.
- [ ] **Scheitern erkennbar an:** Der Satz fehlt und die gelöschten Einsätze
      stehen in der **Tagesübersicht**. Dann läuft der alte Rückweg — das ist
      der schlimmere Fall von zwei, weil er wie Erfolg aussieht.

### 4.3 Ein gelöschter Diensttag kommt als Ganzes zurück

- [ ] **Weg:** Im Zielkonto aus 4.2 auf **Papierkorb** gehen, beim gelöschten
      Diensttag *Wiederherstellen* klicken.
- [ ] **Erwartung:** Der Tag steht wieder in der Tagesleiste und bringt genau
      seine mitgelöschten Einsätze mit. Einzeln gelöschte Einsätze bleiben im
      Papierkorb liegen.
- [ ] **Scheitern erkennbar an:** Der Tag kommt **leer** zurück (dann ist
      `deleted_with_day` falsch geschrieben), oder ein Einsatz, den du einzeln
      gelöscht hattest, kommt mit (dann ebenfalls).

### 4.4 Die 90 Tage beginnen neu

- [ ] **Weg:** Im Zielkonto aus 4.2 die Spalte „gelöscht am" im Papierkorb
      lesen.
- [ ] **Erwartung:** Alle Einträge tragen den **Zeitpunkt des Einspielens**,
      nicht den ursprünglichen Löschzeitpunkt — und alle denselben.
- [ ] **Scheitern erkennbar an:** Verschiedene oder alte Zeitpunkte. Dann
      wurde der Wert aus der Datei übernommen, und Einträge mit abgelaufener
      Frist verschwinden beim nächsten Aufräumlauf.

### 4.5 Ein Import überspringt Tage, die *hier* im Papierkorb liegen

- [ ] **Weg:** Im Zielkonto einen Diensttag löschen, dann dieselbe Sicherung
      **erneut** einspielen.
- [ ] **Erwartung:** Die Rückmeldung nennt „Diensttag liegt hier im
      Papierkorb" mit Anzahl, und die Einsätze dieses Tages laufen unter
      „Diensttag wurde übersprungen".
- [ ] **Scheitern erkennbar an:** Ein roher Schlüssel wie
      `tag_im_papierkorb 2` in der Meldung (dann fehlt die Beschriftung), oder
      der gelöschte Tag steht plötzlich wieder aktiv da (dann greift die
      D1-Regel nicht).

### 4.6 Der CSV-Rückweg: Notizen und der offene Einsatz

Braucht einen Einsatz mit **mehrzeiliger** Notiz und einen **nicht
abgeschlossenen** Einsatz. Beide gibt es im Demo-Konto.

- [ ] **Weg:** **Import / Export** → Export, Format *CSV (Standard)*, Zeitraum
      *Alles*, personenbezogene Angaben **an**, Passwortschutz aus. Danach die
      `.zip` im selben oder einem leeren Konto wieder importieren.
- [ ] **Erwartung:** Die mehrzeilige Notiz ist im Einsatzformular weiterhin
      mehrzeilig. Der offene Einsatz hat weiterhin **kein** Ende und gilt
      nicht als abgeschlossen.
- [ ] **Scheitern erkennbar an:** Die Notiz steht in einer Zeile (Nr. 27
      wieder da), oder der offene Einsatz hat plötzlich eine Endzeit gleich
      seiner Alarmzeit (Nr. 28 wieder da).

### 4.7 Der Excel-Rückweg verhält sich wie bisher

- [ ] **Weg:** Denselben Bestand als *Excel (Standard)* exportieren und in ein
      leeres Konto importieren.
- [ ] **Erwartung:** Alle Einsätze gelten als abgeschlossen, das Ende ist
      gleich dem Beginn — **auch** der vorher offene. Diese Datei führt weder
      `ende` noch `final`; das ist so beschrieben (`Export-Format.md` 5.2).
- [ ] **Scheitern erkennbar an:** Einsätze ohne Ende oder mit `final = 0`.
      Dann sendet der Browser Felder, die die Datei nicht führt.

### 4.8 Das Demo-Konto nach dem Deploy

- [ ] **Weg:** Adminbereich → **Demo-Konto** → *Auf Standard zurücksetzen*.
- [ ] **Erwartung:** Der Bericht nennt `papierkorb: {einsaetze: 5,
      diensttage: 1, ruhezeiten: 5}` und **keinen** Nachlauf-Zähler. Die
      Bestandszahlen darüber: 15 Diensttage, 82 Einsätze, 95 Ruhesegmente,
      5 im Papierkorb, 3 Geräte.
- [ ] **Scheitern erkennbar an:** „im Papierkorb 0" (die neue Fixture ist
      nicht angekommen), oder ein Fehler beim Zurücksetzen (dann liegt noch
      die alte Fixture und der Reset läuft trotzdem — das ist geprüft und
      soll funktionieren; ein Fehler wäre also etwas anderes).

### 4.9 Die Administrationsübersicht nennt den Papierkorbanteil

- [ ] **Weg:** Adminbereich → **Sicherungen** → *Alle sichern*.
- [ ] **Erwartung:** Je Zeile „… davon im Papierkorb: N Einsätze, M
      Diensttage, K Ruhezeiten" bzw. „nichts im Papierkorb".
- [ ] **Scheitern erkennbar an:** Der Zusatz fehlt bei einer **neuen**
      Sicherung. Bei **alten** Sicherungen fehlt er absichtlich — dort wurde
      die Zahl nie erhoben, und eine Null wäre eine Behauptung.

### 4.10 Der Mischfall im Papierkorb — der Fehler, der beinahe durchgegangen wäre

Der einzige Punkt dieser Liste, der aus einem **eigenen** Fehler entstanden
ist (F-S1-E). Er braucht einen Diensttag, an dem ein Einsatz **einzeln** und
ein anderer **mit dem Tag** gelöscht wurde.

Lokal ist er belegt (`browser/papierkorb_misch.mjs`, 10 Einzelprüfungen, 0
Befunde). Er steht trotzdem hier, weil er die Stelle ist, an der ein Fehler im
Rückweg **still** bliebe: Man sieht ihn nicht beim Einspielen, sondern erst
Wochen später, wenn jemand etwas im Papierkorb sucht.

- [ ] **Weg:** Einen Diensttag mit mindestens zwei Einsätzen wählen. Einen
      davon einzeln löschen. Dann den **ganzen Tag** löschen. Sichern, in ein
      **leeres** Konto einspielen, dort in den Papierkorb sehen.
- [ ] **Erwartung:** Der Papierkorb zeigt den Diensttag **und daneben** den
      einzeln gelöschten Einsatz als eigenen Eintrag. Stellt man den Tag
      wieder her, kommen nur die mit ihm gelöschten Einsätze zurück; der
      einzeln gelöschte bleibt im Papierkorb liegen.
- [ ] **Scheitern erkennbar an:** Der einzeln gelöschte Einsatz taucht im
      Papierkorb **gar nicht** auf (dann trägt er fälschlich
      `deleted_with_day = 1`), oder er wird beim Wiederherstellen des Tages
      **mit** aktiv, obwohl er vorher schon gelöscht war.

### 4.11 Der Papierkorb lässt keinen halb sichtbaren Einsatz mehr zu

- [ ] **Weg:** Einen Diensttag mit mindestens zwei Einsätzen wählen. Einen
      davon einzeln löschen, dann den ganzen Tag. Im Papierkorb beim **einzeln
      gelöschten Einsatz** auf „Wiederherstellen" klicken.
- [ ] **Erwartung:** Es passiert nichts außer einer Meldung — „Der Diensttag
      dieses Einsatzes liegt ebenfalls im Papierkorb. Stelle zuerst den
      Diensttag wieder her." Der Einsatz steht danach unverändert im
      Papierkorb. Holst du erst den Diensttag zurück, geht es.
- [ ] **Scheitern erkennbar an:** Der Einsatz verschwindet aus dem Papierkorb
      und taucht in keiner Tagesübersicht auf — dann ist er aktiv an einem
      gelöschten Tag, und genau das soll nicht mehr gehen. Oder: Es passiert
      nichts und **es steht auch keine Meldung da** — dann ist die Ablehnung
      zwar wirksam, aber stumm.

### 4.12 Die Uhr legt einen neuen Diensttag an — braucht ein Gerät

Der einzige Punkt dieser Liste, den ich mangels Gerät gar nicht prüfen konnte
(Abschnitt 1.4).

- [ ] **Weg:** Einen Dienst mit der Uhr dokumentieren und hochladen. Im Web den
      entstandenen **Diensttag löschen**. Danach mit der Uhr weiter
      dokumentieren, sodass sie für denselben Dienst nachliefert.
- [ ] **Erwartung:** Es entsteht ein **neuer** Diensttag mit den
      nachgelieferten Einsätzen. Der gelöschte bleibt im Papierkorb, unberührt.
      Beide lassen sich über **Diensttage zusammenführen** wieder vereinen.
- [ ] **Scheitern erkennbar an:** Die Uhr-Daten sind nirgends zu finden (dann
      wurden sie doch am gelöschten Tag abgelegt und sind unsichtbar), oder der
      gelöschte Diensttag steht plötzlich wieder aktiv da.

### 4.13 Der Regressionslauf (R24)

- [ ] **Weg:** `python3 tools/referenzdatensatz/vergleich/kreislauf.py --art
      edbak --frisch` und dasselbe mit `--art csv`.
- [ ] **Erwartung:** beide **0 unerklärte Abweichungen**; erwartete: 16 (edbak)
      und 859 (CSV); **0 ungenutzte Regeln**.
- [ ] **Scheitern erkennbar an:** Eine unerklärte Abweichung ist ein Befund
      der laufenden Phase — nicht in die Ausnahmeliste eintragen. Eine
      **ungenutzte** Regel ist ebenfalls ein Befund: Entweder beschreibt sie
      etwas, das es nicht mehr gibt, oder der Lauf hat weniger geprüft als
      gedacht.

---

## 5. Bekannte offene Punkte — kein Grund zur Beunruhigung, aber zu wissen

**Nichts aus S1 ist offen geblieben.** Die beiden Entscheidungen, die nach der
Nachlese noch anstanden, sind getroffen und umgesetzt (Konzept, C9). Sie
stehen unten mit in der Liste.

**Behoben, hier zur Nachverfolgung:**

| Fund | Wirkung | Backlog |
|---|---|---|
| **F-S1-A** | Der Rückweg der Ruhesegmente prüfte `started_at`/`ended_at` nicht; ein unbrauchbarer Wert brachte die ganze Wiederherstellung zu Fall statt eine Zeile. In C8 behoben, dabei auch das Schreiben der Spurpunkte auf **eine** Stelle für beide Arten gezogen | Nr. 31, erledigt |
| **F-S1-C** | Ein in der Datei **aktiver** Einsatz konnte auf einem **gelöschten** Zieltag landen und stand dann an einem Tag, den die Tagesliste nicht zeigt. Nicht neu — in derselben Form schon vor Web 8.0.0 erreichbar. In C8 entschieden (**E-S1-19**: ablehnen und zählen) und umgesetzt | Nr. 32, erledigt |
| **F-S1-E** | `deleted_with_day` aus der Datei wurde nie gelesen; ein **einzeln** gelöschter Einsatz an einem gelöschten Tag kam als mitgelöschter zurück und wäre beim Wiederherstellen des Tages ungewollt wieder aktiv geworden. **Von dieser Phase eingebaut** und in C8 behoben | kein Eintrag |
| **F-S1-F** | Ein doppeltes `seq` in einer Spur kippte über den Schlüsselkonflikt den ganzen Lauf. In C8 behoben (überspringen und melden) | Nr. 35, erledigt |
| **F-S1-G** | Ein **aktiver** Einsatz konnte an einem **gelöschten** Diensttag stehen und beim endgültigen Löschen des Tages ohne Diensttag zurückbleiben. Vier Klicks in der Oberfläche reichten dafür. In C9 an allen drei Ursachen abgestellt (Zurückholen wird abgelehnt, die Uhr löst einen neuen Tag aus, das endgültige Löschen nimmt alles mit und nennt es vorher); Altbestand meldet `update.php` | Nr. 33, erledigt |
| **F-S1-H** | Schritt 1 der Diensttag-Wiedererkennung nahm den ersten gefundenen Einsatz und verhängte dessen Tag über den ganzen Datei-Tag. In C9 auf „alle Kennungen zählen, nur ein eindeutiges Ergebnis benutzen, den Widerspruch als `tag_mehrdeutig` melden" umgestellt | Nr. 34, erledigt |
| **Nr. 24** | Der Formelschutz-Apostroph bleibt beim CSV-Rückimport im Wert stehen (3 Zellen). Bewusst so gelassen und jetzt dokumentiert | erledigt, dokumentiert |

---

## 6. Grenzen der benutzten Prüfmittel

**Das Vergleichswerkzeug vergleicht Klartext und normalisiert Flüchtiges.**
Zwei Änderungen in S1: `missions[].created_at` wird **nicht mehr**
wegnormalisiert (es kommt jetzt zurück), und `deleted_at` wird durch eine
Zeitmarke ersetzt, **ohne** die Unterscheidung leer/gesetzt zu verlieren. Was
das Werkzeug grundsätzlich nicht sehen kann, prüfen die Gegenproben im
`--testabweichung`-Lauf.

**Der `--testabweichung`-Lauf gehört ohne `--ausnahmen` gefahren** und mit
derselben Datei auf beiden Seiten. Mit geladener Ausnahmeliste schlägt eine
Hinprobe scheinbar fehl, weil sie eine echte Regel trifft.

**Der Kreislauf sieht nicht alles.** Der Formelschutz-Apostroph ist das
Musterbeispiel: Der Wert im Bestand ändert sich, die exportierte Datei nicht.
Wer prüft, ob ein Umlauf verlustfrei war, muss deshalb auch in den **Bestand**
sehen, nicht nur in die Dateien.

**Die Browserskripte** laufen headless in Chromium. Sie sehen, was im DOM
steht und was die Konsole meldet — nicht, ob etwas gut aussieht.

**`browser/demo_pruefen.mjs` verändert das Konto, gegen das es läuft.** Es hat
seit S1 einen Riegel und bricht ab, wenn unter der Demo-Adresse ein Konto
liegt, das nicht als Demo-Konto gekennzeichnet ist. Der Riegel ist geprüft —
aber er ersetzt nicht, dass man weiß, gegen welche Installation man arbeitet.

**Die Wiederherstellungsprobe ist keine Browserprüfung.**
`tools/wiederherstellungs-probe/` ruft `edbak_restore()` unmittelbar auf und
misst den Zustand in der Datenbank. Damit prüft sie genau das, was der
Kreislauf nicht herstellen kann — aber weder den Weg davor (Entschlüsseln im
Browser, Hochladen) noch die Anzeige danach. Dass der Papierkorb den einzeln
gelöschten Einsatz auch *zeigt*, folgt aus `trash_list_missions()`, der
Funktion, die die Seite benutzt; nachgesehen ist es nicht. Deshalb steht
Punkt 4.10 in der Prüfliste.

Ihr zweiter Teil prüft **zwei** Sorten unbrauchbarer Angaben (Zeitwert,
doppelte Spurnummer). Er ist **kein** Beleg dafür, dass keine dritte übrig
ist — dafür wäre die Prüfschicht Feld für Feld gegen das Datenbankschema zu
halten.

**Nicht gefahren:** der Stilvergleich (`tools/stilvergleich/`). In S1 wurde
`server/assets/style.css` nicht angefasst — keine Regel verschoben,
zusammengeführt, entfernt oder in ihrer Reihenfolge berührt.
